<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';

session_start();

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access',
        'count' => 0,
        'notifications' => []
    ]);
    exit;
}

function notification_excerpt($value, $limit = 90) {
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 3) . '...' : $value;
    }

    return strlen($value) > $limit ? substr($value, 0, $limit - 3) . '...' : $value;
}

function run_notifications_query(mysqli $db, $sql, $bindTypes = '', array $bindValues = []) {
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('Notification query failed.');
    }

    if ($bindTypes !== '') {
        $stmt->bind_param($bindTypes, ...$bindValues);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function ensure_notification_reads_table(mysqli $db) {
    $db->query("
        CREATE TABLE IF NOT EXISTS notification_reads (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notification_key VARCHAR(120) NOT NULL,
            read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_notification_read (user_id, notification_key),
            KEY idx_notification_reads_read_at (read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->query("DELETE FROM notification_reads WHERE read_at < DATE_SUB(NOW(), INTERVAL 35 DAY)");
}

try {
    ensure_notification_reads_table($db);

    $notifications = [];
    $count = 0;
    $currentUserId = (int)$_SESSION['user_id'];

    if ($auth->isVolunteer()) {
        $countRows = run_notifications_query(
            $db,
            "
                SELECT COUNT(*) AS total
                FROM volunteer_tasks
                WHERE volunteer_user_id = ?
                  AND status IN ('Assigned', 'Accepted', 'In Progress', 'Completed')
                  AND COALESCE(updated_at, assigned_at) >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                  AND NOT EXISTS (
                      SELECT 1
                      FROM notification_reads nr
                      WHERE nr.user_id = ?
                        AND nr.notification_key = CONCAT('task-', volunteer_tasks.id, '-', volunteer_tasks.status)
                  )
            ",
            'ii',
            [$currentUserId, $currentUserId]
        );
        $count = (int)($countRows[0]['total'] ?? 0);

        $rows = run_notifications_query(
            $db,
            "
                SELECT vt.id, vt.status, vt.assigned_at, vt.updated_at,
                       r.category, r.description, r.municipality
                FROM volunteer_tasks vt
                INNER JOIN reports r ON r.id = vt.report_id
                WHERE vt.volunteer_user_id = ?
                  AND vt.status IN ('Assigned', 'Accepted', 'In Progress', 'Completed')
                  AND COALESCE(vt.updated_at, vt.assigned_at) >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                  AND NOT EXISTS (
                      SELECT 1
                      FROM notification_reads nr
                      WHERE nr.user_id = ?
                        AND nr.notification_key = CONCAT('task-', vt.id, '-', vt.status)
                  )
                ORDER BY vt.updated_at DESC, vt.assigned_at DESC
                LIMIT 20
            ",
            'ii',
            [$currentUserId, $currentUserId]
        );

        $notifications = array_map(function ($row) {
            return [
                'id' => 'task-' . $row['id'] . '-' . $row['status'],
                'title' => 'Task #' . $row['id'] . ' is ' . $row['status'],
                'message' => trim(($row['category'] ?? 'Civic task') . ' in ' . ($row['municipality'] ?: 'assigned ward')),
                'meta' => notification_excerpt($row['description'] ?? ''),
                'status' => $row['status'],
                'created_at' => $row['updated_at'] ?: $row['assigned_at'],
                'url' => 'volunteers/task_view.php?id=' . (int)$row['id']
            ];
        }, $rows);
    } else {
        $where = [];
        $bindTypes = '';
        $bindValues = [];

        if ($auth->isCitizen()) {
            $where[] = "(user_id = ? OR (user_id IS NULL AND email = ?))";
            $bindTypes .= 'is';
            $bindValues[] = (int)$_SESSION['user_id'];
            $bindValues[] = $_SESSION['email'];
            $where[] = "status <> 'Resolved'";
        } elseif ($auth->isWardAdmin()) {
            $where[] = "municipality = ?";
            $bindTypes .= 's';
            $bindValues[] = strtoupper((string)$auth->getWard());
            $where[] = "status IN ('Reported', 'Acknowledged', 'In Progress')";
        } elseif ($auth->isAdmin()) {
            $where[] = "status IN ('Reported', 'Acknowledged', 'In Progress')";
        } else {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Notifications are not available for this account.',
                'count' => 0,
                'notifications' => []
            ]);
            exit;
        }

        $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $where[] = "NOT EXISTS (
            SELECT 1
            FROM notification_reads nr
            WHERE nr.user_id = ?
              AND nr.notification_key = CONCAT('report-', reports.id, '-', reports.status)
        )";
        $bindTypes .= 'i';
        $bindValues[] = $currentUserId;

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $countRows = run_notifications_query(
            $db,
            "SELECT COUNT(*) AS total FROM reports $whereClause",
            $bindTypes,
            $bindValues
        );
        $count = (int)($countRows[0]['total'] ?? 0);

        $rows = run_notifications_query(
            $db,
            "
                SELECT id, category, description, municipality, status, created_at
                FROM reports
                $whereClause
                ORDER BY created_at DESC
                LIMIT 20
            ",
            $bindTypes,
            $bindValues
        );

        $notifications = array_map(function ($row) use ($auth) {
            $isAdmin = $auth->isAdmin();

            return [
                'id' => 'report-' . $row['id'] . '-' . $row['status'],
                'title' => $isAdmin
                    ? 'Report #' . $row['id'] . ' needs review'
                    : 'Report #' . $row['id'] . ' is ' . $row['status'],
                'message' => trim(($row['category'] ?? 'Civic issue') . ' in ' . ($row['municipality'] ?: 'Bhubaneswar')),
                'meta' => notification_excerpt($row['description'] ?? ''),
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'url' => $isAdmin ? null : 'report_history.php'
            ];
        }, $rows);
    }

    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load notifications.',
        'count' => 0,
        'notifications' => []
    ]);
}

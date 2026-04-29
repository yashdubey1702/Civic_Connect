<?php
session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('volunteer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_tasks.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$taskId = (int)($_POST['task_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$note = trim($_POST['note'] ?? '');

$stmt = $db->prepare("SELECT * FROM volunteer_tasks WHERE id = ? AND volunteer_user_id = ? LIMIT 1");
$stmt->bind_param("ii", $taskId, $userId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    header("Location: my_tasks.php?error=" . rawurlencode("Task not found"));
    exit;
}

$newStatus = null;
$timeColumn = null;
$proofPath = null;

if ($action === 'accept' && $task['status'] === 'Assigned') {
    $newStatus = 'Accepted';
    $timeColumn = 'accepted_at';
} elseif ($action === 'start' && in_array($task['status'], ['Accepted', 'Rejected'], true)) {
    $newStatus = 'In Progress';
    $timeColumn = 'started_at';
} elseif ($action === 'complete' && $task['status'] === 'In Progress') {
    $newStatus = 'Completed';
    $timeColumn = 'completed_at';

    if ($note === '') {
        header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Completion note is required"));
        exit;
    }

    if (empty($_FILES['proof_image']['name']) && empty($task['proof_image'])) {
        header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Proof image is required"));
        exit;
    }

    if (!empty($_FILES['proof_image']['name'])) {
        if (($_FILES['proof_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Proof upload failed"));
            exit;
        }

        if (($_FILES['proof_image']['size'] ?? 0) > 5 * 1024 * 1024) {
            header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Proof image must be 5MB or smaller"));
            exit;
        }

        if (!is_uploaded_file($_FILES['proof_image']['tmp_name'])) {
            header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Invalid proof upload"));
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/volunteer_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $_FILES['proof_image']['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!array_key_exists($mime, $allowedTypes)) {
            header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Only JPG, PNG, and WEBP proof images are allowed"));
            exit;
        }

        if (@getimagesize($_FILES['proof_image']['tmp_name']) === false) {
            header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Invalid image file"));
            exit;
        }

        $filename = 'proof_' . $taskId . '_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mime];
        $target = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['proof_image']['tmp_name'], $target)) {
            header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Proof upload failed"));
            exit;
        }

        $proofPath = 'uploads/volunteer_proofs/' . $filename;
    }
} else {
    header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Invalid task action"));
    exit;
}

$db->begin_transaction();

try {
    $oldStatus = $task['status'];

    if ($newStatus === 'Completed') {
        $stmt = $db->prepare("
            UPDATE volunteer_tasks
            SET status = ?, completion_note = ?, proof_image = COALESCE(?, proof_image), admin_review_note = NULL, $timeColumn = NOW()
            WHERE id = ? AND volunteer_user_id = ?
        ");
        $stmt->bind_param("sssii", $newStatus, $note, $proofPath, $taskId, $userId);
    } else {
        $stmt = $db->prepare("
            UPDATE volunteer_tasks
            SET status = ?, $timeColumn = NOW()
            WHERE id = ? AND volunteer_user_id = ?
        ");
        $stmt->bind_param("sii", $newStatus, $taskId, $userId);
    }

    if (!$stmt->execute()) {
        throw new RuntimeException("Task update failed.");
    }
    $stmt->close();

    addVolunteerTaskUpdate($db, $taskId, $userId, $oldStatus, $newStatus, $note ?: null, $proofPath);
    updateReportStatusForVolunteerTask($db, (int)$task['report_id'], $newStatus);

    $db->commit();
    header("Location: task_view.php?id={$taskId}&success=" . rawurlencode("Task updated"));
    exit;
} catch (Throwable $e) {
    $db->rollback();
    header("Location: task_view.php?id={$taskId}&error=" . rawurlencode("Unable to update task"));
    exit;
}

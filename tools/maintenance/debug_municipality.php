<?php
require_once __DIR__ . '/../../app/Core/Database.php';
require_once __DIR__ . '/../../app/Services/BhubaneswarDetector.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    require_once __DIR__ . '/../../app/Core/Auth.php';

    $databaseForAuth = new Database();
    $authDb = $databaseForAuth->getConnection();
    $auth = new Auth($authDb);
    $auth->requireAuth('super_admin');
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeWard($ward) {
    return strtolower(trim((string)$ward));
}

function normalizeWardInput($value) {
    $value = strtoupper(trim((string)$value));
    if (preg_match('/^W?([1-9][0-9]{0,2})$/', $value, $matches)) {
        return 'W' . $matches[1];
    }

    return '';
}

function queryAll($db, $sql) {
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Database prepare failed: ' . $db->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

function getWardAdmins($db) {
    $stmt = $db->prepare("
        SELECT id, full_name, email, UPPER(ward) AS ward_label, is_active, last_login, created_at
        FROM users
        WHERE user_type = 'ward_admin'
        ORDER BY CAST(SUBSTRING(ward, 2) AS UNSIGNED), ward ASC
    ");
    if (!$stmt) {
        throw new RuntimeException('Ward admin query failed: ' . $db->error);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function handleWardAccessAction($db, $action, $post) {
    if ($action === 'add_ward') {
        $wardLabel = normalizeWardInput($post['ward'] ?? '');
        $wardValue = strtolower($wardLabel);
        $adminName = trim($post['full_name'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = (string)($post['password'] ?? '');

        if ($wardLabel === '') {
            throw new RuntimeException('Enter a valid ward, for example W12.');
        }
        if ($adminName === '') {
            throw new RuntimeException('Ward admin name is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid ward admin email.');
        }

        $stmt = $db->prepare("
            SELECT id
            FROM users
            WHERE LOWER(email) = LOWER(?)
              AND NOT (user_type = 'ward_admin' AND LOWER(ward) = LOWER(?))
            LIMIT 1
        ");
        if (!$stmt) {
            throw new RuntimeException('Email check failed.');
        }
        $stmt->bind_param('ss', $email, $wardValue);
        $stmt->execute();
        $stmt->store_result();
        $emailExists = $stmt->num_rows > 0;
        $stmt->close();

        if ($emailExists) {
            throw new RuntimeException('That email is already used by another account.');
        }

        $stmt = $db->prepare("
            SELECT id, password_hash
            FROM users
            WHERE user_type = 'ward_admin' AND LOWER(ward) = LOWER(?)
            LIMIT 1
        ");
        if (!$stmt) {
            throw new RuntimeException('Ward lookup failed.');
        }
        $stmt->bind_param('s', $wardValue);
        $stmt->execute();
        $existingWardAdmin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existingWardAdmin && strlen($password) < 6) {
            throw new RuntimeException('Password must be at least 6 characters for a new ward admin.');
        }

        $passwordHash = $password !== ''
            ? password_hash($password, PASSWORD_DEFAULT)
            : $existingWardAdmin['password_hash'];

        if ($existingWardAdmin) {
            $stmt = $db->prepare("
                UPDATE users
                SET full_name = ?, email = ?, password_hash = ?, ward = ?, is_active = 1
                WHERE id = ? AND user_type = 'ward_admin'
            ");
            if (!$stmt) {
                throw new RuntimeException('Ward admin restore failed.');
            }
            $wardAdminId = (int)$existingWardAdmin['id'];
            $stmt->bind_param('ssssi', $adminName, $email, $passwordHash, $wardValue, $wardAdminId);
            $stmt->execute();
            $stmt->close();

            return "{$wardLabel} ward admin access has been restored.";
        }

        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, password_hash, user_type, ward, is_active)
            VALUES (?, ?, ?, 'ward_admin', ?, 1)
        ");
        if (!$stmt) {
            throw new RuntimeException('Ward admin creation failed.');
        }
        $stmt->bind_param('ssss', $adminName, $email, $passwordHash, $wardValue);
        $stmt->execute();
        $stmt->close();

        return "{$wardLabel} ward admin access has been added.";
    }

    if ($action === 'remove_ward') {
        $wardAdminId = (int)($post['ward_admin_id'] ?? 0);
        if ($wardAdminId <= 0) {
            throw new RuntimeException('Invalid ward admin selected.');
        }

        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND user_type = 'ward_admin'");
        if (!$stmt) {
            throw new RuntimeException('Ward admin removal failed.');
        }
        $stmt->bind_param('i', $wardAdminId);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$removed) {
            throw new RuntimeException('Ward admin was not found or is already removed.');
        }

        return 'Ward admin access has been removed.';
    }

    throw new RuntimeException('Unknown ward access action.');
}

function ringCentroid($ring) {
    $area = 0.0;
    $centerLng = 0.0;
    $centerLat = 0.0;
    $count = count($ring);

    if ($count < 3) {
        return null;
    }

    for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
        $lng1 = (float)$ring[$j][0];
        $lat1 = (float)$ring[$j][1];
        $lng2 = (float)$ring[$i][0];
        $lat2 = (float)$ring[$i][1];
        $factor = ($lng1 * $lat2) - ($lng2 * $lat1);

        $area += $factor;
        $centerLng += ($lng1 + $lng2) * $factor;
        $centerLat += ($lat1 + $lat2) * $factor;
    }

    $area *= 0.5;
    if (abs($area) < 0.0000001) {
        return null;
    }

    return [
        'lat' => $centerLat / (6 * $area),
        'lng' => $centerLng / (6 * $area)
    ];
}

function averagePoint($ring) {
    $lat = 0.0;
    $lng = 0.0;
    $count = 0;

    foreach ($ring as $point) {
        if (!isset($point[0], $point[1])) {
            continue;
        }
        $lng += (float)$point[0];
        $lat += (float)$point[1];
        $count++;
    }

    if ($count === 0) {
        return null;
    }

    return [
        'lat' => $lat / $count,
        'lng' => $lng / $count
    ];
}

function gridPointCandidates($ring, $steps = 12) {
    $minLat = null;
    $maxLat = null;
    $minLng = null;
    $maxLng = null;

    foreach ($ring as $point) {
        if (!isset($point[0], $point[1])) {
            continue;
        }

        $lng = (float)$point[0];
        $lat = (float)$point[1];
        $minLat = $minLat === null ? $lat : min($minLat, $lat);
        $maxLat = $maxLat === null ? $lat : max($maxLat, $lat);
        $minLng = $minLng === null ? $lng : min($minLng, $lng);
        $maxLng = $maxLng === null ? $lng : max($maxLng, $lng);
    }

    if ($minLat === null || $minLng === null || $minLat === $maxLat || $minLng === $maxLng) {
        return [];
    }

    $candidates = [];
    for ($row = 1; $row < $steps; $row++) {
        for ($col = 1; $col < $steps; $col++) {
            $candidates[] = [
                'lat' => $minLat + (($maxLat - $minLat) * ($row / $steps)),
                'lng' => $minLng + (($maxLng - $minLng) * ($col / $steps)),
            ];
        }
    }

    return $candidates;
}

function getPolygonRings($geometry) {
    if (!isset($geometry['type'], $geometry['coordinates'])) {
        return [];
    }

    if ($geometry['type'] === 'Polygon') {
        return [$geometry['coordinates'][0] ?? []];
    }

    if ($geometry['type'] === 'MultiPolygon') {
        $rings = [];
        foreach ($geometry['coordinates'] as $polygon) {
            $rings[] = $polygon[0] ?? [];
        }
        return $rings;
    }

    return [];
}

function chooseTestPoint($geometry, $expectedWard, $wardDetector) {
    $fallback = null;

    foreach (getPolygonRings($geometry) as $ring) {
        $candidates = [
            ringCentroid($ring),
            averagePoint($ring),
        ];

        foreach (gridPointCandidates($ring) as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            $fallback = $fallback ?: $candidate;
            $detected = $wardDetector->detectWard($candidate['lat'], $candidate['lng']);
            if (normalizeWard($detected) === normalizeWard($expectedWard)) {
                return $candidate;
            }
        }
    }

    return $fallback;
}

function buildDiagnostics($db, $wardDetector) {
    $wards = $wardDetector->getAllWards();
    $reports = queryAll(
        $db,
        "SELECT id, latitude, longitude, municipality, category, description, created_at
         FROM reports
         ORDER BY created_at DESC"
    );

    $wardChecks = [];
    $wardFailures = 0;
    foreach ($wards as $key => $ward) {
        $point = chooseTestPoint($ward['geometry'], $ward['ward_no'], $wardDetector);
        $detected = $point ? $wardDetector->detectWard($point['lat'], $point['lng']) : null;
        $passed = normalizeWard($detected) === normalizeWard($ward['ward_no']);

        if (!$passed) {
            $wardFailures++;
        }

        $wardChecks[] = [
            'ward' => $ward['ward_no'],
            'zone' => $ward['zone'],
            'lat' => $point['lat'] ?? null,
            'lng' => $point['lng'] ?? null,
            'detected' => $detected,
            'passed' => $passed,
        ];
    }

    $totalReports = count($reports);
    $missingWard = 0;
    $mismatchedWard = 0;
    $outsideBoundary = 0;
    $correctWard = 0;
    $issueRows = [];
    $recentRows = array_slice($reports, 0, 20);
    $wardDistribution = [];

    foreach ($reports as $report) {
        $storedWard = normalizeWard($report['municipality'] ?? '');
        $detectedWard = $wardDetector->detectWard((float)$report['latitude'], (float)$report['longitude']);
        $detectedKey = normalizeWard($detectedWard ?? '');
        $issue = null;

        if ($detectedWard === null) {
            $outsideBoundary++;
            $issue = 'Outside boundary or not detected';
        } elseif ($storedWard === '') {
            $missingWard++;
            $issue = 'Missing ward';
        } elseif ($storedWard !== $detectedKey) {
            $mismatchedWard++;
            $issue = 'Stored ward mismatch';
        } else {
            $correctWard++;
        }

        if ($detectedWard !== null) {
            $wardDistribution[$detectedWard] = ($wardDistribution[$detectedWard] ?? 0) + 1;
        }

        if ($issue && count($issueRows) < 30) {
            $issueRows[] = $report + [
                'detected_ward' => $detectedWard,
                'issue' => $issue,
            ];
        }
    }

    ksort($wardDistribution, SORT_NATURAL);

    return [
        'wards' => $wards,
        'ward_checks' => $wardChecks,
        'ward_failures' => $wardFailures,
        'reports' => $reports,
        'recent_reports' => $recentRows,
        'issue_rows' => $issueRows,
        'ward_distribution' => $wardDistribution,
        'summary' => [
            'total_reports' => $totalReports,
            'total_wards' => count($wards),
            'missing_ward' => $missingWard,
            'mismatched_ward' => $mismatchedWard,
            'outside_boundary' => $outsideBoundary,
            'correct_ward' => $correctWard,
            'repairable' => $missingWard + $mismatchedWard,
        ],
    ];
}

function repairReports($db, $wardDetector, $mode) {
    $reports = queryAll(
        $db,
        "SELECT id, latitude, longitude, municipality
         FROM reports
         ORDER BY created_at DESC"
    );

    $updated = 0;
    $skipped = 0;
    $outside = 0;
    $errors = 0;
    $logs = [];

    foreach ($reports as $report) {
        $detectedWard = $wardDetector->detectWard((float)$report['latitude'], (float)$report['longitude']);
        $storedWard = normalizeWard($report['municipality'] ?? '');
        $detectedKey = normalizeWard($detectedWard ?? '');

        if ($detectedWard === null) {
            $outside++;
            continue;
        }

        $shouldUpdate = false;
        if ($mode === 'missing') {
            $shouldUpdate = $storedWard === '';
        } elseif ($mode === 'sync') {
            $shouldUpdate = $storedWard !== $detectedKey;
        }

        if (!$shouldUpdate) {
            $skipped++;
            continue;
        }

        $stmt = $db->prepare("UPDATE reports SET municipality = ? WHERE id = ?");
        if (!$stmt) {
            $errors++;
            $logs[] = 'Report #' . $report['id'] . ': update prepare failed.';
            continue;
        }

        $id = (int)$report['id'];
        $stmt->bind_param('si', $detectedWard, $id);

        if ($stmt->execute()) {
            $updated++;
            if (count($logs) < 12) {
                $logs[] = 'Report #' . $id . ' set to ' . $detectedWard . '.';
            }
        } else {
            $errors++;
            $logs[] = 'Report #' . $id . ': update failed.';
        }

        $stmt->close();
    }

    return [
        'updated' => $updated,
        'skipped' => $skipped,
        'outside' => $outside,
        'errors' => $errors,
        'logs' => $logs,
    ];
}

$database = new Database();
$db = $database->getConnection();
$wardDetector = new BhubaneswarWardDetector();
$flash = null;

if (!$isCli) {
    if (empty($_SESSION['maintenance_csrf'])) {
        $_SESSION['maintenance_csrf'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';
        $action = $_POST['action'] ?? '';

        if (!hash_equals($_SESSION['maintenance_csrf'], $csrf)) {
            $flash = [
                'type' => 'danger',
                'title' => 'Action blocked',
                'message' => 'Security token expired. Refresh the page and try again.',
            ];
        } elseif ($action === 'repair_missing' || $action === 'sync_mismatched') {
            $repair = repairReports($db, $wardDetector, $action === 'repair_missing' ? 'missing' : 'sync');
            $flash = [
                'type' => $repair['errors'] > 0 ? 'warning' : 'success',
                'title' => $action === 'repair_missing' ? 'Missing wards repaired' : 'Ward values synchronized',
                'message' => "{$repair['updated']} updated, {$repair['skipped']} already correct, {$repair['outside']} outside boundary, {$repair['errors']} errors.",
                'logs' => $repair['logs'],
            ];
        } elseif ($action === 'add_ward' || $action === 'remove_ward') {
            try {
                $message = handleWardAccessAction($db, $action, $_POST);
                $flash = [
                    'type' => 'success',
                    'title' => 'Ward access updated',
                    'message' => $message,
                ];
            } catch (Throwable $e) {
                $flash = [
                    'type' => 'danger',
                    'title' => 'Ward access update failed',
                    'message' => $e->getMessage(),
                ];
            }
        }
    }
}

$diagnostics = buildDiagnostics($db, $wardDetector);
$summary = $diagnostics['summary'];
$statusLevel = 'healthy';
$statusText = 'System healthy';

if ($summary['repairable'] > 0 || $diagnostics['ward_failures'] > 0) {
    $statusLevel = 'warning';
    $statusText = 'Needs attention';
}

if ($summary['total_wards'] === 0) {
    $statusLevel = 'danger';
    $statusText = 'GeoJSON not loaded';
}

if ($isCli) {
    echo "=== Municipality Maintenance Diagnostic ===\n";
    echo "Status: {$statusText}\n";
    echo "Wards loaded: {$summary['total_wards']}\n";
    echo "Ward geometry failures: {$diagnostics['ward_failures']}\n";
    echo "Reports: {$summary['total_reports']}\n";
    echo "Correct wards: {$summary['correct_ward']}\n";
    echo "Missing wards: {$summary['missing_ward']}\n";
    echo "Mismatched wards: {$summary['mismatched_ward']}\n";
    echo "Outside boundary: {$summary['outside_boundary']}\n";
    echo "\nRun from browser as super admin for one-click repairs.\n";
    exit;
}

$csrfToken = $_SESSION['maintenance_csrf'];
$wardAdmins = getWardAdmins($db);
$activeWardCount = count(array_filter($wardAdmins, static fn($wardAdmin) => (int)$wardAdmin['is_active'] === 1));
$removedWardCount = count($wardAdmins) - $activeWardCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipality Maintenance | Town Issues</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #f3f6fb;
            --panel: #ffffff;
            --soft: #f8fafc;
            --line: #dbe3ef;
            --text: #122033;
            --muted: #64748b;
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --green: #16a34a;
            --orange: #f97316;
            --red: #dc2626;
            --shadow: 0 20px 60px rgba(15, 23, 42, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: 'Inter', Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 32%),
                linear-gradient(135deg, #f8fbff 0%, var(--bg) 100%);
        }

        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 48px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
        }

        .brand img {
            width: 42px;
            height: 42px;
            padding: 7px;
            object-fit: contain;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid var(--line);
        }

        .nav-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 16px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            color: var(--text);
            background: #ffffff;
            border: 1px solid var(--line);
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .btn-primary {
            color: #ffffff;
            background: var(--blue);
            border-color: var(--blue);
        }

        .btn-primary:hover {
            background: var(--blue-dark);
        }

        .btn-warning {
            color: #ffffff;
            background: var(--orange);
            border-color: var(--orange);
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.58;
            transform: none;
            box-shadow: none;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .hero-card,
        .status-card,
        .panel,
        .stat-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #cfd9e8;
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .hero-card {
            padding: 30px;
        }

        .eyebrow {
            margin: 0 0 10px;
            color: var(--blue);
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        h1 {
            margin: 0;
            font-size: clamp(2rem, 5vw, 3.5rem);
            line-height: 1.02;
            letter-spacing: 0;
        }

        .lead {
            margin: 16px 0 0;
            max-width: 720px;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.7;
        }

        .status-card {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 18px;
        }

        .status-pill {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.9rem;
        }

        .status-pill.healthy {
            color: #166534;
            background: #dcfce7;
        }

        .status-pill.warning {
            color: #9a3412;
            background: #ffedd5;
        }

        .status-pill.danger {
            color: #991b1b;
            background: #fee2e2;
        }

        .status-number {
            margin: 0;
            font-size: 3rem;
            line-height: 1;
            font-weight: 800;
        }

        .status-copy {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .flash {
            margin-bottom: 18px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: #ffffff;
        }

        .flash.success {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .flash.warning {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        .flash.danger {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .flash h2 {
            margin: 0 0 6px;
            font-size: 1rem;
        }

        .flash p,
        .flash ul {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .flash ul {
            margin-top: 10px;
            padding-left: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .stat-card {
            padding: 18px;
        }

        .stat-card span {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .stat-card strong {
            display: block;
            margin-top: 8px;
            font-size: 2rem;
            line-height: 1;
        }

        .automation {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 18px;
        }

        .ward-admin-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .panel {
            padding: 22px;
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .panel h2 {
            margin: 0;
            font-size: 1.15rem;
        }

        .panel p {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field label {
            color: #475569;
            font-size: 0.86rem;
            font-weight: 800;
        }

        .field input {
            width: 100%;
            min-height: 46px;
            padding: 0 13px;
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--text);
            background: #ffffff;
            font: inherit;
            outline: none;
        }

        .field input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        th,
        td {
            padding: 13px 14px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
            vertical-align: top;
        }

        th {
            color: #475569;
            background: var(--soft);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .text-cell {
            max-width: 360px;
            white-space: normal;
            color: var(--muted);
            line-height: 1.45;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .badge.good {
            color: #166534;
            background: #dcfce7;
        }

        .badge.warn {
            color: #9a3412;
            background: #ffedd5;
        }

        .badge.bad {
            color: #991b1b;
            background: #fee2e2;
        }

        .btn-danger {
            min-height: 38px;
            padding: 0 12px;
            color: #ffffff;
            background: var(--red);
            border-color: var(--red);
            font-size: 0.86rem;
        }

        .muted {
            color: var(--muted);
        }

        .stack {
            display: grid;
            gap: 18px;
        }

        .distribution {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
            gap: 10px;
        }

        .ward-chip {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--soft);
            font-weight: 800;
        }

        .ward-chip span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.84rem;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .hero,
            .automation,
            .ward-admin-summary,
            .form-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-actions,
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="topbar">
            <div class="brand">
                <img src="/town_issues/assets/images/BRP.png" alt="Town Issues logo">
                <span>Town Issues Maintenance</span>
            </div>
            <nav class="nav-actions" aria-label="Maintenance navigation">
                <a class="btn" href="/town_issues/admin_dashboard.php">
                    <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
                    Dashboard
                </a>
                <a class="btn" href="#ward-access">
                    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                    Ward Access
                </a>
            </nav>
        </header>

        <?php if ($flash): ?>
            <section class="flash <?php echo h($flash['type']); ?>" role="status">
                <h2><?php echo h($flash['title']); ?></h2>
                <p><?php echo h($flash['message']); ?></p>
                <?php if (!empty($flash['logs'])): ?>
                    <ul>
                        <?php foreach ($flash['logs'] as $log): ?>
                            <li><?php echo h($log); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="hero">
            <div class="hero-card">
                <p class="eyebrow">Automated Municipality Diagnostics</p>
                <h1>Find and repair ward mapping issues faster.</h1>
                <p class="lead">
                    This screen checks the GeoJSON ward file, validates detection logic, scans reports,
                    and gives you safe repair actions for missing or incorrect report wards.
                </p>
            </div>

            <aside class="status-card">
                <span class="status-pill <?php echo h($statusLevel); ?>">
                    <i class="fa-solid <?php echo $statusLevel === 'healthy' ? 'fa-circle-check' : ($statusLevel === 'danger' ? 'fa-circle-xmark' : 'fa-triangle-exclamation'); ?>" aria-hidden="true"></i>
                    <?php echo h($statusText); ?>
                </span>
                <div>
                    <p class="status-number"><?php echo h($summary['repairable']); ?></p>
                    <p class="status-copy">report ward values can be repaired automatically.</p>
                </div>
            </aside>
        </section>

        <section class="stats-grid" aria-label="Maintenance summary">
            <div class="stat-card">
                <span>Wards Loaded</span>
                <strong><?php echo h($summary['total_wards']); ?></strong>
            </div>
            <div class="stat-card">
                <span>Reports Checked</span>
                <strong><?php echo h($summary['total_reports']); ?></strong>
            </div>
            <div class="stat-card">
                <span>Missing Ward</span>
                <strong><?php echo h($summary['missing_ward']); ?></strong>
            </div>
            <div class="stat-card">
                <span>Mismatched Ward</span>
                <strong><?php echo h($summary['mismatched_ward']); ?></strong>
            </div>
        </section>

        <section class="automation">
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Repair missing report wards</h2>
                        <p>Only fills blank municipality values when the report location is inside a detected ward.</p>
                    </div>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="action" value="repair_missing">
                    <button class="btn btn-primary" type="submit" <?php echo $summary['missing_ward'] === 0 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                        Auto Repair Missing
                    </button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Sync mismatched report wards</h2>
                        <p>Updates stored municipality values when GeoJSON detection says the report belongs to another ward.</p>
                    </div>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="action" value="sync_mismatched">
                    <button class="btn btn-warning" type="submit" <?php echo ($summary['missing_ward'] + $summary['mismatched_ward']) === 0 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                        Sync All Repairable
                    </button>
                </form>
            </div>
        </section>

        <div class="stack">
            <section class="panel" id="ward-access">
                <div class="panel-header">
                    <div>
                        <h2>Ward access maintenance</h2>
                        <p>Add, restore, or remove ward admin access from the same maintenance workspace.</p>
                    </div>
                    <span class="badge good"><?php echo h($activeWardCount); ?> active</span>
                </div>

                <section class="stats-grid ward-admin-summary" aria-label="Ward access summary">
                    <div class="stat-card">
                        <span>Configured Ward Admins</span>
                        <strong><?php echo h(count($wardAdmins)); ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Active Access</span>
                        <strong><?php echo h($activeWardCount); ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Removed Access</span>
                        <strong><?php echo h($removedWardCount); ?></strong>
                    </div>
                </section>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                    <input type="hidden" name="action" value="add_ward">
                    <div class="form-grid">
                        <div class="field">
                            <label for="ward">Ward</label>
                            <input id="ward" name="ward" placeholder="Example: W12" required>
                        </div>
                        <div class="field">
                            <label for="full_name">Ward Admin Name</label>
                            <input id="full_name" name="full_name" placeholder="Ward 12 Admin" required>
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" placeholder="ward12@bmc.gov.in" required>
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" placeholder="Required for new ward">
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Add or Restore Ward Access
                    </button>
                </form>

                <div class="table-wrap" style="margin-top:18px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Ward</th>
                                <th>Admin</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($wardAdmins)): ?>
                                <tr>
                                    <td colspan="6">No ward admin access has been configured yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($wardAdmins as $wardAdmin): ?>
                                    <tr>
                                        <td><strong><?php echo h($wardAdmin['ward_label']); ?></strong></td>
                                        <td><?php echo h($wardAdmin['full_name']); ?></td>
                                        <td><?php echo h($wardAdmin['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo (int)$wardAdmin['is_active'] === 1 ? 'good' : 'bad'; ?>">
                                                <?php echo (int)$wardAdmin['is_active'] === 1 ? 'Active' : 'Removed'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo h($wardAdmin['last_login'] ?: '-'); ?></td>
                                        <td>
                                            <?php if ((int)$wardAdmin['is_active'] === 1): ?>
                                                <form method="post" onsubmit="return confirm('Remove access for <?php echo h($wardAdmin['ward_label']); ?>?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="remove_ward">
                                                    <input type="hidden" name="ward_admin_id" value="<?php echo (int)$wardAdmin['id']; ?>">
                                                    <button class="btn btn-danger" type="submit">
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                        Remove
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">Add again to restore</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Reports needing attention</h2>
                        <p>Showing the latest 30 reports that are missing, mismatched, or outside the ward boundary.</p>
                    </div>
                    <span class="badge <?php echo empty($diagnostics['issue_rows']) ? 'good' : 'warn'; ?>">
                        <?php echo empty($diagnostics['issue_rows']) ? 'Clean' : h(count($diagnostics['issue_rows']) . ' shown'); ?>
                    </span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Issue</th>
                                <th>Stored</th>
                                <th>Detected</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($diagnostics['issue_rows'])): ?>
                                <tr>
                                    <td colspan="7">No report ward issues found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($diagnostics['issue_rows'] as $report): ?>
                                    <tr>
                                        <td>#<?php echo h($report['id']); ?></td>
                                        <td><span class="badge warn"><?php echo h($report['issue']); ?></span></td>
                                        <td><?php echo h($report['municipality'] ?: 'Blank'); ?></td>
                                        <td><?php echo h($report['detected_ward'] ?: 'Not detected'); ?></td>
                                        <td><?php echo h($report['category']); ?></td>
                                        <td class="text-cell"><?php echo h(mb_strimwidth((string)($report['description'] ?? ''), 0, 95, '...')); ?></td>
                                        <td><?php echo h($report['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Ward detection self-test</h2>
                        <p>Each ward is tested with a calculated internal point from the GeoJSON geometry.</p>
                    </div>
                    <span class="badge <?php echo $diagnostics['ward_failures'] === 0 ? 'good' : 'bad'; ?>">
                        <?php echo h($diagnostics['ward_failures']); ?> failed
                    </span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Ward</th>
                                <th>Zone</th>
                                <th>Test Lat</th>
                                <th>Test Lng</th>
                                <th>Detected</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($diagnostics['ward_checks'])): ?>
                                <tr>
                                    <td colspan="6">No wards loaded. Check data/Wards.geojson.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($diagnostics['ward_checks'] as $wardCheck): ?>
                                    <tr>
                                        <td><strong><?php echo h($wardCheck['ward']); ?></strong></td>
                                        <td><?php echo h($wardCheck['zone']); ?></td>
                                        <td><?php echo h($wardCheck['lat'] === null ? '-' : number_format((float)$wardCheck['lat'], 7)); ?></td>
                                        <td><?php echo h($wardCheck['lng'] === null ? '-' : number_format((float)$wardCheck['lng'], 7)); ?></td>
                                        <td><?php echo h($wardCheck['detected'] ?: 'Not detected'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $wardCheck['passed'] ? 'good' : 'bad'; ?>">
                                                <?php echo $wardCheck['passed'] ? 'Pass' : 'Fail'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Detected report distribution</h2>
                        <p>Counts are calculated from current report coordinates, not only stored database values.</p>
                    </div>
                </div>
                <div class="distribution">
                    <?php if (empty($diagnostics['ward_distribution'])): ?>
                        <p>No reports are currently detected inside any ward.</p>
                    <?php else: ?>
                        <?php foreach ($diagnostics['ward_distribution'] as $ward => $count): ?>
                            <div class="ward-chip">
                                <?php echo h($ward); ?>
                                <span><?php echo h($count); ?> reports</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>

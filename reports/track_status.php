<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';

$token = strtoupper(trim($_POST['tracking_token'] ?? $_GET['tracking_token'] ?? ''));

if ($token === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your tracking token.'
    ]);
    exit;
}

if (!preg_match('/^CC-[A-F0-9]{8}$/', $token)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid tracking token format.'
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT status FROM reports WHERE tracking_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    echo json_encode([
        'success' => false,
        'message' => 'No report found for this token.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'status' => $report['status']
]);

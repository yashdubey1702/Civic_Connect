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
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notificationKey = trim((string)($input['notification_id'] ?? ''));

if (!preg_match('/^(report|task)-\d+-[A-Za-z ]+$/', $notificationKey)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification.'
    ]);
    exit;
}

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

$stmt = $db->prepare("
    INSERT INTO notification_reads (user_id, notification_key, read_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to mark notification.'
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt->bind_param('is', $userId, $notificationKey);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success
]);

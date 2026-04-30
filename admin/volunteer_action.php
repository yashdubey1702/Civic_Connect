<?php

session_start();
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Support/volunteer.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireAuth('any_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /town_issues/admin/volunteers.php");
    exit;
}

$profileId = (int)($_POST['profile_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$adminNote = trim($_POST['admin_note'] ?? '');

$statusMap = [
    'approve' => 'Approved',
    'reject' => 'Rejected',
    'suspend' => 'Suspended',
    'restore' => 'Approved'
];

if ($profileId <= 0 || !isset($statusMap[$action]) || !adminCanAccessVolunteer($db, $auth, $profileId)) {
    header("Location: /town_issues/admin/volunteers.php?error=" . rawurlencode("Invalid volunteer action"));
    exit;
}

$newStatus = $statusMap[$action];
$adminId = (int)$_SESSION['user_id'];

if ($newStatus === 'Approved') {
    $stmt = $db->prepare("
        UPDATE volunteer_profiles
        SET status = ?, admin_note = ?, approved_by = ?, approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ssii", $newStatus, $adminNote, $adminId, $profileId);
} else {
    $stmt = $db->prepare("
        UPDATE volunteer_profiles
        SET status = ?, admin_note = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $newStatus, $adminNote, $profileId);
}

$success = $stmt->execute();
$stmt->close();

if (!$success) {
    header("Location: /town_issues/admin/volunteer_view.php?id={$profileId}&error=" . rawurlencode("Unable to update volunteer"));
    exit;
}

header("Location: /town_issues/admin/volunteer_view.php?id={$profileId}&success=" . rawurlencode("Volunteer updated"));
exit;

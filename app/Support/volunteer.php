<?php

// Escapes output before rendering it in HTML.
function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Converts a volunteer status into a CSS class name.
function volunteerStatusClass($status)
{
    return strtolower(str_replace(' ', '-', (string)$status));
}

// Formats a database datetime for display.
function formatDateTime($value)
{
    if (empty($value)) {
        return '-';
    }

    return date('M j, Y g:i A', strtotime($value));
}

// Shortens long text for compact table and card views.
function truncateText($value, $length = 80)
{
    $text = (string)$value;

    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, max(0, $length - 3)) . '...';
}

// Restricts the current page to approved volunteer users.
function requireVolunteer($auth)
{
    $auth->requireAuth();

    if (($auth->getRole() ?? '') !== 'volunteer') {
        header("Location: /town_issues/public/unauthorized.php");
        exit;
    }
}

// Loads the volunteer profile for a user.
function getVolunteerProfile($db, $userId)
{
    $stmt = $db->prepare("
        SELECT vp.*, u.email, u.full_name AS user_full_name
        FROM volunteer_profiles vp
        INNER JOIN users u ON u.id = vp.user_id
        WHERE vp.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $profile;
}

// Creates a volunteer profile when one does not already exist.
function ensureVolunteerProfile($db, $userId, $fullName)
{
    $profile = getVolunteerProfile($db, $userId);

    if ($profile) {
        return $profile;
    }

    $stmt = $db->prepare("
        INSERT INTO volunteer_profiles (user_id, full_name, status)
        VALUES (?, ?, 'Pending')
    ");
    $stmt->bind_param("is", $userId, $fullName);
    $stmt->execute();
    $stmt->close();

    return getVolunteerProfile($db, $userId);
}

// Checks whether the admin can view or update a report.
function adminCanAccessReport($db, $auth, $reportId)
{
    if (!$auth->isAdmin()) {
        return false;
    }

    if (!$auth->isWardAdmin()) {
        return true;
    }

    $ward = strtoupper((string)$auth->getWard());
    $stmt = $db->prepare("SELECT id FROM reports WHERE id = ? AND UPPER(municipality) = ? LIMIT 1");
    $stmt->bind_param("is", $reportId, $ward);
    $stmt->execute();
    $stmt->store_result();
    $allowed = $stmt->num_rows === 1;
    $stmt->close();

    return $allowed;
}

// Checks whether the admin can view or update a volunteer task.
function adminCanAccessTask($db, $auth, $taskId)
{
    if (!$auth->isAdmin()) {
        return false;
    }

    if (!$auth->isWardAdmin()) {
        return true;
    }

    $ward = strtoupper((string)$auth->getWard());
    $stmt = $db->prepare("
        SELECT vt.id
        FROM volunteer_tasks vt
        INNER JOIN reports r ON r.id = vt.report_id
        WHERE vt.id = ? AND UPPER(r.municipality) = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $taskId, $ward);
    $stmt->execute();
    $stmt->store_result();
    $allowed = $stmt->num_rows === 1;
    $stmt->close();

    return $allowed;
}

// Checks whether the admin can view or update a volunteer profile.
function adminCanAccessVolunteer($db, $auth, $profileId)
{
    if (!$auth->isAdmin()) {
        return false;
    }

    if (!$auth->isWardAdmin()) {
        return true;
    }

    $ward = strtoupper((string)$auth->getWard());
    $stmt = $db->prepare("SELECT id FROM volunteer_profiles WHERE id = ? AND UPPER(ward_no) = ? LIMIT 1");
    $stmt->bind_param("is", $profileId, $ward);
    $stmt->execute();
    $stmt->store_result();
    $allowed = $stmt->num_rows === 1;
    $stmt->close();

    return $allowed;
}

// Records a status change for a volunteer task.
function addVolunteerTaskUpdate($db, $taskId, $userId, $oldStatus, $newStatus, $note = null, $imagePath = null)
{
    $stmt = $db->prepare("
        INSERT INTO volunteer_task_updates
            (task_id, user_id, old_status, new_status, note, image_path)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissss", $taskId, $userId, $oldStatus, $newStatus, $note, $imagePath);
    $stmt->execute();
    $stmt->close();
}

// Keeps the report status aligned with the volunteer task status.
function updateReportStatusForVolunteerTask($db, $reportId, $taskStatus)
{
    $reportStatus = null;

    if ($taskStatus === 'In Progress') {
        $reportStatus = 'In Progress';
    } elseif ($taskStatus === 'Verified') {
        $reportStatus = 'Resolved';
    }

    if ($reportStatus === null) {
        return;
    }

    $stmt = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $reportStatus, $reportId);
    $stmt->execute();
    $stmt->close();
}

// Builds the HTML badge used to show a status.
function badge($status)
{
    return '<span class="status-badge status-' . h(volunteerStatusClass($status)) . '">' . h($status) . '</span>';
}

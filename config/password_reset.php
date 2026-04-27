<?php

const RESET_OTP_TTL_MINUTES = 5;
const RESET_OTP_WINDOW_MINUTES = 15;
const RESET_OTP_MAX_PER_WINDOW = 5;
const RESET_OTP_COOLDOWN_SECONDS = 60;

function maskResetEmail($email) {
    $email = trim((string)$email);
    $parts = explode('@', $email, 2);

    if (count($parts) !== 2 || $parts[0] === '') {
        return 'your email address';
    }

    $local = $parts[0];
    $domain = $parts[1];
    $visible = substr($local, 0, 1);

    return $visible . str_repeat('*', max(3, strlen($local) - 1)) . '@' . $domain;
}

function passwordResetUserExists($db, $email) {
    $query = "SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows === 1;
    $stmt->close();

    return $exists;
}

function cleanupPasswordResetAttempts($db, $email) {
    $windowStart = date("Y-m-d H:i:s", time() - (RESET_OTP_WINDOW_MINUTES * 60));

    $query = "DELETE FROM password_resets WHERE email = ? AND created_at < ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $email, $windowStart);
    $stmt->execute();
    $stmt->close();
}

function isPasswordResetThrottled($db, $email) {
    $windowStart = date("Y-m-d H:i:s", time() - (RESET_OTP_WINDOW_MINUTES * 60));

    $query = "
        SELECT COUNT(*) AS total, MAX(created_at) AS last_sent
        FROM password_resets
        WHERE email = ? AND created_at >= ?
    ";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $email, $windowStart);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: ['total' => 0, 'last_sent' => null];
    $stmt->close();

    if ((int)$row['total'] >= RESET_OTP_MAX_PER_WINDOW) {
        return true;
    }

    if (!empty($row['last_sent'])) {
        $lastSentAt = strtotime($row['last_sent']);
        if ($lastSentAt !== false && (time() - $lastSentAt) < RESET_OTP_COOLDOWN_SECONDS) {
            return true;
        }
    }

    return false;
}

function issuePasswordResetOtp($db, $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    if (!passwordResetUserExists($db, $email)) {
        return null;
    }

    cleanupPasswordResetAttempts($db, $email);

    if (isPasswordResetThrottled($db, $email)) {
        return null;
    }

    $otp = (string)random_int(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+" . RESET_OTP_TTL_MINUTES . " minutes"));

    $query = "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("sss", $email, $otp, $expiry);
    $stmt->execute();
    $stmt->close();

    return $otp;
}

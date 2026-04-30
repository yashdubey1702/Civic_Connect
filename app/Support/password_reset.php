<?php

const RESET_OTP_TTL_MINUTES = 5;
const RESET_OTP_WINDOW_MINUTES = 15;
const RESET_OTP_MAX_PER_WINDOW = 5;
const RESET_OTP_COOLDOWN_SECONDS = 60;

// Masks an email address before showing it on reset screens.
function maskResetEmail($email)
{
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

// Checks whether an email belongs to an account.
function passwordResetUserExists($db, $email)
{
    $query = "SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows === 1;
    $stmt->close();

    return $exists;
}

// Removes expired password reset attempts.
function cleanupPasswordResetAttempts($db, $email)
{
    $windowMinutes = (int)RESET_OTP_WINDOW_MINUTES;

    $query = "DELETE FROM password_resets
              WHERE email = ?
              AND (
                  created_at < DATE_SUB(NOW(), INTERVAL {$windowMinutes} MINUTE)
                  OR expires_at <= NOW()
              )";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
}

// Checks whether too many reset attempts were requested.
function isPasswordResetThrottled($db, $email)
{
    $windowMinutes = (int)RESET_OTP_WINDOW_MINUTES;

    $query = "
        SELECT COUNT(*) AS total, MAX(created_at) AS last_sent
        FROM password_resets
        WHERE email = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL {$windowMinutes} MINUTE)
    ";

    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: ['total' => 0, 'last_sent' => null];
    $stmt->close();

    if ((int)$row['total'] >= RESET_OTP_MAX_PER_WINDOW) {
        return true;
    }

    if (!empty($row['last_sent'])) {
        $cooldownSeconds = (int)RESET_OTP_COOLDOWN_SECONDS;
        $query = "SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) AS seconds_since_last";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $row['last_sent']);
        $stmt->execute();
        $result = $stmt->get_result();
        $timeRow = $result->fetch_assoc() ?: ['seconds_since_last' => $cooldownSeconds];
        $stmt->close();

        if ((int)$timeRow['seconds_since_last'] < $cooldownSeconds) {
            return true;
        }
    }

    return false;
}

// Creates and sends a password reset OTP.
function issuePasswordResetOtp($db, $email)
{
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
    $ttlMinutes = (int)RESET_OTP_TTL_MINUTES;

    $query = "INSERT INTO password_resets (email, otp, expires_at)
              VALUES (?, ?, DATE_ADD(NOW(), INTERVAL {$ttlMinutes} MINUTE))";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $stmt->close();

    return $otp;
}

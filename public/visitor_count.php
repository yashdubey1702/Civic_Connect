<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Support/env.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$counterFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'visitor_count.json';
$cookieName = 'civicconnect_visitor_counted';
$cookieLifetime = 60 * 60 * 24;
$cookiePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$cookiePath = $cookiePath === '' ? '/' : $cookiePath;
$alreadyCounted = isset($_COOKIE[$cookieName]);

if (!is_dir(dirname($counterFile)) && !civicconnect_is_vercel()) {
    mkdir(dirname($counterFile), 0755, true);
}

$handle = fopen($counterFile, 'c+b');

if ($handle === false) {
    $fallback = is_readable($counterFile) ? json_decode((string) file_get_contents($counterFile), true) : [];

    echo json_encode([
        'success' => true,
        'count' => isset($fallback['count']) ? max(0, (int) $fallback['count']) : 0,
        'counted' => false,
        'readonly' => true
    ]);
    exit;
}

flock($handle, LOCK_EX);

$contents = stream_get_contents($handle);
$data = $contents ? json_decode($contents, true) : [];
$count = isset($data['count']) ? max(0, (int) $data['count']) : 0;

if (!$alreadyCounted) {
    $count++;
    $data = [
        'count' => $count,
        'updated_at' => gmdate('c')
    ];

    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
    fflush($handle);

    setcookie($cookieName, '1', [
        'expires' => time() + $cookieLifetime,
        'path' => $cookiePath,
        'samesite' => 'Lax'
    ]);
}

flock($handle, LOCK_UN);
fclose($handle);

echo json_encode([
    'success' => true,
    'count' => $count,
    'counted' => !$alreadyCounted
]);

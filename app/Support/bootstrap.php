<?php

require_once __DIR__ . '/env.php';

function civicconnect_bootstrap(): void
{
    static $booted = false;

    if ($booted) {
        return;
    }

    $booted = true;
    date_default_timezone_set(civicconnect_env('APP_TIMEZONE', 'Asia/Kolkata'));

    if (PHP_SAPI === 'cli' || session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secureCookie = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || civicconnect_is_vercel()
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $driver = strtolower(civicconnect_env('SESSION_DRIVER', civicconnect_is_vercel() ? 'database' : 'files'));

    if ($driver === 'database') {
        require_once __DIR__ . '/../Core/DatabaseSessionHandler.php';

        $ttl = (int) civicconnect_env('SESSION_LIFETIME', (string) ini_get('session.gc_maxlifetime'));
        session_set_save_handler(new DatabaseSessionHandler($ttl), true);
    }
}

civicconnect_bootstrap();

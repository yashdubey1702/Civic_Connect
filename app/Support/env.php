<?php

// Loads local .env values once while still allowing hosted environment variables to win.
function civicconnect_load_env_once(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $envFile = __DIR__ . '/../../.env';

    if (!is_readable($envFile)) {
        return;
    }

    $values = parse_ini_file($envFile, false, INI_SCANNER_RAW);

    if (!is_array($values)) {
        return;
    }

    foreach ($values as $key => $value) {
        if (getenv((string) $key) !== false || array_key_exists((string) $key, $_ENV)) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[(string) $key] = $value;
    }
}

// Reads an environment value with a fallback.
function civicconnect_env(string $key, string $default = ''): string
{
    civicconnect_load_env_once();

    if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    $value = getenv($key);
    return $value === false || $value === '' ? $default : (string) $value;
}

function civicconnect_is_vercel(): bool
{
    return civicconnect_env('VERCEL') !== '' || strtolower(civicconnect_env('APP_RUNTIME')) === 'vercel';
}

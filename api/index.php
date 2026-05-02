<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Support/bootstrap.php';

$root = realpath(__DIR__ . '/..');
$path = (string) ($_GET['__path'] ?? '');
$path = str_replace('\\', '/', $path);
$path = preg_replace('#^/+|^town_issues/#', '', $path);
unset($_GET['__path'], $_REQUEST['__path']);

$allowedDirectories = [
    'admin',
    'auth',
    'public',
    'reports',
    'user',
    'volunteers',
];

function civicconnect_send_404(): void
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

if ($path === '' || str_contains($path, "\0") || str_contains($path, '..') || !str_ends_with($path, '.php')) {
    civicconnect_send_404();
}

$firstSegment = strtok($path, '/');

if (!in_array($firstSegment, $allowedDirectories, true)) {
    civicconnect_send_404();
}

$target = realpath($root . DIRECTORY_SEPARATOR . $path);

if ($target === false || !str_starts_with($target, $root) || !is_file($target)) {
    civicconnect_send_404();
}

chdir($root);

$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['SCRIPT_FILENAME'] = $target;
$_SERVER['SCRIPT_NAME'] = '/' . $path;
$_SERVER['PHP_SELF'] = '/' . $path;

require $target;

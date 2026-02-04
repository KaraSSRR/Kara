<?php
declare(strict_types=1);

spl_autoload_register(function(string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;

    $rel = substr($class, strlen($prefix));
    $relPath = str_replace('\\', '/', $rel) . '.php';
    $path = __DIR__ . '/../' . $relPath;

    if (is_file($path)) require $path;
});

// load helpers (functions)
require_once __DIR__ . '/Helpers.php';

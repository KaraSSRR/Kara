<?php
declare(strict_types=1);

namespace App;

use App\Support\Env;
use App\Support\Security;

require __DIR__ . '/Support/autoload.php';

Env::load(__DIR__ . '/../.env');

Security::configurePhp();

set_error_handler(function(int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return true;
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function(\Throwable $e): void {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unhandled exception: ' . $e->getMessage();
});

<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Infrastructure\Database;

try {
    $pdo = Database::pdo();
    $ok = $pdo->query('SELECT 1')->fetchColumn();
    if ((int)$ok !== 1) throw new RuntimeException('DB test failed');
    echo "OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL: {$e->getMessage()}\n");
    exit(1);
}

<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Infrastructure\Database;
use App\Domain\Game\CreatureRepository;
use App\Domain\Game\ItemRepository;

$argv = $_SERVER['argv'] ?? [];
$cmd = $argv[1] ?? '';

$usage = function(): void {
    echo "Commands:\n";
    echo "  php bin/admin.php create-demo-user <username> <email> <password>\n";
    echo "  php bin/admin.php grant-item <user_id> <item_id> <count>\n";
    echo "  php bin/admin.php grant-creature <user_id> <species_id> [nickname]\n";
    exit(1);
};

if ($cmd === '') $usage();

$pdo = Database::pdo();

if ($cmd === 'create-demo-user') {
    $username = $argv[2] ?? '';
    $email = $argv[3] ?? '';
    $password = $argv[4] ?? '';
    if ($username === '' || $email === '' || $password === '') $usage();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO users (username,email,password_hash,current_location_id,created_at) VALUES (?,?,?,?,NOW())');
    $st->execute([$username, $email, $hash, 1]);
    $uid = (int)$pdo->lastInsertId();

    $creatures = new CreatureRepository();
    $items = new ItemRepository();
    $creatures->createStarter($uid, 1, '');
    $items->grant($uid, 1, 10);

    echo "Created user #{$uid} (starter creature + items).\n";
    exit(0);
}

if ($cmd === 'grant-item') {
    $uid = (int)($argv[2] ?? 0);
    $itemId = (int)($argv[3] ?? 0);
    $count = (int)($argv[4] ?? 0);
    if ($uid <= 0 || $itemId <= 0 || $count <= 0) $usage();

    (new ItemRepository())->grant($uid, $itemId, $count);
    echo "Granted item {$itemId} x{$count} to user #{$uid}.\n";
    exit(0);
}

if ($cmd === 'grant-creature') {
    $uid = (int)($argv[2] ?? 0);
    $speciesId = (int)($argv[3] ?? 0);
    $nickname = (string)($argv[4] ?? '');
    if ($uid <= 0 || $speciesId <= 0) $usage();

    $id = (new CreatureRepository())->createStarter($uid, $speciesId, $nickname);
    echo "Created creature #{$id} for user #{$uid}.\n";
    exit(0);
}

$usage();

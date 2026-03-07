<?php
declare(strict_types=1);

namespace App\Domain\Game;

use App\Infrastructure\Database;

final class LocationRepository {
    public function listAll(): array {
        $pdo = Database::pdo();
        $st = $pdo->query('SELECT id, name, slug, region, description, is_pve FROM locations ORDER BY id ASC');
        return $st->fetchAll();
    }

    public function find(int $id): ?array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id, name, slug, region, description, is_pve FROM locations WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }
}

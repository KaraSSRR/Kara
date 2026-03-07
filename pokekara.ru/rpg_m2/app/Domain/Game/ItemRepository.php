<?php
declare(strict_types=1);

namespace App\Domain\Game;

use App\Infrastructure\Database;

final class ItemRepository {
    public function listInventory(int $userId): array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            SELECT ui.item_id, ui.count, i.name, i.category, i.description
            FROM user_items ui
            JOIN items i ON i.id = ui.item_id
            WHERE ui.user_id = ?
            ORDER BY i.name ASC
        ');
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function grant(int $userId, int $itemId, int $count): void {
        $count = max(0, $count);
        if ($count <= 0) return;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO user_items (user_id, item_id, count, updated_at)
                VALUES (?,?,?,NOW())
                ON DUPLICATE KEY UPDATE count = count + VALUES(count), updated_at = NOW()
            ');
            $st->execute([$userId, $itemId, $count]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

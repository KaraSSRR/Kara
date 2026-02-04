<?php
declare(strict_types=1);

namespace App\Domain\Battle;

use App\Infrastructure\Database;

final class BattleRepository {
    public function findActiveForUser(int $userId): ?array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM battles WHERE user_id = ? AND status = "active" ORDER BY id DESC LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findByIdForUser(int $userId, int $battleId): ?array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM battles WHERE id = ? AND user_id = ? LIMIT 1');
        $st->execute([$battleId, $userId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function create(int $userId, ?int $locationId, int $playerCreatureId, array $opponentSnapshot, int $seed, array $state): int {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            INSERT INTO battles (user_id, location_id, player_creature_id, opponent_creature_snapshot, seed, turn, status, state, result, created_at, updated_at)
            VALUES (?,?,?,?,?,0,"active",?,"{}",NOW(),NOW())
        ');
        $st->execute([
            $userId,
            $locationId,
            $playerCreatureId,
            json_encode($opponentSnapshot, JSON_UNESCAPED_UNICODE),
            $seed,
            json_encode($state, JSON_UNESCAPED_UNICODE),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function updateState(int $battleId, int $turn, string $status, array $state, array $result = [], ?string $finishedAt = null): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            UPDATE battles
            SET turn = ?, status = ?, state = ?, result = ?, updated_at = NOW(), finished_at = ?
            WHERE id = ?
        ');
        $st->execute([
            $turn,
            $status,
            json_encode($state, JSON_UNESCAPED_UNICODE),
            json_encode($result, JSON_UNESCAPED_UNICODE),
            $finishedAt,
            $battleId,
        ]);
    }

    public function addAction(int $battleId, int $turn, string $side, array $action): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            INSERT INTO battle_actions (battle_id, turn, side, action, created_at)
            VALUES (?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE action = VALUES(action)
        ');
        $st->execute([
            $battleId,
            $turn,
            $side,
            json_encode($action, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** @param array<int,array{turn:int,seq:int,message:string,payload:array}> $logs */
    public function addLogs(int $battleId, array $logs): void {
        if (!$logs) return;
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            INSERT INTO battle_logs (battle_id, turn, seq, message, payload, created_at)
            VALUES (?,?,?,?,?,NOW())
        ');
        foreach ($logs as $l) {
            $st->execute([
                $battleId,
                (int)$l['turn'],
                (int)$l['seq'],
                (string)$l['message'],
                json_encode($l['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function addSnapshot(int $battleId, int $turn, array $state): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            INSERT INTO battle_snapshots (battle_id, turn, state, created_at)
            VALUES (?,?,?,NOW())
            ON DUPLICATE KEY UPDATE state = VALUES(state)
        ');
        $st->execute([
            $battleId,
            $turn,
            json_encode($state, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function listLogs(int $battleId, int $limit = 120): array {
        $limit = max(1, min(500, $limit));
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            SELECT turn, seq, message, payload, created_at
            FROM battle_logs
            WHERE battle_id = ?
            ORDER BY turn DESC, seq DESC
            LIMIT ' . $limit
        );
        $st->execute([$battleId]);
        $rows = $st->fetchAll();
        // reverse to chronological
        return array_reverse($rows);
    }

    public function listActions(int $battleId): array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            SELECT turn, side, action, created_at
            FROM battle_actions
            WHERE battle_id = ?
            ORDER BY turn ASC, side ASC
        ');
        $st->execute([$battleId]);
        return $st->fetchAll();
    }

    /** @return array<string,mixed> */
    public static function decodeJson($json): array {
        if (is_array($json)) return $json;
        if ($json === null) return [];
        $s = (string)$json;
        if ($s === '') return [];
        $d = json_decode($s, true);
        return is_array($d) ? $d : [];
    }
}

<?php
declare(strict_types=1);

namespace App\Domain\Chat;

use App\Infrastructure\Database;

final class ChatRepository {
    /** @return array<int, array<string,mixed>> */
    public function pollChannel(string $channel, int $afterId, int $limit = 50): array {
        $pdo = Database::pdo();
        $limit = max(1, min(100, $limit));

        if ($afterId <= 0) {
            // Initial load: return the latest N messages, but in ascending order for UI.
            $sql =
                'SELECT * FROM (
                    SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, u.username AS from_username,
                           m.to_user_id, m.clan_id, m.body, m.created_at
                    FROM chat_messages m
                    LEFT JOIN users u ON u.id = m.from_user_id
                    WHERE m.channel = ?
                    ORDER BY m.id DESC
                    LIMIT ' . (int)$limit .
                ') t ORDER BY t.id ASC';
            $st = $pdo->prepare($sql);
            $st->execute([$channel]);
            return $st->fetchAll() ?: [];
        }

        $st = $pdo->prepare(
            'SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, u.username AS from_username,
                    m.to_user_id, m.clan_id, m.body, m.created_at
             FROM chat_messages m
             LEFT JOIN users u ON u.id = m.from_user_id
             WHERE m.channel = ? AND m.id > ?
             ORDER BY m.id ASC
             LIMIT ' . (int)$limit
        );
        $st->execute([$channel, $afterId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int, array<string,mixed>> */
    public function pollDm(int $userId, int $peerId, int $afterId, int $limit = 50): array {
        $pdo = Database::pdo();
        $limit = max(1, min(100, $limit));

        if ($afterId <= 0) {
            $sql =
                'SELECT * FROM (
                    SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, u.username AS from_username,
                           m.to_user_id, m.clan_id, m.body, m.created_at
                    FROM chat_messages m
                    LEFT JOIN users u ON u.id = m.from_user_id
                    WHERE m.channel = \'dm\'
                      AND ((m.from_user_id = ? AND m.to_user_id = ?) OR (m.from_user_id = ? AND m.to_user_id = ?))
                    ORDER BY m.id DESC
                    LIMIT ' . (int)$limit .
                ') t ORDER BY t.id ASC';
            $st = $pdo->prepare($sql);
            $st->execute([$userId, $peerId, $peerId, $userId]);
            return $st->fetchAll() ?: [];
        }

        $st = $pdo->prepare(
            'SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, u.username AS from_username,
                    m.to_user_id, m.clan_id, m.body, m.created_at
             FROM chat_messages m
             LEFT JOIN users u ON u.id = m.from_user_id
             WHERE m.channel = \'dm\'
               AND m.id > ?
               AND ((m.from_user_id = ? AND m.to_user_id = ?) OR (m.from_user_id = ? AND m.to_user_id = ?))
             ORDER BY m.id ASC
             LIMIT ' . (int)$limit
        );
        $st->execute([$afterId, $userId, $peerId, $peerId, $userId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int, array<string,mixed>> */
    public function pollBotDm(int $userId, int $afterId, int $limit = 50): array {
        $pdo = Database::pdo();
        $limit = max(1, min(100, $limit));

        if ($afterId <= 0) {
            $sql =
                'SELECT * FROM (
                    SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, NULL AS from_username,
                           m.to_user_id, m.clan_id, m.body, m.created_at
                    FROM chat_messages m
                    WHERE m.channel = \'dm\'
                      AND m.kind IN (\'bot\',\'system\')
                      AND m.from_user_id IS NULL
                      AND m.to_user_id = ?
                    ORDER BY m.id DESC
                    LIMIT ' . (int)$limit .
                ') t ORDER BY t.id ASC';
            $st = $pdo->prepare($sql);
            $st->execute([$userId]);
            return $st->fetchAll() ?: [];
        }

        $st = $pdo->prepare(
            'SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, NULL AS from_username,
                    m.to_user_id, m.clan_id, m.body, m.created_at
             FROM chat_messages m
             WHERE m.channel = \'dm\'
               AND m.id > ?
               AND m.kind IN (\'bot\',\'system\')
               AND m.from_user_id IS NULL
               AND m.to_user_id = ?
             ORDER BY m.id ASC
             LIMIT ' . (int)$limit
        );
        $st->execute([$afterId, $userId]);
        return $st->fetchAll() ?: [];
    }

    /** @return array<int, array<string,mixed>> */
    public function pollClan(int $clanId, int $afterId, int $limit = 50): array {
        $pdo = Database::pdo();
        $limit = max(1, min(100, $limit));

        if ($afterId <= 0) {
            $sql =
                'SELECT * FROM (
                    SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, u.username AS from_username,
                           m.to_user_id, m.clan_id, m.body, m.created_at
                    FROM chat_messages m
                    LEFT JOIN users u ON u.id = m.from_user_id
                    WHERE m.channel = \'clan\' AND m.clan_id = ?
                    ORDER BY m.id DESC
                    LIMIT ' . (int)$limit .
                ') t ORDER BY t.id ASC';
            $st = $pdo->prepare($sql);
            $st->execute([$clanId]);
            return $st->fetchAll() ?: [];
        }

        $st = $pdo->prepare(
            'SELECT m.id, m.channel, m.kind, m.bot_name, m.from_user_id, u.username AS from_username,
                    m.to_user_id, m.clan_id, m.body, m.created_at
             FROM chat_messages m
             LEFT JOIN users u ON u.id = m.from_user_id
             WHERE m.channel = \'clan\' AND m.clan_id = ? AND m.id > ?
             ORDER BY m.id ASC
             LIMIT ' . (int)$limit
        );
        $st->execute([$clanId, $afterId]);
        return $st->fetchAll() ?: [];
    }

    public function findUserIdByUsername(string $username): ?int {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $id = $st->fetchColumn();
        if ($id === false || $id === null) return null;
        return (int)$id;
    }

    public function getClanIdForUser(int $userId): ?int {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT clan_id FROM clan_members WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        $id = $st->fetchColumn();
        if ($id === false || $id === null) return null;
        return (int)$id;
    }

    public function insertMessage(
        string $channel,
        string $kind,
        ?int $fromUserId,
        ?int $toUserId,
        ?int $clanId,
        string $body,
        ?string $botName = null
    ): int {
        $pdo = Database::pdo();
        $st = $pdo->prepare(
            'INSERT INTO chat_messages (channel, kind, bot_name, from_user_id, to_user_id, clan_id, body, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $st->execute([$channel, $kind, $botName, $fromUserId, $toUserId, $clanId, $body]);
        return (int)$pdo->lastInsertId();
    }

    /** @return array<int, array<string,mixed>> */
    public function dmPeers(int $userId, int $limit = 30): array {
        $pdo = Database::pdo();
        $limit = max(1, min(100, $limit));

        $sql = '
            SELECT peer_id, MAX(id) AS last_id, MAX(created_at) AS last_at
            FROM (
                SELECT
                    CASE WHEN from_user_id = :uid THEN to_user_id ELSE from_user_id END AS peer_id,
                    id, created_at
                FROM chat_messages
                WHERE channel = \'dm\' AND (from_user_id = :uid OR to_user_id = :uid)
            ) t
            WHERE peer_id IS NOT NULL
            GROUP BY peer_id
            ORDER BY last_id DESC
            LIMIT ' . (int)$limit;

        $st = $pdo->prepare($sql);
        $st->execute(['uid' => $userId]);
        $rows = $st->fetchAll() ?: [];
        // Optional: include bot inbox (TradeBot / system)
        $bot = $this->botDmSummary($userId);

        if (!$rows && !$bot) return [];

        $peerIds = array_map(fn($r) => (int)$r['peer_id'], $rows);
        $in = implode(',', array_fill(0, count($peerIds), '?'));
        $map = [];
        if (count($peerIds) > 0) {
            $st2 = $pdo->prepare('SELECT id, username FROM users WHERE id IN (' . $in . ')');
            $st2->execute($peerIds);
            $users = $st2->fetchAll() ?: [];
            foreach ($users as $u) $map[(int)$u['id']] = $u['username'];
        }

        $out = [];
        if ($bot) {
            $out[] = [
                'user_id' => 0,
                'username' => (string)($bot['bot_name'] ?? 'TradeBot'),
                'last_id' => (int)$bot['last_id'],
                'last_at' => (string)$bot['last_at'],
            ];
        }
        foreach ($rows as $r) {
            $pid = (int)$r['peer_id'];
            $out[] = [
                'user_id' => $pid,
                'username' => $map[$pid] ?? ('#' . $pid),
                'last_id' => (int)$r['last_id'],
                'last_at' => (string)$r['last_at'],
            ];
        }
        return $out;
    }

    /** @return array{last_id:int,last_at:string,bot_name:string}|null */
    private function botDmSummary(int $userId): ?array {
        $pdo = Database::pdo();
        $st = $pdo->prepare(
            'SELECT MAX(id) AS last_id, MAX(created_at) AS last_at, MAX(bot_name) AS bot_name
             FROM chat_messages
             WHERE channel = \'dm\'
               AND kind IN (\'bot\',\'system\')
               AND from_user_id IS NULL
               AND to_user_id = ?'
        );
        $st->execute([$userId]);
        $row = $st->fetch() ?: null;
        if (!$row || !$row['last_id']) return null;
        return [
            'last_id' => (int)$row['last_id'],
            'last_at' => (string)($row['last_at'] ?? ''),
            'bot_name' => (string)($row['bot_name'] ?? 'TradeBot'),
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Domain\Auth;

use App\Infrastructure\Database;

final class UserRepository {
    public function findById(int $id): ?array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id, username, email, current_location_id, created_at, last_login_at FROM users WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function findForLogin(string $login): ?array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $st->execute([$login, $login]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function existsUsername(string $username): bool {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        return (bool)$st->fetchColumn();
    }

    public function existsEmail(string $email): bool {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        return (bool)$st->fetchColumn();
    }

    public function create(string $username, string $email, string $passwordHash, int $startLocationId): int {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO users (username, email, password_hash, current_location_id, created_at) VALUES (?,?,?,?,NOW())');
            $st->execute([$username, $email, $passwordHash, $startLocationId]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function touchLogin(int $id): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $st->execute([$id]);
    }

    public function setLocation(int $id, int $locationId): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare('UPDATE users SET current_location_id = ? WHERE id = ?');
        $st->execute([$locationId, $id]);
    }
}

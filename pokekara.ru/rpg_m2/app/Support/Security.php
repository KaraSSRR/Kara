<?php
declare(strict_types=1);

namespace App\Support;

use App\Infrastructure\Database;

final class Security {
    public static function configurePhp(): void {
        $secure = Env::bool('SESSION_SECURE', false);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_only_cookies', '1');

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    public static function csrfToken(): string {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) < 16) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }

    public static function requireCsrf(Request $request): void {
        $token = $request->input('_csrf');
        if (!$token || !isset($_SESSION['_csrf']) || !hash_equals((string)$_SESSION['_csrf'], (string)$token)) {
            Response::apiError($request, 'csrf', 'Invalid CSRF token', 419);
        }
    }

    public static function flash(string $type, string $message): void {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): ?array {
        if (!isset($_SESSION['_flash'])) return null;
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        if (!is_array($f) || !isset($f['type'], $f['message'])) return null;
        return $f;
    }

    public static function userId(): ?int {
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    public static function requireAuth(Request $request): int {
        $uid = self::userId();
        if (!$uid) {
            if ($request->wantsJson()) Response::apiError($request, 'unauthorized', 'Unauthorized', 401);
            self::flash('err', 'Нужно войти в аккаунт.');
            Response::redirect('/login');
        }
        return (int)$uid;
    }

    /**
     * Simple DB-backed rate limit.
     * @return bool true if allowed, false if limited
     */
    public static function rateLimit(string $bucket, string $key, int $max, int $windowSeconds): bool {
        $pdo = Database::pdo();
        $now = time();
        $id = hash('sha256', $bucket . '|' . $key);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, window_start, count FROM rate_limits WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                $ins = $pdo->prepare('INSERT INTO rate_limits (id, bucket, k, window_start, count) VALUES (?,?,?,?,?)');
                $ins->execute([$id, $bucket, $key, $now, 1]);
                $pdo->commit();
                return true;
            }

            $ws = (int)$row['window_start'];
            $cnt = (int)$row['count'];

            if ($now - $ws >= $windowSeconds) {
                $upd = $pdo->prepare('UPDATE rate_limits SET window_start = ?, count = 1 WHERE id = ?');
                $upd->execute([$now, $id]);
                $pdo->commit();
                return true;
            }

            if ($cnt >= $max) {
                $pdo->commit();
                return false;
            }

            $upd = $pdo->prepare('UPDATE rate_limits SET count = count + 1 WHERE id = ?');
            $upd->execute([$id]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('rateLimit error: ' . $e->getMessage());
            return true; // fail-open
        }
    }
}

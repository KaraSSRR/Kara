<?php
declare(strict_types=1);

namespace App\Domain\Auth;

final class AuthService {
    public function __construct(private UserRepository $users) {}

    public function validateUsername(string $username): ?string {
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            return 'Логин: 3–20 символов, латиница/цифры/_';
        }
        return null;
    }

    public function validateEmail(string $email): ?string {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Некорректный email';
        if (strlen($email) > 254) return 'Email слишком длинный';
        return null;
    }

    public function validatePassword(string $password): ?string {
        if (strlen($password) < 8) return 'Пароль минимум 8 символов';
        if (strlen($password) > 255) return 'Пароль слишком длинный';
        return null;
    }

    public function register(string $username, string $email, string $password, int $startLocationId): array {
        $errors = [];
        if ($msg = $this->validateUsername($username)) $errors['username'] = $msg;
        if ($msg = $this->validateEmail($email)) $errors['email'] = $msg;
        if ($msg = $this->validatePassword($password)) $errors['password'] = $msg;

        if ($this->users->existsUsername($username)) $errors['username'] = 'Логин занят';
        if ($this->users->existsEmail($email)) $errors['email'] = 'Email уже используется';

        if ($errors) return ['ok' => false, 'errors' => $errors];

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id = $this->users->create($username, $email, $hash, $startLocationId);

        return ['ok' => true, 'user_id' => $id];
    }

    public function login(string $login, string $password): array {
        $u = $this->users->findForLogin($login);
        if (!$u) return ['ok' => false, 'error' => 'Неверный логин или пароль'];

        $hash = (string)($u['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return ['ok' => false, 'error' => 'Неверный логин или пароль'];
        }

        return ['ok' => true, 'user_id' => (int)$u['id']];
    }
}

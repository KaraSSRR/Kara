<?php
declare(strict_types=1);

namespace App\Support;

final class Env {
    private static array $data = [];

    public static function load(string $path): void {
        if (!is_file($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }

            self::$data[$key] = $val;
            $_ENV[$key] = $val;
            putenv($key . '=' . $val);
        }
    }

    public static function get(string $key, ?string $default = null): ?string {
        if (array_key_exists($key, self::$data)) return self::$data[$key];
        $v = $_ENV[$key] ?? getenv($key);
        if ($v === false || $v === null || $v === '') return $default;
        return (string)$v;
    }

    public static function bool(string $key, bool $default = false): bool {
        $v = self::get($key);
        if ($v === null) return $default;
        return in_array(strtolower($v), ['1','true','yes','on'], true);
    }

    public static function int(string $key, int $default = 0): int {
        $v = self::get($key);
        if ($v === null) return $default;
        return (int)$v;
    }
}

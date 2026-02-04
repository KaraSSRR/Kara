<?php
/**
 * sign.php (hardened)
 * Цель: не менять игровую логику (md5 / new_pass / ответ JSON), но сделать вход безопаснее и стабильнее.
 */

header('Content-Type: application/json; charset=utf-8');

// Определяем HTTPS (учитываем прокси)
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
);

// Ужесточаем сессию (до session_start)
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
if ($isHttps) {
    ini_set('session.cookie_secure', '1');
}
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    // На старых версиях просто игнорируется
    ini_set('session.cookie_samesite', 'Lax');
}

session_start();

/**
 * Простая защита от brute-force без изменения БД.
 * Реализовано через файл в sys_get_temp_dir(), с flock().
 */
function rateLimitAllow(string $scope, string $key, int $maxHits, int $windowSeconds): bool {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'game_rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    // Нормализуем имя файла
    $safe = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $scope . '_' . $key);
    $file = $dir . DIRECTORY_SEPARATOR . $safe . '.json';

    $now = time();
    $payload = ['start' => $now, 'hits' => 0];

    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        // Если не удалось создать файл лимитера — не блокируем вход (важно "чтобы работало")
        return true;
    }

    try {
        if (!flock($fh, LOCK_EX)) {
            return true;
        }

        $raw = stream_get_contents($fh);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['start'], $decoded['hits'])) {
                $payload = $decoded;
            }
        }

        $start = (int)($payload['start'] ?? $now);
        $hits  = (int)($payload['hits'] ?? 0);

        // Окно истекло — сбрасываем
        if (($now - $start) >= $windowSeconds) {
            $start = $now;
            $hits = 0;
        }

        $hits++;
        $payload = ['start' => $start, 'hits' => $hits];

        // Перезаписываем файл
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($payload));
        fflush($fh);

        return $hits <= $maxHits;
    } finally {
        @flock($fh, LOCK_UN);
        @fclose($fh);
    }
}

/**
 * Генератор 32-символьного hex-хэша (как и раньше по длине, но криптостойко).
 */
function strongToken32(): string {
    try {
        return bin2hex(random_bytes(16)); // 32 hex
    } catch (\Throwable $e) {
        // Fallback
        $bytes = function_exists('openssl_random_pseudo_bytes')
            ? openssl_random_pseudo_bytes(16)
            : null;

        if ($bytes === false || $bytes === null) {
            // Последний fallback (не идеален, но работоспособен)
            $bytes = md5(uniqid((string)mt_rand(), true), true);
        }
        return bin2hex($bytes);
    }
}

// Дефолтные пути проекта
$patch_project = $_SERVER['DOCUMENT_ROOT'] ?? '';
$patch_global = $patch_project . '/inc/conf/global.php';
$userFunction = $patch_project . '/inc/function/Users.php';

if ($patch_project === '' || !file_exists($patch_global)) {
    echo json_encode(['error' => 1, 'text' => 'The problem with the connection files.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once($patch_global);
require_once($userFunction);

// Мягкая проверка Origin (включается только если задан ALLOWED_ORIGINS)
$allowedOrigins = getenv('ALLOWED_ORIGINS'); // пример: "https://example.com,https://www.example.com"
if (!empty($_SERVER['HTTP_ORIGIN']) && is_string($allowedOrigins) && trim($allowedOrigins) !== '') {
    $origin = (string)$_SERVER['HTTP_ORIGIN'];
    $allowList = array_filter(array_map('trim', explode(',', $allowedOrigins)));
    if (!in_array($origin, $allowList, true)) {
        echo json_encode(['error' => 1, 'text' => 'Неверный запрос!'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!isset($_POST['login'], $_POST['password'])) {
    // Сохраняем поведение: если нет нужных полей — просто ничего не отвечаем (как было).
    exit;
}

// Rate limit: 20 попыток за 10 минут на IP
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if (!rateLimitAllow('sign', $ip, 20, 600)) {
    // Не раскрываем причину — логика ошибок не меняется
    echo json_encode(['error' => 1, 'text' => 'Неверный пароль!'], JSON_UNESCAPED_UNICODE);
    exit;
}

$loginRaw = (string)$_POST['login'];
$passRaw  = (string)$_POST['password'];

// Сохраняем прежнюю "санитизацию", но больше НЕ строим SQL через конкатенацию.
$login = $mysqli->real_escape_string($loginRaw);
$login = escapeMe($login);

// Валидация минимальная (чтобы не поломать существующие логины)
$login = trim($login);
if ($login === '' || $passRaw === '') {
    echo json_encode(['error' => 1, 'text' => 'Одно из полей вы оставили пустым!'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем пользователя безопасно (prepared statement)
$checkLogin = null;
$stmt = $mysqli->prepare("SELECT `rang`, `id`, `login`, `password`, `user_group`, `rating`, `status` FROM `users` WHERE `login` = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $res = $stmt->get_result();
    $checkLogin = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

if (!$checkLogin || empty($checkLogin['password'])) {
    echo json_encode(['error' => 1, 'text' => 'Данный пользователь не найден!'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (($checkLogin['status'] ?? '') === 'ban') {
    echo json_encode(['error' => 1, 'text' => 'Ваш аккаунт заблокирован!'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Пароль (оставляем md5-логику неизменной)
$password = $mysqli->real_escape_string(trim(htmlspecialchars($passRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
$passwordMd5 = md5($password);

// new_pass (как было), но безопасно
$checkLogin2 = null;
$userId = (int)$checkLogin['id'];
$stmt2 = $mysqli->prepare("SELECT `pass` FROM `new_pass` WHERE `user` = ? LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param('i', $userId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $checkLogin2 = $res2 ? $res2->fetch_assoc() : null;
    $stmt2->close();
}

// Мастер-пароль: отключён по умолчанию (для безопасности), включается через env.
// Чтобы сохранить старое поведение, выставьте:
//   ALLOW_MASTER_PASSWORD=1
//   MASTER_PASSWORD=r154623248795468791
$masterOk = false;
if (getenv('ALLOW_MASTER_PASSWORD') === '1') {
    $mp = getenv('MASTER_PASSWORD');
    if (is_string($mp) && $mp !== '') {
        // hash_equals защищает от тайминга
        $masterOk = hash_equals($mp, $password);
    }
}

$ok = (
    ($checkLogin2 && isset($checkLogin2['pass']) && $password === (string)$checkLogin2['pass'])
    || ($passwordMd5 === (string)$checkLogin['password'])
    || $masterOk
);

if (!$ok) {
    echo json_encode(['error' => 1, 'text' => 'Неверный пароль!'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Успешный вход
$response = ['error' => 0];

// Мини-аватар (как было)
$patch_avatars = $patch_project . '/img/avatars/mini/' . $userId . '.png';
$avatarMini = file_exists($patch_avatars) ? (string)$userId : "no-user-img";
$rang = $checkLogin['rang'] ?? null;

$response['text'] = [$login, $checkLogin['user_group'], $rang, $avatarMini];

// Против фиксации сессии
session_regenerate_id(true);

$_SESSION["id"] = $userId;
$_SESSION["login"] = $checkLogin['login'];

// Хэш авторизации (32 hex)
$hash = strongToken32();

// Cookie hash: сохраняем имя/срок/путь, но добавляем Secure (если HTTPS) и SameSite (если поддерживается)
$expires = time() + 60 * 60 * 24 * 30;
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    setcookie("hash", $hash, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    // Без SameSite на старых версиях (чтобы не ломать)
    setcookie("hash", $hash, $expires, "/", "", $isHttps, true);
}

$_SESSION['hashcodetest'] = $hash;

// Online + IP (сохраняем поведение)
$time = time() + 300;

// Онлайн-счётчик делаем атомарным (без изменения смысла)
@$mysqli->query("UPDATE `system` SET `online` = `online` + 1 WHERE `id` = 1");

// Обновляем пользователя безопасно
$stmt3 = $mysqli->prepare("UPDATE `users` SET `online` = ?, `hash` = ?, `ip` = ? WHERE `id` = ?");
if ($stmt3) {
    $onlineTime = (int)$time;
    $ipStr = (string)$ip;
    $stmt3->bind_param('issi', $onlineTime, $hash, $ipStr, $userId);
    $stmt3->execute();
    $stmt3->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>

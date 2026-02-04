<?php
// === Конфиг/инициализация ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';

if (empty($patch_global) || !file_exists($patch_global) || !is_readable($patch_global)) {
    die('The problem with the connection files.');
}
require_once $patch_global;

session_start(); // на всякий случай

// Проверяем сессию
if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    die('Invalid session ID.');
}

$userId = (int)$_SESSION['id'];
$now    = time();
$newOnline = $now + 300; // online хранится как "срок жизни"

// Транзакция защитит от гонок при одновременных запросах
$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

try {
    // Читаем текущие значения пользователя
    $sql = "SELECT `online`, 
                   IFNULL(`play_seconds`,0) AS play_seconds,
                   IFNULL(`last_play_at`,0) AS last_play_at
            FROM `users`
            WHERE `id` = ?
            FOR UPDATE";
    if (!$stmt = $mysqli->prepare($sql)) {
        throw new Exception('Prepare failed: '.$mysqli->error);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        throw new Exception('User not found.');
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    $prevExpire   = (int)$row['online'];             // было "now + 300" при прошлом пинге
    $prevPing     = $prevExpire > 0 ? max($prevExpire - 300, 0) : 0; // восстановим прошлый момент пинга
    $lastPlayAt   = (int)$row['last_play_at'];       // когда в последний раз учитывали секунды
    $playSeconds  = (int)$row['play_seconds'];

    // Базовая точка — максимальная из "последний учёт" и "реальный прошлый пинг"
    $baseline = max($lastPlayAt, $prevPing);

    // Если предыдущий online уже истёк (пользователь был офлайн), секунды не начисляем
    if ($prevExpire < $now) {
        $delta = 0;
    } else {
        // Начисляем только положительную дельту и не больше 10 минут за один заход
        $delta = $now - $baseline;
        if ($delta < 0)   $delta = 0;
        if ($delta > 600) $delta = 600; // анти-накрутка: максимум 10 минут за пинг
    }

    $newPlaySeconds = $playSeconds + $delta;
    $newHours       = (int) floor($newPlaySeconds / 3600);

    // Сохраняем обновления
    $sqlUpd = "UPDATE `users`
               SET `online` = ?, 
                   `last_play_at` = ?, 
                   `play_seconds` = ?, 
                   `hours` = ?
               WHERE `id` = ?";
    if (!$stmt = $mysqli->prepare($sqlUpd)) {
        throw new Exception('Prepare (update) failed: '.$mysqli->error);
    }
    $stmt->bind_param('iiiii', $newOnline, $now, $newPlaySeconds, $newHours, $userId);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    // Можно ничего не выводить; если нужен отклик:
    // echo json_encode(['ok'=>1,'delta'=>$delta,'hours'=>$newHours], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo 'Time track error: '.$e->getMessage();
    exit;
}
?>

<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/php-error.log');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header("Content-Type: application/json");

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
if (!file_exists($patch_global)) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => 'Ошибка: Файл конфигурации не найден.']);
    exit;
}
require_once($patch_global);

if (!isset($_SESSION['id'])) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => 'Пожалуйста, войдите в аккаунт, чтобы оставить реакцию.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['reaction'])) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => 'Неверный запрос.']);
    exit;
}

$userId = (int)$_SESSION['id'];
$newsId = (int)$_POST['id'];
$reaction = $mysqli->real_escape_string($_POST['reaction']);

$checkNews = $mysqli->query("SELECT id FROM news WHERE id = $newsId");
if (!$checkNews || $checkNews->num_rows === 0) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => 'Новость не найдена.']);
    exit;
}

$allowedReactions = ['like', 'love', 'haha', 'sad'];
if (!in_array($reaction, $allowedReactions, true)) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => 'Недопустимая реакция.']);
    exit;
}

$checkReaction = $mysqli->query("SELECT id FROM news_reactions WHERE user_id = $userId AND news_id = $newsId");
if ($checkReaction && $checkReaction->num_rows > 0) {
    $mysqli->query("UPDATE news_reactions SET reaction = '$reaction' WHERE user_id = $userId AND news_id = $newsId");
} else {
    $mysqli->query("INSERT INTO news_reactions (user_id, news_id, reaction) VALUES ($userId, $newsId, '$reaction')");
}

$mysqli->query("UPDATE news SET `like`=0, `love`=0, `haha`=0, `sad`=0 WHERE id = $newsId");

$updateStats = $mysqli->query("
    SELECT reaction, COUNT(*) as count
    FROM news_reactions
    WHERE news_id = $newsId
    GROUP BY reaction
");
if ($updateStats) {
    $stats = ['like'=>0,'love'=>0,'haha'=>0,'sad'=>0];
    while ($r = $updateStats->fetch_assoc()) {
        $stats[$r['reaction']] = (int)$r['count'];
    }
    $mysqli->query("UPDATE news SET 
        `like` = {$stats['like']}, 
        `love` = {$stats['love']}, 
        `haha` = {$stats['haha']}, 
        `sad` = {$stats['sad']}
        WHERE id = $newsId
    ");
}

$reactionsResult = $mysqli->query("
    SELECT reaction, COUNT(*) as count
    FROM news_reactions
    WHERE news_id = $newsId
    GROUP BY reaction
");

$reactions = [];
if ($reactionsResult) {
    while ($row = $reactionsResult->fetch_assoc()) {
        $reactions[$row['reaction']] = (int)$row['count'];
    }
}
foreach ($allowedReactions as $allowed) {
    if (!isset($reactions[$allowed])) $reactions[$allowed]=0;
}

$userReaction = $reaction;

if (ob_get_length()) ob_end_clean();
echo json_encode([
    'error' => 0,
    'reactions' => $reactions,
    'userReaction' => $userReaction
]);
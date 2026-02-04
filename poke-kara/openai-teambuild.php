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

// === Подключение к БД и проверка авторизации, как в вашем примере ===
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
    echo json_encode(['error' => 1, 'text' => 'Пожалуйста, войдите в аккаунт, чтобы использовать бота.']);
    exit;
}

$userId = (int)$_SESSION['id'];
$today = date('Y-m-d');

// === ОГРАНИЧЕНИЕ 20 запросов в день ===
$limitPerDay = 20;

// Таблица: openai_limits(user_id INT, date DATE, count INT)
// Создайте такую таблицу, если её нет!
$limitCheck = $mysqli->query("SELECT count FROM openai_limits WHERE user_id = $userId AND date = '$today' LIMIT 1");
$limitCount = 0;
if ($limitCheck && $row = $limitCheck->fetch_assoc()) {
    $limitCount = (int)$row['count'];
}
if ($limitCount >= $limitPerDay) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => 'Лимит запросов исчерпан (20 в день). Приходите завтра!']);
    exit;
}

// === Увеличиваем счетчик перед отправкой к OpenAI ===
if ($limitCount > 0) {
    $mysqli->query("UPDATE openai_limits SET count = count + 1 WHERE user_id = $userId AND date = '$today'");
} else {
    $mysqli->query("INSERT INTO openai_limits (user_id, date, count) VALUES ($userId, '$today', 1)");
}

// === ОСТАЛЬНОЙ КОД OpenAI ===
$OPENAI_API_KEY = "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";

$data = json_decode(file_get_contents('php://input'), true);
$purpose = isset($data['purpose']) ? $data['purpose'] : '';
$mons = isset($data['mons']) ? $data['mons'] : '';
$question = isset($data['question']) ? $data['question'] : '';

$systemPrompt = "Ты — дружелюбный и опытный гид-бот по браузерной игре о покемонах. Ты обладаешь глубокими знаниями по реальным механикам, типам, способностям и стратегиям покемонов, и всегда основываешь советы только на достоверной и актуальной информации, без выдуманных фактов. Используй знания о типах, синергии, сильных и слабых сторонах покемонов, их наборах приёмов и ролях в команде. 
Отвечай только конкретно и по делу, без лишней воды. Даёшь только лучшие и реально рабочие тактики для указанных условий и цели игрока. 
Помоги собрать оптимальную команду под задачу: \"$purpose\". Покемоны игрока: $mons. Если есть отдельный вопрос — вот он: $question. 
Дай лаконичные, экспертные советы по тимбилдингу, объясни выбор каждого покемона с опорой на реальные игровые механики, укажи слабые места команды (если есть) и предложи конкретные улучшения.";

$postData = [
    "model" => "gpt-4o",
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $question ? $question : "Цель: $purpose. Моя команда: $mons"]
    ],
    "max_tokens" => 500,
    "temperature" => 0.7
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $OPENAI_API_KEY",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);

if ($response === false) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => "Ошибка соединения с OpenAI API: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = json_decode($response, true);

if (!$json || !isset($json['choices'][0]['message']['content'])) {
    $errorMsg = "Совет не получен. ";
    if (isset($json['error']['message'])) {
        $errorMsg .= "Ошибка OpenAI: " . $json['error']['message'];
    } else {
        $errorMsg .= "HTTP-код: $http_code. Ответ: $response";
    }
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['error' => 1, 'text' => $errorMsg]);
    exit;
}

$advice = $json['choices'][0]['message']['content'];
if (ob_get_length()) ob_end_clean();
echo json_encode(['error' => 0, 'advice' => $advice]);
?>
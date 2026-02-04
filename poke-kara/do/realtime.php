<?php
// Подключение к сессии, БД и т.д.
session_start();
header('Content-Type: application/json');

// Пример универсальной проверки (user_id или id - поддержка разных авторизаций)
$userId = 0;
if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
    $userId = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['id']) && intval($_SESSION['id']) > 0) {
    $userId = intval($_SESSION['id']);
}

// Если не авторизован - возвращаем ошибку
if ($userId <= 0) {
    echo json_encode(['error' => 'not authorized']);
    exit;
}

// Функции получения данных (заглушки - замените на ваши реальные функции/запросы)
function getBattleInfo($userId) {
    // Здесь логика получения инфы о бою для пользователя
    // return array или null
    return isset($_SESSION['battleInfo']) ? $_SESSION['battleInfo'] : null;
}
function getTradeInfo($userId) {
    // return array или null
    return isset($_SESSION['tradeInfo']) ? $_SESSION['tradeInfo'] : null;
}
function getUserPokList($userId) {
    // return array
    return isset($_SESSION['userPokList']) ? $_SESSION['userPokList'] : [];
}
function getUserPokedex($userId) {
    // return array
    return isset($_SESSION['userPokedex']) ? $_SESSION['userPokedex'] : [];
}
function getLocationHtml($userId) {
    // return html-код локации
    return isset($_SESSION['locationHtml']) ? $_SESSION['locationHtml'] : '';
}
function getMd5LocList($userId) {
    // return string
    return isset($_SESSION['md5locList']) ? $_SESSION['md5locList'] : '';
}
function getBattleInfoRage($userId) {
    // return int
    return isset($_SESSION['battleInfoRage']) ? $_SESSION['battleInfoRage'] : 0;
}

// Собираем ответ
$response = [
    'battleInfo'      => getBattleInfo($userId),
    'tradeInfo'       => getTradeInfo($userId),
    'userPokList'     => getUserPokList($userId),
    'userPokedex'     => getUserPokedex($userId),
    'locationHtml'    => getLocationHtml($userId),
    'md5locList'      => getMd5LocList($userId),
    'battleInfoRage'  => getBattleInfoRage($userId)
    // Добавляйте другие поля по необходимости
];

// Чтобы не слать "null" если нет данных, убираем их из ответа
$response = array_filter($response, function($v) { return $v !== null; });

echo json_encode($response);
exit;
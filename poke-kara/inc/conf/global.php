<?php
ob_start(); // Включаем буферизацию вывода
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(); // Запускаем сессию только если ещё не запущена
}

// Функция завершения работы с выводом ошибки в JSON-формате
function closed($text = null, $param = null) {
    if ($text) {
        $response = array('error' => array('type' => 1, 'text' => $text, 'param' => $param));
        // Завершаем буферизацию и выводим только JSON-ответ
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    exit;
}

// Проверка пути проекта
if (!isset($patch_project) || empty($patch_project)) {
    closed('Ошибка: Путь к проекту не задан [Global::3].');
}

// Пути к необходимым файлам
$filePaths = [
    $patch_project . '/inc/conf/const.php',
    $patch_project . '/inc/function/Functions.php',
    $patch_project . '/inc/conf/connect.php',
    $patch_project . '/makasimka/inc/conf/global.php'
];

// Проверка наличия всех файлов перед подключением
foreach ($filePaths as $filePath) {
    if (!file_exists($filePath)) {
        closed('Ошибка: Отсутствует файл ' . $filePath);
    }
}

// Подключение файлов (избегаем повторного подключения)
foreach ($filePaths as $filePath) {
    if (!in_array($filePath, get_included_files())) {
        require_once($filePath);
    }
}

// Проверка определения константы SHOW_FILE
if (!defined('SHOW_FILE')) {
    closed('Ошибка: Константа SHOW_FILE не определена [Global::1].');
}

// Подключение к базе данных и выполнение запроса
$s = $mysqli->query("SELECT * FROM `system` WHERE `id` = 1");

if (!$s) {
    closed('Ошибка запроса к базе данных: ' . $mysqli->error);
}

// Получение данных из запроса
$systemData = $s->fetch_assoc();
if (!$systemData) {
    closed('Ошибка: В таблице `system` нет данных.');
}

// Сохранение данных в переменные
$mainTechWork = $systemData['closed'] ?? null;
$versionGame = $systemData['version'] ?? null;
$reitsGlobal = $systemData['reits'] ?? null;
$reitsGlobalText = $systemData['reits_text'] ?? null;

// Очистка буфера вывода (если не JSON-ответ)
if (ob_get_level()) ob_end_flush();
?>
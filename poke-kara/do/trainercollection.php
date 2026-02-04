<?php
// trainercollection.php — отдельный обработчик коллекции тренера

// Грамотное подключение конфигов и БД (универсально)
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$patch_func   = $patch_project . '/inc/function/Functions.php';
if (!file_exists($patch_global)) die('The problem with the connection files.');
require_once($patch_global);
if (file_exists($patch_func)) require_once($patch_func);

// Подключение БД (если не через глобальный конфиг)
$patch_db = $patch_project . '/inc/conf/db_connect.php';
if (file_exists($patch_db)) require_once($patch_db);

// Стартуем сессию, если не стартована
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Получение параметров фильтрации и поиска из AJAX
$filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

// Данные пользователя
$user_id = !empty($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if (!$user_id) {
    echo '<div class="no-pokemons">Пользователь не определён.</div>';
    exit;
}

// --- Данные берем с /do/Pokedex.php ---
$patch_pokedex = $patch_project . '/do/Pokedex.php';
if (!file_exists($patch_pokedex)) {
    echo '<div class="no-pokemons">Модуль Pokédex не найден.</div>';
    exit;
}

// Подключаем Pokédex и получаем коллекцию тренера
require_once($patch_pokedex);

// Универсальный вызов функции или обработка переменной (совместимость)
if (function_exists('getTrainerCollection')) {
    // Получаем html коллекции тренера
    $tpl = getTrainerCollection($user_id, $filter, $search);
    echo $tpl;
    exit;
} elseif (isset($response['html'])) {
    // Если Pokedex.php выставил $response['html']
    echo $response['html'];
    exit;
} elseif (function_exists('buildTrainerCollection')) {
    // Альтернативная функция, если другая структура
    $tpl = buildTrainerCollection($user_id, $filter, $search);
    echo $tpl;
    exit;
}

// Если ничего не получилось — выводим сообщение
echo '<div class="no-pokemons">Нет данных для отображения коллекции тренера.</div>';
?>
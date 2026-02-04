<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$patch_func = $patch_project . '/inc/function/Functions.php';

if (!file_exists($patch_global)) {
    echo json_encode(['text' => 'Ошибка подключения к файлам конфигурации.', 'error' => 'error']);
    exit;
}

require_once($patch_global);
require_once($patch_func);

// Включить отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Проверка наличия необходимых данных
if (!isset($_POST['name'], $_POST['type'], $_POST['price'], $_POST['description'])) {
    echo json_encode(['text' => 'Не все данные переданы.', 'error' => 'error']);
    exit;
}

$name = $mysqli->real_escape_string(trim($_POST['name']));
$type = $mysqli->real_escape_string(trim($_POST['type']));
$price = intval($_POST['price']);
$description = $mysqli->real_escape_string(trim($_POST['description']));

// Проверка на пустые данные
if (empty($name) || empty($type) || empty($price) || empty($description)) {
    echo json_encode(['text' => 'Все поля обязательны для заполнения.', 'error' => 'error']);
    exit;
}

// Проверка, существует ли предмет
$item_check = $mysqli->query("SELECT * FROM `base_items` WHERE `name` = '$name'");
if ($item_check && $item_check->num_rows > 0) {
    echo json_encode(['text' => 'Предмет с таким названием уже существует.', 'error' => 'error']);
    exit;
}

// Добавление нового предмета
$query = "INSERT INTO `base_items` (`name`, `type`, `price`, `description`) VALUES ('$name', '$type', $price, '$description')";
if ($mysqli->query($query)) {
    echo json_encode(['text' => 'Предмет успешно создан!', 'error' => 'success']);
} else {
    echo json_encode(['text' => 'Ошибка при создании предмета: ' . $mysqli->error, 'error' => 'error']);
}

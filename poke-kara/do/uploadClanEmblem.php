<?php
session_start();

if (!isset($_SESSION['id'])) {
    die('Вы не авторизованы.');
}

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/world/clans/emblems/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die('Ошибка при загрузке файла.');
}

$allowedFormats = ['image/png', 'image/jpeg'];
$fileType = mime_content_type($_FILES['file']['tmp_name']);

if (!in_array($fileType, $allowedFormats)) {
    die('Разрешены только PNG и JPEG файлы.');
}

$extension = $fileType === 'image/png' ? 'png' : 'jpg';
$fileName = uniqid('clan_', true) . '.' . $extension;
$filePath = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
    echo 'Эмблема успешно загружена! Путь к эмблеме: /img/world/clans/emblems/' . $fileName;
} else {
    die('Не удалось сохранить эмблему.');
}
?>

<?php
$pack = isset($_GET['pack']) ? preg_replace('/[^a-z0-9]/i', '', $_GET['pack']) : '';
$dir = $pack !== '' ? __DIR__ . "/img/em/$pack" : '';

if (!$pack || $dir === '' || !is_dir($dir)) {
    echo json_encode(["emojis" => []]);
    exit;
}

$files = array_diff(scandir($dir), ['.', '..']); // Получаем список файлов

header('Content-Type: application/json');
echo json_encode(["emojis" => $files]);

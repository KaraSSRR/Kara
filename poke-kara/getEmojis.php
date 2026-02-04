<?php
$pack = isset($_GET['pack']) ? $_GET['pack'] : '';
$dir = __DIR__ . "/img/em/$pack";

if (!$pack || !is_dir($dir)) {
    echo json_encode(["emojis" => []]);
    exit;
}

$files = array_diff(scandir($dir), ['.', '..']); // Получаем список файлов

header('Content-Type: application/json');
echo json_encode(["emojis" => $files]);

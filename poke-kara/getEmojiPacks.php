<?php
$pack = isset($_GET['pack']) ? preg_replace('/[^a-z0-9]/i', '', $_GET['pack']) : '';
$directory = __DIR__ . "/img/em/$pack/";

$allowedFormats = ['png', 'gif'];
$emojis = [];

if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($ext, $allowedFormats)) {
            $emojis[] = $file;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($emojis);
?>

<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
        require_once($patch_func);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    exit;
}

$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($userId <= 0) {
    exit;
}

$op = isset($_POST['op']) ? (int)$_POST['op'] : 0;
if ($op === 1) {
    $text = trim((string)($_POST['text'] ?? ''));
    $text = escapeMe($text);
    if ($text !== '' && isset($mysqli) && $mysqli instanceof mysqli) {
        $stmt = $mysqli->prepare('INSERT INTO `opros` (`user`,`text`) VALUES (?, ?)');
        if ($stmt) {
            $stmt->bind_param('is', $userId, $text);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $stmt = $mysqli->prepare('UPDATE `users` SET `opros` = 0 WHERE `id` = ?');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

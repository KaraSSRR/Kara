<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die(json_encode(['error' => 'No config']));
    }else{
        require_once($patch_global);
    }
}
session_start();
if(!isset($_SESSION['id']) || !isset($_COOKIE['hash'])){
    echo json_encode(['error' => 'not authorized']);
    exit;
}
$user = $mysqli->query('SELECT `id`,`login`,`user_group`,`rang`,`hash`,`location`,`sound`,`lang`,`opros`,`HotClick` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
if($_COOKIE['hash'] != $user['hash']){
    echo json_encode(['error' => 'bad hash']);
    exit;
}

// --- Пример: уведомления, чат, фреймы, статус и т.п. ---
// Реализуйте эти функции так, чтобы они возвращали актуальные данные для пользователя в HTML или JSON
$notify = render_notifications($user['id']); // HTML уведомлений
$chat = render_chat($user['id']); // HTML чата/или сообщения массивом
$frames = render_frames($user['id']); // HTML или массив для фреймов

echo json_encode([
    'notifications' => $notify,
    'chat' => $chat,
    'frames' => $frames,
    // добавьте любые нужные поля
]);
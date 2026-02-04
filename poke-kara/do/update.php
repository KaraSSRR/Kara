<?php
// /do/update.php

// --- 1. ПОДКЛЮЧЕНИЕ ОСНОВНЫХ ФАЙЛОВ ВАШЕГО ПРОЕКТА ---
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$patch_func = $patch_project . '/inc/function/Functions.php';

if (file_exists($patch_global) && file_exists($patch_func)) {
    require_once($patch_global);
    require_once($patch_func);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Core project files not found.']);
    exit();
}

// --- 2. НАСТРОЙКИ ДЛЯ СВЯЗИ С NODE.JS СЕРВЕРОМ (ДЛЯ PHP) ---
define('NODE_SERVER_IP', '90.156.169.219'); // IP-адрес вашего VPS
define('NODE_SERVER_PORT', 8081);
define('NODE_SERVER_SECRET', 'gT8$pL#w9!zXcVbN@q7'); // TODO: Замените на ваш собственный сложный ключ!

/**
 * Функция для отправки событий из PHP на Node.js сервер.
 * Она по-прежнему использует прямой IP, так как это связь сервер-сервер.
 */
function push_to_socket($target, $eventName, $data) {
    $url = 'http://' . NODE_SERVER_IP . ':' . NODE_SERVER_PORT . '/send-message';
    $payload = json_encode([
        'secret' => NODE_SERVER_SECRET,
        'target' => $target,
        'event'  => $eventName,
        'data'   => $data
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 1,
        CURLOPT_NOSIGNAL => 1,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// --- 3. ОБРАБОТКА AJAX-ЗАПРОСА ОТ КЛИЕНТА ---
header('Content-Type: application/json');

if (isset($_POST['type']) && $_POST['type'] === 'socket_init') {
    
    // Используем $_SESSION['id'] для проверки авторизации
    if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
        
        $token = $_SESSION['id'];
        
        $response = [
            'status' => 'success',
            // --- ГЛАВНОЕ ИСПРАВЛЕНИЕ ---
            // Указываем безопасный поддомен без порта
            'socket_server' => 'wss://ws.poke-kara.ru',
            'token' => $token,
            'user_start' => 0,
        ];
        
    } else {
        $response = ['status' => 'error', 'message' => 'User not authenticated'];
    }
    
    echo json_encode($response);
    exit();
}

echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request type'
]);
exit();
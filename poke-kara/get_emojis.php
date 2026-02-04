<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$patch_func = $patch_project . '/inc/function/Functions.php';
require_once($patch_global);
require_once($patch_func);

$response = [];
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['id']) ? intval($_SESSION['id']) : 0);

$pack = isset($_GET['pack']) ? preg_replace('/[^a-z0-9]/i', '', $_GET['pack']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

$packs_free = ['pack1', 'pack2'];
$pack_prices = [
    'pack3' => 50,
    'pack4' => 50,
    'pack5' => 50,
    'pack6' => 50,
];

// --- Проверка подключения к БД ---
if (!isset($mysqli) || !$mysqli) {
    header('Content-Type: application/json');
    echo json_encode([
        'plus' => '',
        'error' => 1,
        'text' => 'Ошибка соединения с базой данных!',
        'minus' => ''
    ]);
    exit;
}

// --- Покупка пака смайлов ---
if ($action === 'buy') {
    if (!$user_id || !$pack) {
        $response = [
            'plus' => '',
            'error' => 1,
            'text' => 'Ошибка авторизации или неверный набор!',
            'minus' => ''
        ];
        echo json_encode($response); exit;
    }
    if (in_array($pack, $packs_free)) {
        $response = [
            'plus' => '',
            'error' => 1,
            'text' => 'Этот набор бесплатный!',
            'minus' => ''
        ];
        echo json_encode($response); exit;
    }
    $already = $mysqli->query("SELECT 1 FROM user_emoji_packs WHERE user_id = $user_id AND emoji_pack = '$pack'");
    if ($already && $already->fetch_row()) {
        $response = [
            'plus' => '',
            'error' => 1,
            'text' => 'Набор уже куплен!',
            'minus' => ''
        ];
        echo json_encode($response); exit;
    }

    $price = isset($pack_prices[$pack]) ? $pack_prices[$pack] : 50;

    // --- Проверка наличия item 25 (камни) ---
    $r = $mysqli->query("SELECT count FROM items_users WHERE user = $user_id AND item_id = 25");
    $row = $r ? $r->fetch_assoc() : null;
    if (!$row || $row['count'] < $price) {
        $response = [
            'plus' => '',
            'error' => 1,
            'text' => 'Недостаточно драгоценных камней!',
            'minus' => ''
        ];
        echo json_encode($response); exit;
    }

    // Списание камней
    $update = $mysqli->query("UPDATE items_users SET count = count - $price WHERE user = $user_id AND item_id = 25");
    if (!$update) {
        $response = [
            'plus' => '',
            'error' => 1,
            'text' => 'Ошибка списания камней!',
            'minus' => ''
        ];
        echo json_encode($response); exit;
    }

    // Добавляем набор
    $insert = $mysqli->query("INSERT INTO user_emoji_packs (user_id, emoji_pack, date_buy) VALUES ($user_id, '$pack', NOW())");
    if (!$insert) {
        $response = [
            'plus' => '',
            'error' => 1,
            'text' => 'Ошибка добавления набора!',
            'minus' => ''
        ];
        echo json_encode($response); exit;
    }

    // Формируем уведомление как в магазине
    // Можно добавить иконку набора, если она есть: icon.png или первый смайл
    $icon_file = '/img/em/'.$pack.'/icon.png';
    // Если файла нет, можно использовать первый смайл из набора (или стандартную картинку)
    if (!file_exists($_SERVER['DOCUMENT_ROOT'].$icon_file)) {
        $directory = __DIR__ . "/img/em/$pack/";
        $files = is_dir($directory) ? scandir($directory) : [];
        $icon_file = '';
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($file != '.' && $file != '..' && ($ext === 'png' || $ext === 'gif')) {
                $icon_file = '/img/em/'.$pack.'/'.$file;
                break;
            }
        }
        if (!$icon_file) $icon_file = '/img/em/default_pack_icon.png'; // если нет ни одного смайла
    }
    $response = [
        'plus' => '<img src="'.$icon_file.'" class="item"> Получен набор смайлов <b>'.$pack.'</b><br>',
        'error' => 0,
        'text' => 'Набор куплен! Для корректного отображения новых смайлов, пожалуйста, <b>обновите страницу</b>.',
        'minus' => '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x'.$price.'</b><br>',
        'pack' => $pack
    ];
    echo json_encode($response); exit;
}

// --- Получение всех купленных паков пользователя ---
if ($action === 'get_packs') {
    $packs = $packs_free;
    if ($user_id) {
        $res = $mysqli->query("SELECT emoji_pack FROM user_emoji_packs WHERE user_id = $user_id");
        if ($res) {
            while($row = $res->fetch_assoc()) $packs[] = $row['emoji_pack'];
        }
    }
    echo json_encode(array_unique($packs)); exit;
}

// --- Выдача списка смайлов (ВСЕГДА, даже если набор не куплен) ---
if ($pack) {
    $directory = __DIR__ . "/img/em/$pack/";
    $allowedFormats = ['png', 'gif'];
    $emojis = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($ext, $allowedFormats)) {
                $emojis[] = $file;
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($emojis); exit;
}

// --- Если нет параметров ---
header('Content-Type: application/json');
echo json_encode([
    'plus' => '',
    'error'=>1,
    'text'=>'Некорректный запрос',
    'minus' => ''
]);
exit;
?>
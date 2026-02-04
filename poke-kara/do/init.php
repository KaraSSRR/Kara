<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';

// Проверяем существование файлов перед подключением
if (!file_exists($patch_global) || !file_exists($patch_func)) {
    die('The problem with the connection files.');
}

require_once($patch_global);
require_once($patch_func);

# GET USER INFO
if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    die(json_encode(['error' => 'Invalid session']));
}

$user_id = (int)$_SESSION['id'];

$stmt = $mysqli->prepare('SELECT `login`, `user_group`, `rang`, `location`, `LastPrize` FROM `users` WHERE `id` = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die(json_encode(['error' => 'User not found']));
}

# UPDATE USER COUNT POKEMONS
$stmt = $mysqli->prepare("SELECT COUNT(DISTINCT `basenum`) as count FROM `user_pokemons` WHERE `user_id` = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$countPoks = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare("SELECT COUNT(DISTINCT `basenum`) as count FROM `user_pokemons` WHERE `user_id` = ? AND `type` = 'shine'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$countPoksShine = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $mysqli->prepare('UPDATE `users` SET `countShine` = ?, `countNormal` = ? WHERE `id` = ?');
$stmt->bind_param('iii', $countPoksShine['count'], $countPoks['count'], $user_id);
$stmt->execute();
$stmt->close();

# SENT ALL DATA AJAX
$patch_avatars = $patch_project.'/img/avatars/mini/'.$user_id.'.png';

$response['data'] = [
    'login'      => mb_strimwidth(htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8'), 0, 15, "..."),
    'rang'       => mb_strimwidth(htmlspecialchars($user['rang'], ENT_QUOTES, 'UTF-8'), 0, 15, "..."),
    'user_group' => (int)$user['user_group'],
    'img'        => file_exists($patch_avatars) ? $user_id : "no-user-img",
    'newPrize'   => isset($user['LastPrize']) && $user['LastPrize'] < date('Y-m-d')
];

echo json_encode($response);
?>

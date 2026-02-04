<?php
session_start();

ini_set('display_errors', 'ON');
error_reporting(E_ALL);

$patch_global = $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';
if (!file_exists($patch_global)) {
    _setError('The problem with the connection files.');
}
require_once($patch_global);

/** @var mysqli $mysqli */
$userID = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if ($userID <= 0) {
    _setError('К сожалению, доступ сюда закрыт.');
}

$userInfoQ = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$userID);
if (!$userInfoQ || !($userInfo = $userInfoQ->fetch_assoc())) {
    _setError('Пользователь не найден.');
}

$talk = new MyTalk($userInfo, $mysqli);

print Info::_parseData($talk->response());
<?php
// encyclopedia/_inc/bootstrap.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Prevent "headers already sent" issues on saves/redirects (global.php may echo/warn)
if (!ob_get_level()) {
    @ob_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Игра ожидает переменную $patch_project и константу SHOW_FILE до подключения global.php
$patch_project = (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : realpath(dirname(dirname(__DIR__))));
if (!defined('SHOW_FILE')) {
    define('SHOW_FILE', true);
}

$patch_global = rtrim($patch_project, '/') . '/inc/conf/global.php';
if (!file_exists($patch_global)) {
    http_response_code(500);
    die('Ошибка энциклопедии: отсутствует конфигурация игры.');
}
require_once $patch_global;

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    die('Ошибка энциклопедии: база данных недоступна.');
}

@$mysqli->set_charset('utf8mb4');
@$mysqli->query("SET NAMES utf8mb4");

$encyInstalled = enc_is_installed($mysqli);

$encyHasBuildIndex = $encyInstalled ? enc_has_build_index($mysqli) : false;
$encyHasPopularity = $encyInstalled ? enc_has_popularity($mysqli) : false;

$encyUserId = enc_current_user_id();
$encyUser = $encyUserId > 0 ? enc_fetch_user($mysqli, $encyUserId) : null;
if ($encyUserId > 0 && !$encyUser) {
    // Fallback for schemas where enc_fetch_user cannot read optional columns
    $encyUser = ['id'=>$encyUserId,'login'=>'','user_group'=>0];
}

$encyIsAdmin = enc_is_admin_user($encyUser);

?>

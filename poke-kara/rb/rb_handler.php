<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php');

$userId = $_SESSION['id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'not_auth']);
    exit;
}

if ($action == 'give') {
    rb_givePokemons($mysqli, $userId);
    echo json_encode(['success' => true]);
} elseif ($action == 'remove') {
    rb_removePokemons($mysqli, $userId);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'no_action']);
}
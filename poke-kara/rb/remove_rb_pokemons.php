<?php
// Простая обработка ajax-запроса на удаление всех временных (РБ) покемонов пользователя
header('Content-Type: application/json');

require_once($_SERVER['DOCUMENT_ROOT'].'/constants.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');

$userId = intval($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Некорректный user_id']);
    exit;
}

// Удаляем всех покемонов с static='rb'
if ($mysqli->query("DELETE FROM user_pokemons WHERE user_id = $userId AND static = 'rb'")) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $mysqli->error]);
}
?>
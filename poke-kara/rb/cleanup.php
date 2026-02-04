<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');
$userId = intval($_SESSION['id'] ?? 0);
if ($userId > 0) {
    rb_removePokemons($mysqli, $userId);
    $mysqli->query("UPDATE users SET status = 'free', status_id = 0 WHERE id = $userId");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
exit;
?>
<?php
require 'config.php';

$toUser = intval($_POST['to_user']);
$userId = $_SESSION['user_id']; // Текущий пользователь

$sql = "SELECT * FROM chat_new 
        WHERE type = 1 
        AND (user = ? OR touser = ?) 
        ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $toUser]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);
?>

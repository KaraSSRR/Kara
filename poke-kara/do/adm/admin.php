<?php
require 'DolznPanel.php';
require 'auth.php'; // авторизація користувача

$response = [];
new DolznPanel($_POST['type'] ?? '', $_POST['val'] ?? [], $userInfo, $response);
echo json_encode($response);

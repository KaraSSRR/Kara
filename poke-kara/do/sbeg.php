<?php
// /do/sbeg/ — контроллер управления шансом "сбегов"
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';
header('Content-Type: application/json; charset=utf-8');

// Разрешаем только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// TODO: замените на вашу реальную проверку прав администратора
if (empty($_SESSION['id']) || empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : null;   // 0/1
$mult    = isset($_POST['mult'])    ? (float)$_POST['mult']    : null; // например 1, 2, 3, 1.5

$sets = [];
if ($enabled !== null) $sets[] = 'sbeg_enabled='.(int)$enabled;
if ($mult !== null)    $sets[] = 'sbeg_multiplier='.number_format(max(0.0, $mult), 2, '.', '');

if (!$sets) {
    echo json_encode(['ok'=>false,'error'=>'no_fields']);
    exit;
}

$sql = 'UPDATE `system` SET '.implode(', ', $sets).' LIMIT 1';
$ok  = Work::$sql->query($sql);

// Вернём актуальные значения для UI
$current = Work::$sql->query('SELECT sbeg_enabled, sbeg_multiplier FROM `system` LIMIT 1')->fetch_assoc();

echo json_encode([
    'ok' => (bool)$ok,
    'data' => [
        'sbeg_enabled'    => isset($current['sbeg_enabled']) ? (int)$current['sbeg_enabled'] : null,
        'sbeg_multiplier'  => isset($current['sbeg_multiplier']) ? (float)$current['sbeg_multiplier'] : null,
    ]
], JSON_UNESCAPED_UNICODE);
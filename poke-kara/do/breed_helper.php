<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/classes/ReproductionHelper.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/classes/Reproduction.php';

session_start();

$mysqli = $GLOBALS['mysqli'];
$userId = $_SESSION['id'];

$input = json_decode(file_get_contents("php://input"), true);
$type = $input['type'] ?? '';

$helper = new ReproductionHelper($mysqli, $userId);

if ($type === 'massBreed') {
  // Приклад простого підбору пар
  $all = $helper->getPossiblePartners($input['pok1'] ?? 0); // або отримаємо список автоматично
  $pairs = [];

  for ($i = 0; $i < count($all) - 1; $i += 2) {
    $pairs[] = [$all[$i]['id'], $all[$i+1]['id']];
  }

  $helper->massBreed($pairs);
}
?>

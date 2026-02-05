<?php
// Legacy wrapper: all clan actions are handled by /do/clanAction.php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$clanAction = $patch_project . '/do/clanAction.php';
if (is_file($clanAction)) {
    require $clanAction;
    exit;
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error'=>1,'success'=>false,'text'=>'clanAction.php not found'], JSON_UNESCAPED_UNICODE);

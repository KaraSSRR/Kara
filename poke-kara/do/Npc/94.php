<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$uid = (int)($_SESSION['id'] ?? 0);
$npcStep = $_POST['npcStep'] ?? "false";
if (!$uid) pp_json(['name'=>'Северный терминал','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_HALL_N])) pp_json(pp_fail_location_response('Северный терминал'));

$state = pp_get($uid); $step = (int)$state['step'];

if ($npcStep === "false") {
    if ($step!=2) pp_json(['name'=>'Северный терминал','question'=>'Неактивно. Обратитесь к диспетчеру.','answer'=>[0=>'Ок'],'closeDialog'=>true]);
    if ((int)($state['flags']['panel_north']??0)) pp_json(['name'=>'Северный терминал','question'=>'Уже перезапущен.','answer'=>[0=>'Ок'],'closeDialog'=>true]);
    pp_json(['name'=>'Северный терминал','question'=>'Ошибка питания. Выполнить перезапуск?','answer'=>[2=>'Перезапустить',0=>'Отмена']]);
}
if ((int)$npcStep===2 && $step==2){
    pp_flag($uid,'panel_north',1);
    pp_notify($uid,'Северный терминал перезапущен.');
    pp_json(['name'=>'Северный терминал','question'=>'Перезапуск выполнен.','answer'=>[0=>'Готово'],'closeDialog'=>true]);
}
pp_json(['name'=>'Северный терминал','question'=>'Готово.','answer'=>[0=>'Ок'],'closeDialog'=>true]);

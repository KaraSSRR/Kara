<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$uid = (int)($_SESSION['id'] ?? 0);
$npcStep = $_POST['npcStep'] ?? "false";
if (!$uid) pp_json(['name'=>'Главный рубильник','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_SWITCH])) pp_json(pp_fail_location_response('Главный рубильник'));

$state=pp_get($uid); $step=(int)$state['step'];

if ($npcStep === "false") {
    if ($step<4){
        pp_json(['name'=>'Главный рубильник','question'=>'Требуются установленные предохранители и допуск инженера.',
                 'answer'=>[0=>'Понял'],'closeDialog'=>true]);
    } elseif ($step==4){
        pp_json(['name'=>'Главный рубильник','question'=>'Готов к перезапуску. Поднять рубильник?',
                 'answer'=>[2=>'Поднять',0=>'Отмена']]);
    } else {
        pp_json(['name'=>'Главный рубильник','question'=>'Станция в штатном режиме.',
                 'answer'=>[0=>'Ок'],'closeDialog'=>true]);
    }
}
if ((int)$npcStep===2 && $step==4){
    pp_set_step($uid,5,['main_switch'=>1]);
    pp_notify($uid,'Питание восстановлено! Идите к директору за наградой.');
    pp_json(['name'=>'Главный рубильник','question'=>'Перезапуск выполнен.',
             'answer'=>[0=>'К директору'],'closeDialog'=>true]);
}
pp_json(['name'=>'Главный рубильник','question'=>'В норме.','answer'=>[0=>'Ок'],'closeDialog'=>true]);

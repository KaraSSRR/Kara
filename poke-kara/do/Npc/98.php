<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$uid = (int)($_SESSION['id'] ?? 0);
$npcStep = $_POST['npcStep'] ?? "false";
if (!$uid) pp_json(['name'=>'Директор станции','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_OFFICE])) pp_json(pp_fail_location_response('Директор станции'));

$state=pp_get($uid); $step=(int)$state['step'];

if ($npcStep === "false") {
    if ($step<5){
        pp_json(['name'=>'Директор станции','question'=>'Обратись к персоналу станции.',
                 'answer'=>[0=>'Понял'],'closeDialog'=>true]);
    } elseif ($step==5){
        pp_json(['name'=>'Директор станции','question'=>'Станция благодарит за помощь. Прими награду.',
                 'answer'=>[2=>'Получить'],'closeDialog'=>false]);
    } else {
        pp_json(['name'=>'Директор станции','question'=>'Рад видеть тебя снова.',
                 'answer'=>[0=>'До встречи'],'closeDialog'=>true]);
    }
}
if ((int)$npcStep===2 && $step==5){
    pp_item_add(PP_REWARD_ITEM,1,$uid);
    if (function_exists('itemAdd')) itemAdd(1, PP_REWARD_COINS, $uid);
    pp_set_step($uid,6,['reward'=>1]);
    pp_notify($uid,'Квест «Электростанция» завершён. Награда выдана.');
    pp_json(['name'=>'Директор станции','question'=>'Заслужено! Удачи.',
             'answer'=>[0=>'Спасибо'],'closeDialog'=>true]);
}
pp_json(['name'=>'Директор станции','question'=>'Удачной смены.','answer'=>[0=>'Спасибо'],'closeDialog'=>true]);

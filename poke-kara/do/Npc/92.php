<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$uid = (int)($_SESSION['id'] ?? 0);
$npcStep = $_POST['npcStep'] ?? "false";
if (!$uid) pp_json(['name'=>'Инженер Барнс','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_TECH])) pp_json(pp_fail_location_response('Инженер Барнс'));

$state=pp_get($uid); $step=(int)$state['step'];

if ($npcStep === "false") {
    if ($step<3){
        pp_json(['name'=>'Инженер Барнс','question'=>'Если не от Лины — не мешай.',
                 'answer'=>[0=>'Ладно'],'closeDialog'=>true]);
    } elseif ($step==3){
        if (!pp_item_has(PP_FUSE_ITEM,3,$uid)){
            pp_json(['name'=>'Инженер Барнс','question'=>'Нужны три предохранителя. В залах и на складе точно найдёшь.',
                     'answer'=>[0=>'Искать',4=>'Подскажи точнее']]);
        } else {
            pp_json(['name'=>'Инженер Барнс','question'=>'Вижу 3 предохранителя. Устанавливаем?',
                     'answer'=>[2=>'Установить',0=>'Потом']]);
        }
    } elseif ($step==4){
        pp_json(['name'=>'Инженер Барнс','question'=>'Идём к главному рубильнику.',
                 'answer'=>[0=>'Вперёд'],'closeDialog'=>true]);
    } else {
        pp_json(['name'=>'Инженер Барнс','question'=>'Отличная работа. Директор ждёт.',
                 'answer'=>[0=>'Хорошо'],'closeDialog'=>true]);
    }
}

if ((int)$npcStep===4 && $step==3){
    pp_json(['name'=>'Инженер Барнс','question'=>'Поищи ящики у распределительных шкафов в залах N/W/E и на складе.',
             'answer'=>[0=>'Понял'],'closeDialog'=>true]);
}

if ((int)$npcStep===2 && $step==3){
    if (!pp_item_has(PP_FUSE_ITEM,3,$uid)){
        pp_json(['name'=>'Инженер Барнс','question'=>'Маловато. Нужно ровно три.',
                 'answer'=>[0=>'Ок'],'closeDialog'=>true]);
    }
    pp_item_take(PP_FUSE_ITEM,3,$uid);
    pp_set_step($uid,4,['fuses_installed'=>1]);
    pp_notify($uid,'Предохранители установлены. Поднимите главный рубильник.');
    pp_json(['name'=>'Инженер Барнс','question'=>'Готово. Остался главный рубильник в щитовой.',
             'answer'=>[0=>'Иду'],'closeDialog'=>true]);
}

pp_json(['name'=>'Инженер Барнс','question'=>'Встретимся у рубильника.','answer'=>[0=>'Ок'],'closeDialog'=>true]);

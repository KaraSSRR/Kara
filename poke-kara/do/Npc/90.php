<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$response = ['name' => 'Охранник КПП'];
$uid = (int)($_SESSION['id'] ?? 0);
$npcStep = $_POST['npcStep'] ?? "false";
if (!$uid) pp_json(['name'=>'Охранник КПП','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_GATE])) pp_json(pp_fail_location_response('Охранник КПП'));

$state = pp_get($uid); $step = (int)$state['step'];

if ($npcStep === "false") {
    if ($step <= 0) {
        pp_json(['name'=>'Охранник КПП','question'=>'Без допуска нельзя. Помощь нужна — выручишь?',
                 'answer'=>[2=>'Готов помочь',0=>'Не сейчас']]);
    } elseif ($step == 1) {
        pp_json(['name'=>'Охранник КПП','question'=>'Диспетчер в операторской, проходи.',
                 'answer'=>[0=>'Иду'],'closeDialog'=>true]);
    } else {
        pp_json(['name'=>'Охранник КПП','question'=>'Не задерживайся внутри.',
                 'answer'=>[0=>'Ок'],'closeDialog'=>true]);
    }
}

if ((int)$npcStep === 2 && $step <= 0) {
    if (!pp_item_has(PP_BADGE_ITEM,1,$uid)) pp_item_add(PP_BADGE_ITEM,1,$uid);
    pp_set_step($uid,1,['badge'=>1]);
    pp_notify($uid,'Получен допуск. Идите в операторскую к диспетчеру.');
    pp_json(['name'=>'Охранник КПП','question'=>'Вот пропуск. Операторская — по коридору направо.',
             'answer'=>[0=>'Понял'],'closeDialog'=>true]);
}

pp_json(['name'=>'Охранник КПП','question'=>'Ничего нового.','answer'=>[0=>'Назад'],'closeDialog'=>true]);

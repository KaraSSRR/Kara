<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$uid = (int)($_SESSION['id'] ?? 0);
$npcStep = $_POST['npcStep'] ?? "false";
if (!$uid) pp_json(['name'=>'Диспетчер Лина','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_OPERATOR])) pp_json(pp_fail_location_response('Диспетчер'));

$state = pp_get($uid); $step = (int)$state['step'];

if ($npcStep === "false") {
    if ($step == 1) {
        pp_json(['name'=>'Диспетчер Лина',
                 'question'=>'Нужно перезапустить три терминала — Северный, Западный и Восточный. Поможешь?',
                 'answer'=>[2=>'Сделаю',0=>'Позже']]);
    } elseif ($step == 2) {
        $f=$state['flags']; $done=(int)($f['panel_north']??0)+(int)($f['panel_west']??0)+(int)($f['panel_east']??0);
        if ($done>=3){
            pp_json(['name'=>'Диспетчер Лина',
                     'question'=>'Отлично! Иди к инженеру Барнсу на техэтаж — нужны 3 предохранителя.',
                     'answer'=>[3=>'Где Барнс?']]);
        } else {
            pp_json(['name'=>'Диспетчер Лина','question'=>'Осталось терминалов: '.(3-$done).'.',
                     'answer'=>[0=>'Побежал'],'closeDialog'=>true]);
        }
    } elseif ($step>=3) {
        pp_json(['name'=>'Диспетчер Лина','question'=>'Барнс ждёт тебя на техэтаже.',
                 'answer'=>[0=>'Ок'],'closeDialog'=>true]);
    } else {
        pp_json(['name'=>'Диспетчер Лина','question'=>'Сначала получи допуск у охраны.',
                 'answer'=>[0=>'Понял'],'closeDialog'=>true]);
    }
}

if ((int)$npcStep === 2 && $step==1){
    pp_set_step($uid,2,['panel_north'=>0,'panel_west'=>0,'panel_east'=>0]);
    pp_notify($uid,'Задание: перезапустить Север/Запад/Восток терминалы.');
    pp_json(['name'=>'Диспетчер Лина','question'=>'Запусти три терминала и вернись.',
             'answer'=>[0=>'Понял'],'closeDialog'=>true]);
}

if ((int)$npcStep === 3 && $step==2){
    pp_set_step($uid,3);
    pp_notify($uid,'Найди инженера Барнса на техэтаже. Нужны 3 предохранителя.');
    pp_json(['name'=>'Диспетчер Лина','question'=>'Барнс — на техэтаже, у щитов.',
             'answer'=>[0=>'Иду'],'closeDialog'=>true]);
}

pp_json(['name'=>'Диспетчер Лина','question'=>'Действуй по плану.','answer'=>[0=>'Ок'],'closeDialog'=>true]);

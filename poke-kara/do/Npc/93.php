<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');

$uid = (int)($_SESSION['id'] ?? 0);
if (!$uid) pp_json(['name'=>'Мастер смены','question'=>'Ошибка авторизации','closeDialog'=>true]);

if (!pp_on_locations($uid,[PP_LOC_OPERATOR,PP_LOC_TECH])) pp_json(pp_fail_location_response('Мастер смены'));

$state = pp_get($uid); $step = (int)$state['step'];
$npcStep = $_POST['npcStep'] ?? "false";

if ($npcStep === "false") {
    if ($step<=1) {
        pp_json(['name'=>'Мастер смены','question'=>'Электрики дальше по коридору. Допуск не забудь.',
                 'answer'=>[0=>'Спасибо'],'closeDialog'=>true]);
    } elseif ($step==2) {
        pp_json(['name'=>'Мастер смены','question'=>'Терминалы по залам N/W/E. Начни с ближайшего.',
                 'answer'=>[0=>'Принято'],'closeDialog'=>true]);
    } elseif ($step==3) {
        pp_json(['name'=>'Мастер смены','question'=>'Барнс на техэтаже ищет предохранители.',
                 'answer'=>[0=>'Ок'],'closeDialog'=>true]);
    } else {
        pp_json(['name'=>'Мастер смены','question'=>'Сегодня спокойно — спасибо тебе.',
                 'answer'=>[0=>'Рад помочь'],'closeDialog'=>true]);
    }
}

pp_json(['name'=>'Мастер смены','question'=>'Работаем.','answer'=>[0=>'Ладно'],'closeDialog'=>true]);

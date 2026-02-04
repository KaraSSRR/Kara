<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 83 — Смотритель питомника
 * Квест: "Инкубатор трёх стихий" (quest_id = 130)
 *
 * v5 (fix):
 *  - Яйцо НЕ выдаётся при старте (выдаётся только в финале после калибровки)
 *  - Исправлены обрезанные строки/ошибки (без "..." внутри кода)
 *  - Повторяемость: раз в 3 дня после завершения (repeat_at, с fallback если колонки нет)
 *  - PHP 5.6 safe
 */

$questId = 130;
$npcId   = 83;

if (!isset($npcStep)) $npcStep = 1;

// NPC meta
$npc = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId." LIMIT 1")->fetch_assoc();
$response = array();
$response['name']  = !empty($npc['name']) ? $npc['name'] : 'Неизвестный NPC';
$response['image'] = !empty($npc['image']) ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
$now    = time();

// Иконка модуля
$moduleHint = '<img src="/img/world/items/little/5026.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> <b>Калибровочный модуль</b>';

// user_quests (с repeat_at, если колонка есть)
$uq = null;
$repeatAt = 0;
$isEnd = 0;
$step  = 0;

if ($userId > 0) {
    $q = $mysqli->query("SELECT `step`,`end`,`repeat_at` FROM `user_quests` WHERE `user_id`=".$userId." AND `quest_id`=".$questId." LIMIT 1");
    if ($q) {
        $uq = $q->fetch_assoc();
        $step = !empty($uq['step']) ? (int)$uq['step'] : 0;
        $isEnd = !empty($uq['end']) ? (int)$uq['end'] : 0;
        $repeatAt = isset($uq['repeat_at']) ? (int)$uq['repeat_at'] : 0;
    } else {
        // fallback если repeat_at отсутствует
        $q = $mysqli->query("SELECT `step`,`end` FROM `user_quests` WHERE `user_id`=".$userId." AND `quest_id`=".$questId." LIMIT 1");
        if ($q) {
            $uq = $q->fetch_assoc();
            $step = !empty($uq['step']) ? (int)$uq['step'] : 0;
            $isEnd = !empty($uq['end']) ? (int)$uq['end'] : 0;
            $repeatAt = 0;
        }
    }
}

// формат времени до повтора
$leftStr = '';
if ($repeatAt > $now) {
    $left = $repeatAt - $now;
    $d = floor($left / 86400); $left = $left % 86400;
    $h = floor($left / 3600);  $left = $left % 3600;
    $m = floor($left / 60);
    if ($d > 0) $leftStr .= $d.' д. ';
    if ($h > 0) $leftStr .= $h.' ч. ';
    $leftStr .= $m.' мин.';
}

// Пулы яйца по стихиям (выдаём в ФИНАЛЕ)
$poolFire  = array(37, 58, 77);
$poolWater = array(54, 60, 72);
$poolGrass = array(273, 69, 191);

function q130_make_gens($branch) {
    $g1 = mt_rand(16,20);
    $g2 = mt_rand(16,20);
    $g3 = mt_rand(16,20);
    $g4 = mt_rand(16,20);
    $g5 = mt_rand(16,20);
    $g6 = mt_rand(16,20);

    // небольшие тематические бонусы (как было задумано)
    if ($branch == 10) $g2 = min(31, $g2 + 2); // огонь
    if ($branch == 20) $g4 = min(31, $g4 + 2); // вода
    if ($branch == 30) $g1 = min(31, $g1 + 2); // трава

    return $g1.','.$g2.','.$g3.','.$g4.','.$g5.','.$g6;
}


function q130_poke_name($num) {
    $num = (int)$num;
    global $mysqli;
    if (!$mysqli) return '#'.$num;

    $q = $mysqli->query("SELECT `name_rus`,`name` FROM `base_pokemons` WHERE `id`=".$num." LIMIT 1");
    if ($q) {
        $r = $q->fetch_assoc();
        if (!empty($r['name_rus'])) return $r['name_rus'];
        if (!empty($r['name'])) return $r['name'];
    }
    return '#'.$num;
}

function q130_item_line($id, $cnt) {
    $id  = (int)$id;
    $cnt = (int)$cnt;
    $name = null;
    if (function_exists('item_info')) $name = item_info($id, 'name');
    if (!$name) $name = 'Предмет #'.$id;

    $icon = '/img/world/items/little/'.$id.'.png';
    return '<img src="'.$icon.'" class="item" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;border-radius:8px;"> <b>'.$name.'</b> x'.$cnt;
}


switch ($npcStep) {

    default:

        // Повторяемость: если выполнен — показываем таймер или кнопку повтора
        if ($isEnd == 1) {

            if ($repeatAt > $now) {
                $response['question'] = 'Ты уже помог питомнику. Повторить задание можно через <b>'.$leftStr.'</b>.';
                $response['answer']   = array(1 => 'Понял.');
                break;
            }

            $response['question'] = 'Смотрю на индикаторы… инкубатор снова “капризничает”. Хочешь провести ещё одну проверку и помочь питомнику?';
            $response['answer']   = array(5 => 'Повторить задание.', 1 => 'Не сейчас.');
            break;
        }

        // Если калибровка завершена у техника (12/22/32) — даём финальную кнопку запуска (яйцо выдаём тут!)
        if (quest_step($questId, 12) || quest_step($questId, 22) || quest_step($questId, 32)) {

            $response['question'] = 'Вижу отметку техника: <b>калибровка пройдена</b>. Остался финальный щелчок — запуск. Готов?';
            $response['answer']   = array(50 => 'Запустить инкубатор.', 1 => 'Позже.');
            $response['nav']      = array(
                'route' => array(
                    array('type' => 'location', 'slug' => 'nursery')
                )
            );
            break;
        }

        // Если игрок уже выбрал стихию, но ещё не калибровал
        if (quest_step($questId, 10) || quest_step($questId, 20) || quest_step($questId, 30)) {
            $response['question'] = 'Стихию ты уже выбрал. Теперь нам нужен '.$moduleHint.' — техник соберёт его в одну штуку, а потом проведёт калибровку на месте.';
            $response['answer']   = array(1 => 'Иду к технику.');
            $response['nav']      = array(
                'route' => array(
                    array('type' => 'location', 'slug' => 'nursery'),
                    array('type' => 'npc', 'slug' => 'incubator_tech')
                )
            );
            break;
        }

        // Старт
        $response['actionQuest'] = 'Новое задание доступно: <b>Инкубатор трёх стихий</b>.';
        $response['question']    = 'Тренер, как раз вовремя. В питомнике три редких яйца ждут запуска, но инкубатор “упрямится” — автоматика сбилась. Поможешь запустить всё безопасно?';
        $response['answer']      = array(2 => 'Что случилось?', 99 => 'Не сейчас.');
        break;

    // Повтор: сбрасываем квест (end=0, step=0) и возвращаемся к старту
    case 5:

        if ($userId > 0) {
            // пытаемся с repeat_at, если колонки нет — делаем без неё
            $ok = $mysqli->query("UPDATE `user_quests` SET `step`=0, `end`=0, `repeat_at`=0 WHERE `user_id`=".$userId." AND `quest_id`=".$questId." LIMIT 1");
            if (!$ok) {
                $mysqli->query("UPDATE `user_quests` SET `step`=0, `end`=0 WHERE `user_id`=".$userId." AND `quest_id`=".$questId." LIMIT 1");
            }
        }

        if (function_exists('quest_update')) quest_update($questId, 0);

        $response['actionQuest'] = 'Задание <b>Инкубатор трёх стихий</b> снова доступно.';
        $response['question']    = 'Хорошо. Схема такая: инкубатор работает в трёх режимах — огонь, вода и трава. Мы выбираем режим, техник калибрует блок, а ты запускаешь финальный цикл и получаешь яйцо. Поможешь провести цикл до конца? <span style="opacity:.9;">Важно: повторные попытки калибровки у техника платные — он берёт детали калибровочного модуля.</span>';
        $response['answer']      = array(2 => 'Что случилось?', 99 => 'Не сейчас.');
        break;

    case 2:
        $response['question'] = 'Выбери режим запуска: <b>Огонь</b>, <b>Вода</b> или <b>Трава</b>. Я зафиксирую выбор, а яйцо выдадим только после <b>успешной калибровки</b> — так честнее и безопаснее.';
        $response['answer']   = array(10 => 'Огонь', 20 => 'Вода', 30 => 'Трава', 1 => 'Мне нужно подумать.');
        break;

    case 10:
    case 20:
    case 30:

        $branch = (int)$npcStep;

        // фиксируем выбор стихии (без выдачи яйца!)
        if (function_exists('quest_update')) quest_update($questId, $branch);

        if (function_exists('update_zap')) {
            $t = ($branch == 10) ? 'Выбрана стихия: Огонь.' : (($branch == 20) ? 'Выбрана стихия: Вода.' : 'Выбрана стихия: Трава.');
            update_zap($questId, $branch, $t.' Яйцо будет выдано после калибровки у техника. Нужен '.$moduleHint.'.');
        }

        $response['actionQuest'] = 'Вы приняли задание <b>Инкубатор трёх стихий</b>.';
        $response['question']    = 'Принято. Режим записан. <span style="opacity:.9;">Если на калибровке ошибёшься, техник возьмёт плату за повтор узла: для <b>Огня</b> — грива/пластина/перо, для <b>Воды</b> — кристалл/перо/пластина, для <b>Травы</b> — семя/кристалл/пластина.</span> Теперь к технику: он соберёт '.$moduleHint.' и “приглушит” капризы инкубатора. Возвращайся ко мне после его отметки.';
        $response['answer']      = array(1 => 'Иду.');
        $response['nav']         = array(
            'route' => array(
                array('type' => 'location', 'slug' => 'nursery'),
                array('type' => 'npc', 'slug' => 'incubator_tech')
            )
        );
        break;

    case 50:

        // проверяем калибровку
        $branch = 0;
        if (quest_step($questId, 12)) $branch = 10;
        if (quest_step($questId, 22)) $branch = 20;
        if (quest_step($questId, 32)) $branch = 30;

        if ($branch == 0) {
            $response['question'] = 'Пока не спеши: без калибровки запуск может “съесть” заряд и испортить цикл. Сначала к технику — пусть поставит отметку.';
            $response['answer']   = array(1 => 'Понял.');
            $response['nav']      = array(
                'route' => array(
                    array('type' => 'location', 'slug' => 'nursery'),
                    array('type' => 'npc', 'slug' => 'incubator_tech')
                )
            );
            break;
        }

        // 1) Выдаём яйцо ТОЛЬКО в финале
        $eggBase = 43;
        if ($branch == 10) $eggBase = $poolFire[mt_rand(0, count($poolFire)-1)];
        if ($branch == 20) $eggBase = $poolWater[mt_rand(0, count($poolWater)-1)];
        if ($branch == 30) $eggBase = $poolGrass[mt_rand(0, count($poolGrass)-1)];

        // понятное имя награды (яйцо — благодарность, а не "калиброванное яйцо")
        $eggName = q130_poke_name($eggBase);

        $modeHuman = 'режим';
        if ($branch == 10) $modeHuman = 'огненный режим';
        if ($branch == 20) $modeHuman = 'водный режим';
        if ($branch == 30) $modeHuman = 'травяной режим';

        $gens = q130_make_gens($branch);

        if (function_exists('plusEgg')) {
            plusEgg($gens, false, 0, false, false, $eggBase, false, false, false);
        }

        // 2) Награда (оставляем как было)
        if (function_exists('itemAdd')) {
            itemAdd(1, 6000);
            itemAdd(2, 15);
            itemAdd(3, 7);
            itemAdd(10, 4);
            itemAdd(15, 3);
        }


        // 2.1) Готовим красивый вывод награды (чтобы игрок видел, что получил)
        $rewardHtml = '<div style="margin-top:10px;"><b>Награда:</b><br>'
                    . '• <b>Капсула с яйцом</b>: <b>'.$eggName.'</b> <span style="opacity:.85;">(гены 16–22)</span><br>'
                    . '• '.q130_item_line(1, 6000).'<br>'
                    . '• '.q130_item_line(2, 15).'<br>'
                    . '• '.q130_item_line(3, 7).'<br>'
                    . '• '.q130_item_line(10, 4).'<br>'
                    . '• '.q130_item_line(15, 3).'</div>';
        // 3) Завершаем квест
        if (function_exists('quest_update')) quest_update($questId, 99);

        // 4) Фиксируем повтор через 3 дня
        $next = $now + 3*86400;

        if ($userId > 0) {
            if ($uq) {
                $ok = $mysqli->query("UPDATE `user_quests` SET `step`=99, `end`=1, `repeat_at`=".$next." WHERE `user_id`=".$userId." AND `quest_id`=".$questId." LIMIT 1");
                if (!$ok) {
                    $mysqli->query("UPDATE `user_quests` SET `step`=99, `end`=1 WHERE `user_id`=".$userId." AND `quest_id`=".$questId." LIMIT 1");
                }
            } else {
                $ok = $mysqli->query("INSERT INTO `user_quests` (`user_id`,`quest_id`,`step`,`end`,`repeat_at`) VALUES (".$userId.",".$questId.",99,1,".$next.")");
                if (!$ok) {
                    $mysqli->query("INSERT INTO `user_quests` (`user_id`,`quest_id`,`step`,`end`) VALUES (".$userId.",".$questId.",99,1)");
                }
            }
        }

        if (function_exists('update_zap')) {
            update_zap($questId, 99, 'Инкубатор запущен. Яйцо выдано. Награда получена. Повтор будет доступен через 3 дня.');
        }

        $response['actionQuest'] = 'Задание <b>Инкубатор трёх стихий</b> выполнено.';
        $response['question']    = 'Слушай… тишина. Это хороший знак.<br>'
                              . 'Калибровка инкубатора завершена: <b>'.$modeHuman.'</b> держится стабильно.<br><br>'
                              . 'Капсула с яйцом — это наша благодарность за твою помощь и заботу о питомнике. Забирай награду.'
                              . $rewardHtml
                              . '<br><span style="opacity:.85;">Повтор квеста будет доступен через 3 дня.</span>';
        $response['answer']      = array(1 => 'Спасибо!');
        break;

    case 99:
        $response['question'] = 'Понял. Если передумаешь — подходи. Инкубатор любит терпеливых тренеров.';
        $response['answer']   = array(1 => 'Понял.');
        break;
}
?>
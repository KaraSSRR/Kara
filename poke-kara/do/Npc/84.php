<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 84 — Техник инкубатора (питомник)
 * Quest 130: "Инкубатор трёх стихий"
 *
 * v9 (доработка):
 *  - Чётко предупреждаем: за ПОВТОР этапа берём оплату (разные детали модуля по этапам + стихиям)
 *  - Разные правильные ответы/тексты для разных стихий (Огонь/Вода/Трава)
 *  - Анти-эксплойт: если нет оплаты — ставим "долг" (16/17/18; 26/27/28; 36/37/38) и не даём бесконечно щёлкать
 *  - Модуль (5026) забирается при старте калибровки (как и раньше)
 *
 * Совместимо с PHP 5.6
 */

$questId = 130;
$npcId   = 84;

if (!isset($npcStep)) $npcStep = 1;

$npc = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId." LIMIT 1")->fetch_assoc();

$response = array();
$response['name']  = !empty($npc['name']) ? $npc['name'] : 'NPC';
$response['image'] = !empty($npc['image']) ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

// user_quests (с repeat_at, если колонка есть)
$qUq = null;
$curStep = 0; $isEnd = 0;
try {
    $qUq = $mysqli->query("SELECT `step`, `end`, `repeat_at` FROM `user_quests` WHERE `user_id` = ".$userId." AND `quest_id` = ".$questId." LIMIT 1");
    $uqr = $qUq ? $qUq->fetch_assoc() : null;
    if (!empty($uqr['step'])) $curStep = (int)$uqr['step'];
    if (!empty($uqr['end']))  $isEnd  = (int)$uqr['end'];
} catch (Exception $e) {
    $qUq2 = $mysqli->query("SELECT `step`, `end` FROM `user_quests` WHERE `user_id` = ".$userId." AND `quest_id` = ".$questId." LIMIT 1");
    $uqr2 = $qUq2 ? $qUq2->fetch_assoc() : null;
    if (!empty($uqr2['step'])) $curStep = (int)$uqr2['step'];
    if (!empty($uqr2['end']))  $isEnd  = (int)$uqr2['end'];
}

// определяем ветку (Огонь/Вода/Трава), включая "долги" 16-18 / 26-28 / 36-38
$base = 0;
if (in_array($curStep, array(10,11,12,13,14,15,16,17,18))) $base = 10;
if (in_array($curStep, array(20,21,22,23,24,25,26,27,28))) $base = 20;
if (in_array($curStep, array(30,31,32,33,34,35,36,37,38))) $base = 30;

$label = '';
if ($base == 10) $label = 'Огонь';
if ($base == 20) $label = 'Вода';
if ($base == 30) $label = 'Трава';

// шаги
$stepNeed  = $base + 1;  // 11/21/31 — ждём модуль
$stepDone  = $base + 2;  // 12/22/32 — калибровка завершена
$stepMini1 = $base + 3;  // 13/23/33 — этап 1
$stepMini2 = $base + 4;  // 14/24/34 — этап 2
$stepMini3 = $base + 5;  // 15/25/35 — этап 3

// "долги" (чтобы нельзя было ретраить без оплаты)
$stepDebt1 = $base + 6;  // 16/26/36 — долг за этап 1
$stepDebt2 = $base + 7;  // 17/27/37 — долг за этап 2
$stepDebt3 = $base + 8;  // 18/28/38 — долг за этап 3

// справочник предметов деталей
$parts = array(
    5021 => array('Перо Муркроу',        '/img/world/items/little/5021.png'),
    5022 => array('Пластина Арона',      '/img/world/items/little/5022.png'),
    5023 => array('Кристалл Карбинка',   '/img/world/items/little/5023.png'),
    5024 => array('Тлеющая грива Пониты','/img/world/items/little/5024.png'),
    5025 => array('Солнечное семя Санкерна','/img/world/items/little/5025.png'),
    5026 => array('Калибровочный модуль','/img/world/items/little/5026.png'),
);

function part_html($parts, $id, $cnt) {
    $cnt = (int)$cnt;
    $id  = (int)$id;
    $name = !empty($parts[$id][0]) ? $parts[$id][0] : ('Предмет #'.$id);
    $img  = !empty($parts[$id][1]) ? $parts[$id][1] : '/img/world/items/little/0.png';
    return '<img src="'.$img.'" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> <b>'.$name.'</b> x'.$cnt;
}

// цены повтора (этап => предмет/кол-во) зависят от стихии
$retry = array(
    10 => array(1 => array(5024,1), 2 => array(5022,1), 3 => array(5021,1)), // Огонь
    20 => array(1 => array(5023,1), 2 => array(5021,1), 3 => array(5022,1)), // Вода
    30 => array(1 => array(5025,1), 2 => array(5023,1), 3 => array(5022,1)), // Трава
);

function get_retry_cost($retry, $base, $stage) {
    $base = (int)$base; $stage = (int)$stage;
    if (!empty($retry[$base]) && !empty($retry[$base][$stage])) return $retry[$base][$stage];
    return array(5021,1);
}

// рецепт модуля (по кнопке)
$needHtml =
    '<div style="margin-bottom:6px;"><img src="/img/world/items/little/5026.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> <b>Калибровочный модуль</b></div>'
  . '<div><img src="/img/world/items/little/5021.png" class="item" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Перо Муркроу <b>x4</b></div>'
  . '<div><img src="/img/world/items/little/5022.png" class="item" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Пластина Арона <b>x2</b></div>'
  . '<div><img src="/img/world/items/little/5023.png" class="item" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Кристалл Карбинка <b>x3</b></div>'
  . '<div><img src="/img/world/items/little/5024.png" class="item" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Тлеющая грива Пониты <b>x1</b></div>'
  . '<div><img src="/img/world/items/little/5025.png" class="item" style="width:28px;height:28px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Солнечное семя Санкерна <b>x5</b></div>';

function nav_to_keeper() {
    return array(
        'route' => array(
            array('type' => 'location', 'slug' => 'nursery'),
            array('type' => 'npc', 'slug' => 'nursery_keeper')
        )
    );
}

switch ($npcStep) {

    default:

        if ($isEnd == 1) {
            $response['question'] = 'У меня всё записано: цикл закрыт, модуль учтён, риск снят. Дальше — к смотрителю.';
            $response['answer']   = array(1 => 'Понял.');
            $response['nav']      = nav_to_keeper();
            break;
        }

        if ($base == 0) {
            $response['question'] = 'Без выбранного режима я не полезу в панель. Сначала договорись со смотрителем питомника.';
            $response['answer']   = array(1 => 'Понял.');
            $response['nav']      = nav_to_keeper();
            break;
        }

        // уже завершено
        if (quest_step($questId, $stepDone)) {
            $response['question'] = 'Калибровка по режиму <b>'.$label.'</b> завершена. Я поставил отметку. Иди к смотрителю — он запустит цикл и выдаст капсулу с яйцом.';
            $response['answer']   = array(1 => 'Иду.');
            $response['nav']      = nav_to_keeper();
            break;
        }

        // долги (после ошибки)
        if (quest_step($questId, $stepDebt1) || quest_step($questId, $stepDebt2) || quest_step($questId, $stepDebt3)) {

            $stage = 1;
            if (quest_step($questId, $stepDebt2)) $stage = 2;
            if (quest_step($questId, $stepDebt3)) $stage = 3;

            $c = get_retry_cost($retry, $base, $stage);
            $itemId = (int)$c[0]; $cnt = (int)$c[1];

            $response['question'] = '<b>Сбой на узле '.$stage.'/3</b><br>'
                                  . 'Я остановил калибровку, чтобы инкубатор не “переехал” режим <b>'.$label.'</b>.<br>'
                                  . 'Чтобы вернуть тебя к попытке, нужна компенсация расходника: '.part_html($parts, $itemId, $cnt).'.';
            $response['answer'] = array(
                ($stage == 1 ? 201 : ($stage == 2 ? 211 : 221)) => 'Оплатить и продолжить.',
                4 => 'Показать рецепт модуля.',
                1 => 'Понял.'
            );
            break;
        }

        // только выбрал стихию
        if (quest_step($questId, 10) || quest_step($questId, 20) || quest_step($questId, 30)) {

            // текст предупреждения по ветке
            $c1 = get_retry_cost($retry, $base, 1);
            $c2 = get_retry_cost($retry, $base, 2);
            $c3 = get_retry_cost($retry, $base, 3);

            $warn = '<div style="margin-top:6px;"><b>Важно</b><br>'
                  . 'Калибровка — три узла. Ошибся на узле — повтор платный.<br>'
                  . 'Для режима <b>'.$label.'</b>:<br>'
                  . '• узел 1: '.part_html($parts, (int)$c1[0], (int)$c1[1]).'<br>'
                  . '• узел 2: '.part_html($parts, (int)$c2[0], (int)$c2[1]).'<br>'
                  . '• узел 3: '.part_html($parts, (int)$c3[0], (int)$c3[1]).'</div>';

            $response['question'] = 'Так… режим выбран: <b>'.$label.'</b>.<br>'
                                  . 'Для калибровки мне нужен '.part_html($parts, 5026, 1).'.<br>'
                                  . '<span style="opacity:.9;">Если надо — покажу рецепт.</span>'
                                  . $warn;

            $response['answer']   = array(3 => 'Понял, соберу и скрафчу.', 4 => 'Показать рецепт.');
            // фиксируем стадию ожидания модуля
            quest_update($questId, $stepNeed);
            if (function_exists('update_zap')) update_zap($questId, $stepNeed, 'Техник выдал задание: собрать части и скрафтить Калибровочный модуль.');
            break;
        }

        // ждём модуль (11/21/31)
        if (quest_step($questId, $stepNeed)) {

            $has = item_isset(5026, 1) ? 1 : 0;

            $response['question'] = 'Перед тем как лезть в параметры — модуль на стол. Есть <b>Калибровочный модуль</b>?';

            if ($has) {
                $response['answer'] = array(
                    1 => 'Пока нет.',
                    2 => 'Да, модуль готов. Начинай калибровку.',
                    4 => 'Показать рецепт.'
                );
            } else {
                $response['answer'] = array(
                    1 => 'Пока нет.',
                    4 => 'Показать рецепт.'
                );
            }
            break;
        }

        // этап 1
        if (quest_step($questId, $stepMini1)) {

            $c = get_retry_cost($retry, $base, 1);
            $fee = part_html($parts, (int)$c[0], (int)$c[1]);

            if ($base == 10) {
                $q = '<b>Калибровка Огонь 1/3</b><br>Термоконтур “дышит” и срывает розжиг. Выставь <b>частоту стабилизации</b>:';
                $a = array(102 => '13.7 кГц', 103 => '9.1 кГц', 101 => '18.4 кГц');
            } elseif ($base == 20) {
                $q = '<b>Калибровка Вода 1/3</b><br>Контур охлаждения гуляет волной. Выставь <b>частоту стабилизации</b>:';
                $a = array(101 => '13.7 кГц', 102 => '9.1 кГц', 103 => '18.4 кГц');
            } else {
                $q = '<b>Калибровка Трава 1/3</b><br>Биоконтур “шуршит” и теряет ритм. Выставь <b>частоту стабилизации</b>:';
                $a = array(102 => '13.7 кГц', 101 => '9.1 кГц', 103 => '18.4 кГц');
            }

            $response['question'] = $q.'<br><span style="opacity:.85;">Цена ошибки (повтор узла): '.$fee.'</span>';
            $response['answer']   = $a;
            break;
        }

        // этап 2
        if (quest_step($questId, $stepMini2)) {

            $c = get_retry_cost($retry, $base, 2);
            $fee = part_html($parts, (int)$c[0], (int)$c[1]);

            if ($base == 10) {
                $q = '<b>Калибровка Огонь 2/3</b><br>Искра бьёт не в фазе. Выбери <b>полярность</b>:';
                $a = array(112 => 'Инвертировать фазу', 113 => 'Оставить как есть', 111 => 'Поменять местами контакты');
            } elseif ($base == 20) {
                $q = '<b>Калибровка Вода 2/3</b><br>Насос в противофазе, шумит. Выбери <b>полярность</b>:';
                $a = array(111 => 'Инвертировать фазу', 112 => 'Оставить как есть', 113 => 'Поменять местами контакты');
            } else {
                $q = '<b>Калибровка Трава 2/3</b><br>Контур питания “перекашивает”. Выбери <b>полярность</b>:';
                $a = array(112 => 'Инвертировать фазу', 111 => 'Оставить как есть', 113 => 'Поменять местами контакты');
            }

            $response['question'] = $q.'<br><span style="opacity:.85;">Цена ошибки (повтор узла): '.$fee.'</span>';
            $response['answer']   = $a;
            break;
        }

        // этап 3
        if (quest_step($questId, $stepMini3)) {

            $c = get_retry_cost($retry, $base, 3);
            $fee = part_html($parts, (int)$c[0], (int)$c[1]);

            if ($base == 10) {
                $q = '<b>Калибровка Огонь 3/3</b><br>Последний узел — перегрев. Укажи <b>температурный порог</b>:';
                $a = array(122 => '38°C', 123 => '29°C', 121 => '45°C');
            } elseif ($base == 20) {
                $q = '<b>Калибровка Вода 3/3</b><br>Последний узел — холодный старт. Укажи <b>температурный порог</b>:';
                $a = array(121 => '38°C', 122 => '29°C', 123 => '45°C');
            } else {
                $q = '<b>Калибровка Трава 3/3</b><br>Последний узел — “живой” контур. Укажи <b>температурный порог</b>:';
                $a = array(122 => '38°C', 121 => '29°C', 123 => '45°C');
            }

            $response['question'] = $q.'<br><span style="opacity:.85;">Цена ошибки (повтор узла): '.$fee.'</span>';
            $response['answer']   = $a;
            break;
        }

        // иначе — отправляем к смотрителю
        $response['question'] = 'Я по уши в проводке и датчиках. Подходи по инструкции: сначала смотритель, потом модуль, потом калибровка.';
        $response['answer']   = array(1 => 'Понял.');
        $response['nav']      = nav_to_keeper();
        break;

    case 4:
        $response['question'] = $needHtml;
        $response['answer']   = array(1 => 'Понял.');
        break;

    case 3:
        $response['question'] = 'Без модуля я даже крышку панели не открою. Принесёшь <b>Калибровочный модуль</b> — начнём.';
        $response['answer']   = array(1 => 'Ок.', 4 => 'Показать рецепт.');
        break;

    // Старт калибровки: забираем модуль и ставим этап 1
    case 2:

        if (!quest_step($questId, $stepNeed)) {
            $response['question'] = 'Сначала договорись со смотрителем и выбери режим. Потом — модуль.';
            $response['answer']   = array(1 => 'Понял.');
            $response['nav']      = nav_to_keeper();
            break;
        }

        if (!item_isset(5026, 1)) {
            $response['question'] = 'Модуля нет. Без него я не имею права включать калибровку.';
            $response['answer']   = array(1 => 'Понял.', 4 => 'Показать рецепт.');
            break;
        }

        minus_item(5026, 1);
        quest_update($questId, $stepMini1);
        if (function_exists('update_zap')) update_zap($questId, $stepMini1, 'Начата калибровка инкубатора (мини-игра).');

        $response['question'] = 'Модуль забрал. Включаю панель… Держи ритм, и всё будет чисто.';
        $response['answer']   = array(1 => 'Поехали.');
        break;

    // ======= Оплата долга (после ошибки) =======
    case 201: // долг этап 1
    case 211: // долг этап 2
    case 221: // долг этап 3

        $stage = ($npcStep == 211) ? 2 : (($npcStep == 221) ? 3 : 1);
        $targetStep = ($stage == 1) ? $stepMini1 : (($stage == 2) ? $stepMini2 : $stepMini3);
        $targetDebt = ($stage == 1) ? $stepDebt1 : (($stage == 2) ? $stepDebt2 : $stepDebt3);

        if (!quest_step($questId, $targetDebt)) {
            $response['question'] = 'Сейчас нет долга по этому узлу.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        $c = get_retry_cost($retry, $base, $stage);
        $itemId = (int)$c[0]; $cnt = (int)$c[1];

        if (!item_isset($itemId, $cnt)) {
            $response['question'] = 'Не хватает расходника: '.part_html($parts, $itemId, $cnt).'.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        minus_item($itemId, $cnt);
        quest_update($questId, $targetStep);

        $response['question'] = 'Принято. Узел '.$stage.'/3 разблокирован. Возвращайся к панели.';
        $response['answer']   = array(1 => 'Ок.');
        break;

    // ======= Успехи =======
    case 101:
        quest_update($questId, $stepMini2);
        $response['question'] = 'Есть контакт. Для режима <b>'.$label.'</b> сигнал встал ровно. Переходим ко второму узлу.';
        $response['answer']   = array(1 => 'Продолжить.');
        break;

    case 111:
        quest_update($questId, $stepMini3);
        $response['question'] = 'Фаза поймана. Режим <b>'.$label.'</b> держится без дрожи. Остался последний узел.';
        $response['answer']   = array(1 => 'Продолжить.');
        break;

    case 121:
        quest_update($questId, $stepDone);
        if (function_exists('update_zap')) update_zap($questId, $stepDone, 'Калибровка завершена. Можно запускать инкубатор.');
        $response['question'] = 'Готово. Я поставил отметку по режиму <b>'.$label.'</b>. Дальше — к смотрителю: он запустит цикл и выдаст яйцо.';
        $response['answer']   = array(1 => 'Иду.');
        $response['nav']      = nav_to_keeper();
        break;

    // ======= Ошибки (оплата/долг) =======
    case 102:
    case 103:

        $c = get_retry_cost($retry, $base, 1);
        $itemId = (int)$c[0]; $cnt = (int)$c[1];

        if (!item_isset($itemId, $cnt)) {
            quest_update($questId, $stepDebt1);
            $response['question'] = 'Срыв на узле 1/3. Я остановил панель — иначе режим <b>'.$label.'</b> “поплывёт”.<br>'
                                  . 'Чтобы дать повтор, нужна компенсация: '.part_html($parts, $itemId, $cnt).'.';
            $response['answer'] = array(1 => 'Понял.');
            break;
        }

        minus_item($itemId, $cnt);
        quest_update($questId, $stepMini1);
        $response['question'] = 'Ошибка. Узел 1/3 перезапущен — я списал расходник: '.part_html($parts, $itemId, $cnt).'.';
        $response['answer']   = array(1 => 'Повторить.');
        break;

    case 112:
    case 113:

        $c = get_retry_cost($retry, $base, 2);
        $itemId = (int)$c[0]; $cnt = (int)$c[1];

        if (!item_isset($itemId, $cnt)) {
            quest_update($questId, $stepDebt2);
            $response['question'] = 'Срыв на узле 2/3. Режим <b>'.$label.'</b> держится на честном слове… и моих предохранителях.<br>'
                                  . 'Чтобы дать повтор, нужна компенсация: '.part_html($parts, $itemId, $cnt).'.';
            $response['answer'] = array(1 => 'Понял.');
            break;
        }

        minus_item($itemId, $cnt);
        quest_update($questId, $stepMini2);
        $response['question'] = 'Ошибка. Узел 2/3 перезапущен — списал расходник: '.part_html($parts, $itemId, $cnt).'.';
        $response['answer']   = array(1 => 'Повторить.');
        break;

    case 122:
    case 123:

        $c = get_retry_cost($retry, $base, 3);
        $itemId = (int)$c[0]; $cnt = (int)$c[1];

        if (!item_isset($itemId, $cnt)) {
            quest_update($questId, $stepDebt3);
            $response['question'] = 'Срыв на узле 3/3. Последний шаг — и самый капризный.<br>'
                                  . 'Для повтора нужна компенсация: '.part_html($parts, $itemId, $cnt).'.';
            $response['answer'] = array(1 => 'Понял.');
            break;
        }

        minus_item($itemId, $cnt);
        quest_update($questId, $stepMini3);
        $response['question'] = 'Ошибка. Узел 3/3 перезапущен — списал расходник: '.part_html($parts, $itemId, $cnt).'.';
        $response['answer']   = array(1 => 'Повторить.');
        break;
}
?>
<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
        require_once($patch_func);
    }
}

function info_quest_craft($id,$tip){
    global $mysqli;
    $quest = $mysqli->query("SELECT * FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."'")->fetch_assoc();
    return ($quest[$tip] ? $quest[$tip] : false);
}

// === ДОБАВЛЯЕМ ВОЗВРАТ ТЕКУЩЕЙ КАТЕГОРИИ ===
// === ДОБАВИЛ: читаем категорию, чтобы фронт мог вернуть её после крафта
$category = isset($_POST['category']) ? preg_replace('/[^a-z_0-9]/ui', '', $_POST['category']) : 'all';

$type  = clearInt($_POST["item"]);
$count = max(1, clearInt($_POST["count"]));

$response = [];

switch ($type) {

    /* ====================== HELD / BOOST ITEMS (из craft.ods) ====================== */

    // 127 — Магнит
    case 127:
        if (item_isset(5004, 5*$count) && item_isset(5001, 25*$count) && item_isset(5005, 1*$count)) {
            minus_item(5004, 5*$count); minus_item(5001, 25*$count); minus_item(5005, 1*$count);
            itemAdd(127, $count);
            $response['plus']  = '<img src="/img/world/items/little/127.png" class="item"> Магнит <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5004.png" class="item"> Искры <b>x'.(5*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(25*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5005.png" class="item"> Загадочные камушки <b>x'.(1*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 129 — Водный амулет
    case 129:
        if (item_isset(5006,10*$count) && item_isset(5007,8*$count) && item_isset(5009,8*$count)) {
            minus_item(5006,10*$count); minus_item(5007,8*$count); minus_item(5009,8*$count);
            itemAdd(129,$count);
            $response['plus']  = '<img src="/img/world/items/little/129.png" class="item"> Водный амулет <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5006.png" class="item"> Капелька воды <b>x'.(10*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5007.png" class="item"> Нитка <b>x'.(8*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5009.png" class="item"> Ткань тонкая <b>x'.(8*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 131 — Заострённый клюв
    case 131:
        if (item_isset(5011,7*$count) && item_isset(5001,12*$count)) {
            minus_item(5011,7*$count); minus_item(5001,12*$count);
            itemAdd(131,$count);
            $response['plus']  = '<img src="/img/world/items/little/131.png" class="item"> Заострённый клюв <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5011.png" class="item"> Клюв <b>x'.(7*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(12*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 133 — Уголь
    case 133:
        if (item_isset(5002,3*$count) && item_isset(5003,5*$count)) {
            minus_item(5002,3*$count); minus_item(5003,5*$count);
            itemAdd(133,$count);
            $response['plus']  = '<img src="/img/world/items/little/133.png" class="item"> Уголь <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5002.png" class="item"> Угольки <b>x'.(3*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5003.png" class="item"> Искорки <b>x'.(5*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 137 — Шелковый шарф
    case 137:
        if (item_isset(5008,10*$count) && item_isset(5009,15*$count)) {
            minus_item(5008,10*$count); minus_item(5009,15*$count);
            itemAdd(137,$count);
            $response['plus']  = '<img src="/img/world/items/little/137.png" class="item"> Шёлковый шарф <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5008.png" class="item"> Нитки <b>x'.(10*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5009.png" class="item"> Ткань тонкая <b>x'.(15*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 139 — Чудесное семя
    case 139:
        if (item_isset(5012,3*$count) && item_isset(5013,5*$count) && item_isset(5001,3*$count)) {
            minus_item(5012,3*$count); minus_item(5013,5*$count); minus_item(5001,3*$count);
            itemAdd(139,$count);
            $response['plus']  = '<img src="/img/world/items/little/139.png" class="item"> Чудесное семя <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5012.png" class="item"> Детки какнеи <b>x'.(3*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5013.png" class="item"> Листики <b>x'.(5*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(3*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 140 — Прочный камень
    case 140:
        /* Осколки камней в твоей базе уже использовались как:
           73-громовой, 74-водный, 75-лиственный, 76-огненный, 77-лунный, 78-солнечный, 79-сумрачный
           По таблице для Прочного камня нужен "Осколок лиственного камня x3".
        */
        if (item_isset(75,3*$count)) {
            minus_item(75,3*$count);
            itemAdd(140,$count);
            $response['plus']  = '<img src="/img/world/items/little/140.png" class="item"> Прочный камень <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/75.png" class="item"> Осколок лиственного камня <b>x'.(3*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 132 — Сухой лёд
    case 132:
        if (item_isset(5014,4*$count) && item_isset(5015,5*$count)) {
            minus_item(5014,4*$count); minus_item(5015,5*$count);
            itemAdd(132,$count);
            $response['plus']  = '<img src="/img/world/items/little/132.png" class="item"> Сухой лёд <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5014.png" class="item"> Ледяной осколок <b>x'.(4*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5015.png" class="item"> Горсть снега <b>x'.(5*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 143 — Чёрный пояс (вариант из S)
    case 143:
        if (item_isset(5009,10*$count) && item_isset(5016,4*$count) && item_isset(5001,7*$count)) {
            minus_item(5009,10*$count); minus_item(5016,4*$count); minus_item(5001,7*$count);
            itemAdd(143,$count);
            $response['plus']  = '<img src="/img/world/items/little/143.png" class="item"> Чёрный пояс <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5009.png" class="item"> Ткань тонкая <b>x'.(10*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5016.png" class="item"> Краситель чёрный <b>x'.(4*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(7*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 141 — Серебряная пыль
    case 141:
        if (item_isset(5018,1*$count) && item_isset(5017,1*$count) && item_isset(5001,5*$count)) {
            minus_item(5018,1*$count); minus_item(5017,1*$count); minus_item(5001,5*$count);
            itemAdd(141,$count);
            $response['plus']  = '<img src="/img/world/items/little/141.png" class="item"> Серебряная пыль <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5018.png" class="item"> Жало Драпиона <b>x'.(1*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5017.png" class="item"> Яд Эканса <b>x'.(1*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(5*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 145 — Остатки
    case 145:
        if (item_isset(5010,5*$count) && item_isset(5009,4*$count)) {
            minus_item(5010,5*$count); minus_item(5009,4*$count);
            itemAdd(145,$count);
            $response['plus']  = '<img src="/img/world/items/little/145.png" class="item"> Остатки <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5010.png" class="item"> Яблоко <b>x'.(5*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5009.png" class="item"> Ткань тонкая <b>x'.(4*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 128 — Мешочек с землёй
    case 128:
        if (item_isset(5019,3*$count) && item_isset(5001,2*$count)) {
            minus_item(5019,3*$count); minus_item(5001,2*$count);
            itemAdd(128,$count);
            $response['plus']  = '<img src="/img/world/items/little/128.png" class="item"> Мешочек с землёй <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5019.png" class="item"> Плодородная земля <b>x'.(3*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(2*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 100 — Броня
    case 100:
        if (item_isset(5020,10*$count) && item_isset(5001,20*$count)) {
            minus_item(5020,10*$count); minus_item(5001,20*$count);
            itemAdd(100,$count);
            $response['plus']  = '<img src="/img/world/items/little/100.png" class="item"> Броня <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5020.png" class="item"> Рога Агрона <b>x'.(10*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(20*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    // 134 — Ядовитый шип
    case 134:
        if (item_isset(5017,1*$count) && item_isset(5001,4*$count)) {
            minus_item(5017,1*$count); minus_item(5001,4*$count);
            itemAdd(134,$count);
            $response['plus']  = '<img src="/img/world/items/little/134.png" class="item"> Ядовитый шип <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5017.png" class="item"> Яд Эканса <b>x'.(1*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5001.png" class="item"> Эссенция (к) <b>x'.(4*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    /* ====================== ЭВОЛЮЦИОННЫЕ КАМНИ (как и раньше) ====================== */
    case 80: case 81: case 82: case 83: case 84: case 85: case 86:
        $shardMap = [80=>73, 81=>74, 82=>75, 83=>76, 84=>77, 85=>78, 86=>79];
        $shard = $shardMap[$type]; $c1 = 3*$count;
        if(item_isset($shard,$c1)){
            minus_item($shard,$c1); itemAdd($type,$count);
            $names = [80=>'Громовой камень',81=>'Водный камень',82=>'Лиственный камень',83=>'Огненный камень',84=>'Лунный камень',85=>'Солнечный камень',86=>'Сумрачный камень'];
            $response['plus']  = '<img src="/img/world/items/little/'.$type.'.png" class="item"> '.$names[$type].' <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/'.$shard.'.png" class="item"> Осколок <b>x'.$c1.'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    /* ====================== ЗЕЛЬЯ, МОДИФИКАТОРЫ и т.п. — оставил как было ====================== */
    // … (оставь/перенеси твои старые блоки, если они ещё нужны)
    // -----------------------------------------------------------------

    // 5026 — Калибровочный модуль (квестовый крафт)
    case 5026:
        if (item_isset(5021, 4*$count) && item_isset(5022, 2*$count) && item_isset(5023, 3*$count) && item_isset(5024, 1*$count) && item_isset(5025, 5*$count)) {
            minus_item(5021, 4*$count); minus_item(5022, 2*$count); minus_item(5023, 3*$count); minus_item(5024, 1*$count); minus_item(5025, 5*$count);
            itemAdd(5026, $count);
            $response['plus']  = '<img src="/img/world/items/little/5026.png" class="item"> Калибровочный модуль <b>x'.$count.'</b>';
            $response['minus'] = '<img src="/img/world/items/little/5021.png" class="item"> Перо Муркроу <b>x'.(4*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5022.png" class="item"> Пластина Арона <b>x'.(2*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5023.png" class="item"> Кристалл Карбинка <b>x'.(3*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5024.png" class="item"> Тлеющая грива Пониты <b>x'.(1*$count).'</b><br>'.
                                 '<img src="/img/world/items/little/5025.png" class="item"> Солнечное семя Санкерна <b>x'.(5*$count).'</b>';
            $response['html']='Вы удачно скрафтили предмет.'; $response['error']='success';
        } else { $response['html']='Условия крафта не соблюдены.'; $response['error']='error'; }
    break;

    default:
$response['html'] = 'Неизвестный рецепт.'; $response['error'] = 'error';
    break;
}

// чтобы фронт понимал, какую категорию перерисовать
$response['category'] = $category;
echo json_encode($response);

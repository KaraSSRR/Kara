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
$type = $_POST["type"];
$count = clearInt($_POST['count']);
$itemID = clearInt($_POST['itemID']);
$pokID = clearInt($_POST['pokID']);
$checkAction = $mysqli->query("SELECT `dress`,`drop`,`type`,`info` FROM `base_items` WHERE `id` = '".$itemID."'")->fetch_assoc();
$userStatus = $mysqli->query("SELECT `status`,`location` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
$npcLoc = $mysqli->query("SELECT * FROM `base_npc` WHERE `loc_id` = '".$userStatus['location']."'");
if($userStatus['status'] != 'free'){
    $response['text'] = 'Вы заняты!';
	$response['error'] = 1;
	die(json_encode($response));
}


/**
 * --- TERA: предметы и маппинг ---
 * IDs зарезервированы высокими значениями, чтобы не конфликтовать с существующими предметами.
 *
 * Осколки: 20001..20018
 * Камни:   20101..20118
 * Ядро:    20201
 */
define('TERA_SHARD_BASE', 20001);
define('TERA_STONE_BASE', 20101);
define('TERA_TYPES_COUNT', 18);
define('TERA_REWRITE_CORE_ID', 20201);

function tera_types_list(){
    return ['bug','dark','dragon','electric','fairy','fighting','fire','fly','ghost','grass','ground','ice','normal','poison','psychic','rock','steel','water'];
}

function tera_type_from_item($itemId){
    $types = tera_types_list();
    if($itemId >= TERA_SHARD_BASE && $itemId < (TERA_SHARD_BASE + TERA_TYPES_COUNT)){
        return $types[$itemId - TERA_SHARD_BASE] ?? '';
    }
    if($itemId >= TERA_STONE_BASE && $itemId < (TERA_STONE_BASE + TERA_TYPES_COUNT)){
        return $types[$itemId - TERA_STONE_BASE] ?? '';
    }
    return '';
}

function tera_shard_ids(){
    $ids = [];
    for($i = 0; $i < TERA_TYPES_COUNT; $i++){
        $ids[] = TERA_SHARD_BASE + $i;
    }
    return $ids;
}

function tera_user_items_sum($mysqli, $userId, $itemIds){
    $ids = array_map('intval', (array)$itemIds);
    if(empty($ids)){
        return [0, []];
    }

    $sql = "SELECT item_id, SUM(count) AS cnt FROM items_users WHERE (user=".$userId." OR user_id=".$userId.") AND item_id IN (".implode(',', $ids).") GROUP BY item_id";
    $res = $mysqli->query($sql);

    $map = [];
    if($res){
        while($row = $res->fetch_assoc()){
            $map[(int)$row['item_id']] = (int)$row['cnt'];
        }
    }

    $total = 0;
    foreach($ids as $id){
        $total += ($map[$id] ?? 0);
    }

    return [$total, $map];
}

/**
 * Списывает "любые" тера-осколки, пока не наберётся нужное количество.
 * Возвращает false, если осколков не хватило (ничего не обещаем про частичные списания,
 * поэтому сначала делаем проверку по сумме).
 */
function tera_deduct_any_shards($mysqli, $userId, $need){
    $shards = tera_shard_ids();
    list($total, $map) = tera_user_items_sum($mysqli, $userId, $shards);

    if($total < $need){
        return false;
    }

    $left = (int)$need;
    foreach($shards as $sid){
        $have = (int)($map[$sid] ?? 0);
        if($have <= 0) continue;

        $take = ($have >= $left ? $left : $have);
        if($take > 0){
            minus_item($sid, $take);
            $left -= $take;
            if($left <= 0) break;
        }
    }

    return ($left <= 0);
}

switch ($type) {
    case 'card':
        $my = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$itemID)->fetch_assoc();
        $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$my['item_id'])->fetch_assoc();
        $p = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = ".$i['info'])->fetch_assoc();
        if(item_isset($my['item_id'],30)){
            $response['text'] = 'Карточки успешно отправлены в коллекцию!';
                $response['error'] = 0;
                $response['minus'] = '<img src="/img/world/items/little/'.$i['id'].'.png" class="item"> '.$i['name'].' <b>x30</b>';
                $response['plus'] = '<img src="/img/pokemons/animation/'.$p['id'].'.png"> #'.numbPok($p['id']).' '.$p['name_rus'].'<br>';
                newPokemon($i['info'],$_SESSION['id'],1,32,0,'true',1,false,true,false,false,true);
                minus_item($my['item_id'],30);
                $mysqli->query("INSERT INTO `user_card_collection` (`user`,`card`,`collection`) VALUES ('".$_SESSION['id']."','".$my['item_id']."',1) ");
        }else{
            $response['text'] = 'У вас недостаточно карточек!';
			$response['error'] = 1;
        }

    break;
		case 'drop':
    // $p = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `startGame` = 1");
    // while($pok = $p->fetch_assoc()){
    //   $v = $pok['ev'] + $pok['vitamines'];
    //   Work::$sql->query('UPDATE `user_pokemons` SET `ev` = "'.$v.'" WHERE `id` = '.$pok['id']);
    // }
      //  $p = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `startGame` = 1");
      //  while($pok = $p->fetch_assoc()){
      //    $ev = $pok['lvl'] * 3;
      //   $ev1 = $ev - 15;
      //    if($ev1 <= 0) {
      //      $ev1 = 0;
      //    }else{
      //      $ev1 = $ev - 15;
      //    }
      //    Work::$sql->query('UPDATE `user_pokemons` SET `evcounts` = "0,0,0,0,0,0", `ev` = "'.$ev1.'" WHERE `id` = '.$pok['id']);
      //  }
      $my = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$itemID)->fetch_assoc();
      $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$my['item_id'])->fetch_assoc();

      $loc = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id'])->fetch_assoc();
			if(item_isset($my['item_id'],$count) && $i['drop'] != 'false'){
				minus_item_id($itemID,$count);
				$response['error'] = 0;
				$response['text'] = 'Предмет успешно выброшен!';
        $response['minus'] = '<img src="/img/world/items/little/'.$i['id'].'.png" class="item"> '.$i['name'].' <b>x'.$count.'</b>';
        if($loc['location'] == 77 && ($my['item_id'] == 131 or $my['item_id'] == 127 or $my['item_id'] == 2 or $my['item_id'] == 10)){
            if($my['item_id'] == 131){
                $b = 7000;
                itemAdd(1,7000);
            }elseif($my['item_id'] == 127){
                $b = 7000;
                itemAdd(1,7000);
            }elseif($my['item_id'] == 2){
                $b = 100*$count;
                itemAdd(1,$b);
            }elseif($my['item_id'] == 10){
                $b = 50*$count;
                itemAdd(1,$b);
            }
            $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x'.number_format($b,0,'.','.').'</b>';
if(check_mission(28)){ add_mission(28,$count);}
if(check_mission_ivent(37)){ add_mission_ivent(37,$count);}
        }
			}else{
        $response['text'] = 'Недостаточно предметов.';
				$response['error'] = 1;
			}
		break;
case 'plane_ticket':
    // Получаем информацию о предмете игрока по id строки из items_users
    $my = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = " . intval($itemID) . " AND `count` > 0 AND `user` = " . intval($_SESSION['id']))->fetch_assoc();
    if (!$my) {
        $response['error'] = 1;
        $response['text'] = 'Билет не найден в вашем инвентаре!';
        break;
    }

    // Получаем информацию о самом предмете
    $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = " . intval($my['item_id']))->fetch_assoc();
    if (!$i) {
        $response['error'] = 1;
        $response['text'] = 'Информация о билете не найдена!';
        break;
    }


    // Массив билетов: item_id => [название, id локации]
    $plane_tickets = [
        600 => [ 'name' => 'Авиабилет в Канто', 'location_id' => 3 ],  // 3 — id Канто
        601 => [ 'name' => 'Авиабилет в Джото', 'location_id' => 4 ],  // 4 — id Джото
        // Добавляйте новые билеты здесь при необходимости
    ];

    if (!isset($plane_tickets[$my['item_id']])) {
        $response['error'] = 1;
        $response['text'] = 'Этот билет не поддерживается!';
        break;
    }

    $ticket = $plane_tickets[$my['item_id']];
    $target_location = $ticket['location_id'];

    // Проверка на достаточное количество билетов
    if ($my['count'] < $count || $count < 1) {
        $response['error'] = 1;
        $response['text'] = 'Недостаточно билетов!';
        break;
    }

    // Удаляем билет (одноразовый, либо списываем несколько)
    minus_item_id($itemID, $count);

    // Сброс команды покемонов (если используется)
    if (function_exists('rb_cleanup_team_on_location_change')) {
        rb_cleanup_team_on_location_change($_SESSION['id'], $target_location);
    }

    // Телепортируем пользователя
    $mysqli->query("UPDATE `users` SET `location` = '" . intval($target_location) . "' WHERE `id` = '" . intval($_SESSION['id']) . "'");

    // Ответ для клиента
    $response['error'] = 0;
    $response['text'] = 'Вы использовали ' . htmlspecialchars($ticket['name']) . ' и были мгновенно перемещены!';
    $response['teleported'] = true;
    $response['new_location'] = $target_location;
    $response['minus'] = '<img src="/img/world/items/little/' . $i['id'] . '.png" class="item"> ' . htmlspecialchars($i['name']) . ' <b>x' . $count . '</b>';
    break;
    case 'dropEgg':
			$egg = $mysqli->query("SELECT * FROM `user_egg` WHERE `id` = '".$itemID."' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
			if($egg){
        $response['minus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо <b>x1</b>';
				$response['error'] = 0;
				$response['text'] = 'Вы выкинули яйцо покемона.';
				Work::$sql->query("DELETE FROM `user_egg` WHERE `id` = '".$itemID."' AND `user` = '".$_SESSION['id']."'");
			}
		break;
    case 'incubEgg':
			$egg = $mysqli->query("SELECT * FROM `user_egg` WHERE `id` = '".$itemID."' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
			if($egg){
        if(item_isset(267,1)) {
          minus_item(267,1);
          $reborn = floor(($egg['reborn'] - time())/2);
          $newReborn = time() + $reborn;
          $response['minus'] = '<img src="/img/world/items/little/267.png" class="item"> Портативный инкубатор <b>x1</b>';
  				$response['error'] = 0;
  				$response['text'] = 'Срок вылупления яйца уменьшен в два раза.';
  				Work::$sql->query('UPDATE `user_egg` SET `reborn` = '.$newReborn.' WHERE `id` = '.$egg['id']);
if(check_mission_ivent(40)){ add_mission_ivent(40);}
	if(check_mission(23)){ add_mission(23);}
	update_achiv(23,1);
        }else{
          $response['error'] = 1;
  				$response['text'] = 'У вас нет инкубатора.';
        }
			}

// $response['error'] = 1;
//   				$response['text'] = 'Использование инкубатора сейчас недоступно';
		break;
		case 'buyDonat':
			$thing = $mysqli->query("SELECT * FROM aquarits WHERE id = ".$itemID)->fetch_assoc();
      $thing1 = $mysqli->query("SELECT name FROM base_items WHERE id = ".$thing['item'])->fetch_assoc();
			if(item_isset(25,$thing['price'])){
				minus_item(25,$thing['price']);
				itemAdd($thing['item'],1);
				Info::_logGame($_SESSION['id'], 'BUY_DONAT', ['itemID'=>$itemID], 'items');
        $response['plus'] = '<img src="/img/world/items/little/'.$thing['item'].'.png" class="item"> '.$thing1['name'].' <b>x1</b>';
        $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x'.$thing['price'].'</b>';
				$response['error'] = 0;
			}else{
				$response['error'] = 1;
			}
		break;
		case 'DressPok':
    // Получаем информацию о предмете пользователя
    $my = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$itemID)->fetch_assoc();
    $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$my['item_id'])->fetch_assoc();

    // Проверка на наличие предмета и возможность надеть (dress != 'false')
    if (item_isset($my['item_id'], $count) && $i['dress'] != 'false') {

        // Получаем информацию о покемоне пользователя
        $pokemon = $mysqli->query("SELECT `item_id`,`item_str`,`static` FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();

        // === Ограничение для временных РБ-покемонов ===
        if (isset($pokemon['static']) && $pokemon['static'] === 'rb') {
            $response['error'] = 1;
            $response['text'] = 'На временных покемонов RB нельзя надевать предметы.';
            break;
        }
        // === Конец ограничения ===

        // Если на покемоне уже есть предмет, снимаем его
        if ($pokemon['item_id'] != 0) {
            if (empty($pokemon['item_str'])) {
                itemAdd($pokemon['item_id'], 1);
            } else {
                $mysqli->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$_SESSION['id']."','".$pokemon['item_id']."',1,'".$pokemon['item_str']."') ");
            }
        }

        // Проверяем прочность предмета (если есть str)
        if (!empty($my['str'])) {
            $str = explode(',', $my['str']);
            if ($str[0] <= 0) {
                $response['error'] = 1;
                $response['text'] = 'Невозможно выполнить это действие. Предмет сломан.';
            } else {
                $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$my['item_id'])->fetch_assoc();
                $mysqli->query("UPDATE `user_pokemons` SET `item_id` = '".$my['item_id']."', `item_str` = '".$my['str']."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
                $response['minus'] = '<img src="/img/world/items/little/'.$i['id'].'.png" class="item"> '.$i['name'].' <b>x1</b>';
                $response['error'] = 0;
                $response['text'] = 'Предмет успешно надет!';
                minus_item_id($itemID, 1);
            }
        } else {
            $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$my['item_id'])->fetch_assoc();
            $mysqli->query("UPDATE `user_pokemons` SET `item_id` = '".$my['item_id']."', `item_str` = '".$my['str']."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
            $response['minus'] = '<img src="/img/world/items/little/'.$i['id'].'.png" class="item"> '.$i['name'].' <b>x1</b>';
            $response['error'] = 0;
            $response['text'] = 'Предмет успешно надет!';
            minus_item_id($itemID, 1);
        }
    } else {
        $response['error'] = 1;
    }
    break;
		case 'GivePok':
    if(item_isset($itemID,$count)){
        require_once($patch_project.'/inc/function/Items.php');

        // 1) Берём инфо о предмете (пригодится для определения скина)
        $itemInfo = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".(int)$itemID." LIMIT 1")->fetch_assoc();

        // 2) ВЕТКА: предмет-СКИН для ПОКЕМОНА
        // Условие: либо отдельный тип 'skin_pokemon', либо meta в поле `str` содержит 'skin_form='
        if ($itemInfo && (
              (isset($itemInfo['type']) && $itemInfo['type'] === 'skin_pokemon')
           || (isset($itemInfo['str']) && strpos($itemInfo['str'], 'skin_form=') !== false)
        )) {
            pokemon_skin_new($pokID, $itemID); // применяем скин к ЭТОМУ покемону и списываем 1 шт.
        }

        // --- дальше остаются ваши ветки как были ---
        elseif($itemID == 10 || $itemID == 11 || $itemID == 12 || $itemID == 23 || $itemID == 24 || $itemID == 149 || $itemID == 152 || $itemID == 160 || $itemID == 162 || $itemID == 163 || $itemID == 164 || $itemID == 167 || $itemID == 416 || $itemID == 318 || $itemID == 331) {
            potion_new($pokID,$itemID,$count);#Зелья
        }
        elseif($itemID == 14 || $itemID == 15 || $itemID == 16 || $itemID == 313) {
            potion_pp_new($pokID,$itemID,$count);#Зелья-PP
        }
        elseif(($itemID >= 80 and $itemID <= 119) or $itemID == 559 or $itemID == 560) {
            evol_stones_new($pokID,$itemID);#Камни эволюции
        }
        elseif ($itemID >= 26 and $itemID <= 31) {
            candy_color_new($pokID,$itemID,$count);#Цветные конфеты
        }
        elseif ($itemID == 32) {
            candy_chocolate_new($pokID,$itemID);#Шоколадная конфета
        }
        elseif ($itemID == 33) {
            candy_bitter_new($pokID,$itemID);#Горькая конфета
        }
        elseif ($itemID == 34) {
            candy_black_new($pokID,$itemID);#Черная конфета
        }
        elseif ($itemID >= 35 and $itemID <= 52) {
            candy_type_new($pokID,$itemID,$count);#Типовые конфеты
        }
        elseif ($itemID >= 120 and $itemID <= 123) {
            nectar_new($pokID,$itemID);#Нектар
        }
        elseif ($itemID == 196) {
            personal_new($pokID,$itemID);#Именной бланк
        }
        elseif ($itemID == 197 || $itemID == 597) {
            training_new($pokID,$itemID);#Набор тренировки и Личный набор тренировки
        }
        elseif ($itemID == 198) {
            training_low_new($pokID,$itemID);#Набор ослаблений
        }
        elseif ($itemID >= 199 and $itemID <= 204) {
            vitamines_new($pokID,$itemID,$count);#Витамины
        }
        elseif ($itemID == 241) {
            capsule_ability_new($pokID,$itemID);#Капсула способностей
        }
        elseif ($itemID == 244) {
            tablet_ability_new($pokID,$itemID);#Таблетка способностей
        }
        elseif ($itemID == 245) {
            cake_new($pokID,$itemID);#Кекс
        }
        elseif ($itemID == 246) {
            sweet_cake_new($pokID,$itemID);#Сладкий кекс
        }
        elseif ($itemID == 255) {
            root_apricorn_new($pokID,$itemID);#Корень априкорна
        }
        elseif ($itemID == 256) {
            medicine_new($pokID,$itemID);#Лекарство
        }
        elseif ($itemID >= 257 and $itemID <= 262) {
            pills_new($pokID,$itemID);#Витамины
        }
        elseif ($itemID == 268) {
            memory_potion_new($pokID,$itemID);#Зелье памяти
        }
        elseif ($itemID == 269 || $itemID == 270) {
            hormones_new($pokID,$itemID);#Гормональные
        }
        elseif ($itemID == 426) {
            sweetty_watt_new($pokID);#Гормональные
        }
        elseif ($itemID == 473) {
            hormones_now_new($pokID,$itemID);#Псевдогормональное
        }
        elseif ($itemID == 472) {
            absobent_shine_new($pokID,$itemID);#Абсорбент краски
        }
        elseif ($itemID == 287 || $itemID == 288 || $itemID == 289) {
            preparations_new($pokID,$itemID);#Препараты
        }
        elseif ($itemID == 552 || $itemID == 553 || $itemID == 554 || $itemID == 555 || $itemID == 556) {
            sfere($pokID,$itemID,$count);#Кристаллы
        }
        elseif ($itemID == 324 || $itemID == 325 || $itemID == 333 || $itemID == 305 || $itemID == 307 || $itemID == 312) {
            berry_new($pokID,$itemID);#Препараты
        }
        elseif ($itemID == 367 || $itemID == 368 || $itemID == 369 || $itemID == 370 || $itemID == 371 || $itemID == 372) {
            flings_new($pokID,$itemID,$count);#Препараты
        }
        elseif ($itemID >= 1001 && $itemID <= 2100) {
            tm_new($pokID,$itemID);#TM
        }
        else{
            $_SESSION['text'] = 'У предмета нет функции!';
            $_SESSION['error'] = 1;
        }

        $response['error'] = ($_SESSION['error']?$_SESSION['error']:0);
        $response['action'] = ($_SESSION['action']?$_SESSION['action']:0);
        $response['echo'] = $_SESSION['echo'];
        $response['text'] = $_SESSION['text'];
        $response['other'] = $_SESSION['other'];
        $response['other2'] = $_SESSION['other2'];
        $response['error'] = $_SESSION['error'];
        $i = $mysqli->query("SELECT * FROM base_items WHERE id = ".$itemID)->fetch_assoc();
        if($response['echo'] == 1){

        }else{
            $response['minus'] = '<img src="/img/world/items/little/'.$itemID.'.png" class="item"> '.$i['name'].' <b>x'.$count.'</b>';
        }
    }else{
        $response['text'] = 'Недостаточно предметов.';
        $response['error'] = 1;
    }
    break;

case 'UseSkin':
    header('Content-Type: application/json; charset=utf-8');

    $rowId  = (int)($_POST['itemID'] ?? 0);           // ID строки из items_users
    $userId = (int)($_SESSION['id'] ?? 0);
    if (!$rowId || !$userId) {
        echo json_encode(['error'=>1,'echo'=>0,'text'=>'Нет itemID или вы не авторизованы.']); exit;
    }

    // строка предмета в инвентаре
    $itemRow = $mysqli->query("SELECT * FROM `items_users`
                               WHERE `id`={$rowId} AND `user`={$userId}
                               LIMIT 1")->fetch_assoc();
    if (!$itemRow || $itemRow['count'] <= 0) {
        echo json_encode(['error'=>1,'echo'=>0,'text'=>'У вас нет этого предмета.']); exit;
    }

    // описание предмета
    $itemInfo = $mysqli->query("SELECT * FROM `base_items`
                                WHERE `id`={$itemRow['item_id']}
                                LIMIT 1")->fetch_assoc();
    if (!$itemInfo || $itemInfo['type'] !== 'skin') {
        echo json_encode(['error'=>1,'echo'=>0,'text'=>'Этот предмет не является скином.']); exit;
    }

    // пол пользователя
    $userSexRow = $mysqli->query("SELECT `sex` FROM `users` WHERE `id`={$userId} LIMIT 1")->fetch_assoc();
    $userSex = $userSexRow['sex'] ?? '';

    if (!empty($itemInfo['sex'])) {
        if ($itemInfo['sex']==='f' && $userSex==='m') {
            echo json_encode(['error'=>1,'echo'=>0,'text'=>'Ой! Этот наряд не для тренеров-мужчин. 😉']); exit;
        }
        if ($itemInfo['sex']==='m' && $userSex==='f') {
            echo json_encode(['error'=>1,'echo'=>0,'text'=>'Упс! Это мужской наряд. Попробуй что-то изящнее. 😏']); exit;
        }
    }

    // параметры скина из предмета
    $newSkinId    = (int)($itemInfo['skin_id'] ?? 0);
    $newSkinColor = $mysqli->real_escape_string($itemInfo['skin_color'] ?? 'a');
    $newSkinName  = $mysqli->real_escape_string($itemInfo['info'] ?? '');
    if ($newSkinId <= 0) {
        echo json_encode(['error'=>1,'echo'=>0,'text'=>'У предмета не задан skin_id.']); exit;
    }

    // если такой же уже надет — не тратим предмет
    $curr = $mysqli->query("SELECT `skin`,`color` FROM `cloth` WHERE `user`={$userId} LIMIT 1")->fetch_assoc();
    if ($curr && (int)$curr['skin'] === $newSkinId && ($curr['color'] ?? '') === $newSkinColor) {
        echo json_encode(['error'=>0,'echo'=>0,'text'=>'Этот скин уже надет.']); exit;
    }

    $mysqli->begin_transaction();
    try {
        // 1) вернуть старый скин в инвентарь (если был)
        skin_returnEquippedIfAny($mysqli, $userId);

        // 2) надеть новый
        $q = $mysqli->query("UPDATE `cloth`
                             SET `skin`={$newSkinId}, `color`='{$newSkinColor}', `skinName`='{$newSkinName}'
                             WHERE `user`={$userId}");
        if (!$q || $mysqli->affected_rows === 0) {
            // если строки cloth нет — создадим
            $mysqli->query("INSERT INTO `cloth` (`user`,`skin`,`color`,`skinName`)
                            VALUES ({$userId}, {$newSkinId}, '{$newSkinColor}', '{$newSkinName}')
                            ON DUPLICATE KEY UPDATE `skin`=VALUES(`skin`),`color`=VALUES(`color`),`skinName`=VALUES(`skinName`)");
        }

        // 3) списать предмет
        if ((int)$itemRow['count'] > 1) {
            $ok = $mysqli->query("UPDATE `items_users` SET `count`=`count`-1 WHERE `id`={$rowId}");
            if (!$ok) throw new Exception('Не удалось списать предмет.');
        } else {
            $ok = $mysqli->query("DELETE FROM `items_users` WHERE `id`={$rowId}");
            if (!$ok) throw new Exception('Не удалось удалить предмет.');
        }
        if (function_exists('notify')) {
            notify($userId, 'minus_item', ['item_id'=>$itemRow['item_id'], 'count'=>1]);
        }

        $mysqli->commit();
        echo json_encode([
            'error'=>0,'echo'=>1,
            'text'=>'Скин успешно применён!',
            'skin_id'=>$newSkinId,'skin_color'=>$newSkinColor,'skin_name'=>$newSkinName
        ]);
        exit;

    } catch (Throwable $e) {
        $mysqli->rollback();
        echo json_encode(['error'=>1,'echo'=>0,'text'=>'Ошибка: не удалось применить скин.']); exit;
    }
    break;


case 'remove_hat':
    // Получаем cloth пользователя
    $cloth = $mysqli->query("SELECT `hat` FROM `cloth` WHERE `user` = '".$_SESSION['id']."'")->fetch_assoc();

    if (!$cloth) {
        $response['error'] = 1;
        $response['text'] = 'У вас нет шапки!';
        break;
    }

    // Проверка: есть ли шапка
    if (empty($cloth['hat'])) {
        $response['error'] = 1;
        $response['text'] = 'Шапка не надета!';
        break;
    }

    // Снимаем шапку
    $mysqli->query("UPDATE `cloth` SET `hat`=NULL WHERE `user` = '".$_SESSION['id']."'");

    $response['error'] = 0;
    $response['text'] = 'Шапка успешно снята!';
    break;
		case 'use':

			if(item_isset($itemID, $count)){
				// --- TERA: применить тера-камень к покемону ---
if($itemID >= TERA_STONE_BASE && $itemID < (TERA_STONE_BASE + TERA_TYPES_COUNT)){

    $teraType = tera_type_from_item($itemID);
    if(empty($teraType)){
        $response['error'] = 1;
        $response['text'] = 'Неверный тера-камень.';
        break;
    }

    if(empty($pokID)){
        $response['error'] = 1;
        $response['text'] = 'Выберите покемона.';
        break;
    }

    $uid = (int)$_SESSION['id'];
    $pokeQ = $mysqli->query("SELECT id, tera_type FROM user_pokemons WHERE id=".$pokID." AND user_id=".$uid." LIMIT 1");
    if(!$pokeQ || $pokeQ->num_rows == 0){
        $response['error'] = 1;
        $response['text'] = 'Покемон не найден.';
        break;
    }

    $pokeRow = $pokeQ->fetch_assoc();
    if(!empty($pokeRow['tera_type'])){
        $response['error'] = 1;
        $response['text'] = 'У этого покемона уже есть тератип.';
        break;
    }

    $mysqli->query("UPDATE user_pokemons SET tera_type='".$mysqli->real_escape_string($teraType)."' WHERE id=".$pokID." AND user_id=".$uid." LIMIT 1");
    minus_item($itemID, 1);

    $response['error'] = 0;
    $response['text'] = 'Тератип установлен: <b>'.$teraType.'</b>.';
    $response['minus'] = '<img src="/img/world/items/little/'.$itemID.'.png" width="16"> -1';
    $response['plus'] = '<img src="/img/world/typs/'.$teraType.'.png" width="16"> +тератип';
    break;
}

// --- TERA: ядро переплавки не используется напрямую ---
if($itemID == TERA_REWRITE_CORE_ID){
    $response['error'] = 1;
    $response['text'] = 'Ядро переплавки используется через действие переплавки тератипа.';
    break;
}

require_once($patch_project.'/inc/function/Items.php');
				// if($itemID >= 116 && $itemID <= 119)Box($itemID);#Ящики
				// if($itemID == 152){
        //
        //
				//     if($count > 2000){
        //                 $count = 2000;
        //             }
        //
				//     if($count > 0){
        //
				//         for($i = 0; $i < $count; $i++){
        //                     NGBox($itemID, ($i + 1)); #Новогодний подарок
        //                 }
        //
        //                 minus_item($itemID, $count);
        //
        //             }
        //
        //         }
				if($itemID == 223) { mysterious_ball_new($itemID); }
        elseif ($itemID == 242) { new_year_box_new($itemID); }

        elseif ($itemID == 456) { test($itemID); }
        elseif ($itemID == 195) { set_orb_new($itemID); }
        elseif ($itemID == 194) { set_balls_new($itemID); }
        elseif ($itemID == 193) { set_vitamines_new($itemID); }
        elseif ($itemID == 243) { set_vitamines_small_new($itemID); }
        elseif ($itemID == 181 || $itemID == 182 || $itemID == 183 || $itemID == 184 || $itemID == 448 || $itemID == 5 || $itemID == 6 || $itemID == 187 || $itemID == 189 || $itemID == 190 || $itemID == 192 || $itemID == 185 || $itemID == 191) { amplifiers_catch_money_new($itemID); }
        else{
          $_SESSION['text'] = 'У предмета нет функции!';
        $_SESSION['error'] = 1;}
        #Рецепты
        // if($itemID == 254)drazdo($itemID);#Драздо
        // if($itemID == 327)hell($itemID);#Хэллоуин
        // if($itemID == 255)korobka_pirozn($itemID);#Коробка с пирожными
        // if($itemID == 256)korobka_banki($itemID);#Коробка с витаминами
        // if($itemID == 237 || $itemID == 238 || $itemID == 239 || $itemID == 240 || $itemID == 324)usili($itemID);#Усилители
				// if($itemID == 110)Cloth($itemID);#Одежда
        // if($itemID == 178)MainBox($itemID);#НовыйMainЯщик
				// if($itemID == 173 || $itemID == 174 || $itemID == 175 || $itemID == 176)easterEgg($itemID);#Пасхальные яйца
				// if($itemID > 83 && $itemID < 91)bagUpdate($itemID);#Рюкзаки
				// if($itemID == 111)bagBox($itemID);#Набор рюкзаков
        if($_SESSION['error'] == 0 and $itemID != 456 and $itemID != 5 and $itemID != 6 and $itemID != 187 and $itemID != 189 and $itemID != 190 and $itemID != 192) {
          $i = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$itemID)->fetch_assoc();
          $response['minus'] = '<img src="/img/world/items/little/'.$itemID.'.png" class="item"> '.$i['name'].' <b>x'.$count.'</b>';
        }
        if($_SESSION['plus']) {
          $response['plus'] = $_SESSION['plus'];
        }
				$response['error'] = ($_SESSION['error']?$_SESSION['error']:0);
				$response['text'] = $_SESSION['text'];
				if(isset($_SESSION['text']) && is_array($_SESSION['text'])){
                    $_SESSION['text'] = '';
                }
			}else{
			    $response['text'] = 'Недостаточно предметов.';
				$response['error'] = 1;
			}
		break;
		case 'UsePokemonSkin':
    header('Content-Type: application/json; charset=utf-8');

    $rowId     = (int)($_POST['itemID'] ?? 0);      // строка items_users
    $pokemonId = (int)($_POST['pokemon_id'] ?? 0);  // id в user_pokemons
    $userId    = (int)($_SESSION['id'] ?? 0);

    $fail = function($msg){ echo json_encode(['error'=>1,'text'=>$msg], JSON_UNESCAPED_UNICODE); exit; };
    if(!$rowId || !$pokemonId || !$userId) $fail('Не переданы параметры.');

    // предмет у пользователя
    $itemRow  = $mysqli->query("SELECT * FROM `items_users` WHERE `id`={$rowId} AND `user`={$userId} LIMIT 1")->fetch_assoc();
    if(!$itemRow || $itemRow['count']<=0) $fail('У вас нет этого предмета.');

    // инфо о предмете
    $itemInfo = $mysqli->query("SELECT * FROM `base_items` WHERE `id`={$itemRow['item_id']} LIMIT 1")->fetch_assoc();
    if(!$itemInfo) $fail('Предмет не найден.');
    if($itemInfo['type']!=='skin') $fail('Данный предмет не является скином.');

    // какую «визуальную форму» применяем
    $skinForm = trim($itemInfo['skin_form'] ?? ''); // например, 'alola', 'galar', 'mega' и т.д.
    if($skinForm==='') $fail('У предмета не задана визуальная форма.');

    // сам покемон
    $poke = $mysqli->query("SELECT `id`,`user_id`,`basenum` FROM `user_pokemons` WHERE `id`={$pokemonId} LIMIT 1")->fetch_assoc();
    if(!$poke || (int)$poke['user_id']!==$userId) $fail('Покемон не найден или не ваш.');
    $basenum = (int)$poke['basenum'];

    // Находим запись формы для ЭТОГО вида, чтобы взять тип-папку под спрайт
    // (это не меняет боевой тип — только путь к файлу)
    $formRow = $mysqli->query("
        SELECT `id_form`,`type`
          FROM `base_pokemon_forms_new`
         WHERE `pokemons`={$basenum} AND `id_form`='". $mysqli->real_escape_string($skinForm) ."'
         LIMIT 1
    ")->fetch_assoc();
    if(!$formRow) $fail('Эта визуальная форма для данного покемона не найдена.');

    $spriteType = $formRow['type']; // например, 'dark', 'psychic', 'fire' — для каталога спрайта

    $mysqli->begin_transaction();
    try {
        // фиксируем только визуальную форму и «тип-папку» для спрайта
        $ok1 = $mysqli->query("
            UPDATE `user_pokemons`
               SET `skin_form` = '".$mysqli->real_escape_string($skinForm)."',
                   `skin_sprite_type` = '".$mysqli->real_escape_string($spriteType)."'
             WHERE `id`={$pokemonId} LIMIT 1
        ");
        if(!$ok1) throw new Exception('Не удалось применить визуальную форму.');

        // списываем предмет
        if($itemRow['count']>1){
            $ok2 = $mysqli->query("UPDATE `items_users` SET `count`=`count`-1 WHERE `id`={$rowId} LIMIT 1");
        }else{
            $ok2 = $mysqli->query("DELETE FROM `items_users` WHERE `id`={$rowId} LIMIT 1");
        }
        if(!$ok2) throw new Exception('Не удалось списать предмет.');

        $mysqli->commit();
        echo json_encode(['error'=>0,'text'=>'Скин (визуальная форма) применён','pokemon_id'=>$pokemonId,'skin_form'=>$skinForm], JSON_UNESCAPED_UNICODE);
        exit;
    } catch(Exception $e){
        $mysqli->rollback();
        $fail($e->getMessage());
    }
    break;

case 'remove':
{
    // ID предмета, который ломается при снятии
    $BREAKABLE_RING_ID = 563;

    // Получаем все нужные поля, включая birthday
    $pokemon = $mysqli->query("
        SELECT `item_id`,`item_str`,`gym`,`birthday`
        FROM `user_pokemons`
        WHERE `id` = '".intval($pokID)."' AND `user_id` = '".intval($_SESSION['id'])."' AND `active` = 1
        LIMIT 1
    ")->fetch_assoc();

    // Проверка: если покемон не найден
    if (!$pokemon) {
        $response['error'] = 1;
        $response['text']  = 'Покемон не найден!';
        break;
    }

    // Проверка: если это временный РБ-покемон, запрещаем снять предмет
    if (strpos((string)$pokemon['birthday'], '"user_id":4') !== false) {
        $response['error'] = 1;
        $response['text']  = 'Нельзя снимать предмет с временного покемона арены!';
        break;
    }

    if ((int)$pokemon['item_id'] === (int)$itemID) {

        if ((int)$pokemon['gym'] != 1) {

            $isBreakableRing = ((int)$pokemon['item_id'] === (int)$BREAKABLE_RING_ID);

            // Для текста уведомления (и совместимости с фронтом)
            $itemSel = $mysqli->query("SELECT `name` FROM `base_items` WHERE `id` = ".intval($itemID)." LIMIT 1")->fetch_assoc();
            $response['nameItem'] = $itemSel['name'] ?? '';

            if ($isBreakableRing) {
                // НИЧЕГО не возвращаем в инвентарь — предмет сломан при снятии
                $response['error'] = 0;
                $response['text']  = 'Скобовое кольцо сломано.';
            } else {
                // Обычная логика возврата предмета в инвентарь
                if (empty($pokemon['item_str'])) {
                    itemAdd($pokemon['item_id'], 1);
                } else {
                    $mysqli->query("
                        INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`)
                        VALUES ('".intval($_SESSION['id'])."','".intval($pokemon['item_id'])."',1,'".$mysqli->real_escape_string($pokemon['item_str'])."')
                    ");
                }
                $response['error'] = 0;
                $response['text']  = 'Предмет успешно снят!';
            }

            // Снимаем предмет с покемона
            $mysqli->query("
                UPDATE `user_pokemons`
                SET `item_id` = 0, `item_str` = ''
                WHERE `id` = '".intval($pokID)."' AND `user_id` = '".intval($_SESSION['id'])."' AND `active` = 1
            ");

        } else {
            $response['error'] = 1;
            $response['text']  = 'Невозможно снять предмет с этого покемона!';
        }

    } else {
        $response['error'] = 1;
        $response['text']  = 'У покемона надет другой предмет.';
    }

    break;
}

		case 'pokList':
			$teamUserQuery = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND active = 1");

			$pokList = [];
			if($teamUserQuery){
				while($teamUser = $teamUserQuery->fetch_assoc()){
					$pokList[] = [
								'id'=>$teamUser["id"],
								'basenum'=>numbPok($teamUser["basenum"]),
								'type'=>($teamUser["type"] != 'normal' ? 'shine' : 'normal'),
								'name'=>$teamUser["name_new"],
								'class'=>($teamUser["type"] == 'shadow' ? 'shadow-color' : '')
								];
				}
				$response['pokList'] = $pokList;
			}else{
				$response = 0;
			}
		break;
		case 'reproductionList':
			$teamUserQuery = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = '.$_SESSION['id'].' AND `active` = 1 AND `sparka` = 0');
			$pokList = [];
			if($teamUserQuery){
				while($teamUser = $teamUserQuery->fetch_assoc()){
					$pokList[] = [
								'id'=>$teamUser["id"],
								'basenum'=>numbPok($teamUser["basenum"]),
								'type'=>$teamUser["type"],
								'name'=>$teamUser["name_new"],
								'gender'=>$teamUser["gender"],
								'sparkaNumber'=>$teamUser["sparkaNumber"],
								'gen'=>$teamUser["gen"],
								];
				}
				$response['pokList'] = $pokList;
			}else{
				$response = 0;
			}
		break;
		// --- TERA: крафт тера-камня из осколков ---
case 'craft_tera_stone':

    if($count < 1) $count = 1;

    if($itemID < TERA_SHARD_BASE || $itemID >= (TERA_SHARD_BASE + TERA_TYPES_COUNT)){
        $response['error'] = 1;
        $response['text'] = 'Неверный тера-осколок.';
        break;
    }

    $teraType = tera_type_from_item($itemID);
    $stoneId  = TERA_STONE_BASE + ($itemID - TERA_SHARD_BASE);
    $need     = 100 * $count;

    if(!item_isset($itemID, $need)){
        $response['error'] = 1;
        $response['text'] = 'Недостаточно осколков. Нужно: '.$need.'.';
        break;
    }

    minus_item($itemID, $need);
    itemAdd($stoneId, $count);

    $response['error'] = 0;
    $response['text'] = 'Крафт завершён: тера-камень <b>'.$teraType.'</b> x'.$count.'.';
    $response['minus'] = '<img src="/img/world/items/little/'.$itemID.'.png" width="16"> -'.$need;
    $response['plus']  = '<img src="/img/world/items/little/'.$stoneId.'.png" width="16"> +'.$count;

break;

// --- TERA: переплавка/смена тератипа ---
case 'tera_rewrite':

    $uid = (int)$_SESSION['id'];

    if(empty($pokID)){
        $response['error'] = 1;
        $response['text'] = 'Выберите покемона.';
        break;
    }

    $pokeQ = $mysqli->query("SELECT id, tera_type FROM user_pokemons WHERE id=".$pokID." AND user_id=".$uid." LIMIT 1");
    if(!$pokeQ || $pokeQ->num_rows == 0){
        $response['error'] = 1;
        $response['text'] = 'Покемон не найден.';
        break;
    }

    $mode = (isset($_POST['mode']) ? (string)$_POST['mode'] : 'choose');
    $teraPick = (isset($_POST['tera_type']) ? (string)$_POST['tera_type'] : '');

    // ядро — обязательный расходник для обеих схем (чтобы держать экономику)
    if(!item_isset(TERA_REWRITE_CORE_ID, 1)){
        $response['error'] = 1;
        $response['text'] = 'Нужно: ядро переплавки.';
        break;
    }

    $types = tera_types_list();

    if($mode === 'choose'){

        if(!in_array($teraPick, $types, true)){
            $response['error'] = 1;
            $response['text'] = 'Неверный тип.';
            break;
        }

        // 250 осколков любых + 100 аметиста
        if(!item_isset(25, 100)){
            $response['error'] = 1;
            $response['text'] = 'Нужно: 100 аметиста.';
            break;
        }

        list($totalShards, $map) = tera_user_items_sum($mysqli, $uid, tera_shard_ids());
        if($totalShards < 250){
            $response['error'] = 1;
            $response['text'] = 'Нужно: 250 тера-осколков (любых).';
            break;
        }

        // списания
        minus_item(TERA_REWRITE_CORE_ID, 1);
        minus_item(25, 100);
        tera_deduct_any_shards($mysqli, $uid, 250);

        $mysqli->query("UPDATE user_pokemons SET tera_type='".$mysqli->real_escape_string($teraPick)."' WHERE id=".$pokID." AND user_id=".$uid." LIMIT 1");

        $response['error'] = 0;
        $response['text'] = 'Тератип изменён на <b>'.$teraPick.'</b>.';
        $response['minus'] = '<img src="/img/world/items/little/'.TERA_REWRITE_CORE_ID.'.png" width="16"> -1 <img src="/img/world/items/little/25.png" width="16"> -100 <span style="margin-left:6px;">-250 осколков</span>';
        $response['plus']  = '<img src="/img/world/typs/'.$teraPick.'.png" width="16"> +тератип';

    }elseif($mode === 'reroll'){

        // 150 осколков любых + 200000 монет
        if(!item_isset(1, 200000)){
            $response['error'] = 1;
            $response['text'] = 'Нужно: 200000 монет.';
            break;
        }

        list($totalShards, $map) = tera_user_items_sum($mysqli, $uid, tera_shard_ids());
        if($totalShards < 150){
            $response['error'] = 1;
            $response['text'] = 'Нужно: 150 тера-осколков (любых).';
            break;
        }

        $randType = $types[array_rand($types)];

        // списания
        minus_item(TERA_REWRITE_CORE_ID, 1);
        minus_item(1, 200000);
        tera_deduct_any_shards($mysqli, $uid, 150);

        $mysqli->query("UPDATE user_pokemons SET tera_type='".$mysqli->real_escape_string($randType)."' WHERE id=".$pokID." AND user_id=".$uid." LIMIT 1");

        $response['error'] = 0;
        $response['text'] = 'Тератип перераздан: <b>'.$randType.'</b>.';
        $response['minus'] = '<img src="/img/world/items/little/'.TERA_REWRITE_CORE_ID.'.png" width="16"> -1 <img src="/img/world/items/little/1.png" width="16"> -200000 <span style="margin-left:6px;">-150 осколков</span>';
        $response['plus']  = '<img src="/img/world/typs/'.$randType.'.png" width="16"> +тератип';

    }else{
        $response['error'] = 1;
        $response['text'] = 'Неверный режим.';
    }

break;

default:
			echo "Unknown error";
		break;
	}
echo json_encode($response);
?>

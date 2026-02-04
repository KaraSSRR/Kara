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
$type = (isset($_POST["category"]) ? clearStr($_POST["category"]) : null);
$npc = (isset($_POST["npc"]) ? clearInt($_POST["npc"]) : null);
$id = (isset($_POST["id"]) ? clearInt($_POST['id']) : null);
$count = (isset($_POST["count"]) ? clearInt($_POST['count']) : null);
if(empty($type) ||  empty($npc) || empty($id) || empty($count)){
	$response['error'] = 'error';
	$response['text'] = 'Ошибка в выполнении скрипта.';
	die(json_encode($response));
}
$LocationNpc = $mysqli->query('SELECT
								`bn`.`loc_id`,
								`u`.`location`
							FROM `base_npc` AS `bn`
							INNER JOIN `users` AS `u`
							ON `u`.`id` = '.$_SESSION['id'].'
							WHERE
								`bn`.`id` = '.$npc
						)->fetch_assoc();
$EggQuery = $mysqli->query('SELECT * FROM `user_egg` WHERE `user` = '.$_SESSION['id'].' AND `id` = '.$id)->fetch_assoc();
$quest10 = $mysqli->query('SELECT `need` FROM `npc_more_quest` WHERE `user_id` = '.$_SESSION['id'].' AND `quest_id` = 10')->fetch_assoc();
$response['error'] = 'error';
if($LocationNpc['loc_id'] != $LocationNpc['location']){
	$response['text'] = 'Персонаж отсутствует на этой локации.';
	die(json_encode($response));
}
if($type == 'item') {
  $ItemQuery = $mysqli->query('SELECT * FROM `items_users` WHERE `user` = '.$_SESSION['id'].' AND `id` = '.$id)->fetch_assoc();
  if($ItemQuery) {
    $ItemQueryBase = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$ItemQuery['item_id'])->fetch_assoc();
  }
}
switch ($type) {
    case 'item':
      switch ($npc) {
            case 7:
            case 8:
            case 11:
            case 12:
            case 15:
            case 17:
            case 39:
                if($ItemQuery && in_array($ItemQuery['item_id'], [441,442,443,446])) {
                    if(item_isset($ItemQuery['item_id'],1)) {
                        $count = item_isset_count($ItemQuery['item_id']);
                        $response['text'] = 'Продавец купил ваши товары!';
                        $response['error'] = "success";
                        $response['minus'] = '<img src="/img/world/items/little/'.$ItemQueryBase['id'].'.png" class="item"> '.$ItemQueryBase['name'].' <b>x'.item_isset_count($ItemQuery['item_id']).'</b>';
                        if($ItemQuery['item_id'] == 441){ 
                            $swer = 5000;
                        }elseif($ItemQuery['item_id'] == 442){ 
                            $swer = 10000;
                        }elseif($ItemQuery['item_id'] == 443){ 
                            $swer = 30000;
                        }elseif($ItemQuery['item_id'] == 446){ 
                            $swer = 30000;
                        }
                        $c = $swer*$count;
                        itemAdd(1,$c);
                        $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х'.$c.'</b><br>'; 
                        minus_item($ItemQuery['item_id'],item_isset_count($ItemQuery['item_id']));
                    }else{
                        $response['text'] = 'У вас недостаточное количество.';
                        $response['error'] = "error";
                    }
                }else{
                    $response['text'] = 'Персонажу не нужен данный айтем.';
                    $response['error'] = "error";
                }  
            break;
        //   case 44:
        //   if($ItemQuery && $ItemQuery['item_id'] == 9999) {
        //       if(item_isset($ItemQuery['item_id'],1)) {
        //           $P = $mysqli->query('SELECT * FROM `items_users` WHERE `item_id` = '.$ItemQuery['item_id'].' AND `dop` = '.$ItemQuery['dop'].' AND `user` = '.$_SESSION['id'].' LIMIT 1')->fetch_assoc();
        //       if($ItemQuery['dop'] == 144) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/001.png"> #001 Бульбазавр<br>'; 
        //           newPokemon(1,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 895) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/120.png"> #120 Старью<br>'; 
        //           newPokemon(120,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 664) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/123.png"> #123 Сайтер<br>'; 
        //           newPokemon(123,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2035) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/175.png"> #175 Тогепи<br>'; 
        //           newPokemon(175,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1485) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/190.png"> #190 Айпом<br>'; 
        //           newPokemon(190,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 195) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/207.png"> #207 Глайгер<br>'; 
        //           newPokemon(207,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 767) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/214.png"> #214 Геракросс<br>'; 
        //           newPokemon(214,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 81) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/239.png"> #239 Элекид<br>'; 
        //           newPokemon(239,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2355) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/255.png"> #255 Торчик<br>'; 
        //           newPokemon(255,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1282) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/270.png"> #270 Лотад<br>'; 
        //           newPokemon(270,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 321) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/280.png"> #280 Ралтс<br>'; 
        //           newPokemon(280,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1276) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/293.png"> #293 Вишмур<br>'; 
        //           newPokemon(293,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 127) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/303.png"> #303 Мавайл<br>'; 
        //           newPokemon(303,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2174) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/311.png"> #311 Плюсл<br>'; 
        //           newPokemon(311,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1319) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/312.png"> #312 Минун<br>'; 
        //           newPokemon(312,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1410) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/349.png"> #349 Фибас<br>'; 
        //           newPokemon(349,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 230) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/422.png"> #422 Шеллос<br>'; 
        //           newPokemon(422,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 631) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/436.png"> #436 Бронзор<br>'; 
        //           newPokemon(436,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 608) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/442.png"> #442 Спиритомб<br>'; 
        //           newPokemon(442,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1291) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/447.png"> #447 Риолу<br>'; 
        //           newPokemon(447,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 122) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/529.png"> #529 Дриллбур<br>'; 
        //           newPokemon(529,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1474) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/551.png"> #551 Сандайл<br>'; 
        //           newPokemon(551,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1011) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/568.png"> #568 Траббиш<br>'; 
        //           newPokemon(568,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1972) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/582.png"> #582 Ваниллита<br>'; 
        //           newPokemon(582,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 650) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/594.png"> #594 Аломомола<br>'; 
        //           newPokemon(594,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1819) {
        //           $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х1</b><br>'; 
        //           itemAdd(1,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2050) {
        //           $response['plus'] = '<img src="/img/world/items/little/2.png" class="item"> Покебол <b>х1</b><br>'; 
        //           itemAdd(2,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1264) {
        //           $response['plus'] = '<img src="/img/world/items/little/197.png" class="item"> Набор тренировки <b>х5</b><br>'; 
        //           itemAdd(197,5); $e = 1;
        //       }elseif($ItemQuery['dop'] == 848) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 582) {
        //           $response['plus'] = '<img src="/img/world/items/little/94.png" class="item"> Острый клык <b>х1</b><br>'; 
        //           itemAdd(94,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 967) {
        //           $response['plus'] = '<img src="/img/world/items/little/195.png" class="item"> Коробка с окаменелостями <b>х2</b><br>'; 
        //           itemAdd(195,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1791) {
        //           $response['plus'] = '<img src="/img/world/items/little/53.png" class="item"> Бриллиантовый покебол <b>х10</b><br>'; 
        //           itemAdd(53,10); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1919) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 707) {
        //           $response['plus'] = '<img src="/img/world/items/little/32.png" class="item"> Шоколадная конфета <b>х5</b><br>'; 
        //           itemAdd(32,5); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1784) {
        //           $response['plus'] = '<img src="/img/world/items/little/13.png" class="item"> Стимпак <b>х50</b><br>'; 
        //           itemAdd(13,50); $e = 1;
        //       }elseif($ItemQuery['dop'] == 429) {
        //           $response['plus'] = '<img src="/img/world/items/little/210.png" class="item"> Типовые кристаллы <b>х18</b><br>'; 
        //           itemAdd(205,1); itemAdd(206,1); itemAdd(207,1); itemAdd(208,1); itemAdd(209,1); itemAdd(210,1);
        //           itemAdd(211,1); itemAdd(212,1); itemAdd(213,1); itemAdd(214,1); itemAdd(215,1); itemAdd(216,1);
        //           itemAdd(217,1); itemAdd(218,1); itemAdd(219,1); itemAdd(220,1); itemAdd(221,1); itemAdd(222,1);$e = 1;
        //       }elseif($ItemQuery['dop'] == 170) {
        //           $response['plus'] = '<img src="/img/world/items/little/268.png" class="item"> Зелье памяти <b>х1</b><br>'; 
        //           itemAdd(268,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1942) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 2149) {
        //           $response['plus'] = '<img src="/img/world/items/little/243.png" class="item"> Маленький кейс витаминов <b>х2</b><br>'; 
        //           itemAdd(243,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 341) {
        //           $response['plus'] = '<img src="/img/world/items/little/2.png" class="item"> Покебол <b>х100</b><br>'; 
        //           itemAdd(2,100); $e = 1;
        //       }elseif($ItemQuery['dop'] == 792) {
        //           $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х100.000</b><br>'; 
        //           itemAdd(1,100000); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1732) {
        //           $response['plus'] = '<img src="/img/world/items/little/240.png" class="item"> Капсула <b>х20</b><br>'; 
        //           itemAdd(240,20); $e = 1;
        //       }elseif($ItemQuery['dop'] == 507) {
        //           $response['plus'] = '<img src="/img/world/items/little/2084.png" class="item"> TR84 Кипяток <b>х1</b><br>'; 
        //           itemAdd(2084,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 573) {
        //           $response['plus'] = '<img src="/img/world/items/little/2010.png" class="item"> TR10 Землетрясение <b>х1</b><br>'; 
        //           itemAdd(2010,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 171) {
        //           $response['plus'] = '<img src="/img/world/items/little/2002.png" class="item"> TR02 Огнемет <b>х1</b><br>'; 
        //           itemAdd(2002,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 827) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/640.png"> #640 Виризион<br>'; 
        //           newPokemon(640,$_SESSION['id'],1,20,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 882) {
        //           $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х1.000.000</b><br>'; 
        //           itemAdd(1,1000000); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1193) {
        //           $response['plus'] = '<img src="/img/world/items/little/103.png" class="item"> Перламутровая чешуя <b>х1</b><br>'; 
        //           itemAdd(103,1); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1699) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/147.png"> #147 Дратини<br>'; 
        //           newPokemon(147,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 47) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/255.png"> #255 Торчик<br>'; 
        //           newPokemon(255,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2289) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 1383) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/371.png"> #371 Багон<br>'; 
        //           newPokemon(371,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1270) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/387.png"> #387 Туртвиг<br>'; 
        //           newPokemon(387,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 358) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/390.png"> #390 Чимчар<br>'; 
        //           newPokemon(390,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 97) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/440.png"> #440 Хеппини<br><img src="/img/world/items/little/88.png" class="item"> Овальный камень <b>х1</b><br>'; 
        //           newPokemon(440,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //           itemAdd(88,1);
        //       }elseif($ItemQuery['dop'] == 1671) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/451.png"> #451 Скорупи<br>'; 
        //           newPokemon(451,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 255) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/495.png"> #495 Снайви<br>'; 
        //           newPokemon(495,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 544) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/501.png"> #501 Ошавот<br>'; 
        //           newPokemon(501,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1152) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 1609) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/532.png"> #532 Тимбур<br>'; 
        //           newPokemon(532,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2175) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/554.png"> #554 Дарумака<br>'; 
        //           newPokemon(554,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2119) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/559.png"> #559 Скрагги<br><img src="/img/world/items/little/2051.png" class="item"> ТR51 Танец дракона <b>х1</b><br>'; 
        //           newPokemon(559,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //           itemAdd(2051,1);
        //       }elseif($ItemQuery['dop'] == 625) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/562.png"> #562 Ямаск<br>'; 
        //           newPokemon(562,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 357) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/587.png"> #587 Эмолга<br>'; 
        //           newPokemon(587,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2049) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/592.png"> #592 Фриллиш<br>'; 
        //           newPokemon(592,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2531) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/599.png"> #599 Клинк<br>'; 
        //           newPokemon(599,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1756) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/602.png"> #602 Тинамо<br>'; 
        //           newPokemon(602,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 664) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/613.png"> #613 Кабчу<br>'; 
        //           newPokemon(613,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1484) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/618.png"> #618 Станфиск<br>'; 
        //           newPokemon(618,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 248) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/619.png"> #619 Минфу<br>'; 
        //           newPokemon(619,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 322) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/627.png"> #627 Раффлет<br>'; 
        //           newPokemon(627,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1603) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/669.png"> #669 Флабэбэ<br><img src="/img/world/items/little/87.png" class="item"> Сияющий камень <b>х1</b><br>'; 
        //           newPokemon(669,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //           itemAdd(87,1);
        //       }elseif($ItemQuery['dop'] == 2495) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/696.png"> #696 Тайрант<br><img src="/img/world/items/little/2051.png" class="item"> ТR51 Танец дракона <b>х1</b><br>'; 
        //           newPokemon(696,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //           itemAdd(2051,1);
        //       }elseif($ItemQuery['dop'] == 699) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/707.png"> #707 Клефки<br>'; 
        //           newPokemon(707,$_SESSION['id'],1,28,1,'true',1,false,true,false,false,true); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1125) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 2549) {
        //           $e = 2;
        //       }elseif($ItemQuery['dop'] == 4) {
        //           $response['plus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>х50</b><br>'; 
        //           itemAdd(25,50); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2479) {
        //           $response['plus'] = '<img src="/img/world/items/little/223.png" class="item"> Загадочный покебол <b>х2</b><br>'; 
        //           itemAdd(223,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 141) {
        //           $response['plus'] = '<img src="/img/world/items/little/448.png" class="item"> Премиум <b>х1</b><br>'; 
        //           itemAdd(448,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1677) {
        //           $response['plus'] = '<img src="/img/world/items/little/1014.png" class="item"> ТМ14 Электрошок <b>х1</b><br>'; 
        //           itemAdd(1014,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 774) {
        //           $response['plus'] = '<img src="/img/world/items/little/2033.png" class="item"> TR33 Шар Тьмы <b>х1</b><br>'; 
        //           itemAdd(2033,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 742) {
        //           $response['plus'] = '<img src="/img/world/items/little/1017.png" class="item"> ТМ17 Экран света <b>х1</b><br>'; 
        //           itemAdd(17,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 1294) {
        //           $response['plus'] = '<img src="/img/world/items/little/1014.png" class="item"> ТМ18 Отражение <b>х1</b><br>'; 
        //           itemAdd(1018,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 2320) {
        //           $response['plus'] = '<img src="/img/world/items/little/2033.png" class="item"> TR08 Молния <b>х1</b><br>'; 
        //           itemAdd(2008,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 610) {
        //           $response['plus'] = '<img src="/img/world/items/little/2033.png" class="item"> TR05 Ледяной луч <b>х1</b><br>'; 
        //           itemAdd(2005,2); $e = 1;
        //       }elseif($ItemQuery['dop'] == 753) {
        //           $response['plus'] = '<img src="/img/pokemons/animation/607.png"> #607 Литвик<br><img src="/img/world/items/little/86.png" class="item"> Сумрачный камень <b>х1</b><br>'; 
        //           newPokemon(607,$_SESSION['id'],1,30,1,'true',1,false,true,false,false,true); $e = 1; itemAdd(86,1);
        //       }else{
        //           $e = 0;
        //       }
              
              
        //       if($e == 1){
        //           $response['text'] = 'Лотерейщик забрал выигрышный билет #'.$ItemQuery['dop'];
        //           $response['error'] = "success";
        //           $response['minus'] = '<img src="/img/world/items/little/9999.png" class="item"> Лотерейный билет <b>#'.$ItemQuery['dop'].'</b>';
        //           minus_item_id($P['id'],1);
        //       }elseif($e == 2){
        //           $response['text'] = 'Билет #'.$ItemQuery['dop'].' имеет уникальный приз, сообщите об этом администрации для его получения.';
        //           $response['error'] = "success";
        //         }else{
        //           $response['text'] = 'Лотерейщик посмотрел на билет #'.$ItemQuery['dop'].', но выигрыша не было!';
        //           $response['error'] = "info";
        //           minus_item_id($P['id'],1);
        //       }
              
        //       }else{
        //     $response['text'] = 'У вас недостаточно билетов.';
        //     $response['error'] = "error";
        //   }
        //   }else{
        //     $response['text'] = 'Персонажу не нужен данный айтем.';
        //     $response['error'] = "error";
        //   }
        // break;
          case 46:
              if($ItemQuery && in_array($ItemQuery['item_id'], [205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222])) {
                if(item_isset(205,1) and item_isset(206,1) and item_isset(207,1) and item_isset(208,1) and item_isset(209,1) and item_isset(210,1) and item_isset(211,1) and item_isset(212,1) and item_isset(213,1) and
                item_isset(214,1) and item_isset(215,1) and item_isset(216,1) and item_isset(217,1) and item_isset(218,1) and item_isset(219,1) and item_isset(220,1) and item_isset(221,1) and item_isset(222,1)) {
                  $r = rand(1,18);
                  if($r == 1){
                      $response['text'] = "Сработал Кристалл Жук";
                      newPokemon(872,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/872.png"> #872 Сном<br>';
                  }elseif($r == 2){
                      $response['text'] = "Сработал Темный кристалл";
                      newPokemon(629,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/629.png"> #629 Валлаби<br>';
                  }elseif($r == 3){
                      $response['text'] = "Сработал Драконий кристалл";
                      newPokemon(704,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/704.png"> #704 Гуми<br>';
                  }elseif($r == 4){
                      $response['text'] = "Сработал Электрический кристалл";
                      newPokemon(602,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/602.png"> #602 Тинамо<br>';
                  }elseif($r == 5){
                      $response['text'] = "Сработал Кристалл Феи";
                      newPokemon(669,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/669.png"> #669 Флабэбэ<br>';
                  }elseif($r == 6){
                      $response['text'] = "Сработал Боевой кристалл";
                      newPokemon(56,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/56.png"> #056 Манки<br>';
                  }elseif($r == 7){
                      $response['text'] = "Сработал Огненный кристалл";
                      newPokemon(322,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/322.png"> #322 Нумел<br>';
                  }elseif($r == 8){
                      $response['text'] = "Сработал Летающий кристалл";
                      newPokemon(701,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/701.png"> #701 Холуча<br>';
                  }elseif($r == 9){
                      $response['text'] = "Сработал Призрачный кристалл";
                      newPokemon(592,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/592.png"> #592 Фриллиш<br>';
                  }elseif($r == 10){
                      $response['text'] = "Сработал Травяной кристалл";
                      newPokemon(191,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/191.png"> #191 Санкерн<br>';
                  }elseif($r == 11){
                      $response['text'] = "Сработал Земляной кристалл";
                      newPokemon(769,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/769.png"> #769 Сэндигаст<br>';
                  }elseif($r == 12){
                      $response['text'] = "Сработал Ледяной кристалл";
                      newPokemon(238,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/238.png"> #238 Смучам<br>';
                  }elseif($r == 13){
                      $response['text'] = "Сработал Нормальный кристалл";
                      newPokemon(52,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/052.png"> #052 Мяут<br>';
                  }elseif($r == 14){
                      $response['text'] = "Сработал Ядовитый кристалл";
                      newPokemon(88,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/088.png"> #088 Граймер<br>';
                  }elseif($r == 15){
                      $response['text'] = "Сработал Психический кристалл";
                      newPokemon(96,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/096.png"> #096 Дроузи<br>';
                  }elseif($r == 16){
                      $response['text'] = "Сработал Каменный кристалл";
                      newPokemon(304,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/304.png"> #304 Арон<br>';
                  }elseif($r == 17){
                      $response['text'] = "Сработал Стальной кристалл";
                      newPokemon(679,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/679.png"> #679 Хонэдж<br>';
                  }elseif($r == 18){
                      $response['text'] = "Сработал Водный кристалл";
                      newPokemon(211,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true);
                      $response['plus'] = '<img src="/img/pokemons/animation/211.png"> #211 Квилфиш<br>';
                  }
                  minus_item(205,1);
                  minus_item(206,1);
                  minus_item(207,1);
                  minus_item(208,1);
                  minus_item(209,1);
                  minus_item(210,1);
                  minus_item(211,1);
                  minus_item(212,1);
                  minus_item(213,1);
                  minus_item(214,1);
                  minus_item(215,1);
                  minus_item(216,1);
                  minus_item(217,1);
                  minus_item(218,1);
                  minus_item(219,1);
                  minus_item(220,1);
                  minus_item(221,1);
                  minus_item(222,1);
                  $mysqli->query("INSERT INTO `drazdo` (`user`,`prize`,`date`) VALUES ('".$_SESSION['id']."','".$response['plus']."', '2') ");
                  $response['error'] = "success";
                  
                $response['minus'] = 'Типовые кристаллы <b>x18</b>';
                }else{
                    $response['text'] = 'У вас недостаточно кристаллов.';
                    $response['error'] = "error";
                }
              }else{
                $response['text'] = 'Персонажу не нужен данный айтем.';
                $response['error'] = "error";
              }
            break;
          case 37:
          if($ItemQuery && in_array($ItemQuery['item_id'], [452,453,454,455])) {
              if(item_isset($ItemQuery['item_id'],1)) {
                  $count = item_isset_count($ItemQuery['item_id']);
                $response['text'] = 'Кролик успешно забрал пасхальные яйца!';
                $response['error'] = "success";
                $response['minus'] = '<img src="/img/world/items/little/'.$ItemQueryBase['id'].'.png" class="item"> '.$ItemQueryBase['name'].' <b>x'.item_isset_count($ItemQuery['item_id']).'</b>';
                    if($ItemQuery['item_id'] == 452){ 
                        if($count >= 1){ $rs .= '<img src="/img/world/items/little/26.png" class="item"> Желтая конфета <b>x10</b><br>'; itemAdd(26,10); }
                        if($count >= 5){ if(rand(1,2) == 1){$rs .= '<img src="/img/world/items/little/181.png" class="item"> Малый усилитель ловли <b>x1</b><br>'; itemAdd(181,1); }else{$rs .= '<img src="/img/world/items/little/182.png" class="item"> Малый усилитель монет <b>x1</b><br>'; itemAdd(183,1); }}
                        if($count >= 10){ $rs .= '<img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x5</b><br>'; itemAdd(29,5); }
                        if($count >= 20){ $rs .= '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x2</b><br>'; itemAdd(245,2); }
                        if($count >= 30){ if(rand(1,2) == 1){$rs .= '<img src="/img/world/items/little/145.png" class="item"> Объедки <b>x1</b><br>'; itemAdd(145,1); }else{$rs .= '<img src="/img/world/items/little/147.png" class="item"> Балласт <b>x1</b><br>'; itemAdd(147,1); }}
                        if($count >= 35){ $rs .= '<img src="/img/world/items/little/240.png" class="item"> Капсула <b>x4</b><br>'; itemAdd(240,4); }
                        if($count >= 50){ if(rand(1,2) == 1){ $rs .= '<img src="/img/pokemons/animation/311.png"> #311 Плюсл<br>'; newPokemon(311,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true); }else{ $rs .= '<img src="/img/pokemons/animation/312.png"> #312 Минун<br>'; newPokemon(312,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true); }}
                        if($count >= 65){ $rs .= '<img src="/img/pokemons/animation/431.png"> #431 Глеймяу<br>'; newPokemon(431,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true); }
                        if($count >= 80){ $rs .= '<img src="/img/pokemons/animation/320.png"> #320 Вайлмер<br>'; newPokemon(320,$_SESSION['id'],1,28,0,'true',0,false,false,false,false,true); }
                        if($count >= 100){ $rs .= '<img src="/img/world/items/little/98.png" class="item"> Протектор <b>x1</b><br>'; itemAdd(98,1); }
                    }
                    
                    if($ItemQuery['item_id'] == 453){ 
                        if($count >= 1){ $r = rand(125,141); $rs .= '<img src="/img/world/items/little/'.$r.'.png" class="item"> Стабовый усилитель <b>x1</b><br>'; itemAdd($r,1); }
                        if($count >= 4){ if(rand(1,2) == 1){$rs .= '<img src="/img/world/items/little/142.png" class="item"> Блестки <b>x1</b><br>'; itemAdd(142,1); }else{$rs .= '<img src="/img/world/items/little/143.png" class="item"> Линзы <b>x1</b><br>'; itemAdd(143,1); }}
                        if($count >= 10){ $rs .= '<img src="/img/world/items/little/62.png" class="item"> Даркбол <b>x5</b><br>'; itemAdd(62,5); }
                        if($count >= 20){ $rs .= '<img src="/img/world/items/little/5.png" class="item"> Старая удочка <b>x1</b><br>'; itemAdd(5,1); }
                        if($count >= 25){ $rs .= '<img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x2</b><br>'; itemAdd(246,2); }
                    }
                    if($ItemQuery['item_id'] == 454){ 
                        if($count >= 1){ if(rand(1,2) == 1){$rs .= '<img src="/img/world/items/little/146.png" class="item"> Адреналиновый шар <b>x1</b><br>'; itemAdd(146,1); }else{$rs .= '<img src="/img/world/items/little/153.png" class="item"> Защитные очки <b>x1</b><br>'; itemAdd(153,1); }}
                        if($count >= 2){ $rs .= '<img src="/img/world/items/little/197.png" class="item"> Набор тренировки <b>x2</b><br>'; itemAdd(197,2); }
                        if($count >= 4){ $rs .= '<img src="/img/world/items/little/195.png" class="item"> Коробка с окаменелостями <b>x1</b><br>'; itemAdd(195,1); }
                        if($count >= 6){ $rs .= '<img src="/img/world/items/little/192.png" class="item"> Мед <b>x1</b><br>'; itemAdd(192,1); }
                    }
                    if($ItemQuery['item_id'] == 455){ 
                        if($count >= 1){ if(rand(1,2) == 1){$rs .= '<img src="/img/world/items/little/255.png" class="item"> Корень априкорна <b>x1</b><br>'; itemAdd(255,1); }else{$rs .= '<img src="/img/world/items/little/268.png" class="item"> Зелье памяти <b>x1</b><br>'; itemAdd(268,1); }}
                        if($count >= 2){ $rs .= '<img src="/img/world/items/little/448.png" class="item"> Премиум <b>x1</b><br>'; itemAdd(448,1); }
                        if($count >= 3){ $rs .= '<img src="/img/world/items/little/6.png" class="item"> Спиннинг <b>x1</b><br>'; itemAdd(6,1); }
                        if($count >= 4){ $rs .= '<img src="/img/world/items/little/223.png" class="item"> Загадочный покебол <b>x1</b><br>'; itemAdd(223,1); }
                    }
                
                $response['plus'] = $rs;
                minus_item($ItemQuery['item_id'],item_isset_count($ItemQuery['item_id']));
              }else{
            $response['text'] = 'У вас недостаточно яиц.';
            $response['error'] = "error";
          }
          }else{
            $response['text'] = 'Персонажу не нужен данный айтем.';
            $response['error'] = "error";
          }
        break;
        case 35:
          if($ItemQuery && in_array($ItemQuery['item_id'], [224,225,226,227,228,229,230,231,232,233,234,235,236,237,238])) {
              if(item_isset($ItemQuery['item_id'],1)) {
                  $P = $mysqli->query('SELECT * FROM `items_users` WHERE `item_id` = '.$ItemQuery['item_id'].' AND `dop` = '.$ItemQuery['dop'].' AND `user` = '.$_SESSION['id'].' LIMIT 1')->fetch_assoc();
              if(rand(1,130) <= $ItemQuery['dop']) {
                  
                $response['text'] = 'Воссоздание покемона прошло успешно';
                $response['error'] = "success";
                $response['minus'] = '<img src="/img/world/items/little/'.$ItemQueryBase['id'].'.png" class="item"> '.$ItemQueryBase['name'].' <b>x1</b>';
                    if($ItemQuery['item_id'] == 224){ $response['plus'] = '<img src="/img/pokemons/animation/138.png"> #138 Оманайт<br>'; $p = 138; }
                elseif($ItemQuery['item_id'] == 225){  $response['plus'] = '<img src="/img/pokemons/animation/140.png"> #140 Кабуто<br>'; $p = 140; }
                elseif($ItemQuery['item_id'] == 226){  $response['plus'] = '<img src="/img/pokemons/animation/142.png"> #142 Аэродактиль<br>'; $p = 142; }
                elseif($ItemQuery['item_id'] == 227){  $response['plus'] = '<img src="/img/pokemons/animation/345.png"> #345 Лилип<br>'; $p = 345; }
                elseif($ItemQuery['item_id'] == 228){  $response['plus'] = '<img src="/img/pokemons/animation/347.png"> #347 Анорит<br>'; $p = 347; }
                elseif($ItemQuery['item_id'] == 229){  $response['plus'] = '<img src="/img/pokemons/animation/408.png"> #408 Кранидос<br>'; $p = 408; }
                elseif($ItemQuery['item_id'] == 230){  $response['plus'] = '<img src="/img/pokemons/animation/410.png"> #410 Шелдон<br>'; $p = 410; }
                elseif($ItemQuery['item_id'] == 231){  $response['plus'] = '<img src="/img/pokemons/animation/566.png"> #566 Архен<br>'; $p = 566; }
                elseif($ItemQuery['item_id'] == 232){  $response['plus'] = '<img src="/img/pokemons/animation/564.png"> #564 Тиртога<br>'; $p = 564; }
                elseif($ItemQuery['item_id'] == 233){  $response['plus'] = '<img src="/img/pokemons/animation/698.png"> #698 Амаура<br>'; $p = 698; }
                elseif($ItemQuery['item_id'] == 234){  $response['plus'] = '<img src="/img/pokemons/animation/696.png"> #696 Тайрант<br>'; $p = 696; }
                elseif($ItemQuery['item_id'] == 235){  $response['plus'] = '<img src="/img/pokemons/animation/880.png"> #880 Дракозольт<br>'; $p = 880; }
                elseif($ItemQuery['item_id'] == 236){  $response['plus'] = '<img src="/img/pokemons/animation/881.png"> #881 Арктозольт<br>'; $p = 881; }
                elseif($ItemQuery['item_id'] == 237){  $response['plus'] = '<img src="/img/pokemons/animation/882.png"> #882 Драковиш<br>'; $p = 882; }
                elseif($ItemQuery['item_id'] == 238){  $response['plus'] = '<img src="/img/pokemons/animation/883.png"> #883 Арктовиш<br>'; $p = 883; }
                if(achiv_utility(16)){
								        $util = 28;
								    }else{
								        $util = 24;
								    }
                newPokemon($p,$_SESSION['id'],1,$util,1,'false',1,false,false,false,false,true);
                minus_item_id($P['id'],1);
                update_achiv(16,1);
                $mysqli->query("INSERT INTO `drazdo` (`user`,`prize`,`date`) VALUES ('".$_SESSION['id']."','".$response['plus']."', '1') ");
                if(check_mission_ivent(45)){ add_mission_ivent(45);}
              }else{
                $response['text'] = 'Воссоздать покемона не получилось!';
                minus_item_id($P['id'],1);
                $response['error'] = "error";
              }
              }else{
            $response['text'] = 'У вас недостаточно окаменелостей.';
            $response['error'] = "error";
          }
          }else{
            $response['text'] = 'Персонажу не нужен данный айтем.';
            $response['error'] = "error";
          }
        break;
        case 36:
          if($ItemQuery && in_array($ItemQuery['item_id'], [439,440])) {
              if(item_isset($ItemQuery['item_id'],$count)) {
              if($ItemQuery['item_id'] == 439){ $m = $count*1500; }else{ $m = $count*3000; }
                $response['text'] = 'Коллекционер забрал крышки и вознагродил вас';
                $response['error'] = "success";
                $response['minus'] = '<img src="/img/world/items/little/'.$ItemQueryBase['id'].'.png" class="item"> '.$ItemQueryBase['name'].' <b>x'.$count.'</b>';
                $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x'.$m.'</b>';
                minus_item($ItemQuery['item_id'],$count);
                itemAdd(1,$m);
                if(check_mission_ivent(28)){ add_mission_ivent(28,$count);}
          }else{
            $response['text'] = 'У вас недостаточно крышек.';
            $response['error'] = "error";
          }
          }else{
            $response['text'] = 'Персонажу не нужен данный айтем.';
            $response['error'] = "error";
          }
        break;
        // case 208:
        //   if($ItemQuery && in_array($ItemQuery['item_id'], [69,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195])) {
        //     $proch = explode(',',$ItemQuery['str']);
        //     $PilBase = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$ItemQueryBase['info'])->fetch_assoc();
        //     $response['text'] = 'Предмет удачно измельчен на пыль.';
        //     $response['error'] = "success";
        //     $response['plus'] = '<img src="/img/world/items/little/'.$PilBase['id'].'.png" class="item"> '.$PilBase['name'].' ('.$proch[1].' шт.)';
        //     $response['minus'] = '<img src="/img/world/items/little/'.$ItemQuery['item_id'].'.png" class="item"> '.$ItemQueryBase['name'].' (1 шт.)';
        //     itemAdd($PilBase['id'],$proch[1]);
        //     $mysqli->query("DELETE FROM `items_users` WHERE `id` = '".$id."'");
        //   }else{
        //     $response['text'] = 'Персонажу не нужен данный айтем.';
        //   }
        // break;
        // case 188:
        //   if($ItemQuery && $ItemQuery['item_id'] == 184) {
        //     $PokemonQuery = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = '.$_SESSION['id'].' AND `active` = 1 AND `start_pok` = 1')->fetch_assoc();
        //     if($PokemonQuery && $PokemonQuery['basenum'] == 82) {
        //       $mysqli->query("DELETE FROM `items_users` WHERE `id` = '".$id."'");
        //       $mysqli->query('UPDATE `user_pokemons` SET `basenum` = 462, `name_new` = "Магнезон" WHERE `id` = '.$PokemonQuery['id']);
        //       $response['minus'] = '<img src="/img/world/items/little/184.png" class="item"> Магнит (1 шт.)';
        //       $response['text'] = 'Покемон удачно эволюционировал.';
        //       $response['error'] = "success";
        //     }else{
        //       $response['text'] = 'Ошибка в выборе покемона.';
        //     }
        //   }else{
        //     $response['text'] = 'Персонажу не нужен данный айтем.';
        //   }
        // break;
        // case 190:
        //   if($ItemQuery && $ItemQuery['item_id'] == 106) {
        //     $usilItem = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = '301'")->fetch_assoc();
        //     $usilFunc = explode(',',$usilItem['info']);
        //     $time = time() + $usilFunc[0];
        //     $usil = $mysqli->query("SELECT * FROM `bafs` WHERE `type` = '".$usilFunc[1]."' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
        //     if($usil) {
      	// 			$mysqli->query("UPDATE `bafs` SET `time` = '".$time."',`baf` = '301' WHERE `type` = '".$usilFunc[1]."' AND `user` = '".$_SESSION['id']."'");
      	// 		}else{
      	// 			$mysqli->query("INSERT INTO `bafs` (`user`,`baf`,`time`,`type`) VALUES ('".$_SESSION['id']."','301', '".$time."', '".$usilFunc[1]."') ");
      	// 		}
        //     $mysqli->query("DELETE FROM `items_users` WHERE `id` = '".$id."'");
        //     $response['minus'] = '<img src="/img/world/items/little/106.png" class="item"> Объедки (1 шт.)';
        //     $response['text'] = 'Снорлакс насытился и впустил вас на Военный участок. Будьте осторожны!';
        //     $response['error'] = "success";
        //   }else{
        //     $response['text'] = 'Персонажу не нужен данный айтем.';
        //   }
        // break;
        default:
					$response['text'] = 'Данный персонаж не взаимодействует с этим предметом.';
				break;
      }
    break;
		case 'egg':
			switch ($npc) {
				// case 99:
				// 	if(!item_isset(1, 350000)){
				// 		$response['text'] = 'У вас недостаточно денег.';
				// 	}else{
				// 		$reborn = floor(($EggQuery['reborn'] - time())/2);
				// 		$newReborn = time() + $reborn;
				// 		minus_item(1,350000);
        //     $response['minus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета (350000 шт.)';
				// 		$mysqli->query('UPDATE `user_egg` SET `reborn` = '.$newReborn.' WHERE `id` = '.$id);
				// 		$response['text'] = 'Срок вылупления яйца уменьшен.';
        //     $response['error'] = "success";
				// 	}
				// break;
				// case 116:
				// 	if(Work::_questStep(10,6) && $EggQuery['basenum']){
        //
				// 		if($EggQuery['basenum'] == $quest10['need']){
				// 			if(!Work::_npcTimeCheck(116)){
				// 				$rand = rand(1,5);
				// 				if($rand == 1){
				// 					Work::_itemPlus(109,5);
				// 					$prize = 'Генобол (5 шт.)';
        //           $response['plus'] = '<img src="/img/world/items/little/109.png" class="item"> Генобол (5 шт.)';
				// 				}elseif($rand == 2){
				// 					Work::_itemPlus(1,100000);
				// 					$prize = 'Монета (100000 шт.)';
        //           $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета (100000 шт.)';
				// 				}elseif($rand == 3){
				// 					Work::_itemPlus(31,10);
				// 					$prize = 'Леденец в форме Торчика (10 шт.)';
        //           $response['plus'] = '<img src="/img/world/items/little/31.png" class="item"> Леденец в форме Торчика (10 шт.)';
				// 				}elseif($rand == 4){
				// 					Work::_itemPlus(35,1);
				// 					$prize = 'Лунный камень (1 шт.)';
        //           $response['plus'] = '<img src="/img/world/items/little/35.png" class="item"> Лунный камень (1 шт.)';
				// 				}else{
				// 					Work::_itemPlus(149,3);
				// 					$prize = 'Любовное ожерелье (3 шт.)';
        //           $response['plus'] = '<img src="/img/world/items/little/149.png" class="item"> Любовное ожерелье (3 шт.)';
				// 				}
				// 				$mysqli->query("DELETE FROM `user_egg` WHERE `id` = '".$id."'");
				// 				$wait = time()+86400;
				// 				$mysqli->query("DELETE FROM `base_npc_data` WHERE `npcID` = 116 AND `userID` = '".$_SESSION['id']."'");
				// 				$mysqli->query("INSERT INTO `base_npc_data` (`userID`,`npcID`,`time`) VALUES('".$_SESSION['id']."','116','".$wait."') ");
				// 				Work::_questUpdate(10,5);
				// 				$mysqli->query("UPDATE `quest_steps` SET `text` = 'Отдал яйцо Арине. Еще одного заказа от нее не получил, ибо никто не заказал какое-либо яйцо, пока что. Приду позже.' WHERE `id_user` = '".$_SESSION['id']."' AND `quest_id` = 10 AND `quest_step` = 6");
        //         $response['minus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо (1 шт.)';
        //       }else{
				// 				$response['text'] = 'Данный персонаж не взаимодействует с этим предметом.';
				// 			}
				// 		}else{
				// 			$response['text'] = 'Данный персонаж не взаимодействует с этим предметом.';
				// 		}
				// 	}else{
				// 		$response['text'] = 'Данный персонаж не взаимодействует с этим предметом.';
				// 	}
				// break;
				default:
					$response['text'] = 'Данный персонаж не взаимодействует с этим предметом.';
				break;
			}
		break;
		default:
			$response['text'] = 'Ошибка #1.';
		break;
	}
echo json_encode($response);
?>

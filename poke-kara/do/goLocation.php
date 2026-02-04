<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}

// Подключаем rb/functions_rb.php для доступа к функции удаления RB команды
require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');

// --- Очистка RB-команды при смене локации ---
function rb_cleanup_team_on_location_change($userId, $newLocationId) {
    global $mysqli;
    $rbAllowedLocation = 8009; // ID вашей RB-арены (исправлено: арена = 8009)
    // Если новая локация не равна арене, удаляем временных покемонов
    if ($newLocationId != $rbAllowedLocation) {
        rb_removePokemons($mysqli, $userId);
        $mysqli->query("UPDATE users SET status = 'free', status_id = 0 WHERE id = $userId");
    }
}

function npc_time_check($id){
	global $mysqli;
	$q = $mysqli->query("SELECT `time` FROM `base_npc_data` WHERE `userID` = '".$_SESSION['id']."' AND `npcID` = '".$id."'")->fetch_assoc();
	$a = ($q['time'] > time() ? true : false);
	return $a;
}
function info_quest($id,$tip){
  global $mysqli;
  $quest = $mysqli->query("SELECT * FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."'")->fetch_assoc();
  $a = ($quest[$tip]?$quest[$tip]:false);
  return $a;
}
function quest_update($id, $step, $end=false){
	global $mysqli;
	if($end == false){$end = '0';}
	if(quest_isset($id)){
		$a = $mysqli->query("UPDATE `user_quests` SET `step` = '".$step."', `end` = '".$end."' WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."' ");
	}else{
		$a = $mysqli->query("INSERT INTO `user_quests` (`quest_id`,`user_id`,`step`,`end`) VALUES('".$id."','".$_SESSION['id']."','".$step."','".$end."') ");
	}
return $a;
}
function quest_isset($id){
  global $mysqli;
  $quest = $mysqli->query("SELECT `quest_id` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."'")->fetch_assoc();
  $a = ($quest['quest_id']?true:false);
  return $a;
}
function quest_step($id, $step){
  global $mysqli;
  if($step == 0)  $a = true;
   else{
      $q = $mysqli->query("SELECT `step` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."'")->fetch_assoc();
      $a = ($q['step'] == $step?true:false);
  }
 return $a;
}
if(!empty($_POST['location_id'])){
    $location_id = clearInt($_POST["location_id"]);
    $telep = 0;
}elseif(!empty($_POST['location_id_telep'])){
    $location_id = 3;
    $telep = 1;
}elseif(!empty($_POST['location_id_arena'])){         // << NEW
    $location_id = 8009;                               // локация Арены
    $telep = 1;                                        // это телепорт
}

$quest_id6_pokemon = item_isset(29,1); // Тут должен быть покемон, а не итем.
$loc = $mysqli->query("SELECT `search_tipe` FROM `base_location` WHERE `id` = '".$location_id."' ")->fetch_assoc(); //Ивент, др игры
$locationID = $mysqli->query("SELECT `location`,`status`,`botID`,`weight`,`bagType`,`lvl`,`user_group`,`military_key` FROM `users` WHERE `id`='".$_SESSION['id']."'")->fetch_assoc();
$typePokemon = $mysqli->query('
                SELECT
                  `up`.`id`,
                  `bp`.`type`
                FROM `user_pokemons` AS `up`
                INNER JOIN `base_pokemons` AS `bp`
                  ON `bp`.`id` = `up`.`basenum`
                WHERE
                    `up`.`user_id` = '.intval($_SESSION['id']).' AND
                    `up`.`active` = 1 AND (`bp`.`type` = "fire" OR `bp`.`type_two` = "fire")
            ')->fetch_assoc();
if(rand(1,100) > 20){
	if($locationID['botID'] != 0){
		$locationList = $mysqli->query('SELECT `id` FROM `base_location` WHERE `id` != 0 AND `region` = 1 ORDER BY RAND() LIMIT 1')->fetch_assoc();
		$mysqli->query('UPDATE `users` SET `location` = '.$locationList['id'].' WHERE `id` = '.$locationID['botID']);
	}
}
$it_cave = $mysqli->query("SELECT * FROM `ivent_cave` WHERE `user` = ".$_SESSION['id'])->fetch_assoc();
if($location_id == 88 && $locationID['military_key'] == 0){
	$response['error'] = 1;
	$response['text'] = 'У вас нет ключа от заповедника!';
}else if($location_id == 10 && !quest_step(1,6)){
	$response['error'] = 1;
	$response['text'] = 'Необходимо закончить квест <b>Начало путешествия</b>!';
//}else if($location_id == 19 && !item_isset(8,1)){
   // $response['error'] = 1;
  //	$response['text'] = 'Необходимо иметь в инвентаре <b>Велосипед</b>!';
}
// else if($location_id == 36 && !item_isset(7,1)){
//     $response['error'] = 1;
//   	$response['text'] = 'Необходимо иметь в инвентаре <b>Горнолыжное снаряжение</b>!';
// }
// else if($location_id == 44 && $timeday == 4 || $location_id == 44 && $timeday == 1){
//     $response['error'] = 1;
//   	$response['text'] = 'Невозможно попасть в локацию. Электростанция закрыта. Она открыта лишь утром и днем.';
// }
//else if($location_id == 48 && !item_isset(9,1)){
    //$response['error'] = 1;
  	//$response['text'] = 'Необходимо иметь в инвентаре <b>Набор шахтера</b>!';
//}else if($location_id == 57 && !item_isset(9,1)){
    //$response['error'] = 1;
  	//$response['text'] = 'Необходимо иметь в инвентаре <b>Набор шахтера</б>!';
//}else if($location_id == 9 and $locationID['lvl'] < 10){
    //$response['error'] = 1;
  	//$response['text'] = 'Для прохождения в порт нужен <b>10</b> уровень!';
//}else if($location_id == 89 and $locationID['id'] == 4){
   // $response['error'] = 1;
  //	$response['text'] = 'Колизей на реконструкции!';
//}

else if($location_id == 3 and $locationID['lvl'] < 1 and $telep == 1){
    $response['error'] = 1;
  	$response['text'] = 'Для телепортации нужен <b>5</b> уровень!';
}

else if($location_id == 78){
    if(quest_isset(42) and (date("H:i") > "06:00" and date("H:i") < "15:59")){
        $response['error'] = 1;
  	$response['text'] = 'Заперто. Попробую вернуться позже';
    }elseif(!quest_isset(42)){
        $response['error'] = 1;
  	$response['text'] = 'Заперто. Попробую вернуться позже';
    }else{
        $mysqli->query("UPDATE `users` SET `location`='79' WHERE `id`='".$_SESSION['id']."'");
    }

}
// --- Проход к локации 13 закрыт, пока не завершён квест 51 (шаг 13). Делаем жёсткую проверку с ранним выходом.
else if ($location_id == 13 && !quest_step(51, 13)) {
    $response['error'] = 1;
    $response['text']  = 'Абамасноу перекрыл дорогу, дальше не пройти.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// elseif($location_id == 37 и empty($typePokemon['type'])){
// 	$response['error'] = 1;
// 	$response['text'] = 'Слишком темно! Может быть стоит попробовать взять с собой Огненного покемона?';
// }else if($location_id == 105 || $location_id == 106 || $location_id == 107 || $location_id == 111 || $location_id == 103){
// 	if(!npc_time_check(60)){
// 		$ra = rand(1,10);
// 	if($ra <= 7){
// 		$ran = rand(1,3);
// 		if($ran == 1){
// 			$mysqli->query("UPDATE `users` SET `location`='104' WHERE `id`='".$_SESSION['id']."'");
// 		}elseif($ran == 2){
// 			$mysqli->query("UPDATE `users` SET `location`='109' WHERE `id`='".$_SESSION['id']."'");
// 		}elseif($ran == 3){
// 			$mysqli->query("UPDATE `users` SET `location`='110' WHERE `id`='".$_SESSION['id']."'");
// 		}
// 	}else{
// 		$rand = rand(1,3);
// 		if($rand == 1){
// 			$mysqli->query("UPDATE `users` SET `location`='108' WHERE `id`='".$_SESSION['id']."'");
// 		}else{
// 			$mysqli->query("UPDATE `users` SET `location`='112' WHERE `id`='".$_SESSION['id']."'");
// 		}
// 	}
// 	}else{
// 		$response['text'] = 'Невозможно попасть в локацию!';
// 	}
// 	$response['action'] = 'updateLocation';
// }else if($location_id == 101){
// 	$response['error'] = 1;
// 	$response['text'] = 'Злой ученый не пускает вас туда!';
// }else if($location_id == 999){
// 	if ($_SESSION['id'] != 5 && $_SESSION['id'] != 101){
// 	$response['error'] = 1;
// 	$response['text'] = 'Вы не Администратор!';
// 	}
//
// }else if($location_id == 202 && $timeday == 4 || $location_id == 202 && $timeday == 1){
//   if($locationID['location'] != 203) {
//     $response['error'] = 1;
//   	$response['text'] = 'Невозможно попасть в локацию. Мастерская закрыта. Она открыта лишь утром и днем.';
//   }
// }else if($location_id == 199){
//   if(item_isset(93,1)){
//     $rand = rand(1,100);
//     if($rand <= 2) {
//       minus_item(93,1);
//       $response['error'] = 1;
//   		$response['text'] = 'Пытаясь спуститься вниз, ваша веревка оборвалась. Вниз вы так и не спустились.';
//     }
//   }else{
//     $response['error'] = 1;
//   	$response['text'] = 'Невозможно попасть в локацию. Необходима прочная веревка.';
//   }
// }elseif($location_id == 309){
//   $usil = $mysqli->query("SELECT * FROM `bafs` WHERE `type` = '4' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
//   if(isset($usil) && $usil['time'] > time()) {
//
//   }else{
//     $response['error'] = 1;
//     $response['text'] = 'Злой голодный Снорлакс не пускает вас туда.';
//   }
// }else if($location_id == 193 && !item_isset(132,1) && info_quest(6,'step') <= 16){
// 	$response['error'] = 1;
// 	$response['text'] = 'У вас нет Огненного ключа';
// }else if($location_id == 218 && info_quest(12,'step') <= 3){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию.';
// }else if($location_id == 222 и info_quest(11,'step') <= 1){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию.';
// }else if($location_id == 193 && item_isset(132,1) && info_quest(6,'step') <= 16){
// 	$rand = rand(1,3);
// 	if($rand != 1){
// 		$response['error'] = 1;
// 		$response['text'] = 'Ключ сломался. Необходим еще один.';
// 		minus_item(132,1);
// 	}else{
// 		quest_update(6,17);
// 		update_zap(6,17,'Дверь в закрытую комнату открыта. Что же там внутри меня ждет?');
// 		$response['error'] = 0;
// 		$mysqli->query("UPDATE `users` SET `location`='".$location_id."' WHERE `id`='".$_SESSION['id']."'");
// 		$mysqli->query("DELETE FROM `items_users` WHERE `item_id` = '132' AND `user` = '".$_SESSION['id']."'");
// 	}
// }else if($location_id == 115 && $locationID['location'] == 51){
// 	if(item_isset(134,1)){
// 		$mysqli->query("UPDATE `users` SET `location`='".$location_id."' WHERE `id`='".$_SESSION['id']."'");
// 		minus_item(134,1);
// 		$response['error'] = 0;
// 	}else{
// 		$response['error'] = 1;
// 		$response['text'] = 'Невозможно попасть в локацию. У вас нет пропуска.';
// 	}
// }else if($location_id == 74 и info_quest(6,'step') >= 1 и info_quest(6,'step') <= 4){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию. Проход завален камнями.';
// }else if($location_id == 206 и info_quest(10,'step') < 1){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию. Незачем идти туда.';
// }else if($location_id == 207 и !quest_step(10,3)){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию. Незачем идти туда.';
// }else if($location_id == 89){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию. Турнир Кубок Морских Глубин еще не начался.';
// }else if($location_id == 6 и info_quest(1,'step') < 1){
// 	$response['error'] = 1;
// 	$response['text'] = 'Поговорите с Профессором Оланом.';
// }else if($location_id == 5 и info_quest(1,'step') < 3){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию.';
// }else if($location_id == 72 и !quest_isset(6)){
// 	$response['error'] = 1;
// 	$response['text'] = 'Из-за любопытства к руинам, уходить дальше пока что нет желания.';
// }else if($location_id == 72 и quest_step(6,1)){
// 	$mysqli->query("UPDATE `users` SET `location`='73' WHERE `id`='".$_SESSION['id']."'");
// 	$response['action'] = 'updateLocation';
// 	update_zap(6,2,'Лестница сломалась, я упал вниз. Было больно. Надо выбираться отсюда.');
// 	quest_update(6,2);
// }else if($location_id == 87){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию. В праздничные дни Игровой центр закрыт.';
// }else if($location_id == 79 и info_quest(6,'step') <= 4){
// 	$response['error'] = 1;
// 	$response['text'] = 'Невозможно попасть в локацию.';
// }
	if($locationID['status'] != 'free' || $locationID['user_group'] == 8) {
    $response['error'] = 1;
    $response['text'] = 'В данный момент вы не можете передвигаться!';
    die(json_encode($response));
}

$response['error'] = 0;

// Получаем список разрешённых путей для текущей локации
// Получаем список разрешённых путей для текущей локации
$getUserLocationInfo = $mysqli->query("SELECT `roads` FROM `loc_to` WHERE `loc_id` = '".(int)$locationID['location']."'")->fetch_assoc();

// Безопасный decode: если записи нет или JSON битый — считаем, что дорог нет
$getUserLocationInfo1 = [];
if (!empty($getUserLocationInfo) && isset($getUserLocationInfo['roads'])) {
    $tmp = json_decode($getUserLocationInfo['roads'], true);
    if (is_array($tmp)) $getUserLocationInfo1 = $tmp;
}
// Проверяем, можно ли перейти в новую локацию или используется телепорт
if(in_array($location_id, $getUserLocationInfo1) || $telep == 1){
    // --- Очистка RB-команды при смене локации ---
    rb_cleanup_team_on_location_change($_SESSION['id'], $location_id);

    // Обновляем локацию пользователя
    $mysqli->query("UPDATE `users` SET `location` = '".$location_id."' WHERE `id` = '".$_SESSION['id']."'");

    // Для телепорта на арену — отдадим явный сигнал фронту обновить мир
    if ($telep == 1 && $location_id == 8009) {
        $response['action'] = 'updateLocation';
        $response['text']   = 'Телепортация на арену выполнена.';
    }
}

echo json_encode($response);
?>

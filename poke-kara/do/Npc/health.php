<?
$sum = 0;
$pokList = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `active` = 1 AND `user_id` = '".$_SESSION['id']."'");
while($poks = $pokList->fetch_assoc()){
$poksA = explode(',',$poks['attacks']);
$poksPP = explode(',',$poks['pp_attacks']);
$stats = explode(',',$poks['stats']);
if($poksA[0]) {	$pokList1 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[0]."")->fetch_assoc();	$pokList1 = $pokList1['pp'];	}else{$pokList1 = '0';}
if($poksA[1]) {	$pokList2 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[1]."")->fetch_assoc();	$pokList2 = $pokList2['pp'];	}else{$pokList2 = '0';}
if($poksA[2]) {	$pokList3 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[2]."")->fetch_assoc();	$pokList3 = $pokList3['pp'];	}else{$pokList3 = '0';}
if($poksA[3]) {	$pokList4 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[3]."")->fetch_assoc();	$pokList4 = $pokList4['pp'];	}else{$pokList4 = '0';}
$sum = $sum+($pokList1-$poksPP[0])+($pokList2-$poksPP[1])+($pokList3-$poksPP[2])+($pokList4-$poksPP[3])+($stats[0]-$poks['hp']);
}

	$response['name'] = 'Сестра Джой';
	switch($npcStep){
		#Лечение покемонов
		case 1:
			while($poks = $pokList->fetch_assoc()){
				$update = $mysqli->query("UPDATE `user_pokemons` SET `hp` = '".$stats[0]."' WHERE `id` = '".$poks['id']."'");
				$update1 = $mysqli->query("UPDATE `user_pokemons` SET `pp_attacks` = '".$pokList1.",".$pokList2.",".$pokList3.",".$pokList4.",' WHERE `id` = '".$poks['id']."'");
			}
			if($sum == 0){
				$response['question'] = 'Ваши покемоны полностью здоровы!';
}else{
				$response['question'] = 'Лечение всех ваших покемонов составит '.$sum.' монет.';
				$response['answer'] = array(
					4 => "Лечить всех",
				);
			}
			break;
		#Питомник покемонов
		case 2:
			$response['question'] = '{{new}}';
			$response['type'] = 'nursery';
		break;
		#Питомник покемонов
		case 3:
			$response['question'] = '{{new}}';
			$response['type'] = 'reproduction';
		break;
		case 4:
		if(item_isset(1,$sum)){
			$pokList = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `active` = 1 AND `user_id` = '".$_SESSION['id']."'");
			while($poks = $pokList->fetch_assoc()){
				$poksA = explode(',',$poks['attacks']);
				$poksPP = explode(',',$poks['pp_attacks']);
				$stats = explode(',',$poks['stats']);
				if($poksA[0]) {	$pokList1 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[0]."")->fetch_assoc();	$pokList1 = $pokList1['pp'];	}else{$pokList1 = '0';}
				if($poksA[1]) {	$pokList2 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[1]."")->fetch_assoc();	$pokList2 = $pokList2['pp'];	}else{$pokList2 = '0';}
				if($poksA[2]) {	$pokList3 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[2]."")->fetch_assoc();	$pokList3 = $pokList3['pp'];	}else{$pokList3 = '0';}
				if($poksA[3]) {	$pokList4 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[3]."")->fetch_assoc();	$pokList4 = $pokList4['pp'];	}else{$pokList4 = '0';}
				$update = $mysqli->query("UPDATE `user_pokemons` SET `hp` = '".$stats[0]."' WHERE `id` = '".$poks['id']."'");
				$update1 = $mysqli->query("UPDATE `user_pokemons` SET `pp_attacks` = '".$pokList1.",".$pokList2.",".$pokList3.",".$pokList4.",' WHERE `id` = '".$poks['id']."'");
			}
			$response['question'] = 'Ваши покемоны вылечены!';
$response['actionQuestMinus'] = '<img src="img/world/items/little/1.png" class="item"> Монета ('.$sum.' шт.)';
			minus_item(1,$sum);
		}else{
			$response['question'] = 'У вас недостаточно монет!';
		}
		break;
		default:
			$qStory = null;
			if (class_exists('JoyStoryHook')) {
				$qStory = JoyStoryHook::defaultQuestion($mysqli);
			}
			if ($qStory) {
				$response['question'] = $qStory;
			} else {
				$response['question'] = 'Здравствуй, тренер! Добро пожаловать в наш Покецентр. Я могу чем-нибудь помочь?';
			}
			if (!isset($response['answer'])) {
				$response['answer'] = array(
					1 => "Вылечите моих покемонов",
					2 => "Я хочу получить доступ к питомнику",
					3 => "Я хочу развести покемонов"
				);
			}
		break;
}

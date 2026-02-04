<?
$sum = 0;
$us = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
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

// Helper: count pokemons for storyline checks (1101/1102)
if (!function_exists('_cntUserPokesJoy')) {
	function _cntUserPokesJoy($mysqli, $userId) {
		$res = $mysqli->query("SELECT COUNT(*) AS c FROM `user_pokemons` WHERE `user_id`=".(int)$userId);
		$row = $res ? $res->fetch_assoc() : null;
		return (int)($row['c'] ?? 0);
	}
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
$response["question"] = "Ваши покемоны полностью здоровы!";
$response["actionHeal"] = 1;
if (class_exists("JoyStoryHook")) { JoyStoryHook::afterHeal(); }
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
		    if($us['status'] == "free"){
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
$response['actionQuestMinus'] = '<img src="img/world/items/little/1.png" class="item"> Монета <b>x'.$sum.'</b>';
        			$response['actionHeal'] = 1;
					if (class_exists('JoyStoryHook')) { JoyStoryHook::afterHeal(); }
					// Сюжетный квест 1102: завершить после лечения.
					// Если по каким-то причинам 1102 не был создан (например, игрок не вернулся к профессору), создаём его здесь.
					if (class_exists('QuestKit')) {
						$uid = (int)($_SESSION['id'] ?? 0);
					$shouldStory = QuestKit::exists(1102)
						|| (function_exists('quest_step') && quest_step(1, 7, 1))
						|| (QuestKit::exists(1101) && QuestKit::end(1101) == 1);
						if ($shouldStory) {
							if (!QuestKit::exists(1102)) {
								QuestKit::set(1102, 1, 0);
								if (function_exists('update_zap')) {
									update_zap(1102, 1, 'Вылечи команду у сестры Джой (меню «Лечение»), чтобы завершить обучение.');
								}
							}
							if (QuestKit::end(1102) == 0) {
								QuestKit::set(1102, 2, 1);
								if (function_exists('update_zap')) {
									update_zap(1102, 2, 'Я вылечил команду в Покецентре. Теперь можно помогать офицеру Дженни.');
								}
								if (!QuestKit::exists(1103)) {
									QuestKit::set(1103, 1, 0);
									if (function_exists('update_zap')) {
										update_zap(1103, 1, 'Офицер Дженни ищет помощника: нужно разобраться с подозрительными людьми на окраине города.');
									}
								}
								$response['actionQuest'] = 'Задание <b>Покецентр: первая помощь</b> выполнено. Доступно новое задание: <b>Офицер Дженни: подозрительные воры</b>.';
							}
						}
					}

        			minus_item(1,$sum);
        			update_achiv(28,$sum);
        			if(check_mission(6)){ add_mission(6,$sum);}
        		}else{
        			$response['question'] = 'У вас недостаточно монет!';
        		}
		    }else{
        		$response['question'] = 'В данный момент вы заняты!';
        	}
		
		break;
		default:
			// Сюжетная интеграция: выдаём/показываем 1102 у Джой (чтобы квест не зависел от возврата к профессору).
			if (class_exists('QuestKit')) {
				$uid = (int)($_SESSION['id'] ?? 0);
				// Если 1101 активен и по факту уже выполнен — закрываем и выдаём 1102
				if (QuestKit::exists(1101) && QuestKit::end(1101) == 0) {
					$baseline = (int)QuestKit::dataGet(1101, 'baseline_pokes', 0);
					if ($baseline <= 0) {
						$baseline = _cntUserPokesJoy($mysqli, $uid);
						QuestKit::set(1101, 1, 0, null, ['baseline_pokes' => $baseline]);
					}
					$current = _cntUserPokesJoy($mysqli, $uid);
					if ($current > $baseline) {
						QuestKit::set(1101, 2, 1);
						if (function_exists('update_zap')) {
							update_zap(1101, 2, 'Я поймал своего первого дикого покемона. Пора зайти в Покецентр и вылечить команду.');
						}
						if (!QuestKit::exists(1102)) {
							QuestKit::set(1102, 1, 0);
							if (function_exists('update_zap')) {
								update_zap(1102, 1, 'Вылечи команду у сестры Джой (меню «Лечение»), чтобы завершить обучение.');
							}
						}
					}
				}

				// Если игрок завершил стартовый квест (1), но 1102 ещё не выдан — выдаём его здесь.
				// Это делает "квест лечения" независимым от обязательного возврата к профессору.
				if (!QuestKit::exists(1102) && function_exists('quest_step') && quest_step(1, 7, 1)) {
					QuestKit::set(1102, 1, 0);
					if (function_exists('update_zap')) {
						update_zap(1102, 1, 'Зайди в Покецентр к сестре Джой и вылечи команду (меню «Лечение»), чтобы завершить обучение.');
					}
				}

				if (QuestKit::exists(1102) && QuestKit::end(1102) == 0 && QuestKit::step(1102) == 1) {
					$response['question'] = 'Здравствуй, тренер! Профессор попросил научить тебя базовой заботе о команде. Выбери «Лечение» и вылечи покемонов — так ты завершишь задание.';
					break;
				}
			}
			$response['question'] = 'Здравствуй, тренер! Добро пожаловать в наш Покецентр. Я могу чем-нибудь помочь?';
		break;
	}

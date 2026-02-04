<?

	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	
	if(isset($_POST['postArgument'])) {
			
		$patch_project = $_SERVER['DOCUMENT_ROOT'];
		$patch_global = $patch_project.'/inc/conf/global.php';
		if(!empty($patch_global)){
			if(!file_exists($patch_global)){
				die('The problem with the connection files.');
			}else{
				require_once($patch_global);
			}
		}
		
		$stmt = $mysqli->prepare("SELECT user_group FROM users WHERE hash = ?");
                            $stmt->bind_param("s", $_COOKIE['hash']);
                            $stmt->execute();
                            $data = $stmt->get_result();
                            $userInfo = $data->fetch_assoc();
		
		if($userInfo["user_group"] != 1 || !isset($userInfo)) {
			
			$response['text'] = 'УХАДИ! АТАШЁЛ!';
			$response['error'] = 1;
		
			die(json_encode($response));
		
		}
		
		
		$parse = explode("|", $_POST['postArgument']);
		
		if($parse[0] == "user") {
			
			$stmt = $mysqli->prepare("UPDATE `users` SET `status` = ?, status_id = ? WHERE `login` = ?");
			$status = "free";
			$sId = 0;
                            $stmt->bind_param("sis", $status, $sId, $parse[1]);
                            $stmt->execute();
			
			$response['text'] = "".$parse[1]." освобождён!";
			
		} else if($parse[0] == "notify") {
			
			$stmt = $mysqli->prepare("INSERT INTO adminNotify (date,text,author) VALUES (?,?,?)");
			$time = time();
                            $stmt->bind_param("isi", $time, $parse[1], $_SESSION["id"]);
                            $stmt->execute();
			
			$response['text'] = "".$parse[1]." - успех!";
			
		} else if($parse[0] == "item") {
			
			$users = $mysqli->query("SELECT * FROM users");
			
			while($user = $users->fetch_assoc()) {
				
				itemAdd(intval($parse[1]), intval($parse[2]), $user["id"]);
				
			}
			
			$response['text'] = "".$parse[1]." - выдан всем!";
			
		} else {
			
			$response['text'] = 'Неверный запрос!';
			$response['error'] = 1;
			
		}
		
		die(json_encode($response));
			
	}

	$response['name'] = 'Лакей Админа';
	
	$stmt = $mysqli->prepare("SELECT user_group FROM users WHERE hash = ?");
                            $stmt->bind_param("s", $_COOKIE['hash']);
                            $stmt->execute();
                            $data = $stmt->get_result();
                            $userInfo = $data->fetch_assoc();
	
	if($userInfo["user_group"] != 1) {
		
		$response['question'] = 'УХАДИ! АТАШЁЛ!';
		
	} else {
	
		if($npcStep == "false") {
			
			$response['question'] = 'Хозяин!';
				
			$response['answer'] = array(
				1 => "Глобальное уведомление",
				2 => "Сократить срок всех яиц",
				3 => "Выдать всем предмет",
				4 => "Вытащить тренера из боя",
				6 => "Просмотр заявок на создание клана",
			);
			
		} else if($npcStep == 1) {
			
			$response['question'] = 'Введите текст уведомления(notify|ваш текст): <form class="evolNpcForm" onsubmit="evolutionPok('.$matsukaNpcId.', true);return false;""><input id="pokID" value="notify|текст"><input class="mn-btn" type="submit" value="Выбрать"></form>';
			
		} else if($npcStep == 2) {
			
			$response['question'] = 'Уверены, что хотите сократить всем срок яиц?';
			
			$response['answer'] = array(
			
				5 => "Yes"
			
			);
			
		} else if($npcStep == 3) {
		
			$base_items = $mysqli->query("SELECT * FROM base_items ORDER by name");
			
			$options = "";
			
			while($baseItem = $base_items->fetch_assoc()) {
				
				$options .= '<option value="'.$baseItem['id'].'">ID '.$baseItem["id"].' - '.$baseItem["name"].'</option>';
				
			}
			
			$response['question'] = "";
			
			$response['question'] .= '<br>* Выбрать предмет(item|Айди|Количество) *
											<form class="evolNpcForm" onsubmit="evolutionPok('.$matsukaNpcId.', true);return false;"">
											<select>'.$options.'
											</select>
											<input id="pokID" value="item|1|10">
												<input class="mn-btn" type="submit" value="Выдать">
											</form>';
		
		} else if($npcStep == 4) {
			
			$response['question'] = 'Введите ник(user|ник): <form class="evolNpcForm" onsubmit="evolutionPok('.$matsukaNpcId.', true);return false;""><input id="pokID" value="user|ник"><input class="mn-btn" type="submit" value="Выбрать"></form>';
			
		} else if($npcStep == 5) {
			
			$eggs = $mysqli->query("SELECT * FROM user_egg");
			
			while($egg = $eggs->fetch_assoc()) {
				
				$reborn = floor(($egg['reborn'] - time())/2);
				$newReborn = time() + $reborn;
				$stmt = $mysqli->prepare('UPDATE user_egg SET reborn = ? WHERE id = ?');
                            $stmt->bind_param("ii", $newReborn, $egg["id"]);
                            $stmt->execute();
				
			}
			
			$response['question'] = 'Успех!';
			
		} else if($npcStep == 6) {
			
			$response['question'] = 'Заявки на создание клана:';
			
			$clans = $mysqli->query("SELECT * FROM `clan_app`");
			$nextClanId = $mysqli->query("SELECT MAX(id) as id FROM base_clans")->fetch_assoc()["id"] + 1;
			
			$response['question'] = 'Следующий клан создастся с id <b>'.$nextClanId.'</b> (именно таким должен быть файл изображения эмблемы).<br/><br/>Заявки на создание клана:';
			
			while($clan = $clans->fetch_assoc()) {
				
				$stmt = $mysqli->prepare("SELECT `login`,`user_group` FROM `users` WHERE `id` = ?");
                            $stmt->bind_param("i", $clan['user']);
                            $stmt->execute();
                            $data = $stmt->get_result();
                            $user = $data->fetch_assoc();
				
				$response['question'] .= '<br><br>Клан <b>'.$clan['name'].'</b> от тренера <div class="user-link u-'.$user['user_group'].'">'.$user['login'].'</div> (id '.$clan['user'].'). <a href="'.$clan['emblem'].'" target="_blank">Ссылка на эмблему</a>. <br><a onclick="PP.clans.clanGo(0, '.$clan['id'].')">Одобрить</a> или <a onclick="PP.clans.clanGo(1, '.$clan['id'].')">Отклонить</a>';
				
			}
			
		}
		
	}

?>
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

/* ====== Attack Presets (user, per pokemon id) ====== */
if(!function_exists('pa_presetsEnsureTable')){
    function pa_presetsEnsureTable($mysqli){
        // Try to create table if possible, but don't hard-fail if DDL is запрещён.
        @mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS `user_attack_presets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `pok_id` int(11) NOT NULL DEFAULT 0,
            `name` varchar(40) NOT NULL DEFAULT '',
            `attacks` varchar(80) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `user_pok` (`user_id`,`pok_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Validate доступность таблицы простым SELECT (без SHOW TABLES, т.к. иногда режется правами).
        $r = @mysqli_query($mysqli, "SELECT 1 FROM `user_attack_presets` LIMIT 1");
        return ($r !== false);
    }
}

if(!function_exists('pa_presetsParseAttacks')){
    function pa_presetsParseAttacks($str){
        $parts = explode(',', (string)$str);
        $out = array(0,0,0,0);
        for($i=0; $i<4; $i++){
            $out[$i] = isset($parts[$i]) ? intval($parts[$i]) : 0;
            if($out[$i] < 0) $out[$i] = 0;
        }
        return $out;
    }
}

if(!function_exists('pa_presetsList')){
    function pa_presetsList($mysqli, $userID, $pokID, $limit = 50){
        $list = array();
        $userID = (int)$userID;
        $pokID = (int)$pokID;
        $limit = (int)$limit;
        if($userID <= 0 || $pokID <= 0) return $list;
        if($limit <= 0) $limit = 50;
        if($limit > 200) $limit = 200;

        if(!pa_presetsEnsureTable($mysqli)) return $list;

        $tmp = array();
        $needAtk = array();

        $res = mysqli_query($mysqli, "SELECT `id`,`name`,`attacks`,`created_at` FROM `user_attack_presets` WHERE `user_id` = '".$userID."' AND `pok_id` = '".$pokID."' ORDER BY `id` DESC LIMIT ".$limit);
        if($res){
            while($p = mysqli_fetch_assoc($res)){
                $atts = pa_presetsParseAttacks(isset($p['attacks']) ? $p['attacks'] : '');

                foreach($atts as $aid){
                    $aid = (int)$aid;
                    if($aid > 0) $needAtk[$aid] = 1;
                }

                $tmp[] = array(
                    'id' => (int)$p['id'],
                    'name' => (string)$p['name'],
                    'attacks' => $atts,
                    'created_at' => (int)$p['created_at']
                );
            }
            mysqli_free_result($res);
        }

        // Подтягиваем названия атак, чтобы в UI не показывать цифры
        $atkMap = array();
        if(!empty($needAtk)){
            $ids = array_keys($needAtk);
            // safety: ограничим, чтобы не делать огромный IN
            if(count($ids) > 500) $ids = array_slice($ids, 0, 500);
            $ids = array_map('intval', $ids);
            $in = implode(',', $ids);

            $r2 = mysqli_query($mysqli, "SELECT `id`,`name`,`name_rus` FROM `base_atk` WHERE `id` IN (".$in.")");
            if($r2){
                while($a = mysqli_fetch_assoc($r2)){
                    $atkMap[(int)$a['id']] = array(
                        'name' => isset($a['name']) ? (string)$a['name'] : '',
                        'name_rus' => isset($a['name_rus']) ? (string)$a['name_rus'] : ''
                    );
                }
                mysqli_free_result($r2);
            }
        }

        $attack_lang = $_SESSION['attack_lang'] ?? 'rus';
        foreach($tmp as $p){
            $moves = array();
            foreach($p['attacks'] as $aid){
                $aid = (int)$aid;
                if($aid <= 0){
                    $moves[] = '—';
                    continue;
                }
                if(isset($atkMap[$aid])){
                    if($attack_lang === 'eng'){
                        $nm = $atkMap[$aid]['name'];
                        if($nm === '' && $atkMap[$aid]['name_rus'] !== '') $nm = $atkMap[$aid]['name_rus'];
                        $moves[] = ($nm !== '' ? $nm : ('#'.$aid));
                    }else{
                        $nm = $atkMap[$aid]['name_rus'];
                        if($nm === '' && $atkMap[$aid]['name'] !== '') $nm = $atkMap[$aid]['name'];
                        $moves[] = ($nm !== '' ? $nm : ('#'.$aid));
                    }
                }else{
                    $moves[] = '#'.$aid;
                }
            }

            $list[] = array(
                'id' => (int)$p['id'],
                'name' => (string)$p['name'],
                'attacks' => $p['attacks'],
                'moves' => $moves,
                'preview' => implode(' / ', $moves),
                'created_at' => (int)$p['created_at']
            );
        }

        return $list;
}
}

$type = $_POST["type"];
$pokemonID = clearInt($_POST['pokID']);
$pokemonID = $mysqli->real_escape_string($pokemonID);
$user = $mysqli->query("SELECT `status` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
if($user['status'] != 'free' && $type != 'attackInfo' && $type != 'presetList' && $type != 'presetSave' && $type != 'applyPreset' && $type != 'presetDelete'){
	$response['text'] = 'В данный момент вы заняты!';
	$response['error'] = 1;
}else{
	switch ($type) {
		case 'addEV':
    $stat = intval($_POST['stat']);
    $count = intval($_POST['count']);
    $pokemonID = intval($_POST['pokID']);
    
    $response['error'] = 1;
    
    // Валидация входных данных
    if ($count <= 0 || $count > 126) {
        $response['text'] = 'Количество EV должно быть от 1 до 126!';
    } elseif ($stat < 0 || $stat > 5) {
        $response['text'] = 'Неверный номер стата!';
    } else {
        // Получаем покемона
        $pokemon = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokemonID."' AND `active` = '1' AND `user_id` = '".$_SESSION['id']."'")->fetch_assoc();
        
        if (empty($pokemon['id'])) {
            $response['text'] = 'Покемон не найден!';
        } else {
            // Парсим текущие EV по статам
            $evcounts = explode(',', $pokemon['evcounts']);
            
            // Проверяем что массив содержит 6 элементов
            if (count($evcounts) !== 6) {
                $evcounts = [0, 0, 0, 0, 0, 0];
            }
            
            // Приводим к числам
            for ($i = 0; $i < 6; $i++) {
                $evcounts[$i] = intval($evcounts[$i]);
            }
            
            $currentStatEV = $evcounts[$stat];
            $availableEV = intval($pokemon['ev']);
            
            // Проверки
            if ($availableEV < $count) {
                $response['text'] = 'Недостаточно доступных EV! У вас: ' . $availableEV . ', требуется: ' . $count;
            } elseif ($currentStatEV >= 126) {
                $response['text'] = 'В этом стате уже максимальное количество EV (126)!';
            } elseif ($currentStatEV + $count > 126) {
                $maxCanAdd = 126 - $currentStatEV;
                $response['text'] = 'Превышен лимит! Максимум можно добавить в этот стат: ' . $maxCanAdd . ' EV';
            } else {
                // Добавляем EV к выбранному стату
                $evcounts[$stat] += $count;
                
                // Формируем строку для БД
                $evCountsString = implode(',', $evcounts);
                
                // Вычисляем оставшиеся общие EV
                $evRemaining = $availableEV - $count;
                
                // Обновляем в БД
                $updateQuery = "UPDATE `user_pokemons` SET `evcounts` = '".$evCountsString."', `ev` = '".$evRemaining."' WHERE `id` = '".$pokemonID."'";
                
                if ($mysqli->query($updateQuery)) {
                    $statNames = ['HP', 'Атаку', 'Защиту', 'Скорость', 'Спец.Атаку', 'Спец.Защиту'];
                    
                    $response['error'] = 0;
                    $response['text'] = 'Добавлено ' . $count . ' EV в ' . $statNames[$stat] . '! Стат: ' . $evcounts[$stat] . '/126';
                    $response['ev'] = $evRemaining;
                    $response['newStatEV'] = $evcounts[$stat];
                    $response['stat'] = $stat;
                } else {
                    $response['text'] = 'Ошибка при сохранении в базу данных!';
                }
            }
        }
    }
    break;
		
case 'deletePok':
    $response['error'] = 1;

    // Принимаем и старое, и новое имя параметра
    $pokemonID = isset($_POST['pokemonID']) ? intval($_POST['pokemonID']) :
                 (isset($_POST['pokID'])     ? intval($_POST['pokID'])     : 0);

    $category  = intval($_POST['category'] ?? 1); // 1 — выбранный, иначе — остальных

    // Сколько активных покемонов у пользователя
    $activeSet = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `active` = 1 AND `user_id` = '".$_SESSION['id']."'");
    if ($activeSet->num_rows < 2) {
        $response['text'] = 'В команде должен оставаться 1 покемон!';
        break;
    }

    // Проверяем выбранного
    $pokemon = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokemonID."' AND `active` = 1 AND `user_id` = '".$_SESSION['id']."'")->fetch_assoc();
    if (empty($pokemon['id'])) {
        $response['text'] = 'Ошибка в выборе покемона!';
        break;
    }

    // Время «заморозки» после выпуска
    $t = time() + 3600*24*30;

    if ($category === 1) {
        // --- Отпустить ТОЛЬКО выбранного ---
        if (intval($pokemon['startGame']) === 1) {
            $response['text'] = 'Стартового покемона отпустить нельзя!';
            break;
        }

        // Вернуть предмет, если был
        if (intval($pokemon['item_id']) > 0) {
            if (empty($pokemon['item_str'])) {
                itemAdd($pokemon['item_id'], 1);
            } else {
                $mysqli->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$_SESSION['id']."','".$pokemon['item_id']."',1,'".$mysqli->real_escape_string($pokemon['item_str'])."')");
            }
        }

        // Выпускаем выбранного
        $mysqli->query("UPDATE `user_pokemons`
                        SET `user_id` = 2,
                            `item_id` = 0,
                            `item_str` = '',
                            `active` = 0,
                            `del_pok` = '".$_SESSION['id']."',
                            `del_time` = '".$t."'
                        WHERE `id` = '".$pokemonID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");

        $response['error']   = 0;
        $response['text']    = 'Покемон успешно отпущен!';
        $response['pokId']   = numbPok($pokemon['basenum']);
        $response['pokName'] = '#'.numbPok($pokemon['basenum']).' '.$pokemon['name_new'];

    } else {
        // --- Отпустить ОСТАЛЬНЫХ (id != выбранного), исключая стартового ---
        $others = $mysqli->query("
            SELECT `id`, `item_id`, `item_str`
            FROM `user_pokemons`
            WHERE `id` != '".$pokemonID."'
              AND `user_id` = '".$_SESSION['id']."'
              AND `active` = 1
              AND `startGame` != 1
        ");

        if ($others && $others->num_rows > 0) {
            // Вернуть предметы со всех остальных
            while ($row = $others->fetch_assoc()) {
                if (intval($row['item_id']) > 0) {
                    if (empty($row['item_str'])) {
                        itemAdd($row['item_id'], 1);
                    } else {
                        $mysqli->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$_SESSION['id']."','".$row['item_id']."',1,'".$mysqli->real_escape_string($row['item_str'])."')");
                    }
                }
            }

            // Выпускаем остальных (кроме стартового)
            $mysqli->query("UPDATE `user_pokemons`
                            SET `user_id` = 2,
                                `item_id` = 0,
                                `item_str` = '',
                                `active` = 0,
                                `del_pok` = '".$_SESSION['id']."',
                                `del_time` = '".$t."'
                            WHERE `id` != '".$pokemonID."'
                              AND `user_id` = '".$_SESSION['id']."'
                              AND `active` = 1
                              AND `startGame` != 1");

            $response['error'] = 0;
            $response['text']  = 'Покемоны успешно отпущены!';
        } else {
            $response['text'] = 'Нет покемонов для отпуска!';
        }
    }
break;


		case 'attackInfo':
    $baseInfo = $mysqli->query('SELECT * FROM `base_atk` WHERE `id` = '.$pokemonID)->fetch_assoc();

    // Получаем язык атак пользователя (например, из сессии или профиля)
    $attack_lang = $_SESSION['attack_lang'] ?? 'rus';

    // Название атаки по языку
    if ($attack_lang === 'eng') {
        $response['name'] = $baseInfo['name'];
    } else {
        $response['name'] = $baseInfo['name_rus'];
    }

    $response['nameRus'] = $baseInfo['name_rus'];
    $response['nameEng'] = $baseInfo['name'];
    $response['pp'] = $baseInfo['pp'];
    $response['title'] = $baseInfo['title'];
    $response['type'] = $baseInfo['type'];
    $response['accuracy'] = $baseInfo['accuracy'];
    $response['power'] = $baseInfo['power'];
    $response['tech'] = $baseInfo['tech'];

    // Контакт на языке пользователя
    if ($baseInfo['contact'] == 1) {
        $response['contact'] = ($attack_lang === 'eng') ? ', contact' : ', контакт';
    } else {
        $response['contact'] = '';
    }

    // Категория атаки на языке пользователя
    if ($baseInfo['category'] == 'physical') {
        $response['category'] = ($attack_lang === 'eng') ? 'Physical' : 'Физическая';
    } elseif ($baseInfo['category'] == 'special') {
        $response['category'] = ($attack_lang === 'eng') ? 'Special' : 'Специальная';
    } elseif ($baseInfo['category'] == 'status') {
        $response['category'] = ($attack_lang === 'eng') ? 'Status' : 'Статусная';
    } else {
        $response['category'] = ($attack_lang === 'eng') ? 'Other' : 'Специфическая';
    }
break;
		case 'putPok': {
    $response = ['error' => 1];

    $userId    = (int)($_SESSION['id'] ?? 0);
    $pokemonID = (int)($_POST['pokemonID'] ?? ($pokemonID ?? 0));
    $category  = (int)($_POST['category'] ?? 1);

    if ($userId <= 0 || $pokemonID <= 0) {
        $response['text'] = 'Ошибка в параметрах запроса!';
        break;
    }

    $allowedLocations = ['Покецентр', 'Поле для тренировок', 'Турнирная арена', 'Стадион', 'Коллизей'];

    // Определяем локацию пользователя
    $stmt = $mysqli->prepare("
        SELECT bl.name
        FROM users u
        JOIN base_location bl ON bl.id = u.location
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($locationName);
    $stmt->fetch();
    $stmt->close();

    if (empty($locationName) || !in_array($locationName, $allowedLocations, true)) {
        $response['text'] = 'На данной локации нету питомников!';
        break;
    }

    // Считаем активных покемонов
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM user_pokemons WHERE active = 1 AND user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($activeCount);
    $stmt->fetch();
    $stmt->close();

    // Получаем выбранного активного покемона
    $stmt = $mysqli->prepare("
        SELECT id
        FROM user_pokemons
        WHERE id = ? AND active = 1 AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $pokemonID, $userId);
    $stmt->execute();
    $stmt->bind_result($pokeId);
    $stmt->fetch();
    $stmt->close();

    if (empty($pokeId)) {
        $response['text'] = 'Ошибка в выборе покемона!';
        break;
    }

    $mysqli->begin_transaction();

    try {
        if ($category === 1) {
            // Отправить в питомник ВЫБРАННОГО покемона
            if ($activeCount <= 1) {
                // Если выбранный — единственный активный, активируем другого из неактивных
                $stmt = $mysqli->prepare("
                    SELECT id
                    FROM user_pokemons
                    WHERE user_id = ? AND active = 0
                    ORDER BY start_pok DESC, id DESC
                    LIMIT 1
                ");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->bind_result($replacementId);
                $stmt->fetch();
                $stmt->close();

                if (empty($replacementId)) {
                    throw new Exception('В команде должен оставаться 1 покемон!');
                }

                $stmt = $mysqli->prepare("
                    UPDATE user_pokemons
                    SET active = 1, start_pok = 1
                    WHERE id = ? AND user_id = ? AND active = 0
                    LIMIT 1
                ");
                $stmt->bind_param('ii', $replacementId, $userId);
                $stmt->execute();
                $stmt->close();

                $stmt = $mysqli->prepare("
                    UPDATE user_pokemons
                    SET start_pok = 0
                    WHERE user_id = ? AND id <> ?
                ");
                $stmt->bind_param('ii', $userId, $replacementId);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $mysqli->prepare("
                UPDATE user_pokemons
                SET active = 0, start_pok = 0
                WHERE id = ? AND user_id = ? AND active = 1
                LIMIT 1
            ");
            $stmt->bind_param('ii', $pokemonID, $userId);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();
            $response['error'] = 0;
            $response['text']  = 'Покемон успешно перемещен в питомник!';
        } else {
            // Отправить в питомник ВСЕХ, КРОМЕ выбранного
            $stmt = $mysqli->prepare("
                UPDATE user_pokemons
                SET active = 0, start_pok = 0
                WHERE user_id = ? AND active = 1 AND id <> ?
            ");
            $stmt->bind_param('ii', $userId, $pokemonID);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare("
                UPDATE user_pokemons
                SET start_pok = 1
                WHERE id = ? AND user_id = ? AND active = 1
                LIMIT 1
            ");
            $stmt->bind_param('ii', $pokemonID, $userId);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare("
                UPDATE user_pokemons
                SET start_pok = 0
                WHERE user_id = ? AND active = 1 AND id <> ?
            ");
            $stmt->bind_param('ii', $userId, $pokemonID);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();
            $response['error'] = 0;
            $response['text']  = 'Покемоны успешно перемещены в питомник!';
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        $response['text'] = $e->getMessage();
    }

    break;
}

		case 'setStart':
			$response['error'] = 1;
				$pokemon = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `id` = '".$pokemonID."' AND `active` = '1' AND `user_id` = '".$_SESSION['id']."'")->fetch_assoc();
				if(empty($pokemon['id'])){
					$response['text'] = 'Ошибка в выборе покемона!';
				}else{
					$response['error'] = 0;
					$response['text'] = 'Стартовый покемон успешно выбран!';
					$mysqli->query("UPDATE `user_pokemons` SET `start_pok` = 1 WHERE `id` = '".$pokemonID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
					$mysqli->query("UPDATE `user_pokemons` SET `start_pok` = 0 WHERE `start_pok` > 0 AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 AND `id` != '".$pokemonID."'");
				}

		break;
		case 'wentPok':
			$response['error'] = 1;
			$pokemon = $mysqli->query("SELECT `happy`,`lastWent` FROM `user_pokemons` WHERE `active` = '1' AND `user_id` = '".$_SESSION['id']."' AND `id` = '".$pokemonID."'")->fetch_assoc();
			if(achiv_utility(33)){
								        $happy = 20;
								    }else{
								        $happy = 10;
								    }
			if($pokemon['happy'] == 255){
				$response['text'] = 'Ваш покемон самый счастливый';
			}else{
				$happyAll = $pokemon['happy']+$happy;
				if($happyAll > 255){
					$happyAll = 255;
				}
				$rand = rand(3,10);
        if(time() > $pokemon['lastWent']){
          sleep($rand);
          $lastWent = time()+3600*12;
          $mysqli->query("UPDATE `user_pokemons` SET `happy` = '".$happyAll."', `lastWent` = '".$lastWent."' WHERE `id` = '".$pokemonID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
          $response['error'] = 0;
          $response['text'] = 'Прогулка понравилась покемону, он повысил свое счастье.';
          if(check_mission(27)){ add_mission(27);}
          if(check_mission_ivent(12)){ add_mission_ivent(12);}
          update_achiv(33,1);
          week_mission('walk');
        }else{
          $response['text'] = 'Покемон может гулять лишь один раз в 12 часов.';
        }
			}
		break;
		case 'renamePok':
			$name = clearStr($_POST['name']);
			$name = $mysqli->real_escape_string($name);
      $pokemon = $mysqli->query("SELECT `basenum` FROM `user_pokemons` WHERE `id` = '".$pokemonID."' AND `active` = '1' AND `user_id` = '".$_SESSION['id']."'")->fetch_assoc();
      if(empty($pokemon['id'])){
        $response['error'] = 0;
  			$response['text'] = 'Покемон успешно переименован!';
  			$response['name'] = '#'.numbPok($pokemon['basenum']).' '.$name;
  			$mysqli->query("UPDATE `user_pokemons` SET `name_new` = '".$name."' WHERE `id` = '".$pokemonID."'");
      }else{
        $response['text'] = 'Ошибка в выборе покемона!';
      }
		break;
		case 'open':
    $pokemon = $mysqli->query("SELECT `basenum`,`form` FROM `user_pokemons` WHERE `id` = '".$pokemonID."' AND `active` = '1' AND `user_id` = '".$_SESSION['id']."'")->fetch_assoc();
            $atkNumb = intval($_POST['attackID']);

            if($atkNumb >= 0){
                // if($pokemon['form'] == 0 or $pokemon['form'] == ""){
                //     $pokemon = $mysqli->query('
                //     SELECT
                //         up.basenum,
                //         up.lvl,
                //         bap.attacks,
                //         bap.lvl a_lvl
                //     FROM user_pokemons up
                //     LEFT JOIN base_attacks_pokemons bap
                //       ON bap.pok = up.basenum
                //     WHERE
                //       bap.type = "lvl" AND bap.form = "'.$pokemon['form'].'"  AND
                //       up.id = '.intval($pokemonID).' AND
                //       up.active = 1 AND
                //       up.user_id = '.intval($_SESSION['id']).'
                // ')->fetch_assoc();
                // }else{
                    $pokemon = $mysqli->query('
                    SELECT
                        up.basenum,
                        up.lvl,
                        bap.attacks,
                        bap.lvl a_lvl
                    FROM user_pokemons up
                    LEFT JOIN base_attacks_pokemons bap
                      ON bap.pok = up.basenum
                    WHERE
                      bap.type = "lvl" AND bap.form = "'.$pokemon['form'].'"  AND
                      up.id = '.intval($pokemonID).' AND
                      up.active = 1 AND
                      up.user_id = '.intval($_SESSION['id']).'
                ')->fetch_assoc();


                if(empty($pokemon)){
                    $pokemon = $mysqli->query('
                    SELECT
                        up.basenum,
                        up.lvl,
                        bap.attacks,
                        bap.lvl a_lvl
                    FROM user_pokemons up
                    LEFT JOIN base_attacks_pokemons bap
                      ON bap.pok = up.basenum
                    WHERE
                      bap.type = "lvl" AND bap.form = "0"  AND
                      up.id = '.intval($pokemonID).' AND
                      up.active = 1 AND
                      up.user_id = '.intval($_SESSION['id']).'
                ')->fetch_assoc();
                }
                // }
                //         $batk_p = $mysqli->query('SELECT * FROM `base_attacks_pokemons` WHERE `pok` = "'.$pokemon['basenum'].'" AND `type` = "lvl" AND `form` = "'.$pokemon['form'].'"')->fetch_assoc();
                //         if($batk_p){


                //       bap.type = "lvl" AND bap.form = "" AND


				$pokTM = $mysqli->query('
									SELECT
									`attacks`
									FROM `user_pokemons_tm`
									WHERE `pok` = '.intval($pokemonID));
				if(!empty($pokemon)){
                    $attacks = explode(',', $pokemon['attacks']);
                    $lvl = explode(',', $pokemon['a_lvl']);

                    $countAtk = sizeof($attacks);

                    $atkListID = [];
                    $baseAtkList = [];

                    for($i=0; $i<=$countAtk; $i++){

                        if(isset($attacks[$i], $lvl[$i])){

                            if($lvl[$i] > $pokemon['lvl']){
                                continue;
                            }

                            $atkListID[] = intval($attacks[$i]);
                        }

                    }

					if(!empty($pokTM)){
						while($atkTM = $pokTM->fetch_assoc()){
							$atkListID[] = intval($atkTM['attacks']);
						}
					}
                    if(!empty($atkListID)){
    $atkB = [];
    // Получаем язык атак пользователя
    $attack_lang = $_SESSION['attack_lang'] ?? 'rus';
    $atk_name_col = ($attack_lang === 'eng') ? 'name' : 'name_rus';

    $baseAtk = $mysqli->query('SELECT * FROM base_atk WHERE id IN ('.implode(',', $atkListID).')');
    while($row = $baseAtk->fetch_assoc()){ $atkB[] = $row; }
    if(!empty($atkB)){
        foreach($atkB AS $key=>$value){
            // Используем нужное поле для названия атаки
            $baseAtkList[$value['id']] = $value[$atk_name_col].','.$pokemonID.','.$atkNumb.','.$value['type'].','.$value['pp'].','.$value['category'].','.$value['id'];
        }

                            //unset($key, $value);
                        }

                    }

                    $response['error'] = 0;
                    $response['text'] = $baseAtkList;
                    $response['attacks'] = $baseAtkList;
                }
            }
		break;
		case 'add':
			$positionAtk = $_POST['positionAtk'];
			$pokemon = $mysqli->query("SELECT `basenum`,`attacks`,`pp_attacks`,`form` FROM `user_pokemons` WHERE `id` = '".$pokemonID."' AND `active` = '1' AND `user_id` = '".$_SESSION['id']."'")->fetch_assoc();
			$atkNumb = $_POST['attackID'];
			$atkNumb = $mysqli->real_escape_string($atkNumb);

        $pok = $pokemon['basenum'];
			$atkList = $mysqli->query("SELECT `attacks` FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' and `type` = 'lvl' and `form` = '".$pokemon['form']."' ")->fetch_assoc();
			if(empty($atkList)){
			$atkList = $mysqli->query("SELECT `attacks` FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' and `type` = 'lvl' and `form` = '0' ")->fetch_assoc();
			}
			$tmList = $mysqli->query("SELECT `id` FROM `user_pokemons_tm` WHERE `pok` = '".$pokemonID."' AND `attacks` = '".$atkNumb."'")->fetch_assoc();
			$attacks = explode(',',$atkList['attacks']);
			if(in_array($atkNumb,$attacks) || !empty($tmList['id'])){
				$pokAtk = explode(',',$pokemon['attacks']);
				if(in_array($atkNumb,$pokAtk)){
					$response['error'] = 1;
					$response['text'] = 'Данная атака уже изучена покемоном!';
				}else{
					$pokPPAtk = explode(',',$pokemon['pp_attacks']);
					$pokAtk[$positionAtk] = $atkNumb;
					$pokPPAtk[$positionAtk] = 0;
					$implodeAtk = implode(',',$pokAtk);
					$implodePPAtk = implode(',',$pokPPAtk);


					$mysqli->query("UPDATE `user_pokemons` SET `attacks` = '".$implodeAtk."', `pp_attacks` = '".$implodePPAtk."' WHERE `id` = '".$pokemonID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
					$response['error'] = 0;
					$response['text'] = 'Атака успешно изучена!';
				}
			}else{
				$response['error'] = 1;
				$response['text'] = 'Ошибка';
			}
		break;
		
		case 'presetList':
			$response['error'] = 0;

			$pokID = clearInt($_POST['pokID']);
			if($pokID <= 0){
				$response['error'] = 1;
				$response['text'] = 'Покемон не выбран!';
				break;
			}

			$own = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = '1' LIMIT 1")->fetch_assoc();
			if(empty($own['id'])){
				$response['error'] = 1;
				$response['text'] = 'Покемон не найден!';
				break;
			}

			$response['presets'] = pa_presetsList($mysqli, $_SESSION['id'], $pokID);
		break;

		case 'presetSave':
			$response['error'] = 1;

			$pokID = clearInt($_POST['pokID']);
			if($pokID <= 0){
				$response['text'] = 'Покемон не выбран!';
				break;
			}

			if(!pa_presetsEnsureTable($mysqli)){
				$response['text'] = 'Таблица пресетов недоступна.';
				break;
			}

			$name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
			$name = preg_replace('/\s+/u', ' ', $name);
			$name = trim($name);
			if($name == ''){
				$response['text'] = 'Введите название пресета!';
				break;
			}
			if(mb_strlen($name, 'UTF-8') > 40){
				$name = mb_substr($name, 0, 40, 'UTF-8');
			}

			$pokemon = $mysqli->query("SELECT `attacks` FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
			if(empty($pokemon)){
				$response['text'] = 'Покемон не найден!';
				break;
			}

			$att = trim((string)$pokemon['attacks']);
			if($att === ''){
				$response['text'] = 'Нет атак для сохранения!';
				break;
			}

			// лимит пресетов на покемона (старый удаляем)
			$limit_max = 25;
			$cntRow = $mysqli->query("SELECT COUNT(*) c FROM `user_attack_presets` WHERE `user_id` = '".intval($_SESSION['id'])."' AND `pok_id` = '".intval($pokID)."'")->fetch_assoc();
			$cnt = !empty($cntRow['c']) ? intval($cntRow['c']) : 0;
			if($cnt >= $limit_max){
				$old = $mysqli->query("SELECT `id` FROM `user_attack_presets` WHERE `user_id` = '".intval($_SESSION['id'])."' AND `pok_id` = '".intval($pokID)."' ORDER BY `id` ASC LIMIT 1")->fetch_assoc();
				if(!empty($old['id'])){
					$mysqli->query("DELETE FROM `user_attack_presets` WHERE `id` = '".intval($old['id'])."' AND `user_id` = '".intval($_SESSION['id'])."' AND `pok_id` = '".intval($pokID)."' LIMIT 1");
				}
			}

			$mysqli->query("INSERT INTO `user_attack_presets` (`user_id`,`pok_id`,`name`,`attacks`,`created_at`) VALUES ('".intval($_SESSION['id'])."','".intval($pokID)."','".$mysqli->real_escape_string($name)."','".$mysqli->real_escape_string($att)."','".time()."')");
			$response['error'] = 0;
			$response['text'] = 'Пресет сохранён!';
			$response['presets'] = pa_presetsList($mysqli, $_SESSION['id'], $pokID);
		break;

		case 'applyPreset':
			$response['error'] = 1;

			$pokID = clearInt($_POST['pokID']);
			if($pokID <= 0){
				$response['text'] = 'Покемон не выбран!';
				break;
			}

			$key = isset($_POST['preset']) ? trim((string)$_POST['preset']) : '';
			if($key == ''){
				$response['text'] = 'Не выбран пресет!';
				break;
			}
			// поддержка u_ и p_ (на будущее)
			if(strpos($key, 'u_') === 0){
				$pid = intval(substr($key, 2));
			}else if(strpos($key, 'p_') === 0){
				$pid = intval(substr($key, 2));
			}else{
				$pid = intval($key);
			}
			if($pid <= 0){
				$response['text'] = 'Не выбран пресет!';
				break;
			}

			if(!pa_presetsEnsureTable($mysqli)){
				$response['text'] = 'Таблица пресетов недоступна.';
				break;
			}

			$preset = $mysqli->query("SELECT `attacks`,`name` FROM `user_attack_presets` WHERE `id` = '".$pid."' AND `user_id` = '".intval($_SESSION['id'])."' AND `pok_id` = '".intval($pokID)."' LIMIT 1")->fetch_assoc();
			if(empty($preset)){
				$response['text'] = 'Пресет не найден!';
				break;
			}

			$want = pa_presetsParseAttacks($preset['attacks']);
			$newAtt = implode(',', $want);

			$r = $mysqli->query("UPDATE `user_pokemons` SET `attacks` = '".$mysqli->real_escape_string($newAtt)."', `pp_attacks` = '0,0,0,0' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
			if($r){
				$response['error'] = 0;
				$response['text'] = 'Пресет применён!';
				$response['attacks'] = $want;
			}else{
				$response['text'] = 'Ошибка применения пресета!';
			}
		break;

		case 'presetDelete':
			$response['error'] = 1;

			$pokID = clearInt($_POST['pokID']);
			if($pokID <= 0){
				$response['text'] = 'Покемон не выбран!';
				break;
			}

			$key = isset($_POST['preset']) ? trim((string)$_POST['preset']) : '';
			if($key == ''){
				$response['text'] = 'Не выбран пресет!';
				break;
			}
			if(strpos($key, 'u_') === 0){
				$pid = intval(substr($key, 2));
			}else if(strpos($key, 'p_') === 0){
				$pid = intval(substr($key, 2));
			}else{
				$pid = intval($key);
			}
			if($pid <= 0){
				$response['text'] = 'Не выбран пресет!';
				break;
			}

			if(!pa_presetsEnsureTable($mysqli)){
				$response['text'] = 'Таблица пресетов недоступна.';
				break;
			}

			$ex = $mysqli->query("SELECT `id` FROM `user_attack_presets` WHERE `id` = '".$pid."' AND `user_id` = '".intval($_SESSION['id'])."' AND `pok_id` = '".intval($pokID)."' LIMIT 1")->fetch_assoc();
			if(empty($ex)){
				$response['text'] = 'Пресет не найден!';
				break;
			}

			$mysqli->query("DELETE FROM `user_attack_presets` WHERE `id` = '".$pid."' AND `user_id` = '".intval($_SESSION['id'])."' AND `pok_id` = '".intval($pokID)."' LIMIT 1");
			$response['error'] = 0;
			$response['text'] = 'Пресет удалён!';
			$response['presets'] = pa_presetsList($mysqli, $_SESSION['id'], $pokID);
		break;

default:
			echo 'Error';
		break;
	}
}
echo json_encode($response);

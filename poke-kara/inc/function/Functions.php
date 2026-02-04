<?php
function _runtime_cache($group, $key = null, $value = null, $mode = 'get'){
	static $cache = array();
	if(!isset($cache[$group])){
		$cache[$group] = array();
	}
	if($mode == 'get'){
		if($key === null){
			return $cache[$group];
		}
		return isset($cache[$group][$key]) ? $cache[$group][$key] : null;
	}elseif($mode == 'set'){
		$cache[$group][$key] = $value;
		return $value;
	}elseif($mode == 'has'){
		return isset($cache[$group][$key]);
	}elseif($mode == 'del'){
		if($key === null){
			$cache[$group] = array();
		}else{
			unset($cache[$group][$key]);
		}
		return true;
	}
	return null;
}

function week_mission($type){
    $server = Work::$sql->query('SELECT * FROM `system` WHERE id = 1')->fetch_assoc();
    if($server['week'] == 1){
        $ivent = Work::$sql->query('SELECT * FROM `a_ivent_week_arheolog` WHERE user = '.$_SESSION['id'])->fetch_assoc();
    }elseif($server['week'] == 2){
        $ivent = Work::$sql->query('SELECT * FROM `a_ivent_week_labirint` WHERE user = '.$_SESSION['id'])->fetch_assoc();
    }elseif($server['week'] == 3){
        $ivent = Work::$sql->query('SELECT * FROM `a_ivent_week_playhome` WHERE user = '.$_SESSION['id'])->fetch_assoc();
    }else{
        return false;
    }
    if($ivent){
        $mission = Work::$sql->query('SELECT * FROM `a_ivent_week_mission` WHERE user = '.$_SESSION['id'])->fetch_assoc();
        if($mission[$type] >= 1){
            $upd = $mission[$type]-1;
            if($upd != 0){
                Work::$sql->query('UPDATE `a_ivent_week_mission` SET `'.$type.'` = "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                if($server['week'] == 3){
                    $updat = $ivent['ticket']+1;
                    Work::$sql->query('UPDATE `a_ivent_week_playhome` SET `ticket`= "'.$updat.'" WHERE `user` = '.$_SESSION['id']);
                }
            }else{
                Work::$sql->query('UPDATE `a_ivent_week_mission` SET `'.$type.'` = "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                if($server['week'] == 1){
                    $upd2 = $ivent['shovel']+4;
                    Work::$sql->query('UPDATE `a_ivent_week_arheolog` SET `shovel`= "'.$upd2.'" WHERE `user` = '.$_SESSION['id']);
                }elseif($server['week'] == 2){
                    $upd2 = $ivent['ticket']+1;
                    Work::$sql->query('UPDATE `a_ivent_week_labirint` SET `ticket`= "'.$upd2.'" WHERE `user` = '.$_SESSION['id']);
                }elseif($server['week'] == 3){
                    $upd2 = $ivent['ticket']+3;
                    Work::$sql->query('UPDATE `a_ivent_week_playhome` SET `ticket`= "'.$upd2.'" WHERE `user` = '.$_SESSION['id']);
                }
            }
        }
    }
}
function new_arheolog_map($s){
    Work::$sql->query("DELETE FROM `a_ivent_week_arheolog_prize` WHERE `user` = '".$_SESSION['id']."' ");
    $a = rand(1,24);
    $arc[] = $a;
    $i = 0;
    while(true){
        $r = rand(1,24);
        if(!in_array($r, $arc)){
            $i++;
            $arc[] = $r;
        }else{
            continue;
        }
    if($i == 12) break;
    }
    $bv = $arc[6].','.$arc[1].','.$arc[2].','.$arc[3].','.$arc[4].','.$arc[5].','.$arc[7].','.$arc[8].','.$arc[9].','.$arc[10];                                    
    Work::$sql->query('UPDATE `a_ivent_week_arheolog` SET `empty`= "'.$bv.'",`map` = "'.$s.'",`key` = "'.$a.'",`block` = "",`open` = "" WHERE `user` = '.$_SESSION['id']);
    
}
/**
 * Улучшенная версия news_friend:
 * - Безопасные запросы (prepared statements)
 * - Нормальная маршрутизация по типам, без дублирования кода
 * - Экранирование HTML (XSS-safe)
 * - Аккуратные кэстинги типов
 * - Возвращает boolean (успех вставки) вместо молчаливого поведения
 *
 * Совместимость:
 * - Сигнатура функции сохранена: news_friend($type, $id, $us = false)
 * - Поддержка функций numbPok, update_achiv, user_to_chat_add (как и раньше)
 */
function news_friend($type, $id, $us = false) {
    $db = Work::$sql ?? null;
    if (!$db) return false;

    $uid = $us ? (int)$us : (int)($_SESSION['id'] ?? 0);
    if ($uid <= 0) return false;

    $type = (int)$type;
    $id   = (int)$id;

    // Получаем пол пользователя
    $user = null;
    if ($stmt = $db->prepare('SELECT id, sex FROM users WHERE id = ? LIMIT 1')) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$user) return false;

    $sexKey = (($user['sex'] ?? '') === 'f') ? 'f' : 'm';

    // Словарь глаголов по полу
    $verbs = [
        'm' => [1=>'выбил',  2=>'поймал',  3=>'обновил',  4=>'добавил',  5=>'получил',  6=>'изучил'],
        'f' => [1=>'выбила', 2=>'поймала', 3=>'обновила', 4=>'добавила', 5=>'получила', 6=>'изучила'],
    ];
    $verb = $verbs[$sexKey][$type] ?? ($sexKey === 'f' ? 'сделала' : 'сделал');

    // Эскапер HTML
    $h = function($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $text = '';

    switch ($type) {
        case 1: // предмет
            if ($stmt = $db->prepare('SELECT id, name FROM base_items WHERE id = ? LIMIT 1')) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $iid   = (int)$row['id'];
                    $iname = $h($row['name']);
                    $text  = $verb.' <img src="/img/world/items/little/'.$iid.'.png" onclick="issetAll('.$iid.')"> '.$iname;
                }
            }
            break;

        case 2: // покемон
            if ($stmt = $db->prepare('SELECT id, name_rus FROM base_pokemons WHERE id = ? LIMIT 1')) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $pid   = (int)$row['id'];
                    $pnum  = numbPok($pid);
                    $pname = $h($row['name_rus']);
                    $text  = $verb.' <span onclick="openDex('.$pid.')"><img src="/img/pokemons/animation/'.$pnum.'.png"> #'.$pnum.' '.$pname.'</span>';
                }
            }
            break;

        case 3: // статус обновлён (здесь $id = текст статуса в исходной логике)
            // В старой реализации в $id приходил текст, поэтому учитываем это поведение:
            $statusText = $h((string)($_POST['status_text'] ?? $id)); // если хотите, передавайте отдельно через POST
            $text = $verb.' статус на <b>'.$statusText.'</b>';
            break;

        case 4: // добавлен друг
            if (function_exists('update_achiv')) update_achiv(32, 1, $uid);

            if ($stmt = $db->prepare('SELECT id, user_group, login FROM users WHERE id = ? LIMIT 1')) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $fid    = (int)$row['id'];
                    $fgrp   = (int)$row['user_group'];
                    $flogin = $h($row['login']);
                    $text   = $verb.' в друзья <div class="user-link"><div class="u-'.$fgrp.' label" onclick="user_to_chat_add(\''.$fid.'\')">'.$flogin.'</div></div>';
                }
            }
            break;

        case 5: // достижение
            if ($stmt = $db->prepare('SELECT name FROM base_achievements WHERE id = ? LIMIT 1')) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $aname = $h($row['name']);
                    $text  = $verb.' достижение <span class="noclick">'.$aname.'</span> ';
                }
            }
            break;

        case 6: // изучена атака
            $lang = $_SESSION['attack_lang'] ?? 'rus';
            $col  = ($lang === 'eng') ? 'name' : 'name_rus';

            // Колонку нельзя параметризовать, но мы её бел-списком выбрали выше
            if ($stmt = $db->prepare('SELECT `'.$col.'` AS n FROM base_atk WHERE id = ? LIMIT 1')) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $aname = $h($row['n']);
                    $text  = $verb.' атаку <span class="noclick">'.$aname.'</span> ';
                }
            }
            break;
    }

    if ($text === '') return false;

    // Вставляем запись в ленту друзей
    $ok = false;
    if ($stmt = $db->prepare('INSERT INTO friends_news (`user`, `text`, `date`) VALUES (?, ?, ?)')) {
        $now = time();
        $stmt->bind_param('isi', $uid, $text, $now);
        $ok = $stmt->execute();
        $stmt->close();
    }

    return $ok;
}

// Обновление прогресса миссии Battle Pass
function battle_pass_mission_upd($id, $count) {
    $user_id = intval($_SESSION['id']);
    $id = intval($id);
    $count = intval($count);

    // Получаем миссию пользователя
    $bd = Work::$sql->query("SELECT * FROM `aa_battle_pass_mission` WHERE `user` = $user_id AND `mission_id` = $id")->fetch_assoc();
    if ($bd) {
        // Для миссий с прогрессом
        if ($id == 1 || $id == 2) {
            $upd = intval($bd['mission_count']) + $count;
            if ($upd >= intval($bd['mission_count_max'])) {
                // Награда, удаление миссии, новая миссия
                battlepass_exp($bd['mission_exp'], $id);
                Work::$sql->query("DELETE FROM `aa_battle_pass_mission` WHERE `id` = '{$bd['id']}' AND `user` = $user_id LIMIT 1");
                battle_pass_mission_new();
            } else {
                // Увеличение прогресса
                Work::$sql->query("UPDATE `aa_battle_pass_mission` SET `mission_count` = $upd WHERE `id` = '{$bd['id']}'");
            }
        }
        // Для миссий разовых (сдать что-то)
        elseif ($id == 3 || $id == 4) {
            battlepass_exp($bd['mission_exp'], $id);
            Work::$sql->query("DELETE FROM `aa_battle_pass_mission` WHERE `id` = '{$bd['id']}' AND `user` = $user_id LIMIT 1");
            battle_pass_mission_new();
        }
    }
}

// Генерация новой миссии пользователю (чтобы не было дублирующихся)
function battle_pass_mission_new() {
    $user_id = intval($_SESSION['id']);

    // Не допускаем дубликатов по mission_id
    while (true) {
        $r = rand(1, 4);
        $bd = Work::$sql->query("SELECT `user`,`mission_id` FROM `aa_battle_pass_mission` WHERE `user` = $user_id AND `mission_id` = $r")->fetch_assoc();
        if ($bd) continue; else break;
    }

    if ($r == 1) {
        $r2 = rand(0, 4);
        $count = [100, 125, 150, 175, 200];
        $exp = [85, 110, 125, 155, 180];
        $text = "Победите <i>{$count[$r2]}</i> покемонов в PVE";
        Work::$sql->query("INSERT INTO `aa_battle_pass_mission`
            (`user`,`mission_text`,`mission_id`,`mission_type`,`mission_count_max`,`mission_exp`,`mission_count`)
            VALUES ($user_id, '$text', $r, $r2, {$count[$r2]}, {$exp[$r2]}, 0)");
    }
    elseif ($r == 2) {
        $r2 = rand(0, 4);
        $count = [15000, 20000, 25000, 30000, 50000];
        $exp = [75, 100, 115, 130, 250];
        $text = "Выбейте <i>{$count[$r2]}</i> генкар с диких покемонов в PVE";
        Work::$sql->query("INSERT INTO `aa_battle_pass_mission`
            (`user`,`mission_text`,`mission_id`,`mission_type`,`mission_count_max`,`mission_exp`,`mission_count`)
            VALUES ($user_id, '$text', $r, $r2, {$count[$r2]}, {$exp[$r2]}, 0)");
    }
    elseif ($r == 3) {
        $r2 = rand(1, 2);
        if ($r2 == 1) { // lvl
            $r3 = rand(0, 4);
            $count = [25, 30, 35, 40, 45];
            $exp_dop = [15, 20, 30, 50, 60];
            $type = "lvl"; $text_dop = "<i>{$count[$r3]}</i> уровня";
        } elseif ($r2 == 2) { // gender
            $r3 = rand(0, 1);
            $count = ['Мальчик', 'Девочка'];
            $exp_dop = [25, 25];
            $type = "gender"; $text_dop = "с полом <i>{$count[$r3]}</i>";
        }
        $r4 = rand(0, 20);
        $pokemon = [300,315,821,276,104,54,731,590,27,76,505,522,161,43,209,187,672,418,278,399,50];
        $pok = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = {$pokemon[$r4]}")->fetch_assoc();
        $exp = rand(60,100) + $exp_dop[$r3];
        $text = "Принесите Карлу <span class=\"intextpoke\" onclick=\"openDex({$pok['id']})\">#".numbPok($pok['id'])." {$pok['name_rus']}</span> {$text_dop}";
        $dop = "$type,{$count[$r3]}";
        Work::$sql->query("INSERT INTO `aa_battle_pass_mission`
            (`user`,`mission_text`,`mission_id`,`mission_type`,`mission_count`,`mission_exp`,`dop`)
            VALUES ($user_id, '$text', $r, $r2, {$pokemon[$r4]}, $exp, '$dop')");
    }
    elseif ($r == 4) {
        $r2 = rand(1, 2);
        if ($r2 == 1) { // har
            $r3 = rand(0, 25);
            $count = range(1, 26);
            $exp_dop = array_fill(0, 26, 15);
            $type = "har"; $text_dop = "с характером <i>".haracter_pokes($count[$r3])."</i> ";
        } elseif ($r2 == 2) { // gen
            $r3 = rand(0, 7);
            $count = [12,13,14,15,16,17,18,19];
            $exp_dop = [0,0,5,10,15,20,25,30];
            $r35 = rand(0, 5);
            $stat = ['HP','Атаки','Защиты','Скорости','Спец.Атаки','Спец.Защиты'];
            $type = "gen_$r35"; $text_dop = "с геном {$stat[$r35]} >= <i>{$count[$r3]}</i>";
        }
        $r4 = rand(0, 20);
        $pokemon = [300,315,821,276,104,54,731,590,27,76,505,522,161,43,209,187,672,418,278,399,50];
        $pok = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = {$pokemon[$r4]}")->fetch_assoc();
        $exp = rand(80,120) + $exp_dop[$r3];
        $text = "Принесите Джону <span class=\"intextpoke\" onclick=\"openDex({$pok['id']})\">#".numbPok($pok['id'])." {$pok['name_rus']}</span> {$text_dop}";
        $dop = "$type,{$count[$r3]}";
        Work::$sql->query("INSERT INTO `aa_battle_pass_mission`
            (`user`,`mission_text`,`mission_id`,`mission_type`,`mission_count`,`mission_exp`,`dop`)
            VALUES ($user_id, '$text', $r, $r2, {$pokemon[$r4]}, $exp, '$dop')");
    }
}

// Проверка наличия миссии
function battlepass_check_mission($id, $type = false) {
    $user_id = intval($_SESSION['id']);
    $id = intval($id);
    $q = "SELECT * FROM `aa_battle_pass_mission` WHERE `user` = $user_id AND `mission_id` = $id";
    if ($type !== false) {
        $type = intval($type);
        $q .= " AND `mission_type` = $type";
    }
    $bd = Work::$sql->query($q)->fetch_assoc();
    return $bd ? true : false;
}

// Выдача опыта Battle Pass (с учётом лимитов)
function battlepass_exp($exp, $mission = false) {
    $user_id = intval($_SESSION['id']);
    $exp = intval($exp);
    $use = Work::$sql->query("SELECT `lvl`,`exp_me` FROM `aa_battle_pass_user` WHERE `user` = $user_id")->fetch_assoc();
    if ($use) {
        if ($mission) {
            $mission = intval($mission);
            $mis = Work::$sql->query("SELECT * FROM `aa_battle_pass_mission_ogr` WHERE `user` = $user_id AND `mission` = $mission")->fetch_assoc();
            if ($mis) {
                $upd = $mis['count'] + 1;
                if ($upd > $mis['max']) {
                    $exp = 0;
                } else {
                    Work::$sql->query("UPDATE `aa_battle_pass_mission_ogr` SET `count` = $upd WHERE `user` = $user_id AND `mission` = $mission");
                }
            } else {
                // switch для совместимости с любой версией PHP
                switch ($mission) {
                    case 1:
                    case 2:
                        $max = 100;
                        break;
                    case 3:
                        $max = 50;
                        break;
                    case 4:
                        $max = 10;
                        break;
                    case 5:
                        $max = 20;
                        break;
                    default:
                        $max = 10;
                }
                Work::$sql->query("INSERT INTO `aa_battle_pass_mission_ogr` (`user`,`mission`,`count`,`max`) VALUES ($user_id, $mission, 1, $max)");
            }
        }
        if ($exp != 0) {
            $lvl = intval($use['lvl']);
            $exp_upd = intval($use['exp_me']) + $exp;
            if ($exp_upd >= 800) {
                $lvl++;
                $exp_upd -= 800;
                Work::$sql->query("UPDATE `aa_battle_pass_user` SET `lvl` = $lvl WHERE `user` = $user_id");
            }
            Work::$sql->query("UPDATE `aa_battle_pass_user` SET `exp_me` = $exp_upd WHERE `user` = $user_id");
        }
    }
}
function escapeMe($value){
    global $mysqli;
$pattern = [
"'", '"', '}', ']', ')', '{', '[', '(', '+', ' +', "'+", '"+',
"\x27", "\x22", "\x60", "\\t", "\\n",  "*", "<", ">", "?", "!", "\r\n", '<?', 'php',
"select", "update", "insert", "drop", "delete", "where", "\\", "`", "~", "set", "values",
"create", "database", "character", "collate", "grant", "show", "describe",
'select', 'update', 'insert', 'drop', 'delete',
'alert', 'javascript', 'alert',
'eval', 'system', 'exec', 'worldofpokemon', 'l-17', 'league17revival', 'league17reborn'
];
$value = str_ireplace($pattern, '', $value);
$value = $mysqli->real_escape_string($value);
$value = trim($value);
$value = preg_replace("/[\r\n]{3,}/i", "\r\n\r\n", $value);
$value = stripslashes($value);
return $value;
}

function escapeMeTWO($value){
    global $mysqli;
$pattern = [
"select", "update", "insert", "drop", "delete", "where",  "`", "~", "set", "values", "database", "character", "collate", "grant", "show", "describe",
'select', 'update', 'insert', 'drop', 'delete',
'alert', 'javascript', 'alert',
'eval', 'system', 'exec', 'worldofpokemon', 'l-17', 'league17revival', 'league17reborn'
];
$value = str_ireplace($pattern, '', $value);
$value = $mysqli->real_escape_string($value);
$value = trim($value);
$value = preg_replace("/[\r\n]{3,}/i", "\r\n\r\n", $value);
$value = stripslashes($value);
return $value;
}
function gen_password($length = 15){
	$password = '';
	$arr = array(
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
		'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
		'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
		'1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
	);

	for ($i = 0; $i < $length; $i++) {
		$password .= $arr[random_int(0, count($arr) - 1)];
	}
	return $password;
}
function gen_captcha($length = 6){
	$password = '';
	$arr = array(
		'1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
	);

	for ($i = 0; $i < $length; $i++) {
		$password .= $arr[random_int(0, count($arr) - 1)];
	}
	return $password;
}
function item_info($id,$type){
	$id = (int)$id;
	$key = 'item_'.$id;
	$bd = _runtime_cache('base_items', $key);
	if($bd === null){
		$bd = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$id)->fetch_assoc();
		if(!$bd){
			$bd = array();
		}
		_runtime_cache('base_items', $key, $bd, 'set');
	}
	return isset($bd[$type]) ? $bd[$type] : null;
}

function check_evol($id,$next=false){
    $bd = Work::$sql->query('SELECT * FROM user_pokemons WHERE id = '.$id)->fetch_assoc(); 
    $bd_pok = Work::$sql->query('SELECT * FROM base_pokemons WHERE id = '.$bd['basenum'])->fetch_assoc();
    if($bd_pok['evol_type'] == "lvl" and $bd_pok['evol_lvl'] <= $bd['lvl'] and isset($bd_pok['evol_basenum']) and $bd_pok['evol_lvl'] > 0){
        $a = "check";
    }else{
        $a = "no-check";
    }
    if($bd['basenum'] == 27 and $bd['form'] == "alola" and $bd['lvl'] >= 22){
        $a = "no-check";
    }
    if($bd['basenum'] == 100 and $bd['form'] == "hisuian" and $bd['lvl'] >= 30){
        $a = "no-check";
    }
    if($bd['basenum'] == 122 and $bd['form'] == "galar" and $bd['lvl'] >= 42){
        $a = "check";
    }
    if($bd['basenum'] == 222 and $bd['form'] == "galar" and $bd['lvl'] >= 38){
        $a = "check";
    }
    if($bd['basenum'] == 415 and $bd['gender'] == "Мальчик" and $bd['lvl'] >= 21){
        $a = "no-check";
    }
    if($bd['basenum'] == 562 and $bd['form'] == "galar" and $bd['lvl'] >= 34){
        $a = "no-check";
    }
    if($bd['basenum'] == 757 and $bd['gender'] == "Мальчик" and $bd['lvl'] >= 33){
        $a = "no-check";
    }
    if($bd['basenum'] == 264 and $bd['form'] != "galar"){
        $a = "no-check";
    }elseif($bd['basenum'] == 264 and $bd['form'] == "galar" and (date('H:i') >= '00:00' and date('H:i') <= '05:59')){
        $a = "check";
    }
    if($bd['basenum'] == 971 and $bd['lvl'] >= 30 and (date('H:i') <= '23:59' and date('H:i') >= '06:00')){
        $a = "no-check";
    }
    if($bd['basenum'] == 674 and $bd['lvl'] >= 34 and !pok_type_check('dark')){
        $a = "no-check";
    }
    
    
    // if(($bd_pok['type'] == "item" and $bd_pok['evol_item'] != 0) or (($bd['basenum'] == 27 and $bd['form'] == "alola") or ($bd['basenum'] == 79))){
    //     $a = "no-check";
    //     if($bd_pok['evol_type_item'] == 1){
            
    //     }else{
    //         if($bd['basenum'] == 27 and $bd['form'] == 'alola' and item_isset(90,1)){
    //             $a = "check";
    //         }
    //         if($bd['basenum'] == 37 and $bd['form'] != 'alola' and item_isset(83,1)){
    //             $a = 'check';
    //         }
    //         if($bd['basenum'] == 37 and $bd['form'] == 'alola' and item_isset(83,1)){
    //             $a = 'check';
    //         }
    //         if($bd['basenum'] == 44 and (item_isset(82,1) or item_isset(85,1))){
    //             $a = 'check';
    //         }
    //         if($bd['basenum'] == 61 and (item_isset(81,1) or item_isset(99,1))){
    //             $a = 'check';
    //         }
    //         if($bd['basenum'] == 79 and ($bd['lvl'] >= 37 or item_isset(99,1))){
    //             $a = 'check';
    //         }
    //         if($bd['basenum'] == 133 and (($bd['lvl'] >= 37) or item_isset(99,1))){
    //             $a = 'check';
    //         }
    //     }
    // }
        return $a;
}

#Функция прибавления PVE очков
function plus_learn_pve($count) {
  $user = Work::$sql->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
  $pve = json_decode($user['rating']);
  $winnerRatingUpd = '{"pve": '.($pve->pve+$count).', "pvp": '.$pve->pvp.', "battleCount": '.$pve->battleCount.'}';
  Work::$sql->query("UPDATE `users` SET `rating` = '".$winnerRatingUpd."' WHERE `id` = '".$_SESSION['id']."'");
  if(function_exists('newyear_event_add')){
    newyear_event_add((int)$_SESSION['id'], 'pve_win', (int)$count);
  }

}
#Функция отнимания PVE очков
function minus_learn_pve($count) {
  $user = Work::$sql->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
  $pve = json_decode($user['rating']);
  $winnerRatingUpd = '{"pve": '.($pve->pve-$count).', "pvp": '.$pve->pvp.', "battleCount": '.$pve->battleCount.'}';
  Work::$sql->query("UPDATE `users` SET `rating` = '".$winnerRatingUpd."' WHERE `id` = '".$_SESSION['id']."'");
}
#Функция проверки на количество PVE очков
function learn_pve() {
  $user = Work::$sql->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
  $pve = json_decode($user['rating']);
  return $pve->pve;
}
function ability_name($id){
	$id = (int)$id;
	$key = 'ability_name_rus_'.$id;
	$cached = _runtime_cache('base_ability', $key);
	if($cached !== null){
		return $cached;
	}
	$ability = Work::$sql->query('SELECT `name_rus` FROM base_ability WHERE id = '.$id)->fetch_assoc();
	$name = !empty($ability['name_rus']) ? $ability['name_rus'] : '';
	_runtime_cache('base_ability', $key, $name, 'set');
	return $name;
}

function ability_name_eng($id){
	$id = (int)$id;
	$key = 'ability_name_eng_'.$id;
	$cached = _runtime_cache('base_ability', $key);
	if($cached !== null){
		return $cached;
	}
	$ability = Work::$sql->query('SELECT `name_eng` FROM base_ability WHERE id = '.$id)->fetch_assoc();
	$name = !empty($ability['name_eng']) ? $ability['name_eng'] : '';
	_runtime_cache('base_ability', $key, $name, 'set');
	return $name;
}

function limit_pok_plus(){
    $user = Work::$sql->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
    $bafs = Work::$sql->query('SELECT * FROM bafs WHERE user = '.$_SESSION['id'].' AND baf = 448 AND time > '.time())->fetch_assoc();
    if($bafs){
        if(rand(1,100) < 40){
            $lv = $user['limit_pok']+1;
            Work::$sql->query('UPDATE `users` SET `limit_pok` = '.$lv.' WHERE `id` = '.$_SESSION['id']);
        }
    }else{
    $lv = $user['limit_pok']+1;
    Work::$sql->query('UPDATE `users` SET `limit_pok` = '.$lv.' WHERE `id` = '.$_SESSION['id']);
    }
}
function lvluser(){
  $use = Work::$sql->query('SELECT `lvl` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
  return $use['lvl'];
}

function rand_pok_last(){
    $r = rand(1,898);
    $bd = Work::$sql->query('SELECT `id`,`evol_basenum` FROM `base_pokemons` WHERE `id` = '.$r)->fetch_assoc();
    if($bd['evol_basenum'] != 0  and isset($bd)){
        $r = $bd['evol_basenum'];
        $bd2 = Work::$sql->query('SELECT `id`,`evol_basenum` FROM `base_pokemons` WHERE `id` = '.$bd['evol_basenum'])->fetch_assoc();
        if($bd2['evol_basenum'] != 0 and isset($bd2)){
            $r = $bd2['evol_basenum'];
            $bd3 = Work::$sql->query('SELECT `id`,`evol_basenum` FROM `base_pokemons` WHERE `id` = '.$bd2['evol_basenum'])->fetch_assoc();
            if($bd3['evol_basenum'] != 0 and isset($bd3)){
                $r = $bd3['evol_basenum'];
            }
        }
    }
    return $r;
}
function createLinks( $links){
    $total = $links;
    $i = 1;
    $d = " ";
    while($total >= 20){
        $d .= '<div onclick="nursery(\'page\',false,false,'.$i.');">'.$i.'</div>';
        $i++;
        $total = $total-20;
    }
    if($total > 0){
        $d .= '<div onclick="nursery(\'page\',false,false,'.$i.');">'.$i.'</div>';
    }


    return $d;
}


function lvlupuser($exp,$us=false){
    $exp = $exp;
    $bafprem = Work::$sql->query('SELECT * FROM bafs WHERE type = 3 AND user = '.$_SESSION['id'])->fetch_assoc();
          if($bafprem) {
            if($bafprem['time'] > time()) {
              $exp = $exp*2;
            }else{
              $exp = $exp;
            }
          }else{
            $exp = $exp;
          }
          if(check_mission(14)){ add_mission(14,$exp);}
  if($us){
    $user = $us;
  }else{
    $user = $_SESSION['id'];
  }
  $use = Work::$sql->query('SELECT `lvl`,`exp_lvl`,`exp_lvl_to` FROM `users` WHERE `id` = '.$user)->fetch_assoc();
  $exp_to = $use['exp_lvl_to'];
  $lvl = $use['lvl'];
  $exp_upd = $use['exp_lvl']+$exp;
  if($exp_upd >= $exp_to){
  while($exp_upd >= $exp_to) {
    $lvl = $lvl+1;
    $exp_upd = $exp_upd - $exp_to;
    $exp_to = ($lvl*($lvl/2))*30;
  }
    $exp_lvl_to_upd = ($lvl*($lvl/2))*30;
    Work::$sql->query('UPDATE `users` SET `exp_lvl_to` = '.$exp_lvl_to_upd.', `lvl` = '.$lvl.' WHERE `id` = '.$user);
  }
  Work::$sql->query('UPDATE `users` SET `exp_lvl` = '.$exp_upd.' WHERE `id` = '.$user);
}

function check_mission_ivent($id,$user=false){
    if($user){ $u = $user;}else{ $u = $_SESSION['id'];}
    $use = Work::$sql->query('SELECT * FROM `ivent_mission_check` WHERE `mission` = "'.$id.'" AND `end` = "0" AND `user` = '.$u)->fetch_assoc();
    if(!empty($use)){
        $a = true;
    }else{
        $a = false;
    }
    return $a;
}
function mission_ivent_info($id,$type){
    $use = Work::$sql->query('SELECT * FROM `ivent_mission_check` WHERE `mission` = "'.$id.'" AND `end` = "0" AND `user` = '.$_SESSION['id'])->fetch_assoc();
    if($use){
        if($type == 'int'){
            return $use['min'];
        }
        if($type == 'width'){
            $a = ($use['min']/$use['max'])*100;
            return $a;
        }
    }else{
        $a = 0;
        return $a;
    }
}
function add_mission_ivent($id,$count=false,$user=false){
    if(in_array($id, [1,4,7,10,13,16,19,22,25,28,31,34,37,40,43])){ $mis = 1;}
    elseif(in_array($id, [2,5,8,11,14,17,20,23,26,29,32,35,38,41,44])){ $mis = 2;}
    elseif(in_array($id, [3,6,9,12,15,18,21,24,27,30,33,36,39,42,45])){ $mis = 3;}
    
    if($user){ $u = $user;}else{ $u = $_SESSION['id'];}
    $ivent = Work::$sql->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$u)->fetch_assoc();
    $use = Work::$sql->query('SELECT * FROM `ivent_mission_check` WHERE `mission` = "'.$id.'" AND `end` = "0" AND `user` = '.$u)->fetch_assoc();
    if($use){
        if($count){ $c = $count; }else{ $c = 1; }
        $th = $use['min']+$c;
        if($th <= $use['max']){
            Work::$sql->query('UPDATE `ivent_mission_check` SET `min` = '.$th.' WHERE `id` = '.$use['id']);
        }
        if($th >= $use['max']){
            Work::$sql->query('UPDATE `ivent_mission_check` SET `end` = 1 WHERE `id` = '.$use['id']);
            if($ivent['mission_check'] == ""){ $t = $mis;}else{ $t = $ivent['mission_check'].','.$mis; }
            Work::$sql->query('UPDATE `ivent_birthday` SET `mission_check` = "'.$t.'" WHERE `user` = '.$u);
        }


    }
}

function ivent_check($type,$id){

    $use = Work::$sql->query('SELECT * FROM `ivent_mission_check` WHERE `mission` = "'.$id.'" AND `end` = "0" AND `user` = '.$_SESSION['id'])->fetch_assoc();
    if($type == 1){
        $t = $use['min'];
    }
    if($type == 2){
        $t = $use['min']/$use['max']*100;
    }

    return $t;
}

function check_mission($id,$user=false){
    if($user){ $u = $user;}else{ $u = $_SESSION['id'];}
    $use = Work::$sql->query('SELECT * FROM `user_mission_day` WHERE `id_mission` = "'.$id.'" AND `end` = "0" AND `user` = '.$u)->fetch_assoc();
    if(!empty($use)){
        $a = true;
    }else{
        $a = false;
    }
    return $a;
}
function add_mission($id,$count=false,$user=false){
    if($user){ $u = $user;}else{ $u = $_SESSION['id'];}
    $use = Work::$sql->query('SELECT * FROM `user_mission_day` WHERE `id_mission` = "'.$id.'" AND `end` = "0" AND `user` = '.$u)->fetch_assoc();
    if($use){
        if($count){ $c = $count; }else{ $c = 1; }
        $th = $use['this_process']+$c;
        if($th > $use['end_process']) $th = $use['end_process'];
        if($th <= $use['end_process']){
            Work::$sql->query('UPDATE `user_mission_day` SET `this_process` = '.$th.' WHERE `id` = '.$use['id']);
        }


    }
}
#Функция расчета разницы между двумя датами
function downcounter($date,$date2=false){
    $check_time = $date - time();
    if($check_time <= 0){
        return false;
    }
    $year = floor($check_time/31536000);
    $mounth = floor($check_time/2592000);
    $days = floor($check_time/86400);
    $hours = floor(($check_time%86400)/3600);
    $minutes = floor(($check_time%3600)/60);
    $seconds = $check_time%60;
    $str = '';
    if($year > 0) $str .= declension($year,array('год','года','лет')).' ';
    if($mounth > 0) $str .= declension($mounth,array('месяц','месяца','месяцев')).' ';
    if($days > 0) $str .= declension($days,array('день','дня','дней')).' ';
    if($hours > 0) $str .= declension($hours,array('час','часа','часов')).' ';
    if($minutes > 0) $str .= declension($minutes,array('минута','минуты','минут')).' ';
    if($seconds > 0) $str .= declension($seconds,array('секунда','секунды','секунд'));
    return $str;
}
function downcountermin($date,$type=false){
    $check_time = $date - time();
    if($check_time <= 0){
        $str = 'закончил';
        return $str;
    }
    $days = floor($check_time/86400);
    $hours = floor(($check_time%86400)/3600);
    $minutes = floor(($check_time%3600)/60);
    $seconds = $check_time%60;
    $str = '';
    if($type == 2){
        if($days > 0) $str .= declension($days,array('дн.','дн.','дн.')).' ';
    if($hours > 0) $str .= declension($hours,array('ч.','ч.','ч.')).' ';
    if($minutes > 0 AND $days <= 0) $str .= declension($minutes,array('мин.','мин.','мин.')).' ';
    if($seconds > 0 AND $hours <= 0) $str .= declension($seconds,array('сек.','сек.','сек.'));
    }else{
        if($days > 0) $str .= declension($days,array('день','дня','дней')).' ';
    if($hours > 0) $str .= declension($hours,array('час','часа','часов')).' ';
    if($minutes > 0 AND $days <= 0) $str .= declension($minutes,array('минута','минуты','минут')).' ';
    if($seconds > 0 AND $hours <= 0) $str .= declension($seconds,array('секунда','секунды','секунд'));
    }
    
    return $str;
}
#Функция склонения слов
function declension($digit,$expr,$onlyword=false){
    if(!is_array($expr)) $expr = array_filter(explode(' ', $expr));
    if(empty($expr[2])) $expr[2]=$expr[1];
    $i=preg_replace('/[^0-9]+/s','',$digit)%100;
    if($onlyword) $digit='';
    if($i>=5 && $i<=20) $res=$digit.' '.$expr[2];
    else{
        $i%=10;
        if($i==1) $res=$digit.' '.$expr[0];
        elseif($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
        else $res=$digit.' '.$expr[2];
    }
    return trim($res);
}
function time_game(){
  if(date("H:i") >= "04:00" AND date("H:i") <= "09:59"){
    $time = "Утро";
  }elseif(date("H:i") >= "10:00" AND date("H:i") <= "17:59"){
    $time = "День";
  }else{
    $time = "Ночь";
  }
  return $time;
}
function stats_update_form($base_stat,$pok){
  $user_pokemons = Work::$sql->query('SELECT * FROM `user_pokemons` WHERE `id` = '.$pok)->fetch_assoc();
  $har  = Work::$sql->query("SELECT * FROM `har` WHERE `id_har` = ".$user_pokemons['character'])->fetch_assoc();
  $bs = explode(',',$base_stat);
  $ev = explode(',',$user_pokemons['evcounts']);
  $gen = explode(',',$user_pokemons['gen']);
  $hp = intval(round((($bs[0] * 2) + $gen[0] + ($ev[0]/2)) * ($user_pokemons['lvl']/100) + 10 + $user_pokemons['lvl'] ));
  $atk = intval(round(((($bs[1] * 2 + $gen[1] + ($ev[1]/2)) * $user_pokemons['lvl']/100 + 5) * $har['atk'])));
  $def = intval(round(((($bs[2] * 2 + $gen[2] + ($ev[2]/2)) * $user_pokemons['lvl']/100 + 5) * $har['def'])));
  $spd = intval(round(((($bs[3]* 2 + $gen[3] + ($ev[3]/2)) * $user_pokemons['lvl']/100 + 5) * $har['speed'])));
  $satk = intval(round(((($bs[4] * 2 + $gen[4] + ($ev[4]/2)) * $user_pokemons['lvl']/100 + 5) * $har['satk'])));
  $sdef = intval(round(((($bs[5] * 2 + $gen[5] + ($ev[5]/2)) * $user_pokemons['lvl']/100 + 5) * $har['sdef'])));
  if($user_pokemons['tren_stat'] == 1){ $atk = intval(round($atk*classific($user_pokemons['tren'])));}
  if($user_pokemons['tren_stat'] == 2){ $def = intval(round($def*classific($user_pokemons['tren'])));}
  if($user_pokemons['tren_stat'] == 3){ $spd = intval(round($spd*classific($user_pokemons['tren'])));}
  if($user_pokemons['tren_stat'] == 4){ $satk = intval(round($satk*classific($user_pokemons['tren'])));}
  if($user_pokemons['tren_stat'] == 5){ $sdef = intval(round($def*classific($user_pokemons['tren'])));}
  $stat = $hp.','.$atk.','.$def.','.$spd.','.$satk.','.$sdef;
  return $stat;
}
#Функция обновления статов

function evol_pok_lvl($id, $sp = false)
{
    $bd      = Work::$sql->query('SELECT * FROM user_pokemons WHERE id = '.$id)->fetch_assoc();
    if (!$bd) return;

    $bd_pok  = Work::$sql->query('SELECT * FROM base_pokemons WHERE id = '.$bd['basenum'])->fetch_assoc();
    if (!$bd_pok) return;

    $b       = Work::$sql->query('SELECT * FROM `base_pokemons` WHERE id = '.$bd_pok['evol_basenum'])->fetch_assoc();
    $user    = Work::$sql->query('SELECT * FROM `users` WHERE id = '.$_SESSION['id'])->fetch_assoc();
    $loc     = Work::$sql->query('SELECT * FROM `base_location` WHERE id = '.$user['location'])->fetch_assoc();

    // Эволюции по уровню (и проверка на возможность эволюции)
    if ($bd_pok['evol_type'] == "lvl" && $bd_pok['evol_lvl'] <= $bd['lvl'] && check_evol($id) == "check") {

        $form = $bd['form'];          // форма по умолчанию — текущая
        $name = $bd['name_new'];      // имя по умолчанию — текущее
        $num  = null;                 // будущий basenum (заполним ниже)

        // --- Особые ветки ---

        // #052 Meowth (Galar) → #863 Perrserker (lvl 28)
        if ($bd['basenum'] == 52 && $bd['form'] == "galar" && $bd['lvl'] >= 28) {
            $num  = 863;  $name = "Перрсеркер";  $form = 0;

        // #194 Wooper (Paldea) → #980 Clodsire (lvl 20)
        } elseif ($bd['basenum'] == 194 && $bd['form'] == "paldea" && $bd['lvl'] >= 20) {
            $num  = 980;  $name = "Клодсайр";    $form = 0;

        // #236 Tyrogue → Hitmon-линия по EV (lvl 20)
        } elseif ($bd['basenum'] == 236 && $bd['lvl'] >= 20) {
            $ev = explode(',', $bd['evcounts']);
            if ($ev[1] >  $ev[2]) { $num = 106; $name = "Хитмонли";  }
            elseif ($ev[1] < $ev[2]) { $num = 107; $name = "Хитмончан"; }
            else { $num = 237; $name = "Хитмонтоп"; }

        // #122 Mr. Mime (Galar) → #866 Mr. Rime (lvl 42)
        } elseif ($bd['basenum'] == 122 && $bd['form'] == "galar" && $bd['lvl'] >= 42) {
            $num  = 866;  $name = "Мистер Райм"; $form = 0;

        // #222 Corsola (Galar) → #864 Cursola (lvl 38)
        } elseif ($bd['basenum'] == 222 && $bd['form'] == "galar" && $bd['lvl'] >= 38) {
            $num  = 864;  $name = "Курсола";     $form = 0;

        // #264 Linoone (Galar) → #862 Obstagoon (ночью)
        } elseif ($bd['basenum'] == 264 && $bd['form'] == "galar" && (date('H:i') >= '00:00' && date('H:i') <= '05:59')) {
            $num  = 862;  $name = "Обстагун";    $form = 0;

        // === ИСПРАВЛЕНО: линия Вурмпл ===
        // #265 Wurmple → 7 ур.: днём (04:00–17:59) в #266 Silcoon, иначе в #268 Cascoon
        } elseif ($bd['basenum'] == 265 && $bd['lvl'] >= 7) {
            if (date('H:i') >= '04:00' && date('H:i') <= '17:59') {
                $num  = 266;  $name = "Силкун";
            } else {
                $num  = 268;  $name = "Каскун";   // было 267 — ошибка; правильно 268
            }

        // #266 Silcoon → 10 ур. → #267 Beautifly
        } elseif ($bd['basenum'] == 266 && $bd['lvl'] >= 10) {
            $num  = 267;  $name = "Бьютифлай";

        // #268 Cascoon → 10 ур. → #269 Dustox
        } elseif ($bd['basenum'] == 268 && $bd['lvl'] >= 10) {
            $num  = 269;  $name = "Дастокс";

        // #290 Nincada → #291 Ninjask (+ #292 Shedinja при условии слота)
        } elseif ($bd['basenum'] == 290 && $bd['lvl'] >= 20) {
            $num  = 291;  $name = "Нинджаск";
            $cx = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1");
            if ($cx->num_rows <= 5) {
                newPokemon(292, $_SESSION['id'], 1, 20, 0, 'true', 0, false, false, false, false, true);
            }

        // #412 Burmy → #413 Wormadam (♀, форма по биому) / #414 Mothim (♂)
        } elseif ($bd['basenum'] == 412 && $bd['lvl'] >= 20) {
            if ($bd['gender'] == "Девочка") {
                if ($loc['search_tipe'] == 'city')     { $form = 'trash';  }
                elseif ($loc['search_tipe'] == 'mountain'){ $form = 'sandy'; }
                $num = 413; $name = "Вармадам";
            } else {
                $num = 414; $name = "Мотим";
            }

        // #744 Rockruff → #745 Lycanroc (ночью — midnight)
        } elseif ($bd['basenum'] == 744) {
            $num  = 745; $name = "Лайканрок";
            if (time_game() == "Ночь") { $form = 'midnight'; }

        // #677 Espurr → #678 Meowstic (♀ форма)
        } elseif ($bd['basenum'] == 677) {
            $num  = 678; $name = "Мяустик";
            if ($bd['gender'] == "Девочка") { $form = 'female'; }

        // #848 Toxel → #849 Toxtricity (низкий/высокий ключ по характеру)
        } elseif ($bd['basenum'] == 848) {
            $num  = 849; $name = "Токстрисити";
            if (in_array($bd['character'], [3,7,11,16,10,14,6,22,26,24,21,20])) { $form = 'lowkey'; }

        // #415 Combee (♀, lvl 21) → #416 Vespiquen
        } elseif ($bd['basenum'] == 415 && $bd['lvl'] >= 21 && $bd['gender'] == "Девочка") {
            $num  = 416; $name = "Веспиквин";

        // #674 Pancham (lvl 34 + наличие тьмы в команде) → #675 Pangoro
        } elseif ($bd['basenum'] == 674 && $bd['lvl'] >= 34 && pok_type_check('dark')) {
            $num  = 675; $name = "Пангоро";

        // #757 Salandit (♀, lvl 33) → #758 Salazzle
        } elseif ($bd['basenum'] == 757 && $bd['lvl'] >= 33 && $bd['gender'] == "Девочка") {
            $num  = 758; $name = "Салазл";

        // #790 Cosmoem → #791 Solgaleo / #792 Lunala по времени
        } elseif ($bd['basenum'] == 790 && $bd['lvl'] >= 53) {
            if (date('H:i') >= '06:00' && date('H:i') <= '17:59') { $num = 791; $name = "Солгалео"; }
            else                                                  { $num = 792; $name = "Лунала";   }

        // #915 Lechonk → #916 Oinkologne (♀ форма)
        } elseif ($bd['basenum'] == 915) {
            $num  = 916; $name = "Ойнкологн";
            if ($bd['gender'] == "Девочка") { $form = 'female'; }

        // --- Общая (дефолтная) эволюция по данным из базы ---
        } else {
            if (!empty($bd_pok['evol_basenum'])) {
                $num = (int)$bd_pok['evol_basenum'];
                // Если имя не меняли — подставим русское имя формы эволюции из базы
                if ($bd_pok['name_rus'] == $bd['name_new']) { $name = $b['name_rus']; }

                // Особый случай для #666 Vivillon — рандом формы
                if ($num == 666) {
                    $input = ['archipelago','continental','elegant','fancy','garden','highplains','icysnow','jungle','marine','modern','monsoon','ocean','pokeball','polar','river','sandstorm','savanna','sun','tundra'];
                    $rand_keys = array_rand($input, 2);
                    $form = $input[$rand_keys[1]];
                }
            }
        }

        // Проставляем «женские» формы, если требуют: Frillish/Jellicent, Unfezant, Meowstic, Indeedee, Oinkologne
        if (in_array($num, [592, 593, 521, 678, 876, 916]) && $bd['gender'] == "Девочка") {
            $form = 'female';
        }

        // Если вдруг $num не определился (не нашли подходящую ветку) — безопасно выходим
        if ($num === null) {
            return;
        }

        // Обновляем запись покемона
        Work::$sql->query('UPDATE `user_pokemons`
            SET
                `basenum` = '.$num.',
                `form`    = "'.$form.'",
                `name_new`= "'.$name.'",
                `exp`     = '.Info::_getExp($bd['lvl'], $bd_pok['exp_group']).',
                `exp_max` = '.Info::_getExp(($bd['lvl']+1), $bd_pok['exp_group']).'
            WHERE `id` = '.$bd['id']);

        // Подбор способности после эволюции
        $Ability = Work::$sql->query('SELECT * FROM base_ability_pokemon WHERE id = '.$num)->fetch_assoc();
        $AbilityNow = null; $Slot = 0;

        if ($bd['ability_slot'] != 3) {
            if ($bd['ability_slot'] == 1) { $AbilityNow = $Ability['slot1']; $Slot = 1; }
            elseif ($bd['ability_slot'] == 2) { $AbilityNow = $Ability['slot2']; $Slot = 2; }

            if ($AbilityNow == NULL) { $AbilityNow = $Ability['slot1']; $Slot = 1; }
        }
        if ($bd['ability_slot'] == 3 && $Ability['hidden'] != "0") {
            $AbilityNow = $Ability['hidden']; $Slot = 3;
        }
        if ($AbilityNow == NULL) { $AbilityNow = 0; $Slot = 0; }

        addAbilityPok($bd['id'], $AbilityNow, $Slot);

        // Немного счастья за эволюцию
        $happyAll = $bd['happy'] + 25;
        if ($happyAll > 255) { $happyAll = 255; }
        Work::$sql->query('UPDATE `user_pokemons` SET `happy` = '.$happyAll.' WHERE `id` = '.$bd['id']);

        // Ивентовая миссия (как было)
        if ($bd_pok['evol_lvl'] >= 15 && check_mission_ivent(20)) { add_mission_ivent(20); }
    }
}


function stat_updates($infoPoke){
    if(!is_array($infoPoke)){

        $infoPoke = Work::$sql->query('
        SELECT
          `up`.* ,

          `bp`.`hp` AS `base_hp`,
          `bp`.`atk` AS `base_atk`,
          `bp`.`def` AS `base_def`,
          `bp`.`spd` AS `base_spd`,
          `bp`.`satk` AS `base_satk`,
          `bp`.`sdef` AS `base_sdef`


        FROM `user_pokemons` AS `up`
        INNER JOIN `base_pokemons` AS `bp`
          ON `bp`.`id` = `up`.`basenum`
        WHERE
          `up`.`id` = '.intval($infoPoke).'
      ')->fetch_assoc();

    }else{

        $basePoke = Work::$sql->query('
            SELECT

            `hp` AS `base_hp`,
            `atk` AS `base_atk`,
            `def` AS `base_def`,
            `spd` AS `base_spd`,
            `satk` AS `base_satk`,
            `sdef` AS `base_sdef`

            FROM `base_pokemons`
            WHERE
              `id` = '.$infoPoke['basenum'].'
        ')->fetch_assoc();

        $infoPoke = array_merge($infoPoke, $basePoke);
    }
    $bd  = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$infoPoke['id']."'")->fetch_assoc();
    if(!empty($bd) && $bd['form'] != "0"){
        $basePokes = Work::$sql->query('
            SELECT

            `hp` AS `base_hp`,
            `atk` AS `base_atk`,
            `def` AS `base_def`,
            `spd` AS `base_spd`,
            `satk` AS `base_satk`,
            `sdef` AS `base_sdef`

            FROM `base_pokemon_forms_new`
            WHERE
              `pokemons` = '.$infoPoke['basenum'].' AND
             `id_form` = "'.$bd['form'].'"
        ')->fetch_assoc();


    }

    if($basePokes) $infoPoke = array_merge($infoPoke, $basePokes);

    if(isset($infoPoke['base_hp'])){

        $hp    = stats($infoPoke,0);
        $atk   = stats($infoPoke,1);
        $def   = stats($infoPoke,2);
        $speed = stats($infoPoke,3);
        $spAtk = stats($infoPoke,4);
        $spDef = stats($infoPoke,5);

        $stats = $hp.','.$atk.','.$def.','.$speed.','.$spAtk.','.$spDef;
        Work::$sql->query("UPDATE `user_pokemons` SET `stats`='".$stats."' WHERE `user_id` = '".$_SESSION['id']."' AND `id` = '".$infoPoke['id']."'");

    }

}

function addAbilityPok($id,$abil,$slot){
      Work::$sql->query('UPDATE `user_pokemons` SET
                                  `ability` = '.$abil.',
                                  `ability_slot` = '.$slot.'
                                WHERE `id` = '.$id);
    }

function pok_type_check($type){
    $i = 0;
    $Poks = Work::$sql->query('
      								SELECT
      								*
      								FROM `user_pokemons`
      								WHERE
      									`active` = 1
      								AND `user_id` = '.$_SESSION['id']);
      while($pok = $Poks->fetch_assoc()){
      $bd  = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pok['basenum']."'")->fetch_assoc();
          if($bd['type'] == $type or $bd['type_two'] == $type){
              $i++;
          }
      }
      if($i != 0){
          return true;
      }else{
          return false;
      }
}
#Формула статов
function stats($infoPoke, $tip){
    if($infoPoke && isset($infoPoke['base_hp'])){

        $gens = explode(',', $infoPoke['gen']);
        $ev   = explode(',', $infoPoke['evcounts']);
        $har  = Work::$sql->query("SELECT * FROM `har` WHERE `id_har` = '".$infoPoke['character']."'")->fetch_assoc();

        if($tip == 0){

            $stat = (($infoPoke['base_hp'] * 2) + $gens[0] + ($ev[0]/2)) * ($infoPoke['lvl']/100) + 10 + $infoPoke['lvl'];

        }elseif($tip == 1){

            $stat = ((($infoPoke['base_atk'] * 2 + $gens[1] + ($ev[1]/2)) * $infoPoke['lvl']/100 + 5) * $har['atk']);

            if($infoPoke['tren_stat'] == 1){
                $stat = $stat*classific($infoPoke['tren']);
            }

        }elseif($tip == 2){
            $stat = (($infoPoke['base_def'] * 2 + $gens[2] + ($ev[2]/2)) * $infoPoke['lvl']/100 + 5) * $har['def'];

            if($infoPoke['tren_stat'] == 2){
                $stat = $stat*classific($infoPoke['tren']);
            }

        }elseif($tip == 3){

            $stat = (($infoPoke['base_spd'] * 2 + $gens[3] + ($ev[3]/2)) * $infoPoke['lvl']/100 + 5) * $har['speed'];

            if($infoPoke['tren_stat'] == 3){
                $stat = $stat*classific($infoPoke['tren']);
            }

        }elseif($tip == 4){

            $stat = (($infoPoke['base_satk'] * 2 + $gens[4] + ($ev[4]/2)) * $infoPoke['lvl']/100 + 5) * $har['satk'];

            if($infoPoke['tren_stat'] == 4){
                $stat = $stat*classific($infoPoke['tren']);
            }

        }elseif($tip == 5){

            $stat = (($infoPoke['base_sdef'] * 2 + $gens[5] + ($ev[5]/2)) * $infoPoke['lvl']/100 + 5) * $har['sdef'];

            if($infoPoke['tren_stat'] == 5){
                $stat = $stat*classific($infoPoke['tren']);
            }

        }else{
            $stat = 0;
        }
        return intval(round($stat));
    }

    return 0;
}
#Формула тренировок
function classific($class){
    switch($class){
        case 1:
            $stat = 1.03;
            break;
        case 2:
            $stat = 1.06;
            break;
        case 3:
            $stat = 1.10;
            break;
        case 4:
            $stat = 1.14;
            break;
        case 5:
            $stat = 1.18;
            break;
        case 6:
            $stat = 1.21;
            break;
        default:
            $stat = 1;
            break;
    }

    return $stat;
}
#Закрыть уведомление администрации
function closeNotify($id){
	Work::$sql->query("INSERT INTO `adminNotifyCheck` (`user_id`,`id_notify`) VALUES ('".$_SESSION['id']."','".$id."') ");
}
#Проверяет время добычи
function discoveryTime($loc,$type){

	$q = Work::$sql->query("SELECT * FROM `discovery` WHERE `user` = '".$_SESSION['id']."' AND `id_loc` = '".$loc."' AND `type` = '".$type."' ORDER BY `id` DESC")->fetch_assoc();
	$a = ($q['time'] > time() ? true : false);
	return $a;
}
#Проверяет, изучен ли рецепт
function recipeCheck($id){

	$a = Work::$sql->query("SELECT * FROM `craft_recipe_user` WHERE `recipe` = '".$id."' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
	if(!$a){
		$b = 'no-active';
	}else{
		$b = '';
	}
	return $b;
}
#Добавляет нули в номере к покемону по айди
function numCheck($id){

	$a = Work::$sql->query("SELECT `basenum` FROM `user_pokemons` WHERE `id` = '".$id."'")->fetch_assoc();
	if($a['basenum'] >= 1 && $a['basenum'] <= 9){
		$b = '00'.$a['basenum'];
	}elseif($a['basenum'] >= 10 && $a['basenum'] <= 99){
		$b = '0'.$a['basenum'];
	}else{
		$b = $a['basenum'];
	}
	return $b;
}
function numCheck_basenum($id){

	if($id >= 1 && $id <= 9){
		$b = '00'.$id;
	}elseif($id >= 10 && $id <= 99){
		$b = '0'.$id;
	}else{
		$b = $id;
	}
	return $b;

}
#Апдейт элементов одежды
function update_cloth($slot, $id){
	Work::$sql->query("UPDATE `cloth` SET `".$slot."` = '".$id."' WHERE `user` = '".$_SESSION['id']."'");
}
#Апдейт записей квестов в дневнике
function update_zap($id, $step, $text){
	Work::$sql->query("INSERT INTO `quest_steps` (`id_user`,`text`,`quest_id`,`quest_step`) VALUES ('".$_SESSION['id']."','".$text."','".$id."','".$step."') ");
}
#Апдейт ачивок.
function update_ach($id, $count,$user = false){
    //Старое ничего не делает
}

function update_achiv($id, $count,$user = false){
    $user = (!$user ? $_SESSION['id'] : $user);
  $ach = Work::$sql->query("SELECT * FROM `base_achievements` WHERE `id` = '".$id."'")->fetch_assoc();
  $ach_user = Work::$sql->query("SELECT * FROM `user_achievements` WHERE `user_id` = '".$user."' AND `id_ach` = '".$id."'")->fetch_assoc();
  if($ach_user){
	  if($ach['category'] == 1){
		  $cnt = ($count + $ach_user['count']);
		  Work::$sql->query("UPDATE `user_achievements` SET `count` = '".$cnt."' WHERE `id_ach` = '".$id."' AND `user_id` = '".$user."'");
		  if($cnt >= $ach['need'] AND $ach_user['complete'] != 1){
			  Work::$sql->query("UPDATE `user_achievements` SET `complete` = 1 WHERE `id_ach` = '".$id."' AND `user_id` = '".$user."'");
			  news_friend(5,$id);
		  }
	  }else{
		  Work::$sql->query("UPDATE `user_achievements` SET `complete` = 1 WHERE `id_ach` = '".$id."' AND `user_id` = '".$user."'");
		  
	  }
  }else{
	  if($ach['category'] == 1){
		  Work::$sql->query("INSERT INTO `user_achievements` (`user_id`,`id_ach`,`complete`,`count`) VALUES ('".$user."','".$id."',0,'".$count."') ");
	  }else{
		  Work::$sql->query("INSERT INTO `user_achievements` (`user_id`,`id_ach`,`complete`,`count`) VALUES ('".$user."','".$id."',1,0) ");
	  }
  }
}
function achiv_utility($id,$user = false){
    $user = (!$user ? $_SESSION['id'] : $user);
    $ach = Work::$sql->query("SELECT * FROM `user_achievements_utility` WHERE `utility` = '".$id."' AND `user` = '".$user."'")->fetch_assoc();
    if(isset($ach)){
        return true;
    }else{
        return false;
    }
}
#Найти что-либо о Юзере по его ID.
function info_user($id, $type){

  $user = Work::$sql->query("SELECT * FROM `users` WHERE `id` = '".$id."'")->fetch_assoc();
  if($user){
	  $b = $user[$type];
  }else{
	  $b = 'error';
  }
  return $b;
}
function info_item($id, $type){

  $item = Work::$sql->query("SELECT * FROM `base_items` WHERE `id` = '".$id."'")->fetch_assoc();
  if($item){
	  $b = $item[$type];
  }else{
	  $b = 'error';
  }
  return $b;
}
#Проверка на начало квеста в дневнике.
function quest_isset_book($id){

  $quest = Work::$sql->query("SELECT * FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."'")->fetch_assoc();
  if($quest){
	  if($quest['end'] == 1){
		 $b = '<div class="Progress type2">Выполнен</div>';
	  }else{
		 $b = '<div class="Progress type3">В процессе</div>';
	  }
  }else{
	  $b = '<div class="Progress type1">Квест не начат</div>';
  }
  return $b;
}
function quest_info($id,$type){

	$quest = Work::$sql->query("SELECT * FROM `base_quest` WHERE `id` = ".$id."")->fetch_assoc();
	$a = $quest[$type];
	return $a;
}
function quest_ready($id){

	$quest = Work::$sql->query("SELECT * FROM `user_quests` WHERE `quest_id` = ".$id." AND `user_id` = ".$SESSION['id']." ")->fetch_assoc();
	return $quest['end'];
}

function add_pokemon_military(){
    $base = Work::$sql->query("SELECT `military` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
    $b = $base['military']+1;
    Work::$sql->query("UPDATE `users` SET `military` = '".$b."' WHERE `id` = '".$_SESSION['id']."' ");
}

function add_pokemon_military_catch(){
    $base = Work::$sql->query("SELECT `military_limit` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
    $b = $base['military_limit']-1;
    Work::$sql->query("UPDATE `users` SET `military_limit` = '".$b."' WHERE `id` = '".$_SESSION['id']."' ");
}
#Добавления предмета



function itemAdd($itemID, $count, $user = false, $dop = false){
	if($itemID > 0){
		$user = (!$user ? $_SESSION['id'] : $user);
		$dop = (!$dop ? 0 : $dop);
    $base = Work::$sql->query("SELECT `str`,`expiration`,`cool` FROM `base_items` WHERE `id`= '".$itemID."'")->fetch_assoc();
		$items = Work::$sql->query("SELECT * FROM `items_users` WHERE `user`= '".$user."' AND `item_id` = '".$itemID."'")->fetch_assoc();
		if(!empty($items['count']) && $base['str'] == '0' && $base['cool'] == 0){
			$itemsCount = $items['count'] + $count;
			Work::$sql->query("UPDATE `items_users` SET `count` = '".$itemsCount."' WHERE `item_id` = '".$itemID."' AND `user` = '".$user."'");
		}else{
		    if($itemID >= 224 and $itemID <= 238 ){$dop = rand(20,55);}
		    if($itemID == 408){$dop = 100;}
      if($base['str'] == '0') {
        if($base['expiration'] == 0){
          if($base['cool'] == 0){

            Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`dop`) VALUES ('".$user."','".$itemID."','".$count."','".$dop."') ");
          }else{
            $i = 1;
            while($i <= $count) {
              Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`dop`) VALUES ('".$user."','".$itemID."','1','".$dop."') ");
              $i++;
            }
          }

        }else{
          $date_receiving = time();
          $date_expiration = time()+60*$base['expiration'];
          if($count == 1){
            Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`date_receiving`,`date_expiration`,`dop`) VALUES ('".$user."','".$itemID."','1','".$date_receiving."','".$date_expiration."','".$dop."') ");
          }else{
            $i = 1;
            while($i <= $count) {
              Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`date_receiving`,`date_expiration`,`dop`) VALUES ('".$user."','".$itemID."','1','".$date_receiving."','".$date_expiration."','".$dop."') ");
              $i++;
            }
          }
        }
      }else{
        if($base['expiration'] == 0){
        $i = 1;
        $str = explode(',',$base['str']);
        while($i <= $count) {
          $rand_str = mt_rand($str[0],$str[1]);
          Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`,`dop`) VALUES ('".$user."','".$itemID."','1','".$rand_str.','.$rand_str."','".$dop."') ");
          $i++;
        }
      }else{
        $date_receiving = time();
        $date_expiration = time()+60*$base['expiration'];
        if($count == 1){
          Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`date_receiving`,`date_expiration`,`dop`) VALUES ('".$user."','".$itemID."','1','".$date_receiving."','".$date_expiration."','".$dop."') ");
        }else{
          $i = 1;
          while($i <= $count) {
            Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`date_receiving`,`date_expiration`,`dop`) VALUES ('".$user."','".$itemID."','1','".$date_receiving."','".$date_expiration."','".$dop."') ");
            $i++;
          }
        }
      }
      }
		}
	}else{
		return false;
	}
	// Сброс кеша предметов пользователя в рамках текущего запроса
	_runtime_cache('items_users', ((int)$user).'-'.((int)$itemID), null, 'del');

}


#Определяет наличие предмета
function item_isset($item_id, $count, $user = false){
	if(empty($user)){
		$user = $_SESSION['id'];
	}
	$item_id = (int)$item_id;
	$count = (int)$count;
	$user = (int)$user;

	$key = $user.'-'.$item_id;
	$have = _runtime_cache('items_users', $key);

	// В items_users могут быть несколько строк для одного item_id (например, разные date_expiration).
	// Кешируем реальное доступное количество, чтобы корректно обрабатывать разные запросы count в рамках одного запроса.
	if($have === null){
		$i = Work::$sql->query("SELECT SUM(`count`) AS `count` FROM `items_users` WHERE `user` = '".$user."' AND `item_id` = '".$item_id."'")->fetch_assoc();
		$have = (!empty($i['count']) && (int)$i['count'] > 0) ? (int)$i['count'] : 0;
		_runtime_cache('items_users', $key, $have, 'set');
	}

	if($have >= $count && $have > 0){
		return $have;
	}else{
		return false;
	}
}


function item_isset_baf($item_id, $user = false){
	if(empty($user)){
		$user = $_SESSION['id'];
	}
	$item_id = (int)$item_id;
	$user = (int)$user;

	$time = time();
	$key = $user.'-'.$item_id.'-'.$time;

	// Для bafs важна актуальность "на сейчас", поэтому кешируем только в рамках одного запроса по текущему time().
	$cached = _runtime_cache('bafs', $key);
	if($cached !== null){
		return $cached;
	}

	$i = Work::$sql->query("SELECT `id` FROM `bafs` WHERE `user` = '".$user."' AND `baf` = '".$item_id."' AND `time` >= '".$time."' LIMIT 1")->fetch_assoc();
	$res = (!empty($i['id'])) ? true : false;
	_runtime_cache('bafs', $key, $res, 'set');

	return $res;
}

#Определяет количество предмета
function item_isset_count($item_id, $user = false){
	if(empty($user)){
		$user = $_SESSION['id'];
	}
	$i = Work::$sql->query("SELECT `count` FROM `items_users` WHERE `user` = '".$user."' AND `item_id` = '".$item_id."' ")->fetch_assoc();
	if($i){
	    return $i['count'];
	}else{
	    return 0;
	}
}
#Отнимает предмет
function minus_item($item_id, $count, $user = false){

	if($user == false){
		$user = $_SESSION['id'];
	}
	$item_id = (int)$item_id;
	$count = (int)$count;
	$user = (int)$user;

	$item = Work::$sql->query("SELECT * FROM `items_users` WHERE `user` = '".$user."' AND `item_id` = '".$item_id."' AND `count` >= '".$count."' LIMIT 1");
	if($item->num_rows > 0){
		$items = $item->fetch_assoc();
		if($items['date_expiration'] > 0){
		    Work::$sql->query("DELETE FROM `items_users` WHERE `item_id` = '".$item_id."' AND `user` = '".$user."' LIMIT ".$count);
			// Для exp items корректный остаток считать сложно (может быть несколько строк) — просто сбрасываем кеш.
			_runtime_cache('items_users', $user.'-'.$item_id, null, 'del');
		}else{
		    if($items['count'] > $count){
			$x = (int)$items['count'] - $count;
			Work::$sql->query("UPDATE `items_users` SET `count` = '".$x."' WHERE `item_id` = '".$item_id."' AND `user` = '".$user."'");
			_runtime_cache('items_users', $user.'-'.$item_id, $x, 'set');
		}else{
			Work::$sql->query("DELETE FROM `items_users` WHERE `item_id` = '".$item_id."' AND `user` = '".$user."'");
			_runtime_cache('items_users', $user.'-'.$item_id, 0, 'set');
		}
		}

    }
}

#Отнимает предмет по ID
function minus_item_id($item_id, $count, $user=false){

    if($user == false) $user = $_SESSION['id'];
    $item = Work::$sql->query("SELECT * FROM `items_users` WHERE `user` = '".$user."' AND `id` = '".$item_id."' AND `count` >= '".$count."'");
    if($item->num_rows > 0){
        $items = $item->fetch_assoc();
        if($items['count'] > $count){
            $x = $items['count'] - $count;
            Work::$sql->query("UPDATE `items_users` SET `count` = '".$x."' WHERE `id` = '".$item_id."' AND `user` = '".$user."'");
        }else{
            Work::$sql->query("DELETE FROM `items_users` WHERE `id` = '".$item_id."' AND `user` = '".$user."'");
        }
    }
}
#Функция распознования характера
function haracter_pokes($a){
    $harakter['1'] = "Веселый";
    $harakter['2'] = "Выносливый";
    $harakter['3'] = "Застенчивый";
    $harakter['4'] = "Кроткий";
    $harakter['5'] = "Мирный";
    $harakter['6'] = "Мягкий";
    $harakter['7'] = "Наглый";
    $harakter['8'] = "Наивный";
    $harakter['9'] = "Нахальный";
    $harakter['10'] = "Нежный";
    $harakter['11'] = "Непослушный";
    $harakter['12'] = "Непреклонный";
    $harakter['13'] = "Обычный";
    $harakter['14'] = "Одинокий";
    $harakter['15'] = "Озорной";
    $harakter['16'] = "Осторожный";
    $harakter['17'] = "Поспешный";
    $harakter['18'] = "Причудливый";
    $harakter['19'] = "Распущенный";
    $harakter['20'] = "Робкий";
    $harakter['21'] = "Серьезный";
    $harakter['22'] = "Скромный";
    $harakter['23'] = "Смелый";
    $harakter['24'] = "Спокойный";
    $harakter['25'] = "Стремительный";
    $harakter['26'] = "Тихий";
  $b = $harakter[$a];
  if(!$b) $b = 'UNDEFINED';
 return $b;
}

function tren_name($a){
    $info = [];
    $info['1'] = "Атака";
    $info['2'] = "Защита";
    $info['3'] = "Скорость";
    $info['4'] = "Спец. Атака";
    $info['5'] = "Спец. Защита";

    if(isset($info[$a])){
        return $info[$a];
    }

    return 'Отсутствует';
}

function Check_Attack($pokemon,$id){
    global $mysqli;
$pok_base = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokemon."'")->fetch_assoc();
$check = false;
$arrayAtk = explode(',',$pok_base['attacks']);
if($arrayAtk[0] == $id or $arrayAtk[1] == $id or $arrayAtk[2] == $id or $arrayAtk[3] == $id) $check = true;
return $check;
}
#Функция добавления нового покемона
function newPokemon($pok,$user_new=false,$lvl=false,$gen=false,$startGame=false,$trade=false,$sparka=false,$event=false,$shine=false,$character=false,$ev=false,$eggThis=false) {
	global $mysqli;
  if($trade == 'false'){
    $trade = 'false';
  }else{
    $trade = 'true';
  }
	$pok_base = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pok."'")->fetch_assoc();
	$event = ($event?$event:0);
	$lvl = ($lvl?$lvl:1);
	$startGame = ($startGame?$startGame:0);
	$user_new = ($user_new?$user_new:$_SESSION['id']);

    $users =  $mysqli->query("SELECT `user_group`,`login` FROM `users` WHERE `id`='".$user_new."'")->fetch_assoc();
	$usersPokemon =  $mysqli->query("SELECT `active` FROM `user_pokemons` WHERE `user_id`='".$_SESSION['id']."' AND `active` = 1");
  if($usersPokemon->num_rows < 6){
		if($eggThis == false){
			$active = 0;
		}else{
			$active = 1;
		}
	}else{
		$active = 0;
	}
	$month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
	$dateGet = '{"user_id":"'.$user_new.'","date": "'.time().'"}';
	#Яйцевая атака
	$eggAttacks = $mysqli->query("SELECT `attacks` FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' AND `type` = 'sex'")->fetch_assoc();
	$arrayAttacks = explode(',',$eggAttacks['attacks']);
	if(rand(1,100) < 45){
		$numberAttack = mt_rand(0, count($arrayAttacks) - 1);
		if($eggThis == false){
			if(!empty($arrayAttacks[$numberAttack])){
				$attacksList = $arrayAttacks[$numberAttack].',0,0,0';
				$atk_egg = $arrayAttacks[$numberAttack];
			}else{
				$attacksList = '0,0,0,0';
			}
		}else{
			$attacksList = '0,0,0,0';
		}
	}else{
		$attacksList = '0,0,0,0';
	}
	if($shine == 1){
			$shine = "shine";
}else{
  $shine = "normal";
}
  if($pok_base['sex_m'] == 0 && $pok_base['sex_f'] == 0) {
    $gender = 'Бесполый';
  }else{
    $gender = ($pok_base['sex_f'] > 0 ? ( $pok_base['sex_f'] >= mt_rand(0, 100) ? 'Девочка' : 'Мальчик' ) : 'Мальчик');
  }
	$character = ($character?$character:rand(1,26));
	$har = $mysqli->query("SELECT * FROM `har` WHERE `id_har` = '".$hr."' ")->fetch_assoc();
	$hg = rand(17,23);
	$ag = rand(17,23);
	$dg = rand(17,23);
	$sg = rand(17,23);
	$sag = rand(17,23);
	$sdg = rand(17,23);
	if($gen){
	    $gens = $gen.','.$gen.','.$gen.','.$gen.','.$gen.','.$gen;
	}else{
	    $gens = $hg.','.$ag.','.$dg.','.$sg.','.$sag.','.$sdg;
	}

	$s1 = round((($pok_base['hp'] * 2) + $hg) * (1/100) + 10 + 1);
	$s2 = round((($pok_base['atk'] * 2 + $ag) * 1/100 + 5) * $har['atk']);
	$s3 = round((($pok_base['def'] * 2 + $dg) * 1/100 + 5) * $har['def']);
	$s4 = round((($pok_base['spd'] * 2 + $sg) * 1/100 + 5) * $har['speed']);
	$s5 = round((($pok_base['satk'] * 2 + $sag) * 1/100 + 5) * $har['satk']);
	$s6 = round((($pok_base['sdef'] * 2 + $sdg) * 1/100 + 5) * $har['sdef']);

  	$ev = ($ev?$ev:0);
	$sparkNumber = mt_rand(1, 3);
    $stats = $s1.','.$s2.','.$s3.','.$s4.','.$s5.','.$s6;
	$expirience = Info::_getExp($lvl, $pok_base['exp_group']);
	$expirienceMax = Info::_getExp(($lvl+1), $pok_base['exp_group']);
	$ability = generateAbilityPok($pok_base['id']);
	$abil_id = $ability[0];
	$abil_slot = $ability[1];
	$teraType = generateTeraType($pok_base['type'] ?? 'normal', $pok_base['type_two'] ?? '');

	$mysqli->query("INSERT INTO `user_pokemons` (`user_id`,`basenum`,`name_new`,`ability`,`ability_slot`,`character`,`lvl`,`birthday`,`active`,`type`,`gender`,`exp`,`exp_max`,`ev`,`hp`,`stats`,`gen`,`owner`,`master`,`startGame`,`sparka`,`attacks`,`sparkaNumber`,`trade`,`event`) VALUES ('".$user_new."','".$pok_base['id']."','".$pok_base['name_rus']."','".$abil_id."','".$abil_slot."','".$character."','".$lvl."','".$dateGet."','".$active."','".$shine."','".$gender."','".$expirience."','".$expirienceMax."','".$ev."','".$s1."','".$stats."','".$gens."','".$user_new."','".$user_new."','".$startGame."','".$sparka."','".$attacksList."','".$sparkNumber."','".$trade."','".$event."') ");
	$id_pok_new = $mysqli->insert_id;
	if($atk_egg){
	    $mysqli->query("INSERT INTO `user_pokemons_tm` (`pok`,`attacks`) VALUES ('".$id_pok_new."','".$atk_egg."') ");
	}
	if($eggThis == false){
		$month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
	$dayToday = date("d");
	$monthToday = $month[date("n")];
	$YearToday = date("Y");
	$date = $dayToday.' '.$monthToday.' '.$YearToday.'г. в '.date("H").':'.date("i");
	$text = 'В вашу приманку попал покемон <b>'.$pok_base['name_rus'].'</b>. Проверьте свой питомник.';
	$mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) 
VALUES ('".$text."','".$user_new."','/img/pokemons/animation/".numbPok($pok_base['id']).".png','".$date."')");
	update_ach(3,1,$user_new);
	}
  lvlupuser(10,$user_new);
	}

	function generateAbilityPok($num){
	    global $mysqli;
      $Ability = $mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$num)->fetch_assoc();
      $Abil1 = ($Ability['slot1'] != "0" ? $Ability['slot1'] : 0);
      $Abil2 = ($Ability['slot2'] != "0" ? $Ability['slot2'] : 0);
      $AbilArray = [$Abil1,$Abil2];
      if($AbilArray[0] != "0" && $AbilArray[1] == "0") {
        $AbilityEnd = $AbilArray[0];
        $Slot = 1;
      }
      if($AbilArray[0] != "0" && $AbilArray[1] != "0") {
        $randAbil = mt_rand(1,2);
        if($randAbil == 1) {
          $AbilityEnd = $AbilArray[0];
          $Slot = 1;
        }else{
          $AbilityEnd = $AbilArray[1];
          $Slot = 2;
        }
      }
      if($AbilArray[0] == "0" && $AbilArray[1] != "0") {
        $AbilityEnd = $AbilArray[1];
        $Slot = 2;
      }
      if($AbilArray[0] == "0" && $AbilArray[1] == "0") {
        $AbilityEnd = 0;
        $Slot = 0;
      }
      return [$AbilityEnd,$Slot];

    }

#Функция добавления яйца
function plusEgg($gens=false,$character=false,$shine=false,$trade=false,$reborn=false,$basenum=false,$sparka=false,$userEgg=false,$form=false){
  $user = ($userEgg == false ? $_SESSION['id'] : $userEgg);
	$sprk = ($sparka == false ? 0 : 1);
	$character = ($character?$character:rand(1,26));
	$hg = rand(10,26);
	$ag = rand(10,26);
	$dg = rand(10,26);
	$sg = rand(10,26);
	$sag = rand(10,26);
	$sdg = rand(10,26);
	$gens = ($gens?$gens:$hg.','.$ag.','.$dg.','.$sg.','.$sag.','.$sdg);
  if($shine == 1) {
    $shine = 1;
  }else{
		if(mt_rand(1, 11000) < 10){
			$shine = 1;
		}else{
			$shine = 0;
		}
	}
	if($form){ $form = $form; }else{$form = 0;}
	$trade = ($trade == false ? 'false' : 'true');
	$countDay = rand(5,11);
	$reborn = ($reborn?$reborn:time()+(3600*24*$countDay));
	$basenum = ($basenum?$basenum:rand(1,898));
	$baseGet = Work::$sql->query("SELECT `eggBasenum`,`type`,`type_two` FROM `base_pokemons` WHERE `id` = '".$basenum."'")->fetch_assoc();
	if(check_mission(2) and $baseGet['eggBasenum'] == 165){ add_mission(2);}
	if(check_mission(9) and $baseGet['eggBasenum'] == 16){ add_mission(9);}
	if(check_mission(10) and $baseGet['eggBasenum'] == 92){ add_mission(10);}
	if(check_mission(11) and $baseGet['eggBasenum'] == 194){ add_mission(11);}

	if(check_mission(16) and ($baseGet['type'] == "rock" or $baseGet['type_two'] == "rock")){ add_mission(16);}
	if(check_mission(24) and ($baseGet['type'] == "water" or $baseGet['type_two'] == "water")){ add_mission(24);}
	if(check_mission(25) and ($baseGet['type'] == "grass" or $baseGet['type_two'] == "grass")){ add_mission(25);}


// 	if(check_mission_ivent(4)){ add_mission_ivent(4);}
// 	if(check_mission_ivent(20)){ add_mission_ivent(20);}
	$a = Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`,`form`) VALUES('".$gens."','".$character."','".$shine."','".$trade."','".$reborn."','".$user."','".$baseGet['eggBasenum']."','".$sprk."','".$form."') ");
}
function generateRandomPokemon($userId) {
    $basenum = rand(1, 898); // Генерация случайного покемона (ID в покедексе)
    $lvl = rand(90, 100); // Случайный уровень
    $attacks = [
        rand(1, 750), 
        rand(1, 750), 
        rand(1, 750), 
        rand(1, 750) // Четыре случайные атаки
    ];
    $gen = implode(',', [
        rand(15, 31), // IV HP
        rand(15, 31), // IV Attack
        rand(15, 31), // IV Defense
        rand(15, 31), // IV Sp. Attack
        rand(15, 31), // IV Sp. Defense
        rand(15, 31)  // IV Speed
    ]);

    return addPokemonToUser($userId, $basenum, $lvl, $attacks, $gen);
}
function giveArenaPokemonsToUser($userId) {
    $numPokemons = 6; // Количество покемонов для выдачи
    $pokemons = [];

    for ($i = 0; $i < $numPokemons; $i++) {
        $pokemonId = generateRandomPokemon($userId);
        if ($pokemonId) {
            $pokemons[] = $pokemonId;
        } else {
            error_log("Ошибка выдачи покемона пользователю ID={$userId}");
        }
    }

    return $pokemons;
}

function addPokemonToUser($userId, $basenum, $lvl, $attacks, $gen) {
    global $mysqli;

    $attacksStr = implode(',', $attacks);
   $row = $mysqli->query("SELECT type, type_two FROM base_pokemons WHERE id=".(int)$basenum)->fetch_assoc();
$teraType = generateTeraType($row['type'] ?? 'normal', $row['type_two'] ?? '');

$stmt = $mysqli->prepare("INSERT INTO user_pokemons (user_id, basenum, lvl, attacks, gen, tera_type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiisss", $userId, $basenum, $lvl, $attacksStr, $gen, $teraType);

    $stmt->execute();
    return $mysqli->insert_id; // ID добавленного покемона
}
function startBattle($user1, $user2) {
    global $mysqli;

    // Отправляем текущих покемонов в питомник
    sendPokemonsToNursery($user1);
    sendPokemonsToNursery($user2);

    // Выдаем случайных покемонов
    $user1Pokemons = giveRandomArenaPokemons($user1);
    $user2Pokemons = giveRandomArenaPokemons($user2);

    // Проверяем выдачу покемонов
    if (!$user1Pokemons || !$user2Pokemons) {
        error_log("Ошибка выдачи покемонов для пользователей: {$user1}, {$user2}");
        return false;
    }

    // Логируем битву
    $stmt = $mysqli->prepare("INSERT INTO arena_battles (user1, user2, pokemons1, pokemons2, status) VALUES (?, ?, ?, ?, 'active')");
    $pokemons1Str = implode(',', $user1Pokemons);
    $pokemons2Str = implode(',', $user2Pokemons);
    $stmt->bind_param("iiss", $user1, $user2, $pokemons1Str, $pokemons2Str);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        error_log("Битва успешно создана между пользователями: {$user1} и {$user2}");
        return $mysqli->insert_id;
    } else {
        error_log("Ошибка создания битвы.");
        return false;
    }
}



function endBattle($battleId, $winner) {
    global $mysqli;

    // Обновляем статус битвы
    $stmt = $mysqli->prepare("UPDATE arena_battles SET status = 'finished', winner = ? WHERE id = ?");
    $stmt->bind_param("ii", $winner, $battleId);
    $stmt->execute();

    // Получаем ID покемонов из битвы
    $stmt = $mysqli->prepare("SELECT pokemons1, pokemons2 FROM arena_battles WHERE id = ?");
    $stmt->bind_param("i", $battleId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $pokemonsToRemove = array_merge(
        explode(',', $result['pokemons1']),
        explode(',', $result['pokemons2'])
    );

    // Удаляем покемонов
    foreach ($pokemonsToRemove as $pokemonId) {
        $stmt = $mysqli->prepare("DELETE FROM user_pokemons WHERE id = ?");
        $stmt->bind_param("i", $pokemonId);
        $stmt->execute();
    }
    if(function_exists('newyear_event_add')){
        newyear_event_add((int)$winner, 'pvp_win', 1);
    }

}
function findOpponent($userId) {
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT user_id FROM arena_queue WHERE user_id != ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? $result['user_id'] : false;
}
// Получение случайной загадки
function getRandomRiddle() {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM `riddles` ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Получение правильного покемона для загадки
function getCorrectPokemonForRiddle($riddleId) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM `riddle_pokemon_mapping` WHERE `riddle_id` = ?");
    $stmt->bind_param("i", $riddleId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    return $data['pokemon_id'];
}
function handleArenaRequest($userId) {
    global $mysqli;

    // Проверяем, есть ли другой игрок в очереди
    $stmt = $mysqli->prepare("SELECT user FROM arena WHERE user != ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $opponent = $stmt->get_result()->fetch_assoc();

    if ($opponent) {
        // Если найден соперник, запускаем битву
        $opponentId = $opponent['user'];

        // Удаляем записи о заявках из `arena`
        $mysqli->query("DELETE FROM arena WHERE user IN ($userId, $opponentId)");

        // Запускаем битву
        $battleId = startBattle($userId, $opponentId);

        if ($battleId) {
            return "Битва началась! ID битвы: $battleId";
        } else {
            return "Ошибка при создании битвы.";
        }
    } else {
        // Если соперника нет, добавляем игрока в очередь
        $stmt = $mysqli->prepare("INSERT INTO arena (user, time) VALUES (?, ?)");
        $time = time();
        $stmt->bind_param("ii", $userId, $time);
        $stmt->execute();
        return "Вы добавлены в очередь. Ожидайте соперника.";
    }
}
function sendPokemonsToNursery($userId) {
    global $mysqli;
    // Помечаем всех активных покемонов как отправленных в питомник
    $stmt = $mysqli->prepare("UPDATE user_pokemons SET active = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}
function cleanUpOldArenaRequests() {
    global $mysqli;
    $timeout = time() - 600; // 10 минут
    $mysqli->query("DELETE FROM arena WHERE time < {$timeout}");
}
/** Найти id предмета в base_items, который соответствует надетому скину */
function skin_getItemIdForReturn(mysqli $mysqli, int $skinId, ?string $color = null, ?string $skinName = null): ?int {
    $skinId = (int)$skinId;
    $color = $color !== null ? $mysqli->real_escape_string($color) : null;
    $skinName = $skinName !== null ? $mysqli->real_escape_string($skinName) : null;

    // 1) точное совпадение по skin_id + color
    if ($color !== null && $color !== '') {
        $q1 = $mysqli->query("SELECT `id` FROM `base_items`
                              WHERE `type`='skin' AND `skin_id`={$skinId} AND `skin_color`='{$color}'
                              LIMIT 1");
        if ($q1 && ($r=$q1->fetch_assoc())) return (int)$r['id'];
    }

    // 2) точное совпадение по skin_id + skinName (base_items.info)
    if ($skinName !== null && $skinName !== '') {
        $q2 = $mysqli->query("SELECT `id` FROM `base_items`
                              WHERE `type`='skin' AND `skin_id`={$skinId} AND `info`='{$skinName}'
                              LIMIT 1");
        if ($q2 && ($r=$q2->fetch_assoc())) return (int)$r['id'];
    }

    // 3) по одному skin_id
    $q3 = $mysqli->query("SELECT `id` FROM `base_items`
                          WHERE `type`='skin' AND `skin_id`={$skinId}
                          LIMIT 1");
    if ($q3 && ($r=$q3->fetch_assoc())) return (int)$r['id'];

    return null;
}

/** Добавить предмет в инвентарь (или увеличить счётчик) */
function inv_addItem(mysqli $mysqli, int $userId, int $itemId, int $cnt = 1): bool {
    $row = $mysqli->query("SELECT `id`,`count` FROM `items_users`
                           WHERE `item_id`={$itemId} AND `user`={$userId}
                           LIMIT 1")->fetch_assoc();
    if ($row) {
        $mysqli->query("UPDATE `items_users` SET `count`=`count`+{$cnt} WHERE `id`={$row['id']}");
    } else {
        $mysqli->query("INSERT INTO `items_users` (`item_id`,`count`,`user`) VALUES ({$itemId},{$cnt},{$userId})");
    }
    if (function_exists('notify')) {
        notify($userId, 'plus_item', ['item_id'=>$itemId,'count'=>$cnt]);
    }
    return true;
}

/** Вернуть текущий надетый скин в инвентарь (если он есть) */
function skin_returnEquippedIfAny(mysqli $mysqli, int $userId): bool {
    $cl = $mysqli->query("SELECT `skin`,`color`,`skinName` FROM `cloth`
                          WHERE `user`={$userId} LIMIT 1")->fetch_assoc();
    if (!$cl || empty($cl['skin'])) return true;

    $itemId = skin_getItemIdForReturn($mysqli, (int)$cl['skin'], $cl['color'] ?? null, $cl['skinName'] ?? null);
    if ($itemId) {
        inv_addItem($mysqli, $userId, $itemId, 1);
    }
    return true;
}

/* ===========================================================
 *  Новогодний ивент (серверный) + интерактивная Ёлка + админ-оверрайд
 *  - Серверный прогресс и дерево (общие для всех)
 *  - Бесплатное действие у ёлки 1 раз в день на аккаунт
 *  - Админ может запустить/остановить вручную
 *  ВАЖНО: сделано максимально совместимо со старыми схемами БД:
 *  - Таблицы создаются при первом обращении
 *  - base_items пополняется через динамический INSERT по доступным колонкам
 * =========================================================== */

function newyear_db(){
    // Единая точка получения подключения к БД в разных окружениях проекта
    if(class_exists('Work') && isset(Work::$sql) && Work::$sql instanceof mysqli){
        return Work::$sql;
    }
    if(isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli){
        return $GLOBALS['mysqli'];
    }
    if(isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli){
        return $GLOBALS['db'];
    }
    return null;
}

/**
 * Возвращает текущий unix time. Для таблиц с TIMESTAMP мы используем NOW()/FROM_UNIXTIME().
 */
function newyear_now_kiev(){
    return time();
}

/** Ключ суток (Ymd) */
function newyear_day_key($ts = null){
    if($ts === null) $ts = time();
    try {
        $dt = new DateTime('@'.$ts);
        $dt->setTimezone(new DateTimeZone('Europe/Kyiv'));
        return $dt->format('Ymd');
    } catch (Throwable $e){
        return date('Ymd', $ts);
    }
}


/**
 * Вспомогательное: получить список колонок таблицы (кэшируется).
 */
function newyear_table_cols(mysqli $db, string $table): array {
    static $cache = [];
    if(isset($cache[$table])) return $cache[$table];

    // защита от инъекций в имени таблицы
    if(!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return $cache[$table] = [];

    $cols = [];
    $res = $db->query("SHOW COLUMNS FROM `$table`");
    if($res){
        while($r = $res->fetch_assoc()){
            $cols[strtolower($r['Field'])] = true;
        }
    }
    return $cache[$table] = $cols;
}

function newyear_pick_col(array $cols, array $candidates, string $fallback = ''): string {
    foreach($candidates as $c){
        $k = strtolower($c);
        if(isset($cols[$k])) return $c;
    }
    return $fallback;
}

/**
 * Гарантирует наличие таблиц новогоднего ивента.
 * ВАЖНО: у вас в дампе таблицы отличаются от "старой" реализации, поэтому используем схему из БД.
 */
function newyear_ensure_tables(){
    $db = newyear_db();
    if(!$db) return false;

    // server
    $db->query("CREATE TABLE IF NOT EXISTS `a_ivent_newyear_server` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `active_override` TINYINT(1) NOT NULL DEFAULT 0,
        `force_stop` TINYINT(1) NOT NULL DEFAULT 0,
        `start_ts` INT NOT NULL DEFAULT 0,
        `end_ts` INT NOT NULL DEFAULT 0,
        `progress` BIGINT NOT NULL DEFAULT 0,
        `target` BIGINT NOT NULL DEFAULT 0,
        `tier` INT NOT NULL DEFAULT 0,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `pause` TINYINT(1) NOT NULL DEFAULT 0,
        `mode` VARCHAR(32) NOT NULL DEFAULT 'standard',
        `mult_pve` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
        `mult_pvp` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
        `mult_gift` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
        `mult_tree` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
        `active` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `updated_at` (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // users
    $db->query("CREATE TABLE IF NOT EXISTS `a_ivent_newyear_users` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `user_id` INT NOT NULL DEFAULT 0,
        `snowflakes` BIGINT NOT NULL DEFAULT 0,
        `contribute` BIGINT NOT NULL DEFAULT 0,
        `tree_free_day` VARCHAR(16) NOT NULL DEFAULT '',
        `daily_json` TEXT NULL,
        `tier_json` TEXT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `claims` TEXT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`),
        KEY `idx_updated_at` (`updated_at`),
        KEY `idx_contribute` (`contribute`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // server row id=1
    $sv = $db->query("SELECT * FROM `a_ivent_newyear_server` WHERE `id`=1")->fetch_assoc();
    if(!$sv){
        $db->query("INSERT INTO `a_ivent_newyear_server` (`id`,`target`,`tier`,`active`) VALUES (1, 100000, 0, 0)");
    }

    return true;
}

function newyear_get_server(): array {
    $db = newyear_db();
    if(!$db) return [];
    newyear_ensure_tables();

    $sv = $db->query("SELECT * FROM `a_ivent_newyear_server` WHERE `id`=1")->fetch_assoc();
    return $sv ?: [];
}

/**
 * Активность: поддерживаем и календарное окно, и override, и ручной active.
 */
function newyear_is_active(): bool {
    $sv = newyear_get_server();
    if(!$sv) return false;

    if((int)$sv['force_stop'] === 1) return false;

    $now = time();
    // если override включен — используем start/end
    if((int)$sv['active_override'] === 1){
        $start = (int)$sv['start_ts'];
        $end   = (int)$sv['end_ts'];
        if($start > 0 && $now < $start) return false;
        if($end > 0 && $now > $end) return false;
        return (int)$sv['active'] === 1;
    }

    // ручной active + (опционально) окно по датам start/end, если задано
    if((int)$sv['active'] === 1){
        $start = (int)$sv['start_ts'];
        $end   = (int)$sv['end_ts'];
        if($start > 0 && $now < $start) return false;
        if($end > 0 && $now > $end) return false;
        return true;
    }

    // календарное окно (31.12–06.01) как fallback
    try {
        $dt = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
        $m = (int)$dt->format('n');
        $d = (int)$dt->format('j');
    } catch (Throwable $e){
        $m = (int)date('n');
        $d = (int)date('j');
    }
    if(($m === 12 && $d >= 31) || ($m === 1 && $d <= 6)){
        return true;
    }
    return false;
}

/** tier по процентам */
function newyear_calc_tier(int $progress, int $target): int {
    if($target <= 0) return 0;
    $pct = ($progress / $target) * 100.0;
    if($pct >= 100) return 5;
    if($pct >= 75)  return 4;
    if($pct >= 50)  return 3;
    if($pct >= 25)  return 2;
    if($pct >= 10)  return 1;
    return 0;
}

function newyear_ensure_user(int $userId): array {
    $db = newyear_db();
    if(!$db) return [];
    newyear_ensure_tables();

    $cols = newyear_table_cols($db, 'a_ivent_newyear_users');
    $uCol = newyear_pick_col($cols, ['user_id','user','id_user','uid'], 'user_id');

    $u = $db->query("SELECT * FROM `a_ivent_newyear_users` WHERE `$uCol`=".(int)$userId)->fetch_assoc();
    if(!$u){
        $db->query("INSERT INTO `a_ivent_newyear_users` (`$uCol`) VALUES (".(int)$userId.")");
        $u = $db->query("SELECT * FROM `a_ivent_newyear_users` WHERE `$uCol`=".(int)$userId)->fetch_assoc();
    }
    return $u ?: [];
}

/**
 * Состояние ёлки/ивента для UI.
 */
function newyear_tree_status($userId){
    $db = newyear_db();
    if(!$db) return ['active'=>0,'error'=>1,'text'=>'DB not available'];

    $userId = (int)$userId;
    newyear_ensure_tables();

    $sv = newyear_get_server();
    $active = newyear_is_active() ? 1 : 0;

    $u = newyear_ensure_user($userId);

    $uCols = newyear_table_cols($db, 'a_ivent_newyear_users');
    $snowCol = newyear_pick_col($uCols, ['snowflakes','snow'], 'snowflakes');
    $contribCol = newyear_pick_col($uCols, ['contribute','contrib'], 'contribute');
    $freeDayCol = newyear_pick_col($uCols, ['tree_free_day','tree_day'], 'tree_free_day');

    $snow = isset($u[$snowCol]) ? (int)$u[$snowCol] : 0;
    $contrib = isset($u[$contribCol]) ? (int)$u[$contribCol] : 0;

    $progress = isset($sv['progress']) ? (int)$sv['progress'] : 0;
    $target   = isset($sv['target']) ? (int)$sv['target'] : 0;
    $tier     = isset($sv['tier']) ? (int)$sv['tier'] : 0;
    if($tier === 0 && $progress > 0 && $target > 0){
        $tier = newyear_calc_tier($progress, $target);
    }

    $pct = ($target > 0) ? (int)floor(min(100, ($progress / $target) * 100)) : 0;

    $dayKey = newyear_day_key();
    $last = isset($u[$freeDayCol]) ? (string)$u[$freeDayCol] : '';
    $freeAvail = ($last !== $dayKey) ? 1 : 0;

    return [
        'active' => $active,
        'server_progress' => $progress,
        'server_target' => $target,
        'server_percent' => $pct,
        'server_tier' => $tier,
        'snowflakes' => $snow,
        'contrib' => $contrib,
        'free_available' => $freeAvail,
        'day_key' => $dayKey,
        'pause' => isset($sv['pause']) ? (int)$sv['pause'] : 0,
        'start_ts' => isset($sv['start_ts']) ? (int)$sv['start_ts'] : 0,
        'end_ts' => isset($sv['end_ts']) ? (int)$sv['end_ts'] : 0,
    ];
}

/**
 * Начисление: снежинки + вклад в серверный прогресс.
 * $action: pve_win / pvp_win / gift_send / tree_free / etc.
 */
function newyear_event_add($userId, $action, $value = 1){
    $db = newyear_db();
    if(!$db) return false;

    $userId = (int)$userId;
    $value = max(1, (int)$value);
    $action = (string)$action;

    newyear_ensure_tables();
    if(!newyear_is_active()) return false;

    $sv = newyear_get_server();

    // базовые награды
    $baseSnow = 0;
    $baseProg = 0;
    switch($action){
        case 'pve_win':   $baseSnow = 3;  $baseProg = 1;  $mult = (float)($sv['mult_pve'] ?? 1.0); break;
        case 'pvp_win':   $baseSnow = 4;  $baseProg = 2;  $mult = (float)($sv['mult_pvp'] ?? 1.0); break;
        case 'gift_send': $baseSnow = 6;  $baseProg = 2;  $mult = (float)($sv['mult_gift'] ?? 1.0); break;
        case 'tree_free': $baseSnow = 0;  $baseProg = 1;  $mult = (float)($sv['mult_tree'] ?? 1.0); break;
        default:          $baseSnow = 1;  $baseProg = 0;  $mult = 1.0; break;
    }

    $snowAdd = (int)round($baseSnow * $value * $mult);
    $progAdd = (int)round($baseProg * $value * $mult);

    $u = newyear_ensure_user($userId);

    $uCols = newyear_table_cols($db, 'a_ivent_newyear_users');
    $uCol = newyear_pick_col($uCols, ['user_id','user','id_user','uid'], 'user_id');
    $snowCol = newyear_pick_col($uCols, ['snowflakes','snow'], 'snowflakes');
    $contribCol = newyear_pick_col($uCols, ['contribute','contrib'], 'contribute');

    // атомарно
    $db->query("START TRANSACTION");
    try {
        if($snowAdd > 0){
            $db->query("UPDATE `a_ivent_newyear_users` SET `$snowCol` = `$snowCol` + $snowAdd, `$contribCol` = `$contribCol` + $snowAdd WHERE `$uCol` = $userId");
            if(function_exists('itemAdd')){
                // 1200 = снежинки
                @itemAdd(1200, $snowAdd, $userId);
            }
        } elseif($progAdd > 0){
            $db->query("UPDATE `a_ivent_newyear_users` SET `$contribCol` = `$contribCol` + $progAdd WHERE `$uCol` = $userId");
        }

        if($progAdd > 0){
            $db->query("UPDATE `a_ivent_newyear_server` SET `progress` = `progress` + $progAdd WHERE `id`=1");
        }

        // обновим tier по факту
        $sv2 = $db->query("SELECT `progress`,`target` FROM `a_ivent_newyear_server` WHERE `id`=1")->fetch_assoc();
        if($sv2){
            $tier = newyear_calc_tier((int)$sv2['progress'], (int)$sv2['target']);
            $db->query("UPDATE `a_ivent_newyear_server` SET `tier`=".(int)$tier." WHERE `id`=1");
        }

        $db->query("COMMIT");
    } catch (Throwable $e){
        $db->query("ROLLBACK");
        return false;
    }

    return true;
}

/**
 * Бесплатное действие 1 раз в сутки: начисляем случайные снежинки + шанс подарка/фейерверка.
 */
function newyear_tree_free_action($userId){
    $db = newyear_db();
    if(!$db) return ['error'=>1,'text'=>'DB not available'];

    $userId = (int)$userId;
    newyear_ensure_tables();
    if(!newyear_is_active()) return ['error'=>1,'text'=>'Ивент не активен'];

    $u = newyear_ensure_user($userId);
    $uCols = newyear_table_cols($db, 'a_ivent_newyear_users');
    $uCol = newyear_pick_col($uCols, ['user_id','user','id_user','uid'], 'user_id');
    $freeDayCol = newyear_pick_col($uCols, ['tree_free_day','tree_day'], 'tree_free_day');

    $dayKey = newyear_day_key();
    $last = isset($u[$freeDayCol]) ? (string)$u[$freeDayCol] : '';
    if($last === $dayKey){
        return ['error'=>1,'text'=>'Уже получено сегодня'];
    }

    $snow = rand(3, 10);
    newyear_event_add($userId, 'tree_free', 1);

    // дополнительно к серверному прогрессу tree_free добавим рандомную выдачу снежинок (и в инвентарь, и в таблицу)
    $snowCol = newyear_pick_col($uCols, ['snowflakes','snow'], 'snowflakes');
    $contribCol = newyear_pick_col($uCols, ['contribute','contrib'], 'contribute');

    $db->query("UPDATE `a_ivent_newyear_users` SET `$snowCol`=`$snowCol`+$snow, `$contribCol`=`$contribCol`+$snow WHERE `$uCol`=$userId");
    if(function_exists('itemAdd')){
        @itemAdd(1200, $snow, $userId);
    }
    // шанс подарка/фейерверка
    $extra = [];
    if(rand(1,100) <= 10 && function_exists('itemAdd')){
        @itemAdd(1201, 1, $userId);
        $extra[] = 'Подарок';
    }
    if(rand(1,100) <= 5 && function_exists('itemAdd')){
        @itemAdd(1202, 1, $userId);
        $extra[] = 'Фейерверк';
    }

    $db->query("UPDATE `a_ivent_newyear_users` SET `$freeDayCol`='".$db->real_escape_string($dayKey)."' WHERE `$uCol`=$userId");

    $msg = "Вы получили $snow снежинок.";
    if($extra){
        $msg .= " Дополнительно: ".implode(', ', $extra).".";
    }

    return ['error'=>0,'text'=>$msg];
}

function newyear_admin_start($days = 10){
    $db = newyear_db();
    if(!$db) return false;
    newyear_ensure_tables();
    $days = max(1, (int)$days);
    $now = time();
    $end = $now + $days*86400;
    $db->query("UPDATE `a_ivent_newyear_server`
        SET `active_override`=1, `force_stop`=0, `start_ts`=$now, `end_ts`=$end, `active`=1
        WHERE `id`=1");
    return true;
}

function newyear_admin_stop(){
    $db = newyear_db();
    if(!$db) return false;
    newyear_ensure_tables();
    $db->query("UPDATE `a_ivent_newyear_server`
        SET `active_override`=0, `active`=0, `force_stop`=1
        WHERE `id`=1");
    return true;
}

function newyear_ensure_items(){
    // базовые предметы: снежинки, подарок, фейерверк
    if(function_exists('addItemBase')){
        @addItemBase(1200, 'Снежинка', 1);
        @addItemBase(1201, 'Подарок на Новый год', 1);
        @addItemBase(1202, 'Фейерверк', 1);
        return true;
    }
    // если addItemBase нет — просто не падаем
    return true;
}
function generateTeraType($typeA, $typeB = '') {
    $all = ['bug','dark','dragon','electric','fairy','fighting','fire','fly','ghost','grass','ground','ice','normal','poison','psychic','rock','steel','water'];
    $typeA = $typeA ? $typeA : 'normal';
    $pickBase = (mt_rand(1,100) <= 65);
    if ($pickBase) {
        if ($typeB && $typeB !== '' && mt_rand(1,2) === 2) return $typeB;
        return $typeA;
    }
    return $all[array_rand($all)];
}

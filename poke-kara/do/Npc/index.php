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

// NPC endpoint is JSON-only
header('Content-Type: application/json; charset=utf-8');

// Ensure response is always an array.
// Some modern lib-driven NPC scripts use strict type-hints (array \&$response).
// If $response is not initialized, calling those scripts results in a fatal TypeError (HTTP 500).
$response = ["error" => 0];


// Auto-load all NPC libs (new NPC scripts should be written using these classes).
// Legacy helpers below remain for backward compatibility with existing NPC scripts.
$npcLibDir = __DIR__ . '/lib';
if (is_dir($npcLibDir)) {
    $libs = glob($npcLibDir . '/*.php');
    if ($libs) {
        sort($libs, SORT_STRING);
        foreach ($libs as $libFile) {
            require_once $libFile;
        }
    }
}

function isset_lvl($lvl){
  global $mysqli;
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1 AND `lvl` >= '".$lvl."'")->fetch_assoc();
  if($q){
    $a = true;
  }else{
    $a = false;
  }
  return $a;
}
function check_attack_pok($basenum,$id,$dop){
    global $mysqli;
    if(!empty($dop)) $z = $dop; else $z = ' ';
$pok_base = $mysqli->query("SELECT `attacks` FROM `user_pokemons` WHERE `basenum` = '".$basenum."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 ".$z)->fetch_assoc();
$check = false;
$arrayAtk = explode(',',$pok_base['attacks']);
if($arrayAtk[0] == $id or $arrayAtk[1] == $id or $arrayAtk[2] == $id or $arrayAtk[3] == $id) { $check = true; }
return $check;
}
function delete_pok_active($basenum,$dop=false){
  global $mysqli;
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1 AND `basenum` = '".$basenum."'")->fetch_assoc();
  if($q['item_id'] != 0){ itemAdd($q['item_id'],1); }
  if(!empty($dop)) $z = $dop; else $z = ' ';
  $a = $mysqli->query("DELETE FROM `user_pokemons` WHERE `basenum` = '".$basenum."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 ".$z);
  return $a;
}
function cool_pok_active($cool){
  global $mysqli;
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1 ");
  if($q->num_rows >= $cool){
    $a = true;
  }else{
    $a = false;
  }
  return $a;
}
function npc_active_pok($cool){
  global $mysqli;
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1 ");
  $i = $q->num_rows-$cool;
  if($i >= 1){
    $a = true;
  }else{
    $a = false;
  }
  return $a;
}
function search_pok_active_stat($basenum,$cool,$dop=false,$count=false){
  global $mysqli;
  if(!empty($dop)) $z = $dop; else $z = 0;
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `basenum` = '".$basenum."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 ");
  $i = 0;
  while($p = $q->fetch_assoc()){
      $stat = explode(',',$p['stats']);
      if($stat[$z] >= $count){
          $i++;
      }
  }
  if($i >= $cool){
    $a = true;
  }else{
    $a = false;
  }
  return $a;
}

function search_pok_active($basenum,$cool,$dop=false){
  global $mysqli;
  if(!empty($dop)) $z = $dop; else $z = ' ';
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `basenum` = '".$basenum."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 ".$z);
  if($q->num_rows >= $cool){
    $a = true;
  }else{
    $a = false;
  }
  return $a;
}
function search_pok_active_ivent($basenum,$cool,$dop=false){
  global $mysqli;
  if(!empty($dop)) $z = $dop; else $z = ' ';
  $q = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `basenum` = '".$basenum."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 ".$z);
  $q1 = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `basenum` = '".$basenum."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1 ".$z)->fetch_assoc();
  $json = $q1['birthday'];
  $obj = json_decode($json);
  if($q->num_rows >= $cool AND $obj->{'date'} > 1666883389){
    $a = true;
  }else{
    $a = false;
  }
  return $a;
}

function quest_step($id, $step, $end = null){
  if (defined('NPC_STRICT_LIB') && NPC_STRICT_LIB) {
    trigger_error('Legacy quest_step() is disabled in strict mode. Use QuestKit.', E_USER_ERROR);
  }
  global $mysqli;
  if ($step == 0) {
    return true;
  }
  $q = $mysqli->query("SELECT `step`, `end` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."' ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
  if (!$q) return false;
  if ($q['step'] != $step) return false;
  if ($end !== null && (int)$q['end'] !== (int)$end) return false;
  return true;
}
#Проверка на начало квеста
function quest_isset($id){
  if (defined('NPC_STRICT_LIB') && NPC_STRICT_LIB) {
    trigger_error('Legacy quest_isset() is disabled in strict mode. Use QuestKit.', E_USER_ERROR);
  }
  global $mysqli;
  $quest = $mysqli->query("SELECT `quest_id` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."' ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
  $a = ($quest['quest_id']?true:false);
  return $a;
}
# Обновление данных о квесте
function quest_update($id, $step, $end = false, $step_data = NULL) {
    if (defined('NPC_STRICT_LIB') && NPC_STRICT_LIB) {
        trigger_error('Legacy quest_update() is disabled in strict mode. Use QuestKit.', E_USER_ERROR);
    }
    global $mysqli;

    if ($end === false) {
        $end = '0';
    }

    if (quest_isset($id)) {
        // Если квест уже существует, обновляем `step` и `step_data`
        $stmt = $mysqli->prepare("UPDATE `user_quests` SET `step` = ?, `end` = ?, `step_data` = ? WHERE `user_id` = ? AND `quest_id` = ?");
        // step_data is INT in some schemas and TEXT/JSON in others; bind as string for maximum compatibility.
        $stmt->bind_param("iisii", $step, $end, $step_data, $_SESSION['id'], $id);
    } else {
        // Если квеста еще нет, добавляем его в БД
        $stmt = $mysqli->prepare("INSERT INTO `user_quests` (`quest_id`, `user_id`, `step`, `end`, `step_data`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $id, $_SESSION['id'], $step, $end, $step_data);
    }

    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

#Проверка на начало ивента
function events_isset($id){
	global $mysqli;
	$event = $mysqli->query("SELECT id FROM base_events WHERE user = ".$_SESSION['id']." AND event = ".$id)->fetch_assoc();
	return $event['id'] ? true : false;
}
#Обновление данных об ивенте
function events_update($id, $step){
	global $mysqli;
	if(events_isset($id)){
		$a = $mysqli->query("UPDATE base_events SET step = ".$step." WHERE user = ".$_SESSION['id']." AND event = ".$id);
	}else{
		$a = $mysqli->query("INSERT INTO base_events(event,user,step) VALUES(".$id.",".$_SESSION['id'].",".$step.")");
	}
	return $a;
}
#Проверка стадии ивента
function events_step($id, $step){
	global $mysqli;
	if($step == 0){
		$a = true;
	}else{
		$q = $mysqli->query("SELECT step FROM base_events WHERE user = ".$_SESSION['id']." AND event = ".$id)->fetch_assoc();
		$a = ($q['step'] == $step ? true : false);
	}
	return $a;
}
#Обновление принесённого кол-ва предметов на ивенте
function events_count($id, $count){
	global $mysqli;
	if(events_isset($id)){
		$mysqli->query("UPDATE base_events SET count = count + ".$count." WHERE user = ".$_SESSION['id']." AND event = ".$id);
	}
}
#Определяет наличие ивент предмета
function events_count_item($id){
	$i = Work::$sql->query("SELECT count FROM base_events WHERE user = ".$_SESSION['id']." AND event = ".$id)->fetch_assoc();
	if(!empty($i['count']) && $i['count'] > 0){
		return $i['count'];
	}else{
		return false;
	}
}

#Проверка на время нпс
function npc_time_check($id){
	if (defined('NPC_STRICT_LIB') && NPC_STRICT_LIB) {
		trigger_error('Legacy npc_time_check() is disabled in strict mode. Use NpcKit/NpcDataKit.', E_USER_ERROR);
	}
	global $mysqli;
	$q = $mysqli->query("SELECT `time` FROM `base_npc_data` WHERE `userID` = '".$_SESSION['id']."' AND `npcID` = '".$id."' ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
	$until = isset($q['time']) ? (int)$q['time'] : 0;
	$a = ($until > time() ? true : false);
return $a;
}
#Данные из квеста
function info_quest($id,$tip){
	if (defined('NPC_STRICT_LIB') && NPC_STRICT_LIB) {
		trigger_error('Legacy info_quest() is disabled in strict mode. Use QuestKit.', E_USER_ERROR);
	}
  global $mysqli;
  $quest = $mysqli->query("SELECT * FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."' ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
  $a = ($quest[$tip]?$quest[$tip]:false);
  return $a;
}
// Функция для получения актуального количества убитых покемонов
function countKillPok() {
    global $mysqli;
    $userData = $mysqli->query("SELECT countKillPok FROM users WHERE id = " . $_SESSION['id'])->fetch_assoc();
    return (int) ($userData['countKillPok'] ?? 0); // Если null, возвращаем 0
}

if(isset($_POST['type'])){
	$checkUserLoc = $mysqli->query("SELECT `location`,`status` FROM `users` WHERE `id`='".$_SESSION['id']."'")->fetch_assoc();
	if($checkUserLoc['status'] != 'free'){
		$response['error'] = 2;
		die(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
	$type = $_POST['type'];
	if($type == 'nursery'){
		$checkLocName = $mysqli->query("SELECT `name` FROM `base_location` WHERE `id`='".$checkUserLoc['location']."'")->fetch_assoc();
		if($checkLocName['name'] == 'Покецентр' || $checkLocName['name'] == 'Стадион' || $checkLocName['name'] == 'Поле для тренировок' || $checkLocName['name'] == 'Турнирная арена' || $checkLocName['name'] == 'Колизей'){
			$patch_Npc = $patch_project.'/do/Npc/nursery.php';
			require_once($patch_Npc);
		}else{
			$response['error'] = 2;
		}
	}elseif($type == 'fabian'){
		$patch_Npc = $patch_project.'/do/Npc/fabian.php';
		require_once($patch_Npc);
	}elseif($type == 'jess'){
		$patch_Npc = $patch_project.'/do/Npc/jess.php';
		require_once($patch_Npc);
	}elseif($type == 'reproduction'){
		$patch_Npc = $patch_project.'/do/Npc/reproduction.php';
		require_once($patch_Npc);
	}elseif($type == 'lombard'){
		$patch_Npc = $patch_project.'/do/Npc/lombard.php';
		require_once($patch_Npc);
	}
}elseif(isset($_POST['npc'])){
	$npcId = clearInt($_POST['npc']);
	$npcStep = $_POST['step'];
	$patch_Npc = $patch_project.'/do/Npc/'.$npcId.'.php';

	if(file_exists($patch_Npc)){
			// Location gating: supports both strict loc_id and multi-location list (locations_text).
					$npcRow = $mysqli->query("SELECT `id`,`loc_id`,`locations_text` FROM `base_npc` WHERE `id`=".(int)$npcId)->fetch_assoc();
					$checkUserLoc = $mysqli->query("SELECT `location` FROM `users` WHERE `id`=".(int)$_SESSION['id'])->fetch_assoc();
			$allowed = false;
			if($npcRow){
				$allowed = ((int)$npcRow['loc_id'] === (int)$checkUserLoc['location']);
				if(!$allowed && class_exists('NpcKit')){
					$allowed = NpcKit::isAllowedInLocation($npcRow, (int)$checkUserLoc['location']);
				}
			}
			if(!$allowed){
				$response['error'] = 2;
			}else{
				require_once($patch_Npc);
			}
	}else{
		$response['error'] = 1;
	}
}
	echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

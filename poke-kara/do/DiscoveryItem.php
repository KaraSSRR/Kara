<?php
if(isset($_POST['pokID'])){
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
$pid = clearInt($_POST['pokID']);
$type = escapeMe($_POST['type']);
$pokemon = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pid."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
$pokemon_base = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
$user = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
if($pokemon){
	switch ($type) {
		case 'ore':
			if($user['location'] == 11 && $pokemon['lvl'] >= 80 && $pokemon_base['type'] == 'ground' || $user['location'] == 11 && $pokemon['lvl'] >= 80 && $pokemon_base['type_two'] == 'ground' || $user['location'] == 11 && $pokemon['lvl'] >= 80 && $pokemon_base['type'] == 'rock' || $user['location'] == 11 && $pokemon['lvl'] >= 80 && $pokemon_base['type_two'] == 'rock'){
				$ore = $mysqli->query("SELECT * FROM `discovery` WHERE `user` = '".$_SESSION['id']."' AND `type` = 'ore' AND id_loc = 11")->fetch_assoc();
				if(!discoveryTime(11,'ore') && !$ore){
					$wait = time()+3600;
					$mysqli->query("INSERT INTO `discovery` (`user`,`id_loc`,`type`,`time`,`pok`) VALUES('".$_SESSION['id']."',11,'ore',".$wait.",'".$pid."') ");
					$mysqli->query("UPDATE `user_pokemons` SET `user_id` = 2 WHERE `user_id` = '".$_SESSION['id']."' AND `id` = ".$pid."");
					$response['html'] .= 'Ваш покемон начал добывать предметы';
					$response['error'] = 'success';
				}else{
					$response['html'] .= 'Ваш покемон уже добывает здесь предметы';
					$response['error'] = 'error';
				}
			}else{
				$response['html'] = 'Не соблюдены условия добычи';
				$response['error'] = 'error';
			}
		break;
		case 'seed':
			if($user['location'] == 30 && $pokemon['lvl'] >= 90 && $pokemon_base['type'] == 'grass' OR $pokemon_base['type_two'] == 'grass' && $user['location'] == 30 && $pokemon['lvl'] >= 90){
				$seed = $mysqli->query("SELECT * FROM `discovery` WHERE `user` = '".$_SESSION['id']."' AND `type` = 'seed' AND id_loc = 30")->fetch_assoc();
				if(!discoveryTime(30,'seed') && !$seed){
					$wait = time()+3600;
					$mysqli->query("INSERT INTO `discovery` (`user`,`id_loc`,`type`,`time`,`pok`) VALUES('".$_SESSION['id']."',30,'seed',".$wait.",'".$pid."') ");
					$mysqli->query("UPDATE `user_pokemons` SET `user_id` = 2 WHERE `user_id` = '".$_SESSION['id']."' AND `id` = ".$pid."");
					$response['html'] .= 'Ваш покемон начал добывать предметы';
					$response['error'] = 'success';
				}else{
					$response['html'] .= 'Ваш покемон уже добывает здесь предметы';
					$response['error'] = 'error';
				}
			}else{
				$response['html'] = 'Не соблюдены условия добычи';
				$response['error'] = 'error';
			}
		break;
		default:
			echo "Unknown error";
		break;
	}
}else{
	$response['html'] = 'Ошибка!';
	$response['error'] = 'error';
}
echo json_encode($response);
}
?>

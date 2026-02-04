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
$rand1 = rand(1,1000);$rand2 = rand(1,1000);$rand3 = rand(1,1000);$rand4 = rand(1,1000);$rand5 = rand(1,1000);
if(isset($_POST['id_discovery'])){
	$a = clearInt($_POST['id_discovery']);
	$b = $mysqli->query("SELECT * FROM `discovery` WHERE `id` = '".$a."' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
	$d = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
	if(!discoveryTime(11,'ore') && $b['type'] == 'ore'){
		if($b['user'] == $_SESSION['id'] && $d['location'] == $b['id_loc']){
			if($rand1 < 10){
				itemAdd(64,1);
				update_ach(22,1);
				update_ach(21,1);
				$c .= "<div class='itemIsset' onclick='issetAll(64,\"item\")' style='background-image: url(/img/world/items/little/64.png)'></div>";
			}elseif($rand1 >= 10 && $rand1 <= 700){
				update_ach(20,1);
				update_ach(22,1);
				itemAdd(63,1);
				$c .= "<div class='itemIsset' onclick='issetAll(63,\"item\")' style='background-image: url(/img/world/items/little/63.png)'></div>";
			}else{
				itemAdd(62,1);
				update_ach(22,1);
				$c .= "<div class='itemIsset' onclick='issetAll(62,\"item\")' style='background-image: url(/img/world/items/little/62.png)'></div>";
			}
			if($rand2 < 10){itemAdd(64,1); update_ach(22,1); update_ach(21,1); $c .= "<div class='itemIsset' onclick='issetAll(64,\"item\")' style='background-image: url(/img/world/items/little/64.png)'></div>";}elseif($rand2 >= 10 && $rand2 <= 700){itemAdd(63,1); update_ach(20,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(63,\"item\")' style='background-image: url(/img/world/items/little/63.png)'></div>";}else{itemAdd(62,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(62,\"item\")' style='background-image: url(/img/world/items/little/62.png)'></div>";}
			if($rand3 < 10){itemAdd(64,1); update_ach(22,1); update_ach(21,1); $c .= "<div class='itemIsset' onclick='issetAll(64,\"item\")' style='background-image: url(/img/world/items/little/64.png)'></div>";}elseif($rand3 >= 10 && $rand3 <= 700){itemAdd(63,1); update_ach(20,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(63,\"item\")' style='background-image: url(/img/world/items/little/63.png)'></div>";}else{itemAdd(62,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(62,\"item\")' style='background-image: url(/img/world/items/little/62.png)'></div>";}
			if($rand4 < 10){itemAdd(64,1); update_ach(22,1); update_ach(21,1); $c .= "<div class='itemIsset' onclick='issetAll(64,\"item\")' style='background-image: url(/img/world/items/little/64.png)'></div>";}elseif($rand4 >= 10 && $rand4 <= 700){itemAdd(63,1); update_ach(20,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(63,\"item\")' style='background-image: url(/img/world/items/little/63.png)'></div>";}else{itemAdd(62,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(62,\"item\")' style='background-image: url(/img/world/items/little/62.png)'></div>";}
			if($rand5 < 10){itemAdd(64,1); update_ach(22,1); update_ach(21,1); $c .= "<div class='itemIsset' onclick='issetAll(64,\"item\")' style='background-image: url(/img/world/items/little/64.png)'></div>";}elseif($rand5 >= 10 && $rand5 <= 700){itemAdd(63,1); update_ach(20,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(63,\"item\")' style='background-image: url(/img/world/items/little/63.png)'></div>";}else{itemAdd(62,1); update_ach(22,1); $c .= "<div class='itemIsset' onclick='issetAll(62,\"item\")' style='background-image: url(/img/world/items/little/62.png)'></div>";}
			$response['prize'] = 'Ваш покемон добыл для вас: <div class="itemDiscovery">'.$c.'</div>';
			if(!$c){
				$response["html"] = "Ваш покемон не добыл предметы";
				}else{
				$response["html"] = "Ваш покемон добыл несколько предметов";
				}
			$response["error"] = "success";
			$mysqli->query("UPDATE `user_pokemons` SET `user_id` = ".$_SESSION['id']." WHERE `user_id` = 2 AND `id` = ".$b['pok']."");
			$mysqli->query("DELETE FROM `discovery` WHERE `id` = '".$a."'");
		}else{
			$response["html"] = "Ошибка!";
			$response["error"] = "error";
		}
	}elseif(!discoveryTime(30,'seed') && $b['type'] == 'seed'){
		if($b['user'] == $_SESSION['id'] && $d['location'] == $b['id_loc']){
			if($rand1 >= 1 && $rand1 <= 300){itemAdd(14,1); $c .= "<div class='itemIsset' onclick='issetAll(14,\"item\")' style='background-image: url(/img/world/items/little/14.png)'></div>";}else{itemAdd(12,1); $c .= "<div class='itemIsset' onclick='issetAll(12,\"item\")' style='background-image: url(/img/world/items/little/12.png)'></div>";}
			if($rand2 >= 1 && $rand2 <= 300){itemAdd(14,1); $c .= "<div class='itemIsset' onclick='issetAll(14,\"item\")' style='background-image: url(/img/world/items/little/14.png)'></div>";}else{itemAdd(12,1); $c .= "<div class='itemIsset' onclick='issetAll(12,\"item\")' style='background-image: url(/img/world/items/little/12.png)'></div>";}
			if($rand3 >= 1 && $rand3 <= 300){itemAdd(14,1); $c .= "<div class='itemIsset' onclick='issetAll(14,\"item\")' style='background-image: url(/img/world/items/little/14.png)'></div>";}else{itemAdd(12,1); $c .= "<div class='itemIsset' onclick='issetAll(12,\"item\")' style='background-image: url(/img/world/items/little/12.png)'></div>";}
			if($rand4 >= 1 && $rand4 <= 300){itemAdd(14,1); $c .= "<div class='itemIsset' onclick='issetAll(14,\"item\")' style='background-image: url(/img/world/items/little/14.png)'></div>";}else{itemAdd(12,1); $c .= "<div class='itemIsset' onclick='issetAll(12,\"item\")' style='background-image: url(/img/world/items/little/12.png)'></div>";}
			if($rand5 >= 1 && $rand5 <= 300){itemAdd(14,1); $c .= "<div class='itemIsset' onclick='issetAll(14,\"item\")' style='background-image: url(/img/world/items/little/14.png)'></div>";}else{itemAdd(12,1); $c .= "<div class='itemIsset' onclick='issetAll(12,\"item\")' style='background-image: url(/img/world/items/little/12.png)'></div>";}
			$response['prize'] = 'Ваш покемон добыл для вас: <div class="itemDiscovery">'.$c.'</div>';
			if(!$c){
				$response["html"] = "Ваш покемон не добыл предметы";
				}else{
				$response["html"] = "Ваш покемон добыл несколько предметов";
				}
			$response["error"] = "success";
			$mysqli->query("UPDATE `user_pokemons` SET `user_id` = ".$_SESSION['id']." WHERE `user_id` = 2 AND `id` = ".$b['pok']."");
			$mysqli->query("DELETE FROM `discovery` WHERE `id` = '".$a."'");
		}else{
			$response["html"] = "Ошибка!";
			$response["error"] = "error";
		}
	}else{
		$response["html"] = "Покемон все еще добывает предметы";
		$response["error"] = "error";
	}
	die(json_encode($response));
}
$UserQuery = $mysqli->query("SELECT `location` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
$pokc = $mysqli->query('SELECT `id`, `basenum`, `name_new`, `lvl` FROM `user_pokemons` WHERE `active` = 1 AND user_id = '.$_SESSION['id'].'');
while($pok = $pokc->fetch_assoc()){
	$list.= '<option value="'.$pok['id'].'">#'.numbPok($pok['basenum']).' '.$pok['name_new'].' '.$pok['lvl'].' ур.</option>';
}
$loc = $UserQuery['location'];
$type = $_POST["category"];
$locname = $mysqli->query("SELECT * FROM `base_location` WHERE `id` = '".$loc."'")->fetch_assoc();
switch ($type) {
		case 'ore':
				$a = '<div class="discovery">
					<span>На разных локациях вы можете добыть следующие предметы:</span><br>
          <div class="it"><img class="item" src="/img/world/items/little/56.png" onclick="issetAll(56,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/57.png" onclick="issetAll(57,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/58.png" onclick="issetAll(58,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/59.png" onclick="issetAll(59,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/60.png" onclick="issetAll(60,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/61.png" onclick="issetAll(61,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/62.png" onclick="issetAll(62,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/63.png" onclick="issetAll(63,\'item\')"><span>90%</span></div>
          <div class="it"><img class="item" src="/img/world/items/little/64.png" onclick="issetAll(64,\'item\')"><span>90%</span></div>';
      if($loc == 11){ $a .=  '<div class="mn-btn" onclick="discoveryCategory(\'oreGo\',1)"><i class="fas fa-search-location"></i>Отправить покемона</div>'; }
      $response['html'] = $a;
		break;
		//case 'seed':
		//	if($loc == 30){
		//		$response['html'] = '
		//		<div class="discovery">
		//			<span>В этой локации можно найти следующие семена:</span>
	//				<div class="item"><img src="img/world/items/little/12.png"> <span>Красный априкорн</span> <div class="chanse">часто</div></div>
		//			<div class="item"><img src="img/world/items/little/14.png"> <span>Синий априкорн</span> <div class="chanse">средне</div></div>
		//			<div class="mn-btn" onclick="discoveryCategory(\'seedGo\')">Выбрать покемона для добычи</div>
	//			</div>';
		//	}else{
	//			$response['html'] = '<div class="preview">На данной локации найти ничего нельзя!</div>';
		//	}
	//	break;
		case 'oreGo':
			if($loc == 11){

				$response['html'] = '
        <div class="Name">'.$locname['name'].'</div>
        <div class="Conditions">
          Условия:<br>
          Покемон должен иметь <b>каменный</b> или <b>земляной</b> тип.<br>
          Покемон должен быть <b>90</b> или выше уровня.<br>
          На 1 локации может работать только 1 покемон.
        </div>
				<div class="About">
					<form class="evolNpcForm" onsubmit="discoveryGo(\'ore\');return false;"">
						<select id="pokID">'.$list.'</select>
						<input class="mn-btn" type="submit" value="Выбрать">
					</form>
				</div>';
			}else{
				$response['html'] = '<div class="preview">На данной локации найти ничего нельзя!</div>';
			}
		break;
		case 'seedGo':
			if($loc == 30){
				$response['html'] = '
				<div class="discovery">
					<span>Условия добычи на этой локации:<br>1. <b>Нужен покемон травяного типа.</b><br>2. <b>Покемон должен быть больше 90 ур.</b><br>Больше одного покемона отправлять на добычу нельзя!</span>
					<form class="evolNpcForm" onsubmit="discoveryGo(\'seed\');return false;"">
						<select id="pokID">'.$list.'</select>
						<input class="mn-btn" type="submit" value="Выбрать">
					</form>
				</div>';
			}else{
				$response['html'] = '<div class="preview">На данной локации найти ничего нельзя!</div>';
			}
		break;
		case 'mypok':
			$response['html'] .= '
				<div class="discovery">
					<span>Ваши покемоны добытчики:</span>
				</div>';
      $discoveryQuery = $mysqli->query("SELECT * FROM `discovery` WHERE `user` = '".$_SESSION['id']."'");
      while($a = $discoveryQuery->fetch_assoc()){
				$b = $mysqli->query("SELECT `name` FROM `base_location` WHERE `id` = '".$a['id_loc']."'")->fetch_assoc();
				$response['html'] .= '
				<div class="pokemonDiscovery" style="background-image: url(/img/pokemons/sprite/normal/'.numCheck($a['pok']).'.gif);">
					<div class="LocationName">'.$b['name'].'</div>
          <div class="Timers"> 5 числа в 20:34</div>
					<div class="mn-btn" onclick="giveDiscovery('.$a['id'].')">Забрать</div>
				</div>
				';
			}
		break;
		default:
			echo "Unknown error";
		break;
	}
echo json_encode($response);
?>

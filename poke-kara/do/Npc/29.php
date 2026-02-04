<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Артикуно';
	switch($npcStep){
		
		
		default:
		if(quest_step(6,6) or !item_isset(72,1)){
		    $user = $mysqli->query("SELECT * FROM users WHERE id = ".$_SESSION['id'])->fetch_assoc();
		    if($user['status'] == 'free') {
	        $mysqli->query("UPDATE `users` SET `status`='battle' WHERE `id`='".$_SESSION['id']."'");
	        $response['question'] = '<i> ( Вы натыкаетесь на покемона, который резко поворачивается на вас и начинает свою атаку! ) </i>';
	        $location_id = $mysqli->query("SELECT `id`,`login`,`user_group`,`region`,`location`,`sex`,`ban`,`status`,`status_id`,`rating`,`rang`,`botID`,`sprite` FROM `users` WHERE `id`='".$_SESSION['id']."'")->fetch_assoc();
	        Info::_generatePve($location_id, $location_id['location'], null, 3);
		    }else{
		      $response['question'] = 'Вы заняты.';
		    }
		}else{
		    $response['question'] = 'Ошибка';
		}
		break;
	}
?>

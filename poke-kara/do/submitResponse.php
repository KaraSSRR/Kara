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
$type = $_POST["type"];
$data = $_POST['data'];
$user = clearInt($_POST['user']);
$user = $mysqli->real_escape_string($user);
switch ($type) {
	case 'friend':
		$friends = $mysqli->query("SELECT * FROM `users_friend` WHERE `user_id` = '".$user."' AND `friend_id` = '".$_SESSION['id']."'");
		if($friends->num_rows > 0){
			if($data == 1){
				$response['error'] = 0;
				$response['text'] = 'Дружба принята!';
				update_ach(2,1);
				$mysqli->query("UPDATE `users_friend` SET `status` = '1' WHERE `friend_id` = '".$_SESSION['id']."' AND `user_id` = '".$user."'");
				// $users = $mysqli->query("SELECT `login`,`user_group` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
				// $text = '<div class="user-link u-'.$users['user_group'].'">'.$users['login'].'</div> принял заявку в друзья.';
				// $img = '/img/avatars/mini/'.$_SESSION['id'].'.png';
				// $month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
				// $dayToday = date("d");
				// $monthToday = $month[date("n")];
				// $YearToday = date("Y");
				// $YearToday = date("Y");
				// $YearToday = date("Y");
				// $date = $dayToday.' '.$monthToday.' '.$YearToday.'г. в '.date("H").':'.date("i");
				// $mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$text."','".$user."','".$img."','".$date."')");
				news_friend(4,$_SESSION['id'],$user);
				news_friend(4,$user,$_SESSION['id']);
			}else{
				$response['error'] = 1;
				$response['text'] = 'Дружба отклонена!';
				$mysqli->query("DELETE FROM `users_friend` WHERE `friend_id` = '".$_SESSION['id']."' AND `user_id` = '".$user."'");
			}
		}else{
			$response['error'] = 1;
			$response['text'] = 'Ошибка запроса!';
		}
	break;
	case 'clan':
		$a = $mysqli->query("SELECT * FROM `user_clan_accept` WHERE `user_id` = ".$_SESSION['id'])->fetch_assoc();
		if($a){
			if($data == 1){
				$clan = $mysqli->query("SELECT * FROM `base_clans_users` WHERE `user_id` = ".$_SESSION['id'])->fetch_assoc();
				$clan1 = $mysqli->query("SELECT * FROM `user_clan_accept` WHERE `user_id` = ".$_SESSION['id'])->fetch_assoc();
				$users = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id'])->fetch_assoc();
				if($clan){
					$response['error'] = 1;
					$response['text'] = 'Вы уже состоите в клане.';
				}else{
					$response['error'] = 0;
					$response['text'] = 'Вы вступили в клан.';
					$insertJson = '{"user_new":"'.$users['login'].'","user_group":"'.$users['user_group'].'","date":"'.date('d.m.Y').'"}';
					$mysqli->query("INSERT INTO `log_game` (`user_id`,`type`,`title`,`info`) VALUES ('".$clan1['clan_id']."','clan','ADD_CLAN_USER','".$insertJson."')");
					$mysqli->query("INSERT INTO `base_clans_users` (`user_id`,`clan_id`,`raiting`,`status`) VALUES ('".$_SESSION['id']."','".$clan1['clan_id']."','0','Новобранец')");
					$mysqli->query("DELETE FROM `user_clan_accept` WHERE `user_id` = ".$_SESSION['id']);
				}
			}else{
				$response['error'] = 1;
				$response['text'] = 'Заявка отклонена.';
				$mysqli->query("DELETE FROM `user_clan_accept` WHERE `user_id` = ".$_SESSION['id']);
			}
		}
	break;
	case 'trade':
			if($data == 1){
				$response['error'] = 0;
				$response['text'] = 'Обмен начался!';
				$user1 = $mysqli->query("SELECT `location`,`status` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
				$user2 = $mysqli->query("SELECT `location`,`status` FROM `users` WHERE `id` = '".$user."'")->fetch_assoc();
				if($user1['location'] != $user2['location']){
					$response['error'] = 1;
					$response['text'] = 'Вы слишком далеко!';
				}elseif($user1['status'] != 'free'){
					$response['error'] = 1;
					$response['text'] = 'Сейчас вы не можете начать обмен!';
				}else{
					$mysqli->query("UPDATE `users` SET `status` = 'trade' WHERE `id` = '".$_SESSION['id']."'");
					$mysqli->query("UPDATE `users` SET `status` = 'trade' WHERE `id` = '".$user."'");
					$mysqli->query("UPDATE `users_trade` SET `status` = 1 WHERE `user1` = '".$user."'");	
					$response['type'] = 'trade';
				}
			}else{
				$response['error'] = 1;
				$response['text'] = 'Обмен отклонён!';
				$mysqli->query("DELETE FROM `users_trade` WHERE `user2` = '".$_SESSION['id']."'");
				$mysqli->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$user."'");
				$mysqli->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$_SESSION['id']."'");
			}
	break;
	default:
		echo 'Error';
	break;
}
echo json_encode($response);
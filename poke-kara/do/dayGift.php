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

$user = $mysqli->query('SELECT `LastPrize` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();

	$gifts = $mysqli->query('SELECT * FROM `dayGift`');

	$giftsList = [];
	if(!empty($gifts)){
		while ($gift = $gifts->fetch_assoc()){
			$giftsList[] = [
				'id'	=>$gift['item'],
				'count'	=>$gift['count']
			];
		}
	}
  $carusel = array_merge($giftsList, $giftsList);
  shuffle($carusel);
	if($user['LastPrize'] < date('Y-m-d')){
    $day = 1;
	}else{
		$day = 0;
	}
	
	$us = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_to` = '".$_SESSION['id']."' AND `active` = 0")->fetch_assoc();
		if($us){
		    $ts = "shakeGift";
		    $ts2 = 'onclick="issetAll(1,\'GiftUsFr\')"';
		}else{
		    $ts = "";
		    $ts2 = "";
		}

	$return = [
				'list'=>$giftsList,
				'carusel'=>$carusel,
				'ts'=>$ts,
				'ts2'=>$ts2,
        'day'=>$day
			];

echo json_encode($return);
?>

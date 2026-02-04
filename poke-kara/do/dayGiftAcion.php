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

if($user['LastPrize'] < date('Y-m-d')){
$rand = rand(1,50);

$gifts = $mysqli->query('SELECT * FROM `dayGift` WHERE `chanse` > '.$rand.' ORDER BY RAND() LIMIT 1')->fetch_assoc();

$info = $mysqli->query('SELECT `name` FROM `base_items` WHERE `id` = '.$gifts['item'])->fetch_assoc();

	$return = [
			'id' => $gifts['item'],
			'name' => $info['name'],
			'count' => $gifts['count']
		];
    update_achiv(31,1);
	itemAdd($gifts['item'],$gifts['count']);
		$mysqli->query('UPDATE `users`
					SET
						`LastPrize` = "'.date('Y-m-d').'"
					WHERE
						`id` = '.$_SESSION['id']);
}else{
	$return = [
			'id' => 0
		];
}

echo json_encode($return);
?>

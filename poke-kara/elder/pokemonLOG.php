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
// Проверка доступа по ID (только для пользователя с id=4)
session_start();
if (!isset($_SESSION['id']) || $_SESSION['id'] != 4) {
    http_response_code(403);
    die('<div style="margin:60px auto;max-width:420px;padding:36px 22px 30px 22px;background:#fff6;border-radius:19px;box-shadow:0 6px 32px #7050c022;font-family:Nunito,Arial,sans-serif;text-align:center;">
        <span style="display:block;font-size:3.4em;line-height:1;color:#caa2e6;">⛔</span>
        <div style="font-size:1.25em;color:#a184ca;font-weight:bold;margin:9px 0 13px 0;">Доступ запрещён</div>
        <div style="color:#8160a0;font-size:1em;">У вас нет прав для просмотра этой страницы.</div>
        </div>');
}


?>
<head>
    <title>Poke-Route</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable = no">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
    <?
    $log = $mysqli->query('SELECT * FROM `log_pokemon` ORDER BY `id` ASC');
				while($l = $log->fetch_assoc()){
				    if($l['type'] == 'pok'){
				        echo 'Выдан покемон '.$l['pok'].' под id'.$l['pok_id'].' <small>'.$l['date'].'</small><br>';
				    }
				    if($l['type'] == 'items'){
				        echo 'Выдан предмет '.$l['pok'].' тренеру id'.$l['pok_id'].' <small>'.$l['date'].'</small><br>';
				    }
				    if($l['type'] == 'attack'){
				        echo 'Выдана атака '.$l['pok'].' покемону id'.$l['pok_id'].' <small>'.$l['date'].'</small><br>';
				    }
				}
    
    ?>
</body>
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
if($_POST['op'] == 1){
    $mysqli->query('INSERT INTO `opros` (`user`,`text`) VALUES ('.$_SESSION['id'].',"'.escapeMe($_POST['text']).'")');
    $mysqli->query("UPDATE `users` SET `opros` = 0 WHERE `id` = ".$_SESSION['id']); 
}else{
    $mysqli->query("UPDATE `users` SET `opros` = 0 WHERE `id` = ".$_SESSION['id']); 
}

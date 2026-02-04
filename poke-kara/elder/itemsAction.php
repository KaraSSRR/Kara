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

if(isset($_POST['name'])){
    $name = $_POST['name'];
    $mysqli->query("INSERT INTO `pointer_category` (`name`) VALUES ('".$name."')");
    $response['text'] = 'Категория создана!';
    $response['error'] = 'success';
}
if(isset($_POST['item'])){
    $item = $_POST['item'];
    $id = $_POST['cat'];
    $s = $mysqli->query('SELECT * FROM `pointer_item` WHERE `item` = "'.$item.'" ')->fetch_assoc();
	if($s){
	    if($s['category'] != $id){
	        $mysqli->query("UPDATE `pointer_item` SET `category`= ".$id." WHERE `item`= '".$item."'");
	    }
	}else{
	    $mysqli->query("INSERT INTO `pointer_item` (`item`,`category`) VALUES ('".$item."','".$id."')");
	}
    $response['text'] = 'Предмет изменил категорию!';
    $response['error'] = 'success';
}
if(isset($_POST['delete'])){
    $id = $_POST['delete'];
    $mysqli->query("DELETE FROM `pointer_category` WHERE `id` = '".$id."'  ");
    $mysqli->query("DELETE FROM `pointer_item` WHERE `category` = '".$id."'  ");
    $response['text'] = 'Категория удалена!';
    $response['error'] = 'success';
}





echo json_encode($response);
?>
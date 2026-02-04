<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        _setError('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}


if(!empty($_POST['val'])){
    $value = $_POST['val'];
    $batk = $mysqli->query('SELECT * FROM `user_notes` WHERE `id` = "'.$_SESSION['id'].'" ')->fetch_assoc();
    if($batk){
        $mysqli->query('UPDATE `user_notes` SET `notes` = "'.$value.'" WHERE `id` = '.$_SESSION['id']);
    }else{
        $mysqli->query("INSERT INTO `user_notes` (`id`,`notes`) VALUES('".$_SESSION['id']."','".$value."') ");
    }
    
    $response['text'] = "Заметка успешно изменена!";
    $response['error'] = "success";
}


echo json_encode($response);
?>
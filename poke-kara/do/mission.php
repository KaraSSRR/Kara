<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        _setError('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}
if(!empty($_POST['id'])){
    $id = escapeMe($_POST['id']);
    $response['text'] = $id;
}else{
    $response['text'] = 'NULL';
}























echo json_encode($response);
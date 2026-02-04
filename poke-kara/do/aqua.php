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
ini_set('display_errors', 'ON');
error_reporting(E_ALL);

$userInfo = $mysqli->query('SELECT * FROM `users` WHERE `id`='.$_SESSION['id'])->fetch_assoc();

if(!empty($_POST['id'])) {

  switch($_POST['id']) {

    case 'edit':
      $response = [];
      new Edit($_POST['type'], $_POST['val'], $userInfo, $response);
      Work::_setStrongInfo($response);
    break;

  }

}
Work::_viewOut();

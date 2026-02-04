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
$type = $_POST['type'];
switch($type){
    case 'open':
        $response['html'] = 'Выбери предмет для улучшения! <div class="selectItem"><select class="revival_item"><option id="1">aa</option><option id="1">aa</option><option id="1">aa</option></select></div> ';
        $response['error'] = 0;
    break;
    default:
        $response['html'] = "Неизвестная ошибка!";
        $response['error'] = 1;
}


echo json_encode($response);
?>
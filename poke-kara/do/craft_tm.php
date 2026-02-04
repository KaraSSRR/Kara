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
$category = $_POST["category"];

switch ($category) {
	case 'selectType':
	    $type = escapeMe($_POST["type"]);
	    $bd = $mysqli->query('
									SELECT
    								    `bi`.`tm_id`,
    								    `bi`.`name`,
    								    `ba`.`id`,
    								    `ba`.`type`

									FROM `base_items` AS `bi`

									INNER JOIN `base_atk` AS `ba`
										ON
											`bi`.`tm_id` = `ba`.`id`
									WHERE
											`bi`.`type` = "tm"
											AND
											`ba`.`type` = "'.$type.'"
									');
	    switch($type){
	       case 'normal':
	            $tpl .= '<div class="imgType" onclick="openModal(\'crafttm\')"><img src="/img/world/typs/normal.png"></div>';
	            while($atk = $bd->fetch_assoc()){
	                $tpl .= $atk['name'];
	            }
	            $response['html'] = $tpl;
	       break;
	       case 'bug':
	            $response['html'] = "Все открыто успешно";
	       break;
	       default:
	           $response['error'] = 1;
	           $response['text'] = "ТМ/TR Атаки для этого типа еще не добавлены";
	    }
	break;
}

echo json_encode($response);
?>
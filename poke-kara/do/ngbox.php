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
 $type = escapeMe($_POST["type"]);
 if(empty($response)){
     $response = [];
 }
 die();
 switch ($type) {
     case 'box':
         $count = 0;
         $count_line = 150;


         $where = '';
         $rare = 0;
         if(!empty($_POST['rare']) && $_POST['rare'] > 0 && $_POST['rare'] < 4){
             $count_line = 300;
             $rare = intval($_POST['rare']);
             $where = ' AND `ng`.`type` = '.$rare.' ';
         }

         $title = [
             'Последние '.$count_line.' открытых подарков',
             'Последние '.$count_line.' обычных призов',
             'Последние '.$count_line.' редких призов',
             'Последние '.$count_line.' уникальных призов',
         ];

         $ng = $mysqli->query("SELECT
 							`ng`.`prize`,
 							`us`.`user_group`,
 							`us`.`login` 
 							FROM `ngBox` AS `ng`
 							INNER JOIN `users` AS `us`
							ON `us`.`id` = `ng`.`user`
 							WHERE `us`.`user_group` != 1 
 							".$where."
 							ORDER BY `ng`.`id` DESC LIMIT 0,".$count_line);

         if(empty($_POST['ref'])){
             $response['html'] .= '<div class="model">';
         }


         $response['html'] .= '
 				<div class="header">Подарки 
 				'.($rare > 0 ? '<div style="cursor: pointer; display: inline-block; font-size: 14px; margin-left: 13px;" onclick="ngBox(\'box\', 1, 0);">ВСЕ</div>' : '').'
 				<div style="cursor: pointer; display: inline-block; font-size: 12px; margin-left: 15px;" onclick="ngBox(\'box\', 1, 1);">Обычные</div> | 
 				<div style="cursor: pointer; display: inline-block; font-size: 12px;" onclick="ngBox(\'box\', 1, 2);">Редкие</div> | 
 				<div style="cursor: pointer; display: inline-block; font-size: 12px;" onclick="ngBox(\'box\', 1, 3);">Уникальные</div>
 				<span class="ref" onclick="ngBox(\'box\', 1, '.$rare.');"><img src="/img/world/100487.png" style="width: 18px;margin: 6px;"></span>
 				<span onclick="$(\'.model\').remove();"><img src="/img/world/close.png"></span></div>
 			<div class="content-model">
 				<div class="prize">
 					<div class="name">'.$title[$rare].':</div>
 					';
         while($a = $ng->fetch_assoc()){
             $response['html'] .='<div class="item" style="font-size: 11px;">'.(++$count).'. <div class="user-link u-'.$a['user_group'].'">'.$a['login'].'</div> получил '.$a['prize'].'</div>';
         }
         $response['html'] .='
 				</div>
 			</div>';

         if(empty($_POST['ref'])){
             $response['html'] .= '</div>';
         }

         break;
     case 'snow':
         $count = 0;

         $rare = 0;
         if(!empty($_POST['rare']) && $_POST['rare'] > 0 && $_POST['rare'] < 4){
             $rare = $_POST['rare'];
             $ng = $mysqli->query("SELECT

 							SUM(`sb`.`count_ball`) AS `count_ball_all`,
						  								
 							`us2`.`user_group`,
 							`us2`.`login`
							
 							FROM `snowballs` AS `sb`
 							INNER  JOIN `users` AS `us2` ON `us2`.`id` = `sb`.`touser`
							
							WHERE `us2`.`user_group` > 1 
							GROUP BY `sb`.`touser`
 							ORDER BY `count_ball_all` DESC LIMIT 0,10");

         }else{
             $ng = $mysqli->query("SELECT

 							`sb`.`count_ball`,
							
 							`us`.`user_group`,
 							`us`.`login`,
							
 							`us2`.`user_group` AS `user_group_2`,
 							`us2`.`login`  AS `login_2`
							
 							FROM `snowballs` AS `sb`
 							INNER JOIN `users` AS `us`  ON `us`.`id` = `sb`.`user_id`
 							INNER  JOIN `users` AS `us2` ON `us2`.`id` = `sb`.`touser`
							
 							WHERE `us`.`user_group` > 1 
 							ORDER BY `sb`.`count_ball` DESC LIMIT 0,10");
         }


         if(empty($_POST['ref'])){
             $response['html'] .= '<div class="model">';
         }


         $response['html'] .= '
 				<div class="header"> 
			   
 			    <div style="cursor: pointer; display: inline-block;" onclick="ngBox(\'snow\', 1, 0);">Забияки</div>
			    
 			    <div style="cursor: pointer; display: inline-block; margin-left: 20px;" onclick="ngBox(\'snow\', 1, 1);">МИШЕНИ</div>

 				<span class="ref" onclick="ngBox(\'snow\', 1, '.$rare.');"><img src="/img/world/100487.png" style="width: 18px;margin: 6px;"></span><span onclick="$(\'.model\').remove();"><img src="/img/world/close.png"></span></div>
 			<div class="content-model">
 				<div class="prize">
 					<div class="name">'.($rare > 0 ? 'Самые терпиливые мишени' : 'Самые активные забияки').':</div>
 					';
         while($a = $ng->fetch_assoc()){
             if($rare > 0){

                $response['html'] .='<div class="item" style="font-size: 11px;">'.(++$count).'. <b><div class="user-link u-'.$a['user_group'].'">'.$a['login'].'</div> пережили метаний на </b> ('.$a['count_ball_all'].' оч.)</div>';

             }else{

                 $response['html'] .='<div class="item" style="font-size: 11px;">'.(++$count).'. <b><div class="user-link u-'.$a['user_group'].'">'.$a['login'].'</div> закидал <div class="user-link u-'.$a['user_group_2'].'">'.$a['login_2'].'</div></b> ('.$a['count_ball'].' оч.)</div>';

             }
         }
         $response['html'] .='
 				</div>
 			</div>';

         if(empty($_POST['ref'])){
             $response['html'] .= '</div>';
         }
         break;
     default:
         echo "Unknown error";
         break;
 }
 echo json_encode($response);
?>
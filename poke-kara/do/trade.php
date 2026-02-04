<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
$patch_makasimka = $patch_project.'/makasimka/';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
        require_once($patch_func);
    }
}

$type = $_POST["type"];
$id = clearInt($_POST['id']);
$id = $mysqli->real_escape_string($id);
$response['error'] = 0;
$response_makasimka = '';
switch ($type) {
    case 'send':
        /*$user1 = $mysqli->query("SELECT `status`,`location` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
        $user2 = $mysqli->query("SELECT `status`,`location`,`online` FROM `users` WHERE `id` = '".$id."'")->fetch_assoc();
        if($user1['location'] != $user2['location']){
            $response['error'] = 1;
            $response['text'] = 'Тренер слишком далеко!';
        }elseif($user2['online'] < time()-300){
            $response['error'] = 1;
            $response['text'] = 'Тренер не в сети!';
        }elseif($user1['status'] !== 'free'){
            $response['error'] = 1;
            $response['text'] = 'Сейчас не можете предлогать обмен!';
        }elseif($user2['status'] !== 'free'){
            $response['error'] = 1;
            $response['text'] = 'Тренер занят!';
        }else{
            $trade = $mysqli->query("SELECT * FROM `users_trade` WHERE (`user1` = '".$_SESSION['id']."' OR `user2` = '".$_SESSION['id']."') ")->fetch_assoc();
            if(!$trade['id']){
                $mysqli->query("INSERT INTO `users_trade` (`user1`,`user2`) VALUE ('".$_SESSION['id']."','".$id."')");
                $response['text'] = 'Запрос успешно отправлен!';
            }else{
                $response['text'] = 'Запрос успешно отправлен!';
            }
        }*/
        break;
    case 'check':
        /*$trade = $mysqli->query("SELECT `id` FROM `users_trade` WHERE (`user1` = '".$_SESSION['id']."' OR `user2` = '".$_SESSION['id']."') AND `status` = 1");

        if($trade->num_rows > 0){
            $response['trade'] = 1;
        }*/

        break;
    case 'exit':
        /*$trade = $mysqli->query("SELECT `id`,`user1`,`user2` FROM `users_trade` WHERE (`user1` = '".$_SESSION['id']."' OR `user2` = '".$_SESSION['id']."') AND `status` = 1")->fetch_assoc();
        if($trade['id']){
            $mysqli->query("DELETE FROM `users_trade` WHERE (`user1` = '".$_SESSION['id']."' OR `user2` = '".$_SESSION['id']."') AND `status` = 1");
            $mysqli->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$trade['user1']."'");
            $mysqli->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$trade['user2']."'");
            $response['trade'] = 0;
        }**/
        break;
    case 'view':
/*
        $trade = $mysqli->query("
              SELECT * 
              FROM `users_trade` 
              WHERE 
                (
                    `user1` = '".$_SESSION['id']."' 
                  OR 
                    `user2` = '".$_SESSION['id']."'
                ) 
                AND 
                    `status` = 1
            ")->fetch_assoc();

        if(!empty($trade['id'])){
            new Trade($mysqli, $trade, $response_makasimka);
        }
*/
        break;
    case 'action':
    $trade = $mysqli->query("SELECT * FROM `users_trade` WHERE (`user1` = '".$_SESSION['id']."' OR `user2` = '".$_SESSION['id']."') AND `status` = 1")->fetch_assoc();
    if(!$trade['id']){
        $response['error'] = 1;
        $response['text'] = 'Ошибка обмена!';
    }else{
        if($_POST['typePut'] == 'item'){
            $count = clearInt(isset($_POST['count']) ? $_POST['count'] : 1);
            if(!item_isset($id, $count)){
                $response['error'] = 1;
                $response['text'] = 'Недостаточно предметов!';
            }else{
                $baseItems = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = '".$id."'")->fetch_assoc();
                $info = array(
                    'id'=>$id,
                    'name'=>$baseItems['name'],
                    'count'=>$count
                );
                if($trade['user1'] == $_SESSION['id']){
                    $check = $mysqli->query("SELECT * FROM `tradeList` WHERE `tradeID` = '".$trade['id']."' AND `item` = '".$id."' AND `user1` = '".$trade['user1']."'")->fetch_assoc();
                    if($check){
                        $mysqli->query("UPDATE `tradeList` SET `count` = `count`+'".$count."' WHERE `tradeID` = '".$trade['id']."' AND `item` = '".$id."' AND `count` > 0 AND `user1` = '".$trade['user1']."'");
                        $response['update'] = 1;
                        $info['count'] = $check['count']+$count;
                    }else{
                        $mysqli->query("INSERT INTO `tradeList` (`tradeID`,`user1`,`user2`,`item`,`count`) VALUE ('".$trade['id']."','".$trade['user1']."','".$trade['user2']."','".$id."','".$count."')");
                        $response['update'] = 0;
                    }
                    minus_item($id,$count);
                }else{
                    $check = $mysqli->query("SELECT * FROM `tradeList` WHERE `tradeID` = '".$trade['id']."' AND `item` = '".$id."' AND `user2` = '".$trade['user2']."'")->fetch_assoc();
                    if($check){
                        $mysqli->query("UPDATE `tradeList` SET `count` = `count`+'".$count."' WHERE `tradeID` = '".$trade['id']."' AND `item` = '".$id."' AND `count` > 0 AND `user2` = '".$trade['user2']."'");
                        $response['update'] = 1;
                        $info['count'] = $check['count']+$count;
                    }else{
                        $mysqli->query("INSERT INTO `tradeList` (`tradeID`,`user1`,`user2`,`item`,`count`) VALUE ('".$trade['id']."','".$trade['user2']."','".$trade['user1']."','".$id."','".$count."')");
                        minus_item($id,$count);
                        $response['update'] = 0;
                    }
                }
                $response['error'] = 0;
                $response['data'] = $info;
            }
        }
    }
    break;
       
    default:
        $response['error'] = 1;
        $response['text'] = 'Ошибка обмена!';
        break;
}

if(!empty($response_makasimka)){
    print $response_makasimka;
}else{
    echo json_encode($response);
}

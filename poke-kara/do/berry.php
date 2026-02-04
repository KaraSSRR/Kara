<?
if(isset($_POST['berryID'])){
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}
$berry = escapeMe($_POST['berryID']);
$type = escapeMe($_POST['type']);
$slot = escapeMe($_POST['slot']);
$slots = $mysqli->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
$slote = $mysqli->query('SELECT * FROM user_berry_slot WHERE user = '.$_SESSION['id'].' AND slot = '.$slot)->fetch_assoc();
$user = $_SESSION['id'];
switch($type){
	case "add":
    if(item_isset($berry,1)){
      if($slots['berry_slot'] >= $slot){
        if(!$slote){
          $br = $mysqli->query('SELECT * FROM base_items WHERE id = '.$berry)->fetch_assoc();
          if($br['type'] == 'berry'){
              $time = time()+3600*24;
              $t = time();
              $r = rand(2,3);
              $mysqli->query("INSERT INTO `user_berry_slot` (`user`,`berry`,`cool`,`date_st`,`date`,`slot`) VALUES ('".$user."','".$berry."','".$r."','".$t."','".$time."','".$slot."') ");
              $response['text'] = '<div class="Timer">100%</div><img src="/img/world/items/little/'.$berry.'.png">';
              $response['html'] = '<img src="/img/world/items/little/'.$berry.'.png" class="item"> '.$br['name'].' <b>x1</b></div>';
              $response['error'] = "minus";
              minus_item($berry,1);
          }else{
              $response['html'] = "Не верно выбран предмет!";
            $response['error'] = "error";
          }
        }else{
          $response['html'] = "Данный слот занят!";
          $response['error'] = "error";
        }
      }else{
        $response['html'] = "Неверный слот!";
        $response['error'] = "error";
      }
    }else{
      $response['html'] = "Недостаточно ягод!";
      $response['error'] = "error";
    }
  break;
  case 'small':
    if(item_isset(1,1)){
      if($slots['berry_slot'] >= $slot){
        if($slote){
          if($slote['s_fertilizer'] == 0){
            $time_n_1 = $slote['date_st']-3600*2;
            $time_n_2 = $slote['date']-3600*2;
            $mysqli->query("UPDATE `user_berry_slot` SET
              `s_fertilizer` = '1',
              `date_st` = '".$time_n_1."',
              `date` = '".$time_n_2."'  WHERE `slot` = '".$slot."' AND `user` = '".$user."'");
              $expiration = round(100-(((time()-$time_n_1)/($time_n_2-$time_n_1))*100));
              minus_item(264,1);
              if($expiration <= 0){ $expiration = 0;}
            $response['text'] = '<div class="Timer">'.$expiration.'%</div><img src="/img/world/items/little/'.$slote['berry'].'.png">';
            $response['html'] = '<img src="/img/world/items/little/264.png" class="item"> Малое удобрение <b>x1</b></div>';
            $response['error'] = "minus";
          }else{
            $response['html'] = "Малое удобрение уже было использовано!";
            $response['error'] = "error";
          }
        }else{
          $response['html'] = "Данный слот свободен!";
          $response['error'] = "error";
        }
      }else{
        $response['html'] = "Неверный слот!";
        $response['error'] = "error";
      }
    }else{
      $response['html'] = "Недостаточно удобрений!";
      $response['error'] = "error";
    }
  break;
  case 'medium':
    if(item_isset(1,1)){
      if($slots['berry_slot'] >= $slot){
        if($slote){
          if($slote['m_fertilizer'] == 0){
            $time_n_1 = $slote['date_st']-3600*2;
            $time_n_2 = $slote['date']-3600*2;
            $cool = $slote['cool']+1;
            $mysqli->query("UPDATE `user_berry_slot` SET
              `m_fertilizer` = '1',
              `date_st` = '".$time_n_1."',
              `date` = '".$time_n_2."',
              `cool` = '".$cool."' WHERE `slot` = '".$slot."' AND `user` = '".$user."'");
              $expiration = round(100-(((time()-$time_n_1)/($time_n_2-$time_n_1))*100));
              minus_item(265,1);
              if($expiration <= 0){ $expiration = 0;}
            $response['text'] = '<div class="Timer">'.$expiration.'%</div><img src="/img/world/items/little/'.$slote['berry'].'.png">';
            $response['html'] = '<img src="/img/world/items/little/265.png" class="item"> Среднее удобрение <b>x1</b></div>';
            $response['error'] = "minus";
          }else{
            $response['html'] = "Среднее удобрение уже было использовано!";
            $response['error'] = "error";
          }
        }else{
          $response['html'] = "Данный слот свободен!";
          $response['error'] = "error";
        }
      }else{
        $response['html'] = "Неверный слот!";
        $response['error'] = "error";
      }
    }else{
      $response['html'] = "Недостаточно удобрений!";
      $response['error'] = "error";
    }
  break;
  case 'large':
    if(item_isset(1,1)){
      if($slots['berry_slot'] >= $slot){
        if($slote){
          if($slote['l_fertilizer'] == 0){
            $time_n_1 = $slote['date_st']-3600*4;
            $time_n_2 = $slote['date']-3600*4;
            $cool = $slote['cool']+2;
            $mysqli->query("UPDATE `user_berry_slot` SET
              `l_fertilizer` = '1',
              `date_st` = '".$time_n_1."',
              `date` = '".$time_n_2."',
              `cool` = '".$cool."'  WHERE `slot` = '".$slot."' AND `user` = '".$user."'");
              $expiration = round(100-(((time()-$time_n_1)/($time_n_2-$time_n_1))*100));
              minus_item(266,1);
              if($expiration <= 0){ $expiration = 0;}
            $response['text'] = '<div class="Timer">'.$expiration.'%</div><img src="/img/world/items/little/'.$slote['berry'].'.png">';
            $response['html'] = '<img src="/img/world/items/little/266.png" class="item"> Большое удобрение <b>x1</b></div>';
            $response['error'] = "minus";
          }else{
            $response['html'] = "Большое удобрение уже было использовано!";
            $response['error'] = "error";
          }
        }else{
          $response['html'] = "Данный слот свободен!";
          $response['error'] = "error";
        }
      }else{
        $response['html'] = "Неверный слот!";
        $response['error'] = "error";
      }
    }else{
      $response['html'] = "Недостаточно удобрений!";
      $response['error'] = "error";
    }
  break;
  case 'pick':
      if($slots['berry_slot'] >= $slot){
        if($slote){
          if($slote['date'] <= time()){
            if($slote['s_fertilizer'] == 1) {$a = 10;}else{ $a = 0; }
            if($slote['m_fertilizer'] == 1) {$b = 10;}else{ $b = 0; }
            if($slote['l_fertilizer'] == 1) {$c = 10;}else{ $c = 0; }
            $d = 50+$a+$b+$c;
            if(rand(1,100) <= $d){
                $br = $mysqli->query('SELECT * FROM base_items WHERE id = '.$slote['berry'])->fetch_assoc();
                itemAdd($slote['berry'],$slote['cool']);
                $response['text'] = '<img src="/img/world/plus.png">';
                $response['html'] = '<img src="/img/world/items/little/'.$slote['berry'].'.png" class="item"> '.$br['name'].' <b>x'.$slote['cool'].' </b></div>';
                $response['error'] = "plus";
                $mysqli->query("DELETE FROM `user_berry_slot` WHERE `slot` = '".$slot."' AND `user` = '".$user."'");
            }else{
                itemAdd(366,$slote['cool']);
                $response['text'] = '<img src="/img/world/plus.png">';
                $response['html'] = '<img src="/img/world/items/little/366.png" class="item"> Протухшие продукты <b>x'.$slote['cool'].' </b></div>';
                $response['error'] = "plus";
                $mysqli->query("DELETE FROM `user_berry_slot` WHERE `slot` = '".$slot."' AND `user` = '".$user."'");
            }
          }else{
            $response['html'] = "Плод еще не созрел!";
            $response['error'] = "error";
          }
        }else{
          $response['html'] = "Данный слот свободен!";
          $response['error'] = "error";
        }
      }else{
        $response['html'] = "Неверный слот!";
        $response['error'] = "error";
      }
  break;
  case 'FarmAdd':
    if(item_isset(1,300000)){
      if($slots['berry_slot'] <= 10){
        $s = $slots['berry_slot']+5;
        $mysqli->query("UPDATE `users` SET `berry_slot` = '".$s."' WHERE `id` = '".$user."'");
        $response['html'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x300.000</b></div>';
        $response['error'] = "minus";
        minus_item(1,300000);
      }else{
        $response['html'] = "Вы максимально увеличили ферму!";
        $response['error'] = "error";
      }
    }else{
      $response['html'] = "Нехватает монет!";
      $response['error'] = "error";
    }
  break;
  default:
    echo "Unknown error";
  break;
}
echo json_encode($response);
}
?>

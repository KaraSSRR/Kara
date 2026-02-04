<?php

Class DolznPanel {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;
      $prava = json_decode($userInfo['prava']);

      switch($_POST['type']){

        case 'tpGym':
          if($userInfo['user_group'] == 5 && $userInfo['status'] == 'free'  && $userInfo['location'] != 758|| $userInfo['user_group'] == 1 && $userInfo['status'] == 'free' && $userInfo['location'] != 758) {
            $stmtgym = Work::$sql->prepare("SELECT * FROM gym_text WHERE gym = ?");
                $stmtgym->bind_param("i", $userInfo['gym']);
                $stmtgym->execute();
                $datagym = $stmtgym->get_result();
                $gym = $datagym->fetch_assoc();
            if($gym['tp'] == 0) {
              $sq1l = "UPDATE gym_text SET tp = ? WHERE gym = ?";
                $stmt1 = Work::$sql->prepare($sq1l);
                $stmt1->bind_param("ii", $userInfo['location'], $userInfo['gym']);
                $stmt1->execute();
                $stmt1->close();
                
              $sq2l = "UPDATE users SET location = ? WHERE id = ?";
                $stmt2 = Work::$sql->prepare($sq2l);
                $stmt2->bind_param("ii", $userInfo['gym'], $userInfo['id']);
                $stmt2->execute();
                $stmt2->close();
                
            }else{
              $sq2l = "UPDATE users SET location = ? WHERE id = ?";
                $stmt2 = Work::$sql->prepare($sq2l);
                $stmt2->bind_param("ii", $gym['tp'], $userInfo['id']);
                $stmt2->execute();
                $stmt2->close();
                
              $sq3l = "UPDATE gym_text SET tp = ? WHERE gym = ?";
                $stmt32 = Work::$sql->prepare($sq3l);
                $b = 0;
                $stmt32->bind_param("ii", $b, $userInfo['gym']);
                $stmt32->execute();
                $stmt32->close();
            }
            $text = 'Вы были телепортированы.';
            $error = 'success';
          }else{
            $text = 'У вас нет доступа.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'textGym':
          if($userInfo['user_group'] == 5 || $userInfo['user_group'] == 1) {
            $text = 'Информация изменена.';
            $error = 'success';
            $sq3l = "UPDATE gym_text SET text = ? WHERE gym = ?";
                $stmt32 = Work::$sql->prepare($sq3l);
                $b = $val[0];
                $stmt32->bind_param("si", $b, $userInfo['gym']);
                $stmt32->execute();
                $stmt32->close();
          }else{
            $text = 'У вас нет доступа.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'turScore':
          if($userInfo['user_group'] == 1) {
            $stmt32 = Work::$sql->prepare("UPDATE users SET tour = tour + ? WHERE login = ?");
                $stmt32->bind_param("is", $val[1], $val[0]);
                $stmt32->execute();
            $text = 'Очки добавлены.';
            $error = 'success';
            $score = Info::getStringUser(['login',$val[0],'tour']);
            $id_user = Info::getStringUser(['login',$val[0],'id']);

            if($score >= 15 && $score <= 29) {
               $stmtz = Work::$sql->prepare("SELECT id FROM items_users WHERE item_id = ? AND user = ?");
                $a = 165;
                $stmtz->bind_param("ii", $a, $id_user);
                $stmtz->execute();
                $dataz = $stmtz->get_result();
                $znak = $dataz->fetch_assoc();
              if(!isset($znak)) {
                  
                $stmtS = Work::$sql->prepare("INSERT INTO items_users (item_id,count,user,trophy,split) VALUES (?,?,?,?,?) "); 
                $s = 165;
                $s1 = 1;
                        $stmtS->bind_param("iiiii", $s, $s1, $id_user, $s1, $s1);
                        $stmtS->execute();
                        
              }
            }

            if($score >= 30) {
              $stmtZ = Work::$sql->prepare("SELECT id FROM items_users WHERE item_id = 166 AND user = ?");
              $S = 166;
                $stmtZ->bind_param("ii", $S, intval($id_user));
                $stmtZ->execute();
                $dataZ = $stmtZ->get_result();
                $znak = $dataZ->fetch_assoc();
              if(!isset($znak)) {
                  
                $q = Work::$sql->prepare("DELETE FROM items_users WHERE item_id = ? AND user = ?"); 
                $S = 165;
                        $q->bind_param("ii", $S, $id_user);
                        $q->execute();

                $a = 166;
                $a1 = 1;
                $stmt = Work::$sql->prepare("INSERT INTO items_users (item_id,count,user,trophy,split) VALUES (?,?,?,?,?) "); 
                        $stmt->bind_param("iiiii", $a, $a1, $id_user, $a1, $a1);
                        $stmt->execute();
              }
            }

            // $poke = Work::$sql->query("SELECT * FROM users");
            // while($p = $poke->fetch_assoc()) {
            //   Work::$sql->query("UPDATE users SET battlepass = 0 WHERE id = ".$p['id']);
            // }

            // $poke = Work::$sql->query("SELECT * FROM user_pokemons WHERE basenum = 53");
            // while($p = $poke->fetch_assoc()) {
            //   if($p['gender'] == 'Девочка') {
            //     Work::$sql->query("UPDATE user_pokemons SET form = 85 WHERE id = ".$p['id']);
            //   }else{
            //     Work::$sql->query("UPDATE user_pokemons SET form = 84 WHERE id = ".$p['id']);
            //   }
            // }

            //Work::$sql->query("DELETE FROM items_users WHERE item_id = 198");

            //plusEgg(false,false,false,false,false,1,true);
            // $poke = Work::$sql->query("SELECT id,basenum FROM user_pokemons");
            // while($p = $poke->fetch_assoc()) {
            //   Info::generateAbilityLol($p['basenum'],$p['id']);
            // }


            // $jem = Work::$sql->query("SELECT * FROM user_pokemons WHERE basenum = 741");
            // while($j = $jem->fetch_assoc()) {
            //   $randForm = mt_rand(37,40);
            //   Work::$sql->query("UPDATE user_pokemons SET form = ".$randForm." WHERE id = ".$j['id']);
            // }


            // $jem = Work::$sql->query("SELECT * FROM base_items WHERE type = 'tm'");
            // while($j = $jem->fetch_assoc()) {
            //   Work::$sql->query("UPDATE base_items SET type = 'modificator' WHERE id = ".$j['id']);
            // }

          }else{
            $text = 'У вас нет доступа.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'addTur':
          if($prava->tur == 1 || $userInfo['user_group'] == 1) {
            $stmt = Work::$sql->prepare("INSERT INTO tournaments (name,lvl,count,item_pokemon,item_battle,system,place,dop,prize,date,close) VALUES (?,?,?,?,?,?,?,?,?,?,?) "); 
            $zero = 0;
                        $stmt->bind_param("sssssssssss", $val[0],$val[1],$val[2],$val[3],$val[4],$val[5],$val[6],$val[7],$val[8],$val[9],$zero);
                        $stmt->execute();
            $text = 'Турнир добавлен';
            $error = 'success';
          }else{
            $text = 'У вас нет доступа.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'aboutLocation':
          if($userInfo['user_group'] == 1 || $userInfo['dolzn_panel'] == 1) {
            $text = 'Описание изменено.';
            $error = 'success';
            $stmt = Work::$sql->prepare("UPDATE base_location SET description = ? WHERE id = ?");
                $stmt->bind_param("si", $val[1], $val[0]);
                $stmt->execute();
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'namePokemon':
          if($userInfo['user_group'] == 1 || $userInfo['dolzn_panel'] == 1) {
            $text = 'Имя изменено.';
            $error = 'success';
            
            $stmt = Work::$sql->prepare("UPDATE base_pokemons SET name_rus = ? WHERE id = ?");
                $stmt->bind_param("si", $val[1], $val[0]);
                $stmt->execute();
                
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'nameAttack':
          if($userInfo['user_group'] == 1 || $userInfo['dolzn_panel'] == 1) {
            $text = 'Название изменено.';
            $error = 'success';
            $stmt = Work::$sql->prepare("UPDATE base_atk SET name_rus = ? WHERE name_rus = ?");
                $stmt->bind_param("ss", $val[1], $val[0]);
                $stmt->execute();
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'nameAbility':
          if($userInfo['user_group'] == 1 || $userInfo['dolzn_panel'] == 1) {
            $text = 'Название изменено.';
            $error = 'success';
            $stmt = Work::$sql->prepare("UPDATE base_ability SET name_rus = ? WHERE name_rus = ?");
                $stmt->bind_param("ss", $val[1], $val[0]);
                $stmt->execute();
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'aboutAttack':
          if($userInfo['user_group'] == 1 || $userInfo['dolzn_panel'] == 1) {
            $text = 'Описание изменено.';
            $error = 'success';
            $stmt = Work::$sql->prepare("UPDATE base_atk SET title_all = ? WHERE name_rus = ?");
                $stmt->bind_param("ss", $val[1], $val[0]);
                $stmt->execute();
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'goHelper':
          if($userInfo['user_group'] == 1) {
            $User = Info::getStringUser(['login',$val[0],'dolzn_panel']);
            $sql = "UPDATE users SET dolzn_panel = ? WHERE login = ?";
            $s = ($User == 0 ? 1 : 0);
                $stmt = Work::$sql->prepare("UPDATE users SET dolzn_panel = ? WHERE login = ?");
                $stmt->bind_param("ss", $s, $val[0]);
                $stmt->execute();
            $text = ($User == 0 ? 'Сделан Хелпером.' : 'Убран из Хелперов.');
            $error = 'success';
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'checkLoto':
          if($userInfo['user_group'] == 1) {
            $stmtz = Work::$sql->prepare("SELECT * FROM items_users WHERE item_id = ? AND json = ?");
            $iID = 198;
                $a = 165;
                $stmtz->bind_param("is", $iID, $val[0]);
                $stmtz->execute();
                $dataz = $stmtz->get_result();
                $loto = $dataz->fetch_assoc();
            $User = Info::getStringUser(['id',$loto['user'],'login']);
            $text = $User;
            $error = 'success';
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'giveEgg':
          if($userInfo['user_group'] == 1) {
            $User = Info::getStringUser(['login',$val[0],'id']);
            $text = 'Яйцо выдано.';
            $error = 'success';
            if($val[3] == '100') {
              $val[3] = mt_rand(1,26);
            }
            plusEgg($val[2],$val[3],$val[4],$val[5],$val[7],$val[1],$val[6],$User,$val[8]);
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'givePok':
          if($userInfo['user_group'] == 1) {
            $atk = explode(',',$val[12]);
            if($atk[0] != '0') {
              $atk[0] = Info::getStringAttack(['name_rus',$atk[0],'id']);
            }
            if($atk[1] != '0') {
              $atk[1] = Info::getStringAttack(['name_rus',$atk[1],'id']);
            }
            if($atk[2] != '0') {
              $atk[2] = Info::getStringAttack(['name_rus',$atk[2],'id']);
            }
            if($atk[3] != '0') {
              $atk[3] = Info::getStringAttack(['name_rus',$atk[3],'id']);
            }
            $atk = implode(',',$atk);
            $abil = Info::getAbilityBase(['name_rus',$val[16],'id']);
            $abil_slot = Info::getAbilityBaseId([$val[1],$abil]);
            $User = Info::getMainUser(['login',$val[0]]);
            $dateGet = '{"user_id":"'.$User[0].'","date": "'.time().'"}';
            $stmt = Work::$sql->prepare("INSERT INTO user_pokemons (user_id,basenum,name_new,`character`,lvl,birthday,active,type,gender,`exp`,exp_max,ev,gen,vitamines,owner,master,sparka,happy,attacks,pp_attacks,trade,sparkaNumber,tren,tren_stat,ability,ability_slot) VALUES (
              ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) "); 
              $s = Work::$sql->real_escape_string($User[0]);
              $s1 = Work::$sql->real_escape_string($val[1]);
              $s2 = "Pokemon";
              $s3 = Work::$sql->real_escape_string($val[3]);
              $s4 = Work::$sql->real_escape_string($val[7]);
              $s5 = Work::$sql->real_escape_string($dateGet);
              $s6 = 1;
              $s7 = Work::$sql->real_escape_string($val[4]);
              $s8 = Work::$sql->real_escape_string($val[8]);
              $s9 = Work::$sql->real_escape_string($val[9]);
              $s10 = 1;
              $s11 = 2;
              $s12 = Work::$sql->real_escape_string($val[2]);
              $s13 = Work::$sql->real_escape_string($val[10]);
              $s14 = Work::$sql->real_escape_string($User[0]);
              $s15 = Work::$sql->real_escape_string($User[0]);
              $s16 = Work::$sql->real_escape_string($val[6]);
              $s17 = Work::$sql->real_escape_string($val[11]);
              $s18 = Work::$sql->real_escape_string($atk);
              $s19 = Work::$sql->real_escape_string($val[5]);
              $s20 = Work::$sql->real_escape_string(0,0,0,0);
              $s21= Work::$sql->real_escape_string($val[13]);
              $s22 = Work::$sql->real_escape_string($val[14]);
              $s23 = Work::$sql->real_escape_string($val[15]);
              $s24 = Work::$sql->real_escape_string($abil);
              $s25= Work::$sql->real_escape_string($abil_slot);
               
                        $stmt->bind_param("iisiisissiiisisssisssiiiss", $s, $s1,$s2,$s3,$s4,$s5,$s6,$s7,$s8,$s9,$s10,$s11,$s12,$s13,$s14,$s15,$s16,$s17,$s18,$s19,$s20,$s21,$s22,$s23,$s24,$s25);
                        $stmt->execute();
                        $stmt->close();
            $text = 'Покемон выдан.';
            $error = 'success';
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'giveTrophy':
          if($userInfo['user_group'] == 1) {
			if(in_array($val[1], [14,15,16])) {
				Battlepass::getExp(10000);
			}
            $User = Info::getMainUser(['login',$val[0]]);
            $stmt = Work::$sql->prepare("INSERT INTO items_users (item_id,count,user,about,trophy) VALUES (?,?,?,?,?) "); 
            $s1 = 1;
                        $stmt->bind_param("iiisi", $val[1], $s1, $User[0], $val[2], $s1);
                        $stmt->execute();
            $text = 'Награда выдана.';
            $error = 'success';
            if(in_array($val[1], [14,15,16])) {
              achievment_update(2,1,$User[0]);
            }
            $stmt1 = Work::$sql->prepare("INSERT INTO news_friend (type,text,num,user,date) VALUES (?,?,?,?,?) "); 
            $time = time();
            $s1 = 0;
            $s = 'trophy';
                        $stmt1->bind_param("siiii", $s, $s1, $val[1], $User[0], $time);
                        $stmt1->execute();
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

/*        case 'bugGym':
          if($userInfo['user_group'] == 5 || $userInfo['user_group'] == 1) {
			$val[0] = Work::$sql->real_escape_string($val[0]);
            $User = Info::getMainUser(['login',$val[0]]);
            switch($userInfo['gym']) {
              case 396:
                $bug = 207;
              break;
              case 397:
                $bug = 206;
              break;
              case 398:
                $bug = 223;
              break;
              case 399:
                $bug = 208;
              break;
              case 400:
                $bug = 209;
              break;
              case 401:
                $bug = 210;
              break;
              case 402:
                $bug = 211;
              break;
              case 403:
                $bug = 212;
              break;
              case 404:
                $bug = 214;
              break;
              case 405:
                $bug = 213;
              break;
              case 406:
                $bug = 215;
              break;
              case 407:
                $bug = 216;
              break;
              case 408:
                $bug = 217;
              break;
              case 409:
                $bug = 218;
              break;
              case 410:
                $bug = 219;
              break;
              case 411:
                $bug = 220;
              break;
              case 412:
                $bug = 222;
              break;
              case 413:
                $bug = 221;
              break;
            }
			if(item_isset($bug,$User[0])) {
				$text = 'Такой значок уже имеется у тренера.';
	            $error = 'error';
			}else{
				Work::$sql->query("INSERT INTO items_users (item_id,count,user,trophy) VALUES (".$bug.",1,".$User[0].",1) ");
	            $text = 'Значок выдан';
	            $error = 'success';
	            Work::$sql->query("INSERT INTO news_friend (type,text,num,user,date) VALUES ('bug',0,".$bug.",".$User[0].",".time().") ");
			}
          }else{
            $text = 'У вас нет прав.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;*/

        // case 'pass':
        //   $new_pass = Work::$sql->query("SELECT * FROM new_pass WHERE user = '".$userInfo['id']."'")->fetch_assoc();
        //   if($userInfo['password'] == md5($val[0]) || $new_pass && $val[0] == $new_pass['pass']){
        //     if($val[1] != $val[2]) {
        //       $this->response['text'] = 'Неверно введен новый пароль. Повторите попытку.';
        //       $this->response['error'] = 1;
        //     }else{
        //       $password = Work::$sql->real_escape_string($val[1]);
      	// 			$password = htmlspecialchars($password);
      	// 			$password = trim($password);
      	// 			$password = md5($password);
        //       Work::$sql->query("UPDATE users SET password = '".$password."' WHERE id = '".$userInfo['id']."'");
        //       Work::$sql->query("DELETE FROM new_pass WHERE user = ".$userInfo['id']);
        //       $this->response['error'] = 0;
        //     }
        //   }else{
        //     $this->response['text'] = 'Неверно введен старый пароль. Повторите попытку.';
        //     $this->response['error'] = 1;
        //   }
        // break;

      }

    }

  }

}

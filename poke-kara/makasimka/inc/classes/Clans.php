<?php

Class Clans {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'clanRed':
          $stmtMC = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ? AND group_clan = ?");
          $a = 1;
                $stmtMC->bind_param("ii", $userInfo['id'], $a);
                $stmtMC->execute();
                $dataMC = $stmtMC->get_result();
                $MyClan = $dataMC->fetch_assoc();
                
          if($userInfo['status'] != 'free') {
              
            $error = 'error';
            $text = 'Сейчас вы заняты.';
            
          }else{
              
            if(isset($MyClan)) {
                
              if(in_array($val[0], ['notice','takemoney','nalog','nalog_get'])) {
                  
                $MyClanUser = 0;
                
              }else{
                  
                $Trener = Info::getMainUser(['login',$val[1]]);
                $stmtMCU = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ? AND clan_id = ?");
                    $stmtMCU->bind_param("ii", $Trener[0], $MyClan['clan_id']);
                    $stmtMCU->execute();
                    $dataMCU = $stmtMCU->get_result();
                    $MyClanUser = $dataMCU->fetch_assoc();
                
              }
              
              if(isset($MyClanUser)) {
                  
                switch($val[0]){
                  case 'nalog_get':
                    $stmtClan = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
                        $stmtClan->bind_param("i", $MyClan['clan_id']);
                        $stmtClan->execute();
                        $dataClan = $stmtClan->get_result();
                        $Clan = $dataClan->fetch_assoc();
                        
                    if($Clan['nalog_count'] < 5000) {
                        
                      $error = 'error';
                      $text = 'Необходимо, чтобы накопления были от 5.000 генкар.';
                      
                    }else{
                        
                      $money = $Clan['nalog_count'];
                      $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'","money":"'.$money.'"}';
                      $insertJson = Work::$sql->real_escape_string($insertJson);
                      
                      $stmtJ = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                      $c = 'clan';
                      $c1 = 'NALOG_CREDITED';
                            $stmtJ->bind_param("isss", $MyClan['clan_id'], $c, $c1, $insertJson);
                            $stmtJ->execute();
                            $stmtJ->close();
                      
                      $sql = "UPDATE base_clans SET nalog_count = ?, `money` = `money` + ? WHERE id = ?";
                        $stmtUd = Work::$sql->prepare($sql);
                        $ac1 = 0;
                        $stmtUd->bind_param("iii", $ac1, intval($money), intval($Clan['id']));
                        $stmtUd->execute();
                        $stmtUd->close();
                      $error = 'success';
                      $text = 'Вы добавили генкары в клан.';
                      
                    }
                  break;
                  case 'nalog':
                    $per = intval($val[1]);
                    $stmtClan = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
                        $stmtClan->bind_param("i", intval($MyClan['clan_id']));
                        $stmtClan->execute();
                        $dataClan = $stmtClan->get_result();
                        $Clan = $dataClan->fetch_assoc();
                    if($per < 0 || $per > 20) {
                      $error = 'error';
                      $text = 'Значение должно быть от 0 до 20.';
                    }else{
                      $stmtClanA = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                        $stmtClanA->bind_param("i", intval($Clan['id']));
                        $stmtClanA->execute();
                        $AllUsers = $stmtClanA->get_result();
                      while($u = $AllUsers->fetch_assoc()) {
                        $time = time() + 15;
                        $as = "clan_nalog";
                        $stmtA = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                            $stmtA->bind_param("iiisi", intval($userInfo['id']), intval($u['user_id']), intval($time), $as, intval($per));
                            $stmtA->execute();
                            $stmtA->close();
                      }
                      $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'","notice":"'.$per.'%"}';
                      $insertJson = Work::$sql->real_escape_string($insertJson);
                      $stmtI = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                      $b = 'clan';
                      $b1 = "NALOG_SETUP";
                        $stmtI->bind_param("isss", intval($MyClan['clan_id']), $b, $b1, $insertJson);
                        $stmtI->execute();
                        $stmtI->close();
                      $sqlA = "UPDATE base_clans SET nalog_percent = ? WHERE id = ?";
                        $stmtAS = Work::$sql->prepare($sqlA);
                        $stmtAS->bind_param("ii", intval($per), intval($Clan['id']));
                        $stmtAS->execute();
                        $stmtAS->close();

                      $error = 'success';
                      $text = 'Налог установлен.';
                    }
                  break;
                  case 'takemoney':
                    $money = intval($val[1]);
                    $stmt = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
                        $stmt->bind_param("i", intval($MyClan['clan_id']));
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $Clan = $data->fetch_assoc();
                    if($money < 0) {
                      $error = 'error';
                      $text = 'Недостаточно средств в клане.';
                    }else{
                      if($Clan['money'] < $money) {
                        $error = 'error';
                        $text = 'Недостаточно средств в клане.';
                      }else{
                        $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                            $stmtAU->bind_param("i", intval($Clan['id']));
                            $stmtAU->execute();
                            $AllUsers = $stmtAU->get_result();
                        while($u = $AllUsers->fetch_assoc()) {
                          $time = time() + 15;
                          $stmtM = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                          $as = "clan_take_money";
                          $vs = Work::$sql->real_escape_string(Items::countItem($money,0));
                            $stmtM->bind_param("iiiss", intval($userInfo['id']), intval($u['user_id']), intval($time), $as, $vs);
                            $stmtM->execute();
                            $stmtM->close();

                        }
                        $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'","money":"'.$money.'"}';
                        $insertJson = Work::$sql->real_escape_string($insertJson);
                        $a = "clan";
                        $a1 = "CLAN_TAKE_MONEY";
                        $stmtX = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                            $stmtX->bind_param("isss", intval($Clan['id']), $a, $a1, $insertJson);
                            $stmtX->execute();
                            $stmtX->close();
                        $sqlA = "UPDATE base_clans SET `money` = `money` - ? WHERE id = ?";
                            $stmtD = Work::$sql->prepare($sqlA);
                            $stmtD->bind_param("ii", intval($money), intval($Clan['id']));
                            $stmtD->execute();
                            $stmtD->close();
                        itemAdd(1,$money);
                        $plus = Items::arrayItem([1,[$money,1]]);
                        $error = 'success';
                        $text = 'Вы сняли эмерльды с клана.';
                      }
                    }
                  break;
                  case 'notice':
				 	$val[1] = Work::$sql->real_escape_string($val[1]);
                    if(strlen($val[1]) > 150) {
                      $error = 'error';
                      $text = 'Максимально допустимое количество символов - 150.';
                    }else{
                      $status = trim($val[1]);
                      $status = htmlspecialchars($status);
                      $status = addslashes($status);
                      $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'","notice":"'.$status.'"}';
                      $insertJson = Work::$sql->real_escape_string($insertJson);
                      
                      $stmtI = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                        $stmtI->bind_param("isss", intval($MyClan['clan_id']), $a, $a1, $insertJson);
                        $a = "clan";
                        $a1 = "CLAN_NOTICE";
                        $stmtI->execute();
                        $stmtI->close();
                      
                      $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                        $stmtAU->bind_param("i", intval($MyClan['clan_id']));
                        $stmtAU->execute();
                        $AllUsers = $stmtAU->get_result();
                      while($u = $AllUsers->fetch_assoc()) {
                        $time = time() + 15;
                        $stmtAS = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                        $status = Work::$sql->real_escape_string($status);
                        $S = "clan_notice";
                            $stmtAS->bind_param("iiiss", intval($userInfo['id']), intval($u['user_id']), intval($time), $S, $status);
                            $stmtAS->execute();
                            $stmtAS->close();
                      }
                      $error = 'success';
                      $text = 'Вы оповестили участников своего клана.';
                    }
                  break;
                  case 'titul':
				  	$val[1] = Work::$sql->real_escape_string($val[1]);
                    if(mb_strlen($val[2]) > 20) {
                      $error = 'error';
                      $text = 'Максимально допустимое количество символов - 20.';
                    }else{
                      $status = trim(strip_tags(stripslashes($val[2])));
                      $insertJson = '{"user_id":"'.$Trener[0].'","date":"'.time().'"}';
                      $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                        $stmtAU->bind_param("i", $MyClan['clan_id']);
                        $stmtAU->execute();
                        $AllUsers = $stmtAU->get_result();

                      while($u = $AllUsers->fetch_assoc()) {
                        $time = time() + 15;
                        $A = "clan_titul";
                        $status = Work::$sql->real_escape_string($status);
                        $stmtASX = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                            $stmtASX->bind_param("iiiss", $Trener[0], $u['user_id'], $time, $A, $status);
                            $stmtASX->execute();
                            $stmtASX->close();
                      }
                      
                      $stmtASXS = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                      $insertJson = Work::$sql->real_escape_string($insertJson);
                      $bx = "clan";
                      $bxx = "CLAN_STATUS_USER";
                            $stmtASXS->bind_param("iiss", $MyClan['clan_id'], $bx, $bxx, $insertJson);
                            $stmtASXS->execute();
                            $stmtASXS->close();
                      $status = Work::$sql->real_escape_string($status);
                      
                      $sqlQ = "UPDATE base_clans_users SET status = ? WHERE user_id = ?";
                            $stmtQ = Work::$sql->prepare($sqlQ);
                            $stmtQ->bind_param("si", $status, $Trener[0]);
                            $stmtQ->execute();
                            $stmtQ->close();
                      $error = 'success';
                      $text = 'Звание успешно изменено.';
                    }
                  break;
                  case 'leader':
                    if($MyClanUser['group_clan'] == 1) {
                      $error = 'error';
                      $text = 'Тренер уже лидер.';
                    }else{
                      $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                        $stmtAU->bind_param("i", $MyClan['clan_id']);
                        $stmtAU->execute();
                        $AllUsers = $stmtAU->get_result();
                      while($u = $AllUsers->fetch_assoc()) {
                        $time = time() + 15;
                        $stmtI = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                        $cl = "clan_leader";
                            $stmtI->bind_param("iiis", intval($MyClanUser['user_id']), intval($u['user_id']), intval($time), $cl);
                            $stmtI->execute();
                            $stmtI->close();
                      }
                      $error = 'success';
                      $text = 'Тренер стал лидером.';
                      $sqlq = "UPDATE base_clans_users SET group_clan = ? WHERE user_id = ?";
                      $a = 1;
                        $stmtQ = Work::$sql->prepare($sqlq);
                        $stmtQ->bind_param("ii", $a, intval($Trener[0]));
                        $stmtQ->execute();
                        $stmtQ->close();
                      $insertJson = '{"user_id":"'.$Trener[0].'","date":"'.time().'"}';
                      $insertJson = Work::$sql->real_escape_string($insertJson);
                      $stmtS = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                      $s = "clan";
                      $s1 = 'CLAN_LEADER';
                        $stmtS->bind_param("isss", intval($MyClan['clan_id']), $s, $s1, $insertJson);
                        $stmtS->execute();
                        $stmtS->close();
                    }
                  break;
                  case 'unleader':
                    if($MyClanUser['group_clan'] != 1) {
                      $error = 'error';
                      $text = 'Тренер не лидер.';
                    }else{
                      $stmtClan = Work::$sql->prepare("SELECT * FROM base_clans WHERE creater = ? AND id = ?");
                        $stmtClan->bind_param("ii", intval($Trener[0]), intval($MyClan['clan_id']));
                        $stmtClan->execute();
                        $dataClan = $stmtClan->get_result();
                        $Clan = $dataClan->fetch_assoc();
                      if(isset($Clan)) {
                        $error = 'error';
                        $text = 'Нельзя убирать с лидеров создателя клана.';
                      }else{
                        $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                            $stmtAU->bind_param("i", intval($MyClan['clan_id']));
                            $stmtAU->execute();
                            $AllUsers = $stmtAU->get_result();
                        while($u = $AllUsers->fetch_assoc()) {
                          $time = time() + 15;
                          $stmtI = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                          $b = "clan_leader_delete";
                            $stmtI->bind_param("iiis", intval($MyClanUser['user_id']), intval($u['user_id']), intval($u['user_id']), $b);
                            $stmtI->execute();
                            $stmtI->close();
                        }
                        $sqlB = "UPDATE base_clans_users SET group_clan = ? WHERE user_id = ?";
                        $s = 3;
                            $stmtB = Work::$sql->prepare($sqlB);
                            $stmtB->bind_param("ii", $s, intval($Trener[0]));
                            $stmtB->execute();
                            $stmtB->close();
                        $insertJson = '{"user_id":"'.$Trener[0].'","date":"'.time().'"}';
                        $insertJson = Work::$sql->real_escape_string($insertJson);
                        $stmtER = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                        $er = "clan";
                        $er1 = "CLAN_UNLEADER";
                            $stmtER->bind_param("isss", intval($MyClan['clan_id']), $er, $er1, $insertJson);
                            $stmtER->execute();
                            $stmtER->close();
                        $error = 'success';
                        $text = 'Тренер убран с лидеров.';
                      }
                    }
                  break;
                }
              }else{
                $error = 'error';
                $text = 'Тренер не в вашем клане.';
              }
            }else{
              $error = 'error';
              $text = 'Вы не лидер клана клана.';
            }
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error,
            'plus' => (isset($plus) ? $plus : 0)
          );
        break;

        case 'delete':
          $stmtMC = Work::$sql->prepare("SELECT * FROM base_clans WHERE creater = ?");
            $stmtMC->bind_param("i",intval($userInfo['id']));
            $stmtMC->execute();
            $dataMC = $stmtMC->get_result();
            $MyClan = $dataMC->fetch_assoc();
          if(isset($MyClan)) {
            $stmtA = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE clan_id = ?");
                $stmtA->bind_param("i", intval($MyClan['id']));
                $stmtA->execute();
                $MyClanUser = $stmtA->get_result();
            if($MyClanUser->num_rows == 1) {
              $q = Work::$sql->prepare("DELETE FROM base_clans_users WHERE user_id = ?"); 
                if($q)
                {
                    $q->bind_param("i", intval($userInfo['id']));
                    $q->execute();
                   $q->close();
                }
              $q1 = Work::$sql->prepare("DELETE FROM base_clans WHERE id = ?"); 
                if($q1)
                {
                    $q1->bind_param("i", intval($MyClan['id']));
                    $q1->execute();
                   $q1->close();
                }
              minus_item(205,1);
            }else{
              $error = 'error';
              $text = 'Нельзя удалить клан, пока в нем есть участники.';
            }
          }else{
            $error = 'error';
            $text = 'Вы не создатель клана.';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

		case 'openControl':
		
			$stmtMC = Work::$sql->prepare("SELECT clan_id,group_clan FROM base_clans_users WHERE user_id = ?");
                $stmtMC->bind_param("i", $userInfo['id']);
                $stmtMC->execute();
                $dataMC = $stmtMC->get_result();
                $MyClan = $dataMC->fetch_assoc();
                
			if($MyClan["group_clan"] == 1) {
				
				$stmtX = Work::$sql->prepare("SELECT nalog_count, nalog_percent FROM base_clans WHERE id = ?");
                    $stmtX->bind_param("i", $MyClan['clan_id']);
                    $stmtX->execute();
                    $dataX = $stmtX->get_result();
                    $Clan = $dataX->fetch_assoc();
				
				$stmtZ = Work::$sql->prepare("SELECT user, date FROM clan_users_application WHERE clan = ?");
                    $stmtZ->bind_param("i", $MyClan['clan_id']);
                    $stmtZ->execute();
                    $ClanApplicationsOfUsers = $stmtZ->get_result();

				$applicationsResponse = [];
				
				while($ClanApplication = $ClanApplicationsOfUsers->fetch_assoc()) {
					
					if($ClanApplication["date"] > time()) {
						
						$applicationsResponse[] = Info::getMainUser(["id", $ClanApplication["user"]]);
						
					}
					
				}
				
				$this->response['response'] = array(
				
					"clanId" => $MyClan['clan_id'],
				
					'leader' => 1,
					
					'nalog' => $Clan['nalog_percent'],
					'nalog_count' => (Items::countItem($Clan['nalog_count'],2) == 0 ? 0 : Items::countItem($Clan['nalog_count'],2)),
					
					'applications' => $applicationsResponse,
					
				);
				
			} else {
				
				$this->response['response'] = array(
					'leader' => 0
				);
				
			}
			
		break;

        case 'addMoney':
          $stmtMC = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ?");
            $stmtMC->bind_param("i", intval($userInfo['id']));
            $stmtMC->execute();
            $dataMC = $stmtMC->get_result();
            $MyClan = $dataMC->fetch_assoc();
          if(isset($MyClan)) {
            $stmtC = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
                $stmtC->bind_param("i", intval($MyClan['clan_id']));
                $stmtC->execute();
                $dataC = $stmtC->get_result();
                $Clan = $dataC->fetch_assoc();
            $money = intval($val);
            if(item_isset(1,$money)) {
              if($money < 1000) {
                $text = 'Минимальное количество генкар, которое вы можете положить в клан - 1.000.';
                $error = 'error';
              }else{
                $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                    $stmtAU->bind_param("i", intval($MyClan['clan_id']));
                    $stmtAU->execute();
                    $AllUsers = $stmtAU->get_result();
                while($u = $AllUsers->fetch_assoc()) {
                  $time = time() + 15;
                  $stmtE = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                  $a = "clan_add_money"; 
                  $a1 = Work::$sql->real_escape_string(Items::countItem($money,0));
                        $stmtE->bind_param("iiiss", intval($userInfo['id']), intval($u['user_id']), intval($time), $a, $a1);
                        $stmtE->execute();
                        $stmtE->close();

                }
                $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'","money":"'.$money.'"}';
                $insertJson = Work::$sql->real_escape_string($insertJson);
                $stmtW = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                $c = "clan";
                $c1 = "CLAN_ADD_MONEY";
                        $stmtW->bind_param("isss", intval($Clan['id']), $c, $c1, $insertJson);
                        $stmtW->execute();
                        $stmtW->close();
                $sqlD = "UPDATE base_clans SET `money` = `money` + ? WHERE id = ?";
                    $stmtD = Work::$sql->prepare($sqlD);
                    $stmtD->bind_param("ii", intval($money), intval($Clan['id']));
                    $stmtD->execute();
                    $stmtD->close();
                minus_item(1,$money);
                $text = 'Генкары успешно добавлены в клан.';
                $error = 'success';
                $minus = Items::arrayItem([1,[$money,1]]);
              }
            }else{
              $text = 'Недостаточно генкар.';
              $error = 'error';
            }
          }else{
            $text = 'Вы не состоите в клане.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error,
            'id_clan' => (isset($Clan['id']) ? $Clan['id'] : 0),
            'minus' => (isset($minus) ? $minus : 0)
          );
        break;

        case 'left':
          $stmtMC = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ?");
            $stmtMC->bind_param("i", intval($userInfo['id']));
            $stmtMC->execute();
            $dataMC = $stmtMC->get_result();
            $MyClan = $dataMC->fetch_assoc();
          if(isset($MyClan)) {
            $stmtC = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
                $stmtC->bind_param("i", intval($MyClan['clan_id']));
                $stmtC->execute();
                $dataC = $stmtC->get_result();
                $Clan = $dataC->fetch_assoc();
            if($Clan['creater'] != $userInfo['id']) {
              $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                    $stmtAU->bind_param("i", intval($MyClan['clan_id']));
                    $stmtAU->execute();
                    $AllUsers = $stmtAU->get_result();
              while($u = $AllUsers->fetch_assoc()) {
                $time = time() + 15;
                $stmtE = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                  $a = "clan_left"; 
                  $a1 = Work::$sql->real_escape_string(Items::countItem($money,0));
                        $stmtE->bind_param("iiiss", intval($userInfo['id']), intval($u['user_id']), intval($time), $a, $a1);
                        $stmtE->execute();
                        $stmtE->close();
              }
              $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'"}';
              $insertJson = Work::$sql->real_escape_string($insertJson);
              $stmtW = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                $c = "clan";
                $c1 = "CLAN_LEFT";
                        $stmtW->bind_param("isss", intval($Clan['id']), $c, $c1, $insertJson);
                        $stmtW->execute();
                        $stmtW->close();
              $q = Work::$sql->prepare("DELETE FROM base_clans_users WHERE user_id = ?"); 
                if($q)
                {
                    $q->bind_param("i", intval($userInfo['id']));
                    $q->execute();
                   $q->close();
                }
              minus_item(205,1);
              $text = 'Вы успешно покинули клан.';
              $error = 'success';
            }else{
              $text = 'Нельзя покинуть клан его создателю.';
              $error = 'error';
            }
          }else{
            $text = 'Вы не состоите в клане.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;
		
		case "join":
		
			$stmtMC = Work::$sql->prepare("SELECT id FROM base_clans_users WHERE user_id = ?");
                $stmtMC->bind_param("i", $userInfo['id']);
                $stmtMC->execute();
                $dataMC = $stmtMC->get_result();
                $MyClan = $dataMC->fetch_assoc();
			
			if(isset($MyClan)) {
				
				$this->response['response'] = [
					'text' => "Вы уже состоите в клане",
					'error' => "error",
				];
				
				return;
				
			}
			
			$stmtC = Work::$sql->prepare("SELECT id FROM clan_users_application WHERE clan = ? AND user = ?");
                $stmtC->bind_param("ii", $MyClan["id"], $userInfo['id']);
                $stmtC->execute();
                $dataC = $stmtC->get_result();
                $DBAlreadyHasApplicationWithThisUser = $dataC->fetch_assoc();
			
			if(isset($DBAlreadyHasApplicationWithThisUser)) {
				
				$this->response['response'] = [
					'text' => "Вы уже подавали заявку на вступление в этот клан!",
					'error' => "error",
				];
				
				return;
				
			}
			
			$date = time() + (60 * 60 * 24 * 7);
			
			intval($val);
			
			$stmt = Work::$sql->prepare("INSERT INTO clan_users_application (user,clan,date) VALUES (?,?,?)");
                $stmt->bind_param("iii", $userInfo['id'], $val, $date);
                $stmt->execute();
			
			$this->response['response'] = [
				'text' => "Заявка отправлена!",
				'error' => "success",
			];
		
		break;
		
		case "acceptApplication":
		
			intval($val[0]);
			intval($val[1]);
			intval($val[2]);
		
			$stmt = Work::$sql->prepare("SELECT group_clan FROM base_clans_users WHERE user_id = ? AND clan_id = ?");
                $stmt->bind_param("ii", $userInfo['id'], $val[0]);
                $stmt->execute();
                $data = $stmt->get_result();
                $MyClanUser = $data->fetch_assoc();
			
			if($MyClanUser["group_clan"] != 1) {
				
				$this->response["error"] = "Вы не лидер!";
				return;
				
			}
			
			if($userInfo['status'] != 'free') {
				
				$this->response["error"] = "Вы заняты!";
				return;
				
			}
			
			$stmtAI = Work::$sql->prepare("SELECT id, date FROM clan_users_application WHERE user = ? AND clan = ?");
                $stmtAI->bind_param("ii", $val[1], $val[0]);
                $stmtAI->execute();
                $dataAI = $stmtAI->get_result();
                $applicationInfo = $dataAI->fetch_assoc();
			
			if($applicationInfo["date"] <= time()) {
				
				$this->response["error"] = "Истёк срок заявки!";
				return;
				
			}
			
			if($val[2] == 1) {
				
				$stmtmC = Work::$sql->prepare('SELECT id FROM base_clans_users WHERE user_id = ?');
                    $stmtmC->bind_param("i", $val[1]);
                    $stmtmC->execute();
                    $datamC = $stmtmC->get_result();
                    $myClan = $datamC->fetch_assoc();
				
				if(isset($myClan)) {
					
					$this->response["error"] = "Тренер уже нашёл клан!";
					return;
					
				}
				
				$stmtmC = Work::$sql->prepare("INSERT INTO base_clans_users (user_id,clan_id,raiting,status) VALUES (?,?,?,?)");
				$zero = 0;
				$rank = "Новобранец";
                    $stmtmC->bind_param("iiis", $val[1], $val[0], $zero, $rank);
                    $stmtmC->execute();
				
				$insertJson = '{"user_id":"'.$val[1].'","date":"'.time().'"}';
				
				$stmtmC2 = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) ");
				$type = "clan";
				$title = "ADD_CLAN_USER";
                    $stmtmC2->bind_param("isss", $val[0], $type, $title, $insertJson);
                    $stmtmC2->execute();
				
				$stmtAu = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                    $stmtAu->bind_param("i", $val[0]);
                    $stmtAu->execute();
                    $AllUsers = $stmtAu->get_result();
				
				while($u = $AllUsers->fetch_assoc()) {
					
					$time = time() + 15;
					
					$stmtAu = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)');
					$type = "clan_add_user";
                        $stmtAu->bind_param("iiis", $val[1], $u['user_id'], $time, $type);
                        $stmtAu->execute();
					
				}
				
				$stmtD = Work::$sql->prepare("DELETE FROM clan_users_application WHERE id = ?");
                        $stmtD->bind_param("i", $applicationInfo["id"]);
                        $stmtD->execute();
                        
				$this->response["text"] = "Тренер принят в клан!";
				
			} else {
				
				$stmtD = Work::$sql->prepare("DELETE FROM clan_users_application WHERE id = ?");
                        $stmtD->bind_param("i", $applicationInfo["id"]);
                        $stmtD->execute();
				
				$this->response["text"] = "Заявка успешно отклонена!";
				
			}
		
		break;

        case 'showClan':
		$val = intval($val);
		
          $stmtC = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
            $stmtC->bind_param("i", $val);
            $stmtC->execute();
            $dataC = $stmtC->get_result();
            $Clan = $dataC->fetch_assoc();
            
          $stmtMC = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ?");
            $stmtMC->bind_param("i", $userInfo['id']);
            $stmtMC->execute();
            $dataMC = $stmtMC->get_result();
            $MyClan = $dataMC->fetch_assoc();
            
           $stmtCU = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE clan_id = ?");
            $stmtCU->bind_param("i", $Clan['id']);
            $stmtCU->execute();
            $ClanUsers = $stmtCU->get_result();
            
          $stmtCU = Work::$sql->prepare("SELECT * FROM log_game WHERE user_id = ? AND type = ? ORDER BY id ASC");
          $t = "clan";
            $stmtCU->bind_param("is", $Clan['id'], $t);
            $stmtCU->execute();
            $ClanLogs = $stmtCU->get_result();
            
          $ListLog = [];
          $ListUsers = [];
          
          while($Log = $ClanLogs->fetch_assoc()){
              
            $LogJson = json_decode($Log['info']);
            
            if(isset($MyClan) && in_array($Log['title'], ['CLAN_TERR','CLAN_ADD_MONEY','CLAN_TAKE_MONEY','CLAN_FINE_MONEY','CLAN_NOTICE','NALOG_SETUP','NALOG_CREDITED']) && $Log['user_id'] == $MyClan['clan_id'] || !in_array($Log['title'], ['CLAN_TERR','CLAN_ADD_MONEY','CLAN_TAKE_MONEY','CLAN_FINE_MONEY','CLAN_NOTICE','NALOG_SETUP','NALOG_CREDITED']) || in_array($userInfo['user_group'], [1,2])) {
              $ListLog[$Log['id']] = array(
                  'date' => $LogJson->date,
                  'user' => Info::getMainUser(['id',$LogJson->user_id]),
                  'type' => $Log['title'],
                  'money' => (isset($LogJson->money) ? number_format($LogJson->money,0,'.','.') : 0),
                  'notice' => (isset($LogJson->notice) ? $LogJson->notice : 0)
              );
            }
          }
          while($User = $ClanUsers->fetch_assoc()) {
            $ListUsers[$User['id']] = [
              'User' => Info::getMainUser(['id',$User['user_id']]),
              'group' => $User['group_clan'],
              'status' => $User['status'],
              'rating' => $User['raiting'],
              'creater' => $Clan['creater']
            ];
          }
          usort($ListUsers, function($a, $b){
            return ($a['group'] - $b['group']);
          });
          $this->response['response'] = array(
            'id' => $Clan['id'],
            'my_clan_id' => (in_array($userInfo['user_group'],[1,2]) ? 1 : (isset($MyClan) && $MyClan['clan_id'] == $Clan['id'] ? 1 : 0)),
            'leader' => (isset($MyClan) && $MyClan['clan_id'] == $Clan['id'] && $MyClan['group_clan'] == 1 ? 1 : 0),
            'name' => $Clan['name'],
            'money' => (in_array($userInfo['user_group'],[1,2]) ? number_format($Clan['money'],0,'.','.') : (isset($MyClan) && $MyClan['clan_id'] == $Clan['id'] ? number_format($Clan['money'],0,'.','.') : 0)),
            'rating' => $Clan['rating'],
            'users' => $ClanUsers->num_rows,
            'date' => $Clan['date'],
            'listUsers' => $ListUsers,
            'listLog' => array_reverse($ListLog)
          );
        break;

        case 'intClan':
            
          $stmtCUM = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ?");
            $stmtCUM->bind_param("i", $userInfo['id']);
            $stmtCUM->execute();
            $dataCUM = $stmtCUM->get_result();
            $ClanUserMy = $dataCUM->fetch_assoc();
            
          $User = Info::getMainUser(['login',$val[0]]);
          
          $stmtCU = Work::$sql->prepare("SELECT * FROM base_clans_users WHERE user_id = ?");
            $stmtCU->bind_param("i", $User[0]);
            $stmtCU->execute();
            $dataCU = $stmtCU->get_result();
            $ClanUser = $dataCU->fetch_assoc();
            
            if(isset($ClanUserMy) && $ClanUserMy['group_clan'] == 1) {
              if($User[1] == $userInfo['login']) {
                $text = 'Нельзя пригласить самого себя.';
                $error = 'error';
              }else{
                if($val[1] == 0) {
                  if(isset($ClanUser)) {
                    $text = 'Тренер уже состоит в клане.';
                    $error = 'error';
                  }else{
                    $stmtD = Work::$sql->prepare("INSERT INTO `user_clan_accept` (`user_id`,`clan_id`) VALUES (?,?)"); 
                        $stmtD->bind_param("ii", $User[0], $ClanUserMy['clan_id']);
                        $stmtD->execute();
                    $text = 'Тренеру выслано приглашение.';
                    $error = 'success';
                  }
                }else{
                  if(isset($ClanUser) && $ClanUserMy['clan_id'] == $ClanUser['clan_id']) {
                      
                    $stmtBC = Work::$sql->prepare("SELECT * FROM base_clans WHERE id = ?");
                        $stmtBC->bind_param("i", $ClanUserMy['clan_id']);
                        $stmtBC->execute();
                        $dataBC = $stmtBC->get_result();
                        $BaseClan = $dataBC->fetch_assoc();
                        
                    if($BaseClan['creater'] == $User[0]) {
                        
                      $text = 'Нельзя исключить создателя клана.';
                      $error = 'error';
                      
                    }else{
                        
                      $stmtAU = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                        $stmtAU->bind_param("i", $BaseClan['id']);
                        $stmtAU->execute();
                        $AllUsers = $stmtAU->get_result();
                        
                      while($u = $AllUsers->fetch_assoc()) {
                        $time = time() + 15;
                        
                        $stmtC = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                        $c = "clan_left_alert";
                            $stmtC->bind_param("iiis", $User[0], $u['user_id'], $time, $c);
                            $stmtC->execute();
                      }
                      
                      $insertJson = '{"user_id":"'.$User[0].'","date":"'.time().'"}';
          			  $stmtw = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
          			  $c1 = "clan";
          			  $c2 = "CLAN_LEFT_ALERT";
                        $stmtw->bind_param("isss", $BaseClan['id'], $c1, $c2, $insertJson);
                        $stmtw->execute();
                        
                      $q = Work::$sql->prepare("DELETE FROM base_clans_users WHERE user_id = ?"); 
                            $q->bind_param("i", $User[0]);
                            $q->execute();
                            
                      minus_item(205,1);
                      $text = 'Тренер исключен из клана.';
                      $error = 'success';
                    }
                  }else{
                    $text = 'Тренер не в вашем клане.';
                    $error = 'error';
                  }
                }
              }
            }else{
              $text = 'Вы не являетесь лидером клана.';
              $error = 'error';
            }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        case 'clanGo':
          if($userInfo['user_group'] == 1) {
			 $val[0] = intval($val[0]);
            $stmtC = Work::$sql->prepare("SELECT * FROM clan_app WHERE id = ?");
                $stmtC->bind_param("i", $val[0]);
                $stmtC->execute();
                $dataC = $stmtC->get_result();
                $Clan = $dataC->fetch_assoc();
                
            if(isset($Clan)) {
                
              if($val[1] == 1) {
                  
                Notify::create_bell_noty("/img/logo.png","Ваш клан \"".$Clan['name']."\" не был создан. Причины отклонения заявки могут быть разные. Советуем подать еще одну заявку на создание, сделав ее более корректной.",time(),$Clan['user'],1);
                itemAdd(1,1000000,$Clan['user']);
                $text = 'Заявка отклонена.';
                $error = 'success';
                
              }else{
                  
                $stmtW = Work::$sql->prepare("INSERT INTO base_clans (name,creater,date) VALUES (?,?,?) "); 
                $time = time();
                        $stmtW->bind_param("sii", $Clan['name'], $Clan['user'], $time);
                        $stmtW->execute();
                        
    			$stmtCNS = Work::$sql->prepare("SELECT * FROM base_clans ORDER BY `id` DESC");
                                $stmtCNS->execute();
                                $dataCNS = $stmtCNS->get_result();
                                $clanNewSelect = $dataCNS->fetch_assoc();
                                
                $stmtAS = Work::$sql->prepare("INSERT INTO base_clans_users (user_id,clan_id,raiting,status,group_clan) VALUES (?,?,?,?,?)"); 
                $c = 0; 
                $c1 = "Новобранец";
                $c2 = 1;
                        $stmtAS->bind_param("iiisi", $Clan['user'], $clanNewSelect['id'], $c, $c1, $c2);
                        $stmtAS->execute();
                        
    			$insertJson = '{"user_id":"'.$Clan['user'].'","date":"'.time().'"}';
    			$stmtJ = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
    			$c = "clan";
    			$c1 = "CLAN_CREATE";
                        $stmtJ->bind_param("isss", intval($clanNewSelect['id']), $c, $c1, $insertJson);
                        $stmtJ->execute();
                $text = 'Заявка одобрена.';
                $error = 'success';
				Notify::create_bell_noty("/img/logo.png","Ваш клан \"".$Clan['name']."\" был успешно создан. Желаем вам успехов в продвижении вашего клана.",time(),$Clan['user'],1);
                achievment_update(10,1,$Clan['user']);
              }
              $q = Work::$sql->prepare("DELETE FROM clan_app WHERE id = ?"); 
                $q->bind_param("i", Clan['id']);
                $q->execute();
            }
          }else{
            $text = 'Вы не Администратор.';
            $error = 'error';
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error
          );
        break;

        /* case 'create':
          $MyClanCreater = Work::$sql->query('SELECT * FROM base_clans WHERE creater = '.$userInfo['id'])->fetch_assoc();
          $MyClanMember = Work::$sql->query('SELECT * FROM base_clans_users WHERE user_id = '.$userInfo['id'])->fetch_assoc();
          if(isset($MyClanCreater)) {
            $error = 'error';
            $text = 'Вы уже являетесь создателем клана. Чтобы создать новый клан, необходимо удалить старый.';
          }else{
            if(item_isset(1,1000000)) {
              if($userInfo['status'] != 'free') {
                $error = 'error';
                $text = 'В данный момент вы заняты.';
              }else{
                if(isset($MyClanMember)) {
                  $error = 'error';
                  $text = 'Вы уже состоите в клане.';
                }else{
				$val[0] = Work::$sql->real_escape_string($val[0]);
				$val[1] = Work::$sql->real_escape_string($val[1]);
                  $val[0] = addslashes($val[0]);
                  Work::$sql->query("INSERT INTO clan_app (user,name,emblem) VALUES ('".$userInfo['id']."','".$val[0]."','".$val[1]."')");
                  minus_item(1,1000000);
                  $minus = '<img src="/img/world/items/little/1.png" class="item"> генкар x1000000';
                  $error = 'success';
                  $text = 'Заявка успешно подана. Ожидайте ответа от Администрации.';
                }
              }
            }else{
              $error = 'error';
              $text = 'Недостаточно средств.';
            }
          }
          $this->response['response'] = array(
            'text' => $text,
            'error' => $error,
            'minus' => (isset($minus) ? $minus : 0)
          );
        break; */

        case 'open':
          $stmtC = Work::$sql->prepare('SELECT * FROM base_clans');
            $stmtC->execute();
            $Clans = $stmtC->get_result();
          $ClanList = [];
          while($Clan = $Clans->fetch_assoc()) {
            $stmtCU = Work::$sql->prepare('SELECT id FROM base_clans_users WHERE clan_id = ?');
                $stmtCU->bind_param("i", $Clan['id']);
                $stmtCU->execute();
                $ClanUser = $stmtCU->get_result();
                
            $ClanList[$Clan['id']] = [
              'id' => $Clan['id'],
              'name' => $Clan['name'],
              'rating' => $Clan['rating'],
              'users' => $ClanUser->num_rows
            ];
            
          }
          
          usort($ClanList, function($a, $b){
              
            return ($b['rating'] - $a['rating']);
            
          });
          
          $this->response['response'] = array(
              
            'clan_list' => $ClanList
            
          );
          
        break;

      }

    }

  }

}

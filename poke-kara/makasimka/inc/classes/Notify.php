<?php

function populationInNumbers($population) {
	
	if($population < 100) $rang = 1;
	else if($population >= 100 && $population <= 250) $rang = 2;
	else if($population >= 251 && $population <= 400) $rang = 3;
	else if($population >= 401 && $population <= 900) $rang = 4;
	else if($population >= 901 && $population <= 1600) $rang = 5;
	else if($population >= 1601 && $population <= 2500) $rang = 6;
	else if($population >= 2501 && $population <= 5000) $rang = 7;
	else if($population >= 5001 && $population <= 10000) $rang = 8;
	else if($population >= 10001 && $population <= 25000) $rang = 9;
	else if($population >= 25001 && $population <= 100000) $rang = 10;
	else if($population >= 100000) $rang = 11;
	
	return $rang;
	
}

Class Notify {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

		case 'bell_delete':
			$id = intval($val);
			$stmt = Work::$sql->prepare('SELECT `id`,`user` FROM `notification` WHERE `id` = ?');
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $data = $stmt->get_result();
                $noty = $data->fetch_assoc();
			if($noty['user'] == $userInfo['id']) {
				$error = 0;
			$q = Work::$sql->prepare('DELETE FROM `notification` WHERE `id` = ?'); 
                    if($q)
                    {
                        $q->bind_param("i", $id);
                        $q->execute();
                       $q->close();
                    }
			}else{
				$error = 1;
			}
			$this->response['response'] = array(
              'error' => $error
            );
		break;

        case 'simple':
          switch($val[0]) {
			  //соглашение второго игрока
            case 'list':
			
				//dodj - something old thing, should be removed
				//val = ["list", typeOfNotify, acceptOrDecline, notifyID
			
              $error = 0;
			  intval($val[3]);
              $stmt = Work::$sql->prepare('SELECT * FROM notice_user WHERE id = ?');
                $stmt->bind_param("i", $val[3]);
                $stmt->execute();
                $data = $stmt->get_result();
                $noty = $data->fetch_assoc();
              if(isset($noty)) {
                if($noty['user2'] == $userInfo['id']) {
                  if($val[2] == 'dodj') {
                    $q = Work::$sql->prepare('DELETE FROM notice_user WHERE id = ?'); 
                        $q->bind_param("i", $noty['id']);
                        $q->execute();
                  }
                  if($val[2] == 'yes') {
                    switch($val[1]) {
						
						case "joinToCommandBattle":
						
							$qR = Work::$sql->prepare('DELETE FROM notice_user WHERE id = ?'); 
                              $qR->bind_param("i", $noty['id']);
                              $qR->execute();
						
							$sReq = Work::$sql->prepare('SELECT * FROM users WHERE id = ?');
                              $sReq->bind_param("i", $noty['user1']);
                              $sReq->execute();
                              $dReq = $sReq->get_result();
                              $userTo = $dReq->fetch_assoc();
						
							if($userInfo['location'] != $userTo['location']) {
								
								$this->response['response'] = ['error' => 3];
								return;
								
							}
							
							if($userTo['status'] != 'free') {
							
								$this->response['response'] = ['error' => 7];
								return;
							
							}
							
							/* $info_1 = Info::_userInfoBattle($userInfo['id'], 'pvp', [
								'uinfo'=>$userInfo
							]);
							
							if(empty($info_1)) {
								
								$this->response['response'] = ['error' => 2];
								return;
								
							} */
							
							$info_2 = Info::_userInfoBattle($noty['user1'], 'pvp', [
								'uinfo'=>$userTo
							]);
							
							if(empty($info_2)) {
								
								$this->response['response'] = ['error' => 8];
								return;
								
							}
							
					    	$sli = Work::$sql->prepare("                        
                              SELECT id
                              FROM matsuka_command_battles_list
                              WHERE                        
                            (status = ? OR status = ?)
                            AND
                            (organizer1 = ? OR organizer2 = ?)                          
                            ");
                            $s1 = 'active';
                            $s2 = 'waitForBattles';
                              $sli->bind_param("ssii", $s1, $s2, $userInfo["id"], $userInfo["id"]);
                              $sli->execute();
                              $dlis = $sli->get_result();
                              $commandBattleInfo = $dlis->fetch_assoc();
							
							if(!isset($commandBattleInfo)) {
								
								$this->response['response'] = ['error' => "Командного боя не существует!"];
								return;
								
							}
							
							$slins = Work::$sql->prepare('
                        
                        INSERT INTO matsuka_command_battles_members
                        
                        (battleId, userId, commandId, opponentId, status)
                        
                        VALUES
                        
                        (?,?,?,?,?)
                        
                      ');
                        $ss = 0;
                        $sz = 'free';
                          $slins->bind_param("iiiis", $commandBattleInfo["id"], $userTo["id"], $userInfo["id"], $ss, $sz);
                          $slins->execute();
							
							$supd = Work::$sql->prepare('UPDATE users SET status = ? WHERE id = ?');
                        $ssw = 'battle';
                          $supd->bind_param("si", $ssw, $userTo['id']);
                          $supd->execute();
							
							$time = time() + 15;
							$sNot = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)');
                        $ssx = 'commandBattleAcceptMember';
                          $sNot->bind_param("iiis", $userInfo["id"], $userTo['id'], $time, $ssx);
                          $sNot->execute();
							
							$error = 6;
						
						break;
						
                      case 'addclan':
                       // if($userInfo['location'] == Info::getStringUser(['id',$noty['user1'],'location'])) {
                       if(true){
                          if(Info::getStringUser(['id',$noty['user1'],'status']) != 'free') {
                            $error = 7;
                          }else{
                            $stmt = Work::$sql->prepare('SELECT * FROM base_clans_users WHERE user_id = ?');
                                $stmt->bind_param("i", $userInfo['id']);
                                $stmt->execute();
                                $data = $stmt->get_result();
                                $myClan = $data->fetch_assoc();
                            if(isset($myClan)) {
                              $error = 10;
                            }else{
                              $error = 6;
                              $stmtC = Work::$sql->prepare('SELECT * FROM base_clans WHERE name = ?');
                                $stmtC->bind_param("s", $noty['text']);
                                $stmtC->execute();
                                $dataC = $stmtC->get_result();
                                $Clan = $dataC->fetch_assoc();
                              $stmtA = Work::$sql->prepare(("INSERT INTO base_clans_users (user_id,clan_id,raiting,status) VALUES (?,?,?,?)")); 
                              $a = 0;
                              $A = 'Новобранец';
                                $stmtA->bind_param("iiis", $userInfo['id'], $Clan['id'], $a, $A);
                                $stmtA->execute();
                                $stmtA->close();
                              $sql = "UPDATE users SET chat_clan = ? WHERE id = ?";
                                    $stmt = Work::$sql->prepare($sql);
                                    $ze = 1;
                                    $stmt->bind_param("ii",$ze, $userInfo['id']);
                                    $stmt->execute();
                                    $stmt->close();
                              $insertJson = '{"user_id":"'.$userInfo['id'].'","date":"'.time().'"}';
                  						$stmtAS = Work::$sql->prepare("INSERT INTO log_game (user_id,type,title,info) VALUES (?,?,?,?) "); 
                                            $as = 'clan';
                                            $As = 'ADD_CLAN_USER';
                                              $stmtAS->bind_param("isss", $Clan['id'], $as, $As, $insertJson);
                                              $stmtAS->execute();
                                              $stmtAS->close();
                              $stmtAI = Work::$sql->prepare("SELECT user_id FROM base_clans_users WHERE clan_id = ?");
                                    $stmtAI->bind_param("i", $Clan['id']);
                                    $stmtAI->execute();
                                    $AllUsers = $stmtAI->get_result();
                              while($u = $AllUsers->fetch_assoc()) {
                                $time = time() + 15;
                                $stmtAS = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                                            $d = 'clan_add_user';
                                              $stmtAS->bind_param("iiis", $userInfo['id'], $u['user_id'], $time, $d);
                                              $stmtAS->execute();
                                              $stmtAS->close();
                              }
                            }
                          }
                        }else{
                          $error = 3;
                        }
                      break;
                      case 'friend':
                        if($userInfo['location'] == Info::getStringUser(['id',$noty['user1'],'location'])) {
                          if($userInfo['status'] != 'free') {
                            $error = 1;
                          }else{
                            $stmtF = Work::$sql->prepare("SELECT * FROM users_friend WHERE user_id = ? AND friend_id = ?");
                                $stmtF->bind_param("ii", $noty['user1'], $userInfo['id']);
                                $stmtF->execute();
                                $dataF = $stmtF->get_result();
                                $friends = $dataF->fetch_assoc();
                            if(isset($friends)) {
                              $error = 12;
                            }else{
                              $error = 6;
                              $stmtZ = Work::$sql->prepare("INSERT INTO users_friend (user_id,friend_id,status) VALUES (?,?,?)"); 
                              $a = 1;
                                $stmtZ->bind_param("iii", $userInfo['id'], $noty['user1'], $a);
                                $stmtZ->execute();
                                $stmtZ->close();
                              $time = time() + 15;
                              $stmtZX = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                              $aX = "friend_ok";
                                $stmtZX->bind_param("iiis", $userInfo['id'], $noty['user1'], $time, $aX);
                                $stmtZX->execute();
                                $stmtZX->close();
                              $time = time() + 15;
                              achievment_update(12,1,$noty['user1']);
                              achievment_update(12,1,$userInfo['id']);
                              $q = Work::$sql->prepare('DELETE FROM notice_user WHERE id = ?'); 
                                if($q)
                                {
                                    $q->bind_param("i", $noty['id']);
                                    $q->execute();
                                   $q->close();
                                }
                            }
                          }
                        }else{
                          $error = 3;
                        }
                      break;
                      case 'trade':
					  
					  $stmtF = Work::$sql->prepare("SELECT location, status FROM users WHERE id = ?");
                                $stmtF->bind_param("i", $noty["user1"]);
                                $stmtF->execute();
                                $dataF = $stmtF->get_result();
                                $secondUser = $dataF->fetch_assoc();
					  
                      if($userInfo['location'] == $secondUser['location']) {
                        if($userInfo['status'] != 'free') {
                          $error = 1;
                        }else{
                          if($secondUser['status'] != 'free') {
                            $error = 7;
                          }else{
                             $stmt = Work::$sql->prepare("INSERT INTO users_trade (user1,user2,status) VALUE (?,?,?)"); 
                             $a = 1;
                                $stmt->bind_param("iii", $userInfo['id'], $noty['user1'], $a);
                                $stmt->execute();
                                $stmt->close();

                            $sql = 'UPDATE users SET status = ?, status_id = ? WHERE id IN (?,?)';
                            $A = 'trade';
                            $Z = Work::$sql->insert_id;
                                $stmta = Work::$sql->prepare($sql);
                                $stmta->bind_param("siii", $A, $Z, $userInfo['id'], $noty['user1']);
                                $stmta->execute();
                                $stmta->close();
                            $error = 6;
                            $q = Work::$sql->prepare('DELETE FROM notice_user WHERE id = ?'); 
                                    if($q)
                                    {
                                        $q->bind_param("i", $noty['id']);
                                        $q->execute();
                                       $q->close();
                                    }
                          }
                        }
                      }else{
                        $error = 3;
                      }
                      break;
					  
                      case 'battle':
					  
						  if($userInfo['location'] == Info::getStringUser(['id',$noty['user1'],'location'])) {
							
							$stmt = Work::$sql->prepare('SELECT * FROM users WHERE id = ?');
                            $stmt->bind_param("i", $noty['user1']);
                            $stmt->execute();
                            $data = $stmt->get_result();
                            $userTo = $data->fetch_assoc();
							
							$info_1 = Info::_userInfoBattle($userInfo['id'], 'pvp', [
								'uinfo'=>$userInfo
							]);
							$info_2 = Info::_userInfoBattle($noty['user1'], 'pvp', [
								'uinfo'=>$userTo
							]);
							
							if(empty($info_1)) {

								$error = 2;

							} else {

								if($userInfo['status'] != 'free') {

									$error = 1;

								} else {

									if(Info::getStringUser(['id',$noty['user1'],'status']) != 'free') {

										$error = 7;

									} else {

										if(empty($info_2)) {

											$error = 8;

										} else {
											
											include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/npc/tournamentArena/constants.php');
											
											if($userInfo['location'] == $arenaConstants["arenaLocationID"]) {
												
												$one = 1;
												$stmtAM = Work::$sql->prepare("SELECT round FROM matsuka_tournament_arena WHERE `id` = ?");
                                                    $stmtAM->bind_param("i", $one);
                                                    $stmtAM->execute();
                                                    $dataAM = $stmtAM->get_result();
                                                    $arenaMainInfo = $dataAM->fetch_assoc();
												
												$stmtAM = Work::$sql->prepare("SELECT id FROM matsuka_tournament_arena_users WHERE user = ? AND opponent = ? AND status = ? AND round = ?");
												$sta = "wait";
                                                    $stmtAM->bind_param("iiss", $userInfo["id"], $noty['user1'], $sta, $arenaMainInfo["round"]);
                                                    $stmtAM->execute();
                                                    $dataAM = $stmtAM->get_result();
                                                    $checkTournamentArenaApplication = $dataAM->fetch_assoc();
												
												if(!isset($checkTournamentArenaApplication)) {
												
													$this->response['response'] = ['error' => "Ошибка при выборе соперника."];
													return;
												
												}
												
											}

											$error = 6;

											$info_1 = Info::_parseData($info_1);
											$info_2 = Info::_parseData($info_2);
											
											$WeatherNum = Info::getRegion(['id',Info::getLocation(['id',$userInfo['location'],'region']),'weather']);
											
											$stmtWd = Work::$sql->prepare('INSERT INTO battle (user_1,user_2,info_1,info_2,type,weather) VALUES (?,?,?,?,?,?)'); 
                                                $A = 'pvp';
                                                $stmtWd->bind_param("iisssi", $userInfo['id'], $noty['user1'], $info_1, $info_2, $A, $WeatherNum);
                                                $stmtWd->execute();
                                                $stmtWd->close();
                                                
                                            $stmtLB = Work::$sql->prepare('SELECT id FROM battle ORDER BY id DESC');
                                                $stmtLB->execute();
                                                $dataLB = $stmtLB->get_result();
                                                $lastBattle = $dataLB->fetch_assoc();
                                                
                                            $sqlq = 'UPDATE users SET status = ?, status_id = ? WHERE id IN (?,?)';
                                            $s = "battle";
                                                $stmtq = Work::$sql->prepare($sqlq);
                                                $stmtq->bind_param("siii", $s, $lastBattle['id'], $userInfo['id'], $noty['user1']);
                                                $stmtq->execute();
                                                $stmtq->close();
                                                
                                            $stmtW = Work::$sql->prepare('INSERT INTO battle_log (battle,round,text,end,user,starter) VALUES (?,?,?,?,?,?)'); 
                                            $As = '0';
                                            $vs = "";
                                            $aq = 1;
                                                    $stmtW->bind_param("iisiii", $lastBattle['id'], $As, $vs, $As, $userInfo['id'], $aq);
                                                    $stmtW->execute();
                                                    $stmtW->close();
                                                    
                                            $q = Work::$sql->prepare('DELETE FROM notice_user WHERE id = ?'); 
                                                    if($q)
                                                    {
                                                        $q->bind_param("i", $noty['id']);
                                                        $q->execute();
                                                       $q->close();
                                                    }
										}

									}

								}

							}

						} else {

							$error = 3;

						}
					  
                      break;
                    }
                  }
                  if($val[2] == 'no') {
                    $q = Work::$sql->prepare('DELETE FROM notice_user WHERE id = ?'); 
                                                    if($q)
                                                    {
                                                        $q->bind_param("i", $noty['id']);
                                                        $q->execute();
                                                       $q->close();
                                                    }
                    $error = 6;
                  }
                }
              }
            break;
			
			case "joinToCommandBattle":
			
				if(!isset($val[1]) || !isset($val[2])) {
					
					$this->response['response'] = ['error' => "Wrong Params!"];
					return;
					
				}
			
				//...
				intval($val[1]);
				intval($val[2]);
			
				if($userInfo["id"] == $val[1]) {
					
					$this->response['response'] = ['error' => "Невозможно применить к себе!"];
					return;
					
				}
			
				if($userInfo['status'] != 'free') {
					
					$this->response['response'] = ['error' => 1];
					return;
					
				}
				
				$info_1 = Info::_userInfoBattle($userInfo['id'], 'pvp', [
					'uinfo'=>$userInfo
				]);
				
				if(empty($info_1)) {
					
					$this->response['response'] = ['error' => 2];
					return;
					
				}
				
	                $stV = Work::$sql->prepare("              
                      SELECT id
                      FROM matsuka_command_battles_list
                      WHERE id = ? AND (
                      
                        organizer1 = ?
                        OR
                        organizer2 = ?
                      
                      ) AND (
                      
                        status = ?
                        OR
                        status = ?
                        
                      )              
                    ");
                    $st1 = 'active';
                    $st2 = 'waitForBattles';
                                $stV->bind_param("iiiss", $val[2], $val[1], $val[1], $st1, $st2);
                                $stV->execute();
                                $datV = $stV->get_result();
                                $commandBattleInfo = $datV->fetch_assoc();
				
				if(!isset($commandBattleInfo)) {
					
					$this->response['response'] = ['error' => "Такого командного боя не существует!"];
					return;
					
				}
				
				if($userInfo['location'] != Info::getStringUser(['id',$val[1],'location'])) {
					
					$this->response['response'] = ['error' => 3];
					return;
					
				}
				
				$stB = Work::$sql->prepare('SELECT * FROM notice_user WHERE type = ? AND user1 = ? AND user2 = ?');
                $st3 = 'joinToCommandBattle';
                                $stB->bind_param("sii", $st3, $userInfo['id'],$val[1]);
                                $stB->execute();
                                $daB = $stB->get_result();
                                $battle = $daB->fetch_assoc();
				
				if(isset($battle)) {
					
					$this->response['response'] = ['error' => 4];
					return;
					
				}
				
				$stBs = Work::$sql->prepare("
              
                  SELECT
                  
                    `cblist`.`id`,
                    `cblist`.`status`,
                    
                    `cbmembers`.`battleId`
                    
                  FROM `matsuka_command_battles_members` as `cbmembers`
                  
                  INNER JOIN `matsuka_command_battles_list` as `cblist`
                  
                    ON
                    
                      `cblist`.`id` = `cbmembers`.`battleId`
                      AND
                      (`cblist`.`status` = 'active' OR `cblist`.`status` = 'waitForBattles')
                    
                  WHERE userId = ?
                
                ");
                                $stBs->bind_param("i", $userInfo["id"]);
                                $stBs->execute();
                                $daBs = $stBs->get_result();
                                $checkIfInCommandBattle = $daBs->fetch_assoc();
				
				if(isset($checkIfInCommandBattle)) {
					
					$this->response['response'] = ['error' => "Вы уже находитесь в одном из командных боёв! Подождите, пока он не закончится!"];
					return;
					
				}
				
				$time = time() + 20;
				
				$stBsx = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)');
                  $join = 'joinToCommandBattle';
                                $stBsx->bind_param("iiis", $userInfo['id'], $val[1], $time, $join);
                                $stBsx->execute();
				
				$error = 5;
			
			break;
			
			case "attack":
				
				$adminByPass = true;
				
				if($userInfo['status'] != 'free') {
					
					$this->response['response'] = ['error' => 1];
					return;
					
				}
				
				$info_1 = Info::_userInfoBattle($userInfo['id'], 'pvp', [
					'uinfo'=>$userInfo
				]);
				
				if(empty($info_1)) {
					
					$this->response['response'] = ['error' => 2];
					return;
					
				}
				
				if($userInfo['location'] != Info::getStringUser(['id',$val[1],'location'])) {
					
					$this->response['response'] = ['error' => 3];
					return;
					
				}
				
				$stmt = Work::$sql->prepare('SELECT * FROM users WHERE id = ?');
                                $stmt->bind_param("i", $val[1]);
                                $stmt->execute();
                                $data = $stmt->get_result();
                                $userTo = $data->fetch_assoc();
				
				if($userTo["user_group"] == 1 && $userInfo["user_group"] != 1) {
					
					$this->response['response'] = ['error' => "Ты на кого попёр!"];
					return;
					
				}
				
				if($userInfo["user_group"] == 1 && $adminByPass) {
					
				} else {
					
					$stmt = Work::$sql->prepare('SELECT gym, pvp, pve FROM base_location WHERE id = ?');
                                $stmt->bind_param("i", $userInfo['location']);
                                $stmt->execute();
                                $data = $stmt->get_result();
                                $loc = $data->fetch_assoc();
					
					if(
					
						(
							
							(
							
								$loc["gym"] > 0
								||
								$loc["pvp"] == 0
							
							)
							
							&&
							
							//Разрешение только по воскресеньям
							date("w") != 0
							
						)
						
						||
						
						(
							
							$userInfo["location"] == 8009
							||
							$userInfo["location"] == 8010
							||
							$userInfo["location"] == 8011
							
						)
						
					) {
						
						$this->response['response'] = ['error' => "На этой локации нападения запрещены!"];
						return;
						
					}
					
					$userInfo["karma"] = intval($userInfo["karma"]);
					$userTo["karma"] = intval($userTo["karma"]);
					
					if(
						$loc["pve"] == 0
						
						&&
						
						(
						
							$userInfo["karma"] < 10
							
							||
							
							$userTo["karma"] >= -9
							
						)
						
					) {
						
						$this->response['response'] = ['error' => "На безопасной локации может нападать только защитник на преступника! Защитником считается тренер, у которого карма равна или более 10. Преступником же тот, карма которого менее или равна -10."];
						return;
						
					}
					
					$ratings1 = $userInfo['population'];
					$ratings2 = $userTo['population'];
					
					if($ratings1 <= 400) {
						
						$this->response['response'] = ['error' => "У Вас слишком маленький рейтинг!"];
						return;
						
					}
					
					if($ratings2 <= 400) {
						
						$this->response['response'] = ['error' => "У оппонента слишком маленький рейтинг!"];
						return;
						
					}
					
				}
				
				if($userTo['status'] != 'free') {
					
					$this->response['response'] = ['error' => 7];
					return;
					
				}
				
				$info_2 = Info::_userInfoBattle($userTo['id'], 'pvp', [
					'uinfo'=>$userTo
				]);
				
				if(empty($info_2)) {
					
					$this->response['response'] = ['error' => 8];
					return;
					
				}
				
				if($userInfo["user_group"] == 1 && $adminByPass) {
					
				} else {
					
					if(!item_isset(1, 30000)) {
						
						$this->response['response'] = ['error' => "У Вас нету необходимой суммы для совершения нападения!"];
						return;
						
					}
					
					$diff = populationInNumbers($ratings1) - populationInNumbers($ratings2);
					
					if(
						
						//Обороняющийся - нейтрал/защитник
						$userTo["karma"] >= -9
						
						&&
						
						//Атакующий - не защитник
						$userInfo["karma"] < 10
						
						&&
						
						(
						
							$ratings1 > $ratings2
							&&
							$diff > 1
							
						)
						
					) {
						
						$this->response['response'] = ['error' => "Для преступников и тех, кто хочет им стать, действует правило о максимальной разнице в рангах, которое не соблюдено. Вы можете напасть только на тренера, у которого ранг ниже на 1 ступень, равен Вашему или превышает Ваш. Если у соперника ранг ниже на 2 и более - нападение запрещено!"];
						return;
						
					}
					
					minus_item(1, 30000);
					
				}
				
				$info_1 = Info::_parseData($info_1);
				$info_2 = Info::_parseData($info_2);
				
				$WeatherNum = Info::getRegion(['id',Info::getLocation(['id',$userInfo['location'],'region']),'weather']);
				
				$stmtW = Work::$sql->prepare('INSERT INTO battle (user_1,user_2,info_1,info_2,type,weather,isForce) VALUES (?,?,?,?,?,?,?)'); 
                                                        $A = 'pvp';
                                                                $stmtW->bind_param("iisssii", $userInfo['id'], $userTo['id'], $info_1, $info_2, $A, $WeatherNum, $userInfo['id']);
                                                                $stmtW->execute();
                                                                $stmtW->close();
				
				$stmtLB = Work::$sql->prepare('SELECT id FROM battle ORDER BY id DESC');
                                                    $stmtLB->execute();
                                                    $dataLB = $stmtLB->get_result();
                                                    $lastBattle = $dataLB->fetch_assoc();
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/constants/global.php');
				
				$stmtWs = Work::$sql->prepare("INSERT INTO `user_quests` (`quest_id`,`user_id`,`step`,`data`,`end`) VALUES(?,?,?,?,?) "); 
                                                        $Ag = '10000';
                                                        $vss = '';
                                                        $stmtWs->bind_param("iiisi", $Ag, $_SESSION['id'], $userTo['id'], $vss, $lastBattle['id']);
                                                        $stmtWs->execute();
				
				$sqlq = 'UPDATE users SET status = ?, status_id = ? WHERE id IN (?,?)';
                                                $s = "battle";
                                                    $stmtq = Work::$sql->prepare($sqlq);
                                                    $stmtq->bind_param("siii", $s, $lastBattle['id'], $userInfo['id'], $userTo['id']);
                                                    $stmtq->execute();
				
				$stmtWd = Work::$sql->prepare('INSERT INTO battle_log (battle,round,text,end,user,starter) VALUES (?,?,?,?,?,?)'); 
                                                $As = '0';
                                                $vs = "";
                                                $aq = 1;
                                                        $stmtWd->bind_param("iisiii", $lastBattle['id'], $As, $vs, $As, $userInfo['id'], $aq);
                                                        $stmtWd->execute();
				
				$time = time() + 15;
				
				$stmtNC = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
				$type = "forceBattle";
                                                        $stmtNC->bind_param("iiis", $userInfo['id'], $userTo["id"], $time, $type);
                                                        $stmtNC->execute();
				
				$error = 6;
				
			break;
            case 'battle':
              if($userInfo['status'] != 'free') {
                $error = 1;
              }else{
                $info_1 = Info::_userInfoBattle($userInfo['id'], 'pvp', [
                    'uinfo'=>$userInfo
                ]);
                if(empty($info_1)) {
                  $error = 2;
                }else{
                  if($userInfo['location'] != Info::getStringUser(['id',$val[1],'location'])) {
                    $error = 3;
                  }else{
                    $stmt = Work::$sql->prepare('SELECT * FROM notice_user WHERE type = ? AND user1 = ? AND user2 = ?');
                    $A = "battle";
                        $stmt->bind_param("sii", $A, $userInfo['id'], $val[1]);
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $battle = $data->fetch_assoc();
                    if(isset($battle)) {
                      $error = 4;
                    }else{
                      if(Info::getLocation(['id',$userInfo['location'],'pvp']) == 0) {
                        $error = 11;
                      }else{
                        if($userInfo['id'] != $val[1]) {
							
							include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/npc/tournamentArena/constants.php');
							
							if($userInfo['location'] == $arenaConstants["arenaLocationID"]) {
								
								$stmtaMI = Work::$sql->prepare("SELECT round FROM matsuka_tournament_arena WHERE `id` = ?");
								$one = 1;
                                    $stmtaMI->bind_param("i", $one);
                                    $stmtaMI->execute();
                                    $dataaMI = $stmtaMI->get_result();
                                    $arenaMainInfo = $dataaMI->fetch_assoc();
								
								$stmtaCI = Work::$sql->prepare("SELECT id FROM tableArenaUsers WHERE user = ? AND opponent = ? AND status = ? AND round = ?");
								$status = "wait";
                                    $stmtaCI->bind_param("iiss", $userInfo["id"], $val[1], $status, $arenaMainInfo["round"]);
                                    $stmtaCI->execute();
                                    $dataaCI = $stmtaCI->get_result();
                                    $checkTournamentArenaApplication = $dataaCI->fetch_assoc();
								
								if(!isset($checkTournamentArenaApplication)) {
								
									$this->response['response'] = ['error' => "Ошибка при выборе соперника!"];
									return;
								
								}
								
							}
							
                          $time = time() + 15;
                          $stmtA = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                          $A = "battle";
                            $stmtA->bind_param("iiis", $userInfo['id'], $val[1], $time, $A);
                            $stmtA->execute();
                          $error = 5;
                        }
                      }
                    }
                  }
                }
              }
            break;
            case 'addclan':
              if($userInfo['status'] != 'free') {
                $error = 1;
              }else{
                //if($userInfo['location'] != Info::getStringUser(['id',$val[1],'location'])) {
                if(false) {
                  $error = 3;
                }else{
                  $stmt = Work::$sql->prepare('SELECT * FROM notice_user WHERE type = ? AND user1 = ? AND user2 = ?');
                  $A = "addclan";
                    $stmt->bind_param("sii", $A, $userInfo['id'], $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $addclan = $data->fetch_assoc();
                  if(isset($addclan)) {
                    $error = 4;
                  }else{
                    if($userInfo['id'] != $val[1]) {
                      if(Info::getStringUser(['id',$val[1],'status']) != 'free') {
                        $error = 7;
                      }else{
                        $stmtMCX = Work::$sql->prepare('SELECT * FROM base_clans_users WHERE user_id = ?');
                              $stmtMCX->bind_param("i", $userInfo['id']);
                              $stmtMCX->execute();
                              $datac = $stmtMCX->get_result();
                              $myClan = $datac->fetch_assoc();
                        if(!isset($myClan)) {
                          $error = 9;
                        }else{
                          if($myClan['group_clan'] == 1) {
                            $stmts = Work::$sql->prepare('SELECT * FROM base_clans_users WHERE user_id = ?');
                                $stmts->bind_param("i", $val[1]);
                                $stmts->execute();
                                $datsac = $stmts->get_result();
                                $himClan = $datsac->fetch_assoc();
                            if(isset($himClan)) {
                              $error = 10;
                            }else{
                              $stmtss = Work::$sql->prepare('SELECT name FROM base_clans WHERE id = ?');
                                $stmtss->bind_param("i", $myClan['clan_id']);
                                $stmtss->execute();
                                $datsasc = $stmtss->get_result();
                                $myClanName = $datsasc->fetch_assoc();
                              $time = time() + 15;
                              $A = "addclan";
                              $stmtQ = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type,text) VALUES (?,?,?,?,?)'); 
                                    $stmtQ->bind_param("iiiss",$userInfo['id'], $val[1], $time, $A, $myClanName['name']);
                                    $stmtQ->execute();
                              $error = 5;
                            }
                          }else{
                            $error = 9;
                          }
                        }
                      }
                    }
                  }
                }
              }
            break;
            case 'friend':
              if($userInfo['status'] != 'free') {
                $error = 1;
              }else{
                if($userInfo['location'] != Info::getStringUser(['id',$val[1],'location'])) {
                  $error = 3;
                }else{
                  $stmt = Work::$sql->prepare('SELECT * FROM notice_user WHERE type = ? AND user1 = ? AND user2 = ?');
                  $a = "friend";
                    $stmt->bind_param("sii",$a, $userInfo['id'], $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $friend = $data->fetch_assoc();
                  if(isset($friend)) {
                    $error = 4;
                  }else{
                    if($userInfo['id'] != $val[1]) {
                      if(Info::getStringUser(['id',$val[1],'status']) != 'free') {
                        $error = 7;
                      }else{
                        $time = time() + 15;
                        $stmt = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                        $a = "friend";
                            $stmt->bind_param("iiis", $userInfo['id'], $val[1], $time, $a);
                            $stmt->execute();
                            $stmt->close();
                        $error = 5;
                      }
                    }
                  }
                }
              }
            break;
             case 'trade':
            if($userInfo['status'] != 'free') {
              $error = 1;
            }else{
              if($userInfo['location'] != Info::getStringUser(['id',$val[1],'location'])) {
                $error = 3;
              }else{
                $stmt = Work::$sql->prepare('SELECT * FROM notice_user WHERE type = ? AND user1 = ? AND user2 = ?');
                $a = "trade";
                    $stmt->bind_param("sii", $a, $userInfo['id'], $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $trade = $data->fetch_assoc();
                if(isset($trade)) {
                  $error = 4;
                }else{
                  if(Info::getLocation(['id',$userInfo['location'],'trade']) == 1) {
                    $error = 8;
                  }else{
                    if($userInfo['id'] != $val[1]) {
                      if(Info::getStringUser(['id',$val[1],'status']) != 'free') {
                        $error = 7;
                      }else{
                        $time = time() + 15;
                        $stmt = Work::$sql->prepare('INSERT INTO notice_user (user1,user2,time,type) VALUES (?,?,?,?)'); 
                        $a = "trade";
                            $stmt->bind_param("iiis", $userInfo['id'], $val[1], $time, $a);
                            $stmt->execute();
                            $stmt->close();
                        $error = 5;
                      }
                    }
                  }
                }
              }
            }
            break;
          }
          $this->response['response'] = array(
            'error' => $error
          );
        break;

        case 'show':
		  intval($val);
		  if($val <= 5) {
			 $sql = "UPDATE notification SET checked = ? WHERE checked = ? AND user = ?";
			 $a = 0;
			 $A = 1;
                    $stmt = Work::$sql->prepare($sql);
                    $stmt->bind_param("iii",  $a, $A, $userInfo['id']);
                    $stmt->execute();
		  }
          $limit = $val;
          $limit2 = $val + 10;
          // Получаем приглашения в клан
$stmtC = Work::$sql->prepare('SELECT * FROM user_clan_accept WHERE user_id = ?');
$stmtC->bind_param("i", $userInfo['id']);
$stmtC->execute();
$clans = $stmtC->get_result();

// Получаем обычные уведомления
$stmtN = Work::$sql->prepare("SELECT * FROM `notification` WHERE `user` = ? ORDER BY id DESC LIMIT ?, ?");
$stmtN->bind_param("iii", $userInfo['id'], $limit, $limit2);
$stmtN->execute();
$notifications = $stmtN->get_result();

$NotifyList = [];
$i = 1;

// Сначала добавляем уведомления о приглашениях в клан
while ($clanInvite = $clans->fetch_assoc()) {
    // Получаем инфу о клане
    $clanInfoStmt = Work::$sql->prepare('SELECT name FROM base_clans WHERE id = ?');
    $clanInfoStmt->bind_param("i", $clanInvite['clan_id']);
    $clanInfoStmt->execute();
    $clanInfo = $clanInfoStmt->get_result()->fetch_assoc();

    $NotifyList[$i] = [
        'id' => 'clan_invite_' . $clanInvite['id'], // уникальный id
        'img' => '/img/clan_invite.png',            // свой значок для приглашения
        'words' => 'Вас пригласили в клан: ' . ($clanInfo['name'] ?? 'Неизвестный клан'),
        'date' => $clanInvite['date'] ?? '',        // если в user_clan_accept есть столбец date
        'system' => 1                               // помечаем как системное
    ];
    $i++;
}

// Затем добавляем обычные уведомления
while ($noty = $notifications->fetch_assoc()) {
    $NotifyList[$i] = [
        'id' => $noty['id'],
        'img' => $noty['img'],
        'words' => $noty['text'],
        'date' => $noty['date'],
        'system' => $noty['system']
    ];
    $i++;
}

$this->response['response'] = array(
    'notyList' => $NotifyList,
    'limit' => $limit2
);
break;

      }

    }

  }

  public static function create_bell_noty($img,$text,$time,$user,$system) {
	  $stmt = Work::$sql->prepare("INSERT INTO notification (text,user,img,date,system) VALUES (?,?,?,?,?) "); 
                        $stmt->bind_param("sisii", $text, $user, $img, $time, $system);
                        $stmt->execute();
                        $stmt->close();
  }

}

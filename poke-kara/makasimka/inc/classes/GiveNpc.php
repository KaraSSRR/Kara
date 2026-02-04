<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

function isNpcAviable($npcList, $needId) {
	
	for($i = 0; $i < count($npcList); ++$i) {
		
		if($npcList[$i]["id"] == $needId) {
			
			return $npcList[$i];
			
		}
		
	}
	
	return false;
	
}

Class GiveNpc {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

	if($type) {

		$this->response =& $response;

        switch($type) {

          case 'get':
		  
            $stmt = Work::$sql->prepare("SELECT * FROM base_npc WHERE id = ? AND loc_id = ?");
                $stmt->bind_param("ii", $val[2], $userInfo['location']);
                $stmt->execute();
                $data = $stmt->get_result();
                $Npc = $data->fetch_assoc();
			
			$npc = [];
			$meUser = $userInfo;
			$mysqli = Work::$sql;
			
			include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/npc/controllerAppearence.php');
			
			$isNpcAviableResult = isNpcAviable($npc, $val[2]);
			
			if(!!$isNpcAviableResult) {
				
				$Npc = $isNpcAviableResult;
				
			}
			
            if(isset($Npc)) {
              if($userInfo['status'] != 'free') {
                $error = 2;
                $type = 'error';
              }else{
                switch($val[0]) {
                  case 'egg':
                    $descCheck = 0;
                    if(Info::checkEggUserId($val[1])) {
                      switch($Npc['id']) {
						  
						  case 116:
						  
							$questID = 10;
							$stmtQ = Work::$sql->prepare("SELECT `step`,`quest_id`,`data` FROM `user_quests` WHERE `user_id` = ? AND `quest_id` = ?");
                                $stmtQ->bind_param("ii", $_SESSION['id'], $questID);
                                $stmtQ->execute();
                                $dataQ = $stmtQ->get_result();
                                $questInfo = $dataQ->fetch_assoc();
							
							if($questInfo["step"] == 6) {
								
								$stmtQe = Work::$sql->prepare("SELECT * FROM `user_egg` WHERE `user` = ? AND `id` = ?");
                                    $stmtQe->bind_param("ii", $_SESSION['id'], $val[1]);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $questEgg = $dataQe->fetch_assoc();
								
								$eggs = $questInfo["data"];
								
								$eggsArr = explode("|", $eggs);
								
								$needArr = [];
								
								for($i = 0; $i < count($eggsArr); ++$i) {
									
									$needArr[] = intval(explode(",", $eggsArr[$i])[0]);
									
								}
								
								$key = array_search(intval($questEgg["basenum"]), $needArr);
								
								if($key !== false && $questEgg["basenum"] > 0) {
									
									$newData = "";
									$next = true;
									
									for($i = 0; $i < count($eggsArr); ++$i) {
										
										$newValue = explode(",", $eggsArr[$i])[1];
										
										if(intval($questEgg["basenum"]) == $needArr[$i]) {
											
											$newValue = $newValue - 1;
											
										}
										
										if($newValue < 0) {
											
											$next = false;
											break;
											
										}
										
										$newData .= "".$needArr[$i].",".$newValue."|";
										
									}
									
									if($next == true) {
										
										$newData = substr($newData, 0, strlen($newData) - 1);
										
										$dialog = $Npc["id"];
										$error = 10;
										$type = 'success';
										$minus = Items::arrayItem([54,[1,1]]);
										$step = 7;
										
										$sqlD = "UPDATE `user_quests` SET `data` = ? WHERE `user_id` = ? AND `quest_id` = ?";
                                            $stmtD = Work::$sql->prepare($sqlD);
                                            $stmtD->bind_param("sii", $newData, $_SESSION['id'], $questID);
                                            $stmtD->execute();
									
										$q = Work::$sql->prepare("DELETE FROM `user_egg` WHERE `user` = ? AND `id` = ?"); 
                                            if($q)
                                            {
                                                $q->bind_param("ii", $_SESSION['id'],$val[1]);
                                                $q->execute();
                                               $q->close();
                                            }
										
									} else {
										
										$error = 4;
										$type = 'error';
										
									}
									
								} else {
									
									$error = 5;
									$type = 'error';
									
								}
								
							} else {
								
								$error = 4;
								$type = 'error';
								
							}
						  
						  break;
						  
						case 8017:
						  
							$questID = 9563;
							$stmtQ = Work::$sql->prepare("SELECT `step`,`quest_id`,`data` FROM `user_quests` WHERE `user_id` = ? AND `quest_id` = ?");
                                $stmtQ->bind_param("ii", $_SESSION['id'], $questID);
                                $stmtQ->execute();
                                $dataQ = $stmtQ->get_result();
                                $questInfo = $dataQ->fetch_assoc();
							
							if($questInfo["step"] >= 7 && $questInfo["step"] <= 12) {
								
								$need = explode(",", $questInfo["data"]);
								
								$stmtQe = Work::$sql->prepare("SELECT * FROM `user_egg` WHERE `user` = ? AND `id` = ?");
                                    $stmtQe->bind_param("ii", $_SESSION['id'], $val[1]);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $questEgg = $dataQe->fetch_assoc();
								
								if(!$questEgg["basenum"] || $need[0] != $questEgg["basenum"]) {
									
									$error = 5;
									$type = 'error';
									
								} else {
									
									$newCount = $need[1] - 1;
									
									$error = 10;
									$type = 'success';
									$minus = Items::arrayItem([54,[1,1]]);
									$dialog = 8017;
									
									if($newCount == 0) {
										
										$step = 4;
										
									} else {
										
										$step = 3;
										
									}
									
									$newNeed = "".$need[0].",".$newCount;
									
									$sqlD = "UPDATE `user_quests` SET `data` = ? WHERE `user_id` = ? AND `quest_id` = ?";
                                            $stmtD = Work::$sql->prepare($sqlD);
                                            $stmtD->bind_param("sii", $newNeed, $_SESSION['id'], $questID);
                                            $stmtD->execute();
									
									$q = Work::$sql->prepare("DELETE FROM `user_egg` WHERE `user` = ? AND `id` = ?"); 
                                            if($q)
                                            {
                                                $q->bind_param("i", $_SESSION['id'], $val[1]);
                                                $q->execute();
                                               $q->close();
                                            }
									
								}
								
							} else {
								
								$error = 4;
								$type = 'error';
								
							}
						  
						break;
						  
                        case 289:
                          $userDr = Info::_unParseData($userInfo['dr']);
                          if($userDr['egg'] < 10) {
                            if(item_isset(43,5)) {
                              minus_item(43,5);
                              $minus = Items::arrayItem([43,[5,1]]);
                              $userDr['egg'] = $userDr['egg'] + 1;
                              $jsonUpdate = Info::_parseData($userDr);
                              $stmt = Work::$sql->prepare("UPDATE users SET dr = ? WHERE id = ?"); 
                                                $stmt->bind_param("si", $jsonUpdate, $_SESSION['id']);
                                                $stmt->execute();
                                                
                              $time = time();
                              $stmt2 = Work::$sql->prepare('UPDATE user_egg SET reborn = ? WHERE id = ?'); 
                                                $stmt2->bind_param("ii", $time, $val[1]);
                                                $stmt2->execute();
                              $error = 8;
                              $type = 'success';
                            }else{
                              $error = 6;
                              $type = 'error';
                            }
                          }else{
                            $error = 7;
                            $type = 'error';
                          }
                        break;
                        case 250:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 9;
                                    $stmtQe->bind_param("ii", $reg, $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('egg', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'egg') {
                                if($need->quest1->id == Info::getEgg([$val[1],'basenum']) && $end[0] == 0) {
                                  $end[0] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = "'.$end.'" WHERE user = '.$userInfo['id'].' AND region = 9'); 
                                  $reg = 9;
                                                $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                                $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  
                                  $stmtD = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?"); 
                                  $reg = 9;
                                                $stmtD->bind_param("i", $val[1]);
                                                $stmtD->execute();
                                                
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest2->type == 'egg') {
                                if($need->quest2->id == Info::getEgg([$val[1],'basenum']) && $end[1] == 0) {
                                  $end[1] = 1;
                                  $end = implode(',',$end);
                                  
                                  $stmtU = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?'); 
                                  $reg = 9;
                                                $stmtU->bind_param("sii", $end, $userInfo['id'], $reg);
                                                $stmtU->execute();
                                                
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  
                                  $stmtD2 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?"); 
                                  $reg = 9;
                                                $stmtD2->bind_param("i", $val[1]);
                                                $stmtD2->execute();
                                                
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest3->type == 'egg') {
                                if($need->quest3->id == Info::getEgg([$val[1],'basenum']) && $end[2] == 0) {
                                  $end[2] = 1;
                                  $end = implode(',',$end);
                                  
                                  $stmtU2 = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?'); 
                                  $reg = 9;
                                                $stmtU2->bind_param("sii", $end, $userInfo['id'], $reg);
                                                $stmtU2->execute();
                                                
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  
                                  $stmtD3 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?"); 
                                                $stmtD3->bind_param("i", $val[1]);
                                                $stmtD3->execute();
                                                
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($descCheck == 0) {
                                $error = 4;
                                $type = 'error';
                              }
                            }else{
                              $error = 4;
                              $type = 'error';
                            }
                          }else{
                            $error = 4;
                            $type = 'error';
                          }
                        break;
                        case 251:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 1;
                                    $stmtQe->bind_param("ii", $reg, $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('egg', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'egg') {
                                if($need->quest1->id == Info::getEgg([$val[1],'basenum']) && $end[0] == 0) {
                                  $end[0] = 1;
                                  $end = implode(',',$end);
                                  $stmtU = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 1;
                                    $stmtU->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmtU->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);

                                  $stmtD = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD->bind_param("i", $val[1]);
                                    $stmtD->execute();
                                    
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest2->type == 'egg') {
                                if($need->quest2->id == Info::getEgg([$val[1],'basenum']) && $end[1] == 0) {
                                  $end[1] = 1;
                                  $end = implode(',',$end);
                                  
                                  $stmtU1 = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 1;
                                    $stmtU1->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmtU1->execute();
                                    
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  
                                  $stmtD = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD->bind_param("i", $val[1]);
                                    $stmtD->execute();
                                    
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest3->type == 'egg') {
                                if($need->quest3->id == Info::getEgg([$val[1],'basenum']) && $end[2] == 0) {
                                  $end[2] = 1;
                                  $end = implode(',',$end);
                                  $stmtU2 = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 1;
                                    $stmtU2->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmtU2->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                    
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($descCheck == 0) {
                                $error = 4;
                                $type = 'error';
                              }
                            }else{
                              $error = 4;
                              $type = 'error';
                            }
                          }else{
                            $error = 4;
                            $type = 'error';
                          }
                        break;
                        case 329:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 2;
                                    $stmtQe->bind_param("ii", $reg, $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('egg', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'egg') {
                                if($need->quest1->id == Info::getEgg([$val[1],'basenum']) && $end[0] == 0) {
                                  $end[0] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 2;
                                    $stmt->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest2->type == 'egg') {
                                if($need->quest2->id == Info::getEgg([$val[1],'basenum']) && $end[1] == 0) {
                                  $end[1] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 2;
                                    $stmt->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest3->type == 'egg') {
                                if($need->quest3->id == Info::getEgg([$val[1],'basenum']) && $end[2] == 0) {
                                  $end[2] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 2;
                                    $stmt->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($descCheck == 0) {
                                $error = 4;
                                $type = 'error';
                              }
                            }else{
                              $error = 4;
                              $type = 'error';
                            }
                          }else{
                            $error = 4;
                            $type = 'error';
                          }
                        break;
                        case 252:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 7;
                                    $stmtQe->bind_param("ii", $reg, $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('egg', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'egg') {
                                if($need->quest1->id == Info::getEgg([$val[1],'basenum']) && $end[0] == 0) {
                                  $end[0] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 7;
                                    $stmt->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                    
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest2->type == 'egg') {
                                if($need->quest2->id == Info::getEgg([$val[1],'basenum']) && $end[1] == 0) {
                                  $end[1] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 7;
                                    $stmt->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($need->quest3->type == 'egg') {
                                if($need->quest3->id == Info::getEgg([$val[1],'basenum']) && $end[2] == 0) {
                                  $end[2] = 1;
                                  $end = implode(',',$end);
                                  $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                  $reg = 7;
                                    $stmt->bind_param("sii",$end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                  $error = 10;
                                  $type = 'success';
                                  $minus = Items::arrayItem([54,[1,1]]);
                                  $stmtD1 = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?");
                                    $stmtD1->bind_param("i", $val[1]);
                                    $stmtD1->execute();
                                  $descCheck = 1;

                                  $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                }
                              }
                              if($descCheck == 0) {
                                $error = 4;
                                $type = 'error';
                              }
                            }else{
                              $error = 4;
                              $type = 'error';
                            }
                          }else{
                            $error = 4;
                            $type = 'error';
                          }
                        break;
                        default:
                          $error = 4;
                          $type = 'error';
                        break;
                      }
                    }
                  break;
                  case 'item':
                    $descCheck = 0;
                    if(Items::checkItemUserId($val[1])) {
                      switch($Npc['id']) {
						  
						  case 8017:
						  
							$questID = 9563;
							$stmtQ = Work::$sql->prepare("SELECT `step`,`quest_id`,`data` FROM `user_quests` WHERE `user_id` = ? AND `quest_id` = ?");
                                    $stmtQ->bind_param("ii", $_SESSION['id'], $questID);
                                    $stmtQ->execute();
                                    $dataQ = $stmtQ->get_result();
                                    $questInfo = $dataQ->fetch_assoc();
							
							if($questInfo["step"] >= 1 && $questInfo["step"] <= 6) {
								
								$need = explode(",", $questInfo["data"]);
								
								//$questItem = Work::$sql->query("SELECT `count` FROM `items_users` WHERE `user` = '".$_SESSION["id"]."' AND `item_id` = '".$need[0]."' AND `count` >= '".$need[1]."'")->fetch_assoc();
								$stmtQI = Work::$sql->prepare("SELECT `count`,`item_id` FROM `items_users` WHERE `user` = ? AND `id` = ? AND `count` >= ?");
                                    $stmtQI->bind_param("iii", $_SESSION['id'], $val[1], $need[1]);
                                    $stmtQI->execute();
                                    $dataQI = $stmtQI->get_result();
                                    $questItem = $dataQI->fetch_assoc();
								
								$stmtIRI = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
								$zero = 0;
                                    $stmtIRI->bind_param("ii", $val[1], $zero);
                                    $stmtIRI->execute();
                                    $dataIRI = $stmtIRI->get_result();
                                    $isRentItem = $dataIRI->fetch_assoc();
								
								if(!$questItem["count"] || $questItem["item_id"] != $need[0] || isset($isRentItem)) {
									
									$error = 5;
									$type = 'error';
									
								} else {
									
									$newCount = $need[1] - 1;
									
									$error = 10;
									$type = 'success';
									$minus = Items::arrayItem([$need[0],[1,1]]);
									$dialog = 8017;
									
									if($newCount == 0) {
										
										$step = 6;
										
									} else {
										
										$step = 5;
										
									}
									
									$newNeed = "".$need[0].",".$newCount;
									
									$stmt = Work::$sql->prepare("UPDATE `user_quests` SET `data` = ? WHERE `user_id` = ? AND `quest_id` = ?");
                                    $stmt->bind_param("sii", $newNeed, $_SESSION['id'], $questID);
                                    $stmt->execute();
									
									minus_item($need[0], 1);
									
								}
								
							} else {
								
								$error = 3;
								$type = 'error';
								
							}
						  
						break;
						  
						  
                        // case 270:
                        // if(in_array(Items::baseItemUserId([$val[1],'id']), [104,2,103,105])) {
                        //   $Item270 = Work::$sql->query('SELECT id,str FROM items_users WHERE item_id = '.Items::baseItemUserId([$val[1],'id']).' AND user = '.$userInfo['id']);
                        //   $StrItem270 = [];
                        //   $Item270Comp = 0;
                        //   while($i270 = $Item270->fetch_assoc()) {
                        //     $str = explode(',',$i270['str']);
                        //     if($str[0] <= 0) {
                        //       array_push($StrItem270, $i270['id']);
                        //     }
                        //     if(count($StrItem270) >= 3) {
                        //       $Item270Comp = 1;
                        //       break;
                        //     }
                        //   }
                        //   if($Item270Comp == 1) {
                        //     if(item_isset(1,10000)) {
                        //       minus_item(1,10000);
                        //       itemAdd(Items::baseItemUserId([$val[1],'id']),1);
                        //       $itemMinusArr = [Items::arrayItem([Items::baseItemUserId([$val[1],'id']),[3,1]]),Items::arrayItem([1,[10000,1]])];
                        //       $plus = Items::arrayItem([Items::baseItemUserId([$val[1],'id']),[1,1]]);
                        //       Work::$sql->query("DELETE FROM items_users WHERE id = ".$StrItem270[0]);
                        //       Work::$sql->query("DELETE FROM items_users WHERE id = ".$StrItem270[1]);
                        //       Work::$sql->query("DELETE FROM items_users WHERE id = ".$StrItem270[2]);
                        //       $error = 10;
                        //       $type = 'success';
                        //     }else{
                        //       $error = 6;
                        //       $type = 'error';
                        //     }
                        //   }else{
                        //     $error = 5;
                        //     $type = 'error';
                        //   }
                        // }
                        // break;
                        case 252:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 7;
                                    $stmtQe->bind_param("ii", $reg, $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('item', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'item') {
                                if($need->quest1->id == Items::baseItemUserId([$val[1],'id']) && $end[0] == 0) {
                                  if(item_isset($need->quest1->id,$need->quest1->count)) {
                                    $end[0] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 7;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest1->id,[$need->quest1->count,1]]);
                                    minus_item_id($val[1],$need->quest1->count);
                                    $descCheck = 1;
                                    $idEvent = WeekEvents::getWeekEvent();
                                    if($idEvent == 1) {
                                      $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
                                      WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
                                    }
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest2->type == 'item') {
                                if($need->quest2->id == Items::baseItemUserId([$val[1],'id']) && $end[1] == 0) {
                                  if(item_isset($need->quest2->id,$need->quest2->count)) {
                                    $end[1] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 7;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest2->id,[$need->quest2->count,1]]);
                                    minus_item_id($val[1],$need->quest2->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest3->type == 'item') {
                                if($need->quest3->id == Items::baseItemUserId([$val[1],'id']) && $end[2] == 0) {
                                  if(item_isset($need->quest3->id,$need->quest3->count)) {
                                    $end[2] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 7;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest3->id,[$need->quest3->count,1]]);
                                    minus_item_id($val[1],$need->quest3->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
                                    if($idEvent == 1) {
                                      $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
                                      WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
                                    }
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($descCheck == 0) {
                                $error = 3;
                                $type = 'error';
                              }
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }else{
                            $error = 3;
                            $type = 'error';
                          }
                        break;
                        case 251:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 1;
                                    $stmtQe->bind_param("ii", $reg,  $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('item', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'item') {
                                if($need->quest1->id == Items::baseItemUserId([$val[1],'id']) && $end[0] == 0) {
                                  if(item_isset($need->quest1->id,$need->quest1->count)) {
                                    $end[0] = 1;
                                    $end = implode(',',$end);
                                   $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 1;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest1->id,[$need->quest1->count,1]]);
                                    minus_item_id($val[1],$need->quest1->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest2->type == 'item') {
                                if($need->quest2->id == Items::baseItemUserId([$val[1],'id']) && $end[1] == 0) {
                                  if(item_isset($need->quest2->id,$need->quest2->count)) {
                                    $end[1] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 1;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest2->id,[$need->quest2->count,1]]);
                                    minus_item_id($val[1],$need->quest2->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest3->type == 'item') {
                                if($need->quest3->id == Items::baseItemUserId([$val[1],'id']) && $end[2] == 0) {
                                  if(item_isset($need->quest3->id,$need->quest3->count)) {
                                    $end[2] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 1;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest3->id,[$need->quest3->count,1]]);
                                    minus_item_id($val[1],$need->quest3->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($descCheck == 0) {
                                $error = 3;
                                $type = 'error';
                              }
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }else{
                            $error = 3;
                            $type = 'error';
                          }
                        break;
                        case 329:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 2;
                                    $stmtQe->bind_param("ii", $reg,  $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('item', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'item') {
                                if($need->quest1->id == Items::baseItemUserId([$val[1],'id']) && $end[0] == 0) {
                                  if(item_isset($need->quest1->id,$need->quest1->count)) {
                                    $end[0] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 2;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest1->id,[$need->quest1->count,1]]);
                                    minus_item_id($val[1],$need->quest1->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest2->type == 'item') {
                                if($need->quest2->id == Items::baseItemUserId([$val[1],'id']) && $end[1] == 0) {
                                  if(item_isset($need->quest2->id,$need->quest2->count)) {
                                    $end[1] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 2;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest2->id,[$need->quest2->count,1]]);
                                    minus_item_id($val[1],$need->quest2->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest3->type == 'item') {
                                if($need->quest3->id == Items::baseItemUserId([$val[1],'id']) && $end[2] == 0) {
                                  if(item_isset($need->quest3->id,$need->quest3->count)) {
                                    $end[2] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 2;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest3->id,[$need->quest3->count,1]]);
                                    minus_item_id($val[1],$need->quest3->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($descCheck == 0) {
                                $error = 3;
                                $type = 'error';
                              }
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }else{
                            $error = 3;
                            $type = 'error';
                          }
                        break;
                        case 250:
                          $stmtQe = Work::$sql->prepare('SELECT * FROM region_desc WHERE region = ? AND user = ?');
                          $reg = 9;
                                    $stmtQe->bind_param("ii", $reg,  $userInfo['id']);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $desc = $dataQe->fetch_assoc();
                          if(isset($desc)){
                            $end = explode(',',$desc['end']);
                            $need = json_decode($desc['val']);
                            if(in_array('item', [$need->quest1->type,$need->quest2->type,$need->quest3->type])){
                              if($need->quest1->type == 'item') {
                                if($need->quest1->id == Items::baseItemUserId([$val[1],'id']) && $end[0] == 0) {
                                  if(item_isset($need->quest1->id,$need->quest1->count)) {
                                    $end[0] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 9;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest1->id,[$need->quest1->count,1]]);
                                    minus_item_id($val[1],$need->quest1->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest2->type == 'item') {
                                if($need->quest2->id == Items::baseItemUserId([$val[1],'id']) && $end[1] == 0) {
                                  if(item_isset($need->quest2->id,$need->quest2->count)) {
                                    $end[1] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 9;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest2->id,[$need->quest2->count,1]]);
                                    minus_item_id($val[1],$need->quest2->count);
                                    $descCheck = 1;
                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($need->quest3->type == 'item') {
                                if($need->quest3->id == Items::baseItemUserId([$val[1],'id']) && $end[2] == 0) {
                                  if(item_isset($need->quest3->id,$need->quest3->count)) {
                                    $end[2] = 1;
                                    $end = implode(',',$end);
                                    $stmt = Work::$sql->prepare('UPDATE region_desc SET end = ? WHERE user = ? AND region = ?');
                                    $reg = 9;
                                    $stmt->bind_param("sii", $end, $userInfo['id'], $reg);
                                    $stmt->execute();
                                    $error = 10;
                                    $type = 'success';
                                    $minus = Items::arrayItem([$need->quest3->id,[$need->quest3->count,1]]);
                                    minus_item_id($val[1],$need->quest3->count);
                                    $descCheck = 1;

                                    $idEvent = WeekEvents::getWeekEvent();
if($idEvent == 1) {
  $eventMy = WeekEvents::getWeekEventMy($userInfo['id']);
  WeekEvents::updateScore(1,1,$eventMy['score'],$userInfo['id']);
}
                                  }else{
                                    $error = 5;
                                    $type = 'error';
                                  }
                                }
                              }
                              if($descCheck == 0) {
                                $error = 3;
                                $type = 'error';
                              }
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }else{
                            $error = 3;
                            $type = 'error';
                          }
                        break;
                        case 323:
                          if(Info::quest_step(14,5)) {
                            if(Items::baseItemUserId([$val[1],'id']) == 105) {
                              $stmtQe = Work::$sql->prepare('SELECT need FROM npc_more_quest WHERE user_id = ? AND quest_id = ?');
                              $qID = 323;
                                    $stmtQe->bind_param("ii", $userInfo['id'], $qID);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $quest323 = $dataQe->fetch_assoc();
                              if($quest323['need'] == 2) {
                                $zero = 0;
                                $stmt1 = Work::$sql->prepare('UPDATE npc_more_quest SET need = ? WHERE user_id = ? AND quest_id = ?');
                                  $qID = 323;
                                    $stmt1->bind_param("sii", $zero, $userInfo['id'], $qID);
                                    $stmt1->execute();
                                Info::quest_update(14,6);
                                $dialog = 323;
                                $step = 5;
                              }else{
                                $dialog = 323;
                                $step = 4;
                                $quest323 = $quest323['need'] + 1;
                                $stmt = Work::$sql->prepare('UPDATE npc_more_quest SET need = ? WHERE user_id = ? AND quest_id = ?');
                                  $qID = 323;
                                    $stmt->bind_param("sii", $quest323, $userInfo['id'], $qID);
                                    $stmt->execute();
                              }
                              minus_item_id($val[1],1);
                              $minus = Items::arrayItem([105,[1,1]]);
                              $error = 10;
                              $type = 'success';
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }elseif(Info::quest_step(14,6)) {
                            if(Items::baseItemUserId([$val[1],'id']) == 116) {
                              $dialog = 323;
                              $step = 5;
                              Info::quest_update(14,7);
                              minus_item_id($val[1],1);
                              $minus = Items::arrayItem([116,[1,1]]);
                              $error = 10;
                              $type = 'success';
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }
                        break;
                        case 205:
                          if(Info::quest_step(10,3)) {
                            if(Items::baseItemUserId([$val[1],'id']) == 76) {
                              if(item_isset(76,1)) {
                                $stmtQe = Work::$sql->prepare('SELECT need FROM npc_more_quest WHERE user_id = ? AND quest_id = ?');
                                  $qID = 205;
                                    $stmtQe->bind_param("ii", $userInfo['id'], $qID);
                                    $stmtQe->execute();
                                    $dataQe = $stmtQe->get_result();
                                    $quest205 = $dataQe->fetch_assoc();
                                if($quest205['need'] == 0) {
                                  $quest205 = $quest205['need'] + 1;
                                  $stmt = Work::$sql->prepare('UPDATE npc_more_quest SET need = ? WHERE user_id = ? AND quest_id = ?');
                                  $qID = 205;
                                    $stmt->bind_param("sii", $quest205, $userInfo['id'], $qID);
                                    $stmt->execute();
                                  
                                }else{
                                  Work::$sql->query('UPDATE npc_more_quest SET need = 0 WHERE user_id = '.$userInfo['id'].' AND quest_id = 205');
                                  $stmt1 = Work::$sql->prepare('UPDATE npc_more_quest SET need = ? WHERE user_id = ? AND quest_id = ?');
                                  $qID = 205;
                                    $stmt1->bind_param("sii", $zero, $userInfo['id'], $qID);
                                    $stmt1->execute();
                                  itemAdd(25,1);
                                  $plus = Items::arrayItem([25,[1,1]]);
                                }
                                minus_item_id($val[1],1);
                                $error = 10;
                                $type = 'success';
                                $minus = Items::arrayItem([76,[1,1]]);
                              }else{
                                $error = 5;
                                $type = 'error';
                              }
                            }else{
                              $error = 3;
                              $type = 'error';
                            }
                          }else{
                            $error = 3;
                            $type = 'error';
                          }
                        break;
                        default:
                          $error = 3;
                          $type = 'error';
                        break;
                      }
                    }
                  break;
                }
              }
            }else{
              $error = 1;
              $type = 'error';
            }
			
            $this->response['response'] = array(
              'dialog' => (isset($dialog) ? $dialog : 0),
              'step' => (isset($step) ? $step : 0),
              'error' => $error,
              'type' => $type,
              'minus' => (isset($minus) ? $minus : 0),
              'plus' => (isset($plus) ? $plus : 0),
              'itemMinusArr' => (isset($itemMinusArr) ? $itemMinusArr : 0)
            );
          break;

          case 'open':
            $stmtQe = Work::$sql->prepare("SELECT * FROM base_npc WHERE loc_id = ?");
                                    $stmtQe->bind_param("i", $userInfo['location']);
                                    $stmtQe->execute();
                                    $Npcs = $stmtQe->get_result();
            $npc = [];
            while($NpcInfo = $Npcs->fetch_assoc()) {
              $npc[$NpcInfo['id']] = [
                'id' => $NpcInfo['id'],
                'name' => $NpcInfo['name'],
                'type' => $val[0],
                'object' => $val[1]
              ];
            }
			
			$meUser = $userInfo;
			$mysqli = Work::$sql;
			
			include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/npc/controllerAppearence.php');
			
			//TODO: В корне неверная конструкция(отдача type=val0,object=val1, которые должны на клиенте априори быть), в будущем убрать и переделать клиент
			for($i = 0; $i < count($npc); ++$i) {
				
				if(!isset($npc[$i]["type"])) {
					
					$npc[$i]["type"] = $val[0];
					
				}
				
				if(!isset($npc[$i]["object"])) {
					
					$npc[$i]["object"] = $val[1];
					
				}
				
			}
			
            $this->response['response'] = array(
              'npc_list' => $npc
            );
			
          break;

        }

    }

  }

}

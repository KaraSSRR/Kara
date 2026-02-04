<?php

Class Attack {

  private $response = [];
  private $userInfo = [];
  private $Error = 0;

  public function __construct($type, $val = [], $pok, array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;
      $stmt = Work::$sql->prepare("SELECT id,attacks,basenum,pp_attacks,form,lvl FROM user_pokemons WHERE user_id = ? AND active = 1 AND id = ?");
        $stmt->bind_param("ii", $userInfo['id'], $pok);
        $stmt->execute();
        $data = $stmt->get_result();
        $Pokemon = $data->fetch_assoc();
            switch($type){
              case 'forgetGet':
                if($userInfo['status'] == 'free') {
                  if(isset($Pokemon)) {
                    $atkNumb = clearInt($val[0]);
                     $stmtTMl = Work::$sql->prepare("SELECT id FROM user_pokemons_tm WHERE pok = ? AND attacks = ?");
                        $stmtTMl->bind_param("ii", $Pokemon['id'], $atkNumb);
                        $stmtTMl->execute();
                        $dataTM = $stmtTMl->get_result();
                        $tmList = $dataTM->fetch_assoc();
                        
                    if(isset($tmList)) {
                      $this->Error = 2;
                    }else{
                      $pokAtk = explode(',',$Pokemon['attacks']);
                      if(in_array($atkNumb,$pokAtk)){
              					$this->Error = 2;
              				}else{
                        if(item_isset(288,1)) {
                          minus_item(288,1);
                          $stmtI = Work::$sql->prepare('INSERT INTO user_pokemons_tm (pok,attacks) VALUES (?,?) '); 
                            $stmtI->bind_param("ii", $pok, $atkNumb);
                            $stmtI->execute();
                          $minus = Items::arrayItem([288,[1,1]]);
                        }else{
                          $this->Error = 7;
                        }
                      }
                    }
                  }else{
                    $this->Error = 4;
                  }
                }else{
                  $this->Error = 1;
                }
                $this->response['response'] = array(
                  'error' => $this->Error,
                  'minus' => (isset($minus) ? $minus : 0)
                );
              break;
              case 'lvl':
                if($userInfo['status'] == 'free') {
                  if(isset($Pokemon)) {
					  
                    $positionAtk = $val[0];
                    $atkNumb = clearInt($val[2]);
					
					$stmtAtkL = Work::$sql->prepare("SELECT `attacks` FROM `base_attacks_pokemons` WHERE `pok` = ?");
                        $stmtAtkL->bind_param("i",$Pokemon['basenum']);
                        $stmtAtkL->execute();
                        $dataAtkList = $stmtAtkL->get_result();
                        $atkList = $dataAtkList->fetch_assoc();
					$stmtTML = Work::$sql->prepare("SELECT `id` FROM `user_pokemons_tm` WHERE `pok` = ? AND `attacks` = ?");
                        $stmtTML->bind_param("ii", $Pokemon['id'], $atkNumb);
                        $stmtTML->execute();
                        $dataTML = $stmtTML->get_result();
                        $tmList = $dataTML->fetch_assoc();
					
                    $attacks = explode(',',$atkList['attacks']);
					if(in_array($atkNumb,$attacks) || !empty($tmList['id'])){
						
                      $pokAtk = explode(',',$Pokemon['attacks']);
                      if(in_array($atkNumb,$pokAtk)){
              			$this->Error = 2;
              		  }else{
						$pokPPAtk = explode(',',$Pokemon['pp_attacks']);
						$pokAtk[$positionAtk] = $atkNumb;
						$pokPPAtk[$positionAtk] = 0;
						$implodeAtk = implode(',',$pokAtk);
						$implodePPAtk = implode(',',$pokPPAtk);
						if(!empty($tmList['id'])){
							$q = Work::$sql->prepare('DELETE FROM user_pokemons_tm WHERE id = ?'); 
                                if($q)
                                {
                                    $q->bind_param("i", $tmList['id']);
                                    $q->execute();
                                   $q->close();
                                }
						}
						$stmt = Work::$sql->prepare("UPDATE user_pokemons SET attacks = ?, pp_attacks = ? WHERE id = ?");
                            $stmt->bind_param("ssi", $implodeAtk, $implodePPAtk, intval($Pokemon['id']));
                            $stmt->execute();
                            $stmt->close();
						$this->Error = 0;
                      }
                    }else{
                      $this->Error = 3;
                    }
                  }else{
                    $this->Error = 4;
                  }
                }else{
                  $this->Error = 1;
                }
                $this->response['response'] = array(
                  'error' => $this->Error
                );
              break;
              case 'forget':
                if($userInfo['status'] == 'free') {
                  $stmt = Work::$sql->prepare("SELECT * FROM base_attacks_pokemons WHERE pok = ? AND type = 'lvl' AND form = ?");
                    $stmt->bind_param("ii", $Pokemon['basenum'], $Pokemon['form']);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $AttackLearn = $data->fetch_assoc();
                  $attacks = explode(',',$AttackLearn['attacks']);
                  $lvl = explode(',',$AttackLearn['lvl']);
                  $countAtk = sizeof($attacks);
                  $atkListID = [];
                  $baseAtkList = [];
                  for($i=0; $i<=$countAtk; $i++){
                      if(isset($attacks[$i], $lvl[$i])){
                          if($lvl[$i] > $Pokemon['lvl']){
                              continue;
                          }
                          $atkListID[] = intval($attacks[$i]);
                      }
                  }
                  $atkB = [];
                  $baseAtk = Work::$sql->query('SELECT * FROM base_atk WHERE id IN ('.Work::$sql->real_escape_string(implode(',', $atkListID)).')');
                  if(!empty($baseAtk)) {
                    while($row = $baseAtk->fetch_assoc()){
                      $atkB[] = $row;
                    }
                  }else{
                    $noneAtk = 1;
                  }
                  if(!empty($atkB)){
                    foreach($atkB AS $key=>$value){
                      $baseAtkList[$value['id']] = Info::getMainAttack($value['id']);
                    }
                  }
                  if(isset($noneAtk)) {
                    $this->Error = 6;
                  }else{
                    $this->Error = 0;
                  }
                }else{
                  $this->Error = 1;
                }
                $this->response['response'] = array(
                  'list_atk' => (isset($baseAtkList) ? $baseAtkList : 0),
                  'error' => $this->Error
                );
              break;
              case 'switchView':
                if($userInfo['status'] == 'free') {
                  if(isset($Pokemon)) {
                    $stmtTM = Work::$sql->prepare('SELECT attacks FROM user_pokemons_tm WHERE pok = ?');
                        $stmtTM->bind_param("i", $Pokemon['id']);
                        $stmtTM->execute();
                        $pokTM = $stmtTM->get_result();
                    $atkListID = [];
                    $baseAtkList = [];
                    if(!empty($pokTM)){
          						while($atkTM = $pokTM->fetch_assoc()){
          							$atkListID[] = $atkTM['attacks'];
          						}
          					}
                    $atkB = [];
                    $baseAtk = Work::$sql->query('SELECT * FROM base_atk WHERE id IN ('.implode(',', $atkListID).')');
                    if(!empty($baseAtk)) {
                      while($row = $baseAtk->fetch_assoc()){
                        $atkB[] = $row;
                      }
                    }else{
                      $noneAtk = 1;
                    }
                    if(!empty($atkB)){
                      foreach($atkB AS $key=>$value){
                        $baseAtkList[$value['id']] = Info::getMainAttack($value['id']);
                      }
                    }
                    if(isset($noneAtk)) {
                      $this->Error = 6;
                    }else{
                      $this->Error = 0;
                    }
                  }else{
                    $this->Error = 4;
                  }
                }else{
                  $this->Error = 1;
                }
                $this->response['response'] = array(
                  'list_atk' => (isset($baseAtkList) ? $baseAtkList : 0),
                  'error' => $this->Error
                );
              break;
              case 'learn':
                if($userInfo['status'] == 'free') {
                  if(isset($Pokemon)) {
					$val[0] = clearInt($val[0]);
                    $stmt = Work::$sql->prepare("SELECT * FROM base_attacks_pokemons WHERE pok = ? AND type = 'npc' AND attacks = ?");
                        $stmt->bind_param("is", $Pokemon['basenum'], $val[0]);
                        $stmt->execute();
                        $stmt->close();
                        $data = $stmt->get_result();
                        $AttackLearn = $data->fetch_assoc();
                    if(isset($AttackLearn)) {
                      switch($val[0]) {
                        case 233:
                        case 476:
                        case 135:
                        case 270:
                        case 103:
                          if(Info::getStringNpc(['id',295,'loc_id']) == $userInfo['location']) {
                            if(item_isset(163,1400)) {
                              $this->Learn($Pokemon['id'],$val[0]);
                              $minus = '<img src="/img/world/items/little/163.webp" class="item"> Бамбук x1.400';
                              minus_item(163,1400);
                            }else{
                              $this->Error = 2;
                            }
                          }else{
                            $this->Error = 3;
                          }
                        break;
                        case 107:
                          if(Info::quest_step(14,12) || Info::quest_step(14,13) || Info::quest_step(14,14) || Info::quest_step(14,15) || Info::quest_step(14,16) || Info::quest_step(14,17)) {
                            if(Info::getStringNpc(['id',323,'loc_id'])  == $userInfo['location']) {
                              if(item_isset(145,3)) {
                                $minus = '<img src="/img/world/items/little/145.webp" class="item"> Напиток знаний x3';
                                $this->Learn($Pokemon['id'],$val[0]);
                                minus_item(145,3);
                              }else{
                                $this->Error = 2;
                              }
                            }else{
                              $this->Error = 3;
                            }
                          }else{
                            $this->Error = 3;
                          }
                        break;
                        case 552:
                          if(Info::quest_step(14,12) || Info::quest_step(14,13) || Info::quest_step(14,14) || Info::quest_step(14,15) || Info::quest_step(14,16) || Info::quest_step(14,17)) {
                            if(Info::getStringNpc(['id',323,'loc_id'])  == $userInfo['location']) {
                              if(item_isset(145,2)) {
                                $minus = '<img src="/img/world/items/little/145.webp" class="item"> Напиток знаний x2';
                                $this->Learn($Pokemon['id'],$val[0]);
                                minus_item(145,2);
                              }else{
                                $this->Error = 2;
                              }
                            }else{
                              $this->Error = 3;
                            }
                          }else{
                            $this->Error = 3;
                          }
                        break;
                        case 547:
                          if(Info::quest_step(2019,1) && Info::getStringNpc(['id',225,'loc_id']) == $userInfo['location']) {
                            $this->Learn($Pokemon['id'],$val[0]);
                            Info::quest_update(2019,2);
                          }else{
                            $this->Error = 3;
                          }
                        break;
                        case 198:
                          if(Info::quest_step(16,9) && Info::getStringNpc(['id',244,'loc_id']) == $userInfo['location']) {
                            if(item_isset(145,1)) {
                              $minus = '<img src="/img/world/items/little/145.webp" class="item"> Напиток знаний';
                              $this->Learn($Pokemon['id'],$val[0]);
                              minus_item(145,1);
                            }else{
                              $this->Error = 2;
                            }
                          }else{
                            $this->Error = 3;
                          }
                        break;
                        case 235:
                          if(Info::quest_step(11,2) && Info::getStringNpc(['id',206,'loc_id']) == $userInfo['location']) {
                            if(item_isset(145,3)) {
                              $minus = '<img src="/img/world/items/little/145.webp" class="item"> Напиток знаний x3';
                              $this->Learn($Pokemon['id'],$val[0]);
                              minus_item(145,3);
                            }else{
                              $this->Error = 2;
                            }
                          }else{
                            $this->Error = 3;
                          }
                        break;
                      }
                    }else{
                      $this->Error = 5;
                    }
                  }else{
                    $this->Error = 4;
                  }
                }else{
                  $this->Error = 1;
                }
                $this->response['response'] = array(
                  'error' => $this->Error,
                  'minus' => (isset($minus) ? $minus : 0),
                  'val' => (isset($response_val) ? $response_val : 0)
                );
              break;

            }

    }

  }

  private function Learn($pok, $id) {
    $stmt = Work::$sql->prepare('INSERT INTO user_pokemons_tm (pok,attacks) VALUES (?,?) '); 
                $stmt->bind_param("ii", $pok, $id);
                $stmt->execute();
                $stmt->close();
    
  }

}

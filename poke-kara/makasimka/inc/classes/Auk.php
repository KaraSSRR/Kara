<?php

Class Auk {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'action':
          switch($val[0]) {

		    case 'cancel':
		  		$val[1] = intval($val[1]);
				$stmt = Work::$sql->prepare('SELECT user, close, user_bet FROM auk WHERE id = ?');
                    $stmt->bind_param("i", intval($val[1]));
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $lot = $data->fetch_assoc();
				if($lot['user'] == $userInfo['id'] && $lot['user_bet'] == 0 && $lot['close'] == 0) {
					$sql = "UPDATE auk SET close = 1 WHERE id = ?";
                        $stmt = Work::$sql->prepare($sql);
                        $stmt->bind_param("i", intval($val[1]));
                        $stmt->execute();
					$this->response['response'] = array(
	                  'error' => 16
	                );
				}
		    break;

            case 'get':
			
              $count = intval($val[3]);
              if(empty($val[6])) {
                $autobuy = 0;
              }else{
                $autobuy = intval($val[6]);
              }
              $day = intval($val[7]);
              $day = ($day > 30 ? 30 : $day);
              $day = ($day < 1 ? 1 : $day);
              $now = intval($val[4]);
              $min = intval($val[5]);
              $time = ($day * 24 * 60 * 60) + time();
              /* if($min < 5000) {
                $error = 7;
              } */if(false) {
				  
			  } else{
                if($autobuy < 0 || $count < 0 || $day < 0 || $now < 0) {
                  $error = 14;
                }else{
                  if(item_isset(1,20000)) {
                    if(Info::getLocation(['id',$userInfo['location'],'trade']) == 1) {
                      $error = 13;
                    }else{
                      if($userInfo['status'] == 'free'){
						  
						  include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/constants/minusMoney.php');
						  
                        if($val[2] == 'egg') {
							
							/* $this->response['response'] = array(
								'error' => 13,
								'sell' => 0
							);
							
							return; */
							
						  intval($val[1]);
                          $egg = Work::$sql->query('SELECT * FROM user_egg WHERE user = '.$userInfo['id'].' AND id = '.$val[1])->fetch_assoc();
                          if(isset($egg)) {
							$textLot = "Яйцо покемона #".Info::getNumPokemonNum($egg['basenum'])." ".Info::getPokemonBase(['id',$egg['basenum'],'name_rus']);
							
                            $stmtE = Work::$sql->prepare("INSERT INTO auk (type,split,id_type,id_bet,count,autobuy,time_start,time_end,min_bet,now_bet,user,user_bet,text) VALUES ('egg',0,54,?,1,?,?,?,?,?,?,0,?) "); 
                            $timeX = time();
                                $stmtE->bind_param("iiiiiiis", $egg['id'], $autobuy, $timeX, $time, $min, $now, $userInfo['id'], $textLot);
                                $stmtE->execute();
                                
                            $sql = "UPDATE user_egg SET user = 2 WHERE id = ?";
                                $stmtS = Work::$sql->prepare($sql);
                                $stmtS->bind_param("i", $egg['id']);
                                $stmtS->execute();
                                
                            $sell = '<img src="/img/world/items/little/54.png" class="item"> Яйцо покемона';
                            $error = 8;
							
							if(\matsukaConstants\minusMoneyFor["aukAction"] > 0) {
							
								minus_item(1, \matsukaConstants\minusMoneyFor["aukAction"]);
								
							}
							
                          }
                        }elseif($val[2] == 'pokemon') {
							
							/* $this->response['response'] = array(
								'error' => 13,
								'sell' => 0
							);
							
							return; */
				
						  intval($val[1]);
                          $stmt = Work::$sql->prepare('SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
                          $active = 1;
                            $stmt->bind_param("iii", $userInfo['id'],$active, $val[1]);
                            $stmt->execute();
                            $data = $stmt->get_result();
                            $pokemon = $data->fetch_assoc();
						  
							if($pokemon["realMOwner"] > 0) {

								$this->response['response'] = array(
									'error' => 18,
									'sell' => 0
								);
								
								return;

							}
						  
						  $textLot = "#".Info::getNumPokemonNum($pokemon['basenum'])." ".Info::getPokemonBase(['id',$pokemon['basenum'],'name_rus']);
                          if($pokemon['trade'] == 'false') {
                            $error = 9;
                          }else{
                            if($pokemon['item_id'] == 0) {
                                
                              $sql = "UPDATE user_pokemons SET user_id = 2 WHERE id = ?";
                                $stmtU = Work::$sql->prepare($sql);
                                $stmtU->bind_param("i", $pokemon['id']);
                                $stmtU->execute();
                                
                              $stmtS = Work::$sql->prepare("INSERT INTO auk (type,split,id_type,id_bet,count,autobuy,time_start,time_end,min_bet,now_bet,user,user_bet,text) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?) "); 
                              $timeX = time();
                              $zero = 0;
                              $one = 1;
                              $pO = "pokemon";
                                $stmtS->bind_param("siiiiiiiiiis", $pO,$zero, $pokemon['basenum'], $pokemon['id'], $autobuy,$one, $timeX, $time, $min, $now, $userInfo['id'], $zero, $textLot);
                                $stmtS->execute();
                                
                              $sell = '<img src="/img/pokemons/anim/normal/'.$pokemon['basenum'].'.gif"> #'.Info::getNumPokemonNum($pokemon['basenum']).' '.Info::getPokemonBase(['id',$pokemon['basenum'],'name_rus']);
                              $error = 8;
                              
								if(\matsukaConstants\minusMoneyFor["aukAction"] > 0) {
									
									minus_item(1, \matsukaConstants\minusMoneyFor["aukAction"]);
									
								}
							  
                            }else{
                              $error = 12;
                            }
                          }
                        }elseif($val[2] == 'item') {
							
						  intval($val[1]);
                          $stmt = Work::$sql->prepare('SELECT * FROM items_users WHERE user = ? AND id = ?');
                            $stmt->bind_param("ii", $userInfo['id'], $val[1]);
                            $stmt->execute();
                            $data = $stmt->get_result();
                            $item = $data->fetch_assoc();
						  
						  $textLot = Info::getItemBase(['id',$item['item_id'],'name']);
						  
                          if(isset($item)) {
							  
							$stmtRent = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
							$zero = 0;
                                $stmtRent->bind_param("ii", $val[1], $zero);
                                $stmtRent->execute();
                                $dataRent = $stmtRent->get_result();
                                $isRentItem = $dataRent->fetch_assoc();
							  
							if(isset($isRentItem)) {
								  
								$this->response['response'] = array(
									'error' => 18,
									'sell' => 0
								);

								return;
								  
							}
							  
                            if(Info::getItemBase(['id',$item['item_id'],'trade']) == 'true' && $item['item_id'] != 1 && $item['crash'] != 1) {
                                
                              if($item['split'] == 1 || !empty($item['str'])) {
								  
                                $stmt = Work::$sql->prepare("UPDATE items_users SET user = 2 WHERE id = ?");
                                    $stmt->bind_param("i", $item['id']);
                                    $stmt->execute();
                                    
                                $stmtI = Work::$sql->prepare("INSERT INTO auk (type,split,id_type,id_bet,count,autobuy,time_start,time_end,min_bet,now_bet,user,user_bet,text) VALUES ('item',1,?,?,1,?,?,?,?,?,?,0,?) "); 
                                $timeX = time();
                                    $stmtI->bind_param("iiiiiiiis", $item['item_id'], $item['id'], $autobuy, $timeX, $time, $min, $now, $userInfo['id'], $textLot);
                                    $stmtI->execute();
                                    
                                $sell = '<img src="/img/world/items/little/'.$item['item_id'].'.png" class="item"> '.Info::getItemBase(['id',$item['item_id'],'name']).' '.Items::countItem(1,1);
                                $error = 8;
                                
								if(\matsukaConstants\minusMoneyFor["aukAction"] > 0) {
									
									minus_item(1, \matsukaConstants\minusMoneyFor["aukAction"]);
									
								}
								
                              }else{
                                if($count <= 0) {
                                  $error = 10;
                                }else{
                                  if(item_isset($item['item_id'],$count)) {

                                    minus_item($item['item_id'],$count);
                                    $error = 8;
                                    
									if(\matsukaConstants\minusMoneyFor["aukAction"] > 0) {
									
										minus_item(1, \matsukaConstants\minusMoneyFor["aukAction"]);
										
									}
									$timeX = time();
                                    $sell = '<img src="/img/world/items/little/'.$item['item_id'].'.png" class="item"> '.Info::getItemBase(['id',$item['item_id'],'name']).' '.Items::countItem($count,1);
                                    $stmt = Work::$sql->prepare("INSERT INTO auk (type,split,id_type,id_bet,count,autobuy,time_start,time_end,min_bet,now_bet,user,user_bet,text) VALUES ('item',0,?,0,?,?,?,?,?,?,?,0,?) "); 
                                        $stmt->bind_param("iiiiiiiis", $item['item_id'], $count, $autobuy, $timeX, $time, $min, $now, $userInfo['id'], $textLot);
                                        $stmt->execute();
                                  }
                                }
                              }
                            }else{
                              $error = 11;
                            }
                          }
                        }
                      }else{
                        $error = 1;
                      }
                    }
                  }else{
                    $error = 4;
                  }
                }
              }
              $this->response['response'] = array(
                'error' => $error,
                'sell' => (isset($sell) ? $sell : 0)
              );
            break;
            case 'buy':
              $stmt = Work::$sql->prepare('SELECT * FROM auk WHERE id = ?');
                    $stmt->bind_param("i", $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $auk = $data->fetch_assoc();
                    
              if(isset($auk) && $userInfo['status'] == 'free' && $auk['autobuy'] != 0 && $auk['close'] != 1) {
                  
                if(Info::getLocation(['id',$userInfo['location'],'trade']) == 1) {
                    
                  $error = 13;
                  
                }else{
                    
                  if(item_isset(1,$auk['autobuy'])) {
                      
                    itemAdd(1,$auk['autobuy'], $auk['user']);
                    minus_item(1,$auk['autobuy']);
                    
                    if($auk['user_bet'] != 0) {
                        
                      itemAdd(1,$auk['now_bet'],$auk['user_bet']);
					  Notify::create_bell_noty("/img/world/notify/auk.png","<b>Лот #".$auk['id']." - ".$auk["text"]."</b>, на который вы поставили ставку, был выкуплен другим тренером.",time(),$auk['user_bet'],1);
					  
                    }
                    if($auk['type'] == 'item') {
                        
                      $buy = '<img src="/img/world/items/little/'.$auk['id_type'].'.png" class="item"> '.Info::getItemBase(['id',$auk['id_type'],'name']).' '.Items::countItem($auk['count'],1);
                      
                      if($auk['split'] == 1) {
                          
                        $stmt = Work::$sql->prepare("UPDATE items_users SET user = ? WHERE id = ?");
                            $stmt->bind_param("ii", $userInfo['id'], $auk['id_bet']);
                            $stmt->execute();
                        
                      }else{
                          
                        itemAdd($auk['id_type'],$auk['count']);
                        
                      }
                      
                    }elseif($auk['type'] == 'egg') {
                        
                      $buy = '<img src="/img/world/items/little/54.png" class="item"> Яйцо покемона';
                      
                      $stmt = Work::$sql->prepare("UPDATE user_egg SET user = ? WHERE id = ?");
                            $stmt->bind_param("ii", $userInfo['id'], $auk['id_bet']);
                            $stmt->execute();
                      
                    }elseif($auk['type'] == 'pokemon') {
                        
                      $buy = '<img src="/img/pokemons/anim/normal/'.$auk['id_type'].'.gif"> #'.Info::getNumPokemonNum($auk['id_type']).' '.Info::getPokemonBase(['id',$auk['id_type'],'name_rus']);

                      $stmt = Work::$sql->prepare("UPDATE user_pokemons SET user_id = ?, active = ? WHERE id = ?");
                            $zero = 0;
                            $stmt->bind_param("iii", $userInfo['id'],$zero, $auk['id_bet']);
                            $stmt->execute();
                      
                    }
					Notify::create_bell_noty("/img/world/notify/auk.png","Ваш <b>Лот #".$auk['id']." - ".$auk["text"]."</b> был выкуплен за <b>".Items::countItem($auk['autobuy'],0)." мон.</b>",time(),$auk['user'],1);
                    $error = 6;
                    
                    $stmt = Work::$sql->prepare("INSERT INTO log_auk (id_lot,type,user,user2,buy,type_id,lot_id,dateend,count) VALUES (?,?,?,?,?,?,?,?,?)"); 
                    $timeX = time();
                        $stmt->bind_param("isiiiiiii", $auk['id'], $auk['type'], $auk['user'], $userInfo['id'], $auk['autobuy'], $auk['id_type'], $auk['id_bet'], $timeX, $auk['count']);
                        $stmt->execute();
                        
                    $q = Work::$sql->prepare("DELETE FROM auk WHERE id = ?"); 
                            $q->bind_param("i", intval($auk['id']));
                            $q->execute();
                  }else{
                    $error = 4;
                  }
                }
              }else{
                $error = 1;
              }
              $this->response['response'] = array(
                'error' => $error,
                'count' => Items::countItem($auk['autobuy'],1),
                'buy' => (isset($buy) ? $buy : 0)
              );
            break;

            case 'bet':
				
			
			
              $stmt =  Work::$sql->prepare('SELECT * FROM auk WHERE id = ?');
                    $stmt->bind_param("i", intval($val[1]));
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $auk = $data->fetch_assoc();
                    
              if(isset($auk) && $userInfo['status'] == 'free' && $auk['close'] != 1) {
                  
                $count = intval($val[2]);
                
                if($count < $auk['min_bet'] && $count <= 0) {
                    
                  $error = 2;
                }else{
                    
                  if(($auk['now_bet'] + $auk['min_bet']) > $count) {
                      
                    $error = 3;
                    
                  }else{
                      
                    if(Info::getLocation(['id',$userInfo['location'],'trade']) == 1) {
                        
                      $error = 13;
                      
                    }else{
                        
                      if($auk['user'] != $userInfo['id']) {
                          
                        if(item_isset(1,$count)) {
                            
                          minus_item(1,$count);
                          
                          if($auk['user_bet'] != 0) {
                              
                            itemAdd(1,$auk['now_bet'],$auk['user_bet']);
							Notify::create_bell_noty("/img/world/notify/auk.webp","Ваша ставка на <b>Лот #".$auk['id']." - ".$auk["text"]."</b> была перебита другим тренером.",time(),$auk['user_bet'],1);
							
                          }
                          
                          $sql1 = "UPDATE auk SET count_bet = count_bet + 1, now_bet = ?, user_bet = ? WHERE id = ?";
                            $stmt1 = Work::$sql->prepare($sql1);
                            $stmt1->bind_param("iii", intval($count), intval($userInfo['id']), intval($auk['id']));
                            $stmt1->execute();
                            $stmt1->close();
                            
                          if($auk['time_end'] <= (time() + 300)) {
                              
                            $aT = $auk['time_end'] + 60;
                            $sql2 = "UPDATE auk SET time_end = ? WHERE id = ?";
                            $stmt2 = Work::$sql->prepare($sql2);
                                $stmt2->bind_param("ii", intval($aT), intval($auk['id']));
                                $stmt2->execute();
                                $stmt2->close();
                                
                          }
                          $error = 5;
                        }else{
                          $error = 4;
                        }
                      }else{
                        $error = 15;
                      }
                    }
                  }
                }
              }else{
                $error = 1;
              }
              $this->response['response'] = array(
                'error' => $error,
                'count' => Items::countItem($count,1)
              );
            break;
          }
        break;

		case 'load':
			$val[1] = Work::$sql->real_escape_string($val[1]);
			$val[2] = intval($val[2]);
			if($val[2] == 0) {
				$sort = 'ORDER BY id ASC';
			}elseif($val[2] == 1){
				$sort = 'ORDER BY id DESC';
			}elseif($val[2] == 2){
				$sort = 'ORDER BY time_end DESC';
			}elseif($val[2] == 3){
				$sort = 'ORDER BY count_bet ASC';
			}elseif($val[2] == 4){
				$sort = 'ORDER BY count_bet DESC';
			}elseif($val[2] == 5){
				$sort = 'AND user = '.$userInfo['id'];
			}elseif($val[2] == 6){
				$sort = 'AND user = 1';
			}
			if($val[1] == '') {
				$Auks = Work::$sql->query('SELECT * FROM auk WHERE close != 1 '.$sort);
			}else{
				$valInt = intval($val[1]);
				if($valInt >= 1) {
					$Auks = Work::$sql->query("SELECT * FROM auk WHERE close != 1 AND id LIKE '%".$valInt."%' OR id_type LIKE '%".$valInt."%' AND type != 'item'".$sort);
				}else{
					$Auks = Work::$sql->query("SELECT * FROM auk WHERE close != 1 AND text LIKE '%".$val[1]."%'".$sort);
				}
			}
			$AukList = [];
			
			while($Auk = $Auks->fetch_assoc()) {
			    
			  if($Auk['split'] == 1) {
			      
				$str = Items::checkStrId($Auk['id_bet']);
				
			  }

                        $AukList[$Auk['id']] = [
			            	'id' => $Auk['id'],
			            	'id_type' => ($Auk['type'] == 'pokemon' ? Info::getNumPokemonNum($Auk['id_type']) : $Auk['id_type']),
			            	'type' => $Auk['type'],
			            	'name' => ($Auk['type'] == 'item' || $Auk['type'] == 'egg' ? ($Auk['id_type'] == 54 ? 'Яйцо #'.Info::getNumPokemonNum(Info::getEgg([$Auk['id_bet'],'basenum'])).' '.Info::getPokemonBase(['id',Info::getEgg([$Auk['id_bet'],'basenum']),'name_rus']).' '.Info::getGenEgg(Info::getEgg([$Auk['id_bet'],'gens'])) : Info::getItemBase(['id',$Auk['id_type'],'name']).' '.($Auk['split'] == 1 ? '['.$str[0].'/'.$str[1].']' : '')).' '.Items::countItem($Auk['count'],1) : '<div class="'.Info::getStringMyPokemon(['id',$Auk['id_bet'],'type']).'-color">#'.Info::getNumPokemonNum($Auk['id_type']).' '.Info::getStringMyPokemon(['id',$Auk['id_bet'],'name_new']).' '.Info::getStringMyPokemon(['id',$Auk['id_bet'],'lvl']).' ур. <i class="'.(Info::getStringMyPokemon(['id',$Auk['id_bet'],'sparka']) == 1 ? 'PokIconSpar ' : '').'fa fa-'.(Info::getStringMyPokemon(['id',$Auk['id_bet'],'gender']) == 'Бесполый' ? 'genderless' : (Info::getStringMyPokemon(['id',$Auk['id_bet'],'gender']) == 'Мальчик' ? 'mars' : 'venus')).'"></i></div>'),
			            	'time_end' => $Auk['time_end'],
			            	'egg_reborn' => ($Auk['type'] == 'egg' ? (Info::getEgg([$Auk['id_bet'],'reborn']) < time() ? 0 : Info::getEgg([$Auk['id_bet'],'reborn'])) : 0),
			            	'time_start' => $Auk['time_start'],
			            	'autobuy' => Items::countItem($Auk['autobuy'],0),
			            	'now_bet' => Items::countItem($Auk['now_bet'],0),
			            	'user' => Info::getMainUser(['id',$Auk['user']]),
			            	'type_color' => ($Auk['type'] == 'pokemon' ? Info::getStringMyPokemon(['id',$Auk['id_bet'],'type']) : 0),
			            	'id_bet' => $Auk['id_bet'],
			            	'my' => ($Auk['user'] == $userInfo['id'] && $Auk['user_bet'] == 0 ? 1 : 0)
			              ];
			}
			
			$this->response['response'] = array(
			  'auk_list' => array_reverse($AukList)
			);
			
		break;

        case 'open':
		  $stmt = Work::$sql->prepare("SELECT id FROM auk WHERE close != 1 AND user = 1");
                $stmt->execute();
                $data = $stmt->get_result();
                $AukAdmin = $data->fetch_assoc();
                
		  $stmtMy = Work::$sql->prepare("SELECT id FROM auk WHERE close != 1 AND user = ?");
            $stmtMy->bind_param("i", $userInfo['id']);
            $stmtMy->execute();
            $dataMy = $stmtMy->get_result();
            $AukMy = $dataMy->fetch_assoc();
            
          $this->response['response'] = array(
            'adminlot' => (isset($AukAdmin) ? 1 : 0),
			'mylot' => (isset($AukMy) ? 1 : 0)
          );
        break;

      }

    }

  }

}

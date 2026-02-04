<?php

Class Inventory {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    $settings = json_decode($userInfo['settings']);

    if($type) {

      $this->response =& $response;
	  $val[3] = (isset($val[3]) ? intval($val[3]) : 0);
	  $val[5] = (isset($val[5]) ? intval($val[5]) : 0);
	  $val[6] = (isset($val[6]) ? intval($val[6]) : 0);
	  $val[7] = (isset($val[7]) ? intval($val[7]) : 0);
	  
	  include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_itemsBlackList.php');

      switch($_POST['type']){

        case 'settings':
          switch($val[0]) {
            case 'open':
              $this->response['response'] = array(
                'sort' => (isset($settings->inventory->sort) ? $settings->inventory->sort : 0),
				'mini' => (isset($settings->inventory->mini) ? $settings->inventory->mini : 0),
				'anim_item' => (isset($settings->inventory->anim_item) ? $settings->inventory->anim_item : 0)
              );
            break;
            case 'redact':
              switch($val[1]){
				  case 'sort':
                    $settings->inventory->sort = ($settings->inventory->sort == 1 ? 0 : 1);
                    $json = json_encode($settings);
                    $stmt = Work::$sql->prepare("UPDATE users SET settings = ? WHERE id = ?");
                        $stmt->bind_param("si", $json, $userInfo['id']);
                        $stmt->execute();
                    $response = array(
                      'error' => 0
                    );
                  break;
				  case 'mini':
				  	if(!isset($settings->inventory->mini)) {
						$settings->inventory->mini = 1;
					}else{
						$settings->inventory->mini = ($settings->inventory->mini == 1 ? 0 : 1);
					}
                    $json = json_encode($settings);
                    $stmt = Work::$sql->prepare("UPDATE users SET settings = ? WHERE id = ?");
                        $stmt->bind_param("si", $json, $userInfo['id']);
                        $stmt->execute();
                    $response = array(
                      'error' => 0
                    );
                  break;
				  case 'anim_item':
				  	if(!isset($settings->inventory->anim_item)) {
						$settings->inventory->anim_item = 1;
					}else{
						$settings->inventory->anim_item = ($settings->inventory->anim_item == 1 ? 0 : 1);
					}
                    $json = json_encode($settings);
                    $stmt = Work::$sql->prepare("UPDATE users SET settings = ? WHERE id = ?");
                        $stmt->bind_param("si", $json, $userInfo['id']);
                        $stmt->execute();
                    $response = array(
                      'error' => 0
                    );
                  break;
              }
            break;
          }
        break;
		
		case 'viewItemsThatCanReturn':
			
			$stmt = Work::$sql->prepare('SELECT id, time, objectID FROM matsuka_rent WHERE type = ?');
                                $stmt->bind_param("i", $userInfo["id"]);
                                $stmt->execute();
                                $objects = $stmt->get_result();
			
			$result = [];
			
			while($object = $objects->fetch_assoc()) {
				
				//user в matsuka_rentItemOnPok скорее не нужен, можно удалить, если не будет конфликтов (то же самое и с matsuka_time_pok_items возможно)
				//up: переименовано в base
				$stmtT = Work::$sql->prepare('SELECT base, pok FROM matsuka_rentItemOnPok WHERE connection = ');
                                $stmtT->bind_param("s", $object["id"]);
                                $stmtT->execute();
                                $dataT = $stmtT->get_result();
                                $isItOnPok = $dataT->fetch_assoc();
				
				$baseInfo = isset($isItOnPok) ? Items::viewItem($isItOnPok["base"]) : Items::baseInfoByIUID($object["objectID"], "noMatter");
				
				if(isset($isItOnPok)) {
					
					$stmtSI = Work::$sql->prepare('SELECT user_id FROM user_pokemons WHERE id = ');
                                $stmtSI->bind_param("i", $isItOnPok["pok"]);
                                $stmtSI->execute();
                                $dataSI = $stmtSI->get_result();
                                $slaveID = $dataSI->fetch_assoc();
					
					if(isset($slaveID) && isset($slaveID["user_id"])) {
						
						$slaveID = $slaveID["user_id"];
						
					}
					
				} else {
					
					$stmtSI = Work::$sql->prepare('SELECT user_id FROM user_pokemons WHERE id = ');
                                $stmtSI->bind_param("i", $object["objectID"]);
                                $stmtSI->execute();
                                $dataSI = $stmtSI->get_result();
                                $slaveID = $dataSI->fetch_assoc();
					
					if(isset($slaveID) && isset($slaveID["user"])) {
						
						$slaveID = $slaveID["user"];
						
					}
					
				}
				
				$result[] = [
				
					"id" => $object["id"],
					"time" => $object["time"],
					"baseInfo" => $baseInfo,
					"slave" => isset($slaveID) ? Info::getMainUser(["id", $slaveID]) : false,
					"isItOnPok" => !!isset($isItOnPok),
				
				];
				
			}
			
			$this->response['response'] = [
			
				"objects" => $result
				
			];
			
		break;
		
		case 'returnItem':
			
			if($userInfo['status'] != 'free') {
				
				$this->response['error'] = "Вы заняты.";
				return;
				
			}
			
			if(Info::getLocation(['id',$userInfo['location'],'pit']) != 1) {
				
				$this->response['error'] = "Поблизости нет питомников.";
				return;
				
			}
		
			$stmt = Work::$sql->prepare('SELECT id, type, time, objectID FROM matsuka_rent WHERE id = ?');
                                $stmt->bind_param("i", $val);
                                $stmt->execute();
                                $data = $stmt->get_result();
                                $isRentItem = $data->fetch_assoc();
			
			if(!$isRentItem) {
				
				$this->response["error"] = "Этот предмет не значится в арендованных!";
				return;
				
			}
			
			if($isRentItem["type"] != $userInfo["id"]) {
				
				$this->response["error"] = "Вы не являетесь хозяином этого предмета!";
				return;
				
			}
			
			if($isRentItem["time"] > time()) {
				
				$this->response['error'] = "Срок аренды ещё не истёк.";
				return;
				
			}
			
			include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/returnRentedItem.php');
			
			$resultRRI = \matsuka\returnRentedItem($isRentItem);
			
			if($resultRRI["error"]) {
				
				$this->response['error'] = $resultRRI["error"];
				return;
				
			}
			
			if($resultRRI["hasBeenBroken"]) {
				
				$this->response['text'] = "Срок предмета истёк, возвращать нечего!";
				
			} else {
				
				$this->response['text'] = "Предмет возвращён!";
				
			}
		
		break;

        case 'action':
		  $val[1] = intval($val[1]);
          switch($val[0]) {
            case 'activate':
			
				$isRentItem = Work::$sql->query('SELECT id FROM matsuka_rent WHERE objectID = '.$val[1].' AND type > 0')->fetch_assoc();
			
              if($userInfo['status'] == 'free' && !isset($isRentItem)) {
				  
                $my = Items::mainItem($val[1]);
				
				if(!in_array($my["item_id"], \matsuka\projectItemsBlackList)) {
				
					if(Items::checkItemUserId($val[1]) && $my['crash'] == 1 && $my['crash_activate'] == 0){
					  $crash = explode(',',Info::getItemBase(['id',$my['item_id'],'crash_time']));
					  $crashTime = time() + mt_rand($crash[0],$crash[1]);
					  
                      $a = 1;
                      
                      $stmt = Work::$sql->prepare('UPDATE items_users SET crash_activate = ?, crash_time = ? WHERE id = ?');
                            $stmt->bind_param("iii", $a, $crashTime, $val[1]);
                            $stmt->execute();
                            
					}
					$error = 0;
					
				} else {
					
					$error = 1;
					
				}
				
              }else{
                $error = 1;
              }
              $this->response['response'] = array(
                'error' => $error
              );
            break;
            case 'dress':
			
				//Арена случайных поков, Собачий Мир
				if($userInfo['status'] == 'free' && $userInfo["location"] != 8009 && $userInfo["location"] != 8000) {
					
					$my = Items::mainItem($val[1]);
					
					if(!in_array($my["item_id"], \matsuka\projectItemsBlackList)) {
						
						if(Items::checkItemUserId($val[1]) && $my['dress'] != 'false') {
							
							$pokemonID = intval($val[5]);
							
							$itemOnPokemon = Info::getStringMyPokemon(['id',$val[5],'item_id']);
								
							if($itemOnPokemon != 0 && $itemOnPokemon != 10003) {
								  
								$itemStrOnPokemon = Info::getStringMyPokemon(['id',$val[5],'item_str']);
								  
								$stmt = Work::$sql->prepare("SELECT time FROM matsuka_time_pok_items WHERE pok = ?");
                                    $stmt->bind_param("i", $pokemonID);
                                    $stmt->execute();
                                    $data = $stmt->get_result();
                                    $isTimeItem = $data->fetch_assoc();
								
								$stmtR = Work::$sql->prepare('SELECT connection FROM matsuka_rentItemOnPok WHERE pok = ');
                                    $stmtR->bind_param("i", $pokemonID);
                                    $stmtR->execute();
                                    $dataR = $stmtR->get_result();
                                    $isRentItem = $dataR->fetch_assoc();
								
								/* die(var_dump([
								
									$isTimeItem,
									$isRentItem,
									$itemStrOnPokemon,
								
								])); */
								
								if(isset($isTimeItem)) {
									
									$q = Work::$sql->prepare("DELETE FROM matsuka_time_pok_items WHERE pok = ?"); 
                                            $q->bind_param("i", $pokemonID);
                                            $q->execute();
									
									if($isTimeItem["time"] > time()) {
										
										$stmtA = Work::$sql->prepare("INSERT INTO items_users (user,item_id,count,str,crash_activate,crash_time,split) VALUES (?,?,?,?,?,?,?) "); 
									    $a = 1;
                                            $stmtA->bind_param("iiisiii", $_SESSION['id'], $itemOnPokemon, $a, $itemStrOnPokemon, $a, $isTimeItem["time"],$a);
                                            $stmtA->execute();

										if(isset($isRentItem)) {
											
											$qq = Work::$sql->prepare("UPDATE matsuka_rent SET objectID = ? WHERE id = ?"); 
											$ll = Work::$sql->insert_id;
                                                $qq->bind_param("is", $ll, $isRentItem["connection"]);
                                                $qq->execute();
                                                
											$qqQ = Work::$sql->prepare("DELETE FROM matsuka_rentItemOnPok WHERE connection = "); 
                                                $qqQ->bind_param("s", $isRentItem["connection"]);
                                                $qqQ->execute();
											
										}
										
									}
									
								} else if($itemStrOnPokemon != '') {
									
									$stmtA = Work::$sql->prepare("INSERT INTO items_users (user,item_id,count,str,split) VALUES (?,?,?,?,?) "); 
									$a = 1;
                                        $stmtA->bind_param("iiisi", $userInfo['id'], $itemOnPokemon, $a, $itemStrOnPokemon,$a);
                                        $stmtA->execute();
									
									if(isset($isRentItem)) {
										
										$qq = Work::$sql->prepare("UPDATE matsuka_rent SET objectID = ? WHERE id = ?"); 
											$ll = Work::$sql->insert_id;
                                                $qq->bind_param("is", $ll, $isRentItem["connection"]);
                                                $qq->execute();
                                                
											$qqQ = Work::$sql->prepare("DELETE FROM matsuka_rentItemOnPok WHERE connection = "); 
                                                $qqQ->bind_param("s", $isRentItem["connection"]);
                                                $qqQ->execute();
										
									}
								  
								} else {
									
									itemAdd($itemOnPokemon, 1);
									
								}
								
							}
							
							$error = 0;
							  
							if(!empty($my['str'])) {
								
								$str = explode(',', $my['str']);
								
								if($str[0] <= 0) {
									
								  $error = 3;
								  
								}
								
							}
							
							if($error != 3) {
							  
								if($my["crash_activate"] == 1) {

									$stmtA = Work::$sql->prepare("INSERT INTO matsuka_time_pok_items (user, pok, time) VALUES (?, ?, ?)"); 
									$a = 1;
                                        $stmtA->bind_param("iii", $_SESSION["id"], $pokemonID, $my["crash_time"]);
                                        $stmtA->execute();

								}
								
								$stmtiRI = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
								$zero = 0;
                                    $stmtiRI->bind_param("si", $val[1], $zero);
                                    $stmtiRI->execute();
                                    $dataiRI = $stmtiRI->get_result();
                                    $isRentItem = $dataiRI->fetch_assoc();
								
								if($isRentItem) {
									
									$stmt = Work::$sql->prepare("
										INSERT INTO matsuka_rentItemOnPok
										(base, pok, connection)
										VALUES
										(?,?,?)
									");
                                        $stmt->bind_param("iii", $my["item_id"], $pokemonID, $isRentItem["id"]);
                                        $stmt->execute();
									
								}

								$stmtX = Work::$sql->prepare("UPDATE user_pokemons SET item_id = ?, item_str = ? WHERE id = ? AND user_id = ? AND active = ?");
								$one = 1;
                                        $stmtX->bind_param("isiii", $my['item_id'], $my['str'], $pokemonID, $userInfo['id'], $one);
                                        $stmtX->execute();
								

								minus_item_id($val[1],1);

								$error = 2;
								
							}
							
						}
							
					} else {
						
						$error = 1;
						
					}
					
				} else {
					
					$error = 1;
					
				}
				
				$this->response['response'] = array(
					'error' => $error
				);
			
            break;
            case 'use':
			
				$stmtiRI = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
								$zero = 0;
                                    $stmtiRI->bind_param("si", $val[1], $zero);
                                    $stmtiRI->execute();
                                    $dataiRI = $stmtiRI->get_result();
                                    $isRentItem = $dataiRI->fetch_assoc();
				
				if(isset($isRentItem)) {
					
					$this->response['response'] = [
					
						'error' => 18,
						'type' => "error",

					];
					
					return;
					
				}
			
              if($userInfo['status'] == 'free') {
                if(Info::getItemBase(['id',$val[1],'use']) != 'false') {
                  if(item_isset($val[1],$val[3]) && !in_array($val[1], \matsuka\projectItemsBlackList)) {
                    switch($val[1]) {
						case 355:
						
							$resp = Items::nextPveIsShine($val[1],$userInfo);
						
						break;
                      case 294:
                        if(Items::checkItemUserId($val[5])) {
                          //$resp = Items::fossil($val[5]);
                        }
                      break;
                      //case 271:
                      //case 272:
                      //case 273:
                        $resp = Items::region_emblem($val[1],$userInfo);
                      break;
					  case 33:
					  	$resp = Items::candy_zagadka($val[1],$val[3]);
					  break;
                      case 280:
                      case 281:
                      case 282:
                      case 283:
                        $resp = Items::exp_bp($val[1],$userInfo);
                      break;
                      case 188:
                      case 270:
                        $resp = Items::svitok($val[1],$userInfo);
                      break;
                      case 11:
                      case 95:
                      //case 171:
                      //case 173:
                      //case 255:
                      //case 256:
                      //case 257:
                      //case 269:
					  //case 314:
                        $resp = Items::chest($val[1],$userInfo["login"]);
                      break;
                      case 13:
                      case 24:
                      case 153:
                        $resp = Items::smile($val[1]);
                      break;
                      case 94:
                        //$resp = Items::premium($userInfo['prem']);
                      break;
                      case 136:
                        $resp = Items::rune($val[1],$userInfo["login"]);
                      break;
                      case 152:
                        $resp = Items::box($val[1]);
                      break;
                      case 146:
                        if(Items::checkItemUserId($val[5])) {
							
							if(item_isset(147, 1)) {
								
								//$resp = Items::crystalBox($val[1], $val[5]);
								
							} else {
								
								$error = 29;
								$type = 'error';
								
							}
							
                        }
                      break;
					  case 133:
					  
						$resp = Items::hallowenChest($val[1]);
					  
					  break;
					  
					  case 337:
					  
						$resp = Items::clad($val[1]);
					  
					  break;
                    }
                    $error = $resp['error'];
                    $type = $resp['type'];
                  }else{
                    $error = 1;
                    $type = 'error';
                  }
                }
              }else{
                $error = 2;
                $type = 'error';
              }
              $this->response['response'] = array(
                'error' => $error,
                'type' => $type,
				'text' => (isset($resp['text']) ? $resp['text'] : 0),
                'item' => (isset($resp['item']) ? $resp['item'] : 0),
                'item2' => (isset($resp['item2']) ? $resp['item2'] : 0),
                'item3' => (isset($resp['item3']) ? $resp['item3'] : 0),
                'item4' => (isset($resp['item4']) ? $resp['item4'] : 0)
              );
			  
			  if(isset($resp["minusItem"])) {
				  
				  $this->response['response']["minusItem"] = $resp["minusItem"];
				  
			  }
			  if(isset($resp["plusItem"])) {
				  
				  $this->response['response']["plusItem"] = $resp["plusItem"];
				  
			  }
            break;
            case 'give':
			
              if($userInfo['status'] == 'free') {
                $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE active = ? AND user_id = ? AND id = ?");
                $a = 1;
                    $stmt->bind_param("iii", $a, $userInfo['id'], $val[5]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pokemon = $data->fetch_assoc();
				
				if($pokemon["realMOwner"] > 0) {
					
					$this->response['response'] = [
					
						'error' => 999,
						'text' => "Вы должны быть полноправным хозяином для совершения этого действия!",
						"type" => "error",
						
					];
					
					return;
					
				}
				
				$stmtR = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
                $a = 0;
                    $stmtR->bind_param("ii", $val[1], $a);
                    $stmtR->execute();
                    $dataR = $stmtR->get_result();
                    $isRentItem = $dataR->fetch_assoc();
				
				if(isset($isRentItem)) {
					
					$this->response['response'] = [
					
						'error' => 999,
						'text' => "Вы должны быть полноправным хозяином для совершения этого действия!",
						"type" => "error",
						
					];
					
					return;
					
				}
				
                if(isset($pokemon) && Info::getItemBase(['id',$val[1],'give']) != 'false') {
                  if(item_isset($val[1],$val[3]) && !in_array($val[1], \matsuka\projectItemsBlackList)) {
                    switch($val[1]) {
                      case 254:
                        $resp = Items::rotom($val[5]);
                      break;
                      case 172:
                        $resp = Items::pillChar($val[5],$val[7]);
                      break;
                      case 170:
                        if(Items::checkItemUserId($val[6])) {
                          $resp = Items::mandarin($val[6],$val[5],$userInfo['ng']);
                        }
                      break;
                      case 197:
                        if(Items::checkItemUserId($val[6])) {
                          $resp = Items::pill_char($val[6],$val[5],$val[7],$val[1]);
                        }
                      break;
					  case 354:
                        $resp = Items::deleteShineColor($val[5]);
                      break;
                      case 196:
                       // $resp = Items::ability_pill2($val[5]);
                      break;
                      case 191:
                        //$resp = Items::ability_pill($val[5]);
                      break;
					  case 127:
                        $resp = Items::goodies($val[5], $userInfo["login"]);
                      break;
					  case 313:
                        $resp = Items::koreneffect($val[5]);
                      break;
                      case 101:
                        $resp = Items::paints($val[5]);
                      break;
                      case 7:
                      case 8:
                      case 9:
                      case 10:
					  
						if($userInfo["location"] == 759) {
					  
							$resp = array(
								'error' => 999,
								'text' => "На этой локации запрещён хил!",
								'type' => 'error'
							);
						
						} else {
							
							$resp = Items::heal($val[1],$val[5],$val[3]);
							
						}
						
                      break;
                      case 160:
                        $resp = Items::coockie($val[5],$val[3]);
                      break;
                      case 151:
                        $resp = Items::cosmoStone($val[5]);
                      break;
                      case 37:
                      case 38:
                      case 39:
                      case 40:
                      case 41:
                      case 42:
                        $resp = Items::banka($val[1],$val[5],$val[3],$userInfo);
                      break;
					  case 358:
                      case 359:
					  
						if($userInfo["location"] == 759) {
					  
							$resp = array(
								'error' => 999,
								'text' => "На этой локации запрещён хил!",
								'type' => 'error'
							);
						
						} else {
					  
							$resp = Items::elixir($val[1],$val[5],$val[3]);
						
						}
						
                      break;
                      case 17:
                      case 32:
					  case 234:
					  case 235:
					  case 236:
					  case 237:
					  case 238:
					  case 239:
					  case 240:
					  case 241:
					  case 242:
					  case 243:
					  case 244:
					  case 245:
					  case 246:
					  case 247:
					  case 248:
					  case 249:
					  case 250:
					  case 251:
                        $resp = Items::lvlCandy($val[1],$val[5],$val[3]);
                      break;
                      case 31:
                        $resp = Items::whiteCandy($val[5]);
                      break;
                      case 30:
                        $resp = Items::blackCandy($val[5]);
                      break;
                      case 134:
                        $resp = Items::everStone($val[5]);
                      break;
                      default:
                        if(Info::getItemBase(['id',$val[1],'type']) == 'ball') {
                          $resp = Items::setBall($val[1],$val[5]);
                        }elseif(Info::getItemBase(['id',$val[1],'type']) == 'evolver') {
                          $resp = Items::setEvol($val[1],$val[5]);
                        }
                        if(($val[1] >= 1001 && $val[1] <= 1100) || $val[1] == 1106) {
                          $resp = Items::setTm($val[1],$val[5],$userInfo);
                        }
                      break;
                    }
                    $error = $resp['error'];
                    $type = $resp['type'];
                  }else{
                    $error = 1;
                    $type = 'error';
                  }
                }
              }else{
                $error = 2;
                $type = 'error';
              }
              $this->response['response'] = array(
                'error' => $error,
                'type' => $type,
                'item' => (isset($resp['item']) ? $resp['item'] : 0),
                'pok' => (isset($val[5]) ? $val[5] : 0),
				'animation' => (isset($settings->inventory->anim_item) ? $settings->inventory->anim_item : 0)
              );
			  if(isset($resp["text"])) {
				  
				  $this->response['response']["text"] = $resp["text"];
				  
			  }
			  if(isset($resp["minusItem"])) {
				  
				  $this->response['response']["minusItem"] = $resp["minusItem"];
				  
			  }
			  if(isset($resp["plusItem"])) {
				  
				  $this->response['response']["plusItem"] = $resp["plusItem"];
				  
			  }
            break;
            case 'drop':
              switch($val[2]) {
                case 'item':
				
					$stmtR = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
                    $a = 0;
                        $stmtR->bind_param("ii", $val[1], $a);
                        $stmtR->execute();
                        $dataR = $stmtR->get_result();
                        $isRentItem = $dataR->fetch_assoc();
				
                  if($userInfo['status'] == 'free' && !isset($isRentItem)) {
                      
                    $stmt = Work::$sql->prepare("SELECT * FROM items_users WHERE user = ? AND id = ?");
                        $stmt->bind_param("ii", $userInfo['id'], $val[1]);
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $my = $data->fetch_assoc();
                    
                    if(isset($my)) {
                        
                      $countVal = intval($val[3]);
                      
                      if(item_isset($my['item_id'],$countVal) && Info::getItemBase(['id',$my['item_id'],'drop']) != 'false' && $countVal > 0) {
                          
                        minus_item_id($val[1],$countVal);
                        $item = Items::arrayItem([$my['item_id'],[$countVal,1]]);
                        $error = 0;
                        
                      }else{
                          
                        $error = 1;
                        
                      }
                      
                    }
                    
                  }else{
                      
                    $error = 2;
                    
                  }
                  
                  $this->response['response'] = array(
                    'error' => $error,
                    'item' => (isset($item) ? $item : 0)
                  );
                break;
                case 'egg':
                  if($userInfo['status'] == 'free') {
                    $stmt = Work::$sql->prepare("SELECT * FROM user_egg WHERE user = ? AND id = ?");
                        $stmt->bind_param("ii", $userInfo['id'], $val[1]);
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $egg = $data->fetch_assoc();
                    if(isset($egg)) {
                        
                      $q = Work::$sql->prepare("DELETE FROM user_egg WHERE id = ?"); 
                            $q->bind_param("i", $val[1]);
                            $q->execute();
                            
                      $error = 0;
                      
                    }
                    
                  }else{
                      
                    $error = 2;
                    
                  }
                  
                  $this->response['response'] = array(
                    'error' => $error
                  );
                break;
              }
            break;
            case 'incubator':
              /*if($userInfo['status'] == 'free') {
                if(item_isset(12,1)) {
                    
                  $stmt = Work::$sql->prepare("SELECT * FROM user_egg WHERE user = ? AND id = ?");
                        $stmt->bind_param("ii", $userInfo['id'], $val[1]);
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $egg = $data->fetch_assoc();
                        
                  if(isset($egg)) {
					  
                    //$reborn = floor(($egg['reborn'] - time())/2);
                    //$newReborn = time() + $reborn;
					
                    achievment_update(8,1);
                    minus_item(12,1);
					
                    //Work::$sql->query('UPDATE user_egg SET reborn = '.$newReborn.' WHERE id = '.$val[1]);
                    //Work::$sql->query('UPDATE user_egg SET reborn = 0 WHERE id = '.$val[1]);
					
					newPokemon($egg['basenum'],$egg['user'],1,$egg['gens'],0,$egg['trade'],$egg['sparka'],false,$egg['shine'],$egg['character'], false, false, $egg['attack']);
		
					$pokInfo = Work::$sql->query("SELECT name_rus, id FROM base_pokemons WHERE id = ".$egg['basenum'])->fetch_assoc();
								
					include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/pokemonAsMiniImage.php');
					
				//	$msg = "Поздравляем Тренера ".$userInfo["login"]." с вылуплением ".matsuka\pokemonAsMiniImage($pokInfo["id"], true)." ".$pokInfo["name_rus"]."!";
					
				//	GameChat::matsukaInfoMessage($msg);
					
					Work::$sql->query('DELETE FROM `user_egg` WHERE `user` = '.$_SESSION['id'].' AND `id` = '.$egg["id"]);
					
					achievment_update(15, 1);
					
                    $error = 0;
					
                  }
                }else{
                  $error = 1;
                }
              }else{
                $error = 2;
              }
              $this->response['response'] = array(
                'error' => $error
              );*/
              
              if($userInfo['status'] == 'free') {
                if(item_isset(12,1)) {
                  $stmt = Work::$sql->prepare("SELECT * FROM user_egg WHERE user = ? AND id = ?");
                        $stmt->bind_param("ii", $userInfo['id'], $val[1]);
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $egg = $data->fetch_assoc();
                  if(isset($egg)) {
                    $reborn = floor(($egg['reborn'] - time())/2);
                    $newReborn = time() + $reborn;
                    achievment_update(8,1);
                    minus_item(12,1);
                    $sql = 'UPDATE user_egg SET reborn = ? WHERE id = ?';
                        $stmt = Work::$sql->prepare($sql);
                        $stmt->bind_param("ii", $newReborn, $val[1]);
                        $stmt->execute();
                        $stmt->close();
                    $error = 0;
                  }
                }else{
                  $error = 1;
                }
              }else{
                $error = 2;
              }
              $this->response['response'] = array(
                'error' => $error
              );
              
            break;
            case 'star':
              if($val[3] == 1) {
                $error = 0;
              }else{
                $error = 1;
              }
              if($val[2] == 'egg') {
                $sql = "UPDATE user_egg SET star = ? WHERE id = ? AND user = ?";
                        $stmt = Work::$sql->prepare($sql);
                        $stmt->bind_param("sii", $val[3], $val[1], $userInfo['id']);
                        $stmt->execute();
              }else{
                  
                $sql = "UPDATE items_users SET star = ? WHERE id = ? AND user = ?";
                        $stmt = Work::$sql->prepare($sql);
                        $stmt->bind_param("sii", $val[3], $val[1], $userInfo['id']);
                        $stmt->execute();
                        
              }
              $this->response['response'] = array(
                'error' => $error
              );
            break;
            case 'checkChest':
              switch($val[3]) {
                case 146:
                  $stmt = Work::$sql->prepare('SELECT * FROM case_loot WHERE item = ?');
                    $stmt->bind_param("i", $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $Chest = $data->fetch_assoc();
				  
				  if(!($Chest["id"] > 0)) {
					  
					  $Chest = Items::generateCrystalPrize($val[1]);
					  
				  }
				  
                  $prize1 = explode(',',$Chest['prize1']);
                  $prize2 = explode(',',$Chest['prize2']);
                  $prize3 = explode(',',$Chest['prize3']);
                  $prize4 = explode(',',$Chest['prize4']);
                  $prize5 = explode(',',$Chest['prize5']);
                  $Loot1 = [
                    'id' => ($prize1[0] == 'egg' ? 54 : $prize1[1]),
                    'count' => Items::countItem($prize1[2],1),
                    'name' => ($prize1[0] == 'egg' ? '#'.Info::getNumPokemonNum($prize1[1]).' '.Info::getPokemonBase(['id',$prize1[1],'name_rus']) : Info::getItemBase(['id',$prize1[1],'name']))
                  ];
                  $Loot2 = [
                    'id' => ($prize2[0] == 'egg' ? 54 : $prize2[1]),
                    'count' => Items::countItem($prize2[2],1),
                    'name' => ($prize2[0] == 'egg' ? '#'.Info::getNumPokemonNum($prize2[1]).' '.Info::getPokemonBase(['id',$prize2[1],'name_rus']) : Info::getItemBase(['id',$prize2[1],'name']))
                  ];
                  $Loot3 = [
                    'id' => ($prize3[0] == 'egg' ? 54 : $prize3[1]),
                    'count' => Items::countItem($prize3[2],1),
                    'name' => ($prize3[0] == 'egg' ? '#'.Info::getNumPokemonNum($prize3[1]).' '.Info::getPokemonBase(['id',$prize3[1],'name_rus']) : Info::getItemBase(['id',$prize3[1],'name']))
                  ];
                  $Loot4 = [
                    'id' => ($prize4[0] == 'egg' ? 54 : $prize4[1]),
                    'count' => Items::countItem($prize4[2],1),
                    'name' => ($prize4[0] == 'egg' ? '#'.Info::getNumPokemonNum($prize4[1]).' '.Info::getPokemonBase(['id',$prize4[1],'name_rus']) : Info::getItemBase(['id',$prize4[1],'name']))
                  ];
                  $Loot5 = [
                    'id' => ($prize5[0] == 'egg' ? 54 : $prize5[1]),
                    'count' => Items::countItem($prize5[2],1),
                    'name' => ($prize5[0] == 'egg' ? '#'.Info::getNumPokemonNum($prize5[1]).' '.Info::getPokemonBase(['id',$prize5[1],'name_rus']) : Info::getItemBase(['id',$prize5[1],'name']))
                  ];
                  $this->response['response'] = array(
                    'id' => $Chest['item'],
                    'prize1' => $Loot1,
                    'prize2' => $Loot2,
                    'prize3' => $Loot3,
                    'prize4' => $Loot4,
                    'prize5' => $Loot5
                  );
                break;
              }
            break;
          }
        break;

        case 'view':
		//itemAdd(44, 1);
          $stmt = Work::$sql->prepare('SELECT * FROM items_users WHERE user = ?');
                    $stmt->bind_param("i", $userInfo['id']);
                    $stmt->execute();
                    $items = $stmt->get_result();
                    
          $stmtegg = Work::$sql->prepare('SELECT * FROM user_egg WHERE user = ?');
                    $stmtegg->bind_param("i", $userInfo['id']);
                    $stmtegg->execute();
                    $eggs = $stmtegg->get_result();
                    
          $itemsList = [];
          $eggList = [];
          while($item = $items->fetch_assoc()) {
			  
			  if($item['crash_activate'] == 1 && time() > $item["crash_time"]) {
				  
				  $q = Work::$sql->prepare("DELETE FROM `items_users` WHERE `id` = ?"); 
                            $q->bind_param("i", $item["id"]);
                            $q->execute();
				  
				  continue;
				  
			  }
			  
            $stmtBI = Work::$sql->prepare("SELECT type,name FROM base_items WHERE id = ?");
                $stmtBI->bind_param("i", $item['item_id']);
                $stmtBI->execute();
                $dataBI = $stmtBI->get_result();
                $baseItem = $dataBI->fetch_assoc();
                
            if($item['str'] == NULL) {
                
              $str = 0;
              
            }else{
                
              $strExp = explode(',',$item['str']);
              $str =  1;
              
            }
            
            if($item['item_id'] >= 1001 && $item['item_id'] <= 1099) {
                
              $tmView = 1;
              
            }else{
                
              $tmView = 0;
              
            }
            
            $itemsList[$item['id']] = [
              'id' => $item['id'],
              'id_item' => $item['item_id'],
              'count' => number_format($item['count'], 0, '.', '.'),
              'str' => ($str == 0 ? 0 : $strExp[0].'/'.$strExp[1]),
              'crashprocent' => Items::crashProcent($item['id']),
              'type' => $baseItem['type'],
              'star' => $item['star'],
              'tm' => ($tmView == 1 ? substr($baseItem['name'], 0, 5) : 0)
            ];
          }
          while($egg = $eggs->fetch_assoc()) {
            $eggList[$egg['id']] = [
              'id' => $egg['id'],
              'star' => $egg['star'],
              'pok' => Info::getNumPokemonNum($egg['basenum'])
            ];
          }
          $this->response['response'] = array(
			'mini' => (isset($settings->inventory->mini) ? $settings->inventory->mini : 0),
            'items_list' => ($settings->inventory->sort == 0 ? $itemsList : array_reverse($itemsList)),
            'egg_list' => $eggList
          );
        break;
		
		case 'extendTime':
		
			if(count($val) >= 2) {
		
				$error = 0;
				
				$stmt = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
				    $zero = 0;
                    $stmt->bind_param("i", $val[0], $zero);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $isRentItemOrig = $data->fetch_assoc();

				$stmtT = Work::$sql->prepare('SELECT id FROM matsuka_rent WHERE objectID = ? AND type > ?');
				    $zero = 0;
                    $stmtT->bind_param("i", $val[1], $zero);
                    $stmtT->execute();
                    $dataT = $stmtT->get_result();
                    $isRentItem2 = $dataT->fetch_assoc();
				
				if(isset($isRentItemOrig) || isset($isRentItem2)) {
					
					$this->response['response'] = array(
					
						"error" => "Вы должны быть полноправным хозяином для совершения этого действия!",
						
					);
					
					return;
					
				}
			
				$stmtO = Work::$sql->prepare("SELECT crash_time, item_id FROM items_users WHERE user = ? AND id = ? AND crash_activate = ?");
				    $one = 1;
                    $stmtO->bind_param("iii", $_SESSION["id"], $val[0], $one);
                    $stmtO->execute();
                    $dataO = $stmtO->get_result();
                    $itemOrig = $dataO->fetch_assoc();
				
				$stmtI = Work::$sql->prepare("SELECT crash_time, item_id FROM items_users WHERE user = ? AND id = ? AND crash_activate = ?");
                    $stmtI->bind_param("iii", $_SESSION["id"], $val[1], $one);
                    $stmtI->execute();
                    $dataI = $stmtI->get_result();
                    $item = $dataI->fetch_assoc();
				
				$time = time();
				
				if(isset($itemOrig) && isset($item) && $item["crash_time"] > $time) {
					
					if($itemOrig["item_id"] != $item["item_id"]) {
						
						$error = "Возможно только с одинаковыми предметами!";
						
					} else {
						
						$timeUp = $itemOrig["crash_time"] + ($item["crash_time"] - $time);
						
						//include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/timer.php');
						//$error = \matsuka\matsukaTimer($timeUp, ": ", "несколько минут");
						
						$stmtI = Work::$sql->prepare("UPDATE items_users SET crash_time = ? WHERE id = ?");
                            $stmtI->bind_param("ii", $timeUp, $val[0]);
                            $stmtI->execute();
                            
						$q = Work::$sql->prepare("DELETE FROM items_users WHERE id = ?"); 
                            $q->bind_param("i", $val[1]);
                            $q->execute();
						
					}
					
				} else {
					
					$error = "Время существования предмета истекло!";
					
				}
				
				$this->response['response'] = array(
				
					"error" => $error,
					
				);
				
			}
		
		break;
		
		case 'searchItem':
			
			$stmt = Work::$sql->prepare("
			
				SELECT * FROM items_users
				JOIN (select id as biID, name from base_items) bi
				ON bi.biID = items_users.item_id
				WHERE user = ? AND bi.name LIKE ?
				GROUP BY items_users.id
				
			");
			$l = "%$val[0]%";
                $stmt->bind_param("is", $userInfo['id'], $l);
                $stmt->execute();
                $items = $stmt->get_result();
			
			$stmtEgg = Work::$sql->prepare("
			
				SELECT * FROM user_egg
				JOIN (select id as bpID, name_rus from base_pokemons) bp
				ON bp.bpID = user_egg.basenum
				WHERE user = ? AND bp.name_rus LIKE ?
				
			");
			$lE = "%$val[0]%";
                $stmtEgg->bind_param("is", $userInfo['id'], $lE);
                $stmtEgg->execute();
                $eggs = $stmtEgg->get_result();
			
			$itemsList = [];
			$eggList = [];
			
          while($item = $items->fetch_assoc()) {
			  
            $stmtbI = Work::$sql->prepare("SELECT type,name FROM base_items WHERE id = ?");
                $stmtbI->bind_param("i", $item['item_id']);
                $stmtbI->execute();
                $databI = $stmtbI->get_result();
                $baseItem = $databI->fetch_assoc();
			
            if($item['str'] == NULL || $item["str"] == 0) {
                
              $str = 0;
              
            }else{
                
              $strExp = explode(',',$item['str']);
              $str =  1;
              
            }
            
            if($item['item_id'] >= 1001 && $item['item_id'] <= 1099) {
                
              $tmView = 1;
              
            }else{
                
              $tmView = 0;
              
            }
            
            $itemsList[$item['id']] = [
              'id' => $item['id'],
              'id_item' => $item['item_id'],
              'count' => number_format($item['count'], 0, '.', '.'),
              'str' => ($str == 0 ? 0 : $strExp[0].'/'.$strExp[1]),
              'crashprocent' => Items::crashProcent($item['id']),
              'type' => $baseItem['type'],
              'star' => $item['star'],
              'tm' => ($tmView == 1 ? substr($baseItem['name'], 0, 5) : 0)
            ];
          }
          while($egg = $eggs->fetch_assoc()) {
            $eggList[$egg['id']] = [
              'id' => $egg['id'],
              'star' => $egg['star'],
              'pok' => Info::getNumPokemonNum($egg['basenum'])
            ];
          }
          $this->response['response'] = array(
			'mini' => (isset($settings->inventory->mini) ? $settings->inventory->mini : 0),
            'items_list' => ($settings->inventory->sort == 0 ? $itemsList : array_reverse($itemsList)),
            'egg_list' => $eggList
          );
		
		break;

        case 'open':
          //
        break;
      }

    }

  }

}

<?php

Class WeekEvents {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;
      $this->userInfo =& $userInfo;

      switch($_POST['type']){

       /*  case 'openVirus':
          $virus = Work::$sql->query("SELECT * FROM plague");
          $PokList = [];
          $virus_count = 0;
          while($v = $virus->fetch_assoc()) {
            $virus_count = $virus_count + $v['count'];
            $PokList[$v['id']] = [
              'num' => $v['pok'],
              'name' => '#'.Info::getNumPokemonNum($v['pok']).' '.Info::getBasePokemon(['id',$v['pok'],'name_rus']),
              'count' => $v['count']
            ];
          }
          $virus_prize = 0;
          if($userInfo['virus_proc'] == 0) {
            if($userInfo['virus'] >= 1) {
              $virus_prize = 1;
              $virusPrizeList = [
                [152,1,'Коробка с витаминами',1],
                [192,1,'Акваланг',2]
              ];
            }
          }elseif($userInfo['virus_proc'] == 1) {
            if($userInfo['virus'] >= 30) {
              $virus_prize = 1;
              $virusPrizeList = [
                [98,1,'Глубоководный зуб',3],
                [82,1,'Гнутая ложка',4]
              ];
            }
          }elseif($userInfo['virus_proc'] == 2) {
            if($userInfo['virus'] >= 70) {
              $virus_prize = 1;
              $virusPrizeList = [
                [3,1,'#427 Банири',5],
                [3,1,'#418 Буизель',6]
              ];
            }
          }elseif($userInfo['virus_proc'] == 3) {
            if($userInfo['virus'] >= 100) {
              $virus_prize = 1;
              $virusPrizeList = [
                [99,1,'Глубоководная чешуя',7],
                [19,1,'Сумрачный камень',8]
              ];
            }
          }elseif($userInfo['virus_proc'] == 4) {
            if($userInfo['virus'] >= 120) {
              $virus_prize = 1;
              $virusPrizeList = [
                [147,1,'Хрустальный ключ',9],
                [127,3,'Набор вкусностей x3',10]
              ];
            }
          }elseif($userInfo['virus_proc'] == 5) {
            if($userInfo['virus'] >= 160) {
              $virus_prize = 1;
              $virusPrizeList = [
                [20,1,'Солнечный камень',11],
                [35,1,'Лунный камень',12]
              ];
            }
          }elseif($userInfo['virus_proc'] == 6) {
            if($userInfo['virus'] >= 160) {
              $virus_prize = 1;
              $virusPrizeList = [
                [1042,1,'TM 42 - Мужество',13],
                [1005,1,'TM 05 - Рык',14]
              ];
            }
          }elseif($userInfo['virus_proc'] == 7) {
            if($userInfo['virus'] >= 300) {
              $virus_prize = 1;
              $virusPrizeList = [
                [191,5,'Пилюля скрытой способности x5',15],
                [3,1,'#506 Лилипап',16]
              ];
            }
          }elseif($userInfo['virus_proc'] == 8) {
            if($userInfo['virus'] >= 450) {
              $virus_prize = 1;
              $virusPrizeList = [
                [73,1,'Потертый диск',17],
                [22,1,'Ткань жнеца',18]
              ];
            }
          }elseif($userInfo['virus_proc'] == 9) {
            if($userInfo['virus'] >= 700) {
              $virus_prize = 1;
              $virusPrizeList = [
                [3,1,'#328 Трапинч',19],
                [3,1,'#736 Граббин',20],
                [3,1,'#605 Элгием',21]
              ];
            }
          }elseif($userInfo['virus_proc'] == 10) {
            if($userInfo['virus'] >= 1300) {
              $virus_prize = 1;
              $virusPrizeList = [
                [51,1,'Магмарайзер',22],
                [53,1,'Электрайзер',23],
                [74,1,'Усовершенствованный диск',24]
              ];
            }
          }elseif($userInfo['virus_proc'] == 11) {
            if($userInfo['virus'] >= 2200) {
              $virus_prize = 1;
              $virusPrizeList = [
                [3,1,'#767 Вимпод',25],
                [3,1,'#607 Литвик',26],
                [3,1,'#701 Холуча',27]
              ];
            }
          }elseif($userInfo['virus_proc'] == 12) {
            if($userInfo['virus'] >= 4500) {
              $virus_prize = 1;
              $virusPrizeList = [
                [3,1,'#443 Гибл',28],
                [3,1,'#782 Джангмо-о',29]
              ];
            }
          }
          $this->response['response'] = array(
            'prize_list' => ($virus_prize == 1 ? $virusPrizeList : 0),
            'prizes_vision' => $virus_prize,
            'pok_list' => $PokList,
            'count' => $virus_count,
            'news' => 'Спасибо огромное тренерам, которые помогали нам лечить покемонов от вируса. Все дикие покемоны были вылечены, инфекция уничтожена. Тренеры, которые принимали участие в этом мероприятии, получат от нас награды.<br><br><b>ВАМИ ВЫЛЕЧЕНО ПОКЕМОНОВ: '.$userInfo['virus'].' шт.</b>'
          );
        break;

        case 'getVirus':
          if($userInfo['id'] != 2) {
            switch($val) {
              case 1:
                if($userInfo['virus'] >= 1 && $userInfo['virus_proc'] == 0) {
                  itemAdd(152,1);
                  $plus = Items::arrayItem([152,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 1 WHERE id = ".$userInfo['id']);
                }
              break;
              case 2:
                if($userInfo['virus'] >= 1 && $userInfo['virus_proc'] == 0) {
                  itemAdd(192,1);
                  $plus = Items::arrayItem([192,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 1 WHERE id = ".$userInfo['id']);
                }
              break;
              case 3:
                if($userInfo['virus'] >= 30 && $userInfo['virus_proc'] == 1) {
                  itemAdd(98,1);
                  $plus = Items::arrayItem([98,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 2 WHERE id = ".$userInfo['id']);
                }
              break;
              case 4:
                if($userInfo['virus'] >= 30 && $userInfo['virus_proc'] == 1) {
                  itemAdd(82,1);
                  $plus = Items::arrayItem([82,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 2 WHERE id = ".$userInfo['id']);
                }
              break;
              case 5:
                if($userInfo['virus'] >= 70 && $userInfo['virus_proc'] == 2) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(427,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 3 WHERE id = ".$userInfo['id']);
                }
              break;
              case 6:
                if($userInfo['virus'] >= 70 && $userInfo['virus_proc'] == 2) {
                  newPokemon(418,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  $plus = Items::arrayItem([3,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 3 WHERE id = ".$userInfo['id']);
                }
              break;
              case 7:
                if($userInfo['virus'] >= 100 && $userInfo['virus_proc'] == 3) {
                  itemAdd(99,1);
                  $plus = Items::arrayItem([99,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 4 WHERE id = ".$userInfo['id']);
                }
              break;
              case 8:
                if($userInfo['virus'] >= 100 && $userInfo['virus_proc'] == 3) {
                  itemAdd(19,1);
                  $plus = Items::arrayItem([19,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 4 WHERE id = ".$userInfo['id']);
                }
              break;
              case 9:
                if($userInfo['virus'] >= 120 && $userInfo['virus_proc'] == 4) {
                  itemAdd(147,1);
                  $plus = Items::arrayItem([147,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 5 WHERE id = ".$userInfo['id']);
                }
              break;
              case 10:
                if($userInfo['virus'] >= 120 && $userInfo['virus_proc'] == 4) {
                  itemAdd(127,3);
                  $plus = Items::arrayItem([127,[3,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 5 WHERE id = ".$userInfo['id']);
                }
              break;
              case 11:
                if($userInfo['virus'] >= 160 && $userInfo['virus_proc'] == 5) {
                  itemAdd(20,1);
                  $plus = Items::arrayItem([20,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 6 WHERE id = ".$userInfo['id']);
                }
              break;
              case 12:
                if($userInfo['virus'] >= 160 && $userInfo['virus_proc'] == 5) {
                  itemAdd(35,1);
                  $plus = Items::arrayItem([35,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 6 WHERE id = ".$userInfo['id']);
                }
              break;
              case 13:
                if($userInfo['virus'] >= 160 && $userInfo['virus_proc'] == 6) {
                  itemAdd(1042,1);
                  $plus = Items::arrayItem([1042,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 7 WHERE id = ".$userInfo['id']);
                }
              break;
              case 14:
                if($userInfo['virus'] >= 160 && $userInfo['virus_proc'] == 6) {
                  itemAdd(1005,1);
                  $plus = Items::arrayItem([1005,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 7 WHERE id = ".$userInfo['id']);
                }
              break;
              case 15:
                if($userInfo['virus'] >= 300 && $userInfo['virus_proc'] == 7) {
                  itemAdd(191,5);
                  $plus = Items::arrayItem([191,[5,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 8 WHERE id = ".$userInfo['id']);
                }
              break;
              case 16:
                if($userInfo['virus'] >= 300 && $userInfo['virus_proc'] == 7) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(506,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 8 WHERE id = ".$userInfo['id']);
                }
              break;
              case 17:
                if($userInfo['virus'] >= 450 && $userInfo['virus_proc'] == 8) {
                  itemAdd(73,1);
                  $plus = Items::arrayItem([73,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 9 WHERE id = ".$userInfo['id']);
                }
              break;
              case 18:
                if($userInfo['virus'] >= 450 && $userInfo['virus_proc'] == 8) {
                  itemAdd(22,1);
                  $plus = Items::arrayItem([22,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 9 WHERE id = ".$userInfo['id']);
                }
              break;
              case 19:
                if($userInfo['virus'] >= 700 && $userInfo['virus_proc'] == 9) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(328,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 10 WHERE id = ".$userInfo['id']);
                }
              break;
              case 20:
                if($userInfo['virus'] >= 700 && $userInfo['virus_proc'] == 9) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(736,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 10 WHERE id = ".$userInfo['id']);
                }
              break;
              case 21:
                if($userInfo['virus'] >= 700 && $userInfo['virus_proc'] == 9) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(605,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 10 WHERE id = ".$userInfo['id']);
                }
              break;
              case 22:
                if($userInfo['virus'] >= 1300 && $userInfo['virus_proc'] == 10) {
                  itemAdd(51,1);
                  $plus = Items::arrayItem([51,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 11 WHERE id = ".$userInfo['id']);
                }
              break;
              case 23:
                if($userInfo['virus'] >= 1300 && $userInfo['virus_proc'] == 10) {
                  itemAdd(53,1);
                  $plus = Items::arrayItem([53,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 11 WHERE id = ".$userInfo['id']);
                }
              break;
              case 24:
                if($userInfo['virus'] >= 1300 && $userInfo['virus_proc'] == 10) {
                  itemAdd(74,1);
                  $plus = Items::arrayItem([74,[1,1]]);
                  Work::$sql->query("UPDATE users SET virus_proc = 11 WHERE id = ".$userInfo['id']);
                }
              break;
              case 25:
                if($userInfo['virus'] >= 2200 && $userInfo['virus_proc'] == 11) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(767,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 12 WHERE id = ".$userInfo['id']);
                }
              break;
              case 26:
                if($userInfo['virus'] >= 2200 && $userInfo['virus_proc'] == 11) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(607,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 12 WHERE id = ".$userInfo['id']);
                }
              break;
              case 27:
                if($userInfo['virus'] >= 2200 && $userInfo['virus_proc'] == 11) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(701,$userInfo['id'],1,'29,29,29,29,29,29',0,'true',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 12 WHERE id = ".$userInfo['id']);
                }
              break;
              case 28:
                if($userInfo['virus'] >= 4500 && $userInfo['virus_proc'] == 12) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(443,$userInfo['id'],1,'27,27,27,27,27,27',0,'false',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 13 WHERE id = ".$userInfo['id']);
                }
              break;
              case 29:
                if($userInfo['virus'] >= 4500 && $userInfo['virus_proc'] == 12) {
                  $plus = Items::arrayItem([3,[1,1]]);
                  newPokemon(782,$userInfo['id'],1,'27,27,27,27,27,27',0,'false',1,false,false,false,false,true);
                  Work::$sql->query("UPDATE users SET virus_proc = 13 WHERE id = ".$userInfo['id']);
                }
              break;
            }
            $this->response['response'] = array(
              'plus' => (isset($plus) ? $plus : 0)
            );
          }
        break; */

        case 'get':
		  
			$events = WeekEvents::getWeekEvents();

			$id = $val[2];
			
			if(!WeekEvents::isEventStarted($events, $id)) {
				
				$error = 1;
				
				$this->response['response'] = array(
					'error' => $error,
					'plus' => 0
				);
				
				Work::_setStrongInfo($response);
				Work::_viewOut();
				die();
				
			}
		  
          $event = WeekEvents::getMainWeekEvent($id);
          $eventMy = WeekEvents::getWeekEventMy($id, $userInfo['id']);
          switch($val[0]) {
            case 'trade':
              $prize = WeekEvents::getPrize($id);
              switch($id) {
                case 2:
                  $item = 311;
                break;
				case 3:
                  $item = 199;
                break;
              }
              if(item_isset($item,$prize[$val[1]][0])) {
				  
                $error = 2;
				
                if($prize[$val[1]][1] == 'item') {
					
					itemAdd($prize[$val[1]][2],$prize[$val[1]][3]);
					$plus = [Items::arrayItem([$prize[$val[1]][2],[$prize[$val[1]][3],1]])];
				  
                } else {
					
					include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/newPokemon.php');

					\matsuka\mNewPokemon($prize[$val[1]][2], 1, 'true', 0, false, false, false, \matsuka\mRepeatIV(25));
					
					$plus = [Pokemons::arrayPokemon([Info::getBasePokemon(['id',$prize[$val[1]][2],'name_rus']),1,Info::getNumPokemonNum($prize[$val[1]][2])])];
				  
                }
				
                $minus = [Items::arrayItem([$item,[$prize[$val[1]][0],1]])];
                minus_item($item,$prize[$val[1]][0]);
                
              }else{
                  
                $error = 1;
                
              }
              
              $this->response['response'] = array(
                'error' => $error,
                'plus' => (isset($plus) ? $plus : 0),
                'minus' => (isset($minus) ? $minus : 0)
              );
              
            break;

            case 'score':
              $prize = WeekEvents::getPrize($id);
              
              if($eventMy['prize'][$val[1]] == 0) {
                  
                if($prize[$val[1]][0] <= $eventMy['score']) {
                    
                  $error = 3; // Получаем награду
                  
                  if($prize[$val[1]][1] == 'item') {
                      
                    itemAdd($prize[$val[1]][2],$prize[$val[1]][3]);
                    $plus = [Items::arrayItem([$prize[$val[1]][2],[$prize[$val[1]][3],1]])];
                    
                  }else{
                      
                    newPokemon($prize[$val[1]][2],$userInfo['id'],1,false,0,'false',1,false,false,false,false,true);
                    $plus = [Pokemons::arrayPokemon([Info::getBasePokemon(['id',$prize[$val[1]][2],'name_rus']),1,Info::getNumPokemonNum($prize[$val[1]][2])])];
                    
                  }
                  
                  WeekEvents::updatePrizeScore($id, $val[1]);
                  
                }else{
                    
                  $error = 2; // Недостаточно очков
                  
                }
                
              }else{
                  
                $error = 1; // Уже получен
                
              }
              
              $this->response['response'] = array(
                'error' => $error,
                'plus' => (isset($plus) ? $plus : 0)
              );
              
            break;
          }
        break;

        case 'getPrize':
          
			$events = WeekEvents::getWeekEvents();

			$id = $val;
			
			if(!WeekEvents::isEventStarted($events, $id)) {
				
				$error = 1;
				
				$this->response['response'] = array(
					'error' => $error,
					'plus' => 0
				);
				
				Work::_setStrongInfo($response);
				Work::_viewOut();
				die();
				
			}

          $prize = WeekEvents::checkPrize($id);
          $eventMy = WeekEvents::getWeekEventMy($id, $userInfo['id']);
          $mainEvent = WeekEvents::getMainWeekEvent($id);
          switch($id) {
            case 6:
              if($eventMy['score'] >= 24) {
                $error = 2;
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_random.php');
				
				$newPrize = matsuka\weekEvent6Random($prize, $mainEvent, $eventMy)["objects"];
				
                shuffle($newPrize);
				
                if($newPrize[0][0] == 'item') {
					
                  $plus = [Items::arrayItem([$newPrize[0][1],[$newPrize[0][2],1]])];
				  
                  itemAdd($newPrize[0][1],$newPrize[0][2]);
				  
                } else {
					
                  $plus = [Pokemons::arrayPokemon([Info::getBasePokemon(['id',$newPrize[0][1],'name_rus']),1,Info::getNumPokemonNum($newPrize[0][1])])];

				  \matsuka\mNewPokemon($newPrize[0][1], 1, 'true', 0, false, false, false, \matsuka\mRepeatIV(25));
				  
                }
				
                WeekEvents::nullScore($id);
                
              }else{
                  
                $error = 1;
                
              }
              
            break;
          }
          $this->response['response'] = array(
            'error' => $error,
            'plus' => (isset($plus) ? $plus : 0)
          );
        break;

        case 'activate':
          
			$events = WeekEvents::getWeekEvents();

			$id = $val[1];
			
			if(!WeekEvents::isEventStarted($events, $id)) {
				
				$error = 1;
				
				$this->response['response'] = array(
					'error' => $error,
					'minus' => 0
				);
				
				Work::_setStrongInfo($response);
				Work::_viewOut();
				die();
				
			}
		  
          $eventMy = WeekEvents::getWeekEventMy($id, $userInfo['id']);
          
          $mainEvent = WeekEvents::getMainWeekEvent($id);
          
          switch($id) {
            case 6:
              switch($val[0]) {
                case 228:
                  if(item_isset(228,1)) {
                      
                    if($mainEvent['score_count'] == $eventMy['score']) {
                        
                      $error = 2;
                      
                    }else{
                        
                      $minus = [Items::arrayItem([228,[1,1]])];
                      minus_item(228,1);
                      $error = 3;
                      WeekEvents::updateScore($id,1,$eventMy['score'],$userInfo['id']);
                      
                    }
                    
                  }else{
                      
                    $error = 1;
                    
                  }
                  
                break;
              }
            break;
          }
          $this->response['response'] = array(
            'error' => $error,
            'minus' => (isset($minus) ? $minus : 0)
          );
        break;

        case 'buy':
          
			$events = WeekEvents::getWeekEvents();

			$id = $val[1];
			
			if(!WeekEvents::isEventStarted($events, $id)) {
				
				$error = 1;
				
				$this->response['response'] = array(
					'error' => $error,
					'plus' => 0,
					'minus' => 0,
				);
				
				Work::_setStrongInfo($response);
				Work::_viewOut();
				die();
				
			}
		  
          $buy = WeekEvents::getBuyThings($id);
          $eventMy = WeekEvents::getWeekEventMy($id, $userInfo['id']);
          
          if($buy != 0) {
              
            $thing = $buy[$val[0]];
            
            if($eventMy['prize'][$val[0]] != 0) {
                
              if(item_isset(43,$thing[3])) {
                  
                $error = 3;
                minus_item(43,$thing[3]);
                itemAdd($thing[1],$thing[2]);
                $plus = [Items::arrayItem([$thing[1],[$thing[2],1]])];
                $minus = [Items::arrayItem([43,[$thing[3],1]])];
                $left = $eventMy['prize'][$val[0]] - 1;
                WeekEvents::updateBuy($id, $val[0],$left);
                
              }else{
                  
                $error = 2;
                
              }
              
            }else{
                
              $error = 1;
            }
            
          }
          
          $this->response['response'] = array(
            'error' => $error,
            'plus' => (isset($plus) ? $plus : 0),
            'minus' => (isset($minus) ? $minus : 0)
          );
          
        break;
		
		case 'open':
		
			$events = WeekEvents::getWeekEvents();
			
			$eventNames = [];
			
			for($i = 0; $i < count($events); ++$i) {
				
				$eventNames[] = WeekEvents::getMainWeekEvent($events[$i]);
				
			}
			
			$featureEvents = WeekEvents::getFeatureWeekEvents();
			
			$featureEventNames = [];
			
			for($i = 0; $i < count($featureEvents); ++$i) {
				
				$featureEventNames[] = WeekEvents::getMainWeekEvent($featureEvents[$i]);
				
			}
			
			$this->response["events"] = $eventNames;
			$this->response["feature_events"] = $featureEventNames;
		
		break;

		case 'openCurrent':
		
			$events = WeekEvents::getWeekEvents();
			
			//$id = intval($val);
			$id = $val; //Не надо
			
			if(!WeekEvents::isEventStarted($events, $id)) {
				
				$response["error"] = "Ивент ещё не начался!";
				
				Work::_setStrongInfo($response);
				Work::_viewOut();
				die();
				
			}
		  
			$prize = WeekEvents::getPrize($id);
			$prizeNew = [];
			
			$eventMy = WeekEvents::getWeekEventMy($id, $userInfo['id']);
			$mainEvent = WeekEvents::getMainWeekEvent($id);
			
			$buy = WeekEvents::getBuyThings($id);
			$loot = WeekEvents::checkPrize($id);
			
			if($buy != 0) {
				
				//Для 4, 5 и 6 ивентов
				
				$buyNew = [];
				
				foreach($buy as $key => $val) {
					
					if($val[0] == 'item') {
						
						$buyPar = [
							'item',
							$val[3],
							Items::arrayItem([$val[1],[$val[2],1]]),
							$eventMy['prize'][$key],
							$key,
						];
						
					}
					
					array_push($buyNew, $buyPar);
					
				}
				
			}
			
			if($mainEvent['score'] == 1 && $prize != 0) {
				
				//Для 1, 4, 5 событий
				
				foreach($prize as $key => $val) {
					
					if($val[1] == 'item' || $val[1] == 'egg') {
						
						$val[2] = Items::arrayItem([$val[2],[$val[3],1]]);
						
					} else {
						
						$val[2] = [
							'name' => '#'.Info::getNumPokemonNum($val[2]).' '.Info::getBasePokemon(['id',$val[2],'name_rus']),
							'lvl' => $val[3],
							'id' => $val[2]
						];
						
					}
					
					$val[4] = $key;
					
					$val[3] = $eventMy['prize'][$key];
					
					array_push($prizeNew, $val);
					
				}
				
			} else {
				
				if($mainEvent['type'] == 1) {
					
					//Для 2 и 3 события
					
					foreach($prize as $key => $val) {
						
						if($val[1] == 'item' || $val[1] == 'egg') {
							
							$val[2] = Items::arrayItem([$val[2],[$val[3],1]]);
							
						} else {
							
							$val[2] = [
								'name' => '#'.Info::getNumPokemonNum($val[2]).' '.Info::getBasePokemon(['id',$val[2],'name_rus']),
								'lvl' => $val[3],
								'id' => $val[2]
							];
						
						}
						
						$val[4] = $key;
						
						array_push($prizeNew, $val);
						
					}
					
				}
				
			}
			
			if($loot != 0) {
				
				//Для 6 события
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_random.php');
				
				matsuka\applyChancesToWeekEventNumber6($loot, $mainEvent);
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_applyUpChanceEffect.php');
	
				matsuka\applyUpChanceEffect($eventMy['score'], $loot);
				
				$lootNew = [];
				$chancesWeekEvent6 = [];
				
				foreach($loot as $i => $object) {
				
					foreach($object["objects"] as $key => $val) {
						
						$val[3] = $object["lvl"];
						
						if($val[0] == 'item') {
							
							$lootPar = ['item',Items::arrayItem([$val[1],[$val[2],1]]),$val[3]];
							
						} else {
							
							$lootPar = ['pokemon',Pokemons::arrayPokemon([Info::getBasePokemon(['id',$val[1],'name_rus']),1,Info::getNumPokemonNum($val[1])]),$val[3]];
							
						}
						
						array_push($lootNew, $lootPar);
						
					}
					
					$chancesWeekEvent6[] = $object["chance"];
					
				}
				
			}
			
			$this->response['response'] = array(
				'event' => $mainEvent,
				'event_my' => $eventMy,
				'prize' => $prizeNew,
				'buy' => (isset($buyNew) ? $buyNew : $buy),
				'checkLoot' => (isset($lootNew) ? $lootNew : $loot)
			);
			
			if($id == 6 && isset($chancesWeekEvent6)) {
				
				$this->response['response']["chances"] = $chancesWeekEvent6;
				
			}
		  
        break;

      }

    }

  }

  public static function updateScore($id,$count,$my,$user) {
    $stmt = Work::$sql->prepare("SELECT * FROM base_week_events WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $event = $data->fetch_assoc();
    if($event['score'] == 1) {
      $plus = $my + $count;
      if($plus >= $event['score_count']) {
        $plus = $event['score_count'];
      }
      $stmtq = Work::$sql->prepare("UPDATE user_week_events SET score = ? WHERE user = ? AND event = ?");
                $stmtq->bind_param("sii", $plus, $user, $id);
                $stmtq->execute();
    }
  }

  public function updateBuy($eventID, $id, $count) {

    $stmt = Work::$sql->prepare("SELECT * FROM user_week_events WHERE user = ? AND event = ?");
                    $stmt->bind_param("ii", $this->userInfo['id'], $eventID);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $user_event = $data->fetch_assoc();
                    
    $prize = Info::_unParseData($user_event['prize']);
    $prize[$id] = $count;
    $parse = Info::_ParseData($prize);
    
    $stmtq = Work::$sql->prepare("UPDATE user_week_events SET score = ? WHERE user = ? AND event = ?");
                $stmtq->bind_param("sii", $parse, $this->userInfo['id'], $eventID);
                $stmtq->execute();
  }

  public function getBuyThings($id) {
	  
	  //type, id, count, price
	  
    switch($id) {
      case 4:
        $buy = [
          5 => ['item',227,1,1]
        ];
      break;
      case 5:
        $buy = [
          8 => ['item',1,1,100000],
          9 => ['item',1,1,100000]
        ];
      break;
      case 6:
        $buy = [
          1 => ['item',228,2,1]
        ];
      break;
      default:
        $buy = 0;
      break;
    }
    return $buy;
  }

  public function updatePrizeScore($eventID, $id) {
      
    $stmt = Work::$sql->prepare("SELECT * FROM user_week_events WHERE user = ? AND event = ?");
                    $stmt->bind_param("ii", $this->userInfo['id'], $eventID);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $user_event = $data->fetch_assoc();
                    
    $prize = Info::_unParseData($user_event['prize']);
    $prize[$id] = 1;
    $parse = Info::_ParseData($prize);
    
    $stmtq = Work::$sql->prepare("UPDATE user_week_events SET prize = ? WHERE user = ? AND event = ?");
                $stmtq->bind_param("sii", $parse, $this->userInfo['id'], $eventID);
                $stmtq->execute();
  }

  public function nullScore($eventID) {
    $A = 0;
    $stmtq = Work::$sql->prepare("UPDATE user_week_events SET score = ? WHERE user = ? AND event = ?");
                $stmtq->bind_param("iii", $A, $this->userInfo['id'], $eventID);
                $stmtq->execute();
  }

  public function checkPrize($id) {
    switch ($id) {
      case 6:
        
		include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_prizes.php');
		
		$prizes = matsuka\getWeekEventsPrizesOfEvent6();
		
      break;
      default:
        $prizes = 0;
      break;
    }
    return $prizes;
  }

	public function basePrize() {

		$stmt = Work::$sql->prepare("SELECT event_info FROM system WHERE id = ?");
		$a = 1;
                    $stmt->bind_param("i", $a);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $prize = $data->fetch_assoc();

		$eventInfo = Info::_unParseData($prize['event_info']);

		return isset($eventInfo["prizeIndex"]) ? $eventInfo["prizeIndex"] : 1;
		
	}

	public function getPrize($id) {
		
		switch ($id) {
			
			case 1:
			
				switch(WeekEvents::basePrize()) {
					
					case 1:
					
						$prizeBase = ['pokemon', 775];
					
					break;
					
					case 2:
					
						$prizeBase = ['pokemon', 568];
						
					break;
					
					case 3:
					
						$prizeBase = ['pokemon', 506];
						
					break;
					
					default:
					
						$prizeBase = ['pokemon', 568];
						
					break;
				
				}
				
				$prize = [
					1 => [3,'item',33,10],
					2 => [6,'item',191,3],
					3 => [9,'item',192,1],
					4 => [12,'item',1,300000],
					5 => [15,'item',175,1],
					6 => [18,'item',72,1],
					7 => [24,$prizeBase[0],$prizeBase[1],1]
				];
				
			break;
			
			case 2:
			
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_top.php');
				
				$timeGameCoefficient = \matsuka\getTimeGameCoefficient();
				
				$division = 1;
				
				if($timeGameCoefficient == 0) {
					
					$division = 2;
					
				} else if($timeGameCoefficient == 1) {
					
					$division = 1.5;
					
				}
			
				$prize = [
					1 => [1,'item',1,400],
					10 => [10,'item',1,4000],
					12 => [50,'item',1,20000],
					11 => [100,'item',1,40000],
					2 => [300,'item',12,1],
					3 => [400,'item',11,1],
					7 => [1200,'item',127,1],
					4 => [2400,'item',313,1],
					5 => [3600,'item',73,1],
					6 => [4800,'item',74,1],
					8 => [6000,'pokemon',574,1],
					9 => [9000,'pokemon',529,1],
				];
				
				if($division > 1) {
					
					for($i = 1; $i <= count($prize); ++$i) {
						
						$prize[$i][0] = round($prize[$i][0] / $division);
						
					}
					
				}
				
			break;
			
			case 3:
			
				$prize = [
					1 => [1,'item',1,4000],
					6 => [10,'item',1,40000],
					7 => [50,'item',1,200000],
					8 => [100,'item',1,400000],
					2 => [5,'item',149,1],
					3 => [30,'item',109,3],
					4 => [60,'item',152,1],
					5 => [90,'item',127,2]
				];
			
			break;
			
			case 4:
			
				$prize = [
					1 => [250,'item',33,10],
					2 => [500,'item',191,3],
					3 => [750,'item',192,1],
					4 => [1000,'item',147,2]
				];
			
			break;
			
			case 5:
			
				$prize = [
					1 => [10,'item',33,10],
					2 => [25,'item',191,3],
					3 => [50,'item',192,1],
					4 => [75,'item',147,2],
					5 => [100,'item',175,1],
					6 => [125,'item',36,1],
					7 => [150,'pokemon',653,1]
				];
			
			break;
			
			case 6:
			
				$prize = 0;
			
			break;
			
			default:
			
				$prize = 0;
			
			break;
			
		}
		
		return $prize;
		
	}

  public static function getWeekEventMy($eventID, $user) {
    $stmt = Work::$sql->prepare("SELECT * FROM user_week_events WHERE user = ? AND event = ?");
                    $stmt->bind_param("ii", $user, $eventID);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $user_event = $data->fetch_assoc();
    return [
      'score' => $user_event['score'],
      'prize' => Info::_unParseData($user_event['prize'])
    ];
  }
  
	public static function getWeekEvents() {
		
		$stmt = Work::$sql->prepare("SELECT `event` FROM `system` WHERE id = ?");
		$a = 1;
                    $stmt->bind_param("i", $a);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $system = $data->fetch_assoc();
		
		return explode(",", $system['event']);
		
	}
	
	public static function getFeatureWeekEvents() {
		
		$stmt = Work::$sql->prepare("SELECT feature_events FROM `system` WHERE id = ?");
		$a = 1;
                    $stmt->bind_param("i", $a);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $system = $data->fetch_assoc();
		
		return explode(",", $system['feature_events']);
		
	}
	
	public static function isEventStarted($events, $id) {
		
		for($i = 0; $i < count($events); ++$i) {
			
			if($events[$i] == $id) return true;
			
		}
		
		return false;
		
	}
	
	public static function externalCheckStartEvent($id) {
		
		$events = WeekEvents::getWeekEvents();
		
		return WeekEvents::isEventStarted($events, $id);
		
	}

  public static function getMainWeekEvent($id) {
    $stmt = Work::$sql->prepare("SELECT * FROM base_week_events WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $event = $data->fetch_assoc();
		
    return [
      'id' => $event['id'],
      'name' => $event['name'],
      'about' => $event['about'],
      'score' => $event['score'],
      'score_count' => $event['score_count'],
      'type' => $event['type']
    ];
  }

}

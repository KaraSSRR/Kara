<?php

include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/timer.php');

function dropInfo($location, $basenum) {
	
	if($basenum == 0) {
		
		$stmtdi = Work::$sql->prepare("SELECT * FROM `base_drop_pokemons` WHERE `location_id`= ? AND `quest_id`= ?");
		$a = 0;
            $stmtdi->bind_param("ii", $location, $a);
            $stmtdi->execute();
            $dropInfo = $stmtdi->get_result();

	} else {
	
		$stmtdi = Work::$sql->prepare("SELECT * FROM `base_drop_pokemons` WHERE `location_id`= ? AND `pok_num`= ?");
            $stmtdi->bind_param("ii", $location, $basenum);
            $stmtdi->execute();
            $dropInfo = $stmtdi->get_result();
		
	}
					
	$textDrop = "";
	
	while($drop = $dropInfo->fetch_assoc()) {

		if($drop['questItem'] == 0) {
		
			$textDrop .= '<br><div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$drop["item_id"].'])" style="background-image: url(/img/world/items/little/'.$drop["item_id"].'.webp)"></div>';
			
		} else {
			
			$textDrop .= '<br><div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$drop["item_id"].'])" style="background-image: url(/img/world/items/little/'.$drop["item_id"].'.webp)"></div>';
			
		}
		
	}
	
	if($textDrop == "") {
		
		$textDrop = "Ничего";
		
	}
	
	return $textDrop;
	
}

function standardQuestMake($id, $questName, $notes) {
	
	$QuestList = [];
		  
	$stmtQ = Work::$sql->prepare("SELECT `step`, `end`, `data` FROM `user_quests` WHERE `user_id` = ? AND `quest_id` = ?");
        $stmtQ->bind_param("ii", $_SESSION['id'], $id);
        $stmtQ->execute();
        $dataQ = $stmtQ->get_result();
        $q = $dataQ->fetch_assoc();
	
	$qStep = $q["step"];
	
	$status = $q["end"] == 1;
	
	if($status) {

		$status = 2;

	} else if(isset($q)) {

		$status = 1;

	}
	
	if(is_array($notes)) {
		
		for($i = 0; $i < count($notes); ++$i) {
			
			if(is_array($notes[$i])) {
			
				if($qStep >= $notes[$i][0]) {
					
					$QuestList[] = $notes[$i][1];

				}
				
			} else {
				
				$text = $notes[$i]($qStep, $q["data"], $q["end"]);
				
				if(isset($text)) {
					
					$QuestList[] = $text;
					
				}
				
			}
			
		}
		
	}
	
	for($i = 0; $i < count($QuestList); ++$i) {

		$QuestList[$i] = ["text" => $QuestList[$i], "id" => ($i + 1)];

	}

	return array(
		'id' => $id,
		'name' => $questName,
		'proc' => $status,
		'step_list' => $QuestList
	);
	
}

Class Diary {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'openQuest':
		
			$mysqli = Work::$sql;
		  
		    switch(intval($val)) {
			
				case 1:
				
					$this->response['response'] = standardQuestMake(1, "Знакомство с миром", [
					
						[1, "Мне сказали пройти в <b>Кабинет</b>. Жду не дождусь пройти курс обучения и стать настоящим тренером покемонов!"],
						[3, "Я выбрал своего первого покемона. Надеюсь, мы с ним подружимся. Также я получил задание от профессора Карпинский. Мне нужно выбить <b>Денежное кольцо</b> из <b>#172 Пичу</b> на <b>Поле для тренировок</b>. Думаю, я справлюсь с этим заданием!"],
						[4, "Мне дали 20 покеболов, 1 генератор геноболов, 2 генератора дропа, 3 генератора опыта, 3 драгоценных ящика, 1 инкубатор, 1 набор вкусностей, 1 премиум. Теперь Мне нужно поймать <b>#172 Пичу</b> на <b>Поле для тренировок</b>. Не буду терять времени, пойду ловить этого покемона!"],
						/* function($step, $data, $end) {
							
							if($step >= 5) {
								
								return "Профессор попросил поймать пару для моего Пичу, а затем спарить их.";
								
							}
							
						}, */
						[5, "Профессор попросил поймать пару для моего Пичу, а затем спарить их."],
						[6, "Я прошёл курс обучения! Теперь Я - тренер покемонов! Моё путешествие по этому миру началось! Но, надо бы заглянуть к приятелю профессора Карпинского, который проживает в Лавендере."],
					
					]);
				
				break;
				
				case 2:
				
					$this->response['response'] = standardQuestMake(2, "Блокбастер", [
					
						function($step, $data, $end) {
							
							if($step >= 1) {
								
								if($data != "") {
									
									include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/getHar.php');
									include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/getPokemonBasenum.php');
							
									$info = Info::_unParseData($data);
						
									$need = "";
									
									for($i = 0; $i < 3; ++$i) {
										
										$basePok = Work::$sql->query("SELECT `name_rus` FROM `base_pokemons` WHERE `id` = ".$info[$i]["pok"])->fetch_assoc();
										
										$need .= "<b>#".\matsuka\getPokemonBasenum($info[$i]["pok"])." ".$basePok["name_rus"]."</b> с характером <b>".\matsuka\getHar($info[$i]["har"])."</b> и ".($info[$i]["gender"] == 1 ? "мужским" : "женским")." полом;<br>";
										
									}
									
									$need = substr($need, 0, strlen($need) - strlen("<br>"));
									
									return "Капиталло Назэбалло попросил помочь ему с фильмом. Мне нужно принести следующих покемонов:<br>".$need;
									
								} else {
									
									return "Капиталло Назэбалло попросил помочь ему с фильмом.";
									
								}
								
							}
							
						},
						[2, "Я отдал всех нужных покемонов Капиталло. В награду он мне дал <b>яйцо #236 Тирог и 4 шайнибола</b>."],
					
					]);
				
				break;
				
				case 4:
				
					$this->response['response'] = standardQuestMake(4, "Украденная игрушка", [
					
						[1, "Злые <b>#568 Траббиши</b> украли игрушку у маленькой девочки! Я должен помочь этой девочке вернуть игрушку. Бедняжка показывала пальцем в сторону <b>Дороги 18</b>. Наверное, там я и должен искать игрушку."],
						[2, "Я вернул игрушку. В награду Синди дала мне несколько конфет и драгоценный ящик."],
					
					]);
				
				break;
				
			  case 10:
			  
				$QuestList = [];
		  
				$b = 10;
				$stmt10 = Work::$sql->prepare("SELECT `step`,`end`,`data` FROM `user_quests` WHERE `user_id` = ? AND `quest_id` = ?");
                    $stmt10->bind_param("ii", intval($_SESSION['id']), $b);
                    $stmt10->execute();
                    $data10 = $stmt10->get_result();
                    $q10 = $data10->fetch_assoc();
				
				$status = intval($q10["step"]) == 5 && intval($q10["end"]) > 0;
				  
				  if($status) {
					  
					  $status = 2;
					  
				  } else if(isset($q10)) {
					  
					  $status = 1;
					  
				  }
				  
				  if($q10["step"] > 0) {
					  
					  if($q10["step"] >= 1) {
					  
						$QuestList[] = "Было интересно зайти в Поке-ясли. Необычное место. В одной из комнат нашел женщину, судя по надписи на бейджике, ее зовут Арина Политова. Она попросила меня принести переносную кроватку из спальни. Не буду ей отказывать.";
						
					  }
					  
					  if($q10["step"] >= 2) {
					  
						$QuestList[] = "Я взял кроваку, но меня укусил Ратикейт. Было больно.. Нужно сообщить Арине про злых крыс в спальне.";
						
					  }
					  
					  if($q10["step"] >= 3) {
					  
						$QuestList[] = "Эти Ратикейты лезут из подвала. Нужно прогнать их.";
						
					  }
					  
					  if($q10["step"] >= 4) {
					  
						$QuestList[] = "Большую часть крысенышей я прогнал. Арина дала мне 2 коробки с опытом Джирачи за помощь.";
						
					  }
					  
					  if($q10["step"] >= 5) {
						  
						  if($q10["end"] > time()) {
					  
							$QuestList[] = "Поке-ясли помимо ухаживания за покемонами занимаются еще и продажей покемонов. Засчет этого у них хватает средств на содержание малюток. Можно будет помочь Арине с заказами на яйца покемонов. Следует вернуться через ".\matsuka\matsukaTimer($q10["end"]-1," : ", "несколько минут")."";
							
						  } else {
							  
							  $QuestList[] = "Поке-ясли помимо ухаживания за покемонами занимаются еще и продажей покемонов. Засчет этого у них хватает средств на содержание малюток. Можно будет помочь Арине с заказами на яйца покемонов. Следует вернуться сейчас.";
							  
						  }
						
					  }
					  
					  if($q10["step"] >= 6) {
						  
						    $eggs = $q10["data"];
				
							$eggsText = "";
                               $sLang = Work::$sql->prepare("SELECT lang FROM user_settings WHERE user_id = ?");
                                             $sLang->bind_param("i", $_SESSION['id']);
                                             $sLang->execute();
                                             $dlang = $sLang->get_result();
                                             $lang = $dlang->fetch_assoc();
                                 
							$eggsArr = explode("|", $eggs);
							
							for($i = 0; $i < count($eggsArr); ++$i) {
								
								$thisEggInfo = explode(",", $eggsArr[$i]);
								
								$stmtBP = Work::$sql->prepare("SELECT `name_rus`,`name` FROM `base_pokemons` WHERE `id` = ?");
                                    $stmtBP->bind_param("i", intval($thisEggInfo[0]));
                                    $stmtBP->execute();
                                    $dataBP = $stmtBP->get_result();
                                    $basePok = $dataBP->fetch_assoc();
								if(!isset($lang) || $lang['lang'] == 0){
                                     $eggsText .= "яйца #".$thisEggInfo[0]." ".$basePok["name"]." ".$thisEggInfo[1]." шт., ";
                                 }else{
                                     $eggsText .= "яйца #".$thisEggInfo[0]." ".$basePok["name_rus"]." ".$thisEggInfo[1]." шт., ";
                                 }
								
							}
					  
							$QuestList[] = "Тётушке Политовой нужны следующие яйца: ".$eggsText." .";
						
					  }
					  
				  }
				  
				  for($i = 0; $i < count($QuestList); ++$i) {
					  
					  $QuestList[$i] = ["text" => $QuestList[$i], "id" => ($i + 1)];
					  
				  }
				  
				  $this->response['response'] = array(
					  'id' => 10,
					  'name' => "На радость детям",
					  'proc' => $status,
					  'step_list' => $QuestList
				  );
			  
			  break;
				
				default:
				
					$copy = $val;
					$val = intval(sprintf("%d", $val));
				
					$stmtD = Work::$sql->prepare('SELECT * FROM base_quest WHERE id = ?');
                                    $stmtD->bind_param("i", $copy);
                                    $stmtD->execute();
                                    $dataD = $stmtD->get_result();
                                    $Diary = $dataD->fetch_assoc();
                                    
					$stmtS = Work::$sql->prepare('SELECT * FROM quest_steps WHERE id_user = ? AND quest_id = ?');
                                    $stmtS->bind_param("ii", $userInfo['id'], $val);
                                    $stmtS->execute();
                                    $Steps = $stmtS->get_result();
					
					$QuestList = [];
					
					while($Step = $Steps->fetch_assoc()) {
						
						$QuestList[$Step['id']] = [
						  'text' => $Step['text'],
						  'id' => $Step['quest_step']
						];	
						
					}
					
					$this->response['response'] = array(
						'id' => $Diary['id'],
						'name' => $Diary['name'],
						'proc' => Info::getProcessVisibleQuest($Diary['id']),
						'step_list' => $QuestList
					);
				
				break;
			  
		  }
		  
        break;
        case 'open':
		
		if(!is_array($val)) die();
		
          switch($val[0]) {
            case 'news':
              $stmtN = Work::$sql->prepare('SELECT * FROM news_friend');
                    $stmtN->execute();
                    $News = $stmtN->get_result();
              $NewsList = [];
              while($New = $News->fetch_assoc()) {
                $stmtF = Work::$sql->prepare('SELECT * FROM users_friend WHERE status = ? AND (user_id = ? OR friend_id = ?) AND (user_id = ? OR friend_id = ?)');
                $s = 1;
                    $stmtF->bind_param("iiiii", $s, $userInfo['id'], $userInfo['id'], $New['user'], $New['user']);
                    $stmtF->execute();
                    $dataF = $stmtF->get_result();
                    $Friend = $dataF->fetch_assoc();
				
                if(isset($Friend) || $New["user"] == $_SESSION["id"] || $_SESSION["login"] == "Мемфиз") {
                  $text = explode(',',$New['text']);
                  if($New['type'] == 'cache') {
                    $text[1] = Items::countItem($text[1],1);
                  } else {
					  
					  
				  }
				  
				  $userInfoDiary = Info::getMainUser(['id',$New['user']]);
				  
				  if(!isset($userInfoDiary[0])) continue;
 
                  $NewsList[$New['id']] = [
                    'type' => $New['type'],
                    'text' => $text,
                    'num' => $New['num'],
                    'User' => $userInfoDiary,
                    'date' => $New['date']
                  ];
				  
                }
              }
			  
              $this->response['response'] = array(
                'news_list' => array_reverse($NewsList)
              );
            break;
            case "region":
			
				$stmtSB = Work::$sql->prepare('SELECT * FROM `system` WHERE id = ?');
				$a = 1;
                    $stmtSB->bind_param("i", $a);
                    $stmtSB->execute();
                    $dataSB = $stmtSB->get_result();
                    $systemBonuses = $dataSB->fetch_assoc();
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_okMnePox.php');
				
				$stmtLI = Work::$sql->prepare("SELECT location FROM `users` WHERE `id`= ?");
                    $stmtLI->bind_param("i", $_SESSION['id']);
                    $stmtLI->execute();
                    $dataLI = $stmtLI->get_result();
                    $locInfoQ = $dataLI->fetch_assoc();
                    
                $stmtRI = Work::$sql->prepare("SELECT region FROM `base_location` WHERE `id`= ?");
                    $stmtRI->bind_param("i", $locInfoQ['location']);
                    $stmtRI->execute();
                    $dataRI = $stmtRI->get_result();
                    $locInfo = $dataRI->fetch_assoc();
				
				
                $stmtAV = Work::$sql->prepare('
				
					SELECT * FROM pokemons_location
					JOIN base_location bl
					ON pokemons_location.location_id = bl.id
					WHERE bl.region = ?
					AND location_id != ?
					ORDER BY basenum ASC
					
				');
				$l = 0;
                    $stmtAV->bind_param("ii", $locInfo['region'], $l);
                    $stmtAV->execute();
                    $aviableWildPoksSql = $stmtAV->get_result();
			    
				
				
				$aviableWildPoksArr = [];
				
				$baseLocations = [];
				
				while($aviableWildPok = $aviableWildPoksSql->fetch_assoc()) {
					
					if(!isset($baseLocations[$aviableWildPok["location_id"]])) {
					
				 	$slN = Work::$sql->prepare("SELECT * FROM `base_location` WHERE `id`= ?");
                            $slN->bind_param("i", $aviableWildPok["location_id"]);
                            $slN->execute();
                            $dlN = $slN->get_result();
                            $locName = $dlN->fetch_assoc()["name"];

						
				 		$baseLocations[$aviableWildPok["location_id"]] = $locName;
						
					} else {
						
				 		$locName = $baseLocations[$aviableWildPok["location_id"]];
						
					}
					
					$drop = dropInfo($aviableWildPok["location_id"], $aviableWildPok["basenum"]);
					
					$aviableWildPoksArr[] = [
					
						"basenum" => $aviableWildPok["basenum"],
						"isRare" => $aviableWildPok["isRare"],
						"lvl" => $aviableWildPok["lvl"],
						"time" => $aviableWildPok["text_time"],
						'need' => $aviableWildPok['text_catch_condition'],
						'drop' => $drop,
						'catch' => $aviableWildPok['catch'],
						
				 		"location" => $locName,
						
					];
					
				}
				
				
				$this->response['aviableWildPoks'] = $aviableWildPoksArr;
			
			break;
			case 'location':
			
				//$PokemonsSql = Work::$sql->query('SELECT * FROM pokemons_location WHERE hide_loc = 0 AND location_id = '.$userInfo['location']);
				
				$stmtS = Work::$sql->prepare('SELECT catch, basenum, lvl, gen, sparka, timezone, timezone_b, item, isRare FROM pokemons_location WHERE location_id = ?');
                    $stmtS->bind_param("i", $userInfo['location']);
                    $stmtS->execute();
                    $PokemonsSql = $stmtS->get_result();
                    

				$stmtSQ = Work::$sql->prepare('SELECT * FROM pokemons_location WHERE location_id = ?');
				$s = 0; 
                    $stmtSQ->bind_param("i", $s);
                    $stmtSQ->execute();
                    $sbegsSql = $stmtSQ->get_result();
			  
				$PokemonsArr = [];
				$SbegsArr = [];
			  
				while($Pokemon = $PokemonsSql->fetch_assoc()) {
				  
					$PokemonsArr[] = $Pokemon;
				  
				}
			  
				while($Pokemon = $sbegsSql->fetch_assoc()) {
					  
					if(!($Pokemon["basenum"] > 0)) continue;
					  
					$SbegsArr[] = $Pokemon;
					  
				}
				  
				$PokemonList = [];
				  
				for($i = 0; $i < count($PokemonsArr); ++$i) {
						
					$Pokemon = $PokemonsArr[$i];
					
					$textDrop = dropInfo($userInfo['location'], $Pokemon['basenum']);
					
					$timeText = 0;
					
					if($Pokemon["timezone"] != "00:00:00" || $Pokemon["timezone_b"] != "00:00:00") {
						
						$timeText = "С ".$Pokemon["timezone"]." до ".$Pokemon["timezone_b"]."";
						
					}
					
					$need = 0;
					
					if($Pokemon["item"] > 0) {
						
						$need = "<div class=\"itemIsset\" onclick=\"PP.issets('item',['view', ".$Pokemon["item"]."])\" style=\"background-image: url(/img/world/items/little/".$Pokemon["item"].".png)\"></div>";
						
					}
					
					$PokemonList[] = [
					
					  'basenum' => Info::getNumPokemonNum($Pokemon['basenum']),
					  "lvl" => $Pokemon["lvl"],
					  'num' => $Pokemon['basenum'],
					  'catch' => $Pokemon['catch'],
					  'time' => $timeText,

					  'isRare' => $Pokemon["isRare"],

					  'drop' => $textDrop,
					  'need' => $need,
					  
					  'gen' => $Pokemon["gen"],
					  'repro' => $Pokemon["sparka"],
					  
					];
					
				}
				  
				$sbegListAll = [];

				for($i = 0; $i < count($SbegsArr); ++$i) {
						
					$Pokemon = $SbegsArr[$i];
					  
					if(!($Pokemon["basenum"] > 0)) continue;
					
					$textDrop = dropInfo($userInfo['location'], $Pokemon['basenum']);
					
					$timeText = 0;
					
					if($Pokemon["timezone"] != "00:00:00" || $Pokemon["timezone_b"] != "00:00:00") {
						
						$timeText = "С ".$Pokemon["timezone"]." до ".$Pokemon["timezone_b"]."";
						
					}
					
					$need = 0;
					
					if($Pokemon["item"] > 0) {
						
						$need = "<div class=\"itemIsset\" onclick=\"PP.issets('item',['view', ".$Pokemon["item"]."])\" style=\"background-image: url(/img/world/items/little/".$Pokemon["item"].".png)\"></div>";
						
					}
					
					$sbegListAll[] = [
					
					  'basenum' => Info::getNumPokemonNum($Pokemon['basenum']),
					  "lvl" => $Pokemon["lvl"],
					  'num' => $Pokemon['basenum'],
					  'catch' => $Pokemon['catch'],
					  'time' => $timeText,

					  'isRare' => $Pokemon["isRare"],

					  'drop' => $textDrop,
					  'need' => $need,
					  
					  'gen' => $Pokemon["gen"],
					  'repro' => $Pokemon["sparka"],
					  
					];
					
				}
				
				$globalDrop = dropInfo(0, 0);
				
				$this->response['response'] = array(
				
					'location' => Info::getLocation(['id',$userInfo['location'],'name']),
					'weather' => Info::getRegion(['id',Info::getLocation(['id',$userInfo['location'],'region']),'weather']),
					'pokemon_list' => $PokemonList,
					"sbeg_list" => $sbegListAll,
					"globalDrop" => $globalDrop,

				);
				
            break;
			
			case 'regionDrop':
				
				if(!isset($val[1])) die();
				
				$stmtLR = Work::$sql->prepare("SELECT id, name FROM `base_location` WHERE `region`= ?");
                    $stmtLR->bind_param("i", $val[1]);
                    $stmtLR->execute();
                    $locsInRegion = $stmtLR->get_result();
				
				$locationsArr = [];
				
				while($loc = $locsInRegion->fetch_assoc()) {
					
					$locArray = [$loc["name"], []];
					
					$stmtDI = Work::$sql->prepare("SELECT pok_num, item_id, questItem FROM `base_drop_pokemons` WHERE `location_id`= ? AND `quest_id`= ? AND pok_num > ?");
					$a = 0;
					$a1 = 0;
                        $stmtDI->bind_param("iii", $loc["id"], $a, $a1);
                        $stmtDI->execute();
                        $dropInfo = $stmtDI->get_result();
					
					while($drop = $dropInfo->fetch_assoc()) {
						
						$textDrop = [];
						
						if($drop['questItem'] == 0) {
						
							$textDrop = '<br><div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$drop["item_id"].'])" style="background-image: url(/img/world/items/little/'.$drop["item_id"].'.webp)"></div>';
							
						} else {
							
							$textDrop = '<br><div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$drop["item_id"].'])" style="background-image: url(/img/world/items/little/'.$drop["item_id"].'.webp)"></div>';
							
						}
						
						if(!isset($locArray[1][$drop["pok_num"]])) {
						
							$locArray[1][$drop["pok_num"]] = [$textDrop];
							
						} else {
							
							$locArray[1][$drop["pok_num"]][] = $textDrop;
							
						}
						
					}
					
					if(count($locArray[1]) > 0) {
					
						$locationsArr[] = $locArray;
						
					}
					
				}
				
				$this->response['response'] = array(
				
					"locationsDrop" => $locationsArr,
				
				);
			
			break;
			
			case "useful":
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_prizes.php');
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_random.php');
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_applyUpChanceEffect.php');
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/_top.php');
	
				$upPrizesCoefficient = \matsuka\getUpPrizesCoefficient();
				
				$standardUpGuaranteeChance = \matsuka\getStandardUpGuaranteeChance();
				$increasedUpGuaranteeChance = \matsuka\getIncreasedUpGuaranteeChance();
				
				$stmP = Work::$sql->prepare("SELECT progress FROM matsuka_garant_items WHERE user = ? AND item = ?");
				$p = 11;
                    $stmP->bind_param("ii", $_SESSION['id'], $p);
                    $stmP->execute();
                    $dataP = $stmP->get_result();
                    $guaranteePreciousChest = $dataP->fetch_assoc();
                    
				$stmR = Work::$sql->prepare("SELECT progress FROM matsuka_garant_items WHERE user = ? AND item = ?");
				$R = 9000;
                    $stmR->bind_param("ii", $_SESSION['id'], $R);
                    $stmR->execute();
                    $dataR = $stmR->get_result();
                    $guaranteeRunes = $dataR->fetch_assoc();
                    
				$stmT = Work::$sql->prepare("SELECT progress FROM matsuka_garant_items WHERE user = ? AND item = ?");
				$T = 9001;
                    $stmT->bind_param("ii", $_SESSION['id'], $T);
                    $stmT->execute();
                    $dataT = $stmT->get_result();
                    $guaranteeTreasure = $dataT->fetch_assoc();
				
				$prizesChest = \matsuka\getChestPrizes(isset($guaranteePreciousChest) ? $guaranteePreciousChest["progress"] : 0);
				$prizesPremiumChest = \matsuka\getPremiumChestPrizes();
				
				$prizesRunes = \matsuka\getRunesPrizes(isset($guaranteeRunes) ? $guaranteeRunes["progress"] : 0);
				
				$prizesTreasure = \matsuka\getTreasurePrizes(isset($guaranteeTreasure) ? $guaranteeTreasure["progress"] : 0);

				$eventMy = WeekEvents::getWeekEventMy(6, $_SESSION['id']);
				$mainEvent = WeekEvents::getMainWeekEvent(6);
				
				$prizesOfWeekEvent6 = matsuka\getWeekEventsPrizesOfEvent6();
				
				matsuka\applyChancesToWeekEventNumber6($prizesOfWeekEvent6, $mainEvent);

				for($i = 0; $i < count($prizesOfWeekEvent6); ++$i) {

					$prizesOfWeekEvent6[$i]["oChance"] = $prizesOfWeekEvent6[$i]["chance"];

				}

				matsuka\applyUpChanceEffect($eventMy['score'], $prizesOfWeekEvent6);
				
				$this->response['prizesChest'] = $prizesChest;
				$this->response['prizesPremiumChest'] = $prizesPremiumChest;
				
				$this->response['prizesRunes'] = $prizesRunes;
				
				$this->response['prizesTreasure'] = $prizesTreasure;
				
				$this->response['prizesOfWeekEvent6'] = $prizesOfWeekEvent6;
				
				$this->response['upPrizesCoefficient'] = $upPrizesCoefficient;
				
				$this->response['standardUpGuaranteeChance'] = $standardUpGuaranteeChance;
				$this->response['increasedUpGuaranteeChance'] = $increasedUpGuaranteeChance;
			
			break;
			
			case "regionPoks":
				
				if(!isset($val[1])) die();
				
				$stmtAV = Work::$sql->prepare('
				
					SELECT location_id, basenum, timezone, timezone_b, item, isRare, lvl, catch FROM pokemons_location
					JOIN base_location bl
					ON pokemons_location.location_id = bl.id
					WHERE bl.region = ?
					ORDER BY basenum ASC
					
				');
                    $stmtAV->bind_param("i", $val[1]);
                    $stmtAV->execute();
                    $availableWildPoksSql = $stmtAV->get_result();
				
				$availableWildPoksArr = [];
				$unAvailableWildPoksArr = [];
				
				$baseLocations = [];
				
				while($Pokemon = $availableWildPoksSql->fetch_assoc()) {
					
					if(!isset($baseLocations[$Pokemon["location_id"]])) {
					
						$stmtLI = Work::$sql->prepare("SELECT name FROM base_location WHERE id= ?");
                                $stmtLI->bind_param("i", $Pokemon["location_id"]);
                                $stmtLI->execute();
                                $dataLI = $stmtLI->get_result();
                                $locName = $dataLI->fetch_assoc()["name"];
						
						$baseLocations[$Pokemon["location_id"]] = $locName;
						
					} else {
						
						$locName = $baseLocations[$Pokemon["location_id"]];
						
					}
					
					$drop = dropInfo($Pokemon["location_id"], $Pokemon["basenum"]);
					
					$timeText = 0;
					
					if($Pokemon["timezone"] != "00:00:00" || $Pokemon["timezone_b"] != "00:00:00") {
						
						$timeText = "С ".$Pokemon["timezone"]." до ".$Pokemon["timezone_b"]."";
						
					}
					
					$need = 0;
					
					if($Pokemon["item"] > 0) {
						
						$need = "<div class=\"itemIsset\" onclick=\"PP.issets('item',['view', ".$Pokemon["item"]."])\" style=\"background-image: url(/img/world/items/little/".$Pokemon["item"].".png)\"></div>";
						
					}
					
					if($Pokemon["catch"] >= 1) {
						
						$availableWildPoksArr[] = [
						
							"basenum" => $Pokemon["basenum"],
							"isRare" => $Pokemon["isRare"],
							"lvl" => $Pokemon["lvl"],
							"time" => $timeText,
							'need' => $need,
							'drop' => $drop,
							
							"location" => $locName,
							
						];
						
					} else {
						
						$unAvailableWildPoksArr[] = [
						
							"basenum" => $Pokemon["basenum"],
							"isRare" => $Pokemon["isRare"],
							"lvl" => $Pokemon["lvl"],
							"time" => $timeText,
							'need' => $need,
							'drop' => $drop,
							
							"location" => $locName,
							
						];
						
					}
					
				}
				
				$this->response['available'] = $availableWildPoksArr;
				$this->response['unAvailable'] = $unAvailableWildPoksArr;
			
			break;
			
            case 'quests':
              $Diary = Work::$sql->query('SELECT * FROM base_quest');
              $QuestList = [];
              while($Quest = $Diary->fetch_assoc()) {
                $QuestList[$Quest['id']] = [
                  'id' => $Quest['id'],
                  'name' => $Quest['name'],
                  'proc' => Info::getProcessVisibleQuest($Quest['id'])
                ];
              }
              
			  $q10 = Work::$sql->query("SELECT `step`,`end` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '10'")->fetch_assoc();
			  
			  $status = intval($q10["step"]) == 5 && intval($q10["end"]) > 0;
			  
			  if($status) {
				  
				  $status = 2;
				  
			  } else if(isset($q10)) {
				  
				  $status = 1;
				  
			  }
		  
			  $QuestList[10] = [
			  
				"id" => 10,
				"name" => "На радость детям",
				"proc" => $status
			  
			  ];
			  
			  $q7 = Work::$sql->query("SELECT `step`,`data` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '7'")->fetch_assoc();
			  
			  $status = (intval($q7["step"]) == 1005 && $q7["data"] == "true") || (intval($q7["step"]) == 4 && $q7["data"] == "true");
			  
			  if($status) {
				  
				  $status = 2;
				  
			  } else if(isset($q7)) {
				  
				  $status = 1;
				  
			  }
		  
			  $QuestList[7] = [
			  
				"id" => 7,
				"name" => "Помощь старому приятелю",
				"proc" => $status
			  
			  ];
			  
              $this->response['response'] = array(
                'quest_list' => $QuestList
              );
              
            break;
          }
        break;

      }

    }

  }

}

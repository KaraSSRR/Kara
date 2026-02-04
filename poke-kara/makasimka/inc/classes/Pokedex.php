<?php

Class Pokedex {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'search':
		
			if(!isset($val) || mb_strlen($val, 'utf8') <= 1) {
				
				$this->response = ["error" => "Введите более 1 знака или любой номер"];
				return;
				
			}

			$stmt = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE name_rus LIKE ? OR name LIKE ?");
            $a = "%$val%";
                    $stmt->bind_param("ss", $a, $a);
                    $stmt->execute();
                    $Pokemon = $stmt->get_result();

			$PokList = [];

			while($Pok = $Pokemon->fetch_assoc()) {
				
				$PokList[$Pok['id']] = [
					
					'num' => $Pok['id'],
					'name' => $Pok['name_rus']
					
				];
				
			}

			$this->response = [
				
				'pok_list' => $PokList,
				
			];
			
        break;
		
		/*case "advancedSearch":
			
			if(!isset($val) || !is_array($val)) {
				
				$this->response = ["error" => "Некорректные данные!"];
				return;
				
			}
			
			$whereClause = [];
			
			$typesDecode = ['bug','dark','dragon','electric','fighting','fire','fly','ghost','grass','ground','ice','normal','poison','psychic','rock','steel','water','fairy'];
			
			for($i = 0; $i < count($val); ++$i) {
				
				$value = $val[$i];
				
				if(count($value) != 3) continue;
				
				if(
				
					(
					
						$value[0] == "type"
						
						||
						
						$value[0] == "type_two"
					
					)
					
					&&
					
					(
					
						$value[1] == "="
						
						||
						
						$value[1] == "!="
					
					)
					
					&&
					
					(
					
						$value[2] >= 0
						
						&&
						
						$value[2] <= 17
					
					)
					
				) {
					
					$whereClause[] = $value[0].$value[1].'"'.$typesDecode[$value[2]].'"';
					
				} else if(
					
					$value[1] == "="
					
					||
					
					$value[1] == "!="
					
					||
					
					$value[1] == "<"
					
					||
					
					$value[1] == "<="
					
					||
					
					$value[1] == ">"
					
					||
					
					$value[1] == ">="
					
				) {
					
					if(
					
						$value[0] == "hp"
						
						||
						
						$value[0] == "atk"
						
						||
						
						$value[0] == "def"
						
						||
						
						$value[0] == "spd"
						
						||
						
						$value[0] == "satk"
						
						||
						
						$value[0] == "sdef"
						
						||
						
						$value[0] == "power_category"
						
						||
						
						$value[0] == "height"
						
						||
						
						$value[0] == "weight"
						
						||
						
						$value[0] == "sex_m"
						
						||
						
						$value[0] == "sex_f"
						
					) {
						
						$whereClause[] = $value[0].$value[1].'"'.Work::$sql->real_escape_string($value[2]).'"';
						
					}
					
				}
				
			}
			
			if(count($whereClause) <= 0) {
				
				$this->response = ["error" => "Некорректные данные!"];
				return;
				
			}
			
			$whereClause = join(" AND ", $whereClause);
			
			//die(var_dump($whereClause));
			
			$Pokemon = Work::$sql->query("SELECT id, name_rus FROM base_pokemons WHERE ".$whereClause);
			
			$PokList = [];
			
			while($Pok = $Pokemon->fetch_assoc()) {
				
				$PokList[$Pok['id']] = [
					
					'num' => $Pok['id'],
					'name' => $Pok['name_rus']
					
				];
				
			}

			$this->response = [
				
				'pok_list' => $PokList,
				
			];
			
		break;*/

        case 'tab':
          switch($val[0]) {
            case 1:
              $stmt = Work::$sql->prepare("SELECT * FROM attac_poke_tm WHERE poke_base_id = ?");
                    $stmt->bind_param("i", $val[1]);
                    $stmt->execute();
                    $Attacks = $stmt->get_result();
                    
              $AtkList = [];
              while($Atk = $Attacks->fetch_assoc()) {
				$al = mb_strlen($Atk['tm_id']);
  				if($al == 2) {
  				  $al = '10'.$Atk['tm_id'];
  				  $al2 = $Atk['tm_id'];
			  }elseif($al == 3){
  				  $al2 = $Atk['tm_id'];
  				  $al = '1'.$Atk['tm_id'];
			  }else{
				  $al2 = '0'.$Atk['tm_id'];
  				  $al = '100'.$Atk['tm_id'];
			  }
                $stmtt = Work::$sql->prepare("SELECT * FROM base_items WHERE id = ?");
                    $stmtt->bind_param("i", $al);
                    $stmtt->execute();
                    $datat = $stmtt->get_result();
                    $tm_id = $datat->fetch_assoc();
                    
                $tm_name = mb_substr($tm_id['name'],0,2);
                
                $stmtts = Work::$sql->prepare('SELECT id,category,name_rus,name,type FROM base_atk WHERE id = ?');
                    $stmtts->bind_param("i", $tm_id['info']);
                    $stmtts->execute();
                    $datats = $stmtts->get_result();
                    $info = $datats->fetch_assoc();
				
				if($userInfo["langOfAtcks"] == "eng") {

					$tempName = $info["name_rus"];
					$info["name_rus"] = $info["name"];
					$info["name"] = $tempName;

				}
				
                $AtkList[$Atk['id_structure']] = [
                  'id' => $info['id'],
                  'category' => $info['category'],
                  'name_rus' => $info['name_rus'],
                  'type' => $info['type'],
                  'tm' => $tm_name.' '.$al2
                ];
              }
              $this->response['response'] = array(
                'atk_list' => $AtkList
              );
            break;
            case 2:
              $stmt = Work::$sql->prepare("SELECT * FROM base_attacks_pokemons WHERE type = ? AND pok = ?");
              $a = "sex";
                    $stmt->bind_param("si", $a, $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $Attacks = $data->fetch_assoc();
                    
              $atkList = explode(',',$Attacks['attacks']);
              for($i = 0; $i < count($atkList); $i++){
            		$stmta = Work::$sql->prepare('SELECT * FROM base_atk WHERE id = ?');
                              $stmta->bind_param("i", $atkList[$i]);
                              $stmta->execute();
                              $dataa = $stmta->get_result();
                              $info = $dataa->fetch_assoc();
					
					if($userInfo["langOfAtcks"] == "eng") {

						$tempName = $info["name_rus"];
						$info["name_rus"] = $info["name"];
						$info["name"] = $tempName;

					}
					
              	$val2[$i] = $info;
            	}
              $this->response['response'] = array(
                'atk_list' => $val2
              );
            break;
            case 5:
              $stmt = Work::$sql->prepare("SELECT * FROM base_attacks_pokemons WHERE type = ? AND pok = ?");
              $a = "lvl";
              $stmt->bind_param("si", $a, $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $Attacks = $data->fetch_assoc();
              $atkLvl = explode(',',$Attacks['lvl']);
              $atkList = explode(',',$Attacks['attacks']);
              for($i=0;$i<count($atkLvl);$i++){
            		$stmta = Work::$sql->prepare('SELECT * FROM base_atk WHERE id = ?');
                              $stmta->bind_param("i", $atkList[$i]);
                              $stmta->execute();
                              $dataa = $stmta->get_result();
                              $info = $dataa->fetch_assoc();
					
					if($userInfo["langOfAtcks"] == "eng") {

						$tempName = $info["name_rus"];
						$info["name_rus"] = $info["name"];
						$info["name"] = $tempName;

					}
					
            		$val1[$i] = $atkLvl[$i];
              	$val2[$i] = $info;
            	}
              $this->response['response'] = array(
                'atk_list' => $val2,
                'lvl_list' => $val1
              );
            break;
            case 3:
              $stmt = Work::$sql->prepare("SELECT * FROM base_attacks_pokemons WHERE type = ? AND pok = ?");
              $a = "npc";
                    $stmt->bind_param("si", $a, $val[1]);
                    $stmt->execute();
                    $Attacks = $stmt->get_result();
              $AtkList = [];
              while($Atk = $Attacks->fetch_assoc()) {
                $stmta = Work::$sql->prepare('SELECT * FROM base_atk WHERE id = ?');
                              $stmta->bind_param("i", $Atk['attacks']);
                              $stmta->execute();
                              $dataa = $stmta->get_result();
                              $info = $dataa->fetch_assoc();
				
				if($userInfo["langOfAtcks"] == "eng") {

					$tempName = $info["name_rus"];
					$info["name_rus"] = $info["name"];
					$info["name"] = $tempName;

				}
				
                $AtkList[$Atk['id']] = [
                  'id' => $info['id'],
                  'category' => $info['category'],
                  'name_rus' => $info['name_rus'],
                  'type' => $info['type']
                ];
              }
              $this->response['response'] = array(
                'atk_list' => $AtkList
              );
            break;
            case 6:
              $stmt = Work::$sql->prepare("SELECT id FROM user_pokemons WHERE basenum = ?");
                    $stmt->bind_param("i", $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $PokemonCount = $data->num_rows;
			  
			  $stmtA = Work::$sql->prepare("SELECT id FROM user_pokemons WHERE user_id = ? AND basenum = ?");
                    $stmtA->bind_param("ii", $_SESSION["id"], $val[1]);
                    $stmtA->execute();
                    $dataA = $stmtA->get_result();
                    $iHaveThisPok = $dataA->fetch_assoc();
			  
			  $stmtAS = Work::$sql->prepare("SELECT id FROM user_pokemons WHERE user_id = ? AND type != ? AND basenum = ?");
			  $a = 'normal';
                    $stmtAS->bind_param("isi", $_SESSION["id"], $a, $val[1]);
                    $stmtAS->execute();
                    $dataAS = $stmtAS->get_result();
                    $iHaveShineThisPok = $dataAS->fetch_assoc();
			  
			  if($userInfo["user_group"] == 1 && $PokemonCount > 0 /* && in_array(intval($val[1]), [133, 134, 135, 136, 137, 233, 474, 175, 176, 468, 196, 197, 280, 281, 282, 475, 470, 471, 700, 144, 145, 146, 150, 151, 228, 229, 245, 244, 243, 251, 374, 375, 376, 377, 378, 379, 380, 381, 480, 481, 482, 485, 490, 491, 492, 493, 494, 529, 530, 566, 567, 570, 571, 592, 593, 607, 608, 609, 610, 611, 612, 633, 634, 635, 636, 637, 638, 639, 640, 645, 646, 647, 669, 670, 671, 679, 680, 681, 701, 704, 705, 706, 712, 713, 714, 715, 718, 782, 783, 784, 785, 786, 787, 788, 789, 790, 791, 792, 793, 794]) */) {
				  
					$PokemonCount .= ".<br></b>Тренеры которые имеют данного покемона:<br><br>";
				  
					$stmtHT = Work::$sql->prepare("SELECT user_id FROM user_pokemons WHERE user_id != ? AND basenum = ?");
					    $two = 2;
                        $stmtHT->bind_param("ii", $two, $val[1]);
                        $stmtHT->execute();
                        $whoHasThisPok = $stmtHT->get_result();

					$usersArr = [];
						
					while($trainer = $whoHasThisPok->fetch_assoc()) {
						
						if(!isset($usersArr[$trainer["user_id"]])) {
							
							$stmtl = Work::$sql->prepare("SELECT login FROM users WHERE id = ?");
                                $stmtl->bind_param("i", $trainer["user_id"]);
                                $stmtl->execute();
                                $datal = $stmtl->get_result();
                                $login = $datal->fetch_assoc()["login"];
							
							$usersArr[$trainer["user_id"]] = [
							
								"login" => $login,
								"count" => 1,
								
							];
							
						} else {
							
							$usersArr[$trainer["user_id"]]["count"] += 1;
							
						}
						
					}
					
					foreach($usersArr as $key => $value) {
						
						$PokemonCount .= "<b>".$value["login"]." - ".$value["count"]." шт!<br>";
						
					}
					
					//$PokemonCount = substr($PokemonCount, 0, strlen($PokemonCount) - 4);
				  
			  }
			  
              $this->response['response'] = array(
                'count' => $PokemonCount,
				"have" => (!$iHaveThisPok["id"] ? 0 : 1),
				"haveShine" => (!$iHaveShineThisPok["id"] ? 0 : 1)
              );
            break;
            case 4:
              $stmt = Work::$sql->prepare("SELECT * FROM pokemons_location WHERE hide_loc = ? AND basenum = ?");
              $a = 0;
                    $stmt->bind_param("ii",$a, $val[1]);
                    $stmt->execute();
                    $Locations = $stmt->get_result();
                    
              $stmtA = Work::$sql->prepare("SELECT * FROM base_pokemon_areal WHERE pokemon = ?");
              $a = 0;
                    $stmtA->bind_param("i", $val[1]);
                    $stmtA->execute();
                    $Areals = $stmtA->get_result();
                    
              $LocationList = [];
              $ArealList = [];
              
              while($Loc = $Locations->fetch_assoc()) {
                  
                if($Loc['location_id'] != 777777) {
                    
                  $LocationList[$Loc['id']] = [
                    'location' => Info::getLocation(['id',$Loc['location_id'],'name']),
                    'region' => Info::getRegion(['id',Info::getLocation(['id',$Loc['location_id'],'region']),'name']),
                    'catch' => $Loc['catch']
                  ];
                  
                }
                
              }
              
              while($Areal = $Areals->fetch_assoc()) {
                switch($Areal['type']) {
                  case 1:
                    $ArealVal = [Info::getStringQuest(['id',$Areal['val'],'name'])];
                  break;
                  case 2:
                  case 3:
                  case 4:
                  case 5:
                    $ArealVal = [$Areal['val']];
                  break;
                }
                $ArealList[$Areal['id']] = [
                  'type' => $Areal['type'],
                  'arealVal' => $ArealVal
                ];
              }
              $this->response['response'] = array(
                'location_list' => $LocationList,
                'areal_list' => $ArealList
              );
            break;
          }
        break;

        case 'checkForm':
          $stmt = Work::$sql->prepare("SELECT * FROM base_pokemon_forms WHERE pokemons = ?");
                    $stmt->bind_param("i", $val);
                    $stmt->execute();
                    $PokemonForm = $stmt->get_result();
                    
          $PokemonFormVal = [];
          while($pf = $PokemonForm->fetch_assoc()) {
            $PokemonFormVal[$pf['id']] = [
              'name' => $pf['name'],
              'form' => $pf['id_form'],
              'pokemon' => $pf['pokemons']
            ];
          }
          $this->response['response'] = array(
            'forms' => $PokemonFormVal
          );
        break;

        case 'open':
          $stmt = Work::$sql->prepare("SELECT id,name_rus,type,type_two,hp,def,sdef,satk,atk,spd,weight,height,sex_f,sex_m,power_category,exp_group FROM base_pokemons WHERE id = ?");
                    $stmt->bind_param("i", $val[0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $Pokemon = $data->fetch_assoc();
          /*$stmtA = Work::$sql->prepare("SELECT * FROM base_pokemon_ability WHERE pok = ? ");
                    $stmtA->bind_param("i", $Pokemon['id']);
                    $stmtA->execute();
                    $dataA = $stmtA->get_result();
                    $Ability = $dataA->fetch_assoc();*/
        //var_dump($val);
          if($val[1] == 'false') {
            $stmtAS = Work::$sql->prepare("SELECT * FROM base_pokemon_forms WHERE start = ? AND pokemons = ?");
            $a = 1;
                    $stmtAS->bind_param("ii", $a, $Pokemon['id']);
                    $stmtAS->execute();
                    $dataAS = $stmtAS->get_result();
                    $PokemonForm = $dataAS->fetch_assoc();
            if(isset($PokemonForm)) {
              $PokemonFormVal = [$PokemonForm['name'],$PokemonForm['id_form']];
            }else{
              $PokemonFormVal = 0;
            }
          }else{
              
            $stmts = Work::$sql->prepare("SELECT * FROM base_pokemon_forms WHERE pokemons = ? AND id_form = ?");
                    $stmts->bind_param("ii", $val[0], $val[1]);
                    $stmts->execute();
                    $datas = $stmts->get_result();
                    $PokemonForm = $datas->fetch_assoc();
            $PokemonFormVal = [$PokemonForm['name'],$PokemonForm['id_form']];        
            /*$stmtA = Work::$sql->prepare("SELECT * FROM base_pokemon_ability WHERE pok = ?");
                    $stmtA->bind_param("i", $Pokemon['id']);
                    $stmtA->execute();
                    $dataA = $stmtA->get_result();
                    $Ability = $dataA->fetch_assoc();*/
                    
          }
          $this->response['response'] = array(
            'form' => $PokemonFormVal,
            'num' => $Pokemon['id'],
            'num_next' => ($Pokemon['id'] + 1),
            'num_back' => ($Pokemon['id'] - 1),
            'basenum' => Info::getNumPokemonNum($Pokemon['id']),
            'name' => $Pokemon['name_rus'],
            'type_a' => ($PokemonFormVal != 0 ? ($PokemonForm['type'] == 'not' ? 0 : $PokemonForm['type']) : ($Pokemon['type'] == 'not' ? 0 : $Pokemon['type'])),
            'type_b' => ($PokemonFormVal != 0 ? ($PokemonForm['type_two'] == 'not' ? 0 : $PokemonForm['type_two']) : ($Pokemon['type_two'] == 'not' ? 0 : $Pokemon['type_two'])),
            'stat_atk' => ($PokemonFormVal != 0 ? $PokemonForm['atk'] : $Pokemon['atk']),
            'stat_hp' => ($PokemonFormVal != 0 ? $PokemonForm['hp'] : $Pokemon['hp']),
            'stat_def' => ($PokemonFormVal != 0 ? $PokemonForm['def'] : $Pokemon['def']),
            'stat_satk' => ($PokemonFormVal != 0 ? $PokemonForm['satk'] : $Pokemon['satk']),
            'stat_sdef' => ($PokemonFormVal != 0 ? $PokemonForm['sdef'] : $Pokemon['sdef']),
            'stat_spd' => ($PokemonFormVal != 0 ? $PokemonForm['spd'] : $Pokemon['spd']),
            'go' => $Pokemon['exp_group'],
            'weight' => $Pokemon['weight'],
            'height' => $Pokemon['height'],
            'sex_m' => $Pokemon['sex_m'],
            'sex_f' => $Pokemon['sex_f'],
            'stars' => $Pokemon['power_category']
          );
        break;

      }

    }

  }

}

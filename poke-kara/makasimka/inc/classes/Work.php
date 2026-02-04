<?php
class Work{

    /**@var $sql mysqli*/
    public static $sql;

    /**@var $info array*/
    private static $info = [];

    public function __construct(mysqli $mysqli){
        self::$sql = $mysqli;
    }

    public static function _setInfo($key, $value){
        if(isset(self::$info[$key])){

            if(is_array(self::$info[$key])){

                if(!is_array($value)){
                    self::$info[$key][] = $value;
                }else{
                    self::$info[$key] = array_merge(self::$info[$key], $value);
                }

            }else{
                self::$info[$key] = $value;
            }

        }else{
            self::$info[$key] = $value;
        }
    }

    public static function _setStrongInfo($value){
        if($value && is_array($value)){
            self::$info = $value;
        }
    }


    public static function _viewOut(){
        global $appError;
        $systemInfo = self::$sql->query('SELECT * FROM `system` WHERE `id`= 1')->fetch_assoc();
        if(!$appError || $systemInfo['closed'] == 1){
            if(!empty($server_ver)){
                self::$info['server_ver'] = $server_ver;
            }
            print Info::_parseData(self::$info);
            die();
        }
    }

	public function _questStep($questID, $questStep){
		if($questStep == 0) $a = true;
		else{
			$q = self::$sql->query('SELECT `step`
									FROM `user_quests`
									WHERE
										`user_id` = '.$_SESSION['id'].'
									AND
										`quest_id` = '.$questID
									)->fetch_assoc();
			$a = ($q['step'] == $questStep ? true : false);
		}

		return $a;
	}

	public function _itemIsset($itemID, $itemCount, $userID){
		$userID = (empty($userID) ? $_SESSION['id'] : clearInt($userID));
		$i = self::$sql->query('SELECT `count`
								FROM `items_users`
								WHERE
									`user` = '.$userID.'
								AND
									`item_id` = '.$itemID.'
								AND
									`count` >= '.$itemCount
							)->fetch_assoc();
		$a = (!empty($i['count']) && $i['count'] > 0 ? true : false);

		return $a;
	}

	public function _questUpdate($questID, $questStep, $questEnd = false){
		$questEnd = (!$questEnd ? 0 : $questEnd);
		if(self::_questIsset($questID)){
			$a = self::$sql->query('UPDATE `user_quests`
									SET
										`step` = '.$questStep.',
										`end` = '.$questEnd.'
									WHERE
										`user_id` = '.$_SESSION['id'].'
									AND
										`quest_id` = '.$questID);
		}else{
			$a = self::$sql->query('INSERT INTO
									`user_quests` 	(`quest_id`,`user_id`,`step`,`end`)
									VALUES			('.$questID.','.$_SESSION['id'].','.$questStep.','.$questEnd.') ');
		}
		return $a;
	}

	public function _questIsset($questID){
	  $quest = self::$sql->query('SELECT `quest_id`
								FROM `user_quests`
								WHERE
									`user_id` = '.$_SESSION['id'].'
								AND
									`quest_id` = '.$questID
							)->fetch_assoc();
	  $a = ($quest['quest_id'] ? true : false);
	  return $a;
	}

	public function _questNote($questID, $questStep, $questNote){
		self::$sql->query('INSERT INTO `quest_steps`
								(`id_user`,`text`,`quest_id`,`quest_step`)
							VALUES
								('.$_SESSION['id'].',"'.$questNote.'",'.$questID.','.$questStep.')
						');
	}

  public function _itemPlus($itemID, $count, $user = false){
    if($itemID > 0){
      $user = (!$user ? $_SESSION['id'] : $user);
      $base = self::$sql->query("SELECT `str` FROM `base_items` WHERE `id`= '".$itemID."'")->fetch_assoc();
      $items = self::$sql->query("SELECT * FROM `items_users` WHERE `user`= '".$user."' AND `item_id` = '".$itemID."'")->fetch_assoc();
      if(!empty($items['count']) && $base['str'] == '0'){
        $itemsCount = $items['count'] + $count;
        self::$sql->query("UPDATE `items_users` SET `count` = '".$itemsCount."' WHERE `item_id` = '".$itemID."' AND `user` = '".$user."'");
      }else{
        if($base['str'] == '0') {
          self::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`) VALUES ('".$user."','".$itemID."','".$count."') ");
        }else{
          $i = 1;
          $str = explode(',',$base['str']);
          while($i <= $count) {
            $rand_str = mt_rand($str[0],$str[1]);
            self::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$user."','".$itemID."','1','".$rand_str.','.$rand_str."') ");
            $i++;
          }
        }
      }
    }else{
      return false;
    }
	}

	public function _npcTimeCheck($npcID){
		if($npcID > 0){
			$timeCheck = self::$sql->query('SELECT `time`
									FROM `base_npc_data`
									WHERE
										`userID` = '.$_SESSION['id'].'
									AND
										`npcID` = '.$npcID
								)->fetch_assoc();
			$a = ($timeCheck['time'] > time() ? true : false);

			return $a;
		}else{
			return false;
		}
	}

	public function _createPokemon($pokID, $userID = false, $infoPokemon = false){
		if($pokID > 0){
			$userID = (isset($userID) ? $userID : $_SESSION['id']);
			$pokemon = self::$sql->query('SELECT `name_rus`,
												`sex_m`,
                        `sex_m`,
												`exp_group`,
												`hp`,
												`atk`,
												`def`,
												`spd`,
												`satk`,
												`sdef`
										FROM `base_pokemons`
										WHERE
											`id` = '.$pokID)->fetch_assoc();

				$lvl = ($infoPokemon['lvl'] ? $infoPokemon['lvl'] : 1);

				$character = ($infoPokemon['character'] ? $infoPokemon['character'] : rand(1,26));

				$birthday = '{"user_id":"'.$userID.'","date": "'.time().'"}';

				$active = ($infoPokemon['active'] ? $infoPokemon['active'] : 0);

				$type = ($infoPokemon['type'] ? $infoPokemon['type'] : 'normal');
        if($pokemon['sex_f'] == 0 && $pokemon['sex_m'] == 0) {
          $gender = 'Бесполый';
        }else{
          $gender = ($pokemon['sex_f'] > 0 ? ( $pokemon['sex_f'] >= mt_rand(0, 100) ? 'Девочка' : 'Мальчик' ) : 'Мальчик');
        }

				$exp = [
						'now'=>Info::_getExp($lvl, $pokemon['exp_group']),
						'max'=>Info::_getExp(($lvl + 1), $pokemon['exp_group'])
					];

				$ev = ($infoPokemon['ev'] ? $infoPokemon['ev'] : 0);

				$trade = ($infoPokemon['trade'] ? $infoPokemon['trade'] : 'false');

				$gen = ($infoPokemon['gen'] ? $infoPokemon['gen'] : rand(20,28).','.rand(20,28).','.rand(20,28).','.rand(20,28).','.rand(20,28).','.rand(20,28));

				$characterQuery = self::$sql->query('SELECT * FROM `har` WHERE `id_har` = '.$character)->fetch_assoc();

				$statsArray = [
							'HP'	=>	round((($pok_base['hp'] * 2) + rand(20, 28)) * (1 / 100) + 10 + 1),
							'ATK'	=>	round((($pok_base['atk'] * 2 + rand(20, 28)) * 1 / 100 + 5) * $har['atk']),
							'DEF'	=>	round((($pok_base['def'] * 2 + rand(20, 28)) * 1 / 100 + 5) * $har['def']),
							'SPD'	=>	round((($pok_base['spd'] * 2 + rand(20, 28)) * 1 / 100 + 5) * $har['speed']),
							'SATK'	=>	round((($pok_base['satk'] * 2 + rand(20, 28)) * 1 / 100 + 5) * $har['satk']),
							'SDEF'	=>	round((($pok_base['sdef'] * 2 + rand(20, 28)) * 1 / 100 + 5) * $har['sdef'])
						];

				$stats = implode(',', $statsArray);

				$eggAttacks = self::$sql->query('SELECT `attacks`
												FROM `base_attacks_pokemons`
												WHERE
													`pok` = '.$pokID.'
												AND
													`type` = "sex"
											')->fetch_assoc();
				$arrayAttacks = explode(',',$eggAttacks['attacks']);
				if(rand(1,100) < 15){
					$numberAttack = mt_rand(0, count($arrayAttacks) - 1);
					if($eggThis == false){
						if(!empty($arrayAttacks[$numberAttack])){
							$attacksList = $arrayAttacks[$numberAttack].',0,0,0';
						}else{
							$attacksList = '0,0,0,0';
						}
					}else{
						$attacksList = '0,0,0,0';
					}
				}else{
					$attacksList = '0,0,0,0';
				}

				$sparkNumber = rand(1,3);

				// self::$sql->query("INSERT INTO `user_pokemons`
											// (`user_id`,`basenum`,`name_new`,`character`,`lvl`,`birthday`,`active`,`type`,`gender`,`exp`,`exp_max`,`ev`,`hp`,`stats`,`gen`,`owner`,`master`,`attacks`,`trade`)
								// VALUES 		(".$userID.",".$pokID.",'".$pokemon['name_rus']."','".$character."','".$lvl."','".$birthday."','".$active."','".$type."','".$gender."','".$exp['now']."','".$exp['max']."','".$ev."','".$statsArray['HP']."','".$stats."','".$gen."','".$userID."','".$userID."','".$attacksList."','".$trade."') ");
		}else{
			return false;
		}
	}
}

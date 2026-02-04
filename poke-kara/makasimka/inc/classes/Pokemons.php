<?php

Class Pokemons {

  private $response = [];
  private $userInfo = [];

  public static function arrayPokemon($val) {
		return [
			'name' => $val[0],
      'lvl' => $val[1],
      'num' => $val[2]
		];
	}

  public static function hiddenPower($gen) {
    $hpgen[0] = (($gen[0] % 2) == 0 ? 0 : 1);
    $hpgen[1] = (($gen[1] % 2) == 0 ? 0 : 1);
    $hpgen[2] = (($gen[2] % 2) == 0 ? 0 : 1);
    $hpgen[3] = (($gen[3] % 2) == 0 ? 0 : 1);
    $hpgen[4] = (($gen[4] % 2) == 0 ? 0 : 1);
    $hpgen[5] = (($gen[5] % 2) == 0 ? 0 : 1);
    $hptype = ((($hpgen[0] + (2 * $hpgen[1]) + (4 * $hpgen[2]) + (8 * $hpgen[3]) + (16 * $hpgen[4]) + (32 * $hpgen[5])) * 15) / 63);
    $hptype = floor($hptype);
    return $hptype;
  }
  
  public function _getModAtk($pok){
        return (isset($pok['modified'], $pok['modified']['atk']) ? $pok['modified']['atk'] : []);
}
    public function _getModDef($pok){
        return (isset($pok['modified'], $pok['modified']['def']) ? $pok['modified']['def'] : []);
    }
    public function _getModSpd($pok){
        return (isset($pok['modified'], $pok['modified']['spd']) ? $pok['modified']['spd'] : []);
    }
    public function _getModSAtk($pok){
        return (isset($pok['modified'], $pok['modified']['satk']) ? $pok['modified']['satk'] : []);
    }
    public function _getModSDef($pok){
        return (isset($pok['modified'], $pok['modified']['sdef']) ? $pok['modified']['sdef'] : []);
    }
	
	public function _checkStatus($pok, $statusName){
        if(isset($pok['status_list'], $pok['status_list'][$statusName])){
            return true;
        }
        return false;
    }
  
  public function _getStatAtk($pok, $stat){

        /*if(isset($pok['ability']) && $pok['ability'] == 76) {
          $stat = ($stat * 2);
        }

        if(isset($pok['ability']) && $pok['ability'] == 37) {
          if($pok['hp'] <= floor(($pok['hp_max'] / 2))) {
            $stat = ($stat / 2);
          }
        }*/

        if($stat > 0){
            $mod = $this->_getModAtk($pok);

            if(!empty($mod)){
                $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
            }
        }
        return $stat;
    }
    public function _getStatDef($pok, $stat){

        /*if(isset($pok['ability']) && $pok['ability'] == 63) {
          $stat = ($stat * 2);
        }

        if(isset($pok['ability']) && $pok['ability'] == 68 && $this->_checkStatus($pok, 'terrGrass')) {
          $stat = ($stat * 1.5);
        }*/

        if($stat > 0){
            $mod = $this->_getModDef($pok);

            if(!empty($mod)){
                $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
            }
        }

        return $stat;

    }
    public function _getStatSpd($pok, $stat){

        if($stat > 0){
            $mod = $this->_getModSpd($pok);

            if(!empty($mod)){
                $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
            }
        }

        if($this->_checkStatus($pok, 'paralyzed')){
            $stat = floor($stat / 4);
        }

        return $stat;

    }
    public function _getStatSAtk($pok, $stat){

        /*if(isset($pok['ability']) && $pok['ability'] == 37) {
          if($pok['hp'] <= floor(($pok['hp_max'] / 2))) {
            $stat = ($stat / 2);
          }
        }*/

        if($stat > 0){
            $mod = $this->_getModSAtk($pok);

            if(!empty($mod)){
                $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
            }
        }

        return $stat;

    }
    public function _getStatSDef($pok, $stat){

        if($stat > 0){
            $mod = $this->_getModSDef($pok);

            if(!empty($mod)){
                $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
            }
        }

        return $stat;

    }
	
	public static function getInfoAboutAtks($atk, $pp, $gen, $fromZero = false) {
		
		$returns = [];

		for($i = 0; $i < 4; ++$i) {
			
			$index = ($fromZero == false ? "atk_".($i + 1) : $i);
			
			$returns[$index] = [
		
				'type' => (isset($atk[$i]) && $atk[$i] > 0 ? Info::getStringAttack(['id',$atk[$i],'type']) : 'empty'),
				'name' => (isset($atk[$i]) && $atk[$i] > 0 ?  Info::getStringAttack(['id',$atk[$i],'name_rus']) : 'Нет атаки'),
				'category' => (isset($atk[$i]) && $atk[$i] > 0 ?  Info::getCategoryAtk(Info::getStringAttack(['id',$atk[$i],'category'])) : '0'),
				'id' => (isset($atk[$i]) && $atk[$i] > 0 ? Info::getStringAttack(['id',$atk[$i],'id']) : 0),
				'pp' => (isset($atk[$i]) && $atk[$i] > 0 ? $pp[$i].'/'.Info::getStringAttack(['id',$atk[$i],'pp']) : '0/0'),
				'val' => [(isset($atk[$i]) && $atk[$i] > 0 ? (Info::getStringAttack(['id',$atk[$i],'id']) == 238 ? Pokemons::hiddenPower(explode(',',$gen)) : 100) : 100)]
			
			];
			
		}
		
		return $returns;
		
	}

  public function getMainPokemon($id) {
	  
	if($this->userInfo["status"] == "battle") {
		
		$stmt = Work::$sql->prepare("SELECT user_1, user_2, info_1, info_2 FROM battle WHERE id  = ? AND (user_1 = ? OR user_2 = ?)");
                    $stmt->bind_param("iii", $this->userInfo["status_id"], $_SESSION["id"], $_SESSION["id"]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $battleInfo = $data->fetch_assoc();
		
		//бой не начался де факто, мб це ком
		if(!isset($battleInfo)) {
			
			//stat_updates($id);
			
			$stmtp = Work::$sql->prepare('SELECT * FROM user_pokemons WHERE id = ?');
                    $stmtp->bind_param("i", $id);
                    $stmtp->execute();
                    $datap = $stmtp->get_result();
                    $pok = $datap->fetch_assoc();
                    
			$pp = explode(',', $pok['pp_attacks']);
			
		} else {
			
			if($battleInfo["user_1"] == $_SESSION["id"]) {
				
				$info = $battleInfo["info_1"];
				
			} else {
				
				$info = $battleInfo["info_2"];
				
			}
			
			$info = Info::_unParseData($info);
			
			if(isset($info["pokeLIst"]["p".$id])) {
				
				$pok = $info["pokeLIst"]["p".$id];
				
				if(is_string($pok["stats"])) $pok['stats'] = explode(",", $pok['stats']);
				if(is_string($pok["evcounts"])) $pok['evcounts'] = explode(",", $pok['evcounts']);
				if(is_string($pok["gen"])) $pok['gen'] = explode(",", $pok['gen']);
				
				$pok['stats'][1] = $this->_getStatAtk($pok, $pok["stats"][1]);
				$pok['stats'][2] = $this->_getStatDef($pok, $pok["stats"][2]);
				$pok['stats'][3] = $this->_getStatSpd($pok, $pok["stats"][3]);
				$pok['stats'][4] = $this->_getStatSAtk($pok, $pok["stats"][4]);
				$pok['stats'][5] = $this->_getStatSDef($pok, $pok["stats"][5]);
				
				$pok['stats'] = join(",", $pok['stats']);
				$pok['evcounts'] = join(",", $pok['evcounts']);
				$pok['gen'] = join(",", $pok['gen']);
				
				$pp = explode(',', $pok['pp_my']);
				
				//echo var_dump($pok);
				
				//die(var_dump($pok));
				
			} else {
				
				//небоеспособный
				
				$sxx = Work::$sql->prepare('SELECT * FROM `user_pokemons` WHERE id = ?');
                    $sxx->bind_param("i", $id);
                    $sxx->execute();
                    $dxx = $sxx->get_result();
                    $pok = $dxx->fetch_assoc();
                    
				$pp = explode(',', $pok['pp_attacks']);
				
			}
			
		}
		
	} else {

		stat_updates($id);
		
		$stmt = Work::$sql->prepare('SELECT * FROM user_pokemons WHERE id = ?');
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result();
            $pok = $data->fetch_assoc();
            
		$pp = explode(',', $pok['pp_attacks']);

	}
	
    $stmtTrade = Work::$sql->prepare('SELECT user1, user2 FROM users_trade WHERE (user1 = ? OR user2 = ?)');
                    $stmtTrade->bind_param("ii", $this->userInfo['id'], $this->userInfo['id']);
                    $stmtTrade->execute();
                    $dataTrade = $stmtTrade->get_result();
                    $trade = $dataTrade->fetch_assoc();
    
    if(isset($pok)) {
      $stmtauk = Work::$sql->prepare('SELECT id FROM auk WHERE id_bet = ? AND type = ?');
      $a = "pokemon";
                    $stmtauk->bind_param("is", $pok['id'], $a);
                    $stmtauk->execute();
                    $dataauk = $stmtauk->get_result();
                    $auk = $dataauk->fetch_assoc();
                    
      if(isset($trade) && in_array($pok['intrade_user'], [$trade['user1'],$trade['user2']]) || isset($auk) || $pok['active'] == 0 && $pok['user_id'] == $this->userInfo['id'] && Info::getLocation(['id',$this->userInfo['location'],'pit']) == 1 || $pok['active'] == 1 && $pok['user_id'] == $this->userInfo['id']) {
        $stat = explode(',',$pok['stats']);
        $evcounts = explode(',',$pok['evcounts']);
        $atk = explode(',',$pok['attacks']);
        $birthdayJson = json_decode($pok['birthday']);
        $str = ($pok['item_str'] == NULL ? 0 : explode(',',$pok['item_str']));
		
		$atks = Pokemons::getInfoAboutAtks($atk, $pp, $pok["gen"]);
		
		if($pok["realMOwner"] > 0) {
			
			$stmtRT = Work::$sql->prepare('SELECT time FROM matsuka_rent WHERE type = ? AND objectID = ?');
            $zero = 0;
                    $stmtRT->bind_param("ii", $zero, $pok["id"]);
                    $stmtRT->execute();
                    $dataRT = $stmtRT->get_result();
                    $rentTime = $dataRT->fetch_assoc()["time"];
			
		}
		
		//die(var_dump($pok));
		
        return array_merge([
		  'koren' => $pok['koren'],
          'evolstone' => $pok['evolstone'],
          'hiddenpower' => Pokemons::hiddenPower(explode(',',$pok['gen'])),
          'cosmic' => $pok['cosmicGen'],
          'form' => $pok['form'],
          'str' => ($str == 0 ? 0 : $str[0].'/'.$str[1]),
          'tren' => $pok['tren'],
          'type' => $pok['type'],
          'basenum' => Info::getNumPokemonNum($pok['basenum']),
          'num' => $pok['basenum'],
          'ball' => $pok['ball'],
          'lvl' => $pok['lvl'],
          'name' => $pok['name_new'],
          'sparka' => $pok['sparka'],
          'gender' => ($pok['gender'] == 'Мальчик' ? 'mars' : ($pok['gender'] == 'Девочка' ? 'venus' : 'genderless')),
          'hp' => $pok['hp'],
          'stat' => $stat,
          'hp_p' => ($pok['hp'] / $stat[0] * 100),
          'exp' => ($pok['lvl'] >= 100 ? 100 : $pok['exp']),
          'exp_max' => ($pok['lvl'] >= 100 ? 100 : $pok['exp_max']),
          'exp_p' => ($pok['lvl'] >= 100 ? 100 : $pok['exp'] / $pok['exp_max'] * 100),
          'happy' => $pok['happy'],
          'happy_p' => ($pok['happy'] / 255 * 100),
          'id' => $pok['id'],
          'trade' => ($pok['trade'] == 'false' ? 'No' : 'Yes'),
          'char' => $pok['character'],
          'gen' => Info::getGenEgg($pok['gen']),
          'vitamines' => $pok['vitamines'],
          'sparkaNumber' => $pok['sparkaNumber'],
          'birthday' => date('d.m.Y',$birthdayJson->date),
          'user' => Info::getMainUser(['id',$birthdayJson->user_id]),
          'evcounts' => $evcounts,
          'stat_color' => array("",color_char($pok['character'],1),color_char($pok['character'],2),color_char($pok['character'],3),color_char($pok['character'],4),color_char($pok['character'],5)),
          'tren_stat' => $pok['tren_stat'],
          'ev' => $pok['ev'],
          'start' => $pok['start_pok'],
          'item_id' => $pok['item_id'],
          'my' => ($pok['active'] == 1 && $pok['user_id'] == $this->userInfo['id'] ? 1 : 0),
		  'statMod'   => (isset($pok['modified']) ? $pok['modified'] : []),
		  'statusList'=> (isset($pok['status_list']) ? $pok['status_list'] : []),
		  
		  //TODO: only group and login
		  "owner" => $pok["realMOwner"] > 0 ? Info::getMainUser(["id", $pok["realMOwner"]]) : 0,
		  "rentTime" => isset($rentTime) ? $rentTime : false,
		  
        ], $atks);
      }
    }
  }

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;
      $this->userInfo =& $userInfo;

      switch($_POST['type']){

        case 'trashGet':
          if($userInfo['status'] == 'free') {
            if(Info::getLocation(['id',$userInfo['location'],'pit']) == 1) {
                
              $stmt = Work::$sql->prepare('SELECT * FROM user_pokemons WHERE id = ?');
                    $stmt->bind_param("i", $val);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
                    
              if(isset($pok)) {
                if($userInfo['id'] == $pok['loose']) {
                  if(item_isset(1,10000)) {
                    $error = 5;
                    $sql = "UPDATE user_pokemons SET loose = ?, loose_time = ?, user_id = ? WHERE id = ?";
                    $a = 0;
                        $stmtd = Work::$sql->prepare($sql);
                        $stmtd->bind_param("iiii", $a, $a, $userInfo['id'], $val);
                        $stmtd->execute();
                    minus_item(1,10000);
                    $minus = Items::arrayItem([1,[10000,1]]);
                  }else{
                    $error = 4;
                  }
                }else{
                  $error = 3;
                }
              }else{
                $error = 3;
              }
            }else{
              $error = 2;
            }
          }else{
            $error = 1;
          }
          $this->response['response'] = array(
            'error' => $error,
            'minus' => (isset($minus) ? $minus : 0)
          );
        break;
		
		case 'returnPokemon':
		
			if($userInfo['status'] != 'free') {
				
				$this->response['error'] = "Вы заняты.";
				return;
				
			}
			
			if(Info::getLocation(['id',$userInfo['location'],'pit']) != 1) {
				
				$this->response['error'] = "Поблизости нет питомников.";
				return;
				
			}
			
			$stmt = Work::$sql->prepare('SELECT realMOwner FROM user_pokemons WHERE id = ?');
                    $stmt->bind_param("i", $val);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();

			$stmtX = Work::$sql->prepare('SELECT time FROM matsuka_rent WHERE type = ? AND objectID = ?');
			$zero = 0;
                    $stmtX->bind_param("ii", $zero, $val);
                    $stmtX->execute();
                    $dataX = $stmtX->get_result();
                    $rentInfo = $dataX->fetch_assoc();
			
			if(!isset($pok) || $pok["realMOwner"] != $userInfo["id"] || !isset($rentInfo)) {
				
				$this->response['error'] = "Вы не имеете доступа к этому покемону.";
				return;
				
			}
			
			if($rentInfo["time"] > time()) {
				
				$this->response['error'] = "Срок аренды ещё не истёк.";
				return;
				
			}
			
			$stmtU = Work::$sql->prepare("UPDATE user_pokemons SET realMOwner = ?, start_pok = ?, active = ?, user_id = ? WHERE id = ?");
                        $stmtU->bind_param("iiiii", $zero, $zero, $zero, $userInfo['id'], $val);
                        $stmtU->execute();
                        
			$stmtD = Work::$sql->prepare("DELETE FROM matsuka_rent WHERE type = ? AND objectID = ?");
                        $stmtU->bind_param("ii", $zero, $val);
                        $stmtU->execute();
			
			$this->response['text'] = "Покемон возвращён!";
		
		break;

        case 'trashOpen':
          $stmt = Work::$sql->prepare('SELECT * FROM user_pokemons WHERE loose = ?');
                    $stmt->bind_param("i", $userInfo['id']);
                    $stmt->execute();
                    $poks = $stmt->get_result();
          $ListPok = [];
          while($pok = $poks->fetch_assoc()){
            $ListPok[$pok['id']] = array(
              'id' => $pok['id'],
              'basenum' => Info::getNumPokemonNum($pok['basenum']),
              'type' => $pok['type'],
              'lvl' => $pok['lvl'],
              'gen' => Info::getGenEgg($pok['gen']),
              'gender' => ($pok['gender'] == 'Мальчик' ? 'mars' : ($pok['gender'] == 'Девочка' ? 'venus' : 'genderless')),
              'sparka' => $pok['sparka'],
              'sparkaNumber' => $pok['sparkaNumber'],
              'num' => $pok['basenum'],
              'name' => $pok['name_new'],
              'item' => ($pok['item_id'] == 0 ? 0 : Info::getItemBase(['id',$pok['item_id'],'name'])),
              'tren' => $pok['tren'],
              'har' => $pok['character']
            );
          }
          $this->response['response'] = array(
            'PokList' => array_reverse($ListPok)
          );
        break;

        /*case 'checkAbility':
          $PokemonList = [];
          $poks = Work::$sql->query("SELECT * FROM base_pokemon_ability WHERE (slot1 = ".intval($val).") OR (slot2 = ".intval($val).") OR (hidden = ".intval($val).")");
          while($pok = $poks->fetch_assoc()) {
            $PokemonList[$pok['id']] = array(
              'basenum' => Info::getNumPokemonNum($pok['pok']),
              'num' => $pok['pok'],
              'name' => Info::getPokemonBase(['id',$pok['pok'],'name_rus'])
            );
          }
          $this->response['response'] = array(
            'pokemon_list' => $PokemonList
          );
        break;*/

        case 'open':
          $PokemonList = [];
          $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ?");
          $a = 1;
                    $stmt->bind_param("ii", $userInfo['id'], $a);
                    $stmt->execute();
                    $poks = $stmt->get_result();
          while($pok = $poks->fetch_assoc()) {
            //$stat = explode(',',$pok['stats']);
            $PokemonList[$pok['id']] = array(
              'pok' => $this->getMainPokemon($pok['id'])
            );
          }
          $this->response['response'] = array(
            'pokemon_list' => $PokemonList
          );
        break;
		
		case 'viewPokemonsThatCanReturn':
		
			$PokemonList = [];
			
			$stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE realMOwner = ?");
                    $stmt->bind_param("i", $userInfo['id']);
                    $stmt->execute();
                    $poks = $stmt->get_result();
			
			while($pok = $poks->fetch_assoc()) {
				
				$stmtrI = Work::$sql->prepare('SELECT time FROM matsuka_rent WHERE type = ? AND objectID = ?');
                $zero = 0;
                    $stmtrI->bind_param("ii", $zero, $pok['id']);
                    $stmtrI->execute();
                    $datarI = $stmtrI->get_result();
                    $rentInfo = $datarI->fetch_assoc();
				
				$PokemonList[$pok['id']] = array(
					'id' => $pok['id'],
					'basenum' => Info::getNumPokemonNum($pok['basenum']),
					'type' => $pok['type'],
					'lvl' => $pok['lvl'],
					'gen' => Info::getGenEgg($pok['gen']),
					'gender' => ($pok['gender'] == 'Мальчик' ? 'mars' : ($pok['gender'] == 'Девочка' ? 'venus' : 'genderless')),
					'sparka' => $pok['sparka'],
					'sparkaNumber' => $pok['sparkaNumber'],
					'num' => $pok['basenum'],
					'name' => $pok['name_new'],
					'item' => ($pok['item_id'] == 0 ? 0 : Info::getItemBase(['id',$pok['item_id'],'name'])),
					'tren' => $pok['tren'],
					'har' => $pok['character'],
					
					'time' => $rentInfo["time"],
					'slave' => Info::getMainUser(["id", $pok["user_id"]]),
					
				);
				
			}
			
			$this->response = [
				'objects' => $PokemonList
			];
			
		break;

        case 'action':
          switch($val[0]) {
            case 'ev':
              if($userInfo['status'] == 'free') {
                $stmt = Work::$sql->prepare('SELECT id,evcounts,ev,realMOwner FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
                $a = 1;
                    $stmt->bind_param("iii", $userInfo['id'], $a, $val[1][0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
                if(isset($pok)) {
					
					if($pok["realMOwner"] > 0) {
						
						$this->response['response'] = ['error' => 18];
						return;
						
					}
					
                  $ev = explode(',',$pok['evcounts']);
                  $count = intval($val[1][2]);
                  $count = ($count <= 0 ? 1 : $count);
                  if($val[1][1] >= 0 && $val[1][1] <= 5) {
                    $plus = $ev[$val[1][1]] + $count;
                    if($pok['ev'] >= $count) {
                      if($plus > 126) {
                        $error = 15;
                      }else{
                        $ev[$val[1][1]] = $plus;
                        $minus = $pok['ev'] - $count;
                        $ev = implode(",",$ev);
                        
                        $sql = "UPDATE user_pokemons SET evcounts = ?, ev = ? WHERE id = ?";
                            $stmtQ = Work::$sql->prepare($sql);
                            $stmtQ->bind_param("sii", $ev, $minus, $pok['id']);
                            $stmtQ->execute();
                            
                        $error = 16;
                      }
                    }else{
                      $error = 14;
                    }
                  }
                }
              }else{
                $error = 1;
              }
            break;
            case 'remove':
			
				if($userInfo['status'] == 'free' && $userInfo["location"] != 8009) {
					
					$stmt = Work::$sql->prepare('SELECT id,item_id,item_str FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
					$a = 1;
                        $stmt->bind_param("iii", $_SESSION["id"], $a, $val[1][0]);
                        $stmt->execute();
                        $data = $stmt->get_result();
                        $pok = $data->fetch_assoc();
					
					if(isset($pok)) {
						
						if($pok['item_id'] != 0) {

							if($pok['item_id'] != 10003) {
								
								$stmtSS = Work::$sql->prepare("SELECT time FROM matsuka_time_pok_items WHERE pok = ?");
                                        $stmtSS->bind_param("i", $pok["id"]);
                                        $stmtSS->execute();
                                        $dataSS = $stmtSS->get_result();
                                        $isTimeItem = $dataSS->fetch_assoc();
								
								$stmtRI = Work::$sql->prepare('SELECT connection FROM matsuka_rentItemOnPok WHERE pok = ?');
                                        $stmtRI->bind_param("i", $pok["id"]);
                                        $stmtRI->execute();
                                        $dataRI = $stmtRI->get_result();
                                        $isRentItem = $dataRI->fetch_assoc();
							
								if(isset($isTimeItem)) {
									
									$q = Work::$sql->prepare("DELETE FROM matsuka_time_pok_items WHERE pok = ?"); 
                                        if($q)
                                        {
                                            $q->bind_param("i", $pok["id"]);
                                            $q->execute();
                                           $q->close();
                                        }
									
									if($isTimeItem["time"] > time()) {
										
										$stmtX = Work::$sql->prepare("INSERT INTO items_users (user,item_id,count,str,crash_activate,crash_time,split) VALUES (?,?,?,?,?,?,?)"); 
										$a = 1;
                                            $stmtX->bind_param("iiisiii", $_SESSION['id'], $pok["item_id"], $a, $pok["item_str"], $a, $isTimeItem["time"], $a);
                                            $stmtX->execute();
										
										if(isset($isRentItem)) {
											
											$ll = Work::$sql->insert_id;
											$stmtc = Work::$sql->prepare("UPDATE matsuka_rent SET objectID = ? WHERE id = ?"); 
                                                $stmtc->bind_param("ii", $ll, $isRentItem["connection"]);
                                                $stmtc->execute();
											$stmtD = Work::$sql->prepare("DELETE FROM matsuka_rentItemOnPok WHERE connection = ?"); 
                                                $stmtD->bind_param("s", $isRentItem["connection"]);
                                                $stmtD->execute();
											
										}
										
									}
									
								} else if($pok['item_str'] != NULL) {
									
									$stmtX = Work::$sql->prepare("INSERT INTO items_users (user,item_id,count,str,split) VALUES (?,?,?,?,?) "); 
										$a = 1;
                                            $stmtX->bind_param("iiisi", $_SESSION['id'], $pok["item_id"], $a, $pok["item_str"], $a);
                                            $stmtX->execute();
									
									if(isset($isRentItem)) {
										
										$stmtc = Work::$sql->prepare("UPDATE matsuka_rent SET objectID = ? WHERE id = ?"); 
                                                $stmtc->bind_param("ii", $ll, $isRentItem["connection"]);
                                                $stmtc->execute();
											$stmtD = Work::$sql->prepare("DELETE FROM matsuka_rentItemOnPok WHERE connection = ?"); 
                                                $stmtD->bind_param("s", $isRentItem["connection"]);
                                                $stmtD->execute();
										
									}
									
								} else {
									
									itemAdd($pok['item_id'], 1);
									
								}

							}

							$sqlc = "UPDATE user_pokemons SET item_id = ?, item_str = ? WHERE id = ?";
							$a = '';
							$b = '';
                                $stmtc = Work::$sql->prepare($sqlc);
                                $stmtc->bind_param("isi", $a, $b, $pok['id']);
                                $stmtc->execute();
							$error = 13;

						}
						
					}
					
				} else {
					
					$error = 1;
					
				}
				
            break;
            case 'start':
              if($userInfo['status'] == 'free') {
                $stmt = Work::$sql->prepare('SELECT id FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
                $a = 1;
                    $stmt->bind_param("iii", $userInfo['id'], $a, $val[1][0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
                if(isset($pok)) {
                  $a = 0;
                  $b = 1;
                        $stmtC = Work::$sql->prepare('UPDATE user_pokemons SET start_pok = ? WHERE start_pok = ? AND user_id = ?');
                        $stmtC->bind_param("iii", $a, $b, $this->userInfo['id']);
                        $stmtC->execute();

                  $a = 1;
                        $stmtx = Work::$sql->prepare('UPDATE user_pokemons SET start_pok = ? WHERE id = ?');
                        $stmtx->bind_param("ii", $a, $pok['id']);
                        $stmtx->execute();
                  $error = 12;
                }
              }else{
                $error = 1;
              }
            break;
            case 'delete':
              if($userInfo['status'] == 'free') {
                $stmt = Work::$sql->prepare('SELECT id,trade,realMOwner FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
                $a = 1;
                    $stmt->bind_param("iii", $userInfo['id'], $a, $val[1][0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
                if(isset($pok)) {
					
					if($pok["realMOwner"] > 0) {
						
						$this->response['response'] = ['error' => 18];
						return;
						
					}
					
                  if($pok['trade'] != 'true') {
                    $error = 10;
                  }else{
                    $stmtA = Work::$sql->prepare('SELECT id FROM user_pokemons WHERE user_id = ? AND active = ?');
                        $a = 1;
                            $stmtA->bind_param("ii", $userInfo['id'], $a);
                            $stmtA->execute();
                            $poks = $stmtA->get_result();
                    if($poks->num_rows <= 1) {
                      $error = 9;
                    }else{
                      $error = 11;
                      /*$battlepass_quest1 = Work::$sql->query("SELECT * FROM battlepass_quests WHERE user = ".intval($_SESSION['id'])." AND quest_id = 1")->fetch_assoc();
                      if(isset($battlepass_quest1)) {
                        $quest_1 = explode(',',$battlepass_quest1['val']);
                        if($quest_1[0] == 'ability') {
                          if($pok['ability'] == $quest_1[1]) {
                            Work::$sql->query("DELETE FROM battlepass_quests WHERE id = ".intval($battlepass_quest1['id']));
                            Battlepass::getExp(500);
                          }
                        }
                      }
                      $battlepass_quest2 = Work::$sql->query("SELECT * FROM battlepass_quests WHERE user = ".intval($_SESSION['id'])." AND quest_id = 2")->fetch_assoc();
                      if(isset($battlepass_quest2)) {
                        $quest_2 = explode(',',$battlepass_quest2['val']);
                        if($quest_2[0] == 'ability') {
                          if($pok['ability'] == $quest_2[1]) {
                            Work::$sql->query("DELETE FROM battlepass_quests WHERE id = ".intval($battlepass_quest2['id']));
                            Battlepass::getExp(500);
                          }
                        }
                      }
                      $battlepass_quest3 = Work::$sql->query("SELECT * FROM battlepass_quests WHERE user = ".intval($_SESSION['id'])." AND quest_id = 3")->fetch_assoc();
                      if(isset($battlepass_quest3)) {
                        $quest_3 = explode(',',$battlepass_quest3['val']);
                        if($quest_3[0] == 'ability') {
                          if($pok['ability'] == $quest_3[1]) {
                            Work::$sql->query("DELETE FROM battlepass_quests WHERE id = ".intval($battlepass_quest3['id']));
                            Battlepass::getExp(500);
                          }
                        }
                      }*/
					  
					  if($userInfo['location'] == 8000) {
						  
						  $q = Work::$sql->prepare('DELETE FROM user_pokemons WHERE id = ?'); 
                                    $q->bind_param("i", $pok['id']);
                                    $q->execute();
						  
					  } else {
					  
						Work::$sql->query('UPDATE user_pokemons SET user_id = 2, active = 0, loose = '.intval($_SESSION['id']).', loose_time = '.(time() + 864000).' WHERE id = '.intval($pok['id']));
						$a = 2;
						$b = 0;
						$c = (time() + 604800);
                        $stmt = Work::$sql->prepare("UPDATE user_pokemons SET user_id = ?, active = ?, loose = ?, loose_time = ? WHERE id = ?");
                                $stmt->bind_param("iiiii", $a, $b, $_SESSION['id'], $c, $pok['id']);
                                $stmt->execute();
					  
					  }
                    }
                  }
                }
              }else{
                $error = 1;
              }
            break;
            case 'take':
              if($userInfo['status'] == 'free' && Info::getLocation(['id',$userInfo['location'],'pit']) == 1) {
                $stmt = Work::$sql->prepare('SELECT id FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
                $a = 0;
                    $stmt->bind_param("iii", $userInfo['id'], $a, $val[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
                if(isset($pok)) {
                  $stmta = Work::$sql->prepare('SELECT id FROM user_pokemons WHERE user_id = ? AND active = ?');
                    $a = 1;
                        $stmta->bind_param("ii", $userInfo['id'], $a);
                        $stmta->execute();
                        $poks = $stmta->get_result();
                  if($poks->num_rows >= 6) {
                    $error = 2;
                  }else{
                    $sql = 'UPDATE user_pokemons SET active = ? WHERE id = ?';
                    $a = 1;
                        $stmts = Work::$sql->prepare($sql);
                        $stmts->bind_param("ii", $a, $pok['id']);
                        $stmts->execute();
                    $error = 3;
                  }
                }
              }else{
                $error = 1;
              }
            break;
            case 'rename':
              $stmt = Work::$sql->prepare('SELECT id,basenum,realMOwner FROM user_pokemons WHERE user_id = ? AND id = ?');
                    $stmt->bind_param("ii", $userInfo['id'], $val[1][0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
              if(isset($pok)) {
				  
				if($pok["realMOwner"] > 0) {

					$this->response['response'] = ['error' => 18];
					return;

				}
				  
                $error = 4;
                $name = preg_replace('/[^ a-zа-яё\d]/ui', '',$val[1][1]);
                $name = substr($name,0,30);
                $name = ($name == '' ? Info::getPokemonBase(['id',$pok['basenum'],'name_rus']) : $name);
                $stmt = Work::$sql->prepare('UPDATE user_pokemons SET name_new = ? WHERE id = ?');
                    $name = htmlspecialchars($name);
                    $stmt->bind_param("si", $name, $pok['id']);
                    $stmt->execute();
              }
            break;
            // case 'abil':
            //   $pok = Work::$sql->query('SELECT id,basenum,ability,ability_slot FROM user_pokemons WHERE user_id = '.$userInfo['id'].' AND id = '.$val[1][0])->fetch_assoc();
            //   if(isset($pok)) {
            //     $abil = Info::generateAbility($pok['basenum']);
            //     Work::$sql->query('UPDATE user_pokemons SET ability = '.$abil[0].', ability_slot = '.$abil[1].' WHERE id = '.$pok['id']);
            //     $error = 17;
            //   }else{
            //     $error = 5;
            //   }
            // break;
            case 'went':
               $stmt = Work::$sql->prepare('SELECT id,lastWent,happy,realMOwner FROM user_pokemons WHERE user_id = ? AND id = ?');
                    $stmt->bind_param("ii", $userInfo['id'], $val[1][0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
              if(isset($pok) && time() > $pok['lastWent']) {
				  
				if($pok["realMOwner"] > 0) {

					$this->response['response'] = ['error' => 18];
					return;

				}
				  
                $error = 6;
                $happy = (($pok['happy'] + 10) >= 255 ? 255 : ($pok['happy'] + 10));
                $a = (time() + 86400);
                $stmt = Work::$sql->prepare('UPDATE user_pokemons SET happy = ?, lastWent = ? WHERE id = ?');
                    $stmt->bind_param("iii", $happy, $a, $pok['id']);
                    $stmt->execute();
              }else{
                $error = 5;
              }
            break;
            case 'gopit':
              if($userInfo['status'] == 'free') {
                $stmt = Work::$sql->prepare('SELECT id,happy FROM user_pokemons WHERE user_id = ? AND active = ? AND id = ?');
                $a = 1;
                    $stmt->bind_param("iii", $userInfo['id'], $a, $val[1][0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pok = $data->fetch_assoc();
                    
                if(isset($pok)) {
					//757 - революционный ивент лока
                  if(Info::getLocation(['id',$this->userInfo['location'],'pit']) == 1 && $userInfo["location"] != 757 && $userInfo["location"] != 8009) {
                    $stmts = Work::$sql->prepare('SELECT id FROM user_pokemons WHERE user_id = ? AND active = ?');
                    $a = 1;
                        $stmts->bind_param("ii", $userInfo['id'], $a);
                        $stmts->execute();
                        $poks = $stmts->get_result();
                    if($poks->num_rows <= 1) {
                      $error = 9;
                    }else{
					  
					  $happy = (($pok['happy'] - 5) <= 0 ? 0 : ($pok['happy'] - 5));
                      $sql = 'UPDATE user_pokemons SET happy = ?, active = ? WHERE id = ?';
                      $a = 0;
                            $stmt = Work::$sql->prepare($sql);
                            $stmt->bind_param("iii", $happy, $a, $pok['id']);
                            $stmt->execute();
                      $error = 8;
                    }
                  }else{
                    $error = 7;
                  }
                }
              }else{
                $error = 1;
              }
            break;
			
			case 'gopitall':
				
				if($userInfo['status'] != 'free') {
					
					$this->response['response'] = ["error" => 1];
					return;
					
				}
				
				if(Info::getLocation(['id',$this->userInfo['location'],'pit']) != 1 || $userInfo["location"] == 757 || $userInfo["location"] == 8009) {
					
					$this->response['response'] = ["error" => 7];
					return;
					
				}
				
				include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/constants/global.php');
				
				$happy = \matsukaConstants\reduceHappyValueIfGotPitCommand;
                      $sql = 'UPDATE user_pokemons SET happy = happy - ?, active = ?, start_pok = ? WHERE id != ? AND user_id = ? AND active = ?';
                      $one = 1;
                      $a = 0;
                            $stmt = Work::$sql->prepare($sql);
                            $stmt->bind_param("iii", $happy, $a, $a, $val[1][0], $_SESSION["id"], $one);
                            $stmt->execute();
				
				$error = 19;
				
			break;
			
          }
          $this->response['response'] = array(
            'error' => $error
          );
        break;

        case 'pit':
          if($val != 2) {
            if(isset($val[0])) {
              $name = $val[0];
            }else{
              $name = '';
            }
            if(isset($val[1])) {
              if($val[1] == 1) {
                $sort1 = 'ORDER BY lvl DESC';
              }elseif($val[1] == 2){
                $sort1 = 'ORDER BY basenum DESC';
              }else{
                $sort1 = '';
              }
            }else{
              $sort1 = '';
            }
            if(isset($val[2])) {
              if($val[2] == 1) {
                $sort2 = 'Мальчик';
              }elseif($val[2] == 2) {
                $sort2 = 'Девочка';
              }elseif($val[2] == 3){
                $sort2 = 'Бесполый';
              }else{
                $sort2 = '';
              }
            }else{
              $sort2 = '';
            }
            if(isset($val[3])) {
              if($val[3] == 1) {
                $sort3 = '1';
                $sort4 = '';
              }elseif($val[3] == 2) {
                $sort3 = '0';
                $sort4 = '';
              }elseif($val[3] == 3) {
                $sort3 = '0';
                $sort4 = '1';
              }elseif($val[3] == 4) {
                $sort3 = '0';
                $sort4 = '2';
              }elseif($val[3] == 5) {
                $sort3 = '0';
                $sort4 = '3';
              }else{
                $sort3 = '';
                $sort4 = '';
              }
            }else{
              $sort3 = '';
            }
			if(isset($val[6])) {
              if($val[6] == 'обычный') {
                $sort5 = 'normal';
			}elseif($val[6] == '0'){
                $sort5 = '';
              }else{
                $sort5 = $val[6];
              }
            }else{
              $sort5 = '';
            }
            if(isset($val[5]) && $val[5] > 0) {
              if(isset($val[4]) && $val[4] != 0) {
                
                $stmtXX = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? 
                AND active = ? AND ? LIKE ? AND gender LIKE ? AND type LIKE ? AND sparka LIKE ? 
                AND sparkaNumber LIKE ? AND `character` = ? AND `lvl` = ? ".$sort1);
                    $a = 0; 
                    $a1 = is_numeric($name) ? 'basenum' : 'name_new';
                    $b = "%$name%";
                    $c = "%$sort2%";
                    $d = "%$sort5%";
                    $e = "%$sort3%";
                    $A = "%$sort4%";
                    
                    $stmtXX->bind_param("iissssssii", $userInfo['id'], $a, $a1, $b, $c, $d, $e, $A, $val[4], $val[5]);
                    $stmtXX->execute();
                    $poks = $stmtXX->get_result();
              }else{
                
                $stmtXX = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND ? LIKE ?
                AND gender LIKE ? AND type LIKE ? AND sparka LIKE ? AND sparkaNumber LIKE ? AND `lvl` = ? ".$sort1);
                    $a = 0; 
                    $a1 = is_numeric($name) ? 'basenum' : 'name_new';
                    $b = "%$name%";
                    $c = "%$sort2%";
                    $d = "%$sort5%";
                    $e = "%$sort3%";
                    $A = "%$sort4%";
                    
                    $stmtXX->bind_param("iissssssi", $userInfo['id'], $a, $a1, $b, $c, $d, $e, $A, $val[5]);
                    $stmtXX->execute();
                    $poks = $stmtXX->get_result();
              }
            }else{
              if(isset($val[4]) && $val[4] != 0) {

                 $stmtXX = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND ".(is_numeric($name) ? 'basenum' : 'name_new')." 
                LIKE ? AND gender LIKE ? AND type LIKE ? AND sparka LIKE ? AND sparkaNumber LIKE ? AND `character` = ? ".$sort1);
                     $a = 0; 
                $A = "%$name%";
                $B = "%$sort2%";
                $C = "%$sort5%";
                $D = "%$sort3%";
                $E = "%$sort4%";

                     $stmtXX->bind_param("iisssssi", $userInfo['id'], $a, $A, $B, $C, $D, $E, intval($val[4]));
                     $stmtXX->execute();
                     $poks = $stmtXX->get_result();
              }else{

                     $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND ".(is_numeric($name) ? 'basenum' : 'name_new')." 
                                LIKE ? AND gender LIKE ? AND type LIKE ? AND sparka LIKE ? AND sparkaNumber LIKE ? ".$sort1);
                                $A = "%$name%";
                                $B = "%$sort2%";
                                $C = "%$sort5%";
                                $D = "%$sort3%";
                                $E = "%$sort4%";
                            $stmt->bind_param("iisssss", $userInfo['id'], $a, $A, $B, $C, $D, $E);
                            $a = 0;
                            $stmt->execute();
                            $poks = $stmt->get_result();


              }
            }
          }else{
            $a = 0;
            $stmtXX = Work::$sql->prepare('SELECT * FROM user_pokemons WHERE user_id = ? AND active = ?');
                    $stmtXX->bind_param("ii", $userInfo['id'], $a);
                    $stmtXX->execute();
                    $poks = $stmtXX->get_result();
          }
          $ListPok = [];
          while($pok = $poks->fetch_assoc()){
            $ListPok[$pok['id']] = array(
              'id' => $pok['id'],
              'basenum' => Info::getNumPokemonNum($pok['basenum']),
              'type' => $pok['type'],
              'lvl' => $pok['lvl'],
              'gen' => Info::getGenEgg($pok['gen']),
              'gender' => ($pok['gender'] == 'Мальчик' ? 'mars' : ($pok['gender'] == 'Девочка' ? 'venus' : 'genderless')),
              'sparka' => $pok['sparka'],
              'sparkaNumber' => $pok['sparkaNumber'],
              'num' => $pok['basenum'],
              'name' => $pok['name_new'],
              'item' => ($pok['item_id'] == 0 ? 0 : Info::getItemBase(['id',$pok['item_id'],'name'])),
              'tren' => $pok['tren'],
              'har' => $pok['character']
            );
          }
          $this->response['response'] = array(
            'PokList' => array_reverse($ListPok)
          );
        break;

        case 'view':
          $this->response['response'] = $this->getMainPokemon($val);
        break;

      }

    }

  }

}

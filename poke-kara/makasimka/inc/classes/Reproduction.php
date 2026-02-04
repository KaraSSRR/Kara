<?php

Class Reproduction {

  private $response = [];
  private $userInfo = [];
  private $pok1 = [];
  private $pok2 = [];
  private $reproductionType = 1;
  private $infoEgg = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){
        case 'go':
          if($userInfo['status'] == 'free') {
            if(in_array(0,[$val[0],$val[1]])) {
              $error = 3;
            }else{
              if(Info::getLocation(['id',$userInfo['location'],'pit']) == 1) {
                $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND sparka = ? AND id = ?");
                $a = 1;
                $A = 0;
                    $stmt->bind_param("iiii", $userInfo['id'], $a, $A, $val[0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $this->pok1 = $data->fetch_assoc();
                    
                $stmt1 = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND sparka = ? AND id = ?");
                    $stmt1->bind_param("iiii", intval($userInfo['id']), $a, $A, intval($val[1]));
                    $stmt1->execute();
                    $data1 = $stmt1->get_result();
                    $this->pok2 = $data1->fetch_assoc();
                    
                if(isset($this->pok1) && isset($this->pok2)) {
					
					if($this->pok1["realMOwner"] > 0 || $this->pok2["realMOwner"] > 0) {

						$this->response['response'] = [
						
							'error' => 11,
							'minus' => 0,
							'plus' => 0,
							
						];
						return;

					}
					
                  if($this->pok1['sparkaNumber'] != $this->pok2['sparkaNumber']) {
                    $error = 5;
                  }else{
                    if(in_array(1,[$this->pok1['sparka'],$this->pok2['sparka']])) {
                      $error = 6;
                    }else{
                      if($this->pok1['gender'] == $this->pok2['gender']) {
                        if($this->pok1['gender'] != 'Бесполый' || $this->pok2['gender'] != 'Бесполый') {
                          $error = 7;
                        }else{
                          if($this->pok1['basenum'] != $this->pok2['basenum']) {
                            $error = 8;
                          }else{
                            $this->infoEgg['eggBasenum'] = (mt_rand(1, 2) == 2 ? $this->pok1['basenum'] : $this->pok2['basenum']);
                            $this->reproductionType = 3;
                            $result = $this->result($userInfo);
                            $error = $result[0];
                            $plus = $result[1];
                            $minus = $result[2];
                          }
                        }
                      }else{
                        if($this->pok1['gender'] == 'Бесполый' || $this->pok2['gender'] == 'Бесполый') {
                          $error = 7;
                        }else{
                          $err2 = 0;
                          if(($this->pok1['basenum'] == 29 && $this->pok2['basenum'] == 32) || ($this->pok1['basenum'] == 32 && $this->pok2['basenum'] == 29)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 30 && $this->pok2['basenum'] == 33) || ($this->pok1['basenum'] == 33 && $this->pok2['basenum'] == 30)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 31 && $this->pok2['basenum'] == 34) || ($this->pok1['basenum'] == 34 && $this->pok2['basenum'] == 31)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 124 && $this->pok2['basenum'] == 106) || ($this->pok1['basenum'] == 106 && $this->pok2['basenum'] == 124)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 124 && $this->pok2['basenum'] == 107) || ($this->pok1['basenum'] == 107 && $this->pok2['basenum'] == 124)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 128 && $this->pok2['basenum'] == 241) || ($this->pok1['basenum'] == 241 && $this->pok2['basenum'] == 128)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 238 && $this->pok2['basenum'] == 236) || ($this->pok1['basenum'] == 236 && $this->pok2['basenum'] == 238)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 238 && $this->pok2['basenum'] == 237) || ($this->pok1['basenum'] == 237 && $this->pok2['basenum'] == 238)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 313 && $this->pok2['basenum'] == 314) || ($this->pok1['basenum'] == 314 && $this->pok2['basenum'] == 313)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 380 && $this->pok2['basenum'] == 381) || ($this->pok1['basenum'] == 381 && $this->pok2['basenum'] == 380)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 413 && $this->pok2['basenum'] == 414) || ($this->pok1['basenum'] == 414 && $this->pok2['basenum'] == 413)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 627 && $this->pok2['basenum'] == 629) || ($this->pok1['basenum'] == 629 && $this->pok2['basenum'] == 627)){
                              $this->reproductionType = 2;
                          }elseif(($this->pok1['basenum'] == 628 && $this->pok2['basenum'] == 630) || ($this->pok1['basenum'] == 630 && $this->pok2['basenum'] == 628)){
                              $this->reproductionType = 2;
                          }else{
                              if($this->pok1['basenum'] == 616 && $this->pok2['basenum'] == 588 || $this->pok1['basenum'] == 588 && $this->pok2['basenum'] == 616 || $this->pok1['basenum'] == $this->pok2['basenum']) {
                                $stmts = Work::$sql->prepare(("SELECT * FROM base_pokemons WHERE id = ?"));
                                    $stmts->bind_param("i", $this->pok1['basenum']);
                                    $stmts->execute();
                                    $datas = $stmts->get_result();
                                    $sex1 = $datas->fetch_assoc();
                                    
                                $stmts2 = Work::$sql->prepare(("SELECT * FROM base_pokemons WHERE id = ?"));
                                    $stmts2->bind_param("i", $this->pok2['basenum']);
                                    $stmts2->execute();
                                    $datas2 = $stmts2->get_result();
                                    $sex2 = $datas2->fetch_assoc();
                                if($sex1['evol_type'] == 'sex'){
                                    switch($sex1['id']) {
                                      /*case 93:
                                        Work::$sql->query("UPDATE user_pokemons SET ability = 31 WHERE id = '".$this->pok1['id']."'");
                                      break;*/
                                    }
                                    if(!in_array($sex1['id'], [588,616]) || !in_array($sex2['id'], [588,616])) {
                                      $stmtSX = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE id = ?");
                                            $stmtSX->bind_param("i", $sex1['evol_basenum']);
                                            $stmtSX->execute();
                                            $dataSX = $stmtSX->get_result();
                                            $sex1_new = $dataSX->fetch_assoc();
                                      $sql = "UPDATE user_pokemons SET basenum = ?, name_new = ? WHERE id = ?";
                                        $a = 31;
                                            $stmt = Work::$sql->prepare($sql);
                                            $stmt->bind_param("isi", $sex1['evol_basenum'], $sex1_new['name_rus'], $this->pok1['id']);
                                            $stmt->execute();
                                    }else{
                                      if($sex1['id'] == 616 && $sex2['id'] == 588 || $sex1['id'] == 588 && $sex2['id'] == 616) {
                                        $stmtSX = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE id = ?");
                                            $stmtSX->bind_param("i", $sex1['evol_basenum']);
                                            $stmtSX->execute();
                                            $dataSX = $stmtSX->get_result();
                                            $sex1_new = $dataSX->fetch_assoc();
                                            
                                        $sql = "UPDATE user_pokemons SET basenum = ?, name_new = ? WHERE id = ?";
                                        $a = 31;
                                            $stmt = Work::$sql->prepare($sql);
                                            $stmt->bind_param("isi", $sex1['evol_basenum'], $sex1_new['name_rus'], $this->pok1['id']);
                                            $stmt->execute();
                                            
                                        if($sex1['evol_basenum'] == 617) {
                                          /*if($this->pok1['ability_slot'] == 2) {
                                            Work::$sql->query("UPDATE user_pokemons SET ability = 193 WHERE id = '".$this->pok1['id']."'");
                                          }*/
                                        }
                                        if($sex1['evol_basenum'] == 589) {
                                          /*if($this->pok1['ability_slot'] == 2) {
                                            Work::$sql->query("UPDATE user_pokemons SET ability = 170 WHERE id = '".$this->pok1['id']."'");
                                          }*/
                                        }
                                      }
                                    }
                                }
                                if($sex2['evol_type'] == 'sex'){
                                    switch($sex2['id']) {
                                      /*case 93:
                                        Work::$sql->query("UPDATE user_pokemons SET ability = 31 WHERE id = '".$this->pok2['id']."'");
                                      break;*/
                                    }
                                    if(!in_array($sex1['id'], [588,616]) || !in_array($sex2['id'], [588,616])) {
                                      $stmtSX = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE id = ?");
                                            $stmtSX->bind_param("i", $sex2['evol_basenum']);
                                            $stmtSX->execute();
                                            $dataSX = $stmtSX->get_result();
                                            $sex2_new = $dataSX->fetch_assoc();
                                      $sql = "UPDATE user_pokemons SET basenum = ?, name_new = ? WHERE id = ?";
                                        $a = 31;
                                            $stmt = Work::$sql->prepare($sql);
                                            $stmt->bind_param("isi", $sex2['evol_basenum'], $sex2_new['name_rus'], $this->pok2['id']);
                                            $stmt->execute();
                                    }else{
                                      if($sex2['id'] == 616 && $sex1['id'] == 588 || $sex2['id'] == 588 && $sex1['id'] == 616) {
                                        $stmtSX = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE id = ?");
                                            $stmtSX->bind_param("i", $sex2['evol_basenum']);
                                            $stmtSX->execute();
                                            $dataSX = $stmtSX->get_result();
                                            $sex1_new = $dataSX->fetch_assoc();
                                        $sql = "UPDATE user_pokemons SET basenum = ?, name_new = ? WHERE id = ?";
                                        $a = 31;
                                            $stmt = Work::$sql->prepare($sql);
                                            $stmt->bind_param("isi", $sex2['evol_basenum'], $sex2_new['name_rus'], $this->pok2['id']);
                                            $stmt->execute();
                                        if($sex2['evol_basenum'] == 617) {
                                          /*if($this->pok2['ability_slot'] == 2) {
                                            Work::$sql->query("UPDATE user_pokemons SET ability = 193 WHERE id = '".$this->pok2['id']."'");
                                          }*/
                                        }
                                        if($sex2['evol_basenum'] == 589) {
                                          /*if($this->pok2['ability_slot'] == 2) {
                                            Work::$sql->query("UPDATE user_pokemons SET ability = 170 WHERE id = '".$this->pok2['id']."'");
                                          }*/
                                        }
                                      }
                                    }
                                }
                                $this->infoEgg['eggBasenum'] = (random_int(1,100)> 40 ? $sex1['eggBasenum'] : $sex2['eggBasenum']);
                              }else{
                                $err2 = 1;
                                $error = 4;
                              }
                          }
                          if($err2 == 0) {
                            $this->reproductionType = 2;
                            $result = $this->result($userInfo);
                            $error = $result[0];
                            $plus = $result[1];
                            $minus = $result[2];
                          }
                        }
                      }
                    }
                  }
                }else{
                  $error = 4;
                }
              }else{
                $error = 2;
              }
            }
          }else{
            $error = 1;
          }
          $this->response['response'] = array(
            'error' => $error,
            'minus' => (isset($minus) ? (empty($minus) ? 0 : $minus) : 0),
            'plus' => (isset($plus) ? (empty($plus) ? 0 : $plus) : 0)
          );
        break;
        case 'select':
          $listPokemon = [];
          $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND sparka = ?");
          $a = 1;
          $A = 0;
                    $stmt->bind_param("iii", $userInfo['id'], $a, $A);
                    $stmt->execute();
                    $Pokemons = $stmt->get_result();
          while($Pok = $Pokemons->fetch_assoc()) {
            if(!in_array($Pok['id'],[$val[0],$val[1]])) {
              $gen = explode(',',$Pok['gen']);
              $listPokemon[$Pok['id']] = [
                'num' => $Pok['basenum'],
                'basenum' => Info::getNumPokemonNum($Pok['basenum']),
                'type' => $Pok['type'],
                'name' => $Pok['name_new'],
                'lvl' => $Pok['lvl'],
                'id' => $Pok['id'],
                'sparkaNumber' => $Pok['sparkaNumber'],
                'gender' => ($Pok['gender'] == 'Мальчик' ? 'mars' : ($Pok['gender'] == 'Девочка' ? 'venus' : 'genderless')),
                'gen'=> 'h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5]
              ];
            }
          }
          $this->response['response'] = array(
            'list' => (!empty($listPokemon) ? $listPokemon : 0)
          );
        break;

      }

    }

  }

  private function result($userInfo){
    $err = 0;
    $minus = [];
    $plus = [];
    if($this->reproductionType == 2){
        $stmt = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE id = ?");
                    $stmt->bind_param("i", $this->pok1['basenum']);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $sex1 = $data->fetch_assoc();
        $stmt2 = Work::$sql->prepare("SELECT * FROM base_pokemons WHERE id = ?");
                    $stmt2->bind_param("i", $this->pok2['basenum']);
                    $stmt2->execute();
                    $data2 = $stmt2->get_result();
                    $sex2 = $data2->fetch_assoc();
        $this->infoEgg['eggBasenum'] = (random_int(1,100)> 40 ? $sex1['eggBasenum'] : $sex2['eggBasenum']);
    }elseif($this->reproductionType == 3){
        if($this->pok1['item_id'] != 149 && $this->pok2['item_id'] != 149){
          $err = 1;
          return [9];
        }else{
          array_push($minus,Items::arrayItem([149,[2,1]]));
          $sql = "UPDATE user_pokemons SET item_id = ? WHERE id IN (?,?)";
          $a = 0;
                $stmt = Work::$sql->prepare($sql);
                $stmt->bind_param("iii", $a, $this->pok1['id'], $this->pok2['id']);
                $stmt->execute();
        }
    }
    if($err == 0) {
        $sql = "UPDATE user_pokemons SET sparka = ? WHERE id IN (?,?)";
          $a = 1;
                $stmt = Work::$sql->prepare($sql);
                $stmt->bind_param("iii", $a, $this->pok1['id'], $this->pok2['id']);
                $stmt->execute();
        array_push($plus,Items::arrayItem([54,[1,1]]));
        /*$battlepass_quest1 = Work::$sql->query("SELECT * FROM battlepass_quests WHERE user = ".$_SESSION['id']." AND quest_id = 1 AND val = 'sex'")->fetch_assoc();
        if(isset($battlepass_quest1)) {
          Work::$sql->query("DELETE FROM battlepass_quests WHERE id = ".$battlepass_quest1['id']);
          Battlepass::getExp(500);
        }else{
          $battlepass_quest2 = Work::$sql->query("SELECT * FROM battlepass_quests WHERE user = ".$_SESSION['id']." AND quest_id = 2 AND val = 'sex'")->fetch_assoc();
          if(isset($battlepass_quest2)) {
            Work::$sql->query("DELETE FROM battlepass_quests WHERE id = ".$battlepass_quest2['id']);
            Battlepass::getExp(500);
          }else{
            $battlepass_quest3 = Work::$sql->query("SELECT * FROM battlepass_quests WHERE user = ".$_SESSION['id']." AND quest_id = 3 AND val = 'sex'")->fetch_assoc();
            if(isset($battlepass_quest3)) {
              Work::$sql->query("DELETE FROM battlepass_quests WHERE id = ".$battlepass_quest3['id']);
              Battlepass::getExp(500);
            }
          }
        }*/
		/*$SparkaBattlePass = Info::_unParseData($userInfo['battlepass_task']);
		if(!isset($SparkaBattlePass['task']['sparka'])) {
			$SparkaBattlePass['task']['sparka'] = 1;
			Battlepass::getExp(50);
		}else{
			if($SparkaBattlePass['task']['sparka'] < 100) {
				$SparkaBattlePass['task']['sparka'] = $SparkaBattlePass['task']['sparka'] + 1;
				Battlepass::getExp(50);
			}
		}*/
		/*$SparkaBattlePass = Info::_ParseData($SparkaBattlePass);
		Work::$sql->query("UPDATE users SET battlepass_task = '".$SparkaBattlePass."' WHERE id = ".$userInfo['id']);*/
        $this->updateGens();
        $gens = $this->infoEgg['eggGens'];
        plusEgg($gens,false,false,true,false,$this->infoEgg['eggBasenum'],false);
		
        /* if(WeekEvents::externalCheckStartEvent(3)) {
			
          $eventMy = Work::$sql->query("SELECT prize FROM user_week_events WHERE event = 3 AND user = ".$_SESSION['id'])->fetch_assoc();
		  
          if(isset($eventMy)) {
			  
            $js = Info::_unParseData($eventMy['prize']);
			
            if($js[1] > 0) {
				
				array_push($plus,Items::arrayItem([199,[1,1]]));
				
				itemAdd(199,1);
				
				$js[1] = $js[1] - 1;
				
				$eventMy['prize'] = Info::_ParseData($js);
				
				Work::$sql->query("UPDATE user_week_events SET prize = '".$eventMy['prize']."' WHERE event = 3 AND user = ".intval($_SESSION['id']));
			  
            }
			
          }
		  
        } */
		
        return [10,$plus,$minus];
    }
  }

  private function updateGens(){
      $genOne = explode(',',$this->pok1['gen']);
      $genTwo = explode(',',$this->pok2['gen']);
      
    $randHp = mt_rand(1,100);
    $randAtk = mt_rand(1,100);
    $randDef = mt_rand(1,100);
    $randSpd = mt_rand(1,100);
    $randSA = mt_rand(1,100);
    $randSD = mt_rand(1,100);
    
    
    $hp = ($genOne[0] > $genTwo[0] ? $genOne[0] : $genTwo[0]);
    $atk = ($genOne[1] > $genTwo[1] ? $genOne[1] + 1 :  $genTwo[1]);
    $def = ($genOne[2] > $genTwo[2] ? $genOne[2] : $genTwo[2]);
    $spd = ($genOne[3] > $genTwo[3] ? $genOne[3] : $genTwo[3]);
    $sa = ($genOne[4] > $genTwo[4] ? $genOne[4] : $genTwo[4]);
    $sd = ($genOne[5] > $genTwo[5] ? $genOne[5] : $genTwo[5]);
    
    
    if($randHp < 33){
        
        $hp = $hp + 1; 
        
    }elseif($randHp >= 33 && $randHp <= 66){
        
        $hp = $hp - 1; 
        
    }else{
        
        $hp = $hp;
        
    }
    
    if($randAtk < 33){
        
        $atk = $atk + 1; 
        
    }elseif($randAtk >= 33 && $randAtk <= 66){
        
        $atk = $atk - 1; 
        
    }else{
        
        $atk = $atk;
        
    }
    
    if($randDef < 33){
        
        $def = $def + 1; 
        
    }elseif($randDef >= 33 && $randDef <= 66){
        
        $def = $def - 1; 
        
    }else{
        
        $def = $def;
        
    }
    
    if($randSpd < 33){
        
        $spd = $spd + 1; 
        
    }elseif($randSpd >= 33 && $randSpd <= 66){
        
        $spd = $spd - 1; 
        
    }else{
        
        $spd = $spd;
        
    }
    
    if($randSA < 33){
        
        $sa = $sa + 1; 
        
    }elseif($randSA >= 33 && $randSA <= 66){
        
        $sa = $sa - 1; 
        
    }else{
        
        $sa = $sa;
        
    }
    
    if($randSD < 33){
        
        $sd = $sd + 1; 
        
    }elseif($randSD >= 33 && $randSD <= 66){
        
        $sd = $sd - 1; 
        
    }else{
        
        $sd = $sd;
        
    }
    
    

      
     /* $hp = ($genOne[0] > $genTwo[0] ? (random_int(1,100) < 40 ? $genOne[0] + 1 : $genOne[0] - 1) : (random_int(1,100) < 40 ? $genTwo[0] + 1 : $genTwo[0] - 1));
      $atk = ($genOne[1] > $genTwo[1] ? (random_int(1,100) < 40 ? $genOne[1] + 1 : $genOne[1] - 1) : (random_int(1,100) < 40 ? $genTwo[1] + 1 : $genTwo[1] - 1));
      $def = ($genOne[2] > $genTwo[2] ? (random_int(1,100) < 40 ? $genOne[2] + 1 : $genOne[2] - 1) : (random_int(1,100) < 40 ? $genTwo[2] + 1 : $genTwo[2] - 1));
      $spd = ($genOne[3] > $genTwo[3] ? (random_int(1,100) < 40 ? $genOne[3] + 1 : $genOne[3] - 1) : (random_int(1,100) < 40 ? $genTwo[3] + 1 : $genTwo[3] - 1));
      $sa = ($genOne[4] > $genTwo[4] ? (random_int(1,100) < 40 ? $genOne[4] + 1 : $genOne[4] - 1) : (random_int(1,100) < 40 ? $genTwo[4] + 1 : $genTwo[4] - 1));
      $sd = ($genOne[5] > $genTwo[5] ? (random_int(1,100) < 40 ? $genOne[5] + 1 : $genOne[5] - 1) : (random_int(1,100) < 40 ? $genTwo[5] + 1 : $genTwo[5] - 1));*/

      $this->infoEgg['eggGens'] = $hp.','.$atk.','.$def.','.$spd.','.$sa.','.$sd;
  }

}

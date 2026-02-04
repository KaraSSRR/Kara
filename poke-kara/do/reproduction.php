<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
        require_once($patch_func);
    }
}
Class Reproduction{
    /**
     * @property $reproductionType 1 = Обычная
     * @property $reproductionType 2 = С разными формами
     * @property $reproductionType 3 = Бесполые
     */
    private $mysqli;

    private $response = [];

    private $pok1 = [];

    private $pok2 = [];

    private $reproductionType = 1;

    private $infoEgg = [];

    public function __construct(mysqli $mysqli, $pok1, $pok2){
        $this->mysqli = $mysqli;

        $pokOne = $this->mysqli->query("SELECT `basenum`,`gender`,`gen`,`sparkaNumber`, `item_id`,`name_new`, `ability`,`ability_slot` FROM `user_pokemons`
											WHERE `id` = '".$pok1."' AND `user_id` = '".$_SESSION['id']."'
											AND `active` = 1 AND `sparka` = 0")->fetch_assoc();

        $pokTwo = $this->mysqli->query("SELECT `basenum`,`gender`,`gen`,`sparkaNumber`, `item_id`,`name_new`, `ability`,`ability_slot` FROM `user_pokemons`
											WHERE `id` = '".$pok2."' AND `user_id` = '".$_SESSION['id']."'
											AND `active` = 1 AND `sparka` = 0")->fetch_assoc();

        if(empty($pokOne) || empty($pokTwo) ){
            $this->response['error'] = 1;
            $this->response['text'] = 'Ошибка выбора покемона!';
            die(json_encode($this->response));
        }
        elseif($pokOne['sparkaNumber'] != $pokTwo['sparkaNumber']){
            $this->response['error'] = 1;
            $this->response['text'] = 'Покемоны не нравятся друг другу!';
            die(json_encode($this->response));
        }
        elseif($this->response['error'] != 1){
            $this->pok1 = array('id'=>intval($pok1),'basenum'=>$pokOne['basenum'],'gender'=>$pokOne['gender'],'gen'=>$pokOne['gen'], 'item'=>(empty($pokOne['item_id']) ? 0 : intval($pokOne['item_id'])));
            $this->pok2 = array('id'=>intval($pok2),'basenum'=>$pokTwo['basenum'],'gender'=>$pokTwo['gender'],'gen'=>$pokTwo['gen'], 'item'=>(empty($pokTwo['item_id']) ? 0 : intval($pokTwo['item_id'])));
            $this->response['error'] = 0;
            $this->response['text'] = 'Разведение прошло успешно!';
            $this->checkPoks(intval($this->pok1['basenum']), intval($this->pok2['basenum']));
            lvlupuser(20);
            battlepass_exp(15,1);
        }else{
            $this->response['error'] = 1;
            $this->response['text'] = 'Покемоны не нравятся друг другу!';
            die(json_encode($this->response));
        }
    }

    private function checkPoks(int $pok1, int $pok2){
        if($this->pok1['gender'] == $this->pok2['gender']){
            if(
                ($pok1 == 144 && $pok2 == 144) ||
                ($pok1 == 374 && $pok2 == 374) ||
                ($pok1 == 494 && $pok2 == 494) ||
                ($pok1 == 640 && $pok2 == 640) ||
                ($pok1 == 100 && $pok2 == 100) ||
                ($pok1 == 649 && $pok2 == 649) ||
                ($pok1 == 622 && $pok2 == 622) ||
                ($pok1 == 623 && $pok2 == 623) ||
                ($pok1 == 385 && $pok2 == 385) ||
                ($pok1 == 132 && $pok2 == 132) ||
                ($pok1 == 145 && $pok2 == 145) ||
                ($pok1 == 644 && $pok2 == 644) ||
                ($pok1 == 647 && $pok2 == 647) ||
                ($pok1 == 599 && $pok2 == 599) ||
                ($pok1 == 601 && $pok2 == 601) ||
                ($pok1 == 600 && $pok2 == 600) ||
                ($pok1 == 638 && $pok2 == 638) ||
                ($pok1 == 615 && $pok2 == 615) ||
                ($pok1 == 646 && $pok2 == 646) ||
                ($pok1 == 462 && $pok2 == 462) ||
                ($pok1 == 120 && $pok2 == 120) ||
                ($pok1 == 121 && $pok2 == 121) ||
                ($pok1 == 343 && $pok2 == 343) ||
                ($pok1 == 344 && $pok2 == 344) ||
                ($pok1 == 81 && $pok2 == 81) ||
                ($pok1 == 82 && $pok2 == 82) ||
				($pok1 == 436 && $pok2 == 436) ||
				($pok1 == 437 && $pok2 == 437) ||
                ($pok1 == 376 && $pok2 == 376) ||
                ($pok1 == 375 && $pok2 == 375) ||
                ($pok1 == 146 && $pok2 == 146) ||
                ($pok1 == 150 && $pok2 == 150) ||
                ($pok1 == 137 && $pok2 == 137) ||
                ($pok1 == 233 && $pok2 == 233) ||
                ($pok1 == 474 && $pok2 == 474) ||
                ($pok1 == 643 && $pok2 == 643) ||
                ($pok1 == 639 && $pok2 == 639) ||
                ($pok1 == 101 && $pok2 == 101) ||
                ($pok1 == 703 && $pok2 == 703) ||
                ($pok1 == 337 && $pok2 == 337) ||
                ($pok1 == 338 && $pok2 == 338) ||
                ($pok1 == 924 && $pok2 == 924) ||
                ($pok1 == 925 && $pok2 == 925) ||
                ($pok1 == 999 && $pok2 == 999) ||
                ($pok1 == 1000 && $pok2 == 1000)
            ){
                $this->reproductionType = 3;
                $this->infoEgg['eggBasenum'] = (mt_rand(1, 2) == 2 ? $this->pok1['basenum'] : $this->pok2['basenum']);
                $this->getResult();
            }else{
                $this->response['error'] = 1;
                $this->response['text'] = 'Покемоны должны быть одной формы и разных полов!';
                die(json_encode($this->response));
            }
        }else{
            if(($pok1 == 29 && $pok2 == 32) || ($pok1 == 32 && $pok2 == 29)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 30 && $pok2 == 33) || ($pok1 == 33 && $pok2 == 30)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 31 && $pok2 == 34) || ($pok1 == 34 && $pok2 == 31)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 124 && $pok2 == 106) || ($pok1 == 106 && $pok2 == 124)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 124 && $pok2 == 107) || ($pok1 == 107 && $pok2 == 124)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 128 && $pok2 == 241) || ($pok1 == 241 && $pok2 == 128)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 238 && $pok2 == 236) || ($pok1 == 236 && $pok2 == 238)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 238 && $pok2 == 237) || ($pok1 == 237 && $pok2 == 238)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 313 && $pok2 == 314) || ($pok1 == 314 && $pok2 == 313)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 380 && $pok2 == 381) || ($pok1 == 381 && $pok2 == 380)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 413 && $pok2 == 414) || ($pok1 == 414 && $pok2 == 413)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 588 && $pok2 == 616) || ($pok1 == 616 && $pok2 == 588)){
                $sex1 = $this->mysqli->query("SELECT `evol_type`,`evol_basenum`,`eggBasenum`,`name_rus`,`sex_m`,`sex_f` FROM `base_pokemons` WHERE `id` = '".$pok1."'")->fetch_assoc();
                    $sex2 = $this->mysqli->query("SELECT `evol_type`,`evol_basenum`,`name_rus`,`sex_m`,`sex_f` FROM `base_pokemons` WHERE `id` = '".$pok2."'")->fetch_assoc();
                    $sex1_1 = $this->mysqli->query("SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '".$sex1['evol_basenum']."'")->fetch_assoc();

                    $sex2_1 = $this->mysqli->query("SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '".$sex2['evol_basenum']."'")->fetch_assoc();
                    if($sex1['evol_type'] == 'sex'){
                        if($this->pok1['name_new'] == $sex1['name_rus']){ $name = $this->pok1['name_new'];}else{$name = $sex1_1['name_rus'];}
                        $this->mysqli->query("UPDATE `user_pokemons` SET `basenum` = '".$sex1['evol_basenum']."',`name_new` = '".$name."' WHERE `id` = '".$this->pok1['id']."'");
                        $Ability = $this->mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$sex1['evol_basenum'])->fetch_assoc();
                                      if($this->pok1['ability_slot'] != 3){
                                        if($this->pok1['ability_slot'] == 1) {
                                          $AbilityNow = $Ability['slot1'];
                                          $Slot = 1;
                                        }elseif($this->pok1['ability_slot'] == 2){
                                          $Slot = 2;
                                          $AbilityNow = $Ability['slot2'];
                                        }
                                        //var_dump($AbilityNow);
                                        if($AbilityNow == NULL) {
                                          $Slot = 1;
                                          $AbilityNow = $Ability['slot1'];
                                        }
                                      }
                                      if($this->pok1['ability_slot'] == 3 && $Ability['hidden'] != "0") {
                                        $AbilityNow = $Ability['hidden'];
                                        $Slot = 3;
                                      }
                                      if($AbilityNow == NULL) {
                                        $AbilityNow = 0;
                                        $Slot = 0;
                                      }
                                      addAbilityPok($this->pok1['id'],$AbilityNow,$Slot);
                    }
                    if($sex2['evol_type'] == 'sex'){
                        if($this->pok2['name_new'] == $sex2['name_rus']){ $name = $this->pok2['name_new'];}else{$name = $sex2_1['name_rus'];}
                        $this->mysqli->query("UPDATE `user_pokemons` SET `basenum` = '".$sex2['evol_basenum']."',`name_new` = '".$name."' WHERE `id` = '".$this->pok2['id']."'");
                        $Ability = $this->mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$sex2['evol_basenum'])->fetch_assoc();
                                      if($this->pok2['ability_slot'] != 3){
                                        if($this->pok2['ability_slot'] == 1) {
                                          $AbilityNow = $Ability['slot1'];
                                          $Slot = 1;
                                        }elseif($this->pok2['ability_slot'] == 2){
                                          $Slot = 2;
                                          $AbilityNow = $Ability['slot2'];
                                        }
                                        //var_dump($AbilityNow);
                                        if($AbilityNow == NULL) {
                                          $Slot = 1;
                                          $AbilityNow = $Ability['slot1'];
                                        }
                                      }
                                      if($this->pok1['ability_slot'] == 3 && $Ability['hidden'] != "0") {
                                        $AbilityNow = $Ability['hidden'];
                                        $Slot = 3;
                                      }
                                      if($AbilityNow == NULL) {
                                        $AbilityNow = 0;
                                        $Slot = 0;
                                      }
                                      addAbilityPok($this->pok2['id'],$AbilityNow,$Slot);
                    }
                
                $this->reproductionType = 2;
            }elseif(($pok1 == 627 && $pok2 == 629) || ($pok1 == 629 && $pok2 == 627)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 628 && $pok2 == 630) || ($pok1 == 630 && $pok2 == 628)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 856 && $pok2 == 859) || ($pok1 == 859 && $pok2 == 856)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 857 && $pok2 == 860) || ($pok1 == 860 && $pok2 == 857)){
                $this->reproductionType = 2;
            }elseif(($pok1 == 858 && $pok2 == 861) || ($pok1 == 861 && $pok2 == 858)){
                $this->reproductionType = 2;
            }else{
                if($this->pok1['basenum'] != $this->pok2['basenum']){
                    $this->response['error'] = 1;
                    $this->response['text'] = 'Выберите правильную пару';
                    die(json_encode($this->response));
                    
                }else{
                    $sex1 = $this->mysqli->query("SELECT `evol_type`,`evol_basenum`,`eggBasenum`,`name_rus`,`sex_m`,`sex_f` FROM `base_pokemons` WHERE `id` = '".$pok1."'")->fetch_assoc();
                    $sex2 = $this->mysqli->query("SELECT `evol_type`,`evol_basenum`,`name_rus`,`sex_m`,`sex_f` FROM `base_pokemons` WHERE `id` = '".$pok2."'")->fetch_assoc();
                    $sex1_1 = $this->mysqli->query("SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '".$sex1['evol_basenum']."'")->fetch_assoc();

                    $sex2_1 = $this->mysqli->query("SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '".$sex2['evol_basenum']."'")->fetch_assoc();
                    if($sex1['evol_type'] == 'sex'){
                        if($this->pok1['name_new'] == $sex1['name_rus']){ $name = $this->pok1['name_new'];}else{$name = $sex1_1['name_rus'];}
                        $this->mysqli->query("UPDATE `user_pokemons` SET `basenum` = '".$sex1['evol_basenum']."',`name_new` = '".$name."' WHERE `id` = '".$this->pok1['id']."'");
                        $Ability = $this->mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$sex1['evol_basenum'])->fetch_assoc();
                                      if($this->pok1['ability_slot'] != 3){
                                        if($this->pok1['ability_slot'] == 1) {
                                          $AbilityNow = $Ability['slot1'];
                                          $Slot = 1;
                                        }elseif($this->pok1['ability_slot'] == 2){
                                          $Slot = 2;
                                          $AbilityNow = $Ability['slot2'];
                                        }
                                        //var_dump($AbilityNow);
                                        if($AbilityNow == NULL) {
                                          $Slot = 1;
                                          $AbilityNow = $Ability['slot1'];
                                        }
                                      }
                                      if($this->pok1['ability_slot'] == 3 && $Ability['hidden'] != "0") {
                                        $AbilityNow = $Ability['hidden'];
                                        $Slot = 3;
                                      }
                                      if($AbilityNow == NULL) {
                                        $AbilityNow = 0;
                                        $Slot = 0;
                                      }
                                      addAbilityPok($this->pok1['id'],$AbilityNow,$Slot);
                    }
                    if($sex2['evol_type'] == 'sex'){
                        if($this->pok2['name_new'] == $sex2['name_rus']){ $name = $this->pok2['name_new'];}else{$name = $sex2_1['name_rus'];}
                        $this->mysqli->query("UPDATE `user_pokemons` SET `basenum` = '".$sex2['evol_basenum']."',`name_new` = '".$name."' WHERE `id` = '".$this->pok2['id']."'");
                        $Ability = $this->mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$sex2['evol_basenum'])->fetch_assoc();
                                      if($this->pok2['ability_slot'] != 3){
                                        if($this->pok2['ability_slot'] == 1) {
                                          $AbilityNow = $Ability['slot1'];
                                          $Slot = 1;
                                        }elseif($this->pok2['ability_slot'] == 2){
                                          $Slot = 2;
                                          $AbilityNow = $Ability['slot2'];
                                        }
                                        //var_dump($AbilityNow);
                                        if($AbilityNow == NULL) {
                                          $Slot = 1;
                                          $AbilityNow = $Ability['slot1'];
                                        }
                                      }
                                      if($this->pok1['ability_slot'] == 3 && $Ability['hidden'] != "0") {
                                        $AbilityNow = $Ability['hidden'];
                                        $Slot = 3;
                                      }
                                      if($AbilityNow == NULL) {
                                        $AbilityNow = 0;
                                        $Slot = 0;
                                      }
                                      addAbilityPok($this->pok2['id'],$AbilityNow,$Slot);
                    }
                    week_mission('breeding');
                    if(check_mission_ivent(26) and ($sex2['evol_type'] == 'sex' or $sex1['evol_type'] == 'sex')){ add_mission_ivent(26);}
                    if(($sex1['sex_m'] != $sex2['sex_f'] AND $sex1['sex_f'] != $sex2['sex_m']) AND check_mission_ivent(29)){ add_mission_ivent(29);}
                    $this->infoEgg['eggBasenum'] = $sex1['eggBasenum'];
                }
            }
            $this->getResult();
        }
    }

    public function getPossiblePartners(int $pok1Id): array {
        $q = $this->mysqli->prepare("
            SELECT u.id, u.name_new, u.gender, u.basenum
            FROM user_pokemons u
            JOIN user_pokemons p1 ON p1.user_id = ? AND p1.id = ?
            WHERE u.user_id = ? AND u.active = 1 AND u.sparka = 0
            AND u.sparkaNumber = p1.sparkaNumber
            AND u.id <> ?
        ");
        $q->bind_param("iiii", $this->userId, $pok1Id, $this->userId, $pok1Id);
        $q->execute();
        return $q->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function massBreed(array $pairs): void {
        $results = [];
        foreach ($pairs as [$pok1, $pok2]) {
            $handler = new Reproduction($this->mysqli, $pok1, $pok2);
            $results[] = $handler->getResponse();
        }
        echo json_encode(['results' => $results]);
    }


    private function getResult(){

        if($this->reproductionType == 2){
            $sex1 = $this->mysqli->query("SELECT `eggBasenum` FROM `base_pokemons` WHERE `id` = '".$this->pok1['basenum']."'")->fetch_assoc();
            $sex2 = $this->mysqli->query("SELECT `eggBasenum` FROM `base_pokemons` WHERE `id` = '".$this->pok2['basenum']."'")->fetch_assoc();
            $this->infoEgg['eggBasenum'] = (random_int(1,100)> 40 ? $sex1['eggBasenum'] : $sex2['eggBasenum']);
        }else if($this->reproductionType == 3){
            if(! ($this->pok1['item'] == 124 && $this->pok2['item'] == 124) ){
                $this->response['error'] = 1;
                $amur = ($this->pok1['item'] != 124 ? ' ('.$this->pok1['id'].')' : '').($this->pok2['item'] != 124 ? ' ('.$this->pok2['id'].')' : '');
                $this->response['text'] = 'На покемонах должны быть Амуриты.';
                die(json_encode($this->response));
            }
			$this->mysqli->query("
				UPDATE `user_pokemons`
				SET
				  `item_id` = 0
				WHERE
				  `id` IN (".$this->pok1['id'].", ".$this->pok2['id'].")
			");
			if(check_mission_ivent(21)){ add_mission_ivent(21);}
        }

        $this->mysqli->query("
            UPDATE `user_pokemons`
            SET
              `sparka` = 1
            WHERE
              `id` IN (".$this->pok1['id'].", ".$this->pok2['id'].")
        ");
        $this->updateGens();
        $gens = $this->infoEgg['eggGens'];
        $lrn = $this->mysqli->query('SELECT * FROM `learn_pve` WHERE `user` = '.$_SESSION['id'].' AND type = 3')->fetch_assoc();
        if(isset($lrn)) {
            if(achiv_utility(23)){
								        $countDay = mt_rand(5,8);
								    }else{
								        $countDay = mt_rand(7,10);
								    }
          $tm = time()+(3600*24*$countDay);
          plusEgg($gens,false,false,true,$tm,$this->infoEgg['eggBasenum'],false);
        }else{
          plusEgg($gens,false,false,true,false,$this->infoEgg['eggBasenum'],false);
        }
        $ib = $this->mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$this->infoEgg['eggBasenum'].' ')->fetch_assoc();
        if(check_mission_ivent(5) and ($ib['type'] == 'water' or $ib['type_two'] == 'water')){ add_mission_ivent(5);}
        if(check_mission_ivent(23) and ($ib['type'] == 'fly' or $ib['type_two'] == 'fly')){ add_mission_ivent(23);}
        battlepass_exp(15);
        echo json_encode($this->response);
    }

private function updateGens(){
    $genOne = explode(',', $this->pok1['gen']);
    $genTwo = explode(',', $this->pok2['gen']);

    if($genOne[0] > $genTwo[0]){
        $hp = (random_int(1,100) > 67 ? $genOne[0] + 1 : (random_int(1,100) > 33 ? $genOne[0] : $genOne[0] - 1));
    }else{
        $hp = (random_int(1,100) > 67 ? $genTwo[0] + 1 : (random_int(1,100) > 33 ? $genTwo[0] : $genTwo[0] - 1));
    }

    if($genOne[1] > $genTwo[1]){
        $atk = (random_int(1,100) > 67 ? $genOne[1] + 1 : (random_int(1,100) > 33 ? $genOne[1] : $genOne[1] - 1));
    }else{
        $atk = (random_int(1,100) > 67 ? $genTwo[1] + 1 : (random_int(1,100) > 33 ? $genTwo[1] : $genTwo[1] - 1));
    }

    if($genOne[2] > $genTwo[2]){
        $def = (random_int(1,100) > 67 ? $genOne[2] + 1 : (random_int(1,100) > 33 ? $genOne[2] : $genOne[2] - 1));
    }else{
        $def = (random_int(1,100) > 67 ? $genTwo[2] + 1 : (random_int(1,100) > 33 ? $genTwo[2] : $genTwo[2] - 1));
    }

    if($genOne[3] > $genTwo[3]){
        $spd = (random_int(1,100) > 67 ? $genOne[3] + 1 : (random_int(1,100) > 33 ? $genOne[3] : $genOne[3] - 1));
    }else{
        $spd = (random_int(1,100) > 67 ? $genTwo[3] + 1 : (random_int(1,100) > 33 ? $genTwo[3] : $genTwo[3] - 1));
    }

    if($genOne[4] > $genTwo[4]){
        $sa = (random_int(1,100) > 67 ? $genOne[4] + 1 : (random_int(1,100) > 33 ? $genOne[4] : $genOne[4] - 1));
    }else{
        $sa = (random_int(1,100) > 67 ? $genTwo[4] + 1 : (random_int(1,100) > 33 ? $genTwo[4] : $genTwo[4] - 1));
    }

    if($genOne[5] > $genTwo[5]){
        $sd = (random_int(1,100) > 67 ? $genOne[5] + 1 : (random_int(1,100) > 33 ? $genOne[5] : $genOne[5] - 1));
    }else{
        $sd = (random_int(1,100) > 67 ? $genTwo[5] + 1 : (random_int(1,100) > 33 ? $genTwo[5] : $genTwo[5] - 1));
    }

    // Ограничение на максимальное значение гена (26) и минимальное (1)
    $hp = min(max($hp, 1), 31);
    $atk = min(max($atk, 1), 31);
    $def = min(max($def, 1), 31);
    $spd = min(max($spd, 1), 31);
    $sa = min(max($sa, 1), 31);
    $sd = min(max($sd, 1), 31);

    $this->infoEgg['eggGens'] = "$hp,$atk,$def,$spd,$sa,$sd";
}
}
$reproduction = new Reproduction($mysqli, clearInt($_POST['pok1']), clearInt($_POST['pok2']));
?>

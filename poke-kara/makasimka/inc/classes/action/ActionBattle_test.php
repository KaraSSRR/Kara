<?php
function quest_step($id, $step){
  global $mysqli;
  if($step == 0)  $a = true;
   else{
      $q = $mysqli->query("SELECT `step` FROM `user_quests` WHERE `user_id` = '".$_SESSION['id']."' AND `quest_id` = '".$id."'")->fetch_assoc();
      $a = ($q['step'] == $step?true:false);
  }
 return $a;
}

class ActionBattle{

    private $defaultInfo = [
        'id'=>0,
        'login'=>'Дикий покемон',
        'user_group'=>6
    ];

    public $next_turn = 0;

    public $round = 0;

    public $weather = 0;

    public $img = 0;

    public $autolog = '';

        public $battleId = 0;

    private $update = [];

    private $battleInfo = [];

    private $battleHH = 0;

    private $battleAM = 0;

    private $battleType = 'pve';

    public $userInfo = [];

    private $enemyInfo = [];

    private $userData = [];

    private $enemyData = [];

    private $userPokes = [];

    private $enemyPokes = [];

    private $userTarget = [];

    private $enemyTarget = [];

    private $otherInfo = [];

    private $response = [];

    private $answer = [];

    private $log = [];
    private $userId1 = 0;

    private $userId2 = 0;

    const TIMER_ATK_ROUND = 120;

    const LOSE_NO_HP = 'NO_HP';
    const LOSE_TIMEOUT = 'NO_TIME';
    const LOSE_COWARD = 'COWARD';
    const LOSE_ALL = 'ALL';
    const LOSE_OTHER = 'OTHER';


    public function __construct(array $userInfo = [], array $enemyInfo = [], array &$response = []){

        if(Work::$sql){

            $this->response =& $response;

            $this->userInfo = array_merge($this->defaultInfo, $userInfo);


            if($this->userInfo['id'] && $this->userInfo['id'] > 0){

                if(isset($this->userInfo['status_id']) && $this->userInfo['status_id'] > 0){

                    if(empty($enemyInfo)){

                    }else{
                        $this->enemyInfo = array_merge($this->defaultInfo, $enemyInfo);
                    }

                    $this->battleInfo = Work::$sql->query('SELECT * FROM `battle` WHERE `id` ='.intval($this->userInfo['status_id']))->fetch_assoc();

                    if($this->_isPVP()){
                      $this->userId1 = $this->battleInfo['user1'];
                      $this->userId2 = $this->battleInfo['user2'];
                    }

                    if(!empty($this->battleInfo)){
                        $this->start();
                        return;
                    }

                }

                $this->resetAction();

            }
        }
        return null;
    }

    private function generateAnswer(array $value, $user_id = null){
        $user_id = ($user_id ? $user_id : $this->enemyInfo['id']);

        if($user_id && $user_id > 0){
            if(isset($this->answer['u'.$user_id])){
                $this->answer['u'.$user_id] = array_merge($this->answer['u'.$user_id], $value);
            }else{
                $this->answer['u'.$user_id] = $value;
            }
        }

        return $value;
    }

    private function start(){
        if($this->parser()){
            $this->parserPost();
            $this->response['battleInfo'] = $this->viewInfo();
            $this->response['battleHH'] = 0;
            $this->response['battleAM'] = 0;
        }else{
            $this->resetAction();
        }

    }

    private function parser(){
        if(isset($this->battleInfo['id'])){

            $this->round = intval($this->battleInfo['round']);
            $this->weather = intval($this->battleInfo['weather']);
            $this->img = intval($this->battleInfo['img']);

            $this->battleType = $this->battleInfo['type'];

            if($this->userInfo['id'] == $this->battleInfo['user_1']){
                $this->userData  = $this->battleInfo['info_1'];
                $this->enemyData = $this->battleInfo['info_2'];
            }else{
                $this->userData  = $this->battleInfo['info_2'];
                $this->enemyData = $this->battleInfo['info_1'];
            }

            $this->userData  = Info::_unParseData($this->userData);
            $this->enemyData = Info::_unParseData($this->enemyData);

            if(!empty($this->battleInfo['other'])){
                $this->otherInfo = array_merge($this->otherInfo, Info::_unParseData($this->battleInfo['other']));
            }

            if(!empty($this->battleInfo['answer'])){
                $this->answer = array_merge($this->answer, Info::_unParseData($this->battleInfo['answer']));
            }

            if(isset($this->userData['userInfo'])){
                $this->userInfo = array_merge($this->userInfo, $this->userData['userInfo']);
            }
            if(isset($this->enemyData['userInfo'])){
                $this->enemyInfo = array_merge($this->enemyInfo, $this->enemyData['userInfo']);
            }

            if(isset($this->userData['pokeLIst'])){
                $this->userPokes =& $this->userData['pokeLIst'];
            }
            if(isset($this->enemyData['pokeLIst'])){
                $this->enemyPokes =& $this->enemyData['pokeLIst'];
            }

            if($this->_isPVP()){
                if(isset($this->answer['battleEND']) || ($this->battleInfo['user_2'] <= 0 || $this->battleInfo['user_1'] <= 0)){
                    $this->resetAction(true);
                }
            }

            if($this->userPokes && is_array($this->userPokes) && $this->enemyPokes && is_array($this->enemyPokes)){

                if(isset($this->enemyData['target']) && $this->enemyData['target'] > 0){

                    if(isset($this->enemyPokes['p'.$this->enemyData['target']])){
                        $this->enemyTarget = $this->enemyPokes['p'.$this->enemyData['target']];
                    }else{
                        $this->enemyTarget = [];
                    }

                }

                if(isset($this->userData['target']) && $this->userData['target'] > 0){

                    if(isset($this->userPokes['p'.$this->userData['target']])){
                        $this->userTarget = $this->userPokes['p'.$this->userData['target']];
                    }else{
                        $this->userTarget = [];
                    }

                }else{
                    $this->enemyTarget = [];
                }

                return true;
            }

        }
        return false;
    }

    // private function captcha($value = false){

    //     if($this->_isPVP()){
    //         return true;
    //     }

    //     if(!empty($value)){
    //         if(isset($_SESSION['user_captcha'])){
    //             if($_SESSION['user_captcha']['value'] == $value){

    //                 $_SESSION['user_captcha'] = [
    //                     'tick'=>0,
    //                     'max_tick'=>mt_rand(30, 50),
    //                     'value'=>0,
    //                     'title'=>''
    //                 ];

    //                 $this->response['captcha'] = [
    //                     'cpl'=>true
    //                 ];

    //             }else{

    //                 $val_1 = mt_rand(1, 10);
    //                 $val_2 = mt_rand(1, 10);

    //                 $_SESSION['user_captcha']['value'] = ($val_1 + $val_2);
    //                 $_SESSION['user_captcha']['title'] = $val_1.' + '.$val_2.' = ';

    //                 $this->response['captcha'] = [
    //                     'title'=>$_SESSION['user_captcha']['title']
    //                 ];

    //             }
    //         }
    //         return true;
    //     }

    //     if(isset($_SESSION['user_captcha'])){

    //         $_SESSION['user_captcha']['tick'] = $_SESSION['user_captcha']['tick'] + 1;

    //         if($_SESSION['user_captcha']['tick'] >= $_SESSION['user_captcha']['max_tick']){

    //             if($_SESSION['user_captcha']['value'] <= 0){

    //                 $val_1 = mt_rand(1, 10);
    //                 $val_2 = mt_rand(1, 10);

    //                 $_SESSION['user_captcha']['value'] = ($val_1 + $val_2);
    //                 $_SESSION['user_captcha']['title'] = $val_1.' + '.$val_2.' = ';

    //             }

    //             $this->response['captcha'] = [
    //                 'title'=>$_SESSION['user_captcha']['title']
    //             ];

    //             return false;
    //         }

    //     }else{
    //         $_SESSION['user_captcha'] = [
    //             'tick'=>0,
    //             'max_tick'=>mt_rand(30, 50),
    //             'value'=>0,
    //             'title'=>''
    //         ];
    //     }

    //     return true;
    // }

    private function checkTimeout($userInfo){
        if($userInfo && isset($userInfo['timer'])){
            $time = time();
            if(isset($userInfo['timer']['atk']) && $userInfo['timer']['atk'] > 0 && $time > $userInfo['timer']['atk']){
                return true;
            }
        }
        return false;
    }

    private function parserPost(){
        if($this->userPokes && $this->enemyPokes){

            if(isset($_POST['targetPoke'])){

                $target = intval($_POST['targetPoke']);

                if($this->checkTimeout($this->enemyData)){
                    $this->lose($this->userInfo['id'], self::LOSE_TIMEOUT);
                    return;
                }

                if(isset($this->userPokes['p'.$target])){

                    if(isset($this->userData['target']) && $this->userData['target'] > 0){

                        $this->update['my'] = true;

                        $target = new PokeBattle($this->userPokes['p'.$target]);

                        if(isset($this->userTarget['hp']) && $this->userTarget['hp'] <= 0){
              Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$this->userInfo['id']);
              Work::$sql->query('DELETE FROM atk_furycutter WHERE user = '.$this->userInfo['id']);
              Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$this->userInfo['id']);
              Work::$sql->query('DELETE FROM atk_tripleaxel WHERE user = '.$this->userInfo['id']);
							Work::$sql->query('DELETE FROM battle_block WHERE user = '.$this->userInfo['id']);
							Work::$sql->query('INSERT INTO battle_log (battle,round,text,end,user) VALUES ('.$this->battleInfo['id'].','.$this->battleInfo['round'].',"[{"user": "'.$this->userInfo['id'].'", fdsf") WHERE battle = '.$this->battleInfo['id'].' ');


                $this->userData['targetAtk'] = 9999;
                $this->enemyData['targetAtk'] = 754;
                $this->userData['targetPokemon'] = $target->_getID();
                $this->update['my'] = true;
                $this->next_turn = 1;
                $this->goRound();


                        }else{

                            // if(!$this->captcha()){
                            //     return;
                            // }

                            if($target->hp <= 0) {
                              _setError('Ошибка! У покемона недостаточно здоровья для выхода в бой.','plus');
                              return;
                            }else{
                              $this->userData['targetAtk'] = 9999;
                              $this->userData['targetPokemon'] = $target->_getID();

                              if(!$this->goRound()){ // Апдейт боя
                                  $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                                  $this->update['my'] = true;
                              }
                            }

                        }

                    }else{
                        $this->userTarget = $this->userPokes['p'.$target];
                        $this->userData['target'] = $target;
                        $this->update['my'] = true;
                    }

                }

            }elseif(isset($_POST['aHH'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 235;
                $target2 = intval($_POST['aHH']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                    // if(!$this->captcha()){
                    //     return;
                    // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetHH'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['aPS'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 374;
                $target2 = intval($_POST['aPS']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                     // if(!$this->captcha()){
                     //     return;
                     // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetPS'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['aHW'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 229;
                $target2 = intval($_POST['aHW']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                     // if(!$this->captcha()){
                     //     return;
                     // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetHW'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['aUT'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 583;
                $target2 = intval($_POST['aUT']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                     // if(!$this->captcha()){
                     //     return;
                     // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetUT'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['aWS'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 592;
                $target2 = intval($_POST['aWS']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                     // if(!$this->captcha()){
                     //     return;
                     // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetWS'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['aBP'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 34;
                $target2 = intval($_POST['aBP']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                     // if(!$this->captcha()){
                     //     return;
                     // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetBP'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['aAM'])){

              if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){
                $target = 21;
                $target2 = intval($_POST['aAM']);
                if($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a'.$target])){
                  if($this->userTarget['hp'] > 0){
                     // if(!$this->captcha()){
                     //     return;
                     // }
                    $target = $this->userTarget['atkList']['a'.$target];
                    if(isset($target['id'])){

                      $target2 = new PokeBattle($this->userPokes['p'.$target2]);
                      $this->userData['targetAM'] = $target2->_getID();

                        $this->userData['targetAtk'] = $target['id'];

                        $disable = explode(',',$this->userTarget['disable_my']);
                        if($disable[$target['attack_num']] == 0) {
                          if(!$this->goRound()){
                              $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                              $this->update['my'] = true;
                          }
                        }

                    }
                  }
                }
              }

            }elseif(isset($_POST['targetAtk'])){

                if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){

                    $target = intval($_POST['targetAtk']);

                    if($this->userTarget && $this->enemyTarget && $target > 0 && isset($this->userTarget['atkList']['a'.$target])){

                        if($this->userTarget['hp'] > 0){

                             // if(!$this->captcha()){
                             //     return;
                             // }

                            $target = $this->userTarget['atkList']['a'.$target];
                            $disable = explode(',',$this->userTarget['disable_my']);
                            if($disable[$target['attack_num']] == 0) {
                              if(isset($target['id'])){

                                  $this->userData['targetAtk'] = $target['id'];

                                  if(!$this->goRound()){
                                      $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                                      $this->update['my'] = true;
                                  }

                              }
                            }
                        }

                    }
                }
            }elseif(isset($_POST['coward'])){
                $this->lose($this->userInfo['id'], self::LOSE_COWARD);
            }elseif(isset($_POST['catch'])){

				if(!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)){

				// 	if(!$this->captcha()){
				// 		return;
				// 	}
					if($this->userTarget && $this->userTarget['hp'] > 0){
						$this->pokeCatch($_POST['catch']);
					}

				}


            }
            // elseif(isset($_POST['captcha'])){

            //     $this->captcha($_POST['captcha']);



            // }
            elseif(isset($_POST['check_timer'])){

              if($this->userData['targetAtk'] > 0 && $this->enemyData['targetAtk'] > 0) {
                if($this->_isPVP()){
                  $this->goRound();
                }
              }

            }

        }
    }

    private function pokeCatch($targetID){
        if($targetID && $targetID > 0){
          $sel = Work::$sql->query('
                  SELECT
                    `bi`.`id` AS `number`,
                    `bi`.`name`,
                    `bi`.`type`,
                    `bi`.`info`,
                    `ui`.`id`,
                    `ui`.`id`,
                    `ui`.`count`,
                    `bi`.`battle`
                  FROM `items_users` AS `ui`
                  INNER JOIN `base_items` AS `bi`
                    ON `bi`.`id`= `ui`.`item_id`
                  WHERE
                    `ui`.`id` = '.$targetID.' AND
                    `ui`.`user` = '.$_SESSION['id'].'
              ')->fetch_assoc();

                if(!empty($sel['count']) && $sel['count'] > 0 && $sel['battle'] == '1'){
                    if($sel['type'] == 'ball'){
                      if(isset($this->enemyInfo['catch']) && $this->enemyInfo['catch'] > 0){
                        if(!isset($this->userTarget['status_list']['two_turn'])) {
                          minus_item_id($targetID, 1, $_SESSION['id']);
                        }
                        $this->userData['targetAtk'] = 9998;
                        $this->userData['targetItem'] = [
                            'number'=>intval($sel['number']),
                            'name'=>$sel['name'],

                            'class' =>$sel['type'],
                            'val'=>($sel['info'] > 0 ? floatval($sel['info']) : 1)
                        ];
          						  if(!$this->goRound()){
          							  $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
          							  $this->update['my'] = true;
          						  }
                      }else{
                          _setError('Этого покемона ловить нельзя!','plus');
                      }
                    }else{
                        if(isset($this->userTarget['item_battle']) && $this->userTarget['item_battle'] <= 1){
                          if(!isset($this->userTarget['status_list']['two_turn'])) {
                            minus_item_id($targetID, 1, $_SESSION['id']);
                          }
                      $this->userData['targetAtk'] = 9998;
                      $this->userData['targetItem'] = [
                          'number'=>intval($sel['number']),
                            'class' =>$sel['type'],
                          'name'=>$sel['name'],
                          'val'=>($sel['info'] > 0 ? floatval($sel['info']) : 1)
                      ];
          						if(!$this->goRound()){
          							$this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
          							$this->update['my'] = true;
          						}
                        }else{
                          _setError('Вы уже использовали 2 предмета на этого покемона!','plus');
                      }
                    }
                }
        }
    }

    private function goRound(){

        if(!$this->_isPVP()){
            if(isset($this->enemyTarget['attacks'],$this->enemyTarget['atkList'])){

                if(!is_array($this->enemyTarget['attacks'])){
                    $this->enemyTarget['attacks'] = explode(',', $this->enemyTarget['attacks']);
                }

                if(shuffle($this->enemyTarget['attacks'])){
                    $this->enemyData['targetAtk'] = intval($this->enemyTarget['attacks'][0]);
                }

            }
        }

        if(isset($this->enemyData['targetAtk']) && $this->enemyData['targetAtk'] > 0 || $this->enemyData['targetAtk'] > 0 && $this->userData['targetAtk'] > 0 || $this->next_turn == 1){

$last_atk_my_id = $this->userData['targetAtk'];

            $this->update['enemy'] = true;

            $u =& $this->userTarget;
            $e =& $this->enemyTarget;

            if($this->next_turn == 1) {
              $this->enemyData['targetAtk'] = 754;
            }

            $battle = new Battle($this, $this->userData, $this->enemyData, $u, $e);

            $this->userPokes['p'.$u['id']] = $u;
            $this->enemyPokes['p'.$e['id']] = $e;

            $this->log[] = [
                'round'=>$this->battleInfo['round'],
                'log'=>$battle->_getLog(),
                'log_status'=>$battle->_getLogStatus()
            ];
			$LogGame = json_encode($battle->_getLog(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
			$LogGame1 = json_encode($battle->_getLogStatus(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

			Work::$sql->query('INSERT INTO battle_log (battle,round,text,end,user) VALUES ('.$this->battleInfo['id'].','.$this->battleInfo['round'].',"'.Work::$sql->real_escape_string($LogGame).'","'.Work::$sql->real_escape_string($LogGame1).'",0)');
			$this->generateAnswer([
                'log'=>$this->log
            ]);

            $myRetarget = $this->_isRetarget($this->userPokes);
            $enemyRetarget = $this->_isRetarget($this->enemyPokes);

            if(isset($this->enemyData['timer'], $this->enemyData['timer']['atk'])){
                $this->enemyData['timer']['atk'] = 0;
            }
            if(isset($this->userData['timer'], $this->userData['timer']['atk'])){
                $this->userData['timer']['atk'] = 0;
            }


            if(!$myRetarget && !$enemyRetarget){
                $this->lose(true, self::LOSE_ALL);
            }elseif(!$myRetarget){
                $this->lose($this->userInfo['id'], self::LOSE_NO_HP);
            }elseif(!$enemyRetarget){
              if(isset($u['atkList']['a'.$last_atk_my_id])) {
                $last_atk_my = $u['atkList']['a'.$last_atk_my_id];
              }else{
                $last_atk_my = false;
              }
                      $this->lose($this->enemyInfo['id'], self::LOSE_NO_HP, $last_atk_my);
            }else{
                /*if($this->userTarget['hp'] <= 0){
                    $this->
                }*/
            }
            return true;
        }

        return false;
    }

    private function viewInfo(){
        if($this->userPokes && $this->enemyPokes){

            $myAnswer = [];
            if(!empty($this->answer)){
                if(isset($this->answer['u'.$this->userInfo['id']])){
                    $myAnswer = $this->answer['u'.$this->userInfo['id']];
                    $this->update['my'] = true;
                    unset($this->answer['u'.$this->userInfo['id']]);
                }
            }


if($this->round == 1) {
  if($this->enemyData['target'] == 0 || $this->userData['target'] == 0) {
    $txtStartBattle = 'Подготовка к бою ddd.<br>';
  }else{
    $enemyPokemonStart = $this->enemyPokes['p'.$this->enemyData['target']];
    $userPokemonStart = $this->userPokes['p'.$this->userData['target']];
  $txtStartBattle = 'Начало боя.<br>';
  if($userPokemonStart['ability'] == 44 || $enemyPokemonStart['ability'] == 44) {
    $txtStartBattle .= '<div class=Ability onclick=issetAll(44,\'ability\')>Осушение</div> меняет погоду на Солнечную.';
    Work::$sql->query('UPDATE battle SET weather = 2 WHERE id = '.$this->battleInfo['id']);
    $this->weather = 2;
  }
  if($userPokemonStart['ability'] == 43 || $enemyPokemonStart['ability'] == 43) {
    $txtStartBattle .= '<div class=Ability onclick=issetAll(43,\'ability\')>Изморось</div> меняет погоду на Дождь.';
    Work::$sql->query('UPDATE battle SET weather = 3 WHERE id = '.$this->battleInfo['id']);
    $this->weather = 3;
  }

  if($userPokemonStart['ability'] == 89) {
    $this->enemyPokes['p'.$this->enemyData['target']]['modified']['atk']['minus'] = 1;
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(89,\'ability\')>Яростный взгляд</div> понижает Атаку  '.$this->_getNameBattle($enemyPokemonStart);
  }

  if($enemyPokemonStart['ability'] == 89) {
    $this->userPokes['p'.$this->userData['target']]['modified']['atk']['minus'] = 1;
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(89,\'ability\')>Яростный взгляд</div> понижает Атаку  '.$this->_getNameBattle($userPokemonStart);
  }

  if($userPokemonStart['ability'] == 255) {
    $this->userPokes['p'.$this->userData['target']]['modified']['atk']['plus'] = 1;
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(255,\'ability\')>Отважный мечник</div> повышает Атаку  '.$this->_getNameBattle($userPokemonStart);
  }

  if($enemyPokemonStart['ability'] == 255) {
    $this->enemyPokes['p'.$this->enemyData['target']]['modified']['atk']['plus'] = 1;
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(255,\'ability\')>Отважный мечник</div> повышает Атаку  '.$this->_getNameBattle($enemyPokemonStart);
  }

  if($userPokemonStart['ability'] == 256) {
    $this->userPokes['p'.$this->userData['target']]['modified']['atk']['plus'] = 1;
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(256,\'ability\')>Бесстрашный защитник</div> повышает Защиту  '.$this->_getNameBattle($userPokemonStart);
  }

  if($enemyPokemonStart['ability'] == 256) {
    $this->enemyPokes['p'.$this->enemyData['target']]['modified']['atk']['plus'] = 1;
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(256,\'ability\')>Бесстрашный защитник</div> повышает Защиту  '.$this->_getNameBattle($enemyPokemonStart);
  }

  if($userPokemonStart['ability'] == 96) {
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(96,\'ability\')>Левитация</div> позволяет '.$this->_getNameBattle($userPokemonStart).' левитировать.';
    $this->userPokes['p'.$this->userData['target']]['status_list']['levitation'] = [
      'type' => 'levitation',
      'count' => 9999,
      'val' => 0
    ];
  }

  if($enemyPokemonStart['ability'] == 96) {
    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(96,\'ability\')>Левитация</div> позволяет '.$this->_getNameBattle($enemyPokemonStart).' левитировать.';
    $this->enemyPokes['p'.$this->enemyData['target']]['status_list']['levitation'] = [
      'type' => 'levitation',
      'count' => 9999,
      'val' => 0
    ];
  }

  $this->update['my'] = true;
  $this->update['enemy'] = true;
  }
Work::$sql->query('UPDATE battle_log SET text = "'.$txtStartBattle.'" WHERE starter = 1 AND battle = '.$this->battleInfo['id']);

}
            $LogBattle = Work::$sql->query('SELECT * FROM battle_log WHERE battle = '.$this->battleInfo['id'].' AND starter != 1 ORDER BY id DESC');
            $LogBattleStart = Work::$sql->query('SELECT * FROM battle_log WHERE battle = '.$this->battleInfo['id'].' AND starter = 1')->fetch_assoc();


            $LogBattleSel = '';

            while($Log = $LogBattle->fetch_row()){
              $LogBattle1 = json_decode($Log[3], true);
              $LogEndBattle = json_decode($Log[4], true);
              $user1 = Work::$sql->query('SELECT login,user_group FROM users WHERE id = '.$LogBattle1[0]['user'])->fetch_row();
              if(isset($LogBattle1[1]) && $LogBattle1[1]['user'] != 0){
                $user2 = Work::$sql->query('SELECT login,user_group FROM users WHERE id = '.$LogBattle1[1]['user'])->fetch_row();
                $userNick2 = '<div class="u-'.$user2[1].'">'.$user2[0].'</div>';
                if(file_exists('img/avatars/mini/'.$LogBattle1[1]['user'].'.png')){
                  $userImage2 = '<img src="img/avatars/mini/'.$LogBattle1[1]['user'].'.png" class="ava">';
                }else{
                  $userImage2 = '<img src="img/avatars/mini/no-user-img.png" class="ava">';
                }
              }else{
                $userNick2 = '<div class="u-6">Дикий покемон</div>';
                $userImage2 = '';
              }
              $userNick1 = '<div class="u-'.$user1[1].'">'.$user1[0].'</div>';
              if(file_exists('img/avatars/mini/'.$LogBattle1[0]['user'].'.png')){
                $userImage1 = '<img src="img/avatars/mini/'.$LogBattle1[0]['user'].'.png" class="ava">';
              }else{
                $userImage1 = '<img src="img/avatars/mini/no-user-img.png" class="ava">';
              }
              $LogText1 = "";
              $LogText2 = "";
              $LogEnd = "";
              if(isset($LogBattle1[0]["log"])){
                $LogText1 .= '<div class="User">'.$userNick1.'</div>';
                foreach($LogBattle1[0]["log"] as $a){
                  $LogText1 .= "<span>".$a."</span>";
                }
              }
              if(isset($LogBattle1[1]["log"])){
                $LogText2 .= '<div class="User">'.$userNick2.'</div>';
                foreach($LogBattle1[1]["log"] as $b){
                  $LogText2 .= "<span>".$b."</span>";
                }
              }
              foreach($LogEndBattle as $c){
                $LogEnd .= "<span>".$c."</span>";
              }
              $LogBattleSel .= '<div class="Step"><div class="Round">Раунд '.$Log[2].'</div><div class="Process">'.$LogText1.'</div><div class="Process">'.$LogText2.''.$LogEnd.'</div></div>';
            }

            if(isset($LogBattleStart)) {
              $LogBattleSel .= '<div class="Step"><div class="Process"><span>'.$LogBattleStart['text'].'</span></div></div>';
            }

            $this->autolog = $LogBattleSel;

		    $return = [
                'id'			=> intval($this->battleInfo['id']),
                'my'			=> $this->viewInfoUser($this->userInfo),
                'enemy'			=> $this->viewInfoUser($this->enemyInfo),
                'myTarget'		=> $this->viewInfoTarget($this->userTarget, true),
                'enemyTarget'	=> $this->viewInfoTarget($this->enemyTarget),
                'myTeam'		=> $this->viewInfoTeam($this->userPokes),
                'myTeamHP'		=> $this->viewInfoTeamHP($this->userPokes),
                'timeout'		=> isset($this->userData['timer']) ? $this->userData['timer'] : [],
                'time'			=> time(),
                'round'			=> $this->round,
                'weather'   => $this->weather,
                'battleLog' => $this->autolog,
                'img'       => $this->img,
                'battleId' => $this->battleId
            ];

			if(isset($myAnswer['log'])){
                $return['log'] = $myAnswer['log'];
            }else if(!empty($this->log)){
                $return['log'] = $this->log;
            }

            if(isset($myAnswer['battleEND'])){
                if(empty($return['log'])){
                    $return['log'] = [];
                }
                $return['log'] = array_merge($return['log'], ['battleEND'=>$myAnswer['battleEND']]);
            }
            return $return;
        }
        return [];
    }

    private function _getNameBattle($pok) {
      return '<span class=\'bgPok\' onclick=openDex('.$pok['basenum'].')><img src=/img/pokemons/animation/'.$pok['basenum'].'.png> <div class='.$pok['type'].'-color style=\'display: inline-block;\'>#'.Info::getNumPokemonNum($pok['basenum']).' '.$pok['name_new'].'</div></span>';
    }
    
    private function viewInfoUser($userInfo){
        if($userInfo && isset($userInfo['id'])){
            return [
                'id'    =>$userInfo['id'],
                'login' =>$userInfo['login'],
                'group' =>$userInfo['group'],
                'sex'   =>$userInfo['sex'],
                'catch' =>$userInfo['catch'],
            ];
        }
        return [];
    }

    private function viewInfoTarget($targetID, $my = false){
        if(is_array($targetID) && isset($targetID['id'])){
            $targetID = intval($targetID['id']);
        }
        if(is_numeric($targetID) && $targetID > 0){
            $targetID = (isset($this->userPokes['p'.$targetID]) ? $this->userPokes['p'.$targetID] : (isset($this->enemyPokes['p'.$targetID]) ? $this->enemyPokes['p'.$targetID] : [] ) );
        }
        if(!empty($targetID) && is_array($targetID)){

            if($targetID['hp'] === true){
                $targetInfo = new PokeBattle($targetID);
                $targetID = $targetInfo->_getData();
            }

            $targetID['stats'] = (isset($targetID['stats']) ? (is_array($targetID['stats']) ? $targetID['stats'] : explode(',', $targetID['stats'])) : []);

		  //   $LogBattle = Work::$sql->query('SELECT * FROM battle_log WHERE battle = '.$this->battleInfo['id'].' ORDER BY id DESC');
			// $LogBattleSel = '';
			$BallsPoke = '';
			if(isset($this->enemyData['pokeLIst']) && $this->battleInfo['type'] == 'pve'){
				$BallsPoke = '<div class="Ball" style="background-image: url(/img/world/items/little/2.png);"></div>';
			}else{
				foreach($this->enemyData['pokeLIst'] as $abc){
					if(isset($abc)){
						if($abc['hp'] <= 0){
							$noneHp = 'noHp';
						}else{
							$noneHp = '';
						}
						$BallsPoke .= '<div class="Ball '.$noneHp.'" style="background-image: url(/img/world/items/little/'.$abc['ball'].'.png);"></div>';
					}
				}
			}
			// while($Log = $LogBattle->fetch_row()){
			// 	$LogBattle1 = json_decode($Log[3], true);
			// 	$LogEndBattle = json_decode($Log[4], true);
			// 	$user1 = Work::$sql->query('SELECT login,user_group FROM users WHERE id = '.$LogBattle1[0]['user'])->fetch_row();
			// 	if(isset($LogBattle1[1]) && $LogBattle1[1]['user'] != 0){
			// 		$user2 = Work::$sql->query('SELECT login,user_group FROM users WHERE id = '.$LogBattle1[1]['user'])->fetch_row();
			// 		$userNick2 = '<div class="u-'.$user2[1].'">'.$user2[0].'</div>';
			// 		if(file_exists('img/avatars/mini/'.$LogBattle1[1]['user'].'.png')){
			// 			$userImage2 = '<img src="img/avatars/mini/'.$LogBattle1[1]['user'].'.png" class="ava">';
			// 		}else{
			// 			$userImage2 = '<img src="img/avatars/mini/no-user-img.png" class="ava">';
			// 		}
			// 	}else{
			// 		$userNick2 = '<div class="u-6">Дикий покемон</div>';
			// 		$userImage2 = '';
			// 	}
			// 	$userNick1 = '<div class="u-'.$user1[1].'">'.$user1[0].'</div>';
			// 	if(file_exists('img/avatars/mini/'.$LogBattle1[0]['user'].'.png')){
			// 		$userImage1 = '<img src="img/avatars/mini/'.$LogBattle1[0]['user'].'.png" class="ava">';
			// 	}else{
			// 		$userImage1 = '<img src="img/avatars/mini/no-user-img.png" class="ava">';
			// 	}
			// 	$LogText1 = "";
			// 	$LogText2 = "";
			// 	$LogEnd = "";
			// 	if(isset($LogBattle1[0]["log"])){
			// 		$LogText1 .= '<div class="User">'.$userNick1.'</div>';
			// 		foreach($LogBattle1[0]["log"] as $a){
			// 			$LogText1 .= "<span>".$a."</span>";
			// 		}
			// 	}
			// 	if(isset($LogBattle1[1]["log"])){
			// 		$LogText2 .= '<div class="User">'.$userNick2.'</div>';
			// 		foreach($LogBattle1[1]["log"] as $b){
			// 			$LogText2 .= "<span>".$b."</span>";
			// 		}
			// 	}
			// 	foreach($LogEndBattle as $c){
			// 		$LogEnd .= "<span>".$c."</span>";
			// 	}
			// 	$LogBattleSel .= '<div class="Step"><div class="Round">Раунд '.$Log[2].'</div><div class="Process">'.$LogText1.'</div><div class="Process">'.$LogText2.''.$LogEnd.'</div></div>';
			// }
            
            #Спрайты Шайни/Шадоу/Милитари/Файтер/Чемпион
            if ($targetID['type'] != 'normal') { $typeSprite = 'shine';}else { $typeSprite = 'normal';}
            if($targetID['form'] != "0"){
                $form = "_".$targetID['form'];
            }else{
                $form = "";
            }
			$sprite = '<img src="/img/pokemons/sprite/'.$typeSprite.'/'.numbPok($targetID['basenum']).$form.'.gif">';
			#Спрайты Шайни/Шадоу/Милитари/Файтер/Чемпион END

            //var_dump($targetID['pp_my']);
            if($targetID['gender'] == 'Мальчик') {
              $sex2 = 'mars';
            }elseif($targetID['gender'] == 'Девочка') {
              $sex2 = 'venus';
            }else{
              $sex2 = 'genderless';
            }
            $explvllow = Info::_getExp($targetID['lvl']-1, $targetID['base_exp_group']);
                  $explvl = $targetID['exp']-$explvllow;
                  $explvl2 = $targetID['exp_max']-$explvllow;
                $Boss = Work::$sql->query('
                                        SELECT  `hp`, `hp_max`
                                        FROM `base_boss`
                                        WHERE `basenum` =  '.$targetID['basenum'].' and `death` = 0
            ')->fetch_assoc();
            if($Boss){
                $hp = $Boss['hp'];
                $hp_max = $Boss['hp_max'];
            }else{
                if(isset($targetID['hp'])){ $hp = intval($targetID['hp']); }else{ $hp = 0; }
                if(isset($targetID['stats'][0])){ $hp_max = intval($targetID['stats'][0]); }else{ $hp_max = 0; }
            }

            return [
                'id'        => ((isset($targetID['id']) ? intval($targetID['id']) : 0)),
                'ball'      => ((isset($targetID['ball']) ? intval($targetID['ball']) : 3)),
                'basenum'   => ((isset($targetID['basenum']) ? intval($targetID['basenum']) : 0)),
                'basenum2'  => numbPok($targetID['basenum']),
                'form'      => $targetID['form'],
                'name'      => ((isset($targetID['name_new']) ? $targetID['name_new'] : '...')),
                'lvl'       => ((isset($targetID['lvl']) ? intval($targetID['lvl']) : 1)),
                'type2'     => ($targetID['type'] == 'normal' ? '' : $targetID['type']),
                'type'      => ((isset($targetID['type']) ? $targetID['type'] : 'normal')),
                'sex'       => ((isset($targetID['gender']) ? $targetID['gender'] : 'Мальчик')),
                'sex2'      => $sex2,
                'hp'        => $hp,
                'hp_max'    => $hp_max,
                'hp_before' => 0,
                'desteny_bond' => 0,
                'atk_zamena' => 0,
                'round_before' => 0,
                'atk_before'=> 0,
                'atk_beforeNow' => 0,
				'metronom' => 0,
                'exp'       => (isset($targetID['base_exp_group']) ? Info::_pokeEXP($targetID['lvl'], $targetID['base_exp_group'], $explvl, $explvl2) : []),
                'item'      => ((isset($targetID['item_id']) ? intval($targetID['item_id']) : 0)),
                'pp_my'     => ''.$targetID['pp_my'].'',
                'disable_my' => ''.$targetID['disable_my'].'',
                'atkList'   => ((isset($targetID['atkList']) && $my) ? $targetID['atkList'] : []),
                'statMod'   => (isset($targetID['modified']) ? $targetID['modified'] : []),
				'statusList'=> (isset($targetID['status_list']) ? $targetID['status_list'] : []),
				'item_battle' => (isset($targetID['item_battle']) ? $targetID['item_battle'] : []),
				'tren'      => (isset($targetID['tren']) ? $targetID['tren'] : []),
				'BallsPoke' => $BallsPoke,
				'sprite'	=> $sprite,
            ];

        }
        return [];
    }

    private function viewInfoTeam($teamList){
        if(!empty($teamList) && is_array($teamList)){
            $return = [];
            foreach($teamList AS $key=>$value){
                if(isset($value['id'])){
                    $return['p'.$value['id']] = $this->viewInfoTarget($value);
                }
            }
            return $return;
        }
        return [];
    }

    private function viewInfoTeamHP($teamList){
        if(!empty($teamList) && is_array($teamList)){
            $return = [];
            foreach($teamList AS $key=>$value){
                if(isset($value['id']) AND $value['hp'] > 0){
                    $return['p'.$value['id']] = $this->viewInfoTarget($value);
                }
            }
            return $return;
        }
        return [];
    }

    private function resetAction($battleEnd = false){

        if(isset($this->userInfo['id'])){
            Work::$sql->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '" . intval($this->userInfo['id']) . "'");
        }

        if($battleEnd){

            if($this->_isPVP()){
                if($this->battleInfo['user_1'] <= 0 || $this->battleInfo['user_2'] <= 0){

                    $this->update = [];
                    Work::$sql->query('DELETE FROM atk_helping_hand WHERE user = '.$_SESSION['id']);
                    Work::$sql->query('DELETE FROM atk_aromatic_mist WHERE user = '.$_SESSION['id']);
					Work::$sql->query('DELETE FROM battle_block WHERE user = '.$_SESSION['id']);
          Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$_SESSION['id']);
          Work::$sql->query('DELETE FROM atk_furycutter WHERE user = '.$_SESSION['id']);
          Work::$sql->query('DELETE FROM atk_tripleaxel WHERE user = '.$_SESSION['id']);
          Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$_SESSION['id']);
          Work::$sql->query('DELETE FROM battle_effects WHERE user = '.$_SESSION['id']);
                    Work::$sql->query('DELETE FROM `battle` WHERE `id` = '.intval($this->battleInfo['id']));

                }else{

                    $upd = ($this->battleInfo['user_1'] == $_SESSION['id'] ? 'user_1' : 'user_2');
                    Work::$sql->query('UPDATE `battle` SET
                                        `'.$upd.'` = 0
                                     WHERE `id` = '.intval($this->battleInfo['id']));

                }


            }else{

                $this->update = [];
                Work::$sql->query('DELETE FROM atk_helping_hand WHERE user = '.$_SESSION['id']);
                Work::$sql->query('DELETE FROM atk_aromatic_mist WHERE user = '.$_SESSION['id']);
				Work::$sql->query('DELETE FROM battle_block WHERE user = '.$_SESSION['id']);
        Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$_SESSION['id']);
        Work::$sql->query('DELETE FROM atk_furycutter WHERE user = '.$_SESSION['id']);
        Work::$sql->query('DELETE FROM atk_tripleaxel WHERE user = '.$_SESSION['id']);
        Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$_SESSION['id']);
        Work::$sql->query('DELETE FROM battle_effects WHERE user = '.$_SESSION['id']);
                Work::$sql->query('DELETE FROM `battle` WHERE `id` = '.intval($this->battleInfo['id']));

            }
        }

    }

    /** ~~ PUBLIC METHOD's ~~ **/

    public function _nextRound(){
        $this->round += 1;
    }

    public function lose($user_lose_id, $lose_type = 'OTHER', $last_atk = false){

        if(isset($this->userData['lose']) || isset($this->enemyData['lose'])){
            return;
        }
        $this->userData['lose'] = $lose_type;
        $this->update['my'] = true;

        $user_lose = [];
        $user_winner = [];

        if($user_lose_id === true && $lose_type != 'CATCH'){
            if($this->userInfo['id'] > 0){
                if($this->_isPVP()){
                    foreach($this->userPokes AS $key=>&$value){
                        $value = Info::_updatePokeExp($value, false, true);
                    }
                    unset($key,$value);
                }
            }

            if($this->enemyInfo['id'] > 0){
                if($this->_isPVP()){
                    foreach($this->enemyPokes AS $key=>&$value){
                        $value = Info::_updatePokeExp($value, false,true);
                    }
                    unset($key,$value);
                }
            }

        }else{

            $user_lose   = ($this->userInfo['id'] == $user_lose_id ? $this->userInfo  : $this->enemyInfo);
            $user_winner = ($this->userInfo['id'] == $user_lose_id ? $this->enemyInfo : $this->userInfo);

            if($lose_type != 'CATCH') {

                if($this->userInfo['id'] == $user_lose_id){
                    $loser_pokes =& $this->userPokes;
                }else{
                    $loser_pokes =& $this->enemyPokes;
                }

                if($this->userInfo['id'] == $user_lose_id){
                    $winner_pokes =& $this->enemyPokes;
                }else{
                    $winner_pokes =& $this->userPokes;
                }

                if($user_lose['id'] > 0){
    
                    if($this->_isPVP()){
                        foreach($loser_pokes AS $key=>&$value){
                            $value = Info::_updatePokeExp($value, false, true);
                        }
                        unset($key,$value);
                    }
                }
            }
            if($user_winner['id'] > 0 || $lose_type == 'CATCH'){

				if($lose_type == 'CATCH') {
					$user_winner = $this->userInfo;
				}

                if(!$this->_isPVP() && $this->battleInfo['user_2'] == 0){

					$winnerRating = json_decode($user_winner['rating']);
					$winnerRatingUpd = '{"pve": '.($winnerRating->pve+1).', "pvp": '.$winnerRating->pvp.', "battleCount": '.$winnerRating->battleCount.'}';

					Work::$sql->query("UPDATE `users`
										SET `rating` = '".$winnerRatingUpd."', `countKillPok` = `countKillPok` + 1 WHERE `id` = '".$user_winner['id']."'");
					if(check_mission(5)){ add_mission(5);}
					if(check_mission_ivent(2)){ add_mission_ivent(2);}
					if(check_mission_ivent(9)){ add_mission_ivent(9);}
					if(check_mission_ivent(11)){ add_mission_ivent(11);}
					if(check_mission_ivent(15)){ add_mission_ivent(15);}
					if(check_mission_ivent(19)){ add_mission_ivent(19);}
					if(check_mission_ivent(25)){ add_mission_ivent(25);}
					if(check_mission_ivent(28)){ add_mission_ivent(28);}
                    lvlupuser(3);
                    $d = Work::$sql->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
                    // $it_cave = Work::$sql->query("SELECT * FROM `ivent_cave` WHERE `user` = ".$_SESSION['id'])->fetch_assoc();
                    // if($it_cave){
                    //     if($d['location'] == 88){
                    //         $pl_1 = $it_cave['l1']+1;
                    //         Work::$sql->query("UPDATE `ivent_cave`	SET `l1` = `l1` + 1 WHERE `user` = '".$d['id']."'");
                    //     }
                    // }


                    // GOVNO drop (Дроп не из БД)============================================================================================
                    #Проверка на Ожерелье монет
                    if($this->userTarget['item_id'] == 157) {
                        $kolco = 1.2;
                    }else{
                        $kolco = 1;
                    }
                    #Проверка на системные рейты монет
                    $systemBonuses = Work::$sql->query('SELECT * FROM system WHERE id = 1')->fetch_assoc();
                    #Проверка на усилитель монет (Бафы)
                    $bafBonuses = Work::$sql->query('SELECT * FROM bafs WHERE type = 2 AND user = '.$_SESSION['id'])->fetch_assoc();
                    if($bafBonuses) {
                        $bafBonuses1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$bafBonuses['baf'])->fetch_assoc();
                        $bafBonuses3 = explode(',',$bafBonuses1['info']);
                        if($bafBonuses['time'] > time()) {
                            $bafBonuses2 = $bafBonuses3['1'];
                        }else{
                            $bafBonuses2 = 1;
                        }
                    }else{
                        $bafBonuses2 = 1;
                    }
                    #Проверка на премиум
                    $bafprem = Work::$sql->query('SELECT * FROM bafs WHERE type = 3 AND user = '.$_SESSION['id'])->fetch_assoc();
                    if($bafprem) {
                        if($bafprem['time'] > time()) {
                            $bafpr = 1.5;
                        }else{
                            $bafpr = 1;
                        }
                    }else{
                        $bafpr = 1;
                    }
                    #Формула начисления монет 
                    $money = round(abs((ceil(round(rand(((10*$this->enemyTarget['lvl'])*1.5)/2,((15*($this->enemyTarget['lvl']+5))*1.5)/2))*$systemBonuses['money']*$bafBonuses2) * $kolco)*$bafpr));
                    
                    #Отсекаем жуликов
                    if ($_SESSION['id'] == 180 OR $_SESSION['id'] == 194) { 
                        $money = $money/2;
                    }
                    
                    #Округляем монеты до целого числа
                    $money = round($money*1);
                    #Формируем сообщение для вывода игроку
                    $this->response['logDrop'] = [];
                    $this->response['logDrop'][] = [
                        'name'=>'Монета',
                        'count'=>$money,
                        'id'=> 1
                    ];
                    #Добавление дропнутых монет и обновление ачивки      
                    itemAdd(1, $money, $_SESSION['id']);
                    update_ach(27,$money);
                    #Добавление дропнутых монет и обновление ачивки END
                    
                    #Дроп предметов
                    include "drop.php";
                    #Дроп предметов END

                    #Выборка дропа из БД (Убрать дроп из БД)
                    if ($item == 0) {
                        $dropQueryInfo = [];
                        $dropQuery = Work::$sql->query('SELECT * FROM `base_drop_pokemons` WHERE `location_id` IN (0, '.$this->userInfo['location'].') ');
                        while($row = $dropQuery->fetch_assoc()){ $dropQueryInfo[] = $row; }
                        	if(!empty($dropQueryInfo)){
                        	    foreach($dropQuery AS $key=>$value){
                        	        $bafprem = Work::$sql->query('SELECT * FROM bafs WHERE type = 3 AND user = '.$_SESSION['id'])->fetch_assoc();
                                    if($bafprem) {
                                        if($bafprem['time'] > time()) {
                                            $bafpr = 2;
                                        }else{
                                            $bafpr = 1;
                                        }
                                    }else{
                                        $bafpr = 1;
                                    }
                        		    if(isset($value['chance'], $value['item_id'], $value['item_count'])){
                        				if(!item_isset($value['item_inv'],$value['item_inv_count']) OR $value['item_inv'] == 0){
                        					if($value['quest_id'] != 0 && !quest_step($value['quest_id'],$value['quest_progress'])){
                        						break;
                        					}
                        					if($value['pok_id'] > 0 && $value['pok_id'] != $this->enemyTarget['id']){
                        						continue;
                        					}
                        					if($value['pok_num'] > 0 && $value['pok_num'] != $this->enemyTarget['basenum']){
                        						continue;
                        					}
                        					if($value['item_id'] <= 0 || $value['chance'] <= 0){
                        						continue;
                        					}
                        					if(($value['chance'] * $systemBonuses['drop'] * $bafpr)<= random_int(0,1000)){
                        						continue;
                        					}
                        					$value['item_count'] = explode(',', $value['item_count']);
                        					if(isset($value['item_count'][1]) && $value['item_count'][1] > $value['item_count'][0]){
                        						$value['item_count'] = mt_rand($value['item_count'][0], $value['item_count'][1]);
                        					}else{
                        						$value['item_count'] = intval($value['item_count'][0]);
                        					}
                        					if($value['item_count'] > 0){
                        						$itemInfo = Work::$sql->query('SELECT `name`,`id`,`news` FROM `base_items` WHERE `id` = "'.$value['item_id'].'"')->fetch_assoc();
                        						if(!empty($itemInfo)){
                        							$this->response['logDrop'][] = [
                        								'name'=>$itemInfo['name'],
                        								'count'=>$value['item_count'],
                        								'id'=>$itemInfo['id']
                        							];
                                                    if($itemInfo['news'] == 1) {
                                                        $user = Work::$sql->query("SELECT * FROM users WHERE id = ".$_SESSION['id'])->fetch_assoc();
                                                        $catch = ($user['sex'] == "m" ? "выбил" : "выбила");
                                                        $text = '<div class="user-link"><div onclick=showUserTooltip('.$user['id'].') class="Info-Link sex'.$user['sex'].'"><i class="fa fa-info"></i></div> <div class="u-'.$user['user_group'].' label" onclick=user_to_chat_add('.$user['id'].')>'.$user['login'].'</div></div> <span>'.$catch.' '.$itemInfo['name'].'</span>';
                                                        Work::$sql->query("INSERT INTO friends_news (user,text,date) VALUES (".$_SESSION['id'].",'".$text."',".time().")");
                                                    }
                        							itemAdd($value['item_id'], $value['item_count'], $_SESSION['id']);
                        							$item = $value['item_id'];	
                        						}
                        					}
                        				}
                                    }
                                }
                                unset($key, $value);
                            }
                        }
                    }
                    #Запись дропа в лог
                    if ($item != 0) {
                        $log = Work::$sql->query("INSERT INTO `log_drop`(`user`, `item`) VALUES ('".$_SESSION['id']."','".$item."')");
                    }
                    #Выборка дропа из БД END
                    // GOVNO drop (Дроп не из БД) END============================================================================================
        
				#updRating
				if($this->_isPVP()){
          Work::$sql->query('INSERT INTO battle_end (user1,user2,win,battle) VALUES ('.$user_lose['id'].','.$user_winner['id'].','.$user_winner['id'].','.$this->battleInfo['id'].')');
					Work::$sql->query("UPDATE `users`
										SET `pvp` = `pvp` + 2, `battleCount` = `battleCount` + 1 WHERE `id` = '".$user_winner['id']."'");
					Work::$sql->query("UPDATE `users`
										SET `battleCount` = `battleCount` + 1 WHERE `id` = '".$user_lose['id']."'");
                    lvlupuser(25,$user_winner['id']);
                    if(check_mission(13,$user_winner['id'])){ add_mission(13,false,$user_winner['id']);}
                    if(check_mission(13,$user_lose['id'])){ add_mission(13,false,$user_lose['id']);}


                    if(check_mission_ivent(8,$user_winner['id'])){ add_mission_ivent(8,false,$user_winner['id']);}
                    if(check_mission_ivent(8,$user_lose['id'])){ add_mission_ivent(8,false,$user_lose['id']);}

                    if(check_mission_ivent(16,$user_winner['id'])){ add_mission_ivent(16,false,$user_winner['id']);}
                    if(check_mission_ivent(16,$user_lose['id'])){ add_mission_ivent(16,false,$user_lose['id']);}

                    if(check_mission_ivent(26,$user_winner['id'])){ add_mission_ivent(26,false,$user_winner['id']);}
                    if(check_mission_ivent(26,$user_lose['id'])){ add_mission_ivent(26,false,$user_lose['id']);}
                    $user_clan_win = Work::$sql->query("SELECT * FROM base_clans_users WHERE user_id = ".$user_winner['id'])->fetch_assoc();
                    $user_clan_lose = Work::$sql->query("SELECT * FROM base_clans_users WHERE user_id = ".$user_lose['id'])->fetch_assoc();
          if($user_clan_win and $user_clan_lose){
            if($user_clan_win['clan_id'] != $user_clan_lose['clan_id']){
              Work::$sql->query("UPDATE `base_clans_users`
    										SET `raiting` = `raiting` + 2 WHERE `user_id` = '".$user_winner['id']."'");
    					Work::$sql->query("UPDATE `base_clans_users`
    										SET `raiting` = `raiting` - 1 WHERE `user_id` = '".$user_lose['id']."'");
                        Work::$sql->query("UPDATE `base_clans`
              										SET `rating` = `rating` + 2 WHERE `id` = '".$user_clan_win['clan_id']."'");
              					Work::$sql->query("UPDATE `base_clans`
              										SET `rating` = `rating` - 1 WHERE `id` = '".$user_clan_lose['clan_id']."'");
            }
          }
				}else{
            if($lose_type != 'CATCH') {
              foreach($winner_pokes AS $key=>&$value){
                        $value = Info::_updatePokeExp($value,$this->enemyTarget['base_effort']);
                    }
                    unset($key,$value);  
                
            }
        }

            }

        }

        $log = $this->generateAnswer([
            'battleEND'=>[
                'title'=>$lose_type,
                'winner'=>(isset($user_winner['id']) ? $user_winner['id'] : false),
                'loser'=>(isset($user_lose['id']) ? $user_lose['id'] : false),
            ]
        ]);

        $this->log = array_merge($this->log, $log);

        if(!$this->_isPVP()){
            $this->resetAction(true);
        }else{
            $this->resetAction(true);
        }

    }

    public function _isPVP(){
        return ($this->battleType === 'pvp');
    }

    public function _isRetarget($pokeList){
        if($pokeList){
            $return = false;

            foreach($pokeList AS $key=>$value){
                if($value && isset($value['hp']) && $value['hp'] > 0){
                    $return = true;
                    break;
                }
            }

            return $return;
        }
        return false;
    }

    public function &_getUserData($userID){
        if($this->userInfo['id'] == $userID){
            return $this->userData;
        }else{
            return $this->enemyData;
        }
    }

    public function &_getUserInfo($userID){
        if($this->userInfo['id'] == $userID){
            return $this->userInfo;
        }else{
            return $this->enemyInfo;
        }
    }

    public function _getUserPokes($userID, $pokes = false){
        if($this->userInfo['id'] == $userID){
            $list =  $this->userPokes;
        }else{
            $list =  $this->enemyPokes;
        }

        if($pokes === false){
            return $list;
        }else{
            return (isset($list['p'.$pokes]) ? $list['p'.$pokes] : []);
        }
    }

    public function _setUserPokes($userID, $pokes, $info = []){
        if($this->userInfo['id'] == $userID){

            if(is_array($pokes)){
                $this->userPokes = $pokes;
            }else{

                if($info){
                    $this->userPokes['p'.$pokes] = $info;
                }

            }

        }else{

            if(is_array($pokes)){
                $this->enemyPokes = $pokes;
            }else{

                if($info){
                    $this->enemyPokes['p'.$pokes] = $info;
                }

            }

        }

    }

    public function _setTarget($userID, $target){
        if(!empty($target)){

            if($this->userInfo['id'] == $userID){
                $this->userData['target'] = $target['id'];
                $this->userTarget = $target;
            }else{
                $this->enemyData['target'] = $target['id'];
                $this->enemyTarget = $target;
            }

        }
    }

    public function __destruct(){
        if(!empty($this->update) && isset($this->battleInfo['id'])){


          if(isset($this->userData['targetHW2'])) {
            $this->userPokes['p'.$this->userData['targetHW2']]['hp'] = 0;
            $this->userData['targetHW2'] = 0;
          }

            if($this->userInfo['id'] == $this->battleInfo['user_1']){
                $upd_my = ' `info_1` = "'.Work::$sql->real_escape_string(Info::_parseData($this->userData)).'" ';
                $upd_enemy = ' `info_2` = "'.Work::$sql->real_escape_string(Info::_parseData($this->enemyData)).'" ';
            }else{
                $upd_my = ' `info_2` = "'.Work::$sql->real_escape_string(Info::_parseData($this->userData)).'" ';
                $upd_enemy = ' `info_1` = "'.Work::$sql->real_escape_string(Info::_parseData($this->enemyData)).'" ';
            }

            Work::$sql->query('UPDATE `battle` SET
                                        `round` = '.$this->round.',
                                        '.$upd_my.'
                                        '.(isset($this->update['enemy']) ? ','.$upd_enemy : '').'
                                        '.($this->otherInfo ? ', `other` = "'.Work::$sql->real_escape_string(Info::_parseData($this->otherInfo)).'" ' : '').'
                                        '.($this->answer ? ', `answer` = "'.Work::$sql->real_escape_string(Info::_parseData($this->answer)).'" ' : ', `answer` = "" ').'
                                     WHERE `id` = '.intval($this->battleInfo['id']));

        }
    }
}
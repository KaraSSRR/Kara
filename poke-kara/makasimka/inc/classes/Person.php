<?php

Class Person {

  private $response = [];
  private $userInfo = [];
  public function timersOtshet($time, $txt, $texttrues){
		
		$a = 24*60*60;
		$b = time();
		$c = $time;
		$d = $c-$b;
		$e = $d/$a;
		$f = floor($e);
		$g = ($e-$f)*24;
		$i = floor($g);
		$m = floor(($g-$i)*60);
		$tt_text = $txt;
		if($f > 0)$tt_text .= "<b>$f</b> д., ";
		if($i > 0)$tt_text .= "<b>$i</b> ч. и ";
		$tt_text .= "<b>$m</b> мин";
		$tt_text .= ".";
		if($time <= $b) $tt_text = $txt.$texttrues.'.';
		return $tt_text;
		
	}
  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;
      $this->userInfo =& $userInfo;

      switch($_POST['type']){

        case 'addons':
          switch($val[0]) {
            case 'heal':
              if($userInfo['status'] == 'free') {
                if(Info::getLocation(['id',$userInfo['location'],'pit']) == 1) {
					$stmt = Work::$sql->prepare("SELECT hp,stats FROM user_pokemons WHERE active = ? AND user_id = ?");
					$a = 1;
                        $stmt->bind_param("ii", $a, $userInfo['id']);
                        $stmt->execute();
                        $pokemons = $stmt->get_result();
                        
					$money = 0;
					
					while($p = $pokemons->fetch_assoc()) {
					    
						$statHp = explode(',',$p['stats']);
						$money = $money + ($statHp[0] - $p['hp']);
						
					}
					if(item_isset(1,$money) && $userInfo['location'] != 757) {
						$error = 3;
	                  	Person::heal();
						minus_item(1,$money);
						$minus = Items::arrayItem([1,[$money,1]]);
					}else{
						Person::heal();
						$error = 4;
					}
                }else{
                  $error = 2;
                }
              }else{
                $error = 1;
              }
            break;
          }
          $this->response['response'] = array(
            'error' => $error,
			'minus' => (isset($minus) ? $minus : 0)
          );
        break;

        case 'requestGym':
          $stmt = Work::$sql->prepare("SELECT * FROM gym_users WHERE user = ?");
                    $stmt->bind_param("i", intval($userInfo['id']));
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $gymUser = $data->fetch_assoc();
          if(isset($gymUser)) {
            $error = 1;
            $text = 'Вы уже подали заявку на гим!';        
          }else{
            if(Info::getLocation(['id',$userInfo['location'],'gym']) == 1) {
                
                $sW = Work::$sql->prepare("SELECT time FROM gym_wait WHERE `user_id` = ?");
                            $sW->bind_param("i", $_SESSION['id']);
                            $sW->execute();
                            $dW = $sW->get_result();
                            $GW = $dW->fetch_assoc();
                $text = 'Заявка успешно подана!';            
                if($GW['time'] < time() || !isset($GW['time'])){
                    $q = Work::$sql->prepare("DELETE FROM gym_wait WHERE user_id = ?"); 
                        if($q)
                        {
                            $q->bind_param("i", $_SESSION['id']);
                            $q->execute();
                           $q->close();
                        }
                    $sWa = Work::$sql->prepare("INSERT INTO `gym_wait` (`user_id`,`time`) VALUES (?,?)"); 
                    $TT = time() + 172800;
                        $sWa->bind_param("ii", $_SESSION['id'], $TT);
                        $sWa->execute();
                        $sWa->close();
              $stmst = Work::$sql->prepare("INSERT INTO gym_users (user,gym) VALUES (?,?) "); 
                        $stmst->bind_param("ii", $userInfo['id'], $userInfo['location']);
                        $stmst->execute();
                        $stmst->close();
                
              $error = 3;
                }else{
                    $text = 'Возвращайся снова через '.self::timersOtshet($GW['time']-1," : ", "несколько минут").'';
                    $error = 4;
                }
            }else{
              $error = 2;
            }
          }
          $this->response['response'] = array(
            'error' => $error,
            'text' => $text
          );
        break;
        case 'DelRequestGym':
          $stmt = Work::$sql->prepare("SELECT * FROM gym_users WHERE user = ?");
                    $stmt->bind_param("i", $userInfo['id']);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $gymUser = $data->fetch_assoc();
          if(!isset($gymUser)) {
            $error = 1;
            $text = 'Вы не подавали заявку на гим!';        
          }else{
            if(Info::getLocation(['id',$userInfo['location'],'gym']) == 1) {
                
                $sW = Work::$sql->prepare("SELECT time FROM gym_wait WHERE `user_id` = ?");
                            $sW->bind_param("i", $_SESSION['id']);
                            $sW->execute();
                            $dW = $sW->get_result();
                            $GW = $dW->fetch_assoc();
                $text = 'Заявка успешно подана!';            
                if(isset($GW['time']) && $gymUser['complete'] == 0){
                    
                    $q = Work::$sql->prepare("DELETE FROM gym_wait WHERE user_id = ?"); 
                            $q->bind_param("i", $_SESSION['id']);
                            $q->execute();
                           $q->close();
                           
                     $q1 = Work::$sql->prepare("DELETE FROM gym_users WHERE id = ?"); 
                            $q1->bind_param("i", $gymUser['id']);
                            $q1->execute();
                           $q1->close();
              $error = 3;
                }else{
                    $text = 'У вас нет активных заявок!';
                    $error = 4;
                }
            }else{
              $error = 2;
            }
          }
          $this->response['response'] = array(
            'error' => $error,
            'text' => $text
          );
        break;

        case 'goFind':
          if(Info::getLocation(['id',$userInfo['location'],'find']) == 1) {
            $timeEnd = (time() + Info::getLocation(['id',$userInfo['location'],'find_time']));
            
            $A = 1;
                $stmt = Work::$sql->prepare("UPDATE users SET find = ?, find_location = ?, find_time = ? WHERE id = ?");
                $stmt->bind_param("iiii", $A, $userInfo['location'], $timeEnd, $userInfo['id']);
                $stmt->execute();
                
            $error = 2;
          }else{
            $error = 1;
          }
          $this->response['response'] = array(
            'error' => $error
          );
        break;

        case 'getFind':
          if($userInfo['find'] == 1) {
            if(time() <= $userInfo['find_time']) {
              $error = 2;
            }else{
              if($userInfo['location'] != $userInfo['find_location']) {
                $error = 1;
              }else{
                $error = 4;
                $a = 0;
                    $stmt = Work::$sql->prepare("UPDATE users SET find = ?, find_location = ?, find_time = ? WHERE id = ?");
                    $stmt->bind_param("iiis", $a, $a, $a, $userInfo['id']);
                    $stmt->execute();
                $prizes = Person::findList($userInfo['find_location']);
                $prizeList = [];
                foreach($prizes as $key => $val) {
                  array_push($prizeList,$val);
                }
                $valCount = count($prizeList);
                $i = 0;
                $myLoot = [];
                while($valCount > $i) {
                    
                  $rand = mt_rand(1,1000);
                  
                  if($prizeList[$i][2] >= $rand) {
                      
                    $randCount = mt_rand($prizeList[$i][1][0],$prizeList[$i][1][1]);
                    $item = [Items::arrayItem([$prizeList[$i][0],[$randCount,1]])];
                    array_push($myLoot,$item);
                    itemAdd($prizeList[$i][0],$randCount);
                    
                  }
                  
                  $i++;
                  
                }
                
              }
              
            }
            
          }else{
              
            $error = 3;
            
          }
          
          $this->response['response'] = array(
            'error' => $error,
            'myLoot' => (isset($myLoot) ? (count($myLoot) <= 0 ? 0 : $myLoot) : 1)
          );
        break;

        case 'openFind':
          if(Info::getLocation(['id',$userInfo['location'],'find']) == 1) {
            $FindList = Person::findList($userInfo['location']);
            $List = [];
            foreach($FindList as $key => $val) {
              $lootPar = [Items::arrayItem([$val[0],[1,1]])];
              array_push($List, $lootPar);
            }
            if($userInfo['find'] != 0) {
              if(time() >= $userInfo['find_time']) {
                $userTimeEndGet = 1;
              }else{
                $userTimeEndGet = 0;
              }
            }
            $this->response['response'] = array(
              'leave' => $userInfo['find_leave'],
              'time' => (Info::getLocation(['id',$userInfo['location'],'find_time']) / 60),
              'findList' => $List,
              'myFind' => ($userInfo['find'] == 0 ? 0 : [Info::getLocation(['id',$userInfo['find_location'],'name']),$userInfo['find_time'],$userTimeEndGet])
            );
          }
        break;

        case 'openGym':
          $stmt = Work::$sql->prepare("SELECT * FROM gym_text WHERE gym = ?");
                    $stmt->bind_param("i", intval($userInfo['location']));
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $gym = $data->fetch_assoc();
          $stmtgu = Work::$sql->prepare("SELECT * FROM gym_users WHERE gym = ? AND complete != ?");
          $comp = 1;
                    $stmtgu->bind_param("ii", $gym['gym'], $comp);
                    $stmtgu->execute();
                    $gymUsers = $stmtgu->get_result();
          $UserList = [];
          while($user = $gymUsers->fetch_assoc()) {
            $UserList[$user['id']] = [
              'User' => Info::getMainUser(['id',$user['user']])
            ];
          }
          $this->response['response'] = array(
            'User' => Info::getMainUser(['gym',$gym['gym']]),
            'Text' => $gym['text'],
            'user_list' => $UserList,
            'Gym' => $gym['gym'],
            'Count' => $gym['count'],
            'Lvl' => $gym['lvl']
          );
        break;

      }

    }

  }

  public function findList($location) {
    switch($location) {
      case 364:
        $list = [
          [265,[1,2],300,1],
          [266,[1,2],300,1],
          [263,[1,2],300,1],
          [264,[1,2],300,1]
        ];
      break;
      case 15:
        $list = [
          [260,[1,2],300,1],
          [261,[1,2],300,1],
          [262,[1,2],300,1],
          [264,[1,2],300,1]
        ];
      break;
    }
    return $list;
  }

  public static function heal($user = false) {
	  
	  if($user == false) {
		  
		  $user = $_SESSION["id"];
		  
	  }
	  
    $a = 1;
    $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE active = ? AND user_id = ?");
                    $stmt->bind_param("ii", $a, $user);
                    $stmt->execute();
                    $pokList = $stmt->get_result();
    while($poks = $pokList->fetch_assoc()){
      $poksA = explode(',',$poks['attacks']);
      if(isset($poksA[0])) {
        $stmt = Work::$sql->prepare("SELECT id,pp FROM base_atk WHERE id = ?");
                    $stmt->bind_param("i", $poksA[0]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pokList1 = $data->fetch_assoc();
        
        $pokList1 = $pokList1['pp'];
      }else{
        $pokList1 = '0';
      }
      if(isset($poksA[1])) {
        $stmt = Work::$sql->prepare("SELECT id,pp FROM base_atk WHERE id = ?");
                    $stmt->bind_param("i", $poksA[1]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pokList2 = $data->fetch_assoc();
        $pokList2 = $pokList2['pp'];
      }else{
        $pokList2 = '0';
      }
      if(isset($poksA[2])) {
        $stmt = Work::$sql->prepare("SELECT id,pp FROM base_atk WHERE id = ?");
                    $stmt->bind_param("i", $poksA[2]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pokList3 = $data->fetch_assoc();
        $pokList3 = $pokList3['pp'];
      }else{
        $pokList3 = '0';
      }
      if(isset($poksA[3])) {
        $stmt = Work::$sql->prepare("SELECT id,pp FROM base_atk WHERE id = ?");
                    $stmt->bind_param("i", $poksA[3]);
                    $stmt->execute();
                    $data = $stmt->get_result();
                    $pokList4 = $data->fetch_assoc();
        $pokList4 = $pokList4['pp'];
      }else{
        $pokList4 = '0';
      }
      $stats = explode(',',$poks['stats']);
      
      $sql1 = "UPDATE user_pokemons SET hp = ? WHERE id = ?";
        $stmta = Work::$sql->prepare($sql1);
        $stmta->bind_param("ii", $stats[0], $poks['id']);
        $update = $stmta->execute();
        $stmta->close();
        
      $sqls = "UPDATE user_pokemons SET pp_attacks = ? WHERE id = ?";
      $e = "$pokList1,$pokList2,$pokList3,$pokList4";
        $stmtx = Work::$sql->prepare($sqls);
        $stmtx->bind_param("si", $e, $poks['id']);
        $update1 = $stmtx->execute();
        $stmtx->close();
    }
  }

}

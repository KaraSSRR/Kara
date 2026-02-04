<?php

Class Issets {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'location':
			$val[0] = intval($val[0]);
			
	 	$stmt = Work::$sql->prepare('SELECT clan_control,id FROM base_location WHERE id = ?');
            $stmt->bind_param("i", $val[0]);
            $stmt->execute();
            $data = $stmt->get_result();
            $location = $data->fetch_assoc();
            
          if($location['clan_control'] != 0) {
              
            $clan = Work::$sql->query('SELECT name,id FROM base_clans WHERE id = '.$location['clan_control'])->fetch_assoc();
            
          }
          
          $this->response['response'] = array(
            'id' => $location['id'],
            'clan_control' => (isset($clan) ? [$clan['id'],$clan['name']] : 0)
          );
          
        break;

        /*case 'ability':
		$val[0] = intval($val[0]);
          $ability = Work::$sql->query('SELECT * FROM base_ability WHERE id = '.$val[0])->fetch_assoc();
          $this->response['response'] = array(
            'name' => $ability['name_rus'],
            'name_eng' => $ability['name'],
            'about' => $ability['about'],
            'id' => $ability['id']
          );
        break;*/

        case 'status':
          $this->response['response'] = array(
            'param' => (!empty($val[1]) ? $val[1] : ''),
            'type' => (isset($val[2]) ? $val[2] : 'Normal')
          );
        break;

        case 'auk':
		$val[0] = intval($val[0]);
          $stmtC = Work::$sql->prepare('SELECT * FROM auk WHERE id = ?');
                $stmtC->bind_param("i", $val[0]);
                $stmtC->execute();
                $dataC = $stmtC->get_result();
                $auk = $dataC->fetch_assoc();
                
          $this->response['response'] = array(
            'user_bet' => ($auk['user_bet'] != 0 ? Info::getMainUser(['id',$auk['user_bet']]) : 0),
            'now_bet' => Items::countItem($auk['now_bet'],0),
            'min_bet' => Items::countItem($auk['min_bet'],0)
          );
        break;

        case 'egg':
          switch($val[0]) {
            case 'my':
			$val[1] = intval($val[1]);
              $this->response['response'] = array(
                'egg' => Items::mainEgg($val[1])
              );
            break;
          }
        break;

        case 'item':
		$val[1] = intval($val[1]);
		
		include_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/numberFormat.php');
		
          switch($val[0]) {
            case 'view':
			
				$item = Items::viewItem($val[1]);
				
			
				$this->response['response'] = array(
					'item' => $item
				);
				
            break;
            case 'my':
			
				$item = Items::mainItem($val[1]);
				
			
				$this->response['response'] = array(
					'item' => $item,
				);
			
            break;
            case 'diamond':
			
				$item = Items::diamondItem($val[1]);
				
			
				$this->response['response'] = array(
					'item' => $item
				);
				
            break;
            case 'craft':
                
                $item = Items::craftItem($val[1],$val[2],$val[3],$val[4]);
			
			
				$this->response['response'] = array(
					'item' => $item
				);
				
            break;
          }
        break;

        case 'achievment':
            
          if(isset($val[1])) {
              
            $stmtC = Work::$sql->prepare("SELECT * FROM user_achievements WHERE id_ach = ? AND user_id = ?");
                $stmtC->bind_param("ii", $val[0], $val[1]);
                $stmtC->execute();
                $dataC = $stmtC->get_result();
                $AchievmentUser = $dataC->fetch_assoc();
                
          }
          
          $stmtCA = Work::$sql->prepare("SELECT * FROM base_achievements WHERE id = ?");
                $stmtCA->bind_param("i", $val[0]);
                $stmtCA->execute();
                $dataCA = $stmtCA->get_result();
                $Achievment = $dataCA->fetch_assoc();
                
          $this->response['response'] = array(
            'name' => $Achievment['name'],
            'about' => $Achievment['about'],
            'id' => $Achievment['id'],
            'category' => $Achievment['category'],
            'need' => $Achievment['need'],
            'user' => (isset($val[1]) ? 1 : 0),
            'count' => (isset($AchievmentUser) ? $AchievmentUser['count'] : 0)
          );
          
        break;

        case 'attack':
		
		  intval($val[0]);
		
          $Atk = Info::getMainAttack($val[0]);
		  
          $this->response['response'] = array(
            'name' => $Atk['name'],
            'name_rus' => $Atk['name_rus'],
            'tech' => $Atk['tech'],
            'pp' => $Atk['pp'],
            'about' => $Atk['about'],
            'category' => $Atk['category'],
            'priority' => $Atk['priority'],
            'type' => $Atk['type'],
            'power' => $Atk['power'],
            'accuracy' => $Atk['accuracy'],
            'contact' => $Atk['contact'],
            'bite' => $Atk['bite'],
			'sound' => $Atk['sound'],
			'punch' => $Atk['punch'],
			'pulse' => $Atk['pulse']
          );
		  
        break;

      }

    }

  }

}

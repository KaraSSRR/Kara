<?php

Class Trainers {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'search':
		
          $stmt = Work::$sql->prepare("SELECT id, online FROM users WHERE login LIKE ? ORDER BY online DESC");
          $a = "%$val%";
                    $stmt->bind_param("s", $a);
                    $stmt->execute();
                    $Trainers = $stmt->get_result();
          $TrainersList = [];
          while($Trainer = $Trainers->fetch_assoc()) {
            $User = Info::getMainUser(['id',$Trainer['id']]);
            $TrainersList[$Trainer['online']] = [
              'User' => Info::getMainUser(['id',$Trainer['id']])
            ];
          }
          $this->response['response'] = array(
            'trainer_list' => $TrainersList
          );
        break;

        case 'tabs':
          switch($val){
            case 7:
              $stmt = Work::$sql->prepare("SELECT * FROM users_friend WHERE status = 1 AND (user_id = ? OR friend_id = ?)");
                    $stmt->bind_param("ii", $userInfo['id'], $userInfo['id']);
                    $stmt->execute();
                    $Friends = $stmt->get_result();
                    
              $FriendsList = [];
              while($Friend = $Friends->fetch_assoc()) {
                $Id = ($Friend['user_id'] == $userInfo['id'] ? $Friend['friend_id'] : $Friend['user_id']);
                $User = Info::getMainUser(['id',$Id]);
                $User[2] = Info::getRegion(['id',Info::getLocation(['id',Info::getStringUser(['id',$Id,'location']),'region']),'name']).', '.Info::getLocation(['id',Info::getStringUser(['id',$Id,'location']),'name']);
                $FriendsList[$Friend['id']] = [
                  'User' => $User
                ];
              }
              $this->response['response'] = array(
                'trainer_list' => $FriendsList
              );
            break;
            case 6:
              $Trainers = Work::$sql->query("SELECT * FROM users");
              $TrainersList = [];
              while($Trainer = $Trainers->fetch_assoc()) {
                $User = Info::getMainUser(['id',$Trainer['id']]);
                if($User[4] == 'Online') {
                  $TrainersList[$Trainer['id']] = [
                    'User' => Info::getMainUser(['id',$Trainer['id']])
                  ];
                }
              }
              $this->response['response'] = array(
                'trainer_list' => $TrainersList
              );
            break;
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
              $stmt = Work::$sql->prepare("SELECT * FROM users WHERE user_group = ?");
                    $stmt->bind_param("i", $val);
                    $stmt->execute();
                    $Trainers = $stmt->get_result();
              $TrainersList = [];
              while($Trainer = $Trainers->fetch_assoc()) {
                $TrainersList[$Trainer['id']] = [
                  'User' => Info::getMainUser(['id',$Trainer['id']])
                ];
              }
              $this->response['response'] = array(
                'trainer_list' => $TrainersList
              );
            break;
          }
        break;

      }

    }

  }

}

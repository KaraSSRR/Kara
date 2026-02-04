<?php

Class Edit {

  //private $User;

  private $response = [];

  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    //$this->User = new User;

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'pass':
          $new_pass = Work::$sql->query("SELECT * FROM new_pass WHERE user = '".$userInfo['id']."'")->fetch_assoc();
          if($userInfo['password'] == md5($val[0]) || $new_pass && $val[0] == $new_pass['pass']){
            if($val[1] != $val[2]) {
              $this->response['text'] = 'Неверно введен новый пароль. Повторите попытку.';
              $this->response['error'] = 1;
            }else{
              $password = Work::$sql->real_escape_string($val[1]);
      				$password = htmlspecialchars($password);
      				$password = trim($password);
      				$password = md5($password);
              Work::$sql->query("UPDATE users SET password = '".$password."' WHERE id = '".$userInfo['id']."'");
              Work::$sql->query("DELETE FROM new_pass WHERE user = ".$userInfo['id']);
              $this->response['error'] = 0;
            }
          }else{
            $this->response['text'] = 'Неверно введен старый пароль. Повторите попытку.';
            $this->response['error'] = 1;
          }
        break;

        case 'audio':
          if($userInfo['sound'] == 0) {
            Work::$sql->query('UPDATE users SET sound = 1 WHERE id = '.$userInfo['id']);
          }else{
            Work::$sql->query('UPDATE users SET sound = 0 WHERE id = '.$userInfo['id']);
          }
          $this->response['error'] = 0;
        break;

        case 'mission':
          if($userInfo['mission_day'] == 0) {
            Work::$sql->query('UPDATE users SET mission_day = 1 WHERE id = '.$userInfo['id']);
          }else{
            Work::$sql->query('UPDATE users SET mission_day = 0 WHERE id = '.$userInfo['id']);
          }
          $this->response['error'] = 0;
        break;
        
        case 'hotclick':
          if($userInfo['HotClick'] == 0) {
            Work::$sql->query('UPDATE users SET HotClick = 1 WHERE id = '.$userInfo['id']);
          }else{
            Work::$sql->query('UPDATE users SET HotClick = 0 WHERE id = '.$userInfo['id']);
          }
          $this->response['error'] = 0;
        break;
        
        case 'boss_battle':
          if($userInfo['boss_battle'] == 0) {
            Work::$sql->query('UPDATE users SET boss_battle = 1 WHERE id = '.$userInfo['id']);
          }else{
            Work::$sql->query('UPDATE users SET boss_battle = 0 WHERE id = '.$userInfo['id']);
          }
          $this->response['error'] = 0;
        break;

        case 'team':
          if($userInfo['team_open'] == 0) {
            Work::$sql->query('UPDATE users SET team_open = 1 WHERE id = '.$userInfo['id']);
          }else{
            Work::$sql->query('UPDATE users SET team_open = 0 WHERE id = '.$userInfo['id']);
          }
          $this->response['error'] = 0;
        break;

        case 'color':
          $color = ($val[0] <= 6 ? $val[0] : 1);
          Work::$sql->query('UPDATE users SET colorChat = '.$color.' WHERE id = '.$userInfo['id']);
          $this->response['error'] = 0;
        break;
        case 'inv':
          $filt = ($val[0] <= 6 ? $val[0] : 1);
          Work::$sql->query('UPDATE users SET items_filter = '.$filt.' WHERE id = '.$userInfo['id']);
          $this->response['error'] = 0;
        break;

      }

      return $this->response;

    }

    return false;

  }

}

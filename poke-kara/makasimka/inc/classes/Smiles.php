<?php

Class Smiles {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        /* case 'buy':
          if(in_array($val, ['cat','spider','enot','cat2'])) {
            $smile = Work::$sql->query("SELECT * FROM user_smiles WHERE user = ".$userInfo['id']." AND smiles = '".$val."'")->fetch_assoc();
            if(isset($smile)) {
              $error = 2;
            }else{
              if(item_isset(43,3)) {
                $error = 3;
                $minus = Items::arrayItem([43,[3,1]]);
                minus_item(43,3);
                Work::$sql->query("INSERT INTO user_smiles (user,smiles) VALUES (".$userInfo['id'].",'".$val."') ");
              }else{
                $error = 1;
              }
            }
          }else{
            $error = 2;
          }
          $this->response['response'] = array(
            'error' => $error,
            'minus' => (isset($minus) ? $minus : 0)
          );
        break; */

        case 'load':
          switch($val) {
            case 'standart':
              $smile_count = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25];
            break;
            case 'fruit':
              $smile_count = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19];
            break;
            case 'cat':
              $smile_count = [1,2,3,4,5,6,7,8,9,10,11];
            break;
            case 'spider':
              $smile_count = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26];
            break;
            case 'enot':
              $smile_count = [1,2,3,4,5,6,7,8,9,10,11];
            break;
            case 'cat2':
              $smile_count = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25];
            break;
          }
		  
		  $buy = 1;
		  
          /* if(in_array($val, ['standart','fruit'])) {
            $buy = 1;
          }else{
            $smile = Work::$sql->query("SELECT * FROM user_smiles WHERE user = ".$userInfo['id']." AND smiles = '".$val."'")->fetch_assoc();
            if(isset($smile)) {
              $buy = 1;
            }else{
              $buy = 0;
            }
          } */
		  
          $this->response['response'] = array(
            'smile_list' => $smile_count,
            'buy' => $buy
          );
        break;

      }

    }

  }

}

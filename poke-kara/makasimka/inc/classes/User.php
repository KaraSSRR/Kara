<?php

Class User {

  private $user;

  public function SearchUser($id, $val = []) {
    $this->user = Work::$sql->query('SELECT '.$id.' FROM users WHERE '.$val[0].' = '.$val[1])->fetch_assoc();
    return $this->user;
  }

}

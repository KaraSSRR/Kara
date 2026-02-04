<?php

Class Diamonds {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'trade':
          $login = Work::$sql->query('SELECT id FROM users WHERE login = "'.$val[0].'"')->fetch_assoc();
          if(isset($login)) {
            $count = abs(intval($val[1]));
            $count = $count + 3;
            if(item_isset(43,$count)) {
              minus_item(43,$count);
              $count2 = $count - 3;
              itemAdd(43,$count2,$login['id']);
              $minus = Items::arrayItem([43,[$count2,1]]);
              $about = clearStr($val[2]);
              
              $stmtW = Work::$sql->prepare("INSERT INTO diamond_trade (count,user1,user2,about,data) VALUES (?,?,?,?,?) "); 
              $TIME = time();
                        $stmtW->bind_param("iissi", $count2, $userInfo['id'], $login['id'], $about, $TIME);
                        $stmtW->execute();
                        
              $error = 3;
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

        case 'buyPack':
          $stmtbP = Work::$sql->prepare('SELECT * FROM base_pack WHERE close = ? AND id = ?');
          $b = 0;
            $stmtbP->bind_param("ii", $b, $val);
            $stmtbP->execute();
            $databP = $stmtbP->get_result();
            $Pack = $databP->fetch_assoc();
            
            $stmtMP = Work::$sql->prepare('SELECT * FROM user_pack WHERE user = ? AND pack = ?');
                $stmtMP->bind_param("ii", $userInfo['id'], $Pack['id']);
                $stmtMP->execute();
                $dataMP = $stmtMP->get_result();
                $MyPack = $dataMP->fetch_assoc();
                
          if(isset($MyPack)) {
              
            $error = 2;
            
          }else{
              
            if(item_isset(43,$Pack['price'])) {
                
              $Loot = json_decode($Pack['loot']);
              $plus = [];
              
              foreach($Loot as $key => $value) {
                  
                itemAdd($key,$value);
                array_push($plus, Items::arrayItem([$key,[$value,1]]));
                
              }
              
            $stmtD = Work::$sql->prepare("INSERT INTO user_pack (user,pack) VALUES (?,?) "); 
                $stmtD->bind_param("ii", $userInfo['id'], $Pack['id']);
                $stmtD->execute();
              $error = 3;
              $minus = Items::arrayItem([43,[$Pack['price'],1]]);
              minus_item(43,$Pack['price']);
            }else{
              $error = 1;
            }
          }
          $this->response['response'] = array(
            'error' => $error,
            'minus' => (isset($minus) ? $minus : 0),
            'plus' => (isset($plus) ? $plus : 0)
          );
        break;

        case 'checkPack':
          $stmtbP = Work::$sql->prepare('SELECT * FROM base_pack WHERE id = ?');
            $stmtbP->bind_param("i", $val);
            $stmtbP->execute();
            $databP = $stmtbP->get_result();
            $Pack = $databP->fetch_assoc();
          $LootList = [];
          $Loot = json_decode($Pack['loot']);
          foreach($Loot as $key => $value) {
            array_push($LootList, Items::arrayItem([$key,[$value,1]]));
          }
          $this->response['response'] = array(
            'loot_list' => $LootList
          );
        break;

        case 'openPack':
          $stmtbP = Work::$sql->prepare('SELECT * FROM base_pack WHERE close = ?');
          $b = 0;
            $stmtbP->bind_param("i", $b);
            $stmtbP->execute();
            $Packs = $stmtbP->get_result();
          $PackList = [];
          while($Pack = $Packs->fetch_assoc()) {
            $stmMP = Work::$sql->prepare('SELECT * FROM user_pack WHERE user = ? AND pack = ?');
                $stmMP->bind_param("ii", $userInfo['id'], $Pack['id']);
                $stmMP->execute();
                $dataMP = $stmMP->get_result();
                $MyPack = $dataMP->fetch_assoc();
            if(!isset($MyPack)) {
              $PackList[$Pack['id']] = [
                'id' => $Pack['id'],
                'price' => $Pack['price']
              ];
            }
          }
          $this->response['response'] = array(
            'pack_list' => $PackList
          );
        break;

        case 'buy':
            
          $stmtI = Work::$sql->prepare('SELECT * FROM diamonds WHERE id = ?');
            $stmtI->bind_param("i", $val[0]);
            $stmtI->execute();
            $dataI = $stmtI->get_result();
            $Item = $dataI->fetch_assoc();
            
          if(isset($Item)) {
              
            $count = intval($val[1]);
            $countAll = $count * $Item['price'];
            
            if($count > 0) {
                
              if(item_isset(43,$countAll)) {
                  
                if($Item['left_count'] >= $count || $Item['left_count'] == "-1") {
                    
                  minus_item(43,$countAll);
                  
                  if($Item['num'] != 54) {
                      
                    itemAdd($Item['num'],($count * $Item['count']));
                    
                  }else{
                      
                    $i = 1;
                    
                    while($i <= $count) {
                      plusEgg(false,false,false,false,false,$Item['val'],true);
                      $i++;
                      
                    }
                  }
                  $item = Items::arrayItem([43,[$countAll,1]]);
                  $item2 = Items::arrayItem([$Item['num'],[($count * $Item['count']),1]]);
                  $error = 2;
                  if($Item['left_count'] != "-1") {
                      
                    $sql2 = "UPDATE diamonds SET left_count = left_count - ? WHERE id = ?";
                        $stmt2 = Work::$sql->prepare($sql2);
                        $stmt2->bind_param("ii", $count, $Item['id']);
                        $stmt2->execute();
                        
                  }
                }else{
                  $error = 3;
                }
              }else{
                $error = 1;
              }
            }else{
              $error = 4;
            }
          }else{
            $error = 1;
          }
          $this->response['response'] = array(
            'error' => $error,
            'item' => (isset($item) ? $item : 0),
            'item2' => (isset($item2) ? $item2 : 0)
          );
        break;

        case 'open':
          $stmtI = Work::$sql->prepare('SELECT * FROM diamonds');
            $stmtI->execute();
            $Items = $stmtI->get_result();
            
          $ItemList = [];
          
          while($Item = $Items->fetch_assoc()) {
              
            $ItemList[$Item['id']] = [
              'category' => $Item['category'],
              'type' => $Item['type'],
              'num' => $Item['num'],
              'price' => $Item['price'],
              'val' => Info::getNumPokemonNum($Item['val']),
              'count' => Items::countItem($Item['count'],0),
              'left_count' => $Item['left_count'],
              'id' => $Item['id']
            ];
            
          }
          
          $stmtd = Work::$sql->prepare('SELECT count FROM items_users WHERE item_id = 43 AND user = ?');
            $stmtd->bind_param("i", $userInfo['id']);
            $stmtd->execute();
            $datad = $stmtd->get_result();
            $diamonds = $datad->fetch_assoc();
            
          $this->response['response'] = array(
            'item_list' => $ItemList,
            'diamonds' => (isset($diamonds) ? $diamonds['count'] : 0)
          );
        break;

      }

    }

  }

}

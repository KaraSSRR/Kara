<?php

Class Battlepass {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;
      $this->userInfo =& $userInfo;

      switch($_POST['type']){
        case 'getPrize':
          $val = intval($val);
          
          $stmtMP = Work::$sql->prepare("SELECT * FROM battlepass_user WHERE prize = ? AND user = ?");
            $stmtMP->bind_param("si", $val, $userInfo['id']);
            $stmtMP->execute();
            $dataMP = $stmtMP->get_result();
            $myPrizes = $dataMP->fetch_assoc();
            
          if(isset($myPrizes)) {
              
            $error = 1;
            
          }else{
              
            if(Battlepass::getLvl($userInfo['battlepass'],1) < $val) {
                
              $error = 2;
              
            }else{
                
			  $prize = Battlepass::allPrizes();
			  $prize = $prize[$val];
			  if(isset($prize[5]) && $prize[5] == 'gold') {
			      
				  if($userInfo['battlepass_gold'] == 1) {
				      
					  $prizeGo = 1;
					  
				  }else{
				      
					  $prizeGo = 0;
					  
				  }
				  
			  }else{
			      
				  $prizeGo = 1;
				  
			  }
			  
			  if($prizeGo == 1) {
				  $error = 3;
	              if($prize[1] == 'item') {
	                $plus = [Items::arrayItem([$prize[2],[$prize[3],1]])];
	                itemAdd($prize[2],$prize[3]);
	                $stmtI = Work::$sql->prepare("INSERT INTO battlepass_user (prize,user) VALUES (?,?) "); 
                        $stmtI->bind_param("si", $val, $userInfo['id']);
                        $stmtI->execute();
                        
	              }else{
	                $plus = [
	                  'num' => Info::getNumPokemonNum($prize[2]),
	                  'lvl' => 1,
	                  'name' => Info::getPokemonBase(['id',$prize[2],'name_rus'])
	                ];
	                newPokemon($prize[2],$userInfo['id'],1,false,0,'true',1,false,false,false,false,true);
	                $stmtI = Work::$sql->prepare("INSERT INTO battlepass_user (prize,user) VALUES (?,?) "); 
                        $stmtI->bind_param("si", $val, $userInfo['id']);
                        $stmtI->execute();
	              }
			  }else{
				  $error = 4;
			  }
            }
          }
          $this->response['response'] = array(
            'error' => $error,
            'plus' => (isset($plus) ? $plus : 0),
          );
        break;
		case 'buyDiamond':
          $count = intval($val);
          if($count <= 0) {
            $error = 1;
          }else{
            $diamond = $count * 3;
            if(item_isset(43,$diamond)) {
              $error = 3;
              $item = Items::arrayItem([43,[$diamond,1]]);
              $lvl = $count * 500;
              minus_item(43,$diamond);
              $sql = "UPDATE users SET battlepass = battlepass + ? WHERE id = ?";
                $stmtU = Work::$sql->prepare($sql);
                $stmtU->bind_param("ii", $lvl, $userInfo['id']);
                $stmtU->execute();
            }else{
              $error = 2;
            }
          }
          $this->response['response'] = array(
            'item' => (isset($item) ? $item : 0),
            'error' => $error
          );
        break;
		case 'buyGold':
	        if(item_isset(43,35)) {
				if($userInfo['battlepass_gold'] == 0) {
		  	        $item = Items::arrayItem([43,[35,1]]);
		  	        minus_item(43,35);
		  	        $up = 1;
		  	        
                    $stmt = Work::$sql->prepare("UPDATE users SET battlepass_gold = ? WHERE id = ?");
                        $stmt->bind_param("ii", $up, $userInfo['id']);
                        $stmt->execute();
                        
					$error = 3;
				}else{
				   $error = 2;
				}
	        }else{
	          $error = 1;
	        }
          $this->response['response'] = array(
            'item' => (isset($item) ? $item : 0),
            'error' => $error
          );
        break;
		case 'openway':
			$bw = Info::_unParseData($userInfo['battlepass_task']);
			$this->response['response'] = array(
              'tm' => (isset($bw['task']['tm']) ? (20 - $bw['task']['tm']) : 20),
			  'rune' => (isset($bw['task']['rune']) ? (3 - $bw['task']['rune']) : 3),
			  'sparka' => (isset($bw['task']['sparka']) ? (100 - $bw['task']['sparka']) : 100),
			  'lvl' => (isset($bw['task']['lvl']) ? (600 - $bw['task']['lvl']) : 600),
            );
		break;
        case 'open':
          $myPrizes = Battlepass::allPrizes();
          $Prizes = [];
          foreach($myPrizes as $key => $val) {
              
            $stmtMP = Work::$sql->prepare("SELECT * FROM battlepass_user WHERE prize = ? AND user = ?");
                $stmtMP->bind_param("ii", $key, $userInfo['id']);
                $stmtMP->execute();
                $dataMP = $stmtMP->get_result();
                $myPrizes = $dataMP->fetch_assoc();
                
            if(!isset($myPrizes)) {
              $val[3] = Items::arrayItem([$val[2],[$val[3],1]]);
			  $val[6] = (isset($val[5]) && $val[5] == 'gold' ? 1 : 0);
              array_push($Prizes, $val);
            }
          }
          $stmtQ = Work::$sql->prepare("SELECT * FROM battlepass_quests WHERE user = ?");
                $stmtQ->bind_param("i", $userInfo['id']);
                $stmtQ->execute();
                $Quests = $stmtQ->get_result();
                
          $QuestList = [];
          while($q = $Quests->fetch_assoc()) {
            $value = explode(',',$q['val']);
            /*if($value[0] == 'ability') {
              $QuestList[$q['id']] = [
                'type' => 'ability',
                'id' => $value[1],
                'name' => Info::getAbilityBase(['id',$value[1],'name_rus'])
              ];
            }*/
            if($value[0] == 'evol') {
              $QuestList[$q['id']] = [
                'type' => 'evol'
              ];
            }
            if($value[0] == 'genoball') {
              $QuestList[$q['id']] = [
                'type' => 'genoball'
              ];
            }
            if($value[0] == 'sex') {
              $QuestList[$q['id']] = [
                'type' => 'sex'
              ];
            }
          }
          $this->response['response'] = array(
            'prizes' => $Prizes,
            'lvl' => Battlepass::getLvl($userInfo['battlepass'],1),
            'exp' => Battlepass::getLvl($userInfo['battlepass'],2),
            'quest_list' => $QuestList,
			'gold' => $userInfo['battlepass_gold']
          );
        break;

      }

    }

  }

  public static function Quest($user) {
    $quest_rand = ['evol','sex','genoball'];
    shuffle($quest_rand);
    $region = Info::getLocation(['id',Info::getStringUser(['id',$user,'location']),'region']);
    /*if($quest_rand[0] == 'ability') {
      if($region == 7) {
        $randAbility = [22,28,33,61,70,77,79,81,89,124,146,157,161,170,171,174,196,200,211,225];
      }elseif($region == 9) {
        $randAbility = [7,22,45,46,63,89,93,95,98,99,107,124,130,155,157,167,168,171,189,195,196,220];
	  }elseif($region == 2) {
        $randAbility = [161,22,171,162,93,157,32,207,223,6,130,30,170,124,7,202,194,120,146,87,80,178,165,155,196,98,184,28,173,219,205,21,168,72,152,198,33,225,210,78,79,167,89,61,90,189];
      }else{
        $randAbility = [22,28,45,47,52,63,70,87,88,89,93,106,107,120,130,134,157,161,168,174,178,183,196,200,202];
      }
      shuffle($randAbility);
      Work::$sql->query("INSERT INTO battlepass_quests (user,quest_id,val) VALUES (".$user.",1,'ability,".$randAbility[0]."') ");
    }else{
      Work::$sql->query("INSERT INTO battlepass_quests (user,quest_id,val) VALUES (".$user.",1,'".$quest_rand[0]."') ");
    }*/
    /*if($quest_rand[1] == 'ability') {
		if($region == 7) {
	      $randAbility = [22,28,33,61,70,77,79,81,89,124,146,157,161,170,171,174,196,200,211,225];
	    }elseif($region == 9) {
	      $randAbility = [7,22,45,46,63,89,93,95,98,99,107,124,130,155,157,167,168,171,189,195,196,220];
		  }elseif($region == 2) {
	      $randAbility = [161,22,171,162,93,157,32,207,223,6,130,30,170,124,7,202,194,120,146,87,80,178,165,155,196,98,184,28,173,219,205,21,168,72,152,198,33,225,210,78,79,167,89,61,90,189];
	    }else{
	      $randAbility = [22,28,45,47,52,63,70,87,88,89,93,106,107,120,130,134,157,161,168,174,178,183,196,200,202];
	    }
      shuffle($randAbility);
      Work::$sql->query("INSERT INTO battlepass_quests (user,quest_id,val) VALUES (".$user.",2,'ability,".$randAbility[0]."') ");
    }else{
      Work::$sql->query("INSERT INTO battlepass_quests (user,quest_id,val) VALUES (".$user.",2,'".$quest_rand[1]."') ");
    }
    if($quest_rand[2] == 'ability') {
		if($region == 7) {
          $randAbility = [22,28,33,61,70,77,79,81,89,124,146,157,161,170,171,174,196,200,211,225];
        }elseif($region == 9) {
          $randAbility = [7,22,45,46,63,89,93,95,98,99,107,124,130,155,157,167,168,171,189,195,196,220];
  	  }elseif($region == 2) {
          $randAbility = [161,22,171,162,93,157,32,207,223,6,130,30,170,124,7,202,194,120,146,87,80,178,165,155,196,98,184,28,173,219,205,21,168,72,152,198,33,225,210,78,79,167,89,61,90,189];
        }else{
          $randAbility = [22,28,45,47,52,63,70,87,88,89,93,106,107,120,130,134,157,161,168,174,178,183,196,200,202];
        }
      shuffle($randAbility);
      Work::$sql->query("INSERT INTO battlepass_quests (user,quest_id,val) VALUES (".$user.",3,'ability,".$randAbility[0]."') ");
    }else{
      Work::$sql->query("INSERT INTO battlepass_quests (user,quest_id,val) VALUES (".$user.",3,'".$quest_rand[2]."') ");
    }*/
  }

  public static function getExp($exp, $id = null) {

    if($id == null){
      $id = $_SESSION['id'];
    }
    $sql = "UPDATE users SET battlepass = battlepass + ? WHERE id = ?";
        $stmt = Work::$sql->prepare($sql);
        $stmt->bind_param("ii", $exp, $id);
        $stmt->execute();
        $stmt->close();
  }

  public static function getLvl($exp,$type) {
    if($type == 1) {
      return floor($exp / 500);
    }else{
      return 500 - (((floor($exp / 500) * 500) + 500) - $exp);
    }
  }

  public static function allPrizes() {
    $prizes = [
	  1 => ['big','pokemon',238,1,1,14],
      2 => ['small','item',1,15000,2],
      3 => ['small','item',17,5,3],
      5 => ['small','item',33,10,5],
      8 => ['small','item',42,5,8],
      10 => ['small','item',63,1,10],
      11 => ['small','item',127,2,11,'gold'],
      12 => ['small','item',75,1,12],
      14 => ['small','item',1,30000,14],
      15 => ['small','item',27,1,15],
      17 => ['small','item',37,5,17],
      19 => ['small','item',127,2,19],
	  21 => ['small','item',33,20,21,'gold'],
      28 => ['small','item',40,5,28],
      30 => ['small','item',1,50000,30],
      32 => ['small','item',25,1,32],
      35 => ['small','item',82,1,35],
      37 => ['small','item',100,1,37],
	  38 => ['small','item',11,3,38,'gold'],
      43 => ['small','item',104,1,43],
      45 => ['small','item',79,1,45],
      48 => ['small','item',39,5,48],
	  50 => ['big','pokemon',123,1,50,11],
	  52 => ['small','item',127,2,52],
	  55 => ['small','item',30,1,55],
	  60 => ['small','item',1,65000,60],
	  65 => ['small','item',38,5,65],
	  67 => ['small','item',26,1,67],
	  69 => ['small','item',11,1,69],
	  72 => ['small','item',33,15,72],
	  75 => ['small','item',105,1,75],
	  77 => ['small','item',51,1,77],
	  80 => ['small','item',5,10,80],
	  83 => ['small','item',2,1,83],
	  90 => ['small','item',1,80000,90],
	  100 => ['big','pokemon',215,1,100,9],
	  102 => ['small','item',1015,1,102],
	  103 => ['small','item',1,300000,103,'gold'],
	  106 => ['small','item',33,25,106],
	  115 => ['small','item',288,1,115],
	  //122 => ['small','item',1,100000,122],
	  122 => ['small','item',281,2,122],
	  125 => ['small','item',103,1,125],
	  129 => ['small','item',52,1,129],
	  133 => ['small','item',31,1,133],
	  136 => ['small','item',12,1,136],
	  140 => ['small','item',19,1,140],
	  142 => ['small','item',64,1,142],
	  144 => ['small','item',95,1,144],
	  148 => ['small','item',115,5,148],
	  150 => ['small','item',127,3,150],
	  152 => ['small','item',136,1,152],
	  158 => ['small','item',1025,1,158],
	  164 => ['small','item',106,1,164],
	  166 => ['small','item',33,20,166],
	  167 => ['small','item',95,2,167,'gold'],
	  168 => ['small','item',99,1,168],
	  170 => ['small','item',11,2,170],
	  173 => ['small','item',160,5,173],
	  185 => ['small','item',1042,1,185],
	  187 => ['small','item',21,1,187],
	  190 => ['small','item',41,5,190],
	  192 => ['small','item',62,1,192],
	  195 => ['small','item',73,1,195],
	  200 => ['big','pokemon',133,1,200,13],
	  202 => ['small','item',127,3,202],
	  205 => ['small','item',1,125000,205],
	  208 => ['small','item',33,20,208],
	  209 => ['small','item',127,5,209,'gold'],
	  211 => ['small','item',1063,1,211],
	  240 => ['small','item',288,1,240],
	  242 => ['small','item',1,150000,242],
	  //245 => ['small','item',309,5,245],
	  245 => ['small','item',282,3,245],
	  248 => ['small','item',1010,1,248],
	  250 => ['small','item',71,1,250],
	  253 => ['small','item',5,15,253],
	  258 => ['small','item',28,1,258],
	  261 => ['small','item',98,1,261],
	  265 => ['small','item',11,2,265],
	  267 => ['small','item',109,5,267],
	  275 => ['small','item',161,1,275],
	  279 => ['small','item',149,3,279],
	  282 => ['small','item',160,10,282],
	  288 => ['small','item',127,5,288],
	  300 => ['big','pokemon',551,1,300,10],
	  302 => ['small','item',74,1,302],
	  310 => ['small','item',17,100,310],
	  313 => ['small','item',71,1,313],
	  317 => ['small','item',53,1,317],
	  320 => ['small','item',11,3,320],
	  325 => ['small','item',127,7,325],
	  330 => ['small','item',1094,1,330],
	  333 => ['small','item',33,50,333],
	  343 => ['small','item',81,1,343],
	  348 => ['small','item',100,1,348],
	  351 => ['small','item',162,1,351],
	  360 => ['small','item',1,250000,360],
	  362 => ['small','item',1002,1,362],
	  376 => ['small','item',115,10,376],
	  380 => ['small','item',1,300000,380],
	  386 => ['small','item',1051,1,386],
	  390 => ['small','item',23,1,390],
	  393 => ['small','item',72,1,393],
	  400 => ['big','pokemon',570,1,400,12],
	  401 => ['small','item',127,10,401,'gold'],
	  410 => ['small','item',11,6,410],
	  420 => ['small','item',66,1,420],
	  425 => ['small','item',65,1,425],
	  430 => ['small','item',127,10,430],
	  445 => ['small','item',95,5,445],
	  485 => ['small','item',127,15,485],
	  490 => ['small','item',5,15,490],
	  495 => ['small','item',1004,1,495],
	  500 => ['big','pokemon',701,1,500,8],
    ];
    return $prizes;
  }


}

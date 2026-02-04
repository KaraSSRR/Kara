<?php

Class Craft {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){
        case 'action':
          switch($val[0]) {
            case 'alchimy':
            case 'ingener':
            case 'food':
              $count = intval($val[4]);
              if($count <= 0) {
                $error = [1,'error'];
              }else{
                $Ingr = Craft::ingr($val[0]);
                foreach($Ingr as $IngrItem) {
                  if($IngrItem[0][0] == $val[1]) {
                    $MinusItem = [];
                    $IngrItemTwo = 0;
                    foreach($IngrItem[1] as $IngrItemCount) {
                      if($IngrItemTwo != 2) {
                        if($IngrItemCount[0] == 'item') {
                          if(item_isset($IngrItemCount[1],($IngrItemCount[2]*$count))) {
                            $IngrItemTwo = 1;
                            array_push($MinusItem,$IngrItemCount);
                          }else{
                            $IngrItemTwo = 2;
                          }
                        }elseif($IngrItemCount[0] == 'quest') {
                          if(quest_isset_a($IngrItemCount[1])) {
                            if(quest_step_a($IngrItemCount[1]) >= $IngrItemCount[2]) {
                              $IngrItemTwo = 1;
                            }else{
                              $IngrItemTwo = 2;
                            }
                          }else{
                            $IngrItemTwo = 2;
                          }
                        }elseif($IngrItemCount[0] == 'location') {
                          if($userInfo['location'] == $IngrItemCount[1]) {
                            $IngrItemTwo = 1;
                          }else{
                            $IngrItemTwo = 2;
                          }
                        }
                      }
                    }
                    if(in_array($IngrItemTwo, [0,2])) {
                      $error = [1,'error'];
                    }else{
                      $minus = [];
                      foreach($MinusItem as $MI) {
                        array_push($minus, Items::arrayItem([$MI[1],[($MI[2]*$count),1]]));
                        minus_item($MI[1],($MI[2]*$count));
                      }
                      itemAdd($val[1],$count);
                      $plus = Items::arrayItem([$val[1],[$count,1]]);
                      $error = [2,'success'];
                    }
                  }
                }
              }
            break;
            case 'remont':
              switch($val[1]) {
                case 104:
                  if(item_isset(252,1)) {
                    $stmt = Work::$sql->prepare("SELECT id,str FROM items_users WHERE item_id = ? AND user = ?");
                    $a = 105;
                        $stmt->bind_param("ii", $a, intval($userInfo['id']));
                        $stmt->execute();
                        $ItemNeed = $stmt->get_result();
                    $StrItem = [];
                    while($i = $ItemNeed->fetch_assoc()) {
                      $str = explode(',',$i['str']);
                      if($str[0] <= 0) {
                        array_push($StrItem, $i['id']);
                      }
                      if(count($StrItem) >= 1) {
                        $ItemComp = 1;
                        break;
                      }
                    }
                    if(isset($ItemComp)){
                       $q = Work::$sql->prepare("DELETE FROM items_users WHERE id = ?"); 
                        if($q)
                        {
                            $q->bind_param("i", intval($StrItem[0]));
                            $q->execute();
                           $q->close();
                        }
                      minus_item(252,1);
                      itemAdd(104,1);
                      $error = [2,'success'];
                      $minus = [Items::arrayItem([252,[1,1]]),Items::arrayItem([104,[1,1]])];
                      $plus = Items::arrayItem([104,[1,1]]);
                    }else{
                      $error = [1,'error'];
                    }
                  }else{
                    $error = [1,'error'];
                  }
                break;
                case 105:
                  if(item_isset(252,2)) {
                    $stmt = Work::$sql->prepare("SELECT id,str FROM items_users WHERE item_id = ? AND user = ?");
                    $a = 105;
                        $stmt->bind_param("ii", $a, $userInfo['id']);
                        $stmt->execute();
                        $ItemNeed = $stmt->get_result();
                    $StrItem = [];
                    while($i = $ItemNeed->fetch_assoc()) {
                      $str = explode(',',$i['str']);
                      if($str[0] <= 0) {
                        array_push($StrItem, $i['id']);
                      }
                      if(count($StrItem) >= 1) {
                        $ItemComp = 1;
                        break;
                      }
                    }
                    if(isset($ItemComp)){
                      $q = Work::$sql->prepare("DELETE FROM items_users WHERE id = ?"); 
                        if($q)
                        {
                            $q->bind_param("i", $StrItem[0]);
                            $q->execute();
                           $q->close();
                        }
                      minus_item(252,2);
                      itemAdd(105,1);
                      $error = [2,'success'];
                      $minus = [Items::arrayItem([252,[2,1]]),Items::arrayItem([105,[1,1]])];
                      $plus = Items::arrayItem([105,[1,1]]);
                    }else{
                      $error = [1,'error'];
                    }
                  }else{
                    $error = [1,'error'];
                  }
                break;
                case 103:
                  if(item_isset(187,1) && item_isset(253,1)) {
                    $stmt = Work::$sql->prepare("SELECT id,str FROM items_users WHERE item_id = ? AND user = ?");
                    $a = 103;
                        $stmt->bind_param("ii", $a, $userInfo['id']);
                        $stmt->execute();
                        $ItemNeed = $stmt->get_result();
                    $StrItem = [];
                    while($i = $ItemNeed->fetch_assoc()) {
                      $str = explode(',',$i['str']);
                      if($str[0] <= 0) {
                        array_push($StrItem, $i['id']);
                      }
                      if(count($StrItem) >= 1) {
                        $ItemComp = 1;
                        break;
                      }
                    }
                    if(isset($ItemComp)){
                      $q = Work::$sql->prepare("DELETE FROM items_users WHERE id = ?"); 
                        if($q)
                        {
                            $q->bind_param("i", $StrItem[0]);
                            $q->execute();
                           $q->close();
                        }
                      minus_item(253,1);
                      minus_item(187,1);
                      itemAdd(103,1);
                      $error = [2,'success'];
                      $minus = [Items::arrayItem([187,[1,1]]),Items::arrayItem([103,[1,1]]),Items::arrayItem([253,[1,1]])];
                      $plus = Items::arrayItem([103,[1,1]]);
                    }else{
                      $error = [1,'error'];
                    }
                  }else{
                    $error = [1,'error'];
                  }
                break;
              }
            break;
          }
          $this->response['response'] = array(
            'error' => $error,
            'plus' => (isset($plus) ? $plus : 0),
            'minus' => (isset($minus) ? $minus : 0)
          );
        break;
        case 'open':
          if(in_array($val,['remont','alchimy','ingener','food'])) {
            $this->response['response'] = array(
              'item_list' => Craft::ingr($val)
            );
          }
        break;

      }

    }

  }

  public static function ingr($craft) {
    switch($craft) {
		case 'food':
		
			$itemsCraft = [];
			
			/* for($i = 234; $i <= 251; ++$i) {
				
				$itemsCraft[] = [
				
					[$i, 1],
					
					[
						['item', 366, 7]
					]
					
				];
				
			} */
			
			$itemsCraft[] = [
				
				[32, 1],
				
				[
					['item', 366, 8]
				]
				
			];
			
			$itemsCraft[] = [
				
				[17, 1],
				
				[
					['item', 366, 5]
				]
				
			];
		
		break;
      case 'remont':
        $itemsCraft = [
          [
            [104,1],
            [
              ['item',104,1,'crash'],
              ['item',252,1]
            ]
          ],
          [
            [105,1],
            [
              ['item',105,1,'crash'],
              ['item',252,2]
            ]
          ],
          [
            [103,1],
            [
              ['item',187,1],
              ['item',103,1,'crash'],
              ['item',253,1]
            ]
          ]
        ];
      break;
      case 'ingener':
        $itemsCraft = [
          [
            [296,1],
            [
              ['item',252,2]
            ]
          ],
          [
            [297,1],
            [
              ['item',296,1],
              ['item',174,1]
            ]
          ],
          [
            [300,1],
            [
              ['item',296,1],
              ['item',186,1]
            ]
          ],
          [
            [301,1],
            [
              ['item',296,1],
              ['item',26,1]
            ]
          ],
		  [
            [254,1],
            [
              ['item',297,1],
              ['item',300,1],
              ['item',301,1],
              ['item',3,1],
              ['item',289,2]
            ]
	  	  ],
		  [
            [294,1],
            [
				['item',286,1],
	            ['item',289,2],
	            ['item',296,1]
            ]
	  	  ],
		  [
            [101,1],
            [
				['item',126,3]
            ]
	  	  ],
		  [
            [25,1],
            [
				['item',1108,10]
            ]
	  	  ],
		  [
            [26,1],
            [
				['item',1109,10]
            ]
	  	  ],
		  [
            [27,1],
            [
				['item',1110,10]
            ]
	  	  ],
		  [
            [28,1],
            [
				['item',1111,5]
            ]
	  	  ],
        ];
      break;
      case 'alchimy':
        $itemsCraft = [
          [
            [188,1],
            [
              ['item',175,1],
              ['item',115,5],
              //['quest',13,1],
              ['quest',3,3],
              //['location',30]
              ['location',15]
            ]
          ],
          [
            [145,1],
            [
              ['item',187,5],
              ['item',174,5],
              //['item',26,1],
              ['item',185,3],
              //['quest',13,1],
              ['quest',3,3],
              //['location',30]
              ['location',15]
            ]
          ],
          [
            [284,1],
            [
              ['item',174,3],
              ['item',285,1]
            ]
          ],
          [
            [35,1],
            [
              ['item',286,5]
            ]
          ],
          [
            [20,1],
            [
              ['item',284,1],
              ['item',35,1]
            ]
          ],
          [
            [287,1],
            [
              ['item',253,1],
              ['item',20,1],
              ['item',35,1]
            ]
          ],
          [
            [288,1],
            [
              ['item',287,1],
              ['item',285,1],
              ['item',7,10]
            ]
          ]
        ];
      break;
    }
    return $itemsCraft;
  }

}

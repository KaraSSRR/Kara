<?php

Class Choose {

  private $response = [];
  private $userInfo = [];
  private $List = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type[0]) {

      $this->response =& $response;

      if($userInfo['status'] == 'free') {

          switch($type[0]){

            case 'pokemon':

              $stmt = Work::$sql->prepare("SELECT * FROM user_pokemons WHERE user_id = ? AND active = ?");
              $a = 1;
                $stmt->bind_param("ii", $userInfo['id'], $a);
                $stmt->execute();
                $Pokemons = $stmt->get_result();

              while($Pok = $Pokemons->fetch_assoc()) {
                $this->List[$Pok['id']] = [
                  'num' => $Pok['basenum'],
                  'basenum' => Info::getNumPokemonNum($Pok['basenum']),
                  'type' => $Pok['type'],
                  'name' => $Pok['name_new'],
                  'lvl' => $Pok['lvl'],
                  'id' => $Pok['id']
                ];
              }

            break;
			
			case 'items':
			
				if(count($val) == 2) {
			
					$a = 1;
					$stmt = Work::$sql->prepare("SELECT id, item_id FROM items_users WHERE user = ? AND id != ? AND crash_activate = ? AND item_id = ?");
                        $stmt->bind_param("iiii", $_SESSION["id"],$val[0], $a, $val[1]);
                        $stmt->execute();
                        $items = $stmt->get_result();
					
					$stmtIB = Work::$sql->prepare("SELECT name FROM base_items WHERE id = ?");
                        $stmtIB->bind_param("i", $val[1]);
                        $stmtIB->execute();
                        $dataIB = $stmtIB->get_result();
                        $itemBase = $dataIB->fetch_assoc();
					
					while($item = $items->fetch_assoc()) {
						
						$crash = Items::crashProcent($item['id']);
						
						$this->List[] = [
						
							"crash" => $crash,
							"id" => $item["id"],
							"item_id" => $item["item_id"],
							"name" => $itemBase["name"],
						
						];
						
					}
					
				}
			
			break;

          }

      }else{
        $this->List = 0;
      }

      $this->response['response'] = array(
        'list' => $this->List,
      );

    }

  }

}

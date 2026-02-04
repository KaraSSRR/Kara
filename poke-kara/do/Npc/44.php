<?
	$response['name'] = 'Лотерейщик';
		switch($npcStep){
			case 2:
				$response['question'] = 'Супер! Цена 1 билета - 35.000 монет. Тебя это устраивает?';
			    $response['answer'] = array(
					4 => "Да, я покупаю билет"
				);
			break;
			case 3:
				$response['question'] = 'Я продаю специальные билеты с личными номерами, когда подойдет время - некоторые из этих номеров станут выигрышными и вы получите приз. Это добровольное участие, вы можете и вовсе ничего не получить.';
			    $response['answer'] = array(
			        2 => "Я хочу купить их"
				);
			break;
			case 4:
			    if(item_isset(1,35000)) {
			        $it = $mysqli->query("SELECT * FROM `system` WHERE `id` = 1 ")->fetch_assoc();
			        $dop = $it['loto'];
			        $dop1 = $dop+1;
			        $response['actionQuestPlus'] = '<img src="/img/world/items/little/9999.png" class="item"> Лотерейный билет <b>#'.$dop.'</b><br>';
			        $response['actionQuestMinus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х35.000</b><br>';
                    itemAdd(9999,1,$_SESSION['id'],$dop);
			        $mysqli->query("UPDATE `system` SET `loto` = '".$dop1."' WHERE `id` = 1");
			        $response['question'] = 'Здорово! Держи! Купишь еще?';
			        minus_item(1,35000);
			    $response['answer'] = array(
					4 => "Купить еще"
				);
				
			    }else{
			        $response['question'] = 'У тебя недостаточно монет!';
			    }
			break;
			default:
				// $response['question'] = 'Добро пожаловать, интересуешься лотерейными билетами?';
				// $response['answer'] = array(
				// 	2 => "Да, я именно за ними",
				// 	3 => "Что это такое?"
				// );
				$col = $mysqli->query('SELECT `loto` FROM `system`')->fetch_assoc();
				$response['question'] = 'Добро пожаловать, к сожалению продажа билетов уже закончилась. Было продано '.$col['loto'].' билетов.';
				
			break;
		}
?>

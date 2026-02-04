<?
	$response['name'] = 'Профессор Квил';
		switch($npcStep){
			case 2:
				$response['question'] = 'Привет! Извини, но некогда объяснять, мне срочно нужно вылечить всех диких покемонов от болезни.';
			    $response['answer'] = array(
					3 => "От болезни? Я замечал странное поведение у диких покемонов."
				);
			break;
			case 3:
				$response['question'] = 'Охх, так оказывается ты мне и нужен. Мы впервые сталкиваемся с таким вирусом, который затрагивает только покемонов. От него у покемонов краснеют глаза, поднимается температура, их лихорадит и они не могут нормально передвигаться. Мы нашли единственное лекарство от этой болезни, достаточно только кинуть ее дикому покемону и он сразу ее съест. Однако проблема в том, что этот вирус уже массово заразил несколько тысяч покемонов, обитавших в регионе.';
			    $response['answer'] = array(
			        4 => "И где же взять это лекарство?"
				);
			break;
			case 4:
				$response['question'] = 'Мы создали огромную партию, я продаю их. Могу продать и тебе, нужно?';
			    $response['answer'] = array(
			        5 => "Да, давайте"
				);
			break;
			case 5:
				$response['question'] = 'Я продаю по упаковкам по 100 штук. 1 упаковка стоит 25.000 монет, купишь одну или может сразу 4 за 100.000 монет?';
			    $response['answer'] = array(
			        6 => "Куплю одну упаковку",7 => "Куплю четыре упаковки"
				);
				quest_update(5002,1);
			break;
			case 6:
			    if(item_isset(1,25000)) {
			        $response['actionQuestPlus'] = '<img src="/img/world/items/little/479.png" class="item"> Медицинская таблетка <b>х100</b><br>';
			        $response['actionQuestMinus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х25.000</b><br>';
                    itemAdd(479,100,$_SESSION['id']);
			        $response['question'] = 'Здорово! Держи! Купишь еще?';
			        minus_item(1,25000);
			    $response['answer'] = array(
					6 => "Купить еще"
				);
				
			    }else{
			        $response['question'] = 'У тебя недостаточно монет!';
			    }
			break;
			case 7:
			    if(item_isset(1,100000)) {
			        $response['actionQuestPlus'] = '<img src="/img/world/items/little/479.png" class="item"> Медицинская таблетка <b>х400</b><br>';
			        $response['actionQuestMinus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>х100.000</b><br>';
                    itemAdd(479,400,$_SESSION['id']);
			        $response['question'] = 'Здорово! Держи! Купишь еще?';
			        minus_item(1,100000);
			    $response['answer'] = array(
					7 => "Купить еще"
				);
				
			    }else{
			        $response['question'] = 'У тебя недостаточно монет!';
			    }
			break;
			default:
			    if(quest_step(5002,1)){
				$response['question'] = 'И снова привет! Ты за таблетками? Я продаю по упаковкам по 100 штук. 1 упаковка стоит 25.000 монет, купишь одну или может сразу 4 за 100.000 монет?';
			    $response['answer'] = array(
			        6 => "Куплю одну упаковку",7 => "Куплю четыре упаковки"
				);
			    }else{
			        $response['question'] = '<i>*Взрослый мужчина бегает по городу в поисках чего-то, на его лице явно видна тревога*</i>';
				$response['answer'] = array(
					2 => "Мужчина, вы что-то потеряли?"
				);
			    }
			break;
		}
?>

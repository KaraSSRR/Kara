<?
	$response['name'] = 'Алла';
		switch($npcStep){
		    case 1:
		        $response['question'] = 'Я занимаюсь продажами высококачественных велосипедов. Приобретешь один?';
				    $response['answer'] = array(
			        2 => "А зачем он мне?"
				);
		    break;
		    case 2:
		        $response['question'] = 'Как это зачем?! Посетить Дикие земли калоса конечно! Путь к ним лежит через велосипедную дорожку которая соединяет Санталун и Сновбейл. Поэтому попасть туда можно только имея при себе велосипед. Так что, возьмешь один за 500.000 монет?';
			    $response['answer'] = array(
			        3 => "Да, давай"
				);
		    break;
		    case 3:
		        if(!item_isset(8,1)){
		            if(item_isset(1,500000)){
    		            itemAdd(8,1);
    		            minus_item(1,500000);
    		            $response['actionQuestPlus'] = '<img src="/img/world/items/little/8.png" class="item"> Велосипед <b>x1</b>';
    		            $response['actionQuestMinus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x500.000</b>';
    				    
    				    $response['question'] = 'Удачи тебе в путешествии!';
    		        
		            }else{
		                $response['question'] = 'У тебя недостаточно монет!';
		            }
		        }else{
		            $response['question'] = 'У тебя уже есть велосипед!';
    			    
		        }
		    break;
			default:
			    if(item_isset(8,1)){
				$response['question'] = 'Привет! Я вижу велосипед в отличном состоянии!';
			    }else{
			        $response['question'] = 'Молодой человек, добро пожаловать в Силейдж!';
				    $response['answer'] = array(
			        1 => "Добрый день"
				);
			    }
			break;
		}
?>

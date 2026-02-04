<?
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Искатель Рю';
	switch($npcStep){
		case 1:
			$response['question'] = 'Ого! Такой юный, а уже знает про золотые ключи, что ты хочешь о них узнать?';
			$response['answer'] = array(
				2 => "У меня есть немного таких ключей"
			);
		break;
		case 2:
			$response['question'] = 'ХА-ХА-ХА Не смеши меня. Чтобы у тебя они были. <i> (Вы показываете ему ключи) </i> Вау! Это и правда золотые ключи, что ты хочешь за них?';
			$response['answer'] = array(
				3 => "А что ты можешь предложить?"
			);
		break;
		case 3:
			$response['question'] = 'За золотые ключи я могу предложить тебе некоторых покемонов:<br>
			#037 Вульпикс - 5 ключей<br>
			#133 Иви - 70 ключей<br>
			#214 Геракросс - 100 ключей<br>
			#679 Хонэдж - 40 ключей<br>
			#751 Дьюпайдер - 20 ключей<br>
			#827 Никит - 5 ключей<br>';
			quest_update(5003,1);
			$response['answer'] = array(
				4 => "Вульпикс",
				5 => "Иви",
				6 => "Геракросс",
				7 => "Хонэдж",
				8 => "Дьюпайдер",
				9 => "Никит"
			);
		break;
		case 10:
			$response['question'] = 'За золотые ключи я могу предложить тебе некоторых покемонов:<br>
			#037 Вульпикс - 5 ключей<br>
			#133 Иви - 70 ключей<br>
			#214 Геракросс - 100 ключей<br>
			#679 Хонэдж - 40 ключей<br>
			#751 Дьюпайдер - 20 ключей<br>
			#827 Никит - 5 ключей<br>';
			$response['answer'] = array(
				4 => "Вульпикс",
				5 => "Иви",
				6 => "Геракросс",
				7 => "Хонэдж",
				8 => "Дьюпайдер",
				9 => "Никит"
			);
		break;
		case 4:
		    if(item_isset(457,5)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/037.png"> #037 Вульпикс';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/457.png" class="item"> Золотой ключ <b>x5</b>';
				newPokemon(37,$_SESSION['id'],1,30,0,'true',0,false,0,false,false,true);
				minus_item(457,5);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    				10 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает ключей!';
		    }
		break;
		case 5:
		    if(item_isset(457,70)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/133.png"> #133 Иви';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/457.png" class="item"> Золотой ключ <b>x70</b>';
				newPokemon(133,$_SESSION['id'],1,28,0,'true',0,false,0,false,false,true);
				minus_item(457,70);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    				10 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает ключей!';
		    }
		break;
		case 6:
		    if(item_isset(457,100)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/214.png"> #214 Геракросс';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/457.png" class="item"> Золотой ключ <b>x100</b>';
				newPokemon(214,$_SESSION['id'],1,28,0,'true',0,false,0,false,false,true);
				minus_item(457,70);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    				10 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает ключей!';
		    }
		break;
		case 7:
		    if(item_isset(457,40)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/679.png"> #679 Хонэдж';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/457.png" class="item"> Золотой ключ <b>x40</b>';
				newPokemon(679,$_SESSION['id'],1,28,0,'true',0,false,0,false,false,true);
				minus_item(457,40);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    				10 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает ключей!';
		    }
		break;
		case 8:
		    if(item_isset(457,20)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/751.png"> #751 Дьюпайдер';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/457.png" class="item"> Золотой ключ <b>x20</b>';
				newPokemon(751,$_SESSION['id'],1,30,0,'true',0,false,0,false,false,true);
				minus_item(457,20);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    				10 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает ключей!';
		    }
		break;
		case 9:
		    if(item_isset(457,5)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/827.png"> #827 Никит';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/457.png" class="item"> Золотой ключ <b>x5</b>';
				newPokemon(827,$_SESSION['id'],1,30,0,'true',0,false,0,false,false,true);
				minus_item(457,5);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    				10 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает ключей!';
		    }
		break;
		default:
		    if(!quest_isset(5003)){
			$response['question'] = 'Добрый день! У тебя есть ко мне дело? Если нет, то проваливай, малыш!';
            $response['answer'] = array(
				1 => "Золотые ключи."
			);
		    }else{
		        $response['question'] = 'Добрый день! Нашел еще ключи!';
                $response['answer'] = array(
    				10 => "Да, я хочу поменять их на покемонов"
    			);
		    }
		break;
	}
?>

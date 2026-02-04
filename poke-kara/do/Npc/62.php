<?
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Тыква Джек';
	switch($npcStep){
// 		case 1:
// 			$response['question'] = 'Ого! Ты меня даже не боишься. Удивительно, что за малышня нынче пошла(';
// 			$response['answer'] = array(
// 				2 => "А почему я должен тебя бояться? Я за свою жизнь уже много чего более ужасного повстречал в этом мире"
// 			);
// 		break;
// 		case 2:
// 			$response['question'] = 'ХА-ХА-ХА Понятно. Мне нужна твоя помощь, готов мне помочь? Я буду благодарен!';
// 			$response['answer'] = array(
// 				3 => "Что от меня требуется?"
// 			);
// 		break;
// 		case 3:
// 		    $response['question'] = 'Хм, как бы так сказать.... Мне нужны конфеты... НЕТ НЕТ не подумай, я не сладкоежка. Я хочу наконец-то завершить свое приключение, для этого мне нужно очень много конфет. Они позволят мне откупиться у одного мистера и он позволит... Ну ладно, не важно. Ты поможешь мне?';
// 			$response['answer'] = array(
// 				4 => "Где мне найти нужные тебе конфеты?"
// 			);
// 		break;
// 		case 4:
// 		    if(!quest_isset(44)){
// 			    $response['question'] = 'Смотри! Мне необходимы Жуткие конфеты. Найти их можно тремя способами: <b>сразиться с призрачной командой</b> (они нападут сами), они могут превращаться в любого покемона, за победу над ними ты получить 3 конфеты, также ты можешь <b>найти на локациях</b> конфетки, они разбросаны по всему миру(они появятся на твоем мониторе), там как типовые, так и жуткие, они скрываются под их видом, ну и третий способ - <b>излечить покемонов с окрасом shadow</b>, они выделяются от обычных розоватым именем. Вот, держи мою сумку, в ней специальная призрачная пыль, использовав ее - ты сможешь забрать у покемона жуткую конфету и вернуть ему его натуру. Собери <b>как можно больше конфет</b>, а я приду сюда 6 числа с товарами для тебя.';
// 			    $response['actionQuest'] = 'Вы начали участие в ивенте <b>Хэллоуин 2022</b>!';
// 			    $response['actionQuestPlus'] = '<img src="/img/world/items/little/290.png" class="item"> Сумка Джека <b>x1</b>';
// 			    itemAdd(290,1);
// 			    quest_update(44,1);
// 			    $mysqli->query("UPDATE `users` SET `hell`= 1 WHERE `id`='".$_SESSION['id']."'");
// 		    }else{
// 				$response['question'] = 'Ошибка!';
// 			}
// 		break;
		case 1:
		    if(!quest_isset(44)){
				$response['question'] = '{{makasimka}}';
				$response['npc_id'] = 62;
		    }else{
		        $response['question'] = 'Ты не участвовал в моем мероприятии!';
		    }
		break;
		case 2:
		    if(quest_isset(44)){
			$response['question'] = 'Все покемоны выдаются 1 уровня с геном 30 и окрасом шайни, вот покемоны которых я могу тебе предложить:<br>
			#355 Даскул - 1.000 конфет<br>
			#562 Ямаск - 1.000 конфет<br>
			#607 Литвик - 1.000 конфет<br>
			#679 Хонэдж - 1.000 конфет<br>
			#885 Дрипи - 2.000 конфет<br>';
			$response['answer'] = array(
				3 => "Даскул",
				4 => "Ямаск",
				5 => "Литвик",
				6 => "Хонэдж",
				7 => "Дрипи"
			);
		    }else{
		        $response['question'] = 'Ты не участвовал в моем мероприятии!';
		    }
		break;
		case 3:
		    if(item_isset(432,1000)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/355.png"> #355 Даскул';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/432.png" class="item"> Жуткая конфета <b>x1.000</b>';
				newPokemon(355,$_SESSION['id'],1,30,0,'false',1,false,1,false,false,true);
				minus_item(432,1000);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    			   'by' => ['title'=>'Хочу посмотреть предметы', 'npc_id'=>62],
    				2 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает конфет!';
		    }
		break;
		case 4:
		    if(item_isset(432,1000)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/562.png"> #562 Ямаск<br><br>';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/432.png" class="item"> Жуткая конфета <b>x1.000</b>';
				newPokemon(562,$_SESSION['id'],1,30,0,'false',1,false,1,false,false,true);
				minus_item(432,1000);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    			   'by' => ['title'=>'Хочу посмотреть предметы', 'npc_id'=>62],
    				2 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает конфет!';
		    }
		break;
		case 5:
		    if(item_isset(432,1000)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/607.png"> #607 Литвик<br><br>';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/432.png" class="item"> Жуткая конфета <b>x1.000</b>';
				newPokemon(607,$_SESSION['id'],1,30,0,'false',1,false,1,false,false,true);
				minus_item(432,1000);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    			   'by' => ['title'=>'Хочу посмотреть предметы', 'npc_id'=>62],
    				2 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает конфет!';
		    }
		break;
		case 6:
		    if(item_isset(432,1000)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/679.png"> #679 Хонэдж<br><br>';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/432.png" class="item"> Жуткая конфета <b>x1.000</b>';
				newPokemon(679,$_SESSION['id'],1,30,0,'false',1,false,1,false,false,true);
				minus_item(432,1000);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    			   'by' => ['title'=>'Хочу посмотреть предметы', 'npc_id'=>62],
    				2 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает конфет!';
		    }
		break;
		case 7:
		    if(item_isset(432,2000)){
			    $response['actionQuestPlus'] = '<img src="/img/pokemons/animation/885.png"> #885 Дрипи<br><br>';
			    $response['actionQuestMinus'] = '<img src="/img/world/items/little/432.png" class="item"> Жуткая конфета <b>x2.000</b>';
				newPokemon(885,$_SESSION['id'],1,30,0,'false',1,false,1,false,false,true);
				minus_item(432,2000);
				$response['question'] = 'Супер, хочешь еще одного покемона?';
				$response['answer'] = array(
    			   'by' => ['title'=>'Хочу посмотреть предметы', 'npc_id'=>62],
    				2 => "Посмотреть покемонов"
    			);
		    }else{
		        $response['question'] = 'У тебя не хватает конфет!';
		    }
		break;
		default:
			$response['question'] = 'Привет!!!! Что-то я задержался, какой вид приза тебя интересует?';
            $response['answer'] = array(
			   'by' => ['title'=>'Предметы', 'npc_id'=>62],
				2 => "Покемоны"
			);
		break;
	}
?>

<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Анна';
	switch($npcStep){
		case 1:
        $response['question'] = '<i>( Сквозь слёзы )</i> Да! Это все тот страшный призрак! Он появился и... и... <i>( Снова начинает сильно плакать )</i>';
				$response['answer'] = array(
					2 => "Ну подожди, не плачь. Расскажи, что случилось?"
				);
		break;
		case 2:
        $response['question'] = 'Он налетел на меня и украл <div class="itemIsset" onclick="issetAll(393,\'item\')" style="background-image: url(/img/world/items/little/393.png)"></div> цветок грацидеи! В этой местности он очень редок. Я его очень долго искала специально для мамы! Теперь я не смогу... <i>( Снова начинает сильно плакать )</i>';
				$response['answer'] = array(
					3 => "Ну погоди, давай я помогу тебе его вернуть?"
				);
		break;
		case 3:
        $response['question'] = 'П-правда? Ты правда сможешь помочь мне вернуть его? Но этот покемон был очень страшный!';
				$response['answer'] = array(
					4 => "Правда смогу. Не переживай, я сильный боец."
				);
		break;
		case 4:
        $response['question'] = 'Хорошо, спасибо большое!';
				$response['answer'] = array(
					5 => "Скажи, что это был за покемон призрак?"
				);
		break;
		case 5:
        $response['question'] = 'Я... я не знаю. Я еще слишком маленькая чтобы учить покемонов. Он резко появился перед глазами, украл цветок и спрятался в кустах.';
				$response['answer'] = array(
					6 => "Хорошо, я поищу его. Жди меня тут или лучше спрячься. Дикие покемоны могут напасть на тебя."
				);
		break;
		case 6:
			quest_update(4,1);
        $response['question'] = 'Хорошо! Я буду ждать тут.';
				$response['actionQuest'] = 'Обновлена информация в квесте <b>Потерянный цветок</b>. Загляните в Дневник.';
				update_zap(4,1,'У маленькой девочки Ани украли <div class="itemIsset" onclick="issetAll(393)" style="background-image: url(/img/world/items/little/393.png)"></div> Цветок Грацидеи, нужно помочь ей и вернуть его. Она рассказала мне, что это был страшный покемон <b>призрак</b>, обитавший на 14 тропе.');
		break;
    case 7:
			if(item_isset(393,1)){
				if(quest_step(4,1)){
					quest_update(4,2,1);
					$response['actionQuestMinus'] = '<img src="/img/world/items/little/393.png" class="item"> Цветок грацидеи <b>x1</b>';
					$response['actionQuestPlus'] = '<img src="/img/world/items/little/26.png" class="item"> Желтая конфета <b>x10</b><br><img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x10</b>';
					$response['actionQuest'] = 'Обновлена информация в квесте <b>Потерянный цветок</b>. Загляните в Дневник.';
					itemAdd(26,10);
					itemAdd(29,10);
					lvlupuser(500);
					minus_item(393,1);
					$response['question'] = 'Это и правда он! Спасибо тебе огромное. Я даже не знаю как тебя отблагодарить, возьми вот эти конфетки.';
				  update_zap(4,2,'Отлично! Я смог найти цветок и вернуть его Ане. Надеюсь больше она не попадет в неприятности.');
				}else{
					$response['question'] = 'Ошибка';
				}
			}else{
				$response['question'] = 'Ошибка!';
			}
		break;
		default:
		if(quest_step(4,1)){
			$response['question'] = 'Ты уже вернулся? Ты нашел цветок?';
      if(item_isset(393,1)){
				$response['answer'] = array(
					7 => "Да, вот он"
				);
			}
		}else if(quest_step(4,2)){
			$response['question'] = 'Маме очень понравился цветок, спасибо тебе еще раз!';
		}else{
			$response['question'] = '<i>( Сильно плачет )</i>';
			$response['answer'] = array(
				1 => "Эй, что случилось? Почему ты плачешь? Тебя кто-то обидел?"
			);
		}
		break;
	}
?>

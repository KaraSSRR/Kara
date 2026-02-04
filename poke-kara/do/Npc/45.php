<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Старик Ник';
	switch($npcStep){
		case 1:
        $response['question'] = 'Здравствуй! Видишь ли, я только что прибыл в регион Калос и отошел от своих сумок, чтобы позвонить своей дочери. Когда возвращался, то вижу как небольшая группа <span class="intextpoke sp40" onclick="openDex(821)">#821 Рукиди</span> копаются в моих вещах. Я начал их отпугивать и заметил, что у одного на шее был мой именной золотой кулон. Он мне очень дорог, мне подарила его моя жена на нашу годовщину и сейчас я не знаю, что мне делать. Без него я не могу, он напоминает мне о былых временах, когда мы с женой гуляли по прекрасной набережной в молодости.  ';
				$response['answer'] = array(
					2 => "Это ужасно! Давайте я помогу вам! Вы заметили куда полетели эти #821 Рукиди?"
				);
		break;
		case 2:
		    if(!quest_isset(41)){
        $response['question'] = 'Да, они полетели в сторону леса, говорят они обитают за ним.';
		quest_update(41,1);	
		$response['actionQuest'] = 'Обновлена информация в квесте <b>Важная вещь</b>. Загляните в Дневник.';
      update_zap(41,1,'Встретив старика в порту Санталуна, я узнал, что у него украли его именной золотой кулон <span class="intextpoke sp40" onclick="openDex(821)">#821 Рукиди</span>. Нужно пойти и найти того самого Рукиди и забрать кулон.');
		
		    }
		break;
    case 5:
    if(quest_step(41,1)){
      if(item_isset(470,1)){
          quest_update(41,2,1);
          lvlupuser(600);
          minus_item(470,1);
          itemAdd(1,100000);
          itemAdd(246,2);
          $response['actionQuest'] = 'Обновлена информация в квесте <b>Важная вещь</b>. Загляните в Дневник.';
      update_zap(41,2,'Я смог найти кулон и вернут его старику. Он был очень рад и вознаградил меня.');
          $response['actionQuestMinus'] = '<img src="/img/world/items/little/470.png" class="item"> Золотой кулон <b>x1</b>';
          $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x100.000</b><br><img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x2</b>';
            $response['question'] = 'Здорово! Спасибо тебе большое! Держи небольшой подарочек.';
      }else{
        $response['question'] = 'У тебя нет его!';
      }
      }else{
      $response['question'] = 'Ошибка!';
    }
    break;
    
		default:
		if(!quest_isset(41)){
			$response['question'] = '<i>( Вы замечаете грустного старика с сумками в зоне ожидания и решаетесь подойти к нему. )</i>';
      $response['answer'] = array(
        1 => "Добрый день! Что с вами случилось?"
      );
		}else if(quest_step(41,1)){
			$response['question'] = '<i>( Старик сидит у окна и смотрит в окно на спокойное голубое море и замечает вас в отражении )</i> Ооо, ты уже пришел, смог найти кулон?';
      $response['answer'] = array(
        5 => "Да, вот он"
      );
		}else{
			$response['question'] = 'В этом регионе и правда прекрасная погода...';

		}
		break;
	}
?>

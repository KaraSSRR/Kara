<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Эдвард';
	switch($npcStep){
		case 1:
        $response['question'] = 'Привет! Видишь ли, вчерашний шторм был настолько силен, что на берег выбросило огромного <span class="intextpoke sp40" onclick="openDex(321)">#321 Вайлорда</span>. Он закрыл собой всю территорию пляжа и людям даже попросту негде поместиться. Но это не главное! Нам срочно нужно вернуть покемона в море, пока он не погиб.';
				$response['answer'] = array(
					2 => "Это ужасно! Давайте я помогу вам!"
				);
		break;
		case 2:
		    if(!quest_isset(33)){
        $response['question'] = 'Смотри, обычно вес <span class="intextpoke sp40" onclick="openDex(321)">#321 Вайлорда</span> около 400кг. Нам нужны покемоны, которые смогут поднять его и оттолкнуть обратно в море. Я думаю, что <b>4 <span class="intextpoke sp40" onclick="openDex(68)"> #068 Мачампа</span> с статами атаки выше 120 </b> прекрасно подойдут для такой работы. Принеси их мне.';
		quest_update(33,1);	
		$response['actionQuest'] = 'Обновлена информация в квесте <b>Закрытый пляж</b>. Загляните в Дневник.';
      update_zap(33,1,'Эдвард рассказал о том, почему закрыли пляж. Я решился помочь ему разобраться с огромным <span class="intextpoke sp40" onclick="openDex(321)">#321 Вайлордом</span> и вернуть его в море. Для этого потребуется принести <b>4 <span class="intextpoke sp40" onclick="openDex(68)"> #068 Мачампов</span> с статами атаки выше 120 </b>.');
		
		    }
		break;
    case 5:
    if(quest_step(33,1)){
      if(search_pok_active_stat(68,4,1,120)){
        if(cool_pok_active(4)){
          $response['actionQuestMinus'] = '<img src="/img/pokemons/animation/068.png"> #068 Мачамп<br><br><img src="/img/pokemons/animation/068.png"> #068 Мачамп<br><br><img src="/img/pokemons/animation/068.png"> #068 Мачамп<br><br><img src="/img/pokemons/animation/068.png"> #068 Мачамп';
          delete_pok_active(68," LIMIT 4");
          quest_update(33,2,1);
          lvlupuser(1000);
          itemAdd(1,50000);
          itemAdd(157,1);
          itemAdd(153,1);
          if(item_isset(5,1)){ itemAdd(1,100000); $ds = '<br><img src="/img/world/items/little/1.png" class="item"> Монета <b>x100.000</b>';}else{ itemAdd(5,1); $ds = '<br><img src="/img/world/items/little/5.png" class="item"> Старая удочка <b>x1</b>'; }
          $response['actionQuest'] = 'Обновлена информация в квесте <b>Закрытый пляж</b>. Загляните в Дневник.';
      update_zap(33,2,'Я отдал Мачампов Эдварду и они, хоть и с трудом, но смогли вернуть <span class="intextpoke sp40" onclick="openDex(321)">#321 Вайлорда</span> обратно в море. Эдвард поблагодарил меня и дал небольшую награду.');
          $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x50.000</b><br><img src="/img/world/items/little/153.png" class="item"> Защитные очки <b>x1</b><br><img src="/img/world/items/little/157.png" class="item"> Ожерелье монет <b>x1</b>'.$ds;
            $response['question'] = 'Отлично! Они мне подойдут, спасибо тебе большое. Держи небольшую награду за помощь.';
        }else{
          $response['question'] = 'Ты и правда принес их! Но если я его заберу ты останешься без покемонов.';
        }
      }else{
        $response['question'] = 'У тебя нет их!';
      }
      }else{
      $response['question'] = 'Ошибка!';
    }
    break;
    
		default:
		if(!quest_isset(33)){
			$response['question'] = '<i>( Придя отдохнуть на пляж, вы замечаете вывеску «Пляж закрыт». Вдалеке вы замечаете взрослого мужчину в зеленом костюме, который пытается до кого-то дозвониться. Вы решаетесь подойти и спросить что случилось. )</i>';
      $response['answer'] = array(
        1 => "Добрый день! Почему пляж закрыт?"
      );
		}else if(quest_step(33,1)){
			$response['question'] = '<i>( Эдвард полевает Вайлорда из шланга, чтобы тот не засох. Он замечает вас и на его лице начинает появляться улыбка. )</i> Ооо, ты уже пришел, принес Мачампов?';
      $response['answer'] = array(
        5 => "Да, вот они"
      );
		}else{
			$response['question'] = 'Какая прекрасная и спокойная погода...';

		}
		break;
	}
?>

<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Дея';
	switch($npcStep){
		case 1:
        $response['question'] = '<i>( Сквозь слезы )</i>к-к-куклу. Они з-заб-брали мою к-куклу.';
				$response['answer'] = array(
					2 => "А кто именно забрал твою куклу? Расскажи мне и я помогу тебе её вернуть"
				);
		break;
		case 2:
		    $response['question'] = '<i>( Ничего не ответив, показала пальцем в сторону тропы 2 )</i>';
				$response['answer'] = array(
					3 => "А кто там? Хотя бы скажи, человек это или покемон"
				);
		break;
		case 3:
		    $response['question'] = '<i>( Ещё сильнее заливается слезами и ничего не отвечает )</i>';
				$response['answer'] = array(
					4 => "Не расстраивайся, я найду и верну твою куклу"
				);
		break;
		
		case 4:
		    
		    if(!quest_isset(42)){
        $response['question'] = 'С-с-спасибо';
		quest_update(42,1);	
		$response['actionQuest'] = 'Обновлена информация в квесте <b>Потерянная кукла</b>. Загляните в Дневник.';
      update_zap(42,1,'У малышки с 1 тропы истерика. Нужно ей обязательно помочь и вернуть куклу. Она показала рукой в сторону 2 тропы. Нужно начать поиски оттуда.');
		
		    }
		break;
    case 5:
    if(quest_step(42,8)){
      if(item_isset(402,1)){
          quest_update(42,9,1);
          lvlupuser(1500);
          minus_item(402,1);
          $countDay = mt_rand(7,10);
          $tm = time()+(3600*24*$countDay);
          $gens = "30,30,30,30,30,30";
          $basenum = 679;
          plusEgg($gens,false,false,true,$tm,$basenum,false);
          $response['actionQuest'] = 'Обновлена информация в квесте <b>Потерянная кукла</b>. Загляните в Дневник.';
            update_zap(42,9,'Как оказалось, это всё была одна большая шутка покемонов-призраков. Не скажу, что мне было очень весело, но награда превзошла все ожидания.');
          $response['actionQuestMinus'] = '<img src="/img/world/items/little/402.png" class="item"> Кукла <b>x1</b>';
          $response['actionQuestPlus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо Хонэдж';
            $response['question'] = 'Как ты быстро справился! Спасибо тебе огромное. В благодарность я отдам тебе одну из своих самых дорогих вещей - удочку. Она хоть немного и потрёпана, но прекрасно служит и помогает ловить многих редких покемонов. Ну всё, я одеваюсь и отчаливаю. Удачи!';
      }else{
        $response['question'] = 'Ты не нашел все необходимые предметы!';
      }
      }else{
      $response['question'] = 'Ошибка!';
    }
    break;
    
		default:
		if(!quest_isset(42)){
			$response['question'] = '<i>( Протяжно плачет, заливаясь слезами )</i>';
      $response['answer'] = array(
        1 => "Привет, малышка. Что случилось? Кто тебя обидел?"
      );
		}else if(quest_isset(42) and item_isset(402,1)){
			$response['question'] = '<i>( Плачет )</i>';
      $response['answer'] = array(
        5 => "Держи, вот твоя кукла. А ещё, я поймал тебе в утешение #434 Станки."
      );
		}else if(quest_isset(42) and !item_isset(402,1)){
			$response['question'] = '<i>( Протяжно плачет, заливаясь слезами )</i>';
		}
		break;
	}
?>

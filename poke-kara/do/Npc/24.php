<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Виола';
	switch($npcStep){
		case 1:
        $response['question'] = '<i>( Вздрагивает )</i> Ты давно тут? Я не заметила как ты зашел';
				$response['answer'] = array(
					2 => "Только пришел. Так вы что-то потеряли?"
				);
		break;
		case 2:
        $response['question'] = 'Да! Я потеряла покеболы своей сестры. Она Гим-Лидер этого стадиона и сейчас по работе уехала в столицу. Она прибьет меня, если я не найду ее покемонов. Пожалуйста, помоги мне!';
				$response['answer'] = array(
					3 => "Каких покемонов ты потеряла?"
				);
		break;
		case 3:
        $response['question'] = 'У нее было 3 покемона: <b><span class="intextpoke sp284" onclick="openDex(284)">#284 Маскурейн</span></b>, <b><span class="intextpoke sp542" onclick="openDex(542)">#542 Ливанни</span></b> и <b><span class="intextpoke sp666" onclick="openDex(666)">#666 Вивиллон</span></b>. Принеси мне их, пожалуйста, я буду благодарна тебе. Кстати, вместо <b><span class="intextpoke sp542" onclick="openDex(542)">#542 Ливанни</span></b> можешь принести <b><span class="intextpoke sp541" onclick="openDex(541)">#541 Свадлун</span></b>, а дальше я уже сама его эволюционирую. Все они должны быть 40 уровня.';
				$response['answer'] = array(
					4 => "Хорошо. Я принесу их тебе"
				);
		break;
		case 4:
    quest_update(5,1);
      $response['question'] = 'Спасибо! Я буду ждать тебя.';
      $response['actionQuest'] = 'Обновлена информация в квесте <b>Помощь Виоле</b>. Загляните в Дневник.';
      update_zap(5,1,'Помощница Гим-Лидера стадиона Жуков в Санталуне потеряла покеболы с покемонами своей сестры. Она попросила помощи принести ей таких же покемонов: <b><span class="intextpoke sp284" onclick="openDex(284)">#284 Маскурейн</span></b> <b><span class="intextpoke sp541" onclick="openDex(541)">#541 Свадлун</span></b> и <b><span class="intextpoke sp666" onclick="openDex(666)">#666 Вивиллон</span></b>, всех 40 уровня.');
    break;
    case 5:
    if(search_pok_active(284,1,"AND lvl = 40") and search_pok_active(541,1,"AND lvl = 40") and search_pok_active(666,1,"AND lvl = 40") ){
      if(cool_pok_active(4)){
        delete_pok_active(284,"AND lvl = 40  LIMIT 1");
        delete_pok_active(541,"AND lvl = 40  LIMIT 1");
        delete_pok_active(666,"AND lvl = 40  LIMIT 1");
        itemAdd(267,3);
        itemAdd(141,1);
        itemAdd(1,100000);
        quest_update(5,2,1);
        update_zap(5,2,'Я принес всех нужных покемонов Виоле и она вознаградила меня некоторыми предметами.');
        lvlupuser(800);
        $response['question'] = 'Здорово! Это и правда они! Спасибо тебе огромное, возьми это в знак благодарности. <i>( Протягивает руки с парой предметов и монетами )</i>';
        $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x100.000</b><br><img src="/img/world/items/little/141.png" class="item"> Серебряная пыль <b>x1</b><br><img src="/img/world/items/little/267.png" class="item"> Портативный инкубатор <b>x3</b>';
        $response['actionQuestMinus'] = '<img src="/img/pokemons/animation/284.png"> #284 Маскурейн<br><img src="/img/pokemons/animation/541.png"> #541 Свадлун<br><img src="/img/pokemons/animation/666.png"> #666 Вивиллон';
      }else{
        $response['question'] = 'Ты и правда нашел их! Но яесли я их заберу ты останешься без покемонов.';
      }
    }else{
      $response['question'] = 'Ты не нашел всех нужных покемонов';
    }
    break;
		default:
		if(quest_step(5,1)){
			$response['question'] = 'Привет! Ты смог найти нужных покемонов?';
      $response['answer'] = array(
				5 => "Да, вот они"
			);
		}elseif(quest_step(5,2)){
$response['question'] = 'Привет! Рада снова тебя тут видеть.';
    }else{
			$response['question'] = '<i>( Бегает из стороны в сторону в поисках чего-то )</i> да где же они?!';
			$response['answer'] = array(
				1 => "Вы что-то потеряли?"
			);
		}
		break;
	}
?>

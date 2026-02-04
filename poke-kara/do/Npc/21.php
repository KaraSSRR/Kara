<?
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Рола';
	switch($npcStep){
		case 1:
        $response['question'] = 'Эволюция - процесс, при котором покемон превращается в покемона другого вида, связанного с ним эволюционной цепочкой и находящегося на следующей стадии развития. Эволюцию можно назвать взрослением покемона, только эти изменения происходят за несколько секунд и зачастую сопровождаются значительным изменением внешнего вида и характеристик.<br> Для того, чтобы началась эволюция, должно выполниться специальное условие, зависящее от вида покемона. Его ты можешь посмотреть в покедексе. В большинстве случаев это происходит при достижении покемоном определенного уровня, но есть виды, у которых эволюция происходит от использования специального предмета эволюции или при спаривании. <br>Преобразовавшийся покемон может вернуться в свою первоначальную форму при использовании <div class="itemIsset" onclick="issetAll(33,\'item\')" style="background-image: url(/img/world/items/little/33.png)"></div> Горькой конфеты.<br>Покемоны, входящие в одно эволюционное семейство, составляют цепочку эволюции. Каждый занимает в ней строго отведенное место, тем самым определяя, из кого получился данный покемон и в кого еще он может превратиться. В мире покемонов часто можно встретить цепочки из трех или двух последовательных покемонов, не эволюционирующих покемонов или покемонов с разветвленной цепочкой эволюции. О каком виде эволюции тебе рассказать подробнее?';
				$response['answer'] = array(
	        3 => "От повышения уровня",
					4 => "От использования предмета",
					5 => "От размножения"
	      );
		break;
		case 2:
        $response['question'] = 'Хорошо, о каком виде эволюции тебе рассказать еще?';
				$response['answer'] = array(
	        3 => "От повышения уровня",
					4 => "От использования предмета",
					5 => "От размножения"
	      );
		break;
		case 3:
        $response['question'] = 'Это самый популярный вид эволюции. Достигнув определенного уровня покемон эволюционирует в следующую форму, согласно его цепочке. Ты уже сталкивался с этим видом: твой стартовый покемон относится к нему. Однако, есть и уникальные случаи такого вида. На данный момент их насчитывается 8.<br>
				Первый связан с семейством <b><span class="intextpoke sp236" onclick="openDex(236)">#236 Тирога</span></b>. На 20 уровне он может эволюционировать в одного из 3 покемонов, в зависимости от расстановки EV. Например, если EV Защиты больше EV Атаки, то <b><span class="intextpoke sp236" onclick="openDex(236)">#236 Тирог</span></b> превратится в <b><span class="intextpoke sp107" onclick="openDex(107)">#107 Хитмончана</span></b>, если наоборот, то в <b><span class="intextpoke sp106" onclick="openDex(106)">#106 Хитмонли</span></b>, если же вдруг они равны, то <b><span class="intextpoke sp237" onclick="openDex(237)">#237 Хитмонтопа</span></b>.<br>
				Второй связан с семейством <b><span class="intextpoke sp265" onclick="openDex(265)">#265 Вермпл</span></b>. В зависимости от времени он эволюционирует либо в <b><span class="intextpoke sp266" onclick="openDex(266)">#266 Силкуна</span></b>, либо в <b><span class="intextpoke sp268" onclick="openDex(268)">#268 Каскуна</span></b>.<br>
				Третий связан с семейством <b><span class="intextpoke sp458" onclick="openDex(458)">#458 Мантайка</span></b>. Он эволюционирует на 20 уровне в <b><span class="intextpoke sp226" onclick="openDex(226)">#226 Мантина</span></b> только если в команде имеется <b><span class="intextpoke sp223" onclick="openDex(223)">#223 Реморейд</span></b>.<br>
				Четвертый связан с семейством <b><span class="intextpoke sp412" onclick="openDex(412)">#412 Бурми</span></b>. На 20 уровне самки  <b><span class="intextpoke sp412" onclick="openDex(412)">#412 Бурми</span></b> эволюционируют в <b><span class="intextpoke sp413" onclick="openDex(413)">#413 Вармадам</span></b>, а самцы в <b><span class="intextpoke sp414" onclick="openDex(414)">#414 Мотим</span></b><br>
				Пятый связан с семейством <b><span class="intextpoke sp415" onclick="openDex(415)">#415 Комби</span></b>. Только самки <b><span class="intextpoke sp415" onclick="openDex(415)">#415 Комби</span></b> на 21 уровне могут превратиться в <b><span class="intextpoke sp416" onclick="openDex(416)">#416 Веспиквин</span></b>.<br>
				Шестой связан с семейством <b><span class="intextpoke sp290" onclick="openDex(290)">#290 Нинкада</span></b> и является самым интересным. <b><span class="intextpoke sp290" onclick="openDex(290)">#290 Нинкада</span></b> - единственный покемон при эволюции которого могут получиться сразу 2 покемона. Если при достижении 20 уровня в момент эволюции у вас в команде будет 1 свободное место, то помимо превращения <b><span class="intextpoke sp290" onclick="openDex(290)">#290 Нинкады</span></b> в <b><span class="intextpoke sp291" onclick="openDex(291)">#291 Нинджаска</span></b>, вы так же получите и <b><span class="intextpoke sp292" onclick="openDex(292)">#292 Шединью</span></b>.<br>
				Седьмой связан с семейством <b><span class="intextpoke sp674" onclick="openDex(674)">#674 Панчама</span></b>. Он эволюционирует на 34 уровне в <b><span class="intextpoke sp675" onclick="openDex(675)">#675 Пангоро</span></b>, если у вас в команде есть покемон темного типа.<br>
				Восьмой связан с семейством <b><span class="intextpoke sp757" onclick="openDex(757)">#757 Саландит</span></b>. Только самки <b><span class="intextpoke sp757" onclick="openDex(757)">#757 Саландит</span></b> на 33 уровне могут превратиться в <b><span class="intextpoke sp758" onclick="openDex(758)">#758 Салазл</span></b>.<br> Что ж, а теперь у меня есть для тебя небольшое задание.';
				$response['answer'] = array(
	        2 => "Расскажите о другом виде эволюции",
					6 => "Какое задание?"
	      );
		break;
		case 4:
        $response['question'] = 'Предметы эволюции - общее название для предметов, использование которых на покемоне вызывает его эволюцию. Это позволяет трансформироваться покемону любого уровня - первого, тридцатого или сотого. После эволюции предмет исчезает.<br>Есть большое разнообразие предметов эволюции, в мире Poke-Route насчитывается около 40 предметов эволверов и узнать о всех них ты сможешь в процессе своего приключения. Я могу рассказать только о некоторых из них, которые имеют уникальный функционал и свои условия. Например <div class="itemIsset" onclick="issetAll(107,\'item\')" style="background-image: url(/img/world/items/little/107.png)"></div> Эволвер счастья не будет работать на покемона если показатель его счастья ниже 250, а для использования <div class="itemIsset" onclick="issetAll(108,\'item\')" style="background-image: url(/img/world/items/little/108.png)"></div> Эволвера знаний необходимо чтобы покемон знал определенную атаку, для <b><span class="intextpoke sp439" onclick="openDex(439)">#439 Майм Джуниора</span></b> например это атака <b>Имитация</b>, а для <b><span class="intextpoke sp221" onclick="openDex(221)">#221 Пилосвайна</span></b> это <b>Древняя сила</b>.<br>Так же эволюция <b><span class="intextpoke sp133" onclick="openDex(133)">#133 Иви</span></b> при использовании <div class="itemIsset" onclick="issetAll(107,\'item\')" style="background-image: url(/img/world/items/little/107.png)"></div> Эволвера счастья зависит еще и от времени: днем он эволюционирует в <b><span class="intextpoke sp196" onclick="openDex(196)">#196 Эспеона</span></b>, а ночью в <b><span class="intextpoke sp197" onclick="openDex(197)">#197 Умбреона</span></b>.<br>Так же есть условия у <div class="itemIsset" onclick="issetAll(89,\'item\')" style="background-image: url(/img/world/items/little/89.png)"></div> Камня рассвета: только самка <b><span class="intextpoke sp361" onclick="openDex(361)">#361 Снорант</span></b> может эволюционировать в <b><span class="intextpoke sp478" onclick="openDex(478)">#478 Фросласс</span></b> и только самцы <b><span class="intextpoke sp281" onclick="openDex(281)">#281 Кирилия</span></b> в <b><span class="intextpoke sp475" onclick="openDex(475)">#475 Галлейда</span></b>.<br> Что ж, а теперь у меня есть для тебя небольшое задание.';
				$response['answer'] = array(
	        2 => "Расскажите о другом виде эволюции",
					6 => "Какое задание?"
	      );
		break;
		case 5:
        $response['question'] = 'Разведение покемонов - очень важный элемент игры. Он помогает тренеру выводить более сильных покемонов, но так же помогает некоторым покемонам эволюционировать. Такими покемонами являются <b><span class="intextpoke sp064" onclick="openDex(64)">#064 Кадабра</span></b>, <b><span class="intextpoke sp075" onclick="openDex(75)">#075 Гровелер</span></b> и др.<br>Для эволюции необходимо спарить этих покемонов и они сразу эволюционируют. НО нужно быть аккуратным: если <b><span class="intextpoke sp74" onclick="openDex(74)">#074 Джеодуд</span></b> будет спарен, то после эволюции в <b><span class="intextpoke sp75" onclick="openDex(75)">#075 Гровелера</span></b> он не сможет развиться дальше в <b><span class="intextpoke sp76" onclick="openDex(76)">#076 Голема</span></b>. Для возвращения покемонам возможности к разведению ученные смогли создать гормональные препараты <div class="itemIsset" onclick="issetAll(269,\'item\')" style="background-image: url(/img/world/items/little/269.png)"></div> тестостерона и <div class="itemIsset" onclick="issetAll(270,\'item\')" style="background-image: url(/img/world/items/little/270.png)"></div> эстрогена.<br>У такого вида эволюции так же есть и свои условия, например только спаривая <b><span class="intextpoke sp588" onclick="openDex(588)">#588 Каррабласта</span></b> с <b><span class="intextpoke sp616" onclick="openDex(616)">#616 Шелмета</span></b> они эволюционируют в <b><span class="intextpoke sp589" onclick="openDex(589)">#589 Эскавальера</span></b> и <b><span class="intextpoke sp617" onclick="openDex(617)">#617 Ацелгора</span></b> соответственно.<br> Что ж, а теперь у меня есть для тебя небольшое задание.';
				$response['answer'] = array(
	        2 => "Расскажите о другом виде эволюции",
					6 => "Какое задание?"
	      );
		break;
		case 6:
		if(lvluser() < 10){
        $response['question'] = 'Я не буду требовать от тебя чего-то сверхестесвенного. Я попрошу принести мне полную цепочку эволюции одного покемона.';
				$response['answer'] = array(
	        7 => "Какого именно покемона?"
	      );
			}else{
				$response['question'] = 'Да не, для тебя это уже будет не интересно)';
				quest_update(2,2,1);
					update_zap(2,1,'Рола рассказала мне обо всех видах эволюции и их особенностях. Видимо я был уже слишком опытен для выполнения ее задания.');
			}
		break;
		case 7:
		if(!quest_step(2,1) or !quest_step(2,2)){
					quest_update(2,1);
					$response['actionQuest'] = 'Обновлена информация в квесте <b>Мастер эволюции</b>. Загляните в Дневник.';
					$response['question'] = 'Тут неподалеку, на 2 тропе, обитают <b><span class="intextpoke sp016" onclick="openDex(16)">#016 Пиджи</span></b>. Принеси мне его и двух остальных его форм: <b><span class="intextpoke sp017" onclick="openDex(17)">#017 Пиджеотто</span></b> и <b><span class="intextpoke sp018" onclick="openDex(18)">#018 Пиджеот</span></b>.';
					update_zap(2,1,'Рола рассказала мне обо всех видах эволюции и их особенностях. Так же она попросила принести ей <b><span class="intextpoke sp016" onclick="openDex(16)">#016 Пиджи</span></b> <b><span class="intextpoke sp017" onclick="openDex(17)">#017 Пиджеотто</span></b> и <b><span class="intextpoke sp018" onclick="openDex(18)">#018 Пиджеот</span></b>. Они обитают на 2 тропе.');
				}else{
					$response['question'] = 'Ошибка';
				}
		break;
		case 8:
			if(quest_step(2,1)){
				if(search_pok_active(16,1) and search_pok_active(17,1) and search_pok_active(18,1)){
					quest_update(2,2,1);
					lvlupuser(500);
					$response['actionQuest'] = 'Квест <b>Мастер эволюции</b> успешно пройден. Загляните в Дневник.';
					$response['question'] = 'Ооо... Да ты и правда перспективный тренер! Я рада, что ты так быстро выполнил мое задание. Я тут нашла небольшие осколки некоторых камней эволюций, держи немного. Удачи тебе и приходи ко мне если захочешь снова послушать про виды эволюции.';
					update_zap(2,2,'Я выполнил задание Ролы и она дала мне несколько осколков камней эволюции для крафта.');
					delete_pok_active(16,'LIMIT 1');
	                delete_pok_active(17,'LIMIT 1');
	                delete_pok_active(18,'LIMIT 1');
					$i = 0;
					while($i < 5) {
						$rand = rand(1,7);
						if($rand == 1){ $tpl .= '<img src="/img/world/items/little/73.png" class="item"> Осколок громового камня <b>x1</b><br>'; itemAdd(73,1);
						}elseif($rand == 2){ $tpl .= '<img src="/img/world/items/little/74.png" class="item"> Осколок водного камня <b>x1</b><br>'; itemAdd(74,1);
						}elseif($rand == 3){ $tpl .= '<img src="/img/world/items/little/75.png" class="item"> Осколок лиственного камня <b>x1</b><br>'; itemAdd(75,1);
						}elseif($rand == 4){ $tpl .= '<img src="/img/world/items/little/76.png" class="item"> Осколок огненного камня <b>x1</b><br>'; itemAdd(76,1);
						}elseif($rand == 5){ $tpl .= '<img src="/img/world/items/little/77.png" class="item"> Осколок лунного камня <b>x1</b><br>'; itemAdd(77,1);
						}elseif($rand == 6){ $tpl .= '<img src="/img/world/items/little/78.png" class="item"> Осколок солнечного камня <b>x1</b><br>'; itemAdd(78,1);
						}elseif($rand == 7){ $tpl .= '<img src="/img/world/items/little/79.png" class="item"> Осколок сумрачного камня <b>x1</b><br>'; itemAdd(79,1);}
				    $i++;
				  }
					$response['actionQuestPlus'] = $tpl;
					$response['actionQuestMinus'] = '<img src="/img/pokemons/animation/016.png"> #016 Пиджи<br><br><img src="/img/pokemons/animation/017.png"> #017 Пиджеотто<br><br><img src="/img/pokemons/animation/018.png"> #017 Пиджеот<br><br>';
				}else{
					$response['question'] = 'Но у тебя их нет!';
				}
				}else{
					$response['question'] = 'Ошибка';
				}
		break;
		case 9:
        $response['question'] = 'Хорошо, о каком виде эволюции тебе рассказать еще?';
				$response['answer'] = array(
	        10 => "От повышения уровня",
					11 => "От использования предмета",
					12 => "От размножения"
	      );
		break;
		case 10:
        $response['question'] = 'Это самый популярный вид эволюции. Достигнув заданного уровня покемон эволюционирует в следующую форму согласно его цепочке. Ты уже наверное сталкивался с этим видом, твой стартовый покемон относится к нему. Однако есть и уникальные случаи такого вида. На данный момент их насчитывается 8.<br>
				Первый связан с семейством <b><span class="intextpoke sp236" onclick="openDex(236)">#236 Тирога</span></b>. На 20 уровне он может эволюционировать в одного из 3 покемонов в зависимости от расстановки EV. Например если EV Защиты больше EV Атаки, то <b><span class="intextpoke sp236" onclick="openDex(236)">#236 Тирог</span></b> превратится в <b><span class="intextpoke sp107" onclick="openDex(107)">#107 Хитмончана</span></b>, если наоборот то в <b><span class="intextpoke sp106" onclick="openDex(106)">#106 Хитмонли</span></b>, если же вдруг они равны, то <b><span class="intextpoke sp237" onclick="openDex(237)">#237 Хитмонтопа</span></b>.<br>
				Второй связан с семейством <b><span class="intextpoke sp265" onclick="openDex(265)">#265 Вермпл</span></b>. В зависимости от времени он эволюционирует либо в <b><span class="intextpoke sp266" onclick="openDex(266)">#266 Силкуна</span></b>, либо в <b><span class="intextpoke sp268" onclick="openDex(268)">#268 Каскуна</span></b>.<br>
				Третий связан с семейством <b><span class="intextpoke sp458" onclick="openDex(458)">#458 Мантайка</span></b>. Он эволюционирует на 20 уровне в <b><span class="intextpoke sp226" onclick="openDex(226)">#226 Мантина</span></b> только если в команде имеется <b><span class="intextpoke sp223" onclick="openDex(223)">#223 Реморейд</span></b>.<br>
				Четвертый связан с семейством <b><span class="intextpoke sp412" onclick="openDex(412)">#412 Бурми</span></b>. На 20 уровне самки  <b><span class="intextpoke sp412" onclick="openDex(412)">#412 Бурми</span></b> эволюционируют в <b><span class="intextpoke sp413" onclick="openDex(413)">#413 Вармадам</span></b>, а самцы в <b><span class="intextpoke sp414" onclick="openDex(414)">#414 Мотим</span></b><br>
				Пятый связан с семейством <b><span class="intextpoke sp415" onclick="openDex(415)">#415 Комби</span></b>. Только самки <b><span class="intextpoke sp415" onclick="openDex(415)">#415 Комби</span></b> на 21 уровне могут превратиться в <b><span class="intextpoke sp416" onclick="openDex(416)">#416 Веспиквин</span></b>.<br>
				Шестой связан с семейством <b><span class="intextpoke sp290" onclick="openDex(290)">#290 Нинкада</span></b> и является самым интересным. <b><span class="intextpoke sp290" onclick="openDex(290)">#290 Нинкада</span></b> единственный покемон при эволюции которого могут получиться сразу 2 покемона. Если при достижении 20 уровня в момент эволюции у вас в команде будет 1 свободное место, то помимо превращения <b><span class="intextpoke sp290" onclick="openDex(290)">#290 Нинкады</span></b> в <b><span class="intextpoke sp291" onclick="openDex(291)">#291 Нинджаска</span></b> вы так же получите и <b><span class="intextpoke sp292" onclick="openDex(292)">#292 Шединью</span></b>.<br>
				Седьмой связан с семейством <b><span class="intextpoke sp674" onclick="openDex(674)">#674 Панчама</span></b>. Он эволюционирует на 34 уровне в <b><span class="intextpoke sp675" onclick="openDex(675)">#675 Пангоро</span></b> если у вас в команде есть покемон темного типа.<br>
				Восьмой связан с семейством <b><span class="intextpoke sp757" onclick="openDex(757)">#757 Саландит</span></b>. Только самки <b><span class="intextpoke sp757" onclick="openDex(757)">#757 Саландит</span></b> на 33 уровне могут превратиться в <b><span class="intextpoke sp758" onclick="openDex(758)">#758 Салазл</span></b>.';
				$response['answer'] = array(
	        9 => "Расскажите о другом виде эволюции"
	      );
		break;
		case 11:
        $response['question'] = 'Предметы эволюции - общее название для предметов, использование которых на покемоне вызывает его эволюцию. Это позволяет трансформироваться покемону любого уровня - первого, тридцатого или сотого. После эволюции предмет исчезает.<br>Есть большое разнообразие предметов эволюции, в мире Poke-Route насчитывается около 40 предметов эволверов и узнать о всех них ты сможешь в процессе своего приключения. Я могу рассказать только о некоторых из них, которые имеют уникальный функционал и свои условия. Например <div class="itemIsset" onclick="issetAll(107,\'item\')" style="background-image: url(/img/world/items/little/107.png)"></div> Эволвер счастья не будет работать на покемона если показатель его счастья ниже 250, а для использования <div class="itemIsset" onclick="issetAll(108,\'item\')" style="background-image: url(/img/world/items/little/108.png)"></div> Эволвера знаний необходимо чтобы покемон знал определенную атаку, для <b><span class="intextpoke sp439" onclick="openDex(439)">#439 Майм Джуниора</span></b> например это атака <b>Имитация</b>, а для <b><span class="intextpoke sp221" onclick="openDex(221)">#221 Пилосвайна</span></b> это <b>Древняя сила</b>.<br>Так же эволюция <b><span class="intextpoke sp133" onclick="openDex(133)">#133 Иви</span></b> при использовании <div class="itemIsset" onclick="issetAll(107,\'item\')" style="background-image: url(/img/world/items/little/107.png)"></div> Эволвера счастья зависит еще и от времени: днем он эволюционирует в <b><span class="intextpoke sp196" onclick="openDex(196)">#196 Эспеона</span></b>, а ночью в <b><span class="intextpoke sp197" onclick="openDex(197)">#197 Умбреона</span></b>.<br>Так же есть условия у <div class="itemIsset" onclick="issetAll(89,\'item\')" style="background-image: url(/img/world/items/little/89.png)"></div> Камня рассвета: только самка <b><span class="intextpoke sp361" onclick="openDex(361)">#361 Снорант</span></b> может эволюционировать в <b><span class="intextpoke sp478" onclick="openDex(478)">#478 Фросласс</span></b> и только самцы <b><span class="intextpoke sp281" onclick="openDex(281)">#281 Кирилия</span></b> в <b><span class="intextpoke sp475" onclick="openDex(475)">#475 Галлейда</span></b>.';
				$response['answer'] = array(
	        9 => "Расскажите о другом виде эволюции"
	      );
		break;
		case 12:
        $response['question'] = 'Разведение покемонов - очень важный элемент игры. Он помогает тренеру выводить более сильных покемонов, но так же помогает некоторым покемонам эволюционировать. Такими покемонами являются <b><span class="intextpoke sp064" onclick="openDex(64)">#064 Кадабра</span></b>, <b><span class="intextpoke sp075" onclick="openDex(75)">#075 Гровелер</span></b> и др.<br>Для эволюции необходимо спарить этих покемонов и они сразу эволюционируют. НО нужно быть аккуратным: если <b><span class="intextpoke sp74" onclick="openDex(74)">#074 Джеодуд</span></b> будет спарен, то после эволюции в <b><span class="intextpoke sp75" onclick="openDex(75)">#075 Гровелера</span></b> он не сможет развиться дальше в <b><span class="intextpoke sp76" onclick="openDex(76)">#076 Голема</span></b>. Для возвращения покемонам возможности к разведению ученные смогли создать гормональные препараты <div class="itemIsset" onclick="issetAll(269,\'item\')" style="background-image: url(/img/world/items/little/269.png)"></div> тестостерона и <div class="itemIsset" onclick="issetAll(270,\'item\')" style="background-image: url(/img/world/items/little/270.png)"></div> эстрогена.<br>У такого вида эволюции так же есть и свои условия, например только спаривая <b><span class="intextpoke sp588" onclick="openDex(588)">#588 Каррабласта</span></b> с <b><span class="intextpoke sp616" onclick="openDex(616)">#616 Шелмета</span></b> они эволюционируют в <b><span class="intextpoke sp589" onclick="openDex(589)">#589 Эскавальера</span></b> и <b><span class="intextpoke sp617" onclick="openDex(617)">#617 Ацелгора</span></b> соответственно.';
				$response['answer'] = array(
	        9 => "Расскажите о другом виде эволюции"
	      );
		break;
		default:
		if(quest_step(2,1)){
			$response['question'] = 'С возвращением! Ты принес мне покемонов, что я просила?';
			$response['answer'] = array(
				8 => "Да, вот они"
			);
		}else if(quest_step(2,2)){
			$response['question'] = 'Приветствую, '.$_SESSION['login'].'! Я рада снова тебя видеть тут.';
			$response['answer'] = array(
				9 => "Я хочу снова послушать про виды эволюций"
			);
		}else{
			$response['question'] = 'Приветствую тебя, '.$_SESSION['login'].'! Обберта передала мне, что ты должен подойти. Она рассказала мне о том, что ты очень перспективный тренер, я буду верить в это! Я проведу тебе небольшую экскурсию по эволюции покемонов, ты готов?';
      $response['answer'] = array(
        1 => "Да, я готов"
      );
		}
		break;
	}
?>

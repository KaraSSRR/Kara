<?
	$response['name'] = 'Медсестра Нина';
	$us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
		switch($npcStep){
			case 1:
				$response['question'] = 'Я выдаю награды поэтапно, ты всегда сможешь вернуться на прошлый этап если хочешь получить приз повторно, но не сможешь продолжить если у тебя не хватит вылеченных покемонов!';
			    $response['answer'] = array(
					2 => "Отлично! Какой приз за 1 этап?"
				);
			break;
			case 2:
				$response['question'] = 'Приз за <b>первый</b> этап ты получишь если у тебя есть <b>10</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Стимпах х1</li><li>Желтая конфета х1</li><li>Эликсир х1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			    $response['answer'] = array(
			        3 => "*Взять приз*"
				);
			break;
			case 3:
			    if($us_bd['virus'] >= 10){
			        $r = rand(1,9);
			        if($r >= 1 and $r <= 3){itemAdd(13,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/13.png" class="item"> Стимпак <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){itemAdd(15,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/15.png" class="item"> Эликсир <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(26,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/26.png" class="item"> Желтая конфета <b>х1</b>';}
			        $upd = $us_bd['virus']-10;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>второй</b> этап ты получишь если у тебя есть еще <b>25</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Типовая конфета х1</li><li>Амурит х1</li><li>Капсула х1</li><li>Крыло х1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
    			        3 => "*Получить приз повторно*",
    			        4 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 4:
			    if($us_bd['virus'] >= 25){
			        $r = rand(1,12);
			        if($r >= 1 and $r <= 3){$s = rand(35,52); itemAdd($s,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/'.$s.'.png" class="item"> Конфета <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){itemAdd(124,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/124.png" class="item"> Амурит <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(240,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/240.png" class="item"> Капсула <b>х1</b>';}
			        elseif($r >= 10 and $r <= 12){$s = rand(367,371); itemAdd($s,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/'.$s.'.png" class="item"> Крыло <b>х1</b>';}
			        $upd = $us_bd['virus']-25;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>третий</b> этап ты получишь если у тебя есть еще <b>50</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Стабовый усилитель х1</li><li>Банка витамин х1</li><li>Кекс х1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
			        4 => "*Получить приз повторно*",
			        5 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 5:
			    if($us_bd['virus'] >= 50){
			        $r = rand(1,9);
			        if($r >= 1 and $r <= 3){$s = rand(125,141); itemAdd($s,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/'.$s.'.png" class="item"> Стабовый усилитель <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){$s = rand(199,204); itemAdd($s,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/'.$s.'.png" class="item"> Банка витамин <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(245,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>х1</b>';}
			        $upd = $us_bd['virus']-50;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>четвертый</b> этап ты получишь если у тебя есть еще <b>100</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Огненный камень х1</li><li>Водный камень х1</li><li>Лиственный камень х1</li><li>Громовой камень х1</li><li>Лунный камень х1</li><li>Солнечный камень х1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
			        5 => "*Получить приз повторно*",
			        6 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 6:
			    if($us_bd['virus'] >= 100){
			        $r = rand(1,18);
			        if($r >= 1 and $r <= 3){itemAdd(80,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/80.png" class="item"> Громовой камень <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){itemAdd(81,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/81.png" class="item"> Водный камень <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(82,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/82.png" class="item"> Лиственный камень <b>х1</b>';}
			        elseif($r >= 10 and $r <= 12){itemAdd(83,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/83.png" class="item"> Огненный камень <b>х1</b>';}
			        elseif($r >= 13 and $r <= 15){itemAdd(84,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/84.png" class="item"> Лунный камень <b>х1</b>';}
			        elseif($r >= 16 and $r <= 18){itemAdd(85,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/85.png" class="item"> Солнечный камень <b>х1</b>';}
			        $upd = $us_bd['virus']-100;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>пятый</b> этап ты получишь если у тебя есть еще <b>200</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Даркбол х1</li><li>Мастербол х1</li><li>Бриллиантовый покебол х1</li><li>Набор тренировки х1</li><li>Набор ослаблений х1</li><li>Сладкий кекс х1</li><li>Прочные колючки х1</li><li>Сфера жизни х1</li><li>Эволвер счастья х1</li><li>Гормон тестостерон х1</li><li>Гормон эстроген х1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
			        6 => "*Получить приз повторно*",
			        7 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 7:
			    if($us_bd['virus'] >= 200){
			        $r = rand(1,33);
			        if($r >= 1 and $r <= 3){itemAdd(62,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/62.png" class="item"> Даркбол <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){itemAdd(56,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/56.png" class="item"> Мастербол <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(53,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/53.png" class="item"> Бриллиантовый покебол <b>х1</b>';}
			        elseif($r >= 10 and $r <= 12){itemAdd(197,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/197.png" class="item"> Набор тренировки <b>х1</b>';}
			        elseif($r >= 13 and $r <= 15){itemAdd(198,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/198.png" class="item"> Набор ослаблений <b>х1</b>';}
			        elseif($r >= 16 and $r <= 18){itemAdd(246,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>х1</b>';}
			        elseif($r >= 19 and $r <= 21){itemAdd(168,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/168.png" class="item"> Прочные колючки <b>х1</b>';}
			        elseif($r >= 22 and $r <= 24){itemAdd(166,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/166.png" class="item"> Сфера жизни <b>х1</b>';}
			        elseif($r >= 25 and $r <= 27){itemAdd(107,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/107.png" class="item"> Эволвер счастья <b>х1</b>';}
			        elseif($r >= 28 and $r <= 30){itemAdd(269,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/269.png" class="item"> Гормон тестостерон <b>х1</b>';}
			        elseif($r >= 31 and $r <= 33){itemAdd(270,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/270.png" class="item"> Гормон эстроген <b>х1</b>';}
			        $upd = $us_bd['virus']-200;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>шестой</b> этап ты получишь если у тебя есть еще <b>400</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Сумрачный камень х1</li><li>Сияющий камень х1</li><li>Овальный камень х1</li><li>Камень рассвета х1</li><li>Острый клык х1</li><li>Острый коготь х1</li><li>Жуткая ткань х1</li><li>Электрайзер х1</li><li>Магмарайзер х1</li><li>Протектор х1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
			        7 => "*Получить приз повторно*",
			        8 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 8:
			    if($us_bd['virus'] >= 400){
			        $r = rand(1,30);
			        if($r >= 1 and $r <= 3){itemAdd(86,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/86.png" class="item"> Сумрачный камень <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){itemAdd(87,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/87.png" class="item"> Сияющий камень <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(88,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/88.png" class="item"> Овальный камень <b>х1</b>';}
			        elseif($r >= 10 and $r <= 12){itemAdd(89,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/89.png" class="item"> Камень рассвета <b>х1</b>';}
			        elseif($r >= 13 and $r <= 15){itemAdd(93,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/93.png" class="item"> Острый коготь <b>х1</b>';}
			        elseif($r >= 16 and $r <= 18){itemAdd(94,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/94.png" class="item"> Острый клык <b>х1</b>';}
			        elseif($r >= 19 and $r <= 21){itemAdd(95,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/95.png" class="item"> Жуткая ткань <b>х1</b>';}
			        elseif($r >= 22 and $r <= 24){itemAdd(98,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/98.png" class="item"> Протектор <b>х1</b>';}
			        elseif($r >= 25 and $r <= 27){itemAdd(102,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/102.png" class="item"> Электрайзер <b>х1</b>';}
			        elseif($r >= 28 and $r <= 30){itemAdd(101,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/101.png" class="item"> Магмарайзер <b>х1</b>';}
			        $upd = $us_bd['virus']-400;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>седьмой</b> этап ты получишь если у тебя есть еще <b>600</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>TM08 - Гиперлуч х1</li><li>TM10 - Магический лист х1</li><li>TM14 - Электрошок х1</li><li>TM22 - Камнепад х1</li><li>TM30 - Стальное крыло х1</li><li>TM38 - Блуждающие огни х1</li><li>TM56 - Подставной ход х1</li><li>TM64 - Лавина х1</li><li>TM65 - Коготь тьмы х1</li><li>TM74 - Веношок х1</li><li>TM78 - Акробатика x1</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
			        8 => "*Получить приз повторно*",
			        9 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 9:
			    if($us_bd['virus'] >= 600){
			        $r = rand(1,33);
			        if($r >= 1 and $r <= 3){itemAdd(1008,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1008.png" class="item"> TM08 - Гиперлуч <b>х1</b>';}
			        elseif($r >= 4 and $r <= 6){itemAdd(1010,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1010.png" class="item"> TM10 - Магический лист <b>х1</b>';}
			        elseif($r >= 7 and $r <= 9){itemAdd(1014,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1014.png" class="item"> TM14 - Электрошок <b>х1</b>';}
			        elseif($r >= 10 and $r <= 12){itemAdd(1022,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1022.png" class="item"> TM22 - Камнепад <b>х1</b>';}
			        elseif($r >= 13 and $r <= 15){itemAdd(1030,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1030.png" class="item"> TM30 - Стальное крыло <b>х1</b>';}
			        elseif($r >= 16 and $r <= 18){itemAdd(1038,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1038.png" class="item"> TM38 - Блуждающие огни <b>х1</b>';}
			        elseif($r >= 19 and $r <= 21){itemAdd(1056,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1056.png" class="item"> TM56 - Подставной ход <b>х1</b>';}
			        elseif($r >= 22 and $r <= 24){itemAdd(1064,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1064.png" class="item"> TM64 - Лавина <b>х1</b>';}
			        elseif($r >= 25 and $r <= 27){itemAdd(1065,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1065.png" class="item"> TM65 - Коготь тьмы <b>х1</b>';}
			        elseif($r >= 28 and $r <= 30){itemAdd(1074,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1074.png" class="item"> TM74 - Веношок <b>х1</b>';}
			        elseif($r >= 31 and $r <= 33){itemAdd(1078,1,$_SESSION['id']); $response['actionQuestPlus'] = '<img src="/img/world/items/little/1078.png" class="item"> TM78 - Акробатика <b>х1</b>';}
			        $upd = $us_bd['virus']-600;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $us_bd = $mysqli->query('SELECT `virus` FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
			        $response['question'] = 'Отлично! Приз за <b>восьмой</b> этап ты получишь если у тебя есть еще <b>3.000</b> вылеченных покемонов, а получишь один предмет из этого списка:<br><ol><li>Яйцо #147 Дратини</li><li>Яйцо #446 Манчлакс</li><li>Яйцо #447 Риолу</li><li>Яйцо #885 Дрипи</li></ol><br>У тебя <b>'.$us_bd['virus'].'</b> вылеченных покемонов!';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*",
			        9 => "*Получить приз повторно*",
			        10 => "*Взять приз*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			case 10:
			    if($us_bd['virus'] >= 3000){
			        $r = rand(1,12);
			        $countDay = mt_rand(7,10);
                                    $tm = time()+(3600*24*$countDay);
                                    $gens = "25,25,25,25,25,25";
                                    
			        if($r >= 1 and $r <= 3){plusEgg($gens,false,false,true,$tm,147,false); $response['actionQuestPlus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо #147 Дратини';}
			        elseif($r >= 4 and $r <= 6){plusEgg($gens,false,false,true,$tm,446,false); $response['actionQuestPlus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо #446 Манчлакс';}
			        elseif($r >= 7 and $r <= 9){plusEgg($gens,false,false,true,$tm,447,false); $response['actionQuestPlus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо #447 Риолу';}
			        elseif($r >= 10 and $r <= 12){plusEgg($gens,false,false,true,$tm,885,false); $response['actionQuestPlus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо #885 Дрипи';}
			        $upd = $us_bd['virus']-3000;
			        $mysqli->query('UPDATE `users` SET `virus` = '.$upd.' WHERE `id` = '.$_SESSION['id']);
			        $response['question'] = 'Отлично! Ты забрал все призы! Если у тебя остались еще вылеченные покемоны, то ты можешь вернуться в начало';
			        $response['answer'] = array(
			            2 => "*Вернуться в начало*"
				);
			    }else{
			       $response['question'] = 'У тебя недостаточно вылеченных покемонов, подходи позже!';
			    }
			break;
			default:
			    if(quest_step(5002,1)){
				$response['question'] = 'Привет! Вижу ты вылечил уже много покемонов. Я готова вознаградить тебя за это.';
			    $response['answer'] = array(
			        1 => "Ух ты, спасибо!"
				);
			    }else{
			        $response['question'] = 'Удивительно странный вирус бродит в этом мире...';
				
			    }
			break;
		}
?>

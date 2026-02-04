<?
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Ратим';
	switch($npcStep){
		case 1:
			$response['question'] = 'Мне нужно, чтобы ты принес мне <b>#623 Голурк 50 лвл</b>, и еще у него обязательно должна быть выучена атака <b>Шар Тьмы</b> - это очень важно. Ты справишься?';
			$response['answer'] = array(
				2 => "Конечно, я уже в пути"
			);
		break;
		case 2:
		    if(!quest_isset(43)){
			    $response['question'] = 'Возвращайся как можно скорее';
			    quest_update(43,1);
			    $response['actionQuest'] = 'Получено первое задание в контесте <b>Вневременной гость</b>!';
		    }else{
				$response['question'] = 'Ошибка!';
			}
		break;
		case 3:
		    if(quest_step(43,1)){
		        if(search_pok_active_ivent(623,1,"AND  `lvl` = 50") and check_attack_pok(623,467,"AND  `lvl` = 50") ){
		            if(cool_pok_active(2)){
		                $response['question'] = 'Спасибо, это подходящий покемон! '.$_SESSION['login'].', послушай меня внимательно, мои приборы показывают, что моё присутствие вызывает возмущение в материи, нам нужно торопиться. Скажи вы уже освоили нектроновую энергию?!';
		                $response['answer'] = array(
				            4 => "Я не знаю, скорее всего нет"
			            );
			            delete_pok_active(623,"AND `lvl` = 50  LIMIT 1");
			            quest_update(43,2);
			            $response['actionQuest'] = 'Первое задание в контесте <b>Вневременной гость</b> успешно засчитано!';
			            $text = 'Завершил 1 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужного мне покемона!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 4:
		    if(quest_step(43,2)){
			    $response['question'] = 'Так я думал. Но я уверен, что электроэнергию вы наверняка используете, ее нужно очень много, поэтому прошу принести мне следующих электрических покемонов: <b>#462 Магнезон 50 лвл</b> с атакой <b>Электроволны</b> и <b>#026 Райчу 40 лвл</b> с атакой <b>Электрический шар</b>. На сколько я знаю, тебе это пригодится <i>( Протягивает руку с мешочком )</i>';
			    $response['actionQuest'] = 'Получено второе задание в контесте <b>Вневременной гость</b>!';
			    $response['actionQuestPlus'] = '<img src="/img/world/items/little/80.png" class="item"> Громовой камень <b>x4</b>';
			    itemAdd(80,4);
			    quest_update(43,3);
		    }else{
				$response['question'] = 'Ошибка!';
			}
		break;
		case 5:
		    if(quest_step(43,3)){
		        if(search_pok_active_ivent(462,1,"AND  `lvl` = 50") and check_attack_pok(462,568,"AND  `lvl` = 50") and search_pok_active_ivent(26,1,"AND  `lvl` = 40") and check_attack_pok(26,139,"AND  `lvl` = 40")){
		            if(cool_pok_active(3)){
		                $response['question'] = 'Огромное спасибо, тебе, с помощью их я смогу подзарядить своё оборудование и точно сказать, что происходит. Но есть еще одна проблема...';
		                $response['answer'] = array(
				            6 => "Что за проблема?"
			            );
			            delete_pok_active(462,"AND `lvl` = 50  LIMIT 1");
			            delete_pok_active(26,"AND `lvl` = 40  LIMIT 1");
			            quest_update(43,4);
			            $response['actionQuest'] = 'Второе задание в контесте <b>Вневременной гость</b> успешно засчитано!';
			            $text = 'Завершил 2 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужных мне покемонов!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 6:
		    if(quest_step(43,4)){
			    $response['question'] = 'Один из моих типовых кристаллов сломался, а из-за этого я не могу зарядить аппаратуру. Мне очень нужно, чтобы ты принес мне кристалл <b>призрачного</b> типа';
			    $response['actionQuest'] = 'Получено третье задание в контесте <b>Вневременной гость</b>!';
			    quest_update(43,5);
		    }else{
				$response['question'] = 'Ошибка!';
			}
		break;
		case 7:
		    if(quest_step(43,5)){
		        if(item_isset(219,1)){
		                $response['question'] = 'Спасибо, '.$_SESSION['login'].', за помощь, я этого не забуду. Секунду, я заменю кристалл.<br>Приборы показали, что пространственный поток, был атакован каким-то покемоном, из-за этого, я и оказался тут. И я думаю, что этот покемон не простой, скорее всего, легендарный, но кто бы это мог быть? Хотя, какая разница, главное нам быстрее починить подпространственный переместитесь.';
		                $response['answer'] = array(
				            8 => "Что тебе нужно для этого?"
			            );
			            minus_item(219,1);
			            $response['actionQuestMinus'] = '<img src="/img/world/items/little/219.png" class="item"> Призрачный кристалл <b>x1</b>';
			            quest_update(43,6);
			            $response['actionQuest'] = 'Третье задание в контесте <b>Вневременной гость</b> успешно засчитано!';
			            $text = 'Завершил 3 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		        }else{
		            $response['question'] = 'У тебя нет нужного мне кристалла!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 8:
		    if(quest_step(43,6)){
			    $response['question'] = 'На этот раз мне нужна энергия покемонов призраков. Думаю <b>#094 Генгара 60 лвл</b> с атакой <b>Сглаз</b> вполне подойдет. Знаешь, я хочу тебя немного отбалгодарить за уже проделанный труд. Держи!';
			    $response['actionQuest'] = 'Получено четвертое задание в контесте <b>Вневременной гость</b>!';
			    itemAdd(107,2);
			    $response['actionQuestPlus'] = '<img src="/img/world/items/little/107.png" class="item"> Эволвер счастья <b>x2</b>';
			    quest_update(43,7);
		    }else{
				$response['question'] = 'Ошибка!';
			}
		break;
		case 9:
		    if(quest_step(43,7)){
		        if(search_pok_active_ivent(94,1,"AND  `lvl` = 60") and check_attack_pok(94,236,"AND  `lvl` = 60") ){
		            if(cool_pok_active(2)){
		                $response['question'] = 'Спасибо, это подходящий покемон! Блин, его энергии не хватает, давай попробуем еще и <b>#708 Фантамп 40 лвл</b> с характером <b>непреклонный</b> и атакой <b>Изумление</b>';
			            delete_pok_active(94,"AND `lvl` = 60  LIMIT 1");
			            quest_update(43,8);
			            $response['actionQuest'] = 'Получено шестое задание в контесте <b>Вневременной гость</b>!';
			            $text = 'Завершил 5 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужного мне покемона!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 10:
		    if(quest_step(43,8)){
		        if(search_pok_active_ivent(708,1,"AND  `lvl` = 40 AND `character` = 12") and check_attack_pok(708,24,"AND  `lvl` = 40 AND `character` = 12 ") ){
		            if(cool_pok_active(2)){
		                $response['question'] = 'Отлично, попробуем еще раз! Блин, и его энергии не хватает, давай попробуем еще и <b>#426 Дрифблим 50 лвл</b> со способность <b>Детонация</b>';
			            delete_pok_active(708,"AND  `lvl` = 40 AND `character` = 12  LIMIT 1");
			            quest_update(43,9);
			            $response['actionQuest'] = 'Получено седьмое задание в контесте <b>Вневременной гость</b>!';
			            $text = 'Завершил 6 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужного мне покемона!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 11:
		    if(quest_step(43,9)){
		        if(search_pok_active_ivent(426,1,"AND  `lvl` = 50 AND `ability` = 3")){
		            if(cool_pok_active(2)){
		                $response['question'] = 'Отлично, попробуем еще раз! Да что такое, почему не хватает энергии, давай попробуем еще и <b>#292 Шединья 60 лвл</b> с атакой <b>Коготь тьмы</b>';
			            delete_pok_active(426,"AND  `lvl` = 50 AND `ability` = 3  LIMIT 1");
			            quest_update(43,10);
			            $response['actionQuest'] = 'Получено восьмое задание в контесте <b>Вневременной гость</b>!';
			            $text = 'Завершил 7 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужного мне покемона!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 12:
		    if(quest_step(43,10)){
		        if(search_pok_active_ivent(292,1,"AND  `lvl` = 60") and check_attack_pok(292,468,"AND  `lvl` = 60")){
		            if(cool_pok_active(2)){
		                $response['question'] = 'Думаю, призрачной силы будет достаточно, чтобы открыть портал. Сейчас я попробую сконцентрировать энергию. <i>( Происходит взрыв) </i>Эх, неудача. Теперь нужно искать материалы, чтобы починить оборудование.';
			            delete_pok_active(292,"AND  `lvl` = 60  LIMIT 1");
			            quest_update(43,11);
			            $response['answer'] = array(
				            13 => "Я помогу вам"
			            );
			            $response['actionQuest'] = 'Восьмое задание в контесте <b>Вневременной гость</b> успешно засчитано!';
			            $text = 'Завершил 8 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужного мне покемона!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 13:
		    if(quest_step(43,11)){
			    $response['question'] = 'Это очень хорошо, мне нужно: <b>5 Лазуритов, 5 Малахитов, и 5 Oниксов</b>. Принеси их как можно быстрее.';
			    $response['actionQuest'] = 'Получено девятое задание в контесте <b>Вневременной гость</b>!';
			    quest_update(43,12);
		    }else{
				$response['question'] = 'Ошибка!';
			}
		break;
		case 14:
		    if(quest_step(43,12)){
		        if(item_isset(376,5) and item_isset(375,5) and item_isset(374,5)){
		                $response['question'] = 'Отлично! Я смог починить Подпространственный переместитесь. и уже готов отправляться домой, но перед этим я хотел тебя попросить о двух вещях.';
		                $response['answer'] = array(
				            15 => "Каких?"
			            );
			            minus_item(374,5);
			            minus_item(275,5);
			            minus_item(376,5);
			            $response['actionQuestMinus'] = '<img src="/img/world/items/little/374.png" class="item"> Малахит <b>x5</b><br>
			            <img src="/img/world/items/little/375.png" class="item"> Лазурит <b>x5</b><br>
			            <img src="/img/world/items/little/376.png" class="item"> Оникс <b>x5</b>';
			            quest_update(43,13);
			            $response['actionQuest'] = 'Девятое задание в контесте <b>Вневременной гость</b> успешно засчитано!';
			            $text = 'Завершил 9 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		        }else{
		            $response['question'] = 'У тебя нет нужного мне кристалла!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 15:
		    if(quest_step(43,13)){
			    $response['question'] = 'Не мог бы ты поймать для меня Муркроу? Понимаешь, в моем времени их популяция находится на стадий полнейшего исчезновения. Одна из моих задач, как странника, - это сохранение этих покемонов. Мне нужен любой <b>#198 Муркроу</b>. Я буду тебе очень признателен.';
			    $response['actionQuest'] = 'Получено десятое задание в контесте <b>Вневременной гость</b>!';
			    quest_update(43,14);
		    }else{
				$response['question'] = 'Ошибка!';
			}
		break;
		case 16:
		    if(quest_step(43,14)){
		        if(search_pok_active_ivent(198,1)){
		            if(cool_pok_active(2)){
		                $response['question'] = 'Великолепно, какой прекрасный экземпляр, я обещаю, что позабочусь о нём. Понимаешь, я путешествую по разным временным линиям, и всегда привожу сувениры, чтобы помнить о своих приключениях. Я слышал от местных жителей, что с Гаярдоса иногда можно добыть <b>Чешую</b>. Она будет хорошим сувениром. Принеси ее мне.';
			            delete_pok_active(198," LIMIT 1");
			            quest_update(43,15);
			            $response['actionQuest'] = 'Десятое задание в контесте <b>Вневременной гость</b> успешно засчитано!';
			            $text = 'Завершил 10 задание '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		            }else{
		                $response['question'] = 'У тебя должен остаться хотя бы 1 покемон в команде!';
		            }
		        }else{
		            $response['question'] = 'У тебя нет нужного мне покемона!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		case 17:
		    if(quest_step(43,15)){
		        if(item_isset(429,1)){
		                $response['question'] = 'Спасибо тебе тренер, ты очень хороший человек. Я никогда тебя не забуду. Нам пора прощаться. Еще раз спасибо тебе за помощь. Может еще свидимся, ведь время штука не предсказуемая. До встречи друг!';
			            minus_item(429,1);
			            $response['actionQuestMinus'] = '<img src="/img/world/items/little/429.png" class="item"> Чешуя <b>x1</b>';
			            $response['actionQuestPlus'] = '<img src="/img/world/items/little/32.png" class="item"> Шоколадная конфета <b>x2</b><br><img src="/img/world/items/little/187.png" class="item"> Приманка <b>x10</b><br><img src="/img/world/items/little/268.png" class="item"> Зелье памяти <b>x3</b><br>';
			            quest_update(43,16,1);
			            itemAdd(32,2);
			            itemAdd(187,10);
			            itemAdd(268,3);
			            $response['actionQuest'] = 'Все задания в контесте <b>Вневременной гость</b> успешно выполнены!';
			            $text = 'Завершил контест '. date("Y-m-d H:i:s");
			            $mysqli->query("INSERT INTO `contest_log` (`user`,`text`) VALUES ('".$_SESSION['id']."','".$text."')");
		        }else{
		            $response['question'] = 'У тебя нет нужного мне кристалла!';
		        }
		    }else{
		        $response['question'] = 'Ошибка!';
		    }
		break;
		default:
		if(quest_step(43,1)){
			$response['question'] = 'Привет, '.$_SESSION['login'].'! Я рад видеть тебя снова тут! Ты принес мне <b>#623 Голурк 50 лвл</b> с атакой <b>Шар Тьмы</b>?';
			$response['answer'] = array(
				3 => "Да, он у меня"
			);
		}elseif(quest_step(43,2)){
			$response['question'] = 'Привет! Скажи вы уже освоили нектроновую энергию?!';
			$response['answer'] = array(
				4 => "Я не знаю, скорее всего нет"
			);
		}elseif(quest_step(43,3)){
			$response['question'] = 'Привет! Как обстоят дела с поиском <b>#462 Магнезон 50 лвл</b> с атакой <b>Электроволны</b> и <b>#026 Райчу 40 лвл</b> с атакой <b>Электрический шар</b>?';
			$response['answer'] = array(
				5 => "Все покемоны у меня"
			);
		}elseif(quest_step(43,4)){
			$response['question'] = 'Черт! Я без понятия как решить эту проблему...';
			$response['answer'] = array(
				6 => "Что за проблема?"
			);
		}elseif(quest_step(43,5)){
			$response['question'] = 'Првиет! Нашел <b>призрачный</b> кристал?';
			$response['answer'] = array(
				7 => "Да! Он у меня"
			);
		}elseif(quest_step(43,6)){
			$response['question'] = 'Првиет! Нужно починить пространственный переместитель...';
			$response['answer'] = array(
				8 => "Что тебе нужно для этого?"
			);
		}elseif(quest_step(43,7)){
			$response['question'] = 'Привет! Как обстоят дела с поиском <b>#094 Генгара 60 лвл</b> с атакой <b>Сглаз?</b>?';
			$response['answer'] = array(
				9 => "Покемон у меня"
			);
		}elseif(quest_step(43,8)){
			$response['question'] = 'Привет! Как обстоят дела с поиском <b>#708 Фантамп 40 лвл</b> с характером <b>непреклонный</b> и атакой <b>Изумление</b>?';
			$response['answer'] = array(
				10 => "Покемон у меня"
			);
		}elseif(quest_step(43,9)){
			$response['question'] = 'Привет! Как обстоят дела с поиском <b>#426 Дрифблим 50 лвл</b> со способность <b>Детонация</b>?';
			$response['answer'] = array(
				11 => "Покемон у меня"
			);
		}elseif(quest_step(43,10)){
			$response['question'] = 'Привет! Как обстоят дела с поиском <b>#292 Шединья 60 лвл</b> с атакой <b>Коготь тьмы</b>?';
			$response['answer'] = array(
				12 => "Покемон у меня"
			);
		}elseif(quest_step(43,11)){
			$response['question'] = 'Мне срочно нужны материалы для ремонта';
			$response['answer'] = array(
				13 => "Что для этого нужно?"
			);
		}elseif(quest_step(43,12)){
			$response['question'] = 'Ты принес мне <b>5 Лазуритов, 5 Малахитов, и 5 Oниксов</b>?';
			$response['answer'] = array(
				14 => "Да, все необходимое у меня"
			);
		}elseif(quest_step(43,13)){
			$response['question'] = 'Переместитель успешно починен, но мне нужно попросить у тебя о двух вещах';
			$response['answer'] = array(
				15 => "Каких?"
			);
		}elseif(quest_step(43,14)){
			$response['question'] = 'Ты уже нашел для меня <b>#198 Муркроу</b>?';
			$response['answer'] = array(
				16 => "Да, покемон у меня"
			);
		}elseif(quest_step(43,15)){
			$response['question'] = 'Ты упринес для меня <b>Чешуя</b>?';
			$response['answer'] = array(
				17 => "Да, держи"
			);
		}elseif(!quest_isset(43)){
			$response['question'] = 'Приветствую тебя тренер, меня зовут Ратим, я путешественник во времени в ходе пространственного перемещения, что-то пошло не так, и меня закинуло в вашу временную линию. Для перемещения я использую силу призрачных покемонов. Но у меня беда, я не знаю где найти покемонов-призраков, и я прошу тебя мне помочь.';
            $response['answer'] = array(
				1 => "Конечно, что я должен сделать?"
			);
		}else{
		    $response['question'] = 'Спасибо тебе за помощь!!!';
		}
		break;
	}
?>

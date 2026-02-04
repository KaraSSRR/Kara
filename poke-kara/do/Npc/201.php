<?
    $fix = uFix();
	$stmt = $mysqli->prepare('SELECT * FROM user_pokemons WHERE user_id = ? AND active = ? AND start_pok = ?');
	$A = 1;
    $stmt->bind_param("iii", $fix, $A, $A);
    $stmt->execute();
    $data = $stmt->get_result();
    $pokemon = $data->fetch_assoc();
	$response['name'] = 'Дин и Сэм';
	switch($npcStep){
    case 1:
      $response['question'] = 'Привет, ты отправляешься в глубь леса? <i>~Спросил один из мужчин~</i>';
      $response['answer'] = array(
				2 => "Да. Там пропала девушка. Хочу отыскать ее"
			);
    break;
    case 2:
      $response['question'] = 'Мы не советуем тебе туда идти. Ты ведь слышал, что покемоны бегут оттуда из-за сильного холода? Мы думаем, что там какая-то нечисть... Ледяная нечисть. Слушай. Мы бы помогли отыскать девушку, будь у нас эффективные покемоны против <b>Льда</b>, например, <b>Огненный</b>, <b>Каменный</b> и <b>Боевой</b>. Возможно, что твоя "подружка" все еще там. Вообщем, если хочешь, чтобы мы помогли, то принеси нам трех покемонов данных типов. А лучше с с характером <b>Смелый</b> и уровнем <b>больше 30</b>, чтобы они были хоть как-то боеспособны.';
      $response['answer'] = array(
				3 => "Без проблем. Я принесу вам таких покемонов. Мне главное, чтобы вы отыскали несчастную девушку"
			);
    break;
		case 3:
      if(quest_step(20,1)){
        quest_update(20,2);
        $response['actionQuest'] = '<img src="/img/quests/20.webp" class="quest"> Обновлена информация в задании «Мороз по коже». Загляните в Аквабук.';
        update_zap(20,2,'В лесу я встретил двух ребят, возможно братьев. Они помогут мне с поиском девушки. Два брата говорят, что в лесу поселилась ледяная нечисть. Ее нужно победить. Мне необходимо поймать им различных покемонов. Для начала они попросили <b>Огненного покемона со смелым характером и уровнем более, чем 30</b>. Не буду медлить.');
        $response['question'] = 'Вот и отлично. Неси покемонов поочереди. Сначала принеси нам покемона с типом <b>Огненный</b> со смелым характером и больше 30 ур.';
      }
    break;
		case 4:
      if(quest_step(20,2)){
				if($pokemon && $pokemon['character'] == 23 && $pokemon['lvl'] >= 30) {
					$stmtPB = $mysqli->prepare('SELECT * FROM base_pokemons WHERE id = ?');
                        $stmtPB->bind_param("i", $pokemon['basenum']);
                        $stmtPB->execute();
                        $dataPB = $stmtPB->get_result();
                        $pokemonBase = $dataPB->fetch_assoc();
                        
					if($pokemonBase['id'] >= 1 && $pokemonBase['id'] <= 9) {
						$b = '00'.$$pokemonBase['id'];
					}elseif($pokemonBase['id'] >= 10 && $pokemonBase['id'] <= 99) {
						$b = '0'.$pokemonBase['id'];
					}else{
						$b = $pokemonBase['id'];
					}
					if($pokemonBase['type'] == 'fire' || $pokemonBase['type_two'] == 'fire') {
					    
						$response['actionQuestMinus'] = '<img src="/img/pokemons/anim/normal/'.$pokemonBase['id'].'.gif"> #'.$b.' '.$pokemonBase['name_rus'].'';
						
						$q = $mysqli->prepare('DELETE FROM user_pokemons WHERE user_id = ? AND id = ?'); 
                            if($q)
                            {
                                $q->bind_param("ii", $fix, $pokemon['id']);
                                $q->execute();
                               $q->close();
                            }
						quest_update(20,3);
		        $response['actionQuest'] = '<img src="/img/quests/20.webp" class="quest"> Обновлена информация в задании «Мороз по коже». Загляните в Аквабук.';
		        update_zap(20,3,'Огненного покемона я принес. Теперь мне нужно принести <b>каменного</b>. Характер такой же - <b>смелый</b>. Уровень <b>30 или больше</b>.');
		        $response['question'] = 'Отлично! Тащи следующего покемона. Теперь уже Каменного типа. Смелого, с уровнем 30 или больше.';
					}else{
						$response['question'] = 'У тебя нет нужного покемона.';
					}
				}else{
					$response['question'] = 'У тебя нет нужного покемона.';
				}
      }
    break;
		case 5:
      if(quest_step(20,3)){
				if($pokemon && $pokemon['character'] == 23 && $pokemon['lvl'] >= 30) {
				    
					$stmtPB = $mysqli->prepare('SELECT * FROM base_pokemons WHERE id = ?');
					
                        $stmtPB->bind_param("i", $pokemon['basenum']);
                        $stmtPB->execute();
                        $dataPB = $stmtPB->get_result();
                        $pokemonBase = $dataPB->fetch_assoc();
					if($pokemonBase['id'] >= 1 && $pokemonBase['id'] <= 9) {
						$b = '00'.$$pokemonBase['id'];
					}elseif($pokemonBase['id'] >= 10 && $pokemonBase['id'] <= 99) {
						$b = '0'.$pokemonBase['id'];
					}else{
						$b = $pokemonBase['id'];
					}
					if($pokemonBase['type'] == 'rock' || $pokemonBase['type_two'] == 'rock') {
						$response['actionQuestMinus'] = '<img src="/img/pokemons/anim/normal/'.$pokemonBase['id'].'.gif"> #'.$b.' '.$pokemonBase['name_rus'].'';
						$q = $mysqli->prepare('DELETE FROM user_pokemons WHERE user_id = ? AND id = ?'); 
                            if($q)
                            {
                                $q->bind_param("ii", $fix, $pokemon['id']);
                                $q->execute();
                               $q->close();
                            }
						quest_update(20,4);
		        $response['actionQuest'] = '<img src="/img/quests/20.webp" class="quest"> Обновлена информация в задании «Мороз по коже». Загляните в Аквабук.';
		        update_zap(20,4,'Принес им каменного покемона. Теперь последний - <b>боевой</b>. Характер такой же - <b>смелый</b>. Уровень <b>30 или больше</b>.');
		        $response['question'] = 'Отлично! Тащи следующего покемона. Теперь уже Боевого типа. Смелого, с уровнем 30 или больше.';
					}else{
						$response['question'] = 'У тебя нет нужного покемона.';
					}
				}else{
					$response['question'] = 'У тебя нет нужного покемона.';
				}
      }
    break;
		case 6:
      if(quest_step(20,4)){
				if($pokemon && $pokemon['character'] == 23 && $pokemon['lvl'] >= 30) {
					$stmtPB = $mysqli->prepare('SELECT * FROM base_pokemons WHERE id = ?');
                        $stmtPB->bind_param("i", $pokemon['basenum']);
                        $stmtPB->execute();
                        $dataPB = $stmtPB->get_result();
                        $pokemonBase = $dataPB->fetch_assoc();
					if($pokemonBase['type'] == 'fighting' || $pokemonBase['type_two'] == 'fighting') {
						if($pokemonBase['id'] >= 1 && $pokemonBase['id'] <= 9) {
							$b = '00'.$$pokemonBase['id'];
						}elseif($pokemonBase['id'] >= 10 && $pokemonBase['id'] <= 99) {
							$b = '0'.$pokemonBase['id'];
						}else{
							$b = $pokemonBase['id'];
						}
						$response['actionQuestMinus'] = '<img src="/img/pokemons/anim/normal/'.$pokemonBase['id'].'.gif"> #'.$b.' '.$pokemonBase['name_rus'].'';
						$q = $mysqli->prepare('DELETE FROM user_pokemons WHERE user_id = ? AND id = ?'); 
                            if($q)
                            {
                                $q->bind_param("ii", $fix, $pokemon['id']);
                                $q->execute();
                               $q->close();
                            }
						$rand = rand(1800,2400);
						$wait = time()+$rand;
						$stmtASD = $mysqli->prepare("INSERT INTO `base_npc_data` (`userID`,`npcID`,`time`) VALUES(?,?,?) "); 
                            $stmtASD->bind_param("iii",$fix, $npcId, $wait);
                            $stmtASD->execute();
						quest_update(20,5);
		        $response['actionQuest'] = '<img src="/img/quests/20.webp" class="quest"> Обновлена информация в задании «Мороз по коже». Загляните в Аквабук.';
		        update_zap(20,5,'Я отдал им последнего покемона. Парни ушли биться со злом. Сказали, что придут через <b>30 - 40 минут</b>. Буду ждать их в Лесу.');
		        $response['question'] = 'Отличный покемон. А теперь дай нам время, чтобы справиться со злом. Мы должны уложиться минут за 30 - 40. Жди нас тут.';
					}else{
						$response['question'] = 'У тебя нет нужного покемона.';
					}
				}else{
					$response['question'] = 'У тебя нет нужного покемона.';
				}
      }
    break;
		case 7:
      if(quest_step(20,5)){
				if(!npc_time_check($npcId)){
					newPokemon(582,$fix,5,false,1,'true',1,false,false,false,false,true);
					$response['actionQuestPlus'] = '<img src="/img/pokemons/anim/normal/582.gif"> #582 Ваниллита';
					quest_update(20,6,1);
					$response['actionQuest'] = '<img src="/img/quests/20.webp" class="quest"> Обновлена информация в задании «Мороз по коже». Загляните в Аквабук.';
					update_zap(20,6,'Девушка спасена. В качестве благодарности двое ребят дали мне #582 Ваниллиту, которая была взаперти вместе с девушкой, которую мы искали. Я рад, что все обошлось.');
					$response['question'] = '<i>~Девушка радостно вам говорит~</i> Не надо! Я сама его обрадую. Он очень сильно удивиться!<br>Спасибо тебе за помощь! Если бы не ты, я бы до сих пор там сидела. Возьми в награду этого покемона. Он был обессилен в пещере, в которой держал меня злой Фросласс. Еще раз спасибо тебе большое!';
				}
      }
    break;
		default:
		if(quest_step(20,1)){
			$response['question'] = '<i>~Мужики что-то спорят между собой. Лучше не вмешиваться.~</i>';
      $response['answer'] = array(
				1 => "Здравствуйте"
			);
		}elseif(quest_step(20,2)){
      $response['question'] = 'Принес покемона? <i>~Нужный покемон должен быть отмечен как "стартовый" в Вашей покекарте.~</i>';
       $response['answer'] = array(
			 	4 => "Да, держите"
			 );
    }elseif(quest_step(20,3)){
      $response['question'] = 'Принес покемона? <i>~Нужный покемон должен быть отмечен как "стартовый" в Вашей покекарте.~</i>';
       $response['answer'] = array(
			 	5 => "Да, держите"
			 );
    }elseif(quest_step(20,4)){
      $response['question'] = 'Принес покемона? <i>~Нужный покемон должен быть отмечен как "стартовый" в Вашей покекарте.~</i>';
        $response['answer'] = array(
			  	6 => "Да, держите"
			  );
    }elseif(quest_step(20,5)){
			if(!npc_time_check($npcId)){
				$response['question'] = '<i>Вы видете возвращающихся ребят. С ними идет девушка. Все получилось? Они нашли ее в дебрях холодного леса?</i> Фух! Это было сложно, но мы справились. Твои покемоны нам отлично помогли. Девушку мы нашли. Ее держал взаперти злой Фросласс. Но сейчас с ней все хорошо.';
        $response['answer'] = array(
			  	7 => "Это прекрасная новость. Нужно срочно обрадовать Марка. Он все еще ждет в Фолларборе"
			  );
			}else{
				$response['question'] = '<i>~Ребята еще не вернулись~</i>';
			}
    }else{
			$response['question'] = '<i>~Мужики что-то спорят между собой. Лучше не вмешиваться.~</i>';
		}
		break;
	}
?>

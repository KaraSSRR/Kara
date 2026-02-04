<?
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
	$response['name'] = 'Капитан Сайлен';
	switch($npcStep){
		case 1:
			$response['question'] = 'Он должен был рассказать, что как минимум <b>все принесенные покемоны должны иметь приставку military</b>. Все покемоны должны быть <b>пойманы тобой</b>. Также хочу уточнить, что нам подходит <b>любый дикий покемон</b> и если ты хочешь повысить свои шансы на хорошие призы, то приноси покемонов в <b>более ценных боллах</b>(оцениваются Покебол, Грейтбол, Ультрабол, Даркбол, Мастербол). <br><i>Покемон в Бриллиантовом покеболле с 100% шансом принесет Набор тренировки х1</i>.';
			$response['answer'] = array(
				2 => "У меня уже есть покемоны"
			);
			quest_update(5001,2);
		break;
		case 2:
		    $response['question'] = 'Здорово! Но наши сборы уже закончены. Я позже смогу забрать оставшихся покемонов и дать небольшую награду. Подойди через час, я дам тебе ключ от нашего заповедника.';
    		
		break;
		case 5:
		    $mili_users_bd = $mysqli->query("SELECT `military` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
		    if($mili_users_bd != 0){
		        if($mili_users_bd['military'] >= 1 and $mili_users_bd['military'] <= 2){ $b = 2; }
		        elseif($mili_users_bd['military'] >= 3 and $mili_users_bd['military'] <= 7){ $b = 4; }
		        elseif($mili_users_bd['military'] >= 8 and $mili_users_bd['military'] <= 13){ $b = 6; }
		        elseif($mili_users_bd['military'] >= 14 and $mili_users_bd['military'] <= 30){ $b = 8; }
		        elseif($mili_users_bd['military'] >= 31 and $mili_users_bd['military'] <= 40){ $b = 9; }
		        elseif($mili_users_bd['military'] >= 41 and $mili_users_bd['military'] <= 60){ $b = 12; }
		        elseif($mili_users_bd['military'] >= 60 and $mili_users_bd['military'] <= 80){ $b = 17; }
		        $response['question'] = 'Хорошо, держи ключ! С ним ты можешь пройти в наш заповедник, там обитают редкие покемоны. Я даю тебе разрешение словить там <b>'.$b.' покемонов</b>.';
    		    $mysqli->query('UPDATE `users` SET `military_limit` = '.$b.', `military_key` = 1 WHERE `id` = '.$_SESSION['id']);
    		    quest_update(5001,3);
		    }else{
		        $response['question'] = "К сожалению, я не могу дать тебе ключ";
		    }
		break;
		case 6:
			
			$military_bd = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `type` = "military" and `active` = 1 and `user_id` = '.$_SESSION['id']);
			$i = $military_bd->num_rows;
			if($i != 0){
    			if(npc_active_pok($i)){
                    while($military = $military_bd->fetch_assoc()){
			            $mili_users_bd = $mysqli->query("SELECT `military` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
                        if($mili_users_bd['military'] <= 99){
                        $d = explode(',',$military['birthday']);
                        $s = '{"user_id":"'.$_SESSION['id'].'"';
                        $s1 = '{"user_id":'.$_SESSION['id'].'';
                        if($d[0] == $s or $d[0] == $s1){
                                    $ite .= '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x15.000</b><br>';
                                    itemAdd(1,15000);
                                
                            $pok .= '<img src="/img/pokemons/animation/'.$military['basenum'].'.png"> #'.NumbPok($military['basenum']).' '.$military['name_new'].'<br>';
                            $mysqli->query('DELETE FROM user_pokemons WHERE id = '.$military['id']);
                        }
                        }
                    }
                    $response['actionQuestPlus'] = $ite;
                    $response['actionQuestMinus'] = $pok;
                    $response['question'] = "Спасибо!";
                    
    			}else{
                    $response['question'] = 'У тебя должен остаться хотя бы 1 покемон!';
    			}
			}else{
			    $response['question'] = 'У тебя нет подходящих покемонов!';
			}
		break;
// 		case 2:
// 		    $mili_users_bd = $mysqli->query("SELECT `military` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
// 		    if($mili_users_bd['military'] != 100){
//     			$response['question'] = 'Здорово! Я могу их забрать?<br><i>Персонаж заберет всех подходящих покемонов из вашей команды.</i>';
//     			$response['answer'] = array(
//     				4 => "Да, держи"
//     			);
// 		    }else{
// 		        $response['question'] = 'Спасибо, но ты принес уже максимальное количество нужных нам покемонов! Я смогу у тебя забрать их после окончания сборов за поощрительные призы.';
// 		    }
// 		break;
// 		case 3:
// 			$response['question'] = 'Как минимум <b>все принесенные покемоны должны иметь приставку military</b>. Все покемоны должны быть <b>пойманы тобой</b>. Также хочу уточнить, что нам подходит <b>любый дикий покемон</b> и если ты хочешь повысить свои шансы на хорошие призы, то приноси покемонов в <b>более ценных боллах</b>(оцениваются Покебол, Грейтбол, Ультрабол, Даркбол, Мастербол). <br><i>Покемон в Бриллиантовом покеболле с 100% шансом принесет Набор тренировки х1</i>.';
// 			$response['answer'] = array(
// 				2 => "У меня уже есть покемоны"
// 			);
// 		break;
// 		case 4:
			
// 			$military_bd = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `type` = "military" and `active` = 1 and `user_id` = '.$_SESSION['id']);
// 			$i = $military_bd->num_rows;
// 			if($i != 0){
//     			if(npc_active_pok($i)){
//                     while($military = $military_bd->fetch_assoc()){
// 			            $mili_users_bd = $mysqli->query("SELECT `military` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
//                         if($mili_users_bd['military'] <= 99){
//                         $d = explode(',',$military['birthday']);
//                         $s = '{"user_id":"'.$_SESSION['id'].'"';
//                         $s1 = '{"user_id":'.$_SESSION['id'].'';
//                         if($d[0] == $s or $d[0] == $s1){
//                             if($military['ball'] == 2){ //Покебол
//                                 $r = rand(1,100);
//                                 if($r >= 1 and $r < 20){
//                                     $ite .= '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x25.000</b><br>';
//                                     itemAdd(1,25000);
//                                 }elseif($r >= 20 and $r < 35){
//                                     $ite .= '<img src="/img/world/items/little/15.png" class="item"> Эликсир <b>x1</b><br>';
//                                     itemAdd(15,1);
//                                 }elseif($r >= 35 and $r < 50){
//                                     $ite .= '<img src="/img/world/items/little/28.png" class="item"> Зеленая конфета <b>x3</b><br>';
//                                     itemAdd(28,3);
//                                 }elseif($r >= 50 and $r < 60){
//                                     $ite .= '<img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x1</b><br>';
//                                     itemAdd(29,1);
//                                 }elseif($r >= 60 and $r < 80){
//                                     $n = rand(199,204);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Банка витаминов <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }elseif($r >= 80 and $r < 95){
//                                     $ite .= '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x1</b><br>';
//                                     itemAdd(245,1);
//                                 }elseif($r >= 95 and $r <= 100){
//                                     $n = rand(367,372);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Крыло <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }
//                             }
//                             if($military['ball'] == 3){ //Грейтбол
//                                 $r = rand(1,100);
//                                 if($r >= 1 and $r < 15){
//                                     $ite .= '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x25.000</b><br>';
//                                     itemAdd(1,25000);
//                                 }elseif($r >= 15 and $r < 35){
//                                     $ite .= '<img src="/img/world/items/little/15.png" class="item"> Эликсир <b>x1</b><br>';
//                                     itemAdd(15,1);
//                                 }elseif($r >= 35 and $r < 45){
//                                     $ite .= '<img src="/img/world/items/little/28.png" class="item"> Зеленая конфета <b>x4</b><br>';
//                                     itemAdd(28,4);
//                                 }elseif($r >= 45 and $r < 60){
//                                     $ite .= '<img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x2</b><br>';
//                                     itemAdd(29,2);
//                                 }elseif($r >= 60 and $r < 80){
//                                     $n = rand(199,204);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Банка витаминов <b>x2</b><br>';
//                                     itemAdd($n,2);
//                                 }elseif($r >= 80 and $r < 90){
//                                     $ite .= '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x1</b><br>';
//                                     itemAdd(245,1);
//                                 }elseif($r >= 90 and $r <= 100){
//                                     $n = rand(367,372);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Крыло <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }
//                             }
//                             if($military['ball'] == 4){ //Ультрабол
//                                 $r = rand(1,150);
//                                 if($r >= 1 and $r < 15){
//                                     $ite .= '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x35.000</b><br>';
//                                     itemAdd(1,35000);
//                                 }elseif($r >= 15 and $r < 35){
//                                     $ite .= '<img src="/img/world/items/little/16.png" class="item"> Мощный эликсир <b>x1</b><br>';
//                                     itemAdd(16,1);
//                                 }elseif($r >= 35 and $r < 45){
//                                     $ite .= '<img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x2</b><br>';
//                                     itemAdd(29,2);
//                                 }elseif($r >= 45 and $r < 55){
//                                     $ite .= '<img src="/img/world/items/little/30.png" class="item"> Розовая конфета <b>x2</b><br>';
//                                     itemAdd(30,2);
//                                 }elseif($r >= 55 and $r < 70){
//                                     $n = rand(125,141);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Стабовый усилитель <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }elseif($r >= 70 and $r < 75){
//                                     $ite .= '<img src="/img/world/items/little/196.png" class="item"> Именной бланк <b>x1</b><br>';
//                                     itemAdd(196,1);
//                                 }elseif($r >= 75 and $r <= 90){
//                                     $n = rand(199,204);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Банка витаминов <b>x3</b><br>';
//                                     itemAdd($n,3);
//                                 }elseif($r >= 90 and $r < 105){
//                                     $ite .= '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x2</b><br>';
//                                     itemAdd(245,2);
//                                 }elseif($r >= 105 and $r < 115){
//                                     $ite .= '<img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x1</b><br>';
//                                     itemAdd(246,1);
//                                 }elseif($r >= 115 and $r < 125){
//                                     $ite .= '<img src="/img/world/items/little/269.png" class="item"> Гормон тестостерон <b>x1</b><br>';
//                                     itemAdd(269,1);
//                                 }elseif($r >= 125 and $r < 125){
//                                     $ite .= '<img src="/img/world/items/little/270.png" class="item"> Гормон эстроген <b>x1</b><br>';
//                                     itemAdd(270,1);
//                                 }elseif($r >= 125 and $r <= 140){
//                                     $n = rand(367,372);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Крыло <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }elseif($r >= 140 and $r < 150){
//                                     $ite .= '<img src="/img/world/items/little/473.png" class="item"> Псевдогормон <b>x1</b><br>';
//                                     itemAdd(473,1);
//                                 }
//                             }
//                             if($military['ball'] == 62){ //Даркбол
//                                 $r = rand(1,240);
//                                 if($r >= 1 and $r < 20){
//                                     $ite .= '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x55.000</b><br>';
//                                     itemAdd(1,55000);
//                                 }elseif($r >= 20 and $r < 40){
//                                     $ite .= '<img src="/img/world/items/little/16.png" class="item"> Мощный эликсир <b>x2</b><br>';
//                                     itemAdd(16,2);
//                                 }elseif($r >= 40 and $r < 60){
//                                     $ite .= '<img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x3</b><br>';
//                                     itemAdd(29,3);
//                                 }elseif($r >= 60 and $r < 70){
//                                     $ite .= '<img src="/img/world/items/little/30.png" class="item"> Розовая конфета <b>x3</b><br>';
//                                     itemAdd(30,3);
//                                 }elseif($r >= 70 and $r < 100){
//                                     $n = rand(125,141);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Стабовый усилитель <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }elseif($r >= 100 and $r < 110){
//                                     $ite .= '<img src="/img/world/items/little/153.png" class="item"> Защитные очки <b>x1</b><br>';
//                                     itemAdd(153,1);
//                                 }elseif($r >= 110 and $r < 120){
//                                     $ite .= '<img src="/img/world/items/little/155.png" class="item"> Быстрый коготь <b>x1</b><br>';
//                                     itemAdd(155,1);
//                                 }elseif($r >= 120 and $r < 130){
//                                     $ite .= '<img src="/img/world/items/little/196.png" class="item"> Именной бланк <b>x1</b><br>';
//                                     itemAdd(196,1);
//                                 }elseif($r >= 130 and $r < 145){
//                                     $ite .= '<img src="/img/world/items/little/196.png" class="item"> Набор тренировки <b>x1</b><br>';
//                                     itemAdd(197,1);
//                                 }elseif($r >= 145 and $r <= 165){
//                                     $n = rand(199,204);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Банка витаминов <b>x5</b><br>';
//                                     itemAdd($n,5);
//                                 }elseif($r >= 165 and $r < 185){
//                                     $ite .= '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x2</b><br>';
//                                     itemAdd(245,2);
//                                 }elseif($r >= 185 and $r < 200){
//                                     $ite .= '<img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x1</b><br>';
//                                     itemAdd(246,1);
//                                 }elseif($r >= 200 and $r < 210){
//                                     $ite .= '<img src="/img/world/items/little/269.png" class="item"> Гормон тестостерон <b>x1</b><br>';
//                                     itemAdd(269,1);
//                                 }elseif($r >= 210 and $r < 220){
//                                     $ite .= '<img src="/img/world/items/little/270.png" class="item"> Гормон эстроген <b>x1</b><br>';
//                                     itemAdd(270,1);
//                                 }elseif($r >= 220 and $r <= 230){
//                                     $n = rand(367,372);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Крыло <b>x3</b><br>';
//                                     itemAdd($n,3);
//                                 }elseif($r >= 230 and $r < 240){
//                                     $ite .= '<img src="/img/world/items/little/473.png" class="item"> Псевдогормон <b>x1</b><br>';
//                                     itemAdd(473,1);
//                                 }
//                             }
//                             if($military['ball'] == 56){ //Мастербол
//                                 $r = rand(1,240);
//                                 if($r >= 1 and $r < 20){
//                                     $ite .= '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x55.000</b><br>';
//                                     itemAdd(1,55000);
//                                 }elseif($r >= 20 and $r < 40){
//                                     $ite .= '<img src="/img/world/items/little/16.png" class="item"> Мощный эликсир <b>x2</b><br>';
//                                     itemAdd(16,2);
//                                 }elseif($r >= 40 and $r < 60){
//                                     $ite .= '<img src="/img/world/items/little/29.png" class="item"> Фиолетовая конфета <b>x3</b><br>';
//                                     itemAdd(29,3);
//                                 }elseif($r >= 60 and $r < 70){
//                                     $ite .= '<img src="/img/world/items/little/30.png" class="item"> Розовая конфета <b>x3</b><br>';
//                                     itemAdd(30,3);
//                                 }elseif($r >= 70 and $r < 100){
//                                     $n = rand(125,141);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Стабовый усилитель <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }elseif($r >= 100 and $r < 110){
//                                     $ite .= '<img src="/img/world/items/little/153.png" class="item"> Защитные очки <b>x1</b><br>';
//                                     itemAdd(153,1);
//                                 }elseif($r >= 110 and $r < 120){
//                                     $ite .= '<img src="/img/world/items/little/155.png" class="item"> Быстрый коготь <b>x1</b><br>';
//                                     itemAdd(155,1);
//                                 }elseif($r >= 120 and $r < 130){
//                                     $ite .= '<img src="/img/world/items/little/196.png" class="item"> Именной бланк <b>x1</b><br>';
//                                     itemAdd(196,1);
//                                 }elseif($r >= 130 and $r < 145){
//                                     $ite .= '<img src="/img/world/items/little/196.png" class="item"> Набор тренировки <b>x1</b><br>';
//                                     itemAdd(197,1);
//                                 }elseif($r >= 145 and $r <= 165){
//                                     $n = rand(199,204);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Банка витаминов <b>x5</b><br>';
//                                     itemAdd($n,5);
//                                 }elseif($r >= 165 and $r < 185){
//                                     $ite .= '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x2</b><br>';
//                                     itemAdd(245,2);
//                                 }elseif($r >= 185 and $r < 200){
//                                     $ite .= '<img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x1</b><br>';
//                                     itemAdd(246,1);
//                                 }elseif($r >= 200 and $r < 210){
//                                     $ite .= '<img src="/img/world/items/little/269.png" class="item"> Гормон тестостерон <b>x1</b><br>';
//                                     itemAdd(269,1);
//                                 }elseif($r >= 210 and $r < 220){
//                                     $ite .= '<img src="/img/world/items/little/270.png" class="item"> Гормон эстроген <b>x1</b><br>';
//                                     itemAdd(270,1);
//                                 }elseif($r >= 220 and $r <= 230){
//                                     $n = rand(367,372);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> Крыло <b>x3</b><br>';
//                                     itemAdd($n,3);
//                                 }elseif($r >= 230 and $r < 240){
//                                     $ite .= '<img src="/img/world/items/little/473.png" class="item"> Псевдогормон <b>x1</b><br>';
//                                     itemAdd(473,1);
//                                 }
//                             }
//                             if($military['ball'] == 53){ //Бриллиантовый покебол
//                                 $ite .= '<img src="/img/world/items/little/197.png" class="item"> Набор тренировки <b>x1</b><br>';
//                                 itemAdd(197,1);
//                             }
//                             $pok .= '<img src="/img/pokemons/animation/'.$military['basenum'].'.png"> #'.NumbPok($military['basenum']).' '.$military['name_new'].'<br>';
//                             add_pokemon_military();
//                             $mysqli->query('DELETE FROM user_pokemons WHERE id = '.$military['id']);
//                             $mili_users = $mysqli->query("SELECT `military` FROM `users` WHERE `id`= ".$_SESSION['id'])->fetch_assoc();
//                             if($mili_users['military'] == 20 or $mili_users['military'] == 40 or $mili_users['military'] == 60 or $mili_users['military'] == 80 or $mili_users['military'] == 100){
//                                 if(rand(1,2) == 1){
//                                 $n = rand(1001,1099);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> ТМ-Атака <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }else{
//                                     $n = rand(2000,2098);
//                                     $ite .= '<img src="/img/world/items/little/'.$n.'.png" class="item"> ТR-Атака <b>x1</b><br>';
//                                     itemAdd($n,1);
//                                 }
//                             }
//                         }
//                         }
//                     }
//                     $response['actionQuestPlus'] = $ite;
//                     $response['actionQuestMinus'] = $pok;
//                     $response['question'] = "Спасибо!";
                    
//     			}else{
//                     $response['question'] = 'У тебя должен остаться хотя бы 1 покемон!';
//     			}
// 			}else{
// 			    $response['question'] = 'У тебя нет подходящих покемонов!';
// 			}
// 		break;
		
		default:
		if(quest_step(5001,1)){
			$response['question'] = 'Привет, '.$_SESSION['login'].'! Мне Камадо передал, что ты подойдешь ко мне. Он объяснил каких покемонов мы ищем или рассказать подробнее?';
			$response['answer'] = array(
				1 => "Хочу услышать поподробнее"
			);
		}
// 		elseif(quest_step(5001,2)){
// 			$response['question'] = 'Ну что, ты принес нужных покемонов?';
// 			$response['answer'] = array(
// 				3 => "Напомни, каких покемонов нужно принести?",
// 				2 => "Я принес несколько покемонов"
// 			);
// 		}
		elseif(quest_step(5001,3)){
			$response['question'] = 'Привет, прекрасная погода!';
			$response['answer'] = array(
				6 => "Я могу отдать еще покемонов?"
			);
		}
		elseif(quest_step(5001,2)){
			$response['question'] = 'Привет, спасибо тебе огромное за помощь, ты пришел за ключом?';
			$response['answer'] = array(
				5 => "Да, я пришел за ключом"
			);
		}
		else{
			$response['question'] = 'Привет, тяжелые сейчас времена!';
		}
		break;
	}
?>

<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
		require_once($patch_func);
    }
}

if(!empty($_POST['pokemon_catch'])){
    $id = escapeMe($_POST['pokemon_catch']);
    $pok = $mysqli->query("SELECT * FROM `a_ivent_week_pokemon_catch` WHERE `id` = ".$id)->fetch_assoc();
    if(!empty($pok) and $pok['user'] == $_SESSION['id'] and $pok['view'] != 1){
        $mission = $mysqli->query("SELECT `catch` FROM `a_ivent_week_mission` WHERE `user` = ".$_SESSION['id'])->fetch_assoc();
        if($mission['catch'] >= 1){
            if($mission['catch'] != 1){
                $response['html'] = "Прогресс увеличен!";
                $response['error'] = "success";
            }else{
                $response['html'] = "Вы успешно выполнили задание!";
                $response['error'] = "success";
            }
            week_mission('catch');
            $mysqli->query("UPDATE `a_ivent_week_pokemon_catch` SET `view` = '1'  WHERE  `id` = '".$id."'");
        }else{
            $response['html'] = "Лимит задания превышен!";
            $response['error'] = "error";
        }
    }else{
        $response['html'] = "Ошибка!";
        $response['error'] = "error";
    }
}
    
    



if(!empty($_POST['hell_team'])){
    $id = $_POST['hell_team'];
    $user = $mysqli->query("SELECT `hell_team` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
    if($user['hell_team']  != 0){
        if($id == 1){
                $mysqli->query("UPDATE `users` SET `status`='battle',`hell_team`='0' WHERE `id`='".$_SESSION['id']."'");
                $location_id = $mysqli->query("SELECT `id`,`login`,`user_group`,`region`,`location`,`sex`,`ban`,`status`,`status_id`,`rating`,`rang`,`botID`,`sprite` FROM `users` WHERE `id`='".$_SESSION['id']."'")->fetch_assoc();
                $lvl_bos = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = '.$_SESSION['id'].' AND `active` = 1 ORDER BY `lvl` DESC LIMIT 1 ')->fetch_assoc();
                $lvl = $lvl_bos['lvl']+rand(15,20);
                if($lvl < 55) $lvl = 55;
                if($lvl > 100) $lvl = 100;
                Info::_generatePve($location_id, 2,null,2143,null,$lvl);
                $response['html'] = "Бой начался!";
                $response['error'] = "success";
        }else{
            if(item_isset(1,$user['hell_team'])){
                $response['minus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкар <b>'.$user['hell_team'].'</b>';
                $response['html'] = 'Вы успешно откупились!';
                $response['error'] = 'success';
                $mysqli->query("UPDATE `users` SET `hell_team`= 0 WHERE `id`='".$_SESSION['id']."'");
                minus_item(1,$user['hell_team']);
            }else{
                $response['html'] = 'У вас не хватает денег!';
                $response['error'] = 'error';
            }
        }
    }else{
        $response['html'] = 'Ошибка!';
        $response['error'] = 'error';
    } 
        
}

if(!empty($_POST['hell_candy'])){
    $id = $_POST['hell_candy'];
    $user = $mysqli->query("SELECT `hell_candy` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
    if($user['hell_candy']  != 0){
        if(rand(1,2) == 1){
            $id = 432;
        }
        itemAdd($id,1,$_SESSION['id']);
        $response['plus'] = '<img src="/img/world/items/little/'.$id.'.png" class="item"> '.info_item($id,'name').' <b>x1</b>';
        $response['html'] = 'Конфетка найдена!';
        $response['error'] = 'success';
        $mysqli->query("UPDATE `users` SET `hell_candy`= 0 WHERE `id`='".$_SESSION['id']."'");
    }else{
        $response['html'] = 'Ошибка!';
        $response['error'] = 'error';
    } 
        
}
if (!empty($_POST['web'])) {
    $slot = (int)$_POST['web'];
    $type = (int)$_POST['type'];
    $uid  = (int)$_SESSION['id'];

    if ($type == 1) {
        // ОТКРЫТИЕ ДОП. СЛОТОВ
        $user = $mysqli->query("SELECT `web_lot` FROM `users` WHERE `id` = {$uid} LIMIT 1")->fetch_assoc();
        $cur  = isset($user['web_lot']) ? (int)$user['web_lot'] : 0;

        // Разрешаем открывать ТОЛЬКО следующий слот
        $costMap = [3 => 20, 4 => 30, 5 => 40];               // сколько камней нужно, когда текущее = key
        if (isset($costMap[$cur]) && $slot === $cur + 1) {
            $need = $costMap[$cur];
            if (item_isset(25, $need)) {
                minus_item(25, $need);
                $mysqli->query("UPDATE `users` SET `web_lot` = ".($cur + 1)." WHERE `id` = {$uid} LIMIT 1");

                $response['text']  = 'Успех!';
                $response['error'] = 'success';
                $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x'.$need.'</b>';
            } else {
                $response['text']  = 'Недостаточно драгоценных камней.';
                $response['error'] = 'error';
            }
        } else {
            $response['text']  = 'Ошибка!';
            $response['error'] = 'error';
        }

    } elseif ($type == 2) {
        // ПОСТАНОВКА ПРИМАНКИ В СЛОТ
        $us  = $mysqli->query("SELECT `web_lot`,`location` FROM `users` WHERE `id` = {$uid} LIMIT 1")->fetch_assoc();
        $lot = $mysqli->query("SELECT * FROM `user_web_slot` WHERE `location` = ".(int)$us['location']." AND `slot` = {$slot} AND `user` = {$uid} LIMIT 1")->fetch_assoc();

        // базовые проверки
        if ($slot < 1 || $slot > 6 || $slot > (int)$us['web_lot']) {
            $response['text']  = 'Слот закрыт.';
            $response['error'] = 'error';
        } elseif (!item_isset(187, 1)) {
            $response['text']  = 'У вас нет приманок!';
            $response['error'] = 'error';
        } elseif (!empty($lot)) {
            $response['text']  = 'Слот занят!';
            $response['error'] = 'error';
        } else {
            // ставим приманку + фиксируем время
            $now = time();
            $mysqli->query("INSERT INTO `user_web_slot` (`location`,`user`,`slot`,`pok`,`placed_at`) 
                            VALUES (".(int)$us['location'].", {$uid}, {$slot}, 0, {$now})");
            minus_item(187, 1);

            $response['text']  = 'Успешно!';
            $response['error'] = 'success';
            $response['minus'] = '<img src="/img/world/items/little/187.png" class="item"> Приманка <b>x1</b>';
        }

    } else {
        // ЗАБРАТЬ ПОКЕМОНА / СНЯТЬ ПРИМАНКУ
        $us  = $mysqli->query("SELECT `web_lot`,`location` FROM `users` WHERE `id` = {$uid} LIMIT 1")->fetch_assoc();
        $lot = $mysqli->query("SELECT * FROM `user_web_slot` WHERE `location` = ".(int)$us['location']." AND `slot` = {$slot} AND `user` = {$uid} LIMIT 1")->fetch_assoc();

        if (!empty($lot) && (int)$lot['pok'] != 0) {
            // есть покемон — выдаём и очищаем слот
            newPokemon((int)$lot['pok'], $uid, rand(4,8), false, false, 1, 0, false, false, false, false, false, true);

            // история: игрок забрал покемона из сети
            $mysqli->query("INSERT INTO `user_web_history` (`user`,`location`,`slot`,`pok`,`caught`,`action`,`time`)
                            VALUES ({$uid}, ".(int)$us['location'].", {$slot}, ".(int)$lot['pok'].", 1, 'take', ".time().")");

            $mysqli->query("DELETE FROM `user_web_slot` WHERE `id` = ".(int)$lot['id']." LIMIT 1");

            $response['text']  = 'Успешно!';
            $response['error'] = 'success';

        } elseif (!empty($lot) && (int)$lot['pok'] == 0) {
            // приманка стояла, но покемона нет — снимаем приманку (по желанию можно вернуть предмет)
            $mysqli->query("INSERT INTO `user_web_history` (`user`,`location`,`slot`,`pok`,`caught`,`action`,`time`)
                            VALUES ({$uid}, ".(int)$us['location'].", {$slot}, 0, 0, 'remove', ".time().")");
            $mysqli->query("DELETE FROM `user_web_slot` WHERE `id` = ".(int)$lot['id']." LIMIT 1");

            $response['text']  = 'Приманка снята.';
            $response['error'] = 'success';
        } else {
            $response['text']  = 'Ошибка!';
            $response['error'] = 'error';
        }
    }
}

if(!empty($_POST['itemsplash'])){
    $itemsQuery = $mysqli->query('SELECT `iu`.*, `bi`.`lombard`,`bi`.`rait_it`,`bi`.`name`,`bi`.`about`,`bi`.`weight`,`bi`.`use`,`bi`.`dress`,`bi`.`drop`,`bi`.`trade`,`bi`.`give`,`bi`.`info`,`bi`.`tm_id`,`bi`.`type` FROM `items_users` AS `iu` INNER JOIN `base_items` AS `bi` ON `bi`.`id` = `iu`.`item_id`  WHERE `iu`.`user` = '.intval($_SESSION['id']).'  and `iu`.`item_id` = '.intval($_POST['itemsplash']).' ');
   $user = $mysqli->query("SELECT `status`,`bagType`,`location`,`items_filter` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
		
   $LocationNpcCount = $mysqli->query('SELECT `id` FROM `base_npc` WHERE `loc_id` = '.$user['location']);
    while($items = $itemsQuery->fetch_assoc()){
        $str = explode(',',$items['str']);
			$imgItem = ($items['item_id'] >= 1000001 ? $mesto[0].'.'.$mesto[1] : $items['item_id']);
            $weight = $items['count']*$items['weight'] + $weight;
            if($items['dop'] != 0){ $dop = ", ".$items['dop']."%";}else{ $dop = "";}
            if($items['dop'] != 0 and $items['item_id'] == 9999){$dop = " #".$items['dop']; }
            $basenumegg = 0;
			$tpl .= '<div class="Item" onclick="itemOpen(this,\'modificator\','.$items['id'].',\''.($items['item_id'] == 10001 ? $items['name'].' №'.$items['json'] : $items['name']).'\',\''.$items['about'].'\','.$imgItem.','.$items['count'].','.$items['weight'].','.$items['use'].','.$items['dress'].','.$items['drop'].','.$items['trade'].','.$items['give'].',\'false\',\''.$user['status'].'\',false,'.$LocationNpcCount->num_rows.',\''.$items['type'].'\',\''.$dop.'\',\''.$basenumegg.'\','.$items['lombard'].');"><div class="blockrait more"></div>';
			if($items['date_expiration'] != 0) $tpll.= '<div class="ItemReborn">'.$expiration.'%</div>';
			if($items['type'] == 'tm' and $items['item_id'] <= 1100){ $tpll.= '<div class="Name">TM '.$items['tm_id'].'</div>'; }
			if($items['type'] == 'tm' and $items['item_id'] >= 2000){ $tpll.= '<div class="Name">TR '.$tr_id.'</div>'; }
			if($items['item_id'] == 9999){ $tpll.= '<div class="Name">#'.$items['dop'].'</div>'; }
			$tpl .= '<img id="imgItem" src="/img/world/items/little/'.$imgItem.'.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');">';
			$tpl .= '<div class="Count">'.($items['count'] != 1 ? number_format($items['count'],0,'.','.') : '').' </div> '.($items['str'] != NULL ? '<div class="Str">'.$str[0].'/'.$str[1].'</div>' : '');
			$tpl .= '</div>';
        
        
        
        
    }
    
    $response['html'] = $tpl;
}
if(!empty($_POST['lotpok'])){
    $id = escapeMe($_POST['lotpok']);
    $start = escapeMe($_POST['start']);
    $hod = escapeMe($_POST['hod']);
    $day = escapeMe($_POST['day']);
    $buy = escapeMe($_POST['buy']);
    $prodv = escapeMe($_POST['prodv']);
    if(!$buy) $buy = 0;
    if(!$prodv) $prodv = 0;
    if(!empty($start) and !empty($hod) and !empty($day)){
        $pok_bd = $mysqli->query("SELECT `id`,`name_new`,`lvl`,`basenum`,`trade`,`user_id`,`type` FROM `user_pokemons` WHERE `id`= ".$id." ")->fetch_assoc();
        $pok_bd_base = $mysqli->query("SELECT `id`,`name` FROM `base_pokemons` WHERE `id`= ".$pok_bd['basenum']." ")->fetch_assoc();
        $pok_bd_count = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `active`= 1 AND `user_id` = ".$_SESSION['id']." ");
        if($pok_bd_count->num_rows >= 2){
            if($pok_bd['user_id'] == $_SESSION['id']){
                if($pok_bd['trade'] == "true"){
                    if(($prodv == 0 and item_isset(1,40000)) or ($prodv != 0 and item_isset(1,65000))){
                        if(($start >= 10000 and $start <= 10000000) and $hod <= 1000000){
                            if($day >= 3 and $day < 30){
                                if($buy == 0 or ($buy != 0 and $buy > $start)){
                                    if($buy <= 100000000){
                                        if($pok_bd['type'] != "normal"){
                                            $text = "<span class=\'shine-color\'>#".numbPok($pok_bd['basenum'])." ".$pok_bd_base['name']." ".$pok_bd['lvl']."-lvl</span>";
                                        }else{
                                            $text = "#".numbPok($pok_bd['basenum'])." ".$pok_bd_base['name']." ".$pok_bd['lvl']."-lvl";
                                        }
                                        $c = time()+3600*24*$day;
                                        $pr = $start-$hod;
                                        if($pr < 0 ){
                                            $pr = $start;
                                        }
                                        $mysqli->query("INSERT INTO `log_lombard` (`category`,`name`,`count`,`priceStart`,`priceStep`,`priceNow`,`priceBuy`,`dateEnd`,`userID`,`productID`,`prodv`) VALUES 
                                         ('pokemon','".$text."','".$pok_bd['basenum']."','".$start."','".$hod."','".$pr."','".$buy."','".$c."','".$_SESSION['id']."','".$pok_bd['id']."','".$prodv."')");
                                        $mysqli->query("UPDATE `user_pokemons` SET `user_id`= 3,`active`= 0 WHERE `id`='".$id."'");
                                        
                                        if($prodv == 0){
                                             minus_item(1,40000);
                                        $response['minus'] = "<img src='/img/pokemons/animation/".$pok_bd['basenum'].".png'> ".$text."<br><img src='/img/world/items/little/1.png' class='item'> Генкар <b>x40.000</b>";
                                         }else{
                                             minus_item(1,65000);
                                        $response['minus'] = "<img src='/img/pokemons/animation/".$pok_bd['basenum'].".png'> ".$text."<br><img src='/img/world/items/little/1.png' class='item'> Генкар <b>x65.000</b>";
                                         }
                                        $response['html'] = 'Успешно!';
                                        $response['info'] = 'success';
                                    }else{
                                        $response['html'] = 'Выкуп не может превышать 100.000.000 моет!';
                                        $response['info'] = 'error';
                                    }
                                }else{
                                    $response['html'] = 'Выкуп должен превышать начальную стоимость!';
                                    $response['info'] = 'error';
                                }
                            }else{
                                $response['html'] = 'Лот можно выставить от 3 до 30 дней!';
                                $response['info'] = 'error';
                            }
                        }else{
                            $response['html'] = 'Начальная ставка должна быть от 10.000 до 10.000.000 Генкар, а также максимальный размер хода 1.000.000!';
                            $response['info'] = 'error';
                        }
                    }else{
                        $response['html'] = 'У вас недостаточно генкаров!';
                        $response['info'] = 'error';
                    }
                }else{
                    $response['html'] = 'Этот покемон приручен!';
                    $response['info'] = 'error';
                }
            }else{
                $response['html'] = 'Это не ваш покемон!';
                $response['info'] = 'error';
            }
        }else{
            $response['html'] = 'У вас должен остаться хотя бы 1 покемон!';
            $response['info'] = 'error';
        }
    }else{
        $response['html'] = 'Не все поля заполнены!';
        $response['info'] = 'error';
    }
}


if(!empty($_POST['lotitem'])){
    $id = escapeMe($_POST['lotitem']);
    $count = escapeMe($_POST['count']);
    $start = escapeMe($_POST['start']);
    $hod = escapeMe($_POST['hod']);
    $day = escapeMe($_POST['day']);
    $buy = escapeMe($_POST['buy']);
    $prodv = escapeMe($_POST['prodv']);
    if(!$buy) $buy = 0;
    if(!$prodv) $prodv = 0;
    if(!empty($count) and !empty($start) and !empty($hod) and !empty($day)){
        $item_bd_us = $mysqli->query("SELECT * FROM `items_users` WHERE `id`= ".$id." ")->fetch_assoc();
        $item_bd = $mysqli->query("SELECT * FROM `base_items` WHERE `id`= ".$item_bd_us['item_id']." ")->fetch_assoc();
        if($item_bd_us['user'] == $_SESSION['id']){
            if($item_bd_us['count'] >= $count and $count >= 1){
                if($item_bd['lombard'] == "true"){
                    if(($prodv == 0 and item_isset(1,40000)) or ($prodv != 0 and item_isset(1,65000))){
                        if(($start >= 10000 and $start <= 10000000) and $hod <= 1000000){
                            if($day >= 3 and $day < 30){
                                if($buy == 0 or ($buy != 0 and $buy > $start)){
                                    if($buy <= 100000000){
                                        if($item_bd_us['str'] != ""){
                                            $s = explode(',',$item_bd_us['str']);
                                            $text = $item_bd['name']." [".$s[0]."/".$s[1]."]";
                                            $mysqli->query("DELETE FROM `items_users` WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['id']."' ");
                                        }else{
                                            $text = $item_bd['name']." <b>x".number_format($count,0,'.','.')." </b>";
                                            minus_item($item_bd['id'],$count);
                                        }
                                        $c = time()+3600*24*$day;
                                        $pr = $start-$hod;
                                        if($pr < 0 ){
                                            $pr = $start;
                                        }
                                        $mysqli->query("INSERT INTO `log_lombard` (`category`,`name`,`count`,`priceStart`,`priceStep`,`priceNow`,`priceBuy`,`dateEnd`,`userID`,`productID`,`strItem`,`prodv`) VALUES 
                                         ('".$item_bd['type']."','".$text."','".$count."','".$start."','".$hod."','".$pr."','".$buy."','".$c."','".$_SESSION['id']."','".$item_bd['id']."','".$item_bd_us['str']."','".$prodv."')");
                                         if($prodv == 0){
                                             minus_item(1,40000);
                                        $response['minus'] = "<img src='/img/world/items/little/".$item_bd['id'].".png'  class='item'> ".$text."<br><img src='/img/world/items/little/1.png' class='item'> Генкар <b>x40.000</b>";
                                         }else{
                                             minus_item(1,65000);
                                        $response['minus'] = "<img src='/img/world/items/little/".$item_bd['id'].".png'  class='item'> ".$text."<br><img src='/img/world/items/little/1.png' class='item'> Генкар <b>x65.000</b>";
                                         }
                                        
                                        
                                        $response['html'] = 'Успешно!';
                                        $response['info'] = 'success';
                                    }else{
                                        $response['html'] = 'Выкуп не может превышать 100.000.000 моет!';
                                        $response['info'] = 'error';
                                    }
                                }else{
                                    $response['html'] = 'Выкуп должен превышать начальную стоимость!';
                                    $response['info'] = 'error';
                                }
                            }else{
                                $response['html'] = 'Лот можно выставить от 3 до 30 дней!';
                                $response['info'] = 'error';
                            }
                        }else{
                            $response['html'] = 'Начальная ставка должна быть от 10.000 до 10.000.000 генкар, а также максимальный размер хода 1.000.000!';
                            $response['info'] = 'error';
                        }
                    }else{
                        $response['html'] = 'У вас недостаточно генкар!';
                        $response['info'] = 'error';
                    }
                }else{
                    $response['html'] = 'Этот предмет не продается!';
                    $response['info'] = 'error';
                }
            }else{
                $response['html'] = 'Некоректно установлено количество!';
                $response['info'] = 'error';
            }
        }else{
            $response['html'] = 'Это не ваш предмет!';
            $response['info'] = 'error';
        }
    }else{
        $response['html'] = 'Не все поля заполнены!';
        $response['info'] = 'error';
    }
}

if(!empty($_POST['vikup'])){
    $lot = escapeMe($_POST['vikup']);
    $lot_bd = $mysqli->query("SELECT * FROM `log_lombard` WHERE `id`= ".$lot)->fetch_assoc();
    $d = date("Y-m-d H:i:s");
    $month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
                        	$dayToday = date("d");
                        	$monthToday = $month[date("n")];
                        	$YearToday = date("Y");
                        	$date = $dayToday.' '.$monthToday.' '.$YearToday.'г. в '.date("H").':'.date("i");
    if($lot_bd['userID'] != $_SESSION['id']){
        if($lot_bd['priceBuy'] > 0){
            if(item_isset(1,$lot_bd['priceBuy'])){
                    if($lot_bd['dateEnd'] > time()){
                        //возврат прошлой ставки
                        $lot_bd_sc = $mysqli->query("SELECT * FROM `log_lombard_bidcounter` WHERE `lotID`= ".$lot." ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
                        if($lot_bd_sc){
                            itemAdd(1,$lot_bd_sc['money'],$lot_bd_sc['user']);
                        	$text = 'Ваша ставка на лот #'.$lot.' была перебита! Лот был выкуплен!';
                        	$mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$text."','".$lot_bd_sc['user']."','/img/world/items/little/1.png','".$date."')");
                        }
                        $text2 = "Ваш лот ".$lot_bd['name']." был успешно куплен за ".number_format($lot_bd['priceBuy'],0,'.','.')." м.";
                            $mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$text2."','".$lot_bd['userID']."','/img/world/items/little/1.png','".$date."')");
                        //новая ставка
                        if($lot_bd['category'] == "pokemon"){
                            $mysqli->query("UPDATE `user_pokemons` SET `user_id`= ".$_SESSION['id'].",`active`= 0 WHERE `id`='".$lot_bd['productID']."'");
                        }else{
                            if($lot_bd['strItem'] != ""){
                                $mysqli->query("INSERT INTO `items_users` (`item_id`,`count`,`user`,`str`) VALUES (".$lot_bd['productID'].",'1',".$_SESSION['id'].",'".$lot_bd['strItem']."')");
                            }else{
                                itemAdd($lot_bd['productID'],$lot_bd['count']);
                            }
                        }
                        $response['html'] = 'Лот успешно куплен!';
                        $response['info'] = 'success';
                        minus_item(1,$lot_bd['priceBuy']);
                        itemAdd(1,$lot_bd['priceBuy'],$lot_bd['userID']);
                        $mysqli->query("INSERT INTO `log_lombard_bidcounter` (`lotID`,`user`,`money`,`date`,`vikup`,`polz`) VALUES (".$lot.",".$_SESSION['id'].",'".$lot_bd['priceBuy']."','".$d."','".$lot_bd['name']."','".$lot_bd['userID']."')");
                        $mysqli->query("DELETE FROM `log_lombard` WHERE `id` = '".$lot."'  ");
                        
                        
                        $response['minus'] = "<img src='/img/world/items/little/1.png' class='item'> Генкар <b>x".number_format($lot_bd['priceBuy'],0,'.','.')."</b>";
                        
                        
                        
                    }else{
                        $response['html'] = 'Этот лот уже продан!';
                        $response['info'] = 'error';
                    }
            }else{
                $response['html'] = 'У вас недостаточно генкар!';
                $response['info'] = 'error';
            }
        }else{
            $response['html'] = 'Этот лот нельзя выкупить';
            $response['info'] = 'error';
        }
    }else{
        $response['html'] = 'Нельзя выкупить свой же лот!';
        $response['info'] = 'error';
    }
}

if(!empty($_POST['lot'])){
    $lot = escapeMe($_POST['lot']);
    $stavk = escapeMe($_POST['stav']);
    $lot_bd = $mysqli->query("SELECT * FROM `log_lombard` WHERE `id`= ".$lot)->fetch_assoc();
    $cer = $lot_bd['priceNow']+$lot_bd['priceStep'];
    $d = date("Y-m-d H:i:s");
    if($lot_bd['userID'] != $_SESSION['id']){
        if($stavk > 0 and $stavk >= $cer){
            if(item_isset(1,$cer)){
                if($lot_bd['userBuy'] != $_SESSION['id']){
                    if($lot_bd['dateEnd'] > time()){
                        //возврат прошлой ставки
                        $lot_bd_sc = $mysqli->query("SELECT * FROM `log_lombard_bidcounter` WHERE `lotID`= ".$lot." ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
                        if($lot_bd_sc){
                            itemAdd(1,$lot_bd_sc['money'],$lot_bd_sc['user']);
                            $month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
                        	$dayToday = date("d");
                        	$monthToday = $month[date("n")];
                        	$YearToday = date("Y");
                        	$date = $dayToday.' '.$monthToday.' '.$YearToday.'г. в '.date("H").':'.date("i");
                        	$text = 'Ваша ставка на лот #'.$lot.' была перебита!';
                        	$mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$text."','".$lot_bd_sc['user']."','/img/world/items/little/1.png','".$date."')");
                        }
                        //новая ставка
                        $response['html'] = 'Ваша ставка успешно зарегестрирована!';
                        $response['info'] = 'success';
                        $mysqli->query("UPDATE `log_lombard` SET `priceNow`=".$stavk.",`userBuy`= ".$_SESSION['id']." WHERE `id`='".$lot."'");
                        $mysqli->query("INSERT INTO `log_lombard_bidcounter` (`lotID`,`user`,`money`,`date`) VALUES (".$lot.",".$_SESSION['id'].",'".$stavk."','".$d."')");
                        minus_item(1,$stavk);
                        $response['minus'] = "<img src='/img/world/items/little/1.png' class='item'> генкар <b>x".number_format($stavk,0,'.','.')."</b>";
                        
                        
                        
                    }else{
                        $response['html'] = 'Ставки на этот лот уже не принимаются!';
                        $response['info'] = 'error';
                    }
                }else{
                    $response['html'] = 'Нельзя перебить свою же ставку!';
                    $response['info'] = 'error';
                }
            }else{
                $response['html'] = 'У вас недостаточно генкар!';
                $response['info'] = 'error';
            }
        }else{
            $response['html'] = 'Минимальная ставка '.number_format($cer,0,'.','.').' м.';
            $response['info'] = 'error';
        }
    }else{
        $response['html'] = 'Нельзя сделать ставку на свой же лот!';
        $response['info'] = 'error';
    }
}
if(!empty($_POST['boss'])){
    $npcBoss = $mysqli->query("SELECT * FROM `base_boss` WHERE `id` = ".$_POST['boss']." AND `user` = ".$_SESSION['id']." ")->fetch_assoc();
    $user = $mysqli->query("SELECT * FROM users WHERE id = ".$_SESSION['id'])->fetch_assoc();
    $my_pok = $mysqli->query("SELECT * FROM user_pokemons WHERE user_id = ".$_SESSION['id']." AND active = 1");
    if($user['status'] == 'free') {
        if($npcBoss){
            if($npcBoss['death'] == 0 and $npcBoss['time'] > time()){
                if($my_pok->num_rows <= 3){
                    $mysqli->query("UPDATE `users` SET `status`='battle' WHERE `id`='".$_SESSION['id']."'");
                    $location_id = $mysqli->query("SELECT `id`,`login`,`user_group`,`region`,`location`,`sex`,`ban`,`status`,`status_id`,`rating`,`rang`,`botID`,`sprite` FROM `users` WHERE `id`='".$_SESSION['id']."'")->fetch_assoc();
                    $lvl = 100;
                    Info::_generatePve($location_id, $location_id['location'],null,$npcBoss['id'],null,$lvl);
                    $response['text'] = "Бой начался!";
                    $response['error'] = "success";
                }else{
                    $response['text'] = "У вас должно быть 3 покемона в команде!";
                    $response['error'] = "error";
                }
            }else{
                $response['text'] = "Ошибка!";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "Системная ошибка!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Вы заняты!";
        $response['error'] = "error";
    }
}


if(!empty($_POST['evolution_lvl'])){
        $id = escapeMe($_POST['evolution_lvl']);
        $bd = $mysqli->query('SELECT * FROM user_pokemons WHERE id = '.$id)->fetch_assoc(); 
        $us = $mysqli->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc(); 
        if($us['status'] == "free"){
        if($bd['user_id'] == $_SESSION['id']){
            if(check_evol($id) == "check"){
                // if($bd['basenum'] == 44){
                //     $response['html'] = '<img class="'.check_evol($id,45).'" src="/img/pokemons/sprite/'.$bd['type'].'/045.gif"><img class="'.check_evol($id,182).'" src="/img/pokemons/sprite/'.$bd['type'].'/182.gif">';
                //     $response['er'] = 2;
                // }else{
                    $response['text'] = "Покемон успешно эволюционировал!";
                    evol_pok_lvl($id);
                    battlepass_exp(30,2);
                    week_mission('evolution');
                    $response['er'] = 1;
                // }
            }else{
                $response['text'] = "Этот покемон не может эволюционировать!";
                $response['er'] = 0;
            }
        
        }else{
            $response['text'] = "Это не ваш покемон!";
            $response['er'] = 0;
        }
        }else{
            $response['text'] = "Вы заняты!";
            $response['er'] = 0;
        }
    
}

if(!empty($_POST['giftFriend'])){
		$us = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_to` = '".$_SESSION['id']."' AND `active` = 0")->fetch_assoc();
		if($us){
		    $response['text'] = 1;
		}else{
		    $response['text'] = 0;
		}
}
if(!empty($_POST['GiveGift'])){
    $i = escapeMe($_POST['GiveGift']);
    $us = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_to` = '".$_SESSION['id']."' AND `active` = 0 AND `id` = ".$i)->fetch_assoc();
    if($us){
        $r = rand(1,120);
        if($r >= 1 and $r < 30){
            itemAdd(1,25000);
            $response['plus'] = "<img src='/img/world/items/little/1.png' class='item'> Генкар <b>x25.000</b>";
        }elseif($r >= 30 and $r < 35){
            itemAdd(62,1);
            $response['plus'] = "<img src='/img/world/items/little/62.png' class='item'> Даркбол <b>x1</b>";
        }elseif($r >= 35 and $r < 40){
            itemAdd(33,1);
            $response['plus'] = "<img src='/img/world/items/little/33.png' class='item'> Горькая конфета <b>x1</b>";
        }elseif($r >= 40 and $r < 55){
            itemAdd(26,1);
            $response['plus'] = "<img src='/img/world/items/little/26.png' class='item'> Желтая конфета <b>x1</b>";
        }elseif($r >= 55 and $r < 65){
            itemAdd(29,1);
            $response['plus'] = "<img src='/img/world/items/little/29.png' class='item'> Фиолетовая конфета <b>x1</b>";
        }elseif($r >= 65 and $r < 70){
            itemAdd(197,1);
            $response['plus'] = "<img src='/img/world/items/little/197.png' class='item'> Набор тренировки <b>x1</b>";
        }elseif($r >= 70 and $r < 80){
            itemAdd(245,1);
            $response['plus'] = "<img src='/img/world/items/little/245.png' class='item'> Кекс <b>x1</b>";
        }elseif($r >= 80 and $r < 84){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,48,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #048 Венонат <b>x1</b>";
        }elseif($r >= 84 and $r < 88){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,88,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #088 Граймер <b>x1</b>";
        }elseif($r >= 88 and $r < 92){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,102,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #102 Экзекут <b>x1</b>";
        }elseif($r >= 92 and $r < 96){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,339,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #339 Барбоч <b>x1</b>";
        }elseif($r >= 96 and $r < 100){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,353,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #353 Шупет <b>x1</b>";
        }elseif($r >= 100 and $r < 105){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,417,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #417 Пачирису <b>x1</b>";
        }elseif($r >= 105 and $r < 110){
            $countDay = mt_rand(7,10);
            $tm = time()+(3600*24*$countDay);
            $gens = "25,25,25,25,25,25";
            plusEgg($gens,false,false,true,$tm,734,false);
            $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо #734 Янгус <b>x1</b>";
        }else{
            itemAdd(1,25000);
            $response['plus'] = "<img src='/img/world/items/little/1.png' class='item'> Генкар <b>x25.000</b>";
        }
        $response['html'] = "Подарок получен!";
        if(check_mission_ivent(34)){ add_mission_ivent(34);}
		$response['error'] = "success";
		$mysqli->query("UPDATE `GiftFriend` SET `active` = '1' WHERE `id` = '".$i."'");
    }else{
        $response['html'] = "Подарок не найден!";
		$response['error'] = "error";
    }
}
if(!empty($_POST['captcha'])){
    $us = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
    if($us['captcha'] == $_POST['captcha']){
        $mysqli->query("UPDATE `users` SET `captcha` = '0' WHERE `id` = '".$_SESSION['id']."'");
        update_achiv(10,1);
        $response['text'] = "Успешно!";
        $response['error'] = "success";
    }else{
        $response['text'] = "Вы ввели не то число!";
        $response['error'] = "error";
    }
}
//Лечение покемонов в пц
if(!empty($_POST['recover'])){
    $sum = 0;
$us = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
$pokList = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `active` = 1 AND `user_id` = '".$_SESSION['id']."'");
while($poks = $pokList->fetch_assoc()){
$poksA = explode(',',$poks['attacks']);
$poksPP = explode(',',$poks['pp_attacks']);
$stats = explode(',',$poks['stats']);
if($poksA[0]) {	$pokList1 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[0]."")->fetch_assoc();	$pokList1 = $pokList1['pp'];	}else{$pokList1 = '0';}
if($poksA[1]) {	$pokList2 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[1]."")->fetch_assoc();	$pokList2 = $pokList2['pp'];	}else{$pokList2 = '0';}
if($poksA[2]) {	$pokList3 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[2]."")->fetch_assoc();	$pokList3 = $pokList3['pp'];	}else{$pokList3 = '0';}
if($poksA[3]) {	$pokList4 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[3]."")->fetch_assoc();	$pokList4 = $pokList4['pp'];	}else{$pokList4 = '0';}
if($poks['gym'] == 1){
    $sum = $sum+1;
}else{
    $sum = $sum+($pokList1-$poksPP[0])+($pokList2-$poksPP[1])+($pokList3-$poksPP[2])+($pokList4-$poksPP[3])+($stats[0]-$poks['hp']);
}
}
$bc = $mysqli->query("SELECT * FROM `base_location` WHERE `id` = '".$us['location']."'")->fetch_assoc();
if($bc['name'] == "Покецентр" or $bc['name'] == "Поле для тренировок" or $bc['name'] == "Стадион" or $bc['name'] == "Турнирная арена" or $bc['name'] == "Колизей"){
    if($us['status'] == "free"){
        if($sum > 0){
            if(item_isset(1,$sum)){
                $pokList = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `active` = 1 AND `user_id` = '".$_SESSION['id']."'");
            			while($poks = $pokList->fetch_assoc()){
            				$poksA = explode(',',$poks['attacks']);
            				$poksPP = explode(',',$poks['pp_attacks']);
            				$stats = explode(',',$poks['stats']);
            				if($poksA[0]) {	$pokList1 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[0]."")->fetch_assoc();	$pokList1 = $pokList1['pp'];	}else{$pokList1 = '0';}
            				if($poksA[1]) {	$pokList2 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[1]."")->fetch_assoc();	$pokList2 = $pokList2['pp'];	}else{$pokList2 = '0';}
            				if($poksA[2]) {	$pokList3 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[2]."")->fetch_assoc();	$pokList3 = $pokList3['pp'];	}else{$pokList3 = '0';}
            				if($poksA[3]) {	$pokList4 = $mysqli->query("SELECT `id`,`pp` FROM `base_atk` WHERE `id` = ".$poksA[3]."")->fetch_assoc();	$pokList4 = $pokList4['pp'];	}else{$pokList4 = '0';}
            				$update = $mysqli->query("UPDATE `user_pokemons` SET `hp` = '".$stats[0]."' WHERE `id` = '".$poks['id']."'");
            				$update1 = $mysqli->query("UPDATE `user_pokemons` SET `pp_attacks` = '".$pokList1.",".$pokList2.",".$pokList3.",".$pokList4.",' WHERE `id` = '".$poks['id']."'");
            			}
            			$response['html'] = 'Ваши покемоны вылечены!';
            			$response['minus'] = '<img src="img/world/items/little/1.png" class="item"> Генкар <b>x'.$sum.'</b>';
            			$response['error'] = "success";
            			minus_item(1,$sum);
            			update_achiv(28,$sum);
            			if(check_mission(6)){ add_mission(6,$sum);}
            }else{
                $response['html'] = "У вас недостаточно генкар!";
            $response['error'] = "error";
            }
        }else{
            $response['html'] = "Лечение не требуется!";
        $response['error'] = "info";
        }
    }else{

        $response['html'] = "Вы заняты!";
            $response['error'] = "error";
    }
}else{
    $response['html'] = "Лечение покемонов невозможно!";
        $response['error'] = "error";
}
}
//Шар с водой
if(!empty($_POST['water_ball'])){
    $id = escapeMe($_POST['water_ball']);
    $bd = $mysqli->query('SELECT * FROM `water_ball` WHERE `userto` = '.$_SESSION['id'].' AND `active` = 1')->fetch_assoc();
    if(!empty($bd['id'])){
        $response['html'] = "Протерто!";
                $response['error'] = "success";
                // itemAdd(1,5000);
                // $response['plus'] = "<img src='/img/world/items/little/1.png' class='item'> Монета <b>x5.000</b>";

    }else{
        $response['html'] = "Ошибка!";
        $response['error'] = "error";
    }
}

if(!empty($_POST['snow_ball'])){
    $id = escapeMe($_POST['snow_ball']);
    $bd = $mysqli->query('SELECT * FROM `snow_ball` WHERE `userto` = '.$_SESSION['id'].' AND `active` = 1')->fetch_assoc();
    if(!empty($bd['id'])){
        if(rand(1,100) < 5){
            $us2 = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'].' ')->fetch_assoc();

    $st2 = explode(',',$us2['snow_ball']);
    $st2[2] = $st2[2]+1;
    $stUpd2 = implode(',',$st2);
    $mysqli->query("UPDATE `users` SET `snow_ball` = '".$stUpd2."' WHERE  `id` = '".$_SESSION['id']."'");
        $response['html'] = "Вы нашли счастливый снежок!";
                $response['error'] = "success";
                itemAdd(31,1);
                $response['plus'] = "<img src='/img/world/items/little/31.png' class='item'> Красная конфета <b>x1</b>";
        }
    }else{
        $response['html'] = "Ошибка!";
        $response['error'] = "error";
    }
}
//Бросок шара с водой
if(!empty($_POST['water_ball_drop'])){
    $id = escapeMe($_POST['water_ball_drop']);


    $us2 = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$id.' ')->fetch_assoc();

    $st2 = explode(',',$us2['water_ball']);
    $st2[1] = $st2[1]+1;
    $stUpd2 = implode(',',$st2);
    $mysqli->query("UPDATE `users` SET `water_ball` = '".$stUpd2."' WHERE  `id` = '".$id."'");
    if(item_isset(471,1)){
      $r = rand(1,100);
      if($r >= 60){
        $us1 = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'].' ')->fetch_assoc();
        $st1 = explode(',',$us1['water_ball']);
    $st1[0] = $st1[0]+1;
    $add = $st1[0];
    $stUpd1 = implode(',',$st1);
        $mysqli->query("UPDATE `users` SET `water_ball` = '".$stUpd1."' WHERE  `id` = '".$_SESSION['id']."'");
        $mysqli->query("INSERT INTO `water_ball` (`user`,`userto`,`active`) VALUES (".$_SESSION['id'].",'".$id."',0)");
        $response['html'] = "Шарик успешно брошен! Вы уже бросили ".$add." шаров.";
                $response['error'] = "success";
      }else{
        $response['html'] = "Шарик лопнул, так и не достигнув цели.";
                $response['error'] = "warning";
      }
                minus_item(471,1);
                    $response['minus'] = "<img src='/img/world/items/little/471.png' class='item'> Шарик с водой <b>x1</b>";
    }else{
        $response['html'] = "У вас нету шарика!";
                $response['error'] = "error";
    }

}
if(!empty($_POST['snow_ball_drop'])){
    $id = escapeMe($_POST['snow_ball_drop']);


    $us2 = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'].' ')->fetch_assoc();

    $st2 = explode(',',$us2['snow_ball']);
    $st2[1] = $st2[1]+1;
    $stUpd2 = implode(',',$st2);
    $mysqli->query("UPDATE `users` SET `snow_ball` = '".$stUpd2."' WHERE  `id` = '".$_SESSION['id']."'");
    if(item_isset(433,1)){
        if($id != 4 and $id != 100){
      $r = rand(1,100);
      if($r >= 40){
        $us1 = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'].' ')->fetch_assoc();
        $st1 = explode(',',$us1['snow_ball']);
    $st1[0] = $st1[0]+1;
    $add = $st1[0];
    $stUpd1 = implode(',',$st1);
        $mysqli->query("UPDATE `users` SET `snow_ball` = '".$stUpd1."' WHERE  `id` = '".$_SESSION['id']."'");
        $mysqli->query("INSERT INTO `snow_ball` (`user`,`userto`,`active`) VALUES (".$_SESSION['id'].",'".$id."',0)");
        $response['html'] = "Снежок успешно достиг своей цели! Вы уже попали ".$add." снежками.";
                $response['error'] = "success";
                
        if(rand(1,100) < 10){
            $r = rand(199,204);
            itemAdd($r,1);
                $response['plus'] = "<img src='/img/world/items/little/$r.png' class='item'> Банка с витаминами <b>x1</b>";
        }        
                
      }else{
        $response['html'] = "Снежок полетел мимо цели";
                $response['error'] = "warning";
      }
                minus_item(433,1);
                    $response['minus'] = "<img src='/img/world/items/little/433.png' class='item'> Снежок <b>x1</b>";
    
        }else{
        $response['html'] = "Тренер защищен от снежков!";
                $response['error'] = "error";
    }
        }else{
        $response['html'] = "У вас нету снежка!";
                $response['error'] = "error";
    }

}
//Реферальная система
if(!empty($_POST['referal'])){
    $id = escapeMe($_POST['referal']);
    $level = !empty($_POST['level']) ? intval($_POST['level']) : null;

    $bd = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
    $bd_ref = $mysqli->query('SELECT * FROM `users` WHERE `referal_you` = "'.$bd['referal'].'" AND `id` = '.$id)->fetch_assoc();
    if(isset($bd_ref)){
        // Проверяем, есть ли уже награды по этому рефералу
        $vc = $mysqli->query('SELECT * FROM `base_referal` WHERE `user` = '.$_SESSION['id'].' AND `referal` = "'.$bd_ref['id'].'" ')->fetch_assoc();
        $awarded_levels = [];
        if(!empty($vc) && !empty($vc['levels'])) {
            $awarded_levels = explode(',', $vc['levels']);
        }

        // Массив наград по уровням
        $rewards = [
            10 => [
                'action' => function() { itemAdd(1, 300000); },
                'desc' => "<img src='/img/world/items/little/1.png' class='item'> Генкары <b>x300.000</b>"
            ],
            15 => [
                'action' => function() { itemAdd(448, 1); }, // функция, которая даст премиум на 2 дня
                'desc' => "<img src='/img/world/items/little/448.png' class='item'> Премиум <b>2 дня</b>"
            ],
            20 => [
                'action' => function() { itemAdd(25, 50); }, // 25 — ID Аметиста, 50 шт
                'desc' => "<img src='/img/world/items/little/25.png' class='item'> Аметист <b>x50</b>"
            ],
        ];

        $rewarded = false;
        $reward_msgs = [];

        // Если передан конкретный level, выдаём только награду за этот level (и только если доступна)
        if ($level !== null && isset($rewards[$level])) {
            if ($bd_ref['lvl'] >= $level && !in_array($level, $awarded_levels)) {
                $rewards[$level]['action']();
                $reward_msgs[] = $rewards[$level]['desc'];
                $awarded_levels[] = $level;
                $rewarded = true;
            }
        } else {
            // Старое поведение: можно получить несколько сразу (если не передан level)
            foreach ($rewards as $lvl => $reward) {
                if ($bd_ref['lvl'] >= $lvl && !in_array($lvl, $awarded_levels)) {
                    // Выдаём награду
                    $reward['action']();
                    $reward_msgs[] = $reward['desc'];
                    $awarded_levels[] = $lvl;
                    $rewarded = true;
                }
            }
        }

        if ($rewarded) {
            // Обновляем таблицу base_referal с новыми уровнями
            if (empty($vc)) {
                $mysqli->query("INSERT INTO `base_referal` (`user`,`referal`,`levels`) VALUES (".$_SESSION['id'].",'".$bd_ref['id']."','".implode(',', $awarded_levels)."')");
            } else {
                $mysqli->query("UPDATE `base_referal` SET `levels`='".implode(',', $awarded_levels)."' WHERE `id`=".$vc['id']);
            }
            $response['plus'] = implode("<br>", $reward_msgs);
            $response['html'] = "Награда получена!";
            $response['error'] = "success";
            update_achiv(17,1);
        } else {
            if ($level !== null) {
                if (in_array($level, $awarded_levels)) {
                    $response['html'] = "Эта награда уже получена!";
                } else {
                    $response['html'] = "Недостаточный уровень для награды (требуется: $level, у реферала: ".$bd_ref['lvl'].")";
                }
            } else {
                $response['html'] = "Нет новых наград. Уровень реферала: ".$bd_ref['lvl'];
            }
            $response['error'] = "error";
        }
    } else {
        $response['html'] = "Ошибка! Реферал не найден.";
        $response['error'] = "error";
    }
}
//Умения
if(!empty($_POST['utility'])){
    $id = $_POST['utility'];
    $bd = $mysqli->query('SELECT * FROM `user_achievements_utility` WHERE `utility` = '.$id.' AND `user` = '.$_SESSION['id'])->fetch_assoc();
    $bd_ach = $mysqli->query('SELECT * FROM `base_achievements` WHERE `prize` != "" AND `id` = '.$id)->fetch_assoc();
    $bd_ach_compl = $mysqli->query('SELECT * FROM `user_achievements` WHERE `id_ach` = '.$id.' AND `user_id` = '.$_SESSION['id'])->fetch_assoc();
    if($bd_ach){
        if($bd_ach_compl['complete'] == 1){
            if($bd){
                $mysqli->query("DELETE FROM `user_achievements_utility` WHERE `utility` = '".$id."' AND `user` = '".$_SESSION['id']."' ");
            }else{
                $mysqli->query("INSERT INTO `user_achievements_utility` (`user`,`utility`) VALUES (".$_SESSION['id'].",'".$id."')");
            }
            $response['text'] = "Успешно!";
            $response['error'] = "success";
        }else{
            $response['text'] = "Вы не получили достижение!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Умение не найдено!";
        $response['error'] = "error";
    }
}
//Починка предмета
if(!empty($_POST['repair'])){
    if(!empty($_POST['type'])){
        if($_POST['type'] == "check"){
            $us = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$_POST['id']." AND `user` = ".$_SESSION['id'])->fetch_assoc();
            if(!empty($us)){
                $response['error'] = "success";
                $response['html'] = "Предмет выбран!";
                if($us['str']){
                    $str = explode(',',$us['str']);
                    $t = $str[0].'/'.$str[1];
                }elseif($us['dop']){
                    $t = $us['dop'].'%';
                }
                $response['text'] = "<div>".$t."</div><img src='/img/world/items/little/".$us['item_id'].".png'>";
            }else{
                $response['error'] = "error";
                $response['html'] = "Ошибка!";
            }
        }elseif($_POST['type'] == "repair"){
            $item = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$_POST['item']." AND `user` = ".$_SESSION['id'])->fetch_assoc();
            $potion = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$_POST['potion']." AND `user` = ".$_SESSION['id'])->fetch_assoc();
            if($item and $potion){
                $dr = $potion['dop']/5;//Количество возможных прочностей
                $str = explode(',',$item['str']);
                $t = $str[1]-$str[0];//Колчиество недостающих прочностей
                if($t >= $dr){
                    $s = $str[0]+$dr;
                    $str_upd = $s.','.$str[1];
                    $mysqli->query("UPDATE `items_users` SET `str` = '".$str_upd."' WHERE  `id` = '".$item['id']."'");
                    $mysqli->query("DELETE FROM `items_users` WHERE `id` = '".$potion['id']."' AND `user` = '".$_SESSION['id']."' ");
                }elseif($t < $dr){
                    $s = $str[0]+$t;
                    $str_upd = $s.','.$str[1];
                    $str_dop = ($dr-$t)*5;
                    $mysqli->query("UPDATE `items_users` SET `str` = '".$str_upd."' WHERE  `id` = '".$item['id']."'");
                    $mysqli->query("UPDATE `items_users` SET `dop` = '".$str_dop."' WHERE  `id` = '".$potion['id']."'");
                }
                $response['error'] = "success";
                $response['html'] = "Предмет починен!";
            }else{
                $response['error'] = "error";
                $response['html'] = "Ошибка!";
            }
        }
    }
}
//Приз календаря
if(!empty($_POST['calendar'])){
    $id = $_POST['calendar'];
    $date = date('j');
    $us = $mysqli->query("SELECT * FROM `users_calendar_day` WHERE `user` = ".$_SESSION['id'])->fetch_assoc();
    $syst = $mysqli->query("SELECT * FROM `system` WHERE `id` = 1")->fetch_assoc();
    $day = explode(',',$us['day']);
    $mout = explode(',',$syst['calendar']);
    if(!in_array($id,$day)){
        if($id == $date){
            if($mout[0] == date("n")){
            $bds = $mysqli->query("SELECT * FROM `base_calendar_day` WHERE `id` = ".$id)->fetch_assoc();
            if($bds['type'] == 1){
                $it = explode(',',$bds['item']);
                $bds_item = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$it[0])->fetch_assoc();
                $response['plus'] = '<img src="/img/world/items/little/'.$it[0].'.png" class="item"> '.$bds_item['name'].' <b>x'.number_format($it[1],0,'.','.').'</b>';
                itemAdd($it[0],$it[1]);
            }else{
                $bds_pok = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$bds['pok']."' ")->fetch_assoc();
                $response['plus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо '.$bds_pok['name_rus'].' <b>x1</b>';
                $countDay = mt_rand(7,10);
                $tm = time()+(3600*24*$countDay);
                $gens = "25,25,25,25,25,25";
                $basenum = $bds['pok'];
                plusEgg($gens,false,false,true,$tm,$basenum,false);
            }
            if($us){
                $upd = $us['day'].','.$id;
                $mysqli->query("UPDATE `users_calendar_day` SET `day` = '".$upd."' WHERE  `user` = '".$_SESSION['id']."'");
            }else{
                $mysqli->query("INSERT INTO `users_calendar_day` (`user`,`day`) VALUES (".$_SESSION['id'].",'".$id."')");
            }
            $response['text'] = "Ежедневная награда отмечена!";
        $response['error'] = "success";
            }else{
            $response['text'] = "Ошибка! Этот день из прошлого месяца!";
        $response['error'] = "error";
        }
        }else{
            $response['text'] = "Этот день еще не наступил или уже прошел!";
        $response['error'] = "error";
        }
    }else{
        $response['text'] = "Вы уже получили эту награду!";
        $response['error'] = "error";
    }
}
//Получение подарка на др
if(!empty($_POST['Birthday'])){
    $us = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id'])->fetch_assoc();
    if($us['birthday'] == 1){
        itemAdd(1,500000);
        itemAdd(246,5);
        itemAdd(197,5);
        itemAdd(31,5);
        itemAdd(62,5);
        $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкар <b>x500.000</b><br>
        <img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x5</b><br>
        <img src="/img/world/items/little/197.png" class="item"> Набор тренировки <b>x5</b><br>
        <img src="/img/world/items/little/31.png" class="item"> Красная конфета <b>x5</b><br>
        <img src="/img/world/items/little/62.png" class="item"> Даркбол <b>x5</b><br>';
        $mysqli->query("UPDATE `users` SET `birthday` = '0' WHERE  `id` = '".$_SESSION['id']."'");
        $response['error'] = "success";
        $response['text'] = "Вы успешно забрали подарок!";
    }else{
        $response['error'] = "error";
        $response['text'] = "Ошибка!";
    }
}
//Получение награды за онлайн
if(!empty($_POST['GiftOnline'])){

    $iv = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id'])->fetch_assoc();
    if($iv){
        if($iv['gift'] == 1){

    $t = time()+rand(7000,10000);
    $rand = rand(1,125);
    if($rand >= 1 and $rand < 10){
        $response['plus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкар <b>x25.000</b>';
        itemAdd(1,25000);
    }elseif($rand >= 10 and $rand < 20){
        $response['plus'] = '<img src="/img/world/items/little/181.png" class="item"> Малый усилитель ловли <b>x1</b>';
        itemAdd(181,1);
    }elseif($rand >= 20 and $rand < 30){
        $response['plus'] = '<img src="/img/world/items/little/183.png" class="item"> Малый усилитель генкар <b>x1</b>';
        itemAdd(183,1);
    }elseif($rand >= 30 and $rand < 40){
        $response['plus'] = '<img src="/img/world/items/little/56.png" class="item"> Мастербол <b>x1</b>';
        itemAdd(56,1);
    }elseif($rand >= 40 and $rand < 50){
        $response['plus'] = '<img src="/img/world/items/little/62.png" class="item"> Даркбол <b>x1</b>';
        itemAdd(62,1);
    }elseif($rand >= 50 and $rand < 60){
        $response['plus'] = '<img src="/img/world/items/little/16.png" class="item"> Мощный эликсир <b>x1</b>';
        itemAdd(16,1);
    }elseif($rand >= 60 and $rand < 70){
        $response['plus'] = '<img src="/img/world/items/little/26.png" class="item"> Желтая конфета <b>x5</b>';
        itemAdd(26,5);
    }elseif($rand >= 70 and $rand < 80){
        $response['plus'] = '<img src="/img/world/items/little/27.png" class="item"> Голубая конфета <b>x5</b>';
        itemAdd(27,5);
    }elseif($rand >= 80 and $rand < 90){
        $response['plus'] = '<img src="/img/world/items/little/28.png" class="item"> Зеленая конфета <b>x5</b>';
        itemAdd(28,5);
    }elseif($rand >= 90 and $rand < 95){
        $response['plus'] = '<img src="/img/world/items/little/169.png" class="item"> Светящийся клей <b>x1</b>';
        itemAdd(169,1);
    }elseif($rand >= 95 and $rand < 100){
        $response['plus'] = '<img src="/img/world/items/little/171.png" class="item"> Поглощаяющая лампа <b>x1</b>';
        itemAdd(171,1);
    }elseif($rand >= 100 and $rand < 105){
        $response['plus'] = '<img src="/img/world/items/little/173.png" class="item"> Черная грязь <b>x1</b>';
        itemAdd(173);
    }elseif($rand >= 105 and $rand < 115){
        $response['plus'] = '<img src="/img/world/items/little/245.png" class="item"> Кекс <b>x1</b>';
        itemAdd(245,1);
    }elseif($rand >= 115 and $rand < 120){
        $response['plus'] = '<img src="/img/world/items/little/246.png" class="item"> Сладкий кекс <b>x1</b>';
        itemAdd(246,1);
    }else{
        $response['plus'] = '<img src="/img/world/items/little/151.png" class="item"> Яйцо покемона <b>x1</b>';
        $countDay = mt_rand(7,10);
        $tm = time()+(3600*24*$countDay);
        $gens = "25,25,25,25,25,25";
        $input = array(406,54,194,109,590,48,193,118,831,370,304,273,415,505,736,300,179,220,527,77);
        $rand_keys = array_rand($input, 2);
        $basenum = $input[$rand_keys[1]];
        plusEgg($gens,false,false,true,$tm,$basenum,false);
        $t = time()+rand(10000,13000);
    }
    $us = $mysqli->query('SELECT * FROM `bafs` WHERE `user` = '.$_SESSION['id'].' AND `baf` = 448')->fetch_assoc();
    if($us['time'] >= time()){
        $t = $t/2;
    }else{
        $t = $t;
    }
    $mysqli->query("UPDATE `users` SET `gift` = '0', `gift_online` = ".$t." WHERE  `id` = '".$_SESSION['id']."'");
        }
    }
}
//Сравнивание покемонов в покедексе
if(!empty($_POST['comparison'])){
    $id = escapeMe($_POST['comparison']);
    $us = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id'])->fetch_assoc();
    if($id == $us['comparison']){
        $response['text'] = "Вы больше не сравниваете покемонов!";
        $mysqli->query("UPDATE `users` SET `comparison` = '0'  WHERE  `id` = '".$_SESSION['id']."'");
    }else{
        $response['text'] = "Теперь вы сравниваете этого покемона!";
        $mysqli->query("UPDATE `users` SET `comparison` = '".$id."'  WHERE  `id` = '".$_SESSION['id']."'");
    }
}
//Выдача команды покемону
if(!empty($_POST['selectTeam'])){
    $id = escapeMe($_POST['selectTeam']);
    $idpok = escapeMe($_POST['idpok']);

    $qwerty = $mysqli->query("SELECT * FROM `user_pok_team` WHERE `id` = ".$id)->fetch_assoc();
    $qwerty2 = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = ".$idpok)->fetch_assoc();
    if($qwerty2['user_id'] ==  $_SESSION['id']){
        if($qwerty['user'] == $_SESSION['id']){
            $response['text'] = "Ваш покемон теперь в команде ".$qwerty['name']."!";
            $mysqli->query("UPDATE `user_pokemons` SET `team_id` = '".$id."'  WHERE  `id` = '".$idpok."'");
        }else{
            $response['text'] = "Это не ваша команда!";
        }
    }else{
        $response['text'] = "Это не ваш покемон!";
    }
}
//Удаление команды у покемона
if(!empty($_POST['delTeam'])){
    $id = escapeMe($_POST['delTeam']);
    $qwerty = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = ".$id)->fetch_assoc();
    if($qwerty['user_id'] == $_SESSION['id']){
        $response['text'] = "Покемон больше не состоит в команде!";
        $mysqli->query("UPDATE `user_pokemons` SET `team_id` = '0'  WHERE  `id` = '".$id."'");
    }else{
        $response['text'] = "Это не ваш покемон!";
    }
}
//Создание команды
if(!empty($_POST['addTeam'])){
    $id = escapeMe($_POST['addTeam']);
    $name = $mysqli->real_escape_string($_POST['name']);
		$name = htmlspecialchars($name);
		$name = trim($name);
		$name = addslashes($name);
    $qwerty = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = ".$id)->fetch_assoc();
    if($name != ''){
    if($qwerty['user_id'] == $_SESSION['id']){
        $response['text'] = "Команда успешно создана!";
        $mysqli->query("INSERT INTO `user_pok_team` (`user`,`name`) VALUES (".$_SESSION['id'].",'".$name."')");
        $ID = $mysqli->insert_id;
        $mysqli->query("UPDATE `user_pokemons` SET `team_id` = '".$ID."'  WHERE  `id` = '".$id."'");
    }else{
        $response['text'] = "Это не ваш покемон!";
    }
  }else{
    $response['text'] = "Введите корректное название!";
  }
}



//Удаление команды у тренера
if(!empty($_POST['delTeamUser'])){
    $id = escapeMe($_POST['delTeamUser']);
    $qwerty = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `team_id` = ".$id);
    $qwerty_team = $mysqli->query("SELECT * FROM `user_pok_team` WHERE `id` = ".$id)->fetch_assoc();
    if($qwerty_team['user'] == $_SESSION['id']){
      while($gi = $qwerty->fetch_assoc()){
        $mysqli->query("UPDATE `user_pokemons` SET `team_id` = '0'  WHERE  `id` = '".$gi['id']."'");
      }
        $response['text'] = "Команда успешно удалена!";
        $mysqli->query('DELETE FROM user_pok_team WHERE id = '.$id);
    }else{
        $response['text'] = "Это не ваша команда!";
    }
}

//Поиск покемона по имени в покедексе
if(!empty($_POST['id']) and $_POST['id'] == "pokedex"){
    if($_POST['type'] == 'search'){
    $search = $mysqli->real_escape_string($_POST['pok']);
	$search = clearStr($search);
    $gift8 = $mysqli->query("SELECT `id`,`name_rus` FROM `base_pokemons` WHERE `name_rus` LIKE '%".$search."%' And `form` = 'Обычный'"); // Подарки на 8 марта

		while($gift = $gift8->fetch_assoc()){
			$gift8List[$gift['id']] = [
				'num' => $gift['id'],
          'basenum' => numbPok($gift['id']),
          'name' => $gift['name_rus']
			];
		}
		$response = array(
			'pok_list' => $gift8List
		);
    }
    if($_POST['type'] == "form"){
        $gift8 = $mysqli->query("SELECT * FROM `base_pokemon_forms_new` WHERE `pokemons` = ".$_POST['pok']);
        $a = "<div class='Head'>Формы покемона</div>";
        while($g = $gift8->fetch_assoc()){
            if($g['start'] == 1){
                $a .= '<div class="listForm" onclick="openDex('.$g['pokemons'].');"><i class="fal fa-scarecrow"></i> '.$g['name'].'</div>';
            }else{
                $a .= '<div class="listForm" onclick="openDex('.$g['pokemons'].',\''.$g['name'].'\');"><i class="fal fa-scarecrow"></i> '.$g['name'].'</div>';
            }

        }
        $response['html'] = $a;
    }
}
//Информация о покемоне в питомнике
if(!empty($_POST['nursery'])){
    $id = escapeMe($_POST['nursery']);
    $pokemon = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = ".$_SESSION['id']." AND `id` = ".$id)->fetch_assoc();
    if($pokemon){
        stat_updates($pokemon['id']);
      $stats = explode(',',$pokemon['stats']);
			$gen = explode(',',$pokemon['gen']);
      if($pokemon['type'] == 'shine'){
        $textUnik = '<div class="Unik shine-color">shine</div>';
				$typeSprite = 'shine';
			}else{
        $typeSprite = 'normal';
        $textUnik = '';
      }
      if($pokemon['form'] != "0"){
        $form = "_".$pokemon['form'];
      }else{
        $form = "";
      }
			$sprite = '<div class="Image"><img onclick="openDex('.$pokemon['basenum'].')" src="/img/pokemons/sprite/'.$pokemon['type'].'/'.numbPok($pokemon['basenum']).$form.'.gif"></div>';
			$fullBasenum = numbPok($pokemon['basenum']);
			$type = $pokemon['type'] != 'normal' ? '<i>'.$pokemon['type'].'</i>' : '';

      $p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.(int)$pokemon['basenum'].'" ')->fetch_object();

      $explvllow = Info::_getExp($pokemon['lvl']-1, $p->exp_group);
      $explvl = $pokemon['exp']-$explvllow;
      $explvl2 = $pokemon['exp_max']-$explvllow;


			$exp = (int)$pokemon['lvl'] != 100 ? $explvl.' / '.$explvl2 : '';
			$paired = $pokemon['sparka'] == 1 ? 'spar' : '';
      if($pokemon['gender'] == 'Девочка') {
        $gender = 'venus';
      }elseif($pokemon['gender'] == 'Мальчик') {
        $gender = 'mars';
      }else{
        $gender = 'genderless';
      }
			$gen = 'h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5];
      $str = explode(',',$pokemon['item_str']);
      if(!empty($pokemon['item_str'])) {
        $str = explode(',',$pokemon['item_str']);
        $str = $str[0].'/'.$str[1];
      }else{
        $str = '';
      }
			$item = $pokemon['item_id'] != 0 ? '<div style="background-image: url(/img/world/items/little/'.$pokemon['item_id'].'.png);" class="Item" onclick=itemAction('.$pokemon['item_id'].',\'remove\',false,false,'.$pokemon['id'].');' .'><div class="str">'.$str.'</div></div>' : '';
			$tren = $pokemon['tren'] != 0 ? '<div class="Tren" id="TrenPokemon'. $pokemon['id'] .'" style="background-image: url(/img/tren/'.$pokemon['tren'].'.png);"></div>' : '';
      $fHp = (($pokemon['hp'] / $stats[0]) * 100);
      if((int)$pokemon['lvl'] == 100) {
        $fExp = 100;
      }else{
        $fExp = ((mb_strimwidth($explvl, 0, 5, "..") / mb_strimwidth($explvl2, 0, 5, "..")) * 100);
      }
      $fHappy = (($pokemon['happy'] / 255) * 100);
			$birthdayJson = json_decode($pokemon['birthday']);
			$up = json_decode($pokemon['modification']);
   $attack_lang = $_SESSION['attack_lang'] ?? 'rus';
$atk_name_col = ($attack_lang === 'eng') ? 'name' : 'name_rus';

$attacks = explode(',', $pokemon['attacks']);
$attack_pp = explode(',', $pokemon['pp_attacks']);

// Первая атака
if ($attacks[0] > 0) {
    $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[0]."'")->fetch_assoc();
    $atkOne = $atkQuery[$atk_name_col];
    $ppOne = $atkQuery['pp'];
    $typeOne = $atkQuery['type'];
    if ($atkQuery['category'] === 'physical') {
        $category1 = 1;
    } elseif ($atkQuery['category'] === 'special') {
        $category1 = 2;
    } else {
        $category1 = 3;
    }
    $d1 = 'onclick="viewDescriptionAttak(this,'.$attacks[0].');"';
} else {
    $atkOne = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
    $attacks[0] = 0;
    $d1 = '';
}

// Вторая атака
if ($attacks[1] > 0) {
    $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[1]."'")->fetch_assoc();
    $atkTwo = $atkQuery[$atk_name_col];
    $ppTwo = $atkQuery['pp'];
    $typeTwo = $atkQuery['type'];
    if ($atkQuery['category'] === 'physical') {
        $category2 = 1;
    } elseif ($atkQuery['category'] === 'special') {
        $category2 = 2;
    } else {
        $category2 = 3;
    }
    $d2 = 'onclick="viewDescriptionAttak(this,'.$attacks[1].');"';
} else {
    $atkTwo = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
    $attacks[1] = 0;
    $d2 = '';
}

// Третья атака
if ($attacks[2] > 0) {
    $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[2]."'")->fetch_assoc();
    $atkThree = $atkQuery[$atk_name_col];
    $ppThree = $atkQuery['pp'];
    $typeThree = $atkQuery['type'];
    if ($atkQuery['category'] === 'physical') {
        $category3 = 1;
    } elseif ($atkQuery['category'] === 'special') {
        $category3 = 2;
    } else {
        $category3 = 3;
    }
    $d3 = 'onclick="viewDescriptionAttak(this,'.$attacks[2].');"';
} else {
    $atkThree = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
    $attacks[2] = 0;
    $d3 = '';
}

// Четвертая атака
if ($attacks[3] > 0) {
    $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[3]."'")->fetch_assoc();
    $atkFour = $atkQuery[$atk_name_col];
    $ppFour = $atkQuery['pp'];
    $typeFour = $atkQuery['type'];
    if ($atkQuery['category'] === 'physical') {
        $category4 = 1;
    } elseif ($atkQuery['category'] === 'special') {
        $category4 = 2;
    } else {
        $category4 = 3;
    }
    $d4 = 'onclick="viewDescriptionAttak(this,'.$attacks[3].');"';
} else {
    $atkFour = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
    $attacks[3] = 0;
    $d4 = '';

  		}
      if($pokemon['trade'] == 'false') {
        $trd = 'tradeNo';
        $clos = '<i class="fas fa-lock"></i>';
      }else{
        $trd = 'tradeYes';
        $clos = '';
      }
      if($pokemon['sparka'] == 1) {
        $spr = 'Недоступно';
      }else{
        $spr = 'Доступно';
      }


      $tr_b = 'angle-double-up';
          if($pokemon['tren'] == 1) { $tr_n = 'tr1'; }
      elseif($pokemon['tren'] == 2) { $tr_n = 'tr2'; }
      elseif($pokemon['tren'] == 3) { $tr_n = 'tr3'; }
      elseif($pokemon['tren'] == 4) { $tr_n = 'tr4'; }
      elseif($pokemon['tren'] == 5) { $tr_n = 'tr5'; }
      elseif($pokemon['tren'] == 6) { $tr_n = 'tr6'; $tr_b = 'crown'; }

          if($pokemon['tren_stat'] == 1) { $tr1 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>'; }
      elseif($pokemon['tren_stat'] == 2) { $tr2 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}
      elseif($pokemon['tren_stat'] == 3) { $tr3 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}
      elseif($pokemon['tren_stat'] == 4) { $tr4 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}
      elseif($pokemon['tren_stat'] == 5) { $tr5 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}

      if($pokemon['tren']){
        $b = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';
      }else{
        $b = '';
      }
      $evcounts = explode(',',$pokemon['evcounts']);
      $userPokemon = $mysqli->query("SELECT `id`,`login`,`user_group`,`sex` FROM `users` WHERE `id` = '".$birthdayJson->user_id."'")->fetch_assoc();
      $dop = explode(',',$pokemon['stat_pl_mn']);
      if($dop[0] > 0){ $dop_hp = '<span class="Green-Color">+'.$dop[0].'</span>';}elseif($dop[0] < 0){ $dop_hp = '<span class="Red-Color">'.$dop[0].'</span>';}else{$dop_hp = '';}
      if($dop[1] > 0){ $dop_atk = '<span class="Green-Color">+'.$dop[1].'</span>';}elseif($dop[1] < 0){ $dop_atk = '<span class="Red-Color">'.$dop[1].'</span>';}else{$dop_atk = '';}
      if($dop[2] > 0){ $dop_def = '<span class="Green-Color">+'.$dop[2].'</span>';}elseif($dop[2] < 0){ $dop_def = '<span class="Red-Color">'.$dop[2].'</span>';}else{$dop_def = '';}
      if($dop[3] > 0){ $dop_spd = '<span class="Green-Color">+'.$dop[3].'</span>';}elseif($dop[3] < 0){ $dop_spd = '<span class="Red-Color">'.$dop[3].'</span>';}else{$dop_spd = '';}
      if($dop[4] > 0){ $dop_satk = '<span class="Green-Color">+'.$dop[4].'</span>';}elseif($dop[4] < 0){ $dop_satk = '<span class="Red-Color">'.$dop[4].'</span>';}else{$dop_satk = '';}
      if($dop[5] > 0){ $dop_sdef = '<span class="Green-Color">+'.$dop[5].'</span>';}elseif($dop[5] < 0){ $dop_sdef = '<span class="Red-Color">'.$dop[5].'</span>';}else{$dop_sdef = '';}
      if($pokemon['sparka'] == 1){
        $sex = 'Red-Color';
      }else{
        $sex = 'Green-Color';
      }

      $haras = $mysqli->query("SELECT * FROM `har` WHERE `id_har` = '".$pokemon['character']."' ")->fetch_assoc();
      if($haras['atk'] == 1.1){$har1 = "Green-Color";}elseif($haras['atk'] == 0.9){$har1 = "Red-Color";}else{ $har1 = "";}
      if($haras['def'] == 1.1){$har2 = "Green-Color";}elseif($haras['def'] == 0.9){$har2 = "Red-Color";}else{ $har2 = "";}
      if($haras['speed'] == 1.1){$har3 = "Green-Color";}elseif($haras['speed'] == 0.9){$har3 = "Red-Color";}else{ $har3 = "";}
      if($haras['satk'] == 1.1){$har4 = "Green-Color";}elseif($haras['satk'] == 0.9){$har4 = "Red-Color";}else{ $har4 = "";}
      if($haras['sdef'] == 1.1){$har5 = "Green-Color";}elseif($haras['sdef'] == 0.9){$har5 = "Red-Color";}else{ $har5 = "";}


      $st = explode(',',$pokemon['static']);


      if($pokemon['team_id'] != 0){
          $bd_team = $mysqli->query("SELECT * FROM `user_pok_team` WHERE `id` = '".$pokemon['team_id']."' ")->fetch_assoc();
          $team = $bd_team['name'];
      }else{
          $team = "";
      }

      if($pokemon['ability'] != 0){
          $abl = '<div onclick="viewDescriptionAbility('.$pokemon['ability'].');" class="AbilityPok">'.ability_name($pokemon['ability']).'</div>';
      }else{
          $abl = 'Отсутствует';
      }
      
      $hpgen[0] = ((intval($gen[0]) % 2) == 0 ? 0 : 1); // HP
$hpgen[1] = ((intval($gen[1]) % 2) == 0 ? 0 : 1); // Atk
$hpgen[2] = ((intval($gen[2]) % 2) == 0 ? 0 : 1); // Def
$hpgen[3] = ((intval($gen[5]) % 2) == 0 ? 0 : 1); // Spe (Speed is gen[5]!)
$hpgen[4] = ((intval($gen[3]) % 2) == 0 ? 0 : 1); // SpA (Sp.Atk is gen[3]!)
$hpgen[5] = ((intval($gen[4]) % 2) == 0 ? 0 : 1); // SpD (Sp.Def is gen[4]!)

$hptype = floor((($hpgen[0] + 2 * $hpgen[1] + 4 * $hpgen[2] + 8 * $hpgen[3] + 16 * $hpgen[4] + 32 * $hpgen[5]) * 15) / 63);

$hptypenumrus = [
  'Боевой',        // 0
  'Летающий',      // 1
  'Ядовитый',      // 2
  'Земляной',      // 3
  'Каменный',      // 4
  'Насекомое',     // 5
  'Призрачный',    // 6
  'Стальной',      // 7
  'Огненный',      // 8 🔥
  'Водный',        // 9
  'Травяной',      // 10
  'Электрический', // 11
  'Психический',   // 12
  'Ледяной',       // 13
  'Дракон',        // 14
  'Темный'         // 15
];

          $hiddenpower = 'Тип скрытой силы '.$hptypenumrus[$hptype];
      
      
      $tr_l = explode(',',$pokemon['tren_log']);
          $tren_log = '<div class="tren_path">
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr1"></i>
                                </div>
                                <span>'.$tr_l[0].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr2"></i>
                                </div>
                                <span>'.$tr_l[1].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr3"></i>
                                </div>
                                <span>'.$tr_l[2].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr4"></i>
                                </div>
                                <span>'.$tr_l[3].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr5"></i>
                                </div>
                                <span>'.$tr_l[4].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-crown tr6"></i>
                                </div>
                                <span>'.$tr_l[5].'</span>
                            </div>
                        </div>';
      $html = '
      <div class="Info">
				<div class="Left">
					<div class="PokemonBox">
            <div class="Modif">'.$b.'</div>
						'.$sprite.' <div class="Ball" style="background-image: url(/img/world/items/little/'.$pokemon['ball'].'.png);"></div>
						<div class="Lvl">'.$pokemon['lvl'].'</div>

            '.$item.'
            '.($pokemon['type'] == 'normal' ? '' : '<div class="Unik '.$pokemon['type'].'-color">'.$pokemon['type'].'</div>').'
            <div class="Name '.$pokemon['type'].'-color">
      					<div class="Text">
      						#'.$fullBasenum.' '. mb_strimwidth($pokemon['name_new'], 0, 22, '...') .'
      					</div>
      					<div class="Sex '.$paired.'"><i class="fas fa-'.$gender.'"></i></div>
      				</div>
              <div class="Bars">
  <div class="Bar hp_proggresbar" data-title="HP: '.$pokemon['hp'].' / '.$stats[0].'">
    <div class="HpBar" style="width: '.$fHp.'%;"></div>
    <div class="HpText">'.$pokemon['hp'].' / '.$stats[0].'</div>
  </div>
  <div class="Bar exp_progressbar" data-title="Опыт: '. $exp .'">
    <div class="ExpBar" style="width: '.$fExp.'%;"></div>
  </div>
  <div class="Bar happy_progressbar" data-title="Счастье: '.$pokemon['happy'].' / 255">
    <div class="HappyBar" style="width: '.$fHappy.'%;"></div>
  </div>
</div>
          </div>
					<div class="MoveBox">
            <div class="Move">
              <img src="/img/world/typs/'.($typeOne?$typeOne:'empty').'.png" '.$d1.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category1.'">'.$atkOne.'</div>
                <div class="PP">'.$attack_pp[0].'/'.($ppOne?$ppOne:0).' PP</div>
              </div>
            </div>
            <div class="Move">
              <img src="/img/world/typs/'.($typeTwo?$typeTwo:'empty').'.png" '.$d2.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category2.'">'.$atkTwo.'</div>
                <div class="PP">'.$attack_pp[1].'/'.($ppTwo?$ppTwo:0).' PP</div>
              </div>
            </div>
            <div class="Move">
              <img src="/img/world/typs/'.($typeThree?$typeThree:'empty').'.png" '.$d3.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category3.'">'.$atkThree.'</div>
                <div class="PP">'.$attack_pp[2].'/'.($ppThree?$ppThree:0).' PP</div>
              </div>
            </div>
            <div class="Move">
              <img src="/img/world/typs/'.($typeFour?$typeFour:'empty').'.png" '.$d4.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category4.'">'.$atkFour.'</div>
                <div class="PP">'.$attack_pp[3].'/'.($ppFour?$ppFour:0).' PP</div>
              </div>
            </div>
					</div>
				</div>
				<div class="Right">
					<div class="Id Id-'.$trd.' idPok">id'.$pokemon['id'].' '.$clos.'</div>
					<div class="teamPok">'.$team.'</div>
					<div class="Info">
            <div class="BigInfo">
              <div class="Step genderPok">
                <i class="fal fa-heart"></i>
                <div class="Other '.$sex.'">'.$pokemon['sparkaNumber'].'</div>
              </div>
              <div class="Step vitaminesPok">
                <i class="fal fa-prescription-bottle-alt"></i>
                <div class="Other">'.$pokemon['vitamines'].' / 100</div>
              </div>
              <div class="Step evPok">
                <i class="fal fa-bullseye"></i>
                <div class="Other">'.$pokemon['ev'].'</div>
              </div>
            </div>
						<div class="Step">
							Характер: <span>'.haracter_pokes($pokemon['character']).'</span> <i class="far fa-info-circle staticPok"></i>
						</div>
						<div class="Step">
							Способность: <span>'.$abl.'</span>
						</div>
            <div class="Step">
							Генокод: <span>'.$gen.'</span>
						</div>
						<div class="Step">
							Пойман: <span>'.date('d.m.Y',$birthdayJson->date).'г. в '.date('H:i',$birthdayJson->date).' тренером <div class="user-link"><div onclick=showUserTooltip("'.$userPokemon['id'].'") class="Info-Link sex'.$userPokemon['sex'].'"><i class="fas fa-info"></i></div> <div class="u-'.$userPokemon['user_group'].' label" onclick=user_to_chat_add("'.$userPokemon['id'].'")>'.$userPokemon['login'].'</div></div></span>
						</div>
					</div>
					<div class="MainInfo">
						<div class="Stats">
							<div class="Stat">
								<div class="Name">Здоровье</div>
								<div class="Count">'.$stats[0].' '.$dop_hp.'</div>
								<div class="Progress" data-title="EV: '.$evcounts[0].' / 126">
									<div class="Bar" style="width: '.(($evcounts[0] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har1.'">Атака '.$tr1.' </div>
								<div class="Count '.$har1.'">'.$stats[1].' '.$dop_atk.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[1].' / 126">
									<div class="Bar" style="width: '.(($evcounts[1] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har2.'">Защита '.$tr2.'</div>
								<div class="Count '.$har2.'">'.$stats[2].' '.$dop_def.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[2].' / 126">
									<div class="Bar" style="width: '.(($evcounts[2] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har3.'">Скорость '.$tr3.'</div>
								<div class="Count '.$har3.'">'.$stats[3].'  '.$dop_spd.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[3].' / 126">
									<div class="Bar" style="width: '.(($evcounts[3] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har4.'">Спец. Атака '.$tr4.'</div>
								<div class="Count '.$har4.'">'.$stats[4].' '.$dop_satk.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[4].' / 126">
									<div class="Bar" style="width: '.(($evcounts[4] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har5.'">Спец. Защита '.$tr5.'</div>
								<div class="Count '.$har5.'">'.$stats[5].' '.$dop_sdef.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[5].' / 126">
									<div class="Bar" style="width: '.(($evcounts[5] / 126) * 100).'%;"></div>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
      ';
      $response = array(
        'html' => $html,
        'sexGroup'=>$pokemon['sparkaNumber'],
				'trade'=>$pokemon['trade'],
				'sparka'=>$pokemon['sparka'],
				'st0'=>$st['0'],
				'st1'=>$st['1'],
				'st2'=>$st['2'],
				'st3'=>$st['3'],
				'st4'=>$st['4'],
				'st5'=>$st['5'],
				'st6'=>$st['6'],
				'st7'=>$st['7'],
                'st8'=>$tren_log,
                'hidden'=>$hiddenpower
			);
    }else{
        $response['error'] = 1;
    }
}

if(!empty($_POST['lombard'])){
    $id = escapeMe($_POST['lombard']);
    $pokemon = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `id` = ".$id)->fetch_assoc();
    if($pokemon){
        stat_updates($pokemon['id']);
      $stats = explode(',',$pokemon['stats']);
			$gen = explode(',',$pokemon['gen']);
      if($pokemon['type'] == 'shine'){
        $textUnik = '<div class="Unik shine-color">shine</div>';
				$typeSprite = 'shine';
			}else{
        $typeSprite = 'normal';
        $textUnik = '';
      }
      if($pokemon['form'] != "0"){
        $form = "_".$pokemon['form'];
      }else{
        $form = "";
      }
			$sprite = '<div class="Image"><img onclick="openDex('.$pokemon['basenum'].')" src="/img/pokemons/sprite/'.$pokemon['type'].'/'.numbPok($pokemon['basenum']).$form.'.gif"></div>';
			$fullBasenum = numbPok($pokemon['basenum']);
			$type = $pokemon['type'] != 'normal' ? '<i>'.$pokemon['type'].'</i>' : '';

      $p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.(int)$pokemon['basenum'].'" ')->fetch_object();

      $explvllow = Info::_getExp($pokemon['lvl']-1, $p->exp_group);
      $explvl = $pokemon['exp']-$explvllow;
      $explvl2 = $pokemon['exp_max']-$explvllow;


			$exp = (int)$pokemon['lvl'] != 100 ? $explvl.' / '.$explvl2 : '';
			$paired = $pokemon['sparka'] == 1 ? 'spar' : '';
      if($pokemon['gender'] == 'Девочка') {
        $gender = 'venus';
      }elseif($pokemon['gender'] == 'Мальчик') {
        $gender = 'mars';
      }else{
        $gender = 'genderless';
      }
			$gen = 'h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5];
      $str = explode(',',$pokemon['item_str']);
      if(!empty($pokemon['item_str'])) {
        $str = explode(',',$pokemon['item_str']);
        $str = $str[0].'/'.$str[1];
      }else{
        $str = '';
      }
			$item = $pokemon['item_id'] != 0 ? '<div style="background-image: url(/img/world/items/little/'.$pokemon['item_id'].'.png);" class="Item" onclick=itemAction('.$pokemon['item_id'].',\'remove\',false,false,'.$pokemon['id'].');' .'><div class="str">'.$str.'</div></div>' : '';
			$tren = $pokemon['tren'] != 0 ? '<div class="Tren" id="TrenPokemon'. $pokemon['id'] .'" style="background-image: url(/img/tren/'.$pokemon['tren'].'.png);"></div>' : '';
      $fHp = (($pokemon['hp'] / $stats[0]) * 100);
      if((int)$pokemon['lvl'] == 100) {
        $fExp = 100;
      }else{
        $fExp = ((mb_strimwidth($explvl, 0, 5, "..") / mb_strimwidth($explvl2, 0, 5, "..")) * 100);
      }
      $fHappy = (($pokemon['happy'] / 255) * 100);
			$birthdayJson = json_decode($pokemon['birthday']);
			$up = json_decode($pokemon['modification']);
      $attacks = explode(',',$pokemon['attacks']);
  		$attack_pp = explode(',',$pokemon['pp_attacks']);
  		if($attacks[0] > 0){
  			$atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[0]."'")->fetch_assoc();
  			$atkOne = $atkQuery['name_rus'];
  			$ppOne = $atkQuery['pp'];
  			$typeOne = $atkQuery['type'];
  			if($atkQuery['category'] === 'physical'){$category1 = 1;}
  			elseif($atkQuery['category'] === 'special'){$category1 = 2;}
  			else{$category1 = 3;}
  			$d1 = 'onclick="viewDescriptionAttak(this,'.$attacks[0].');"';
  		}else{
  			$atkOne = 'Нет атаки';
  			$attacks[0] = 0;
  		}
  		if($attacks[1] > 0){
  			$atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[1]."'")->fetch_assoc();
  			$atkTwo = $atkQuery['name_rus'];
  			$ppTwo = $atkQuery['pp'];
  			$typeTwo = $atkQuery['type'];
  			if($atkQuery['category'] === 'physical'){$category2 = 1;}
  			elseif($atkQuery['category'] === 'special'){$category2 = 2;}
  			else{$category2 = 3;}
  			$d2 = 'onclick="viewDescriptionAttak(this,'.$attacks[1].');"';
  		}else{
  			$atkTwo = 'Нет атаки';
  			$attacks[1] = 0;
  			$d2 = '';
  		}
  		if($attacks[2] > 0){
  			$atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[2]."'")->fetch_assoc();
  			$atkThree = $atkQuery['name_rus'];
  			$ppThree = $atkQuery['pp'];
  			$typeThree = $atkQuery['type'];
  			if($atkQuery['category'] === 'physical'){$category3 = 1;}
  			elseif($atkQuery['category'] === 'special'){$category3 = 2;}
  			else{$category3 = 3;}
  			$d3 = 'onclick="viewDescriptionAttak(this,'.$attacks[2].');"';
  		}else{
  			$atkThree = 'Нет атаки';
  			$attacks[2] = 0;

  			$d3 = '';
  		}
  		if($attacks[3] > 0){
  			$atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacks[3]."'")->fetch_assoc();
  			$atkFour = $atkQuery['name_rus'];
  			$ppFour = $atkQuery['pp'];
  			$typeFour = $atkQuery['type'];
  			if($atkQuery['category'] === 'physical'){$category4 = 1;}
  			elseif($atkQuery['category'] === 'special'){$category4 = 2;}
  			else{$category4 = 3;}
  			$d4 = 'onclick="viewDescriptionAttak(this,'.$attacks[3].');"';
  		}else{
  			$atkFour = 'Нет атаки';
  			$attacks[3] = 0;

  			$d3 = '';
  		}
      if($pokemon['trade'] == 'false') {
        $trd = 'tradeNo';
        $clos = '<i class="fas fa-lock"></i>';
      }else{
        $trd = 'tradeYes';
        $clos = '';
      }
      if($pokemon['sparka'] == 1) {
        $spr = 'Недоступно';
      }else{
        $spr = 'Доступно';
      }


      $tr_b = 'angle-double-up';
          if($pokemon['tren'] == 1) { $tr_n = 'tr1'; }
      elseif($pokemon['tren'] == 2) { $tr_n = 'tr2'; }
      elseif($pokemon['tren'] == 3) { $tr_n = 'tr3'; }
      elseif($pokemon['tren'] == 4) { $tr_n = 'tr4'; }
      elseif($pokemon['tren'] == 5) { $tr_n = 'tr5'; }
      elseif($pokemon['tren'] == 6) { $tr_n = 'tr6'; $tr_b = 'crown'; }

          if($pokemon['tren_stat'] == 1) { $tr1 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>'; }
      elseif($pokemon['tren_stat'] == 2) { $tr2 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}
      elseif($pokemon['tren_stat'] == 3) { $tr3 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}
      elseif($pokemon['tren_stat'] == 4) { $tr4 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}
      elseif($pokemon['tren_stat'] == 5) { $tr5 = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';}

      if($pokemon['tren']){
        $b = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>';
      }else{
        $b = '';
      }
      $evcounts = explode(',',$pokemon['evcounts']);
      $userPokemon = $mysqli->query("SELECT `id`,`login`,`user_group`,`sex` FROM `users` WHERE `id` = '".$birthdayJson->user_id."'")->fetch_assoc();
      $dop = explode(',',$pokemon['stat_pl_mn']);
      if($dop[0] > 0){ $dop_hp = '<span class="Green-Color">+'.$dop[0].'</span>';}elseif($dop[0] < 0){ $dop_hp = '<span class="Red-Color">'.$dop[0].'</span>';}else{$dop_hp = '';}
      if($dop[1] > 0){ $dop_atk = '<span class="Green-Color">+'.$dop[1].'</span>';}elseif($dop[1] < 0){ $dop_atk = '<span class="Red-Color">'.$dop[1].'</span>';}else{$dop_atk = '';}
      if($dop[2] > 0){ $dop_def = '<span class="Green-Color">+'.$dop[2].'</span>';}elseif($dop[2] < 0){ $dop_def = '<span class="Red-Color">'.$dop[2].'</span>';}else{$dop_def = '';}
      if($dop[3] > 0){ $dop_spd = '<span class="Green-Color">+'.$dop[3].'</span>';}elseif($dop[3] < 0){ $dop_spd = '<span class="Red-Color">'.$dop[3].'</span>';}else{$dop_spd = '';}
      if($dop[4] > 0){ $dop_satk = '<span class="Green-Color">+'.$dop[4].'</span>';}elseif($dop[4] < 0){ $dop_satk = '<span class="Red-Color">'.$dop[4].'</span>';}else{$dop_satk = '';}
      if($dop[5] > 0){ $dop_sdef = '<span class="Green-Color">+'.$dop[5].'</span>';}elseif($dop[5] < 0){ $dop_sdef = '<span class="Red-Color">'.$dop[5].'</span>';}else{$dop_sdef = '';}
      if($pokemon['sparka'] == 1){
        $sex = 'Red-Color';
      }else{
        $sex = 'Green-Color';
      }

      $haras = $mysqli->query("SELECT * FROM `har` WHERE `id_har` = '".$pokemon['character']."' ")->fetch_assoc();
      if($haras['atk'] == 1.1){$har1 = "Green-Color";}elseif($haras['atk'] == 0.9){$har1 = "Red-Color";}else{ $har1 = "";}
      if($haras['def'] == 1.1){$har2 = "Green-Color";}elseif($haras['def'] == 0.9){$har2 = "Red-Color";}else{ $har2 = "";}
      if($haras['speed'] == 1.1){$har3 = "Green-Color";}elseif($haras['speed'] == 0.9){$har3 = "Red-Color";}else{ $har3 = "";}
      if($haras['satk'] == 1.1){$har4 = "Green-Color";}elseif($haras['satk'] == 0.9){$har4 = "Red-Color";}else{ $har4 = "";}
      if($haras['sdef'] == 1.1){$har5 = "Green-Color";}elseif($haras['sdef'] == 0.9){$har5 = "Red-Color";}else{ $har5 = "";}


      $st = explode(',',$pokemon['static']);


      if($pokemon['team_id'] != 0){
          $bd_team = $mysqli->query("SELECT * FROM `user_pok_team` WHERE `id` = '".$pokemon['team_id']."' ")->fetch_assoc();
          $team = $bd_team['name'];
      }else{
          $team = "";
      }

      if($pokemon['ability'] != 0){
          $abl = '<div onclick="viewDescriptionAbility('.$pokemon['ability'].');" class="AbilityPok">'.ability_name($pokemon['ability']).'</div>';
      }else{
          $abl = 'Отсутствует';
      }
      
      $hpgen[0] = ((intval($gen[0]) % 2) == 0 ? 0 : 1);
          $hpgen[1] = ((intval($gen[1]) % 2) == 0 ? 0 : 1);
          $hpgen[2] = ((intval($gen[2]) % 2) == 0 ? 0 : 1);
          $hpgen[3] = ((intval($gen[3]) % 2) == 0 ? 0 : 1);
          $hpgen[4] = ((intval($gen[4]) % 2) == 0 ? 0 : 1);
          $hpgen[5] = ((intval($gen[5]) % 2) == 0 ? 0 : 1);
          $hptype = ((($hpgen[0] + (2 * $hpgen[1]) + (4 * $hpgen[2]) + (8 * $hpgen[3]) + (16 * $hpgen[4]) + (32 * $hpgen[5])) * 15) / 63);
          $hptype = floor($hptype);
          $hptypenumrus = ['Насекомое','Темный','Дракон','Электрический','Боевой','Огненный','Летающий','Призрачный','Травяной','Земляной','Ледяной','Водный','Ядовитый','Психический','Каменный','Стальной'];
          $hiddenpower = 'Тип скрытой силы '.$hptypenumrus[$hptype];
      
      
      $tr_l = explode(',',$pokemon['tren_log']);
          $tren_log = '<div class="tren_path">
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr1"></i>
                                </div>
                                <span>'.$tr_l[0].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr2"></i>
                                </div>
                                <span>'.$tr_l[1].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr3"></i>
                                </div>
                                <span>'.$tr_l[2].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr4"></i>
                                </div>
                                <span>'.$tr_l[3].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr5"></i>
                                </div>
                                <span>'.$tr_l[4].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-crown tr6"></i>
                                </div>
                                <span>'.$tr_l[5].'</span>
                            </div>
                        </div>';
                        
      $html = '
      <div class="Info">
				<div class="Left">
					<div class="PokemonBox">
            <div class="Modif">'.$b.'</div>
						'.$sprite.' <div class="Ball" style="background-image: url(/img/world/items/little/'.$pokemon['ball'].'.png);"></div>
						<div class="Lvl">'.$pokemon['lvl'].'</div>

            '.$item.'
            '.($pokemon['type'] == 'normal' ? '' : '<div class="Unik '.$pokemon['type'].'-color">'.$pokemon['type'].'</div>').'
            <div class="Name '.$pokemon['type'].'-color">
      					<div class="Text">
      						#'.$fullBasenum.' '. mb_strimwidth($pokemon['name_new'], 0, 22, '...') .'
      					</div>
      					<div class="Sex '.$paired.'"><i class="fas fa-'.$gender.'"></i></div>
      				</div>
              <div class="Bars">
                <div class="Bar hp_proggresbar" data-title="HP: '.$pokemon['hp'].' / '.$stats[0].'">
                  <div class="HpBar" style="width: '.$fHp.'%;"></div>
                </div>
                <div class="Bar exp_progressbar" data-title="Опыт: '. $exp .'">
                  <div class="ExpBar" style="width: '.$fExp.'%;"></div>
                </div>
                <div class="Bar happy_progressbar" data-title="Счастье: '.$pokemon['happy'].' / 255">
                  <div class="HappyBar" style="width: '.$fHappy.'%;"></div>
                </div>
              				</div>
          </div>
					<div class="MoveBox">
            <div class="Move">
              <img src="/img/world/typs/'.($typeOne?$typeOne:'empty').'.png" '.$d1.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category1.'">'.$atkOne.'</div>
                <div class="PP">'.$attack_pp[0].'/'.($ppOne?$ppOne:0).' PP</div>
              </div>
            </div>
            <div class="Move">
              <img src="/img/world/typs/'.($typeTwo?$typeTwo:'empty').'.png" '.$d2.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category2.'">'.$atkTwo.'</div>
                <div class="PP">'.$attack_pp[1].'/'.($ppTwo?$ppTwo:0).' PP</div>
              </div>
            </div>
            <div class="Move">
              <img src="/img/world/typs/'.($typeThree?$typeThree:'empty').'.png" '.$d3.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category3.'">'.$atkThree.'</div>
                <div class="PP">'.$attack_pp[2].'/'.($ppThree?$ppThree:0).' PP</div>
              </div>
            </div>
            <div class="Move">
              <img src="/img/world/typs/'.($typeFour?$typeFour:'empty').'.png" '.$d4.'>
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$category4.'">'.$atkFour.'</div>
                <div class="PP">'.$attack_pp[3].'/'.($ppFour?$ppFour:0).' PP</div>
              </div>
            </div>
					</div>
				</div>
				<div class="Right">
					<div class="Id Id-'.$trd.' idPok">id'.$pokemon['id'].' '.$clos.'</div>
					<div class="teamPok">'.$team.'</div>
					<div class="Info">
            <div class="BigInfo">
              <div class="Step genderPok">
                <i class="fal fa-heart"></i>
                <div class="Other '.$sex.'">'.$pokemon['sparkaNumber'].'</div>
              </div>
              <div class="Step vitaminesPok">
                <i class="fal fa-prescription-bottle-alt"></i>
                <div class="Other">'.$pokemon['vitamines'].' / 100</div>
              </div>
              <div class="Step evPok">
                <i class="fal fa-bullseye"></i>
                <div class="Other">'.$pokemon['ev'].'</div>
              </div>
            </div>
						<div class="Step">
							Характер: <span>'.haracter_pokes($pokemon['character']).'</span> <i class="far fa-info-circle staticPok"></i>
						</div>
						<div class="Step">
							Способность: <span>'.$abl.'</span>
						</div>
            <div class="Step">
							Генокод: <span>'.$gen.'</span>
						</div>
						<div class="Step">
							Пойман: <span>'.date('d.m.Y',$birthdayJson->date).'г. в '.date('H:i',$birthdayJson->date).' тренером <div class="user-link"><div onclick=showUserTooltip("'.$userPokemon['id'].'") class="Info-Link sex'.$userPokemon['sex'].'"><i class="fas fa-info"></i></div> <div class="u-'.$userPokemon['user_group'].' label" onclick=user_to_chat_add("'.$userPokemon['id'].'")>'.$userPokemon['login'].'</div></div></span>
						</div>
					</div>
					<div class="MainInfo">
						<div class="Stats">
							<div class="Stat">
								<div class="Name">Здоровье</div>
								<div class="Count">'.$stats[0].' '.$dop_hp.'</div>
								<div class="Progress" data-title="EV: '.$evcounts[0].' / 126">
									<div class="Bar" style="width: '.(($evcounts[0] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har1.'">Атака '.$tr1.' </div>
								<div class="Count '.$har1.'">'.$stats[1].' '.$dop_atk.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[1].' / 126">
									<div class="Bar" style="width: '.(($evcounts[1] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har2.'">Защита '.$tr2.'</div>
								<div class="Count '.$har2.'">'.$stats[2].' '.$dop_def.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[2].' / 126">
									<div class="Bar" style="width: '.(($evcounts[2] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har3.'">Скорость '.$tr3.'</div>
								<div class="Count '.$har3.'">'.$stats[3].'  '.$dop_spd.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[3].' / 126">
									<div class="Bar" style="width: '.(($evcounts[3] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har4.'">Спец. Атака '.$tr4.'</div>
								<div class="Count '.$har4.'">'.$stats[4].' '.$dop_satk.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[4].' / 126">
									<div class="Bar" style="width: '.(($evcounts[4] / 126) * 100).'%;"></div>
								</div>
							</div>
							<div class="Stat">
								<div class="Name '.$har5.'">Спец. Защита '.$tr5.'</div>
								<div class="Count '.$har5.'">'.$stats[5].' '.$dop_sdef.'</div>
								<div class="Progress"  data-title="EV: '.$evcounts[5].' / 126">
									<div class="Bar" style="width: '.(($evcounts[5] / 126) * 100).'%;"></div>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
      ';
      $response = array(
        'html' => $html,
        'sexGroup'=>$pokemon['sparkaNumber'],
				'trade'=>$pokemon['trade'],
				'sparka'=>$pokemon['sparka'],
				'st0'=>$st['0'],
				'st1'=>$st['1'],
				'st2'=>$st['2'],
				'st3'=>$st['3'],
				'st4'=>$st['4'],
				'st5'=>$st['5'],
				'st6'=>$st['6'],
				'st7'=>$st['7'],
                'st8'=>$tren_log,
                'hidden'=>$hiddenpower

			);
    }else{
        $response['error'] = 1;
    }
}

echo json_encode($response);

?>

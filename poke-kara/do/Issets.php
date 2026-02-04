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
$type = escapeMe($_POST["type"]);
$id = escapeMe($_POST["id"]);
$other = escapeMe($_POST["other"]);
switch ($type) {
    case 'week':
        if($id == 1){
            $a .= '<div class="Name">Блокирование слотов</div>
            <div class="About">Эта функция позволяет заблокировать 100% 3 слота которые не содержат в себе ключ. ВАЖНО!!! Эта функция блокирует не только пустые слоты, но и слоты содержавшие в себе клад. Стоимость этой функции Драгоценный камень х5.</div>
            ';
        }
        $response['text'] = $a;
    break;
    case 'cristmas':
        if($id == 1){
            $a .= '<div class="Name">Условия получения награды</div>
            <div class="About">После окончания ивента вы сможете получить награду за серверную ёлку в зависимости от того, сколько веток будет сдано всеми игроками. Ваша личная ёлка должна иметь минимум 100 веток.</div>
            <div class="Conditions">
                <ul>
                    <li>500 - <img src="/img/world/items/little/196.png"> Именной бланк х2</li>
                    <li>2.000 - <img src="/img/world/items/little/34.png"> Черная конфета х3</li>
                    <li>3.500 - <img src="/img/world/items/little/197.png"> Набор тренировки х10</li>
                    <li>5.000 - <img src="/img/world/items/little/256.png"> Лекарство х3</li>
                    <li>7.500 - <img src="/img/world/items/little/268.png"> Зелье памяти х2</li>
                    <li>10.000 - <img src="/img/pokemons/animation/776.png"> Яйцо #776 Туртонатор</li>
                    <li>15.000 - <img src="/img/pokemons/animation/131.png"> Яйцо #131 Лапрас</li>
                </ul>
            </div>
            ';
        }elseif($id == 2){
            $a .= '<div class="Name">Условия получения награды</div>
            <div class="About">После окончания ивента вы сможете получить награду за личную ёлку в зависимости от того, сколько веток будет сдано лично Вами.</div>
            <div class="Conditions">
                <ul>
                    <li>10 - <img src="/img/world/items/little/1.png"> Монета х100.000</li>
                    <li>25 - <img src="/img/world/items/little/30.png"> Розовая конфета х20</li>
                    <li>50 - <img src="/img/world/items/little/53.png"> Бриллиантовый покебол х10</li>
                    <li>75 - <img src="/img/pokemons/animation/088.png"> Яйцо #088 Граймер - Алола</li>
                    <li>100 - <img src="/img/world/items/little/40.png"> Типовая конфета х25</li>
                    <li>150 - <img src="/img/world/items/little/187.png"> Приманка х10</li>
                    <li>175 - <img src="/img/pokemons/animation/079.png"> Яйцо #079 Слоупок - Алола</li>
                    <li>200 - <img src="/img/world/items/little/196.png"> Именной бланк х5</li>
                    <li>250 - <img src="/img/pokemons/animation/122.png"> Яйцо #122 Мистер Майм - Галар</li>
                    <li>300 - <img src="/img/world/items/little/426.png"> Сладкая вата х15</li>
                    <li>350 - <img src="/img/world/items/little/34.png"> Черная конфета х4</li>
                    <li>500 - <img src="/img/pokemons/animation/554.png"> Яйцо #554 Дарумака - Галар</li>
                    <li>600 - <img src="/img/world/items/little/269.png">/<img src="/img/world/items/little/270.png"> Гормон тестостерон или эстроген х5</li>
                    <li>750 - <img src="/img/world/items/little/268.png"> Зелье памяти х4</li>
                    <li>1.000 - <img src="/img/world/items/little/255.png"> Корень априкорна х2</li>
                    <li>1.250 - <img src="/img/world/items/little/32.png"> Шоколадная конфета х3</li>
                    <li>1.500 - <img src="/img/world/items/little/289.png"> Препарат Q х3</li>
                    <li>2.000 - <img src="/img/pokemons/animation/246.png"> Яйцо #246 Ларвитар или <img src="/img/pokemons/animation/371.png"> Яйцо #371 Багон или <img src="/img/pokemons/animation/374.png"> Яйцо #374 Белдум или <img src="/img/pokemons/animation/633.png"> Яйцо #633 Дейно</li>
                </ul>
            </div>
            ';
        }else{
            $a .= '<div class="Name">Условия получения награды</div>
            <div class="About">В любой момент вы можете обменять жетоны на награды. В зависимости от сданных жетонов призы будут умножаться.</div>
            <div class="Conditions">
                <ul>
                    <li>1 - <img src="/img/world/items/little/1.png"> Монета х4.000</li>
                    <li>5 - <img src="/img/world/items/little/30.png"> Розовая конфета х1</li>
                    <li>15 - <img src="/img/world/items/little/246.png"> Кекс х1</li>
                    <li>30 - <img src="/img/world/items/little/197.png"> Набор тренировки х1</li>
                    <li>45 - <img src="/img/world/items/little/256.png"> Лекарство х1</li>
                    <li>70 - <img src="/img/world/items/little/34.png"> Черная конфета х1</li>
                    <li>100 - <img src="/img/world/items/little/513.png"> Загатовочный диск х1</li>
                    <li>125 - <img src="/img/world/items/little/32.png"> Шоколадная конфета х1</li>
                    <li>150 - <img src="/img/world/items/little/255.png"> Корень априкорна х1</li>
                    <li>300 - <img src="/img/world/items/little/223.png"> Загадочный покебол х1</li>
                </ul>
            </div>
            ';
        }
        $response['text'] = $a;
    break;
    case 'web':
        $user = $mysqli->query("SELECT `location`,`web_lot` FROM `users` WHERE `id` = '".$_SESSION['id']."' ")->fetch_assoc();
        $base = $mysqli->query("SELECT * FROM `user_web_slot` WHERE `location` = ".$user['location']." AND `slot` = ".$id." AND `user` = '".$_SESSION['id']."' ")->fetch_assoc();
        $a .= '<div class="Name">Слот #'.$id.'</div>';
        if($base){
            $a .= '<div class="About">Приманка установлена, ожидайте покемона.</div>';
            if($base['pok'] != 0){
                $a .= '<h2>Покемон пойман!</h2>';
                $a .= '<div class="Buttons"><div onclick="slot_web('.$id.',3);">Забрать</div></div>'; 
            }
        }else{
            if($id > $user['web_lot']){
                if($id == 4){
                    $a .= '<div class="About">Условия для открытия:<br>
                                <span>Открыто слотов: <b>3</b></span></div>';
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_web('.$id.',1);">Открыть</div><span>20 драг. камней</span></div>';
                }
                if($id == 5){
                    $a .= '<div class="About">Условия для открытия:<br>
                                <span>Открыто слотов: <b>4</b></span></div>';
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_web('.$id.',1);">Открыть</div><span>30 драг. камней</span></div>';
                }
                if($id == 6){
                    $a .= '<div class="About">Условия для открытия:<br>
                                <span>Открыто слотов: <b>5</b></span></div>';
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_web('.$id.',1);">Открыть</div><span>40 драг. камней</span></div>';
                }
            }else{
                $a .= '<div class="Buttons"><div onclick="slot_web('.$id.',2);">Установить</div></div>'; 
            }
        }
        $response['text'] = $a;
    break;
    case 'crafting_slot':
        $user = $mysqli->query("SELECT `crafting_lot`,`lvl` FROM `users` WHERE `id` = '".$_SESSION['id']."' ")->fetch_assoc();
        $l = $mysqli->query("SELECT * FROM `users_tm_create` WHERE `user` = '".$_SESSION['id']."' AND `slot` = '".$id."' ")->fetch_assoc();
        if(empty($l)){
            $a .= '<div class="Name">Слот #'.$id.'</div>';
            if($id == 3){
                $a .= '<div class="About">Условия для открытия:<br><span>Уровень тренера: <b>25</b></span>
                <span>Открыто слотов: <b>2</b></span></div>';
                if($user['lvl'] >= 25 AND $user['crafting_lot'] == 2){
                    $a .= '<div class="Buttons"><div onclick="slot_tm('.$id.',2);">Открыть</div></div>'; 
                }else{
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_tm('.$id.',1);">Открыть сейчас</div><span>20 драг. камней</span></div>';
                }
            }
            if($id == 4){
                $a .= '<div class="About">Условия для открытия:<br><span>Уровень тренера: <b>30</b></span>
                <span>Открыто слотов: <b>3</b></span></div>';
                if($user['lvl'] >= 30 AND $user['crafting_lot'] == 3){
                    $a .= '<div class="Buttons"><div onclick="slot_tm('.$id.',2);">Открыть</div></div>'; 
                }elseif($user['lvl'] >= 30 AND $user['crafting_lot'] != 3){
                    
                }else{
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_tm('.$id.',1);">Открыть сейчас</div><span>35 драг. камней</span></div>';
                }
            }
            if($id == 5){
                $a .= '<div class="About">Условия для открытия:<br><span>Уровень тренера: <b>35</b></span>
                <span>Открыто слотов: <b>4</b></span></div>';
                if($user['lvl'] >= 35 AND $user['crafting_lot'] == 4){
                    $a .= '<div class="Buttons"><div onclick="slot_tm('.$id.',2);">Открыть</div></div>'; 
                }elseif($user['lvl'] >= 35 AND $user['crafting_lot'] != 4){
                    
                }else{
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_tm('.$id.',1);">Открыть сейчас</div><span>40 драг. камней</span></div>';
                }
            }
            if($id == 6){
                $a .= '<div class="About">Условия для открытия:<br><span>Уровень тренера: <b>45</b></span>
                <span>Открыто слотов: <b>5</b></span></div>';
                if($user['lvl'] >= 45 AND $user['crafting_lot'] == 5){
                    $a .= '<div class="Buttons"><div onclick="slot_tm('.$id.',2);">Открыть</div></div>'; 
                }elseif($user['lvl'] >= 45 AND $user['crafting_lot'] != 5){
                    
                }else{
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_tm('.$id.',1);">Открыть сейчас</div><span>40 драг. камней</span></div>';
                }
            }
            if($id == 7){
                $a .= '<div class="About">Условия для открытия:<br><span>Уровень тренера: <b>50</b></span>
                <span>Открыто слотов: <b>6</b></span></div>';
                if($user['lvl'] >= 50 AND $user['crafting_lot'] == 6){
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_tm('.$id.',1);">Открыть сейчас</div><span>50 драг. камней</span></div>';
                }
            }
            if($id == 8){
                $a .= '<div class="About">Условия для открытия:<br><span>Уровень тренера: <b>50</b></span>
                <span>Открыто слотов: <b>7</b></span></div>';
                if($user['lvl'] >= 50 AND $user['crafting_lot'] == 7){
                    $a .= '<div class="Buttons"><div class="lombard" onclick="slot_tm('.$id.',1);">Открыть сейчас</div><span>50 драг. камней</span></div>';
                }
            }
            
            
            
            
            
            
        }else{
            $items = $mysqli->query("SELECT `name` FROM `base_items` WHERE `info` = '".$l['atk']."' AND `id` > 1000 ")->fetch_assoc();
            $a = '<div class="Name">Слот #'.$id.'</div> <div class="About">Изготавливается '.$items['name'].'</div>';
            $time = downcounter($l['time_end']);
            if($l['time_end'] > time()){
                $a .= '<h2>НЕ ГОТОВО!</h2>';
                $a .= 'Будет готово через '.$time;
                $a .= '<div class="Buttons"><div class="lombard" onclick="give_tm('.$l['id'].',1);">Ускорить</div><span>20 драг. камней</span></div>';
            }else{
               $a .= '<div class="Buttons"><div onclick="give_tm('.$l['id'].',2);">Забрать</div></div>'; 
            }
        }
        $response['text'] = $a;
    break;
    case 'attackpok':
        $a = '<div class="Name">Выберите атаку</div>';
        $pok = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1 AND `id` = '".$id."' ")->fetch_assoc();
        $atk = explode(',',$pok['attacks']);
        for($i=0;$i<=3;$i++){
            if(isset($atk[$i])){
                $baseInfo = $mysqli->query('SELECT * FROM `base_atk` WHERE `id` = '.$atk[$i])->fetch_assoc();
                if(!empty($baseInfo)){
                    $a .= '<div class="atkcraft_poklist" onclick="select_attack_tmcrafting('.$id.','.$baseInfo['id'].')"><img src="/img/world/typs/'.$baseInfo['type'].'.png"><span>'.$baseInfo['name_rus'].'</span></div>';
                }
            }
        }
        $response['text'] = $a;
    break;
    case 'learnpok': {
    // параметры как раньше через /do/issets
    $attackId = (int)($_POST['pokID'] ?? 0);
    $mode     = (int)($_POST['mode']  ?? 1);  // 1=уровень, 2=TM/TR, 3=разведение, 4=обучение (tutor)

    // защита и заголовок
    if ($attackId <= 0) {
        $response['text'] = '<div class="About">Ошибка: не передан ID атаки.</div>';
        break;
    }

    $atkRow = $mysqli->query("SELECT `name_rus` FROM `base_atk` WHERE `id` = {$attackId}")->fetch_assoc();
    $atkName = $atkRow ? htmlspecialchars($atkRow['name_rus']) : ('#'.(int)$attackId);

    $html = '<div class="Name">'.$atkName.'</div>';

    // общий рендер списка
    $renderList = function(array $ids) use ($mysqli) {
        if (empty($ids)) {
            return '<div class="blockpok"><div class="linepok">Никто не изучает.</div></div>';
        }
        // уникализируем и сортируем
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);

        // тянем имена одним запросом
        $in = implode(',', $ids);
        $names = [];
        if ($in !== '') {
            $res = $mysqli->query("SELECT `id`,`name_rus` FROM `base_pokemons` WHERE `id` IN ($in)");
            while ($r = $res->fetch_assoc()) {
                $names[(int)$r['id']] = $r['name_rus'];
            }
        }

        $out = '<div class="blockpok">';
        foreach ($ids as $pid) {
            $name = isset($names[$pid]) ? htmlspecialchars($names[$pid]) : '';
            // numbPok() у тебя уже есть – форматирует номер типа #007
            $out .= '<div class="linepok" onclick="openDex('.$pid.')">'
                  .    '<img src="/img/pokemons/animation/'.$pid.'.png"> '
                  .    '#'.numbPok($pid).' '.$name
                  .  '</div>';
        }
        $out .= '</div>';
        return $out;
    };

    if ($mode === 1) {
        // По уровню
        $html .= '<div class="About">Покемоны, которые могут выучить эту атаку по уровню</div>';
        $q = $mysqli->query('SELECT `pok`,`attacks` FROM `base_attacks_pokemons` WHERE `type`="lvl" AND `pok` < 1000 ORDER BY `pok` ASC');
        $ids = [];
        while ($row = $q->fetch_assoc()) {
            $arr = explode(',', $row['attacks']);
            if (in_array($attackId, array_map('intval', $arr), true)) {
                $ids[] = (int)$row['pok'];
            }
        }
        $html .= $renderList($ids);

    } elseif ($mode === 2) {
        // TM/TR
        $html .= '<div class="About">Покемоны, которые могут выучить эту атаку как TM/TR</div>';
        $ids = [];
        $tm = $mysqli->query('SELECT `tm_id` FROM `base_items` WHERE `info` = "'.$attackId.'" LIMIT 1')->fetch_assoc();
        if ($tm && isset($tm['tm_id'])) {
            $tmid = $mysqli->real_escape_string($tm['tm_id']);
            $q = $mysqli->query('SELECT `poke_base_id` FROM `attac_poke_tm` WHERE `tm_id` = "'.$tmid.'" AND `poke_base_id` < 1000 ORDER BY `poke_base_id` ASC');
            while ($row = $q->fetch_assoc()) {
                $ids[] = (int)$row['poke_base_id'];
            }
            $html .= $renderList($ids);
        } else {
            $html .= '<div class="blockpok"><div class="linepok">~Не является TM/TR-атакой~</div></div>';
        }

    } elseif ($mode === 3) {
        // Разведение
        $html .= '<div class="About">Покемоны, которые могут выучить эту атаку по разведению</div>';
        $q = $mysqli->query('SELECT `pok`,`attacks` FROM `base_attacks_pokemons` WHERE `type`="sex" AND `pok` < 1000 ORDER BY `pok` ASC');
        $ids = [];
        while ($row = $q->fetch_assoc()) {
            $arr = explode(',', $row['attacks']);
            if (in_array($attackId, array_map('intval', $arr), true)) {
                $ids[] = (int)$row['pok'];
            }
        }
        $html .= $renderList($ids);

    } else {
        // Обучение (tutor) — пробуем несколько возможных значений type
        $html .= '<div class="About">Покемоны, которые могут выучить эту атаку через обучение</div>';
        $ids = [];
        $types = ['teach','tutor','ob','learn']; // на случай разных схем
        foreach ($types as $tp) {
            $q = $mysqli->query('SELECT `pok`,`attacks` FROM `base_attacks_pokemons` WHERE `type`="'.$mysqli->real_escape_string($tp).'" AND `pok` < 1000 ORDER BY `pok` ASC');
            if ($q) {
                while ($row = $q->fetch_assoc()) {
                    $arr = explode(',', $row['attacks']);
                    if (in_array($attackId, array_map('intval', $arr), true)) {
                        $ids[] = (int)$row['pok'];
                    }
                }
                if (!empty($ids)) break; // нашли — хватит
            }
        }
        if (empty($ids)) {
            $html .= '<div class="blockpok"><div class="linepok">Список для обучения не найден.</div></div>';
        } else {
            $html .= $renderList($ids);
        }
    }

    $response['text'] = $html;
    break;
}

    case 'playing_house':
        $playing = $mysqli->query('SELECT * FROM `a_ivent_week_playhome_game` WHERE `user` = "'.$_SESSION['id'].'" ')->fetch_assoc();
        $a .= '<div class="Name">Выберите покемона</div>
                <div class="pokemonWish" onclick="playing_give('.$id.',\''.$other.'\',1)"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$playing['pok1'].'.gif"></div>
                <div class="pokemonWish" onclick="playing_give('.$id.',\''.$other.'\',2)"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$playing['pok2'].'.gif"></div>
                <div class="pokemonWish" onclick="playing_give('.$id.',\''.$other.'\',3)"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$playing['pok3'].'.gif"></div>
                <div class="pokemonWish" onclick="playing_give('.$id.',\''.$other.'\',4)"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$playing['pok4'].'.gif"></div>
        ';
        $response['text'] = $a;
    break;
    case 'codeloto':
        
        $info = $mysqli->query('SELECT * FROM `mom_loterry_code` WHERE `id` = '.$id)->fetch_assoc();
        if($info){
            if($info['uses'] == 0){
                $a = '<div class="Name">Модификация</div>';
                $pok = $mysqli->query('SELECT `id`,`active`,`basenum`,`name_new`,`lvl`,`user_id` FROM `user_pokemons` WHERE `user_id` = "'.$_SESSION['id'].'" AND `active` = "1"');
                if($info['type'] == 1){
                    $a .= '<div class="About">Этот код позволяет изменить характер покемону на ваше усмотрение. <br>После этого покемон становится приручен! <br>Менять характер можно только покемонам до 7 категории силы включительно! <br>У покемона должна быть возможность применения пилюлю для успешной модификации!</div>
                            <div class="Buttons"><label>Выберите покемона</label><select id="pokID">';
                    while ($pk = $pok->fetch_assoc()){
            $a .= '<option value="'.$pk['id'].'">#'.numbPok($pk['basenum']).' '.$pk['name_new'].' '.$pk['lvl'].'lvl</option>';
          }
					$a .='</select>
					<label>Выберите характер</label>
					<select id="harID">
					    <option value="1">Веселый</option>
  <option value="2">Выносливый</option>
  <option value="3">Застенчивый</option>
  <option value="4">Кроткий</option>
  <option value="5">Мирный</option>
  <option value="6">Мягкий</option>
  <option value="7">Наглый</option>
  <option value="8">Наивный</option>
  <option value="9">Нахальный</option>
  <option value="10">Нежный</option>
  <option value="11">Непослушный</option>
  <option value="12">Непреклонный</option>
  <option value="13">Обычный</option>
  <option value="14">Одинокий</option>
  <option value="15">Озорной</option>
  <option value="16">Осторожный</option>
  <option value="17">Поспешный</option>
  <option value="18">Причудливый</option>
  <option value="19">Распущенный</option>
  <option value="20">Робкий</option>
  <option value="21">Серьезный</option>
  <option value="22">Скромный</option>
  <option value="23">Смелый</option>
  <option value="24">Спокойный</option>
  <option value="25">Стремительный</option>
  <option value="26">Тихий</option>
					    
					</select>
						<div onclick="CodeActive('.$info['id'].',1);">Изменить</div></div>
        ';
                }elseif($info['type'] == 2){
                    
                    
                    $a .= '<div class="About">Этот код позволяет покрасить покемона в шайни. <br>После этого покемон становится приручен! <br>Менять окрас можно только покемонам до 7 категории силы включительно! <br>Покемон должен иметь обычный окрас!</div>
                            <div class="Buttons"><label>Выберите покемона</label><select id="pokID">';
                    while ($pk = $pok->fetch_assoc()){
            $a .= '<option value="'.$pk['id'].'">#'.numbPok($pk['basenum']).' '.$pk['name_new'].' '.$pk['lvl'].'lvl</option>';
          }
          $a .= '</select><div onclick="CodeActive('.$info['id'].',2);">Изменить</div></div>
        ';
        
        
                }elseif($info['type'] == 3){
                    
                    
                    $a .= '<div class="About">Этот код позволяет увеличить генокод одного из статов на 2 пункта. <br>После этого покемон становится приручен и теряет возможность к разведению! <br>Увеличивать генокод можно только покемонам до 7 категории силы включительно!</div>
                            <div class="Buttons"><label>Выберите покемона</label><select id="pokID">';
                    while ($pk = $pok->fetch_assoc()){
            $a .= '<option value="'.$pk['id'].'">#'.numbPok($pk['basenum']).' '.$pk['name_new'].' '.$pk['lvl'].'lvl</option>';
          }
          $a .= '</select><label>Выберите стат</label>
					<select id="statID">
					    <option value="0">HP</option>
  <option value="1">Атака</option>
  <option value="2">Защита</option>
  <option value="3">Скорость</option>
  <option value="4">Спец. Атака</option>
  <option value="5">Спец. Защита</option>
					</select><div onclick="CodeActive('.$info['id'].',3);">Увеличить</div></div>
        ';
                    
                    
                }elseif($info['type'] == 4){
                    
                    
                    $a .= '<div class="About">Этот код позволяет повысить счастье вашего покемона на 75 пунктов. <br>Повышать счастье можно только покемонам до 7 категории силы включительно!</div>
                            <div class="Buttons"><label>Выберите покемона</label><select id="pokID">';
                    while ($pk = $pok->fetch_assoc()){
            $a .= '<option value="'.$pk['id'].'">#'.numbPok($pk['basenum']).' '.$pk['name_new'].' '.$pk['lvl'].'lvl</option>';
          }
          $a .= '</select><div onclick="CodeActive('.$info['id'].',4);">Повысить</div></div>
        ';
        
        
                }elseif($info['type'] == 5){
                    
                    
                    $a .= '<div class="About">Этот код позволяет добавить 5 ev очков покемону в свободные. <br>После этого покемон становится приручен! <br>Добавлять ev очки можно только покемонам до 7 категории силы включительно!</div>
                            <div class="Buttons"><label>Выберите покемона</label><select id="pokID">';
                    while ($pk = $pok->fetch_assoc()){
            $a .= '<option value="'.$pk['id'].'">#'.numbPok($pk['basenum']).' '.$pk['name_new'].' '.$pk['lvl'].'lvl</option>';
          }
          $a .= '</select><div onclick="CodeActive('.$info['id'].',5);">Добавить</div></div>
        ';
        
        
                }else{
                    $a = '<div class="Name">Ошибка! Код имеет неверный тип!</div>';
                }
            }else{
                $a = '<div class="Name">Ошибка! Код уже был использован!</div>';
            }
        }else{
            $a = '<div class="Name">Ошибка! Код не найден!</div>';
        }         
        $response['text'] = $a;
    break;
    //case 'lot_m_allprize':
        //$a = '<div class="Name">Возможные призы</div><div class="blockprize">';
        //$bd = $mysqli->query('SELECT * FROM `mom_loterry_prize` ORDER BY `chance` DESC');
        //if($bd->num_rows > 0){
            //while($bd_s = $bd->fetch_assoc()){
                //$ch = round((($bd_s['chance']/260)*100),2);
                //if($bd_s['prize'] == 151){
                    //$info = $mysqli->query('SELECT `name_rus`,`id` FROM `base_pokemons` WHERE `id` = '.$bd_s['pok'])->fetch_assoc();
                    //$a .= '<div class="prizeblock"><div class="namePr"> <img src="/img/world/items/little/151.png" class="item"> Яйцо '.$info['name_rus'].'</div><div class="count"><span class="ch">Шанс '.$ch.'%</span>Получено <span class="Green-Color">'.$bd_s['count'].'</span> из <span class="Red-Color">'.$bd_s['count_max'].'</span></div></div>';
                //}elseif($bd_s['prize'] == 999){
                    //$a .= '<div class="prizeblock"><div class="namePr"> Модификация </div><div class="count"><span class="ch">Шанс '.$ch.'%</span>Получено <span class="Green-Color">'.$bd_s['count'].'</span> из <span class="Red-Color">'.$bd_s['count_max'].'</span></div></div>';
                //}else{
                    //$info = $mysqli->query('SELECT `name`,`id` FROM `base_items` WHERE `id` = '.$bd_s['prize'])->fetch_assoc();
                    //$a .= '<div class="prizeblock"><div class="namePr"> <img src="/img/world/items/little/'.$bd_s['prize'].'.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> '.$info['name'].'</div><div class="count"><span class="ch">Шанс '.$ch.'%</span>Получено <span class="Green-Color">'.$bd_s['count'].'</span> из <span class="Red-Color">'.$bd_s['count_max'].'</span></div></div>';
                //}
            //}
        //}else{
            //$a .= '<center>У вас нет призов!</center>';
        //}
        //$a .= '</div>';
        //$response['text'] = $a;
    //break;
    //case 'lot_m_youprize':
        //$a = '<div class="Name">Ваши призы</div><div class="blockpok">';
        //$bd_code = $mysqli->query('SELECT * FROM `mom_loterry_code` WHERE `user` = '.$_SESSION['id'].' AND `uses` = 0 ORDER BY `type` DESC');
        //if($bd_code->num_rows > 0){
            //while($bd_s_code = $bd_code->fetch_assoc()){
                //$a .= '<div class="linepok gd"> <span class="Red-Color">коды</span>';
                
                //if($bd_s_code['type'] == 1){
                        //$tlp = 'Модификация на смену характера ';
                    //}elseif($bd_s_code['type'] == 2){
                        //$tlp = 'Модификация на окрас в шайни ';
                    //}elseif($bd_s_code['type'] == 3){
                        //$tlp = 'Модификация на повышение гена ';
                    //}elseif($bd_s_code['type'] == 4){
                        //$//tlp = 'Модификация на повышение счастья ';
                    //}elseif($bd_s_code['type'] == 5){
                        //$tlp = 'Модификация на добавление EV ';
                    //}
                //
                //$a .= '<span class="text_pr"> '.$tlp.' <b>'.$bd_s_code['code'].'</b></span></div>';
           // }
        //}else{
            //$a .= '<center>У вас нет призов!</center>';
        //}
        // $bd = $mysqli->query('SELECT * FROM `mom_loterry_log` WHERE `user` = '.$_SESSION['id'].' AND `type` != 0 ORDER BY `type` DESC');
        // if($bd->num_rows > 0){
        //     while($bd_s = $bd->fetch_assoc()){
        //         $a .= '<div class="linepok gd"> <span class="date">'.$bd_s['date'].'</span> <span class="text_pr">'.$bd_s['text'].'</span></div>';
        //     }
        // }else{
        //     $a .= '<center>У вас нет призов!</center>';
        // }
    break;
    case 'ability':
        $bd = $mysqli->query('SELECT * FROM `base_ability` WHERE `id` = '.$id)->fetch_assoc();
        
        $a = '<div class="Name">'.$bd['name_rus'].'</div><div class="About">'.$bd['about'].'</div>';
        $response['text'] = $a;
    break;
    case 'GiftUsFr':
        $a = '<div class="Name">Подарки от друзей</div><div class="About">Получите подарки от своих друзей, пока они не удалились.</div><div class="blockpok">';
        $bd = $mysqli->query('SELECT * FROM `GiftFriend` WHERE `user_to` = '.$_SESSION['id'].' AND `active` = 0');
        if($bd->num_rows > 0){
            while($bd_s = $bd->fetch_assoc()){
                $bd_ds = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$bd_s['user_from'])->fetch_assoc();
                $a .= '<div class="linepok gd"><i class="fa fa-gift giftic"></i> от <b>'.$bd_ds['login'].'</b> <button class="giftgive" onclick="GiveGift('.$bd_s['id'].')">Забрать</button></div>';
            }
        }else{
            $a .= '<center>У вас нет подарков!</center>';
        }
        $a .= '</div>';
        $response['text'] = $a;
    break;
case 'referal':
    $bd = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
    $bd_ref = $mysqli->query('SELECT * FROM `users` WHERE `referal_you` = "'.$bd['referal'].'" ');
    $a = '<div class="Name">Ваши рефералы</div><div class="About"><div class="ReferalList">';
    if($bd_ref->num_rows > 0){
        while($ref = $bd_ref->fetch_assoc()){
            $vc = $mysqli->query('SELECT * FROM `base_referal` WHERE `user` = '.$_SESSION['id'].' AND `referal` = "'.$ref['id'].'" ')->fetch_assoc();
            // Определяем, какие награды уже получены для этого реферала
            $awarded_levels = [];
            if (!empty($vc) && !empty($vc['levels'])) {
                $awarded_levels = explode(',', $vc['levels']);
            }
            $is_online = ($ref['online'] >= time()-300) ? 'onl' : 'ofl';

            // ---- Улучшенная структура TrainerBlock ----
            $a .= '<div class="User">';
            $a .= '<div class="TrainerBlock">';
            
            // Левая колонка — аватар и статус
            $a .= '<div class="AvatarWrap">';
            $a .= '<div onclick="showUserTooltip('.$ref['id'].')" class="Avatar" style="background-image: url(/img/avatars/mini/'.$ref['id'].'.png);">';
            $a .= '<div class="Status '.$is_online.'"></div>';
            $a .= '</div>';
            $a .= '</div>';

            // Правая часть — основная информация
            $a .= '<div class="TrainerInfo">';
            $a .= '<div class="NameRow">';
            $a .= '<span class="TrainerName u-'.$ref['user_group'].' label" onclick="user_to_chat_add('.$ref['id'].')">'.$ref['login'].'</span>';
            $a .= '<span class="TrainerRang">'.$ref['rang'].'</span>';
            $a .= '</div>';
            $a .= '<div class="LevelRow">';
            $a .= '<span class="TrainerLevel">'.$ref['lvl'].'</span>';
            $a .= '<span class="TrainerLvlLabel">уровень</span>';
            $a .= '</div>';

            // Показываем только одну (следующую) актуальную награду с кнопкой "Забрать", если можно получить
            $rewards = [
                10 => [
                    'img' => '/img/world/items/little/1.png',
                    'desc' => '300 000 <span class="Green-Color">генкаров</span>',
                    'got' => in_array(10, $awarded_levels)
                ],
                15 => [
                    'img' => '/img/world/items/little/448.png',
                    'desc' => 'Премиум <span class="Premium-Color">2 дня</span>',
                    'got' => in_array(15, $awarded_levels)
                ],
                20 => [
                    'img' => '/img/world/items/little/25.png',
                    'desc' => '50 <span class="Violet-Color">аметистов</span>',
                    'got' => in_array(20, $awarded_levels)
                ]
            ];

            $a .= '<div class="RefRewardSingle">';
            $shown = false;
            foreach ($rewards as $lvl => $rwd) {
                if (!$rwd['got']) {
                    if ($ref['lvl'] >= $lvl) {
                        // Можно получить прямо сейчас! Показываем кнопку
                        $a .= '
                        <form class="RefRewardForm" method="post" onsubmit="getReferalReward(event, '.$ref['id'].','.$lvl.')">
                            <button type="submit" class="RefRewardBtn" data-tipped="Забрать награду за '.$lvl.' уровень">
                                <img src="'.$rwd['img'].'" alt="prize"> 
                                <span class="RewardText">'.$rwd['desc'].'</span>
                                <span class="RewardBtnText">Забрать</span>
                            </button>
                        </form>';
                    } else {
                        // Показываем ближайшую ожидаемую
                        $a .= '<span class="RefReward next""><img src="'.$rwd['img'].'" alt="prize"> <span class="RewardText">Будет на '.$lvl.' уровне</span></span>';
                    }
                    $shown = true;
                    break;
                }
            }
            $a .= '</div>'; // .RefRewardSingle

            $a .= '</div>'; // .TrainerInfo
            $a .= '</div>'; // .TrainerBlock
            $a .= '</div>'; // .User
        }
    } else {
        $a .= 'По вашему коду нет зарегистрированных тренеров.';
    }
    $a .= '</div>
    <div class="hr"></div>
    <div class="ReferalRewardInfo">
    <b>Актуальные награды:</b>
    <ul style="margin:0 0 10px 24px;padding:0;">
        <li><img src="/img/world/items/little/1.png" style="vertical-align:middle;width:20px;height:20px;"> 10 уровень — <b>300 000 генкаров</b></li>
        <li><img src="/img/world/items/little/448.png" style="vertical-align:middle;width:20px;height:20px;"> 15 уровень — <b>Премиум на 2 дня</b></li>
        <li><img src="/img/world/items/little/25.png" style="vertical-align:middle;width:20px;height:20px;"> 20 уровень — <b>50 аметистов</b></li>
    </ul>
    <span class="Gray-Color">Награды выдаются за достижение рефералом соответствующего уровня.<br>Кнопка <b>Забрать</b> появляется, когда награда доступна.</span>
    </div>
    </div>';
    $response['text'] = $a;
break;
    case 'weather':
        if($id == 1){
            $a = '<div class="Name">Обычная</div>
            <div class="About">Нет дополнительных эффектов</div>';
        }elseif($id == 2){
            $a = '<div class="Name">Солнечная</div>
            <div class="About">Мощность огненных атак увеличена на 50%. <br>Мощность водных атак уменьшена на 50%.<br>Покемона невозможно заморозить.<br>Солнечный луч и Солнечный меч не требуют хода заряда.</div>';
        }elseif($id == 3){
            $a = '<div class="Name">Дождь</div>
            <div class="About">Мощность водных атак увеличена на 50%. <br>Мощность огненных атак уменьшена на 50%.<br>Гроза имеет 100% точность.</div>';
        }elseif($id == 4){
            $a = '<div class="Name">Град</div>
            <div class="About">Все покемоны, кроме ледяных, получают урон 1/16hp каждый раунд.</div>';
        }elseif($id == 5){
            $a = '<div class="Name">Песчанная буря</div>
            <div class="About">Все покемоны, кроме каменных, стальных и земляных, получают урон 1/16hp каждый раунд.</div>';
        }
        $response['text'] = $a;
    break;
    case 'team':
        $bd_team = $mysqli->query('SELECT * FROM `user_pok_team` WHERE `user` = '.$_SESSION['id']);
        $pok_team = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `id` = '.$id)->fetch_assoc();
        $a = '<div class="Name">Команды покемонов</div><div class="ListTeam">';
        while($team = $bd_team->fetch_assoc()){
            if($team['id'] == $pok_team['team_id']){ $b = "selectTEAM";}else{ $b = "";}
            $a .= '<div><div onclick="selectTeam('.$team['id'].','.intval($id).');" class="team '.$b.'">'.$team['name'].'</div> <i class="fas fa-trash-alt" onclick="delTeamUser('.$team['id'].');"></i></div>';
        }
        $a .= '</div><div class="Buttons">
                          <input placeholder="Название команды"  type="nameTeam"/>
                          <div onclick="addTeam('.intval($id).', $(this).prev().val());">Создать команду</div>
                          <div onclick="delTeam('.intval($id).');" class="drop">Удалить из команды</div>
                        </div>';
        $response['text'] = $a;
    break;
    case 'sortProduct':
        if($id == 1){
            $response['text'] = '<div class="Name">Сортировка покемонов</div>
            <div class="About">
            <div class="butt sort s1" onclick="sortPr(1)">По цене</div>
            <div class="butt sort s2"  onclick="sortPr(2)">По уровню</div>
            <div class="butt sort s3"  onclick="sortPr(3)">По номеру</div>
            <div class="butt sort s4"  onclick="sortPr(4)">По дате</div>
            <b>Поиск</b><br>
            <label>Номер</label><input type="text" id="searchNumber" onkeydown="if(event.keyCode == 13){searchPr("numb",$(this).val());}"><br>
            <label>Характер</label><select size="1" id="searchCharacter">
  <option value="0">Все</option>
  <option value="1">Веселый</option>
  <option value="2">Выносливый</option>
  <option value="3">Застенчивый</option>
  <option value="4">Кроткий</option>
  <option value="5">Мирный</option>
  <option value="6">Мягкий</option>
  <option value="7">Наглый</option>
  <option value="8">Наивный</option>
  <option value="9">Нахальный</option>
  <option value="10">Нежный</option>
  <option value="11">Непослушный</option>
  <option value="12">Непреклонный</option>
  <option value="13">Обычный</option>
  <option value="14">Одинокий</option>
  <option value="15">Озорной</option>
  <option value="16">Осторожный</option>
  <option value="17">Поспешный</option>
  <option value="18">Причудливый</option>
  <option value="19">Распущенный</option>
  <option value="20">Робкий</option>
  <option value="21">Серьезный</option>
  <option value="22">Скромный</option>
  <option value="23">Смелый</option>
  <option value="24">Спокойный</option>
  <option value="25">Стремительный</option>
  <option value="26">Тихий</option>

</select><br>
            <label>Уровень</label><input type="text" id="searchLvl" onkeydown="if(event.keyCode == 13){searchPr("numb",$(this).val());}"><br>
            <label>Окрас</label><select size="1" id="searchOkras">
  <option value="0">Все</option>
  <option value="normal">Обычный</option>
  <option value="shine">Шайни</option>
</select><br>
            </div>';
        }else{
            $response['text'] = ' Сортировка инвентаря';
        }
    break;
  case 'shopitem':
    $it_b = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = ".$id)->fetch_assoc();
    $it_n = $mysqli->query("SELECT * FROM `items_npc` WHERE `item_id` = ".$id." AND `npc_id` = ".$other)->fetch_assoc();
    $response['text'] = '<div class="Name">'.$it_b['name'].'</div>
                        <div class="Image"><img src="/img/world/items/little/'.$id.'.png"></div>
                        <div class="About">'.$it_b['about'].' <br><span>Цена: <b>'.$it_n['item_price'].'</b></span></div>
                        <div class="Buttons">
                          <input placeholder="Количество" value="1" type="number"/>
                          <div onclick="ClassInfo._byItem('.intval($id).', '.$other.', $(this).prev().val());">Купить</div>
                        </div>';
  break;
case 'shop_it':
    // Получаем данные о предмете
    $it_b = $mysqli->query("SELECT * FROM `aquarits` WHERE `item` = " . intval($id))->fetch_assoc();
    $type = isset($it_b['type']) ? intval($it_b['type']) : 1;

    // По умолчанию — обычный товар
    $it_n = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = " . intval($id))->fetch_assoc();
    $name = htmlspecialchars($it_n['name']);
    $img = '/img/world/items/little/' . intval($id) . '.png';
    $about = htmlspecialchars($it_n['about']);
    $price = intval($it_b['price']);
    $sale_price = isset($it_b['sale_price']) && $it_b['sale_price'] !== null ? intval($it_b['sale_price']) : null;
    $show_price = $sale_price && $sale_price > 0 && $sale_price < $price ? $sale_price : $price;

    // Для скинов
if ($type == 3) {
    $name = !empty($it_b['name']) ? htmlspecialchars($it_b['name']) : htmlspecialchars($it_n['name']);
    $img = '/img/world/items/skins/' . intval($id) . '.png';
    // Берём описание из aquarits, если есть, иначе из base_items, иначе дефолт
    if (!empty($it_b['about'])) {
        $about = htmlspecialchars($it_b['about']);
    } elseif (!empty($it_n['about'])) {
        $about = htmlspecialchars($it_n['about']);
    } else {
        $about = 'Уникальный скин для вашего персонажа!';
    }
    $price = isset($it_b['price']) ? intval($it_b['price']) : 0;
    $sale_price = isset($it_b['sale_price']) && $it_b['sale_price'] !== null ? intval($it_b['sale_price']) : null;
    $show_price = ($sale_price && $sale_price > 0 && $sale_price < $price) ? $sale_price : $price;
}

    // Для набора
    if ($type == 4) {
        // Подключаем bundles.php для определения содержимого набора
        if (!isset($bundles)) {
            require_once($_SERVER['DOCUMENT_ROOT'].'/do/bundles.php'); // Укажи верный путь
        }
        $name = htmlspecialchars($it_b['name']);
        $img = '/img/world/items/packs/' . intval($id) . '.png';
        $about = !empty($it_b['about']) ? htmlspecialchars($it_b['about']) : '';
        $bundle_items = isset($bundles[$id]) ? $bundles[$id] : [];

        $about .= '<div class="PackContent">';
        if ($bundle_items && count($bundle_items) > 0) {
            foreach ($bundle_items as $item) {
                $base = $mysqli->query("SELECT name FROM base_items WHERE id = " . intval($item['item_id']))->fetch_assoc();
                $item_name = $base ? htmlspecialchars($base['name']) : '';
                $about .= '<div class="PackRow">
                    <img src="/img/world/items/little/' . $item['item_id'] . '.png" title="' . $item_name . '" class="pack-icon">
                    <span>x' . $item['count'] . ' ' . $item_name . '</span>
                </div>';
            }
        } else {
            $about .= '<div class="PackView-empty">Состав набора не определён.</div>';
        }
        $about .= '</div>';
        $price = intval($it_b['price']);
        $sale_price = isset($it_b['sale_price']) && $it_b['sale_price'] !== null ? intval($it_b['sale_price']) : null;
        $show_price = $sale_price && $sale_price > 0 && $sale_price < $price ? $sale_price : $price;
    }

    // --- Вывод информации о лимитах и покупках пользователя ---
    $limit_info = '';
    $user_limit = isset($it_b['user_limit']) ? (int)$it_b['user_limit'] : 0;
    $limit_period = isset($it_b['limit_period']) ? $it_b['limit_period'] : '';
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['id']) ? intval($_SESSION['id']) : 0);

    if ($user_limit > 0 && $user_id) {
        $period_sql = '';
        $period_text = '';
        if ($limit_period == 'once' || $limit_period == '' || $limit_period == NULL) {
            $period_sql = "";
            $period_text = "за всё время";
        } elseif ($limit_period == 'day') {
            $period_sql = "AND DATE(date) = CURDATE()";
            $period_text = "в сутки";
        } elseif ($limit_period == 'week') {
            $period_sql = "AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
            $period_text = "в неделю";
        } elseif ($limit_period == 'month') {
            $period_sql = "AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
            $period_text = "в месяц";
        } elseif ($limit_period == 'year') {
            $period_sql = "AND YEAR(date) = YEAR(CURDATE())";
            $period_text = "в год";
        } else {
            $period_text = "за период";
        }

        // Сколько уже куплено
        $sql = "SELECT IFNULL(SUM(count),0) FROM shop_log WHERE user_id = $user_id AND item_id = $id $period_sql";
        $already_bought = (int)$mysqli->query($sql)->fetch_row()[0];

        $can_buy = $user_limit - $already_bought;
        if ($can_buy < 0) $can_buy = 0;

        $limit_info = '<div class="limit-info" style="color:#607aad;font-size:14px;margin-bottom:7px;">'
            . 'Доступно для покупки: <b>' . $can_buy . '</b> шт. ' . $period_text . '<br>'
            . 'Уже куплено: <b>' . $already_bought . '</b> шт. ' . $period_text
            . '</div>';
    }

    // --- Выводим блок цены с поддержкой акционной ---
    $price_html = '';
    if ($sale_price && $sale_price > 0 && $sale_price < $price) {
        $price_html = '
        <div class="PackView-price">
            <span class="old-price" style="color:#a9a9a9;text-decoration:line-through;margin-right:9px;font-size:16px;">'.$price.' алм.</span>
            <span class="sale-label" style="background:#ffedd4;color:#ff9800;border-radius:4px;padding:2px 7px;font-size:13px;font-weight:500;margin-right:7px;">Акция!</span>
            <span class="price-label">Цена:</span> 
            <span class="price-value">'.$sale_price.' алм.</span>
        </div>';
    } else {
        $price_html = '
        <div class="PackView-price">
            <span class="price-label">Цена:</span> 
            <span class="price-value">'.$price.' алм.</span>
        </div>';
    }

    $response['text'] = '
        <div class="Name">' . $name . '</div>
        <div class="Image"><img src="' . $img . '"></div>
        <div class="About">' . $about . '</div>
        ' . $limit_info . '
        ' . $price_html . '
        <div class="Buttons PackView-controls">
            <input placeholder="Количество" value="1" min="1" type="number" class="PackView-count"/>
            <button class="PackView-buy" onclick="by_item_shop(' . intval($id) . ', $(this).prev().val());">Купить</button>
        </div>
    ';
    break;
    case 'skin_preview_inv':
    if (session_status() == PHP_SESSION_NONE) session_start();
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $user_id = intval($_SESSION['id']);
    $user_item_id = isset($_POST['item']) ? intval($_POST['item']) : 0;
    if (!$user_item_id) {
        echo json_encode(['success'=>false, 'error'=>'No item id']);
        exit;
    }

    // 1. По user_item_id ищем item_id в инвентаре пользователя
    $res = $mysqli->query("SELECT item_id FROM items_users WHERE id = {$user_item_id} AND user = {$user_id} LIMIT 1");
    if (!$res) {
        echo json_encode(['success'=>false, 'error'=>'SQL: '.$mysqli->error]);
        exit;
    }
    if (!$row = $res->fetch_assoc()) {
        echo json_encode(['success'=>false, 'error'=>'Inventory item not found']);
        exit;
    }
    $item_id = intval($row['item_id']);

    // 2. Получаем модель пользователя
    $res = $mysqli->query("SELECT model FROM cloth WHERE user = {$user_id} LIMIT 1");
    $user_model = 1;
    if ($res && $row = $res->fetch_assoc()) {
        $user_model = intval($row['model']);
    }

    // 3. Получаем данные по скину из base_items
    $res = $mysqli->query("SELECT skin_id, skin_color FROM base_items WHERE id = {$item_id} LIMIT 1");
    if (!$res) {
        echo json_encode(['success'=>false, 'error'=>'SQL: '.$mysqli->error]);
        exit;
    }
    if ($row = $res->fetch_assoc()) {
        $skin  = ($row['skin_id'] !== null && $row['skin_id'] !== '') ? $row['skin_id'] : $user_model;
        $color = ($row['skin_color'] !== null && $row['skin_color'] !== '') ? $row['skin_color'] : 'a';
    } else {
        echo json_encode(['success'=>false, 'error'=>'Skin not found']);
        exit;
    }

    // 4. Генерируем путь к аватару
    $func_path = $_SERVER['DOCUMENT_ROOT'].'/do/avatar_functions.php';
    if (!file_exists($func_path)) {
        echo json_encode(['success'=>false, 'error'=>'avatar_functions.php not found']);
        exit;
    }
    require_once($func_path);

    $cloth = [
        'model' => $user_model,
        'skin'  => $skin,
        'color' => $color
    ];

    if (!function_exists('getAvatarPath')) {
        echo json_encode(['success'=>false, 'error'=>'getAvatarPath not found']);
        exit;
    }

    $avatar_path = getAvatarPath($cloth);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'preview' => '/' . ltrim($avatar_path, '/')
    ]);
    exit;
case 'skin_preview':
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $user_id = $_SESSION['id'];
    
    // Получаем модель пользователя и определяем его пол по модели
    $res = $mysqli->query("SELECT model FROM cloth WHERE user = '{$user_id}' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $user_model = intval($row['model']);
        $user_sex = ($user_model >= 1 && $user_model <= 4) ? 'f' : 'm';
    } else {
        echo json_encode(['success'=>false, 'error'=>'User data not found']);
        exit;
    }

    $skin_item_id = isset($_POST['item']) ? intval($_POST['item']) : 0;
    $preview_mode = isset($_POST['mode']) ? $_POST['mode'] : 'auto';
    $force_model = isset($_POST['force_model']) ? intval($_POST['force_model']) : null;
    
    $skin = $user_model;
    $color = 'a';
    $is_compatible = true;
    $used_base_model = false;
    $preview_model = $user_model;

    // Получаем данные скина с информацией о поле
    $res = $mysqli->query("SELECT skin_id, skin_color, sex FROM base_items WHERE id = {$skin_item_id} LIMIT 1");
    if (!$res) {
        echo json_encode(['success'=>false, 'error'=>'SQL: '.$mysqli->error]);
        exit;
    }
    
    if ($row = $res->fetch_assoc()) {
        $skin_sex = isset($row['sex']) ? strtolower($row['sex']) : 'all';
        $skin_id = $row['skin_id'];
        $skin_color = $row['skin_color'];
        
        // Проверяем совместимость по полу
        if ($skin_sex && $skin_sex !== 'all' && $skin_sex !== $user_sex) {
            $is_compatible = false;
        }
        
        // Логика выбора скина и модели в зависимости от режима
        switch ($preview_mode) {
            case 'force_model':
                // Принудительно используем выбранную модель
                if ($force_model && $force_model >= 1 && $force_model <= 3) {
                    $preview_model = $force_model;
                    $skin = ($skin_id !== null && $skin_id !== '') ? $skin_id : $preview_model;
                    $color = ($skin_color !== null && $skin_color !== '') ? $skin_color : 'a';
                } else {
                    echo json_encode(['success'=>false, 'error'=>'Invalid model selection']);
                    exit;
                }
                break;
                
            case 'strict':
                if (!$is_compatible) {
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Скин не подходит вашему полу',
                        'skin_sex' => $skin_sex,
                        'user_sex' => $user_sex
                    ]);
                    exit;
                }
                $skin = ($skin_id !== null && $skin_id !== '') ? $skin_id : $user_model;
                $color = ($skin_color !== null && $skin_color !== '') ? $skin_color : 'a';
                break;
                
            case 'force_base':
                $skin = $user_model;
                $color = ($skin_color !== null && $skin_color !== '') ? $skin_color : 'a';
                $used_base_model = true;
                break;
                
            case 'auto':
            default:
                if (!$is_compatible) {
                    // Для несовместимых скинов НЕ используем базовую модель автоматически
                    // Возвращаем оригинальный скин с информацией о несовместимости
                    $skin = ($skin_id !== null && $skin_id !== '') ? $skin_id : $user_model;
                } else {
                    $skin = ($skin_id !== null && $skin_id !== '') ? $skin_id : $user_model;
                }
                $color = ($skin_color !== null && $skin_color !== '') ? $skin_color : 'a';
                break;
        }
    } else {
        echo json_encode(['success'=>false, 'error'=>'Skin not found']);
        exit;
    }

    $func_path = $_SERVER['DOCUMENT_ROOT'].'/do/avatar_functions.php';
    if (!file_exists($func_path)) {
        echo json_encode(['success'=>false, 'error'=>'avatar_functions.php not found']);
        exit;
    }
    require_once($func_path);

    $cloth = [
        'model' => $preview_model,  // Используем выбранную модель для предпросмотра
        'skin'  => $skin,
        'color' => $color
    ];

    if (!function_exists('getAvatarPath')) {
        echo json_encode(['success'=>false, 'error'=>'getAvatarPath not found']);
        exit;
    }

    // Определяем пол для функции getAvatarPath на основе модели предпросмотра
    $preview_sex = ($preview_model >= 1 && $preview_model <= 4) ? 'f' : 'm';
    $avatar_path = getAvatarPath($cloth, $preview_sex);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'preview' => '/' . $avatar_path,
        'skin_info' => [
            'skin_sex' => $skin_sex ?? 'unknown',
            'user_sex' => $user_sex,
            'is_compatible' => $is_compatible,
            'used_base_model' => $used_base_model,
            'preview_mode' => $preview_mode,
            'preview_model' => $preview_model,
            'user_model' => $user_model,
            'skin_id' => $skin,
            'color' => $color,
            'forced_model' => ($preview_mode === 'force_model')
        ]
    ]);
    exit;
case 'rol_pok':
   switch ($id){
     case 1:
      $a = '<div class="Name">Специальный атакер</div><div class="About"><span>Имеет хороший стат Специальной атаки</span>
        <span>Располагает разнообразным набором специальных атак</span>
        <span>В некоторых случаях имееет хороший стат Скорости</span>
        <span>Имеет атаки-бафы, повышающие Специальную атаку</span>
        <span>Может носить предметы, повышающие Специальную атаку или Скорость</span>
        </div>
      ';
      break;
      case 2:
      $a = '<div class="Name">Физический атакер</div><div class="About"><span>Имеет хороший стат Физической атаки</span>
        <span>Располагает разнообразным набором физических атак</span>
        <span>В некоторых случаях имееет хороший стат Скорости</span>
        <span>Имеет атаки-бафы, повышающие Физическую атаку</span>
        <span>Может носить предметы, повышающие Физическую атаку или Скорость</span>
        </div>
      ';
      break;
      case 3:
      $a = '<div class="Name">Универсальный атакер</div><div class="About"><span>Имеет хорошие статы Специальной и Физической атаки</span>
        <span>Располагает разнообразным набором специальных и физических атак</span>
        <span>В некоторых случаях имееет хороший стат Скорости</span>
        <span>Имеет атаки-бафы, повышающие Специальную атаку</span>
        <span>Может носить предметы, повышающие Специальную, Физическую атаку и/или другие статы вместе с ней</span>
        </div>
      ';
      break;
      case 4:
      $a = '<div class="Name">Вредитель</div><div class="About"><span>Имеет различные атаки-ловушки</span>
        <span>Имеет атаки для налоения отрицательных статусов противнику</span>
        </div>
      ';
     break;
     case 5:
      $a = '<div class="Name">Подрывник</div><div class="About"><span>Владеет приемами <b><i>Взрыв</i></b> или <b><i>Самоуничтожение</i></b></span>
        <span>В некоторых случаях имееет хороший стат Скорости</span>
        </div>
      ';
     break;
     case 6:
      $a = '<div class="Name">Эстафетчик</div><div class="About"><span>Владеет приемом <b><i>Эстафета</i></b></span>
        <span>В некоторых случаях имееет хороший стат Скорости</span>
        <span>Имеет атаки-бафы, повышающие различные статы для передачи союзнику</span>
        </div>
      ';
     break;
     case 7:
      $a = '<div class="Name">Физическая стена</div><div class="About"><span>Имеет хороший стат Физической защиты и HP</span>
        <span>Хорошо сочетается с ролями <b><i>Физический атакер</i></b> и <b><i>Вредитель</i></b></span>
        <span>Имеет атаки-бафы, повышающие Физическую защиту</span>
        <span>Может иметь атаки для исцеления HP</span>
        </div>
      ';
      break;
      case 8:
      $a = '<div class="Name">Специальная стена</div><div class="About"><span>Имеет хороший стат Специальной защиты и HP</span>
        <span>Хорошо сочетается с ролями <b><i>Специальный атакер</i></b> и <b><i>Вредитель</i></b></span>
        <span>Имеет атаки-бафы, повышающие Специальную защиту</span>
        <span>Может иметь атаки для исцеления HP</span>
        </div>
      ';
      break;
      case 9:
      $a = '<div class="Name">Танк</div><div class="About"><span>Имеет хороший стат Специальной защиты, Физической защиты и HP</span>
        <span>Хорошо сочетается с ролями <b><i>Специальный атакер</i></b>, <b><i>Физический атакер</i></b> и <b><i>Вредитель</i></b></span>
        <span>Имеет атаки-бафы, повышающие Специальную и/или Физическую защиту</span>
        <span>Может иметь атаки для исцеления HP</span>
        </div>
      ';
      break;
      case 10:
      $a = '<div class="Name">Экранщик</div><div class="About"><span>Имеет воможность выставить <b><i>Экран света</i></b> или <b><i>Отражение</i></b></span>
        <span>Хорошо сочетается с ролями <b><i>Физическая стена</i></b> или <b><i>Танк</i></b></span>
        </div>
      ';
      break;
      case 11:
      $a = '<div class="Name">Универсальный экранщик</div><div class="About"><span>Имеет воможность выставить <b><i>Экран света</i></b> и/или <b><i>Отражение</i></b></span>
        <span>Хорошо сочетается с ролями <b><i>Физическая стена</i></b> или <b><i>Танк</i></b></span>
        </div>
      ';
      break;
      case 12:
      $a = '<div class="Name">Помощник</div><div class="About"><span>Располагает разнообразным набором атак для защиты союзников от отрицательных статусов</span>
        <span>Имеет воможность убирать с поля боя ловушки и/или экраны</span>
        <span>Имеет возможность менять погоду</span>
        </div>
      ';
      break;
      case 13:
      $a = '<div class="Name">Помощник</div><div class="About"><span>Располагает разнообразным набором атак для защиты союзников от отрицательных статусов</span>
        <span>Имеет воможность убирать с поля боя ловушки и/или экраны</span>
        <span>Имеет возможность менять погоду</span>
        </div>
      ';
      break;
      case 14:
      $a = '<div class="Name">Синоптик</div><div class="About"><span>Располагает разнообразным набором атак для смены погодных условий</span>
        </div>
      ';
      break;
      case 15:
      $a = '<div class="Name">Убийца</div><div class="About"><span>Имеет HitOneKo атаки</span>
      <span>В некоторых случаях имееет хороший стат Скорости</span>
      <span>Хорошо сочетается с ролями <b><i>Специальная стена</i></b>, <b><i>Физическая стена</i></b> и <b><i>Танк</i></b></span>
        </div>
      ';
      break;
   }
    $response['text'] = $a;
  break;
  case 'questChest':
   switch ($id){
     case 1:
      $a = '<div class="Name">Начало путешествия</div><div class="Conditions"><ol><li>Стартовый покемон на выбор</li> <li>Генкар х10.000</li> <li>Покебол х15</li><li>Опыт х500</li></ol></div>';
     break;
     case 2:
      $a = '<div class="Name">Мастер эволюции</div><div class="Conditions"><ol><li>Рандомные осколки камней х5</li> <li>Опыт х500</li></ol></div>';
     break;
     case 3:
      $a = '<div class="Name">Мастер боев</div><div class="Conditions"><ol><li>Рандомный стабовый усилитель х2</li> <li>Опыт х500</li></ol></div>';
     break;
     case 4:
      $a = '<div class="Name">Потерянный цветок</div><div class="Conditions"><ol><li>Желтая конфета х10</li><li>Фиолетовая конфета х10</li> <li>Опыт х500</li></ol></div>';
     break;
     case 5:
      $a = '<div class="Name">Помощь Виоле</div><div class="Conditions"><ol><li>Портативный инкубатор х3</li><li>Серебряная пыль х1</li><li>Генкар х100.000</li> <li>Опыт х800</li></ol></div>';
     break;
     case 6:
      $a = '<div class="Name">Мальчик с душой Артикуно</div><div class="Conditions"><ol><li>Бриллиантовый покебол х5</li><li>Коробка с окаменелостями х1</li><li>Загадочный покебол х1</li><li>Монета х100.000</li> <li>Опыт х1.500</li></ol></div>';
     break;
     case 7:
      $a = '<div class="Name">Археологические исследования</div><div class="Conditions"><ol><li>Коробка с окаменелостями х1</li> <li>Опыт х500</li></ol></div>';
     break;
     case 8:
      $a = '<div class="Name">Призрак старого дома</div><div class="Conditions"><ol><li>Опыт х800</li></ol></div>';
     break;
     case 9:
      $a = '<div class="Name">Проблема на ипподроме</div><div class="Conditions"><ol><li>Опыт х700</li></ol></div>';
     break;
     case 10:
      $a = '<div class="Name">Старинные рукописи</div><div class="Conditions"><ol><li>Корень априкорнов х1</li><li>Обучение мега-эволюции</li> <li>Рандомный мега камень х1</li><li>Опыт х700</li></ol></div>';
     break;
     case 11:
      $a = '<div class="Name">Лаборатория мисс Дженни</div><div class="Conditions"><ol><li>Обучение крафту пилюли</li><li>Капсула х6</li> <li>Лунный камень х1</li><li>Опыт х500</li></ol></div>';
     break;
     case 12:
      $a = '<div class="Name">Хлопок</div><div class="Conditions"><ol><li>Билет на корабль Калос-Хоенн х1</li><li>Монета х150.000</li> <li>Поглощающая лампа х1</li><li>Приманка х1</li><li>Опыт х800</li></ol></div>';
     break;
     case 33:
      $a = '<div class="Name">Закрытый пляж</div><div class="Conditions"><ol><li>Монета х50.000</li><li>Защитные очки х1</li> <li>Амулет х1</li><li>Старая удочка х1</li><li>Опыт х1.000</li></ol></div>';
     break;
     case 41:
      $a = '<div class="Name">Важная вещь</div><div class="Conditions"><ol><li>Монета х100.000</li><li>Сладкий кекс х2</li><li>Опыт х600</li></ol></div>';
     break;
     case 15:
      $a = '<div class="Name">Рыбалка - это жизнь</div><div class="Conditions"><ol><li>Спинниг х1</li><li>Опыт х700</li></ol></div>';
     break;
   }
    $response['text'] = $a;
  break;
  case 'char':
  $a = '
  <div class="Name">Информация о характерах</div>
  <div class="Conditions">
  <div class="har">Веселый</div>        <div class="up st">С</div>  <div class="low st">СА</div>  <div class="up fd">Сладкое</div>  <div class="low fd">Сухое</div><br>
  <div class="har">Мирный</div>         <div class="up st">СЗ</div> <div class="low st">А</div>   <div class="up fd">Горькое</div>  <div class="low fd">Острое</div><br>
  <div class="har">Мягкий</div>         <div class="up st">СА</div> <div class="low st">З</div>   <div class="up fd">Сухое</div>    <div class="low fd">Солёное</div><br>
  <div class="har">Наглый</div>         <div class="up st">З</div>  <div class="low st">А</div>   <div class="up fd">Солёное</div>  <div class="low fd">Острое</div><br>
  <div class="har">Наивный</div>        <div class="up st">С</div>  <div class="low st">СЗ</div>  <div class="up fd">Сладкое</div>  <div class="low fd">Горькое</div><br>
  <div class="har">Нахальный</div>      <div class="up st">СЗ</div> <div class="low st">С</div>   <div class="up fd">Горькое</div>  <div class="low fd">Сладкое</div><br>
  <div class="har">Нежный</div>         <div class="up st">СЗ</div> <div class="low st">З</div>   <div class="up fd">Горькое</div>  <div class="low fd">Солёное</div><br>
  <div class="har">Непослушный</div>    <div class="up st">А</div>  <div class="low st">СЗ</div>  <div class="up fd">Острое</div>   <div class="low fd">Горькое</div><br>
  <div class="har">Непреклонный</div>   <div class="up st">А</div>  <div class="low st">СА</div>  <div class="up fd">Острое</div>   <div class="low fd">Сухое</div><br>
  <div class="har">Одинокий</div>       <div class="up st">А</div>  <div class="low st">З</div>   <div class="up fd">Острое</div>   <div class="low fd">Солёное</div><br>
  <div class="har">Озорной</div>        <div class="up st">З</div>  <div class="low st">СА</div>  <div class="up fd">Солёное</div>  <div class="low fd">Сухое</div><br>
  <div class="har">Осторожный</div>     <div class="up st">СЗ</div> <div class="low st">СА</div>  <div class="up fd">Горькое</div>  <div class="low fd">Сухое</div><br>
  <div class="har">Поспешный</div>      <div class="up st">С</div>  <div class="low st">З</div>   <div class="up fd">Сладкое</div>  <div class="low fd">Солёное</div><br>
  <div class="har">Распущенный</div>    <div class="up st">З</div>  <div class="low st">СА</div>  <div class="up fd">Солёное</div>  <div class="low fd">Горькое</div><br>
  <div class="har">Робкий</div>         <div class="up st">С</div>  <div class="low st">А</div>   <div class="up fd">Сладкое</div>  <div class="low fd">Острое</div><br>
  <div class="har">Скромный</div>       <div class="up st">СА</div> <div class="low st">А</div>   <div class="up fd">Сухое</div>    <div class="low fd">Острое</div><br>
  <div class="har">Смелый</div>         <div class="up st">А</div>  <div class="low st">С</div>   <div class="up fd">Острое</div>   <div class="low fd">Сладкое</div><br>
  <div class="har">Спокойный</div>      <div class="up st">З</div>  <div class="low st">С</div>   <div class="up fd">Солёное</div>  <div class="low fd">Сладкое</div><br>
  <div class="har">Стремительный</div>  <div class="up st">СА</div> <div class="low st">СЗ</div>  <div class="up fd">Сухое</div>    <div class="low fd">Горькое</div><br>
  <div class="har">Тихий</div>          <div class="up st">СА</div> <div class="low st">С</div>   <div class="up fd">Сухое</div>    <div class="low fd">Сладкое</div><br>
  Нейтральные: <div class="har">Выносливый</div><div class="har">Застенчивый</div> <div class="har">Кроткий</div> <div class="har">Обычный</div> <div class="har">Серьезный</div> <div class="har">Причудливый</div>
  </div>
  ';
  $response['text'] = $a;
  break;

  case 'baf':
    // Безопасное получение id и защита от SQL-инъекции
    $baf_id = intval($id);

    // Получаем баф пользователя
    $baf = $mysqli->query("SELECT * FROM `bafs` WHERE `baf` = '".$baf_id."' AND `user` = ".$_SESSION['id'])->fetch_assoc();

    if($baf) {
      $time = time();
      // Получаем данные о предмете-бафе
      $item = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = '".$baf['baf']."'")->fetch_assoc();

      ob_start();
      ?>
      <div class="BuffCard">
        <div class="BuffCard-header">
          <div class="BuffCard-title"><?=htmlspecialchars($item['name'])?></div>
          <div class="BuffCard-timer">
            <?php if($time < $baf['time']): ?>
              <span class="BuffCard-active">
                Активен до <b><?=date('H:i d.m.Y', $baf['time'])?></b>
                (ещё <?=gmdate('H:i:s', $baf['time'] - $time)?>)
              </span>
            <?php else: ?>
              <span class="BuffCard-expired">Эффект закончился</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="BuffCard-image">
          <img src="/img/world/items/little/<?=intval($item['id'])?>.png" alt="<?=htmlspecialchars($item['name'])?>">
        </div>
        <div class="BuffCard-about">
          <?=nl2br(htmlspecialchars($item['about']))?>
        </div>
        <?php if(!empty($item['bonus'])): ?>
          <div class="BuffCard-bonus">
            <b>Бонус:</b> <?=htmlspecialchars($item['bonus'])?>
          </div>
        <?php endif; ?>
        <?php if($time < $baf['time']): ?>
          <div class="BuffCard-actions">
            <button class="Button BuffCard-close" onclick="$('.BuffCard').fadeOut(200);">Закрыть</button>
          </div>
        <?php endif; ?>
      </div>
      <?php
      $a = ob_get_clean();
    } else {
      $a = '<div class="BuffCard"><div class="BuffCard-expired">Баф не найден.</div></div>';
    }
    $response['text'] = $a;
  break;
		case 'item':
			$item = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = '".$id."'")->fetch_assoc();
      if($item['id'] >= 1000001) {
        $mesto = explode(',',$item['info']);
        $img = $mesto[0].'.'.$mesto[1];
      }else{
        $img = $item['id'];
      }
			$a .= '
			<div class="Name">'.$item['name'].'</div>
			<div class="Image"><img id="imgItem" src="/img/world/items/little/'.$img.'.png"></div>
			<div class="About">'.$item['about'].'<br><br><i class="far fa-map-marker-alt"></i> <b>Где найти:</b> <br>'.$item['drop_it'].'</div>
			';
			$response['text'] = $a;
		break;
		case 'lot_stavk':
    $lot   = $mysqli->query("SELECT * FROM `log_lombard` WHERE `id` = '".intval($id)."'")->fetch_assoc();
    $stavk = $mysqli->query("SELECT * FROM `log_lombard_bidcounter` WHERE `lotID` = '".intval($lot['id'])."' ");
    $v     = $stavk ? $stavk->num_rows : 0;

    // Человекочитаемая «осталось»
    $leftTxt = '—';
    if (!empty($lot['dateEnd']) && intval($lot['dateEnd']) > time()) {
        $diff = intval($lot['dateEnd']) - time();
        $h = intdiv($diff, 3600);
        $m = intdiv($diff % 3600, 60);
        $s = $diff % 60;
        $leftTxt = ($h > 0) ? ($h.'ч '.$m.'м') : ($m.'м '.$s.'с');
    }

    $priceNow  = (int)$lot['priceNow'];
    $priceStep = (int)$lot['priceStep'];
    $priceBuy  = (int)$lot['priceBuy'];
    $cer       = $priceNow + $priceStep;

    // Последний покупатель (по текущим данным лота)
    $lastBidder = null;
    if ($v > 0 && !empty($lot['userBuy'])) {
        $lastBidder = $mysqli->query("SELECT `login`,`id`,`sex`,`user_group` FROM `users` WHERE `id` = ".intval($lot['userBuy']))->fetch_assoc();
    }

    // Подключаем стили 1 раз
    if (empty($GLOBALS['__mkx_bid_css'])) {
        $GLOBALS['__mkx_bid_css'] = true;
        $a .= '
<style id="mkx-bid-css">
  .mkx-bid{--bd:#e6eafe;--txt:#1b2b4f;--mut:#6f7b95;--bg:#fff;--chip:#f6f8ff;--brand:#2f74ff;--brand2:#0e55b6}
  .mkx-bid{background:var(--bg);border:1px solid var(--bd);border-radius:14px;padding:10px;box-shadow:0 14px 34px rgba(23,35,74,.10);font-family:Nunito,Inter,Arial,sans-serif;color:var(--txt)}
  .mkx-bid .mkx-head{display:flex;gap:10px;align-items:center;margin-bottom:8px}
  .mkx-bid .mkx-ico{width:32px;height:32px;border-radius:9px;border:1px solid var(--bd);display:grid;place-items:center;background:linear-gradient(180deg,#fff,#f8faff)}
  .mkx-bid .mkx-title{font-weight:900;line-height:1.1}
  .mkx-bid .mkx-sub{font-size:12px;color:var(--mut);font-weight:800}
  .mkx-bid .mkx-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:8px 0}
  .mkx-bid .mkx-card{border:1px solid var(--bd);background:var(--chip);border-radius:12px;padding:8px}
  .mkx-bid .mkx-card .t{font-size:11px;color:var(--mut);margin-bottom:2px}
  .mkx-bid .mkx-card .v{font-weight:900}
  .mkx-bid .mkx-last{border-top:1px dashed var(--bd);padding-top:8px;margin-top:4px;font-size:13px}
  .mkx-bid .mkx-last .user-link{display:inline-flex;align-items:center;gap:6px;vertical-align:middle}
  .mkx-bid .mkx-act{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px}
  .mkx-bid .mkx-input{flex:1 1 160px;display:flex;align-items:center;gap:6px;border:1px solid var(--bd);border-radius:10px;padding:6px 8px;background:#fff}
  .mkx-bid .mkx-input input{width:100%;border:0;outline:0;background:transparent;font-weight:900}
  .mkx-bid .mkx-hint{font-size:11px;color:var(--mut)}
  .mkx-bid .mkx-btn{border:1px solid var(--brand);background:linear-gradient(180deg,var(--brand),var(--brand2));border-radius:10px;color:#fff;font-weight:900;padding:8px 12px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
  .mkx-bid .mkx-btn i{font-size:14px}
  .mkx-bid .mkx-link{border:1px solid var(--bd);background:#fff;border-radius:10px;padding:8px 10px;cursor:pointer;font-weight:800}
  @media (max-width:560px){ .mkx-bid .mkx-cards{grid-template-columns:1fr} }
  /* Совместимость со старой обёрткой */
  .Buttons .mkx-btn{margin:0}
</style>';
    }

    // Шапка (оставляем .Name как у тебя, чтобы не ломать внешние стили)
    $a .= '<div class="Name">'.htmlspecialchars($lot['name']).'</div>';

    // Карточка
    $a .= '<div class="mkx-bid">
      <div class="mkx-head">
        <div class="mkx-ico"><i class="far fa-gavel"></i></div>
        <div>
          <div class="mkx-title">Сделайте ставку</div>
          <div class="mkx-sub">Лот #'.intval($lot['id']).' • Осталось: '.$leftTxt.'</div>
        </div>
      </div>

      <div class="mkx-cards">
        <div class="mkx-card">
          <div class="t">Текущая ставка</div>
          <div class="v">'.($priceNow ? number_format($priceNow,0,'.',' ').' м.' : '—').'</div>
        </div>
        <div class="mkx-card">
          <div class="t">Мин. новая ставка</div>
          <div class="v">'.number_format($cer,0,'.',' ').' м.</div>
        </div>
        <div class="mkx-card">
          <div class="t">Шаг</div>
          <div class="v">'.number_format($priceStep,0,'.',' ').' м.</div>
        </div>
        <div class="mkx-card">
          <div class="t">Выкуп</div>
          <div class="v">'.($priceBuy ? ('<b>'.number_format($priceBuy,0,'.',' ').' м.</b>') : '—').'</div>
        </div>
      </div>';

    // Блок "последняя ставка"
    if ($v > 0 && $lastBidder) {
        $a .= '<div class="mkx-last">
          Последняя ставка:
          <span class="user-link">
            <div onclick=showUserTooltip("'.$lastBidder['id'].'") class="Info-Link sex'.intval($lastBidder['sex']).'">
              <i class="fas fa-info"></i>
            </div>
            <div class="u-'.intval($lastBidder['user_group']).' label" onclick=user_to_chat_add("'.$lastBidder['id'].'")>'.
              htmlspecialchars($lastBidder['login']).'
            </div>
          </span>
          <span class="mkx-hint"> • Всего ставок: '.$v.'</span>
        </div>';
    } else {
        $a .= '<div class="mkx-last">Ставок ещё нет</div>';
    }

    // Поле ввода + кнопки (сохраняем id и вызов lotstavka)
    $a .= '<div class="mkx-act">
        <label class="mkx-input" for="countStavkInput">
          <i class="far fa-coins"></i>
          <input id="countStavkInput" type="number" min="'.($cer).'" step="'.max(1,$priceStep).'" value="'.($cer).'" placeholder="Сумма ставки">
        </label>
        <button class="mkx-btn" onclick="lotstavka('.intval($lot['id']).');"><i class="far fa-gavel"></i> Сделать ставку</button>
        <button class="mkx-link" onclick="if(window.issetAll){ issetAll('.intval($lot['id']).',\'bidcounter\'); }">История ставок</button>
        <div class="mkx-hint">Новая ставка должна быть не ниже '.number_format($cer,0,'.',' ').' м.</div>
      </div>
    </div>

    <script>
      (function(){
        var el = document.getElementById("countStavkInput");
        if(!el) return;
        el.addEventListener("focus", function(){ this.select(); });
        el.addEventListener("keydown", function(e){
          if(e.key === "Enter"){
            e.preventDefault();
            try{ lotstavka('.intval($lot['id']).'); }catch(_){}
          }
        });
      })();
    </script>';

    $response['text'] = $a;
    break;

	case 'pok_lot':
	    $pok_bd = $mysqli->query("SELECT `id`,`name_new` FROM `user_pokemons` WHERE `id` = ".$id)->fetch_assoc();
	    $a .= '<div class="Name">Выставить '.$pok_bd['name_new'].'</div>
	    <div class="divAddLot">
	        <span class="inpt">Стартовая цена:</span><input id="lot_priceStart" type="number" pattern="[0-9]*"> м.<br>
	        <span class="inpt">Шаг:</span><input id="lot_priceHod" type="number" pattern="[0-9]*"> м.<br>
	        <span class="inpt">Срок, дней:</span><input id="lot_day" value="3" type="number" pattern="[0-9]*"><br>
	        <span class="inpt">Цена выкупа:</span><input id="lot_priceBuy" type="number" pattern="[0-9]*" placeholder="(не обязательно)"> м.<br>
	        <span class="inpt">Продвижение:</span><input id="lot_prodv" type="number" pattern="[0-9]*" placeholder="(1 если надо)"><br>
	        <small>Продвижение позволяет показывать ваш лот выше остальных и подсвечивать зеленым. Стоимость этого 25.000 генкар.</small>
	        <div><div class="button" onclick="addLotPokemon('.$id.')">Добавить лот</div></div>
	        <div class="hr"></div>
	        <div class="divLotAddPrice">Стоимость добавления лота: <b>40.000 э.</b></div>
	    </div>
	    
	    
	    ';
			$response['text'] = $a;
	break;
	case 'item_lot':
	    $it = $mysqli->query("SELECT * FROM `items_users` WHERE `id` = ".$id)->fetch_assoc();
	    $it_bd = $mysqli->query("SELECT `id`,`name` FROM `base_items` WHERE `id` = ".$it['item_id'])->fetch_assoc();
	    $a .= '<div class="Name">Выставить '.$it_bd['name'].'</div>
	    <div class="divAddLot">
	        <span class="inpt">Количество:</span><input id="lot_count" type="number" pattern="[0-9]*"> <br>
	        <span class="inpt">Стартовая цена:</span><input id="lot_priceStart" type="number" pattern="[0-9]*"> м.<br>
	        <span class="inpt">Шаг:</span><input id="lot_priceHod" type="number" pattern="[0-9]*"> м.<br>
	        <span class="inpt">Срок, дней:</span><input id="lot_day" value="3" type="number" pattern="[0-9]*"><br>
	        <span class="inpt">Цена выкупа:</span><input id="lot_priceBuy" type="number" pattern="[0-9]*" placeholder="(не обязательно)"> м.<br>
	        <span class="inpt">Продвижение:</span><input id="lot_prodv" type="number" pattern="[0-9]*" placeholder="(1 если надо)"><br>
	        <small>Продвижение позволяет показывать ваш лот выше остальных и подсвечивать зеленым. Стоимость этого 25.000 генкар.</small>
	        <div><div class="button" onclick="addLotItem('.$id.')">Добавить лот</div></div>
	        <div class="hr"></div>
	        <div class="divLotAddPrice">Стоимость добавления лота: <b>40.000 э.</b></div>
	    </div>
	    
	    
	    ';
			$response['text'] = $a;
	break;
    case 'bidcounter':
        $item = $mysqli->query("SELECT * FROM `log_lombard_bidcounter` WHERE `lotID` = '".$id."' ORDER BY `id` DESC");
        $a .= '
			<div class="Name">История ставок lot'.$id.'</div>
			<div class="AucHistory">
			    ';
while ($it = $item->fetch_assoc()){
    $user_bd = $mysqli->query("SELECT `login`,`id`,`sex`,`user_group` FROM `users` WHERE `id` = ".$it['user'])->fetch_assoc();
            $a .= '<div><div class="aucdate">'.$it['date'].'</div> <span><div class="user-link">
                                        <div onclick=showUserTooltip("'.$user_bd['id'].'") class="Info-Link sex'.$user_bd['sex'].'">
                                            <i class="fas fa-info"></i>
                                        </div> 
                                        <div class="u-'.$user_bd['user_group'].' label" onclick=user_to_chat_add("'.$user_bd['id'].'")>
                                            '.$user_bd['login'].'
                                        </div>
                                    </div> '.number_format($it['money'],0,'.','.').' м.</span></div>';
          }			    
			    
		$a .= '	</div>
			';
			$response['text'] = $a;
    break;
    case 'dex':
    // ---------- конфигурация карточек ----------
    $cards = [];

    // Admin карточки
    if (!empty($_SESSION['id']) && (int)$_SESSION['id'] === 4) {
        $cards[] = ['act'=>"openModal('craft')",      'icon'=>'fas fa-hammer',        'title'=>'Крафт',      'sub'=>'Рецепты и сборка','badge'=>'admin'];
        $cards[] = ['act'=>"openModal('battlepass')", 'icon'=>'fas fa-ticket-alt',    'title'=>'Пропуск',    'sub'=>'Сезонные награды','badge'=>'admin'];
        $cards[] = ['act'=>"openModal('discovery')",  'icon'=>'fas fa-binoculars',    'title'=>'Поиск',      'sub'=>'Предметы и лут','badge'=>'admin'];
    }

    // Основные карточки
    $cards = array_merge($cards, [
        ['act'=>"openDex(0)",                       'icon'=>'fa fa-book',            'title'=>'Покедекс',           'sub'=>'Виды и информация'],
        ['act'=>"showTrainerCollectionSwitch()",    'icon'=>'fas fa-user-astronaut', 'title'=>'Коллекция тренера',  'sub'=>'Достижения и сеты'],
        ['act'=>"openModal('rules')",               'icon'=>'fas fa-scroll',         'title'=>'Правила',            'sub'=>'Основные положения'],
        ['act'=>"openModal('map')",                 'icon'=>'fas fa-map',            'title'=>'Карта',              'sub'=>'Локации и перемещения'],
        ['act'=>"openModal('forum')",               'icon'=>'fas fa-atlas',          'title'=>'Форум',              'sub'=>'Общение и объявления'],
        ['act'=>"openModal('atcdex')",              'icon'=>'fas fa-fist-raised',    'title'=>'Атакдекс',           'sub'=>'Все атаки и эффекты'],
        ['act'=>"openModal('abldex')",              'icon'=>'fas fa-sparkles',       'title'=>'Способности',        'sub'=>'Полный список'],
    ]);

    // ---------- сборка карточек ----------
    $list = '';
    foreach ($cards as $c) {
        $badge = !empty($c['badge']) ? '<span class="hx-badge">'.$c['badge'].'</span>' : '';
        $onclick = htmlspecialchars("pkxHelperClose(); ".$c['act'].";");
        $list .= '
        <button class="hx-item" onclick="'.$onclick.'">
          <span class="hx-ico"><i class="'.htmlspecialchars($c['icon']).'"></i></span>
          <span class="hx-text">
            <span class="hx-ttl">'.htmlspecialchars($c['title']).$badge.'</span>
            <span class="hx-sub">'.htmlspecialchars($c['sub']).'</span>
          </span>
        </button>';
    }

    // ---------- CSS + шаблон + JS ----------
    $a = '
    <style id="pkx-helper-panel-css">
      .pkx-helper-panel{position:fixed;z-index:1000002;left:50%;bottom:24px;transform:translateX(-50%);
        width:340px;max-width:92vw;background:#fff;border:1px solid #e6eafe;border-radius:16px;
        box-shadow:0 18px 42px rgba(23,35,74,.16);font-family:Nunito,Inter,Arial,sans-serif;color:#1b2b4f}
      .pkx-helper-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;
        background:linear-gradient(180deg,#ffffff,#f5f7ff);border-bottom:1px solid #e6eafe;border-top-left-radius:16px;border-top-right-radius:16px;cursor:grab}
      .pkx-helper-title{font-weight:300;font-size:15px}
      .pkx-helper-close{appearance:none;border:0;background:#fff;border:1px solid #e6eafe;width:28px;height:28px;
        border-radius:8px;cursor:pointer;line-height:26px;font-size:16px;color:#6f7b95;touch-action:manipulation}
      .pkx-helper-close:hover{box-shadow:0 6px 18px rgba(23,35,74,.12)}
      .pkx-helper-body{padding:8px 10px}
      .hx-scroll{max-height:58vh;overflow:auto}
      .hx-list{display:flex;flex-direction:column;gap:8px}
      .hx-item{display:flex;align-items:center;gap:10px;width:100%;text-align:left;padding:8px 10px;
        background:#fff;border:1px solid #e6eafe;border-radius:12px;cursor:pointer}
      .hx-item:hover{border-color:#2f74ff;box-shadow:0 10px 22px rgba(46,116,255,.14)}
      .hx-ico{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;background:#eef3ff;border:1px solid #dbe6ff;color:#0e55b6}
      .hx-text{display:flex;flex-direction:column}
      .hx-ttl{font-weight:300;font-size:14px;line-height:1.1}
      .hx-sub{font-size:12px;color:#6f7b95;font-weight:300;margin-top:2px}
      .hx-badge{margin-left:8px;padding:1px 6px;border-radius:999px;background:#fff6e6;border:1px solid #ffd9a8;
        font-size:10px;font-weight:300;color:#a05a00}
      @media (max-width:560px){
        .pkx-helper-panel{bottom:10px;width:94vw}
        .hx-scroll{max-height:62vh}
      }
    </style>

    <template id="pkx-helper-tpl">
      <div class="pkx-helper-panel" role="dialog" aria-modal="false">
        <div class="pkx-helper-head">
          <div class="pkx-helper-title">Помощник</div>
          <button class="pkx-helper-close" data-nodrag="1" aria-label="Закрыть">×</button>
        </div>
        <div class="pkx-helper-body">
          <div class="hx-scroll">
            <div class="hx-list">'.$list.'</div>
          </div>
        </div>
      </div>
    </template>

    <script>
    (function(){
      // Глобальная функция закрытия
      window.pkxHelperClose = function(){
        var p = document.querySelector(".pkx-helper-panel");
        if (!p) return;
        p.remove();
        document.removeEventListener("keydown", escClose, true);
      };

      function escClose(e){ if(e.key === "Escape") pkxHelperClose(); }

      // Скрыть tooltip, если он есть
      var tip = document.querySelector(".tooltip"); if (tip) tip.style.display="none";

      // Монтируем панель единоразово
      if (!document.querySelector(".pkx-helper-panel")){
        var t = document.getElementById("pkx-helper-tpl");
        if (t && t.content){
          var node = document.importNode(t.content,true);
          document.body.appendChild(node);
        }
      }
      var panel = document.querySelector(".pkx-helper-panel");
      if (!panel) return;

      // --- Кнопка закрытия: поддержка мобильных (tap) ---
      var closeBtn = panel.querySelector(".pkx-helper-close");
      var closeHandler = function(ev){ ev.preventDefault(); ev.stopPropagation(); pkxHelperClose(); };
      closeBtn.addEventListener("click", closeHandler, {passive:false});
      closeBtn.addEventListener("touchend", closeHandler, {passive:false});
      closeBtn.addEventListener("pointerup", closeHandler, {passive:false});

      // Закрытие по ESC
      document.addEventListener("keydown", escClose, true);

      // --- Перетаскивание за шапку (не цепляемся за крестик) ---
      (function(){
        var head = panel.querySelector(".pkx-helper-head"); if(!head) return;
        var dx=0, dy=0, dragging=false;

        function start(e){
          // Если начали с крестика — не стартуем drag
          var target = e.target;
          if (target && (target.closest(".pkx-helper-close") || target.dataset.nodrag === "1")) { return; }
          dragging=true;
          var r = panel.getBoundingClientRect();
          var pt = (e.touches? e.touches[0] : e);
          var x = pt.clientX, y = pt.clientY;
          dx = x - r.left; dy = y - r.top;
          document.addEventListener("mousemove", move);
          document.addEventListener("mouseup", stop);
          document.addEventListener("touchmove", move, {passive:false});
          document.addEventListener("touchend", stop);
          e.preventDefault(); // предотвращаем скролл только когда реально начинаем drag
        }
        function move(e){
          if(!dragging) return;
          var vw = window.innerWidth, vh = window.innerHeight;
          var pt = (e.touches? e.touches[0] : e);
          var x = pt.clientX, y = pt.clientY;
          var left = Math.min(Math.max(6, x-dx), vw- panel.offsetWidth - 6);
          var top  = Math.min(Math.max(6, y-dy), vh- 40);
          panel.style.left = left + "px";
          panel.style.bottom = "auto";
          panel.style.top  = top + "px";
          panel.style.transform = "none";
        }
        function stop(){
          dragging=false;
          document.removeEventListener("mousemove", move);
          document.removeEventListener("mouseup", stop);
          document.removeEventListener("touchmove", move);
          document.removeEventListener("touchend", stop);
        }
        head.addEventListener("mousedown", start);
        head.addEventListener("touchstart", start, {passive:false});
      })();

      // ------- Авто-закрытие при вызове других UI-функций -------
      function patchClose(name){
        var fn = window[name];
        if (typeof fn !== "function" || fn.__pkxPatched) return;
        var wrapped = function(){ try{ pkxHelperClose(); }catch(_){} return fn.apply(this, arguments); };
        wrapped.__pkxPatched = true; window[name] = wrapped;
      }
      ["openModal","openDex","showTrainerCollectionSwitch"].forEach(patchClose);
    })();
    </script>';

    $response['html'] = $a;
    $response['text'] = $a;
    break;


    case 'atcdex':
    $a = '<div class="Name">Сортировка по типу</div>
    <div class="About">
      <div class="TypeCenter">
        <div class="btnType typenormal" onclick="catatcdex(\'normal\')">Нормальный</div>
        <div class="btnType typefighting" onclick="catatcdex(\'fighting\')">Боевой</div>
        <div class="btnType typefly" onclick="catatcdex(\'fly\')">Летающий</div>
        <div class="btnType typepoison" onclick="catatcdex(\'poison\')">Ядовитый</div>
        <div class="btnType typeground" onclick="catatcdex(\'ground\')">Земляной</div>
        <div class="btnType typerock" onclick="catatcdex(\'rock\')">Каменный</div>
        <div class="btnType typebug" onclick="catatcdex(\'bug\')">Насекомый</div>
        <div class="btnType typeghost" onclick="catatcdex(\'ghost\')">Призрачный</div>
        <div class="btnType typefire" onclick="catatcdex(\'fire\')">Огненный</div>
        <div class="btnType typewater" onclick="catatcdex(\'water\')">Водный</div>
        <div class="btnType typegrass" onclick="catatcdex(\'grass\')">Травяной</div>
        <div class="btnType typeelectric" onclick="catatcdex(\'electric\')">Электрический</div>
        <div class="btnType typepsychic" onclick="catatcdex(\'psychic\')">Психический</div>
        <div class="btnType typeice" onclick="catatcdex(\'ice\')">Ледяной</div>
        <div class="btnType typedragon" onclick="catatcdex(\'dragon\')">Драконий</div>
        <div class="btnType typedark" onclick="catatcdex(\'dark\')">Темный</div>
        <div class="btnType typesteel" onclick="catatcdex(\'steel\')">Стальной</div>
        <div class="btnType typefairy" onclick="catatcdex(\'fairy\')">Волшебный</div>
        <div class="btnType " onclick="catatcdex(\'all\')">Все</div>
      </div>
    </div>
    ';
    $response['text'] = $a;
    break;
    case 'atcdex2':
    $a = '<div class="Name">Сортировка по типу</div>
    <div class="About">
      <div class="TypeCenter">
        <div class="btnType typephysical" onclick="catatcdex(\'physical\',1)">Физические</div>
        <div class="btnType typespecial" onclick="catatcdex(\'special\',1)">Специальные</div>
        <div class="btnType typestatus" onclick="catatcdex(\'status\',1)">Статусные</div>
        <div class="btnType typespecific" onclick="catatcdex(\'specific\',1)">Специфические</div>
        <div class="btnType " onclick="catatcdex(\'all\',1)">Все</div>
      </div>
    </div>
    ';
    $response['text'] = $a;
    break;
    //case 'FarmInfo':
     // $slot = $mysqli->query('SELECT * FROM user_berry_slot WHERE user = '.$_SESSION['id'].' AND slot = '.$id)->fetch_assoc();
      //$berry = $mysqli->query('SELECT * FROM base_items WHERE id = '.$slot['berry'])->fetch_assoc();
//
     // $time = downcounter($slot['date']);
     // $s_f = $slot['s_fertilizer'];
     // $m_f = $slot['m_fertilizer'];
     // $l_f = $slot['l_fertilizer'];
     // if(time() < $slot['date']){
     // if(!$s_f AND !$m_f AND !$l_f){
      //  $fert_use = "Не было использовано удобрений ";
      //  $b_s_f = '<div onclick="berryGo(\'small\','.$slot['slot'].')">Малое удобрение</div>';
      //  $b_m_f = '<div onclick="berryGo(\'medium\','.$slot['slot'].')">Среднее удобрение</div>';
      //  $b_l_f = '<div onclick="berryGo(\'large\','.$slot['slot'].')">Большое удобрение</div>';
     // }else{
      //  $fert_use = "Было использовано ";
      //  if($s_f){
      //    $b .= " Малое удобрение ";
      //    $b_s_f = "";
      //  }else{
      //    $b_s_f = '<div  onclick="berryGo(\'small\','.$slot['slot'].')">Малое удобрение</div>';
     //   }
      //  if($m_f){
      //    $b .= " Среднее удобрение ";
      //  }else{
      //    $b_m_f = '<div  onclick="berryGo(\'medium\','.$slot['slot'].')">Среднее удобрение</div>';
      //  }
      //  if($l_f){
      //    $b .= " Большое удобрение ";
      //    $b_l_f = "";
      //  }else{
      //    $b_l_f = '<div onclick="berryGo(\'large\','.$slot['slot'].')">Большое удобрение</div>';
      //  }
     // }
   // }else{
    //  if(!$s_f AND !$m_f AND !$l_f){
    //    $fert_use = "Не было использовано удобрений ";
    //  }else{
    //    $fert_use = "Было использовано ";
    //    if($s_f){  $b .= " Малое удобрение ";}
    //    if($m_f){$b .= " Среднее удобрение ";}
    //    if($l_f){$b .= " Большое удобрение ";}
    //  }
    //  $b_s_f = '<div onclick="berryGo(\'pick\','.$slot['slot'].')">Собрать</div>';
   // }
    //  $a .= '<div class="Name">'.$berry['name'].' x'.$slot['cool'].'</div>
    //  <div class="Image"><img id="imgItem" src="/img/world/items/little/'.$berry['id'].'.png"></div>
   // //  <div class="About">'.$berry['about'].'</div>
//
    //  <div class="About">
    //  Созреет через <b>'.$time.'</b><br>
    //  '.$fert_use.' <b>'.$b.'</b>
   //   </div>
    //  <div class="Buttons">
    //    '.$b_s_f.' '.$b_m_f.' '.$b_l_f.'
    //  </div>';
    //  $response['text'] = $a;
   // break;
    case 'Repair':
        if($id == 1){
            $berry2 = $mysqli->query('SELECT
                `bi`.`id` as `item_id`,
                `bi`.`name`,
                `iu`.`dop`,
                `iu`.`id`,
                `iu`.`str`
                FROM `base_items` AS `bi`
                INNER JOIN `items_users` AS `iu`
                  ON
                    `iu`.`item_id` = `bi`.`id`
                WHERE
                   `iu`.`str` != "" AND `iu`.`user` = '.$_SESSION['id']);
        $a .= '<div class="Name">Выбор предмета</div>
          <div class="About">Выберите подходящий предмет для починки</div>
          <form class="evolNpcForm" onsubmit="RepItemGo();return false;" "=""><select id="itemID">';
          while ($ber2 = $berry2->fetch_assoc()){
              $str = explode(',',$ber2['str']);
              if($str[0] != $str[1]){

            $a .= '<option value="'.$ber2['id'].'">'.$ber2['name'].' '.$str[0].'/'.$str[1].'</option>';
              }
          }
					$a .='</select>
						<input class="mn-btn" type="submit" value="Выбрать">
					</form>
        ';
        }elseif($id == 2){
            $berry = $mysqli->query('SELECT
                `bi`.`id` as `item_id`,
                `bi`.`name`,
                `iu`.`dop`,
                `iu`.`id`
                FROM `base_items` AS `bi`
                INNER JOIN `items_users` AS `iu`
                  ON
                    `iu`.`item_id` = `bi`.`id`
                WHERE
                  `iu`.`item_id` = 408 AND `iu`.`user` = '.$_SESSION['id']);

        $a .= '<div class="Name">Выбор зелья</div>
          <div class="About">Выберите подходящее зелье для починки предмета</div>
          <form class="evolNpcForm" onsubmit="RepPotionGo();return false;" "=""><select id="potionID">';
          while ($ber = $berry->fetch_assoc()){
            $a .= '<option value="'.$ber['id'].'">'.$ber['name'].', '.$ber['dop'].'%</option>';
          }
					$a .='</select>
						<input class="mn-btn" type="submit" value="Выбрать">
					</form>
        ';
        }

        $response['text'] = $a;
    break;
    case 'FarmInfo2':
      $t = "berry";
      $berry= $mysqli->query('SELECT
                `bi`.`id`,
                `bi`.`name`,
                `iu`.`count`
                FROM `base_items` AS `bi`
                INNER JOIN `items_users` AS `iu`
                  ON
                    `iu`.`item_id` = `bi`.`id`
                WHERE
                  `bi`.`type` = "'.$t.'" AND `iu`.`user` = '.$_SESSION['id']);
        $a .= '<div class="Name">Слот #'.$id.'</div>
          <div class="About">Выберите ягоду чтобы посадить ее. Она будет созревать 24 часа, после чего вы сможете собрать ее.</div>
          <form class="evolNpcForm" onsubmit="berryGo(\'add\','.$id.');return false;" "=""><select id="berryID">';
          while ($ber = $berry->fetch_assoc()){
            $a .= '<option value="'.$ber['id'].'">'.$ber['name'].' x'.$ber['count'].'</option>';
          }
					$a .='</select>
						<input class="mn-btn" type="submit" value="Выбрать">
					</form>
        ';
    //   while ($ber = $berry->fetch_assoc()){
    //     $a .= "f";
    //   }
      $response['text'] = $a;
    break;
		case 'pokTeam':
			$pokemon = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$id."'")->fetch_assoc();
			$user = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$other."'")->fetch_assoc();
			if($user['team_open'] == 1){
				if($pokemon['id'] >= 1 && $pokemon['id'] <= 9){
					$b = '00'.$pokemon['id'];
				}elseif($pokemon['id'] >= 10 && $pokemon['id'] <= 99){
					$b = '0'.$pokemon['id'];
				}else{
					$b = $pokemon['id'];
				}
				$a = '<div class="t-w">#'.$b.' '.$pokemon['name_rus'].'</div><br><center><img src="/img/pokemons/mini/normal/'.$b.'.png"></center>';
			}else{
				$a = 'Команда данного тренера закрыта';
			}
			$response['text'] = $a;
		break;
		default:
			echo "Unknown error";
		break;
	}
echo json_encode($response);
?>

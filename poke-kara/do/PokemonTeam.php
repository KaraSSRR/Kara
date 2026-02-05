<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}
switch($_POST["type"]){
case "load":
    $pokList = [];
    
    // Получаем данные из активного боя (PvE И PvP)
$battlePokemonData = [];
$battleQuery = $mysqli->query('SELECT * FROM `battle` WHERE 
    (user_1 = '.(int)$_SESSION['id'].' OR user_2 = '.(int)$_SESSION['id'].') 
    AND (type = "pve" OR type = "pvp")
    ORDER BY id DESC LIMIT 1');

if ($battleQuery && $battleQuery->num_rows > 0) {
    $battle = $battleQuery->fetch_assoc();
    $isUser1 = $battle['user_1'] == $_SESSION['id'];
    $infoField = $isUser1 ? 'info_1' : 'info_2';
    
    if (!empty($battle[$infoField])) {
        $battleData = json_decode($battle[$infoField], true);
        if (isset($battleData['pokeLIst'])) {
            foreach ($battleData['pokeLIst'] as $pokemonKey => $pokemonData) {
                $pokemonId = str_replace('p', '', $pokemonKey);
                $battlePokemonData[$pokemonId] = $pokemonData;
            }
        }
    }
}
    $pokemons = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = "'.(int)$_SESSION['id'].'" AND `active` = 1');
    $user = $mysqli->query('SELECT sprite FROM users WHERE id = '.(int)$_SESSION['id'])->fetch_assoc();
    
    while($pokemon = $pokemons->fetch_assoc()){
        stat_updates($pokemon['id']);

        // Проверяем, есть ли покемон в бою
        $battlePokemon = isset($battlePokemonData[$pokemon['id']]) ? $battlePokemonData[$pokemon['id']] : null;
        
        // ИСПРАВЛЯЕМ: Получаем базовые статы БЕЗ модификаторов для HP
        $originalStats = explode(',', $pokemon['stats']);
        $displayStats = $originalStats; // Для показа в команде используем оригинальные
        
        // ИСПРАВЛЯЕМ: HP всегда базовый, модификаторы не влияют на максимальный HP
        $maxHP = (int)$originalStats[0];
        $curHP = $battlePokemon ? (int)$battlePokemon['hp'] : (int)$pokemon['hp'];
        
        // Но для других статов применяем модификаторы если покемон в бою
        if ($battlePokemon) {
            $pokeObj = new PokeBattle($pokemon, true);
            
            // Применяем боевые модификаторы если есть
            if (isset($battlePokemon['modified']) && is_array($battlePokemon['modified'])) {
                $pokeObj->modified = $battlePokemon['modified'];
            }
            
            // Применяем статусы если есть
            if (isset($battlePokemon['status_list']) && is_array($battlePokemon['status_list'])) {
                $pokeObj->status_list = $battlePokemon['status_list'];
            }
            
            // Пересчитываем ТОЛЬКО боевые статы (ATK, DEF, SPD, SATK, SDEF)
            // HP остается базовым!
            $displayStats = [
                (int)$originalStats[0],              // HP - ВСЕГДА базовый!
                round($pokeObj->_getStatAtk()),      // Атака с модификаторами
                round($pokeObj->_getStatDef()),      // Защита с модификаторами
                round($pokeObj->_getStatSpd()),      // Скорость с модификаторами
                round($pokeObj->_getStatSAtk()),     // Спец.Атака с модификаторами
                round($pokeObj->_getStatSDef())      // Спец.Защита с модификаторами
            ];
        }

        // Обработка tren_stat
        switch((int)$pokemon['tren_stat']){
            case 1: $trenStat = 2; break;
            case 2: $trenStat = 3; break;
            case 3: $trenStat = 4; break;
            case 4: $trenStat = 5; break;
            default: $trenStat = 6;
        }
        
        $stats = $displayStats;
        $gen = explode(',', $pokemon['gen']);
        
        if($pokemon['type'] == 'shine'){
            $textUnik = '<div class="Unik shine-color">shine</div>';
            $typeSprite = 'shine';
        }elseif($pokemon['type'] == 'shadow'){
            $textUnik = '<div class="Unik shadow-color">shadow</div>';
            $typeSprite = 'shadow';
        }elseif($pokemon['type'] == 'NewYear'){
            $textUnik = '<div class="Unik shine-color">NewYear</div>';
            $typeSprite = 'NewYear';
        }else{
            $typeSprite = 'normal';
            $textUnik = '';
        }
        $form = ($pokemon['form'] != "0") ? "_".$pokemon['form'] : "";

        $sprite = '<div class="Image"><img  onclick="Game.pokemonTeamTabs('.$pokemon['id'].',\'info\')" src="/img/pokemons/sprite/'.$pokemon['type'].'/'.numbPok($pokemon['basenum']).$form.'.gif"></div>';
        $fullBasenum = numbPok($pokemon['basenum']);
        $type = $pokemon['type'] != 'normal' ? '<i>'.$pokemon['type'].'</i>' : '';

        $p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.(int)$pokemon['basenum'].'" ')->fetch_object();

        // Корректный расчет опыта
        $explvllow = Info::_getExp($pokemon['lvl']-1, $p->exp_group);
        $explvl = $pokemon['exp']-$explvllow;
        $explvl2 = $pokemon['exp_max']-$explvllow;
        $exp = (int)$pokemon['lvl'] != 100 ? $explvl.' / '.$explvl2 : 'full';

        $paired = $pokemon['sparka'] == 1 ? 'spar' : '';
        if($pokemon['gender'] == 'Девочка') {
            $gender = 'venus';
        }elseif($pokemon['gender'] == 'Мальчик') {
            $gender = 'mars';
        }else{
            $gender = 'genderless';
        }
        $gen = 'h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5];

        if(!empty($pokemon['item_str'])) {
            $str = explode(',', $pokemon['item_str']);
            $str = $str[0].'/'.$str[1];
        }else{
            $str = '';
        }
        $item = $pokemon['item_id'] != 0 ? '<div style="background-image: url(/img/world/items/little/'.$pokemon['item_id'].'.png);" class="Item" onclick=itemAction('.$pokemon['item_id'].',\'remove\',false,false,'.$pokemon['id'].');' .'><div class="str">'.$str.'</div></div>' : '';
        $tren = $pokemon['tren'] != 0 ? '<div class="Tren" id="TrenPokemon'. $pokemon['id'] .'" style="background-image: url(/img/tren/'.$pokemon['tren'].'.png);"></div>' : '';

        // ИСПРАВЛЯЕМ: HP расчет с правильными значениями
        $fHp = ($curHP / $maxHP) * 100;
        if($fHp > 100) $fHp = 100;
        elseif($fHp < 0) $fHp = 0;

        if((int)$pokemon['lvl'] == 100) {
            $fExp = 100;
        }else{
            $fExp = ($explvl2 > 0) ? ((mb_strimwidth($explvl, 0, 5, "..") / mb_strimwidth($explvl2, 0, 5, "..")) * 100) : 0;
        }
        $fHappy = (($pokemon['happy'] / 255) * 100);
        
        // Боевые индикаторы
        $battleIndicators = '';
        if ($battlePokemon) {
            
            // Статусы
            if (isset($battlePokemon['status_list'])) {
                foreach ($battlePokemon['status_list'] as $status => $data) {
                    switch ($status) {
                        case 'burn':
                            $battleIndicators .= '<i class="status-burn fas fa-fire" title="Ожог (-50% Атака)"></i>';
                            break;
                        case 'poison':
                            $battleIndicators .= '<i class="status-poison fas fa-skull-crossbones" title="Отравление"></i>';
                            break;
                        case 'paralyzed':
                            $battleIndicators .= '<i class="status-paralyze fas fa-bolt" title="Паралич (-75% Скорость)"></i>';
                            break;
                        case 'sleep':
                            $battleIndicators .= '<i class="status-sleep fas fa-bed" title="Сон"></i>';
                            break;
                        case 'freeze':
                            $battleIndicators .= '<i class="status-freeze fas fa-snowflake" title="Заморозка"></i>';
                            break;
                    }
                }
            }
            
            
        }
        
        $tr = '';
        $tr_b = 'angle-double-up';
        if($pokemon['tren'] == 1) { $tr_n = 'tr1'; }
        elseif($pokemon['tren'] == 2) { $tr_n = 'tr2'; }
        elseif($pokemon['tren'] == 3) { $tr_n = 'tr3'; }
        elseif($pokemon['tren'] == 4) { $tr_n = 'tr4'; }
        elseif($pokemon['tren'] == 5) { $tr_n = 'tr5'; }
        elseif($pokemon['tren'] == 6) { $tr_n = 'tr6'; $tr_b = 'crown'; }
        if($pokemon['tren'] >= 1){ $tr = '<i class="trening fas fa-'.$tr_b.' '.$tr_n.'"></i>'; }

        
        // --- TERA: атрибуты для отображения тератипа в UI (world.js) ---
        $teraType = (!empty($pokemon['tera_type']) ? $pokemon['tera_type'] : '');
        $teraAttr = ($teraType ? ' data-tera-type="'.htmlspecialchars($teraType, ENT_QUOTES).'"' : '');
        $isTeraActive = (!empty($battlePokemon) && !empty($battlePokemon['tera_active']));
        $teraActiveAttr = ($isTeraActive ? ' data-tera-active="1"' : '');
        $teraBadge = $teraType
            ? '<div class="TeraBadge'.($isTeraActive ? ' is-active' : '').'" title="Тератип: '.htmlspecialchars($teraType, ENT_QUOTES).'"><img src="/img/world/typs/'.htmlspecialchars($teraType, ENT_QUOTES).'.png" alt="'.htmlspecialchars($teraType, ENT_QUOTES).'"><span>'.htmlspecialchars($teraType, ENT_QUOTES).'</span></div>'
            : '';

$html = ' <div class="Modif" >'.$tr.$battleIndicators.'</div>'.$sprite.' <div class="Ball '.check_evol($pokemon['id']).' " onclick="pokAction(this,'.$pokemon['id'].','.$pokemon['start_pok'].',false,'.$pokemon['basenum'].')" style="background-image: url(/img/world/items/little/'.$pokemon['ball'].'.png);"></div>
      <div class="Lvl">'.$pokemon['lvl'].'</div>
      '.$item.'
      '.($pokemon['type'] == 'normal' ? '' : '<div class="Unik '.$pokemon['type'].'-color">'.$pokemon['type'].'</div>').'
      '.$teraBadge.'
      <div class="Name '.$pokemon['type'].'-color">
					<div class="Text">
						#'.$fullBasenum.' '. mb_strimwidth($pokemon['name_new'], 0, 22, '...') .'
					</div>
					<div class="Sex '.$paired.'"><i class="fas fa-'.$gender.'"></i></div>
				</div>
       <div class="Bars">
  <div class="Bar hp_proggresbar" data-title="HP: '.$curHP.' / '.$maxHP.'">
    <div class="HpBar" style="width: '.$fHp.'%;"></div>
    <div class="HpText">'.$curHP.' / '.$maxHP.'</div>
  </div>
  <div class="Bar exp_progressbar" data-title="Опыт: '. $exp .'">
    <div class="ExpBar" style="width: '.$fExp.'%;"></div>
  </div>
  <div class="Bar happy_progressbar" data-title="Счастье: '.$pokemon['happy'].' / 255">
    <div class="HappyBar" style="width: '.$fHappy.'%;"></div>
  </div>
</div>
				';

        $pokList[$pokemon['id']] = [
            'id'        => $pokemon['id'],
            'basenum'   => $fullBasenum,
            'start'     => $pokemon['start_pok'] == 1 ? "Start-Pokemon-Box" : "",
            'hp'        => $curHP,
            'maxHP'     => $maxHP,
            'exp'       => $pokemon['exp'],
            'expMax'    => $pokemon['exp_max'],
            'happy'     => $pokemon['happy'],
            'trenColor' => $pokemon['tren'],
            'trenStat'  => $trenStat,
            'inBattle'  => !!$battlePokemon,
            'originalStats' => $originalStats,    // Базовые статы
            'battleStats' => $battlePokemon ? $displayStats : null, // Статы с модификаторами
            'html'      => $html
        ];
    }
    echo json_encode($pokList);
break; 

case "set_trainer_pokemon":
    /**
     * Улучшенный вывод премиум-уведомления:
     *  - ВСЕГДА JSON.
     *  - При отсутствии премиума возвращаем:
     *      error = 1
     *      code  = 'premium_required'
     *      premium_html = готовый красивый HTML блок (можно просто вставить в модал).
     *  - При успехе: как раньше (success= true, error=0, trainerPokemonBasenum).
     *
     * Использование на фронте (см. JS-патч): если resp.code === 'premium_required' -> вставить resp.premium_html.
     */

    $pokemon_id = isset($_POST['pokemon_id']) ? (int)$_POST['pokemon_id'] : 0;
    $user_id    = (int)$_SESSION['id'];
    $now        = time();

    header('Content-Type: application/json; charset=utf-8');

    $result = [
        'success' => false,
        'error'   => 1,
        'text'    => 'Неизвестная ошибка'
    ];

    // Проверяем премиум баф 448
    $hasPremium = false;
    if ($stmt = $mysqli->prepare("SELECT 1 FROM `bafs` WHERE `user` = ? AND `baf` = 448 AND `time` > ? LIMIT 1")) {
        $stmt->bind_param('ii', $user_id, $now);
        $stmt->execute();
        $stmt->store_result();
        $hasPremium = $stmt->num_rows > 0;
        $stmt->close();
    }

    if (!$hasPremium) {
        // Готовим красивый HTML блок (можно без inline стилей, но для автономности даём ключевые прямо здесь)
        ob_start();
        ?>
        <div class="tpPremiumNoticeWrap mini">
          <div class="tpPN-card">
            <div class="tpPN-icon"><i class="fa fa-crown" aria-hidden="true"></i></div>
            <div class="tpPN-body">
              <div class="tpPN-title">Нужен Премиум</div>
              <div class="tpPN-text">
                Для вывода покемона рядом с аватаром требуется активный премиум‑баф
                <span class="tpPN-badge">#448</span>.
              </div>
              <ul class="tpPN-benefits">
                <li><i class="fa fa-check-circle"></i> Покемон на тренер-карте</li>
                <li><i class="fa fa-check-circle"></i> Дополнительная персонализация</li>
                <li><i class="fa fa-check-circle"></i> Будущие визуальные эффекты</li>
              </ul>
              <div class="tpPN-actions">
                <a href="/shop/premium" class="tpPN-btn tpPN-btn-primary">
                  <i class="fa fa-gem"></i> Получить Премиум
                </a>
                <button type="button" class="tpPN-btn tpPN-btn-ghost" onclick="(function(b){ if(b && b.parentNode){ b.parentNode.remove(); } })(this.closest('.tpPremiumNoticeWrap')); ">
                  Закрыть
                </button>
              </div>
              <div class="tpPN-footnote">После активации просто повторите выбор покемона.</div>
            </div>
          </div>
        </div>
        <?php
        $premiumHtml = ob_get_clean();

        $result['text']         = 'Нужен активный Премиум (баф #448) для размещения покемона.';
        $result['code']         = 'premium_required';
        $result['premium_html'] = $premiumHtml;

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;
    }

    // Проверяем принадлежность покемона
    if ($pokeStmt = $mysqli->prepare("SELECT `basenum` FROM `user_pokemons` WHERE `id` = ? AND `user_id` = ? LIMIT 1")) {
        $pokeStmt->bind_param('ii', $pokemon_id, $user_id);
        $pokeStmt->execute();
        $pokeStmt->bind_result($basenum);
        if ($pokeStmt->fetch()) {
            $pokeStmt->close();
            $basenum = (int)$basenum;

            if ($upd = $mysqli->prepare("UPDATE `users` SET `trainer_pokemon` = ? WHERE `id` = ? LIMIT 1")) {
                $upd->bind_param('ii', $basenum, $user_id);
                if ($upd->execute()) {
                    $result['success'] = true;
                    $result['error']   = 0;
                    $result['text']    = 'Покемон добавлен на тренер-карту.';
                    $result['trainerPokemonBasenum'] = function_exists('numbPok')
                        ? numbPok($basenum)
                        : str_pad($basenum, 3, '0', STR_PAD_LEFT);
                } else {
                    $result['text'] = 'Не удалось сохранить покемона. Повторите попытку.';
                }
                $upd->close();
            } else {
                $result['text'] = 'Ошибка подготовки запроса (update).';
            }
        } else {
            $pokeStmt->close();
            $result['text'] = 'Покемон не найден или не принадлежит вам.';
        }
    } else {
        $result['text'] = 'Ошибка подготовки запроса (select).';
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    break;
case "info":
    $pokemon = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = "'.$_SESSION['id'].'" AND `active` = 1 AND id = '.$_POST['other'])->fetch_assoc();
    $pokemon2 = $mysqli->query('SELECT * FROM `lombard` WHERE `category` = "pok" AND `productID` = '.$_POST['other'])->fetch_assoc();
    if($pokemon || isset($pokemon2)){
        if(isset($pokemon2)) {
            $pokemon = $mysqli->query('SELECT * FROM `user_pokemons` WHERE id = '.$pokemon2['productID'])->fetch_assoc();
        }
        stat_updates($pokemon['id']);
        
        // Получаем данные из активного боя (PvE И PvP)
        $battlePokemonData = [];
        $battleQuery = $mysqli->query('SELECT * FROM `battle` WHERE 
            (user_1 = '.(int)$_SESSION['id'].' OR user_2 = '.(int)$_SESSION['id'].') 
            AND (type = "pve" OR type = "pvp")
            ORDER BY id DESC LIMIT 1');

        if ($battleQuery && $battleQuery->num_rows > 0) {
            $battle = $battleQuery->fetch_assoc();
            $isUser1 = $battle['user_1'] == $_SESSION['id'];
            $infoField = $isUser1 ? 'info_1' : 'info_2';
            
            if (!empty($battle[$infoField])) {
                $battleData = json_decode($battle[$infoField], true);
                if (isset($battleData['pokeLIst'])) {
                    foreach ($battleData['pokeLIst'] as $pokemonKey => $pokemonData) {
                        $pokemonId = str_replace('p', '', $pokemonKey);
                        $battlePokemonData[$pokemonId] = $pokemonData;
                    }
                }
            }
        }
        
        // ИСПРАВЛЕНО: Получаем данные конкретного покемона из боя
        $battlePokemon = isset($battlePokemonData[$pokemon['id']]) ? $battlePokemonData[$pokemon['id']] : null;
        
        // ИСПРАВЛЕНО: Используем статы из базы данных (актуальные)
        $stats = explode(',', $pokemon['stats']);
        
        // ИСПРАВЛЕНО: HP берем из боевых данных если покемон в бою
        if ($battlePokemon && isset($battlePokemon['hp'])) {
            $curHP = (int)$battlePokemon['hp'];
            $maxHP = isset($battlePokemon['hp_max']) ? (int)$battlePokemon['hp_max'] : (int)$stats[0];
        } else {
            $curHP = (int)$pokemon['hp'];
            $maxHP = (int)$stats[0];
        }
        
        // Определяем состояние нокаута
        $isFainted = ($curHP <= 0);
        $faintedClass = $isFainted ? ' fainted' : '';
        
        $gen = explode(',', $pokemon['gen']);
        
        if($pokemon['type'] == 'shine'){
            $textUnik = '<div class="Unik shine-color">shine</div>';
            $typeSprite = 'shine';
        }elseif($pokemon['type'] == 'shadow'){
            $textUnik = '<div class="Unik shadow-color">shadow</div>';
            $typeSprite = 'shadow';
        }elseif($pokemon['type'] == 'NewYear'){
            $textUnik = '<div class="Unik shine-color">NewYear</div>';
            $typeSprite = 'NewYear';
        }else{
            $typeSprite = 'normal';
            $textUnik = '';
        }
        
        $form = ($pokemon['form'] != "0") ? "_".$pokemon['form'] : "";
        $sprite = '<div class="Image"><img onclick="Game.modals.pokemons()" src="/img/pokemons/sprite/'.$pokemon['type'].'/'.numbPok($pokemon['basenum']).$form.'.gif"></div>';
        $fullBasenum = numbPok($pokemon['basenum']);
        $type = $pokemon['type'] != 'normal' ? '<i>'.$pokemon['type'].'</i>' : '';

        $p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.(int)$pokemon['basenum'].'" ')->fetch_object();

        // Расчет опыта
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
        $gen_display = 'h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5];
        
        if(!empty($pokemon['item_str'])) {
            $str = explode(',', $pokemon['item_str']);
            $str = $str[0].'/'.$str[1];
        }else{
            $str = '';
        }
        $item = $pokemon['item_id'] != 0 ? '<div style="background-image: url(/img/world/items/little/'.$pokemon['item_id'].'.png);" class="Item" onclick=itemAction('.$pokemon['item_id'].',\'remove\',false,false,'.$pokemon['id'].');' .'><div class="str">'.$str.'</div></div>' : '';
        $tren = $pokemon['tren'] != 0 ? '<div class="Tren" id="TrenPokemon'. $pokemon['id'] .'" style="background-image: url(/img/tren/'.$pokemon['tren'].'.png);"></div>' : '';
        
        // Расчет процентов для полосок
        $fHp = ($curHP / $maxHP) * 100;
        if($fHp > 100) $fHp = 100;
        elseif($fHp < 0) $fHp = 0;
        
        if((int)$pokemon['lvl'] == 100) {
            $fExp = 100;
        }else{
            $fExp = ($explvl2 > 0) ? (($explvl / $explvl2) * 100) : 0;
        }
        $fHappy = (($pokemon['happy'] / 255) * 100);
        
        $birthdayJson = json_decode($pokemon['birthday']);
        $up = json_decode($pokemon['modification']);
        
        // Получение атак
        $attack_lang = $_SESSION['attack_lang'] ?? 'rus';
        $atk_name_col = ($attack_lang === 'eng') ? 'name' : 'name_rus';
        $attacks = explode(',', $pokemon['attacks']);
        
        // ИСПРАВЛЕНО: PP берем из боевых данных если есть
        if ($battlePokemon && isset($battlePokemon['pp_my'])) {
            $attack_pp = explode(',', rtrim($battlePokemon['pp_my'], ','));
        } else {
            $attack_pp = explode(',', $pokemon['pp_attacks']);
        }

        // Атака 1
        if (isset($attacks[0]) && $attacks[0] > 0) {
            $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '" . $attacks[0] . "'")->fetch_assoc();
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
        } else {
            $atkOne = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
            $attacks[0] = 0;
            $ppOne = 0;
            $typeOne = '';
            $category1 = 3;
        }

        // Атака 2
        if (isset($attacks[1]) && $attacks[1] > 0) {
            $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '" . $attacks[1] . "'")->fetch_assoc();
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
        } else {
            $atkTwo = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
            $attacks[1] = 0;
            $ppTwo = 0;
            $typeTwo = '';
            $category2 = 3;
        }

        // Атака 3
        if (isset($attacks[2]) && $attacks[2] > 0) {
            $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '" . $attacks[2] . "'")->fetch_assoc();
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
        } else {
            $atkThree = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
            $attacks[2] = 0;
            $ppThree = 0;
            $typeThree = '';
            $category3 = 3;
        }

        // Атака 4
        if (isset($attacks[3]) && $attacks[3] > 0) {
            $atkQuery = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '" . $attacks[3] . "'")->fetch_assoc();
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
        } else {
            $atkFour = ($attack_lang === 'eng') ? 'No attack' : 'Нет атаки';
            $attacks[3] = 0;
            $ppFour = 0;
            $typeFour = '';
            $category4 = 3;
        }
        
        // Остальные данные
        if($pokemon['trade'] == 'false') {
            $trd = 'tradeNo';
            $clos = '<i class="fas fa-lock"></i>';
        }else{
            $trd = 'tradeYes';
            $clos = '';
        }

        // Тренировки
        $tr_b = 'angle-double-up';
        if($pokemon['tren'] == 1) { $tr_n = 'tr1'; }
        elseif($pokemon['tren'] == 2) { $tr_n = 'tr2'; }
        elseif($pokemon['tren'] == 3) { $tr_n = 'tr3'; }
        elseif($pokemon['tren'] == 4) { $tr_n = 'tr4'; }
        elseif($pokemon['tren'] == 5) { $tr_n = 'tr5'; }
        elseif($pokemon['tren'] == 6) { $tr_n = 'tr6'; $tr_b = 'crown'; }

        $tr1 = $tr2 = $tr3 = $tr4 = $tr5 = '';
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
        
        if($pokemon['sparka'] == 1){
            $sex = 'Red-Color';
        }else{
            $sex = 'Green-Color';
        }
        
        // Характер
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
        
        // ИСПРАВЛЕНО: Боевые индикаторы (только статусы)
        $battleIndicators = '';
        if ($battlePokemon && isset($battlePokemon['status_list']) && is_array($battlePokemon['status_list'])) {
            foreach ($battlePokemon['status_list'] as $status => $data) {
                switch ($status) {
                    case 'burn':
                        $battleIndicators .= '<i class="status-burn fas fa-fire" title="Ожог"></i>';
                        break;
                    case 'poison':
                        $battleIndicators .= '<i class="status-poison fas fa-skull-crossbones" title="Отравление"></i>';
                        break;
                    case 'toxic':
                        $battleIndicators .= '<i class="status-poison fas fa-skull-crossbones" title="Сильное отравление"></i>';
                        break;
                    case 'paralyzed':
                        $battleIndicators .= '<i class="status-paralyze fas fa-bolt" title="Паралич"></i>';
                        break;
                    case 'sleep':
                        $battleIndicators .= '<i class="status-sleep fas fa-bed" title="Сон"></i>';
                        break;
                    case 'freeze':
                        $battleIndicators .= '<i class="status-freeze fas fa-snowflake" title="Заморозка"></i>';
                        break;
                }
            }
        }
        $teraType = (!empty($pokemon['tera_type']) ? $pokemon['tera_type'] : '');
        $teraAttr = ($teraType ? ' data-tera-type="'.htmlspecialchars($teraType, ENT_QUOTES).'"' : '');
        $isTeraActive = (!empty($battlePokemon) && !empty($battlePokemon['tera_active']));
        $teraActiveAttr = ($isTeraActive ? ' data-tera-active="1"' : '');
        $teraBadge = $teraType
            ? '<div class="TeraBadge'.($isTeraActive ? ' is-active' : '').'" title="Тератип: '.htmlspecialchars($teraType, ENT_QUOTES).'"><img src="/img/world/typs/'.htmlspecialchars($teraType, ENT_QUOTES).'.png" alt="'.htmlspecialchars($teraType, ENT_QUOTES).'"><span>'.htmlspecialchars($teraType, ENT_QUOTES).'</span></div>'
            : '';
        
        $html = '
        <div class="Info">
            <div class="Left">
                <div class="PokemonBox'.$faintedClass.'" data-pokemon-id="'.$pokemon['id'].'"'.$teraAttr.$teraActiveAttr.'>
                    <div class="Modif">'.$b.$battleIndicators.'</div>
                    '.$sprite.' 
                    <div class="Ball '.check_evol($pokemon['id']).'" onclick="pokAction(this,'.$pokemon['id'].','.$pokemon['start_pok'].',false,'.$pokemon['basenum'].')" style="background-image: url(/img/world/items/little/'.$pokemon['ball'].'.png);"></div>
                    <div class="Lvl">'.$pokemon['lvl'].'</div>
                    '.$item.'
                    '.($pokemon['type'] == 'normal' ? '' : '<div class="Unik '.$pokemon['type'].'-color">'.$pokemon['type'].'</div>').'
                    '.$teraBadge.'
                    <div class="Name '.$pokemon['type'].'-color">
                        <div class="Text">
                            #'.$fullBasenum.' '. mb_strimwidth($pokemon['name_new'], 0, 22, '...') .'
                        </div>
                        <div class="Sex '.$paired.'"><i class="fas fa-'.$gender.'"></i></div>
                    </div>
                    <div class="Bars">
                        <div class="Bar hp_proggresbar" data-title="HP: '.$curHP.' / '.$maxHP.'">
                            <div class="HpBar" style="width: '.$fHp.'%;"></div>
                            <div class="HpText">'.$curHP.' / '.$maxHP.'</div>
                        </div>
                        <div class="Bar exp_progressbar tipped" data-title="Опыт: '.$explvl.' / '.$explvl2.' (до следующего уровня: '.($explvl2 - $explvl).')">
                            <div class="ExpBar" style="width: '.$fExp.'%;"></div>
                        </div>
                        <div class="Bar happy_progressbar tipped" data-title="Счастье: '.$pokemon['happy'].' / 255 ('.round($fHappy, 1).'%)">
                            <div class="HappyBar" style="width: '.$fHappy.'%;"></div>
                        </div>
                    </div>
                </div>
                <div class="MoveBox">
                    <div class="Move" onclick="viewDescriptionAttak(this,'.$attacks[0].','.$pokemon['id'].',0);">
                        <img src="/img/world/typs/'.($typeOne?$typeOne:'empty').'.png" >
                        <div class="MoveInfo">
                            <div class="Name MoveCategory'.$category1.'">'.$atkOne.'</div>
                            <div class="PP">'.($attack_pp[0] ?? 0).'/'.($ppOne?$ppOne:0).' PP</div>
                        </div>
                    </div>
                    <div class="Move" onclick="viewDescriptionAttak(this,'.$attacks[1].','.$pokemon['id'].',1);">
                        <img src="/img/world/typs/'.($typeTwo?$typeTwo:'empty').'.png" >
                        <div class="MoveInfo">
                            <div class="Name MoveCategory'.$category2.'">'.$atkTwo.'</div>
                            <div class="PP">'.($attack_pp[1] ?? 0).'/'.($ppTwo?$ppTwo:0).' PP</div>
                        </div>
                    </div>
                    <div class="Move" onclick="viewDescriptionAttak(this,'.$attacks[2].','.$pokemon['id'].',2);">
                        <img src="/img/world/typs/'.($typeThree?$typeThree:'empty').'.png" >
                        <div class="MoveInfo">
                            <div class="Name MoveCategory'.$category3.'">'.$atkThree.'</div>
                            <div class="PP">'.($attack_pp[2] ?? 0).'/'.($ppThree?$ppThree:0).' PP</div>
                        </div>
                    </div>
                    <div class="Move" onclick="viewDescriptionAttak(this,'.$attacks[3].','.$pokemon['id'].',3);">
                        <img src="/img/world/typs/'.($typeFour?$typeFour:'empty').'.png" >
                        <div class="MoveInfo">
                            <div class="Name MoveCategory'.$category4.'">'.$atkFour.'</div>
                            <div class="PP">'.($attack_pp[3] ?? 0).'/'.($ppFour?$ppFour:0).' PP</div>
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
                    '.(!empty($pokemon['tera_type']) ? '<div class="Step">Тератип: <span><img src="/img/world/typs/'.htmlspecialchars($pokemon['tera_type'], ENT_QUOTES).'.png" style="width:18px;vertical-align:middle;"> <b>'.htmlspecialchars($pokemon['tera_type'], ENT_QUOTES).'</b></span></div>' : '').'
                    <div class="Step">
                        Генокод: <span>'.$gen_display.'</span>
                    </div>
                    <div class="Step">
                        Потенциал: <span>Отсутствует</span>
                    </div>
                    <div class="Step">
                        Пойман: <span>'.date('d.m.Y',$birthdayJson->date).'г. в '.date('H:i',$birthdayJson->date).' тренером <div class="user-link"><div onclick=showUserTooltip("'.$userPokemon['id'].'") class="Info-Link sex'.$userPokemon['sex'].'"><i class="fas fa-info"></i></div> <div class="u-'.$userPokemon['user_group'].' label" onclick=user_to_chat_add("'.$userPokemon['id'].'")>'.$userPokemon['login'].'</div></div></span>
                    </div>
                </div>
                <div class="MainInfo">
                    <div class="Stats">
                        <div class="Stat">
                            <div class="Name">Здоровье</div>
                            <div class="Count">'.$stats[0].' </div>
                            <div class="Progress" data-title="EV: '.$evcounts[0].' / 126">
                                <div class="Bar" style="width: '.(($evcounts[0] / 126) * 100).'%;"></div>
                            </div>
                            <div class="GoEv">
                                <div onclick=addEV("open",0,this,false,'.$pokemon['id'].'); class="Plus"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>
                        <div class="Stat">
                            <div class="Name '.$har1.'">Атака '.$tr1.' </div>
                            <div class="Count '.$har1.'">'.$stats[1].' </div>
                            <div class="Progress"  data-title="EV: '.$evcounts[1].' / 126">
                                <div class="Bar" style="width: '.(($evcounts[1] / 126) * 100).'%;"></div>
                            </div>
                            <div class="GoEv">
                                <div onclick=addEV("open",1,this,false,'.$pokemon['id'].'); class="Plus"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>
                        <div class="Stat">
                            <div class="Name '.$har2.'">Защита '.$tr2.'</div>
                            <div class="Count '.$har2.'">'.$stats[2].' </div>
                            <div class="Progress"  data-title="EV: '.$evcounts[2].' / 126">
                                <div class="Bar" style="width: '.(($evcounts[2] / 126) * 100).'%;"></div>
                            </div>
                            <div class="GoEv">
                                <div onclick=addEV("open",2,this,false,'.$pokemon['id'].'); class="Plus"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>
                        <div class="Stat">
                            <div class="Name '.$har3.'">Скорость '.$tr3.'</div>
                            <div class="Count '.$har3.'">'.$stats[3].' </div>
                            <div class="Progress"  data-title="EV: '.$evcounts[3].' / 126">
                                <div class="Bar" style="width: '.(($evcounts[3] / 126) * 100).'%;"></div>
                            </div>
                            <div class="GoEv">
                                <div onclick=addEV("open",3,this,false,'.$pokemon['id'].'); class="Plus"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>
                        <div class="Stat">
                            <div class="Name '.$har4.'">Спец. Атака '.$tr4.'</div>
                            <div class="Count '.$har4.'">'.$stats[4].'</div>
                            <div class="Progress"  data-title="EV: '.$evcounts[4].' / 126">
                                <div class="Bar" style="width: '.(($evcounts[4] / 126) * 100).'%;"></div>
                            </div>
                            <div class="GoEv">
                                <div onclick=addEV("open",4,this,false,'.$pokemon['id'].'); class="Plus"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>
                        <div class="Stat">
                            <div class="Name '.$har5.'">Спец. Защита '.$tr5.'</div>
                            <div class="Count '.$har5.'">'.$stats[5].' </div>
                            <div class="Progress"  data-title="EV: '.$evcounts[5].' / 126">
                                <div class="Bar" style="width: '.(($evcounts[5] / 126) * 100).'%;"></div>
                            </div>
                            <div class="GoEv">
                                <div onclick=addEV("open",5,this,false,'.$pokemon['id'].'); class="Plus"><i class="fa fa-plus"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        // Скрытая сила и остальные данные...
        $hpgen[0] = ((intval($gen[0]) % 2) == 0 ? 0 : 1);
        $hpgen[1] = ((intval($gen[1]) % 2) == 0 ? 0 : 1);
        $hpgen[2] = ((intval($gen[2]) % 2) == 0 ? 0 : 1);
        $hpgen[3] = ((intval($gen[5]) % 2) == 0 ? 0 : 1);
        $hpgen[4] = ((intval($gen[3]) % 2) == 0 ? 0 : 1);
        $hpgen[5] = ((intval($gen[4]) % 2) == 0 ? 0 : 1);

        $hptype = floor((($hpgen[0] + 2 * $hpgen[1] + 4 * $hpgen[2] + 8 * $hpgen[3] + 16 * $hpgen[4] + 32 * $hpgen[5]) * 15) / 63);

        $hptypenumrus = [
            'Боевой', 'Летающий', 'Ядовитый', 'Земляной', 'Каменный', 'Насекомое',
            'Призрачный', 'Стальной', 'Огненный', 'Водный', 'Травяной', 'Электрический',
            'Психический', 'Ледяной', 'Дракон', 'Темный'
        ];

        $hiddenpower = 'Тип скрытой силы '.$hptypenumrus[$hptype];
        $tr_l = explode(',',$pokemon['tren_log']);
        $tren_log = '<div class="tren_path">
                        <div><div><i class="trening fas fa-angle-double-up tr1"></i></div><span>'.$tr_l[0].'</span></div>
                        <div><div><i class="trening fas fa-angle-double-up tr2"></i></div><span>'.$tr_l[1].'</span></div>
                        <div><div><i class="trening fas fa-angle-double-up tr3"></i></div><span>'.$tr_l[2].'</span></div>
                        <div><div><i class="trening fas fa-angle-double-up tr4"></i></div><span>'.$tr_l[3].'</span></div>
                        <div><div><i class="trening fas fa-angle-double-up tr5"></i></div><span>'.$tr_l[4].'</span></div>
                        <div><div><i class="trening fas fa-crown tr6"></i></div><span>'.$tr_l[5].'</span></div>
                    </div>';
        
        $pokList = array(
            'html' => $html,
            'bDay'=>date("d", $birthdayJson->date),
            'bMounth'=>date("m", $birthdayJson->date),
            'bYear'=>date("o", $birthdayJson->date),
            'bTrener'=>'<div class="user-link u-'.$userPokemon['user_group'].'">'.$userPokemon['login'].'</div>',
            'genUp'=>$up->genUp,
            'harUp'=>$up->charEdit,
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
            'st8'=>$hiddenpower,
            'st9'=>$tren_log,
            'curHP' => $curHP,
            'maxHP' => $maxHP,
            'inBattle' => !!$battlePokemon,
            'fainted' => $isFainted
        );
    }
    die(json_encode($pokList));
break;
	case "stats":
		$pokemon = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = "'.$_SESSION['id'].'" AND `active` = 1 AND id = '.$_POST['other'])->fetch_assoc();
		if($pokemon){
			$stats = explode(',',$pokemon['stats']);
            $evcounts = explode(',',$pokemon['evcounts']);
			stat_updates($pokemon['id']);
			$pokList = array(
				'ev'=>$pokemon['ev'],
				'evHp'=>$evcounts[0],
				'evAtk'=>$evcounts[1],
				'evDef'=>$evcounts[2],
				'evSpd'=>$evcounts[3],
				'evSa'=>$evcounts[4],
				'evSd'=>$evcounts[5],
				'statHp'=>$stats[0],
				'statAtk'=>$stats[1],
				'statDef'=>$stats[2],
				'statSpd'=>$stats[3],
				'statSa'=>$stats[4],
				'statSd'=>$stats[5]
			);
		}
		die(json_encode($pokList));
	break;
}

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
// ... (ваш код подключения конфигов и БД)

function getTrainerCollection($user_id, $filter = 'all', $search = '', $selected_pok = null) {
    global $mysqli;

    // Получаем всех покемонов пользователя, делаем быстрый lookup
    $user_pokemons = [];
    $sql = "SELECT basenum, type FROM user_pokemons WHERE user_id = $user_id";
    $res = $mysqli->query($sql);
    while ($row = $res->fetch_assoc()) {
        if (!isset($user_pokemons[$row['basenum']])) {
            $user_pokemons[$row['basenum']] = [];
        }
        $user_pokemons[$row['basenum']][$row['type']] = true;
    }

    // Получаем весь список покемонов (строго все, даже не пойманных)
    $where = "form = 'Обычный'";
    if ($search !== '') {
        $search_safe = $mysqli->real_escape_string($search);
        $where .= " AND (name_rus LIKE '%$search_safe%' OR id LIKE '%$search_safe%')";
    }
    $sql = "SELECT id, name_rus FROM base_pokemons WHERE $where ORDER BY id ASC";
    $res_all = $mysqli->query($sql);

    $a = '<div class="TrainerCollectionPanel">
            <div class="trainer-pokedex-bar">';
    $count = 0;
    while($row = $res_all->fetch_assoc()){
        $caught     = !empty($user_pokemons[$row['id']]['normal']);
        $shiny      = !empty($user_pokemons[$row['id']]['shine']);
        $has_any    = $caught || $shiny;

        // Фильтрация (логика для каждого фильтра)
        if ($filter == 'caught' && !$caught) continue;
        if ($filter == 'shiny' && !$shiny) continue;
        if ($filter == 'uncaught' && $has_any) continue;

        $itemClass = 'trainer-pokebar-item';
        if ($caught) $itemClass .= ' caught';
        if ($shiny) $itemClass .= ' shiny';
        if ($selected_pok !== null && $row['id'] == $selected_pok) $itemClass .= ' selected';

        $a .= '<div class="'.$itemClass.'" onclick="showDexModal('.$row['id'].')">';
        $a .= '<div class="pokebar-card">';
        $a .= '<div class="pokebar-border">';
        $a .= '<img src="/img/pokemons/pokedex/'.$row['id'].'.png" alt="'.$row['name_rus'].'" />';
        $a .= '<div class="trainer-pokename vertical">'.$row['name_rus'].'</div>';
        $a .= '<div class="trainer-pokid">'.$row['id'].'</div>';
        if ($caught) $a .= '<div class="trainer-pokstatus caught" title="Пойман">✓</div>';
        if ($shiny) $a .= '<div class="trainer-pokstatus shiny" title="Шайни">★ Shiny</div>';
        $a .= '</div>'; // pokebar-border
        $a .= '</div>'; // pokebar-card
        $a .= '</div>'; // trainer-pokebar-item
        $count++;
    }
    if ($count == 0) {
        $a .= '<div class="no-pokemons">По вашему запросу ничего не найдено.</div>';
    }
    $a .= '</div>'; // trainer-pokedex-bar
    $a .= '</div>'; // TrainerCollectionPanel

    return $a;
}

// ... (ваш основной обработчик POST)

if(isset($_POST['setTab']) && $_POST['setTab'] == 'trainercollection') {
    $user_id = $_SESSION['id'];
    $filter = $_POST['filter'] ?? 'all';
    $search = trim($_POST['search'] ?? '');
    $selected_pok = isset($_POST['selected_pok']) ? intval($_POST['selected_pok']) : null;
    $response['html'] = getTrainerCollection($user_id, $filter, $search, $selected_pok);
    die(json_encode($response));
}

// ... (остальной switch-case с табами/группами/дексами)
switch($tab) {
    // ... ваши case 1-5

    case 6:
        // Теперь просто используем функцию!
        $user_id = $_SESSION['id'];
        $filter = $_POST['filter'] ?? 'all';
        $search = trim($_POST['search'] ?? '');
        $selected_pok = isset($_POST['selected_pok']) ? intval($_POST['selected_pok']) : null;
        $response['html'] = getTrainerCollection($user_id, $filter, $search, $selected_pok);
        break;

    // ... остальные case
}
// ...
if(isset($_POST['setTab']) && intval($_POST['setTab']) && !empty($_POST['setTab'])){
	$tab = (isset($_POST['setTab']) ? $_POST['setTab'] : 1);
	$pok = (isset($_POST['pok']) ? clearInt($_POST['pok']) : 1);
	
	$form_q = (isset($_POST['form']) ? clearStr($_POST['form']) : "");
  $dex = $mysqli->real_escape_string($_POST['pok']);
	$dex = clearStr($dex);
	if(is_numeric($dex)){
		$pokemon = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` LIKE '%".$dex."%'")->fetch_assoc();
	}elseif(is_string($dex)){
		$pokemon = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `name_rus` LIKE '%".$dex."%'")->fetch_assoc();
	}else{
		exit();
	}
	$pokemonTypeN = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `gym` = 0 and `user_id` != 4 and `basenum` = ".$pokemon['id'])->num_rows;
	$pokemonTypeS = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `gym` = 0 and `user_id` != 4 and `type` = 'shine' AND `basenum` = ".$pokemon['id']." OR `type` = 'shadow' AND `basenum` = ".$pokemon['id']." OR `type` = 'snowy' AND `basenum` = ".$pokemon['id']."")->num_rows;
	$evolutions .= $pokemon['nextEvolution'];
    $ability = $mysqli->query("SELECT * FROM `base_ability_pokemon` WHERE `id` = ".$pokemon['id'])->fetch_assoc();
    if($ability['slot1']){$a1 = '<div onclick="viewDescriptionAbility('.$ability['slot1'].');" class="Red-Color">'.ability_name($ability['slot1']).'</div>';}else{ $a1 = ''; }
    if($ability['slot2']){$a2 = '<div onclick="viewDescriptionAbility('.$ability['slot2'].');" class="Red-Color">'.ability_name($ability['slot2']).'</div>';}else{ $a2 = ''; }
    if($ability['hidden']){$a3 = '<div onclick="viewDescriptionAbility('.$ability['hidden'].');" class="Red-Color">'.ability_name($ability['hidden']).'</div>';}else{ $a3 = ''; }
	$numb = numbPok(floor($pokemon['id']));
	$nalichie = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = ".$_SESSION['id']." AND `basenum` = ".$pokemon['id'])->fetch_assoc();
	$wish = $mysqli->query("SELECT * FROM `user_wish` WHERE `user` = ".$_SESSION['id']." AND `pok` = ".$pokemon['id'])->fetch_assoc();
    if($nalichie){
        $nal = '<br><i class="far fa-check Green-Color"></i> У вас есть этот покемон';
    }else{
        $nal = '';
    }
    if($wish){
        $nal_w = '<br><i class="far fa-check Green-Color"></i> Покемон есть в вашем списке желаний';
        $btn_w = '<div onclick="wishuserpok('.$pokemon['id'].');" class="btn">Убрать из желаний</div>';
    }else{
        $nal_w = '';
        $btn_w = '<div onclick="wishuserpok('.$pokemon['id'].');" class="btn">Добавить в желания</div>';
    }
    if(isset($_POST['setTab']) && $_POST['setTab'] == 'trainercollection') {
    $user_id = $_SESSION['id'];
    $filter = $_POST['filter'] ?? 'all';
    $search = trim($_POST['search'] ?? '');
    $selected_pok = isset($_POST['selected_pok']) ? intval($_POST['selected_pok']) : null;
    $response['html'] = getTrainerCollection($user_id, $filter, $search, $selected_pok);
    die(json_encode($response));
}
    
    $form_bd = $mysqli->query("SELECT * FROM `base_pokemon_forms` WHERE `name` = '".$form_q."' AND `pokemons` = ".$pok)->fetch_assoc();
    
	switch($tab){
		case 1:
		    
      $a .= '
      <div class="DexAbout">'.$pokemon['about'].'</div>
      <div class="DexEvolv">'.$evolutions.'</div>
      <div class="DexbtnEtc">'.$btn_w.'</div>
      <div class="DexEtc">
        В игре: <b>'.$pokemonTypeN.'</b> ( шайни: <b>'.$pokemonTypeS.'</b>)<br> Способности: <small>x</small>'.$a1.' <small>y</small>'.$a2.' <br> Скрытая способность: '.$a3.' '.$nal.' '.$nal_w.'
      </div>';
      $response['html'] = $a;
		break;
	case 2:
    if ($form_bd && $form_bd['start'] != 1) {
        $atkQuery = $mysqli->query("SELECT * FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' AND `type` = 'lvl' AND `form` = '".$form_bd['id_form']."' ")->fetch_assoc();
        if (empty($atkQuery)) {
            $atkQuery = $mysqli->query("SELECT * FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' AND `type` = 'lvl' AND `form` = '0' ")->fetch_assoc();
        }
    } else {
        $atkQuery = $mysqli->query("SELECT * FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' AND `type` = 'lvl' AND `form` = '0' ")->fetch_assoc();
    }

    $attack_lang = $_SESSION['attack_lang'] ?? 'rus'; // Получаем язык атак из сессии/профиля
    $atk_name_col = ($attack_lang === 'eng') ? 'name' : 'name_rus';

    $atkLvl = explode(',', $atkQuery['lvl']);
    $atkList = explode(',', $atkQuery['attacks']);
    if ($atkQuery) {
        for ($i = 0; $i < count($atkLvl); $i++) {
            $info = Work::$sql->query('SELECT
                                        `id`,
                                        `'.$atk_name_col.'` AS `name`,
                                        `title`,
                                        `type`,
                                        `category`,
                                        `priority`,
                                        `power`,
                                        `accuracy`,
                                        `pp`
                                    FROM `base_atk`
                                    WHERE `id` = '.$atkList[$i]
                                )->fetch_assoc();
            $a .= '<div class="Move"><img src="/img/world/typs/'.$info['type'].'.png" onclick=viewDescriptionAttak(this,'.$info['id'].');> <div class="MoveInfo"><div class="Name typec'.$info['category'].'">'.$info['name'].'</div><div class="PP">'.$atkLvl[$i].'</div></div></div>';
        }
    } else {
        $a = ($attack_lang === 'eng')
            ? '<div class="titl noatk">This Pokémon does not learn attacks by level!</div>'
            : '<div class="titl noatk">Покемон не изучает атак по уровню!</div>';
    }
    $response['html'] = $a;
    break;
		case 3:
    // Получаем язык атак пользователя (например, из сессии или профиля)
    $attack_lang = $_SESSION['attack_lang'] ?? 'rus';
    $atk_name_col = ($attack_lang === 'eng') ? 'name' : 'name_rus';

    $atkQuery = $mysqli->query("SELECT * FROM `attac_poke_tm` WHERE `poke_base_id` = '".$pok."' ORDER BY  `tm_id` ASC ");
    if($atkQuery && $atkQuery->num_rows > 0){
        while($atk = $atkQuery->fetch_assoc()) {
            $tm_id = $mysqli->query("SELECT `info`, `tm_id` FROM `base_items` WHERE `tm_id` = '".$atk['tm_id']."'")->fetch_assoc();
            if($tm_id['tm_id'] >= '1000') {
                $q = $tm_id['tm_id']-2000;
                $tm_id['tm_id'] = 'TR '.$q;
            }else{
                $tm_id['tm_id'] = 'TM '.$tm_id['tm_id'];
            }
            $at = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` = '".$tm_id['info']."'")->fetch_assoc();
            $a .= '<div class="Move"><img src="/img/world/typs/'.$at['type'].'.png" onclick=viewDescriptionAttak(this,'.$at['id'].');> <div class="MoveInfo"><div class="Name typec'.$at['category'].'">'.$at[$atk_name_col].'</div><div class="PP">'.$tm_id['tm_id'].'</div></div></div>';
        }
    }else{
        $a = ($attack_lang === 'eng')
            ? '<div class="titl noatk">This Pokémon does not learn TM or TR attacks!</div>'
            : '<div class="titl noatk">Покемон не изучает TM и TR атаки!</div>';
    }
    $response['html'] = $a;
    break;

case 4:
    $attack_lang = $_SESSION['attack_lang'] ?? 'rus';
    $atk_name_col = ($attack_lang === 'eng') ? 'name' : 'name_rus';

    $atkQuery = $mysqli->query("SELECT * FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' AND `type` = 'sex'")->fetch_assoc();
    if($atkQuery){
        $atkList = explode(',',$atkQuery['attacks']);
        for($i=0;$i<count($atkList);$i++){
            $info = Work::$sql->query('SELECT
                        `id`,
                        `'.$atk_name_col.'` AS `name`,
                        `title`,
                        `type`,
                        `category`,
                        `priority`,
                        `power`,
                        `accuracy`,
                        `pp`
                    FROM `base_atk`
                    WHERE `id` = '.$atkList[$i]
                    )->fetch_assoc();
            $a .= '<div class="Move"><img src="/img/world/typs/'.$info['type'].'.png" onclick=viewDescriptionAttak(this,'.$info['id'].');> <div class="MoveInfo"><div class="Name typec'.$info['category'].'">'.$info['name'].'</div><div class="PP"></div></div></div>';
        }
    }else{
        $a = ($attack_lang === 'eng')
            ? '<div class="titl noatk">This Pokémon does not learn breeding attacks!</div>'
            : '<div class="titl noatk">Покемон не изучает атак по разведению!</div>';
    }
    $response['html'] = $a;
    break;
// case 6: Коллекция тренера — фильтры, поиск, красивый вывод

case 6:
        // Теперь просто используем функцию!
        $user_id = $_SESSION['id'];
        $filter = $_POST['filter'] ?? 'all';
        $search = trim($_POST['search'] ?? '');
        $selected_pok = isset($_POST['selected_pok']) ? intval($_POST['selected_pok']) : null;
        $response['html'] = getTrainerCollection($user_id, $filter, $search, $selected_pok);
        break;
		case 5:
    $loc = $mysqli->query("SELECT * FROM `pokemons_location` WHERE `location_id` != 0 AND `basenum` = '".$pok."' AND `hide_loc` != 1 AND `location_id` <= 100 ");
    $a .= "<div class='titl'>Нападает на локациях:</div><br>";
      while($atk = $loc->fetch_assoc()) {
            $location = $mysqli->query("SELECT * FROM `base_location` WHERE `id` = '".$atk['location_id']."'")->fetch_assoc();
            $region = $mysqli->query("SELECT * FROM `base_region` WHERE `id` = '".$location['region']."'")->fetch_assoc();
            $a .= $region['name'].' <i class="fa fa-long-arrow-alt-right"></i> '.$location['name'].'<br>';
          }
			$response['html'] = $a;
		break;
		default:
			$response['error'] = 1;
			$response['text'] = '{"error":1,"file":"Pokedex:33"}';
		break;
	}

	die(json_encode($response));
}
if(isset($_POST['dex'])){
    $pokform = escapeMe($_POST['form']);
	$dex = $mysqli->real_escape_string($_POST['dex']);
	$dex = clearStr($dex);
	if(is_numeric($dex)){
		$pokemon = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` LIKE '%".$dex."%'")->fetch_assoc();
	}elseif(is_string($dex)){
		$pokemon = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `name_rus` LIKE '%".$dex."%'")->fetch_assoc();
	}else{
		exit();
	}
	if($pokemon){
	    $error = 0;
	}else{
	    $error = 1;
	}
	if($error == 0){
	$pokemonTypeN = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `gym` = 0 and `user_id` != 4 and `basenum` = ".$pokemon['id'])->num_rows;
	$pokemonTypeS = $mysqli->query("SELECT `id` FROM `user_pokemons` WHERE `gym` = 0 and `user_id` != 4 and `type` = 'shine' AND `basenum` = ".$pokemon['id']." OR `type` = 'shadow' AND `basenum` = ".$pokemon['id']." OR `type` = 'snowy' AND `basenum` = ".$pokemon['id']."")->num_rows;
	$evolutions .= $pokemon['nextEvolution'];

	$numb = numbPok($pokemon['id']);
	$numv = numbPok(floor($pokemon['id']));
	$fr = $mysqli->query("SELECT * FROM `base_pokemon_forms` WHERE `pokemons` = ".$pokemon['id']." AND `start` = 1")->fetch_assoc();
	if($fr){
	    $form = "".$fr['name'].""; 
          $form_eng = "";
          $click = "clickable"; 
          $func = 'onclick="formDex('.$pokemon['id'].');"';
	}else{
	    $form = ""; $click = ""; $func = ""; $form_eng = "";
	}
	
	
	if($pokform){
      $formsearch = $mysqli->query("SELECT * FROM `base_pokemon_forms` WHERE `pokemons` = ".$pokemon['id']." AND `name` = '".$pokform."' ")->fetch_assoc();
      if($formsearch){
          $pokemon['hp'] = $formsearch['hp'];
          $pokemon['atk'] = $formsearch['atk'];
          $pokemon['def'] = $formsearch['def'];
          $pokemon['spd'] = $formsearch['spd'];
          $pokemon['satk'] = $formsearch['satk'];
          $pokemon['sdef'] = $formsearch['sdef'];
          $pokemon['type'] = $formsearch['type'];
          $pokemon['type_two'] = $formsearch['type_two'];
          $form = "".$formsearch['name'].""; 
          $form_eng = "_".$formsearch['id_form']."";
          $click = "clickable"; 
          $func = 'onclick="formDex('.$pokemon['id'].');"';
      }
  }
  
  $hpbar_top = round(120-((($pokemon['hp']/255)*100)*1.1),2);
$hpbar_left = 135;
$atkbar_top = round(120-((($pokemon['atk']/181)*100)*0.55),2);
$atkbar_left = round(135+((($pokemon['atk']/181)*100)*0.935),2);
$defbar_top = round(120+((($pokemon['def']/230)*100)*0.55),2);
$defbar_left = round(135+((($pokemon['def']/230)*100)*0.935),2);
$spdbar_top = round(120+((($pokemon['spd']/200)*100)*1.1),2);
$spdbar_left = 135;
$satkbar_top = round(120-((($pokemon['satk']/173)*100)*0.55),2);
$satkbar_left = round(135-((($pokemon['satk']/173)*100)*0.935),2);
$sdefbar_top = round(120+((($pokemon['sdef']/230)*100)*0.55),2);
$sdefbar_left = round(135-((($pokemon['sdef']/230)*100)*0.935),2);
  $total = $pokemon['hp']+$pokemon['atk']+$pokemon['def']+$pokemon['spd']+$pokemon['satk']+$pokemon['sdef'];
  
  
  
  
//сравнение
$us = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id'])->fetch_assoc();
$pok_por = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = ".$us['comparison'])->fetch_assoc();
if($us['comparison'] != 0){
$hpbar_top_r = round(120-((($pok_por['hp']/255)*100)*1.1),2);
$hpbar_left_r = 135;
$atkbar_top_r = round(120-((($pok_por['atk']/181)*100)*0.55),2);
$atkbar_left_r = round(135+((($pok_por['atk']/181)*100)*0.935),2);
$defbar_top_r = round(120+((($pok_por['def']/230)*100)*0.55),2);
$defbar_left_r = round(135+((($pok_por['def']/230)*100)*0.935),2);
$spdbar_top_r = round(120+((($pok_por['spd']/200)*100)*1.1),2);
$spdbar_left_r = 135;
$satkbar_top_r = round(120-((($pok_por['satk']/173)*100)*0.55),2);
$satkbar_left_r = round(135-((($pok_por['satk']/173)*100)*0.935),2);
$sdefbar_top_r = round(120+((($pok_por['sdef']/230)*100)*0.55),2);
$sdefbar_left_r = round(135-((($pok_por['sdef']/230)*100)*0.935),2);
   $text_por = '<span class="comparison"> <span class="bgPok selectPok" onclick="openDex('.$pok_por['id'].')"><img src="/img/pokemons/animation/'.numbPok(floor($pok_por['id'])).'.png"> </span></span>';
   
   if($pokemon['id'] == $us['comparison']){ 
       $txt = '<i class="fal fa-not-equal"></i>'; 
        $text_por = '';
   }else{ $txt = '<i class="fal fa-equals"></i>';}
}else{
    $text_por = '';
    $txt = '<i class="fal fa-equals"></i>';
}
  
  
  
  
  
  
  
    $ability = $mysqli->query("SELECT * FROM `base_ability_pokemon` WHERE `id` = ".$pokemon['id'])->fetch_assoc();
    if($ability['slot1']){$a1 = '<div onclick="viewDescriptionAbility('.$ability['slot1'].');" class="Red-Color">'.ability_name($ability['slot1']).'</div>';}else{ $a1 = ''; }
    if($ability['slot2']){$a2 = '<div onclick="viewDescriptionAbility('.$ability['slot2'].');" class="Red-Color">'.ability_name($ability['slot2']).'</div>';}else{ $a2 = ''; }
    if($ability['hidden']){$a3 = '<div onclick="viewDescriptionAbility('.$ability['hidden'].');" class="Red-Color">'.ability_name($ability['hidden']).'</div>';}else{ $a3 = ''; }
    
    if($pokemon['class'] != 'none'){
        if($pokemon['class'] == 'start') { $cl = '<div class="uberness uberness1" onclick="groupDex(this)" data-title="class start">Стартовый</div> ';}
        elseif($pokemon['class'] == 'unique') { $cl = '<div class="uberness uberness2"  onclick="groupDex(this)" data-title="class unique">Уникальный</div>  ';}
        elseif($pokemon['class'] == 'mythical') { $cl = '<div class="uberness uberness3"  onclick="groupDex(this)" data-title="class mythical" >Мифический</div>  ';}
        elseif($pokemon['class'] == 'legendary') { $cl = '<div class="uberness uberness4"   onclick="groupDex(this)" data-title="class legendary">Легендарный</div>  ';}
        elseif($pokemon['class'] == 'beasts') { $cl = '<div class="uberness uberness5"   onclick="groupDex(this)" data-title="class beasts">Ультра-звери</div>  ';}
        elseif($pokemon['class'] == 'ancient') { $cl = '<div class="uberness uberness6"   onclick="groupDex(this)" data-title="class ancient">Древние</div> ';}
        
        elseif($pokemon['class'] == 'paradox') { $cl = '<div class="uberness uberness7"   onclick="groupDex(this)" data-title="class paradox">Парадоксальные</div> ';}
        elseif($pokemon['class'] == 'future') { $cl = '<div class="uberness uberness8"   onclick="groupDex(this)" data-title="class paradox">Роботизированные</div> ';}
    }else{
        $cl = "";
    }
    
    
    $nalichie = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = ".$_SESSION['id']." AND `basenum` = ".$pokemon['id'])->fetch_assoc();
    $wish = $mysqli->query("SELECT * FROM `user_wish` WHERE `user` = ".$_SESSION['id']." AND `pok` = ".$pokemon['id'])->fetch_assoc();
    
    if($nalichie){
        $nal = '<br><i class="far fa-check Green-Color"></i> У вас есть этот покемон';
    }else{
        $nal = '';
    }
    if($wish){
        $nal_w = '<br><i class="far fa-check Green-Color"></i> Покемон есть в вашем списке желаний';
        $btn_w = '<div onclick="wishuserpok('.$pokemon['id'].');" class="btn">Убрать из желаний</div>';
    }else{
        $nal_w = '';
        $btn_w = '<div onclick="wishuserpok('.$pokemon['id'].');" class="btn">Добавить в желания</div>';
    }
        $return = array(
	 'error'=>$error,
	 'id'=>$pokemon['id'],
	 'typeNormal'=>$pokemonTypeN,
	 'typeUnik'=>$pokemonTypeS,
	 'name'=>$pokemon['name_rus'],
	 'numb'=>$numb,
	 'numv'=>$numv,
	 'height'=>$pokemon['height'],
	 'weight'=>$pokemon['weight'],
	 'm'=>$pokemon['sex_m'],
	 'd'=>$pokemon['sex_f'],
	 'go'=>$pokemon['exp_group'],
	 'pwr'=>$pokemon['power_category'],
   'cc'=>$pokemon['chanceCatch'],
	 'hp'=>$pokemon['hp'],
	 'atk'=>$pokemon['atk'],
	 'def'=>$pokemon['def'],
	 'sp'=>$pokemon['spd'],
	 'sa'=>$pokemon['satk'],
	 'sd'=>$pokemon['sdef'],
	 'v_rol'=>$pokemon['v_rol'],
   'hpbar_top'=>$hpbar_top,
   'hpbar_left'=>$hpbar_left,
   'atkbar_top'=>$atkbar_top,
   'atkbar_left'=>$atkbar_left,
   'defbar_top'=>$defbar_top,
   'defbar_left'=>$defbar_left,
   'spdbar_top'=>$spdbar_top,
   'spdbar_left'=>$spdbar_left,
   'satkbar_top'=>$satkbar_top,
   'satkbar_left'=>$satkbar_left,
   'sdefbar_top'=>$sdefbar_top,
   'sdefbar_left'=>$sdefbar_left,
   'hpbar_top_r'=>$hpbar_top_r,
   'hpbar_left_r'=>$hpbar_left_r,
   'atkbar_top_r'=>$atkbar_top_r,
   'atkbar_left_r'=>$atkbar_left_r,
   'defbar_top_r'=>$defbar_top_r,
   'defbar_left_r'=>$defbar_left_r,
   'spdbar_top_r'=>$spdbar_top_r,
   'spdbar_left_r'=>$spdbar_left_r,
   'satkbar_top_r'=>$satkbar_top_r,
   'satkbar_left_r'=>$satkbar_left_r,
   'sdefbar_top_r'=>$sdefbar_top_r,
   'sdefbar_left_r'=>$sdefbar_left_r,
    'a1'=>$a1,
    'a2'=>$a2,
    'a3'=>$a3,
   'total'=>$total,
	 'info'=>$pokemon['about'],
	 'typeOne'=>$pokemon['type'],
	 'typeTwo'=>$pokemon['type_two'],
	 'nal'=>$nal,
	 'btn_w'=>$btn_w,
	 'nal_w'=>$nal_w,
	 'effort'=>$pokemon['effort'],
	 'evolutions'=>$evolutions,
	 'class'=>$cl,
	 'txt'=>$txt,
	 'por'=>$text_por,
	  'form'=>$form,
	  'form_eng'=>$form_eng,
	  'click'=>$click,
	  'func'=>$func
	 );
    }else{
        $return = array(
	 'error'=>$error
	 );
    }
	
	echo json_encode($return);
}
if(isset($_POST['group'])){
    $tpl = '<div class="Dex-Inputs">
        <div onclick="openDex(0)"><i class="fas fa-chevron-left"></i></div>
        <input type="text" placeholder="Найти покемона..." onkeydown="if(event.keyCode == 13){openDex($(this).val());}">
        <div onclick="openDex(1)"><i class="fas fa-chevron-right"></i></div>
    </div>';
    $List = explode(' ',$_POST['group']);
    $tip = $List[0];
    $ser = $List[1];

    if($tip == "tip"){
        $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`type` = "'.$ser.'" OR `type_two` = "'.$ser.'") AND `form` = "Обычный" ORDER BY `id` ASC');
        // Заголовок типа (вне DexList)
        $tpl .= '<div class="type-header"><img src="/img/world/typs/'.$ser.'.png" width="50px"></div>';
        // Список покемонов
        $tpl .= '<div class="DexList">';
        while($abls = $abl->fetch_assoc()){
            $tpl .= '<div class="pokSELECT" onclick="openDex('.$abls['id'].')">
                <img src="/img/pokemons/animation/'.numbPok($abls['id']).'.png">
                #'.numbPok($abls['id']).' '.$abls['name_rus'].'
            </div>';
        }
        $tpl .= '</div>';
    } elseif($tip == "generation") {
        if($ser == 'one'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 1 AND `id` <= 151) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation1">I поколение</div>';
        }elseif($ser == 'two'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 152 AND `id` <= 251) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation2">II поколение</div>';
        }elseif($ser == 'three'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 252 AND `id` <= 386) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation3">III поколение</div>';
        }elseif($ser == 'four'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 387 AND `id` <= 493) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation4">IV поколение</div>';
        }elseif($ser == 'five'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 494 AND `id` <= 649) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation5">V поколение</div>';
        }elseif($ser == 'six'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 650 AND `id` <= 721) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation6">VI поколение</div>';
        }elseif($ser == 'seven'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 722 AND `id` <= 809) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation7">VII поколение</div>';
        }elseif($ser == 'eight'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 810 AND `id` <= 905) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation8">VIII поколение</div>';
        }elseif($ser == 'nine'){
            $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  (`id` >= 906 AND `id` <= 1008) AND `form` = "Обычный" ORDER BY `id` ASC');
            $t = '<div class="generation generation9">IX поколение</div>';
        }
        // Заголовок поколения (вне DexList)
        $tpl .= '<div class="generation-header">'.$t.'</div>';
        // Список покемонов
        $tpl .= '<div class="DexList">';
        while($abls = $abl->fetch_assoc()){
            $tpl .= '<div class="pokSELECT" onclick="openDex('.$abls['id'].')">
                <img src="/img/pokemons/animation/'.numbPok($abls['id']).'.png">
                #'.numbPok($abls['id']).' '.$abls['name_rus'].'
            </div>';
        }
        $tpl .= '</div>';
    } else {
        $abl = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  `class` = "'.$ser.'"  AND `form` = "Обычный" ORDER BY `id` ASC');
        if($ser == "start"){$t = '<div class="uberness uberness1">Стартовый</div>';}
        elseif($ser == "unique"){$t = '<div class="uberness uberness2">Уникальный</div>';}
        elseif($ser == "mythical"){$t = '<div class="uberness uberness3">Мифический</div>';}
        elseif($ser == "legendary"){$t = '<div class="uberness uberness4">Легендарный</div>';}
        elseif($ser == "beasts"){$t = '<div class="uberness uberness5">Ультра-звери</div>';}
        elseif($ser == "ancient"){$t = '<div class="uberness uberness6">Древние</div>';}
        elseif($ser == "paradox"){$t = '<div class="uberness uberness7">Парадоксальные</div>';}
        elseif($ser == "future"){$t = '<div class="uberness uberness8">Роботизированные</div>';}
        // Заголовок класса (вне DexList)
        $tpl .= '<div class="class-header">'.$t.'</div>';
        // Список покемонов
        $tpl .= '<div class="DexList">';
        while($abls = $abl->fetch_assoc()){
            $tpl .= '<div class="pokSELECT" onclick="openDex('.$abls['id'].')">
                <img src="/img/pokemons/animation/'.numbPok($abls['id']).'.png">
                #'.numbPok($abls['id']).' '.$abls['name_rus'].'
            </div>';
        }
        $tpl .= '</div>';
    }

    $response['html'] = $tpl;
    echo json_encode($response);
}

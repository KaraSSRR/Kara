<?php
$_SESSION['plus'] = false;
$_SESSION['echo'] = 0;

//Боллы-начало
function balls_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	Work::$sql->query("UPDATE `user_pokemons` SET `ball` = ".$itemID." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
	$_SESSION['text'] = 'Вы успешно пересадили своего покемона в другой покебол.';
	minus_item($itemID,1);
	lvlupuser(15);
	$_SESSION['error'] = 0;
}
//Боллы-конец
/**
 * Применить скин к конкретному покемону и списать 1 предмет.
 * Формат meta в base_items.str: "apply_pokemon=121;skin_form=star" (+опц. "sprite_type=water")
 * Дополнительно поддерживается тип предмета 'skin_pokemon'.
 */
function pokemon_skin_new($pokID, $itemID) {
    
    $_SESSION['error'] = 0;
    $_SESSION['echo']  = 0;
    $_SESSION['text']  = '';
    $_SESSION['action']= 0;

    $userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
	$db = Work::$sql;
    if (!$userId) {
        $_SESSION['error'] = 1;
        $_SESSION['text']  = 'Пользователь не авторизован.';
        return;
    }

    // Инфо о предмете
    $item = $db->query("SELECT * FROM `base_items` WHERE `id`=".(int)$itemID." LIMIT 1")->fetch_assoc();
    if (!$item) {
        $_SESSION['error'] = 1;
        $_SESSION['text']  = 'Предмет не найден.';
        return;
    }

    // Парсим meta из поля `str`
    $meta = [];
    if (!empty($item['str'])) {
        foreach (explode(';', $item['str']) as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) $meta[trim($kv[0])] = trim($kv[1]);
        }
    }

    $skinForm  = isset($meta['skin_form']) ? $meta['skin_form'] : '';
    $applyNum  = isset($meta['apply_pokemon']) ? (int)$meta['apply_pokemon'] : 0;
    $spriteTyp = isset($meta['sprite_type']) ? $meta['sprite_type'] : '';

    // Мини-валидации
    if ($item['type'] !== 'skin_pokemon' && $skinForm === '') {
        $_SESSION['error'] = 1;
        $_SESSION['text']  = 'У предмета не задана форма скина.';
        return;
    }

    // Сам покемон — проверяем владение и вид
    $poke = $db->query("
        SELECT `id`,`user_id`,`basenum`,`type`,`form`
          FROM `user_pokemons`
         WHERE `id`=".(int)$pokID."
         LIMIT 1
    ")->fetch_assoc();

    if (!$poke || (int)$poke['user_id'] !== $userId) {
        $_SESSION['error'] = 1;
        $_SESSION['text']  = 'Покемон не найден или не принадлежит вам.';
        return;
    }

    if ($applyNum > 0 && (int)$poke['basenum'] !== $applyNum) {
        $_SESSION['error'] = 1;
        $_SESSION['text']  = 'Этот скин нельзя применить к данному виду покемона.';
        return;
    }

    // Определим папку типа для спрайта:
    // 1) приоритет meta sprite_type=..., 2) иначе ищем тип у формы из справочника форм, 3) иначе берём текущий type покемона
    $spriteType = $spriteTyp;
    if ($spriteType === '') {
        if ($skinForm !== '') {
            $row = $db->query(
                "SELECT `type` FROM `base_pokemon_forms_new`
                  WHERE `pokemons`=".(int)$poke['basenum']." AND `id_form`='".$db->real_escape_string($skinForm)."'
                  LIMIT 1"
            )->fetch_assoc();
            if ($row && !empty($row['type'])) {
                $spriteType = $row['type'];
            }
        }
        if ($spriteType === '') {
            $spriteType = $poke['type']; // запасной вариант
        }
    }

    // Применяем: ставим skin_form, skin_sprite_type И form
    $db->begin_transaction();
    try {
        $ok1 = $db->query("
            UPDATE `user_pokemons`
               SET `skin_form` = '".$db->real_escape_string($skinForm)."',
                   `skin_sprite_type` = '".$db->real_escape_string($spriteType)."',
                   `form` = '".$db->real_escape_string($skinForm)."'   -- NEW: меняем боевое поле form
             WHERE `id`=".(int)$poke['id']."
             LIMIT 1
        ");
        if (!$ok1) { throw new Exception('Не удалось применить скин.'); }

        // Списываем 1 шт.
        if (function_exists('minus_item')) {
            minus_item($itemID, 1);
        } else {
            $db->query("UPDATE `items_users` SET `count`=`count`-1 WHERE `user`={$userId} AND `item_id`=".(int)$itemID." AND `count`>0 LIMIT 1");
            $db->query("DELETE FROM `items_users` WHERE `user`={$userId} AND `item_id`=".(int)$itemID." AND `count`<=0");
        }

        $db->commit();

        $_SESSION['text']  = 'Скин применён: форма покемона изменена.';
        $_SESSION['echo']  = 1;
        $_SESSION['error'] = 0;
        $_SESSION['action']= 1;
        $_SESSION['other'] = [
            'pokemon_id' => (int)$poke['id'],
            'skin_form'  => $skinForm,
            'form'       => $skinForm,
            'sprite_type'=> $spriteType
        ];
    } catch (Exception $e) {
        $db->rollback();
        $_SESSION['error'] = 1;
        $_SESSION['text']  = $e->getMessage();
        return;
    }
}

//Зелья-начало
function potion_new($pokID,$itemID,$count){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$max_hp = explode(',',$pokemon['stats']);
	$it = Work::$sql->query("SELECT * FROM `items_users` WHERE `item_id` = '".$itemID."' and `user` = '".$_SESSION['id']."' ")->fetch_assoc();
	if($itemID == 10) $hpPlus = 20*$count;
	elseif($itemID == 11) $hpPlus = 50*$count;
	elseif($itemID == 12) $hpPlus = 100*$count;
	elseif($itemID == 149) $hpPlus = ceil(($max_hp[0]/100)*15)*$count;
	elseif($itemID == 152) $hpPlus = 50*$count;
	elseif($itemID == 160) $hpPlus = 70*$count;
	elseif($itemID == 162) $hpPlus = 20*$count;
	elseif($itemID == 163) $hpPlus = 200*$count;
	elseif($itemID == 164) $hpPlus = 50*$count;
	elseif($itemID == 167) $hpPlus = 30*$count;
	elseif($itemID == 416) $hpPlus = 100*$count;
	elseif($itemID == 318) $hpPlus = 10*$count;
	elseif($itemID == 331) $hpPlus = ceil(($max_hp[0]/100)*10)*$count;
	elseif($itemID == 23) $hpPlus = ceil($max_hp[0]/2);
	elseif($itemID == 24) $hpPlus = $max_hp[0];
	if($pokemon['hp'] == 0){
		if($itemID == 23 or $itemID == 24){
			Work::$sql->query("UPDATE `user_pokemons` SET `hp` = ".$hpPlus." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
			$_SESSION['text'] = 'Покемон пришел в сознание и восстановил '.$hpPlus.' HP.';
			$_SESSION['error'] = 0;
			minus_item($itemID,1);
		}else{
			$_SESSION['text'] = 'Ошибка!';
			$_SESSION['error'] = 1;
		}
	}else{
		if(($itemID == 10 or $itemID == 11 or $itemID == 12 or $itemID == 149 or $itemID == 152 or $itemID == 160 or $itemID == 162 or $itemID == 163 or $itemID == 164 or $itemID == 167  or $itemID == 416  or $itemID == 318  or $itemID == 331) AND $pokemon['hp'] != $max_hp[0]){
			$hp_upd = $pokemon['hp']+$hpPlus;
			if($hp_upd >= $max_hp[0]) $hp_upd = $max_hp[0];
			Work::$sql->query("UPDATE `user_pokemons` SET `hp` = ".$hp_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
			$_SESSION['text'] = 'Покемон успешно восстановил '.$hpPlus.' HP.';
			$_SESSION['error'] = 0;
			if($itemID == 318 or $itemID == 331){
				minus_item_id($it['id'],1);
			}else{
				minus_item($itemID,$count);
			}
		}else{
			$_SESSION['text'] = 'Ошибка!';
			$_SESSION['error'] = 1;
		}
	}
}
//Зелья-конец

//Эволверы-начало
function evol_stones_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$pokemon_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
	$it = Work::$sql->query("SELECT * FROM `items_users` WHERE `item_id` = '".$itemID."' and `user` = '".$_SESSION['id']."' ")->fetch_assoc();
	if($pokemon['id']){
		switch($itemID){

			case 80://Громовой камень
				switch($pokemon['basenum']){
					case 25:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 26;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 26 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 82:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 462;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 462 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 299:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 476;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 476 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 133:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 135;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 135 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 603:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 604;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 604 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 737:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 738;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 738 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 81://Водный камень
				switch($pokemon['basenum']){
					case 61:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 62;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 62 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 90:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 91;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 91 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 120:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 121;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 121 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 133:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 134;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 134 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 271:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 272;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 272 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 515:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 516;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 516 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 550:
						if($pokemon['gender'] == "Мальчик" and $pokemon['form'] == "white"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 902;
							if($pokemon['gender'] == "Мальчик"){$fo = '';}else{$fo = 'female';}
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 902, `form` = '".$fo."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
					break;
					
					
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 82://Лиственный камень
				switch($pokemon['basenum']){
					case 44:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 45;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 45 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 70:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 71;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 71 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 100:
					if($pokemon['form'] == 'hisuian'){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 101;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 101 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
						break;
					case 102:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 103;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 103 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 133:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 470;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 470 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 274:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 275;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 275 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 511:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 512;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 512 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 83://Огненный камень
				switch($pokemon['basenum']){
					case 37:
						if($pokemon['form'] == 0){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 38;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 38 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
						}
					break;
					case 58:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 59;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 59 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 133:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 136;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 136 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 513:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 514;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 514 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 938:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 939;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 939 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 84://Лунный камень
				switch($pokemon['basenum']){
					case 30:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 31;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 31 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 33:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 34;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 34 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 35:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 36;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 36 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 39:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 40;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 40 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 300:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 301;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 301 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 517:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 518;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 518 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 85://Солнечный камень
				switch($pokemon['basenum']){
					case 44:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 188;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 182 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 191:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 192;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 192 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 546:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 547;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 547 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 548:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 549;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 549 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 694:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 695;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 695 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 86://Сумрачный камень
				switch($pokemon['basenum']){
					case 198:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 430;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 430 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 200:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 429;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 429 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 608:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 609;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 609 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 680:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 681;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 681 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 87://Сияющий камень
				switch($pokemon['basenum']){
					case 176:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 468;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 468 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 315:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 407;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 407 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 572:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 573;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 573 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 670:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 671;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 671 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 88://Овальный камень
				switch($pokemon['basenum']){
					case 440:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 113;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 113 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 89://Рассвет камень
				switch($pokemon['basenum']){
					case 281:
						if($pokemon['gender'] == "Мальчик"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 475;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 475 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
					break;
					case 361:
						if($pokemon['gender'] == "Девочка"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 478;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 478 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
					break;
					case 953:
						if($pokemon['lvl'] >= 50){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 954;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 954 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 90://Ледяной камень
				switch($pokemon['basenum']){
					case 27:
						if($pokemon['form'] == 'alola'){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 28;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 28 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
					break;
					case 37:
						if($pokemon['form'] == "alola"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 38;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 38 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
							$_SESSION['other2'] = 1;
						$_SESSION['other'] = 0;
						}
					break;
					case 133:
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 471;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 471 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
					break;
					case 739:
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 740;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 740 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
					break;
					case 554:
						if($pokemon['form'] == "alola"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 555;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 555 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка!';
							$_SESSION['error'] = 1;
						}
					break;
					case 974:
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 975;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 975 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 91://Глубоководный зуб
				switch($pokemon['basenum']){
					case 366:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 367;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 367 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 92://Глубоководная чешуя
				switch($pokemon['basenum']){
					case 366:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 368;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 368 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 93://Острый коготь
				switch($pokemon['basenum']){
					case 215:
					    if($pokemon['form'] == 'hisuian'){
					        $_SESSION['other2'] = 903;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 903, `form` = '' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						
					    }else{
					        $_SESSION['other2'] = 461;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 461 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						
					    }
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 94://Острый клык
				switch($pokemon['basenum']){
					case 207:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 472;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 472 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 95://Жуткая ткань
				switch($pokemon['basenum']){
					case 356:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 477;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 477 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					case 625:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 983;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 983 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 96://Флакон духов
				switch($pokemon['basenum']){
					case 682:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 683;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 683 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 97://Пироженка
				switch($pokemon['basenum']){
					case 684:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 685;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 685 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 98://Протектор
				switch($pokemon['basenum']){
					case 112:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 464;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 464 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 99://Корона
				switch($pokemon['basenum']){
					case 61:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 186;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 186 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item_id($it['id'],1);
					break;
					case 79:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 199;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 199 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item_id($it['id'],1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 100://Броня
				switch($pokemon['basenum']){
					case 95:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 208;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 208 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!'.$it['id'];
						$_SESSION['error'] = 0;
						minus_item_id($it['id'],1);
					break;
					case 123:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 212;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 212 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item_id($it['id'],1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 101://Магмарайзер
				switch($pokemon['basenum']){
					case 126:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 467;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 467 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 102://Электрайзер
				switch($pokemon['basenum']){
					case 125:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 466;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 466 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 103://Перламутровая чешуя
				switch($pokemon['basenum']){
					case 349:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 350;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 350 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 104://Модернизтор
				switch($pokemon['basenum']){
					case 137:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 233;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 233 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 105://Улучшенный модернизтор
				switch($pokemon['basenum']){
					case 233:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 474;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 474 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 106://Чешуя дракона
				switch($pokemon['basenum']){
					case 117:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 230;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 230 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 107://Эволвер счастья
				switch($pokemon['basenum']){
					case 42:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 169;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 169 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 52:
						if($pokemon['happy'] >= 250 and $pokemon['form'] == "alola"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 53;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 53 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 113:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 242;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 242 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 133:
						if($pokemon['happy'] >= 250 and time_game() == "День"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 196;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 196 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}elseif($pokemon['happy'] >= 250 and time_game() == "Ночь"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 197;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 197 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 172:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 25;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 25 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 173:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 35;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 35 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 174:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 39;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 39 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 175:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 176;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 176 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 203:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 981;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 981 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 206:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 982;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 982 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 217:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 901;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 901 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					
					case 298:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 183;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 183 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 406:
						if($pokemon['happy'] >= 250  and time_game() == "День"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 315;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 315 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 427:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 428;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 428 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 433:
						if($pokemon['happy'] >= 250 and time_game() == "Ночь"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 358;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 358 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 446:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 143;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 143 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 447:
						if($pokemon['happy'] >= 250 and time_game() == "День"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 448;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 448 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 527:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 528;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 528 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 541:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 542;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 542 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 772:
						if($pokemon['happy'] >= 250){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 773;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 773 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 872:
						if($pokemon['happy'] >= 250 and time_game() == "Ночь"){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 873;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 873 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 249.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 108://Эволвер знаний
				switch($pokemon['basenum']){
				    case 57:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],562)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 979;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 979 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 108:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],446)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 463;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 463 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 114:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],15)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 465;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 465 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 123:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],415)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 900;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 900 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 133:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],72)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 700;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 700 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 190:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],116)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 424;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 424 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 193:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],15)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 469;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 469 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 221:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],15)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 473;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 473 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 234:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],417)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 899;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 899 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 439:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],322)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 122;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 122 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 686:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],570)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 687;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 687 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 762:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],522)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 763;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 763 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 803:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],124)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 804;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 804 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 852:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],554)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 853;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 853 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					case 922:
						if($pokemon['happy'] >= 100 and Check_Attack($pokemon['id'],604)){
							$_SESSION['other'] = $pokemon['basenum'];
							$_SESSION['other2'] = 923;
							Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 923 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
							$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
							$_SESSION['error'] = 0;
							minus_item($itemID,1);
						}else{
							$_SESSION['text'] = 'Ошибка! Счастье покемона должно быть больше 99 и должна быть выучена определенная атака.';
							$_SESSION['error'] = 1;
							$_SESSION['other'] = 0;
						$_SESSION['other2'] = 1;
						}
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 109://Разбитый чайник
				switch($pokemon['basenum']){
					case 854:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 855;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 855 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 110://Треснувший чайник
				switch($pokemon['basenum']){
					case 854:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 855;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 855 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 111://Сладкое яблоко
				switch($pokemon['basenum']){
					case 840:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 842;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 842 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 112://Тухлое яблоко
				switch($pokemon['basenum']){
					case 840:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 841;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 841 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 113://Ягодная посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = '0' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 114://Бабочка посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = 'ribbon' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 115://Цветочная посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = 'flower' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 116://Черешня посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = 'berry' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 117://Любовная посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = 'love' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 118://Клеверная посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = 'clover' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;


			case 119://Звездная посыпка
				switch($pokemon['basenum']){
					case 868:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 869;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 869, `form` = 'star' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;
			
			case 559://Звездная посыпка
				switch($pokemon['basenum']){
					case 935:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 936;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 936 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;
			case 560://Звездная посыпка
				switch($pokemon['basenum']){
					case 935:
						$_SESSION['other'] = $pokemon['basenum'];
						$_SESSION['other2'] = 937;
						Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = 937 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
						$_SESSION['text'] = ''.$pokemon_base['name_rus'].' удачно эволюционировал!';
						$_SESSION['error'] = 0;
						minus_item($itemID,1);
					break;
					default:
						$_SESSION['other'] = 0;
						$_SESSION['text'] = 'На '.$pokemon_base['name_rus'].' нельзя использовать данный предмет.';
						$_SESSION['error'] = 1;
						$_SESSION['other2'] = 1;
					break;
				}
			break;
			case 1201://Новогодний сундук
				if(function_exists('newyear_event_bootstrap')){ newyear_event_bootstrap(); }
				if(function_exists('newyear_event_on_chest_open')){ newyear_event_on_chest_open($_SESSION['id']); }
				minus_item($itemID,1);
				$t = '<b>Вы открыли Новогодний сундук!</b>';
				$snow = mt_rand(15,35);
				itemAdd(1200,$snow);
				$t .= '<br><img src="/img/world/items/little/1200.png" class="item"> Снежинка <b>х'.$snow.'</b>';

				$chance = mt_rand(1,100);
				if($chance >= 1 and $chance <= 40){
					$rand = mt_rand(80000,200000);
					itemAdd(1,$rand);
					$t .= '<br><img src="/img/world/items/little/1.png" class="item"> Генкар <b>х'.number_format($rand,0,'.','.').'</b>';
				}elseif($chance > 40 and $chance <= 65){
					$rand = mt_rand(6,14);
					itemAdd(62,$rand);
					$t .= '<br><img src="/img/world/items/little/62.png" class="item"> Даркбол <b>х'.$rand.'</b>';
				}elseif($chance > 65 and $chance <= 80){
					$rand = mt_rand(5,10);
					itemAdd(53,$rand);
					$t .= '<br><img src="/img/world/items/little/53.png" class="item"> Ультрабол <b>х'.$rand.'</b>';
				}elseif($chance > 80 and $chance <= 92){
					$rand = mt_rand(1,2);
					itemAdd(1202,$rand);
					$t .= '<br><img src="/img/world/items/little/1202.png" class="item"> Фейерверк <b>х'.$rand.'</b>';
				}else{
					$rand = 1;
					itemAdd(1201,$rand);
					$t .= '<br><img src="/img/world/items/little/1201.png" class="item"> Новогодний сундук <b>х'.$rand.'</b>';
				}
				lvlupuser(30);
				battlepass_exp(30);
				$_SESSION['text'] = $t;
				$_SESSION['error'] = 0;
			break;

			case 1202://Фейерверк
				if(function_exists('newyear_event_bootstrap')){ newyear_event_bootstrap(); }
				if(function_exists('newyear_event_on_firework_use')){ newyear_event_on_firework_use($_SESSION['id']); }
				minus_item($itemID,1);
				$t = '<b>Вы запустили фейерверк!</b><br>Пусть удача будет на вашей стороне!';
				$snow = mt_rand(5,15);
				itemAdd(1200,$snow);
				$t .= '<br><img src="/img/world/items/little/1200.png" class="item"> Снежинка <b>х'.$snow.'</b>';
				$rand = mt_rand(30000,80000);
				itemAdd(1,$rand);
				$t .= '<br><img src="/img/world/items/little/1.png" class="item"> Генкар <b>х'.number_format($rand,0,'.','.').'</b>';
				lvlupuser(15);
				battlepass_exp(15);
				$_SESSION['text'] = $t;
				$_SESSION['error'] = 0;
			break;

			default:
				$_SESSION['text'] = 'Ошибка!';
			break;
		}
	}
	if($_SESSION['error'] == 0){
	    $updateName = Work::$sql->query('SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '.$_SESSION['other2'])->fetch_assoc();

	    $updateNamessss = Work::$sql->query('SELECT * FROM `user_pokemons` WHERE `id` = '.$pokID)->fetch_assoc();



                                      if($updateNamessss['ability_slot'] != 0){
                                      $Ability = Work::$sql->query('SELECT * FROM `base_ability_pokemon` WHERE id = '.$_SESSION['other2'])->fetch_assoc();

                                      if($updateNamessss['ability_slot'] != 3){
                                        if($updateNamessss['ability_slot'] == 1) {
                                          $AbilityNow = $Ability['slot1'];
                                          $Slot = 1;
                                        }elseif($updateNamessss['ability_slot'] == 2){
                                          $Slot = 2;
                                          $AbilityNow = $Ability['slot2'];
                                        }
                                        //var_dump($AbilityNow);
                                        if($Ability['slot2'] == 0 and $Slot == 2){
                                          $Slot = 1;
                                          $AbilityNow = $Ability['slot1'];
                                        }
                                      }
                                      if($updateNamessss['ability_slot'] == 3 && $Ability['hidden'] != "0") {
                                        $AbilityNow = $Ability['hidden'];
                                        $Slot = 3;
                                      }

                                          addAbilityPok($pokID,$AbilityNow,$Slot);
                                      }


	Work::$sql->query("UPDATE `user_pokemons` SET `name_new` = '".$updateName['name_rus']."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
		lvlupuser(100);
		battlepass_exp(50);

	if(check_mission(22)){ add_mission(22);}
	}
	return $_SESSION['text'];
}
//Эволверы-конец

//Зелья-pp-начало
function potion_pp_new($pokID,$itemID,$count){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$it = Work::$sql->query("SELECT * FROM `items_users` WHERE `item_id` = '".$itemID."' and `user` = '".$_SESSION['id']."' ")->fetch_assoc();
	$atk = explode(',',$pokemon['attacks']);
	$pp = explode(',',$pokemon['pp_attacks']);
	if($itemID == 14) $ppPlus = 5*$count;
	elseif($itemID == 15) $ppPlus = 10*$count;
	elseif($itemID == 16) $ppPlus = 20*$count;
	elseif($itemID == 313) $ppPlus = 10;
	if($atk[0] != 0){
		$atk1 = Work::$sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atk[0])->fetch_assoc();
		$pp1_upd = $pp[0]+$ppPlus;
		if($atk1['pp'] <= $pp1_upd) $pp1_upd = $atk1['pp'];
	}else{
		$pp1_upd = 0;
	}
	if($atk[1] != 0){
		$atk2 = Work::$sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atk[1])->fetch_assoc();
		$pp2_upd = $pp[1]+$ppPlus;
		if($atk2['pp'] <= $pp2_upd) $pp2_upd = $atk2['pp'];
	}else{
		$pp2_upd = 0;
	}
	if($atk[2] != 0){
		$atk3 = Work::$sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atk[2])->fetch_assoc();
		$pp3_upd = $pp[2]+$ppPlus;
		if($atk3['pp'] <= $pp3_upd) $pp3_upd = $atk3['pp'];
	}else{
		$pp3_upd = 0;
	}
	if($atk[3] != 0){
		$atk4 = Work::$sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atk[3])->fetch_assoc();
		$pp4_upd = $pp[3]+$ppPlus;
		if($atk4['pp'] <= $pp4_upd) $pp4_upd = $atk4['pp'];
	}else{
		$pp4_upd = 0;
	}
	$pp_upd = $pp1_upd.','.$pp2_upd.','.$pp3_upd.','.$pp4_upd;
	Work::$sql->query("UPDATE `user_pokemons` SET `pp_attacks` = '".$pp_upd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
	$_SESSION['text'] = 'Покемон успешно восстановил '.$ppPlus.'PP всем атакам.';
	$_SESSION['error'] = 0;
	if($itemID == 313){
		minus_item_id($it['id'],1);
	}else{
		minus_item($itemID,$count);
	}
}
//Зелья-pp-конец

function sfere($pokID,$itemID,$count){
    $pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$pok_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
	if($itemID == 552){
	    $exp = 150;
	    $hap = 5;
	}elseif($itemID == 553){
	    $exp = 300;
	    $hap = 10;
	}elseif($itemID == 554){
	    $exp = 600;
	    $hap = 20;
	}elseif($itemID == 555){
	    $exp = 1500;
	    $hap = 50;
	}elseif($itemID == 556){
	    $exp = 5000;
	    $hap = 100;
	}
	$a ='.';
	
    if($pokemon['lvl'] < 100){
        $dop = $exp*$count;
	    $expirience = $pokemon['exp']+$dop;
	    $exp_to = $pokemon['exp_max'];
	    $lvl = $pokemon['lvl'];
	    $ev = 0;
	    $happy = $pokemon['happy'];
	    $col = 1;
	    if($expirience >= $pokemon['exp_max']){
	        $vc = Info::_getExp(100, $pok_base['exp_group']);
	        if($vc > $expirience){
        	    while($expirience >= $exp_to){
        	        if($lvl < 100){
        	            $lvl = $lvl+1;
        	            $exp_to = Info::_getExp(($lvl), $pok_base['exp_group']);
        	            $ev = $ev+6;
        	        }else{
        	            $_SESSION['text'] = 'Нельзя использовать столько за раз';
    	                $_SESSION['error'] = 1;
    	                break;
        	        }
    	        }
    	        if($pokemon['happy'] != 0){
    	            $happy = $pokemon['happy']-$hap*$count;
    	            if($happy < 0){ $happy = 0;}
    	            $a = ', а также снизил показатель счастья до '.$happy.'!';
    	        }
    	        $_SESSION['text'] = 'Ваш покемон получил '.number_format($dop,0,'.','.').' опыта, повысил свой уровень до '.$lvl.', получил '.$ev.'ev'.$a;
    	        $_SESSION['error'] = 0;
    	        $ev = $ev+$pokemon['ev'];
    	        minus_item($itemID,$count);
    	        Work::$sql->query("UPDATE `user_pokemons` SET  `exp` = '".$expirience."',`exp_max` = '".$exp_to."',`lvl` = '".$lvl."',`ev` = '".$ev."', `happy`='".$happy."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
	        }else{
	            $_SESSION['text'] = 'Нельзя использовать столько за раз'.$pok_base['exp_group'].'-'.$vc.'-'.$expirience;
    	        $_SESSION['error'] = 1;
	        }
	    }else{
	        if($pokemon['happy'] != 0){
	            $happy = $pokemon['happy']-$hap*$count;
	            if($happy < 0){ $happy = 0;}
	            $a = ', а также снизил показатель счастья до '.$happy.'!';
	        }
	        $_SESSION['text'] = 'Ваш покемон получил '.number_format($dop,0,'.','.').' опыта'.$a;
	        $_SESSION['error'] = 0;
	        minus_item($itemID,$count);
	        Work::$sql->query("UPDATE `user_pokemons` SET  `exp` = '".$expirience."', `happy`='".$happy."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
	    }
	    
	    
	}else{
	    $_SESSION['text'] = 'Уровень вашего покемона максимальный';
	    $_SESSION['error'] = 1;
	}
}


// while($exp_upd >= $exp_to) {
//     $lvl = $lvl+1;
//     $exp_upd = $exp_upd - $exp_to;
//     $exp_to = ($lvl*($lvl/2))*30;
//   }

//Цветные конфеты-начало
function candy_color_new($pokID,$itemID,$count){
	$a = ".";
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$pok_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
	if($itemID == 26) $ev = 0;
	elseif($itemID == 27) $ev = 1;
	elseif($itemID == 28) $ev = 2;
	elseif($itemID == 29) $ev = 3;
	elseif($itemID == 30) $ev = 4;
	elseif($itemID == 31) $ev = 5;
		$c = $pokemon['lvl']+$count;
		if($c <= 100){
			$ev_upd_f = $count*$ev;
			$exp = 3*$count;
			$ev_upd = $count*$ev+$pokemon['ev'];
			$expirience = Info::_getExp(($c-1), $pok_base['exp_group']);
		$expirienceMax = Info::_getExp(($c), $pok_base['exp_group']);
	Work::$sql->query("UPDATE `user_pokemons` SET `lvl` = '".$c."', `exp` = '".$expirience."', `exp_max`='".$expirienceMax."',`ev` = '".$ev_upd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
			if($itemID == 30  and $pokemon['trade'] == "true"){
				$rand = mt_rand(1,100);
				if($rand <= 50){
					$tr = "false";
					Work::$sql->query("UPDATE `user_pokemons` SET `trade` = '".$tr."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
					$a = ", а так же стал непередаваемым!";
				}
			}
			if($itemID == 31 and $pokemon['trade'] == "true"){
					$tr = "false";
					Work::$sql->query("UPDATE `user_pokemons` SET `trade` = '".$tr."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
					$a = ", а так же стал непередаваемым!";
			}
			minus_item($itemID,$count);

	if(check_mission(18)){ add_mission(18,$count);}

			if(check_mission_ivent(36)){ add_mission_ivent(36,$count);}
			lvlupuser($exp);
			battlepass_exp($exp);
			$_SESSION['text'] = 'Покемон успешно повысил уровень до '.$c.' и получил '.$ev_upd_f.'ev'.$a;
			$_SESSION['error'] = 0;
		}else{
			$_SESSION['text'] = 'Нельзя использовать такое колчество предметов за раз!';
			$_SESSION['error'] = 1;
		}
}
//Цветные конфеты-конец

//Шоколадная конфета-начало
function candy_chocolate_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($st[0] == 0){
    	$pok_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
    	if($pokemon['lvl'] != 100){
    		Work::$sql->query("UPDATE `user_pokemons` SET `lvl` = 100, `ev` = 297,`evcounts`= '0,0,0,0,0,0', `vitamines` = 0  WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    		minus_item($itemID,1);
    		lvlupuser(150);
    		battlepass_exp(250,4);
    		if(check_mission(18)){ add_mission(18);}
    // 		if(check_mission_ivent(27)){ add_mission_ivent(27,$count);}
    		$_SESSION['text'] = 'Покемон успешно повысил уровень до 100 и получил 297ev';
    		$_SESSION['error'] = 0;
            $st[0] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    	}else{
    		$_SESSION['text'] = 'Нельзя использовать эту конфету на покемона 100 уровня!';
    		$_SESSION['error'] = 1;
    	}
	}else{
		$_SESSION['text'] = 'На этого покемона уже была использована конфета!';
		$_SESSION['error'] = 1;
	}
}
//Шоколадная конфета-конец

//Горькая конфета-начало
function candy_bitter_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($st[1] == 0){
	$pok_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
		if($pokemon['lvl'] != 1){
			$expirienceMax = Info::_getExp(1, $pok_base['exp_group']);
			$pok_base_egg = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pok_base['eggBasenum']."'")->fetch_assoc();
			if($pokemon['name_new'] == $pok_base['name_rus']){
			    $name = $pok_base_egg['name_rus'];
			}else{
			    $name = $pokemon['name_new'];
			}
			
			Work::$sql->query("UPDATE `user_pokemons` SET `basenum` = ".$pok_base_egg['id'].",`name_new` = '".$name."', `lvl` = 1, `ev` = 6, `hp` = 0, `evcounts`= '0,0,0,0,0,0', `vitamines` = 0, `happy` = 0, `flings` = 0, `stat_pl_mn` =  '0,0,0,0,0,0',`attacks` =  '0,0,0,0',`pp_attacks` =  '0,0,0,0,0,0', `exp` = 1, `exp_max` = '".$expirienceMax."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
			minus_item($itemID,1);
			lvlupuser(50);
			if(check_mission(18)){ add_mission(18);}
// 			if(check_mission_ivent(27)){ add_mission_ivent(27);}
			$_SESSION['text'] = 'Покемон успешно понизил уровень до 1 и получил 6ev!';
			$_SESSION['error'] = 0;
			$st[1] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		}else{
			$_SESSION['text'] = 'Нельзя использовать эту конфету на покемона 1 уровня!';
			$_SESSION['error'] = 1;
		}
	}else{
		$_SESSION['text'] = 'На этого покемона уже была использована конфета!';
		$_SESSION['error'] = 1;
	}
}
//Горькая конфета - конец

//Черная конфета-начало
function candy_black_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$r = mt_rand(1,26);
		Work::$sql->query("UPDATE `user_pokemons` SET `character` = '".$r."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		minus_item($itemID,1);
		lvlupuser(50);
		$_SESSION['text'] = 'Покемон успешно изменил свой характер на '.haracter_pokes($r).'!';
		$_SESSION['error'] = 0;

}
//Черная конфета - конец

//Цветные конфеты-начало
function candy_type_new($pokID,$itemID,$count){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$pok_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
	if($itemID == 35 AND ($pok_base['type'] == 'electric' or $pok_base['type_two'] == 'electric')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 36 AND ($pok_base['type'] == 'fire' or $pok_base['type_two'] == 'fire')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 37 AND ($pok_base['type'] == 'steel' or $pok_base['type_two'] == 'steel')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 38 AND ($pok_base['type'] == 'rock' or $pok_base['type_two'] == 'rock')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 39 AND ($pok_base['type'] == 'fairy' or $pok_base['type_two'] == 'fairy')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 40 AND ($pok_base['type'] == 'fighting' or $pok_base['type_two'] == 'fighting')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 41 AND ($pok_base['type'] == 'dragon' or $pok_base['type_two'] == 'dragon')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 42 AND ($pok_base['type'] == 'water' or $pok_base['type_two'] == 'water')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 43 AND ($pok_base['type'] == 'ice' or $pok_base['type_two'] == 'ice')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 44 AND ($pok_base['type'] == 'fly' or $pok_base['type_two'] == 'fly')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 45 AND ($pok_base['type'] == 'ghost' or $pok_base['type_two'] == 'ghost')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 46 AND ($pok_base['type'] == 'poison' or $pok_base['type_two'] == 'poison')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 47 AND ($pok_base['type'] == 'grass' or $pok_base['type_two'] == 'grass')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 48 AND ($pok_base['type'] == 'ground' or $pok_base['type_two'] == 'ground')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 49 AND ($pok_base['type'] == 'dark' or $pok_base['type_two'] == 'dark')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 50 AND ($pok_base['type'] == 'normal' or $pok_base['type_two'] == 'normal')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 51 AND ($pok_base['type'] == 'bug' or $pok_base['type_two'] == 'bug')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	elseif($itemID == 52 AND ($pok_base['type'] == 'psychic' or $pok_base['type_two'] == 'psychic')) {$ev = 3*$count; $happy_upd = $pokemon['happy']+2*$count; }
	else{ $ev = 2*$count;  $happy_upd = $pokemon['happy']-2*$count; $bp = 1;}
	if($happy_upd >= 255) { $happy_upd = 255;}
	if($happy_upd <= 0) { $happy_upd = 0;}
		$c = $pokemon['lvl']+$count;
		if($c <= 100){
			$exp = 3*$count;
			$ev_upd = $ev+$pokemon['ev'];
			$expirience = Info::_getExp(($c-1), $pok_base['exp_group']);
		$expirienceMax = Info::_getExp(($c), $pok_base['exp_group']);
	Work::$sql->query("UPDATE `user_pokemons` SET `lvl` = '".$c."', `happy` = '".$happy_upd."', `exp` = '".$expirience."', `exp_max`='".$expirienceMax."',`ev` = '".$ev_upd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
			minus_item($itemID,$count);
			lvlupuser($exp);
			update_achiv(7,$count);
			if($bp == 1){
			    
			}else{
			    $exp1 = 30*$count;
			    battlepass_exp(30,3);
			    if(check_mission_ivent(15)){ add_mission_ivent(15,$count);}
			}
			
			if(check_mission(18)){ add_mission(18,$count);}
// 			if(check_mission_ivent(27)){ add_mission_ivent(27,$count);}
			$_SESSION['text'] = 'Покемон успешно повысил уровень до '.$c.' и получил '.$ev.'ev.';
			$_SESSION['error'] = 0;
		}else{
			$_SESSION['text'] = 'Нельзя использовать такое колчество предметов за раз!';
			$_SESSION['error'] = 1;
		}
}
//Цветные конфеты-конец

//Нектар - начало
function nectar_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	if($pokemon['basenum'] == 741){
		if($itemID == 120) {$okr = "sensu";}
		if($itemID == 121) {$okr = "baile";}
		if($itemID == 122) {$okr = "pompom";}
		if($itemID == 123) {$okr = "pau";}
		Work::$sql->query("UPDATE `user_pokemons` SET `form` = '".$okr."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		minus_item($itemID,1);
		$_SESSION['text'] = 'Орикорио успешна изменила форму!';
		$_SESSION['error'] = 0;
	}else{
		$_SESSION['text'] = 'Можно использовать только на #741 Орикорио!';
		$_SESSION['error'] = 1;
	}
}
//Нектар - конец

//Именной бланк- начало
function personal_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	if($pokemon['startGame'] == 0 and $pokemon['tren'] != 6){
		$r = mt_rand(1,100);
		if($r <= 40){
    			if($pokemon['trade'] == "false"){
    				Work::$sql->query("UPDATE `user_pokemons` SET `trade` = 'true' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    				$_SESSION['text'] = 'Покемон успешно изменил свою прирученность! Теперь его можно передавать!';
    			}else{
    				Work::$sql->query("UPDATE `user_pokemons` SET `trade` = 'false' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    				$_SESSION['text'] = 'Покемон успешно изменил свою прирученность! Теперь его нельзя передавать!';
    			}
    			minus_item($itemID,1);
    			$_SESSION['error'] = 0;
    			$_SESSION['other'] = 1;
		}else{
			$_SESSION['text'] = 'Покемон не стал менять свою прирученность!';
			$_SESSION['error'] = 0;
			$_SESSION['other'] = 0;
			minus_item($itemID,1);
		}
	}else{
		$_SESSION['text'] = 'У этого покемона нельзя менять прирученность!';
		$_SESSION['error'] = 1;
	}
}
//Именной бланк - конец

//Набор тренировки - начало
function training_new($pokID,$itemID){

    $u234 = Work::$sql->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$pokemon_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
	$rand = mt_rand(1,100);
	$rand_stat = mt_rand(1,5);
	if($rand_stat == 1){
		$a = 'Атаки';
	}elseif($rand_stat == 2){
		$a = 'Защиты';
	}elseif($rand_stat == 3){
		$a = 'Скорости';
	}elseif($rand_stat == 4){
		$a = 'Спец. Атаки';
	}else{
		$a = 'Спец. Защиты';
	}
	if($u234['lvl'] >= 1){
	if(item_isset($itemID,1)){
		if($pokemon['tren'] == 0){
			if($rand <= 90){
			    $happy_upd = $pokemon['happy']+10;
	if($happy_upd >= 255) { $happy_upd = 255;}
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 1, `tren_stat` = ".$rand_stat." , `happy` = ".$happy_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешная тренировка! '.$pokemon_base['name_rus'].' натренировал стат '.$a.' до начальной тренировки!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачная тренировка! '.$pokemon_base['name_rus'].' не натренировал стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 1){
			if($rand <= 55){
			    $happy_upd = $pokemon['happy']+20;
	if($happy_upd >= 255) { $happy_upd = 255;}
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 2, `tren_stat` = ".$rand_stat." , `happy` = ".$happy_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешная тренировка! '.$pokemon_base['name_rus'].' натренировал стат '.$a.' до расширенной тренировки!';
				$_SESSION['error'] = 0;
				$_SESSION['action'] = 'updateTeam';
	  		$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачная тренировка! '.$pokemon_base['name_rus'].' не натренировал стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 2){
			if($rand <= 10){
			    $happy_upd = $pokemon['happy']+30;
	if($happy_upd >= 255) { $happy_upd = 255;}
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 3, `tren_stat` = ".$rand_stat.", `happy` = ".$happy_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешная тренировка! '.$pokemon_base['name_rus'].' натренировал стат '.$a.' до мастерской тренировки!';
				$_SESSION['error'] = 0;
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачная тренировка! '.$pokemon_base['name_rus'].' не натренировал стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
			if(check_mission_ivent(30)){ add_mission_ivent(30);}
		}else if($pokemon['tren'] == 3){
			if($rand <= 6){
			    $happy_upd = $pokemon['happy']+40;
	if($happy_upd >= 255) { $happy_upd = 255;}
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 4, `tren_stat` = ".$rand_stat.", `happy` = ".$happy_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешная тренировка! '.$pokemon_base['name_rus'].' натренировал стат '.$a.' до знаменитой тренировки!';
				$_SESSION['error'] = 0;
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачная тренировка! '.$pokemon_base['name_rus'].' не натренировал стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 4){
			if($rand <= 3){
			    $happy_upd = $pokemon['happy']+50;
	if($happy_upd >= 255) { $happy_upd = 255;}
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 5, `tren_stat` = ".$rand_stat.", `happy` = ".$happy_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешная тренировка! '.$pokemon_base['name_rus'].' натренировал стат '.$a.' до легендарной тренировки!';
				$_SESSION['error'] = 0;
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачная тренировка! '.$pokemon_base['name_rus'].' не натренировал стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 5){
			if($rand <= 1){
			    $happy_upd = $pokemon['happy']+60;
	if($happy_upd >= 255) { $happy_upd = 255;}
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 6, `tren_stat` = ".$rand_stat.", `trade` = 'false', `happy` = ".$happy_upd." WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешная тренировка! '.$pokemon_base['name_rus'].' натренировал стат '.$a.' до именной тренировки! Покемон стал непередаваемым!';
				$_SESSION['error'] = 0;
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['other'] = 1;
				update_achiv(13,1);
			}else{
				$_SESSION['text'] = 'Неудачная тренировка! '.$pokemon_base['name_rus'].' не натренировал стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else{
			$_SESSION['text'] = ''.$pokemon_base['name_rus'].' уже полностью натренерован!';
			$rand_stat = 0;
			$_SESSION['error'] = 0;
			$_SESSION['other'] = 0;
		}
	    if($pokemon['tren'] != 6){
	       $t = explode(',',$pokemon['tren_log']);
	       $i = $pokemon['tren'];
		    $t[$i] += 1;
		    $tupd = implode(',',$t);
		
		Work::$sql->query("UPDATE `user_pokemons` SET `tren_log` = '".$tupd."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
	}
	}
	}else{
	    $_SESSION['text'] = 'Вам еще нельзя использовать этот предмет!';
		$_SESSION['error'] = 0;
		$_SESSION['other'] = 0;
	}
	if($_SESSION['other'] == 1){
		lvlupuser(15);
		update_achiv(25,1);
		
				
	}
	return $_SESSION['text'];
}
//Набор тренировки - конец

//Набор ослаблений - начало
function training_low_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$pokemon_base = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."'")->fetch_assoc();
	$rand = mt_rand(1,100);
	if($pokemon['tren_stat'] == 1){
		$a = 'Атаки';
	}elseif($pokemon['tren_stat'] == 2){
		$a = 'Защиты';
	}elseif($pokemon['tren_stat'] == 3){
		$a = 'Скорости';
	}elseif($pokemon['tren_stat'] == 4){
		$a = 'Спец. Атаки';
	}else{
		$a = 'Спец. Защиты';
	}
	if(item_isset($itemID,1)){
		if($pokemon['tren'] == 6){
			if($rand <= 90){
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 5 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешное ослабление! '.$pokemon_base['name_rus'].' ослабил стат '.$a.' до легендарной тренировки!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачное ослабление! '.$pokemon_base['name_rus'].' не ослабил стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 5){
			if($rand <= 55){
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 4 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешное ослабление! '.$pokemon_base['name_rus'].' ослабил стат '.$a.' до знаменитой тренировки!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачное ослабление! '.$pokemon_base['name_rus'].' не ослабил стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 4){
			if($rand <= 10){
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 3 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешное ослабление! '.$pokemon_base['name_rus'].' ослабил стат '.$a.' до мастерсокй тренировки!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачное ослабление! '.$pokemon_base['name_rus'].' не ослабил стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 3){
			if($rand <= 6){
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 2 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешное ослабление! '.$pokemon_base['name_rus'].' ослабил стат '.$a.' до расширенной тренировки!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачное ослабление! '.$pokemon_base['name_rus'].' не ослабил стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 2){
			if($rand <= 3){
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 1 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешное ослабление! '.$pokemon_base['name_rus'].' ослабил стат '.$a.' до начальной тренировки!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачное ослабление! '.$pokemon_base['name_rus'].' не ослабил стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else if($pokemon['tren'] == 1){
			if($rand <= 1){
				Work::$sql->query("UPDATE `user_pokemons` SET `tren` = 0, `tren_stat` = 0 WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
				$_SESSION['text'] = 'Успешное ослабление! '.$pokemon_base['name_rus'].' полностью ослабил стат!';
				$_SESSION['action'] = 'updateTeam';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 1;
			}else{
				$_SESSION['text'] = 'Неудачное ослабление! '.$pokemon_base['name_rus'].' не ослабил стат!';
				$_SESSION['error'] = 0;
				$_SESSION['other'] = 0;
			}
			minus_item($itemID,1);
		}else{
			$_SESSION['text'] = ''.$pokemon_base['name_rus'].' не имеет тренировок!';
			$rand_stat = 0;
			$_SESSION['error'] = 0;
			$_SESSION['other'] = 0;
		}
	}
	if($_SESSION['other'] == 1){
		lvlupuser(20);
	}
	return $_SESSION['text'];
}
//Набор ослаблений - конец

//Витамины - начало
function vitamines_new($pokID,$itemID,$count){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	if($itemID == 199){#hp
		$stat = 0;
	}elseif($itemID == 200){#atk
		$stat = 1;
	}elseif($itemID == 201){#def
		$stat = 2;
	}elseif($itemID == 202){#spd
		$stat = 3;
	}elseif($itemID == 203){#satk
		$stat = 4;
	}elseif($itemID == 204){#sdef
		$stat = 5;
	}
	$ev = explode(',',$pokemon['evcounts']);
	$i = 1;
	$lol = $ev[$stat] + ($count*2);
	$acxz = $count*2;
	update_achiv(21,$acxz);
	$exp = 10*$count;
	$lol2 = $pokemon['vitamines'] + ($count*2);
	if($ev[$stat] >= 125){
		$_SESSION['text'] = 'Количество EV на данном стате достигло максимума.';
		$_SESSION['error'] = 1;
	}elseif($lol > 126){
		$_SESSION['text'] = 'Невозможно использовать столько за один раз.';
		$_SESSION['error'] = 1;
	}elseif($pokemon['vitamines'] >= 99){
		$_SESSION['text'] = 'На данного покемона больше нельзя использовать витамины.';
		$_SESSION['error'] = 1;
	}elseif($lol2 > 100){
		$_SESSION['text'] = 'Невозможно использовать столько за один раз.';
		$_SESSION['error'] = 1;
	}else{
		while ($i <= $count){
			$ev[$stat] = $ev[$stat] + 2;
			$evcounts =  implode(',',$ev);
			$a = 2*$count;
			
			$vitaminka = $a+$pokemon['vitamines'];
			Work::$sql->query("UPDATE `user_pokemons` SET `evcounts` = '".$evcounts."', `vitamines` = '".$vitaminka."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
			$_SESSION['error'] = 0;
			$_SESSION['text'] = 'Очки EV успешно добавлены!';
			minus_item($itemID,1);

	if(check_mission(20)){ add_mission(20,$count);}
			$i++;
		}
		if(check_mission_ivent(39)){ add_mission_ivent(39,$count);}
		$lol3 = $pokemon['happy'] + ($count*3);
		if($lol3 > 255){
			$happy = 255;
		}else{
			$happy = $lol3;
		}
			lvlupuser($exp);

		Work::$sql->query("UPDATE `user_pokemons` SET `happy` = '".$happy."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
	}
}
//Витамины - конец

//Капсула способностей - начало
function capsule_ability_new($pokID,$itemID){
    $pokemon = Work::$sql->query("SELECT `ability`,`ability_slot`,`basenum` FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
    if($pokemon){
    	if($pokemon['ability'] != 0){
    	    $abil_bd = Work::$sql->query("SELECT * FROM `base_ability_pokemon` WHERE `id` = '".$pokemon['basenum']."' ")->fetch_assoc();
    	    if($pokemon['ability_slot'] == 1 or $pokemon['ability_slot'] == 2){
    	        if($pokemon['ability_slot'] == 1){
    	            if($abil_bd['slot2'] != 0){
    	                Work::$sql->query("UPDATE `user_pokemons` SET `ability` = '".$abil_bd['slot2']."', `ability_slot` = '2' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
    	                $_SESSION['text'] = 'Покемон успешно сменил способность!';
    	                $_SESSION['error'] = 0;
    	                minus_item($itemID,1);
    	            }else{
    	                $_SESSION['text'] = 'У покемона нет возможности сменить способность!';
    	                $_SESSION['error'] = 1;
    	            }
    	        }else{
    	            Work::$sql->query("UPDATE `user_pokemons` SET `ability` = '".$abil_bd['slot1']."', `ability_slot` = '1' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
    	            $_SESSION['text'] = 'Покемон успешно сменил способность!';
    	                $_SESSION['error'] = 0;
    	                minus_item($itemID,1);
    	        }
    	    }else{
    	        $_SESSION['text'] = 'Покемон не может сменить скрытую способность!';
    	        $_SESSION['error'] = 1;
    	    }
    	}else{
    	    $_SESSION['text'] = 'У покемона нет способности';
    	    $_SESSION['error'] = 1;
    	}
    }else{
        $_SESSION['text'] = 'Системная ошибка';
	    $_SESSION['error'] = 1;
    }
}
//Капсула способностей - конец

//Таблетка способностей - начало
function tablet_ability_new($pokID,$itemID){
	$_SESSION['text'] = 'Данная функция сейчас недоступна';
	$_SESSION['error'] = 1;
}
//Таблетка способностей - конец

//Кекс - начало
function cake_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	if($pokemon['type'] == "normal"){
		$r = mt_rand(1,100);
		if($r <= 10){
			Work::$sql->query("UPDATE `user_pokemons` SET `type` = 'shine' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
			$_SESSION['text'] = 'Покемон успешно изменил свой окрас!';
			minus_item($itemID,1);
			$_SESSION['error'] = 0;
			$_SESSION['other'] = 1;
			if(check_mission_ivent(27)){add_mission_ivent(27);}
		}else{
			$_SESSION['text'] = 'Покемон не стал шайни!';
			$_SESSION['error'] = 0;
			minus_item($itemID,1);
			$_SESSION['other'] = 0;
		}
		lvlupuser(30);
	}else{
		$_SESSION['text'] = 'Этот покемон уже шайни!';
		$_SESSION['error'] = 1;
	}
	return $_SESSION['text'];
}
//Кекс - конец

//Сладкий кекс - начало
function sweet_cake_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($st[2] == 0){
	$gen = explode(',',$pokemon['gen']);
	$s = false; $g = false;
	$r = mt_rand(1,100); $r2 = mt_rand(1,100); $r3 = mt_rand(1,6)-1;
	if($r3 == 0) {$t = 'HP';}elseif($r3 == 1){$t = 'Атаки';}elseif($r3 == 2){$t = 'Защиты';}elseif($r3 == 3){$t = 'Скорости';}elseif($r3 == 4){$t = 'Спец. Атаки';}elseif($r3 == 5){$t = 'Спец. Защиты';}
	$gen[$r3] += 1; $genUpd = implode(',',$gen);
	if($r <= 20) {$s = true;}else{$s = false;}
	if($r <= 15) {$g = true;}else{$g = false;}
	if($s and $g){
		if($gen[$r3] <= 42){
			$a .= "Покемон повысил генокод ".$t." до ".$gen[$r3]."! ";

			$st[2] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `gen` = '".$genUpd."', `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		}else{
			$a .= "Покемон достиг макисмального показателя генокода ".$t."! ";
		}
		if($pokemon['type'] == "normal"){
			$a .= "Покемон успешно изменил свой окрас! ";
			$st[2] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `type` = 'shine', `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
            if(check_mission_ivent(27)){ add_mission_ivent(27);}
		}else{
			$a .= "Покемон не может изменить свой окрас так как уже является шайни! ";
		}
		$_SESSION['error'] = 0;
	$_SESSION['other'] = 1;
	}elseif($s and !$g){
		if($pokemon['type'] == "normal"){
			$a .= "Покемон успешно изменил свой окрас! ";
			$st[2] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `type` = 'shine', `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
            if(check_mission_ivent(27)){ add_mission_ivent(27);}
		}else{
			$a .= "Покемон не может изменить свой окрас так как уже является шайни! ";
		}
	}elseif(!$s and $g){
		if($gen[$r3] <= 42){
			$a .= "Покемон повысил генокод ".$t." до ".$gen[$r3]."! ";
			$st[2] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `gen` = '".$genUpd."', `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		}else{
			$a .= "Покемон достиг макисмального показателя генокода ".$t."! ";
		}
	}else{
		$a .= "Покемон не изменил окрас и не повысил генокод!";
		$_SESSION['error'] = 0;
	$_SESSION['other'] = 0;
	}
		$_SESSION['text'] = $a;
	minus_item($itemID,1);
	lvlupuser(45);
	if(!$_SESSION['error']){
	    battlepass_exp(40);
	}
	}else{
		$_SESSION['text'] = 'На этого покемона уже был использован сладкий кекс!';
		$_SESSION['error'] = 1;
	}
}
//Сладкий кекс - конец

//Корень априкорна - начало
function root_apricorn_new($pokID,$itemID){
	$a = "!";
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($st[3] == 0){
		$ev = explode(',',$pokemon['evcounts']);
		$ev_upd = $ev[0]+$ev[1]+$ev[2]+$ev[3]+$ev[4]+$ev[5]+$pokemon['ev'];
		$evc = "0,0,0,0,0,0";
		Work::$sql->query("UPDATE `user_pokemons` SET `ev` = '".$ev_upd."', `evcounts` = '".$evc."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		$r = mt_rand(1,100);
		if($r <= 30 and $pokemon['trade'] == "true"){
			Work::$sql->query("UPDATE `user_pokemons` SET `trade` = 'false' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
			$a = ", а так же стал непередаваемым!";
		}
		$_SESSION['text'] = 'Покемон успешно сбросил все свои EV в свободные'.$a;
		$_SESSION['error'] = 0;
		minus_item($itemID,1);
		lvlupuser(45);
		$st[3] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
	}else{
		$_SESSION['text'] = 'На покемона уже был использован корень априкорна!';
		$_SESSION['error'] = 1;
	}
}
//Корень априкорна - конец

//Лекарство - начало
function medicine_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($st[7] == 1){
	    if($st[4] == 0){
		Work::$sql->query("UPDATE `user_pokemons_baf` SET `pills` = 0  WHERE `pokemon` = ".$pokID);
		$_SESSION['text'] = 'Покемон успешно вернул возможность снова сменить характер!';
		$_SESSION['error'] = 0;
		minus_item($itemID,1);
		lvlupuser(35);
		$st[4] = 1; $st[7] = 0;$stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
	    }else{
    		$_SESSION['text'] = 'На покемона уже было использовано лекарство!';
    		$_SESSION['error'] = 1;
    	}
	}else{
		$_SESSION['text'] = 'На покемона не были использованы пилюли!';
		$_SESSION['error'] = 1;
	}
}
//Лекарство - конец

//Пилюли - начало
function pills_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($itemID == 257){ $arr = array(11,12,14,23);} //атака
	elseif($itemID == 258){$arr = array(7,15,19,24);} //защита
	elseif($itemID == 259){$arr = array(1,8,17,20);} //скорость
	elseif($itemID == 260){$arr = array(6,22,25,26);} //спец атака
	elseif($itemID == 261){$arr = array(5,9,10,16);} //спец защита
	elseif($itemID == 262){$arr = array(2,3,4,13,18,21);} //стабильность
	$r = mt_rand(1,4)-1;
	$har = $arr[$r];
	if($st[7] == 0){
	    if(achiv_utility(35)){
			if($pokemon['character'] == $har){
			    
			}else{
			    minus_item($itemID,1);
			    $st[7] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
			}
		}else{
			minus_item($itemID,1);
			$st[7] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		}
		Work::$sql->query("UPDATE `user_pokemons` SET `character` = '".$har."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		$_SESSION['text'] = 'Покемон успешно изменил свой харакетр на '.haracter_pokes($har).'!';
		$_SESSION['error'] = 0;
		lvlupuser(45);
            update_achiv(35,1);
			if(check_mission(19)){ add_mission(19);}
			if(check_mission_ivent(18)){ add_mission_ivent(18);}
	}else{
		$_SESSION['text'] = 'Покемон уже менял свой характер!';
		$_SESSION['error'] = 1;
	}
}
//Пилюли - конец

//Зелье памяти - начало
function memory_potion_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$eggAttacks = Work::$sql->query("SELECT * FROM `base_attacks_pokemons` WHERE `pok` = '".$pokemon['basenum']."' AND `type` = 'sex'")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	$arrayAttacks = explode(',',$eggAttacks['attacks']);
	$numberAttack = mt_rand(0, count($arrayAttacks) - 1);
	if(!empty($arrayAttacks[$numberAttack])){
		$attacksList = $arrayAttacks[$numberAttack];
	}
	$inf_atk = Work::$sql->query("SELECT * FROM `base_atk` WHERE `id` = '".$attacksList."' ")->fetch_assoc();
	if($st[6] == 0){
		if(!$pokemon_baf){ Work::$sql->query("INSERT INTO `user_pokemons_baf` (`pokemon`,`attack`) VALUES ('".$pokID."','1') ");}else{ Work::$sql->query("UPDATE `user_pokemons_baf` SET `attack` = 1  WHERE `pokemon` = ".$pokID);}
		Work::$sql->query("INSERT INTO `user_pokemons_tm` (`pok`,`attacks`) VALUES ('".$pokID."','".$attacksList."') ");
		Work::$sql->query("UPDATE `user_pokemons` SET `trade` = 'false' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		$_SESSION['text'] = "Покемон успешно выучил атаку <b>".$inf_atk['name_rus']."</b> и стал прирученным!";
		$_SESSION['error'] = 0;
		minus_item($itemID,1);
		lvlupuser(250);
		$st[6] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
            update_achiv(14,1);
	}else{
		$_SESSION['text'] = 'Покемон не может выучить новую яйцевую атаку!';
		$_SESSION['error'] = 1;
	}
}
//Зелье памяти - конец
function sweetty_watt_new($pokID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = $pokemon['happy']+15;
    	if($st <= 255){
    	    Work::$sql->query("UPDATE `user_pokemons` SET `happy` = '".$st."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    		$_SESSION['text'] = 'Покемон увеличил показатель счастья до '.$st.'!';
    		$_SESSION['error'] = 0;
    		minus_item(426,1);
    		lvlupuser(30);
    	}else{
    		$_SESSION['text'] = 'Счастье покемона достигло максимального значения!';
    		$_SESSION['error'] = 1;
    	}
}
//Гормоны - начало
function hormones_new($pokID,$itemID){

    $u234 = Work::$sql->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($u234['lvl'] >= 13){
    	if($st[5] == 0){
    			if($pokemon['sparka'] == 1){
    				if(($itemID == 269 and $pokemon['gender'] == 'Мальчик') or ($itemID == 270 and $pokemon['gender'] == 'Девочка') or ($pokemon['gender'] == 'Бесполый')){
    					Work::$sql->query("UPDATE `user_pokemons` SET `sparka` = 0 WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    					$_SESSION['text'] = 'Покемон успешно вернул возможность к разведению!';
    					$_SESSION['error'] = 0;
    					minus_item($itemID,1);
    					lvlupuser(45);
    					$st[5] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
    				}else{
    					$_SESSION['text'] = 'Покемону не подходит банка с гормонами!';
    					$_SESSION['error'] = 1;
    				}
    			}else{
    				$_SESSION['text'] = 'Покемон все еще имеет возможность к разведению!';
    				$_SESSION['error'] = 1;
    			}
    	}else{
    		$_SESSION['text'] = 'Покемон уже подвергался гормональному воздействию!';
    		$_SESSION['error'] = 1;
    	}
	}else{
		$_SESSION['text'] = 'Вам еще нельзя использовать этот предмет!';
		$_SESSION['error'] = 1;
	}
}
//Гормоны - конец

//Гормоны - начало
function hormones_now_new($pokID,$itemID){

    $u234 = Work::$sql->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$st = explode(',',$pokemon['static']);
	if($u234['lvl'] >= 13){
    	if($st[5] == 0){
    		if($pokemon['startGame'] == 0 or $pokemon['trade'] == 'true'){
    			if($pokemon['sparka'] == 1){
    					$_SESSION['text'] = 'Покемону заблокировано использование гормональных!';
    					$_SESSION['error'] = 0;
    					minus_item($itemID,1);
    					lvlupuser(45);
    					$st[5] = 1; $stUpd = implode(',',$st);
            Work::$sql->query("UPDATE `user_pokemons` SET `static` = '".$stUpd."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");

    			}else{
    				$_SESSION['text'] = 'Покемон все еще имеет возможность к разведению!';
    				$_SESSION['error'] = 1;
    			}
    		}else{
    			$_SESSION['text'] = 'Нельзя использовать на этого покемона!';
    			$_SESSION['error'] = 1;
    		}
    	}else{
    		$_SESSION['text'] = 'Покемон уже подвергался гормональному воздействию!';
    		$_SESSION['error'] = 1;
    	}
	}else{
		$_SESSION['text'] = 'Вам еще нельзя использовать этот предмет!';
		$_SESSION['error'] = 1;
	}
}

function absobent_shine_new($pokID,$itemID){

	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();

    		if($pokemon['startGame'] == 0 or $pokemon['type'] == 'shine'){

    					$_SESSION['text'] = 'Покемон вернул обычный окрас!';
    					$_SESSION['error'] = 0;
    					minus_item($itemID,1);
    					lvlupuser(45);
            Work::$sql->query("UPDATE `user_pokemons` SET `type` = 'normal' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");


    		}else{
    			$_SESSION['text'] = 'Нельзя использовать на этого покемона!';
    			$_SESSION['error'] = 1;
    		}

}
//Гормоны - конец

//Таблетка способностей - начало
function preparations_new($pokID,$itemID){
    if($itemID == 289){
        $pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
        $bd = Work::$sql->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pokemon['basenum']."' ")->fetch_assoc();
        $chance = 40+(5*$bd['power_category']);
        $bd_2 = Work::$sql->query("SELECT * FROM `base_ability_pokemon` WHERE `id` = '".$pokemon['basenum']."' ")->fetch_assoc();
        if($bd_2['hidden'] != 0){
            if($pokemon['ability'] != $bd_2['hidden']){
                if(rand(1,100) >= $chance){
                    $_SESSION['text'] = 'Покемон получил скрытую способность!';
            		$_SESSION['error'] = 0;
            		Work::$sql->query("UPDATE `user_pokemons` SET `ability` = '".$bd_2['hidden']."',`ability_slot` = '3' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
                }else{
                    $_SESSION['text'] = 'Покемон не получил способность!';
            		$_SESSION['error'] = 1;
                }
                minus_item($itemID,1);
            }else{
                $_SESSION['text'] = 'Покемон уже обладает скрытой способностью!';
    		    $_SESSION['error'] = 1;
            }
        }else{
            $_SESSION['text'] = 'У покемона нет скрытой способности!';
    		$_SESSION['error'] = 1;
        }
    }else{
    	$_SESSION['text'] = 'Данная функция сейчас недоступна';
    	$_SESSION['error'] = 1;
    }
}
//Таблетка способностей - конец

//Ягоды - начало
function berry_new($pokID,$itemID){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$it = Work::$sql->query("SELECT * FROM `items_users` WHERE `item_id` = '".$itemID."' and `user` = '".$_SESSION['id']."' ")->fetch_assoc();
	$stat = explode(',',$pokemon['stats']);
	$dop = explode(',',$pokemon['stat_pl_mn']);
	if($itemID == 324) {$t = 'HP'; $st = 0;}
	elseif($itemID == 312){$t = 'Атаки';$st = 1;}
	elseif($itemID == 325){$t = 'Защиты';$st = 2;}
	elseif($itemID == 333){$t = 'Скорости';$st = 3;}
	elseif($itemID == 307){$t = 'Спец. Атаки';$st = 4;}
	elseif($itemID == 305){$t = 'Спец. Защиты';$st = 5;}
	$dop[$st] -= 1;
	$dopUpd = implode(',',$dop);
	$lol3 = $pokemon['happy'] + 40;
	if($lol3 > 255){
		$happy = 255;
	}else{
		$happy = $lol3;
	}
	if($stat[$st] >= 4){
		minus_item_id($it['id'],1);
		Work::$sql->query("UPDATE `user_pokemons` SET `stat_pl_mn` = '".$dopUpd."',`happy` = '".$happy."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
		$_SESSION['text'] = 'Покемон увеличил счастье, но понизил стат '.$t.' на 1!';
		$_SESSION['error'] = 0;
	}else{
		$_SESSION['text'] = 'Покемон не может понизить стат ниже 3!';
		$_SESSION['error'] = 1;
	}
}
//Ягоды - конец

//Крылья - начало
function flings_new($pokID,$itemID,$count){
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	if($itemID == 369) {$t = 'HP'; $stat = 0;}
	elseif($itemID == 370){$t = 'Атаки';$stat = 1;}
	elseif($itemID == 371){$t = 'Защиты';$stat = 2;}
	elseif($itemID == 372){$t = 'Скорости';$stat = 3;}
	elseif($itemID == 368){$t = 'Спец. Атаки';$stat = 4;}
	elseif($itemID == 367){$t = 'Спец. Защиты';$stat = 5;}
	$ev = explode(',',$pokemon['evcounts']);
	$i = 1;
	$lol = $ev[$stat] + $count;
	$exp = 5*$count;
	$lol2 = $pokemon['flings'] + $count;
	if($ev[$stat] > 125){
		$_SESSION['text'] = 'Количество EV на данном стате достигло максимума.';
		$_SESSION['error'] = 1;
	}elseif($lol > 126){
		$_SESSION['text'] = 'Невозможно использовать столько за один раз.';
		$_SESSION['error'] = 1;
	}elseif($pokemon['flings'] >= 59){
		$_SESSION['text'] = 'На данного покемона больше нельзя использовать витамины.';
		$_SESSION['error'] = 1;
	}elseif($lol2 > 50){
		$_SESSION['text'] = 'Невозможно использовать столько за один раз.';
		$_SESSION['error'] = 1;
	}else{
		
			$ev[$stat] = $lol;
			$evcounts =  implode(',',$ev);
			$a = $count;
			$vitaminka = $a+$pokemon['flings'];
			$_SESSION['error'] = 0;
			$_SESSION['text'] = 'Очки EV успешно добавлены!';
			minus_item($itemID,$count);
		update_achiv(21,$count);
		$lol3 = $pokemon['happy'] + ($count*2);
		if($lol3 > 255){
			$happy = 255;
		}else{
			$happy = $lol3;
		}
			lvlupuser($exp);
            if(check_mission_ivent(24)){ add_mission_ivent(24,$count);}
		Work::$sql->query("UPDATE `user_pokemons` SET `evcounts` = '".$evcounts."', `flings` = '".$vitaminka."', `happy` = '".$happy."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1");
	}
}
// function flings_new($pokID,$itemID){

//     $u234 = Work::$sql->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
// 	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
// 	$stat = explode(',',$pokemon['stats']);
// 	$dop = explode(',',$pokemon['stat_pl_mn']);
// 	if($itemID == 369) {$t = 'HP'; $st = 0;}
// 	elseif($itemID == 370){$t = 'Атаки';$st = 1;}
// 	elseif($itemID == 371){$t = 'Защиты';$st = 2;}
// 	elseif($itemID == 372){$t = 'Скорости';$st = 3;}
// 	elseif($itemID == 368){$t = 'Спец. Атаки';$st = 4;}
// 	elseif($itemID == 367){$t = 'Спец. Защиты';$st = 5;}
// 	$dop[$st] += 1;
// 	$dopUpd = implode(',',$dop);
// 	if($u234['lvl'] >= 10){
//     	if($dop[$st] <= 15){
//     		minus_item($itemID,1);
//     		lvlupuser(10);
//     		$cool_fling = $pokemon['flings']+1;
//     		Work::$sql->query("UPDATE `user_pokemons` SET `stat_pl_mn` = '".$dopUpd."',`flings` = '".$cool_fling."' WHERE `id` = ".$pokID." AND `user_id` = ".$_SESSION['id']." AND `active` = 1");
//     		$_SESSION['text'] = 'Покемон увеличил стат '.$t.' на 1!';
//     		$_SESSION['error'] = 0;
//     	}else{
//     		$_SESSION['text'] = 'Покемон не может повысить стат больше, чем на 15!';
//     		$_SESSION['error'] = 1;
//     	}
//     }else{
// 		$_SESSION['text'] = 'Вам еще нельзя использовать этот предмет!';
// 		$_SESSION['error'] = 1;
// 	}
// }
//Крылья - конец

//ТМ-Атаки - начало
function tm_new($pokID,$itemID) {
	$pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
	$item = Work::$sql->query("SELECT * FROM `base_items` WHERE `id` = '".$itemID."'")->fetch_assoc();
	$tm = Work::$sql->query("SELECT * FROM `attac_poke_tm` WHERE `tm_id` = '".$item['tm_id']."' AND `poke_base_id` = '".$pokemon['basenum']."'")->fetch_assoc();
	if($tm){
		$tmCheck = Work::$sql->query("SELECT * FROM `user_pokemons_tm` WHERE `pok` = '".$pokID."' AND `attacks` = '".$item['info']."'")->fetch_assoc();
		if($tmCheck){
			$_SESSION['error'] = 1;
			$_SESSION['text'] = 'Покемон уже обучен этой атаке.';
		}else{
			lvlupuser(75);
			if($itemID >= 1001 and $itemID <= 1099){
			    $r = rand(1,100);
			    if($r <= 70){
			        minus_item($itemID,1);

			    }else{
			        $_SESSION['echo'] = 1;
			    }
			}else{
			    minus_item($itemID,1);
			}
			Work::$sql->query("INSERT INTO `user_pokemons_tm` (`pok`,`attacks`) VALUES ('".$pokID."','".$item['info']."') ");
			$_SESSION['error'] = 0;
			$_SESSION['text'] = 'Вы обучили покемона новой атаке.';
			update_achiv(37,1);
		}
	}else{
		$_SESSION['error'] = 1;
		$_SESSION['text'] = 'Покемон не может быть обучен этой атаке.';
	}
}
//ТМ-Атаки - конец

//Загадочный покебол - начало
function mysterious_ball_new($itemID){
    $u234 = Work::$sql->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
    if($u234['lvl'] >= 10){
	$reborn = time()+(3600*24*6);
	$character = rand(1,26);
	$s1 = rand(25,30); $s2 = rand(25,30); $s3 = rand(25,30); $s4 = rand(25,30); $s5 = rand(25,30); $s6 = rand(25,30);
	$gens = $s1.','.$s2.','.$s3.','.$s4.','.$s5.','.$s6;
	$s1_2 = rand(20,25); $s2_2 = rand(20,25); $s3_2 = rand(20,25); $s4_2 = rand(20,25); $s5_2 = rand(20,25); $s6_2 = rand(20,25);
	$gens_2 = $s1_2.','.$s2_2.','.$s3_2.','.$s4_2.','.$s5_2.','.$s6_2;
	$rlvl2 = mt_rand(1,100); $rlvl3 = mt_rand(1,100); $rlvl4 = mt_rand(1,100); $rlvl5 = mt_rand(1,100); $rlvl6 = mt_rand(1,100); $rlvl7 = mt_rand(1,100); $rlvl8 = mt_rand(1,100); $rlvl9 = mt_rand(1,100); $rlvl10 = mt_rand(1,100);
	$chance_1 = mt_rand(1,100); $chance_2_1 = mt_rand(1,100); $chance_2_2 = mt_rand(1,100); $chance_3 = mt_rand(1,75); $chance_4 = mt_rand(1,85); $chance_5 = mt_rand(1,90); $chance_6 = mt_rand(1,70); $chance_7 = mt_rand(1,80); $chance_8 = mt_rand(1,45); $chance_9 = mt_rand(1,50);

	if($chance_1 >= 1 and $chance_1 < 20){
		$rand = mt_rand(250000,500000);
		itemAdd(1,$rand);
		$t .= '<img src="/img/world/items/little/1.png" class="item"> Генкар <b>х'.number_format($rand,0,'.','.').'</b>';
	}elseif($chance_1 >= 20 and $chance_1 < 30){
		$rand = mt_rand(5,8);
		itemAdd(62,$rand);
		$t .= '<img src="/img/world/items/little/62.png" class="item"> Даркбол <b>х'.$rand.'</b>';
	}elseif($chance_1 >= 30 and $chance_1 < 37){
		$rand = mt_rand(3,4);
		itemAdd(53,$rand);
		$t .= '<img src="/img/world/items/little/53.png" class="item"> Бриллиантовый покебол <b>х'.$rand.'</b>';
	}elseif($chance_1 >= 37 and $chance_1 < 45){
		$r_it = mt_rand(125,141);
		$it = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r_it)->fetch_assoc();
		itemAdd($r_it,1);
		$t .= '<img src="/img/world/items/little/'.$r_it.'.png" class="item"> '.$it['name'].' <b>х1</b>';
	}elseif($chance_1 >= 45 and $chance_1 < 70){
		$rand = mt_rand(10,15);
		itemAdd(30,$rand);
		$t .= '<img src="/img/world/items/little/30.png" class="item"> Розовая конфета <b>х'.$rand.'</b>';
	}elseif($chance_1 >= 70 and $chance_1 < 85){
		$rand = mt_rand(4,6);
		itemAdd(197,$rand);
		$t .= '<img src="/img/world/items/little/197.png" class="item"> Набор тренировки <b>х'.$rand.'</b>';
	}elseif($chance_1 >= 85 and $chance_1 <= 100){
		$rand = mt_rand(2,4);
		itemAdd(198,$rand);
		$t .= '<img src="/img/world/items/little/198.png" class="item"> Набор ослаблений <b>х'.$rand.'</b>';
	}
	if($rlvl2 <= 95){
		if($chance_2_1 >= 1 and $chance_2_1 < 25){
			$r1 = rand(5,7);
		    $i = 0;
		    while($i<$r1){
		        $r2 = mt_rand(35,52);
		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х5</b>';
		        itemAdd($r2,5);
		        $i++;
		    }
		}elseif($chance_2_1 >= 25 and $chance_2_1 < 35){
		    $rand = mt_rand(1,2);
			itemAdd(255,$rand);
			$t .= '<br><img src="/img/world/items/little/255.png" class="item"> Корень априкорна <b>х'.$rand.'</b>';
		}elseif($chance_2_1 >= 35 and $chance_2_1 < 40){
		    $rand = mt_rand(1,2);
			itemAdd(32,$rand);
			$t .= '<br><img src="/img/world/items/little/32.png" class="item"> Шоколадная конфета <b>х'.$rand.'</b>';
		}elseif($chance_2_1 >= 40 and $chance_2_1 < 55){
		    $rand = mt_rand(1,2);
			itemAdd(33,$rand);
			$t .= '<br><img src="/img/world/items/little/33.png" class="item"> Горькая конфета <b>х'.$rand.'</b>';
		}elseif($chance_2_1 >= 55 and $chance_2_1 < 75){
		    $r1 = rand(5,8);
		    $i = 0;
		    while($i<$r1){
		        $r2 = mt_rand(205,211);
		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х1</b>';
		        itemAdd($r2,1);
		        $i++;
		    }
		}elseif($chance_2_1 >= 75 and $chance_2_1 < 85){
			$rand = mt_rand(2,4);
			itemAdd(256,$rand);
			$t .= '<br><img src="/img/world/items/little/256.png" class="item"> Лекарство <b>х'.$rand.'</b>';
		}elseif($chance_2_1 >= 85 and $chance_2_1 <= 100){
		    $r1 = rand(5,7);
		    $i = 0;
		    while($i<$r1){
		        $r2 = mt_rand(199,204);
		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х5</b>';
		        itemAdd($r2,5);
		        $i++;
		    }
		}
		if($chance_2_2 >= 1 and $chance_2_2 < 25){
			$r1 = rand(5,7);
		    $i = 0;
		    while($i<$r1){
		        $r2 = mt_rand(35,52);
		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х5</b>';
		        itemAdd($r2,5);
		        $i++;
		    }
		}elseif($chance_2_2 >= 25 and $chance_2_2 < 35){
		    $rand = mt_rand(1,2);
			itemAdd(255,$rand);
			$t .= '<br><img src="/img/world/items/little/255.png" class="item"> Корень априкорна <b>х'.$rand.'</b>';
		}elseif($chance_2_2 >= 35 and $chance_2_2 < 40){
		    $rand = mt_rand(1,2);
			itemAdd(32,$rand);
			$t .= '<br><img src="/img/world/items/little/32.png" class="item"> Шоколадная конфета <b>х'.$rand.'</b>';
		}elseif($chance_2_2 >= 40 and $chance_2_2 < 55){
		    $rand = mt_rand(1,2);
			itemAdd(33,$rand);
			$t .= '<br><img src="/img/world/items/little/33.png" class="item"> Горькая конфета <b>х'.$rand.'</b>';
		}elseif($chance_2_2 >= 55 and $chance_2_2 < 75){
		    $r1 = rand(5,8);
		    $i = 0;
		    while($i<$r1){
		        $r2 = mt_rand(205,211);
		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х1</b>';
		        itemAdd($r2,1);
		        $i++;
		    }
		}elseif($chance_2_2 >= 75 and $chance_2_2 < 85){
			$rand = mt_rand(2,4);
			itemAdd(256,$rand);
			$t .= '<br><img src="/img/world/items/little/256.png" class="item"> Лекарство <b>х'.$rand.'</b>';
		}elseif($chance_2_2 >= 85 and $chance_2_2 <= 100){
		    $r1 = rand(5,7);
		    $i = 0;
		    while($i<$r1){
		        $r2 = mt_rand(199,204);
		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х5</b>';
		        itemAdd($r2,5);
		        $i++;
		    }
		}
		if($rlvl3 <= 85){
		    if($chance_3 >= 1 and $chance_3 < 5){
    		    $rand = mt_rand(1,3);
    			itemAdd(142,$rand);
		        $t .= '<br><img src="/img/world/items/little/142.png" class="item"> Блестки <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 5 and $chance_3 < 10){
    		    $rand = mt_rand(1,3);
    			itemAdd(144,$rand);
		        $t .= '<br><img src="/img/world/items/little/144.png" class="item"> Лупа <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 10 and $chance_3 < 15){
    		    $rand = mt_rand(1,3);
    			itemAdd(171,$rand);
		        $t .= '<br><img src="/img/world/items/little/171.png" class="item"> Поглощающая лампа <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 15 and $chance_3 < 20){
    		    $rand = mt_rand(1,3);
    			itemAdd(143,$rand);
		        $t .= '<br><img src="/img/world/items/little/143.png" class="item"> Линзы <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 20 and $chance_3 < 25){
    		    $rand = mt_rand(1,3);
    			itemAdd(147,$rand);
		        $t .= '<br><img src="/img/world/items/little/147.png" class="item"> Балласт <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 25 and $chance_3 < 30){
    		    $rand = mt_rand(1,3);
    			itemAdd(145,$rand);
		        $t .= '<br><img src="/img/world/items/little/145.png" class="item"> Объедки <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 30 and $chance_3 < 35){
    		    $rand = mt_rand(1,3);
    			itemAdd(166,$rand);
		        $t .= '<br><img src="/img/world/items/little/166.png" class="item"> Сфера жизни <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 35 and $chance_3 < 40){
    		    $rand = mt_rand(1,3);
    			itemAdd(155,$rand);
		        $t .= '<br><img src="/img/world/items/little/155.png" class="item"> Коготь <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 40 and $chance_3 < 55){
    		    $rand = mt_rand(1,3);
    			itemAdd(146,$rand);
		        $t .= '<br><img src="/img/world/items/little/146.png" class="item"> Адреналиновый шар <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 55 and $chance_3 < 60){
    		    $rand = mt_rand(1,3);
    			itemAdd(148,$rand);
		        $t .= '<br><img src="/img/world/items/little/148.png" class="item"> Большой корень <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 60 and $chance_3 < 65){
    		    $rand = mt_rand(1,3);
    			itemAdd(153,$rand);
		        $t .= '<br><img src="/img/world/items/little/153.png" class="item"> Защитные очки <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 65 and $chance_3 < 70){
    		    $rand = mt_rand(1,3);
    			itemAdd(165,$rand);
		        $t .= '<br><img src="/img/world/items/little/165.png" class="item"> Тяжелые ботинки <b>х'.$rand.'</b>';
    		}elseif($chance_3 >= 70 and $chance_3 <= 75){
    		    $rand = mt_rand(1,3);
    			itemAdd(169,$rand);
		        $t .= '<br><img src="/img/world/items/little/169.png" class="item"> Светящийся клей <b>х'.$rand.'</b>';
    		}
		    if($rlvl4 <= 75){
		        if($chance_4 >= 1 and $chance_4 < 10){
        		    $rand = mt_rand(1,2);
        			itemAdd(85,$rand);
    		        $t .= '<br><img src="/img/world/items/little/85.png" class="item"> Солнечный камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 10 and $chance_4 < 15){
        		    $rand = mt_rand(1,2);
        			itemAdd(86,$rand);
    		        $t .= '<br><img src="/img/world/items/little/86.png" class="item"> Сумрачный камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 15 and $chance_4 < 20){
        		    $rand = mt_rand(1,2);
        			itemAdd(87,$rand);
    		        $t .= '<br><img src="/img/world/items/little/87.png" class="item"> Сияющий камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 20 and $chance_4 < 25){
        		    $rand = mt_rand(1,2);
        			itemAdd(89,$rand);
    		        $t .= '<br><img src="/img/world/items/little/89.png" class="item"> Камень рассвета <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 25 and $chance_4 < 35){
        		    $rand = mt_rand(1,2);
        			itemAdd(90,$rand);
    		        $t .= '<br><img src="/img/world/items/little/90.png" class="item"> Ледяной камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 35 and $chance_4 < 45){
        		    $rand = mt_rand(1,2);
        			itemAdd(84,$rand);
    		        $t .= '<br><img src="/img/world/items/little/84.png" class="item"> Лунный камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 45 and $chance_4 < 55){
        		    $rand = mt_rand(1,2);
        			itemAdd(83,$rand);
    		        $t .= '<br><img src="/img/world/items/little/83.png" class="item"> Огненный камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 55 and $chance_4 < 65){
        		    $rand = mt_rand(1,2);
        			itemAdd(81,$rand);
    		        $t .= '<br><img src="/img/world/items/little/81.png" class="item"> Водный камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 65 and $chance_4 < 75){
        		    $rand = mt_rand(1,2);
        			itemAdd(80,$rand);
    		        $t .= '<br><img src="/img/world/items/little/80.png" class="item"> Громовой камень <b>х'.$rand.'</b>';
        		}elseif($chance_4 >= 75 and $chance_4 <= 85){
        		    $rand = mt_rand(1,2);
        			itemAdd(82,$rand);
    		        $t .= '<br><img src="/img/world/items/little/82.png" class="item"> Лиственный камень <b>х'.$rand.'</b>';
        		}
		        if($rlvl5 <= 65){
		            if($chance_5 >= 1 and $chance_5 < 15){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #058 Гроули';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',58,1) ");
					}elseif($chance_5 >= 15 and $chance_5 < 30){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #200 Мисдривус';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',200,1) ");
					}elseif($chance_5 >= 30 and $chance_5 < 40){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #349 Фибас';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',349,1) ");
					}elseif($chance_5 >= 40 and $chance_5 < 44){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #442 Спиритомб';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',442,1) ");
					}elseif($chance_5 >= 44 and $chance_5 < 60){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #535 Тимпол';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',535,1) ");
					}elseif($chance_5 >= 60 and $chance_5 < 75){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #701 Холуча';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',701,1) ");
					}elseif($chance_5 >= 75 and $chance_5 < 85){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #780 Дрампа';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',780,1) ");
					}elseif($chance_5 >= 85 and $chance_5 < 90){
						$t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #848 Токсель';
						Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',848,1) ");
					}
		            if($rlvl6 <= 60){
		                if($chance_6 >= 1 and $chance_6 < 20){
                		    $rand = mt_rand(7,14);
                			itemAdd(246,$rand);
            		        $t .= '<br><img src="/img/world/items/little/246.png" class="item"> Слакдий кекс <b>х'.$rand.'</b>';
                		}elseif($chance_6 >= 20 and $chance_6 < 25){
                		    $rand = mt_rand(2,4);
                			itemAdd(195,$rand);
            		        $t .= '<br><img src="/img/world/items/little/195.png" class="item"> Коробка с окаменелостями <b>х'.$rand.'</b>';
                		}elseif($chance_6 >= 25 and $chance_6 < 35){
                		    $rand = mt_rand(1,2);
                			itemAdd(269,$rand);
            		        $t .= '<br><img src="/img/world/items/little/269.png" class="item"> Гормон Тестостерон <b>х'.$rand.'</b>';
                		}elseif($chance_6 >= 35 and $chance_6 < 45){
                		    $rand = mt_rand(1,2);
                			itemAdd(270,$rand);
            		        $t .= '<br><img src="/img/world/items/little/270.png" class="item"> Гормон Эстроген <b>х'.$rand.'</b>';
                		}elseif($chance_6 >= 45 and $chance_6 < 50){
                		    $rand = mt_rand(10,15);
                			itemAdd(31,$rand);
            		        $t .= '<br><img src="/img/world/items/little/31.png" class="item"> Красная конфета <b>х'.$rand.'</b>';
                		}elseif($chance_6 >= 50 and $chance_6 < 60){
                		    itemAdd(185,1);
            		        $t .= '<br><img src="/img/world/items/little/185.png" class="item"> Графитовый колокольчик <b>х1</b>';
                		}elseif($chance_6 >= 60 and $chance_6 < 65){
                		    $r1 = rand(3,5);
                		    $i = 0;
                		    while($i<$r1){
                		        $r2 = mt_rand(367,372);
                		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r2)->fetch_assoc();
                		        $t .= '<br><img src="/img/world/items/little/'.$r2.'.png" class="item"> '.$it1['name'].' <b>х5</b>';
                		        itemAdd($r2,5);
                		        $i++;
                		    }
                		}elseif($chance_6 >= 65 and $chance_6 <= 70){
                		    itemAdd(417,5);
            		        $t .= '<br><img src="/img/world/items/little/417.png" class="item"> Осколок Мега-камня <b>х5</b>';
                		}
		                if($rlvl7 <= 50){
		                    if($chance_7 >= 1 and $chance_7 < 5){
                    		    itemAdd(88,1);
                		        $t .= '<br><img src="/img/world/items/little/88.png" class="item"> Овальный камень <b>х1</b>';
                    		}elseif($chance_7 >= 5 and $chance_7 < 10){
                    		    itemAdd(103,1);
                		        $t .= '<br><img src="/img/world/items/little/103.png" class="item"> Перламутровая чешуя <b>х1</b>';
                    		}elseif($chance_7 >= 10 and $chance_7 < 15){
                		        $r = mt_rand(120,123);
                		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r)->fetch_assoc();
                		        $t .= '<br><img src="/img/world/items/little/'.$r.'.png" class="item"> '.$it1['name'].' <b>х5</b>';
                		        itemAdd($r,1);
                    		}elseif($chance_7 >= 15 and $chance_7 < 20){
                    		    itemAdd(106,1);
                		        $t .= '<br><img src="/img/world/items/little/106.png" class="item"> Чешуя дракона <b>х1</b>';
                    		}elseif($chance_7 >= 20 and $chance_7 < 25){
                    		    itemAdd(99,1);
                		        $t .= '<br><img src="/img/world/items/little/99.png" class="item"> Корона <b>х1</b>';
                    		}elseif($chance_7 >= 25 and $chance_7 < 30){
                    		    itemAdd(100,1);
                		        $t .= '<br><img src="/img/world/items/little/100.png" class="item"> Броня <b>х1</b>';
                    		}elseif($chance_7 >= 30 and $chance_7 < 35){
                    		    itemAdd(97,1);
                		        $t .= '<br><img src="/img/world/items/little/97.png" class="item"> Пироженка <b>х1</b>';
                    		}elseif($chance_7 >= 35 and $chance_7 < 40){
                    		    itemAdd(96,1);
                		        $t .= '<br><img src="/img/world/items/little/96.png" class="item"> Флакон духов <b>х1</b>';
                    		}elseif($chance_7 >= 40 and $chance_7 < 45){
                    		    itemAdd(93,1);
                		        $t .= '<br><img src="/img/world/items/little/93.png" class="item"> Острый коготь <b>х1</b>';
                    		}elseif($chance_7 >= 45 and $chance_7 < 50){
                    		    itemAdd(94,1);
                		        $t .= '<br><img src="/img/world/items/little/94.png" class="item"> Острый клык <b>х1</b>';
                    		}elseif($chance_7 >= 50 and $chance_7 < 55){
                    		    itemAdd(102,1);
                		        $t .= '<br><img src="/img/world/items/little/102.png" class="item"> Электрайзер <b>х1</b>';
                    		}elseif($chance_7 >= 55 and $chance_7 < 60){
                    		    itemAdd(101,1);
                		        $t .= '<br><img src="/img/world/items/little/101.png" class="item"> Магмарайзер <b>х1</b>';
                    		}elseif($chance_7 >= 60 and $chance_7 < 65){
                    		    itemAdd(98,1);
                		        $t .= '<br><img src="/img/world/items/little/98.png" class="item"> Протектор <b>х1</b>';
                    		}elseif($chance_7 >= 65 and $chance_7 < 70){
                    		    itemAdd(111,1);
                		        $t .= '<br><img src="/img/world/items/little/111.png" class="item"> Сладкое яблоко <b>х1</b>';
                    		}elseif($chance_7 >= 70 and $chance_7 < 75){
                    		    itemAdd(112,1);
                		        $t .= '<br><img src="/img/world/items/little/112.png" class="item"> Кислое яблоко <b>х1</b>';
                    		}elseif($chance_7 >= 75 and $chance_7 <= 80){
                    		    $r = mt_rand(113,119);
                		        $it1 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$r)->fetch_assoc();
                		        $t .= '<br><img src="/img/world/items/little/'.$r.'.png" class="item"> '.$it1['name'].' <b>х1</b>';
                		        itemAdd($r,1);
                    		}
		                    if($rlvl8 <= 40){
		                        if($chance_8 >= 1 and $chance_8 < 5){
                        		    itemAdd(1015,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM15 - Подкоп <b>х1</b>';
                        		}elseif($chance_8 >= 5 and $chance_8 < 10){
                        		    itemAdd(1027,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM27 - Холодный ветер <b>х1</b>';
                        		}elseif($chance_8 >= 10 and $chance_8 < 15){
                        		    itemAdd(1028,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM28 - Истощение <b>х1</b>';
                        		}elseif($chance_8 >= 15 and $chance_8 < 20){
                        		    itemAdd(1048,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM48 - Оползень <b>х1</b>';
                        		}elseif($chance_8 >= 20 and $chance_8 < 25){
                        		    itemAdd(1056,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM56 - Подставной ход <b>х1</b>';
                        		}elseif($chance_8 >= 25 and $chance_8 < 30){
                        		    itemAdd(1078,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM78 - Акробатика <b>х1</b>';
                        		}elseif($chance_8 >= 30 and $chance_8 < 35){
                        		    itemAdd(1092,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM92 - Волшебный огонь <b>х1</b>';
                        		}elseif($chance_8 >= 35 and $chance_8 < 40){
                        		    itemAdd(1096,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM96 - Прямое попадание <b>х1</b>';
                        		}elseif($chance_8 >= 40 and $chance_8 <= 45){
                        		    itemAdd(1099,1);
                    		        $t .= '<br><img src="/img/world/items/little/1001.png" class="item"> TM99 - Прорывной взмах <b>х1</b>';
                        		}
		                        if($rlvl9 <= 35){
		                            if($chance_9 >= 1 and $chance_9 < 5){
                            		    itemAdd(2001,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR01 - Слэм <b>х1</b>';
                            		}elseif($chance_9 >= 5 and $chance_9 < 10){
                            		    itemAdd(2002,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR02 - Огнемет <b>х1</b>';
                            		}elseif($chance_9 >= 10 and $chance_9 < 15){
                            		    itemAdd(2008,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR08 - Молния <b>х1</b>';
                            		}elseif($chance_9 >= 15 and $chance_9 < 20){
                            		    itemAdd(2022,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR22 - Бомба слизи <b>х1</b>';
                            		}elseif($chance_9 >= 20 and $chance_9 < 25){
                            		    itemAdd(2024,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR24 - Неистовство <b>х1</b>';
                            		}elseif($chance_9 >= 25 and $chance_9 < 30){
                            		    itemAdd(2032,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR32 - Разгрызание <b>х1</b>';
                            		}elseif($chance_9 >= 30 and $chance_9 < 35){
                            		    itemAdd(2037,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR37 - Насмешка <b>х1</b>';
                            		}elseif($chance_9 >= 35 and $chance_9 < 40){
                            		    itemAdd(2053,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR53 - Ближний бой <b>х1</b>';
                            		}elseif($chance_9 >= 40 and $chance_9 < 45){
                            		    itemAdd(2075,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR75 - Каменное лезвие <b>х1</b>';
                            		}elseif($chance_9 >= 45 and $chance_9 < 50){
                            		    itemAdd(2092,1);
                        		        $t .= '<br><img src="/img/world/items/little/2001.png" class="item"> TR92 - Ослепительный свет <b>х1</b>';
                            		}
		                            if($rlvl10 <= 7){
		                                $t .= '<br><img src="/img/world/items/little/151.png" class="item"> Яйцо #385 Джирачи';
								        Work::$sql->query("INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`) VALUES('".$gens_2."','".$character."',1,'false','".$reborn."','".$_SESSION['id']."',385,1) ");
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		}
	}


	$_SESSION['plus'] = $t;
	$_SESSION['text'] = 'Вы успешно открыли покебол.';
	$_SESSION['error'] = 0;
	minus_item($itemID,1);
	lvlupuser(200);
	update_achiv(24,1);
    }else{
		$_SESSION['text'] = 'Вам еще нельзя использовать этот предмет!';
		$_SESSION['error'] = 1;
	}
}
//Загадочный покебол - конец

//Таблетка способностей - начало
function new_year_box_new($itemID){
	$_SESSION['text'] = 'Данная функция сейчас недоступна';
	$_SESSION['error'] = 1;
}
//Таблетка способностей - конец


function set_orb_new($itemID) {
	$randOrb = mt_rand(224,238);
	$it2 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$randOrb)->fetch_assoc();
	$_SESSION['plus'] = '<img src="/img/world/items/little/'.$it2['id'].'.png" class="item"> '.$it2['name'].' <b>x1</b>';
	$_SESSION['text'] = 'Коробка успешно открыта.';
	$_SESSION['error'] = 0;
	itemAdd($randOrb,1);
	minus_item($itemID,1);
}

function set_balls_new($itemID) {
	$r = mt_rand(1,30);
	if($r >= 1 and $r < 10){
		$it = 53;
		$it2 = 56;
	}elseif($r >= 10 and $r < 20){
		$it = 53;
		$it2 = 62;
	}elseif($r >= 20 and $r <= 30){
		$it = 56;
		$it2 = 62;
	}
	$itb = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$it)->fetch_assoc();
	$itb2 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$it2)->fetch_assoc();
	$_SESSION['plus'] = '<img src="/img/world/items/little/'.$itb['id'].'.png" class="item"> '.$itb['name'].' <b>x5</b>
	<br><img src="/img/world/items/little/'.$itb2['id'].'.png" class="item"> '.$itb2['name'].' <b>х5</b>';
	$_SESSION['text'] = 'Коробка успешно открыта.';
	$_SESSION['error'] = 0;
	itemAdd($it,5);
	itemAdd($it2,5);
	minus_item($itemID,1);
}

function set_vitamines_new($itemID) {
	$randOrb = mt_rand(199,204);
	$it2 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$randOrb)->fetch_assoc();
	$_SESSION['plus'] = '<img src="/img/world/items/little/'.$it2['id'].'.png" class="item"> '.$it2['name'].' <b>x10</b>';
	$_SESSION['text'] = 'Коробка успешно открыта.';
	$_SESSION['error'] = 0;
	itemAdd($randOrb,10);
	minus_item($itemID,1);
}

function set_vitamines_small_new($itemID) {
	$randOrb = mt_rand(199,204);
	$it2 = Work::$sql->query('SELECT * FROM base_items WHERE id = '.$randOrb)->fetch_assoc();
	$_SESSION['plus'] = '<img src="/img/world/items/little/'.$it2['id'].'.png" class="item"> '.$it2['name'].' <b>x5</b>';
	$_SESSION['text'] = 'Кейс успешно открыт.';
	$_SESSION['error'] = 0;
	itemAdd($randOrb,5);
	minus_item($itemID,1);
}

function amplifiers_catch_money_new($itemID) {
	$usilItem = Work::$sql->query("SELECT * FROM `base_items` WHERE `id` = '".$itemID."'")->fetch_assoc();
	$usilFunc = explode(',',$usilItem['info']);
	$time = time() + (int)$usilFunc[0];
	$usil = Work::$sql->query("SELECT * FROM `bafs` WHERE `type` = '".$usilFunc[1]."' AND `user` = '".$_SESSION['id']."'")->fetch_assoc();
	if($usil) {
		Work::$sql->query("UPDATE `bafs` SET `time` = '".$time."',`baf` = '".$itemID."' WHERE `type` = '".$usilFunc[1]."' AND `user` = '".$_SESSION['id']."'");
	}else{
		Work::$sql->query("INSERT INTO `bafs` (`user`,`baf`,`time`,`type`) VALUES ('".$_SESSION['id']."','".$itemID."', '".$time."', '".$usilFunc[1]."') ");
	}

	// Quest 131: 5044 (приманка) расходуется, 5045 (прокатная удочка) НЕ расходуется
	if($itemID != 5 and $itemID != 6 and $itemID != 187 and $itemID != 189 and $itemID != 190 and $itemID != 192 and $itemID != 185 and $itemID != 191 and $itemID != 5044 and $itemID != 5045){
	    $_SESSION['text'] = 'Вы удачно активировали усилитель.';
	    minus_item($itemID,1);
	}elseif($itemID == 5 or $itemID == 6 or $itemID == 5045){
	    $_SESSION['text'] = 'Вы закинули удочку.';
	}elseif($itemID == 187){
	    $_SESSION['text'] = 'Приманка установлена.';
	}elseif($itemID == 5044){
	    $_SESSION['text'] = 'Приманка установлена. У вас есть 30 минут.';
	    minus_item($itemID,1);
	}elseif($itemID == 189){
	    $_SESSION['text'] = 'Вы закинули сеть.';
	}elseif($itemID == 190){
	    $_SESSION['text'] = 'Громкий свист привлекает внимание.';
	}elseif($itemID == 192){
	    $_SESSION['text'] = 'Чувствуется ароматный запах сладкого меда в воздухе.';
	}elseif($itemID == 185 or $itemID == 191){
	    $_SESSION['text'] = 'Громкий звон колокола теперь окружает вас.';
	    minus_item($itemID,1);
	}

	if($itemID == 6){
	    update_achiv(22,1);
	}
	if($itemID == 448){
	    update_achiv(34,1);
	}
	$_SESSION['error'] = 0;
	$_SESSION['plus'] = false;
}
function brace_ring($pokID, $action) {
    $pokemon = Work::$sql->query("SELECT * FROM `user_pokemons` WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."' AND `active` = 1")->fetch_assoc();
    
    if (!$pokemon) {
        $_SESSION['text'] = 'Ошибка: Покемон не найден!';
        $_SESSION['error'] = 1;
        return;
    }

    $itemID = 563; // ID Скобового Кольца

    if ($action == 'equip') {
        if ($pokemon['item'] == $itemID) {
            $_SESSION['text'] = 'Скобовое Кольцо уже надето!';
            $_SESSION['error'] = 1;
            return;
        }

        if ($pokemon['item'] != NULL) {
            $_SESSION['text'] = 'Сначала снимите другой предмет!';
            $_SESSION['error'] = 1;
            return;
        }

        $new_speed = max(1, floor($pokemon['speed'] / 2));
        Work::$sql->query("UPDATE `user_pokemons` SET `item` = '".$itemID."', `speed` = '".$new_speed."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."'");

        $_SESSION['text'] = 'Скобовое Кольцо надето! Скорость снижена вдвое, но EV теперь увеличиваются на 50%.';
        $_SESSION['error'] = 0;

    } elseif ($action == 'remove') {
        if ($pokemon['item'] != $itemID) {
            $_SESSION['text'] = 'Ошибка: Скобовое Кольцо не надето!';
            $_SESSION['error'] = 1;
            return;
        }

        $original_speed = $pokemon['speed'] * 2;
        Work::$sql->query("UPDATE `user_pokemons` SET `item` = NULL, `speed` = '".$original_speed."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."'");

        $_SESSION['text'] = 'Скобовое Кольцо сломалось при снятии! Скорость покемона восстановлена.';
        $_SESSION['error'] = 0;

    } elseif ($action == 'apply_EV') {
        $base_ev = 10;
        $ev_multiplier = ($pokemon['item'] == $itemID) ? 1.5 : 1;
        $ev_points = ceil($base_ev * $ev_multiplier);
        $new_ev = $pokemon['ev'] + $ev_points;

        Work::$sql->query("UPDATE `user_pokemons` SET `ev` = '".$new_ev."' WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."'");

        $_SESSION['text'] = 'EV покемона увеличены! Новое значение: '.$new_ev;
        $_SESSION['error'] = 0;

    } elseif ($action == 'attack_remove') {
        if ($pokemon['item'] == $itemID) {
            Work::$sql->query("UPDATE `user_pokemons` SET `item` = NULL WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."'");

            $_SESSION['text'] = 'Скобовое Кольцо было уничтожено атакой!';
            $_SESSION['error'] = 0;
        }

    } elseif ($action == 'modifier_remove') {
        if ($pokemon['item'] == $itemID) {
            Work::$sql->query("UPDATE `user_pokemons` SET `item` = NULL WHERE `id` = '".$pokID."' AND `user_id` = '".$_SESSION['id']."'");

            $_SESSION['text'] = 'Скобовое Кольцо было автоматически снято при использовании модификатора!';
            $_SESSION['error'] = 0;
        }

    } elseif ($action == 'evolution_check') {
        if ($pokemon['item'] == $itemID) {
            $_SESSION['text'] = 'Ваш покемон эволюционировал, но Скобовое Кольцо осталось на нем!';
            $_SESSION['error'] = 0;
        }
    }
}


function test($itemID){
    $_SESSION['error'] = 0;
	$_SESSION['text'] = 'Просто текст';
}
// function scheme_new($itemID){
// 	$recipe = Work::$sql->query("SELECT * FROM `craft_recipe_user` WHERE `user` = '".$_SESSION['id']."' AND `recipe` = ".$id)->fetch_assoc();
// 	if($recipe){
// 		$_SESSION['error'] = 1;
// 		$_SESSION['text'] = 'Вы уже изучили это';
// 	}else{
// 		switch($id){
// 			case 130:
// 				$_SESSION['error'] = 0;
// 				$_SESSION['text'] = 'Теперь вы умеете изготавливать Веревку.';
// 				Work::$sql->query("INSERT INTO `craft_recipe_user` (`user`,`recipe`) VALUES ('".$_SESSION['id']."',130) ");
// 				minus_item(130,1);
// 			break;
// 			case 141:
// 				$_SESSION['error'] = 0;
// 				$_SESSION['text'] = 'Теперь вы умеете готовить Вкусные леденцы.';
// 				Work::$sql->query("INSERT INTO `craft_recipe_user` (`user`,`recipe`) VALUES ('".$_SESSION['id']."',141) ");
// 				minus_item(141,1);
// 			break;
// 			case 144:
// 				$_SESSION['error'] = 0;
// 				$_SESSION['text'] = 'Теперь вы умеете изготавливать Априкорновый аппарат.';
// 				Work::$sql->query("INSERT INTO `craft_recipe_user` (`user`,`recipe`) VALUES ('".$_SESSION['id']."',144) ");
// 				minus_item(144,1);
// 			break;
// 			default:
// 				$_SESSION['error'] = 1;
// 				$_SESSION['text'] = 'Ошибка!';
// 			break;
// 		}
// 	}
// }
?>

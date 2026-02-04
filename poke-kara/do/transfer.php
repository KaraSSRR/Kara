<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        _setError('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}
$gl_user = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();

if(!empty($_POST['id'])){
  $id = $_POST['id'];
  $l1 = $id*30;
  $us = $mysqli->query('SELECT * FROM `transfer_users` WHERE `reg` = '.$_SESSION['id'])->fetch_assoc();
    $pok = $mysqli->query('SELECT * FROM `transfer_pokemon` WHERE `users` = '.$us['id'].' ORDER BY `basenum` ASC LIMIT '.$l1.',30');
    
  while($poks = $pok->fetch_assoc()){
					$tr = "";
					$o = "";
					if($poks['sex'] == 1){ $s = '<i class="fas fa-mars"></i>';}
					if($poks['sex'] == 2){ $s = '<i class="fas fa-venus"></i>';}
					if($poks['tips'] == 'shine'){ $o = "shine-color";}
					if($poks['traning'] == 1.1){
					    $tr = '<i class="trening fas fa-angle-double-up tr1"></i>';
					}elseif($poks['traning'] == 1.18){
					    $tr = '<i class="trening fas fa-angle-double-up tr2"></i>';
					}elseif($poks['traning'] == 1.25){
					    $tr = '<i class="trening fas fa-angle-double-up tr3"></i>';
					}elseif($poks['traning'] == 1.31){
					    $tr = '<i class="trening fas fa-angle-double-up tr4"></i>';
					}elseif($poks['traning'] == 1.36){
					    $tr = '<i class="trening fas fa-angle-double-up tr5"></i>';
					}elseif($poks['traning'] == 1.4){
					    $tr = '<i class="trening fas fa-crown tr6"></i>';
					}
					$basenum = numbPok($poks['basenum']);
					$p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$poks['basenum'])->fetch_assoc();
					$tpl .= '<div onclick="transfPok('.$poks['id'].')" class="listPok id'.$poks['id'].'">
        <div class="number_block">
          <img src="/img/pokemons/animation/'.$basenum.'.png"> # '.$basenum.'
        </div>
        <div class="info_block">
          <div class="name '.$o.'">'.$p['name_rus'].' '.$s.' '.$poks['lvl'].'lvl '.$tr.'</div>
        </div>
      </div>';
				}

$response["html"] = $tpl;
}

if(!empty($_POST['pok'])){
  $pok = $_POST['pok'];
  $tpl = '<div class="PokLast">';
  $tr = "";
  $o = "";
  $poks = $mysqli->query('SELECT * FROM `transfer_pokemon` WHERE `id` = '.$pok)->fetch_assoc();
  $gen = 'h'.$poks['hp_iv'].'a'.$poks['atk_iv'].'d'.$poks['def_iv'].'s'.$poks['speed_iv'].'sa'.$poks['satk_iv'].'sd'.$poks['sdef_iv'];
  if($poks['sex'] == 1){ $s = '<i class="fas fa-mars"></i>';}
					if($poks['sex'] == 2){ $s = '<i class="fas fa-venus"></i>';}
					if($poks['tips'] == 'shine'){ $o = "shine-color";}
					if($poks['traning'] == 1.1){
					    $tr = '<i class="trening fas fa-angle-double-up tr1"></i>';
					}elseif($poks['traning'] == 1.18){
					    $tr = '<i class="trening fas fa-angle-double-up tr2"></i>';
					}elseif($poks['traning'] == 1.25){
					    $tr = '<i class="trening fas fa-angle-double-up tr3"></i>';
					}elseif($poks['traning'] == 1.31){
					    $tr = '<i class="trening fas fa-angle-double-up tr4"></i>';
					}elseif($poks['traning'] == 1.36){
					    $tr = '<i class="trening fas fa-angle-double-up tr5"></i>';
					}elseif($poks['traning'] == 1.4){
					    $tr = '<i class="trening fas fa-crown tr6"></i>';
					}
					$basenum = numbPok($poks['basenum']);
					$p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$poks['basenum'])->fetch_assoc();
  if($poks['reproduction'] == 1) {
    $spr = 'Недоступно';
  }else{
    $spr = 'Доступно';
  }
  if($poks['startone'] == 1) {
    $trd = 'Приручен';
  }else{
    $trd = 'Не приручен';
  }
    $t_c = $gl_user['tr_pok']+1;
  $tpl .= '<div class="name '.$o.'"><img src="/img/pokemons/animation/'.$basenum.'.png"> #'.$basenum.' '.$p['name_rus'].' '.$s.' '.$poks['lvl'].'lvl '.$tr.'</div>
  <div class="info">
  <b>Характер: </b> '.haracter_pokes($poks['har']).'<br>
  <b>Генокод:</b> '.$gen.' <br>
  <b>EV:</b> '.$poks['evcount'].' <br>
  <b>Разведение:</b> '.$spr.' <br>
  <b>Приручение:</b> '.$trd.'</div>';
  $tpl .= '</div><div class="arrow"><i class="far fa-arrow-alt-down"></i></div><div class="PokNew">';
  $tr1 = "";
  $pok1 = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$poks['basenum'])->fetch_assoc();
  if($poks['traning'] == 1.1){
					    $tr2 = '';
					}elseif($poks['traning'] == 1.18){
					    $tr2 = '';
					}elseif($poks['traning'] == 1.25){
					    $tr2 = '<i class="trening fas fa-angle-double-up tr1"></i>';
					}elseif($poks['traning'] == 1.31){
					    $tr2 = '<i class="trening fas fa-angle-double-up tr2"></i>';
					}elseif($poks['traning'] == 1.36){
					    $tr2 = '<i class="trening fas fa-angle-double-up tr3"></i>';
					}elseif($poks['traning'] == 1.4){
					    $tr2 = '<i class="trening fas fa-angle-double-up tr4"></i>';
					}

  $basenum1 = numbPok($pok1['eggBasenum']);
  $pok2 = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$basenum1)->fetch_assoc();
  $name = $pok2['name_rus'];
  $lvl = 1;
  if($poks['lvl'] >= 30 and $poks['lvl'] <= 60){
    $ev = 12;
  }elseif($poks['lvl'] > 60){
    $ev = 24;
  }else{
    $ev = 0;
  }
  $tpl .= '<div class="name '.$o.'"><img src="/img/pokemons/animation/'.$basenum1.'.png"> #'.$basenum1.' '.$name.' '.$s.' '.$lvl.'lvl '.$tr2.'</div>
  <div class="info">
  <b>Характер:</b> '.haracter_pokes($poks['har']).'<br>
  <b>Генокод:</b> '.$gen.' <br>
  <b>EV:</b> '.$ev.' <br>
  <b>Разведение:</b> '.$spr.' <br>
  <b>Приручение:</b> '.$trd.'</div>';
  if($pok2['power_category'] == 1){
    $p1 = "4.000";
    $p2 = "20.000";
  }elseif($pok2['power_category'] == 2){
    $p1 = "8.000";
    $p2 = "40.000";
  }elseif($pok2['power_category'] == 3){
    $p1 = "15.000";
    $p2 = "75.000";
  }elseif($pok2['power_category'] == 4){
    $p1 = "30.000";
    $p2 = "150.000";
  }elseif($pok2['power_category'] == 5){
    $p1 = "60.000";
    $p2 = "300.000";
  }elseif($pok2['power_category'] == 6){
    $p1 = "150.000";
    $p2 = "750.000";
  }elseif($pok2['power_category'] == 7){
    $p1 = "300.000";
    $p2 = "1.500.000";
  }elseif($pok2['power_category'] == 8){
    $p1 = "800.000";
    $p2 = "4.000.000";
  }
  if($pok2['power_category'] >= 8){
      $tpl .= '</div><div class="but">В данный момент перенос этого покемона недоступен!</div>';
  }else{
       if($gl_user['tr_pok'] >= 6){
            $tpl .= '</div><div class="but"><button  onclick="transfPokM('.$poks['id'].',2)">С приручением</button> '.$p1.' м.<br><button  onclick="transfPokM('.$poks['id'].',1)">Без приручения</button> '.$p2.' м.<br><br><button  onclick="deletePokM('.$poks['id'].')" style="background: #be5353;">Удалить покемона</button></div></div>';
       }else{
           $tpl .= '</div><div class="but"><button onclick="transfPokM('.$poks['id'].',1)">Перенести</button><br><br><button  onclick="deletePokM('.$poks['id'].')" style="background: #be5353;">Удалить покемона</button></div>';
       }
  }
  $response["html"] = $tpl;
}


if(!empty($_POST['del'])){
    $us = $mysqli->query('SELECT * FROM `transfer_users` WHERE `reg` = '.$_SESSION['id'])->fetch_assoc();
    $poks = $mysqli->query('SELECT * FROM `transfer_pokemon` WHERE  `id` = '.$_POST['del'].' AND `users` = '.$us['id'] )->fetch_assoc();
    if($poks){
        $mysqli->query("DELETE FROM `transfer_pokemon` WHERE `id` = '".$_POST['del']."' AND `users` = '".$us['id']."'");
        $response['html'] = "Покемон успешно удален!";
        $response['error'] = "success";
    }else{
        $response['html'] = "Покемон не найден!";
        $response['error'] = "error";
    }
}

if(!empty($_POST['pt'])){
    $us = $mysqli->query('SELECT * FROM `transfer_users` WHERE `reg` = '.$_SESSION['id'])->fetch_assoc();
    $poks = $mysqli->query('SELECT * FROM `transfer_pokemon` WHERE  `id` = '.$_POST['pt'].' AND `users` = '.$us['id'] )->fetch_assoc();
    $po = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  `id` = '.$poks['basenum'] )->fetch_assoc();
    $pok_base = $mysqli->query('SELECT * FROM `base_pokemons` WHERE  `id` = '.$po['eggBasenum'] )->fetch_assoc();
    if($poks and $pok_base['power_category'] < 9){
        $gen = 'h'.$poks['hp_iv'].'a'.$poks['atk_iv'].'d'.$poks['def_iv'].'s'.$poks['spd_iv'].'sa'.$poks['satk_iv'].'sd'.$poks['sdef_iv'];
        if($poks['hp_iv'] >= 32){ $gen_hp = 32; }else{ $gen_hp = $poks['hp_iv'];}
        if($poks['atk_iv'] >= 32){ $gen_atk = 32; }else{ $gen_atk = $poks['atk_iv'];}
        if($poks['def_iv'] >= 32){ $gen_def = 32; }else{ $gen_def = $poks['def_iv'];}
        if($poks['speed_iv'] >= 32){ $gen_spd = 32; }else{ $gen_spd = $poks['speed_iv'];}
        if($poks['satk_iv'] >= 32){ $gen_satk = 32; }else{ $gen_satk = $poks['satk_iv'];}
        if($poks['sdef_iv'] >= 32){ $gen_sdef = 32; }else{ $gen_sdef = $poks['sdef_iv'];}
        if($pok_base['sex_m'] == 0 and $pok_base['sex_f'] == 0 ){
            $gender = 'Бесполый';
        }else{
            if($poks['sex'] == 1){ if($pok_base['sex_m'] == 0){ $gender = 'Девочка'; }else{ $gender = 'Мальчик'; } }
			elseif($poks['sex'] == 2){ if($pok_base['sex_f'] == 0){ $gender = 'Мальчик'; }else{ $gender = 'Девочка'; } }
        }
        if($poks['traning'] == 1.1){
			$tr = 0;
		}elseif($poks['traning'] == 1.18){
		    $tr = 0;
		}elseif($poks['traning'] == 1.25){
		    $tr = 1;
		}elseif($poks['traning'] == 1.31){
		    $tr = 2;
		}elseif($poks['traning'] == 1.36){
		    $tr = 3;
		}elseif($poks['traning'] == 1.4){
		    $tr = 4;
		}
		if($poks['lvl'] >= 30 and $poks['lvl'] <= 60){
            $ev = 12;
        }elseif($poks['lvl'] > 60){
            $ev = 24;
        }else{
            $ev = 0;
        }
        if($poks['startone'] == 1) {
            $trd = "false";
        }else{
            $trd = "true";
        }
        $gen = $gen_hp.','.$gen_atk.','.$gen_def.','.$gen_spd.','.$gen_satk.','.$gen_sdef;
        $gender = $gender;
        $har = $poks['har'];
        $tren = $tr;
        if($tr == 0){
            $atat_tren = 0;
        }else{
            $atat_tren = $poks['stat_traning'];
        }
        $ev = $ev;
        $spar = $poks['reproduction'];
        $priruch = $trd;
        $lvl = 1;
        $type = $poks['tips'];
        $brt = '{"user_id":"'.$_SESSION['id'].'","date": "'.time().'"}';
        $sn = rand(1,3);
        $t_c = $gl_user['tr_pok']+1;
        
        	$ability = generateAbilityPok($pok_base['id']);
	$abil_id = $ability[0];
	$abil_slot = $ability[1];
        if($gl_user['tr_pok'] >= 6){
            if($pok_base['power_category'] == 1){
                $p1 = "4000";
                $p2 = "20000";
            }elseif($pok_base['power_category'] == 2){
                $p1 = "8000";
                $p2 = "40000";
            }elseif($pok_base['power_category'] == 3){
                $p1 = "15000";
                $p2 = "75000";
            }elseif($pok_base['power_category'] == 4){
                $p1 = "30000";
                $p2 = "150000";
            }elseif($pok_base['power_category'] == 5){
                $p1 = "60000";
                $p2 = "300000";
            }elseif($pok_base['power_category'] == 6){
                $p1 = "150000";
                $p2 = "750000";
            }elseif($pok_base['power_category'] == 7){
                $p1 = "300000";
                $p2 = "1500000";
            }elseif($pok_base['power_category'] == 8){
                $p1 = "800000";
                $p2 = "4000000";
            }
            if(!empty($_POST['pt'])){
                if($_POST['tr'] == 1){ $mon = $p2; $priruch = $priruch; }else{ $mon = $p1; $priruch = "false";}
            }
            
	
            if($priruch == 'false' and $_POST['tr'] == 1){
                $response['html'] = "Нельзя перенести этого покемона без приручения!";
                $response['error'] = "error";
            }else{
                if(item_isset(1,$mon)){
                    
                $mysqli->query("INSERT INTO `user_pokemons` (`user_id`,`basenum`,`name_new`,`ability`,`ability_slot`,`character`,`lvl`,`birthday`,`type`,`gender`,`exp_max`,`ev`,`gen`,`owner`,`master`,`sparka`,`trade`,`sparkaNumber`,`tren`,`tren_stat`) VALUES 
                                                    ('".$_SESSION['id']."','".$pok_base['id']."','".$pok_base['name_rus']."','".$abil_id."','".$abil_slot."','".$har."','".$lvl."','".$brt."','".$type."','".$gender."',12,'".$ev."','".$gen."','".$_SESSION['id']."','".$_SESSION['id']."','".$spar."','".$priruch."','".$sn."','".$tr."','".$atat_tren."') ");
                
                $mysqli->query("DELETE FROM `transfer_pokemon` WHERE `id` = '".$_POST['pt']."' AND `users` = '".$us['id']."'");
                $mysqli->query("UPDATE `users` SET `tr_pok` = '".$t_c."' WHERE `id` = '".$_SESSION['id']."'");
                $response['html'] = "Покемон успешно перенесен!";
                $response['error'] = "success";
                $response['plus'] = '<img src="/img/pokemons/animation/'.$pok_base['id'].'.png"> #'.numCheck_basenum($pok_base['id']).' '.$pok_base['name_rus'].'';
                $response['minus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x'.number_format($mon,0,'.','.').'</b>';
                minus_item(1,$mon);
            }else{
                $response['html'] = "Недостаточно монет!";
                $response['error'] = "error";
            }
            }
            
        }else{
            $mysqli->query("INSERT INTO `user_pokemons` (`user_id`,`basenum`,`name_new`,`ability`,`ability_slot`,`character`,`lvl`,`birthday`,`type`,`gender`,`exp_max`,`ev`,`gen`,`owner`,`master`,`sparka`,`trade`,`sparkaNumber`,`tren`,`tren_stat`) VALUES 
                                                    ('".$_SESSION['id']."','".$pok_base['id']."','".$pok_base['name_rus']."','".$abil_id."','".$abil_slot."','".$har."','".$lvl."','".$brt."','".$type."','".$gender."',12,'".$ev."','".$gen."','".$_SESSION['id']."','".$_SESSION['id']."','".$spar."','".$priruch."','".$sn."','".$tr."','".$atat_tren."') ");
            $mysqli->query("DELETE FROM `transfer_pokemon` WHERE `id` = '".$_POST['pt']."' AND `users` = '".$us['id']."'");
            $mysqli->query("UPDATE `users` SET `tr_pok` = '".$t_c."' WHERE `id` = '".$_SESSION['id']."'");
            $response['html'] = "Покемон успешно перенесен!";
            $response['error'] = "success";
            $response['plus'] = '<img src="/img/pokemons/animation/'.$pok_base['id'].'.png"> #'.numCheck_basenum($pok_base['id']).' '.$pok_base['name_rus'];
        }
    }else{
        $response['html'] = "Покемон не найден!";
        $response['error'] = "error";
    }
}

if(!empty($_POST['it'])){
    $us = $mysqli->query('SELECT * FROM `transfer_users` WHERE `reg` = '.$_SESSION['id'])->fetch_assoc();
    $uss = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
    $it = $mysqli->query('SELECT * FROM `transfer_items` WHERE `user_id` = '.$us['id']);
    if($uss['tr_it'] != 1){
        while($its = $it->fetch_assoc()){
        $it_n = 0;
		if($its['item_id'] == 6){$it_n = 26;}
		elseif($its['item_id'] == 7){$it_n = 27;}
		elseif($its['item_id'] == 8){$it_n = 28;}
		elseif($its['item_id'] == 9){$it_n = 29;}
		elseif($its['item_id'] == 10){$it_n = 30;}
		elseif($its['item_id'] == 11){$it_n = 31;}
		elseif($its['item_id'] == 12){$it_n = 34;}
		elseif($its['item_id'] == 15){$it_n = 17;}
		elseif($its['item_id'] == 17){$it_n = 32;}
		elseif($its['item_id'] == 25){$it_n = 22;}
		elseif($its['item_id'] == 26){$it_n = 18;}
		elseif($its['item_id'] == 27){$it_n = 21;}
		elseif($its['item_id'] == 28){$it_n = 19;}
		elseif($its['item_id'] == 29){$it_n = 10;}
		elseif($its['item_id'] == 30){$it_n = 11;}
		elseif($its['item_id'] == 31){$it_n = 12;}
		elseif($its['item_id'] == 32){$it_n = 13;}
		elseif($its['item_id'] == 40){$it_n = 80;}
		elseif($its['item_id'] == 41){$it_n = 83;}
		elseif($its['item_id'] == 42){$it_n = 81;}
		elseif($its['item_id'] == 43){$it_n = 82;}
		elseif($its['item_id'] == 44){$it_n = 84;}
		elseif($its['item_id'] == 66){$it_n = 245;}
		elseif($its['item_id'] == 67){$it_n = 246;}
		elseif($its['item_id'] == 90){$it_n = 85;}
		elseif($its['item_id'] == 91){$it_n = 99;}
		elseif($its['item_id'] == 92){$it_n = 87;}
		elseif($its['item_id'] == 93){$it_n = 86;}
		elseif($its['item_id'] == 94){$it_n = 89;}
		elseif($its['item_id'] == 95){$it_n = 88;}
		elseif($its['item_id'] == 96){$it_n = 94;}
		elseif($its['item_id'] == 97){$it_n = 100;}
		elseif($its['item_id'] == 98){$it_n = 92;}
		elseif($its['item_id'] == 99){$it_n = 91;}
		elseif($its['item_id'] == 100){$it_n = 102;}
		elseif($its['item_id'] == 101){$it_n = 101;}
		elseif($its['item_id'] == 102){$it_n = 98;}
		elseif($its['item_id'] == 103){$it_n = 95;}
		elseif($its['item_id'] == 104){$it_n = 103;}
		elseif($its['item_id'] == 105){$it_n = 104;}
		elseif($its['item_id'] == 106){$it_n = 93;}
		elseif($its['item_id'] == 107){$it_n = 106;}
		elseif($its['item_id'] == 111){$it_n = 201;}
		elseif($its['item_id'] == 112){$it_n = 199;}
		elseif($its['item_id'] == 113){$it_n = 202;}
		elseif($its['item_id'] == 114){$it_n = 204;}
		elseif($its['item_id'] == 115){$it_n = 203;}
		elseif($its['item_id'] == 116){$it_n = 200;}
		elseif($its['item_id'] == 120){$it_n = 105;}
		elseif($its['item_id'] == 121){$it_n = 96;}
		elseif($its['item_id'] == 122){$it_n = 97;}
		elseif($its['item_id'] == 124){$it_n = 205;}
		elseif($its['item_id'] == 125){$it_n = 206;}
		elseif($its['item_id'] == 126){$it_n = 207;}
		elseif($its['item_id'] == 127){$it_n = 208;}
		elseif($its['item_id'] == 128){$it_n = 209;}
		elseif($its['item_id'] == 129){$it_n = 210;}
		elseif($its['item_id'] == 130){$it_n = 211;}
		elseif($its['item_id'] == 131){$it_n = 212;}
		elseif($its['item_id'] == 132){$it_n = 213;}
		elseif($its['item_id'] == 133){$it_n = 214;}
		elseif($its['item_id'] == 134){$it_n = 215;}
		elseif($its['item_id'] == 135){$it_n = 216;}
		elseif($its['item_id'] == 136){$it_n = 217;}
		elseif($its['item_id'] == 137){$it_n = 218;}
		elseif($its['item_id'] == 138){$it_n = 219;}
		elseif($its['item_id'] == 139){$it_n = 220;}
		elseif($its['item_id'] == 140){$it_n = 221;}
		elseif($its['item_id'] == 141){$it_n = 222;}
        elseif($its['item_id'] == 142){$it_n = 267;}
        elseif($its['item_id'] == 161){$it_n = 56;}
        elseif($its['item_id'] == 162){$it_n = 62;}
        elseif($its['item_id'] == 163){$it_n = 53;}
        elseif($its['item_id'] == 170){$it_n = 185;}
        elseif($its['item_id'] == 171){$it_n = 169;}
		elseif($its['item_id'] == 174){$it_n = 154;}
		elseif($its['item_id'] == 179){$it_n = 158;}
		elseif($its['item_id'] == 180){$it_n = 157;}
        elseif($its['item_id'] == 181){$it_n = 191;}
        elseif($its['item_id'] == 182){$it_n = 14;}
        elseif($its['item_id'] == 183){$it_n = 15;}
        elseif($its['item_id'] == 184){$it_n = 16;}
        elseif($its['item_id'] == 194){$it_n = 255;}
        elseif($its['item_id'] == 195){$it_n = 256;}
        elseif($its['item_id'] == 199){$it_n = 143;}
        elseif($its['item_id'] == 201){$it_n = 51;}
        elseif($its['item_id'] == 202){$it_n = 49;}
        elseif($its['item_id'] == 203){$it_n = 41;}
        elseif($its['item_id'] == 204){$it_n = 35;}
        elseif($its['item_id'] == 205){$it_n = 40;}
        elseif($its['item_id'] == 206){$it_n = 36;}
        elseif($its['item_id'] == 207){$it_n = 44;}
        elseif($its['item_id'] == 208){$it_n = 45;}
        elseif($its['item_id'] == 209){$it_n = 47;}
        elseif($its['item_id'] == 210){$it_n = 48;}
        elseif($its['item_id'] == 211){$it_n = 43;}
        elseif($its['item_id'] == 212){$it_n = 50;}
        elseif($its['item_id'] == 213){$it_n = 46;}
        elseif($its['item_id'] == 214){$it_n = 52;}
        elseif($its['item_id'] == 215){$it_n = 38;}
        elseif($its['item_id'] == 216){$it_n = 37;}
        elseif($its['item_id'] == 217){$it_n = 42;}
        elseif($its['item_id'] == 218){$it_n = 39;}
        elseif($its['item_id'] == 236){$it_n = 155;}
        elseif($its['item_id'] == 314){$it_n = 23;}
        elseif($its['item_id'] == 315){$it_n = 24;}
        elseif($its['item_id'] == 317){$it_n = 447;}
        elseif($its['item_id'] == 329){$it_n = 193;}
        elseif($its['item_id'] == 415){$it_n = 195;}
        elseif($its['item_id'] == 423){$it_n = 33;}
        elseif($its['item_id'] == 431){$it_n = 260;}
        elseif($its['item_id'] == 432){$it_n = 257;}
        elseif($its['item_id'] == 433){$it_n = 262;}
        elseif($its['item_id'] == 434){$it_n = 261;}
        elseif($its['item_id'] == 435){$it_n = 258;}
        elseif($its['item_id'] == 436){$it_n = 259;}
        elseif($its['item_id'] == 450){$it_n = 139;}
        elseif($its['item_id'] == 451){$it_n = 133;}
        elseif($its['item_id'] == 452){$it_n = 129;}
        elseif($its['item_id'] == 453){$it_n = 141;}
        elseif($its['item_id'] == 454){$it_n = 131;}
        elseif($its['item_id'] == 455){$it_n = 137;}
        elseif($its['item_id'] == 456){$it_n = 134;}
        elseif($its['item_id'] == 457){$it_n = 127;}
        elseif($its['item_id'] == 458){$it_n = 128;}
        elseif($its['item_id'] == 459){$it_n = 136;}
        elseif($its['item_id'] == 460){$it_n = 125;}
        elseif($its['item_id'] == 461){$it_n = 140;}
        elseif($its['item_id'] == 462){$it_n = 132;}
        elseif($its['item_id'] == 463){$it_n = 126;}
        elseif($its['item_id'] == 464){$it_n = 100;}
        elseif($its['item_id'] == 465){$it_n = 135;}
        elseif($its['item_id'] == 466){$it_n = 130;}
        elseif($its['item_id'] == 467){$it_n = 138;}
        elseif($its['item_id'] == 500){$it_n = 142;}
        elseif($its['item_id'] == 501){$it_n = 144;}
        elseif($its['item_id'] == 502){$it_n = 145;}
        elseif($its['item_id'] == 503){$it_n = 99;}
        elseif($its['item_id'] >= 1002 and $its['item_id'] <= 1050){ $it_n = 417;}
        if($it_n != 0){
            $t = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$it_n)->fetch_assoc();
            itemAdd($it_n,$its['count']);
            $mysqli->query('UPDATE `users` SET `tr_it` = 1 WHERE `id` = '.$_SESSION['id']);
            $tpl .= '<img src="/img/world/items/little/'.$it_n.'.png" class="item">'.$t['name'].' <b>x'.$its['count'].'</b><br>';
        }
    }
    $response['html'] = "Инвентарь успешно перенесен!";
    $response['error'] = "success";
    $response['plus'] = $tpl;
    }else{
        $response['html'] = "Вы уже переносили инвентарь!";
    $response['error'] = "error";
    }
    
}
echo json_encode($response);
?>

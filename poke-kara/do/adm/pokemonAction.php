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
if(!empty($_POST['type'])){
    $id = $_POST['type'];
    $e = explode(',',$id);
    $pok = $mysqli->query('SELECT `id`,`name_rus` FROM `base_pokemons` WHERE `name_rus` = "'.$e[0].'" ')->fetch_assoc();
    $user = $mysqli->query('SELECT `id`,`user_group`,`login` FROM `users` WHERE `id` = "'.$e[23].'" ')->fetch_assoc();
    if(isset($pok)){
        if(isset($user)){
            if($e[1] >= 1 and $e[1] <= 100){
                
                $number = $pok['id'];
                $name = $pok['name_rus'];
	            $user = $user['id'];
	            $lvl=$e[1];
	            $gen= $e[7].','.$e[8].','.$e[9].','.$e[10].','.$e[11].','.$e[12];
	            $trade = $e[14];
	            $sparka = $e[13];
	            $shine= $e[4];
	            $character= $e[3];
	            $ev= $e[6];
	            $form= $e[2];
	            $sex= $e[5];
	            $tren= $e[26];
	            $tren_st= $e[27];
	            if($e[15] == 'false'){ $d1 = 0; }else{ $d1 = 1;}
	            if($e[16] == 'false'){ $d2 = 0; }else{ $d2 = 1;}
	            if($e[17] == 'false'){ $d3 = 0; }else{ $d3 = 1;}
	            if($e[18] == 'false'){ $d4 = 0; }else{ $d4 = 1;}
	            if($e[19] == 'false'){ $d5 = 0; }else{ $d5 = 1;}
	            if($e[20] == 'false'){ $d6 = 0; }else{ $d6 = 1;}
	            if($e[21] == 'false'){ $d7 = 0; }else{ $d7 = 1;}
	            if($e[22] == 'false'){ $d8 = 0; }else{ $d8 = 1;}
	            $static = $d1.','.$d2.','.$d3.','.$d4.','.$d5.','.$d6.','.$d7.','.$d8;
	
	            $month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
	            $dateGet = '{"user_id":"'.$user.'","date": "'.time().'"}';
	            $har = $mysqli->query("SELECT * FROM `har` WHERE `id_har` = '".$character."' ")->fetch_assoc();
	

	$s1 = round((($pok_base['hp'] * 2) + $hg) * (1/100) + 10 + 1);
	$s2 = round((($pok_base['atk'] * 2 + $ag) * 1/100 + 5) * $har['atk']);
	$s3 = round((($pok_base['def'] * 2 + $dg) * 1/100 + 5) * $har['def']);
	$s4 = round((($pok_base['spd'] * 2 + $sg) * 1/100 + 5) * $har['speed']);
	$s5 = round((($pok_base['satk'] * 2 + $sag) * 1/100 + 5) * $har['satk']);
	$s6 = round((($pok_base['sdef'] * 2 + $sdg) * 1/100 + 5) * $har['sdef']);

	$sparkNumber = mt_rand(1, 3);
    $stats = $s1.','.$s2.','.$s3.','.$s4.','.$s5.','.$s6;
	$expirience = 1;
	$expirienceMax = 6;

	$Ability = $mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$pok['id'])->fetch_assoc();
	$abil = $e[24];
	if($abil == 1){
	    $ability = $Ability['slot1'];
	}elseif($abil == 2){
	    $ability = $Ability['slot2'];
	}else{
	    $ability = $Ability['hidden'];
	}
	
	if($ability == 0){
	    $ability = $Ability['slot1'];
	    $abil = 1;
	}
	$abil_id = $ability;
	$abil_slot = $abil;
	
	
	if(!empty($e[25])){
	    $item = $e[25];
	    $item_str = "999,999";
	}else{
	    $item = 0;
	    $item_str = NULL;
	}
	
	
	$mysqli->query("INSERT INTO `user_pokemons` (`tren`,`tren_stat`,`item_id`,`item_str`,`user_id`,`basenum`,`name_new`,`form`,`ability`,`ability_slot`,`character`,`lvl`,`birthday`,`active`,`type`,`gender`,`exp`,`exp_max`,`ev`,`hp`,`stats`,`gen`,`owner`,`master`,`startGame`,`sparka`,`attacks`,`sparkaNumber`,`trade`,`static`) VALUES 
	                                            ('".$tren."','".$tren_st."','".$item."','".$item_str."','".$user."','".$number."','".$name."','".$form."','".$abil_id."','".$abil_slot."','".$character."','".$lvl."','".$dateGet."','0','".$shine."','".$sex."','".$expirience."','".$expirienceMax."','".$ev."','".$s1."','".$stats."','".$gen."','".$user."','".$user."','0','".$sparka."','0,0,0,0','".$sparkNumber."','".$trade."','".$static."') ");
	$id_pok_new = $mysqli->insert_id;
    $mysqli->query("INSERT INTO `log_pokemon` (`date`,`pok`,`pok_id`,`type`) VALUES 
	                                            ('".date('d.m H:i')."','".$name."','".$id_pok_new."','pok') ");            

                $response['text'] = 'Успешно';
                $response['error'] = 'success';
            }else{
                $response['text'] = 'Неподходящий уровень';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'Пользователь не найден';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Покемон не найден';
        $response['error'] = 'error';
    }
}
if(!empty($_POST['items'])){
    $id = $_POST['items'];
    $e = explode(',',$id);
    $pok = $mysqli->query('SELECT `id`,`name` FROM `base_items` WHERE `name` = "'.$e[0].'" ')->fetch_assoc();
    $user = $mysqli->query('SELECT `id`,`user_group`,`login` FROM `users` WHERE `id` = "'.$e[2].'" ')->fetch_assoc();
    if(isset($pok)){
        if(isset($user)){
            if($e[1] <= 1000000){
                $name = $e[0].' x'.$e[1];
                $count = $e[1];
                $user = $e[2];
                if($e[3] == 'true'){ $tr = 1; }else{ $tr = 0;}
                if($tr == 1){
                    $mysqli->query("INSERT INTO `items_users` (`item_id`,`count`,`user`,`trophy`) VALUES 
	                                            ('".$pok['id']."','".$count."','".$user."','".$tr."') ");
	                                            
                }else{
                    itemAdd($pok['id'],$count,$user);
                }
                
    $mysqli->query("INSERT INTO `log_pokemon` (`date`,`pok`,`pok_id`,`type`) VALUES 
	                                            ('".date('d.m H:i')."','".$name."','".$user."','items') ");            

                $response['text'] = 'Успешно';
                $response['error'] = 'success';
                
            }else{
                $response['text'] = 'Количество превышает 1.000.000';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'Пользователь не найден';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Предмет не найден';
        $response['error'] = 'error';
    }
}

if(!empty($_POST['attack'])){
    $id = $_POST['attack'];
    $e2 = explode(',',$id);
    $attack2 = $mysqli->query('SELECT `id`,`name` FROM `base_atk` WHERE `name` = "'.$e2[0].'" ')->fetch_assoc();
    $pok2 = $mysqli->query('SELECT `id` FROM `user_pokemons` WHERE `id` = "'.$e2[1].'" ')->fetch_assoc();
    if(isset($pok2)){
        if(isset($attack2)){
            $name = $e2[0];
            $pok = $e2[1];
            $mysqli->query("INSERT INTO `user_pokemons_tm` (`pok`,`attacks`) VALUES 
	                                            ('".$pok2['id']."','".$attack2['id']."') ");
                
    $mysqli->query("INSERT INTO `log_pokemon` (`date`,`pok`,`pok_id`,`type`) VALUES 
	                                            ('".date('d.m H:i')."','".$name."','".$pok."','attack') ");            

            $response['text'] = 'Успешно';
            $response['error'] = 'success';
        }else{
            $response['text'] = 'Покемон не найден';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Атака не найдена';
        $response['error'] = 'error';
    }
}
if(!empty($_POST['attack2'])){
    $id = $_POST['attack2'];
    $e2 = explode(',',$id);
    $attack2 = $mysqli->query('SELECT `id`,`name` FROM `base_atk` WHERE `id` = "'.$e2[0].'" ')->fetch_assoc();
    $pok2 = $mysqli->query('SELECT `id` FROM `user_pokemons` WHERE `id` = "'.$e2[1].'" ')->fetch_assoc();
    if(isset($pok2)){
        if(isset($attack2)){
            $name = $attack2['name'];
            $pok = $e2[1];
            $mysqli->query("INSERT INTO `user_pokemons_tm` (`pok`,`attacks`) VALUES 
	                                            ('".$pok2['id']."','".$attack2['id']."') ");
                
    $mysqli->query("INSERT INTO `log_pokemon` (`date`,`pok`,`pok_id`,`type`) VALUES 
	                                            ('".date('d.m H:i')."','".$name."','".$pok."','attack') ");            

            $response['text'] = 'Успешно';
            $response['error'] = 'success';
        }else{
            $response['text'] = 'Покемон не найден';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Атака не найдена';
        $response['error'] = 'error';
    }
}

// if(!empty($_POST['id'])){
//     $id = escapeMe($_POST['id']);
//     $pok = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$id)->fetch_assoc();
//     $poka = $mysqli->query('SELECT * FROM `base_ability_pokemon` WHERE `id` = '.$id)->fetch_assoc();
//     $response['nameRU'] = $pok['name_rus'];
//     $response['nameENG'] = $pok['name'];   
//     $response['weight'] = $pok['weight'];
//     $response['height'] = $pok['height'];  
//     $response['type1'] = $pok['type'];
//     $response['type2'] = $pok['type_two']; 
//     $response['exp_group'] = $pok['exp_group'];
//     $response['power_category'] = $pok['power_category'];
//     $response['catch'] = $pok['chanceCatch'];
//     $response['sex_m'] = $pok['sex_m'];
//     $response['sex_f'] = $pok['sex_f'];
//     $response['hp'] = $pok['hp'];
//     $response['atk'] = $pok['atk'];   
//     $response['def'] = $pok['def'];
//     $response['spd'] = $pok['spd'];  
//     $response['satk'] = $pok['satk'];
//     $response['sdef'] = $pok['sdef'];
//     $response['ability'] = ability_name_eng($poka['slot1']);
//     $response['ability2'] = ability_name_eng($poka['slot2']);
//     $response['ability3'] = ability_name_eng($poka['hidden']);
//     $response['type_item'] = $pok['evol_type_item'];
//     $response['item'] = $pok['evol_item'];
//     $response['effort'] = $pok['effort'];
//     $response['rol'] = $pok['v_rol'];
//     $response['evol'] = $pok['nextEvolution'];
    
//     $response['about'] = $pok['about'];
//     $response['class'] = $pok['class'];
    
//     $response['lvl'] = $pok['evol_lvl'];
//     $response['nevol'] = $pok['evol_basenum'];
//     $response['egg'] = $pok['eggBasenum'];
//     $response['type_evol'] = $pok['evol_type'];
// }
// if(!empty($_POST['type'])){
//      $info = escapeMe($_POST['type']);
//     $value = escapeMe($_POST['val']);
//     $bas = escapeMe($_POST['basenum']);
//     $pok = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$bas)->fetch_assoc();
//     if($pok){
//         if($info == 'slot1' or $info == 'slot2' or $info == 'hidden'){
//             if($value != "0"){
//                 $abl = $mysqli->query('SELECT * FROM `base_ability` WHERE `name` = "'.$value.'" ')->fetch_assoc();
//             }else{
//                 $abl = true;
//             }
            
//             if($abl){
//                 $mysqli->query('UPDATE `base_ability_pokemon` SET `'.$info.'` = "'.$abl['id'].'" WHERE `id` = '.$bas);
//                 $response['text'] = "Информация успешно изменена!";
//                 $response['error'] = "success";
//             }else{
//                 $response['text'] = "Способность не найдена!";
//                 $response['error'] = "error";
//             }
//         }else{
//             $mysqli->query('UPDATE `base_pokemons` SET `'.$info.'` = "'.$value.'" WHERE `id` = '.$bas);
//             $response['text'] = "Информация успешно изменена!";
//         $response['error'] = "success";
//         }
//     }else{
//         $response['text'] = "Покемон не найден!";
//         $response['error'] = "error";
//     }
// }


// if(!empty($_POST['typ'])){
//     $pokemon = escapeMe($_POST['pokemon']);
//     $type = escapeMe($_POST['typ']);
//     $atk = escapeMe($_POST['name']);
//     if($pokemon and $type){
//         if($pokemon <= 898 and $pokemon > 0){
//                 if($type == "tm" or $type == "tr"){
//                         if($type == "tm"){
//                             $tm = $atk;
//                         }else{
//                             $tm = 2000+$atk;
//                         }
//                                 $mysqli->query("INSERT INTO `attac_poke_tm` (`poke_base_id`,`tm_id`) VALUES('".$pokemon."','".$tm."') ");
                            
                        
//                         $response['text'] = "Удачно!";
//                         $response['error'] = "success";
//                 }else{
//                     $response['text'] = "Тип атаки не подходит!";
//                     $response['error'] = "error";
//                 }
            
//         }else{
//             $response['text'] = "Покемон не найден!";
//             $response['error'] = "error";
//         }
//     }else{
//         $response['text'] = "Одно из полей отсутствует!";
//         $response['error'] = "error";
//     }
// }

// if(!empty($_POST['vst'])){
//     $vst = escapeMe($_POST['vst']);
//     $cop = escapeMe($_POST['cop']);
//     $batk = $mysqli->query('SELECT * FROM `base_attacks_pokemons` WHERE `pok` = "'.$cop.'" AND `type` = "sex" ')->fetch_assoc();
//     if($batk){
//         $batk2 = $mysqli->query('SELECT * FROM `base_attacks_pokemons` WHERE `pok` = "'.$vst.'" AND `type` = "sex" ')->fetch_assoc();
//         if($batk2){
//             $mysqli->query('UPDATE `base_attacks_pokemons` SET `attacks` = "'.$batk['attacks'].'" WHERE `type` = "sex" AND `pok` = '.$vst);
//         }else{
//             $a = $mysqli->query("INSERT INTO `base_attacks_pokemons` (`pok`,`attacks`,`type`) VALUES('".$vst."','".$batk['attacks']."','sex') ");
//         }
//         $response['text'] = "Атаки успешно скопированы!";
//         $response['error'] = "success";
//     }else{
//         $response['text'] = "У этого покемона нет яйцевых атак!";
//         $response['error'] = "error";
//     }
// }

// if(!empty($_POST['del'])){
//     $del = escapeMe($_POST['del']);
//     $tmtr = escapeMe($_POST['tmtr']);
//     $numb = escapeMe($_POST['delnumb']);
//     if($tmtr == "tm"){ $tm = $numb;}else{ $tm = 2000+$numb;}
//     $mysqli->query('DELETE FROM `attac_poke_tm` WHERE `poke_base_id` = "'.$del.'" AND `tm_id` = "'.$tm.'" ');
//     $response['text'] = "Атака успешно удалена!";
//         $response['error'] = "success";
// }


// if(!empty($_POST['check'])){
//     $id = escapeMe($_POST['check']);
//     $batk = $mysqli->query('SELECT * FROM `base_attacks_pokemons` WHERE `pok` = "'.$id.'" AND `type` = "lvl" ')->fetch_assoc();
//     $batk2 = $mysqli->query('SELECT * FROM `base_attacks_pokemons` WHERE `pok` = "'.$id.'" AND `type` = "sex" ')->fetch_assoc();
//     $atkLvl = explode(',',$batk['lvl']);
//     $atkList = explode(',',$batk['attacks']);
//     $a .= '<b>Атаки по уровню:</b><br>';
//     if($batk){
//       for($i=0;$i<count($atkLvl);$i++){
//     		$info = Work::$sql->query('SELECT
//     										`id`,
//                                             `name`,
//                                             `title`,
//                                             `type`,
//                                             `category`,
//                                             `priority`,
//                                             `power`,
//                                             `accuracy`,
//                                             `pp`
//                                         FROM `base_atk`
//     									WHERE `id` = '.$atkList[$i]
//                                     )->fetch_assoc();
//         $a .= $atkLvl[$i].'lvl - '.$info['name'].' <br>';
//     	}
//     	}else{
//     	    $a .= '<div class="titl noatk">Покемон не изучает атак по уровню!</div>';
//     	}
//     	$a .= '<br><b>Атаки по разведению:</b><br>';
//     	if($batk2){
//     $atkList = explode(',',$batk2['attacks']);
//     for($i=0;$i<count($atkList);$i++){
//       $info = Work::$sql->query('SELECT
//                       `id`,
//                                           `name`,
//                                           `title`,
//                                           `type`,
//                                           `category`,
//                                           `priority`,
//                                           `power`,
//                                           `accuracy`,
//                                           `pp`
//                                       FROM `base_atk`
//                     WHERE `id` = '.$atkList[$i]
//                                   )->fetch_assoc();
//       $a .= $info['name'].' <br>';
//     }
//     }else{
//         $a .= '<div class="titl noatk">Покемон не изучает атак по разведению!</div>';
//     }
//       $response['atklist'] = $a;
    
    
    
    
    
    
    
    
    
    
// }
echo json_encode($response);
?>
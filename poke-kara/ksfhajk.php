<?php

//ini_set('display_errors', 'ON');
//error_reporting(E_ALL);
/*define('', '');*/
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/battlepass_cron.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}
if(!empty($_GET['ksfhajk'])){
    
    
    if($_GET['ksfhajk'] == 1){
        $mysqli->query('UPDATE `system` SET `closed` = "1" WHERE `id` = "1"');
        
        if(date("j") == 1){
            $calendar_delete = $mysqli->query('SELECT * FROM `users_calendar_day`');
            $system = $mysqli->query('SELECT * FROM `system` WHERE `id` = 1')->fetch_assoc();
            $mont = explode(',',$system['calendar']);
            while($calendar_delete_us = $calendar_delete->fetch_assoc()){
                
                $ar = explode(',',$calendar_delete_us['day']);
                $ars = count($ar);
                if($ars >= $mont[1]){
                    itemAdd(513,2,$calendar_delete_us['user']);
                    itemAdd(rand(205,222),1,$calendar_delete_us['user']);
                    itemAdd(rand(205,222),1,$calendar_delete_us['user']);
                    update_achiv(31,1,$calendar_delete_us['user']);
                }
            }
            $mysqli->query('TRUNCATE `users_calendar_day`');
            $mysqli->query('TRUNCATE `base_calendar_day`');
            for($i=1;$i<=date("t");$i++){
                if(rand(1,100) <= 80){
                    if(rand(1,100) <= 70){
                        $r = rand(1,110);
                        if($r >= 1 AND $r <= 10){ $item = '1,30000';}
                        elseif($r >= 11 AND $r <= 20){ $item = '1,70000';}
                        elseif($r >= 21 AND $r <= 30){ $item = '25,3';}
                        elseif($r >= 31 AND $r <= 40){ $item = '56,5';}
                        elseif($r >= 41 AND $r <= 50){ $item = '62,5';}
                        elseif($r >= 51 AND $r <= 60){ $item = '13,5';}
                        elseif($r >= 61 AND $r <= 70){ $item = '31,2';}
                        elseif($r >= 71 AND $r <= 80){ $item = rand(35,52).',5';}
                        elseif($r >= 81 AND $r <= 90){ $item = '124,2';}
                        elseif($r >= 91 AND $r <= 100){ $item = rand(367,372).',5';}
                        elseif($r >= 101 AND $r <= 110){ $item = '240,3';}
                        $mysqli->query("INSERT INTO `base_calendar_day` (`type`,`item`,`pok`) VALUES ('1','".$item."','0')");
                    }else{
                        $input = array(182,185,184,191,193,194,195,196,243,448,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,150,153,155,173,174,198,241,246,256,269,270,469,80,81,82,83,84,85,86,90,96,97,99,100,107,108,513);
                        $rand_keys = array_rand($input, 2);
                        $newForm = $input[$rand_keys[1]];
                        $mysqli->query("INSERT INTO `base_calendar_day` (`type`,`item`,`pok`) VALUES ('1','".$newForm.",1','0')");
                    }
                }else{
                    $input = array(27,37,48,52,86,88,114,213,231,270,320,363,440,527,613,669,677,779,852);
                    $rand_keys = array_rand($input, 2);
                    $newForm = $input[$rand_keys[1]];
                    $mysqli->query("INSERT INTO `base_calendar_day` (`type`,`item`,`pok`) VALUES ('2','','".$newForm."')");
                }
            }      
            $dnb = date("n").','.date("t");
            $mysqli->query('UPDATE `system` SET `calendar` = "'.$dnb.'" WHERE `id` = "1"');
        }
        
        echo "Done1";
    }
    
    // Простая защита "секретом"
/* ----------------------------------------------------------
 * 10) Крон боевого пропуска
 * URL: ksfhajk.php?ksfhajk=10[&force=daily|weekly|both|special|all]
 * Возвращает: "Done10", "Done10(daily)" и т.п.
 * ---------------------------------------------------------- */
if (isset($_GET['ksfhajk']) && (string)$_GET['ksfhajk'] === '10') {
    // всегда отдаём 200 OK и простой текст как у остальных задач
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
    }
    ignore_user_abort(true);
    set_time_limit(120);

    // мягкий лок, чтобы не запускать одновременно
    $lockFile = sys_get_temp_dir() . '/bp_cron.lock';
    $fp = @fopen($lockFile, 'c+');
    if ($fp && !@flock($fp, LOCK_EX | LOCK_NB)) {
        echo "Done10(lock)\n"; // уже крутится — выходим «успешно»
        exit;
    }

    // нормализуем force: daily|weekly|both|special|all (поддержка сокращений)
    $force = null;
    if (!empty($_GET['force'])) {
        $f = strtolower(trim((string)$_GET['force']));
        $map = [
            'd' => 'daily',   'day' => 'daily',    'daily'   => 'daily',
            'w' => 'weekly',  'week'=> 'weekly',   'weekly'  => 'weekly',
            'both' => 'both',
            's' => 'special', 'spec'=> 'special',  'special' => 'special',
            'all' => 'all'
        ];
        if (isset($map[$f])) $force = $map[$f];
    }

    // сам проход (функция подключена из /inc/battlepass_cron.php)
    // внутри bp_run_cron реализованы:
    //  - истечение просроченных
    //  - daily/weekly сброс и раздача
    //  - довыдача special только для type=2
    //  - выбор миссий с учётом cooldown и без дублей «одновременно»
    try {
        bp_run_cron($mysqli, $force);
        echo "Done10" . ($force ? "({$force})" : "") . "\n";
    } catch (Throwable $e) {
        // не валим крон: возвращаем «успешно», как и другие ветки, чтобы wget не краснел
        // при желании можно логировать в таблицу/файл
        echo "Done10\n";
    }

    if ($fp) { @flock($fp, LOCK_UN); @fclose($fp); }
    exit;
}

/* ----------------------------------------------------------
 * 11) Крон мировых боссов
 * URL: ksfhajk.php?ksfhajk=11
 * Возвращает: "Done11", "Done11(spawned:2,expired:1)" и т.п.
 * ---------------------------------------------------------- */
if (isset($_GET['ksfhajk']) && (string)$_GET['ksfhajk'] === '11') {
    // отдаём 200 OK для cron
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
    }
    ignore_user_abort(true);
    set_time_limit(120);

    // мягкий лок, чтобы не запускать одновременно
    $lockFile = sys_get_temp_dir() . '/world_boss_cron.lock';
    $fp = @fopen($lockFile, 'c+');
    if ($fp && !@flock($fp, LOCK_EX | LOCK_NB)) {
        echo "Done11(lock)\n";
        exit;
    }

    try {
        // Подключаем класс мировых боссов
        require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/world_boss_manager.php';
        
        $spawned = 0;
        $expired = 0;
        $rewardsProcessed = 0;
        
        // 1. Спавним запланированных боссов
        $activeBefore = count(WorldBossManager::getActiveBosses());
        WorldBossManager::spawnScheduledBosses();
        $activeAfter = count(WorldBossManager::getActiveBosses());
        $spawned = max(0, $activeAfter - $activeBefore);
        
        // 2. Удаляем просроченных боссов
        $expiredInstances = $mysqli->query("
            SELECT COUNT(*) as count FROM world_boss_instances 
            WHERE status IN ('spawning', 'active') AND expire_time <= NOW()
        ")->fetch_assoc();
        $expiredBefore = (int)($expiredInstances['count'] ?? 0);
        
        WorldBossManager::despawnExpiredBosses();
        $expired = $expiredBefore;
        
        // 3. Обрабатываем завершённые рейды (награды)
        if (class_exists('BossRewardDistributor')) {
            $defeatedInstances = $mysqli->query("
                SELECT COUNT(*) as count FROM world_boss_instances 
                WHERE status = 'defeated' AND defeat_time IS NOT NULL
            ")->fetch_assoc();
            $defeatedBefore = (int)($defeatedInstances['count'] ?? 0);
            
            BossRewardDistributor::processCompletedRaids();
            $rewardsProcessed = $defeatedBefore;
        }
        
        // Формируем статистику
        $stats = [];
        if ($spawned > 0) $stats[] = "spawned:{$spawned}";
        if ($expired > 0) $stats[] = "expired:{$expired}";
        if ($rewardsProcessed > 0) $stats[] = "rewards:{$rewardsProcessed}";
        
        $statsText = !empty($stats) ? '(' . implode(',', $stats) . ')' : '';
        echo "Done11{$statsText}\n";
        
        // Логируем для отладки (опционально)
        if ($spawned > 0 || $expired > 0) {
            error_log("WORLD_BOSS_CRON: spawned:{$spawned}, expired:{$expired}, rewards:{$rewardsProcessed} at " . date('Y-m-d H:i:s'));
        }
        
    } catch (Throwable $e) {
        // Не валим крон - возвращаем успешно для wget
        echo "Done11\n";
        error_log("WORLD_BOSS_CRON_ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }

    if ($fp) { @flock($fp, LOCK_UN); @fclose($fp); }
    exit;
}

    if($_GET['ksfhajk'] == 2){
        $mysqli->query('TRUNCATE `atk_aromatic_mist`');
        $mysqli->query('TRUNCATE `atk_echo`');
        $mysqli->query('TRUNCATE `atk_furycutter`');
        $mysqli->query('TRUNCATE `atk_helping_hand`');
        $mysqli->query('TRUNCATE `atk_rollout`');
        $mysqli->query('TRUNCATE `atk_tripleaxel`');
        $mysqli->query('TRUNCATE `battle`');
        $mysqli->query('TRUNCATE `battle_block`');
        $mysqli->query('TRUNCATE `battle_effects`');
        $mysqli->query('TRUNCATE `battle_log`');
        $mysqli->query('TRUNCATE `battle_screen`');
        $mysqli->query('TRUNCATE `battle_settings`');
        $mysqli->query('TRUNCATE `battle_status`');
        $mysqli->query('TRUNCATE `user_mission_day`');
        $mysqli->query('TRUNCATE `user_notice`');

        $d = time()-3600*24*3;
        $news_delete = $mysqli->query('SELECT * FROM `friends_news` WHERE `date` < '.$d.' LIMIT 2000');
        while($news_delete_us = $news_delete->fetch_assoc()){
            $mysqli->query('DELETE FROM friends_news WHERE id = '.$news_delete_us['id']);
        }

        if(date('l') == "Monday"){
            $arena_delete = $mysqli->query('SELECT * FROM `users` LIMIT 1500');
            while($arena_delete_us = $arena_delete->fetch_assoc()){
                $mysqli->query('UPDATE `users` SET `arena` = "0,0" WHERE `id` = '.$arena_delete_us['id']);
                $mysqli->query('DELETE FROM `items_users` WHERE `item_id` = "480" ');
            }
            // $mysqli->query('TRUNCATE ...');
        }

        $loto_delete = $mysqli->query('SELECT * FROM `users` WHERE `loto` != 0 LIMIT 1500');
        while($loto_delete_us = $loto_delete->fetch_assoc()){
            $mysqli->query('UPDATE `users` SET `loto` = "0" WHERE `id` = '.$loto_delete_us['id']);
        }

        $pokdelete = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = 2 AND `del_pok` != 0 ORDER BY `id` ASC');
        while($pokdelete_us = $pokdelete->fetch_assoc()){
            if($pokdelete_us['del_time'] < time()){
                $mysqli->query('DELETE FROM user_pokemons WHERE id = '.$pokdelete_us['id']);
            }
        }

        $giftdelete = $mysqli->query('SELECT * FROM `GiftFriend` WHERE `active` = 0 ORDER BY `id` ASC');
        while($giftdelete_us = $giftdelete->fetch_assoc()){
            if($giftdelete_us['date_limit'] < time()){
                $mysqli->query('UPDATE `GiftFriend` SET `active` = "2" WHERE `id` = '.$giftdelete_us['id']);
            }
        }

        $usr0 = $mysqli->query('SELECT * FROM `users` ORDER BY `id` ASC');
        while($usrs0 = $usr0->fetch_assoc()){
            $friends = $mysqli->query("SELECT * FROM `users_friend` WHERE (`user_id` = '".$usrs0['id']."' OR `friend_id` = '".$usrs0['id']."') AND `status` = '1'")->num_rows;
            if($friends != 0){
                $fri = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_from` = '".$usrs0['id']."' AND `active` = '0'")->num_rows;
                $d = ceil($friends/100*30)-$fri;
                $mysqli->query('UPDATE `users` SET `gift_limit` = "'.$d.'" WHERE `id` = '.$usrs0['id']);
            }
        }

        $usr = $mysqli->query('SELECT * FROM `users` WHERE `status` = "battle" ORDER BY `id` ASC');
        while($usrs = $usr->fetch_assoc()){
            $mysqli->query('UPDATE `users` SET `status` = "free" WHERE `id` = '.$usrs['id']);
        }

        $usr1 = $mysqli->query('SELECT * FROM `users` WHERE `limit_pok` > "0" ORDER BY `id` ASC');
        while($usrs1 = $usr1->fetch_assoc()){
            $mysqli->query('UPDATE `users` SET `limit_pok` = "0" WHERE `id` = '.$usrs1['id']);
        }

        $time = time();
        $baf = $mysqli->query('SELECT * FROM `bafs` WHERE `time` < "'.$time.'" ORDER BY `id` ASC');
        while($bafs = $baf->fetch_assoc()){
            $mysqli->query('DELETE FROM bafs WHERE id = '.$bafs['id']);
        }

        $usr2 = $mysqli->query('SELECT * FROM `users` WHERE `mission_day` = 1 ORDER BY `id` ASC');
        while($usrs2 = $usr2->fetch_assoc()){
            $arc = array();
            $i = 0;
            while(true){
                $r = rand(1,28);
                if(!in_array($r, $arc)){
                    $i++;
                    $arc[] = $r;
                }else{
                    continue;
                }
                if($i == 4) break;
            }
            $b = 0;
            while($b < 4){
                if($arc[$b] == 1){ $tb = 20;}
                elseif($arc[$b] == 5){ $tb = 200;}
                elseif($arc[$b] == 6){ $tb = 30000;}
                elseif($arc[$b] == 7){ $tb = 5;}
                elseif($arc[$b] == 12){ $tb = 10;}
                elseif($arc[$b] == 13){ $tb = 8;}
                elseif($arc[$b] == 14){ $tb = 1500;}
                elseif($arc[$b] == 15){ $tb = 5;}
                elseif($arc[$b] == 17){ $tb = 25;}
                elseif($arc[$b] == 18){ $tb = 15;}
                elseif($arc[$b] == 20){ $tb = 30;}
                elseif($arc[$b] == 21){ $tb = 5;}
                elseif($arc[$b] == 26){ $tb = 15;}
                elseif($arc[$b] == 27){ $tb = 15;}
                elseif($arc[$b] == 28){ $tb = 10;}
                else{$tb = 1;}

                $rand = rand(1,165);
                if($rand >= 1 and $rand < 25){ $it = 1; $count = 30000;}
                elseif($rand >= 25 and $rand < 30){ $it = 25; $count = 2;}
                elseif($rand >= 30 and $rand < 45){ $it = 183; $count = 1;}
                elseif($rand >= 45 and $rand < 55){ $it = 195; $count = 1;}
                elseif($rand >= 55 and $rand < 65){ $it = 62; $count = 2;}
                elseif($rand >= 65 and $rand < 75){ $it = 29; $count = 5;}
                elseif($rand >= 75 and $rand < 83){ $it = 33; $count = 1;}
                elseif($rand >= 83 and $rand < 100){ $it = rand(125,141); $count = 1;}
                elseif($rand >= 100 and $rand < 108){ $it = 197; $count = 1;}
                elseif($rand >= 108 and $rand < 128){ $it = 245; $count = 2;}
                elseif($rand >= 128 and $rand < 135){ $it = 198; $count = 1;}
                elseif($rand >= 135 and $rand < 155){ $it = 240; $count = 4;}
                elseif($rand >= 155 and $rand <= 165){ $it = rand(481,492); $count = 1;}
                else{ $it = 1; $count = 30000;}
                $mysqli->query("INSERT INTO `user_mission_day` (`user`,`id_mission`,`end_process`,`present_id`,`present_count`) VALUES ('".$usrs2['id']."','".$arc[$b]."','".$tb."','".$it."','".$count."') ");
                $b++;
            }
        }
        echo "Done2";
    }
    if($_GET['ksfhajk'] == 3){
        $t = time();
$eggQuery = $mysqli->query("SELECT * FROM `user_egg` WHERE `reborn` < '".$t."' ");

	while($egg = $eggQuery->fetch_assoc()){

    $atk_egg = false;
	$pok = $egg['basenum'];
	$user_new = $egg['user'];
	$lvl=1;
	$gen=$egg['gens'];
	$startGame=0;
	$trade = $egg['trade'];
	$sparka = $egg['sparka'];
	$event=false;
	$shine=$egg['shine'];
	$character=$egg['character'];
	$form=$egg['form'];
	$ev=false;
	$eggThis=false;
  if($trade == 'false'){
    $trade = 'false';
  }else{
    $trade = 'true';
  }
	$pok_base = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` = '".$pok."'")->fetch_assoc();

    $users =  $mysqli->query("SELECT `user_group`,`login` FROM `users` WHERE `id`='".$user_new."'")->fetch_assoc();
	$month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
	$dateGet = '{"user_id":"'.$user_new.'","date": "'.time().'"}';
	#Яйцевая атака
	$eggAttacks = $mysqli->query("SELECT `attacks` FROM `base_attacks_pokemons` WHERE `pok` = '".$pok."' AND `type` = 'sex'")->fetch_assoc();
	$arrayAttacks = explode(',',$eggAttacks['attacks']);
	if(achiv_utility(12,$user_new)){
								        $util = rand(1,90);
								    }else{
								        $util = rand(1,100);
								    }
	if($util < 35){
		$numberAttack = mt_rand(0, count($arrayAttacks) - 1);
		if($eggThis == false){
			if(!empty($arrayAttacks[$numberAttack])){
				$attacksList = $arrayAttacks[$numberAttack].',0,0,0';
				$atk_egg = $arrayAttacks[$numberAttack];
			}else{
				$attacksList = '0,0,0,0';
			}
		}else{
			$attacksList = '0,0,0,0';
		}
	}else{
		$attacksList = '0,0,0,0';
	}
	if($shine == 1){
			$shine = "shine";
}else{
  $shine = "normal";
}
  if($pok_base['sex_m'] == 0 && $pok_base['sex_f'] == 0) {
    $gender = 'Бесполый';
  }else{
    $gender = ($pok_base['sex_f'] > 0 ? ( $pok_base['sex_f'] >= mt_rand(0, 100) ? 'Девочка' : 'Мальчик' ) : 'Мальчик');
  }
	$har = $mysqli->query("SELECT * FROM `har` WHERE `id_har` = '".$hr."' ")->fetch_assoc();
	$hg = rand(10,26);
	$ag = rand(10,26);
	$dg = rand(10,26);
	$sg = rand(10,26);
	$sag = rand(10,26);
	$sdg = rand(10,26);
	if($gen){
	    $gens = $gen.','.$gen.','.$gen.','.$gen.','.$gen.','.$gen;
	}else{
	    $gens = $hg.','.$ag.','.$dg.','.$sg.','.$sag.','.$sdg;
	}

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

	$Ability = $mysqli->query('SELECT * FROM base_ability_pokemon WHERE id = '.$pok)->fetch_assoc();
      $Abil1 = ($Ability['slot1'] != "0" ? $Ability['slot1'] : 0);
      $Abil2 = ($Ability['slot2'] != "0" ? $Ability['slot2'] : 0);
      $AbilArray = [$Abil1,$Abil2];
      if($AbilArray[0] != "0" && $AbilArray[1] == "0") {
        $AbilityEnd = $AbilArray[0];
        $Slot = 1;
      }
      if($AbilArray[0] != "0" && $AbilArray[1] != "0") {
        $randAbil = mt_rand(1,2);
        if($randAbil == 1) {
          $AbilityEnd = $AbilArray[0];
          $Slot = 1;
        }else{
          $AbilityEnd = $AbilArray[1];
          $Slot = 2;
        }
      }
      if($AbilArray[0] == "0" && $AbilArray[1] != "0") {
        $AbilityEnd = $AbilArray[1];
        $Slot = 2;
      }
      if($AbilArray[0] == "0" && $AbilArray[1] == "0") {
        $AbilityEnd = 0;
        $Slot = 0;
      }
	$abil_id = $AbilityEnd;
	$abil_slot = $Slot;
	$mysqli->query("INSERT INTO `user_pokemons` (`user_id`,`basenum`,`name_new`,`ability`,`ability_slot`,`character`,`lvl`,`birthday`,`active`,`type`,`gender`,`exp`,`exp_max`,`ev`,`hp`,`stats`,`gen`,`owner`,`master`,`startGame`,`sparka`,`attacks`,`sparkaNumber`,`trade`,`event`,`form`) VALUES ('".$user_new."','".$pok_base['id']."','".$pok_base['name_rus']."','".$abil_id."','".$abil_slot."','".$character."','".$lvl."','".$dateGet."','0','".$shine."','".$gender."','".$expirience."','".$expirienceMax."','".$ev."','".$s1."','".$stats."','".$gens."','".$user_new."','".$user_new."','".$startGame."','".$sparka."','".$attacksList."','".$sparkNumber."','".$trade."','".$event."','".$form."') ");
	$id_pok_new = $mysqli->insert_id;
	if($atk_egg){
	    $mysqli->query("INSERT INTO `user_pokemons_tm` (`pok`,`attacks`) VALUES ('".$id_pok_new."','".$atk_egg."') ");
	}
	if($eggThis == false){
		$month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
	$dayToday = date("d");
	$monthToday = $month[date("n")];
	$YearToday = date("Y");
	$date = $dayToday.' '.$monthToday.' '.$YearToday.'г. в '.date("H").':'.date("i");
	$text = 'С пополнением! Вылупилось яйцо <b>'.$pok_base['name_rus'].'</b>. Проверьте свой питомник.';
	$mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$text."','".$user_new."','/img/world/items/little/151.png','".$date."')");
    update_achiv(12,1,$user_new);
	}


		$mysqli->query('DELETE FROM `user_egg` WHERE `id` = '.$egg['id'].' AND `reborn` < '.$t);
	}
	echo "Done3";
    }
    
    
    if($_GET['ksfhajk'] == 4){ 
 
 $mysqli->query('UPDATE `system` SET `closed` = "0" WHERE `id` = "1"'); 
 echo "Done4";
 
 }
    
if($_GET['ksfhajk'] == 5){
    
    
    
    
    
    
    
    
    
   
    
    
    $server = $mysqli->query('SELECT * FROM `system` WHERE `id` = 1')->fetch_assoc();
    if($server['week'] == 1 or $server['week'] == 2 or $server['week'] == 3){
        if(date(H) == 3 or date(H) == 6 or date(H) == 9 or date(H) == 12 or date(H) == 15 or date(H) == 18 or date(H) == 21 or date(H) == 0){
            $asdf = $mysqli->query('SELECT * FROM `a_ivent_week_mission` ');
            while($asdf_us = $asdf->fetch_assoc()){
                if($server['week'] != 3){
                    $mysqli->query("UPDATE `a_ivent_week_mission` SET `breeding`= '5',`fight`= '100',`catch`= '15',`creater`= '10',`walk`= '10',`evolution`= '5' WHERE `id`='".$asdf_us['id']."'");
                }else{
                    $mysqli->query("UPDATE `a_ivent_week_mission` SET `breeding`= '5',`fight`= '20',`catch`= '15',`creater`= '10',`walk`= '10',`evolution`= '5' WHERE `id`='".$asdf_us['id']."'");
                }
            }
        }
    }
    
	echo "Done5";
}

/* ==================== ksfhajk = 7 — закрытие просроченных лотов по dateEnd ==================== */
if (isset($_GET['ksfhajk']) && (string)$_GET['ksfhajk'] === '7') {
    if (!headers_sent()) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(200); }
    ignore_user_abort(true);
    set_time_limit(120);

    // лёгкий lock, чтобы не запустить параллельно
    $lockFile = sys_get_temp_dir().'/lombard_close.lock';
    $fp = @fopen($lockFile, 'c+');
    if ($fp && !@flock($fp, LOCK_EX | LOCK_NB)) { echo "Done7(lock)\n"; exit; }

    // утилиты
    $ruMonth = [1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря'];
    $nowText = function() use ($ruMonth){ return date("d").' '.$ruMonth[date("n")].' '.date("Y").'г. в '.date("H").':'.date("i"); };
    $notify  = function(mysqli $db, int $userId, string $text, string $img='/img/world/items/little/1.png') use ($nowText){
        $db->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$db->real_escape_string($text)."', {$userId}, '".$db->real_escape_string($img)."', '".$db->real_escape_string($nowText())."')");
    };

    $closed = 0;
    $now = time();

    // Берём пачку просроченных. Можно поднять лимит при необходимости.
    $r = $mysqli->query("SELECT `id` FROM `log_lombard` WHERE `dateEnd` <= {$now} ORDER BY `dateEnd` ASC LIMIT 400");
    if ($r) while ($row = $r->fetch_assoc()) {
        $lotId = (int)$row['id'];

        $mysqli->begin_transaction();
        try {
            // Лочим и читаем свежие данные
            $lot = $mysqli->query("SELECT * FROM `log_lombard` WHERE `id` = {$lotId} FOR UPDATE")->fetch_assoc();
            if (!$lot) { $mysqli->commit(); continue; }

            // Если вдруг время сдвинули — не закрываем
            if ((int)$lot['dateEnd'] > time()) { $mysqli->commit(); continue; }

            $winnerId = (int)$lot['userBuy'];                 // 0 — никто не сделал ставку
            $sellerId = (int)$lot['userID'];
            $name     = $lot['name'];
            $priceNow = (int)$lot['priceNow'];
            $prodId   = (int)$lot['productID'];
            $strItem  = trim((string)$lot['strItem']);
            $category = trim((string)$lot['category']);

            if ($winnerId > 0) {
                // Победитель есть — отдаём лот победителю и деньги продавцу
                if ($category === 'pokemon') {
                    $mysqli->query("UPDATE `user_pokemons` SET `user_id`={$winnerId}, `active`=0 WHERE `id`={$prodId}");
                } else {
                    if ($strItem !== '') {
                        $mysqli->query("INSERT INTO `items_users` (`item_id`,`count`,`user`,`str`) VALUES ({$prodId}, 1, {$winnerId}, '".$mysqli->real_escape_string($strItem)."')");
                    } else {
                        itemAdd($prodId, (int)$lot['count'], $winnerId);
                    }
                }
                // Деньги продавцу (победитель уже внёс ставку в момент ставки)
                itemAdd(1, $priceNow, $sellerId);

                // Уведомления
                $notify($mysqli, $winnerId, 'Вы успешно выиграли аукцион на лот '.$name.'!');
                $notify($mysqli, $sellerId, 'Ваш лот '.$name.' был успешно куплен за '.number_format($priceNow,0,'.','.').' м.');

                // Финальная запись в историю ставок
                $d = date("Y-m-d H:i:s");
                $mysqli->query("INSERT INTO `log_lombard_bidcounter` (`lotID`,`user`,`money`,`date`,`vikup`,`polz`)
                                VALUES ({$lotId}, {$winnerId}, '{$priceNow}', '{$d}', '".$mysqli->real_escape_string($name)."', {$sellerId})");

            } else {
                // Победителя нет — возвращаем лот продавцу
                if ($category === 'pokemon') {
                    $mysqli->query("UPDATE `user_pokemons` SET `user_id`={$sellerId}, `active`=0 WHERE `id`={$prodId}");
                } else {
                    if ($strItem !== '') {
                        $mysqli->query("INSERT INTO `items_users` (`item_id`,`count`,`user`,`str`) VALUES ({$prodId}, 1, {$sellerId}, '".$mysqli->real_escape_string($strItem)."')");
                    } else {
                        itemAdd($prodId, (int)$lot['count'], $sellerId);
                    }
                }
                $notify($mysqli, $sellerId, 'Ваш лот '.$name.' был возвращён вам!');
            }

            // Удаляем лот из аукциона
            $mysqli->query("DELETE FROM `log_lombard` WHERE `id` = {$lotId}");
            $mysqli->commit();
            $closed++;

        } catch (Throwable $e) {
            $mysqli->rollback();
            // продолжаем следующие лоты
        }
    }

    echo "Done7({$closed})\n";

    if ($fp) { @flock($fp, LOCK_UN); @fclose($fp); }
    exit;
}


    if ($_GET['ksfhajk'] == 8) {

    // делаем ответ «успешным» для cron (а логи — в истории)
    if (!headers_sent()) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(200); }
    ignore_user_abort(true);

    $now = time();

    // БЕЗОПАСНО: если запрос не выполнился — выходим корректно
    $base = $mysqli->query('SELECT * FROM `user_web_slot` WHERE `pok` = 0 ');
    if (!$base) { echo "Done8\n"; return; }

    while ($bd = $base->fetch_assoc()) {

        // --- учёт таймера: не чаще 1 раза в час на СЛОТ ---
        $placed = isset($bd['placed_at']) ? (int)$bd['placed_at'] : 0;

        // если приманка только что поставлена — инициализируем таймер и пропускаем
        if ($placed <= 0) {
            $mysqli->query("UPDATE `user_web_slot` SET `placed_at` = {$now} WHERE `id`=".(int)$bd['id']);
            continue;
        }
        // ещё час не прошёл — пропускаем слот
        if (($now - $placed) < 3600) {
            continue;
        }
        // ---------------------------------------------------

        $r = rand(1,100);
        if ($r <= 8) {

            $pok   = 0;
            $text2 = '';

            // 51 и 10 — это один «Лесной оазис»
            if ($bd['location'] == 51 || $bd['location'] == 10) {
                $rand = rand(1,100);
                if     ($rand >= 1  && $rand <= 20) { $pok = 401; } // Крикетот - 20%
                elseif ($rand > 20  && $rand <= 25) { $pok = 123; } // Сайтер - 5%
                elseif ($rand > 25  && $rand <= 43) { $pok = 48;  } // Венонат shine - 18%
                elseif ($rand > 43  && $rand <= 58) { $pok = 204; } // Пинеко - 15%
                elseif ($rand > 58  && $rand <= 65) { $pok = 742; } // Кьютифлай - 7%
                else                                { $pok = 595; } // Джолтик - 5%
                $text2 = 'В вашу приманку попал покемон на локации <b>Лесной оазис</b>';

            } elseif ($bd['location'] == 62) {
                $rand = rand(1,100);
                if     ($rand >= 1  && $rand <= 35) { $pok = 86;  }
                elseif ($rand > 35  && $rand <= 65) { $pok = 120; }
                elseif ($rand > 65  && $rand <= 73) { $pok = 211; }
                elseif ($rand > 73  && $rand <= 75) { $pok = 349; }
                elseif ($rand > 75  && $rand <= 95) { $pok = 363; }
                else                                { $pok = 594; }
                $text2 = 'В вашу приманку попал покемон на локации <b>Пляж</b>';

            } elseif ($bd['location'] == 68) {
                $rand = rand(1,100);
                if     ($rand >= 1  && $rand <= 2)  { $pok = 207; }
                elseif ($rand > 2   && $rand <= 37) { $pok = 304; }
                elseif ($rand > 37  && $rand <= 67) { $pok = 557; }
                elseif ($rand > 67  && $rand <= 72) { $pok = 843; }
                elseif ($rand > 72  && $rand <= 80) { $pok = 874; }
                else                                { $pok = 878; }
                $text2 = 'В вашу приманку попал покемон на локации <b>Блестящая пещера</b>';
            }

            if ($pok > 0) {
                // ставим покемона + фиксируем момент проверки
                $mysqli->query("UPDATE `user_web_slot` 
                                SET `pok` = ".$pok.", `placed_at` = {$now} 
                                WHERE `id` = ".(int)$bd['id']);

                // история — успешный спавн/поимка
                $mysqli->query("INSERT INTO `user_web_history`
                                (`user`,`location`,`slot`,`pok`,`caught`,`action`,`time`)
                                VALUES (".(int)$bd['user'].", ".(int)$bd['location'].", ".(int)$bd['slot'].", ".$pok.", 1, 'spawn', ".$now.")");

                // ачивки / уведомление — как было
                update_achiv(38,1,$bd['user']);
                if (achiv_utility(38,$bd['user'])) {
                    $month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
                    $date = date("d").' '.$month[date("n")].' '.date("Y").'г. в '.date("H").':'.date("i");
                    $mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) 
                                    VALUES ('".$text2."', ".(int)$bd['user'].", '/img/world/items/little/187.png', '".$mysqli->real_escape_string($date)."')");
                }

            } else {
                // на всякий случай только сдвигаем таймер следующей проверки
                $mysqli->query("UPDATE `user_web_slot` SET `placed_at` = {$now} WHERE `id` = ".(int)$bd['id']);
            }

        } else {
            // приманка не сработала — проверим этот слот снова через ~час
            $mysqli->query("UPDATE `user_web_slot` SET `placed_at` = {$now} WHERE `id` = ".(int)$bd['id']);
        }
    }

    echo "Done8\n";
}
}


if ($_GET['ksfhajk'] == 9) {
    // Время 2 дня назад в формате UNIX timestamp
    $two_days_ago = time() - 2 * 24 * 60 * 60;

    // Удаление старых записей
    $mysqli->query("DELETE FROM `chat_new` WHERE `lifetime` < $two_days_ago");

    echo "Старые сообщения удалены";
}

?>
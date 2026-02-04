<?php
/**
 * НПС: Кассир — поезд Канто ⇄ Джотто
 * Станции: 27 (Канто) ⇄ 70 (Джотто)
 * Поезд (локация): 8010
 * Рейсы (МСК): 02:00, 08:00, 14:00, 20:00 (в пути 2 часа)
 * Билеты: предметы #602 (27→70) и #603 (70→27), срок годности 3 дня
 * Цена: 150000 × предмет #1 (генкар)
 * user_id = 4 — админ, может садиться в поезд в любое время (мгновенный рейс для проверки)
 */

if (!defined('TIMEZONE_SET_TRAIN')) {
    // ВРЕМЯ МСК
    date_default_timezone_set('Europe/Moscow');
    define('TIMEZONE_SET_TRAIN', true);
}
$npc = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id` = ".(int)$npcId)->fetch_assoc();
$response['name'] = 'Кассир';
$response['image'] = isset($npc['image']) ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    $response['question'] = 'Ошибка авторизации. Пожалуйста, войдите в игру.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['id'];

/* ---------------- Константы ---------------- */
$REGION_A_NAME     = 'Канто';  // id 27
$REGION_B_NAME     = 'Джотто'; // id 70

$STATION_A         = 27;    // Канто
$STATION_B         = 70;    // Джотто
$TRAIN_LOCATION_ID = 8010;

$HOURS       = array(2, 8, 14, 20);  // 4 рейса/сутки (включая ночь)
$TRAVEL_SEC  = 2 * 3600;             // 2 часа в пути
$BOARD_OPEN  = 10 * 60;              // окно посадки: -10 минут
$BOARD_CLOSE = 5  * 60;              // +5 минут

$TICKET_A2B  = 602;                  // 27 → 70
$TICKET_B2A  = 603;                  // 70 → 27
$TICKET_TTL  = 3 * 86400;            // 3 дня

$CURRENCY_ITEM_ID = 1;               // генкар
$TICKET_PRICE     = 150000;          // стоимость билета

$ADMIN_BYPASS_ID  = 4;               // админ: можно садиться всегда

$now = time();

/* Путь к мини-иконкам предметов */
$IMG_ITEM = function($id){
    return "/img/items/mini/{$id}.png";
};

/* ---------------- SQL утилиты ---------------- */
function q(mysqli $db, $sql){ return $db->query($sql); }
function esc(mysqli $db, $v){ return "'".$db->real_escape_string($v)."'"; }

/* ---------------- Таблицы службы ---------------- */
function ensure_tables(mysqli $db){
    // Активные поездки
    q($db, "CREATE TABLE IF NOT EXISTS `train_travel`(
       `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       `user` INT UNSIGNED NOT NULL,
       `from_loc` INT NOT NULL,
       `to_loc` INT NOT NULL,
       `depart_at` INT UNSIGNED NOT NULL,
       `arrive_at` INT UNSIGNED NOT NULL,
       `active` TINYINT(1) NOT NULL DEFAULT 1,
       KEY `user_idx`(`user`), KEY `active_idx`(`active`), KEY `depart_idx`(`depart_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Кошелёк билетов с TTL
    q($db, "CREATE TABLE IF NOT EXISTS `train_ticket_user`(
       `user` INT UNSIGNED NOT NULL,
       `item_id` INT UNSIGNED NOT NULL,
       `amount` INT UNSIGNED NOT NULL DEFAULT 0,
       `expires_at` INT UNSIGNED NOT NULL,
       PRIMARY KEY(`user`,`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Регистрация предметов билетов 602/603 (поля под вашу схему base_items) */
function register_items_602_603(mysqli $db){
    @q($db, "
    INSERT INTO `base_items`
    (`id`,`cool`,`name`,`skinName`,`type`,`sex`,`about`,`categories`,`weight`,
     `give`,`dress`,`drop`,`trade`,`use`,`lombard`,`battle`,`info`,`str`,
     `news`,`tm_id`,`nameEng`,`descriptionEng`,`expiration`,`drop_it`,`rait_it`,
     `craft`,`skin_id`,`skin_color`,`skin_form`)
    VALUES
    (602,0,'Билет: 27→70','', 'other', NULL,
     'Проездной билет. Нельзя использовать/надевать. Срок годности 3 дня.',
     0,0,'false','false','false','false','false','false',0,NULL,'0',0,0,
     'Ticket: 27→70','Travel ticket. Not usable/wearable. Valid 3 days.',0,'<span>Не частая награда</span>','normal','',NULL,NULL,NULL),
    (603,0,'Билет: 70→27','', 'other', NULL,
     'Проездной билет. Нельзя использовать/надевать. Срок годности 3 дня.',
     0,0,'false','false','false','false','false','false',0,NULL,'0',0,0,
     'Ticket: 70→27','Travel ticket. Not usable/wearable. Valid 3 days.',0,'<span>Не частая награда</span>','normal','',NULL,NULL,NULL)
    ON DUPLICATE KEY UPDATE
      `name`=VALUES(`name`),`about`=VALUES(`about`),`type`=VALUES(`type`),
      `use`=VALUES(`use`),`dress`=VALUES(`dress`),`give`=VALUES(`give`),
      `trade`=VALUES(`trade`),`drop`=VALUES(`drop`),`lombard`=VALUES(`lombard`),
      `nameEng`=VALUES(`nameEng`),`descriptionEng`=VALUES(`descriptionEng`);
    ");
}

/* ---------------- Инвентарь предметов (обнаружение таблицы) ---------------- */
/** Возвращает [table, user_col, item_col, count_col] или null. */
function detect_inventory_table(mysqli $db){
    $candidates = array('items_users','user_items','users_items','aa_user_items','inventory','inv','items','bag');
    $tables = array();
    $r = q($db,"SHOW TABLES");
    while($row = $r->fetch_row()){ $tables[] = $row[0]; }

    $names = array(
        'user'  => array('user','user_id','uid','owner','owner_id'),
        'item'  => array('item','item_id','iid','thing','object_id'),
        'count' => array('count','kol','amount','cnt','num','value','qty','quantity')
    );

    foreach($candidates as $t){
        if (!in_array($t,$tables,true)) continue;
        $cols = array();
        $rc = q($db,"SHOW COLUMNS FROM `{$t}`");
        while($c = $rc->fetch_assoc()){ $cols[] = $c['Field']; }
        $uc = $ic = $cc = null;
        foreach($names['user'] as $x){ if (in_array($x,$cols,true)){ $uc = $x; break; } }
        foreach($names['item'] as $x){ if (in_array($x,$cols,true)){ $ic = $x; break; } }
        foreach($names['count'] as $x){ if (in_array($x,$cols,true)){ $cc = $x; break; } }
        if ($uc && $ic && $cc) return array($t,$uc,$ic,$cc);
    }
    return null;
}

$INV = detect_inventory_table($mysqli);

/** Кол-во предмета у игрока */
function inv_get(mysqli $db, $INV, $uid, $itemId){
    if (!$INV) return 0;
    list($t,$uc,$ic,$cc) = $INV;
    $r = q($db,"SELECT SUM(`{$cc}`) AS c FROM `{$t}` WHERE `{$uc}`={$uid} AND `{$ic}`={$itemId}");
    $row = $r->fetch_assoc(); return (int)$row['c'];
}
/** Изменить кол-во предмета (amount может быть отрицательным) */
function inv_add(mysqli $db, $INV, $uid, $itemId, $amount){
    if (!$INV) return false;
    list($t,$uc,$ic,$cc) = $INV;
    $r = q($db,"SELECT `{$cc}` FROM `{$t}` WHERE `{$uc}`={$uid} AND `{$ic}`={$itemId} LIMIT 1");
    if ($row = $r->fetch_assoc()){
        $new = max(0, (int)$row[$cc] + $amount);
        q($db,"UPDATE `{$t}` SET `{$cc}`={$new} WHERE `{$uc}`={$uid} AND `{$ic}`={$itemId} LIMIT 1");
        return true;
    } else {
        if ($amount < 0) return false;
        q($db,"INSERT INTO `{$t}` (`{$uc}`,`{$ic}`,`{$cc}`) VALUES ({$uid},{$itemId},{$amount})");
        return true;
    }
}

/* ---------------- Тикеты с TTL ---------------- */
function ticket_user_get(mysqli $db, $uid, $itemId){
    return q($db,"SELECT * FROM `train_ticket_user` WHERE `user`={$uid} AND `item_id`={$itemId}")->fetch_assoc();
}
function ticket_user_add(mysqli $db, $uid, $itemId, $ttlSec){
    $exp = time()+$ttlSec;
    q($db,"INSERT INTO `train_ticket_user`(`user`,`item_id`,`amount`,`expires_at`)
           VALUES ({$uid},{$itemId},1,{$exp})
           ON DUPLICATE KEY UPDATE
             `amount`=`amount`+1,
             `expires_at`=GREATEST(`expires_at`,VALUES(`expires_at`))");
}
function ticket_user_has_valid(mysqli $db, $uid, $itemId){
    $r = ticket_user_get($db,$uid,$itemId);
    return $r && (int)$r['amount']>0 && (int)$r['expires_at']>time();
}
function ticket_user_consume(mysqli $db, $uid, $itemId){
    $r = ticket_user_get($db,$uid,$itemId);
    if (!$r || (int)$r['amount']<=0 || (int)$r['expires_at']<=time()) return false;
    q($db,"UPDATE `train_ticket_user` SET `amount`=`amount`-1 WHERE `user`={$uid} AND `item_id`={$itemId} AND `amount`>0 LIMIT 1");
    return true;
}

/* ---------------- Поездки и расписание ---------------- */
function me(mysqli $db, $uid){ return q($db,"SELECT `location` FROM `users` WHERE `id`={$uid}")->fetch_assoc(); }
function set_location(mysqli $db, $uid, $loc){ q($db,"UPDATE `users` SET `location`={$loc} WHERE `id`={$uid} LIMIT 1"); }

function active_trip(mysqli $db, $uid){
    return q($db,"SELECT * FROM `train_travel` WHERE `user`={$uid} AND `active`=1 ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
}
function finish_trip(mysqli $db, $tripId){ q($db,"UPDATE `train_travel` SET `active`=0 WHERE `id`={$tripId} LIMIT 1"); }

function schedule_two_days($hours, $travelSec, $now){
    $out = array();
    $d0 = strtotime('today 00:00', $now);
    $d1 = strtotime('tomorrow 00:00', $now);
    foreach (array($d0,$d1) as $d){
        foreach ($hours as $h){
            $dep = $d + $h*3600;
            $out[] = array(
                'depart' => $dep,
                'arrive' => $dep + $travelSec,
                'label'  => date('H:i', $dep),
                'day'    => date('d.m', $dep)
            );
        }
    }
    usort($out, function($a,$b){ return ($a['depart']<$b['depart']) ? -1 : (($a['depart']==$b['depart'])?0:1); });
    return $out;
}
function left_h($sec){
    if ($sec<=0) return '0 мин';
    $h = floor($sec/3600); $m = floor(($sec%3600)/60);
    return $h>0 ? ($h.' ч '.($m>0?$m.' мин':'')) : ($m.' мин');
}
function resolve_direction($loc, $A, $B){
    if ($loc===$A) return array('from'=>$A,'to'=>$B,'ticket'=>602,'fromName'=>'Канто','toName'=>'Джотто');
    if ($loc===$B) return array('from'=>$B,'to'=>$A,'ticket'=>603,'fromName'=>'Джотто','toName'=>'Канто');
    return array('from'=>$A,'to'=>$B,'ticket'=>602,'fromName'=>'Канто','toName'=>'Джотто');
}

/* ---------------- Инициализация ---------------- */
ensure_tables($mysqli);
register_items_602_603($mysqli);

$me   = me($mysqli,$userId);
$loc  = isset($me['location']) ? (int)$me['location'] : 0;

/* Если поездка завершилась — довозим до станции назначения */
if ($trip = active_trip($mysqli,$userId)) {
    if ((int)$trip['arrive_at'] <= $now) {
        finish_trip($mysqli,(int)$trip['id']);
        set_location($mysqli,$userId,(int)$trip['to_loc']);
        $response['question']    = 'Поезд прибыл на станцию назначения.';
        $response['answer']      = array( 0 => 'Спасибо' );
        $response['closeDialog'] = true;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* ---------------- Роутинг ---------------- */
switch ($npcStep) {

    /* 0. Приветствие (по кнопке смотрим расписание) */
    case 0:
    default: {
        if ($trip) {
            $left = max(0,(int)$trip['arrive_at']-$now);
            $response['question'] = 'Здравствуйте! Вы уже в пути. Прибытие в <b>'.date('H:i',(int)$trip['arrive_at']).'</b> (МСК). Осталось: '.left_h($left).'. Чем ещё помочь?';
            $response['answer']   = array( 2=>'Перейти в вагон', 4=>'Расписание (МСК)', 10=>'Купить билет', 1=>'Сесть в ближайший рейс', 0=>'Закрыть' );
        } else {
            $response['question'] = 'Здравствуйте, тренер! Касса направления <b>Канто ⇄ Джотто</b>. Время и рейсы — по <b>МСК</b>. Что пожелаете?';
            $response['answer']   = array( 4=>'Посмотреть расписание', 10=>'Купить билет', 1=>'Сесть в ближайший рейс', 0=>'Закрыть' );
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    /* 1. Сесть в ближайший рейс (учёт окна, билетов; админ — всегда можно) */
    case 1: {
        if ($trip = active_trip($mysqli,$userId)){
            $left=max(0,(int)$trip['arrive_at']-$now);
            $response['question']='Вы уже в пути. Прибытие: <b>'.date('H:i',(int)$trip['arrive_at']).'</b> (МСК). Осталось: '.left_h($left).'.';
            $response['answer']  = array( 2=>'Перейти в вагон', 0=>'Закрыть' );
            $response['nav']     = array( 'route'=> array( array('type'=>'location','id'=>$TRAIN_LOCATION_ID) ) );
            $response['closeDialog']=true;
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }

        if ($loc!==$STATION_A && $loc!==$STATION_B){
            $response['question']='Посадка доступна только на станциях #'.$STATION_A.' (Канто) и #'.$STATION_B.' (Джотто).';
            $response['answer']  = array( 4=>'Расписание', 0=>'Понятно' );
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }

        $dir = resolve_direction($loc,$STATION_A,$STATION_B);
        $needTicket = $dir['ticket'];

        $sched = schedule_two_days($HOURS,$TRAVEL_SEC,$now);
        $slot = null;

        // Админ: всегда можно — делаем мгновенный рейс (сейчас → +2 часа)
        if ($userId === $ADMIN_BYPASS_ID){
            $slot = array('depart'=>$now, 'arrive'=>$now+$TRAVEL_SEC, 'label'=>date('H:i',$now), 'day'=>date('d.m',$now));
        } else {
            // обычное «окно» посадки
            for ($i=0;$i<count($sched);$i++){
                $s = $sched[$i];
                $open  = $s['depart'] - $BOARD_OPEN;
                $close = $s['depart'] + $BOARD_CLOSE;
                if ($now >= $open && $now <= $close){ $slot = $s; break; }
                if ($s['depart'] > $now+$BOARD_CLOSE) break;
            }
            if (!$slot){
                // ближайший будущий
                for ($i=0;$i<count($sched);$i++){ if ($sched[$i]['depart']>$now){ $slot=$sched[$i]; break; } }
                if ($slot){
                    $response['question']='Посадка откроется к ближайшему рейсу <b>'.$slot['label'].'</b> (через '.left_h($slot['depart']-$now).', МСК). Билет можно купить заранее.';
                    $response['answer']  = array( 10=>'Купить билет', 4=>'Расписание', 0=>'Закрыть' );
                } else {
                    $response['question']='Сегодня рейсы завершены. Приходите завтра.';
                    $response['answer']  = array( 4=>'Расписание', 0=>'Закрыть' );
                }
                echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
            }
        }

        // Требуются: действующий TTL и предмет-билет
        $hasTTL  = ticket_user_has_valid($mysqli,$userId,$needTicket);
        $hasItem = $INV ? inv_get($mysqli,$INV,$userId,$needTicket) > 0 : false;

        if (!$hasTTL || !$hasItem){
            $response['question'] =
                'Для посадки нужен действующий билет (предмет <img src="'.$GLOBALS['IMG_ITEM']($needTicket).'" width="18" height="18" style="vertical-align:-3px;border-radius:4px"> #'.$needTicket.'). '.
                'Купите билет у кассира.';
            $response['answer']  = array( 10=>'Купить билет', 4=>'Расписание', 0=>'Закрыть' );
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }

        // Списание билета (TTL + предмет)
        if (!ticket_user_consume($mysqli,$userId,$needTicket) || !inv_add($mysqli,$INV,$userId,$needTicket,-1)){
            $response['question']='Ошибка списания билета. Попробуйте ещё раз.';
            $response['answer']  = array( 0=>'Ок' );
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }

        // Регистрируем поездку и сажаем в вагон
        $from=$dir['from']; $to=$dir['to'];
        $departAt=(int)$slot['depart']; $arriveAt=(int)$slot['arrive'];
        q($mysqli,"INSERT INTO `train_travel`(`user`,`from_loc`,`to_loc`,`depart_at`,`arrive_at`,`active`)
                   VALUES ({$userId},{$from},{$to},{$departAt},{$arriveAt},1)");
        set_location($mysqli,$userId,$TRAIN_LOCATION_ID);

        $response['question']='Вы сели в поезд. Отправление: <b>'.date('H:i',$departAt).'</b>, прибытие: <b>'.date('H:i',$arriveAt).'</b> (МСК). В пути ~2 часа.';
        $response['answer']  = array( 2=>'Перейти в вагон', 0=>'Спасибо' );
        $response['nav']     = array( 'route'=> array( array('type'=>'location','id'=>$TRAIN_LOCATION_ID) ) );
        $response['closeDialog']=true;
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    /* 2. Перейти в вагон */
    case 2: {
        if ($trip = active_trip($mysqli,$userId)){
            if ($loc!==$TRAIN_LOCATION_ID) set_location($mysqli,$userId,$TRAIN_LOCATION_ID);
            $left=max(0,(int)$trip['arrive_at']-$now);
            $response['question']='Вы в вагоне (локация '.$TRAIN_LOCATION_ID.'). Осталось: '.left_h($left).'.';
            $response['answer']  = array( 0=>'Ок' );
            $response['closeDialog']=true;
        } else {
            $response['question']='У вас нет активной поездки.';
            $response['answer']  = array( 4=>'Расписание', 0=>'Закрыть' );
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    /* 4. Расписание (по кнопке) */
    case 4: {
        if ($trip = active_trip($mysqli,$userId)){
            $left=max(0,(int)$trip['arrive_at']-$now);
            $response['question']='Вы уже в пути. Прибытие: <b>'.date('H:i',(int)$trip['arrive_at']).'</b> (МСК). Осталось: '.left_h($left).'.';
            $response['answer']  = array( 2=>'Перейти в вагон', 0=>'Закрыть' );
            $response['closeDialog']=true;
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }

        $sched = schedule_two_days($HOURS,$TRAVEL_SEC,$now);
        $lines = array();
        for ($i=0;$i<count($sched);$i++){
            $s = $sched[$i];
            $open=$s['depart']-$BOARD_OPEN; $close=$s['depart']+$BOARD_CLOSE;
            $status = ($now < $open) ? ('через '.left_h($s['depart']-$now))
                    : (($now <= $close) ? 'посадка идёт' : 'рейс отправлен');
            $lines[] = $s['day'].' '.$s['label'].' — '.$status;
        }
        $textSchedule = implode('<br>',$lines);

        ob_start(); ?>
        <style>
          .trainBox{border:1px solid #e7e9f2;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(17,24,39,.04);padding:14px}
          .trainTitle{display:flex;align-items:center;gap:10px;margin-bottom:8px;font-weight:900;font-size:18px;color:#6D4AFF}
          .trainTitle .ico{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:#f4f3ff;border:1px solid rgba(109,74,255,.22)}
          .trainGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
          .trainCard{border:1px solid #e7e9f2;border-radius:10px;padding:10px;background:#fffef9}
          .trainCard .hh{font-size:16px;font-weight:800}
          .trainCard .sub{font-size:12px;color:#6b7280}
          .trainCard .status{margin-top:6px;font-size:12px}
          .trainCard .status .badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#f4f3ff;border:1px solid rgba(109,74,255,.22);color:#6D4AFF;font-weight:700}
          .trainActions{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap}
          .bpBtn{border:none;border-radius:10px;padding:10px 14px;cursor:pointer;background:#6D4AFF;color:#fff;font-weight:800}
          .bpBtn.info{background:#0ea5e9}
          @media(max-width:860px){.trainGrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        </style>
        <div class="trainBox">
          <div class="trainTitle"><div class="ico"><i class="fa fa-train"></i></div>Расписание (МСК) — <?= $REGION_A_NAME ?> ⇄ <?= $REGION_B_NAME ?>, в пути 2 ч</div>
          <div id="trainGrid" class="trainGrid">
            <?php foreach ($sched as $s):
              $open=$s['depart']-$BOARD_OPEN; $close=$s['depart']+$BOARD_CLOSE;
              $isBoard=($now>=$open && $now<=$close); $isFuture=($s['depart']>$now); ?>
              <div class="trainCard" data-depart="<?= (int)$s['depart'] ?>">
                <div class="hh"><?= htmlspecialchars($s['label']) ?></div>
                <div class="sub"><?= htmlspecialchars($s['day']) ?></div>
                <div class="status">
                  <?php if ($isBoard): ?><span class="badge">Посадка идёт</span>
                  <?php elseif ($isFuture): ?><span class="badge">Через <span class="left" data-left="<?= (int)($s['depart']-$now) ?>"></span></span>
                  <?php else: ?><span class="badge" style="opacity:.6">Отправлен</span><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="trainActions">
            <button class="bpBtn" onclick="typeof npc_navigate==='function'?npc_navigate({step:1}):0">Сесть в ближайший рейс</button>
            <button class="bpBtn info" onclick="typeof npc_navigate==='function'?npc_navigate({step:10}):0">Купить билет</button>
          </div>
        </div>
        <script>
        (function(){
          var nowServer = <?= (int)$now ?>*1000, grid=document.getElementById('trainGrid');
          function tick(){
            nowServer += 1000;
            var cards = grid ? grid.querySelectorAll('.trainCard') : [];
            for (var i=0;i<cards.length;i++){
              var card = cards[i];
              var depart = parseInt(card.getAttribute('data-depart'),10)*1000;
              var leftEl = card.querySelector('.left');
              if(!leftEl) continue;
              var left = Math.max(0, Math.floor((depart - nowServer)/1000));
              var h = Math.floor(left/3600), m = Math.floor((left%3600)/60);
              leftEl.textContent = (h>0?(h+' ч '):'') + m + ' мин';
            }
          }
          setInterval(tick,1000);
        })();
        </script>
        <?php
        $html = ob_get_clean();

        $response['question'] = 'Расписание (сегодня/завтра, МСК):<br>'.$textSchedule;
        $response['html']     = $html;
        $response['answer']   = array( 1=>'Сесть в ближайший рейс', 10=>'Купить билет', 0=>'Назад' );
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    /* 10. Меню покупки билетов (современный вывод с иконками) */
    case 10: {
        $bal = $INV ? inv_get($mysqli,$INV,$userId,$CURRENCY_ITEM_ID) : 0;
        $haveA = ticket_user_get($mysqli,$userId,$TICKET_A2B);
        $haveB = ticket_user_get($mysqli,$userId,$TICKET_B2A);

        $warnA = (ticket_user_has_valid($mysqli,$userId,$TICKET_A2B) || ($INV && inv_get($mysqli,$INV,$userId,$TICKET_A2B)>0));
        $warnB = (ticket_user_has_valid($mysqli,$userId,$TICKET_B2A) || ($INV && inv_get($mysqli,$INV,$userId,$TICKET_B2A)>0));

        ob_start(); ?>
        <style>
          .shopBox{border:1px solid #e7e9f2;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(17,24,39,.04);padding:14px}
          .shopHeader{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
          .bal{display:flex;align-items:center;gap:8px;font-weight:700}
          .bal img{width:20px;height:20px;border-radius:4px}
          .shopGrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
          .card{border:1px solid #e7e9f2;border-radius:10px;padding:12px;background:#ffffff;display:flex;gap:12px}
          .card .icon img{width:44px;height:44px;border-radius:8px;border:1px solid #e7e9f2;background:#f8fafc}
          .card .t{font-weight:900;font-size:16px;margin-bottom:6px}
          .card .d{font-size:12px;color:#6b7280}
          .card .exp{font-size:12px;color:#334155;margin-top:6px}
          .card .price{margin-top:10px;font-weight:800}
          .card .price img{width:18px;height:18px;vertical-align:-3px;border-radius:4px}
          .card .act{margin-top:8px}
          .bpBtn{border:none;border-radius:10px;padding:10px 14px;cursor:pointer;background:#6D4AFF;color:#fff;font-weight:800}
          .bpBtn[disabled]{opacity:.5;cursor:not-allowed}
          @media(max-width:860px){.shopGrid{grid-template-columns:1fr}}
        </style>
        <div class="shopBox">
          <div class="shopHeader">
            <div style="font-weight:900">Покупка билетов (МСК)</div>
            <div class="bal">
              <img src="<?= $IMG_ITEM($CURRENCY_ITEM_ID) ?>" alt="#">
              Баланс: <?= number_format($bal,0,'',' ') ?> × #<?= $CURRENCY_ITEM_ID ?> (генкар)
            </div>
          </div>
          <div class="shopGrid">
            <div class="card">
              <div class="icon"><img src="<?= $IMG_ITEM($TICKET_A2B) ?>" alt="ticket"></div>
              <div class="body">
                <div class="t">Билет <?= $REGION_A_NAME ?> → <?= $REGION_B_NAME ?></div>
                <div class="d">Срок годности — 3 дня. Нужен для посадки на станции #<?= $STATION_A ?>.</div>
                <div class="exp">
                  <?php if ($haveA && (int)$haveA['amount']>0 && (int)$haveA['expires_at']>$now): ?>
                    У вас: <?= (int)$haveA['amount'] ?> шт, до <?= date('d.m H:i',(int)$haveA['expires_at']) ?> (МСК).
                  <?php else: ?>
                    У вас билетов нет.
                  <?php endif; ?>
                </div>
                <div class="price">Цена: <?= number_format($TICKET_PRICE,0,'',' ') ?> × <img src="<?= $IMG_ITEM($CURRENCY_ITEM_ID) ?>" alt="#"> #<?= $CURRENCY_ITEM_ID ?></div>
                <div class="act">
                  <button class="bpBtn" onclick="typeof npc_navigate==='function'?npc_navigate({step:11}):0">
                    Купить <?= $REGION_A_NAME ?> → <?= $REGION_B_NAME ?><?php if ($warnA): ?> (у вас уже есть)<?php endif; ?>
                  </button>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="icon"><img src="<?= $IMG_ITEM($TICKET_B2A) ?>" alt="ticket"></div>
              <div class="body">
                <div class="t">Билет <?= $REGION_B_NAME ?> → <?= $REGION_A_NAME ?></div>
                <div class="d">Срок годности — 3 дня. Нужен для посадки на станции #<?= $STATION_B ?>.</div>
                <div class="exp">
                  <?php if ($haveB && (int)$haveB['amount']>0 && (int)$haveB['expires_at']>$now): ?>
                    У вас: <?= (int)$haveB['amount'] ?> шт, до <?= date('d.m H:i',(int)$haveB['expires_at']) ?> (МСК).
                  <?php else: ?>
                    У вас билетов нет.
                  <?php endif; ?>
                </div>
                <div class="price">Цена: <?= number_format($TICKET_PRICE,0,'',' ') ?> × <img src="<?= $IMG_ITEM($CURRENCY_ITEM_ID) ?>" alt="#"> #<?= $CURRENCY_ITEM_ID ?></div>
                <div class="act">
                  <button class="bpBtn" onclick="typeof npc_navigate==='function'?npc_navigate({step:12}):0">
                    Купить <?= $REGION_B_NAME ?> → <?= $REGION_A_NAME ?><?php if ($warnB): ?> (у вас уже есть)<?php endif; ?>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
            <button class="bpBtn" onclick="typeof npc_navigate==='function'?npc_navigate({step:1}):0">Сесть в ближайший рейс</button>
            <button class="bpBtn" style="background:#0ea5e9" onclick="typeof npc_navigate==='function'?npc_navigate({step:4}):0">Расписание</button>
          </div>
        </div>
        <?php
        $html = ob_get_clean();

        $response['question'] = 'Выберите направление. Стоимость: <img src="'.$IMG_ITEM($CURRENCY_ITEM_ID).'" width="18" height="18" style="vertical-align:-3px;border-radius:4px"> '.number_format($TICKET_PRICE,0,'',' ').' × #'.$CURRENCY_ITEM_ID.' (генкар).';
        $response['html']     = $html;
        $response['answer']   = array( 11=>'Купить 27→70', 12=>'Купить 70→27', 4=>'Расписание', 0=>'Назад' );
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    /* 11. Купить 27→70 (+ подтверждение при наличии билета) */
    case 11: {
        $hasValid = ticket_user_has_valid($mysqli,$userId,$TICKET_A2B) || ($INV && inv_get($mysqli,$INV,$userId,$TICKET_A2B)>0);
        if ($hasValid && (!isset($_GET['confirm']) || $_GET['confirm']!=='1') && (!isset($_POST['confirm']) || $_POST['confirm']!=='1')) {
            $response['question'] = 'У вас уже есть действующий билет 27→70. Купить ещё один?';
            $response['answer']   = array( 111=>'Купить всё равно', 10=>'Назад' );
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
        if (!$INV){
            $response['question']='Инвентарь предметов не найден. Покупка невозможна.';
            $response['answer']  = array( 0=>'Ок' ); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
        $bal = inv_get($mysqli,$INV,$userId,$CURRENCY_ITEM_ID);
        if ($bal < $TICKET_PRICE){
            $response['question']='Недостаточно <img src="'.$IMG_ITEM($CURRENCY_ITEM_ID).'" width="18" height="18" style="vertical-align:-3px;border-radius:4px"> #'.$CURRENCY_ITEM_ID.'. Нужно: '.number_format($TICKET_PRICE,0,'',' ').', у вас: '.number_format($bal,0,'',' ').'.';
            $response['answer']  = array( 0=>'Понятно', 10=>'Назад' ); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
        inv_add($mysqli,$INV,$userId,$CURRENCY_ITEM_ID,-$TICKET_PRICE); // списали генкары
        inv_add($mysqli,$INV,$userId,$TICKET_A2B,1);                    // выдали предмет билета
        ticket_user_add($mysqli,$userId,$TICKET_A2B,$TICKET_TTL);       // записали TTL
        $info = ticket_user_get($mysqli,$userId,$TICKET_A2B);
        $response['question']='Билет 27→70 <img src="'.$IMG_ITEM($TICKET_A2B).'" width="18" height="18" style="vertical-align:-3px;border-radius:4px"> выдан. Действует до '.date('d.m H:i',(int)$info['expires_at']).' (МСК).';
        $response['answer']  = array( 1=>'Сесть в ближайший рейс', 10=>'Купить ещё', 4=>'Расписание', 0=>'Готово' );
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }
    case 111: {
        $_GET['confirm']='1'; $npcStep=11;
        // повтор — как в 11-м шаге:
        if (!$INV){ $response['question']='Инвентарь предметов не найден.'; $response['answer']=array(0=>'Ок'); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit; }
        $bal = inv_get($mysqli,$INV,$userId,$CURRENCY_ITEM_ID);
        if ($bal < $TICKET_PRICE){ $response['question']='Недостаточно средств.'; $response['answer']=array(0=>'Ок'); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit; }
        inv_add($mysqli,$INV,$userId,$CURRENCY_ITEM_ID,-$TICKET_PRICE);
        inv_add($mysqli,$INV,$userId,$TICKET_A2B,1);
        ticket_user_add($mysqli,$userId,$TICKET_A2B,$TICKET_TTL);
        $info = ticket_user_get($mysqli,$userId,$TICKET_A2B);
        $response['question']='Билет 27→70 выдан. Действует до '.date('d.m H:i',(int)$info['expires_at']).' (МСК).';
        $response['answer']  = array( 1=>'Сесть в ближайший рейс', 10=>'Купить ещё', 4=>'Расписание', 0=>'Готово' );
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    /* 12. Купить 70→27 (+ подтверждение при наличии билета) */
    case 12: {
        $hasValid = ticket_user_has_valid($mysqli,$userId,$TICKET_B2A) || ($INV && inv_get($mysqli,$INV,$userId,$TICKET_B2A)>0);
        if ($hasValid && (!isset($_GET['confirm']) || $_GET['confirm']!=='1') && (!isset($_POST['confirm']) || $_POST['confirm']!=='1')) {
            $response['question'] = 'У вас уже есть действующий билет 70→27. Купить ещё один?';
            $response['answer']   = array( 121=>'Купить всё равно', 10=>'Назад' );
            echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
        if (!$INV){
            $response['question']='Инвентарь предметов не найден. Покупка невозможна.';
            $response['answer']  = array( 0=>'Ок' ); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
        $bal = inv_get($mysqli,$INV,$userId,$CURRENCY_ITEM_ID);
        if ($bal < $TICKET_PRICE){
            $response['question']='Недостаточно <img src="'.$IMG_ITEM($CURRENCY_ITEM_ID).'" width="18" height="18" style="vertical-align:-3px;border-radius:4px"> #'.$CURRENCY_ITEM_ID.'. Нужно: '.number_format($TICKET_PRICE,0,'',' ').', у вас: '.number_format($bal,0,'',' ').'.';
            $response['answer']  = array( 0=>'Понятно', 10=>'Назад' ); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
        }
        inv_add($mysqli,$INV,$userId,$CURRENCY_ITEM_ID,-$TICKET_PRICE);
        inv_add($mysqli,$INV,$userId,$TICKET_B2A,1);
        ticket_user_add($mysqli,$userId,$TICKET_B2A,$TICKET_TTL);
        $info = ticket_user_get($mysqli,$userId,$TICKET_B2A);
        $response['question']='Билет 70→27 <img src="'.$IMG_ITEM($TICKET_B2A).'" width="18" height="18" style="vertical-align:-3px;border-radius:4px"> выдан. Действует до '.date('d.m H:i',(int)$info['expires_at']).' (МСК).';
        $response['answer']  = array( 1=>'Сесть в ближайший рейс', 10=>'Купить ещё', 4=>'Расписание', 0=>'Готово' );
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }
    case 121: {
        $_GET['confirm']='1'; $npcStep=12;
        if (!$INV){ $response['question']='Инвентарь предметов не найден.'; $response['answer']=array(0=>'Ок'); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit; }
        $bal = inv_get($mysqli,$INV,$userId,$CURRENCY_ITEM_ID);
        if ($bal < $TICKET_PRICE){ $response['question']='Недостаточно средств.'; $response['answer']=array(0=>'Ок'); echo json_encode($response, JSON_UNESCAPED_UNICODE); exit; }
        inv_add($mysqli,$INV,$userId,$CURRENCY_ITEM_ID,-$TICKET_PRICE);
        inv_add($mysqli,$INV,$userId,$TICKET_B2A,1);
        ticket_user_add($mysqli,$userId,$TICKET_B2A,$TICKET_TTL);
        $info = ticket_user_get($mysqli,$userId,$TICKET_B2A);
        $response['question']='Билет 70→27 выдан. Действует до '.date('d.m H:i',(int)$info['expires_at']).' (МСК).';
        $response['answer']  = array( 1=>'Сесть в ближайший рейс', 10=>'Купить ещё', 4=>'Расписание', 0=>'Готово' );
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }
}

<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global))(!file_exists($patch_global) ?  die('The problem with the connection files.') : require_once($patch_global));
$uid = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
$patch_avatars = $patch_project.'/img/avatars/mini/'.$uid.'.png';
if(!empty($uid) && file_exists($patch_avatars)){
	$avatarMini = 'img/avatars/mini/'.$uid.'.png';
}else{
	$avatarMini = "no-user-img";
}
$bundles_file = $patch_project.'/do/bundles.php';
if (file_exists($bundles_file)) {
    require_once($bundles_file);
}
$type = isset($_POST["type"]) ? escapeMe($_POST["type"]) : '';
$tab = isset($_POST["tab"]) ? (int)$_POST["tab"] : 1;
$u = $uid ? $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$uid)->fetch_assoc() : null;
switch ($type) {
case 'web_offline':

    // Заголовок
    $tpl .= '<div class="Title">
                <div class="Name">Ловушка</div>
                <div class="Info">Расставляйте приманки, чтобы приманивать покемонов и ловить их в сеть.</div>
                <div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
             </div>';

    // Данные пользователя
    $us = $mysqli->query("SELECT `web_lot`,`location` FROM `users` WHERE `id`=".(int)$_SESSION['id']." LIMIT 1")->fetch_assoc();

    // Разрешённые локации (поддержим старую 51 как эквивалент 10)
    $allowed = [10,51,62,68];
    $loc     = (int)$us['location'];
    if (!in_array($loc, $allowed, true)) {
        $tpl .= '<div class="WebModal"><div class="WebInfo"><div class="WebInfoBlock">
                    <h2>Недоступно</h2>
                    <div class="WebInfoPrint">Ловушки работают только на специальных локациях.</div>
                 </div></div></div>';
        $response['html'] = $tpl;
        break;
    }
    $locKey = ($loc === 51 ? 10 : $loc);

    // Количество приманок
    $baitCount = number_format(item_isset_count(187), 0, '.', '.');

    // Загружаем слоты разом
    $slots = [];
    $resSlots = $mysqli->query("SELECT * FROM `user_web_slot` WHERE `location`={$loc} AND `user`=".(int)$_SESSION['id']);
    if ($resSlots) {
        while ($row = $resSlots->fetch_assoc()) {
            $slots[(int)$row['slot']] = $row;
        }
    }

    // История (если есть таблица user_web_history/user_web_log)
    $history = [];
    $histTable = null;
    if (($r1 = $mysqli->query("SHOW TABLES LIKE 'user_web_history'")) && $r1->num_rows) $histTable = 'user_web_history';
    if (!$histTable && ($r2 = $mysqli->query("SHOW TABLES LIKE 'user_web_log'")) && $r2->num_rows) $histTable = 'user_web_log';

    if ($histTable) {
        $qHist = "
            SELECT h.`pok` AS pid,
                   h.`caught` AS caught,
                   h.`time`   AS ts,
                   IFNULL(bp.`name_rus`, CONCAT('#', LPAD(h.`pok`,3,'0'))) AS nm
            FROM `{$histTable}` h
            LEFT JOIN `base_pokemons` bp ON bp.`id` = h.`pok`
            WHERE h.`user`=".(int)$_SESSION['id']." AND h.`location`={$loc}
            ORDER BY IFNULL(h.`time`,0) DESC, h.`id` DESC
            LIMIT 12";
        if ($rh = $mysqli->query($qHist)) {
            while ($r = $rh->fetch_assoc()) {
                $history[] = [
                    'id'     => (int)$r['pid'],
                    'name'   => $r['nm'],
                    'when'   => ($r['ts'] ? date('d.m H:i', (int)$r['ts']) : ''),
                    'caught' => ((int)$r['caught'] ? 1 : 0),
                ];
            }
        }
    }

    // ======= Стили (компактно) =======
    $tpl .= '<style>
      .TrapUI{max-width:940px;margin:8px auto 14px;padding:0 10px}
      .TrapUI .top{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:6px 0 8px}
      .TrapUI .pill{display:inline-flex;align-items:center;gap:6px;background:#f6f8fc;border:1px solid #e7eef7;color:#29405f;
                    padding:6px 10px;border-radius:10px;font-weight:700;font-size:12px}
      .TrapUI .pill img{width:16px;height:16px}
      .TrapUI .grid{display:grid;grid-template-columns:1fr 320px;gap:12px}
      @media(max-width:900px){.TrapUI .grid{grid-template-columns:1fr}}
      .TrapUI .box{background:#fff;border:1px solid #ecf1f7;border-radius:12px}
      .TrapUI .box.pad{padding:10px}
      .TrapUI .slots{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
      @media(max-width:560px){.TrapUI .slots{grid-template-columns:repeat(3,1fr)}}
      .TrapUI .slot{height:72px;border:1px dashed #d8e4f1;border-radius:10px;background:#fbfdff;
                    display:flex;align-items:center;justify-content:center;position:relative;cursor:pointer}
      .TrapUI .slot img{max-width:56px;max-height:56px;image-rendering:pixelated}
      .TrapUI .slot .hint{position:absolute;left:6px;bottom:6px;font-size:10px;color:#6d7f98;background:#f2f6fc;border:1px solid #e6edf6;
                          border-radius:8px;padding:1px 6px}
      .TrapUI .eta{position:absolute;top:6px;right:6px;font-size:10px;color:#0f55b5;background:#e9f2ff;border:1px solid #cfe4ff;
                   border-radius:8px;padding:1px 6px}
      .TrapUI .slot.lock{background:#f7f9fc;border-style:solid;border-color:#e6edf6}
      .TrapUI .slot.lock i{color:#95a6bf;font-size:20px}
      .TrapUI h3{font-size:14px;margin:0 0 8px 0;color:#243b55}
      .TrapUI .legend{display:flex;gap:10px;font-size:11px;color:#6a7c95;margin-bottom:6px}
      .TrapUI .legend .dot{width:8px;height:8px;border-radius:50%}
      .TrapUI .list{display:grid;gap:6px;max-height:410px;overflow:auto;padding-right:4px}
      .TrapUI .row{display:flex;align-items:center;gap:8px;border:1px solid #eef3f8;border-radius:10px;background:#fcfdff;padding:6px}
      .TrapUI .row img{width:32px;height:32px;image-rendering:pixelated}
      .TrapUI .nm{font-weight:700;color:#243b55;font-size:13px}
      .TrapUI .pc{margin-left:auto;font-weight:800;padding:2px 8px;border-radius:999px;border:1px solid #e6edf6;font-size:12px}
      .TrapUI .pc.g{background:#eafff3;color:#189a58;border-color:#c8f2de}
      .TrapUI .pc.o{background:#fff4e6;color:#c77300;border-color:#ffe3bf}
      .TrapUI .pc.r{background:#ffecec;color:#c33;border-color:#ffd1d1}
      .TrapUI .how{margin-top:10px}
      .TrapUI .how p{margin:0 0 6px;color:#4a617e;line-height:1.4;font-size:13px}
      .TrapUI .note{color:#cf3a3a}
      .TrapUI .tabs{display:flex;gap:6px;margin-bottom:8px}
      .TrapUI .tabbtn{flex:1 1 0;border:1px solid #e7eef7;background:#f6f8fc;color:#29405f;border-radius:8px;padding:6px 8px;font-weight:700;
                      font-size:12px;cursor:pointer}
      .TrapUI .tabbtn.active{background:#e9f2ff;border-color:#cfe4ff;color:#0f55b5}
      .TrapUI .pane{display:none}
      .TrapUI .pane.active{display:block}
      .TrapUI .histrow{display:flex;align-items:center;gap:8px;border:1px solid #eef3f8;background:#fcfdff;padding:6px;border-radius:10px}
      .TrapUI .histrow img{width:28px;height:28px;image-rendering:pixelated}
      .TrapUI .when{margin-left:auto;color:#6d7f98;font-size:12px}
      .TrapUI .ok{color:#189a58;font-weight:700}
      .TrapUI .fail{color:#c33;font-weight:700}
    </style>';

    // Набор покемонов по локации
    if ($locKey === 10) {
        $pokeList = [
            ['id'=>401,'name'=>'Крикетот'                 ,'pc'=>'20%','class'=>'g'],
            ['id'=>123,'name'=>'Сайтер'                   ,'pc'=>'5%' ,'class'=>'r'],
            ['id'=> 48,'name'=>'Венонат <span class="shine">shine</span>','pc'=>'18%','class'=>'g'],
            ['id'=>204,'name'=>'Пинеко'                   ,'pc'=>'15%','class'=>'o'],
            ['id'=>742,'name'=>'Кьютифлай'                ,'pc'=>'7%' ,'class'=>'o'],
            ['id'=>595,'name'=>'Джолтик'                  ,'pc'=>'5%' ,'class'=>'r'],
        ];
    } elseif ($locKey === 62) {
        $pokeList = [
            ['id'=> 86,'name'=>'Сил'       ,'pc'=>'35%','class'=>'g'],
            ['id'=>120,'name'=>'Старью'    ,'pc'=>'30%','class'=>'g'],
            ['id'=>211,'name'=>'Квилфиш'   ,'pc'=>'8%' ,'class'=>'o'],
            ['id'=>349,'name'=>'Фибас'     ,'pc'=>'2%' ,'class'=>'r'],
            ['id'=>363,'name'=>'Сфил'      ,'pc'=>'20%','class'=>'o'],
            ['id'=>594,'name'=>'Аломомола' ,'pc'=>'5%' ,'class'=>'r'],
        ];
    } else { // 68
        $pokeList = [
            ['id'=>207,'name'=>'Глайгер'    ,'pc'=>'2%' ,'class'=>'r'],
            ['id'=>304,'name'=>'Арон'       ,'pc'=>'35%','class'=>'g'],
            ['id'=>557,'name'=>'Двибл'      ,'pc'=>'30%','class'=>'g'],
            ['id'=>843,'name'=>'Силикобра'  ,'pc'=>'5%' ,'class'=>'r'],
            ['id'=>874,'name'=>'Стонжорнер' ,'pc'=>'8%' ,'class'=>'o'],
            ['id'=>878,'name'=>'Куфант'     ,'pc'=>'20%','class'=>'o'],
        ];
    }

    // Вспомогалка ETA в секундах для слота (от часа с момента установки, либо до ближайшего верха часа)
    $now = time();
    $globalMinEta = null;
    $fmt_eta = function(int $sec): string { $sec=max(0,$sec); $m=intdiv($sec,60); $s=$sec%60; return sprintf('%02d:%02d',$m,$s); };
    $calc_eta = function(array $row) use ($now) {
        // приоритет полей timestamp
        $ts = 0;
        foreach (['time','placed_at','updated_at','created_at'] as $k) {
            if (isset($row[$k]) && (int)$row[$k] > 0) { $ts = (int)$row[$k]; break; }
        }
        if ($ts > 0) {
            $elapsed = ($now - $ts) % 3600;
            $eta = 3600 - $elapsed;
            if ($eta === 3600) $eta = 0;
            return $eta;
        }
        // фолбэк: до ближайшего верха часа
        $eta = 3600 - ((int)date('i')*60 + (int)date('s'));
        if ($eta === 3600) $eta = 0;
        return $eta;
    };

    // Разметка
    $tpl .= '<div class="WebModal TrapUI">
        <div class="top">
            <div class="pill">Локация '.$loc.'</div>
            <div class="pill"><img src="/img/world/items/little/187.png" alt=""> '.$baitCount.' шт.</div>
            <div class="pill">Слотов '.(int)$us['web_lot'].' / 6</div>
        </div>

        <div class="grid">
            <!-- Левая колонка: слоты + памятка -->
            <div>
                <div class="box pad">
                    <div class="slots">';

    // 6 слотов
    for ($i=1; $i<=6; $i++) {
        if ($i <= (int)$us['web_lot']) {
            if (empty($slots[$i])) {
                // пустая сеть
                $tpl .= '<div class="slot" onclick="issetAll('.$i.',\'web\')">
                            <i class="fal fa-spider-web" style="font-size:20px;color:#7b90aa"></i>
                            <span class="hint">свободно</span>
                         </div>';
            } else {
                $l = $slots[$i];
                if ((int)$l['pok'] === 0) {
                    // приманка стоит — покажем ETA
                    $eta = $calc_eta($l);
                    if ($globalMinEta === null || $eta < $globalMinEta) $globalMinEta = $eta;
                    $tpl .= '<div class="slot" onclick="issetAll('.$i.',\'web\')">
                                <img src="/img/world/items/little/187.png" alt="">
                                <span class="hint">приманка</span>
                                <span class="eta" data-left="'.$eta.'">'.$fmt_eta($eta).'</span>
                             </div>';
                } else {
                    // пойманный покемон — ETA не нужен
                    $tpl .= '<div class="slot" onclick="issetAll('.$i.',\'web\')">
                                <img src="/img/pokemons/animation/'.numbPok($l['pok']).'.png" alt="">
                                <span class="hint">пойман</span>
                             </div>';
                }
            }
        } else {
            // закрытый слот
            $tpl .= '<div class="slot lock" onclick="issetAll('.$i.',\'web\')">
                        <i class="fas fa-lock-alt"></i>
                        <span class="hint">закрыт</span>
                     </div>';
        }
    }

    // Памятка
    $tpl .=        '</div>
                </div>

                <div class="box pad how">
                    <h3>Как это работает</h3>
                    <p>Поставьте приманку в свободную ячейку с сетью. Каждые ~60 минут для этого слота происходит проверка.</p>
                    <p>Слот, установленный позже, проверяется по своему таймеру.</p>
                    <p>Список возможных покемонов зависит от локации.</p>
                    <p class="note">Нет гарантии поимки даже через 12 часов.</p>
                </div>
            </div>

            <!-- Правая колонка: табы Покемоны / История -->
            <div class="box pad">
                <div class="tabs">
                    <button class="tabbtn active" data-tab="pok">Покемоны</button>
                    <button class="tabbtn" data-tab="hist">История</button>
                </div>

                <!-- ПАНЕЛЬ: Покемоны -->
                <div class="pane active" id="pane-pok">
                    <h3>Покемоны</h3>
                    <div class="legend">
                        <span class="dot" style="background:#19a05a"></span> высокий
                        <span class="dot" style="background:#c77300"></span> средний
                        <span class="dot" style="background:#c33"></span> низкий
                    </div>
                    <div class="list">';

    foreach ($pokeList as $p) {
        $tpl .= '<div class="row">
                    <img src="/img/pokemons/animation/'.sprintf('%03d',(int)$p['id']).'.png" alt="">
                    <div class="nm">#'.sprintf('%03d',(int)$p['id']).' '.$p['name'].'</div>
                    <div class="pc '.$p['class'].'">'.$p['pc'].'</div>
                 </div>';
    }

    $tpl .=       '</div>
                </div>

                <!-- ПАНЕЛЬ: История -->
                <div class="pane" id="pane-hist">
                    <h3>История ловушки</h3>
                    <div class="list">';

    if (!empty($history)) {
        foreach ($history as $h) {
            $cls = $h['caught'] ? 'ok' : 'fail';
            $txt = $h['caught'] ? 'пойман' : 'ушёл';
            $img = $h['id'] > 0 ? '/img/pokemons/animation/'.sprintf("%03d",$h['id']).'.png' : '/img/world/items/little/187.png';
            $nm  = ($h['id'] > 0 ? $h['name'] : '—');
            $when = $h['when'];
            $tpl .= '<div class="histrow">
                        <img src="'.$img.'" alt="">
                        <div class="nm">'.$nm.' — <span class="'.$cls.'">'.$txt.'</span></div>
                        <div class="when">'.$when.'</div>
                     </div>';
        }
    } else {
        $tpl .= '<div class="histrow"><div class="nm">Записей пока нет.</div></div>';
    }

    $tpl .=        '</div>
                </div>
            </div> <!-- /right box -->
        </div> <!-- /grid -->
    </div> <!-- /TrapUI -->';

    // JS: табы + таймеры (пер-слот и общий)
    $globalLeft = ($globalMinEta === null ? -1 : (int)$globalMinEta);
    $tpl .= '
    <script>
      (function(){
        var wrap = document.querySelector(".TrapUI");
        if (!wrap) return;

        // Табы
        var tabs = wrap.querySelectorAll(".tabbtn");
        var pok  = wrap.querySelector("#pane-pok");
        var his  = wrap.querySelector("#pane-hist");
        tabs.forEach(function(b){
          b.addEventListener("click", function(){
            tabs.forEach(function(x){ x.classList.remove("active"); });
            b.classList.add("active");
            if (b.getAttribute("data-tab") === "hist"){
              his.classList.add("active"); pok.classList.remove("active");
            } else {
              pok.classList.add("active"); his.classList.remove("active");
            }
          });
        });

        // Пер-слот таймеры
        function fmt(n){ n = Math.max(0, n|0); var m=(n/60)|0, s=n%60; return (m<10?"0":"")+m+":"+(s<10?"0":"")+s; }
        function tick(){
          var minLeft = null;
          wrap.querySelectorAll(".eta").forEach(function(el){
            var left = parseInt(el.getAttribute("data-left")||"0",10);
            if (isNaN(left)) left = 0;
            if (left > 0) {
              left--;
              el.setAttribute("data-left", left);
              el.textContent = fmt(left);
            } else {
              el.textContent = "00:00";
            }
            if (minLeft === null || left < minLeft) minLeft = left;
          });
          // Общий ближайший
          var eg = document.getElementById("etaGlobal");
          if (eg) eg.textContent = fmt(minLeft == null ? 0 : minLeft);
        }

        // Инициализировать «общий» ближайший ETA из PHP
        var eg = document.getElementById("etaGlobal");
        if (eg) eg.textContent = fmt('.$globalLeft.');

        setInterval(tick, 1000);
      })();
    </script>';

    $response['html'] = $tpl;
break;



    case 'calendar_day':
        $tpl .= '<div class="Title"><div class="Name">Ежедневные призы</div><div class="Info">Заходите каждый день, отмечайте его в календаре и получайте приз. В начале месяца всем, кто отметил все дни - выдается главный приз.</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>
        <div class="ContentCalendarDay">';
        for($i=1;$i<=date("t");$i++){
            if(date("j") == $i){ $men = 'today clickable';}else{ $men = '';}
            if($i <= date("j")){
                $us_c = $mysqli->query('SELECT * FROM `users_calendar_day` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
                $day = explode(',',$us_c['day']);
                if(in_array($i,$day)){
                    $select = 'takeday';
                    if(date("j") == $i){ $men = ''; }else{ $men = ''; }
                }else{
                    if(date("j") == $i){ $men = 'today clickable'; $func= 'onclick="calendar_gift('.$i.');"'; $select = ''; }else{ $men = ''; $select = 'donttakeday';}
                    
                }
            }else{
                $select = '';
            }
            $tpl .= '<div class="block_day day'.$i.' '.$men.' '.$select.'" '.$func.'><div class="back">'.$i.'</div>';
            $bd_pr_id = $mysqli->query('SELECT * FROM `base_calendar_day` WHERE `id` = '.$i)->fetch_assoc();
            if($bd_pr_id['type'] == 1){
                $item = explode(',',$bd_pr_id['item']);
                $bd_it = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$item[0])->fetch_assoc();
                
                $tpl .= '<div class="front"><span><p>'.$bd_it['name'].'</p></span><div><img src="/img/world/items/little/'.$item[0].'.png"><div class="count">x'.number_format($item[1],0,'.','.').'</div></div></div>';
            }else{
                $bd_pok = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.$bd_pr_id['pok'].'" ')->fetch_assoc();
                $tpl .= '<div class="front"><span><p>Яйцо '.$bd_pok['name_rus'].'</p></span><div><img src="/img/world/items/little/151.png"></div></div>';
            }
            
            
            $tpl .= '</div>';
            
        }
        $us_gift = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_to` = '".$_SESSION['id']."' AND `active` = 0")->fetch_assoc();
		if($us_gift){
		    $ts = "shakeGift";
		    $ts2 = 'onclick="issetAll(1,\'GiftUsFr\')"';
		}else{
		    $ts = "";
		    $ts2 = "";
		}
        $tpl .= '<div class="block_day friends_gift"><div class="back"><div class="gift '.$ts.'" '.$ts2.'><i class="fa fa-gift"></i></div></div></div>';
        $tpl .= '</div>';
        $response['html'] = $tpl;
    break;
    case 'crafttm':
        $tpl .= '<div class="Title"><div class="Name">Разработка дисков</div><div class="Info">Программирование дисков, позволяющее создать ТМ/TR Атаки.</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>';
        if($u['lvl'] >= 20){
            $tpl .= ' <div class="listBlockProccess">';
        $us = $mysqli->query("SELECT `crafting_lot` FROM `users` WHERE `id` = '".$_SESSION['id']."' ")->fetch_assoc();
        for($i=1;$i<9;$i++){
            if($i <= $us['crafting_lot']){
                $l = $mysqli->query("SELECT * FROM `users_tm_create` WHERE `user` = '".$_SESSION['id']."' AND `slot` = '".$i."' ")->fetch_assoc();
                    if(empty($l)){
                        $tpl .= '<div class="blockproccess slot'.$i.' noclick"><i class="fas fa-plus"></i></div>';
                    }else{
                        $items = $mysqli->query("SELECT `tm_id`,`id` FROM `base_items` WHERE `info` = '".$l['atk']."' AND `id` > 1000 ")->fetch_assoc();
                        $t = 'TM';
                        $tm = $items['tm_id'];
                        if($items['id'] >= 2000) { $tm = $items['tm_id']-2000; $t = 'TR'; }
                        $proc = round(((time()-$l['time'])/($l['time_end']-$l['time']))*100);
                        if($proc >= 100) $proc = 100;
                        $tpl .= '<div class="blockproccess slot'.$i.'" onclick="issetAll('.$i.',\'crafting_slot\');" ><div class="tm_name">'.$t.$tm.'</div><div class="tm_proc">'.$proc.'%</div><img src="/img/world/items/little/1001.png"></div>';
                    }
            }else{
                $tpl .= '<div class="blockproccess" onclick="issetAll('.$i.',\'crafting_slot\');" ><i class="fas fa-lock-alt"></i></div>';
            }
        }            
        $tpl .= '            </div>
                    
                    
                    <div class="ListPokTM">';
        $us_pokemon = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1");
        while($pok = $us_pokemon->fetch_assoc()){
            $bd_pok = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.$pok['basenum'].'" ')->fetch_assoc();
            $tpl .= '<div class="PokTM" onclick=\'issetAll('.$pok['id'].',"attackpok")\'><div class="imgPOK"><img src="/img/pokemons/animation/'.numbPok($bd_pok['id']).'.png"></div><div class="namePOK">'.$bd_pok['name_rus'].'</div></div>';
        }
        
            $tpl .= '   </div><div class="ListTM">';
            
            $tm_attack_bd = $mysqli->query("SELECT * FROM `user_tm_crafting` WHERE `user` = '".$_SESSION['id']."' ORDER BY `info` DESC");
            while($tm = $tm_attack_bd->fetch_assoc()){
                $it = $mysqli->query("SELECT `info`,`tm_id`,`id`,`name`,`craft` FROM `base_items` WHERE `id` > 1000 AND `info` = '".$tm['tm']."' ")->fetch_assoc();//поиск итема по атаке
                $atk = $mysqli->query("SELECT `type`,`name_rus` FROM `base_atk` WHERE `id` = '".$it['info']."' ")->fetch_assoc();//поиск итема по атаке
                $info = explode(',',$it['craft']);
                if($tm['info'] >= 100){
                    $tpl .= '<div class="TMBlock">
                                <div class="imgTM"><img src="/img/world/typs/'.$atk['type'].'.png"></div>
                                <div class="infoTM">
                                    <div class="inTM">
                                        <div class="nameTM">'.$atk['name_rus'].'</div>
                                        <div class="progressTM"><button onclick="create_attack_tmcrafting('.$it['info'].');">Создать</button></div>
                                    </div>
                                    <div class="condTM">
                                        <div><i class="far fa-sack-dollar"></i><span>'.number_format($info[1],0,'.','.').'</span></div>
                                        <div><i class="far fa-clock"></i><span>'.$info[0].'дн.</span></div>
                                    </div>
                                </div>
                            </div>';
                }else{
                    $tpl .= '<div class="TMBlock noactive">
                                <div class="imgTM"><img src="/img/world/typs/'.$atk['type'].'.png"></div>
                                <div class="infoTM">
                                    <div class="inTM">
                                        <div class="nameTM">'.$atk['name_rus'].'</div>
                                        <div class="progressTM"><div class="progressbar"><div style="width:'.$tm['info'].'%;"></div></div></div>
                                    </div>
                                    <div class="condTM">
                                        <div><i class="far fa-sack-dollar"></i><span>'.number_format($info[1],0,'.','.').'</span></div>
                                        <div><i class="far fa-clock"></i><span>'.$info[0].'дн.</span></div>
                                    </div>
                                </div>
                            </div>';
                }
            }
                            
                            
            $tpl .= '            </div>';    
        }else{
					$tpl .= '<div class="block"><i class="far fa-lock-alt"></i><br><span>Разработка дисков доступна игрокам, достигшим <b>20</b> уровня.</span></div>';
				}
        $response['html'] = $tpl;
    break;
    case 'qqqqqq':
        $tpl .= '<div class="Title"><div class="Name">Разработка дисков</div><div class="Info">Программирование дисков, позволяющее создать ТМ/TR Атаки.</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>
            <div class="craft_tm">
                <h2>Выберите тип атаки для продолжения разработки</h2>
                <div class="card-tm">
                    <ul>
                        <li style="--i:9;--clr:#9099a1;" onclick="craft_tm_type(\'normal\');" class="active"><img src="/img/world/typs/normal.png"> Нормальный</li>
                        <li style="--i:8;--clr:#90aade;" onclick="craft_tm_type(\'fly\');"><img src="/img/world/typs/fly.png"> Летающий</li>
                        <li style="--i:7;--clr:#da7844;" onclick="craft_tm_type(\'ground\');" class="active"><img src="/img/world/typs/ground.png"> Земляной</li>
                        <li style="--i:6;--clr:#91c228;" onclick="craft_tm_type(\'bug\');"><img src="/img/world/typs/bug.png"> Насекомый</li>
                        <li style="--i:5;--clr:#ff9d53;" onclick="craft_tm_type(\'fire\');" class="active"><img src="/img/world/typs/fire.png"> Огненный</li>
                        <li style="--i:4;--clr:#63bc5b;" onclick="craft_tm_type(\'grass\');"><img src="/img/world/typs/grass.png"> Травяной</li>
                        <li style="--i:3;--clr:#f97176;" onclick="craft_tm_type(\'psychic\');" class="active"><img src="/img/world/typs/psychic.png"> Психический</li>
                        <li style="--i:2;--clr:#0a6dc4;" onclick="craft_tm_type(\'dragon\');"><img src="/img/world/typs/dragon.png"> Драконий</li>
                        <li style="--i:1;--clr:#5a8ea1;" onclick="craft_tm_type(\'steel\');" class="active"><img src="/img/world/typs/steel.png"> Стальной</li>
                    </ul>
                </div>
                <div class="card-tm">
                    <ul>
                        <li style="--i:18;--clr:#ce4069;" onclick="craft_tm_type(\'fighting\');" class="active"><img src="/img/world/typs/fighting.png"> Боевой</li>
                        <li style="--i:17;--clr:#ab6ac8;" onclick="craft_tm_type(\'poison\');"><img src="/img/world/typs/poison.png"> Ядовитый</li>
                        <li style="--i:16;--clr:#c7b78b;" onclick="craft_tm_type(\'rock\');"  class="active"><img src="/img/world/typs/rock.png"> Каменный</li>
                        <li style="--i:15;--clr:#5269ac;" onclick="craft_tm_type(\'ghost\');"><img src="/img/world/typs/ghost.png"> Призрачный</li>
                        <li style="--i:14;--clr:#4c91d6;" onclick="craft_tm_type(\'water\');"  class="active"><img src="/img/world/typs/water.png"> Водный</li>
                        <li style="--i:13;--clr:#f3d338;" onclick="craft_tm_type(\'electric\');"><img src="/img/world/typs/electric.png"> Электрический</li>
                        <li style="--i:12;--clr:#74cec0;" onclick="craft_tm_type(\'ice\');"  class="active"><img src="/img/world/typs/ice.png"> Ледяной</li>
                        <li style="--i:11;--clr:#5a5366;" onclick="craft_tm_type(\'dark\');"><img src="/img/world/typs/dark.png"> Темный</li>
                        <li style="--i:10;--clr:#ec8fe6;" onclick="craft_tm_type(\'fairy\');" class="active"><img src="/img/world/typs/fairy.png"> Волшебный</li>
                    </ul>
                </div>
                <div class="card-tm">
                    <ul>
                        <li style="--i:18;--clr:#9099a1;" onclick="craft_tm_type(\'normal\');"><img src="/img/world/typs/normal.png"> Нормальный</li>
                        <li style="--i:17;--clr:#90aade;" onclick="craft_tm_type(\'fly\');"><img src="/img/world/typs/fly.png"> Летающий</li>
                        <li style="--i:16;--clr:#da7844;" onclick="craft_tm_type(\'ground\');"><img src="/img/world/typs/ground.png"> Земляной</li>
                        <li style="--i:15;--clr:#91c228;" onclick="craft_tm_type(\'bug\');"><img src="/img/world/typs/bug.png"> Насекомый</li>
                        <li style="--i:14;--clr:#ff9d53;" onclick="craft_tm_type(\'fire\');"><img src="/img/world/typs/fire.png"> Огненный</li>
                        <li style="--i:13;--clr:#63bc5b;" onclick="craft_tm_type(\'grass\');"><img src="/img/world/typs/grass.png"> Травяной</li>
                        <li style="--i:12;--clr:#f97176;" onclick="craft_tm_type(\'psychic\');"><img src="/img/world/typs/psychic.png"> Психический</li>
                        <li style="--i:11;--clr:#0a6dc4;" onclick="craft_tm_type(\'dragon\');"><img src="/img/world/typs/dragon.png"> Драконий</li>
                        <li style="--i:10;--clr:#5a8ea1;" onclick="craft_tm_type(\'steel\');"><img src="/img/world/typs/steel.png"> Стальной</li>
                        <li style="--i:9;--clr:#ce4069;" onclick="craft_tm_type(\'fighting\');"><img src="/img/world/typs/fighting.png"> Боевой</li>
                        <li style="--i:8;--clr:#ab6ac8;" onclick="craft_tm_type(\'poison\');"><img src="/img/world/typs/poison.png"> Ядовитый</li>
                        <li style="--i:7;--clr:#c7b78b;" onclick="craft_tm_type(\'rock\');"><img src="/img/world/typs/rock.png"> Каменный</li>
                        <li style="--i:6;--clr:#5269ac;" onclick="craft_tm_type(\'ghost\');"><img src="/img/world/typs/ghost.png"> Призрачный</li>
                        <li style="--i:5;--clr:#4c91d6;" onclick="craft_tm_type(\'water\');"><img src="/img/world/typs/water.png"> Водный</li>
                        <li style="--i:4;--clr:#f3d338;" onclick="craft_tm_type(\'electric\');"><img src="/img/world/typs/electric.png"> Электрический</li>
                        <li style="--i:3;--clr:#74cec0;" onclick="craft_tm_type(\'ice\');"><img src="/img/world/typs/ice.png"> Ледяной</li>
                        <li style="--i:2;--clr:#5a5366;" onclick="craft_tm_type(\'dark\');"><img src="/img/world/typs/dark.png"> Темный</li>
                        <li style="--i:1;--clr:#ec8fe6;" onclick="craft_tm_type(\'fairy\');"><img src="/img/world/typs/fairy.png"> Волшебный</li>
                    </ul>
                </div>
            </div>
        ';
        $response['html'] = $tpl;
    break;
case "battlepass":
    // Проверка наличия активного сезона
    $season = $mysqli->query("SELECT * FROM `aa_battle_pass_season` WHERE `is_active` = 1 LIMIT 1")->fetch_assoc();
    if (!$season) {
        $response['error'] = "В данный момент активного сезона боевого пропуска нет.";
        break;
    }
    // Проверка авторизации пользователя
    if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
        $response['error'] = "Ошибка авторизации пользователя.";
        break;
    }

    $user_id = (int)$_SESSION['id'];
    $bd = $mysqli->query("SELECT * FROM `aa_battle_pass_user` WHERE `user` = '$user_id' AND `season_id` = '".$season['id']."'")->fetch_assoc();

    // --- Акцентные цвета из сезона (только оформление)
    $accent = !empty($season['accent']) ? (string)$season['accent'] : (!empty($season['color1']) ? (string)$season['color1'] : '#7d7cf8');
    $toRgba = function (string $hex, float $a) {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
        $r = hexdec(substr($h,0,2)); $g = hexdec(substr($h,2,2)); $b = hexdec(substr($h,4,2));
        return "rgba($r,$g,$b,$a)";
    };
    $accent_bg  = htmlspecialchars($toRgba($accent, 0.10), ENT_QUOTES, 'UTF-8'); // мягкий фон иконки
    $accent_bd  = htmlspecialchars($toRgba($accent, 0.22), ENT_QUOTES, 'UTF-8'); // рамка/деликатная тень
    $accent_txt = htmlspecialchars($accent, ENT_QUOTES, 'UTF-8');                // цвет заголовка/акцентов

    // --- Минимальная шапка: единый стиль с battlepass (без изменения привязок)
    $tpl = '
    <style>
      /* ЕДИНЫЙ СТИЛЬ ДЛЯ .bpTopHero (и совместимость с .bpHeroTop) */
      .bpTopHero, .bpHeroTop{
        margin:-5px 0 14px 0;
        padding:14px;
        border-radius:14px;
        background:linear-gradient(180deg,#ffffff 0%, #fffef8 100%);
        border:1px solid '.$accent_bd.';
        box-shadow:0 2px 0 '.$accent_bd.', 0 8px 24px rgba(17,24,39,.04);
      }

      /* Одинаковая сетка в шапке */
      .bpTopGrid, .bpHeroTop .bpTopGrid{
        display:grid;
        grid-template-columns:1fr auto;
        gap:12px;
        align-items:center;
      }

      /* Левая часть: иконка + заголовок */
      .bpLeft{display:flex;gap:12px;align-items:center}
      .bpTopHero .bpIcon, .bpHeroTop .bpIcon{
        width:44px;height:44px;min-width:44px;
        display:flex;align-items:center;justify-content:center;
        border-radius:12px;
        background:'.$accent_bg.';
        border:1px solid '.$accent_bd.';
        color:'.$accent_txt.';
        box-shadow: inset 0 1px 0 #fff;
      }
      .bpTopHero .bpIcon i, .bpHeroTop .bpIcon i{font-size:22px;line-height:1}

      .bpTopHero .bpTitle, .bpHeroTop .bpTitle{
        font-size:20px;font-weight:900;letter-spacing:.2px;margin:0;
        color:'.$accent_txt.';
      }

      /* Правая часть: крестик закрытия */
      .bpRight .Close{cursor:pointer;color:#8a8a8a;transition:color .18s ease, transform .18s ease}
      .bpRight .Close:hover{color:'.$accent_txt.';transform:scale(1.05)}

      /* Адаптив */
      @media(max-width:860px){
        .bpTopGrid, .bpHeroTop .bpTopGrid{grid-template-columns:1fr}
        .bpTopHero, .bpHeroTop{padding:12px 12px}
        .bpTopHero .bpTitle, .bpHeroTop .bpTitle{font-size:19px}
      }
    </style>

    <div class="bpTopHero">
      <div class="bpTopGrid">
        <div class="bpLeft">
          <div class="bpIcon"><i class="fa fa-gem" aria-hidden="true"></i></div>
          <div class="bpTitle">Боевой пропуск</div>
        </div>
        <div class="bpRight">
          <div class="Close" onclick="closeModal()" title="Закрыть"><i class="fas fa-times"></i></div>
        </div>
      </div>
    </div>';

    // Контейнер
    $tpl .= '<div class="DivCraft">';

    // Меню категорий (только навигация; контент грузится через passCategory)
    $tpl .= '<div class="CraftCategory bp-newCategory">';
    if ($bd) {
        $tpl .= '
          <div class="bpNewBtn mission active" onclick="passCategory(\'mission\')"><i class="fas fa-scroll"></i><span>Задания</span></div>
          <div class="bpNewBtn gift"    onclick="passCategory(\'track\')"><i class="fas fa-gift"></i><span>Награды</span></div>
          <div class="bpNewBtn stat"    onclick="passCategory(\'stat\')"><i class="fas fa-chart-bar"></i><span>Статистика</span></div>
          <div class="bpNewBtn history" onclick="passCategory(\'history\')"><i class="fas fa-history"></i><span>История</span></div>
          <div class="bpNewBtn info"    onclick="passCategory(\'info\')"><i class="fas fa-info-circle"></i><span>Инфо</span></div>
          <div class="bpNewBtn emblem"  onclick="passCategory(\'shop\')"><i class="fas fa-store"></i><span>Магазин</span></div>';
    }
    $tpl .= '</div>';

    // Пустой контент — НЕ рендерим, если пропуск не активирован
    if ($bd) {
        $tpl .= '<div class="CraftContent bp-newContent" id="bpContent"></div>';
    }

    // Если пропуск не активирован — минимальный экран приветствия
    if (!$bd) {
        $tpl .= '
        <div class="bp-welcome-block" style="text-align:center;padding:36px 10px;">
          <i class="fa fa-gem" style="font-size:56px;color:'.$accent_txt.';opacity:.7;"></i><br><br>
          <b style="font-size:20px; color:'.$accent_txt.';">Боевой пропуск</b><br><br>
          <span style="font-size:14px; color:#4a4a4a;">
            Выполняйте задания, открывайте уровни и получайте награды.
          </span><br>
          <button class="battlepassactivated"
                  style="margin-top:18px;font-size:16px;padding:9px 28px;cursor:pointer;
                         background:linear-gradient(90deg,'.$accent_txt.' 0%,#1bbb70 100%);
                         color:#fff;font-weight:800;border:none;border-radius:8px;"
                  onclick="battlepassactivated()">
            <i class="fa fa-play"></i> Активировать
          </button>
        </div>';
    } else {
        // По умолчанию грузим "Задания" внешним кодом
        $tpl .= '<script>setTimeout(function(){ if (typeof passCategory === "function") { passCategory("mission"); } }, 0);</script>';
    }

    $tpl .= '</div>'; // .DivCraft

    $response["html"] = $tpl;
    break;

case "nurseryGet":
        $basenum = isset($_POST['basenum']) ? clearInt($_POST['basenum']) : 0;
        $basenum = (int)$mysqli->real_escape_string($basenum);

        $PokemonQuery = $mysqli->query("
            SELECT `basenum`
            FROM `user_pokemons`
            WHERE `id` = '{$basenum}'
              AND `user_id` = '{$_SESSION['id']}'
              AND `active` = 0
            LIMIT 1
        ");
        $PokemonCount = $mysqli->query("
            SELECT `id`
            FROM `user_pokemons`
            WHERE `user_id` = '{$_SESSION['id']}'
              AND `active` = 1
            LIMIT 7
        ");

        if (!$PokemonQuery || $PokemonQuery->num_rows < 1) {
            $response['error'] = 1;
            $response['html']  = 'Ошибка получения покемона.';
        } elseif ($PokemonCount && $PokemonCount->num_rows > 5) {
            $response['error'] = 1;
            $response['html']  = 'В вашей команде может быть только 6 покемонов!';
        } else {
            $row = $PokemonQuery->fetch_assoc();
            $mysqli->query("
                UPDATE `user_pokemons`
                SET `active` = 1
                WHERE `user_id` = '{$_SESSION['id']}'
                  AND `id` = '{$basenum}'
                LIMIT 1
            ");

            $response['error']   = 0;
            $response['html']    = 'Покемон успешно переведён в команду!';
            $response['basenum'] = $row['basenum'];
        }
    break;

    case "nurseryList":

        $basenum = isset($_POST['basenum']) ? clearInt($_POST['basenum']) : 0;
        $basenum = (int)$mysqli->real_escape_string($basenum);

        $sort = isset($_POST['sort']) ? (int)$_POST['sort'] : 0;
        // 0: lvl DESC, 1: lvl ASC, 2: sparka ASC, 3: gender DESC, 4: gender ASC, 5: sparka DESC, 6+: name_new
        $orderMap = [
            0 => '`up`.`lvl` DESC',
            1 => '`up`.`lvl` ASC',
            2 => '`up`.`sparka` ASC',
            3 => '`up`.`gender` DESC',
            4 => '`up`.`gender` ASC',
            5 => '`up`.`sparka` DESC',
        ];
        $sortNursery = $orderMap[$sort] ?? '`up`.`name_new` ASC';

        $PokemonQuery = $mysqli->query("
            SELECT
                `up`.`id`,
                `up`.`basenum`,
                `up`.`type`,
                `up`.`name_new`,
                `up`.`lvl`,
                `up`.`gender`,
                `up`.`sparka`,
                `up`.`sparkaNumber`,
                `up`.`item_id`,
                `up`.`tren`,
                `up`.`trade`,
                `up`.`gen`,
                `up`.`vitamines`,
                `up`.`character`,
                `bp`.`name_rus`
            FROM `user_pokemons` AS `up`
            INNER JOIN `base_pokemons` AS `bp`
                ON `bp`.`id` = `up`.`basenum`
            WHERE
                `up`.`basenum` = {$basenum}
                AND `up`.`user_id` = {$_SESSION['id']}
                AND `up`.`active` = 0
            ORDER BY {$sortNursery}
        ");

        if (!$PokemonQuery || $PokemonQuery->num_rows < 1) {
            $response['html']    = '<center>Список данных покемонов пуст</center>';
            $response['pokList'] = [];
            break;
        }

        $dex     = numbPok($basenum);
        $pokList = [];
        $cards   = '<div class="nursery-wrap"><div class="nursery-grid">';

        while ($p = $PokemonQuery->fetch_assoc()) {
            // тренировка (иконка)
            if ($p['tren'] == 6)       { $tr = '<i class="trening fas fa-crown tr6"></i>'; }
            elseif ($p['tren'] == 5)   { $tr = '<i class="trening fas fa-angle-double-up tr5"></i>'; }
            elseif ($p['tren'] == 4)   { $tr = '<i class="trening fas fa-angle-double-up tr4"></i>'; }
            elseif ($p['tren'] == 3)   { $tr = '<i class="trening fas fa-angle-double-up tr3"></i>'; }
            elseif ($p['tren'] == 2)   { $tr = '<i class="trening fas fa-angle-double-up tr2"></i>'; }
            elseif ($p['tren'] == 1)   { $tr = '<i class="trening fas fa-angle-double-up tr1"></i>'; }
            else                        { $tr = ''; }

            // пол + спарка
            $paired = ($p['sparka'] == 1) ? 'spar' : '';
            if ($p['gender'] == 'Девочка')      { $genderIco = 'venus'; }
            elseif ($p['gender'] == 'Мальчик')  { $genderIco = 'mars'; }
            else                                 { $genderIco = 'genderless'; }
            $sexIcon = '<i class="fas fa-'.$genderIco.' '.$paired.' "></i>';

            // подготовка данных для клиента (оставляем структуру)
            $pokList[] = [
                'id'            => (int)$p['id'],
                'basenum'       => $dex,
                'type'          => $p['type'],
                'name'          => ($p['name_new'] ? $p['name_new'] : $p['name_rus']),
                'lvl'           => (int)$p['lvl'],
                'gender'        => $p['gender'],
                'sparka'        => (int)$p['sparka'],
                'sparkaNumber'  => (int)$p['sparkaNumber'],
                'trade'         => $p['trade'],
                'item_id'       => (int)$p['item_id'],
                'gen'           => $p['gen'],
                'tren'          => $tr,
                'vitamines'     => (int)$p['vitamines'],
                'sex'           => $sexIcon,
                'character'     => haracter_pokes($p['character'])
            ];

            // компактная карточка единого стиля
            $genParts = explode(',', (string)$p['gen']);
            $g0 = (int)($genParts[0] ?? 0);
            $g1 = (int)($genParts[1] ?? 0);
            $g2 = (int)($genParts[2] ?? 0);
            $g3 = (int)($genParts[3] ?? 0);
            $g4 = (int)($genParts[4] ?? 0);
            $g5 = (int)($genParts[5] ?? 0);

            $isShine = ($p['type'] === 'shine');
            $gCls = 'none';
            if ($p['gender'] === 'Мальчик') $gCls = 'male';
            elseif ($p['gender'] === 'Девочка') $gCls = 'female';

            $lockChip = ($p['trade'] === "false") ? '<span class="chip chip--lock" title="Заблокирован для обмена"><i class="fas fa-lock"></i></span>' : '';
            $itemChip = ((int)$p['item_id'] >= 1) ? '<span class="chip chip--item" title="Есть предмет"><i class="fas fa-cube"></i></span>' : '';
            $trenChip = ($tr !== '') ? '<span class="chip chip--train">'.$tr.'</span>' : '';

            $nameEsc = htmlspecialchars($p['name_new'] ? $p['name_new'] : $p['name_rus']);

            $cards .= '
            <div class="divFarmPoke id'.(int)$p['id'].'">
              <article class="poke-card '.($isShine ? 'is-shine' : '').' id'.(int)$p['id'].'" data-id="'.(int)$p['id'].'">
                <button class="btnBackPokemon poke-card__action" onclick="nursery(&quot;tooltip&quot;,'.(int)$p['id'].',this);" title="Забрать из питомника">
                  <i class="fas fa-sign-out-alt"></i>
                </button>

                <div class="pokemonBoxTiny size0 clickable poke-card__media" onclick="openInfoNursery('.(int)$p['id'].');">
                  <img class="image" loading="lazy" decoding="async" src="/img/pokemons/pokedex/'.$dex.'.png" alt="#'.$dex.' '.$nameEsc.'">
                </div>

                <div class="poke-card__name nameNur '.$p['type'].'-color">
                  <span class="dex">#'.$dex.'</span> '.$nameEsc.'
                </div>

                <div class="poke-card__chips">
                  <span class="chip chip--lvl">Lv '.(int)$p['lvl'].'</span>
                  <span class="chip chip--gender '.$gCls.(($p['sparka']==1)?' is-paired':'').'">
                    '.$sexIcon.(($p['sparka']==1)?' <span class="paired">('.(int)$p['sparkaNumber'].')</span>':'').'
                  </span>
                  '.$trenChip.$lockChip.$itemChip.'
                </div>

                <div class="extra poke-card__meta">
                  <span class="ivcode">
                    h'.$g0.'a'.$g1.'d'.$g2.'s'.$g3.'sa'.$g4.'sd'.$g5.'.'.(int)$p['vitamines'].' ('.(int)$p['sparkaNumber'].')
                    '.$tr.(($p['trade']==="false") ? ' <i class="fas fa-lock"></i>' : '').(((int)$p['item_id']>=1) ? ' <i class="fas fa-cube"></i>' : '').'
                  </span>
                </div>
              </article>
            </div>';
        }

        $cards .= '</div></div>';

        // возвращаем и массив (для существующего фронта), и готовый HTML (для прямого рендера)
        $response['pokList'] = $pokList;
        $response['html']    = $cards;

    break;

    case 'inventory':
		$user = $mysqli->query("SELECT `status`,`bagType`,`location`,`items_filter` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
		$eggsQuery = $mysqli->query('SELECT `ue`.*,`bp`.`name_rus`,`ue`.`reborn` FROM `user_egg` AS `ue` INNER JOIN `base_pokemons` AS `bp` ON `bp`.`id` = `ue`.`basenum` WHERE `ue`.`user` = '.intval($_SESSION['id']).' ');
		$categoryItems = (isset($_POST['category']) && $_POST['category'] != 'all' ? 'AND `bi`.`type` = "'.$_POST['category'].'"' : 'AND `bi`.`type` != "berry"');
		if($user['items_filter'] == 1){//Сначала новые
			$itemsQuery = $mysqli->query('SELECT `iu`.*, `bi`.`lombard`,`bi`.`rait_it`,`bi`.`name`,`bi`.`about`,`bi`.`weight`,`bi`.`use`,`bi`.`dress`,`bi`.`drop`,`bi`.`trade`,`bi`.`give`,`bi`.`info`,`bi`.`tm_id`,`bi`.`type`,`bi`.`cool` FROM `items_users` AS `iu` INNER JOIN `base_items` AS `bi` ON `bi`.`id` = `iu`.`item_id` '.$categoryItems.' WHERE `iu`.`user` = '.intval($_SESSION['id']).'  ORDER BY `iu`.`id` ASC');
		}elseif($user['items_filter'] == 2){//Сначала старые
			$itemsQuery = $mysqli->query('SELECT `iu`.*, `bi`.`lombard`,`bi`.`rait_it`,`bi`.`name`,`bi`.`about`,`bi`.`weight`,`bi`.`use`,`bi`.`dress`,`bi`.`drop`,`bi`.`trade`,`bi`.`give`,`bi`.`info`,`bi`.`tm_id`,`bi`.`type`,`bi`.`cool` FROM `items_users` AS `iu` INNER JOIN `base_items` AS `bi` ON `bi`.`id` = `iu`.`item_id` '.$categoryItems.' WHERE `iu`.`user` = '.intval($_SESSION['id']).'   ORDER BY `iu`.`id` DESC');
		}elseif($user['items_filter'] == 3){//По категории
			$itemsQuery = $mysqli->query('SELECT `iu`.*, `bi`.`lombard`,`bi`.`rait_it`,`bi`.`name`,`bi`.`about`,`bi`.`weight`,`bi`.`use`,`bi`.`dress`,`bi`.`drop`,`bi`.`trade`,`bi`.`give`,`bi`.`info`,`bi`.`tm_id`,`bi`.`type`,`bi`.`cool` FROM `items_users` AS `iu` INNER JOIN `base_items` AS `bi` ON `bi`.`id` = `iu`.`item_id` '.$categoryItems.' WHERE `iu`.`user` = '.intval($_SESSION['id']).' ORDER BY `bi`.`type` ASC');
		}elseif($user['items_filter'] == 4){//По количеству
			$itemsQuery = $mysqli->query('SELECT `iu`.*, `bi`.`lombard`,`bi`.`rait_it`,`bi`.`name`,`bi`.`about`,`bi`.`weight`,`bi`.`use`,`bi`.`dress`,`bi`.`drop`,`bi`.`trade`,`bi`.`give`,`bi`.`info`,`bi`.`tm_id`,`bi`.`type`,`bi`.`cool` FROM `items_users` AS `iu` INNER JOIN `base_items` AS `bi` ON `bi`.`id` = `iu`.`item_id` '.$categoryItems.' WHERE `iu`.`user` = '.intval($_SESSION['id']).'  ORDER BY `iu`.`count` DESC');
		}else{
			$itemsQuery = $mysqli->query('SELECT `iu`.*, `bi`.`lombard`,`bi`.`rait_it`,`bi`.`name`,`bi`.`about`,`bi`.`weight`,`bi`.`use`,`bi`.`dress`,`bi`.`drop`,`bi`.`trade`,`bi`.`give`,`bi`.`info`,`bi`.`tm_id`,`bi`.`type`,`bi`.`cool` FROM `items_users` AS `iu` INNER JOIN `base_items` AS `bi` ON `bi`.`id` = `iu`.`item_id` '.$categoryItems.' WHERE `iu`.`user` = '.intval($_SESSION['id']).'  ORDER BY `bi`.`id` ASC');
		}
		$weight = 0;

		switch($user['bagType']){
            case 87:$weightMax = 500;break;
            case 85:$weightMax = 1000;break;
            case 88:$weightMax = 2500;break;
            case 84:$weightMax = 5000;break;
            case 89:$weightMax = 7500;break;
            case 90:$weightMax = 10000;break;
			case 99999:$weightMax = 10000000;break;
            default:$weightMax = 250;break;
        }
				$LocationNpcCount = $mysqli->query('SELECT `id` FROM `base_npc` WHERE `loc_id` = '.$user['location']);
		if($_POST['category'] == 'egg' || $_POST['category'] == 'all' || !isset($_POST['category'])){
			$eggList = [];
			while ($egg = $eggsQuery->fetch_assoc()){
				$gen = explode(',',$egg['gens']);
				$gen = '[h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5].']';
				$month = array(1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря');
				$reborn = $egg['reborn'] - $time;
		$reborn = time()+$reborn;
		$reborn = downcountermin($reborn);
		if($egg['reborn'] > time()){
			$reborn = 'Вылупится через '.$reborn;
		}else{
			$reborn = 'Яйцо готово к вылуплению...';
		}
				$eggList[$egg['id']] = [
					'ctgMdl'=>$_POST['category'],
					'id'=>$egg['id'],
					'gen'=>'Генокод: '.$gen,
					'reborn'=>$reborn,
					'other'=>'151,1,0,false,false,false,true,false,false,\''.$user['status'].'\',true',
					'loc'=>$LocationNpcCount->num_rows,
					'name'=>$egg['name_rus'],
					'ustatus'=>$user['status'],
					'basenum'=>$egg['basenum']
				];
				$dop = "";
				$basenumegg = $egg['basenum'];
			}
		}
		// while ($items = $itemsQuery->fetch_assoc()){
			// $imgItem = ($items['item_id'] == '1' ? (item_isset(1,3000000) ? '1.2' : '1') : $items['item_id']);
			// $weight = $items['count']*$items['weight'] + $weight;
			// $itemList[$items['id']] = [
				// 'ctgMdl'=>$_POST['category'],
				// 'id'=>$items['id'],
				// 'name'=>($items['item_id'] == 10001 ? $items['name'].' №'.$items['json'] : $items['name']),
				// 'about'=>$items['about'],
				// 'img'=>$imgItem,
				// 'count'=>$items['count'],
				// 'itemWeight'=>$items['weight'],
				// 'use'=>$items['use'],
				// 'dress'=>$items['dress'],
				// 'drop'=>$items['drop'],
				// 'trade'=>$items['trade'],
				// 'give'=>$items['give'],
				// 'ustatus'=>$user['status'],
				// 'count2'=>number_format($items['count'],0,'.','.')
			// ];
		// }






        $arc = array();
		    while($items = $itemsQuery->fetch_assoc()){
		    if(!in_array($items['item_id'], $arc)){
		        
		$arc[] = $items['item_id'];
			if($items['date_expiration'] == 0 or ($items['date_expiration'] > 0 AND $items['date_expiration'] > time())){
			    if($items['str'] or $items['cool'] == 1){
			        $tpll.= '<div class="Item" onclick="itemOpenMore('.$items['item_id'].')"><div class="blockrait '.$items['rait_it'].'"></div><img id="imgItem" src="/img/world/items/little/'.$items['item_id'].'.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"><div class="Arrow"><i class="fa fa-chevron-right"></i></div></div><span class="MoreItems MoreItems'.$items['item_id'].'"></span>';
			
			    }else{


			if($items['item_id'] >= 1000001) {
				$mesto = explode(',',$items['info']);
			}
			if($items['type'] == 'tm' and $items['item_id'] >= 2000) $tr_id = $items['tm_id']-2000;
            if($items['date_expiration']-$items['date_receiving']==0){
                $expiration = 0;
            }else{
                $expiration = round(100-(((time()-$items['date_receiving'])/($items['date_expiration']-$items['date_receiving']))*100));
            }
			$imgItem = ($items['item_id'] >= 1000001 ? $mesto[0].'.'.$mesto[1] : $items['item_id']);
            $weight = $items['count']*$items['weight'] + $weight;
            if($items['dop'] != 0){ $dop = ", ".$items['dop']."%";}else{ $dop = "";}
            if($items['dop'] != 0 and $items['item_id'] == 9999){$dop = " #".$items['dop']; }
            $basenumegg = 0;
			$tpll.= '<div class="Item" onclick="itemOpen(this,\''.$_POST['category'].'\','.$items['id'].',\''.($items['item_id'] == 10001 ? $items['name'].' №'.$items['json'] : $items['name']).'\',\''.$items['about'].'\','.$imgItem.','.$items['count'].','.$items['weight'].','.$items['use'].','.$items['dress'].','.$items['drop'].','.$items['trade'].','.$items['give'].',\'false\',\''.$user['status'].'\',false,'.$LocationNpcCount->num_rows.',\''.$items['type'].'\',\''.$dop.'\',\''.$basenumegg.'\','.$items['lombard'].');"><div class="blockrait '.$items['rait_it'].'"></div>';
			if($items['date_expiration'] != 0) $tpll.= '<div class="ItemReborn">'.$expiration.'%</div>';
			if($items['type'] == 'tm' and $items['item_id'] <= 1100){ $tpll.= '<div class="Name">TM '.$items['tm_id'].'</div>'; }
			if($items['type'] == 'tm' and $items['item_id'] >= 2000){ $tpll.= '<div class="Name">TR '.$tr_id.'</div>'; }
			if($items['item_id'] == 9999){ $tpll.= '<div class="Name">#'.$items['dop'].'</div>'; }
			$tpll.= '<img id="imgItem" src="/img/world/items/little/'.$imgItem.'.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');">';
			$tpll.= '<div class="Count">'.($items['count'] != 1 ? number_format($items['count'],0,'.','.') : '').' </div> '.($items['str'] != NULL ? '<div class="Str">'.$str[0].'/'.$str[1].'</div>' : '');
			$tpll.= '</div>';
			    }
		}else{
		  //  if($items['type'] == 'berry'){
		  //      itemAdd(366,1);
		  //  }
			$mysqli->query("DELETE FROM `items_users` WHERE `id` = '".$items['id']."'");

		}}}
		$mysqli->query('UPDATE `users` SET `weight` = '.$weight.' WHERE `id` = '.$_SESSION['id']);
        $bagName = $mysqli->query("SELECT `name` FROM `base_items` WHERE `id` = ".$user['bagType'])->fetch_assoc();
		$response['eggList'] = $eggList;
		//$response['itemList'] = $itemList;
		$response['bagName'] = $bagName['name'];
		$response['bagType'] = $user['bagType'];

		$response['moneyyoy'] = number_format(item_isset_count(1),0,'.','.');
		$response['bag'] = $weight;
		$response['bagMax'] = $weightMax;
        $response["html"] = $tpll;
        break;
        case 'versionGame':
            $tpl = '<div class="Title"><div class="Name">Обновления игры</div><div class="Info">Информация о старых обновлениях игры.</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>';
				$tpl .= '
				<div class="VersionList">';
				$bdq = $mysqli->query('SELECT * FROM `base_old_version` ORDER BY `id` DESC');
				while($bd = $bdq->fetch_assoc()){

				    $tpl .= ' <div class="blockVersion ">
				                <h2>Обновление '.$bd['version'].'</h2>'.$bd['text'].' <div class="dateVersion">'.$bd['date'].'</div>
				    </div>';
				}


        $tpl .= '</div>';
				$response["html"] = $tpl;
        break;

		//case 'transfer':

				//$tpl = '<div class="Title"><div class="Name">Перенос аккаунта</div><div class="Info">Здесь можно перенести инвентарь и покемонов из прошлой версии игры.</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div><div class="TransferContent">';
				//if($u['invaite'] != 0){
				//if($u['lvl'] >= 5){
					//$tpl .= '<div class="Inv">Перенос инвентаря: ';
					//if($u['tr_it'] == 0){
					    //$tpl .= '<div class="button" onclick="transfItem();">Перенести</div>';
					//}else{
					    //$tpl .= '<div class="button" >Уже перенесен</div>';
					//}
					//$tpl .= '</div><div class="PokemonTr">Перенос покемонов:</div>';
					//$tpl .= '<div class="ListPokemonTransfer"><div class="list">';
					//$us = $mysqli->query('SELECT * FROM `transfer_users` WHERE `reg` = '.$_SESSION['id'])->fetch_assoc();
					//$pok = $mysqli->query('SELECT * FROM `transfer_pokemon` WHERE `users` = '.$us['id'].' ORDER BY `basenum` ASC LIMIT 30');
				//while($poks = $pok->fetch_assoc()){
				//	//$tr = "";
					//$o = "";
					//if($poks['sex'] == 1){ $s = '<i class="fas fa-mars"></i>';}
					//if($poks['sex'] == 2){ $s = '<i class="fas fa-venus"></i>';}
					//if($poks['tips'] == 'shine'){ $o = "shine-color";}
					//if($poks['traning'] == 1.1){
					    //$tr = '<i class="trening fas fa-angle-double-up tr1"></i>';
					//}elseif($poks['traning'] == 1.18){
					    //$tr = '<i class="trening fas fa-angle-double-up tr2"></i>';
					//}elseif($poks['traning'] == 1.25){
					    //$tr = '<i class="trening fas fa-angle-double-up tr3"></i>';
					//}elseif($poks['traning'] == 1.31){
					    //$tr = '<i class="trening fas fa-angle-double-up tr4"></i>';
					//}elseif($poks['traning'] == 1.36){
					    //$tr = '<i class="trening fas fa-angle-double-up tr5"></i>';
					//}elseif($poks['traning'] == 1.4){
					    //$tr = '<i class="trening fas fa-crown tr6"></i>';
					//}
					//$basenum = numbPok($poks['basenum']);
					//$p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$poks['basenum'])->fetch_assoc();
					//$tpl .= '<div onclick="transfPok('.$poks['id'].')" class="listPok id'.$poks['id'].'">
        //<div class="number_block">
          //<img src="/img/pokemons/animation/'.$basenum.'.png"> # '.$basenum.'
        //</div>
        //<div class="info_block">
          //<div class="name '.$o.'">'.$p['name_rus'].' '.$s.' '.$poks['lvl'].'lvl '.$tr.'</div>
        //</div>
      //</div>';
				//}
				//$tpl.= '</div><button class="DopBtn" onclick="transferload()" data-title="1">Еще</button>';
					//$tpl .= '</div><div class="PokemonVeiw"></div>';
				//}else{
					//$tpl .= '<div class="block"><i class="far fa-lock-alt"></i><br><span>Перенос аккаунта доступен игрокам, достигшим <b>5</b> уровня.</span></div>';
				//}
				//}else{
					//$tpl .= '<div class="block"><i class="far fa-lock-alt"></i><br><span>Вам недоступен перенос аккаунта!</div>';
				//}
				//$tpl .= '</div>';
				//$response["html"] = $tpl;
				//break;
    case 'dialogs':
			$Dialogs = $mysqli->query('SELECT
									`udl`.*,
									`u`.`login`,
									`u`.`user_group`
									FROM `user_dialogs_links` AS `udl`
									LEFT JOIN `users` AS `u`
									ON
										`u`.`id` != '.$uid.' AND (`u`.`id` = `udl`.`sender` OR `u`.`id` = `udl`.`me`)
									WHERE (`udl`.`me`= '.$uid.' OR `udl`.`sender`= '.$uid.')
									ORDER BY `date` DESC LIMIT 200');
			if($Dialogs->num_rows < 1){
				$tpl .= '<center><b>У вас нет личных сообщений.</b></center>';
			}else{
				while($send = $Dialogs->fetch_assoc()){
					$tpl .= '<div class="user-link u-'.$send['user_group'].'">'.$send['login'].'</div><br />';
				}
			}
			$response["html"] = $tpl;
        break;
case 'trainers':
    $users_list = "";
    $timeOnline = time() - 300;

    // допустимые вкладки
    $allowedTabs = [1,2,3,4,5,6,7,8,9,10,11,12];

    // ВСЕГДА дефолт = 2 (Онлайн), без хранения в сессии
    if (isset($_REQUEST['tab']) && in_array((int)$_REQUEST['tab'], $allowedTabs, true)) {
        $tab = (int)$_REQUEST['tab'];
    } else {
        $tab = 2; // Онлайн по умолчанию
    }


    // ---------- helpers ----------
    $h = static function($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

    $avatarFile = static function($id) use ($patch_project){
        $id = (int)$id;
        $path = $patch_project.'/img/avatars/mini/'.$id.'.png';
        return file_exists($path) ? $id : 'no-user-img';
    };

    $onlineDot = static function($online) use ($timeOnline){
        return ((int)$online >= $timeOnline)
            ? '<div class="Status onl"></div>'
            : '<div class="Status ofl"></div>';
    };

    $trainerRow = static function(array $u, string $subtitle = '', ?int $place = null) use ($avatarFile, $onlineDot, $h){
        $id      = (int)$u['id'];
        $group   = (int)$u['user_group'];
        $login   = $h($u['login']);
        $subtitle= trim($subtitle) !== '' ? $h($subtitle) : '';
        $avatar  = $avatarFile($id);
        $online  = $onlineDot($u['online']);
        $placeHtml = $place !== null ? '<div class="Place">'.$place.'</div>' : '';

        return '
        <div class="TrainerRow">
            '.$placeHtml.'
            <div onclick=showUserTooltip("'.$id.'") class="Avatar" style="background-image:url(img/avatars/mini/'.$avatar.'.png);">'.$online.'</div>
            <div class="Info">
                <div class="Name"><div class="u-'.$group.' label" onclick=user_to_chat_add("'.$id.'")>'.$login.'</div></div>
                '.($subtitle !== '' ? '<div class="Other">'.$subtitle.'</div>' : '').'
            </div>
        </div>';
    };

    // Виды стадионов (пример)
    $gymTypes = [
        1001 => "Электрический стадион",
        1002 => "Боевой стадион",
        1003 => "Стадион Драконов",
        1004 => "Огненный стадион",
        1005 => "Ядовитый стадион",
        1006 => "Тёмный стадион",
        1007 => "Земляной стадион",
        1008 => "Психический стадион",
    ];

    // Текущий поисковый запрос (если серверная вкладка 9 используется)
    $searchQuery = ($tab === 9 && isset($_POST['text'])) ? trim((string)$_POST['text']) : '';

    // ---------- data ----------
    switch ($tab) {
        case 1: // Друзья
            $me = (int)$_SESSION['id'];
            $friendsQuery = $mysqli->query("
                SELECT `friend_id`,`user_id`
                FROM `users_friend`
                WHERE (`user_id`={$me} OR `friend_id`={$me}) AND `status`='1'
            ");
            $ids = [];
            while ($row = $friendsQuery->fetch_assoc()) {
                $ids[] = ($row['user_id'] == $me) ? (int)$row['friend_id'] : (int)$row['user_id'];
            }
            if ($ids) {
                $res = $mysqli->query("
                    SELECT u.id,u.login,u.user_group,u.location,u.online, bl.name AS loc_name
                    FROM users u
                    LEFT JOIN base_location bl ON bl.id = u.location
                    WHERE u.id IN (".implode(',', array_map('intval',$ids)).")
                ");
                while ($u = $res->fetch_assoc()) {
                    $subtitle = $u['loc_name'] ?: 'Местоположение неизвестно';
                    $users_list .= $trainerRow($u, $subtitle);
                }
            }
            break;

        case 2: // Игроки онлайн
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online, br.name AS region_name
                FROM users u
                LEFT JOIN base_region br
                  ON br.id = (SELECT region FROM base_location bl WHERE bl.id = u.location)
                WHERE u.online >= ".(int)$timeOnline."
                ORDER BY u.user_group ASC, u.id ASC
            ");
            while ($u = $res->fetch_assoc()) {
                $subtitle = $u['region_name'] ? ('Регион: '.$u['region_name']) : 'Онлайн';
                $users_list .= $trainerRow($u, $subtitle);
            }
            break;

        case 3: // Администрация
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online, br.name AS region_name
                FROM users u
                LEFT JOIN base_region br
                  ON br.id = (SELECT region FROM base_location bl WHERE bl.id = u.location)
                WHERE u.user_group = 1
                ORDER BY u.online DESC
            ");
            while ($u = $res->fetch_assoc()) {
                $subtitle = $u['region_name'] ? ('Регион: '.$u['region_name']) : 'Администрация';
                $users_list .= $trainerRow($u, $subtitle);
            }
            break;

        case 4: // Лидеры стадионов
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online
                FROM users u
                WHERE u.user_group = 5
                ORDER BY u.online DESC
            ");
            while ($u = $res->fetch_assoc()) {
                $type = $gymTypes[(int)$u['id']] ?? 'Лидер стадиона';
                $users_list .= $trainerRow($u, $type);
            }
            break;

        case 5: // Полиция
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online, br.name AS region_name
                FROM users u
                LEFT JOIN base_region br
                  ON br.id = (SELECT region FROM base_location bl WHERE bl.id = u.location)
                WHERE u.user_group = 2
                ORDER BY u.online DESC
            ");
            while ($u = $res->fetch_assoc()) {
                $subtitle = $u['region_name'] ? ('Регион: '.$u['region_name']) : 'Полиция';
                $users_list .= $trainerRow($u, $subtitle);
            }
            break;

        case 6: // Модераторы
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online, br.name AS region_name
                FROM users u
                LEFT JOIN base_region br
                  ON br.id = (SELECT region FROM base_location bl WHERE bl.id = u.location)
                WHERE u.user_group = 3
                ORDER BY u.online DESC
            ");
            while ($u = $res->fetch_assoc()) {
                $subtitle = $u['region_name'] ? ('Регион: '.$u['region_name']) : 'Модератор';
                $users_list .= $trainerRow($u, $subtitle);
            }
            break;

        case 7: // Поддержка
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online, br.name AS region_name
                FROM users u
                LEFT JOIN base_region br
                  ON br.id = (SELECT region FROM base_location bl WHERE bl.id = u.location)
                WHERE u.user_group = 4
                ORDER BY u.online DESC
            ");
            while ($u = $res->fetch_assoc()) {
                $subtitle = $u['region_name'] ? ('Регион: '.$u['region_name']) : 'Поддержка';
                $users_list .= $trainerRow($u, $subtitle);
            }
            break;

        case 8: // Топ 50 PVE
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online,u.countKillPok
                FROM users u
                WHERE u.user_group != 1
                ORDER BY u.countKillPok DESC
                LIMIT 50
            ");
            $you = $mysqli->query("SELECT id,login,user_group,location,online,countKillPok FROM users WHERE id=".(int)$_SESSION['id'])->fetch_assoc();
            $rankYou = $mysqli->query("
                SELECT COUNT(*) AS cnt
                FROM users
                WHERE user_group!=1 AND id!=2 AND countKillPok >= ".(int)$you['countKillPok']."
            ")->fetch_assoc();
            $users_list .= $trainerRow($you, 'Рейтинг PVE '.$you['countKillPok'], (int)$rankYou['cnt']);
            $users_list .= '<div class="Divider"></div>';
            $i = 1;
            while ($u = $res->fetch_assoc()) {
                $users_list .= $trainerRow($u, 'Рейтинг PVE '.$u['countKillPok'], $i++);
            }
            break;

        case 9: // Поиск (серверная вкладка — оставляем совместимость)
            $stmt = $mysqli->prepare("
                SELECT u.id,u.login,u.user_group,u.location,u.online, br.name AS region_name
                FROM users u
                LEFT JOIN base_region br
                  ON br.id = (SELECT region FROM base_location bl WHERE bl.id = u.location)
                WHERE u.login LIKE CONCAT('%', ?, '%')
                ORDER BY u.online DESC
            ");
            $stmt->bind_param('s', $searchQuery);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($u = $res->fetch_assoc()) {
                $subtitle = $u['region_name'] ? ('Регион: '.$u['region_name']) : 'Профиль';
                $users_list .= $trainerRow($u, $subtitle);
            }
            $stmt->close();
            break;

        case 10: // Топ 50 PVP
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online,u.pvp
                FROM users u
                WHERE u.user_group != 1
                ORDER BY u.pvp DESC
                LIMIT 50
            ");
            $you = $mysqli->query("SELECT id,login,user_group,location,online,pvp FROM users WHERE id=".(int)$_SESSION['id'])->fetch_assoc();
            $rankYou = $mysqli->query("
                SELECT COUNT(*) AS cnt
                FROM users
                WHERE user_group!=1 AND id!=2 AND pvp >= ".(int)$you['pvp']."
            ")->fetch_assoc();
            $users_list .= $trainerRow($you, 'Рейтинг PVP '.$you['pvp'], (int)$rankYou['cnt']);
            $users_list .= '<div class="Divider"></div>';
            $i = 1;
            while ($u = $res->fetch_assoc()) {
                $users_list .= $trainerRow($u, 'Рейтинг PVP '.$u['pvp'], $i++);
            }
            break;

        case 11: // Покедекс (Топ 50)
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online,u.countNormal
                FROM users u
                WHERE u.user_group != 1
                ORDER BY u.countNormal DESC
                LIMIT 50
            ");
            $you = $mysqli->query("SELECT id,login,user_group,location,online,countNormal FROM users WHERE id=".(int)$_SESSION['id'])->fetch_assoc();
            $rankYou = $mysqli->query("
                SELECT COUNT(*) AS cnt
                FROM users
                WHERE user_group!=1 AND id!=2 AND countNormal >= ".(int)$you['countNormal']."
            ")->fetch_assoc();
            $users_list .= $trainerRow($you, 'Покедекс '.$you['countNormal'], (int)$rankYou['cnt']);
            $users_list .= '<div class="Divider"></div>';
            $i = 1;
            while ($u = $res->fetch_assoc()) {
                $users_list .= $trainerRow($u, 'Покедекс '.$u['countNormal'], $i++);
            }
            break;

        case 12: // Шайнидекс (Топ 50)
            $res = $mysqli->query("
                SELECT u.id,u.login,u.user_group,u.location,u.online,u.countShine
                FROM users u
                WHERE u.user_group != 1
                ORDER BY u.countShine DESC
                LIMIT 50
            ");
            $you = $mysqli->query("SELECT id,login,user_group,location,online,countShine FROM users WHERE id=".(int)$_SESSION['id'])->fetch_assoc();
            $rankYou = $mysqli->query("
                SELECT COUNT(*) AS cnt
                FROM users
                WHERE user_group!=1 AND id!=2 AND countShine >= ".(int)$you['countShine']."
            ")->fetch_assoc();
            $users_list .= $trainerRow($you, 'Шайнидекс '.$you['countShine'], (int)$rankYou['cnt']);
            $users_list .= '<div class="Divider"></div>';
            $i = 1;
            while ($u = $res->fetch_assoc()) {
                $users_list .= $trainerRow($u, 'Шайнидекс '.$u['countShine'], $i++);
            }
            break;

        default:
            $js.="$.notify({message:'Ошибка в выполнении скрипта. Перезагрузите страницу.'},{type:'danger',placement:{from:'top',align:'right'}});";
            break;
    }

    if ($users_list === "") {
        $users_list = "<div class='unknown'>Категория пуста</div>";
    }

    // ---------- sidebar (подкатегории) ----------
    $groups = [
  'Основное' => [
    ['id'=>2, 'title'=>'Игроки онлайн'],
    ['id'=>1, 'title'=>'Друзья'],
    ['id'=>9, 'title'=>'Поиск'],
  ],
        'Команда' => [
            ['id'=>3,  'title'=>'Администрация'],
            ['id'=>5,  'title'=>'Полиция'],
            ['id'=>6,  'title'=>'Модераторы'],
        ],
        'Лиги' => [
            ['id'=>4,  'title'=>'Лидеры стадионов'],
        ],
        'Рейтинги' => [
            ['id'=>10, 'title'=>'Топ 50 PVP'],
            ['id'=>8,  'title'=>'Топ 50 PVE'],
            ['id'=>11, 'title'=>'Покедекс'],
            ['id'=>12, 'title'=>'Шайнидекс'],
        ],
    ];

    $catsHtml = '';
    foreach ($groups as $groupTitle => $items) {
        $catsHtml .= '<div class="CatGroup"><div class="CatGroupTitle">'.$h($groupTitle).'</div>';
        foreach ($items as $it) {
            $catsHtml .= '<div class="Cat '.($tab==(int)$it['id']?'active':'').'" data-id="'.(int)$it['id'].'" onclick="setTab(this,'.(int)$it['id'].')">'.$h($it['title']).'</div>';
        }
        $catsHtml .= '</div>';
    }

    // ---------- стили (500×535; List=340px; Sidebar=140px; ник/other без обрезки) ----------
    $style = '
    <style id="trainers-style-500x535">
    .Trainers{ --bg:#eef3fa; --panel:#f9fbff; --line:#dde3ee; --text:#223148; --muted:#63758e;
      --accent:#6c99ca; --ok:#26b37e; --off:#c9ced8; --card:#fff;
      width:500px; max-width:96vw; height:535px; max-height:96vh;
      color:var(--text); background:var(--bg); border-radius:12px; box-shadow:0 6px 24px rgba(39,69,120,.08); overflow:hidden;}
    :root, html, body, .Trainers .List, .Trainers .Sidebar{scroll-behavior:auto!important}

    .Title{display:flex;align-items:center;gap:12px;padding:8px 10px 6px 12px;
      background:linear-gradient(180deg,#f6f9ff 0%,#eef3fa 100%);border:1px solid var(--line);border-radius:12px 12px 0 0}
    .Title .Name{font-family:"Nunito",system-ui,Arial;font-weight:900;letter-spacing:.2px;text-transform:uppercase;color:#1b2b45}
    .Title .Close{margin-left:auto;color:#9aa7bb;cursor:pointer;transition:color .12s,transform .12s}
    .Title .Close:hover{color:#d67a82;transform:scale(1.06)}

    .Trainers .Body{height:calc(100% - 44px);
      display:grid;grid-template-columns:340px 140px;gap:10px;padding:10px;box-sizing:border-box}
    @media (max-width:560px){ .Trainers{width:98vw;height:92vh} .Trainers .Body{grid-template-columns:1fr;gap:8px} }

    .Trainers .List{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:4px;height:100%;overflow:auto;
      scrollbar-width:thin;scrollbar-color:#cfd7e8 transparent}
    .Trainers .List::-webkit-scrollbar{width:10px}
    .Trainers .List::-webkit-scrollbar-thumb{background:#cfd7e8;border-radius:10px}

    .Trainers .TrainerRow{display:grid;grid-template-columns:auto 40px 1fr;gap:10px;align-items:center;
      background:var(--card);border:1px solid var(--line);border-radius:12px;padding:8px 10px;margin:6px;
      transition:transform .04s,box-shadow .12s,border-color .12s}
    .Trainers .TrainerRow:hover{box-shadow:0 6px 16px rgba(48,78,130,.10);border-color:#cfd7e8}
    .Trainers .TrainerRow:active{transform:translateY(1px)}
    .Trainers .TrainerRow .Place{width:26px;height:26px;border-radius:7px;background:linear-gradient(180deg,#f4f7ff 0%,#e9eef9 100%);
      border:1px solid var(--line);display:grid;place-items:center;font-weight:800;color:#6f7c94;font-size:12px}
    .Trainers .TrainerRow .Avatar{width:40px;height:40px;border-radius:50%;background-size:cover;background-position:center;border:2px solid #fff;
      box-shadow:0 0 0 1px var(--line) inset;position:relative;cursor:pointer}
    .Trainers .TrainerRow .Avatar .Status{position:absolute;right:-3px;bottom:-3px;width:12px;height:12px;border:2px solid #fff;border-radius:50%}
    .Trainers .TrainerRow .Avatar .Status.onl{background:var(--ok)}
    .Trainers .TrainerRow .Avatar .Status.ofl{background:var(--off)}
    .Trainers .TrainerRow .Info{min-width:120px;display:flex;flex-direction:column}
    .Trainers .TrainerRow .Name{font-weight:800;line-height:1.22;margin-bottom:2px;font-size:14px;min-width:0}
    .Trainers .TrainerRow .Name .label{display:inline;white-space:normal;overflow:visible;text-overflow:clip;word-break:break-word}
    .Trainers .TrainerRow .Other{color:var(--muted);font-size:12.6px;line-height:1.22;white-space:normal;overflow:visible;text-overflow:clip}
    .Trainers .Divider{height:1px;margin:6px;background:var(--line)}
    .unknown{text-align:center;color:var(--muted);font-style:italic;padding:12px 0}

    .Trainers .Sidebar{background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:6px;display:flex;flex-direction:column;gap:6px;
      height:100%;overflow:auto;scrollbar-width:thin;scrollbar-color:#cfd7e8 transparent}
    .Trainers .Sidebar::-webkit-scrollbar{width:10px}
    .Trainers .Sidebar::-webkit-scrollbar-thumb{background:#cfd7e8;border-radius:10px}
    .Trainers .Sidebar .SearchBox input{width:89%;height:34px;padding:6px 9px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--text);
      font-size:13px;outline:none;transition:box-shadow .15s,border-color .15s}
    .Trainers .Sidebar .SearchBox input:focus{border-color:#c7d3ea;box-shadow:0 0 0 3px rgba(108,153,202,.18)}

    .Trainers .CatList{display:flex;flex-direction:column;gap:6px}
    .Trainers .CatGroup{display:flex;flex-direction:column;gap:5px}
    .Trainers .CatGroupTitle{font-weight:900;color:#7b8aa3;font-size:10.6px;text-transform:uppercase;letter-spacing:.35px;padding:0 2px}
    .Trainers .Cat{background:#fff;border:1px solid var(--line);border-radius:9px;padding:6px 8px;font-weight:750;font-size:12px;color:#3b5173;cursor:pointer;
      user-select:none;transition:background .12s,border-color .12s,box-shadow .12s,transform .04s,color .12s}
    .Trainers .Cat:hover{background:#f3f7fe;border-color:#c7d3ea;box-shadow:0 3px 10px rgba(48,78,130,.08)}
    .Trainers .Cat:active{transform:translateY(1px)}
    .Trainers .Cat.active{background:linear-gradient(180deg,#7fb0de 0%,#6c99ca 100%);border-color:transparent;color:#fff;box-shadow:0 6px 16px rgba(108,153,202,.30)}

    /* Мобильные: сайдбар сверху, группы сворачиваются */
    @media (max-width:560px){
      .Trainers .Sidebar{order:-1}
      .Trainers .CatGroup{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
      .Trainers .CatGroupTitle{grid-column:1 / -1;margin-top:2px;display:flex;align-items:center;gap:6px;cursor:pointer}
      .Trainers .CatGroup.collapsed .Cat{display:none}
      .Trainers .CatGroupTitle::before{content:"▾";font-weight:900;color:#90a2bf}
      .Trainers .CatGroup.collapsed .CatGroupTitle::before{content:"▸"}
    }

    
    </style>';

    // ---------- layout ----------
    $tpl = '
    <div class="Title">
        <div class="Name">Тренеры</div>
        <div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
    </div>

    <div class="Trainers">
        <div class="Body">
            <div class="List">'.$users_list.'</div>

            <aside class="Sidebar">
                <div class="SearchBox">
                    <input type="text" placeholder="Найти тренера" id="searchUser"
                           value="'.$h($searchQuery).'"
                           onkeydown="if(event.keyCode==13){searchUser();}">
                </div>
                <div class="CatList">'.$catsHtml.'</div>
            </aside>
        </div>
    </div>';

    // ---------- JS: живой поиск в том же окне + сохранение скролла + аккордеон ----------
    $script = '
    <script>
    (function(){
      function applyClientSearch(term){
        term = (term||"").trim().toLowerCase();
        var rows = document.querySelectorAll(".Trainers .TrainerRow");
        var visible = 0;
        rows.forEach(function(r){
          var name = (r.querySelector(".Name .label")?.textContent || "").toLowerCase();
          var other = (r.querySelector(".Other")?.textContent || "").toLowerCase();
          var ok = !term || name.indexOf(term) > -1 || other.indexOf(term) > -1;
          r.style.display = ok ? "" : "none";
          if(ok) visible++;
        });
        var list = document.querySelector(".Trainers .List");
        if(list){
          var emp = list.querySelector(".client-search-empty");
          if(!visible){
            if(!emp){
              emp = document.createElement("div");
              emp.className = "client-search-empty unknown";
              emp.textContent = "Ничего не найдено";
              list.appendChild(emp);
            }
          }else if(emp){ emp.remove(); }
        }
      }

      var input = document.getElementById("searchUser");
      if(input){
        input.addEventListener("input", function(){ applyClientSearch(this.value); });
        // Если мы пришли на вкладку 9 с готовым запросом — сразу фильтруем
        if(input.value.trim()) setTimeout(function(){ applyClientSearch(input.value); }, 0);
      }

      // Переопределяем searchUser, чтобы НЕ открывалась новая модалка
      var origSearch = window.searchUser;
      window.searchUser = function(){
        var inp = document.getElementById("searchUser");
        if(!inp){ if (typeof origSearch==="function") origSearch(); return; }
        applyClientSearch(inp.value);
        // дополнительно, если у тебя есть серверная вкладка 9 — дерни её:
        try { if (typeof setTab === "function") { setTab(document.querySelector(".Trainers .Cat[data-id=\'9\']")||null, 9, inp.value); } } catch(e){}
      };

      // Сохраняем скролл списка между вкладками
      document.querySelectorAll(".Trainers .Cat").forEach(function(btn){
        btn.addEventListener("mousedown", function(){
          var list = document.querySelector(".Trainers .List");
          if(list) window.__trainersScrollTop = list.scrollTop;
        }, {capture:true});
      });
      setTimeout(function(){
        var list = document.querySelector(".Trainers .List");
        if(list && typeof window.__trainersScrollTop === "number"){ list.scrollTop = window.__trainersScrollTop; }
      }, 0);

      // Аккордеон для групп на мобилках
      var mq = window.matchMedia("(max-width:560px)");
      function initMobile(){
        if(!mq.matches) return;
        document.querySelectorAll(".Trainers .CatGroup").forEach(function(g, i){
          var t = g.querySelector(".CatGroupTitle");
          if(!t) return;
          if(i>0 && !g.classList.contains("inited")) g.classList.add("collapsed");
          t.setAttribute("role","button"); t.tabIndex = 0;
          var toggle = function(){ g.classList.toggle("collapsed"); };
          if(!g.classList.contains("inited")){
            t.addEventListener("click", toggle);
            t.addEventListener("keydown", function(e){ if(e.key==="Enter"||e.key===" "){ e.preventDefault(); toggle(); } });
            g.classList.add("inited");
          }
        });
      }
      initMobile();
      if(mq.addEventListener) mq.addEventListener("change", initMobile);
    })();
    </script>';

    $response["title"] = "Тренеры";
    $response["html"]  = $style . $tpl . $script;
    break;



 case 'clans':
    // Получаем список всех кланов, сортированных по рейтингу (убыв.)
    $clans = $mysqli->query("SELECT * FROM `base_clans` ORDER BY `rating` DESC");

    $i = 1;
    $clansList = [];

    ob_start();
    ?>
    <style>
      .clansWrap{display:flex;flex-direction:column;gap:12px}
      .clansHeader{display:flex;align-items:center;justify-content:space-between}
      .clansTitle{font-size:20px;font-weight:900;color:#4a46e0;letter-spacing:.2px}
      .clanGrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
      .clanEmpty{padding:22px;text-align:center;border:1px dashed #e3e6ff;border-radius:12px;color:#6b6b8a;background:#fafbff}

      .clanCard{background:#fff;border:1px solid #eceeff;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);padding:12px;display:flex;flex-direction:column;gap:10px;transition:.15s ease}
      .clanCard:hover{box-shadow:0 6px 20px rgba(0,0,0,.08);transform:translateY(-1px)}
      .clanTop{display:flex;align-items:center;gap:10px}
      .rankBadge{width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff}
      .rank-1{background:linear-gradient(180deg,#ffd700,#f6b400)}
      .rank-2{background:linear-gradient(180deg,#cfd8dc,#90a4ae)}
      .rank-3{background:linear-gradient(180deg,#d9a273,#b87333)}
      .rank-default{background:#f1f3ff;color:#6b6b8a;border:1px solid #e3e6ff}
      .clanMain{flex:1 1 auto;min-width:0}
      .clanName{font-weight:900;color:#2f2e6a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
      .clanMeta{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
      .meta{display:inline-flex;gap:6px;align-items:center;font-size:12px;color:#4a4a9b;background:#fff;border:1px solid #e3e6ff;border-radius:999px;padding:3px 8px}
      .rating{color:#ad6b00;border-color:#ffe3b3;background:#fff9f0}
      .clanBar{display:flex;flex-direction:column;gap:6px}
      .progress{height:10px;border-radius:999px;background:#f2f3ff;border:1px solid #eceeff;overflow:hidden}
      .progress > div{height:100%;background:linear-gradient(90deg,#7d7cf8,#1bbb70)}
      .lvlRow{display:flex;justify-content:space-between;font-size:12px;color:#6b6b8a}
    </style>
    <div class="clansWrap">
      <div class="clansHeader">
        <div class="clansTitle"><i class="fa fa-users" style="color:#7d7cf8"></i> Рейтинг кланов</div>
      </div>

      <?php if ($clans->num_rows < 1): ?>
        <div class="clanEmpty"><i class="fa fa-box-open"></i> Кланов пока нет</div>
      <?php else: ?>
        <div class="clanGrid">
          <?php
          while ($clan = $clans->fetch_assoc()) {
              // Обновляем позицию (место в рейтинге)
              $mysqli->query('UPDATE `base_clans` SET `position` = '.$i.' WHERE `id` = '.(int)$clan['id']);

              // JSON с информацией
              $infoObj = json_decode($clan['info'] ?: '{}', true);
              $name = htmlspecialchars((string)($infoObj['name'] ?? 'Клан'), ENT_QUOTES, 'UTF-8');

              // Поля
              $rating   = (int)$clan['rating'];
              $users    = (int)$clan['users_clan'];
              $level    = isset($clan['level']) ? (int)$clan['level'] : (isset($clan['lvl']) ? (int)$clan['lvl'] : 1);
              $exp      = isset($clan['exp']) ? (int)$clan['exp'] : 0;
              $exp_next = isset($clan['exp_next']) ? (int)$clan['exp_next'] : 1000;
              $pos      = (int)$i;

              $progress = $exp_next > 0 ? max(0, min(100, round($exp / $exp_next * 100, 1))) : 0;

              // Бейдж позиции
              $rankClass = $pos === 1 ? 'rank-1' : ($pos === 2 ? 'rank-2' : ($pos === 3 ? 'rank-3' : 'rank-default'));

              // Список для ответа (API)
              $clansList[] = [
                  'id'         => (int)$clan['id'],
                  'name'       => $infoObj['name'] ?? 'Клан',
                  'rating'     => $rating,
                  'users_cool' => $users,
                  'level'      => $level,
                  'exp'        => $exp,
                  'exp_next'   => $exp_next,
                  'position'   => $pos,
              ];
          ?>
            <div class="clanCard" data-clan-id="<?= (int)$clan['id'] ?>">
              <div class="clanTop">
                <div class="rankBadge <?= $rankClass ?>" title="Место: <?= $pos ?>">
                  <?= $pos ?>
                </div>
                <div class="clanMain">
                  <div class="clanName"><?= $name ?></div>
                  <div class="clanMeta">
                    <span class="meta rating" title="Рейтинг"><i class="fa fa-star"></i> <?= $rating ?></span>
                    <span class="meta" title="Участники"><i class="fa fa-user-friends"></i> <?= $users ?></span>
                    <span class="meta" title="Уровень клана"><i class="fa fa-signal"></i> Lv <?= $level ?></span>
                  </div>
                </div>
              </div>
              <div class="clanBar">
                <div class="progress" title="Опыт: <?= $exp ?> / <?= $exp_next ?>">
                  <div style="width: <?= $progress ?>%"></div>
                </div>
                <div class="lvlRow">
                  <span>Опыт</span>
                  <span><?= $exp ?> / <?= $exp_next ?> (<?= $progress ?>%)</span>
                </div>
              </div>
            </div>
          <?php
              $i++;
          } // while
          ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
    $response['html'] = ob_get_clean();
    $response['clansList'] = $clansList;
    break;
    case 'diary':
		switch($_POST['category']){
		    case 'mission':
				$a = '<div class="MissionBlock" onclick="mission(1)" ><div class="NumbMission"><i class="fas fa-stop-circle"></i> 1</div> <span class="NameMission">Красота окраса</div><div class="info" id="mission1" style="display: none;"></div></div>
				<div class="MissionBlock" onclick="mission(2)"><div class="NumbMission"><i class="fas fa-stop-circle"></i> 2</div> <span class="NameMission">Очаровательный Тайлоу</div><div class="info"  id="mission2" style="display: none;"></div></div>
				<div class="MissionBlock" onclick="mission(3)"><div class="NumbMission"><i class="fas fa-stop-circle"></i> 3</div> <span class="NameMission">Игра в мяч</div><div class="info"  id="mission3" style="display: none;"></div></div>
				<div class="MissionBlock" onclick="mission(4)"><div class="NumbMission"><i class="fas fa-stop-circle"></i> 4</div> <span class="NameMission">Надоедливый Бидуф</div><div class="info"  id="mission4" style="display: none;"></div></div>
				<div class="MissionBlock" onclick="mission(5)"><div class="NumbMission"><i class="fas fa-stop-circle"></i> 5</div> <span class="NameMission">Цветет или нет?</div><div class="info"  id="mission5" style="display: none;"></div></div>
				';
				$response['missionList'] = $a;
			break;
// Предполагается, что $mysqli уже подключен и $_SESSION['id'] определён.
case 'quests':
    $questList = [];
    $user_id = intval($_SESSION['id']);

    // Получаем все активные квесты
    $questQuery = $mysqli->query("SELECT `id` FROM `base_quest` WHERE `ready` = 1");

    // Подготовим запрос для user_quests, чтобы избежать SQL-инъекций
    $stmt = $mysqli->prepare("SELECT `end` FROM `user_quests` WHERE `quest_id` = ? AND `user_id` = ?");
    
    while ($quest = $questQuery->fetch_assoc()) {
        $quest_id = (int)$quest['id'];
        $checkq = 0;

        // Безопасный запрос к user_quests
        $stmt->bind_param('ii', $quest_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($end);
        if ($stmt->fetch()) {
            $checkq = ($end == 1) ? 1 : 2;
        }
        $stmt->free_result();

        $questList[$quest_id] = [
            'id' => $quest_id,
            'name' => quest_info($quest_id, 'name'),
            'check' => $checkq,
            'exp' => quest_info($quest_id, 'exp'),
            'location' => quest_info($quest_id, 'location'),
            'lvl' => quest_info($quest_id, 'lvl'),
            'time' => quest_info($quest_id, 'time'),
            'class_lvl' => quest_info($quest_id, 'class_lvl'),
            'img' => "/img/quests/$quest_id.png"
        ];
    }
    $stmt->close();

    $response['questList'] = $questList;
    break;
 case 'questsList':
{
    $questList = [];
    $questID = isset($_POST['pokID']) ? (int)clearInt($_POST['pokID']) : 1;
    $userId  = (int)$_SESSION['id'];

    /* 1) Шаги пользователя по квесту, по порядку */
    $stmt = $mysqli->prepare(
        'SELECT `id`,`quest_step`,`text`
           FROM `quest_steps`
          WHERE `quest_id` = ? AND `id_user` = ?
          ORDER BY `quest_step` ASC, `id` ASC'
    );
    $stmt->bind_param('ii', $questID, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $questList[(int)$row['id']] = [
            'step' => (int)$row['quest_step'],
            'text' => $row['text'],
        ];
    }
    $stmt->close();

    /* 2) Карточка квеста */
    $stmt = $mysqli->prepare('SELECT `name`,`about` FROM `base_quest` WHERE `id` = ? LIMIT 1');
    $stmt->bind_param('i', $questID);
    $stmt->execute();
    $questB = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    /* 3) Сведения (meta) */
    $response['meta'] = [
        'exp'        => quest_info($questID, 'exp'),
        'location'   => quest_info($questID, 'location'),
        'time'       => quest_info($questID, 'time'),
        'difficulty' => quest_info($questID, 'lvl'),
    ];

    /* 4) Награды: исходные строки для квестов (как в questChest) */
    $rewardsMap = [
        1  => ['Стартовый покемон на выбор', 'Генкар ×10000', 'Покебол ×15', 'Опыт ×500'],
        2  => ['Рандомные осколки камней ×5', 'Опыт ×500'],
        3  => ['Рандомный стабовый усилитель ×2', 'Опыт ×500'],
        4  => ['Желтая конфета ×10', 'Фиолетовая конфета ×10', 'Опыт ×500'],
        5  => ['Портативный инкубатор ×3', 'Серебряная пыль ×1', 'Генкар ×100000', 'Опыт ×800'],
        6  => ['Бриллиантовый покебол ×5', 'Коробка с окаменелостями ×1', 'Загадочный покебол ×1', 'Монета ×100000', 'Опыт ×1500'],
        7  => ['Коробка с окаменелостями ×1', 'Опыт ×500'],
        8  => ['Опыт ×800'],
        9  => ['Опыт ×700'],
        10 => ['Корень априкорнов ×1', 'Обучение мега-эволюции', 'Рандомный мега-камень ×1', 'Опыт ×700'],
        11 => ['Обучение крафту пилюли', 'Капсула ×6', 'Лунный камень ×1', 'Опыт ×500'],
        12 => ['Билет на корабль Калос-Хоенн ×1', 'Монета ×150000', 'Поглощающая лампа ×1', 'Приманка ×1', 'Опыт ×800'],
        15 => ['Спиннинг ×1', 'Опыт ×700'],
        33 => ['Монета ×50000', 'Защитные очки ×1', 'Амулет ×1', 'Старая удочка ×1', 'Опыт ×1000'],
        41 => ['Монета ×100000', 'Сладкий кекс ×2', 'Опыт ×600'],
    ];

    /* 4.1) Явные сопоставления "название → item_id" (расширяй по мере надобности) */
    $nameToItem = [
        'Генкар'               => 1,
        'Лунный камень'          => 84,   // пример; подставь свой id, если отличается
        'Покебол'                => 2,    // если другой id — поправь
        'Капсула'                => 366,  // пример
        'Желтая конфета'         => 1006, // пример
        'Фиолетовая конфета'     => 1007, // пример
        'Портативный инкубатор'  => 1234, // пример
        'Серебряная пыль'        => 1102, // пример
        'Коробка с окаменелостями' => 1401, // пример
        'Загадочный покебол'     => 2005, // пример
        'Бриллиантовый покебол'  => 2006, // пример
        'Корень априкорнов'      => 1501, // пример
        'Рандомный мега-камень'  => 1600, // пример
        'Приманка'               => 187,  // у тебя уже есть 187 для приманок
        'Старая удочка'          => 5, // пример
        'Защитные очки'          => 3010, // пример
        'Амулет'                 => 3020, // пример
        'Спиннинг'               => 6, // пример
        'Поглощающая лампа'      => 1701, // пример
        // добавляй дальше по необходимости
    ];

    /* 4.2) Функция: преобразуем строки наград в структурированный массив */
    $rewards = [];
    $rawList = $rewardsMap[$questID] ?? [];

    foreach ($rawList as $raw) {
        if (is_array($raw)) { // на случай, если уже передан структурированный объект
            $rewards[] = $raw;
            continue;
        }
        $text  = trim($raw);
        $count = null;

        // парсим "Название ×123" (×, x, х)
        if (preg_match('~(.+?)\s*[xх×]\s*([\d\s\.,]+)$~u', $text, $m)) {
            $label = trim($m[1]);
            $count = trim($m[2]);
        } else {
            $label = $text;
        }

        $item_id = $nameToItem[$label] ?? null;

        // если id не задан в карте — пробуем найти в базе по точному имени
        if (!$item_id) {
            $stmt = $mysqli->prepare('SELECT `id` FROM `base_items` WHERE `name` = ? LIMIT 1');
            $stmt->bind_param('s', $label);
            if ($stmt->execute()) {
                $row = $stmt->get_result()->fetch_assoc();
                if ($row) $item_id = (int)$row['id'];
            }
            $stmt->close();
        }

        if ($item_id) {
            $rewards[] = [
                'type'    => 'item',
                'item_id' => $item_id,
                'text'    => $label,
                'count'   => $count,
            ];
        } else {
            // иконки по смыслу
            $icon = null;
            if (mb_stripos($label,'опыт') !== false)           $icon = 'star';
            elseif (mb_stripos($label,'монет') !== false
                 || mb_stripos($label,'монета') !== false)     $icon = 'coin';
            elseif (mb_stripos($label,'обучен') !== false)      $icon = 'trophy';
            $rewards[] = [
                'icon'  => $icon ?: 'gift',
                'text'  => $label,
                'count' => $count,
            ];
        }
    }

    /* 5) Ответ */
    $response['rewards']   = $rewards;                 // ← теперь фронт сможет показать картинки и клик
    $response['questList'] = $questList;
    $response['progress']  = quest_isset_book($questID);
    $response['id']        = $questID;
    $response['name']      = $questB['name']  ?? 'Квест';
    $response['about']     = $questB['about'] ?? '';
    $response['img']       = "/img/quests/{$questID}.png";
    break;
}

    
		case 'news':
    $dt = time() - 3600 * 24 * 3;
    $newsA = $mysqli->query('SELECT * FROM `friends_news` WHERE `date` > '.$dt.' ORDER BY `id` DESC');

    $u = 0;
    $i = 0;
    $openedGroup = false;
    $newsBook = '';

    $newsBook .= '
    <style>
      .newsWrap {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: 100%;
      }
      .New {
        display: grid;
        grid-template-columns: 40px 1fr;
        gap: 8px;
        background: #fff;
        border: 1px solid #eceeff;
        border-radius: 10px;
        padding: 10px;
      }
      .user-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f4f6ff;
        border: 1px solid #eceeff;
        background-size: cover;
        background-position: center;
        cursor: pointer;
      }
      .news_trainer {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0; /* Prevents content overflow */
      }
      .user-link {
        display: flex;
        align-items: center;
        gap: 6px;
      }
      .newsItem {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 8px;
        border: 1px solid #eceeff;
        background: #fafbff;
        border-radius: 8px;
      }
      .newsItem img {
        max-width: 40px;
        height: 40px;
        object-fit: contain;
        border-radius: 6px;
      }
      .newsContent {
        flex: 1;
        min-width: 0;
      }
      .TimeBook {
        font-size: 11px;
        color: #8a8ab3;
        margin-top: 4px;
      }
      @media(max-width: 480px) {
        .New { 
          grid-template-columns: 32px 1fr;
          padding: 8px;
        }
        .user-icon {
          width: 32px;
          height: 32px;
        }
        .newsItem img {
          max-width: 32px;
          height: 32px;
        }
      }
    </style>
    <div class="newsWrap">
    ';

    while ($news = $newsA->fetch_assoc()) {
        $friend = $mysqli->query("SELECT * FROM `users_friend` WHERE (`user_id` = '".$_SESSION['id']."' AND `friend_id` = '".$news['user']."') OR (`user_id` = '".$news['user']."' AND `friend_id` = '".$_SESSION['id']."') AND `status` = 1");
        if ($friend->num_rows > 0 || $news['user'] == $_SESSION['id']) {

            $us = $mysqli->query("SELECT `id`,`login`,`sex`,`user_group` FROM users WHERE id = ".$news['user'])->fetch_assoc();
            if (!$us) continue;

            $uid = (int)$us['id'];
            $ulogin = htmlspecialchars((string)$us['login'], ENT_QUOTES, 'UTF-8');
            $ugroup = (int)$us['user_group'];
            $timeStr = date('H:i d.m', (int)$news['date']);
            
            // Prepare news content - combine text and image if exists
            $newsContent = '';
            if (!empty($news['image'])) {
                $newsContent .= '<img src="'.$news['image'].'" alt="" loading="lazy">';
            }
            $newsContent .= '<div class="newsContent">'.$news['text'].'<div class="TimeBook">'.$timeStr.'</div></div>';

            if ($u == 0) {
                $newsBook .= '
                    <div class="New">
                        <div class="user-icon" style="background-image: url(/img/avatars/mini/'.$uid.'.png);" onclick="showUserTooltip('.$uid.')"></div>
                        <div class="news_trainer">
                            <div class="user-link">
                                <div class="u-'.$ugroup.' label" onclick="user_to_chat_add('.$uid.')">'.$ulogin.'</div>
                            </div>
                            <div class="newsItem">
                                '.$newsContent.'
                            </div>
                ';
                $openedGroup = true;
            } else {
                if ($us['id'] == $u) {
                    $newsBook .= '
                            <div class="newsItem">
                                '.$newsContent.'
                            </div>
                    ';
                } else {
                    $newsBook .= '
                        </div>
                    </div>
                    <div class="New">
                        <div class="user-icon" style="background-image: url(/img/avatars/mini/'.$uid.'.png);" onclick="showUserTooltip('.$uid.')"></div>
                        <div class="news_trainer">
                            <div class="user-link">
                                <div class="u-'.$ugroup.' label" onclick="user_to_chat_add('.$uid.')">'.$ulogin.'</div>
                            </div>
                            <div class="newsItem">
                                '.$newsContent.'
                            </div>
                    ';
                    $openedGroup = true;
                }
            }

            $u = $us['id'];
            $i++;
            if ($i >= 100) break;
        }
    }

    if ($openedGroup) {
        $newsBook .= '
                        </div>
                    </div>
        ';
    } else {
        $newsBook .= '
            <div class="newsItem" style="text-align:center;border-radius:10px;background:#fff;border:1px dashed #e3e6ff;">
                <div style="font-size:13px;color:#6b6b8a;padding:12px 8px;">
                    <i class="fa fa-box-open" style="color:#7d7cf8"></i> Нет новых событий за последние 3 дня
                </div>
            </div>
        ';
    }

    $newsBook .= '</div>';
    
    $response['newsBook'] = $newsBook;
    break;
				case 'location':
    // Градация шанса
    if (!function_exists('gradation_chance')) {
        function gradation_chance($chance, $only_text = false) {
            $chance = floatval($chance);
            if ($chance >= 80) return $only_text ? "очень часто" : "<span class='chance-text chance-very-often'>очень часто</span>";
            if ($chance >= 50) return $only_text ? "часто" : "<span class='chance-text chance-often'>часто</span>";
            if ($chance >= 20) return $only_text ? "редко" : "<span class='chance-text chance-rare'>редко</span>";
            if ($chance >= 5)  return $only_text ? "очень редко" : "<span class='chance-text chance-very-rare'>очень редко</span>";
            if ($chance >= 1)  return $only_text ? "крайне редко" : "<span class='chance-text chance-extreme-rare'>крайне редко</span>";
            return $only_text ? "?" : "<span class='chance-text chance-unknown'>?</span>";
        }
    }

    // Формат уровней: "7 ур." или "7—14 ур."
    if (!function_exists('format_level_label')) {
        function format_level_label($lvlStr, $suffix = ' ур.') {
            $lvlStr = (string)$lvlStr;
            $parts = preg_split('/\s*[,\-–—]\s*/u', $lvlStr, -1, PREG_SPLIT_NO_EMPTY);
            $levels = array_values(array_filter(array_map('intval', $parts), static function ($v) { return $v >= 0; }));
            if (count($levels) === 0) return '?' . $suffix;
            if (count($levels) === 1) return $levels[0] . $suffix;
            $min = min($levels);
            $max = max($levels);
            return ($min === $max) ? ($min . $suffix) : ($min . '—' . $max . $suffix);
        }
    }

    // Определение ID предмета по тексту условий поимки
    if (!function_exists('detect_catch_item_id')) {
        function detect_catch_item_id(mysqli $mysqli, $conditionText) {
            static $cacheByText = [];
            $key = trim((string)$conditionText);
            if ($key === '') return 0;
            if (isset($cacheByText[$key])) return $cacheByText[$key];

            $text = (string)$conditionText;

            if (preg_match('~\/img\/world\/items\/(?:little|big)\/(\d+)\.png~i', $text, $m)) {
                return $cacheByText[$key] = (int)$m[1];
            }
            if (preg_match('~\[item[:=](\d+)\]~i', $text, $m)) {
                return $cacheByText[$key] = (int)$m[1];
            }
            $nameCandidate = trim(strip_tags($text));
            if ($nameCandidate !== '' && mb_strlen($nameCandidate, 'UTF-8') <= 40) {
                $nameEsc = $mysqli->real_escape_string($nameCandidate);
                $res = $mysqli->query("SELECT `id` FROM `base_items` WHERE `name` = '{$nameEsc}' LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) {
                    return $cacheByText[$key] = (int)$row['id'];
                }
            }
            return $cacheByText[$key] = 0;
        }
    }

    $pokList = [];
    $user = $mysqli->query('SELECT * FROM `users` WHERE `id` = ' . (int)$_SESSION['id'])->fetch_assoc();
    $q = $mysqli->query('SELECT * FROM `base_location` WHERE `id` = ' . (int)$user['location'])->fetch_assoc();
    $g = $mysqli->query('SELECT * FROM `base_region` WHERE `id` = ' . (int)$q['region'])->fetch_assoc();

    if($q['weather'] != 0){
        $w = (int)$q['weather'];
        $t = '';
    }else{
        $w = (int)$g['weather'];
        $t = '<br>Смена погоды в ' . date("H:i", (int)$g['weather_time']);
    }

    if($w == 1) $n = 'Обычная';
    elseif($w == 2) $n = 'Солнечно';
    elseif($w == 3) $n = 'Дождь';
    elseif($w == 4) $n = 'Град';
    elseif($w == 5) $n = 'Песчаная буря';
    else $n = 'Неизвестная погода';

    // Обновлённые стили: условия поимки теперь в общем ряду бейджей, авто-перенос и тримминг
    $tpl = '
    <style>
      .PokemonBook{overflow:hidden} /* на всякий случай, чтобы ничего не вылазило */
      .pkMeta{
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:8px;
        row-gap:8px;
        min-width:0;
        margin-top:6px
      }
      .pkBadge{
        display:inline-flex;
        gap:6px;
        align-items:center;
        font-size:12px;
        color:#444;
        background:#fff;
        border:1px solid #e3e6ff;
        border-radius:20px;
        padding:4px 10px;
        white-space:nowrap;
        max-width:100%;
      }
      .pkBadge i{color:#7d7cf8}
      .PokemonBook.noCatch{opacity:.7}
      .PokemonBook b{color:#2b2b6a}
      .chance-text{font-weight:700}
      .chance-very-often{color:#1b7a29}
      .chance-often{color:#1a5aa8}
      .chance-rare{color:#a86c00}
      .chance-very-rare{color:#7b1aa8}
      .chance-extreme-rare{color:#a83a1a}
      .chance-unknown{color:#8a8ab3}

      /* Бейдж условий поимки — участвует в общем потоке бейджей,
         но стремится к правому краю первой строки. Если не поместится —
         переносится на следующую строку без наложений. */
      .pkCatch{
        display:inline-flex;
        align-items:center;
        gap:8px;
        border:1px solid #e3e6ff;
        background:#fff;
        border-radius:20px;
        padding:4px 10px;
        min-width:0;
        max-width: clamp(220px, 40vw, 420px);
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        margin-left:auto; /* уводим вправо в текущей строке, если хватает места */
        order: 99;        /* и в любом случае делаем её последней в ряду */
      }
      .pkCatch img{
        width:20px;height:20px;object-fit:contain;
        border-radius:6px;border:1px solid #eceeff;background:#fff;flex:0 0 auto
      }
      .pkCatch .pkCatchText{
        font-size:13px;color:#5c5c8a;min-width:0;overflow:hidden;text-overflow:ellipsis
      }

      /* На узких экранах убираем "прилипание" вправо, даем 100% ширины */
      @media (max-width: 600px){
        .pkCatch{margin-left:0;max-width:100%;order:0}
      }
    </style>';

    $tpl .= '
    <div class="h2">Вы в регионе ' . htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8') . '</div>
    <img src="/img/weather/' . $w . '.png" alt="">
    <span>' . $n . '</span>' . $t;

    // ===== Дикие на текущей локации =====
    $a = $mysqli->query('SELECT * FROM pokemons_location WHERE hide_loc != 1 AND location_id = ' . (int)$user['location'] . ' ORDER BY basenum ASC');

    if ((int)$q['pve'] != 0) {
        $tpl .= '<details class="tree-nav__item is-expandable">
            <summary class="tree-nav__item-title">Дикие покемоны на локации</summary>
            <div class="LocBook">';

        while ($pok = $a->fetch_assoc()) {
            $b_pok = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = ' . (int)$pok['basenum'])->fetch_assoc();
            $catchClass = ((int)$pok['catch'] == 0) ? 'noCatch' : '';

            // Время
            $time_from = trim((string)$pok['timezone']);
            $time_to   = trim((string)$pok['timezone_b']);
            $time_text = ($time_from === '' || $time_to === '' || ($time_from === '00:00' && ($time_to === '23:59' || $time_to === '24:00')))
                ? 'Круглосуточно'
                : ($time_from . ' — ' . $time_to);

            // Шанс
            $chance = isset($pok['chance']) ? (float)$pok['chance'] : 0.0;
            $chance_disp = ($chance > 0 && $chance < 1) ? number_format($chance, 2, '.', '') : (int)round($chance);
            $chance_html = gradation_chance($chance, false);

            // Уровень
            $lvl_label = format_level_label($pok['lvl']);

            // Условия поимки
            $catch_text_raw   = (string)($pok['text_catch_condition'] ?? '');
            $catch_text_clean = trim(strip_tags($catch_text_raw));
            $catch_text       = htmlspecialchars($catch_text_clean, ENT_QUOTES, 'UTF-8');
            $catch_item_id    = $catch_text !== '' ? detect_catch_item_id($mysqli, $catch_text_raw) : 0;

            $tpl .= '
                <div class="PokemonBook ' . $catchClass . '">
                    <img src="/img/pokemons/animation/' . numbPok((int)$b_pok['id']) . '.png" onclick="openDex(' . (int)$b_pok['id'] . ')"> 
                    <b>#' . numbPok((int)$b_pok['id']) . ' ' . htmlspecialchars($b_pok['name_rus'], ENT_QUOTES, 'UTF-8') . '</b> ' . $lvl_label . '
                    <div class="pkMeta">
                        <span class="pkBadge" title="Время появления: ' . $time_from . ' - ' . $time_to . '"><i class="far fa-clock"></i> ' . $time_text . '</span>
                        <span class="pkBadge" title="Шанс появления: ~' . $chance_disp . '%"><i class="fas fa-star"></i> ' . $chance_html . ' (≈' . $chance_disp . '%)</span>' .
                        (
                            $catch_text !== ''
                                ? ('<span class="pkCatch" title="' . htmlspecialchars($catch_text_clean, ENT_QUOTES, 'UTF-8') . '">' .
                                    ($catch_item_id > 0
                                        ? '<img src="/img/world/items/little/' . (int)$catch_item_id . '.png" alt="" onerror="this.src=\'/img/world/items/little/undefined.png\'">'
                                        : '<img src="/img/world/items/little/undefined.png" alt="">'
                                    ) .
                                    '<span class="pkCatchText">' . $catch_text . '</span>' .
                                   '</span>')
                                : ''
                        ) . '
                    </div>' .
                    (!empty($pok['text_drop']) ? ('<div class="drop">' . $pok['text_drop'] . '</div>') : '') . 
                '</div>';
        }
        $tpl .= '</div></details>';
    }

    // ===== Дикие по региону =====
    $a = $mysqli->query('
        SELECT pl.*, bp.name_rus AS pokemon_name, bp.id AS base_id, bl.name AS location_name, bl.region AS location_region
        FROM pokemons_location pl
        JOIN base_pokemons bp ON bp.id = pl.basenum
        JOIN base_location bl ON bl.id = pl.location_id
        WHERE pl.hide_loc != 1 
          AND pl.location_id != 0 
          AND bl.region = ' . (int)$user['region'] . '
        ORDER BY pl.location_id ASC, pl.basenum ASC
    ');

    $locations = [];
    while ($pok = $a->fetch_assoc()) {
        $locations[$pok['location_id']]['name'] = $pok['location_name'];
        $locations[$pok['location_id']]['pokemons'][] = $pok;
    }

    $tpl .= '<details class="tree-nav__item is-expandable">
        <summary class="tree-nav__item-title">Дикие покемоны региона</summary>
        <div class="LocBook">';

    foreach ($locations as $location_id => $location) {
        $tpl .= '<details class="tree-nav__item is-expandable">
            <summary class="tree-nav__item-title">' . htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') . '</summary>
            <div class="LocBook">';

        foreach ($location['pokemons'] as $pok) {
            $catchClass = ((int)$pok['catch'] == 0) ? 'noCatch' : '';

            $time_from = trim((string)$pok['timezone']);
            $time_to   = trim((string)$pok['timezone_b']);
            $time_text = ($time_from === '' || $time_to === '' || ($time_from === '00:00' && ($time_to === '23:59' || $time_to === '24:00')))
                ? 'Круглосуточно'
                : ($time_from . ' — ' . $time_to);

            $chance = isset($pok['chance']) ? (float)$pok['chance'] : 0.0;
            $chance_disp = ($chance > 0 && $chance < 1) ? number_format($chance, 2, '.', '') : (int)round($chance);
            $chance_html = gradation_chance($chance, false);

            $lvl_label = format_level_label($pok['lvl']);

            $catch_text_raw   = (string)($pok['text_catch_condition'] ?? '');
            $catch_text_clean = trim(strip_tags($catch_text_raw));
            $catch_text       = htmlspecialchars($catch_text_clean, ENT_QUOTES, 'UTF-8');
            $catch_item_id    = $catch_text !== '' ? detect_catch_item_id($mysqli, $catch_text_raw) : 0;

            $tpl .= '
                <div class="PokemonBook ' . $catchClass . '">
                    <img src="/img/pokemons/animation/' . numbPok((int)$pok['base_id']) . '.png" onclick="openDex(' . (int)$pok['base_id'] . ')"> 
                    <b>#' . numbPok((int)$pok['base_id']) . ' ' . htmlspecialchars($pok['pokemon_name'], ENT_QUOTES, 'UTF-8') . '</b> ' . $lvl_label . '
                    <div class="pkMeta">
                        <span class="pkBadge" title="Время появления: ' . $time_from . ' - ' . $time_to . '"><i class="far fa-clock"></i> ' . $time_text . '</span>
                        <span class="pkBadge" title="Шанс появления: ~' . $chance_disp . '%"><i class="fas fa-star"></i> ' . $chance_html . ' (≈' . $chance_disp . '%)</span>' .
                        (
                            $catch_text !== ''
                                ? ('<span class="pkCatch" title="' . htmlspecialchars($catch_text_clean, ENT_QUOTES, 'UTF-8') . '">' .
                                    ($catch_item_id > 0
                                        ? '<img src="/img/world/items/little/' . (int)$catch_item_id . '.png" alt="" onerror="this.src=\'/img/world/items/little/undefined.png\'">'
                                        : '<img src="/img/world/items/little/undefined.png" alt="">'
                                    ) .
                                    '<span class="pkCatchText">' . $catch_text . '</span>' .
                                   '</span>')
                                : ''
                        ) . '
                    </div>' .
                    (!empty($pok['text_drop']) ? ('<div class="drop">' . $pok['text_drop'] . '</div>') : '') . 
                '</div>';
        }

        $tpl .= '</div></details>';
    }

    $tpl .= '</div></details>';

// ===== Дроп на локации (обычный) =====
$n_d = $mysqli->query('SELECT * FROM `base_drop_pokemons` WHERE `quest_id` = 0 AND `location_id` > 0 AND `is_active` = 1 ORDER BY `item_id` DESC, `priority` DESC');
$tpl .= '<details class="tree-nav__item is-expandable">
            <summary class="tree-nav__item-title">Дроп на локации</summary>
            <div class="listDrop">';
$i = 0;
while ($not = $n_d->fetch_assoc()) {
    // Получаем информацию о покемоне и локации
    $p = null;
    $l = null;
    
    if ($not['pokemon_num'] > 0) {
        $p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = ' . (int)$not['pokemon_num'])->fetch_assoc();
    }
    if ($not['location_id'] > 0) {
        $l = $mysqli->query('SELECT * FROM `base_location` WHERE `id` = ' . (int)$not['location_id'])->fetch_assoc();
    }
    
    if ($i == $not['item_id']) {
        if ($p && $l) {
            $tpl .= '<img onclick="openDex(' . (int)$p['id'] . ')" class="cl" src="/img/pokemons/animation/' . (int)$p['id'] . '.png"> #' . numbPok((int)$p['id']) . ' ' . htmlspecialchars($p['name_rus'], ENT_QUOTES, 'UTF-8') . ' на ' . htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8') . ' (1/' . (int)$not['drop_chance'] . ')<br>';
        }
    } else {
        if ($i != 0) {
            $tpl .= '</span></div>';
        }
        $b = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = ' . (int)$not['item_id'])->fetch_assoc();

        $tpl .= '<div class="dropBlock">
            <img onclick="issetAll(' . (int)$not['item_id'] . ')"  class="cl" src="/img/world/items/little/' . (int)$not['item_id'] . '.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> ' . htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') . '
            <span class="loc">';
        
        if ($p && $l) {
            $tpl .= ' <img onclick="openDex(' . (int)$p['id'] . ')" class="cl" src="/img/pokemons/animation/' . (int)$p['id'] . '.png"> #' . numbPok((int)$p['id']) . ' ' . htmlspecialchars($p['name_rus'], ENT_QUOTES, 'UTF-8') . ' на ' . htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8') . ' (1/' . (int)$not['drop_chance'] . ') <br>';
        }
    }
    $i = (int)$not['item_id'];
}
if ($i != 0) {
    $tpl .= '</span></div>';
}
$tpl .= '</div></details>';

// ===== Квестовый дроп =====
$quest_drops = $mysqli->query('SELECT * FROM `base_drop_pokemons` WHERE `quest_id` > 0 AND `is_active` = 1 ORDER BY `quest_id` ASC, `item_id` DESC, `priority` DESC');
if ($quest_drops && $quest_drops->num_rows > 0) {
    $tpl .= '<details class="tree-nav__item is-expandable">
                <summary class="tree-nav__item-title">Квестовый дроп</summary>
                <div class="listDrop">';
    
    $current_quest = 0;
    $i = 0;
    while ($not = $quest_drops->fetch_assoc()) {
        // Если новый квест - добавляем заголовок
        if ($current_quest != $not['quest_id']) {
            if ($current_quest != 0) {
                if ($i != 0) {
                    $tpl .= '</span></div>';
                }
            }
            $current_quest = $not['quest_id'];
            $quest_info = $mysqli->query('SELECT * FROM `base_quest` WHERE `id` = ' . (int)$not['quest_id'])->fetch_assoc();
            $quest_name = $quest_info ? htmlspecialchars($quest_info['name'], ENT_QUOTES, 'UTF-8') : 'Квест #' . (int)$not['quest_id'];
            $tpl .= '<div class="questDropHeader"><strong>' . $quest_name . '</strong></div>';
            $i = 0;
        }
        
        // Получаем информацию о покемоне и локации
        $p = null;
        $l = null;
        
        if ($not['pokemon_num'] > 0) {
            $p = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = ' . (int)$not['pokemon_num'])->fetch_assoc();
        }
        if ($not['location_id'] > 0) {
            $l = $mysqli->query('SELECT * FROM `base_location` WHERE `id` = ' . (int)$not['location_id'])->fetch_assoc();
        }
        
        if ($i == $not['item_id']) {
            if ($p) {
                $locationText = ($l) ? ' на ' . htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8') : '';
                $limitText = '';
                if ($not['limit_item_id'] > 0 && $not['limit_count'] > 0) {
                    $limitText = ' (макс. ' . (int)$not['limit_count'] . ' шт.)';
                }
                $tpl .= '<img onclick="openDex(' . (int)$p['id'] . ')" class="cl" src="/img/pokemons/animation/' . (int)$p['id'] . '.png"> #' . numbPok((int)$p['id']) . ' ' . htmlspecialchars($p['name_rus'], ENT_QUOTES, 'UTF-8') . $locationText . ' (1/' . (int)$not['drop_chance'] . ')' . $limitText . '<br>';
            }
        } else {
            if ($i != 0) {
                $tpl .= '</span></div>';
            }
            $b = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = ' . (int)$not['item_id'])->fetch_assoc();

            $locationText = '';
            $limitText = '';
            if ($p) {
                $locationText = ($l) ? ' на ' . htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8') : '';
            }
            if ($not['limit_item_id'] > 0 && $not['limit_count'] > 0) {
                $limitText = ' (макс. ' . (int)$not['limit_count'] . ' шт.)';
            }
            
            $tpl .= '<div class="dropBlock">
                <img onclick="issetAll(' . (int)$not['item_id'] . ')"  class="cl" src="/img/world/items/little/' . (int)$not['item_id'] . '.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> ' . htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') . '
                <span class="loc">';
            
            if ($p) {
                $tpl .= ' <img onclick="openDex(' . (int)$p['id'] . ')" class="cl" src="/img/pokemons/animation/' . (int)$p['id'] . '.png"> #' . numbPok((int)$p['id']) . ' ' . htmlspecialchars($p['name_rus'], ENT_QUOTES, 'UTF-8') . $locationText . ' (1/' . (int)$not['drop_chance'] . ')' . $limitText . ' <br>';
            } else {
                $tpl .= ' Квестовый предмет (1/' . (int)$not['drop_chance'] . ')' . $limitText . ' <br>';
            }
        }
        $i = (int)$not['item_id'];
    }
    
    if ($i != 0) {
        $tpl .= '</span></div>';
    }
    $tpl .= '</div></details>';
}

// ===== Дроп со всех покемонов (универсальный) =====
$universal_drops = $mysqli->query('SELECT * FROM `base_drop_pokemons` WHERE `quest_id` = 0 AND `location_id` = 0 AND `pokemon_num` = 0 AND `is_active` = 1 ORDER BY `item_id` DESC, `priority` DESC');
if ($universal_drops && $universal_drops->num_rows > 0) {
    $tpl .= '<details class="tree-nav__item is-expandable">
                <summary class="tree-nav__item-title">Дроп со всех покемонов</summary>
                <div class="listDrop">';
    
    $i = 0;
    while ($not = $universal_drops->fetch_assoc()) {
        if ($i == $not['item_id']) {
            // Для одинаковых предметов - просто добавляем новую строку
            $dropDescription = 'Со всех диких покемонов';
            
            // Проверяем дроп по типу покемона (используем строковые типы из base_pokemons)
            if (!empty($not['pokemon_type_id'])) {
                $typeNames = [
                    'fire' => 'огненных',
                    'water' => 'водных', 
                    'grass' => 'травяных',
                    'electric' => 'электрических',
                    'psychic' => 'психических',
                    'ice' => 'ледяных',
                    'dragon' => 'драконьих',
                    'dark' => 'темных',
                    'fairy' => 'волшебных',
                    'normal' => 'нормальных',
                    'fighting' => 'боевых',
                    'poison' => 'ядовитых',
                    'ground' => 'наземных',
                    'flying' => 'летающих',
                    'bug' => 'жучьих',
                    'rock' => 'каменных',
                    'ghost' => 'призрачных',
                    'steel' => 'стальных'
                ];
                
                $typeName = $typeNames[$not['pokemon_type_id']] ?? $not['pokemon_type_id'];
                $dropDescription = 'С ' . $typeName . ' покемонов';
            }
            
            // Добавляем информацию о регионе
            if (!empty($not['region_id']) && $not['region_id'] > 0) {
                $region = $mysqli->query('SELECT * FROM `base_region` WHERE `id` = ' . (int)$not['region_id'])->fetch_assoc();
                if ($region) {
                    $dropDescription .= ' в регионе ' . htmlspecialchars($region['name'], ENT_QUOTES, 'UTF-8');
                }
            }
            
            $chanceText = (int)$not['drop_chance'] > 0 ? ' (1/' . (int)$not['drop_chance'] . ')' : '';
            $countText = '';
            
            // Обработка количества предметов
            if (!empty($not['item_count'])) {
                if (strpos($not['item_count'], ',') !== false) {
                    $countText = ' x' . $not['item_count'];
                } elseif ((int)$not['item_count'] > 1) {
                    $countText = ' x' . (int)$not['item_count'];
                }
            }
            
            $limitText = '';
            if ($not['limit_item_id'] > 0 && $not['limit_count'] > 0) {
                $limitText = ' (макс. ' . (int)$not['limit_count'] . ' шт.)';
            }
            
            $tpl .= $dropDescription . $chanceText . $countText . $limitText . '<br>';
        } else {
            if ($i != 0) {
                $tpl .= '</span></div>';
            }
            $b = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = ' . (int)$not['item_id'])->fetch_assoc();

            $dropDescription = 'Со всех диких покемонов';
            
            // Проверяем дроп по типу покемона (используем строковые типы из base_pokemons)
            if (!empty($not['pokemon_type_id'])) {
                $typeNames = [
                    'fire' => 'огненных',
                    'water' => 'водных', 
                    'grass' => 'травяных',
                    'electric' => 'электрических',
                    'psychic' => 'психических',
                    'ice' => 'ледяных',
                    'dragon' => 'драконьих',
                    'dark' => 'темных',
                    'fairy' => 'волшебных',
                    'normal' => 'нормальных',
                    'fighting' => 'боевых',
                    'poison' => 'ядовитых',
                    'ground' => 'наземных',
                    'flying' => 'летающих',
                    'bug' => 'жучьих',
                    'rock' => 'каменных',
                    'ghost' => 'призрачных',
                    'steel' => 'стальных'
                ];
                
                $typeName = $typeNames[$not['pokemon_type_id']] ?? $not['pokemon_type_id'];
                $dropDescription = 'С ' . $typeName . ' покемонов';
            }
            
            // Добавляем информацию о регионе если есть
            if (!empty($not['region_id']) && $not['region_id'] > 0) {
                $region = $mysqli->query('SELECT * FROM `base_region` WHERE `id` = ' . (int)$not['region_id'])->fetch_assoc();
                if ($region) {
                    $dropDescription .= ' в регионе ' . htmlspecialchars($region['name'], ENT_QUOTES, 'UTF-8');
                }
            }
            
            $chanceText = (int)$not['drop_chance'] > 0 ? ' (1/' . (int)$not['drop_chance'] . ')' : '';
            $countText = '';
            
            // Обработка количества предметов
            if (!empty($not['item_count'])) {
                if (strpos($not['item_count'], ',') !== false) {
                    $countText = ' x' . $not['item_count'];
                } elseif ((int)$not['item_count'] > 1) {
                    $countText = ' x' . (int)$not['item_count'];
                }
            }
            
            $limitText = '';
            if ($not['limit_item_id'] > 0 && $not['limit_count'] > 0) {
                $limitText = ' (макс. ' . (int)$not['limit_count'] . ' шт.)';
            }
            
            $tpl .= '<div class="dropBlock">
                <img onclick="issetAll(' . (int)$not['item_id'] . ')"  class="cl" src="/img/world/items/little/' . (int)$not['item_id'] . '.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> ' . htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') . '
                <span class="loc"> ' . $dropDescription . $chanceText . $countText . $limitText . ' <br>';
        }
        $i = (int)$not['item_id'];
    }
    if ($i != 0) {
        $tpl .= '</span></div>';
    }
    $tpl .= '</div></details>';
} else {
    // Если в БД нет универсального дропа, показываем старый статичный список
    $tpl .= '<details class="tree-nav__item is-expandable">
        <summary class="tree-nav__item-title">Дроп со всех покемонов</summary>
        <div class="listDrop">
            <div class="dropBlock">
                <img onclick="issetAll(2)"  class="cl" src="/img/world/items/little/2.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Покебол
                <span class="loc">Со всех диких покемонов (1/20)</span>
            </div>
            <div class="dropBlock">
                <img onclick="issetAll(10)"  class="cl" src="/img/world/items/little/10.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Стимулятор
                <span class="loc">Со всех диких покемонов (1/15)</span>
            </div>
            <div class="dropBlock">
                <img onclick="issetAll(462)" class="cl" src="/img/world/items/little/462.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Карты
                <span class="loc">Со всех диких покемонов (1/25)</span>
            </div>
            <div class="dropBlock">
                <img class="cl" onclick="issetAll(73)"  src="/img/world/items/little/73.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Оск. громового камня
                <span class="loc">С электрических покемонов (1/100)</span>
            </div>
            <div class="dropBlock">
                <img class="cl" onclick="issetAll(74)" src="/img/world/items/little/74.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Оск. водного камня
                <span class="loc">С водных покемонов (1/100)</span>
            </div>
            <div class="dropBlock">
                <img class="cl" onclick="issetAll(75)" src="/img/world/items/little/75.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Оск. лиственного камня
                <span class="loc">С травяных покемонов (1/100)</span>
            </div>
            <div class="dropBlock">
                <img class="cl" onclick="issetAll(76)" src="/img/world/items/little/76.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Оск. огненного камня
                <span class="loc">С огненных покемонов (1/100)</span>
            </div>
        </div></details>';
}

// ===== Региональный дроп =====
$regional_drops = $mysqli->query('SELECT * FROM `base_drop_pokemons` WHERE `quest_id` = 0 AND `region_id` > 0 AND `location_id` = 0 AND `is_active` = 1 ORDER BY `region_id` ASC, `item_id` DESC, `priority` DESC');
if ($regional_drops && $regional_drops->num_rows > 0) {
    $tpl .= '<details class="tree-nav__item is-expandable">
                <summary class="tree-nav__item-title">Региональный дроп</summary>
                <div class="listDrop">';
    
    $current_region = 0;
    $i = 0;
    while ($not = $regional_drops->fetch_assoc()) {
        // Если новый регион - добавляем заголовок
        if ($current_region != $not['region_id']) {
            if ($current_region != 0) {
                if ($i != 0) {
                    $tpl .= '</span></div>';
                }
            }
            $current_region = $not['region_id'];
            $region_info = $mysqli->query('SELECT * FROM `base_region` WHERE `id` = ' . (int)$not['region_id'])->fetch_assoc();
            $region_name = $region_info ? htmlspecialchars($region_info['name'], ENT_QUOTES, 'UTF-8') : 'Регион #' . (int)$not['region_id'];
            $tpl .= '<div class="questDropHeader"><strong>' . $region_name . '</strong></div>';
            $i = 0;
        }
        
        if ($i == $not['item_id']) {
            $dropDescription = 'Во всем регионе';
            if (!empty($not['pokemon_type_id'])) {
                $typeNames = [
                    'fire' => 'огненных',
                    'water' => 'водных', 
                    'grass' => 'травяных',
                    'electric' => 'электрических',
                    'psychic' => 'психических',
                    'ice' => 'ледяных',
                    'dragon' => 'драконьих',
                    'dark' => 'темных',
                    'fairy' => 'волшебных',
                    'normal' => 'нормальных',
                    'fighting' => 'боевых',
                    'poison' => 'ядовитых',
                    'ground' => 'наземных',
                    'flying' => 'летающих',
                    'bug' => 'жучьих',
                    'rock' => 'каменных',
                    'ghost' => 'призрачных',
                    'steel' => 'стальных'
                ];
                
                $typeName = $typeNames[$not['pokemon_type_id']] ?? $not['pokemon_type_id'];
                $dropDescription = 'С ' . $typeName . ' покемонов';
            }
            
            $chanceText = (int)$not['drop_chance'] > 0 ? ' (1/' . (int)$not['drop_chance'] . ')' : '';
            $countText = '';
            if (!empty($not['item_count'])) {
                if (strpos($not['item_count'], ',') !== false) {
                    $countText = ' x' . $not['item_count'];
                } elseif ((int)$not['item_count'] > 1) {
                    $countText = ' x' . (int)$not['item_count'];
                }
            }
            
            $limitText = '';
            if ($not['limit_item_id'] > 0 && $not['limit_count'] > 0) {
                $limitText = ' (макс. ' . (int)$not['limit_count'] . ' шт.)';
            }
            
            $tpl .= $dropDescription . $chanceText . $countText . $limitText . '<br>';
        } else {
            if ($i != 0) {
                $tpl .= '</span></div>';
            }
            $b = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = ' . (int)$not['item_id'])->fetch_assoc();

            $dropDescription = 'Во всем регионе';
            if (!empty($not['pokemon_type_id'])) {
                $typeNames = [
                    'fire' => 'огненных',
                    'water' => 'водных', 
                    'grass' => 'травяных',
                    'electric' => 'электрических',
                    'psychic' => 'психических',
                    'ice' => 'ледяных',
                    'dragon' => 'драконьих',
                    'dark' => 'темных',
                    'fairy' => 'волшебных',
                    'normal' => 'нормальных',
                    'fighting' => 'боевых',
                    'poison' => 'ядовитых',
                    'ground' => 'наземных',
                    'flying' => 'летающих',
                    'bug' => 'жучьих',
                    'rock' => 'каменных',
                    'ghost' => 'призрачных',
                    'steel' => 'стальных'
                ];
                
                $typeName = $typeNames[$not['pokemon_type_id']] ?? $not['pokemon_type_id'];
                $dropDescription = 'С ' . $typeName . ' покемонов';
            }
            
            $chanceText = (int)$not['drop_chance'] > 0 ? ' (1/' . (int)$not['drop_chance'] . ')' : '';
            $countText = '';
            if (!empty($not['item_count'])) {
                if (strpos($not['item_count'], ',') !== false) {
                    $countText = ' x' . $not['item_count'];
                } elseif ((int)$not['item_count'] > 1) {
                    $countText = ' x' . (int)$not['item_count'];
                }
            }
            
            $limitText = '';
            if ($not['limit_item_id'] > 0 && $not['limit_count'] > 0) {
                $limitText = ' (макс. ' . (int)$not['limit_count'] . ' шт.)';
            }
            
            $tpl .= '<div class="dropBlock">
                <img onclick="issetAll(' . (int)$not['item_id'] . ')"  class="cl" src="/img/world/items/little/' . (int)$not['item_id'] . '.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> ' . htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') . '
                <span class="loc"> ' . $dropDescription . $chanceText . $countText . $limitText . ' <br>';
        }
        $i = (int)$not['item_id'];
    }
    
    if ($i != 0) {
        $tpl .= '</span></div>';
    }
    $tpl .= '</div></details>';
}
    $response['htmlWeather'] = $tpl;
break;
			case 'notes':
			    $notes = $mysqli->query('SELECT * FROM `user_notes` WHERE id = '.$_SESSION['id'])->fetch_assoc();
			    $a .= "
					<textarea id='notesUsers' onkeydown='if(event.altKey && event.keyCode == 13){notesUsers();}' autocomplete='off'></textarea><br>
					<button onclick='notesUsers()' class='btnNotes'>Сохранить</button>
					";
					$response['notes'] = $notes['notes'];
			    $response['html'] = $a;
			break;
			
		}
        break;
		
		case 'craft':
    $tpl = '
<style>
  :root{
    --cr-bg:#f7f9ff; --cr-b:#e6eafe; --cr-card:#fff; --cr-t:#1b2b4f; --cr-sub:#6f7b95; --cr-ac:#0e55b6;
    --cr-shadow:0 12px 28px rgba(20,35,80,.10);
  }

  /* Контейнер мастерской (структура как в исходнике) */
  .DivCraft{
    background:var(--cr-bg);
    border-top:1px solid var(--cr-b);
    padding:12px;
  }

  /* Категории: делаем горизонтальные «табы» */
  .DivCraft .CraftCategory{
    display:flex; gap:10px; flex-wrap:wrap;
    background:var(--cr-card);
    border:1px solid var(--cr-b);
    border-radius:14px;
    padding:10px;
    box-shadow:var(--cr-shadow);
    margin-bottom:12px;
  }
  .DivCraft .CraftCategory .Button{
    appearance:none; border:1px solid var(--cr-b);
    background:#f1f5ff; color:#2f4477;
    border-radius:12px; padding:8px 12px;
    font:800 13px/1 Nunito,Arial,sans-serif;
    cursor:pointer; transition:.12s ease;
    user-select:none;
  }
  .DivCraft .CraftCategory .Button:hover{ transform:translateY(-1px) }
  .DivCraft .CraftCategory .Button.active{
    background:#eaf1ff; border-color:#cfe0ff; color:var(--cr-ac);
    box-shadow:0 0 0 2px rgba(14,85,182,.08) inset;
  }

  /* Блок контента */
  .DivCraft .CraftContent{ display:block }

  /* Краткое описание */
  .DivCraft .CraftContent .preview{
    background:var(--cr-card);
    border:1px solid var(--cr-b);
    border-radius:14px;
    padding:12px;
    box-shadow:var(--cr-shadow);
    color:var(--cr-sub);
    line-height:1.55;
    margin-bottom:12px;
  }
  .DivCraft .CraftContent .preview b{ color:#22345e }

  /* Список предметов — сетка карточек.
     Внутреннюю разметку генерирует твой world.js, мы аккуратно «подсвечиваем» любые элементы. */
  #craftList{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
    gap:14px;
    min-height:120px;
  }
  #craftList > *{
    background:var(--cr-card);
    border:1px solid var(--cr-b);
    border-radius:14px;
    padding:12px;
    box-shadow:var(--cr-shadow);
    color:var(--cr-t);
    transition:transform .12s, box-shadow .12s;
  }
  #craftList > *:hover{ transform:translateY(-2px); box-shadow:0 18px 28px rgba(23,35,74,.15) }
  #craftList img{ max-width:64px; max-height:64px; object-fit:contain; border-radius:12px; background:#fff; border:1px solid var(--cr-b) }
  #craftList .title, #craftList .name{ font:900 14px/1.2 Nunito,Arial; color:#22345e; margin:6px 0 4px }
  #craftList .desc,  #craftList .info{ font:600 12px/1.35 Nunito,Arial; color:#6f7b95 }
  #craftList .row,   #craftList .line{ display:flex; align-items:center; gap:8px; margin-top:6px; flex-wrap:wrap }
  #craftList .btn,   #craftList button{
    appearance:none; border:1px solid var(--cr-b); border-radius:12px; background:#f6f9ff;
    padding:8px 10px; font:900 12px Nunito,Arial; color:#2f4374; cursor:pointer;
  }
  #craftList .btn.primary, #craftList button.primary{
    background:linear-gradient(180deg,#2f74ff,#0e55b6); color:#fff; border-color:#2f74ff;
    box-shadow:0 8px 20px rgba(46,96,220,.25);
  }
  #craftList .tag{
    border:1px solid var(--cr-b); background:#f4f7ff; color:#3b4c77;
    border-radius:999px; padding:3px 8px; font:800 11px Nunito,Arial;
  }

  /* Детали рецепта (как раньше) */
  #craftRecipeDetail{
    background:var(--cr-card);
    border:1px solid var(--cr-b);
    border-radius:14px;
    padding:14px;
    box-shadow:var(--cr-shadow);
    margin-top:12px;
    min-height:130px;
  }
  #craftRecipeDetail .ph{ color:#8996ba; font:600 13px Nunito,Arial }

  /* Адаптив */
  @media (max-width:640px){
    .DivCraft .CraftCategory .Button{ padding:7px 10px; font-size:12.5px }
    #craftList{ grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)) }
  }
</style>

<div class="Title">
  <div class="Name">Мастерская</div>
  <div class="Info">Создавайте предметы с помощью крафта</div>
  <div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
</div>

<div class="DivCraft">
  <div class="CraftCategory">
    <div class="Button all active" onclick="craftCategory(\'all\')">Все</div>
    <div class="Button favorites" onclick="craftCategory(\'favorites\')">Избранное</div>
    <div class="Button modificator" onclick="craftCategory(\'modificator\')">Модификаторы</div>
    <div class="Button ball" onclick="craftCategory(\'ball\')">Покеболы</div>
    <div class="Button evolver" onclick="craftCategory(\'evolver\')">Камни</div>
    <div class="Button potion" onclick="craftCategory(\'potion\')">Зелья</div>
    <div class="Button medicine" onclick="craftCategory(\'medicine\')">Лечение</div>
    <div class="Button etc" onclick="craftCategory(\'etc\')">Прочее</div>
  </div>

  <div class="CraftContent">
    <div class="preview">
      <b>Крафт</b> позволяет собирать предметы из ресурсов. Выберите категорию выше — доступные рецепты появятся ниже.
    </div>
    <div id="craftList"></div>
    <div id="craftRecipeDetail"><div class="ph">Выберите рецепт, чтобы увидеть детали.</div></div>
  </div>
</div>

<script src="/js/world.js"></script>
<script>
  // Мягкая авто-загрузка «Все», чтобы список сразу появился (не мешает твоему world.js)
  if (typeof craftCategory === "function") {
    try { craftCategory("all"); } catch(e){}
  }
</script>
';
    $response["html"] = $tpl;
    break;

		case 'ItemOpen':
    $itemId = isset($_POST['item']) ? (int)$_POST['item'] : 0;

    $stmt = $mysqli->prepare("SELECT `name`, `description` FROM `craft_item` WHERE `id` = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Небольшой помощник для экранирования
    $h = static function($s){
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    if ($row = $result->fetch_assoc()) {
        $name = $h($row['name']);
        $desc = trim((string)$row['description']) !== '' ? $h($row['description']) : 'Описание пока недоступно.';

        // Карточка в едином стиле с крафтом
        $html = '
        <style id="item-open-css-v1">
          .ItemCard{background:var(--cr-card,#fff);border:1px solid var(--cr-b,#e6eaf3);border-radius:14px;
                    box-shadow:0 12px 28px rgba(60,90,120,.12);padding:14px;display:grid;
                    grid-template-columns:64px 1fr;gap:12px;align-items:center;}
          .ItemCard__icon{width:64px;height:64px;border-radius:12px;overflow:hidden;border:1px solid var(--cr-b,#e6eaf3);
                          background:#fff;display:grid;place-items:center}
          .ItemCard__icon img{width:60px;height:60px;object-fit:contain;display:block}
          .ItemCard__body{min-width:0;position:relative}
          .ItemCard__title{font:900 16px/1.2 Nunito,Arial,sans-serif;color:var(--cr-ac,#406abf);margin:0 0 6px 0}
          .ItemCard__desc{font:600 13px/1.5 Nunito,Arial,sans-serif;color:var(--cr-sub,#6a7387)}
          .ItemCard__id{position:absolute;top:-2px;right:0;font:800 11px/1 Nunito,Arial,sans-serif;color:#6f7b95;
                        background:#f3f6ff;border:1px solid var(--cr-b,#e6eaf3);padding:4px 8px;border-radius:999px}
          @media(max-width:560px){
            .ItemCard{grid-template-columns:52px 1fr;padding:12px}
            .ItemCard__icon{width:52px;height:52px}
            .ItemCard__icon img{width:48px;height:48px}
            .ItemCard__title{font-size:15px}
            .ItemCard__desc{font-size:12.5px}
          }
        </style>
        <div class="ItemCard">
          <div class="ItemCard__icon">
            <img src="/img/world/items/big/'.$itemId.'.png"
                 onerror="this.onerror=null; this.src=\'/img/world/items/little/'.$itemId.'.png\'; this.onerror=function(){this.src=\'/img/world/items/little/undefined.png\';};"
                 alt="'.$name.'">
          </div>
          <div class="ItemCard__body">
            <div class="ItemCard__title">'.$name.'</div>
            <div class="ItemCard__desc">'.$desc.'</div>
            <div class="ItemCard__id">ID: '.$itemId.'</div>
          </div>
        </div>';

        $response = [
            'error' => false,
            'html'  => $html
        ];
    } else {
        $response = [
            'error'   => true,
            'message' => 'Предмет не найден.'
        ];
    }

    echo json_encode($response);
    break;

    case 'trainercollection':
    $tpl = '<div class="Title">
        <div class="Name">Коллекция тренера</div>
        <div class="Info">Вся ваша коллекция покемонов, включая пойманных и шайни. Можно фильтровать и искать!</div>
        <div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
    </div>';

    // Фильтры и поиск
    $tpl .= '
    <div class="TrainerCollectionPanel">
        <div class="trainer-filters">
            <button onclick="TrainerCollection(\'filter\',\'all\')" class="TrainerFilterBtn" id="filterAll">Все</button>
            <button onclick="TrainerCollection(\'filter\',\'caught\')" class="TrainerFilterBtn" id="filterCaught">Пойманные</button>
            <button onclick="TrainerCollection(\'filter\',\'shiny\')" class="TrainerFilterBtn" id="filterShiny">Шайни</button>
            <button onclick="TrainerCollection(\'filter\',\'uncaught\')" class="TrainerFilterBtn" id="filterUncaught">Не пойманные</button>
            <input type="text" id="trainer-search" class="TrainerCollectionSearch" placeholder="Поиск по имени или номеру..." onkeyup="TrainerCollection(\'search\',this.value)">
        </div>
    ';

    // Получение параметров фильтрации и поиска из AJAX
    $filter = $_POST['filter'] ?? 'all';
    $search = trim($_POST['search'] ?? '');

    // Данные пользователя
    $user_id = $_SESSION['id'];
    $user_pokemons = [];
    $sql = "SELECT basenum, type FROM user_pokemons WHERE user_id = $user_id";
    $res = $mysqli->query($sql);
    while ($row = $res->fetch_assoc()) {
        if (!isset($user_pokemons[$row['basenum']])) {
            $user_pokemons[$row['basenum']] = [];
        }
        $user_pokemons[$row['basenum']][$row['type']] = true;
    }

    // Покемоны из базы с поиском
    $where = "form = 'Обычный'";
    if ($search !== '') {
        // Экранируй строку поиска для безопасности
        $search_safe = $mysqli->real_escape_string($search);
        $where .= " AND (name_rus LIKE '%$search_safe%' OR id LIKE '%$search_safe%')";
    }
    $sql = "SELECT id, name_rus FROM base_pokemons WHERE $where ORDER BY id ASC";
    $res_all = $mysqli->query($sql);

    $tpl .= '<div class="trainer-pokedex-bar">';
    $count = 0;
    while($row = $res_all->fetch_assoc()){
        $caught = isset($user_pokemons[$row['id']]['normal']);
        $shiny = isset($user_pokemons[$row['id']]['shine']);
        // Фильтрация
        if ($filter == 'caught' && !$caught) continue;
        if ($filter == 'shiny' && !$shiny) continue;
        if ($filter == 'uncaught' && ($caught || $shiny)) continue;

        $itemClass = 'trainer-pokebar-item';
        if ($caught) $itemClass .= ' caught';
        if ($shiny) $itemClass .= ' shiny';

        $tpl .= '<div class="'.$itemClass.'" onclick="openDex('.$row['id'].')">';
        $tpl .= '<img src="/img/pokemons/pokedex/'.$row['id'].'.png" alt="'.$row['name_rus'].'" />';
        $tpl .= '<div class="trainer-pokename">'.$row['name_rus'].'</div>';
        $tpl .= '<div class="trainer-pokid">'.$row['id'].'</div>';
        if ($caught) $tpl .= '<div class="trainer-pokstatus caught" title="Пойман">✓</div>';
        if ($shiny) $tpl .= '<div class="trainer-pokstatus shiny" title="Шайни">★ Shiny</div>';
        $tpl .= '</div>';
        $count++;
    }
    // Сообщение если нет ни одного покемона по фильтру
    if ($count == 0) {
        $tpl .= '<div class="no-pokemons">По вашему запросу ничего не найдено.</div>';
    }
    $tpl .= '</div>'; // trainer-pokedex-bar
    $tpl .= '</div>'; // TrainerCollectionPanel

    $response["html"] = $tpl;
    break;
		case 'discovery':
				$UserQuery = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
				$tpl.= '<div class="Title"><div class="Name">Поиск предметов</div><div class="Info">Добывайте предметы на локациях</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div><div class="DivCraft">
			<div class="CraftCategory">
				<div class="Button ore" onclick="discoveryCategory(\'ore\')">Археология</div>
				<div class="Button seed" onclick="discoveryCategory(\'seed\')">Семена</div>
				<div class="Button disc"  onclick="discoveryCategory(\'mypok\')">Добытчики</div>
			</div>
			<div class="CraftContent">
				<div class="preview">Начните добывать себе предметы, выбрав категорию. </div>
			</div></div>';
			$response["html"] = $tpl;
        break;
				case 'work':
				$tpl.= '<div class="Title"><div class="Name">Работа покемонов</div><div class="Info">Отправляйте покемонов на подходящую им работу </div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>';
				if($u['lvl'] >= 10){
				$tpl .= '<div class="modal_WorkPokemon"><div class="WorkPokemon"><div class="About">Выберите профессию чтобы перейти к подробной информации:</div><div class="WorkList">';
					$works = $mysqli->query('SELECT * FROM `base_work`');
					while($work = $works->fetch_assoc()){
						$tpl .= '<div class="WorkBtn" onclick="workCategory('.$work['id'].')"><img src="/img/pokemon_work/'.$work['id'].'.png">'.$work['name'].'</div>';
					}
				$tpl.= '</div></div></div>';
}else{
					$tpl .= '<div class="block"><i class="far fa-lock-alt"></i><br><span>Работа покемонов доступна игрокам, достигшим <b>15</b> уровня.</span></div>';
				}
				$tpl .= '</div>';
			//$response["html"] = $tpl;
				//break;
		case 'rules':
    $tpl = '<div class="Title">
                <div class="Name">Правила игры</div>
                <div class="Info">Ознакомьтесь с основными правилами игры</div>
                <div class="Close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </div>
            </div>';

    // Путь к файлу правил
    $rulesPath = $_SERVER['DOCUMENT_ROOT'] . '/rules.php';

    // Детектор HTML и мягкая санитизация
    $isLikelyHtml = function (string $s): bool {
        return (bool)preg_match('/<\\s*(div|p|ul|ol|li|details|summary|section|article|h[1-6]|span|br|a|strong|em|i|b|hr|nav|header|footer)\\b/i', $s);
    };
    $sanitizeHtml = function (string $html): string {
        $html = preg_replace('#<script\\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('/\\son[a-z]+\\s*=\\s*(["\']).*?\\1/iu', '', $html) ?? $html;
        $html = preg_replace('/\\b(href|src)\\s*=\\s*(["\'])\\s*javascript:[^\\2]*\\2/iu', '$1="#"', $html) ?? $html;
        return $html;
    };

    if (file_exists($rulesPath)) {
        $ext = strtolower(pathinfo($rulesPath, PATHINFO_EXTENSION));
        $raw = '';

        if ($ext === 'php') {
            ob_start();
            include $rulesPath;
            $raw = ob_get_clean();
        } else {
            $raw = file_get_contents($rulesPath);
        }
        $raw = (string)$raw;

        $isHtml = in_array($ext, ['php', 'html', 'htm'], true) || $isLikelyHtml($raw);
        $rendered = $isHtml
            ? $sanitizeHtml($raw)
            : nl2br(htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $updatedAt    = @filemtime($rulesPath) ?: time();
        $updatedLabel = 'Обновлено: ' . date('d.m.Y H:i', $updatedAt);

        // Обёртка с более узкой шириной + адаптив
        $tpl .= <<<'HTML'
<div class="RulesShell">
  <div class="RulesBlock" id="rulesBlock">
    <div class="RulesHead" id="rulesHead" aria-label="Панель управления правилами">
      <div class="HeadRow HeadRow--toolbar">
        <input type="text" class="RT-search" id="rulesSearch" placeholder="Поиск по правилам…">
        <div class="HeadActions">
          <button type="button" class="RT-btn" data-action="expand">Развернуть всё</button>
          <button type="button" class="RT-btn" data-action="collapse">Свернуть всё</button>
          <button type="button" class="RT-btn RT-print" data-action="print">Печать</button>
        </div>
      </div>

      <div class="HeadRow HeadRow--meta">
        <div class="RulesMeta" id="rulesMeta"></div>
      </div>

      <div class="HeadRow HeadRow--nav">
        <div class="RulesNav" id="rulesNav"></div>
        <button type="button" class="NavMore" id="navMore" style="display:none;">Ещё</button>
      </div>

      <div class="HeadRow HeadRow--filters">
        <label class="RF-field">
          <span class="RF-label">Раздел:</span>
          <select id="rfSectionSelect" class="RF-select"></select>
        </label>

        <div class="RF-field">
          <span class="RF-label">Наказание:</span>
          <div class="RF-segment" id="rfPenaltyGroup" role="tablist" aria-label="Фильтр по наказанию">
            <button type="button" class="seg active" data-penalty="any" role="tab" aria-selected="true">Любые</button>
            <button type="button" class="seg" data-penalty="mute" role="tab" aria-selected="false">Мут</button>
            <button type="button" class="seg" data-penalty="jail" role="tab" aria-selected="false">Стража</button>
            <button type="button" class="seg" data-penalty="return" role="tab" aria-selected="false">Возврат</button>
          </div>
        </div>

        <div class="RF-spacer"></div>
        <button type="button" class="RT-btn RF-reset" id="rfReset">Сбросить</button>
        <div class="RF-stats" id="rfStats"></div>
      </div>
    </div>

    <div class="RulesContent" id="RulesContent">
HTML;

        $tpl .= $rendered;

        $tpl .= <<<'HTML'
    </div>
  </div>
</div>

<style>
  /* БОЛЕЕ УЗКАЯ ширина + центрирование */
  .RulesShell{
    max-width: clamp(560px, 88vw, 820px);
    margin: 8px auto 0;
  }

  .RulesBlock{
    --rules-head-h: 0px; /* JS подставит фактическую высоту шапки */
    max-height: 72vh;
    overflow: auto;
    background:#fff;
    border-radius:12px;
    border:1px solid #efe7fb;
    position:relative;
    -webkit-overflow-scrolling:touch;
  }
  .RulesBlock::-webkit-scrollbar{height:8px;width:10px}
  .RulesBlock::-webkit-scrollbar-thumb{background:#e6defa;border-radius:6px}
  .RulesBlock::-webkit-scrollbar-track{background:transparent}

  .RulesHead{
    position:sticky; top:-25px; z-index:5; background:#fff;
    border-bottom:1px solid #efe7fb; box-shadow:0 0 0 rgba(0,0,0,0);
  }
  .RulesHead.is-scrolled{box-shadow:0 2px 10px rgba(93,62,188,.06)}
  .HeadRow{display:flex; align-items:center; gap:12px; padding:10px 12px}
  .HeadRow--toolbar{justify-content:space-between; padding-bottom:8px}
  .HeadRow--meta{padding-top:0; padding-bottom:6px; color:#6d5fa0; font-size:.88rem}
  .HeadRow--nav{padding-top:0; padding-bottom:8px; gap:8px; overflow:hidden}
  .HeadRow--filters{padding-top:8px; border-top:1px dashed #f1eafd; flex-wrap:wrap}

  .RT-search{flex:1 1 300px; padding:8px 10px; border:1px solid #e9e1fb; border-radius:8px; font-size:.95rem; outline:none}
  .RT-search:focus{border-color:#9d4edd; box-shadow:0 0 0 3px rgba(157,78,221,.12)}
  .RT-btn{padding:8px 10px; border:1px solid #e9e1fb; background:#fff; border-radius:8px; cursor:pointer; font-size:.92rem}
  .RT-btn:hover{background:#faf6ff}

  .RulesNav{display:flex; gap:8px; flex-wrap:nowrap}
  .RulesNav .chip{
    white-space:nowrap; padding:6px 10px; border:1px solid #efe7fb; border-radius:999px; text-decoration:none; color:#6d5fa0; font-size:.9rem; background:#fff;
  }
  .RulesNav .chip:hover{background:#faf6ff}
  .NavMore{padding:6px 10px; border:1px solid #efe7fb; background:#fff; border-radius:999px; color:#6d5fa0; font-size:.88rem; cursor:pointer}


  .RulesContent details{border:1px solid #f0e9ff; border-radius:10px; padding:10px 12px; margin:10px 0; background:#fcfaff}
  .RulesContent summary{cursor:pointer; font-weight:700; color:#5b4ca0}
  .RulesContent .highlight{background:#fff7d6; border-radius:4px}
  .RulesContent hr{border:0; border-top:1px dashed #eee; margin:10px 0}

  .RF-field{display:flex; align-items:center; gap:8px}
  .RF-label{color:#8a7dc4; font-size:.9rem}
  .RF-select{min-width:220px; padding:7px 10px; border:1px solid #e9e1fb; border-radius:8px; font-size:.92rem; background:#fff}
  .RF-segment{display:flex; border:1px solid #e9e1fb; border-radius:10px; overflow:hidden}
  .RF-segment .seg{padding:7px 12px; border:0; background:#fff; font-size:.9rem; cursor:pointer}
  .RF-segment .seg + .seg{border-left:1px solid #e9e1fb}
  .RF-segment .seg.active{background:#f3ecff}
  .RF-spacer{flex:1}
  .RF-stats{color:#8a7dc4; font-size:.88rem}

  .badge{display:inline-block; padding:2px 6px; border-radius:6px; font-size:.85em; line-height:1}
  .badge-mute{background:#fff1da; color:#8a5a00; border:1px solid #ffd8a3}
  .badge-jail{background:#fde2e2; color:#8a1e1e; border:1px solid #f8b3b3}
  .badge-return{background:#e6f7e8; color:#146c2e; border:1px solid #bde5c4}

  .gr-list{list-style:none; padding:0; margin:0}
  .gr-item{display:flex; gap:10px; padding:10px 6px; border-bottom:1px dashed #f0e9ff; position:relative}
  .gr-item:last-child{border-bottom:none}
  .rule-num{display:inline-block; min-width:2.2em; color:#aa9bd6; font-weight:600; margin-right:6px}
  .gr-item .rule-anchor{opacity:0; position:absolute; right:6px; top:8px; font-size:.9rem; color:#b3a7db; cursor:pointer}
  .gr-item:hover .rule-anchor{opacity:1}

  /* ====== BREAKPOINTS ====== */
  @media (max-width: 980px){
    .RulesShell{max-width: clamp(560px, 94vw, 860px);}
  }
  @media (max-width: 780px){
    .RulesShell{max-width: 92vw;}
    .RF-select{min-width:200px}
  }
  @media (max-width: 600px){
    .RulesShell{max-width: 96vw;}
    .RulesBlock{max-height: 64vh;}
    .HeadRow{padding:8px 10px}
    .HeadRow--filters{gap:10px}
    .RT-search{flex:1 1 100%; width:100%}
    .HeadActions{gap:6px}
    .RT-btn{padding:7px 9px}
    .RT-btn.RT-print{display:none} /* печать прячем на мобиле */
    .RulesNav{overflow:auto; -webkit-overflow-scrolling:touch}
    #navMore{display:none !important;} /* на мобиле достаточно горизонтального скролла */
    .RF-select{min-width: 180px}
    .RF-stats{width:100%; text-align:right}
  }
  @media (max-width: 420px){
    .RulesBlock{max-height: 60vh;}
    .RF-segment{display:grid; grid-template-columns: repeat(2, minmax(0,1fr));}
    .RF-segment .seg{border:1px solid #e9e1fb !important; margin:-1px 0 0 -1px} /* сетка без щелей */
    .RF-select{min-width: 100%}
    .RF-field{width:100%}
  }
</style>

<script>
(function(){
  var boxEl        = document.getElementById('rulesBlock');
  var headEl       = document.getElementById('rulesHead');
  var containerEl  = document.getElementById('RulesContent');
  var searchEl     = document.getElementById('rulesSearch');
  var navEl        = document.getElementById('rulesNav');
  var navMoreEl    = document.getElementById('navMore');
  var metaEl       = document.getElementById('rulesMeta');
  var sectionSelect= document.getElementById('rfSectionSelect');
  var penaltyGroup = document.getElementById('rfPenaltyGroup');
  var resetBtn     = document.getElementById('rfReset');
  var statsEl      = document.getElementById('rfStats');

  if (metaEl) metaEl.textContent = '__UPDATED_LABEL__';

  /* высота шапки -> паддинг контента (чтобы не перекрывалось) */
  function syncHeadSpace(){
    if (!boxEl || !headEl) return;
    var h = headEl.getBoundingClientRect().height;
    boxEl.style.setProperty('--rules-head-h', h + 'px');
    headEl.classList.toggle('is-scrolled', boxEl.scrollTop > 0);
  }
  boxEl && boxEl.addEventListener('scroll', function(){
    headEl.classList.toggle('is-scrolled', boxEl.scrollTop > 0);
  });
  window.addEventListener('resize', syncHeadSpace);
  setTimeout(syncHeadSpace, 0);
  setTimeout(syncHeadSpace, 150);

  /* якорный скролл с учетом высоты шапки */
  function scrollToTarget(el){
    if (!el || !boxEl || !headEl) return;
    var delta = (el.getBoundingClientRect().top - boxEl.getBoundingClientRect().top)
                - headEl.getBoundingClientRect().height - 8;
    boxEl.scrollBy({ top: delta, behavior: 'smooth' });
  }

  /* навигация по разделам (до 5 чипов; на мобиле — горизонтальный скролл) */
  function buildNav(){
    if (!containerEl) return;
    var summaries = containerEl.querySelectorAll('details[id^="gr-"] > summary');
    navEl.innerHTML = '';
    var hidden = [];
    summaries.forEach(function(s, i){
      var host = s.parentElement;
      if (!host.id) host.id = 'gr-top-' + (i+1);
      var chip = document.createElement('a');
      chip.href = '#' + host.id;
      chip.className = 'chip';
      chip.textContent = (s.textContent || '').trim().replace(/\s+/g,' ').slice(0,60);
      chip.addEventListener('click', function(e){
        e.preventDefault(); host.open = true; scrollToTarget(host);
      });
      if (i >= 5) hidden.push(chip);
      navEl.appendChild(chip);
    });
    if (hidden.length) {
      navMoreEl.style.display = '';
      var expanded = false;
      navMoreEl.onclick = function(){
        expanded = !expanded;
        hidden.forEach(function(c){ c.style.display = expanded ? '' : 'none'; });
        navMoreEl.textContent = expanded ? 'Скрыть' : 'Ещё';
        syncHeadSpace();
      };
      hidden.forEach(function(c){ c.style.display = 'none'; });
    } else {
      navMoreEl.style.display = 'none';
    }
  }

  /* поиск + подсветка */
  var lastMarks = [];
  function clearHighlights(){
    lastMarks.forEach(function(el){
      var p = el.parentNode; p && p.replaceChild(document.createTextNode(el.textContent), el);
      p && p.normalize();
    });
    lastMarks = [];
  }
  function highlight(term){
    if (!term || !containerEl) return;
    var rx = new RegExp(term.replace(/[.*+?^${}()|[\\]\\\\]/g,'\\\\$&'), 'gi');
    var walker = document.createTreeWalker(containerEl, NodeFilter.SHOW_TEXT, {
      acceptNode: function(node){
        if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
        var p = node.parentNode; if (!p) return NodeFilter.FILTER_REJECT;
        var tag = p.tagName ? p.tagName.toLowerCase() : '';
        if (tag === 'script' || tag === 'style') return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });
    var nodes = []; while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(function(textNode){
      var text = textNode.nodeValue; if (!rx.test(text)) return;
      var frag = document.createDocumentFragment(), lastIndex = 0;
      text.replace(rx, function(match, idx){
        var before = text.slice(lastIndex, idx);
        if (before) frag.appendChild(document.createTextNode(before));
        var mark = document.createElement('mark'); mark.className = 'highlight'; mark.textContent = match;
        frag.appendChild(mark); lastMarks.push(mark);
        lastIndex = idx + match.length;
      });
      var after = text.slice(lastIndex); if (after) frag.appendChild(document.createTextNode(after));
      textNode.parentNode.replaceChild(frag, textNode);
    });
  }
  function applySearchFlag(q){
    var items = containerEl ? containerEl.querySelectorAll('.gr-item, li') : [];
    items.forEach(function(el){
      if (!q) { el.dataset.searchHit = '1'; return; }
      var has = el.querySelector('.highlight') || (el.textContent || '').toLowerCase().includes(q.toLowerCase());
      el.dataset.searchHit = has ? '1' : '0';
    });
  }
  if (searchEl) {
    var tId = null;
    searchEl.addEventListener('input', function(){
      clearTimeout(tId);
      tId = setTimeout(function(){
        clearHighlights();
        var v = searchEl.value.trim();
        if (!v) { applySearchFlag(''); applyFilters(); return; }
        highlight(v); applySearchFlag(v); applyFilters();
      }, 100);
    });
  }

  /* индексация правил (нумерация/якоря/метки наказаний) */
  var sectionList = [];
  function indexSectionsAndRules(){
    if (!containerEl) return;
    sectionList = [];
    var detailsList = Array.prototype.slice.call(containerEl.querySelectorAll('details[id^="gr-"]'));
    detailsList.forEach(function(d, idx){
      var name = (d.querySelector('summary') ? d.querySelector('summary').textContent.trim() : ('Раздел ' + (idx+1)));
      sectionList.push({id: d.id, name: name});
      var rules = d.querySelectorAll('.gr-item, li');
      var k = 0;
      Array.prototype.forEach.call(rules, function(li){
        if (!li.textContent.trim()) return;
        k++;
        li.dataset.section = d.id;
        var penalties = [];
        if (li.querySelector('.badge-mute')) penalties.push('mute');
        if (li.querySelector('.badge-jail')) penalties.push('jail');
        if (li.querySelector('.badge-return')) penalties.push('return');
        li.dataset.penalties = penalties.join(',') || '';

        if (!li.querySelector('.rule-num')) {
          var num = document.createElement('span');
          num.className = 'rule-num';
          num.textContent = (idx+1) + '.' + k;
          var block = li.querySelector('.gr-text') || li.firstElementChild || li;
          block.firstChild ? block.insertBefore(num, block.firstChild) : block.appendChild(num);
        }
        if (!li.id) li.id = 'rule-' + (idx+1) + '-' + k;
        if (!li.querySelector('.rule-anchor')) {
          var a = document.createElement('span');
          a.className = 'rule-anchor';
          a.title = 'Скопировать ссылку на правило';
          a.innerHTML = '<i class="fas fa-link"></i>';
          a.addEventListener('click', function(){
            var url = location.origin + location.pathname + location.search + '#' + li.id;
            navigator.clipboard && navigator.clipboard.writeText && navigator.clipboard.writeText(url);
          });
          li.appendChild(a);
        }
      });
    });
  }

  /* селект раздела */
  function buildSectionSelect(){
    if (!sectionSelect) return;
    sectionSelect.innerHTML = '';
    var optAll = document.createElement('option');
    optAll.value = ''; optAll.textContent = 'Все разделы';
    sectionSelect.appendChild(optAll);
    sectionList.forEach(function(s){
      var o = document.createElement('option');
      o.value = s.id;
      o.textContent = s.name.replace(/^[0-9.\\s-]+/, '').trim();
      sectionSelect.appendChild(o);
    });
  }

  /* фильтр наказаний */
  function setPenaltyActive(val){
    penaltyGroup.querySelectorAll('.seg').forEach(function(b){
      var active = b.dataset.penalty === val;
      b.classList.toggle('active', active);
      b.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  }
  var activePenalty = 'any';
  var activeSection = '';
  penaltyGroup && penaltyGroup.addEventListener('click', function(e){
    var btn = e.target.closest('.seg'); if (!btn) return;
    activePenalty = btn.dataset.penalty || 'any';
    setPenaltyActive(activePenalty); applyFilters(); persistFilters();
  });
  sectionSelect && sectionSelect.addEventListener('change', function(){
    activeSection = sectionSelect.value || ''; applyFilters(); persistFilters();
  });

  /* тулбар */
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.RT-btn'); if (!btn) return;
    var act = btn.getAttribute('data-action');
    if (act === 'expand') {
      containerEl && containerEl.querySelectorAll('details').forEach(function(d){ d.open = true; });
      syncHeadSpace();
    } else if (act === 'collapse') {
      containerEl && containerEl.querySelectorAll('details').forEach(function(d){ d.open = false; });
      syncHeadSpace();
    } else if (act === 'print') {
      var w = window.open('', '_blank'); if (!w) return;
      var printCss = '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif;line-height:1.4;padding:16px;}details{border:1px solid #ccc;border-radius:8px;padding:10px;margin:8px 0;}summary{font-weight:700;}.rule-anchor{display:none}</style>';
      w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Правила игры</title>'+printCss+'</head><body>'+ document.querySelector('.RulesBlock').innerHTML +'</body></html>');
      w.document.close(); w.focus(); w.print();
    }
  });

  /* применение фильтров + счётчик */
  function applyFilters(){
    if (!containerEl) return;
    var items = containerEl.querySelectorAll('.gr-item, li');
    var total = 0, shown = 0;
    items.forEach(function(el){
      var text = (el.textContent || '').trim();
      if (!text) { el.style.display = 'none'; return; }
      total++;
      var passSearch  = (el.dataset.searchHit !== '0');
      var passSection = activeSection ? (el.dataset.section === activeSection) : true;
      var passPenalty = (activePenalty === 'any') ? true :
        ((el.dataset.penalties || '').split(',').filter(Boolean).indexOf(activePenalty) >= 0);
      var visible = passSearch && passSection && passPenalty;
      el.style.display = visible ? '' : 'none';
      if (visible) shown++;
    });
    var detailsList = containerEl.querySelectorAll('details[id^="gr-"]');
    detailsList.forEach(function(d){
      var hasVisible = !!d.querySelector('.gr-item:not([style*="display: none"]), li:not([style*="display: none"])');
      d.style.display = hasVisible ? '' : 'none';
      if (hasVisible) d.open = true;
    });
    statsEl && (statsEl.textContent = 'Показано: ' + shown + ' из ' + total);
  }

  /* сброс */
  resetBtn && resetBtn.addEventListener('click', function(){
    searchEl && (searchEl.value='');
    clearHighlights(); applySearchFlag('');
    activeSection = ''; sectionSelect && (sectionSelect.value = '');
    activePenalty = 'any'; setPenaltyActive('any');
    applyFilters(); persistFilters(); syncHeadSpace();
  });

  /* локальное хранилище */
  function persistFilters(){
    try { localStorage.setItem('rules.filters.v6', JSON.stringify({
      s: activeSection, p: activePenalty, q: (searchEl ? searchEl.value.trim() : '')
    })); } catch(e){}
  }
  function restoreFilters(){
    try {
      var raw = localStorage.getItem('rules.filters.v6'); if (!raw) return;
      var data = JSON.parse(raw);
      activeSection = data.s || ''; activePenalty = data.p || 'any';
      searchEl && data.q && (searchEl.value = data.q);
    } catch(e){}
  }

  // init
  buildNav();
  indexSectionsAndRules();
  buildSectionSelect();
  restoreFilters();
  setPenaltyActive(activePenalty);
  applySearchFlag(searchEl ? searchEl.value.trim() : '');
  applyFilters();
  syncHeadSpace();

  // удобные хоткеи
  document.addEventListener('keydown', function(e){
    if (e.key.toLowerCase() === 'f' && !e.ctrlKey && !e.metaKey && !e.altKey) {
      searchEl && (searchEl.focus(), searchEl.select(), e.preventDefault());
    }
    if (e.key === 'Escape') {
      if (searchEl && searchEl.value) { searchEl.value = ''; searchEl.dispatchEvent(new Event('input')); }
    }
  });
})();
</script>
HTML;

        $tpl = str_replace('__UPDATED_LABEL__', htmlspecialchars($updatedLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $tpl);
    } else {
        $tpl .= '<div class="ErrorMessage">Файл правил не найден. Обратитесь к Администрации.</div>';
    }

    $response['html'] = $tpl;
    break;

    	case 'dolzn':
    $tpl = '<div class="Title">
                <div class="Name">Правила игры</div>
                <div class="Info">Ознакомьтесь с основными правилами игры</div>
                <div class="Close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </div>
            </div>';
    // Проверяем, существует ли файл правил
    $rulesPath = $_SERVER['DOCUMENT_ROOT'] . '/do/DolznPanel'; // Укажите путь к файлу правил
    if (file_exists($rulesPath)) {
        // Читаем содержимое файла и обрабатываем переносы строк
        $rulesContent = file_get_contents($rulesPath);
        $tpl .= '<div class="RulesBlock">
                    <div class="RulesContent">' . nl2br(htmlspecialchars($rulesContent)) . '</div>
                 </div>';
    } else {
        // Сообщение об ошибке, если файл не найден
        $tpl .= '<div class="ErrorMessage">Файл правил не найден. Обратитесь к Администрации.</div>';
    }

    $response["html"] = $tpl;
    break;
    case 'forum':
    $tpl = '<div class="Title">
                <div class="Name">Форум</div>
                <div class="Info">Перейдите на форум для обсуждения и общения с другими игроками</div>
                <div class="Close" onclick="closeModal()">
                    <i class="fas fa-atlas"></i>
                </div>
            </div>';
    
    // Указываем ссылку на форум
    $forumLink = 'http://forum.pokemon-emerald.ru/'; // Укажите реальный URL форума

    // Добавляем кнопку для перехода на форум
    $tpl .= '<div class="ForumLink">
                <a href="' . $forumLink . '" target="_blank" class="btn">Перейти на форум</a>
             </div>';

    $response["html"] = $tpl;
    break;

    	case 'map':
    $tpl = '<div class="Title">
            <div class="Name">Карта</div>
            <div class="Info">Карта игрового мира — исследуйте регион</div>
            <div class="Close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </div>
        </div>';

    // Карта: используем старый стабильный путь + fallback через onerror
    $mapPathPrimary = '/map.jpg';
    $mapPathFallback = '/img/world/map/region_map.png'; // если у вас нет — можно заменить

    $tpl .= '
    <style>
      #WorldMapHead{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:10px 0 8px}
      #WorldMapCurrentLoc{font-weight:700}
      #WorldMapLegend{display:flex;align-items:center;gap:16px;color:#64748b}
      .WM_dot{display:inline-block;width:12px;height:12px;border-radius:50%;margin-right:6px;vertical-align:-2px}
      .WM_dot--me{background:#ff3b30}
      .WM_dot--road{background:rgba(255,255,255,.9)}
      .WM_dot--pc{background:rgba(80,200,255,.95)}
      #WorldMapWrap{position:relative;max-width:1200px;margin:0 auto;border-radius:16px;overflow:hidden;background:#0b1220;box-shadow:0 12px 30px rgba(0,0,0,.18);}
      #WorldMapImage{display:block;width:100%;height:auto;user-select:none;-webkit-user-drag:none;}
      #WorldMapOverlay{position:absolute;inset:0;pointer-events:none;}
      .WM_node{position:absolute;width:14px;height:14px;margin-left:-7px;margin-top:-7px;border-radius:50%;background:rgba(255,255,255,.9);box-shadow:0 0 0 3px rgba(0,0,0,.35),0 0 18px rgba(255,255,255,.35);pointer-events:auto;cursor:pointer;z-index:12}
      .WM_node:hover{transform:scale(1.12)}
      .WM_node--pc{background:rgba(80,200,255,.95);box-shadow:0 0 0 3px rgba(0,0,0,.35),0 0 18px rgba(80,200,255,.45)}

      .WM_actions{display:flex;gap:10px;justify-content:flex-end;margin-top:10px}
      .WM_btn{border:1px solid rgba(255,255,255,.15);background:rgba(15,23,42,.85);color:#fff;padding:10px 12px;border-radius:12px;font-weight:700;cursor:pointer}
      .WM_btn:hover{background:rgba(30,41,59,.92)}
      .WM_hint{margin-top:8px;color:#64748b;font-size:12px}
    </style>

    <div id="WorldMapHead">
      <div id="WorldMapCurrentLoc">Текущая локация: —</div>
      <div id="WorldMapLegend">
        <span><i class="WM_dot WM_dot--me"></i>Вы</span>
        <span><i class="WM_dot WM_dot--road"></i>Переход</span>
        <span><i class="WM_dot WM_dot--pc"></i>Покецентр</span>
      </div>
    </div>

    <div id="WorldMapWrap">
      <img id="WorldMapImage" src="'.$mapPathPrimary.'" alt="Карта"
           onerror="this.onerror=null; this.src=\''.$mapPathFallback.'\';" />
      <div id="WorldMapOverlay"></div>
    </div>

    <div class="WM_actions" id="WorldMapActions"></div>
    <div class="WM_hint" id="WorldMapHint">Если точки не отображаются — проверьте, что /do/updateLocation.php возвращает player.map_x/map_y и roads[].map_x/map_y.</div>
    ';

    $response["html"] = $tpl;
    break;


				case 'repair':
                    $tpl.= '<div class="Title"><div class="Name">Починка предметов</div><div class="Info">Чините предметы и используйте их снова</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>';
				    $tpl .= '<div class="Repair">
				        <div id="itemRep" class="blockrepair" onclick="issetAll(\'1\',\'Repair\')" data-title="0"><img src="/img/world/plus.png"><div>Предмет</div></div>
				        <div id="potionRep" class="blockrepair" onclick="issetAll(\'2\',\'Repair\')" data-title="0"><img src="/img/world/plus.png"><div>Зелье</div></div>
				        <div class="settingsrepair">
				        Для починки предметов необходим Сок из листьев Оддиша.<br>1 прочность = 5% сока.<br>Сок восстанавливает до максимальной прочности если есть такая возможность.
				        </div>
				        <div class="button" onclick="repair()">Починить</div>
				    </div>

				    ';
				$tpl .= '</div>';
			$response["html"] = $tpl;
    break;
				case 'lvlpr':

    // --- данные пользователя ---
    $user = $mysqli->query('SELECT * FROM `users` WHERE `id`='.(int)$_SESSION['id'].' LIMIT 1')->fetch_assoc();

    // --- список наград ---
    $trophies = [];
    $qT = $mysqli->query('SELECT * FROM `base_trophy` ORDER BY `id` ASC');
    while ($row = $qT->fetch_assoc()) { $trophies[] = $row; }

    // --- уже полученные ---
    $claimed = [];
    $qC = $mysqli->query('SELECT `lvl` FROM `base_trophy_user` WHERE `user`='.(int)$_SESSION['id']);
    while ($r = $qC->fetch_assoc()) { $claimed[(int)$r['lvl']] = true; }

    // --- хелперы ---
    if (!function_exists('lp_strip_scripts')) {
        function lp_strip_scripts($s) {
            // Убираем только <script>...</script>, остальной HTML оставляем (иконки/onclick остаются рабочими)
            return preg_replace('~<\s*script[^>]*>.*?<\s*/\s*script\s*>~is', '', (string)$s);
        }
    }

    // --- раскладываем по статусам ---
    $avail = []; $got = []; $lock = [];
    foreach ($trophies as $t) {
        $lvlId = (int)$t['id'];
        if (isset($claimed[$lvlId]))         $got[]   = $t;
        elseif ((int)$user['lvl'] > $lvlId)  $avail[] = $t;
        else                                 $lock[]  = $t;
    }

    // --- шапка модалки ---
    $tpl  = '<div class="Title">
               <div class="Name">Награды за уровень</div>
               <div class="Info">Повышайте уровень и забирайте призы</div>
               <div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
             </div>';

    // --- стили + контейнер ---
    $tpl .= '
<style>
:root{
  --lp-bg:#f6f8ff; --lp-b:#e7ecff; --lp-card:#ffffff; --lp-title:#0e55b6; --lp-sub:#6e7aa4;
  --lp-ok:#16a36a; --lp-warn:#7d7cf8; --lp-lock:#9fb0d9;
  --lp-grad:linear-gradient(135deg, #2f74ff 0%, #0e55b6 100%);
  --lp-shadow:0 14px 32px rgba(18,30,70,.10);
}

/* скроллим контент внутри модалки */
.lvlprContainer{
  padding:12px;
  background:var(--lp-bg);
  border-top:1px solid var(--lp-b);
  max-height:calc(100vh - 180px);
  overflow:auto;
  border-radius:12px;
}

/* верхняя панель */
.lvlprHeader{
  display:flex; gap:14px; align-items:center; justify-content:space-between;
  background:var(--lp-card); border:1px solid var(--lp-b); border-radius:16px; padding:14px 16px;
  box-shadow:var(--lp-shadow); margin-bottom:12px;
}
.lvlNow{ display:flex; align-items:center; gap:12px; }
.lvlNow .badge{
  width:52px; height:52px; border-radius:14px; display:grid; place-items:center;
  background:var(--lp-grad); color:#fff; font:900 18px/1 Nunito,Arial,sans-serif;
  box-shadow:0 10px 22px rgba(47,116,255,.30);
}
.lvlNow .text{ color:#22345e; font:900 18px/1.2 Nunito,Arial,sans-serif; }
.lvlNow .sub{ color:var(--lp-sub); font:700 12px/1.3 Nunito,Arial,sans-serif; }

.lvlNav{ display:flex; gap:8px; flex-wrap:wrap; }
.lvlNav a{
  text-decoration:none; border:1px solid var(--lp-b); background:#f4f7ff; color:#2b3e73;
  font:800 12.5px/1 Nunito,Arial; padding:9px 12px; border-radius:12px;
}
.lvlNav a:hover{ background:#edf3ff; }

/* заголовки секций */
.lvlSection{ margin:10px 0 14px; }
.lvlSection .title{
  display:flex; align-items:center; gap:8px; margin:6px 2px 10px;
  color:#22345e; font:900 15px/1.2 Nunito,Arial;
}
.dot{ width:10px; height:10px; border-radius:50%; }
.dot.ok{ background:var(--lp-ok); } .dot.got{ background:var(--lp-warn); } .dot.lock{ background:var(--lp-lock); }

/* сетка карточек */
.lvlGrid{
  display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
  gap:16px;
}

/* карточка награды */
.trophyCard{
  background:var(--lp-card);
  border:1px solid var(--lp-b);
  border-radius:16px;
  padding:16px 16px 14px;
  box-shadow:var(--lp-shadow);
  transition:transform .12s ease, box-shadow .12s ease;
  position:relative;
}
.trophyCard:hover{ transform:translateY(-2px); box-shadow:0 18px 34px rgba(18,30,70,.14); }

/* плашка статуса */
.stateChip{
  position:absolute; top:10px; right:10px;
  display:inline-flex; align-items:center; gap:6px;
  font:800 11px/1 Nunito,Arial; padding:7px 10px; border-radius:999px;
  border:1px solid var(--lp-b); background:#f5f7ff; color:#2a3f73;
}
.stateChip i{opacity:.85}
.state-ok{ background:#e9fbf3; color:#0e6f49; border-color:#c7f0df; }
.state-got{ background:#f3ecff; color:#5a3f9f; border-color:#e5d7ff; }
.state-lock{ background:#f6f7fb; color:#6d7591; border-color:#e6e9f6; }

/* шапка карточки */
.tcTop{ display:flex; align-items:center; gap:12px; margin-bottom:10px; }
.levelCircle{
  min-width:38px; height:38px; border-radius:12px; display:grid; place-items:center;
  background:linear-gradient(180deg,#fafcff,#eef3ff);
  border:1px solid var(--lp-b); color:#0e55b6; font:900 14px Nunito,Arial;
}
.tcTitle{ font:900 15px/1.2 Nunito,Arial; color:#22345e; }

/* блок наград (ВАШ HTML ВНУТРИ) */
.tcRewards{
  color:var(--lp-sub); font:600 13.5px/1.55 Nunito,Arial;
  background:#f9fbff; border:1px dashed #d9e3ff; border-radius:12px;
  padding:10px; margin-top:6px;
}
/* делаем красивую строку с иконками/текстом, как в вашем <div class="trophy"> */
.tcRewards .trophy{
  display:flex; align-items:center; gap:10px; flex-wrap:wrap;
}
.tcRewards .itemisset{
  width:50px; height:50px; border-radius:12px;
  background-size:cover; background-position:center; background-repeat:no-repeat;
  border:1px solid var(--lp-b); box-shadow:0 3px 8px rgba(20,35,80,.10);
}
.tcRewards b{ color:#2a3f73; }

/* низ карточки */
.tcActions{ display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
.btn{
  appearance:none; border:1px solid var(--lp-b); background:#f6f9ff; color:#2b3e73;
  border-radius:12px; padding:10px 14px; font:900 13px Nunito,Arial; cursor:pointer; transition:.12s;
}
.btn.primary{
  background:var(--lp-grad); color:#fff; border-color:#2f74ff;
  box-shadow:0 10px 22px rgba(47,116,255,.28);
}
.btn[disabled]{ opacity:.6; cursor:not-allowed; }

/* мобильная адаптация */
@media (max-width:700px){
  .lvlprContainer{ max-height:calc(100vh - 140px); }
  .lvlprHeader{ flex-direction:column; align-items:flex-start; gap:10px; }
  .lvlNow .text{ font-size:16px; }
  .lvlGrid{ grid-template-columns:1fr; }
}
</style>

<div class="lvlprContainer">
  <div class="lvlprHeader">
    <div class="lvlNow">
      <div class="badge">'.(int)$user['lvl'].'</div>
      <div>
        <div class="text">Текущий уровень</div>
        <div class="sub">Забирайте награды по мере роста</div>
      </div>
    </div>
    <div class="lvlNav">
      <a href="#sec-available">Доступно</a>
      <a href="#sec-claimed">Получено</a>
      <a href="#sec-upcoming">Предстоит</a>
    </div>
  </div>

  <!-- Доступно -->
  <div id="sec-available" class="lvlSection">
    <div class="title"><span class="dot ok"></span>Доступно к получению</div>
    <div class="lvlGrid">';
    if (!$avail) {
        $tpl .= '<div class="trophyCard">
                  <div class="stateChip"><i class="fas fa-info-circle"></i> —</div>
                  <div class="tcTop"><div class="levelCircle">—</div><div class="tcTitle">Пока нет доступных наград</div></div>
                  <div class="tcRewards">Как только ваш уровень превысит требуемый — здесь появится кнопка <b>Получить</b>.</div>
                </div>';
    } else {
        foreach ($avail as $a) {
            $tpl .= '<div class="trophyCard">
                      <div class="stateChip state-ok"><i class="fas fa-gift"></i> Можно получить</div>
                      <div class="tcTop">
                        <div class="levelCircle">'.(int)$a['id'].'</div>
                        <div class="tcTitle">Награда за уровень <b>'.(int)$a['id'].'</b></div>
                      </div>
                      <div class="tcRewards">'. lp_strip_scripts($a['text']) .'</div>
                      <div class="tcActions">
                        <button class="btn primary" onclick="trophyuserlvl('.(int)$a['id'].')">Получить</button>
                      </div>
                    </div>';
        }
    }
    $tpl .= '</div>
  </div>

  <!-- Получено -->
  <div id="sec-claimed" class="lvlSection">
    <div class="title"><span class="dot got"></span>Полученные награды</div>
    <div class="lvlGrid">';
    if (!$got) {
        $tpl .= '<div class="trophyCard">
                  <div class="stateChip"><i class="fas fa-info-circle"></i> —</div>
                  <div class="tcTop"><div class="levelCircle">—</div><div class="tcTitle">Вы ещё не получили ни одной награды</div></div>
                  <div class="tcRewards">Выполняйте условия уровней — и история наград появится здесь.</div>
                </div>';
    } else {
        foreach ($got as $a) {
            $tpl .= '<div class="trophyCard">
                      <div class="stateChip state-got"><i class="fas fa-check"></i> Получено</div>
                      <div class="tcTop">
                        <div class="levelCircle">'.(int)$a['id'].'</div>
                        <div class="tcTitle">За уровень <b>'.(int)$a['id'].'</b></div>
                      </div>
                      <div class="tcRewards">'. lp_strip_scripts($a['text']) .'</div>
                    </div>';
        }
    }
    $tpl .= '</div>
  </div>

  <!-- Предстоит -->
  <div id="sec-upcoming" class="lvlSection">
    <div class="title"><span class="dot lock"></span>Предстоящие награды</div>
    <div class="lvlGrid">';
    if (!$lock) {
        $tpl .= '<div class="trophyCard">
                  <div class="stateChip"><i class="fas fa-info-circle"></i> —</div>
                  <div class="tcTop"><div class="levelCircle">∞</div><div class="tcTitle">Все награды получены</div></div>
                  <div class="tcRewards">Похоже, вы достигли всех доступных порогов.</div>
                </div>';
    } else {
        foreach ($lock as $a) {
            $tpl .= '<div class="trophyCard">
                      <div class="stateChip state-lock"><i class="fas fa-lock"></i> Недоступно</div>
                      <div class="tcTop">
                        <div class="levelCircle">'.(int)$a['id'].'</div>
                        <div class="tcTitle">Награда на уровне <b>'.(int)$a['id'].'</b></div>
                      </div>
                      <div class="tcRewards">'. lp_strip_scripts($a['text']) .'</div>
                      <div class="tcActions">
                        <button class="btn" disabled>Требуется уровень '.(int)$a['id'].'+</button>
                      </div>
                    </div>';
        }
    }
    $tpl .= '</div>
  </div>
</div>';

    $response['html'] = $tpl;
    break;

				case 'calendar':
				    $us = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();

				    $miss = $mysqli->query('SELECT * FROM `user_mission_day` WHERE `user` = '.$_SESSION['id'].' AND `end`= 0')->fetch_assoc();
					$tpl = '<div class="Title"><div class="Name">Календарь мероприятий</div><div class="Info">Выполняйте ежедневные задания и следите за мероприятиями в игре.</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>
					<div class="DivCraft">
				<div class="CraftCategory">
					<div class="Button today active" onclick="calendarCategory(\'today\')">Сегодня</div>
				</div>
				<div class="CraftContent">
                    <div class="CalendarHead">Задания на сегодня</div>
              <div class="CalendarPrewiev">
                Выполняйте задания каждый день и получайте награду.
              </div><div class="TasksList"> ';


              if($us['mission_day'] == 1){
                  if($miss){
                      $mission = $mysqli->query('SELECT * FROM `user_mission_day` WHERE `user` = '.$_SESSION['id'].' AND `end`= 0');
                      while($mis = $mission->fetch_assoc()){
                          if($mis['id_mission'] == 1){ $text = "Поймайте 20 покемонов";}
                          elseif($mis['id_mission'] == 2){ $text = 'Получите яйцо <span class="intextpoke sp165" onclick="openDex(165)">#165 Ледиба</span>'; }
                          elseif($mis['id_mission'] == 3){ $text = 'Выбейте Острый клюв х1'; }
                          elseif($mis['id_mission'] == 4){ $text = 'Выбейте Набор тренировки х1'; }
                          elseif($mis['id_mission'] == 5){ $text = 'Победите 200 покемонов в pve'; }
                          elseif($mis['id_mission'] == 6){ $text = 'Потратьте 30.000 генкаров на лечение покемонов'; }
                          elseif($mis['id_mission'] == 7){ $text = 'Найдите любые крышечки х5'; }
                          elseif($mis['id_mission'] == 8){ $text = 'Поймайте <span class="intextpoke sp280" onclick="openDex(280)">#280 Ралтс</span>'; }
                          elseif($mis['id_mission'] == 9){ $text = 'Получите яйцо <span class="intextpoke sp16" onclick="openDex(16)">#016 Пиджи</span>'; }
                          elseif($mis['id_mission'] == 10){ $text = 'Получите яйцо <span class="intextpoke sp92" onclick="openDex(92)">#092 Гастли</span>'; }
                          elseif($mis['id_mission'] == 11){ $text = 'Получите яйцо <span class="intextpoke sp194" onclick="openDex(194)">#194 Вупер</span>'; }
                          elseif($mis['id_mission'] == 12){ $text = 'Выбейте Кварц х10'; }
                          elseif($mis['id_mission'] == 13){ $text = 'Поучаствуйте в 8 pvp боях'; }
                          elseif($mis['id_mission'] == 14){ $text = 'Получите 1.500 опыта'; }
                          elseif($mis['id_mission'] == 15){ $text = 'Поймайте 5 любых покемонов с характером Обычный'; }
                          elseif($mis['id_mission'] == 16){ $text = 'Получите яйцо любого покемона каменного типа'; }
                          elseif($mis['id_mission'] == 17){ $text = 'Используйте в боях не менее 25 предметов'; }
                          elseif($mis['id_mission'] == 18){ $text = 'Используйте на покемонов суммарно не менее 15 конфет'; }
                          elseif($mis['id_mission'] == 19){ $text = 'Используйте любую пилюлю один раз'; }
                          elseif($mis['id_mission'] == 20){ $text = 'Добавьте покемонам 30ev витаминами'; }
                          elseif($mis['id_mission'] == 21){ $text = 'Найдите любые априкорны x5'; }
                          elseif($mis['id_mission'] == 22){ $text = 'Используйте любой эволвер'; }
                          elseif($mis['id_mission'] == 23){ $text = 'Используйте Портативный инкубатор'; }
                          elseif($mis['id_mission'] == 24){ $text = 'Получите яйцо водного покемона'; }
                          elseif($mis['id_mission'] == 25){ $text = 'Получите яйцо травяного покемона'; }
                          elseif($mis['id_mission'] == 26){ $text = 'Используйте 15 критических ударов'; }
                          elseif($mis['id_mission'] == 27){ $text = 'Погуляйте с покемонами 15 раз'; }
                          elseif($mis['id_mission'] == 28){ $text = 'Отправьте в пункт переработки 10 предметов'; }





                          $hint = 'Если прогресс не меняется — обновите календарь. В некоторых действиях есть задержка обновления до ~60 сек.';
                          switch((int)$mis['id_mission']){
                            case 1: $hint = 'Засчитывается только успешная поимка (если покемон пойман и появился в списке). Срыв шара/побег обычно не считается. Если прогресс не меняется — обновите календарь, иногда есть задержка до ~60 сек.'; break;
                            case 2: $hint = 'Нужно получить яйцо #165 Ледиба (чтобы яйцо появилось в вашем списке яиц). Разведение/ивентовые способы — зависит от механики сервера. Если не засчиталось — проверьте, что задание активно сегодня, и обновите календарь (задержка до ~60 сек).'; break;
                            case 3: $hint = 'Засчитывается при зачислении предмета «Острый клюв» в инвентарь. Если предмет выпал, но не был получен/зачислен — прогресс не пойдёт. Обновите календарь (иногда задержка до ~60 сек).'; break;
                            case 4: $hint = 'Засчитывается при получении «Набор тренировки» в инвентарь. Если предмет выпал, но не был зачислен — прогресс не пойдёт. Возможна задержка обновления до ~60 сек.'; break;
                            case 5: $hint = 'Засчитываются победы в PvE (дикие/тренеры) после завершения боя. Поражения/выход из боя обычно не учитываются. Если прогресс стоит — обновите календарь (возможна задержка).'; break;
                            case 6: $hint = 'Считаются траты на лечение покемонов (как правило, именно в механике лечения/покецентре). Покупки/прочие траты обычно не засчитываются. Если не засчитало — проверьте место лечения и обновите календарь.'; break;
                            case 7: $hint = 'Считается получение любых «крышечек» (за счёт находок/наград) при зачислении в инвентарь. Если прогресс не меняется — обновите календарь (задержка до ~60 сек).'; break;
                            case 8: $hint = 'Нужна успешная поимка #280 Ралтс. Эволюция/обмен обычно не считаются. Обновление прогресса может быть с задержкой до ~60 сек.'; break;
                            case 9: $hint = 'Нужно получить яйцо #016 Пиджи (должно появиться в списке яиц). Если не засчиталось — обновите календарь и проверьте, что задание активно.'; break;
                            case 10: $hint = 'Нужно получить яйцо #092 Гастли (должно появиться в списке яиц). Возможна задержка обновления до ~60 сек.'; break;
                            case 11: $hint = 'Нужно получить яйцо #194 Вупер (должно появиться в списке яиц). Если не засчиталось — обновите календарь.'; break;
                            case 12: $hint = 'Считается зачисление «Кварц» в инвентарь суммарно x10. Если добываете пачками — прогресс может обновляться не мгновенно (до ~60 сек).'; break;
                            case 13: $hint = 'Засчитываются завершённые PvP-бои (как правило, после результата). Отмены/выход могут не считаться. Если прогресс стоит — обновите календарь.'; break;
                            case 14: $hint = 'Считается получение опыта суммарно (из боёв/наград). Иногда обновление идёт с задержкой до ~60 сек — просто обновите календарь.'; break;
                            case 15: $hint = 'Считаются ПОЙМАННЫЕ сегодня покемоны с характером «Обычный». Старые покемоны не подходят. Проверьте характер в карточке и обновите календарь при задержке.'; break;
                            case 16: $hint = 'Нужно получить яйцо покемона каменного типа (важно, чтобы яйцо появилось в списке яиц). Если не засчитало — обновите календарь.'; break;
                            case 17: $hint = 'Считаются предметы, использованные прямо в бою (не вне боя). Если предмет «не применился» — прогресс не пойдёт. Возможна задержка обновления.'; break;
                            case 18: $hint = 'Считаются конфеты, успешно применённые на покемонов суммарно (не просто покупка). Если упёрлись в ограничения — может не засчитываться. Обновите календарь.'; break;
                            case 19: $hint = 'Считается успешное применение любой пилюли на покемона. Если действие отклонено (условия/лимиты) — прогресс не пойдёт. Обновите календарь.'; break;
                            case 20: $hint = 'Считаются EV, добавленные витаминами суммарно на 30. Если EV не добавились из‑за лимитов — задание не продвинется. Обновление прогресса может быть с задержкой.'; break;
                            case 21: $hint = 'Считаются найденные априкорны x5 при зачислении в инвентарь. Если прогресс стоит — обновите календарь (задержка до ~60 сек).'; break;
                            case 22: $hint = 'Считается успешное использование эволвера (предмета эволюции). Если эволюция не произошла (условия не выполнены) — прогресс не пойдёт.'; break;
                            case 23: $hint = 'Считается применение «Портативного инкубатора». Если предмет не применился из‑за условий (например, уже активен) — может не засчитаться.'; break;
                            case 24: $hint = 'Нужно получить яйцо водного покемона (яйцо должно появиться в списке). Обновите календарь при задержке.'; break;
                            case 25: $hint = 'Нужно получить яйцо травяного покемона (яйцо должно появиться в списке). Обновите календарь при задержке.'; break;
                            case 26: $hint = 'Считаются критические удары в бою суммарно 15 (обычно после завершения боя). Если прогресс не двигается — обновите календарь.'; break;
                            case 27: $hint = 'Считаются завершённые прогулки с покемонами (механика прогулки должна отработать и выдать результат). Если не засчитало — обновите календарь.'; break;
                            case 28: $hint = 'Считаются предметы, отправленные в пункт переработки (не выброшенные). Если отправка не прошла — прогресс не пойдёт. Возможна задержка обновления.'; break;
                          }

                          if($mis['this_process'] == $mis['end_process'] and $mis['this_process'] >= 1){ $bar = '<button onclick="successMission('.$mis['id_mission'].')">Сдать</button>';}
                          elseif($mis['this_process'] != $mis['end_process'] and $mis['end_process'] > 1){ $pr = $mis['this_process']/$mis['end_process']*100; $bar = '<div class="TaskBar" data-title="'.$mis['this_process'].' / '.$mis['end_process'].'"><div style="width: '.$pr.'%"></div></div>';}
                          else{ $bar = ''; }
                          $tpl .= '<div class="Task">
                  <div class="TaskLeft">
                    <div class="TaskText">'.$text.' <span class="TaskHint" data-hint="'.htmlspecialchars($hint, ENT_QUOTES).'"><i class="far fa-question-circle"></i></span></div>
                    <div class="TaskProgress">
                      '.$bar.'
                    </div>
                  </div>
                  <div class="TaskImage">
                    <img src="/img/world/items/little/'.$mis['present_id'].'.png" onclick="issetAll('.$mis['present_id'].')" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');" ><span>x'.$mis['present_count'].'</span>
                  </div>
                </div>';
                      }
                  }else{
                      $tpl .= '<h2>У вас не осталось заданий на сегодня!</h2>';
                  }
              }else{
                  $tpl .= '<h2>Вы не включили получение заданий в настройках!</h2>';
              }
                $tpl .= '</div>';
                  $tpl .= '<div class="CalendarHead">Сражения с боссом</div>
              <div class="CalendarPrewiev">
                Сражайтесь с текущими боссами и получайте награду за победу над ними.
              </div>
              <div class="BossListCalendar">';
                  $boss = $mysqli->query('SELECT * FROM `base_boss` WHERE `user` = "'.$_SESSION['id'].'" AND `prize` != 1 AND `time` > "'.time().'" ');
                  if($boss->num_rows >= 1){
                  while($bs = $boss->fetch_assoc()){
                      if($bs['death'] != 1){
                      $star = '';
                      for($i=0;$i<$bs['type'];$i++){
                          $star .= '<i class="fas fa-star"></i>';
                      }
                      $loc = $mysqli->query('SELECT * FROM `base_location` WHERE `id` = "'.$bs['location'].'" ')->fetch_assoc();
                      $tpl .= ' <div class="BossBlock">
                                    <div class="imgPokBoss"><img src="/img/pokemons/animation/'.numbPok($bs['basenum']).'.png"></div>
                                    <div class="InfoPokBoss">
                                        <div class="TypeBoss">
                                            '.$star.'
                                        </div>
                                        <div class="LocationBoss">
                                            <i class="fas fa-map-marker-alt"></i> '.$loc['name'].'
                                        </div>
                                        <div class="TimeBoss">
                                            <i class="fas fa-clock"></i> до '.date("H:i",$bs['time']).'
                                        </div>
                                    </div>
                                </div>';
                      }else{
                          $tpl .= ' <div class="BossBlock">
                                    <div class="imgPokBoss"><img src="/img/pokemons/animation/'.numbPok($bs['basenum']).'.png"></div>
                                    <div class="InfoPokBoss">
                                        <div class="BtnBoss">
                                            <div onclick="BossPrize('.$bs['id'].')">Забрать приз</div>
                                        </div>
                                        <div class="TimeBoss">
                                            <i class="fas fa-clock"></i> до '.date("H:i",$bs['time']).'
                                        </div>
                                    </div>
                                </div>';
                      }
                  }
                  }else{
                      $tpl .= '<center><h2>~ Боссы отсутствуют ~</h2></center>';
                  }
                $tpl .= '</div>';  
              
              

					$response["html"] = $tpl;
				break;
				case 'atcdex':
				$tpl.= '<div class="Title">
									<div class="Name">Атакдекс</div>
									<div class="Info">Узнайте информацию об атаках</div>
									<div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
								</div>
								<div class="AtcdexList"><div class="ListAtack">';
						$atc = $mysqli->query('SELECT * FROM `base_atk` ORDER BY `name_rus` ASC LIMIT 20');
					while($atcs = $atc->fetch_assoc()){
						if($atcs['power'] == 0){ $power = "-"; }else{ $power = $atcs['power']; }
						if($atcs['accuracy'] == 0){ $acc = "-"; }else{ $acc = $atcs['accuracy']; }
						if($atcs['type'] == "normal"){$type = "Нормальный";}
						elseif($atcs['type'] == "fighting"){$type = "Боевой";}
						elseif($atcs['type'] == "fly"){$type = "Летающий";}
						elseif($atcs['type'] == "poison"){$type = "Ядовитый";}
						elseif($atcs['type'] == "ground"){$type = "Земляной";}
						elseif($atcs['type'] == "rock"){$type = "Каменный";}
						elseif($atcs['type'] == "bug"){$type = "Насекомый";}
						elseif($atcs['type'] == "ghost"){$type = "Призрачный";}
						elseif($atcs['type'] == "fire"){$type = "Огненный";}
						elseif($atcs['type'] == "water"){$type = "Водный";}
						elseif($atcs['type'] == "grass"){$type = "Травяной";}
						elseif($atcs['type'] == "electric"){$type = "Электрический";}
						elseif($atcs['type'] == "psychic"){$type = "Психический";}
						elseif($atcs['type'] == "ice"){$type = "Ледяной";}
						elseif($atcs['type'] == "dragon"){$type = "Драконий";}
						elseif($atcs['type'] == "dark"){$type = "Темный";}
						elseif($atcs['type'] == "steel"){$type = "Стальной";}
						elseif($atcs['type'] == "fairy"){$type = "Волшебный";}

						if($atcs['category'] == "specific"){ $cat = "Специф";}
						elseif($atcs['category'] == "physical"){ $cat = "Физ";}
						elseif($atcs['category'] == "special"){ $cat = "Спец";}
						elseif($atcs['category'] == "status"){ $cat = "Стат";}
						$tpl .= '	<div class="AtcBox" onclick="viewDescriptionAttak(this,'.$atcs['id'].');">
												<div class="InfoTop">
													<div class="Name">'.$atcs['name_rus'].'</div>
													<div class="Power">'.$power.'<div>Сила</div></div>
													<div class="Accuracy">'.$acc.'<div>Точн.</div></div>
													<div class="Pp">'.$atcs['pp'].'<div>PP</div></div>
												</div>
												<div class="InfoBottom">
													<div class="Type type'.$atcs['type'].' "><div class="TitleType">Тип:</div><div class="TypeAtc">'.$type.'</div></div>
													<div class="Category '.$atcs['category'].'">'.$cat.'</div>
												</div>
											</div>';
					}
$tpl.= '</div>';
					$tpl.= '<button class="AtcBtn" onclick="atcdexload()" data-title="1">Еще</button>';

					$tpl.= '</div>';
					$tpl.= '<div class="SortAtc"><button class="catcatack" onclick="issetAll(1,\'atcdex\')" data-title="all">Все Типы</button> <button class="catatack"  onclick="issetAll(1,\'atcdex2\')"  data-title="all" >Все Категории</button> </div>';
			$response["html"] = $tpl;
				break;
				case 'abldex':
				$tpl.= '<div class="Title">
									<div class="Name">Список способностей</div>
									<div class="Info">Узнайте информацию о способностях</div>
									<div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
								</div>
								<div class="AbldexList"><div class="ListAbility">';
						$abl = $mysqli->query('SELECT * FROM `base_ability` WHERE `about` != "..." ORDER BY `name_rus` ASC LIMIT 20');
					while($abls = $abl->fetch_assoc()){
						$tpl .= '	<div class="AblBox" onclick="viewDescriptionAbility('.$abls['id'].');">
												<div class="InfoTop">
													<div class="Name">'.$abls['name_rus'].'</div>
												</div>
												<div class="InfoBottom">
													<div class="Description">'.$abls['about'].'</div>
												</div>
											</div>';
					}
$tpl.= '</div>';
					$tpl.= '<button class="AblBtn" onclick="abldexload()" data-title="1">Еще</button>';

					$tpl.= '</div>';
					$response["html"] = $tpl;
				break;
				case 'thing':
				$tpl.= '<div class="Title"><div class="Name">Одежда</div><div class="Info">Улучшайте вещи чтобы получить больший бонус</div><div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div></div>';
			$response["html"] = $tpl;
				break;
    // case 'map':
        // $response["title"] = "Аквабук";
        // $tpl .= '<div class="tr-but">';
		// $tpl .=	'<div>Канто</div>';
		// $tpl .=	'<div>Джото</div>';
		// $tpl .=	'<div>Хоэнн</div>';
		// $tpl .=	'<div>О-ва Севии</div>';
		// $tpl .=	'<div>Синно</div>';
		// $tpl .=	'<div>Юнова</div>';
		// $tpl .=	'<div>Калос</div>';
		// $tpl .=	'</div>';
		// $tpl .=	'<div class="map-preview"></div>';
        // $response["html"] = $tpl;
        // break;
case 'shop':
    // Получаем ID пользователя из сессии
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['id']) ? intval($_SESSION['id']) : 0);
    if (!$user_id) {
        $tpl = '<div class="error">Ошибка: пользователь не найден!</div>';
        $response["html"] = $tpl;
        break;
    }
    // Получение количества Аметистов
    $stmt = $mysqli->prepare('SELECT count FROM items_users WHERE user = ? AND item_id = 25');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($emerald_count);
    $stmt->fetch();
    $stmt->close();
    $count = is_null($emerald_count) ? 0 : (int)$emerald_count;

    // Получение списков товаров по категориям
    $it_list         = $mysqli->query('SELECT * FROM aquarits WHERE type = 1');
    $it_list_packs   = $mysqli->query('SELECT * FROM aquarits WHERE type = 4');
    $it_list_skins   = $mysqli->query('SELECT * FROM aquarits WHERE type = 3');

    // Шапка магазина
    $tpl = '<div class="Title">
                <div class="Name">Аметистовый магазин</div>
                <div class="Info">Покупайте предметы за донат валюту</div>
                <div class="Close" onclick="closeModal()"><i class="fas fa-times"></i></div>
            </div>
            <div class="ContentShop">
                <div class="Preview">
                    <div class="balance-display">
                        <div class="item-icon"></div>
                        <span class="balance-info">x'.$count.'</span>
                    </div>
                    <button class="recharge-button" type="button"><i class="fas fa-wallet"></i> Пополнить</button>
                </div>
                <div class="CategoryMenu">
                    <div class="Category active" data-category="items" onclick="switchCategory(\'items\')"><i class="fas fa-box"></i> Товары</div>
                    <div class="Category" data-category="packs" onclick="switchCategory(\'packs\')"><i class="fas fa-boxes"></i> Наборы</div>
                    <div class="Category" data-category="skins" onclick="switchCategory(\'skins\')"><i class="fas fa-user-astronaut"></i> Скины</div>
                </div>
                <div class="ShopList">';

    // Товары
    $tpl .= '<div class="CategoryContent active" id="items">
                <div class="Name">Товары</div>
                <div class="ItemList">';
    if ($it_list && $it_list->num_rows > 0) {
        while ($th = $it_list->fetch_assoc()) {
            $count_display = ($th['count'] != 999) ? '<div class="item-count">x'.$th['count'].'</div>' : '';

            // --- Акционная цена для товаров ---
            $price = intval($th['price']);
            $sale_price = (isset($th['sale_price']) && $th['sale_price'] !== null) ? intval($th['sale_price']) : null;
            $show_price = ($sale_price && $sale_price > 0 && $sale_price < $price) ? $sale_price : $price;
            $price_html = '';
            if ($sale_price && $sale_price > 0 && $sale_price < $price) {
                $price_html = '<div class="price">
                                <span class="old-price" style="color:#a9a9a9;text-decoration:line-through;margin-right:7px;font-size:15px;">'.$price.'</span>
                                <span class="sale-label" style="background:#ffedd4;color:#ff9800;border-radius:4px;padding:2px 7px;font-size:13px;font-weight:500;margin-right:5px;">Акция!</span>
                                <i class="fas fa-gem"></i> '.$sale_price.'
                               </div>';
            } else {
                $price_html = '<div class="price"><i class="fas fa-gem"></i> '.$price.'</div>';
            }

            $tpl .= '<div class="Thing">
                        <div class="block"></div>
                        <img src="/img/world/items/little/'.$th['item'].'.png" onclick="issetAll('.$th['item'].',\'shop_it\')">
                        '.$count_display.'
                        '.$price_html.'
                    </div>';
        }
    } else {
        $tpl .= '<div class="error">Нет товаров для отображения.</div>';
    }
    $tpl .= '</div></div>';

    // Наборы
    $tpl .= '<div class="CategoryContent" id="packs">
        <div class="Name">Наборы</div>
        <div class="PackGrid">';
    if ($it_list_packs && $it_list_packs->num_rows > 0) {
        while ($th = $it_list_packs->fetch_assoc()) {
            $bundle_items = isset($bundles[$th['item']]) ? $bundles[$th['item']] : [];
            $pack_name = htmlspecialchars($th['name']);
            $pack_img = '/img/world/items/packs/' . $th['item'] . '.png';

            // --- Акционная цена для наборов ---
            $price = intval($th['price']);
            $sale_price = (isset($th['sale_price']) && $th['sale_price'] !== null) ? intval($th['sale_price']) : null;
            $show_price = ($sale_price && $sale_price > 0 && $sale_price < $price) ? $sale_price : $price;
            $price_html = '';
            if ($sale_price && $sale_price > 0 && $sale_price < $price) {
                $price_html = '<div class="PackCard-price">
                    <span class="old-price" style="color:#a9a9a9;text-decoration:line-through;margin-right:7px;font-size:15px;">'.$price.' алм.</span>
                    <span class="sale-label" style="background:#ffedd4;color:#ff9800;border-radius:4px;padding:2px 7px;font-size:13px;font-weight:500;margin-right:5px;">Акция!</span>
                    <i class="fas fa-gem"></i> '.$sale_price.' алм.
                </div>';
            } else {
                $price_html = '<div class="PackCard-price"><i class="fas fa-gem"></i> '.$price.' алм.</div>';
            }

            // -- НАЧАЛО основной карточки набора --
            $tpl .= '<div class="PackCard">
                <div class="PackCard-content">
                    <div class="PackCard-items">';
            // Состав набора
            if ($bundle_items && count($bundle_items) > 0) {
                foreach ($bundle_items as $item) {
                    $base = $mysqli->query("SELECT name FROM base_items WHERE id = ".intval($item['item_id']))->fetch_assoc();
                    $item_name = $base ? htmlspecialchars($base['name']) : '';
                    $tpl .= '<div class="PackCard-item">
                        <img src="/img/world/items/little/'.$item['item_id'].'.png" title="'.$item_name.'">
                        <span>x'.$item['count'].'</span>
                    </div>';
                }
            } else {
                $tpl .= '<span style="color:#888;">Нет содержимого</span>';
            }
            $tpl .= '</div>
                    '.$price_html.'
                </div>
                <div class="PackCard-bg">
                    <img src="'.$pack_img.'" alt="'.$pack_name.'">
                </div>
                <div class="PackCard-overlay" onclick="issetAll('.$th['item'].',\'shop_it\')" style="position:absolute;left:0;top:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>
            </div>';
            // -- КОНЕЦ основной карточки набора --
        }
    } else {
        $tpl .= '<div class="error">Нет наборов для отображения.</div>';
    }
    $tpl .= '</div></div>';

    // Скины (добавлен только значок пола рядом с картинкой)
    $tpl .= '<div class="CategoryContent" id="skins">
        <div class="Name">Скины</div>
        <div class="ItemList">';
    if ($it_list_skins && $it_list_skins->num_rows > 0) {
        while ($th = $it_list_skins->fetch_assoc()) {
            $count_display = ($th['count'] != 999) ? '<div class="item-count">x'.$th['count'].'</div>' : '';

            // --- Акционная цена для скинов ---
            $price = intval($th['price']);
            $sale_price = (isset($th['sale_price']) && $th['sale_price'] !== null) ? intval($th['sale_price']) : null;
            $show_price = ($sale_price && $sale_price > 0 && $sale_price < $price) ? $sale_price : $price;
            $price_html = '';
            if ($sale_price && $sale_price > 0 && $sale_price < $price) {
                $price_html = '<div class="price">
                                <span class="old-price" style="color:#a9a9a9;text-decoration:line-through;margin-right:7px;font-size:15px;">'.$price.'</span>
                                <span class="sale-label" style="background:#ffedd4;color:#ff9800;border-radius:4px;padding:2px 7px;font-size:13px;font-weight:500;margin-right:5px;">Акция!</span>
                                <i class="fas fa-gem"></i> '.$sale_price.'
                               </div>';
            } else {
                $price_html = '<div class="price"><i class="fas fa-gem"></i> '.$price.'</div>';
            }

            // --- Бейдж пола скина (берём из base_items.sex) ---
            $sexRow = $mysqli->query("SELECT `sex` FROM `base_items` WHERE `id` = ".intval($th['item'])." LIMIT 1")->fetch_assoc();
            $skinSex = isset($sexRow['sex']) ? strtolower($sexRow['sex']) : 'all';
            if ($skinSex === 'm') {
                $sexBadge = '<span class="skin-sex-badge skin-sex-m" title="Мужской">♂</span>';
            } elseif ($skinSex === 'f') {
                $sexBadge = '<span class="skin-sex-badge skin-sex-f" title="Женский">♀</span>';
            } else {
                $sexBadge = '<span class="skin-sex-badge skin-sex-all" title="Подходит всем">◎</span>';
            }

            $tpl .= '<div class="Thing skin">
                        <img src="/img/world/items/little/'.$th['item'].'.png" onclick="issetAll('.$th['item'].',\'shop_it\')">
                        '.$sexBadge.'
                        '.$count_display.'
                        '.$price_html.'
                        <div style="flex:1 1 auto"></div>
                        <button 
                            class="skin-preview-btn" 
                            data-item="'.$th['item'].'"
                            onclick="previewSkin(this); return false;"
                        >Предпросмотр</button>
                    </div>';
        }
    } else {
        $tpl .= '<div class="error">Нет скинов для отображения.</div>';
    }
    $tpl .= '</div></div>';

    $tpl .= '</div></div>'; // Завершение ShopList и ContentShop

    // Минимальные стили для бейджа пола (не меняют вашу структуру)
    $tpl .= '<style>
        .Thing.skin .skin-sex-badge{
            display:inline-flex;align-items:center;justify-content:center;
            width:18px;height:18px;border-radius:50%;
            font-size:11px;font-weight:900;line-height:1;
            margin-left:-14px;transform:translateY(-6px);
            border:1px solid #e2e8f0;background:#f1f5f9;color:#334155;
        }
        .Thing.skin .skin-sex-badge.skin-sex-m{background:#e9f3ff;border-color:#d0e6ff;color:#0b4da8}
        .Thing.skin .skin-sex-badge.skin-sex-f{background:#fff0f5;border-color:#ffd7e6;color:#a10b6a}
        .Thing.skin .skin-sex-badge.skin-sex-all{background:#eef2f7;border-color:#e3e8ef;color:#334155}
    </style>';

    $response["html"] = $tpl;
    break;

case 'clanCard':
    // Получаем ID клана
    $clanID = isset($_POST['id']) ? clearInt($_POST['id']) : 0;
    if ($clanID < 1) {
        $response['text'] = 'Некорректный идентификатор клана!';
        break;
    }

    // 1) Информация о клане (prepared)
    $stmtInfo = $mysqli->prepare('
        SELECT `info`,`position`,`rating`,`money`,`level`,`exp`,`exp_next`
        FROM `base_clans`
        WHERE `id` = ?
        LIMIT 1
    ');
    $stmtInfo->bind_param('i', $clanID);
    $stmtInfo->execute();
    $resInfo = $stmtInfo->get_result();
    $info    = $resInfo ? $resInfo->fetch_assoc() : null;
    $stmtInfo->close();

    if (!$info) {
        $response['text'] = 'Клан не найден!';
        break;
    }

    // 2) Пользователь в клане (если есть)
    $userIdSession = (int)($_SESSION['id'] ?? 0);
    $stmtMy = $mysqli->prepare('SELECT `clan_id`,`group` FROM `base_clans_users` WHERE `user_id` = ? LIMIT 1');
    $stmtMy->bind_param('i', $userIdSession);
    $stmtMy->execute();
    $resMy = $stmtMy->get_result();
    $userClanMy = $resMy ? $resMy->fetch_assoc() : null;
    $stmtMy->close();

    $a = $userClanMy['clan_id'] ?? null; // id клана пользователя
    $b = $userClanMy['group']   ?? null; // группа пользователя

    // 3) Участники клана
    $stmtUsers = $mysqli->prepare('
        SELECT bsu.*, u.`login`, u.`user_group`
        FROM `base_clans_users` AS bsu
        INNER JOIN `users` AS u ON u.`id` = bsu.`user_id`
        WHERE bsu.`clan_id` = ?
        ORDER BY bsu.`group` ASC, bsu.`id` ASC
    ');
    $stmtUsers->bind_param('i', $clanID);
    $stmtUsers->execute();
    $usersList = $stmtUsers->get_result();

    if ($usersList && $usersList->num_rows > 0) {
        $usersData = [];
        while ($users = $usersList->fetch_assoc()) {
            // ВАЖНО: статус оставляем строкой, чтобы фронт корректно отрисовывал звание
            $login      = (string)$users['login'];
            $user_group = (int)$users['user_group'];
            $raiting    = (int)$users['raiting']; // поле так и называется в БД
            $status     = (string)($users['status'] ?? ''); // БЫЛО: (int) — ломало фронт
            $group      = (int)$users['group'];
            $user_id    = (int)$users['user_id'];

            // Совместимый формат: "login,user_group,raiting,status,group,clanID,myClanId,myGroup,user_id"
            $usersData[] = $login.','.$user_group.','.$raiting.','.$status.','.$group.','.$clanID.','.$a.','.$b.','.$user_id;
        }
        $stmtUsers->close();

        // 4) Логи клана (prepared + лимит)
        $stmtLogs = $mysqli->prepare("
            SELECT `title`,`info`
            FROM `log_game`
            WHERE `user_id` = ? AND `type` = 'clan'
            ORDER BY `id` DESC
            LIMIT 200
        ");
        $stmtLogs->bind_param('i', $clanID);
        $stmtLogs->execute();
        $logsRes = $stmtLogs->get_result();

        $log = [];
        if ($logsRes) {
            while ($logClan = $logsRes->fetch_assoc()) {
                $log[] = [
                    'type' => (string)$logClan['title'],
                    'info' => (string)$logClan['info'],
                ];
            }
        }
        $stmtLogs->close();

        // 5) Ответ (структуру и ключи не меняем — только добавили вспомогательные поля)
        $response['users']       = $usersData;
        $response['info']        = (string)$info['info'];
        $response['rating']      = isset($info['rating'])   ? (int)$info['rating']   : 0;
        $response['position']    = isset($info['position']) ? (int)$info['position'] : 0;
        $response['money']       = isset($info['money'])    ? (int)$info['money']    : 0;
        $response['log']         = $log;
        $response['countUsers']  = count($usersData);

        // Уровень и опыт
        $response['clan_level']    = isset($info['level'])    ? (int)$info['level']    : 1;
        $response['clan_exp']      = isset($info['exp'])      ? (int)$info['exp']      : 0;
        $response['clan_exp_next'] = isset($info['exp_next']) ? (int)$info['exp_next'] : 1000;

        // Дополнительно, чтобы фронт мог проще проверять права (не нарушает совместимость)
        $response['is_member'] = ($a && (int)$a === (int)$clanID) ? 1 : 0;
        $response['my_group']  = $b !== null ? (int)$b : 0;

    } else {
        if ($stmtUsers) { $stmtUsers->close(); }
        $response['text'] = 'Данный клан пустой!';
    }
    break;

case 'clanCardControl':
    // Получаем ID клана и информацию о пользователе
    $clanIDParam = clearInt($_POST['id'] ?? 0);
    $userIdSession = (int)($_SESSION['id'] ?? 0);

    // Текущий пользователь и его клан
    $stmtUser = $mysqli->prepare("SELECT id, login, user_group FROM `users` WHERE `id` = ? LIMIT 1");
    $stmtUser->bind_param('i', $userIdSession);
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    $stmtUC = $mysqli->prepare("SELECT `clan_id`,`group` FROM `base_clans_users` WHERE `user_id` = ? LIMIT 1");
    $stmtUC->bind_param('i', $userIdSession);
    $stmtUC->execute();
    $userClan = $stmtUC->get_result()->fetch_assoc();
    $stmtUC->close();

    // Информация о клане для шапки (уровень и опыт)
    $clanInfo = null;
    if ($userClan) {
        $stmtCI = $mysqli->prepare("SELECT level, exp, exp_next FROM base_clans WHERE id = ? LIMIT 1");
        $stmtCI->bind_param('i', $userClan['clan_id']);
        $stmtCI->execute();
        $clanInfo = $stmtCI->get_result()->fetch_assoc();
        $stmtCI->close();
    }

    // Разметка окна
    ob_start();
    ?>
    <div class="modal--clan-control__dialog" role="dialog" aria-modal="true" aria-label="Управление кланом">
      <div class="header">
        Управление кланом
        <span onclick="CloseModel()" aria-label="Закрыть" role="button" tabindex="0"><i class="fas fa-times"></i></span>
      </div>

      <div class="content-model clan-pan">
        <div class="cc-wrap">
          <?php if ($clanInfo && $userClan): ?>
            <?php
              $clanLevel   = (int)$clanInfo['level'];
              $clanExp     = max(0, (int)$clanInfo['exp']);
              $clanExpNext = max(0, (int)$clanInfo['exp_next']);
              $expPercent  = $clanExpNext > 0 ? max(0, min(100, floor($clanExp / $clanExpNext * 100))) : 0;
            ?>
            <div class="cc-card">
              <div class="cc-headerRow">
                <div class="cc-badge"><i class="fas fa-users"></i> Клан</div>
                <div class="cc-badge" title="ID клана">ID: <?= htmlspecialchars((string)$userClan['clan_id'], ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <div class="cc-summary">
                <div class="cc-lvl" title="Текущий уровень клана" aria-label="Уровень клана">Lv <?= $clanLevel ?></div>
                <div class="cc-progress">
                  <div>Опыт клана</div>
                  <div class="cc-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $expPercent ?>">
                    <span style="width:<?= $expPercent ?>%;"></span>
                    <div class="cc-barLabel"><?= $clanExp ?> / <?= $clanExpNext ?> (<?= $expPercent ?>%)</div>
                  </div>
                  <div class="cc-note">Зарабатывайте опыт клана, выполняя активности сообща.</div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($userClan): ?>
            <?php $isLeader = ((int)$userClan['group'] === 1); ?>
            <?php if ($isLeader): ?>
              <div class="cc-grid">
                <!-- Объявление -->
                <div class="cc-card cc-section">
                  <div class="cc-title"><i class="fas fa-bullhorn"></i> Объявление</div>
                  <div class="cc-row">
                    <input class="cc-input" type="text" placeholder="Текст объявления…" id="goNotifyClan" maxlength="200" autocomplete="off">
                    <button class="cc-btn" data-action="goNotifyClan" type="button"><i class="fas fa-paper-plane"></i><span>Опубликовать</span></button>
                  </div>
                  <div class="cc-note">Сообщение увидят все участники клана.</div>
                </div>

                <!-- Лидер клана -->
                <div class="cc-card cc-section">
                  <div class="cc-title"><i class="fas fa-user-shield"></i> Лидер клана</div>
                  <div class="cc-row">
                    <input class="cc-input" type="text" placeholder="Имя тренера…" id="goLeaderClan" maxlength="32" autocomplete="off">
                    <button class="cc-btn" data-action="goLeaderClan" type="button"><i class="fas fa-plus"></i><span>Назначить лидером</span></button>
                    <button class="cc-btn leave" data-action="goUnleaderClan" type="button"><i class="fas fa-user-slash"></i><span>Снять с лидерства</span></button>
                  </div>
                  <div class="cc-note">Доступно только текущему лидеру.</div>
                </div>

                <!-- Вручить звание -->
                <div class="cc-card cc-section">
                  <div class="cc-title"><i class="fas fa-crown"></i> Вручить звание</div>
                  <div class="cc-row">
                    <input class="cc-input" type="text" placeholder="Имя тренера…" id="goStatusClanLogin" maxlength="32" autocomplete="off">
                    <input class="cc-input" type="text" placeholder="Звание…" id="goStatusClanText" maxlength="64" autocomplete="off">
                    <button class="cc-btn" data-action="goStatusClan" type="button"><i class="fas fa-check"></i><span>Вручить</span></button>
                  </div>
                  <div class="cc-note">Звание отображается в профиле участника клана.</div>
                </div>

                <!-- Исключить участника -->
                <div class="cc-card cc-section">
                  <div class="cc-title"><i class="fas fa-user-times"></i> Исключить участника</div>
                  <div class="cc-row">
                    <input class="cc-input" type="text" placeholder="Имя тренера…" id="goDeleteClan" maxlength="32" autocomplete="off">
                    <button class="cc-btn leave" data-action="goDeleteClan" type="button"><i class="fas fa-ban"></i><span>Исключить</span></button>
                  </div>
                  <div class="cc-note">Участник будет удалён из клана.</div>
                </div>

                <!-- Добавить монеты -->
                <div class="cc-card cc-section">
                  <div class="cc-title"><i class="fas fa-coins"></i> Добавить монеты в клан</div>
                  <div class="cc-row">
                    <input class="cc-input" type="number" min="1" step="1" placeholder="Сумма…" id="addClanMoneyInput" inputmode="numeric" autocomplete="off">
                    <button class="cc-btn" data-action="addClanMoney" type="button"><i class="fas fa-donate"></i><span>Добавить</span></button>
                  </div>
                  <div class="cc-note">Клановые монеты используются для развития и бонусов.</div>
                </div>
              </div>

              <script>
                // Закрыть модал
                if (typeof window.CloseModel !== 'function') {
                  window.CloseModel = function() {
                    var dlg = document.querySelector('.modal--clan-control__dialog');
                    if (dlg && dlg.parentNode) dlg.parentNode.removeChild(dlg);
                    var mud = document.querySelector('.mudol');
                    if (mud) mud.remove();
                  };
                }

                (function(){
                  function bindEnter(id, action){
                    var el = document.getElementById(id);
                    if(!el) return;
                    el.addEventListener('keydown', function(e){
                      if(e.key === 'Enter'){ e.preventDefault(); trigger(action); }
                    });
                  }
                  function doClanAction(type, payload, cb) {
                    payload = payload || {};
                    payload.object = 'clan'; // важно для /do/clanAction
                    payload.type   = type;   // роутер поймет и по type, и по object
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '/do/clanAction', true);
                    xhr.onreadystatechange = function(){
                      if (xhr.readyState === 4) {
                        try { var res = JSON.parse(xhr.responseText); } catch(e){ res = {}; }
                        var ok = (res.error === 'success') || (res.error === 0) || (res.success === true);
                        if (window.Game && Game.notifications && Game.notifications.main) {
                          Game.notifications.main(res.text || (ok ? 'Готово' : 'Ошибка'), ok ? 'success' : 'error');
                        }
                        if (ok && typeof cb === 'function') cb(res);
                      }
                    };
                    var form = new FormData();
                    for (var k in payload) if (payload.hasOwnProperty(k)) form.append(k, payload[k]);
                    xhr.send(form);
                  }
                  function trigger(action){
                    var payload = {};
                    switch(action){
                      case 'goNotifyClan': {
                        var v = document.getElementById('goNotifyClan');
                        payload.name = v ? v.value.trim() : '';
                        if (!payload.name) { if (window.Game?.notifications?.main) Game.notifications.main('Введите текст объявления','error'); return; }
                        // сервер: goNotifyClan (name)
                        doClanAction('goNotifyClan', payload);
                        break;
                      }
                      case 'goLeaderClan': {
                        var lg = document.getElementById('goLeaderClan');
                        var login = lg ? lg.value.trim() : '';
                        if (!login) { if (window.Game?.notifications?.main) Game.notifications.main('Введите логин тренера','error'); return; }
                        // Поддержим разные роуты: сначала попробуем setClanLeader, если бэкенд ждет goLeaderClan — он тоже поймет по type
                        payload.name = login;
                        doClanAction('setClanLeader', payload, function(){ /*опц: обновить UI*/ });
                        break;
                      }
                      case 'goUnleaderClan': {
                        var lg2 = document.getElementById('goLeaderClan');
                        var login2 = lg2 ? lg2.value.trim() : '';
                        if (!login2) { if (window.Game?.notifications?.main) Game.notifications.main('Введите логин тренера','error'); return; }
                        payload.name = login2;
                        doClanAction('goUnleaderClan', payload);
                        break;
                      }
                      case 'goStatusClan': {
                        var u = document.getElementById('goStatusClanLogin');
                        var s = document.getElementById('goStatusClanText');
                        payload.name = u ? u.value.trim() : '';
                        payload.other = s ? s.value.trim() : '';
                        if (!payload.name || !payload.other) { if (window.Game?.notifications?.main) Game.notifications.main('Введите логин и звание','error'); return; }
                        doClanAction('goStatusClan', payload);
                        break;
                      }
                      case 'goDeleteClan': {
                        var d = document.getElementById('goDeleteClan');
                        payload.name = d ? d.value.trim() : '';
                        if (!payload.name) { if (window.Game?.notifications?.main) Game.notifications.main('Введите логин тренера','error'); return; }
                        doClanAction('goDeleteClan', payload, function(){ /*опц: обновить список*/ });
                        break;
                      }
                      case 'addClanMoney': {
                        var m = document.getElementById('addClanMoneyInput');
                        var amount = m ? parseInt(m.value, 10) : 0;
                        if (!amount || amount < 1) { if (window.Game?.notifications?.main) Game.notifications.main('Введите корректную сумму','error'); return; }
                        payload.money = amount;
                        doClanAction('addClanMoney', payload, function(){ /*опц: обновить баланс*/ });
                        break;
                      }
                    }
                  }

                  document.addEventListener('click', function(e){
                    var btn = e.target.closest('.cc-btn[data-action]');
                    if(!btn) return;
                    e.preventDefault();
                    btn.disabled = true;
                    setTimeout(function(){ btn.disabled = false; }, 1200);
                    trigger(btn.getAttribute('data-action'));
                  });

                  // Enter по инпутам
                  bindEnter('goNotifyClan','goNotifyClan');
                  bindEnter('goLeaderClan','goLeaderClan');
                  bindEnter('goStatusClanLogin','goStatusClan');
                  bindEnter('goStatusClanText','goStatusClan');
                  bindEnter('goDeleteClan','goDeleteClan');
                  bindEnter('addClanMoneyInput','addClanMoney');

                  // Перетаскивание при наличии Draggabilly
                  if (window.jQuery && jQuery.fn && jQuery.fn.draggabilly && (!window.device || !device.mobile())) {
                    var $dlg = jQuery('.modal--clan-control__dialog');
                    if ($dlg.length) {
                      try { $dlg.draggabilly({ handle: '.header', containment: true }); } catch(e){}
                    }
                  }
                })();
              </script>
            <?php else: ?>
              <div class="cc-card cc-empty">
                Вы состоите в клане, но не являетесь лидером. Управление доступно только лидеру клана.
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="cc-card cc-empty">
              Вы не состоите в клане.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
    $response['html'] = ob_get_clean();

    // Данные клана для фронта (подстройка прогресса после загрузки)
    if ($clanInfo) {
        $response['clan_level']    = (int)$clanInfo['level'];
        $response['clan_exp']      = max(0, (int)$clanInfo['exp']);
        $response['clan_exp_next'] = max(0, (int)$clanInfo['exp_next']);
    }
    break;
	case 'giveEggNpc':
			$locationNPC = $mysqli->query('SELECT
                      `bn`.`id`,
                      `bn`.`name`
                    FROM `base_npc` AS `bn`
                    LEFT JOIN `users` AS `u`
                      ON `bn`.`loc_id` = `u`.`location`
                    WHERE
                      `u`.`id` = '.$_SESSION['id']
				);
			if(empty($locationNPC)){
				$response['error'] = 1;
			}else{
				$response['npc'] = [];
				while($npc = $locationNPC->fetch_assoc()){
					$response['npc'][] = [
										'id'	=>$npc['id'],
										'name'	=>$npc['name']
									];
			}
		}
		break;
    default:
        $response['error'] = 1;
        $response['message'] = 'Unknown error';
    break;
}

echo json_encode($response);
?>
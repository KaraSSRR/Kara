<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

$response['name'] = 'Проводник';
if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }
$uid = (int)$_SESSION['id'];
global $mysqli;

/* ---------- узнаём id НПС так же, как в рабочем примере ---------- */
function _npc_id(mysqli $db): int {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id > 0) return $id;

    $base = basename(__FILE__, '.php');
    if (ctype_digit($base)) return (int)$base;

    $me  = $db->query("SELECT `location` FROM `users` WHERE `id`=".(int)$_SESSION['id']." LIMIT 1")->fetch_assoc();
    $loc = (int)($me['location'] ?? 0);
    if ($loc) {
        $r = $db->query("SELECT `id` FROM `base_npc` WHERE `name`='Проводник' AND `loc_id`=".$loc." AND `hide`=0 LIMIT 1")->fetch_assoc();
        if ($r && !empty($r['id'])) return (int)$r['id'];
    }
    return 0;
}
$npcId = _npc_id($mysqli);

/* ===================== НАСТРОЙКИ ===================== */
const QUEST_ID              = 120;
const CELLS                 = 9;     // 3x3
const PUZZLE_STAGE_LIFETIME = 18;    // сек на фазу
const COOLDOWN_SUCCESS      = 45;    // КД удача
const COOLDOWN_FAIL         = 20;    // КД промах
const TRAIN_WINDOW_MIN      = 0.15;  // окно по прогрессу поездки
const TRAIN_WINDOW_MAX      = 0.90;
const HARMONY_BONUS_CHANCE  = 12;    // шанс +1 символ

/* ===================== КВЕСТ-ХРАНИЛКА ===================== */
function q120_get(mysqli $db, int $uid) {
    $r = $db->query("SELECT * FROM user_quests WHERE user_id=$uid AND quest_id=".QUEST_ID." LIMIT 1")->fetch_assoc();
    if (!$r) return null;
    $r['step'] = (int)$r['step'];
    $r['end']  = (int)($r['end'] ?? 0);
    $r['data'] = json_decode($r['data'] ?? '[]', true) ?: [];
    return $r;
}
function q120_create(mysqli $db, int $uid) {
    $need = rand(5,8);
    $data = ['echo'=>0,'need_echo'=>$need,'cool_ts'=>0,'puz'=>null];
    $j = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    $db->query("INSERT INTO user_quests (user_id,quest_id,step,end,data) VALUES ($uid,".QUEST_ID.",1,0,'$j')");
    return q120_get($db,$uid);
}
function q120_save(mysqli $db, int $uid, int $step, array $data, int $end=0) {
    $j = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    $db->query("UPDATE user_quests SET step=$step,end=$end,data='$j' WHERE user_id=$uid AND quest_id=".QUEST_ID." LIMIT 1");
}

/* ===================== ПОЕЗДКА ===================== */
function current_trip(mysqli $db, int $uid) {
    return $db->query("SELECT * FROM train_travel WHERE user=$uid AND active=1 LIMIT 1")->fetch_assoc();
}
function trip_progress(array $t): float {
    $now  = time();
    $len  = max(1, (int)$t['arrive_at'] - (int)$t['depart_at']);
    $past = $now - (int)$t['depart_at'];
    return max(0, min(1, $past / $len));
}

/* ===================== ГЕНЕРАЦИЯ ПАЗЛОВ ===================== */
function make_stage1(): array {
    $arr = [];
    $rows=[rand(3,9),rand(3,9),rand(3,9)];
    $cols=[rand(3,9),rand(3,9),rand(3,9)];
    for($r=0;$r<3;$r++){
        for($c=0;$c<3;$c++){
            $arr[] = min(100, $rows[$r]*$cols[$c] + rand(10,22));
        }
    }
    $maxi = rand(0, CELLS-1);
    $peak = rand(78,95);
    $arr[$maxi] = $peak;

    do { $second = rand(0, CELLS-1); } while ($second === $maxi);
    $arr[$second] = max(0, $peak - rand(2,4));

    for($i=0;$i<CELLS;$i++){
        if ($i!==$maxi && $i!==$second) $arr[$i] = min($arr[$i], rand(20, $peak-5));
    }
    foreach($arr as $i=>$v){ if($i!==$maxi && $v===$arr[$maxi]) {$arr[$maxi]+=1; break;} }

    return ['stage'=>1,'strength'=>$arr,'max_i'=>$maxi,'deadline'=>time()+PUZZLE_STAGE_LIFETIME];
}
function make_stage2(int $maxi): array {
    $arr = array_fill(0, CELLS, rand(25,40));
    $peak = rand(80,98);
    $arr[$maxi] = $peak;

    $used = [$maxi=>true];
    for($k=0;$k<2;$k++){
        do { $i = rand(0, CELLS-1); } while(isset($used[$i]));
        $used[$i]=true;
        $arr[$i] = max(0, $peak - rand(1,2));
    }
    for($i=0;$i<CELLS;$i++){
        if (!isset($used[$i])) $arr[$i] = rand(30, $peak-6);
    }
    foreach($arr as $i=>$v){ if($i!==$maxi && $v===$arr[$maxi]) {$arr[$maxi]+=1; break;} }

    return ['stage'=>2,'strength'=>$arr,'max_i'=>$maxi,'deadline'=>time()+PUZZLE_STAGE_LIFETIME];
}

/* ===================== UI ===================== */
function cell_btn(int $npcId, int $idx, int $value): string {
    $h = max(4, min(100, (int)$value));
    $delay = ($idx%3)*120;
    return '<button type="button" class="echo-cell" onclick="return NpcDialog('.$npcId.','.(300+$idx).');" data-step="'.(300+$idx).'">
              <div class="bar" style="height:'.$h.'%; --pulse-delay: '.$delay.'ms"></div>
              <div class="ripple"></div>
            </button>';
}
function done_html(): string {
    return '
    <style>
      .echo-wrap{color:#e9e7ff}
      .echo-card{border:1px solid rgba(255,255,255,.12);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.04));padding:14px}
      .echo-head{display:flex;align-items:center;gap:10px}
      .echo-ico{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,rgba(106,108,246,.25),rgba(106,108,246,.12));border:1px solid rgba(106,108,246,.35);display:flex;align-items:center;justify-content:center}
      .echo-ico i{color:#9aa0ff}
      .echo-title{font-weight:900;font-size:20px;color:#c9ccff}
      .echo-sub{color:#b2b6d9}
    </style>
    <div class="echo-wrap"><div class="echo-card">
      <div class="echo-head">
        <div class="echo-ico"><i class="fa fa-check"></i></div>
        <div>
          <div class="echo-title">Пакет собран</div>
          <div class="echo-sub">Отправляйтесь к <b>Технику эфирной связи</b> для калибровки. Скан недоступен.</div>
        </div>
      </div>
    </div></div>';
}
function greeting_html(): string {
    // стандартное короткое приветствие после завершения квеста
    return '<div class="echo-wrap"><div class="echo-card"><div class="echo-head"><div class="echo-ico"><i class="fa fa-train"></i></div><div><div class="echo-title">Приятного пути!</div><div class="echo-sub">Проводник вежливо кивает вам.</div></div></div></div></div>';
}
function puzzle_html(int $npcId, array $puz, int $echo, int $need, int $cdLeft): string {
    $s = $puz['strength'];
    $cells = '';
    for ($i=0;$i<CELLS;$i++) $cells .= cell_btn($npcId,$i,$s[$i]);

    $timeLeft = max(0, $puz['deadline'] - time());
    $pct      = max(0, min(100, (int)round(100*$timeLeft/PUZZLE_STAGE_LIFETIME)));
    $phase    = (int)$puz['stage'];

    return '
    <style>
      .echo-wrap{color:#e9e7ff}
      .echo-card{border:1px solid rgba(255,255,255,.12);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.04));padding:14px;box-shadow:0 10px 24px rgba(0,0,0,.25)}
      .echo-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
      .echo-ico{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,rgba(106,108,246,.25),rgba(106,108,246,.12));border:1px solid rgba(106,108,246,.35);display:flex;align-items:center;justify-content:center}
      .echo-ico i{color:#9aa0ff}
      .echo-title{font-weight:900;font-size:20px;color:#c9ccff}
      .echo-sub{color:#b2b6d9}
      .phase{margin-left:auto;padding:6px 10px;border-radius:10px;border:1px dashed rgba(255,255,255,.18);background:rgba(255,255,255,.06);font-weight:800}
      .timer{height:6px;border-radius:999px;background:rgba(255,255,255,.1);overflow:hidden;margin-top:6px}
      .timer>span{display:block;height:100%;background:linear-gradient(90deg,#8bb8ff,#6a6cf6)}
      .echo-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
      .echo-cell{position:relative;aspect-ratio:1/1;min-height:90px;border-radius:14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);cursor:pointer;overflow:hidden;isolation:isolate}
      .echo-cell .bar{
         position:absolute;left:0;right:0;bottom:0;margin:0 8px;border-radius:8px;
         background:linear-gradient(180deg,#a5abff,#6a6cf6);
         box-shadow:0 0 14px rgba(130,140,255,.25) inset, 0 4px 16px rgba(0,0,0,.25);
         animation:pulse 2.2s ease-in-out infinite; animation-delay:var(--pulse-delay,0ms);
      }
      .echo-cell::after{
        content:""; position:absolute; inset:0; pointer-events:none; opacity:.08;
        background:radial-gradient(ellipse at 30% 20%,#fff 0%,transparent 60%),
                   radial-gradient(ellipse at 70% 80%,#fff 0%,transparent 60%);
        mix-blend-mode:overlay; animation:drift 6s linear infinite;
      }
      .echo-cell .ripple{pointer-events:none; position:absolute;inset:0; border-radius:inherit; background:radial-gradient(circle at var(--x,50%) var(--y,50%), rgba(255,255,255,.28), transparent 45%); opacity:0; transform:scale(0.9); transition:.3s}
      .echo-cell:active .ripple{opacity:1; transform:scale(1)}
      .echo-prog{height:8px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden}
      .echo-prog>span{display:block;height:100%;background:linear-gradient(90deg,#8bb8ff,#6a6cf6)}
      .echo-hint{margin-top:8px;color:#b8bce3}
      @keyframes pulse{ 0%,100%{filter:brightness(1)} 50%{filter:brightness(1.15)} }
      @keyframes drift{ 0%{transform:translateX(-10%)} 100%{transform:translateX(10%)} }
      @media (max-width:560px){ .echo-title{font-size:18px} .echo-cell{min-height:80px;border-radius:12px} .echo-grid{gap:8px} }
      @media (max-width:380px){ .echo-cell{min-height:72px} }
    </style>
    <div class="echo-wrap">
      <div class="echo-card">
        <div class="echo-head">
          <div class="echo-ico"><i class="fa fa-wave-square"></i></div>
          <div style="min-width:0">
            <div class="echo-title">Трекинг резонанса</div>
            <div class="echo-sub">Прогресс: <b>'.$echo.' / '.$need.'</b></div>
            <div class="echo-prog"><span style="width:'.round(100*min(1,$echo/max(1,$need))).'%"></span></div>
            <div class="timer"><span id="timerbar" style="width: '.$pct.'%; transition: width '.$timeLeft.'s linear"></span></div>
          </div>
          <div class="phase">Фаза '.$phase.' / 2 · <span id="tleft">'.$timeLeft.'</span>с</div>
        </div>
        <div class="echo-hint">'.($phase===1 ? 'Найдите пик сигнала.' : 'Сигнал нестабилен — повторите захват ещё раз.').'</div>
        <div class="echo-grid" id="echogrid">'.$cells.'</div>
      </div>
    </div>
    <script>
      (function(){
        var t = '.$timeLeft.';
        var span = document.getElementById("tleft");
        var bar  = document.getElementById("timerbar");
        if (bar){ requestAnimationFrame(function(){ bar.style.width = "0%"; }); }
        if (span && t>0){
          var iv = setInterval(function(){ t--; if (t<0){clearInterval(iv); t=0;} span.textContent = t; },1000);
        }
        var g=document.getElementById("echogrid");
        if(g){
          g.addEventListener("pointerdown",function(e){
            var c=e.target.closest(".echo-cell"); if(!c) return;
            var r=c.querySelector(".ripple"); if(!r) return;
            var rect=c.getBoundingClientRect();
            r.style.setProperty("--x",(e.clientX-rect.left)+"px");
            r.style.setProperty("--y",(e.clientY-rect.top)+"px");
          },{passive:true});
        }
      })();
    </script>';
}
function start_html(int $echo, int $need, int $cdLeft, bool $inWindow, string $note=''): string {
    $hint = $note !== '' ? $note : (
        !$inWindow ? 'Сканирование лучше проводить в середине пути (15–90%).'
                   : ($cdLeft>0 ? 'Повторная калибровка будет доступна через '.$cdLeft.'с.' : 'Готовы — запустите скан.')
    );
    return '
    <style>
      .echo-wrap{color:#e9e7ff}
      .echo-card{border:1px solid rgba(255,255,255,.12);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.04));padding:14px}
      .echo-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
      .echo-ico{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,rgba(106,108,246,.25),rgba(106,108,246,.12));border:1px solid rgba(106,108,246,.35);display:flex;align-items:center;justify-content:center}
      .echo-ico i{color:#9aa0ff}
      .echo-title{font-weight:900;font-size:20px;color:#c9ccff}
      .echo-sub{color:#b2b6d9}
      .echo-prog{height:8px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden}
      .echo-prog>span{display:block;height:100%;background:linear-gradient(90deg,#8bb8ff,#6a6cf6)}
      .echo-hint{margin-top:8px;color:#b8bce3}
    </style>
    <div class="echo-wrap">
      <div class="echo-card">
        <div class="echo-head">
          <div class="echo-ico"><i class="fa fa-compass"></i></div>
          <div>
            <div class="echo-title">Калибровка сканера</div>
            <div class="echo-sub">Прогресс: <b>'.$echo.' / '.$need.'</b></div>
            <div class="echo-prog"><span style="width:'.round(100*min(1,$echo/max(1,$need))).'%"></span></div>
          </div>
        </div>
        <div class="echo-hint">'.$hint.'</div>
      </div>
    </div>';
}

/* ===================== ЛОГИКА ===================== */
$quest = q120_get($mysqli, $uid) ?: q120_create($mysqli, $uid);

/* после полного завершения — стандартное приветствие */
if ((int)$quest['end'] === 1) { $response['question'] = greeting_html(); $response['answer'] = []; return; }

$need = (int)($quest['data']['need_echo'] ?? 5);
$echo = (int)($quest['data']['echo'] ?? 0);

/* пакет уже собран? (запрет скана) */
$packageDone = ($echo >= $need) || ((int)$quest['step'] >= 2);

$trip = current_trip($mysqli, $uid);
if (!$trip) { $response['question'] = 'Прослушивание доступно только в пути. Сядьте на поезд — и снова ко мне.'; $response['answer']=[]; return; }
$prog = trip_progress($trip);
$inWindow = ($prog >= TRAIN_WINDOW_MIN && $prog <= TRAIN_WINDOW_MAX);

$step = isset($npcStep) ? (int)$npcStep : 0;

/* чистим протухшую фазу */
if (!empty($quest['data']['puz']) && time() > (int)$quest['data']['puz']['deadline']) {
    $quest['data']['puz'] = null;
    q120_save($mysqli, $uid, max(1,(int)$quest['step']), $quest['data'], 0);
}

/* ответы по умолчанию */
$response['answer'] = [];
if (!$packageDone) {
    if (!empty($quest['data']['puz'])) {
        $response['answer'][221] = 'Отмена скана';
    } else {
        $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
        if ($inWindow && $cdLeft===0) $response['answer'][220] = 'Запустить скан';
    }
}

/* ВЕТВЛЕНИЯ */
switch (true) {

    /* запуск скана */
    case ($step === 220):
        if ($packageDone) { $response['question'] = done_html(); $response['answer'] = []; break; }
        $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
        if ($cdLeft > 0 || !$inWindow) {
            $response['question'] = start_html($echo, $need, $cdLeft, $inWindow);
            $response['answer']   = [];
            break;
        }
        $quest['data']['puz'] = make_stage1();
        q120_save($mysqli, $uid, $quest['step'], $quest['data'], 0);
        $response['question'] = puzzle_html($npcId, $quest['data']['puz'], $echo, $need, 0);
        $response['answer']   = [221 => 'Отмена скана'];
        break;

    /* отмена скана */
    case ($step === 221):
        if ($packageDone) { $response['question'] = done_html(); $response['answer'] = []; break; }
        $quest['data']['puz'] = null;
        q120_save($mysqli, $uid, $quest['step'], $quest['data'], 0);
        $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
        $response['question'] = start_html($echo, $need, $cdLeft, $inWindow, 'Скан отменён.');
        $response['answer']   = (!$packageDone && $inWindow && $cdLeft===0) ? [220 => 'Запустить скан'] : [];
        break;

    /* клик по ячейке */
    case ($step >= 300 && $step <= 308):
        if ($packageDone) { $response['question'] = done_html(); $response['answer'] = []; break; }

        $puz = $quest['data']['puz'] ?? null;
        if (!$puz) {
            $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
            $response['question'] = start_html($echo, $need, $cdLeft, $inWindow);
            $response['answer']   = (!$packageDone && $inWindow && $cdLeft===0) ? [220 => 'Запустить скан'] : [];
            break;
        }
        $idx   = $step - 300;
        $stage = (int)$puz['stage'];

        if ($stage === 1) {
            $good = ($idx === (int)$puz['max_i']);
            if ($good) {
                $quest['data']['puz'] = make_stage2((int)$puz['max_i']);
                q120_save($mysqli, $uid, $quest['step'], $quest['data'], 0);
                $response['question'] = puzzle_html($npcId, $quest['data']['puz'], $echo, $need, 0);
                $response['answer']   = [221 => 'Отмена скана'];
            } else {
                $quest['data']['puz']     = null;
                $quest['data']['cool_ts'] = time() + COOLDOWN_FAIL;
                q120_save($mysqli, $uid, $quest['step'], $quest['data'], 0);
                $response['question'] = start_html($echo, $need, COOLDOWN_FAIL, $inWindow, 'Шум… не та частота. Стабилизатор перегрелся.');
                $response['answer']   = [];
            }
            break;
        }

        if ($stage === 2) {
            $good = ($idx === (int)$puz['max_i']);
            $gain = $good ? 1 : 0;
            if ($good && rand(1,100) <= HARMONY_BONUS_CHANCE) $gain += 1;

            $quest['data']['puz']     = null;
            $echo += $gain;
            $quest['data']['echo']    = $echo;
            $quest['data']['cool_ts'] = time() + ($good ? COOLDOWN_SUCCESS : COOLDOWN_FAIL);

            $next = $quest['step'];
            if ($echo >= $need) $next = 2; // пакет собран

            q120_save($mysqli, $uid, $next, $quest['data'], 0);

            if ($echo >= $need) {
                $response['question'] = done_html();
                $response['answer']   = [];
            } else {
                $msg = $good
                    ? ('Эхо поймано! +'.$gain.' символ'.($gain>1?'а':'').'. Прогресс: <b>'.$echo.' / '.$need.'</b>.')
                    : 'Срыв сигнала. Потребуется повторная калибровка.';
                $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
                $response['question'] = start_html($echo, $need, $cdLeft, $inWindow, $msg);
                $response['answer']   = [];
            }
            break;
        }

        // fallback
        $quest['data']['puz'] = null;
        q120_save($mysqli, $uid, $quest['step'], $quest['data'], 0);
        $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
        $response['question'] = start_html($echo, $need, $cdLeft, $inWindow);
        $response['answer']   = [];
        break;

    /* состояния по умолчанию */
    default:
        if ($packageDone) { $response['question'] = done_html(); $response['answer'] = []; break; }
        $cdLeft = max(0, (int)$quest['data']['cool_ts'] - time());
        if (!empty($quest['data']['puz'])) {
            $response['question'] = puzzle_html($npcId, $quest['data']['puz'], $echo, $need, $cdLeft);
            $response['answer']   = [221 => 'Отмена скана'];
        } else {
            $response['question'] = start_html($echo, $need, $cdLeft, $inWindow);
            $response['answer']   = ($inWindow && $cdLeft===0) ? [220 => 'Запустить скан'] : [];
        }
        break;
}
?>

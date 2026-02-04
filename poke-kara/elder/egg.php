<?php
/**
 * Админ-панель: выдача яиц (user_egg)
 *
 * Файл самодостаточный:
 * - GET  -> HTML интерфейс
 * - POST -> JSON (AJAX) для выдачи
 *
 * Доступ ограничен пользователем с id=4 (как в существующих админках).
 */

$patch_project = $_SERVER['DOCUMENT_ROOT'] ?? '';
$patch_global  = rtrim($patch_project, '/').'/inc/conf/global.php';
if (!file_exists($patch_global)) {
    http_response_code(500);
    die('The problem with the connection files.');
}
require_once($patch_global);

session_start();
if (!isset($_SESSION['id']) || (int)$_SESSION['id'] !== 4) {
    http_response_code(403);
    die('<div style="margin:60px auto;max-width:420px;padding:36px 22px;background:#0e1116;border-radius:18px;box-shadow:0 10px 40px #0003;color:#e5e7eb;font-family:Inter,Arial,sans-serif;text-align:center">
        <div style="font-size:52px">⛔</div>
        <div style="font-size:20px;margin:8px 0;color:#c7b9ff">Доступ запрещён</div>
        <div style="font-size:14px;color:#9aa4b2">У вас нет прав для просмотра этой страницы.</div>
    </div>');
}

/**
 * JSON-ответ для AJAX.
 */
function _json_exit(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Пытаемся писать в log_pokemon, если таблица существует.
 */
function _try_log_egg(mysqli $mysqli, int $eggId, string $text): void {
    // Быстрое кэширование результата проверки таблицы.
    static $hasTable = null;
    if ($hasTable === null) {
        $hasTable = false;
        if ($r = $mysqli->query("SHOW TABLES LIKE 'log_pokemon'")) {
            $hasTable = (bool)$r->fetch_row();
            $r->free();
        }
    }
    if (!$hasTable) return;

    $dt = date('d.m H:i');
    if ($stmt = $mysqli->prepare('INSERT INTO `log_pokemon` (`date`,`pok`,`pok_id`,`type`) VALUES (?,?,?,?)')) {
        $type = 'egg';
        $stmt->bind_param('ssis', $dt, $text, $eggId, $type);
        @$stmt->execute();
        @$stmt->close();
    }
}

/**
 * Админ-выдача яйца.
 * ВАЖНО: В отличие от plusEgg(), здесь можно принудительно выставить shine=0,
 * а не получать случайный шанс.
 */
function admin_add_egg(mysqli $mysqli, int $userId, int $pokemonId, array $opt): array {
    // Опции
    $count      = max(1, (int)($opt['count'] ?? 1));
    $sparka     = !empty($opt['sparka']) ? 1 : 0;
    $trade      = !empty($opt['trade']) ? 'true' : 'false';
    $form       = max(0, (int)($opt['form'] ?? 0));
    $hatchDays  = (int)($opt['hatch_days'] ?? 0);
    $character  = (int)($opt['character'] ?? 0);
    $gensIn     = (string)($opt['gens'] ?? '');
    $shineMode  = (string)($opt['shine_mode'] ?? 'force0'); // force0|force1|random

    if ($count > 500) {
        // Предохранитель от случайного «залить 1e6».
        return ['ok' => false, 'msg' => 'Слишком много яиц за раз (лимит 500 на запрос).'];
    }
    if ($hatchDays < 0) $hatchDays = 0;
    if ($hatchDays > 60) $hatchDays = 60;
    if ($character < 0) $character = 0;
    if ($character > 26) $character = 26;

    // Базовый покемон -> eggBasenum
    $base = null;
    if ($stmt = $mysqli->prepare('SELECT `id`,`name_rus`,`eggBasenum`,`type`,`type_two` FROM `base_pokemons` WHERE `id` = ? LIMIT 1')) {
        $stmt->bind_param('i', $pokemonId);
        $stmt->execute();
        $base = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$base) {
        return ['ok' => false, 'msg' => 'Покемон не найден (base_pokemons).'];
    }
    $eggBasenum = (int)($base['eggBasenum'] ?? 0);
    if ($eggBasenum <= 0) $eggBasenum = (int)$base['id'];
    $nameRus = (string)($base['name_rus'] ?? '');

    // Проверяем пользователя
    $u = null;
    if ($stmt = $mysqli->prepare('SELECT `id`,`login` FROM `users` WHERE `id` = ? LIMIT 1')) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$u) {
        return ['ok' => false, 'msg' => 'Пользователь не найден.'];
    }

    // Подготовка INSERT
    $insert = $mysqli->prepare('INSERT INTO `user_egg` (`gens`,`character`,`shine`,`trade`,`reborn`,`user`,`basenum`,`sparka`,`form`) VALUES (?,?,?,?,?,?,?,?,?)');
    if (!$insert) {
        return ['ok' => false, 'msg' => 'Не удалось подготовить запрос вставки (user_egg).'];
    }

    $eggIds = [];
    for ($i = 0; $i < $count; $i++) {
        // gens
        $gens = '';
        if ($gensIn !== '') {
            $g = array_map('trim', explode(',', $gensIn));
            if (count($g) === 6 && count(array_filter($g, fn($x) => $x !== '' && ctype_digit((string)$x))) === 6) {
                $nums = array_map('intval', $g);
                $nums = array_map(fn($x) => max(0, min(31, $x)), $nums);
                $gens = implode(',', $nums);
            }
        }
        if ($gens === '') {
            $nums = [rand(10,26), rand(10,26), rand(10,26), rand(10,26), rand(10,26), rand(10,26)];
            $gens = implode(',', $nums);
        }

        // character
        $char = $character ?: rand(1,26);

        // shine
        if ($shineMode === 'force1') {
            $shine = 1;
        } elseif ($shineMode === 'random') {
            // Поведение как в plusEgg(): mt_rand(1,11000) < 10
            $shine = (mt_rand(1, 11000) < 10) ? 1 : 0;
        } else {
            $shine = 0;
        }

        // reborn
        $days = ($hatchDays > 0) ? $hatchDays : rand(5,11);
        $reborn = time() + (3600 * 24 * $days);

        // bind + execute
        $insert->bind_param('siisiiiii', $gens, $char, $shine, $trade, $reborn, $userId, $eggBasenum, $sparka, $form);
        if (!$insert->execute()) {
            $insert->close();
            return ['ok' => false, 'msg' => 'Ошибка вставки в user_egg: '.$mysqli->error];
        }
        $eggId = (int)$mysqli->insert_id;
        $eggIds[] = $eggId;

        _try_log_egg($mysqli, $eggId, 'Яйцо: '.$eggBasenum.' '.($nameRus ? '('.$nameRus.')' : ''));
    }
    $insert->close();

    return [
        'ok' => true,
        'msg' => 'Успешно: выдано '.$count.' шт. (ID яиц: '.implode(',', array_slice($eggIds, 0, 25)).(count($eggIds) > 25 ? '…' : '').')',
        'egg_ids' => $eggIds,
        'egg_basenum' => $eggBasenum,
        'pokemon_name' => $nameRus,
        'user' => ['id' => (int)$u['id'], 'login' => (string)$u['login']],
    ];
}

/**
 * AJAX обработчик
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') {
    global $mysqli;

    $userId = (int)($_POST['user_id'] ?? 0);
    $userToken = trim((string)($_POST['user_token'] ?? ''));
    if ($userId <= 0 && $userToken !== '') {
        // Разрешаем ID или login
        if (ctype_digit($userToken)) {
            $userId = (int)$userToken;
        } else {
            if ($stmt = $mysqli->prepare('SELECT `id` FROM `users` WHERE `login` = ? LIMIT 1')) {
                $stmt->bind_param('s', $userToken);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) $userId = (int)$row['id'];
            }
        }
    }
    if ($userId <= 0) {
        _json_exit(['error' => 'error', 'text' => 'Не указан получатель (user_id).'], 400);
    }

    $pokeToken = trim((string)($_POST['pokemon'] ?? ''));
    $pokemonId = 0;
    if ($pokeToken === '') {
        // Если не указали — случайно
        $max = 898;
        if ($r = $mysqli->query('SELECT MAX(`id`) AS m FROM `base_pokemons`')) {
            $m = $r->fetch_assoc();
            if (!empty($m['m'])) $max = (int)$m['m'];
            $r->free();
        }
        $pokemonId = rand(1, max(1, $max));
    } elseif (ctype_digit($pokeToken)) {
        $pokemonId = (int)$pokeToken;
    } else {
        // По name_rus (и на всякий случай по name)
        if ($stmt = $mysqli->prepare('SELECT `id` FROM `base_pokemons` WHERE `name_rus` = ? OR `name` = ? LIMIT 1')) {
            $stmt->bind_param('ss', $pokeToken, $pokeToken);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) $pokemonId = (int)$row['id'];
        }
    }
    if ($pokemonId <= 0) {
        _json_exit(['error' => 'error', 'text' => 'Не удалось определить покемона (pokemon).'], 400);
    }

    $opt = [
        'count'      => (int)($_POST['count'] ?? 1),
        'sparka'     => (int)($_POST['sparka'] ?? 0) === 1,
        'trade'      => (int)($_POST['trade'] ?? 0) === 1,
        'form'       => (int)($_POST['form'] ?? 0),
        'hatch_days' => (int)($_POST['hatch_days'] ?? 0),
        'character'  => (int)($_POST['character'] ?? 0),
        'gens'       => trim((string)($_POST['gens'] ?? '')),
        'shine_mode' => trim((string)($_POST['shine_mode'] ?? 'force0')),
    ];

    $res = admin_add_egg($mysqli, $userId, $pokemonId, $opt);
    if (!$res['ok']) {
        _json_exit(['error' => 'error', 'text' => $res['msg']], 400);
    }
    _json_exit(['error' => 'success', 'text' => $res['msg'], 'data' => $res]);
}

// Данные для интерфейса
$usersQ = $mysqli->query('SELECT `id`,`login` FROM `users` ORDER BY `id` ASC');
$USERS = [];
if ($usersQ) {
    while ($u = $usersQ->fetch_assoc()) {
        $USERS[] = ['id' => (int)$u['id'], 'login' => $u['login']];
    }
}

$pokesQ = $mysqli->query('SELECT `id`,`name_rus` FROM `base_pokemons` ORDER BY `id` ASC');
$POKES = [];
if ($pokesQ) {
    while ($p = $pokesQ->fetch_assoc()) {
        $POKES[] = ['id' => (int)$p['id'], 'name_rus' => $p['name_rus']];
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Админ — выдача яиц</title>
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<link href="/fontawesome/css/all.css" rel="stylesheet">
<script src="/js/jquery/jquery.js"></script>
<style>
:root{
  --bg:#0b0f14; --surface:#111723; --card:#151e2c; --text:#e6ecf5; --muted:#94a3b8;
  --primary:#8b5cf6; --accent:#22d3ee; --border:rgba(255,255,255,.12); --good:#34d399; --bad:#ef4444;
  --r:14px; --shadow:0 18px 46px rgba(0,0,0,.5);
}
*{box-sizing:border-box} html,body{height:100%}
body{margin:0;background:radial-gradient(1000px 420px at -10% -10%,rgba(139,92,246,.15),transparent 50%),radial-gradient(900px 420px at 120% 0,rgba(34,211,238,.15),transparent 55%),linear-gradient(180deg,var(--bg) 0%,#0e131b 100%);color:var(--text);font:16px/1.45 Inter,system-ui,Segoe UI,Roboto,Arial}
.wrap{max-width:1100px;margin:0 auto;padding:24px 12px}
h1{margin:0 0 6px 0;font-size:22px} .muted{color:var(--muted);font-size:14px;margin-bottom:14px}
.card{background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.02));border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;margin:10px 0}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
@media (max-width:860px){.grid{grid-template-columns:repeat(6,1fr)}}
@media (max-width:560px){.grid{grid-template-columns:repeat(2,1fr)}}
.col-12{grid-column:span 12}.col-8{grid-column:span 8}.col-6{grid-column:span 6}.col-4{grid-column:span 4}.col-3{grid-column:span 3}
@media (max-width:860px){.col-8,.col-6,.col-4,.col-3{grid-column:span 6}}
@media (max-width:560px){.col-8,.col-6,.col-4,.col-3{grid-column:span 2}}

label{font-size:12px;color:var(--muted);font-weight:800;letter-spacing:.02em}
input[type=text],input[type=number],select{height:42px;border:1px solid var(--border);border-radius:10px;background:var(--card);color:var(--text);padding:0 12px;outline:none}
input:focus,select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(139,92,246,.25)}

.toggle{display:flex;gap:6px}
.toggle .chip{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 12px;border:1px solid var(--border);border-radius:999px;background:var(--card);cursor:pointer;font-weight:800}
.toggle .chip.active{border-color:var(--primary);box-shadow:0 0 0 3px rgba(139,92,246,.2)}

.btn{height:44px;border:none;border-radius:12px;padding:0 16px;font-weight:900;cursor:pointer}
.btn-primary{color:#fff;background:linear-gradient(90deg,var(--primary),#9a7bff)}
.btn:disabled{opacity:.55;cursor:not-allowed}

.note{font-size:12px;color:var(--muted)}
.hr{height:1px;background:var(--border);margin:12px 0;border-radius:1px}

.progress{height:10px;border-radius:999px;background:#0f172a;border:1px solid var(--border);overflow:hidden}
.progress>span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--accent),#60a5fa)}
.status{min-height:22px;margin-top:6px;font-weight:800}
.status.ok{color:var(--good)} .status.err{color:var(--bad)}

.badge{display:inline-flex;gap:6px;align-items:center;font-size:12px;color:var(--muted)}
kbd{background:#0a0f17;border:1px solid var(--border);border-radius:6px;padding:2px 6px;color:#cbd5e1}

.tagbox{display:flex;gap:6px;flex-wrap:wrap;padding:8px;border:1px dashed var(--border);border-radius:10px;background:var(--card)}
.tag{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;background:#0e1726;border:1px solid var(--border);font-size:13px}
.tag button{background:#0000;border:none;color:#cbd5e1;cursor:pointer}
.help{font-size:12px;color:var(--muted)}
</style>
</head>
<body>
<div class="wrap">
  <h1>Выдача яиц</h1>
  <div class="muted">Выдача идёт напрямую в <code>user_egg</code> (POST на эту же страницу). Доступ только у admin-4. Массовая выдача работает последовательно с прогрессом.</div>

  <div class="card">
    <div class="badge"><i class="fa fa-egg"></i> Выдача яйца</div>
    <div class="grid" style="margin-top:8px">
      <div class="col-6">
        <label>Покемон для яйца (ID или имя)</label>
        <input list="pokeList" id="pokeInput" placeholder="Пусто = случайно" autocomplete="off">
        <datalist id="pokeList">
          <?php foreach($POKES as $p){ echo '<option value="'.htmlspecialchars($p['name_rus']).'">#'.$p['id'].' '.htmlspecialchars($p['name_rus']).'</option>'; } ?>
        </datalist>
        <div class="help">Если введёте имя — будет искаться по <b>name_rus</b> (и дополнительно по <b>name</b>).</div>
      </div>
      <div class="col-3">
        <label>Количество (на 1 получателя)</label>
        <input type="number" id="eggCount" placeholder="1" min="1" step="1" value="1">
        <div class="note">Ограничение: до 500 за запрос</div>
      </div>
      <div class="col-3">
        <label>Shiny</label>
        <select id="shineMode">
          <option value="force0">Обычное (shine=0)</option>
          <option value="force1">Shiny (shine=1)</option>
          <option value="random">Случайно (как в plusEgg)</option>
        </select>
      </div>

      <div class="col-3">
        <label>Trade</label>
        <select id="trade">
          <option value="0">Нет</option>
          <option value="1">Да</option>
        </select>
      </div>
      <div class="col-3">
        <label>Sparka</label>
        <select id="sparka">
          <option value="0">Нет</option>
          <option value="1">Да</option>
        </select>
      </div>
      <div class="col-3">
        <label>Form</label>
        <input type="number" id="form" placeholder="0" min="0" step="1" value="0">
      </div>
      <div class="col-3">
        <label>Вылупление (дней)</label>
        <input type="number" id="hatchDays" placeholder="0" min="0" max="60" step="1" value="0">
        <div class="note">0 = рандом 5–11 дней</div>
      </div>

      <div class="col-6">
        <label>Gens (опционально)</label>
        <input type="text" id="gens" placeholder="Напр.: 10,12,26,18,21,11" autocomplete="off">
        <div class="help">Если пусто — сгенерируется как в плюсEgg (10–26). Если задано — будет нормализовано в диапазон 0–31.</div>
      </div>
      <div class="col-6">
        <label>Character (опционально)</label>
        <input type="number" id="character" placeholder="0" min="0" max="26" step="1" value="0">
        <div class="help">0 = случайный (1–26).</div>
      </div>

      <div class="col-12"><div class="hr"></div></div>

      <div class="col-12">
        <label>Режим выдачи</label>
        <div class="toggle" id="modeToggle">
          <div class="chip active" data-mode="single">Одному</div>
          <div class="chip" data-mode="multi">Нескольким</div>
          <div class="chip" data-mode="all">Всем</div>
        </div>
      </div>

      <div class="col-6 mode mode-single">
        <label>Игрок (ID или ник)</label>
        <input list="usersList" id="userInput" placeholder="Например: 4 или Kara" autocomplete="off">
        <datalist id="usersList">
          <?php foreach($USERS as $u){ echo '<option value="'.htmlspecialchars($u['login']).'">#'.$u['id'].' '.htmlspecialchars($u['login']).'</option>'; } ?>
        </datalist>
        <div class="help">Можно ввести <b>ID</b> или ник — ID определится автоматически</div>
      </div>

      <div class="col-6 mode mode-multi" style="display:none">
        <label>Список пользователей (ID/ники)</label>
        <div class="tagbox" id="multiBox" onclick="document.getElementById('multiInput').focus()">
          <input id="multiInput" type="text" placeholder="Вводите ID/ники и жмите Enter" style="background:transparent;border:none;outline:none;color:var(--text);width:220px">
        </div>
        <div class="help">Разделяйте запятой или Enter</div>
      </div>

      <div class="col-12">
        <div class="actions" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">
          <button class="btn btn-primary" id="btnGiveEgg"><i class="fa fa-paper-plane"></i> Выдать яйца</button>
          <span class="note">Горячая клавиша: <kbd>Ctrl/⌘</kbd> + <kbd>Enter</kbd></span>
        </div>
        <div class="progress" style="margin-top:10px;display:none" id="eggProg"><span></span></div>
        <div id="eggStatus" class="status"></div>
      </div>
    </div>
  </div>
</div>

<script>
/* ===== Данные пользователей из PHP ===== */
const USERS = <?php echo json_encode($USERS, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
const USER_MAP = new Map(USERS.map(u => [u.login.toLowerCase(), u.id]));
const ALL_USER_IDS = USERS.map(u => u.id);

/* ===== Утилиты ===== */
const qs = (s,p=document)=>p.querySelector(s), qsa=(s,p=document)=>Array.from(p.querySelectorAll(s));
function isNumeric(v){ return /^[0-9]+$/.test(String(v).trim()); }
function resolveUserId(token){
  if (!token) return null;
  token = String(token).trim();
  if (isNumeric(token)) return parseInt(token,10);
  const id = USER_MAP.get(token.toLowerCase());
  return id ? parseInt(id,10) : null;
}
function notify(text, type){
  try { Game.notifications.main(String(text), String(type||'info')); }
  catch(e){ console.log('[notify]', type||'info', text); }
}
function setStatus(el, msg, ok=false){ el.textContent = msg; el.className = 'status ' + (ok ? 'ok' : 'err'); }

/* ===== Режим выдачи: single / multi / all ===== */
let MODE = 'single';
qsa('#modeToggle .chip').forEach(ch=>{
  ch.addEventListener('click', ()=>{
    qsa('#modeToggle .chip').forEach(c=>c.classList.remove('active'));
    ch.classList.add('active'); MODE = ch.dataset.mode;
    qsa('.mode').forEach(m=>m.style.display='none');
    qsa('.mode-'+MODE).forEach(m=>m.style.display='block');
  });
});

/* ===== MULTI input → теги ===== */
const tags = [];
const multiInput = qs('#multiInput'), multiBox = qs('#multiBox');
function renderTags(){
  multiBox.querySelectorAll('.tag').forEach(x=>x.remove());
  tags.forEach((t,i)=>{
    const tag = document.createElement('span'); tag.className='tag'; tag.textContent=t+' ';
    const b = document.createElement('button'); b.textContent='×'; b.title='Удалить'; b.onclick=()=>{ tags.splice(i,1); renderTags(); };
    tag.appendChild(b); multiBox.insertBefore(tag, multiInput);
  });
}
multiInput.addEventListener('keydown', e=>{
  if(e.key==='Enter' || e.key===','){
    e.preventDefault();
    const raw = multiInput.value.trim().replace(/,+$/,'');
    if(raw){ raw.split(/[ ,\n\t]+/).forEach(tok=>{ if(tok) tags.push(tok); }); renderTags(); multiInput.value=''; }
  }else if(e.key==='Backspace' && !multiInput.value && tags.length){ tags.pop(); renderTags(); }
});

/* ===== Выдача яиц ===== */
async function giveEggs(){
  const pokemon = qs('#pokeInput').value.trim();
  const count = parseInt(qs('#eggCount').value || '1', 10);
  const shineMode = qs('#shineMode').value;
  const trade = parseInt(qs('#trade').value || '0', 10);
  const sparka = parseInt(qs('#sparka').value || '0', 10);
  const form = parseInt(qs('#form').value || '0', 10);
  const hatchDays = parseInt(qs('#hatchDays').value || '0', 10);
  const gens = qs('#gens').value.trim();
  const character = parseInt(qs('#character').value || '0', 10);

  const statusEl = qs('#eggStatus');
  const prog = qs('#eggProg'), bar = prog.querySelector('span');

  if(!count || count < 1){ setStatus(statusEl, 'Некорректное количество', false); notify('Некорректное количество','error'); return; }

  let userIds = [];
  if(MODE==='single'){
    const token = qs('#userInput').value.trim();
    const id = resolveUserId(token);
    if(!id){ setStatus(statusEl, 'Не найден пользователь (ID или ник)', false); notify('Не найден пользователь','error'); return; }
    userIds = [id];
  }else if(MODE==='multi'){
    const all = tags.slice();
    if(multiInput.value.trim()) all.push(multiInput.value.trim());
    userIds = all.map(resolveUserId).filter(x=>Number.isInteger(x));
    if(userIds.length===0){ setStatus(statusEl, 'Добавьте ID/ники для массовой выдачи', false); notify('Добавьте получателей','error'); return; }
  }else if(MODE==='all'){
    userIds = ALL_USER_IDS.slice();
    if(userIds.length===0){ setStatus(statusEl, 'Список пользователей пуст', false); notify('Список пользователей пуст','error'); return; }
  }

  qs('#btnGiveEgg').disabled = true;
  prog.style.display='block'; bar.style.width='0%'; setStatus(statusEl, '', true);

  let ok=0, fail=0;
  for(let i=0;i<userIds.length;i++){
    const uid = userIds[i];
    try{
      const resp = await $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
          ajax: 1,
          user_id: uid,
          pokemon,
          count,
          shine_mode: shineMode,
          trade,
          sparka,
          form,
          hatch_days: hatchDays,
          gens,
          character
        }
      });
      let data;
      try{ data = (typeof resp==='string') ? JSON.parse(resp) : resp; }catch(e){ data={text:'Неверный ответ сервера', error:'error'}; }
      notify(data.text || 'Готово', data.error || 'success');
      if((data.error||'').toLowerCase()==='error'){ fail++; } else { ok++; }
    }catch(err){
      fail++; console.error('Ошибка запроса', err);
      notify('Ошибка сети при выдаче пользователю #'+uid, 'error');
    }
    bar.style.width = Math.round(((i+1)/userIds.length)*100)+'%';
    await new Promise(r=>setTimeout(r, 120));
  }

  const msg = `Готово: успешно ${ok}, ошибок ${fail}. (Яиц на каждого: ${count})`;
  setStatus(statusEl, msg, fail===0);
  qs('#btnGiveEgg').disabled = false;
}

qs('#btnGiveEgg').addEventListener('click', giveEggs);
document.addEventListener('keydown', e=>{
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='enter'){
    e.preventDefault(); giveEggs();
  }
});

/* ===== Подсказки: автозаполнение ID из ника ===== */
qs('#userInput').addEventListener('blur', e=>{
  const v = e.target.value.trim();
  if(!v) return;
  if(!isNumeric(v)){
    const id = resolveUserId(v);
    if(id){ e.target.value = id; notify('Найден ID: '+id,'info'); }
  }
});
</script>
</body>
</html>

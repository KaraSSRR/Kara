<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project.'/inc/conf/global.php';
if (!file_exists($patch_global)) { die('The problem with the connection files.'); }
require_once($patch_global);

/* Доступ только для пользователя с id = 4 */
session_start();
if (!isset($_SESSION['id']) || (int)$_SESSION['id'] !== 4) {
    http_response_code(403);
    die('<div style="margin:60px auto;max-width:420px;padding:36px 22px;background:#0e1116;border-radius:18px;box-shadow:0 10px 40px #0003;color:#e5e7eb;font-family:Inter,Arial,sans-serif;text-align:center">
        <div style="font-size:52px">⛔</div>
        <div style="font-size:20px;margin:8px 0;color:#c7b9ff">Доступ запрещён</div>
        <div style="font-size:14px;color:#9aa4b2">У вас нет прав для просмотра этой страницы.</div>
    </div>');
}

/* Получим данные для автокомплита */
$items  = $mysqli->query('SELECT `id`,`name` FROM `base_items` ORDER BY `name` ASC');
$attks  = $mysqli->query('SELECT `id`,`name` FROM `base_atk` ORDER BY `name` ASC');
$usersQ = $mysqli->query('SELECT `id`,`login` FROM `users` ORDER BY `id` ASC');

$USERS = [];
if ($usersQ) {
    while($u = $usersQ->fetch_assoc()){
        $USERS[] = ['id' => (int)$u['id'], 'login' => $u['login']];
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Админ — выдача предметов и атак</title>
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
.btn-ghost{color:var(--text);background:var(--card);border:1px solid var(--border)}
.btn-danger{color:#fff;background:linear-gradient(90deg,#ef4444,#fb7185)}
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
  <h1>Выдача предметов и атак</h1>
  <div class="muted">Все операции отправляются на <code>/do/adm/pokemonAction</code>. Доступ к странице есть только у admin-4. Массовая выдача работает последовательно с прогрессом.</div>

  <!-- ВЫДАЧА ПРЕДМЕТОВ -->
  <div class="card">
    <div class="badge"><i class="fa fa-box-open"></i> Выдача предмета</div>
    <div class="grid" style="margin-top:8px">
      <div class="col-6">
        <label>Предмет</label>
        <input list="itemsList" id="itemName" placeholder="Начните вводить..." autocomplete="off">
        <datalist id="itemsList">
          <?php if($items){ while($p=$items->fetch_assoc()){ echo '<option value="'.htmlspecialchars($p['name']).'">#'.$p['id'].' '.htmlspecialchars($p['name']).'</option>'; } } ?>
        </datalist>
      </div>
      <div class="col-3">
        <label>Количество</label>
        <input type="number" id="itemCount" placeholder="1" min="1" step="1">
        <div class="note">Без лимита — укажите любое число</div>
      </div>
      <div class="col-3">
        <label>Флаги</label>
        <select id="isReward">
          <option value="0">Обычная выдача</option>
          <option value="1">Награда</option>
        </select>
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
          <button class="btn btn-primary" id="btnGiveItem"><i class="fa fa-paper-plane"></i> Выдать предмет</button>
          <span class="note">Горячая клавиша: <kbd>Ctrl/⌘</kbd> + <kbd>Enter</kbd></span>
        </div>
        <div class="progress" style="margin-top:10px;display:none" id="itemProg"><span></span></div>
        <div id="itemStatus" class="status"></div>
      </div>
    </div>
  </div>

  <!-- ВЫДАЧА АТАК -->
  <div class="card">
    <div class="badge"><i class="fa fa-bolt"></i> Выдача атаки по названию</div>
    <div class="grid" style="margin-top:8px">
      <div class="col-6">
        <label>Атака</label>
        <input list="attkList" id="atkName" placeholder="Начните вводить..." autocomplete="off">
        <datalist id="attkList">
          <?php if($attks){ while($a=$attks->fetch_assoc()){ echo '<option value="'.htmlspecialchars($a['name']).'">#'.$a['id'].' '.htmlspecialchars($a['name']).'</option>'; } } ?>
        </datalist>
      </div>
      <div class="col-6">
        <label>ID покемона</label>
        <input type="number" id="pokId1" placeholder="0" min="0" step="1">
      </div>
      <div class="col-12">
        <button class="btn btn-primary" id="btnGiveAtk1"><i class="fa fa-paper-plane"></i> Выдать</button>
        <div id="atkStatus1" class="status"></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="badge"><i class="fa fa-bolt"></i> Выдача атаки по ID</div>
    <div class="grid" style="margin-top:8px">
      <div class="col-6">
        <label>ID атаки</label>
        <input type="number" id="atkId" placeholder="0" min="0" step="1">
      </div>
      <div class="col-6">
        <label>ID покемона</label>
        <input type="number" id="pokId2" placeholder="0" min="0" step="1">
      </div>
      <div class="col-12">
        <button class="btn btn-primary" id="btnGiveAtk2"><i class="fa fa-paper-plane"></i> Выдать</button>
        <div id="atkStatus2" class="status"></div>
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
    if(raw){ raw.split(/[,\s]+/).forEach(tok=>{ if(tok) tags.push(tok); }); renderTags(); multiInput.value=''; }
  }else if(e.key==='Backspace' && !multiInput.value && tags.length){ tags.pop(); renderTags(); }
});

/* ===== Выдача предметов ===== */
async function giveItem(){
  const name = qs('#itemName').value.trim();
  const count = parseInt(qs('#itemCount').value || '1', 10);
  const isReward = qs('#isReward').value === '1';
  const statusEl = qs('#itemStatus');
  const prog = qs('#itemProg'), bar = prog.querySelector('span');

  if(!name){ setStatus(statusEl, 'Укажите предмет', false); notify('Укажите предмет','error'); return; }
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

  // последовательная выдача
  qs('#btnGiveItem').disabled = true;
  prog.style.display='block'; bar.style.width='0%'; setStatus(statusEl, '', true);

  let ok=0, fail=0;
  for(let i=0;i<userIds.length;i++){
    const uid = userIds[i];
    try{
      const resp = await $.ajax({
        url: "/do/adm/pokemonAction",
        method: "POST",
        data: "items=" + [name, count, uid, isReward].join(","),
      });
      let data;
      try{ data = (typeof resp==='string') ? JSON.parse(resp) : resp; }catch(e){ data={text:'Неверный ответ сервера', error:'error'}; }
      // Game.notifications совместим
      notify(data.text || 'Готово', data.error || 'success');
      if((data.error||'').toLowerCase()==='error'){ fail++; } else { ok++; }
    }catch(err){
      fail++; console.error('Ошибка запроса', err);
      notify('Ошибка сети при выдаче пользователю #'+uid, 'error');
    }
    bar.style.width = Math.round(((i+1)/userIds.length)*100)+'%';
    await new Promise(r=>setTimeout(r, 120)); // мягкий троттлинг
  }

  const msg = `Готово: успешно ${ok}, ошибок ${fail}.`;
  setStatus(statusEl, msg, fail===0);
  qs('#btnGiveItem').disabled = false;
}

/* Кнопка и шорткат */
qs('#btnGiveItem').addEventListener('click', giveItem);
document.addEventListener('keydown', e=>{
  if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='enter'){
    e.preventDefault(); giveItem();
  }
});

/* ===== Выдача атак ===== */
function giveAttackByName(){
  const name = qs('#atkName').value.trim();
  const pok  = parseInt(qs('#pokId1').value||'0',10);
  const target = qs('#atkStatus1');
  if(!name || !pok){ setStatus(target, 'Укажите атаку и ID покемона', false); notify('Укажите атаку и покемона','error'); return; }
  $.ajax({
    url: "/do/adm/pokemonAction",
    type: "POST",
    data: "attack="+[name,pok].join(","),
    success: function(resp){
      try{ resp = (typeof resp==='string')?JSON.parse(resp):resp; }catch(e){ resp={text:'Неверный ответ',error:'error'}; }
      notify(resp.text, resp.error); setStatus(target, resp.text, (resp.error||'')!=='error');
      qs('#atkName').value=''; qs('#pokId1').value='';
    },
    error: function(){ setStatus(target,'Ошибка сети',false); notify('Ошибка сети','error'); }
  });
}
function giveAttackById(){
  const atk = parseInt(qs('#atkId').value||'0',10);
  const pok = parseInt(qs('#pokId2').value||'0',10);
  const target = qs('#atkStatus2');
  if(!atk || !pok){ setStatus(target, 'Укажите ID атаки и ID покемона', false); notify('Укажите ID атаки и покемона','error'); return; }
  $.ajax({
    url: "/do/adm/pokemonAction",
    type: "POST",
    data: "attack2="+[atk,pok].join(","),
    success: function(resp){
      try{ resp = (typeof resp==='string')?JSON.parse(resp):resp; }catch(e){ resp={text:'Неверный ответ',error:'error'}; }
      notify(resp.text, resp.error); setStatus(target, resp.text, (resp.error||'')!=='error');
      qs('#atkId').value=''; qs('#pokId2').value='';
    },
    error: function(){ setStatus(target,'Ошибка сети',false); notify('Ошибка сети','error'); }
  });
}
qs('#btnGiveAtk1').addEventListener('click', giveAttackByName);
qs('#btnGiveAtk2').addEventListener('click', giveAttackById);

/* ===== Подсказки: автозаполнение ID из ника ===== */
qs('#userInput').addEventListener('blur', e=>{
  const v = e.target.value.trim();
  if(!v) return;
  if(!isNumeric(v)){ const id = resolveUserId(v); if(id){ e.target.value = id; notify('Найден ID: '+id,'info'); } }
});
</script>
</body>
</html>

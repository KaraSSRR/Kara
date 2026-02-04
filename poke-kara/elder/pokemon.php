<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project.'/inc/conf/global.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
    }
}

// ===== Доступ только для пользователя с id=4 =====
session_start();
if (!isset($_SESSION['id']) || (int)$_SESSION['id'] !== 4) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <title>Доступ запрещён</title>
        <style>
            :root{--bg:#0b0f14;--panel:#141a22;--brd:rgba(255,255,255,.08);--txt:#e2e6ef;--muted:#9aa6b6;--acc:#7c4dff}
            *{box-sizing:border-box} html,body{height:100%}
            body{margin:0;display:grid;place-items:center;background:radial-gradient(1100px 520px at 10% -10%,rgba(124,77,255,.18),transparent 60%),#0b0f14;color:var(--txt);font:16px/1.45 Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
            .card{width:min(92vw,560px);background:var(--panel);border:1px solid var(--brd);border-radius:18px;box-shadow:0 16px 40px rgba(0,0,0,.5);padding:28px;text-align:center}
            .ico{font-size:64px;line-height:1;margin:.1em 0 .25em 0}
            h1{margin:.1em 0 .25em 0;font-size:26px;color:#cbbaff}
            p{margin:.25em 0 .75em 0;color:var(--muted)}
            a{display:inline-block;color:#fff;text-decoration:none;background:linear-gradient(90deg,#7c4dff,#9a6cff);padding:10px 14px;border-radius:10px;font-weight:700}
        </style>
    </head>
    <body>
        <div class="card">
            <div class="ico">⛔</div>
            <h1>Доступ запрещён</h1>
            <p>У вас нет прав для просмотра этой страницы.</p>
            <a href="/">На главную</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ===== Подтянем список пользователей для автодополнения =====
$usersList = [];
if (isset($mysqli)) {
    if ($res = $mysqli->query('SELECT `id`,`login` FROM `users` ORDER BY `login` ASC')) {
        while ($u = $res->fetch_assoc()) {
            $usersList[] = [ 'id' => (int)$u['id'], 'login' => (string)$u['login'] ];
        }
        $res->close();
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="theme-color" content="#0b0f14">
    <title>Poke‑Route · Выдача покемона (админ)</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/fontawesome/css/all.css">
    <script src="/js/jquery/jquery.js"></script>
    <style>
        /* =====================  Чистый DARK UI (без сторонних CSS)  ===================== */
        :root{
            --bg:#0B0F14; --bg2:#0e131b; --panel:#121822; --panel2:#0e141c;
            --brd:rgba(255,255,255,.10); --txt:#E6EAF2; --muted:#9CA7B8;
            --acc:#7C4DFF; --acc2:#4DD0FF; --good:#3DDB85; --bad:#FF6B6B;
            --shadow:0 18px 48px rgba(0,0,0,.55);
            --r-lg:18px; --r-md:12px; --r-sm:9px; --s1:6px; --s2:10px; --s3:14px; --s4:18px; --s5:24px; --s6:32px;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{margin:0;color:var(--txt);background:radial-gradient(900px 420px at 15% -10%, rgba(124,77,255,.16), transparent 60%),radial-gradient(800px 520px at 120% 20%, rgba(77,208,255,.10), transparent 60%),linear-gradient(180deg, var(--bg) 0%, var(--bg2) 100%);font:16px/1.45 Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
        .container{max-width:1180px;margin:0 auto;padding:var(--s6) var(--s3)}
        .card{background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03)); border:1px solid var(--brd); border-radius:var(--r-lg); box-shadow:var(--shadow); padding:var(--s5)}
        h1{margin:0 0 var(--s3) 0; font-size:28px}
        p.lead{margin:0 0 var(--s4) 0; color:var(--muted)}

        form{display:grid; gap:var(--s4)}
        .grid{display:grid; grid-template-columns:repeat(12,1fr); gap:var(--s3)}
        .col-12{grid-column:span 12} .col-6{grid-column:span 12} .col-4{grid-column:span 12} .col-3{grid-column:span 12} .col-2{grid-column:span 12}
        @media(min-width:760px){ .col-6{grid-column:span 6} .col-4{grid-column:span 4} .col-3{grid-column:span 3} .col-2{grid-column:span 2} }

        label{font-size:12px; font-weight:800; letter-spacing:.02em; color:var(--muted)}
        .field{display:flex; flex-direction:column; gap:8px}
        input[type=text], input[type=number], select{height:42px; background:var(--panel2); color:var(--txt); border:1px solid var(--brd); border-radius:12px; padding:0 12px; outline:none; transition:.16s}
        input:focus, select:focus{border-color:#9a6cff; box-shadow:0 0 0 3px rgba(154,108,255,.18)}
        .tips{font-size:12px; color:var(--muted)}

        .radio{display:flex; gap:8px; flex-wrap:wrap}
        .radio .opt{position:relative}
        .radio input{position:absolute; inset:0; opacity:0}
        .radio .chip{display:inline-flex; align-items:center; justify-content:center; height:36px; padding:0 12px; min-width:70px; border-radius:10px; background:var(--panel2); color:var(--muted); border:1px solid var(--brd); font-weight:800}
        .radio input:checked + .chip{border-color:#a58bff; color:#fff; background:linear-gradient(180deg, #b3b6e6, #a6ddcf)}

        .checks{display:flex; gap:12px; flex-wrap:wrap}
        .checks label{display:flex; align-items:center; gap:8px; font-weight:700}
        .checks input{width:18px; height:18px}

        .kpis{display:flex; gap:10px; flex-wrap:wrap}
        .kpi-chip{display:inline-flex; align-items:center; gap:8px; height:30px; padding:0 10px; border-radius:999px; background:var(--panel2); border:1px solid var(--brd); color:var(--muted); font-weight:800}

        .actions{display:flex; gap:10px; flex-wrap:wrap}
        .btn{appearance:none; border:none; cursor:pointer; height:44px; padding:0 16px; border-radius:12px; font-weight:900}
        .btn-primary{color:#fff; background:linear-gradient(90deg,#7c4dff,#9a6cff); box-shadow:0 14px 30px rgba(124,77,255,.3)}
        .btn-ghost{color:var(--txt); background:var(--panel2); border:1px solid var(--brd)}
        .btn-danger{color:#fff; background:linear-gradient(90deg,#ff6b6b,#ff8a8a)}

        .overlay{position:fixed; inset:0; display:none; align-items:center; justify-content:center; backdrop-filter:blur(3px)}
        .overlay.show{display:flex}
        .spinner{width:58px; height:58px; border:4px solid rgba(154,108,255,.25); border-top-color:#9a6cff; border-radius:50%; animation:spin 1s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}

        .modal{position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:16px}
        .modal.show{display:flex}
        .modal .dlg{width:min(96vw,760px); background:var(--panel); border:1px solid var(--brd); border-radius:16px; box-shadow:var(--shadow); padding:18px}
        .modal pre{white-space:pre-wrap; word-break:break-all; background:#0e141c; border:1px dashed var(--brd); padding:12px; border-radius:12px}

        /* Toast уведомления (fallback, если нет Game.notifications) */
        .toast{position:fixed; right:16px; bottom:16px; display:grid; gap:10px; z-index:1000}
        .toast .t{background:var(--panel); border:1px solid var(--brd); color:var(--txt); border-left:4px solid var(--acc); padding:10px 12px; border-radius:12px; box-shadow:var(--shadow); min-width:260px}
        .toast .t.success{border-left-color:var(--good)}
        .toast .t.error{border-left-color:var(--bad)}
        .toast .t.info{border-left-color:var(--acc2)}

        /* Блок с формами */
        .forms-help{display:flex; gap:8px; flex-wrap:wrap; margin-top:-6px}
        .badge{display:inline-flex; align-items:center; gap:6px; height:28px; padding:0 10px; border-radius:999px; background:var(--panel2); border:1px solid var(--brd); color:var(--muted); font-weight:800; cursor:pointer}
        .badge code{color:#cbbaff}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Выдача покемона</h1>
        <p class="lead">Все поля валидируются, доступны пресеты и предпросмотр запроса. Формат отправки на сервер полностью сохранён.</p>

        <form id="giveForm" autocomplete="off">
            <div class="kpis">
                <span class="kpi-chip"><i class="fa-solid fa-id-badge"></i> Целевой тренер: <b id="kpiUser">—</b></span>
                <span class="kpi-chip"><i class="fa-solid fa-lock"></i> Доступ к странице: admin‑4</span>
            </div>

            <div class="actions" style="margin-top:8px">
                <button type="button" class="btn btn-ghost" id="presetTournament"><i class="fa-solid fa-trophy"></i> Турнирный пресет</button>
                <span class="tips">Подсказка: Alt+1 — быстрый вызов пресета</span>
            </div>

            <!-- Целевой тренер -->
            <div class="grid">
                <div class="field col-6">
                    <label for="userNick">Никнейм тренера</label>
                    <input list="usersList" id="userNick" placeholder="Начните вводить ник">
                    <datalist id="usersList">
                        <?php foreach($usersList as $u){ echo '<option value="'.htmlspecialchars($u['login']).'">#'.(int)$u['id'].' '.htmlspecialchars($u['login'])."</option>"; } ?>
                    </datalist>
                </div>
                <div class="field col-6">
                    <label for="user">ID тренера</label>
                    <input type="number" id="user" min="1" placeholder="Автозаполнение по нику">
                </div>
            </div>

            <!-- Основные -->
            <div class="grid">
                <div class="field col-6">
                    <label for="navegador">Покемон</label>
                    <input list="navegadores" id="naveгador" placeholder="Начните вводить имя" required>
                    <datalist id="navegadores">
                        <?php $pok = $mysqli->query('SELECT * FROM `base_pokemons` ORDER BY `id` ASC'); while($p = $pok->fetch_assoc()){ echo '<option value="'.htmlspecialchars($p['name_rus']).'">'.htmlspecialchars($p['name_rus'])."</option>"; } ?>
                    </datalist>
                </div>
                <div class="field col-2">
                    <label for="lvl">Уровень</label>
                    <input type="number" id="lvl" min="1" max="100" placeholder="1–100" required>
                </div>
                <div class="field col-2">
                    <label for="form">Форма</label>
                    <input type="text" id="form" value="0" placeholder="0/alola/galar/mega" required>
                </div>
                <div class="field col-2">
                    <label for="navegador2">Предмет</label>
                    <input list="navegadores2" id="navegador2" placeholder="ID или имя">
                    <datalist id="navegadores2">
                        <?php $item = $mysqli->query('SELECT * FROM `base_items`'); while($i = $item->fetch_assoc()){ echo '<option value="'.htmlspecialchars($i['id']).'">'.htmlspecialchars($i['name'])."</option>"; } ?>
                    </datalist>
                </div>
            </div>

            <!-- Подсказка по формам (кликабельно) -->
            <div class="forms-help" aria-label="Подсказки по формам">
                <span class="badge" data-form="0"><code>0</code> нет формы</span>
                <span class="badge" data-form="alola"><code>alola</code> Алола</span>
                <span class="badge" data-form="galar"><code>galar</code> Галар</span>
                <span class="badge" data-form="mega"><code>mega</code> Мега</span>
            </div>

            <!-- Настройки -->
            <div class="grid">
                <div class="field col-3">
                    <label for="SortAbility">Слот способности</label>
                    <select id="SortAbility">
                        <option value="1">Слот X</option>
                        <option value="2">Слот Y</option>
                        <option value="3">Скрытая</option>
                    </select>
                </div>
                <div class="field col-3">
                    <label for="SortTren">Тренировка</label>
                    <select id="SortTren">
                        <option value="0">Нет</option>
                        <option value="1">Начальная</option>
                        <option value="2">Расширенная</option>
                        <option value="3">Мастерская</option>
                        <option value="4">Знаменитая</option>
                        <option value="5">Легендарная</option>
                        <option value="6">Именная</option>
                    </select>
                </div>
                <div class="field col-3">
                    <label for="SortTrenStat">Стат тренировки</label>
                    <select id="SortTrenStat">
                        <option value="0">Нет</option>
                        <option value="1">Атака</option>
                        <option value="2">Защита</option>
                        <option value="3">Скорость</option>
                        <option value="4">Спец. Атака</option>
                        <option value="5">Спец. Защита</option>
                    </select>
                </div>
                <div class="field col-3">
                    <label for="SortCharacter">Характер</label>
                    <select id="SortCharacter">
                        <option value="1">Веселый</option>
                        <option value="2">Выносливый</option>
                        <option value="3">Застенчивый</option>
                        <option value="4">Кроткий</option>
                        <option value="5">Мирный</option>
                        <option value="6">Мягкий</option>
                        <option value="7">Наглый</option>
                        <option value="8">Наивный</option>
                        <option value="9">Нахальный</option>
                        <option value="10">Нежный</option>
                        <option value="11">Непослушный</option>
                        <option value="12">Непреклонный</option>
                        <option value="13">Обычный</option>
                        <option value="14">Одинокий</option>
                        <option value="15">Озорной</option>
                        <option value="16">Осторожный</option>
                        <option value="17">Поспешный</option>
                        <option value="18">Причудливый</option>
                        <option value="19">Распущенный</option>
                        <option value="20">Робкий</option>
                        <option value="21">Серьёзный</option>
                        <option value="22">Скромный</option>
                        <option value="23">Смелый</option>
                        <option value="24">Спокойный</option>
                        <option value="25">Стремительный</option>
                        <option value="26">Тихий</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                <div class="field col-3">
                    <label for="Okras">Окрас</label>
                    <select id="Okras">
                        <option value="normal">Обычный</option>
                        <option value="shine">Шайни</option>
                    </select>
                </div>
                <div class="field col-3">
                    <label for="GenSex">Пол</label>
                    <select id="GenSex">
                        <option value="Мальчик">Мальчик</option>
                        <option value="Девочка">Девочка</option>
                        <option value="Бесполый">Бесполый</option>
                    </select>
                </div>
                <div class="field col-3">
                    <label for="ev">Свободные EV</label>
                    <input type="number" id="ev" min="0" max="510" value="0" placeholder="0–510">
                </div>
            </div>

            <!-- IVs -->
            <div class="grid">
                <div class="field col-2"><label for="hp">HP</label><input type="number" id="hp" min="0" max="31" value="28"></div>
                <div class="field col-2"><label for="atk">Атака</label><input type="number" id="atk" min="0" max="31" value="28"></div>
                <div class="field col-2"><label for="def">Защита</label><input type="number" id="def" min="0" max="31" value="28"></div>
                <div class="field col-2"><label for="spd">Скорость</label><input type="number" id="spd" min="0" max="31" value="28"></div>
                <div class="field col-2"><label for="satk">Сп. Атака</label><input type="number" id="satk" min="0" max="31" value="28"></div>
                <div class="field col-2"><label for="sdef">Сп. Защита</label><input type="number" id="sdef" min="0" max="31" value="28"></div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" id="btnMaxIV"><i class="fa-solid fa-wand-magic-sparkles"></i> Макс. IV</button>
                <button type="button" class="btn btn-ghost" id="btnRandIV"><i class="fa-solid fa-shuffle"></i> Случайные IV</button>
                <button type="button" class="btn btn-ghost" id="btnClearIV"><i class="fa-regular fa-circle-xmark"></i> Очистить IV</button>
                <button type="button" class="btn btn-ghost" id="btnToggleShiny"><i class="fa-regular fa-star"></i> Shiny</button>
            </div>

            <!-- Радио -->
            <div class="grid">
                <div class="field col-6">
                    <label>Спарен</label>
                    <div class="radio" role="radiogroup" aria-label="Спарен">
                        <label class="opt"><input type="radio" name="reprod" value="1" checked><span class="chip">Да</span></label>
                        <label class="opt"><input type="radio" name="reprod" value="0"><span class="chip">Нет</span></label>
                    </div>
                </div>
                <div class="field col-6">
                    <label>Приручен</label>
                    <div class="radio" role="radiogroup" aria-label="Приручен">
                        <label class="opt"><input type="radio" name="trade" value="false" checked><span class="chip">Да</span></label>
                        <label class="opt"><input type="radio" name="trade" value="true"><span class="chip">Нет</span></label>
                    </div>
                </div>
            </div>

            <!-- Флаги -->
            <div class="grid">
                <div class="field col-12">
                    <label>Что было использовано?</label>
                    <div class="checks">
                        <label><input type="checkbox" id="color-1" value="candy_choc"> Шоколадная конфета</label>
                        <label><input type="checkbox" id="color-2" value="candy_sr"> Горькая конфета</label>
                        <label><input type="checkbox" id="color-3" value="candy_cooc"> Сладкий кекс</label>
                        <label><input type="checkbox" id="color-4" value="apricorn"> Корень априкорна</label>
                        <label><input type="checkbox" id="color-5" value="medicine"> Лекарство</label>
                        <label><input type="checkbox" id="color-6" value="gormon"> Гормональные</label>
                        <label><input type="checkbox" id="color-7" value="potion"> Зелье памяти</label>
                        <label><input type="checkbox" id="color-8" value="pills"> Пилюля</label>
                    </div>
                </div>
            </div>

            <!-- Действия -->
            <div class="actions">
                <button class="btn btn-primary" type="submit" id="btnGive"><i class="fa-solid fa-gift"></i> Выдать</button>
                <button class="btn btn-ghost" type="button" id="btnPreview"><i class="fa-regular fa-eye"></i> Предпросмотр</button>
                <button class="btn btn-ghost" type="button" id="btnReset"><i class="fa-solid fa-rotate"></i> Сброс</button>
                <span class="kpi-chip" id="stateChip"><i class="fa-regular fa-circle-check"></i> Автосохранение</span>
            </div>
        </form>
    </div>
</div>

<!-- Оверлей -->
<div class="overlay" id="loader" aria-hidden="true"><div class="spinner" role="status"></div></div>
<div class="toast" id="toast"></div>

<!-- Модал -->
<div class="modal" id="modalPreview" aria-hidden="true">
    <div class="dlg">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px">
            <strong>Предпросмотр payload</strong>
            <div>
                <button class="btn btn-ghost" id="btnCopyPayload"><i class="fa-regular fa-copy"></i> Копировать</button>
                <button class="btn btn-danger" id="btnClosePreview"><i class="fa-regular fa-circle-xmark"></i> Закрыть</button>
            </div>
        </div>
        <pre id="payloadText"></pre>
    </div>
</div>

<script>
(function(){
    const $form   = window.jQuery ? jQuery('#giveForm') : null;
    const $loader = window.jQuery ? jQuery('#loader') : null;
    const $modal  = window.jQuery ? jQuery('#modalPreview') : null;
    const $payloadText = window.jQuery ? jQuery('#payloadText') : null;
    const LS_KEY = 'poke_admin_state_v4';

    // Карты пользователей из PHP
    window.USER_MAP = <?php echo json_encode(array_column($usersList, 'id', 'login'), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    (function(){ const _id2login = {}; <?php foreach($usersList as $u){ echo '_id2login['.(int)$u['id'].'] = '.json_encode($u['login'], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).";
"; } ?> window.USER_ID2LOGIN = _id2login; })();

    // ===== Уведомления =====
    function addToast(msg, type){
        const box = document.getElementById('toast');
        const t = document.createElement('div');
        t.className = 't '+(type||'info');
        t.textContent = msg;
        box.appendChild(t);
        setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateY(8px)'; }, 2600);
        setTimeout(()=>{ box.removeChild(t); }, 3200);
    }
    function notify(text, type){
        if (window.Game && Game.notifications && typeof Game.notifications.main === 'function'){
            Game.notifications.main(text, type || 'info');
        } else { addToast(text, type); }
    }

    // ===== Синхронизация ID <-> ник =====
    function syncFromNick(){
        const nick = document.getElementById('userNick').value;
        const id = (window.USER_MAP||{})[nick];
        if(id){ document.getElementById('user').value = id; document.getElementById('kpiUser').textContent = String(id); }
        else { document.getElementById('kpiUser').textContent = document.getElementById('user').value || '—'; }
        autosave();
    }
    function syncFromId(){
        const id = document.getElementById('user').value;
        const nick = (window.USER_ID2LOGIN||{})[id];
        if(nick){ document.getElementById('userNick').value = nick; }
        document.getElementById('kpiUser').textContent = id || '—';
        autosave();
    }
    document.getElementById('userNick').addEventListener('input', syncFromNick);
    document.getElementById('user').addEventListener('input', syncFromId);

    // ===== Пресеты =====
    function setChecks(list, on){
        const all = ['#color-1','#color-2','#color-3','#color-4','#color-5','#color-6','#color-7','#color-8'];
        all.forEach(id => { const el = document.querySelector(id); if(el) el.checked = false; });
        (list||[]).forEach(id => { const el = document.querySelector(id); if(el) el.checked = !!on; });
    }
    function applyTournamentPreset(){
        document.getElementById('SortAbility').value = '3';
        document.getElementById('SortTren').value = '5';
        document.getElementById('SortTrenStat').value = '3';
        setChecks(['#color-6','#color-5','#color-1','#color-2','#color-4'], true);
        notify('Пресет применён: турнирный','info');
        autosave();
    }
    // Кнопка — привязываем и vanilla, и jQuery (на всякий случай)
    const presetBtn = document.getElementById('presetTournament');
    if(presetBtn){ presetBtn.addEventListener('click', applyTournamentPreset); }
    if(window.jQuery){ jQuery('#presetTournament').on('click', applyTournamentPreset); }
    // Хоткей Alt+1
    document.addEventListener('keydown', (e)=>{ if(e.altKey && e.key==='1'){ e.preventDefault(); applyTournamentPreset(); }});

    // ===== Кликабельные формы (бейджи) =====
    document.querySelectorAll('.badge[data-form]').forEach(b=>{
        b.addEventListener('click', ()=>{ document.getElementById('form').value = b.getAttribute('data-form'); autosave(); });
    });

    // ===== IV helpers =====
    function maxIV(){ ['hp','atk','def','spd','satk','sdef'].forEach(id=>document.getElementById(id).value=31); }
    function clearIV(){ ['hp','atk','def','spd','satk','sdef'].forEach(id=>document.getElementById(id).value=''); }
    function randIV(){ ['hp','atk','def','spd','satk','sdef'].forEach(id=>document.getElementById(id).value=Math.floor(Math.random()*32)); }
    document.getElementById('btnMaxIV').addEventListener('click', maxIV);
    document.getElementById('btnClearIV').addEventListener('click', clearIV);
    document.getElementById('btnRandIV').addEventListener('click', randIV);
    document.getElementById('btnToggleShiny').addEventListener('click', ()=>{ const s=document.getElementById('Okras'); s.value = (s.value==='shine'?'normal':'shine'); autosave(); });

    // ===== Автосохранение =====
    function collectState(){
        const g = id=>document.getElementById(id);
        return {
            userNick:g('userNick').value, user:g('user').value,
            name:g('naveгador').value, item:g('navegador2').value, lvl:g('lvl').value, form:g('form').value,
            char:g('SortCharacter').value, abil:g('SortAbility').value, tren:g('SortTren').value, tren_st:g('SortTrenStat').value,
            okras:g('Okras').value, sex:g('GenSex').value, ev:g('ev').value,
            iv:{ hp:g('hp').value, atk:g('atk').value, def:g('def').value, spd:g('spd').value, satk:g('satk').value, sdef:g('sdef').value },
            flags:{ id1:document.getElementById('color-1').checked, id2:document.getElementById('color-2').checked, id3:document.getElementById('color-3').checked, id4:document.getElementById('color-4').checked, id5:document.getElementById('color-5').checked, id6:document.getElementById('color-6').checked, id7:document.getElementById('color-7').checked, id8:document.getElementById('color-8').checked }
        };
    }
    function applyState(s){ if(!s) return; const g=id=>document.getElementById(id); g('userNick').value=s.userNick||''; g('user').value=s.user||''; document.getElementById('kpiUser').textContent=g('user').value||'—'; g('naveгador').value=s.name||''; g('navegador2').value=s.item||''; g('lvl').value=s.lvl||''; g('form').value=s.form||'0'; g('SortCharacter').value=s.char||'13'; g('SortAbility').value=s.abil||'1'; g('SortTren').value=s.tren||'0'; g('SortTrenStat').value=s.tren_st||'0'; g('Okras').value=s.okras||'normal'; g('GenSex').value=s.sex||'Мальчик'; g('ev').value=s.ev||'0'; if(s.iv){ g('hp').value=s.iv.hp||''; g('atk').value=s.iv.atk||''; g('def').value=s.iv.def||''; g('spd').value=s.iv.spd||''; g('satk').value=s.iv.satk||''; g('sdef').value=s.iv.sdef||''; } if(s.flags){ document.getElementById('color-1').checked=!!s.flags.id1; document.getElementById('color-2').checked=!!s.flags.id2; document.getElementById('color-3').checked=!!s.flags.id3; document.getElementById('color-4').checked=!!s.flags.id4; document.getElementById('color-5').checked=!!s.flags.id5; document.getElementById('color-6').checked=!!s.flags.id6; document.getElementById('color-7').checked=!!s.flags.id7; document.getElementById('color-8').checked=!!s.flags.id8; } }
    function autosave(){ localStorage.setItem(LS_KEY, JSON.stringify(collectState())); const chip=document.getElementById('stateChip'); chip.innerHTML='<i class="fa-regular fa-floppy-disk"></i> Сохранено'; clearTimeout(window.__chipTimer); window.__chipTimer=setTimeout(()=>chip.innerHTML='<i class="fa-regular fa-circle-check"></i> Автосохранение',1200); }
    document.querySelectorAll('#giveForm input, #giveForm select').forEach(el=>{ el.addEventListener('input', autosave); el.addEventListener('change', autosave); });
    (function init(){ const raw = localStorage.getItem(LS_KEY); if(raw){ try{ applyState(JSON.parse(raw)); }catch(e){} } })();

    // ===== Предпросмотр =====
    function toggleModal(v){ if(!$modal) return; $modal.toggleClass('show', !!v).attr('aria-hidden', v? 'false':'true'); }
    const btnPreview = document.getElementById('btnPreview'); if(btnPreview){ btnPreview.addEventListener('click', ()=>{ const payload = buildPayload(true); if($payloadText) $payloadText.text('type='+encodeURIComponent(payload)); toggleModal(true); }); }
    const btnClosePreview = document.getElementById('btnClosePreview'); if(btnClosePreview){ btnClosePreview.addEventListener('click', ()=>toggleModal(false)); }
    const btnCopyPayload = document.getElementById('btnCopyPayload'); if(btnCopyPayload){ btnCopyPayload.addEventListener('click', ()=>{ navigator.clipboard.writeText($payloadText ? $payloadText.text() : ''); notify('Скопировано в буфер','info'); }); }

    // ===== Submit =====
    if($form){ $form.on('submit', function(e){ e.preventDefault(); give(); }); } else { document.getElementById('giveForm').addEventListener('submit', (e)=>{ e.preventDefault(); give(); }); }

    function buildPayload(allowAutoUser){
        const g=id=>document.getElementById(id);
        const name=g('naveгador').value.trim(); const item=g('navegador2').value.trim(); const lvl=g('lvl').value; const form=g('form').value.trim(); const char=g('SortCharacter').value; const abil=g('SortAbility').value; const tren=g('SortTren').value; const tren_st=g('SortTrenStat').value; const okras=g('Okras').value; const sex=g('GenSex').value; const ev=g('ev').value; const genHP=g('hp').value; const genA=g('atk').value; const genD=g('def').value; const genS=g('spd').value; const genSA=g('satk').value; const genSD=g('sdef').value; const reprod=document.querySelector('input[name="reprod"]:checked').value; const trade=document.querySelector('input[name="trade"]:checked').value; const id1=document.getElementById('color-1').checked; const id2=document.getElementById('color-2').checked; const id3=document.getElementById('color-3').checked; const id4=document.getElementById('color-4').checked; const id5=document.getElementById('color-5').checked; const id6=document.getElementById('color-6').checked; const id7=document.getElementById('color-7').checked; const id8=document.getElementById('color-8').checked; let user=g('user').value; if(allowAutoUser && (!user||user==='')){ const nick=g('userNick').value.trim(); if(nick && (window.USER_MAP||{})[nick]){ user=String(window.USER_MAP[nick]); g('user').value=user; } } return [name,lvl,form,char,okras,sex,ev,genHP,genA,genD,genS,genSA,genSD,reprod,trade,id1,id2,id3,id4,id5,id6,id7,id8,user,abil,item,tren,tren_st].join(','); }

    function give(){
        const name = document.getElementById('naveгador').value.trim();
        const lvl  = +document.getElementById('lvl').value;
        if(!name){ notify('Укажите покемона','error'); return; }
        if(!lvl || lvl<1 || lvl>100){ notify('Уровень должен быть от 1 до 100','error'); return; }
        let candidateUser = document.getElementById('user').value;
        if(!candidateUser){ const nick = document.getElementById('userNick').value.trim(); if(nick && (window.USER_MAP||{})[nick]){ candidateUser = String(window.USER_MAP[nick]); document.getElementById('user').value=candidateUser; } }
        if(!candidateUser){ notify('Укажите ID или ник тренера','error'); return; }

        const all = buildPayload(false);
        if($loader){ $loader.addClass('show').attr('aria-hidden','false'); } else { document.getElementById('loader').classList.add('show'); }
        if(window.jQuery){
            jQuery.ajax({ url:"/elder/pokemonAction", type:"POST", data:"type="+encodeURIComponent(all), success:function(resp){ try{ resp=(typeof resp==='string')?JSON.parse(resp):resp; }catch(e){ resp={text:'Некорректный ответ сервера', error:'error'} } notify(String(resp.text||'Готово'), String(resp.error||'info')); notify('Покемон успешно выдан тренеру #'+candidateUser, 'success'); jQuery('#naveгador, #navegador2, #lvl, #userNick, #user').val(''); document.getElementById('kpiUser').textContent='—'; autosave(); }, error:function(){ notify('Сервер недоступен или ошибка сети','error'); }, complete:function(){ if($loader){ $loader.removeClass('show').attr('aria-hidden','true'); } } });
        } else {
            // На случай отсутствия jQuery — fallback на fetch
            fetch('/elder/pokemonAction', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'type='+encodeURIComponent(all) })
            .then(r=>r.text()).then(txt=>{ let resp; try{ resp=JSON.parse(txt); }catch(e){ resp={text:'Готово', error:'info'} } notify(String(resp.text||'Готово'), String(resp.error||'info')); notify('Покемон успешно выдан тренеру #'+candidateUser, 'success'); ['naveгador','navegador2','lvl','userNick','user'].forEach(id=>document.getElementById(id).value=''); document.getElementById('kpiUser').textContent='—'; autosave(); })
            .catch(()=>notify('Сервер недоступен или ошибка сети','error'))
            .finally(()=>{ document.getElementById('loader').classList.remove('show'); });
        }
    }
})();
</script>
</body>
</html>

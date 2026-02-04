<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
if (!file_exists($patch_global)) { die('The problem with the connection files.'); }
require_once($patch_global);

/** Доступ: только пользователь с id = 4 */
session_start();
if (!isset($_SESSION['id']) || (int)$_SESSION['id'] !== 4) {
    http_response_code(403); ?>
    <!doctype html><html lang="ru"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Доступ запрещён</title>
    <style>
      :root{--bg:#0b0f14;--panel:#141a22;--brd:rgba(255,255,255,.08);--txt:#e2e6ef;--muted:#9aa6b6}
      *{box-sizing:border-box} html,body{height:100%}
      body{margin:0;display:grid;place-items:center;background:#0b0f14;color:var(--txt);font:16px/1.45 Inter,system-ui,Segoe UI,Roboto,Arial}
      .card{width:min(92vw,560px);background:var(--panel);border:1px solid var(--brd);border-radius:18px;box-shadow:0 16px 40px rgba(0,0,0,.5);padding:28px;text-align:center}
      .ico{font-size:64px;margin:.1em 0 .25em} h1{margin:.1em 0;font-size:26px;color:#cbbaff}
      p{margin:.25em 0 .75em;color:var(--muted)}
    </style></head><body><div class="card"><div class="ico">⛔</div><h1>Доступ запрещён</h1><p>У вас нет прав для просмотра этой страницы.</p></div></body></html>
    <?php exit;
}

/** имя файла */
function generateFileName($extension='png'){ return uniqid('news_', true).'.'.$extension; }

/** загрузка изображения (PNG/GIF) */
function handleImageUpload($inputName='image', $uploadDir='/upload/news/'){
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) return '';
    $allowed = ['image/png'=>'png','image/gif'=>'gif'];
    $tmp = $_FILES[$inputName]['tmp_name'];
    $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : $_FILES[$inputName]['type'];
    if (!isset($allowed[$mime])) return '';
    $ext = $allowed[$mime];
    $path = rtrim($_SERVER['DOCUMENT_ROOT'].$uploadDir, '/').'/';
    if (!is_dir($path)) mkdir($path,0755,true);
    $name = generateFileName($ext);
    if (move_uploaded_file($tmp, $path.$name)) return rtrim($uploadDir,'/').'/'.$name;
    return '';
}

/** санитайз текста */
function sanitizeNewsHtml($html){
    $allowed = '<b><strong><i><em><u><s><strike><a><ul><ol><li><br><p><span>';
    $html = preg_replace('#on\w+="[^"]*"#i','',$html);
    $html = preg_replace("#on\w+='[^']*'#i",'',$html);
    $html = preg_replace('#javascript:#i','',$html);
    return strip_tags($html,$allowed);
}

/** POST -> JSON */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($mysqli) || !$mysqli) { echo json_encode(["error"=>"Ошибка подключения к базе данных."]); exit; }

    $author = isset($_POST['author']) ? trim($_POST['author']) : 'Администратор';
    $date   = date("Y-m-d H:i:s");
    $title  = isset($_POST['title']) ? trim($_POST['title']) : '';
    $text   = isset($_POST['text'])  ? sanitizeNewsHtml($_POST['text']) : '';

    $color  = isset($_POST['color']) ? trim($_POST['color']) : '#000000';
    $font   = isset($_POST['font'])  ? trim($_POST['font'])  : 'Arial';
    $size   = isset($_POST['size'])  ? (int)$_POST['size']   : 16;
    $bold   = !empty($_POST['bold'])   ? 'font-weight: bold;'  : '';
    $italic = !empty($_POST['italic']) ? 'font-style: italic;' : '';

    $allowedFonts = ['Hagin','Arial','Verdana','Times New Roman','Courier New','Georgia','Comic Sans MS','Trebuchet MS'];
    if (!in_array($font,$allowedFonts,true)) $font='Arial';

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) $image = handleImageUpload('image');
    elseif (!empty($_POST['image'])) $image = trim($_POST['image']);

    if ($title==='' || trim(strip_tags($text))==='') { echo json_encode(["error"=>"Заполните все поля."]); exit; }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    $style = "font-family:{$font}; font-size:{$size}px; color:{$color}; {$bold}{$italic}";
    $newsContent = "<div class=\"__title\" style=\"{$style}\">{$safeTitle}</div><div class=\"__text\">{$text}</div>";
    if ($image !== '') $newsContent = "<img src=\"".htmlspecialchars($image,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')."\" class=\"news-image\" alt=\"Новость\"> ".$newsContent;

    $stmt = $mysqli->prepare("INSERT INTO `news` (`author`,`date`,`text`,`img`) VALUES (?,?,?,?)");
    if (!$stmt) { echo json_encode(["error"=>"Ошибка подготовки запроса: ".$mysqli->error]); exit; }
    $stmt->bind_param("ssss", $author, $date, $newsContent, $image);
    $ok = $stmt->execute();
    echo json_encode($ok ? ["success"=>"✅ Новость успешно добавлена!"] : ["error"=>"Ошибка при добавлении новости: ".$stmt->error]);
    $stmt->close();
    exit;
}
?>
<!doctype html>
<html lang="ru" data-theme="dark">
<head>
<meta charset="utf-8">
<title>Добавление новостей</title>
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
<meta name="theme-color" content="#0b0f14">
<style>
:root{
  --bg:#0B0F14; --surface:#121822; --card:#151d28; --text:#E6EAF2; --muted:#9CA7B8;
  --primary:#7C4DFF; --accent:#4DD0FF; --border:rgba(255,255,255,.10);
  --good:#3DDB85; --bad:#FF6B6B; --shadow:0 18px 48px rgba(0,0,0,.55); --radius:16px;
}
html[data-theme="light"]{--bg:#f6f7fb;--surface:#fff;--card:#fff;--text:#0e1014;--muted:#586273;--primary:#6842ff;--accent:#00b4ff;--border:rgba(0,0,0,.10);--shadow:0 12px 30px rgba(0,0,0,.08)}
html[data-theme="violet"]{--bg:#0f0a1f;--surface:#1a1236;--card:#201645;--text:#ece6ff;--muted:#bdb3e9;--primary:#a78bfa;--accent:#7dd3fc;--border:rgba(255,255,255,.12)}
html[data-theme="aqua"]{--bg:#06131a;--surface:#0a202b;--card:#0d2733;--text:#e2fbff;--muted:#9fd0dc;--primary:#00d0ff;--accent:#6dffac;--border:rgba(255,255,255,.10)}
html[data-theme="amoled"]{--bg:#000;--surface:#0a0a0a;--card:#0e0e0e;--text:#f1f1f1;--muted:#9c9c9c;--primary:#7C4DFF;--accent:#4DD0FF;--border:rgba(255,255,255,.08)}

*{box-sizing:border-box} html,body{height:100%}
body{
  margin:0;color:var(--text);
  background:
    radial-gradient(900px 420px at 10% -10%, rgba(124,77,255,.18), transparent 60%),
    radial-gradient(800px 520px at 120% 20%, rgba(77,208,255,.12), transparent 60%),
    linear-gradient(180deg, var(--bg) 0%, #0e131b 100%);
  font:16px/1.45 Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
}
.wrap{max-width:1180px;margin:0 auto;padding:28px 14px}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px}
@media (max-width:860px){ .grid{grid-template-columns:repeat(6,1fr)} }
@media (max-width:560px){ .grid{grid-template-columns:repeat(2,1fr)} }

.card{background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:18px}
.header{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:10px}
h1{margin:0;font-size:22px}
.muted{color:var(--muted);font-size:14px}

label{font-size:12px;color:var(--muted);font-weight:800;letter-spacing:.02em}
.field{display:flex;flex-direction:column;gap:8px}
input[type=text], input[type=number], input[type=color], select{height:42px;background:var(--card);color:var(--text);border:1px solid var(--border);border-radius:10px;padding:0 12px;outline:none;transition:.16s}
input:focus, select:focus{border-color:var(--primary); box-shadow:0 0 0 3px rgba(124,77,255,.20)}

.toolbar{display:flex;flex-wrap:wrap;gap:6px}
.toolbtn{height:36px;padding:0 10px;background:var(--card);border:1px solid var(--border);border-radius:10px;color:var(--text);cursor:pointer;font-weight:800}
.toolbtn:hover{border-color:var(--primary)}
.toolbtn .dot{display:inline-block;width:14px;height:14px;border-radius:4px;border:1px solid var(--border);margin-left:6px;vertical-align:middle}
.editor{min-height:160px;max-height:320px;overflow:auto;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:10px}
.editor:empty:before{content:attr(data-placeholder); color:var(--muted)}

.drop{display:flex;align-items:center;justify-content:center;gap:8px;background:var(--card);border:1px dashed var(--border);border-radius:10px;padding:16px;cursor:pointer;text-align:center}
.drop.drag{border-color:var(--accent)}
.preview-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px}
.preview-card img{max-width:100%;border-radius:10px;margin-bottom:8px}

.actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{height:44px;border:none;border-radius:12px;padding:0 16px;font-weight:900;cursor:pointer}
.btn-primary{color:#fff;background:linear-gradient(90deg,var(--primary),#9a6cff)}
.btn-ghost{color:var(--text);background:var(--card);border:1px solid var(--border)}
.btn-danger{color:#fff;background:linear-gradient(90deg,#ff6b6b,#ff8a8a)}
.status{min-height:22px;margin-top:6px;font-weight:700}
.status.ok{color:#3DDB85}.status.err{color:#FF6B6B}

.theme-switch{display:flex;gap:8px;flex-wrap:wrap}
.chip{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 12px;border-radius:999px;background:var(--card);border:1px solid var(--border);cursor:pointer;font-weight:800}
.chip.active{border-color:var(--primary);box-shadow:0 0 0 3px rgba(124,77,255,.18)}

.toast{position:fixed;right:16px;bottom:16px;display:grid;gap:10px;z-index:2000}
.toast .t{background:var(--surface);border:1px solid var(--border);color:var(--text);border-left:4px solid var(--primary);padding:10px 12px;border-radius:12px;box-shadow:var(--shadow);min-width:260px}
.toast .t.ok{border-left-color:var(--good)} .toast .t.err{border-left-color:var(--bad)}

/* ===== Поповер цвета (общий для ТЕКСТ/ПОДСВЕТКА) ===== */
.color-pop{position:fixed;z-index:3000;display:none;min-width:280px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:10px;box-shadow:var(--shadow)}
.color-pop.show{display:block}
.pop-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}
.pop-head .modes{display:flex;gap:6px}
.modebtn{height:30px;border:1px solid var(--border);background:var(--card);color:var(--text);border-radius:8px;padding:0 10px;cursor:pointer}
.modebtn.active{border-color:var(--primary)}
.color-row{display:flex;gap:8px;flex-wrap:wrap;margin:6px 0}
.swatch{width:24px;height:24px;border-radius:6px;border:1px solid var(--border);cursor:pointer}
.grad-wrap{margin:6px 0} #gradCanvas{display:block;width:100%;height:18px;border-radius:8px;border:1px solid var(--border);cursor:crosshair}
.pop-actions{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-top:6px}
.mini-input{height:32px;width:46px;border:1px solid var(--border);border-radius:8px;background:var(--card);color:var(--text);padding:0 6px}
.tiny-btn{height:32px;border:1px solid var(--border);background:var(--card);color:var(--text);border-radius:8px;padding:0 10px;cursor:pointer}
.tiny-btn:hover{border-color:var(--primary)}
.fav-list{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.fav-item{width:22px;height:22px;border-radius:6px;border:1px solid var(--border);cursor:pointer;position:relative}
.fav-item .rm{position:absolute;right:-7px;top:-7px;background:#0008;border:none;border-radius:50%;width:16px;height:16px;color:#fff;font-size:11px;line-height:16px;cursor:pointer}

/* ===== Палитра и избранные для заголовка ===== */
#titlePaletteRow{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>
      <h1>Добавление новости</h1>
      <div class="muted">Форматирование, предпросмотр, PNG/GIF, автосохранение, палитры «плитка/градиент» с избранным, темы, Ctrl/⌘+Enter.</div>
    </div>
    <div class="theme-switch" id="themeSwitch">
      <div class="chip" data-theme="light">🌤️ Light</div>
      <div class="chip active" data-theme="dark">🌙 Dark</div>
      <div class="chip" data-theme="violet">💜 Violet</div>
      <div class="chip" data-theme="aqua">🌊 Aqua</div>
      <div class="chip" data-theme="amoled">🖤 Amoled</div>
    </div>
  </div>

  <div class="grid">
    <!-- ЛЕВАЯ -->
    <div class="card col-8">
      <div class="grid">
        <div class="field col-6"><label for="author">Автор</label><input type="text" id="author" placeholder="Введите имя автора"></div>
        <div class="field col-6"><label for="title">Заголовок</label><input type="text" id="title" placeholder="Введите заголовок" maxlength="200"></div>
      </div>

      <div class="field">
        <label>Текст новости</label>
        <div class="toolbar">
          <button class="toolbtn" type="button" data-cmd="bold"><b>B</b></button>
          <button class="toolbtn" type="button" data-cmd="italic"><i>I</i></button>
          <button class="toolbtn" type="button" data-cmd="underline"><u>U</u></button>
          <button class="toolbtn" type="button" data-cmd="strikeThrough"><s>S</s></button>

          <button class="toolbtn" type="button" id="btnClr">Цвет текста <span class="dot" id="dotClr"></span></button>
          <button class="toolbtn" type="button" id="btnBg">Подсветка <span class="dot" id="dotBg"></span></button>

          <button class="toolbtn" type="button" id="btnLink">🔗 Ссылка</button>
          <button class="toolbtn" type="button" id="btnUl">• Список</button>
          <button class="toolbtn" type="button" id="btnOl">1. Список</button>
          <button class="toolbtn" type="button" id="btnClearFmt">✖ Очистить</button>
        </div>
        <div id="text" class="editor" contenteditable="true" data-placeholder="Введите текст новости"></div>
      </div>

      <div class="grid">
        <div class="field col-6">
          <label for="image">Изображение (PNG, GIF)</label>
          <div class="drop" id="dropZone">
            <input type="file" id="image" accept="image/png, image/gif" style="display:none">
            <span id="pickFile">Перетащите файл сюда или нажмите для выбора</span>
          </div>
        </div>
        <div class="field col-6">
          <label>Предпросмотр изображения</label>
          <div class="preview-card">
            <img id="imgPreview" alt="" style="display:none">
            <div class="muted" id="imgHint">Нет изображения</div>
          </div>
        </div>
      </div>

      <div class="actions" style="margin-top:10px">
        <button class="btn btn-primary" id="sendBtn">Добавить новость (Ctrl/⌘+Enter)</button>
        <button class="btn btn-ghost" id="btnPreview">Предпросмотр карточки</button>
        <button class="btn btn-danger" id="btnClear">Очистить черновик</button>
      </div>
      <div class="status" id="statusMsg"></div>
    </div>

    <!-- ПРАВАЯ -->
    <div class="card col-4">
      <div class="field">
        <label for="color">Цвет заголовка</label>
        <input type="color" id="color" value="#5b5b5b">
        <div class="color-row" id="titlePaletteRow"></div>
        <div class="actions" style="margin-top:6px">
          <select id="titlePaletteSel" class="btn btn-ghost" style="height:36px">
            <option value="material">Material</option>
            <option value="pastel">Pastel</option>
            <option value="vivid">Vivid</option>
            <option value="muted">Muted</option>
            <option value="gray">Gray</option>
          </select>
          <button class="btn btn-ghost" id="titleAddFav" style="height:36px">★ в избранное</button>
        </div>
        <div class="fav-list" id="titleFavs"></div>
      </div>

      <div class="field">
        <label for="font">Шрифт заголовка</label>
        <select id="font">
          <option value="Hagin">Hagin</option><option value="Arial" selected>Arial</option>
          <option value="Verdana">Verdana</option><option value="Times New Roman">Times New Roman</option>
          <option value="Courier New">Courier New</option><option value="Georgia">Georgia</option>
          <option value="Comic Sans MS">Comic Sans MS</option><option value="Trebuchet MS">Trebuchet MS</option>
        </select>
      </div>
      <div class="field"><label for="size">Размер заголовка</label><input type="number" id="size" value="20" min="10" max="40"></div>
      <div class="field">
        <label>Форматирование заголовка</label>
        <div class="actions">
          <label class="chip"><input type="checkbox" id="bold" style="margin-right:6px">Жирный</label>
          <label class="chip"><input type="checkbox" id="italic" style="margin-right:6px">Курсив</label>
        </div>
      </div>

      <div class="field">
        <label>Живой предпросмотр</label>
        <div class="preview-card" id="newsPreview"><div class="muted">Пока пусто — добавьте заголовок/текст</div></div>
      </div>
    </div>
  </div>
</div>

<!-- Поповер выбора цвета (ТЕКСТ/ПОДСВЕТКА): ПЛИТКА/ГРАДИЕНТ + избранное -->
<div class="color-pop" id="colorPop">
  <div class="pop-head">
    <strong id="popTitle">Цвет текста</strong>
    <div class="modes">
      <button class="modebtn active" id="modeTiles">Плитка</button>
      <button class="modebtn" id="modeGradient">Градиент</button>
    </div>
  </div>
  <div class="color-row" id="paletteRow"></div>
  <div class="grad-wrap" id="gradWrap" style="display:none"><canvas id="gradCanvas" width="280" height="18"></canvas></div>
  <div class="pop-actions">
    <div>
      <input type="color" id="nativeColor" class="mini-input" title="Пипетка">
      <input type="text" id="hexInput" class="mini-input" maxlength="7" value="#ff6b6b" title="HEX">
      <button class="tiny-btn" id="applyBtn">Применить</button>
    </div>
    <div><select id="paletteSel" class="tiny-btn">
      <option value="material">Material</option><option value="pastel">Pastel</option>
      <option value="vivid">Vivid</option><option value="muted">Muted</option><option value="gray">Gray</option>
    </select></div>
  </div>
  <div class="fav-list" id="favRow"></div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const qs=(s,p=document)=>p.querySelector(s), qsa=(s,p=document)=>Array.from(p.querySelectorAll(s));
  const editor=qs('#text'), status=qs('#statusMsg'), toastBox=qs('#toast'), previewCard=qs('#newsPreview');
  const img=qs('#imgPreview'), imgHint=qs('#imgHint'), imageInput=qs('#image'), dropZone=qs('#dropZone'), pickFile=qs('#pickFile'), sendBtn=qs('#sendBtn');
  const dotClr=qs('#dotClr'), dotBg=qs('#dotBg');

  const colorPop=qs('#colorPop'), paletteSel=qs('#paletteSel'), paletteRow=qs('#paletteRow'), favRow=qs('#favRow');
  const nativeColor=qs('#nativeColor'), hexInput=qs('#hexInput'), applyBtn=qs('#applyBtn');
  const popTitle=qs('#popTitle'), modeTilesBtn=qs('#modeTiles'), modeGradBtn=qs('#modeGradient'), gradWrap=qs('#gradWrap'), gradCanvas=qs('#gradCanvas');

  const titlePaletteSel=qs('#titlePaletteSel'), titlePaletteRow=qs('#titlePaletteRow'), titleFavs=qs('#titleFavs'), inputTitleColor=qs('#color');

  const LS_DRAFT='news_draft_full', THEME_KEY='news_theme',
        FAV_TEXT='fav_text_colors', FAV_BG='fav_bg_colors', FAV_TITLE='fav_title_colors';

  const PALETTES={ material:['#EF4444','#F59E0B','#10B981','#3B82F6','#8B5CF6','#EC4899','#14B8A6','#F97316','#64748B','#111827'],
                   pastel:['#FCA5A5','#FBCFE8','#A7F3D0','#BFDBFE','#DDD6FE','#FDE68A','#C7D2FE','#FFE4E6','#D9F99D','#F5D0FE'],
                   vivid:['#ff1744','#ff9100','#1de9b6','#2979ff','#d500f9','#00e5ff','#ffea00','#00e676','#ff3d00','#f50057'],
                   muted:['#6B7280','#9CA3AF','#4B5563','#374151','#1F2937','#A78BFA','#93C5FD','#F59E0B','#10B981','#EF4444'],
                   gray:['#111827','#374151','#4B5563','#6B7280','#9CA3AF','#D1D5DB','#E5E7EB','#F3F4F6','#FFFFFF','#000000'] };

  /* ===== Темы ===== */
  function setTheme(name){ document.documentElement.setAttribute('data-theme',name); localStorage.setItem(THEME_KEY,name);
    qsa('#themeSwitch .chip').forEach(c=>c.classList.toggle('active',c.dataset.theme===name)); }
  setTheme(localStorage.getItem(THEME_KEY)||'dark');
  qsa('#themeSwitch .chip').forEach(c=>c.addEventListener('click',()=>setTheme(c.dataset.theme)));

  /* ===== Утилиты/уведомления ===== */
  function addToast(msg,cls){ const t=document.createElement('div'); t.className='t '+(cls||''); t.textContent=msg; toastBox.appendChild(t);
    setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(6px)';},2300); setTimeout(()=>t.remove(),3000); }
  function show(msg,err){ status.textContent=msg; status.className='status '+(err?'err':'ok'); addToast(msg,err?'err':'ok'); }
  function getPlainText(html){ const d=document.createElement('div'); d.innerHTML=html; return d.textContent||d.innerText||''; }
  const clampHex=v=>{v=v.trim(); if(!v.startsWith('#')) v='#'+v; if(v.length===4) v='#'+v[1]+v[1]+v[2]+v[2]+v[3]+v[3]; return v.slice(0,7).toUpperCase(); };

  /* ===== Редактор ===== */
  function cmd(c,a){ document.execCommand(c,false,a||null); updatePreview(); saveDraft(); }
  qsa('.toolbtn[data-cmd]').forEach(b=> b.addEventListener('click',()=>cmd(b.dataset.cmd)));
  qs('#btnLink').addEventListener('click',()=>{ const u=prompt('Введите адрес ссылки:','https://'); if(u&&u!=='https://') cmd('createLink',u); });
  qs('#btnUl').addEventListener('click',()=>cmd('insertUnorderedList')); qs('#btnOl').addEventListener('click',()=>cmd('insertOrderedList')); qs('#btnClearFmt').addEventListener('click',()=>cmd('removeFormat'));

  /* ===== Поповер: Плитка/Градиент + избранные ===== */
  let popMode='fore', colorMode='tiles', popAnchor=null; // fore|back, tiles|gradient
  function buildTiles(row, pal){ row.innerHTML=''; (PALETTES[pal]||[]).forEach(c=>{ const s=document.createElement('div'); s.className='swatch'; s.style.background=c; s.title=c; s.addEventListener('click',()=>applyImmediate(c)); row.appendChild(s); }); }
  function buildGradient(){ const ctx=gradCanvas.getContext('2d'), w=gradCanvas.width, h=gradCanvas.height; const gr=ctx.createLinearGradient(0,0,w,0); for(let i=0;i<=360;i+=10){ gr.addColorStop(i/360, `hsl(${i} 100% 50%)`); } ctx.fillStyle=gr; ctx.fillRect(0,0,w,h); }
  function pickFromGradient(e){ const r=gradCanvas.getBoundingClientRect(); const x=Math.max(0,Math.min(e.clientX-r.left, r.width-1)), y=Math.max(0,Math.min(e.clientY-r.top, r.height-1)); const sx=gradCanvas.width/r.width, sy=gradCanvas.height/r.height;
    const {data}=gradCanvas.getContext('2d').getImageData(Math.floor(x*sx),Math.floor(y*sy),1,1); const hex='#'+[data[0],data[1],data[2]].map(n=>n.toString(16).padStart(2,'0')).join('').toUpperCase(); applyImmediate(hex); }
  function openPop(anchor, mode){
    popMode=mode; popAnchor=anchor; popTitle.textContent=(mode==='fore'?'Цвет текста':'Подсветка');
    paletteSel.value=(localStorage.getItem('last_palette')||'material'); buildTiles(paletteRow, paletteSel.value);
    gradWrap.style.display = (colorMode==='gradient')?'block':'none'; paletteRow.style.display= (colorMode==='tiles')?'flex':'none';
    modeTilesBtn.classList.toggle('active', colorMode==='tiles'); modeGradBtn.classList.toggle('active', colorMode==='gradient'); if(colorMode==='gradient') buildGradient();
    const r=anchor.getBoundingClientRect(); colorPop.style.left=Math.min(r.left, window.innerWidth-300)+'px'; colorPop.style.top=(r.bottom+8)+'px'; colorPop.classList.add('show'); renderFav(favRow, popMode==='fore'?FAV_TEXT:FAV_BG);
  }
  function closePop(){ colorPop.classList.remove('show'); }
  function applyImmediate(color){ const col=clampHex(color); if(popMode==='fore'){ document.execCommand('foreColor',false,col); dotClr.style.background=col; } else { document.execCommand('hiliteColor',false,col); document.execCommand('backColor',false,col); dotBg.style.background=col; } hexInput.value=nativeColor.value=col; updatePreview(); saveDraft(); closePop(); }
  paletteSel.addEventListener('change', ()=>{ localStorage.setItem('last_palette',paletteSel.value); buildTiles(paletteRow, paletteSel.value); });
  nativeColor.addEventListener('input', ()=>{ hexInput.value=nativeColor.value.toUpperCase(); });
  hexInput.addEventListener('input', ()=>{ hexInput.value=clampHex(hexInput.value); nativeColor.value=hexInput.value; });
  applyBtn.addEventListener('click', ()=>applyImmediate(hexInput.value||'#ff6b6b'));
  modeTilesBtn.addEventListener('click', ()=>{ colorMode='tiles'; paletteRow.style.display='flex'; gradWrap.style.display='none'; modeTilesBtn.classList.add('active'); modeGradBtn.classList.remove('active'); });
  modeGradBtn.addEventListener('click', ()=>{ colorMode='gradient'; paletteRow.style.display='none'; gradWrap.style.display='block'; buildGradient(); modeGradBtn.classList.add('active'); modeTilesBtn.classList.remove('active'); });
  gradCanvas.addEventListener('click', pickFromGradient);
  function loadFav(key){ try{return JSON.parse(localStorage.getItem(key)||'[]');}catch(e){return[];} }
  function saveFav(key,arr){ localStorage.setItem(key, JSON.stringify(arr.slice(0,24))); }
  function renderFav(row,key){
    row.innerHTML=''; const favs=loadFav(key);
    favs.forEach((c,i)=>{ const d=document.createElement('div'); d.className='fav-item'; d.style.background=c; d.title=c; d.addEventListener('click',()=>applyImmediate(c));
      const rm=document.createElement('button'); rm.className='rm'; rm.textContent='×'; rm.addEventListener('click',e=>{ e.stopPropagation(); const a=loadFav(key); a.splice(i,1); saveFav(key,a); renderFav(row,key); });
      d.appendChild(rm); row.appendChild(d); });
    if(!favs.length){ const m=document.createElement('div'); m.className='muted'; m.textContent='Избранных нет'; m.style.fontSize='12px'; row.appendChild(m); }
  }
  qs('#btnClr').addEventListener('click', e=>openPop(e.currentTarget,'fore'));
  qs('#btnBg').addEventListener('click',  e=>openPop(e.currentTarget,'back'));
  qs('#addFavBtn')?.addEventListener('click', ()=>{ const key=popMode==='fore'?FAV_TEXT:FAV_BG; const c=clampHex(hexInput.value||'#ff6b6b'); const a=loadFav(key); if(!a.includes(c)) a.unshift(c); saveFav(key,a); renderFav(favRow,key); });
  document.addEventListener('click', e=>{ if(colorPop.classList.contains('show') && !colorPop.contains(e.target) && e.target!==popAnchor) closePop(); });
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') closePop(); });

  /* ===== Палитра заголовка (плитки + избранные) ===== */
  function buildTitlePalette(){ titlePaletteRow.innerHTML=''; (PALETTES[titlePaletteSel.value]||[]).forEach(c=>{ const s=document.createElement('div'); s.className='swatch'; s.style.background=c; s.title=c; s.addEventListener('click',()=>{ inputTitleColor.value=c; updatePreview(); saveDraft(); }); titlePaletteRow.appendChild(s); }); }
  function renderTitleFavs(){ titleFavs.innerHTML=''; const favs=loadFav(FAV_TITLE); favs.forEach((c,i)=>{ const d=document.createElement('div'); d.className='fav-item'; d.style.background=c; d.title=c; d.addEventListener('click',()=>{ inputTitleColor.value=c; updatePreview(); saveDraft(); }); const rm=document.createElement('button'); rm.className='rm'; rm.textContent='×'; rm.addEventListener('click',e=>{ e.stopPropagation(); const a=loadFav(FAV_TITLE); a.splice(i,1); saveFav(FAV_TITLE,a); renderTitleFavs(); }); d.appendChild(rm); titleFavs.appendChild(d); }); if(!favs.length){ const m=document.createElement('div'); m.className='muted'; m.textContent='Избранных нет'; m.style.fontSize='12px'; titleFavs.appendChild(m); } }
  qs('#titleAddFav').addEventListener('click', ()=>{ const c=clampHex(inputTitleColor.value||'#5b5b5b'); const a=loadFav(FAV_TITLE); if(!a.includes(c)) a.unshift(c); saveFav(FAV_TITLE,a); renderTitleFavs(); });
  titlePaletteSel.addEventListener('change', buildTitlePalette);
  inputTitleColor.addEventListener('input', ()=>{ updatePreview(); saveDraft(); });
  buildTitlePalette(); renderTitleFavs();

  /* ===== Изображение ===== */
  function setPreviewFile(file){ if(!file) return;
    if(!(file.type==='image/png'||file.type==='image/gif')){ show('Разрешены только изображения PNG и GIF.',true); return; }
    const r=new FileReader(); r.onload=e=>{ img.src=e.target.result; img.style.display='block'; imgHint.textContent=file.name; updatePreview(); }; r.readAsDataURL(file);
    const dt=new DataTransfer(); dt.items.add(file); imageInput.files=dt.files;
  }
  pickFile.addEventListener('click', ()=>imageInput.click());
  imageInput.addEventListener('change', e=> setPreviewFile(e.target.files[0]));
  ['dragenter','dragover'].forEach(t=>dropZone.addEventListener(t,e=>{e.preventDefault(); dropZone.classList.add('drag');}));
  ['dragleave','drop'].forEach(t=>dropZone.addEventListener(t,e=>{e.preventDefault(); dropZone.classList.remove('drag');}));
  dropZone.addEventListener('drop', e=> setPreviewFile(e.dataTransfer.files[0]));

  /* ===== Черновик ===== */
  function saveDraft(){
    const d={ author:qs('#author').value, title:qs('#title').value, text:editor.innerHTML, color:qs('#color').value,
              font:qs('#font').value, size:qs('#size').value, bold:qs('#bold').checked, italic:qs('#italic').checked,
              theme:document.documentElement.getAttribute('data-theme'), dotClr:dotClr.style.background||'', dotBg:dotBg.style.background||'' };
    localStorage.setItem(LS_DRAFT, JSON.stringify(d));
  }
  function loadDraft(){ const raw=localStorage.getItem(LS_DRAFT); if(!raw) return;
    try{ const d=JSON.parse(raw);
      qs('#author').value=d.author||''; qs('#title').value=d.title||''; editor.innerHTML=d.text||'';
      qs('#color').value=d.color||'#5b5b5b'; qs('#font').value=d.font||'Arial'; qs('#size').value=d.size||'20';
      qs('#bold').checked=!!d.bold; qs('#italic').checked=!!d.italic; if(d.theme) setTheme(d.theme);
      if(d.dotClr) dotClr.style.background=d.dotClr; if(d.dotBg) dotBg.style.background=d.dotBg; updatePreview();
    }catch(e){}
  }
  function clearDraft(){ localStorage.removeItem(LS_DRAFT); qs('#author').value=''; qs('#title').value=''; editor.innerHTML='';
    imageInput.value=''; img.src=''; img.style.display='none'; imgHint.textContent='Нет изображения'; dotClr.style.background=''; dotBg.style.background='';
    updatePreview(); show('Черновик очищен'); }
  qsa('input,select').forEach(el=> el.addEventListener('input',()=>{ saveDraft(); updatePreview(); }));
  editor.addEventListener('input', ()=>{ saveDraft(); updatePreview(); });
  qs('#btnClear').addEventListener('click', clearDraft);
  loadDraft();

  /* ===== Предпросмотр ===== */
  const escapeHtml=s=>s.replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  function updatePreview(){
    const title=qs('#title').value.trim(), text=editor.innerHTML.trim(), color=qs('#color').value, font=qs('#font').value;
    const size=parseInt(qs('#size').value||20,10), bold=qs('#bold').checked?'font-weight:bold;':'', italic=qs('#italic').checked?'font-style:italic;':'';
    if(!title && !text){ previewCard.innerHTML='<div class="muted">Пока пусто — добавьте заголовок/текст</div>'; return; }
    const imgHtml=(img && img.src && img.style.display!=='none')?`<img src="${img.src}" alt="">`:'';
    previewCard.innerHTML=`${imgHtml}<div style="font-family:${font};font-size:${size}px;color:${color};${bold}${italic}">${escapeHtml(title)}</div><div class="__text">${text}</div>`;
  }
  qs('#btnPreview').addEventListener('click', updatePreview);

  /* ===== Submit ===== */
  async function submitForm(){
    const author=qs('#author').value.trim()||'Администратор';
    const title =qs('#title').value.trim();
    const text  =editor.innerHTML.trim();
    if(!title || !getPlainText(text).trim()){ show('Пожалуйста, заполните заголовок и текст.', true); return; }

    const fd=new FormData();
    fd.append('author',author); fd.append('title',title); fd.append('text',text);
    fd.append('color',qs('#color').value); fd.append('font',qs('#font').value); fd.append('size',qs('#size').value);
    fd.append('bold',qs('#bold').checked?1:0); fd.append('italic',qs('#italic').checked?1:0);
    if(imageInput.files.length>0){ const f=imageInput.files[0]; if(!(f.type==='image/png'||f.type==='image/gif')){ show('Разрешены только изображения PNG и GIF.',true); return; } fd.append('image', f); }

    sendBtn.disabled=true; const prev=sendBtn.textContent; sendBtn.textContent='Отправка...'; status.textContent='';
    try{
      const resp=await fetch(window.location.pathname,{method:'POST',body:fd});
      const txt=await resp.text(); let data; try{ data=JSON.parse(txt); }catch(e){ data={error:'Сервер вернул неожиданное содержимое: '+txt.slice(0,180)}; }
      if(data.success){
        show(data.success,false);
        qs('#title').value=''; editor.innerHTML=''; imageInput.value=''; img.src=''; img.style.display='none'; imgHint.textContent='Нет изображения';
        saveDraft(); updatePreview();
      }else{ show(data.error||'Неизвестная ошибка.',true); }
    }catch(e){ show('Ошибка сети: '+e,true); }
    finally{ sendBtn.disabled=false; sendBtn.textContent=prev; }
  }
  sendBtn.addEventListener('click', submitForm);
  document.addEventListener('keydown', e=>{ if((e.ctrlKey||e.metaKey) && e.key.toLowerCase()==='enter'){ e.preventDefault(); submitForm(); } });

  /* ===== Тема на старте из LS ===== */
  const themeLS=localStorage.getItem('news_theme'); if(themeLS) { document.documentElement.setAttribute('data-theme', themeLS); }

})();
</script>
</body>
</html>

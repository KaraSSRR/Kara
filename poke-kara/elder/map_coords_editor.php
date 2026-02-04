<?php
/**
 * Админ-инструмент: проставление координат map_x/map_y в base_location кликом по карте.
 * Координаты сохраняются в пикселях исходного изображения карты (naturalWidth/naturalHeight).
 *
 * Требования:
 * - base_location.map_x, base_location.map_y уже существуют (у вас они есть, но NULL).
 * - доступ к $mysqli из /inc/conf/global.php.
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
session_start();

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
if (!file_exists($patch_global)) {
    die('The problem with the connection files.');
}
require_once($patch_global);

// Простая защита: если у вас есть роль/группа админа — вставьте проверку тут.
// Например: if (empty($_SESSION['id']) || ($_SESSION['group'] ?? 0) < 9) { http_response_code(403); die('Forbidden'); }

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Bad JSON'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $locId = isset($payload['id']) ? (int)$payload['id'] : 0;
    $x     = array_key_exists('x', $payload) ? $payload['x'] : null;
    $y     = array_key_exists('y', $payload) ? $payload['y'] : null;

    if ($locId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid id'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Если x/y не переданы (null) — очищаем координаты (NULL)
    if ($x === null || $y === null) {
        $stmt = $mysqli->prepare("UPDATE `base_location` SET `map_x`=NULL, `map_y`=NULL WHERE `id`=? LIMIT 1");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'DB prepare failed'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt->bind_param('i', $locId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true, 'id' => $locId, 'x' => null, 'y' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $x = (int)$x;
    $y = (int)$y;

    $stmt = $mysqli->prepare("UPDATE `base_location` SET `map_x`=?, `map_y`=? WHERE `id`=? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'DB prepare failed'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt->bind_param('iii', $x, $y, $locId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'id' => $locId, 'x' => $x, 'y' => $y], JSON_UNESCAPED_UNICODE);
    exit;
}

// GET: рисуем интерфейс
$locRes = $mysqli->query("SELECT `id`,`name`,`search_tipe`,`map_x`,`map_y` FROM `base_location` ORDER BY `id` ASC");
$locations = [];
if ($locRes) {
    while ($r = $locRes->fetch_assoc()) {
        $locations[] = $r;
    }
}

// Пути к карте: подстройте под ваш проект при необходимости
$mapCandidates = [
    '/img/world/map/region_map_upgraded_final.png',
    '/img/world/map/region_map.png',
    '/map.jpg'
];
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Map координаты — base_location</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; margin:16px; background:#0b1220; color:#e5e7eb;}
.card{background:#0f172a; border:1px solid rgba(255,255,255,.10); border-radius:14px; padding:14px; max-width:1400px; margin:0 auto;}
.row{display:flex; gap:14px; flex-wrap:wrap; align-items:flex-start;}
.controls{flex: 0 0 360px;}
.controls select{width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,.14); background:#111827; color:#fff;}
.small{opacity:.85; font-size:13px; margin-top:10px; line-height:1.35;}
.badge{display:inline-block; padding:2px 8px; border-radius:999px; border:1px solid rgba(255,255,255,.15); margin-left:8px; font-size:12px;}
.mapWrap{position:relative; flex: 1 1 720px; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.35);}
.mapWrap img{display:block; width:100%; height:auto; user-select:none;}
.overlay{position:absolute; inset:0; pointer-events:none;}
.marker{position:absolute; width:18px; height:18px; margin-left:-9px; margin-top:-9px; border-radius:50%;
 background:radial-gradient(circle at 35% 35%, #fff 0 30%, #ff3b30 31% 100%);
 box-shadow:0 0 0 4px rgba(0,0,0,.35), 0 0 18px rgba(255,59,48,.45);
}
.status{margin-top:10px; font-size:13px;}
.btn{margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,.14); background:#1f2937; color:#fff; cursor:pointer;}
.btn:hover{background:#273449;}
</style>
</head>
<body>
<div class="card">
  <h2 style="margin:0 0 12px 0;">Проставление координат локаций (map_x/map_y)</h2>
  <div class="row">
    <div class="controls">
      <label>Локация</label>
      <select id="locSelect">
        <option value="">— выберите —</option>
        <?php foreach ($locations as $l): ?>
          <option value="<?= (int)$l['id'] ?>"
            data-x="<?= htmlspecialchars($l['map_x'] ?? '') ?>"
            data-y="<?= htmlspecialchars($l['map_y'] ?? '') ?>"
            data-name="<?= htmlspecialchars($l['name'] ?? '') ?>"
            data-type="<?= htmlspecialchars($l['search_tipe'] ?? '') ?>"
          >
            #<?= (int)$l['id'] ?> — <?= htmlspecialchars($l['name'] ?? '') ?> (<?= htmlspecialchars($l['search_tipe'] ?? '') ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <div class="small">
        1) Выберите локацию<br/>
        2) Кликните по карте — координаты сохранятся в <code>base_location.map_x/map_y</code><br/>
        Координаты считаются в пикселях исходного изображения (natural size).
      </div>

      <div class="status" id="status">Статус: ожидание</div>
      <button class="btn" id="clearBtn" type="button">Очистить координаты (NULL)</button>
    </div>

    <div class="mapWrap">
      <img id="mapImg" alt="map" />
      <div class="overlay" id="overlay"></div>
    </div>
  </div>
</div>

<script>
const mapCandidates = <?= json_encode($mapCandidates, JSON_UNESCAPED_UNICODE) ?>;
const mapImg = document.getElementById('mapImg');
let candIdx = 0;
function tryLoadMap(){
  mapImg.src = mapCandidates[candIdx] + '?v=' + Date.now();
}
mapImg.onerror = () => {
  candIdx++;
  if (candIdx < mapCandidates.length) tryLoadMap();
  else document.getElementById('status').textContent = 'Статус: не найден файл карты. Проверьте пути в map_coords_editor.php';
};
tryLoadMap();

const select = document.getElementById('locSelect');
const overlay = document.getElementById('overlay');
const statusEl = document.getElementById('status');
let marker = null;

function setStatus(t){ statusEl.textContent = 'Статус: ' + t; }

function placeMarkerByNatural(x, y){
  if (!marker){
    marker = document.createElement('div');
    marker.className = 'marker';
    overlay.appendChild(marker);
  }
  const rect = mapImg.getBoundingClientRect();
  const nx = mapImg.naturalWidth || 1;
  const ny = mapImg.naturalHeight || 1;
  const sx = (x / nx) * rect.width;
  const sy = (y / ny) * rect.height;
  marker.style.left = sx + 'px';
  marker.style.top  = sy + 'px';
}

select.addEventListener('change', () => {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) { setStatus('выберите локацию'); return; }
  const x = opt.getAttribute('data-x');
  const y = opt.getAttribute('data-y');
  const nm = opt.getAttribute('data-name');
  const tp = opt.getAttribute('data-type');
  setStatus(`выбрано #${opt.value} — ${nm} (${tp})`);
  if (x !== '' && y !== '') placeMarkerByNatural(parseInt(x,10), parseInt(y,10));
});

mapImg.addEventListener('click', async (e) => {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) { setStatus('сначала выберите локацию'); return; }

  const rect = mapImg.getBoundingClientRect();
  const nx = mapImg.naturalWidth || 1;
  const ny = mapImg.naturalHeight || 1;

  const sx = e.clientX - rect.left;
  const sy = e.clientY - rect.top;

  const x = Math.round((sx / rect.width) * nx);
  const y = Math.round((sy / rect.height) * ny);

  placeMarkerByNatural(x, y);
  setStatus(`сохранение координат x=${x}, y=${y}...`);

  const res = await fetch(location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: parseInt(opt.value,10), x, y })
  });
  const data = await res.json();
  if (data.ok){
    // обновим атрибуты выбранного option
    opt.setAttribute('data-x', String(x));
    opt.setAttribute('data-y', String(y));
    setStatus(`сохранено #${data.id}: x=${data.x}, y=${data.y}`);
  } else {
    setStatus('ошибка сохранения: ' + (data.error || 'unknown'));
  }
});

document.getElementById('clearBtn').addEventListener('click', async () => {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) { setStatus('сначала выберите локацию'); return; }
  setStatus('очистка координат...');
  const res = await fetch(location.href, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ id: parseInt(opt.value,10), x: null, y: null })
  });
    const data = await res.json();
  if (data.ok){
    opt.setAttribute('data-x','');
    opt.setAttribute('data-y','');
    setStatus(`очищено #${data.id}: координаты NULL`);
    if (marker) { marker.remove(); marker = null; }
  } else {
    setStatus('ошибка очистки: ' + (data.error || 'unknown'));
  }
});
</script>
</body>
</html>

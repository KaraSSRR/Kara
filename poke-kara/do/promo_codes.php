<?php
/**
 * Promo Codes (standalone endpoint)
 * - Separate from Event Center
 * - Rewards and public codes are stored in FILES (JSON), not in DB
 * - DB tables users_code / users_code_active are used for limits and activation history
 * - Admin (user id=4 or user_group>=10): manage packs, create codes (public/personal), toggle active, view rewards
 *
 * Returns JSON: {html:"..."} or action results.
 */

$patch_project = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
$patch_global  = rtrim($patch_project, '/').'/inc/conf/global.php';
$patch_func    = rtrim($patch_project, '/').'/inc/function/Functions.php';

if (file_exists($patch_global)) require_once $patch_global;
if (file_exists($patch_func))   require_once $patch_func;

session_start();
header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function(){
    $e = error_get_last();
    if (!$e) return;
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($e['type'], $fatal, true)) return;

    if (headers_sent() === false) header('Content-Type: application/json; charset=utf-8', true, 200);
    echo json_encode(['error'=>'Server error', 'detail'=>$e['message'].' @ '.$e['file'].':'.$e['line']], JSON_UNESCAPED_UNICODE);
});

function pc_h($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function pc_db(){
    if (class_exists('Work') && isset(Work::$sql) && Work::$sql) return Work::$sql;
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
    return null;
}
function pc_json($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function pc_data_dir(){
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}
function pc_path_packs(){ return pc_data_dir().'/promo_packs.json'; }
function pc_path_codes(){ return pc_data_dir().'/promo_codes.json'; }

function pc_defaults(){
    return [
        'packs' => [
            'gengargang' => [
                'title' => 'GengarGang',
                'desc'  => 'Подарок для игроков сообщества.',
                'rewards' => [
                    ['id'=>1200,'count'=>50],
                    ['id'=>25,'count'=>3],
                ],
            ],
            'welcome' => [
                'title' => 'Welcome',
                'desc'  => 'Стартовый бонус к открытию игры.',
                'rewards' => [
                    ['id'=>1200,'count'=>25],
                ],
            ],
        ],
        'codes' => [
            // Example public code (file-based)
            // 'WELCOME-ABCDEF123456' => ['pack_id'=>'welcome','public'=>1,'user_id'=>0,'expires_at'=>0,'note'=>'','created_at'=>0],
        ],
        'rules' => [
            // Legacy mapping (DB codes without file definition)
            ['type'=>'exact','value'=>'GengarGang','pack_id'=>'gengargang'],
            ['type'=>'prefix','value'=>'WELCOME-','pack_id'=>'welcome'],
        ]
    ];
}

function pc_load_store(){
    $packsPath = pc_path_packs();
    $codesPath = pc_path_codes();

    $def = pc_defaults();

    if (!file_exists($packsPath)) {
        @file_put_contents($packsPath, json_encode($def['packs'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }
    if (!file_exists($codesPath)) {
        $init = ['codes'=>$def['codes'], 'rules'=>$def['rules']];
        @file_put_contents($codesPath, json_encode($init, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }

    $packs = json_decode((string)@file_get_contents($packsPath), true);
    if (!is_array($packs)) $packs = $def['packs'];

    $codesWrap = json_decode((string)@file_get_contents($codesPath), true);
    if (!is_array($codesWrap)) $codesWrap = ['codes'=>$def['codes'], 'rules'=>$def['rules']];
    if (!isset($codesWrap['codes']) || !is_array($codesWrap['codes'])) $codesWrap['codes'] = [];
    if (!isset($codesWrap['rules']) || !is_array($codesWrap['rules'])) $codesWrap['rules'] = $def['rules'];

    return [$packs, $codesWrap];
}
function pc_save_packs($packs){
    @file_put_contents(pc_path_packs(), json_encode($packs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
function pc_save_codeswrap($wrap){
    @file_put_contents(pc_path_codes(), json_encode($wrap, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
function pc_norm_code($code){
    $code = trim((string)$code);
    $code = preg_replace('/\s+/', '', $code);
    if ($code === '') return '';
    // allow letters, digits, dash, underscore
    if (!preg_match('/^[A-Za-z0-9\-_]{3,50}$/', $code)) return '';
    return $code;
}
function pc_rand($n=12){
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s='';
    for($i=0;$i<$n;$i++) $s .= $alphabet[random_int(0, strlen($alphabet)-1)];
    return $s;
}
function pc_get_item_names($db, $ids){
    $ids = array_values(array_unique(array_filter(array_map('intval',$ids))));
    if (!$ids) return [];
    $in = implode(',', $ids);
    $names = [];
    $res = $db->query("SELECT `id`,`name` FROM `base_items` WHERE `id` IN ($in)");
    if ($res) while($r=$res->fetch_assoc()) $names[(int)$r['id']] = $r['name'];
    return $names;
}
function pc_match_rule($rules, $code){
    foreach ($rules as $r) {
        $t = $r['type'] ?? '';
        $v = $r['value'] ?? '';
        $p = $r['pack_id'] ?? '';
        if (!$p) continue;
        if ($t==='exact' && strcasecmp($code, $v)===0) return $p;
        if ($t==='prefix' && stripos($code, $v)===0) return $p;
        if ($t==='regex' && @preg_match($v, $code)) return $p;
    }
    return '';
}

$user_id = (int)($_SESSION['id'] ?? 0);
if (!$user_id) pc_json(['error'=>'Ошибка авторизации']);
$db = pc_db();
if (!$db) pc_json(['error'=>'Нет соединения с БД']);

$urow = $db->query("SELECT `id`,`login`,`user_group` FROM `users` WHERE `id`=".$user_id." LIMIT 1")->fetch_assoc();
$user_group = (int)($urow['user_group'] ?? 0);
$is_admin = ($user_id === 4) || ($user_group >= 10);

list($packs, $codesWrap) = pc_load_store();
$codes = $codesWrap['codes'];
$rules = $codesWrap['rules'];

$type = (string)($_POST['type'] ?? 'open');

// -------------------- ACTIONS --------------------
if ($type === 'redeem') {
    $code = pc_norm_code($_POST['code'] ?? '');
    if (!$code) pc_json(['error'=>'Некорректный код']);

    $def = $codes[$code] ?? null;

    // Determine pack
    $pack_id = '';
    $expires_at = 0;
    $target_user = 0;
    $is_public = 0;

    if (is_array($def)) {
        $pack_id = (string)($def['pack_id'] ?? '');
        $expires_at = (int)($def['expires_at'] ?? 0);
        $target_user = (int)($def['user_id'] ?? 0);
        $is_public = (int)($def['public'] ?? 0);
    } else {
        // legacy rule mapping (DB-only codes)
        $pack_id = pc_match_rule($rules, $code);
    }

    if (!$pack_id || !isset($packs[$pack_id])) pc_json(['error'=>'Код не найден или не настроен']);
    if ($expires_at && time() > $expires_at) pc_json(['error'=>'Срок действия кода истёк']);
    if ($target_user && $target_user !== $user_id) pc_json(['error'=>'Этот код предназначен для другого аккаунта']);

    // Ensure code exists in DB
    $esc = $db->real_escape_string($code);
    $row = $db->query("SELECT * FROM `users_code` WHERE `code`='$esc' LIMIT 1")->fetch_assoc();
    if (!$row) {
        // create with defaults
        $max = 1;
        if (is_array($def) && isset($def['max'])) $max = max(1, (int)$def['max']);
        $db->query("INSERT INTO `users_code` (`code`,`uses`,`max`,`active`) VALUES ('$esc',0,$max,1)");
        $row = $db->query("SELECT * FROM `users_code` WHERE `code`='$esc' LIMIT 1")->fetch_assoc();
    }
    if (!$row) pc_json(['error'=>'Не удалось создать код в БД']);
    if ((int)$row['active'] !== 1) pc_json(['error'=>'Код отключён']);
    if ((int)$row['uses'] >= (int)$row['max']) pc_json(['error'=>'Лимит активаций исчерпан']);

    // Check already used
    $used = $db->query("SELECT `id` FROM `users_code_active` WHERE `user`=$user_id AND `code`='$esc' LIMIT 1")->fetch_assoc();
    if ($used) pc_json(['error'=>'Вы уже активировали этот код']);

    $db->query("INSERT INTO `users_code_active` (`user`,`code`) VALUES ($user_id,'$esc')");
    $db->query("UPDATE `users_code` SET `uses`=`uses`+1 WHERE `code`='$esc' LIMIT 1");

    $rewardList = $packs[$pack_id]['rewards'] ?? [];
    if (!is_array($rewardList)) $rewardList = [];

    // Item names for response
    $ids = [];
    foreach ($rewardList as $it) $ids[] = (int)($it['id'] ?? 0);
    $names = pc_get_item_names($db, $ids);

    $outRewards = [];
    foreach ($rewardList as $it) {
        $iid = (int)($it['id'] ?? 0);
        $cnt = (int)($it['count'] ?? 0);
        if ($iid <= 0 || $cnt <= 0) continue;

        if (function_exists('itemAdd')) {
            itemAdd($iid, $cnt, $user_id);
        } else {
            // last-resort direct insert (not recommended, but avoids silent loss)
            $db->query("INSERT INTO `items_users` (`user`,`item_id`,`count`) VALUES ($user_id,$iid,$cnt)");
        }
        $outRewards[] = ['id'=>$iid,'count'=>$cnt,'title'=>($names[$iid] ?? ('Предмет #'.$iid))];
    }

    pc_json([
        'text' => 'Промокод активирован',
        'rewards' => $outRewards,
        'pack_id' => $pack_id
    ]);
}

if (strpos($type, 'admin_') === 0) {
    if (!$is_admin) pc_json(['error'=>'Нет доступа']);
}

if ($type === 'admin_pack_create') {
    $pack_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['pack_id'] ?? ''));
    if (!$pack_id) pc_json(['error'=>'Некорректный pack_id']);
    if (isset($packs[$pack_id])) pc_json(['error'=>'Такой pack_id уже существует']);
    $packs[$pack_id] = ['title'=>$pack_id,'desc'=>'','rewards'=>[]];
    pc_save_packs($packs);
    pc_json(['text'=>'Пак создан']);
}

if ($type === 'admin_pack_save') {
    $pack_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['pack_id'] ?? ''));
    if (!$pack_id) pc_json(['error'=>'Некорректный pack_id']);
    if (!isset($packs[$pack_id])) $packs[$pack_id] = ['title'=>$pack_id,'desc'=>'','rewards'=>[]];

    $packs[$pack_id]['title'] = (string)($_POST['title'] ?? $packs[$pack_id]['title'] ?? $pack_id);
    $packs[$pack_id]['desc']  = (string)($_POST['desc'] ?? $packs[$pack_id]['desc'] ?? '');

    $rewards_json = trim((string)($_POST['rewards_json'] ?? ''));
    $rewards = json_decode($rewards_json, true);
    if (!is_array($rewards)) pc_json(['error'=>'Неверный JSON наград']);
    // normalize
    $norm = [];
    foreach ($rewards as $it) {
        $iid = (int)($it['id'] ?? 0);
        $cnt = (int)($it['count'] ?? 0);
        if ($iid>0 && $cnt>0) $norm[] = ['id'=>$iid,'count'=>$cnt];
    }
    $packs[$pack_id]['rewards'] = $norm;

    pc_save_packs($packs);
    pc_json(['text'=>'Пак сохранён']);
}

if ($type === 'admin_code_create') {
    $pack_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['pack_id'] ?? ''));
    if (!$pack_id || !isset($packs[$pack_id])) pc_json(['error'=>'Пак не найден']);

    $max = max(1, (int)($_POST['max'] ?? 1));
    $expires_days = (int)($_POST['expires_days'] ?? 0);
    $public = (int)($_POST['public'] ?? 0) ? 1 : 0;
    $target_user = max(0, (int)($_POST['user_id'] ?? 0));

    // generate code
    $prefix = strtoupper(substr($pack_id, 0, 4));
    $prefix = preg_replace('/[^A-Z0-9]/', 'X', $prefix);
    $code = $prefix . '-' . pc_rand(12);
    if (strlen($code) > 30) $code = substr($code, 0, 30);

    // ensure unique in file and DB
    $try = 0;
    while ((isset($codes[$code]) || $db->query("SELECT `id` FROM `users_code` WHERE `code`='".$db->real_escape_string($code)."' LIMIT 1")->fetch_assoc()) && $try < 10) {
        $code = $prefix . '-' . pc_rand(12);
        if (strlen($code) > 30) $code = substr($code, 0, 30);
        $try++;
    }
    if ($try >= 10) pc_json(['error'=>'Не удалось сгенерировать уникальный код']);

    $expires_at = 0;
    if ($expires_days > 0) $expires_at = time() + ($expires_days * 86400);

    $codes[$code] = [
        'pack_id' => $pack_id,
        'public' => $public,
        'user_id' => $target_user,
        'expires_at' => $expires_at,
        'note' => '',
        'created_at' => time(),
        'max' => $max,
    ];
    $codesWrap['codes'] = $codes;
    pc_save_codeswrap($codesWrap);

    $esc = $db->real_escape_string($code);
    $db->query("INSERT INTO `users_code` (`code`,`uses`,`max`,`active`) VALUES ('$esc',0,$max,1)");

    pc_json(['text'=>'Код создан', 'code'=>$code]);
}

if ($type === 'admin_code_toggle') {
    $code = pc_norm_code($_POST['code'] ?? '');
    if (!$code) pc_json(['error'=>'Некорректный код']);
    $active = (int)($_POST['active'] ?? 0) ? 1 : 0;
    $esc = $db->real_escape_string($code);
    $db->query("UPDATE `users_code` SET `active`=$active WHERE `code`='$esc' LIMIT 1");
    pc_json(['text'=> $active ? 'Код включён' : 'Код выключен']);
}

// -------------------- OPEN UI --------------------
function pc_render_rewards_inline($db, $rewardList){
    if (!is_array($rewardList) || !$rewardList) return '<span class="ecSmall">—</span>';
    $ids = [];
    foreach ($rewardList as $it) $ids[] = (int)($it['id'] ?? 0);
    $names = pc_get_item_names($db, $ids);

    $parts = [];
    foreach ($rewardList as $it) {
        $iid = (int)($it['id'] ?? 0);
        $cnt = (int)($it['count'] ?? 0);
        if ($iid<=0 || $cnt<=0) continue;
        $nm = pc_h($names[$iid] ?? ('Предмет #'.$iid));
        $parts[] = $nm.' x'.$cnt;
    }
    return $parts ? pc_h(implode(', ', $parts)) : '<span class="ecSmall">—</span>';
}

function pc_db_code_row($db, $code){
    $esc = $db->real_escape_string($code);
    return $db->query("SELECT * FROM `users_code` WHERE `code`='$esc' LIMIT 1")->fetch_assoc();
}

$public_cards = '';
foreach ($codes as $code=>$def) {
    $public = (int)($def['public'] ?? 0);
    if (!$public) continue;
    $expires_at = (int)($def['expires_at'] ?? 0);
    if ($expires_at && time() > $expires_at) continue;

    $pack_id = (string)($def['pack_id'] ?? '');
    if (!$pack_id || !isset($packs[$pack_id])) continue;

    $dbrow = pc_db_code_row($db, $code);
    $active = $dbrow ? (int)$dbrow['active'] : 1;
    $uses = $dbrow ? (int)$dbrow['uses'] : 0;
    $max = $dbrow ? (int)$dbrow['max'] : (int)($def['max'] ?? 1);

    if ($active !== 1) continue;

    $rewardInline = pc_render_rewards_inline($db, $packs[$pack_id]['rewards'] ?? []);
    $expTxt = $expires_at ? date('d.m.Y', $expires_at) : 'без срока';
    $public_cards .= '
      <div class="ecCard" style="margin-bottom:10px">
        <div class="ecRow"><span><b>'.pc_h($packs[$pack_id]['title'] ?? $pack_id).'</b></span><span class="ecTag">'.pc_h($code).'</span></div>
        <div class="ecSmall" style="margin-top:6px">'.pc_h($packs[$pack_id]['desc'] ?? '').'</div>
        <div class="ecSmall" style="margin-top:6px"><b>Награды:</b> '.$rewardInline.'</div>
        <div class="ecSmall" style="margin-top:6px">Доступно: <b>'.max(0, ($max-$uses)).'</b> / '.$max.' • Срок: <b>'.$expTxt.'</b></div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
          <button class="ecBtn" data-pc-action="copy" data-code="'.pc_h($code).'">Скопировать</button>
          <button class="ecBtn primary" data-pc-action="use" data-code="'.pc_h($code).'">Использовать</button>
        </div>
      </div>
    ';
}
if ($public_cards === '') $public_cards = '<div class="ecCard"><div class="ecSmall">Публичных промокодов сейчас нет.</div></div>';

// Admin blocks
$admin_html = '';
if ($is_admin) {
    // Packs editor
    $packs_html = '';
    foreach ($packs as $pid=>$p) {
        $pj = json_encode($p['rewards'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $packs_html .= '
          <details class="ecCard" style="margin-bottom:10px">
            <summary style="cursor:pointer;display:flex;justify-content:space-between;gap:10px;align-items:center">
              <span><b>'.pc_h($pid).'</b> — '.pc_h($p['title'] ?? '').'</span>
              <span class="ecTag">редактировать</span>
            </summary>
            <div style="margin-top:10px" class="ecGrid">
              <div>
                <div class="ecSmall">Название</div>
                <input class="ecInp" style="letter-spacing:0;font-family:inherit" data-pack-title="'.pc_h($pid).'" value="'.pc_h($p['title'] ?? '').'">
                <div class="ecSmall" style="margin-top:10px">Описание</div>
                <input class="ecInp" style="letter-spacing:0;font-family:inherit" data-pack-desc="'.pc_h($pid).'" value="'.pc_h($p['desc'] ?? '').'">
              </div>
              <div>
                <div class="ecSmall">Награды (JSON)</div>
                <textarea class="ecInp" data-pack-json="'.pc_h($pid).'" style="height:140px;letter-spacing:0;font-family:ui-monospace,monospace">'.pc_h($pj).'</textarea>
                <div style="margin-top:10px">
                  <button class="ecBtn primary" data-pc-action="admin_pack_save" data-pack="'.pc_h($pid).'">Сохранить пак</button>
                </div>
              </div>
            </div>
          </details>
        ';
    }
    $packs_html = $packs_html ?: '<div class="ecCard"><div class="ecSmall">Паков пока нет.</div></div>';

    // Codes list
    $codes_rows = '';
    $i = 0;
    foreach ($codes as $code=>$def) {
        if ($i >= 200) break;
        $i++;

        $pack_id = (string)($def['pack_id'] ?? '');
        $dbrow = pc_db_code_row($db, $code);
        $uses = $dbrow ? (int)$dbrow['uses'] : 0;
        $max  = $dbrow ? (int)$dbrow['max'] : (int)($def['max'] ?? 1);
        $active = $dbrow ? (int)$dbrow['active'] : 1;

        $public = (int)($def['public'] ?? 0);
        $uid = (int)($def['user_id'] ?? 0);
        $exp = (int)($def['expires_at'] ?? 0);
        $expTxt = $exp ? date('d.m.Y', $exp) : '∞';

        $rewardInline = ($pack_id && isset($packs[$pack_id])) ? pc_render_rewards_inline($db, $packs[$pack_id]['rewards'] ?? []) : '—';
        $toggleBtn = $active ? '<button class="ecBtn" data-pc-action="admin_code_toggle" data-code="'.pc_h($code).'" data-on="0">Выключить</button>'
                             : '<button class="ecBtn primary" data-pc-action="admin_code_toggle" data-code="'.pc_h($code).'" data-on="1">Включить</button>';

        $codes_rows .= '<tr>
          <td>'.pc_h($code).'</td>
          <td>'.pc_h($pack_id).'</td>
          <td>'.$uses.' / '.$max.'</td>
          <td>'.($active?'<span class="ecTag">on</span>':'<span class="ecTag">off</span>').'</td>
          <td>'.($public?'<span class="ecTag">public</span>':'<span class="ecTag">—</span>').'</td>
          <td>'.($uid?('#'.$uid):'—').'</td>
          <td>'.$expTxt.'</td>
          <td style="max-width:260px">'. $rewardInline .'</td>
          <td>'.$toggleBtn.'</td>
        </tr>';
    }
    if ($codes_rows==='') $codes_rows = '<tr><td colspan="9" class="ecSmall">Кодов в файле нет.</td></tr>';

    $packs_options = '';
    foreach ($packs as $pid=>$p) {
        $packs_options .= '<option value="'.pc_h($pid).'">'.pc_h($pid.' — '.($p['title'] ?? '')).'</option>';
    }

    $admin_html = '
      <div class="ecGrid">
        <div class="ecCard">
          <h4>Создать код</h4>
          <div class="ecSmall">Можно создать публичный или индивидуальный (user_id).</div>
          <div style="margin-top:10px">
            <div class="ecSmall">Пак</div>
            <select class="ecInp" data-admin-pack style="letter-spacing:0;font-family:inherit">'.$packs_options.'</select>
            <div class="ecGrid" style="margin-top:10px">
              <div>
                <div class="ecSmall">Лимит (max)</div>
                <input class="ecInp" data-admin-max type="number" value="1" min="1" style="letter-spacing:0;font-family:inherit">
              </div>
              <div>
                <div class="ecSmall">Срок (дней, 0 = без срока)</div>
                <input class="ecInp" data-admin-days type="number" value="0" min="0" style="letter-spacing:0;font-family:inherit">
              </div>
            </div>
            <div class="ecGrid" style="margin-top:10px">
              <div>
                <div class="ecSmall">Индивидуально для user_id (0 = всем)</div>
                <input class="ecInp" data-admin-user type="number" value="0" min="0" style="letter-spacing:0;font-family:inherit">
              </div>
              <div style="display:flex;align-items:flex-end;gap:10px">
                <label class="ecSmall" style="display:flex;gap:8px;align-items:center;margin:0">
                  <input type="checkbox" data-admin-public> Публичный
                </label>
              </div>
            </div>
            <div style="margin-top:10px">
              <button class="ecBtn primary" data-pc-action="admin_code_create">Создать код</button>
            </div>
          </div>
        </div>

        <div class="ecCard">
          <h4>Паки наград</h4>
          <div style="display:flex;justify-content:flex-end;margin-top:6px">
            <button class="ecBtn" data-pc-action="admin_pack_create">Создать новый пак</button>
          </div>
          <div style="margin-top:10px">'.$packs_html.'</div>
        </div>
      </div>

      <div class="ecCard" style="margin-top:10px">
        <h4>Коды (последние 200 из файла)</h4>
        <div class="ecSmall">Показывается состав наград через привязанный пак.</div>
        <div style="overflow:auto;margin-top:8px">
          <table class="ecTable">
            <thead><tr>
              <th>Код</th><th>Пак</th><th>Uses</th><th>Active</th><th>Public</th><th>User</th><th>До</th><th>Награды</th><th></th>
            </tr></thead>
            <tbody>'.$codes_rows.'</tbody>
          </table>
        </div>
      </div>
    ';
}

$html = '
<div class="ecShell">
  <div class="ecHead">
    <div>
      <div class="ecTitle">Промокоды</div>
      <div class="ecSub">Активируйте код или посмотрите доступные промокоды</div>
    </div>
    <button class="ecClose" data-pc-close>×</button>
  </div>

  <div class="pcTabs ecTabs">
    <button class="pcTab ecTab isActive" data-tab="redeem">Активировать</button>
    <button class="pcTab ecTab" data-tab="available">Доступные</button>
    '.($is_admin ? '<button class="pcTab ecTab" data-tab="admin">Админ</button>' : '').'
  </div>

  <div class="pcPanel ecPanel isActive" data-panel="redeem">
    <div class="ecCard">
      <div class="ecSmall">Введите промокод (без пробелов)</div>
      <div style="margin-top:8px">
        <input class="ecInp" data-pc-input="code" placeholder="CODE-XXXXXXXXXXXX" autocomplete="off" autocapitalize="characters">
      </div>
      <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
        <button class="ecBtn primary" data-pc-action="redeem">Активировать</button>
        <span class="ecSmall">После активации награды сразу поступят на аккаунт.</span>
      </div>
    </div>
  </div>

  <div class="pcPanel ecPanel" data-panel="available">
    '.$public_cards.'
  </div>

  '.($is_admin ? '<div class="pcPanel ecPanel" data-panel="admin">'.$admin_html.'</div>' : '').'
</div>';

pc_json(['html'=>$html]);

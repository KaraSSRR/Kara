<?php
/* ===========================
   /do/chat.php — backend чата
   =========================== */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
if (!file_exists($patch_global)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 1, 'text' => 'The problem with the connection files.']);
    exit;
}
require_once $patch_global;

/* ---------------- helpers ---------------- */

function jenc($v){ return json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); }
function jdec($s){
    if (is_array($s)) return $s;
    if ($s === '' || $s === null) return [];
    $x = json_decode($s, true);
    return is_array($x) ? $x : [];
}

/** Канал вида idMIN_idMAX */
function pm_channel_from_pair(int $a, int $b): string {
    if ($a > $b) { $t=$a; $a=$b; $b=$t; }
    return 'id'.$a.'_'.$b;
}

/** Разбор канала "idMIN_idMAX" → [min,max] */
function pm_pair_from_channel(string $channel): ?array {
    if (preg_match('/^id(\d+)_(\d+)$/', $channel, $m)) {
        $x=(int)$m[1]; $y=(int)$m[2];
        if ($x && $y) return [$x,$y];
    }
    return null;
}

/** HH:MM из unix */
function fmt_hm(int $ts): string {
    if ($ts > 10000000000) $ts = (int)floor($ts/1000);
    return $ts>0 ? date('H:i', $ts) : '';
}

/** Восстановление метки времени.
 *  Во многих схемах `lifetime` — это время удаления (TTL=30д).
 *  Тогда created ≈ lifetime - 30 суток. При необходимости поменяйте 2592000.
 */
function recover_msg_ts(array $row, array $info): int {
    if (!empty($info['msg_ts'])) {
        $ts = (int)$info['msg_ts'];
        return ($ts > 10000000000) ? (int)floor($ts/1000) : $ts;
    }
    if (!empty($row['lifetime'])) {
        $approx = (int)$row['lifetime'] - 2592000; // 30 * 86400
        if ($approx > 0) return $approx;
    }
    return time();
}

/** Нормализация chat_new -> payload, максимально сохраняем исходный info */
function normalize_row_payload(array $row): array {
    // НЕ теряем всё, что уже есть в info
    $info = jdec($row['info'] ?? []);

    // Базовые поля
    if (!isset($info['msg_type']))  $info['msg_type']  = (int)($row['type'] ?? 0);
    if (!isset($info['user_id']))   $info['user_id']   = (string)($row['user']   ?? '');
    if (!isset($info['userto_id'])) $info['userto_id'] = (string)($row['touser'] ?? '');

    // Канал
    $u1 = (int)($row['user'] ?? 0);
    $u2 = (int)($row['touser'] ?? 0);
    if (empty($info['private_channel_id'])) {
        $info['private_channel_id'] = pm_channel_from_pair($u1,$u2);
    }

    // Фолбэки для текста: text/msg/message → user_msg
    if (!isset($info['user_msg'])) {
        if (isset($info['text']))     $info['user_msg'] = $info['text'];
        elseif (isset($info['msg']))  $info['user_msg'] = $info['msg'];
        elseif (isset($info['message'])) $info['user_msg'] = $info['message'];
        else $info['user_msg'] = '';
    }

    // Время
    $ts = recover_msg_ts($row, $info);
    if (empty($info['msg_ts']))   $info['msg_ts']   = $ts;
    if (empty($info['msg_time'])) $info['msg_time'] = fmt_hm($ts);

    // Безопасные дефолты для отсутствующих визуальных полей
    if (!isset($info['user_login']))      $info['user_login'] = $info['user_login'] ?? '';
    if (!isset($info['userto_login']))    $info['userto_login'] = $info['userto_login'] ?? '';
    if (!isset($info['user_group']))      $info['user_group'] = $info['user_group'] ?? 0;
    if (!isset($info['userto_group']))    $info['userto_group'] = $info['userto_group'] ?? 0;
    if (!isset($info['user_sex']))        $info['user_sex'] = $info['user_sex'] ?? 0;
    if (!isset($info['user_msg_color']))  $info['user_msg_color'] = $info['user_msg_color'] ?? 0;

    return $info;
}


/** Выборка истории ЛС строго между двумя id с пагинацией по id вниз (DESC) */
function fetch_pm_history(mysqli $db, int $idA, int $idB, int $beforeId, int $limit): array {
    $limit = max(1, min($limit, 1000));

    // Условие именно для пары (а не «любой из IN»), с кавычками у `user`
    $condPair = "((`user`={$idA} AND `touser`={$idB}) OR (`user`={$idB} AND `touser`={$idA}))";

    $where = "`type`=1 AND {$condPair}";
    if ($beforeId > 0) $where .= " AND `id` < {$beforeId}";

    $sql = "SELECT `id`,`type`,`user`,`touser`,`location`,`clan`,`info`,`lifetime`,`img`,`img_to`
            FROM `chat_new`
            WHERE {$where}
            ORDER BY `id` DESC
            LIMIT {$limit}";

    $res = $db->query($sql);
    $rowsDesc = [];
    $minId = 0;

    if ($res && $res->num_rows) {
        while ($r = $res->fetch_assoc()) {
            $rowsDesc[] = $r;
            $rid = (int)$r['id'];
            if ($minId === 0 || $rid < $minId) $minId = $rid;
        }
    }

    // Отдаём по возрастанию id
    $rowsAsc = array_reverse($rowsDesc);

    $history = [];
    foreach ($rowsAsc as $row) {
        $mid = (int)$row['id'];
        $history[$mid] = normalize_row_payload($row);
    }

    // Есть ли ещё более старые
    $hasMore = false; $nextBefore = 0;
    if ($minId > 0) {
        $sqlMore = "SELECT 1 FROM `chat_new` WHERE {$where} AND `id` < {$minId} LIMIT 1";
        $rMore = $db->query($sqlMore);
        $hasMore = ($rMore && $rMore->num_rows > 0);
        $nextBefore = $minId;
    }

    return [
        'history'        => $history,
        'has_more'       => $hasMore ? 1 : 0,
        'next_before_id' => $nextBefore
    ];
}

/* --------------- router --------------- */

$chatAction = $_POST['chat'] ?? '';


/* ===== Profanity dictionary =====
   Справочник хранится в /inc/data/profanity_dict.json и масштабируется без изменения фронта.
   Клиент присылает ver + ua (updated_at). Если совпадает — changed=0.
   Если у клиента словарь пустой — можно отправить ver=0 (клиентская миграция).
*/
function load_profanity_dict() {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/inc/data/profanity_dict.json';
    $fallback = [
        'version' => 1,
        'updated_at' => time(),
        'roots' => ['хуй','пизд','еб','бля','сука','пидор','fuck','shit'],
        'regex' => [],
        'whitelist' => []
    ];

    // APCu кеш на 30 сек
    if (function_exists('apcu_fetch')) {
        $k = 'chat_profanity_dict_cache';
        $cached = apcu_fetch($k, $ok);
        if ($ok && is_array($cached)) return $cached;
    }

    $dict = $fallback;
    if (file_exists($path)) {
        $raw = @file_get_contents($path);
        if ($raw !== false) {
            $tmp = @json_decode($raw, true);
            if (is_array($tmp)) {
                // нормализуем поля
                if (!isset($tmp['version'])) $tmp['version'] = 1;
                if (!isset($tmp['updated_at'])) $tmp['updated_at'] = filemtime($path);
                if (!isset($tmp['roots']) || !is_array($tmp['roots'])) $tmp['roots'] = [];
                if (!isset($tmp['regex']) || !is_array($tmp['regex'])) $tmp['regex'] = [];
                if (!isset($tmp['whitelist']) || !is_array($tmp['whitelist'])) $tmp['whitelist'] = [];
                $dict = $tmp;
            }
        }
    }

    if (function_exists('apcu_store')) {
        apcu_store('chat_profanity_dict_cache', $dict, 30);
    }
    return $dict;
}



/* ===== profanity_dict endpoint ===== */
if ($chatAction === 'profanity_dict') {
    header('Content-Type: application/json; charset=utf-8');

    $ver = isset($_POST['ver']) ? (int)$_POST['ver'] : 0;
    $ua  = isset($_POST['ua']) ? (int)$_POST['ua'] : 0;

    $dict = load_profanity_dict();
    $dver = isset($dict['version']) ? (int)$dict['version'] : 0;
    $dua  = isset($dict['updated_at']) ? (int)$dict['updated_at'] : 0;

    if ($ver > 0 && $dver > 0 && $ver === $dver && $ua > 0 && $dua > 0 && $ua === $dua) {
        echo jenc(['error'=>0,'changed'=>0,'version'=>$dver,'updated_at'=>$dua]);
        exit;
    }

    echo jenc(['error'=>0,'changed'=>1,'version'=>$dver,'updated_at'=>$dua,'dict'=>$dict]);
    exit;
}


/* ===== История ЛС ===== */
if ($chatAction === 'pm_history' && !empty($_POST['channel'])) {
    header('Content-Type: application/json; charset=utf-8');

    $pair = pm_pair_from_channel($_POST['channel']);
    if (!$pair) {
        echo jenc(['history'=>[], 'has_more'=>0, 'next_before_id'=>0]);
        exit;
    }
    [$id1,$id2] = $pair;

    $beforeId = isset($_POST['before_id']) ? (int)$_POST['before_id'] : 0;
    $limitReq = isset($_POST['limit']) ? (int)$_POST['limit'] : 300;

    $out = fetch_pm_history($mysqli, $id1, $id2, $beforeId, $limitReq);

    // На всякий случай проставим правильный канал всем сообщениям
    $chan = pm_channel_from_pair($id1,$id2);
    foreach ($out['history'] as &$m) {
        if (empty($m['private_channel_id'])) $m['private_channel_id'] = $chan;
        if (empty($m['msg_type'])) $m['msg_type'] = 1;
    }
    unset($m);

    echo jenc($out);
    exit;
}

/* ===== Главный чат (онлайн) ===== */
if ($chatAction !== '') {
    $response = [];
    // Ваш класс, как и раньше
    if (class_exists('GameChat')) {
        $chet = new GameChat($mysqli, $_POST, $response);
    } else {
        $response = ['error'=>1,'text'=>'GameChat class is missing.'];
    }

    // Дополняем входящие сообщения, чтобы фронту не пришлось догадываться
    if (!empty($response['infoChat']) && is_array($response['infoChat'])) {
        foreach ($response['infoChat'] as $k => &$msg) {
            // если пришло строкой JSON — декодируем
            if (is_string($msg)) $msg = jdec($msg);

            if (isset($msg['msg_type']) && (int)$msg['msg_type'] === 1) {
                // id берём из стандартных полей, если что — из альтернативных
                $uid = isset($msg['user_id'])   ? (int)$msg['user_id']   : (isset($msg['user'])   ? (int)$msg['user']   : 0);
                $tid = isset($msg['userto_id']) ? (int)$msg['userto_id'] : (isset($msg['touser']) ? (int)$msg['touser'] : 0);
                if ($uid && $tid && empty($msg['private_channel_id'])) {
                    $msg['private_channel_id'] = pm_channel_from_pair($uid,$tid);
                }
                // время
                if (empty($msg['msg_ts']) || empty($msg['msg_time'])) {
                    $rowStub = [
                        'user'     => $uid,
                        'touser'   => $tid,
                        'lifetime' => isset($msg['lifetime']) ? (int)$msg['lifetime'] : 0
                    ];
                    $ts = recover_msg_ts($rowStub, $msg);
                    if (empty($msg['msg_ts']))   $msg['msg_ts']   = $ts;
                    if (empty($msg['msg_time'])) $msg['msg_time'] = fmt_hm($ts);
                }
            }
        }
        unset($msg);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo jenc($response);
    exit;
}

/* ===== default ===== */
header('Content-Type: application/json; charset=utf-8');
echo jenc(['error'=>1,'text'=>'Invalid request']);
exit;

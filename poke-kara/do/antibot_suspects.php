<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sendJson(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    exit;
}

function clearInt($v): int {
    if ($v === null || $v === '') return 0;
    return (int)$v;
}
function clearStr($v): string {
    if ($v === null) return '';
    $v = trim((string)$v);
    $v = stripslashes($v);
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$patch_project = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;

$configPaths = [
    $patch_project . '/inc/conf/const.php',
    $patch_project . '/config/const.php',
    dirname(__DIR__) . '/inc/conf/const.php'
];
foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        break;
    }
}

// fallback (если const.php не найден)
if (!defined('MYSQL_HOST')) {
    define('MYSQL_HOST', 'localhost');
    define('MYSQL_LOGIN', 'root');
    define('MYSQL_PASSWORD', '');
    define('MYSQL_DB', '');
}

$mysqli = @new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_PASSWORD, MYSQL_DB);
if ($mysqli->connect_errno) {
    sendJson(['error' => 'db_connection_failed'], 500);
}
$mysqli->set_charset('utf8mb4');

// auth
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'method_not_allowed'], 405);
}
if (!isset($_SESSION['id']) || !isset($_SESSION['login'])) {
    sendJson(['error' => 'unauthorized'], 401);
}

$userId = clearInt($_SESSION['id']);
if ($userId <= 0) sendJson(['error' => 'invalid_user_id'], 400);

// check admin (user_group = 1)
$userGroup = 0;
if ($stmt = $mysqli->prepare("SELECT user_group FROM users WHERE id=? LIMIT 1")) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($userGroup);
    $stmt->fetch();
    $stmt->close();
}
if ((int)$userGroup !== 1) {
    sendJson(['error' => 'forbidden'], 403);
}

// input
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) $data = $_POST;

$action = clearStr($data['action'] ?? 'list');

$now = time();

if ($action === 'list') {
    $hours = max(1, min(24*30, clearInt($data['hours'] ?? 24)));
    $since = $now - $hours * 3600;

    $confidence = strtoupper(clearStr($data['confidence'] ?? 'ALL'));
    $status = strtoupper(clearStr($data['status'] ?? 'ALL'));
    $q = trim((string)($data['q'] ?? ''));

    $where = "c.last_seen_ts >= ?";
    $params = [$since];
    $types = "i";

    if (in_array($confidence, ['LOW','MED','HIGH'], true)) {
        $where .= " AND c.confidence = ?";
        $params[] = $confidence;
        $types .= "s";
    }
    if (in_array($status, ['NEW','ACK','CLOSED'], true)) {
        $where .= " AND c.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if ($q !== '') {
        if (ctype_digit($q)) {
            $where .= " AND c.user_id = ?";
            $params[] = (int)$q;
            $types .= "i";
        } else {
            $where .= " AND c.login LIKE ?";
            $params[] = '%' . $q . '%';
            $types .= "s";
        }
    }

    $sql = "
        SELECT 
            c.user_id, c.login, c.confidence, c.severity, c.status,
            c.first_seen_ts, c.last_seen_ts, c.reasons_json, c.evidence_json, c.snapshot_id,
            s.bucket_start_ts, s.bucket_len_sec, s.reqs, s.trap_total,
            s.suspicious_avg, s.suspicious_max, s.webdriver_flag, s.headless_flag,
            s.session_dur_max, s.last_url, s.ip, s.ua_hash, UNIX_TIMESTAMP(s.updated_at) AS snap_updated_ts
        FROM antibot_case c
        LEFT JOIN antibot_snapshot s ON s.id = c.snapshot_id
        WHERE $where
        ORDER BY c.severity DESC, c.last_seen_ts DESC
        LIMIT 200
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) sendJson(['error'=>'sql_prepare_failed'], 500);

    // bind dynamic
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $row['reasons'] = json_decode($row['reasons_json'] ?? '[]', true) ?: [];
        $row['evidence'] = json_decode($row['evidence_json'] ?? '{}', true) ?: [];
        unset($row['reasons_json'], $row['evidence_json']);
        $items[] = $row;
    }
    $stmt->close();

    sendJson(['ok'=>1, 'items'=>$items, 'since_ts'=>$since, 'now_ts'=>$now]);
}

if ($action === 'details') {
    $targetId = clearInt($data['user_id'] ?? 0);
    if ($targetId <= 0) sendJson(['error'=>'invalid_user_id'], 400);

    $case = null;
    if ($stmt = $mysqli->prepare("SELECT * FROM antibot_case WHERE user_id=? LIMIT 1")) {
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $res = $stmt->get_result();
        $case = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
    if (!$case) sendJson(['error'=>'not_found'], 404);

    $case['reasons'] = json_decode($case['reasons_json'] ?? '[]', true) ?: [];
    $case['evidence'] = json_decode($case['evidence_json'] ?? '{}', true) ?: [];
    unset($case['reasons_json'], $case['evidence_json']);

    $hours = max(1, min(24*30, clearInt($data['hours'] ?? 24)));
    $since = $now - $hours * 3600;

    $snaps = [];
    if ($stmt = $mysqli->prepare("SELECT *, UNIX_TIMESTAMP(updated_at) AS updated_ts FROM antibot_snapshot WHERE user_id=? AND bucket_start_ts >= ? ORDER BY bucket_start_ts DESC LIMIT 80")) {
        $stmt->bind_param('ii', $targetId, $since);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $snaps[] = $row;
        $stmt->close();
    }

    sendJson(['ok'=>1, 'case'=>$case, 'snapshots'=>$snaps, 'since_ts'=>$since, 'now_ts'=>$now]);
}

if ($action === 'set_status') {
    $targetId = clearInt($data['user_id'] ?? 0);
    $newStatus = strtoupper(clearStr($data['status'] ?? ''));
    if ($targetId <= 0) sendJson(['error'=>'invalid_user_id'], 400);
    if (!in_array($newStatus, ['NEW','ACK','CLOSED'], true)) sendJson(['error'=>'invalid_status'], 400);

    if ($stmt = $mysqli->prepare("UPDATE antibot_case SET status=? WHERE user_id=? LIMIT 1")) {
        $stmt->bind_param('si', $newStatus, $targetId);
        $stmt->execute();
        $stmt->close();
    }

    sendJson(['ok'=>1]);
}

sendJson(['error'=>'unknown_action'], 400);

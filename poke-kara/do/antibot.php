<?php
/**
 * Антибот-система с исправлением уникального ключа
 * Дата: 2025-09-05 17:06:02 UTC
 * Пользователь: KaraRRS
 */

declare(strict_types=1);

// Отключаем весь вывод до JSON-ответа
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Расширенное логирование
ini_set('log_errors', '1');
ini_set('error_log', '/tmp/antibot_debug.log');

function criticalLog($message, $sendAlert = false) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] CRITICAL AntiBot: $message\n";
    error_log($logMessage, 3, '/tmp/antibot_debug.log');
    error_log("AntiBot CRITICAL: $message");
    
    if ($sendAlert) {
        sendAdminAlert($message);
    }
}

function sendAdminAlert($message) {
    global $mysqli;
    
    try {
        if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
            $alertText = "🚨 AntiBot System Alert: $message";
            $dateStr = date('j') . ' ' . ['','Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря'][date('n')] . ' ' . date('Y') . 'г. в ' . date('H:i');
            
            static $notifOk = null;
            if ($notifOk === null) {
                $checkTable = $mysqli->query("SHOW TABLES LIKE 'notification'");
                $notifOk = ($checkTable && $checkTable->num_rows > 0);
            }
            if ($notifOk) {
                $sql = "INSERT INTO `notification` (`user`, `text`, `img`, `checked`, `date`, `created_at`)
                        SELECT `id`, ?, '/img/world/items/little/151.png', 0, ?, NOW()
                        FROM `users` WHERE `user_group` = 1 LIMIT 5";
                
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param('ss', $alertText, $dateStr);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        
        // Файл критичных ошибок
        $criticalFile = '/tmp/antibot_critical.log';
        $criticalMessage = "[" . date('Y-m-d H:i:s') . "] $message\n";
        file_put_contents($criticalFile, $criticalMessage, FILE_APPEND | LOCK_EX);
        
    } catch (Exception $e) {
        error_log("Failed to send admin alert: " . $e->getMessage());
    }
}

function debugLog($message) {
    // Логи включаются только при DEBUG=true (чтобы не грузить диск/CPU на онлайне).
    $debug = false;
    if (defined('AB_CONFIG') && isset(AB_CONFIG['DEBUG'])) {
        $debug = (bool)AB_CONFIG['DEBUG'];
    }
    if (!$debug) return;

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] AntiBot: $message
";
    error_log($logMessage, 3, '/tmp/antibot_debug.log');
}

// Обработчики ошибок
set_error_handler(function($severity, $message, $file, $line) {
    criticalLog("PHP Error: $message in $file:$line", true);
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'php_error', 'debug' => $message], JSON_UNESCAPED_UNICODE);
    exit;
});

set_exception_handler(function($exception) {
    criticalLog("Exception: " . $exception->getMessage(), true);
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'exception', 'debug' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sendJsonResponse(array $data, int $httpCode = 200): void {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    if ($json === false) {
        $json = '{"error":"json_encoding_failed"}';
    }
    
    echo $json;
    exit;
}

if (!function_exists('clearInt')) {
    function clearInt($value) {
        if ($value === null || $value === '') return 0;
        return abs((int)$value);
    }
}

if (!function_exists('clearStr')) {
    function clearStr($text) {
        if ($text === null) return '';
        $text = trim((string)$text);
        $text = stripslashes($text);
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

$patch_project = $_SERVER['DOCUMENT_ROOT'];

$configFound = false;
$configPaths = [
    $patch_project . '/inc/conf/const.php',
    $patch_project . '/config/const.php',
    dirname(__DIR__) . '/inc/conf/const.php'
];

foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configFound = true;
        debugLog("Config loaded from: $configPath");
        break;
    }
}

if (!defined('MYSQL_HOST')) {
    define('MYSQL_HOST', 'localhost');
    define('MYSQL_LOGIN', 'karasrgd_1');
    define('MYSQL_PASSWORD', 'KillerUser_31');
    define('MYSQL_DB', 'karasrgd_1');
    debugLog("Using default DB config");
}

try {
    $mysqli = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_PASSWORD, MYSQL_DB);
    
    if ($mysqli->connect_errno) {
        criticalLog("DB connection failed: " . $mysqli->connect_error, true);
        sendJsonResponse(['error' => 'db_connection_failed'], 500);
    }
    
    $mysqli->set_charset("utf8mb4");
    debugLog("DB connected successfully");
    
} catch (Exception $e) {
    criticalLog("DB exception: " . $e->getMessage(), true);
    sendJsonResponse(['error' => 'db_exception'], 500);
}

date_default_timezone_set("Europe/Moscow");

const AB_CONFIG = [
    'DEBUG' => false,
    'JSON_MAX_BYTES' => 32768,
    'FORCE_SAVE' => false,
    'ALERT_ADMIN_ON_SAVE_FAIL' => true,
    'RISK_THRESHOLDS' => [
        'LOW' => 25,
        'MEDIUM' => 50,
        'HIGH' => 80,
        'CRITICAL' => 95
    ],
    'RATE_LIMITS' => [
        'REQUESTS_PER_MINUTE' => 30,
        'CLEANUP_PROBABILITY' => 0.01
    ]
];

// ==================== ОСНОВНОЙ КЛАСС
// ==================== V2: АГРЕГАЦИЯ + SNAPSHOT/CASE (минимум записей) ====================
final class AntiBotV2Ingestor {
    private mysqli $db;
    private int $userId;
    private string $login;
    private string $ip;
    private string $ua;

    // Длина окна агрегации (сек). 900 = 15 минут. Можно увеличить до 1800/3600 для ещё меньшего числа записей.
    private const BUCKET_LEN = 900;

    private const SCORE_MED  = 50;
    private const SCORE_HIGH = 80;
    private const SCORE_CRIT = 95;

    // Антиспам для уведомлений админам по одному пользователю
    private const ADMIN_ALERT_COOLDOWN = 600; // 10 минут

    public function __construct(mysqli $db, int $userId, string $login) {
        $this->db = $db;
        $this->userId = $userId;
        $this->login = $login;
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function handle(array $data): array {
        $now = time();
        $bucketStart = intdiv($now, self::BUCKET_LEN) * self::BUCKET_LEN;

        // 1) Агрегат в сессии
        $agg = $_SESSION['ab_v2_agg'] ?? null;

        // 2) Если окно сменилось — один раз сбрасываем snapshot в БД
        if (is_array($agg) && (int)($agg['bucket_start_ts'] ?? 0) !== $bucketStart) {
            $this->flushSnapshot($agg);
            $agg = null;
        }

        // 3) Инициализация окна
        if (!is_array($agg)) {
            $agg = [
                'bucket_start_ts' => $bucketStart,
                'bucket_len_sec'  => self::BUCKET_LEN,
                'reqs' => 0,
                'mouse_total' => 0,
                'click_total' => 0,
                'key_total' => 0,
                'trap_total' => 0,
                'score_sum' => 0,
                'score_n' => 0,
                'score_max' => 0,
                'webdriver_flag' => 0,
                'headless_flag' => 0,
                'session_dur_max' => 0,
                'last_url' => '',
                'last_ts' => $now
            ];
        }

        // 4) Извлекаем “лёгкие” признаки
        $mouseCount = 0;
        if (isset($data['mouseN'])) $mouseCount = clearInt($data['mouseN']);
        elseif (isset($data['mouse']) && is_array($data['mouse'])) $mouseCount = count($data['mouse']);

        $clickCount = 0;
        if (isset($data['clickN'])) $clickCount = clearInt($data['clickN']);
        elseif (isset($data['click']) && is_array($data['click'])) $clickCount = count($data['click']);

        $keyCount = 0;
        if (isset($data['keyN'])) $keyCount = clearInt($data['keyN']);
        elseif (isset($data['key']) && is_array($data['key'])) $keyCount = count($data['key']);

        $trap = clearInt($data['trap'] ?? 0);
        $score = clearInt($data['suspiciousScore'] ?? 0);
        $sessDur = clearInt($data['sessionDuration'] ?? 0);

        $url = '';
        if (isset($data['url'])) {
            $url = substr(clearStr((string)$data['url']), 0, 200);
        }

        // Жёсткие флаги
        $webdriver = 0;
        $headless = 0;
        if (isset($data['navigator']) && is_array($data['navigator'])) {
            if (!empty($data['navigator']['webdriver']) || !empty($data['navigator']['selenium'])) $webdriver = 1;
            if (!empty($data['navigator']['headless'])) $headless = 1;
        }

        // 5) Обновляем агрегат
        $agg['reqs']++;
        $agg['mouse_total'] += $mouseCount;
        $agg['click_total'] += $clickCount;
        $agg['key_total'] += $keyCount;

        $agg['trap_total'] += max(0, $trap);
        $agg['score_sum'] += max(0, $score);
        $agg['score_n'] += 1;
        $agg['score_max'] = max((int)$agg['score_max'], $score);

        $agg['webdriver_flag'] = ($agg['webdriver_flag'] || $webdriver) ? 1 : 0;
        $agg['headless_flag'] = ($agg['headless_flag'] || $headless) ? 1 : 0;

        $agg['session_dur_max'] = max((int)$agg['session_dur_max'], $sessDur);
        if ($url) $agg['last_url'] = $url;
        $agg['last_ts'] = $now;

        $avg = ($agg['score_n'] > 0) ? (int)round($agg['score_sum'] / $agg['score_n']) : 0;

        $reasons = [];
        if ($agg['webdriver_flag']) $reasons[] = 'webdriver_detected';
        if ($agg['trap_total'] > 0) $reasons[] = 'honeypot_interaction';
        if ($score >= self::SCORE_CRIT || $agg['score_max'] >= self::SCORE_CRIT) $reasons[] = 'high_client_risk_score';

        $confidence = $this->confidenceFrom($avg, (int)$agg['score_max'], $reasons);
        $severity = $this->severityFrom($avg, (int)$agg['score_max'], $reasons, (int)$agg['trap_total']);

        // 6) Решаем, нужно ли писать в БД прямо сейчас
        $needFlush = false;

        // Жёсткие причины — пишем сразу.
        if (!empty($reasons)) $needFlush = true;

        // Если вырос риск до MED/HIGH и уже есть хотя бы 5 минут данных окна — пишем.
        if (!$needFlush && $confidence !== 'LOW' && ($now - $bucketStart) >= 300) $needFlush = true;

        // Пишем агрегат в сессию и отпускаем блокировку как можно раньше.
        $_SESSION['ab_v2_agg'] = $agg;
        session_write_close();

        $snapshotId = null;
        if ($needFlush) {
            $snapshotId = $this->flushSnapshot($agg);
            if ($confidence !== 'LOW') {
                $this->upsertCase(
                    $snapshotId,
                    $confidence,
                    $severity,
                    $reasons,
                    [
                        'avg' => $avg,
                        'max' => (int)$agg['score_max'],
                        'trap_total' => (int)$agg['trap_total'],
                        'reqs' => (int)$agg['reqs'],
                        'url' => $agg['last_url'],
                        'ip' => $this->ip,
                        'ua_hash' => sha1($this->ua),
                        'bucket_start_ts' => $bucketStart
                    ]
                );
            }
        }

        return [
            'ok' => 1,
            'ts' => $now,
            'bucket_start_ts' => $bucketStart,
            'avg' => $avg,
            'max' => (int)$agg['score_max'],
            'confidence' => $confidence,
            'flushed' => $needFlush ? 1 : 0,
            'snapshot_id' => $snapshotId
        ];
    }

    private function confidenceFrom(int $avg, int $max, array $reasons): string {
        if (in_array('webdriver_detected', $reasons, true) || in_array('honeypot_interaction', $reasons, true)) {
            return 'HIGH';
        }
        if ($max >= self::SCORE_HIGH || $avg >= self::SCORE_HIGH) return 'MED';
        if ($max >= self::SCORE_MED || $avg >= self::SCORE_MED) return 'LOW';
        return 'LOW';
    }

    private function severityFrom(int $avg, int $max, array $reasons, int $trapTotal): int {
        $sev = max($avg, $max);
        if (in_array('webdriver_detected', $reasons, true)) $sev += 25;
        if ($trapTotal > 0) $sev += 20;
        if ($sev > 100) $sev = 100;
        return $sev;
    }

    private function flushSnapshot(array $agg): ?int {
        $avg = ($agg['score_n'] > 0) ? (int)round($agg['score_sum'] / $agg['score_n']) : 0;

        $sql = "
            INSERT INTO antibot_snapshot
              (user_id, bucket_start_ts, bucket_len_sec, reqs,
               mouse_total, click_total, key_total,
               trap_total, suspicious_avg, suspicious_max,
               webdriver_flag, headless_flag,
               session_dur_max, last_url, ip, ua_hash)
            VALUES (?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              id = LAST_INSERT_ID(id),
              reqs = VALUES(reqs),
              mouse_total = VALUES(mouse_total),
              click_total = VALUES(click_total),
              key_total = VALUES(key_total),
              trap_total = VALUES(trap_total),
              suspicious_avg = VALUES(suspicious_avg),
              suspicious_max = GREATEST(suspicious_max, VALUES(suspicious_max)),
              webdriver_flag = GREATEST(webdriver_flag, VALUES(webdriver_flag)),
              headless_flag = GREATEST(headless_flag, VALUES(headless_flag)),
              session_dur_max = GREATEST(session_dur_max, VALUES(session_dur_max)),
              last_url = VALUES(last_url),
              ip = VALUES(ip),
              ua_hash = VALUES(ua_hash)
        ";

        $uaHash = sha1($this->ua);

        if ($stmt = $this->db->prepare($sql)) {
            $types = 'iiiiiiiiiiiiisss'; // 13 ints + 3 strings
            $stmt->bind_param(
                $types,
                $this->userId,
                $agg['bucket_start_ts'],
                $agg['bucket_len_sec'],
                $agg['reqs'],

                $agg['mouse_total'],
                $agg['click_total'],
                $agg['key_total'],

                $agg['trap_total'],
                $avg,
                $agg['score_max'],

                $agg['webdriver_flag'],
                $agg['headless_flag'],

                $agg['session_dur_max'],
                $agg['last_url'],
                $this->ip,
                $uaHash
            );

            if ($stmt->execute()) {
                $id = (int)$this->db->insert_id; // работает и на UPDATE из-за LAST_INSERT_ID(id)
                $stmt->close();
                return $id;
            }
            $stmt->close();
        }
        return null;
    }

    private function upsertCase(?int $snapshotId, string $confidence, int $severity, array $reasons, array $evidence): void {
        $now = time();

        // 1) Читаем текущий кейс (для антиспама алертов)
        $cur = null;
        if ($stmt = $this->db->prepare("SELECT confidence, severity, status, last_alert_ts FROM antibot_case WHERE user_id=? LIMIT 1")) {
            $stmt->bind_param('i', $this->userId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                $cur = $res ? $res->fetch_assoc() : null;
            }
            $stmt->close();
        }

        $reasonsJson = json_encode(array_values(array_unique($reasons)), JSON_UNESCAPED_UNICODE);
        $evidenceJson = json_encode($evidence, JSON_UNESCAPED_UNICODE);

        // 2) Upsert (1 строка на юзера)
        $sql = "
            INSERT INTO antibot_case
              (user_id, login, first_seen_ts, last_seen_ts, confidence, severity, reasons_json, evidence_json, status, last_alert_ts, snapshot_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'NEW', 0, ?)
            ON DUPLICATE KEY UPDATE
              login = VALUES(login),
              last_seen_ts = VALUES(last_seen_ts),
              confidence = VALUES(confidence),
              severity = GREATEST(severity, VALUES(severity)),
              reasons_json = VALUES(reasons_json),
              evidence_json = VALUES(evidence_json),
              snapshot_id = VALUES(snapshot_id),
              status = IF(status='CLOSED','NEW',status)
        ";

        if ($stmt = $this->db->prepare($sql)) {
            $types = 'isiisissi';
            $stmt->bind_param(
                $types,
                $this->userId,
                $this->login,
                $now,
                $now,
                $confidence,
                $severity,
                $reasonsJson,
                $evidenceJson,
                $snapshotId
            );
            $stmt->execute();
            $stmt->close();
        }

        // 3) Уведомление админам: только HIGH + cooldown
        $shouldAlert = false;
        if ($confidence === 'HIGH') {
            $lastAlertTs = $cur ? (int)($cur['last_alert_ts'] ?? 0) : 0;
            if (($now - $lastAlertTs) >= self::ADMIN_ALERT_COOLDOWN) {
                $shouldAlert = true;
            }
        }

        if ($shouldAlert) {
            if ($stmt = $this->db->prepare("UPDATE antibot_case SET last_alert_ts=? WHERE user_id=? LIMIT 1")) {
                $stmt->bind_param('ii', $now, $this->userId);
                $stmt->execute();
                $stmt->close();
            }

            $msg = "Подозрение на автоматизацию: {$this->login} (#{$this->userId}), confidence=HIGH, severity={$severity}, reasons=" . implode(',', $reasons);
            sendAdminAlert($msg);
        }
    }
}


// ==================== ТОЧКА ВХОДА ====================

debugLog("=== AntiBot request started ===");
debugLog("Request time: " . date('Y-m-d H:i:s'));
debugLog("User session ID: " . ($_SESSION['id'] ?? 'NONE'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'method_not_allowed'], 405);
}

if (!isset($_SESSION['id']) || !isset($_SESSION['login'])) {
    debugLog("User not authorized");
    sendJsonResponse(['error' => 'unauthorized'], 401);
}

$userId = clearInt($_SESSION['id']);
if ($userId <= 0) {
    debugLog("Invalid user ID: $userId");
    sendJsonResponse(['error' => 'invalid_user_id'], 400);
}

debugLog("Processing for user: $userId ({$_SESSION['login']})");

$rawInput = file_get_contents('php://input', false, null, 0, AB_CONFIG['JSON_MAX_BYTES']);
$data = json_decode($rawInput ?: '', true);

if (!is_array($data)) {
    $data = isset($_POST['data']) ? json_decode(clearStr($_POST['data']), true) : null;
}

if (!is_array($data)) {
    debugLog("Invalid JSON data received");
    sendJsonResponse(['error' => 'invalid_json'], 400);
}

debugLog("JSON data received with keys: " . implode(', ', array_keys($data)));

try {
    $ingestor = new AntiBotV2Ingestor($mysqli, $userId, (string)$_SESSION['login']);
    $result = $ingestor->handle($data);
    
    debugLog("=== AntiBot request completed ===");
    sendJsonResponse($result);
    
} catch (Exception $e) {
    criticalLog("Fatal exception: " . $e->getMessage(), true);
    sendJsonResponse(['error' => 'fatal_error'], 500);
}
?>
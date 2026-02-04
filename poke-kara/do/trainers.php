<?php
/* ===========================================================
 *  trainers.php — backend для Тренеркарты
 *  Полная версия: логи, безопасные запросы, учёт времени в игре
 *  и расчёт % побед. Ничего не урезано.
 * =========================================================== */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ---------- Debug ---------- */
define('DEBUG_FILE', $_SERVER['DOCUMENT_ROOT'].'/do/trainers_debug.log');
function debug_log($step, $data = null) {
    $str = "[".date("Y-m-d H:i:s")."] $step\n";
    if ($data !== null) $str .= print_r($data, true)."\n";
    @file_put_contents(DEBUG_FILE, $str, FILE_APPEND);
}

/* ---------- Конфиг и БД ---------- */
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project.'/inc/conf/global.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        debug_log('ERROR: Файл глобального конфига не найден', $patch_global);
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
    }
}
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

/* ---------- Утилиты ----------
   НЕ переопределяем clearStr() — во многих инсталлах она уже есть.
   Используем алиас tc_clear(), который делегирует в глобальную clearStr при наличии. */
function tc_clear($val) {
    if (function_exists('clearStr')) {
        return clearStr($val);
    }
    if ($val === null) return '';
    if (is_array($val)) $val = implode(',', $val);
    return trim(filter_var($val, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
}
function json_ok($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}
function json_error($msg, $code = 1, $extra = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['error'=>$code,'text'=>$msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- NewYear helpers (server-wide event / tree / admin) ---------- */
function ny_is_admin(mysqli $mysqli): bool {
    if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) return false;
    $uid = (int)$_SESSION['id'];
    $u = $mysqli->query("SELECT `user_group` FROM `users` WHERE `id`={$uid} LIMIT 1")->fetch_assoc();
    if(!$u) return false;
    return ((int)$u['user_group'] === 1);
}
function ny_call_safe($fn, ...$args) {
    if (function_exists($fn)) {
        return $fn(...$args);
    }
    return null;
}
function arr_get($arr, $keys, $default = null) {
    foreach ((array)$keys as $k) {
        if (isset($arr[$k]) && $arr[$k] !== '' && $arr[$k] !== null) return $arr[$k];
    }
    return $default;
}
function seconds_to_hms($secs) {
    $secs = max(0, (int)$secs);
    $h = floor($secs / 3600);
    $m = floor(($secs % 3600) / 60);
    $s = $secs % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
/**
 * Возвращает часы игры по различным возможным полям пользователя
 * @param array $u Данные пользователя
 * @return int Количество часов в игре
 */

/* ---------- NewYear Event Hub (always-visible button + archive) ---------- */
/**
 * Возвращает количество снежинок (item 1200) у пользователя
 */
function ny_evt_get_snow(int $userId): int {
    $db = ny_call_safe('newyear_db');
    if (!$db) return 0;
    $userId = (int)$userId;
    $res = $db->query("SELECT SUM(`count`) AS c FROM `items_users` WHERE `user`={$userId} AND `item_id`=1200");
    if(!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

/**
 * Возвращает описание последнего "окна" ивента (для архива), даже если ивент не активен.
 * Приоритет:
 *  1) active_override (start_ts/end_ts) — если когда-либо запускали админом.
 *  2) календарное окно 31.12–06.01 (последнее завершившееся/текущее).
 */
function ny_evt_last_window(): array {
    $sv = ny_call_safe('newyear_get_server');
    $now = (int)ny_call_safe('newyear_now_kiev');
    if(!$now) $now = time();

    // Если когда-то использовали active_override — показываем именно его диапазон.
    if($sv && isset($sv['start_ts'], $sv['end_ts']) && (int)($sv['active_override'] ?? 0) === 1){
        $st = (int)$sv['start_ts'];
        $en = (int)$sv['end_ts'];
        return ['mode'=>'override','start_ts'=>$st,'end_ts'=>$en];
    }

    // Календарное окно
    $y = (int)date('Y', $now);
    $start = strtotime($y.'-12-31 00:00:00');
    $end   = strtotime(($y+1).'-01-06 23:59:59');
    if($now < $start){
        $start = strtotime(($y-1).'-12-31 00:00:00');
        $end   = strtotime(($y).'-01-06 23:59:59');
    }
    return ['mode'=>'calendar','start_ts'=>$start,'end_ts'=>$end];
}

/**
 * Обмен снежинок на ивентовые предметы.
 * pack/kind:
 *  - gift     => item 1201
 *  - firework => item 1202
 */
function ny_evt_exchange(int $userId, string $pack): array {
    $userId = (int)$userId;
    $pack = trim($pack);

    $catalog = [
        'gift' => ['cost'=>60, 'item'=>1201, 'count'=>1, 'name'=>'Подарок на Новый год'],
        'firework' => ['cost'=>25, 'item'=>1202, 'count'=>1, 'name'=>'Фейерверк'],
    ];
    if(!isset($catalog[$pack])){
        return ['error'=>1,'text'=>'Неизвестный пакет обмена.'];
    }
    $c = $catalog[$pack];

    if(!ny_call_safe('item_isset', 1200, (int)$c['cost'], $userId)){
        return ['error'=>1,'text'=>'Недостаточно снежинок. Нужно: '.$c['cost']];
    }

    ny_call_safe('minus_item', 1200, (int)$c['cost'], $userId);
    ny_call_safe('itemAdd', (int)$c['item'], (int)$c['count'], $userId);

    return ['error'=>0,'text'=>'Обмен выполнен: '.$c['name'].' x'.$c['count']];
}

/**
 * Награда за тир (1..5). Храним в a_ivent_newyear_users.tier_claims (CSV).
 */
function ny_evt_claim_tier(int $userId, int $tier): array {
    $userId = (int)$userId;
    $tier = (int)$tier;
    if($tier < 1 || $tier > 5) return ['error'=>1,'text'=>'Некорректный тир.'];

    $db = ny_call_safe('newyear_db');
    if(!$db) return ['error'=>1,'text'=>'DB error'];

    ny_call_safe('newyear_ensure_tables');
    $sv = ny_call_safe('newyear_get_server');
    if(!$sv) return ['error'=>1,'text'=>'Не удалось получить прогресс сервера.'];

    $serverTier = (int)($sv['tier'] ?? 0);
    if($serverTier < $tier){
        return ['error'=>1,'text'=>'Этот тир ещё не открыт.'];
    }

    $u = $db->query("SELECT * FROM `a_ivent_newyear_users` WHERE `user`={$userId}")->fetch_assoc();
    if(!$u){
        // Создадим через стандартный статус (он сам инитит строку)
        ny_call_safe('newyear_tree_status', $userId);
        $u = $db->query("SELECT * FROM `a_ivent_newyear_users` WHERE `user`={$userId}")->fetch_assoc();
    }
    $claimedCsv = (string)($u['tier_claims'] ?? '');
    $claimed = [];
    foreach(array_filter(array_map('trim', explode(',', $claimedCsv))) as $t){
        $claimed[(int)$t] = true;
    }
    if(isset($claimed[$tier])){
        return ['error'=>1,'text'=>'Награда этого тира уже получена.'];
    }

    // Конфиг наград — можно тонко сбалансировать позже
    $rewards = [
        1 => [['item'=>1200,'count'=>30,'label'=>'Снежинки']],
        2 => [['item'=>1201,'count'=>1,'label'=>'Подарок']],
        3 => [['item'=>1202,'count'=>2,'label'=>'Фейерверк']],
        4 => [['item'=>1201,'count'=>2,'label'=>'Подарок']],
        5 => [['item'=>1201,'count'=>3,'label'=>'Подарок'], ['item'=>1202,'count'=>3,'label'=>'Фейерверк']],
    ];

    $parts = [];
    foreach($rewards[$tier] as $rw){
        ny_call_safe('itemAdd', (int)$rw['item'], (int)$rw['count'], $userId);
        $parts[] = $rw['label'].' x'.$rw['count'];
    }

    $claimed[$tier] = true;
    $newCsv = implode(',', array_keys($claimed));
    $newCsv = $db->real_escape_string($newCsv);
    $now = (int)ny_call_safe('newyear_now_kiev');
    if(!$now) $now = time();

    $db->query("UPDATE `a_ivent_newyear_users` SET `tier_claims`='{$newCsv}', `updated_at`={$now} WHERE `user`={$userId} LIMIT 1");

    return ['error'=>0,'text'=>'Награда получена: '.implode(', ', $parts)];
}

/**
 * Рендер HTML для модалки "Ивент-центр".
 * Показывается всегда: активный ивент или архив.
 */
function ny_evt_render_hub(int $userId, bool $isAdmin): string {
    $userId = (int)$userId;

    $status = ny_call_safe('newyear_tree_status', $userId);
    if(!$status) $status = ['active'=>0,'server_percent'=>0,'server_tier'=>0,'free_available'=>0,'server_progress'=>0,'server_target'=>0];

    $active = (int)($status['active'] ?? 0);
    $snow = ny_evt_get_snow($userId);

    $sv = ny_call_safe('newyear_get_server');
    $win = ny_evt_last_window();
    $st = (int)($win['start_ts'] ?? 0);
    $en = (int)($win['end_ts'] ?? 0);

    $fmt = function($ts){
        if(!$ts) return '—';
        return date('d.m.Y H:i', (int)$ts);
    };

    $pct = (float)($status['server_percent'] ?? 0);
    $tier = (int)($status['server_tier'] ?? 0);
    $progress = (int)($status['server_progress'] ?? 0);
    $target = (int)($status['server_target'] ?? 0);
    $free = (int)($status['free_available'] ?? 0);

    // Простая витрина ссылок (можно заменить на любые ваши файлы/страницы)
    $links = [
        ['title'=>'Арты ивента', 'url'=>'/img/event/', 'hint'=>'Папка с визуалами (если настроено на сервере)'],
        ['title'=>'Правила ивента', 'url'=>'/news', 'hint'=>'Пост/новость с описанием'],
        ['title'=>'Топ участников', 'url'=>'/rating', 'hint'=>'Рейтинг/лидерборды'],
    ];

    ob_start();
    ?>
    <div style="padding:16px 16px 14px;max-width:720px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:18px;font-weight:800;letter-spacing:.2px;">Ивент-центр</div>
                <div style="margin-top:2px;font-size:12px;opacity:.85;">
                    <?php if($active){ ?>
                        <span style="display:inline-flex;align-items:center;gap:6px;">
                            <span style="width:8px;height:8px;border-radius:99px;background:#22c55e;display:inline-block;"></span>
                            Активный ивент
                        </span>
                    <?php } else { ?>
                        <span style="display:inline-flex;align-items:center;gap:6px;">
                            <span style="width:8px;height:8px;border-radius:99px;background:#94a3b8;display:inline-block;"></span>
                            Сейчас нет активного ивента
                        </span>
                    <?php } ?>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px;opacity:.85;">Снежинки</div>
                <div style="font-size:18px;font-weight:800;"><?= (int)$snow ?></div>
            </div>
        </div>

        <div style="margin-top:12px;padding:12px;border-radius:14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:12px;opacity:.85;">Прогресс сервера</div>
                    <div style="font-size:14px;font-weight:800;"><?= (int)$progress ?> / <?= (int)$target ?> (<?= $pct ?>%)</div>
                    <div style="font-size:12px;opacity:.85;margin-top:2px;">Тир: <b><?= (int)$tier ?></b></div>
                </div>
                <?php if($active){ ?>
                    <button onclick="newyearDailyClaim()" <?= $free ? '' : 'disabled' ?>
                        style="padding:10px 12px;border-radius:12px;border:0;font-weight:800;cursor:pointer;<?= $free ? 'background:#22c55e;color:#0b1220;' : 'background:#334155;color:#94a3b8;cursor:not-allowed;' ?>">
                        <?= $free ? 'Потрясти ёлку (бесплатно)' : 'Бесплатно уже получено' ?>
                    </button>
                <?php } else { ?>
                    <div style="font-size:12px;opacity:.85;">
                        Последнее окно: <b><?= $fmt($st) ?></b> — <b><?= $fmt($en) ?></b>
                    </div>
                <?php } ?>
            </div>

            <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                <button onclick="newyearExchange('gift')" style="padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font-weight:800;cursor:pointer;">
                    Обмен: Подарок (60)
                </button>
                <button onclick="newyearExchange('firework')" style="padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font-weight:800;cursor:pointer;">
                    Обмен: Фейерверк (25)
                </button>
            </div>
        </div>

        <div style="margin-top:12px;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;">
            <?php for($t=1;$t<=5;$t++){ 
                $unlocked = ($tier >= $t);
            ?>
            <div style="padding:10px;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);text-align:center;">
                <div style="font-size:12px;opacity:.85;">Тир <?= $t ?></div>
                <div style="margin-top:6px;">
                    <button onclick="newyearTierClaim(<?= $t ?>)" <?= $unlocked ? '' : 'disabled' ?>
                        style="width:100%;padding:8px 10px;border-radius:12px;border:0;font-weight:800;<?= $unlocked ? 'background:#3b82f6;color:#fff;cursor:pointer;' : 'background:#334155;color:#94a3b8;cursor:not-allowed;' ?>">
                        <?= $unlocked ? 'Забрать' : 'Закрыто' ?>
                    </button>
                </div>
            </div>
            <?php } ?>
        </div>

        <div style="margin-top:12px;padding:12px;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);">
            <div style="font-weight:800;">Интересное</div>
            <div style="margin-top:8px;display:flex;gap:10px;flex-wrap:wrap;">
                <?php foreach($links as $ln){ ?>
                    <a href="<?= htmlspecialchars($ln['url']) ?>" target="_blank"
                       style="padding:8px 10px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;text-decoration:none;font-weight:700;"
                       title="<?= htmlspecialchars($ln['hint']) ?>">
                        <?= htmlspecialchars($ln['title']) ?>
                    </a>
                <?php } ?>
            </div>
        </div>

        <?php if($isAdmin){ ?>
        <div style="margin-top:12px;padding:12px;border-radius:14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);">
            <div style="font-weight:900;">Админ</div>
            <div style="margin-top:8px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <button onclick="newyearAdminStart(10)" style="padding:10px 12px;border-radius:12px;border:0;font-weight:900;cursor:pointer;background:#f59e0b;color:#111;">
                    Запустить на 10 дней
                </button>
                <button onclick="newyearAdminStop()" style="padding:10px 12px;border-radius:12px;border:0;font-weight:900;cursor:pointer;background:#ef4444;color:#111;">
                    Остановить
                </button>
            </div>
            <div style="margin-top:8px;font-size:12px;opacity:.85;">
                Примечание: по календарю ивент активен только 31.12–06.01, вне окна запускайте через override.
            </div>
        </div>
        <?php } ?>

        <div style="margin-top:14px;display:flex;justify-content:flex-end;">
            <button onclick="(function(){ if(typeof startGame==='function'){ startGame(); } else { $('.GlassModalBg').remove(); $('.GlassModal').remove(); } })()"
                style="padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#fff;font-weight:800;cursor:pointer;">
                Закрыть
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


function get_user_hours(array $u): int {
    // Приоритет 1: Поле hours (если есть и больше 0)
    $hours = arr_get($u, ['hours', 'game_hours', 'play_hours'], null);
    if ($hours !== null && $hours > 0) {
        return (int)$hours;
    }
    
    // Приоритет 2: Секунды (самый точный способ)
    $secs = arr_get($u, ['play_seconds', 'game_seconds', 'seconds_played', 'time_played', 'play_time', 'game_time'], null);
    if ($secs !== null && $secs > 0) {
        return (int)floor(((int)$secs) / 3600);
    }
    
    // Приоритет 3: Минуты (менее точный)
    $mins = arr_get($u, ['play_minutes', 'game_minutes'], null);
    if ($mins !== null && $mins > 0) {
        return (int)floor(((int)$mins) / 60);
    }
    
    // Приоритет 4: Часы из поля hours даже если 0 (для новых пользователей)
    if ($hours !== null) {
        return (int)$hours;
    }
    
    return 0;
}
/** Возвращает (wins, losses, percent) из users (или 0) */
function get_user_wins(array $u): array {
    $pairs = [
        ['wins','lose'],
        ['wins','losses'],
        ['win','lose'],
        ['win','loss'],
        ['pvp_wins','pvp_lose'],
        ['pvp_win','pvp_lose'],
        ['pve_wins','pve_losses']
    ];
    $w = 0; $l = 0;
    foreach ($pairs as $pair) {
        $w_c = arr_get($u, $pair[0]);
        $l_c = arr_get($u, $pair[1]);
        if ($w_c !== null || $l_c !== null) {
            $w = (int)($w_c ?? 0);
            $l = (int)($l_c ?? 0);
            break;
        }
    }
    $total = $w + $l;
    $pct = $total > 0 ? round(($w / $total) * 100, 1) : 0.0;
    return ['wins'=>$w, 'losses'=>$l, 'wins_percent'=>$pct];
}

/* ---------- Учёт времени в игре ----------
 * Требуемые поля в users:
 *   play_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0
 *   hours        INT    UNSIGNED NOT NULL DEFAULT 0
 *   last_play_at INT    UNSIGNED NOT NULL DEFAULT 0
 */
function track_play_time(mysqli $mysqli): void {
    if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) return;

    $userId = (int)$_SESSION['id'];
    $now    = time();
    $newOnline = $now + 300;

    try { $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE); } catch (Throwable $e) {}

    try {
        $sql = "SELECT `online`,
                       IFNULL(`play_seconds`,0) AS play_seconds,
                       IFNULL(`last_play_at`,0) AS last_play_at
                FROM `users`
                WHERE `id` = ?
                FOR UPDATE";
        if (!$stmt = $mysqli->prepare($sql)) throw new Exception('Prepare failed: '.$mysqli->error);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) { $stmt->close(); throw new Exception('User not found.'); }
        $row = $res->fetch_assoc();
        $stmt->close();

        $prevExpire  = (int)$row['online'];           // было now+300
        $prevPing    = $prevExpire > 0 ? max($prevExpire-300, 0) : 0;
        $lastPlayAt  = (int)$row['last_play_at'];
        $playSeconds = (int)$row['play_seconds'];

        // если прошлый online истёк — пользователь офлайн, секунды не добавляем
        if ($prevExpire < $now) {
            $delta = 0;
        } else {
            $baseline = max($lastPlayAt, $prevPing);
            $delta = $now - $baseline;
            if ($delta < 0)   $delta = 0;
            if ($delta > 600) $delta = 600; // защита от скачков
        }

        $newPlaySeconds = $playSeconds + $delta;
        $newHours       = (int) floor($newPlaySeconds / 3600);

        $sqlUpd = "UPDATE `users`
                   SET `online` = ?,
                       `last_play_at` = ?,
                       `play_seconds` = ?,
                       `hours` = ?
                   WHERE `id` = ?";
        if (!$stmt = $mysqli->prepare($sqlUpd)) throw new Exception('Update prepare failed: '.$mysqli->error);
        $stmt->bind_param('iiiii', $newOnline, $now, $newPlaySeconds, $newHours, $userId);
        $stmt->execute();
        $stmt->close();

        try { $mysqli->commit(); } catch (Throwable $e) {}
    } catch (Throwable $e) {
        try { $mysqli->rollback(); } catch (Throwable $e2) {}
        debug_log('track_play_time error', $e->getMessage());
    }
}
// Начисляем время на каждом обращении к файлу
track_play_time($mysqli);

/* ---------- Вход ---------- */
$type = $_POST["type"] ?? '';
$user = isset($_POST['user']) ? $_POST['user'] : ($_SESSION['id'] ?? null);

debug_log('Запрос', ['type' => $type, 'user' => $user, 'POST' => $_POST, 'SESSION' => $_SESSION]);

$response = [];

/* ===========================================================
 *  SWITCH
 * =========================================================== */
switch ($type) {

    /* --------------------- wish --------------------- */
    case 'wish': {
        $pok = (int)($_POST['pok'] ?? 0);
        if (!$pok || !isset($_SESSION['id'])) json_error('Некорректный запрос');

        $stmt = $mysqli->prepare("SELECT id FROM `user_wish` WHERE `user` = ? AND `pok` = ?");
        $stmt->bind_param('ii', $_SESSION['id'], $pok);
        $stmt->execute();
        $bd = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($bd) {
            $stmt = $mysqli->prepare('DELETE FROM user_wish WHERE user = ? AND pok = ?');
            $stmt->bind_param('ii', $_SESSION['id'], $pok);
            $stmt->execute();
            $stmt->close();
            $response['text'] = 'Покемон удален из списка желаний!';
        } else {
            $stmt = $mysqli->prepare("INSERT INTO `user_wish` (`user`,`pok`) VALUES (?,?)");
            $stmt->bind_param('ii', $_SESSION['id'], $pok);
            $stmt->execute();
            $stmt->close();
            $response['text'] = 'Покемон добавлен в список желаний!';
        }
    } break;

    /* --------------------- setColor --------------------- */
    case 'setColor': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');
        $value = tc_clear($_POST['color'] ?? '#515151');
        $stmt = $mysqli->prepare("UPDATE `users` SET `colorChat` = ? WHERE `id` = ?");
        $stmt->bind_param('si', $value, $_SESSION['id']);
        $stmt->execute(); $stmt->close();
        json_ok(['error'=>0,'text'=>'ok','color'=>$value]);
    } break;

    /* --------------------- status --------------------- */
    case 'status': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');
        $value = tc_clear($_POST['value'] ?? '');
        $value = $mysqli->real_escape_string($value);
        $mysqli->query("UPDATE `users` SET `about` = '".$value."' WHERE `id` = '".$_SESSION['id']."'");
        if (function_exists('news_friend')) news_friend(3,$value);
        $response['text'] = $_POST['value'] ?? '';
    } break;

    /* --------------------- friend --------------------- */
    case 'friend': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');
        $login = tc_clear($user);
        $stmtU = $mysqli->prepare("SELECT `id`,`location` FROM `users` WHERE `login` = ? LIMIT 1");
        $stmtU->bind_param('s', $login);
        $stmtU->execute();
        $users = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();

        $stmtMe = $mysqli->prepare("SELECT `location`,`id` FROM `users` WHERE `id` = ? LIMIT 1");
        $stmtMe->bind_param('i', $_SESSION['id']);
        $stmtMe->execute();
        $my = $stmtMe->get_result()->fetch_assoc();
        $stmtMe->close();

        if (!$users) { $response = ['error'=>1,'text'=>'Тренер не найден!']; break; }

        if ($users['location'] != $my['location']) {
            $response = ['error'=>1,'text'=>'Вы слишком далеко друг от друга!'];
        } elseif ((int)$users['id'] === (int)$my['id']) {
            $response = ['error'=>1,'text'=>'Нельзя добавить себя в друзья!'];
        } else {
            $stmtF = $mysqli->prepare("SELECT id FROM `users_friend` WHERE (`user_id` = ? AND `friend_id` = ?) OR (`user_id` = ? AND `friend_id` = ?)");
            $stmtF->bind_param('iiii', $_SESSION['id'], $users['id'], $users['id'], $_SESSION['id']);
            $stmtF->execute();
            $exists = $stmtF->get_result()->num_rows > 0;
            $stmtF->close();

            if ($exists) {
                $response = ['error'=>1,'text'=>'Заявка уже отправлена, либо тренер у вас в друзьях!'];
            } else {
                $stmtI = $mysqli->prepare("INSERT INTO `users_friend` (`user_id`,`friend_id`,`status`) VALUES (?,?,0)");
                $stmtI->bind_param('ii', $_SESSION['id'], $users['id']);
                $stmtI->execute(); $stmtI->close();
                $response = ['error'=>0,'text'=>'Заявка успешно отправлена!'];
            }
        }
    } break;

    /* --------------------- AddClan --------------------- */
    case 'AddClan': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $login = tc_clear($user);
        $u = $mysqli->query("SELECT * FROM `users` WHERE `login` = '".$mysqli->real_escape_string($login)."'")->fetch_assoc();
        if (!$u) { $response['text']='Тренер не найден.'; break; }

        $users_clan = $mysqli->query("SELECT * FROM `base_clans_users` WHERE `user_id` = ".(int)$u['id'])->fetch_assoc();
        $clan = $mysqli->query("SELECT * FROM `base_clans_users` WHERE `user_id` = ".(int)$_SESSION['id'])->fetch_assoc();

        if ($clan) {
            if ($users_clan) {
                $response['text'] = 'Тренер уже состоит в клане.';
            } else {
                if ((int)$clan['group'] === 1) {
                    $response['error'] = 0;
                    $response['text'] = 'Заявка тренеру отправлена.';
                    $mysqli->query("INSERT INTO `user_clan_accept` (`user_id`,`clan_id`) VALUES (".(int)$u['id'].",".(int)$clan['clan_id'].")");
                } else {
                    $response['error'] = 1;
                    $response['text'] = 'Невозможно добавить в клан. Вы не лидер клана.';
                }
            }
        } else {
            $response['text'] = 'Вы не лидер клана.';
        }
    } break;

    /* --------------------- DeleteClan --------------------- */
    case 'DeleteClan': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $login = tc_clear($user);
        $u = $mysqli->query("SELECT * FROM `users` WHERE `login` = '".$mysqli->real_escape_string($login)."'")->fetch_assoc();
        if (!$u) { $response['error']=1; $response['text']='Тренер не найден.'; break; }

        $users_clan = $mysqli->query("SELECT * FROM `base_clans_users` WHERE `user_id` = ".(int)$u['id'])->fetch_assoc();
        $clan       = $mysqli->query("SELECT * FROM `base_clans_users` WHERE `user_id` = ".(int)$_SESSION['id'])->fetch_assoc();

        if ($clan) {
            if ((int)$clan['group'] === 1) {
                if ((int)$_SESSION['id'] == (int)$users_clan['user_id']) {
                    $response = ['error'=>1,'text'=>'Невозможно удалить себя из клана.'];
                } else {
                    if ((int)$users_clan['clan_id'] === (int)$clan['clan_id']) {
                        $users_clан_gl = $mysqli->query("SELECT * FROM `base_clans` WHERE `id` = ".(int)$users_clan['clan_id'])->fetch_assoc();
                        $info = json_decode($users_clan_gl['info'] ?? '{}');
                        if ((int)($info->Creater ?? 0) === (int)$users_clan['user_id']) {
                            $response = ['error'=>1,'text'=>'Невозможно удалить из клана. Тренер является основателем.'];
                        } else {
                            $response = ['error'=>0,'text'=>'Тренер удален из клана.'];
                            $insertJson = '{"user_new":"'.$u['login'].'","user_group":"'.$u['user_group'].'","date":"'.date('d.m.Y').'"}';
                            $mysqli->query("INSERT INTO `log_game` (`user_id`,`type`,`title`,`info`) VALUES ('".$users_clan['clan_id']."','clan','LEFT_CLAN_ALERT','".$insertJson."')");
                            $mysqli->query("DELETE FROM `base_clans_users` WHERE `user_id` = ".(int)$users_clan['user_id']);
                        }
                    } else {
                        $response = ['error'=>1,'text'=>'Невозможно удалить из клана. Тренер не состоит в вашем клане.'];
                    }
                }
            } else {
                $response = ['error'=>1,'text'=>'Невозможно удалить из клана. Вы не лидер клана.'];
            }
        } else {
            $response['text'] = 'Вы не состоите в клане.';
        }
    } break;

    /* --------------------- DeleteFriend --------------------- */
    case 'DeleteFriend': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $login = tc_clear($user);
        $u = $mysqli->query("SELECT * FROM `users` WHERE `login` = '".$mysqli->real_escape_string($login)."'")->fetch_assoc();
        if (!$u) { $response = ['error'=>1, 'text'=>'Тренер не найден!']; break; }

        $friends = $mysqli->prepare("SELECT * FROM `users_friend` WHERE (`user_id` = ? AND `friend_id` = ?) OR (`user_id` = ? AND `friend_id` = ?)");
        $friends->bind_param('iiii', $_SESSION['id'], $u['id'], $u['id'], $_SESSION['id']);
        $friends->execute();
        $res = $friends->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $friends->close();
            $mysqli->query("DELETE FROM `users_friend` WHERE `id` = '".(int)$row['id']."'");
            $response = ['error'=>0,'text'=>'Дружба с '.$login.' разорвана!'];
        } else {
            $friends->close();
            $response = ['error'=>1,'text'=>'Тренера нет у вас в друзьях!'];
        }
    } break;

    /* --------------------- mygifts --------------------- */
    case 'mygifts': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $giftList = [];
        $stmt = $mysqli->prepare("SELECT * FROM `gifts` WHERE `user_to` = ? AND `status` = 'received' ORDER BY `id` DESC");
        $stmt->bind_param('i', $_SESSION['id']);
        $stmt->execute();
        $gifts = $stmt->get_result();
        while ($gift = $gifts->fetch_assoc()) {
            $from_login = $mysqli->query("SELECT `login` FROM `users` WHERE `id` = '".(int)$gift['user_from']."'")->fetch_assoc()['login'] ?? '';
            $giftRow = $mysqli->query("SELECT `name`,`img` FROM `giftshop_items` WHERE `id` = '".(int)$gift['gift_id']."'")->fetch_assoc();
            $giftList[] = [
                'id'        => (int)$gift['id'],
                'gift_id'   => (int)$gift['gift_id'],
                'from_user' => (int)$gift['user_from'],
                'from_login'=> $from_login,
                'message'   => $gift['message'],
                'date'      => date('d.m.Y H:i', (int)$gift['date']),
                'title'     => $giftRow['name'] ?? 'Подарок',
                'img'       => $giftRow['img']  ?? '/images/giftshop/default.png',
            ];
        }
        $stmt->close();
        json_ok(['giftList' => $giftList]);
    } break;

    /* --------------------- GiftFriend --------------------- */
    case 'GiftFriend': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $login = tc_clear($user);
        $users = $mysqli->query("SELECT * FROM `users` WHERE `login` = '".$mysqli->real_escape_string($login)."'")->fetch_assoc();
        if (!$users) { $response = ['error'=>1,'text'=>'Тренер не найден!']; break; }

        $friends = $mysqli->prepare("SELECT * FROM `users_friend` WHERE (`user_id` = ? AND `friend_id` = ?) OR (`user_id` = ? AND `friend_id` = ?)");
        $friends->bind_param('iiii', $_SESSION['id'], $users['id'], $users['id'], $_SESSION['id']);
        $friends->execute();
        $res = $friends->get_result();
        if ($res->num_rows > 0) {
            $us = $mysqli->query("SELECT * FROM `users` WHERE `id` = '".(int)$_SESSION['id']."'")->fetch_assoc();
            $us_gift = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_from` = '".(int)$_SESSION['id']."' AND `user_to` = '".(int)$users['id']."' AND `active` = 0 ")->fetch_assoc();
            if (!$us_gift) {
                $us_gift_del = $mysqli->query("SELECT * FROM `GiftFriend` WHERE `user_from` = '".(int)$_SESSION['id']."' AND `user_to` = '".(int)$users['id']."' ORDER BY `id` DESC LIMIT 1 ")->fetch_assoc();
                if (($us_gift_del['date_limit'] ?? 0) < time()) {
                    if ((int)$us['gift_limit'] > 0) {
                        $l = (int)$us['gift_limit'] - 1;
                        if      ($l === 0) $a = "У вас не осталось больше подарков!";
                        else if ($l === 1) $a = "У вас остался <b>1</b> подарок!";
                        else if ($l >= 2 && $l <= 4) $a = "У вас осталось <b>".$l."</b> подарка!";
                        else $a = "У вас осталось <b>".$l."</b> подарков!";
                        $mysqli->query("UPDATE `users` SET `gift_limit` = '".$l."' WHERE `id` = '".(int)$_SESSION['id']."'");
                        $lim = time()+172800;
                        $mysqli->query("INSERT INTO `GiftFriend` (`user_to`,`user_from`,`date_limit`) VALUES ('".(int)$users['id']."','".(int)$_SESSION['id']."','".$lim."')");
                        $response = ['error'=>0,'text'=>'Подарок для <b>'.$login.'</b> отправлен! '.$a];
                        if (function_exists('check_mission_ivent') && function_exists('add_mission_ivent')) {
                            if (check_mission_ivent(2)) add_mission_ivent(2);
                        }
                    } else {
                        $response = ['error'=>1,'text'=>'У вас нет доступных подарков!'];
                    }
                } else {
                    $response = ['error'=>1,'text'=>'Еще не пришло время для подарка <b>'.$login.'</b>!'];
                }
            } else {
                $response = ['error'=>1,'text'=>'У <b>'.$login.'</b> еще не распакован прошлый ваш подарок!'];
            }
        } else {
            $response = ['error'=>1,'text'=>'Тренера нет у вас в друзьях!'];
        }
    } break;

    /* --------------------- giftinfo (JSON + exit) --------------------- */
    case 'giftinfo': {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['id'])) json_ok(['error' => 'Не авторизован']);
        if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) json_ok(['error' => 'Некорректный запрос']);

        $giftId = (int)$_GET['id'];
        $userId = (int)$_SESSION['id'];

        $stmt = $mysqli->prepare("
            SELECT id, from_user, to_user, gift_id, message, created_at, status
            FROM gifts
            WHERE id = ? AND to_user = ? AND status IN ('received','pending')
            LIMIT 1
        ");
        if (!$stmt) json_ok(['error' => 'DB error (prepare)']);
        $stmt->bind_param('ii', $giftId, $userId);
        $stmt->execute();
        $gift = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$gift) json_ok(['error' => 'Подарок не найден']);

        $stmt = $mysqli->prepare("SELECT name, img, description, price FROM giftshop_items WHERE id = ? LIMIT 1");
        if (!$stmt) json_ok(['error' => 'DB error (prepare base)']);
        $stmt->bind_param('i', $gift['gift_id']);
        $stmt->execute();
        $baseGift = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$baseGift) json_ok(['error' => 'Информация о предмете не найдена']);

        $stmt = $mysqli->prepare("SELECT login FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $gift['from_user']);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else $u = null;

        json_ok([
            'id'              => (int)$gift['id'],
            'gift_id'         => (int)$gift['gift_id'],
            'title'           => $baseGift['name'],
            'img'             => $baseGift['img'],
            'description'     => $baseGift['description'],
            'price'           => $baseGift['price'],
            'message'         => $gift['message'],
            'from_user_id'    => (int)$gift['from_user'],
            'from_user_login' => $u ? $u['login'] : 'Неизвестно',
            'date'            => $gift['created_at'],
            'status'          => $gift['status']
        ]);
    } break;

    /* --------------------- trainercard (JSON + exit) --------------------- */
case 'trainercard': {
    try {
        if (file_exists($_SERVER['DOCUMENT_ROOT'].'/do/avatar_functions.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'].'/do/avatar_functions.php';
        }

        // Подключаем функции расчета рангов
        if (file_exists($_SERVER['DOCUMENT_ROOT'].'/inc/function/rank_functions.php')) {
            require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/rank_functions.php';
        }

        $now        = time();
        $timeOnline = $now - 300;

        /* ===== USER ===== */
        $stmtUser = $mysqli->prepare("SELECT * FROM `users` WHERE `login` = ? LIMIT 1");
        $stmtUser->bind_param('s', $user);
        $stmtUser->execute();
        $users = $stmtUser->get_result()->fetch_assoc();
        $stmtUser->close();
        if (!$users) json_error('Тренер не найден');

        $uid = (int)$users['id'];
        $sid = (int)($_SESSION['id'] ?? 0);

        /* ===== LOCATION ===== */
        $stmtLoc = $mysqli->prepare("SELECT * FROM `base_location` WHERE `id` = ? LIMIT 1");
        $stmtLoc->bind_param('i', $users['location']);
        $stmtLoc->execute();
        $location = $stmtLoc->get_result()->fetch_assoc();
        $stmtLoc->close();
        if (!$location) json_error('Локация не найдена');

        $stmtReg = $mysqli->prepare("SELECT * FROM `base_region` WHERE `id` = ? LIMIT 1");
        $stmtReg->bind_param('i', $location['region']);
        $stmtReg->execute();
        $region = $stmtReg->get_result()->fetch_assoc();
        $stmtReg->close();
        if (!$region) json_error('Регион не найден');

        /* ===== CLOTH ===== */
        $stmtCloth = $mysqli->prepare("SELECT * FROM `cloth` WHERE `user` = ? LIMIT 1");
        $stmtCloth->bind_param('i', $uid);
        $stmtCloth->execute();
        $cloth = $stmtCloth->get_result()->fetch_assoc();
        $stmtCloth->close();

        /* ===== ONLINE ===== */
        $is_online = ($users['online'] >= $timeOnline)
            ? ['class' => 'onl', 'text' => 'В сети']
            : ['class' => 'ofl', 'text' => 'Не в сети'];

        /* ===== FRIEND CHECK ===== */
        $stmtFriend = $mysqli->prepare("
            SELECT `id`
            FROM `users_friend`
            WHERE (
                (`user_id` = ? AND `friend_id` = ?) OR
                (`user_id` = ? AND `friend_id` = ?)
            )
            AND `status` = 1
            LIMIT 1
        ");
        $stmtFriend->bind_param('iiii', $uid, $sid, $sid, $uid);
        $stmtFriend->execute();
        $isFriend = $stmtFriend->get_result()->num_rows;
        $stmtFriend->close();

        $avatarMini = $uid;
        $banInfo = json_decode($users['ban'] ?? '{}');
        $bigAva = (function_exists('getAvatarPath') ? getAvatarPath($cloth, $users['sex']) : '');

        /* ===== ФУНКЦИИ ДЛЯ РАНГОВ ===== */
        function getFirstWord($text) {
            if (empty($text)) return '';
            $words = explode(' ', trim($text));
            return $words[0] ?? '';
        }

        // Функции расчета рангов (встроенные)
        function population($population) {
            if ($population >= 0 && $population < 400) return "";
            if ($population >= 401 && $population <= 1000) return "Начинающий";
            if ($population >= 1001 && $population <= 5000) return "Неизвестный";
            if ($population >= 5001 && $population <= 15000) return "Известный";
            if ($population >= 15001 && $population <= 30000) return "Знаменитый";
            if ($population >= 30001 && $population <= 80000) return "Величайший";
            if ($population >= 80001 && $population <= 150000) return "Прославленный";
            if ($population >= 150001 && $population <= 400000) return "Выдающийся";
            if ($population >= 400001 && $population <= 1000000) return "Легендарный";
            if ($population > 1000000) return "Могущественный";
            return "";
        }

        function reputation($reputation, $battleCount) {
            if ($battleCount <= 0) return "";
            
            $countLose = $battleCount - round($reputation / 2);
            $percentWin = ($battleCount - $countLose) * 10;
            
            if ($percentWin >= 0 && $percentWin < 10) return "Ученик";
            if ($percentWin >= 11 && $percentWin <= 20) return "Тренер";
            if ($percentWin >= 21 && $percentWin <= 35) return "Мастер";
            if ($percentWin >= 36 && $percentWin <= 49) return "Профи";
            if ($percentWin >= 50 && $percentWin <= 68) return "Мудрец";
            if ($percentWin >= 69 && $percentWin <= 79) return "Отверженец";
            if ($percentWin >= 80 && $percentWin <= 90) return "Чемпион";
            if ($percentWin >= 91 && $percentWin <= 100) return "Гуру";
            return "";
        }

        /* ===== РАСЧЕТ РАНГА ТРЕНЕРА ===== */
        $trainerRank = 'Новичок'; // По умолчанию
        
        // Получаем данные рейтинга
        $pver = json_decode($users['rating'] ?? '{}');
        if ($pver && isset($pver->pve, $pver->pvp, $pver->battleCount)) {
            $pveRank = population((int)$pver->pve);
            $pvpRank = reputation((int)$pver->pvp, (int)$pver->battleCount);
            
            // Объединяем ранги
            $combinedRank = trim($pveRank . ' ' . $pvpRank);
            
            if (!empty($combinedRank)) {
                // Извлекаем только первое слово
                $trainerRank = getFirstWord($combinedRank);
            }
        }
        
        // Если ранг пустой, используем сохраненный в БД
        if (empty($trainerRank) || $trainerRank === '') {
            $trainerRank = getFirstWord($users['rang'] ?? 'Новичок');
        }
        
        // Если все еще пустой - ставим дефолт
        if (empty($trainerRank)) {
            $trainerRank = 'Новичок';
        }

        /* ===== ADMIN RANKS ===== */
        $groupRankMap = [
            2 => 'Полицейский',
            3 => 'Модератор',
            4 => 'Наставник',
            5 => 'Лидер ',
            8 => 'Заключенный (до '.date('d.m.Y', $banInfo->game ?? 0).')',
            9 => 'Художник',
            100 => 'Куратор турниров'
        ];
        $adminRank = $groupRankMap[$users['user_group']] ?? '';
        
        $gymUsers = [
            262 => 'электрического стадиона',
            410 => 'боевого стадиона',
            917 => 'стадиона драконов',
            312 => 'огненного стадиона',
            118 => 'ядовитого стадиона',
            841 => 'темного стадиона',
            79  => 'земляного стадиона',
            134 => 'психического стадиона'
        ];
        $gymType = ($users['user_group'] == 5 && isset($gymUsers[$uid])) ? $gymUsers[$uid] : '';

        /* ===== POKEDEX COUNTS ===== */
        $stmtCount = $mysqli->prepare("SELECT COUNT(DISTINCT basenum) as cnt FROM `user_pokemons` WHERE user_id = ?");
        $stmtCount->bind_param('i', $uid);
        $stmtCount->execute();
        $rs = $stmtCount->get_result()->fetch_assoc();
        $stmtCount->close();

        $stmtShine = $mysqli->prepare("SELECT COUNT(DISTINCT basenum) as cnt FROM `user_pokemons` WHERE user_id = ? AND type='shine'");
        $stmtShine->bind_param('i', $uid);
        $stmtShine->execute();
        $rsSh = $stmtShine->get_result()->fetch_assoc();
        $stmtShine->close();
        $countPoks = [
            'normal' => (int)($rs['cnt'] ?? 0),
            'shine'  => (int)($rsSh['cnt'] ?? 0)
        ];

        /* ===== FRIENDS COUNT ===== */
        $stmtFriendsNum = $mysqli->prepare("
            SELECT COUNT(*) as c FROM `users_friend`
            WHERE (`user_id` = ? OR `friend_id` = ?) AND `status` = 1
        ");
        $stmtFriendsNum->bind_param('ii', $uid, $uid);
        $stmtFriendsNum->execute();
        $friends = (int)($stmtFriendsNum->get_result()->fetch_assoc()['c'] ?? 0);
        $stmtFriendsNum->close();

        /* ===== TOURNAMENT RATING ===== */
        $tournamentRating = (int)($users['tournament_rating'] ?? 0);
        if      ($tournamentRating <= 9)  { $ratingCategory = 'C'; $categoryTitle = 'Новичок'; }
        elseif  ($tournamentRating <=24 ) { $ratingCategory = 'B'; $categoryTitle = 'Неопытный'; }
        elseif  ($tournamentRating <=49 ) { $ratingCategory = 'A'; $categoryTitle = 'Опытный'; }
        else                              { $ratingCategory = 'S'; $categoryTitle = 'Мастер'; }

        /* ===== TIME STRINGS ===== */
        $qq = ($users['sex'] == 'f') ? 'Была' : 'Был';
        $timeReg = $now - (int)$users['dateReg']; $timeReg = $now + $timeReg;
        $timeRegArr = explode(' ', downcounter($timeReg));
        $timeRegistration = ($timeRegArr[0] ?? '') . ' ' . ($timeRegArr[1] ?? '');
        $timeOnl = $now - (int)$users['online']; $timeOnl = $now + $timeOnl;
        $timeOnlArr = explode(' ', downcounter($timeOnl));
        $timeOnlines = ($timeOnlArr[0] ?? '') . ' ' . ($timeOnlArr[1] ?? '');
        $is_online1 = ((int)$users['online'] >= $timeOnline) ? '' : '<br> '.$qq.' в сети '.$timeOnlines.' назад';

        /* ===== TROPHIES ===== */
        $trophyList = [];
        $trophys = $mysqli->query("
            SELECT iu.id AS id_tr, iu.item_id, bi.info
            FROM items_users iu
            INNER JOIN base_items bi ON bi.id = iu.item_id
            WHERE iu.user = {$uid} AND iu.trophy = 1
        ");
        while ($trophy = $trophys->fetch_assoc()) {
            $imgItem = $trophy['item_id'];
            if ((int)$trophy['item_id'] >= 1000001) {
                $mesto = explode(',', $trophy['info']);
                $imgItem = ($mesto[0] ?? '') . '.' . ($mesto[1] ?? '');
            }
            $trophyList[$trophy['id_tr']] = [
                'trophy' => $imgItem,
                'info'   => $trophy['item_id']
            ];
        }

        /* ===== LEGACY user_gift LIST ===== */
        $giftList = [];
        if ($stmt = $mysqli->prepare("SELECT id, id_gift, user2 FROM user_gift WHERE user = ?")) {
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($g = $res->fetch_assoc()) {
                $giftList[(int)$g['id']] = [
                    'gift' => (int)$g['id_gift'],
                    'user' => (int)$g['user2']
                ];
            }
            $stmt->close();
        }

        /* ===== FRIENDS LIST (HTML) ===== */
        $friendsIdsRes = $mysqli->query("
            SELECT IF(user_id = {$uid}, friend_id, user_id) AS fid
            FROM users_friend
            WHERE (user_id = {$uid} OR friend_id = {$uid}) AND status = 1
        ");
        $friends_list_id = [];
        while ($row = $friendsIdsRes->fetch_assoc()) $friends_list_id[] = (int)$row['fid'];
        $freindList = '';
        if (!empty($friends_list_id)) {
            $ids = implode(',', array_unique($friends_list_id));
            $userList = $mysqli->query("
                SELECT id, login, user_group, online, rang
                FROM users
                WHERE id IN ($ids)
            ");
            while ($value = $userList->fetch_assoc()) {
                $is_onl_item = ((int)$value['online'] >= $timeOnline) ? 'onl' : 'ofl';
                // Для друзей тоже берем только первое слово ранга
                $friendRank = getFirstWord($value['rang'] ?? 'Новичок');
                
                $freindList .= '<div class="User"><div class="TrainerBlock">'
                             . '<div onclick="showUserTooltip('. (int)$value['id'] .')" class="Avatar" style="background-image: url(/img/avatars/mini/'.(int)$value['id'].'.png);">'
                             . '<div class="Status '.$is_onl_item.'"></div></div>'
                             . '<div class="Title"><div class="Name"><div class="u-'.(int)$value['user_group'].' label" onclick="user_to_chat_add('.(int)$value['id'].')">'
                             . htmlspecialchars($value['login']).'</div></div><div class="Other">'
                             . htmlspecialchars($friendRank).'</div></div></div></div>';
            }
        }

        /* ===== ACHIEVEMENTS ===== */
        $achivm = '';
        if ($uid == $sid) {
            $achivQuery = "
                SELECT ba.*, ua.count AS ua_count, ua.complete AS ua_complete
                FROM base_achievements ba
                LEFT JOIN user_achievements ua
                    ON ua.id_ach = ba.id AND ua.user_id = {$uid}
                ORDER BY ua.complete DESC
            ";
        } else {
            $achivQuery = "
                SELECT ba.*, ua.count AS ua_count, ua.complete AS ua_complete
                FROM base_achievements ba
                LEFT JOIN user_achievements ua
                    ON ua.id_ach = ba.id AND ua.user_id = {$uid}
                WHERE ua.complete = 1
                ORDER BY ua.complete DESC
            ";
        }
        $achiv = $mysqli->query($achivQuery);
        while ($ach = $achiv->fetch_assoc()) {
            $compl = ((int)$ach['ua_complete'] === 1 ? 'complete' : '');
            $achivm .= '<div class="achiev-container">'
                     . '<div class="achiev '.$compl.'" onclick="issetAll('.(int)$ach['id'].',\'achiev\','.$uid.');">'
                     . '<img src="/img/achiv/'.(int)$ach['id'].'.png" alt="'.htmlspecialchars($ach['name']).'">'
                     . '<div class="text"><div class="name">'.htmlspecialchars($ach['name']).'</div></div>'
                     . '</div></div>';
        }

        /* ===== ACTIVE TEAM ===== */
        $ballList = [];
        $Balls = $mysqli->query("SELECT * FROM `user_pokemons` WHERE `user_id` = {$uid} AND `active` = 1");
        $teamOpen = (int)$users['team_open'] === 1;
        while ($pok = $Balls->fetch_assoc()) {
            if ($teamOpen) {
                $ballList[$pok['id']] = [
                    'ball' => numCheck_basenum($pok['basenum']),
                    'styleball' => 'div',
                    'typeball' => '/img/pokemons/animation/'
                ];
            } else {
                $ballList[$pok['id']] = [
                    'ball' => $pok['ball'],
                    'styleball' => 'div',
                    'typeball' => '/img/world/items/little/'
                ];
            }
        }

        /* ===== NEW GIFTS (gifts table) ===== */
        $needStatuses = ($uid === $sid) ? ['received','pending'] : ['received'];
        $receivedGifts = [];
        $sqlStatuses  = implode(',', array_fill(0, count($needStatuses), '?'));
        $types = 'i' . str_repeat('s', count($needStatuses)) . 'i';
        $params = [$uid, ...$needStatuses, 120];
        $sql = "
            SELECT g.id, g.gift_id, g.from_user, g.to_user, g.message, g.created_at, g.status,
                   gi.name, gi.img, gi.description, gi.price,
                   u.login AS from_login
            FROM gifts g
            LEFT JOIN giftshop_items gi ON gi.id = g.gift_id
            LEFT JOIN users u ON u.id = g.from_user
            WHERE g.to_user = ? AND g.status IN ($sqlStatuses)
            ORDER BY g.id DESC
            LIMIT ?
        ";
        if ($stmtG = $mysqli->prepare($sql)) {
            $stmtG->bind_param($types, ...$params);
            $stmtG->execute();
            $res = $stmtG->get_result();
            while ($r = $res->fetch_assoc()) {
                $receivedGifts[] = [
                    'id'               => (int)$r['id'],
                    'gift_id'          => (int)$r['gift_id'],
                    'from_user_id'     => (int)$r['from_user'],
                    'from_user_login'  => $r['from_login'] ?? 'Неизвестно',
                    'title'            => $r['name'] ?? 'Без названия',
                    'img'              => $r['img'] ?? '/images/giftshop/default.png',
                    'description'      => $r['description'] ?? '',
                    'price'            => $r['price'] ?? '',
                    'message'          => $r['message'] ?? '',
                    'date'             => $r['created_at'] ?? '',
                    'status'           => $r['status'] ?? ''
                ];
            }
            $stmtG->close();
        }

        $giftsCounters = ['received'=>0,'pending'=>0,'other'=>0];
        foreach ($receivedGifts as $g) {
            $st = strtolower($g['status']);
            if (isset($giftsCounters[$st])) $giftsCounters[$st]++; else $giftsCounters['other']++;
        }
        $receivedGiftsMap = [];
        foreach ($receivedGifts as $g) $receivedGiftsMap[$g['id']] = $g;

        /* ===== WISH LIST ===== */
        $WishList = '';
        $Wish = $mysqli->query("SELECT `pok` FROM `user_wish` WHERE `user` = {$uid} ORDER BY `pok` ASC");
        while ($ws = $Wish->fetch_assoc()) {
            $WishList .= '<img src="/img/pokemons/animation/'.(int)$ws['pok'].'.png" onclick="openDex('.(int)$ws['pok'].')">';
        }

        /* ===== PERKS ===== */
        $UmenList = '';
        $util = $mysqli->query("SELECT * FROM `user_achievements_utility` WHERE `user` = {$uid} ORDER BY `utility` ASC");
        while ($ut = $util->fetch_assoc()) {
            $base_util = $mysqli->query("SELECT `name` FROM `base_achievements` WHERE `id` = '".(int)$ut['utility']."'")->fetch_assoc();
            if ($base_util) $UmenList .= '<div class="perk"><span>'.htmlspecialchars($base_util['name']).'</span></div>';
        }

        /* ===== CLAN ===== */
        $stmtClan = $mysqli->prepare("SELECT `clan_id` FROM `base_clans_users` WHERE `user_id` = ? LIMIT 1");
        $stmtClan->bind_param('i', $uid);
        $stmtClan->execute();
        $clanUser = $stmtClan->get_result()->fetch_assoc();
        $stmtClan->close();

        /* ===== LVL PROGRESS ===== */
        $expLvl   = (int)$users['exp_lvl'];
        $expLvlTo = (int)$users['exp_lvl_to'];
        $wdth     = ($expLvlTo > 0 ? ($expLvl / $expLvlTo) * 100 : 0);

        /* ===== TRAINER POKEMON (NEAR) ===== */
        $trainerPokemonBasenum = (
            isset($users['trainer_pokemon']) && $users['trainer_pokemon'] !== null && $users['trainer_pokemon'] !== ""
                ? str_pad((int)$users['trainer_pokemon'], 3, "0", STR_PAD_LEFT)
                : "000"
        );

        /* ===== HOURS & WINS% ===== */
        // Функция для получения часов пользователя
        function getUserHours($users) {
            // Приоритет 1: Секунды (самый точный способ)
            $secs = $users['play_seconds'] ?? $users['game_seconds'] ?? $users['seconds_played'] ?? $users['time_played'] ?? $users['play_time'] ?? $users['game_time'] ?? null;
            if ($secs !== null && $secs > 0) {
                return (int)floor(((int)$secs) / 3600);
            }
            
            // Приоритет 2: Минуты (менее точный)
            $mins = $users['play_minutes'] ?? $users['game_minutes'] ?? null;
            if ($mins !== null && $mins > 0) {
                return (int)floor(((int)$mins) / 60);
            }
            
            // Приоритет 3: Часы (совместимость)
            $hours = $users['hours'] ?? $users['game_hours'] ?? $users['play_hours'] ?? null;
            if ($hours !== null) {
                return (int)$hours;
            }
            
            return 0;
        }

        // Функция для получения статистики побед (исправлена под ваши поля БД)
        function getUserWins($users) {
            // Получаем данные из полей БД
            $wins = (int)($users['pvp'] ?? 0);           // pvp = количество побед
            $totalBattles = (int)($users['battleCount'] ?? 0); // battleCount = общее количество битв
            $losses = $totalBattles - $wins;            // losses = общее - победы
            
            // Рассчитываем процент побед с округлением в большую сторону
            if ($totalBattles > 0) {
                $winsPercentFloat = ($wins / $totalBattles) * 100;
                $wins_percent = (int)ceil($winsPercentFloat); // Округляем в большую сторону
            } else {
                $wins_percent = 0;
            }
            
            return [
                'wins' => $wins,
                'losses' => $losses,
                'total_battles' => $totalBattles,
                'wins_percent' => $wins_percent
            ];
        }

        // Функция для конвертации секунд в формат HH:MM:SS
        function secondsToHms($seconds) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $seconds = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        // Часы из разных возможных полей
        $hours = getUserHours($users);
        // Сырые секунды (если есть в базе)
        $time_played_seconds = (int)($users['play_seconds'] ?? $users['time_played'] ?? $users['game_time'] ?? $users['play_time'] ?? 0);
        $time_played_human   = secondsToHms($time_played_seconds);
        $hours_text          = $hours.' ч';

        $winsMeta = getUserWins($users);
        $wins_percent_text = $winsMeta['wins_percent'].'%';

        // Формируем группу пользователя для отображения
        $userGroupText = '';
        if ($users['user_group'] == 1) {
            $userGroupText = 'Администрация';
        } else {
            $userGroupText = trim($adminRank.' '.$gymType);
        }

        /* ===== RESPONSE ===== */
        $resp = [
            'id' => $uid,
            'clanUserCheck' => ($clanUser ? 1 : 0),
            'clanUser' => $clanUser['clan_id'] ?? null,
            'ballList' => $ballList,

            // достижения
            'achivmentsList' => $achivm,
            'achievementsList' => $achivm, // алиас

            // друзья
            'friendsList' => $freindList,

            // награды (трофеи)
            'trophyList' => $trophyList,
            'trophies'   => $trophyList,   // алиас

            'WishList' => $WishList,
            'UmenList' => $UmenList,

            // legacy + новые подарки
            'giftList' => $giftList,
            'receivedGifts' => $receivedGifts,
            'receivedGiftsMap' => $receivedGiftsMap,
            'giftsCounters' => $giftsCounters,

            'editStatus' => ($uid == $sid ? 1 : 0),
            'login' => $user,
            'status' => $users['about'] ?? '',
            'miniIcon' => $avatarMini,
            'bigAva' => $bigAva,
            'rang' => $trainerRank,  // ТОЛЬКО ПЕРВОЕ СЛОВО РАНГА
            'userGroupText' => $userGroupText,  // Группа пользователя (админ/модер)
            'classOnl' => $is_online['class'] ?? '',
            'textOnl'  => $is_online['text'] ?? '',
            'location' => ($isFriend > 0 || $uid == $sid ? $location['name'] : '...'),
            'region'   => $region['name'] ?? '',
            'sex'      => $users['sex'] ?? 'm',

            'dex'       => $countPoks['normal'],
            'shineDex'  => $countPoks['shine'],
            'friends'   => $friends,

            'tournamentRating' => $tournamentRating,
            'ratingCategory'   => $ratingCategory,
            'categoryTitle'    => $categoryTitle,

            'inGame'    => $timeRegistration,
            'lastGame'  => $is_online1,

            'pver' => (int)($pver->pve ?? 0),
            'error' => 0,

            'lvluser' => (int)($users['lvl'] ?? 0),
            'explvl' => $expLvl,
            'explvl_to' => $expLvlTo,
            'widthlvluser' => $wdth,

            'model' => $cloth['model'] ?? null,
            'skin'  => $cloth['skin'] ?? null,
            'color' => $cloth['color'] ?? null,
            'hat'   => $cloth['hat'] ?? null,
            'skinName' => (
                isset($cloth['skinName']) && $cloth['skinName']
                    ? $cloth['skinName']
                    : (
                        isset($cloth['model'], $cloth['skin']) && $cloth['model'] && $cloth['skin']
                            ? $cloth['model'].'/'.$cloth['skin'].(
                                isset($cloth['color']) && $cloth['color'] ? ' ('.$cloth['color'].')' : ''
                              )
                            : null
                      )
            ),
            'hatName' => (isset($cloth['hat']) && function_exists('getHatTitle')) ? getHatTitle($cloth['hat']) : ($cloth['hat'] ?? null),

            'trainerPokemonBasenum' => $trainerPokemonBasenum,

            // ВРЕМЯ В ИГРЕ — сразу несколько ключей для совместимости
            'hours'               => $hours,                // целое количество часов
            'hours_text'          => $hours_text,           // "NN ч"
            'time_played_seconds' => $time_played_seconds,  // сырые секунды (если есть в БД)
            'time_played_human'   => $time_played_human,    // "HH:MM:SS"

            // WINS (исправлено под ваши поля БД)
            'wins'               => $winsMeta['wins'],          // pvp (победы)
            'wins_losses'        => $winsMeta['losses'],        // поражения (battleCount - pvp)
            'total_battles'      => $winsMeta['total_battles'], // battleCount (общее количество битв)
            'wins_percent'       => $winsMeta['wins_percent'],  // процент побед (округлен вверх)
            'wins_percent_text'  => $wins_percent_text          // "XX%"
        ];

        json_ok($resp);

    } catch (Throwable $e) {
        debug_log('FATAL trainercard', ['msg'=>$e->getMessage(), 'file'=>$e->getFile(), 'line'=>$e->getLine()]);
        json_error('FATAL ERROR: '.$e->getMessage());
    }
} break;
    /* --------------------- gift (магазин наборов) --------------------- */
    case 'gift': {
        $gift1List = [];
        $gift23List = [];
        $gift8List = [];

        $gift1 = $mysqli->query("SELECT * FROM `base_gift` WHERE `type` = 1 AND `close` = 0");
        while($gift = $gift1->fetch_assoc()){
            $gift1List[$gift['id']] = ['price' => $gift['price'], 'id' => $gift['id']];
        }
        $gift23 = $mysqli->query("SELECT * FROM `base_gift` WHERE `type` = 23 AND `close` = 0");
        while($gift = $gift23->fetch_assoc()){
            $gift23List[$gift['id']] = ['price' => $gift['price'], 'id' => $gift['id']];
        }
        $gift8 = $mysqli->query("SELECT * FROM `base_gift` WHERE `type` = 8 AND `close` = 0");
        while($gift = $gift8->fetch_assoc()){
            $gift8List[$gift['id']] = ['price' => $gift['price'], 'id' => $gift['id']];
        }

        $response = [
            'gift1List'  => $gift1List,
            'gift23List' => $gift23List,
            'gift8List'  => $gift8List
        ];
    } break;

    /* --------------------- setting --------------------- */
    case 'setting': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $UserQuery = $mysqli->query("SELECT * FROM users WHERE id = ".(int)$_SESSION['id'])->fetch_assoc();
        $server    = $mysqli->query("SELECT * FROM `system` WHERE `id`=1")->fetch_assoc();
        $boss      = $mysqli->query("SELECT * FROM base_boss ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
        $pokemon   = $mysqli->query("SELECT * FROM base_pokemons WHERE id = ".(int)($boss['basenum'] ?? 0))->fetch_assoc();
        $damage    = $mysqli->query("SELECT * FROM users WHERE id = ".(int)$_SESSION['id'])->fetch_assoc();

        // Достижения-умения
        $achiev = $mysqli->query("SELECT * FROM base_achievements WHERE prize != '' ");
        $umen = '';
        while($ach = $achiev->fetch_assoc()){
            $user_ach = $mysqli->query("SELECT * FROM user_achievements WHERE id_ach = ".(int)$ach['id']." AND user_id = ".(int)$_SESSION['id'])->fetch_assoc();
            $user_ach_utility = $mysqli->query("SELECT * FROM user_achievements_utility WHERE utility = ".(int)$ach['id']." AND user = ".(int)$_SESSION['id'])->fetch_assoc();
            if($user_ach){
                if((int)$user_ach['complete'] === 1){
                    $click= 'clickable';
                    $func = 'onclick="utility_activ('.(int)$ach['id'].');"';
                }else{
                    $click= 'hide_displ';
                    $func = '';
                }
            }else{
                $click= 'hide_displ';
                $func = '';
            }
            $activ = ($user_ach_utility ? '' : 'no-active');
            $umen .= '<span class="perk '.$click.' '.$activ.'" '.$func.'><span>'.htmlspecialchars($ach['name']).'</span></span>';
        }

        // Информация о боссе
        if ((int)$boss['death'] !== 1){
            $vs = round(((float)$damage['boss_damage'])/10000000*100,2);
            $text = 'Сейчас по миру ходит босс <span class="bgPok" onclick="openDex('.(int)$boss['basenum'].')"><img src="/img/pokemons/animation/'.numbPok($boss['basenum']).'.png"> <div class="normal-color" style="display: inline-block;">#'.numbPok($boss['basenum']).' '.$pokemon['name'].'</div></span>.<br>
            У него осталось <span class="Red-Color"><b>'.number_format((float)$boss['hp'],0,'.','.').'HP</b></span>!<br>
            Ваш вклад <span class="Green-Color"><b>'.$vs.'%</b></span>!';
        } else {
            if ((int)$boss['prize'] === 1){
                $text = 'Прошлый босс побежден!';
            } else {
                $vs = round(((float)$damage['boss_damage'])/10000000*100,2);
                $text = 'Прошлый босс побежден! Ваш вклад <span class="Green-Color">'.$vs.'</span>!';
            }
        }

        // *_Two флаги (инверсия значения из БД)
        $soundTwo      = ($UserQuery['sound'] == 0 ? 1 : 0);
        $missionTwo    = ($UserQuery['mission_day'] == 0 ? 1 : 0);
        $hotClickTwo   = ($UserQuery['HotClick'] == 0 ? 1 : 0);
        $bossBattleTwo = ($UserQuery['boss_battle'] == 0 ? 1 : 0);

        // Список ваших рефералов
        $my_referals = [];
        $q = $mysqli->query("SELECT login, id FROM users WHERE referal_you = '".$mysqli->real_escape_string($UserQuery['referal'])."'");
        while ($row = $q->fetch_assoc()) $my_referals[] = $row['login'];

        $response = [
            'mail' => $UserQuery['email'],
            'sound' => $UserQuery['sound'],
            'soundTwo' => $soundTwo,
            'mission' => $UserQuery['mission_day'],
            'missionTwo' => $missionTwo,
            'HotClick' => $UserQuery['HotClick'],
            'HotClickTwo' => $hotClickTwo,
            'BossBattle' => $UserQuery['boss_battle'],
            'BossBattleTwo' => $bossBattleTwo,
            'sprite' => (int)$UserQuery['sprite'],
            'promo' => $UserQuery['promo'],
            'referal' => $UserQuery['referal'],
            'referal_you' => $UserQuery['referal_you'],
            'my_referals' => $my_referals,
            'limit_pok' => $UserQuery['limit_pok'],
            'version' => $server['version'],
            'textBoss' => $text,
            'utility' => $umen,
            'attack_lang' => $UserQuery['attack_lang']
        ];
    } break;

    
    /* --------------------- newyear_tree_status --------------------- */
    case 'newyear_tree_status': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $data = ny_call_safe('newyear_tree_status', (int)$_SESSION['id']);
        if (!$data) {
            json_error('Функции ивента не подключены или произошла ошибка.', 1);
        }
        $response = $data;
    } break;

    /* --------------------- newyear_tree_free_action --------------------- */
    case 'newyear_tree_free_action': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $data = ny_call_safe('newyear_tree_free_action', (int)$_SESSION['id']);
        if (!$data) {
            json_error('Функции ивента не подключены или произошла ошибка.', 1);
        }
        // newyear_tree_free_action возвращает уже структурированный массив с error/text
        $response = $data;
    } break;

    /* --------------------- newyear_ping --------------------- */
    case 'newyear_ping': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $data = ny_call_safe('newyear_tree_status', (int)$_SESSION['id']);
        if (!$data) json_error('Функции ивента не подключены или произошла ошибка.', 1);

        // Доп. поля для UX (архив/период)
        $data['last_window'] = ny_evt_last_window();
        $data['snow'] = ny_evt_get_snow((int)$_SESSION['id']);

        $response = $data;
    } break;

    /* --------------------- newyear_event --------------------- */
    case 'newyear_event': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $html = ny_evt_render_hub((int)$_SESSION['id'], ny_is_admin($mysqli));
        $response = ['error'=>0,'html'=>$html];
    } break;

    /* --------------------- newyear_daily_claim --------------------- */
    case 'newyear_daily_claim': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $data = ny_call_safe('newyear_tree_free_action', (int)$_SESSION['id']);
        if (!$data) json_error('Функции ивента не подключены или произошла ошибка.', 1);

        $response = $data;
    } break;

    /* --------------------- newyear_exchange --------------------- */
    case 'newyear_exchange': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $pack = (string)arr_get($_POST, ['pack','kind'], '');
        $response = ny_evt_exchange((int)$_SESSION['id'], $pack);
    } break;

    /* --------------------- newyear_tier_claim --------------------- */
    case 'newyear_tier_claim': {
        if (!isset($_SESSION['id'])) json_error('Не авторизован');

        $tier = (int)($_POST['tier'] ?? 0);
        $response = ny_evt_claim_tier((int)$_SESSION['id'], $tier);
    } break;



    /* --------------------- newyear_admin_status --------------------- */
    case 'newyear_admin_status': {
        if (!ny_is_admin($mysqli)) json_error('Недостаточно прав');

        $sv = ny_call_safe('newyear_get_server');
        $active = ny_call_safe('newyear_is_active');
        if (!$sv) json_error('Не удалось получить состояние ивента');

        $response = [
            'error' => 0,
            'active' => ($active ? 1 : 0),
            'server' => $sv
        ];
    } break;

    /* --------------------- newyear_admin_start --------------------- */
    case 'newyear_admin_start': {
        if (!ny_is_admin($mysqli)) json_error('Недостаточно прав');

        $days = (int)($_POST['days'] ?? 7);
        $data = ny_call_safe('newyear_admin_start', $days);
        if (!$data) json_error('Не удалось запустить ивент');

        $response = $data;
    } break;

    /* --------------------- newyear_admin_stop --------------------- */
    case 'newyear_admin_stop': {
        if (!ny_is_admin($mysqli)) json_error('Недостаточно прав');

        $data = ny_call_safe('newyear_admin_stop');
        if (!$data) json_error('Не удалось остановить ивент');

        $response = $data;
    } break;

/* --------------------- default --------------------- */
    default: {
        json_error('Unknown type');
    }
}

/* ---------- Единый корректный JSON-ответ ---------- */
json_ok($response);

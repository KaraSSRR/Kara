<?php
/**
 * /do/notifications
 * Персональные уведомления пользователя (system + счетчики трейдов/кланов/друзей).
 *
 * Основные исправления:
 * - Совместимость с PHP 5.6 (убраны ?? и return type-hints).
 * - Корректная работа поля checked типа SET('0','1'): сравнения/обновления только строками '0'/'1'.
 * - Разделение поведения: auto-read только при type=load (не при update).
 * - Улучшенная устойчивость (проверки mysqli, обработка ошибок, безопасные ответы).
 * - Небольшие UX-улучшения (empty-state, классы unread, встроенная фильтрация/поиск/действия, если JS отсутствует).
 */

$patch_project = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
$patch_global  = $patch_project . '/inc/conf/global.php';

if ($patch_global && !file_exists($patch_global)) {
    http_response_code(500);
    die('The problem with the connection files.');
}
if ($patch_global) {
    require_once($patch_global);
}

if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
} else {
    // PHP < 5.4 fallback (на всякий случай)
    if (!isset($_SESSION)) { @session_start(); }
}

$type   = isset($_POST['type']) ? (string)$_POST['type'] : '';
$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if ($userId <= 0) {
    http_response_code(403);
    echo '0';
    exit;
}



// Если расширение mysqli не загружено, дальше работать не можем.
if (!class_exists('mysqli')) {
    http_response_code(500);
    echo '0';
    exit;
}

// В разных частях проекта подключение может называться по-разному.
// Пробуем подхватить Work::$sql, если $mysqli не задан.
if (!isset($mysqli) && class_exists('Work') && isset(Work::$sql) && (Work::$sql instanceof mysqli)) {
    $mysqli = Work::$sql;
}
// На некоторых проектах включён strict-режим MySQLi (исключения на ошибки SQL).
// Для этого endpoint лучше мягко деградировать (0/пусто), а не отдавать HTTP 500.
if (function_exists('mysqli_report')) { @mysqli_report(MYSQLI_REPORT_OFF); }
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo '0';
    exit;
}

/* ---------- helpers ---------- */
function send_json($data) {
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function send_html($html) {
    if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function safe_notification_html($html) {
    // Базовая защита от XSS, но оставляем форматирование (b/strong/i/em/span/br/a).
    // Если в вашем проекте текст 100% доверенный, можно заменить на return $html;
    $allowed = '<b><strong><i><em><u><span><br><a>';
    $html = strip_tags((string)$html, $allowed);

    // Убираем потенциально опасные атрибуты, оставляем href у <a>
    // 1) убираем on*=
    $html = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/iu', '', $html);
    // 2) очищаем javascript: в href
    $html = preg_replace_callback('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>/iu', function($m){
        $q = $m[1];
        $href = trim($m[2]);
        if (preg_match('/^\s*javascript:/i', $href)) { $href = '#'; }
        // Пересобираем тег: оставим только href + target/rel (если были)
        $tag = $m[0];
        $target = (preg_match('/\btarget\s*=\s*(["\']).*?\1/iu', $tag, $mt)) ? $mt[0] : '';
        $rel    = (preg_match('/\brel\s*=\s*(["\']).*?\1/iu', $tag, $mr)) ? $mr[0] : '';
        $title  = (preg_match('/\btitle\s*=\s*(["\']).*?\1/iu', $tag, $mti)) ? $mti[0] : '';
        return '<a href=' . $q . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . $q . ' ' . $target . ' ' . $rel . ' ' . $title . '>';
    }, $html);

    return $html;
}

function relTimeFromRow($row) {
    $ts = 0;

    if (!empty($row['created_at']) && $row['created_at'] !== '0000-00-00 00:00:00') {
        $ts = strtotime($row['created_at']);
    }

    if (!$ts) {
        if (!empty($row['date'])) {
            $months = array('Января'=>1,'Февраля'=>2,'Марта'=>3,'Апреля'=>4,'Мая'=>5,'Июня'=>6,'Июля'=>7,'Августа'=>8,'Сентября'=>9,'Октября'=>10,'Ноября'=>11,'Декабря'=>12);
            if (preg_match('~(\d{1,2})\s+([А-Яа-я]+)\s+(\d{4}).*?(\d{1,2}):(\d{2})~u', $row['date'], $m)) {
                $d=(int)$m[1]; $M=isset($months[$m[2]]) ? $months[$m[2]] : 1; $y=(int)$m[3]; $H=(int)$m[4]; $i=(int)$m[5];
                $ts = strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $M, $d, $H, $i));
            } else {
                $tmp = strtotime($row['date']);
                $ts = $tmp ? $tmp : time();
            }
        } else {
            $ts = time();
        }
    }

    $diff = time() - $ts;
    if ($diff < 60) return 'только что';
    $m = floor($diff/60); if ($m < 60) return $m . ' мин. назад';
    $h = floor($m/60);    if ($h < 24) return $h . ' ч. назад';
    $d = floor($h/24);    return $d . ' дн. назад';
}

function notifyCountClass($n) { return ($n > 0) ? 'notify-count active' : 'notify-count'; }

function stmt_count($mysqli, $sql, $types, $params) {
    $res = 0;
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return 0;

    // bind_param с переменным числом аргументов (MySQLi требует ссылки)
    $bind = array($types);
    for ($i = 0; $i < count($params); $i++) { $bind[] = &$params[$i]; }
    call_user_func_array(array($stmt, 'bind_param'), $bind);

    if ($stmt->execute()) {
        $stmt->bind_result($c);
        if ($stmt->fetch()) { $res = (int)$c; }
    }
    $stmt->close();
    return $res;
}
/* ---------- counters ---------- */
$count_trades  = stmt_count($mysqli, "SELECT COUNT(*) c FROM users_trade WHERE user2=? AND status=0", "i", array($userId));
$count_clans   = stmt_count($mysqli, "SELECT COUNT(*) c FROM user_clan_accept WHERE user_id=? AND status=0", "i", array($userId));
$count_friends = stmt_count($mysqli, "SELECT COUNT(*) c FROM users_friend WHERE friend_id=? AND status=0", "i", array($userId));

// ВАЖНО: checked — SET('0','1'), поэтому сравнение строго со строками!
$count_notifications = stmt_count($mysqli, "SELECT COUNT(*) c FROM notification WHERE user=? AND checked='0'", "i", array($userId));

/* ---------- routing ---------- */
switch ($type) {

    /* ========== LIST (HTML) ========== */
    case 'load':
    case 'update':
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 150;
        if ($limit <= 0) $limit = 150;
        if ($limit > 200) $limit = 200;

        $totalAll = $count_trades + $count_clans + $count_friends + $count_notifications;
        $endpoint = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/do/notifications';

        $notes  = '<div id="notifyRoot" class="notify-root" data-endpoint="' . htmlspecialchars($endpoint, ENT_QUOTES, 'UTF-8') . '">';
        $notes .= '  <div class="notify-panel">';
        $notes .= '    <div class="notify-filters-grid">';
        $notes .= '      <button class="notify-filter-btn active" type="button" onclick="notifyFilter(\'all\',this)">Все <span class="' . notifyCountClass($totalAll) . '">' . $totalAll . '</span></button>';
        $notes .= '      <button class="notify-filter-btn" type="button" onclick="notifyFilter(\'trade\',this)">Трейды <span class="' . notifyCountClass($count_trades) . '">' . $count_trades . '</span></button>';
        $notes .= '      <button class="notify-filter-btn" type="button" onclick="notifyFilter(\'clan\',this)">Кланы <span class="' . notifyCountClass($count_clans) . '">' . $count_clans . '</span></button>';
        $notes .= '      <button class="notify-filter-btn" type="button" onclick="notifyFilter(\'friend\',this)">Друзья <span class="' . notifyCountClass($count_friends) . '">' . $count_friends . '</span></button>';
        $notes .= '      <button class="notify-filter-btn" type="button" onclick="notifyFilter(\'system\',this)">Системные <span class="' . notifyCountClass($count_notifications) . '">' . $count_notifications . '</span></button>';
        $notes .= '      <button class="notify-filter-btn" type="button" onclick="notifyFilter(\'new\',this)">Только новые</button>';
        $notes .= '      <span></span><span></span><span></span>';
        $notes .= '    </div>';
        $notes .= '    <div class="notify-actions">';
        $notes .= '      <input class="notify-search" type="text" placeholder="Поиск..." oninput="notifySearch()" id="notifySearchInput" autocomplete="off">';
        $notes .= '      <button id="clearNotifications" class="clear-btn" type="button" onclick="clearNotifications()" title="Очистить системные уведомления"><i class="fas fa-broom"></i></button>';
        $notes .= '      <button class="read-btn" type="button" onclick="notifyMarkAllRead()" title="Отметить системные уведомления как прочитанные">✓</button>';
        $notes .= '    </div>';
        $notes .= '  </div>';

        $notes .= '  <div class="notify-list" id="notifyList">';

        $notes .= '    <style id="notify-extra-css">'
               . '/* modern additions — scoped to notifications panel/popover */'
               . '#notifyRoot .notify-badge, .LittleModal.notify-pop .notify-badge{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:900;letter-spacing:.2px;border:1px solid rgba(0,0,0,.08);background:rgba(0,0,0,.03);color:rgba(0,0,0,.75)}'
               . '#notifyRoot .notify-badge.friend, .LittleModal.notify-pop .notify-badge.friend{background:rgba(91,108,255,.10);border-color:rgba(91,108,255,.22);color:var(--np-ac,#5b6cff)}'
               . '#notifyRoot .notify-badge.trade, .LittleModal.notify-pop .notify-badge.trade{background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.22);color:rgba(34,197,94,.95)}'
               . '#notifyRoot .notify-badge.clan, .LittleModal.notify-pop .notify-badge.clan{background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.25);color:rgba(180,83,9,.95)}'
               . '#notifyRoot .notify-badge.system, .LittleModal.notify-pop .notify-badge.system{background:rgba(148,163,184,.18);border-color:rgba(148,163,184,.30);color:rgba(51,65,85,.95)}'
               . '#notifyRoot .DivNotify.unread, .LittleModal.notify-pop .DivNotify.unread{border-color:rgba(91,108,255,.30);box-shadow:0 0 0 2px rgba(91,108,255,.10)}'
               . '#notifyRoot .notify-dot, .LittleModal.notify-pop .notify-dot{display:inline-block;width:8px;height:8px;border-radius:999px;background:var(--np-ac,#5b6cff);box-shadow:0 0 0 3px rgba(91,108,255,.12);margin-left:8px;vertical-align:middle}'
               . '#notifyRoot .notify-user, .LittleModal.notify-pop .notify-user{color:inherit;text-decoration:none;font-weight:900;border-bottom:1px dashed rgba(91,108,255,.35)}'
               . '#notifyRoot .notify-user:hover, .LittleModal.notify-pop .notify-user:hover{border-bottom-color:rgba(91,108,255,.85)}'
               . '#notifyRoot .notify-ctas, .LittleModal.notify-pop .notify-ctas{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}'
               . '#notifyRoot .notify-action, .LittleModal.notify-pop .notify-action{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:12px;border:1px solid var(--np-br,#e7ecff);background:var(--np-chip,#eef3ff);color:var(--np-t,#1b2b4f) !important;-webkit-text-fill-color:var(--np-t,#1b2b4f) !important;font-size:12px !important;font-weight:900;line-height:1.2;text-indent:0 !important;letter-spacing:0 !important;white-space:nowrap;text-decoration:none !important;cursor:pointer;user-select:none;transition:transform .12s ease, box-shadow .12s ease, filter .12s ease}'
               . '#notifyRoot .notify-action:hover, .LittleModal.notify-pop .notify-action:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(15,23,42,.10);filter:saturate(1.05)}'
               . '#notifyRoot .notify-action:active, .LittleModal.notify-pop .notify-action:active{transform:translateY(0)}'
               . '#notifyRoot .notify-action.accept, .LittleModal.notify-pop .notify-action.accept{background:rgba(91,108,255,.12);border-color:rgba(91,108,255,.28);color:var(--np-ac,#5b6cff) !important;-webkit-text-fill-color:var(--np-ac,#5b6cff) !important}'
               . '#notifyRoot .notify-action.decline, .LittleModal.notify-pop .notify-action.decline{background:rgba(255,77,79,.12);border-color:rgba(255,77,79,.26);color:#ff4d4f !important;-webkit-text-fill-color:#ff4d4f !important;opacity:.95}'
               . '#notifyRoot .notify-action.busy, .LittleModal.notify-pop .notify-action.busy{pointer-events:none;opacity:.55;filter:grayscale(.2)}'
               . '#notifyRoot .notify-action .ico, .LittleModal.notify-pop .notify-action .ico{font-size:13px;line-height:1}'
               . '</style>';


        $hasAnyGlobal = false;
        $hadError = false;

        /* ====== TRADE (pending) ====== */
        $qTrade = $mysqli->query("SELECT * FROM users_trade WHERE user2=" . (int)$userId . " AND status=0 ORDER BY id DESC LIMIT 50");
        if ($qTrade) {
            while ($t = $qTrade->fetch_assoc()) {
                $hasAnyGlobal = true;

                $from = 0;
                foreach (array('user1','user_id','user','from_user','sender_id','from_id') as $k) {
                    if (isset($t[$k])) { $from = (int)$t[$k]; break; }
                }
                $tid = isset($t['id']) ? (int)$t['id'] : 0;

                $when = relTimeFromRow($t);
                $msg  = ($from > 0) ? ('Запрос на трейд от игрока <b>#' . $from . '</b>') : 'Новый запрос на трейд';
                if ($tid > 0) { $msg .= ' <span class="notify-meta">(ID ' . $tid . ')</span>'; }

                $notes .= '<div class="DivNotify notify-type-trade unread" data-type="trade" data-checked="0" data-id="' . $tid . '">';
                $notes .= '  <img src="/img/avatars/mini/0.png" alt="" loading="lazy">';
                $notes .= '  <div class="Text">';
                $notes .= '    <div class="Words"><span class="notify-badge trade">Трейд</span> ' . $msg . '</div>';
                $notes .= '    <div class="Data">' . htmlspecialchars($when, ENT_QUOTES, 'UTF-8') . '</div>';
                $notes .= '  </div>';
                $notes .= '</div>';
            }
        }

        /* ====== CLAN (pending) ====== */
        $qClan = $mysqli->query("SELECT * FROM user_clan_accept WHERE user_id=" . (int)$userId . " AND status=0 ORDER BY id DESC LIMIT 50");
        if ($qClan) {
            while ($c = $qClan->fetch_assoc()) {
                $hasAnyGlobal = true;

                $cid = isset($c['id']) ? (int)$c['id'] : 0;
                $clanId = 0;
                foreach (array('clan_id','clan','id_clan') as $k) {
                    if (isset($c[$k])) { $clanId = (int)$c[$k]; break; }
                }

                $when = relTimeFromRow($c);
                $msg  = 'Приглашение в клан';
                if ($clanId > 0) { $msg .= ' <b>#' . $clanId . '</b>'; }
                if ($cid > 0) { $msg .= ' <span class="notify-meta">(ID ' . $cid . ')</span>'; }

                $notes .= '<div class="DivNotify notify-type-clan unread" data-type="clan" data-checked="0" data-id="' . $cid . '">';
                $notes .= '  <img src="/img/avatars/mini/0.png" alt="" loading="lazy">';
                $notes .= '  <div class="Text">';
                $notes .= '    <div class="Words"><span class="notify-badge clan">Клан</span> ' . $msg . '</div>';
                $notes .= '    <div class="Data">' . htmlspecialchars($when, ENT_QUOTES, 'UTF-8') . '</div>';
                $notes .= '  </div>';
                $notes .= '</div>';
            }
        }

        /* ====== FRIEND (pending) ====== */
        $qFriend = $mysqli->query("SELECT * FROM users_friend WHERE friend_id=" . (int)$userId . " AND status=0 ORDER BY id DESC LIMIT 50");
        if ($qFriend) {
            while ($f = $qFriend->fetch_assoc()) {
                $hasAnyGlobal = true;

                $fid = isset($f['id']) ? (int)$f['id'] : 0;
                $from = 0;
                foreach (array('user_id','user','from_user','sender_id') as $k) {
                    if (isset($f[$k])) { $from = (int)$f[$k]; break; }
                }

                $when = relTimeFromRow($f);
                $ava = '/img/avatars/mini/' . (($from > 0) ? $from : 0) . '.png';
                $avaEsc = htmlspecialchars($ava, ENT_QUOTES, 'UTF-8');
                $msg  = ($from > 0) ? ('Заявка в друзья от игрока <a href="javascript:void(0)" class="notify-user" onclick="if(typeof showUserTooltip===\'function\'){showUserTooltip(' . $from . ');}return false;"><b>#' . $from . '</b></a>') : 'Новая заявка в друзья';
                if ($fid > 0) { $msg .= ' <span class="notify-meta">(ID ' . $fid . ')</span>'; }

                $notes .= '<div class="DivNotify notify-type-friend unread" data-type="friend" data-checked="0" data-id="' . $fid . '">';
                $notes .= '  <img src="' . $avaEsc . '" alt="" loading="lazy" onerror="this.onerror=null;this.src=\'/img/avatars/mini/0.png\';">';
                $notes .= '  <div class="Text">';
                $notes .= '    <div class="Words"><span class="notify-badge friend">Друг</span><span class="notify-dot"></span> ' . $msg . '</div>';
                $notes .= '    <div class="Data">' . htmlspecialchars($when, ENT_QUOTES, 'UTF-8') . '</div>';
                $notes .= '    <div class="notify-ctas">';
                $notes .= '      <a href="javascript:void(0)" class="notify-action accept" '
                       . 'onclick="(function(a){var $=window.jQuery;if(!$)return false;var $a=$(a);if($a.hasClass(\'busy\'))return false;$a.addClass(\'busy\');'
                       . '$.post(\'/do/notifications\',{type:\'friend_accept\',id:' . $fid . '},function(res){'
                       . 'if(res&&((res.error===0)||(res.error===\'0\'))){'
                       . 'try{$a.closest(\'.DivNotify\').fadeOut(160,function(){$(this).remove();});}catch(e){}'
                       . 'try{if(window.Game&&Game.notifications&&Game.notifications.main){Game.notifications.main(res.text||\'Заявка принята\',\'success\');}}catch(e){}'
                       . 'try{if(window.Game&&Game.notifications&&Game.notifications.count){Game.notifications.count();}}catch(e){}'
                       . '}else{'
                       . 'try{if(window.Game&&Game.notifications&&Game.notifications.main){Game.notifications.main((res&&res.text)||\'Ошибка\',\'error\');}}catch(e){}'
                       . '}'
                       . '},\'json\').always(function(){try{$a.removeClass(\'busy\');}catch(e){}});'
                       . '})(this);return false;"><span class="ico">✓</span><span class="txt">Принять</span></a>';
                $notes .= '      <a href="javascript:void(0)" class="notify-action decline" '
                       . 'onclick="(function(a){var $=window.jQuery;if(!$)return false;var $a=$(a);if($a.hasClass(\'busy\'))return false;$a.addClass(\'busy\');'
                       . '$.post(\'/do/notifications\',{type:\'friend_decline\',id:' . $fid . '},function(res){'
                       . 'if(res&&((res.error===0)||(res.error===\'0\'))){'
                       . 'try{$a.closest(\'.DivNotify\').fadeOut(160,function(){$(this).remove();});}catch(e){}'
                       . 'try{if(window.Game&&Game.notifications&&Game.notifications.main){Game.notifications.main(res.text||\'Заявка отклонена\',\'info\');}}catch(e){}'
                       . 'try{if(window.Game&&Game.notifications&&Game.notifications.count){Game.notifications.count();}}catch(e){}'
                       . '}else{'
                       . 'try{if(window.Game&&Game.notifications&&Game.notifications.main){Game.notifications.main((res&&res.text)||\'Ошибка\',\'error\');}}catch(e){}'
                       . '}'
                       . '},\'json\').always(function(){try{$a.removeClass(\'busy\');}catch(e){}});'
                       . '})(this);return false;"><span class="ico">✕</span><span class="txt">Отклонить</span></a>';
                $notes .= '    </div>';
                $notes .= '  </div>';
                $notes .= '</div>';
            }
        }


        // SYSTEM notifications
        $stmt = $mysqli->prepare("SELECT id, text, user, img, checked, date, created_at FROM notification WHERE user=? ORDER BY id DESC LIMIT ?");
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $limit);
            if ($stmt->execute()) {
                $stmt->bind_result($nid, $ntext, $nuser, $nimg, $nchecked, $ndate, $ncreated_at);

                $hasAny = false;
                while ($stmt->fetch()) {
                    $hasAny = true;
                    $hasAnyGlobal = true;

                    $checked = ($nchecked === '1') ? 1 : 0;
                    $when    = relTimeFromRow(array('created_at' => $ncreated_at, 'date' => $ndate));

                    // GIF покемона по шаблону #ID: 3-значное дополнение нулями
                    $thumb = (string)$nimg;
                    if (preg_match('/#(\d{1,4})\b/u', (string)$ntext, $m)) {
                        $pid   = (int)$m[1];
                        $fname = sprintf('%03d', $pid);
                        $thumb = "/img/pokemons/animation/{$fname}.gif";
                    }
                    if ($thumb === '') $thumb = '/img/avatars/mini/0.png';
                    $thumbEsc = htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8');

                    $textHtml = safe_notification_html((string)$ntext);

                    $notes .= '<div class="DivNotify notify-type-system' . ($checked==='0' ? ' unread' : '') . '" data-type="system" data-checked="' . $checked . '" data-id="' . (int)$nid . '">';
                    $notes .= '  <img src="' . $thumbEsc . '" alt="" loading="lazy" onerror="this.onerror=null;this.src=\'/img/avatars/mini/0.png\';">';
                    $notes .= '  <div class="Text">';
                    $notes .= '    <div class="Words"><span class="notify-badge system">Система</span>' . ($checked==='0' ? '<span class="notify-dot"></span>' : '') . ' ' . $textHtml . '</div>';
                    $notes .= '    <div class="Data">' . htmlspecialchars($when, ENT_QUOTES, 'UTF-8') . '</div>';
                    $notes .= '  </div>';
                    $notes .= '  <button class="notify-del" type="button" title="Удалить" onclick="notifyDeleteOne(' . (int)$nid . ')">×</button>';
                    $notes .= '</div>';
                }
            }
            $stmt->close();
        } else {
            $hadError = true;
            $notes .= '<div class="notify-empty"><div class="notify-empty-title">Ошибка загрузки</div><div class="notify-empty-sub">Не удалось подготовить запрос к базе данных.</div></div>';
        }

        if (!$hasAnyGlobal && !$hadError) {
            $notes .= '<div class="notify-empty" data-type="system"><div class="notify-empty-title">Нет уведомлений</div><div class="notify-empty-sub">Пока здесь пусто. Как только появятся новые события — они будут отображаться в этом списке.</div></div>';
        }

        $notes .= '  </div>'; // notify-list

        // auto-read только при реальном открытии (load), а не при фоновых апдейтах (update)
        if ($type === 'load') {
            $stmt = $mysqli->prepare("UPDATE notification SET checked='1' WHERE user=? AND checked='0'");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Встроенные стили/скрипты — только если их нет (идемпотентно).
        $notes .= '
<style id="notify-inline-style">
/* минимальные улучшения визуала, безопасно заскоуплено */
.notify-root{display:block}
.notify-panel{display:flex;flex-direction:column;gap:10px;margin-bottom:10px}
.notify-filters-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;align-items:center}
.notify-filter-btn{cursor:pointer;border:1px solid rgba(0,0,0,.08);border-radius:10px;padding:8px 10px;background:#fff;font-size:13px;line-height:1.1}
.notify-filter-btn.active{border-color:rgba(0,0,0,.22);box-shadow:0 1px 6px rgba(0,0,0,.06)}
.notify-count{display:inline-block;margin-left:6px;padding:2px 7px;border-radius:999px;background:rgba(0,0,0,.06)}
.notify-count.active{background:rgba(0,0,0,.12)}
.notify-actions{display:flex;gap:8px;align-items:center}
.notify-search{flex:1;min-width:120px;border:1px solid rgba(0,0,0,.12);border-radius:10px;padding:8px 10px;outline:none}
.clear-btn,.read-btn{border:1px solid rgba(0,0,0,.12);border-radius:10px;padding:8px 10px;background:#fff;cursor:pointer}
.notify-list{display:flex;flex-direction:column;gap:8px}
.DivNotify{display:flex;gap:10px;align-items:flex-start;padding:10px;border:1px solid rgba(0,0,0,.08);border-radius:14px;background:#fff;position:relative}
.DivNotify.unread{border-color:rgba(0,0,0,.18)}
.DivNotify img{width:44px;height:44px;object-fit:contain;border-radius:10px;background:rgba(0,0,0,.03)}
.DivNotify .Text{flex:1;min-width:0}
.DivNotify .Words{font-size:14px;line-height:1.25;word-wrap:break-word}
.DivNotify .Data{margin-top:4px;font-size:12px;opacity:.7}
.DivNotify .notify-ctas button{color:#111 !important;font-size:12px !important;line-height:1.2 !important;font-weight:600 !important;text-transform:none !important;}
.notify-badge{display:inline-block;vertical-align:middle;margin-right:8px;padding:2px 8px;border-radius:999px;background:rgba(0,0,0,.06);font-size:12px;line-height:18px}
.notify-badge.trade{background:rgba(0,0,0,.08)}
.notify-badge.clan{background:rgba(0,0,0,.08)}
.notify-badge.friend{background:rgba(0,0,0,.08)}
.notify-meta{opacity:.7;font-size:12px;margin-left:6px}
.notify-del{position:absolute;top:6px;right:8px;border:0;background:transparent;cursor:pointer;font-size:18px;line-height:1;opacity:.55}
.notify-del:hover{opacity:1}
.notify-empty{padding:18px;border:1px dashed rgba(0,0,0,.12);border-radius:14px;background:rgba(0,0,0,.02)}
.notify-empty-title{font-weight:600;margin-bottom:4px}
.notify-empty-sub{opacity:.75;font-size:13px}
@media (max-width: 700px){.notify-filters-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<script id="notify-inline-script">
(function(){
  // Идемпотентно: не дублируем стили/скрипты при повторных подгрузках
  var style = document.getElementById("notify-inline-style");
  if (style && style.__notify_applied) { /* ok */ } else if (style) { style.__notify_applied = true; }

  function root(){ return document.getElementById("notifyRoot"); }
  function endpoint(){
    var r = root();
    if (!r) return "/do/notifications";
    return r.getAttribute("data-endpoint") || "/do/notifications";
  }

  function applyFilterAndSearch(){
    var r = root();
    if (!r) return;
    var list = document.getElementById("notifyList");
    if (!list) return;

    var f = window.__notifyFilter || "all";
    var q = (document.getElementById("notifySearchInput") ? document.getElementById("notifySearchInput").value : "");
    q = (q || "").toLowerCase();

    var items = list.querySelectorAll(".DivNotify, .notify-empty");
    for (var i=0; i<items.length; i++){
      var it = items[i];
      var type = it.getAttribute("data-type") || "system";
      var checked = it.getAttribute("data-checked") || "1";

      var okType = (f === "all") || (f === type) || (f === "new" && checked === "0");
      var text = (it.textContent || "").toLowerCase();
      var okSearch = (!q) || (text.indexOf(q) !== -1);

      it.style.display = (okType && okSearch) ? "" : "none";
    }
  }

  if (!window.notifyFilter){
    window.notifyFilter = function(type, btn){
      window.__notifyFilter = type;
      var grid = btn && btn.parentNode ? btn.parentNode : null;
      if (grid){
        var btns = grid.querySelectorAll(".notify-filter-btn");
        for (var i=0;i<btns.length;i++){ btns[i].classList.remove("active"); }
        btn.classList.add("active");
      }
      applyFilterAndSearch();
    };
  }

  if (!window.notifySearch){
    window.notifySearch = function(){ applyFilterAndSearch(); };
  }

  function post(data, cb){
    try{
      var xhr = new XMLHttpRequest();
      xhr.open("POST", endpoint(), true);
      xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
      xhr.onreadystatechange = function(){
        if (xhr.readyState === 4){
          var res = null;
          try{ res = JSON.parse(xhr.responseText); }catch(e){}
          cb && cb(xhr.status, res, xhr.responseText);
        }
      };
      var parts = [];
      for (var k in data){
        if (!data.hasOwnProperty(k)) continue;
        parts.push(encodeURIComponent(k) + "=" + encodeURIComponent(data[k]));
      }
      xhr.send(parts.join("&"));
    }catch(e){
      cb && cb(0, null, "");
    }
  }

  if (!window.clearNotifications){
    window.clearNotifications = function(){
      post({type:"clear_notifications"}, function(code, res){
        if (!res || res.error){ return; }
        // перезагрузим список
        post({type:"update"}, function(code2, res2, html){
          // update возвращает HTML, не JSON — просто подменяем контейнер
          var r = root();
          if (r && typeof html === "string" && html.indexOf("notify-root") !== -1){
            r.outerHTML = html;
          }
        });
      });
    };
  }

  if (!window.notifyMarkAllRead){
    window.notifyMarkAllRead = function(){
      post({type:"mark_all_read"}, function(code, res){
        // визуально снимем unread
        var list = document.getElementById("notifyList");
        if (!list) return;
        var items = list.querySelectorAll(".DivNotify[data-type=\"system\"]");
        for (var i=0;i<items.length;i++){
          items[i].setAttribute("data-checked","1");
          items[i].classList.remove("unread");
        }
        applyFilterAndSearch();
      });
    };
  }

  if (!window.notifyDeleteOne){
    window.notifyDeleteOne = function(id){
      id = parseInt(id,10);
      if (!id) return;
      post({type:"delete_one", id:id}, function(code, res){
        if (!res || res.error){ return; }
        var el = document.querySelector(".DivNotify[data-id=\""+id+"\"]");
        if (el && el.parentNode) el.parentNode.removeChild(el);
        applyFilterAndSearch();
      });
    };
  }

  // применяем фильтр/поиск при первой отрисовке
  applyFilterAndSearch();
})();
</script>';

        $notes .= '</div>'; // notify-root

        send_html($notes);

    /* ========== COUNTS (JSON) ========== */
    case 'count':
        // Важно: фронтенд (Game.notifications.count в world.js) ожидает ПЛОСКОЕ число, не JSON.
        if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
        echo (string)(int)($count_notifications + $count_friends + $count_trades + $count_clans);
        exit;

    
    /* ========== FRIEND ACCEPT/DECLINE (JSON) ========== */
    case 'friend_accept':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) send_json(array('error' => 1, 'text' => 'Некорректный запрос'));

        $stmt = $mysqli->prepare("UPDATE `users_friend` SET `status` = 1 WHERE `id` = ? AND `friend_id` = ? AND `status` = 0");
        if (!$stmt) send_json(array('error' => 1, 'text' => 'DB error'));
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $aff = (int)$stmt->affected_rows;
        $stmt->close();

        if ($aff > 0) {
            send_json(array('error' => 0, 'text' => 'Заявка в друзья принята.'));
        }

        // если заявка уже принята или удалена — вернём понятный ответ
        $stmt = $mysqli->prepare("SELECT `status` FROM `users_friend` WHERE `id` = ? AND `friend_id` = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $userId);
            $stmt->execute();
            $stmt->bind_result($st);
            if ($stmt->fetch()) {
                $stmt->close();
                if ((int)$st === 1) send_json(array('error' => 0, 'text' => 'Заявка уже принята.'));
                send_json(array('error' => 1, 'text' => 'Заявка недоступна.'));
            }
            $stmt->close();
        }
        send_json(array('error' => 1, 'text' => 'Заявка не найдена.'));

    case 'friend_decline':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) send_json(array('error' => 1, 'text' => 'Некорректный запрос'));

        $stmt = $mysqli->prepare("DELETE FROM `users_friend` WHERE `id` = ? AND `friend_id` = ? AND `status` = 0");
        if (!$stmt) send_json(array('error' => 1, 'text' => 'DB error'));
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $aff = (int)$stmt->affected_rows;
        $stmt->close();

        if ($aff > 0) {
            send_json(array('error' => 0, 'text' => 'Заявка в друзья отклонена.'));
        }

        // если записи уже нет — считаем успехом, чтобы не раздражать пользователя
        $stmt = $mysqli->prepare("SELECT `id` FROM `users_friend` WHERE `id` = ? AND `friend_id` = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $userId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $stmt->close();
                send_json(array('error' => 0, 'text' => 'Заявка уже обработана.'));
            }
            $stmt->close();
        }

        send_json(array('error' => 1, 'text' => 'Заявка не найдена.'));

/* ========== MARK ALL READ (JSON) ========== */
    case 'mark_all_read':
        $stmt = $mysqli->prepare("UPDATE notification SET checked='1' WHERE user=? AND checked='0'");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
        send_json(array('success' => 'Все системные уведомления отмечены как прочитанные!'));

    /* ========== DELETE ONE (JSON) ========== */
    case 'delete_one':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) send_json(array('error' => 'Bad id'));

        $stmt = $mysqli->prepare("DELETE FROM notification WHERE id=? AND user=?");
        if (!$stmt) send_json(array('error' => 'DB error: prepare failed'));
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $removed = $stmt->affected_rows;
        $stmt->close();

        send_json(array('success' => 'Удалено', 'removed' => (int)$removed));

    /* ========== CLEAR ALL (JSON) ========== */
    case 'clear_notifications':
        $removed = 0;
        $stmt = $mysqli->prepare("DELETE FROM notification WHERE user=?");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $removed = (int)$stmt->affected_rows;
            $stmt->close();
        } else {
            send_json(array('error' => 'DB error: prepare failed'));
        }

        send_json(array(
            'success' => 'Системные уведомления очищены!',
            'removed' => $removed,
            'counts'  => array(
                'notifications' => 0,
                'friends'       => $count_friends,
                'trades'        => $count_trades,
                'clans'         => $count_clans,
                'total_unread'  => $count_friends + $count_trades + $count_clans
            )
        ));

    default:
        echo '0';
        exit;
}
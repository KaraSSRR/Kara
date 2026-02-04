<?php
/**
 * /do/clanAction.php
 *
 * Современный функционал кланов (backend).
 *
 * Принципы:
 * - Все операции (создание/управление/банк/склад/магазин/заявки/фракции/локации/квесты) доступны через этот файл.
 * - Максимальная безопасность под MySQL 5.7: транзакции, блокировки FOR UPDATE, подготовленные выражения.
 * - PHP 5.6+: без null-coalescing (??), без Throwable, без типизации.
 *
 * Совместимость с существующим фронтом (world.js):
 * - object: 'clanCard,<id>'
 * - object: 'addMoney,<count>' / 'minusMoney,<count>' / 'left'
 * - object: 'clanCardControl,<id>' возвращает HTML управления
 * - object: 'goLeaderClan'/'goUnleaderClan'/'goDeleteClan'/'goStatusClan'/'goNotifyClan' с POST-полями name/other
 * - object: 'openStorage' (POST: clan_id), 'getUserItemsForClanStorage', 'clanStorageAdd', 'clanStorageTake'
 * - object: 'shopList' (POST: clan_id), 'shopBuy' (POST: clan_id,catalog_id,amount)
 */

// -------------------------
// Bootstrap
// -------------------------
if (function_exists('session_status')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
} else {
    // PHP < 5.4
    if (!isset($_SESSION)) {
        @session_start();
    }
}

header('Content-Type: application/json; charset=utf-8');

$patch_project = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
$patch_global  = $patch_project . '/inc/conf/global.php';
if (!is_file($patch_global)) {
    echo json_encode(array('error'=>1,'success'=>false,'text'=>'Ошибка: не найден inc/conf/global.php'), JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $patch_global;

// Включаем исключения mysqli для корректного try/catch (локально для этого запроса)
if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// -------------------------
// DB handle
// -------------------------
$db = null;

// 1) Work::$sql (если в проекте используется такой контейнер)
if (class_exists('Work')) {
    // не обращаемся к Work::$sql напрямую без проверок (чтобы не ловить fatals)
    try {
        if (property_exists('Work', 'sql') && isset(Work::$sql) && Work::$sql instanceof mysqli) {
            $db = Work::$sql;
        }
    } catch (Exception $e) {
        // игнор
    }
}

// 2) глобальный $mysqli (из connect.php)
if (!$db) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $db = $mysqli;
    }
}

if (!$db) {
    echo json_encode(array('error'=>1,'success'=>false,'text'=>'DB: соединение не найдено.'), JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------
// Auth
// -------------------------
$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($userId <= 0) {
    echo json_encode(array('error'=>1,'success'=>false,'text'=>'Требуется авторизация.'), JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------
// Helpers
// -------------------------
function respond($ok, $text, $extra) {
    if (!is_array($extra)) $extra = array();
    $payload = array_merge(array(
        'error'   => $ok ? 0 : 1,
        'success' => $ok ? true : false,
        'status'  => $ok ? 'success' : 'error',
        'text'    => (string)$text,
        'notify'  => (string)$text,
    ), $extra);

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function pStr($key, $def) {
    if (!isset($_POST[$key])) return $def;
    return trim((string)$_POST[$key]);
}
function pInt($key, $def) {
    if (!isset($_POST[$key])) return $def;
    return (int)$_POST[$key];
}

function clearName($s) {
    $s = trim((string)$s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s;
}

function fetchOne($db, $sql, $types, $params) {
    if ($types === null) $types = '';
    if (!is_array($params)) $params = array();
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    if ($types !== '' && count($params) > 0) {
        // bind_param требует ссылки
        $bind = array();
        $bind[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind[] = &$params[$i];
        }
        call_user_func_array(array($stmt,'bind_param'), $bind);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? $row : null;
}

function fetchAll($db, $sql, $types, $params) {
    if ($types === null) $types = '';
    if (!is_array($params)) $params = array();
    $stmt = $db->prepare($sql);
    if (!$stmt) return array();
    if ($types !== '' && count($params) > 0) {
        $bind = array();
        $bind[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind[] = &$params[$i];
        }
        call_user_func_array(array($stmt,'bind_param'), $bind);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    $stmt->close();
    return $rows;
}

function tableExists($db, $name) {
    $row = fetchOne($db, "SELECT 1 AS ok FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1", 's', array($name));
    return $row ? true : false;
}

function columnExists($db, $table, $column) {
    $row = fetchOne($db, "SELECT 1 AS ok FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=? LIMIT 1", 'ss', array($table, $column));
    return $row ? true : false;
}

function nowHuman($ts) {
    if (!$ts) return date('d.m H:i');
    $t = strtotime($ts);
    if (!$t) return date('d.m H:i');
    return date('d.m H:i', $t);
}

// -------------------------
// Schema guard
// -------------------------
function requireClanSchema($db) {
    // Базовые таблицы v2
    if (!tableExists($db, 'clans') || !tableExists($db, 'clan_members')) {
        respond(false, 'Не установлена схема кланов (таблицы clans/clan_members отсутствуют). Запустите SQL-миграцию кланов.', array());
    }
}

// -------------------------
// Inventory adapter (items_users)
// -------------------------
function invDetect($db) {
    static $cache = null;
    if ($cache !== null) return $cache;

    // user column
    $userCol = null;
    if (columnExists($db, 'items_users', 'user')) $userCol = 'user';
    else if (columnExists($db, 'items_users', 'user_id')) $userCol = 'user_id';

    // qty column
    $qtyCol = null;
    if (columnExists($db, 'items_users', 'amount')) $qtyCol = 'amount';
    else if (columnExists($db, 'items_users', 'count')) $qtyCol = 'count';

    $cache = array('user_col'=>$userCol, 'qty_col'=>$qtyCol);
    return $cache;
}

function invMoneyItemId() {
    // В проекте "генкары" обычно item_id=1
    return 1;
}

function invSum($db, $userId, $itemId) {
    $d = invDetect($db);
    if (!$d['user_col'] || !$d['qty_col']) return 0;

    $sql = "SELECT COALESCE(SUM(".$d['qty_col']."),0) AS s FROM items_users WHERE ".$d['user_col']."=? AND item_id=?";
    $row = fetchOne($db, $sql, 'ii', array((int)$userId, (int)$itemId));
    return $row ? (int)$row['s'] : 0;
}

function invTakeTx($db, $userId, $itemId, $need) {
    $need = (int)$need;
    if ($need <= 0) return true;

    // Если в проекте есть штатные функции — используем их (они учитывают нестэковый инвентарь/прочие поля)
    if (function_exists('minus_item')) {
        return (bool)minus_item((int)$itemId, $need, (int)$userId);
    }

    $d = invDetect($db);
    if (!$d['user_col'] || !$d['qty_col']) return false;

    // Собираем строки под блокировкой
    $sql = "SELECT id, ".$d['qty_col']." AS qty FROM items_users WHERE ".$d['user_col']."=? AND item_id=? ORDER BY id ASC FOR UPDATE";
    $rows = fetchAll($db, $sql, 'ii', array((int)$userId, (int)$itemId));

    $have = 0;
    for ($i=0; $i<count($rows); $i++) $have += (int)$rows[$i]['qty'];
    if ($have < $need) return false;

    $left = $need;
    for ($i=0; $i<count($rows) && $left > 0; $i++) {
        $rid = (int)$rows[$i]['id'];
        $qty = (int)$rows[$i]['qty'];
        if ($qty <= 0) continue;
        if ($qty <= $left) {
            $stmt = $db->prepare("DELETE FROM items_users WHERE id=?");
            $stmt->bind_param('i', $rid);
            $stmt->execute();
            $stmt->close();
            $left -= $qty;
        } else {
            $newQty = $qty - $left;
            $stmt = $db->prepare("UPDATE items_users SET ".$d['qty_col']."=? WHERE id=?");
            $stmt->bind_param('ii', $newQty, $rid);
            $stmt->execute();
            $stmt->close();
            $left = 0;
        }
    }

    return true;
}

function invGiveTx($db, $userId, $itemId, $amount) {
    $amount = (int)$amount;
    if ($amount <= 0) return true;

    if (function_exists('itemAdd')) {
        // (item_id, count, user_id, dop)
        itemAdd((int)$itemId, $amount, (int)$userId, 0);
        return true;
    }

    $d = invDetect($db);
    if (!$d['user_col'] || !$d['qty_col']) return false;

    // Если есть хотя бы одна строка — увеличим её. Иначе — вставим новую.
    $sql = "UPDATE items_users SET ".$d['qty_col']." = ".$d['qty_col']." + ? WHERE ".$d['user_col']."=? AND item_id=? ORDER BY id ASC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iii', $amount, $userId, $itemId);
    $stmt->execute();
    $aff = $stmt->affected_rows;
    $stmt->close();

    if ($aff > 0) return true;

    $sql = "INSERT INTO items_users (".$d['user_col'].", item_id, ".$d['qty_col'].") VALUES (?,?,?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iii', $userId, $itemId, $amount);
    $stmt->execute();
    $stmt->close();
    return true;
}

// -------------------------
// Clan: roles, perms, membership
// -------------------------
function clanEnsureDefaultRoles($db, $clanId) {
    $clanId = (int)$clanId;

    $hasIsSystem = columnExists($db, 'clan_roles', 'is_system');

    $roles = array(
        array('Лидер', 100),
        array('Офицер', 50),
        array('Участник', 10),
    );

    for ($i=0; $i<count($roles); $i++) {
        $name = $roles[$i][0];
        $rank = (int)$roles[$i][1];
        if ($hasIsSystem) {
            $stmt = $db->prepare("INSERT IGNORE INTO clan_roles (clan_id, name, `rank`, is_system) VALUES (?,?,?,1)");
            $stmt->bind_param('isi', $clanId, $name, $rank);
        } else {
            $stmt = $db->prepare("INSERT IGNORE INTO clan_roles (clan_id, name, `rank`) VALUES (?,?,?)");
            $stmt->bind_param('isi', $clanId, $name, $rank);
        }
        $stmt->execute();
        $stmt->close();
    }

    // map
    $rows = fetchAll($db, "SELECT id, name, `rank` FROM clan_roles WHERE clan_id=?", 'i', array($clanId));
    $map = array();
    for ($i=0; $i<count($rows); $i++) {
        $map[$rows[$i]['name']] = (int)$rows[$i]['id'];
    }

    // permissions (если таблица существует)
    if (tableExists($db, 'clan_role_permissions')) {
        $perm = array(
            'Лидер' => array(
                'clan.manage','clan.bank.deposit','clan.bank.withdraw',
                'clan.storage.deposit','clan.storage.withdraw',
                'clan.shop.buy','clan.members.kick','clan.roles.manage',
                'clan.faction.set','clan.location.manage','clan.quest.manage'
            ),
            'Офицер' => array(
                'clan.bank.deposit',
                'clan.storage.deposit','clan.storage.withdraw',
                'clan.shop.buy','clan.members.kick',
                'clan.location.manage','clan.quest.manage'
            ),
            'Участник' => array(
                'clan.bank.deposit','clan.storage.deposit','clan.shop.buy'
            ),
        );

        foreach ($perm as $roleName => $keys) {
            if (!isset($map[$roleName])) continue;
            $roleId = (int)$map[$roleName];
            for ($k=0; $k<count($keys); $k++) {
                $key = (string)$keys[$k];
                $stmt = $db->prepare("INSERT IGNORE INTO clan_role_permissions (role_id, perm_key) VALUES (?,?)");
                $stmt->bind_param('is', $roleId, $key);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    return $map;
}

function clanGetMembership($db, $userId) {
    $userId = (int)$userId;
    $sql = "SELECT cm.clan_id, cm.role_id, c.name AS clan_name, cr.`rank` AS role_rank, cr.name AS role_name"
        . (columnExists($db,'clan_members','title') ? ", cm.title" : "")
        . " FROM clan_members cm"
        . " JOIN clans c ON c.id = cm.clan_id"
        . " LEFT JOIN clan_roles cr ON cr.id = cm.role_id"
        . " WHERE cm.user_id=? AND (c.is_deleted=0 OR c.is_deleted IS NULL) LIMIT 1";

    return fetchOne($db, $sql, 'i', array($userId));
}

function clanHasPerm($db, $userId, $permKey) {
    $userId = (int)$userId;
    if (!tableExists($db, 'clan_role_permissions')) {
        // если таблицы прав нет — допускаем по рангу: лидер (>=100) имеет всё, офицер (>=50) — ограниченно
        $m = clanGetMembership($db, $userId);
        if (!$m) return false;
        $rank = isset($m['role_rank']) ? (int)$m['role_rank'] : 0;
        if ($rank >= 100) return true;
        if ($rank >= 50) {
            $allow = array('clan.bank.deposit','clan.storage.deposit','clan.storage.withdraw','clan.shop.buy','clan.members.kick');
            return in_array($permKey, $allow, true);
        }
        $allow = array('clan.bank.deposit','clan.storage.deposit','clan.shop.buy');
        return in_array($permKey, $allow, true);
    }

    $row = fetchOne(
        $db,
        "SELECT 1 AS ok FROM clan_members cm JOIN clan_role_permissions rp ON rp.role_id=cm.role_id WHERE cm.user_id=? AND rp.perm_key=? LIMIT 1",
        'is',
        array((int)$userId, (string)$permKey)
    );

    return $row ? true : false;
}

function clanRequirePerm($db, $userId, $permKey) {
    if (!clanHasPerm($db, $userId, $permKey)) {
        respond(false, 'Недостаточно прав.', array());
    }
}

function clanAudit($db, $clanId, $actorUserId, $action, $targetUserId, $meta) {
    if (!tableExists($db, 'clan_audit_log')) return;
    if (!is_array($meta)) $meta = array();
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    $stmt = $db->prepare("INSERT INTO clan_audit_log (clan_id, actor_user_id, target_user_id, action, meta) VALUES (?,?,?,?,?)");
    $cid = (int)$clanId;
    $aid = (int)$actorUserId;
    $tid = $targetUserId === null ? null : (int)$targetUserId;
    // bind_param не любит null в i. Приведём к int 0 и храним null, если колонка допускает? Безопаснее 0.
    if ($tid === null) $tid = 0;
    $stmt->bind_param('iiiss', $cid, $aid, $tid, $action, $metaJson);
    $stmt->execute();
    $stmt->close();
}

// -------------------------
// Clan wallet
// -------------------------
function walletEnsure($db, $clanId) {
    if (!tableExists($db, 'clan_wallet')) return;
    $stmt = $db->prepare("INSERT IGNORE INTO clan_wallet (clan_id, currency, balance) VALUES (?, 'genkars', 0)");
    $cid = (int)$clanId;
    $stmt->bind_param('i', $cid);
    $stmt->execute();
    $stmt->close();
}

function walletGetForUpdate($db, $clanId) {
    walletEnsure($db, $clanId);
    $row = fetchOne($db, "SELECT balance FROM clan_wallet WHERE clan_id=? AND currency='genkars' FOR UPDATE", 'i', array((int)$clanId));
    return $row ? (int)$row['balance'] : 0;
}

function walletSet($db, $clanId, $balance) {
    walletEnsure($db, $clanId);
    $stmt = $db->prepare("UPDATE clan_wallet SET balance=? WHERE clan_id=? AND currency='genkars'");
    $b = (int)$balance;
    $cid = (int)$clanId;
    $stmt->bind_param('ii', $b, $cid);
    $stmt->execute();
    $stmt->close();
}

function walletLedger($db, $clanId, $userId, $delta, $reason, $meta) {
    if (!tableExists($db, 'clan_wallet_ledger')) return;
    if (!is_array($meta)) $meta = array();
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    $stmt = $db->prepare("INSERT INTO clan_wallet_ledger (clan_id, user_id, delta, reason, meta) VALUES (?,?,?,?,?)");
    $cid = (int)$clanId;
    $uid = (int)$userId;
    $d = (int)$delta;
    $stmt->bind_param('iiiss', $cid, $uid, $d, $reason, $metaJson);
    $stmt->execute();
    $stmt->close();
}

function saveEmblem($clanId, $tmpPath, $mime) {
    $dir = (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '.') . '/img/world/clans/emblems';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $clanId = (int)$clanId;
    $targetPng = $dir . '/' . $clanId . '.png';

    // Ограничения безопасности
    if (!is_file($tmpPath)) return null;

    // PNG
    if ($mime === 'image/png') {
        if (@move_uploaded_file($tmpPath, $targetPng)) return '/img/world/clans/emblems/' . $clanId . '.png';
        // fallback, если файл уже не "uploaded"
        if (@copy($tmpPath, $targetPng)) return '/img/world/clans/emblems/' . $clanId . '.png';
        return null;
    }

    // JPEG -> PNG
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
        if (function_exists('imagecreatefromjpeg') && function_exists('imagepng')) {
            $im = @imagecreatefromjpeg($tmpPath);
            if ($im) {
                @imagepng($im, $targetPng);
                @imagedestroy($im);
                return '/img/world/clans/emblems/' . $clanId . '.png';
            }
        }
        // Без GD — сохраняем как jpg рядом
        $targetJpg = $dir . '/' . $clanId . '.jpg';
        if (@move_uploaded_file($tmpPath, $targetJpg)) return '/img/world/clans/emblems/' . $clanId . '.jpg';
        if (@copy($tmpPath, $targetJpg)) return '/img/world/clans/emblems/' . $clanId . '.jpg';
        return null;
    }

    return null;
}

// -------------------------
// Routing
// -------------------------
$objectRaw = pStr('object', '');
if ($objectRaw === '') {
    respond(false, 'Нет действия.', array());
}

$action = $objectRaw;
$param = null;
if (strpos($objectRaw, ',') !== false) {
    $parts = explode(',', $objectRaw, 2);
    $action = trim($parts[0]);
    $param  = trim($parts[1]);
}

// -------------------------
// Actions
// -------------------------
try {

    switch ($action) {

        // -------------------------
        // Public: list
        // -------------------------
        case 'clansList': {
            requireClanSchema($db);
            $rows = fetchAll($db,
                "SELECT id, name, level, rating, emblem_path FROM clans WHERE is_deleted=0 ORDER BY level DESC, rating DESC, name ASC LIMIT 200",
                '',
                array()
            );
            $out = array();
            for ($i=0; $i<count($rows); $i++) {
                $id = (int)$rows[$i]['id'];
                $emblem = $rows[$i]['emblem_path'];
                if (!$emblem) $emblem = '/img/world/clans/emblems/' . $id . '.png';
                $out[] = array(
                    'id' => $id,
                    'name' => $rows[$i]['name'],
                    'level' => (int)$rows[$i]['level'],
                    'rating' => (int)$rows[$i]['rating'],
                    'emblem' => $emblem,
                );
            }
            respond(true, '', array('clansList'=>$out));
        }

        // -------------------------
        // Create
        // -------------------------
        case 'createClan': {
            requireClanSchema($db);

            $name = clearName(pStr('name', ''));
            if (function_exists('mb_strlen')) {
                $len = mb_strlen($name, 'UTF-8');
            } else {
                $len = strlen($name);
            }
            if ($len < 3 || $len > 32) respond(false, 'Название клана должно быть 3–32 символа.', array());
            if (!preg_match('/^[a-zA-Z0-9А-Яа-яЁё _\-]+$/u', $name)) respond(false, 'Недопустимые символы в названии.', array());

            $member = clanGetMembership($db, $userId);
            if ($member) respond(false, 'Вы уже состоите в клане.', array());

            $exists = fetchOne($db, "SELECT id FROM clans WHERE name=? AND is_deleted=0 LIMIT 1", 's', array($name));
            if ($exists) respond(false, 'Клан с таким названием уже существует.', array());

            $price = 1500000;
            $moneyItem = invMoneyItemId();

            $db->begin_transaction();
            try {
                // списание
                if (!invTakeTx($db, $userId, $moneyItem, $price)) {
                    throw new Exception('Недостаточно Генкаров для создания клана.');
                }

                // создание клана (минимальный набор полей)
                $stmt = $db->prepare("INSERT INTO clans (name, created_by) VALUES (?,?)");
                $stmt->bind_param('si', $name, $userId);
                $stmt->execute();
                $clanId = (int)$stmt->insert_id;
                $stmt->close();

                // роли + лидер
                $roleMap = clanEnsureDefaultRoles($db, $clanId);
                $leaderRoleId = isset($roleMap['Лидер']) ? (int)$roleMap['Лидер'] : 0;
                if ($leaderRoleId <= 0) throw new Exception('Не удалось создать роли.');

                $stmt = $db->prepare("INSERT INTO clan_members (clan_id, user_id, role_id) VALUES (?,?,?)");
                $stmt->bind_param('iii', $clanId, $userId, $leaderRoleId);
                $stmt->execute();
                $stmt->close();

                // кошелёк
                walletEnsure($db, $clanId);

                // эмблема
                $emblemPath = null;
                if (isset($_FILES['emblem']) && isset($_FILES['emblem']['tmp_name']) && is_uploaded_file($_FILES['emblem']['tmp_name'])) {
                    $mime = isset($_FILES['emblem']['type']) ? (string)$_FILES['emblem']['type'] : '';
                    if ($mime === 'image/png' || $mime === 'image/jpeg' || $mime === 'image/jpg') {
                        $emblemPath = saveEmblem($clanId, $_FILES['emblem']['tmp_name'], $mime);
                        if ($emblemPath && columnExists($db,'clans','emblem_path')) {
                            $stmt = $db->prepare("UPDATE clans SET emblem_path=? WHERE id=?");
                            $stmt->bind_param('si', $emblemPath, $clanId);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }

                // аудит
                clanAudit($db, $clanId, $userId, 'clan.create', 0, array('date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));
                clanAudit($db, $clanId, $userId, 'member.join', $userId, array('date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));

                $db->commit();

                respond(true, 'Клан успешно создан!', array(
                    'clanId' => $clanId,
                    'minus'  => '-' . $price,
                    'emblem' => $emblemPath ? $emblemPath : ('/img/world/clans/emblems/' . $clanId . '.png')
                ));

            } catch (Exception $e) {
                $db->rollback();
                respond(false, $e->getMessage() ? $e->getMessage() : 'Ошибка создания клана.', array());
            }
        }

        // -------------------------
        // Card
        // -------------------------
        case 'clanCard': {
            requireClanSchema($db);
            $clanId = (int)$param;
            if ($clanId <= 0) respond(false, 'Некорректный клан.', array());

            $clan = fetchOne($db,
                "SELECT id, name, created_by, created_at, level, exp, exp_next, rating, emblem_path FROM clans WHERE id=? AND is_deleted=0 LIMIT 1",
                'i',
                array($clanId)
            );
            if (!$clan) respond(false, 'Клан не найден.', array());

            // баланс
            $balance = 0;
            if (tableExists($db,'clan_wallet')) {
                $row = fetchOne($db, "SELECT balance FROM clan_wallet WHERE clan_id=? AND currency='genkars' LIMIT 1", 'i', array($clanId));
                $balance = $row ? (int)$row['balance'] : 0;
            } elseif (columnExists($db,'clans','money')) {
                $row = fetchOne($db, "SELECT money FROM clans WHERE id=? LIMIT 1", 'i', array($clanId));
                $balance = $row ? (int)$row['money'] : 0;
            }

            // участники
            $hasTitle = columnExists($db,'clan_members','title');
            $sqlMembers = "SELECT u.id AS uid, u.login, u.user_group, cm.role_id, cr.`rank`" . ($hasTitle ? ", cm.title" : "") .
                " FROM clan_members cm JOIN users u ON u.id=cm.user_id LEFT JOIN clan_roles cr ON cr.id=cm.role_id WHERE cm.clan_id=? ORDER BY cr.`rank` DESC, cm.joined_at ASC";
            $members = fetchAll($db, $sqlMembers, 'i', array($clanId));

            $users = array();
            for ($i=0; $i<count($members); $i++) {
                $m = $members[$i];
                $uid = (int)$m['uid'];
                $group = isset($m['user_group']) ? (int)$m['user_group'] : 1;
                $rank = isset($m['rank']) ? (int)$m['rank'] : 0;

                $roleClass = 0;
                if ($rank >= 100) $roleClass = 1;
                else if ($rank >= 50) $roleClass = 2;

                $rating = 0;
                $title  = $hasTitle ? (string)$m['title'] : '';

                $isLeader = ($rank >= 100) ? 1 : 0;

                // Формат важен для существующего фронта
                $users[] = $m['login'] . ',' . $group . ',' . $rating . ',' . $title . ',' . $roleClass . ',' . $userId . ',' . $uid . ',' . $isLeader . ',' . $uid;
            }

            // логи (последние 30)
            $outLog = array();
            if (tableExists($db,'clan_audit_log')) {
                $logs = fetchAll($db,
                    "SELECT al.action, al.meta, al.created_at, u.login AS actor_login, u.user_group AS actor_group"
                    . " FROM clan_audit_log al LEFT JOIN users u ON u.id=al.actor_user_id"
                    . " WHERE al.clan_id=? ORDER BY al.id DESC LIMIT 30",
                    'i',
                    array($clanId)
                );

                for ($i=0; $i<count($logs); $i++) {
                    $l = $logs[$i];
                    $meta = array();
                    if (isset($l['meta']) && $l['meta'] !== '') {
                        $tmp = json_decode($l['meta'], true);
                        if (is_array($tmp)) $meta = $tmp;
                    }

                    $type = 'ABOUT_CLAN_ALERT';
                    switch ($l['action']) {
                        case 'clan.create': $type = 'CLAN_CREATE'; break;
                        case 'member.join': $type = 'ADD_CLAN_USER'; break;
                        case 'member.leave': $type = 'LEFT_CLAN'; break;
                        case 'member.kick': $type = 'LEFT_CLAN_ALERT'; break;
                        case 'bank.deposit': $type = 'ADD_CLAN_MONEY'; break;
                        case 'bank.withdraw': $type = 'TAKE_CLAN_MONEY'; break;
                        case 'clan.notice': $type = 'ADD_CLAN_NOTICE'; break;
                        case 'member.title': $type = 'ADD_CLAN_STATUS'; break;
                        case 'leader.assign': $type = 'ADD_CLAN_ADMIN'; break;
                        case 'leader.revoke': $type = 'DELETE_CLAN_ADMIN'; break;
                        default: $type = 'ABOUT_CLAN_ALERT';
                    }

                    $info = array(
                        'date' => nowHuman($l['created_at']),
                        'user_group' => isset($l['actor_group']) ? (int)$l['actor_group'] : 1,
                        'user_new' => isset($l['actor_login']) ? (string)$l['actor_login'] : '',
                    );
                    foreach ($meta as $k=>$v) $info[$k] = $v;

                    $outLog[] = array(
                        'type' => $type,
                        'info' => json_encode($info, JSON_UNESCAPED_UNICODE)
                    );
                }
            }

            $dateCreate = '';
            if (isset($clan['created_at']) && $clan['created_at']) {
                $ts = strtotime($clan['created_at']);
                if ($ts) $dateCreate = date('d.m.Y', $ts);
            }
            if (!$dateCreate) $dateCreate = date('d.m.Y');

            $infoObj = array(
                'name' => $clan['name'],
                'Creater' => (int)$clan['created_by'],
                'Money' => $balance,
                'dateCreate' => $dateCreate,
            );

            $emblem = isset($clan['emblem_path']) && $clan['emblem_path'] ? $clan['emblem_path'] : ('/img/world/clans/emblems/' . $clanId . '.png');

            respond(true, '', array(
                'info' => json_encode($infoObj, JSON_UNESCAPED_UNICODE),
                'users' => $users,
                'log' => $outLog,
                'position' => 1,
                'rating' => (int)$clan['rating'],
                'countUsers' => count($members),
                'clan_level' => (int)$clan['level'],
                'clan_exp' => (int)$clan['exp'],
                'clan_exp_next' => (int)$clan['exp_next'],
                'emblem' => $emblem,
            ));
        }

        // -------------------------
        // Bank
        // -------------------------
        case 'addMoney': {
            requireClanSchema($db);
            $amount = (int)$param;
            if ($amount < 1) $amount = 1;

            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.bank.deposit');

            $clanId = (int)$mem['clan_id'];
            $moneyItem = invMoneyItemId();

            $db->begin_transaction();
            try {
                if (!invTakeTx($db, $userId, $moneyItem, $amount)) {
                    throw new Exception('Недостаточно Генкаров.');
                }

                $bal = walletGetForUpdate($db, $clanId);
                $new = $bal + $amount;
                walletSet($db, $clanId, $new);

                walletLedger($db, $clanId, $userId, $amount, 'bank.deposit', array('count'=>$amount));
                clanAudit($db, $clanId, $userId, 'bank.deposit', 0, array('count'=>$amount, 'date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));

                $db->commit();
                respond(true, 'Средства внесены на счёт клана.', array('balance'=>$new));
            } catch (Exception $e) {
                $db->rollback();
                respond(false, $e->getMessage() ? $e->getMessage() : 'Ошибка внесения средств.', array());
            }
        }

        case 'minusMoney': {
            requireClanSchema($db);
            $amount = (int)$param;
            if ($amount < 1) $amount = 1;

            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.bank.withdraw');

            $clanId = (int)$mem['clan_id'];
            $moneyItem = invMoneyItemId();

            $db->begin_transaction();
            try {
                $bal = walletGetForUpdate($db, $clanId);
                if ($bal < $amount) throw new Exception('Недостаточно средств на счёте клана.');

                $new = $bal - $amount;
                walletSet($db, $clanId, $new);

                invGiveTx($db, $userId, $moneyItem, $amount);

                walletLedger($db, $clanId, $userId, -$amount, 'bank.withdraw', array('count'=>$amount));
                clanAudit($db, $clanId, $userId, 'bank.withdraw', 0, array('count'=>$amount, 'date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));

                $db->commit();
                respond(true, 'Средства сняты со счёта клана.', array('balance'=>$new));
            } catch (Exception $e) {
                $db->rollback();
                respond(false, $e->getMessage() ? $e->getMessage() : 'Ошибка снятия средств.', array());
            }
        }

        // -------------------------
        // Leave
        // -------------------------
        case 'left': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());

            $clanId = (int)$mem['clan_id'];
            $rank = isset($mem['role_rank']) ? (int)$mem['role_rank'] : 0;

            $cnt = fetchOne($db, "SELECT COUNT(*) AS c FROM clan_members WHERE clan_id=?", 'i', array($clanId));
            $countMembers = $cnt ? (int)$cnt['c'] : 0;
            if ($rank >= 100 && $countMembers > 1) {
                respond(false, 'Лидер не может покинуть клан, пока в нём есть участники. Передайте лидерство.', array());
            }

            $stmt = $db->prepare("DELETE FROM clan_members WHERE user_id=?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();

            clanAudit($db, $clanId, $userId, 'member.leave', $userId, array('date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));
            respond(true, 'Вы покинули клан.', array());
        }

        // -------------------------
        // Control UI
        // -------------------------
        case 'clanCardControl': {
            requireClanSchema($db);
            $clanId = (int)$param;
            if ($clanId <= 0) respond(false, 'Некорректный клан.', array());

            $mem = clanGetMembership($db, $userId);
            if (!$mem || (int)$mem['clan_id'] !== $clanId) respond(false, 'Это не ваш клан.', array());
            clanRequirePerm($db, $userId, 'clan.manage');

            $clan = fetchOne($db, "SELECT id, name, level, exp, exp_next" . (columnExists($db,'clans','notice')?", notice":"") . " FROM clans WHERE id=? AND is_deleted=0 LIMIT 1", 'i', array($clanId));
            if (!$clan) respond(false, 'Клан не найден.', array());

            $notice = isset($clan['notice']) ? (string)$clan['notice'] : '';

            $html = '';
            $html .= '<div class="modal--clan-control__dialog">';
            $html .= '  <div class="header"><span>Управление кланом</span><div class="closeModel" onclick="CloseModel();"><i class="fa fa-times"></i></div></div>';
            $html .= '  <div class="content-model" style="padding:14px;">';
            $html .= '    <div style="display:flex;gap:14px;align-items:center;margin-bottom:10px;">';
            $html .= '      <div class="cc-lvl" style="font-weight:900;font-size:18px;">Lv '.(int)$clan['level'].'</div>';
            $html .= '      <div class="cc-bar" style="flex:1;height:14px;background:#eee;border-radius:10px;overflow:hidden;position:relative;">';
            $html .= '        <span style="display:block;height:100%;width:0;background:#6cc04a"></span>';
            $html .= '        <div class="cc-barLabel" style="position:absolute;inset:0;display:grid;place-items:center;font-size:12px;"></div>';
            $html .= '      </div>';
            $html .= '      <div style="font-weight:900;">ID: '.(int)$clanId.'</div>';
            $html .= '    </div>';

            $html .= '    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">';

            $html .= '      <div style="background:#f8fbff;border:1px solid #e6eafe;border-radius:12px;padding:12px;">';
            $html .= '        <div style="font-weight:900;margin-bottom:8px;">Объявление</div>';
            $html .= '        <input class="cc-input text" id="goNotifyClan" placeholder="Текст объявления..." value="'.htmlspecialchars($notice, ENT_QUOTES, 'UTF-8').'" style="width:100%;height:34px;border-radius:10px;border:1px solid #e6eafe;padding:0 10px;">';
            $html .= '        <button class="cc-btn" onclick="upgradeClan(\'goNotifyClan\')" style="margin-top:8px;">Опубликовать</button>';
            $html .= '      </div>';

            $html .= '      <div style="background:#f8fbff;border:1px solid #e6eafe;border-radius:12px;padding:12px;">';
            $html .= '        <div style="font-weight:900;margin-bottom:8px;">Лидер клана</div>';
            $html .= '        <input class="cc-input text" id="goLeaderClan" placeholder="Имя тренера..." style="width:100%;height:34px;border-radius:10px;border:1px solid #e6eafe;padding:0 10px;">';
            $html .= '        <div style="display:flex;gap:8px;margin-top:8px;">';
            $html .= '          <button class="cc-btn" onclick="upgradeClan(\'goLeaderClan\')" style="flex:1;">Назначить лидером</button>';
            $html .= '          <button class="cc-btn" onclick="upgradeClan(\'goUnleaderClan\')" style="flex:1;background:#ffefef;border-color:#ffd1d1;">Снять с лидерства</button>';
            $html .= '        </div>';
            $html .= '      </div>';

            $html .= '      <div style="background:#f8fbff;border:1px solid #e6eafe;border-radius:12px;padding:12px;">';
            $html .= '        <div style="font-weight:900;margin-bottom:8px;">Вручить звание</div>';
            $html .= '        <input class="cc-input text" id="goStatusClanLogin" placeholder="Имя тренера..." style="width:100%;height:34px;border-radius:10px;border:1px solid #e6eafe;padding:0 10px;">';
            $html .= '        <input class="cc-input text" id="goStatusClanText" placeholder="Звание..." style="width:100%;height:34px;border-radius:10px;border:1px solid #e6eafe;padding:0 10px;margin-top:8px;">';
            $html .= '        <button class="cc-btn" onclick="upgradeClan(\'goStatusClan\')" style="margin-top:8px;">Вручить</button>';
            $html .= '      </div>';

            $html .= '      <div style="background:#fff5f5;border:1px solid #ffd1d1;border-radius:12px;padding:12px;">';
            $html .= '        <div style="font-weight:900;margin-bottom:8px;">Исключить участника</div>';
            $html .= '        <input class="cc-input text" id="goDeleteClan" placeholder="Имя тренера..." style="width:100%;height:34px;border-radius:10px;border:1px solid #ffd1d1;padding:0 10px;">';
            $html .= '        <button class="cc-btn" onclick="upgradeClan(\'goDeleteClan\')" style="margin-top:8px;background:#ff5a6b;border-color:#ff5a6b;">Исключить</button>';
            $html .= '      </div>';

            $html .= '    </div>';
            $html .= '  </div>';
            $html .= '</div>';

            respond(true, '', array(
                'html' => $html,
                'clan_level' => (int)$clan['level'],
                'clan_exp' => (int)$clan['exp'],
                'clan_exp_next' => (int)$clan['exp_next'],
            ));
        }

        // -------------------------
        // Management actions
        // -------------------------
        case 'goNotifyClan': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.manage');

            $clanId = (int)$mem['clan_id'];
            $text = clearName(pStr('name', ''));
            if (function_exists('mb_strlen') && mb_strlen($text,'UTF-8') > 200) {
                $text = mb_substr($text, 0, 200, 'UTF-8');
            } else if (strlen($text) > 200) {
                $text = substr($text, 0, 200);
            }

            if (columnExists($db,'clans','notice')) {
                $stmt = $db->prepare("UPDATE clans SET notice=? WHERE id=?");
                $stmt->bind_param('si', $text, $clanId);
                $stmt->execute();
                $stmt->close();
            }

            clanAudit($db, $clanId, $userId, 'clan.notice', 0, array('notice'=>$text, 'date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));
            respond(true, 'Объявление обновлено.', array());
        }

        case 'goStatusClan': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.manage');

            $clanId = (int)$mem['clan_id'];
            $login = clearName(pStr('name', ''));
            $title = clearName(pStr('other', ''));
            if ($login === '' || $title === '') respond(false, 'Укажите игрока и звание.', array());

            if (function_exists('mb_strlen') && mb_strlen($title,'UTF-8') > 32) {
                $title = mb_substr($title, 0, 32, 'UTF-8');
            } else if (strlen($title) > 32) {
                $title = substr($title, 0, 32);
            }

            $target = fetchOne($db, "SELECT id FROM users WHERE login=? LIMIT 1", 's', array($login));
            if (!$target) respond(false, 'Игрок не найден.', array());
            $targetId = (int)$target['id'];

            $row = fetchOne($db, "SELECT 1 AS ok FROM clan_members WHERE clan_id=? AND user_id=? LIMIT 1", 'ii', array($clanId, $targetId));
            if (!$row) respond(false, 'Игрок не состоит в вашем клане.', array());

            if (columnExists($db,'clan_members','title')) {
                $stmt = $db->prepare("UPDATE clan_members SET title=? WHERE clan_id=? AND user_id=?");
                $stmt->bind_param('sii', $title, $clanId, $targetId);
                $stmt->execute();
                $stmt->close();
            }

            clanAudit($db, $clanId, $userId, 'member.title', $targetId, array('status'=>$title, 'date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:'', 'target'=>$login));
            respond(true, 'Звание выдано.', array());
        }

        case 'goDeleteClan': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.members.kick');

            $clanId = (int)$mem['clan_id'];
            $login = clearName(pStr('name', ''));
            if ($login === '') respond(false, 'Укажите игрока.', array());

            $target = fetchOne($db, "SELECT id FROM users WHERE login=? LIMIT 1", 's', array($login));
            if (!$target) respond(false, 'Игрок не найден.', array());
            $targetId = (int)$target['id'];

            // нельзя кикнуть себя
            if ($targetId === (int)$userId) respond(false, 'Нельзя исключить самого себя.', array());

            // проверка членства
            $row = fetchOne($db, "SELECT 1 AS ok FROM clan_members WHERE clan_id=? AND user_id=? LIMIT 1", 'ii', array($clanId, $targetId));
            if (!$row) respond(false, 'Игрок не состоит в вашем клане.', array());

            // если кикают лидера — запрет
            $r = fetchOne($db,
                "SELECT cr.`rank` AS r FROM clan_members cm LEFT JOIN clan_roles cr ON cr.id=cm.role_id WHERE cm.clan_id=? AND cm.user_id=? LIMIT 1",
                'ii',
                array($clanId, $targetId)
            );
            $rank = $r ? (int)$r['r'] : 0;
            if ($rank >= 100) respond(false, 'Нельзя исключить лидера. Сначала снимите лидерство.', array());

            $stmt = $db->prepare("DELETE FROM clan_members WHERE clan_id=? AND user_id=?");
            $stmt->bind_param('ii', $clanId, $targetId);
            $stmt->execute();
            $stmt->close();

            clanAudit($db, $clanId, $userId, 'member.kick', $targetId, array('date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:'', 'target'=>$login));
            respond(true, 'Игрок исключён из клана.', array());
        }

        case 'goLeaderClan': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            // в текущей модели только лидер имеет clan.manage по умолчанию
            clanRequirePerm($db, $userId, 'clan.manage');

            $clanId = (int)$mem['clan_id'];
            $login = clearName(pStr('name', ''));
            if ($login === '') respond(false, 'Укажите игрока.', array());

            $target = fetchOne($db, "SELECT id FROM users WHERE login=? LIMIT 1", 's', array($login));
            if (!$target) respond(false, 'Игрок не найден.', array());
            $targetId = (int)$target['id'];

            $row = fetchOne($db, "SELECT 1 AS ok FROM clan_members WHERE clan_id=? AND user_id=? LIMIT 1", 'ii', array($clanId, $targetId));
            if (!$row) respond(false, 'Игрок не состоит в вашем клане.', array());

            $roleMap = clanEnsureDefaultRoles($db, $clanId);
            $leaderRole = isset($roleMap['Лидер']) ? (int)$roleMap['Лидер'] : 0;
            $officerRole = isset($roleMap['Офицер']) ? (int)$roleMap['Офицер'] : 0;
            if ($leaderRole <= 0 || $officerRole <= 0) respond(false, 'Не удалось получить роли.', array());

            $db->begin_transaction();
            try {
                // текущие лидеры -> офицеры
                $stmt = $db->prepare(
                    "UPDATE clan_members cm JOIN clan_roles cr ON cr.id=cm.role_id SET cm.role_id=? WHERE cm.clan_id=? AND cr.`rank`>=100"
                );
                $stmt->bind_param('ii', $officerRole, $clanId);
                $stmt->execute();
                $stmt->close();

                // новый лидер
                $stmt = $db->prepare("UPDATE clan_members SET role_id=? WHERE clan_id=? AND user_id=?");
                $stmt->bind_param('iii', $leaderRole, $clanId, $targetId);
                $stmt->execute();
                $stmt->close();

                clanAudit($db, $clanId, $userId, 'leader.assign', $targetId, array('date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:'', 'target'=>$login));

                $db->commit();
                respond(true, 'Лидер клана изменён.', array());

            } catch (Exception $e) {
                $db->rollback();
                respond(false, 'Ошибка назначения лидера.', array());
            }
        }

        case 'goUnleaderClan': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.manage');

            $clanId = (int)$mem['clan_id'];
            $login = clearName(pStr('name', ''));
            if ($login === '') respond(false, 'Укажите игрока.', array());

            $target = fetchOne($db, "SELECT id FROM users WHERE login=? LIMIT 1", 's', array($login));
            if (!$target) respond(false, 'Игрок не найден.', array());
            $targetId = (int)$target['id'];

            $roleMap = clanEnsureDefaultRoles($db, $clanId);
            $memberRole = isset($roleMap['Участник']) ? (int)$roleMap['Участник'] : 0;
            if ($memberRole <= 0) respond(false, 'Не удалось получить роли.', array());

            $stmt = $db->prepare("UPDATE clan_members SET role_id=? WHERE clan_id=? AND user_id=?");
            $stmt->bind_param('iii', $memberRole, $clanId, $targetId);
            $stmt->execute();
            $aff = $stmt->affected_rows;
            $stmt->close();

            if ($aff <= 0) respond(false, 'Не удалось изменить роль (проверьте, что игрок в клане).', array());

            clanAudit($db, $clanId, $userId, 'leader.revoke', $targetId, array('date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:'', 'target'=>$login));
            respond(true, 'Лидерство снято.', array());
        }

        // -------------------------
        // Storage
        // -------------------------
        case 'openStorage': {
            requireClanSchema($db);
            $clanId = pInt('clan_id', 0);
            if ($clanId <= 0) respond(false, 'Некорректный клан.', array());

            $mem = clanGetMembership($db, $userId);
            if (!$mem || (int)$mem['clan_id'] !== $clanId) respond(false, 'Это не ваш клан.', array());

            $storage = fetchAll($db,
                "SELECT s.item_id, s.amount, i.name, i.icon FROM clan_storage s LEFT JOIN base_items i ON i.id=s.item_id WHERE s.clan_id=? ORDER BY s.amount DESC, s.item_id ASC",
                'i',
                array($clanId)
            );
            respond(true, '', array('storage'=>$storage));
        }

        case 'getUserItemsForClanStorage': {
            requireClanSchema($db);
            $d = invDetect($db);
            if (!$d['user_col'] || !$d['qty_col']) respond(false, 'Инвентарь недоступен (items_users).', array());

            $sql = "SELECT iu.item_id, SUM(iu.".$d['qty_col'].") AS cnt, bi.name, bi.icon"
                . " FROM items_users iu JOIN base_items bi ON bi.id=iu.item_id"
                . " WHERE iu.".$d['user_col']."=? GROUP BY iu.item_id HAVING cnt>0 ORDER BY bi.name ASC";
            $items = fetchAll($db, $sql, 'i', array($userId));

            $out = array();
            for ($i=0; $i<count($items); $i++) {
                $out[] = array(
                    'item_id' => (int)$items[$i]['item_id'],
                    'count'   => (int)$items[$i]['cnt'],
                    'name'    => $items[$i]['name'],
                    'icon'    => $items[$i]['icon'],
                );
            }
            respond(true, '', array('items'=>$out));
        }

        case 'clanStorageAdd': {
            requireClanSchema($db);
            $clanId = pInt('clan_id', 0);
            $itemId = pInt('item_id', 0);
            $amount = pInt('amount', 1);
            if ($amount < 1) $amount = 1;
            if ($clanId <= 0 || $itemId <= 0) respond(false, 'Некорректные данные.', array());

            $mem = clanGetMembership($db, $userId);
            if (!$mem || (int)$mem['clan_id'] !== $clanId) respond(false, 'Это не ваш клан.', array());
            clanRequirePerm($db, $userId, 'clan.storage.deposit');

            $db->begin_transaction();
            try {
                if (!invTakeTx($db, $userId, $itemId, $amount)) throw new Exception('Недостаточно предметов.');

                $stmt = $db->prepare("UPDATE clan_storage SET amount=amount+? WHERE clan_id=? AND item_id=?");
                $stmt->bind_param('iii', $amount, $clanId, $itemId);
                $stmt->execute();
                $aff = $stmt->affected_rows;
                $stmt->close();

                if ($aff === 0) {
                    $stmt = $db->prepare("INSERT INTO clan_storage (clan_id, item_id, amount) VALUES (?,?,?)");
                    $stmt->bind_param('iii', $clanId, $itemId, $amount);
                    $stmt->execute();
                    $stmt->close();
                }

                if (tableExists($db,'clan_storage_log')) {
                    $stmt = $db->prepare("INSERT INTO clan_storage_log (clan_id,item_id,user_id,amount,action) VALUES (?,?,?,?, 'add')");
                    $stmt->bind_param('iiii', $clanId, $itemId, $userId, $amount);
                    $stmt->execute();
                    $stmt->close();
                }

                clanAudit($db, $clanId, $userId, 'storage.deposit', 0, array('item_id'=>$itemId,'count'=>$amount,'date'=>date('d.m H:i'),'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));

                $db->commit();

                $storage = fetchAll($db,
                    "SELECT s.item_id, s.amount, i.name, i.icon FROM clan_storage s LEFT JOIN base_items i ON i.id=s.item_id WHERE s.clan_id=? ORDER BY s.amount DESC, s.item_id ASC",
                    'i',
                    array($clanId)
                );

                respond(true, 'Предмет положен на склад!', array('storage'=>$storage));

            } catch (Exception $e) {
                $db->rollback();
                respond(false, $e->getMessage() ? $e->getMessage() : 'Ошибка склада.', array());
            }
        }

        case 'clanStorageTake': {
            requireClanSchema($db);
            $clanId = pInt('clan_id', 0);
            $itemId = pInt('item_id', 0);
            $amount = pInt('amount', 1);
            if ($amount < 1) $amount = 1;
            if ($clanId <= 0 || $itemId <= 0) respond(false, 'Некорректные данные.', array());

            $mem = clanGetMembership($db, $userId);
            if (!$mem || (int)$mem['clan_id'] !== $clanId) respond(false, 'Это не ваш клан.', array());
            clanRequirePerm($db, $userId, 'clan.storage.withdraw');

            $db->begin_transaction();
            try {
                $row = fetchOne($db, "SELECT amount FROM clan_storage WHERE clan_id=? AND item_id=? FOR UPDATE", 'ii', array($clanId, $itemId));
                $have = $row ? (int)$row['amount'] : 0;
                if ($have < $amount) throw new Exception('Недостаточно на складе.');

                $new = $have - $amount;
                if ($new > 0) {
                    $stmt = $db->prepare("UPDATE clan_storage SET amount=? WHERE clan_id=? AND item_id=?");
                    $stmt->bind_param('iii', $new, $clanId, $itemId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $db->prepare("DELETE FROM clan_storage WHERE clan_id=? AND item_id=?");
                    $stmt->bind_param('ii', $clanId, $itemId);
                    $stmt->execute();
                    $stmt->close();
                }

                invGiveTx($db, $userId, $itemId, $amount);

                if (tableExists($db,'clan_storage_log')) {
                    $stmt = $db->prepare("INSERT INTO clan_storage_log (clan_id,item_id,user_id,amount,action) VALUES (?,?,?,?, 'take')");
                    $stmt->bind_param('iiii', $clanId, $itemId, $userId, $amount);
                    $stmt->execute();
                    $stmt->close();
                }

                clanAudit($db, $clanId, $userId, 'storage.withdraw', 0, array('item_id'=>$itemId,'count'=>$amount,'date'=>date('d.m H:i'),'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));

                $db->commit();

                $storage = fetchAll($db,
                    "SELECT s.item_id, s.amount, i.name, i.icon FROM clan_storage s LEFT JOIN base_items i ON i.id=s.item_id WHERE s.clan_id=? ORDER BY s.amount DESC, s.item_id ASC",
                    'i',
                    array($clanId)
                );

                respond(true, 'Предмет выдан со склада.', array('storage'=>$storage));

            } catch (Exception $e) {
                $db->rollback();
                respond(false, $e->getMessage() ? $e->getMessage() : 'Ошибка выдачи.', array());
            }
        }

        // -------------------------
        // Shop
        // -------------------------
        case 'shopList': {
            requireClanSchema($db);
            $clanId = pInt('clan_id', 0);
            if ($clanId <= 0) respond(false, 'Некорректный клан.', array());

            $mem = clanGetMembership($db, $userId);
            if (!$mem || (int)$mem['clan_id'] !== $clanId) respond(false, 'Это не ваш клан.', array());
            clanRequirePerm($db, $userId, 'clan.shop.buy');

            $balance = 0;
            if (tableExists($db,'clan_wallet')) {
                $row = fetchOne($db, "SELECT balance FROM clan_wallet WHERE clan_id=? AND currency='genkars' LIMIT 1", 'i', array($clanId));
                $balance = $row ? (int)$row['balance'] : 0;
            }

            if (!tableExists($db,'clan_shop_catalog')) {
                respond(true, '', array('balance'=>$balance, 'items'=>array()));
            }

            $items = fetchAll($db,
                "SELECT c.id AS catalog_id, c.item_id, c.price, c.currency, c.min_clan_level, c.weekly_limit_clan, bi.name, bi.icon"
                . " FROM clan_shop_catalog c LEFT JOIN base_items bi ON bi.id=c.item_id"
                . " WHERE c.is_enabled=1 ORDER BY c.sort_order ASC, c.id ASC",
                '',
                array()
            );

            $pMap = array();
            if (tableExists($db,'clan_shop_purchases')) {
                $p = fetchAll($db,
                    "SELECT catalog_id, SUM(amount) AS cnt FROM clan_shop_purchases WHERE clan_id=? AND YEARWEEK(created_at, 1)=YEARWEEK(NOW(),1) GROUP BY catalog_id",
                    'i',
                    array($clanId)
                );
                for ($i=0; $i<count($p); $i++) {
                    $pMap[(int)$p[$i]['catalog_id']] = (int)$p[$i]['cnt'];
                }
            }

            $cl = fetchOne($db, "SELECT level FROM clans WHERE id=? LIMIT 1", 'i', array($clanId));
            $clanLevel = $cl ? (int)$cl['level'] : 1;

            $out = array();
            for ($i=0; $i<count($items); $i++) {
                $it = $items[$i];
                $catId = (int)$it['catalog_id'];
                $limit = (int)$it['weekly_limit_clan'];
                $spent = isset($pMap[$catId]) ? (int)$pMap[$catId] : 0;
                $remaining = ($limit > 0) ? max(0, $limit - $spent) : 999999;

                // скрываем недоступное по уровню
                if ($clanLevel < (int)$it['min_clan_level']) {
                    // можно вернуть, но фронт не рендерит "disabled" по уровню — проще скрыть
                    continue;
                }

                $out[] = array(
                    'catalog_id' => $catId,
                    'item_id' => (int)$it['item_id'],
                    'name' => $it['name'] ? $it['name'] : ('#' . (int)$it['item_id']),
                    'icon' => $it['icon'] ? $it['icon'] : '',
                    'price' => (int)$it['price'],
                    'currency' => $it['currency'],
                    'min_clan_level' => (int)$it['min_clan_level'],
                    'weekly_limit_clan' => $limit,
                    'remaining' => $remaining,
                );
            }

            respond(true, '', array('balance'=>$balance, 'items'=>$out));
        }

        case 'shopBuy': {
            requireClanSchema($db);
            $clanId = pInt('clan_id', 0);
            $catalogId = pInt('catalog_id', 0);
            $amount = pInt('amount', 1);
            if ($amount < 1) $amount = 1;
            if ($clanId <= 0 || $catalogId <= 0) respond(false, 'Некорректные данные.', array());

            $mem = clanGetMembership($db, $userId);
            if (!$mem || (int)$mem['clan_id'] !== $clanId) respond(false, 'Это не ваш клан.', array());
            clanRequirePerm($db, $userId, 'clan.shop.buy');

            if (!tableExists($db,'clan_shop_catalog')) respond(false, 'Магазин клана не настроен.', array());

            $cat = fetchOne($db,
                "SELECT id, item_id, price, currency, min_clan_level, weekly_limit_clan FROM clan_shop_catalog WHERE id=? AND is_enabled=1 LIMIT 1",
                'i',
                array($catalogId)
            );
            if (!$cat) respond(false, 'Товар не найден.', array());

            $clan = fetchOne($db, "SELECT level FROM clans WHERE id=? AND is_deleted=0 LIMIT 1", 'i', array($clanId));
            if (!$clan) respond(false, 'Клан не найден.', array());
            if ((int)$clan['level'] < (int)$cat['min_clan_level']) respond(false, 'Недостаточный уровень клана для покупки.', array());

            $limit = (int)$cat['weekly_limit_clan'];
            if ($limit > 0 && tableExists($db,'clan_shop_purchases')) {
                $row = fetchOne($db,
                    "SELECT COALESCE(SUM(amount),0) AS cnt FROM clan_shop_purchases WHERE clan_id=? AND catalog_id=? AND YEARWEEK(created_at,1)=YEARWEEK(NOW(),1)",
                    'ii',
                    array($clanId, $catalogId)
                );
                $spent = $row ? (int)$row['cnt'] : 0;
                if ($spent + $amount > $limit) respond(false, 'Превышен недельный лимит клана на этот товар.', array());
            }

            $cost = (int)$cat['price'] * $amount;
            if ($cat['currency'] !== 'clan') respond(false, 'Поддерживается только валюта клана.', array());

            $db->begin_transaction();
            try {
                $bal = walletGetForUpdate($db, $clanId);
                if ($bal < $cost) throw new Exception('Недостаточно средств на счёте клана.');

                $new = $bal - $cost;
                walletSet($db, $clanId, $new);
                walletLedger($db, $clanId, $userId, -$cost, 'shop.buy', array('catalog_id'=>$catalogId,'item_id'=>(int)$cat['item_id'],'amount'=>$amount,'cost'=>$cost));

                invGiveTx($db, $userId, (int)$cat['item_id'], $amount);

                if (tableExists($db,'clan_shop_purchases')) {
                    $stmt = $db->prepare("INSERT INTO clan_shop_purchases (clan_id, user_id, catalog_id, item_id, amount, cost) VALUES (?,?,?,?,?,?)");
                    $itemId = (int)$cat['item_id'];
                    $stmt->bind_param('iiiiii', $clanId, $userId, $catalogId, $itemId, $amount, $cost);
                    $stmt->execute();
                    $stmt->close();
                }

                clanAudit($db, $clanId, $userId, 'shop.buy', 0, array('item_id'=>(int)$cat['item_id'],'count'=>$amount,'cost'=>$cost,'date'=>date('d.m H:i'),'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));

                $db->commit();
                respond(true, 'Покупка успешна.', array('balance'=>$new));
            } catch (Exception $e) {
                $db->rollback();
                respond(false, $e->getMessage() ? $e->getMessage() : 'Ошибка покупки.', array());
            }
        }

        // -------------------------
        // Factions (backend API for trainer card / clan profile)
        // -------------------------
        case 'factionsList': {
            requireClanSchema($db);
            if (!tableExists($db,'clan_factions')) {
                respond(true, '', array('items'=>array()));
            }
            $rows = fetchAll($db, "SELECT id, code, name, badge_color FROM clan_factions WHERE is_enabled=1 ORDER BY sort_order ASC, id ASC", '', array());
            respond(true, '', array('items'=>$rows));
        }

        case 'setFaction': {
            requireClanSchema($db);
            $mem = clanGetMembership($db, $userId);
            if (!$mem) respond(false, 'Вы не состоите в клане.', array());
            clanRequirePerm($db, $userId, 'clan.faction.set');

            $clanId = (int)$mem['clan_id'];
            $factionId = pInt('faction_id', 0);
            if ($factionId <= 0) respond(false, 'Выберите фракцию.', array());

            if (!columnExists($db,'clans','faction_id')) respond(false, 'В таблице clans нет поля faction_id (не установлена миграция фракций).', array());

            $row = fetchOne($db, "SELECT id FROM clan_factions WHERE id=? AND is_enabled=1 LIMIT 1", 'i', array($factionId));
            if (!$row) respond(false, 'Фракция не найдена.', array());

            $stmt = $db->prepare("UPDATE clans SET faction_id=? WHERE id=?");
            $stmt->bind_param('ii', $factionId, $clanId);
            $stmt->execute();
            $stmt->close();

            clanAudit($db, $clanId, $userId, 'clan.faction.set', 0, array('faction_id'=>$factionId, 'date'=>date('d.m H:i'), 'user_new'=>isset($_SESSION['login'])?$_SESSION['login']:''));
            respond(true, 'Фракция клана обновлена.', array());
        }

        // -------------------------
        // Default
        // -------------------------
        default:
            respond(false, 'Неизвестное действие.', array());
    }

} catch (Exception $e) {
    // Глобальная защита: не проливаем stacktrace в прод
    respond(false, 'Ошибка сервера: '.$e->getMessage(), array());
}


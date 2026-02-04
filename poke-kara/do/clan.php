<?php
/**
 * clan.php — переписанный модуль кланов (единый контроллер)
 *
 * Возможности:
 *  - Унифицированные JSON-ответы (respond)
 *  - Только prepared statements; транзакции на чувствительных операциях
 *  - Клановый банк (пожертвования → XP; списания на покупки/апгрейды)
 *  - Корректный склад клана (upsert, логи, роли)
 *  - Клановый магазин (каталог, типы стока, недельные лимиты)
 *  - Клановые локации (уровни, производство по времени, сбор, апгрейды)
 *  - CSRF-проверка на всех мутирующих действиях
 *
 * -------------------------------------------
 * SQL МИГРАЦИИ (запустить один раз; адаптируйте под вашу БД)
 * -------------------------------------------
 * -- Расширение таблицы кланов
 * ALTER TABLE `base_clans`
 *   ADD COLUMN `bank_balance` BIGINT NOT NULL DEFAULT 0,
 *   ADD COLUMN `level` INT NOT NULL DEFAULT 1,
 *   ADD COLUMN `xp` BIGINT NOT NULL DEFAULT 0;
 *
 * -- Леджер движений клановой валюты
 * CREATE TABLE IF NOT EXISTS `clan_currency_ledger` (
 *   `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
 *   `clan_id` INT NOT NULL,
 *   `user_id` INT NULL,
 *   `delta` BIGINT NOT NULL,
 *   `kind` ENUM('bank','reward','purchase') NOT NULL,
 *   `reason` VARCHAR(64) NOT NULL,
 *   `meta` JSON NULL,
 *   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   INDEX(`clan_id`), INDEX(`user_id`)
 * ) ENGINE=InnoDB;
 *
 * -- Склад клана
 * CREATE TABLE IF NOT EXISTS `clan_storage` (
 *   `clan_id` INT NOT NULL,
 *   `item_id` INT NOT NULL,
 *   `amount` BIGINT NOT NULL DEFAULT 0,
 *   PRIMARY KEY (`clan_id`,`item_id`)
 * ) ENGINE=InnoDB;
 *
 * CREATE TABLE IF NOT EXISTS `clan_storage_log` (
 *   `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
 *   `clan_id` INT NOT NULL,
 *   `user_id` INT NOT NULL,
 *   `action` ENUM('deposit','withdraw') NOT NULL,
 *   `item_id` INT NOT NULL,
 *   `amount` BIGINT NOT NULL,
 *   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   INDEX(`clan_id`), INDEX(`user_id`), INDEX(`item_id`)
 * ) ENGINE=InnoDB;
 *
 * -- Магазин клана
 * CREATE TABLE IF NOT EXISTS `clan_shop_catalog` (
 *   `id` INT AUTO_INCREMENT PRIMARY KEY,
 *   `item_id` INT NOT NULL,
 *   `price_bank` BIGINT NOT NULL DEFAULT 0,
 *   `min_clan_level` INT NOT NULL DEFAULT 1,
 *   `weekly_limit_per_user` INT NULL,
 *   `stock_type` ENUM('infinite','finite','timed') NOT NULL DEFAULT 'infinite',
 *   `base_stock` INT NULL,
 *   `restock_interval_hours` INT NULL,
 *   `active` TINYINT NOT NULL DEFAULT 1,
 *   INDEX(`item_id`)
 * ) ENGINE=InnoDB;
 *
 * CREATE TABLE IF NOT EXISTS `clan_shop_stock` (
 *   `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
 *   `clan_id` INT NOT NULL,
 *   `catalog_id` INT NOT NULL,
 *   `stock_remaining` INT NULL,
 *   `next_restock_at` DATETIME NULL,
 *   UNIQUE KEY `uniq_clan_catalog` (`clan_id`,`catalog_id`),
 *   INDEX(`next_restock_at`)
 * ) ENGINE=InnoDB;
 *
 * CREATE TABLE IF NOT EXISTS `clan_shop_purchases` (
 *   `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
 *   `clan_id` INT NOT NULL,
 *   `user_id` INT NOT NULL,
 *   `catalog_id` INT NOT NULL,
 *   `qty` INT NOT NULL,
 *   `iso_year` INT NOT NULL,
 *   `iso_week` INT NOT NULL,
 *   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   INDEX(`clan_id`), INDEX(`user_id`), INDEX(`catalog_id`), INDEX(`iso_year`,`iso_week`)
 * ) ENGINE=InnoDB;
 *
 * -- Локации
 * CREATE TABLE IF NOT EXISTS `clan_buildings` (
 *   `clan_id` INT NOT NULL,
 *   `code` VARCHAR(32) NOT NULL,
 *   `level` INT NOT NULL DEFAULT 1,
 *   `stored_yield` BIGINT NOT NULL DEFAULT 0,
 *   `last_tick_at` DATETIME NULL,
 *   PRIMARY KEY (`clan_id`,`code`)
 * ) ENGINE=InnoDB;
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/conf/global.php';
@session_start();

// (опционально) если в проекте функции предметов подключаются отдельно
@include_once $_SERVER['DOCUMENT_ROOT'].'/Functions.php';
@include_once $_SERVER['DOCUMENT_ROOT'].'/Items.php';
@include_once $_SERVER['DOCUMENT_ROOT'].'/Info.php';

header('Content-Type: application/json; charset=utf-8');

/* -----------------------------
   SCHEMA HELPERS (compat)
------------------------------ */
function dbHasTable($table) {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    $stmt = Work::$sql->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
    if (!$stmt) return $cache[$table] = false;
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $ok = (bool)($stmt->get_result()->fetch_assoc() ?? null);
    $stmt->close();
    return $cache[$table] = $ok;
}
function dbHasColumn($table, $column) {
    static $cache = [];
    $k = $table.'|'.$column;
    if (isset($cache[$k])) return $cache[$k];
    $stmt = Work::$sql->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) return $cache[$k] = false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $ok = (bool)($stmt->get_result()->fetch_assoc() ?? null);
    $stmt->close();
    return $cache[$k] = $ok;
}

/* -----------------------------
   CLAN WALLET (v4)
------------------------------ */
function clanWalletEnsureTx($clanId, $currency='genkars') {
    if (!dbHasTable('clan_wallet')) return;
    $stmt = Work::$sql->prepare("INSERT IGNORE INTO clan_wallet (clan_id, currency, balance) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $clanId, $currency);
    $stmt->execute();
    $stmt->close();
}
function clanWalletGetBalanceTx($clanId, $currency='genkars', $forUpdate=true) {
    if (!dbHasTable('clan_wallet')) {
        if (dbHasColumn('base_clans','bank_balance')) {
            $r = fetchOne("SELECT bank_balance AS b FROM base_clans WHERE id=? ".($forUpdate?'FOR UPDATE':''), "i", [$clanId]);
            return (int)($r['b'] ?? 0);
        }
        if (dbHasColumn('base_clans','money')) {
            $r = fetchOne("SELECT money AS b FROM base_clans WHERE id=? ".($forUpdate?'FOR UPDATE':''), "i", [$clanId]);
            return (int)($r['b'] ?? 0);
        }
        return 0;
    }
    clanWalletEnsureTx($clanId, $currency);
    $r = fetchOne("SELECT balance FROM clan_wallet WHERE clan_id=? AND currency=? ".($forUpdate?'FOR UPDATE':''), "is", [$clanId, $currency]);
    return (int)($r['balance'] ?? 0);
}
function clanWalletDeltaTx($clanId, $userId, $delta, $kind='bank', $reason='unknown', $currency='genkars', $meta=[]) {
    $bal = clanWalletGetBalanceTx($clanId, $currency, true);
    $new = $bal + (int)$delta;
    if ($new < 0) throw new Exception('insufficient_funds');

    if (dbHasTable('clan_wallet')) {
        $stmt = Work::$sql->prepare("UPDATE clan_wallet SET balance=?, updated_at=NOW() WHERE clan_id=? AND currency=?");
        $stmt->bind_param("iis", $new, $clanId, $currency);
        $stmt->execute();
        $stmt->close();
    }

    // legacy sync
    if ($currency === 'genkars') {
        if (dbHasColumn('base_clans','bank_balance')) {
            $st = Work::$sql->prepare("UPDATE base_clans SET bank_balance=? WHERE id=?");
            $st->bind_param("ii", $new, $clanId);
            $st->execute(); $st->close();
        } elseif (dbHasColumn('base_clans','money')) {
            $st = Work::$sql->prepare("UPDATE base_clans SET money=? WHERE id=?");
            $st->bind_param("ii", $new, $clanId);
            $st->execute(); $st->close();
        }
    }

    if (dbHasTable('clan_wallet_ledger')) {
        $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
        $stmt2 = Work::$sql->prepare("INSERT INTO clan_wallet_ledger (clan_id, currency, user_id, delta, kind, reason, meta) VALUES (?,?,?,?,?,?,?)");
        $stmt2->bind_param("isiisss", $clanId, $currency, $userId, $delta, $kind, $reason, $metaJson);
        $stmt2->execute();
        $stmt2->close();
    }
    return $new;
}


// === Конфигурация модуля ===
class ClanCfg {
    // ID внутриигровой валюты у пользователя (которую жертвуют в банк)
    const MONEY_ITEM_ID = 1;

    // Соответствие кодов построек → ID предметов, которые они производят (попадают в склад)
    public static array $BUILDING_YIELD_ITEM = [
        'mine'   => 9001, // пример: руда клана
        'garden' => 9002, // пример: травы клана
        // 'lab' => 9003, // пример: катализатор
    ];

    // Настройки построек (примерные значения — подстройте для баланса)
    public static array $BUILDING_CFG = [
        'outpost' => [
            'max_level' => 5,
            'upgrade_cost_bank' => [1=>0,2=>5000,3=>20000,4=>60000,5=>150000],
        ],
        'mine' => [
            'max_level' => 5,
            'base_per_hour' => 10,
            'mult_per_level' => 1.25,
            'cap' => [1=>250,2=>600,3=>1200,4=>2400,5=>4000],
            'upgrade_cost_bank' => [1=>0,2=>4000,3=>12000,4=>36000,5=>90000],
        ],
        'garden' => [
            'max_level' => 5,
            'base_per_hour' => 6,
            'mult_per_level' => 1.30,
            'cap' => [1=>180,2=>420,3=>900,4=>1600,5=>3000],
            'upgrade_cost_bank' => [1=>0,2=>3000,3=>9000,4=>27000,5=>80000],
        ],
        'lab' => [
            'max_level' => 3,
            'upgrade_cost_bank' => [1=>0,2=>25000,3=>80000],
        ],
        'dojo' => [
            'max_level' => 3,
            'upgrade_cost_bank' => [1=>0,2=>15000,3=>45000],
        ],
    ];
}

// === Унифицированный ответ ===
function respond($ok, $text = '', array $data = [])
{
    $base = [
        'error' => $ok ? 0 : 1,
        'text'  => (string)$text,
    ];
    echo json_encode(array_merge($base, $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// === Безопасные геттеры ===
function pStr($arr, $key, $def = '') { return isset($arr[$key]) ? clearStr((string)$arr[$key]) : $def; }
function pInt($arr, $key, $def = 0)  { return isset($arr[$key]) ? (int)$arr[$key] : (int)$def; }

// === CSRF на мутирующие действия ===
function requireCsrf() {
    $t = $_POST['csrf'] ?? '';
    $s = $_SESSION['csrf_token'] ?? '';
    if (!$t || !$s || !hash_equals($s, $t)) {
        respond(false, 'CSRF: неверный токен.');
    }
}

// === Вспомогательные утилиты БД ===
function fetchOne($sql, $types = '', $params = []) {
    $stmt = Work::$sql->prepare($sql);
    if (!$stmt) return null;
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}
function fetchAll($sql, $types = '', $params = []) {
    $stmt = Work::$sql->prepare($sql);
    if (!$stmt) return [];
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}
function beginTx() { Work::$sql->begin_transaction(); }
function commitTx() { Work::$sql->commit(); }
function rollbackTx() { Work::$sql->rollback(); }
function isoWeekYear(DateTime $dt) { return [(int)$dt->format('o'), (int)$dt->format('W')]; }

// === Авторизация ===
$userId = (int)($_SESSION['id'] ?? 0);
if ($userId <= 0) respond(false, 'Необходима авторизация.');

// === Получаем объект действия и значения из POST ===
$object = isset($_POST['object']) ? trim((string)$_POST['object']) : '';
$val    = $_POST['val'] ?? [];
if (!is_array($val)) {
    if (is_string($val) && strlen($val)) $val = explode('|', $val); else $val = [];
}

// === Пользователь ===
$userQuery = Work::$sql->prepare("SELECT id, login, user_group FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userInfo = $userQuery->get_result()->fetch_assoc();
$userQuery->close();
if (!$userInfo) respond(false, 'Пользователь не найден.');

// === Клан/роль пользователя ===
function getClanMembership($userId) {
    $roleExpr = dbHasColumn('base_clans_users','group_clan') ? "COALESCE(cu.`group`, cu.group_clan)" : "cu.`group`";

    $stmt = Work::$sql->prepare("
        SELECT cu.clan_id AS clan_id, $roleExpr AS role
        FROM base_clans_users cu
        WHERE cu.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $m = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$m) return null;

    $clanId = (int)$m['clan_id'];
    $role = (int)($m['role'] ?? 3);

    // level/xp (совместимость)
    $level = 1;
    if (dbHasColumn('base_clans','level')) {
        $r = fetchOne("SELECT level FROM base_clans WHERE id=? LIMIT 1", "i", [$clanId]);
        $level = (int)($r['level'] ?? 1);
    }
    $xp = 0;
    if (dbHasColumn('base_clans','xp')) {
        $r = fetchOne("SELECT xp FROM base_clans WHERE id=? LIMIT 1", "i", [$clanId]);
        $xp = (int)($r['xp'] ?? 0);
    } elseif (dbHasColumn('base_clans','exp')) {
        $r = fetchOne("SELECT exp FROM base_clans WHERE id=? LIMIT 1", "i", [$clanId]);
        $xp = (int)($r['exp'] ?? 0);
    }

    $bankBalance = clanWalletGetBalanceTx($clanId, 'genkars', false);

    return [
        'clan_id' => $clanId,
        'role' => $role,
        'level' => $level,
        'xp' => $xp,
        'bank_balance' => $bankBalance,
    ];
}

function isLeader($userId, &$clanId) {
    $m = getClanMembership($userId);
    if ($m && (int)$m['role'] === 1) { $clanId = (int)$m['clan_id']; return true; }
    return false;
}
function requireMembership($userId, &$clanId, &$role, &$clanLevel, &$clanXp, &$bankBalance) {
    $m = getClanMembership($userId);
    if (!$m) respond(false, 'Вы не состоите в клане.');
    $clanId = (int)$m['clan_id'];
    $role = (int)$m['role'];
    $clanLevel = (int)$m['level'];
    $clanXp = (int)$m['xp'];
    $bankBalance = (int)$m['bank_balance'];
}
function ensureMinRole($role, $required /*1..3*/) {
    // 1=leader, 2=officer, 3=member; чем меньше — тем выше право
    if ((int)$role > (int)$required) respond(false, 'Недостаточно прав.');
}

// === Лог клана (в вашу общую лог-таблицу) ===
function addClanLog($clanId, $title, array $metaJson) {
    $json = json_encode($metaJson, JSON_UNESCAPED_UNICODE);
    $type = 'clan';
    $stmt = Work::$sql->prepare("INSERT INTO log_game (user_id, type, title, info) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $clanId, $type, $title, $json);
    $stmt->execute();
    $stmt->close();
}

// === XP клана и банк (внутри транзакций) ===
function clanXpAddTx($clanId, $amount) {
    $row = fetchOne("SELECT level, xp FROM base_clans WHERE id=? FOR UPDATE", "i", [$clanId]);
    if (!$row) throw new Exception('clan_not_found');
    $xp = (int)$row['xp'] + (int)$amount;
    $lvl = (int)$row['level'];
    while ($xp >= 1000) { $xp -= 1000; $lvl++; }
    $stmt = Work::$sql->prepare("UPDATE base_clans SET xp=?, level=? WHERE id=?");
    $stmt->bind_param("iii", $xp, $lvl, $clanId);
    $stmt->execute();
    $stmt->close();
}
function clanBankDeltaTx($clanId, $userId, $delta, $reason, $kind='bank', $meta=[]) {
    // v4: clan_wallet + ledger (с синхронизацией legacy полей)
    $new = clanWalletDeltaTx($clanId, $userId, (int)$delta, $kind, $reason, 'genkars', $meta);
    if ($new < 0) throw new Exception('insufficient_funds');
}


// === Адаптеры предметов (тонкие обёртки к вашим функциям) ===
class UserItems {
    public static function has($userId, $itemId, $count) {
        if (function_exists('item_isset')) return (bool)item_isset((int)$itemId, (int)$count, (int)$userId);
        $row = fetchOne("SELECT COALESCE(SUM(amount),0) AS s FROM items_users WHERE user=? AND item_id=?", "ii", [$userId,$itemId]);
        return (int)($row['s'] ?? 0) >= (int)$count;
    }
    public static function give($userId, $itemId, $count, $dop=0) {
        if (function_exists('itemAdd')) { itemAdd((int)$itemId, (int)$count, (int)$userId, $dop); return true; }
        $stmt = Work::$sql->prepare("INSERT INTO items_users (user,item_id,amount) VALUES (?,?,?) ON DUPLICATE KEY UPDATE amount=amount+VALUES(amount)");
        $stmt->bind_param("iii", $userId, $itemId, $count);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    public static function take($userId, $itemId, $count) {
        if (function_exists('minus_item')) return (bool)minus_item((int)$itemId, (int)$count, (int)$userId);
        // Простейший фолбэк
        beginTx();
        try {
            $row = fetchOne("SELECT amount FROM items_users WHERE user=? AND item_id=? FOR UPDATE", "ii", [$userId,$itemId]);
            if (!$row || (int)$row['amount'] < (int)$count) throw new Exception('no_items');
            $new = (int)$row['amount'] - (int)$count;
            if ($new > 0) {
                $u = Work::$sql->prepare("UPDATE items_users SET amount=? WHERE user=? AND item_id=?");
                $u->bind_param("iii", $new, $userId, $itemId);
                $u->execute(); $u->close();
            } else {
                $d = Work::$sql->prepare("DELETE FROM items_users WHERE user=? AND item_id=?");
                $d->bind_param("ii", $userId, $itemId);
                $d->execute(); $d->close();
            }
            commitTx(); return true;
        } catch (Throwable $e) { rollbackTx(); return false; }
    }
}

// === Проверка, что предмет стэкуемый (без expiration/cool) — для корректного склада (Фаза 1) ===
function itemIsStackable($itemId) {
    $row = fetchOne("SELECT str, cool, expiration FROM base_items WHERE id=? LIMIT 1", "i", [$itemId]);
    if (!$row) return false;
    $str = (string)($row['str'] ?? '0'); // у вас str='0' обычно означает стэк
    $cool = (int)($row['cool'] ?? 0);
    $exp = (int)($row['expiration'] ?? 0);
    return ($str === '0' && $cool === 0 && $exp === 0);
}

// === SWITCH действий ===
switch ($object) {

    // -------------------------------------------
    // БАНК КЛАНА
    // -------------------------------------------
    case 'bankInfo': {
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        respond(true, '', [
            'bank_balance' => (int)$bankBalance,
            'level' => (int)$clanLevel,
            'xp' => (int)$clanXp,
        ]);
    }

    case 'bankDonate': {
        requireCsrf();
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);

        $amount = max(0, pInt($_POST, 'amount', 0));
        if ($amount <= 0) respond(false, 'Сумма должна быть > 0');

        if (!UserItems::has($userId, ClanCfg::MONEY_ITEM_ID, $amount)) respond(false, 'Недостаточно средств.');

        beginTx();
        try {
            if (!UserItems::take($userId, ClanCfg::MONEY_ITEM_ID, $amount)) throw new Exception('Не удалось списать средства.');
            clanBankDeltaTx($clanId, $userId, +$amount, 'donation', 'bank');
            $xpGain = (int)floor($amount / 100); // 1 XP за 100 валюты
            if ($xpGain > 0) clanXpAddTx($clanId, $xpGain);

            addClanLog($clanId, 'CLAN_DONATION', [
                'user' => $userInfo['login'],
                'amount' => $amount,
                'xp_gain' => $xpGain,
                'date' => date('Y-m-d H:i:s'),
            ]);

            commitTx();
            respond(true, 'Пожертвование учтено.', ['donated' => $amount, 'xp_gain' => $xpGain]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    // -------------------------------------------
    // СКЛАД КЛАНА
    // -------------------------------------------
    case 'bankWithdraw': {
        requireCsrf();
        requireOfficerOrLeader($role);
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);

        $amount = max(0, pInt($_POST, 'amount', 0));
        if ($amount <= 0) respond(false, 'Сумма должна быть > 0');

        beginTx();
        try {
            clanBankDeltaTx($clanId, $userId, -$amount, 'withdraw', 'bank', ['to_user'=>$userId]);

            // Выдаём игроку валюту (item id)
            if (!UserItems::give($userId, ClanCfg::MONEY_ITEM_ID, $amount)) throw new Exception('Не удалось выдать средства.');

            addClanLog($clanId, 'CLAN_WITHDRAW', [
                'user' => $userInfo['login'],
                'amount' => $amount,
            ]);

            commitTx();
            respond(true, 'Средства выданы.', [
                'bank_balance' => (int)clanWalletGetBalanceTx($clanId, 'genkars', false),
            ]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    case 'storageOpen': {
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        $rows = fetchAll("
            SELECT cs.item_id, cs.amount, bi.name, bi.type
            FROM clan_storage cs
            JOIN base_items bi ON bi.id = cs.item_id
            WHERE cs.clan_id = ?
            ORDER BY bi.type, bi.id
        ", "i", [$clanId]);

        respond(true, '', ['items' => $rows]);
    }

    case 'storageDeposit': {
        requireCsrf();
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);

        $itemId = pInt($_POST, 'item_id', 0);
        $amount = max(0, pInt($_POST, 'amount', 0));
        if ($itemId <= 0 || $amount <= 0) respond(false, 'Некорректные параметры.');

        // Фаза 1: принимаем только стэкуемые предметы
        if (!itemIsStackable($itemId)) respond(false, 'Этот предмет нельзя сдавать на склад.');

        if (!UserItems::has($userId, $itemId, $amount)) respond(false, 'Недостаточно предметов.');

        beginTx();
        try {
            if (!UserItems::take($userId, $itemId, $amount)) throw new Exception('Не удалось забрать предметы у пользователя.');

            // upsert в склад
            $stmt = Work::$sql->prepare("
                INSERT INTO clan_storage (clan_id, item_id, amount)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
            ");
            $stmt->bind_param("iii", $clanId, $itemId, $amount);
            $stmt->execute(); $stmt->close();

            // лог
            $act = 'deposit';
            $log = Work::$sql->prepare("INSERT INTO clan_storage_log (clan_id, user_id, action, item_id, amount) VALUES (?,?,?,?,?)");
            $log->bind_param("iisii", $clanId, $userId, $act, $itemId, $amount);
            $log->execute(); $log->close();

            // немножко XP за вклад (кап 50 за раз)
            clanXpAddTx($clanId, min($amount, 50));

            commitTx();
            respond(true, 'Внесено на склад.', ['item_id' => $itemId, 'amount' => $amount]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    case 'storageWithdraw': {
        requireCsrf();
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        ensureMinRole($role, 2); // только офицер+

        $itemId = pInt($_POST, 'item_id', 0);
        $amount = max(0, pInt($_POST, 'amount', 0));
        if ($itemId <= 0 || $amount <= 0) respond(false, 'Некорректные параметры.');

        beginTx();
        try {
            $row = fetchOne("SELECT amount FROM clan_storage WHERE clan_id=? AND item_id=? FOR UPDATE", "ii", [$clanId,$itemId]);
            if (!$row || (int)$row['amount'] < $amount) throw new Exception('Недостаточно на складе.');

            $new = (int)$row['amount'] - $amount;
            if ($new > 0) {
                $u = Work::$sql->prepare("UPDATE clan_storage SET amount=? WHERE clan_id=? AND item_id=?");
                $u->bind_param("iii", $new, $clanId, $itemId);
                $u->execute(); $u->close();
            } else {
                $d = Work::$sql->prepare("DELETE FROM clan_storage WHERE clan_id=? AND item_id=?");
                $d->bind_param("ii", $clanId, $itemId);
                $d->execute(); $d->close();
            }

            if (!UserItems::give($userId, $itemId, $amount)) throw new Exception('Не удалось выдать предметы пользователю.');

            $act = 'withdraw';
            $log = Work::$sql->prepare("INSERT INTO clan_storage_log (clan_id, user_id, action, item_id, amount) VALUES (?,?,?,?,?)");
            $log->bind_param("iisii", $clanId, $userId, $act, $itemId, $amount);
            $log->execute(); $log->close();

            commitTx();
            respond(true, 'Выдано со склада.', ['item_id' => $itemId, 'amount' => $amount]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    case 'storageLog': {
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        ensureMinRole($role, 2); // офицер+
        $rows = fetchAll("SELECT * FROM clan_storage_log WHERE clan_id=? ORDER BY id DESC LIMIT 200", "i", [$clanId]);
        respond(true, '', ['log' => $rows]);
    }

    // -------------------------------------------
    // МАГАЗИН КЛАНА
    // -------------------------------------------
    case 'shopList': {
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);

        $cats = fetchAll("SELECT * FROM clan_shop_catalog WHERE active=1 ORDER BY id");
        $now = new DateTime(); [$isoY,$isoW] = isoWeekYear($now);
        $out = [];

        foreach ($cats as $cat) {
            if ($clanLevel < (int)$cat['min_clan_level']) continue;

            // ensure stock row
            $stockRow = fetchOne("SELECT stock_remaining, next_restock_at FROM clan_shop_stock WHERE clan_id=? AND catalog_id=?", "ii", [$clanId, $cat['id']]);
            if (!$stockRow) {
                // создаём дефолтную строку
                $stock = null; $next = null;
                if ($cat['stock_type'] === 'finite') $stock = (int)$cat['base_stock'];
                if ($cat['stock_type'] === 'timed') {
                    $stock = (int)$cat['base_stock'];
                    $h = max(1, (int)$cat['restock_interval_hours']);
                    $next = (new DateTime('+'.$h.' hours'))->format('Y-m-d H:i:s');
                }
                $ins = Work::$sql->prepare("INSERT INTO clan_shop_stock (clan_id,catalog_id,stock_remaining,next_restock_at) VALUES (?,?,?,?)");
                $ins->bind_param("iiis", $clanId, $cat['id'], $stock, $next);
                $ins->execute(); $ins->close();
                $stockRow = ['stock_remaining'=>$stock, 'next_restock_at'=>$next];
            } else {
                // если timed и пора — «ленивый» ресток
                if ($cat['stock_type'] === 'timed' && $stockRow['next_restock_at']) {
                    $nowDt = new DateTime(); $next = new DateTime($stockRow['next_restock_at']);
                    if ($nowDt >= $next) {
                        $stock = (int)$cat['base_stock'];
                        $h = max(1, (int)$cat['restock_interval_hours']);
                        $next2 = (new DateTime('+'.$h.' hours'))->format('Y-m-d H:i:s');
                        $u = Work::$sql->prepare("UPDATE clan_shop_stock SET stock_remaining=?, next_restock_at=? WHERE clan_id=? AND catalog_id=?");
                        $u->bind_param("isii", $stock, $next2, $clanId, $cat['id']);
                        $u->execute(); $u->close();
                        $stockRow['stock_remaining'] = $stock;
                        $stockRow['next_restock_at'] = $next2;
                    }
                }
            }

            // weekly limit для пользователя
            $left = null;
            if (!is_null($cat['weekly_limit_per_user'])) {
                $r = fetchOne("
                    SELECT COALESCE(SUM(qty),0) AS q
                    FROM clan_shop_purchases
                    WHERE clan_id=? AND user_id=? AND catalog_id=? AND iso_year=? AND iso_week=?
                ", "iiiii", [$clanId,$userId,$cat['id'],$isoY,$isoW]);
                $used = (int)($r['q'] ?? 0);
                $lim = (int)$cat['weekly_limit_per_user'];
                $left = max(0, $lim - $used);
            }

            $out[] = [
                'catalog_id' => (int)$cat['id'],
                'item_id' => (int)$cat['item_id'],
                'price_bank' => (int)$cat['price_bank'],
                'min_clan_level' => (int)$cat['min_clan_level'],
                'stock_type' => (string)$cat['stock_type'],
                'stock_remaining' => $stockRow['stock_remaining'],
                'next_restock_at' => $stockRow['next_restock_at'],
                'weekly_limit_left' => $left,
            ];
        }

        respond(true, '', ['items' => $out]);
    }

    case 'shopBuy': {
        requireCsrf();
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        // member+ может покупать
        $catalogId = pInt($_POST, 'catalog_id', 0);
        $qty = max(1, pInt($_POST, 'qty', 1));
        if ($catalogId <= 0) respond(false, 'Некорректный товар.');

        $cat = fetchOne("SELECT * FROM clan_shop_catalog WHERE id=? AND active=1", "i", [$catalogId]);
        if (!$cat) respond(false, 'Товар не найден.');
        if ($clanLevel < (int)$cat['min_clan_level']) respond(false, 'Недостаточный уровень клана.');

        $now = new DateTime(); [$isoY,$isoW] = isoWeekYear($now);

        beginTx();
        try {
            // блокируем строку стока
            $row = fetchOne("SELECT stock_remaining, next_restock_at FROM clan_shop_stock WHERE clan_id=? AND catalog_id=? FOR UPDATE", "ii", [$clanId,$catalogId]);
            if (!$row) {
                // если не было — создаём и перечитываем под блокировкой
                $ins = Work::$sql->prepare("INSERT INTO clan_shop_stock (clan_id,catalog_id,stock_remaining,next_restock_at) VALUES (?,?,NULL,NULL)");
                $ins->bind_param("ii", $clanId, $catalogId);
                $ins->execute(); $ins->close();
                $row = fetchOne("SELECT stock_remaining, next_restock_at FROM clan_shop_stock WHERE clan_id=? AND catalog_id=? FOR UPDATE", "ii", [$clanId,$catalogId]);
            }

            // timed restock под замком
            if ($cat['stock_type'] === 'timed' && $row['next_restock_at']) {
                $next = new DateTime($row['next_restock_at']);
                if ($now >= $next) {
                    $stock = (int)$cat['base_stock'];
                    $h = max(1, (int)$cat['restock_interval_hours']);
                    $next2 = (new DateTime('+'.$h.' hours'))->format('Y-m-d H:i:s');
                    $u = Work::$sql->prepare("UPDATE clan_shop_stock SET stock_remaining=?, next_restock_at=? WHERE clan_id=? AND catalog_id=?");
                    $u->bind_param("isii", $stock, $next2, $clanId, $catalogId);
                    $u->execute(); $u->close();
                    $row['stock_remaining'] = $stock; $row['next_restock_at'] = $next2;
                }
            }

            // недельный лимит
            if (!is_null($cat['weekly_limit_per_user'])) {
                $r = fetchOne("
                    SELECT COALESCE(SUM(qty),0) AS q
                    FROM clan_shop_purchases
                    WHERE clan_id=? AND user_id=? AND catalog_id=? AND iso_year=? AND iso_week=?
                    FOR UPDATE
                ", "iiiii", [$clanId,$userId,$catalogId,$isoY,$isoW]);
                $used = (int)($r['q'] ?? 0);
                $lim = (int)$cat['weekly_limit_per_user'];
                if ($used + $qty > $lim) throw new Exception('Превышен недельный лимит.');
            }

            // проверка/списание стока
            if (in_array($cat['stock_type'], ['finite','timed'], true)) {
                $rem = (int)($row['stock_remaining'] ?? 0);
                if ($rem < $qty) throw new Exception('Недостаточно товара.');
                $new = $rem - $qty;
                $u = Work::$sql->prepare("UPDATE clan_shop_stock SET stock_remaining=? WHERE clan_id=? AND catalog_id=?");
                $u->bind_param("iii", $new, $clanId, $catalogId);
                $u->execute(); $u->close();
            }

            // цена и списание из банка
            $price = (int)$cat['price_bank'] * $qty;
            clanBankDeltaTx($clanId, $userId, -$price, 'shop_buy', 'purchase', ['catalog_id'=>$catalogId,'qty'=>$qty]);

            // выдаём предмет
            if (!UserItems::give($userId, (int)$cat['item_id'], (int)$qty)) throw new Exception('Не удалось выдать предмет.');

            // запись покупки
            $ins = Work::$sql->prepare("
                INSERT INTO clan_shop_purchases (clan_id,user_id,catalog_id,qty,iso_year,iso_week)
                VALUES (?,?,?,?,?,?)
            ");
            $ins->bind_param("iiiiii", $clanId, $userId, $catalogId, $qty, $isoY, $isoW);
            $ins->execute(); $ins->close();

            addClanLog($clanId, 'CLAN_SHOP_BUY', [
                'user' => $userInfo['login'],
                'catalog_id' => $catalogId,
                'qty' => $qty,
                'price' => $price,
                'date' => date('Y-m-d H:i:s'),
            ]);

            commitTx();
            respond(true, 'Покупка успешна.', ['item_id' => (int)$cat['item_id'], 'qty' => $qty, 'price_paid' => $price]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    // -------------------------------------------
    // ЛОКАЦИИ / ПОСТРОЙКИ
    // -------------------------------------------
    case 'locationsList': {
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);

        // гарантируем наличие дефолтных построек
        $codes = ['outpost','mine','garden','lab','dojo'];
        foreach ($codes as $code) {
            $row = fetchOne("SELECT 1 FROM clan_buildings WHERE clan_id=? AND code=? LIMIT 1", "is", [$clanId,$code]);
            if (!$row) {
                $now = date('Y-m-d H:i:s');
                $ins = Work::$sql->prepare("INSERT INTO clan_buildings (clan_id,code,level,stored_yield,last_tick_at) VALUES (?,?,1,0,?)");
                $ins->bind_param("iss", $clanId, $code, $now);
                $ins->execute(); $ins->close();
            }
        }

        // лениво «тики» (без блокировок, достаточно update last_tick_at + накопление)
        foreach ($codes as $code) {
            // лёгкая версия tick: читаем текущее, считаем приращение, обновляем
            $b = fetchOne("SELECT level, stored_yield, last_tick_at FROM clan_buildings WHERE clan_id=? AND code=?", "is", [$clanId,$code]);
            if (!$b) continue;
            $level = (int)$b['level'];
            $cfg = ClanCfg::$BUILDING_CFG[$code] ?? null;
            if (!$cfg) continue;

            $rate = 0.0;
            if (!empty($cfg['base_per_hour'])) {
                $base = (float)$cfg['base_per_hour'];
                $mult = (float)($cfg['mult_per_level'] ?? 1.0);
                $rate = $base * pow($mult, max(0, $level-1));
            }
            if ($rate <= 0) continue;

            $last = $b['last_tick_at'] ? new DateTime($b['last_tick_at']) : new DateTime();
            $now = new DateTime();
            $diffSec = max(0, $now->getTimestamp() - $last->getTimestamp());
            $prod = (int)floor(($rate/3600.0) * $diffSec);
            if ($prod > 0) {
                $capList = $cfg['cap'] ?? [];
                $cap = (int)($capList[$level] ?? 999999999);
                $newStored = min($cap, (int)$b['stored_yield'] + $prod);
                $u = Work::$sql->prepare("UPDATE clan_buildings SET stored_yield=?, last_tick_at=? WHERE clan_id=? AND code=?");
                $ts = $now->format('Y-m-d H:i:s');
                $u->bind_param("isis", $newStored, $ts, $clanId, $code);
                $u->execute(); $u->close();
            } else {
                // всё равно обновим last_tick, чтобы не было дрейфа
                $u = Work::$sql->prepare("UPDATE clan_buildings SET last_tick_at=? WHERE clan_id=? AND code=?");
                $ts = (new DateTime())->format('Y-m-d H:i:s');
                $u->bind_param("sis", $ts, $clanId, $code);
                $u->execute(); $u->close();
            }
        }

        $rows = fetchAll("SELECT code, level, stored_yield, last_tick_at FROM clan_buildings WHERE clan_id=? ORDER BY code", "i", [$clanId]);
        respond(true, '', ['buildings' => $rows]);
    }

    case 'locationsCollect': {
        requireCsrf();
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        ensureMinRole($role, 2); // офицер+

        $code = pStr($_POST, 'code', '');
        if ($code === '') respond(false, 'Не указана локация.');

        beginTx();
        try {
            // под замком считаем тики и снимаем накопленное
            $b = fetchOne("SELECT level, stored_yield, last_tick_at FROM clan_buildings WHERE clan_id=? AND code=? FOR UPDATE", "is", [$clanId,$code]);
            if (!$b) throw new Exception('Локация не найдена.');

            // доначисляем прямо сейчас (точная версия)
            $level = (int)$b['level'];
            $cfg = ClanCfg::$BUILDING_CFG[$code] ?? null;
            if ($cfg && !empty($cfg['base_per_hour'])) {
                $rate = (float)$cfg['base_per_hour'] * pow((float)($cfg['mult_per_level'] ?? 1.0), max(0,$level-1));
                $last = $b['last_tick_at'] ? new DateTime($b['last_tick_at']) : new DateTime();
                $now = new DateTime();
                $diffSec = max(0, $now->getTimestamp() - $last->getTimestamp());
                $prod = (int)floor(($rate/3600.0) * $diffSec);
                if ($prod > 0) {
                    $capList = $cfg['cap'] ?? [];
                    $cap = (int)($capList[$level] ?? 999999999);
                    $newStored = min($cap, (int)$b['stored_yield'] + $prod);
                    $u = Work::$sql->prepare("UPDATE clan_buildings SET stored_yield=?, last_tick_at=? WHERE clan_id=? AND code=?");
                    $ts = $now->format('Y-m-d H:i:s');
                    $u->bind_param("isis", $newStored, $ts, $clanId, $code);
                    $u->execute(); $u->close();
                    $b['stored_yield'] = $newStored;
                } else {
                    $u = Work::$sql->prepare("UPDATE clan_buildings SET last_tick_at=? WHERE clan_id=? AND code=?");
                    $ts = (new DateTime())->format('Y-m-d H:i:s');
                    $u->bind_param("sis", $ts, $clanId, $code);
                    $u->execute(); $u->close();
                }
            }

            $amount = (int)$b['stored_yield'];
            if ($amount <= 0) { commitTx(); respond(true, 'Нечего собирать.', ['collected'=>0,'code'=>$code]); }

            // обнуляем хранилище локации
            $z = Work::$sql->prepare("UPDATE clan_buildings SET stored_yield=0 WHERE clan_id=? AND code=?");
            $z->bind_param("is", $clanId, $code);
            $z->execute(); $z->close();

            // если есть mapping предмета — кладём в склад; иначе — в банк
            $yieldItem = ClanCfg::$BUILDING_YIELD_ITEM[$code] ?? null;
            if ($yieldItem) {
                $ins = Work::$sql->prepare("
                    INSERT INTO clan_storage (clan_id,item_id,amount)
                    VALUES (?,?,?)
                    ON DUPЛICATE KEY UPDATE amount=amount+VALUES(amount)
                ");
                $ins->bind_param("iii", $clanId, $yieldItem, $amount);
                $ins->execute(); $ins->close();

                $act = 'deposit';
                $lg = Work::$sql->prepare("INSERT INTO clan_storage_log (clan_id,user_id,action,item_id,amount) VALUES (?,?,?,?,?)");
                $lg->bind_param("iisii", $clanId, $userId, $act, $yieldItem, $amount);
                $lg->execute(); $lg->close();
            } else {
                clanBankDeltaTx($clanId, $userId, +$amount, 'collect_'.$code, 'reward');
            }

            addClanLog($clanId, 'CLAN_LOCATION_COLLECT', [
                'user' => $userInfo['login'],
                'code' => $code,
                'amount' => $amount,
                'date' => date('Y-m-d H:i:s'),
            ]);

            commitTx();
            respond(true, 'Сбор выполнен.', ['collected'=>$amount,'code'=>$code]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    case 'locationsUpgrade': {
        requireCsrf();
        requireMembership($userId, $clanId, $role, $clanLevel, $clanXp, $bankBalance);
        ensureMinRole($role, 2); // офицер+

        $code = pStr($_POST, 'code', '');
        if ($code === '') respond(false, 'Не указана локация.');

        beginTx();
        try {
            $b = fetchOne("SELECT level FROM clan_buildings WHERE clan_id=? AND code=? FOR UPDATE", "is", [$clanId,$code]);
            if (!$b) throw new Exception('Локация не найдена.');
            $level = (int)$b['level'];

            $cfg = ClanCfg::$BUILDING_CFG[$code] ?? null;
            if (!$cfg) throw new Exception('Неверный код локации.');
            $max = (int)($cfg['max_level'] ?? 1);
            if ($level >= $max) throw new Exception('Достигнут максимум уровня.');

            $next = $level + 1;
            $cost = (int)($cfg['upgrade_cost_bank'][$next] ?? 0);
            if ($cost > 0) {
                clanBankDeltaTx($clanId, $userId, -$cost, 'upgrade_'.$code, 'bank', ['to_level'=>$next]);
            }

            $u = Work::$sql->prepare("UPDATE clan_buildings SET level=? WHERE clan_id=? AND code=?");
            $u->bind_param("iis", $next, $clanId, $code);
            $u->execute(); $u->close();

            addClanLog($clanId, 'CLAN_LOCATION_UPGRADE', [
                'user' => $userInfo['login'],
                'code' => $code,
                'to_level' => $next,
                'cost' => $cost,
                'date' => date('Y-m-d H:i:s'),
            ]);

            commitTx();
            respond(true, 'Уровень повышен.', ['code'=>$code,'level'=>$next,'cost_spent'=>$cost]);
        } catch (Throwable $e) {
            rollbackTx();
            respond(false, 'Ошибка: '.$e->getMessage());
        }
    }

    // -------------------------------------------
    // ИМЕЮЩИЕСЯ У ВАС ДЕЙСТВИЯ (оставлены для совместимости)
    // -------------------------------------------

    // Получение логов клана (только для лидера) с пагинацией
    case 'getClanLogs': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');

        $page  = max(1, pInt($val, 0, 1));
        $limit = max(1, min(100, pInt($val, 1, 20)));
        $offset = ($page - 1) * $limit;

        $stmtCnt = Work::$sql->prepare("SELECT COUNT(*) AS c FROM log_game WHERE user_id = ? AND type = 'clan'");
        $stmtCnt->bind_param("i", $clanId);
        $stmtCnt->execute();
        $cntRow = $stmtCnt->get_result()->fetch_assoc();
        $stmtCnt->close();
        $total = (int)($cntRow['c'] ?? 0);

        $stmt = Work::$sql->prepare("SELECT id, title, info, type FROM log_game WHERE user_id = ? AND type = 'clan' ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $clanId, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        $entries = [];
        while ($log = $res->fetch_assoc()) $entries[] = $log;
        $stmt->close();

        respond(true, '', [
            'logs' => $entries,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    // Получение списка участников клана (только для лидера)
    case 'getClanUsers': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');
        $stmt = Work::$sql->prepare("
            SELECT u.login, u.id, cu.status, cu.`group`, cu.raiting
            FROM base_clans_users cu
            JOIN users u ON cu.user_id = u.id
            WHERE cu.clan_id = ?
            ORDER BY cu.`group` ASC, u.login ASC
        ");
        $stmt->bind_param("i", $clanId);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) {
            $row['id']      = (int)$row['id'];
            $row['group']   = (int)$row['group'];
            $row['raiting'] = (int)$row['raiting'];
            $list[] = $row;
        }
        $stmt->close();
        respond(true, '', ['users' => $list]);
    }

    // Принятие заявки на вступление в клан (только для лидера)
    case 'acceptClanJoin': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');
        $joinUserId = isset($val[0]) ? (int)$val[0] : 0;
        if ($joinUserId < 1) respond(false, 'Некорректный пользователь.');

        $stmt = Work::$sql->prepare("SELECT user, clan, date FROM clan_users_application WHERE user = ? AND clan = ? LIMIT 1");
        $stmt->bind_param("ii", $joinUserId, $clanId);
        $stmt->execute();
        $app = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$app || (int)$app['clan'] !== $clanId) respond(false, 'Заявка не найдена.');

        $chk = Work::$sql->prepare("SELECT 1 FROM base_clans_users WHERE user_id = ? LIMIT 1");
        $chk->bind_param("i", $joinUserId);
        $chk->execute();
        $already = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($already) {
            $d = Work::$sql->prepare("DELETE FROM clan_users_application WHERE user = ? AND clan = ?");
            $d->bind_param("ii", $joinUserId, $clanId);
            $d->execute(); $d->close();
            respond(false, 'Пользователь уже состоит в клане.');
        }

        beginTx();
        try {
            $ins = Work::$sql->prepare("INSERT INTO base_clans_users (user_id, clan_id, raiting, status, `group`) VALUES (?, ?, 0, 'Участник', 3)");
            $ins->bind_param("ii", $joinUserId, $clanId);
            $ins->execute(); $ins->close();

            $del = Work::$sql->prepare("DELETE FROM clan_users_application WHERE user = ? AND clan = ?");
            $del->bind_param("ii", $joinUserId, $clanId);
            $del->execute(); $del->close();

            $uS = Work::$sql->prepare("SELECT login, user_group FROM users WHERE id = ? LIMIT 1");
            $uS->bind_param("i", $joinUserId);
            $uS->execute();
            $u = $uS->get_result()->fetch_assoc();
            $uS->close();

            addClanLog($clanId, 'ADD_CLAN_USER', [
                'user_new'   => (string)($u['login'] ?? ('ID#'.$joinUserId)),
                'user_group' => (int)($u['user_group'] ?? 0),
                'date'       => date('d.m.Y'),
            ]);

            commitTx();
            respond(true, 'Участник принят в клан.');
        } catch (Throwable $t) {
            rollbackTx();
            respond(false, 'Ошибка при принятии в клан.');
        }
    }

    // Отклонение заявки на вступление (только лидер)
    case 'declineClanJoin': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');
        $joinUserId = isset($val[0]) ? (int)$val[0] : 0;
        if ($joinUserId < 1) respond(false, 'Некорректный пользователь.');
        $stmt = Work::$sql->prepare("DELETE FROM clan_users_application WHERE user = ? AND clan = ?");
        $stmt->bind_param("ii", $joinUserId, $clanId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected > 0) respond(true, 'Заявка отклонена.'); else respond(false, 'Заявка не найдена.');
    }

    // Передача лидерства (только лидер)
    case 'setClanLeader': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');
        $login = isset($val[0]) ? clearStr($val[0]) : '';
        if ($login === '') respond(false, 'Некорректный логин.');

        $stmt = Work::$sql->prepare("SELECT id FROM users WHERE login = ? LIMIT 1");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$target) respond(false, 'Игрок не найден.');
        $targetId = (int)$target['id'];

        $stmt = Work::$sql->prepare("SELECT `group` FROM base_clans_users WHERE user_id = ? AND clan_id = ? LIMIT 1");
        $stmt->bind_param("ii", $targetId, $clanId);
        $stmt->execute();
        $targetRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$targetRow) respond(false, 'Игрок не состоит в вашем клане.');
        if ((int)$targetRow['group'] === 1) respond(false, 'Игрок уже лидер.');

        beginTx();
        try {
            $up1 = Work::$sql->prepare("UPDATE base_clans_users SET `group` = 1 WHERE user_id = ? AND clan_id = ?");
            $up1->bind_param("ii", $targetId, $clanId);
            $up1->execute(); $up1->close();

            $up2 = Work::$sql->prepare("UPDATE base_clans_users SET `group` = 3 WHERE user_id = ? AND clan_id = ?");
            $up2->bind_param("ii", $userId, $clanId);
            $up2->execute(); $up2->close();

            addClanLog($clanId, 'ADD_CLAN_ADMIN', [
                'user_new'   => $login,
                'user_group' => (int)$GLOBALS['userInfo']['user_group'],
                'date'       => date('d.m.Y'),
            ]);

            commitTx();
            respond(true, "Игрок {$login} назначен лидером.");
        } catch (Throwable $t) {
            rollbackTx();
            respond(false, 'Ошибка при передаче лидерства.');
        }
    }

    // Обновление описания клана (только лидер)
    case 'updateClanDescription': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');
        $newText = isset($val[0]) ? trim((string)$val[0]) : '';
        $newText = mb_substr($newText, 0, 1000);

        $stmt = Work::$sql->prepare("SELECT info FROM base_clans WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $clanId);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $data = [];
        if ($info && isset($info['info'])) {
            $data = json_decode($info['info'], true);
            if (!is_array($data)) $data = [];
        }
        $data['description'] = $newText;
        $infoEncoded = json_encode($data, JSON_UNESCAPED_UNICODE);

        $up = Work::$sql->prepare("UPDATE base_clans SET info = ? WHERE id = ?");
        $up->bind_param("si", $infoEncoded, $clanId);
        $ok = $up->execute();
        $up->close();

        if ($ok) {
            addClanLog($clanId, 'ABOUT_CLAN_ALERT', [
                'user_new'   => $GLOBALS['userInfo']['login'],
                'user_group' => (int)$GLOBALS['userInfo']['user_group'],
                'date'       => date('d.m.Y'),
            ]);
            respond(true, 'Описание клана обновлено.');
        } else respond(false, 'Ошибка обновления описания.');
    }

    // Обновление эмблемы клана (только лидер)
    case 'updateClanEmblem': {
        if (!isLeader($userId, $clanId)) respond(false, 'Доступ только лидеру.');
        if (!isset($_FILES['emblem']) || $_FILES['emblem']['error'] !== UPLOAD_ERR_OK) respond(false, 'Ошибка загрузки эмблемы.');

        $tmpPath = $_FILES['emblem']['tmp_name'];
        $size    = (int)($_FILES['emblem']['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) respond(false, 'Недопустимый размер файла (макс. 5 МБ).');

        $type = @mime_content_type($tmpPath);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg'];
        if (!isset($allowed[$type])) respond(false, 'Разрешены только PNG и JPEG.');

        $ext = $allowed[$type];
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/world/clans/emblems/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $fileName = $clanId . '.' . $ext;
        $dest = $uploadDir . $fileName;

        foreach (['png','jpg','jpeg'] as $e) {
            $old = $uploadDir . $clanId . '.' . $e;
            if ($e !== $ext && file_exists($old)) @unlink($old);
        }

        if (!move_uploaded_file($tmpPath, $dest)) respond(false, 'Не удалось сохранить эмблему.');

        $path = '/img/world/clans/emblems/' . $fileName . '?v=' . time();
        $up = Work::$sql->prepare("UPDATE base_clans SET emblem = ? WHERE id = ?");
        $up->bind_param("si", $path, $clanId);
        $ok = $up->execute(); $up->close();

        if ($ok) respond(true, 'Эмблема обновлена.', ['path' => $path]);
        else respond(false, 'Ошибка обновления эмблемы.');
    }

    // -------------------------------------------
    default:
        respond(false, 'Неизвестное действие.');
}

// На всякий случай (сюда не дойдём)
echo json_encode(['error'=>1,'text'=>'unexpected_exit'], JSON_UNESCAPED_UNICODE);

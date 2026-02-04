<?php
// /do/makasimka.php
// Полная версия с интеграцией сокетов и корректным закрытием боя

// ================== ОТЛАДКА (выключить на проде) ==================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ==================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';

function log_sql($query, $error = '') {
    $f = __DIR__ . '/debug_sql.log';
    $msg = '[' . date('Y-m-d H:i:s') . '] ' . $query . ($error ? ("\nОШИБКА: " . $error) : '') . "\n\n";
    file_put_contents($f, $msg, FILE_APPEND);
}

// ================== БАЗА/КОНФИГ ==================
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        _setError('Проблема с подключением конфигурационных файлов.');
    } else {
        require_once($patch_global);
    }
}

$_SESSION['id'] = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

// Единый коннектор
/** @var mysqli $mysqli */
global $mysqli;

// ================== SOCKET PUSH МОСТ ==================
// Совместим с твоим /do/update.php (тот же секрет/формат)
if (!defined('NODE_SERVER_IP'))    define('NODE_SERVER_IP',    '90.156.169.219'); // VPS IP
if (!defined('NODE_SERVER_PORT'))  define('NODE_SERVER_PORT',  8081);
if (!defined('NODE_SERVER_SECRET'))define('NODE_SERVER_SECRET','gT8$pL#w9!zXcVbN@q7'); // поменяй на свой

/**
 * Низкоуровневый пуш на Node (одному таргету)
 * @param int|string $target userId или room-ключ на ноде
 * @param string     $event  имя внутреннего события на ноде
 * @param array      $data   полезная нагрузка
 */
function socketPush($target, $event, array $data) {
    $url = 'http://' . NODE_SERVER_IP . ':' . NODE_SERVER_PORT . '/send-message';
    $payload = json_encode([
        'secret' => NODE_SERVER_SECRET,
        'target' => $target,
        'event'  => $event,
        'data'   => $data
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 1,
        CURLOPT_NOSIGNAL       => 1,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Упрощённо: отправить типизированное приложение-событие пользователю (роутится в GameSocket.handleEvent)
 * @param int    $userId
 * @param string $type   например 'notification' | 'user_status' | 'battle' | 'trade' | 'poke_action_result'
 * @param array  $data
 */
function pushAppEventToUser($userId, $type, array $data) {
    socketPush(
        (int)$userId,
        'socket_message',
        ['type' => $type, 'data' => $data]
    );
}

/**
 * Отправить одному и второму игроку однотипное событие
 * @param int    $u1
 * @param int    $u2
 * @param string $type
 * @param array  $data1 payload для u1 (если null — возьмётся $data2)
 * @param array  $data2 payload для u2 (если null — возьмётся $data1)
 */
function pushToBoth($u1, $u2, $type, array $data1 = null, array $data2 = null) {
    $d1 = $data1 ?? $data2 ?? [];
    $d2 = $data2 ?? $data1 ?? [];
    if ($u1) pushAppEventToUser($u1, $type, $d1);
    if ($u2) pushAppEventToUser($u2, $type, $d2);
}

// ================== УТИЛИТЫ ==================
/** Безопасная выборка пользователя */
function fetchUserById($id) {
    global $mysqli;
    $id = intval($id);
    $q  = 'SELECT * FROM `users` WHERE `id`=' . $id . ' LIMIT 1';
    log_sql($q);
    $res = $mysqli->query($q);
    if (!$res) log_sql($q, $mysqli->error);
    return $res ? $res->fetch_assoc() : null;
}

/** Построить снапшот боя через твой ActionBattle (для конкретного пользователя) */
function buildBattleSnapshotForUser($userId) {
    $user = fetchUserById($userId);
    if (!$user) return null;
    $response = [];
    try {
        // ActionBattle сам понимает user->status/status_id
        new ActionBattle($user, [], $response);
        return !empty($response['battleInfo']) ? $response['battleInfo'] : null;
    } catch (\Throwable $e) {
        file_put_contents(__DIR__ . '/php_exceptions.log',
            "[buildBattleSnapshotForUser] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
        return null;
    }
}

/** Разослать боевой снапшот обоим игрокам */
function pushBattleStartSnapshots($u1, $u2) {
    $snap1 = buildBattleSnapshotForUser($u1);
    $snap2 = buildBattleSnapshotForUser($u2);

    if ($snap1) pushAppEventToUser($u1, 'battle', $snap1);
    if ($snap2) pushAppEventToUser($u2, 'battle', $snap2);
}

/** Корректное закрытие боя: статусы обоим -> free, пуш уведомлений/статусов/закрытия */
function closeBattleAndBroadcast($battleId, $whoSurrenderId = null, $reason = 'surrender') {
    global $mysqli;

    $battleId = intval($battleId);
    if ($battleId <= 0) return false;

    // Берём бой
    $q = "SELECT * FROM `battle` WHERE `id` = {$battleId} LIMIT 1";
    log_sql($q);
    $row = $mysqli->query($q);
    if (!$row) { log_sql($q, $mysqli->error); return false; }
    $battle = $row->fetch_assoc();
    if (!$battle) return false;

    $u1 = intval($battle['user_1'] ?? 0);
    $u2 = intval($battle['user_2'] ?? 0);
    $opponent = null;
    if ($whoSurrenderId && ($whoSurrenderId == $u1)) $opponent = $u2;
    if ($whoSurrenderId && ($whoSurrenderId == $u2)) $opponent = $u1;

    // Попробуем зафиксировать окончание в таблице боя (поля могут отличаться, делаем мягко)
    $endTime = time();
    $up = [];
    if (!empty($battle['status'])) { $up[] = "`status`='end'"; }
    if (!empty($battle['winner']) && $opponent) { $up[] = "`winner`=" . intval($opponent); }
    if (!empty($battle['reason']))  { $up[] = "`reason`='" . $mysqli->real_escape_string($reason) . "'"; }
    if (!empty($battle['end_time'])){ $up[] = "`end_time`=" . $endTime; }
    if ($up) {
        $uq = "UPDATE `battle` SET " . implode(',', $up) . " WHERE `id`={$battleId} LIMIT 1";
        log_sql($uq);
        $mysqli->query($uq);
        if ($mysqli->error) log_sql($uq, $mysqli->error);
    }

    // Сбрасываем статусы обоим -> free
    $uuq = "UPDATE `users` SET `status`='free', `status_id`=0 WHERE `id` IN ({$u1},{$u2})";
    log_sql($uuq);
    $mysqli->query($uuq);
    if ($mysqli->error) log_sql($uuq, $mysqli->error);

    // Пуш статусов
    pushToBoth($u1, $u2, 'user_status', ['user_id'=>$u1,'status'=>'free','status_id'=>0], ['user_id'=>$u2,'status'=>'free','status_id'=>0]);

    // Нотификации/текст
    if ($whoSurrenderId) {
        $whoUser = fetchUserById($whoSurrenderId);
        $name    = $whoUser ? ($whoUser['login'] ?? 'Противник') : 'Противник';
        pushToBoth(
            $u1, $u2,
            'poke_action_result',
            ['message' => "{$name} сдался.", 'result' => true],
            ['message' => "{$name} сдался.", 'result' => true]
        );
    } else {
        pushToBoth($u1, $u2, 'poke_action_result',
            ['message' => 'Бой завершён.', 'result' => true],
            ['message' => 'Бой завершён.', 'result' => true]
        );
    }

    // Служебный сигнал в battle-роутер клиента (чтобы окно закрылось, если ожидает)
    pushToBoth($u1, $u2, 'battle', ['ended' => true, 'battle_id'=>$battleId], ['ended' => true, 'battle_id'=>$battleId]);

    return true;
}

// ================== ПОЛЬЗОВАТЕЛЬ И ИНПУТ ==================
$userInfo = fetchUserById($_SESSION['id']);
if (!$userInfo) {
    _setError('Пользователь не найден или сессия неверна.');
}

$func_1 = function($resp = null) {
    $info = [
        'snow_1' => [
            'id' => 150,
            'name' => 'Ком снега',
            'count' => item_isset(150, 1)
        ],
        'snow_2' => [
            'id' => 151,
            'name' => 'Большой ком снега',
            'count' => item_isset(151, 1)
        ]
    ];
    Work::_setStrongInfo([
        'inf_snowball' => $info,
        'inf_count' => !is_null($resp) ? $resp : false
    ]);
};

// ================== ОСНОВНОЙ РОУТЕР ==================
if (!empty($_POST['type'])) {

    switch ($_POST['type']) {

        // ---------------------------------------------
        // ОФФЕРЫ (инвайты): создать/принять/удалить
        // ---------------------------------------------
        case 'offers':
            if ($userInfo['status'] !== 'free') {
                _setError('На данный момент вы не можете совершить это действие.');
            }

            $offerID = isset($_POST['offerID']) ? intval($_POST['offerID']) : null;

            // Принятие/отклонение существующего оффера
            if ($offerID && $offerID > 0) {
                $q = 'SELECT `id`,`type`,`user_id`,`touser_id`,`hash`,`info` FROM `user_notice`
                      WHERE `id` = ' . $offerID . ' AND (`user_id` = ' . $_SESSION['id'] . ' OR `touser_id` = ' . $_SESSION['id'] . ')';
                log_sql($q);
                $info = Work::$sql->query($q);
                if (!$info) log_sql($q, Work::$sql->error);
                $info = $info ? $info->fetch_assoc() : [];

                if (!empty($info)) {
                    // Сносим оффер
                    $dq = 'DELETE FROM `user_notice` WHERE `id` = ' . intval($info['id']);
                    log_sql($dq);
                    Work::$sql->query($dq);

                    // Подтверждение
                    if (isset($_POST['confirmed']) && intval($_POST['confirmed']) > 0) {
                        $userID = ($_SESSION['id'] == $info['user_id'] ? intval($info['touser_id']) : intval($info['user_id']));
                        $q = 'SELECT * FROM `users` WHERE `id` = ' . $userID;
                        log_sql($q);
                        $userTo = Work::$sql->query($q);
                        if (!$userTo) log_sql($q, Work::$sql->error);
                        $userTo = $userTo ? $userTo->fetch_assoc() : [];

                        if (!empty($userTo) && $userTo['status'] == 'free') {
                            if ($userInfo['location'] != $userTo['location']) {
                                _setError('Тренер слишком далеко!');
                            }

                            switch ($info['type']) {
                                // ======= ПВП БОЙ =======
                                case 'battle':
                                    $info_1 = Info::_userInfoBattle($_SESSION['id'], 'pvp', [ 'uinfo' => $userInfo ]);
                                    $info_2 = Info::_userInfoBattle($userID, 'pvp', [ 'uinfo' => $userTo ]);

                                    if (empty($info_1)) _setError('У вас нет покемонов, способных сражаться.');
                                    if (empty($info_2)) _setError('У оппонента нет покемонов, способных сражаться.');

                                    // Локация/погода
                                    $q = 'SELECT location FROM users WHERE id = '.$info_1['userInfo']['id'];
                                    log_sql($q);
                                    $UserSelect = Work::$sql->query($q)->fetch_assoc();
                                    $q = 'SELECT region, img_fight, weather FROM base_location WHERE id = '.$UserSelect['location'];
                                    log_sql($q);
                                    $LocationSelect = Work::$sql->query($q)->fetch_assoc();
                                    $q = 'SELECT weather FROM base_region WHERE id = '.$LocationSelect['region'];
                                    log_sql($q);
                                    $WeatherNum = Work::$sql->query($q)->fetch_assoc();

                                    $weather = ($LocationSelect['weather'] != 0) ? $LocationSelect['weather'] : $WeatherNum['weather'];
                                    $imgFight = $LocationSelect['img_fight'];
                                    $info_1 = Info::_parseData($info_1);
                                    $info_2 = Info::_parseData($info_2);

                                    // Регистрируем бой
                                    $q = 'INSERT INTO `battle`
                                        (`user_1`,`user_2`,`info_1`,`info_2`,`type`,`weather`,`img`)
                                        VALUES (
                                            '.$_SESSION['id'].',
                                            '.$userID.',
                                            "'.Work::$sql->real_escape_string($info_1).'",
                                            "'.Work::$sql->real_escape_string($info_2).'",
                                            "pvp",
                                            "'.$weather.'",
                                            "'.$imgFight.'"
                                        )';
                                    log_sql($q);
                                    $res = Work::$sql->query($q);
                                    if (!$res) log_sql($q, Work::$sql->error);
                                    $battleId = intval(Work::$sql->insert_id);

                                    // Статусы обоим
                                    $q = 'UPDATE `users` SET `status` = "battle", `status_id` = ' . $battleId . '
                                          WHERE `id` IN (' . intval($info['user_id']) . ', ' . intval($info['touser_id']) . ')';
                                    log_sql($q);
                                    $res = Work::$sql->query($q);
                                    if (!$res) log_sql($q, Work::$sql->error);

                                    // ==== СОКЕТЫ ====
                                    // 1) статус обоим
                                    pushToBoth($info['user_id'], $info['touser_id'], 'user_status',
                                        ['user_id'=>$info['user_id'],'status'=>'battle','status_id'=>$battleId],
                                        ['user_id'=>$info['touser_id'],'status'=>'battle','status_id'=>$battleId]
                                    );
                                    // 2) снапшоты боя (каждому — «своим взглядом»)
                                    pushBattleStartSnapshots($info['user_id'], $info['touser_id']);
                                    // 3) нотификация
                                    pushToBoth($info['user_id'], $info['touser_id'], 'notification',
                                        ['text'=>'Бой начался!', 'type'=>'success'],
                                        ['text'=>'Бой начался!', 'type'=>'success']
                                    );
                                break;

                                // ======= ТРЕЙД =======
                                case 'trade':
                                    $q = "INSERT INTO `users_trade` (`user1`, `user2`, `status`)
                                          VALUES ('".intval($_SESSION['id'])."', '".intval($userID)."', 1)";
                                    log_sql($q);
                                    $mysqli->query($q);
                                    if ($mysqli->error) log_sql($q, $mysqli->error);
                                    $tradeId = intval($mysqli->insert_id);

                                    $q = 'UPDATE `users` SET `status` = "trade", `status_id` = ' . $tradeId . '
                                          WHERE `id` IN (' . intval($info['user_id']) . ', ' . intval($info['touser_id']) . ')';
                                    log_sql($q);
                                    $res = Work::$sql->query($q);
                                    if (!$res) log_sql($q, Work::$sql->error);

                                    // ==== СОКЕТЫ ====
                                    pushToBoth($info['user_id'], $info['touser_id'], 'user_status',
                                        ['user_id'=>$info['user_id'],'status'=>'trade','status_id'=>$tradeId],
                                        ['user_id'=>$info['touser_id'],'status'=>'trade','status_id'=>$tradeId]
                                    );
                                    // Можно отправить starter payload трейда (если есть метод для сборки)
                                    pushToBoth($info['user_id'], $info['touser_id'], 'notification',
                                        ['text'=>'Обмен начат', 'type'=>'info'],
                                        ['text'=>'Обмен начат', 'type'=>'info']
                                    );
                                break;
                            }

                        } else {
                            _setError('Данный пользователь сейчас занят.');
                        }
                    }
                }

            // Создание нового оффера
            } elseif ($offerID === -1) {
                $userID = isset($_POST['userID']) ? intval($_POST['userID']) : null;
                if ($userID && $userID > 0) {
                    $q = 'SELECT `id`,`login`,`status`,`location` FROM `users` WHERE `id` = ' . $userID;
                    log_sql($q);
                    $userTo = Work::$sql->query($q);
                    if (!$userTo) log_sql($q, Work::$sql->error);
                    $userTo = $userTo ? $userTo->fetch_assoc() : [];
                    $target = isset($_POST['target']) ? $_POST['target'] : 'default';
                    if (!in_array($target, ['battle', 'trade', 'zoogamy'])) {
                        $target = 'default';
                    }

                    if (!empty($userTo)) {
                        if ($userTo['status'] == 'free') {
                            if ($userTo['location'] != 89) {
                                $hash = md5('u' . $_SESSION['id'] . '-' . $target . '-u' . $userID);

                                $q = 'SELECT `id` FROM `user_notice` WHERE `hash` = "' . $hash . '"';
                                log_sql($q);
                                $check = Work::$sql->query($q);
                                if (!$check) log_sql($q, Work::$sql->error);
                                $check = $check ? $check->fetch_assoc() : [];
                                if (empty($check)) {
                                    $q = 'INSERT INTO `user_notice`
                                          (`type`,`user_id`,`touser_id`,`hash`)
                                          VALUES ("' . $target . '", ' . $_SESSION['id'] . ', ' . $userID . ', "' . $hash . '")';
                                    log_sql($q);
                                    Work::$sql->query($q);
                                }
                                Work::_setInfo('offersCreate', 1);

                                // ==== СОКЕТ УВЕДОМЛЕНИЕ АДРЕСАТУ ====
                                pushAppEventToUser($userID, 'notification', [
                                    'text' => 'Приглашение: ' . ($target === 'battle' ? 'бой' : ($target==='trade'?'обмен':'действие')),
                                    'type' => 'info'
                                ]);

                            } else {
                                _setError('На этой локации запрещены обычные бои и обмены.');
                            }
                        } else {
                            _setError('Данный пользователь сейчас занят.');
                        }
                    }
                }
            }
        break;

        // ---------------------------------------------
        // СИНХРОНИЗАЦИЯ / ЧТЕНИЕ БОЯ (совместимо с прежним)
        // ---------------------------------------------
        case 'battle':
            if ($userInfo['status'] == 'battle') {
                $response = [];
                try {
                    $battle = new ActionBattle($userInfo, [], $response);

                    if (empty($response)) _setError('⚠️ Ошибка: после вызова ActionBattle response пустой.');
                    if (!isset($response['battleInfo'])) _setError('⚠️ Ошибка: battleInfo не сформирован в ActionBattle.');

                    // Отдаём как раньше
                    Work::_setStrongInfo($response);

                    // Дополнительно (мягко): если бой имеет флаг завершения — пульнём завершение
                    if (!empty($response['battleInfo']['ended']) || !empty($response['battleInfo']['end'])) {
                        $bid = intval($userInfo['status_id']);
                        closeBattleAndBroadcast($bid, null, 'finished');
                    }

                } catch (Throwable $e) {
                    file_put_contents(__DIR__ . '/php_exceptions.log', $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
                    _setError('❌ Исключение в ActionBattle: ' . $e->getMessage());
                }
            } else {
                _setError('⛔ Вы не находитесь в статусе "battle". Текущий статус: ' . $userInfo['status']);
            }
        break;

        // ---------------------------------------------
        // ЯВНОЕ ЗАВЕРШЕНИЕ БОЯ (опциональная точка)
        // Можно дёргать из логики сдачи/таймаута, если нужно
        // ---------------------------------------------
        case 'battle_close':
            // ожидаем battle_id (или возьмём из user.status_id)
            $battleId = isset($_POST['battle_id']) ? intval($_POST['battle_id']) : intval($userInfo['status_id']);
            $whoId    = $_SESSION['id']; // кто инициировал (например, сдаётся)
            if ($battleId > 0) {
                closeBattleAndBroadcast($battleId, $whoId, isset($_POST['reason']) ? $_POST['reason'] : 'surrender');
                Work::_setInfo('battleClosed', 1);
            } else {
                _setError('Не найден идентификатор боя.');
            }
        break;

        // ---------------------------------------------
        // ТОРГОВЛЯ (совместимо)
        // ---------------------------------------------
        case 'trade':
            if ($userInfo['status'] == 'trade') {
                $response = [];
                new Trade($mysqli, $userInfo, $response);
                Work::_setStrongInfo($response);
            } else {
                _setError('⛔ Вы не находитесь в статусе "trade".');
            }
        break;

        // ---------------------------------------------
        // МАГАЗИНЫ
        // ---------------------------------------------
        case 'by_shop':
            $response = [];
            Info::_shableShop($_POST, $userInfo, $response);
            Work::_setStrongInfo($response);
        break;

        case 'view_shop':
            $response = [];
            Info::_shableShop(isset($_POST['npc']) ? intval($_POST['npc']) : 0, $userInfo, $response);
            Work::_setStrongInfo($response);
        break;

        // ---------------------------------------------
        // ПРЕДМЕТЫ
        // ---------------------------------------------
        case 'items':
            if (isset($_POST['cat'])) {
                $response = [];
                $type = ($_POST['cat'] == 'ball') ? 'ball' : null;

                if ($type) {
                    $selInfo = [];
                    try {
                        $q = '
                            SELECT
                                `bi`.`name`,
                                `ui`.`id`,
                                `ui`.`count`,
                                `ui`.`item_id`
                            FROM `base_items` AS `bi`
                            INNER JOIN `items_users` AS `ui` ON `ui`.`item_id` = `bi`.`id`
                            WHERE
                                (
                                    (`bi`.`type` = "ball"   AND `ui`.`user` = ' . $_SESSION['id'] . ')
                                    OR
                                    (`bi`.`type` = "potion" AND `ui`.`user` = ' . $_SESSION['id'] . ')
                                    OR
                                    (`bi`.`type` = "berry"  AND `bi`.`battle` = 1 AND `ui`.`user` = ' . $_SESSION['id'] . ')
                                )
                        ';
                        log_sql($q);
                        $sel = Work::$sql->query($q);
                        if (!$sel) log_sql($q, Work::$sql->error);

                        while ($row = $sel->fetch_assoc()) {
                            $selInfo[] = $row;
                        }
                        if (!empty($selInfo)) {
                            $response['ballList'] = $selInfo;
                            Work::_setStrongInfo($response);
                        } else {
                            _setError('⚠️ Не найдено ни одного предмета соответствующей категории.');
                        }
                    } catch (Throwable $e) {
                        file_put_contents(__DIR__ . '/php_exceptions.log', $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
                        _setError('❌ Исключение при выборке предметов: ' . $e->getMessage());
                    }
                } else {
                    _setError('⛔ Неизвестная категория предметов: ' . $_POST['cat']);
                }
            } else {
                _setError('⛔ Не передан параметр cat для items.');
            }
        break;

        
    }
}

Work::_viewOut();

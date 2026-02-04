<?php
/**
 * ВАЖНО:
 * - Класс опирается на глобальные хелперы/классы проекта: Work::$sql (mysqli), Info, Battle, PokeBattle,
 *   а также функции: _setError, escapeMe, itemAdd, minus_item_id, minus_item, news_friend, lvlupuser, etc.
 * - Эта логика не печатает JSON сама по себе — это делает ваш роутер/контроллер.
 * - В ответ (router) возвращайте ТОЛЬКО JSON:
 *     header('Content-Type: application/json; charset=utf-8');
 *     echo json_encode(['battleInfo' => $response['battleInfo']], JSON_UNESCAPED_UNICODE);
 *     exit;
 */

/* ————————— Квест-утилита (как было) ————————— */
function quest_step($id, $step){
    global $mysqli;
    if ($step == 0) {
        return true;
    } else {
        $q = $mysqli->query("SELECT `step` FROM `user_quests` WHERE `user_id` = '".intval($_SESSION['id'])."' AND `quest_id` = '".intval($id)."'")->fetch_assoc();
        if (empty($q)) {
            return false;
        }
        return ($q['step'] == $step ? true : false);
    }
}

/* ————————— Основной класс ActionBattle ————————— */
class ActionBattle {

    /* ——— Константы ——— */
    const TIMER_ATK_ROUND = 120;
    const LOSE_NO_HP   = 'NO_HP';
    const LOSE_TIMEOUT = 'NO_TIME';
    const LOSE_COWARD  = 'COWARD';
    const LOSE_ALL     = 'ALL';
    const LOSE_OTHER   = 'OTHER';

    /* ——— Базовая информация ——— */
    private $defaultInfo = [
        'id'         => 0,
        'login'      => 'Дикий покемон',
        'user_group' => 6
    ];

    /* ——— Публичные поля, которые используются вне ——— */
    public  $next_turn = 0;
    public  $round     = 0;
    public  $weather   = 0;
    public  $weather_round = 0;
    public  $img       = 0;
    public  $autolog   = '';
    public  $battleId  = 0;
    public  $userInfo  = [];

    /* ——— Внутреннее состояние ——— */
    private $mysqli;                 // ссылка на mysqli (Work::$sql)
    private $update     = [];
    private $battleInfo = [];
    private $battleType = 'pve';
    private $battleArena = 0;

    private $enemyInfo  = [];
    private $userData   = [];
    private $enemyData  = [];
    private $userPokes  = [];
    private $enemyPokes = [];
    private $userTarget = [];
    private $enemyTarget = [];
    private $otherInfo  = [];
    private $response   = [];
    private $answer     = [];
    private $log        = [];

    private $userId1 = 0;
    private $userId2 = 0;

    private $forceEndFlush = false;  // отложенная очистка после отдачи финального кадра

    /* ——— Конструктор ——— */
    public function __construct(array $userInfo = [], array $enemyInfo = [], array &$response = []) {
        if (!Work::$sql) {
            return; // нет подключения — тихо выходим
        }

        $this->mysqli   = Work::$sql;
        $this->response =& $response;

        // Пользователь: базовое + пришедшее
        $this->userInfo = array_merge($this->defaultInfo, $userInfo);

        if (!empty($this->userInfo['id']) && $this->userInfo['id'] > 0) {
            if (!empty($this->userInfo['status_id']) && $this->userInfo['status_id'] > 0) {

                // Скрываем enemy при незавершённой замене (чтобы фронт не мигал чужим target’ом)
                if (!empty($enemyInfo)) {
                    if (!empty($this->userInfo['action']) &&
                        $this->userInfo['action'] === 'swap' &&
                        empty($this->userInfo['swap_confirmed'])) {
                        $this->enemyInfo = $this->defaultInfo;
                    } else {
                        $this->enemyInfo = array_merge($this->defaultInfo, $enemyInfo);
                    }
                }

                // Подтягиваем сам бой
                $this->battleInfo = Work::$sql->query(
                    'SELECT * FROM `battle` WHERE `id`='.(int)$this->userInfo['status_id']
                )->fetch_assoc() ?: [];

                // Сохраняем участников PvP
                if (!empty($this->battleInfo) && $this->_isPVPRow($this->battleInfo)) {
                    $this->userId1 = (int)($this->battleInfo['user_1'] ?? 0);
                    $this->userId2 = (int)($this->battleInfo['user_2'] ?? 0);
                }

                if (!empty($this->battleInfo)) {
                    $this->start();
                } else {
                    $this->resetAction();
                }
            } else {
                $this->resetAction();
            }
        }
        // без return
    }

    /* ——— Старт экшена ——— */
    private function start() {
        if ($this->parser()) {
            $this->parserPost();

            // Готовим пакет для фронта
            $this->response['battleInfo'] = $this->viewInfo();

            // После формирования ответа можно очиститься, если был финальный кадр
            if ($this->forceEndFlush) {
                $this->resetAction(true);
            }

            // Сброс transient-полей
            $this->response['battleHH'] = 0;
            $this->response['battleAM'] = 0;
        } else {
            $this->resetAction();
        }
    }

    /* ——— Общие утилиты ——— */

    private function ensurePokesAtkIntegrity(array &$pokeList){
        if(empty($pokeList)){
            return;
        }
        foreach($pokeList as $pk => &$poke){
            if(!is_array($poke)){
                continue;
            }
            // attacks: CSV 0..4 id
            $attacks = $this->_normalizeAtkCsv(($poke['attacks'] ?? '0'), 4);
            $poke['attacks'] = implode(',', $attacks);

            // pp_attacks / pp_my
            $pp = $this->_normalizeAtkCsv(($poke['pp_attacks'] ?? ($poke['pp_my'] ?? '10,10,10,10')), 4, 0, 9999);
            $poke['pp_attacks'] = implode(',', $pp);
            $poke['pp_my'] = $poke['pp_attacks'];

            // atkList: добиваем недостающие описания
            if(!isset($poke['atkList']) || !is_array($poke['atkList'])){
                $poke['atkList'] = [];
            }

            foreach($attacks as $i=>$atkID){
                $atkID = (int)$atkID;
                if($atkID <= 0){
                    // a0 — «Борьба» (fallback)
                    if(!isset($poke['atkList']['a0'])){
                        $poke['atkList']['a0'] = $this->_defaultAtk(0, $i);
                    }
                    continue;
                }
                $key = 'a'.$atkID;
                if(!isset($poke['atkList'][$key])){
                    $poke['atkList'][$key] = $this->_loadAtkInfo($atkID, $i);
                }
                // гарантируем attack_num
                if(isset($poke['atkList'][$key]) && is_array($poke['atkList'][$key])){
                    $poke['atkList'][$key]['attack_num'] = $i;
                }
            }

            // disable_my: 4 значения
            if(!isset($poke['disable_my'])){
                $poke['disable_my'] = '0,0,0,0';
            }
        }
        unset($poke);
    }

    private function _normalizeAtkCsv($csv, $len = 4, $min = 0, $max = 999999){
        $arr = [];
        if(is_array($csv)){
            $arr = $csv;
        }else{
            $csv = (string)$csv;
            $csv = trim($csv);
            if($csv === ''){
                $csv = '0';
            }
            $arr = explode(',', $csv);
        }
        $out = [];
        for($i=0; $i<$len; $i++){
            $v = isset($arr[$i]) ? (int)trim((string)$arr[$i]) : 0;
            if($v < $min) $v = $min;
            if($v > $max) $v = $max;
            $out[$i] = $v;
        }
        return $out;
    }

    private function _defaultAtk($atkID = 0, $attackNum = 0){
        // «Борьба» (Struggle) — безопасный дефолт, если нет нормальных атак.
        return [
            'id' => (int)$atkID,
            'name' => 'Борьба',
            'title' => 'Удар отчаяния',
            'type' => 0,
            'category' => 1,
            'priority' => 0,
            'power' => 50,
            'accuracy' => 100,
            'pp' => 1,
            'target' => 1,
            'settings' => '',
            'my' => '',
            'enemy' => '',
            'contact' => 1,
            'bite' => 0,
            'pulse' => 0,
            'punch' => 0,
            'sound' => 0,
            'magic_coat' => 0,
            'bullet' => 0,
            'attack_num' => (int)$attackNum
        ];
    }

    private function _loadAtkInfo($atkID, $attackNum = 0){
        $atkID = (int)$atkID;
        if($atkID <= 0){
            return $this->_defaultAtk(0, $attackNum);
        }
        if(isset($this->atkCache[$atkID])){
            $atk = $this->atkCache[$atkID];
            $atk['attack_num'] = (int)$attackNum;
            return $atk;
        }

        // Язык атаки: rus/eng (как в Info::_pokeAtkList)
        $lang = $_SESSION['attack_lang'] ?? 'rus';
        $atk_col = ($lang == 'eng') ? 'name' : 'name_rus';

        $atk = false;
        if(Work::$sql){
            $q = Work::$sql->query('SELECT `id`, `'.$atk_col.'` AS `name`, `title`, `type`, `category`, `priority`, `power`, `accuracy`, `pp`, `target`, `settings`, `my`, `enemy`, `contact`, `bite`, `pulse`, `punch`, `sound`, `magic_coat`, `bullet` FROM `base_atk` WHERE `id` = '.$atkID.' LIMIT 1');
            if($q){
                $atk = $q->fetch_assoc();
            }
        }

        // Fallback: отсутствующая атака — не ломаем бой (условный Tackle)
        if(empty($atk) || !isset($atk['id'])){
            $atk = [
                'id' => $atkID,
                'name' => '[MISSING] Атака #'.$atkID,
                'title' => '',
                'type' => 1,
                'category' => 1,
                'priority' => 0,
                'power' => 40,
                'accuracy' => 100,
                'pp' => 35,
                'target' => 1,
                'settings' => '',
                'my' => '',
                'enemy' => '',
                'contact' => 1,
                'bite' => 0,
                'pulse' => 0,
                'punch' => 0,
                'sound' => 0,
                'magic_coat' => 0,
                'bullet' => 0
            ];
        }

        $atk['attack_num'] = (int)$attackNum;
        $this->atkCache[$atkID] = $atk;
        return $atk;
    }
    private function generateAnswer(array $value, $user_id = null) {
        $user_id = ($user_id ? $user_id : ($this->enemyInfo['id'] ?? null));
        if ($user_id && $user_id > 0) {
            if (isset($this->answer['u'.$user_id])) {
                $this->answer['u'.$user_id] = array_merge($this->answer['u'.$user_id], $value);
            } else {
                $this->answer['u'.$user_id] = $value;
            }
        }
        return $value;
    }
/**
 * Корректируем состояние таймеров в начале «тихого» раунда.
 * Правило:
 *  - Когда оба ещё НЕ выбрали — таймеров быть не должно (сбрасываем остатки).
 *  - Когда выбрал только один — таймер должен быть только у «ждущей» стороны.
 *  - Когда выбрали оба — таймеров быть не должно.
 */
private function ensureRoundTimers(): void
{
    if (!$this->_isPVP()) return;

    $now      = time();
    $userAtk  = (int)($this->userData['targetAtk']  ?? 0);
    $enemyAtk = (int)($this->enemyData['targetAtk'] ?? 0);

    // Оба не выбрали — таймеры не нужны
    if ($userAtk <= 0 && $enemyAtk <= 0) {
        if (!empty($this->userData['timer']['atk']))  { $this->userData['timer']['atk']  = 0; $this->update['my']    = true; }
        if (!empty($this->enemyData['timer']['atk'])) { $this->enemyData['timer']['atk'] = 0; $this->update['enemy'] = true; }
        return;
    }

    // Пользователь выбрал, соперник нет — таймер у соперника
    if ($userAtk > 0 && $enemyAtk <= 0) {
        $end = (int)($this->enemyData['timer']['atk'] ?? 0);
        if ($end <= $now) {
            $this->enemyData['timer']['atk'] = $now + self::TIMER_ATK_ROUND;
            $this->update['enemy'] = true;
        }
        return;
    }

    // Соперник выбрал, пользователь нет — таймер у пользователя
    if ($enemyAtk > 0 && $userAtk <= 0) {
        $end = (int)($this->userData['timer']['atk'] ?? 0);
        if ($end <= $now) {
            $this->userData['timer']['atk'] = $now + self::TIMER_ATK_ROUND;
            $this->update['my'] = true;
        }
        return;
    }

    // Оба выбрали — таймеры не нужны
    if ($userAtk > 0 && $enemyAtk > 0) {
        if (!empty($this->userData['timer']['atk']))  { $this->userData['timer']['atk']  = 0; $this->update['my']    = true; }
        if (!empty($this->enemyData['timer']['atk'])) { $this->enemyData['timer']['atk'] = 0; $this->update['enemy'] = true; }
    }
}

/**
 * Нормализация таймера для фронта.
 * ЛОГИКА:
 *  - Таймер всегда хранится у СТОРОНЫ, КОТОРУЮ МЫ ЖДЁМ (т.е. чей ход).
 *  - Для текущего пользователя возвращаем таймер ТОЛЬКО когда это его ход.
 *  - В timeout.atk кладём ОБЩУЮ длительность окна (total секунд).
 *  - В поле 'time' на фронт отдаём turnStartTs (epoch начала отсчёта),
 *    чтобы фронт сам посчитал elapsed.
 */
/**
 * Нормализация таймера для фронта.
 *  - Таймер отдаём ИСКЛЮЧИТЕЛЬНО когда это ход текущего пользователя.
 *  - timeout.atk = длительность окна (сек), time = turnStartTs (epoch).
 *  - myTurn = true/false (фронт покажет «Ваш ход»/«Ход соперника»).
 */
private function normalizeTimeoutForFrontend(array $myTimerRaw, int $userAtk, int $enemyAtk): array
{
    $now    = time();
    $total  = self::TIMER_ATK_ROUND;

    if (!$this->_isPVP()) {
        return [
            'timeout'     => ['atk' => 0],
            'turnStartTs' => 0,
            'left'        => 0,
            'myTurn'      => true
        ];
    }

    $myTurn = ($userAtk <= 0 && $enemyAtk > 0);

    $endTs = (int)($myTimerRaw['atk'] ?? 0);
    if ($myTurn && $endTs > $now) {
        return [
            'timeout'     => ['atk' => $total],
            'turnStartTs' => $endTs - $total,
            'left'        => $endTs - $now,
            'myTurn'      => true
        ];
    }

    return [
        'timeout'     => ['atk' => 0],
        'turnStartTs' => 0,
        'left'        => 0,
        'myTurn'      => $myTurn
    ];
}

/**
 * Если истёк таймер ожидаемой стороны — присудить ей поражение по времени.
 * Работает только в PvP. Ничего не делает, если ход одновременно не выбран у обеих/обоих.
 */
/**
 * Если истёк таймер ожидаемой стороны — присудить ей поражение по времени.
 * Работает только в PvP.
 */
private function applyTurnTimeoutIfExpired(): void
{
    if (!$this->_isPVP()) return;

    $now      = time();
    $userAtk  = (int)($this->userData['targetAtk']  ?? 0);
    $enemyAtk = (int)($this->enemyData['targetAtk'] ?? 0);

    // Ждём пользователя: таймер у userData
    if ($userAtk <= 0 && $enemyAtk > 0) {
        $end = (int)($this->userData['timer']['atk'] ?? 0);
        if ($end > 0 && $now >= $end) {
            $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_TIMEOUT);
        }
        return;
    }

    // Ждём соперника: таймер у enemyData
    if ($enemyAtk <= 0 && $userAtk > 0) {
        $end = (int)($this->enemyData['timer']['atk'] ?? 0);
        if ($end > 0 && $now >= $end) {
            $this->lose((int)($this->enemyInfo['id'] ?? 0), self::LOSE_TIMEOUT);
        }
        return;
    }
}

    private function sendChatSystemMsg($msg) {
        // Используем глобальный mysqli из Work::$sql
        if (!Work::$sql) return;

        $user_id        = 1;
        $user_login     = 'System';
        $user_sex       = 'm';
        $user_group     = 1;
        $user_msg_color = 4;
        $msg_type       = 0;
        $msg_time       = date('H:i');
        $msg_class      = "";
        $msg_region_id  = 0;
        $msg_region_name = "";
        $img            = "system";
        $info = json_encode([
            'user_id'         => $user_id,
            'user_login'      => $user_login,
            'user_sex'        => $user_sex,
            'user_group'      => $user_group,
            'user_msg_color'  => $user_msg_color,
            'user_msg'        => $msg,
            'msg_type'        => $msg_type,
            'msg_time'        => $msg_time,
            'msg_class'       => $msg_class,
            'msg_region_id'   => $msg_region_id,
            'msg_region_name' => $msg_region_name,
            'img'             => $img
        ], JSON_UNESCAPED_UNICODE);

        Work::$sql->query("INSERT INTO chat_new
            (`type`, `user`, `touser`, `location`, `clan`, `info`, `lifetime`, `img`, `img_to`)
            VALUES
            (0, {$user_id}, 0, 0, 0, '".Work::$sql->real_escape_string($info)."', ".(time()+2*3600).", 'system', '')
        ");
    }

    /* ——— Парсер состояния боя и входящих данных ——— */
private function parser() {
    if (!isset($this->battleInfo['id'])) {
        return false;
    }

    // Базовые поля боя
    $this->round         = isset($this->battleInfo['round']) ? (int)$this->battleInfo['round'] : 1;
    $this->weather       = isset($this->battleInfo['weather']) ? (int)$this->battleInfo['weather'] : 0;
    $this->weather_round = isset($this->battleInfo['weather_round']) ? (int)$this->battleInfo['weather_round'] : 0;
    $this->img           = isset($this->battleInfo['img']) ? (int)$this->battleInfo['img'] : 0;

    $this->battleType  = $this->battleInfo['type']  ?? 'pve';
    $this->battleArena = $this->battleInfo['arena'] ?? 0;

    // Определяем стороны (PvP / PvE)
    if ($this->_isPVPRow($this->battleInfo)) {
        // Гарантируем корректный battleType, даже если поле type пустое/старое
        $this->battleType = 'pvp';
        if (!empty($this->userInfo['id']) && $this->userInfo['id'] == $this->battleInfo['user_1']) {
            $this->userData  = $this->battleInfo['info_1'] ?? [];
            $this->enemyData = $this->battleInfo['info_2'] ?? [];
        } else {
            $this->userData  = $this->battleInfo['info_2'] ?? [];
            $this->enemyData = $this->battleInfo['info_1'] ?? [];
        }
    } else {
        // PvE: игрок = info_1, дикий = info_2
        $this->userData  = $this->battleInfo['info_1'] ?? [];
        $this->enemyData = $this->battleInfo['info_2'] ?? [];
    }

    $this->userData  = Info::_unParseData($this->userData);
    $this->enemyData = Info::_unParseData($this->enemyData);

    // --- TERA defaults (совместимость со старыми боями) ---
    if (!isset($this->userData['tera_used'])) $this->userData['tera_used'] = 0;
    if (!isset($this->userData['tera']))      $this->userData['tera']      = 0;
    if (!isset($this->enemyData['tera_used'])) $this->enemyData['tera_used'] = 0;
    if (!isset($this->enemyData['tera']))      $this->enemyData['tera']      = 0;

    /* === Командный режим: экшены (без other) выполняем сразу и выходим удачно ===
     * Разрешен только в PvP. В PvE — мягко игнорируем (кнопка на фронте будет скрыта).
     */
    if (isset($_POST['action'])) {
        $act = $_POST['action'];
        if ($act === 'make_team') {
            if ($this->_isPVP()) {
                $this->makeTeam();
            } else {
                _setError('Командный бой доступен только в PvP.', 'plus');
            }
            $this->update['my'] = true;
            return true;
        }
        if ($act === 'join_team') {
            if ($this->_isPVP()) {
                $this->joinTeam((int)($_POST['side'] ?? 0)); // внутри запрещаем смену стороны
            } else {
                _setError('Командный бой доступен только в PvP.', 'plus');
            }
            $this->update['my'] = true;
            return true;
        }
        if ($act === 'leave_team') {
            if ($this->_isPVP()) {
                $this->leaveTeam();
                $this->update['my'] = true;
            }
            return true;
        }
    }

    // answer/other (other не используется для команды — оставлено для обратной совместимости)
    if (!empty($this->battleInfo['other'])) {
        $this->otherInfo = array_merge($this->otherInfo, Info::_unParseData($this->battleInfo['other']));
    }
    if (!empty($this->battleInfo['answer'])) {
        $this->answer = array_merge($this->answer, Info::_unParseData($this->battleInfo['answer']));
    }

    // Дополним userInfo/enemyInfo из info_* (если там есть)
    if (!empty($this->userData['userInfo']) && is_array($this->userData['userInfo'])) {
        $this->userInfo = array_merge($this->userInfo, $this->userData['userInfo']);
    }
    if (!empty($this->enemyData['userInfo']) && is_array($this->enemyData['userInfo'])) {
        $this->enemyInfo = array_merge($this->enemyInfo, $this->enemyData['userInfo']);
    }

    // pokeLIst → pokeList
    if (!empty($this->userData['pokeList']) && is_array($this->userData['pokeList'])) {
        $this->userPokes =& $this->userData['pokeList'];
    } elseif (!empty($this->userData['pokeLIst']) && is_array($this->userData['pokeLIst'])) {
        $this->userPokes =& $this->userData['pokeLIst'];
    }
    if (!empty($this->enemyData['pokeList']) && is_array($this->enemyData['pokeList'])) {
        $this->enemyPokes =& $this->enemyData['pokeList'];
    } elseif (!empty($this->enemyData['pokeLIst']) && is_array($this->enemyData['pokeLIst'])) {
        $this->enemyPokes =& $this->enemyData['pokeLIst'];
    }

    // PvP: обработка ухода соперника и финального кадра
    if ($this->_isPVP()) {
        $user1 = (int)($this->battleInfo['user_1'] ?? 0);
        $user2 = (int)($this->battleInfo['user_2'] ?? 0);
        $myId  = (int)($this->userInfo['id'] ?? 0);

        $opponentLeft = (($user1 == $myId && $user2 <= 0) || ($user2 == $myId && $user1 <= 0));
        if ($opponentLeft) {
            $this->winByOpponentLeave();
            $this->forceEndFlush = true;
        }

        // Проверка наличия battleEND (в answer)
        $hasBattleEnd = false;
        $uidA = (int)($this->userInfo['id']  ?? 0);
        $uidB = (int)($this->enemyInfo['id'] ?? 0);
        if ($uidA && !empty($this->answer['u'.$uidA]['battleEND'])) $hasBattleEnd = true;
        if ($uidB && !empty($this->answer['u'.$uidB]['battleEND'])) $hasBattleEnd = true;
        if ($hasBattleEnd) {
            $this->forceEndFlush = true;
        }
    }

    // Валидируем наборы (кроме финального кадра)
    if (
        !$this->forceEndFlush &&
        (
            empty($this->userPokes)  || !is_array($this->userPokes) ||
            empty($this->enemyPokes) || !is_array($this->enemyPokes)
        )
    ) {
        $this->resetAction(true);
        return false;
    }

    // enemyTarget
    if (!empty($this->enemyData['target']) && $this->enemyData['target'] > 0) {
        $this->enemyTarget = $this->enemyPokes['p' . $this->enemyData['target']] ?? [];
    } else {
        $this->enemyTarget = [];
    }

    // userTarget
    if (!empty($this->userData['target']) && $this->userData['target'] > 0) {
        $this->userTarget = $this->userPokes['p' . $this->userData['target']] ?? [];
    } else {
        $this->userTarget = [];
    }

    return true;
}

    /** Победа по уходу соперника (PvP) */
    private function winByOpponentLeave() {
        $this->log[] = [
            'round'      => $this->round,
            'log'        => ['Соперник покинул бой или сдался. Вы победили!'],
            'log_status' => ['win_by_leave' => true]
        ];

        $payload = ['battleEND' => [
            'title'  => self::LOSE_COWARD,
            'winner' => (int)$this->userInfo['id'],
            'loser'  => !empty($this->enemyInfo['id']) ? (int)$this->enemyInfo['id'] : false
        ]];

        if (!empty($this->userInfo['id']))  { $this->generateAnswer($payload, (int)$this->userInfo['id']); }
        if (!empty($this->enemyInfo['id'])) { $this->generateAnswer($payload, (int)$this->enemyInfo['id']); }
    }

/* ——— Обработка POST действий ——— */
private function parserPost() {
    // Должны существовать обе стороны с наборами покемонов
    if (!(is_array($this->userPokes) && is_array($this->enemyPokes) && $this->userPokes && $this->enemyPokes)) {
        return;
    }

    // 1) Сразу проверим просрочку хода (если истёк таймер — присуждаем поражение по времени)
    //    Внутри корректно определяется, чей именно таймер истёк, и вызывается lose() с верным user_id.
    $this->applyTurnTimeoutIfExpired();

    // 2) На старте «тихого» раунда корректно выровняем таймеры (например, сбросим у сделавшего ход и дадим — ждущему)
    $this->ensureRoundTimers();

    // 3) В parser() уже обработали make_team / join_team / leave_team — здесь не дублируем

    // 4) Управление таймером (PvP/PvE) — актуальная логика (таймер всегда у «ждущего»)
    $this->handleAttackTimer();

    // — Смена активного покемона —
    if (isset($_POST['targetPoke'])) {
        $targetIndex = intval($_POST['targetPoke']);

        // Если таймер соперника истёк — поражение по времени сопернику
        if ($this->checkTimeout($this->enemyData)) {
            $this->lose((int)($this->enemyInfo['id'] ?? 0), self::LOSE_TIMEOUT);
            return;
        }
        // Если истёк наш таймер — поражение нам
        if ($this->checkTimeout($this->userData)) {
            $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_TIMEOUT);
            return;
        }

        if (isset($this->userPokes['p' . $targetIndex])) {
            if (isset($this->userData['target']) && $this->userData['target'] > 0) {
                $this->update['my'] = true;
                $targetPoke = new PokeBattle($this->userPokes['p' . $targetIndex]);

                if (isset($this->userTarget['hp']) && $this->userTarget['hp'] <= 0) {
                    $userId = isset($this->userInfo['id']) ? intval($this->userInfo['id']) : 0;

                    // Очистка «скользящих» атак/блоков на стороне игрока
                    Work::$sql->query('DELETE FROM atk_rollout    WHERE user = ' . $userId);
                    Work::$sql->query('DELETE FROM atk_furycutter  WHERE user = ' . $userId);
                    Work::$sql->query('DELETE FROM atk_echo       WHERE user = ' . $userId);
                    Work::$sql->query('DELETE FROM atk_tripleaxel WHERE user = ' . $userId);
                    Work::$sql->query('DELETE FROM battle_block   WHERE user = ' . $userId);

                    // Техническая запись в лог (как было)
                    $battle_id = $this->battleInfo['id'] ?? 0;
                    $round     = $this->battleInfo['round'] ?? 0;
                    $log_user_id = $userId;
                    $text = json_encode([["user" => $userId]]);
                    $text_sql = Work::$sql->real_escape_string($text);
                    if ($battle_id > 0 && $log_user_id > 0) {
                        $sql = "INSERT INTO battle_log (battle, round, text, end, user, starter) VALUES (
                            $battle_id, $round, '$text_sql', 0, $log_user_id, 0
                        )";
                        Work::$sql->query($sql);
                    }

                    // Форс‑замена
                    $this->userData['targetAtk']     = 9999; // swap
                    $this->enemyData['targetAtk']    = 754;  // skip/пустой ответ
                    $this->userData['targetPokemon'] = $targetPoke->_getID();

                    $this->update['my']  = true;
                    $this->next_turn     = 1;

                    // ВАЖНО: не ставим таймеры вручную — goRound/handleAttackTimer сделают это корректно
                    $this->goRound();
                } else {
                    if ($targetPoke->hp <= 0) {
                        _setError('Ошибка! У покемона недостаточно здоровья для выхода в бой.', 'plus');
                        return;
                    } else {
                        $this->userData['targetAtk']     = 9999; // swap
                        $this->userData['targetPokemon'] = $targetPoke->_getID();

                        // ВАЖНО: не ставим таймер себе — его получит «ждущая» сторона внутри goRound/handleAttackTimer
                        $this->goRound();
                        $this->update['my'] = true;
                    }
                }
            } else {
                // Первичное выставление активного
                $this->userTarget = $this->userPokes['p' . $targetIndex];
                $this->userData['target'] = $targetIndex;
                $this->update['my'] = true;
            }
        }
        return;
    }

    // — Особые атаки на союзника —
    $attackMap = [
        'aHH' => 'targetHH', 'aPS' => 'targetPS', 'aHW' => 'targetHW',
        'aUT' => 'targetUT', 'aWS' => 'targetWS', 'aBP' => 'targetBP', 'aAM' => 'targetAM'
    ];
    $attackIdMap = [
        'aHH' => 235, 'aPS' => 374, 'aHW' => 229,
        'aUT' => 583, 'aWS' => 592, 'aBP' => 34, 'aAM' => 21
    ];

    foreach ($attackMap as $postKey => $targetField) {
        if (isset($_POST[$postKey])) {
            // Просрочка перед действием?
            if ($this->checkTimeout($this->enemyData)) {
                $this->lose((int)($this->enemyInfo['id'] ?? 0), self::LOSE_TIMEOUT);
                return;
            }
            if ($this->checkTimeout($this->userData)) {
                $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_TIMEOUT);
                return;
            }

            if (!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)) {
                $atkId   = $attackIdMap[$postKey];
                $target2 = intval(escapeMe($_POST[$postKey]));
                if ($this->userTarget && $this->enemyTarget && isset($this->userTarget['atkList']['a' . $atkId])) {
                    if ($this->userTarget['hp'] > 0) {
                        $atk = $this->userTarget['atkList']['a' . $atkId];
                        if (isset($atk['id'])) {
                            $target2obj = new PokeBattle($this->userPokes['p' . $target2]);
                            $this->userData[$targetField] = $target2obj->_getID();
                            $this->userData['targetAtk']  = $atk['id'];
                            $disable = explode(',', $this->userTarget['disable_my']);
                            if ($disable[$atk['attack_num']] == 0) {
                                // ВАЖНО: не ставим таймер себе; «ждущая» сторона получит таймер в goRound/handleAttackTimer
                                $this->goRound();
                                $this->update['my'] = true;
                            }
                        }
                    }
                }
            }
            return;
        }
    }

    // — Обычная атака —
    if (isset($_POST['targetAtk'])) {
        // Просрочка перед действием?
        if ($this->checkTimeout($this->enemyData)) {
            $this->lose((int)($this->enemyInfo['id'] ?? 0), self::LOSE_TIMEOUT);
            return;
        }
        if ($this->checkTimeout($this->userData)) {
            $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_TIMEOUT);
            return;
        }

        if (!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)) {
            $target = intval(escapeMe($_POST['targetAtk']));
            if ($this->userTarget && $this->enemyTarget && $target > 0 && isset($this->userTarget['atkList']['a' . $target])) {
                if ($this->userTarget['hp'] > 0) {
                    $atk = $this->userTarget['atkList']['a' . $target];
                    $disable = explode(',', $this->userTarget['disable_my']);
                    if ($disable[$atk['attack_num']] == 0 && isset($atk['id'])) {
                        $this->userData['mega']      = (!empty($_POST['mega']) && in_array((string)$_POST['mega'], ['1','on','true'], true)) ? 1 : 0;
                        $this->userData['tera']      = (!empty($_POST['tera']) && in_array((string)$_POST['tera'], ['1','on','true'], true)) ? 1 : 0;
                        $this->userData['targetAtk'] = $atk['id'];

                        // ВАЖНО: не ставим таймер себе — он у «ждущей» стороны
                        $this->goRound();
                        $this->update['my'] = true;
                    }
                }
            }
        }
        return;
    }

    // — Сдаться —
    if (isset($_POST['coward'])) {
        $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_COWARD);
        return;
    }

    // — Попытка поимки покемона —
    if (isset($_POST['catch'])) {
        // Просрочка перед действием?
        if ($this->checkTimeout($this->enemyData)) {
            $this->lose((int)($this->enemyInfo['id'] ?? 0), self::LOSE_TIMEOUT);
            return;
        }
        if ($this->checkTimeout($this->userData)) {
            $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_TIMEOUT);
            return;
        }

        if (!(isset($this->userData['targetAtk']) && $this->userData['targetAtk'] > 0)) {
            if ($this->userTarget && $this->userTarget['hp'] > 0) {
                // Внутри pokeCatch() устанавливается targetAtk=9998 и далее вызывается goRound() по общей логике
                $this->pokeCatch(escapeMe($_POST['catch']));
            }
        }
        return;
    }

    // — Синхронизация таймера (PvP) —
    if (isset($_POST['check_timer'])) {
        // 1) Если чья-то очередь просрочена — сразу присуждаем поражение
        $this->applyTurnTimeoutIfExpired();

        // 2) Если оба выбрали — запускаем раунд
        if ($this->_isPVP()) {
            $userAtk  = (int)($this->userData['targetAtk']  ?? 0);
            $enemyAtk = (int)($this->enemyData['targetAtk'] ?? 0);
            if ($userAtk > 0 && $enemyAtk > 0) {
                $this->goRound();
            }
        }
        return;
    }
}




    /* ——— Командный режим: включение (Гаунтлет 6×6) ——— */
/* ——— Командный режим: включение (без other) ——— */
/* ===================== TEAM: хватит этого мини-набора ===================== */

/** Обеспечить строку в battle_team и поднять флаги в battle */
private function ensureTeamRow(int $battleId, string $mode = 'gauntlet', int $limit = 6): void {
    $row = Work::$sql->query("SELECT battle_id FROM battle_team WHERE battle_id={$battleId} LIMIT 1")->fetch_assoc();
    if ($row) {
        $stmt = Work::$sql->prepare("UPDATE battle_team SET mode=?, team_limit=?, updated_at=NOW() WHERE battle_id=?");
        $stmt->bind_param('sii', $mode, $limit, $battleId);
        $stmt->execute(); $stmt->close();
    } else {
        $created = date('Y-m-d H:i:s');
        $stmt = Work::$sql->prepare("INSERT INTO battle_team (battle_id, mode, team_limit, created_at, updated_at) VALUES (?,?,?,?,NOW())");
        $stmt->bind_param('isis', $battleId, $mode, $limit, $created);
        $stmt->execute(); $stmt->close();
    }
    Work::$sql->query("UPDATE battle SET is_command_battle=1, team_mode='".$mode."' WHERE id=".$battleId);
}

/** Добавить участника в сторону */
private function addMemberDb(int $battleId, int $uid, int $side, string $role = 'member'): void {
    if ($uid <= 0) return;
    $side = ($side===1 || $side===2) ? $side : 1;

    $ex = Work::$sql->query("SELECT id FROM battle_team_member WHERE battle_id={$battleId} AND user_id={$uid} LIMIT 1")->fetch_assoc();
    if ($ex) return;

    $posRow = Work::$sql->query("SELECT IFNULL(MAX(position),0)+1 AS p FROM battle_team_member WHERE battle_id={$battleId} AND side={$side}")->fetch_assoc();
    $pos    = (int)($posRow['p'] ?? 1);
    $status = 'queued';

    $stmt = Work::$sql->prepare(
        "INSERT INTO battle_team_member (battle_id, side, user_id, role, status, position, joined_at, updated_at)
         VALUES (?,?,?,?,?,?,NOW(),NOW())"
    );
    $stmt->bind_param('iiisss', $battleId, $side, $uid, $role, $status, $pos);
    $stmt->execute(); $stmt->close();
}

/** Удалить участника из командного боя */
private function removeMemberDb(int $battleId, int $uid): void {
    Work::$sql->query("DELETE FROM battle_team_member WHERE battle_id={$battleId} AND user_id={$uid}");
}

/** Сторона участника (1/2/null) */
private function isMemberDb(int $battleId, int $uid): ?int {
    $r = Work::$sql->query("SELECT side FROM battle_team_member WHERE battle_id={$battleId} AND user_id={$uid} LIMIT 1")->fetch_assoc();
    return $r ? (int)$r['side'] : null;
}

/** Загрузить состав для фронта (ВАЖНО: user_group!) */
private function loadTeamFromDb(int $battleId): array {
    $teamRow = Work::$sql->query("SELECT * FROM battle_team WHERE battle_id={$battleId} LIMIT 1")->fetch_assoc() ?: [];
    $members = [1 => [], 2 => []];

    $sql = "
        SELECT m.side, m.user_id, u.login, u.user_group AS ugroup
        FROM battle_team_member m
        LEFT JOIN users u ON u.id = m.user_id
        WHERE m.battle_id = {$battleId}
        ORDER BY m.side, m.position, m.id
    ";
    if ($q = Work::$sql->query($sql)) {
        while ($r = $q->fetch_assoc()) {
            $members[(int)$r['side']][] = [
                'id'    => (int)$r['user_id'],
                'login' => (string)($r['login'] ?? ''),
                'group' => (int)($r['ugroup'] ?? 6),
            ];
        }
    }

    return [
        'name1'      => 'Сторона 1',
        'name2'      => 'Сторона 2',
        'organizers' => array_values(array_filter([
            (int)($teamRow['side1_organizer'] ?? 0),
            (int)($teamRow['side2_organizer'] ?? 0),
        ])),
        'locked'     => false,
        'token'      => '',
        'sides'      => [1 => ['members'=>$members[1]], 2 => ['members'=>$members[2]]],
        'team_limit' => (int)($teamRow['team_limit'] ?? 6),
    ];
}

/** Включить командный бой (кнопка) */
private function makeTeam(): void {
    if (!$this->_isPVP()) { _setError('Командный бой доступен только в PvP.', 'plus'); return; }

    $bid = (int)($this->battleInfo['id'] ?? 0);
    if ($bid <= 0) return;

    $this->ensureTeamRow($bid, 'gauntlet', 6);

    $uid = (int)($this->userInfo['id'] ?? 0);
    if ($uid <= 0) return;

    $sideMe = ((int)$this->battleInfo['user_1'] === $uid) ? 1 : 2;
    $this->addMemberDb($bid, $uid, $sideMe, 'organizer');

    $this->update['my'] = true;
}

/** Вступить в команду (по стороне) */
private function joinTeam(int $side): void {
    if ($side!==1 && $side!==2) { _setError('Неверно указана сторона.', 'plus'); return; }
    if (!$this->_isPVP())       { _setError('Командный бой доступен только в PvP.', 'plus'); return; }

    $bid = (int)($this->battleInfo['id'] ?? 0);
    $uid = (int)($this->userInfo['id'] ?? 0);
    if ($bid<=0 || $uid<=0) return;

    // запрет перебежек
    $curr = $this->isMemberDb($bid, $uid);
    if ($curr && $curr !== $side) { _setError('Нельзя менять сторону в командном бою.', 'plus'); return; }
    if ($curr === $side) { $this->update['my'] = true; return; }

    $this->ensureTeamRow($bid, 'gauntlet', 6);
    $this->addMemberDb($bid, $uid, $side, 'member');
    $this->update['my'] = true;
}

/** Покинуть командный бой */
private function leaveTeam(): void {
    if (!$this->_isPVP()) return;

    $bid = (int)($this->battleInfo['id'] ?? 0);
    $uid = (int)($this->userInfo['id'] ?? 0);
    if ($bid<=0 || $uid<=0) return;

    $this->removeMemberDb($bid, $uid);

    // если команд больше нет — снимаем флаг
    $row = Work::$sql->query("SELECT COUNT(*) c FROM battle_team_member WHERE battle_id={$bid}")->fetch_assoc();
    if ((int)($row['c'] ?? 0) === 0) {
        Work::$sql->query("UPDATE battle SET is_command_battle=0, team_mode='off' WHERE id=".$bid);
    }
    $this->update['my'] = true;
}

/** Доложить фронту состояние командного боя */
private function appendTeamToReturn(array &$return): void {
    $bid = (int)($this->battleInfo['id'] ?? 0);
    $isCmd = (int)($this->battleInfo['is_command_battle'] ?? 0) === 1;

    $team = $this->loadTeamFromDb($bid);

    $uid = (int)($this->userInfo['id'] ?? 0);
    $mySide = $uid > 0 ? $this->isMemberDb($bid, $uid) : null;

    $payload = [
        'name1'      => $team['name1'],
        'name2'      => $team['name2'],
        'organizers' => $team['organizers'],
        'locked'     => $team['locked'],
        'token'      => '',
        'sides'      => [
            1 => ['members' => $team['sides'][1]['members']],
            2 => ['members' => $team['sides'][2]['members']],
        ],
        'mySide'     => $mySide,
        'allowed'    => $this->_isPVP(),
    ];

    $return['isCommandBattle'] = $isCmd;
    $return['team']            = $payload;
    $return['teamAllowed']     = $this->_isPVP();

    if ($isCmd) { $return['labels']['mode'] = 'Командный бой'; }
}


/* ——— Таймер атаки (PvP/PvE) ——— */
private function handleAttackTimer()
{
    if ($this->_isPVP()) {
        $userAtk  = (int)($this->userData['targetAtk']  ?? 0);
        $enemyAtk = (int)($this->enemyData['targetAtk'] ?? 0);
        $now      = time();

        // Выбрал только пользователь — ЖДЁМ СОПЕРНИКА → таймер у enemyData
        if ($userAtk > 0 && $enemyAtk <= 0) {
            $end = (int)($this->enemyData['timer']['atk'] ?? 0);
            if ($end <= $now) {
                $this->enemyData['timer']['atk'] = $now + self::TIMER_ATK_ROUND;
                $this->update['enemy'] = true;
            }
        }
        // Выбрал только соперник — ЖДЁМ ПОЛЬЗОВАТЕЛЯ → таймер у userData
        elseif ($enemyAtk > 0 && $userAtk <= 0) {
            $end = (int)($this->userData['timer']['atk'] ?? 0);
            if ($end <= $now) {
                $this->userData['timer']['atk'] = $now + self::TIMER_ATK_ROUND;
                $this->update['my'] = true;
            }
        }
        // Оба выбрали — таймеры не нужны
        elseif ($userAtk > 0 && $enemyAtk > 0) {
            if (!empty($this->userData['timer']['atk']))  { $this->userData['timer']['atk']  = 0; $this->update['my']    = true; }
            if (!empty($this->enemyData['timer']['atk'])) { $this->enemyData['timer']['atk'] = 0; $this->update['enemy'] = true; }
        }
    } else {
        // PvE: таймеры на клиенте не используются
        if (!empty($this->userData['timer']['atk'])) {
            $this->userData['timer']['atk'] = 0;
            $this->update['my'] = true;
        }
    }
}


    private function checkTimeout($userInfo) {
        if ($userInfo && isset($userInfo['timer'])) {
            $time = time();
            if (isset($userInfo['timer']['atk']) && $userInfo['timer']['atk'] > 0 && $time > $userInfo['timer']['atk']) {
                return true;
            }
        }
        return false;
    }

    /* ——— Использование предмета/покебола ——— */
    private function pokeCatch($targetID) {
        $userId   = intval($_SESSION['id']);
        $targetID = intval($targetID);

        if ($targetID <= 0) {
            _setError('Некорректный предмет.', 'plus');
            return;
        }

        // Предмет игрока
        $sel = Work::$sql->query('
            SELECT
                bi.id AS number,
                bi.name,
                bi.type,
                bi.info,
                ui.id,
                ui.count,
                bi.battle
            FROM items_users AS ui
            INNER JOIN base_items AS bi ON bi.id = ui.item_id
            WHERE ui.id = '.$targetID.' AND ui.user = '.$userId.'
            LIMIT 1
        ')->fetch_assoc();

        if (empty($sel) || empty($sel['count']) || $sel['count'] <= 0 || $sel['battle'] != '1') {
            _setError('Предмет не найден или не может быть использован в бою.', 'plus');
            return;
        }

        $itemType      = $sel['type'];
        $itemCanBeUsed = false;
        $itemVal       = ($sel['info'] > 0 ? floatval($sel['info']) : 1);

        // Поймать покемона можно только если enemyInfo['catch'] > 0
        if ($itemType == 'ball') {
            if (!isset($this->enemyInfo['catch']) || $this->enemyInfo['catch'] <= 0) {
                _setError('Этого покемона ловить нельзя!', 'plus');
                return;
            }
            // Не даём использовать, если активен статус two_turn (Dig/Fly и т.п.)
            if (isset($this->userTarget['status_list']['two_turn'])) {
                _setError('Покемон недоступен для поимки в данный момент!', 'plus');
                return;
            }
            $itemCanBeUsed = true;
        } else {
            // Любой другой предмет (лечение/баф): максимум 2 на покемона
            if (!isset($this->userTarget['item_battle']) || $this->userTarget['item_battle'] > 1) {
                _setError('Вы уже использовали 2 предмета на этого покемона!', 'plus');
                return;
            }
            if (isset($this->userTarget['status_list']['two_turn'])) {
                _setError('Покемон недоступен для использования предмета в данный момент!', 'plus');
                return;
            }
            $itemCanBeUsed = true;
        }

        if ($itemCanBeUsed) {
            minus_item_id($targetID, 1, $userId);

            $this->userData['targetAtk'] = 9998;
            $this->userData['targetItem'] = [
                'number' => intval($sel['number']),
                'name'   => $sel['name'],
                'class'  => $itemType,
                'val'    => $itemVal
            ];

            if (!$this->goRound()) {
                $this->userData['timer']['atk'] = time() + self::TIMER_ATK_ROUND;
                $this->update['my'] = true;
            }
        }
    }

/* ——— Запуск раунда ——— */
private function goRound() {
    // На всякий случай ещё раз проверим таймаут прямо перед запуском
    $this->applyTurnTimeoutIfExpired();

    // PvE: автоатака дикого покемона
    if (!$this->_isPVP()) {
        if (isset($this->enemyTarget['attacks'], $this->enemyTarget['atkList'])) {
            if (!is_array($this->enemyTarget['attacks'])) {
                $this->enemyTarget['attacks'] = explode(',', $this->enemyTarget['attacks']);
            }
            if (!empty($this->enemyTarget['attacks'])) {
                shuffle($this->enemyTarget['attacks']);
                $this->enemyData['targetAtk'] = intval($this->enemyTarget['attacks'][0]);
            }
        }
    }

    $userAtk  = isset($this->userData['targetAtk'])  ? (int)$this->userData['targetAtk']  : 0;
    $enemyAtk = isset($this->enemyData['targetAtk']) ? (int)$this->enemyData['targetAtk'] : 0;

    // FIX TIMER (PvP): если готов только один — заводим таймер «ждущей» стороны (если ещё не активен) и не запускаем бой
    if ($this->_isPVP() && $this->next_turn != 1) {
        $now = time();

        // Пользователь сходил, соперник нет → таймер ставим сопернику
        if ($userAtk > 0 && $enemyAtk <= 0) {
            $end = (int)($this->enemyData['timer']['atk'] ?? 0);
            if ($end <= $now) {
                $this->enemyData['timer']['atk'] = $now + self::TIMER_ATK_ROUND;
                $this->update['enemy'] = true;
            }
            return false;
        }

        // Соперник сходил, пользователь нет → таймер ставим пользователю
        if ($enemyAtk > 0 && $userAtk <= 0) {
            $end = (int)($this->userData['timer']['atk'] ?? 0);
            if ($end <= $now) {
                $this->userData['timer']['atk'] = $now + self::TIMER_ATK_ROUND;
                $this->update['my'] = true;
            }
            return false;
        }
    } else {
        // PvE — таймер не используем
        $this->userData['timer']['atk'] = 0;
    }

    // Условие запуска
    $canStart = $this->_isPVP()
        ? (($userAtk > 0 && $enemyAtk > 0) || $this->next_turn == 1)
        : (($enemyAtk > 0) || $this->next_turn == 1);

    if ($canStart) {
        $last_atk_my_id = $userAtk;
        $this->update['enemy'] = true;

        $u = &$this->userTarget;
        $e = &$this->enemyTarget;


        // --- TERA: сообщения для лога (будем вставлять перед строкой атаки) ---
        $teraMsgUser = '';
        $teraMsgEnemy = '';
        // Natural Cure при замене (уходящий)
        if (isset($this->userData['prevTarget']) && isset($this->userPokes['p' . $this->userData['prevTarget']])) {
            $prevUserPoke = &$this->userPokes['p' . $this->userData['prevTarget']];
            if (isset($prevUserPoke['ability']) && $prevUserPoke['ability'] == 120) {
                if (isset($prevUserPoke['status']) && $prevUserPoke['status'] != '' && $prevUserPoke['status'] != 'none') {
                    $prevUserPoke['status'] = 'none';
                    $this->log[] = $prevUserPoke['name'].' исцеляет свой статус благодаря способности <div class="Ability" onclick="issetAll(120,\'ability\')">Естественное исцеление</div>.';
                }
            }
        }
        if (isset($this->enemyData['prevTarget']) && isset($this->enemyPokes['p' . $this->enemyData['prevTarget']])) {
            $prevEnemyPoke = &$this->enemyPokes['p' . $this->enemyData['prevTarget']];
            if (isset($prevEnemyPoke['ability']) && $prevEnemyPoke['ability'] == 120) {
                if (isset($prevEnemyPoke['status']) && $prevEnemyPoke['status'] != '' && $prevEnemyPoke['status'] != 'none') {
                    $prevEnemyPoke['status'] = 'none';
                    $this->log[] = $prevEnemyPoke['name'].' исцеляет свой статус благодаря способности <div class="Ability" onclick="issetAll(120,\'ability\')">Естественное исцеление</div>.';
                }
            }
        }

        // Обязательный ход
        if ($this->next_turn == 1) {
            $this->enemyData['targetAtk'] = 754;
        }

        // --- TERA: применяем терастализацию ДО запуска Battle (видимая смена типа в бою) ---
if (!empty($this->userData['tera']) && empty($this->userData['tera_used'])) {

    $pid = isset($u['id']) ? (int)$u['id'] : 0;
    $teraType = (!empty($u['tera_type']) ? (string)$u['tera_type'] : '');

    // Если у активного покемона нет тератипа — игнорируем запрос (не тратим tera_used)
    if (empty($teraType)) {
        $this->userData['tera'] = 0;
    } else {
        $this->userData['tera_used'] = 1;
        $this->userData['tera'] = 0;

        // флаги
        $u['tera_type'] = $teraType;
        $u['tera_active'] = 1;

        // сохранить исходные типы (для STAB/логики)
        if (!isset($u['tera_orig_type']))     $u['tera_orig_type']     = ($u['base_type'] ?? '');
        if (!isset($u['tera_orig_type_two'])) $u['tera_orig_type_two'] = ($u['base_type_two'] ?? '');

        // подмена типа в бою (как в Pokémon Showdown): защита/отображение идут по tera_type
        $u['base_type'] = $teraType;
        $u['base_type_two'] = '';

        // синхронизация с массивами стороны (важно: userTarget не всегда ссылка на pokeLIst)
        if ($pid > 0) {
            $k = 'p' . $pid;

            if (isset($this->userPokes[$k]) && is_array($this->userPokes[$k])) {
                $this->userPokes[$k]['tera_type'] = $teraType;
                $this->userPokes[$k]['tera_active'] = 1;
                if (!isset($this->userPokes[$k]['tera_orig_type']))     $this->userPokes[$k]['tera_orig_type']     = ($this->userPokes[$k]['base_type'] ?? '');
                if (!isset($this->userPokes[$k]['tera_orig_type_two'])) $this->userPokes[$k]['tera_orig_type_two'] = ($this->userPokes[$k]['base_type_two'] ?? '');
                $this->userPokes[$k]['base_type'] = $teraType;
                $this->userPokes[$k]['base_type_two'] = '';
            }

            if (isset($this->userData['pokeLIst'][$k]) && is_array($this->userData['pokeLIst'][$k])) {
                $this->userData['pokeLIst'][$k]['tera_type'] = $teraType;
                $this->userData['pokeLIst'][$k]['tera_active'] = 1;
                if (!isset($this->userData['pokeLIst'][$k]['tera_orig_type']))     $this->userData['pokeLIst'][$k]['tera_orig_type']     = ($this->userData['pokeLIst'][$k]['base_type'] ?? '');
                if (!isset($this->userData['pokeLIst'][$k]['tera_orig_type_two'])) $this->userData['pokeLIst'][$k]['tera_orig_type_two'] = ($this->userData['pokeLIst'][$k]['base_type_two'] ?? '');
                $this->userData['pokeLIst'][$k]['base_type'] = $teraType;
                $this->userData['pokeLIst'][$k]['base_type_two'] = '';
            }
            if (isset($this->userData['pokeList'][$k]) && is_array($this->userData['pokeList'][$k])) {
                $this->userData['pokeList'][$k]['tera_type'] = $teraType;
                $this->userData['pokeList'][$k]['tera_active'] = 1;
                if (!isset($this->userData['pokeList'][$k]['tera_orig_type']))     $this->userData['pokeList'][$k]['tera_orig_type']     = ($this->userData['pokeList'][$k]['base_type'] ?? '');
                if (!isset($this->userData['pokeList'][$k]['tera_orig_type_two'])) $this->userData['pokeList'][$k]['tera_orig_type_two'] = ($this->userData['pokeList'][$k]['base_type_two'] ?? '');
                $this->userData['pokeList'][$k]['base_type'] = $teraType;
                $this->userData['pokeList'][$k]['base_type_two'] = '';
            }
        }

        $teraMsgUser = 'Терастализация! <img src="/img/world/typs/'.$teraType.'.png" style="width:18px;vertical-align:middle;"> Покемон стал <b>'.$teraType.'</b>-типа.';
    }
}

// --- TERA: PvP (если соперник тоже отправил tera=1) ---
if ($this->_isPVP() && !empty($this->enemyData['tera']) && empty($this->enemyData['tera_used'])) {

    $epid = isset($e['id']) ? (int)$e['id'] : 0;
    $teraTypeE = (!empty($e['tera_type']) ? (string)$e['tera_type'] : '');

    // Если у активного покемона соперника нет тератипа — игнорируем запрос (не тратим tera_used)
    if (empty($teraTypeE)) {
        $this->enemyData['tera'] = 0;
    } else {
        $this->enemyData['tera_used'] = 1;
        $this->enemyData['tera'] = 0;

        $e['tera_type'] = $teraTypeE;
        $e['tera_active'] = 1;
        if (!isset($e['tera_orig_type']))     $e['tera_orig_type']     = ($e['base_type'] ?? '');
        if (!isset($e['tera_orig_type_two'])) $e['tera_orig_type_two'] = ($e['base_type_two'] ?? '');
        $e['base_type'] = $teraTypeE;
        $e['base_type_two'] = '';

        if ($epid > 0) {
            $ek = 'p' . $epid;

            if (isset($this->enemyPokes[$ek]) && is_array($this->enemyPokes[$ek])) {
                $this->enemyPokes[$ek]['tera_type'] = $teraTypeE;
                $this->enemyPokes[$ek]['tera_active'] = 1;
                if (!isset($this->enemyPokes[$ek]['tera_orig_type']))     $this->enemyPokes[$ek]['tera_orig_type']     = ($this->enemyPokes[$ek]['base_type'] ?? '');
                if (!isset($this->enemyPokes[$ek]['tera_orig_type_two'])) $this->enemyPokes[$ek]['tera_orig_type_two'] = ($this->enemyPokes[$ek]['base_type_two'] ?? '');
                $this->enemyPokes[$ek]['base_type'] = $teraTypeE;
                $this->enemyPokes[$ek]['base_type_two'] = '';
            }

            if (isset($this->enemyData['pokeLIst'][$ek]) && is_array($this->enemyData['pokeLIst'][$ek])) {
                $this->enemyData['pokeLIst'][$ek]['tera_type'] = $teraTypeE;
                $this->enemyData['pokeLIst'][$ek]['tera_active'] = 1;
                if (!isset($this->enemyData['pokeLIst'][$ek]['tera_orig_type']))     $this->enemyData['pokeLIst'][$ek]['tera_orig_type']     = ($this->enemyData['pokeLIst'][$ek]['base_type'] ?? '');
                if (!isset($this->enemyData['pokeLIst'][$ek]['tera_orig_type_two'])) $this->enemyData['pokeLIst'][$ek]['tera_orig_type_two'] = ($this->enemyData['pokeLIst'][$ek]['base_type_two'] ?? '');
                $this->enemyData['pokeLIst'][$ek]['base_type'] = $teraTypeE;
                $this->enemyData['pokeLIst'][$ek]['base_type_two'] = '';
            }
            if (isset($this->enemyData['pokeList'][$ek]) && is_array($this->enemyData['pokeList'][$ek])) {
                $this->enemyData['pokeList'][$ek]['tera_type'] = $teraTypeE;
                $this->enemyData['pokeList'][$ek]['tera_active'] = 1;
                if (!isset($this->enemyData['pokeList'][$ek]['tera_orig_type']))     $this->enemyData['pokeList'][$ek]['tera_orig_type']     = ($this->enemyData['pokeList'][$ek]['base_type'] ?? '');
                if (!isset($this->enemyData['pokeList'][$ek]['tera_orig_type_two'])) $this->enemyData['pokeList'][$ek]['tera_orig_type_two'] = ($this->enemyData['pokeList'][$ek]['base_type_two'] ?? '');
                $this->enemyData['pokeList'][$ek]['base_type'] = $teraTypeE;
                $this->enemyData['pokeList'][$ek]['base_type_two'] = '';
            }
        }

        $teraMsgEnemy = 'Соперник терасталлизовался! <img src="/img/world/typs/'.$teraTypeE.'.png" style="width:18px;vertical-align:middle;">';
    }
}


// Запуск механики боя
        try {
            $battle = new Battle($this, $this->userData, $this->enemyData, $u, $e);
        } catch (\Throwable $ex) {
            error_log("Battle error: " . $ex->getMessage());
            _setError('Ошибка боя. Попробуйте обновить страницу.', 'plus');
            return false;
        }

        // Обновление покемонов после раунда
        if (isset($u['id'])) {
            $this->userPokes['p' . $u['id']] = $u;
        }
        if (isset($e['id'])) {
            $this->enemyPokes['p' . $e['id']] = $e;
        }

        // PvE: если дикий пал — авто-ретаргет
        if (!$this->_isPVP() && isset($this->enemyTarget['hp']) && $this->enemyTarget['hp'] <= 0) {
            $nextTarget = null;
            foreach ($this->enemyPokes as $k => $poke) {
                if (!empty($poke) && isset($poke['hp']) && $poke['hp'] > 0) {
                    $nextTarget = intval(substr($k, 1));
                    break;
                }
            }
            if ($nextTarget !== null) {
                $this->enemyData['target'] = $nextTarget;
                $this->enemyTarget = $this->enemyPokes['p' . $nextTarget];
            }
        }

        // --- ДОПОЛНЕНИЕ: эффекты конца ТЕКУЩЕГО раунда (Wish) ---
        $extraRoundLog = [];

        $battle_id = isset($this->battleInfo['id']) ? intval($this->battleInfo['id']) : 0;
        $roundNow  = isset($this->battleInfo['round']) ? intval($this->battleInfo['round']) : 0;

        $wishRes = Work::$sql->query("
            SELECT `id`,`user`,`end`
              FROM `battle_effects`
             WHERE `battle` = {$battle_id}
               AND `name`   = 'Wish'
               AND `rnd`    = {$roundNow}
        ");
        if ($wishRes && $wishRes->num_rows > 0) {
            while ($w = $wishRes->fetch_assoc()) {
                $sideUser = intval($w['user']);
                $amount   = intval($w['end']);

                if ($sideUser === intval($this->userInfo['id'])) {
                    if (isset($u['hp']) && $u['hp'] > 0) {
                        $heal = min($amount, max(0, $u['hp_max'] - $u['hp']));
                        if ($heal > 0) {
                            $u['hp'] += $heal;
                            $extraRoundLog[] = 'Желание восстанавливает здоровье <span class="HpPlus">+'.$heal.' HP</span>';
                        } else {
                            $extraRoundLog[] = 'Желание срабатывает, но здоровье уже полное.';
                        }
                    }
                } else {
                    if (isset($e['hp']) && $e['hp'] > 0) {
                        $heal = min($amount, max(0, $e['hp_max'] - $e['hp']));
                        if ($heal > 0) {
                            $e['hp'] += $heal;
                            $extraRoundLog[] = 'Желание соперника восстанавливает здоровье <span class="HpPlus">+'.$heal.' HP</span>';
                        } else {
                            $extraRoundLog[] = 'Желание соперника срабатывает, но здоровье уже полное.';
                        }
                    }
                }

                Work::$sql->query("DELETE FROM `battle_effects` WHERE `id` = ".intval($w['id'])." LIMIT 1");
            }
        }
        // --- КОНЕЦ ДОПОЛНЕНИЯ ---

        // Логи хода
        $battleLogs = $battle->_getLog();
        $uid = (int)($this->userInfo['id'] ?? 0);
        $eid = (int)($this->enemyInfo['id'] ?? 0);

        // Вставляем терастализацию в log[] (UI, как правило, рендерит log[], а start[] может не показывать)
        if (!empty($teraMsgUser) || !empty($teraMsgEnemy)) {
            foreach ($battleLogs as &$entry) {
                if (!isset($entry['log']) || !is_array($entry['log'])) {
                    $entry['log'] = [];
                }
                $entryUser = (int)($entry['user'] ?? 0);

                if (!empty($teraMsgUser) && $uid > 0 && $entryUser === $uid) {
                    array_unshift($entry['log'], $teraMsgUser);
                }
                if (!empty($teraMsgEnemy)) {
                    // PvP: по ID соперника, PvE: соперник обычно user=0
                    if (($eid > 0 && $entryUser === $eid) || ($eid <= 0 && $entryUser === 0)) {
                        array_unshift($entry['log'], $teraMsgEnemy);
                    }
                }
            }
            unset($entry);
        }

        // Доп. сообщения (Wish и т.п.) — добавляем в конец log[] каждой стороны
        if (!empty($extraRoundLog) && is_array($extraRoundLog)) {
            foreach ($battleLogs as &$entry) {
                if (!isset($entry['log']) || !is_array($entry['log'])) {
                    $entry['log'] = [];
                }
                foreach ($extraRoundLog as $line) {
                    $entry['log'][] = $line;
                }
            }
            unset($entry);
        }

        // Добавляем активного покемона к каждой стороне в log (мини-иконка + клик в покедекс)
        try {
            $myVI = $this->viewInfoTarget($u, true);
            $enVI = $this->viewInfoTarget($e, false);

            $pokeMe = [
                'basenum' => intval($myVI['basenum'] ?? 0),
                'type'    => (string)($myVI['type'] ?? 'normal'),
                'name'    => (string)($myVI['name'] ?? ''),
            ];
            $pokeEn = [
                'basenum' => intval($enVI['basenum'] ?? 0),
                'type'    => (string)($enVI['type'] ?? 'normal'),
                'name'    => (string)($enVI['name'] ?? ''),
            ];

            foreach ($battleLogs as &$entry) {
                $entryUser = (int)($entry['user'] ?? 0);
                if ($uid > 0 && $entryUser === $uid) $entry['poke'] = $pokeMe;
                else $entry['poke'] = $pokeEn;
            }
            unset($entry);
        } catch (Throwable $t) {
            // ignore
        }



        $logArr = [
            'round'      => $this->battleInfo['round'],
            'log'        => $battleLogs,
            'log_status' => $battle->_getLogStatus()
        ];
        $this->log[] = $logArr;

        $LogGame  = json_encode($logArr['log'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $LogGame1 = json_encode($logArr['log_status'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $battle_id = isset($this->battleInfo['id']) ? intval($this->battleInfo['id']) : 0;
        $round     = isset($this->battleInfo['round']) ? intval($this->battleInfo['round']) : 0;
        $user_id   = isset($this->userInfo['id']) ? intval($this->userInfo['id']) : 0;

        $sql = "INSERT INTO battle_log (battle, round, text, end, user, starter) VALUES (
            " . $battle_id . ",
            " . $round . ",
            '" . Work::$sql->real_escape_string($LogGame) . "',
            '" . Work::$sql->real_escape_string($LogGame1) . "',
            " . $user_id . ",
            0
        )";
        $res = Work::$sql->query($sql);

        // Ответ в обе стороны (чтобы фронт точно получил инкрементальный лог)
        $logPayload = ['log' => $this->log];
        if (!empty($this->userInfo['id']))  { $this->generateAnswer($logPayload, (int)$this->userInfo['id']); }
        if (!empty($this->enemyInfo['id'])) { $this->generateAnswer($logPayload, (int)$this->enemyInfo['id']); }

        // Сброс таймеров (оба выбрали — таймеры не нужны)
        if (isset($this->enemyData['timer']['atk'])) { $this->enemyData['timer']['atk'] = 0; }
        if (isset($this->userData['timer']['atk']))  { $this->userData['timer']['atk']  = 0; }
        $this->next_turn = 0;

        // Ретаргеты (живые есть?)
        $myRetarget    = $this->_isRetarget($this->userPokes);
        $enemyRetarget = $this->_isRetarget($this->enemyPokes);

        // Конец боя?
        if (!$myRetarget && !$enemyRetarget) {
            $this->lose(true, self::LOSE_ALL);
        } elseif (!$myRetarget) {
            $this->lose((int)($this->userInfo['id'] ?? 0), self::LOSE_NO_HP);
        } elseif (!$enemyRetarget) {
            $last_atk_my = false;
            if (isset($u['atkList']['a' . $last_atk_my_id])) {
                $last_atk_my = $u['atkList']['a' . $last_atk_my_id];
            }
            $this->lose((int)($this->enemyInfo['id'] ?? 0), self::LOSE_NO_HP, $last_atk_my);
        }
        return true;
    }

    return false;
}

/* ——— Отрисовка battleInfo для фронта ——— */
private function viewInfo() {
    if ($this->userPokes && $this->enemyPokes) {
        $myAnswer = [];
        if (!empty($this->answer)) {
            $uid = isset($this->userInfo['id']) ? $this->userInfo['id'] : 0;
            if ($uid && isset($this->answer['u' . $uid])) {
                $myAnswer = $this->answer['u' . $uid];
                $this->update['my'] = true;
                unset($this->answer['u' . $uid]);
            }
        }

        // ======= Стартовые эффекты/погода (как у вас) =======
        if ($this->round == 1) {
            $txtStartBattle = '';
            if (
                !isset($this->enemyData['target']) || empty($this->enemyData['target'])
                || !isset($this->userData['target']) || empty($this->userData['target'])
                || !isset($this->enemyPokes['p' . $this->enemyData['target']])
                || !isset($this->userPokes['p' . $this->userData['target']])
            ) {
                $txtStartBattle = 'Подготовка к бою.<br>';
            } else {
                $enemyPokemonStart = $this->enemyPokes['p' . $this->enemyData['target']];
                $userPokemonStart  = $this->userPokes['p' . $this->userData['target']];
                $txtStartBattle = 'Начало боя.<br>';

                if (
                    (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 44) ||
                    (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 44)
                ) {
                    $txtStartBattle .= '<div class=Ability onclick=issetAll(44,\'ability\')>Осушение</div> меняет погоду на Солнечную.';
                    Work::$sql->query('UPDATE battle SET weather = 2, weather_round = 5 WHERE id = ' . intval($this->battleInfo['id']));
                    $this->weather = 2;
                    $this->weather_round = 5;
                }
                if (
                    (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 179) ||
                    (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 179)
                ) {
                    $txtStartBattle .= '<div class=Ability onclick=issetAll(179,\'ability\')>Снежная тревога</div> меняет погоду на Град.';
                    Work::$sql->query('UPDATE battle SET weather = 4, weather_round = 5 WHERE id = ' . intval($this->battleInfo['id']));
                    $this->weather = 4;
                    $this->weather_round = 5;
                }
                if (
                    (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 43 && (!isset($enemyPokemonStart['ability']) || $enemyPokemonStart['ability'] != 113)) ||
                    (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 43 && (!isset($userPokemonStart['ability']) || $userPokemonStart['ability'] != 113))
                ) {
                    $txtStartBattle .= '<div class=Ability onclick=issetAll(43,\'ability\')>Изморось</div> меняет погоду на Дождь.';
                    Work::$sql->query('UPDATE battle SET weather = 3 WHERE id = ' . intval($this->battleInfo['id']));
                    $this->weather = 3;
                }
                if (
                    (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 160) ||
                    (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 160)
                ) {
                    $txtStartBattle .= '<div class=Ability onclick=issetAll(160,\'ability\')>Пробуждение песков</div> меняет погоду на Песчаную бурю.';
                    Work::$sql->query('UPDATE battle SET weather = 5 WHERE id = ' . intval($this->battleInfo['id']));
                    $this->weather = 5;
                }
                if (isset($enemyPokemonStart['numb']) && $enemyPokemonStart['numb'] == 9595) {
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['atk']['plus']  = 2;
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['satk']['plus'] = 2;
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['spd']['plus']  = 2;
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['def']['plus']  = 2;
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['sdef']['plus'] = 2;
                    $txtStartBattle .= '<br>' . $this->_getNameBattle($enemyPokemonStart) . ' повысил свои статы';
                }
                if (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 89) {
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['atk']['minus'] = 1;
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(89,\'ability\')>Яростный взгляд</div> понижает Атаку  ' . $this->_getNameBattle($enemyPokemonStart);
                }
                if (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 89) {
                    $this->userPokes['p' . $this->userData['target']]['modified']['atk']['minus'] = 1;
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(89,\'ability\')>Яростный взгляд</div> понижает Атаку  ' . $this->_getNameBattle($userPokemonStart);
                }
                if (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 255) {
                    $this->userPokes['p' . $this->userData['target']]['modified']['atk']['plus'] = 1;
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(255,\'ability\')>Отважный мечник</div> повышает Атаку  ' . $this->_getNameBattle($userPokemonStart);
                }
                if (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 255) {
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['atk']['plus'] = 1;
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(255,\'ability\')>Отважный мечник</div> повышает Атаку  ' . $this->_getNameBattle($enemyPokemonStart);
                }
                if (isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 256) {
                    $this->userPokes['p' . $this->userData['target']]['modified']['def']['plus'] = 1;
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(256,\'ability\')>Бесстрашный защитник</div> повышает Защиту  ' . $this->_getNameBattle($userPokemonStart);
                }
                if (isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 256) {
                    $this->enemyPokes['p' . $this->enemyData['target']]['modified']['def']['plus'] = 1;
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(256,\'ability\')>Бесстрашный защитник</div> повышает Защиту  ' . $this->_getNameBattle($enemyPokemonStart);
                }
                if (
                    isset($userPokemonStart['ability']) && $userPokemonStart['ability'] == 96 &&
                    (!isset($enemyPokemonStart['ability']) || $enemyPokemonStart['ability'] != 113)
                ) {
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(96,\'ability\')>Левитация</div> позволяет ' . $this->_getNameBattle($userPokemonStart) . ' левитировать.';
                    $this->userPokes['p' . $this->userData['target']]['status_list']['levitation'] = [
                        'type'  => 'levitation',
                        'count' => 9999,
                        'val'   => 0
                    ];
                }
                if (
                    isset($enemyPokemonStart['ability']) && $enemyPokemonStart['ability'] == 96 &&
                    (!isset($userPokemonStart['ability']) || $userPokemonStart['ability'] != 113)
                ) {
                    $txtStartBattle .= '<br><div class=Ability onclick=issetAll(96,\'ability\')>Левитация</div> позволяет ' . $this->_getNameBattle($enemyPokemonStart) . ' левитировать.';
                    $this->enemyPokes['p' . $this->enemyData['target']]['status_list']['levitation'] = [
                        'type'  => 'levitation',
                        'count' => 9999,
                        'val'   => 0
                    ];
                }

                $this->update['my']    = true;
                $this->update['enemy'] = true;
            }
            $txtStartBattleSafe = Work::$sql->real_escape_string($txtStartBattle);
            Work::$sql->query('UPDATE battle_log SET text = "' . $txtStartBattleSafe . '" WHERE starter = 1 AND battle = ' . intval($this->battleInfo['id']));
        }

        // ======= battle_log → HTML (как у вас) =======
        $battleId = isset($this->battleInfo['id']) ? intval($this->battleInfo['id']) : 0;
        $LogBattle = Work::$sql->query('SELECT * FROM battle_log WHERE battle = ' . $battleId . ' AND starter != 1 ORDER BY id DESC');
        $LogBattleStart = Work::$sql->query('SELECT * FROM battle_log WHERE battle = ' . $battleId . ' AND starter = 1')->fetch_assoc();

        $LogBattleSel = '';
        if ($LogBattle) {
            while ($Log = $LogBattle->fetch_row()) {
                $LogBattle1   = isset($Log[3]) ? (json_decode($Log[3], true) ?: []) : [];
                $LogEndBattle = isset($Log[4]) ? (json_decode($Log[4], true) ?: []) : [];
                $user1Id      = isset($LogBattle1[0]['user']) ? intval($LogBattle1[0]['user']) : 0;
                $user1        = $user1Id > 0 ? Work::$sql->query('SELECT login,user_group FROM users WHERE id = ' . $user1Id)->fetch_row() : null;

                if (isset($LogBattle1[1]) && isset($LogBattle1[1]['user']) && $LogBattle1[1]['user'] != 0) {
                    $user2Id = intval($LogBattle1[1]['user']);
                    $user2   = Work::$sql->query('SELECT login,user_group FROM users WHERE id = ' . $user2Id)->fetch_row();
                    $userNick2 = $user2 ? '<div class="u-' . $user2[1] . '">' . $user2[0] . '</div>' : '<div class="u-6">Дикий покемон</div>';
                } else {
                    $userNick2 = '<div class="u-6">Дикий покемон</div>';
                }
                $userNick1 = ($user1 && isset($user1[0], $user1[1])) ? '<div class="u-' . $user1[1] . '">' . $user1[0] . '</div>' : '<div class="u-6">Дикий покемон</div>';

                $LogText1 = "";
                $LogText2 = "";
                $LogEnd   = "";

                if (isset($LogBattle1[0]["log"]) && is_array($LogBattle1[0]["log"])) {
                    $pokeHtml1 = '';
                    if (isset($LogBattle1[0]['poke']) && is_array($LogBattle1[0]['poke'])) { $pokeHtml1 = $this->_getNameBattle($LogBattle1[0]['poke']); }
                    $LogText1 .= '<div class="User UserLine">' . $pokeHtml1 . $userNick1 . '</div>';
                    foreach ($LogBattle1[0]["log"] as $a) { $LogText1 .= "<span>" . $a . "</span>"; }
                }
                if (isset($LogBattle1[1]["log"]) && is_array($LogBattle1[1]["log"])) {
                    $pokeHtml2 = '';
                    if (isset($LogBattle1[1]['poke']) && is_array($LogBattle1[1]['poke'])) { $pokeHtml2 = $this->_getNameBattle($LogBattle1[1]['poke']); }
                    $LogText2 .= '<div class="User UserLine">' . $pokeHtml2 . $userNick2 . '</div>';
                    foreach ($LogBattle1[1]["log"] as $b) { $LogText2 .= "<span>" . $b . "</span>"; }
                }
                if (is_array($LogEndBattle)) {
                    foreach ($LogEndBattle as $c) { $LogEnd .= "<span>" . $c . "</span>"; }
                }
                $LogBattleSel .= '<div class="Step"><div class="Round">Раунд ' . (isset($Log[2]) ? $Log[2] : '') . '</div><div class="Process">' . $LogText1 . '</div><div class="Process">' . $LogText2 . $LogEnd . '</div></div>';
            }
        }
        if (isset($LogBattleStart) && !empty($LogBattleStart['text'])) {
            $LogBattleSel .= '<div class="Step"><div class="Process"><span>' . $LogBattleStart['text'] . '</span></div></div>';
        }
        $this->autolog = $LogBattleSel;

        // ======= Таймер: нормализация payload под фронт =======
        $userAtk  = (int)($this->userData['targetAtk']  ?? 0);
        $enemyAtk = (int)($this->enemyData['targetAtk'] ?? 0);

        // Передаём обе «сырые» структуры таймера — моей и соперника — чтобы корректно определить, чей ход и стартовую метку
        $pack = $this->normalizeTimeoutForFrontend(
            $this->userData['timer']  ?? [],
            $userAtk,
            $enemyAtk,
            $this->enemyData['timer'] ?? []
        );

        // ======= Базовый пакет для фронта =======
        $return = [
            'id'            => (int)$battleId,
            'my'            => $this->viewInfoUser($this->userInfo),
            'enemy'         => $this->viewInfoUser($this->enemyInfo),
            'myTarget'      => $this->viewInfoTarget($this->userTarget, true),
            'enemyTarget'   => $this->viewInfoTargetWithWild($this->enemyTarget),
            'myTeam'        => $this->viewInfoTeam($this->userPokes),
            'myTeamHP'      => $this->viewInfoTeamHP($this->userPokes),
            'myInfo'        => ['tera_used' => (int)($this->userData['tera_used'] ?? 0)],
            'enemyInfo'     => ['tera_used' => (int)($this->enemyData['tera_used'] ?? 0)],

            // Таймер (исправлено):
            //  - timeout.atk = TOTAL секунд на ход (а не endTs)
            //  - time        = turnStartTs (epoch начала отсчёта) — фронт посчитает elapsed сам
            //  - myTurn      = чей ход (фронт показывает «Ваш ход/Ход соперника» корректно)
            'timeout'       => $pack['timeout'],
            'time'          => $pack['turnStartTs'],
            'myTurn'        => (bool)$pack['myTurn'],

            'round'         => $this->round,
            'weather'       => $this->weather,
            'weather_round' => $this->weather_round,
            'battleLog'     => $this->autolog,
            'img'           => $this->img,
            'battleId'      => $this->battleId,
            'labels'        => [],
        ];

        // Точечные ответы из answer
        if (isset($myAnswer['log'])) {
            $return['log'] = $myAnswer['log'];
        } elseif (!empty($this->log)) {
            $return['log'] = $this->log;
        }
        if (isset($myAnswer['battleEND'])) {
            $return['battleEND'] = $myAnswer['battleEND'];
        }

        // Отдаём фронту мой answer гарантированно (для battleEND/логов)
        $uid = isset($this->userInfo['id']) ? (int)$this->userInfo['id'] : 0;
        if ($uid > 0 && !empty($myAnswer)) {
            $return['answer'] = ['u'.$uid => $myAnswer];
        }
        if (isset($this->answer['battleEND'])) {
            $return['answer']['battleEND'] = $this->answer['battleEND'];
        }

        // Командный режим (состав, бейдж, доступность)
        $this->appendTeamToReturn($return);
        if (!empty($return['isCommandBattle'])) {
            $return['labels']['mode'] = 'Командный бой';
        }
        $return['teamAllowed'] = $this->_isPVP();

        return $return;
    }
    return [];
}


/** enemyTarget для фронта + wild-поля */
private function viewInfoTargetWithWild($target) {
    $info = $this->viewInfoTarget($target);
    if (!empty($target['wild'])) {
        $info['isWild']    = true;
        $info['catchable'] = isset($target['catchable']) ? (bool)$target['catchable'] : true;
        $info['rareName']  = $target['rareName']  ?? 'Частый';
        $info['rareClass'] = $target['rareClass'] ?? 'PokRare4';
    } else {
        $info['isWild'] = false;
    }
    return $info;
}

private function _getNameBattle($pok) {
    $basenum = intval($pok['basenum'] ?? 0);
    $type    = htmlspecialchars($pok['type'] ?? 'normal');
    $name    = htmlspecialchars($pok['name_new'] ?? ($pok['name'] ?? '???'));
    return '<span class="bgPok" onclick="openDex(' . $basenum . ')"><img src="/img/pokemons/animation/' . $basenum . '.png"> <div class="' . $type . '-color" style="display: inline-block;">#' . Info::getNumPokemonNum($basenum) . ' ' . $name . '</div></span>';
}

private function viewInfoUser($userInfo) {
    if ($userInfo && isset($userInfo['id'])) {
        
        // Mega (возможность / активность) — для отображения кнопки на фронте
        $formLower = strtolower((string)($targetID['form'] ?? ''));
        $mega_active = in_array($formLower, ['mega','megax','megay'], true) ? 1 : 0;
        $mega_can = 0;
        if ($mega_active === 0) {
            $bn = intval($targetID['basenum'] ?? 0);
            if ($bn > 0) {
                static $megaCache = [];
                if (isset($megaCache[$bn])) {
                    $mega_can = $megaCache[$bn];
                } else {
                    $q = Work::$sql->query("SELECT id_form FROM base_pokemon_forms WHERE pokemons = ".$bn." AND id_form IN ('mega','megax','megay') LIMIT 1");
                    $mega_can = ($q && $q->num_rows > 0) ? 1 : 0;
                    $megaCache[$bn] = $mega_can;
                }
            }
        }

return [
            'id'    => intval($userInfo['id']),
            'login' => $userInfo['login'] ?? '',
            'group' => $userInfo['group'] ?? '',
            'sex'   => $userInfo['sex'] ?? '',
            'catch' => $userInfo['catch'] ?? null,
        ];
    }
    return [];
}

/** Подробная информация о покемоне */
private function viewInfoTarget($targetID, $my = false) {
    if (is_array($targetID) && isset($targetID['id'])) {
        $targetID = intval($targetID['id']);
    }
    if (is_numeric($targetID) && $targetID > 0) {
        $targetID = $this->userPokes['p' . $targetID] ?? $this->enemyPokes['p' . $targetID] ?? [];
    }
    if (!empty($targetID) && is_array($targetID)) {
        if (isset($targetID['hp']) && $targetID['hp'] === true) {
            $targetInfo = new PokeBattle($targetID);
            $targetID = $targetInfo->_getData();
        }

        $targetID['stats'] = isset($targetID['stats'])
            ? (is_array($targetID['stats']) ? $targetID['stats'] : explode(',', $targetID['stats']))
            : [];

        // Покеболы второй стороны
        $BallsPoke = '';
        if (isset($this->enemyData['pokeLIst']) && ($this->battleInfo['type'] ?? '') == 'pve') {
            $BallsPoke = '<div class="Ball" style="background-image: url(/img/world/items/little/2.png);"></div>';
        } elseif (isset($this->enemyData['pokeLIst']) && is_array($this->enemyData['pokeLIst'])) {
            foreach ($this->enemyData['pokeLIst'] as $abc) {
                if ($abc) {
                    $noneHp = (isset($abc['hp']) && $abc['hp'] <= 0) ? 'noHp' : '';
                    $ballNum = isset($abc['ball']) ? intval($abc['ball']) : 3;
                    $BallsPoke .= '<div class="Ball ' . $noneHp . '" style="background-image: url(/img/world/items/little/' . $ballNum . '.png);"></div>';
                }
            }
        }

        // Спрайт/форма
        $typeSprite = $targetID['type'] ?? 'normal';
        $form       = (isset($targetID['form']) && $targetID['form'] != "0") ? "_" . $targetID['form'] : "";
        $gmaxClass  = '';
        if ($form == "_gmax") { $form = ""; $gmaxClass = 'class="gmax" '; }
        $sprite = '<img ' . $gmaxClass . 'src="/img/pokemons/sprite/' . htmlspecialchars($typeSprite) . '/' . numbPok($targetID['basenum'] ?? 0) . $form . '.gif">';

        // Пол
        $sex2 = 'mars';
        if (isset($targetID['gender'])) {
            if ($targetID['gender'] == 'Мальчик') $sex2 = 'mars';
            elseif ($targetID['gender'] == 'Девочка') $sex2 = 'venus';
            else $sex2 = 'genderless';
        }

        // EXP
        $lvl = intval($targetID['lvl'] ?? 1);
        $exp = $targetID['exp'] ?? 0;
        $base_exp_group = $targetID['base_exp_group'] ?? '';
        $explvllow = Info::_getExp($lvl - 1, $base_exp_group);
        $explvl  = $exp - $explvllow;
        $explvl2 = ($targetID['exp_max'] ?? 0) - $explvllow;
        $expBar  = $base_exp_group ? Info::_pokeEXP($lvl, $base_exp_group, $explvl, $explvl2) : [];

        // HP
        $hp     = intval($targetID['hp'] ?? 0);
        $hp_max = intval($targetID['stats'][0] ?? 0);

        // PP/disable
        $pp_my      = $targetID['pp_my']      ?? '';
        $disable_my = $targetID['disable_my'] ?? '';

        // Атаки
        $atkList = ($targetID['atkList'] ?? []);
        if (!$my) $atkList = [];

        // Модификаторы/статусы
        $statMod     = $targetID['modified']    ?? [];
        $statusList  = $targetID['status_list'] ?? [];
        $item_battle = $targetID['item_battle'] ?? [];
        $tren        = $targetID['tren']        ?? [];

        // Иконки статусов
        $statusIcons = [];
        if (!empty($statusList) && is_array($statusList)) {
            foreach ($statusList as $statusName => $statusInfo) {
                $statusIcons[] = [
                    'status' => $statusName,
                    'icon'   => "/img/icons/status/{$statusName}.png",
                    'title'  => $statusInfo['type'] ?? $statusName
                ];
            }
        }

        return [
            'id'            => intval($targetID['id'] ?? 0),
            'ball'          => intval($targetID['ball'] ?? 3),
            'basenum'       => intval($targetID['basenum'] ?? 0),
            'basenum2'      => numbPok($targetID['basenum'] ?? 0),
            'form'          => $targetID['form'] ?? "0",
            'name'          => $targetID['name_new'] ?? '...',
            'lvl'           => $lvl,
            'type2'         => ($typeSprite == 'normal' ? '' : $typeSprite),
            'type'          => $typeSprite,
            'tera_type'     => (string)($targetID['tera_type'] ?? ''),
            'tera_active'   => (int)($targetID['tera_active'] ?? 0),
            'mega_can'      => (int)$mega_can,
            'mega_active'   => (int)$mega_active,
            'sex'           => $targetID['gender'] ?? 'Мальчик',
            'sex2'          => $sex2,
            'hp'            => $hp,
            'hp_max'        => $hp_max,
            'hp_before'     => 0,
            'desteny_bond'  => 0,
            'atk_zamena'    => 0,
            'round_before'  => 0,
            'atk_before'    => 0,
            'atk_beforeNow' => 0,
            'metronom'      => 0,
            'exp'           => $expBar,
            'item'          => intval($targetID['item_id'] ?? 0),
            'pp_my'         => $pp_my,
            'disable_my'    => $disable_my,
            'atkList'       => $atkList,
            'statMod'       => $statMod,
            'statusList'    => $statusList,
            'statusIcons'   => $statusIcons,
            'item_battle'   => $item_battle,
            'tren'          => $tren,
            'BallsPoke'     => $BallsPoke,
            'sprite'        => $sprite
        ];
    }
    return [];
}

private function viewInfoTeam($teamList) {
    $return = [];
    if (!empty($teamList) && is_array($teamList)) {
        foreach ($teamList as $value) {
            if (isset($value['id']) && is_numeric($value['id']) && intval($value['id']) > 0) {
                $pokeKey = 'p' . intval($value['id']);
                $return[$pokeKey] = $this->viewInfoTarget($value);
            }
        }
    }
    return $return;
}

private function viewInfoTeamHP($teamList) {
    $result = [];
    if (!empty($teamList) && is_array($teamList)) {
        foreach ($teamList as $value) {
            if (
                isset($value['id']) && is_numeric($value['id']) && intval($value['id']) > 0 &&
                isset($value['hp']) && is_numeric($value['hp']) && intval($value['hp']) > 0
            ) {
                $pokeKey = 'p' . intval($value['id']);
                $result[$pokeKey] = [
                    'id'     => intval($value['id']),
                    'hp'     => intval($value['hp']),
                    'hp_max' => (isset($value['stats'][0]) && is_numeric($value['stats'][0])) ? intval($value['stats'][0]) : 0,
                    'name'   => $value['name_new'] ?? '',
                    'status' => $value['status'] ?? '',
                ];
            }
        }
    }
    return $result;
}

/* ——— Гаунтлет: передача хода стороне, у которой закончились покемоны ——— */
private function maybeGauntletHandoff(int $losingSide): bool {
    $other = Info::_unParseData($this->battleInfo['other'] ?? '[]');
    if (($other['mode'] ?? '') !== 'gauntlet') return false;

    $q = $other['sides'][$losingSide]['queue_users'] ?? [];
    if (empty($q)) return false;

    $nextUser = (int)array_shift($q);
    $other['sides'][$losingSide]['queue_users'] = $q;
    $other['sides'][$losingSide]['active_user'] = $nextUser;

    $uInfo = Info::_userInfoBattle($nextUser, 'pvp', []);
    if (empty($uInfo)) {
        // Пропускаем пустых — рекурсивно
        return $this->maybeGauntletHandoff($losingSide);
    }
    $infoNew = Info::_parseData($uInfo);
    $col = ($losingSide === 1) ? 'info_1' : 'info_2';

    // Чистим эффекты проигравшей стороны
    $stmtDel = Work::$sql->prepare('DELETE FROM `battle_effects` WHERE battle = ? AND side = ?');
    $stmtDel->bind_param('ii', $this->battleInfo['id'], $losingSide);
    $stmtDel->execute();
    $stmtDel->close();

    // Сохраняем battle: новая info_* и обновлённый other
    $sqlOther = Info::_parseData($other);
    $q = Work::$sql->prepare("UPDATE battle SET `$col` = ?, other = ? WHERE id = ?");
    $q->bind_param('ssi', $infoNew, $sqlOther, $this->battleInfo['id']);
    $q->execute();
    $q->close();

    // Обновляем локальные структуры
    if ($losingSide === 1) {
        $this->userData  = $uInfo;
        $this->userInfo  = $uInfo['userInfo'];
        $this->userPokes = $uInfo['pokeInfo'];
    } else {
        $this->enemyData  = $uInfo;
        $this->enemyInfo  = $uInfo['userInfo'];
        $this->enemyPokes = $uInfo['pokeInfo'];
    }
    $this->battleInfo['other'] = $sqlOther;

    return true;
}

private function maybeGauntletHandoffByUserId(int $user_lose_id): bool {
    $other = Info::_unParseData($this->battleInfo['other'] ?? '[]');
    if (!is_array($other) || ($other['mode'] ?? '') !== 'gauntlet') return false;

    $losingSide = ($this->battleInfo['user_1'] == $user_lose_id) ? 1 : 2;
    $queue = $other['sides'][$losingSide]['queue_users'] ?? [];
    if (empty($queue)) return false;

    $nextUser = (int)array_shift($queue);
    $other['sides'][$losingSide]['queue_users'] = $queue;
    $other['sides'][$losingSide]['active_user'] = $nextUser;

    $uInfo = Info::_userInfoBattle($nextUser, 'pvp', []);
    if (empty($uInfo)) return false;

    $infoNew = Info::_parseData($uInfo);
    $col = ($losingSide === 1) ? 'info_1' : 'info_2';

    $stmtDel = Work::$sql->prepare('DELETE FROM `battle_effects` WHERE battle = ? AND side = ?');
    $stmtDel->bind_param('ii', $this->battleInfo['id'], $losingSide);
    $stmtDel->execute();
    $stmtDel->close();

    $sqlOther = Info::_parseData($other);
    $q2 = Work::$sql->prepare("UPDATE battle SET `$col` = ?, other = ? WHERE id = ?");
    $q2->bind_param('ssi', $infoNew, $sqlOther, $this->battleInfo['id']);
    $q2->execute();
    $q2->close();

    if ($losingSide === 1) {
        $this->userData  = $uInfo;
        $this->userInfo  = $uInfo['userInfo'];
        $this->userPokes = $uInfo['pokeInfo'];
    } else {
        $this->enemyData  = $uInfo;
        $this->enemyInfo  = $uInfo['userInfo'];
        $this->enemyPokes = $uInfo['pokeInfo'];
    }
    $this->battleInfo['other'] = $sqlOther;

    return true;
}

/* ——— Завершение боя / дуэли ——— */
public function lose($user_lose_id, $lose_type = 'OTHER', $last_atk = false) {
    // Battle Pass прогрессоры (если не подключены — подгружаем)
    if (!function_exists('bp_progress_kill') || !function_exists('bp_progress_coins')) {
        @require_once $_SERVER['DOCUMENT_ROOT'].'/inc/battlepass_progress.php';
    }

    $uidMe = (int)($this->userInfo['id']  ?? 0);
    $uidEn = (int)($this->enemyInfo['id'] ?? 0);

    // === ПРОВЕРКА МИРОВОГО БОССА ===
    $isWorldBoss = $this->_isWorldBoss();
    $worldBossInstanceId = (int)($this->battleInfo['world_boss_instance_id'] ?? 0);

    // === Страховка для LOSE_TIMEOUT: если пришёл неверный loser — поправим по факту таймеров
    if ($lose_type === self::LOSE_TIMEOUT && $user_lose_id !== true) {
        $now        = time();
        $myEndTs    = (int)($this->userData['timer']['atk']  ?? 0);
        $enEndTs    = (int)($this->enemyData['timer']['atk'] ?? 0);
        $myExpired  = ($myEndTs > 0 && $now > $myEndTs);
        $enExpired  = ($enEndTs > 0 && $now > $enEndTs);
        if ($myExpired && !$enExpired) {
            $user_lose_id = $uidMe;
        } elseif ($enExpired && !$myExpired) {
            $user_lose_id = $uidEn;
        }
        // если истекли оба или оба 0 — оставляем как есть (дальше обработка ветки LOSE_ALL/прочее)
    }

    // Не выполняем повторно (теперь корректно отмечаем конкретную сторону)
    if (isset($this->userData['lose']) || isset($this->enemyData['lose'])) {
        // Если уже стоит lose у нужной стороны — просто выходим
        if (($uidMe && isset($this->userData['lose'])) || ($uidEn && isset($this->enemyData['lose']))) {
            return;
        }
    }

    $user_lose   = [];
    $user_winner = [];

    // === Командный режим: локальный конец дуэли (гаунтлет) ===
    $other = Info::_unParseData($this->battleInfo['other'] ?? '[]');
    if (is_array($other) && (($other['mode'] ?? '') === 'gauntlet') && $user_lose_id !== true) {
        $losingSide = ($uidMe == (int)$user_lose_id) ? 1 : 2;
        $queue = $other['sides'][$losingSide]['queue_users'] ?? [];
        if (!empty($queue)) {
            if ($this->maybeGauntletHandoff($losingSide)) {
                return; // НЕ финалим бой целиком
            }
        }
    }

    /* ——— Ниже — PvE/PvP/WorldBoss начисления/логика ——— */

    // === KEYS: выставляем флаг lose строго у проигравшей стороны ===
    if ($user_lose_id !== true) {
        if ($uidMe === (int)$user_lose_id) {
            $this->userData['lose'] = $lose_type;
            $this->update['my'] = true;
        } elseif ($uidEn === (int)$user_lose_id) {
            $this->enemyData['lose'] = $lose_type;
            $this->update['enemy'] = true;
        }
    }

    if ($user_lose_id === true && $lose_type != 'CATCH') {
        // Обе стороны потеряли всех покемонов / общий финиш — XP никому (и в PvE, и в PvP).

        // === МИРОВОЙ БОСС: обрабатываем ничью ===
        if ($isWorldBoss && $worldBossInstanceId > 0) {
            $damageDealt = $this->calculateWorldBossDamage();
            $this->handleWorldBossResult('draw', $damageDealt, 0, $worldBossInstanceId);
        }

        // Лог о финале «оба пали»
        $this->appendFinalLogToDbAndMemory(
            ['Обе стороны не могут продолжать бой.'],
            [],
            ['all_fainted' => true]
        );

        // battleEND без конкретного победителя/проигравшего
        $payload = [
            'battleEND' => [
                'title'  => self::LOSE_ALL,
                'winner' => false,
                'loser'  => false,
            ]
        ];
        if (!empty($uidMe)) { $this->generateAnswer($payload, $uidMe); }
        if (!empty($uidEn)) { $this->generateAnswer($payload, $uidEn); }

        $this->resetAction(true);
        return;
    }

    // Определяем winner/loser
    $loserIsMe  = ($uidMe === (int)$user_lose_id);
    $user_lose   = $loserIsMe ? $this->userInfo  : $this->enemyInfo;
    $user_winner = $loserIsMe ? $this->enemyInfo : $this->userInfo;

    // === Ссылка на покемонов победителя (для PvE EXP) ===
    if ($loserIsMe) {
        $winner_pokes =& $this->enemyPokes;
    } else {
        $winner_pokes =& $this->userPokes;
    }

    // Обновление опыта проигравшей стороны (PvP) — ОТКЛЮЧЕНО ПОЛНОСТЬЮ
    if ($lose_type != 'CATCH' && $this->_isPVP() && !empty($user_lose['id'])) {
        // Ничего не делаем по EXP в PvP
    }

    // Победитель определён (PvE/PvP/WorldBoss) — блок PvE наград/дропа
    if (!empty($user_winner['id']) || $lose_type == 'CATCH') {
        if ($lose_type == 'CATCH') {
            $user_winner = $this->userInfo;
        }

        // === МИРОВОЙ БОСС: специальная обработка ===
        if ($isWorldBoss && $worldBossInstanceId > 0) {
            $damageDealt = $this->calculateWorldBossDamage();
            
            if ($loserIsMe) {
                // Игрок проиграл боссу (обычная ситуация)
                $remainingBossHp = max(0, (int)($this->enemyTarget['hp'] ?? 0));
                $this->handleWorldBossResult('lose', $damageDealt, $remainingBossHp, $worldBossInstanceId);
            } else {
                // Игрок победил босса (маловероятно для мирового босса, но возможно)
                $remainingBossHp = 0;
                $this->handleWorldBossResult('win', $damageDealt, $remainingBossHp, $worldBossInstanceId);
            }

            // === МИРОВОЙ БОСС: EXP для финишера (адаптированная логика) ===
            if (!$loserIsMe && isset($winner_pokes) && is_array($winner_pokes)) {
                $enemyLvl   = 100; // Мировой босс всегда 100 уровня
                $effortBase = 50; // Увеличенный effort для мирового босса

                // Определяем финишера (используем ту же логику что и для PvE)
                $finisherKey = $this->determineFinisher($winner_pokes, $last_atk);

                if ($finisherKey !== null && isset($winner_pokes[$finisherKey])) {
                    $poke = $winner_pokes[$finisherKey];
                    $poke['actionCount'] = max(1, (int)($poke['actionCount'] ?? 0));
                    $poke['targetLvl']   = [$enemyLvl];
                    
                    // Увеличенный опыт за мирового босса
                    $poke = Info::_updatePokeExp($poke, $effortBase * 3);
                    $winner_pokes[$finisherKey] = $poke;
                }
            }
        }
        // Обычная PvE логика (НЕ для мировых боссов)
        elseif (!$this->_isPVP() && !$isWorldBoss && isset($this->battleInfo['user_2']) && (int)$this->battleInfo['user_2'] === 0) {
            // === PvE награды (сохранено как у вас; логика не менялась) ===
            $winnerRating = json_decode($user_winner['rating']);
            $winnerRatingUpd = '{"pve": ' . ($winnerRating->pve+1) . ', "pvp": ' . $winnerRating->pvp . ', "battleCount": ' . $winnerRating->battleCount . '}';

            Work::$sql->query("UPDATE `users`
                SET `rating` = '" . $winnerRatingUpd . "', `countKillPok` = `countKillPok` + 1
                WHERE `id` = '" . (int)$user_winner['id'] . "'");

            // 🔥 Квестовый счётчик
            Work::$sql->query("UPDATE `user_quest_info`
                SET `countPok` = `countPok` + 1
                WHERE `userID` = '".(int)$user_winner['id']."' AND `questID` = 1");

            // ===== Quest 131: Янтарное озеро — 10 побед #130 за 30 минут (countPok в user_quest_info) =====
            // ВАЖНО:
            //  - Состояние попытки: user_quests.step = 40 (активна) / 50 (выполнено)
            //  - Таймер окна: bafs.type=6 (приманка), time > now
            //  - Счётчик: user_quest_info.countPok (если строки нет — создаём)
            try {

                $uid = 0;
                if (!empty($_SESSION['id'])) { $uid = (int)$_SESSION['id']; }

                // Определяем локацию максимально надёжно
                $locId = 0;
                if (!empty($this->battleInfo) && !empty($this->battleInfo['location'])) {
                    $locId = (int)$this->battleInfo['location'];
                }
                if ($locId <= 0 && !empty($userData) && !empty($userData['location'])) {
                    $locId = (int)$userData['location'];
                }
                if ($locId <= 0) {
                    $lr = Work::$sql->query("SELECT `location` FROM `users` WHERE `id`=".$uid." LIMIT 1")->fetch_assoc();
                    if (!empty($lr['location'])) $locId = (int)$lr['location'];
                }

                // Определяем basenum врага (в дропе используется basenum)
                $enemyBase = 0;
                if (!empty($this->enemyTarget) && isset($this->enemyTarget['basenum'])) {
                    $enemyBase = (int)$this->enemyTarget['basenum'];
                } elseif (!empty($this->enemyTarget) && isset($this->enemyTarget['num'])) {
                    $enemyBase = (int)$this->enemyTarget['num'];
                }

                // Условия испытания
                if ($uid > 0 && $locId === 48 && $enemyBase === 130) {

                    // Окно приманки
                    $br = Work::$sql->query("SELECT `time` FROM `bafs` WHERE `user`=".$uid." AND `type`=6 ORDER BY `time` DESC LIMIT 1")->fetch_assoc();
                    $baitUntil = !empty($br['time']) ? (int)$br['time'] : 0;

                    // Статус квеста
                    $uq = Work::$sql->query("SELECT `step`,`end` FROM `user_quests` WHERE `user_id`=".$uid." AND `quest_id`=131 LIMIT 1")->fetch_assoc();
                    $qStep = !empty($uq['step']) ? (int)$uq['step'] : 0;
                    $qEnd  = !empty($uq['end']) ? (int)$uq['end'] : 0;

                    if ($qEnd != 1 && $qStep == 40) {

                        // Если окно истекло — сброс (чтобы игрок не фармил после 30 минут)
                        if ($baitUntil > 0 && $baitUntil <= time()) {

                            Work::$sql->query("UPDATE `user_quests` SET `step`=20 WHERE `user_id`=".$uid." AND `quest_id`=131 LIMIT 1");
                            Work::$sql->query("UPDATE `user_quest_info` SET `questStep`=20, `countPok`=0 WHERE `userID`=".$uid." AND `questID`=131 LIMIT 1");
                            // удаляем трофеи водоёма (5046), чтобы не переносились
                            Work::$sql->query("DELETE FROM `items_users` WHERE `user`=".$uid." AND `item_id`=5046");
                            Work::$sql->query("DELETE FROM `items_users` WHERE `user`=".$uid." AND `item`=5046");

                        } else {

                            // Создаём запись счётчика если её нет (у тебя иногда она удаляется системой)
                            $ir = Work::$sql->query("SELECT `id`,`countPok` FROM `user_quest_info` WHERE `userID`=".$uid." AND `questID`=131 LIMIT 1")->fetch_assoc();
                            if (empty($ir['id'])) {
                                Work::$sql->query("INSERT INTO `user_quest_info` (`userID`,`questID`,`questStep`,`countPok`) VALUES (".$uid.",131,40,0)");
                                $count = 0;
                            } else {
                                $count = !empty($ir['countPok']) ? (int)$ir['countPok'] : 0;
                            }

                            if ($count < 10) {

                                // +1 победа
                                Work::$sql->query("UPDATE `user_quest_info` SET `questStep`=40, `countPok`=`countPok`+1 WHERE `userID`=".$uid." AND `questID`=131 LIMIT 1");

                                // выдаём трофей водоёма (5046) как доказательство, максимум 10
                                $tt = 0;
                                $tq = Work::$sql->query("SELECT `count` FROM `items_users` WHERE `user`=".$uid." AND `item_id`=5046 LIMIT 1");
                                if ($tq) { $tr = $tq->fetch_assoc(); if (!empty($tr['count'])) $tt = (int)$tr['count']; }
                                if ($tt <= 0) {
                                    $tq2 = Work::$sql->query("SELECT `count` FROM `items_users` WHERE `user`=".$uid." AND `item`=5046 LIMIT 1");
                                    if ($tq2) { $tr2 = $tq2->fetch_assoc(); if (!empty($tr2['count'])) $tt = (int)$tr2['count']; }
                                }
                                if ($tt < 10) {
                                    itemAdd(5046, 1, $uid);
                                }

                                $cr = Work::$sql->query("SELECT `countPok` FROM `user_quest_info` WHERE `userID`=".$uid." AND `questID`=131 LIMIT 1")->fetch_assoc();
                                $newCount = !empty($cr['countPok']) ? (int)$cr['countPok'] : ($count + 1);

                                // На 10-й победе — фиксируем успех в user_quests (это “истина”, даже если user_quest_info потом удалят)
                                if ($newCount >= 10) {
                                    Work::$sql->query("UPDATE `user_quests` SET `step`=50 WHERE `user_id`=".$uid." AND `quest_id`=131 LIMIT 1");
                                    Work::$sql->query("UPDATE `user_quest_info` SET `questStep`=50, `countPok`=10 WHERE `userID`=".$uid." AND `questID`=131 LIMIT 1");
                                }
                            }
                        }
                    }
                }

            } catch (Exception $e) {
                // молча, чтобы не ломать бой
            }




            // Миссии/уровень/неделя
            if(check_mission(5)){ add_mission(5); }
            if(check_mission_ivent(1)){ add_mission_ivent(1); }
            if(check_mission_ivent(10)){ add_mission_ivent(10); }
            if(check_mission_ivent(25)){ add_mission_ivent(25); }
            lvlupuser(3);
            week_mission('fight');

            // Battle Pass: убийства
            $bpZoneId = 0;
            if (!empty($this->battleInfo['location'])) {
                $bpZoneId = (int)$this->battleInfo['location'];
            } else {
                $locRow = Work::$sql->query("SELECT `location` FROM `users` WHERE `id`=".(int)$user_winner['id']." LIMIT 1")->fetch_assoc();
                if ($locRow) $bpZoneId = (int)$locRow['location'];
            }
            if (function_exists('bp_progress_kill')) {
                bp_progress_kill(Work::$sql, (int)$user_winner['id'], 1, $bpZoneId);
            }

            // ——— Дальше идёт ваш большой блок PvE наград/дропа (без изменений по смыслу) ———
            $userData = Work::$sql->query('SELECT * FROM users WHERE id = ' . (int)$_SESSION['id'])->fetch_assoc();
            $itCave   = Work::$sql->query("SELECT * FROM `ivent_cave` WHERE `user` = " . (int)$_SESSION['id'])->fetch_assoc();

            if ($itCave && $userData['location'] == 88) {
                Work::$sql->query("UPDATE `ivent_cave` SET `l1` = `l1` + 1 WHERE `user` = " . (int)$userData['id']);
            }

            $systemBonuses = Work::$sql->query('SELECT * FROM `system` WHERE id = 1')->fetch_assoc();

            $activeBuffs = [];
            $res = Work::$sql->query('SELECT baf, type, time FROM bafs WHERE user = ' . (int)$_SESSION['id'] . ' AND time > ' . time());
            while($row = $res->fetch_assoc()) {
                $item = Work::$sql->query('SELECT * FROM base_items WHERE id = ' . (int)$row['baf'])->fetch_assoc();
                if ($item) {
                    $activeBuffs[] = [
                        'type'   => $row['type'],
                        'effect' => $item['effect'],
                        'value'  => (float)$item['value'],
                    ];
                }
            }
            $moneyBuff = 1.0;
            foreach ($activeBuffs as $buff) {
                if (($buff['effect'] ?? '') === 'money') {
                    $moneyBuff *= (float)$buff['value'];
                }
            }

            // Premium multiplier: strictly +30% when any premium (type=3) buff is active.
            // IMPORTANT: premium no longer changes the coin cap; it only affects the raw payout.
            $bafPr = 1.0;
            foreach ($activeBuffs as $buff) {
                if ((int)$buff['type'] == 3) {
                    $bafPr = max($bafPr, (float)$buff['value']);
                }
            }
            $premiumMultiplier = ($bafPr > 1.0) ? 1.3 : 1.0;

            // Ring (item 157): deterministic bonus (no RNG).
            $kolco = (isset($this->userTarget['item_id']) && $this->userTarget['item_id'] == 157) ? 60 : 0;

            $lvl = max(1, (int)($this->enemyTarget['lvl'] ?? 1));

            /**
             * Coins formula (deterministic; no per-battle RNG):
             * base(lvl) = 17 * lvl^0.80 + 20 * exp(-lvl/8)
             * raw = base * system.money * moneyBuff * premium(+30% if active)
             *
             * Soft cap (diminishing returns):
             * capMax(lvl)   = 600 + 12*lvl
             * capStart(lvl) = 0.75*capMax
             *
             * payout =
             *   raw, if raw <= capStart
             *   capStart + (capMax-capStart) * (1 - exp(-(raw-capStart)/(0.35*capMax))), otherwise
             */
            $baseMoney = 15 * pow($lvl, 0.80) + 20 * exp(-$lvl / 8);

/**
 * Small, player-friendly variance (no "weird RNG"):
 * - Triangular-ish distribution around 1.00 by averaging two uniform rolls
 * - Default range: ±3% (0.97 .. 1.03), with extremes occurring rarely
 * If you want a different variance, change 97/103 below.
 */
$moneyVariance = (mt_rand(90, 110) + mt_rand(90, 110)) / 200.0;
$baseMoney *= $moneyVariance;


            $rawMoney = $baseMoney;
            $rawMoney *= (float)($systemBonuses['money'] ?? 1.0);
            $rawMoney *= $moneyBuff;
            $rawMoney *= $premiumMultiplier;

            $capMax   = 600 + 12 * $lvl;
            $capStart = 0.75 * $capMax;

            if ($rawMoney <= $capStart) {
                $money = (int)round($rawMoney);
            } else {
                $excess = $rawMoney - $capStart;
                $scale  = max(1.0, 0.35 * $capMax);
                $money  = (int)round($capStart + ($capMax - $capStart) * (1 - exp(-$excess / $scale)));
            }

            $money += $kolco;

            // Boss override stays explicit (kept for existing game design).
            if (in_array(($this->enemyTarget['numb'] ?? 0), [9595, 9596, 9597], true)) {
                $money = 150;
                $bossData = Work::$sql->query(
                    'SELECT * FROM base_boss WHERE location = ' . (int)$userData['location'] .
                    ' AND basenum = ' . (int)($this->enemyTarget['basenum'] ?? 0) .
                    ' AND user = ' . (int)$_SESSION['id']
                )->fetch_assoc();
                if ($bossData) {
                    Work::$sql->query("UPDATE `base_boss` SET `death` = 1 WHERE `id` = " . (int)$bossData['id']);
                }
            }

            // Achievements are intentionally NOT applied to coin payout (per product decision).
            if (check_mission_ivent(17)) {
                add_mission_ivent(17, $money);
            }

            $this->response['logDrop'] = [];

            if (($this->enemyTarget['numb'] ?? 0) != 9495494) {
                $this->response['logDrop'][] = ['name'=>'Генкар','count'=>$money,'id'=>1];
            }

            if (rand(1, 100) <= 30) {
                if (($this->enemyTarget['basenum'] ?? 0) == 486) {
                    $setId = (rand(1, 2) == 2) ? 197 : 198;
                    $setName = ($setId == 197) ? 'Набор тренировки' : 'Набор ослаблений';
                    $this->response['logDrop'][] = ['name'=>$setName,'count'=>1,'id'=>$setId];
                    itemAdd($setId, 1, (int)$_SESSION['id']);
                }
            }

            if (($this->userTarget['ability'] ?? 0) == 130) {
                $randPU = mt_rand(1, 100);
                if ($randPU <= 3) {
                    if (mt_rand(1, 100) <= 60) {
                        $c = mt_rand(1, 100);
                        $this->response['logDrop'][] = ['name'=>'Генкар','count'=>$c,'id'=>1];
                        itemAdd(1, $c, (int)$_SESSION['id']);
                    } else {
                        $map = [
                            1 => ['name'=>'Грейтбол',        'id'=>3],
                            2 => ['name'=>'Желтая конфета',  'id'=>26],
                            3 => ['Суперстимулятор', 'id'=>12],
                        ];
                        $pick = $map[mt_rand(1,3)];
                        $this->response['logDrop'][] = ['name'=>$pick['name'],'count'=>1,'id'=>$pick['id']];
                        itemAdd($pick['id'], 1, (int)$_SESSION['id']);
                    }
                }
            }

            $randStone = rand(1, 8000);
            if ($randStone <= (int)(5 * $bafPr)) {
                $itemType1 = (($this->enemyTarget['base_type'] ?? '') == 'rock' || ($this->enemyTarget['base_type_two'] ?? '') == 'rock') ? 140 : 0;
                if ($itemType1) {
                    $stone = Work::$sql->query('SELECT * FROM base_items WHERE id = '.(int)$itemType1)->fetch_assoc();
                    if ($stone) {
                        $this->response['logDrop'][] = ['name'=>$stone['name'],'count'=>1,'id'=>$itemType1];
                        itemAdd($itemType1, 1, (int)$_SESSION['id']);
                        news_friend(1, (int)$itemType1);
                    }
                }
            }
            $randWater = rand(1, 15000);
            if ($randWater <= (int)(5 * $bafPr)) {
                $itemType1 = (($this->enemyTarget['base_type'] ?? '') == 'water' || ($this->enemyTarget['base_type_two'] ?? '') == 'water') ? 415 : 0;
                if ($itemType1) {
                    $water = Work::$sql->query('SELECT * FROM base_items WHERE id = '.(int)$itemType1)->fetch_assoc();
                    if ($water) {
                        $this->response['logDrop'][] = ['name'=>$water['name'],'count'=>1,'id'=>$itemType1];
                        itemAdd($itemType1, 1, (int)$_SESSION['id']);
                        news_friend(1, (int)$itemType1);
                    }
                }
            }

            // Запись монет
            itemAdd(1, (int)$money, (int)$_SESSION['id']);
            if (function_exists('bp_progress_coins')) {
                bp_progress_coins(Work::$sql, (int)$user_winner['id'], (int)$money);
            }
            update_ach(27, $money);

            // ===== Дроп по правилам (без изменений) =====
            $dropQueryInfo = [];
            $dropQuery = Work::$sql->query('SELECT * FROM `base_drop_pokemons` WHERE `is_active` = 1 AND (`location_id` IN (0, '.(int)$this->userInfo['location'].') OR `region_id` = (SELECT `region` FROM `base_location` WHERE `id` = '.(int)$this->userInfo['location'].' LIMIT 1)) ORDER BY `priority` DESC');
            while ($row = $dropQuery->fetch_assoc()) {
                $dropQueryInfo[] = $row;
            }

            if (!empty($dropQueryInfo)) {
                foreach ($dropQueryInfo as $key => $value) {
                    // Отладка/проверки (оставлено как у тебя)
                    error_log("DROP DEBUG: Processing drop rule ID " . $value['id']);
                    error_log("DROP DEBUG: Current user: " . ($_SESSION['username'] ?? 'Unknown'));
                    error_log("DROP DEBUG: Current location: " . $this->userInfo['location']);
                    error_log("DROP DEBUG: Rule location: " . $value['location_id']);
                    error_log("DROP DEBUG: Rule region: " . $value['region_id']);
                    error_log("DROP DEBUG: Enemy basenum: " . ($this->enemyTarget['basenum'] ?? 'NULL'));
                    error_log("DROP DEBUG: Rule pokemon_num: " . $value['pokemon_num']);
                    error_log("DROP DEBUG: Rule pokemon_type_id: " . ($value['pokemon_type_id'] ?? 'NULL'));
                    error_log("DROP DEBUG: Quest ID: " . $value['quest_id'] . ", Quest Progress: " . $value['quest_progress']);
                    error_log("DROP DEBUG: Item ID: " . $value['item_id'] . ", Drop chance: " . $value['drop_chance']);
                    
                    $bafprem = Work::$sql->query('SELECT * FROM bafs WHERE type = 3 AND user = '.(int)$_SESSION['id'])->fetch_assoc();
                    $bafpr = ($bafprem && $bafprem['time'] > time()) ? 2 : 1;

                    if (!isset($value['drop_chance'], $value['item_id'])) {
                        error_log("DROP DEBUG: Missing drop_chance or item_id");
                        continue;
                    }
                    
                    // ===== НОВАЯ ЛОГИКА ПРОВЕРКИ КВЕСТА =====
                    if ($value['quest_id'] != 0) {
                        if ($value['quest_progress'] == 0) {
                            $userQuest = Work::$sql->query('SELECT * FROM `user_quests` WHERE `user_id` = '.(int)$_SESSION['id'].' AND `quest_id` = '.(int)$value['quest_id'].' AND `step` > 0')->fetch_assoc();
                            
                            if (!$userQuest) {
                                error_log("DROP DEBUG: Quest not active or not started. Quest ID: " . $value['quest_id']);
                                continue;
                            }
                            
                            error_log("DROP DEBUG: ✅ Quest check passed (any step allowed). Quest ID: " . $value['quest_id'] . ", Current step: " . $userQuest['step']);
                        } else {
                            if (!function_exists('quest_step') || !quest_step($value['quest_id'], $value['quest_progress'])) {
                                error_log("DROP DEBUG: Quest step check failed. Quest ID: " . $value['quest_id'] . ", Required step: " . $value['quest_progress']);
                                continue;
                            }
                            
                            error_log("DROP DEBUG: ✅ Quest check passed (specific step). Quest ID: " . $value['quest_id'] . ", Required step: " . $value['quest_progress']);
                        }
                    }
                    
                    // Проверка конкретного покемона по номеру (basenum)
                    if ($value['pokemon_num'] > 0 && $value['pokemon_num'] != ($this->enemyTarget['basenum'] ?? 0)) {
                        error_log("DROP DEBUG: Pokemon basenum mismatch. Expected: " . $value['pokemon_num'] . ", Got: " . ($this->enemyTarget['basenum'] ?? 'NULL'));
                        continue;
                    }
                    
                    // Проверка по типу покемона
                    if (!empty($value['pokemon_type_id'])) {
                        $enemyType1 = $this->enemyTarget['type'] ?? '';
                        $enemyType2 = $this->enemyTarget['type_two'] ?? '';
                        $requiredType = $value['pokemon_type_id'];
                        
                        if ($requiredType && $enemyType1 !== $requiredType && $enemyType2 !== $requiredType) {
                            error_log("DROP DEBUG: Pokemon type mismatch. Required: $requiredType, Got: $enemyType1/$enemyType2");
                            continue;
                        }
                        
                        error_log("DROP DEBUG: ✅ Pokemon type check passed. Required: $requiredType, Got: $enemyType1/$enemyType2");
                    }
                    
                    // Базовые проверки
                    if ($value['item_id'] <= 0 || $value['drop_chance'] <= 0) {
                        error_log("DROP DEBUG: Invalid item_id or drop_chance. Item: " . $value['item_id'] . ", Chance: " . $value['drop_chance']);
                        continue;
                    }

                    // ===== ПРОВЕРКА ЛИМИТА ПРЕДМЕТОВ =====
                    if ($value['limit_item_id'] > 0 && $value['limit_count'] > 0) {
                        $currentItemCount = Work::$sql->query('SELECT SUM(`count`) as total FROM `items_users` WHERE `user` = '.(int)$_SESSION['id'].' AND `item_id` = '.(int)$value['limit_item_id']);
                        
                        if ($currentItemCount) {
                            $currentCount = (int)($currentItemCount->fetch_assoc()['total'] ?? 0);
                            
                            if ($currentCount >= (int)$value['limit_count']) {
                                error_log("DROP DEBUG: Item limit reached. Current: $currentCount, Max: " . $value['limit_count'] . " for item: " . $value['limit_item_id']);
                                continue;
                            } else {
                                error_log("DROP DEBUG: ✅ Limit check passed. Current: " . $currentCount . ", Max: " . $value['limit_count'] . " for item: " . $value['limit_item_id']);
                            }
                        } else {
                            error_log("DROP DEBUG: ⚠️ Warning: Could not check item limit for item: " . $value['limit_item_id']);
                        }
                    }

                    error_log("DROP DEBUG: ✅ All checks passed, rolling for drop...");

                    // ===== ЛОГИКА ШАНСА =====
                    $baseChance = (int)$value['drop_chance'];
                    $systemBonus = $systemBonuses['drop'] ?? 1;
                    
                    $effectiveChance = $baseChance;
                    if ($systemBonus > 1) {
                        $effectiveChance = max(1, (int)($baseChance / $systemBonus));
                    }
                    if ($bafpr > 1) {
                        $effectiveChance = max(1, (int)($effectiveChance / $bafpr));
                    }
                    
                    if ($effectiveChance <= 1) {
                        $dropSuccess = true;
                        error_log("DROP DEBUG: Guaranteed drop (chance: $effectiveChance)");
                    } else {
                        $randomRoll = mt_rand(1, $effectiveChance);
                        $dropSuccess = ($randomRoll === 1);
                        $chancePercent = round((1 / $effectiveChance) * 100, 4);
                        
                        error_log("DROP DEBUG: Random roll: $randomRoll out of $effectiveChance (chance: {$chancePercent}%)");
                        error_log("DROP DEBUG: Base chance: 1/$baseChance, System bonus: x$systemBonus, Baf bonus: x$bafpr");
                        error_log("DROP DEBUG: Effective chance: 1/$effectiveChance");
                    }
                    
                    if (!$dropSuccess) {
                        error_log("DROP DEBUG: Drop roll failed - no luck this time!");
                        continue;
                    }

                    error_log("DROP DEBUG: 🎉 DROP SUCCESSFUL! Item ID: " . $value['item_id']);

                    // Определяем количество предметов
                    $itemCount = 1;
                    if (!empty($value['item_count'])) {
                        if (is_string($value['item_count']) && strpos($value['item_count'], ',') !== false) {
                            $countRange = explode(',', $value['item_count']);
                            if (isset($countRange[1]) && (int)$countRange[1] > (int)$countRange[0]) {
                                $itemCount = mt_rand((int)$countRange[0], (int)$countRange[1]);
                                error_log("DROP DEBUG: Random item count: $itemCount (range: {$countRange[0]}-{$countRange[1]})");
                            } else {
                                $itemCount = (int)$countRange[0];
                            }
                        } else {
                            $itemCount = (int)$value['item_count'];
                        }
                    }
                    
                    error_log("DROP DEBUG: Item count before final limit check: $itemCount");
                    
                    // ===== ФИНАЛЬНАЯ ПРОВЕРКА ЛИМИТА ПЕРЕД ДОБАВЛЕНИЕМ =====
                    if ($value['limit_item_id'] > 0 && $value['limit_count'] > 0) {
                        $finalItemCount = Work::$sql->query('SELECT SUM(`count`) as total FROM `items_users` WHERE `user` = '.(int)$_SESSION['id'].' AND `item_id` = '.(int)$value['limit_item_id']);
                        
                        if ($finalItemCount) {
                            $currentCount = (int)($finalItemCount->fetch_assoc()['total'] ?? 0);
                            $maxCanGet = (int)$value['limit_count'] - $currentCount;
                            $itemCount = min($itemCount, $maxCanGet);
                            
                            if ($itemCount <= 0) {
                                error_log("DROP DEBUG: Item count reduced to 0 due to limit. Current: $currentCount, Max: " . $value['limit_count']);
                                continue;
                            }
                            
                            error_log("DROP DEBUG: Item count after final limit check: $itemCount (limit: {$value['limit_count']}, current: $currentCount)");
                        }
                    }
                    
                    if ($itemCount > 0) {
                        $itemInfo = Work::$sql->query('SELECT `name`,`id`,`news` FROM `base_items` WHERE `id` = "'.(int)$value['item_id'].'"')->fetch_assoc();
                        if (!empty($itemInfo)) {
                            error_log("DROP DEBUG: 🎁 Adding item to inventory: " . $itemInfo['name'] . " x" . $itemCount);
                            
                            // Логируем дроп
                            $this->response['logDrop'][] = [
                                'name' => $itemInfo['name'],
                                'count' => $itemCount,
                                'id' => $itemInfo['id'],
                                'chance' => '1/'.$baseChance
                            ];
                            
                            // Добавляем предмет в инвентарь
                            if (function_exists('itemAdd')) {
                                itemAdd((int)$value['item_id'], $itemCount, (int)$_SESSION['id']);
                            } else {
                                error_log("DROP DEBUG: ⚠️ Warning: itemAdd function not found!");
                            }
                            
                            // Новости для друзей (если включено)
                            if ((int)$itemInfo['news'] != 1) {
                                if (function_exists('news_friend')) {
                                    news_friend(1, (int)$value['item_id']);
                                }
                                
                                $user = Work::$sql->query("SELECT * FROM users WHERE id = ".(int)$_SESSION['id'])->fetch_assoc();
                                if ($user) {
                                    $catch = ($user['sex'] == "m" ? "выбил" : "выбила");
                                    
                                    // Добавляем информацию о лимите если есть
                                    $limitInfo = '';
                                    if ($value['limit_item_id'] > 0 && $value['limit_count'] > 0) {
                                        $limitItemCount = Work::$sql->query('SELECT SUM(`count`) as total FROM `items_users` WHERE `user` = '.(int)$_SESSION['id'].' AND `item_id` = '.(int)$value['limit_item_id']);
                                        if ($limitItemCount) {
                                            $currentCount = (int)($limitItemCount->fetch_assoc()['total'] ?? 0);
                                            $remaining = (int)$value['limit_count'] - $currentCount;
                                            $limitInfo = $remaining > 0 ? " (осталось получить: $remaining)" : " (лимит достигнут)";
                                        }
                                    }
                                    
                                    $text = Work::$sql->real_escape_string('<div class="user-link"><div onclick=showUserTooltip('.(int)$user['id'].') class="Info-Link sex'.$user['sex'].'"><i class="fa fa-info"></i></div> <div class="u-'.$user['user_group'].' label" onclick=user_to_chat_add('.(int)$user['id'].')>'.$user['login'].'</div></div> <span>'.$catch.' '.$itemInfo['name'].$limitInfo.'</span>');
                                    Work::$sql->query("INSERT INTO friends_news (user,text,date) VALUES (".(int)$_SESSION['id'].",'".$text."',".time().")");
                                }
                            }
                        } else {
                            error_log("DROP DEBUG: ❌ Item not found in base_items: " . $value['item_id']);
                        }
                    } else {
                        error_log("DROP DEBUG: ❌ Item count is 0 after limit check");
                    }
                }
                unset($key, $value);
            }

            // === КВЕСТ ЮНЫ (ID 121): автозавершение при победе в её испытании ===
            if (!$loserIsMe && $this->isYunaQuestBattle()) {
                $this->completeYunaQuestIfNeeded((int)$user_winner['id']);
            }

            // === PvE EXP — ТОЛЬКО ОДНОМУ ПОКЕМОНУ-ПОБЕДИТЕЛЮ (ФИНИШЕРУ) ===
            if ($lose_type != 'CATCH' && isset($winner_pokes) && is_array($winner_pokes)) {
                $enemyLvl   = max(1, (int)($this->enemyTarget['lvl'] ?? 1));
                $effortBase = (int)($this->enemyTarget['base_effort'] ?? 1);

                // Определяем финишера
                $finisherKey = $this->determineFinisher($winner_pokes, $last_atk);

                // Обнуляем actionCount/targetLvl у всех КРОМЕ финишера
                if ($finisherKey !== null) {
                    foreach ($winner_pokes as $k => &$p) {
                        if ($k === $finisherKey) continue;
                        if (isset($p['actionCount'])) $p['actionCount'] = 0;
                        if (isset($p['targetLvl']))   unset($p['targetLvl']);
                    } unset($p);
                }

                // Начисляем опыт только финишеру
                if ($finisherKey !== null && isset($winner_pokes[$finisherKey]) && is_array($winner_pokes[$finisherKey])) {
                    $poke = $winner_pokes[$finisherKey];

                    // Гарантируем триггеры для _updatePokeExp()
                    $poke['actionCount'] = max(1, (int)($poke['actionCount'] ?? 0));
                    $poke['targetLvl']   = [$enemyLvl]; // чтобы enemy_lvl > 0 в _updatePokeExp

                    // Передаём базовый effort врага (как было в вашем общем начислении)
                    $poke = Info::_updatePokeExp($poke, $effortBase);

                    // Сохраняем обратно
                    $winner_pokes[$finisherKey] = $poke;
                }
            }
        }
    }

    // Если есть следующий участник — дуэль локально завершена (без battle_end)
    if ($user_lose_id !== true && $this->maybeGauntletHandoffByUserId((int)$user_lose_id)) {
        return;
    }

    // ====== PvP ветка рейтингов / кланов ======
    if($this->_isPVP() && !empty($user_lose['id']) && !empty($user_winner['id'])){
        Work::$sql->query('INSERT INTO battle_end (user1,user2,win,battle) VALUES ('.(int)$user_lose['id'].','.(int)$user_winner['id'].','.(int)$user_winner['id'].','.(int)$this->battleInfo['id'].')');
        Work::$sql->query("UPDATE `users` SET `pvp` = `pvp` + 1, `battleCount` = `battleCount` + 1 WHERE `id` = '".(int)$user_winner['id']."'");
        Work::$sql->query("UPDATE `users` SET `battleCount` = `battleCount` + 1 WHERE `id` = '".(int)$user_lose['id']."'");

        lvlupuser(25,$user_winner['id']);
        if(check_mission(13,$user_winner['id'])){ add_mission(13,false,$user_winner['id']);}
        if(check_mission(13,$user_lose['id'])){ add_mission(13,false,$user_lose['id']);}

        $user_clan_win  = Work::$sql->query("SELECT * FROM base_clans_users WHERE user_id = ".(int)$user_winner['id'])->fetch_assoc();
        $user_clan_lose = Work::$sql->query("SELECT * FROM base_clans_users WHERE user_id = ".(int)$user_lose['id'])->fetch_assoc();

        if(!$this->_isARENA()){
            if($user_clan_win && $user_clan_lose){
                if((int)$user_clan_win['clan_id'] !== (int)$user_clan_lose['clan_id']){
                    Work::$sql->query("UPDATE `base_clans_users` SET `raiting` = `raiting` + 1 WHERE `user_id` = '".(int)$user_winner['id']."'");
                    Work::$sql->query("UPDATE `base_clans_users` SET `raiting` = `raiting` - 1 WHERE `user_id` = '".(int)$user_lose['id']."'");
                    Work::$sql->query("UPDATE `base_clans` SET `rating` = `rating` + 1 WHERE `id` = '".(int)$user_clan_win['clan_id']."'");
                    Work::$sql->query("UPDATE `base_clans` SET `rating` = `rating` - 1 WHERE `id` = '".(int)$user_clan_lose['clan_id']."'");
                }
            }
        } else {
            minus_item(480, 1, (int)$user_lose['id']);
            itemAdd(480, 1, (int)$user_winner['id']);
        }
    } else {
        // В ПВЕ общий цикл EXP для всей команды — УДАЛЁН.
        // EXP уже начислили только финишеру выше.
    }

    // Финальный лог: таймаут/сдача/прочее — запишем корректно и в БД и в память
    $finalStatus = [];
    $loserId  = (int)($user_lose['id']   ?? 0);
    $winnerId = (int)($user_winner['id'] ?? 0);

    if ($lose_type === self::LOSE_TIMEOUT) {
        $this->appendFinalLogToDbAndMemory(
            ['Время на ход истекло. Вы проиграли по таймеру.'],                // Лог для проигравшего
            ['Соперник не сделал ход вовремя. Вы победили по таймеру.'],       // Лог для победителя
            ['timeout' => true, 'timeout_loser' => $loserId]
        );
    } elseif ($lose_type === self::LOSE_COWARD) {
        $this->appendFinalLogToDbAndMemory(
            ['Вы сдались. Поражение.'],
            ['Соперник сдался. Победа!'],
            ['coward' => true]
        );
    } elseif ($lose_type === self::LOSE_NO_HP) {
        if ($isWorldBoss) {
            $this->appendFinalLogToDbAndMemory(
                ['Команда не может продолжать бой с мировым боссом. Вы нанесли урон и получили опыт.'],
                ['Мировой босс выстоял против вашей атаки.'],
                ['world_boss_survived' => true]
            );
        } else {
            $this->appendFinalLogToDbAndMemory(
                ['Команда не может продолжать бой. Поражение.'],
                ['Соперник не может продолжать бой. Победа!'],
                ['no_hp' => true]
            );
        }
    } else {
        // Прочие случаи — нейтральный финальный блок
        $this->appendFinalLogToDbAndMemory(
            ['Бой завершён.'],
            ['Бой завершён.'],
            ['end' => true]
        );
    }

    // battleEND → answer ОБЕИМ сторонам
    $payload = [
        'battleEND'=>[
            'title'=>$lose_type,
            'winner'=>$winnerId ?: false,
            'loser' =>$loserId  ?: false,
        ]
    ];
    if (!empty($uidMe)) { $this->generateAnswer($payload, $uidMe); }
    if (!empty($uidEn)) { $this->generateAnswer($payload, $uidEn); }

    // Финальная очистка (двухфазная в PvP)
    $this->resetAction(true);
}

/**
 * Проверяет, является ли текущий бой боем с мировым боссом
 */
private function _isWorldBoss(): bool {
    return (isset($this->battleInfo['type']) && $this->battleInfo['type'] === 'world_boss') ||
           (isset($this->battleInfo['world_boss_instance_id']) && (int)$this->battleInfo['world_boss_instance_id'] > 0);
}

/**
 * === QUEST 121 (Юна): определение боя-испытания Юны ===
 * Приоритет — метка в `battle.other` (quest_id=121 / yuna_battle=1).
 * Фолбэк — состав команды: Pidgeot(18), Jolteon(135), Victreebel(71), Kingler(99), тип боя 'npc'.
 */
private function isYunaQuestBattle(): bool {
    // Явная метка в other
    $other = Info::_unParseData($this->battleInfo['other'] ?? '[]');
    if (is_array($other)) {
        if ((int)($other['quest_id'] ?? 0) === 121 || !empty($other['yuna_battle']) || !empty($other['q121'])) {
            return true;
        }
    }

    // Тип боя/соперник
    $type = $this->battleInfo['type'] ?? '';
    $u2   = (int)($this->battleInfo['user_2'] ?? 0);

    // Парсим состав противника
    $enemyInfo = Info::_unParseData($this->battleInfo['info_2'] ?? '[]');
    $list = $enemyInfo['pokeLIst'] ?? ($enemyInfo['pokeList'] ?? []);
    if (!is_array($list) || empty($list)) return false;

    $need = [18,135,71,99]; // Pidgeot, Jolteon, Victreebel, Kingler
    $have = [];
    foreach ($list as $p) {
        if (isset($p['basenum'])) {
            $bn = (int)$p['basenum'];
            if (in_array($bn, $need, true)) $have[$bn] = true;
        }
    }
    $okTeam = (count($have) >= 4);

    // Требуем либо явную метку, либо узнаваемую команду + npc-бой
    return $okTeam && ($type === 'npc' || $u2 === 0);
}

/**
 * === QUEST 121 (Юна): завершение квеста и выдача награды при победе ===
 * Вызывается только при победе игрока в бою Юны.
 */
private function completeYunaQuestIfNeeded(int $winnerUserId): void {
    if ($winnerUserId <= 0) return;

    $q = Work::$sql->query("SELECT `id`,`step`,`end`,`data` FROM `user_quests` WHERE `user_id`={$winnerUserId} AND `quest_id`=121 LIMIT 1");
    $row = $q ? $q->fetch_assoc() : null;
    if (!$row) return;

    $step = (int)($row['step'] ?? 0);
    $end  = (int)($row['end']  ?? 0);
    if ($end === 1 || $step < 6) return; // либо уже закрыт, либо ещё рано

    $data  = json_decode($row['data'] ?? '[]', true) ?: [];
    $bonus = !empty($data['bonus']);

    // Награда квеста (как в yuna.php)
    if (function_exists('itemAdd')) {
        itemAdd(1, 120000, $winnerUserId);   // Генкары
        itemAdd(193, 1, $winnerUserId);      // Коробка витаминов
        itemAdd(26, 2, $winnerUserId);       // Жёлтая конфета
        itemAdd(3, 20, $winnerUserId);       // Грейтбол
        if ($bonus) {
            itemAdd(196, 1, $winnerUserId);  // Именной бланк (бонус)
        }
    }

    // Фиксируем завершение
    $json = Work::$sql->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    Work::$sql->query("UPDATE `user_quests` SET `step`=7,`end`=1,`data`='{$json}' WHERE `user_id`={$winnerUserId} AND `quest_id`=121 LIMIT 1");

    // Логи квеста (если доступны)
    if (function_exists('quest_update')) quest_update(121, 7);
    $txt = 'Победа. Тайник Райдена вскрыт, награда выдана.' . ($bonus ? ' Бонус: Именной бланк ×1.' : '');
    if (function_exists('update_zap')) update_zap(121, 7, $txt);
    if (function_exists('quest_zap'))  quest_zap(121, 7, $txt);
}

/**
 * Вычисляет урон, нанесенный мировому боссу
 */
private function calculateWorldBossDamage(): int {
    $damage = 0;
    
    // Получаем изначальное HP босса из info_2
    $enemyInfo = Info::_unParseData($this->battleInfo['info_2'] ?? '[]');
    if (isset($enemyInfo['pokeLIst']) && is_array($enemyInfo['pokeLIst'])) {
        foreach ($enemyInfo['pokeLIst'] as $pokemon) {
            if (isset($pokemon['hp_max'], $pokemon['hp'])) {
                $damage = (int)$pokemon['hp_max'] - (int)$pokemon['hp'];
                break; // У мирового босса один покемон
            }
        }
    }
    
    return max(0, $damage);
}

/**
 * Обрабатывает результат боя с мировым боссом
 */
private function handleWorldBossResult(string $result, int $damageDealt, int $finalBossHp, int $instanceId): void {
    if ($instanceId <= 0) return;
    
    $userId = (int)($this->userInfo['id'] ?? 0);
    if ($userId <= 0) return;
    
    try {
        // Отправляем результат в WorldBossTest.php
        $postData = [
            'action' => 'battle_result',
            'battle_id' => (int)($this->battleInfo['id'] ?? 0),
            'result' => $result,
            'damage_dealt' => $damageDealt,
            'final_boss_hp' => $finalBossHp,
            'user_id' => $userId,
            'instance_id' => $instanceId
        ];
        
        // Вызываем обработку через include (локально)
        $originalPost = $_POST;
        $_POST = $postData;
        
        ob_start();
        @include $_SERVER['DOCUMENT_ROOT'] . '/do/WorldBossTest.php';
        $response = ob_get_clean();
        
        $_POST = $originalPost;
        
        // Логируем результат
        error_log("World Boss battle result: " . json_encode([
            'user_id' => $userId,
            'instance_id' => $instanceId,
            'result' => $result,
            'damage' => $damageDealt,
            'boss_hp' => $finalBossHp
        ]));
        
    } catch (Exception $e) {
        error_log("Error handling world boss result: " . $e->getMessage());
    }
}

/**
 * Определяет финишера (покемона, который нанёс последний удар)
 */
private function determineFinisher(array $winner_pokes, $last_atk) {
    $finisherKey = null;

    // (A) Пробуем извлечь из $last_atk ВСЕ популярные варианты формата
    if ($last_atk !== false && $last_atk !== null) {
        // Вариант: число -> сначала как poke-id
        if (is_numeric($last_atk)) {
            $asInt = (int)$last_atk;
            foreach ($winner_pokes as $k => $p) {
                if ((int)($p['id'] ?? 0) === $asInt) { $finisherKey = $k; break; }
            }
            // если не нашёлся id — трактуем как слот
            if ($finisherKey === null) {
                $cand = 'p'.$asInt;
                if (isset($winner_pokes[$cand])) { $finisherKey = $cand; }
            }
        }
        // Вариант: строка вроде 'p3'/'P3'
        if ($finisherKey === null && is_string($last_atk)) {
            $trim = trim($last_atk);
            if (preg_match('~^[pP]\d+$~', $trim)) {
                $cand = 'p'.(int)substr($trim,1);
                if (isset($winner_pokes[$cand])) { $finisherKey = $cand; }
            } elseif (ctype_digit($trim)) {
                $cand = 'p'.(int)$trim;
                if (isset($winner_pokes[$cand])) { $finisherKey = $cand; }
            }
        }
        // Вариант: массив с ключами id/slot/key
        if ($finisherKey === null && is_array($last_atk)) {
            $idKeys   = ['poke_id','id','pid'];
            $slotKeys = ['slot','p','index','key'];
            $foundId  = null; $foundSlot = null;

            foreach ($idKeys as $ik) {
                if (isset($last_atk[$ik]) && is_numeric($last_atk[$ik])) { $foundId = (int)$last_atk[$ik]; break; }
            }
            if ($foundId !== null) {
                foreach ($winner_pokes as $k => $p) {
                    if ((int)($p['id'] ?? 0) === $foundId) { $finisherKey = $k; break; }
                }
            }
            if ($finisherKey === null) {
                foreach ($slotKeys as $sk) {
                    if (isset($last_atk[$sk])) {
                        $val = $last_atk[$sk];
                        if (is_string($val) && preg_match('~^[pP]\d+$~', $val)) {
                            $cand = 'p'.(int)substr($val,1);
                            if (isset($winner_pokes[$cand])) { $finisherKey = $cand; }
                            break;
                        } elseif (is_numeric($val)) {
                            $cand = 'p'.(int)$val;
                            if (isset($winner_pokes[$cand])) { $finisherKey = $cand; }
                            break;
                        }
                    }
                }
            }
        }
    }

    // (B) Если не нашли — пытаемся по активному
    if ($finisherKey === null) {
        $activeKeys = [];
        foreach ($winner_pokes as $k => $p) {
            if (!empty($p['active'])) { $activeKeys[] = $k; }
        }
        if (count($activeKeys) === 1) {
            $finisherKey = $activeKeys[0];
        }
    }

    // (C) Если несколько активных или ни одного — берём по максимальному «последнему действию»
    if ($finisherKey === null) {
        $tsFields = ['lastAtkTs','last_action_ts','lastMove','ts','updated_at','lastAtk','last_atk'];
        $bestK = null; $bestTs = -1;
        foreach ($winner_pokes as $k => $p) {
            $ts = -1;
            foreach ($tsFields as $f) {
                if (isset($p[$f]) && is_numeric($p[$f])) {
                    $ts = max($ts, (int)$p[$f]);
                }
            }
            if ((int)($p['hp'] ?? 0) > 0 && $ts >= 0) {
                if ($ts > $bestTs) { $bestTs = $ts; $bestK = $k; }
            }
        }
        if ($bestK !== null) { $finisherKey = $bestK; }
    }

    // (D) Если всё ещё нет — берём с наибольшим actionCount > 0
    if ($finisherKey === null) {
        $bestK = null; $bestAC = -1;
        foreach ($winner_pokes as $k => $p) {
            $ac = (int)($p['actionCount'] ?? 0);
            if ((int)($p['hp'] ?? 0) > 0 && $ac > 0) {
                if ($ac > $bestAC) { $bestAC = $ac; $bestK = $k; }
            }
        }
        if ($bestK !== null) { $finisherKey = $bestK; }
    }

    // (E) Если нет — берём по нанесённому урону (если поля есть)
    if ($finisherKey === null) {
        $dmgFields = ['damageDealt','dmg','damage'];
        $bestK = null; $bestD = -1;
        foreach ($winner_pokes as $k => $p) {
            $d = -1;
            foreach ($dmgFields as $f) {
                if (isset($p[$f]) && is_numeric($p[$f])) {
                    $d = max($d, (int)$p[$f]);
                }
            }
            if ((int)($p['hp'] ?? 0) > 0 && $d >= 0) {
                if ($d > $bestD) { $bestD = $d; $bestK = $k; }
            }
        }
        if ($bestK !== null) { $finisherKey = $bestK; }
    }

    // (F) Если нет — берём самого высокого по уровню (живого)
    if ($finisherKey === null) {
        $bestK = null; $bestLvl = -1;
        foreach ($winner_pokes as $k => $p) {
            $lv = (int)($p['lvl'] ?? 0);
            if ((int)($p['hp'] ?? 0) > 0) {
                if ($lv > $bestLvl) { $bestLvl = $lv; $bestK = $k; }
            }
        }
        if ($bestK !== null) { $finisherKey = $bestK; }
    }

    // (G) Крайний случай: берём последний живой слот
    if ($finisherKey === null) {
        $alive = [];
        foreach ($winner_pokes as $k => $p) {
            if ((int)($p['hp'] ?? 0) > 0) { $alive[] = $k; }
        }
        if (!empty($alive)) {
            $finisherKey = end($alive);
        }
    }

    return $finisherKey;
}

/**
 * Записать финальный шаг лога и в память, и в БД (для корректного HTML-журнала).
 * $logLoser/$logWinner — массивы строк, которые покажем у соответствующих пользователей.
 * $status — массив статусов для поля `end` в battle_log.
 */
private function appendFinalLogToDbAndMemory(array $logLoser, array $logWinner, array $status = []): void {
    $battle_id = (int)($this->battleInfo['id'] ?? 0);
    $round     = (int)($this->battleInfo['round'] ?? 0);
    $loserId   = (int)($this->userData['lose'] ? ($this->userInfo['id'] ?? 0) : ($this->enemyInfo['id'] ?? 0)); // не используем напрямую
    $uidMe     = (int)($this->userInfo['id']  ?? 0);
    $uidEn     = (int)($this->enemyInfo['id'] ?? 0);

    // Для фронтового JSON-лога (response['log']) — один суммарный блок
    $summary = [];
    if (!empty($logLoser))  { $summary[] = implode(' ', $logLoser); }
    if (!empty($logWinner)) { $summary[] = implode(' ', $logWinner); }

    $this->log[] = [
        'round'      => $round,
        'log'        => $summary,
        'log_status' => $status
    ];

    // Для HTML-журнала — пишем запись в battle_log с раздельными логами
    if ($battle_id > 0) {
        $rows = [];
        if ($uidMe > 0) {
            $rows[] = ['user' => $uidMe, 'log' => ($uidMe === $loserId ? $logLoser : $logWinner)];
        }
        if ($uidEn > 0) {
            $rows[] = ['user' => $uidEn, 'log' => ($uidEn === $loserId ? $logLoser : $logWinner)];
        }
        // Если одна из сторон — дикий покемон (id=0), оставим второй блок пустым, чтобы рендер не падал
        if ($uidMe === 0 || $uidEn === 0) {
            $rows[] = ['user' => 0, 'log' => []];
        }

        $LogGame  = json_encode($rows,   JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        $LogGame1 = json_encode($status, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);

        $sql = "INSERT INTO battle_log (battle, round, text, end, user, starter)
                VALUES (".$battle_id.", ".$round.", '".Work::$sql->real_escape_string($LogGame)."', '".Work::$sql->real_escape_string($LogGame1)."', ".($uidMe ?: $uidEn).", 0)";
        Work::$sql->query($sql);
    }
}

/* ——— Флаги боя ——— */
public function _isPVP() {
    if(isset($this->battleType) && strtolower($this->battleType) === 'pvp'){
        return true;
    }
    // Фолбэк: если бой создавался по старой схеме без корректного type, определяем PvP по участникам
    if(!empty($this->battleInfo) && is_array($this->battleInfo) && $this->_isPVPRow($this->battleInfo)){
        return true;
    }
    return false;
}
private function _isPVPRow(array $row): bool {
    return (isset($row['type']) && $row['type'] === 'pvp') ||
           (isset($row['user_1'], $row['user_2']) && $row['user_1'] > 0 && $row['user_2'] > 0);
}
public function _isARENA() {
    return (isset($this->battleArena) && intval($this->battleArena) === 1);
}
public function _isRetarget($pokeList) {
    if (is_array($pokeList) && count($pokeList) > 0) {
        foreach ($pokeList as $value) {
            if (is_array($value) && isset($value['hp']) && $value['hp'] > 0) {
                return true;
            }
        }
    }
    return false;
}

/* ——— Геттеры/Сеттеры для Battle ——— */
public function &_getUserData($userID) {
    if (isset($this->userInfo['id']) && $this->userInfo['id'] == $userID) {
        return $this->userData;
    } else {
        return $this->enemyData;
    }
}
public function &_getUserInfo($userID) {
    if (isset($this->userInfo['id']) && $this->userInfo['id'] == $userID) {
        return $this->userInfo;
    } else {
        return $this->enemyInfo;
    }
}
public function _getUserPokes($userID, $pokes = false) {
    $list = ($this->userInfo['id'] == $userID) ? $this->userPokes : $this->enemyPokes;
    if ($pokes === false) {
        return $list;
    } else {
        $pokeKey = 'p' . intval($pokes);
        return (isset($list[$pokeKey]) ? $list[$pokeKey] : []);
    }
}
public function _setUserPokes($userID, $pokes, $info = []) {
    if (isset($this->userInfo['id']) && $this->userInfo['id'] == $userID) {
        if (is_array($pokes)) {
            $this->userPokes = $pokes;
        } elseif (!empty($info) && isset($info['id'])) {
            $this->userPokes['p' . intval($pokes)] = $info;
        }
    } else {
        if (is_array($pokes)) {
            $this->enemyPokes = $pokes;
        } elseif (!empty($info) && isset($info['id'])) {
            $this->enemyPokes['p' . intval($pokes)] = $info;
        }
    }
}
public function _setTarget($userID, $target) {
    if (!empty($target) && is_array($target) && isset($target['id'])) {
        if (isset($this->userInfo['id']) && $this->userInfo['id'] == $userID) {
            if (isset($this->userData['target'])) {
                $this->userData['prevTarget'] = $this->userData['target'];
            }
            $this->userData['target'] = intval($target['id']);
            $this->userTarget = $target;
        } else {
            if (isset($this->enemyData['target'])) {
                $this->enemyData['prevTarget'] = $this->enemyData['target'];
            }
            $this->enemyData['target'] = intval($target['id']);
            $this->enemyTarget = $target;
        }
    }
}

/* ——— Прочее ——— */
public function _nextRound() {
    $this->round += 1;
}
public function _nextRoundWeather() {
    if ($this->weather != 1) {
        $b = $this->weather_round - 1;
        if ($b == 0) {
            $this->weather = 1;
            Work::$sql->query('UPDATE `battle` SET `weather` = 1 WHERE `id` = ' . intval($this->battleInfo['id']));
        }
        Work::$sql->query('UPDATE `battle` SET `weather_round` = ' . $b . ' WHERE `id` = ' . intval($this->battleInfo['id']));
        $this->weather_round = $b;
    }
}

private function resetAction($battleEnd = false): void {
    $meId     = isset($this->userInfo['id'])   ? (int)$this->userInfo['id']   : 0;
    $battleId = isset($this->battleInfo['id']) ? (int)$this->battleInfo['id'] : 0;

    // Освобождаем игрока
    if ($meId > 0) {
        Work::$sql->query("UPDATE `users` SET `status`='free', `status_id`=0 WHERE `id`=".$meId);
        $this->clearBattleEffects($meId);
    }
    if (!$battleEnd || $battleId <= 0) {
        return;
    }

    $u1 = (int)($this->battleInfo['user_1'] ?? ($this->battleInfo['user1'] ?? 0));
    $u2 = (int)($this->battleInfo['user_2'] ?? ($this->battleInfo['user2'] ?? 0));
    $isPvp = (($this->battleInfo['type'] ?? '') === 'pvp') || ($u1 > 0 && $u2 > 0);
    $isWorldBoss = $this->_isWorldBoss();

    // Мировой босс или PvE — удаляем сразу
    if ($isWorldBoss || !$isPvp) {
        $this->update = [];
        Work::$sql->query("DELETE FROM `battle` WHERE `id`=".$battleId);
        return;
    }

    // PvP — двухфазное закрытие
    $ansArr = [];
    if (!empty($this->answer) && is_array($this->answer)) {
        $ansArr = $this->answer;
    } elseif (!empty($this->battleInfo['answer'])) {
        $ansArr = Info::_unParseData($this->battleInfo['answer']);
    }
    $hasFinal =
        (is_array($ansArr)) && (
            !empty($ansArr['battleEND']) ||
            ($u1 && !empty($ansArr['u'.$u1]['battleEND'])) ||
            ($u2 && !empty($ansArr['u'.$u2]['battleEND']))
        );

    Work::$sql->query("UPDATE `battle` SET `type`='pvp' WHERE `id`=".$battleId);

    if ($hasFinal) {
        // Шаг 1: первый завершивший зануляет только свой слот
        if ($u1 > 0 && $u2 > 0) {
            $col = ($u1 === $meId) ? 'user_1' : (($u2 === $meId) ? 'user_2' : null);
            if ($col) {
                Work::$sql->query("UPDATE `battle` SET `{$col}`=0 WHERE `id`=".$battleId);
            }
            return;
        }
        // Шаг 2: второй завершивший — удаляем запись боя
        $this->update = [];
        Work::$sql->query("DELETE FROM `battle` WHERE `id`=".$battleId);
        return;
    }

    // Нет финального кадра — мягко освобождаем только свой слот
    $col = ($u1 === $meId) ? 'user_1' : (($u2 === $meId) ? 'user_2' : null);
    if ($col) {
        Work::$sql->query("UPDATE `battle` SET `{$col}`=0 WHERE `id`=".$battleId);
    }
}

private function clearBattleEffects($userId) {
    $tables = [
        'atk_helping_hand',
        'atk_aromatic_mist',
        'battle_block',
        'atk_rollout',
        'atk_furycutter',
        'atk_tripleaxel',
        'atk_echo',
        'battle_effects'
    ];
    foreach ($tables as $table) {
        Work::$sql->query("DELETE FROM `$table` WHERE user = " . intval($userId));
    }
}

/* ——— Сохранение состояния в БД при завершении запроса ——— */
public function __destruct() {
    if (!empty($this->update) && isset($this->battleInfo['id'])) {
        // Если был targetHW2 — сбрасываем HP и флаг
        if (isset($this->userData['targetHW2']) && $this->userData['targetHW2'] !== 0) {
            $key = 'p' . $this->userData['targetHW2'];
            if (isset($this->userPokes[$key])) {
                $this->userPokes[$key]['hp'] = 0;
            }
            $this->userData['targetHW2'] = 0;
        }

        // Корректная расстановка сторон
        if ($this->_isPVPRow($this->battleInfo)) {
            if (isset($this->userInfo['id'], $this->battleInfo['user_1']) && $this->userInfo['id'] == $this->battleInfo['user_1']) {
                $upd_my    = '`info_1` = "' . Work::$sql->real_escape_string(Info::_parseData($this->userData)) . '"';
                $upd_enemy = '`info_2` = "' . Work::$sql->real_escape_string(Info::_parseData($this->enemyData)) . '"';
            } else {
                $upd_my    = '`info_2` = "' . Work::$sql->real_escape_string(Info::_parseData($this->userData)) . '"';
                $upd_enemy = '`info_1` = "' . Work::$sql->real_escape_string(Info::_parseData($this->enemyData)) . '"';
            }
        } else {
            // PvE/WorldBoss: игрок ВСЕГДА info_1, enemy ВСЕГДА info_2
            $upd_my    = '`info_1` = "' . Work::$sql->real_escape_string(Info::_parseData($this->userData)) . '"';
            $upd_enemy = '`info_2` = "' . Work::$sql->real_escape_string(Info::_parseData($this->enemyData)) . '"';
        }

        $set = [];
        $set[] = '`round` = ' . intval($this->round);
        if (!empty($upd_my))               $set[] = $upd_my;
        if (isset($this->update['enemy'])) $set[] = $upd_enemy;

        // Сохраняем team/is_command_battle (если колонки есть)
        if (array_key_exists('is_command_battle', $this->battleInfo)) {
            $flag = (int)($this->battleInfo['is_command_battle'] ?? 0);
            $set[] = '`is_command_battle` = ' . $flag;
        }
        if (!empty($this->battleInfo['team'])) {
            $teamStr = is_string($this->battleInfo['team'])
                ? $this->battleInfo['team']
                : Info::_parseData($this->battleInfo['team']);
            $set[] = '`team` = "' . Work::$sql->real_escape_string($teamStr) . '"';
        }

        if (!empty($this->otherInfo))  $set[] = '`other`  = "' . Work::$sql->real_escape_string(Info::_parseData($this->otherInfo)) . '"';
        if (!empty($this->answer))     $set[] = '`answer` = "' . Work::$sql->real_escape_string(Info::_parseData($this->answer)) . '"';
        else                           $set[] = '`answer` = ""';

        $sql = 'UPDATE `battle` SET ' . implode(', ', $set) . ' WHERE `id` = ' . intval($this->battleInfo['id']);
        Work::$sql->query($sql);
    }
}
}

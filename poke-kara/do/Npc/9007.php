<?php
/**
 * NPC #9007 — Марек (Покемаркет)
 *
 * Features:
 * - Modern shop UI (buy/sell) via PokeMarketNpc
 * - New quest #132 implemented ONLY with lib-classes (NpcKit + QuestKit + optional NpcBattle)
 * - Backward compatible with legacy NPC system
 */

// Safety: ensure lib classes exist even if index.php autoload is missing
$LIB = __DIR__ . '/lib/';
if (!class_exists('NpcKit') && file_exists($LIB.'NpcKit.php')) require_once $LIB.'NpcKit.php';
if (!class_exists('QuestKit') && file_exists($LIB.'QuestKit.php')) require_once $LIB.'QuestKit.php';
if (!class_exists('PokeMarketNpc') && file_exists($LIB.'PokeMarketNpc.php')) require_once $LIB.'PokeMarketNpc.php';
if (!class_exists('NpcBattle') && file_exists($LIB.'NpcBattle.php')) require_once $LIB.'NpcBattle.php';

// Ensure a DB handle is available for libs that expect global $mysqli
if (!isset($mysqli) || !$mysqli) {
    if (class_exists('Work') && isset(Work::$sql) && Work::$sql) {
        $mysqli = Work::$sql;
    }
}

NpcKit::strict();

// Make sure $response exists as array (prevents TypeError on strict libs)
if (!isset($response) || !is_array($response)) $response = [];

$npcId  = isset($npcId) ? (int)$npcId : (int)($_POST['npc'] ?? 0);
$stepIn = $_POST['step'] ?? ($npcStep ?? 0);
$step   = is_numeric($stepIn) ? (int)$stepIn : 0;

$uid = NpcKit::userId();
if ($uid <= 0) {
    NpcKit::error($response, 401, 'Требуется авторизация.');
    return;
}

// NPC base (name/image) from DB if present
$npcRow = NpcKit::loadNpc($npcId);
if (!$npcRow) {
    $npcRow = ['id'=>$npcId, 'name'=>'Марек', 'image'=>'/do/Npc/9007.png'];
}
$response = array_merge(NpcKit::responseBase($npcRow), $response);

// Quest config
$QID = 132;
$CURRENCY = 1; // Генкары
$PART_THUNDER = 73; // Осколок громового камня (дроп с электрических)
$PART_WATER   = 74; // Осколок водного камня (дроп с водных)
$PART_LEAF    = 75; // Осколок лиственного камня (дроп с травяных)
$POKEBALL  = 2;

$q = QuestKit::get($QID, $uid);
$qStep = $q ? (int)$q['step'] : 0;
$qEnd  = $q ? (int)$q['end']  : 0;
$qData = $q ? (array)$q['data'] : [];

// Helpers
$fmt = function($n) { return number_format((int)$n, 0, '.', '.'); };
$have = function($itemId) use ($uid) {
    $itemId = (int)$itemId;
    $q = Work::$sql->query("SELECT `count` FROM `items_users` WHERE `user` = {$uid} AND `item_id` = {$itemId} LIMIT 1");
    if (!$q) return 0;
    $r = $q->fetch_assoc();
    return $r ? (int)$r['count'] : 0;
};

// SHOP screens (delegated)
if ($step === 1 || $step === 2) {
    PokeMarketNpc::run($response, $npcId, $step);
    return;
}

// Root menu (step 0)
if ($step === 0) {
    $status = ($qEnd === 1)
        ? 'Кратко: магазин открыт, выкуп работает.'
        : (($qStep > 0)
            ? 'Кратко: я прямо сейчас оформляю лицензию на выкуп.'
            : 'Кратко: я стажёр, а терминал уже решил умереть.');

    $text = "Привет! Я <b>Марек</b>. Сегодня мой первый день за стойкой.\n\n".
            "{$status}\n\n".
            "Если хочешь — помоги мне пройти проверку. Это займет пару минут, зато потом я буду работать без сюрпризов (и выкуп тоже).";

    $response['question'] = nl2br($text);
    $ans = [
        1   => 'Купить',
        2   => 'Продать',
        100 => ($qEnd === 1 ? 'Поговорить' : 'Проверка (квест)') ,
        0   => 'Уйти'
    ];
    $response['answer'] = $ans;
    return;
}

// Quest / dialog tree
if ($step === 100) {
    if ($qEnd === 1) {
        $response['question'] = "Марек поправляет бейджик и улыбается: <i>«Спасибо ещё раз. Если что — я тут.»</i>";
        $response['answer'] = [
            1 => 'Купить',
            2 => 'Продать',
            0 => 'Назад'
        ];
        return;
    }

    if ($qStep <= 0) {
        $response['question'] = "Честно? Я волнуюсь. Проверяющий любит придираться.\n\n".
                              "Поможешь мне быстро подготовиться?";
        $response['answer'] = [
            101 => 'Да, что нужно?',
            0   => 'Не сейчас'
        ];
        return;
    }

    // Progress hub
    if ($qStep === 1) {
        $response['question'] = "Мы на шаге <b>1/3</b>. Надо оживить мой ценник-сканер.\n".
                              "Нужна <b>1</b> деталь для замены: осколок громового/водного/лиственного камня — или можно оплатить срочную замену платы.";
        $response['answer'] = [
            110 => 'Показать список',
            0   => 'Назад'
        ];
        return;
    }

    if ($qStep === 2) {
        $response['question'] = "Шаг <b>2/3</b>. Теперь — практика возврата.\n\n".
                              "Дай мне <b>5 покеболов</b>, я оформлю выкуп и верну тебе деньги. Это просто проверка процедуры.";
        $response['answer'] = [
            120 => 'Оформить выкуп (5 покеболов)',
            121 => 'У меня нет покеболов',
            0   => 'Назад'
        ];
        return;
    }

    if ($qStep === 3) {
        $response['question'] = "Финальный шаг <b>3/3</b>. Тут как раз… ситуация.\n\n".
                              "Вон тот тип слишком внимательно смотрит на витрину. Я могу замять по-тихому, а могу попросить тебя <i>«посторожить вход»</i>.";
        $response['answer'] = [
            130 => 'Ок, разберусь сам',
            131 => 'Лучше позовём Дженни',
            0   => 'Назад'
        ];
        return;
    }

    // Fallback
    $response['question'] = "Марек в замешательстве: <i>«Кажется, я потерял лист проверки… Давай начнём заново?»</i>";
    $response['answer'] = [
        101 => 'Начать сначала',
        0 => 'Назад'
    ];
    return;
}

// Accept quest
if ($step === 101) {
    QuestKit::set($QID, 1, false, [
        'fixed' => 0,
        'practice' => 0,
        'final' => 0,
        'ts' => time(),
    ]);

    $response['question'] = "Супер. Тогда по делу.\n\n".
                          "<b>Шаг 1:</b> сканер цен. После доставки он решил, что весь мир — товар, и пищит даже на воздух.\n".
                          "Нужна <b>1</b> деталь для замены: осколок громового/водного/лиственного камня.\n\n".
                          "Если времени нет — можно оплатить срочную замену платы: <b>3.000</b> генкаров.";

    $response['answer'] = [
        110 => 'Проверить список',
        111 => 'Оплатить 3.000 генкаров',
        0   => 'Назад'
    ];
    return;
}

// Step 1 — show requirements
if ($step === 110) {
    // Ensure quest exists
    if (!$q) {
        QuestKit::set($QID, 1, false, ['fixed'=>0,'practice'=>0,'final'=>0,'ts'=>time()]);
        $qStep = 1; $qEnd = 0; $qData = ['fixed'=>0,'practice'=>0,'final'=>0,'ts'=>time()];
    }

    $t = $have($PART_THUNDER);
    $w = $have($PART_WATER);
    $l = $have($PART_LEAF);

    $need = "Нужно: <b>1</b> × любая деталь для замены:
".
            "— Осколок громового камня (ID {$PART_THUNDER})
".
            "— Осколок водного камня (ID {$PART_WATER})
".
            "— Осколок лиственного камня (ID {$PART_LEAF}).

".
            "У тебя сейчас: громовой — <b>{$t}</b>, водный — <b>{$w}</b>, лиственный — <b>{$l}</b>.";

    $response['question'] = nl2br($need);

    $canGive = ($t >= 1 || $w >= 1 || $l >= 1);
    $response['answer'] = [
        112 => $canGive ? 'Отдать деталь' : 'Не хватает деталей',
        111 => 'Оплатить 3.000 генкаров',
        0   => 'Назад'
    ];
    return;
}

// Step 1 — pay alternative
if ($step === 111) {
    if (!$q) {
        QuestKit::set($QID, 1, false, ['fixed'=>0,'practice'=>0,'final'=>0,'ts'=>time()]);
        $qStep = 1; $qEnd = 0; $qData = ['fixed'=>0,'practice'=>0,'final'=>0,'ts'=>time()];
    }

    $price = 3000;
    if (!item_isset($CURRENCY, $price)) {
        $haveCur = $have($CURRENCY);
        $response['question'] = "Марек смущённо кашляет: <i>«Не хочу быть тем парнем, но… у тебя сейчас {$fmt($haveCur)} генкаров. Нужно {$fmt($price)}.»</i>\n\n".
                              "Можешь принести деталь вместо оплаты.";
        $response['answer'] = [
            110 => 'Проверить список',
            0   => 'Назад'
        ];
        return;
    }

    minus_item($CURRENCY, $price);
    $qData['fixed'] = 1;
    $qData['fixed_method'] = 'pay';
    QuestKit::set($QID, 2, false, $qData);

    $response['question'] = "Марек быстро прячет плату под прилавок: <i>«Ого. Тогда я сейчас… да, вот так…»</i>\n\n".
                          "Сканер замолкает.\n\n<b>Шаг 2:</b> покажем проверяющему, что выкуп работает без ошибок.";
    $response['answer'] = [
        100 => 'Продолжить',
        0   => 'Назад'
    ];
    return;
}

// Step 1 — give part
if ($step === 112) {
    if (!$q) {
        QuestKit::set($QID, 1, false, ['fixed'=>0,'practice'=>0,'final'=>0,'ts'=>time()]);
        $qStep = 1; $qEnd = 0; $qData = ['fixed'=>0,'practice'=>0,'final'=>0,'ts'=>time()];
    }

    // Pick any available part
    $giveId = 0;
    if (item_isset($PART_THUNDER, 1)) $giveId = $PART_THUNDER;
    elseif (item_isset($PART_WATER, 1)) $giveId = $PART_WATER;
    elseif (item_isset($PART_LEAF, 1)) $giveId = $PART_LEAF;

    if (!$giveId) {
        $response['question'] = "Марек разводит руками: <i>«Похоже, детали нет. Давай ещё раз сверим.»</i>";
        $response['answer'] = [
            110 => 'Проверить список',
            0   => 'Назад'
        ];
        return;
    }

    minus_item($giveId, 1);

    $qData['fixed'] = 1;
    $qData['fixed_method'] = 'part';
    $qData['fixed_item'] = (int)$giveId;
    QuestKit::set($QID, 2, false, $qData);

    $response['question'] = "Марек аккуратно втыкает деталь в сканер, что-то настраивает…\n\n".
                          "Сканер перестаёт пищать. <i>«Работает! Ты волшебник.»</i>\n\n".
                          "Теперь <b>Шаг 2:</b> практика выкупа.";
    $response['answer'] = [
        100 => 'Продолжить',
        0   => 'Назад'
    ];
    return;
}

// Step 2 — practice (sell 5 pokeballs for fixed amount)
if ($step === 120) {
    if (!$q || $qStep < 2) {
        $response['question'] = "Марек моргает: <i>«Я ещё сканер не починил. Вернёмся к шагу 1?»</i>";
        $response['answer'] = [
            100 => 'К шагу 1',
            0   => 'Назад'
        ];
        return;
    }

    $needBalls = 5;
    if (!item_isset($POKEBALL, $needBalls)) {
        $haveBalls = $have($POKEBALL);
        $response['question'] = "Нужны <b>{$needBalls}</b> покеболов для теста.\n".
                              "У тебя сейчас: <b>{$haveBalls}</b>.\n\n".
                              "Если хочешь — просто зайди в раздел <b>Купить</b> и возьми несколько, а потом вернёмся.";
        $response['answer'] = [
            1 => 'Купить',
            100 => 'Назад к разговору',
            0 => 'Назад'
        ];
        return;
    }

    // Payout: 60% от цены покупки (покебол по 250 -> 150)
    $payoutPer = 150;
    $total = $payoutPer * $needBalls;

    minus_item($POKEBALL, $needBalls);
    itemAdd($CURRENCY, $total);

    $qData['practice'] = 1;
    $qData['practice_payout'] = $total;
    QuestKit::set($QID, 3, false, $qData);

    $response['question'] = "Марек старательно печатает в терминале: <i>«Так… возврат… причина: " .
                          "\"покупатель передумал\"…»</i>\n\n".
                          "Готово. Ты получил <b>{$fmt($total)}</b> генкаров.\n\n".
                          "Остался финальный шаг.";
    $response['answer'] = [
        100 => 'Финальный шаг',
        0   => 'Назад'
    ];
    return;
}

if ($step === 121) {
    $response['question'] = "Окей. Если покеболов нет — это не страшно.\n\n".
                          "Подсказка: в разделе <b>Купить</b> они обычно стоят недорого. Возьми пару пачек и вернись — проверим процедуру выкупа.";
    $response['answer'] = [
        1 => 'Купить',
        100 => 'Назад к разговору',
        0 => 'Назад'
    ];
    return;
}

// Step 3 — final situation: battle or call guard
if ($step === 130) {
    if (!$q || $qStep < 3) {
        $response['question'] = "Марек нервно улыбается: <i>«Давай сначала разберёмся с проверкой шагов…»</i>";
        $response['answer'] = [
            100 => 'К разговору',
            0 => 'Назад'
        ];
        return;
    }

    $response['question'] = "Ты встаёшь у входа, и «покупатель» тут же меняет траекторию.\n\n".
                          "Он свистит — и из-за угла выскакивают два покемона. Похоже, это проверка… или попытка отвлечь.\n\n".
                          "Готов к бою?";
    $response['answer'] = [
        132 => 'Начать бой',
        131 => 'Лучше позовём Дженни',
        0   => 'Назад'
    ];
    return;
}

if ($step === 132) {
    if (!$q || $qStep < 3) {
        $response['question'] = "Марек: <i>«Сначала доведём до финала подготовку…»</i>";
        $response['answer'] = [
            100 => 'К разговору',
            0 => 'Назад'
        ];
        return;
    }

    if (!class_exists('NpcBattle') || !method_exists('NpcBattle', 'start')) {
        $response['question'] = "Не удалось запустить бой: не найдена библиотека <b>NpcBattle</b>.\n\n".
                              "Проверь, что установлен npc_modern_patch (папка <b>/do/Npc/lib/</b>).";
        $response['answer'] = [
            131 => 'Позвать Дженни',
            0   => 'Назад'
        ];
        return;
    }

    // Battle (soft-gated): quest continues regardless of outcome.
    // Enemy pokes are intentionally modest to fit early-game.
    $enemy = [
        ['basenum'=>52, 'lvl'=>12, 'numb'=>0, 'boss'=>0, 'catch'=>0], // Meowth
        ['basenum'=>19, 'lvl'=>11, 'numb'=>0, 'boss'=>0, 'catch'=>0], // Rattata
    ];

    $battleId = NpcBattle::start($enemy, [
        'user_id'       => $uid,
        'battle_type'   => 'pve',
        'weather'       => 1,
        'weather_round' => 10,
        'img'           => 0,
        'arena'         => 0,
        'logText'       => 'Проверка стажёра: начало боя.<br>',
    ]);

    if ($battleId <= 0) {
        $response['question'] = "Бой не запустился (battleId=0).\n\n".
                              "Обычно это значит, что не доступен <b>Info::_userInfoBattle()</b> или не подключен глобальный <b>$mysqli</b>.\n".
                              "Проверь, что в системе есть класс <b>Info</b> и установлен npc_modern_patch.";
        $response['answer'] = [
            130 => 'Назад',
            131 => 'Позвать Дженни',
            0   => 'Закрыть'
        ];
        return;
    }

    $qData['final'] = 1;
    $qData['battle_id'] = (int)$battleId;
    QuestKit::set($QID, 4, false, $qData);

    NpcKit::openBattle($response, $battleId);
    return;
}

if ($step === 131) {
    if (!$q || $qStep < 3) {
        $response['question'] = "Марек кивает: <i>«Да, ты прав. Я тороплюсь.»</i>";
        $response['answer'] = [
            100 => 'К разговору',
            0 => 'Назад'
        ];
        return;
    }

    $qData['final'] = 2;
    QuestKit::set($QID, 4, false, $qData);

    $response['question'] = "Марек нажимает тревожную кнопку.\n\n".
                          "Через минуту в зал заходит Дженни. «Покупатель» резко теряет интерес и исчезает.\n\n".
                          "Марек выдыхает: <i>«Окей… финал пройден.»</i>";
    $response['answer'] = [
        140 => 'Завершить проверку',
        0   => 'Назад'
    ];
    return;
}

// Finalization / reward claim
if ($step === 140) {
    if (!$q || $qEnd === 1) {
        $response['question'] = "Марек: <i>«Мы уже всё закрыли. Спасибо!»</i>";
        $response['answer'] = [
            0 => 'Назад'
        ];
        return;
    }

    if ($qStep < 4) {
        $response['question'] = "Марек: <i>«Подожди, я ещё не закончил процедуру.»</i>";
        $response['answer'] = [
            100 => 'К разговору',
            0 => 'Назад'
        ];
        return;
    }

    $battleNote = '';
    if (!empty($qData['battle_id'])) {
        $bid = (int)$qData['battle_id'];
        $battleNote = "\n\n<i>Проверка боя записана в журнал (ID {$bid}).</i>";
    }

    $response['question'] = "Марек расправляет плечи: <i>«Фух. Спасибо. Без тебя я бы точно накосячил.»</i>\n\n".
                          "Проверка закрыта. Теперь я могу включить выкуп официально.{$battleNote}";

    $response['answer'] = [
        141 => 'Забрать благодарность',
        0   => 'Назад'
    ];
    return;
}

if ($step === 141) {
    if (!$q || $qEnd === 1) {
        $response['question'] = "Марек: <i>«Ты уже всё получил.»</i>";
        $response['answer'] = [0 => 'Назад'];
        return;
    }

    if ($qStep < 4) {
        $response['question'] = "Марек: <i>«Сначала доведём дело до конца.»</i>";
        $response['answer'] = [100 => 'К разговору', 0 => 'Назад'];
        return;
    }

    // Rewards (kept modest). You can adjust numbers safely.
    $rewardMoney = 5000;
    $rewardBalls = 5;

    itemAdd($CURRENCY, $rewardMoney);
    itemAdd(3, $rewardBalls); // Great Ball

    $qData['reward'] = ['money'=>$rewardMoney,'greatball'=>$rewardBalls,'ts'=>time()];
    QuestKit::set($QID, 99, true, $qData);

    $response['question'] = "Марек тихо кладёт в твою ладонь конверт: <i>«Это по-честному.»</i>\n\n".
                          "Получено: <b>{$fmt($rewardMoney)}</b> генкаров и <b>{$rewardBalls}</b> суперболов.\n\n".
                          "<b>Теперь выкуп и продажа работают без ограничений.</b>";
    $response['answer'] = [
        1 => 'Купить',
        2 => 'Продать',
        0 => 'Закрыть'
    ];
    return;
}

// Unknown step fallback
$response['question'] = "Марек моргает: <i>«Я не понял. Давай ещё раз.»</i>";
$response['answer'] = [
    0 => 'Назад'
];
return;

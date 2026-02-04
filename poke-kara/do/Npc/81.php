<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

// NPC 81 — "Сумка профессора" (квест 1: Начало путешествия)
// Важное: бой создаётся как type='npc' и ловля в нём запрещена.

$npcId = 81;

// Данные NPC
$query = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ".intval($npcId)." LIMIT 1");
$npc = $query ? $query->fetch_assoc() : null;

$response = $response ?? [];

if ($npc) {
    $response['name']  = $npc['name'];
    $response['image'] = htmlspecialchars($npc['image']);
} else {
    $response['name']  = 'Неизвестный NPC';
    $response['image'] = '/img/default-npc.png';
}

// Список стартовых покемонов (полный пул; по сюжету — Канто, остальные опционально)
$starters = [
    1 => ["Бульбазавр", "Травяной / Ядовитый", "590,548,0,0", "25,35,0,0"],
    4 => ["Чармандер", "Огненный", "142,458,0,0", "25,35,0,0"],
    7 => ["Сквиртл", "Водный", "625,548,0,0", "25,35,0,0"],

    // Дополнительные регионы (по желанию игрока)
    152 => ["Чикорита", "Травяной", "421,548,0,0", "25,35,0,0"],
    155 => ["Синдаквил", "Огненный", "142,548,0,0", "25,35,0,0"],
    158 => ["Тотодайл", "Водный", "625,458,0,0", "25,35,0,0"],
    252 => ["Трико", "Травяной", "648,392,0,0", "35,35,0,0"],
    255 => ["Торчик", "Огненный", "142,458,0,0", "25,35,0,0"],
    258 => ["Мадкип", "Водный", "625,548,0,0", "25,35,0,0"],
    387 => ["Туртвиг", "Травяной", "1,548,0,0", "15,35,0,0"],
    390 => ["Чимчар", "Огненный", "142,458,0,0", "25,35,0,0"],
    393 => ["Пиплап", "Водный", "58,392,0,0", "30,35,0,0"],
    495 => ["Снайви", "Травяной", "590,548,0,0", "25,35,0,0"],
    498 => ["Тепиг", "Огненный", "142,548,0,0", "25,35,0,0"],
    501 => ["Ошавот", "Водный", "625,548,0,0", "25,35,0,0"],
    650 => ["Чеспин", "Травяной", "590,207,0,0", "25,40,0,0"],
    653 => ["Феннекин", "Огненный", "142,458,0,0", "25,35,0,0"],
    656 => ["Фроаки", "Водный", "58,392,0,0", "30,35,0,0"],
    722 => ["Роулет", "Травяной / Летающий", "648,548,0,0", "40,35,0,0"],
    725 => ["Литтен", "Огненный", "142,458,0,0", "25,35,0,0"],
    728 => ["Попплио", "Водный", "392,625,0,0", "25,35,0,0"],
];

// Набор "канонических" стартов для начала Канто
$kantoStarterIds = [1, 4, 7];

// Внешние переменные диалога приходят из вашего NPC-рантайма
$npcStep = isset($npcStep) ? intval($npcStep) : 0;


// Нормализация шага выбора покемона: некоторые фронты передают basenum напрямую (1/4/7),
// другие — смещённые значения (1001..1999) и подтверждение (2001..2999).
function npc81_normalizePokemonId(int $npcStep, int $rangeBase): int {
    // $rangeBase: 1000 для просмотра, 2000 для подтверждения
    if ($npcStep >= $rangeBase && $npcStep < ($rangeBase + 1000)) {
        return $npcStep - $rangeBase;
    }
    // если пришёл basenum напрямую (и он не конфликтует с управляющими шагами)
    return $npcStep;
}


switch (true) {
    default:
        if (quest_step(1, 4)) {
            $response['question'] = 'Вы замечаете сумку, лежащую на земле. Похоже, профессор Оук уронил её во время нападения диких покемонов.';
            $response['answer']   = [900 => "Открыть сумку."];

            quest_update(1, 4);
            update_zap(1, 4, 'Я нашел сумку профессора. Внутри должны быть покеболы.');
            break;
        }

        if (quest_step(1, 5)) {
            $response['question'] = 'Сумка уже открыта. Пора помочь профессору Оуку — дикие покемоны всё ещё рядом.';
            $response['answer']   = [902 => "Осмотреться вокруг."];
            break;
        }

        if (quest_step(1, 7, 1)) {
            $response['question'] = "Приятное напоминание о начале путешествия.";
            break;
        }

        // fallback
        $response['question'] = 'Похоже, здесь сейчас нечего делать.';
        break;
    case ($npcStep === 900):
        // Если уже выбрал покемона — повторно не даём
        if (quest_step(1, 5)) {
            $response['question'] = "Ты уже выбрал покемона! Теперь помоги защитить Профессора Оука!";
            break;
        }
        if (quest_step(1, 7, 1)) {
            $response['question'] = "Приятное напоминание о начале путешествия.";
            break;
        }

        $response['question'] = 'Ты открываешь сумку. Внутри — три аккуратно подписанных покебола. Профессор Оук всегда начинает путь тренера с выбора партнёра.';
        $response['answer'] = [];

        // Канто-выбор по сюжету
        foreach ($kantoStarterIds as $id) {
            if (!isset($starters[$id])) continue;
            $response['answer'][$id + 1000] = "<img src='/img/pokemons/animation/{$id}.png' style='width: 30px;'> #{$id} {$starters[$id][0]}";
        }
        // Опционально: открыть расширенный список
        $response['answer'][901] = "Похоже, здесь есть и другие покеболы…";
        break;
    case ($npcStep === 901):
        // Расширенный выбор (по желанию)
        $response['question'] = 'В глубине сумки ты находишь дополнительные покеболы — похоже, профессор собирал их для разных учеников. Выбирай осторожно: первый партнёр запоминается навсегда.';
        $response['answer'] = [];
        foreach ($starters as $id => $info) {
            $response['answer'][$id + 1000] = "<img src='/img/pokemons/animation/{$id}.png' style='width: 30px;'> #{$id} {$info[0]}";
        }
        $response['answer'][900] = "Вернуться к трём основным";
        break;
    case ($npcStep === 902):
        // Мягкая подсказка, если игрок на шаге 5 но бой ещё не запустили (например перезагрузка)
        $response['question'] = "Ты слышишь шум в кустах. Дикие покемоны снова приближаются — нужно быть готовым к бою.";
        $response['answer'] = [];
        $response['answer'][903] = "Приготовиться к бою";
        break;
    case ($npcStep === 903):
        // Запуск боя повторно (идемпотентно) на шаге 5
        $userId = intval($_SESSION['id'] ?? 0);
        if ($userId <= 0) {
            $response['question'] = "Ошибка: не удалось определить пользователя.";
            break;
        }

        $enemy = [
            ['basenum' => 10, 'lvl' => 2, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Caterpie
            ['basenum' => 13, 'lvl' => 2, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Weedle
            ['basenum' => 11, 'lvl' => 3, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Metapod (чуть сложнее)
        ];

        $battleId = Info::createNpcBattle($userId, $enemy, [
            'type' => 'npc',
            'arena' => 1,
            'img' => 0,
            'weather' => 1,
            'weather_round' => 10,
            'starterLog' => 1,
            'scripted' => true,
            'npc_id' => 81,
            'quest_id' => 1,
            'quest_step' => 5,
            'other' => ['no_catch' => 1, 'story' => 1],
        ]);

        if ($battleId <= 0) {
            $response['question'] = "Не удалось создать бой. Попробуйте ещё раз.";
            break;
        }

        $response['question'] = 'Дикие покемоны напали! Бой начинается!';
        $response['battle_id'] = $battleId;
        $response['closeDialog'] = true;
        break;

    // Показ информации о выбранном покемоне

// Показ информации о выбранном покемоне (если фронт прислал basenum напрямую)
case ($npcStep > 0 && $npcStep < 1000 && isset($starters[$npcStep])):
    if (!quest_step(1, 4)) {
        $response['question'] = 'Ошибка!';
        break;
    }

    $pokemonID = $npcStep;
    if ($pokemonID <= 0) {
        $response['question'] = 'Ошибка! Некорректный выбор.';
        $response['answer'] = [900 => 'Вернуться к выбору'];
        break;
    }
    if (!isset($starters[$pokemonID])) {
        $response['question'] = 'Ошибка! Покемон не найден.';
        $response['answer'] = [900 => 'Вернуться к выбору'];
        break;
    }

    [$pokemonName, $pokemonType] = $starters[$pokemonID];

    $stmt = Work::$sql->prepare("SELECT name, name_rus, type, about FROM base_pokemons WHERE id = ?");
    $stmt->bind_param("i", $pokemonID);
    $stmt->execute();
    $pokemonData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $pokemonNameEn  = htmlspecialchars($pokemonData['name'] ?? "Unknown");
    $pokemonAbout   = nl2br(htmlspecialchars($pokemonData['about'] ?? "Описание недоступно."));
    $pokemonNameRus = htmlspecialchars($pokemonData['name_rus'] ?? $pokemonName);

    $response['question'] = "
        <div style='text-align:center'>
            <img src='https://img.pokemondb.net/sprites/home/normal/2x/".urlencode(strtolower($pokemonNameEn)).".jpg' width='160'>
            <br><b>{$pokemonNameRus}</b>
        </div><br>
        <b>Тип:</b> {$pokemonType} <br>
        {$pokemonAbout} <br><br>
        Хочешь выбрать {$pokemonNameRus}?";

    $response['answer'] = [
        ($pokemonID + 2000) => "Да ✅",
        900 => "Я хочу другого 🔄"
    ];
    break;

    case ($npcStep >= 1000 && $npcStep < 2000):
        if (!quest_step(1, 4)) {
            $response['question'] = 'Ошибка!';
            break;
        }

        $pokemonID = npc81_normalizePokemonId($npcStep, 1000);
        if ($pokemonID <= 0) {
            $response['question'] = 'Ошибка! Некорректный выбор.';
            $response['answer'] = [900 => 'Вернуться к выбору'];
            break;
        }
        if (!isset($starters[$pokemonID])) {
            $response['question'] = 'Ошибка! Покемон не найден.';
            $response['answer'] = [900 => 'Вернуться к выбору'];
            break;
        }

        [$pokemonName, $pokemonType] = $starters[$pokemonID];

        $stmt = Work::$sql->prepare("SELECT name, name_rus, type, about FROM base_pokemons WHERE id = ?");
        $stmt->bind_param("i", $pokemonID);
        $stmt->execute();
        $pokemonData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $pokemonNameEn  = htmlspecialchars($pokemonData['name'] ?? "Unknown");
        $pokemonAbout   = nl2br(htmlspecialchars($pokemonData['about'] ?? "Описание недоступно."));
        $pokemonNameRus = htmlspecialchars($pokemonData['name_rus'] ?? $pokemonName);

        $response['question'] = "
            <div style='text-align:center'>
                <img src='https://img.pokemondb.net/sprites/home/normal/2x/".urlencode(strtolower($pokemonNameEn)).".jpg' width='160'>
                <br><b>{$pokemonNameRus}</b>
            </div><br>
            <b>Тип:</b> {$pokemonType} <br>
            {$pokemonAbout} <br><br>
            Хочешь выбрать {$pokemonNameRus}?";

        $response['answer'] = [
            $npcStep + 1000 => "Да ✅",
            900 => "Я хочу другого 🔄"
        ];
        break;

    // Подтверждение выбора покемона + запуск боя
    case ($npcStep >= 2000 && $npcStep < 3000):
        if (!quest_step(1, 4)) {
            $response['question'] = 'Ошибка!';
            break;
        }

        $pokemonID = npc81_normalizePokemonId($npcStep, 2000);
        if ($pokemonID <= 0) {
            $response['question'] = 'Ошибка! Некорректный выбор.';
            $response['answer'] = [900 => 'Вернуться к выбору'];
            break;
        }
        if (!isset($starters[$pokemonID])) {
            $response['question'] = 'Ошибка! Покемон не найден.';
            $response['answer'] = [900 => 'Вернуться к выбору'];
            break;
        }

        [$pokemonName, $pokemonType] = $starters[$pokemonID];

        $response['actionQuestPlus'] = "<img src='/img/pokemons/animation/{$pokemonID}.png'> #{$pokemonID} {$pokemonName}<br>";
        $response['actionQuest'] = "Ты выбрал {$pokemonName}!";

        // Выдача покемона игроку
        newPokemon($pokemonID, $_SESSION['id'], 5, 25, 1, 'false', 1, false, false, 4, 15, true);

        // Обновляем атаки/pp для стартера (чтобы фронт точно видел валидный список)
        $res = $mysqli->query("SELECT id FROM user_pokemons WHERE user_id = ".intval($_SESSION['id'])." ORDER BY id DESC LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        $userPokemonId = $row['id'] ?? null;

        if ($userPokemonId && isset($starters[$pokemonID])) {
            $attacksStr = $starters[$pokemonID][2];
            $ppStr      = $starters[$pokemonID][3];
            $mysqli->query("UPDATE user_pokemons SET attacks = '".$mysqli->real_escape_string($attacksStr)."', pp_attacks = '".$mysqli->real_escape_string($ppStr)."' WHERE id = ".intval($userPokemonId));
        }

        // Фиксируем шаг квеста: выбор сделан
        quest_update(1, 5);
        update_zap(1, 5, "Я выбрал {$pokemonName}! Теперь нужно помочь профессору.");

        // Закрываем диалог и создаём сюжетный бой (ловля запрещена)
        $userId = intval($_SESSION['id'] ?? 0);

        $enemy = [
            ['basenum' => 10, 'lvl' => 2, 'numb' => 0, 'boss' => 0, 'catch' => 0],
            ['basenum' => 13, 'lvl' => 2, 'numb' => 0, 'boss' => 0, 'catch' => 0],
            ['basenum' => 11, 'lvl' => 3, 'numb' => 0, 'boss' => 0, 'catch' => 0],
        ];

        $battleId = Info::createNpcBattle($userId, $enemy, [
            'type' => 'npc',
            'arena' => 1,
            'img' => 0,
            'weather' => 1,
            'weather_round' => 10,
            'starterLog' => 1,
            'scripted' => true,
            'npc_id' => 81,
            'quest_id' => 1,
            'quest_step' => 5,
            'other' => ['no_catch' => 1, 'story' => 1, 'scene' => 'oak_bag_attack'],
        ]);

        $response['question'] = 'Дикие покемоны напали! Бой начинается!';
        $response['battle_id'] = $battleId;
        $response['closeDialog'] = true;
        break;
}
?>
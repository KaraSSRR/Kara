<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 81 — Сумка профессора
 *
 * Квест 1 (step=4): выбор стартера + старт scripted боя через NpcBattle.
 * - если late=1 (проспал) — остаётся только Пикачу (#025)
 * - если late=0 — классическая тройка Канто (#001/#004/#007)
 */

$npcId = 81;

$npc = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ".(int)$npcId)->fetch_assoc();
$response['name']  = $npc['name'] ?? 'Сумка';
$response['image'] = isset($npc['image']) ? htmlspecialchars((string)$npc['image']) : '/img/default-npc.png';

$userId = (int)($_SESSION['id'] ?? 0);

// Флаг "опоздал" из квеста 1
$late = 1;
if (class_exists('QuestKit')) {
    $late = (int)QuestKit::dataGet(1, 'late', 1);
}

// Метаданные стартеров (атаки — только если вы уверены в id в вашей базе)
$starters = [
    1  => ["Бульбазавр", "Травяной / Ядовитый", "590,548,0,0", "25,35,0,0"],
    4  => ["Чармандер", "Огненный",             "142,458,0,0", "25,35,0,0"],
    7  => ["Сквиртл",   "Водный",               "625,548,0,0", "25,35,0,0"],
    25 => ["Пикачу",    "Электрический",        null,          null],
];

// Доступный список в зависимости от late
$allowed = $late ? [25] : [1, 4, 7];

function _pokemonCard($pokemonId, $pokemonName, $pokemonNameRus, $pokemonType, $pokemonAbout) {
    $pokemonName = htmlspecialchars((string)$pokemonName);
    $pokemonNameRus = htmlspecialchars((string)$pokemonNameRus);
    $pokemonType = htmlspecialchars((string)$pokemonType);
    $pokemonAbout = nl2br(htmlspecialchars((string)$pokemonAbout));
    $img = 'https://img.pokemondb.net/sprites/home/normal/2x/'.urlencode(strtolower($pokemonName)).'.jpg';

    return "
        <div style='text-align:center'>
            <img src='{$img}' width='160'>
            <br><b>{$pokemonNameRus}</b>
        </div><br>
        <b>Тип:</b> {$pokemonType}<br><br>
        {$pokemonAbout}<br><br>
    ";
}

// -------------------------
// 2000..2999 — подтверждение выбора
// -------------------------
if ($npcStep >= 2000 && $npcStep < 3000) {
    if (!quest_step(1, 4)) {
        $response['question'] = 'Ошибка! Неверный шаг.';
        return;
    }

    $pokemonID = (int)($npcStep - 2000);
    if (!in_array($pokemonID, $allowed, true) || !isset($starters[$pokemonID])) {
        $response['question'] = 'Ошибка! Этот покемон сейчас недоступен.';
        return;
    }

    $pokemonNameRus = $starters[$pokemonID][0];

    $response['actionQuestPlus'] = "<img src='/img/pokemons/animation/{$pokemonID}.png'> #{$pokemonID} {$pokemonNameRus}<br>";
    $response['actionQuest'] = "Ты выбрал {$pokemonNameRus}!";

    // Выдача покемона игроку
    newPokemon($pokemonID, $_SESSION['id'], 5, 25, 1, 'false', 1, false, false, 4, 15, true);

    // Получаем id только что выданного покемона
    $res = $mysqli->query("SELECT id FROM user_pokemons WHERE user_id = ".intval($_SESSION['id'])." ORDER BY id DESC LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    $userPokemonId = $row['id'] ?? null;

    // Если для стартера задан фикс атак — ставим
    if ($userPokemonId && !empty($starters[$pokemonID][2]) && !empty($starters[$pokemonID][3])) {
        $attacksStr = $starters[$pokemonID][2];
        $ppStr = $starters[$pokemonID][3];
        $mysqli->query("UPDATE user_pokemons SET attacks = '".$mysqli->real_escape_string($attacksStr)."', pp_attacks = '".$mysqli->real_escape_string($ppStr)."' WHERE id = ".(int)$userPokemonId);
    }

    // Фиксируем прогресс квеста (step=5)
    if (class_exists('QuestKit')) {
        QuestKit::set(1, 5, 0, null, ['starter_id' => $pokemonID]);
    } else {
        quest_update(1, 5);
    }
    if (function_exists('update_zap')) {
        update_zap(1, 5, "Я выбрал {$pokemonNameRus}. Теперь нужно защитить профессора в бою.");
    }

    // Старт scripted боя (используем текущую локацию игрока для arena/weather/img)
    $arena = 1;
    $weather = 1;
    $imgFight = '/img/battle/battle_bg.jpg';

    $uLoc = $mysqli->query("SELECT `location` FROM `users` WHERE `id`=".(int)$userId)->fetch_assoc();
    if ($uLoc && isset($uLoc['location'])) {
        $arena = (int)$uLoc['location'];
        $locData = $mysqli->query("SELECT `region`,`img_fight`,`weather` FROM `base_location` WHERE `id`=".$arena)->fetch_assoc();
        if ($locData) {
            if (!empty($locData['img_fight'])) $imgFight = $locData['img_fight'];
            if (!empty($locData['weather'])) {
                $weather = (int)$locData['weather'];
            } elseif (!empty($locData['region'])) {
                $regData = $mysqli->query("SELECT `weather` FROM `base_region` WHERE `id`=".(int)$locData['region'])->fetch_assoc();
                if ($regData && !empty($regData['weather'])) $weather = (int)$regData['weather'];
            }
        }
    }

    // Стартовый бой: 2 противника 1 уровня (по запросу)
    $enemy = [
        ['basenum' => 16, 'lvl' => 1, 'numb' => 0, 'boss' => 0, 'catch' => 1], // Pidgey
        ['basenum' => 19, 'lvl' => 1, 'numb' => 0, 'boss' => 0, 'catch' => 1], // Rattata
    ];

    $battleId = 0;
    if (class_exists('NpcBattle')) {
        $battleId = NpcBattle::start($enemy, [
            'battle_type' => 'wild',
            'weather' => $weather,
            'weather_round' => 0,
            'img' => $imgFight,
            'arena' => $arena,
            'logText' => 'На профессора напали дикие покемоны!<br>'
        ]);
    }

    $response['closeDialog'] = true;
    if ($battleId > 0) {
        $response['question'] = 'Дикие покемоны напали! Бой начинается!';
        $response['battle_id'] = $battleId;
    } else {
        $response['question'] = 'Покемон решительно настроен. Скорее помоги профессору!';
    }

    return;
}

// -------------------------
// 1000..1999 — карточка покемона
// -------------------------
if ($npcStep >= 1000 && $npcStep < 2000) {
    if (!quest_step(1, 4)) {
        $response['question'] = 'Сумка закрыта.';
        return;
    }

    $pokemonID = (int)($npcStep - 1000);
    if (!in_array($pokemonID, $allowed, true) || !isset($starters[$pokemonID])) {
        $response['question'] = 'Ошибка! Этот покемон сейчас недоступен.';
        $response['answer'] = [900 => "Вернуться к выбору"];
        return;
    }

    // Запрос описания
    $pokemonName = $starters[$pokemonID][0];
    $pokemonNameRus = $starters[$pokemonID][0];
    $pokemonAbout = 'Описание недоступно.';
    if (isset(Work::$sql)) {
        $stmt = Work::$sql->prepare("SELECT name, name_rus, about FROM base_pokemons WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $pokemonID);
            $stmt->execute();
            $pokemonData = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $pokemonName = $pokemonData['name'] ?? $pokemonName;
            $pokemonNameRus = $pokemonData['name_rus'] ?? $pokemonNameRus;
            $pokemonAbout = $pokemonData['about'] ?? $pokemonAbout;
        }
    }

    $pokemonType = $starters[$pokemonID][1];

    $response['question'] = _pokemonCard($pokemonID, $pokemonName, $pokemonNameRus, $pokemonType, $pokemonAbout)
        . "Хочешь выбрать <b>{$pokemonNameRus}</b>?";

    $response['answer'] = [
        $pokemonID + 2000 => "Да, выбираю",
        900 => "Я хочу другого"
    ];
    return;
}

// -------------------------
// Обычные шаги: 0, 900 и т.п.
// -------------------------
switch ($npcStep) {
    default:
        if (quest_step(1, 4)) {
            $response['question'] = 'На земле лежит сумка профессора. Похоже, он уронил её во время нападения.';
            $response['answer'] = [900 => "Открыть сумку."];
            quest_update(1, 4);
            if (function_exists('update_zap')) {
                update_zap(1, 4, 'Я нашёл сумку профессора. Нужно выбрать покемона и защитить профессора.');
            }
        } elseif (quest_step(1, 5)) {
            $response['question'] = 'Ты уже взял покемона из сумки. Теперь нужно помочь профессору в бою.';
        } elseif (quest_step(1, 7, 1)) {
            $response['question'] = 'Сумка напоминает о том, как началось твоё путешествие.';
        }
        break;

    case 900:
        if (quest_step(1, 5)) {
            $response['question'] = 'Ты уже выбрал покемона. Скорее к профессору!';
            break;
        }
        if (quest_step(1, 7, 1)) {
            $response['question'] = 'Сумка напоминает о начале путешествия.';
            break;
        }

        if ($late) {
            $response['question'] = 'В сумке почти пусто… Остался только один покебол. Изнутри слышится упрямое "пи-ка". Выбери покемона!';
        } else {
            $response['question'] = 'В сумке лежат три покебола. Выбери себе стартового покемона!';
        }

        $response['answer'] = [];
        foreach ($allowed as $pid) {
            $title = $starters[$pid][0];
            $response['answer'][$pid + 1000] = "<img src='/img/pokemons/animation/{$pid}.png' style='width: 30px;'> #{$pid} {$title}";
        }
        break;
}
?>

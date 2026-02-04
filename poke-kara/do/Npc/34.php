<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

// Story libs (defensive; do not depend on autoload)
@require_once __DIR__.'/lib/QuestKit.php';
@require_once __DIR__.'/lib/NpcBattle.php';

/**
 * NPC 34 — Дженни / полицейский
 *
 * Storyline NPC for quests:
 *  - 1103 (Officer Jenny / Team Rocket) — existing
 *  - 1106 (deliver message)
 *  - 1107 (catch & train check)
 *  - 1109 (Team Rocket ambush, 2 battles)
 *
 * Legacy quest 11 is preserved after storyline blocks.
 */

$userId = (int)($_SESSION['id'] ?? 0);

function _cntUserPokesStory($mysqli, int $userId): int {
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM `user_pokemons` WHERE `user_id`=".(int)$userId);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['c'] ?? 0);
}

function _maxUserLvlStory($mysqli, int $userId): int {
    $res = $mysqli->query("SELECT MAX(`lvl`) AS m FROM `user_pokemons` WHERE `user_id`=".(int)$userId." AND `active`=1");
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['m'] ?? 0);
}

function _battleEnded($mysqli, int $battleId): bool {
    if ($battleId <= 0) return false;
    $b = $mysqli->query("SELECT `w_end` FROM `battle` WHERE `id`=".(int)$battleId)->fetch_assoc();
    return ($b && !empty($b['w_end']));
}

function _getBattleEnv($mysqli, int $userId): array {
    $env = [
        'arena' => 1,
        'weather' => 1,
        'weather_round' => 10,
        'img' => 0,
        'existing_battle_id' => 0,
    ];

    $u = $mysqli->query("SELECT `location`,`status`,`status_id` FROM `users` WHERE `id`=".(int)$userId)->fetch_assoc();
    if ($u) {
        if (!empty($u['location'])) $env['arena'] = (int)$u['location'];
        if (!empty($u['status']) && $u['status']==='battle' && (int)$u['status_id']>0) {
            $env['existing_battle_id'] = (int)$u['status_id'];
            return $env;
        }
    }

    $loc = $mysqli->query("SELECT `region`,`img_fight`,`weather` FROM `base_location` WHERE `id`=".(int)$env['arena'])->fetch_assoc();
    if ($loc) {
        if (!empty($loc['img_fight'])) $env['img'] = (int)$loc['img_fight'];
        if (!empty($loc['weather'])) {
            $env['weather'] = (int)$loc['weather'];
        } elseif (!empty($loc['region'])) {
            $reg = $mysqli->query("SELECT `weather` FROM `base_region` WHERE `id`=".(int)$loc['region'])->fetch_assoc();
            if ($reg && !empty($reg['weather'])) $env['weather'] = (int)$reg['weather'];
        }
    }

    return $env;
}

// ------------------------------------------------------------
// Story quest 1106 — deliver message from Brock to Jenny
// ------------------------------------------------------------
if (class_exists('QuestKit') && QuestKit::exists(1106) && QuestKit::end(1106) == 0) {
    $response['name'] = 'Офицер Дженни';

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1106, 2)) {
                $response['question'] = 'Вижу, ты от Брока. Что у тебя?';
                $response['answer'] = [
                    11061 => 'Передать записку Брока.'
                ];
            } else {
                $response['question'] = 'Патруль идёт своим чередом. Если что-то случится — сообщи.';
            }
            break;

        case 11061:
            // Complete 1106 and start 1107
            QuestKit::set(1106, 3, 1);
            if (function_exists('update_zap')) {
                update_zap(1106, 3, 'Я передал записку Брока офицеру Дженни.');
            }

            if (!QuestKit::exists(1107)) {
                // Determine difficulty based on 1105 path.
                $path = (string)QuestKit::dataGet(1105, 'path', 'safe_city');
                $need = ($path === 'hard_forest') ? 3 : 2;
                $baseline = _cntUserPokesStory($mysqli, $userId);
                QuestKit::set(1107, 1, 0, null, [
                    'baseline_pokes' => $baseline,
                    'need_catches' => $need,
                    'need_lvl' => 8,
                ]);
                if (function_exists('update_zap')) {
                    update_zap(1107, 1, 'Дженни попросила собрать небольшую команду и потренироваться на маршруте.');
                }
            }

            $response['actionQuest'] = 'Задание <b>Послание Брока</b> выполнено. Доступно новое задание: <b>Патруль: собери команду</b>.';
            $response['question'] = 'Поняла. На маршруте активность подозрительная. Собери небольшую команду и подтяни уровень — затем подойди ко мне снова.';
            break;
    }

    return;
}

// ------------------------------------------------------------
// Story quest 1107 — catch N and reach level threshold
// ------------------------------------------------------------
if (class_exists('QuestKit') && QuestKit::exists(1107) && QuestKit::end(1107) == 0) {
    $response['name'] = 'Офицер Дженни';

    $baseline = (int)QuestKit::dataGet(1107, 'baseline_pokes', 0);
    $need = (int)QuestKit::dataGet(1107, 'need_catches', 2);
    $needLvl = (int)QuestKit::dataGet(1107, 'need_lvl', 8);

    if ($baseline <= 0) {
        $baseline = _cntUserPokesStory($mysqli, $userId);
        QuestKit::set(1107, 1, 0, null, [
            'baseline_pokes' => $baseline,
            'need_catches' => $need,
            'need_lvl' => $needLvl,
        ]);
    }

    $current = _cntUserPokesStory($mysqli, $userId);
    $delta = max(0, $current - $baseline);
    $maxLvl = _maxUserLvlStory($mysqli, $userId);

    $ready = ($delta >= $need) && ($maxLvl >= $needLvl);

    switch ($npcStep) {
        default:
            if ($ready) {
                $response['question'] = 'Отлично. Вижу, ты уже увереннее держишься. Готов двигаться дальше?';
                $response['answer'] = [
                    11071 => 'Да. Докладываю о готовности.'
                ];
            } else {
                $response['question'] = 'Сначала подготовься: поймай ещё <b>'.($need - $delta).'</b> покемона(ов) и подними уровень активной команды до <b>'.$needLvl.'</b> (сейчас максимум: '.$maxLvl.'). Затем возвращайся.';
            }
            break;

        case 11071:
            if (!$ready) {
                $response['question'] = 'Пока рано. Возвращайся после подготовки.';
                break;
            }

            QuestKit::set(1107, 2, 1);
            if (function_exists('update_zap')) {
                update_zap(1107, 2, 'Я подготовил команду на маршруте. Дженни сказала, что дальше будет серьёзнее.');
            }

            // Rewards (light but tangible)
            itemAdd(1, 15000);
            itemAdd(2, 5);
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x15000</b><br><img src="/img/world/items/little/2.png" class="item"> Покебол <b>x5</b><br>';

            // Start 1108
            if (!QuestKit::exists(1108)) {
                QuestKit::set(1108, 1, 0);
                if (function_exists('update_zap')) {
                    update_zap(1108, 1, 'На маршруте появился соперник. Профессор хочет проверить, чему я научился.');
                }
            }

            $response['actionQuest'] = 'Задание <b>Патруль: собери команду</b> выполнено. Доступно новое задание: <b>Соперник: первая встреча</b>.';
            $response['question'] = 'Хорошо. Кстати, профессор просил передать: на маршруте появился тренер, который ищет сильных новичков. Будь готов.';
            break;
    }

    return;
}

// ------------------------------------------------------------
// Story quest 1109 — Team Rocket ambush, 2 battles
// ------------------------------------------------------------
if (class_exists('QuestKit') && QuestKit::exists(1109) && QuestKit::end(1109) == 0) {
    $response['name'] = 'Офицер Дженни';

    $battle1 = (int)QuestKit::dataGet(1109, 'battle1_id', 0);
    $battle2 = (int)QuestKit::dataGet(1109, 'battle2_id', 0);

    $env = _getBattleEnv($mysqli, $userId);

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1109, 1)) {
                $response['question'] = 'Есть новости: на маршруте видели подозрительных людей. Если это Team Rocket — нужно действовать быстро. Поможешь?';
                $response['answer'] = [
                    11091 => 'Да. Я готов.'
                ];
            } elseif (QuestKit::isStep(1109, 2)) {
                if ($battle1 > 0 && !_battleEnded($mysqli, $battle1)) {
                    $response['question'] = 'Сначала закончи бой. Возвращайся после схватки.';
                } else {
                    // Start second battle (or continue if already started)
                    if ($battle2 > 0 && !_battleEnded($mysqli, $battle2)) {
                        $response['question'] = 'Вторая схватка уже идёт. Дожми их.';
                        $response['closeDialog'] = true;
                        $response['battle_id'] = $battle2;
                    } elseif ($battle2 > 0 && _battleEnded($mysqli, $battle2)) {
                        // Safety: if both ended but quest not closed
                        QuestKit::set(1109, 4, 1);
                        if (function_exists('update_zap')) update_zap(1109, 4, 'Я отбил атаку Team Rocket.');
                        if (!QuestKit::exists(1110)) {
                            QuestKit::set(1110, 1, 0);
                            if (function_exists('update_zap')) update_zap(1110, 1, 'После инцидента Дженни просит обсудить дальнейший путь с Броком.');
                        }
                        $response['actionQuest'] = 'Задание <b>Засада Team Rocket</b> выполнено.';
                        $response['question'] = 'Отличная работа. Теперь поговори с Броком — он подскажет, куда двигаться дальше.';
                    } else {
                        // Start battle #2 now
                        if ($env['existing_battle_id'] > 0) {
                            $response['question'] = 'Сначала закончи текущий бой.';
                            $response['closeDialog'] = true;
                            $response['battle_id'] = (int)$env['existing_battle_id'];
                            break;
                        }

                        $enemy2 = [
                            ['basenum' => 52, 'lvl' => 10, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Meowth
                            ['basenum' => 19, 'lvl' => 8,  'numb' => 0, 'boss' => 0, 'catch' => 0], // Rattata
                            ['basenum' => 41, 'lvl' => 8,  'numb' => 0, 'boss' => 0, 'catch' => 0], // Zubat
                        ];

                        $newBattle = 0;
                        if (class_exists('NpcBattle')) {
                            $newBattle = NpcBattle::start($enemy2, [
                                'battle_type' => 'npc',
                                'weather' => (int)$env['weather'],
                                'weather_round' => (int)$env['weather_round'],
                                'img' => (int)$env['img'],
                                'arena' => (int)$env['arena'],
                                'logText' => 'Team Rocket не сдаётся!<br>'
                            ]);
                        }

                        QuestKit::set(1109, 3, 0, null, ['battle2_id' => $newBattle, 'battle1_id' => $battle1]);
                        $response['closeDialog'] = true;
                        if ($newBattle > 0) {
                            $response['question'] = 'Они зовут подкрепление. Второй бой начинается!';
                            $response['battle_id'] = $newBattle;
                        } else {
                            $response['question'] = 'Подкрепление Team Rocket рядом. Будь начеку!';
                        }
                    }
                }
            } elseif (QuestKit::isStep(1109, 3)) {
                if ($battle2 > 0 && !_battleEnded($mysqli, $battle2)) {
                    $response['question'] = 'Сначала закончи второй бой. Возвращайся после победы.';
                } else {
                    QuestKit::set(1109, 4, 1);
                    if (function_exists('update_zap')) update_zap(1109, 4, 'Я отбил атаку Team Rocket.');

                    // Reward
                    itemAdd(1, 30000);
                    itemAdd(3, 3);
                    $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x30000</b><br><img src="/img/world/items/little/3.png" class="item"> Грейтбол <b>x3</b><br>';

                    if (!QuestKit::exists(1110)) {
                        QuestKit::set(1110, 1, 0);
                        if (function_exists('update_zap')) update_zap(1110, 1, 'После инцидента Дженни просит обсудить дальнейший путь с Броком.');
                    }

                    $response['actionQuest'] = 'Задание <b>Засада Team Rocket</b> выполнено. Доступно новое задание: <b>Новый маршрут</b>.';
                    $response['question'] = 'Спасибо. Дальше будет сложнее — Team Rocket не забывает поражений. Поговори с Броком о следующем маршруте.';
                }
            } else {
                $response['question'] = 'Патруль продолжается.';
            }
            break;

        case 11091:
            if ($env['existing_battle_id'] > 0) {
                $response['question'] = 'Ты уже в бою. Сначала заверши его.';
                $response['closeDialog'] = true;
                $response['battle_id'] = (int)$env['existing_battle_id'];
                break;
            }

            $enemy1 = [
                ['basenum' => 23, 'lvl' => 9, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Ekans
                ['basenum' => 109,'lvl' => 9, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Koffing
            ];

            $newBattle = 0;
            if (class_exists('NpcBattle')) {
                $newBattle = NpcBattle::start($enemy1, [
                    'battle_type' => 'npc',
                    'weather' => (int)$env['weather'],
                    'weather_round' => (int)$env['weather_round'],
                    'img' => (int)$env['img'],
                    'arena' => (int)$env['arena'],
                    'logText' => 'Team Rocket атакует!<br>'
                ]);
            }

            QuestKit::set(1109, 2, 0, null, ['battle1_id' => $newBattle, 'battle2_id' => 0]);

            $response['closeDialog'] = true;
            if ($newBattle > 0) {
                $response['question'] = 'Засада! Бой начинается!';
                $response['battle_id'] = $newBattle;
            } else {
                $response['question'] = 'Засада! Приготовься к схватке.';
            }
            break;
    }

    return;
}

// ------------------------------------------------------------
// Story quest 1103 — existing implementation (kept)
// ------------------------------------------------------------
if (class_exists('QuestKit') && QuestKit::exists(1103) && QuestKit::end(1103) == 0) {
    $response['name'] = 'Офицер Дженни';

    $battleId = (int)QuestKit::dataGet(1103, 'battle_id', 0);

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1103, 1)) {
                $response['question'] = 'Ты выглядишь как начинающий тренер. У нас тут проблема: Team Rocket кружит рядом. Поможешь проверить подозрительного человека?';
                $response['answer'] = [
                    11031 => 'Да, я помогу.',
                    11032 => 'Сейчас не могу.'
                ];
            } elseif (QuestKit::isStep(1103, 2)) {
                // Проверяем завершён ли бой
                $done = false;
                if ($battleId > 0) {
                    $b = $mysqli->query("SELECT `w_end` FROM `battle` WHERE `id`=".(int)$battleId)->fetch_assoc();
                    $done = ($b && !empty($b['w_end']));
                }

                if ($done) {
                    QuestKit::set(1103, 3, 1);
                    if (function_exists('update_zap')) {
                        update_zap(1103, 3, 'Мы отбились от Team Rocket. Дженни сказала, что пора идти к первому стадиону.');
                    }

                    // награды
                    itemAdd(1, 20000);
                    itemAdd(2, 10);

                    $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x20000</b><br><img src="/img/world/items/little/2.png" class="item"> Покебол <b>x10</b><br>';
                    $response['actionQuest'] = 'Задание <b>Офицер Дженни / Team Rocket</b> выполнено. Доступно новое задание: <b>Первый стадион</b>.';

                    // запускаем 1104
                    if (!QuestKit::exists(1104)) {
                        QuestKit::set(1104, 1, 0);
                        if (function_exists('update_zap')) {
                            update_zap(1104, 1, 'Пора к первому стадиону. Найди лидера и пройди испытание.');
                        }
                    }

                    $response['question'] = 'Отличная работа. Теперь твой путь ведёт к первому стадиону. Удачи, тренер!';
                } else {
                    $response['question'] = 'Сначала закончи бой. Возвращайся после схватки.';
                }
            } else {
                $response['question'] = 'Мы следим за порядком.';
            }
            break;

        case 11031:
            // старт NPC боя
            $enemy = [
                ['basenum' => 19, 'lvl' => 6, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Rattata
                ['basenum' => 23, 'lvl' => 6, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Ekans
            ];

            $arena = 1;
            $uLoc = $mysqli->query("SELECT `location` FROM `users` WHERE `id`=".(int)$userId)->fetch_assoc();
            if ($uLoc && isset($uLoc['location'])) $arena = (int)$uLoc['location'];

            $battleId = 0;
            if (class_exists('NpcBattle')) {
                $battleId = NpcBattle::start($enemy, [
                    'battle_type' => 'npc',
                    'weather' => 1,
                    'weather_round' => 10,
                    'img' => 0,
                    'arena' => $arena,
                    'logText' => 'Team Rocket появляется из тени!<br>'
                ]);
            }

            QuestKit::set(1103, 2, 0, null, ['battle_id' => $battleId]);

            $response['closeDialog'] = true;
            if ($battleId > 0) {
                $response['question'] = 'Бой начинается!';
                $response['battle_id'] = $battleId;
            } else {
                $response['question'] = 'Начинаем.';
            }
            break;

        case 11032:
            $response['question'] = 'Понимаю. Если передумаешь — возвращайся.';
            break;
    }

    return;
}

// ------------------------------------------------------------
// Legacy quest 11 (unchanged)
// ------------------------------------------------------------

$response['name'] = 'Джейсон Стентон';
$response['image'] = '/img/pers/npc/39.png';

switch($npcStep){

	case 1:
		if(!quest_isset(11)){
			$response['question'] = 'Подождите секунду, можете мне помочь?';
			$response['answer'] = array(
				2 => "Да, конечно.",
				3 => "Извини, но нет."
			);
		}
	break;
	case 2:
		if(!quest_isset(11)){
			$response['question'] = 'Я тут прогуливался как обычно и заметил, что мой кетчуп упал в траву, а там живут покемоны. Не могли бы вы мне его достать?';
			$response['answer'] = array(
				4 => "Возьмусь за это!",
				3 => "У меня нет времени, извини."
			);
		}
	break;
	case 4:
		if(!quest_isset(11)){
			$response['question'] = 'Замечательно, буду ждать тебя тут!';
			quest_update(11, 1);
			$response['actionQuest'] = 'Информация обновлена в Дневнике.';
			update_zap(11, 1, 'Я решил помочь тренеру. Нужно найти его кетчуп.');
		}
	break;
	case 5:
		if(quest_step(11, 1) && item_isset(57, 1)){
			$response['question'] = 'Спасибо большое! Вот награда за твою помощь!';
			$response['actionQuest'] = 'Задание выполнено! Информация обновлена в Дневнике.';
			quest_update(11, 2, 1);
			update_zap(11, 2, 'Я нашёл кетчуп и вернул его тренеру.');
			minus_item(57, 1);
			itemAdd(1, 5000);
			itemAdd(2, 2);
			$response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x5000</b><br><img src="/img/world/items/little/2.png" class="item"> Покебол <b>x2</b><br>';
		}
	break;
	default:
		if(!quest_isset(11)){
			$response['question'] = 'Привет!';
			$response['answer'] = array(
				1 => "Здравствуйте."
			);
		}else if(quest_step(11, 1)){
			$response['question'] = 'Вы нашли кетчуп?';
			$response['answer'] = array(
				5 => "Да."
			);
		}else{
			$response['question'] = 'Отличная сегодня погода, не правда ли?';
		}
	break;
}
?>

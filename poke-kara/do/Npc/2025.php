<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

// Получение названия предмета по ID из базы данных
function getItemDetails($itemId) {
    $stmt = Work::$sql->prepare("SELECT name FROM base_items WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        return $item['name'];
    } else {
        return "Неизвестный предмет (ID: $itemId)";
    }
}

$response['name'] = 'Профессор Обберта';

switch ($npcStep) {
    case 1: // Ознакомление и информация
        $response['question'] = 'На всех локациях мира появились дикие змеи. Добывай из них частички кожи до 23:59 03.01.2024. После этого приноси их мне, и я тебя награжу!';
        $response['answer'] = [
            2 => "Я понял, приступаю!"
        ];
        break;

    case 2: // Сдача предметов
        if (item_isset(562, 1)) { // Проверяем наличие хотя бы одной частички
            $response['question'] = "У тебя есть частички кожи. Хочешь обменять их на награды?";
            $response['answer'] = [
                3 => "Да, сдаю!",
                4 => "Нет, ещё соберу."
            ];
        } else {
            $response['question'] = 'У тебя нет частичек кожи для обмена!';
        }
        break;

    case 3: // Получение награды и списание
        $rewardTier = 0;

        // Определяем уровень награды по количеству предметов
        if (item_isset(562, 150)) {
            $rewardTier = 5; // Супернаграда
        } elseif (item_isset(562, 100)) {
            $rewardTier = 4; // Высшая награда
        } elseif (item_isset(562, 75)) {
            $rewardTier = 3;
        } elseif (item_isset(562, 50)) {
            $rewardTier = 2;
        } elseif (item_isset(562, 20)) {
            $rewardTier = 1;
        }

        if ($rewardTier > 0) {
            $rewardsDetails = '';
            $chance = mt_rand(1, 100); // Определяем случайный шанс

            switch ($rewardTier) {
                case 5:
                    minus_item(562, 150);
                    $item1 = mt_rand(15, 25);
                    $item2 = mt_rand(3000000, 5000000);
                    $item3 = mt_rand(10, 15);
                    $item267 = mt_rand(3, 5);
                    $item31 = mt_rand(25, 50);
                    itemAdd(197, $item1);
                    itemAdd(1, $item2);
                    itemAdd(53, $item3);
                    itemAdd(267, $item267);
                    itemAdd(31, $item31);

                    // Покемоны для супернаграды
                    if ($chance <= 33) {
                        NewPokemon(609, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/609.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #609 (Chandelure, Shiny)</li>';
                    } elseif ($chance <= 66) {
                        NewPokemon(635, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/635.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #635 (Hydreigon, Shiny)</li>';
                    } else {
                        NewPokemon(612, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/612.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #612 (Haxorus, Shiny)</li>';
                    }

                    $rewardsDetails = '<ul><li><img src="/img/world/items/little/197.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(197) . " x{$item1}</li>" .
                                      '<li><img src="/img/world/items/little/1.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(1) . " x{$item2}</li>" .
                                      '<li><img src="/img/world/items/little/53.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(53) . " x{$item3}</li>" .
                                      '<li><img src="/img/world/items/little/267.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(267) . " x{$item267}</li>" .
                                      '<li><img src="/img/world/items/little/31.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(31) . " x{$item31}</li>" .
                                      $rewardsDetails . '</ul>';
                    break;

                case 4:
                    minus_item(562, 100);
                    $item1 = mt_rand(10, 20);
                    $item2 = mt_rand(2000000, 3500000);
                    $item3 = mt_rand(8, 12);
                    $item267 = mt_rand(2, 4);
                    $item31 = mt_rand(15, 40);
                    itemAdd(197, $item1);
                    itemAdd(1, $item2);
                    itemAdd(53, $item3);
                    itemAdd(267, $item267);
                    itemAdd(31, $item31);

                    // Покемоны для высокой награды
                    if ($chance <= 50) {
                        NewPokemon(628, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/628.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #628 (Braviary, Shiny)</li>';
                    } else {
                        NewPokemon(630, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/630.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #630 (Mandibuzz, Shiny)</li>';
                    }

                    $rewardsDetails = '<ul><li><img src="/img/world/items/little/197.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(197) . " x{$item1}</li>" .
                                      '<li><img src="/img/world/items/little/1.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(1) . " x{$item2}</li>" .
                                      '<li><img src="/img/world/items/little/53.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(53) . " x{$item3}</li>" .
                                      '<li><img src="/img/world/items/little/267.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(267) . " x{$item267}</li>" .
                                      '<li><img src="/img/world/items/little/31.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(31) . " x{$item31}</li>" .
                                      $rewardsDetails . '</ul>';
                    break;

                case 3:
                    minus_item(562, 75);
                    $item1 = mt_rand(8, 15);
                    $item2 = mt_rand(1500000, 2500000);
                    $item3 = mt_rand(6, 10);
                    itemAdd(197, $item1);
                    itemAdd(1, $item2);
                    itemAdd(53, $item3);

                    // Покемоны для средней награды
                    if ($chance <= 50) {
                        NewPokemon(556, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/556.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #556 (Maractus, Shiny)</li>';
                    } else {
                        NewPokemon(613, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/613.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #613 (Emolga, Shiny)</li>';
                    }

                    $rewardsDetails = '<ul><li><img src="/img/world/items/little/197.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(197) . " x{$item1}</li>" .
                                      '<li><img src="/img/world/items/little/1.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(1) . " x{$item2}</li>" .
                                      '<li><img src="/img/world/items/little/53.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(53) . " x{$item3}</li>" .
                                      $rewardsDetails . '</ul>';
                    break;

                case 2:
                    minus_item(562, 50);
                    $item1 = mt_rand(5, 10);
                    $item2 = mt_rand(1000000, 1500000);
                    $item3 = mt_rand(3, 6);
                    itemAdd(197, $item1);
                    itemAdd(1, $item2);
                    itemAdd(53, $item3);

                    // Покемоны для пониженной награды
                    if ($chance <= 50) {
                        NewPokemon(551, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/551.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #551 (Sandile, Shiny)</li>';
                    } else {
                        NewPokemon(657, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                        $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/657.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #657 (Fletchinder, Shiny)</li>';
                    }

                    $rewardsDetails = '<ul><li><img src="/img/world/items/little/197.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(197) . " x{$item1}</li>" .
                                      '<li><img src="/img/world/items/little/1.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(1) . " x{$item2}</li>" .
                                      '<li><img src="/img/world/items/little/53.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(53) . " x{$item3}</li>" .
                                      $rewardsDetails . '</ul>';
                    break;

                case 1:
                    minus_item(562, 20);
                    $item1 = mt_rand(3, 5);
                    $item2 = mt_rand(500000, 1000000);
                    itemAdd(197, $item1);
                    itemAdd(1, $item2);

                    // Покемоны для низкой награды
                    NewPokemon(667, $_SESSION['id'], 1, 30, 0, 'true', 30, false, 0, false, true, true);
                    $rewardsDetails .= '<li><img src="/img/pokemons/anim/normal/667.gif" class="item" style="width: 40px; height: 40px;"> Покемон: #667 (Litleo, Shiny)</li>';

                    $rewardsDetails = '<ul><li><img src="/img/world/items/little/197.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(197) . " x{$item1}</li>" .
                                      '<li><img src="/img/world/items/little/1.png" class="item" style="width: 40px; height: 40px;"> ' . getItemDetails(1) . " x{$item2}</li>" .
                                      $rewardsDetails . '</ul>';
                    break;
            }

            $response['question'] = 'Ты успешно сдал частички кожи и получил награду!';
            $response['actionQuest'] = "notify";
            $response['actionText'] = '<img src="/img/quests/4.png" class="quest"> Обновлена информация в задании «Охота на змей»';
            $response['actionQuestPlus'] = 'Ты получил: ' . $rewardsDetails;
            $response['actionQuestMinus'] = 'Списано: частички кожи';
        } else {
            $response['question'] = 'У тебя недостаточно частичек кожи для обмена!';
        }
        break;

    case 4:
        $response['question'] = 'Хорошо, возвращайся, когда будешь готов.';
        break;

    default:
        $response['question'] = 'Приветствую тебя, '.$_SESSION['login'].'! Собирай частички до 23:59 03.01.2024';
        $response['answer'] = [
            2 => "Хочу сдать частички кожи!"
        ];
        break;
}
?>

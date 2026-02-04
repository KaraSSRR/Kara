<?
$response['name'] = 'Куратор колизея';

// Получаем текущую заявку пользователя
$bd = $mysqli->query('SELECT * FROM `arena` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();

// Удаляем старые заявки
cleanUpOldArenaRequests();

switch ($npcStep) {
    case 1:
        $response['question'] = 'Добро пожаловать в коллизей. Я руковожу битвами здесь!';
        $response['answer'] = array(
            2 => "Что это за место?",
            3 => "Купить 35 жетонов(20.000 монет)",
            4 => "Где я могу обменять жетоны?",
            5 => "Отправить заявку",
            7 => "У меня закончились жетоны"
        );
        break;
    case 2:
        $response['question'] = 'Здесь происходят соревнования силы и защиты чести! Чтобы участвовать, нужно иметь жетоны. Раз в неделю можно купить жетоны. Если готов, подавай заявку!';
        $response['answer'] = array(
            1 => "У меня другой вопрос"
        );
        break;
    case 3:
        if (item_isset(1, 20000)) {
            $us = $mysqli->query("SELECT `id`,`arena` FROM `users` WHERE `id` = ".$_SESSION['id']." ")->fetch_assoc();
            $l = explode(',', $us['arena']);
            if ($l[0] == 0) {
                $l[0] = 1;
                $lupd = implode(',', $l);
                itemAdd(480, 35);
                minus_item(1, 20000);
                $response['actionQuestPlus'] = '<img src="/img/world/items/little/480.png" class="item"> Жетон колизея <b>x35</b>';
                $response['actionQuestMinus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x20.000</b>';
                $mysqli->query('UPDATE `users` SET `arena` = "'.$lupd.'" WHERE `id` = "'.$_SESSION['id'].'" ');
                $response['question'] = 'Удачи в сражениях, тренер!';
                $response['answer'] = array(
                    1 => "У меня другой вопрос"
                );
            } else {
                $response['question'] = 'На этой неделе вы уже покупали жетоны!';
                $response['answer'] = array(
                    1 => "У меня другой вопрос"
                );
            }
        } else {
            $response['question'] = 'У тебя недостаточно монет!';
            $response['answer'] = array(
                1 => "У меня другой вопрос"
            );
        }
        break;
    case 4:
        $response['question'] = 'Обменять жетоны можно у Дженны!';
        $response['answer'] = array(
            1 => "У меня другой вопрос"
        );
        break;
   case 5:
    if (item_isset(480, 1)) {
        // Ищем соперника
        $opponent = findOpponent($_SESSION['id']);
        if ($opponent) {
            // Если найден соперник, начинаем битву
            $battleId = startBattle($_SESSION['id'], $opponent);

            // Удаляем заявки
            Work::$sql->query("DELETE FROM `arena` WHERE `user` IN (" . $_SESSION['id'] . ", " . $opponent . ")");

            // Выдача покемонов обоим участникам
            giveArenaPokemonsToUser($_SESSION['id']);
            giveArenaPokemonsToUser($opponent);

            $response['question'] = 'Битва началась! Удачи!';
            $response['actionBattle'] = "arena_battle?battleId={$battleId}";
        } else {
            // Если соперника нет, добавляем заявку
            Work::$sql->query("INSERT INTO `arena` (`user`, `time`) VALUES ('" . $_SESSION['id'] . "', '" . time() . "')");

            // Выдача покемонов пользователю
            giveArenaPokemonsToUser($_SESSION['id']);

            $response['question'] = 'Заявка отправлена! Ожидайте соперника.';
        }

        $response['answer'] = array(
            6 => "Отменить заявку"
        );
    } else {
        $response['question'] = 'У тебя нет жетонов для участия!';
        $response['answer'] = array(
            1 => "У меня другой вопрос"
        );
    }
    break;

case 6:
    $response['question'] = "Заявка отменена! Приходи к нам еще!";
    $response['answer'] = array(
        1 => "У меня другой вопрос"
    );
    Work::$sql->query("DELETE FROM `arena` WHERE `user` = '" . $_SESSION['id'] . "'");
    break;


    default:
        if ($bd) {
            $response['question'] = 'Ваша заявка ждет соперника!';
            $response['answer'] = array(
                6 => "Отменить заявку"
            );
        } else {
            $response['question'] = 'Добро пожаловать в коллизей!';
            $response['answer'] = array(
                2 => "Что это за место?",
                3 => "Купить 35 жетонов(20.000 монет)",
                4 => "Где я могу обменять жетоны?",
                5 => "Отправить заявку",
                7 => "У меня закончились жетоны"
            );
        }
        break;
}
?>

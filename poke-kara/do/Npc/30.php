<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

// ID текущего NPC (можно менять в зависимости от ситуации)
$npcId = 30;

// Запрос к базе данных для получения информации о NPC
$query = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = $npcId");
$npc = $query->fetch_assoc();

// Проверяем, найден ли NPC
if ($npc) {
    $response['name'] = $npc['name'];
    $response['image'] = htmlspecialchars($npc['image']); // Путь к изображению NPC
} else {
    $response['name'] = 'Неизвестный NPC';
    $response['image'] = '/img/default-npc.png'; // Запасное изображение
}

switch($npcStep){
    case 1:
        $response['question'] = $npcImage . 'Тебе нужно будет отправиться в <b>Шахту</b> и найти там 
        <div class="itemIsset" onclick="issetAll(394,\'item\')" style="background-image: url(/img/world/items/little/394.png)"></div> 
        Загадочный камень х10. Принесешь мне их, и я вознагражу тебя. Но правда есть одно НО, 
        для прохода в шахту нужен 
        <div class="itemIsset" onclick="issetAll(9,\'item\')" style="background-image: url(/img/world/items/little/9.png)"></div> 
        Набор шахтера. Я могу продать тебе его за 250.000 монет, купишь?';
        
        $response['answer'] = array(
            2 => "Да, я куплю"
        );
    break;

    case 2:
        if(item_isset(1,250000)){
            quest_update(7,1);
            update_zap(7,1,'Один из исследователей в Люмиусе рассказал о том, что им нужны 
            <div class="itemIsset" onclick="issetAll(394)" style="background-image: url(/img/world/items/little/394.png)"></div> 
            Загадочные камни х10 которые можно добыть в Шахте. Но туда можно пройти только с 
            <div class="itemIsset" onclick="issetAll(9)" style="background-image: url(/img/world/items/little/9.png)"></div> 
            Набором шахтера, который я купил у Исследователя.');

            minus_item(1,250000);
            itemAdd(9,1);
            
            $response['question'] = $npcImage . 'Держи. Удачи тебе в поисках камней!';
            $response['actionQuest'] = 'Обновлена информация в квесте <b>Археологические исследования</b>. Загляните в Дневник.';
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/9.png" class="item"> Набор шахтера <b>x1</b><br>';
            $response['actionQuestMinus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x250.000</b><br>';
        } else {
            $response['question'] = $npcImage . 'У тебя недостаточно монет!';
        }
    break;

    case 3:
        if(item_isset(394,10)){
            $response['question'] = $npcImage . 'Ооо, это и правда они! Спасибо тебе за помощь, держи 
            <div class="itemIsset" onclick="issetAll(195)" style="background-image: url(/img/world/items/little/195.png)"></div> 
            Кейс с окаменелостями. Через месяц можешь подойти ко мне еще раз, я предложу тебе работу еще раз.';
            
            update_zap(7,2,'Я принес исследователю все камни и он отблагодарил меня. Так же он сказал, 
            что каждый месяц можно будет к нему подходить и он будет предлагать работу еще раз.');
            
            $t = time()+3600*24*30;
            quest_update(7,2);
            minus_item(394,10);
            itemAdd(195,1);
            lvlupuser(500);
            
            $response['actionQuest'] = 'Обновлена информация в квесте <b>Археологические исследования</b>. Загляните в Дневник.';
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/195.png" class="item"> Коробка с окаменелостями <b>x1</b><br>';
            $response['actionQuestMinus'] = '<img src="/img/world/items/little/394.png" class="item"> Загадочные камни <b>x10</b><br>';
            Work::$sql->query("UPDATE `user_quests` SET `time` = '".$t."' WHERE  `quest_id` = 7 AND `user_id` = '".$_SESSION['id']."'");
        } else {
            $response['question'] = $npcImage . 'У тебя нет всех камней';
        }
    break;

    default:
        $q = $mysqli->query("SELECT * FROM `user_quests` WHERE `quest_id` = 7 AND `user_id` = ".$_SESSION['id'])->fetch_assoc();
        if(quest_step(7,1) and !$q['time']){
            $response['question'] = $npcImage . 'Ты уже вернулся? Ты нашел все камни?';
            $response['answer'] = array(
                3 => "Да, я нашел все камни"
            );
        } elseif(quest_step(7,2) and $q['time'] <= time()){
            $response['question'] = $npcImage . 'Привет, ты сможешь снова мне помочь?';
            $response['answer'] = array(
                4 => "Да, конечно"
            );
        } elseif(quest_step(7,2) and $q['time'] > time()){
            $response['question'] = $npcImage . 'Привет, исследования тех камней, что ты мне принес прошли успешно.';
        } elseif(quest_step(7,1) and $q['time']){
            $response['question'] = $npcImage . 'Ты уже вернулся? Ты нашел все камни?';
            $response['answer'] = array(
                6 => "Да, конечно"
            );
        } else {
            $response['question'] = $npcImage . 'Привет, '.$_SESSION['login'].'! Я один из исследователей земной породы. 
            Мы ищем и изучаем разные минералы, загадочные камни. Я готов предложить тебе работу.';
            $response['answer'] = array(
                1 => "Что от меня требуется?"
            );
        }
    break;
}
?>

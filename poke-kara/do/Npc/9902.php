<?php
// Event NPC script. Filename must match NPC id if your engine loads /npc/{id}.php.
// Requires Functions.php (and Work::$sql) to be available.
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
$userId = (int)($_SESSION['id'] ?? 0);
$response = ['question'=>'','answer'=>[]];

if (!function_exists('newyear_is_active')) {
    @require_once($_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php');
}

$active = function_exists('newyear_is_active') ? newyear_is_active() : false;
$window = function_exists('newyear_calendar_window_active') ? newyear_calendar_window_active() : true;

if ($userId <= 0){
    $response['question'] = 'Сессия не найдена.';
    return;
}

if (!$active || !$window){
    $response['question'] = 'Ивент сейчас недоступен.';
    $response['answer'] = [0 => 'Понятно'];
    return;
}

newyear_ensure_tables();
newyear_ensure_items();

// $npcStep is provided by the NPC router
$npcStep = (int)($npcStep ?? 0);

function ny_item_count_local(int $userId, int $itemId): int {
    $q = Work::$sql->query("SELECT SUM(`count`) AS c FROM `items_users` WHERE `user`={$userId} AND `item_id`={$itemId}");
    $r = $q ? $q->fetch_assoc() : null;
    return (int)($r['c'] ?? 0);
}

// NPC 9902: "Снеговик-купец" — обмен снежинок
$snow = ny_item_count_local($userId, 1200);

switch ($npcStep) {
    default:
        $response['question'] =
            "Хо-хо! У тебя сейчас снежинок: {$snow}.\n".
            "Выбирай обмен:";
        $response['answer'] = [
            10 => "10 снежинок → Подарок ×1",
            20 => "30 снежинок → Фейерверк ×1",
            0  => "Назад",
        ];
        break;

    case 10:
        if ($snow < 10) {
            $response['question'] = "Не хватает снежинок (нужно 10).";
            $response['answer'] = [0 => "Назад"];
            break;
        }
        Work::$sql->query("UPDATE `items_users` SET `count` = `count` - 10 WHERE `user`={$userId} AND `item_id`=1200 AND `count` >= 10 LIMIT 1");
        itemAdd(1201, 1, $userId);
        newyear_event_add($userId, 'gift_send', 1);
        $response['question'] = "Обмен успешен: Подарок ×1. (И вклад за подарок тоже засчитан.)";
        $response['answer'] = [0 => "Назад"];
        break;

    case 20:
        if ($snow < 30) {
            $response['question'] = "Не хватает снежинок (нужно 30).";
            $response['answer'] = [0 => "Назад"];
            break;
        }
        Work::$sql->query("UPDATE `items_users` SET `count` = `count` - 30 WHERE `user`={$userId} AND `item_id`=1200 AND `count` >= 30 LIMIT 1");
        itemAdd(1202, 1, $userId);
        newyear_event_add($userId, 'gift_send', 1);
        $response['question'] = "Обмен успешен: Фейерверк ×1.";
        $response['answer'] = [0 => "Назад"];
        break;
}
?>

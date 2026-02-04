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

// NPC 9901: "Глашатай праздника" — вводный квест/инфо
switch ($npcStep) {
    default:
        $response['question'] =
            "С Новым годом! Мы запускаем игру и параллельно поднимаем общую Ёлку сервера.\n".
            "Выполняй активности, собирай снежинки и забирай тир-награды.\n\n".
            "Что сделать?";
        $response['answer'] = [
            10 => "Показать цели и правила",
            20 => "Встряхнуть ёлку (ежедневно)",
            30 => "Перейти к лавке снеговика",
        ];
        break;

    case 10:
        $response['question'] =
            "Правила:\n".
            "• Победы в PVE дают вклад и снежинки.\n".
            "• Раз в день можно встряхнуть ёлку.\n".
            "• За общий прогресс открываются тир-награды.\n\n".
            "Хочешь сразу встряхнуть ёлку?";
        $response['answer'] = [
            20 => "Да, встряхнуть",
            0  => "Назад",
        ];
        break;

    case 20:
        $res = newyear_tree_free_action($userId);
        if (!empty($res['ok'])) {
            Work::$sql->query("UPDATE `a_ivent_newyear_users` SET `daily_claims` = `daily_claims` + 1 WHERE `user_id`={$userId} LIMIT 1");
            $response['question'] = "Вы встряхнули ёлку и получили снежинки ×".(int)($res['snow'] ?? 0)."!";
        } else {
            $response['question'] = (string)($res['msg'] ?? 'Не удалось встряхнуть ёлку.');
        }
        $response['answer'] = [
            0  => "Назад",
            30 => "К снеговику",
        ];
        break;

    case 30:
        $response['nav'] = [
            'route' => [
                ['type' => 'location', 'slug' => 'alabastia'],
                ['type' => 'npc',      'slug' => 'npc_9902'] // если у вас есть slug-система; иначе уберите
            ]
        ];
        $response['question'] = "Иди к Снеговику-купцу: он меняет снежинки на подарки.";
        $response['answer'] = [0 => "Хорошо"];
        break;
}
?>

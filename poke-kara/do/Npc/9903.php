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

// NPC 9903: "Ёлка" — просмотр прогресса сервера (только текстово)
$srv = Work::$sql->query("SELECT * FROM `a_ivent_newyear_server` WHERE `id`=1 LIMIT 1")->fetch_assoc();
$usr = Work::$sql->query("SELECT * FROM `a_ivent_newyear_users` WHERE `user_id`={$userId} LIMIT 1")->fetch_assoc();
$progress = (int)($srv['progress'] ?? 0);
$target   = (int)($srv['target'] ?? 10000);
$tier     = (int)($srv['tier'] ?? 1);
$contrib  = (int)($usr['contrib'] ?? 0);
$snow     = ny_item_count_local($userId, 1200);

switch ($npcStep) {
    default:
        $pct = ($target > 0) ? (int)round(($progress / $target) * 100) : 0;
        $response['question'] =
            "🎄 Ёлка сервера\n".
            "Прогресс: {$progress} / {$target} ({$pct}%)\n".
            "Текущий тир: {$tier}\n\n".
            "Твой вклад: {$contrib}\n".
            "Твои снежинки: {$snow}\n\n".
            "Хочешь встряхнуть ёлку (ежедневно)?";
        $response['answer'] = [
            10 => "Встряхнуть",
            0  => "Назад",
        ];
        break;

    case 10:
        $res = newyear_tree_free_action($userId);
        if (!empty($res['ok'])) {
            Work::$sql->query("UPDATE `a_ivent_newyear_users` SET `daily_claims` = `daily_claims` + 1 WHERE `user_id`={$userId} LIMIT 1");
            $response['question'] = "Вы получили снежинки ×".(int)($res['snow'] ?? 0)."!";
        } else {
            $response['question'] = (string)($res['msg'] ?? 'Не удалось.');
        }
        $response['answer'] = [0 => "Назад"];
        break;
}
?>

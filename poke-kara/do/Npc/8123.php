<?php
// npc/quest120/tech.php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * Техник эфирной связи — калибрует камертон после сбора символов.
 */
$response['name'] = 'Техник эфирной связи';

if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }
$uid = (int)$_SESSION['id'];
global $mysqli;

const QUEST_ID = 120;

/* ===== helpers ===== */
function q120_get(mysqli $db, int $uid): ?array {
    $row = $db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1")->fetch_assoc();
    if (!$row) return null;
    $row['step'] = (int)$row['step'];
    $row['end']  = (int)($row['end'] ?? 0);
    $row['data'] = json_decode($row['data'] ?? '[]', true) ?: [];
    return $row;
}
function q120_save(mysqli $db, int $uid, int $step, array $data, int $end = 0): void {
    $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    $db->query("UPDATE `user_quests` SET `step`={$step}, `end`={$end}, `data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1");
}

/* ===== state ===== */
$me   = q120_get($mysqli, $uid);
$step = isset($npcStep) ? (int)$npcStep : 0;

if (!$me) {
    $response['question'] = 'Подстройка эфира доступна тем, кто уже слышал шёпот пути. Загляните к проводнику на перроне.';
    return;
}

/* Квест полностью закрыт — обычное приветствие технике */
if ((int)$me['end'] === 1) {
    $response['question'] = 'Камертон звенит ровно. Работа выполнена на славу.';
    return;
}

/* Данные прогресса */
$data = $me['data'];
$echo = (int)($data['echo'] ?? 0);
$need = (int)($data['need_echo'] ?? 0);         // цель задаётся у Проводника (динамически 5–8)
if ($need <= 0) { $need = 5; }                  // безопасный минимум
$calibrated = !empty($data['calibrated']);      // флаг калибровки у техника

/* Логика шагов */
switch ($step) {

    case 301: // калибровка по кнопке
        // Разрешаем ТОЛЬКО если у Проводника «пакет собран» (step >= 2) и эха достаточно
        if ((int)$me['step'] < 2 || $echo < $need) {
            $response['question'] = 'Символов недостаточно. Нужен собранный пакет: '.$need.' шт. Обратитесь к Проводнику во время поездки.';
            $response['answer']   = [];
            break;
        }
        // Повторная калибровка не нужна
        if ($calibrated) {
            $response['question'] = 'Камертон уже откалиброван. Вас ждут на перроне Канто (27) у дежурного.';
            $response['answer']   = [];
            break;
        }

        // Отмечаем калибровку и переводим к следующему шагу
        $data['calibrated'] = 1;
        q120_save($mysqli, $uid, 3, $data, 0);

        $response['question'] = 'Готово! Камертон звучит чисто. Возвращайтесь на перрон Канто (27) к дежурному — он всё оформит.';
        $response['answer']   = [ 0 => 'Принято' ];
        break;

    default:
        // Ещё не собран пакет у Проводника
        if ((int)$me['step'] < 2) {
            $response['question'] = 'Принесу частоты, как только вы соберёте достаточно эховых символов в дороге. Поговорите с Проводником.';
            $response['answer']   = [];
            break;
        }

        // Пакет собран, но калибровка ещё не сделана
        if ((int)$me['step'] >= 2 && !$calibrated) {
            $left = max(0, $need - $echo);
            $note = ($left > 0) ? 'Нужно символов: '.$need.' (у вас '.$echo.').' : 'Пакет собран. Приступим к калибровке?';
            $response['question'] = $note;
            $response['answer']   = ($echo >= $need) ? [ 301 => 'Калибровать камертон' ] : [];
            break;
        }

        // Уже откалибровано
        $response['question'] = 'Калибровка завершена. Вас ждут на перроне Канто (27).';
        $response['answer']   = [];
        break;
}

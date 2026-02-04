<?php
// npc/quest120/steward.php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * Квест: Семь шагов эфира (ID 120)
 * НПС: "Смотритель перрона" — в локациях 27 (Канто) и 70 (Джотто).
 */
$response['name'] = 'Смотритель перрона';

if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }
$uid = (int)$_SESSION['id'];
global $mysqli;

const QUEST_ID    = 120;
const TARGET_ECHO = 5; // запасная цель, если нет в data.need_echo

/* ========= служебные функции для прогресса квеста ========= */
function q120_get(mysqli $db, int $uid): ?array {
    $row = $db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1")->fetch_assoc();
    if (!$row) return null;
    $row['step'] = (int)$row['step'];
    $row['end']  = (int)($row['end'] ?? 0);
    $row['data'] = json_decode($row['data'] ?? '[]', true) ?: [];
    return $row;
}
function q120_create(mysqli $db, int $uid): array {
    $data = ['echo'=>0, 'scan_ts'=>0, 'calibrated'=>0];
    $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    $db->query("INSERT INTO `user_quests` (`user_id`,`quest_id`,`step`,`end`,`data`) VALUES ({$uid},".QUEST_ID.",1,0,'{$json}')");
    return q120_get($db, $uid);
}
function q120_save(mysqli $db, int $uid, int $step, array $data, int $end = 0): void {
    $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    $db->query("UPDATE `user_quests` SET `step`={$step}, `end`={$end}, `data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1");
}
/** Возвращает корректную цель: need_echo (если есть), иначе TARGET_ECHO, но не ниже уже собранного. */
function q120_need(array $st): int {
    $echo = (int)($st['data']['echo'] ?? 0);
    $need = (int)($st['data']['need_echo'] ?? 0);
    if ($need <= 0) $need = TARGET_ECHO;
    if ($echo > $need) $need = $echo; // чтобы не было 5/3 и т.п.
    return $need;
}
function q120_status_line(array $st): string {
    $e    = (int)($st['data']['echo'] ?? 0);
    $need = q120_need($st);
    $cal  = !empty($st['data']['calibrated']) ? 'Да' : 'Нет';
    $s    = (int)$st['step'];
    $map  = [1=>'Сбор символов',2=>'Сбор завершён',3=>'Калибровка завершена',4=>'Готово'];
    $stage= $map[$s] ?? ('Шаг '.$s);
    return "Эхо-символы: <b>{$e}/{$need}</b> · Калибровка: <b>{$cal}</b> · Этап: <b>{$stage}</b>";
}

/* ========= компактный UI ========= */
function steward_card(string $title, string $sub, int $echo = null, int $need = null, array $chips = []): string {
    $prog = '';
    if ($echo !== null && $need !== null) {
        $pct = max(0, min(100, (int)round(100 * min(1, $echo / max(1,$need)))));
        $prog = '<div class="st-prog"><span style="width:'.$pct.'%"></span></div>';
    }
    $chipsHtml = '';
    if (!empty($chips)) {
        $chipsHtml = '<div class="st-chips">'.implode('', array_map(function($c){
            return '<div class="chip">'.$c.'</div>';
        }, $chips)).'</div>';
    }

    return '
    <style>
      .st-wrap{color:#e9e7ff}
      .st-card{border:1px solid rgba(255,255,255,.12);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.04));padding:14px;box-shadow:0 10px 24px rgba(0,0,0,.25)}
      .st-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
      .st-ico{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,rgba(106,108,246,.25),rgba(106,108,246,.12));border:1px solid rgba(106,108,246,.35);display:flex;align-items:center;justify-content:center}
      .st-ico i{color:#9aa0ff}
      .st-title{font-weight:900;font-size:20px;color:#c9ccff}
      .st-sub{color:#b2b6d9}
      .st-prog{height:8px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:6px}
      .st-prog>span{display:block;height:100%;background:linear-gradient(90deg,#8bb8ff,#6a6cf6)}
      .st-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
      .chip{display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)}
      .chip i{opacity:.9}
      @media (max-width:560px){ .st-title{font-size:18px} }
    </style>
    <div class="st-wrap">
      <div class="st-card">
        <div class="st-head">
          <div class="st-ico"><i class="fa fa-train"></i></div>
          <div style="min-width:0">
            <div class="st-title">'.$title.'</div>
            <div class="st-sub">'.$sub.'</div>
            '.$prog.'
          </div>
        </div>
        '.$chipsHtml.'
      </div>
    </div>';
}

/* ================= логика диалога ================= */
$step = isset($npcStep) ? (int)$npcStep : 0;
$me   = q120_get($mysqli, $uid);
$loc  = (int)$mysqli->query("SELECT `location` FROM `users` WHERE `id`={$uid}")->fetch_assoc()['location'];

switch ($step) {
    case 101: // принять квест
        if ($me && (int)$me['end'] === 1) {
            $response['question'] = steward_card(
                'Вы уже закрыли это дело',
                'Но двери перрона для вас всегда открыты.'
            );
            break;
        }
        if (!$me) $me = q120_create($mysqli, $uid);
        else q120_save($mysqli, $uid, max(1,(int)$me['step']), $me['data'], 0);

        $need = q120_need($me);
        $response['question'] = steward_card(
            'Задание принято',
            'Сядьте в поезд (локация <b>8010</b>) и найдите <b>Проводника</b>. Во время пути он поможет собрать эха (нужно '.$need.').',
            (int)($me['data']['echo'] ?? 0),
            $need,
            [
                '<i class="fa fa-location-dot"></i> Проводник — локация <b>8010</b>',
                '<i class="fa fa-lightbulb"></i> Работает только в поезде'
            ]
        );
        $response['answer'] = [
            0   => 'Понял, что дальше?',
            201 => 'Где найти Проводника?'
        ];
        break;

    case 201: // подсказка о проводнике
        $response['question'] = steward_card(
            'Где Проводник?',
            'Проводник находится в локации поезда <b>8010</b>. Встаньте на перрон и дождитесь отправления.'
        );
        $response['answer'] = [ 0 => 'Спасибо' ];
        break;

    case 301: // подсказка о технике
        $response['question'] = steward_card(
            'Дальше — к технику',
            'Когда соберёте эха, подойдите к <b>Технику эфирной связи</b> в Джотто (локация <b>70</b>) — он откалибрует камертон.',
            $me ? (int)($me['data']['echo'] ?? 0) : 0,
            $me ? q120_need($me) : TARGET_ECHO,
            ['<i class="fa fa-screwdriver-wrench"></i> Техник — локация <b>70</b>']
        );
        $response['answer'] = [ 0 => 'Хорошо' ];
        break;

    default:
        if (!$me) {
            $side = ($loc === 27) ? 'Канто' : (($loc === 70) ? 'Джотто' : 'станции');
            $response['question'] = steward_card(
                'Перроны '.$side.' шумят…',
                'Эфир полон шёпота. Хочешь услышать его отчётливо? Возьми задание: собери эха в пути — и мы настроим камертон.'
            );
            $response['answer'] = [
                101 => 'Начать задание',
                201 => 'Где найти Проводника?'
            ];
            break;
        }

        $need  = q120_need($me);
        $reply = q120_status_line($me);

        if ((int)$me['end'] === 1) {
            $response['question'] = steward_card(
                'Добро пожаловать на перрон',
                'Задание уже выполнено. '.$reply
            );
            break;
        }

        if ((int)$me['step'] === 1) {
            $response['question'] = steward_card(
                'В пути поймаете шёпот',
                'Соберите эха во время поездки и вернитесь. '.$reply,
                (int)($me['data']['echo'] ?? 0),
                $need,
                [
                    '<i class="fa fa-person-walking"></i> Старт — локация <b>8010</b>',
                    '<i class="fa fa-bullseye"></i> Цель — собрать <b>'.$need.'</b> эха'
                ]
            );
            $response['answer'] = [
                201 => 'Где найти Проводника?',
                301 => 'К кому идти после сбора?'
            ];
        } elseif ((int)$me['step'] === 2) {
            $response['question'] = steward_card(
                'Сбор завершён',
                'Отлично! Теперь к <b>Технику эфирной связи</b> в Джотто (70) — он откалибрует камертон. '.$reply,
                (int)($me['data']['echo'] ?? 0),
                $need,
                ['<i class="fa fa-screwdriver-wrench"></i> Техник — локация <b>70</b>']
            );
            $response['answer'] = [ 301 => 'Где он находится?' ];
        } elseif ((int)$me['step'] >= 3) {
            $response['question'] = steward_card(
                'Калибровка завершена',
                'Возвращайтесь к <b>Дежурному по станции</b> в Канто (27) — он выдаст награду. '.$reply,
                (int)($me['data']['echo'] ?? 0),
                $need,
                ['<i class="fa fa-user-shield"></i> Дежурный — локация <b>27</b>']
            );
            $response['answer'] = [ 0 => 'Хорошо' ];
        }
        break;
}

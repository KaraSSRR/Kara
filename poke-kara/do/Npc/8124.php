<?php
// npc/quest120/officer.php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * Дежурный по станции — финал квеста, выдаёт награды.
 */
$response['name'] = 'Дежурный по станции';

if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }
$uid = (int)$_SESSION['id'];
global $mysqli;

const QUEST_ID = 120;

/* ===== Награда (подправьте по желанию) ===== */
const REWARD_MONEY_ID = 1;         // генкары
const REWARD_MONEY_AMT = 50000;
const REWARD_ITEM_ID   = 26;        // пример: «Жёлтая конфета»
const REWARD_ITEM_AMT  = 2;

/* ===== Вспомогательные ===== */
function q120_get(mysqli $db, int $uid): ?array {
    $row = $db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1")->fetch_assoc();
    if (!$row) return null;
    $row['step'] = (int)$row['step'];
    $row['end']  = (int)($row['end'] ?? 0);
    $row['data'] = json_decode($row['data'] ?? '[]', true) ?: [];
    return $row;
}
function q120_finish(mysqli $db, int $uid, array $data): void {
    $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
    $db->query("UPDATE `user_quests` SET `step`=4, `end`=1, `data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1");
}
function item_meta(mysqli $db, int $id): array {
    // Пытаемся взять название из базы; иконка — стандартный путь
    $name = null;
    if ($res = $db->query("SELECT `name` FROM `base_items` WHERE `id`=".(int)$id." LIMIT 1")) {
        $row = $res->fetch_assoc();
        if ($row && !empty($row['name'])) $name = $row['name'];
    }
    $img = '/img/world/items/little/'.(int)$id.'.png';
    return [
        'name' => $name ?: ('Предмет #'.$id),
        'img'  => $img
    ];
}
function fmt_num($n): string { return number_format((int)$n, 0, '', ' '); }

/* ===== Оформление карточек (мини-UI) ===== */
function reward_preview_html(mysqli $db): string {
    $m = item_meta($db, REWARD_MONEY_ID);
    $i = item_meta($db, REWARD_ITEM_ID);
    $moneyName = htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8');
    $itemName  = htmlspecialchars($i['name'], ENT_QUOTES, 'UTF-8');

    return '
    <style>
      .of-wrap{color:#e9e7ff}
      .of-card{border:1px solid rgba(255,255,255,.12);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.04));padding:14px;box-shadow:0 10px 24px rgba(0,0,0,.25)}
      .of-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
      .of-ico{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,rgba(106,108,246,.25),rgba(106,108,246,.12));border:1px solid rgba(106,108,246,.35);display:flex;align-items:center;justify-content:center}
      .of-ico i{color:#9aa0ff}
      .of-title{font-weight:900;font-size:20px;color:#c9ccff}
      .of-sub{color:#b2b6d9}
      .rewards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:8px}
      .rw{display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12)}
      .rw .img{width:36px;height:36px;border-radius:10px;background:#0f1026 center/contain no-repeat;border:1px solid rgba(255,255,255,.12)}
      .rw .name{font-weight:800;color:#e9eaff}
      .rw .qty{color:#9aa0ff;font-weight:900}
      @media (max-width:560px){ .rewards{grid-template-columns:1fr} .of-title{font-size:18px} }
    </style>
    <div class="of-wrap">
      <div class="of-card">
        <div class="of-head">
          <div class="of-ico"><i class="fa fa-clipboard-check"></i></div>
          <div>
            <div class="of-title">Завершение оформления</div>
            <div class="of-sub">Всё готово — осталось поставить печать. Ваша награда:</div>
          </div>
        </div>
        <div class="rewards">
          <div class="rw">
            <div class="img" style="background-image:url('.htmlspecialchars($m['img']).')"></div>
            <div>
              <div class="name">'.$moneyName.'</div>
              <div class="qty">× '.fmt_num(REWARD_MONEY_AMT).'</div>
            </div>
          </div>
          <div class="rw">
            <div class="img" style="background-image:url('.htmlspecialchars($i['img']).')"></div>
            <div>
              <div class="name">'.$itemName.'</div>
              <div class="qty">× '.fmt_num(REWARD_ITEM_AMT).'</div>
            </div>
          </div>
        </div>
      </div>
    </div>';
}
function reward_done_html(mysqli $db): string {
    $m = item_meta($db, REWARD_MONEY_ID);
    $i = item_meta($db, REWARD_ITEM_ID);
    $moneyName = htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8');
    $itemName  = htmlspecialchars($i['name'], ENT_QUOTES, 'UTF-8');

    return '
    <style>
      .of-wrap{color:#e9e7ff}
      .of-card{border:1px solid rgba(255,255,255,.12);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.04));padding:14px;box-shadow:0 10px 24px rgba(0,0,0,.25)}
      .of-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
      .of-ico{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,rgba(92,214,125,.25),rgba(92,214,125,.12));border:1px solid rgba(92,214,125,.35);display:flex;align-items:center;justify-content:center}
      .of-ico i{color:#6fe39a}
      .of-title{font-weight:900;font-size:20px;color:#d8ffd8}
      .rewards{display:flex;gap:12px;flex-wrap:wrap}
      .chip{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)}
      .chip .img{width:22px;height:22px;border-radius:8px;background:#0f1026 center/contain no-repeat;border:1px solid rgba(255,255,255,.12)}
      .chip b{color:#e9eaff}
      .chip span{color:#9aa0ff;font-weight:900}
    </style>
    <div class="of-wrap">
      <div class="of-card">
        <div class="of-head">
          <div class="of-ico"><i class="fa fa-check"></i></div>
          <div class="of-title">Документы приняты — награда выдана!</div>
        </div>
        <div class="rewards">
          <div class="chip"><div class="img" style="background-image:url('.htmlspecialchars($m['img']).')"></div><b>'.$moneyName.'</b> <span>× '.fmt_num(REWARD_MONEY_AMT).'</span></div>
          <div class="chip"><div class="img" style="background-image:url('.htmlspecialchars($i['img']).')"></div><b>'.$itemName.'</b> <span>× '.fmt_num(REWARD_ITEM_AMT).'</span></div>
        </div>
        <div style="margin-top:8px;color:#b2b6d9">Спасибо за службу! С такими руками наш вокзал работает как часы.</div>
      </div>
    </div>';
}

/* ===== Логика ===== */
$me   = q120_get($mysqli, $uid);
$step = isset($npcStep) ? (int)$npcStep : 0;

if (!$me) {
    $response['question'] = 'По службе вижу — вы ещё не открывали карточку задания у Смотрителя перрона.';
    return;
}

switch ($step) {
    case 401: // завершить
        if ((int)$me['step'] < 3 || empty($me['data']['calibrated'])) {
            $response['question'] = 'Пока калибровка не завершена у техника эфирной связи, я не могу закрыть дело.';
            break;
        }

        // выдача награды
        if (function_exists('itemAdd')) {
            itemAdd(REWARD_MONEY_ID, REWARD_MONEY_AMT);
            itemAdd(REWARD_ITEM_ID,  REWARD_ITEM_AMT);
        }
        $me['data']['rewarded'] = 1;
        q120_finish($mysqli, $uid, $me['data']);

        $response['question'] = reward_done_html($mysqli);
        $response['answer']   = [ 0 => 'Забрать документы' ];
        break;

    default:
        if ((int)$me['end'] === 1) {
            $response['question'] = 'Уже всё оформлено. Если потребуется — возвращайтесь.';
        } elseif ((int)$me['step'] >= 3 && !empty($me['data']['calibrated'])) {
            $response['question'] = reward_preview_html($mysqli);
            $response['answer']   = [ 401 => 'Завершить оформление' ];
        } else {
            $response['question'] = 'Когда техник даст зелёный свет по калибровке, приходите — всё быстро оформим.';
        }
        break;
}

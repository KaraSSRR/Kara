<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

$npc = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id` = ".(int)$npcId)->fetch_assoc();
$response['name'] = 'Игровой автомат';
$response['image'] = isset($npc['image']) ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

/* ====================== НАСТРОЙКИ ====================== */
$TICKET_PRICE = 70000;                           // цена билета
$CURRENCY_ID  = 1;                               // ID генкаров
$CURRENCY_IMG = '/img/world/items/little/1.png'; // иконка валюты

/* ====================== ПУЛ ПРИЗОВ ===================== */
$casinoPrizes = [
    ['id'=>1,'quantity'=>25000,'name'=>'Генкары','image'=>'/img/world/items/little/1.png'],
    ['id'=>1,'quantity'=>40000,'name'=>'Генкары','image'=>'/img/world/items/little/1.png'],
    ['id'=>1,'quantity'=>55000,'name'=>'Генкары','image'=>'/img/world/items/little/1.png'],
    ['id'=>1,'quantity'=>85000,'name'=>'Генкары','image'=>'/img/world/items/little/1.png'],
    ['id'=>2,'quantity'=>5,'name'=>'Покебол','image'=>'/img/world/items/little/2.png'],
    ['id'=>2,'quantity'=>15,'name'=>'Покебол','image'=>'/img/world/items/little/2.png'],
    ['id'=>3,'quantity'=>5,'name'=>'Грейтбол','image'=>'/img/world/items/little/3.png'],
    ['id'=>3,'quantity'=>10,'name'=>'Грейтбол','image'=>'/img/world/items/little/3.png'],
    ['id'=>4,'quantity'=>3,'name'=>'Ультрабол','image'=>'/img/world/items/little/4.png'],
    ['id'=>4,'quantity'=>4,'name'=>'Ультрабол','image'=>'/img/world/items/little/4.png'],
    ['id'=>13,'quantity'=>2,'name'=>'Стимпак','image'=>'/img/world/items/little/13.png'],
    ['id'=>21,'quantity'=>1,'name'=>'Огнетушитель','image'=>'/img/world/items/little/21.png'],
    ['id'=>18,'quantity'=>1,'name'=>'Антиспут','image'=>'/img/world/items/little/18.png'],
    ['id'=>19,'quantity'=>1,'name'=>'Антипарализ','image'=>'/img/world/items/little/19.png'],
    ['id'=>22,'quantity'=>1,'name'=>'Противоядие','image'=>'/img/world/items/little/22.png'],
    ['id'=>17,'quantity'=>1,'name'=>'Энергетик','image'=>'/img/world/items/little/17.png'],
    ['id'=>26,'quantity'=>3,'name'=>'Желтая конфета','image'=>'/img/world/items/little/26.png'],
    ['id'=>26,'quantity'=>4,'name'=>'Желтая конфета','image'=>'/img/world/items/little/26.png'],
    ['id'=>26,'quantity'=>5,'name'=>'Желтая конфета','image'=>'/img/world/items/little/26.png'],
    ['id'=>27,'quantity'=>2,'name'=>'Голубая конфета','image'=>'/img/world/items/little/27.png'],
    ['id'=>27,'quantity'=>3,'name'=>'Голубая конфета','image'=>'/img/world/items/little/27.png'],
    ['id'=>27,'quantity'=>4,'name'=>'Голубая конфета','image'=>'/img/world/items/little/27.png'],
    ['id'=>28,'quantity'=>1,'name'=>'Зеленая конфета','image'=>'/img/world/items/little/28.png'],
    ['id'=>28,'quantity'=>2,'name'=>'Зеленая конфета','image'=>'/img/world/items/little/28.png'],
    ['id'=>28,'quantity'=>3,'name'=>'Зеленая конфета','image'=>'/img/world/items/little/28.png'],
    ['id'=>29,'quantity'=>1,'name'=>'Фиолетовая конфета','image'=>'/img/world/items/little/29.png'],
    ['id'=>29,'quantity'=>2,'name'=>'Фиолетовая конфета','image'=>'/img/world/items/little/29.png'],

    ['id'=>35,'quantity'=>1,'name'=>'Конфета «Вольтаж»','image'=>'/img/world/items/little/35.png'],
    ['id'=>36,'quantity'=>1,'name'=>'Конфета «Вулкан»','image'=>'/img/world/items/little/36.png'],
    ['id'=>37,'quantity'=>1,'name'=>'Конфета «Гематоген»','image'=>'/img/world/items/little/37.png'],
    ['id'=>38,'quantity'=>1,'name'=>'Конфета «Камешки»','image'=>'/img/world/items/little/38.png'],
    ['id'=>39,'quantity'=>1,'name'=>'Конфета «Кудесница»','image'=>'/img/world/items/little/39.png'],
    ['id'=>40,'quantity'=>1,'name'=>'Конфета «Кунг-Фу»','image'=>'/img/world/items/little/40.png'],
    ['id'=>41,'quantity'=>1,'name'=>'Конфета «Легенды Драконов»','image'=>'/img/world/items/little/41.png'],
    ['id'=>42,'quantity'=>1,'name'=>'Конфета «Морской Бриз»','image'=>'/img/world/items/little/42.png'],
    ['id'=>43,'quantity'=>1,'name'=>'Конфета мятная','image'=>'/img/world/items/little/43.png'],
    ['id'=>44,'quantity'=>1,'name'=>'Конфета «Полет»','image'=>'/img/world/items/little/44.png'],
    ['id'=>45,'quantity'=>1,'name'=>'Конфета «Привидение»','image'=>'/img/world/items/little/45.png'],
    ['id'=>46,'quantity'=>1,'name'=>'Конфета просроченная','image'=>'/img/world/items/little/46.png'],
    ['id'=>47,'quantity'=>1,'name'=>'Конфета «Ромашка»','image'=>'/img/world/items/little/47.png'],
    ['id'=>48,'quantity'=>1,'name'=>'Конфета ромовая','image'=>'/img/world/items/little/48.png'],
    ['id'=>49,'quantity'=>1,'name'=>'Конфета с тёмной начинкой','image'=>'/img/world/items/little/49.png'],
    ['id'=>50,'quantity'=>1,'name'=>'Конфета сахарная','image'=>'/img/world/items/little/50.png'],
    ['id'=>51,'quantity'=>1,'name'=>'Конфета «Сладкий Нектар»','image'=>'/img/world/items/little/51.png'],

    ['id'=>35,'quantity'=>2,'name'=>'Конфета «Вольтаж»','image'=>'/img/world/items/little/35.png'],
    ['id'=>36,'quantity'=>2,'name'=>'Конфета «Вулкан»','image'=>'/img/world/items/little/36.png'],
    ['id'=>37,'quantity'=>2,'name'=>'Конфета «Гематоген»','image'=>'/img/world/items/little/37.png'],
    ['id'=>38,'quantity'=>2,'name'=>'Конфета «Камешки»','image'=>'/img/world/items/little/38.png'],
    ['id'=>39,'quantity'=>2,'name'=>'Конфета «Кудесница»','image'=>'/img/world/items/little/39.png'],
    ['id'=>40,'quantity'=>2,'name'=>'Конфета «Кунг-Фу»','image'=>'/img/world/items/little/40.png'],
    ['id'=>41,'quantity'=>2,'name'=>'Конфета «Легенды Драконов»','image'=>'/img/world/items/little/41.png'],
    ['id'=>42,'quantity'=>2,'name'=>'Конфета «Морской Бриз»','image'=>'/img/world/items/little/42.png'],
    ['id'=>43,'quantity'=>2,'name'=>'Конфета мятная','image'=>'/img/world/items/little/43.png'],
    ['id'=>44,'quantity'=>2,'name'=>'Конфета «Полет»','image'=>'/img/world/items/little/44.png'],
    ['id'=>45,'quantity'=>2,'name'=>'Конфета «Привидение»','image'=>'/img/world/items/little/45.png'],
    ['id'=>46,'quantity'=>2,'name'=>'Конфета просроченная','image'=>'/img/world/items/little/46.png'],
    ['id'=>47,'quantity'=>2,'name'=>'Конфета «Ромашка»','image'=>'/img/world/items/little/47.png'],
    ['id'=>48,'quantity'=>2,'name'=>'Конфета ромовая','image'=>'/img/world/items/little/48.png'],
    ['id'=>49,'quantity'=>2,'name'=>'Конфета с тёмной начинкой','image'=>'/img/world/items/little/49.png'],
    ['id'=>50,'quantity'=>2,'name'=>'Конфета сахарная','image'=>'/img/world/items/little/50.png'],
    ['id'=>51,'quantity'=>2,'name'=>'Конфета «Сладкий Нектар»','image'=>'/img/world/items/little/51.png'],

    ['id'=>52,'quantity'=>1,'name'=>'Конфета «Шоколадная»','image'=>'/img/world/items/little/52.png'],
    ['id'=>54,'quantity'=>1,'name'=>'Лавбол','image'=>'/img/world/items/little/54.png'],
    ['id'=>57,'quantity'=>1,'name'=>'Мунбол','image'=>'/img/world/items/little/57.png'],
    ['id'=>56,'quantity'=>1,'name'=>'Мастербол','image'=>'/img/world/items/little/56.png'],
    ['id'=>80,'quantity'=>1,'name'=>'Громовой камень','image'=>'/img/world/items/little/80.png'],
    ['id'=>124,'quantity'=>1,'name'=>'Амурит','image'=>'/img/world/items/little/124.png'],
    ['id'=>62,'quantity'=>1,'name'=>'Даркбол','image'=>'/img/world/items/little/62.png'],
    ['id'=>181,'quantity'=>1,'name'=>'Малый усилитель ловли','image'=>'/img/world/items/little/181.png'],
    ['id'=>183,'quantity'=>1,'name'=>'Малый усилитель монет','image'=>'/img/world/items/little/183.png'],
    ['id'=>196,'quantity'=>1,'name'=>'Именной бланк','image'=>'/img/world/items/little/196.png'],
    ['id'=>193,'quantity'=>1,'name'=>'Коробка витаминов','image'=>'/img/world/items/little/193.png'],
];

/* ====================== ДИНАМИЧЕСКИЙ ВЫБОР «ИЗБРАННЫХ» ====================== */
/**
 * Выбирает динамичную подборку призов.
 * $mode: 'daily' (раз в день), 'hourly' (каждый час), 'session' (кэш 5 мин), 'random' (на каждый вызов).
 */
function casino_pick_featured(array $list, int $count = 6, string $mode = 'daily'): array {
    $n = count($list);
    if ($n <= $count) return $list;

    if ($mode === 'session') {
        if (
            empty($_SESSION['casino_featured']) ||
            empty($_SESSION['casino_featured_expires']) ||
            $_SESSION['casino_featured_expires'] < time()
        ) {
            $keys = array_rand($list, $count);
            if (!is_array($keys)) $keys = [$keys];
            $_SESSION['casino_featured'] = array_values(array_intersect_key($list, array_flip($keys)));
            $_SESSION['casino_featured_expires'] = time() + 300; // 5 минут
        }
        return $_SESSION['casino_featured'];
    }

    if ($mode === 'random') {
        $keys = array_rand($list, $count);
        if (!is_array($keys)) $keys = [$keys];
        return array_values(array_intersect_key($list, array_flip($keys)));
    }

    // Детерминированная «случайность» на период (день/час)
    $seedStr = ($mode === 'hourly') ? date('Y-m-d-H') : date('Y-m-d');
    $seed = crc32('featured:'.$seedStr);

    $copy = $list;
    usort($copy, function($a, $b) use ($seed) {
        $ha = crc32($seed . serialize($a));
        $hb = crc32($seed . serialize($b));
        if ($ha === $hb) return 0;
        return ($ha < $hb) ? -1 : 1;
    });

    return array_slice($copy, 0, $count);
}

/* ====================== СТИЛИ (адаптив) ====================== */
$styles = <<<CSS
<style>
.cj-scope, .cj-scope * { box-sizing: border-box; }
.cj-scope { width:100%; color:#e9e7ff; }

.cj-card{
  width:100%;
  border:1px solid rgba(255,255,255,.12);
  border-radius:16px;
  background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.04));
  padding:14px;
  box-shadow:0 10px 24px rgba(0,0,0,.25);
}

.cj-head{display:flex;align-items:center;gap:12px;margin-bottom:12px;min-width:0}
.cj-ico{
  width:44px;height:44px;border-radius:12px;
  background:linear-gradient(180deg,rgba(106,108,246,.25),rgba(106,108,246,.12));
  border:1px solid rgba(106,108,246,.35);
  display:flex;align-items:center;justify-content:center;flex-shrink:0
}
.cj-ico i{color:#9aa0ff;font-size:20px}
.cj-title{font-weight:900;font-size:24px;color:#c9ccff;line-height:1.1}
.cj-sub{color:#b2b6d9;margin-top:4px}

.cj-badge{
  margin-left:auto; display:inline-flex; align-items:center; gap:8px;
  padding:6px 10px; border-radius:12px;
  border:1px dashed rgba(255,255,255,.18);
  background:rgba(255,255,255,.06);
  color:#e7e8ff; font-weight:800; white-space:nowrap
}
.cj-badge .img{width:16px;height:16px;background-size:contain;background-repeat:no-repeat}

/* Сетка 3-в-ряд (desktop) */
.cj-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;width:100%}

/* Карточка приза — вертикальная колонка */
.cj-prize{
  height:150px; padding:10px; border:1px solid rgba(255,255,255,.12);
  border-radius:14px; background:rgba(255,255,255,.05);
  display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
  gap:8px; overflow:hidden; text-align:center;
}
.cj-prize .img{
  width:64px;height:64px;border-radius:12px;
  background:#0f1026 center/contain no-repeat;
  border:1px solid rgba(255,255,255,.12);
}
.cj-prize .txt{width:100%}
.cj-prize .nm{
  font-size:13px; line-height:1.22; font-weight:800; color:#e7e9ff;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
  overflow:hidden; white-space:normal; word-break:break-word;
}
.cj-prize .qt{font-size:12px; font-weight:900; color:#9aa0ff; margin-top:2px}

/* Экран выигрыша */
.cj-win .cj-title{color:#d6d8ff}
.cj-win .prize-line{display:flex;align-items:center;gap:16px;margin-top:10px}
.cj-win .prize-line .slot{
  width:62px;height:62px;border-radius:14px;background:#0f1026 center/contain no-repeat;
  border:1px solid rgba(255,255,255,.14)
}

/* ====== АДАПТИВ ====== */
@media (max-width: 860px){
  .cj-title{font-size:22px}
  .cj-ico{width:40px;height:40px}
  .cj-grid{gap:10px}
  .cj-prize{height:142px}
  .cj-prize .img{width:60px;height:60px}
}

/* Мобильные ≤ 560px — минимум 2 колонки */
@media (max-width: 560px){
  .cj-card{padding:12px}
  .cj-head{gap:10px;margin-bottom:10px}
  .cj-title{font-size:18px}
  .cj-sub{font-size:13px}
  .cj-ico{width:36px;height:36px;border-radius:10px}
  .cj-badge{padding:5px 8px;border-radius:10px;font-size:12px}
  .cj-badge .img{width:14px;height:14px}

  .cj-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .cj-prize{height:134px;padding:9px;border-radius:12px}
  .cj-prize .img{width:54px;height:54px;border-radius:10px}
  .cj-prize .nm{font-size:12.5px}
  .cj-prize .qt{font-size:11.5px}
}

/* Очень узкие ≤ 380px — по-прежнему 2 колонки */
@media (max-width: 380px){
  .cj-card{padding:10px;border-radius:14px}
  .cj-title{font-size:16px}
  .cj-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
  .cj-prize{height:120px}
  .cj-prize .img{width:48px;height:48px}
  .cj-prize .nm{font-size:12px}
  .cj-prize .qt{font-size:11px}
}
</style>
CSS;

/* ====================== РЕНДЕРЫ ====================== */
$renderPrizeGrid = function(array $list, ?int $limit = null): string {
    if ($limit !== null) $list = array_slice($list, 0, max(0,$limit));
    $html = '<div class="cj-grid">';
    foreach ($list as $p) {
        $img  = htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($p['name'],  ENT_QUOTES, 'UTF-8');
        $qt   = (int)$p['quantity'];
        $html .= '<div class="cj-prize" title="'.$name.' × '.$qt.'">'.
                   '<div class="img" style="background-image:url('.$img.')"></div>'.
                   '<div class="txt"><div class="nm">'.$name.'</div><div class="qt">× '.$qt.'</div></div>'.
                 '</div>';
    }
    $html .= '</div>';
    return $html;
};

$renderIntro = function(array $list) use ($styles,$CURRENCY_IMG,$TICKET_PRICE,$renderPrizeGrid){
    $grid = $renderPrizeGrid($list, null);
    return $styles.'<div class="cj-scope">'.
        '<div class="cj-card">'.
          '<div class="cj-head">'.
            '<div class="cj-ico"><i class="fa fa-dice"></i></div>'.
            '<div>'.
              '<div class="cj-title">«Счастливая Подкова»</div>'.
              '<div class="cj-sub">Купи билет и получи случайный приз из списка ниже.</div>'.
            '</div>'.
            '<div class="cj-badge"><div class="img" style="background-image:url('.htmlspecialchars($CURRENCY_IMG,ENT_QUOTES,'UTF-8').')"></div><span>'.number_format($TICKET_PRICE,0,'',' ').'</span></div>'.
          '</div>'.
          $grid.
        '</div>'.
      '</div>';
};

$renderWin = function(array $prize,int $price,string $currencyImg) use ($styles){
    $name = htmlspecialchars($prize['name'],ENT_QUOTES,'UTF-8');
    $img  = htmlspecialchars($prize['image'],ENT_QUOTES,'UTF-8');
    $qt   = (int)$prize['quantity'];
    return $styles.'<div class="cj-scope cj-win">'.
      '<div class="cj-card">'.
        '<div class="cj-head">'.
          '<div class="cj-ico"><i class="fa fa-trophy"></i></div>'.
          '<div>'.
            '<div class="cj-title">Поздравляем!</div>'.
            '<div class="cj-sub">Приз добавлен в инвентарь.</div>'.
          '</div>'.
          '<div class="cj-badge"><div class="img" style="background-image:url('.htmlspecialchars($currencyImg,ENT_QUOTES,'UTF-8').')"></div><span>-'.number_format($price,0,'',' ').'</span></div>'.
        '</div>'.
        '<div class="prize-line">'.
          '<div class="slot" style="background-image:url('.$img.')"></div>'.
          '<div style="font-size:16px;"><b>'.$name.'</b> × '.$qt.'</div>'.
        '</div>'.
      '</div>'.
    '</div>';
};

/* ====================== ЛОГИКА ====================== */
$step = isset($npcStep) ? (int)$npcStep : 0;

switch ($step) {
    case 10: // полный список
        $response['question'] = $renderIntro($casinoPrizes);
        $response['answer'] = [
            2  => 'Купить билет за '.number_format($TICKET_PRICE,0,'',' '),
            1  => 'Свернуть список до 6',
        ];
        break;

    case 2: // покупка
        if (item_isset($CURRENCY_ID, $TICKET_PRICE)) {
            minus_item($CURRENCY_ID, $TICKET_PRICE);
            $prize = $casinoPrizes[array_rand($casinoPrizes)];
            itemAdd($prize['id'], (int)$prize['quantity']);

            $response['question'] = $renderWin($prize, $TICKET_PRICE, $CURRENCY_IMG);
            $response['answer'] = [
                2  => 'Ещё один билет',
                1  => 'К списку призов',
            ];
        } else {
            $response['question'] = $styles.
            '<div class="cj-scope"><div class="cj-card">'.
              '<div class="cj-head">'.
                '<div class="cj-ico"><i class="fa fa-wallet"></i></div>'.
                '<div>'.
                  '<div class="cj-title">Недостаточно генкаров</div>'.
                  '<div class="cj-sub">Нужно '.number_format($TICKET_PRICE,0,'',' ').'.</div>'.
                '</div>'.
              '</div>'.
            '</div></div>';
            $response['answer'] = [1 => 'Назад'];
        }
        break;

    case 1: // краткий список (динамичные 6)
    default:
        $featured = casino_pick_featured($casinoPrizes, 6, 'daily'); // можно 'hourly' / 'session' / 'random'
        $response['question'] = $renderIntro($featured);
        $response['answer'] = [
            2  => 'Купить билет за '.number_format($TICKET_PRICE,0,'',' '),
            10 => 'Показать все призы',
        ];
        break;
}
?>

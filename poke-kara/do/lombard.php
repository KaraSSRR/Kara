<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
require_once $patch_project.'/inc/conf/global.php';

/* ========= utils ========= */
function esc($s){ global $mysqli; return $mysqli->real_escape_string(trim($s)); }

/* Разрешаем только <b> и <span class="...">, остальное экранируем.
   Встраиваем в текст (НЕ в атрибуты). */
function allow_name_html($s){
  // убираем любые on* обработчики
  $s = preg_replace('#\son\w+="[^"]*"#i','',$s);
  // запрет на любые теги кроме b|span
  $s = preg_replace('#<(?!/?(b|span)(\s+[^>]*)?>)#i', '&lt;', $s);
  return $s;
}

/* ========= входные параметры ========= */
$category    = isset($_POST['category']) ? esc($_POST['category']) : 'all';
$q           = isset($_POST['q'])        ? esc($_POST['q'])        : '';
$sellerQuery = isset($_POST['seller'])   ? esc($_POST['seller'])   : ''; // поиск по нику продавца
$sort        = isset($_POST['sort'])     ? esc($_POST['sort'])     : 'promoted';
$only_buy    = !empty($_POST['only_buy']);
$admin_only  = !empty($_POST['admin_only']);

/* ========= служебные экшены для поповера =========
   1) resolve_pokemon: по id лота вернуть id покемона (productID), если category=pokemon */
if (!empty($_POST['resolve_pokemon'])) {
  $lid = (int)$_POST['resolve_pokemon'];
  $row = $mysqli->query("SELECT `category`,`productID` FROM `log_lombard` WHERE `id`={$lid}")->fetch_assoc();
  $pid = ($row && $row['category']==='pokemon') ? (int)$row['productID'] : null;
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['pokemon_id'=>$pid]);
  exit;
}

/* 2) get_lot: краткая инфа + история ставок */
if (!empty($_POST['get_lot'])) {
  $lid = (int)$_POST['get_lot'];
  $lot = $mysqli->query("SELECT L.*, U.login AS seller
                         FROM `log_lombard` L
                         LEFT JOIN `users` U ON U.id=L.userID
                         WHERE L.id={$lid}")->fetch_assoc();
  header('Content-Type: application/json; charset=utf-8');

  if (!$lot) { echo json_encode(['error'=>1]); exit; }

  // история ставок (последние 15)
  $bq = $mysqli->query("SELECT B.money,B.`date`, UU.login
                        FROM `log_lombard_bidcounter` B
                        LEFT JOIN `users` UU ON UU.id=B.`user`
                        WHERE B.lotID={$lid}
                        ORDER BY B.id DESC
                        LIMIT 15");
  $bids = [];
  while ($b = $bq->fetch_assoc()){
    $bids[] = [
      'money' => (int)$b['money'],
      'user'  => htmlspecialchars($b['login']?:'—', ENT_QUOTES, 'UTF-8'),
      'when'  => date('d.m H:i', strtotime($b['date']))
    ];
  }

  echo json_encode([
    'id'        => (int)$lot['id'],
    'category'  => $lot['category'],
    'name_html' => allow_name_html($lot['name']),
    'seller'    => htmlspecialchars($lot['seller']?:'—', ENT_QUOTES, 'UTF-8'),
    'priceNow'  => (int)$lot['priceNow'],
    'priceBuy'  => (int)$lot['priceBuy'],
    'dateEnd'   => (int)$lot['dateEnd'],
    'bids_cnt'  => count($bids),
    'bids'      => $bids
  ]);
  exit;
}

/* ========= выборка списка лотов ========= */
$where  = [];
$joins  = '';

if ($category !== 'all')  $where[] = "L.`category` = '{$category}'";
if ($q !== '')            $where[] = "L.`name` LIKE '%{$q}%'";
if ($only_buy)            $where[] = "L.`priceBuy` > 0";
if ($admin_only)          $where[] = "L.`is_admin` = 1";

/* поиск по продавцу: по логину в таблице users */
if ($sellerQuery !== '') {
  $joins  .= " LEFT JOIN `users` UU ON UU.id=L.userID ";
  $where[] = "UU.`login` LIKE '%{$sellerQuery}%'";
}

$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

switch ($sort){
  case 'end_asc':    $order = "L.`dateEnd` ASC";         break;
  case 'price_asc':  $order = "L.`priceNow` ASC";        break;
  case 'price_desc': $order = "L.`priceNow` DESC";       break;
  case 'newest':     $order = "L.`id` DESC";             break;
  default:           $order = "L.`prodv` DESC, L.`dateEnd` ASC"; // promoted
}

$sql = "SELECT L.* FROM `log_lombard` L {$joins} {$whereSql} ORDER BY {$order}";
$lot_bd = $mysqli->query($sql);

$a = '';
while ($lot = $lot_bd->fetch_assoc()) {
  $uid = (int)$lot['userID'];
  $user_bd = $mysqli->query("SELECT `login`,`id`,`sex`,`user_group` FROM `users` WHERE `id` = {$uid}")->fetch_assoc();

  // Следующая ставка
  $cer = (int)$lot['priceNow'] + (int)$lot['priceStep'];

  // Классы
  $classes = [];
  if ((int)$lot['prodv'] != 0)      $classes[] = 'pro';
  if (!empty($lot['is_admin']))     $classes[] = 'admin';
  if ((int)$lot['priceBuy'] > 0)    $classes[] = 'has-buy';

  // Тип для фронта
  $type = ($lot['category'] === 'pokemon') ? 'pokemon' : (($lot['category'] === 'egg') ? 'egg' : 'item');

  // конец аукциона → ts ms
  $rawEnd = $lot['dateEnd'];
  $endSec = is_numeric($rawEnd) ? (int)$rawEnd : strtotime($rawEnd);
  $endTs  = $endSec ? ($endSec * 1000) : 0;

  // количество ставок
  $bids_res   = $mysqli->query("SELECT COUNT(1) AS c FROM `log_lombard_bidcounter` WHERE `lotID` = ".(int)$lot['id']);
  $bids_count = (int)$bids_res->fetch_assoc()['c'];

  // карточка (data-* нужны фронту для поповера)
  $a .= '<div class="lot '.implode(' ', $classes).'"'
      . ' data-lot="'.(int)$lot['id'].'"'
      . ' data-type="'.htmlspecialchars($type,ENT_QUOTES,'UTF-8').'"'
      . ' data-bid="'.(int)$cer.'"'
      . ' data-buyprice="'.(int)$lot['priceBuy'].'"'
      . ' data-pricenow="'.(int)$lot['priceNow'].'"'
      . ' data-pricestep="'.(int)$lot['priceStep'].'"'
      . ' data-endts="'.$endTs.'"'
      . ' data-bids="'.$bids_count.'"'
      . ' data-admin="'.(!empty($lot['is_admin'])?1:0).'"'
      . ' data-pro="'.((int)$lot['prodv']!=0?1:0).'"'
      . ' data-seller-id="'.(int)$user_bd['id'].'"' 
      . ' data-seller-name="'.htmlspecialchars($user_bd['login'],ENT_QUOTES,'UTF-8').'"'
      . '><div>';

  // превью
  if ($lot['category'] != "pokemon"){
    $a .= '<div class="item"><div class="blockrait legend"></div>'
        . '<img loading="lazy" class="items" src="/img/world/items/little/'.(int)$lot['productID'].'.png" alt=""></div>';
  } else {
    $a .= '<div class="item"><div class="blockrait legend"></div>'
        . '<img loading="lazy" class="pokemons" src="/img/pokemons/animation/'.(int)$lot['count'].'.png" alt=""></div>';
  }

  // название: разрешённые теги (<b>, <span class="…">)
  $nameSafe = allow_name_html($lot['name']);

  // заголовок + продавец + остаток
  $a .= '<div class="name">'.$nameSafe.
           '<div class="info">от 
             <div class="user-link">
               <div onclick=showUserTooltip("'.(int)$user_bd['id'].'") class="Info-Link sex'.(int)$user_bd['sex'].'">
                 <i class="fas fa-info"></i>
               </div> 
               <div class="u-'.(int)$user_bd['user_group'].' label" onclick=user_to_chat_add("'.(int)$user_bd['id'].'")>'
                 .htmlspecialchars($user_bd['login'],ENT_QUOTES,'UTF-8').
               '</div>
             </div>
             <div class="remain">'.downcountermin($lot['dateEnd'],2).'</div>
           </div>';

  // кнопки (ставка / выкуп) — ваши исходные хендлеры
  $a .=  '<div class="bottom">
             <div class="buttons">
               <div class="button" onclick="issetAll('.(int)$lot['id'].',\'lot_stavk\')">
                 <i class="far fa-gavel"></i> '.number_format($cer,0,'.','.').' гк.
               </div>';

  if ((int)$lot['priceBuy'] > 0) {
    $a .=   '<div class="button" onclick="vikup('.(int)$lot['id'].')">
               <i class="far fa-shopping-cart"></i> '.number_format((int)$lot['priceBuy'],0,'.','.').' гк.
             </div>';
  }

  $a .=     '</div><br>';

  // счётчик ставок + id лота
  if ($bids_count) {
    $a .=   '<span class="bidcounter">'.$bids_count.' <i class="fas fa-circle"></i></span>
             <span class="lotid clickable" onclick="issetAll('.(int)$lot['id'].',\'bidcounter\')">lot'.(int)$lot['id'].'</span>';
  } else {
    $a .=   '<span class="lotid">lot'.(int)$lot['id'].'</span>';
  }

  $a .=   '</div></div></div>';
}

if (!$a) $a = "<div class='emptyLombard'>~В этой выборке нет лотов~</div>";

$ver = substr(md5($a),0,8);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'html'    => $a,
  'money'   => number_format(item_isset_count(1),0,'.','.'),
  'version' => $ver
]);

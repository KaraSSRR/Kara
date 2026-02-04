<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$type = strtolower(trim((string)((isset($_GET['type']) ? $_GET['type'] : ''))));
$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));

if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode(['ok'=>true,'items'=>[]], JSON_UNESCAPED_UNICODE);
  exit;
}

$items = [];
$limit = 20;
$like = '%' . $q . '%';

switch($type) {
  case 'pokemon':
    if (preg_match('~^\\d{1,4}$~', $q)) {
      $id = (int)$q;
      $stmt = $mysqli->prepare("SELECT id,name,name_rus FROM base_pokemons WHERE id=? OR name_rus LIKE ? OR name LIKE ? ORDER BY (id=? ) DESC, id ASC LIMIT $limit");
      $stmt->bind_param("issi", $id, $like, $like, $id);
    } else {
      $stmt = $mysqli->prepare("SELECT id,name,name_rus FROM base_pokemons WHERE name_rus LIKE ? OR name LIKE ? ORDER BY id ASC LIMIT $limit");
      $stmt->bind_param("ss", $like, $like);
    }
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
      $pid=(int)$r['id'];
      $nm = (string)($r['name_rus'] ?: $r['name']);
      $label = '#'.str_pad((string)$pid,3,'0',STR_PAD_LEFT).' '.$nm;
      $items[]=['id'=>$pid,'label'=>$label,'value'=>$label];
    }
    $stmt->close();
    break;

  case 'move':
    $stmt = $mysqli->prepare("SELECT id,name,name_rus FROM base_atk WHERE name_rus LIKE ? OR name LIKE ? OR title_all LIKE ? ORDER BY id ASC LIMIT $limit");
    $stmt->bind_param("sss",$like,$like,$like);
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
      $id=(int)$r['id'];
      $nm=(string)($r['name_rus'] ?: $r['name']);
      $label = $nm.' (#'.$id.')';
      $items[]=['id'=>$id,'label'=>$label,'value'=>$label];
    }
    $stmt->close();
    break;

  case 'ability':
    $stmt = $mysqli->prepare("SELECT id,name,name_rus FROM base_ability WHERE name_rus LIKE ? OR name LIKE ? ORDER BY id ASC LIMIT $limit");
    $stmt->bind_param("ss",$like,$like);
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
      $id=(int)$r['id'];
      $nm=(string)($r['name_rus'] ?: $r['name']);
      $label = $nm.' (#'.$id.')';
      $items[]=['id'=>$id,'label'=>$label,'value'=>$label];
    }
    $stmt->close();
    break;

  case 'item':
    $stmt = $mysqli->prepare("SELECT id,name FROM base_items WHERE name LIKE ? OR about LIKE ? ORDER BY id ASC LIMIT $limit");
    $stmt->bind_param("ss",$like,$like);
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
      $id=(int)$r['id'];
      $nm=(string)$r['name'];
      $label = $nm.' (#'.$id.')';
      $items[]=['id'=>$id,'label'=>$label,'value'=>$label];
    }
    $stmt->close();
    break;

  case 'user':
    // only for logged in
    if ($encyUserId <= 0) break;
    $stmt = $mysqli->prepare("SELECT id,login FROM users WHERE login LIKE ? OR id LIKE ? ORDER BY id ASC LIMIT $limit");
    $stmt->bind_param("ss",$like,$like);
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
      $id=(int)$r['id'];
      $nm=(string)$r['login'];
      $label = $nm.' (#'.$id.')';
      $items[]=['id'=>$id,'label'=>$label,'value'=>$nm];
    }
    $stmt->close();
    break;

  default:
    echo json_encode(['ok'=>false,'error'=>'type'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok'=>true,'items'=>$items], JSON_UNESCAPED_UNICODE);

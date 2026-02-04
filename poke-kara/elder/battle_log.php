<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Пути
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
$patch_func    = $patch_project . '/inc/function/Functions.php';

if (!file_exists($patch_global) || !file_exists($patch_func)) {
    die('<div class="tra"><span>Ошибка</span> Файл конфигурации не найден.</div>');
}
require_once($patch_global);
require_once($patch_func);

if (!isset($mysqli) || !$mysqli) {
    die('<div class="tra"><span>Ошибка</span> Подключение к базе данных не удалось.</div>');
}

// Доступ только пользователю id=4
session_start();
if (!isset($_SESSION['id']) || (int)$_SESSION['id'] !== 4) {
    http_response_code(403);
    die('<div style="margin:60px auto;max-width:520px;padding:36px 22px 30px 22px;background:#fff6;border-radius:19px;box-shadow:0 6px 32px #7050c022;font-family:Nunito,Arial,sans-serif;text-align:center;">
        <span style="display:block;font-size:3.6em;line-height:1;color:#caa2e6;">⛔</span>
        <div style="font-size:1.25em;color:#a184ca;font-weight:bold;margin:9px 0 13px 0;">Доступ запрещён</div>
        <div style="color:#8160a0;font-size:1em;">У вас нет прав для просмотра этой страницы.</div>
    </div>');
}

/* ---------------------------
   ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
----------------------------*/
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function getUserInfo(mysqli $mysqli, $uid) {
    $uid = (int)$uid;
    $res = $mysqli->query('SELECT id, login FROM users WHERE id = '.$uid);
    $row = $res ? $res->fetch_assoc() : null;
    return $row ?: ['id' => $uid, 'login' => 'Неизвестно'];
}
function formatDateSmart($str) {
    $ts = is_numeric($str) ? (int)$str : strtotime($str);
    if ($ts === false || $ts < 10) return e($str);
    $diff = time() - $ts;
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return floor($diff/60).' мин. назад';
    if ($diff < 86400) return floor($diff/3600).' ч. назад';
    return date('d.m.Y H:i', $ts);
}
function hl($haystack, $needle){
    if(!$needle) return e($haystack);
    $needle = preg_quote($needle, '/');
    return preg_replace('/(' . $needle . ')/iu', '<mark>$1</mark>', e($haystack));
}
function qLike($s){ return '%'.$s.'%'; }

function fetchAllPrepared(mysqli $db, string $sql, string $types = '', array $params = []) {
    $stmt = $db->prepare($sql);
    if(!$stmt) return [];
    if($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}
function fetchOnePrepared(mysqli $db, string $sql, string $types = '', array $params = []) {
    $stmt = $db->prepare($sql);
    if(!$stmt) return null;
    if($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function getInt($name, $def = 0) {
    return isset($_GET[$name]) ? (int)$_GET[$name] : $def;
}
function getStr($name, $def = '') {
    return isset($_GET[$name]) ? trim((string)$_GET[$name]) : $def;
}

/* ---------------------------
   ИНИЦИАЛИЗАЦИЯ ФИЛЬТРОВ
----------------------------*/
$tabs = [
    'trade'   => 'Обмены',
    'pokemon' => 'Покемоны',
    'item'    => 'Предметы',
    'attack'  => 'Атаки',
    'battle'  => 'Бои',
];
$currentTab = (isset($_GET['tab']) && isset($tabs[$_GET['tab']])) ? $_GET['tab'] : 'trade';

$searchTrade  = getStr('search_trade');
$searchPoke   = getStr('search_poke');
$searchItem   = getStr('search_item');
$searchAttack = getStr('search_attack');
$searchBattle = getStr('search_battle');

$page     = max(1, getInt('page', 1));
$pageSize = min(100, max(10, getInt('pagesize', 30)));
$offset   = ($page - 1) * $pageSize;

/* ---------------------------
   ПАГИНАЦИЯ / ХЛЕБНЫЕ КРОШКИ
----------------------------*/
function renderPagination($total, $page, $pageSize){
    $totalPages = max(1, (int)ceil($total / $pageSize));
    if ($totalPages <= 1) return '';
    $page = max(1, min($page, $totalPages));
    $qs = $_GET; // текущий набор параметров
    $html = '<div class="pager">';
    $build = function($p) use (&$qs){
        $qs['page'] = $p;
        return '?'.http_build_query($qs);
    };
    $html .= '<a class="pg'.($page==1?' disabled':'').'" href="'.($page==1?'#':$build($page-1)).'">‹</a>';
    $start = max(1, $page-2);
    $end   = min($totalPages, $page+2);
    if($start>1) { $html.='<a class="pg" href="'.$build(1).'">1</a>'; if($start>2)$html.='<span class="dots">…</span>'; }
    for($i=$start;$i<=$end;$i++){
        $html .= '<a class="pg'.($i==$page?' active':'').'" href="'.($i==$page?'#':$build($i)).'">'.$i.'</a>';
    }
    if($end<$totalPages){ if($end<$totalPages-1)$html.='<span class="dots">…</span>'; $html.='<a class="pg" href="'.$build($totalPages).'">'.$totalPages.'</a>'; }
    $html .= '<a class="pg'.($page==$totalPages?' disabled':'').'" href="'.($page==$totalPages?'#':$build($page+1)).'">›</a>';
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Журнал логов игроков</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no,user-scalable=no">
    <link rel="icon" href="/favicon.ico">
    <style>
        :root{
            --bg1:#f3effa;
            --bg2:#efe9ff;
            --card:#ffffffee;
            --amethyst:#a180c2;
            --amethyst-2:#795cb2;
            --ink:#5f4281;
            --soft:#bba3e4;
            --line:#e7ddfa;
            --success:#66c2a6;
            --danger:#dd6b7b;
            --chip:#efe7ff;
        }
        *{box-sizing:border-box}
        html,body{height:100%;margin:0}
        body{
            background: radial-gradient(1400px 640px at 10% -5%, var(--bg2) 0, transparent 60%),
                        radial-gradient(1000px 560px at 90% 0, var(--bg1) 0, transparent 65%),
                        #f7f6fb;
            color:var(--ink);
            font:16px/1.45 Nunito, Arial, sans-serif;
            letter-spacing:.02em;
        }
        .container{
            width:96%;max-width:1100px;margin:28px auto 40px auto;
            background:var(--card);border-radius:20px;
            box-shadow:0 8px 40px #6b4aa80f, 0 2px 10px #6b4aa808;
            padding:20px 18px;
        }
        header{
            display:flex;align-items:center;gap:12px;justify-content:space-between;margin:6px 6px 16px 6px;
        }
        .brand{
            display:flex;align-items:center;gap:12px;
        }
        .brand .logo{
            width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#e7dbff,#c6b4ee);
            box-shadow:0 6px 20px #7e5db420;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900
        }
        .brand h1{margin:0;color:var(--amethyst);font-size:22px;letter-spacing:.05em}
        .tabs{
            display:flex;gap:6px;flex-wrap:wrap;align-items:flex-end;margin:0 6px 14px 6px
        }
        .tab{
            border:1.5px solid var(--line);border-bottom:none;border-radius:10px 10px 0 0;
            padding:10px 18px;background:#f7f4ff;color:var(--amethyst);
            cursor:pointer;font-weight:700;box-shadow:0 3px 10px #00000008;
            transition:.15s;
        }
        .tab:hover{filter:brightness(102%)}
        .tab.active{background:linear-gradient(135deg,#e8dcff,#cdbcf1);color:var(--ink)}
        .tools{
            display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin:8px 6px 10px 6px
        }
        .search{
            display:flex;gap:8px;align-items:center;flex:1;
        }
        .input{
            appearance:none;border:2px solid var(--amethyst);background:#f6f0ff;padding:10px 12px;border-radius:10px;
            min-width:250px;max-width:460px;flex:1;color:var(--ink);outline:none;transition:.15s;font-size:16px
        }
        .input:focus{border-color:var(--amethyst-2);background:#fbf8ff}
        .btn{
            border:none;background:linear-gradient(135deg,#b699e7,#8f73c8);color:#fff;font-weight:800;
            padding:10px 16px;border-radius:10px;cursor:pointer;box-shadow:0 8px 18px #6b4aa81a;transition:.15s;
        }
        .btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px #6b4aa822}
        .btn:disabled{opacity:.55;cursor:not-allowed;transform:none;box-shadow:none}
        .btn-ghost{
            background:#efe8ff;color:var(--amethyst-2);font-weight:800;border:1.5px solid #e3d9ff;
        }
        .list{
            display:grid;grid-template-columns:1fr;gap:12px;margin-top:6px
        }
        .card{
            background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 14px 12px 14px;
            box-shadow:0 4px 18px #6b4aa80e;
        }
        .row{
            display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:space-between
        }
        .title{
            display:flex;align-items:center;gap:10px;font-weight:900;color:var(--ink);
        }
        .badge{
            padding:4px 10px;border-radius:999px;background:var(--chip);border:1px solid #e3dafc;color:var(--amethyst-2);
            font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase
        }
        .meta{color:#9b86c4;font-weight:700}
        .kv{display:flex;gap:10px;align-items:center;color:#8264a8}
        .objects{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}
        .obj{
            display:flex;gap:10px;align-items:center;background:#faf8ff;border:1px solid var(--line);border-radius:10px;padding:7px 9px
        }
        .i{width:42px;height:42px;border-radius:8px;background:#fff;border:1px solid #e8e0ff;background-size:cover;background-position:center}
        .poke-link{color:var(--amethyst-2);font-weight:800;text-decoration:underline;cursor:pointer}
        .empty{
            text-align:center;border:1px dashed #d9cff2;background:#fbf9ff;border-radius:16px;padding:22px;color:#8d77b9
        }
        mark{background:#fff4d0;padding:1px 3px;border-radius:4px}
        .pager{
            display:flex;gap:6px;justify-content:center;align-items:center;margin:18px 4px 6px 4px
        }
        .pg{
            display:inline-flex;min-width:36px;height:36px;border-radius:10px;border:1px solid var(--line);
            align-items:center;justify-content:center;background:#faf8ff;color:var(--amethyst-2);text-decoration:none;font-weight:800
        }
        .pg.active{background:linear-gradient(135deg,#e8dcff,#cdbcf1);color:var(--ink)}
        .pg.disabled{pointer-events:none;opacity:.5}
        .dots{color:#b5a2d8}
        .toolbar{
            display:flex;gap:8px;align-items:center
        }
        .counter{
            color:#9b86c4;font-weight:700
        }
        .floating-top{
            position:fixed;bottom:20px;right:18px;background:linear-gradient(135deg,#b699e7,#8f73c8);
            width:44px;height:44px;border-radius:12px;color:#fff;display:flex;align-items:center;justify-content:center;
            box-shadow:0 10px 22px #6b4aa826;cursor:pointer
        }
        #pokemon-info{
            display:none;background:#efe7ff;border:2px solid #dfd0ff;border-radius:14px;padding:14px;margin-top:12px;color:var(--ink)
        }
        /* мобильные */
        @media(max-width:760px){
            .tools{flex-direction:column;align-items:stretch}
            .input{min-width:unset;max-width:unset}
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="brand">
            <div class="logo">LG</div>
            <h1>Журнал логов игроков</h1>
        </div>
        <div class="toolbar">
            <a class="btn btn-ghost" href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>">⟳ Обновить</a>
        </div>
    </header>

    <div class="tabs">
        <?php foreach($tabs as $key=>$name): ?>
            <a class="tab<?= $currentTab==$key?' active':'' ?>"
               href="?<?= http_build_query(array_merge($_GET, ['tab'=>$key, 'page'=>1])) ?>"><?= e($name) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="tools">
        <?php
        // Подбираем поиск под активный таб
        $ph = [
            'trade'   => 'Поиск по логину или содержимому обмена…',
            'pokemon' => 'Поиск по логину / покемону…',
            'item'    => 'Поиск по логину / предмету…',
            'attack'  => 'Поиск по логину / атаке…',
            'battle'  => 'Поиск по номеру боя / участнику…',
        ][$currentTab];

        $currentSearchName = [
            'trade'   => 'search_trade',
            'pokemon' => 'search_poke',
            'item'    => 'search_item',
            'attack'  => 'search_attack',
            'battle'  => 'search_battle',
        ][$currentTab];

        $currentSearchVal = $$currentSearchName;
        ?>
        <form class="search" method="get">
            <?php foreach($_GET as $k=>$v){ if($k!==$currentSearchName) echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">'; } ?>
            <input class="input" type="text" name="<?= e($currentSearchName) ?>" value="<?= e($currentSearchVal) ?>" placeholder="<?= e($ph) ?>">
            <button class="btn" type="submit">🔍 Найти</button>
        </form>
        <div class="counter">
            Размер страницы:
            <a class="pg<?= $pageSize==20?' active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['pagesize'=>20, 'page'=>1])) ?>">20</a>
            <a class="pg<?= $pageSize==30?' active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['pagesize'=>30, 'page'=>1])) ?>">30</a>
            <a class="pg<?= $pageSize==50?' active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['pagesize'=>50, 'page'=>1])) ?>">50</a>
            <a class="pg<?= $pageSize==100?' active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['pagesize'=>100,'page'=>1])) ?>">100</a>
        </div>
    </div>

    <div class="list" id="log-list">
    <?php
    /* ---------------------------------
       ВЫВОД КАРТОЧЕК ПО ТЕКУЩЕМУ ТАБУ
    ----------------------------------*/
    if ($currentTab === 'trade') {
        $where = "log_game.type='trade'";
        $types = '';
        $params = [];
        if ($searchTrade !== '') {
            $where .= " AND (u.login LIKE ? OR log_game.info LIKE ?)";
            $types = 'ss';
            $like = qLike($searchTrade);
            $params = [$like, $like];
        }
        $row = fetchOnePrepared($mysqli, "SELECT COUNT(*) c FROM log_game INNER JOIN users u ON log_game.user_id=u.id WHERE $where", $types, $params);
        $total = (int)($row['c'] ?? 0);

        $sql = "SELECT log_game.* , u.login FROM log_game INNER JOIN users u ON log_game.user_id = u.id
                WHERE $where ORDER BY log_game.id DESC LIMIT ? OFFSET ?";
        $rows = fetchAllPrepared($mysqli, $sql, $types.'ii', array_merge($params, [$pageSize, $offset]));

        if (!$rows) {
            echo '<div class="empty">В обменах пока пусто.</div>';
        } else {
            foreach ($rows as $log) {
                $info = json_decode($log['info'] ?? '[]', true) ?: [];
                $user1 = ['id'=>$log['user_id'], 'login'=>$log['login'] ?? '—'];
                $user2 = isset($info['user_to']) ? getUserInfo($mysqli, (int)$info['user_to']) : null;

                echo '<div class="card">';
                echo '  <div class="row">';
                echo '      <div class="title">🔁 Обмен #'.(int)$log['id'].' <span class="badge">trade</span></div>';
                echo '      <div class="meta">'.formatDateSmart($log['date']).'</div>';
                echo '  </div>';
                echo '  <div class="kv">';
                echo '      <span><strong>'.hl($user1['login'], $searchTrade).'</strong></span>';
                echo '      <span>→</span>';
                echo '      <span><strong>'.($user2? hl($user2['login'], $searchTrade) : 'не указан').'</strong></span>';
                echo '  </div>';

                if (isset($info['objects']) && is_array($info['objects']) && count($info['objects'])) {
                    echo '<div class="objects">';
                    foreach ($info['objects'] as $obj) {
                        if (($obj['type'] ?? '') === 'poke') {
                            $img = "/img/pokemons/animation/".(int)$obj['number'].".png";
                            echo '<div class="obj">';
                            echo '  <div class="i" style="background-image:url('.e($img).')"></div>';
                            echo '  <div>';
                            echo '    <div><a class="poke-link" onclick="showPokemonInfo('.(int)$obj['id'].')">'.hl($obj['name'] ?? 'Покемон', $searchTrade).'</a></div>';
                            echo '    <small>ID: '.(int)$obj['id'].' · №'.(int)$obj['number'].'</small>';
                            echo '  </div>';
                            echo '</div>';
                        } elseif (($obj['type'] ?? '') === 'item') {
                            $img = "/img/world/items/little/".(int)$obj['id'].".png";
                            echo '<div class="obj">';
                            echo '  <div class="i" style="background-image:url('.e($img).')"></div>';
                            echo '  <div>';
                            echo '    <div>'.hl($obj['name'] ?? 'Предмет', $searchTrade).'</div>';
                            echo '    <small>× '.(int)$obj['count'].'</small>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                } else {
                    echo '<div class="empty" style="margin-top:10px;padding:12px;">Нет детализированных объектов в записи обмена.</div>';
                }
                echo '</div>';
            }
            echo renderPagination($total, $page, $pageSize);
        }
    }

    elseif ($currentTab === 'pokemon') {
        $where = "type='pok'";
        $types = '';
        $params = [];
        if ($searchPoke !== '') {
            $where .= " AND (pok LIKE ? OR pok_id LIKE ?)";
            $types = 'ss';
            $like = qLike($searchPoke);
            $params = [$like, $like];
        }
        $row   = fetchOnePrepared($mysqli, "SELECT COUNT(*) c FROM log_pokemon WHERE $where", $types, $params);
        $total = (int)($row['c'] ?? 0);

        $sql = "SELECT * FROM log_pokemon WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?";
        $rows = fetchAllPrepared($mysqli, $sql, $types.'ii', array_merge($params, [$pageSize, $offset]));

        if (!$rows) {
            echo '<div class="empty">Нет записей о покемонах.</div>';
        } else {
            foreach ($rows as $l) {
                $user = getUserInfo($mysqli, (int)$l['user_id']);
                echo '<div class="card">';
                echo '  <div class="row">';
                echo '      <div class="title">🧬 Выдан покемон <b>#'.hl($l['pok'], $searchPoke).'</b> <span class="badge">pokemon</span></div>';
                echo '      <div class="meta">'.formatDateSmart($l['date']).'</div>';
                echo '  </div>';
                echo '  <div class="kv">Тренер: <strong>'.hl($user['login'], $searchPoke).'</strong> · ID тренера: '.(int)$user['id'].' · ID покемона: <strong>'.(int)$l['pok_id'].'</strong></div>';
                echo '</div>';
            }
            echo renderPagination($total, $page, $pageSize);
        }
    }

    elseif ($currentTab === 'item') {
        $where = "type='items'";
        $types = '';
        $params = [];
        if ($searchItem !== '') {
            $where .= " AND (pok LIKE ? OR pok_id LIKE ?)";
            $types = 'ss';
            $like = qLike($searchItem);
            $params = [$like, $like];
        }
        $row   = fetchOnePrepared($mysqli, "SELECT COUNT(*) c FROM log_pokemon WHERE $where", $types, $params);
        $total = (int)($row['c'] ?? 0);

        $sql = "SELECT * FROM log_pokemon WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?";
        $rows = fetchAllPrepared($mysqli, $sql, $types.'ii', array_merge($params, [$pageSize, $offset]));

        if (!$rows) {
            echo '<div class="empty">Нет записей о предметах.</div>';
        } else {
            foreach ($rows as $l) {
                $user = getUserInfo($mysqli, (int)$l['pok_id']);
                echo '<div class="card">';
                echo '  <div class="row">';
                echo '      <div class="title">🎁 Выдан предмет <b>'.hl($l['pok'], $searchItem).'</b> <span class="badge">item</span></div>';
                echo '      <div class="meta">'.formatDateSmart($l['date']).'</div>';
                echo '  </div>';
                echo '  <div class="kv">Тренер: <strong>'.hl($user['login'], $searchItem).'</strong> · ID тренера: '.(int)$user['id'].'</div>';
                echo '</div>';
            }
            echo renderPagination($total, $page, $pageSize);
        }
    }

    elseif ($currentTab === 'attack') {
        $where = "type='attack'";
        $types = '';
        $params = [];
        if ($searchAttack !== '') {
            $where .= " AND (pok LIKE ? OR pok_id LIKE ?)";
            $types = 'ss';
            $like = qLike($searchAttack);
            $params = [$like, $like];
        }
        $row   = fetchOnePrepared($mysqli, "SELECT COUNT(*) c FROM log_pokemon WHERE $where", $types, $params);
        $total = (int)($row['c'] ?? 0);

        $sql = "SELECT * FROM log_pokemon WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?";
        $rows = fetchAllPrepared($mysqli, $sql, $types.'ii', array_merge($params, [$pageSize, $offset]));

        if (!$rows) {
            echo '<div class="empty">Нет записей об атаках.</div>';
        } else {
            foreach ($rows as $l) {
                $user = getUserInfo($mysqli, (int)$l['pok_id']);
                echo '<div class="card">';
                echo '  <div class="row">';
                echo '      <div class="title">✨ Выдана атака <b>'.hl($l['pok'], $searchAttack).'</b> <span class="badge">attack</span></div>';
                echo '      <div class="meta">'.formatDateSmart($l['date']).'</div>';
                echo '  </div>';
                echo '  <div class="kv">Тренер: <strong>'.hl($user['login'], $searchAttack).'</strong> · ID тренера: '.(int)$user['id'].' · Покемон ID: <strong>'.(int)$l['pok_id'].'</strong></div>';
                echo '</div>';
            }
            echo renderPagination($total, $page, $pageSize);
        }
    }

    elseif ($currentTab === 'battle') {
        $where = "1=1";
        $types = '';
        $params = [];
        if ($searchBattle !== '') {
            $where .= " AND (battle LIKE ? OR user1 IN (SELECT id FROM users WHERE login LIKE ?) OR user2 IN (SELECT id FROM users WHERE login LIKE ?))";
            $types = 'sss';
            $like = qLike($searchBattle);
            $params = [$like, $like, $like];
        }
        $row   = fetchOnePrepared($mysqli, "SELECT COUNT(*) c FROM battle_end WHERE $where", $types, $params);
        $total = (int)($row['c'] ?? 0);

        $sql = "SELECT * FROM battle_end WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?";
        $rows = fetchAllPrepared($mysqli, $sql, $types.'ii', array_merge($params, [$pageSize, $offset]));

        if (!$rows) {
            echo '<div class="empty">Нет записей о боях.</div>';
        } else {
            foreach ($rows as $log) {
                $user1  = getUserInfo($mysqli, (int)$log['user1']);
                $user2  = getUserInfo($mysqli, (int)$log['user2']);
                $winner = ((int)$log['win'] === (int)$log['user1']) ? $user1 : $user2;

                echo '<div class="card">';
                echo '  <div class="row">';
                echo '      <div class="title">⚔️ Бой №<b>'.hl($log['battle'], $searchBattle).'</b> <span class="badge">battle</span></div>';
                echo '      <div class="meta">'.formatDateSmart($log['date']).'</div>';
                echo '  </div>';
                echo '  <div class="kv">'.hl($user1['login'], $searchBattle).' vs '.hl($user2['login'], $searchBattle).' — <strong>победил '.e($winner['login']).'</strong></div>';
                echo '</div>';
            }
            echo renderPagination($total, $page, $pageSize);
        }
    }
    ?>
    </div>

    <!-- Информация о покемоне -->
    <div id="pokemon-info"></div>
</div>

<a class="floating-top" title="Наверх" onclick="window.scrollTo({top:0,behavior:'smooth'})">▲</a>

<script>
function showPokemonInfo(pokeId) {
    fetch('/get_pokemon_info.php?id=' + encodeURIComponent(pokeId))
        .then(r => r.text())
        .then(html => {
            const box = document.getElementById('pokemon-info');
            box.innerHTML = html;
            box.style.display = 'block';
            box.scrollIntoView({behavior:'smooth', block:'start'});
        })
        .catch(() => {
            const box = document.getElementById('pokemon-info');
            box.innerHTML = '<div class="empty">Не удалось получить информацию о покемоне.</div>';
            box.style.display = 'block';
        });
}

// «Быстрый поиск»: Enter — отправка, Esc — очистка
document.querySelectorAll('.input').forEach(inp=>{
    inp.addEventListener('keydown', (e)=>{
        if(e.key==='Escape'){ e.target.value=''; }
    });
});
</script>
</body>
</html>

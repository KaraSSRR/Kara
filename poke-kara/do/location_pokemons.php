<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}

session_start();
$user = $mysqli->query('SELECT * FROM users WHERE id = '.intval($_SESSION['id']))->fetch_assoc();
if(!$user) exit('<div class="pve-disabled-msg">Ошибка: пользователь не найден.</div>');

$location_id = intval($user['location']);
$a = $mysqli->query('SELECT * FROM pokemons_location WHERE hide_loc != 1 AND location_id = '.$location_id.' ORDER BY basenum ASC');

if($user['pve'] != 0){
    echo '<div class="pve-disabled-msg">Покемоны на локации доступны только вне PvE!</div>';
    exit;
}

function gradation_chance($chance, $only_text = false) {
    $chance = floatval($chance);
    if ($chance >= 80) return $only_text ? "очень часто" : "<span class='chance-text chance-very-often'>очень часто</span>";
    if ($chance >= 50) return $only_text ? "часто" : "<span class='chance-text chance-often'>часто</span>";
    if ($chance >= 20) return $only_text ? "редко" : "<span class='chance-text chance-rare'>редко</span>";
    if ($chance >= 5)  return $only_text ? "очень редко" : "<span class='chance-text chance-very-rare'>очень редко</span>";
    if ($chance >= 1)  return $only_text ? "крайне редко" : "<span class='chance-text chance-extreme-rare'>крайне редко</span>";
    return $only_text ? "?" : "<span class='chance-text chance-unknown'>?</span>";
}

// Для красивого времени, например, 08:00 - 19:00
function format_time_range($from, $to) {
    $from = trim($from);
    $to = trim($to);
    // Если оба значения не пустые и похожи на время
    if (preg_match('/^\d{1,2}:\d{2}$/', $from) && preg_match('/^\d{1,2}:\d{2}$/', $to)) {
        return $from . ' — ' . $to;
    }
    // Если только числа (например 8 19)
    if (is_numeric($from) && is_numeric($to)) {
        $from = str_pad($from, 2, "0", STR_PAD_LEFT) . ":00";
        $to   = str_pad($to, 2, "0", STR_PAD_LEFT) . ":00";
        return $from . ' — ' . $to;
    }
    // Иначе просто склеиваем
    return htmlspecialchars($from . ' — ' . $to);
}

echo '<div class="LocPokeModal-list">';
echo '<details class="tree-nav__item is-expandable" open>
    <summary class="tree-nav__item-title">Дикие покемоны на локации</summary>
    <div class="LocBook">';

while($pok = $a->fetch_assoc()){
    $b_pok = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$pok['basenum'])->fetch_assoc();
    $lvl = explode(',', $pok['lvl']);
    $catch = $pok['catch'] == 0 ? 'noCatch' : '';
    $chance = isset($pok['chance']) ? floatval($pok['chance']) : 0;
    $chance_text = gradation_chance($chance);

    $time_range = format_time_range($pok['timezone'], $pok['timezone_b']);

    echo '
        <div class="PokemonBook '.$catch.'">
            <img src="/img/pokemons/animation/'.numbPok($b_pok['id']).'.png" alt="'.htmlspecialchars($b_pok['name_rus']).'" onclick="openDex('.$b_pok['id'].')">
            <div class="poke-main">
                <div class="poke-title">
                    <b>#'.numbPok($b_pok['id']).' '.$b_pok['name_rus'].'</b>
                    <span class="poke-lvl">'.$lvl[0].'-'.$lvl[1].' ур.</span>
                </div>
                <div class="poke-row">
                    <span class="chance">'.$chance_text.'</span>
                    <span class="time"><i class="far fa-clock"></i> '.$time_range.'</span>
                </div>'.
                (empty($pok['text_drop']) ? '' : '<div class="drop">'.$pok['text_drop'].'</div>').
                (empty($pok['text_catch_condition']) ? '' : '<div class="catch_condition">'.$pok['text_catch_condition'].'</div>').
            '</div>
        </div>';
}
echo '</div></details>';
echo '</div>';
?>
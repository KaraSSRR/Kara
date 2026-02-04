<?php
// encyclopedia/_inc/functions.php

// Polyfills for PHP 7.x
if (!function_exists("str_contains")) {
    function str_contains($haystack, $needle) {
        return $needle === "" || strpos((string)$haystack, (string)$needle) !== false;
    }
}
if (!function_exists("str_starts_with")) {
    function str_starts_with($haystack, $needle) {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === "") return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}


// Polyfill for PHP < 7: random_bytes (CSRF tokens, etc.)
if (!function_exists("random_bytes")) {
    function random_bytes($length) {
        $length = (int)$length;
        if ($length <= 0) return '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && strlen($bytes) === $length) return $bytes;
        }
        // Fallback (not cryptographically secure, but sufficient for non-critical tokens)
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= chr(mt_rand(0, 255));
        }
        return $out;
    }
}


function enc_h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function enc_int($v, $default = 0) {
    if ($v === null) return (int)$default;
    if (is_numeric($v)) return (int)$v;
    return (int)$default;
}

/**
 * Ensures a string is valid UTF-8. If not, tries CP1251 -> UTF-8 conversion.
 */
function enc_ensure_utf8($s) {
    if ($s === '') return '';
    if (@preg_match('//u', $s)) return $s;
    if (function_exists('iconv')) {
        $converted = @iconv('CP1251', 'UTF-8//IGNORE', $s);
        if ($converted !== false && $converted !== '') return $converted;
    }
    return $s;
}

function enc_redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    // Fallback when headers already sent (prevents "Cannot modify header" warnings)
    $u = (string)$url;
    $uHtml = htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "<!doctype html><meta charset=\"utf-8\"><meta http-equiv=\"refresh\" content=\"0;url={$uHtml}\">";
    echo "<script>location.replace(" . json_encode($u) . ");</script>";
    exit;
}

function enc_url($path = '/') {
    $path = '/' . ltrim($path, '/');
    return rtrim(ENC_BASE, '/') . $path;
}

function enc_abs_url($path) {
    // For local links only
    if (preg_match('~^https?://~i', $path)) return $path;
    return $path;
}

function enc_csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['enc_csrf'])) {
        $_SESSION['enc_csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['enc_csrf'];
}

function enc_csrf_check() {
    $t = (string)((isset($_POST['csrf']) ? $_POST['csrf'] : ''));
    if ($t === '' || !hash_equals((string)((isset($_SESSION['enc_csrf']) ? $_SESSION['enc_csrf'] : '')), $t)) {
        http_response_code(403);
        die('Ошибка безопасности');
    }
}

function enc_table_exists(mysqli $mysqli,$table) {
    $table = $mysqli->real_escape_string($table);
    $q = $mysqli->query("SHOW TABLES LIKE '{$table}'");
    return ($q && $q->num_rows > 0);
}



function enc_query_count($q) {
    if (!$q) return 0;
    $row = $q->fetch_assoc();
    if (!$row) return 0;
    return isset($row['c']) ? (int)$row['c'] : 0;
}

function enc_table_has_column(mysqli $mysqli, $table, $column) {
    static $cache = array();
    $key = $table . '.' . $column;
    if (isset($cache[$key])) return $cache[$key];

    $tableEsc = $mysqli->real_escape_string($table);
    $colEsc = $mysqli->real_escape_string($column);

    $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableEsc}' AND COLUMN_NAME = '{$colEsc}' LIMIT 1";
    $q = $mysqli->query($sql);
    $cache[$key] = ($q && $q->num_rows > 0);
    return $cache[$key];
}


// --- mysqli_stmt get_result() compatibility (PHP 5.6 without mysqlnd) ---
function enc_stmt_fetch_all_assoc(mysqli_stmt $stmt) {
    // mysqlnd available
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if (!$res) return array();
        $rows = array();
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        return $rows;
    }

    $meta = $stmt->result_metadata();
    if (!$meta) return array();

    $fields = $meta->fetch_fields();
    if (!$fields) return array();

    $row = array();
    $bind = array();
    foreach ($fields as $field) {
        $row[$field->name] = null;
        $bind[] = & $row[$field->name];
    }
    call_user_func_array(array($stmt, 'bind_result'), $bind);

    $out = array();
    while ($stmt->fetch()) {
        $r = array();
        foreach ($row as $k => $v) $r[$k] = $v;
        $out[] = $r;
    }
    return $out;
}

function enc_stmt_fetch_assoc(mysqli_stmt $stmt) {
    $rows = enc_stmt_fetch_all_assoc($stmt);
    return (isset($rows[0]) ? $rows[0] : null);
}


/**
 * bind_param() helper that works with dynamic arrays on PHP 5.6 (bind_param requires references)
 */
function enc_stmt_bind_param(mysqli_stmt $stmt, $types, array $params) {
    $types = (string)$types;
    $refs = array();
    $refs[] = & $types;
    // Make sure params are referenced
    foreach ($params as $k => $v) {
        $refs[] = & $params[$k];
    }
    return call_user_func_array(array($stmt, 'bind_param'), $refs);
}

function enc_build_has_ts_cols(mysqli $mysqli) {
    static $v = null;
    if ($v !== null) return $v;
    $v = enc_table_has_column($mysqli, 'enc_builds', 'created_at_ts') && enc_table_has_column($mysqli, 'enc_builds', 'updated_at_ts');
    return $v;
}


function enc_is_installed(mysqli $mysqli) {
    // Core tables only. Some optional features (build indexes / popularity) can be missing
    // if SQL was not fully applied; module should still render without fatal errors.
    return enc_table_exists($mysqli, 'enc_pages') && enc_table_exists($mysqli, 'enc_builds');
}

function enc_has_build_index(mysqli $mysqli) {
    return enc_table_exists($mysqli, 'enc_build_pokemon');
}

function enc_has_popularity(mysqli $mysqli) {
    return enc_table_exists($mysqli, 'enc_pokemon_popularity');
}

// --- Popularity helpers (supports both legacy and v2 schemas) ---
function enc_popularity_count_col(mysqli $mysqli) {
    static $col = null;
    if ($col !== null) return $col;
    if (enc_table_has_column($mysqli, 'enc_pokemon_popularity', 'builds_count')) {
        $col = 'builds_count';
    } elseif (enc_table_has_column($mysqli, 'enc_pokemon_popularity', 'uses_count')) {
        $col = 'uses_count';
    } else {
        // Fallback: default legacy name
        $col = 'uses_count';
    }
    return $col;
}

function enc_popularity_has_ts(mysqli $mysqli) {
    static $has = null;
    if ($has !== null) return $has;
    $has = enc_table_has_column($mysqli, 'enc_pokemon_popularity', 'updated_at_ts');
    return $has;
}

function enc_popularity_upsert(mysqli $mysqli, $pokemonId, $count) {
    $pokemonId = (int)$pokemonId;
    $count = (int)$count;
    if ($pokemonId <= 0) return;

    $col = enc_popularity_count_col($mysqli);
    $hasTs = enc_popularity_has_ts($mysqli);
    $now = time();

    if ($hasTs) {
        $sql = "INSERT INTO enc_pokemon_popularity (pokemon_id, {$col}, updated_at_ts) VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE {$col}=VALUES({$col}), updated_at_ts=VALUES(updated_at_ts)";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iii', $pokemonId, $count, $now);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Keep legacy updated_at DATETIME if it exists, otherwise just update count
        $hasUpdatedAt = enc_table_has_column($mysqli, 'enc_pokemon_popularity', 'updated_at');
        if ($hasUpdatedAt) {
            $sql = "INSERT INTO enc_pokemon_popularity (pokemon_id, {$col}) VALUES (?,?)
                    ON DUPLICATE KEY UPDATE {$col}=VALUES({$col}), updated_at=CURRENT_TIMESTAMP";
        } else {
            $sql = "INSERT INTO enc_pokemon_popularity (pokemon_id, {$col}) VALUES (?,?)
                    ON DUPLICATE KEY UPDATE {$col}=VALUES({$col})";
        }
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $pokemonId, $count);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Parse build team_json into exactly 6 normalized slots.
 * Each slot includes pokemon_id and form (other keys may exist, but are optional).
 */

/**
 * Stat keys used across EV/IV/nature calculations.
 */
function enc_stat_keys() {
    return array('hp','atk','def','satk','sdef','spd');
}

/**
 * Russian nature names and stat modifiers.
 * Keys are lowercase English nature ids.
 */
function enc_natures_data() {
    static $d = null;
    if ($d !== null) return $d;

    $d = array(
        // Neutral
        'bashful' => array('ru' => 'Застенчивый', 'plus' => null, 'minus' => null),
        'docile'  => array('ru' => 'Послушный',    'plus' => null, 'minus' => null),
        'hardy'   => array('ru' => 'Выносливый',   'plus' => null, 'minus' => null),
        'quirky'  => array('ru' => 'Изворотливый', 'plus' => null, 'minus' => null),
        'serious' => array('ru' => 'Серьёзный',    'plus' => null, 'minus' => null),

        // Attack+
        'lonely'  => array('ru' => 'Одинокий',     'plus' => 'atk',  'minus' => 'def'),
        'adamant' => array('ru' => 'Стойкий',      'plus' => 'atk',  'minus' => 'satk'),
        'naughty' => array('ru' => 'Озорной',      'plus' => 'atk',  'minus' => 'sdef'),
        'brave'   => array('ru' => 'Храбрый',      'plus' => 'atk',  'minus' => 'spd'),

        // Defense+
        'bold'    => array('ru' => 'Смелый',       'plus' => 'def',  'minus' => 'atk'),
        'impish'  => array('ru' => 'Злой',         'plus' => 'def',  'minus' => 'satk'),
        'lax'     => array('ru' => 'Распущенный',  'plus' => 'def',  'minus' => 'sdef'),
        'relaxed' => array('ru' => 'Расслабленный','plus' => 'def',  'minus' => 'spd'),

        // Sp.Atk+
        'modest'  => array('ru' => 'Скромный',     'plus' => 'satk', 'minus' => 'atk'),
        'mild'    => array('ru' => 'Мягкий',       'plus' => 'satk', 'minus' => 'def'),
        'rash'    => array('ru' => 'Безрассудный', 'plus' => 'satk', 'minus' => 'sdef'),
        'quiet'   => array('ru' => 'Тихий',        'plus' => 'satk', 'minus' => 'spd'),

        // Sp.Def+
        'calm'    => array('ru' => 'Спокойный',    'plus' => 'sdef', 'minus' => 'atk'),
        'gentle'  => array('ru' => 'Нежный',       'plus' => 'sdef', 'minus' => 'def'),
        'careful' => array('ru' => 'Осторожный',   'plus' => 'sdef', 'minus' => 'satk'),
        'sassy'   => array('ru' => 'Нахальный',    'plus' => 'sdef', 'minus' => 'spd'),

        // Speed+
        'timid'   => array('ru' => 'Робкий',       'plus' => 'spd',  'minus' => 'atk'),
        'hasty'   => array('ru' => 'Поспешный',    'plus' => 'spd',  'minus' => 'def'),
        'jolly'   => array('ru' => 'Весёлый',      'plus' => 'spd',  'minus' => 'satk'),
        'naive'   => array('ru' => 'Наивный',      'plus' => 'spd',  'minus' => 'sdef'),
    );

    return $d;
}

function enc_sanitize_nature($nature) {
    $nature = strtolower(trim((string)$nature));
    $nature = preg_replace('~[^a-z_]+~', '', $nature);
    $d = enc_natures_data();
    return (isset($d[$nature]) ? $nature : 'hardy');
}

function enc_nature_ru($nature) {
    $d = enc_natures_data();
    $k = enc_sanitize_nature($nature);
    return isset($d[$k]) ? (string)$d[$k]['ru'] : (string)$k;
}

function enc_nature_multiplier($nature, $statKey) {
    $k = enc_sanitize_nature($nature);
    $d = enc_natures_data();
    $plus = isset($d[$k]['plus']) ? $d[$k]['plus'] : null;
    $minus = isset($d[$k]['minus']) ? $d[$k]['minus'] : null;

    $statKey = strtolower(trim((string)$statKey));
    if ($plus && $statKey === $plus) return 1.1;
    if ($minus && $statKey === $minus) return 0.9;
    return 1.0;
}

function enc_norm_ev_array($ev) {
    $out = array('hp'=>0,'atk'=>0,'def'=>0,'satk'=>0,'sdef'=>0,'spd'=>0);
    if (!is_array($ev)) $ev = array();

    foreach ($ev as $k => $v) {
        $k = strtolower(trim((string)$k));
        if ($k === 'spa' || $k === 'spatk' || $k === 'sp_atk') $k = 'satk';
        if ($k === 'spd' || $k === 'spe' || $k === 'speed') $k = 'spd';
        if ($k === 'spdef' || $k === 'sp_def' || $k === 'spddef') $k = 'sdef';
        if (!isset($out[$k])) continue;

        $n = enc_int($v, 0);
        if ($n < 0) $n = 0;
        if ($n > 252) $n = 252;
        $out[$k] = $n;
    }

    // enforce global limit 510 (reduce from last stats first)
    $sum = 0;
    foreach ($out as $n) $sum += (int)$n;
    if ($sum > 510) {
        $excess = $sum - 510;
        $order = array('spd','sdef','satk','def','atk','hp');
        foreach ($order as $k) {
            if ($excess <= 0) break;
            $cur = (int)$out[$k];
            if ($cur <= 0) continue;
            $dec = ($cur >= $excess) ? $excess : $cur;
            $out[$k] = $cur - $dec;
            $excess -= $dec;
        }
    }

    return $out;
}

function enc_norm_iv_array($iv) {
    $out = array('hp'=>31,'atk'=>31,'def'=>31,'satk'=>31,'sdef'=>31,'spd'=>31);
    if (!is_array($iv)) $iv = array();

    foreach ($iv as $k => $v) {
        $k = strtolower(trim((string)$k));
        if ($k === 'spa' || $k === 'spatk' || $k === 'sp_atk') $k = 'satk';
        if ($k === 'spe' || $k === 'speed') $k = 'spd';
        if ($k === 'spdef' || $k === 'sp_def' || $k === 'spddef') $k = 'sdef';
        if (!isset($out[$k])) continue;

        $n = enc_int($v, 31);
        if ($n < 0) $n = 0;
        if ($n > 31) $n = 31;
        $out[$k] = $n;
    }

    return $out;
}

function enc_parse_iv_code($code) {
    $code = trim((string)$code);
    if ($code === '') return null;

    // Accept "31/31/31/31/31/31" or with spaces/commas
    $code = str_replace(array(';', ',', '|', '\\\\', "\n", "\r", "\t"), '/', $code);
    $code = preg_replace('~\s+~', '', $code);
    $parts = explode('/', $code);
    $nums = array();
    foreach ($parts as $p) {
        if ($p === '') continue;
        if (!preg_match('~^-?\d+$~', $p)) return null;
        $nums[] = (int)$p;
    }
    if (count($nums) !== 6) return null;

    $keys = enc_stat_keys();
    $out = array();
    for ($i = 0; $i < 6; $i++) {
        $n = $nums[$i];
        if ($n < 0) $n = 0;
        if ($n > 31) $n = 31;
        $out[$keys[$i]] = $n;
    }
    return $out;
}

function enc_iv_code_from_array($iv) {
    $iv = enc_norm_iv_array($iv);
    $keys = enc_stat_keys();
    $nums = array();
    foreach ($keys as $k) $nums[] = (int)$iv[$k];
    return implode('/', $nums);
}


// --- Genes (IV) presentation ---
function enc_genes_line(array $iv) {
    $iv = enc_norm_iv_array($iv);
    return 'Гены: HP ' . (int)$iv['hp']
      . ' / Atk ' . (int)$iv['atk']
      . ' / Def ' . (int)$iv['def']
      . ' / SpA ' . (int)$iv['satk']
      . ' / SpD ' . (int)$iv['sdef']
      . ' / Spe ' . (int)$iv['spd'];
}

function enc_genes_code(array $iv) {
    $iv = enc_norm_iv_array($iv);
    return (int)$iv['hp'] . '/' . (int)$iv['atk'] . '/' . (int)$iv['def'] . '/' . (int)$iv['satk'] . '/' . (int)$iv['sdef'] . '/' . (int)$iv['spd'];
}

/**
 * Standard stat formulas (main games).
 */
function enc_calc_stat($base, $iv, $ev, $level, $nature, $statKey) {
    $base = enc_int($base, 0);
    $iv = enc_int($iv, 0);
    $ev = enc_int($ev, 0);
    $level = enc_int($level, 100);
    if ($level < 1) $level = 1;
    if ($level > 100) $level = 100;

    $statKey = strtolower(trim((string)$statKey));
    $nature = enc_sanitize_nature($nature);

    $evPart = (int)floor($ev / 4);
    if ($statKey === 'hp') {
        return (int)floor(((2*$base + $iv + $evPart) * $level) / 100) + $level + 10;
    }
    $val = (int)floor(((2*$base + $iv + $evPart) * $level) / 100) + 5;
    $mult = enc_nature_multiplier($nature, $statKey);
    return (int)floor($val * $mult);
}

/**
 * Parse build team_json into exactly 6 normalized slots.
 * Supports both legacy (v1) and advanced (v2) shapes.
 */
function enc_team_slots_from_json($teamJson) {
    $team = json_decode((string)$teamJson, true);

    $slots = array();
    if (is_array($team)) {
        if (isset($team['slots']) && is_array($team['slots'])) {
            $slots = $team['slots']; // v2
        } elseif (isset($team['team']) && is_array($team['team'])) {
            $slots = $team['team'];  // legacy
        } elseif (isset($team['pokemons']) && is_array($team['pokemons'])) {
            $slots = $team['pokemons']; // legacy variant
        } else {
            // JSON might be an array itself: [ {slot1}, ... ]
            $isList = (array_keys($team) === range(0, count($team) - 1));
            if ($isList) $slots = $team;
        }
    }

    $out = array();
    for ($i = 0; $i < 6; $i++) {
        // Some legacy payloads used keys 1..6
        $s = null;
        if (is_array($slots) && array_key_exists($i, $slots)) $s = $slots[$i];
        elseif (is_array($slots) && array_key_exists($i + 1, $slots)) $s = $slots[$i + 1];

        $s = is_array($s) ? $s : array();

        $level = enc_int((isset($s['level']) ? $s['level'] : (isset($s['lvl']) ? $s['lvl'] : 100)), 100);
        if ($level < 1) $level = 1;
        if ($level > 100) $level = 100;

        $nature = enc_sanitize_nature((isset($s['nature']) ? $s['nature'] : (isset($s['nat']) ? $s['nat'] : 'hardy')));

        $ev = (isset($s['ev']) ? $s['ev'] : (isset($s['evs']) ? $s['evs'] : array()));
        $ev = enc_norm_ev_array($ev);

        $iv = (isset($s['iv']) ? $s['iv'] : (isset($s['ivs']) ? $s['ivs'] : array()));
        $iv = enc_norm_iv_array($iv);

        $ivCodeRaw = '';
        if (isset($s['iv_code'])) $ivCodeRaw = (string)$s['iv_code'];
        elseif (isset($s['genocode'])) $ivCodeRaw = (string)$s['genocode'];
        elseif (isset($s['gene'])) $ivCodeRaw = (string)$s['gene'];

        $parsed = enc_parse_iv_code($ivCodeRaw);
        if (is_array($parsed)) $iv = enc_norm_iv_array($parsed);
        $ivCode = enc_iv_code_from_array($iv);

        $moves = array();
        if (isset($s['moves']) && is_array($s['moves'])) {
            foreach ($s['moves'] as $mv) {
                $mv = (int)$mv;
                if ($mv > 0) $moves[] = $mv;
            }
        } elseif (isset($s['attacks']) && is_array($s['attacks'])) {
            // legacy key name
            foreach ($s['attacks'] as $mv) {
                $mv = (int)$mv;
                if ($mv > 0) $moves[] = $mv;
            }
        }

        $out[] = array(
            'pokemon_id' => enc_int((isset($s['pokemon_id']) ? $s['pokemon_id'] : (isset($s['pokemon']) ? $s['pokemon'] : 0)), 0),
            'form' => enc_sanitize_form((isset($s['form']) ? $s['form'] : '')),
            'item_id' => enc_int((isset($s['item_id']) ? $s['item_id'] : (isset($s['item']) ? $s['item'] : 0)), 0),
            'ability_id' => enc_int((isset($s['ability_id']) ? $s['ability_id'] : (isset($s['ability']) ? $s['ability'] : 0)), 0),
            'moves' => $moves,
            'level' => $level,
            'nature' => $nature,
            'ev' => $ev,
            'iv' => $iv,
            'iv_code' => $ivCode,
        );
    }

    return $out;
}



function enc_generations() {
    $raw = json_decode(ENC_GENERATIONS, true);
    return is_array($raw) ? $raw : [];
}

function enc_gen_range($gen) {
    $gens = enc_generations();
    return (isset($gens[$gen]) ? $gens[$gen] : null);
}

function enc_pad3($id) {
    return str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}


function enc_item_little_src($itemId) {
    $id = enc_int($itemId, 0);
    if ($id <= 0) return enc_url('/assets/item_placeholder.svg');
    return '/img/world/items/little/' . enc_pad3($id) . '.png';
}

function enc_item_little_img($itemId, $alt = '', $class = 'item-mini') {
    $src = enc_item_little_src($itemId);
    $ph = enc_url('/assets/item_placeholder.svg');
    $alt = ($alt === null) ? '' : (string)$alt;
    $class = ($class === null) ? 'item-mini' : (string)$class;

    // Use onerror to fallback to placeholder if the actual sprite is missing.
    $onerr = "this.onerror=null;this.src='" . enc_h($ph) . "';";
    return '<img class="'.enc_h($class).'" src="'.enc_h($src).'" alt="'.enc_h($alt).'" onerror="'.enc_h($onerr).'">';
}


function enc_sanitize_form($form) {
    $form = (string)$form;
    $form = trim($form);
    if ($form === '' || $form === '0' || $form === 'normal') return '';
    // allow only a-z 0-9 _
    $form = strtolower($form);
    $form = preg_replace('~[^a-z0-9_]+~', '', $form);
    return (string)$form;
}

function enc_sprite_anim($id,$form = '') {
    $p = enc_pad3($id);
    $form = enc_sanitize_form($form);
    $name = $form !== '' ? "{$p}_{$form}.png" : "{$p}.png";
    return rtrim(ENC_SPRITE_ANIM_DIR, '/') . '/' . $name;
}

function enc_sprite_pokedex($id,$form = '') {
    $p = enc_pad3($id);
    $form = enc_sanitize_form($form);
    $name = $form !== '' ? "{$p}_{$form}.png" : "{$p}.png";
    return rtrim(ENC_SPRITE_POKEDEX_DIR, '/') . '/' . $name;
}

// Unified sprite URL helper.
// $kind: 'pokedex' (static) or 'anim' (animated).
function enc_sprite_url($pokemonId, $form = '', $kind = 'pokedex') {
    $kind = strtolower(trim((string)$kind));
    if ($kind === 'anim') return enc_sprite_anim((int)$pokemonId, $form);
    return enc_sprite_pokedex((int)$pokemonId, $form);
}

// Builds an onerror attribute that swaps an <img> src through a fallback chain.
// Requires window.encImgFallback (see assets/ency.js).
function enc_img_onerror_attr(array $fallbackUrls) {
    $urls = array();
    foreach ($fallbackUrls as $u) {
        $u = trim((string)$u);
        if ($u === '') continue;
        $urls[] = $u;
    }
    return "window.encImgFallback(this," . json_encode(array_values($urls)) . ");";
}

// Renders an <img> tag for a pokemon sprite with a safe fallback chain.
function enc_sprite_img($pokemonId, $form = '', $kind = 'pokedex', $size = 64, $class = '', $alt = '') {
    $pokemonId = (int)$pokemonId;
    $form = enc_sanitize_form($form);
    $kind = strtolower(trim((string)$kind));
    $size = enc_int($size, 64);
    if ($size < 16) $size = 16;
    if ($size > 256) $size = 256;

    $src = enc_sprite_url($pokemonId, $form, $kind);

    $fallbacks = array();
    if ($kind === 'anim') {
        // anim(form) -> pokedex(form) -> pokedex(base) -> placeholder
        if ($form !== '') $fallbacks[] = enc_sprite_pokedex($pokemonId, $form);
        $fallbacks[] = enc_sprite_pokedex($pokemonId, '');
    } else {
        // pokedex(form) -> pokedex(base) -> placeholder
        if ($form !== '') $fallbacks[] = enc_sprite_pokedex($pokemonId, '');
    }
    $fallbacks[] = ENC_SPRITE_PLACEHOLDER;

    $attrs = array();
    $attrs[] = 'src="' . enc_h($src) . '"';
    $attrs[] = 'width="' . enc_h($size) . '"';
    $attrs[] = 'height="' . enc_h($size) . '"';
    $attrs[] = 'loading="lazy"';
    $attrs[] = 'decoding="async"';
    $attrs[] = 'onerror="' . enc_h(enc_img_onerror_attr($fallbacks)) . '"';
    if (trim((string)$alt) !== '') $attrs[] = 'alt="' . enc_h($alt) . '"';
    else $attrs[] = 'alt=""';
    $cls = trim((string)$class);
    if ($cls !== '') $attrs[] = 'class="' . enc_h($cls) . '"';

    return '<img ' . implode(' ', $attrs) . '>';
}

function enc_build_visibility_label($visibility) {
    $visibility = strtolower(trim((string)$visibility));
    if ($visibility === 'public') return 'Публичная';
    if ($visibility === 'shared') return 'Доступ по приглашению';
    return 'Приватная';
}

function enc_build_visibility_badge($visibility) {
    $v = strtolower(trim((string)$visibility));
    if (!in_array($v, array('public','private','shared'), true)) $v = 'private';
    $label = enc_build_visibility_label($v);
    return '<span class="enc-badge enc-badge--visibility enc-badge--' . enc_h($v) . '">' . enc_h($label) . '</span>';
}

function enc_human_updated_at($ts) {
    $ts = (int)$ts;
    if ($ts <= 0) return '';
    $d = date('Y-m-d', $ts);
    $now = date('Y-m-d');
    $y = date('Y-m-d', time() - 86400);
    $t = date('H:i', $ts);
    if ($d === $now) return 'сегодня ' . $t;
    if ($d === $y) return 'вчера ' . $t;
    return date('d.m.Y', $ts);
}

function enc_plaintext_excerpt($text, $maxLen = 160) {
    $text = (string)$text;
    // strip bbcode-like tags and HTML
    $text = preg_replace('~\\[[^\\]]+\\]~', ' ', $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('~\\s+~u', ' ', trim($text));

    $maxLen = enc_int($maxLen, 160);
    if ($maxLen < 40) $maxLen = 40;
    if (mb_strlen($text) <= $maxLen) return $text;
    return rtrim(mb_substr($text, 0, $maxLen - 1)) . '…';
}


function enc_type_ru($t) {
    $map = [
        'normal'=>'Нормальный','fighting'=>'Боевой','fly'=>'Летающий','flying'=>'Летающий',
        'poison'=>'Ядовитый','ground'=>'Земляной','rock'=>'Каменный','bug'=>'Насекомое',
        'ghost'=>'Призрачный','steel'=>'Стальной','fire'=>'Огненный','water'=>'Водный',
        'grass'=>'Травяной','electric'=>'Электрический','psychic'=>'Психический','ice'=>'Ледяной',
        'dragon'=>'Драконий','dark'=>'Тёмный','fairy'=>'Волшебный','not'=>'—'
    ];
    $t = strtolower(trim($t));
    return (isset($map[$t]) ? $map[$t] : $t);
}

function enc_type_badge($t) {
    $t0 = strtolower(trim($t));
    if ($t0 === '' || $t0 === 'not') return '';
    return '<span class="type-badge t-' . enc_h($t0) . '">' . enc_h(enc_type_ru($t0)) . '</span>';
}

function enc_move_category_ru($cat) {
    $c = strtolower(trim((string)$cat));
    if ($c === 'physical' || $c === 'phys') return 'Физическая';
    if ($c === 'special' || $c === 'spec') return 'Специальная';
    if ($c === 'status' || $c === 'stat') return 'Статус';
    if ($c === 'specific') return 'Особая';
    return ($c !== '' ? $c : '—');
}

function enc_move_meta_ru($power, $accuracy, $pp) {
    $power = (int)$power; $accuracy = (int)$accuracy; $pp = (int)$pp;
    $parts = [];
    if ($power > 0) $parts[] = 'Сила ' . $power;
    if ($accuracy > 0) $parts[] = 'Точность ' . $accuracy;
    if ($pp > 0) $parts[] = 'PP ' . $pp;
    return ($parts ? implode(' • ', $parts) : '');
}

// --- BBCode (safe subset) ---
function enc_render_bbcode($s) {
    $s = enc_ensure_utf8($s);
    $s = trim($s);
    if ($s === '') return '';

    $s = str_replace('\\n', "\n", $s);

    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = enc_h($s);

    // [b][i][u][s]
    $repl = [
        '~\[b\](.*?)\[/b\]~si' => '<strong>$1</strong>',
        '~\[i\](.*?)\[/i\]~si' => '<em>$1</em>',
        '~\[u\](.*?)\[/u\]~si' => '<span style="text-decoration:underline">$1</span>',
        '~\[s\](.*?)\[/s\]~si' => '<span style="text-decoration:line-through">$1</span>',
        '~\[code\](.*?)\[/code\]~si' => '<pre class="code"><code>$1</code></pre>',
        '~\[quote\](.*?)\[/quote\]~si' => '<blockquote class="quote">$1</blockquote>',
        '~\[quote=(.*?)\](.*?)\[/quote\]~si' => '<blockquote class="quote"><div class="quote-h">$1</div>$2</blockquote>',
    ];
    foreach ($repl as $pat => $rep) {
        $s = preg_replace($pat, $rep, $s);
    }

    // [url] and [url=]
    $s = preg_replace_callback('~\[url\](.*?)\[/url\]~si', function($m) {
        $u = trim(htmlspecialchars_decode($m[1], ENT_QUOTES));
        $u2 = enc_h($u);
        if ($u === '') return '';
        if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
        return '<a class="link" href="' . enc_h($u) . '" target="_blank" rel="noopener noreferrer">' . $u2 . '</a>';
    }, $s);

    $s = preg_replace_callback('~\[url=(.*?)\](.*?)\[/url\]~si', function($m) {
        $u = trim(htmlspecialchars_decode($m[1], ENT_QUOTES));
        $txt = $m[2];
        if ($u === '') return $txt;
        if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
        return '<a class="link" href="' . enc_h($u) . '" target="_blank" rel="noopener noreferrer">' . $txt . '</a>';
    }, $s);

    // [img]
    $s = preg_replace_callback('~\[img\](.*?)\[/img\]~si', function($m) {
        $u = trim(htmlspecialchars_decode($m[1], ENT_QUOTES));
        if ($u === '') return '';
        // allow absolute http(s) or site-absolute
        if (!preg_match('~^(https?://|/)~i', $u)) return '';
        return '<a href="' . enc_h($u) . '" target="_blank" rel="noopener noreferrer"><img class="bbcode-img" src="' . enc_h($u) . '" alt=""></a>';
    }, $s);

    // Auto-link plain URLs (escape ~ delimiter issues: we use # delimiter)
    $s = preg_replace('#(?<!["\'>])(https?://[^\s<]+)#i', '<a class="link" href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $s);

    // Newlines
    $s = nl2br($s);

    // Replace openDex(123) in stored HTML (if any sneaks in)
    $s = preg_replace_callback('~openDex\((\d+)\)~', function($m) {
        $id = (int)$m[1];
        return enc_url('/pokemon.php?id=' . $id);
    }, $s);

    return $s;
}

/**
 * Plain-text renderer for DB fields that may contain HTML (<br>, <span>, <p>) or BBCode.
 * Keeps UI clean and avoids showing raw tags to users.
 */
function enc_mb_strlen_safe($s) {
    if (function_exists('mb_strlen')) return mb_strlen($s, 'UTF-8');
    return strlen($s);
}
function enc_mb_substr_safe($s, $start, $len = null) {
    if (function_exists('mb_substr')) return ($len === null) ? mb_substr($s, $start, null, 'UTF-8') : mb_substr($s, $start, $len, 'UTF-8');
    return ($len === null) ? substr($s, $start) : substr($s, $start, $len);
}

function enc_strip_html_to_text($html) {
    $s = (string)$html;
    if ($s === '') return '';
    // normalize common HTML breaks to newlines
    $s = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $s);
    $s = preg_replace('~</\s*p\s*>~i', "\n\n", $s);
    $s = preg_replace('~</\s*div\s*>~i', "\n", $s);
    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    // collapse whitespace
    $s = preg_replace('~[ \t]+~u', ' ', $s);
    $s = preg_replace('~\n{3,}~u', "\n\n", $s);
    return trim($s);
}

function enc_excerpt($raw, $maxLen = 160) {
    $txt = enc_strip_html_to_text($raw);
    if ($txt === '') return '';
    if (enc_mb_strlen_safe($txt) <= $maxLen) return $txt;
    if ($maxLen < 10) return enc_mb_substr_safe($txt, 0, $maxLen);
    return rtrim(enc_mb_substr_safe($txt, 0, $maxLen - 1)) . "…";
}

function enc_render_richtext($raw) {
    $txt = enc_strip_html_to_text($raw);
    if ($txt === '') return '<div class="enc-muted">Нет описания.</div>';
    return '<div class="enc-prose">' . enc_render_bbcode($txt) . '</div>';
}

function enc_bool_ru($v) {
    $s = strtolower(trim((string)$v));
    if ($s === '') return '—';
    if ($s === '1' || $s === 'yes' || $s === 'true' || $s === 'y') return 'Да';
    if ($s === '0' || $s === 'no' || $s === 'false' || $s === 'n') return 'Нет';
    // sometimes values are already "Да/Нет"
    if ($s === 'да') return 'Да';
    if ($s === 'нет') return 'Нет';
    return (string)$v;
}

function enc_item_type_ru($type) {
    $t = strtolower(trim((string)$type));
    $map = [
        'tm' => 'ТМ',
        'hm' => 'НМ',
        'ball' => 'Покебол',
        'pokeball' => 'Покебол',
        'potion' => 'Лечение',
        'heal' => 'Лечение',
        'berry' => 'Ягода',
        'stone' => 'Камень',
        'evo' => 'Эволюция',
        'evolution' => 'Эволюция',
        'hold' => 'Держимый',
        'held' => 'Держимый',
        'key' => 'Ключевой',
        'misc' => 'Разное',
        'battle' => 'Боевой',
        'quest' => 'Квестовый',
        'food' => 'Еда',
        'material' => 'Материал',
        'medicine' => 'Лечение',
    ];
    if (isset($map[$t])) return $map[$t];
    if ($t === '') return '—';
    $t = str_replace(['_', '-'], ' ', $t);
    $t = trim($t);
    if (function_exists('mb_strtoupper') && function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($t, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($t, 1, null, 'UTF-8');
    }
    return ucfirst($t);
}

function enc_move_target_ru($target) {
    $t = trim((string)$target);
    if ($t === '') return '';
    $tLow = strtolower($t);
    $map = [
        'self' => 'Себя',
        'ally' => 'Союзник',
        'adjacentally' => 'Соседний союзник',
        'adjacentfoe' => 'Соседний враг',
        'adjacentfoesorally' => 'Соседняя цель',
        'alladjacentfoes' => 'Все соседние враги',
        'alladjacent' => 'Все рядом',
        'all' => 'Все',
        'allies' => 'Все союзники',
        'foes' => 'Все враги',
        'foeside' => 'Сторона врагов',
        'allyside' => 'Сторона союзников',
        'randomnormal' => 'Случайная цель',
        'any' => 'Любая цель',
        'normal' => 'Одна цель',
    ];
    if (isset($map[$tLow])) return $map[$tLow];
    // numeric targets are too technical; hide them
    if (preg_match('~^\d+$~', $tLow)) return '';
    return $t;
}

function enc_move_category_badge($cat) {
    $c = strtolower(trim((string)$cat));
    $label = enc_move_category_ru($c);
    $cls = '';
    if ($c === 'physical' || $c === 'phys') $cls = ' enc-badge--physical';
    elseif ($c === 'special' || $c === 'spec') $cls = ' enc-badge--special';
    elseif ($c === 'status') $cls = ' enc-badge--status';
    return '<span class="enc-badge enc-badge--cat' . $cls . '">' . enc_h($label) . '</span>';
}

function enc_move_param_chips($moveRow) {
    $m = (array)$moveRow;
    $chips = [];

    $p = (isset($m['power']) ? (int)$m['power'] : 0);
    $a = (isset($m['accuracy']) ? (int)$m['accuracy'] : 0);
    $pp = (isset($m['pp']) ? (int)$m['pp'] : 0);

    $chips[] = '<span class="enc-chip">Сила <b>' . enc_h($p > 0 ? $p : '—') . '</b></span>';
    $chips[] = '<span class="enc-chip">Точн. <b>' . enc_h($a > 0 ? ($a . '%') : '—') . '</b></span>';
    $chips[] = '<span class="enc-chip">PP <b>' . enc_h($pp > 0 ? $pp : '—') . '</b></span>';

    if (isset($m['priority'])) {
        $pr = (int)$m['priority'];
        if ($pr !== 0) $chips[] = '<span class="enc-chip">Приор. <b>' . enc_h(($pr > 0 ? '+' : '') . $pr) . '</b></span>';
    }
    $t = (isset($m['target']) ? enc_move_target_ru($m['target']) : '');
    if ($t !== '') $chips[] = '<span class="enc-chip enc-chip--muted">Цель: ' . enc_h($t) . '</span>';

    // flags
    $flags = [
        'contact' => ['✋', 'Контакт'],
        'sound'   => ['🔊', 'Звуковая'],
        'punch'   => ['👊', 'Удар'],
        'bite'    => ['🦷', 'Укус'],
        'bullet'  => ['🔫', 'Снаряд'],
        'pulse'   => ['〰', 'Импульс'],
    ];
    foreach ($flags as $k=>$meta) {
        if (!isset($m[$k])) continue;
        $v = (int)$m[$k];
        if ($v === 1) $chips[] = '<span class="enc-chip enc-chip--flag" title="'.enc_h($meta[1]).'">'.$meta[0].'</span>';
    }

    return '<div class="enc-chip-row">' . implode('', $chips) . '</div>';
}


// --- User / Permissions ---
function enc_current_user_id() {
    // Primary session keys used by different builds of the game
    $candidates = array();

    foreach (array('id','user_id','uid','userid','id_user') as $k) {
        if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) $candidates[] = (int)$_SESSION[$k];
    }

    // Session user arrays (if the core stores user data in session)
    foreach (array('user','u','account','player') as $k) {
        if (isset($_SESSION[$k]) && is_array($_SESSION[$k])) {
            $arr = $_SESSION[$k];
            foreach (array('id','user_id','uid') as $kk) {
                if (isset($arr[$kk]) && is_numeric($arr[$kk])) $candidates[] = (int)$arr[$kk];
            }
        }
    }

    // Common globals populated by /inc/conf/global.php (depends on server build)
    foreach (array('user','u','player','USER') as $k) {
        if (isset($GLOBALS[$k]) && is_array($GLOBALS[$k])) {
            $arr = $GLOBALS[$k];
            foreach (array('id','user_id','uid') as $kk) {
                if (isset($arr[$kk]) && is_numeric($arr[$kk])) $candidates[] = (int)$arr[$kk];
            }
        }
    }
    foreach (array('user_id','uid','id_user') as $k) {
        if (isset($GLOBALS[$k]) && is_numeric($GLOBALS[$k])) $candidates[] = (int)$GLOBALS[$k];
    }

    foreach ($candidates as $v) {
        $v = (int)$v;
        if ($v > 0) return $v;
    }
    return 0;
}


function enc_fetch_user(mysqli $mysqli,$userId) {
    $userId = (int)$userId;
    if ($userId <= 0) return null;

    if (!enc_table_exists($mysqli, 'users')) return null;

    $cols = ['id'];
    if (enc_table_has_column($mysqli, 'users', 'login')) $cols[] = 'login';
    if (enc_table_has_column($mysqli, 'users', 'user_group')) $cols[] = 'user_group';
    if (enc_table_has_column($mysqli, 'users', 'rang')) $cols[] = 'rang';

    $sql = "SELECT " . implode(',', $cols) . " FROM users WHERE id=? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = enc_stmt_fetch_assoc($stmt);
    $stmt->close();
    return $row ? $row : null;
}


// --- TM moves (DB: attac_poke_tm + base_items(type='tm')) ---
function enc_tm_move_ids_for_pokemon(mysqli $mysqli, $pokemonId) {
    $pokemonId = (int)$pokemonId;
    if ($pokemonId <= 0) return [];
    if (!enc_table_exists($mysqli, 'attac_poke_tm') || !enc_table_exists($mysqli, 'base_items')) return [];
    if (!enc_table_has_column($mysqli, 'attac_poke_tm', 'tm_id') || !enc_table_has_column($mysqli, 'attac_poke_tm', 'poke_base_id')) return [];
    if (!enc_table_has_column($mysqli, 'base_items', 'type') || !enc_table_has_column($mysqli, 'base_items', 'tm_id') || !enc_table_has_column($mysqli, 'base_items', 'info')) return [];

    $out = [];
    // Map poke -> tm_id list to move ids via base_items.info
    $stmt = $mysqli->prepare("SELECT DISTINCT bi.info AS move_id
                              FROM attac_poke_tm t
                              INNER JOIN base_items bi
                                ON bi.type='tm' AND bi.tm_id=t.tm_id
                              WHERE t.poke_base_id=?");
    if (!$stmt) return [];
    $stmt->bind_param('i', $pokemonId);
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);
    foreach ($rows as $r) {
            $mid = 0;
            $raw = (string)$r['move_id'];
            if (preg_match('~(\d+)~', $raw, $mm)) $mid = (int)$mm[1];
            if ($mid > 0) $out[$mid] = true;
        }
    $stmt->close();
    return array_map('intval', array_keys($out));
}


/**
 * Normalizes build row for mixed schemas:
 * - created_at/updated_at can be DATETIME or INT timestamp (legacy)
 * - created_at_ts/updated_at_ts may exist in newer schema
 */
function enc_build_normalize_row(array $row) {
    $out = $row;

    // Legacy: created_at/updated_at sometimes stored as INT timestamp
    foreach (array('created_at','updated_at') as $k) {
        if (isset($out[$k]) && is_numeric($out[$k]) && (int)$out[$k] > 1000000000) {
            $tsKey = $k . '_ts';
            if (!isset($out[$tsKey]) || (int)$out[$tsKey] <= 0) $out[$tsKey] = (int)$out[$k];
            $out[$k] = date('Y-m-d H:i:s', (int)$out[$tsKey]);
        }
    }

    // From DATETIME -> ts
    if (!isset($out['created_at_ts']) || (int)$out['created_at_ts'] <= 0) {
        $ts = 0;
        if (isset($out['created_at']) && trim((string)$out['created_at']) !== '') {
            $ts = strtotime((string)$out['created_at']);
        }
        $out['created_at_ts'] = $ts ? (int)$ts : 0;
    }
    if (!isset($out['updated_at_ts']) || (int)$out['updated_at_ts'] <= 0) {
        $ts = 0;
        if (isset($out['updated_at']) && trim((string)$out['updated_at']) !== '') {
            $ts = strtotime((string)$out['updated_at']);
        }
        $out['updated_at_ts'] = $ts ? (int)$ts : 0;
    }

    // From ts -> DATETIME string (if needed)
    if ((!isset($out['created_at']) || trim((string)$out['created_at']) === '') && (int)$out['created_at_ts'] > 0) {
        $out['created_at'] = date('Y-m-d H:i:s', (int)$out['created_at_ts']);
    }
    if ((!isset($out['updated_at']) || trim((string)$out['updated_at']) === '') && (int)$out['updated_at_ts'] > 0) {
        $out['updated_at'] = date('Y-m-d H:i:s', (int)$out['updated_at_ts']);
    }

    return $out;
}


function enc_is_admin_user($u) {
    if (!$u) return false;
    return ((int)((isset($u['user_group']) ? $u['user_group'] : 0)) === 1);
}

function enc_require_login($userId) {
    if ($userId > 0) return;
    enc_redirect(enc_url('/login.php?next=' . urlencode((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : enc_url('/')))));
}

function enc_access_split_tokens($s) {
    $s = trim((string)$s);
    if ($s === '') return array();

    $s = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
    $s = str_replace(array(';', '|'), ',', $s);

    $parts = preg_split('~[\s,]+~', $s);
    $out = array();
    if (is_array($parts)) {
        foreach ($parts as $p) {
            $p = trim((string)$p);
            if ($p === '') continue;
            $out[] = $p;
        }
    }
    return $out;
}

function enc_build_access_from_team_json($teamJson) {
    $rules = array('view'=>array(), 'edit'=>array(), 'deny'=>array());
    $team = json_decode((string)$teamJson, true);
    if (!is_array($team)) return $rules;

    if (!isset($team['access'])) return $rules;
    $acc = $team['access'];

    // String shorthand: treated as view-rules
    if (is_string($acc)) {
        $rules['view'] = enc_access_split_tokens($acc);
        return $rules;
    }

    // Object form: {view:"...", edit:"...", deny:"..."} or arrays
    if (is_array($acc)) {
        // list -> view
        $isList = array_keys($acc) === range(0, count($acc) - 1);
        if ($isList) {
            $rules['view'] = enc_access_split_tokens(implode(',', $acc));
            return $rules;
        }

        foreach (array('view','edit','deny') as $k) {
            if (!isset($acc[$k])) continue;
            $v = $acc[$k];
            if (is_string($v)) $rules[$k] = enc_access_split_tokens($v);
            elseif (is_array($v)) $rules[$k] = enc_access_split_tokens(implode(',', $v));
        }
    }

    return $rules;
}

function enc_build_access_from_text_directives($text) {
    $rules = array('view'=>array(), 'edit'=>array(), 'deny'=>array());
    $text = (string)$text;
    if (trim($text) === '') return $rules;

    // [access view="..." edit="..." deny="..."]
    if (preg_match_all('~\\[access\\s+([^\\]]+)\\]~i', $text, $m)) {
        foreach ($m[1] as $attrs) {
            if (preg_match_all('~(view|edit|deny)\\s*=\\s*"([^"]*)"~i', $attrs, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $a) {
                    $k = strtolower($a[1]);
                    $rules[$k] = array_merge($rules[$k], enc_access_split_tokens($a[2]));
                }
            }
        }
    }

    // [view=...], [edit=...], [deny=...]
    foreach (array('view','edit','deny') as $k) {
        if (preg_match_all('~\\['.$k.'\\s*=\\s*([^\\]]+)\\]~i', $text, $m2)) {
            foreach ($m2[1] as $v) $rules[$k] = array_merge($rules[$k], enc_access_split_tokens($v));
        }
    }

    // @view: ... (line directives)
    foreach (array('view','edit','deny') as $k) {
        if (preg_match_all('~^\\s*@'.$k.'\\s*:\\s*(.+)$~im', $text, $m3)) {
            foreach ($m3[1] as $v) $rules[$k] = array_merge($rules[$k], enc_access_split_tokens($v));
        }
    }

    return $rules;
}

function enc_build_parse_access_rules(array $build) {
    $rules = array('view'=>array(), 'edit'=>array(), 'deny'=>array());

    $tj = (string)((isset($build['team_json']) ? $build['team_json'] : ''));
    $r1 = enc_build_access_from_team_json($tj);

    $title = (string)((isset($build['title']) ? $build['title'] : ''));
    $desc  = (string)((isset($build['description']) ? $build['description'] : ''));
    $r2a = enc_build_access_from_text_directives($title);
    $r2b = enc_build_access_from_text_directives($desc);

    foreach (array('view','edit','deny') as $k) {
        $rules[$k] = array_merge($rules[$k], $r1[$k], $r2a[$k], $r2b[$k]);
    }

    // Normalize: trim + lowercase, unique
    foreach (array('view','edit','deny') as $k) {
        $tmp = array();
        foreach ($rules[$k] as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;
            $tmp[] = strtolower($t);
        }
        $rules[$k] = array_values(array_unique($tmp));
    }

    return $rules;
}

function enc_access_token_matches($viewer, array $build, $token) {
    $token = strtolower(trim((string)$token));
    if ($token === '') return false;

    $viewerId = $viewer ? (int)((isset($viewer['id']) ? $viewer['id'] : 0)) : 0;
    $viewerLogin = $viewer ? strtolower((string)((isset($viewer['login']) ? $viewer['login'] : ''))) : '';
    $viewerGroup = $viewer ? (int)((isset($viewer['user_group']) ? $viewer['user_group'] : 0)) : 0;
    $viewerRang = $viewer ? (int)((isset($viewer['rang']) ? $viewer['rang'] : 0)) : 0;

    $authorId = (int)((isset($build['created_by']) ? $build['created_by'] : 0));
    $sharedTo = (int)((isset($build['shared_user_id']) ? $build['shared_user_id'] : 0));

    if ($token === '*' || $token === 'all' || $token === 'any' || $token === 'public') return true;
    if ($token === 'auth' || $token === 'logged' || $token === 'user') return ($viewerId > 0);
    if ($token === 'guest' || $token === 'anon') return ($viewerId <= 0);
    if ($token === 'admin') return ($viewer && enc_is_admin_user($viewer));
    if ($token === 'owner' || $token === 'author') return ($viewerId > 0 && $viewerId === $authorId);
    if ($token === 'shared') return ($viewerId > 0 && $sharedTo > 0 && $viewerId === $sharedTo);

    if (preg_match('~^(?:id:|#)(\\d+)$~', $token, $m)) {
        return $viewerId > 0 && $viewerId === (int)$m[1];
    }

    if (preg_match('~^(?:user:|login:|@)([a-z0-9_\\-\\.]+)$~', $token, $m)) {
        return $viewerId > 0 && $viewerLogin !== '' && $viewerLogin === strtolower($m[1]);
    }

    if (preg_match('~^group:(\\d+)$~', $token, $m)) {
        return $viewerId > 0 && $viewerGroup === (int)$m[1];
    }

    if (preg_match('~^rang\\s*(>=|<=|=|>|<)\\s*(\\d+)$~', $token, $m)) {
        $op = $m[1];
        $n = (int)$m[2];
        if ($op === '>=') return $viewerRang >= $n;
        if ($op === '<=') return $viewerRang <= $n;
        if ($op === '>') return $viewerRang > $n;
        if ($op === '<') return $viewerRang < $n;
        return $viewerRang === $n;
    }

    // regex:/pattern/flags  OR  /pattern/flags
    if (preg_match('~^(?:regex:)?/(.+)/([a-z]*)$~', $token, $m)) {
        $pat = $m[1];
        $flags = $m[2];
        if ($pat === '') return false;
        $re = '~' . str_replace('~', '\\~', $pat) . '~' . $flags;
        if ($viewerLogin === '') return false;
        return @preg_match($re, $viewerLogin) ? true : false;
    }

    // wildcard
    if (strpos($token, '*') !== false) {
        if ($viewerLogin === '') return false;
        $rx = '~^' . str_replace('\\*', '.*', preg_quote($token, '~')) . '$~i';
        return @preg_match($rx, $viewerLogin) ? true : false;
    }

    // plain login match
    if ($token !== '' && $viewerLogin !== '') {
        return $viewerLogin === $token;
    }

    return false;
}

function enc_access_any_match($viewer, array $build, array $tokens) {
    foreach ($tokens as $t) {
        if (enc_access_token_matches($viewer, $build, $t)) return true;
    }
    return false;
}


function enc_build_owner_id(array $build) {
    $authorId = (int)((isset($build['created_by']) ? $build['created_by'] : 0));
    if ($authorId > 0) return $authorId;

    $tj = (string)((isset($build['team_json']) ? $build['team_json'] : ''));
    if ($tj === '') return 0;

    $j = json_decode($tj, true);
    if (!is_array($j)) return 0;

    if (isset($j['meta']) && is_array($j['meta']) && isset($j['meta']['owner_id']) && is_numeric($j['meta']['owner_id'])) {
        $oid = (int)$j['meta']['owner_id'];
        return $oid > 0 ? $oid : 0;
    }
    return 0;
}

function enc_build_normalize_visibility($vis) {
    // Supports enum strings and legacy numeric visibility
    if (is_numeric($vis)) {
        $n = (int)$vis;
        if ($n === 1) return 'public';
        if ($n === 2) return 'shared';
        return 'private';
    }
    $vis = strtolower(trim((string)$vis));
    if ($vis === 'public' || $vis === 'private' || $vis === 'shared') return $vis;
    return 'private';
}

function enc_can_view_build($viewer, array $build) {
    // Hard overrides
    if ($viewer && enc_is_admin_user($viewer)) return true;

    $viewerId = $viewer ? (int)((isset($viewer['id']) ? $viewer['id'] : 0)) : 0;
    $authorId = enc_build_owner_id($build);

    // Owner always can view
    if ($viewerId > 0 && $authorId > 0 && $viewerId === $authorId) return true;

    $rules = enc_build_parse_access_rules($build);

    // Deny rules apply only for non-owner, non-admin
    if (!empty($rules['deny']) && enc_access_any_match($viewer, $build, $rules['deny'])) return false;

    $vis = enc_build_normalize_visibility((isset($build['visibility']) ? $build['visibility'] : 'public'));

    // Public is always viewable (unless denied explicitly above)
    if ($vis === 'public') return true;

    // If explicit view rules exist, require a match (works for both private/shared)
    if (!empty($rules['view'])) {
        return enc_access_any_match($viewer, $build, $rules['view']);
    }

    if (!$viewer) return false;

    if ($vis === 'shared') {
        $to = (int)((isset($build['shared_user_id']) ? $build['shared_user_id'] : 0));
        return $to > 0 && $to === $viewerId;
    }

    // private
    return false;
}


function enc_can_edit_build($viewer, array $build) {
    if (!$viewer) return false;
    if (enc_is_admin_user($viewer)) return true;

    $viewerId = (int)((isset($viewer['id']) ? $viewer['id'] : 0));
    $authorId = enc_build_owner_id($build);
    if ($viewerId > 0 && $authorId > 0 && $viewerId === $authorId) return true;

    $rules = enc_build_parse_access_rules($build);
    if (!empty($rules['deny']) && enc_access_any_match($viewer, $build, $rules['deny'])) return false;

    if (!empty($rules['edit'])) {
        return enc_access_any_match($viewer, $build, $rules['edit']);
    }

    return false;
}


function enc_build_card_html(array $build, array $pokemonNameMap = array()) {
    $build = enc_build_normalize_row($build);

    $id = (int)$build['id'];
    $title = trim((string)(isset($build['title']) ? $build['title'] : ''));
    if ($title === '') $title = 'Сборка ' . $id;

    $login = (string)(isset($build['login']) ? $build['login'] : '');
    $createdBy = (int)(isset($build['created_by']) ? $build['created_by'] : 0);
    $author = ($login !== '' ? $login : ('#' . $createdBy));

    $visibility = (string)(isset($build['visibility']) ? $build['visibility'] : 'private');
    $isRec = (int)(isset($build['is_recommended']) ? $build['is_recommended'] : 0);

    $desc = enc_plaintext_excerpt((string)(isset($build['description']) ? $build['description'] : ''), 140);

    $slots = enc_team_slots_from_json((string)(isset($build['team_json']) ? $build['team_json'] : ''));
    $filled = array();
    foreach ($slots as $s) {
        $pid = (int)$s['pokemon_id'];
        if ($pid > 0) $filled[] = $s;
    }

    $keyNames = array();
    if (count($filled) > 0 && count($filled) <= 2) {
        foreach ($filled as $s) {
            $pid = (int)$s['pokemon_id'];
            if ($pid > 0 && isset($pokemonNameMap[$pid])) $keyNames[] = (string)$pokemonNameMap[$pid];
        }
    }

    $html = '<a class="enc-build-card" href="' . enc_h(enc_url('/build.php?id=' . $id)) . '">';

    $html .= '<div class="enc-build-card__head">';
    $html .= '<div class="enc-build-card__title">' . enc_h($title) . '</div>';
    $html .= '<div class="enc-build-card__badges">';
    $html .= enc_build_visibility_badge($visibility);
    if ($isRec === 1) $html .= '<span class="enc-badge enc-badge--rec">Рекомендуем</span>';
    $html .= '</div>';
    $html .= '</div>';

    $meta = 'Автор: ' . $author;
    $u = enc_human_updated_at((int)(isset($build['updated_at_ts']) ? $build['updated_at_ts'] : 0));
    if ($u !== '') $meta .= ' • обновлено ' . $u;

    $html .= '<div class="enc-muted enc-build-card__meta">' . enc_h($meta) . '</div>';

    if ($desc !== '') {
        $html .= '<div class="enc-build-card__desc" title="' . enc_h($desc) . '">' . enc_h($desc) . '</div>';
    }

    $html .= '<div class="enc-build-card__team" aria-label="Команда">';
    foreach ($slots as $s) {
        $pid = (int)$s['pokemon_id'];
        if ($pid <= 0) continue;
        $form = (string)$s['form'];

        $titleAttr = '';
        if (isset($pokemonNameMap[$pid])) $titleAttr = (string)$pokemonNameMap[$pid];

        $html .= '<span class="enc-build-card__slot" title="' . enc_h($titleAttr) . '">';
        $html .= enc_sprite_img($pid, $form, 'pokedex', 40, 'enc-build-card__sprite');
        $html .= '</span>';
    }
    $html .= '</div>';

    if ($keyNames) {
        $html .= '<div class="enc-help enc-build-card__keys">' . enc_h(implode(' • ', $keyNames)) . '</div>';
    }

    $html .= '</a>';

    return $html;
}

// --- Pagination helper ---
function enc_pagination($page,$per,$total,$baseUrl, array $query = []) {
    $pages = (int)ceil(max(1, $total) / max(1, $per));
    $page = max(1, min($pages, $page));
    if ($pages <= 1) return '';

    $mk = function($p) use ($baseUrl, $query) {
        $q = $query;
        $q['page'] = $p;
        $href = $baseUrl . '?' . http_build_query($q);
        return '<a class="pager-item" href="' . enc_h($href) . '">' . $p . '</a>';
    };

    $items = [];
    $items[] = 1;
    $win = 2;
    for ($p = $page - $win; $p <= $page + $win; $p++) {
        if ($p > 1 && $p < $pages) $items[] = $p;
    }
    if ($pages > 1) $items[] = $pages;

    $items = array_values(array_unique($items));
    sort($items);

    $html = '<div class="pager">';

    // Prev
    if ($page > 1) {
        $q = $query; $q['page'] = $page - 1;
        $html .= '<a class="pager-item" href="' . enc_h($baseUrl . '?' . http_build_query($q)) . '" aria-label="Предыдущая">‹</a>';
    } else {
        $html .= '<span class="pager-item disabled" aria-hidden="true">‹</span>';
    }

    $prev = null;
    foreach ($items as $p) {
        if ($prev !== null && $p > $prev + 1) {
            $html .= '<span class="pager-dots">…</span>';
        }
        if ($p === $page) {
            $html .= '<span class="pager-item active">' . $p . '</span>';
        } else {
            $html .= $mk($p);
        }
        $prev = $p;
    }

    // Next
    if ($page < $pages) {
        $q = $query; $q['page'] = $page + 1;
        $html .= '<a class="pager-item" href="' . enc_h($baseUrl . '?' . http_build_query($q)) . '" aria-label="Следующая">›</a>';
    } else {
        $html .= '<span class="pager-item disabled" aria-hidden="true">›</span>';
    }

    $html .= '</div>';
    return $html;
}


?>

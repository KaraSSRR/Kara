<?php
// encyclopedia/_inc/config.php

if (!defined('ENC_BASE')) {
    define('ENC_BASE', '/encyclopedia');
}

if (!defined('ENC_POKEDEX_PER_PAGE')) {
    define('ENC_POKEDEX_PER_PAGE', 36);
}
if (!defined('ENC_LIST_PER_PAGE')) {
    define('ENC_LIST_PER_PAGE', 50);
}
if (!defined('ENC_BUILDS_PER_PAGE')) {
    define('ENC_BUILDS_PER_PAGE', 20);
}

if (!defined('ENC_TITLE_MAX')) {
    define('ENC_TITLE_MAX', 120);
}
if (!defined('ENC_TEXT_MAX')) {
    define('ENC_TEXT_MAX', 20000);
}
if (!defined('ENC_SHARED_CAN_EDIT')) {
    define('ENC_SHARED_CAN_EDIT', false); // shared-пользователь может только смотреть
}

// Диапазоны поколений (официальные)
if (!defined('ENC_GENERATIONS')) {
    define('ENC_GENERATIONS', json_encode([
        1 => ['from'=>1, 'to'=>151],
        2 => ['from'=>152, 'to'=>251],
        3 => ['from'=>252, 'to'=>386],
        4 => ['from'=>387, 'to'=>493],
        5 => ['from'=>494, 'to'=>649],
        6 => ['from'=>650, 'to'=>721],
        7 => ['from'=>722, 'to'=>809],
        8 => ['from'=>810, 'to'=>905],
        9 => ['from'=>906, 'to'=>1025],
    ]));
}

// Папки со спрайтами
if (!defined('ENC_SPRITE_ANIM_DIR')) {
    define('ENC_SPRITE_ANIM_DIR', '/img/pokemons/animation');
}
if (!defined('ENC_SPRITE_POKEDEX_DIR')) {
    define('ENC_SPRITE_POKEDEX_DIR', '/img/pokemons/pokedex');
}

// Placeholder sprite used when a file is missing.
if (!defined('ENC_SPRITE_PLACEHOLDER')) {
    define('ENC_SPRITE_PLACEHOLDER', rtrim(ENC_BASE, '/') . '/assets/poke_placeholder.svg');
}

?>

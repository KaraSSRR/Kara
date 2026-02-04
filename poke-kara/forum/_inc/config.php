<?php
// forum/_inc/config.php

// Базовый URL форума (папка, в которой расположен модуль)
if (!defined('FORUM_BASE')) {
    define('FORUM_BASE', '/forum');
}

// Пагинация
if (!defined('FORUM_TOPICS_PER_PAGE')) {
    define('FORUM_TOPICS_PER_PAGE', 25);
}
if (!defined('FORUM_POSTS_PER_PAGE')) {
    define('FORUM_POSTS_PER_PAGE', 20);
}

// Лимиты
if (!defined('FORUM_TITLE_MAX')) {
    define('FORUM_TITLE_MAX', 150);
}
if (!defined('FORUM_POST_MAX')) {
    define('FORUM_POST_MAX', 20000);
}
if (!defined('FORUM_SIGNATURE_MAX')) {
    define('FORUM_SIGNATURE_MAX', 600);
}

// Загрузка изображений
if (!defined('FORUM_UPLOAD_MAX_BYTES')) {
    define('FORUM_UPLOAD_MAX_BYTES', 5 * 1024 * 1024); // 5MB
}
if (!defined('FORUM_UPLOAD_DIR')) {
    // Физический путь (папка uploads внутри форума)
    define('FORUM_UPLOAD_DIR', dirname(__DIR__) . '/uploads');
}
if (!defined('FORUM_UPLOAD_URL_PREFIX')) {
    define('FORUM_UPLOAD_URL_PREFIX', FORUM_BASE . '/uploads');
}

// Разрешённые расширения изображений
if (!defined('FORUM_ALLOWED_IMAGE_EXT')) {
    define('FORUM_ALLOWED_IMAGE_EXT', 'jpg,jpeg,png,gif,webp');
}

// Доступные реакции (оставляем набор)
if (!defined('FORUM_REACTIONS')) {
    // Ключ => подпись
    define('FORUM_REACTIONS', json_encode([
        'like'  => '👍',
        'love'  => '❤️',
        'haha'  => '😂',
        'wow'   => '😮',
        'sad'   => '😢',
        'angry' => '😡',
    ], JSON_UNESCAPED_UNICODE));
}

// Безопасность ссылок
if (!defined('FORUM_ALLOW_EXTERNAL_IMAGES')) {
    define('FORUM_ALLOW_EXTERNAL_IMAGES', true);
}
if (!defined('FORUM_ALLOW_EXTERNAL_LINKS')) {
    define('FORUM_ALLOW_EXTERNAL_LINKS', true);
}

// Может ли администрация/модерация отвечать в закрытых темах

// Дефолтные изображения (категории/темы)
if (!defined('FORUM_DEFAULT_CATEGORY_IMAGE')) {
    define('FORUM_DEFAULT_CATEGORY_IMAGE', FORUM_BASE . '/assets/cat_default.svg');
}
if (!defined('FORUM_DEFAULT_TOPIC_IMAGE')) {
    define('FORUM_DEFAULT_TOPIC_IMAGE', FORUM_BASE . '/assets/topic_default.svg');
}


if (!defined('FORUM_STAFF_CAN_REPLY_LOCKED')) {
    define('FORUM_STAFF_CAN_REPLY_LOCKED', true);
}



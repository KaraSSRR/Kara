<?php
// forum/_inc/bootstrap.php
// Подключение движка игры и подготовка окружения форума.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Конфиг форума (constants)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Игра ожидает переменную $patch_project и константу SHOW_FILE до подключения global.php
$patch_project = $_SERVER['DOCUMENT_ROOT'] ?? realpath(dirname(__DIR__, 2));
if (!defined('SHOW_FILE')) {
    define('SHOW_FILE', true);
}

$patch_global = rtrim($patch_project, '/') . '/inc/conf/global.php';
if (!file_exists($patch_global)) {
    http_response_code(500);
    die('Forum error: missing /inc/conf/global.php');
}
require_once $patch_global;

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    http_response_code(500);
    die('Forum error: mysqli is not available');
}

// Кодировка
@$mysqli->set_charset('utf8mb4');
@$mysqli->query("SET NAMES utf8mb4");

// Проверка установки таблиц форума
$forumInstalled = forum_is_installed($mysqli);

// Схема (мягкая совместимость)
if (!defined('FORUM_HAS_CATEGORY_IMAGE')) {
    define('FORUM_HAS_CATEGORY_IMAGE', forum_table_has_column($mysqli, 'forum_categories', 'image'));
}
if (!defined('FORUM_HAS_TOPIC_IMAGE')) {
    define('FORUM_HAS_TOPIC_IMAGE', forum_table_has_column($mysqli, 'forum_topics', 'image'));
}

// Совместимость: определяем колонку с текстом поста (content/text/message)
// Даже если forumInstalled=false (например, апгрейд не докатан), стараемся корректно читать посты.
try {
    $q = $mysqli->query("SHOW TABLES LIKE 'forum_posts'");
    if ($q && $q->num_rows > 0) {
        $cols = forum_detect_post_content_columns($mysqli);
        if (!defined('FORUM_POST_CONTENT_COL')) {
            define('FORUM_POST_CONTENT_COL', (string)($cols['primary'] ?? 'content'));
        }
        if (!empty($cols['alt']) && !defined('FORUM_POST_CONTENT_ALT_COL')) {
            define('FORUM_POST_CONTENT_ALT_COL', (string)$cols['alt']);
        }
    }
} catch (Throwable $e) {
    // Не блокируем загрузку форума, если не удалось определить схему
    if (!defined('FORUM_POST_CONTENT_COL')) define('FORUM_POST_CONTENT_COL', 'content');
}

// Текущий пользователь из сессии игры
$forumUserId = (int)($_SESSION['id'] ?? 0);
$forumUser = null;
$forumProfile = null;
$forumPerms = [
    'can_post_topics' => 0,
    'can_reply'       => 0,
    'can_react'       => 0,
    'can_upload'      => 0,
    'can_moderate'    => 0,
    'can_admin'       => 0,
];
$forumIsBanned = false;

if ($forumUserId > 0) {
    $stmt = $mysqli->prepare('SELECT id, login, user_group, rang, rating, status FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $forumUserId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $forumUser = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();

    if (!$forumUser) {
        unset($_SESSION['id'], $_SESSION['login']);
        $forumUserId = 0;
    } else {
        if (($forumUser['status'] ?? '') === 'ban') {
            $forumIsBanned = true;
        }

        if ($forumInstalled && !$forumIsBanned) {
            // Профиль форума (автоматически для любого игрока)
            $stmt = $mysqli->prepare('INSERT IGNORE INTO forum_users (user_id, signature, created_at, last_seen_at, topics_count, posts_count) VALUES (?,\'\', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0)');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $stmt->close();

            // Права (по умолчанию разрешены базовые действия; модерация/админ — нет)
            $stmt = $mysqli->prepare('INSERT IGNORE INTO forum_permissions (user_id, can_post_topics, can_reply, can_react, can_upload, can_moderate, can_admin, updated_at) VALUES (?,1,1,1,1,0,0,UNIX_TIMESTAMP())');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $stmt->close();

            // Обновляем last_seen
            $stmt = $mysqli->prepare('UPDATE forum_users SET last_seen_at=UNIX_TIMESTAMP() WHERE user_id=?');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $stmt->close();
        }

        if ($forumInstalled) {
            // Профиль
            $stmt = $mysqli->prepare('SELECT user_id, signature, created_at, last_seen_at, topics_count, posts_count FROM forum_users WHERE user_id=? LIMIT 1');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $rs = $stmt->get_result();
            $forumProfile = $rs ? $rs->fetch_assoc() : null;
            $stmt->close();

            // Права
            $stmt = $mysqli->prepare('SELECT can_post_topics, can_reply, can_react, can_upload, can_moderate, can_admin FROM forum_permissions WHERE user_id=? LIMIT 1');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $rs = $stmt->get_result();
            if ($rs && ($row = $rs->fetch_assoc())) {
                foreach ($forumPerms as $k => $_) {
                    $forumPerms[$k] = (int)($row[$k] ?? 0);
                }
            }
            $stmt->close();
        }

        // Админ игры всегда имеет всё
        if ((int)($forumUser['user_group'] ?? 0) === 1) {
            foreach ($forumPerms as $k => $_) $forumPerms[$k] = 1;
        }

        // Бан на форуме = чтение без прав
        if ($forumIsBanned) {
            foreach ($forumPerms as $k => $_) $forumPerms[$k] = 0;
        }
    }
}

// Глобальные флаги
$canPostTopics = ($forumUserId > 0) && !$forumIsBanned && !empty($forumPerms['can_post_topics']);
$canReply      = ($forumUserId > 0) && !$forumIsBanned && !empty($forumPerms['can_reply']);
$canReact      = ($forumUserId > 0) && !$forumIsBanned && !empty($forumPerms['can_react']);
$canUpload     = ($forumUserId > 0) && !$forumIsBanned && !empty($forumPerms['can_upload']);

// CSRF токен будет создан при первом обращении
csrf_token();

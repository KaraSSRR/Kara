<?php
// forum/_inc/functions.php
// PHP 7.x polyfills
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


/**
 * Гарантируем корректный UTF-8 вывод.
 * Если строка не валидна как UTF-8 (часто бывает при старых базах/кодировках),
 * пытаемся конвертировать CP1251 -> UTF-8.
 */
function forum_ensure_utf8(string $s): string {
    if ($s === '') return '';
    // Быстрая проверка валидности UTF-8
    if (@preg_match('//u', $s)) return $s;

    if (function_exists('iconv')) {
        $converted = @iconv('CP1251', 'UTF-8//IGNORE', $s);
        if ($converted !== false && $converted !== '') {
            // Если после конвертации строка стала валидной UTF-8 — используем её
            if (@preg_match('//u', $converted)) return $converted;
            return $converted;
        }
    }
    return $s;
}

function h(?string $s): string {
    $v = forum_ensure_utf8((string)($s ?? ''));
    // ENT_SUBSTITUTE есть в большинстве окружений (>=5.4). Если вдруг нет — не ломаемся.
    $flags = ENT_QUOTES;
    if (defined('ENT_SUBSTITUTE')) $flags |= ENT_SUBSTITUTE;
    return htmlspecialchars($v, $flags, 'UTF-8');
}

function u_strlen(string $s): int {
    if (function_exists('mb_strlen')) return (int)mb_strlen($s, 'UTF-8');
    if ($s === '') return 0;
    return (int)preg_match_all('/./us', $s, $m);
}

function u_substr(string $s, int $start, ?int $len = null): string {
    if (function_exists('mb_substr')) {
        return $len === null ? (string)mb_substr($s, $start, null, 'UTF-8') : (string)mb_substr($s, $start, $len, 'UTF-8');
    }
    if ($s === '') return '';
    preg_match_all('/./us', $s, $m);
    $chars = $m[0] ?? [];
    $slice = $len === null ? array_slice($chars, $start) : array_slice($chars, $start, $len);
    return implode('', $slice);
}

function forum_url(string $path = ''): string {
    $base = defined('FORUM_BASE') ? FORUM_BASE : '/forum';
    if ($path === '' || $path === '/') return rtrim($base, '/') . '/';
    if ($path[0] !== '/') $path = '/' . $path;
    return rtrim($base, '/') . $path;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['forum_csrf'])) {
        $_SESSION['forum_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['forum_csrf'];
}

function csrf_check(): void {
    $token = (string)($_POST['csrf'] ?? '');
    if ($token === '' || empty($_SESSION['forum_csrf']) || !hash_equals((string)$_SESSION['forum_csrf'], $token)) {
        http_response_code(400);
        die('Bad request (CSRF)');
    }
}

function client_ip(): string {
    $candidates = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($candidates as $k) {
        if (!empty($_SERVER[$k])) {
            $v = (string)$_SERVER[$k];
            if ($k === 'HTTP_X_FORWARDED_FOR') {
                $v = trim(explode(',', $v)[0] ?? '');
            }
            return substr($v, 0, 45);
        }
    }
    return '';
}

function paginate(int $page, int $perPage, int $total): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) $page = $pages;
    $offset = ($page - 1) * $perPage;
    return [$page, $perPage, $pages, $offset];
}

function pagination_html(string $baseUrl, int $page, int $pages): string {
    if ($pages <= 1) return '';
    $html = '<nav class="forum-pagination">';

    $mk = function(int $p, string $label, bool $active=false) use ($baseUrl): string {
        $u = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $p;
        $cls = $active ? ' class="active"' : '';
        return '<a'.$cls.' href="'.h($u).'">'.h($label).'</a>';
    };

    $html .= $mk(1, '«', false);

    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);

    if ($start > 2) {
        $html .= '<span class="dots">…</span>';
    }

    for ($p = $start; $p <= $end; $p++) {
        $html .= $mk($p, (string)$p, $p === $page);
    }

    if ($end < $pages - 1) {
        $html .= '<span class="dots">…</span>';
    }

    $html .= $mk($pages, '»', false);
    $html .= '</nav>';
    return $html;
}

function forum_is_logged_in(int $forumUserId): bool {
    return $forumUserId > 0;
}

function forum_require_login(int $forumUserId): void {
    if ($forumUserId <= 0) {
        $next = $_SERVER['REQUEST_URI'] ?? forum_url('/');
        redirect(forum_url('/login.php?next=' . urlencode($next)));
    }
}

function forum_is_admin(?array $forumUser, ?array $forumPerms): bool {
    if ((int)($forumUser['user_group'] ?? 0) === 1) return true;
    return !empty($forumPerms['can_admin']);
}

function forum_is_moderator(?array $forumUser, ?array $forumPerms): bool {
    if ((int)($forumUser['user_group'] ?? 0) === 1) return true;
    return !empty($forumPerms['can_moderate']) || !empty($forumPerms['can_admin']);
}

function forum_clean_text(string $s, int $maxLen): string {
    $s = forum_ensure_utf8((string)$s);
    $s = trim($s);
    $s = preg_replace('/\r\n|\r/u', "\n", $s);
    // Не сжимаем переносы строк в постах, только трим краёв
    if ($maxLen > 0 && u_strlen($s) > $maxLen) {
        $s = u_substr($s, 0, $maxLen);
    }
    return $s;
}

function forum_clean_title(string $s, int $maxLen): string {
    $s = forum_ensure_utf8((string)$s);
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    if ($maxLen > 0 && u_strlen($s) > $maxLen) {
        $s = u_substr($s, 0, $maxLen);
    }
    return $s;
}

function forum_safe_url(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    // Разрешаем относительные ссылки /...
    if (str_starts_with($url, '/')) return $url;

    // Только http/https
    if (!preg_match('~^https?://~i', $url)) return '';

    // Запрещаем javascript:, data:
    if (preg_match('~^(javascript|data):~i', $url)) return '';
    return $url;
}

/**
 * BBCode рендеринг.
 * Важно: сперва escape HTML, затем заменяем BBCode на HTML.
 */
function forum_render_bbcode(string $raw): string {
    $text = (string)$raw;
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $safe = h($text);

    // [code]...[/code] — защищаем от дальнейших замен
    $codeBlocks = [];
    $safe = preg_replace_callback('~\[code\](.*?)\[/code\]~is', function($m) use (&$codeBlocks) {
        $i = count($codeBlocks);
        $codeBlocks[$i] = '<pre class="bbcode-code"><code>' . $m[1] . '</code></pre>';
        return "@@CODE{$i}@@";
    }, $safe);

    // [quote=author|date]...[/quote]
    $safe = preg_replace_callback('~\[quote(?:=([^\]|]+)(?:\|([^\]]+))?)?\](.*?)\[/quote\]~is', function($m) {
        $who = trim((string)($m[1] ?? ''));
        $when = trim((string)($m[2] ?? ''));
        $head = '';
        if ($who !== '' || $when !== '') {
            $head = '<div class="bbcode-quote-head">' . h($who) . ($when !== '' ? ' · ' . h($when) : '') . '</div>';
        }
        return '<blockquote class="bbcode-quote">' . $head . '<div class="bbcode-quote-body">' . ($m[3] ?? '') . '</div></blockquote>';
    }, $safe);

    // Простые теги
    $map = [
        'b' => 'strong',
        'i' => 'em',
        'u' => 'u',
        's' => 's',
    ];
    foreach ($map as $bb => $html) {
        $safe = preg_replace('~\['.$bb.'\](.*?)\[/'.$bb.'\]~is', '<'.$html.'>$1</'.$html.'>', $safe);
    }

    // [url]link[/url]
    $safe = preg_replace_callback('~\[url\](.*?)\[/url\]~is', function($m) {
        $u = forum_safe_url(htmlspecialchars_decode($m[1] ?? '', ENT_QUOTES));
        if ($u === '' || (!FORUM_ALLOW_EXTERNAL_LINKS && !str_starts_with($u, '/'))) {
            return $m[1] ?? '';
        }
        return '<a href="' . h($u) . '" target="_blank" rel="noopener">' . h($u) . '</a>';
    }, $safe);

    // [url=link]text[/url]
    $safe = preg_replace_callback('~\[url=([^\]]+)\](.*?)\[/url\]~is', function($m) {
        $u = forum_safe_url(htmlspecialchars_decode($m[1] ?? '', ENT_QUOTES));
        if ($u === '' || (!FORUM_ALLOW_EXTERNAL_LINKS && !str_starts_with($u, '/'))) {
            return $m[2] ?? '';
        }
        $label = $m[2] ?? $u;
        return '<a href="' . h($u) . '" target="_blank" rel="noopener">' . $label . '</a>';
    }, $safe);

    // [img]url[/img]
    $safe = preg_replace_callback('~\[img\](.*?)\[/img\]~is', function($m) {
        $u = forum_safe_url(htmlspecialchars_decode($m[1] ?? '', ENT_QUOTES));
        if ($u === '' || (!FORUM_ALLOW_EXTERNAL_IMAGES && !str_starts_with($u, '/'))) {
            return '';
        }
        return '<img class="bbcode-img" src="' . h($u) . '" alt="image" loading="lazy" />';
    }, $safe);

    // Автоссылки http(s) вне [url]
$safe = preg_replace('~(?<!["\w])(https?://[\w\-\._\~:/%\?#\[\]@!\$&\(\)\*\+,;=]+)~u', '<a href="$1" target="_blank" rel="noopener">$1</a>', $safe);

    // Ссылки на посты >>#123
    $safe = preg_replace_callback('~&gt;&gt;#(\d+)~u', function($m) {
        $id = (int)$m[1];
        return '<a class="post-ref" href="#p'.$id.'">&gt;&gt;#'.$id.'</a>';
    }, $safe);

    // Переносы строк
    $safe = nl2br($safe);

    // Возвращаем code blocks
    if (!empty($codeBlocks)) {
        $safe = preg_replace_callback('~@@CODE(\d+)@@~', function($m) use ($codeBlocks) {
            $i = (int)$m[1];
            return $codeBlocks[$i] ?? '';
        }, $safe);
    }

    return $safe;
}

function forum_avatar_url(int $userId): string {
    $p = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/img/avatars/mini/' . $userId . '.png';
    if ($p !== '' && file_exists($p)) {
        return '/img/avatars/mini/' . $userId . '.png';
    }
    return '/img/avatars/mini/no-user-img.png';
}


/**
 * Санитайзер для URL изображений (разрешаем относительные пути и http/https).
 */
function forum_sanitize_image_url(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    // Запрещаем javascript: и data:
    $low = strtolower($url);
    if (strpos($low, 'javascript:') === 0 || strpos($low, 'data:') === 0) return '';
    // Разрешаем абсолютные http/https или относительные /...
    if (preg_match('~^https?://~i', $url)) return $url;
    if ($url[0] === '/') return $url;
    return '';
}

function forum_category_image_url(?string $url): string {
    $u = forum_sanitize_image_url((string)$url);
    if ($u !== '') return $u;
    return (defined('FORUM_DEFAULT_CATEGORY_IMAGE') ? FORUM_DEFAULT_CATEGORY_IMAGE : (FORUM_BASE . '/assets/cat_default.svg'));
}

function forum_topic_image_url(?string $url): string {
    $u = forum_sanitize_image_url((string)$url);
    if ($u !== '') return $u;
    return (defined('FORUM_DEFAULT_TOPIC_IMAGE') ? FORUM_DEFAULT_TOPIC_IMAGE : (FORUM_BASE . '/assets/topic_default.svg'));
}

/**
 * Сохранение загруженного изображения на сервер (используется в админке категорий и при создании темы).
 * Возвращает ['ok'=>1,'url'=>...] либо ['ok'=>0,'error'=>...]
 */
function forum_save_uploaded_image(mysqli $mysqli, int $userId, array $file, string $subdir = 'misc'): array {
    if (empty($file) || !isset($file['tmp_name'])) {
        return ['ok'=>0,'error'=>'Файл не выбран'];
    }
    if (!empty($file['error'])) {
        return ['ok'=>0,'error'=>'Ошибка загрузки: ' . (int)$file['error']];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > (int)FORUM_UPLOAD_MAX_BYTES) {
        return ['ok'=>0,'error'=>'Размер файла превышает лимит'];
    }

    $name = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = array_filter(array_map('trim', explode(',', (string)FORUM_ALLOWED_IMAGE_EXT)));
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        return ['ok'=>0,'error'=>'Разрешены только изображения: ' . implode(', ', $allowed)];
    }

    // MIME-check (мягкий)
    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
            $mime = (string)finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
        }
    }
    if ($mime !== '' && !preg_match('~^image/(jpeg|png|gif|webp)~i', $mime)) {
        return ['ok'=>0,'error'=>'Файл не похож на изображение'];
    }

    // getimagesize (жёсткий)
    $dim = @getimagesize($file['tmp_name']);
    if (!$dim) {
        return ['ok'=>0,'error'=>'Невозможно прочитать изображение'];
    }

    $sub = preg_replace('~[^a-z0-9_-]+~i', '', $subdir);
    if ($sub === '') $sub = 'misc';
    $day = date('Ymd');
    $dir = rtrim(FORUM_UPLOAD_DIR, '/') . '/' . $sub . '/' . $day;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $rand = bin2hex(random_bytes(16));
    $filename = $rand . '.' . $ext;
    $path = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return ['ok'=>0,'error'=>'Не удалось сохранить файл'];
    }

    @chmod($path, 0644);

    $url = rtrim(FORUM_UPLOAD_URL_PREFIX, '/') . '/' . $sub . '/' . $day . '/' . $filename;
    $relPath = 'uploads/' . $sub . '/' . $day . '/' . $filename;

    // Логируем
    $stmt = $mysqli->prepare('INSERT INTO forum_uploads (user_id, post_id, topic_id, path, url, mime, size, created_at) VALUES (?, NULL, NULL, ?, ?, ?, ?, UNIX_TIMESTAMP())');
    if ($stmt) {
        $stmt->bind_param('isssi', $userId, $relPath, $url, $mime, $size);
        $stmt->execute();
        $stmt->close();
    }

    return ['ok'=>1,'url'=>$url];
}



function forum_reactions_config(): array {
    $raw = defined('FORUM_REACTIONS') ? FORUM_REACTIONS : '{}';
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

function forum_reactions_html(mysqli $mysqli, int $postId, int $viewerId, bool $canReact): string {
    $cfg = forum_reactions_config();
    if (empty($cfg)) return '';

    // Счётчики по типам реакции
    $stmt = $mysqli->prepare('SELECT reaction, COUNT(*) cnt FROM forum_reactions WHERE post_id=? GROUP BY reaction');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $counts = [];
    while ($r = $rs->fetch_assoc()) {
        $counts[$r['reaction']] = (int)$r['cnt'];
    }
    $stmt->close();

    // Моя реакция (только одна на пост)
    $mineReaction = '';
    if ($viewerId > 0) {
        $stmt = $mysqli->prepare('SELECT reaction FROM forum_reactions WHERE post_id=? AND user_id=? ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('ii', $postId, $viewerId);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($rs && ($r = $rs->fetch_assoc())) {
            $mineReaction = (string)($r['reaction'] ?? '');
        }
        $stmt->close();
    }

    $html = '<div class="forum-reactions" data-post-id="'.$postId.'">';
    foreach ($cfg as $key => $label) {
        $c = $counts[$key] ?? 0;
        $active = ($mineReaction !== '' && $mineReaction === $key);
        $disabled = !$canReact;
        $title = $key; // подсказка (ключ реакции)
        $html .= '<button type="button" class="react-btn'.($active?' active':'').'" data-reaction="'.h($key).'" '.($disabled?'disabled':'').' title="'.h($title).'" aria-label="'.h($title).'">'
              .  '<span class="emo">'.h((string)$label).'</span>'
              .  ($c>0?'<span class="cnt">'.$c.'</span>':'')
              .  '</button>';
    }
    $html .= '</div>';
    return $html;
}

// --- DB helpers / совместимость схем ---

function forum_table_has_column(mysqli $mysqli, string $table, string $column): bool {
    $table = preg_replace('~[^a-zA-Z0-9_]+~', '', $table);
    $column = preg_replace('~[^a-zA-Z0-9_]+~', '', $column);
    if ($table === '' || $column === '') return false;
    $q = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $mysqli->real_escape_string($column) . "'");
    return ($q && $q->num_rows > 0);
}

/**
 * Определяем колонку с текстом поста.
 * Поддержка старых схем, где текст мог храниться в `text`/`message`.
 * Возвращает массив:
 *  - primary: колонка для записи
 *  - alt: альтернативная колонка (если есть)
 */
function forum_detect_post_content_columns(mysqli $mysqli): array {
    $candidates = ['content', 'text', 'message', 'msg'];
    $have = [];
    foreach ($candidates as $c) {
        if (forum_table_has_column($mysqli, 'forum_posts', $c)) {
            $have[] = $c;
        }
    }
    // primary: предпочитаем content
    $primary = in_array('content', $have, true) ? 'content' : ($have[0] ?? 'content');
    $alt = '';
    // alt: если есть text/message и primary=content (или наоборот) — используем как fallback
    foreach (['text','message','msg','content'] as $c) {
        if ($c !== $primary && in_array($c, $have, true)) {
            $alt = $c;
            break;
        }
    }
    return ['primary'=>$primary, 'alt'=>$alt];
}

/**
 * Возвращает список колонок forum_posts, которые похожи на колонку с телом сообщения.
 *
 * В реальных установках встречаются варианты: content/text/message/msg/body/post и т.п.
 * Также иногда сосуществуют две колонки: старая (text) и новая (content).
 *
 * Мы ищем текстовые типы (TEXT/MEDIUMTEXT/LONGTEXT) и дополнительно ранжируем
 * по ожидаемым названиям, чтобы первым шёл наиболее вероятный кандидат.
 */
function forum_post_text_columns(mysqli $mysqli): array {
    static $cached = null;
    if (is_array($cached)) return $cached;

    $cached = [];

    $res = $mysqli->query('SHOW COLUMNS FROM `forum_posts`');
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $field = (string)($r['Field'] ?? '');
            $type = strtolower((string)($r['Type'] ?? ''));
            if ($field === '') continue;
            // Берём только большие текстовые типы, чтобы не цеплять title/ip/и т.п.
            if (strpos($type, 'text') === false) continue;

            // Санитизация имени колонки
            $field = preg_replace('~[^a-zA-Z0-9_]+~', '', $field);
            if ($field === '') continue;
            $cached[] = $field;
        }
    }

    // Если почему-то не нашли по типу — попробуем по известным названиям
    if (empty($cached)) {
        foreach (['content','text','message','msg','body','post','post_text','text_post','message_text'] as $c) {
            if (forum_table_has_column($mysqli, 'forum_posts', $c)) {
                $cached[] = $c;
            }
        }
    }

    // Уникализация
    $cached = array_values(array_unique($cached));

    // Ранжирование: более «правдоподобные» имена — выше
    $priority = [
        'content' => 100,
        'text' => 90,
        'message' => 80,
        'msg' => 70,
        'body' => 60,
        'post' => 50,
        'post_text' => 45,
        'text_post' => 44,
        'message_text' => 43,
    ];

    usort($cached, function($a, $b) use ($priority) {
        $pa = $priority[$a] ?? 0;
        $pb = $priority[$b] ?? 0;
        if ($pa === $pb) return strcmp($a, $b);
        return $pb <=> $pa;
    });

    return $cached;
}

/**
 * Строит SQL-выражение, которое возвращает тело поста независимо от того,
 * в какой колонке оно хранится.
 *
 * Пример: COALESCE(NULLIF(p.`content`,''), NULLIF(p.`text`,''), NULLIF(p.`message`,''))
 */
function forum_post_content_expr(mysqli $mysqli, string $prefix = 'p'): string {
    $cols = forum_post_text_columns($mysqli);
    if (empty($cols)) return "''";

    $parts = [];
    foreach ($cols as $c) {
        $c = preg_replace('~[^a-zA-Z0-9_]+~', '', (string)$c);
        if ($c === '') continue;
        $parts[] = "NULLIF({$prefix}.`{$c}`,'')";
    }
    if (empty($parts)) return "''";
    if (count($parts) === 1) return $parts[0];
    return 'COALESCE(' . implode(',', $parts) . ')';
}

/**
 * Надёжное чтение текста поста по id (используется как fallback, если при выборке
 * через JOIN по каким-то причинам поле content пришло пустым).
 */
function forum_get_post_content(mysqli $mysqli, int $postId): string {
    $expr = forum_post_content_expr($mysqli, 'p');
    $stmt = $mysqli->prepare('SELECT ' . $expr . ' AS content FROM forum_posts p WHERE p.id=? LIMIT 1');
    if (!$stmt) return '';
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $row = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();
    return (string)($row['content'] ?? '');
}


function forum_is_installed(mysqli $mysqli): bool {
    // Полная установка: таблицы + ключевые колонки...
    $needTables = [
        'forum_categories','forum_topics','forum_posts','forum_users',
        'forum_permissions','forum_ranks','forum_reactions','forum_uploads'
    ];
    foreach ($needTables as $t) {
        $q = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($t) . "'");
        if (!$q || $q->num_rows === 0) return false;
    }

    $needColumns = [
        ['forum_users','topics_count'],
        ['forum_users','posts_count'],
        ['forum_posts','reply_to_post_id'],
    ];
    foreach ($needColumns as $pair) {
        [$table,$col] = $pair;
        $qq = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $mysqli->real_escape_string($col) . "'");
        if (!$qq || $qq->num_rows === 0) return false;
    }
    return true;
}

function forum_get_rank_title(mysqli $mysqli, int $postsCount): string {
    $title = '';
    $stmt = $mysqli->prepare('SELECT title FROM forum_ranks WHERE min_posts <= ? ORDER BY min_posts DESC, sort DESC LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $postsCount);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($rs && ($row = $rs->fetch_assoc())) {
            $title = (string)$row['title'];
        }
        $stmt->close();
    }
    return $title !== '' ? $title : 'Новичок';
}

/**
 * Получить права пользователя форума.
 * Если строки нет — возвращаются дефолтные права.
 */
function forum_get_permissions(mysqli $mysqli, int $userId): array {
    $perms = [
        'can_post_topics' => 1,
        'can_reply'       => 1,
        'can_react'       => 1,
        'can_upload'      => 1,
        'can_moderate'    => 0,
        'can_admin'       => 0,
    ];

    if ($userId <= 0) return $perms;

    $stmt = $mysqli->prepare('SELECT can_post_topics, can_reply, can_react, can_upload, can_moderate, can_admin FROM forum_permissions WHERE user_id=? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($rs && ($row = $rs->fetch_assoc())) {
            foreach ($perms as $k => $_) {
                if (array_key_exists($k, $row) && $row[$k] !== null) {
                    $perms[$k] = (int)$row[$k] ? 1 : 0;
                }
            }
        }
        $stmt->close();
    }

    // Админ игры всегда админ форума
    $stmt = $mysqli->prepare('SELECT user_group FROM users WHERE id=? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rs = $stmt->get_result();
        $u = $rs ? $rs->fetch_assoc() : null;
        $stmt->close();
        if ((int)($u['user_group'] ?? 0) === 1) {
            $perms['can_post_topics'] = 1;
            $perms['can_reply']       = 1;
            $perms['can_react']       = 1;
            $perms['can_upload']      = 1;
            $perms['can_moderate']    = 1;
            $perms['can_admin']       = 1;
        }
    }

    return $perms;
}

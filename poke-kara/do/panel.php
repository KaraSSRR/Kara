<?php
// === Подключение и инициализация ===
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
    }
}

session_start();

if (!function_exists('escapeMe')) {
    function escapeMe($s) {
        return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
    }
}

// Получаем пользователя
$user = $mysqli->query('SELECT * FROM users WHERE id = '.intval($_SESSION['id']))->fetch_assoc();

$type = isset($_POST['type']) ? escapeMe($_POST['type']) : '';

// --- Алиасы команд (совместимость со старыми data-action) ---
$type = trim($type);
$typeAliases = [
    'DbLoad'   => 'systemDbLoad',
    'DbSave'   => 'systemDbSave',
    'eventPre' => 'eventPreset',
    'eventMul' => 'eventMultipliers',
    'eventTar' => 'eventTarget',
    'eventSta' => 'eventStatus',
];
if (isset($typeAliases[$type])) { $type = $typeAliases[$type]; }


// === Если просто открыть панель (нет type), возвращаем HTML панели ===

if (!$type) {
    // Дополнительные вкладки (только для администратора)
    $extraNavButtons = '';
    $extraTabHtml = '';
    $eventOptionsHtml = '';

    if ((int)$user['user_group'] === 1) {
        // Реестр ивентов (code -> title) для выпадающего списка
        $mysqli->query("CREATE TABLE IF NOT EXISTS `a_ivent_registry` (
            `code` varchar(32) NOT NULL,
            `title` varchar(200) NOT NULL,
            `created_at` int NOT NULL,
            PRIMARY KEY (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        // Базовый ивент по умолчанию (не перетираем существующий title)
        $mysqli->query("INSERT INTO `a_ivent_registry` (`code`,`title`,`created_at`)
            VALUES ('newyear','Новый год',".time().")
            ON DUPLICATE KEY UPDATE `title`=`title`");

        $evRes = $mysqli->query("SELECT `code`,`title` FROM `a_ivent_registry` ORDER BY `created_at` DESC");
        if ($evRes) {
            while ($ev = $evRes->fetch_assoc()) {
                $c = htmlspecialchars($ev['code'], ENT_QUOTES, 'UTF-8');
                $t = htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8');
                $eventOptionsHtml .= "<option value=\"{$c}\">{$t} ({$c})</option>";
            }
        }
        if (!$eventOptionsHtml) {
            $eventOptionsHtml = '<option value="newyear">Новый год (newyear)</option>';
        }

        $extraNavButtons = <<<HTML
        <button class="admin-panel-tab-btn" data-tab="events">Ивенты</button>
        <button class="admin-panel-tab-btn" data-tab="systemdb">System DB</button>
HTML;

        $extraTabHtml = <<<HTML
        <!-- Вкладка "Ивенты" -->
        <div class="admin-panel-tab events-tab">
            <div class="panel-form-title">Управление ивентами (серверные)</div>

            <form class="admin-action-form" data-action="eventList" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <button type="submit">Обновить список ивентов</button>
            </form>

            <form class="admin-action-form" data-action="eventCreate" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <input type="text" name="event_code" placeholder="code (пример: newyear)" style="max-width:220px;">
                    <input type="text" name="event_title" placeholder="Название (пример: Новый год)" style="min-width:260px;">
                    <button type="submit">Создать / обновить</button>
                </div>
                <div style="margin-top:6px;color:#666;font-size:12px;">
                    code: латиница/цифры/_. Таблицы создаются автоматически при старте/статусе.
                </div>
            </form>

            <form class="admin-action-form" data-action="eventRename" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <input type="text" name="event_title" placeholder="Новое название" style="min-width:260px;">
                    <button type="submit">Переименовать</button>
                </div>
            </form>

            <form class="admin-action-form" data-action="eventStatus" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <button type="submit">Статус / мониторинг</button>
                </div>
            </form>

            <div id="event_status_box" style="margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:8px;background:#fafafa;"></div>

            <form class="admin-action-form" data-action="eventStart" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <input type="number" name="days" value="7" min="1" max="30" title="Длительность (дней)" style="max-width:120px;">
                    <input type="number" name="target" value="0" min="0" title="Цель (0 = авто/без цели)" style="max-width:140px;">
                    <select name="mode" title="Режим/пресет">
                        <option value="standard">standard</option>
                        <option value="easy_start">easy_start</option>
                        <option value="final_night">final_night</option>
                        <option value="antifarm">antifarm</option>
                    </select>
                    <button type="submit">Запустить</button>
                </div>
            </form>

            <form class="admin-action-form" data-action="eventTarget" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <input type="number" name="target" value="0" min="0" title="Новая цель (0 = без цели)" style="max-width:160px;">
                    <button type="submit">Установить цель</button>
                </div>
            </form>

            <form class="admin-action-form" data-action="eventPreset" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <select name="preset" title="Пресет меняет mode и коэффициенты">
                        <option value="standard">standard</option>
                        <option value="easy_start">easy_start (первые сутки легче)</option>
                        <option value="final_night">final_night (финал x2)</option>
                        <option value="antifarm">antifarm (срез фарма)</option>
                    </select>
                    <button type="submit">Применить пресет</button>
                </div>
                <div style="margin-top:6px;color:#666;font-size:12px;">
                    Подсказка: после применения пресета обновите статус — вы увидите текущие множители и mode.
                </div>
            </form>

            <form class="admin-action-form" data-action="eventMultipliers" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <label title="Множитель вклада/снежинок за PVE">PVE x <input type="number" step="0.01" name="mult_pve" value="1.00" style="max-width:90px;"></label>
                    <label title="Множитель вклада/снежинок за PVP">PVP x <input type="number" step="0.01" name="mult_pvp" value="1.00" style="max-width:90px;"></label>
                    <label title="Множитель вклада за подарки">Gift x <input type="number" step="0.01" name="mult_gift" value="1.00" style="max-width:90px;"></label>
                    <label title="Множитель награды/вклада у ёлки">Tree x <input type="number" step="0.01" name="mult_tree" value="1.00" style="max-width:90px;"></label>
                    <button type="submit">Сохранить коэффициенты</button>
                </div>
            </form>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form class="admin-action-form" data-action="eventPause" style="margin-bottom:14px;">
                    <div class="panel-msg"></div>
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <button type="submit">Пауза</button>
                </form>
                <form class="admin-action-form" data-action="eventResume" style="margin-bottom:14px;">
                    <div class="panel-msg"></div>
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <button type="submit">Продолжить</button>
                </form>
                <form class="admin-action-form" data-action="eventStop" style="margin-bottom:14px;">
                    <div class="panel-msg"></div>
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <button type="submit">Stop (force_stop)</button>
                </form>
                <form class="admin-action-form" data-action="eventUnstop" style="margin-bottom:14px;">
                    <div class="panel-msg"></div>
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <button type="submit">Unstop</button>
                </form>
            </div>

            <hr style="margin:16px 0;">

            <form class="admin-action-form" data-action="eventDelete">
                <div class="panel-msg"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <select name="event_code" style="min-width:260px;">
                        {$eventOptionsHtml}
                    </select>
                    <input type="text" name="confirm_code" placeholder="Подтвердите code" style="max-width:220px;">
                    <button type="submit" style="background:#ffecec;">Удалить таблицы ивента</button>
                </div>
                <div style="margin-top:6px;color:#a33;font-size:12px;">
                    Операция удалит таблицы a_ivent_{code}_server/users/log. Данные восстановить можно только из бэкапа.
                </div>
            </form>
        </div>

        <!-- Вкладка "System DB" -->
        <div class="admin-panel-tab systemdb-tab">
            <div class="panel-form-title">System (DB) — таблица system (id=1)</div>

            <form class="admin-action-form" data-action="systemDbLoad" style="margin-bottom:14px;">
                <div class="panel-msg"></div>
                <button type="submit">Загрузить значения</button>
            </form>

            <form class="admin-action-form" data-action="systemDbSave">
                <div class="panel-msg"></div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <label>version <input name="version" placeholder="1.1"></label>
                    <label>online <input type="number" name="online" placeholder="0"></label>
                    <label>time <input type="date" name="time" disabled title="Время (system.time) не редактируется в админке"></label>
                    <label>skoba <input type="number" name="skoba" placeholder="0"></label>
                    <label>shine <input type="number" name="shine" placeholder="2000" title="Шанс шайни (как в system.shine)"></label>

                    <label>closed
                        <select name="closed" title="0 - сервер открыт, 1 - закрыт">
                            <option value="0">0</option>
                            <option value="1">1</option>
                        </select>
                    </label>

                    <label>money <input name="money" placeholder="1" title="Множитель денег (строка)"></label>
                    <label>exp <input name="exp" placeholder="1" title="Множитель опыта (строка)"></label>
                    <label>drop <input name="drop" placeholder="1" title="Множитель дропа (строка)"></label>

                    <label>volera <input type="number" name="volera" placeholder="0"></label>
                    <label>reits <input type="number" name="reits" placeholder="0"></label>
                    <label style="min-width:320px;">reits_text <input name="reits_text" placeholder="" style="min-width:320px;"></label>

                    <label>loto <input type="number" name="loto" placeholder="0"></label>
                    <label>tren <input type="number" name="tren" placeholder="1"></label>
                    <label>calendar <input name="calendar" placeholder="10,31" title="Список через запятую"></label>
                    <label>tree <input type="number" name="tree" placeholder="0"></label>
                    <label>week <input type="number" name="week" placeholder="0"></label>

                    <label>sbeg_enabled
                        <select name="sbeg_enabled">
                            <option value="0">0</option>
                            <option value="1">1</option>
                        </select>
                    </label>
                    <label>sbeg_multiplier <input type="number" step="0.01" name="sbeg_multiplier" placeholder="1.00"></label>
                </div>

                <button type="submit" style="margin-top:10px;">Сохранить</button>
            </form>

            <hr style="margin:16px 0;">

            <div class="panel-form-title" style="margin:0 0 8px;">Быстрые команды (system)</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <form class="admin-action-form" data-action="systemSetClosed" style="margin:0;">
                    <div class="panel-msg"></div>
                    <select name="closed">
                        <option value="0">Открыть</option>
                        <option value="1">Закрыть</option>
                    </select>
                    <button type="submit">Применить</button>
                </form>

                <form class="admin-action-form" data-action="systemSetShine" style="margin:0;">
                    <div class="panel-msg"></div>
                    <input type="number" name="shine" placeholder="2000" style="max-width:120px;">
                    <button type="submit">Shine</button>
                </form>

                <form class="admin-action-form" data-action="systemSetRates" style="margin:0;">
                    <div class="panel-msg"></div>
                    <input name="money" placeholder="money" style="max-width:90px;">
                    <input name="exp" placeholder="exp" style="max-width:90px;">
                    <input name="drop" placeholder="drop" style="max-width:90px;">
                    <button type="submit">Rates</button>
                </form>

                <form class="admin-action-form" data-action="systemSetSbeg" style="margin:0;">
                    <div class="panel-msg"></div>
                    <select name="sbeg_enabled">
                        <option value="0">sbeg OFF</option>
                        <option value="1">sbeg ON</option>
                    </select>
                    <input type="number" step="0.01" name="sbeg_multiplier" placeholder="1.00" style="max-width:110px;">
                    <button type="submit">SBEG</button>
                </form>

                <form class="admin-action-form" data-action="systemCalendarAdd" style="margin:0;">
                    <div class="panel-msg"></div>
                    <input name="token" placeholder="calendar add (пример: 25)" style="max-width:180px;">
                    <button type="submit">+</button>
                </form>

                <form class="admin-action-form" data-action="systemCalendarDel" style="margin:0;">
                    <div class="panel-msg"></div>
                    <input name="token" placeholder="calendar del" style="max-width:140px;">
                    <button type="submit">-</button>
                </form>
            </div>
        </div></div>
HTML;
    }

    $html = <<<HTML
<div class="admin-panel-full">
    <div class="admin-panel-header">
        <div class="admin-panel-title">
            <i class="fas fa-user-cog"></i> Админ-панель управления
        </div>
        <button type="button" class="admin-panel-close" title="Закрыть панель" onclick="closePanelModal(this)">&times;</button>
    </div>
    <div class="admin-panel-nav">
        <button class="admin-panel-tab-btn active" data-tab="users">Пользователи</button>
        <button class="admin-panel-tab-btn" data-tab="moderation">Модерация</button>
        <button class="admin-panel-tab-btn" data-tab="prison">Тюрьма/Арест</button>
        <button class="admin-panel-tab-btn" data-tab="pokemons">Покемоны</button>
        <button class="admin-panel-tab-btn" data-tab="trophies">Трофеи/Предметы</button>
        <button class="admin-panel-tab-btn" data-tab="locations">Локации</button>
        <button class="admin-panel-tab-btn" data-tab="tournaments">Турниры</button>
        <button class="admin-panel-tab-btn" data-tab="system">Система</button>
        {$extraNavButtons}
    </div>
    <div class="admin-panel-content">
        <!-- Вкладка "Пользователи" -->
        <div class="admin-panel-tab users-tab active">
            <form class="admin-action-form" data-action="setGroup">
                <div class="panel-form-title">Изменить группу пользователя</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <select name="group">
                    <option value="1">Администратор</option>
                    <option value="2">Модератор</option>
                    <option value="3">Младший модератор</option>
                    <option value="4">Наставник</option>
                    <option value="5">Гим-лидер</option>
                    <option value="6">Обычный пользователь</option>
                    <option value="7">Бан</option>
                    <option value="8">Тюрьма</option>
                    <option value="10">Секретарь</option>
                </select>
                <button type="submit">Применить</button>
            </form>
            <form class="admin-action-form" data-action="clear_profile">
                <div class="panel-form-title">Очистить профиль</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <button type="submit">Очистить</button>
            </form>
        </div>
        <!-- Вкладка "Модерация" -->
        <div class="admin-panel-tab moderation-tab">
            <form class="admin-action-form" data-action="mute">
                <div class="panel-form-title">Выдать молчание</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <input type="number" name="time" placeholder="Время (мин)" min="1" max="43200">
                <input type="text" name="title" placeholder="Причина">
                <button type="submit">Молчание</button>
            </form>
            <form class="admin-action-form" data-action="unmute">
                <div class="panel-form-title">Снять молчание</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <input type="text" name="title" placeholder="Причина">
                <button type="submit">Снять</button>
            </form>
            <form class="admin-action-form" data-action="warn">
                <div class="panel-form-title">Выдать предупреждение</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <input type="text" name="title" placeholder="Причина">
                <button type="submit">Предупредить</button>
            </form>
            <form class="admin-action-form" data-action="ban">
                <div class="panel-form-title">Забанить</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <input type="text" name="title" placeholder="Причина">
                <button type="submit">Забанить</button>
            </form>
            <form class="admin-action-form" data-action="unban">
                <div class="panel-form-title">Разбанить</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <button type="submit">Разбанить</button>
            </form>
        </div>
        <!-- Вкладка "Тюрьма/Арест" -->
        <div class="admin-panel-tab prison-tab">
            <form class="admin-action-form" data-action="prison">
                <div class="panel-form-title">Арестовать</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <input type="number" name="time" placeholder="Срок (дней)" min="1" max="365">
                <input type="text" name="title" placeholder="Причина">
                <button type="submit">Арестовать</button>
            </form>
            <form class="admin-action-form" data-action="free">
                <div class="panel-form-title">Освободить из тюрьмы</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <button type="submit">Освободить</button>
            </form>
        </div>
        <!-- Вкладка "Покемоны" -->
        <div class="admin-panel-tab pokemons-tab">
            <form class="admin-action-form" data-action="transf">
                <div class="panel-form-title">Изъять покемона</div>
                <input type="text" name="user" placeholder="ID пользователя" required>
                <input type="text" name="pokeID" placeholder="ID покемона" required>
                <button type="submit">Изъять</button>
            </form>
            <form class="admin-action-form" data-action="give_pokemon">
                <div class="panel-form-title">Выдать покемона</div>
                <input type="text" name="login" placeholder="Логин пользователя" required>
                <input type="text" name="pokemon_data" placeholder="Данные покемона">
                <button type="submit">Выдать</button>
            </form>
        </div>
        <!-- Вкладка "Трофеи/Предметы" -->
        <div class="admin-panel-tab trophies-tab">
            <form class="admin-action-form" data-action="give_trophy">
                <div class="panel-form-title">Выдать трофей</div>
                <input type="text" name="login" placeholder="Логин пользователя" required>
                <input type="text" name="item_id" placeholder="ID предмета/трофея" required>
                <input type="text" name="about" placeholder="Комментарий (необязательно)">
                <button type="submit">Выдать</button>
            </form>
            <form class="admin-action-form" data-action="give_item">
                <div class="panel-form-title">Выдать предмет</div>
                <input type="text" name="login" placeholder="Логин пользователя" required>
                <input type="text" name="item_id" placeholder="ID предмета" required>
                <input type="number" name="count" placeholder="Кол-во" min="1" max="99999999" required>
                <button type="submit">Выдать</button>
            </form>
        </div>
        <!-- Вкладка "Локации" -->
        <div class="admin-panel-tab locations-tab">
            <form class="admin-action-form" data-action="tp">
                <div class="panel-form-title">Телепортировать себя</div>
                <input type="text" name="loc_name" placeholder="Название локации" required>
                <button type="submit">Телепортироваться</button>
            </form>
            <form class="admin-action-form" data-action="edit_location">
                <div class="panel-form-title">Изменить описание локации</div>
                <input type="text" name="loc_id" placeholder="ID локации" required>
                <input type="text" name="description" placeholder="Новое описание" required>
                <button type="submit">Изменить</button>
            </form>
<form id="upload-location-form" action="/do/panel.php" method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
    <div class="panel-form-title">Изменить картинку локации</div>
    <input type="hidden" name="type" value="upload_location_image">
    <input type="text" name="loc_id" placeholder="ID локации (например: 23)" required>
    <input type="file" name="location_img" accept="image/png" required>
    <button type="submit">Загрузить PNG</button>
    <div id="location-upload-msg" style="margin-top:8px;"></div>
</form>
        </div>
        <!-- Вкладка "Турниры" -->
        <div class="admin-panel-tab tournaments-tab">
            <form class="admin-action-form" data-action="add_tournament">
                <div class="panel-form-title">Добавить турнир</div>
                <input type="text" name="name" placeholder="Название турнира" required>
                <input type="text" name="lvl" placeholder="Уровень" required>
                <input type="text" name="count" placeholder="Кол-во участников" required>
                <button type="submit">Добавить</button>
            </form>
        </div>
        <!-- Вкладка "Система" -->
        <div class="admin-panel-tab system-tab">
            <form class="admin-action-form" data-action="announcement" style="margin-top:24px;">
    <div class="panel-form-title">Объявление для всех игроков</div>
    <input type="text" name="text" placeholder="Текст объявления" required>
    <button type="submit">Отправить объявление</button>
</form>
        </div>
    {$extraTabHtml}
    </div>
    <div class="admin-panel-msg"></div>
</div>
<script src="/js/admin-panel.js"></script>
HTML;
    header('Content-Type: application/json');
    echo json_encode(['html'=>$html]);
    exit;
}


// === Если type есть — исполняем действия через AdminPanelFull ===

class AdminPanelFull {
    private $mysqli;
    private $selfUser; // текущий пользователь (кто вызывает панель)
    private $response = ['error'=>false, 'msg'=>'', 'html'=>'', 'data'=>[]];

    public function __construct($mysqli, $selfUser) {
        $this->mysqli = $mysqli;
        $this->selfUser = $selfUser;
    }

    public function getAvailableActions() {
        $group = (int)$this->selfUser['user_group'];
        $actions = [];
        if ($group === 1) { // Администратор — все права
            $actions = [
                'mute', 'unmute', 'warn', 'unwarn', 'ban', 'unban', 'tp', 'set_group', 'give_trophy', 'system_msg', 'comment',
                'prison', 'free', 'fine', 'transf', 'clear_profile', 'moder', 'moderDel', 'nast', 'nastDel', 'upGroup',
                'edit_gym', 'add_tournament', 'edit_location', 'edit_pokemon', 'edit_attack', 'edit_ability',
                'give_egg', 'give_pokemon', 'give_item', 'give_aquamarine', 'mdel', 'mreport', 'aboutLocation',
                'namePokemon', 'nameAttack', 'nameAbility', 'aboutAttack', 'goHelper', 'checkLoto', 'giveTrophy',
                'sekretar', 'tpGym', 'textGym', 'turScore', 'addTur', 'pass', 'upload_location_image', 'announcement', 'eventList','eventCreate','eventRename','eventStatus','eventStart','eventStop','eventUnstop','eventPause','eventResume','eventTarget','eventPreset','eventMultipliers','eventDelete','systemDbLoad','systemDbSave','systemSetClosed','systemSetRates','systemSetShine','systemSetSbeg','systemCalendarAdd','systemCalendarDel'
            ];
        } elseif ($group === 2) { // Модератор
            $actions = [
                'mute', 'unmute', 'warn', 'ban', 'tp', 'prison', 'free', 'fine', 'transf', 'comment', 'clear_profile', 'mreport', 'mdel'
            ];
        } elseif ($group === 3) { // Младший модератор
            $actions = [
                'mute', 'unmute', 'warn', 'comment', 'clear_profile', 'mreport'
            ];
        } elseif ($group === 4) { // Наставник
            $actions = [
                'comment', 'warn'
            ];
        } elseif ($group === 5) { // Гим-лидер (пример)
            $actions = [
                'tpGym', 'textGym'
            ];
        } elseif ($group === 9) {
            $actions = [
                'edit_location', 'upload_location_image', 'tp'
            ];
        }
        // Далее по необходимости для других групп...
        return $actions;
    }

    public function handle($type, $data) {
        $actions = $this->getAvailableActions();
        if (!in_array($type, $actions) && $this->selfUser['user_group'] != 1) {
            $this->response['error'] = true;
            $this->response['msg'] = 'Нет прав!';
            return $this->response;
        }
        switch ($type) {
            case 'mute': return $this->muteUser($data);
            case 'unmute': return $this->unmuteUser($data);
            case 'warn': return $this->warnUser($data);
            case 'unwarn': return $this->unwarnUser($data);
            case 'ban': return $this->banUser($data);
            case 'unban': return $this->unbanUser($data);
            case 'tp': return $this->tpUser($data);
            case 'setGroup':
            case 'set_group':
                return $this->setGroup($data);
            case 'give_trophy': return $this->giveTrophy($data);
            case 'system_msg': return $this->systemMsg($data);
            case 'comment': return $this->addComment($data);
            case 'prison': return $this->prisonUser($data);
            case 'free': return $this->freeUser($data);
            case 'fine': return $this->fineUser($data);
            case 'transf': return $this->transfPokemon($data);
            case 'clear_profile': return $this->clearProfile($data);
            case 'moder': return $this->giveModer($data);
            case 'moderDel': return $this->removeModer($data);
            case 'nast': return $this->giveNast($data);
            case 'nastDel': return $this->removeNast($data);
            case 'upGroup': return $this->upGroup($data);
            case 'edit_gym': return $this->editGym($data);
             case 'announcement': 
                return $this->sendAnnouncement($data);
            case 'add_tournament': return $this->addTournament($data);
            case 'edit_location': return $this->editLocation($data);
            case 'edit_pokemon': return $this->editPokemon($data);
            case 'edit_attack': return $this->editAttack($data);
            case 'edit_ability': return $this->editAbility($data);
            case 'give_egg': return $this->giveEgg($data);
            case 'give_pokemon': return $this->givePokemon($data);
            case 'give_item': return $this->giveItem($data);
            case 'give_aquamarine': return $this->giveAquamarine($data);
            case 'mdel': return $this->deleteMsg($data);
            case 'mreport': return $this->reportMsg($data);
            case 'aboutLocation': return $this->aboutLocation($data);
            case 'namePokemon': return $this->namePokemon($data);
            case 'nameAttack': return $this->nameAttack($data);
            case 'nameAbility': return $this->nameAbility($data);
            case 'aboutAttack': return $this->aboutAttack($data);
            case 'goHelper': return $this->goHelper($data);
            case 'checkLoto': return $this->checkLoto($data);
            case 'giveTrophy': return $this->giveTrophy($data);
            case 'sekretar': return $this->sekretarPanel($data);
            case 'tpGym': return $this->tpGym($data);
            case 'textGym': return $this->textGym($data);
            case 'turScore': return $this->turScore($data);
            case 'addTur': return $this->addTur($data);
            case 'pass': return $this->passChange($data);
            case 'upload_location_image': return $this->uploadLocationImage($data);

            // --- Ивенты (только админ) ---
            case 'eventList': return $this->eventList($data);
            case 'eventCreate': return $this->eventCreate($data);
            case 'eventRename': return $this->eventRename($data);
            case 'eventSta':
            case 'eventStatus': return $this->eventStatus($data);
            case 'eventStart': return $this->eventStart($data);
            case 'eventStop': return $this->eventStop($data);
            case 'eventUnstop': return $this->eventUnstop($data);
            case 'eventPause': return $this->eventPause($data);
            case 'eventResume': return $this->eventResume($data);
            case 'eventTar':
            case 'eventTarget': return $this->eventTarget($data);
            case 'eventPre':
            case 'eventPreset': return $this->eventPreset($data);
            case 'eventMul':
            case 'eventMultipliers': return $this->eventMultipliers($data);
            case 'eventDelete': return $this->eventDelete($data);

            // --- System(DB) (только админ) ---
            case 'DbLoad':
            case 'systemDbLoad': return $this->systemDbLoad($data);
            case 'DbSave':
            case 'systemDbSave': return $this->systemDbSave($data);
            case 'systemSetClosed': return $this->systemSetClosed($data);
            case 'systemSetShine': return $this->systemSetShine($data);
            case 'systemSetRates': return $this->systemSetRates($data);
            case 'systemSetSbeg': return $this->systemSetSbeg($data);
            case 'systemCalendarAdd': return $this->systemCalendarAdd($data);
            case 'systemCalendarDel': return $this->systemCalendarDel($data);

            default:
                $this->response['error'] = true;
                $this->response['msg'] = 'Неизвестная команда: '.$type;
        }
        return $this->response;
    }
      private function uploadLocationImage($data) {
    // Проверка прав
    if ((int)$this->selfUser['user_group'] !== 9 && (int)$this->selfUser['user_group'] !== 1) {
        return $this->err('Нет прав на загрузку изображения!');
    }

    // Проверка ID локации
    $loc_id = isset($data['loc_id']) ? intval($data['loc_id']) : 0;
    if ($loc_id <= 0) return $this->err('Некорректный ID локации.');

    // Проверка файла
    if (!isset($_FILES['location_img']) || $_FILES['location_img']['error'] !== UPLOAD_ERR_OK) {
        return $this->err('Файл не загружен или произошла ошибка.');
    }

    // Проверка расширения и типа
    $fileType = mime_content_type($_FILES['location_img']['tmp_name']);
    if (strpos($fileType, 'png') === false) {
        return $this->err('Можно загружать только PNG-файлы!');
    }

    // Путь для сохранения
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/world/location/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = $loc_id . '.png';
    $destination = $uploadDir . $fileName;

    // Перемещаем файл
    if (move_uploaded_file($_FILES['location_img']['tmp_name'], $destination)) {
        $src = '/img/world/location/' . $fileName;
        // Готовим красивый html для вставки
        $html = '<div class="location-upload-result">'
              . '<div class="success-msg">Изображение успешно загружено!</div>'
              . '<img src="'.htmlspecialchars($src).'?t='.time().'" style="max-width:220px;max-height:220px;border:1px solid #aaa;border-radius:5px;margin:10px 0;">'
              . '</div>';
        return [
            'error' => false,
            'msg'   => 'Изображение успешно загружено!',
            'src'   => $src,
            'html'  => $html,
            'data'  => ['src' => $src]
        ];
    } else {
        return $this->err('Ошибка при сохранении файла.');
    }
}

    // =========================================================
    //                 ИВЕНТЫ + SYSTEM(DB)
    // =========================================================

    private function isAdmin() {
        return ((int)$this->selfUser['user_group'] === 1);
    }

    private function escSql($s) {
        return $this->mysqli->real_escape_string((string)$s);
    }

    // ---------- SYSTEM(DB) ----------
    private function systemEnsureRow() {
        $q = $this->mysqli->query("SELECT `id` FROM `system` WHERE `id`=1 LIMIT 1");
        if ($q && $q->num_rows > 0) return true;

        // Создаём строку id=1 по схеме из дампа (ничего не удаляем)
        $sql = "INSERT INTO `system`
            (`id`,`version`,`online`,`time`,`skoba`,`shine`,`closed`,`money`,`exp`,`drop`,`volera`,`reits`,`reits_text`,`loto`,`tren`,`calendar`,`tree`,`week`,`sbeg_enabled`,`sbeg_multiplier`)
            VALUES
            (1,'1.1',0,CURDATE(),0,2000,'0','1','1','1',0,0,'',0,1,'',0,0,1,1.00)";
        return (bool)$this->mysqli->query($sql);
    }

    private function systemDbLoad($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();

        $q = $this->mysqli->query("SELECT * FROM `system` WHERE `id`=1 LIMIT 1");
        if (!$q) return $this->err('Ошибка чтения system: '.$this->mysqli->error);
        $row = $q->fetch_assoc();
        if (!$row) return $this->err('system.id=1 не найден');

        return $this->ok('System загружен', ['fill' => $row]);
    }

    private function systemDbSave($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();

        // Берём текущую строку, чтобы:
        // 1) не сбрасывать значения при отсутствии полей в POST (например из-за HTML-особенностей)
        // 2) корректно обрабатывать частичные обновления
        $q0 = $this->mysqli->query("SELECT * FROM `system` WHERE `id`=1 LIMIT 1");
        if (!$q0) return $this->err('Ошибка чтения system перед сохранением: '.$this->mysqli->error);
        $cur = $q0->fetch_assoc();
        if (!$cur) return $this->err('system.id=1 не найден');

        // helper: взять значение из POST, если поле реально присутствует, иначе оставить текущее
        $take = function($key) use ($data, $cur) {
            return array_key_exists($key, $data) ? $data[$key] : ($cur[$key] ?? null);
        };

        // Приведение типов согласно дампу. system.time НЕ трогаем (и поле в форме disabled).
        $version = $this->escSql((string)$take('version'));
        $online  = (int)$take('online');

        $skoba  = (int)$take('skoba');
        $shine  = (int)$take('shine');
        $closed = ((string)$take('closed') === '1') ? '1' : '0';

        $money = $this->escSql((string)$take('money'));
        $exp   = $this->escSql((string)$take('exp'));
        $drop  = $this->escSql((string)$take('drop'));

        $volera = (int)$take('volera');
        $reits  = (int)$take('reits');
        $reits_text = $this->escSql((string)$take('reits_text'));

        $loto = (int)$take('loto');
        $tren = (int)$take('tren');

        $calendar = $this->escSql((string)$take('calendar'));
        $tree = (int)$take('tree');
        $week = (int)$take('week');

        $sbeg_enabled = (int)$take('sbeg_enabled');

        // multiplier может приходить как "1,25"
        $sbeg_multiplier_raw = (string)$take('sbeg_multiplier');
        $sbeg_multiplier_raw = str_replace(',', '.', $sbeg_multiplier_raw);
        $sbeg_multiplier = (float)$sbeg_multiplier_raw;

        $sql = "UPDATE `system` SET
            `version`='{$version}',
            `online`={$online},
            `skoba`={$skoba},
            `shine`={$shine},
            `closed`='{$closed}',
            `money`='{$money}',
            `exp`='{$exp}',
            `drop`='{$drop}',
            `volera`={$volera},
            `reits`={$reits},
            `reits_text`='{$reits_text}',
            `loto`={$loto},
            `tren`={$tren},
            `calendar`='{$calendar}',
            `tree`={$tree},
            `week`={$week},
            `sbeg_enabled`={$sbeg_enabled},
            `sbeg_multiplier`={$sbeg_multiplier}
        WHERE `id`=1 LIMIT 1";

        $ok = $this->mysqli->query($sql);
        if (!$ok) return $this->err('Ошибка сохранения system: '.$this->mysqli->error);

        $affected = (int)$this->mysqli->affected_rows;

        // перечитываем, чтобы показать фактическое состояние (и увидеть, если что-то перезаписывается внешним кодом)
        $q = $this->mysqli->query("SELECT * FROM `system` WHERE `id`=1 LIMIT 1");
        $row = $q ? $q->fetch_assoc() : null;

        $msg = ($affected > 0) ? 'System сохранён (id=1)' : 'System сохранён (без изменений)';
        return $this->ok($msg, ['affected_rows'=>$affected, 'fill'=> ($row ?: [])]);
    }

    private function systemSetClosed($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();
        $closed = ((string)($data['closed'] ?? '0') === '1') ? '1' : '0';
        $ok = $this->mysqli->query("UPDATE `system` SET `closed`='{$closed}' WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка system.closed: '.$this->mysqli->error);
        return $this->ok('closed='.$closed);
    }

    private function systemSetShine($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();
        $shine = (int)($data['shine'] ?? 2000);
        $ok = $this->mysqli->query("UPDATE `system` SET `shine`={$shine} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка system.shine: '.$this->mysqli->error);
        return $this->ok('shine='.$shine);
    }

    private function systemSetRates($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();
        $money = $this->escSql($data['money'] ?? '1');
        $exp   = $this->escSql($data['exp'] ?? '1');
        $drop  = $this->escSql($data['drop'] ?? '1');
        $ok = $this->mysqli->query("UPDATE `system` SET `money`='{$money}', `exp`='{$exp}', `drop`='{$drop}' WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка rates: '.$this->mysqli->error);
        return $this->ok('Rates обновлены: money='.$money.', exp='.$exp.', drop='.$drop);
    }

    private function systemSetSbeg($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();
        $sbeg_enabled = (int)($data['sbeg_enabled'] ?? 1);
        $sbeg_multiplier = (float)($data['sbeg_multiplier'] ?? 1.00);
        $ok = $this->mysqli->query("UPDATE `system` SET `sbeg_enabled`={$sbeg_enabled}, `sbeg_multiplier`={$sbeg_multiplier} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка sbeg: '.$this->mysqli->error);
        return $this->ok('SBEG: enabled='.$sbeg_enabled.', x'.$sbeg_multiplier);
    }

    private function systemCalendarTokens($calendar) {
        $calendar = trim((string)$calendar);
        if ($calendar === '') return [];
        $arr = array_filter(array_map('trim', explode(',', $calendar)), function($v){ return $v !== ''; });
        // уникализация, сохранение порядка
        $seen = [];
        $out = [];
        foreach ($arr as $v) {
            if (!isset($seen[$v])) { $seen[$v]=1; $out[]=$v; }
        }
        return $out;
    }

    private function systemCalendarAdd($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();
        $token = trim((string)($data['token'] ?? ''));
        if ($token === '') return $this->err('Не указан token');
        $q = $this->mysqli->query("SELECT `calendar` FROM `system` WHERE `id`=1 LIMIT 1");
        if (!$q) return $this->err('Ошибка чтения calendar: '.$this->mysqli->error);
        $row = $q->fetch_assoc();
        $list = $this->systemCalendarTokens($row['calendar'] ?? '');
        $list[] = $token;
        $list = $this->systemCalendarTokens(implode(',', $list));
        $val = $this->escSql(implode(',', $list));
        $ok = $this->mysqli->query("UPDATE `system` SET `calendar`='{$val}' WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка calendar add: '.$this->mysqli->error);
        return $this->ok('calendar: '.$val, ['fill'=>['calendar'=>$val]]);
    }

    private function systemCalendarDel($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->systemEnsureRow();
        $token = trim((string)($data['token'] ?? ''));
        if ($token === '') return $this->err('Не указан token');
        $q = $this->mysqli->query("SELECT `calendar` FROM `system` WHERE `id`=1 LIMIT 1");
        if (!$q) return $this->err('Ошибка чтения calendar: '.$this->mysqli->error);
        $row = $q->fetch_assoc();
        $list = $this->systemCalendarTokens($row['calendar'] ?? '');
        $list = array_values(array_filter($list, function($v) use ($token){ return $v !== $token; }));
        $val = $this->escSql(implode(',', $list));
        $ok = $this->mysqli->query("UPDATE `system` SET `calendar`='{$val}' WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка calendar del: '.$this->mysqli->error);
        return $this->ok('calendar: '.$val, ['fill'=>['calendar'=>$val]]);
    }

    // ---------- EVENTS ----------
    private function eventRegistryEnsure() {
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS `a_ivent_registry` (
            `code` varchar(32) NOT NULL,
            `title` varchar(200) NOT NULL,
            `created_at` int NOT NULL,
            PRIMARY KEY (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");
    }

    private function eventCode($code) {
        $code = preg_replace('/[^a-z0-9_]+/i', '', (string)$code);
        if ($code === '') $code = 'newyear';
        return strtolower($code);
    }

    private function eventTable($code, $suffix) {
        $code = $this->eventCode($code);
        $suffix = preg_replace('/[^a-z0-9_]+/i', '', (string)$suffix);
        return "a_ivent_{$code}_{$suffix}";
    }

    private function eventEnsureTables($code) {
        $code = $this->eventCode($code);

        $server = $this->eventTable($code, 'server');
        $users  = $this->eventTable($code, 'users');
        $log    = $this->eventTable($code, 'log');

        // server
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS `{$server}` (
            `id` int NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT 0,
            `active_override` tinyint(1) NOT NULL DEFAULT 0,
            `force_stop` tinyint(1) NOT NULL DEFAULT 0,
            `pause` tinyint(1) NOT NULL DEFAULT 0,
            `mode` varchar(32) NOT NULL DEFAULT 'standard',
            `start_ts` int NOT NULL DEFAULT 0,
            `end_ts` int NOT NULL DEFAULT 0,
            `progress` int NOT NULL DEFAULT 0,
            `target` int NOT NULL DEFAULT 0,
            `tier` int NOT NULL DEFAULT 0,
            `mult_pve` decimal(6,2) NOT NULL DEFAULT 1.00,
            `mult_pvp` decimal(6,2) NOT NULL DEFAULT 1.00,
            `mult_gift` decimal(6,2) NOT NULL DEFAULT 1.00,
            `mult_tree` decimal(6,2) NOT NULL DEFAULT 1.00,
            `updated_at` int NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `updated_at` (`updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        // users
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS `{$users}` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_id` int NOT NULL,
            `snowflakes` int NOT NULL DEFAULT 0,
            `contribute` int NOT NULL DEFAULT 0,
            `tree_free_day` int NOT NULL DEFAULT 0,
            `daily_json` varchar(2000) NOT NULL DEFAULT '',
            `tier_json` varchar(2000) NOT NULL DEFAULT '',
            `updated_at` int NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `updated_at` (`updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        // log
        $this->mysqli->query("CREATE TABLE IF NOT EXISTS `{$log}` (
            `id` int NOT NULL AUTO_INCREMENT,
            `ts` int NOT NULL,
            `user_id` int NOT NULL DEFAULT 0,
            `action` varchar(64) NOT NULL DEFAULT '',
            `delta_snow` int NOT NULL DEFAULT 0,
            `delta_progress` int NOT NULL DEFAULT 0,
            `loc_id` int NOT NULL DEFAULT 0,
            `meta` varchar(2000) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            KEY `ts` (`ts`),
            KEY `action` (`action`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");


        // --- миграция схемы (если таблицы уже существовали старой версии) ---
        // server columns
        $this->ensureTableColumn($server, 'active', "`active` tinyint(1) NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'active_override', "`active_override` tinyint(1) NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'force_stop', "`force_stop` tinyint(1) NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'pause', "`pause` tinyint(1) NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'mode', "`mode` varchar(32) NOT NULL DEFAULT 'standard'");
        $this->ensureTableColumn($server, 'start_ts', "`start_ts` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'end_ts', "`end_ts` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'progress', "`progress` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'target', "`target` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'tier', "`tier` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($server, 'mult_pve', "`mult_pve` decimal(6,2) NOT NULL DEFAULT 1.00");
        $this->ensureTableColumn($server, 'mult_pvp', "`mult_pvp` decimal(6,2) NOT NULL DEFAULT 1.00");
        $this->ensureTableColumn($server, 'mult_gift', "`mult_gift` decimal(6,2) NOT NULL DEFAULT 1.00");
        $this->ensureTableColumn($server, 'mult_tree', "`mult_tree` decimal(6,2) NOT NULL DEFAULT 1.00");
        $this->ensureTableColumn($server, 'updated_at', "`updated_at` int NOT NULL DEFAULT 0");
        $this->ensureTableIndex($server, 'updated_at', "KEY `updated_at` (`updated_at`)");

        // users columns
        $this->ensureTableColumn($users, 'user_id', "`user_id` int NOT NULL");
        $this->ensureTableColumn($users, 'snowflakes', "`snowflakes` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($users, 'contribute', "`contribute` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($users, 'tree_free_day', "`tree_free_day` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($users, 'daily_json', "`daily_json` varchar(2000) NOT NULL DEFAULT ''");
        $this->ensureTableColumn($users, 'tier_json', "`tier_json` varchar(2000) NOT NULL DEFAULT ''");
        $this->ensureTableColumn($users, 'updated_at', "`updated_at` int NOT NULL DEFAULT 0");
        $this->ensureTableIndex($users, 'user_id', "KEY `user_id` (`user_id`)");
        $this->ensureTableIndex($users, 'updated_at', "KEY `updated_at` (`updated_at`)");

        // log columns
        $this->ensureTableColumn($log, 'ts', "`ts` int NOT NULL");
        $this->ensureTableColumn($log, 'user_id', "`user_id` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($log, 'action', "`action` varchar(64) NOT NULL DEFAULT ''");
        $this->ensureTableColumn($log, 'delta_snow', "`delta_snow` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($log, 'delta_progress', "`delta_progress` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($log, 'loc_id', "`loc_id` int NOT NULL DEFAULT 0");
        $this->ensureTableColumn($log, 'meta', "`meta` varchar(2000) NOT NULL DEFAULT ''");
        $this->ensureTableIndex($log, 'ts', "KEY `ts` (`ts`)");
        $this->ensureTableIndex($log, 'action', "KEY `action` (`action`)");
        $this->ensureTableIndex($log, 'user_id', "KEY `user_id` (`user_id`)");

        // ensure server row
        $q = $this->mysqli->query("SELECT `id` FROM `{$server}` WHERE `id`=1 LIMIT 1");
        if (!$q || $q->num_rows == 0) {
            $this->mysqli->query("INSERT INTO `{$server}` (`id`,`updated_at`) VALUES (1,".time().")");
        }

        return true;
    }

    private function eventList($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->eventRegistryEnsure();

        $events = [];
        $opts = '';
        $q = $this->mysqli->query("SELECT `code`,`title` FROM `a_ivent_registry` ORDER BY `created_at` DESC");
        if ($q) {
            while ($r = $q->fetch_assoc()) {
                $events[] = $r;
                $c = htmlspecialchars($r['code'], ENT_QUOTES, 'UTF-8');
                $t = htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8');
                $opts .= "<option value=\"{$c}\">{$t} ({$c})</option>";
            }
        }
        if (!$opts) $opts = '<option value="newyear">Новый год (newyear)</option>';

        return $this->ok('Список ивентов обновлён', [
            'events' => $events,
            'event_options_html' => $opts,
            'refresh_event_select' => 1
        ]);
    }

    private function eventCreate($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->eventRegistryEnsure();

        $code = $this->eventCode($data['event_code'] ?? '');
        $title = trim((string)($data['event_title'] ?? ''));
        if ($title === '') return $this->err('Не указано название');

        $codeEsc = $this->escSql($code);
        $titleEsc = $this->escSql($title);
        $ok = $this->mysqli->query("INSERT INTO `a_ivent_registry` (`code`,`title`,`created_at`)
            VALUES ('{$codeEsc}','{$titleEsc}',".time().")
            ON DUPLICATE KEY UPDATE `title`=VALUES(`title`)");
        if (!$ok) return $this->err('Ошибка реестра: '.$this->mysqli->error);

        // таблицы создаём заранее, чтобы пресеты/цель не падали
        $this->eventEnsureTables($code);

        return $this->ok('Ивент создан/обновлён: '.$title, ['refresh_event_select'=>1]);
    }

    private function eventRename($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $this->eventRegistryEnsure();

        $code = $this->eventCode($data['event_code'] ?? '');
        $title = trim((string)($data['event_title'] ?? ''));
        if ($title === '') return $this->err('Не указано название');

        $ok = $this->mysqli->query("UPDATE `a_ivent_registry` SET `title`='".$this->escSql($title)."' WHERE `code`='".$this->escSql($code)."' LIMIT 1");
        if (!$ok) return $this->err('Ошибка: '.$this->mysqli->error);

        return $this->ok('Название обновлено', ['refresh_event_select'=>1]);
    }

    private function eventStatus($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');

        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');

        $q = $this->mysqli->query("SELECT * FROM `{$server}` WHERE `id`=1 LIMIT 1");
        if (!$q) return $this->err('Ошибка чтения server: '.$this->mysqli->error);
        $s = $q->fetch_assoc();
        if (!$s) return $this->err('Нет строки server.id=1');

        $progress = (int)$s['progress'];
        $target = (int)$s['target'];
        $pct = ($target > 0) ? round(($progress / max(1,$target)) * 100, 2) : 0;

        $html = '<div style="line-height:1.6;">'
            .'<b>Ивент:</b> '.htmlspecialchars($code, ENT_QUOTES, 'UTF-8').'<br>'
            .'<b>active:</b> '.(int)$s['active'].' | <b>override:</b> '.(int)$s['active_override'].' | <b>force_stop:</b> '.(int)$s['force_stop'].' | <b>pause:</b> '.(int)$s['pause'].'<br>'
            .'<b>mode:</b> '.htmlspecialchars($s['mode'], ENT_QUOTES, 'UTF-8').'<br>'
            .'<b>start_ts:</b> '.(int)$s['start_ts'].' | <b>end_ts:</b> '.(int)$s['end_ts'].'<br>'
            .'<b>progress:</b> '.$progress.' / <b>target:</b> '.$target.' ('.$pct.'%) | <b>tier:</b> '.(int)$s['tier'].'<br>'
            .'<b>mult:</b> PVE '.$s['mult_pve'].', PVP '.$s['mult_pvp'].', Gift '.$s['mult_gift'].', Tree '.$s['mult_tree'].'<br>'
            .'<b>updated_at:</b> '.(int)$s['updated_at']
            .'</div>';

        $res = $this->ok('Статус обновлён', [
            'event_status_html' => $html,
            'modal_title' => 'Статус ивента',
            'modal_html' => $html,
            'fill' => [
                'mult_pve' => $s['mult_pve'],
                'mult_pvp' => $s['mult_pvp'],
                'mult_gift' => $s['mult_gift'],
                'mult_tree' => $s['mult_tree'],
                'target' => $s['target']
            ]
        ]);
        // Дублируем modal_* в корень ответа для совместимости со старыми клиентами
        $res['modal_title'] = 'Статус ивента';
        $res['modal_html'] = $html;
        return $res;
    }

    private function eventStart($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');

        $days = (int)($data['days'] ?? 7);
        if ($days < 1) $days = 1;
        if ($days > 30) $days = 30;

        $target = (int)($data['target'] ?? 0);
        $mode = trim((string)($data['mode'] ?? 'standard'));
        $mode = $this->eventCode($mode); // нормализуем

        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');

        $now = time();
        $end = $now + $days * 86400;

        $sql = "UPDATE `{$server}` SET
            `active`=1,`active_override`=1,`force_stop`=0,`pause`=0,
            `mode`='".$this->escSql($mode)."',
            `start_ts`={$now},`end_ts`={$end},
            `target`={$target},
            `updated_at`={$now}
        WHERE `id`=1 LIMIT 1";

        $ok = $this->mysqli->query($sql);
        if (!$ok) return $this->err('Ошибка старта: '.$this->mysqli->error);

        return $this->ok('Ивент запущен: '.$code.' на '.$days.' дн.');
    }

    private function eventStop($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');
        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();
        $ok = $this->mysqli->query("UPDATE `{$server}` SET `force_stop`=1,`active`=0,`active_override`=0,`updated_at`={$now} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка stop: '.$this->mysqli->error);
        return $this->ok('Ивент остановлен (force_stop=1)');
    }

    private function eventUnstop($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');
        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();
        $ok = $this->mysqli->query("UPDATE `{$server}` SET `force_stop`=0,`updated_at`={$now} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка unstop: '.$this->mysqli->error);
        return $this->ok('force_stop снят');
    }

    private function eventPause($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');
        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();
        $ok = $this->mysqli->query("UPDATE `{$server}` SET `pause`=1,`updated_at`={$now} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка pause: '.$this->mysqli->error);
        return $this->ok('Пауза включена');
    }

    private function eventResume($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');
        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();
        $ok = $this->mysqli->query("UPDATE `{$server}` SET `pause`=0,`updated_at`={$now} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка resume: '.$this->mysqli->error);
        return $this->ok('Пауза выключена');
    }

    private function eventTarget($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');
        $target = (int)($data['target'] ?? 0);

        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();
        $ok = $this->mysqli->query("UPDATE `{$server}` SET `target`={$target},`updated_at`={$now} WHERE `id`=1 LIMIT 1");
        if (!$ok) return $this->err('Ошибка target: '.$this->mysqli->error);
        return $this->ok('Цель установлена: '.$target, [
            'fill' => [ 'target' => $target ]
        ]);
    }

    private function eventPreset($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');
        $preset = $this->eventCode($data['preset'] ?? 'standard');

        $m = ['pve'=>1.00,'pvp'=>1.00,'gift'=>1.00,'tree'=>1.00];
        if ($preset === 'easy_start')  $m = ['pve'=>1.30,'pvp'=>1.15,'gift'=>1.20,'tree'=>1.20];
        if ($preset === 'final_night') $m = ['pve'=>2.00,'pvp'=>1.60,'gift'=>1.20,'tree'=>1.50];
        if ($preset === 'antifarm')    $m = ['pve'=>0.80,'pvp'=>1.00,'gift'=>1.30,'tree'=>1.00];

        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();

        $sql = "UPDATE `{$server}` SET
            `mode`='".$this->escSql($preset)."',
            `mult_pve`={$m['pve']},
            `mult_pvp`={$m['pvp']},
            `mult_gift`={$m['gift']},
            `mult_tree`={$m['tree']},
            `updated_at`={$now}
        WHERE `id`=1 LIMIT 1";

        $ok = $this->mysqli->query($sql);
        if (!$ok) return $this->err('Ошибка preset: '.$this->mysqli->error);

        return $this->ok('Пресет применён: '.$preset, [
            'fill' => [
                'mult_pve' => number_format($m['pve'], 2, '.', ''),
                'mult_pvp' => number_format($m['pvp'], 2, '.', ''),
                'mult_gift' => number_format($m['gift'], 2, '.', ''),
                'mult_tree' => number_format($m['tree'], 2, '.', '')
            ]
        ]);
    }

    private function eventMultipliers($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? 'newyear');

        $pve  = $this->parseDecimal($data['mult_pve'] ?? 1.00, 1.00, 0.00, 10.00);
        $pvp  = $this->parseDecimal($data['mult_pvp'] ?? 1.00, 1.00, 0.00, 10.00);
        $gift = $this->parseDecimal($data['mult_gift'] ?? 1.00, 1.00, 0.00, 10.00);
        $tree = $this->parseDecimal($data['mult_tree'] ?? 1.00, 1.00, 0.00, 10.00);

        $this->eventEnsureTables($code);
        $server = $this->eventTable($code, 'server');
        $now = time();

        $sql = "UPDATE `{$server}` SET
            `mult_pve`={$pve},
            `mult_pvp`={$pvp},
            `mult_gift`={$gift},
            `mult_tree`={$tree},
            `updated_at`={$now}
        WHERE `id`=1 LIMIT 1";

        $ok = $this->mysqli->query($sql);
        if (!$ok) return $this->err('Ошибка multipliers: '.$this->mysqli->error);

        return $this->ok('Коэффициенты сохранены', [
            'fill' => [
                'mult_pve' => number_format($pve, 2, '.', ''),
                'mult_pvp' => number_format($pvp, 2, '.', ''),
                'mult_gift' => number_format($gift, 2, '.', ''),
                'mult_tree' => number_format($tree, 2, '.', '')
            ]
        ]);
    }

    private function eventDelete($data) {
        if (!$this->isAdmin()) return $this->err('Нет прав!');
        $code = $this->eventCode($data['event_code'] ?? '');
        $confirm = trim((string)($data['confirm_code'] ?? ''));
        if ($code === '' || $confirm !== $code) return $this->err('Подтверждение не совпадает с code');

        $server = $this->eventTable($code, 'server');
        $users  = $this->eventTable($code, 'users');
        $log    = $this->eventTable($code, 'log');

        $this->mysqli->query("DROP TABLE IF EXISTS `{$server}`");
        $this->mysqli->query("DROP TABLE IF EXISTS `{$users}`");
        $this->mysqli->query("DROP TABLE IF EXISTS `{$log}`");

        $this->eventRegistryEnsure();
        $this->mysqli->query("DELETE FROM `a_ivent_registry` WHERE `code`='".$this->escSql($code)."' LIMIT 1");

        return $this->ok('Таблицы ивента удалены: '.$code, ['refresh_event_select'=>1]);
    }

    // 1. Молчание / снятие молчания
    private function muteUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2,3])) return $this->err('Нет прав');
        $time = isset($data['time']) ? intval($data['time']) : 10;
        $title = isset($data['title']) ? $this->escapeMe($data['title']) : '...';
        $ban = $this->_unParseData($user['ban']);
        $ban['chat'] = time() + $time * 60;
        $ban = $this->_parseData($ban);
        $this->mysqli->query("UPDATE users SET ban='".$this->mysqli->real_escape_string($ban)."' WHERE id='".$user['id']."'");
        $msg = 'Выдано молчание тренеру <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> на <span>'.$this->downcounter($time*60).'</span>. По причине: <span>'.$title.'</span>';
        $chatMsg = 'Тренер '.$user['login'].' получил молчание на '.$this->downcounter($time*60).'. Причина: '.$title;
        $this->sendChatSystemMsg($chatMsg);
        return $this->ok($msg, ['css'=>['mut'], 'chat_msg'=>$chatMsg]);
    }
    private function unmuteUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2,3])) return $this->err('Нет прав');
        $title = isset($data['title']) ? $this->escapeMe($data['title']) : '...';
        $ban = $this->_unParseData($user['ban']);
        $ban['chat'] = 0;
        $ban = $this->_parseData($ban);
        $this->mysqli->query("UPDATE users SET ban='".$this->mysqli->real_escape_string($ban)."' WHERE id='".$user['id']."'");
        $msg = 'С тренера <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> снято молчание по причине: <span>'.$title.'</span>';
        $chatMsg = 'С тренера '.$user['login'].' снято молчание. Причина: '.$title;
        $this->sendChatSystemMsg($chatMsg);
        return $this->ok($msg, ['css'=>['mut'], 'chat_msg'=>$chatMsg]);
    }
    // 2. Предупреждение
    private function warnUser($data) {
    $user = $this->getUserById($data['user']);
    if (!$user) return $this->err('Пользователь не найден');
    if (!in_array($this->selfUser['user_group'], [1,2,3])) return $this->err('Нет прав');
    $title = isset($data['title']) ? $this->escapeMe($data['title']) : '...';
    $msg = 'Вам выдано предупреждение по причине: '.$title;

    // Формируем текст сообщения для чата
    $chatMsg = 'Тренер <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> получил предупреждение. Причина: <span>'.$title.'</span>';
    $this->sendChatSystemMsg($chatMsg);

    return $this->ok($msg, ['css'=>['pred'], 'chat_msg'=>$chatMsg]);
}
    private function unwarnUser($data) {
    $user = $this->getUserById($data['user']);
    if (!$user) return $this->err('Пользователь не найден');
    if (!in_array($this->selfUser['user_group'], [1,2,3])) return $this->err('Нет прав');
    $title = isset($data['title']) ? $this->escapeMe($data['title']) : '...';
    $msg = 'С пользователя снято предупреждение: '.$title;

    // Формируем сообщение для чата
    $chatMsg = 'С тренера <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> снято предупреждение. Причина: <span>'.$title.'</span>';
    $this->sendChatSystemMsg($chatMsg);

    return $this->ok($msg, ['css'=>['pred'], 'chat_msg'=>$chatMsg]);
}
    // 3. Бан и разбан
    private function banUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $title = isset($data['title']) ? $this->escapeMe($data['title']) : '...';
        $this->mysqli->query("UPDATE users SET status='ban', rang='Заблокированный', about='Причина блокировки: ".$this->mysqli->real_escape_string($title)."', user_group='7' WHERE id='".$user['id']."'");
        $msg = 'Тренер <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> заблокирован. Причина: <span>'.$title.'</span>';
        $chatMsg = 'Тренер '.$user['login'].' был заблокирован. Причина: '.$title;
        $this->sendChatSystemMsg($chatMsg);
        return $this->ok($msg, ['css'=>['arest'], 'chat_msg'=>$chatMsg]);
    }
    private function unbanUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $this->mysqli->query("UPDATE users SET status='free', rang='', about='', user_group='6' WHERE id='".$user['id']."'");
        $msg = 'Пользователь разблокирован.';
        $chatMsg = 'Тренер '.$user['login'].' был разблокирован.';
        $this->sendChatSystemMsg($chatMsg);
        return $this->ok($msg, ['css'=>['arest'], 'chat_msg'=>$chatMsg]);
    }
    // 4. Телепорт
    private function tpUser($data) {
        if (!in_array($this->selfUser['user_group'], [1, 9])) return $this->err('Нет прав');
        $locName = isset($data['loc_name']) ? $data['loc_name'] : '';
        if (!$locName) return $this->err('Не указано имя локации');
        $baseLoc = $this->mysqli->query("SELECT id FROM base_location WHERE name='".$this->mysqli->real_escape_string($locName)."'")->fetch_assoc();
        if (!$baseLoc) return $this->err('Локация не найдена');
        $this->mysqli->query("UPDATE users SET location='".$baseLoc['id']."' WHERE id='".$this->selfUser['id']."'");
        return $this->ok('Телепорт выполнен!');
    }
    // 5. Смена группы
    private function setGroup($data) {
        if (!in_array($this->selfUser['user_group'], [1])) return $this->err('Нет прав');
        $user = $this->getUserById($data['user']);
        $group = isset($data['group']) ? intval($data['group']) : 6;
        if (!$user) return $this->err('Пользователь не найден');
        $this->mysqli->query("UPDATE users SET user_group=".$group." WHERE id=".$user['id']);
        return $this->ok('Вам была присвоена новая группа.');
    }
    // 6. Выдача трофея
    private function giveTrophy($data) {
        if ($this->selfUser['user_group'] != 1) return $this->err('Нет прав');
        $user = $this->getUserByLogin($data['login']);
        $item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;
        $about = isset($data['about']) ? $this->escapeMe($data['about']) : '';
        if (!$user) return $this->err('Пользователь не найден');
        if (!$item_id) return $this->err('Не указан id трофея');
        $stmt = $this->mysqli->prepare("INSERT INTO items_users (item_id, count, user, about, trophy) VALUES (?,1,?, ?, 1)");
        $stmt->bind_param("iis", $item_id, $user['id'], $about);
        $stmt->execute();
        $stmt->close();
        return $this->ok('Награда выдана');
    }
    // 7. Системное сообщение
    private function systemMsg($data) {
    if ($this->selfUser['user_group'] != 1) return $this->err('Нет прав');
    $text = isset($data['text']) ? trim($data['text']) : '';
    if (!$text) return $this->err('Текст сообщения не должен быть пустым.');

    // Отправка нотификаций всем (оставляем как было)
    $qUsers = $this->mysqli->query("SELECT id, online FROM users WHERE id != '".$this->selfUser['id']."'");
    while ($user = $qUsers->fetch_assoc()) {
        $uid = $user['id'];
        $this->sendNotify($uid, [
            'type'  => 'system',
            'title' => 'Системное сообщение',
            'text'  => $text,
            'icon'  => '/img/icons/sys_notify.png'
        ]);
        $this->mysqli->query("INSERT INTO notification (`user`, `text`, `date`, `img`) VALUES ($uid, '".$this->mysqli->real_escape_string($text)."', NOW(), '/img/icons/sys_notify.png')");
    }

    // Вставка в чат через системную функцию
    $chatMsg = '[Системное сообщение]: '.$text;
    $this->sendChatSystemMsg($chatMsg);

    return $this->ok('Системное сообщение отправлено всем!', ['chat_msg'=>$chatMsg]);
}
    // 8. Комментарий
    private function addComment($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $title = isset($data['title']) ? $this->escapeMe($data['title']) : '';
        if ($title) {
            $this->mysqli->query("INSERT INTO base_comments (user1, user2, comment) VALUES ('".$this->selfUser['id']."', '".$user['id']."', '".$this->mysqli->real_escape_string($title)."')");
            return $this->ok('Комментарий добавлен');
        }
        return $this->err('Комментарий не может быть пустым');
    }
    // 9. Арест (prison) и освобождение (free)
    private function prisonUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $time = isset($data['time']) ? intval($data['time']) : 10;
        $title = isset($data['title']) ? $this->escapeMe($data['title']) : '...';
        $ban = $this->_unParseData($user['ban']);
        $ban['game'] = time() + $time * 24 * 3600;
        $ban = $this->_parseData($ban);
        $this->mysqli->query("INSERT INTO teleport_user (user, location, go) VALUES (".$this->selfUser['id'].",".$this->selfUser['location'].",'prison') ");
        $this->mysqli->query("UPDATE users SET user_group=8, location=0, ban='".$this->mysqli->real_escape_string($ban)."' WHERE id='".$user['id']."'");
        $msg = 'Тренер <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> арестован на <span>'.$this->downcounter($time*24*3600).'</span>. По причине: <span>'.$title.'</span>';
        $chatMsg = 'Тренер '.$user['login'].' арестован на '.$this->downcounter($time*24*3600).'. Причина: '.$title;
        $this->sendChatSystemMsg($chatMsg);
        return $this->ok($msg, ['css'=>['arest'], 'chat_msg'=>$chatMsg]);
    }
    private function freeUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $ban = $this->_unParseData($user['ban']);
        $ban['game'] = 0;
        $ban = $this->_parseData($ban);
        $banan = $this->mysqli->query("SELECT * FROM teleport_user WHERE user = ".$user['id'])->fetch_assoc();
        $banan2 = ($banan ? $banan['location'] : 1);
        $this->mysqli->query("UPDATE users SET user_group=6, location='".$banan2."', ban='".$this->mysqli->real_escape_string($ban)."' WHERE id='".$user['id']."'");
        $this->mysqli->query("DELETE FROM teleport_user WHERE user = ".$user['id']);
        $msg = 'Тренер <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> освобожден из тюрьмы.';
        $chatMsg = 'Тренер '.$user['login'].' освобожден из тюрьмы.';
        $this->sendChatSystemMsg($chatMsg);
        return $this->ok($msg, ['css'=>['arest'], 'chat_msg'=>$chatMsg]);
    }
    // 10. Штраф
    private function fineUser($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $sum = isset($data['sum']) ? intval($data['sum']) : 0;
        $title = isset($data['title']) ? $this->escapeMe($data['title']) : '';
        if ($sum > 0) {
            $this->minus_item(1, $sum, $user['id']);
            $msg = 'Тренеру <div class="u-'.$user['user_group'].'">'.$user['login'].'</div> выписан штраф, на сумму <span>'.$sum.' генк.</span> По причине: <span>'.$title.'</span>';
            $this->sendChatSystemMsg($chatMsg);
            return $this->ok($msg, ['css'=>['arest']]);
        }
        return $this->err('Сумма штрафа должна быть больше 0');
    }
    // 11. Изъятие покемона
    private function transfPokemon($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2])) return $this->err('Нет прав');
        $poke = isset($data['pokeID']) ? intval($data['pokeID']) : 0;
        if ($poke > 0) {
            $result = $this->mysqli->query("SELECT id, user_id FROM user_pokemons WHERE id = '$poke' LIMIT 1");
            if ($result->num_rows > 0) {
                $pokemon = $result->fetch_assoc();
                if ($pokemon['user_id'] != 2) {
                    $this->mysqli->query("UPDATE user_pokemons SET user_id = 4 WHERE id = '$poke'");
                    $this->mysqli->query("INSERT INTO admin_logs (admin_id, action, target_id, timestamp) VALUES ('".$this->selfUser['id']."', 'transf_pokemon', '$poke', NOW())");
                    $this->notify($user['id'], 'Покемон успешно изъят. ID покемона: '.$poke);
                    $this->notify($pokemon['user_id'], 'У вас изъят покемон, ID которого был: '.$poke);
                    return $this->ok('Покемон успешно изъят.');
                } else {
                    return $this->err('Этот покемон уже принадлежит системе!');
                }
            } else {
                return $this->err('Покемон с указанным ID не найден.');
            }
        } else {
            return $this->err('Некорректный ID покемона.');
        }
    }
    // 12. Очищение профиля
    private function clearProfile($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if (!in_array($this->selfUser['user_group'], [1,2,3])) return $this->err('Нет прав');
        $this->mysqli->query("UPDATE users SET about='' WHERE id='".$user['id']."'");
        $this->notify($user['id'], 'Профиль тренера '.$user['login'].' очищен.');
        return $this->ok('Профиль очищен.');
    }
    // 13. Назначение модератора
    private function giveModer($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if ($this->selfUser['id'] != 5) return $this->err('Нет прав');
        $this->mysqli->query("UPDATE users SET user_group=3 WHERE id=".$user['id']);
        $this->notify($user['id'], 'Вы стали модератором.');
        return $this->ok('Права модератора назначены.');
    }
    private function removeModer($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if ($this->selfUser['id'] != 5) return $this->err('Нет прав');
        $this->mysqli->query("UPDATE users SET user_group=6 WHERE id=".$user['id']);
        $this->notify($user['id'], 'Вы больше не модератор.');
        return $this->ok('Модератор снят.');
    }
    private function giveNast($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if ($this->selfUser['id'] != 5) return $this->err('Нет прав');
        $this->mysqli->query("UPDATE users SET user_group=4 WHERE id=".$user['id']);
        $this->notify($user['id'], 'Вы стали наставником.');
        return $this->ok('Права наставника назначены.');
    }
    private function removeNast($data) {
        $user = $this->getUserById($data['user']);
        if (!$user) return $this->err('Пользователь не найден');
        if ($this->selfUser['id'] != 5) return $this->err('Нет прав');
        $this->mysqli->query("UPDATE users SET user_group=6 WHERE id=".$user['id']);
        $this->notify($user['id'], 'Вы больше не наставник.');
        return $this->ok('Наставник снят.');
    }
    private function upGroup($data) {
        if (!in_array($this->selfUser['user_group'], [1])) return $this->err('Нет прав');
        $user = $this->getUserById($data['user']);
        $group = isset($data['group']) ? intval($data['group']) : 6;
        if (!$user) return $this->err('Пользователь не найден');
        $this->mysqli->query("UPDATE users SET user_group=".$group." WHERE id=".$user['id']);
        return $this->ok('Вам была присвоена новая группа.');
    }
    // 14. Удаление сообщения
    private function deleteMsg($data) {
        $msg_id = isset($data['msg_id']) ? intval($data['msg_id']) : 0;
        $msg_text = isset($data['msg_text']) ? $data['msg_text'] : '';
        if ($msg_id > 0) {
            $this->notify($this->selfUser['id'], '{"msg_del":'.intval($msg_id).'}');
            $this->notify($this->selfUser['id'], 'Удалено сообщение: '.$msg_text);
            return $this->ok('Сообщение удалено');
        }
        return $this->err('ID сообщения не указан');
    }
    // 15. Жалоба на сообщение
    private function reportMsg($data) {
        $msg_id = isset($data['msg_id']) ? intval($data['msg_id']) : 0;
        $msg_text = isset($data['msg_text']) ? $data['msg_text'] : '';
        if ($msg_id > 0) {
            $this->notify($this->selfUser['id'], 'Поступила жалоба на сообщение: '.$msg_text);
            return $this->ok('Жалоба отправлена');
        }
        return $this->err('ID сообщения не указан');
    }
    private function editLocation($data) {
        // Проверка наличия прав
        if ((int)$this->selfUser['user_group'] !== 9) {
            return $this->err('Нет прав на выполнение этой операции!');
        }

        // Валидация и экранирование входных данных
        $loc_id = isset($data['loc_id']) ? (int)$data['loc_id'] : 0;
        $description = isset($data['description']) ? trim($data['description']) : '';

        if ($loc_id <= 0) {
            return $this->err('Некорректный ID локации.');
        }
        if ($description === '') {
            return $this->err('Описание не может быть пустым.');
        }

        // Проверяем, существует ли локация
        $q = $this->mysqli->query("SELECT id FROM base_location WHERE id = '$loc_id' LIMIT 1");
        if (!$q || $q->num_rows === 0) {
            return $this->err('Локация с таким ID не найдена.');
        }

        // Экранируем строку
        $description_sql = $this->mysqli->real_escape_string($description);

        // Обновляем описание
        $upd = $this->mysqli->query("UPDATE base_location SET description = '$description_sql' WHERE id = '$loc_id'");

        if ($upd) {
            return $this->ok('Описание локации успешно изменено!', [
                'loc_id' => $loc_id,
                'description' => htmlspecialchars($description)
            ]);
        } else {
            return $this->err('Ошибка при обновлении описания: '.$this->mysqli->error);
        }
    }
private function sendAnnouncement($data) {
    // 1. Проверка прав
    if (!in_array($this->selfUser['user_group'], [1, 2])) {
        return $this->err('Нет прав для отправки объявления!');
    }

    $text = isset($data['text']) ? trim($data['text']) : '';
    if (!$text) return $this->err('Текст объявления не должен быть пустым.');

    // 2. Сохраняем в БД (активное объявление)
    $this->mysqli->query(
        "INSERT INTO announcements (`text`, `admin_id`, `date`, `active`) VALUES 
        ('".$this->mysqli->real_escape_string($text)."', '".$this->selfUser['id']."', NOW(), 1)"
    );
    $announcement_id = $this->mysqli->insert_id; // Получаем ID

    // 3. Отправка в чат как обычное сообщение type=0, msg_class='chat-announcement', active=1, id объявления
    $this->sendAnnouncementMessage($text, $announcement_id, 1);

    // 4. WebSocket push (если нужно)
    $socketServer = defined('NODE_SERVER_IP') ? NODE_SERVER_IP : '127.0.0.1';
    $socketPort   = defined('NODE_SERVER_PORT') ? NODE_SERVER_PORT : '8081';
    $secret       = defined('NODE_SERVER_SECRET') ? NODE_SERVER_SECRET : '';
    $payload = json_encode([
        'secret' => $secret,
        'target' => 'all',
        'event'  => 'announcement',
        'data'   => ['text' => $text, 'id' => $announcement_id, 'active' => 1]
    ]);
    $ch = curl_init("http://$socketServer:$socketPort/send-message");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 2
    ]);
    curl_exec($ch);
    curl_close($ch);

    return $this->ok('Объявление отправлено!');
}

// Новый метод для отправки объявления как обычного сообщения type=0
private function sendAnnouncementMessage($text, $announcement_id, $active = 1) {
    $info = json_encode([
        'user_id'         => 0,
        'user_login'      => '',
        'user_sex'        => '',
        'user_group'      => '',
        'user_msg_color'  => '',
        'user_msg'        => $text,
        'msg_type'        => 0,
        'msg_time'        => date('H:i'),
        'msg_class'       => 'chat-announcement',
        'msg_region_id'   => '0',
        'msg_region_name' => '',
        'img'             => '',
        'active'          => $active,             // <-- добавлено!
        'announcement_id' => $announcement_id     // <-- добавлено!
    ], JSON_UNESCAPED_UNICODE);

    $type = 0;
    $user_id = 0;
    $toUser = 0;
    $location = 0;
    $clan = 0;
    $lifetime = time() + 2 * 3600;

    $this->mysqli->query("INSERT INTO chat_new
        (`type`, `user`, `touser`, `location`, `clan`, `info`, `lifetime`, `img`, `img_to`)
        VALUES
        ($type, $user_id, $toUser, $location, $clan, '".$this->mysqli->real_escape_string($info)."', $lifetime, '', '')
    ");
}
    // 16. Секретарь-панель
    private function sekretarPanel($data) {
        $is_curator = ($this->selfUser['user_group'] == 10);
        $tpl = '<div class="st-panel-header">
                <div class="st-panel-title">Секретарь</div>
                <div class="st-panel-close" onclick="closeLittleModal()"><i class="fas fa-times"></i></div>
            </div>
            <div class="st-sekretar-tabs">
                <div class="st-tab-btn active" data-tab="gyms">Гим-лидеры</div>
                <div class="st-tab-btn" data-tab="tournaments">Турниры</div>';
        if ($is_curator) $tpl .= '<div class="st-tab-btn" data-tab="tournament-admin">Управление</div>';
        $tpl .= '</div>
            <div class="st-sekretar-tab-content gyms-tab active"></div>
            <div class="st-sekretar-tab-content tournaments-tab"></div>';
        if ($is_curator) $tpl .= '<div class="st-sekretar-tab-content tournament-admin-tab"></div>';
        $tpl .= '<script>
        $(".st-sekretar-tabs .st-tab-btn").on("click", function() {
            var tab = $(this).data("tab");
            $(".st-sekretar-tabs .st-tab-btn").removeClass("active");
            $(this).addClass("active");
            $(".st-sekretar-tab-content").removeClass("active");
            $("." + tab + "-tab").addClass("active");
            if(tab === "gyms" && !$(".gyms-tab").html().trim()) loadGymsTab();
            if(tab === "tournaments" && !$(".tournaments-tab").html().trim()) loadTournamentsTab();
            if(tab === "tournament-admin" && !$(".tournament-admin-tab").html().trim()) loadTournamentAdminTab();
        });
        function loadGymsTab() {
            $.post("/do/gym.php", {type: "gyms_panel"}, function(resp){
                $(".gyms-tab").html(resp.html);
            }, "json");
        }
        function loadTournamentsTab() {
            $.post("/do/gym.php", {type: "tournaments_panel"}, function(resp){
                $(".tournaments-tab").html(resp.html);
            }, "json");
        }
        function loadTournamentAdminTab() {
            $.post("/do/gym.php", {type: "tournament_admin_panel"}, function(resp){
                $(".tournament-admin-tab").html(resp.html);
            }, "json");
        }
        $(function(){ loadGymsTab(); });
        </script>';
        return $this->ok('ok', ['html' => $tpl]);
    }

    
    // --- ВСПОМОГАТЕЛЬНЫЕ ДЛЯ ИВЕНТОВ/DB ---
    private function parseDecimal($v, $def = 1.00, $min = 0.00, $max = 10.00) {
        $v = str_replace(',', '.', trim((string)$v));
        if ($v === '') return (float)$def;
        if (!is_numeric($v)) return (float)$def;
        $f = (float)$v;
        if ($f < $min) $f = $min;
        if ($f > $max) $f = $max;
        // 2 знака после запятой для стабильного хранения
        return (float)number_format($f, 2, '.', '');
    }

    private function tableHasColumn($table, $col) {
        $table = preg_replace('/[^a-z0-9_]+/i', '', (string)$table);
        $col = preg_replace('/[^a-z0-9_]+/i', '', (string)$col);
        if ($table === '' || $col === '') return false;
        $q = $this->mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
        return ($q && $q->num_rows > 0);
    }

    private function ensureTableColumn($table, $col, $ddl) {
        if ($this->tableHasColumn($table, $col)) return true;
        return (bool)$this->mysqli->query("ALTER TABLE `{$table}` ADD COLUMN {$ddl}");
    }

    private function ensureTableIndex($table, $idxName, $ddl) {
        $table = preg_replace('/[^a-z0-9_]+/i', '', (string)$table);
        $idxName = preg_replace('/[^a-z0-9_]+/i', '', (string)$idxName);
        if ($table === '' || $idxName === '') return false;

        $q = $this->mysqli->query("SHOW INDEX FROM `{$table}` WHERE Key_name='{$idxName}'");
        if ($q && $q->num_rows > 0) return true;

        return (bool)$this->mysqli->query("ALTER TABLE `{$table}` ADD {$ddl}");
    }

    // --- ВСПОМОГАТЕЛЬНЫЕ ---
    private function ok($msg, $data = []) {
        $this->response['error'] = false;
        $this->response['msg'] = $msg;
        $this->response['data'] = $data;
        return $this->response;
    }
    private function err($msg) {
        $this->response['error'] = true;
        $this->response['msg'] = $msg;
        return $this->response;
    }
    private function escapeMe($s) {
        return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
    }
    private function numFloat($v) {
        $s = trim((string)$v);
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9\.\-]/', '', $s);
        if ($s === '' || $s === '-' || $s === '.') return 0.0;
        $f = (float)$s;
        if ($f < 0) $f = 0;
        if ($f > 10) $f = 10;
        return round($f, 2);
    }
    private function getUserById($id) {
        return $this->mysqli->query("SELECT * FROM users WHERE id='".intval($id)."'")->fetch_assoc();
    }
    private function getUserByLogin($login) {
        return $this->mysqli->query("SELECT * FROM users WHERE login='".$this->mysqli->real_escape_string($login)."'")->fetch_assoc();
    }
    private function notify($user_id, $text) {
        $this->mysqli->query("INSERT INTO notification (user, text, date, img) VALUES ('$user_id', '".$this->mysqli->real_escape_string($text)."', NOW(), '')");
    }
    private function sendNotify($uid, $data) {
        // Реализуй push/notify по своему проекту (например, через fast_notify)
    }
    private function minus_item($item_id, $count, $user_id) {
        // Реализация списания предмета у пользователя
    }
    private function _unParseData($str) {
        return @json_decode($str, true) ?: [];
    }
    private function _parseData($arr) {
        return json_encode($arr);
    }
    private function downcounter($sec) {
        $d = floor($sec/86400);
        $h = floor(($sec%86400)/3600);
        $m = floor(($sec%3600)/60);
        $s = $sec%60;
        $str = ($d>0?"$d дн ":"").($h>0?"$h ч ":"").($m>0?"$m мин ":"").($s>0?"$s сек":"");
        return trim($str);
    }
    private function give_item($item_id, $sum, $user_id) {
    if (!in_array($this->userID, [4])) return false;
    if ($item_id <= 0 || $sum <= 0) return false;
    $user = $this->getUserById($user_id);
    if (!$user) return false;
    itemAdd($item_id, $sum, $user['id']);
    $this->add('Вам выдан предмет ID: '.$item_id.' в количестве: '.$sum.' шт.', 1, $user);
    Info::_logGame($this->userID, 'ADD_ITEM', [
        'user_to' => $user['id'],
        'item_id' => $item_id,
        'count' => $sum,
    ], 'items');
    return true;
}
    private function sendChatSystemMsg($msg) {
    $user_id = "1"; // строкой
    $user_login = 'System';
    $user_sex = 'm';
    $user_group = "1"; // строкой
    $user_msg_color = "4"; // или "1", если хочешь цвет как у модеров, иначе свой
    $msg_type = 0; // строкой "0", если это обычное сообщение
    $msg_time = date('H:i');
    $msg_class = "";
    $msg_region_id = "0";
    $msg_region_name = "";
    $img = "system"; // или "no-user-img" если нет аватара

    $info = json_encode([
        'user_id'         => $user_id,
        'user_login'      => $user_login,
        'user_sex'        => $user_sex,
        'user_group'      => $user_group,
        'user_msg_color'  => $user_msg_color,
        'user_msg'        => $msg,
        'msg_type'        => $msg_type,
        'msg_time'        => $msg_time,
        'msg_class'       => $msg_class,
        'msg_region_id'   => $msg_region_id,
        'msg_region_name' => $msg_region_name,
        'img'             => $img
    ], JSON_UNESCAPED_UNICODE);

    $type = 0;
    $toUser = 0;
    $location = 0;
    $clan = 0;
   $lifetime = time() + 2 * 3600;

    $this->mysqli->query("INSERT INTO chat_new
        (`type`, `user`, `touser`, `location`, `clan`, `info`, `lifetime`, `img`, `img_to`)
        VALUES
        ($type, $user_id, $toUser, $location, $clan, '".$this->mysqli->real_escape_string($info)."', $lifetime, '$img', '')
    ");
}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['type'] === 'upload_location_image') {
    header('Content-Type: application/json; charset=utf-8');
    $loc_id = intval($_POST['loc_id']);
    if ($loc_id <= 0) {
        echo json_encode(['error'=>true, 'msg'=>'Некорректный ID!']);
        exit;
    }
    if (!isset($_FILES['location_img']) || $_FILES['location_img']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error'=>true, 'msg'=>'Ошибка загрузки файла!']);
        exit;
    }
    $fileType = mime_content_type($_FILES['location_img']['tmp_name']);
    if (strpos($fileType, 'png') === false) {
        echo json_encode(['error'=>true, 'msg'=>'Можно только PNG!']);
        exit;
    }
    $uploadDir = $_SERVER['DOCUMENT_ROOT'].'/img/world/location/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $fileName = $loc_id.'.png';
    $destination = $uploadDir.$fileName;
    if (move_uploaded_file($_FILES['location_img']['tmp_name'], $destination)) {
        $src = '/img/world/location/'.$fileName;
        echo json_encode([
            'error' => false,
            'msg'   => 'Изображение успешно загружено!',
            'data'  => ['src' => $src]
        ]);
    } else {
        echo json_encode(['error'=>true, 'msg'=>'Ошибка при сохранении файла!']);
    }
    exit;
}
$adminPanel = new AdminPanelFull($mysqli, $user);
$data = $_POST; // поля формы
$result = $adminPanel->handle($type, $data);
header('Content-Type: application/json');
echo json_encode($result);
exit;
# AUDIT — Этап A (инвентаризация и аудит)

## A1. Карта системы

### 1) Структура проекта (ключевые каталоги)

- `Npc/` — набор PHP-скриптов диалогов/логики НПС (например, покецентр/лечение и т.п.).
- `do/` — основная коллекция HTTP/AJAX entrypoints (обработчики действий и API).
- `inc/` — общий код (конфиги, функции, классы, подсистемы боёв/ивентов).
- `templates/`, `css/`, `js/` — фронтенд-ресурсы и шаблоны.
- `cron/` — фоновые задания/крон-скрипты.
- `payment/` — платёжные/пополнения (интеграции и обработчики).

### 2) Entry points (внешние точки входа)

**HTTP (страницы/входные скрипты):**
- `index.php` — главный роутер/страница, в том числе выдача JSON по `?ajax=`.【index.php†L1-L178】
- `welcome.php`, `world.php`, `rules.php` и др. в корне — страницы сайта.

**AJAX/Action endpoints (`/do/*`):**
- `do/registration.php` — регистрация пользователя (JSON).【do/registration.php†L1-L119】
- `do/sign.php` — логин/авторизация пользователя (JSON).【do/sign.php†L1-L159】
- `do/itemsAction.php` — операции с предметами/инвентарём (сценарии item use/drop).【do/itemsAction.php†L1-L239】
- `do/shop_di.php` — донат-магазин и лимиты/покупки предметов.【do/shop_di.php†L1-L160】
- `do/giftshop.php` — магазин подарков (покупка/получение/списание).【do/giftshop.php†L1-L259】
- `do/viewBattle.php` — список активных PvP боёв (UI/просмотр).【do/viewBattle.php†L26-L71】
- `do/viewBattleView.php` — визуализация конкретного боя и лога раундов.【do/viewBattleView.php†L21-L146】
- `do/WorldBoss.php` — роутер/контроллер действий по мировым боссам.【do/WorldBoss.php†L1-L199】
- `do/calendarAction.php` — события/награды/игровые активности (крупный action-скрипт).【do/calendarAction.php†L1-L120】

**Прочие API в корне:**
- `getEmojis.php`, `getEmojiPacks.php`, `get_emojis.php` — выдача/покупка наборов смайлов и списков файлов.【getEmojis.php†L1-L12】【getEmojiPacks.php†L1-L20】【get_emojis.php†L1-L174】

**Cron/фоновые задачи:**
- `inc/battlepass_cron.php` и скрипты в `cron/` (периодические задачи).

### 3) База данных (ключевые таблицы и назначение)

> Источник: дамп `karasrgd_1.sql`.

**Пользователи/покемоны:**
- `users` — учетные записи и состояние игрока.【karasrgd_1.sql†L53168】
- `base_pokemons` — справочник покемонов.【karasrgd_1.sql†L41088】
- `user_pokemons` — покемоны пользователя.【karasrgd_1.sql†L58148】

**Инвентарь/магазины:**
- `base_items` — справочник предметов.【karasrgd_1.sql†L39678】
- `items_users` — предметы пользователей (инвентарь).【karasrgd_1.sql†L49815】
- `aquarits` — донат-магазин и цены/лимиты.【karasrgd_1.sql†L3282】
- `shop_log` — лог покупок/лимитов магазина.【karasrgd_1.sql†L52673】
- `giftshop_items` — справочник подарков магазина подарков.【karasrgd_1.sql†L49439】
- `gifts` — подарки пользователям и статус получения.【karasrgd_1.sql†L49419】

**Боёвка:**
- `battle` — активные/исторические бои (основной стейт).【karasrgd_1.sql†L43310】
- `battle_log` — журнал боёв (лог/текст по раундам).【karasrgd_1.sql†L43422】
- `battle_effects` — эффекты/статусы в боях.【karasrgd_1.sql†L43364】

**Квесты:**
- `user_quests` — прогресс пользователя по квестам.【karasrgd_1.sql†L59058】
- `quest_steps` — журнал/шаги в дневнике квестов.【karasrgd_1.sql†L52004】

**НПС:**
- `npcs` — базовые данные NPC.【karasrgd_1.sql†L51289】
- `npc_battle` — данные/настройки боёв с NPC.【karasrgd_1.sql†L51307】

**Мировые боссы:**
- `world_bosses` — конфигурация мировых боссов.【karasrgd_1.sql†L59415】
- `world_boss_instances` — активные инстансы мировых боссов.【karasrgd_1.sql†L59482】
- `world_boss_participants` — участники рейдов мировых боссов.【karasrgd_1.sql†L61684】

### 4) PHP-версия (по коду)

- В коде используется оператор `??` (null coalescing), что требует PHP 7+ (минимум).【index.php†L14-L16】

---

## A1. Граф зависимостей (боёвка / квесты / НПС / магазины)

### Боёвка

**Ключевые скрипты и файлы:**
- `do/viewBattle.php` — список текущих PvP боёв (UI/просмотр).【do/viewBattle.php†L26-L71】
- `do/viewBattleView.php` — визуализация конкретного боя и лога раундов.【do/viewBattleView.php†L21-L146】
- `inc/world_boss_battle.php` — логика боёв с мировыми боссами (ActionBattle-подкласс).【inc/world_boss_battle.php†L1-L66】
- `do/WorldBoss.php` — роутер/контроллер для действий по мировым боссам.【do/WorldBoss.php†L1-L199】

**Таблицы:** `battle`, `battle_log`, `battle_effects`, `world_boss_*`.

### Квесты

**Ключевые скрипты и функции:**
- `do/goLocation.php` — содержит `quest_update`, `quest_step` и обновление прогресса в ходе локаций/событий.【do/goLocation.php†L38-L62】
- `inc/function/Functions.php` — журнал шагов квестов через `update_zap` (запись в `quest_steps`).【inc/function/Functions.php†L1301-L1303】

**Таблицы:** `user_quests`, `quest_steps`.

### НПС

**Ключевые скрипты:**
- `Npc/*.php` — сценарии диалогов/действий (например, покецентр).【Npc/3.php†L1-L156】
- `do/Npc/*.php` — AJAX-обработчики отдельных NPC/сценариев (например, боевой NPC Рику).【do/Npc/22.php†L62-L131】

**Таблицы:** `npcs`, `npc_battle`.

### Магазины/инвентарь

**Ключевые скрипты:**
- `do/itemsAction.php` — применение/выброс предметов и выдачи наград.【do/itemsAction.php†L1-L239】
- `do/shop_di.php` — донат-магазин (покупки, лимиты, лог).【do/shop_di.php†L1-L160】
- `do/giftshop.php` — магазин подарков (списания/получение).【do/giftshop.php†L1-L259】

**Таблицы:** `base_items`, `items_users`, `aquarits`, `shop_log`, `giftshop_items`, `gifts`.

# Milestone 0 — Анализ текущего проекта и план

## 1) Карта проекта (директории, модули, точки входа)

**Top-level:**
- `public/` — фронт-контроллер, ассеты, .htaccess для Apache.
- `app/` — доменная логика, HTTP-контроллеры, инфраструктура, поддержка.
- `sql/` — схема и миграции.
- `tests/` — минимальные PHP-скрипты.
- `bin/` — CLI-скрипты (админка).
- `docs/` — выгрузки дерева/контента.

**Внутри `app/`:**
- `Support/` — роутер, запрос/ответ, безопасность, env, шаблоны.
- `Infrastructure/` — подключение к БД (PDO).
- `Domain/` — доменные сервисы и репозитории (Auth/Battle/Game/Chat).
- `Http/` — контроллеры, связывающие домен и представление.
- `Views/` — PHP-шаблоны HTML.
- `bootstrap.php` — загрузка автолоадера, .env и обработчиков ошибок.

**Точки входа:**
- HTTP: `public/index.php` (front controller + маршрутизация).
- CLI: `bin/admin.php` (админ-скрипт; точка для сервисных задач).

## 2) Роутинг и HTTP-слой

**Маршруты:**
- Определяются в `public/index.php` через `App\Support\Router`.
- Поддерживаются GET/POST и path-параметры `{id}`.
- Разделены страницы и API (`/api/*`).

**SPA/PJAX (одна вкладка):**
- Клиент шлёт `X-Partial: 1`, сервер отдаёт JSON `{title, html, flash?}`.
- В “game shell” (страница `/locations`) все переходы открываются модалкой поверх мира.
- История управляется через `history.pushState`.

**Контроллеры:**
- `AuthController`, `ProfileController`, `CreatureController`, `LocationController`, `BattleController`, `ChatController`.
- Внутри используют доменные сервисы и репозитории.

## 3) Домен и логика

**Auth:**
- `AuthService`, `UserRepository`.
- Хеширование паролей через `password_hash`/`password_verify`.

**Game (существа/локации/предметы):**
- `CreatureRepository`, `LocationRepository`, `ItemRepository`.
- `StatCalculator` — расчёт финальных статов по IV/EV/nature/level.

**Battle:**
- `BattleEngine` — детерминированный пошаговый расчёт (order → damage → logs).
- `Rng` — детерминируемый RNG (seed + state).
- `BattleService` — транзакционная запись шагов боя + логов.
- `BattleRepository` — хранения состояния + событий (actions + logs).

**Chat:**
- `ChatService`, `ChatRepository` — получение/отправка сообщений, rate limit.

## 4) База данных

**Схема:** `sql/schema.sql` + миграции `sql/migrations/*`.

Ключевые таблицы:
- `users`, `locations`, `items`.
- `species`, `abilities`, `moves`, `species_abilities`.
- `user_creatures`, `creature_moves`, `user_items`.
- `battles`, `battle_actions`, `battle_logs`.
- `rate_limits`, `clans`, `clan_members`, `chat_messages`.

**Бои:**
- `battles.state` хранит актуальный snapshot.
- `battle_actions` хранит действия по ходам (event log).
- `battle_logs` хранит человекочитаемые сообщения.

## 5) Сессии, безопасность, валидация

**Сессии/куки:**
- `Security::configurePhp()` включает strict mode, httponly, samesite, опциональный secure.

**CSRF:**
- `Security::csrfToken()` + `Security::requireCsrf()`.

**SQL/XSS:**
- PDO + prepared statements; шаблоны используют экранирование через helpers.

**Rate limit:**
- Простая DB-backed реализация `rate_limits`.

## 6) Тесты

- `tests/smoke.php` — базовая проверка проекта.
- `tests/battle_engine_test.php` — проверки детерминизма/урона.

## 7) Риски/проблемные зоны

- **Race conditions в бою:** есть транзакции, но при высоком параллелизме нужна блокировка по battle_id (например, `SELECT ... FOR UPDATE` на `battles`).
- **Скалирование чата:** polling + DB-поиск потребуют индексов/кэша при росте.
- **Логи боёв:** `battle_logs` может сильно разрастаться → нужно архивирование/ротация.
- **Event-sourcing:** сейчас смешанный подход (state + actions). Нужно решить стратегию реплеев и снапшотов, если планируются длительные бои и истории.
- **Энкаунтеры/ловля:** отсутствуют таблицы и доменная логика (Milestone 3).

## 8) Архитектура целевого решения (расширяемая)

**Слои:**
- `Http` → `Domain` → `Infrastructure`.
- Модули: `Battle`, `World`, `Progression`, `Social`.

**Расширения:**
- Кланы/фракции → модуль `Social`.
- Квесты/ивенты → модуль `Progression`.
- PvE-энкаунтеры/ловля → модуль `World` + `Battle` (action `catch`).
- Рынок/крафт/сезоны → отдельные домены с изоляцией таблиц.

**Боевая архитектура:**
- Состояние (`battles.state`) + append-only `battle_actions`.
- Для реплеев: seed + actions (+ snapshots на N ходов).

## 9) План миграции по Milestones

**Milestone 1 (каркас + auth + SPA):**
- Уже реализовано (см. текущую кодовую базу).

**Milestone 2 (Battle MVP + карточка существа):**
- Уже реализовано (модель IV/EV/nature, basic battle engine).

**Milestone 3 (PvE-мир + награды + ловля):**
- Добавить `location_encounters`, `encounter_tables`, `drop_tables`, `catch_attempts`.
- Ввести транзакции наград и идемпотентность.

**Milestone 4 (PvP):**
- Очереди матчей, рейтинги (Elo/Glicko-lite), таймеры ходов.

**Milestone 5+ (SV-like активности):**
- Рейды/ивенты/сезоны, квест-борды, крафт/тренировки.

## 10) Рекомендации к следующему шагу

- Уточнить стратегию хранения боёв (event-sourcing + snapshots).
- Ввести таблицы для энкаунтеров и наград.
- Добавить ограничения целостности и индексы для hot paths (battle_id/user_id/status/created_at).

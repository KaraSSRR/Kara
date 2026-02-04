# Browser RPG v2 — Milestone 2

Milestone 2: **карточка существа (Showdown-like модель)** + **Battle Engine MVP 1v1** + хранилище состояния/лога/реплеев.
Проект остаётся **SPA/PJAX (одна вкладка)**.

Форматы JSON:
- **SPA/PJAX (X-Partial: 1)**: плоский payload `{title, html, flash?}` или `{redirect}`.
- **API (/api/*)**: envelope `{ok:true, data:{...}, request_id}` (ошибки: `{ok:false, error:{code,message,fields}, request_id}`).

Начиная с этого этапа, основной игровой экран — **/locations (мир)**: все остальные страницы в игре открываются **модалкой поверх мира** (Вариант A).

## Что добавлено в Milestone 2

### 1) Карточка существа `/creatures/{id}`
Отображается:
- базовые статы (из `species`) + финальные статы (расчёт Gen9-like),
- `IV/EV`, `nature`, `happiness`, `exp/level`,
- тип(ы), ability, held item (опционально),
- moveset до 4, PP.

### 2) Battle Engine MVP 1v1 `/battle`
**Вариант A**: состояние боя хранится на сервере (`battles.state`), RNG детерминируется `seed`, реплей собирается из `seed` + `battle_actions`.
MVP покрывает:
- priority, speed-ordering
- accuracy/evasion (стадии -6..+6)
- STAB
- type effectiveness (минимальный Gen9-like чартик)
- crit (6.25% базово)
- PP расход
- статусные ходы (MVP: `Growl` снижает ATK на 1 стадию)

### 3) Бой “на локации”
Бой привязан к **текущей локации игрока** (`users.current_location_id`):
- в таблице `battles` хранится `location_id`,
- в UI боя показывается локация.

Сейчас генератор противника выбирает species случайно из `species`.
Следующий шаг — фильтровать пул по локации (таблица связи `location_species` или веса/тайм-слоты), плюс “энкаунтер” запускается непосредственно со страницы локации.

### 4) Чат (всегда справа)
Добавлен чат-панель, которая **всегда видима в игре** (/locations), включая бой (бой открывается “почти фуллскрин”, но не перекрывает чат).
Каналы:
- **Мир** (global)
- **Торговля** (trade) — лимит: 1/30 сек и 30/сутки, TradeBot присылает DM-подсказку при превышении
- **ЛС** (dm) — список диалогов + бот-инбокс
- **Клан** (clan) — активируется если пользователь состоит в клане

---

## Требования
- PHP 8.1+
- MySQL 5.7+ (JSON поддерживается)
- Apache/Nginx (или `php -S`)

## Быстрый запуск (локально)

### Вариант 1: импорт `sql/schema.sql` (рекомендуется для чистой БД)
1) Создай БД:
```sql
CREATE DATABASE rpg_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2) Импортируй схему и сиды:
```bash
mysql -u root -p rpg_v2 < sql/schema.sql
mysql -u root -p rpg_v2 < sql/seed.sql
```

### Вариант 2: миграции по шагам (если контролируешь порядок)
```bash
mysql -u root -p rpg_v2 < sql/migrations/001_init.sql
mysql -u root -p rpg_v2 < sql/migrations/002_m2_showdown_battle.sql
mysql -u root -p rpg_v2 < sql/migrations/003_m2_battle_location.sql
mysql -u root -p rpg_v2 < sql/migrations/004_chat.sql
mysql -u root -p rpg_v2 < sql/migrations/005_species_evolutions.sql
mysql -u root -p rpg_v2 < sql/migrations/006_battle_snapshots.sql
mysql -u root -p rpg_v2 < sql/migrations/007_moves_status.sql
mysql -u root -p rpg_v2 < sql/migrations/008_location_encounters.sql
```

3) Скопируй `.env`:
```bash
cp .env.example .env
# отредактируй DB_* и APP_URL при необходимости
```

4) Запусти dev-сервер:
```bash
php -S localhost:8000 -t public
```

5) Открой:
- `/` — витрина (лендинг)
- `/register` — регистрация
- `/me` — профиль
- `/locations` — мир (основной экран игры)
- `/creatures` — список
- `/creatures/{id}` — карточка существа
- `/battle` — бой
- `/battle/{id}` — бой по ID
- `/locations/{id}` — локация с кнопкой “Исследовать”
- `/api/battle/{id}` — replay JSON (seed + actions + logs)

Примечание (Вариант A): если пользователь авторизован и открывает напрямую `/me`, `/creatures`, `/battle` и т.п., сервер редиректит на `/locations?open=/...` чтобы сохранить правило **"всё в одной вкладке"**.

---

## SPA-навигация (одна вкладка / без перезагрузки страницы)

- Внутренние ссылки (same-origin) перехватываются, контент подгружается через `fetch()`.
- Меняется только содержимое `<main id="app">`, URL обновляется через `history.pushState()`.
- Назад/вперёд работают (popstate).
- POST-формы отправляются через `fetch()` (кроме `multipart/form-data`).

Отключить SPA для конкретной ссылки/формы:
- добавь атрибут `data-no-spa="1"`.

Клиент шлёт заголовки `X-Partial: 1` и `Accept: application/json`.
Сервер отвечает **плоским JSON-пэйлоадом**:
- для GET страниц: `{ title, html, flash? }`
- для POST экшенов: `{ redirect:"/path" }`

---

## Тесты (минимальные)
```bash
php tests/smoke.php
php tests/battle_engine_test.php
```

---

## Деплой под Apache
- DocumentRoot должен смотреть в `public/`.
- `.htaccess` уже настроен на фронт-контроллер.

Если хостинг с корнем `public_html/`, деплой ассетов:
```bash
rsync -a --delete public/ public_html/
```

## Переменные окружения (.env)
- APP_ENV=local|prod
- APP_DEBUG=0|1
- APP_URL=http://... / https://...
- DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
- SESSION_SECURE=0|1 (1 если HTTPS)

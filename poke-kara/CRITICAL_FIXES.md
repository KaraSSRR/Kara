# CRITICAL_FIXES — Этап A (критические ошибки и риски)

Ниже список ключевых проблем (фаталы/варнинги, SQLi/XSS/CSRF, гонки и некорректные списания), с привязкой к файлам и строкам.

## 1) Фаталы/варнинги/compat

1. **Потенциальный фатал:** `inc/world_boss_battle.php` требует `action_battle.php`, которого нет в дереве репозитория — приведёт к `require_once` fatal при вызове боёв мировых боссов.【inc/world_boss_battle.php†L7】
2. **Совместимость:** короткие PHP-теги `<?` без `<?php` зависят от `short_open_tag` и могут ломать окружения (пример — `inc/conf/connect.php`).【inc/conf/connect.php†L1】
3. **Потеря наблюдаемости:** `error_reporting(0)` скрывает варнинги/фаталы и мешает поиску ошибок в проде/стейдже.【inc/conf/connect.php†L63】
4. **Undefined index:** `do/opros.php` использует `$_POST['op']` без проверки `isset`, что вызывает notice/Warning при некорректных запросах.【do/opros.php†L13-L17】
5. **Undefined index:** `do/itemsAction.php` читает `$_POST['type']`, `$_POST['count']`, `$_POST['itemID']`, `$_POST['pokID']` без `isset`, что вызывает notice/Warning и ломает ожидания по данным.【do/itemsAction.php†L13-L16】
6. **Undefined index:** `do/shoplavk.php` читает `$_POST['category']`, `$_POST['sort']`, `$_POST['typ']`, `$_POST['dop']` без проверок `isset` (notice/Warning).【do/shoplavk.php†L13-L18】
7. **Undefined variable:** `do/shoplavk.php` использует `$tpl` до инициализации, что создаёт notice/Warning и нестабильный JSON-ответ.【do/shoplavk.php†L26-L35】
8. **Undefined index:** `do/calendarAction.php` использует `$_POST['BossPrize']` без `isset`, что приводит к notice/Warning при пустых запросах.【do/calendarAction.php†L11-L14】

## 2) SQL-инъекции / небезопасные запросы

9. **Небезопасные «sanitize» функции:** `clearStr/clearInt` не экранируют для SQL и не валидируют типы — при использовании в конкатенации возможно SQLi (и логические ошибки из-за `ceil/abs`).【inc/conf/connect.php†L3-L17】
10. **raw SQL с конкатенацией:** обновление `users.online` по `$_SESSION['id']` без prepared statements.【inc/conf/connect.php†L49-L55】
11. **raw SQL с конкатенацией:** `do/viewBattleView.php` читает `battle_log`/`battle` по `$id` без prepared statements (пусть и кастится в int).【do/viewBattleView.php†L21-L23】
12. **raw SQL с конкатенацией пользовательских данных:** `do/opros.php` вставляет `$_POST['text']` в SQL через строковую конкатенацию.【do/opros.php†L13-L15】
13. **SQLi через динамические условия/ORDER BY:** `do/shoplavk.php` вставляет `$dop`, `$typ` и сортировку без whitelist и prepared statements.【do/shoplavk.php†L13-L44】
14. **raw SQL и конкатенация без проверок:** `do/itemsAction.php` — множественные запросы с конкатенацией входных данных (пример: base_items/users/base_npc).【do/itemsAction.php†L13-L19】
15. **raw SQL в квестах:** `do/goLocation.php` обновляет/вставляет `user_quests` через конкатенацию и без prepared statements.【do/goLocation.php†L38-L45】
16. **raw SQL в квест-проверках:** `do/goLocation.php` читает `user_quests` через конкатенацию на каждом обращении.【do/goLocation.php†L54-L60】
17. **raw SQL с пользовательским текстом:** `update_zap` пишет `quest_steps.text` через конкатенацию без prepared statements/escape (потенциальная SQLi + XSS при выводе).【inc/function/Functions.php†L1301-L1303】
18. **SQLi в emoji-паках:** `get_emojis.php` использует `$pack` в SELECT/INSERT без prepared statements/полного whitelist.【get_emojis.php†L58-L98】
19. **SQLi в новостях:** `index.php` строит SQL `LIMIT $offset,$perPage` строкой (значения приводятся к int, но всё равно нет prepared).【index.php†L169-L178】
20. **SQLi в донат-магазине:** `do/shop_di.php` строит SQL с `$item`/`$user_id`/`$count` напрямую и без prepared statements (несколько SELECT/INSERT/UPDATE).【do/shop_di.php†L17-L154】

## 3) XSS/HTML-инъекции

21. **XSS из боевого лога:** `do/viewBattleView.php` вставляет HTML из `battle_log` (`$a/$b/$c`) без `htmlspecialchars`, что открывает stored XSS при наличии вредного лога.【do/viewBattleView.php†L87-L105】
22. **XSS в дневнике квестов:** `update_zap` вставляет произвольный `text` в `quest_steps`, что при выводе без escape превращается в stored XSS.【inc/function/Functions.php†L1301-L1303】
23. **HTML-инъекции в подарках:** `do/giftshop.php` хранит `message` и возвращает данные без явного escape — риск XSS в местах, где сообщения выводятся напрямую.【do/giftshop.php†L69-L77】

## 4) CSRF и небезопасные state-changing запросы

24. **Нет CSRF-защиты для операций магазина подарков:** `do/giftshop.php` принимает POST и изменяет состояние (списания/подарки), но токенов/nonce нет.【do/giftshop.php†L16-L80】
25. **Нет CSRF-защиты для опроса:** `do/opros.php` изменяет состояние пользователя по POST без токена.【do/opros.php†L13-L17】
26. **Нет CSRF-защиты для операций с предметами:** `do/itemsAction.php` изменяет инвентарь/локации по POST без токена.【do/itemsAction.php†L13-L239】
27. **Нет CSRF-защиты для NPC-боёв/наград:** `do/Npc/22.php` запускает бой и выдаёт награды по POST-сценарию без токена.【do/Npc/22.php†L62-L117】
28. **Нет CSRF-защиты для донат-магазина:** `do/shop_di.php` позволяет покупку через POST без токена/nonce.【do/shop_di.php†L57-L160】
29. **Нет CSRF-защиты для календарных ивентов:** `do/calendarAction.php` изменяет состояние и выдаёт награды по POST без токена/nonce.【do/calendarAction.php†L11-L120】

## 5) Double-spend / race conditions / транзакционность

30. **Покупка подарков без транзакции:** сначала `minus_item`, потом `INSERT gifts` — при сбое/повторе запроса возможен double-spend или потеря валюты без подарка.【do/giftshop.php†L143-L149】
31. **Получение подарка без блокировок:** `UPDATE gifts SET status='received'` без `FOR UPDATE` или транзакции — гонки при двойном клике/повторе запроса.【do/giftshop.php†L230-L245】
32. **Покупка emoji-пака без транзакции:** списание валюты и `INSERT` в разные запросы без `BEGIN/COMMIT` и блокировок.【get_emojis.php†L71-L98】
33. **Операции с предметами без транзакций:** в `do/itemsAction.php` (card/drop) списания и награды выполняются раздельными запросами, что даёт гонки и двойные награды при повторе запроса.【do/itemsAction.php†L118-L174】
34. **Телепорт за билет без транзакции:** списание билета и обновление `users.location` отдельно; возможна потеря билета при сбое или гонка запросов.【do/itemsAction.php†L225-L235】
35. **Квестовые апдейты не идемпотентны:** `quest_update` может одновременно выполнять UPDATE/INSERT без блокировки строки пользователя (двойные клики/гонки).【do/goLocation.php†L38-L45】
36. **NPC-награды без транзакции:** в `do/Npc/22.php` `itemAdd`+`quest_update` без блокировок и флага «reward claimed» — возможны повторные награды при повторе запроса.【do/Npc/22.php†L78-L117】
37. **Лечение у Джой без транзакции:** множественные UPDATE по `user_pokemons` и списание монет отдельно — при повторе/сбое возможно неконсистентное состояние (HP/PP/монеты).【Npc/3.php†L55-L104】
38. **Донат-магазин без транзакций:** `itemAdd`, `minus_item`, `UPDATE aquarits`, `INSERT shop_log` выполняются отдельно — возможно рассинхронизированное состояние при сбоях/повторах запроса.【do/shop_di.php†L128-L154】

## 6) Логика/доступ/целостность

39. **Несанкционированная выдача контента:** `get_emojis.php` отдаёт список смайлов для любого пакета независимо от покупки (логический bypass).【get_emojis.php†L147-L164】
40. **Path traversal риск:** `getEmojis.php` использует `$_GET['pack']` в filesystem path без whitelisting — возможность чтения файлов из произвольных директорий в пределах проекта/FS.【getEmojis.php†L2-L6】
41. **Логи с чувствительными данными:** в `do/WorldBossTest.php` включён `display_errors` и подробные ответы — небезопасно для прод-окружения (утечки).【do/WorldBossTest.php†L1-L39】
42. **RB cleanup без транзакций/блокировок:** в `rb_cleanup_team_on_location_change` меняется статус пользователя и удаляются покемоны без атомарности (гонки при параллельном апдейте локации).【do/goLocation.php†L16-L23】
43. **Непоследовательное хранение user_id в сессии:** `get_emojis.php` использует `$_SESSION['user_id']` или `$_SESSION['id']`, что может ломать доступ/учёт (смешение идентификаторов).【get_emojis.php†L12-L14】
44. **CORS без ограничений на регистрацию:** `do/registration.php` разрешает `Access-Control-Allow-Origin: *`, что упрощает злоупотребление регистрацией через сторонние источники (при отсутствии rate-limit).【do/registration.php†L5-L8】

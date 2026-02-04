<?php
/**
 * JoyStoryHook
 *
 * Centralized storyline integration for Sister Joy NPCs across locations/IDs.
 * Goals:
 *  - Ensure quest 1102 is issued once the player has a starter (quest 1 step>=5) OR has finished quest 1.
 *  - Provide a storyline greeting when 1102 is active.
 *  - Mark 1102 complete after a successful heal action and issue quest 1103.
 *
 * This file is auto-loaded by Npc/index.php (Npc/lib/*.php).
 */
class JoyStoryHook
{
    private static function uid(): int
    {
        return (int)($_SESSION['id'] ?? 0);
    }

    private static function cntUserPokes($db, int $userId): int
    {
        $res = @$db->query("SELECT COUNT(*) AS c FROM `user_pokemons` WHERE `user_id`=".(int)$userId);
        $row = $res ? $res->fetch_assoc() : null;
        return (int)($row['c'] ?? 0);
    }

    private static function ensure1102(): void
    {
        if (!class_exists('QuestKit')) return;
        $uid = self::uid();
        if ($uid <= 0) return;

        if (QuestKit::exists(1102)) return;

        // Start conditions: player has a starter (quest 1 progressed) or finished quest 1, or already in 1101 flow.
        $hasStarter = QuestKit::exists(1) && (int)QuestKit::step(1) >= 5;
        $startDone  = QuestKit::exists(1) && (int)QuestKit::end(1) == 1;
        $has1101    = QuestKit::exists(1101);

        if ($hasStarter || $startDone || $has1101) {
            QuestKit::set(1102, 1, 0);
            if (function_exists('update_zap')) {
                update_zap(1102, 1, 'Зайди в Покецентр к сестре Джой и вылечи команду (меню «Лечение»), чтобы завершить обучение.');
            }
        }
    }

    /**
     * Returns a storyline greeting text if 1102 is active; otherwise null.
     * Also ensures 1102 exists when it should.
     */
    public static function defaultQuestion($mysqliOrDb)
    {
        if (!class_exists('QuestKit')) return null;
        $uid = self::uid();
        if ($uid <= 0) return null;

        // Keep 1101 check lightweight (optional): if baseline is set and player caught something, close 1101.
        if (QuestKit::exists(1101) && (int)QuestKit::end(1101) == 0) {
            $baseline = (int)QuestKit::dataGet(1101, 'baseline_pokes', 0);
            if ($baseline <= 0) {
                $baseline = self::cntUserPokes($mysqliOrDb, $uid);
                QuestKit::set(1101, 1, 0, null, ['baseline_pokes' => $baseline]);
            }
            $current = self::cntUserPokes($mysqliOrDb, $uid);
            if ($current > $baseline) {
                QuestKit::set(1101, 2, 1);
                if (function_exists('update_zap')) {
                    update_zap(1101, 2, 'Я поймал своего первого дикого покемона. Пора зайти в Покецентр и вылечить команду.');
                }
                self::ensure1102();
            }
        }

        // Ensure 1102 is present once it should be.
        self::ensure1102();

        if (QuestKit::exists(1102) && (int)QuestKit::end(1102) == 0 && (int)QuestKit::step(1102) == 1) {
            return 'Здравствуй, тренер! Профессор попросил научить тебя базовой заботе о команде. Выбери «Лечение» и вылечи покемонов — так ты завершишь задание.';
        }

        return null;
    }

    /**
     * Call this after a successful heal action (when pokemons were healed and payment succeeded).
     */
    public static function afterHeal(): void
    {
        if (!class_exists('QuestKit')) return;
        $uid = self::uid();
        if ($uid <= 0) return;

        self::ensure1102();

        if (!QuestKit::exists(1102)) return;

        // If already completed, do nothing.
        if ((int)QuestKit::end(1102) == 1) return;

        QuestKit::set(1102, 2, 1);
        if (function_exists('update_zap')) {
            update_zap(1102, 2, 'Я вылечил команду в Покецентре. Теперь можно помогать офицеру Дженни.');
        }

        if (!QuestKit::exists(1103)) {
            QuestKit::set(1103, 1, 0);
            if (function_exists('update_zap')) {
                update_zap(1103, 1, 'Новое задание: Офицер Дженни просит помощи. Найди её и узнай детали.');
            }
        }

        // Also surface a UI message for legacy Joy scripts that don't build storyline text themselves.
        global $response;
        if (is_array($response)) {
            $response['actionQuest'] = 'Задание <b>Покецентр: первая помощь</b> выполнено. Доступно новое: <b>Офицер Дженни: подозрительные воры</b>.';
        }
    }
}
?>
<?php
// Battle Pass universal progress handler
// Updates user mission progress for generic events (coins earned, kills, etc.)
// Apply this to ANY place where player earns progress outside NPC submission.
//
// Usage examples:
//  - bp_progress_coins($mysqli, $userId, 5000);
//  - bp_progress_kill($mysqli, $userId, 1); // +1 kill (optionally pass $zoneId as target_id)
//  - bp_progress_event($mysqli, $userId, 'custom_type', 3, $customTargetId);
//
// Notes:
//  - Progress applies to ALL matching active missions (not consumed), i.e. each mission receives +delta capped by count_max.
//  - On completion, EXP is awarded via bpAddExp (auto level-up) and a history record is created.

if (!function_exists('bpGetLevelCost')) {
    function bpGetLevelCost(int $lvl): int {
        $base = defined('BP_EXP_PER_LEVEL') ? (int)BP_EXP_PER_LEVEL : 800;
        return max(1, $base);
    }
}
if (!function_exists('bpAddExp')) {
    function bpAddExp(mysqli $mysqli, int $user_id, int $season_id, int $addExp): array {
        $out = ['lvl'=>null,'exp_me'=>null,'exp_to'=>null,'gained_levels'=>0,'added'=>$addExp];
        if ($addExp <= 0) return $out;
        try {
            $mysqli->begin_transaction();
            $row = $mysqli->query("
                SELECT lvl, exp_me, exp_to
                FROM aa_battle_pass_user
                WHERE user = {$user_id} AND season_id = {$season_id}
                FOR UPDATE
            ")->fetch_assoc();
            if (!$row) { $mysqli->rollback(); return $out; }
            $lvl = (int)$row['lvl'];
            $me  = (int)$row['exp_me'] + (int)$addExp;
            $to  = (int)$row['exp_to'];
            if ($to <= 0) $to = bpGetLevelCost($lvl);
            $gained = 0;
            while ($me >= $to) { $me -= $to; $lvl++; $to = bpGetLevelCost($lvl); $gained++; }
            $mysqli->query("UPDATE aa_battle_pass_user SET lvl={$lvl}, exp_me={$me}, exp_to={$to} WHERE user={$user_id} AND season_id={$season_id} LIMIT 1");
            $mysqli->commit();
            $out['lvl']=$lvl; $out['exp_me']=$me; $out['exp_to']=$to; $out['gained_levels']=$gained; return $out;
        } catch (Throwable $e) { if ($mysqli->errno) $mysqli->rollback(); return $out; }
    }
}
if (!function_exists('getActiveSeason')) {
    function getActiveSeason(mysqli $mysqli) {
        return $mysqli->query("SELECT * FROM `aa_battle_pass_season` WHERE `is_active` = 1 LIMIT 1")->fetch_assoc();
    }
}

/**
 * Universal mission progress event.
 * - target_type: e.g. 'coins', 'kills', or any custom type you use in aa_battle_pass_mission.target_type
 * - target_id:   0 for generic, or a specific id (e.g., zone id). Matches missions where target_id=0 OR =target_id
 * - delta:       how much to add to progress (applied to each matching mission, capped by count_max)
 * Returns summary with updated missions and completed count.
 */
function bp_progress_event(mysqli $db, int $user_id, string $target_type, int $delta, int $target_id = 0, ?int $season_id = null): array {
    $summary = [
        'season_id'       => $season_id,
        'user_id'         => $user_id,
        'target_type'     => $target_type,
        'target_id'       => $target_id,
        'delta'           => $delta,
        'updated'         => 0,
        'completed_count' => 0,
        'completed'       => [], // list of um.id that completed
    ];
    if ($user_id <= 0 || $delta <= 0) return $summary;

    if (!$season_id || $season_id <= 0) {
        $season = getActiveSeason($db);
        if (!$season) return $summary;
        $season_id = (int)$season['id'];
        $summary['season_id'] = $season_id;
    }

    $tt   = $db->real_escape_string($target_type);
    $tid  = (int)$target_id;

    try {
        $db->begin_transaction();

        // Fetch matching active missions (not expired, not done)
        $sql = "
            SELECT um.id AS um_id, um.progress, um.done,
                   m.count_max, m.exp, m.text, m.season_id AS m_season_id
            FROM aa_battle_pass_user_mission um
            JOIN aa_battle_pass_mission m ON m.id = um.mission_id
            WHERE um.user_id = {$user_id}
              AND um.season_id = {$season_id}
              AND um.expired = 0
              AND um.done = 0
              AND m.target_type = '{$tt}'
              AND (m.target_id = 0 OR m.target_id = {$tid})
            FOR UPDATE
        ";
        $rs = $db->query($sql);
        if ($rs && $rs->num_rows) {
            while ($r = $rs->fetch_assoc()) {
                $um_id     = (int)$r['um_id'];
                $progress  = (int)$r['progress'];
                $count_max = (int)$r['count_max'];
                $new       = $progress + $delta;
                if ($new > $count_max) $new = $count_max;

                if ($new > $progress) {
                    // Update progress
                    $db->query("UPDATE aa_battle_pass_user_mission SET progress = {$new} WHERE id = {$um_id} AND user_id = {$user_id} LIMIT 1");
                    $summary['updated']++;

                    // If completed now — set done=1 and remember for awarding outside of transaction
                    if ($new >= $count_max && (int)$r['done'] === 0) {
                        $db->query("UPDATE aa_battle_pass_user_mission SET done = 1 WHERE id = {$um_id} AND user_id = {$user_id} AND done = 0 LIMIT 1");
                        $summary['completed'][] = [
                            'um_id'      => $um_id,
                            'exp'        => (int)$r['exp'],
                            'season_id'  => (int)$r['m_season_id'],
                            'text'       => (string)$r['text'],
                        ];
                        $summary['completed_count']++;
                    }
                }
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->errno) $db->rollback();
        return $summary;
    }

    // Award EXP and write history OUTSIDE the transaction to avoid nested tx with bpAddExp
    if (!empty($summary['completed'])) {
        foreach ($summary['completed'] as $c) {
            $cid = (int)$c['um_id'];
            $cex = (int)$c['exp'];
            $cs  = (int)$c['season_id'] ?: $season_id; // fallback to active
            if ($cex > 0) {
                bpAddExp($db, $user_id, $cs, $cex);
            }
            // History
            $db->query("
                INSERT INTO aa_battle_pass_history (user_id, season_id, event, event_id, info)
                VALUES ({$user_id}, {$season_id}, 'mission_complete', {$cid}, '".$db->real_escape_string('Завершено: '.$c['text'])."')
            ");
        }
    }

    return $summary;
}

/**
 * Convenience wrappers.
 */
function bp_progress_coins(mysqli $db, int $user_id, int $amount, ?int $season_id = null): array {
    return bp_progress_event($db, $user_id, 'coins', max(0, (int)$amount), 0, $season_id);
}
function bp_progress_kill(mysqli $db, int $user_id, int $delta = 1, int $zone_id = 0, ?int $season_id = null): array {
    return bp_progress_event($db, $user_id, 'kills', max(0, (int)$delta), (int)$zone_id, $season_id);
}
<?php
declare(strict_types=1);

namespace App\Domain\Game;

use App\Infrastructure\Database;

final class CreatureRepository {
    public function listForUser(int $userId): array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            SELECT uc.id, uc.nickname, uc.level, uc.exp, uc.current_hp, uc.status,
                   uc.iv, uc.ev,
                   uc.nature, uc.happiness,
                   a.name AS ability_name,
                   s.id AS species_id, s.name AS species_name, s.type1, s.type2,
                   s.base_hp, s.base_atk, s.base_def, s.base_spa, s.base_spd, s.base_spe
            FROM user_creatures uc
            JOIN species s ON s.id = uc.species_id
            LEFT JOIN abilities a ON a.id = uc.ability_id
            WHERE uc.user_id = ?
            ORDER BY uc.id ASC
        ');
        $st->execute([$userId]);
        $rows = $st->fetchAll();

        // Enrich list with calculated stats (max_hp, final_stats) for UI.
        foreach ($rows as &$row) {
            $iv = self::decodeStatsJson($row['iv'] ?? null);
            $ev = self::decodeStatsJson($row['ev'] ?? null);

            $base = [
                'hp'  => (int)$row['base_hp'],
                'atk' => (int)$row['base_atk'],
                'def' => (int)$row['base_def'],
                'spa' => (int)$row['base_spa'],
                'spd' => (int)$row['base_spd'],
                'spe' => (int)$row['base_spe'],
            ];

            $level = (int)$row['level'];
            $nature = StatCalculator::normalizeNature((string)($row['nature'] ?? 'hardy'));

            $final = StatCalculator::calcAll($base, $iv, $ev, $level, $nature);

            $row['iv_arr'] = $iv;
            $row['ev_arr'] = $ev;
            $row['final_stats'] = $final;
            $row['max_hp'] = $final['hp'];
            $row['current_hp'] = min((int)$row['current_hp'], (int)$row['max_hp']);
        }
        unset($row);

        return $rows;
    }

    public function findForUserFull(int $userId, int $creatureId): ?array {
        $pdo = Database::pdo();

        $st = $pdo->prepare('
            SELECT uc.*,
                   s.name AS species_name, s.type1, s.type2,
                   s.base_hp, s.base_atk, s.base_def, s.base_spa, s.base_spd, s.base_spe,
                   a.name AS ability_name, a.description AS ability_description,
                   i.name AS held_item_name, i.description AS held_item_description
            FROM user_creatures uc
            JOIN species s ON s.id = uc.species_id
            LEFT JOIN abilities a ON a.id = uc.ability_id
            LEFT JOIN items i ON i.id = uc.held_item_id
            WHERE uc.user_id = ? AND uc.id = ?
            LIMIT 1
        ');
        $st->execute([$userId, $creatureId]);
        $row = $st->fetch();
        if (!$row) return null;

        $iv = self::decodeStatsJson($row['iv'] ?? null);
        $ev = self::decodeStatsJson($row['ev'] ?? null);

        $base = [
            'hp'  => (int)$row['base_hp'],
            'atk' => (int)$row['base_atk'],
            'def' => (int)$row['base_def'],
            'spa' => (int)$row['base_spa'],
            'spd' => (int)$row['base_spd'],
            'spe' => (int)$row['base_spe'],
        ];

        $level = (int)$row['level'];
        $nature = StatCalculator::normalizeNature((string)($row['nature'] ?? 'hardy'));

        $final = StatCalculator::calcAll($base, $iv, $ev, $level, $nature);

        $row['iv_arr'] = $iv;
        $row['ev_arr'] = $ev;
        $row['base_stats'] = $base;
        $row['final_stats'] = $final;

        $row['max_hp'] = $final['hp'];
        $row['current_hp'] = min((int)$row['current_hp'], $row['max_hp']);

        $row['status_arr'] = self::decodeJson($row['status'] ?? null);

        $row['moves'] = $this->movesForCreature($creatureId);

        return $row;
    }

    /** @return array<int, array> */
    public function movesForCreature(int $creatureId): array {
        $pdo = Database::pdo();
        $st = $pdo->prepare('
            SELECT cm.slot, cm.pp_current, cm.pp_max,
                   m.id AS move_id, m.name, m.type, m.category, m.power, m.accuracy, m.pp, m.priority
            FROM creature_moves cm
            JOIN moves m ON m.id = cm.move_id
            WHERE cm.user_creature_id = ?
            ORDER BY cm.slot ASC
        ');
        $st->execute([$creatureId]);
        return $st->fetchAll();
    }

    public function createStarter(int $userId, int $speciesId, string $nickname = ''): int {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Default IV/EV for demo.
            $iv = ['hp'=>12,'atk'=>12,'def'=>12,'spa'=>12,'spd'=>12,'spe'=>12];
            $ev = ['hp'=>0,'atk'=>0,'def'=>0,'spa'=>0,'spd'=>0,'spe'=>0];

            // Resolve default ability for the species (slot 0).
            $abilityId = null;
            try {
                $stA = $pdo->prepare('SELECT ability_id FROM species_abilities WHERE species_id = ? AND slot = 0 LIMIT 1');
                $stA->execute([$speciesId]);
                $abilityId = $stA->fetchColumn();
                if ($abilityId !== false) $abilityId = (int)$abilityId; else $abilityId = null;
            } catch (\Throwable $e) {
                // species_abilities may not exist if migrations weren't applied yet.
                $abilityId = null;
            }

            $nature = 'hardy';
            $happiness = 70;

            // Calculate stats from species base.
            $stS = $pdo->prepare('SELECT base_hp, base_atk, base_def, base_spa, base_spd, base_spe FROM species WHERE id = ?');
            $stS->execute([$speciesId]);
            $s = $stS->fetch();
            if (!$s) throw new \RuntimeException('Species not found');

            $base = [
                'hp'  => (int)$s['base_hp'],
                'atk' => (int)$s['base_atk'],
                'def' => (int)$s['base_def'],
                'spa' => (int)$s['base_spa'],
                'spd' => (int)$s['base_spd'],
                'spe' => (int)$s['base_spe'],
            ];

            $level = 5;
            $final = StatCalculator::calcAll($base, $iv, $ev, $level, $nature);

            // Insert creature (Milestone 2 schema includes nature/happiness/ability_id/held_item_id/is_shiny).
            $st = $pdo->prepare('
                INSERT INTO user_creatures
                    (user_id, species_id, nickname, level, exp, iv, ev, stats, nature, happiness, ability_id, held_item_id, is_shiny, current_hp, status, created_at)
                VALUES
                    (?,?,?,?,?,
                     JSON_OBJECT("hp", ?, "atk", ?, "def", ?, "spa", ?, "spd", ?, "spe", ?),
                     JSON_OBJECT("hp", ?, "atk", ?, "def", ?, "spa", ?, "spd", ?, "spe", ?),
                     JSON_OBJECT("hp", ?, "atk", ?, "def", ?, "spa", ?, "spd", ?, "spe", ?),
                     ?, ?, ?, NULL, 0, ?, JSON_OBJECT(), NOW())
            ');
            $st->execute([
                $userId, $speciesId, $nickname, $level, 0,
                $iv['hp'], $iv['atk'], $iv['def'], $iv['spa'], $iv['spd'], $iv['spe'],
                $ev['hp'], $ev['atk'], $ev['def'], $ev['spa'], $ev['spd'], $ev['spe'],
                $final['hp'], $final['atk'], $final['def'], $final['spa'], $final['spd'], $final['spe'],
                $nature, $happiness, $abilityId,
                $final['hp'],
            ]);
            $id = (int)$pdo->lastInsertId();

            // Default moveset (same as migration 002 backfill logic).
            $this->ensureDefaultMoveset($id, $speciesId);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function ensureDefaultMoveset(int $creatureId, int $speciesId): void {
        $pdo = Database::pdo();

        // If creature_moves table doesn't exist yet, just skip.
        try {
            // Slot 1: Tackle (1)
            $pdo->prepare('
                INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
                VALUES (?,?,?,?,?)
            ')->execute([$creatureId, 1, 1, 35, 35]);

            // Slot 2: signature
            $sig = 5;
            if ($speciesId === 1) $sig = 2;
            if ($speciesId === 2) $sig = 3;
            if ($speciesId === 3) $sig = 4;
            $pp = ($sig === 2 || $sig === 3 || $sig === 4) ? 25 : 30;

            $pdo->prepare('
                INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
                VALUES (?,?,?,?,?)
            ')->execute([$creatureId, 2, $sig, $pp, $pp]);

            // Slot 3: Quick Attack (5)
            $pdo->prepare('
                INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
                VALUES (?,?,?,?,?)
            ')->execute([$creatureId, 3, 5, 30, 30]);

            // Slot 4: Growl (6)
            $pdo->prepare('
                INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
                VALUES (?,?,?,?,?)
            ')->execute([$creatureId, 4, 6, 40, 40]);
        } catch (\Throwable $e) {
            // ignore (migration not applied)
        }
    }

    /** @return array{hp:int,atk:int,def:int,spa:int,spd:int,spe:int} */
    private static function decodeStatsJson($json): array {
        $d = self::decodeJson($json);
        return [
            'hp'  => (int)($d['hp']  ?? 0),
            'atk' => (int)($d['atk'] ?? 0),
            'def' => (int)($d['def'] ?? 0),
            'spa' => (int)($d['spa'] ?? 0),
            'spd' => (int)($d['spd'] ?? 0),
            'spe' => (int)($d['spe'] ?? 0),
        ];
    }

    /** @return array<string,mixed> */
    private static function decodeJson($json): array {
        if (is_array($json)) return $json;
        if ($json === null) return [];
        $s = (string)$json;
        if ($s === '') return [];
        $d = json_decode($s, true);
        return is_array($d) ? $d : [];
    }
}

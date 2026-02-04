<?php
declare(strict_types=1);

namespace App\Domain\Battle;

use App\Infrastructure\Database;
use App\Domain\Game\CreatureRepository;
use App\Domain\Game\StatCalculator;

final class BattleService {
    public function __construct(
        private BattleRepository $battles = new BattleRepository(),
        private CreatureRepository $creatures = new CreatureRepository(),
    ) {}

    public function start1v1(int $userId, int $playerCreatureId): int {
        // If user already has an active battle, return it.
        $active = $this->battles->findActiveForUser($userId);
        if ($active) return (int)$active['id'];

        $c = $this->creatures->findForUserFull($userId, $playerCreatureId);
        if (!$c) throw new \RuntimeException('Creature not found');
        if ((int)$c['current_hp'] <= 0) throw new \RuntimeException('Creature is fainted');

        // Battle happens at player's current location (can be NULL).
        $pdo = Database::pdo();
        $stLoc = $pdo->prepare('SELECT current_location_id FROM users WHERE id = ?');
        $stLoc->execute([$userId]);
        $locationId = $stLoc->fetchColumn();
        $locationId = ($locationId === false || $locationId === null) ? null : (int)$locationId;

        $seed = random_int(1, 2147483647);
        $rng = new Rng($seed);

        $opponent = $this->generateOpponent($rng, (int)$c['level']);

        $state = [
            'location_id' => $locationId,
            'turn' => 0,
            'finished' => false,
            'winner' => null,
            'rng_state' => $rng->getState(),
            'p1' => $this->toBattleSide('p1', $c),
            'p2' => $this->toBattleSide('p2', $opponent),
        ];

        $battleId = $this->battles->create($userId, $locationId, $playerCreatureId, $opponent, $seed, $state);
        $this->battles->addSnapshot($battleId, 0, $state);
        return $battleId;
    }

    public function startEncounter(int $userId, int $locationId, int $playerCreatureId): int {
        $active = $this->battles->findActiveForUser($userId);
        if ($active) return (int)$active['id'];

        $c = $this->creatures->findForUserFull($userId, $playerCreatureId);
        if (!$c) throw new \RuntimeException('Creature not found');
        if ((int)$c['current_hp'] <= 0) throw new \RuntimeException('Creature is fainted');

        $seed = random_int(1, 2147483647);
        $rng = new Rng($seed);
        $opponent = $this->generateOpponentForLocation($rng, $locationId, (int)$c['level']);

        $state = [
            'location_id' => $locationId,
            'turn' => 0,
            'finished' => false,
            'winner' => null,
            'rng_state' => $rng->getState(),
            'p1' => $this->toBattleSide('p1', $c),
            'p2' => $this->toBattleSide('p2', $opponent),
        ];

        $battleId = $this->battles->create($userId, $locationId, $playerCreatureId, $opponent, $seed, $state);
        $this->battles->addSnapshot($battleId, 0, $state);
        return $battleId;
    }

    /** @return array<string,mixed> opponent snapshot */
    private function generateOpponent(Rng $rng, int $level): array {
        $pdo = Database::pdo();

        // Pick random species from DB.
        $species = $pdo->query('SELECT id, name, type1, type2, base_hp, base_atk, base_def, base_spa, base_spd, base_spe FROM species ORDER BY id ASC')->fetchAll();
        if (!$species) throw new \RuntimeException('No species in DB');

        $pick = $species[$rng->nextInt(count($species))];
        $speciesId = (int)$pick['id'];

        // Default ability (slot 0)
        $abilityId = null;
        $abilityName = null;
        $abilityDesc = null;
        try {
            $stA = $pdo->prepare('
                SELECT a.id, a.name, a.description
                FROM species_abilities sa JOIN abilities a ON a.id = sa.ability_id
                WHERE sa.species_id = ? AND sa.slot = 0
                LIMIT 1
            ');
            $stA->execute([$speciesId]);
            $a = $stA->fetch();
            if ($a) { $abilityId = (int)$a['id']; $abilityName = (string)$a['name']; $abilityDesc = (string)$a['description']; }
        } catch (\Throwable $e) {}

        $nature = 'hardy';
        $happiness = 70;

        // IV/EV for NPC are fixed for now; later add randomization.
        $iv = ['hp'=>10,'atk'=>10,'def'=>10,'spa'=>10,'spd'=>10,'spe'=>10];
        $ev = ['hp'=>0,'atk'=>0,'def'=>0,'spa'=>0,'spd'=>0,'spe'=>0];

        $base = [
            'hp'  => (int)$pick['base_hp'],
            'atk' => (int)$pick['base_atk'],
            'def' => (int)$pick['base_def'],
            'spa' => (int)$pick['base_spa'],
            'spd' => (int)$pick['base_spd'],
            'spe' => (int)$pick['base_spe'],
        ];
        $stats = StatCalculator::calcAll($base, $iv, $ev, $level, $nature);

        // Default moves (same mapping as CreatureRepository).
        $moves = $this->defaultMovesForSpecies($speciesId);

        return [
            'id' => null,
            'species_id' => $speciesId,
            'species_name' => (string)$pick['name'],
            'nickname' => '',
            'level' => $level,
            'types' => [ (string)$pick['type1'], $pick['type2'] ? (string)$pick['type2'] : null ],
            'nature' => $nature,
            'happiness' => $happiness,
            'ability_id' => $abilityId,
            'ability_name' => $abilityName,
            'ability_description' => $abilityDesc,
            'held_item_id' => null,
            'held_item_name' => null,
            'iv_arr' => $iv,
            'ev_arr' => $ev,
            'base_stats' => $base,
            'final_stats' => $stats,
            'max_hp' => $stats['hp'],
            'current_hp' => $stats['hp'],
            'status_arr' => [],
            'moves' => $moves,
        ];
    }

    /** @return array<string,mixed> opponent snapshot */
    private function generateOpponentForLocation(Rng $rng, int $locationId, int $level): array {
        $pdo = Database::pdo();

        $timeSlot = $this->currentTimeSlot();
        $st = $pdo->prepare('
            SELECT le.species_id, le.weight, le.min_level, le.max_level,
                   s.name, s.type1, s.type2, s.base_hp, s.base_atk, s.base_def, s.base_spa, s.base_spd, s.base_spe
            FROM location_encounters le
            JOIN species s ON s.id = le.species_id
            WHERE le.location_id = ?
              AND le.is_active = 1
              AND (le.time_slot = "any" OR le.time_slot = ?)
        ');
        $st->execute([$locationId, $timeSlot]);
        $rows = $st->fetchAll();

        if (!$rows) {
            return $this->generateOpponent($rng, $level);
        }

        $pick = $this->weightedPick($rows, $rng);
        $speciesId = (int)$pick['species_id'];
        $minLevel = (int)$pick['min_level'];
        $maxLevel = (int)$pick['max_level'];
        if ($maxLevel < $minLevel) [$minLevel, $maxLevel] = [$maxLevel, $minLevel];
        $level = max(1, $rng->rangeInt($minLevel, $maxLevel));

        $abilityId = null;
        $abilityName = null;
        $abilityDesc = null;
        try {
            $stA = $pdo->prepare('
                SELECT a.id, a.name, a.description
                FROM species_abilities sa JOIN abilities a ON a.id = sa.ability_id
                WHERE sa.species_id = ? AND sa.slot = 0
                LIMIT 1
            ');
            $stA->execute([$speciesId]);
            $a = $stA->fetch();
            if ($a) { $abilityId = (int)$a['id']; $abilityName = (string)$a['name']; $abilityDesc = (string)$a['description']; }
        } catch (\Throwable $e) {}

        $nature = 'hardy';
        $happiness = 70;
        $iv = ['hp'=>10,'atk'=>10,'def'=>10,'spa'=>10,'spd'=>10,'spe'=>10];
        $ev = ['hp'=>0,'atk'=>0,'def'=>0,'spa'=>0,'spd'=>0,'spe'=>0];

        $base = [
            'hp'  => (int)$pick['base_hp'],
            'atk' => (int)$pick['base_atk'],
            'def' => (int)$pick['base_def'],
            'spa' => (int)$pick['base_spa'],
            'spd' => (int)$pick['base_spd'],
            'spe' => (int)$pick['base_spe'],
        ];
        $stats = StatCalculator::calcAll($base, $iv, $ev, $level, $nature);
        $moves = $this->defaultMovesForSpecies($speciesId);

        return [
            'id' => null,
            'species_id' => $speciesId,
            'species_name' => (string)$pick['name'],
            'nickname' => '',
            'level' => $level,
            'types' => [ (string)$pick['type1'], $pick['type2'] ? (string)$pick['type2'] : null ],
            'nature' => $nature,
            'happiness' => $happiness,
            'ability_id' => $abilityId,
            'ability_name' => $abilityName,
            'ability_description' => $abilityDesc,
            'held_item_id' => null,
            'held_item_name' => null,
            'iv_arr' => $iv,
            'ev_arr' => $ev,
            'base_stats' => $base,
            'final_stats' => $stats,
            'max_hp' => $stats['hp'],
            'current_hp' => $stats['hp'],
            'status_arr' => [],
            'moves' => $moves,
        ];
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function weightedPick(array $rows, Rng $rng): array {
        $total = 0;
        foreach ($rows as $row) $total += max(0, (int)$row['weight']);
        if ($total <= 0) return $rows[0];
        $roll = $rng->rangeInt(1, $total);
        $acc = 0;
        foreach ($rows as $row) {
            $acc += max(0, (int)$row['weight']);
            if ($roll <= $acc) return $row;
        }
        return $rows[array_key_last($rows)];
    }

    private function currentTimeSlot(): string {
        $hour = (int)date('G');
        return ($hour >= 6 && $hour < 20) ? 'day' : 'night';
    }

    /** @return array<int,array<string,mixed>> */
    private function defaultMovesForSpecies(int $speciesId): array {
        $pdo = Database::pdo();

        $sig = 5;
        if ($speciesId === 1) $sig = 2;
        if ($speciesId === 2) $sig = 3;
        if ($speciesId === 3) $sig = 4;

        $moveIds = [1, $sig, 5, 6];

        $in = implode(',', array_fill(0, count($moveIds), '?'));
        $st = $pdo->prepare("SELECT id AS move_id, name, type, category, power, accuracy, status_inflict, status_chance, pp, priority FROM moves WHERE id IN ($in)");
        $st->execute($moveIds);
        $rows = $st->fetchAll();

        $byId = [];
        foreach ($rows as $r) $byId[(int)$r['move_id']] = $r;

        $out = [];
        $slot = 1;
        foreach ($moveIds as $mid) {
            $m = $byId[$mid] ?? null;
            if (!$m) continue;
            $pp = (int)$m['pp'];
            $out[] = [
                'slot' => $slot,
                'pp_current' => $pp,
                'pp_max' => $pp,
                'move_id' => (int)$m['move_id'],
                'name' => (string)$m['name'],
                'type' => (string)$m['type'],
                'category' => (string)$m['category'],
                'power' => $m['power'] === null ? null : (int)$m['power'],
                'accuracy' => $m['accuracy'] === null ? null : (int)$m['accuracy'],
                'status_inflict' => $m['status_inflict'] === null ? null : (string)$m['status_inflict'],
                'status_chance' => $m['status_chance'] === null ? null : (int)$m['status_chance'],
                'pp' => $pp,
                'priority' => (int)$m['priority'],
            ];
            $slot++;
        }
        return $out;
    }

    /** Normalize creature array to battle side structure. */
    private function toBattleSide(string $side, array $c): array {
        $status = $c['status_arr'] ?? [];
        if (!is_array($status)) $status = [];
        $statusMajor = $status['major'] ?? null;
        $statusTurns = $status['turns'] ?? 0;
        return [
            'side' => $side,
            'id' => $c['id'],
            'species_id' => (int)$c['species_id'],
            'name' => (string)$c['species_name'],
            'nickname' => (string)($c['nickname'] ?? ''),
            'level' => (int)$c['level'],
            'types' => [ (string)$c['type1'], $c['type2'] ? (string)$c['type2'] : null ],
            'ability' => [
                'id' => $c['ability_id'] ?? null,
                'name' => $c['ability_name'] ?? null,
            ],
            'item' => [
                'id' => $c['held_item_id'] ?? null,
                'name' => $c['held_item_name'] ?? null,
            ],
            'stats' => $c['final_stats'],
            'hp' => (int)$c['current_hp'],
            'max_hp' => (int)$c['max_hp'],
            'status' => [
                'major' => $statusMajor,
                'turns' => (int)$statusTurns,
            ],
            'boosts' => [
                'atk' => 0, 'def' => 0, 'spa' => 0, 'spd' => 0, 'spe' => 0,
                'accuracy' => 0, 'evasion' => 0,
            ],
            'moves' => array_values(array_map(fn($m) => [
                'slot' => (int)$m['slot'],
                'pp_current' => (int)$m['pp_current'],
                'pp_max' => (int)$m['pp_max'],
                'move_id' => (int)$m['move_id'],
                'name' => (string)$m['name'],
                'type' => (string)$m['type'],
                'category' => (string)$m['category'],
                'power' => $m['power'] === null ? null : (int)$m['power'],
                'accuracy' => $m['accuracy'] === null ? null : (int)$m['accuracy'],
                'status_inflict' => $m['status_inflict'] === null ? null : (string)$m['status_inflict'],
                'status_chance' => $m['status_chance'] === null ? null : (int)$m['status_chance'],
                'priority' => (int)$m['priority'],
            ], $c['moves'] ?? [])),
        ];
    }

    /** Player selects a move slot (1..4). Returns updated battle row + state. */
    public function playerMove(int $userId, int $battleId, int $slot): array {
        $battle = $this->battles->findByIdForUser($userId, $battleId);
        if (!$battle) throw new \RuntimeException('Battle not found');
        if ((string)$battle['status'] !== 'active') throw new \RuntimeException('Battle already finished');

        $state = BattleRepository::decodeJson($battle['state']);
        $rng = new Rng((int)$battle['seed']);
        $rng->setState((int)($state['rng_state'] ?? (int)$battle['seed']));

        // Validate move
        $slot = max(1, min(4, $slot));
        $p1Action = ['type' => 'move', 'slot' => $slot];

        // Opponent selects random available move
        $p2Slot = $this->pickRandomAvailableSlot($state['p2']['moves'] ?? [], $rng);
        $p2Action = ['type' => 'move', 'slot' => $p2Slot];

        $res = BattleEngine::step($state, $p1Action, $p2Action, $rng);
        $newState = $res['state'];
        $turn = (int)$newState['turn'];

        // Persist
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $this->battles->addAction($battleId, $turn, 'p1', $p1Action);
            $this->battles->addAction($battleId, $turn, 'p2', $p2Action);
            $this->battles->addLogs($battleId, $res['logs']);
            $this->battles->addSnapshot($battleId, $turn, $newState);

            $status = !empty($newState['finished']) ? 'finished' : 'active';
            $result = [];
            $finishedAt = null;
            if ($status === 'finished') {
                $result = [
                    'winner' => $newState['winner'] ?? null,
                    'turns' => $turn,
                ];
                $finishedAt = date('Y-m-d H:i:s');

                // Write back player's remaining HP to the persistent creature record.
                $playerCreatureId = (int)$battle['player_creature_id'];
                $hp = (int)($newState['p1']['hp'] ?? 0);
                $st = $pdo->prepare('UPDATE user_creatures SET current_hp = ? WHERE id = ?');
                $st->execute([$hp, $playerCreatureId]);
            }

            $this->battles->updateState($battleId, $turn, $status, $newState, $result, $finishedAt);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $battle = $this->battles->findByIdForUser($userId, $battleId);
        return [
            'battle' => $battle,
            'state' => $newState,
        ];
    }

    public function forfeit(int $userId, int $battleId): array {
        $battle = $this->battles->findByIdForUser($userId, $battleId);
        if (!$battle) throw new \RuntimeException('Battle not found');
        if ((string)$battle['status'] !== 'active') throw new \RuntimeException('Battle already finished');

        $state = BattleRepository::decodeJson($battle['state']);
        $turn = (int)($state['turn'] ?? 0);
        $state['finished'] = true;
        $state['winner'] = 'p2';
        $state['forfeit'] = 'p1';

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $this->battles->addLogs($battleId, [
                ['turn' => $turn, 'seq' => 0, 'message' => 'p1 forfeited.', 'payload' => ['side' => 'p1']],
                ['turn' => $turn, 'seq' => 1, 'message' => 'Battle finished.', 'payload' => ['winner' => 'p2']],
            ]);
            $this->battles->addSnapshot($battleId, $turn, $state);
            $this->battles->updateState($battleId, $turn, 'finished', $state, [
                'winner' => 'p2',
                'turns' => $turn,
                'forfeit' => 'p1',
            ], date('Y-m-d H:i:s'));
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'battle' => $this->battles->findByIdForUser($userId, $battleId),
            'state' => $state,
        ];
    }

    private function pickRandomAvailableSlot(array $moves, Rng $rng): int {
        $avail = [];
        foreach ($moves as $m) {
            if ((int)($m['pp_current'] ?? 0) > 0) $avail[] = (int)$m['slot'];
        }
        if (!$avail) return 1;
        return $avail[$rng->nextInt(count($avail))];
    }
}

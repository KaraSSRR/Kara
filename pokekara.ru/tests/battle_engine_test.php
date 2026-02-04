<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Domain\Battle\BattleEngine;
use App\Domain\Battle\Rng;

function assertTrue(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "ASSERT FAIL: {$msg}\n");
        exit(1);
    }
}

$seed = 123456;
$rng1 = new Rng($seed);
$rng2 = new Rng($seed);

$state = [
  'turn' => 0,
  'finished' => false,
  'winner' => null,
  'rng_state' => $seed,
  'p1' => [
    'level' => 5,
    'types' => ['grass', null],
    'hp' => 20, 'max_hp' => 20,
    'stats' => ['hp'=>20,'atk'=>10,'def'=>10,'spa'=>10,'spd'=>10,'spe'=>12],
    'status' => ['major' => null, 'turns' => 0],
    'boosts' => ['atk'=>0,'def'=>0,'spa'=>0,'spd'=>0,'spe'=>0,'accuracy'=>0,'evasion'=>0],
    'moves' => [
      ['slot'=>1,'pp_current'=>35,'pp_max'=>35,'move_id'=>1,'name'=>'Force Tap','type'=>'normal','category'=>'physical','power'=>40,'accuracy'=>100,'priority'=>0],
      ['slot'=>2,'pp_current'=>25,'pp_max'=>25,'move_id'=>2,'name'=>'Thorn Lash','type'=>'grass','category'=>'physical','power'=>45,'accuracy'=>100,'priority'=>0],
      ['slot'=>3,'pp_current'=>30,'pp_max'=>30,'move_id'=>5,'name'=>'Quickstep','type'=>'normal','category'=>'physical','power'=>40,'accuracy'=>100,'priority'=>1],
      ['slot'=>4,'pp_current'=>40,'pp_max'=>40,'move_id'=>6,'name'=>'Low Call','type'=>'normal','category'=>'status','power'=>null,'accuracy'=>100,'priority'=>0],
      ['slot'=>5,'pp_current'=>15,'pp_max'=>15,'move_id'=>9,'name'=>'Dream Dust','type'=>'psychic','category'=>'status','power'=>null,'accuracy'=>100,'priority'=>0,'status_inflict'=>'sleep','status_chance'=>100],
    ],
  ],
  'p2' => [
    'level' => 5,
    'types' => ['water', null],
    'hp' => 20, 'max_hp' => 20,
    'stats' => ['hp'=>20,'atk'=>10,'def'=>10,'spa'=>10,'spd'=>10,'spe'=>10],
    'status' => ['major' => null, 'turns' => 0],
    'boosts' => ['atk'=>0,'def'=>0,'spa'=>0,'spd'=>0,'spe'=>0,'accuracy'=>0,'evasion'=>0],
    'moves' => [
      ['slot'=>1,'pp_current'=>25,'pp_max'=>25,'move_id'=>4,'name'=>'Stream Bolt','type'=>'water','category'=>'special','power'=>40,'accuracy'=>100,'priority'=>0],
    ],
  ],
];

// Test: priority makes p1 go first with Quick Attack even if slower (here p1 is faster anyway, but keep).
$res1 = BattleEngine::step($state, ['type'=>'move','slot'=>3], ['type'=>'move','slot'=>1], $rng1);
$res2 = BattleEngine::step($state, ['type'=>'move','slot'=>3], ['type'=>'move','slot'=>1], $rng2);

assertTrue($res1['state']['turn'] === 1, 'turn should increment');
assertTrue($res2['state']['turn'] === 1, 'turn should increment (determinism)');

// Determinism: same seed + same actions => same hp and same logs
assertTrue($res1['state']['p1']['hp'] === $res2['state']['p1']['hp'], 'p1 hp deterministic');
assertTrue($res1['state']['p2']['hp'] === $res2['state']['p2']['hp'], 'p2 hp deterministic');
assertTrue(count($res1['logs']) === count($res2['logs']), 'log count deterministic');

$state1 = $res1['state'];
assertTrue($state1['p2']['hp'] < 20, 'p2 should take damage');
assertTrue($state1['p1']['moves'][2]['pp_current'] === 29, 'pp should decrement for slot 3');

// Test: status move (Low Call) should reduce target atk by 1 stage (down to -1)
$rng3 = new Rng($seed);
$res3 = BattleEngine::step($state, ['type'=>'move','slot'=>4], ['type'=>'move','slot'=>1], $rng3);
assertTrue(($res3['state']['p2']['boosts']['atk'] ?? 0) === -1, 'low call should lower atk');

// Test: status infliction (sleep)
$rng4 = new Rng($seed);
$res4 = BattleEngine::step($state, ['type'=>'move','slot'=>5], ['type'=>'move','slot'=>1], $rng4);
assertTrue(($res4['state']['p2']['status']['major'] ?? null) === 'sleep', 'sleep should apply');

echo "BATTLE_ENGINE_TEST_OK\n";

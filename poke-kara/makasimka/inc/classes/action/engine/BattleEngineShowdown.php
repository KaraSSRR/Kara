<?php
require_once __DIR__ . '/BattleEngineInterface.php';
require_once __DIR__ . '/BattleEngineLegacy.php';
require_once __DIR__ . '/ShowdownClient.php';
require_once __DIR__ . '/ShowdownMapper.php';

/**
 * Движок на базе pokemon-showdown (Gen9), через внешний микросервис showdown-sim.
 *
 * Принципы:
 * - Делаем PS "источником истины" для урона/точности/приоритетов/эффектов (как на Pokémon Showdown).
 * - Состояние боя храним в info_1['__showdown'] (и зеркалим в info_2), чтобы не требовать правок схемы БД.
 * - Если происходит действие, которого нет в PS (например, использование предметов в бою в вашем проекте),
 *   автоматически падаем назад на legacy-движок и сбрасываем showdown-state (best-effort).
 */
class BattleEngineShowdown implements BattleEngineInterface {

    private $ctx;
    private $client;
    private $legacy;

    public function __construct($actionBattleContext, string $serviceUrl) {
        $this->ctx = $actionBattleContext;
        $this->client = new ShowdownClient($serviceUrl);
        $this->legacy = new BattleEngineLegacy($actionBattleContext);
    }

    public function runTurn(array &$info_1, array &$info_2, array &$target_1, array &$target_2): void {

        // Если кто-то пытается использовать предмет/поймать покемона и т.п. — это вне PS-правил.
        // Временно оставляем это на legacy-движке, но очищаем PS state.
        $atk1 = (int)($info_1['targetAtk'] ?? 0);
        $atk2 = (int)($info_2['targetAtk'] ?? 0);
        if ($atk1 === 9998 || $atk2 === 9998) {
            unset($info_1['__showdown'], $info_2['__showdown']);
            $this->legacy->runTurn($info_1, $info_2, $target_1, $target_2);
            return;
        }

        $state = (isset($info_1['__showdown']) && is_array($info_1['__showdown'])) ? $info_1['__showdown'] : null;

        // === Инициализация showdown-state (seed + packed teams + mapping) ===
        if (!$state || empty($state['seed']) || empty($state['p1']) || empty($state['p2'])) {
            $state = $this->buildInitialState($info_1, $info_2, $target_1, $target_2);
        }

        // Сформируем choice strings (см. SIM-PROTOCOL: move/switch) 
        $choiceP1 = $this->choiceFromInfo($state['p1']['slotMap'] ?? [], $info_1, $target_1);
        $choiceP2 = $this->choiceFromInfo($state['p2']['slotMap'] ?? [], $info_2, $target_2);

        $payload = [
            'state' => $state,
            'choice' => [
                'p1' => $choiceP1,
                'p2' => $choiceP2,
            ],
        ];

        $resp = $this->client->step($payload);

        if (empty($resp['ok'])) {
            // На любой ошибке сервиса — деградация в legacy, чтобы бой не "встал"
            $info_1['__showdown_error'] = $resp['error'] ?? 'unknown';
            unset($info_1['__showdown'], $info_2['__showdown']);
            $this->legacy->runTurn($info_1, $info_2, $target_1, $target_2);
            return;
        }

        $newState = $resp['state'] ?? $state;

        // Логи боя (сырые строки протокола PS) — пригодятся для фронта и дебага
        if (isset($resp['log']) && is_array($resp['log'])) {
            $newState['last_log'] = $resp['log'];
        }

        // Обновим hp/pp/disabled в наших структурах по request-объектам
        $req1 = $resp['request']['p1'] ?? null;
        $req2 = $resp['request']['p2'] ?? null;

        if (is_array($req1)) $this->applyRequestToSide($req1, $state['p1']['slotMap'] ?? [], $info_1, $target_1);
        if (is_array($req2)) $this->applyRequestToSide($req2, $state['p2']['slotMap'] ?? [], $info_2, $target_2);

        // Сохраняем state в обе стороны, чтобы и фронт, и сервер имели доступ
        $info_1['__showdown'] = $newState;
        $info_2['__showdown'] = $newState;

        // Если бой закончился — проставим флаг (ActionBattle дальше отработает награды/завершение)
        if (!empty($resp['battleEnded'])) {
            $info_1['__showdown_battleEnded'] = true;
            $info_2['__showdown_battleEnded'] = true;
        }
    }

    private function buildInitialState(array $info_1, array $info_2, array $target_1, array $target_2): array {
        $p1Sets = [];
        $p2Sets = [];
        $p1SlotMap = [];
        $p2SlotMap = [];

        $list1 = $info_1['pokeLIst'] ?? [];
        $list2 = $info_2['pokeLIst'] ?? [];

        foreach ($list1 as $k => $poke) {
            if (!is_array($poke) || empty($poke['id'])) continue;
            $p1SlotMap[] = (int)$poke['id'];
            $p1Sets[] = ShowdownMapper::pokemonSet($poke);
        }
        foreach ($list2 as $k => $poke) {
            if (!is_array($poke) || empty($poke['id'])) continue;
            $p2SlotMap[] = (int)$poke['id'];
            $p2Sets[] = ShowdownMapper::pokemonSet($poke);
        }

        // Генерируем seed (PS ожидает массив из 4 int)
        $seed = [random_int(1, 2147483647), random_int(1, 2147483647), random_int(1, 2147483647), random_int(1, 2147483647)];

        return [
            'formatid' => 'gen9customgame',
            'seed' => $seed,
            'p1' => [
                'name' => (string)($info_1['userInfo']['login'] ?? 'p1'),
                'team' => $p1Sets,
                'slotMap' => $p1SlotMap,
            ],
            'p2' => [
                'name' => (string)($info_2['userInfo']['login'] ?? 'p2'),
                'team' => $p2Sets,
                'slotMap' => $p2SlotMap,
            ],
            'choices' => [],
        ];
    }

    private function choiceFromInfo(array $slotMap, array $info, array $activePoke): string {
        $atk = (int)($info['targetAtk'] ?? 0);

        // switch
        if ($atk === 9999) {
            $to = (int)($info['targetPokemon'] ?? 0);
            $slot = ShowdownMapper::switchSlotFromPokemonId($slotMap, $to);
            return 'switch ' . $slot;
        }

        // move
        if ($atk > 0) {
            $slot = ShowdownMapper::moveSlotFromAttackId($activePoke, $atk);
            return 'move ' . $slot;
        }

        // fallback: default
        return 'default';
    }

    private function applyRequestToSide(array $req, array $slotMap, array &$info, array &$activePoke): void {
        // request.side.pokemon — состояние команды
        if (isset($req['side']['pokemon']) && is_array($req['side']['pokemon'])) {
            foreach ($req['side']['pokemon'] as $idx => $p) {
                $pokeId = $slotMap[$idx] ?? 0;
                if (!$pokeId) continue;
                $key = 'p' . $pokeId;
                if (!isset($info['pokeLIst'][$key])) continue;

                $cond = (string)($p['condition'] ?? '');
                [$hpNow,$hpMax,$status] = ShowdownMapper::parseCondition($cond);

                // Обновляем HP в наших структурах (и для активного покемона тоже)
                if ($hpNow || $hpMax || $status) {
                    $info['pokeLIst'][$key]['hp'] = $hpNow;
                    $info['pokeLIst'][$key]['hp_ps_max'] = $hpMax; // не ломает старое, но полезно
                    $info['pokeLIst'][$key]['status_ps'] = $status;

                    if ((int)($activePoke['id'] ?? 0) === (int)$pokeId) {
                        $activePoke['hp'] = $hpNow;
                        $activePoke['hp_ps_max'] = $hpMax;
                        $activePoke['status_ps'] = $status;
                    }
                }
            }
        }

        // request.active[0].moves — PP/disabled для активного
        if (isset($req['active'][0]['moves']) && is_array($req['active'][0]['moves'])) {
            $moves = $req['active'][0]['moves'];
            $pp = [];
            $dis = [];

            foreach ($moves as $m) {
                $pp[] = (int)($m['pp'] ?? 0);
                $dis[] = !empty($m['disabled']) ? 1 : 0;
            }

            // Нормализуем до 4 слотов (ваша система ожидает 4)
            $pp = array_pad($pp, 4, 0);
            $dis = array_pad($dis, 4, 0);

            $ppStr = implode(',', array_slice($pp, 0, 4));
            $disStr = implode(',', array_slice($dis, 0, 4));

            $activeKey = 'p' . (int)($activePoke['id'] ?? 0);
            if ($activeKey !== 'p0' && isset($info['pokeLIst'][$activeKey])) {
                $info['pokeLIst'][$activeKey]['pp_attacks'] = $ppStr;
                $info['pokeLIst'][$activeKey]['pp_my'] = $ppStr;
                $info['pokeLIst'][$activeKey]['disable_my'] = $disStr;
            }
        }
    }
}

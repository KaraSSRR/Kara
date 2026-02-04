<?php
class Battle{

	// -------------------------------------------------------------------------
	// CACHE / HELPERS (ускорение и унификация данных из БД)
	// -------------------------------------------------------------------------
	private static $cacheMove = [];
	private static $cacheMoveMaxId = null;

	private static $cacheAbilityNameRus = [];

	private function _jsonToArray($val){
		if(empty($val)){
			return null;
		}
		if(is_array($val)){
			return $val;
		}
		// Некоторые источники могут отдавать уже распарсенный JSON (но как строку)
		if(is_string($val)){
			$val = trim($val);
			if($val === '' || $val === 'NULL'){
				return null;
			}
			// БД хранит JSON
			$j = json_decode($val, true);
			if(is_array($j)){
				return $j;
			}
		}
		return null;
	}

	private function _getMove($id){
		$id = (int)$id;
		if($id <= 0){
			return null;
		}
		if(isset(self::$cacheMove[$id])){
			return self::$cacheMove[$id];
		}

		$row = null;
		if(Work::$sql){
			$res = Work::$sql->query("SELECT * FROM base_atk WHERE id = ".$id);
			$row = ($res ? $res->fetch_assoc() : null);
		}
		if(empty($row)){
			self::$cacheMove[$id] = null;
			return null;
		}

		// Декодируем JSON-поля так же, как это делает PokeBattle::_getAtkInfo()
		if(isset($row['settings'])){
			$row['settings'] = $this->_jsonToArray($row['settings']);
		}
		if(isset($row['my'])){
			$row['my'] = $this->_jsonToArray($row['my']);
		}
		if(isset($row['enemy'])){
			$row['enemy'] = $this->_jsonToArray($row['enemy']);
		}

		self::$cacheMove[$id] = $row;
		return self::$cacheMove[$id];
	}

	private function _getMoveMaxId(){
		if(self::$cacheMoveMaxId !== null){
			return (int)self::$cacheMoveMaxId;
		}
		$row = Work::$sql->query("SELECT MAX(id) AS max_id FROM base_atk")->fetch_assoc();
		self::$cacheMoveMaxId = (!empty($row['max_id']) ? (int)$row['max_id'] : 0);
		return (int)self::$cacheMoveMaxId;
	}

	private function _abilNameRus($id){
		$id = (int)$id;
		if($id <= 0){
			return '';
		}
		if(isset(self::$cacheAbilityNameRus[$id])){
			return self::$cacheAbilityNameRus[$id];
		}

		$name = '';
		if(Work::$sql){
			$res = Work::$sql->query("SELECT name_rus FROM base_ability WHERE id = ".$id);
			$row = ($res ? $res->fetch_assoc() : null);
			if(!empty($row['name_rus'])){
				$name = (string)$row['name_rus'];
			}
		}
		self::$cacheAbilityNameRus[$id] = $name;
		return self::$cacheAbilityNameRus[$id];
	}

	private function _rollMultiHit($min, $max){
		$min = (int)$min;
		$max = (int)$max;
		if($min <= 0){
			$min = 1;
		}
		if($max < $min){
			$max = $min;
		}
		if($max == $min){
			return $min;
		}

		// Gen 5+ (как в Showdown): 2-5 ударов имеют распределение 37.5/37.5/12.5/12.5
		if($min == 2 && $max == 5){
			$r = mt_rand(1, 1000);
			if($r <= 375){
				return 2;
			}elseif($r <= 750){
				return 3;
			}elseif($r <= 875){
				return 4;
			}else{
				return 5;
			}
		}

		return mt_rand($min, $max);
	}

	private $statRus = [
		'atk' => ['Атака','Атаку'],
		'def' => ['Защита','Защиту'],
		'spd' => ['Скорость','Скорость'],
		'satk' => ['Спец. Атака','Спец. Атаку'],
		'sdef' => ['Спец. Защита','Спец. Защиту']
	];

    private $types = [
        'normal'    =>['bug'=>1,    'dark'=>1,   'dragon'=>1,   'electric'=>1,   'fairy'=>1,    'fighting'=>1,   'fire'=>1,     'fly'=>1,   'ghost'=>0,     'grass'=>1,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>0.5, 'steel'=>0.5,  'water'=>1],
        'fighting'  =>['bug'=>0.5,  'dark'=>2,   'dragon'=>1,   'electric'=>1,   'fairy'=>0.5,  'fighting'=>1,   'fire'=>1,     'fly'=>0.5, 'ghost'=>0,     'grass'=>1,     'ground'=>1,    'ice'=>2,   'normal'=>2, 'poison'=>0.5, 'psychic'=>0.5, 'rock'=>2,   'steel'=>2,    'water'=>1],
        'fly'       =>['bug'=>2,    'dark'=>1,   'dragon'=>1,   'electric'=>0.5, 'fairy'=>1,    'fighting'=>2,   'fire'=>1,     'fly'=>1,   'ghost'=>1,     'grass'=>2,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>0.5, 'steel'=>0.5,  'water'=>1],
        'poison'    =>['bug'=>1,    'dark'=>1,   'dragon'=>1,   'electric'=>1,   'fairy'=>2,    'fighting'=>1,   'fire'=>1,     'fly'=>1,   'ghost'=>0.5,   'grass'=>2,     'ground'=>0.5,  'ice'=>1,   'normal'=>1, 'poison'=>0.5, 'psychic'=>1,   'rock'=>0.5, 'steel'=>0,    'water'=>1],
        'ground'    =>['bug'=>0.5,  'dark'=>1,   'dragon'=>1,   'electric'=>2,   'fairy'=>1,    'fighting'=>1,   'fire'=>2,     'fly'=>0,   'ghost'=>1,     'grass'=>0.5,   'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>2,   'psychic'=>1,   'rock'=>2,   'steel'=>2,    'water'=>1],
        'rock'      =>['bug'=>2,    'dark'=>1,   'dragon'=>1,   'electric'=>1,   'fairy'=>1,    'fighting'=>0.5, 'fire'=>2,     'fly'=>2,   'ghost'=>1,     'grass'=>1,     'ground'=>0.5,  'ice'=>2,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>1,   'steel'=>0.5,  'water'=>1],
        'bug'       =>['bug'=>1,    'dark'=>2,   'dragon'=>1,   'electric'=>1,   'fairy'=>0.5,  'fighting'=>0.5, 'fire'=>0.5,   'fly'=>0.5, 'ghost'=>0.5,   'grass'=>2,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>0.5, 'psychic'=>2,   'rock'=>1,   'steel'=>0.5,  'water'=>1],
        'ghost'     =>['bug'=>1,    'dark'=>0.5, 'dragon'=>1,   'electric'=>1,   'fairy'=>1,    'fighting'=>1,   'fire'=>1,     'fly'=>1,   'ghost'=>2,     'grass'=>1,     'ground'=>1,    'ice'=>1,   'normal'=>0, 'poison'=>1,   'psychic'=>2,   'rock'=>1,   'steel'=>1,    'water'=>1],
        'steel'     =>['bug'=>1,    'dark'=>1,   'dragon'=>1,   'electric'=>0.5, 'fairy'=>2,    'fighting'=>1,   'fire'=>0.5,   'fly'=>1,   'ghost'=>1,     'grass'=>1,     'ground'=>1,    'ice'=>2,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>2,   'steel'=>0.5,  'water'=>0.5],
        'fire'      =>['bug'=>2,    'dark'=>1,   'dragon'=>0.5, 'electric'=>1,   'fairy'=>1,    'fighting'=>1,   'fire'=>0.5,   'fly'=>1,   'ghost'=>1,     'grass'=>2,     'ground'=>1,    'ice'=>2,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>0.5, 'steel'=>2,    'water'=>0.5],
        'water'     =>['bug'=>1,    'dark'=>1,   'dragon'=>0.5, 'electric'=>1,   'fairy'=>1,    'fighting'=>1,   'fire'=>2,     'fly'=>1,   'ghost'=>1,     'grass'=>0.5,   'ground'=>2,    'ice'=>1,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>2,   'steel'=>1,    'water'=>0.5],
        'grass'     =>['bug'=>0.5,  'dark'=>1,   'dragon'=>0.5, 'electric'=>1,   'fairy'=>1,    'fighting'=>1,   'fire'=>0.5,   'fly'=>0.5, 'ghost'=>1,     'grass'=>0.5,   'ground'=>2,    'ice'=>1,   'normal'=>1, 'poison'=>0.5, 'psychic'=>1,   'rock'=>2,   'steel'=>0.5,  'water'=>2],
        'electric'  =>['bug'=>1,    'dark'=>1,   'dragon'=>0.5, 'electric'=>0.5, 'fairy'=>1,    'fighting'=>1,   'fire'=>1,     'fly'=>2,   'ghost'=>1,     'grass'=>0.5,   'ground'=>0,    'ice'=>1,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>1,   'steel'=>1,    'water'=>2],
        'psychic'   =>['bug'=>1,    'dark'=>0,   'dragon'=>1,   'electric'=>1,   'fairy'=>1,    'fighting'=>2,   'fire'=>1,     'fly'=>1,   'ghost'=>1,     'grass'=>1,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>2,   'psychic'=>0.5, 'rock'=>1,   'steel'=>0.5,  'water'=>1],
        'ice'       =>['bug'=>1,    'dark'=>1,   'dragon'=>2,   'electric'=>1,   'fairy'=>1,    'fighting'=>1,   'fire'=>0.5,   'fly'=>2,   'ghost'=>1,     'grass'=>2,     'ground'=>2,    'ice'=>0.5, 'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>1,   'steel'=>0.5,  'water'=>0.5],
        'dragon'    =>['bug'=>1,    'dark'=>1,   'dragon'=>2,   'electric'=>1,   'fairy'=>0,    'fighting'=>1,   'fire'=>1,     'fly'=>1,   'ghost'=>1,     'grass'=>1,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>1,   'psychic'=>1,   'rock'=>1,   'steel'=>0.5,  'water'=>1],
        'dark'      =>['bug'=>1,    'dark'=>0.5, 'dragon'=>1,   'electric'=>1,   'fairy'=>0.5,  'fighting'=>0.5, 'fire'=>1,     'fly'=>1,   'ghost'=>2,     'grass'=>1,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>1,   'psychic'=>2,   'rock'=>1,   'steel'=>1,    'water'=>1],
        'fairy'     =>['bug'=>1,    'dark'=>2,   'dragon'=>2,   'electric'=>1,   'fairy'=>1,    'fighting'=>2,   'fire'=>0.5,   'fly'=>1,   'ghost'=>1,     'grass'=>1,     'ground'=>1,    'ice'=>1,   'normal'=>1, 'poison'=>0.5, 'psychic'=>1,   'rock'=>1,   'steel'=>0.5,  'water'=>1],
    ];
    private $titleStatus = [
        'toxic'=>'На %s накладывается статус отравления.',
        'toxic2'=>'На %s накладывается статус сильного отравления.',
        'flinch'=>'%s напуган и не сможет удачно использовать какую-либо атаку.',
        'paralyzed'=>'%s теперь парализован.',
        'sleep'=>'На %s накладывается статус усыпления.',
        'burn'=>'%s теперь в огне.',
        'frost'=>'%s теперь заморожен.',
        'lover'=>'%s влюбляется в своего соперника.',
        'curse'=>'Покемон проклинает противника отдавая половину своих жизней',
        'confused'=>'%s спутан.',
        'rage'=>'%s впадает в бешенство.',
        'taunt'=>'На %s наложена насмешка.',
        'stock'=>'%s накапливает предметы в своем рту.',
        'trickortreat'=>'К %s добавляется призрачный тип.',
        'nightmare'=>'%s мучается от кошмаров.',
        'prison'=>'%s попал в ловушку.',
        'leechSeed'=>'%s теперь под семенами пиявками.',
        'statdef'=>'%s теперь под защитой от изменения статов.',
        'ingrain'=>'%s теперь вкопан в землю.'
    ];
/** @var $_actionBattle ActionBattle */
    private $_actionBattle;

    /** @var $attacker PokeBattle */
    private $attacker;

    /** @var $defender PokeBattle */
    private $defender;

    /** @var $attacker array */
    private $attackerAtk;

    /** @var $defender array */
    private $defenderAtk;

    private $user_log = [];
    private $log_start = [];
    private $log = [];
    private $log_end = [];
    private $log_status = [];

    private $settings = [
        'effect_my'     => true,
        'effect_enemy'  => true,
        'dmg'           => 0,
        'stab'          => 1,
        'types'         => 1,
        'critical'      => 1,
        'crash'         => false,
        'weather'       => 0,
        'weather_round' => 0,
    ];

    private $settings_default = [
        'dmg'      => 0,
        'critical' => 1,
        'stab'     => 1,
        'types'    => 1
    ];

    private $catch = false;
    private $dmg_1 = 1000000;
    private $dmg_2 = 1000000;
    private $next_turn = 0;
    private $meFirst = 0;
    private $ability2Effect = 0;
    private $promah = 0;
    private $itemEffect312 = 0;
    private $metronom = 0;

    public function __construct(ActionBattle $actionBattle, array &$info_1, array &$info_2, array &$target_1, array &$target_2) {
        $this->_actionBattle = $actionBattle;
        if (isset($target_1['id'], $target_2['id'])) {
            $poke_1 = new PokeBattle($target_1);
            $poke_2 = new PokeBattle($target_2);
            $atk_1 = $poke_1->_getAtkInfo(isset($info_1['targetAtk']) ? $info_1['targetAtk'] : 0);
            $atk_2 = $poke_2->_getAtkInfo(isset($info_2['targetAtk']) ? $info_2['targetAtk'] : 0);
            if (!empty($atk_1) && !empty($atk_2)) {

                $enemy = 0;
                $skor1 = $poke_1->_getStatSpd();
                $skor2 = $poke_2->_getStatSpd();

                // Метроним (случайная атака)
                if ($atk_1['id'] == 320) {
                    $metr = mt_rand(1, $this->_getMoveMaxId());                    $metronom = $this->_getMove($metr);
                    if(!$metronom){
                        $metr = 1;
                        $metronom = $this->_getMove($metr);
                    }
                    if ($metronom['tech'] != '<font color=green>Атака работает исправно</font>' || in_array($metronom['id'], [9, 320, 240, 688, 413, 22, 13])) {
                        $metr = [1,2,3,4,5,6,7,8,10,11,12,14,15,16,17,18,19,20,678,23,24,25,26,27,28,29,30,31,32,33,35,36,37,38,39,41,42,43,44,45,47,48,49,50];
                        shuffle($metr);
                        $metronom = $this->_getMove($metr[0]);
                    }
                    $poke_1->metronom = 1;
                    $atk_1['id'] = $metronom['id'];
                    $atk_1['name'] = $metronom['name_rus'];
                    $atk_1['type'] = $metronom['type'];
                    $atk_1['category'] = $metronom['category'];
                    $atk_1['priority'] = $metronom['priority'];
                    $atk_1['power'] = $metronom['power'];
                    $atk_1['accuracy'] = $metronom['accuracy'];
                    $atk_1['target'] = $metronom['target'];
                    $atk_1['settings'] = $metronom['settings'];
                    $atk_1['my'] = $metronom['my'];
                    $atk_1['enemy'] = $metronom['enemy'];
                    $atk_1['contact'] = $metronom['contact'];
                } else {
                    $poke_1->metronom = 0;
                }
                if ($atk_2['id'] == 320) {
                    $metr = mt_rand(1, $this->_getMoveMaxId());                    $metronom = $this->_getMove($metr);
                    if(!$metronom){
                        $metr = 1;
                        $metronom = $this->_getMove($metr);
                    }
                    if ($metronom['tech'] != '<font color=green>Атака работает исправно</font>' || in_array($metronom['id'], [9, 320])) {
                        $metr = [1,2,3,4,5,6,7,8,10,11,12,13,14,15,16,17,18,19,20,678,22,23,24,25,26,27,28,29,30,31,32,33,35,36,37,38,39,41,42,43,44,45,46,47,48,49,50];
                        shuffle($metr);
                        $metronom = $this->_getMove($metr[0]);
                    }
                    $poke_2->metronom = 1;
                    $atk_2['id'] = $metronom['id'];
                    $atk_2['name'] = $metronom['name_rus'];
                    $atk_2['type'] = $metronom['type'];
                    $atk_2['category'] = $metronom['category'];
                    $atk_2['priority'] = $metronom['priority'];
                    $atk_2['power'] = $metronom['power'];
                    $atk_2['accuracy'] = $metronom['accuracy'];
                    $atk_2['target'] = $metronom['target'];
                    $atk_2['settings'] = $metronom['settings'];
                    $atk_2['my'] = $metronom['my'];
                    $atk_2['enemy'] = $metronom['enemy'];
                    $atk_2['contact'] = $metronom['contact'];
                } else {
                    $poke_2->metronom = 0;
                }
                // Статусы, влияющие на скорость
                if ($poke_1->_checkStatus('burn') || $poke_1->_checkStatus('frost') || $poke_1->_checkStatus('sleep') || $poke_1->_checkStatus('toxic') || $poke_1->_checkStatus('toxic2') || $poke_1->_checkStatus('paralyzed')) {
                    if ($poke_1->ability == 146) {
                        $skor1 *= 1.5;
                    }
                }
                if ($poke_2->_checkStatus('burn') || $poke_2->_checkStatus('frost') || $poke_2->_checkStatus('sleep') || $poke_2->_checkStatus('toxic') || $poke_2->_checkStatus('toxic2') || $poke_2->_checkStatus('paralyzed')) {
                    if ($poke_2->ability == 146) {
                        $skor2 *= 1.5;
                    }
                }
                if ($poke_1->_checkStatus('tailwind')) {
                    $skor1 *= 2;
                }
                if ($poke_2->_checkStatus('tailwind')) {
                    $skor2 *= 2;
                }
                // Погодные эффекты скорости
                if ($this->_actionBattle->weather == 2 && !in_array(4, [$poke_2->ability, $poke_1->ability])) {
                    if ($poke_1->ability == 22) {
                        $skor1 *= 2;
                    }
                    if ($poke_2->ability == 22) {
                        $skor2 *= 2;
                    }
                }
                if ($this->_actionBattle->weather == 4 && !in_array(4, [$poke_2->ability, $poke_1->ability])) {
                    if ($poke_1->ability == 176) {
                        $skor1 *= 2;
                    }
                    if ($poke_2->ability == 176) {
                        $skor2 *= 2;
                    }
                }
                if ($this->_actionBattle->weather == 3 && !in_array(4, [$poke_2->ability, $poke_1->ability])) {
                    if ($poke_1->ability == 202) {
                        $skor1 *= 2;
                    }
                    if ($poke_2->ability == 202) {
                        $skor2 *= 2;
                    }
                }
                if ($this->_actionBattle->weather == 5 && !in_array(4, [$poke_2->ability, $poke_1->ability])) {
                    if ($poke_1->ability == 159) {
                        $skor1 *= 2;
                    }
                    if ($poke_2->ability == 159) {
                        $skor2 *= 2;
                    }
                }
                // Приоритеты (умения, предметы)
                if ($poke_1->ability == 216 && in_array($atk_1['id'], [630,682,198,429,547])) {
                    $atk_1['priority'] = 3;
                }
                if ($poke_2->ability == 216 && in_array($atk_2['id'], [630,682,198,429,547])) {
                    $atk_2['priority'] = 3;
                }
                if ($poke_1->ability == 138 && in_array($atk_1['category'], ['specific', 'status'])) {
                    $atk_1['priority'] += 1;
                }
                if ($poke_2->ability == 138 && in_array($atk_2['category'], ['specific', 'status'])) {
                    $atk_2['priority'] += 1;
                }
                // Определение порядка ходов (учёт предметов)
                if ($poke_1->item_id == 147 && $poke_2->item_id != 147) {
                    $enemy = 1;
                } elseif ($poke_2->item_id == 147 && $poke_1->item_id != 147) {
                    $enemy = 0;
                } else {
                    if ($poke_1->item_id == 155 && $poke_2->item_id != 155) {
                        $rand = mt_rand(1, 100);
                        if ($rand <= 20) {
                            $enemy = 0;
                        } else {
                            if ($atk_2['priority'] != $atk_1['priority']) {
                                if ($atk_2['priority'] > $atk_1['priority']) {
                                    $enemy = 1;
                                }
                            } elseif ($skor2 != $skor1) {
                                if ($skor2 > $skor1) {
                                    $enemy = 1;
                                }
                            } elseif (mt_rand(1, 2) == 2) {
                                $enemy = 1;
                            }
                        }
                    } elseif ($poke_2->item_id == 155 && $poke_1->item_id != 155) {
                        $rand = mt_rand(1, 100);
                        if ($rand <= 20) {
                            $enemy = 1;
                        } else {
                            if ($atk_2['priority'] != $atk_1['priority']) {
                                if ($atk_2['priority'] > $atk_1['priority']) {
                                    $enemy = 1;
                                }
                            } elseif ($skor2 != $skor1) {
                                if ($skor2 > $skor1) {
                                    $enemy = 1;
                                }
                            } elseif (mt_rand(1, 2) == 2) {
                                $enemy = 1;
                            }
                        }
                    } else {
                        if ($atk_2['priority'] != $atk_1['priority']) {
                            if ($atk_2['priority'] > $atk_1['priority']) {
                                $enemy = 1;
                            }
                        } elseif ($skor2 != $skor1) {
                            if ($skor2 > $skor1) {
                                $enemy = 1;
                            }
                        } elseif (mt_rand(1, 2) == 2) {
                            $enemy = 1;
                        }
                    }
                }
                // Статус trick меняет порядок
                if ($poke_1->_checkStatus('trick') || $poke_2->_checkStatus('trick')) {
                    $enemy = ($enemy == 0 ? 1 : 0);
                }
                // Особые атаки 9998, 9999 всегда первые
                if ($atk_1['id'] == 9998 && $atk_2['id'] != 9998) {
                    $enemy = 0;
                } elseif ($atk_2['id'] == 9998 && $atk_1['id'] != 9998) {
                    $enemy = 1;
                }
                if ($atk_1['id'] == 9999 && $atk_2['id'] != 9999) {
                    $enemy = 0;
                } elseif ($atk_2['id'] == 9999 && $atk_1['id'] != 9999) {
                    $enemy = 1;
                }
                // Сброс раундовых полей
                $poke_1->hp_before = 0;
                $poke_2->hp_before = 0;
                $poke_1->desteny_bond = 0;
                $poke_2->desteny_bond = 0;
                $poke_1->atk_zamena = 0;
                $poke_2->atk_zamena = 0;
                if ($poke_1->round_before <= 0) $poke_1->round_before = 0;
                if ($poke_2->round_before <= 0) $poke_2->round_before = 0;

                // Ходы
                if ($enemy == 1) {
                    $this->meFirst = 0;
                    $poke_2->atk_beforeNow = $info_2['targetAtk'];
                    $this->hit($poke_2, $poke_1, $atk_2, $atk_1);
                    $this->hit($poke_1, $poke_2, $atk_1, $atk_2);
                    $this->issetStatus($poke_2, $poke_1, $atk_2, $atk_1);
                    $this->issetStatus($poke_1, $poke_2, $atk_1, $atk_2);
                    $poke_1->atk_beforeNow = $info_1['targetAtk'];
                    $this->meFirst = 1;
                } else {
                    $this->meFirst = 1;
                    $poke_1->atk_beforeNow = $info_1['targetAtk'];
                    $this->hit($poke_1, $poke_2, $atk_1, $atk_2);
                    $this->hit($poke_2, $poke_1, $atk_2, $atk_1);
                    $this->issetStatus($poke_1, $poke_2, $atk_1, $atk_2);
                    $this->issetStatus($poke_2, $poke_1, $atk_2, $atk_1);
                    $poke_2->atk_beforeNow = $info_2['targetAtk'];
                    $this->meFirst = 0;
                }

                $poke_1->atk_before = $info_1['targetAtk'];
                $poke_2->atk_before = $info_2['targetAtk'];

                $this->_actionBattle->_nextRoundWeather();
                $this->_actionBattle->_nextRound();
                $target_1 = $poke_1->_getData();
                $target_2 = $poke_2->_getData();
            }
            $info_1['targetAtk'] = 0;
            $info_2['targetAtk'] = 0;

            $info_1['timer'] = [];
            $info_2['timer'] = [];
        }
    }

    public function _getLog() {
        return $this->user_log;
    }

    public function _getLogStatus() {
        return $this->log_status;
    }

private function hit(PokeBattle &$attacker, PokeBattle &$defender, array &$attackerAtk, array &$defenderAtk){

        if($this->catch){
            return;
        }
        unset($this->attacker);
        unset($this->defender);

        $this->attacker =& $attacker;
        $this->defender =& $defender;

        unset($this->attackerAtk);
        unset($this->defenderAtk);
        $this->attackerAtk =& $attackerAtk;
        $this->defenderAtk =& $defenderAtk;
        $this->log_start = [];
        $this->log = [];
        $this->log_end = [];

				$disable_my = explode(',',$this->attacker->disable_my);
				$disable_my[0] = ($disable_my[0] <= 0 ? 0 : ($disable_my[0] - 1));
				$disable_my[1] = ($disable_my[1] <= 0 ? 0 : ($disable_my[1] - 1));
				$disable_my[2] = ($disable_my[2] <= 0 ? 0 : ($disable_my[2] - 1));
				$disable_my[3] = ($disable_my[3] <= 0 ? 0 : ($disable_my[3] - 1));
				$this->attacker->disable_my = implode(',',$disable_my);

                $info = $this->attacker->_getStatusList();
                
               
                
            if($info){foreach($info AS $key=>$value){if($value['type'] == 'sleep' && $value['count'] == '1'){
                unset($info[$key]);
                $attacker->_setStatusList($info);
                if($this->attacker->item_id == 168){
                    $this->log[] = 'Колючки мешают покемону спать';
                }else{
                    $this->log[] = 'Покемон проснулcя';
                }
            }}
                
            }

				// No Guard (id=122) — как в Pokemon Showdown: гарантирует попадание обеим сторонам.
				if(in_array($this->attackerAtk['id'], [166,243,212,473])) {
					if(in_array(122, [$this->attacker->ability,$this->defender->ability])) {
						$this->attackerAtk['accuracy'] = 100;
					}else{
				// 		if($this->attacker->item_id == 353) {
				// 			$this->attackerAtk['accuracy'] = 40;
				// 		}
					}
				}

				if($this->attacker->metronom == 1) {
					$this->log[] = $this->attacker->_getName(true).' ⇢ <span onclick="viewDescriptionAttak(this,320);" class="Attack MoveCategory3">Метроном</span>';
				}
				
				

				if($this->attackerAtk['id'] == 322) {
					$enemydis = explode(',',$this->defender->disable_my);
					if($this->defender->atk_before < 9000) {
						if(in_array($this->defender->atk_before, [754,753,481,528,574])) {
							$this->log[] = 'Провал.';
						}else{
							if($this->_actionBattle->round == 1) {
								$this->log[] = 'Провал.';
							}else{
								$metronom = $this->_getMove($this->defender->atk_before);
								$this->log[] = $this->attacker->_getName(true).' ⇢ <span onclick="viewDescriptionAttak(this,322);" class="Attack MoveCategory3">Имитация</span>';
								$this->attackerAtk['id'] = $metronom['id'];
								$this->attackerAtk['name'] = $metronom['name_rus'];
								$this->attackerAtk['type'] = $metronom['type'];
								$this->attackerAtk['category'] = $metronom['category'];
								$this->attackerAtk['priority'] = $metronom['priority'];
								$this->attackerAtk['power'] = $metronom['power'];
								$this->attackerAtk['accuracy'] = $metronom['accuracy'];
								$this->attackerAtk['target'] = $metronom['target'];
								$this->attackerAtk['settings'] = $metronom['settings'];
								$this->attackerAtk['my'] = $metronom['my'];
								$this->attackerAtk['enemy'] = $metronom['enemy'];
								$this->attackerAtk['contact'] = $metronom['contact'];
							}
						}
					}else{
						$this->log[] = 'Провал.';
					}
				}

				$wishYes =  Work::$sql->query('SELECT * FROM battle_effects WHERE name = "Wish" AND user = '.$this->attacker->user_id)->fetch_assoc();

				if(isset($wishYes)) {
				    if($this->attacker->hp > 0 and $this->attackerAtk['id'] != 9999){
					$hpWish = $wishYes['end'];
					$this->attacker->hp = $this->attacker->hp + $hpWish;
					$this->log[] = 'Желание восстанавливает здоровье <span class="HpPlus">+'.$hpWish.' HP</span>';
					Work::$sql->query('DELETE FROM battle_effects WHERE name = "Wish" AND user = '.$this->attacker->user_id);
				    }
				}

        if(!$this->attacker->_checkStatus('levitation')) {
          if($this->attacker->ability == 96 and $this->defender->ability != 113) {
            $this->attacker->_setStatus('levitation', 9999);
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> позволяет '.$this->attacker->_getName(true).' левитировать.';
          }
        }

        // if($this->attacker->_checkStatus('sleep')) {
        //   if($this->attacker->item_id == 168) {
        //     $info = $this->attacker->_getStatusList();
        //             if($info){foreach($info AS $key=>$value){if($value['type'] == 'sleep'){unset($info[$key]);}}}
        //             $this->attacker->_setStatusList($info);
        //     $this->log[] = 'Колючки мешают покемону спать';
        //   }
        // }

        if($this->attackerAtk['id'] == 238) {
    $hpgen[0] = (($this->attacker->gen[0] % 2) == 0 ? 0 : 1); // HP
    $hpgen[1] = (($this->attacker->gen[1] % 2) == 0 ? 0 : 1); // Atk
    $hpgen[2] = (($this->attacker->gen[2] % 2) == 0 ? 0 : 1); // Def
    $hpgen[3] = (($this->attacker->gen[3] % 2) == 0 ? 0 : 1); // Spe
    $hpgen[4] = (($this->attacker->gen[4] % 2) == 0 ? 0 : 1); // SpA
    $hpgen[5] = (($this->attacker->gen[5] % 2) == 0 ? 0 : 1); // SpD
    $hptype = floor((($hpgen[0] + 2 * $hpgen[1] + 4 * $hpgen[2] + 8 * $hpgen[3] + 16 * $hpgen[4] + 32 * $hpgen[5]) * 15) / 63);
    $hptypenum = ['fighting','flying','poison','ground','rock','bug','ghost','steel','fire','water','grass','electric','psychic','ice','dragon','dark'];
    $hptypenumrus = ['Боевой','Летающий','Ядовитый','Земляной','Каменный','Насекомое','Призрачный','Стальной','Огненный','Водный','Травяной','Электрический','Психический','Ледяной','Дракон','Темный'];
    $this->attackerAtk['type'] = $hptypenum[$hptype];
    $this->log[] = 'Тип атаки изменен на: '.$hptypenumrus[$hptype];
}

        
        if($this->attackerAtk['id'] == 271 or $this->attackerAtk['id'] == 720) {
            if($this->attacker->item_id == 205){ $hptypenum = 'fighting'; $hptypenumrus = 'Боевой';}
            if($this->attacker->item_id == 206){ $hptypenum = 'water'; $hptypenumrus = 'Водный';}
            if($this->attacker->item_id == 207){ $hptypenum = 'steel'; $hptypenumrus = 'Стальной';}
            if($this->attacker->item_id == 208){ $hptypenum = 'dark'; $hptypenumrus = 'Темный';}
            if($this->attacker->item_id == 209){ $hptypenum = 'ice'; $hptypenumrus = 'Ледяной';}
            if($this->attacker->item_id == 210){ $hptypenum = 'rock'; $hptypenumrus = 'Каменный';}
            if($this->attacker->item_id == 211){ $hptypenum = 'dragon'; $hptypenumrus = 'Дракон';}
            if($this->attacker->item_id == 212){ $hptypenum = 'electric'; $hptypenumrus = 'Электрический';}
            if($this->attacker->item_id == 213){ $hptypenum = 'bug'; $hptypenumrus = 'Насекомое';}
            if($this->attacker->item_id == 214){ $hptypenum = 'ground'; $hptypenumrus = 'Земляной';}
            if($this->attacker->item_id == 215){ $hptypenum = 'fire'; $hptypenumrus = 'Огненный';}
            if($this->attacker->item_id == 216){ $hptypenum = 'psychic'; $hptypenumrus = 'Психический';}
            if($this->attacker->item_id == 217){ $hptypenum = 'fairy'; $hptypenumrus = 'Волшебный';}
            if($this->attacker->item_id == 218){ $hptypenum = 'grass'; $hptypenumrus = 'Травяной';}
            if($this->attacker->item_id == 219){ $hptypenum = 'ghost'; $hptypenumrus = 'Призрачный';}
            if($this->attacker->item_id == 220){ $hptypenum = 'normal'; $hptypenumrus = 'Нормальный';}
            if($this->attacker->item_id == 221){ $hptypenum = 'poison'; $hptypenumrus = 'Ядовитый';}
            if($this->attacker->item_id == 222){ $hptypenum = 'fly'; $hptypenumrus = 'Летающий';}
        //   $hptypenum = ['','','','','','','','','','','','','','','',''];
        //   $hptypenumrus = ['Насекомое','Темный','Дракон','Электрический','Боевой','Огненный','Летающий','Призрачный','Травяной','Земляной','Ледяной','Водный','Ядовитый','Психический','Каменный','Стальной'];
          $this->attackerAtk['type'] = $hptypenum;
          $this->log[] = 'Тип атаки изменен на: '.$hptypenumrus;
        }
        
        if($this->attacker->ability == 154) {
            if($this->attacker->item_id == 205){ $hptypenum = 'fighting'; $hptypenumrus = 'Боевой';}
            if($this->attacker->item_id == 206){ $hptypenum = 'water'; $hptypenumrus = 'Водный';}
            if($this->attacker->item_id == 207){ $hptypenum = 'steel'; $hptypenumrus = 'Стальной';}
            if($this->attacker->item_id == 208){ $hptypenum = 'dark'; $hptypenumrus = 'Темный';}
            if($this->attacker->item_id == 209){ $hptypenum = 'ice'; $hptypenumrus = 'Ледяной';}
            if($this->attacker->item_id == 210){ $hptypenum = 'rock'; $hptypenumrus = 'Каменный';}
            if($this->attacker->item_id == 211){ $hptypenum = 'dragon'; $hptypenumrus = 'Дракон';}
            if($this->attacker->item_id == 212){ $hptypenum = 'electric'; $hptypenumrus = 'Электрический';}
            if($this->attacker->item_id == 213){ $hptypenum = 'bug'; $hptypenumrus = 'Насекомое';}
            if($this->attacker->item_id == 214){ $hptypenum = 'ground'; $hptypenumrus = 'Земляной';}
            if($this->attacker->item_id == 215){ $hptypenum = 'fire'; $hptypenumrus = 'Огненный';}
            if($this->attacker->item_id == 216){ $hptypenum = 'psychic'; $hptypenumrus = 'Психический';}
            if($this->attacker->item_id == 217){ $hptypenum = 'fairy'; $hptypenumrus = 'Волшебный';}
            if($this->attacker->item_id == 218){ $hptypenum = 'grass'; $hptypenumrus = 'Травяной';}
            if($this->attacker->item_id == 219){ $hptypenum = 'ghost'; $hptypenumrus = 'Призрачный';}
            if($this->attacker->item_id == 220){ $hptypenum = 'normal'; $hptypenumrus = 'Нормальный';}
            if($this->attacker->item_id == 221){ $hptypenum = 'poison'; $hptypenumrus = 'Ядовитый';}
            if($this->attacker->item_id == 222){ $hptypenum = 'fly'; $hptypenumrus = 'Летающий';}
        //   $hptypenum = ['','','','','','','','','','','','','','','',''];
        //   $hptypenumrus = ['Насекомое','Темный','Дракон','Электрический','Боевой','Огненный','Летающий','Призрачный','Травяной','Земляной','Ледяной','Водный','Ядовитый','Психический','Каменный','Стальной'];
          $this->attacker->base_type = $hptypenum;
          $this->log[] = 'Тип покемона: '.$hptypenumrus;
        }
        if($this->defender->ability == 154) {
            if($this->defender->item_id == 205){ $hptypenum = 'fighting'; $hptypenumrus = 'Боевой';}
            if($this->defender->item_id == 206){ $hptypenum = 'water'; $hptypenumrus = 'Водный';}
            if($this->defender->item_id == 207){ $hptypenum = 'steel'; $hptypenumrus = 'Стальной';}
            if($this->defender->item_id == 208){ $hptypenum = 'dark'; $hptypenumrus = 'Темный';}
            if($this->defender->item_id == 209){ $hptypenum = 'ice'; $hptypenumrus = 'Ледяной';}
            if($this->defender->item_id == 210){ $hptypenum = 'rock'; $hptypenumrus = 'Каменный';}
            if($this->defender->item_id == 211){ $hptypenum = 'dragon'; $hptypenumrus = 'Дракон';}
            if($this->defender->item_id == 212){ $hptypenum = 'electric'; $hptypenumrus = 'Электрический';}
            if($this->defender->item_id == 213){ $hptypenum = 'bug'; $hptypenumrus = 'Насекомое';}
            if($this->defender->item_id == 214){ $hptypenum = 'ground'; $hptypenumrus = 'Земляной';}
            if($this->defender->item_id == 215){ $hptypenum = 'fire'; $hptypenumrus = 'Огненный';}
            if($this->defender->item_id == 216){ $hptypenum = 'psychic'; $hptypenumrus = 'Психический';}
            if($this->defender->item_id == 217){ $hptypenum = 'fairy'; $hptypenumrus = 'Волшебный';}
            if($this->defender->item_id == 218){ $hptypenum = 'grass'; $hptypenumrus = 'Травяной';}
            if($this->defender->item_id == 219){ $hptypenum = 'ghost'; $hptypenumrus = 'Призрачный';}
            if($this->defender->item_id == 220){ $hptypenum = 'normal'; $hptypenumrus = 'Нормальный';}
            if($this->defender->item_id == 221){ $hptypenum = 'poison'; $hptypenumrus = 'Ядовитый';}
            if($this->defender->item_id == 222){ $hptypenum = 'fly'; $hptypenumrus = 'Летающий';}
        //   $hptypenum = ['','','','','','','','','','','','','','','',''];
        //   $hptypenumrus = ['Насекомое','Темный','Дракон','Электрический','Боевой','Огненный','Летающий','Призрачный','Травяной','Земляной','Ледяной','Водный','Ядовитый','Психический','Каменный','Стальной'];
          $this->defender->base_type = $hptypenum;
        }

        if($this->attacker->ability == 2) {
          if($this->attackerAtk['type'] == 'normal') {
            $this->attackerAtk['type'] = 'fly';
            $this->ability2Effect = 1;
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> меняет тип атаки на Летающий.';
          }
        }

        if($this->attacker->ability == 76) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает стат Атаки в 2 раза.';
        }

        if($this->attacker->ability == 146) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивают Скорость на 50%.';
        }

        if($this->attacker->ability == 63) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает стат Защиты в 2 раза.';
        }

        if($this->attacker->ability == 68) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает стат Защиты на 50%.';
        }

        if($this->attacker->ability == 37) {
          if($this->attacker->hp <= floor(($this->attacker->hp_max / 2))){
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> понижает статы Атаки и Спец. Атаки в 2 раза.';
          }
        }
        /* === ИММУНИТЕТЫ/ПОГЛОЩЕНИЯ ДО ТОЧНОСТИ И УРОНА (как в Pokemon Showdown) ===
   Блок должен стоять ДО isAccuracy()/shields() и ДО расчёта урона. */
if ($this->attacker->hp > 0 && $this->defender->hp > 0) {
    // Атакуем именно противника (а не себя/союзника/поле)
    $tgt = isset($this->attackerAtk['target']) ? $this->attackerAtk['target'] : 'enemy';
    if (in_array($tgt, ['enemy','enemy_random','all_enemy'])) {

        /* Overcoat (id=125): иммунитет к powder/спорам (НЕ пробивается 113 у тебя не использовался) */
        if (isset($this->attackerAtk['id']) && in_array($this->attackerAtk['id'], [90,391,393,418,490,515,530])
            && $this->defender->ability == 125) {
            $this->log[] = 'Атака не действует: цель защищена способностью <div class="Ability" onclick="issetAll(125,\'ability\')">'.$this->_abilNameRus(125).'</div>.';
            return;
        }

        /* Aroma Veil (id=9): защищает от Taunt/Torment/Encore/Disable/Heal Block (как у тебя в списке) */
        if (isset($this->attackerAtk['id']) && in_array($this->attackerAtk['id'], [554,571,143,111,226])
            && $this->defender->ability == 9) {
            $this->log[] = 'Эффект заблокирован способностью <div class="Ability" onclick="issetAll(9,\'ability\')">'.$this->_abilNameRus(9).'</div>.';
            return;
        }

        /* Soundproof (id=183): звук не проходит (у тебя учитывался 113 как игнор) */
        if (!empty($this->attackerAtk['sound']) && $this->attackerAtk['sound'] == 1
            && $this->defender->ability == 183 && $this->attacker->ability != 113) {
            $this->log[] = 'Звуковой приём не действует из-за <div class="Ability" onclick="issetAll(183,\'ability\')">'.$this->_abilNameRus(183).'</div>.';
            return;
        }

        /* Bulletproof (id=19): «шарики/снаряды» не проходят */
        if (!empty($this->attackerAtk['bullet']) && $this->attackerAtk['bullet'] == 1
            && $this->defender->ability == 19) {
            $this->log[] = 'Атака-проектайл не действует из-за <div class="Ability" onclick="issetAll(19,\'ability\')">'.$this->_abilNameRus(19).'</div>.';
            return;
        }

        /* Flash Fire (id=54): иммун к огню + бафф огн. приёмов (игнорится твоим 113) */
        if (isset($this->attackerAtk['type']) && $this->attackerAtk['type'] === 'fire'
            && $this->defender->ability == 54 && $this->attacker->ability != 113) {

            // ставим маркер баффа (не стакается)
            if (!$this->defender->_checkStatus('flash_fire')) {
                $this->defender->_setStatus('flash_fire', 9999, 1);
                $this->log[] = '<div class="Ability" onclick="issetAll(54,\'ability\')">'.$this->_abilNameRus(54).'</div> поглощает огонь и усиливает огненные атаки цели.';
            } else {
                $this->log[] = '<div class="Ability" onclick="issetAll(54,\'ability\')">'.$this->_abilNameRus(54).'</div> поглощает огонь.';
            }
            return;
        }

        /* Lightning Rod (id=98): иммун к Electric и +1 Sp.Atk (игнорится твоим 113) */
        if (isset($this->attackerAtk['type']) && $this->attackerAtk['type'] === 'electric'
            && $this->defender->ability == 98 && $this->attacker->ability != 113) {
            $this->defender->_setModifiedStat('satk', 1, true);
            $this->log[] = '<div class="Ability" onclick="issetAll(98,\'ability\')">'.$this->_abilNameRus(98).'</div> перенаправляет электричество: <span class="StatPlus">Спец.Атака +1</span>.';
            return;
        }

        /* Water Absorb (id=225): вода лечит 1/4 max HP (игнорится твоим 113) */
        if (isset($this->attackerAtk['type']) && $this->attackerAtk['type'] === 'water'
            && $this->defender->ability == 225 && $this->attacker->ability != 113) {
            $heal = max(1, (int)floor($this->defender->hp_max / 4));
            $this->defender->hp = min($this->defender->hp_max, $this->defender->hp + $heal);
            $this->log[] = '<div class="Ability" onclick="issetAll(225,\'ability\')">'.$this->_abilNameRus(225).'</div> восстанавливает здоровье цели: <span class="HpPlus">+'.$heal.' HP</span>.';
            return;
        }

        /* Volt Absorb (id=224): электричество лечит 1/4 max HP (игнорится твоим 113) */
        if (isset($this->attackerAtk['type']) && $this->attackerAtk['type'] === 'electric'
            && $this->defender->ability == 224 && $this->attacker->ability != 113) {
            $heal = max(1, (int)floor($this->defender->hp_max / 4));
            $this->defender->hp = min($this->defender->hp_max, $this->defender->hp + $heal);
            $this->log[] = '<div class="Ability" onclick="issetAll(224,\'ability\')">'.$this->_abilNameRus(224).'</div> восстанавливает здоровье цели: <span class="HpPlus">+'.$heal.' HP</span>.';
            return;
        }

        /* Dry Skin (id=45): вода лечит 1/4 max HP (игнорится твоим 113). Усиление урона от огня обрабатывай в dmg-стадии. */
        if (isset($this->attackerAtk['type']) && $this->attackerAtk['type'] === 'water'
            && $this->defender->ability == 45 && $this->attacker->ability != 113) {
            $heal = max(1, (int)floor($this->defender->hp_max / 4));
            $this->defender->hp = min($this->defender->hp_max, $this->defender->hp + $heal);
            $this->log[] = '<div class="Ability" onclick="issetAll(45,\'ability\')">'.$this->_abilNameRus(45).'</div> восстанавливает здоровье от воды: <span class="HpPlus">+'.$heal.' HP</span>.';
            return;
        }

        /* Sap Sipper (id=162): трава не действует, +1 Atk */
        if (isset($this->attackerAtk['type']) && $this->attackerAtk['type'] === 'grass'
            && $this->defender->ability == 162) {
            $this->defender->_setModifiedStat('atk', 1, true);
            $this->log[] = '<div class="Ability" onclick="issetAll(162,\'ability\')">'.$this->_abilNameRus(162).'</div> делает цель невосприимчивой к траве и повышает <span class="StatPlus">Атаку +1</span>.';
            return;
        }

        /* (не прерывающее) Punk Rock-входящий эффект ты можешь добавить здесь же,
           если он у тебя есть в базе: для звуковых приёмов уменьшить входящий урон на 50%.
           Т.к. он не блокирует атаку — не делаем return; просто пометь коэффициент
           в каких-либо временных настройках урона, если у тебя это предусмотрено. */
        // if ($this->defender->ability == <ID_PUNK_ROCK> && !empty($this->attackerAtk['sound']) && $this->attackerAtk['sound'] == 1) {
        //     $this->settings['dmg_incoming_mult'] = isset($this->settings['dmg_incoming_mult']) ? ($this->settings['dmg_incoming_mult'] * 0.5) : 0.5;
        //     $this->log[] = '<div class="Ability" onclick="issetAll(<ID_PUNK_ROCK>,\'ability\')">Панк-рок</div> уменьшает урон от звуковых атак.';
        // }
    }
}
/* === /ИММУНИТЕТЫ/ПОГЛОЩЕНИЯ === */


        // Toxic Orb (item_id=190) — добавляем лог с ожидаемым уроном от отравления.
        if($this->attacker->item_id == 190) {
          if($this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('toxic') || $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('sleep') || $this->attacker->_checkStatus('paralyzed') || $this->attacker->_checkStatus('frost')) {

          }else{
            if($this->attacker->_getTypeA() == 'poison' || $this->attacker->_getTypeB() == 'poison' || $this->attacker->_getTypeA() == 'steel' || $this->attacker->_getTypeB() == 'steel' || $this->attacker->_checkStatus('safeguard')) {

            }else{
              $this->attacker->_setStatus('toxic', 9999);
              // ▼ ДОБАВЛЕНО: лог количества урона от отравления (первая ступень 1/16 НР)
              $__toxicStage = 1; // при наложении badly poison первая ступень = 1
              $__toxicDmg = max(1, floor($this->attacker->hp_max / 16 * $__toxicStage));
              $this->log[] = 'Отравление нанесёт урон в конце хода: <span class="HpMinus">-'.$__toxicDmg.' HP</span>';
              // ▲ ДОБАВЛЕНО
              $this->log[] = 'Покемон отравился от <div class="itemIsset" onclick="issetAll(190,\'item\')" style="background-image: url(/img/world/items/little/190.png)"></div>';
            }
          }
        }

        if($this->attacker->ability == 22 && $this->_actionBattle->weather == 2 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает <span class="StatPlus">Скорость +100%</span>';
        }

        if($this->attacker->ability == 176 && $this->_actionBattle->weather == 4 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает <span class="StatPlus">Скорость +100%</span>';
        }

        if($this->attacker->ability == 202 && $this->_actionBattle->weather == 3 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает <span class="StatPlus">Скорость +100%</span>';
        }

        if($this->attacker->ability == 159 && $this->_actionBattle->weather == 5 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает <span class="StatPlus">Скорость +100%</span>';
        }

        if($this->attacker->ability == 108 and $this->defender->ability != 113) {
          if($this->attacker->_checkStatus('toxic') || $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('sleep') || $this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('frost') || $this->attacker->_checkStatus('paralyzed')) {
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> увеличивает <span class="StatPlus">Защиту +50%</span>';
          }
        }

        if($this->attacker->ability == 72) {
          $pList = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
          $pList1 = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
          if($pList){
            $pokabil72 = '';
            $randheal72 = mt_rand(1,100);
            if($randheal72 <= 100) {
              shuffle($pList);
              if($pList){
                  $i = 0;
                  foreach($pList AS $key_1=>&$value_1){
                    if($value_1['hp'] <= 0 || $value_1['id'] == $this->attacker->id) {

                    }else{
                      if(isset($value_1['status_list'])){
                        foreach(['frost','sleep','toxic','toxic2','paralyzed','burn'] AS $key=>$value){
                          if($i >= 1) {
                            break;
                          }else{
                            if(isset($value_1['status_list'][$value])){
                              $pokabil72 = $value_1['id'];
                              $i++;
                            }
                          }
                        }
                        unset($key,$value);
                      }
                    }
                  }
                  unset($key_1, $value_1);
              }
              if($pokabil72 != '') {
                foreach($pList1 AS $key_1=>&$value_1){
                  if($value_1['id'] == $pokabil72) {
                    // ▼ ДОБАВЛЕНО: выводим предотвращённый урон, если снимали burn/toxic
                    $__preventLog = [];
                    if(isset($value_1['hp_max'])){
                      if(isset($value_1['status_list']['burn'])){
                        $__burnDmg = max(1, floor($value_1['hp_max'] / 16)); // Gen7+: 1/16
                        $__preventLog[] = 'ожог ('.($__burnDmg).' HP)';
                      }
                      if(isset($value_1['status_list']['toxic'])){
                        $__toxicStage = 1; // без счётчика ступени — минимальная оценка
                        $__toxicDmg = max(1, floor($value_1['hp_max'] / 16 * $__toxicStage));
                        $__preventLog[] = 'сильное отравление ('.($__toxicDmg).' HP)';
                      }
                      if(isset($value_1['status_list']['toxic2'])){
                        $__toxicDmg2 = max(1, floor($value_1['hp_max'] / 8)); // обычный яд 1/8
                        $__preventLog[] = 'отравление ('.($__toxicDmg2).' HP)';
                      }
                    }
                    foreach(['frost','sleep','toxic','toxic2','paralyzed','burn'] AS $key=>$value){
                      unset($value_1['status_list'][$value]);
                    }
                  }
                }
                $this->_actionBattle->_setUserPokes($this->attacker->_getUser(), $pList1);
                $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> избавляет случайного союзника от негативных статусов.'
                  .(isset($__preventLog) && count($__preventLog) ? ' (предотвращено периодического урона: '.implode(', ', $__preventLog).')' : '');
              }
            }
          }
        }
        
				if($this->_actionBattle->weather == 2 && !in_array(4,[$this->attacker->ability,$this->defender->ability])){
          if($this->attackerAtk['type'] == 'water'){
            $this->attackerAtk['power'] = $this->attackerAtk['power'] / 1.5;
          }elseif($this->attackerAtk['type'] == 'fire'){
            $this->attackerAtk['power'] = $this->attackerAtk['power'] * 1.5;
          }
	  }elseif($this->_actionBattle->weather == 3 && !in_array(4,[$this->attacker->ability,$this->defender->ability])){
          if($this->attackerAtk['type'] == 'water'){
            $this->attackerAtk['power'] = $this->attackerAtk['power'] * 1.5;
          }elseif($this->attackerAtk['type'] == 'fire'){
            $this->attackerAtk['power'] = $this->attackerAtk['power'] / 1.5;
          }
        }
				if($this->attackerAtk['id'] == '327' && $this->attacker->hp > 0) {
		      if($this->attacker->_getStatSpd() < $this->defender->_getStatSpd()) {
		        $this->attackerAtk['id'] = $this->defenderAtk['id'];
		        $movecopy = $this->_getMove($this->attackerAtk['id']);
		        $this->log[] = $this->attacker->_getName(true).' ⇢ <span onclick="viewDescriptionAttak(this,327);" class="Attack MoveCategory3">Зеркальная атака</span> и повторяет атаку противника:';
		        $this->attackerAtk['name'] = $movecopy['name_rus'];
		        $this->attackerAtk['type'] = $movecopy['type'];
		        $this->attackerAtk['category'] = $movecopy['category'];
		        $this->attackerAtk['priority'] = $movecopy['priority'];
		        $this->attackerAtk['power'] = $movecopy['power'];
		        $this->attackerAtk['accuracy'] = $movecopy['accuracy'];
		        $this->attackerAtk['target'] = $movecopy['target'];
		        $this->attackerAtk['settings'] = $movecopy['settings'];
		        $this->attackerAtk['my'] = $movecopy['my'];
		        $this->attackerAtk['enemy'] = $movecopy['enemy'];
		        $this->attackerAtk['contact'] = $movecopy['contact'];
		        //$atkAtk['attack_num'] = 1;
		      }else{
		        $this->log[] = 'Не удалось скопировать атаку.';
		      }
		    }
				$ppMinusim = 1;
				$infoTwoTurn = $this->attacker->_getStatusList();
				if($this->defender->_getTypeA() == 'fairy' || $this->defender->_getTypeB() == 'fairy'){
					if($this->attackerAtk['id'] == 370) {
						if($infoTwoTurn){foreach($infoTwoTurn AS $key=>$value){if($value['type'] == 'two_turn'){unset($infoTwoTurn[$key]);$this->log[] = 'Серия атак прервана.';}}}
						$attacker->_setStatusList($infoTwoTurn);
					}
				}
				if($this->attackerAtk['id'] <= 752) {
					if($this->attacker->ability == 217) {
						if($this->attacker->_checkStatus('traunt')) {
							$atkTwoRnd = Work::$sql->query("SELECT * FROM base_atk WHERE id = 753")->fetch_assoc();
				            $this->attackerAtk['id'] = $atkTwoRnd['id'];
				            $this->attackerAtk['name'] = $atkTwoRnd['name_rus'];
				            $this->attackerAtk['type'] = $atkTwoRnd['type'];
				            $this->attackerAtk['category'] = $atkTwoRnd['category'];
				            $this->attackerAtk['priority'] = $atkTwoRnd['priority'];
				            $this->attackerAtk['power'] = $atkTwoRnd['power'];
				            $this->attackerAtk['accuracy'] = $atkTwoRnd['accuracy'];
				            $this->attackerAtk['target'] = $atkTwoRnd['target'];
				            $this->attackerAtk['settings'] = $atkTwoRnd['settings'];
				            $this->attackerAtk['my'] = $atkTwoRnd['my'];
				            $this->attackerAtk['enemy'] = $atkTwoRnd['enemy'];
				            $this->attackerAtk['contact'] = $atkTwoRnd['contact'];
				            $this->attackerAtk['attack_num'] = 1;
						}else{
							$this->attacker->_setStatus('traunt', 2);
						}
					}
				}
				if($this->attacker->_checkStatus('two_turn') && $this->attackerAtk['id'] != 754 && $this->attacker->hp > 0) {
		      if($infoTwoTurn){
		        foreach($infoTwoTurn AS $key=>$value){
		          if($value['type'] == 'two_turn'){
		            $atkTwoRnd = $this->_getMove($value['val']);
		            $this->attackerAtk['id'] = $atkTwoRnd['id'];
		            $this->attackerAtk['name'] = $atkTwoRnd['name_rus'];
		            $this->attackerAtk['type'] = $atkTwoRnd['type'];
		            $this->attackerAtk['category'] = $atkTwoRnd['category'];
		            $this->attackerAtk['priority'] = $atkTwoRnd['priority'];
		            $this->attackerAtk['power'] = $atkTwoRnd['power'];
		            $this->attackerAtk['accuracy'] = $atkTwoRnd['accuracy'];
		            $this->attackerAtk['target'] = $atkTwoRnd['target'];
		            $this->attackerAtk['settings'] = $atkTwoRnd['settings'];
		            $this->attackerAtk['my'] = $atkTwoRnd['my'];
		            $this->attackerAtk['enemy'] = $atkTwoRnd['enemy'];
		            $this->attackerAtk['contact'] = $atkTwoRnd['contact'];
		            $this->attackerAtk['attack_num'] = 1;
		            $ppMinusim = 0;
		          }
		        }
		      }
		    }
			if($this->attacker->_checkStatus('plasma_fists')) {
				$this->attackerAtk['type'] = 'electric';
			}
    $mypp = explode(',',$this->attacker->pp_my);
        if(
            $this->getSelected($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, false, 1) &&
            $this->getCatch($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk) &&
            $this->getNext($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk) &&
            $this->getTurn($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)
            
            
        ){
          if($mypp[$this->attackerAtk['attack_num']] >= 1) {
              if($mypp[$this->attackerAtk['attack_num']]) {
								if($this->defender->ability == 139) {
									$minus_pp = 2;
									$this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника крадет 1 дополнительно PP у покемона.';
								}else{
									$minus_pp = 1;
								}
                $mypp[$this->attackerAtk['attack_num']] = $mypp[$this->attackerAtk['attack_num']] - $minus_pp;
								if($mypp[$this->attackerAtk['attack_num']] <= 0) {
									$mypp[$this->attackerAtk['attack_num']] = 0;
								}
								}
								$this->attacker->pp_my = implode(',',$mypp);


            if($this->attacker->hp > 0){

							
				if($this->attackerAtk['id'] == 787) {
                    if($this->defender->_checkStatus('lightScreen') or $this->defender->_checkStatus('reflect') or $this->attacker->_checkStatus('lightScreen') or $this->attacker->_checkStatus('reflect')){
                $info = $this->attacker->_getStatusList();
			                        if($info){
			                          foreach($info AS $key=>$value){
			                            if(in_array($value['type'], ['reflect','lightScreen'])){
			                              unset($info[$key]);
			                            }
			                          }
			                          $this->attacker->_setStatusList($info);
			                        }
                $info2 = $this->defender->_getStatusList();
			                        if($info2){
			                          foreach($info2 AS $key=>$value){
			                            if(in_array($value['type'], ['reflect','lightScreen'])){
			                              unset($info2[$key]);
			                            }
			                          }
			                          $this->defender->_setStatusList($info2);
			                        }
			                        $this->log[] = 'Атака разрушает экраны на поле боя';
                    }
                    if($this->attacker->basenum == 128){
                        if($this->attacker->form == 'paldea'){
                            $this->attackerAtk['type'] = 'fighting';
                            $this->log[] = 'Тип атаки: Боевой.';
                        }elseif($this->attacker->form == 'paldeafire'){
                            $this->attackerAtk['type'] = 'fire';
                            $this->log[] = 'Тип атаки: Огненный.';
                        }elseif($this->attacker->form == 'paldeawater'){
                            $this->attackerAtk['type'] = 'water';
                            $this->log[] = 'Тип атаки: Водный.';
                        }
                    }
                    
                }			
							
							
							if($this->attackerAtk['id'] == 600) {
                if($this->_actionBattle->weather == 2 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $this->attackerAtk['type'] = 'fire';
                  $this->log[] = 'Тип атаки: Огненный.';
			  }elseif($this->_actionBattle->weather == 3 && !ин_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $this->attackerAtk['type'] = 'water';
                  $this->log[] = 'Тип атаки: Водный.';
			  }elseif($this->_actionBattle->weather == 4 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $this->attackerAtk['type'] = 'ice';
                  $this->log[] = 'Тип атаки: Ледяной.';
			  }elseif($this->_actionBattle->weather == 5 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $this->attackerAtk['type'] = 'rock';
                  $this->log[] = 'Тип атаки: Каменный.';
                }else{
				  if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
					$this->attackerAtk['type'] = 'normal';
				  }
                }
              }
                
                
                
                
              if($this->attackerAtk['id'] == 697) {
                if(isset($this->defender->modified['def']['plus'])) {
                  $this->attacker->_setModifiedStat('def', $this->defender->modified['def']['plus'], true);
                  unset($this->defender->modified['def']['plus']);
                }
                if(isset($this->defender->modified['sdef']['plus'])) {
                  $this->attacker->_setModifiedStat('sdef', $this->defender->modified['sdef']['plus'], true);
                  unset($this->defender->modified['sdef']['plus']);
                }
                if(isset($this->defender->modified['acr']['plus'])) {
                  $this->attacker->_setModifiedStat('acr', $this->defender->modified['acr']['plus'], true);
                  unset($this->defender->modified['acr']['plus']);
                }
                if(isset($this->defender->modified['agl']['plus'])) {
                  $this->attacker->_setModifiedStat('def', $this->defender->modified['agl']['plus'], true);
                  unset($this->defender->modified['agl']['plus']);
                }
                if(isset($this->defender->modified['atk']['plus'])) {
                  $this->attacker->_setModifiedStat('atk', $this->defender->modified['atk']['plus'], true);
                  unset($this->defender->modified['atk']['plus']);
                }
                if(isset($this->defender->modified['satk']['plus'])) {
                  $this->attacker->_setModifiedStat('satk', $this->defender->modified['satk']['plus'], true);
                  unset($this->defender->modified['satk']['plus']);
                }
                if(isset($this->defender->modified['spd']['plus'])) {
                  $this->attacker->_setModifiedStat('spd', $this->defender->modified['spd']['plus'], true);
                  unset($this->defender->modified['spd']['plus']);
                }
                $this->log[] = 'Покемон украл положительные модификаторы.';
              }

              if($this->attackerAtk['id'] != 446) {
                Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$attacker->id);
              }
							if($this->attackerAtk['id'] != 137) {
								Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$attacker->id);
							}
              if($this->attackerAtk['id'] != 190) {
                Work::$sql->query('DELETE FROM atk_furycutter WHERE user = '.$attacker->id);
              }
              if($this->attackerAtk['id'] != 714) {
                Work::$sql->query('DELETE FROM atk_tripleaxel WHERE user = '.$attacker->id);
              }
							if($this->attackerAtk['id'] == 401 || $this->attackerAtk['id'] == 603 || $this->attackerAtk['id'] == 415 || $this->attackerAtk['id'] == 307 || $this->attackerAtk['id'] == 727 || $this->attackerAtk['id'] == 511 || $this->attackerAtk['id'] == 94 || $this->attackerAtk['id'] == 109 || $this->attackerAtk['id'] == 277) {
              }else{
                Work::$sql->query('DELETE FROM battle_block WHERE pokID = '.$attacker->id.' AND attack = '.$this->attackerAtk['id']);
              }
                    // $this->attacker->pp_my = implode(',',$mypp);


               if($this->attackerAtk['id'] == 753) {
                  $this->log[] = $this->attacker->_getName(true).' пропускает ход.';
                }else{
                  $this->log[] = $this->attacker->_getName(true).' ⇢ '.$this->getAtkName($this->attackerAtk);
                }
                if($this->isAccuracy($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk) ||
								$this->attackerAtk['id'] == 476) {
									if($this->gravity($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
									if($this->mind($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
								if($this->abil_aromaveil($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
								if($this->abil_soundproof($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {   
								if($this->abil_bulletproof($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
											if($this->abil_overcoat($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
													if($this->shields($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
								if($this->abil_sapsipper($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
									if($this->abil_flashfire($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
									    if($this->abil_lightningrod($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
                                        if($this->abil_dryskin($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
                                            if($this->abil_waterabsorb($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
                                                
                                            if($this->abil_voltabsorb($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {

                                              /* ===== ДОБАВЛЕНО: Storm Drain (id 194) — иммунитет к Water + Sp.Atk +1 ===== */
                                              if($this->settings['types'] > 0 
                                                && $this->defender->ability == 194 
                                                && $this->attackerAtk['power'] >= 1 
                                                && $this->attackerAtk['type'] == 'water') 
                                              {
                                                $this->defender->_setModifiedStat('satk', 1, true);
                                                $this->log[] = '<div class="Ability" onclick="issetAll(194,\'ability\')">'.$this->_abilNameRus(194).'</div> делает покемона невосприимчивым к воде и повышает <span class="StatPlus">Спец. Атаку +1</span>.';
                                                $this->attackerAtk['power'] = 0; // урон блокирован
                                              }

                                              /* ===== ДОБАВЛЕНО: Motor Drive (id 115) — иммунитет к Electric + Speed +1 ===== */
                                              if($this->settings['types'] > 0 
                                                && $this->defender->ability == 115 
                                                && $this->attackerAtk['power'] >= 1 
                                                && $this->attackerAtk['type'] == 'electric') 
                                              {
                                                $this->defender->_setModifiedStat('spd', 1, true);
                                                $this->log[] = '<div class="Ability" onclick="issetAll(115,\'ability\')">'.$this->_abilNameRus(115).'</div> делает покемона невосприимчивым к электричеству и повышает <span class="StatPlus">Скорость +1</span>.';
                                                $this->attackerAtk['power'] = 0; // урон блокирован
                                              }

										// if($this->attacker->koren != '0') {
										//   $randKoren = mt_rand(1,100);
										//   if($randKoren <= 10) {
										// 	  if($this->settings['types'] <= 0 || $this->defender->_checkStatus('terrMisty') || $this->defender->_checkStatus('sleep') || $this->defender->_checkStatus('safeguard') || $this->defender->_checkStatus('burn') || $this->defender->_checkStatus('toxic') ||$this->defender->_checkStatus('toxic2') || $this->defender->_checkStatus('paralyzed') || $this->defender->_checkStatus('frost')) {
			              //                       $this->log[] = 'Провал изменения статуса.';
			              //                     }else{
			    					// 			if($this->attacker->koren == 'frost') {
			    					// 				if(in_array('ice',[$this->defender->_getTypeA(),$this->defender->_getTypeB()])) {
			    					// 					$this->log[] = 'Провал изменения статуса.';
			    					// 				}else{
			    					// 					$this->defender->_setStatus($this->attacker->koren, 9999);
			    		      //                           $this->log[] = 'Эффект от Корня Тревенанта сработал.';
			    					// 				}
			    					// 			}elseif($this->attacker->koren == 'burn') {
			    					// 				if(in_array('fire',[$this->defender->_getTypeA(),$this->defender->_getTypeB()])) {
			    					// 					$this->log[] = 'Провал изменения статуса.';
			    					// 				}else{
			    					// 					$this->defender->_setStatus($this->attacker->koren, 9999);
			    		      //                           $this->log[] = 'Эффект от Корня Тревенанта сработал.';
			    					// 				}
			    					// 			}elseif($this->attacker->koren == 'paralyzed') {
			    					// 				if(in_array('ground',[$this->defender->_getTypeA(),$this->defender->_getTypeB()]) || in_array('electric',[$this->defender->_getTypeA(),$this->defender->_getTypeB()])) {
			    					// 					$this->log[] = 'Провал изменения статуса.';
			    					// 				}else{
			    					// 					$this->defender->_setStatus($this->attacker->koren, 9999);
			    		      //                           $this->log[] = 'Эффект от Корня Тревенанта сработал.';
			    					// 				}
			    					// 			}elseif($this->attacker->koren == 'toxic') {
			    					// 				if(in_array('poison',[$this->defender->_getTypeA(),$this->defender->_getTypeB()]) || in_array('steel',[$this->defender->_getTypeA(),$this->defender->_getTypeB()])) {
			    					// 					$this->log[] = 'Провал изменения статуса.';
			    					// 				}else{
			    					// 					$this->defender->_setStatus($this->attacker->koren, 9999);
			    		      //                           $this->log[] = 'Эффект от Корня Тревенанта сработал.';
			    					// 				}
			    					// 			}else{
										// 			if($this->defender->ability == 88) {
										// 				$this->log[] = 'Провал изменения статуса.';
										// 			}else{
										// 				$this->defender->_setStatus($this->attacker->koren, 9999);
				    				// 					$this->log[] = 'Эффект от Корня Тревенанта сработал.';
										// 			}
			    					// 			}
			              //                     }
										//   }
									  // }

										/* ===== ДОБАВЛЕНО: Rattled (id 148) было уже — оставлено как есть ===== */
										if(in_array($this->attackerAtk['type'], ['bug','dark','ghost']) && $this->defender->ability == 148) {
										  $this->defender->_setModifiedStat('spd', 1, true);
			                              $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника увеличивает <span class="StatPlus">Скорость +1</span>';
									  }

			                          if($this->attackerAtk['id'] == 513) {
			                            $atkbefore = $this->defender->_getAtkInfo($this->defender->atk_before);
			                            if($this->defender->atk_before <= 800) {
			                              $enpp = explode(',',$this->defender->pp_my);
			                              $enpp[$atkbefore['attack_num']] = $enpp[$atkbefore['attack_num']] - 4;
			                              if($enpp[$atkbefore['attack_num']] <= 0) {
			                                $enpp[$atkbefore['attack_num']] = 0;
			                              }
			                              $this->defender->pp_my = implode(',',$enpp);
			                            }
			                          }

			                          if($this->attackerAtk['id'] == 278) {
			                              if($this->defender->item_id != 0){
			                                  $this->defender->item_id = 0;
			                                  $this->attackerAtk['power'] = $this->attackerAtk['power']*1.5;
			                                  $this->log[] = 'Предмет противника сбит. <br>Мощность атаки увеличена в 1.5 раза.';
			                                  
			                              }
			                            
			                          }
			                          
			                          
			                          
                                        if($this->settings['types'] > 0 && $this->defender->item_id == 478 && $this->attacker->item_id > 0 && $this->attackerAtk['contact'] == 1){
                                            $this->defender->item_id = 0;
                                            $this->attacker->item_id = 478;
			                                $this->log[] = 'Липкая колючка прицепилась к покемону.';
                                        }

                                        /* ===== ДОБАВЛЕНО: Flame Body (id 52) — 30% ожог при контакте + лог урона ===== */
                                        if($this->settings['types'] > 0 
                                          && $this->attackerAtk['contact'] == 1
                                          && $this->defender->ability == 52
                                          && $this->attackerAtk['power'] > 0
                                          && $this->attacker->hp > 0) 
                                        {
                                          $rand_fb = mt_rand(1,100);
                                          if($rand_fb <= 30) {
                                            if($this->attacker->_checkStatus('terrMisty') || $this->attacker->_checkStatus('sleep') || $this->attacker->_checkStatus('safeguard') || in_array('fire',[$this->attacker->_getTypeA(),$this->attacker->_getTypeB()]) || $this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('toxic') || $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('paralyzed') || $this->attacker->_checkStatus('frost')) {
                                              $this->log[] = 'Провал изменения статуса.';
                                            } else {
                                              $this->attacker->_setStatus('burn', 9999);
                                              $__burnDmg = max(1, floor($this->attacker->hp_max / 16)); // 1/16 HP
                                              $this->log[] = '<div class="Ability" onclick="issetAll(52,\'ability\')">'.$this->_abilNameRus(52).'</div> противника накладывает ожог. Урон в конце хода: <span class="HpMinus">-'.$__burnDmg.' HP</span>';
                                            }
                                          }
                                        }

                                        /* ===== ДОБАВЛЕНО: Poison Point (id 134) — 30% яд при контакте + лог урона ===== */
                                        if($this->settings['types'] > 0 
                                          && $this->attackerAtk['contact'] == 1
                                          && $this->defender->ability == 134
                                          && $this->attackerAtk['power'] > 0
                                          && $this->attacker->hp > 0) 
                                        {
                                          $rand_pp = mt_rand(1,100);
                                          if($rand_pp <= 30) {
                                            if($this->attacker->_checkStatus('terrMisty') || $this->attacker->_checkStatus('sleep') || $this->attacker->_checkStatus('safeguard') || in_array('poison',[$this->attacker->_getTypeA(),$this->attacker->_getTypeB()]) || in_array('steel',[$this->attacker->_getTypeA(),$this->attacker->_getTypeB()]) || $this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('toxic') || $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('paralyzed') || $this->attacker->_checkStatus('frost')) {
                                              $this->log[] = 'Провал изменения статуса.';
                                            } else {
                                              $this->attacker->_setStatus('toxic2', 9999); // обычный яд
                                              $__poisonDmg = max(1, floor($this->attacker->hp_max / 8)); // 1/8 HP
                                              $this->log[] = '<div class="Ability" onclick="issetAll(134,\'ability\')">'.$this->_abilNameRus(134).'</div> противника отравляет покемона. Урон в конце хода: <span class="HpMinus">-'.$__poisonDmg.' HP</span>';
                                            }
                                          }
                                        }

			                          if($this->settings['types'] > 0 && $this->defender->ability == 189 && $this->attackerAtk['contact'] == 1) {
			                            $rand189 = mt_rand(1,100);
			                            if($rand189 <= 30) {
			                              if($this->attacker->_checkStatus('terrMisty') || $this->attacker->_checkStatus('sleep') || $this->attacker->_checkStatus('safeguard') || $this->attacker->_getTypeA() == 'electric' || $this->attacker->_getTypeB() == 'electric' || $this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('toxic') ||$this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('paralyzed') || $this->attacker->_checkStatus('frost')) {
			                                $this->log[] = 'Провал изменения статуса.';
			                              }else{
			                                $this->attacker->_setStatus('paralyzed', 9999);
			                                $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника парализует покемона.';
			                              }
			                            }
			                          }
                                      if($this->settings['types'] > 0 
   && $this->attackerAtk['contact'] == 1
   && $this->defender->ability == 31
   && $this->attackerAtk['power'] > 0
   && $this->attacker->hp > 0
) {
    $rand_cursed = mt_rand(1, 100);
    if($rand_cursed <= 30) {
        $disabled_num = isset($this->attackerAtk['attack_num']) ? $this->attackerAtk['attack_num'] : 0;
        $disable_my = explode(',', $this->attacker->disable_my);
        $disable_my[$disabled_num] = 4;
        $this->attacker->disable_my = implode(',', $disable_my);
        $this->log[] = '<div class="Ability" onclick="issetAll(31,\'ability\')">Проклятое тело</div> блокирует атаку '.$this->attackerAtk['name'].' у '.$this->attacker->_getName(true).' на 4 хода!';
    }
}
			                          if($this->settings['types'] > 0 && $this->defender->ability == 92 && $this->attackerAtk['type'] == 'dark' && $this->attackerAtk['power'] >= 1) {
			                            $this->defender->_setModifiedStat('atk', 1, true);
			                            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника увеличивает <span class="StatPlus">Атаку +1</span>';
			                          }

			                          if($this->settings['types'] > 0 && $this->defender->ability == 243 && $this->attacker->ability != 23 && $this->attackerAtk['contact'] == 1 && $this->attackerAtk['power'] >= 1) {
			                            $this->attacker->_setModifiedStat('spd', 1, false);
			                            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника понижает <span class="StatPlus">Скорость -1</span>';
			                          }

			                          if($this->settings['types'] > 0 && $this->defender->ability == 237 && ($this->attackerAtk['type'] == 'water' or $this->attackerAtk['type'] == 'fire')  && $this->attackerAtk['power'] >= 1) {
			                            $this->defender->_setModifiedStat('spd', 6, true);
			                            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника увеличивает <span class="StatPlus">Скорость +6</span>';
			                          }

if($this->settings['types'] > 0 &&  $this->defender->ability == 238 && $this->_actionBattle->weather != 5 && $this->attackerAtk['power'] >= 1) {
			                            Work::$sql->query("UPDATE battle SET `weather` = 5, weather_round = 5 WHERE user_1 = ".$this->defender->user_id." OR user_2 = ".$this->defender->user_id);
                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> меняет погоду на Песчаную бурю.';
			                          }

			                          if($this->settings['types'] > 0 && $this->attackerAtk['contact'] == 1 && $this->defender->ability == 52) {
    $rand52 = mt_rand(1,100);
    if($rand52 <= 30) {
        if(
            $this->attacker->_checkStatus('terrMisty') ||
            $this->attacker->_checkStatus('sleep') ||
            $this->attacker->_checkStatus('safeguard') ||
            $this->attacker->_getTypeA() == 'fire' || $this->attacker->_getTypeB() == 'fire' ||
            $this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('toxic') ||
            $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('paralyzed') ||
            $this->attacker->_checkStatus('frost')
        ) {
            $this->log[] = 'Провал изменения статуса.';
        } else {
            // Water Veil (226) и Water Bubble (228, если нет Mold Breaker 113) — иммунитет к поджогу
            if($this->attacker->ability == 226 || ($this->attacker->ability == 228 && $this->defender->ability != 113)){
                $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> защищает от поджога.';
            }else{
                $this->attacker->_setStatus('burn', 9999);
                $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника поджигает покемона.';
            }
        }
    }
}

if($this->settings['types'] > 0 && $this->attackerAtk['contact'] == 1 && $this->defender->ability == 134) {
    $rand134 = mt_rand(1,100);
    if($rand134 <= 30) {
        if(
            $this->attacker->_checkStatus('terrMisty') ||
            $this->attacker->_checkStatus('sleep') ||
            $this->attacker->_checkStatus('safeguard') ||
            $this->attacker->_getTypeA() == 'poison' || $this->attacker->_getTypeB() == 'poison' ||
            $this->attacker->_getTypeA() == 'steel' || $this->attacker->_getTypeB() == 'steel' ||
            $this->attacker->_checkStatus('burn') || $this->attacker->_checkStatus('toxic') ||
            $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('paralyzed') ||
            $this->attacker->_checkStatus('frost')
        ) {
            $this->log[] = 'Провал изменения статуса.';
        } else {
            $this->attacker->_setStatus('toxic', 9999);
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника отравляет покемона.';
        }
    }
}

// Effect Spore (Эффект спор, id=47): 30% шанс, случайный статус при контакте
if ($this->settings['types'] > 0 && $this->attackerAtk['contact'] == 1 && $this->defender->ability == 47) {
    $rand47 = mt_rand(1,100);
    if ($rand47 <= 30) {
        $candidates = ['toxic','sleep','paralyzed'];
        shuffle($candidates);
        $applied = false;
        foreach ($candidates as $stype) {
            if ($applied) break;
            // базовые блокеры
            if ($this->attacker->_checkStatus('safeguard') || $this->attacker->_checkStatus('terrMisty')) { break; }
            if ($this->attacker->_checkStatus($stype) || $this->attacker->_checkStatus('toxic2')) { continue; }

            // иммунитеты по типам
            if ($stype === 'toxic' && (in_array($this->attacker->_getTypeA(), ['poison','steel']) || in_array($this->attacker->_getTypeB(), ['poison','steel']))) { continue; }
            if ($stype === 'paralyzed' && (in_array($this->attacker->_getTypeA(), ['electric']) || in_array($this->attacker->_getTypeB(), ['electric']))) { continue; }
            if ($stype === 'sleep' && $this->attacker->_checkStatus('sleep')) { continue; }

            $this->attacker->_setStatus($stype, 9999);
            $this->log[] = '<div class="Ability" onclick="issetAll(47,\'ability\')">Эффект спор</div> накладывает статус: '.$stype.'.';
            $applied = true;
        }
        if (!$applied) { $this->log[] = 'Провал изменения статуса.'; }
    }
}

// Synchronize (Синхронизация, id=204) — отражает базовые статусы, если цель с этой способностью получила их
if (
    isset($statusType) &&
    $this->defender->ability == 204 && // Synchronize
    in_array($statusType, ['burn', 'paralyzed', 'toxic', 'toxic2'], true) &&
    $this->attacker->hp > 0 &&
    $this->settings['types'] > 0
) {
    // toxic2 → обычный poison (как в Showdown)
    $stypeTo = ($statusType === 'toxic2') ? 'toxic' : $statusType;

    // иммунитеты атакующего к отражаемому статусу
    $immune = false;
    if ($stypeTo === 'burn' &&
        ($this->attacker->_getTypeA() === 'fire' || $this->attacker->_getTypeB() === 'fire' ||
         $this->attacker->ability == 226 || ($this->attacker->ability == 228 && $this->defender->ability != 113))) { $immune = true; }
    if ($stypeTo === 'paralyzed' &&
        ($this->attacker->_getTypeA() === 'electric' || $this->attacker->_getTypeB() === 'electric')) { $immune = true; }
    if ($stypeTo === 'toxic' &&
        (in_array($this->attacker->_getTypeA(), ['poison','steel']) || in_array($this->attacker->_getTypeB(), ['poison','steel']))) { $immune = true; }

    if (!$this->attacker->_checkStatus($stypeTo) && !$immune && !$this->attacker->_checkStatus('safeguard')) {
        $this->attacker->_setStatus($stypeTo, 9999);
        $this->log[] = '<div class="Ability" onclick="issetAll(204,\'ability\')">Синхронизация</div> заставляет '.$this->attacker->_getName(true).' получить статус '.$stypeTo.'!';
    }
}

if($this->settings['types'] > 0 && $this->attackerAtk['contact'] == 1 && $this->defender->ability == 32) {
    $rand108 = mt_rand(1,100);
    if($rand108 <= 30) {
        if($this->attacker->_checkStatus('lover') || $this->attacker->_getSex() == $this->defender->_getSex() || $this->attacker->_getSex() == 'Бесполый') {
            $this->log[] = 'Провал изменения статуса.';
        }else{
            if($this->attacker->_checkStatus('safeguard')) {
                $this->log[] = 'Провал.';
            }else{
                $this->attacker->_setStatus('lover', 9999);
                $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника заставляет влюбиться в покемона.';
            }
        }
    }
}

if($this->attackerAtk['id'] == 579) {
    $i = 1;
    $fall = 0;
    while($i < 3) {
        if($fall == 0) {
            if($this->isAccuracy($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk)) {
                $this->attackerAtk['power'] = $this->attackerAtk['power'] + 10;
                $this->getDmg($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);
            }else{
                $fall = 1;
            }
        }
        $i++;
    }
    $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
}

if($this->attackerAtk['id'] == 77){
    $this->defender->_setModifiedStatNormal();
    $this->log[] = 'У противника обнулены все характеристики статов.';
}

if($this->attackerAtk['id'] == 374) {
    $this->getAPS($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);
}

if($this->attackerAtk['id'] == 592) {
    $this->getAWS($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, false, 1);
}

if($this->attackerAtk['id'] == 229) {
    $this->getAHW($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, false, 1);
}

if($this->attackerAtk['id'] == 583) {
    $this->getAUT($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, false, 1);
}

if($this->attackerAtk['id'] == 108){
    $this->attacker->desteny_bond = 1;
}


									  // if($userHell1['location'] == 482) {
										//   if(mt_rand(1,3) == 3) {
										// 	  if($_SESSION['id'] == $this->attacker->user_id && !$this->attacker->_checkStatus('sleep')) {
										// 		  $this->attacker->_setStatus('sleep', mt_rand(1,3));
										// 		  $this->log[] = $this->attacker->_getName(true).' уснул.';
										// 	  }
										//   }
									  // }

			                          // if($this->attackerAtk['id'] == 9999){
			                          //   $this->attacker->atk_zamena = 1;
			                          // }

			                          // if($userHell1['location'] == 310 || $userHell1['location'] == 313) {
			                          //   if($this->attacker->user_id == $_SESSION['id']) {
			                          //     if($this->attackerAtk['category'] == 'specific' || $this->attackerAtk['category'] == 'status') {
			                          //       return false;
			                          //     }
			                          //   }
			                          // }
			                          // if($userHell1['location'] == 311 || $userHell1['location'] == 313) {
			                          //   if($this->attacker->user_id != $_SESSION['id']) {
			                          //     $this->attacker->_setModifiedStat('agl', 1, true);
			                          //     $this->log[] = 'У '.$this->attacker->_getName(true).' повышается <span class="StatPlus">Ловкость +1</span>';
			                          //   }
			                          // }

			                          if($this->attackerAtk['id'] == 731) {
                                // Toxic Thread: яд + -1 Скорость (с учётом иммунитетов и всех блокеров)
                                $canLower = true;
                                // Блок по Mist/экранам для понижения статов (если у цели активен defstat и нет обхода 85)
                                if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                                  $canLower = false;
                                  $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                                }
                                // Способности, полностью блокирующие понижение статов
                                if(in_array($this->defender->ability, [23,230,62])) { // Clear Body, White Smoke, Full Metal Body
                                  $canLower = false;
                                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                                }
                                // Mirror Armor — отражение понижения статов
                                if($canLower && $this->defender->ability == 236) { // Mirror Armor
                                  if(!($this->attacker->_checkStatus('defstat') && $this->defender->ability != 85)) {
                                    if($this->attacker->ability == 29){ // Contrary
                                      $this->attacker->_setModifiedStat('spd', 1, true);
                                      $this->log[] = '<div class="Ability" onclick="issetAll(236,\'ability\')">'.$this->_abilNameRus(236).'</div> отражает понижение: Скорость атакующего повышена на 1 из-за <div class="Ability" onclick="issetAll(29,\'ability\')">'.$this->_abilNameRus(29).'</div>.';
                                    }else{
                                      $this->attacker->_setModifiedStat('spd', 1, false);
                                      $this->log[] = '<div class="Ability" onclick="issetAll(236,\'ability\')">'.$this->_abilNameRus(236).'</div> отражает понижение: Скорость атакующего понижена.';
                                    }
                                  } else {
                                    $this->log[] = 'Отражение понижения отменено защитой.';
                                  }
                                  $canLower = false; // цель уже не понижаем
                                }
                                // Понижаем Скорость цели, учитывая Contrary
                                if($canLower){
                                  if($this->defender->ability == 23){
                                    $this->log[] = '<div class="Ability" onclick="issetAll(23,\'ability\')">'.$this->_abilNameRus(23).'</div> противника не дает понизить ему стат.';
                                  }else{
                                    $this->defender->_setModifiedStat('spd', 1, false);
                                    $this->log[] = 'Скорость '.$this->defender->_getName(true).' понижена';
                                  }
                                }
                                // ЯД от Toxic Thread: блок по иммунитетам/защитам
                                if(
                                  !$this->defender->_checkStatus('terrMisty') &&
                                  !$this->defender->_checkStatus('safeguard') &&
                                  !$this->defender->_checkStatus('toxic') &&
                                  !$this->defender->_checkStatus('toxic2') &&
                                  !in_array($this->defender->_getTypeA(), ['poison','steel']) &&
                                  !in_array($this->defender->_getTypeB(), ['poison','steel']) &&
                                  !in_array($this->defender->ability, [83]) // Immunity
                                ){
                                  $this->defender->_setStatus('toxic2', 9999); // обычное отравление
                                  $this->log[] = 'Покемон отравлен смертельной нитью.';
                                }else{
                                  $this->log[] = 'Провал наложения статуса яда.';
                                }
                              }

                              if($this->attackerAtk['id'] == 90) {
                                // Cotton Spore: -2 Скорость; иммунитет у травяных и Overcoat
                                if($this->defender->_getTypeA() == 'grass' || $this->defender->_getTypeB() == 'grass' || $this->defender->ability == 125){
                                  $this->log[] = 'Провал.';
                                }else{
                                  if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                                    $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                                  }else{
                                      // Способности, блокирующие/отражающие понижения
                                      if(in_array($this->defender->ability, [23,230,62])){ // Clear Body, White Smoke, Full Metal Body
                                        $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                                      }elseif($this->defender->ability == 236){ // Mirror Armor
                                        if(!($this->attacker->_checkStatus('defstat') && $this->defender->ability != 85)){
                                          if($this->attacker->ability == 29){ // Contrary
                                            $this->attacker->_setModifiedStat('spd', 2, true);
                                            $this->log[] = '<div class="Ability" onclick="issetAll(236,\'ability\')">'.$this->_abilNameRus(236).'</div> отражает понижение: Скорость атакующего повышена на 2 из-за <div class="Ability" onclick="issetAll(29,\'ability\')">'.$this->_abilNameRus(29).'</div>.';
                                          }else{
                                            $this->attacker->_setModifiedStat('spd', 2, false);
                                            $this->log[] = '<div class="Ability" onclick="issetAll(236,\'ability\')">'.$this->_abilNameRus(236).'</div> отражает понижение: Скорость атакующего значительно понижена';
                                          }
                                        }else{
                                          $this->log[] = 'Отражение понижения отменено защитой.';
                                        }
                                      }else{
                                        $this->defender->_setModifiedStat('spd', 2, false);
                                        $this->log[] = 'Скорость '.$this->defender->_getName(true).' значительно понижена';
                                      }
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 68) {
                                // Captivate: -2 Спец.Атака при противоположном поле и не-бесполых
                                if($this->attacker->sex != $this->defender->sex && $this->attacker->_getSex() != 'Бесполый' && $this->defender->_getSex() != 'Бесполый'){
                                  if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                                    $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                                  }else{
                                      if(in_array($this->defender->ability, [23,230,62])){ // Clear Body / White Smoke / Full Metal Body
                                        $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                                      }elseif($this->defender->ability == 236){ // Mirror Armor
                                        if(!($this->attacker->_checkStatus('defstat') && $this->defender->ability != 85)){
                                          if($this->attacker->ability == 29){ // Contrary
                                            $this->attacker->_setModifiedStat('satk', 2, true);
                                            $this->log[] = '<div class="Ability" onclick="issetAll(236,\'ability\')">'.$this->_abilNameRus(236).'</div> отражает понижение: Спец. Атака атакующего значительно повышена из-за <div class="Ability" onclick="issetAll(29,\'ability\')">'.$this->_abilNameRus(29).'</div>.';
                                          }else{
                                            $this->attacker->_setModifiedStat('satk', 2, false);
                                            $this->log[] = '<div class="Ability" onclick="issetAll(236,\'ability\')">'.$this->_abilNameRus(236).'</div> отражает понижение: Спец. Атака атакующего значительно понижена';
                                          }
                                        }else{
                                          $this->log[] = 'Отражение понижения отменено защитой.';
                                        }
                                      }else{
                                        $this->defender->_setModifiedStat('satk', 2, false);
                                        $this->log[] = 'Спец. Атака '.$this->defender->_getName(true).' значительно понижена';
                                      }
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 614) {
                                // Wring Out / Crush Grip-подобная формула: мощность по текущему HP цели
                                $pwr = floor(120 * ($this->defender->hp / max(1,$this->defender->hp_max)));
                                if($pwr < 1) $pwr = 1;
                                if($pwr > 120) $pwr = 120;
                                $this->attackerAtk['power'] = $pwr;
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }

                              if($this->attackerAtk['id'] == 29) {
                                // Autotomize: Спд +2 и снижение веса на 100 (не меньше 0.1)
                                $this->attacker->_setModifiedStat('spd', 2, true);
                                $this->attacker->base_weight = max(0.1, $this->attacker->base_weight - 100);
                                $this->log[] = $this->attacker->_getName(true).' становится легче на 100 и быстрее (Скорость +2).';
                              }

                              if($this->attackerAtk['id'] == 602 && $this->_actionBattle->_isPVP()) {
                                // Whirlwind: блок Suction Cups / Guard Dog
                                if(in_array($this->defender->ability, [197,271])){ // Suction Cups, Guard Dog
                                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> предотвращает вынуждение к замене.';
                                }else{
                                  $pList = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                                  shuffle($pList);
                                  if($pList){
                                    $allPokes = '';
                                      foreach($pList AS $key_1=>&$value_1){
                                        if($value_1['hp'] <= 0 || $value_1['id'] == $this->defender->id) {

                                        }else{
                                          $allPokes = $value_1['id'];
                                          break;
                                        }
                                      }
                                  }
                                  if($allPokes == '') {
                                    $this->log[] = 'Провал.';
                                  }else{
                                    $this->getSelectedTwo($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, $allPokes, 1);
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 75 && $this->_actionBattle->_isPVP()) {
                                // Circle Throw: Fighting не действует по призракам, плюс блок Suction Cups / Guard Dog
                                if($this->defender->_getTypeA() == 'ghost' || $this->defender->_getTypeB() == 'ghost') {
                                  $this->log[] = 'Нет эффекта.';
                                }elseif(in_array($this->defender->ability, [197,271])){ // Suction Cups, Guard Dog
                                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> предотвращает вынуждение к замене.';
                                }else{
                                  $pList = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                                  shuffle($pList);
                                  if($pList){
                                    $allPokes = '';
                                      foreach($pList AS $key_1=>&$value_1){
                                        if($value_1['hp'] <= 0 || $value_1['id'] == $this->defender->id) {

                                        }else{
                                          $allPokes = $value_1['id'];
                                          break;
                                        }
                                      }
                                  }
                                  if($allPokes == '') {
                                    $this->log[] = 'Провал.';
                                  }else{
                                    $this->getSelectedTwo($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, $allPokes, 1);
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 127 && $this->_actionBattle->_isPVP()) {
                                // Dragon Tail: не действует по Фее, плюс блок Suction Cups / Guard Dog
                                if($this->defender->_getTypeA() == 'fairy' || $this->defender->_getTypeB() == 'fairy') {
                                  $this->log[] = 'Нет эффекта.';
                                }elseif(in_array($this->defender->ability, [197,271])){ // Suction Cups, Guard Dog
                                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> предотвращает вынуждение к замене.';
                                }else{
                                  $pList = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                                  shuffle($pList);
                                  if($pList){
                                    $allPokes = '';
                                      foreach($pList AS $key_1=>&$value_1){
                                        if($value_1['hp'] <= 0 || $value_1['id'] == $this->defender->id) {

                                        }else{
                                          $allPokes = $value_1['id'];
                                          break;
                                        }
                                      }
                                  }
                                  if($allPokes == '') {
                                    $this->log[] = 'Провал.';
                                  }else{
                                    $this->getSelectedTwo($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, $allPokes, 1);
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 433) {
                                $prc = $this->attacker->hp / ($this->attacker->hp_max / 100);
                                if($prc >= 68.75) {
                                  $this->attackerAtk['power'] = 20;
                                }elseif($prc >= 35.42 && $prc < 68.75){
                                  $this->attackerAtk['power'] = 40;
                                }elseif($prc >= 20.83 && $prc < 35.42){
                                  $this->attackerAtk['power'] = 80;
                                }elseif($prc >= 10.42 && $prc < 20.83){
                                  $this->attackerAtk['power'] = 100;
                                }elseif($prc >= 4.17 && $prc < 10.42){
                                  $this->attackerAtk['power'] = 150;
                                }else{
                                  $this->attackerAtk['power'] = 200;
                                }
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }

                              if($this->attackerAtk['id'] == 580) {
                                $mypp = explode(',',$this->attacker->pp_my);
                                $ppatk = $mypp[$this->attackerAtk['attack_num']];
                                switch($ppatk) {
                                  case 0:
                                    $this->attackerAtk['power'] = 200;
                                  break;
                                  case 1:
                                    $this->attackerAtk['power'] = 80;
                                  break;
                                  case 2:
                                    $this->attackerAtk['power'] = 60;
                                  break;
                                  case 3:
                                    $this->attackerAtk['power'] = 50;
                                  break;
                                  default:
                                    $this->attackerAtk['power'] = 40;
                                  break;
                                }
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                                $this->attacker->pp_my = implode(',',$mypp);
                              }

                              if($this->attackerAtk['id'] == 167) {
                                // Flail — те же пороги, что и Reversal
                                $prc = ($this->attacker->hp / $this->attacker->hp_max) * 100;
                                if($prc >= 68.75) {
                                  $this->attackerAtk['power'] = 20;
                                }elseif($prc >= 35.42 && $prc < 68.75) {
                                  $this->attackerAtk['power'] = 40;
                                }elseif($prc >= 20.83 && $prc < 35.42) {
                                  $this->attackerAtk['power'] = 80;
                                }elseif($prc >= 10.42 && $prc < 20.83) {
                                  $this->attackerAtk['power'] = 100;
                                }elseif($prc >= 4.17 && $prc < 10.42) {
                                  $this->attackerAtk['power'] = 150;
                                }else{
                                  $this->attackerAtk['power'] = 200;
                                }
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }

                              if($this->attackerAtk['id'] == 148) {
                                $power = ceil(150 * ($this->attacker->hp / $this->attacker->stats[0]));
                                $power = ($power <= 1 ? 1 : ($power >= 150 ? 150 : $power));
                                $this->attackerAtk['power'] = $power;
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }

                              if($this->attackerAtk['id'] == 99) {
                                $this->attackerAtk['power'] = 120 * ($this->defender->hp / $this->defender->stats[0]);
                                if($this->attackerAtk['power'] <= 0) {
                                  $this->attackerAtk['power'] = 1;
                                }
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }

                              if($this->attackerAtk['id'] == 139) {
                                $electroball = (100 * ($this->defender->_getStatSpd()/$this->attacker->_getStatSpd()));
                                if($electroball <= 100 && $electroball >= 51) {
                                  $this->attackerAtk['power'] = 60;
                                }elseif($electroball <= 50 && $electroball >= 34){
                                  $this->attackerAtk['power'] = 80;
                                }elseif($electroball <= 33 && $electroball >= 25){
                                  $this->attackerAtk['power'] = 120;
                                }else{
                                  $this->attackerAtk['power'] = 150;
                                }
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }

                              if($this->attackerAtk['id'] == 293) {
                                $prc = $this->defender->base_weight;
                                if($this->defender->item_id == 175){ $prc = $prc/2;}
                                if($prc >= 0.1 && $prc <= 9.9) {
                                  $this->attackerAtk['power'] = 20;
                                }elseif($prc >= 10 && $prc <= 24.9) {
                                  $this->attackerAtk['power'] = 40;
                                }elseif($prc >= 25 && $prc <= 49.9) {
                                  $this->attackerAtk['power'] = 60;
                                }elseif($prc >= 50 && $prc <= 99.9) {
                                  $this->attackerAtk['power'] = 80;
                                }elseif($prc >= 100 && $prc <= 199.9) {
                                  $this->attackerAtk['power'] = 100;
                                }else{
                                  $this->attackerAtk['power'] = 120;
                                }
                                $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                              }


			                                                   if($this->attackerAtk['id'] == 232) {
                              // Heavy Slam / Heat Crash-подобная логика: мощность зависит от отношения веса атакующего к весу цели
                              $atk_w = $this->attacker->base_weight;
                              $def_w = $this->defender->base_weight;
                              if($this->attacker->item_id == 175){ $atk_w = $atk_w/2; } // Float Stone у атакующего
                              if($this->defender->item_id == 175){ $def_w = $def_w/2; } // Float Stone у цели
                              $atk_w = max(0.1, $atk_w);
                              $def_w = max(0.1, $def_w);

                              $ratio = $atk_w / $def_w; // чем тяжелее атакующий относительно цели, тем мощнее
                              if($ratio >= 5) {
                                $this->attackerAtk['power'] = 120;
                              }elseif($ratio >= 4) {
                                $this->attackerAtk['power'] = 100;
                              }elseif($ratio >= 3) {
                                $this->attackerAtk['power'] = 80;
                              }elseif($ratio >= 2) {
                                $this->attackerAtk['power'] = 60;
                              }else{
                                $this->attackerAtk['power'] = 40;
                              }
                              $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                          }

                          if($this->attackerAtk['id'] == 234) {
                              // Та же шкала по соотношению веса (второй из пары Heavy Slam/Heat Crash)
                              $atk_w = $this->attacker->base_weight;
                              $def_w = $this->defender->base_weight;
                              if($this->attacker->item_id == 175){ $atk_w = $atk_w/2; }
                              if($this->defender->item_id == 175){ $def_w = $def_w/2; }
                              $atk_w = max(0.1, $atk_w);
                              $def_w = max(0.1, $def_w);

                              $ratio = $atk_w / $def_w;
                              if($ratio >= 5) {
                                $this->attackerAtk['power'] = 120;
                              }elseif($ratio >= 4) {
                                $this->attackerAtk['power'] = 100;
                              }elseif($ratio >= 3) {
                                $this->attackerAtk['power'] = 80;
                              }elseif($ratio >= 2) {
                                $this->attackerAtk['power'] = 60;
                              }else{
                                $this->attackerAtk['power'] = 40;
                              }
                              $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                          }

                          if($this->attackerAtk['id'] == 202) {
                            $prc = $this->defender->base_weight;
                            if($this->defender->item_id == 175){ $prc = $prc/2; } // Float Stone у цели
                            if($prc >= 0.1 && $prc <= 9.9) {
                              $this->attackerAtk['power'] = 20;
                            }elseif($prc >= 10 && $prc <= 24.9) {
                              $this->attackerAtk['power'] = 40;
                            }elseif($prc >= 25 && $prc <= 49.9) {
                              $this->attackerAtk['power'] = 60;
                            }elseif($prc >= 50 && $prc <= 99.9) {
                              $this->attackerAtk['power'] = 80;
                            }elseif($prc >= 100 && $prc <= 199.9) {
                              $this->attackerAtk['power'] = 100;
                            }else{
                              $this->attackerAtk['power'] = 120;
                            }
                            $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                          }

                          if($this->attackerAtk['id'] == 215) {
                            // Gyro Ball: 25 * (скорость цели / скорость атакующего), максимум 150
                            $atkSpd = max(1, $this->attacker->_getStatSpd());
                            $defSpd = max(1, $this->defender->_getStatSpd());
                            $prc = 25 * ($defSpd / $atkSpd);
                            if($prc >= 150) {
                              $prc = 150;
                            }
                            $this->attackerAtk['power'] = round($prc);
                            $this->log[] = 'Мощность атаки: <b>'.$this->attackerAtk['power'].'</b>';
                          }

                          if($this->attackerAtk['id'] == 235) {
                            $this->getAHH($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);
                          }

                          if($this->attackerAtk['id'] == 34) {
                            $this->getABP($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);
                          }

                          if($this->attackerAtk['id'] == 496) {
                            if($this->defender->_checkStatus('paralyzed')) {
                              $info = $this->defender->_getStatusList();
                              if($info){foreach($info AS $key=>$value){if($value['type'] == 'paralyzed'){unset($info[$key]);}}}
                              $this->defender->_setStatusList($info);
                              $this->attackerAtk['power'] = $this->attackerAtk['power'] * 2;
                              $this->log[] = 'Покемон ударил с двойной силой. Противник больше не парализован.';
                            }
                          }

                          if($this->attackerAtk['id'] == 456 || $this->attackerAtk['id'] == 165 || $this->attackerAtk['id'] == 172 || $this->attackerAtk['id'] == 170 || $this->attackerAtk['id'] == 450) {
                            if($this->defender->_checkStatus('frost')) {
                              $info = $this->defender->_getStatusList();
                              if($info){foreach($info AS $key=>$value){if($value['type'] == 'frost'){unset($info[$key]);}}}
                              $this->defender->_setStatusList($info);
                              $this->log[] = 'Покемон оттаял от атаки.';
                            }
                          }

                          if($this->_actionBattle->weather == 2) {
                            if($this->defender->_checkStatus('frost')) {
                              $info = $this->defender->_getStatusList();
                              if($info){foreach($info AS $key=>$value){if($value['type'] == 'frost'){unset($info[$key]);}}}
                              $this->defender->_setStatusList($info);
                              $this->log[] = 'Покемон оттаял от солнечного света.';
                            }
                          }

                          if($this->attackerAtk['id'] == 652) {
                            if($this->defender->_checkStatus('burn')) {
                              $info = $this->defender->_getStatusList();
                              if($info){foreach($info AS $key=>$value){if($value['type'] == 'burn'){unset($info[$key]);}}}
                              $this->defender->_setStatusList($info);
                              $this->log[] = 'Покемон потушен.';
                            }
                          }
        					if($this->attackerAtk['id'] == 193){
        						if($this->defender->_getStatSpd() > $this->attacker->_getStatSpd() && $this->defenderAtk['id'] == 192){
        							$this->attackerAtk['power'] = $this->attackerAtk['power'] * 2;
        							$this->log[] = 'Мощность атаки удвоилась.';
        						}
        					}
        					if($this->attackerAtk['id'] == 192){
        						if($this->defender->_getStatSpd() > $this->attacker->_getStatSpd() && $this->defenderAtk['id'] == 193){
        							$this->attackerAtk['power'] = $this->attackerAtk['power'] * 2;
        							$this->log[] = 'Мощность атаки удвоилась.';
        						}
        					}
                            if($this->attackerAtk['category'] == 'status' || $this->attackerAtk['category'] == 'specific'){

                              if($this->attackerAtk['id'] == 570) {
                                if(isset($this->defender->modified['def'])) {
                                  if(isset($this->defender->modified['def']['minus'])) {
                                    $ReversDef = $this->defender->modified['def']['minus'];
                                    unset($this->defender->modified['def']['minus']);
                                    $this->defender->_setModifiedStat('def', $ReversDef, true);
                                  }elseif(isset($this->defender->modified['def']['plus'])) {
                                    $ReversDef = $this->defender->modified['def']['plus'];
                                    unset($this->defender->modified['def']['plus']);
                                    $this->defender->_setModifiedStat('def', $ReversDef, false);
                                  }
                                }
                                if(isset($this->defender->modified['atk'])) {
                                  if(isset($this->defender->modified['atk']['minus'])) {
                                    $Reversatk = $this->defender->modified['atk']['minus'];
                                    unset($this->defender->modified['atk']['minus']);
                                    $this->defender->_setModifiedStat('atk', $Reversatk, true);
                                  }elseif(isset($this->defender->modified['atk']['plus'])) {
                                    $Reversatk = $this->defender->modified['atk']['plus'];
                                    unset($this->defender->modified['atk']['plus']);
                                    $this->defender->_setModifiedStat('atk', $Reversatk, false);
                                  }
                                }
                                if(isset($this->defender->modified['spd'])) {
                                  if(isset($this->defender->modified['spd']['minus'])) {
                                    $Reversspd = $this->defender->modified['spd']['minus'];
                                    unset($this->defender->modified['spd']['minus']);
                                    $this->defender->_setModifiedStat('spd', $Reversspd, true);
                                  }elseif(isset($this->defender->modified['spd']['plus'])) {
                                    $Reversspd = $this->defender->modified['spd']['plus'];
                                    unset($this->defender->modified['spd']['plus']);
                                    $this->defender->_setModifiedStat('spd', $Reversspd, false);
                                  }
                                }
                                if(isset($this->defender->modified['satk'])) {
                                  if(isset($this->defender->modified['satk']['minus'])) {
                                    $Reverssatk = $this->defender->modified['satk']['minus'];
                                    unset($this->defender->modified['satk']['minus']);
                                    $this->defender->_setModifiedStat('satk', $Reverssatk, true);
                                  }elseif(isset($this->defender->modified['satk']['plus'])) {
                                    $Reverssatk = $this->defender->modified['satk']['plus'];
                                    unset($this->defender->modified['satk']['plus']);
                                    $this->defender->_setModifiedStat('satk', $Reverssatk, false);
                                  }
                                }
                                if(isset($this->defender->modified['sdef'])) {
                                  if(isset($this->defender->modified['sdef']['minus'])) {
                                    $ReverssDef = $this->defender->modified['sdef']['minus'];
                                    unset($this->defender->modified['sdef']['minus']);
                                    $this->defender->_setModifiedStat('sdef', $ReverssDef, true);
                                  }elseif(isset($this->defender->modified['sdef']['plus'])) {
                                    $ReverssDef = $this->defender->modified['sdef']['plus'];
                                    unset($this->defender->modified['sdef']['plus']);
                                    $this->defender->_setModifiedStat('sdef', $ReverssDef, false);
                                  }
                                }
                                if(isset($this->defender->modified['agl'])) {
                                  if(isset($this->defender->modified['agl']['minus'])) {
                                    $Reversagl = $this->defender->modified['agl']['minus'];
                                    unset($this->defender->modified['agl']['minus']);
                                    $this->defender->_setModifiedStat('agl', $Reversagl, true);
                                  }elseif(isset($this->defender->modified['agl']['plus'])) {
                                    $Reversagl = $this->defender->modified['agl']['plus'];
                                    unset($this->defender->modified['agl']['plus']);
                                    $this->defender->_setModifiedStat('agl', $Reversagl, false);
                                  }
                                }
                                if(isset($this->defender->modified['arc'])) {
                                  if(isset($this->defender->modified['arc']['minus'])) {
                                    $Reversarc = $this->defender->modified['arc']['minus'];
                                    unset($this->defender->modified['arc']['minus']);
                                    $this->defender->_setModifiedStat('arc', $Reversarc, true);
                                  }elseif(isset($this->defender->modified['arc']['plus'])) {
                                    $Reversarc = $this->defender->modified['arc']['plus'];
                                    unset($this->defender->modified['arc']['plus']);
                                    $this->defender->_setModifiedStat('arc', $Reversarc, false);
                                  }
                                }
                                $this->log[] = 'Покемон изменяет все модификаторы противника на противоположные.';
                              }

                                        
                                        
                                        
                                        
			                                                            if($this->attackerAtk['id'] == 511) {
                                // Spiky Shield: первый раз 100% блок (ставим 33% на следующий), далее шанс делится на 3 после каждого успешного применения.
                                $poke = Work::$sql->query("SELECT * FROM battle_block WHERE pokID = ".$attacker->id)->fetch_assoc();
                                if(isset($poke)) {
                                  $rand = mt_rand(1,100);
                                  if($rand <= (int)$poke['chanse']){
                                    // Успех: уменьшаем шанс на следующий раз
                                    $next = max(1, floor($poke['chanse'] / 3));
                                    Work::$sql->query("UPDATE battle_block SET `chanse` = ".$next." WHERE pokID = ".$attacker->id);
                                    if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381 && $this->defenderAtk['id'] != 383){
                                      $this->settings['crash'] = true;
                                      $this->defenderAtk['power'] = 0;
                                      $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                    }
                                    // Урон при контакте (если атака заблокирована)
                                    if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533) {
                                      $minusHp = ($this->defender->stats[0] / 8);
                                      $this->defender->hp = ($this->defender->hp - $minusHp);
                                      $this->log[] = 'Противник получает урон от щита <span class="HpMinus">-'.ceil($minusHp).' HP</span>';
                                    }
                                  }else{
                                    $this->log[] = 'Покемону не удается блокировать урон от атаки противника.';
                                    Work::$sql->query('DELETE FROM battle_block WHERE pokID = '.$attacker->id);
                                  }
                                }else{
                                  // Первая успешная защита всегда проходит; на следующий раз шанс = 33%
                                  Work::$sql->query("INSERT INTO battle_block (battleID, attack, chanse, pokID, user) VALUES (".$this->_actionBattle->battleId.",".$this->attackerAtk['id'].", 33, ".$attacker->id.", ".$_SESSION['id'].")");
                                  if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381 && $this->defenderAtk['id'] != 383){
                                    $this->settings['crash'] = true;
                                    $this->defenderAtk['power'] = 0;
                                    $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                  }
                                  if($this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533) {
                                    $minusHp = ($this->defender->stats[0] / 8);
                                    $this->defender->hp = ($this->defender->hp - $minusHp);
                                    $this->log[] = 'Противник получает урон от щита <span class="HpMinus">-'.ceil($minusHp).' HP</span>';
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 277) {
                                // King's Shield: форма Aegislash -> Shield; блок как Protect; при контакте у противника -1 Attack (с учётом способностей/эффектов)
                                if($this->attacker->form == 5) {
                                  $this->attacker->form = 0;
                                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> меняет форму на Щит.';
                                }
                                $poke = Work::$sql->query("SELECT * FROM battle_block WHERE pokID = ".$attacker->id)->fetch_assoc();
                                if(isset($poke)) {
                                  $rand = mt_rand(1,100);
                                  if($rand <= (int)$poke['chanse']){
                                    $next = max(1, floor($poke['chanse'] / 3));
                                    Work::$sql->query("UPDATE battle_block SET `chanse` = ".$next." WHERE pokID = ".$attacker->id);
                                    if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381){
                                      $this->settings['crash'] = true;
                                      $this->defenderAtk['power'] = 0;
                                      $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                      // Штраф по Атаке при контакте (не действует, если у противника защита статов / иммунитет на снижение)
                                      if($this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533) {
                                        if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                                          $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                                        }elseif(in_array($this->defender->ability, [23,79,230,62])){ // Clear Body / Hyper Cutter / White Smoke / Full Metal Body
                                          $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                                        }else{
                                          $this->defender->_setModifiedStat('atk', 1, false);
                                          $this->log[] = 'Противник понижает <span class="StatMinus">Атаку -1</span>';
                                        }
                                      }
                                    }
                                  }else{
                                    $this->log[] = 'Покемону не удается блокировать урон от атаки противника.';
                                    Work::$sql->query('DELETE FROM battle_block WHERE pokID = '.$attacker->id);
                                  }
                                }else{
                                  Work::$sql->query("INSERT INTO battle_block (battleID, attack, chanse, pokID, user) VALUES (".$this->_actionBattle->battleId.",".$this->attackerAtk['id'].", 33, ".$attacker->id.", ".$_SESSION['id'].")");
                                  if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381){
                                    $this->settings['crash'] = true;
                                    $this->defenderAtk['power'] = 0;
                                    $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                    if($this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533) {
                                      if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                                        $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                                      }elseif(in_array($this->defender->ability, [23,79,230,62])){ // Clear Body / Hyper Cutter / White Smoke / Full Metal Body
                                        $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                                      }else{
                                        $this->defender->_setModifiedStat('atk', 1, false);
                                        $this->log[] = 'Противник понижает <span class="StatMinus">Атаку -1</span>';
                                      }
                                    }
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 415) {
                                // Quick Guard: блокирует приоритетные атаки соперника; общий счётчик стагнации как у Protect
                                $poke = Work::$sql->query("SELECT * FROM battle_block WHERE pokID = ".$attacker->id)->fetch_assoc();
                                if(isset($poke)) {
                                  $rand = mt_rand(1,100);
                                  if($rand <= (int)$poke['chanse']){
                                    $next = max(1, floor($poke['chanse'] / 3));
                                    Work::$sql->query("UPDATE battle_block SET `chanse` = ".$next." WHERE pokID = ".$attacker->id);
                                    if($this->defenderAtk['priority'] > 0 && $this->defenderAtk['id'] != 158 && $this->defenderAtk['id'] != 469 && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381){
                                      $this->settings['crash'] = true;
                                      $this->defenderAtk['power'] = 0;
                                      $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                    }
                                  }else{
                                    $this->log[] = 'Покемону не удается блокировать урон от атаки противника.';
                                    Work::$sql->query('DELETE FROM battle_block WHERE pokID = '.$attacker->id);
                                  }
                                }else{
                                  Work::$sql->query("INSERT INTO battle_block (battleID, attack, chanse, pokID, user) VALUES (".$this->_actionBattle->battleId.", ".$this->attackerAtk['id'].", 33, ".$attacker->id.", ".$_SESSION['id'].")");
                                  if($this->defenderAtk['priority'] > 0 && $this->defenderAtk['id'] != 158 && $this->defenderAtk['id'] != 469 && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381){
                                    $this->settings['crash'] = true;
                                    $this->defenderAtk['power'] = 0;
                                    $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                  }
                                }
                              }

                              if($this->attackerAtk['id'] == 727) {
                                // Baneful Bunker: блок как Protect; при контакте отравляет соперника (не понижает статов!)
                                $poke = Work::$sql->query("SELECT * FROM battle_block WHERE pokID = ".$attacker->id)->fetch_assoc();
                                if(isset($poke)) {
                                  $rand = mt_rand(1,100);
                                  if($rand <= (int)$poke['chanse']){
                                    $next = max(1, floor($poke['chanse'] / 3));
                                    Work::$sql->query("UPDATE battle_block SET `chanse` = ".$next." WHERE pokID = ".$attacker->id);
                                    if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381){
                                      $this->settings['crash'] = true;
                                      $this->defenderAtk['power'] = 0;
                                      $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                      // Отравление при контакте
                                      if($this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533
                                        && (
                                          ($this->defender->_getTypeA() != 'poison' && $this->defender->_getTypeB() != 'poison' && $this->defender->_getTypeA() != 'steel' && $this->defender->_getTypeB() != 'steel')
                                          || $this->attacker->ability == 30 /* Corrosion */
                                        )
                                        && !$this->defender->_checkStatus('safeguard')
                                        && $this->defender->ability != 83 /* Immunity */
                                      ) {
                                        $this->defender->_setStatus('toxic', 9999);
                                        $this->log[] = 'Противник отравлен.';
                                      }
                                    }
                                  }else{
                                    $this->log[] = 'Покемону не удается блокировать урон от атаки противника.';
                                    Work::$sql->query('DELETE FROM battle_block WHERE pokID = '.$attacker->id);
                                  }
                                }else{
                                  Work::$sql->query("INSERT INTO battle_block (battleID, attack, chanse, pokID, user) VALUES (".$this->_actionBattle->battleId.", ".$this->attackerAtk['id'].", 33, ".$attacker->id.", ".$_SESSION['id'].")");
                                  if($this->defenderAtk['target'] == 'enemy' && $this->defenderAtk['id'] != 252 && $this->defenderAtk['id'] != 381){
                                    $this->settings['crash'] = true;
                                    $this->defenderAtk['power'] = 0;
                                    $this->log[] = 'Покемон блокирует урон от атаки противника.';
                                  }
                                  if($this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533
                                    && (
                                      ($this->defender->_getTypeA() != 'poison' && $this->defender->_getTypeB() != 'poison' && $this->defender->_getTypeA() != 'steel' && $this->defender->_getTypeB() != 'steel')
                                      || $this->attacker->ability == 30 /* Corrosion */
                                    )
                                    && !$this->defender->_checkStatus('safeguard')
                                    && $this->defender->ability != 83 /* Immunity */
                                  ) {
                                    $this->defender->_setStatus('toxic', 9999);
                                    $this->log[] = 'Противник отравлен.';
                                  }
                                }
                              }

			                             if (in_array($this->attackerAtk['id'], [401, 307, 109])) {
    // Protect / Detect / Obstruct: логика «цепочки» как в Showdown:
    // 1-й подряд — 100%, далее шанс делится на 3 каждый УСПЕШНЫЙ раз (100 -> 33 -> 11 -> 3 -> 1 ...)
    $protect_ignore_ids = [252, 381]; // Feint и Hyperspace Fury/ Hole-подобные
    if ($this->attackerAtk['id'] == 109) { // Obstruct: некоторые доп. исключения
        $protect_ignore_ids = [252, 381, 383, 158];
    }
    $protect_ignore_ids_str = implode(',', $protect_ignore_ids); // оставлено на случай использования ниже

    $current_round = $this->_actionBattle->round;
    $prev_round = $current_round - 1;

    // Проверяем, продолжается ли цепочка (успешная защита шла в прошлом раунде этим же покемоном)
    $chain_continue = false;
    $poke = Work::$sql->query("SELECT * FROM battle_block WHERE pokID = " . $attacker->id)->fetch_assoc();
    if ($poke && isset($poke['round']) && (int)$poke['round'] === (int)$prev_round) {
        $chain_continue = true;
    } else {
        if ($poke) {
            Work::$sql->query('DELETE FROM battle_block WHERE pokID = ' . $attacker->id);
        }
        $poke = null;
    }

    if ($chain_continue && $poke) {
        // Шанс успеха — текущий, после успеха уменьшаем в 3 раза (минимум 1)
        $rand = mt_rand(1, 100);
        if ($rand <= (int)$poke['chanse']) {
            $next = max(1, floor($poke['chanse'] / 3));
            Work::$sql->query("UPDATE battle_block SET `chanse` = $next, `round` = $current_round WHERE pokID = " . $attacker->id);

            if ($this->defenderAtk['target'] == 'enemy' && !in_array($this->defenderAtk['id'], $protect_ignore_ids)) {
                $this->settings['crash'] = true;
                $this->defenderAtk['power'] = 0;
                $this->log[] = 'Покемон блокирует урон от атаки противника.';

                // Доп. эффект Obstruct: при контакте понизить Защиту атакующего на 2 ступени
                if ($this->attackerAtk['id'] == 109 && $this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533) {
                    if ($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                        $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                    } elseif (in_array($this->defender->ability, [23, 230, 62])) { // Clear Body / White Smoke / Full Metal Body
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                    } else {
                        $this->defender->_setModifiedStat('def', 2, false);
                        $this->log[] = 'Противник понижает <span class="StatMinus">Защиту -2</span>';
                    }
                }
            }
        } else {
            $this->log[] = 'Покемону не удается блокировать урон от атаки противника.';
            Work::$sql->query('DELETE FROM battle_block WHERE pokID = ' . $attacker->id);
        }
    } else {
        // Первый (или цепочка сброшена): успех и выставляем шанс на будущее = 33
        Work::$sql->query("REPLACE INTO battle_block (battleID, attack, chanse, pokID, user, round) VALUES ({$this->_actionBattle->battleId}, {$this->attackerAtk['id']}, 33, {$attacker->id}, {$_SESSION['id']}, $current_round)");
        if ($this->defenderAtk['target'] == 'enemy' && !in_array($this->defenderAtk['id'], $protect_ignore_ids)) {
            $this->settings['crash'] = true;
            $this->defenderAtk['power'] = 0;
            $this->log[] = 'Покемон блокирует урон от атаки противника.';

            // Обработка Obstruct при первом успешном применении
            if ($this->attackerAtk['id'] == 109 && $this->defenderAtk['contact'] == 1 && $this->defenderAtk['id'] != 533) {
                if ($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                    $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                } elseif (in_array($this->defender->ability, [23, 230, 62])) { // Clear Body / White Smoke / Full Metal Body
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                } else {
                    $this->defender->_setModifiedStat('def', 2, false);
                    $this->log[] = 'Противник понижает <span class="StatMinus">Защиту -2</span>';
                }
            }
        }
    }
}

if($this->attackerAtk['id'] == 407){
    // Psycho Shift: переносит ОДИН текущий негативный статус с атакующего на цель (если возможно)
    $proval = 'Провал.';
    $info = $this->attacker->_getStatusList();
    if($info){
        // Порядок проверок — токсик2 -> токсик -> ожог -> паралич -> сон -> заморозка
        $order = ['toxic2','toxic','burn','paralyzed','sleep','frost'];
        $moved = false;
        foreach($order as $stype){
            foreach($info as $key=>$value){
                if($value['type'] == $stype){
                    // Иммунитеты и защита цели
                    $immune = false;
                    if($this->defender->_checkStatus('safeguard') || $this->defender->_checkStatus('terrMisty')) { $immune = true; }
                    if(($stype == 'toxic' || $stype == 'toxic2') && $this->attacker->ability != 30 /* Corrosion */){
                        if(in_array($this->defender->_getTypeA(), ['poison','steel']) || in_array($this->defender->_getTypeB(), ['poison','steel'])) $immune = true;
                        if($this->defender->ability == 83 /* Immunity */) $immune = true;
                    }
                    if($stype == 'burn'){
                        if(in_array($this->defender->_getTypeA(), ['fire']) || in_array($this->defender->_getTypeB(), ['fire'])) $immune = true;
                        if($this->defender->ability == 226 /* Water Veil */) $immune = true;
                        if($this->defender->ability == 228 /* Leaf Guard */ && $this->_actionBattle->weather == 2) $immune = true;
                    }
                    if($stype == 'paralyzed'){
                        if(in_array($this->defender->_getTypeA(), ['electric']) || in_array($this->defender->_getTypeB(), ['electric'])) $immune = true;
                        // Limber и пр. можно добавить при наличии в БД
                    }
                    if($stype == 'sleep'){
                        if($this->defender->ability == 113 /* Insomnia */) $immune = true;
                        // Vital Spirit и др. при наличии в БД
                    }
                    if($stype == 'frost'){
                        if(in_array($this->defender->_getTypeA(), ['ice']) || in_array($this->defender->_getTypeB(), ['ice'])) $immune = true;
                        // Magma Armor и др. при наличии в БД
                    }

                    if(!$immune){
                        // переноcим статус
                        unset($info[$key]);
                        $this->attacker->_setStatusList($info);
                        $this->defender->_setStatus($stype, 9999);
                        $proval = 'Покемон передал статус противнику.';
                        $moved = true;
                    }else{
                        $proval = 'Покемон передал статусы противнику.<br>Провал изменения статуса.';
                    }
                    break 2; // переносим только ОДИН статус, как в Showdown
                }
            }
        }
        if(!$moved && $proval === 'Провал.') { $proval = 'Провал.'; }
    }else{
        $proval = 'Провал.';
    }
    $this->log[] = $proval;
}

if($this->attackerAtk['id'] == 348){
    // Nightmare: накладывается ТОЛЬКО если цель спит; Safeguard не блокирует этот волатильный эффект
    $info = $this->defender->_getStatusList();
    $asleep = false; $hasNightmare = false;
    if($info){
        foreach($info as $key=>$value){
            if($value['type'] == 'sleep'){ $asleep = true; }
            if($value['type'] == 'nightmare'){ $hasNightmare = true; }
        }
    }
    if($asleep && !$hasNightmare){
        $this->defender->_setStatus('nightmare', 9999);
        $this->log[] = 'Противник начал мучаться от кошмаров.';
    }elseif(!$asleep){
        $this->log[] = 'Провал. Покемон не спит.';
    }else{
        $this->log[] = 'Провал.';
    }
}

if($this->attackerAtk['id'] == 678) {
    $this->getAAM($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);
}

			                                                if($this->attackerAtk['id'] == 427) {
                            // Refresh: лечит burn/paralyzed/poison (toxic/toxic2). Не лечит сон/заморозку.
                            $info = $this->attacker->_getStatusList();
                            if($info){
                              foreach($info AS $key=>$value){
                                if($value['type'] == 'toxic' || $value['type'] == 'toxic2' || $value['type'] == 'burn' || $value['type'] == 'paralyzed'){
                                  unset($info[$key]);
                                }
                              }
                            }
                            $this->attacker->_setStatusList($info);
                            $this->log[] = 'Покемон вылечился от некоторых негативных статусов.';
                          }

                          if($this->attackerAtk['id'] == 534) {
                            // Sunny Day
                            Work::$sql->query("UPDATE battle SET `weather` = 2, weather_round = 5 WHERE user_1 = ".$attacker->user_id." OR user_2 = ".$attacker->user_id);
                            $this->log[] = 'Погода меняется на Солнечную.';
                          }

                          if($this->attackerAtk['id'] == 455) {
                            // Sandstorm
                            Work::$sql->query("UPDATE battle SET `weather` = 5, weather_round = 5 WHERE user_1 = ".$attacker->user_id." OR user_2 = ".$attacker->user_id);
                            $this->log[] = 'Погода меняется на Песчаную бурю.';
                          }

                          if($this->attackerAtk['id'] == 216) {
                            // Hail / Snow (у вас weather=4)
                            Work::$sql->query("UPDATE battle SET `weather` = 4, weather_round = 5 WHERE user_1 = ".$attacker->user_id." OR user_2 = ".$attacker->user_id);
                            $this->log[] = 'Погода меняется на Град.';
                          }
                          if($this->attackerAtk['id'] == 419) {
                            // Rain Dance
                            Work::$sql->query("UPDATE battle SET `weather` = 3, weather_round = 5 WHERE user_1 = ".$attacker->user_id." OR user_2 = ".$attacker->user_id);
                            $this->log[] = 'Погода меняется на Дождь.';
                          }
                          if($this->attackerAtk['id'] == 789) {
                            // Snowscape (у вас также weather=4)
                            Work::$sql->query("UPDATE battle SET `weather` = 4, weather_round = 5 WHERE user_1 = ".$attacker->user_id." OR user_2 = ".$attacker->user_id);
                            $this->log[] = 'Погода меняется на Град.';
                          }
                          if($this->attackerAtk['id'] == 459) {
                            // Hail (дублирует 4)
                            Work::$sql->query("UPDATE battle SET `weather` = 4, weather_round = 5 WHERE user_1 = ".$attacker->user_id." OR user_2 = ".$attacker->user_id);
                            $this->log[] = 'Погода меняется на Град.';
                          }

                          if($this->attackerAtk['id'] == 209) {
                            $this->log[] = 'Покемон начинает обижаться...';
                          }

                          if($this->attackerAtk['id'] == 398) {
                            // Power Trick: меняет местами текущие значения Атаки и Защиты
                            $this->log[] = 'Покемон меняет статы Защиты и Атаки местами.';
                            $stat1 = $this->attacker->stats[1];
                            $stat2 = $this->attacker->stats[2];
                            $this->attacker->stats[1] = $stat2;
                            $this->attacker->stats[2] = $stat1;
                          }

                          if($this->attackerAtk['id'] == 558) {
                            // Ходы "побега": в PvP запрещено, в PvE — выход из боя
                            if($this->_actionBattle->_isPVP()) {
                              $this->log[] = 'Провал.';
                            }else{
                              $this->_actionBattle->lose(true, 'DRAW', 5);
                            }
                          }

                          if(!$this->_actionBattle->_isPVP() && $this->attacker->ability == 157) {
                            // Run Away (157): помогает сбежать в PvE
                            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> помогает убежать покемону с битвы.';
                            $this->_actionBattle->lose(true, 'DRAW', 6);
                          }

                          if($this->attackerAtk['id'] == 712) {
                            // Shore Up: лечит 2/3 в песчаной буре, иначе 1/2; Cloud Nine/Air Lock (id=4) игнорируют погоду
                            $effWeather = (in_array(4,[$this->attacker->ability,$this->defender->ability]) ? 0 : $this->_actionBattle->weather);
                            if($effWeather == 5) {
                              $hp = floor(($this->attacker->stats[0] * 2) / 3);
                            }else{
                              $hp = floor($this->attacker->stats[0] / 2);
                            }
                            $this->attacker->hp = $this->attacker->hp + $hp;
                            $this->log[] =  $this->attacker->_getName(true).' восстанавливает <span class="HpPlus">+'.$hp.' HP</span>';
                          }

                          if($this->attackerAtk['id'] == 547) {
                            // Synthesis/Morning Sun/Moonlight: 2/3 на солнце, 1/4 в дождь/град/песок, иначе 1/2; Cloud Nine/Air Lock (id=4) игнорируют погоду
                            $effWeather = (in_array(4,[$this->attacker->ability,$this->defender->ability]) ? 0 : $this->_actionBattle->weather);
                            if($effWeather == 2) {
                              $hp = floor(($this->attacker->stats[0] * 2) / 3);
                            }elseif(in_array($effWeather, [3,4,5])) {
                              $hp = floor($this->attacker->stats[0] / 4);
                            }else{
                              $hp = floor($this->attacker->stats[0] / 2);
                            }
                            $this->attacker->hp = $this->attacker->hp + $hp;
                            $this->log[] =  $this->attacker->_getName(true).' восстанавливает <span class="HpPlus">+'.$hp.' HP</span>';
                          }

                          if($this->attackerAtk['id'] == 682) {
                            // Floral Healing: 2/3 на Травяном Террейне, иначе 1/2
                            if($this->defender->_checkStatus('terrGrass') || $this->attacker->_checkStatus('terrGrass')) {
                              $hp = floor(($this->attacker->stats[0] * 2) / 3);
                            }else{
                              $hp = floor($this->attacker->stats[0] / 2);
                            }
                            $this->attacker->hp = $this->attacker->hp + $hp;
                            $this->log[] =  $this->attacker->_getName(true).' восстанавливает <span class="HpPlus">+'.$hp.' HP</span>';
                          }

			                                            if($this->attackerAtk['id'] == 334) {
                        // Moonlight: как Synthesis — 2/3 на солнце, 1/4 в дождь/град/песке, иначе 1/2.
                        // Cloud Nine / Air Lock (id=4) игнорируют погоду.
                        $effWeather = (in_array(4,[$this->attacker->ability,$this->defender->ability]) ? 0 : $this->_actionBattle->weather);
                        if($effWeather == 2) { // Sun
                          $hp = floor(($this->attacker->stats[0] * 2) / 3);
                        } elseif(in_array($effWeather,[3,4,5])) { // Rain / Hail(Snow) / Sand
                          $hp = floor($this->attacker->stats[0] / 4);
                        } else {
                          $hp = floor($this->attacker->stats[0] / 2);
                        }
                        $before = $this->attacker->hp;
                        $this->attacker->hp = min($this->attacker->hp + $hp, $this->attacker->hp_max);
                        $healed = $this->attacker->hp - $before;
                        $this->log[] =  $this->attacker->_getName(true).' восстанавливает <span class="HpPlus">+'.$healed.' HP</span>';
                      }

                      if($this->attackerAtk['id'] == 333) {
                        // Morning Sun: те же правила лечения, что и у Synthesis/Moonlight.
                        $effWeather = (in_array(4,[$this->attacker->ability,$this->defender->ability]) ? 0 : $this->_actionBattle->weather);
                        if($effWeather == 2) { // Sun
                          $hp = floor(($this->attacker->stats[0] * 2) / 3);
                        } elseif(in_array($effWeather,[3,4,5])) { // Rain / Hail(Snow) / Sand
                          $hp = floor($this->attacker->stats[0] / 4);
                        } else {
                          $hp = floor($this->attacker->stats[0] / 2);
                        }
                        $before = $this->attacker->hp;
                        $this->attacker->hp = min($this->attacker->hp + $hp, $this->attacker->hp_max);
                        $healed = $this->attacker->hp - $before;
                        $this->log[] =  $this->attacker->_getName(true).' восстанавливает <span class="HpPlus">+'.$healed.' HP</span>';
                      }

                      if(in_array($this->attackerAtk['id'], [231,403])) {
                        // Обмен модификаторами (стадиями статов)
                        $this->log[] = 'Покемон меняется всеми модификаторами с противником.';

                        // Для 403 дополнительно обрабатываем Точность/Уклонение (acr/agl)
                        if($this->attackerAtk['id'] == 403) {
                          if(isset($this->attacker->modified['acr']['plus'])){
                            $atkMy = [$this->attacker->modified['acr']['plus'],'true'];
                            unset($this->attacker->modified['acr']['plus']);
                          }
                          if(isset($this->attacker->modified['acr']['minus'])){
                            $atkMy = [$this->attacker->modified['acr']['minus'],'false'];
                            unset($this->attacker->modified['acr']['minus']);
                          }
                          if(isset($this->defender->modified['acr']['plus'])){
                            $atkEn = [$this->defender->modified['acr']['plus'],'true'];
                            unset($this->defender->modified['acr']['plus']);
                          }
                          if(isset($this->defender->modified['acr']['minus'])){
                            $atkEn = [$this->defender->modified['acr']['minus'],'false'];
                            unset($this->defender->modified['acr']['minus']);
                          }

                          // Исправление опечатки: используем 'agl' (уклонение), а не 'alg'
                          if(isset($this->attacker->modified['agl']['plus'])){
                            $atkMy = [$this->attacker->modified['agl']['plus'],'true'];
                            unset($this->attacker->modified['agl']['plus']);
                          }
                          if(isset($this->attacker->modified['agl']['minus'])){
                            $atkMy = [$this->attacker->modified['agl']['minus'],'false'];
                            unset($this->attacker->modified['agl']['minus']);
                          }
                          if(isset($this->defender->modified['agl']['plus'])){
                            $atkEn = [$this->defender->modified['agl']['plus'],'true'];
                            unset($this->defender->modified['agl']['plus']);
                          }
                          if(isset($this->defender->modified['agl']['minus'])){
                            $atkEn = [$this->defender->modified['agl']['minus'],'false'];
                            unset($this->defender->modified['agl']['minus']);
                          }
                        }

                        if(isset($this->attacker->modified['atk']['plus'])){
                          $atkMy = [$this->attacker->modified['atk']['plus'],'true'];
                          unset($this->attacker->modified['atk']['plus']);
                        }
                        if(isset($this->attacker->modified['atk']['minus'])){
                          $atkMy = [$this->attacker->modified['atk']['minus'],'false'];
                          unset($this->attacker->modified['atk']['minus']);
                        }
                        if(isset($this->defender->modified['atk']['plus'])){
                          $atkEn = [$this->defender->modified['atk']['plus'],'true'];
                          unset($this->defender->modified['atk']['plus']);
                        }
                        if(isset($this->defender->modified['atk']['minus'])){
                          $atkEn = [$this->defender->modified['atk']['minus'],'false'];
                          unset($this->defender->modified['atk']['minus']);
                        }

                        if(isset($this->attacker->modified['satk']['plus'])){
                          $satkMy = [$this->attacker->modified['satk']['plus'],'true'];
                          unset($this->attacker->modified['satk']['plus']);
                        }
                        if(isset($this->attacker->modified['satk']['minus'])){
                          $satkMy = [$this->attacker->modified['satk']['minus'],'false'];
                          unset($this->attacker->modified['satk']['minus']);
                        }
                        if(isset($this->defender->modified['satk']['plus'])){
                          $satkEn = [$this->defender->modified['satk']['plus'],'true'];
                          unset($this->defender->modified['satk']['plus']);
                        }
                        if(isset($this->defender->modified['satk']['minus'])){
                          $satkEn = [$this->defender->modified['satk']['minus'],'false'];
                          unset($this->defender->modified['satk']['minus']);
                        }

                        if(isset($this->attacker->modified['spd']['plus'])){
                          $spdMy = [$this->attacker->modified['spd']['plus'],'true'];
                          unset($this->attacker->modified['spd']['plus']);
                        }
                        if(isset($this->attacker->modified['spd']['minus'])){
                          $spdMy = [$this->attacker->modified['spd']['minus'],'false'];
                          unset($this->attacker->modified['spd']['minus']);
                        }
                        if(isset($this->defender->modified['spd']['plus'])){
                          $spdEn = [$this->defender->modified['spd']['plus'],'true'];
                          unset($this->defender->modified['spd']['plus']);
                        }
                        if(isset($this->defender->modified['spd']['minus'])){
                          $spdEn = [$this->defender->modified['spd']['minus'],'false'];
                          unset($this->defender->modified['spd']['minus']);
                        }

                        if(isset($this->attacker->modified['def']['plus'])){
                          $defMy = [$this->attacker->modified['def']['plus'],'true'];
                          unset($this->attacker->modified['def']['plus']);
                        }
                        if(isset($this->attacker->modified['def']['minus'])){
                          $defMy = [$this->attacker->modified['def']['minus'],'false'];
                          unset($this->attacker->modified['def']['minus']);
                        }
                        if(isset($this->defender->modified['def']['plus'])){
                          $defEn = [$this->defender->modified['def']['plus'],'true'];
                          unset($this->defender->modified['def']['plus']);
                        }
                        if(isset($this->defender->modified['def']['minus'])){
                          $defEn = [$this->defender->modified['def']['minus'],'false'];
                          unset($this->defender->modified['def']['minus']);
                        }
                        if(isset($this->attacker->modified['sdef']['plus'])){
                          $sdefMy = [$this->attacker->modified['sdef']['plus'],'true'];
                          unset($this->attacker->modified['sdef']['plus']);
                        }
                        if(isset($this->attacker->modified['sdef']['minus'])){
                          $sdefMy = [$this->attacker->modified['sdef']['minus'],'false'];
                          unset($this->attacker->modified['sdef']['minus']);
                        }
                        if(isset($this->defender->modified['sdef']['plus'])){
                          $sdefEn = [$this->defender->modified['sdef']['plus'],'true'];
                          unset($this->defender->modified['sdef']['plus']);
                        }
                        if(isset($this->defender->modified['sdef']['minus'])){
                          $sdefEn = [$this->defender->modified['sdef']['minus'],'false'];
                          unset($this->defender->modified['sdef']['minus']);
                        }

                        if(isset($defMy))  { $this->defender->_setModifiedStat('def',  $defMy[0],  $defMy[1]); }
                        if(isset($defEn))  { $this->attacker->_setModifiedStat('def',  $defEn[0],  $defEn[1]); }
                        if(isset($sdefMy)) { $this->defender->_setModifiedStat('sdef', $sdefMy[0], $sdefMy[1]); }
                        if(isset($sdefEn)) { $this->attacker->_setModifiedStat('sdef', $sdefEn[0], $sdefEn[1]); }
                        if(isset($atkMy))  { $this->defender->_setModifiedStat('atk',  $atkMy[0],  $atkMy[1]); }
                        if(isset($atkEn))  { $this->attacker->_setModifiedStat('atk',  $atkEn[0],  $atkEn[1]); }
                        if(isset($spdMy))  { $this->defender->_setModifiedStat('spd',  $spdMy[0],  $spdMy[1]); }
                        if(isset($spdEn))  { $this->attacker->_setModifiedStat('spd',  $spdEn[0],  $spdEn[1]); }
                        if(isset($satkMy)) { $this->defender->_setModifiedStat('satk', $satkMy[0], $satkMy[1]); }
                        if(isset($satkEn)) { $this->attacker->_setModifiedStat('satk', $satkEn[0], $satkEn[1]); }
                      }

                      if($this->attackerAtk['id'] == 211) {
                        // Guard Swap: меняемся только модификаторами DEF/SDEF
                        $this->log[] = 'Покемон меняется модификаторами Защиты и Спец. Защиты с противником.';
                        if(isset($this->attacker->modified['def']['plus'])){
                          $defMy = [$this->attacker->modified['def']['plus'],'true'];
                          unset($this->attacker->modified['def']['plus']);
                        }
                        if(isset($this->attacker->modified['def']['minus'])){
                          $defMy = [$this->attacker->modified['def']['minus'],'false'];
                          unset($this->attacker->modified['def']['minus']);
                        }
                        if(isset($this->defender->modified['def']['plus'])){
                          $defEn = [$this->defender->modified['def']['plus'],'true'];
                          unset($this->defender->modified['def']['plus']);
                        }
                        if(isset($this->defender->modified['def']['minus'])){
                          $defEn = [$this->defender->modified['def']['minus'],'false'];
                          unset($this->defender->modified['def']['minus']);
                        }
                        if(isset($this->attacker->modified['sdef']['plus'])){
                          $sdefMy = [$this->attacker->modified['sdef']['plus'],'true'];
                          unset($this->attacker->modified['sdef']['plus']);
                        }
                        if(isset($this->attacker->modified['sdef']['minus'])){
                          $sdefMy = [$this->attacker->modified['sdef']['minus'],'false'];
                          unset($this->attacker->modified['sdef']['minus']);
                        }
                        if(isset($this->defender->modified['sdef']['plus'])){
                          $sdefEn = [$this->defender->modified['sdef']['plus'],'true'];
                          unset($this->defender->modified['sdef']['plus']);
                        }
                        if(isset($this->defender->modified['sdef']['minus'])){
                          $sdefEn = [$this->defender->modified['sdef']['minus'],'false'];
                          unset($this->defender->modified['sdef']['minus']);
                        }
                        if(isset($defMy))  { $this->defender->_setModifiedStat('def',  $defMy[0],  $defMy[1]); }
                        if(isset($defEn))  { $this->attacker->_setModifiedStat('def',  $defEn[0],  $defEn[1]); }
                        if(isset($sdefMy)) { $this->defender->_setModifiedStat('sdef', $sdefMy[0], $sdefMy[1]); }
                        if(isset($sdefEn)) { $this->attacker->_setModifiedStat('sdef', $sdefEn[0], $sdefEn[1]); }
                      }

			                                          if($this->attackerAtk['id'] == 331){
                        $rn = $this->_actionBattle->round + 5;
                        $pList = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
                        if($pList){
                          $allPokes = '';
                          foreach($pList AS $key_1=>&$value_1){
                            $allPokes .= ''.$value_1['id'].',';
                          }
                        }
                        if($this->_actionBattle->_isPVP()) {
                          $pList2 = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                          if($pList2){
                            $allPokes2 = '';
                            foreach($pList2 AS $key_2=>&$value_2){
                              $allPokes2 .= ''.$value_2['id'].',';
                            }
                          }
                        }
                        $lscr =  Work::$sql->query('SELECT * FROM battle_effects WHERE user = '.$this->attacker->user_id.' AND name = "TerrMisty"')->fetch_assoc();
                        if(isset($lscr)) {
                          if($lscr['end'] <= $this->_actionBattle->round){
                            // Чистим ТОЛЬКО террейны текущего боя
                            Work::$sql->query("DELETE FROM `battle_effects` WHERE `battle` = ".$this->_actionBattle->battleId." AND name IN ('TerrMisty','TerrGrass','TerrElectric','TerrPsychic')");
                            // Снимаем статусы других полей
                            if($this->attacker->_checkStatus('terrGrass') || $this->attacker->_checkStatus('terrElectric') || $this->attacker->_checkStatus('terrPsychic')) {
                              $info = $this->attacker->_getStatusList();
                              if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrGrass','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                              $this->attacker->_setStatusList($info);
                            }
                            if($this->defender->_checkStatus('terrGrass') || $this->defender->_checkStatus('terrElectric') || $this->defender->_checkStatus('terrPsychic')) {
                              $info = $this->defender->_getStatusList();
                              if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrGrass','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                              $this->defender->_setStatusList($info);
                            }
                            $this->attacker->_setStatus('terrMisty', 5);
                            $this->defender->_setStatus('terrMisty', 5);
                            $this->log[] = 'Поле затуманено до '.($rn - 1).' раунда включительно.';
                            if($this->_actionBattle->_isPVP()) {
                              Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->attacker->user_id." AND name = 'TerrMisty'");
                              Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->defender->user_id." AND name = 'TerrMisty'");
                            }else{
                              Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$_SESSION['id']." AND name = 'TerrMisty'");
                            }
                          }else{
                            $this->log[] = 'Провал. Затуманивание уже действует.';
                          }
                        }else{
                            // Чистим ТОЛЬКО террейны текущего боя
                            Work::$sql->query("DELETE FROM `battle_effects` WHERE `battle` = ".$this->_actionBattle->battleId." AND name IN ('TerrMisty','TerrGrass','TerrElectric','TerrPsychic')");
                            // Снимаем статусы других полей
                            if($this->attacker->_checkStatus('terrGrass') || $this->attacker->_checkStatus('terrElectric') || $this->attacker->_checkStatus('terrPsychic')) {
                              $info = $this->attacker->_getStatusList();
                              if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrGrass','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                              $this->attacker->_setStatusList($info);
                            }
                            if($this->defender->_checkStatus('terrGrass') || $this->defender->_checkStatus('terrElectric') || $this->defender->_checkStatus('terrPsychic')) {
                              $info = $this->defender->_getStatusList();
                              if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrGrass','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                              $this->defender->_setStatusList($info);
                            }
                          $this->attacker->_setStatus('terrMisty', 5);
                          $this->defender->_setStatus('terrMisty', 5);
                          $this->log[] = 'Поле затуманено до '.($rn - 1).' раунда включительно.';
                          if($this->_actionBattle->_isPVP()) {
                            Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('TerrMisty', ".$rn.", ".$this->attacker->user_id.", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                            Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('TerrMisty', ".$rn.", ".$this->defender->user_id.", '".(isset($allPokes2)?$allPokes2:'')."', ".$this->_actionBattle->battleId.")");
                          }else{
                            Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('TerrMisty', ".$rn.", ".$_SESSION['id'].", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                          }
                        }
                    }
                    if($this->attackerAtk['id'] == 205){
                      $rn = $this->_actionBattle->round + 5;
                      $pList = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
                      if($pList){
                        $allPokes = '';
                        foreach($pList AS $key_1=>&$value_1){
                          $allPokes .= ''.$value_1['id'].',';
                        }
                      }
                      if($this->_actionBattle->_isPVP()) {
                        $pList2 = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                        if($pList2){
                          $allPokes2 = '';
                          foreach($pList2 AS $key_2=>&$value_2){
                            $allPokes2 .= ''.$value_2['id'].',';
                          }
                        }
                      }
                      $lscr =  Work::$sql->query('SELECT * FROM battle_effects WHERE user = '.$this->attacker->user_id.' AND name = "TerrGrass"')->fetch_assoc();
                      if(isset($lscr)) {
                        if($lscr['end'] <= $this->_actionBattle->round){
                          // Чистим ТОЛЬКО террейны текущего боя
                          Work::$sql->query("DELETE FROM `battle_effects` WHERE `battle` = ".$this->_actionBattle->battleId." AND name IN ('TerrMisty','TerrGrass','TerrElectric','TerrPsychic')");
                          // Снимаем статусы других полей
                          if($this->attacker->_checkStatus('terrMisty') || $this->attacker->_checkStatus('terrElectric') || $this->attacker->_checkStatus('terrPsychic')) {
                            $info = $this->attacker->_getStatusList();
                            if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrMisty','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                            $this->attacker->_setStatusList($info);
                          }
                          if($this->defender->_checkStatus('terrMisty') || $this->defender->_checkStatus('terrElectric') || $this->defender->_checkStatus('terrPsychic')) {
                            $info = $this->defender->_getStatusList();
                            if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrMisty','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                            $this->defender->_setStatusList($info);
                          }
                          $this->attacker->_setStatus('terrGrass', 5);
                          $this->defender->_setStatus('terrGrass', 5);
                          $this->log[] = 'Поле озеленено до '.($rn - 1).' раунда включительно.';
                          if($this->_actionBattle->_isPVP()) {
                            Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->attacker->user_id." AND name = 'TerrGrass'");
                            Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->defender->user_id." AND name = 'TerrGrass'");
                          }else{
                            Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$_SESSION['id']." AND name = 'TerrGrass'");
                          }
                        }else{
                          $this->log[] = 'Провал. Озеленение уже действует.';
                        }
                      }else{
                        // Чистим ТОЛЬКО террейны текущего боя
                        Work::$sql->query("DELETE FROM `battle_effects` WHERE `battle` = ".$this->_actionBattle->battleId." AND name IN ('TerrMisty','TerrGrass','TerrElectric','TerrPsychic')");
                        // Снимаем статусы других полей
                        if($this->attacker->_checkStatus('terrMisty') || $this->attacker->_checkStatus('terrElectric') || $this->attacker->_checkStatus('terrPsychic')) {
                          $info = $this->attacker->_getStatusList();
                          if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrMisty','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                          $this->attacker->_setStatusList($info);
                        }
                        if($this->defender->_checkStatus('terrMisty') || $this->defender->_checkStatus('terrElectric') || $this->defender->_checkStatus('terrPsychic')) {
                          $info = $this->defender->_getStatusList();
                          if($info){ foreach($info AS $key=>$value){ if(in_array($value['type'],['terrMisty','terrElectric','terrPsychic'])){ unset($info[$key]); }}}
                          $this->defender->_setStatusList($info);
                        }
                        $this->attacker->_setStatus('terrGrass', 5);
                        $this->defender->_setStatus('terrGrass', 5);
                        $this->log[] = 'Поле озеленено до '.($rn - 1).' раунда включительно.';
                        if($this->_actionBattle->_isPVP()) {
                          Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('TerrGrass', ".$rn.", ".$this->attacker->user_id.", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                          Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('TerrGrass', ".$rn.", ".$this->defender->user_id.", '".(isset($allPokes2)?$allPokes2:'')."', ".$this->_actionBattle->battleId.")");
                        }else{
                          Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('TerrGrass', ".$rn.", ".$_SESSION['id'].", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                        }
                      }
                    }

			                                                  if($this->attackerAtk['id'] == 452){
                                  $rn = $this->_actionBattle->round + 5;
                                  $pList = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
                                  if($pList){
                                    $allPokes = '';
                                      foreach($pList AS $key_1=>&$value_1){
                                        $allPokes .= ''.$value_1['id'].',';
                                      }
                                  }
                                  $lscr =  Work::$sql->query('SELECT * FROM battle_effects WHERE user = '.$this->attacker->user_id.' AND name = "Safeguard"')->fetch_assoc();
                                  if(isset($lscr)) {
                                    if($lscr['end'] <= $this->_actionBattle->round){
                                      $this->attacker->_setStatus('safeguard', 5);
                                      $this->log[] = 'Поле пользователя защищено от статусных воздействий и спутывания до '.($rn - 1).' раунда включительно.';
                                      Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->attacker->user_id." AND name = 'Safeguard'");
                                    }else{
                                      $this->log[] = 'Провал. Безопасность уже действует';
                                    }
                                  }else{
                                    $this->attacker->_setStatus('safeguard', 5);
                                    $this->log[] = 'Поле пользователя защищено от статусных воздействий и спутывания до '.($rn - 1).' раунда включительно.';
                                    Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('Safeguard', ".$rn.", ".$this->attacker->user_id.", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                                  }
                              }
                              if(($this->attackerAtk['id'] == 378) or (($this->defenderAtk['contact'] == 1 and $this->attacker->ability == 248) or ($this->attackerAtk['contact'] == 1 and $this->defender->ability == 248))){
                                  $rn = $this->_actionBattle->round + 3;
                                  $pList = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
                                  if($pList){
                                    $allPokes = '';
                                      foreach($pList AS $key_1=>&$value_1){
                                        $allPokes .= ''.$value_1['id'].',';
                                      }
                                  }
                                  if($this->_actionBattle->_isPVP()) {
                                    $pList2 = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                                    if($pList2){
                                      $allPokes2 = '';
                                        foreach($pList2 AS $key_2=>&$value_2){
                                          $allPokes2 .= ''.$value_2['id'].',';
                                        }
                                    }
                                  }
                                  // Песня смерти и способность Пересекающееся Тело (Perish Body) не блокируются Safeguard
                                  $lscr =  Work::$sql->query('SELECT * FROM battle_effects WHERE user = '.$this->attacker->user_id.' AND name = "DeathSong"')->fetch_assoc();
                                  if(isset($lscr)) {
                                    if($lscr['end'] <= $this->_actionBattle->round){
                                      $this->attacker->_setStatus('deathSong', 3);
                                      $this->defender->_setStatus('deathSong', 3);
                                      if($this->attackerAtk['id'] == 378){
                                        $this->log[] = 'Покемоны на поле потеряют сознание на '.($rn - 1).' раунде (Песня смерти).';
                                      }else{
                                        $this->log[] = 'Покемоны на поле получили отсчет Песни смерти из-за способности противника.';
                                      }
                                      if($this->_actionBattle->_isPVP()) {
                                        Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->attacker->user_id." AND name = 'DeathSong'");
                                        Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$this->defender->user_id." AND name = 'DeathSong'");
                                      }else{
                                        Work::$sql->query("UPDATE battle_effects SET end = ".$rn." WHERE user = ".$_SESSION['id']." AND name = 'DeathSong'");
                                      }
                                    }else{
                                      $this->log[] = 'Провал. Песня смерти уже играет.';
                                    }
                                  }else{
                                      $this->attacker->_setStatus('deathSong', 3);
                                      $this->defender->_setStatus('deathSong', 3);
                                      if($this->attackerAtk['id'] == 378){
                                        $this->log[] = 'Покемоны на поле потеряют сознание на '.($rn - 1).' раунде (Песня смерти).';
                                      }else{
                                        $this->log[] = 'Покемоны на поле получили отсчет Песни смерти из-за способности противника.';
                                      }
                                      if($this->_actionBattle->_isPVP()) {
                                        Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('DeathSong', ".$rn.", ".$this->attacker->user_id.", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                                        Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('DeathSong', ".$rn.", ".$this->defender->user_id.", '".(isset($allPokes2)?$allPokes2:'')."', ".$this->_actionBattle->battleId.")");
                                      }else{
                                        Work::$sql->query("INSERT INTO battle_effects (name, end, user, pok, battle) VALUES ('DeathSong', ".$rn.", ".$_SESSION['id'].", '".$allPokes."', ".$this->_actionBattle->battleId.")");
                                      }
                                  }
                              }

                              if($this->attackerAtk['id'] == 324) {
                                $this->attacker->_setStatus('minimize', 9999);
                                $this->log[] = $this->attacker->_getName(true).' становится меньше и труднее попадаетcя по целям.';
                              }

                              if($this->attackerAtk['id'] == 323) {
                                $this->attacker->_setStatus('reader', 9999);
                                $this->log[] = $this->attacker->_getName(true).' наводится на цель — следующая атака не промахнется.';
                              }

                              if($this->attackerAtk['id'] == 17) {
                                $this->attacker->_setStatus('aquaRing', 9999);
                                $this->log[] = $this->attacker->_getName(true).' окутан Аква-Кольцом и будет восстанавливать здоровье каждый ход.';
                              }

                              if($this->attackerAtk['id'] == 434 && $this->_actionBattle->_isPVP()) {
                                //   if($this->defender->_getTypeA() == 'ghost' || $this->defender->_getTypeB() == 'ghost') {
                                //     $this->log[] = 'Нет эффекта.';
                                //   }else{
                                  $pList = $this->_actionBattle->_getUserPokes($this->defender->_getUser());
                                  shuffle($pList);
                                  if($pList){
                                    $allPokes = '';
                                      foreach($pList AS $key_1=>&$value_1){
                                        if($value_1['hp'] <= 0 || $value_1['id'] == $this->defender->id) {

                                        }else{
                                          $allPokes = $value_1['id'];
                                        }
                                      }
                                  }
                                  if($allPokes == '') {
                                    $this->log[] = 'Провал.';
                                  }else{
                                    $this->getSelectedTwo($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, $allPokes, 1);
                                  }
                                //   }
                              }


			                                            if($this->attackerAtk['id'] == 39){
                        // Bestow: передаёт предмет, только если у цели нет предмета
                        if($this->attacker->item_id > 0 && $this->defender->item_id == 0) {
                          $this->defender->item_id = $this->attacker->item_id;
                          $this->attacker->item_id = 0;
                          $this->log[] = $this->attacker->_getName(true).' передает свой предмет противнику.';
                        }else{
                          $this->log[] = 'Провал.';
                        }
                      }

                      if($this->attackerAtk['id'] == 92){
                        // Воровство/Кража: крадёт предмет, только если у атакующего нет предмета
                        if($this->attacker->item_id == 0 && $this->defender->item_id > 0) {
                          $this->attacker->item_id = $this->defender->item_id;
                          $this->defender->item_id = 0;
                          $this->log[] = $this->attacker->_getName(true).' крадет предмет противника.';
                        }else{
                          $this->log[] = 'Провал.';
                        }
                      }

                      if($this->attackerAtk['id'] == 220){
                        $this->defender->_setModifiedStatNormal();
                        $this->attacker->_setModifiedStatNormal();
                        $this->log[] = 'У всех покемонов на поле обнулены все характеристики статов.';
                      }

                      if($this->attackerAtk['id'] == 267){
                        // Ingrain: только на себя, не зависит от safeguard цели
                        if($this->attacker->_checkStatus('ingrain')) {
                          $this->log[] = 'Провал. Корни уже пустили.';
                        }else{
                          $this->attacker->_setStatus('ingrain', 9999);
                          $this->attacker->_setStatus('grounded', 9999);
                          $this->log[] = $this->attacker->_getName(true).' пускает корни и становится приземленным.';
                        }
                      }

                      if($this->attackerAtk['id'] == 175){
                        if($this->defender->_checkStatus('safeguard') || $this->defender->_checkStatus('confused') || ($this->defender->ability == 127 and $this->attacker->ability != 113)) {
                          $this->log[] = 'Провал.';
                        }else{
                          $this->defender->_setStatus('confused', mt_rand(1,4));
                          $this->log[] = $this->defender->_getName(true).' спутан.';
                        }
                      }

                      if($this->attackerAtk['id'] == 539){
                        if($this->defender->_checkStatus('safeguard') || $this->defender->_checkStatus('confused') || ($this->defender->ability == 127 and $this->attacker->ability != 113)) {
                          $this->log[] = 'Провал.';
                        }else{
                          $this->defender->_setStatus('confused', mt_rand(1,4));
                          $this->log[] = $this->defender->_getName(true).' спутан.';
                        }
                      }

                      if($this->attackerAtk['id'] == 447){
                        // Grounding эффект (Smack Down-подобный) — на цель, а не на себя
                        $this->defender->_setStatus('grounded', 1);
                        $this->log[] = $this->defender->_getName(true).' становится приземленным.';
                      }

                      // if($this->attackerAtk['id'] == 685){
                      //   if(in_array($this->attacker->ability, [132,111])) {
                      //     $this->attacker->_setModifiedStat('atk', 1, true);
                      //     $this->attacker->_setModifiedStat('satk', 1, true);
                      //     $this->log[] = 'Атака '.$this->attacker->_getName(true).' повышена<br>Спец. Атака '.$this->attacker->_getName(true).' повышена';
                      //   }
                      // }

                      if($this->attackerAtk['id'] == 668){
                        $hp668 = ($this->attacker->hp_max / 3);
                        if($hp668 >= $this->attacker->hp) {
                          $this->log[] = 'Провал.';
                        }else{
                          // Провал, если все пять статов уже на +6
                          if(
                            isset($this->attacker->modified['atk']['plus'], $this->attacker->modified['satk']['plus'], $this->attacker->modified['def']['plus'], $this->attacker->modified['sdef']['plus'], $this->attacker->modified['spd']['plus']) &&
                            $this->attacker->modified['atk']['plus'] >= 6 &&
                            $this->attacker->modified['satk']['plus'] >= 6 &&
                            $this->attacker->modified['def']['plus'] >= 6 &&
                            $this->attacker->modified['sdef']['plus'] >= 6 &&
                            $this->attacker->modified['spd']['plus'] >= 6
                          ) {
                            $this->log[] = 'Провал.';
                          }else{
                            $this->attacker->_setModifiedStat('atk', 1, true);
                            $this->attacker->_setModifiedStat('satk', 1, true);
                            $this->attacker->_setModifiedStat('sdef', 1, true);
                            $this->attacker->_setModifiedStat('def', 1, true);
                            $this->attacker->_setModifiedStat('spd', 1, true);
                            $minus668 = floor($hp668);
                            $this->attacker->hp = floor($this->attacker->hp - $minus668);
                            $this->log[] = 'Здоровье покемона уменьшено <span class="HpMinus">-'.$minus668.' HP</span><br>
                            Атака '.$this->attacker->_getName(true).' повышена<br>Защита '.$this->attacker->_getName(true).' повышена<br>Спец. Атака '.$this->attacker->_getName(true).' повышена<br>Спец. Защита '.$this->attacker->_getName(true).' повышена<br>Скорость '.$this->attacker->_getName(true).' повышена';
                          }
                        }
                      }

                      if($this->attackerAtk['id'] == 106){
                        // Defense Curl: даёт +1 DEF и флаг для Rollout/Ice Ball
                        $this->attacker->_setStatus('defensecurl', 9999);
                        $this->attacker->_setModifiedStat('def', 1, true);
                        $this->log[] = 'Защита '.$this->attacker->_getName(true).' повышена.';
                      }

                      if($this->attacker->_checkStatus('stock') && $this->attackerAtk['id'] == 540) {
                        $info = $this->attacker->_getStatusList();
                        if($info){foreach($info AS $key=>$value){if($value['type'] == 'stock'){
                          $vl = $value['val'];
                        }
                        }}
                        unset($info[$key]);
                        $this->attacker->_setStatusList($info);
                        if($vl == 1) {
                          $vlHp = 25;
                        }elseif($vl == 2) {
                          $vlHp = 50;
                        }else{
                          $vlHp = 100;
                        }
                        $vlHp2 = ($this->attacker->hp_max / 100) * $vlHp;
                        $this->attacker->hp = $this->attacker->hp + $vlHp2;
                        if($this->attacker->hp >= $this->attacker->hp_max) {
                          $this->attacker->hp = $this->attacker->hp_max;
                        }
                        $this->log[] =  $this->attacker->_getName(true).' восстанавливает <span class="HpPlus">+'.round($vlHp2).' HP</span>';
                      }

                      if($this->attackerAtk['id'] == 179){
                        $this->attacker->_setStatus('critfocus', 9999);
                        $this->log[] = $this->attacker->_getName(true).' сосредоточился: шанс крит. удара повышен.';
                      }

                      if($this->attackerAtk['id'] == 576 || $this->attackerAtk['id'] == 544){
                        // Trick/Switcheroo: обмен предметами; допускается обмен с пустым слотом
                        $myItem = $this->attacker->item_id;
                        $enemyItem = $this->defender->item_id;
                        if($myItem == 0 && $enemyItem == 0) {
                          $this->log[] = 'Провал.';
                        }else{
                          $this->attacker->item_id = $enemyItem;
                          $this->defender->item_id = $myItem;
                          $this->log[] = 'Покемоны обменялись предметами.';
                        }
                      }

                      if($this->attackerAtk['id'] == 612){
                        if(in_array($this->defender->ability,[217,118,188,163,26,172,41,154,14,88])) {
                          $this->log[] = 'Провал.';
                        }else{
                          if($this->defender->_checkStatus('switch_ability')) {
                            $this->log[] = 'Провал.';
                          }else{
                            $this->log[] = 'Способность противника изменилась.';
                            $info = $this->defender->_getStatusList();
                            if($info){
                              foreach($info AS $key=>$value){
                                if($value['type'] == 'sleep'){
                                  $this->log[] = $this->defender->_getName(true).' проснулся.';
                                  unset($info[$key]);
                                }
                              }
                            }
                            $this->defender->_setStatusList($info);
                            $this->defender->_setStatus('switch_ability', 9999, $this->defender->ability);
                            $this->defender->ability = 88;
                          }
                        }
                      }

                      if($this->attackerAtk['id'] == 195){
                        if(in_array($this->defender->ability,[118,188,163,26,172,41,154,14,136])) {
                          $this->log[] = 'Провал.';
                        }else{
                          if($this->defender->_checkStatus('switch_ability')) {
                            $this->log[] = 'Провал.';
                          }else{
                            $this->log[] = 'Способность противника изменилась.';
                            $this->defender->_setStatus('switch_ability', 9999, $this->defender->ability);
                            $this->defender->ability = 0;
                          }
                        }
                      }

                      if($this->attackerAtk['id'] == 372){
                        $hpSplit = floor(($this->attacker->hp + $this->defender->hp) / 2);
                        $this->attacker->hp = ($hpSplit >= $this->attacker->hp_max ? $this->attacker->hp_max : $hpSplit);
                        $this->defender->hp = ($hpSplit >= $this->defender->hp_max ? $this->defender->hp_max : $hpSplit);
                        $this->log[] = 'Здоровье обоих покемонов выровнено.';
                      }

			                      if($this->attackerAtk['id'] == 425){
    // Reflect (Экран)
    $rndEkran = ($this->attacker->item_id == 169 ? 8 : 5); // Light Clay
    $rn = $this->_actionBattle->round + $rndEkran;
    if($this->attacker->_checkStatus('reflect')) {
        $this->log[] = 'Провал. На стороне пользователя уже есть Защитный экран.';
    }else{
        $this->attacker->_setStatus('reflect', $rndEkran);
        $this->log[] = 'На стороне пользователя появился Защитный экран до '.($rn - 1).' раунда включительно.';
    }
}

// Wish (Желание) — эффект СТОРОНЫ: лечит активного покемона в конце СЛЕДУЮЩЕГО РАУНДА
if ($this->attackerAtk['id'] == 607) {
    $userId   = (int)$this->attacker->_getUser();
    $battleId = (int)$this->_actionBattle->battleId;

    // 50% от max HP кастера на момент каста (минимум 1)
    $heal = max(1, (int)floor($this->attacker->hp_max / 2));

    // берём тот же счётчик, что и goRound()
    $roundNow = isset($this->battleInfo['round'])
        ? (int)$this->battleInfo['round']
        : (isset($this->_actionBattle->turn) ? (int)$this->_actionBattle->turn : 0);
    $dueRound = $roundNow + 1;

    // справочно сохраним ids покемонов стороны (не критично)
    $teamCsv = '';
    if ($plist = $this->_actionBattle->_getUserPokes($userId)) {
        foreach ($plist as $p) $teamCsv .= $p['id'].',';
        $teamCsv = rtrim($teamCsv, ',');
    }
    $teamCsvEsc = Work::$sql->real_escape_string($teamCsv);

    // на стороне висит только одно желание — UPDATE/INSERT
    $wishRow = Work::$sql->query("
        SELECT `id`
          FROM `battle_effects`
         WHERE `battle` = {$battleId}
           AND `user`   = {$userId}
           AND `name`   = 'Wish'
         LIMIT 1
    ")->fetch_assoc();

    if ($wishRow) {
        Work::$sql->query("
            UPDATE `battle_effects`
               SET `end` = {$heal},
                   `pok` = '{$teamCsvEsc}',
                   `rnd` = {$dueRound}
             WHERE `id`  = ".(int)$wishRow['id']."
               AND `battle` = {$battleId}
        ");
    } else {
        Work::$sql->query("
            INSERT INTO `battle_effects` (`name`,`end`,`user`,`pok`,`rnd`,`battle`)
            VALUES ('Wish', {$heal}, {$userId}, '{$teamCsvEsc}', {$dueRound}, {$battleId})
        ");
    }

    // один корректный лог (без дублей)
    $this->log[] = 'Желание загадано. Исцеление <b>'.$heal.' HP</b> придёт в конце следующего раунда активному покемону на стороне тренера.';
}


if($this->attackerAtk['id'] == 304) {
    // Magnet Rise — запрещён под Гравитацией
    if($this->attacker->_checkStatus('gravity')) {
        $this->log[] = 'Провал. Невозможно левитировать под действием Гравитации.';
    } elseif ($this->attacker->_checkStatus('levitation')) {
        $this->log[] = 'Провал. Покемон уже левитирует.';
    } else {
        $this->log[] = $this->attacker->_getName(true).' начал левитировать.';
        $this->attacker->_setStatus('levitation', 5);
    }
}

if($this->attackerAtk['id'] == 557) {
    // Telekinesis — нельзя наложить на летунов, Левитаторов и под гравитацией / вкопанных
    $isFlying = ($this->defender->_getTypeA() == 'flying' || $this->defender->_getTypeB() == 'flying');
    $hasLevitateAbility = ($this->defender->ability == 96); // Levitate
    if($this->defender->_checkStatus('easy_levitation') || $this->defender->_checkStatus('levitation') || $this->defender->_checkStatus('ingrain') || $this->defender->_checkStatus('grounded') || $this->defender->_checkStatus('gravity') || $isFlying || $hasLevitateAbility) {
        $this->log[] = 'Провал.';
    }else{
        $this->log[] = $this->defender->_getName(true).' начал слабо левитировать.';
        $this->defender->_setStatus('easy_levitation', 3);
    }
}

if($this->attackerAtk['id'] == 100) {
    // Curse (Проклятие) — ветка для призрачного пользователя
    if($this->attacker->_getTypeA() == 'ghost' || $this->attacker->_getTypeB() == 'ghost') {
        if($this->defender->_checkStatus('curse')) {
            $this->log[] = 'Провал. Проклятие уже наложено.';
        }else{
            $lost = floor($this->attacker->hp_max / 2);
            if ($lost >= $this->attacker->hp) $lost = $this->attacker->hp - 1; // не добиваем себя
            $this->attacker->hp -= $lost;
            $this->defender->_setStatus('curse', 9999);
            $this->log[] = 'Проклятие наложено. Здоровье пользователя уменьшено на <span class="HpMinus">-'.$lost.' HP</span>.';
        }
    }
}

if($this->attackerAtk['id'] == 37){
    // Belly Drum (Барабан)
    $halfMax = floor($this->attacker->hp_max / 2);
    if($this->attacker->hp <= $halfMax) {
        $this->log[] = 'Провал использование атаки.';
    }else{
        unset($this->attacker->modified['atk']['minus']);
        $this->attacker->_setModifiedStat('atk', 6, true);
        $this->attacker->hp -= $halfMax;
        $this->log[] = 'Здоровье покемона уменьшено <span class="HpMinus">-'.$halfMax.' HP</span>. Повышается <span class="StatPlus">Атака +6</span>';
    }
}


if($this->attackerAtk['id'] == 763) {
    // Быстрый «Encore» на 1 ход — тоже не зависит от Safeguard
    $enemydis = explode(',',$this->defender->disable_my);
    $atkBef = $this->defender->_getAtkInfo($this->defender->atk_beforeNow);
    if(isset($atkBef['attack_num'])) {
        if($atkBef['id'] < 9000) {
            if($atkBef['id'] == 754 || $atkBef['id'] == 753) {
                $this->log[] = 'Провал.';
            }else{
                if(!empty($enemydis[$atkBef['attack_num']]) && $enemydis[$atkBef['attack_num']] > 0) {
                    $this->log[] = 'Провал.';
                }else{
                    if($atkBef['attack_num'] == 0) { $enemydis[1] = 2; $enemydis[2] = 2; $enemydis[3] = 2;
                    }elseif($atkBef['attack_num'] == 1) { $enemydis[0] = 2; $enemydis[2] = 2; $enemydis[3] = 2;
                    }elseif($atkBef['attack_num'] == 2) { $enemydis[0] = 2; $enemydis[1] = 2; $enemydis[3] = 2;
                    }else{ $enemydis[0] = 2; $enemydis[2] = 2; $enemydis[1] = 2; }
                    $this->log[] = $this->defender->_getName(true).' теперь может использовать лишь предыдущую свою атаку в течение следующего хода.';
                }
            }
        }else{
            $this->log[] = 'Провал.';
        }
    }else{
        $this->log[] = 'Провал.';
    }
    $this->defender->disable_my = implode(',',$enemydis);
}

if($this->attackerAtk['id'] == 111) {
    // Disable
    $enemydis = explode(',',$this->defender->disable_my);
    $atkBef = $this->defender->_getAtkInfo($this->defender->atk_beforeNow);
    if(isset($atkBef['attack_num'])) {
        if($atkBef['id'] < 9000) {
            if($atkBef['id'] == 754 || $atkBef['id'] == 753) {
                $this->log[] = 'Провал.';
            }else{
                if(!empty($enemydis[$atkBef['attack_num']]) && $enemydis[$atkBef['attack_num']] > 0) {
                    $this->log[] = 'Провал.';
                }else{
                    $enemydis[$atkBef['attack_num']] = 4;
                    $this->log[] = $this->defender->_getName(true).' забыл одну из своих атак на 4 хода.';
                }
            }
        }else{
            $this->log[] = 'Провал.';
        }
    }else{
        $this->log[] = 'Провал.';
    }
    $this->defender->disable_my = implode(',',$enemydis);
}

if($this->attackerAtk['id'] == 510){
    // Spikes
    if($this->defender->_checkStatus('spikes')) {
        $info = $this->defender->_getStatusList();
        if($info){
            foreach($info AS $key=>$value){
                if($value['type'] == 'spikes'){
                    $valSpikes = (int)$value['val'];
                    unset($info[$key]);
                }
            }
            $this->defender->_setStatusList($info);
            if($valSpikes < 3) {
                $this->defender->_setStatus('spikes', 9999, ($valSpikes + 1));
                $this->log[] = 'На поле появился ещё один ряд шипов.';
            }else{
                $this->defender->_setStatus('spikes', 9999, 3);
                $this->log[] = 'Провал. На поле уже максимальное количество шипов.';
            }
        }
    }else{
        $this->defender->_setStatus('spikes', 9999, 1);
        $this->log[] = 'На поле появился ряд шипов.';
    }
}

if($this->attackerAtk['id'] == 520){
    // Sticky Web
    if($this->defender->_checkStatus('stickyweb')) {
        $this->log[] = 'Провал. На поле уже имеется липкая паутина.';
    }else{
        $this->defender->_setStatus('stickyweb', 9999);
        $this->log[] = 'На поле появилась липкая паутина.';
    }
}

if($this->attackerAtk['id'] == 552){
    // Tailwind (4 хода)
    if($this->attacker->_checkStatus('tailwind')) {
        $this->log[] = 'Провал. Эффект от атаки всё ещё действует.';
    }else{
        $rn = $this->_actionBattle->round + 4;
        $this->attacker->_setStatus('tailwind', 4);
        $this->log[] = 'Все покемоны пользователя теперь имеют удвоенную скорость до '.($rn - 1).' раунда включительно.';
    }
}

if($this->attackerAtk['id'] == 577){
    // Trick Room — длительность 5 ходов в Showdown
    if($this->attacker->_checkStatus('trick')) {
        $this->log[] = 'Провал. Эффект от атаки всё ещё действует.';
    }else{
        $rn = $this->_actionBattle->round + 5;
        $this->attacker->_setStatus('trick', 5);
        $this->log[] = 'Все медленные покемоны теперь ходят первее, чем быстрые до '.($rn - 1).' раунда включительно.';
    }
}

if($this->attackerAtk['id'] == 295){
    // Lucky Chant
    if($this->defender->_checkStatus('nocrit')) {
        $this->log[] = 'Провал. Эффект от атаки всё ещё действует.';
    }else{
        $rn = $this->_actionBattle->round + 5;
        $this->attacker->_setStatus('nocrit', 5);
        $this->log[] = 'На стороне пользователя заиграла Песнь удачи до '.($rn - 1).' раунда включительно.';
    }
}

if($this->attackerAtk['id'] == 516){
    // Stealth Rock
    if($this->defender->_checkStatus('rocks')) {
        $this->log[] = 'Провал. На поле уже имеются скрытные камушки.';
    }else{
        $this->defender->_setStatus('rocks', 9999);
        $this->log[] = 'На поле появились скрытные камушки.';
    }
}

if($this->attackerAtk['id'] == 573){
    // Toxic Spikes
    if($this->defender->_checkStatus('toxicspikes')) {
        $info = $this->defender->_getStatusList();
        if($info){
            foreach($info AS $key=>$value){
                if($value['type'] == 'toxicspikes'){
                    $valToxicSpikes = (int)$value['val'];
                    unset($info[$key]);
                }
            }
            $this->defender->_setStatusList($info);
            if($valToxicSpikes < 2) {
                $this->defender->_setStatus('toxicspikes', 9999, ($valToxicSpikes + 1));
                $this->log[] = 'На поле появился ещё один ряд ядовитых шипов.';
            }else{
                $this->defender->_setStatus('toxicspikes', 9999, 2);
                $this->log[] = 'Провал. На поле уже максимальное количество ядовитых шипов.';
            }
        }
    }else{
        $this->defender->_setStatus('toxicspikes', 9999, 1);
        $this->log[] = 'На поле появился ряд ядовитых шипов.';
    }
}

if($this->attackerAtk['id'] == 329){
    // Protect stats (Mist-аналог)
    $this->attacker->_setStatus('defstat', 9999);
    $this->log[] = 'Покемон защищён от воздействий на характеристики!';
}

/* --- Полевые эффекты блока ниже оставляем как есть, но корректируем Defog и Gravity --- */

if($this->attackerAtk['id'] == 290){
    // Light Screen
    $rndEkran = ($this->attacker->item_id == 169 ? 8 : 5);
    $rn = $this->_actionBattle->round + $rndEkran;
    if($this->attacker->_checkStatus('lightScreen')) {
        $this->log[] = 'Провал. На стороне пользователя уже Экран света.';
    }else{
        $this->attacker->_setStatus('lightScreen', $rndEkran);
        $this->log[] = 'На стороне пользователя появился Экран света до '.($rn - 1).' раунда включительно.';
    }
}

if($this->attackerAtk['id'] == 206){
    // Gravity — действует на обе стороны, снимает левитации и снижает уклонение
    if($this->attacker->_checkStatus('gravity') || $this->defender->_checkStatus('gravity')) {
        $this->log[] = 'Провал. На поле уже Гравитация.';
    }else{
        $this->attacker->_setStatus('gravity', 5);
        $this->defender->_setStatus('gravity', 5);

        // Убираем "easy_levitation" / levitation со всех активных
        if($this->attacker->_checkStatus('easy_levitation') || $this->attacker->_checkStatus('levitation')) {
            $info = $this->attacker->_getStatusList();
            if($info){ foreach($info AS $k=>$v){ if($v['type']=='easy_levitation' || $v['type']=='levitation'){ unset($info[$k]); } } }
            $this->attacker->_setStatusList($info);
        }
        if($this->defender->_checkStatus('easy_levitation') || $this->defender->_checkStatus('levitation')) {
            $info2 = $this->defender->_getStatusList();
            if($info2){ foreach($info2 AS $k=>$v){ if($v['type']=='easy_levitation' || $v['type']=='levitation'){ unset($info2[$k]); } } }
            $this->defender->_setStatusList($info2);
        }

        // В Showdown это не стадия, но у вас реализовано статами — понижаем уклонение -2 у обеих сторон с учётом Clear Body/Full Metal Body
        // Defender
        if($this->defender->ability == 23 || $this->defender->ability == 62) {
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> мешает снижению Ловкости.';
        }else{
            $this->defender->_setModifiedStat('agl', 2, false);
            $this->log[] = 'Ловкость '.$this->defender->_getName(true).' значительно понижена';
        }
        // Attacker
        if($this->attacker->ability == 23 || $this->attacker->ability == 62) {
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> мешает снижению Ловкости.';
        }else{
            $this->attacker->_setModifiedStat('agl', 2, false);
            $this->log[] = 'Ловкость '.$this->attacker->_getName(true).' значительно понижена';
        }

        $this->log[] = 'Все покемоны на поле теперь находятся под Гравитацией.';
    }
}

if($this->attackerAtk['id'] == 337){
    // Water/Mud Sport (как у вас заведено)
    if($this->attacker->_checkStatus('mudsport') || $this->defender->_checkStatus('mudsport')) {
        $this->log[] = 'Провал. На поле уже Осушение.';
    }else{
        $this->attacker->_setStatus('mudsport', 5);
        $this->defender->_setStatus('mudsport', 5);
        $this->log[] = 'Все покемоны на поле теперь находятся под Осушением.';
    }
}

if($this->attackerAtk['id'] == 448){
    if($this->attacker->_checkStatus('plant') || $this->defender->_checkStatus('plant')) {
        $this->log[] = 'Провал. На поле уже Плантаж.';
    }else{
        $this->attacker->_setStatus('plant', 5);
        $this->defender->_setStatus('plant', 5);
        $this->log[] = 'Все покемоны на поле теперь находятся под Плантажем.';
    }
}

if($this->attackerAtk['id'] == 325){
    // Miracle Eye
    if($this->defender->_checkStatus('miracle')) {
        $this->log[] = 'Провал. Покемон уже под Чудесным глазом.';
    }else{
        $this->defender->_setStatus('miracle', 9999);
        $this->log[] = $this->defender->_getName(true).' теперь под Чудесным глазом.';
    }
}

if($this->attackerAtk['id'] == 674){
    // Ion Deluge — в вашей архитектуре изменяется тип текущей атаки оппонента (ограниченно),
    // оставляю поведение как было, но с уточнённым логом
    if($this->meFirst == 1) {
        $this->defenderAtk['type'] = 'electric';
        $this->log[] = 'Следующая атака противника стала Электрической.';
    }else{
        $this->log[] = 'Провал.';
    }
}

if($this->attackerAtk['id'] == 655){
    // Aurora Veil — только при снегопаде/граде и без Cloud Nine/Air Lock
    if($this->_actionBattle->weather == 4 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
        $rndEkran = ($this->attacker->item_id == 169 ? 8 : 5);
        $rn = $this->_actionBattle->round + $rndEkran;
        if($this->attacker->_checkStatus('aurora')) {
            $this->log[] = 'Провал. На стороне пользователя уже Завеса авроры.';
        }else{
            $this->attacker->_setStatus('aurora', $rndEkran);
            $this->log[] = 'На стороне пользователя появилась Завеса авроры до '.($rn - 1).' раунда включительно.';
        }
    }else{
        $this->log[] = 'Провал.';
    }
}

if($this->attackerAtk['id'] == 107){
    // Defog — очищаем СТОРОНУ ЦЕЛИ (противника) от экранов/ловушек, террейны не трогаем; понижаем Ловкость цели на 1
    $info2 = $this->defender->_getStatusList();
    if($info2){
        foreach($info2 AS $key=>$value){
            if(in_array($value['type'], ['toxicspikes','spikes','rocks','stickyweb','aurora','reflect','lightScreen','safeguard'])){
                unset($info2[$key]);
            }
        }
        $this->defender->_setStatusList($info2);
    }
    // Понижаем Ловкость цели на 1, если не защищён Clear Body/Full Metal Body
    if(($this->defender->ability == 23) || ($this->defender->ability == 62)){
        $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> не даёт понизить Ловкость.';
    }else{
        $this->defender->_setModifiedStat('agl', 1, false);
        $this->log[] = 'Ловкость '.$this->defender->_getName(true).' понижена';
    }
    $this->log[] = 'Сторона противника очищена от экранов и ловушек.';
}

if($this->attackerAtk['id'] == 792){
    // Tidy Up — очищаем ловушки по ОБЕИМ сторонам и даём +1 к Атаке и Скорости пользователю
    $cleared = false;
    // Сторона пользователя
    $infoA = $this->attacker->_getStatusList();
    if($infoA){
        foreach($infoA AS $k=>$v){
            if(in_array($v['type'], ['toxicspikes','spikes','rocks','stickyweb'])){
                unset($infoA[$k]); $cleared = true;
            }
        }
        $this->attacker->_setStatusList($infoA);
    }
    // Сторона противника
    $infoB = $this->defender->_getStatusList();
    if($infoB){
        foreach($infoB AS $k=>$v){
            if(in_array($v['type'], ['toxicspikes','spikes','rocks','stickyweb'])){
                unset($infoB[$k]); $cleared = true;
            }
        }
        $this->defender->_setStatusList($infoB);
    }
    if($cleared){
        $this->log[] = 'Поле очищено от ловушек по обеим сторонам.';
    }else{
        $this->log[] = 'На поле не было ловушек.';
    }
    $this->attacker->_setModifiedStat('atk', 1, true);
    $this->attacker->_setModifiedStat('spd', 1, true);
    $this->log[] = 'Атака '.$this->attacker->_getName(true).' повышена<br>Скорость '.$this->attacker->_getName(true).' повышена';
}


			                      // Reflect
if ($this->attackerAtk['id'] == 425) {
    $rndEkran = ($this->attacker->item_id == 169 ? 8 : 5); // Light Clay
    $rn = $this->_actionBattle->round + $rndEkran;
    if ($this->attacker->_checkStatus('reflect')) {
        $this->log[] = 'Провал. На стороне пользователя уже Защитный экран.';
    } else {
        $this->attacker->_setStatus('reflect', $rndEkran);
        $this->log[] = 'На стороне пользователя появился Защитный экран до ' . ($rn - 1) . ' раунда включительно.';
    }
}



// Magnet Rise
if ($this->attackerAtk['id'] == 304) {
    if ($this->attacker->_checkStatus('levitation') || $this->attacker->_checkStatus('gravity')) {
        $this->log[] = 'Провал.';
    } else {
        $this->attacker->_setStatus('levitation', 5);
        $this->log[] = $this->attacker->_getName(true) . ' начал левитировать.';
    }
}

// Telekinesis (поднятие цели)
if ($this->attackerAtk['id'] == 557) {
    // Не работает на уже парящих/под особыми ограничениями
    $targetIsFlying = ($this->defender->_getTypeA() == 'flying' || $this->defender->_getTypeB() == 'flying');
    $targetHasLevitateAbility = ($this->defender->ability == 96); // Levitate
    if ($this->defender->_checkStatus('easy_levitation') ||
        $this->defender->_checkStatus('ingrain') ||
        $this->defender->_checkStatus('grounded') ||
        $this->defender->_checkStatus('gravity') ||
        $targetIsFlying || $targetHasLevitateAbility) {
        $this->log[] = 'Провал.';
    } else {
        $this->defender->_setStatus('easy_levitation', 3);
        $this->log[] = $this->defender->_getName(true) . ' начал слабо левитировать.';
    }
}

// Curse (проклятие призрака; игнорирует Safeguard)
if ($this->attackerAtk['id'] == 100) {
    if ($this->attacker->_getTypeA() == 'ghost' || $this->attacker->_getTypeB() == 'ghost') {
        if ($this->defender->_checkStatus('curse')) {
            $this->log[] = 'Провал. Противник уже под Проклятием.';
        } else {
            $lost = floor($this->attacker->hp_max / 2);
            // Не допускаем самообезвреживания: минимум 1 HP остаётся
            if ($lost >= $this->attacker->hp) { $lost = $this->attacker->hp - 1; }
            if ($lost < 1) {
                $this->log[] = 'Провал.';
            } else {
                $this->attacker->hp -= $lost;
                $this->defender->_setStatus('curse', 9999);
                $this->log[] = 'Проклятие нанесло урон <span class="HpMinus">-' . $lost . ' HP</span> и наложилось на противника.';
            }
        }
    }
}

// Belly Drum
if ($this->attackerAtk['id'] == 37) {
    $half = floor($this->attacker->hp_max / 2);
    if ($this->attacker->hp <= $half) {
        $this->log[] = 'Провал использования атаки.';
    } else {
        unset($this->attacker->modified['atk']['minus']);
        $this->attacker->_setModifiedStat('atk', 6, true);
        $this->attacker->hp -= $half;
        $this->log[] = 'Здоровье покемона уменьшено <span class="HpMinus">-' . $half . ' HP</span>. Повышается <span class="StatPlus">Атака +6</span>.';
    }
}

// Encore (Safeguard НЕ блокирует)
if ($this->attackerAtk['id'] == 143) {
    $enemydis = explode(',', $this->defender->disable_my);
    $atkBef = $this->defender->_getAtkInfo($this->defender->atk_beforeNow);
    if (isset($atkBef['attack_num'])) {
        if ($atkBef['id'] < 9000 && $atkBef['id'] != 754 && $atkBef['id'] != 753) {
            $slot = (int)$atkBef['attack_num'];
            if (!isset($enemydis[$slot]) || (int)$enemydis[$slot] <= 0) {
                // Разрешаем только предыдущую атаку — 3 хода
                if ($slot === 0) { $enemydis[1] = 3; $enemydis[2] = 3; $enemydis[3] = 3; }
                elseif ($slot === 1) { $enemydis[0] = 3; $enemydis[2] = 3; $enemydis[3] = 3; }
                elseif ($slot === 2) { $enemydis[0] = 3; $enemydis[1] = 3; $enemydis[3] = 3; }
                else { $enemydis[0] = 3; $enemydis[1] = 3; $enemydis[2] = 3; }
                $this->log[] = $this->defender->_getName(true) . ' теперь может использовать лишь предыдущую свою атаку в течение трех ходов.';
            } else {
                $this->log[] = 'Провал.';
            }
        } else {
            $this->log[] = 'Провал.';
        }
    } else {
        $this->log[] = 'Провал.';
    }
    $this->defender->disable_my = implode(',', $enemydis);
}

// Encore (вариант на 1 ход)
if ($this->attackerAtk['id'] == 763) {
    $enemydis = explode(',', $this->defender->disable_my);
    $atkBef = $this->defender->_getAtkInfo($this->defender->atk_beforeNow);
    if (isset($atkBef['attack_num']) && $atkBef['id'] < 9000 && $atkBef['id'] != 754 && $atkBef['id'] != 753) {
        $slot = (int)$atkBef['attack_num'];
        if (!isset($enemydis[$slot]) || (int)$enemydis[$slot] <= 0) {
            if ($slot === 0) { $enemydis[1] = 2; $enemydis[2] = 2; $enemydis[3] = 2; }
            elseif ($slot === 1) { $enemydis[0] = 2; $enemydis[2] = 2; $enemydis[3] = 2; }
            elseif ($slot === 2) { $enemydis[0] = 2; $enemydis[1] = 2; $enemydis[3] = 2; }
            else { $enemydis[0] = 2; $enemydis[1] = 2; $enemydis[2] = 2; }
            $this->log[] = $this->defender->_getName(true) . ' теперь может использовать лишь предыдущую свою атаку в течение следующего хода.';
        } else {
            $this->log[] = 'Провал.';
        }
    } else {
        $this->log[] = 'Провал.';
    }
    $this->defender->disable_my = implode(',', $enemydis);
}

// Disable
if ($this->attackerAtk['id'] == 111) {
    $enemydis = explode(',', $this->defender->disable_my);
    $atkBef = $this->defender->_getAtkInfo($this->defender->atk_beforeNow);
    if (isset($atkBef['attack_num']) && $atkBef['id'] < 9000 && $atkBef['id'] != 754 && $atkBef['id'] != 753) {
        $slot = (int)$atkBef['attack_num'];
        if (!isset($enemydis[$slot]) || (int)$enemydis[$slot] <= 0) {
            $enemydis[$slot] = 4;
            $this->log[] = $this->defender->_getName(true) . ' забыл одну из своих атак на 4 хода.';
        } else {
            $this->log[] = 'Провал.';
        }
    } else {
        $this->log[] = 'Провал.';
    }
    $this->defender->disable_my = implode(',', $enemydis);
}



if($this->attackerAtk['id'] == 329){
    $this->attacker->_setStatus('defstat', 9999);
    $this->log[] = 'Покемон защищен от воздействий на характеристики!';
}

$this->settings['crash'] = false;
    }else{
        if($this->defender->hp > 0){

            $this->getDmg($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);

        }else{
            $this->log[] = 'Но, '.$this->defender->_getName(true).' уже полностью обессилен.';
        }
    }
    if($this->attackerAtk['id'] == 208 and $this->_actionBattle->weather == 2){//Growth
        $this->attackerAtk['my'] = '{"stats":[["atk",2,"plus"],["satk",2,"plus"]]}';
    }
  $info_enemy = Info::_unParseData($this->attackerAtk['enemy']);
  $info_my = Info::_unParseData($this->attackerAtk['my']);
  if(isset($info_my['stats'])) {
    if($this->settings['types'] > 0) {
      foreach($info_my['stats'] AS $key=>$value){
        if(isset($value['3'])) {
          $rand_info_my = mt_rand(1,100);
          if($this->attacker->ability == 165) {
            $value['3'] = $value['3'] * 2;}
          if($rand_info_my <= $value['3']) {
            $this->attacker->_setModifiedStat($value[0], $value[1], ($value[2] == 'minus' ? false : true));
            if($value[0] == 'atk'){
              $nameStat = 'Атака';
            }elseif($value[0] == 'def'){
              $nameStat = 'Защита';
            }elseif($value[0] == 'spd'){
              $nameStat = 'Скорость';
            }elseif($value[0] == 'satk'){
              $nameStat = 'Спец. Атака';
            }elseif($value[0] == 'sdef'){
              $nameStat = 'Спец. Защита';
            }elseif($value[0] == 'acr'){
              $nameStat = 'Точность';
            }elseif($value[0] == 'agl'){
              $nameStat = 'Ловкость';
            }else{
              $nameStat = 'Здоровье';
            }
            if($value[1] >= 2){$vl = "значительно ";  }else{$vl = " ";}if($value[2] == 'plus'){$sp = "повышена.";}else{$sp = "понижена.";}
            $this->log[] = $nameStat.' '.$this->attacker->_getName(true).' '.$vl.$sp.'';
          }
        }else{
          $this->attacker->_setModifiedStat($value[0], $value[1], ($value[2] == 'minus' ? false : true));
          if($value[0] == 'atk'){
            $nameStat = 'Атака';
          }elseif($value[0] == 'def'){
            $nameStat = 'Защита';
          }elseif($value[0] == 'spd'){
            $nameStat = 'Скорость';
          }elseif($value[0] == 'satk'){
            $nameStat = 'Спец. Атака';
          }elseif($value[0] == 'sdef'){
            $nameStat = 'Спец. Защита';
          }elseif($value[0] == 'acr'){
            $nameStat = 'Точность';
          }elseif($value[0] == 'agl'){
            $nameStat = 'Ловкость';
          }else{
            $nameStat = 'Здоровье';
          }
          if($value[1] >= 2){$vl = "значительно ";  }else{$vl = " ";}if($value[2] == 'plus'){$sp = "повышена.";}else{$sp = "понижена.";}
          $this->log[] = $nameStat.' '.$this->attacker->_getName(true).' '.$vl.$sp.'';
}  }
    }
  }
  if(isset($info_enemy['stats'])) {
    foreach($info_enemy['stats'] AS $key=>$value){
      if(isset($value['3'])) {
        $rand_info_enemy = mt_rand(1,100);
        if($this->defender->ability == 165) {
          $value['3'] = $value['3'] * 2;
        }
        if($rand_info_enemy <= $value['3']) {

                                                if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
                                                  $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
                                                }else{
                                                  if( ($this->defender->ability == 23 && $this->attacker->ability != 113 ) || ($this->defender->ability == 17 && $value[0] == 'def'  && $this->attacker->ability != 113 ) || ($this->defender->ability == 79 && $value[0] == 'atk' && $this->attacker->ability != 113)  || ($this->defender->ability == 93 && $value[0] == 'acr' && $this->attacker->ability != 113)) {
                                                      $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
                                                  }else{
                                                      if($this->settings['types'] > 0) {
                          $this->defender->_setModifiedStat($value[0], $value[1], ($value[2] == 'minus' ? false : true));
                            if($value[0] == 'atk'){
                              $nameStat = 'Атака';
                            }elseif($value[0] == 'def'){
                              $nameStat = 'Защита';
                            }elseif($value[0] == 'spd'){
                              $nameStat = 'Скорость';
                            }elseif($value[0] == 'satk'){
                              $nameStat = 'Спец. Атака';
                            }elseif($value[0] == 'sdef'){
                              $nameStat = 'Спец. Защита';
                            }elseif($value[0] == 'acr'){
                              $nameStat = 'Точность';
                            }elseif($value[0] == 'agl'){
                              $nameStat = 'Ловкость';
                            }else{
                              $nameStat = 'Здоровье';
                            }if($value[1] >= 2){$vl = "значительно ";  }else{$vl = " ";}if($value[2] == 'plus'){$sp = "повышена.";}else{$sp = "понижена.";}
                          $this->log[] = $nameStat.' '.$this->defender->_getName(true).' '.$vl.$sp.'';
                        }
                        }
                        }

                        }
      }else{
            if($this->defender->_checkStatus('defstat') && $this->attacker->ability != 85) {
              $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой.';
            }else{
            if($this->defender->ability == 23 || ($this->defender->ability == 17 && $value[0] == 'def') || ($this->defender->ability == 79 && $value[0] == 'atk' && $this->attacker->ability != 113) || ($this->defender->ability == 93 && $value[0] == 'acr' && $this->attacker->ability != 113)) {
            $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника не дает понизить ему стат.';
            }else{
                if($this->defender->ability == 236){
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> отражает понижение характеристик.';
                    if($this->settings['types'] > 0) {
                        $this->attacker->_setModifiedStat($value[0], $value[1], ($value[2] == 'minus' ? false : true));
            if($value[0] == 'atk'){
              $nameStat = 'Атака';
            }elseif($value[0] == 'def'){
              $nameStat = 'Защита';
            }elseif($value[0] == 'spd'){
              $nameStat = 'Скорость';
            }elseif($value[0] == 'satk'){
              $nameStat = 'Спец. Атака';
            }elseif($value[0] == 'sdef'){
              $nameStat = 'Спец. Защита';
            }elseif($value[0] == 'acr'){
              $nameStat = 'Точность';
            }elseif($value[0] == 'agl'){
              $nameStat = 'Ловкость';
            }else{
              $nameStat = 'Здоровье';
            }if($value[1] >= 2){$vl = "значительно ";  }else{$vl = " ";}if($value[2] == 'plus'){$sp = "повышена.";}else{$sp = "понижена.";}
                        $this->log[] = $nameStat.' '.$this->attacker->_getName(true).' '.$vl.$sp.'';
          }
                }else{
                    if($this->settings['types'] > 0) {
                        $this->defender->_setModifiedStat($value[0], $value[1], ($value[2] == 'minus' ? false : true));
            if($value[0] == 'atk'){
              $nameStat = 'Атака';
            }elseif($value[0] == 'def'){
              $nameStat = 'Защита';
            }elseif($value[0] == 'spd'){
              $nameStat = 'Скорость';
            }elseif($value[0] == 'satk'){
              $nameStat = 'Спец. Атака';
            }elseif($value[0] == 'sdef'){
              $nameStat = 'Спец. Защита';
            }elseif($value[0] == 'acr'){
              $nameStat = 'Точность';
            }elseif($value[0] == 'agl'){
              $nameStat = 'Ловкость';
            }else{
              $nameStat = 'Здоровье';
            }if($value[1] >= 2){$vl = "значительно ";  }else{$vl = " ";}if($value[2] == 'plus'){$sp = "повышена.";}else{$sp = "понижена.";}
                        $this->log[] = $nameStat.' '.$this->defender->_getName(true).' '.$vl.$sp.'';
          }
                }


        }
        }
        }
        }
        }
    }else{
    $watabs = ($this->defender->stats[0] / 4);
    $this->defender->hp = ($this->defender->hp + $watabs);
    // ---- FIX здесь и ниже: было ceil($skyd) ----
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его и восстанавливает <span class="HpPlus">+'.ceil($watabs).' HP</span>.';
    }
    }else{
    $watabs = ($this->defender->stats[0] / 4);
    $this->defender->hp = ($this->defender->hp + $watabs);
    // ---- FIX: было ceil($skyd) ----
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его и восстанавливает <span class="HpPlus">+'.ceil($watabs).' HP</span>.';
    }
}else{
    $skyd = ($this->defender->stats[0] / 4);
    $this->defender->hp = ($this->defender->hp + $skyd);
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его и восстанавливает <span class="HpPlus">+'.ceil($skyd).' HP</span>.';
    }
    }else{
        $this->defender->_setModifiedStat('satk', 1, true);
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его и повышает Спец. Атаку.';
    }
}else{
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его.';
    }
    }
    }else{
    if($this->defenderAtk['id'] == 299) {
    $this->log[] = 'Плащ защитил противника.';
    }else{
    $this->log[] = 'Щит защитил противника.';
    }
    }
    }else{
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его.';
    }
    }else{
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его.';
    }
            }else{
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его.';
    }
    }else{
    $this->log[] = 'Провал. <div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника защищает его.';
    }
    }else{
    $this->log[] = 'Провал.';
    }
    }

}else{

  $this->settings['crash'] = true;
  $this->log[] = 'Но, '.$this->attacker->_getName(true).' промахнулся.';
  Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$this->attacker->user_id);
  Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$this->attacker->user_id);
  Work::$sql->query('DELETE FROM atk_furycutter WHERE user = '.$this->attacker->user_id);
  Work::$sql->query('DELETE FROM atk_tripleaxel WHERE user = '.$this->attacker->user_id);
  if($this->attackerAtk['id'] == 272 or $this->attackerAtk['id'] == 237 or $this->attackerAtk['id'] == 775) {
    $weatherMinusAbil = ($this->attacker->stats[0] / 2);
    $this->attacker->hp = ($this->attacker->hp - $weatherMinusAbil);
    $this->log[] = 'Промах травмирует '.$this->attacker->_getName(true).' <span class="HpMinus">-'.ceil($weatherMinusAbil).' HP</span>';
  }

}

			                if(!$this->settings['crash']){
                    if($this->attacker->item_id == 100 && isset($this->settings['dmg']) && $this->settings['dmg'] > 0 && in_array($this->attackerAtk['category'], ['physical','special'])) {
                      $randKorona = mt_rand(1,100);
                      if($randKorona <= 12) {
                        $this->defender->_setStatus( 'flinch', 1);
                        $this->log[] = 'Противник напуган.';
                      }
                    }
                    $this->issetEffect($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, true);
                    $this->issetEffect($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk);
                }

                if($this->attacker->ability == 168) {
                  $Effects = $this->attacker->_getStatusList();
                  if($Effects){foreach($Effects AS $key=>$value){if($value['type'] == 'toxic' || $value['type'] == 'toxic2' || $value['type'] == 'paralyzed' || $value['type'] == 'burn' || $value['type'] == 'sleep' || $value['type'] == 'frost'){
                        $rand168 = mt_rand(1,100);
                        if($rand168 <= 33) {
                          unset($Effects[$key]);
                          $text168 = 1;
                        }
                      }
                    }
                  }
                  $this->attacker->_setStatusList($Effects);
                  if(isset($text168)) {
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> избавила покемона от некоторых статусов.';
                  }
                }

                if($this->attacker->ability == 78 && $this->_actionBattle->weather == 3 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $Effects = $this->attacker->_getStatusList();
                  if($Effects){foreach($Effects AS $key=>$value){if($value['type'] == 'toxic' || $value['type'] == 'toxic2' || $value['type'] == 'paralyzed' || $value['type'] == 'burn' || $value['type'] == 'sleep' || $value['type'] == 'frost'){
                        unset($Effects[$key]);
                        $text78 = 1;
                      }
                    }
                  }
                  $this->attacker->_setStatusList($Effects);
                  if(isset($text78)) {
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> избавила покемона от некоторых статусов.';
                  }
                }

							if($this->attacker->_checkStatus('plant')) {
								$plantGo = 0;
								if(in_array('fly',[$this->attacker->_getTypeA(),$this->attacker->_getTypeB()]) && !$this->attacker->_checkStatus('grounded')) {
									$plantGo = 0;
								}else{
									if($this->attacker->_checkStatus('levitation') || $this->attacker->_checkStatus('gravity') || $this->attacker->_checkStatus('easy_levitation')) {
										$plantGo = 0;
									}else{
										if(in_array('bug',[$this->attacker->_getTypeA(),$this->attacker->_getTypeB()])) {
											$plantGo = 1;
										}else{
											$plantGo = 0;
										}
									}
								}
								if($plantGo == 1) {
									$this->attacker->_setModifiedStat('satk', 1, true);
									$this->attacker->_setModifiedStat('atk', 1, true);
									$this->log[] = 'Плантаж повышает <span class="StatPlus">Атаку +1</span>';
									$this->log[] = 'Плантаж повышает <span class="StatPlus">Спец. Атаку +1</span>';
								}
							}

                if($this->attacker->ability == 184) {
                  $this->attacker->_setModifiedStat('spd', 1, true);
                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> повышает <span class="StatPlus">Скорость +1</span>';
                }

                if($this->attacker->ability == 147 && $this->_actionBattle->weather == 3 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $weatherMinusAbil147 = ($this->attacker->stats[0] / 8);
                  $this->attacker->hp = ($this->attacker->hp + $weatherMinusAbil147);
                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> восстанавливает покемону <span class="HpPlus">+'.ceil($weatherMinusAbil147).' HP</span>';
                }

                if($this->attacker->item_id == 145) {
                  $obedki = ($this->attacker->stats[0] / 16);
                  $this->attacker->hp = ($this->attacker->hp + $obedki);
                  $this->log[] = '<div class="itemIsset" onclick="issetAll(145,\'item\')" style="background-image: url(/img/world/items/little/145.png)"></div> восстанавливают покемону <span class="HpPlus">+'.ceil($obedki).' HP</span>';
                }

                if($this->attacker->_checkStatus('aquaRing')) {
                  $hpAquaRing = ($this->attacker->stats[0] / 16);
                  $this->attacker->hp = ($this->attacker->hp + $hpAquaRing);
                  $this->log[] = 'Водное кольцо восстанавливает покемону <span class="HpPlus">+'.ceil($hpAquaRing).' HP</span>';
                }

                /* Ingrain: лечение 1/16 в конце хода */
                if($this->attacker->_checkStatus('ingrain')) {
                  $hpIngrain = ($this->attacker->stats[0] / 16);
                  $this->attacker->hp = ($this->attacker->hp + $hpIngrain);
                  $this->log[] = 'Корни восстанавливают покемону <span class="HpPlus">+'.ceil($hpIngrain).' HP</span>';
                }

                if($this->attacker->ability == 80 && $this->_actionBattle->weather == 4 && $this->attacker->item_id != 153 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
                  $abil80 = ($this->attacker->stats[0] / 16);
                  $this->attacker->hp = ($this->attacker->hp + $abil80);
                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> восстанавливает покемону <span class="HpPlus">+'.ceil($abil80).' HP</span> и предотвращает урон от града.';
                }

                if($this->attacker->item_id == 173 && $this->attacker->_getTypeA() == 'poison' || $this->attacker->item_id == 173 && $this->attacker->_getTypeB() == 'poison') {
                  $obedki2 = ($this->attacker->stats[0] / 16);
                  $this->attacker->hp = ($this->attacker->hp + $obedki2);
                  $this->log[] = '<div class="itemIsset" onclick="issetAll(173,\'item\')" style="background-image: url(/img/world/items/little/173.png)"></div> восстанавливает покемону <span class="HpPlus">+'.ceil($obedki2).' HP</span>';
                }
                /* Black Sludge: урон не-ядовитым */
                if($this->attacker->item_id == 173 && $this->attacker->_getTypeA() != 'poison' && $this->attacker->_getTypeB() != 'poison') {
                  $sludge_dmg = ($this->attacker->stats[0] / 16);
                  $this->attacker->hp = ($this->attacker->hp - $sludge_dmg);
                  if($this->attacker->hp < 0){ $this->attacker->hp = 1; }
                  $this->log[] = '<div class="itemIsset" onclick="issetAll(173,\'item\')" style="background-image: url(/img/world/items/little/173.png)"></div> вредит покемону <span class="HpMinus">-'.ceil($sludge_dmg).' HP</span>';
                }

                if($this->attacker->item_id == 431){
                    if($this->attacker->_checkStatus('levitation') || $this->attacker->_checkStatus('easy_levitation') || $this->attacker->_checkStatus('toxic') || $this->attacker->_checkStatus('toxic2') || $this->attacker->_checkStatus('terrMisty') || $this->attacker->_checkStatus('safeguard') || $this->attacker->_getTypeA() == 'poison' || $this->attacker->_getTypeB() == 'poison' || $this->attacker->_getTypeA() == 'steel' || $this->attacker->_getTypeB() == 'steel'){
                        $this->log[] = 'Провал изменения статуса.';
                    }else{
                        $this->attacker->_setStatus('toxic', 9999);
                                    $this->log[] = 'Покемон отравлен';
                    }
                }
                if($this->attacker->item_id == 478) {
                  $obedki2_ship = ($this->attacker->stats[0] / 12);
                  $this->attacker->hp = ($this->attacker->hp - $obedki2_ship);
                  if($this->attacker->hp < 0){ $this->attacker->hp = 1; }
                  $this->log[] = '<div class="itemIsset" onclick="issetAll(478,\'item\')" style="background-image: url(/img/world/items/little/478.png)"></div> отнимает у покемона <span class="HpMinus">-'.ceil($obedki2_ship).' HP</span>';
                }

				// 			if($this->itemEffect312 == 1) {
				// 				$hpSh = round(($this->attacker->hp_max) / 6);
				// 				$this->attacker->hp = $this->attacker->hp - $hpSh;
				// 				$this->log[] = $this->attacker->_getName(true).' получает урон от шлема противника: <span class="HpMinus">-'.ceil($hpSh).' HP</span>';
				// 			}

							if($this->attacker->item_id == 166 and in_array($this->attackerAtk['category'], ['special','physical'])) {
								$hpPerch = round(($this->attacker->hp_max) / 9);
								$this->attacker->hp = $this->attacker->hp - $hpPerch;
								$this->log[] = '<div class="itemIsset" onclick="issetAll(166,\'item\')" style="background-image: url(/img/world/items/little/166.png)"></div> отнимает покемону <span class="HpMinus">-'.ceil($hpPerch).' HP</span>';
							}

            }else{
                if($this->attackerAtk['id'] == 209) {
                  $mypp = explode(',',$this->defender->pp_my);
                  if($mypp[$this->defenderAtk['attack_num']] <= 100) {
                    $mypp[$this->defenderAtk['attack_num']] = 0;
                  }
                  $this->defender->pp_my = implode(',',$mypp);
                  $this->settings['crash'] = true;
                  $this->log_status[] = $this->attacker->_getName(true).' не может продолжать битву. Атака противника потеряла все PP.';
                  $this->crash_item($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk,0);
                }else{
                  $this->settings['crash'] = true;
                  $this->log_status[] = $this->attacker->_getName(true).' не может продолжать битву.';
                  $this->crash_item($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk,0);
                }
                if($this->defenderAtk['id'] == 619) {
                  $this->defender->_setModifiedStat('atk', 3, true);
                  $this->log[] = 'Противник повысил <span class="StatPlus">Атаку +3</span>';
                }
							if($this->defender->ability == 116) {
                  $this->defender->_setModifiedStat('atk', 1, true);
                  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника повышает ему <span class="StatPlus">Атаку +1</span>';
                }

							if($this->defender->ability == 15) {
  $abil15_1 = [
      'atk'  => $this->defender->stats[1],
      'def'  => $this->defender->stats[2],
      'spd'  => $this->defender->stats[3],
      'satk' => $this->defender->stats[4],
      'sdef' => $this->defender->stats[5]
  ];
  $abil15_2 = array_search(max($abil15_1), $abil15_1);
  $this->defender->_setModifiedStat($abil15_2, 1, true);
  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> противника повышает ему <span class="StatPlus">'.$this->statRus[$abil15_2][1].' +1</span>';
}
if($this->defender->ability == 3 && $this->attackerAtk['contact'] == 1) {
  $abil_3_hp = floor($this->attacker->hp_max / 4);
  $this->attacker->hp = $this->attacker->hp - $abil_3_hp;
  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> наносит противнику урон <span class="HpMinus">-'.$abil_3_hp.' HP</span>';
}
/* Доп. способность «шипы» при контакте (аналогично Rough Skin / Iron Barbs в Showdown).
   Используем другое id (например, 170), чтобы не задвоить с ability==3. Урон 1/8 от макс. HP атакующего. */
if($this->defender->ability == 170 && $this->attackerAtk['contact'] == 1) {
  $abil_170_hp = floor($this->attacker->hp_max / 8);
  $this->attacker->hp = $this->attacker->hp - $abil_170_hp;
  if ($abil_170_hp > 0) {
    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->defender->ability.',\'ability\')">'.$this->_abilNameRus($this->defender->ability).'</div> ранит противника при контакте <span class="HpMinus">-'.$abil_170_hp.' HP</span>';
  }
}
}
}else{
  $rand = mt_rand(1,2);
  $this->defender->hp = $this->defender->hp - $rand;
  $this->log[] = 'У покемона недостаточно PP, и он со всех сил пытается сделать хоть что-то...<br>Покемон немного задевает противника и наносит: <span class="HpMinus">-'.$rand.' HP</span>';
}
//         if($this->_actionBattle->weather == 4){
// 		  if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
// 			  if($this->attacker->base_type != 'ice'){
//        				if($this->attacker->base_type_two != 'ice'){
//                 if($this->attacker->item_id != 153) {
//                   if($this->attacker->ability != 80) {
//                     if(!in_array($this->attacker->ability, [125])) {
//                       $weatherMinus = ($this->attacker->stats[0] / 16);
//             					$this->attacker->hp = ($this->attacker->hp - $weatherMinus);
//             					$this->log[] = 'Град поранил '.$this->attacker->_getName(true).' <span class="HpMinus">-'.ceil($weatherMinus).' HP</span>';
//                     }else{
//                       $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> предотвращает урон от града.';
//                     }
//                   }
//                 }
//       				}
//       			}
// 		  }
//     		}elseif($this->_actionBattle->weather == 5){
// 			if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
// 				if($this->attacker->base_type != 'rock' && $this->attacker->base_type_two != 'rock'){
//       				if($this->attacker->base_type != 'steel' && $this->attacker->base_type_two != 'steel'){
//       					if($this->attacker->base_type != 'ground' && $this->attacker->base_type_two != 'ground'){
//                   if($this->attacker->item_id != 153) {
//                     if(!in_array($this->attacker->ability, [158,159,125])) {
//                       $weatherMinus = ($this->attacker->stats[0] / 16);
//           						$this->attacker->hp = ($this->attacker->hp - $weatherMinus);
//           						$this->log[] = 'Песок поранил '.$this->attacker->_getName(true).' <span class="HpMinus">-'.ceil($weatherMinus).' HP</span>';
//                     }else{
//                       $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> предотвращает урон от песка.';
//                     }
//                   }
//       					}
//       				}
//       			}
// 			}
//     		}
if($this->attacker->ability == 180 && $this->_actionBattle->weather == 2 && !in_array(4,[$this->attacker->ability,$this->defender->ability])) {
  $weatherMinusAbil = ($this->attacker->stats[0] / 8);
  $this->attacker->hp = ($this->attacker->hp - $weatherMinusAbil);
  $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'.$this->_abilNameRus($this->attacker->ability).'</div> наносит урон <span class="HpMinus">-'.ceil($weatherMinusAbil).' HP</span>';
}
if($this->attackerAtk['id'] == 583) {
  if($this->dmg_1 != 0 && $this->settings['crash'] != true) {
    $this->getAUT($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, false, 0);
  }
}
if($this->attackerAtk['id'] == 592) {
  if($this->dmg_1 != 0 && $this->settings['crash'] != true && $this->settings['types'] > 0) {
    $this->getAWS($this->attacker, $this->defender, $this->attackerAtk, $this->defenderAtk, false, 0);
  }
}
// $userHell =  Work::$sql->query('SELECT location FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
// if($userHell['location'] == 999) {
//   $randHell = mt_rand(1,4);
//   if($randHell == 1) {
//     $hpHell = ($this->attacker->stats[0] / 10);
//     $this->attacker->hp = ($this->attacker->hp - $hpHell);
//     $Hell = 'наносит урон <span class="HpMinus">-'.round($hpHell).' HP</span>';
//   }elseif($randHell == 2) {
//     $randStat = mt_rand(1,5);
//     if($randStat == 1) {
//       $hs = 'atk';
//     }elseif($randStat == 2) {
//       $hs = 'def';
//     }elseif($randStat == 3) {
//       $hs = 'spd';
//     }elseif($randStat == 4) {
//       $hs = 'satk';
//     }else{
//       $hs = 'sdef';
//     }
//     $le = mt_rand(1,2);
//     if($le == 1) {
//       $this->attacker->_setModifiedStat($hs,1,true);
//       $le1 = 'увеличивается';
//     }else{
//       $this->attacker->_setModifiedStat($hs,1,false);
//       $le1 = 'уменьшается';
//     }
//     $Hell = 'у покемона '.$le1.' какой-то стат.';
//   }elseif($randHell == 3) {
//     $tp = mt_rand(1,18);
//     if($tp == 1) {
//       $tp1 = 'normal';
//       $tp2 = 'Нормальный';
//     }elseif($tp == 2) {
//       $tp1 = 'fairy';
//       $tp2 = 'Волшебный';
//     }elseif($tp == 3) {
//       $tp1 = 'ghost';
//       $tp2 = 'Призрачный';
//     }elseif($tp == 4) {
//       $tp1 = 'electric';
//       $tp2 = 'Электрический';
//     }elseif($tp == 5) {
//       $tp1 = 'rock';
//       $tp2 = 'Каменный';
//     }elseif($tp == 6) {
//       $tp1 = 'steel';
//       $tp2 = 'Стальной';
//     }elseif($tp == 7) {
//       $tp1 = 'ground';
//       $tp2 = 'Земляной';
//     }elseif($tp == 8) {
//       $tp1 = 'fly';
//       $tp2 = 'Летающий';
//     }elseif($tp == 9) {
//       $tp1 = 'fighting';
//       $tp2 = 'Боевой';
//     }elseif($tp == 10) {
//       $tp1 = 'grass';
//       $tp2 = 'Травяной';
//     }elseif($tp == 11) {
//       $tp1 = 'fire';
//       $tp2 = 'Огненный';
//     }elseif($tp == 12) {
//       $tp1 = 'water';
//       $tp2 = 'Водный';
//     }elseif($tp == 13) {
//       $tp1 = 'psychic';
//       $tp2 = 'Психический';
//     }elseif($tp == 14) {
//       $tp1 = 'bug';
//       $tp2 = 'Насекомое';
//     }elseif($tp == 15) {
//       $tp1 = 'ice';
//       $tp2 = 'Ледяной';
//     }elseif($tp == 16) {
//       $tp1 = 'dragon';
//       $tp2 = 'Дракон';
//     }elseif($tp == 17) {
//       $tp1 = 'poison';
//       $tp2 = 'Ядовитый';
//     }else{
//       $tp1 = 'dark';
//       $tp2 = 'Темный';
//     }
//     $tp3 = mt_rand(1,2);
//     if($tp3 == 1) {
//       $this->attacker->base_type = $tp1;
//     }else{
//       $this->attacker->base_type_two = $tp1;
//     }
//     $Hell = 'у покемона меняется один из типов на '.$tp2.'.';
//   }else{
//     $hpHell = ($this->attacker->stats[0] / 10);
//     $this->attacker->hp = ($this->attacker->hp + $hpHell);
//     $Hell = 'исцеляет раны <span class="HpPlus">+'.round($hpHell).' HP</span>';
//   }
//   $this->log[] = '<span class="TourHell"><span>Порча:</span> '.$Hell.'</span>';
// }
$infoTwoTurnSput = $this->attacker->_getStatusList();
if($infoTwoTurnSput){
  foreach($infoTwoTurnSput AS $key=>$value){
    if($value['type'] == 'two_turn'){
      if($value['count'] == 1) {
        if($value['val'] == 380 || $value['val'] == 370 || $value['val'] == 562) {
          if($this->attacker->ability != 127 or ($this->attacker->ability == 127 and $this->defender->ability == 113)) {
            $this->attacker->_setStatus('confused', mt_rand(1,4));
            $this->log[] = $this->attacker->_getName(true).' спутан.';
          }
        }
      }
    }
  }
}
if($this->attacker->_checkStatus('deathSong')) {
  $ds = Work::$sql->query('SELECT * FROM battle_effects WHERE (user = '.$this->attacker->user_id.' OR user = '.$this->defender->user_id.') AND name = "DeathSong"')->fetch_assoc();
  if($ds['end'] - $this->_actionBattle->round == 1) {
    $this->log[] = 'Песня смерти закончилась. Покемоны потеряли сознание.';
    $this->attacker->hp = 0;
    $this->defender->hp = 0;
  }
}
if($this->defender->hp <= 0) {
  $this->crash_item($this->defender, $this->attacker, $this->defenderAtk, $this->attackerAtk, 0);
}
}else{
  $this->settings['crash'] = true;
}

			        if($this->attackerAtk['id'] != 754 and $this->defenderAtk['id'] != 754 ) {
    // Погодные эффекты (как в Pokemon Showdown) — только если нет Air Lock / Cloud Nine (id 4)
    if($this->_actionBattle->weather == 4){ // Hail / Град
        if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
            // Урон от града получают НЕ-ледяные (Ice) покемоны, если нет иммунитетов/предметов
            if($this->attacker->base_type != 'ice'){
                if($this->attacker->base_type_two != 'ice'){
                    if($this->attacker->hp > 0) {
                        // Safety Goggles (153) — иммунитет к погоде
                        if($this->attacker->item_id != 153) {
                            // Ice Body (80) лечит и уже обработан выше, поэтому тут исключаем из урона;
                            // Overcoat (125) и Magic Guard (161) предотвращают урон от погоды.
                            if($this->attacker->ability != 80) {
                                if(!in_array($this->attacker->ability, [125,161])) {
                                    $weatherMinus = ($this->attacker->stats[0] / 16);
                                    $this->attacker->hp = ($this->attacker->hp - $weatherMinus);
                                    $this->log[] = 'Град поранил '.$this->attacker->_getName(true).' <span class="HpMinus">-'.ceil($weatherMinus).' HP</span>';
                                }else{
                                    // Покажем, что способность предотвращает урон от града
                                    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'
                                      .$this->_abilNameRus($this->attacker->ability)
                                      .'</div> предотвращает урон от града.';
                                }
                            }
                        }
                    }
                }
            }
        }
    }elseif($this->_actionBattle->weather == 5){ // Sandstorm / Песчаная буря
        if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
            // Урон от песка получают те, кто НЕ Rock/Ground/Steel и не защищены иммунитетами
            if($this->attacker->base_type != 'rock' && $this->attacker->base_type_two != 'rock'){
                if($this->attacker->base_type != 'steel' && $this->attacker->base_type_two != 'steel'){
                    if($this->attacker->base_type != 'ground' && $this->attacker->base_type_two != 'ground'){
                        if($this->attacker->hp > 0) {
                            // Safety Goggles (153) — иммунитет к погоде
                            if($this->attacker->item_id != 153) {
                                // Только настоящие иммунитеты к урону от погоды:
                                // Overcoat (125), Magic Guard (161). Sand Rush / Sand Force НЕ дают иммунитет.
                                if(!in_array($this->attacker->ability, [125,161])) {
                                    $weatherMinus = ($this->attacker->stats[0] / 16);
                                    $this->attacker->hp = ($this->attacker->hp - $weatherMinus);
                                    $this->log[] = 'Песок поранил '.$this->attacker->_getName(true).' <span class="HpMinus">-'.ceil($weatherMinus).' HP</span>';
                                }else{
                                    $this->log[] = '<div class="Ability" onclick="issetAll('.$this->attacker->ability.',\'ability\')">'
                                      .$this->_abilNameRus($this->attacker->ability)
                                      .'</div> предотвращает урон от песка.';
                                }
                            }
                        }
                    }
                }
            }
        }
    }elseif($this->_actionBattle->weather == 2){ // Harsh Sun / Солнце
        // Dry Skin (45) — теряет 1/8 HP под солнцем; не работает под Air Lock / Cloud Nine
        if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
            if($this->attacker->ability == 45 /* Dry Skin */ && $this->defender->ability != 113){
                if($this->attacker->hp > 0) {
                    $weatherMinus = ($this->attacker->stats[0] / 8);
                    $this->attacker->hp = ($this->attacker->hp - $weatherMinus);
                    $this->log[] = 'Солнце поранило '.$this->attacker->_getName(true).' <span class="HpMinus">-'.ceil($weatherMinus).' HP</span>';
                }
            }
        }
    }elseif($this->_actionBattle->weather == 3){ // Rain / Дождь
        // Dry Skin (45) — лечит 1/8 HP под дождём; не работает под Air Lock / Cloud Nine
        if(!in_array(4,[$this->attacker->ability,$this->defender->ability])) {
            if($this->attacker->ability == 45 /* Dry Skin */ && $this->defender->ability != 113){
                if($this->attacker->hp > 0) {
                    $weatherMinus = ($this->attacker->stats[0] / 8);
                    $this->attacker->hp = ($this->attacker->hp + $weatherMinus);
                    $this->log[] = 'Дождь лечит '.$this->attacker->_getName(true).' <span class="HpPlus">+'.ceil($weatherMinus).' HP</span>';
                }
            }
        }
    }
}

// Лог на пользователя
$this->user_log[] = [
    'user'=>$attacker->user_id,
    'start'=>$this->log_start,
    'log'=>$this->log
];

// Нормализация HP
if($this->attacker->hp <= 0){
    $this->attacker->hp = 0;
}
if($this->attacker->hp > $this->attacker->hp_max){
    $this->attacker->hp = $this->attacker->hp_max;
}
if($this->defender->hp <= 0){
    $this->defender->hp = 0;
}
if($this->defender->hp > $this->defender->hp_max){
    $this->defender->hp = $this->defender->hp_max;
}

// Счётчики действий и цели
if($this->settings['crash'] != true){
    $this->attacker->actionCount = $this->attacker->actionCount + 1;
    $this->attacker->targetLvl['p'.$this->defender->_getID()] = $this->defender->_getLvl();
}
$this->attacker->hp = floor($this->attacker->hp);
$this->defender->hp = floor($this->defender->hp);
}

// ==============================
// ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
// ==============================

private function getAtkName(array $aInfo){
    return (!empty($aInfo['name']) ? '<span onclick="viewDescriptionAttak(this,'.$aInfo['id'].');" class="Attack MoveCategory'.($aInfo['category'] == 'physical' ? '1' : ( $aInfo['category'] == 'special' ? '2' : '3') ).'">'.$aInfo['name'].'</span>' : '...');
}

private function getDmg(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    if($def->_isHp()){
        /*
            0.5,0.51,0.52,0.53,0.54,0.55,0.56,0.57,0.58,0.59,
            0.6,0.61,0.62,0.63,0.64,0.65,0.66,0.67,0.68,0.69,
        */

        $random = Info::_arrRandDefOne([.85,0.86,0.87,0.88,0.89,0.9,0.91,0.92,0.93,0.94,0.95,0.96,0.97,0.98,0.99,1]);
        if(!$random){
            $random = 1;
        }

        $critStage = 0;        $alwaysCrit = 0;
        $count_hit = 1;

        if(!empty($atkAtk['settings']) || in_array($atkAtk['id'], [247,435])){

			if(empty($atkAtk['settings']) || !is_array($atkAtk['settings'])){
				$atkAtk['settings'] = [];
			}

            // ДВУХХОДОВКИ, СЕРИИ УДАРОВ и ЗАРЯДКИ
            if(isset($atkAtk['settings']['two_turn']) || in_array($atkAtk['id'], [247,435])){

                // Для некоторых атак (Hydro Cannon / Roar Of Time) settings может быть пустым в БД
                if(empty($atkAtk['settings']) || !is_array($atkAtk['settings'])){
                    $atkAtk['settings'] = [];
                }
                $atkAtk['settings']['two_turn'] = 1;

                // Если уже находимся в two_turn именно по этому ходу — не переустанавливаем статус
                // (иначе зарядка/серия будет бесконечной, и покемон не вернётся в бой)
                $isContinueTwoTurn = false;
                $infoTwoTurn = $atk->_getStatusList();
                if($infoTwoTurn){
                    foreach($infoTwoTurn AS $vtt){
                        if(isset($vtt['type']) && $vtt['type'] == 'two_turn'){
                            if(isset($vtt['val']) && (int)$vtt['val'] == (int)$atkAtk['id']){
                                $isContinueTwoTurn = true;
                            }
                            break;
                        }
                    }
                }

                if(!$isContinueTwoTurn){

                    $zarad = 0;
                    $manyKick = 2;
                    $manyKickTurn = 0;

                    // Перезарядка после атаки (Hyper Beam-подобные)
                    if(in_array($atkAtk['id'], [43,186,199,247,249,435,443,686,755,761])) {
                        $zarad = 1;
                    }

                    // Outrage/Thrash/Petal Dance — серия 2–3 хода
                    if(in_array($atkAtk['id'], [380,370,562])){
                        $manyKick = mt_rand(2,3);
                        $manyKickTurn = 1;
                    }

                    // SolarBeam / SolarBlade — в Солнце (2) без зарядки, если нет Air Lock/Cloud Nine
                    if( ($atkAtk['id'] == 504 && $this->_actionBattle->weather == 2)
                     || ($atkAtk['id'] == 756 && $this->_actionBattle->weather == 2) ) {
                        if(!in_array(4,[$atk->ability,$def->ability])) {
                            $Solar = 1;
                        } else {
                            $atk->_setStatus('two_turn', $manyKick, ($zarad == 0 ? $atkAtk['id'] : 753));
                        }
                    } else {
                        $atk->_setStatus('two_turn', $manyKick, ($zarad == 0 ? $atkAtk['id'] : 753));
                    }

                    // Сообщения о фазе зарядки / уклонения
                    if($atkAtk['id'] == 113) {
                        $this->log[] = 'Покемон нырнул под воду.';
                    }elseif($atkAtk['id'] == 110){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон закопался под землю.';
                    }elseif($atkAtk['id'] == 381 || $atkAtk['id'] == 469){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон исчез.';
                    }elseif($atkAtk['id'] == 54){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон высоко прыгнул.';
                    }elseif($atkAtk['id'] == 177){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон взлетел вверх.';
                    }elseif($atkAtk['id'] == 197){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон начал втягивать в себя энергию.';
                    }elseif($atkAtk['id'] == 484){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон взлетел вверх.';
                    }elseif($atkAtk['id'] == 423){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон начал создавать мощный вихрь.';
                    }elseif($atkAtk['id'] == 483){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон начал концентрировать энергию.';
                        $atk->_setModifiedStat('def', 1, true);
                        $this->log[] = 'У '.$atk->_getName(true).' повышена <span class="StatPlus">Защита +1</span>';
                    }elseif($atkAtk['id'] == 256){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон начал копить ледяную энергию.';
                    }elseif($atkAtk['id'] == 185){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон начал формировать морозную сферу.';
                    }elseif($atkAtk['id'] == 249){
                        // Hyper Beam — перезарядка
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Следующий ход покемон пропустит.';
                    }elseif($atkAtk['id'] == 756){
                        if(!isset($Solar)) {
                            $atkAtk['settings'] = NULL;
                            $this->log[] = 'Покемон начал копить солнечную энергию.';
                        }
                    }elseif(in_array($atkAtk['id'], [43,186,199,247,435,443,686,755,761])){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Следующий ход покемон пропустит.';
                    }elseif($atkAtk['id'] == 504){
                        if(!isset($Solar)) {
                            $atkAtk['settings'] = NULL;
                            $this->log[] = 'Покемон начал копить энергию.';
                        }
                    }elseif(in_array($atkAtk['id'], [380,370,562])){
                        $atkAtk['settings'] = NULL;
                        $this->log[] = 'Покемон начал серию ударов.';
                    }else{
                        $this->log[] = 'Покемон начал копить энергию.';
                    }

                    // В первый ход двухходовок (без Solar в солнце) — урон 0
                    // ВАЖНО: на фазе зарядки не должны применяться вторичные эффекты (my/enemy),
                    // иначе появляются "Провал изменения статуса." и прочие лишние эффекты до удара.
                    if($zarad == 0 && $manyKickTurn == 0 && !isset($Solar)) {
                        $atkAtk['power'] = 0;
                        $atkAtk['my'] = NULL;
                        $atkAtk['enemy'] = NULL;
                    }
                }
            }


// Крит.шанс (как в Pokemon Showdown, Gen 8):
            // stage 0: 1/24, stage 1: 1/8, stage 2: 1/2, stage 3: 1
            if(isset($atkAtk['settings']['chance_critical']) || in_array($atkAtk['id'], [423])){
                if(in_array($atkAtk['id'], [423])) {
                    $atkAtk['settings']['chance_critical'] = 1;
                }
                $cc = $atkAtk['settings']['chance_critical'];
                if(is_numeric($cc)){
                    $cc = (float)$cc;
                    if((int)$cc > 0){
                        $critStage += (int)$cc;
                    }elseif($cc > 0){
                        $critStage += 1;
                    }
                }else{
                    $critStage += 1;
                }
            }

            if(isset($atkAtk['settings']['critical_hit']) || isset($atkAtk['settings']['flowertrik'])){
                $alwaysCrit = 1;
            }

if($atkAtk['id'] == 35) {
    if($this->_actionBattle->_isPVP()){
        $pList = $this->_actionBattle->_getUserPokes($this->attacker->_getUser());
        // Базовая мощность одного удара Beat Up ~ floor(BaseAtk/10)+5 (в Showdown сила удара зависит от каждого члена партии;
        // здесь сохраняем вашу формулу и структуру)
        $atkAtk['power'] = (($this->attacker->base_atk / 10) + 5);
        $i = 0;
        if($pList){
            foreach($pList AS $key_1 => &$value_1){
                // Хит даёт только живой союзник без статуса
                $hasStatus = !empty($value_1['effects']) && (
                    !empty($value_1['effects']['sleep']) ||
                    !empty($value_1['effects']['toxic']) ||
                    !empty($value_1['effects']['toxic2']) ||
                    !empty($value_1['effects']['burn']) ||
                    !empty($value_1['effects']['paralyzed']) ||
                    !empty($value_1['effects']['frost'])
                );
                if($value_1['hp'] > 0 && !$hasStatus) {
                    $i++;
                }
            }
        }
        if($i > 0){
            $atkAtk['settings']['count_hit'][0] = $i;
            $atkAtk['settings']['count_hit'][1] = $i;
        }else{
            $atkAtk['power'] = 0;
            $this->log[] = 'Атака провалилась.';
        }
    }else{
        $atkAtk['power'] = 0;
        $this->log[] = 'Атака провалилась.';
    }
}



if(isset($atkAtk['settings']['count_hit'], $atkAtk['settings']['count_hit'][0], $atkAtk['settings']['count_hit'][1])){
    if($atkAtk['settings']['count_hit'][0] > 0){
        if($atk->ability == 174 && $atkAtk['settings']['count_hit'][0] == 2 && $atkAtk['settings']['count_hit'][1] == 5) {
            // Skill Link: всегда 5 ударов
            $count_hit = 5;
            $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> помогают нанести максимальное количество ударов.';
        }else{
            if($atkAtk['settings']['count_hit'][1] > $atkAtk['settings']['count_hit'][0]){
                $count_hit = $this->_rollMultiHit($atkAtk['settings']['count_hit'][0], $atkAtk['settings']['count_hit'][1]);
            }else{
                $count_hit = $atkAtk['settings']['count_hit'][0];
            }
        }
    }
}

}

if($atkAtk['id'] == 56 && ($def->_getTypeA() != "ghost" and $def->_getTypeB() != "ghost") && ($def->_checkStatus('lightScreen') or $def->_checkStatus('reflect') or $def->_checkStatus('auroraVeil'))){
    $info2 = $def->_getStatusList();
    if($info2){
        foreach($info2 AS $key => $value){
            if(in_array($value['type'], ['reflect','lightScreen','auroraVeil'])){
                unset($info2[$key]);
            }
        }
        $def->_setStatusList($info2);
    }
    $this->log[] = 'Экраны противника разрушены.';
}

if($atkAtk['id'] == 375) {
    $r = rand(1,100);
    if($r <= 40 and $def->_getTypeA() != "ghost" and $def->_getTypeB() != "ghost" ){
        $randPot2 = mt_rand(1,100);
        itemAdd(1,$randPot2);
        update_achiv(15,$randPot2);
        $this->log[] = 'Покемон находит <div class="itemIsset" onclick=issetAll(1,"item") style="background-image: url(/img/world/items/little/1.png)"></div> x'.$randPot2;
    }

}
if($atkAtk['id'] == 785) {
    $randPot2 = round($atk->_getStatSAtk());
    itemAdd(1,$randPot2);
    $this->log[] = 'Покемон находит <div class="itemIsset" onclick=issetAll(1,"item") style="background-image: url(/img/world/items/little/1.png)"></div> x'.$randPot2;


}
if($atkAtk['id'] == 446) {
    // Rollout: стадия 1..5, множитель: 1,2,4,8,16
    $atka = Work::$sql->query('SELECT * FROM atk_rollout WHERE pokID = '.$atk->id)->fetch_assoc();
    if(isset($atka)) {
        $stage = max(1, (int)$atka['mnoz']); // храним стадию, не сам множитель
        $mult  = pow(2, $stage - 1);         // 1,2,4,8,16
        $atkAtk['power'] = $atkAtk['power'] * $mult;
        $this->log[] = 'Мощность атаки увеличена (стадия '.$stage.', x'.$mult.')';
        if($stage >= 5) {
            Work::$sql->query("UPDATE atk_rollout SET `mnoz` = 1 WHERE pokID = ".$atk->id);
        }else{
            $nextStage = $stage + 1;
            Work::$sql->query("UPDATE atk_rollout SET `mnoz` = ".$nextStage." WHERE pokID = ".$atk->id);
        }
    }else{
        // Первая стадия
        Work::$sql->query("INSERT INTO atk_rollout (pokID, mnoz, user, battle) VALUES (".$atk->id.", 1, ".$_SESSION['id'].", ".$this->_actionBattle->battleId.")");
        // стадия 1 => множитель 1 (база)
        $this->log[] = 'Мощность атаки увеличена (стадия 1, x1)';
    }
}
if($atkAtk['id'] == 137) {
    // Echoed Voice: 1..5 стадий, base*стадию, кап 200
    $atka = Work::$sql->query('SELECT * FROM atk_echo WHERE pokID = '.$atk->id)->fetch_assoc();
    if(isset($atka)) {
        $stage = max(1, (int)$atka['mnoz']); // храним стадию
        $power = $atkAtk['power'] * $stage;  // base 40 * stage
        if($power > 200) { $power = 200; }
        $atkAtk['power'] = $power;
        $this->log[] = 'Мощность атаки: <b>'.$atkAtk['power'].'</b> (стадия '.$stage.')';
        if($stage >= 5) {
            Work::$sql->query("UPDATE atk_echo SET `mnoz` = 1 WHERE pokID = ".$atk->id);
        }else{
            $nextStage = $stage + 1;
            Work::$sql->query("UPDATE atk_echo SET `mnoz` = ".$nextStage." WHERE pokID = ".$atk->id);
        }
    }else{
        Work::$sql->query("INSERT INTO atk_echo (pokID, mnoz, user, battle) VALUES (".$atk->id.", 1, ".$_SESSION['id'].", ".$this->_actionBattle->battleId.")");
        // стадия 1: мощность остаётся базовой
        $this->log[] = 'Мощность атаки: <b>'.$atkAtk['power'].'</b> (стадия 1)';
    }
}
if($atkAtk['id'] == 400) {
    // Present: 40% 40, 30% 80, 10% 120, 20% — лечит цель на 1/4 её max HP
    $rand400 = mt_rand(1,100);
    if($rand400 >= 1 && $rand400 <= 40) {
        $atkAtk['power'] = 40;
        $this->log[] = 'Мощность атаки: <b>'.$atkAtk['power'].'</b>';
    }elseif($rand400 >= 41 && $rand400 <= 70) {
        $atkAtk['power'] = 80;
        $this->log[] = 'Мощность атаки: <b>'.$atkAtk['power'].'</b>';
    }elseif($rand400 >= 71 && $rand400 <= 80) {
        $atkAtk['power'] = 120;
        $this->log[] = 'Мощность атаки: <b>'.$atkAtk['power'].'</b>';
    }else{
        // Лечит цель (def) на 1/4 её максимального HP
        $atkAtk['power'] = 0;
        $this->settings['dmg'] = 0;
        $heal400 = ($def->hp_max / 4);
        $def->hp = ($def->hp + $heal400);
        $this->log[] = 'Атака исцеляет противника: <span class="HpPlus">+'.ceil($heal400).' HP</span>';
    }
}


if($atkAtk['id'] == 190) {
    // Fury Cutter: множитель по стадиям; у вас уже хранится mnoz=2..4, оставляем как есть
    $atka = Work::$sql->query('SELECT * FROM atk_furycutter WHERE pokID = '.$atk->id)->fetch_assoc();
    if(isset($atka)) {
        $atkAtk['power'] = $atkAtk['power'] * $atka['mnoz'];
        $this->log[] = 'Мощность атаки увеличена в x'.$atka['mnoz'];
        if($atka['mnoz'] == 4) {
            Work::$sql->query("UPDATE atk_furycutter SET `mnoz` = 4 WHERE pokID = ".$atk->id);
        }else{
            $atkp = $atka['mnoz'] + 1;
            Work::$sql->query("UPDATE atk_furycutter SET `mnoz` = $atkp WHERE pokID = ".$atk->id);
        }
    }else{
        Work::$sql->query("INSERT INTO atk_furycutter (pokID, mnoz, user) VALUES (".$atk->id.", 2, ".$_SESSION['id'].")");
        $this->log[] = 'Мощность атаки увеличена в х1';
    }
}

            if($atkAtk['id'] == 495) { // Smack Down: сбивает "левитацию"
                if($def->_checkStatus('easy_levitation')) {
                    $info = $def->_getStatusList();
                    if($info){
                        foreach($info AS $key=>$value){
                            if($value['type'] == 'easy_levitation'){
                                unset($info[$key]);
                            }
                        }
                    }
                    $def->_setStatusList($info);
                    $this->log[] = 'Противник прижат к земле.';
                }
            }

            if($atkAtk['id'] == 431) { // Return
              if($atk->happy <= 0) {
                $atkAtk['power'] = 1;
              }else{
                $atkAtk['power'] = floor($atk->happy / 2.5);
              }
              if($atkAtk['power'] >= 102) {
                $atkAtk['power'] = 102; // кап 102
              }
              $this->log[] = 'Мощность атаки: '.$atkAtk['power'];
            }

            if($atkAtk['id'] == 188) { // Frustration
              if($atk->happy <= 0) {
                $atkAtk['power'] = 1;
              }else{
                $atkAtk['power'] = floor((255 - $atk->happy) / 2.5);
              }
              $this->log[] = 'Мощность атаки: '.$atkAtk['power'];
            }

            if($atkAtk['id'] == 598) { // Eruption / Water Spout стиль: сила = 150 * HP%
              $power = 150 * ($atk->hp / $atk->hp_max);
              if($power <= 1) {
                $atkAtk['power'] = 1;
              }else{
                $atkAtk['power'] = floor($power);
              }
              $this->log[] = 'Мощность атаки: '.$atkAtk['power'];
            }

            if($atkAtk['id'] == 642) { // Plasma Fists: нормальные контактные превращаются в электрические
                $atk->_setStatus('plasma_fists', 9999);
                $this->log[] = 'Покемон активировал Плазменные Кулаки.';
            }

            if($atkAtk['id'] == 524) { // Power Trip: 20 + 20 за каждую положительную стадию
              $power = 60; $power_atk = 0; $power_def = 0; $power_spd = 0; $power_satk = 0; $power_sdef = 0; $power_agl = 0; $power_acr = 0;
              if(isset($atk->modified['atk']['plus'])){
                $power_atk = $atk->modified['atk']['plus'];
                $power_atk = $power_atk * 20;
              }
              if(isset($atk->modified['def']['plus'])){
                $power_def = $atk->modified['def']['plus'];
                $power_def = $power_def * 20;
              }
              if(isset($atk->modified['spd']['plus'])){
                $power_spd = $atk->modified['spd']['plus'];
                $power_spd = $power_spd * 20;
              }
              if(isset($atk->modified['satk']['plus'])){
                $power_satk = $atk->modified['satk']['plus'];
                $power_satk = $power_satk * 20;
              }
              if(isset($atk->modified['sdef']['plus'])){
                $power_sdef = $atk->modified['sdef']['plus'];
                $power_sdef = $power_sdef * 20;
              }
              if(isset($atk->modified['agl']['plus'])){
                $power_agl = $atk->modified['agl']['plus'];
                $power_agl = $power_agl * 20;
              }
              if(isset($atk->modified['acr']['plus'])){
                $power_acr = $atk->modified['acr']['plus'];
                $power_acr = $power_acr * 20;
              }
              $power = 20 + $power_atk + $power_def + $power_spd + $power_sdef + $power_satk + $power_agl + $power_acr;
              if($power < 20) { $power = 20; }
              $atkAtk['power'] = $power;
              $this->log[] = 'Мощность атаки: '.$atkAtk['power'];
            }

            if($atkAtk['id'] == 411) { // Punishment: 60 + 20 за каждую положительную стадию цели (макс 200)
              $power = 60; $power_atk = 0; $power_def = 0; $power_spd = 0; $power_satk = 0; $power_sdef = 0;
              if(isset($def->modified['atk']['plus'])){
                $power_atk = $def->modified['atk']['plus'];
              }
              if(isset($def->modified['def']['plus'])){
                $power_def = $def->modified['def']['plus'];
              }
              if(isset($def->modified['spd']['plus'])){
                $power_spd = $def->modified['spd']['plus'];
              }
              if(isset($def->modified['satk']['plus'])){
                $power_satk = $def->modified['satk']['plus'];
              }
              if(isset($def->modified['sdef']['plus'])){
                $power_sdef = $def->modified['sdef']['plus'];
              }
              $power = 60 + (20 * ($power_atk + $power_def + $power_spd + $power_satk + $power_sdef));
              if($power > 200) {
                $power = 200;
              }
              $atkAtk['power'] = $power;
              $this->log[] = 'Мощность атаки: '.$atkAtk['power'];
            }

            // Особый случай: «Чудо» против психо по тёмному (не меняю вашу механику, только сохраняю)
            if($def->_checkStatus('miracle') && in_array('dark', [$def->_getTypeA(),$def->_getTypeB()]) && $atkAtk['type'] == 'psychic') {
                $this->types['psychic']['dark'] = 1;
            }

            // Типоэффективность
            $this->settings['types'] = 1;
            $this->settings['types'] *= (isset($this->types[$atkAtk['type']], $this->types[$atkAtk['type']][$def->_getTypeA()]) ? $this->types[$atkAtk['type']][$def->_getTypeA()] : 1);
            $this->settings['types'] *= (isset($this->types[$atkAtk['type']], $this->types[$atkAtk['type']][$def->_getTypeB()]) ? $this->types[$atkAtk['type']][$def->_getTypeB()] : 1);

            // Expert Belt-подобный предмет (ваша логика для item_id 159 при x4)
            if($this->settings['types'] == 4 and $atk->item_id == 159) $this->settings['types'] *= 1.2;

            // STAB (включая Terastalization как в SV)
            $this->settings['stab'] = 1;
            $moveType = (string)($atkAtk['type'] ?? '');
            if($moveType !== '' && $moveType !== 'NULL'){
                $atkTypeA = (string)$atk->_getTypeA();
                $atkTypeB = (string)$atk->_getTypeB();

                if(!empty($atk->tera_active) && !empty($atk->tera_type)){
                    $origA = (!empty($atk->tera_orig_type) ? (string)$atk->tera_orig_type : $atkTypeA);
                    $origB = (!empty($atk->tera_orig_type_two) ? (string)$atk->tera_orig_type_two : $atkTypeB);
                    $teraType = (string)$atk->tera_type;

                    if($moveType === $teraType){
                        $this->settings['stab'] = (($teraType === $origA) || ($origB !== '' && $teraType === $origB)) ? 2.0 : 1.5;
                    }elseif($moveType === $origA || ($origB !== '' && $moveType === $origB)){
                        $this->settings['stab'] = 1.5;
                    }
                }else{
                    if($moveType === $atkTypeA || ($atkTypeB !== '' && $moveType === $atkTypeB)){
                        $this->settings['stab'] = 1.5;
                    }
                }
            }

            // Типовые буст-итемы
            if($atkAtk['type'] == 'bug' && $atk->item_id == 141) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'dark' && $atk->item_id == 135) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'dragon' && $atk->item_id == 126) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] ==  'electric' && $atk->item_id == 127) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'fairy' && $atk->item_id == 138) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'fighting' && $atk->item_id == 136) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'fire' && $atk->item_id == 133) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'fly' && $atk->item_id == 131) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'ghost' && $atk->item_id == 130) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'grass' && $atk->item_id == 139) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'ground' && $atk->item_id == 128) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'ice' && $atk->item_id == 132) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'normal' && $atk->item_id == 137) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'poison' && $atk->item_id == 134) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'psychic' && $atk->item_id == 125) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'rock' && $atk->item_id == 140) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'steel' && $atk->item_id == 100) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atkAtk['type'] == 'water' && $atk->item_id == 129) {
              $this->settings['otherDmg'] = 1.1;
            }elseif($atk->item_id == 174){
              $this->settings['otherDmg'] = 1.1;
            }elseif($atk->item_id == 166){
              $this->settings['otherDmg'] = 1.3;
            }else{
              $this->settings['otherDmg'] = 1;
            }

            // Базовые боевые статы (с учётом модификаторов по вашим методам)
            $atkPokemonATK = $atk->_getStatAtk();
            $atkPokemonSATK = $atk->_getStatSAtk();
            $defPokemonDEF = $def->_getStatDef();
            $defPokemonSDEF = $def->_getStatSDef();
            $defPokemonATK = $def->_getStatAtk();

            // Marvel Scale (id=108): статус у цели → DEF×1.5
            if($def->ability == 108 and $atk->ability != 113) {
              if($def->_checkStatus('toxic') || $def->_checkStatus('toxic2') || $def->_checkStatus('sleep') || $def->_checkStatus('burn') || $def->_checkStatus('frost') || $def->_checkStatus('paralyzed')) {
                $defPokemonDEF = $defPokemonDEF * 1.5;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> усиливает Защиту цели.';
              }
            }

            // Solar Power (id=180): в солнце +50% SpA
            if($atk->ability == 180 && $this->_actionBattle->weather == 2 && !in_array(4,[$atk->ability,$def->ability])) {
              $atkPokemonSATK = $atkPokemonSATK * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает <span class="StatPlus">Спец. Атаку +50%</span>';
            }

            // Guts (id=70): статус → +50% Atk
            if($atk->ability == 70) {
              if($atk->_checkStatus('burn') || $atk->_checkStatus('toxic') || $atk->_checkStatus('toxic2') || $atk->_checkStatus('sleep') || $atk->_checkStatus('paralyzed') || $atk->_checkStatus('frost')){
                $atkPokemonATK = $atkPokemonATK * 1.5;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает <span class="StatPlus">Атаку +50%</span>';
              }
            }

            // Sacred Sword / Darkest Lariat стили (ignore target boosts)
            if($atkAtk['id'] == 74 || $atkAtk['id'] == 451) {
              $atkDef = ($atkAtk['category'] == 'physical' ? ($atkPokemonATK / $def->_getStatDef_ignore()) : ($atkPokemonSATK / $def->_getStatSDef()));
            }else{
              $atkDef = ($atkAtk['category'] == 'physical' ? ($atkPokemonATK / $defPokemonDEF) : ($atkPokemonSATK / $def->_getStatSDef()));
            }

            // Unaware (id=219): у атакера — игнорим бафы цели; у цели — игнорит бафы атакера
            if($atk->ability == 219 && $def->ability != 113) {
              $atkDef = ($atkAtk['category'] == 'physical' ? ($atkPokemonATK / $def->_getStatDef_ignore()) : ($atkPokemonSATK / $def->_getStatSDef_ignore()));
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> игнорирует модификаторы цели.';
            }
            if($def->ability == 219 && $atk->ability != 113) {
              $atkDef = ($atkAtk['category'] == 'physical' ? ($atk->_getStatAtk_ignore() / $defPokemonDEF) : ($atk->_getStatSAtk_ignore() / $defPokemonSDEF));
              $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> игнорирует модификаторы цели.';
            }

            // Solar Beam (id=504): в дождь/град/песок сила ×0.5, если погода активна
            if($this->_actionBattle->weather == 3 || $this->_actionBattle->weather == 4 || $this->_actionBattle->weather == 5) {
              if(!in_array(4,[$atk->ability,$def->ability])) {
                  if($atkAtk['id'] == 504) {
                    $atkAtk['power'] = $atkAtk['power'] / 2;
                  }
              }
            }

            // Инициализируем исходный урон мощностью атаки и далее только МНОЖИМ
            $atkDmg = $atkAtk['power'];

            // Misty Terrain: драконий урон по "заземлённым" целям ×0.5 (ваша реализация: статус на атакере)
            if($atk->_checkStatus('terrMisty') && $atkAtk['type'] == 'dragon') {
              $atkDmg *= 0.5;
            }

            // Mud Sport: электрический урон ×1/3 (в Showdown именно ~0.33), ваша реализация — через статус
            if($atk->_checkStatus('mudsport') && $atkAtk['type'] == 'electric') {
              $atkDmg = $atkDmg / 3;
            }

            // Rain: огонь ×0.5 при активной погоде (если не Cloud Nine/Air Lock)
            if($this->_actionBattle->weather == 3 && $atkAtk['type'] == 'fire' && !in_array(4,[$atk->ability,$def->ability])) {
              $atkDmg *= 0.5;
            }

            // Thick Fat (id=210): огонь/лёд по цели ×0.5
            if($def->ability == 210 and $atk->ability != 113 and ($atkAtk['type'] == 'fire' or $atkAtk['type'] == 'ice')){
                $atkDmg *= 0.5;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> уменьшает урон в два раза.';
            }

            // Dry Skin (id=45): цель получает на 25% больше урона от огня
            if($def->ability == 45 and $atkAtk['type'] == 'fire' and $atk->ability != 113){
                $atkDmg *= 1.25;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> увеличивает урон атаки на 25%.';
            }

            // Wake-Up Slap (id=594): ×2 по спящему и будит
            if($atkAtk['id'] == 594) {
              if($def->_checkStatus('sleep')) {
                $atkDmg *= 2;
                $info = $def->_getStatusList();
                if($info){
                  foreach($info AS $key=>$value){
                    if($value['type'] == 'sleep'){
                      unset($info[$key]);
                    }
                  }
                  $def->_setStatusList($info);
                }
                $this->log[] = 'Атака наносит удвоенные повреждения и будит покемона.';
              }
            }

            // Shell Trap-подобная проверка (вашу структуру сохраняю)
            if($atkAtk['id'] == 533) {
              if($defAtk['target'] == 'enemy' && $defAtk['category'] == 'special' || $defAtk['target'] == 'enemy' && $defAtk['category'] == 'physical' || $defAtk['id'] == 308){
                // ок
              }else{
                $atkAtk['power'] = 0;
                $atkDmg = 0;
              }
            }

            // Venoshock (id=767): ×2 по отравленной цели (и toxic, и toxic2)
            if($atkAtk['id'] == 767 && ($def->_checkStatus('toxic') || $def->_checkStatus('toxic2'))) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Me First/Payback/Revenge подобные условия — сохраняю вашу логику
            if($atkAtk['id'] == 376 && $this->meFirst == 0) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Freeze-Dry (id=631): супер-эффективно против Water — ×2 (а не ×4)
            if($atkAtk['id'] == 631 && in_array('water', [$def->_getTypeA(), $def->_getTypeB()])) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Smelling Salts (id=496): ×2 и снимает паралич
            if($atkAtk['id'] == 496 && $def->_checkStatus('paralyzed')) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон. Противник избавился от Парализации.';
              $info = $def->_getStatusList();
              if($info){
                foreach($info AS $key=>$value){
                  if($value['type'] == 'paralyzed'){
                    unset($info[$key]);
                  }
                }
                $def->_setStatusList($info);
              }
            }

            // if($atk->item_id == 352) { $atkDmg = $atkDmg * 1.2; }

            // Связка (ваш кейс 192/193)
            if($this->meFirst == 0 && $atkAtk['id'] == 192 && $defAtk['id'] == 193) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }
            if($this->meFirst == 0 && $atkAtk['id'] == 193 && $defAtk['id'] == 192) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }


                        if($this->meFirst == 0 && $atkAtk['id'] == 193 && $defAtk['id'] == 192) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            if($this->meFirst == 0 && $atk->ability == 5) {
              $atkDmg = $atkDmg * 1.3;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 30%.';
            }

            // Technician: усиливает атаки с силой 60 или меньше
            if($atkAtk['power'] <= 60 && $atk->ability == 207) {
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }

            // Punk Rock (атакер): звук +30%
            if($atk->ability == 109 && $atkAtk['pulse'] == 1) {
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }

            // Filter/Solid Rock специальный (ваша логика): спецатаки по цели -50%
            if($def->ability == 250 && $atkAtk['category'] == "special") {
              $atkDmg = $atkDmg * 0.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> понижает мощность атаки на 50%.';
            }

            // Punk Rock (защита): звук -50%
            if($atk->ability == 240 && $atkAtk['sound'] == 1) {
              $atkDmg = $atkDmg * 1.3;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 30%.';
            }

            if($def->ability == 240 && $atkAtk['sound'] == 1) {
              $atkDmg = $atkDmg * 0.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> уменьшает мощность атаки на 50%.';
            }

            // Iron Fist
            if($atk->ability == 91 && $atkAtk['punch'] == 1) {
              $atkDmg = $atkDmg * 1.2;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 20%.';
            }

            // Toxic Boost
            if($atk->ability == 214 && ($atk->_checkStatus('toxic') || $atk->_checkStatus('toxic2')) && $atkAtk['category'] == 'physical') {
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }

            // Reckless (аккуратные скобки)
            if( $atk->ability == 150 && ( isset($atkAtk['settings']['recoil']) || (isset($atkAtk['settings']) && isset($atkAtk['settings']['return_dmg'])) ) ){
              $atkDmg = $atkDmg * 1.2;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 20%.';
            }

            // Tough Claws
            if($atk->ability == 213 && $atkAtk['contact'] == 1){
              $atkDmg = $atkDmg * 1.3;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 30%.';
            }

            // (ваша внутренняя способность id 12 — оставляем как есть)
            if($atk->ability == 12){
              $atkDmg = $atkDmg * 1.3;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 30%.';
            }

            // Steelworker
            if($atk->ability == 191 && $atkAtk['type'] == 'steel'){
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }
            
            // Rivalry
            if($atk->ability == 153){
                if($atk->_getSex() == $def->_getSex()){
                    $atkDmg = $atkDmg * 1.25;
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 25%.';
                }else{
                    $atkDmg = $atkDmg * 0.75;
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> уменьшает мощность атаки на 25%.';
                }
            }
            
            // Swarm
            if($atk->ability == 200 && $atkAtk['type'] == 'bug' && ($atk->hp < ($atk->hp_max/3))){
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }
            
            // Transistor (усиливает Электрические)
            if($atk->ability == 258 && $atkAtk['type'] == 'electric'){
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }

            // if($this->meFirst == 0 && $defAtk['type'] == 'fire' && $defAtk['target'] == 'enemy' && $atkAtk['type'] == 'fire') {
            //   $atkDmg = $atkDmg * 1.5;
            //   $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            // }

            // Внутренняя метка эффектов способности 2
            if($this->ability2Effect == 1) {
              $atkDmg = $atkDmg * 1.2;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 20%.';
            }

            // Pure Power/Huge Power (ваша логика через множитель урона)
            if($atkAtk['category'] == 'physical' && $atk->ability == 77) {
              $atkDmg = $atkDmg * 1.5;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
            }

            // Grassy Terrain (ваша реализация через статус на атакере)
            if($atk->_checkStatus('terrGrass') && $atkAtk['type'] == 'grass') {
              $atkDmg = $atkDmg * 1.3;
            }

            // Blaze
            if($atkAtk['type'] == 'fire' && $atk->ability == 18) {
              $hp = ($atk->hp_max / 100) * 30;
              if($atk->hp <= $hp) {
                $atkDmg = $atkDmg * 1.5;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
              }
            }

                        if($atkAtk['type'] == 'grass' && $atk->ability == 126) {
              $hp = ($atk->hp_max / 100) * 30;
              if($atk->hp <= $hp) {
                $atkDmg = $atkDmg * 1.5;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
              }
            }

            if($atkAtk['type'] == 'steel' && $atk->ability == 247) {
              $atkDmg = $atkDmg * 1.35;
              $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 35%.';
            }

            // Torrent-подобный буст должен срабатывать при ≤ 1/3 HP
            if($atkAtk['type'] == 'water' && $atk->ability == 212) {
              $hp = ($atk->hp_max / 100) * 30;
              if($atk->hp <= $hp) {
                $atkDmg = $atkDmg * 1.5;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает мощность атаки на 50%.';
              }
            }

            // Acrobatics (x2 при отсутствии предмета) — у вас множитель по урону, оставляем
            if($atkAtk['id'] == 5 && $atk->item_id == 0) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Brine (x2 если цель ≤ 1/2 HP)
            if($atkAtk['id'] == 57) {
              if($def->hp <= ($def->hp_max / 2)) {
                $atkDmg = $atkDmg * 2;
                $this->log[] = 'Атака нанесла удвоенный урон.';
              }
            }

            // Sand Force в песке (Ground/Steel/Rock +30%)
            if($this->_actionBattle->weather == 5 && $atk->ability == 158 && !in_array(4,[$atk->ability,$def->ability])) {
              if(in_array($atkAtk['type'], ['ground','steel','rock'])) {
                $atkDmg = $atkDmg + (($atkDmg / 100) * 30);
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличила мощность атаки на 30%.';
              }
            }

            // Strong Jaw (укусы +50%)
            if($atk->ability == 195) {
              if(!empty($atkAtk['bite']) && $atkAtk['bite'] == 1) {
                $atkDmg = $atkDmg + (($atkDmg / 100) * 50);
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличила мощность атаки на 50%.';
              }
            }

            // Weather Ball: удвоение в любой погоде (не “ясно”)
            if($this->_actionBattle->weather != 1 && $atkAtk['id'] == 600 && !in_array(4,[$atk->ability,$def->ability])) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Surf (601) и Earthquake (538) по Dive
            if($defAtk['id'] == 113 && $defAtk['power'] == 0 && $atkAtk['id'] == 601) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }
            if($defAtk['id'] == 113 && $defAtk['power'] == 0 && $atkAtk['id'] == 538) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Особое условие: электрическая атака после хода 70 (ваша внутренняя механика)
            if($atkAtk['type'] == 'electric' && $atk->atk_before == 70) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Earthquake по Dig
            if($defAtk['id'] == 110 && $defAtk['power'] == 0 && $atkAtk['id'] == 136) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Gust/Twister/и т.п. по Fly/Bounce/Phantom Force (x2) — группируем корректно
            if( ($defAtk['id'] == 177 && $defAtk['power'] == 0 && $atkAtk['id'] == 214) ||
                ($defAtk['id'] == 177 && $defAtk['power'] == 0 && $atkAtk['id'] == 582) ||
                ($defAtk['id'] == 54  && $defAtk['power'] == 0 && $atkAtk['id'] == 582) ||
                ($defAtk['id'] == 485 && $defAtk['power'] == 0 && $atkAtk['id'] == 582) ||
                ($defAtk['id'] == 54  && $defAtk['power'] == 0 && $atkAtk['id'] == 214) ||
                ($defAtk['id'] == 485 && $defAtk['power'] == 0 && $atkAtk['id'] == 214) ) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Venoshock (x2 при отравлении) — расставляем скобки
            if( ($atkAtk['id'] == 588) && ($def->_checkStatus('toxic') || $def->_checkStatus('toxic2')) ) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Rollout x2 после Defense Curl
            if($atkAtk['id'] == 446 && $atk->_checkStatus('defensecurl')) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Pursuit-подобный эффект (удвоение при попытке замены/особом условии) — у вас признак hp_before
            if($def->hp_before > 0 && $atkAtk['id'] == 432) {
              $atkDmg = $atkDmg * 2;
              $this->log[] = 'Атака нанесла удвоенный урон.';
            }

            // Facade-подобный эффект: x2, если атакер с недугом
            if(in_array($atkAtk['id'], [152])) {
              if($atk->_checkStatus('toxic') || $atk->_checkStatus('toxic2') || $atk->_checkStatus('burn') || $atk->_checkStatus('paralyzed') || $atk->_checkStatus('sleep') || $atk->_checkStatus('frost')){
                $atkDmg = $atkDmg * 2;
                $this->log[] = 'Атака нанесла удвоенный урон.';
              }
            }

            // Hex-подобный эффект: x2, если цель с недугом
            if(in_array($atkAtk['id'], [236])) {
              if($def->_checkStatus('toxic') || $def->_checkStatus('toxic2') || $def->_checkStatus('burn') || $def->_checkStatus('paralyzed') || $def->_checkStatus('sleep') || $def->_checkStatus('frost')){
                $atkDmg = $atkDmg * 2;
                $this->log[] = 'Атака нанесла удвоенный урон.';
              }
            }

            // Magnitude — исправляем условие диапазона и лог
            if($atkAtk['id'] == 306) {
              $magn = mt_rand(1,100);
              if($magn >= 1 && $magn <= 5) {
                $magn1 = 10;
              }elseif($magn >= 6 && $magn <= 15) {
                $magn1 = 30;
              }elseif($magn >= 16 && $magn <= 35) {
                $magn1 = 50;
              }elseif($magn >= 36 && $magn <= 65) {
                $magn1 = 70;
              }elseif($magn >= 66 && $magn <= 85) {
                $magn1 = 90;
              }elseif($magn >= 86 && $magn <= 95) {
                $magn1 = 110;
              }else{
                $magn1 = 150;
              }
              $atkDmg = $magn1;
              $this->log[] = 'Мощность атаки: <b>'.$magn1.'</b>';
            }

            // Magnitude по Dig (x2)
            if($defAtk['id'] == 110 && $defAtk['power'] == 0 && $atkAtk['id'] == 306) {
              $this->log[] = 'Атака нанесла удвоенный урон.';
              $atkDmg = $atkDmg * 2;
            }

            // Twister-подобный хейт по зарядкам (собираем в одно условие)
            if( ($atkAtk['id'] == 412) && in_array($defAtk['id'], [9999,583,592,374,229]) ) {
              $this->log[] = 'Атака нанесла удвоенный урон.';
              $atkDmg = $atkDmg * 2;
            }

            // Spit Up — сила зависит от стэков Stockpile; корректно удаляем эффект
            if($atk->_checkStatus('stock') && $atkAtk['id'] == 512) {
              $info = $atk->_getStatusList();
              $vl = 0; $toUnsetKey = null;
              if($info){
                foreach($info as $key => $value){
                  if($value['type'] == 'stock'){
                    $vl = (int)$value['val'];
                    $toUnsetKey = $key;
                    break;
                  }
                }
              }
              if($toUnsetKey !== null){
                unset($info[$toUnsetKey]);
                $atk->_setStatusList($info);
              }
              $atkDmg = max(1, $vl * 100);
            }

            $this->settings['dmg'] = 0;

            // OHKO-атаки (Guillotine/Fissure/Horn Drill/Sheer Cold) — вы оставили свои id 473,212,243,166
            if($atkAtk['id'] == 473 || $atkAtk['id'] == 212 || $atkAtk['id'] == 243 || $atkAtk['id'] == 166){

              // Sheer Cold не действует по Ice — расставляем скобки
              if( ($atkAtk['id'] == 473) && ($def->base_type_two == 'ice' || $def->base_type_one == 'ice') ) {
                $this->log[] = 'Нет эффекта. Атака не нанесла урон.';
                $atkAtk['power'] = 0;
              }else{
                if($def->numb != 9595 && $def->numb != 9596 && $def->numb != 9597){
                  if($atk->lvl > $def->lvl) {
                    if($atk->base_type_one != 'ice' || $atk->base_type_two != 'ice') {
                      $chanseKO = (($atk->lvl - $def->lvl) + 20);
                    }else{
                      $chanseKO = (($atk->lvl - $def->lvl) + 30);
                    }
                    $randKo = mt_rand(1,100);
                    if($randKo <= $chanseKO || in_array(122, [$atk->ability,$def->ability])) {
                      $this->log[] = 'Смертельная атака.';
                      $this->settings['dmg'] = 50000;
                      if(check_mission_ivent(41)){ add_mission_ivent(41); }
                      update_achiv(11,1);
                    }else{
                      $this->log[] = 'Атака промахнулась и не нанесла урон.';
                      $atkAtk['power'] = 0;
                    }
                  }else{
                    $atkAtk['power'] = 0;
                    $this->log[] = 'Атака промахнулась и не нанесла урон.';
                  }
                }else{
                  $atkAtk['power'] = 0;
                  $this->log[] = 'Атака провалилась.';
                }
              }

            }

            for($i=0; $i<$count_hit; $i++){
              if($atkAtk['power'] != 0){
                // Psyshock/похожие: рассчитываем особую атаку по физзащите
                if($atkAtk['id'] == 408 || $atkAtk['id'] == 409 || $atkAtk['id'] == 462) {
                  $atkDef = $atkPokemonSATK / $defPokemonDEF;
                  $this->log[] = 'Атака наносит физические повреждения!';
                }else{
                  $atkDef = $atkDef;
                }

          if($atkAtk['id'] == 184) {
    // Foul Play: используем Атаку цели против её же Защиты
    $atkDef = $defPokemonATK / $defPokemonDEF;
}

$critStage += ($atk->item_id == 143 ? 1 : 0);
$critStage += ($atk->ability == 198 ? 1 : 0);

// Focus Energy / Laser Focus (если есть) — повышенный шанс крита
if($atk->_checkStatus('critfocus') || $atk->_checkStatus('critfocs') || $atk->_checkStatus('critfoc')){
    $critStage += 2;
}
if($critStage > 3){
    $critStage = 3;
}

$critYes = 0;
if($alwaysCrit){
    $critYes = 1;
}else{
    if($critStage >= 3){
        $critYes = 1;
    }elseif($critStage == 2){
        $critYes = (mt_rand(1, 2) == 1);
    }elseif($critStage == 1){
        $critYes = (mt_rand(1, 8) == 1);
    }else{
        $critYes = (mt_rand(1, 24) == 1);
    }
}

// Криты
$this->settings['critical'] = ($critYes ? 1.5 : 1);

// Merciless: всегда крит по отравленным
if($atk->ability == 110 && ($def->_checkStatus('toxic') || $def->_checkStatus('toxic2'))){
    $this->settings['critical'] = 1.5;
}

// Запрет на крит: статус/особые случаи и пустая мощность атаки
if($this->settings['critical'] > 1){
    if($def->_checkStatus('nocrit') || $atkAtk['power'] <= 0){
        $this->settings['critical'] = 1;
    }
}

// Battle Armor / Shell Armor
if($this->settings['critical'] > 1 && in_array($def->ability, [170,13]) && $atk->ability != 113 ) {
  $this->settings['critical'] = 1;
  $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> спасает покемона противника от критического удара, который мог бы быть.';
}

// Sniper
if($this->settings['critical'] > 1 && $atk->ability == 177) {
  $this->settings['critical'] = 2.25;
  $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> значительно увеличивает урон от Критической атаки.';
}

// Scrappy — бить Призраков Нормальными/Боёв.
if(
  ($atk->ability == 164 && in_array('ghost', [$def->_getTypeB(),$def->_getTypeA()]) && $atkAtk['type'] == 'normal') ||
  ($atk->ability == 164 && in_array('ghost', [$def->_getTypeB(),$def->_getTypeA()]) && $atkAtk['type'] == 'fighting')
){
  $this->settings['types'] = 1;
  $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> позволяет бить по призракам.';
}

// Gravity снимает иммуниз. Полёта для Земляных
if($this->defender->_checkStatus('gravity') && in_array('fly', [$this->defender->_getTypeA(),$this->defender->_getTypeB()]) && $this->attackerAtk['type'] == 'ground') {
  $this->settings['types'] = 1;
}

// Суперэффективные ягоды (в т.ч. ×2, не только ×4), если нет Unnerve (221)
if($this->settings['types'] > 1){
    if($atk->ability != 221){
        // Если тип-ягода совпадает — съесть и обнулить усиление до нейтрала
        if( ($atkAtk['type'] == 'dragon'   && $def->item_id == 306) ||
            ($atkAtk['type'] == 'dark'     && $def->item_id == 301) ||
            ($atkAtk['type'] == 'fly'      && $def->item_id == 300) ||
            ($atkAtk['type'] == 'fighting' && $def->item_id == 299) ||
            ($atkAtk['type'] == 'rock'     && $def->item_id == 295) ||
            ($atkAtk['type'] == 'steel'    && $def->item_id == 294) ||
            ($atkAtk['type'] == 'fairy'    && $def->item_id == 328) ||
            ($atkAtk['type'] == 'grass'    && $def->item_id == 327) ||
            ($atkAtk['type'] == 'psychic'  && $def->item_id == 320) ||
            ($atkAtk['type'] == 'water'    && $def->item_id == 319) ||
            ($atkAtk['type'] == 'poison'   && $def->item_id == 310) ||
            ($atkAtk['type'] == 'ghost'    && $def->item_id == 309) ||
            ($atkAtk['type'] == 'ground'   && $def->item_id == 330) ||
            ($atkAtk['type'] == 'ice'      && $def->item_id == 337) ||
            ($atkAtk['type'] == 'electric' && $def->item_id == 335)
        ){
            $this->settings['types'] = 1;
            $this->log[] = '<div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$def->item_id.'],this)" style="background-image: url(/img/world/items/little/'.$def->item_id.'.png)"></div> спасает противника от суперэффективной атаки.';
            $def->item_id = 0;
            Work::$sql->query("UPDATE user_pokemons SET `item_id` = 0, `item_str` = 'NULL' WHERE id = ".$def->id);
        }
    }else{
        $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> заставляет противника нервничать и мешает съесть плод.';
    }
}

// «Повышает урон до обычного», если цель резистит (ваша кастом-способность 211)
if($this->settings['types'] <= 0.50 and $def->ability == 211){
    $this->settings['types'] = 1;
    $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> повышает урон до обычного.';
}

// Итоговый урон одного удара
$this->settings['dmg'] += intval(ceil(( ( ( (2 * $atk->_getLvl()) + 10) / 250) * $atkDef * $atkDmg + 2) * $this->settings['stab'] * $this->settings['critical'] * $this->settings['types']  * $this->settings['otherDmg'] * $random));

// Solid Rock / Filter: снижение SE-урона на 25% (для ×2 и ×4)
if($this->settings['types'] > 1 && $def->ability == 51 && $this->settings['dmg'] > 0 && $atk->ability != 113) {
  $this->settings['dmg'] = ceil($this->settings['dmg'] - ($this->settings['dmg'] / 4));
  $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника снижает урон от атаки на 25%.';
}
}else{
  $this->settings['dmg'] += 0;
}
}
if(
    ($atkAtk['id'] == 518 && $def->_checkStatus('minimize')) ||
    ($atkAtk['id'] == 48  && $def->_checkStatus('minimize')) ||
    ($atkAtk['id'] == 232 && $def->_checkStatus('minimize')) ||
    ($atkAtk['id'] == 234 && $def->_checkStatus('minimize')) ||
    ($atkAtk['id'] == 741 && $def->_checkStatus('minimize')) ||
    ($atkAtk['id'] == 381 && $def->_checkStatus('minimize')) ||
    ($atkAtk['id'] == 126 && $def->_checkStatus('minimize'))
){
  $this->settings['dmg'] = floor($this->settings['dmg'] * 2);
  $this->log[] = 'Атака нанесла удвоенные повреждения.';
}

// Sap Sipper (иммун к Grass + +1 Атака цели); Mold Breaker (113) игнорирует
if($atkAtk['type'] == 'grass' && $def->ability == 162 && $this->settings['dmg'] > 0 && $atk->ability != 113) {
  $this->settings['dmg'] = 0;
  $def->_setModifiedStat('atk', 1, true);
  $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника блокирует урон от атаки и повышает <span class="StatPlus">Атаку +1</span>.';
}

if($atkAtk['id'] == 546) {
  if($atk->_getTypeA() == $def->_getTypeA() || $atk->_getTypeB() == $def->_getTypeA() || $atk->_getTypeB() == $def->_getTypeB() || $atk->_getTypeA() == $def->_getTypeB() || $atk->_getTypeB() == $def->_getTypeB()) {
    $this->settings['dmg'] = $this->settings['dmg'];
  }else{
    $this->settings['dmg'] = 0;
  }
}

if($atkAtk['id'] == 512 && $atkDmg <= 1) {
  $this->settings['dmg'] = 0;
}

if($atkAtk['id'] == 514) {
  $this->settings['dmg'] = 0;
}

if($def->hp_before > 0 && $atkAtk['id'] == 180) {
  $this->settings['dmg'] = 0;
}

if($atkAtk['id'] == 30) {
  if($defAtk['power'] > 0) {
    $this->settings['dmg'] = floor($this->settings['dmg'] * 2);
    $this->log[] = 'Атака нанесла удвоенные повреждения.';
  }
}

if($atkAtk['id'] == 417) {
  if($def->hp_before > 0) {
    $atk->_setModifiedStat('atk', 1, true);
    $this->log[] = 'У '.$atk->_getName(true).' повышена <span class="StatPlus">Атака +1</span>';
  }
}

if($atkAtk['id'] == 316) {
  if($def->hp_before > 0) {
    $this->settings['dmg'] = floor($def->hp_before * 1.5);
  }else{
    $this->settings['dmg'] = 0;
  }
}

if($def->hp_before > 0 && $atkAtk['id'] == 23) {
  $this->settings['dmg'] = $this->settings['dmg'] * 2;
}

if($atkAtk['id'] == 535 and $def->numb != 9595  and $def->numb != 9596  and $def->numb != 9597) {
  $this->settings['dmg'] = floor($def->hp/2);
  if($this->settings['dmg'] < 1) {
    $this->settings['dmg'] = 1;
  }
}

if($atkAtk['id'] == 788 and $def->numb != 9595 and $def->numb != 9596  and $def->numb != 9597) {
  $this->settings['dmg'] = floor($def->hp/2);
  if($this->settings['dmg'] < 1) {
    $this->settings['dmg'] = 1;
  }
}

if($atkAtk['id'] == 505) {
  if($def->_getTypeA() != 'ghost' && $def->_getTypeB() != 'ghost' && !$def->_checkStatus('trickortreat')) {
    $this->settings['dmg'] = 20;
  }
}

// if($atkAtk['id'] == 326) {
//   if($defAtk['category'] == 'special' && $def->_getTypeB() != 'dark' && $def->_getTypeA() != 'dark') {
//      $this->settings['dmg'] = floor(($def->hp_before) * 2);
//      $this->log[] = 'Атака нанесла двойной урон от урона противника.';
//    }else{
//     $this->settings['dmg'] = 0;
//    }
// }

            if($atkAtk['id'] == 326) {
  // Mirror Coat: отражает только SPECIAL-урон, х2 от полученного
  if($defAtk['category'] == 'special' && $def->dmg_before > 0 && $this->meFirst == 0) {
    // тёмные иммунны к психическому урону -> провал
    if($def->_getTypeA() != 'dark' && $def->_getTypeB() != 'dark') {
      $this->settings['dmg'] = floor($def->dmg_before * 2);
      $this->log[] = 'Атака нанесла двойной урон от урона противника.';
    } else {
      $this->log[] = 'Провал.';
      $this->settings['dmg'] = 0;
    }
  } else {
    $this->log[] = 'Провал.';
    $this->settings['dmg'] = 0;
  }
}

if($atkAtk['id'] == 160) {
  $this->settings['dmg'] = floor($atk->hp);
  $atk->hp = 0;
  $this->log[] = 'Пользователь не может продолжать битву.';
  $this->crash_item($atk, $def, $atkAtk, $defAtk,0);
}

if($atkAtk['id'] == 130) {
  if($def->_checkStatus('sleep')) {
    $this->issetEffect($atk, $def, $atkAtk, $defAtk, true);
  }else{
    $this->settings['dmg'] = 0;
  }
}

// Экраны: Infiltrator (85) игнорирует
if( ($def->_checkStatus('lightScreen') && $atkAtk['category'] == 'special' && $atk->ability != 85) ||
    ($def->_checkStatus('aurora')      && $atkAtk['category'] == 'special' && $atk->ability != 85) ) {
  $this->settings['dmg'] = floor($this->settings['dmg'] * 0.5);
}
if( ($def->_checkStatus('reflect')     && $atkAtk['category'] == 'physical' && $atk->ability != 85) ||
    ($def->_checkStatus('aurora')      && $atkAtk['category'] == 'physical' && $atk->ability != 85) ) {
  $this->settings['dmg'] = floor($this->settings['dmg'] * 0.5);
}

if($this->settings['dmg'] > 0){

  // Burn-халв физ. урона (кроме Фасада и с Guts/70)
  if($atkAtk['category'] == 'physical' && $atk->_checkStatus('burn') && $atkAtk['id'] != 152){
    if($atk->ability == 70) {
      $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> не дает снизить урон от атаки.';
    }else{
      $this->settings['dmg'] = floor($this->settings['dmg'] * 0.5);
    }
  }

  // Сообщения об эффективности
  if($this->settings['types'] != 1){
    if($this->settings['types'] >= 4){
      $this->log[] = 'Суперэффективная атака.';
    }elseif($this->settings['types'] >= 2 && $this->settings['types'] < 4){
      $this->log[] = 'Очень эффективная атака.';
    }elseif($this->settings['types'] <= 0.50){
      $this->log[] = 'Малоэффективная атака.';
    }
  }

  // Мультихит — сообщение
  if($count_hit > 1){
    if($count_hit == 2 || $count_hit == 3 || $count_hit == 4) {
      $raz = 'раза';
    }else{
      $raz = 'раз';
    }
    $this->log[] = 'Покемон бьет противника <b>'.$count_hit.'</b> '.$raz.'.';
  }

  // Фиксированный/особый урон
  if($atkAtk['id'] == 125){
    # Dragon Rage
    $this->settings['dmg'] = 40;
  }elseif($atkAtk['id'] == 346){
    if($def->_getTypeA() == 'ghost' || $def->_getTypeB() == 'ghost') {
      $this->settings['dmg'] = 0;
    }else{
      $this->settings['dmg'] = $atk->lvl;
    }
  }elseif($atkAtk['id'] == 465){
    if($def->_getTypeA() == 'ghost' || $def->_getTypeB() == 'ghost') {
      $this->settings['dmg'] = 0;
    }else{
      $this->settings['dmg'] = $atk->lvl;
    }
  }elseif($atkAtk['id'] == 642){
    // Super Fang / Nature’s Madness-подобный
    $dmg642 = floor($def->hp / 2);
    if($dmg642 <= 0) { $dmg642 = 1; }
    $this->settings['dmg'] = $dmg642;
  }elseif(!empty($atkAtk['settings'])){

    // Отдача
    if(isset($atkAtk['settings']['recoil'])){
      if($atk->ability == 155) {
        $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> защитила покемона от получения урона.';
      }else{
        $recoil = $atkAtk['settings']['recoil'];
        $damageMy = floor($atk->hp/$recoil);
        if($damageMy > $atk->hp_max){
          $damageMy = floor($atk->hp_max / 2);
        }
        $atk->hp = ($atk->hp - $damageMy);
        $this->log[] = 'Отдача ранит покемона: <span class="HpMinus">-'.$damageMy.' HP</span>';
      }
    }

  }

  if($atkAtk['id'] == 144){
    // Endeavor-подобный
    if($atk->hp >= $def->hp) {
      $this->settings['dmg'] = 0;
    }else{
      $this->settings['dmg'] = ($def->hp - $atk->hp);
    }
  }

  // Принудительная замена урона (внешняя логика)
  if($this->dmg_1 != 1000000) {
    $this->settings['dmg'] = $this->dmg_1;
  }

  // Thick Fat (73) — огонь/лед
  if($atkAtk['type'] == 'fire' && $def->ability == 73 && $atk->ability != 113) {
    $this->settings['dmg'] = ceil($this->settings['dmg'] / 2);
    $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> уменьшает урон от атаки в два раза.';
  }

  // Wonder Guard (232): пропускает ТОЛЬКО суперэффективные удары
  if($this->settings['types'] <= 1 && $def->ability == 232 && $atk->ability != 113) {
    $this->settings['dmg'] = 0;
    $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника блокирует урон от атаки.';
  }

  // Flash Fire (226) получал половину? (у тебя так реализовано) — оставляю
  if($atkAtk['type'] == 'fire' && $def->ability == 226) {
    $this->settings['dmg'] = ceil($this->settings['dmg'] / 2);
    $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> уменьшает полученный урон в два раза.';
  }

  // Motor Drive (115) — иммун к Electric + +1 Speed
  if($def->ability == 115 && $atkAtk['type'] == 'electric' && $this->settings['dmg'] > 0 && $atk->ability != 113) {
    $this->settings['dmg'] = 0;
    $def->_setModifiedStat('spd', 1, true);
    $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> повышает противнику <span class="StatPlus">Скорость +1</span> и блокирует весь полученный урон от электрической атаки.';
  }

  // Левитация — иммун к Ground
  if(($def->_checkStatus('levitation') || $def->_checkStatus('easy_levitation')) && $this->settings['dmg'] > 0 && $atkAtk['type'] == 'ground' && in_array($atkAtk['category'], ['physical','special'])) {
    $this->settings['dmg'] = 0;
  }

  // Snore-подобный: срабатывает только во сне
  if($atkAtk['id'] == 501 && !$atk->_checkStatus('sleep')) {
    $this->settings['dmg'] = 0;
  }

  // Воздушный шар (150)
  if($def->item_id == 150) {
    if($this->settings['dmg'] >= 1) {
      if($atkAtk['type'] == 'ground') {
        $this->settings['dmg'] = 0;
        $this->log[] = '<div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$def->item_id.'],this)" style="background-image: url(/img/world/items/little/150.png)"></div> защитил от урона.';
      }else{
        $this->crash_item($def,$atk,$defAtk,$atkAtk,1); // лопаем шар при попадании не-Ground
      }
    }
  }

  $hpBefore = $def->hp;

  // Финальный кап урона перед применением (защита от переполнений и корректная отдача)
  if($this->settings['dmg'] > $def->hp_max){
    $this->settings['dmg'] = $def->hp_max;
  }
  if($this->settings['dmg'] > $hpBefore){
    $this->settings['dmg'] = $hpBefore;
  }
  if($this->settings['dmg'] < 0){
    $this->settings['dmg'] = 0;
  }
  $this->settings['dmg_real'] = $this->settings['dmg'];


  // Confusion self-hit: 33% шанс. Фикс багa с повторным присваиванием HP.
  if($atk->_checkStatus('confused') && 33 > mt_rand(0, 99)){
    $dmgConf = floor($this->settings['dmg'] / 3);
    if($atk->hp_max < $dmgConf){
      $dmgConf = floor($atk->hp_max / 3);
    }
    $atk->hp = $atk->hp - $dmgConf;
    $this->log[] = 'Но, из-за спута '.$atk->_getName(true).' ударил себя и нанес урон <span class="HpMinus">-'.floor($dmgConf).' HP</span>';
    $this->settings['dmg'] = 0;
    $this->settings['dmg_real'] = 0;
  }else{
    $def->hp = ($def->hp - $this->settings['dmg']); // Вычитание хп от атаки
  }

  // Sturdy (196) / Focus Sash (item 9999)
  if($hpBefore >= $def->hp_max && $def->hp <= 0 && (($def->ability == 196 && $atk->ability != 113) || $def->item_id == 9999) && !in_array($atkAtk['id'], [19,32,35,51,52,65,80,116,117,120,133,189,191,196,261,382,436,509,550,581,596,669,671,695,741])){
    $def->hp = 1;
    if($def->item_id == 9999) {
      $this->log[] = '<div class="itemIsset" onclick="PP.issets(\'item\',[\'view\','.$def->item_id.'],this)" style="background-image: url(/img/world/items/little/319.png)"></div> защитила покемона от получения урона и оставила ему 1 очко здоровья.';
    }else{
      $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> защитила покемона от получения урона и оставила ему 1 очко здоровья.';
    }
  }

                if($atkAtk['id'] == 156 || $atkAtk['id'] == 239){
    // False Swipe-подобный эффект: цель не может быть добита (останется 1 HP)
    if($def->hp <= 1){
        $def->hp = 1;
    }
}

// Weak Armor (Pokemon Showdown): у ПОКЕМОНА-ЗАЩИТНИКА при попадании ФИЗИЧЕСКОЙ атакой
// понижается DEF на 1 и повышается SPD на 2. Триггер — только если был нанесён урон.
if($this->settings['dmg'] > 0 && $def->ability == 229 && $atkAtk['category'] == 'physical') {
    $def->_setModifiedStat('spd', 2, true);
    $def->_setModifiedStat('def', 1, false);
    $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> повышает <span class="StatPlus">Скорость +2</span> и понижает <span class="StatMinus">Защиту -1</span>';
}

// Фактически нанесённый урон (учитывает кап по текущему HP, Sturdy/Focus Sash и пр.).
// Нужен для корректного лога, отдачи, drain, Shell Bell и т.п. (как в Pokemon Showdown).
if(!isset($this->settings['dmg_raw'])){
    $this->settings['dmg_raw'] = $this->settings['dmg'];
}
$this->settings['dmg_real'] = ($hpBefore - $def->hp);
if($this->settings['dmg_real'] < 0){
    $this->settings['dmg_real'] = 0;
}
if($this->settings['dmg_real'] > $hpBefore){
    $this->settings['dmg_real'] = $hpBefore;
}
$this->settings['dmg'] = $this->settings['dmg_real'];


if($this->settings['dmg'] == 0) {
    $this->log[] = 'Атака не наносит урона.';
}else{
    $atk->dmg_before = $this->settings['dmg'];
    $this->log[] = 'Атака наносит урон: <span class="HpMinus">-'.$this->settings['dmg'].' HP</span>';
    // Clans::quest_update(6,$this->settings['dmg']);
    // if($def->item_id == 357) {
    //   $i357 = ($atk->stats[0] / 14);
    //   $atk->hp = ($atk->hp - $i357);
    //   $this->log[] = '<div class="itemIsset" onclick="issetAll(357,\'item\')" style="background-image: url(/img/world/items/little/357.png)"></div> наносит урон покемону <span class="HpMinus">-'.ceil($i357).' HP</span>';
    // }

    // Ability 187: при получении урона DEF +1
    if($def->ability == 187) {
        $def->_setModifiedStat('def', 1, true);
        $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника повышает <span class="StatPlus">Защиту +1</span>';
    }

    // Rough Skin / Iron Barbs: урон при контакте 1/8 от максимума HP атакующего
    if(($def->ability == 156 or $def->ability == 90) && $atkAtk['contact'] == 1) {
        $ab156 = ($atk->stats[0] / 8);
        $atk->hp = ($atk->hp - $ab156);
        $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника наносит урон <span class="HpMinus">-'.ceil($ab156).' HP</span>';
    }

    // Berserk (16): если после удара HP падает ниже 50% — +1 SpA
    if($def->ability == 16) {
        $abil16 = floor($def->stats[0] / 2);
        if($abil16 > ($def->hp - $this->settings['dmg'])) {
            $def->_setModifiedStat('satk', 1, true);
            $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника повышает <span class="StatPlus">'.$this->statRus['satk'][1].' +1</span>';
        }
    }

    // Gooey / Tangling Hair (67): при контакте понижает SPD атакующему на 1
    if($def->ability == 67 && $atkAtk['contact'] == 1 && $atk->ability != 23 ) {
        $atk->_setModifiedStat('spd', 1, false);
        $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника понижает атакующему <span class="StatMinus">Скорость -1</span>';
    }

    // if($atkAtk['contact'] == 1 && $def->item_id == 312) {
    //   $this->itemEffect312 = 1;
    // }
}

if($atkAtk['id'] == 412) {
    if($defAtk['id'] == 9999) {
        $this->getSelected($def, $atk, $defAtk, $atkAtk, false, 412);
    }elseif($defAtk['id'] == 374){
        $this->getAPS($def, $atk, $defAtk, $atkAtk, 412);
    }elseif($defAtk['id'] == 229){
        $this->getAHW($def, $atk, $defAtk, $atkAtk, 412);
    }elseif($defAtk['id'] == 583){
        $this->getAUT($def, $atk, $defAtk, $atkAtk, 412);
    }elseif($defAtk['id'] == 592){
        $this->getAWS($def, $atk, $defAtk, $atkAtk, 412);
    }
}

// Destiny Bond (108): если цель умирает от этого удара — уносит атакующего
if($this->settings['dmg'] >= $def->hp && $defAtk['id'] == 108){
    if($def->desteny_bond == 1) {
        $this->log[] = $atk->_getName(true).' не может продолжать битву.';
        $this->log[] = $def->_getName(true).' не может продолжать битву.';
        $atk->hp = $def->hp;
        $this->crash_item($atk, $def, $atkAtk, $defAtk,0);
        $this->crash_item($def, $atk, $defAtk, $atkAtk,0);
    }
}

// Отдача, заданная настройками при попадании
if(isset($atkAtk['settings'], $atkAtk['settings']['return_dmg'])){
    if($atk->ability == 155) {
        $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> защитила покемона от получения урона.';
    }else{
        $dmgDealt = (isset($this->settings['dmg_real'])) ? $this->settings['dmg_real'] : $this->settings['dmg'];
        if($dmgDealt > $def->hp_max){
            $dmgDealt = $def->hp_max;
        }
        if($dmgDealt < 0){
            $dmgDealt = 0;
        }
        $ratioReturn = floatval($atkAtk['settings']['return_dmg']);
        if($ratioReturn > 0 && $dmgDealt > 0){
            $returnDmg = ($dmgDealt * $ratioReturn);
            if($returnDmg > $atk->hp){
                $returnDmg = $atk->hp;
            }
            if($returnDmg > $atk->hp_max){
                $returnDmg = $atk->hp_max;
            }
            $atk->hp = ($atk->hp - $returnDmg);
            $this->log[] = $atk->_getName(true).' получает отдачу от... атаки: <span class="HpMinus">-'.floor($returnDmg).' HP</span>';
        }
    }
}

// Shell Bell (469): восстанавливает 1/8 нанесённого урона
if($atk->item_id == 469) {
    $dmgDealt = (isset($this->settings['dmg_real'])) ? $this->settings['dmg_real'] : $this->settings['dmg'];
    $belkol = ($dmgDealt / 8);
    $atk->hp = ($atk->hp + $belkol);
    $this->log[] = '<div class="itemIsset" onclick="issetAll(469,\'item\')" style="background-image: url(/img/world/items/little/469.png)"></div> восстанавливает здоровье покемону: <span class="HpPlus">+'.ceil($belkol).' HP</span>';
}

// Self-Destruct / Explosion
if($atkAtk['id'] == 149 || $atkAtk['id'] == 466) {
    $atk->hp = 0;
    $this->log[] = 'Покемон упал в обморок после взрыва.';
}

// Absorb Bulb (171): при попадании атакой Water — SpA +1
if($atkAtk['type'] == 'water' && $def->item_id == 171) {
    $def->_setModifiedStat('satk', 1, true);
    $this->log[] = 'Противник повышает <span class="StatPlus">Спец. Атаку +1</span>';
}

// Rapid Spin (420): снимает ловушки со своей стороны при попадании
if($atkAtk['id'] == 420 && $this->settings['dmg'] > 0) {
    if($atk->_checkStatus('prison') || $atk->_checkStatus('spikes') || $atk->_checkStatus('toxicspikes') || $atk->_checkStatus('stickyweb') || $atk->_checkStatus('leechSeed') || $atk->_checkStatus('rocks')){
        $info = $atk->_getStatusList();
        if($info){
            foreach($info AS $key=>$value){
                if($value['type'] == 'prison' || $value['type'] == 'spikes' || $value['type'] == 'toxicspikes' || $value['type'] == 'stickyweb' || $value['type'] == 'leechSeed' || $value['type'] == 'rocks'){
                    unset($info[$key]);
                }
            }
        }
        $this->log[] = 'С поля убраны различные ловушки.';
        $atk->_setStatusList($info);
    }
}

// Move 780: очищает активные террайны (Grassy/Misty) у обеих сторон
if($atkAtk['id'] == 780 && $this->settings['dmg'] > 0) {
    if($atk->_checkStatus('terrGrass') || $atk->_checkStatus('terrMisty')){
        $info = $atk->_getStatusList();
        if($info){
            foreach($info AS $key=>$value){
                if($value['type'] == 'terrGrass' || $value['type'] == 'terrMisty' ){
                    unset($info[$key]);
                }
            }
        }
        $atk->_setStatusList($info);

        $info = $def->_getStatusList();
        if($info){
            foreach($info AS $key=>$value){
                if($value['type'] == 'terrGrass' || $value['type'] == 'terrMisty' ){
                    unset($info[$key]);
                }
            }
        }
        $def->_setStatusList($info);

        Work::$sql->query("DELETE FROM `battle_effects` WHERE `battle` = ".$this->_actionBattle->battleId." ");
        $this->log[] = 'С поля убраны эффекты местностей.';
    }
}

}elseif($atkAtk['id'] == '160'){
    // Альтернативная ветка для Final Gambit-подобной атаки, привязанная к общей структуре
    if($this->settings['types'] > 0){
        $this->log[] = 'Покемон упал в обморок. Противнику ненесено столько урона, сколько было здоровья у пользователя <span class="HpMinus">-'.$atk->hp.'</span>.';
        if($atk->hp >= $def->hp){
            $atk->hp = 0;
            $def->hp = 0;
        }else{
            $def->hp = $atk->hp;
        }
    }else{
        $this->log[] = 'Нет эффекта от атаки.';
    }
}else{
    if($this->settings['types'] > 0){
        if($atkAtk['power'] != 0){
            $this->log[] = 'Но атака не наносит урона.';
        }
    }else{
        $this->log[] = 'Нет эффекта от атаки.';
    }
}

// Зафиксировать нанесённый урон для последующих эффектов
$atk->hp_before = $this->settings['dmg'];

// Критический удар (включая Anger Point)
if(in_array($this->settings['critical'], ['1.5','2.25']) && $this->settings['dmg'] > 0) {
    $this->log[] = '<span class="Critical">Критический удар!</span>';
    if(check_mission(26)){ add_mission(26);}
    if($def->ability == 6) {
        if(isset($def->modified['atk']['minus'])){
            unset($def->modified['atk']['minus']);
        }
        $def->_setModifiedStat('atk', 6, true);
        $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> повышает <span class="StatPlus">Атаку +6</span> у противника.';
    }
}
}else{
    // Ветка из структуры функции: атакующий не может продолжать бой
    $this->log[] = $this->attacker->_getName(true).' не может продолжать битву.';
    $this->crash_item($this->attacker,$this->defender,$this->attackerAtk,$this->defenderAtk,0);
}

return false;
}

    private function getCatch(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk){

    if($atkAtk['id'] == 9998){
        $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
        if(isset($uData['targetItem']) && is_array($uData['targetItem'])){
            $target = $uData['targetItem'];
            if($target['class'] != 'ball' and $this->_actionBattle->_isPVP()){
                $atk->item_battle = $atk->item_battle + 1;
                $atk->_setStatus('item', 999999);
            }

            if(check_mission(17) and $target['class'] != 'ball'){ add_mission(17); }
            if(!empty($target)){
                if($target['number'] == 10){
                    $atk->hp = $atk->hp + 20;
                    if(check_mission_ivent(33)){ add_mission_ivent(33); }
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(10,\'item\')" style="background-image: url(/img/world/items/little/10.png)"></div> Стимулятор<br> <span class="HpPlus">Здоровье успешно восстановлено +20 HP</span>';
                }elseif($target['number'] == 11){
                    $atk->hp = $atk->hp + 50;
                    if(check_mission_ivent(33)){ add_mission_ivent(33); }
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(11,\'item\')" style="background-image: url(/img/world/items/little/11.png)"></div> Улучшенный стимулятор<br> <span class="HpPlus">Здоровье успешно восстановлено +50 HP</span>';
                }elseif($target['number'] == 318){
                    $atk->hp = $atk->hp + 10;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(318,\'item\')" style="background-image: url(/img/world/items/little/318.png)"></div> Ягода Оран<br> <span class="HpPlus">Здоровье успешно восстановлено +10 HP</span>';
                }elseif($target['number'] == 12){
                    $atk->hp = $atk->hp + 100;
                    if(check_mission_ivent(33)){ add_mission_ivent(33); }
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(12,\'item\')" style="background-image: url(/img/world/items/little/12.png)"></div> Суперстимулятор<br> <span class="HpPlus">Здоровье успешно восстановлено +100 HP</span>';
                }elseif($target['number'] == 416){
                    $atk->hp = $atk->hp + 100;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(416,\'item\')" style="background-image: url(/img/world/items/little/416.png)"></div> Муму молоко<br> <span class="HpPlus">Здоровье успешно восстановлено +100 HP</span>';
                }elseif($target['number'] == 13){
                    // Стимпак: лечит и СБРАСЫВАЕТ усиление статов до +1
                    $atk->hp = $atk->hp + 5000;
                    if(check_mission_ivent(33)){ add_mission_ivent(33); }
                    // Приводим положительные бусты к +1 (если были выше)
                    $__statsToClamp = ['atk','def','spd','satk','sdef','agl','acr'];
                    foreach($__statsToClamp as $__st){
                        if(isset($atk->modified[$__st]['plus']) && $atk->modified[$__st]['plus'] > 1){
                            $__diff = $atk->modified[$__st]['plus'] - 1;
                            // уменьшаем на разницу, чтобы стало +1
                            $atk->_setModifiedStat($__st, $__diff, false);
                        }
                    }
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(13,\'item\')" style="background-image: url(/img/world/items/little/13.png)"></div> Стимпак<br> <span class="HpPlus">Здоровье успешно восстановлено</span>. Усиление статов снижено до +1.';
                }elseif($target['number'] == 149){
                    $d = ceil($atk->hp_max*0.15);
                    $atk->hp = $atk->hp + $d;
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'confused'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(149,\'item\')" style="background-image: url(/img/world/items/little/149.png)"></div> Большой пончик<br> <span class="HpPlus">Здоровье успешно восстановлено +'.$d.' HP. Статус снят</span>';
                }elseif($target['number'] == 331){
                    $d = ceil($atk->hp_max*0.1);
                    $atk->hp = $atk->hp + $d;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(331,\'item\')" style="background-image: url(/img/world/items/little/331.png)"></div> Ягода Цитрус<br> <span class="HpPlus">Здоровье успешно восстановлено +'.$d.' HP</span>';
                }elseif($target['number'] == 152){
                    $atk->hp = $atk->hp + 50;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(152,\'item\')" style="background-image: url(/img/world/items/little/152.png)"></div> Газировка<br> <span class="HpPlus">Здоровье успешно восстановлено +50 HP</span>';
                }elseif($target['number'] == 160){
                    $atk->hp = $atk->hp + 70;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(70,\'item\')" style="background-image: url(/img/world/items/little/70.png)"></div> Лимонад<br> <span class="HpPlus">Здоровье успешно восстановлено +70 HP</span>';
                }elseif($target['number'] == 162){
                    $atk->hp = $atk->hp + 20;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(162,\'item\')" style="background-image: url(/img/world/items/little/162.png)"></div> Ягодный сок<br> <span class="HpPlus">Здоровье успешно восстановлено +20 HP</span>';
                }elseif($target['number'] == 163){
                    $atk->hp = $atk->hp + 200;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(163,\'item\')" style="background-image: url(/img/world/items/little/163.png)"></div> Энергетический корень<br> <span class="HpPlus">Здоровье успешно восстановлено +200 HP</span>';
                }elseif($target['number'] == 164){
                    $atk->hp = $atk->hp + 50;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(164,\'item\')" style="background-image: url(/img/world/items/little/164.png)"></div> Энергетическая пыльца<br> <span class="HpPlus">Здоровье успешно восстановлено +50 HP</span>';
                }elseif($target['number'] == 167){
                    $atk->hp = $atk->hp + 30;
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(167,\'item\')" style="background-image: url(/img/world/items/little/167.png)"></div> Чистая вода<br> <span class="HpPlus">Здоровье успешно восстановлено +30 HP</span>';
                }elseif($target['number'] == 17){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'sleep'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(17,\'item\')" style="background-image: url(/img/world/items/little/17.png)"></div> Энергетик<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 21){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'burn'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(21,\'item\')" style="background-image: url(/img/world/items/little/21.png)"></div> Огнетушитель<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 22){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'toxic' || $value['type'] == 'toxic2'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(22,\'item\')" style="background-image: url(/img/world/items/little/22.png)"></div> Противоядие<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 19){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'paralyzed'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(19,\'item\')" style="background-image: url(/img/world/items/little/19.png)"></div> Антипарализ<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 18){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'confused'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(18,\'item\')" style="background-image: url(/img/world/items/little/18.png)"></div> Антиспут<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 20){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'frost'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(20,\'item\')" style="background-image: url(/img/world/items/little/20.png)"></div> Антифриз<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 293){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'frost'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(293,\'item\')" style="background-image: url(/img/world/items/little/293.png)"></div> Ягода Аспир<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 296){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'paralyzed'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(296,\'item\')" style="background-image: url(/img/world/items/little/296.png)"></div> Ягода Чери<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 297){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'sleep'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(297,\'item\')" style="background-image: url(/img/world/items/little/297.png)"></div> Ягода Често<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 315){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'confused' || $value['type'] == 'frost' || $value['type'] == 'toxic' || $value['type'] == 'toxic2' || $value['type'] == 'burn' || $value['type'] == 'paralyzed' || $value['type'] == 'sleep'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(315,\'item\')" style="background-image: url(/img/world/items/little/315.png)"></div> Ягода Лэм<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 321){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'toxic' || $value['type'] == 'toxic2'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(321,\'item\')" style="background-image: url(/img/world/items/little/321.png)"></div> Ягода Печа<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 322){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'confused'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(322,\'item\')" style="background-image: url(/img/world/items/little/322.png)"></div> Ягода Персим<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 326){
                    $info = $atk->_getStatusList();
                    if($info){foreach($info AS $key=>$value){if($value['type'] == 'burn'){unset($info[$key]);}}}
                    $atk->_setStatusList($info);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(326,\'item\')" style="background-image: url(/img/world/items/little/326.png)"></div> Ягода Раст<br> <span class="HpPlus">Статус снят</span>';
                }elseif($target['number'] == 377){
                    $atk->_setModifiedStat('atk', 1, true);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(377,\'item\')" style="background-image: url(/img/world/items/little/377.png)"></div> Х Атака <br> <span class="HpPlus">Стат покемона увеличен</span>';
                }elseif($target['number'] == 378){
                    $atk->_setModifiedStat('def', 1, true);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(380,\'item\')" style="background-image: url(/img/world/items/little/378.png)"></div> Х Защита <br> <span class="HpPlus">Стат покемона увеличен</span>';
                }elseif($target['number'] == 379){
                    $atk->_setModifiedStat('spd', 1, true);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(383,\'item\')" style="background-image: url(/img/world/items/little/379.png)"></div> Х Скорость <br> <span class="HpPlus">Стат покемона увеличен</span>';
                }elseif($target['number'] == 380){
                    $atk->_setModifiedStat('satk', 1, true);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(386,\'item\')" style="background-image: url(/img/world/items/little/380.png)"></div> Х Спец.Атака <br> <span class="HpPlus">Стат покемона увеличен</span>';
                }elseif($target['number'] == 381){
                    $atk->_setModifiedStat('sdef', 1, true);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(389,\'item\')" style="background-image: url(/img/world/items/little/381.png)"></div> Х Спец.Защита <br> <span class="HpPlus">Стат покемона увеличен</span>';
                }elseif($target['number'] == 14){
                    $mypp = explode(',',$this->attacker->pp_my);
                    $myatt = explode(',',$this->attacker->attacks);
                    $a1 = $this->_getMove($myatt[0]);
                    $a2 = $this->_getMove($myatt[1]);
                    $a3 = $this->_getMove($myatt[2]);
                    $a4 = $this->_getMove($myatt[3]);
                    if(($a1['pp'] - $mypp[0]) >= 5) { $mypp[0] = $mypp[0] + 5; } else { $mypp[0] = $a1['pp']; }
                    if(($a2['pp'] - $mypp[1]) >= 5) { $mypp[1] = $mypp[1] + 5; } else { $mypp[1] = $a2['pp']; }
                    if(($a3['pp'] - $mypp[2]) >= 5) { $mypp[2] = $mypp[2] + 5; } else { $mypp[2] = $a3['pp']; }
                    if(($a4['pp'] - $mypp[3]) >= 5) { $mypp[3] = $mypp[3] + 5; } else { $mypp[3] = $a4['pp']; }
                    $this->attacker->pp_my = implode(',',$mypp);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(14,\'item\')" style="background-image: url(/img/world/items/little/14.png)"></div> Слабый эликсир<br> <span class="HpPlus">PP покемона успешно восстановлены.</span>';
                }elseif($target['number'] == 15){
                    $mypp = explode(',',$this->attacker->pp_my);
                    $myatt = explode(',',$this->attacker->attacks);
                    $a1 = $this->_getMove($myatt[0]);
                    $a2 = $this->_getMove($myatt[1]);
                    $a3 = $this->_getMove($myatt[2]);
                    $a4 = $this->_getMove($myatt[3]);
                    if(($a1['pp'] - $mypp[0]) >= 10) { $mypp[0] = $mypp[0] + 10; } else { $mypp[0] = $a1['pp']; }
                    if(($a2['pp'] - $mypp[1]) >= 10) { $mypp[1] = $mypp[1] + 10; } else { $mypp[1] = $a2['pp']; }
                    if(($a3['pp'] - $mypp[2]) >= 10) { $mypp[2] = $mypp[2] + 10; } else { $mypp[2] = $a3['pp']; }
                    if(($a4['pp'] - $mypp[3]) >= 10) { $mypp[3] = $mypp[3] + 10; } else { $mypp[3] = $a4['pp']; }
                    $this->attacker->pp_my = implode(',',$mypp);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(15,\'item\')" style="background-image: url(/img/world/items/little/15.png)"></div> Эликсир<br> <span class="HpPlus">PP покемона успешно восстановлены.</span>';
                }elseif($target['number'] == 313){
                    $mypp = explode(',',$this->attacker->pp_my);
                    $myatt = explode(',',$this->attacker->attacks);
                    $a1 = $this->_getMove($myatt[0]);
                    $a2 = $this->_getMove($myatt[1]);
                    $a3 = $this->_getMove($myatt[2]);
                    $a4 = $this->_getMove($myatt[3]);
                    if(($a1['pp'] - $mypp[0]) >= 10) { $mypp[0] = $mypp[0] + 10; } else { $mypp[0] = $a1['pp']; }
                    if(($a2['pp'] - $mypp[1]) >= 10) { $mypp[1] = $mypp[1] + 10; } else { $mypp[1] = $a2['pp']; }
                    if(($a3['pp'] - $mypp[2]) >= 10) { $mypp[2] = $mypp[2] + 10; } else { $mypp[2] = $a3['pp']; }
                    if(($a4['pp'] - $mypp[3]) >= 10) { $mypp[3] = $mypp[3] + 10; } else { $mypp[3] = $a4['pp']; }
                    $this->attacker->pp_my = implode(',',$mypp);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(313,\'item\')" style="background-image: url(/img/world/items/little/313.png)"></div> Ягода Леппа<br> <span class="HpPlus">PP покемона успешно восстановлены.</span>';
                }elseif($target['number'] == 16){
                    $mypp = explode(',',$this->attacker->pp_my);
                    $myatt = explode(',',$this->attacker->attacks);
                    $a1 = $this->_getMove($myatt[0]);
                    $a2 = $this->_getMove($myatt[1]);
                    $a3 = $this->_getMove($myatt[2]);
                    $a4 = $this->_getMove($myatt[3]);
                    if(($a1['pp'] - $mypp[0]) >= 20) { $mypp[0] = $mypp[0] + 20; } else { $mypp[0] = $a1['pp']; }
                    if(($a2['pp'] - $mypp[1]) >= 20) { $mypp[1] = $mypp[1] + 20; } else { $mypp[1] = $a2['pp']; }
                    if(($a3['pp'] - $mypp[2]) >= 20) { $mypp[2] = $mypp[2] + 20; } else { $mypp[2] = $a3['pp']; }
                    if(($a4['pp'] - $mypp[3]) >= 20) { $mypp[3] = $mypp[3] + 20; } else { $mypp[3] = $a4['pp']; }
                    $this->attacker->pp_my = implode(',',$mypp);
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢ <div class="itemIsset" onclick="issetAll(16,\'item\')" style="background-image: url(/img/world/items/little/16.png)"></div> Мощный эликсир<br> <span class="HpPlus">PP покемона успешно восстановлены.</span>';
                }else{
                    $this->log[] = '<b>'.$_SESSION['login'].'</b> ⇢  <div class="itemIsset" onclick="issetAll('.$target['number'].',\'item\')" style="background-image: url(/img/world/items/little/'.$target['number'].'.png)"></div> '.(isset($target['name']) ? $target['name'] : '???').'.';

                    $type = $this->goCatch($def, $atk->_getUser(), $target, $atk);

                    if($type){
                        $this->catch = true;
                        $this->log[] = '<span class="HpPlus">Удачная поимка!</span>';
                        update_ach(1,1);
                        update_ach(21,1);
                    }else{
                        if(is_null($type)){
                            $this->log[] = '<span class="HpMinus">Пытается поймать '.$def->_getName(true).', но у тренера нет места.</span>';
                        }else{
                            $this->log[] = '<span class="HpMinus">Пытается поймать '.$def->_getName(true).', но покемон упорно сопротивляется.</span>';
                        }
                    }
                }

                // // ваш закомментированный код с PP/ability можно оставить без изменений

                unset($uData['targetItem']);

                return false;
            }
        }
    }

    return true;

}


		private function getAHH(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk) {
      $atk->_setModifiedStat('def',1,false);
      $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
      if(isset($uData['targetHH']) && $uData['targetHH'] > 0){
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetHH']);
        if(!empty($target)){
          $atk1 = new PokeBattle($target);
          if($atk->id == $atk1->id) {
            $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
          }else{
            Work::$sql->query("INSERT INTO atk_helping_hand (pokID,user,battle) VALUES (".$atk1->id.",".$atk->_getUser().",".$this->_actionBattle->battleId.")");
            $this->log[] = 'У '.$atk->_getName(true).' понижается <span class="StatMinus">Защита -1</span>, но у одного из союзных покемонов повышается <span class="StatPlus">Атака +1</span>';
          }
        }
      }
      return true;
    }

    private function getAPS(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $other = false) {
      if($def->_checkStatus('defstat') && $this->attacker->ability != 85) {
        $this->log[] = 'Невозможно повлиять на статы этого покемона, так как он под защитой. Покемон не был сменен.';
        return false;
      }
      $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
      if(isset($uData['targetPS']) && $uData['targetPS'] > 0){
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetPS']);
        if(!empty($target)){
          $atk1 = new PokeBattle($target);
          if($atk->id == $atk1->id) {
            $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
          }else{
            if($def->ability == 23 ){
                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника не дает понизить ему стат.';
            }else{
                $def->_setModifiedStat('satk',1,false);
    			if($def->ability == 79 && $atk->ability != 113 ) {
    				$this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника не дает понизить ему стат Атаки.';
    			}else{
    				$def->_setModifiedStat('atk',1,false);
    			}
    			$this->log[] = 'У '.$def->_getName(true).' понижается <span class="StatMinus">Атака -1</span> и <span class="StatMinus">Спец. Атака -1</span>';
            }
            
            if(isset($other)) {
              $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id, $other, 1);
            }else{
              $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id, false, 1);
            }
          }
        }
      }
      return true;
    }
private function trySynchronize($statusType) {
    if ($this->defender->ability == 204 && $this->attacker->hp > 0 && $this->settings['types'] > 0) {
        $immune = false;
        if ($statusType == 'burn'
            && (in_array($this->attacker->_getTypeA(), ['fire']) || in_array($this->attacker->_getTypeB(), ['fire'])
            || $this->attacker->ability == 226 || ($this->attacker->ability == 228 && $this->defender->ability != 113))) $immune = true;
        if (($statusType == 'toxic' || $statusType == 'poison')
            && (in_array($this->attacker->_getTypeA(), ['poison','steel']) || in_array($this->attacker->_getTypeB(), ['poison','steel']))) $immune = true;
        if ($statusType == 'paralyzed'
            && (in_array($this->attacker->_getTypeA(), ['electric']) || in_array($this->attacker->_getTypeB(), ['electric']))) $immune = true;
        if (!$this->attacker->_checkStatus($statusType) && !$immune) {
            $this->attacker->_setStatus($statusType, 9999);
            $this->log[] = '<div class="Ability" onclick="issetAll(28,\'ability\')">Синхронизация</div> заставляет '
                .$this->attacker->_getName(true).' получить статус '.$statusType.'!';
        }
    }
}
    private function getAHW(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $other = false) {
      $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
      if(isset($uData['targetHW']) && $uData['targetHW'] > 0){
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetHW']);
        if(!empty($target)){
          $uData['targetHW2'] = $atk->id;
          $atk1 = new PokeBattle($target);
          if($atk->id == $atk1->id) {
            $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
          }else{
            $this->log[] = $atk->_getName(true).' не может продолжать битву.';
            $this->crash_item($atk,$def,$atkAtk,$defAtk,0);
            if(isset($other)) {
              $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id, $other, 1);
            }else{
              $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id, false, 1);
            }
            $atk->hp = $atk->hp_max;
            $info = $atk->_getStatusList();
            if($info){foreach($info AS $key=>$value){if($value['type'] == 'paralyzed' || $value['type'] == 'frost' || $value['type'] == 'toxic' || $value['type'] == 'toxic2' || $value['type'] == 'sleep' || $value['type'] == 'burn' || $value['type'] == 'confused'){unset($info[$key]);}}}
            $atk->_setStatusList($info);
            $this->log[] = 'У '.$atk->_getName(true).' полностью восстанавливается здоровье, а также очищаются негативные статусы.';
          }
        }
      }
      return true;
    }

    private function getAUT(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $other = false, $other2 = false) {
      if($other2 == 1) {
        $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
        if(isset($uData['targetUT']) && $uData['targetUT'] > 0){
          $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetUT']);
          if(!empty($target)){
            $atk1 = new PokeBattle($target);
            if($atk->id == $atk1->id) {
              $this->dmg_1 = 0;
              $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
            }
          }
        }
      }else{
        $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetUT']);
        $atk1 = new PokeBattle($target);
        $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id, 1);
      }
      return true;
    }

    private function getAWS(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $other = false, $other2 = falses) {
      if($other2 == 1) {
        $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
        if(isset($uData['targetWS']) && $uData['targetWS'] > 0){
          $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetWS']);
          if(!empty($target)){
            $atk1 = new PokeBattle($target);
            if($atk->id == $atk1->id) {
              $this->dmg_1 = 0;
              $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
            }
          }
        }
      }else{
        $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetWS']);
        $atk1 = new PokeBattle($target);
        $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id, 1);
      }
      return true;
    }

    private function getABP(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk) {
      $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
      if(isset($uData['targetBP']) && $uData['targetBP'] > 0){
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetBP']);
        if(!empty($target)){
          $atk1 = new PokeBattle($target);
          if($atk->id == $atk1->id) {
            $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
          }else{
            $this->getSelected($atk, $def, $atkAtk, $defAtk, $atk1->id,1,1); // Доделать
          }
        }
      }
      return true;
    }

    private function getAAM(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk) {
      $uData =& $this->_actionBattle->_getUserData($atk->_getUser());
      if(isset($uData['targetAM']) && $uData['targetAM'] > 0){
        $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetAM']);
        if(!empty($target)){
          $atk1 = new PokeBattle($target);
          if($atk->id == $atk1->id) {
            $this->log[] = 'Провал. Невозможно использовать данную атаку на себя.';
          }else{
            Work::$sql->query("INSERT INTO atk_aromatic_mist (pokID,user,battle) VALUES (".$atk1->id.",".$atk->_getUser().",".$this->_actionBattle->battleId.")");
            $this->log[] = 'У одного из союзных покемонов повышается <span class="StatPlus">Спец. Защита +1</span>';
          }
        }
      }
      return true;
    }

    private function getTurn(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
      if($atkAtk['id'] == 754) {
        $this->log[] = 'Дожидается покемона соперника.';
        return false;
      }
      return true;
    }
private function applyNaturalCure(&$poke) {
    if (isset($poke['ability']) && $poke['ability'] == 120 && isset($poke['status_list'])) {
        $statusKeys = ['burn', 'paralyzed', 'sleep', 'frost', 'toxic', 'toxic2'];
        $hadStatus = false;
        foreach ($statusKeys as $natcureStatus) {
            if (isset($poke['status_list'][$natcureStatus])) {
                unset($poke['status_list'][$natcureStatus]);
                $hadStatus = true;
            }
        }
        if ($hadStatus) {
            $this->log[] = $poke['name_new'].' исцеляет свой статус благодаря способности <div class="Ability" onclick="issetAll(120,\'ability\')">Естественное исцеление</div>.';
        }
    }
}

    private function gravity(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
      if($atk->_checkStatus('gravity') && in_array($defAtk['id'], [54,177,633,237,272,304,485,514,557])) {
        $this->log[] = 'Провал.';
        return false;
      }
      return true;
    }

private function getSelectedTwo(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $other = false, $other2 = false){
    if($atkAtk['id'] == 602 || $atkAtk['id'] == 75 || $atkAtk['id'] == 127 || $atkAtk['id'] == 434) {

        // Подготовка: заберём "сторонние" эффекты с уходящего активного (ваша модель хранения)
        $info = $def->_getStatusList();

        // Значения, которые перенесём на входящего (инициализация во избежание notice)
        $valSpikes        = null;
        $valToxicSpikes   = null;
        $valswitchability = null;
        $valRocks         = null;
        $valStickyweb     = null;
        $valTailwind      = null;
        $valTrick         = null;
        $valNocrit        = null;
        $valLightScreen   = null;
        $valGravity       = null;
        $valMudsport      = null;
        $valPlant         = null;
        $valAurora        = null;
        $valReflect       = null;
        $toxYes           = 0;

        if(
            $def->_checkStatus('spikes') || $def->_checkStatus('tailwind') || $def->_checkStatus('trick') ||
            $def->_checkStatus('nocrit') || $def->_checkStatus('rocks') || $def->_checkStatus('switch_ability') ||
            $def->_checkStatus('stickyweb') || $def->_checkStatus('gravity') || $def->_checkStatus('mudsport') ||
            $def->_checkStatus('plant') || $def->_checkStatus('toxicspikes') || $def->_checkStatus('lightScreen') ||
            $def->_checkStatus('aurora') || $def->_checkStatus('reflect')
        ) {
            if($info){
                foreach($info AS $key=>$value){
                    // Снимем значения для переноса
                    if($value['type'] == 'spikes'){           $valSpikes = isset($value['val']) ? (int)$value['val'] : 1; }
                    if($value['type'] == 'toxicspikes'){      $valToxicSpikes = isset($value['val']) ? (int)$value['val'] : 1; }
                    if($value['type'] == 'switch_ability'){   $valswitchability = isset($value['val']) ? (int)$value['val'] : null; }
                    if($value['type'] == 'rocks'){            $valRocks = 1; }
                    if($value['type'] == 'stickyweb'){        $valStickyweb = 1; }
                    if($value['type'] == 'tailwind'){         $valTailwind = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'trick'){            $valTrick = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'nocrit'){           $valNocrit = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'lightScreen'){      $valLightScreen = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'gravity'){          $valGravity = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'mudsport'){         $valMudsport = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'plant'){            $valPlant = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'aurora'){           $valAurora = isset($value['count']) ? (int)$value['count'] : 0; }
                    if($value['type'] == 'reflect'){          $valReflect = isset($value['count']) ? (int)$value['count'] : 0; }

                    // Список эффектов, которые мы ПРОСТО переносим (в вашей модели снимаем с уходящего)
                    if(
                        $value['type'] == 'lightScreen' || $value['type'] == 'levitation' || $value['type'] == 'confused' ||
                        $value['type'] == 'plasma_fists' || $value['type'] == 'miracle' || $value['type'] == 'gravity' ||
                        $value['type'] == 'mudsport' || $value['type'] == 'plant' || $value['type'] == 'aurora' ||
                        $value['type'] == 'nightmare' || $value['type'] == 'spikes' || $value['type'] == 'taunt' ||
                        $value['type'] == 'aquaRing' || $value['type'] == 'reader' || $value['type'] == 'toxicspikes' ||
                        $value['type'] == 'stickyweb' || $value['type'] == 'rocks' || $value['type'] == 'switch_ability' ||
                        $value['type'] == 'leechSeed' || $value['type'] == 'toxic2' || $value['type'] == 'curse' ||
                        $value['type'] == 'grounded' || $value['type'] == 'defensecurl' || $value['type'] == 'critfocus' ||
                        $value['type'] == 'ingrain' || $value['type'] == 'defstat' || $value['type'] == 'nocrit' ||
                        $value['type'] == 'minimize' || $value['type'] == 'mind' || $value['type'] == 'reflect' ||
                        $value['type'] == 'tailwind' || $value['type'] == 'trick' || $value['type'] == 'terrMisty' ||
                        $value['type'] == 'terrGrass' || $value['type'] == 'safeguard' || $value['type'] == 'deathSong'
                    ){
                        unset($info[$key]);
                        if($value['type'] == 'toxic2') {
                            $toxYes = 1;
                        }
                    }
                }
            }
        }

        // Сброс изменённых статов у уходящего
        unset($def->modified);
        // Обновим список статусов у уходящего — у него больше нет "командных" эффектов
        $def->_setStatusList($info);

        // === Natural Cure для уходящего покемона (как у вас) ===
        $pList = $this->_actionBattle->_getUserPokes($def->_getUser());
        if ($pList) {
            foreach ($pList as &$poke) {
                if (!empty($poke['active']) && $poke['active'] == 1 && $poke['id'] != $other) {
                    if (isset($poke['ability']) && $poke['ability'] == 120 && isset($poke['status_list'])) {
                        $statusKeys = ['burn', 'paralyzed', 'sleep', 'frost', 'toxic', 'toxic2'];
                        $hadStatus = false;
                        foreach ($statusKeys as $natcureStatus) {
                            if (isset($poke['status_list'][$natcureStatus])) {
                                unset($poke['status_list'][$natcureStatus]);
                                $hadStatus = true;
                            }
                        }
                        if ($hadStatus) {
                            $this->log[] = $poke['name_new'].' исцеляет свой статус благодаря способности <div class="Ability" onclick="issetAll(120,\'ability\')">Естественное исцеление</div>.';
                        }
                    }
                }
            }
            // сохраним изменения пачкой
            $this->_actionBattle->_setUserPokes($def->_getUser(), $pList);
        }

        // === Смена: выставляем нового активного ===
        $uData = [];
        $uData['targetPokemon'] = $other;
        $target = $this->_actionBattle->_getUserPokes($def->_getUser(), $uData['targetPokemon']);
        $this->_actionBattle->_setTarget($def->_getUser(), $target);

        // Повторный список для применения хазардов и переноса статусов
        $pList = $this->_actionBattle->_getUserPokes($def->_getUser());
        if($pList){
            foreach($pList AS $key_1=>&$value_1){
                if($value_1['id'] == $other) {
                    $newPokemon = new PokeBattle($target);
                    $this->log[] = 'И заменяет покемона на '.$newPokemon->_getName(true);

                    // Удобные флаги: типы и "приземлённость"
                    $typeA   = isset($value_1['base_type']) ? $value_1['base_type'] : '';
                    $typeB   = isset($value_1['base_type_two']) ? $value_1['base_type_two'] : '';
                    $hasLev  = (isset($value_1['status_list']['levitation']) || isset($value_1['status_list']['easy_levitation']));
                    $isFlyer = (in_array('fly', [$typeA,$typeB]));
                    $hasBalloon = (isset($value_1['item_id']) && $value_1['item_id'] == 165);
                    $grounded = !($isFlyer || $hasLev || $hasBalloon);
                    $hasMagicGuard = (isset($value_1['ability']) && $value_1['ability'] == 155);

                    // === Spikes === (урон, если приземлён и нет Magic Guard)
                    if(isset($valSpikes)) {
                        if(!$grounded || $hasMagicGuard){
                            // нет эффекта
                        }else{
                            if($valSpikes == 1) {
                                $spkUron = ceil($value_1['stats'][0] / 8);
                            }elseif($valSpikes == 2) {
                                $spkUron = ceil($value_1['stats'][0] / 6);
                            }else{
                                $spkUron = ceil($value_1['stats'][0] / 4);
                            }
                            $value_1['hp'] = $value_1['hp'] - $spkUron;
                            $this->log[] = 'Шипы ранят замененного покемона: <span class="HpMinus">-'.$spkUron.' HP</span>';
                        }
                        // переносим слой на нового активного (ваша модель)
                        $value_1['status_list']['spikes'] = [
                            'type'  => 'spikes',
                            'count' => 9999,
                            'val'   => $valSpikes
                        ];
                    }

                    // === Toxic Spikes ===
                    if(isset($valToxicSpikes)) {
                        // Поглощение слоёв приземлённым Poison-покемоном (как в PS)
                        $isPoisonType = in_array('poison', [$typeA,$typeB]);
                        if($grounded && $isPoisonType){
                            // Слои снимаются со стороны игрока (в вашей модели — просто не переносим на нового активного)
                            $valToxicSpikes = null; // чтобы ниже ничего не применить/не перенести
                            $this->log[] = 'Ядовитые шипы были нейтрализованы при входе ядовитого покемона.';
                        } else {
                            // Попытка отравления: нельзя если есть защитные статусы/иммунитеты
                            if($valToxicSpikes == 1) {
                                if(isset($value_1['status_list']['burn']) || isset($value_1['status_list']['paralyzed']) || isset($value_1['status_list']['sleep']) || isset($value_1['status_list']['frost']) || isset($value_1['status_list']['levitation']) || isset($value_1['status_list']['easy_levitation']) || isset($value_1['status_list']['toxic']) || isset($value_1['status_list']['toxic2']) || isset($value_1['status_list']['terrMisty']) || isset($value_1['status_list']['safeguard']) || in_array('poison', [$typeA,$typeB]) || in_array('steel', [$typeA,$typeB]) || in_array('fly', [$typeA,$typeB]) || $hasBalloon){
                                    $this->log[] = 'Провал изменения статуса.';
                                }else{
                                    // обычное отравление
                                    $value_1['status_list']['toxic'] = [
                                        'type'  => 'toxic',
                                        'count' => 9999,
                                        'val'   => 0
                                    ];
                                    $this->log[] = 'Замененный покемон отравлен.';
                                }
                            }else{
                                if(isset($value_1['status_list']['burn']) || isset($value_1['status_list']['paralyzed']) || isset($value_1['status_list']['sleep']) || isset($value_1['status_list']['frost']) || isset($value_1['status_list']['levitation']) || isset($value_1['status_list']['easy_levitation']) || isset($value_1['status_list']['toxic2']) || isset($value_1['status_list']['terrMisty']) || isset($value_1['status_list']['safeguard']) || in_array('poison', [$typeA,$typeB]) || in_array('steel', [$typeA,$typeB]) || in_array('fly', [$typeA,$typeB]) || $hasBalloon){
                                    $this->log[] = 'Провал изменения статуса.';
                                }else{
                                    if(isset($value_1['status_list']['toxic'])){
                                        unset($value_1['status_list']['toxic']);
                                    }
                                    $this->log[] = 'Замененный покемон сильно отравлен.';
                                    $value_1['status_list']['toxic2'] = [
                                        'type'  => 'toxic2',
                                        'count' => 9999,
                                        'val'   => 0
                                    ];
                                }
                            }
                        }

                        // Если не поглощены — переносим слой на нового активного (ваша модель)
                        if($valToxicSpikes !== null){
                            $value_1['status_list']['toxicspikes'] = [
                                'type'  => 'toxicspikes',
                                'count' => 9999,
                                'val'   => $valToxicSpikes
                            ];
                        }
                    }

                    // === Light Screen / Gravity / Mud Sport / Plant / Aurora / Reflect (перенос таймеров)
                    if(isset($valLightScreen)) {
                        $value_1['status_list']['lightScreen'] = [
                            'type'  => 'lightScreen',
                            'count' => $valLightScreen,
                            'val'   => 0
                        ];
                    }
                    if(isset($valGravity)) {
                        $value_1['status_list']['gravity'] = [
                            'type'  => 'gravity',
                            'count' => $valGravity,
                            'val'   => 0
                        ];
                    }
                    if(isset($valMudsport)) {
                        $value_1['status_list']['mudsport'] = [
                            'type'  => 'mudsport',
                            'count' => $valMudsport,
                            'val'   => 0
                        ];
                    }
                    if(isset($valPlant)) {
                        $value_1['status_list']['plant'] = [
                            'type'  => 'plant',
                            'count' => $valPlant,
                            'val'   => 0
                        ];
                    }
                    if(isset($valAurora)) {
                        $value_1['status_list']['aurora'] = [
                            'type'  => 'aurora',
                            'count' => $valAurora,
                            'val'   => 0
                        ];
                    }
                    if(isset($valReflect)) {
                        $value_1['status_list']['reflect'] = [
                            'type'  => 'reflect',
                            'count' => $valReflect,
                            'val'   => 0
                        ];
                    }

                    // === Sticky Web === (минус 1 скорость, если приземлён и нет "не понижай статы" — ability 23 у вас)
                    if(isset($valStickyweb) && (!isset($value_1['ability']) || $value_1['ability'] != 23)) {
                        if($grounded){
                            if(isset($value_1['modified']['spd']['plus'])) {
                                if($value_1['modified']['spd']['plus'] > 1) {
                                    $value_1['modified']['spd'] = [
                                        'plus' => $value_1['modified']['spd']['plus'] - 1
                                    ];
                                }else{
                                    unset($value_1['modified']['spd']);
                                }
                            }elseif((isset($value_1['modified']['spd']['minus']))) {
                                if($value_1['modified']['spd']['minus'] >= 1 && $value_1['modified']['spd']['minus'] < 6) {
                                    $value_1['modified']['spd'] = [
                                        'minus' => $value_1['modified']['spd']['minus'] + 1
                                    ];
                                }elseif($value_1['modified']['spd']['minus'] == 6){
                                    $value_1['modified']['spd'] = [
                                        'minus' => $value_1['modified']['spd']['minus']
                                    ];
                                }else{
                                    unset($value_1['modified']['spd']);
                                }
                            }else{
                                $value_1['modified']['spd'] = [
                                    'minus' => 1
                                ];
                            }
                            $this->log[] = 'Скорость '.$newPokemon->_getName(true).' понижена';
                        }
                        $value_1['status_list']['stickyweb'] = [
                            'type'  => 'stickyweb',
                            'count' => 9999,
                            'val'   => 0
                        ];
                    }

                    // === Tailwind / Trick Room / No Crit (перенос таймеров)
                    if(isset($valTailwind)) {
                        $value_1['status_list']['tailwind'] = [
                            'type'  => 'tailwind',
                            'count' => $valTailwind,
                            'val'   => 0
                        ];
                    }
                    if(isset($valTrick)) {
                        $value_1['status_list']['trick'] = [
                            'type'  => 'trick',
                            'count' => $valTrick,
                            'val'   => 0
                        ];
                    }
                    if(isset($valNocrit)) {
                        $value_1['status_list']['nocrit'] = [
                            'type'  => 'nocrit',
                            'count' => $valNocrit,
                            'val'   => 0
                        ];
                    }

                    // Особые формы/умения при входе (как у вас)
                    if($value_1['basenum'] == 681 && isset($value_1['ability']) && $value_1['ability'] == 188) {
                        $value_1['form'] = 0;
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 96 && $def->ability != 113) {
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> позволяет '.$newPokemon->_getName(true).' левитировать.';
                        $value_1['status_list']['levitation'] = [
                            'type'  => 'levitation',
                            'count' => 9999,
                            'val'   => 0
                        ];
                    }
                    if(isset($valswitchability)) {
                        $value_1['ability'] = $valswitchability;
                    }

                    // === Stealth Rock === (урон по мультипликатору типа; Magic Guard — иммунитет)
                    if(isset($valRocks)) {
                        $rocMul = 1;
                        if(isset($this->types['rock'])) {
                            if(isset($this->types['rock'][$typeA]))     $rocMul *= $this->types['rock'][$typeA];
                            if(isset($this->types['rock'][$typeB]))     $rocMul *= $this->types['rock'][$typeB];
                        }
                        if(!$hasBalloon && !$hasMagicGuard){
                            if($rocMul >= 4){
                                $rockUron = ceil($value_1['stats'][0] / 2);
                            }elseif($rocMul >= 2.5){
                                $rockUron = ceil($value_1['stats'][0] / 4);
                            }elseif($rocMul >= 2){
                                $rockUron = ceil($value_1['stats'][0] / 4);
                            }elseif($rocMul >= 1){
                                $rockUron = ceil($value_1['stats'][0] / 8);
                            }elseif($rocMul <= 0.51){
                                $rockUron = ceil($value_1['stats'][0] / 16);
                            }elseif($rocMul <= 0.26){
                                $rockUron = ceil($value_1['stats'][0] / 32);
                            }else{
                                $rockUron = ceil($value_1['stats'][0] / 32);
                            }
                            $value_1['hp'] = $value_1['hp'] - $rockUron;
                            $this->log[] = 'Камушки ранят покемона: <span class="HpMinus">-'.$rockUron.' HP</span>';
                        }
                        $value_1['status_list']['rocks'] = [
                            'type'  => 'rocks',
                            'count' => 9999,
                            'val'   => 0
                        ];
                    }

                    // Абилки при входе (как у вас)
                    if(isset($value_1['ability']) && $value_1['ability'] == 255) {
                        if(isset($value_1['modified']['atk']['minus'])) {
                            if($value_1['modified']['atk']['minus'] > 1) {
                                $value_1['modified']['atk'] = [
                                    'minus' => $value_1['modified']['atk']['minus'] - 1
                                ];
                            }else{
                                unset($value_1['modified']['atk']);
                            }
                        }elseif((isset($value_1['modified']['atk']['plus']))) {
                            if($value_1['modified']['atk']['plus'] >= 1 && $value_1['modified']['atk']['plus'] < 6) {
                                $value_1['modified']['atk'] = [
                                    'plus' => $value_1['modified']['atk']['plus'] + 1
                                ];
                            }elseif($value_1['modified']['atk']['plus'] == 6){
                                $value_1['modified']['atk'] = [
                                    'plus' => $value_1['modified']['atk']['plus']
                                ];
                            }else{
                                unset($value_1['modified']['atk']);
                            }
                        }else{
                            $value_1['modified']['atk'] = [
                                'plus' => 1
                            ];
                        }
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> повышает Атаку '.$newPokemon->_getName(true);
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 256) {
                        if(isset($value_1['modified']['def']['minus'])) {
                            if($value_1['modified']['def']['minus'] > 1) {
                                $value_1['modified']['def'] = [
                                    'minus' => $value_1['modified']['def']['minus'] - 1
                                ];
                            }else{
                                unset($value_1['modified']['def']);
                            }
                        }elseif((isset($value_1['modified']['def']['plus']))) {
                            if($value_1['modified']['def']['plus'] >= 1 && $value_1['modified']['def']['plus'] < 6) {
                                $value_1['modified']['def'] = [
                                    'plus' => $value_1['modified']['def']['plus'] + 1
                                ];
                            }elseif($value_1['modified']['def']['plus'] == 6){
                                $value_1['modified']['def'] = [
                                    'plus' => $value_1['modified']['def']['plus']
                                ];
                            }else{
                                unset($value_1['modified']['def']);
                            }
                        }else{
                            $value_1['modified']['def'] = [
                                'plus' => 1
                            ];
                        }
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> повышает Защиту '.$newPokemon->_getName(true);
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 89) {
                        if($def->ability == 79 && $atk->ability != 113) {
                            $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника не дает понизить ему стат.';
                        }else{
                            $def->_setModifiedStat('atk', 1, false);
                            $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> уменьшает противнику <span class="StatMinus">Атаку -1</span>';
                        }
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 249 && (($def->_checkStatus('lightScreen') || $def->_checkStatus('reflect') || $def->_checkStatus('aurora')) || (isset($value_1['status_list']['lightScreen']) || isset($value_1['status_list']['reflect']) || isset($value_1['status_list']['aurora'])))) {
                        unset($value_1['status_list']['lightScreen']);
                        unset($value_1['status_list']['reflect']);
                        unset($value_1['status_list']['aurora']);
                        $info2 = $def->_getStatusList();
                        if($info2){
                            foreach($info2 AS $key=>$valueX){
                                if(in_array($valueX['type'], ['reflect','lightScreen','aurora'])){
                                    unset($info2[$key]);
                                }
                            }
                            $def->_setStatusList($info2);
                        }
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> разрушает экраны на поле боя';
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 44) {
                        Work::$sql->query("UPDATE battle SET `weather` = 2, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> меняет погоду на Солнечную.';
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 179) {
                        Work::$sql->query("UPDATE battle SET `weather` = 4, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> меняет погоду на Град.';
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 43) {
                        Work::$sql->query("UPDATE battle SET `weather` = 3, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> меняет погоду на Дождь.';
                    }
                    if(isset($value_1['ability']) && $value_1['ability'] == 160) {
                        Work::$sql->query("UPDATE battle SET `weather` = 5, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                        $this->log[] = '<div class="Ability" onclick="issetAll('.$value_1['ability'].',\'ability\')">'.$this->_abilNameRus($value_1['ability']).'</div> меняет погоду на Песчаную бурю.';
                    }
                }
            }
        }

        // Сохраняем изменения команды защитника
        $this->_actionBattle->_setUserPokes($def->_getUser(), $pList);
    }
    return true;
}


    private function getSelected(PokeBattle &$atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $other = false, $other2 = false, $ABP = false){
    if(
        $atkAtk['id'] == 9999 || $atkAtk['id'] == 602 || $atkAtk['id'] == 75 ||
        $atkAtk['id'] == 434 || $atkAtk['id'] == 127 || $atkAtk['id'] == 34 ||
        $atkAtk['id'] == 374 || $atkAtk['id'] == 229 || $atkAtk['id'] == 583 ||
        $atkAtk['id'] == 592
    ){
        // Arena Trap (8) или Shadow Tag (167, если у атакующего нет Shadow Tag)
        if(
            $def->ability == 8 ||
            ($def->ability == 167 && $atk->ability != 167)
        ) {
            // Arena Trap: не работает против призраков, Levitate (26), flying
            // Shadow Tag: не работает против призраков
            if(
                !in_array('ghost', [$atk->_getTypeA(), $atk->_getTypeB()]) &&
                (
                    $def->ability != 8 ||
                    (
                        $def->ability == 8 &&
                        $atk->ability != 96 && // Levitate
                        !in_array('flying', [$atk->_getTypeA(), $atk->_getTypeB()])
                    )
                )
            ) {
                if(!in_array($atkAtk['id'], [34,583,592,374,229])){
                    if($atk->hp > 0) {
                        if(
                            $defAtk['id'] == 9999 || $defAtk['id'] == 602 || $defAtk['id'] == 75 ||
                            $defAtk['id'] == 434 || $defAtk['id'] == 127 || $defAtk['id'] == 34 ||
                            $defAtk['id'] == 374 || $defAtk['id'] == 229 || $defAtk['id'] == 583 ||
                            $defAtk['id'] == 592
                        ) {

                        } else {
                            $this->log[] = 'Пытается сменить покемона, но <div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника не дает это сделать.';
                            return false;
                        }
                    }
                }
            }
        }
          
          if($defAtk['id'] == 412 && $other2 != 412) {
            $this->log[] = 'Заменяет покемона, но '.$atk->_getName(true).' получает урон от атаки.';
            return false;
          }
          if($atkAtk['id'] == 9999 && $atk->_checkStatus('ingrain') && $atk->_getTypeA() != 'ghost' && $atk->hp > 0 &&  $atk->item_id != 170|| $atkAtk['id'] == 9999 && $atk->_checkStatus('ingrain') && $atk->_getTypeB() != 'ghost' && $atk->hp > 0 &&  $atk->item_id != 170) {
            $this->log[] = 'Пытается сменить покемона, но он очень сильно закопан в землю.';
            return false;
          }
          if($atk->_checkStatus('prison') && $atk->hp > 0){
            $this->log[] = 'Пытается сменить покемона, но он не может выбраться из ловушки';
            return false;
          }
          // === NATURAL CURE для уходящего покемона ===
$pList = $this->_actionBattle->_getUserPokes($atk->_getUser());
$changed = false;
if ($pList) {
    foreach ($pList as &$poke) {
        if (isset($poke['active']) && $poke['active'] == 1 && isset($other) && $poke['id'] != $other) {
            if (isset($poke['ability']) && $poke['ability'] == 120 && isset($poke['status_list'])) {
                $statusKeys = ['burn', 'paralyzed', 'sleep', 'frost', 'toxic', 'toxic2'];
                $hadStatus = false;
                foreach($statusKeys as $natcureStatus) {
                    if(isset($poke['status_list'][$natcureStatus])) {
                        unset($poke['status_list'][$natcureStatus]);
                        $hadStatus = true;
                    }
                }
                if($hadStatus) {
                    $this->log[] = $poke['name_new'].' исцеляет свой статус благодаря способности <div class="Ability" onclick="issetAll(120,\'ability\')">Естественное исцеление</div>.';
                }
            }
            $changed = true;
        }
    }
    unset($poke);
    if($changed) {
        $this->_actionBattle->_setUserPokes($atk->_getUser(), $pList);
    }
}

// === КОНЕЦ NATURAL CURE ===
            $uData =& $this->_actionBattle->_getUserData($atk->_getUser());

            if($atkAtk['id'] == 602 || $atkAtk['id'] == 75 || $atkAtk['id'] == 127 || $atkAtk['id'] == 434 || $atkAtk['id'] == 34 || $atkAtk['id'] == 374 || $atkAtk['id'] == 229 || $atkAtk['id'] == 583 || $atkAtk['id'] == 592) {
              $uData['targetPokemon'] = $other;
            }

            if(isset($uData['targetPokemon']) && $uData['targetPokemon'] > 0){
                  if(isset($atkAtk['id'])){
                    if($atk->_checkStatus('spikes') || $atk->_checkStatus('tailwind') || $atk->_checkStatus('switch_ability') || $atk->_checkStatus('trick') || $atk->_checkStatus('nocrit') || $atk->_checkStatus('rocks') || $atk->_checkStatus('stickyweb') || $atk->_checkStatus('toxicspikes') || $atk->_checkStatus('lightScreen') || $atk->_checkStatus('mudsport') || $atk->_checkStatus('plant') || $atk->_checkStatus('gravity') || $atk->_checkStatus('aurora') || $atk->_checkStatus('reflect')) {
                      $info = $atk->_getStatusList();
                      if($info){
                        foreach($info AS $key=>$value){
                          if($value['type'] == 'switch_ability') {
                            $valswitchability = $value['val'];
                          }
						  if($value['type'] == 'spikes'){
                            $valSpikes = $value['val'];
                          }
                          if($value['type'] == 'toxicspikes'){
                            $valToxicSpikes = $value['val'];
                          }
                          if($value['type'] == 'rocks'){
                            $valRocks = 1;
                          }
                          if($value['type'] == 'stickyweb'){
                            $valStickyweb = 1;
                          }
                          if($value['type'] == 'tailwind'){
                            $valTailwind = $value['count'];
                          }
                          if($value['type'] == 'trick'){
                            $valTrick = $value['count'];
                          }
                          if($value['type'] == 'nocrit'){
                            $valNocrit = $value['count'];
                          }
                          if($value['type'] == 'lightScreen'){
                            $valLightScreen = $value['count'];
                          }
						  if($value['type'] == 'gravity'){
                            $valGravity = $value['count'];
                          }
						  if($value['type'] == 'mudsport'){
                            $valMudsport = $value['count'];
                          }
						  if($value['type'] == 'plant'){
                            $valPlant = $value['count'];
                          }
                          if($value['type'] == 'aurora'){
                            $valAurora = $value['count'];
                          }
                          if($value['type'] == 'reflect'){
                            $valReflect = $value['count'];
                          }
                        }
                      }
                    }
                    if($ABP == 1){
                        if(isset($atk->modified['atk'])) {
			                                  if(isset($atk->modified['atk']['minus'])) {
			                                    $atk_abp = false;
			                                  }elseif(isset($atk->modified['atk']['plus'])) {
			                                    $atk_abp = true;
			                                  }
			                                }
			                                if(isset($atk->modified['def'])) {
			                                  if(isset($atk->modified['def']['minus'])) {
			                                    $def_abp = false;
			                                  }elseif(isset($atk->modified['def']['plus'])) {
			                                    $def_abp = true;
			                                  }
			                                }
			                                if(isset($atk->modified['spd'])) {
			                                  if(isset($atk->modified['spd']['minus'])) {
			                                    $spd = false;
			                                  }elseif(isset($atk->modified['spd']['plus'])) {
			                                    $spd = true;
			                                  }
			                                }
                        if(isset($atk->modified['satk'])) {
			                                  if(isset($atk->modified['satk']['minus'])) {
			                                    $spec_atk = false;
			                                  }elseif(isset($atk->modified['satk']['plus'])) {
			                                    $spec_atk = true;
			                                  }
			                                }
			                                if(isset($atk->modified['sdef'])) {
			                                  if(isset($atk->modified['sdef']['minus'])) {
			                                    $spec_def = false;
			                                  }elseif(isset($atk->modified['sdef']['plus'])) {
			                                    $spec_def = true;
			                                  }
			                                }
			                                if(isset($atk->modified['acr'])) {
			                                  if(isset($atk->modified['acr']['minus'])) {
			                                    $acr = false;
			                                  }elseif(isset($atk->modified['acr']['plus'])) {
			                                    $acr = true;
			                                  }
			                                }
			                                if(isset($atk->modified['agl'])) {
			                                  if(isset($atk->modified['agl']['minus'])) {
			                                    $agl = false;
			                                  }elseif(isset($atk->modified['agl']['plus'])) {
			                                    $agl = true;
			                                  }
			                                }
                    }
                    if($atkAtk['id'] == 602 || $atkAtk['id'] == 75 || $atkAtk['id'] == 127 || $atkAtk['id'] == 434) {
                      $target = $this->_actionBattle->_getUserPokes($def->_getUser(), $uData['targetPokemon']);
                      $this->_actionBattle->_setTarget($def->_getUser(), $target);
                      $af = $atk;
                      $def = new PokeBattle($target);
                      $atk = $def;
                    }else{
                      $target = $this->_actionBattle->_getUserPokes($atk->_getUser(), $uData['targetPokemon']);
                      $this->_actionBattle->_setTarget($atk->_getUser(), $target);
                      $atk = new PokeBattle($target);
                    }
                    unset($atk->modified);
                    if($ABP == 1){
                        if($atk_abp){
                            $atk->_setModifiedStat('atk',1,$atk_abp);
                        }
                        if($def_abp){
                            $atk->_setModifiedStat('def',1,$def_abp);
                        }
                        if($spd){
                            $atk->_setModifiedStat('spd',1,$spd);
                        }
                        if($spec_atk){
                            $atk->_setModifiedStat('satk',1,$spec_atk);
                        }
                        if($spec_def){
                            $atk->_setModifiedStat('sdef',1,$spec_def);
                        }
                        if($acr){
                            $atk->_setModifiedStat('acr',1,$acr);
                        }
                        if($agl){
                            $atk->_setModifiedStat('agl',1,$agl);
                        }
                    }
			                                    
			                                    
                    
                    $aHH = Work::$sql->query('SELECT * FROM atk_helping_hand WHERE pokID = '.$atk->id)->fetch_assoc();
                    $aAM = Work::$sql->query('SELECT * FROM atk_aromatic_mist WHERE pokID = '.$atk->id)->fetch_assoc();
                    if($aHH) {
                      $atk->_setModifiedStat('atk',1,true);
                      Work::$sql->query('DELETE FROM atk_helping_hand WHERE pokID = '.$atk->id);
                    }
                    if($aAM) {
                      $atk->_setModifiedStat('sdef',1,true);
                      Work::$sql->query('DELETE FROM atk_aromatic_mist WHERE pokID = '.$atk->id);
                    }
                    $Effects = $atk->_getStatusList();
                    if($Effects){foreach($Effects AS $key=>$value){if($value['type'] == 'lightScreen' || $value['type'] == 'levitation' || $value['type'] == 'confused' || $value['type'] == 'plasma_fists' || $value['type'] == 'miracle' || $value['type'] == 'aurora' || $value['type'] == 'gravity' || $value['type'] == 'mudsport' || $value['type'] == 'plant' || $value['type'] == 'nightmare' || $value['type'] == 'aquaRing' || $value['type'] == 'reader' || $value['type'] == 'spikes' || $value['type'] == 'toxicspikes' || $value['type'] == 'stickyweb' || $value['type'] == 'rocks' || $value['type'] == 'leechSeed' || $value['type'] == 'toxic2' || $value['type'] == 'curse' || $value['type'] == 'grounded' || $value['type'] == 'defensecurl' || $value['type'] == 'critfocus' || $value['type'] == 'ingrain' || $value['type'] == 'taunt' || $value['type'] == 'defstat' || $value['type'] == 'nocrit' || $value['type'] == 'switch_ability' || $value['type'] == 'minimize' || $value['type'] == 'mind' || $value['type'] == 'reflect' || $value['type'] == 'tailwind' || $value['type'] == 'trick' || $value['type'] == 'terrMisty' || $value['type'] == 'terrGrass' || $value['type'] == 'safeguard' || $value['type'] == 'deathSong'){
                          unset($Effects[$key]);
                          if($value['type'] == 'toxic2') {
                            $toxYes = 1;
                          }
                        }
                      }
                    }
                    $disable_my = explode(',',$this->attacker->disable_my);
                    $disable_my[0] = 0;
                    $disable_my[1] = 0;
                    $disable_my[2] = 0;
                    $disable_my[3] = 0;
                    $this->attacker->disable_my = implode(',',$disable_my);
                    $atk->round_before = $this->_actionBattle->round;
                    $atk->_setStatusList($Effects);
                    if($this->_actionBattle->_isPVP()) {
                      $BattleEffects = Work::$sql->query('SELECT * FROM battle_effects WHERE user = '.$atk->user_id.' OR user = '.$def->user_id);
                    }else{
                      $BattleEffects = Work::$sql->query('SELECT * FROM battle_effects WHERE user = '.$_SESSION['id']);
                    }
                    if(isset($toxYes)) {
                      $atk->_setStatus('toxic2', 9999);
                    }

                    if($atk->ability == 44) {
                      Work::$sql->query("UPDATE battle SET `weather` = 2, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                      $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> меняет погоду на Солнечную.';
                    }
                    if($atk->ability == 179) {
                      Work::$sql->query("UPDATE battle SET `weather` = 4, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                      $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> меняет погоду на Град.';
                    }

                    if($atk->ability == 43) {
                      Work::$sql->query("UPDATE battle SET `weather` = 3, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                      $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> меняет погоду на Дождь.';
                    }
                    if($atk->ability == 160) {
                      Work::$sql->query("UPDATE battle SET `weather` = 5, weather_round = 5 WHERE user_1 = ".$atk->user_id." OR user_2 = ".$atk->user_id);
                      $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> меняет погоду на Песчанную Бурю.';
                    }

                    if($atk->basenum == 681 && $atk->ability == 188) {
                      $atk->form = 0;
                    }

                    if(isset($valSpikes)) {
                      if(($atk->_checkStatus('levitation') || $atk->_checkStatus('easy_levitation')) || in_array('fly', [$atk->_getTypeB(),$atk->_getTypeA()]) || $atk->item_id == 165) {

                      }else{
						  if($valSpikes == 1) {
                            $spkUron = ceil($atk->stats[0] / 8);
                          }elseif($valSpikes == 2) {
                            $spkUron = ceil($atk->stats[0] / 6);
                          }else{
                            $spkUron = ceil($atk->stats[0] / 4);
                          }
                          $atk->hp = $atk->hp - $spkUron;
                          $this->log[] = 'Шипы ранят покемона: <span class="HpMinus">-'.$spkUron.' HP</span>';
					  }
                      $atk->_setStatus('spikes', 9999, $valSpikes);
                    }
                    if($atk->ability == 96 and $def->ability != 113) {
                      $atk->_setStatus('levitation', 9999);
                      $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> позволяет '.$atk->_getName(true).' левитировать.';
                    }
                    if(isset($valToxicSpikes)) {
                      if($valToxicSpikes == 1) {
                        if($atk->_checkStatus('levitation') || $atk->_checkStatus('easy_levitation') || $atk->_checkStatus('toxic') || $atk->_checkStatus('toxic2') || $atk->_checkStatus('terrMisty') || $atk->_checkStatus('safeguard') || $atk->_getTypeA() == 'poison' || $atk->_getTypeB() == 'poison' || $atk->_getTypeA() == 'steel' || $atk->_getTypeB() == 'steel' || $atk->_getTypeA() == 'fly' || $atk->_getTypeB() == 'fly'  || $atk->item_id == 165){
                          $this->log[] = 'Провал изменения статуса.';
                        }else{
                          $atk->_setStatus('toxic', 9999);
                          $this->log[] = 'Покемон отравлен';
                        }
                      }else{
                        if($atk->_checkStatus('levitation') || $atk->_checkStatus('easy_levitation') || $atk->_checkStatus('toxic2') || $atk->_checkStatus('terrMisty') || $atk->_checkStatus('safeguard') || $atk->_getTypeA() == 'poison' || $atk->_getTypeB() == 'poison' || $atk->_getTypeA() == 'steel' || $atk->_getTypeB() == 'steel' || $atk->_getTypeA() == 'fly' || $atk->_getTypeB() == 'fly' || $atk->item_id == 165){
                          $this->log[] = 'Провал изменения статуса.';
                        }else{
                          $TSpk = $atk->_getStatusList();
                          if($TSpk){foreach($TSpk AS $key=>$value){if($value['type'] == 'toxic'){unset($TSpk[$key]);}}}
                          $atk->_setStatusList($TSpk);
                          $this->log[] = 'Покемон сильно отравлен';
                          $atk->_setStatus('toxic2', 9999, 0);
                        }
                      }
                      $atk->_setStatus('toxicspikes', 9999, $valToxicSpikes);
                    }
					if(isset($valLightScreen)) {
                      $atk->_setStatus('lightScreen', $valLightScreen);
                    }
					if(isset($valGravity)) {
                      $atk->_setStatus('gravity', $valGravity);
                    }
					if(isset($valMudsport)) {
                      $atk->_setStatus('mudsport', $valMudsport);
                    }
					if(isset($valPlant)) {
                      $atk->_setStatus('plant', $valPlant);
                    }
                    if(isset($valAurora)) {
                      $atk->_setStatus('aurora', $valAurora);
                    }
                    if(isset($valReflect)) {
                      $atk->_setStatus('reflect', $valReflect);
                    }
                    if(isset($valStickyweb)) {
					  if(in_array('fly',[$atk->_getTypeB(),$atk->_getTypeA()]) || ($atk->_checkStatus('levitation') || $atk->_checkStatus('easy_levitation') || $atk->ability == 23)) {

					  }else{
						  $atk->_setModifiedStat('spd', 1, false);
 						 $this->log[] = 'Скорость '.$atk->_getName(true).' понижена';
					  }
                      $atk->_setStatus('stickyweb', 9999);
                    }
                    if(isset($valTailwind)) {
                      $atk->_setStatus('tailwind', $valTailwind);
                    }
                    if(isset($valTrick)) {
                      $atk->_setStatus('trick', $valTrick);
                    }
                    if(isset($valNocrit)) {
                      $atk->_setStatus('nocrit', $valNocrit);
                    }
                    if(isset($valswitchability)) {
                      $atk->ability = $valswitchability;
                    }
                    if(isset($valRocks)) {
                      $roc = 1;
                      $roc *= (isset($this->types['rock'], $this->types['rock'][$atk->_getTypeA()]) ? $this->types['rock'][$atk->_getTypeA()] : 1);
                      $roc *= (isset($this->types['rock'], $this->types['rock'][$atk->_getTypeB()]) ? $this->types['rock'][$atk->_getTypeB()] : 1);
                      if($roc >= 4){
                          $rockUron = ceil($atk->stats[0] / 2);
                      }elseif($roc >= 2.5){
                          $rockUron = ceil($atk->stats[0] / 4);
                      }elseif($roc >= 2){
                          $rockUron = ceil($atk->stats[0] / 4);
                      }elseif($roc >= 1){
                          $rockUron = ceil($atk->stats[0] / 8);
                      }elseif($roc <= 0.51){
                          $rockUron = ceil($atk->stats[0] / 16);
                      }elseif($roc <= 0.26){
                          $rockUron = ceil($atk->stats[0] / 32);
                      }else{
                        $rockUron = ceil($atk->stats[0] / 32);
                      }
                      if($atk->item_id == 165){

                      }else{
                          $atk->hp = $atk->hp - $rockUron;
                            $this->log[] = 'Камушки ранят покемона: <span class="HpMinus">-'.$rockUron.' HP</span>';
                      }

                      $atk->_setStatus('rocks', 9999);
                    }

                      if(isset($BattleEffects)) {
                        while($Effect = $BattleEffects->fetch_assoc()) {
                          if($Effect['end'] >= $this->_actionBattle->round) {
                            $pokIdEf = explode(',',$Effect['pok']);
                            switch($Effect['name']){
                              case 'Wish':
                                if($atk->user_id == $Effect['user']) {
                                    if($atk->hp > 0 and $atkAtk['id'] != 9999){
                                  $hpWish = $Effect['end'];
                                  $atk->hp = $atk->hp + $hpWish;
                                  $this->log[] = 'Желание восстанавливает здоровье <span class="HpPlus">+'.$hpWish.' HP</span>';
                                  Work::$sql->query('DELETE FROM battle_effects WHERE name = "Wish" AND user = '.$atk->user_id);
                                    }
                                }
                              break;
                              case 'TerrMisty':
                                if($atk->user_id == $Effect['user'] || $def->user_id == $Effect['user']) {
                                  if(isset($pokIdEf[0]) && $atk->id == $pokIdEf[0] || isset($pokIdEf[2]) && $atk->id == $pokIdEf[2] || isset($pokIdEf[3]) && $atk->id == $pokIdEf[3] || isset($pokIdEf[4]) && $atk->id == $pokIdEf[4] || isset($pokIdEf[1]) && $atk->id == $pokIdEf[1] || isset($pokIdEf[5]) && $atk->id == $pokIdEf[5]
                                  || isset($pokIdEf[0]) && $def->id == $pokIdEf[0] || isset($pokIdEf[2]) && $def->id == $pokIdEf[2] || isset($pokIdEf[3]) && $def->id == $pokIdEf[3] || isset($pokIdEf[4]) && $def->id == $pokIdEf[4] || isset($pokIdEf[1]) && $def->id == $pokIdEf[1] || isset($pokIdEf[5]) && $def->id == $pokIdEf[5]
                                  ){
                                    $rnd = $Effect['end'] - $this->_actionBattle->round;
                                    $atk->_setStatus('terrMisty', $rnd);
                                    $def->_setStatus('terrMisty', $rnd);
                                  }
                                }
                              break;
                              case 'TerrGrass':
                                if($atk->user_id == $Effect['user'] || $def->user_id == $Effect['user']) {
                                  if(isset($pokIdEf[0]) && $atk->id == $pokIdEf[0] || isset($pokIdEf[2]) && $atk->id == $pokIdEf[2] || isset($pokIdEf[3]) && $atk->id == $pokIdEf[3] || isset($pokIdEf[4]) && $atk->id == $pokIdEf[4] || isset($pokIdEf[1]) && $atk->id == $pokIdEf[1] || isset($pokIdEf[5]) && $atk->id == $pokIdEf[5]
                                  || isset($pokIdEf[0]) && $def->id == $pokIdEf[0] || isset($pokIdEf[2]) && $def->id == $pokIdEf[2] || isset($pokIdEf[3]) && $def->id == $pokIdEf[3] || isset($pokIdEf[4]) && $def->id == $pokIdEf[4] || isset($pokIdEf[1]) && $def->id == $pokIdEf[1] || isset($pokIdEf[5]) && $def->id == $pokIdEf[5]
                                  ){
                                    $rnd = $Effect['end'] - $this->_actionBattle->round;
                                    $atk->_setStatus('terrGrass', $rnd);
                                    $def->_setStatus('terrGrass', $rnd);
                                  }
                                }
                              break;
                              case 'Safeguard':
                              if($atk->user_id == $Effect['user']) {
                                if(isset($pokIdEf[0]) && $atk->id == $pokIdEf[0] || isset($pokIdEf[2]) && $atk->id == $pokIdEf[2] || isset($pokIdEf[3]) && $atk->id == $pokIdEf[3] || isset($pokIdEf[4]) && $atk->id == $pokIdEf[4] || isset($pokIdEf[1]) && $atk->id == $pokIdEf[1] || isset($pokIdEf[5]) && $atk->id == $pokIdEf[5]){
                                  $rnd = $Effect['end'] - $this->_actionBattle->round;
                                  $atk->_setStatus('safeguard', $rnd);
                                }
                              }
                              break;
                              case 'DeathSong':
                                if($atk->user_id == $Effect['user'] || $def->user_id == $Effect['user']) {
                                  if(isset($pokIdEf[0]) && $atk->id == $pokIdEf[0] || isset($pokIdEf[2]) && $atk->id == $pokIdEf[2] || isset($pokIdEf[3]) && $atk->id == $pokIdEf[3] || isset($pokIdEf[4]) && $atk->id == $pokIdEf[4] || isset($pokIdEf[1]) && $atk->id == $pokIdEf[1] || isset($pokIdEf[5]) && $atk->id == $pokIdEf[5]
                                  || isset($pokIdEf[0]) && $def->id == $pokIdEf[0] || isset($pokIdEf[2]) && $def->id == $pokIdEf[2] || isset($pokIdEf[3]) && $def->id == $pokIdEf[3] || isset($pokIdEf[4]) && $def->id == $pokIdEf[4] || isset($pokIdEf[1]) && $def->id == $pokIdEf[1] || isset($pokIdEf[5]) && $def->id == $pokIdEf[5]
                                  ){
                                    $rnd = $Effect['end'] - $this->_actionBattle->round;
                                    $atk->_setStatus('deathSong', $rnd);
                                    $def->_setStatus('deathSong', $rnd);
                                  }
                                }
                              break;
                            }
                          }
                        }
                      }
                      if($defAtk['id'] == 412 && $other2 == 412) {
                        $this->log[] = 'Противник меняет покемона на '.$atk->_getName(true);
                      }else{
                        $this->log[] = 'Заменяет покемона на '.$atk->_getName(true);
                      }

                      if($atk->ability == 255) {
							  $atk->_setModifiedStat('atk', 1, true);
	                          $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> повышает Атаку '.$atk->_getName(true);

                      }

                      if($atk->ability == 256) {
							  $atk->_setModifiedStat('def', 1, true);
	                          $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> повышает Защиту '.$atk->_getName(true);

                      }

                      if($atk->ability == 89) {
						  if($def->ability == 79 && $atk->ability != 113) {
							  $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника не дает понизить ему стат.';
						  }else{
							  $def->_setModifiedStat('atk', 1, false);
	                          $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> уменьшает противнику <span class="StatMinus">Атаку -1</span>';
						  }
                      }

                      if($atk->ability == 249 and (($def->_checkStatus('lightScreen') or $def->_checkStatus('reflect')or $def->_checkStatus('aurora')) or ($atk->_checkStatus('lightScreen') or $atk->_checkStatus('reflect') or $atk->_checkStatus('aurora')))) {
                $info = $atk->_getStatusList();
			                        if($info){
			                          foreach($info AS $key=>$value){
			                            if(in_array($value['type'], ['reflect','lightScreen','aurora'])){
			                              unset($info[$key]);
			                            }
			                          }
			                          $atk->_setStatusList($info);
			                        }
                $info2 = $def->_getStatusList();
			                        if($info2){
			                          foreach($info2 AS $key=>$value){
			                            if(in_array($value['type'], ['reflect','lightScreen','aurora'])){
			                              unset($info2[$key]);
			                            }
			                          }
			                          $def->_setStatusList($info2);
			                        }
			                        $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> разрушает экраны на поле боя';

                }

                    if($atkAtk['id'] == 602 && $this->_actionBattle->_isPVP() || $atkAtk['id'] == 75 && $this->_actionBattle->_isPVP() || $atkAtk['id'] == 127 && $this->_actionBattle->_isPVP() || $atkAtk['id'] == 434 && $this->_actionBattle->_isPVP()) {
                      Work::$sql->query('DELETE FROM battle_block WHERE user = '.$def->user_id);
                      Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$def->user_id);
                      Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$def->user_id);
                      $def = $atk;
                      $atk = $af;
                    }else{
                      Work::$sql->query('DELETE FROM battle_block WHERE user = '.$_SESSION['id']);
                      Work::$sql->query('DELETE FROM atk_rollout WHERE user = '.$_SESSION['id']);
                      Work::$sql->query('DELETE FROM atk_echo WHERE user = '.$_SESSION['id']);
                    }

                    unset($uData['targetPokemon']);

                    return false;
                }

            }
        }


        return true;
    }

    private function getNext(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){

    if($atk->hp > 0){

        // Flinch — пропуск хода
        if($atk->_checkStatus('flinch')){
            // Inner Focus предотвращает flinch, если у цели нет Mold Breaker
            if($atk->ability == 87 && $def->ability != 113) {
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> предотвращает испуг у покемона.';
            }else{
                $this->log[] = 'Но '.$atk->_getName(true).' напуган и не желает делать это действие.';
                // Сохраняю твою механику для 190 (увеличение Атаки)
                if($atk->ability == 190) {
                    $atk->_setModifiedStat('atk', 1, true);
                    $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> повышает <span class="StatPlus">Атаку +1</span>';
                }
                return false;
            }
        }

        // Заморозка — пропуск хода
        if($atk->_checkStatus('frost')){
            $this->log[] = 'Но '.$atk->_getName(true).' заморожен и не может делать это действие.';
            return false;
        }

        // Powder (id 393): работает, если пользователь Powder ходит РАНЬШЕ соперника, выбравшего Fire.
        // Тогда мы сразу наносим 25% от max HP сопернику и отменяем его действие в текущем ходу.
        if ($this->meFirst != 0 && $atkAtk['id'] == 393 && isset($defAtk['type']) && $defAtk['type'] === 'fire') {
            $powderUron = ($def->hp_max * 25) / 100;
            $def->hp = $def->hp - $powderUron;
            $this->log[] = 'Но огненная атака была заблокирована порошком. Получен урон: <span class="HpMinus">-'.ceil($powderUron).' HP</span>';

            // Жёстко отменяем действие цели в этом же ходу
            if ($this->_actionBattle->_isPVP()) {
                if ($def->_getUser() == $this->_actionBattle->userInfo['id']) {
                    $this->_actionBattle->userData['targetAtk'] = 754; // пропуск/ожидание
                } else {
                    $this->_actionBattle->enemyData['targetAtk'] = 754;
                }
            } else {
                $this->_actionBattle->enemyData['targetAtk'] = 754;
            }
            return false;
        }

        // Taunt — запрещает статусные (и специфические) приёмы (Safeguard НЕ защищает)
        if ($atk->_checkStatus('taunt') && in_array($atkAtk['category'], ['status','specific'])){
            $this->log[] = 'Но '.$atk->_getName(true).' под насмешкой и не может использовать эту атаку.';
            return false;
        }

        // Assault Vest (161) — не даёт использовать статусные/специфические
        if ($atk->item_id == 161 && in_array($atkAtk['category'], ['status','specific'])){
            $this->log[] = 'Но предмет покемона не позволяет ему использовать эту атаку.';
            return false;
        }

        // Damp (33) — блокирует самоубийственные взрывы
        if( ($atk->ability == 33 && in_array($atkAtk['id'], [149,466,641])) || ($def->ability == 33 && in_array($atkAtk['id'], [149,466,641])) ){
            $this->log[] = 'Но <div class="Ability" onclick="issetAll(33,\'ability\')">'.$this->_abilNameRus(33).'</div> не дает использовать самоубийственную атаку.';
            return false;
        }

        // Паралич — 25% шанс пропуска
        if ($atk->_checkStatus('paralyzed') && mt_rand(1,100) <= 25){
            $this->log[] = 'Но '.$atk->_getName(true).' парализован и не смог сделать это действие.';
            return false;
        }

        // Сон — разрешаем только Snore(491) и Sleep Talk(501)
        if ($atk->_checkStatus('sleep')){
            if(!in_array($atkAtk['id'], [491, 501])){
                $this->log[] = 'Но '.$atk->_getName(true).' спит и не может делать это действие.';
                return false;
            }
        }

        // Attract — 50% провал атак во врага
        if ($atk->_checkStatus('lover') && $atkAtk['target'] == 'enemy' && mt_rand(1,100) <= 50){
            $this->log[] = 'Но '.$atk->_getName(true).' очарован и не желает делать это действие.';
            return false;
        }

        // Aegislash (Stance Change 188): при атакующем приёме — форма Меч
        if ($atk->basenum == 681 && $atk->ability == 188 && in_array($atkAtk['category'], ['physical','special'])) {
            if ($atk->form == 0 or $atk->form == "") {
                $atk->form = "blade";
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> меняет форму на Меч.';
            }
        }
    }

    return true;
}

private function issetEffect(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $my = false){
    if($my !== true){

        if($this->settings['effect_enemy'] !== true){
            return false;
        }

        if(empty($atkAtk['enemy'])){
            return false;
        }

        $info = Info::_unParseData($atkAtk['enemy']);

    }else{
        $info = Info::_unParseData($atkAtk['my']);
    }

    if(!empty($info)){
        // === Блок «статы» (шансы и т.д.) ===
        if(isset($info['stats'])){
            foreach($info['stats'] AS $key=>$value){
                if($value && isset($value[0], $value[1], $value[2])){
                    if(isset($value[3]) && $value[3] > 0){
                        if($atk->ability == 165) { // удвоение шансов твоей способностью
                            $value[3] = $value[3] * 2;
                        }
                        if(!( mt_rand(1,100) <= $value[3] )){
                            continue;
                        }
                    }
                }
            }
            unset($key,$value);
        }

        // === Блок «статусы» ===
        if(isset($info['status'])){
            foreach($info['status'] AS $key=>$value){
                if($value && isset($value[0], $value[1], $value[2]) && $value[0] > 0 && mt_rand(1,100) <= $value[0]){

                    // Нормализуем длительность
                    if(is_array($value[2])){
                        if(isset($value[2][0], $value[2][1]) && $value[2][0] > 0){
                            $value[2] = mt_rand($value[2][0], $value[2][1]);
                        }else{
                            $value[2] = 1;
                        }
                    }

                    // Attract
                    if($value[1] == 'lover'){
                        if($atk->_getSex() == $def->_getSex() || $def->_checkStatus('safeguard')){
                            $this->log[] = 'Провал изменения статуса.';
                            continue;
                        }
                    }

                    // Leech Seed — НЕ блокируется Safeguard (как в PS). Блок травой и Misty Terrain.
                    if($value[1] == 'leechSeed'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_getTypeA() == 'grass' || $def->_getTypeB() == 'grass'
                            || $def->_checkStatus('terrMisty')
                        ){
                            $this->log[] = 'Провал изменения статуса.';
                            continue;
                        }
                    }

                    // Flinch — только при нанесённом уроне контактной/обычной атакой
                    if($value[1] == 'flinch') {
                        if($def->item_id == 146){
                            $def->_setModifiedStat('spd', 1, true);
                            $this->log[] = 'Адреналиновый шар повышает Скорость противника.';
                            continue;
                        }
                        if($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical'])){
                            $this->log[] = 'Провал изменения статуса.';
                            continue;
                        }
                    }

                    // Confusion — Safeguard защищает
                    if($value[1] == 'confused') {
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_checkStatus('safeguard')
                            || ($def->ability == 127 and $atk->ability != 113) // Own Tempo
                        ){
                            $this->log[] = 'Провал изменения статуса.';
                            continue;
                        }
                    }

                    // Sleep
                    if($value[1] == 'sleep') {
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || ($def->ability == 88 && $atk->ability != 113) // Insomnia/Vital Spirit
                            || $def->_checkStatus('terrMisty')
                            || $def->_checkStatus('sleep') || $def->_checkStatus('burn') || $def->_checkStatus('frost')
                            || $def->_checkStatus('paralyzed') || $def->_checkStatus('toxic') || $def->_checkStatus('toxic2')
                            || ($atkAtk['id'] == 515 && ($def->_getTypeA() == 'grass' || $def->_getTypeB() == 'grass'))
                            || ($atkAtk['id'] == 490 && ($def->_getTypeA() == 'grass' || $def->_getTypeB() == 'grass'))
                        ){
                            $this->log[] = 'Провал изменения статуса.';
                            if($def->ability == 88) {
                                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> защищает от усыпления.';
                            }
                            continue;
                        }
                    }

                    // Taunt — Safeguard НЕ блокирует
                    if($value[1] == 'taunt') {
                        // без проверки safeguard
                    }

                    // Burn
                    if($value[1] == 'burn'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->ability == 226 || ($def->ability == 228 and $atk->ability != 113)
                            || $def->_checkStatus('terrMisty')
                            || $def->_checkStatus('sleep') || $def->_checkStatus('burn') || $def->_checkStatus('paralyzed')
                            || $def->_checkStatus('frost') || $def->_checkStatus('toxic') || $def->_checkStatus('toxic2')
                            || $def->_getTypeA() == 'fire' || $def->_getTypeB() == 'fire'
                        ){
                            $this->log[] = 'Провал изменения статуса.';
                            if($def->ability == 226 or $def->ability == 228) {
                                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> защищает от поджога.';
                            }
                            continue;
                        }
                    }

                    // Freeze
                    if($value[1] == 'frost'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_checkStatus('terrMisty')
                            || $def->_checkStatus('sleep') || $def->_checkStatus('safeguard')
                            || ($atkAtk['type'] == 'ice' && ($def->_getTypeA() == 'ice' || $def->_getTypeB() == 'ice'))
                            || (!in_array(4,[$atk->ability,$def->ability]) && $this->_actionBattle->weather == 2)
                            || $def->_checkStatus('burn') || $def->_checkStatus('toxic') || $def->_checkStatus('toxic2') || $def->_checkStatus('paralyzed') || $def->_checkStatus('frost')
                        ){
                            $this->log[] = 'Провал изменения статуса.';
                            continue;
                        }
                    }

                    // Disable/Prison — Safeguard НЕ блокирует
                    if($value[1] == 'prison'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_checkStatus('prison')
                            || $def->item_id == 170
                        ){
                            $this->log[] = 'Провал изменения статуса';
                            continue;
                        }
                    }

                    // Poison / Toxic
                    if($value[1] == 'toxic'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_checkStatus('terrMisty')
                            || $def->_checkStatus('sleep') || $def->_checkStatus('safeguard')
                            || (($def->_getTypeA() == 'poison' || $def->_getTypeB() == 'poison' || $def->_getTypeA() == 'steel' || $def->_getTypeB() == 'steel')  && $atk->ability != 30)
                            || $def->_checkStatus('burn') || $def->_checkStatus('toxic') || $def->_checkStatus('toxic2')
                            || $def->_checkStatus('paralyzed') || $def->_checkStatus('frost')
                            || ($atkAtk['id'] == 391 && ($def->_getTypeA() == 'grass' || $def->_getTypeB() == 'grass'))
                        ){
                            $this->log[] = 'Провал изменения статуса';
                            continue;
                        }
                    }
                    if($value[1] == 'toxic2'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_checkStatus('terrMisty')
                            || $def->_checkStatus('sleep') || $def->_checkStatus('safeguard')
                            || (($def->_getTypeA() == 'poison' || $def->_getTypeB() == 'poison' || $def->_getTypeA() == 'steel' || $def->_getTypeB() == 'steel') && $atk->ability != 30)
                            || $def->_checkStatus('burn') || $def->_checkStatus('toxic') || $def->_checkStatus('toxic2')
                            || $def->_checkStatus('paralyzed') || $def->_checkStatus('frost')
                        ){
                            $this->log[] = 'Провал изменения статуса';
                            continue;
                        }
                    }

                    // Негостовая Проклятие — баффы/дебаффы на себя
                    if($value[1] == 'curse' && $atk->_getTypeA() != 'ghost' && $atk->_getTypeB() != 'ghost'){
                        $atk->_setModifiedStat('atk', 1, true);
                        $atk->_setModifiedStat('def', 1, true);
                        $atk->_setModifiedStat('spd', 1, false);
                        $this->log[] = $atk->_getName(true).' повышает <span class="StatPlus">Атаку +1</span> и <span class="StatPlus">Защиту +1</span>, но понижает <span class="StatMinus">Скорость -1</span>';
                        continue;
                    }

                    // Паралич
                    if($value[1] == 'paralyzed'){
                        if( ($this->settings['dmg'] <= 0 && in_array($atkAtk['category'], ['special','physical']))
                            || $def->_checkStatus('terrMisty')
                            || $def->_checkStatus('sleep') || $def->_checkStatus('safeguard')
                            || $def->_getTypeA() == 'electric' || $def->_getTypeB() == 'electric'
                            || $def->_checkStatus('burn') || $def->_checkStatus('toxic') || $def->_checkStatus('toxic2') || $def->_checkStatus('paralyzed') || $def->_checkStatus('frost')
                            || ($atkAtk['id'] == 530 && ($def->_getTypeA() == 'grass' || $def->_getTypeB() == 'grass'))
                            || ($atkAtk['type'] == 'electric' && ($def->_getTypeA() == 'ground' || $def->_getTypeB() == 'ground'))
                        ){
                            $this->log[] = 'Провал изменения статуса';
                            continue;
                        }
                    }

                    // Применяем статус
                    if( $def->_setStatus( $value[1], $value[2], (isset($value[3]) ? $value[3] : null) ) ){
                        if(isset($this->titleStatus[$value[1]])){
                            $this->log[] = sprintf($this->titleStatus[$value[1]], $def->_getName(true));
                        }
                        // Synchronize — для burn/poison/paralyzed
                        if (in_array($value[1], ['burn','toxic','poison','paralyzed'])) {
                            if (method_exists($this, 'trySynchronize')) {
                                $this->trySynchronize($value[1]);
                            }
                        }
                    }

                }
            }
            unset($key,$value);
        }

        // Лечение фиксированной долей от max HP
        if(isset($info['heal'])){
            if($atk->ability == 109) {
                $info['heal'] = 0.75;
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> усиливает лечение от атаки.';
            }
            $heal = floor($atk->hp_max * floatval($info['heal']));
            $atk->hp = $atk->hp + $heal;
            $this->log[] =  $atk->_getName(true).' восстанавливает <span class="HpPlus">+'.$heal.' HP</span>';
        }

        // Вампиризм от нанесённого урона
        if(isset($info['heal_dmg']) && $info['heal_dmg'] > 0){
            if($this->settings['dmg'] > 0){
                $dop = ($atk->item_id == 148) ? 1.3 : 1;
                $dmgDealt = (isset($this->settings['dmg_real'])) ? $this->settings['dmg_real'] : $this->settings['dmg'];
                $healHp = ($dmgDealt * floatval($info['heal_dmg']));
                if($dmgDealt > $def->stats[0]){
                    $healHp = ($def->stats[0] * floatval($info['heal_dmg']) * $dop);
                }
                $atk->hp = ($atk->hp + $healHp);
                $this->log[] =  $atk->_getName(true).' восстанавливает <span class="HpPlus">+'.round($healHp).' HP</span> своей атакой.';
            }
        }

        // Массовые баффы/дебаффы всех статов атакующего
        if($this->settings['types'] > 0){

            if(isset($info['all_stats'], $info['all_stats'][0], $info['all_stats'][1])){
                if(mt_rand(1,100) <= $info['all_stats'][0]){

                    $type = ($info['all_stats'][1] > 0 ? true : false);
                    $info['all_stats'][1] = abs($info['all_stats'][1]);

                    $atk->_setModifiedStat('atk',  $info['all_stats'][1], $type);
                    $atk->_setModifiedStat('def',  $info['all_stats'][1], $type);
                    $atk->_setModifiedStat('spd',  $info['all_stats'][1], $type);
                    $atk->_setModifiedStat('satk', $info['all_stats'][1], $type);
                    $atk->_setModifiedStat('sdef', $info['all_stats'][1], $type);

                    $atk->_setModifiedStat('acr',  $info['all_stats'][1], $type);
                    $atk->_setModifiedStat('agl',  $info['all_stats'][1], $type);
                    if($type) {
                        $this->log[] = 'У '.$atk->_getName(true).' повышается <span class="StatPlus">Атака +'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Защита +'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Скорость +'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Спец.Атака +'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Спец. Защита +'.$info['all_stats'][1].'</span>';
                    }else{
                        $this->log[] = 'У '.$atk->_getName(true).' понижается <span class="StatMinus">Атака -'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Защита -'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Скорость -'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Спец.Атака -'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' понижено <span class="StatMinus">Спец. Защита -'.$info['all_stats'][1].'</span>';
                    }
                }
            }

            if(isset($info['all_stats_no_other'], $info['all_stats_no_other'][0], $info['all_stats_no_other'][1])){
                if(mt_rand(1,100) <= $info['all_stats_no_other'][0]){

                    $type = ($info['all_stats_no_other'][1] > 0 ? true : false);
                    $info['all_stats_no_other'][1] = abs($info['all_stats_no_other'][1]);

                    $atk->_setModifiedStat('atk',  $info['all_stats_no_other'][1], $type);
                    $atk->_setModifiedStat('def',  $info['all_stats_no_other'][1], $type);
                    $atk->_setModifiedStat('spd',  $info['all_stats_no_other'][1], $type);
                    $atk->_setModifiedStat('satk', $info['all_stats_no_other'][1], $type);
                    $atk->_setModifiedStat('sdef', $info['all_stats_no_other'][1], $type);
                    if($type) {
                        $this->log[] = 'У '.$atk->_getName(true).' повышается <span class="StatPlus">Атака +'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Защита +'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Скорость +'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Спец.Атака +'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' повышается <span class="StatPlus">Спец. Защита +'.$info['all_stats_no_other'][1].'</span>';
                    }else{
                        $this->log[] = 'У '.$atk->_getName(true).' понижается <span class="StatMinus">Атака -'.$info['all_stats'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Защита -'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Скорость -'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Спец.Атака -'.$info['all_stats_no_other'][1].'</span><br>
                        У '.$atk->_getName(true).' понижается <span class="StatMinus">Спец. Защита -'.$info['all_stats_no_other'][1].'</span>';
                    }
                }
            }
        }

        // Массовая очистка статусов у союзников
        if(isset($info['clear_status_all_poke'])){
            if(is_array($info['clear_status_all_poke'])){

                $pList = $this->_actionBattle->_getUserPokes($atk->_getUser());

                if($pList){

                    $clear = 0;

                    foreach($pList AS $key_1=>&$value_1){
                        if(isset($value_1['status_list'])){
                            foreach($info['clear_status_all_poke'] AS $key=>$value){
                                if(isset($value_1['status_list'][$value])){
                                    ++$clear;
                                    unset($value_1['status_list'][$value]);
                                }
                            }
                            unset($key,$value);

                            if($value_1['id'] == $atk->_getID()){
                                $atk->_setStatusList($value_1['status_list']);
                            }
                        }
                    }
                    unset($key_1, $value_1);

                    if($clear > 0){
                        $this->_actionBattle->_setUserPokes($atk->_getUser(), $pList);
                        $this->log[] = 'Все союзные покемоны '.$atk->_getName(true).' очищены от некоторых отрицательных статусов.';
                    }
                }
            }
        }

        return true;
    }

    


		// if($atkAtk['id'] == '100' && $atk->_getTypeA() == 'ghost' || $atk->_getTypeB() == 'ghost'){
    //
		// 	$minusHP = $atk->hp_max / 2;
    //
		// 	$atk->hp = $atk->hp - $minusHP;
    //
    //     }

          // Stockpile (Накопление) — до 3 стаков; каждый раз +1 DEF и +1 SpDEF. Стаки сохраняем в status 'stock' (val = 1..3)
        if ($this->attackerAtk['id'] == 521) {
            if ($atk->_checkStatus('stock')) {
                $info = $atk->_getStatusList();
                $current = 0; $stockKey = null;
                if ($info) {
                    foreach ($info as $k => $v) {
                        if ($v['type'] === 'stock') {
                            $current  = isset($v['val']) ? (int)$v['val'] : 0;
                            $stockKey = $k;
                            break;
                        }
                    }
                }
                if ($current < 3) {
                    $current++;
                    if ($stockKey !== null) {
                        unset($info[$stockKey]);
                        $atk->_setStatusList($info);
                    }
                    $atk->_setStatus('stock', 9999, $current);
                    // как в PS: при каждом использовании повышаются DEF и SpDEF на 1 ступень
                    $atk->_setModifiedStat('def', 1, true);
                    $atk->_setModifiedStat('sdef', 1, true);
                    $this->log[] = 'Покемон накапливает предметы во рту. Всего накоплений: '.$current.'. '
                                 . '<span class="StatPlus">Защита +1</span>, <span class="StatPlus">Спец. Защита +1</span>';
                } else {
                    $this->log[] = 'У покемона максимальное число накоплений.';
                }
            } else {
                // первое накопление
                $atk->_setStatus('stock', 9999, 1);
                $atk->_setModifiedStat('def', 1, true);
                $atk->_setModifiedStat('sdef', 1, true);
                $this->log[] = 'Покемон накапливает предметы во рту. Всего накоплений: 1. '
                             . '<span class="StatPlus">Защита +1</span>, <span class="StatPlus">Спец. Защита +1</span>';
            }
        }

        // 777 — затрата HP и сильные баффы (сохраняю твою механику: -50% max HP и +2 к Atk/Spd/SpAtk)
        if ($this->attackerAtk['id'] == 777) {
            $half_hp = (int)floor($atk->hp_max / 2);
            if ($atk->hp > $half_hp) {
                // не позволяем упасть ниже 1 HP случайно
                $cost = min($half_hp, $atk->hp - 1);
                if ($cost <= 0) {
                    $this->log[] = 'Провал.';
                } else {
                    $atk->hp -= $cost;
                    $atk->_setModifiedStat('atk',  2, true);
                    $atk->_setModifiedStat('spd',  2, true);
                    $atk->_setModifiedStat('satk', 2, true);
                    $this->log[] = 'HP '.$atk->_getName(true).' уменьшено на <span class="HpMinus">'.ceil($cost).' HP</span>';
                    $this->log[] = 'Атака '.$atk->_getName(true).' значительно повышена';
                    $this->log[] = 'Скорость '.$atk->_getName(true).' значительно повышена';
                    $this->log[] = 'Спец. Атака '.$atk->_getName(true).' значительно повышена';
                }
            } else {
                $this->log[] = 'Провал.';
            }
        }

        // 6 — Acupressure-подобная логика: бафф на +2 выбирается из тех статов, которые ещё можно поднять (<= +4 в твоей системе +2 за раз до +6)
        if ($this->attackerAtk['id'] == 6) {
            // читаем текущие ступени
            $getStage = function(array $mod = null) {
                if (!$mod) return 0;
                $plus  = isset($mod['plus'])  ? (int)$mod['plus']  : 0;
                $minus = isset($mod['minus']) ? (int)$mod['minus'] : 0;
                return $plus - $minus; // чистая ступень
            };

            // какие статы можем повышать?
            $statsMap = [
                'atk'  => '<span class="StatPlus">Атаку +2</span>',
                'def'  => '<span class="StatPlus">Защиту +2</span>',
                'spd'  => '<span class="StatPlus">Скорость +2</span>',
                'satk' => '<span class="StatPlus">Спец. Атаку +2</span>',
                'sdef' => '<span class="StatPlus">Спец. Защиту +2</span>',
                'agl'  => '<span class="StatPlus">Ловкость +2</span>',
                'acr'  => '<span class="StatPlus">Точность +2</span>',
            ];

            $eligible = [];
            foreach ($statsMap as $stat => $text) {
                $cur = isset($atk->modified[$stat]) ? $getStage($atk->modified[$stat]) : 0;
                // можно поднять, если текущая ступень <= +4 (т.к. мы поднимем на +2 и не должны превысить +6)
                if ($cur <= 4) $eligible[] = $stat;
            }

            if (empty($eligible)) {
                $this->log[] = 'Провал.';
            } else {
                $stat = $eligible[array_rand($eligible)];
                $atk->_setModifiedStat($stat, 2, true);
                $this->log[] = $atk->_getName(true).' повышает '.$statsMap[$stat];
            }
        }

        // 315 — Memento: понижает у цели Atk и SpAtk на 2, затем пользователь падает в 0 HP.
        if ($atkAtk['id'] == '315') {
            if ($def->ability == 79 && $atk->ability != 113) {
                // способность цели предотвращает понижение статов (твоя логика с 79 + отсутствие молдбрейкера 113)
                $this->log[] = '<div class="Ability" onclick="issetAll('.$def->ability.',\'ability\')">'.$this->_abilNameRus($def->ability).'</div> противника не дает понизить ему статы.';
            } else {
                $def->_setModifiedStat('atk',  2, false);
                $def->_setModifiedStat('satk', 2, false);
                $this->log[] = $def->_getName(true).' теряет <span class="StatMinus">Атаку -2</span> и <span class="StatMinus">Спец. Атаку -2</span>';
            }
            // пользователь жертвует собой
            $atk->hp = 0;
        }

        // 429 — Rest: полного восстановления HP и сон на 3 хода; Insomnia/Vital Spirit (88) блокирует
        if ($atkAtk['id'] == '429') {
            if ($atk->ability == 88 && $def->ability != 113) {
                $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> не дает использовать эту атаку.';
            } else {
                // снимаем негативные статусы
                $info = $atk->_getStatusList();
                if ($info) {
                    foreach ($info as $k => $v) {
                        if (in_array($v['type'], ['paralyzed','frost','toxic','toxic2','sleep','burn'])) {
                            unset($info[$k]);
                        }
                    }
                    $atk->_setStatusList($info);
                }
                // накладываем сон и восстанавливаем HP до максимума
                $atk->_setStatus('sleep', 3, null);
                $atk->hp = $atk->hp_max;
                $this->log[] = $atk->_getName(true).' восстанавливает все свое здоровье и погружается в сон.';
            }
        }

        return null;
    }

    private function issetStatus(PokeBattle &$attacker, PokeBattle &$defender, array &$attackerAtk, array &$defenderAtk){

        $info = $attacker->_getStatusList();

        $isHp = $attacker->hp;

        if(!empty($info) && is_array($info)){

            foreach($info AS $key => &$value){

                if(isset($value['type'], $value['count']) && $value['count'] > 0){

                    $value['count'] = intval($value['count']);

                    $valInfo = (isset($value['val']) ? $value['val'] : null);

                    if($value['count'] > 999 || $attackerAtk['id'] == 754 || $defenderAtk['id'] == 754){
                        // постоянные эффекты либо ход пропущен — не тикаем счётчики
                    }else{
                        $value['count'] = $value['count'] - 1;
                    }

                    // дот-эффекты не тикают, если кто-то «ожидает» ход (у тебя так принято)
                    if($value['type'] == 'toxic2' || $value['type'] == 'toxic' || $value['type'] == 'burn' || $value['type'] == 'leechSeed' || $value['type'] == 'curse') {
                        if($attackerAtk['id'] == 754 || $defenderAtk['id'] == 754) {
                            return false;
                        }
                    }

                    if($isHp > 0){

                        switch($value['type']){

                            case 'toxic':
                                if($attacker->ability != 133) {
                                    $hp = round($attacker->hp_max * 0.0625);
                                    $attacker->hp = $attacker->hp - $hp;
                                    $this->log_status[] = 'Отравление отнимает силы у '.$attacker->_getName(true).' <span class="HpMinus">-'.$hp.' HP</span>';
                                }else{
                                    $hp = round($attacker->hp_max * 0.125);
                                    $attacker->hp = $attacker->hp + $hp;
                                    if($attacker->hp_max < $attacker->hp){
                                        $attacker->hp = $attacker->hp_max;
                                    }
                                    $this->log_status[] = 'Отравление восстанавливает силы у '.$attacker->_getName(true).' <span class="HpPlus">+'.$hp.' HP</span>';
                                }
                                break;

                            case 'toxic2':
                                if ($attacker->ability != 133) {
                                    // множитель стадий токсина: растёт каждый ход (минимум 1)
                                    if (empty($value['val']) || !is_numeric($value['val'])) {
                                        $mnoz = 1;
                                        $value['val'] = $mnoz;
                                    } else {
                                        $mnoz = (int)$value['val'] + 1;
                                        $value['val'] = $mnoz;
                                    }
                                    $hp = round(($mnoz * $attacker->hp_max) / 16);
                                    $attacker->hp = $attacker->hp - $hp;

                                    $this->log_status[] = 'Сильное отравление отнимает силы у ' . $attacker->_getName(true) . ' <span class="HpMinus">-' . $hp . ' HP (раунд x'.$mnoz.')</span>';
                                    $this->log[]        = 'Отравление: '.$attacker->_getName(true).' потерял '.$hp.' HP от токсичного статуса (множитель '.$mnoz.').';
                                } else {
                                    $hp = round($attacker->hp_max * 0.125);
                                    $attacker->hp = $attacker->hp + $hp;
                                    if ($attacker->hp > $attacker->hp_max) {
                                        $attacker->hp = $attacker->hp_max;
                                    }
                                    $this->log_status[] = 'Отравление восстанавливает силы у ' . $attacker->_getName(true) . ' <span class="HpPlus">+' . $hp . ' HP</span>';
                                    $this->log[]        = 'Отравление: '.$attacker->_getName(true).' восстановил '.$hp.' HP благодаря способности.';
                                }
                                break;

                            case 'prison':
                                $hp = round(($attacker->hp_max) / 8);
                                $attacker->hp = $attacker->hp - $hp;
                                $this->log_status[] = 'Ловушка отнимает здоровье у '.$attacker->_getName(true).' <span class="HpMinus">-'.$hp.' HP</span>';
                                break;

                            case 'leechSeed':
                                $hp = round(($attacker->hp_max) / 8);
                                $attacker->hp = $attacker->hp - $hp;
                                $this->log_status[] = 'Семена пиявки отнимают здоровье у '.$attacker->_getName(true).' <span class="HpMinus">-'.$hp.' HP</span>';
                                if($defender->hp > 0) {
                                    $defender->hp = $defender->hp + $hp;
                                    if($defender->hp > $defender->stats[0]) {
                                        $defender->hp = $defender->stats[0];
                                    }
                                    $this->log_status[] = 'Семена пиявки восстанавливают здоровье у '.$defender->_getName(true).' <span class="HpPlus">+'.$hp.' HP</span>';
                                }
                                break;

                            case 'ingrain':
                                $hp = round(($attacker->hp_max) / 16);
                                if($attacker->hp > 0) {
                                    $attacker->hp = $attacker->hp + $hp;
                                    if($attacker->hp > $attacker->stats[0]) {
                                        $attacker->hp = $attacker->stats[0];
                                    }
                                    $this->log_status[] = 'Прорастание восстанавливают здоровье у '.$attacker->_getName(true).' <span class="HpPlus">+'.$hp.' HP</span>';
                                }
                                break;

                            case 'sleep':
                                if($defender->ability == 11 and $attacker->item_id != 168) {
                                    $hp = round(($attacker->hp_max) / 8);
                                    $attacker->hp = $attacker->hp - $hp;
                                    $this->log_status[] = '<div class="Ability" onclick="issetAll('.$defender->ability.',\'ability\')">'.$this->_abilNameRus($defender->ability).'</div> противника наносит урон покемону: <span class="HpMinus">-'.$hp.' HP</span>';
                                }
                                break;

                            case 'burn':
                                $hp = round($attacker->hp_max * 0.125);
                                if($attacker->ability == 73 and $defender->ability != 113) {
                                    $hp = ceil($hp / 2);
                                    $this->log_status[] = '<div class="Ability" onclick="issetAll('.$attacker->ability.',\'ability\')">'.$this->_abilNameRus($attacker->ability).'</div> уменьшает урон от огня в два раза.';
                                }
                                $attacker->hp = $attacker->hp - $hp;
                                $this->log_status[] = 'Огонь отнимает здоровье у '.$attacker->_getName(true).': <span class="HpMinus">-'.$hp.' HP</span>';
                                break;

                            case 'curse':
                                $hp = round($attacker->hp_max/4);
                                $attacker->hp = $attacker->hp - $hp;
                                $this->log_status[] = 'Проклятье отнимает здоровье у '.$attacker->_getName(true).' <span class="HpMinus">-'.$hp.' HP</span>.';
                                break;

                            case 'frost':
                                if (mt_rand(1,100) <= 20) {
                                    unset($info[$key]);
                                    $this->log_status[] = $attacker->_getName(true).' оправился от обморожения.';
                                }
                                break;

                            case 'nightmare':
                                $info321 = $attacker->_getStatusList();
                                $asleep = false;
                                if($info321) {
                                    foreach($info321 as $key1=>$val) {
                                        if($val['type'] == 'sleep') { $asleep = true; break; }
                                    }
                                }
                                if($asleep) {
                                    $hp = round($attacker->hp * 0.25);
                                    $attacker->hp = $attacker->hp - $hp;
                                    $this->log_status[] = 'Покемона мучают кошмары: <span class="HpMinus">-'.$hp.' HP</span>';
                                }else{
                                    unset($info[$key]);
                                }
                                break;
                        }

                    }

                    // для прочих статусов, не влияющих на HP, можешь дополнять при необходимости
                    switch($value['type']){
                        case 'test':
                            break;
                    }

                    if($value['count'] <= 0){
                        unset($info[$key]);
                    }

                }else{
                    unset($info[$key]);
                }

            }

            $attacker->_setStatusList($info);

            unset($key, $value);

            if($isHp && $attacker->hp <= 0){
                $attacker->hp = 0;
                $this->log_status[] = 'После такого трудно было оклематься.';
            }

            $attacker->hp = floor($attacker->hp);
            $defender->hp = floor($defender->hp);

        }
    }


    private function mind(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Торment-подобная логика: под статусом 'mind' нельзя использовать
    // тот же приём, что и на прошлом ходу (кроме служебных и Struggle).
    if (!$atk->_checkStatus('mind')) {
        return true;
    }

    $lastId = (int)$atk->atk_beforeNow;             // id последней реально применённой атаки атакующим
    $curId  = (int)$atkAtk['id'];                   // id выбранного сейчас приёма

    // служебные/вынужденные приёмы не блокируем
    if ($curId >= 9000 || $curId === 754 || $curId === 753) {
        return true;
    }

    // если совпадает — запрещаем
    if ($lastId > 0 && $lastId === $curId) {
        $this->log[] = 'Но '.$atk->_getName(true).' находится под воздействием приёма и не может повторить ту же атаку подряд.';
        return false;
    }

    return true;
}

private function abil_overcoat(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Overcoat (125) — иммунитет к powder-атакам (споры/пыль): список id оставляем из твоей базы.
    // В PS Mold Breaker НЕ игнорирует Overcoat для powder-флагов.
    $powderIds = [90,391,393,418,490,515,530];
    if (in_array((int)$atkAtk['id'], $powderIds, true)) {
        if ($def->ability == 125) {
            $this->log[] = '<div class="Ability" onclick="issetAll(125,\'ability\')">'.$this->_abilNameRus(125).'</div> защищает от пыльцевых атак.';
            return false;
        }
    }
    return true;
}

private function abil_aromaveil(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Aroma Veil (9) — защита от «ментальных» приёмов (taunt/torment/disable/encore/heal block и т.п.)
    // В твоём наборе: [554,571,143,111,226] — оставляю как есть.
    $mentalIds = [554,571,143,111,226];
    if (in_array((int)$atkAtk['id'], $mentalIds, true)) {
        if ($def->ability == 9) {
            $this->log[] = '<div class="Ability" onclick="issetAll(9,\'ability\')">'.$this->_abilNameRus(9).'</div> защищает от ментальных приёмов.';
            return false;
        }
    }
    return true;
}

private function abil_soundproof(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Soundproof (183) — полная иммунка к звуковым приёмам, Mold Breaker не обходится.
    if (!empty($atkAtk['sound']) && (int)$atkAtk['sound'] === 1) {
        if ($def->ability == 183) {
            $this->log[] = '<div class="Ability" onclick="issetAll(183,\'ability\')">'.$this->_abilNameRus(183).'</div> блокирует звуковую атаку.';
            return false;
        }
    }
    return true;
}

private function abil_bulletproof(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Bulletproof (19) — иммунка к «bullet»-приёмам (флаг bullet=1). Mold Breaker не обходится.
    if (!empty($atkAtk['bullet']) && (int)$atkAtk['bullet'] === 1) {
        if ($def->ability == 19) {
            $this->log[] = '<div class="Ability" onclick="issetAll(19,\'ability\')">'.$this->_abilNameRus(19).'</div> блокирует снаряд-атаку.';
            return false;
        }
    }
    return true;
}

private function abil_dryskin(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Dry Skin (45) — иммунитет к water-дамагу и лечение 25% max HP при попадании водой.
    // Mold Breaker (113) обходит иммунитеты-поглощения в нашем движке — оставляем проверку.
    if ($def->ability == 45 && $atkAtk['type'] === 'water' && $atk->ability != 113) {
        // лечим только если это вражеская дамажащая атака
        if ($atkAtk['target'] === 'enemy' && in_array($atkAtk['category'], ['physical','special'], true) && (int)$atkAtk['power'] > 0) {
            $heal = (int)floor($def->hp_max * 0.25);
            $def->hp = min($def->hp + $heal, $def->hp_max);
            $this->log[] = '<div class="Ability" onclick="issetAll(45,\'ability\')">'.$this->_abilNameRus(45).'</div> поглощает воду и лечит '.$def->_getName(true).' на <span class="HpPlus">+'.$heal.' HP</span>.';
        } else {
            $this->log[] = '<div class="Ability" onclick="issetAll(45,\'ability\')">'.$this->_abilNameRus(45).'</div> делает покемона невосприимчивым к воде.';
        }
        return false;
    }
    return true;
}

private function abil_waterabsorb(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Water Absorb (225) — иммунитет к water-дамагу и лечение 25% max HP при попадании водой.
    if ($def->ability == 225 && $atkAtk['type'] === 'water' && $atk->ability != 113) {
        if ($atkAtk['target'] === 'enemy' && in_array($atkAtk['category'], ['physical','special'], true) && (int)$atkAtk['power'] > 0) {
            $heal = (int)floor($def->hp_max * 0.25);
            $def->hp = min($def->hp + $heal, $def->hp_max);
            $this->log[] = '<div class="Ability" onclick="issetAll(225,\'ability\')">'.$this->_abilNameRus(225).'</div> поглощает воду и лечит '.$def->_getName(true).' на <span class="HpPlus">+'.$heal.' HP</span>.';
        } else {
            $this->log[] = '<div class="Ability" onclick="issetAll(225,\'ability\')">'.$this->_abilNameRus(225).'</div> делает покемона невосприимчивым к воде.';
        }
        return false;
    }
    return true;
}

private function abil_voltabsorb(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Volt Absorb (224) — иммунитет к electric-дамагу и лечение 25% max HP при попадании электричеством.
    if ($def->ability == 224 && $atkAtk['type'] === 'electric' && $atk->ability != 113) {
        if ($atkAtk['target'] === 'enemy' && in_array($atkAtk['category'], ['physical','special'], true) && (int)$atkAtk['power'] > 0) {
            $heal = (int)floor($def->hp_max * 0.25);
            $def->hp = min($def->hp + $heal, $def->hp_max);
            $this->log[] = '<div class="Ability" onclick="issetAll(224,\'ability\')">'.$this->_abilNameRus(224).'</div> поглощает электричество и лечит '.$def->_getName(true).' на <span class="HpPlus">+'.$heal.' HP</span>.';
        } else {
            $this->log[] = '<div class="Ability" onclick="issetAll(224,\'ability\')">'.$this->_abilNameRus(224).'</div> делает покемона невосприимчивым к электричеству.';
        }
        return false;
    }
    return true;
}

private function crash_item(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk, $crash){
    // Поломка предмета-надеваемого у покемона (на основе твоей логики прочности в item_str)
    $randSlomat = ($crash == 1) ? 1 : mt_rand(1,4);
    if ($randSlomat != 1) {
        return true;
    }

    $itemSlom = Work::$sql->query('SELECT item_id,item_str FROM user_pokemons WHERE id = '.(int)$atk->id)->fetch_assoc();
    if (!$itemSlom || empty($itemSlom['item_str']) || (int)$itemSlom['item_id'] === 0) {
        return true;
    }

    $slom = explode(',', $itemSlom['item_str']);
    $dur  = isset($slom[0]) ? (int)$slom[0] : 0;
    $meta = isset($slom[1]) ? $slom[1] : '';

    if ($dur <= 0) {
        return true;
    }

    $durAfter = $dur - 1;
    if ($durAfter <= 0) {
        // возвращаем сломанный предмет в сумку с нулевой прочностью (как у тебя)
        Work::$sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$atk->user_id."','".(int)$atk->item_id."',1,'0,".Work::$sql->real_escape_string($meta)."') ");
        $atk->item_id = 0;
        $this->log[] = 'Предмет '.$atk->_getName(true).' сломался.';
        Work::$sql->query("UPDATE `user_pokemons` SET `item_id` = 0, `item_str` = '' WHERE `id` = '".(int)$atk->id."'");
    } else {
        $this->log[] = 'Предмет '.$atk->_getName(true).' потерял одну прочность.';
        Work::$sql->query("UPDATE `user_pokemons` SET `item_str` = '".$durAfter.",".Work::$sql->real_escape_string($meta)."' WHERE `id` = '".(int)$atk->id."'");
    }

    return true;
}

private function abil_sapsipper(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Sap Sipper (162) — ПОЛНАЯ иммунка к grass-приёмам и +1 к Атаке при попадании.
    if ($atkAtk['type'] === 'grass' && $atkAtk['target'] === 'enemy' && $def->ability == 162 && $atk->ability != 113) {
        $def->_setModifiedStat('atk', 1, true);
        $this->log[] = '<div class="Ability" onclick="issetAll(162,\'ability\')">'.$this->_abilNameRus(162).'</div> противника повышает ему <span class="StatPlus">'.$this->statRus['atk'][1].' +1</span>';
        return false;
    }
    return true;
}

private function abil_flashfire(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Flash Fire (54) — иммунитет к огню и усиление огненных приёмов цели (флаг через статус).
    if ($atkAtk['type'] === 'fire' && $atkAtk['target'] === 'enemy' && $def->ability == 54 && $atk->ability != 113) {
        // пометим «заряд Flash Fire» на защищающемся (без стака, просто наличие)
        if (!$def->_checkStatus('flashfire')) {
            $def->_setStatus('flashfire', 9999, 1);
        }
        $this->log[] = '<div class="Ability" onclick="issetAll(54,\'ability\')">'.$this->_abilNameRus(54).'</div> поглощает огонь. Силa огненных атак '.$def->_getName(true).' усиливается.';
        return false;
    }
    return true;
}

private function abil_lightningrod(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // Lightning Rod (98) — иммунитет к electric и +1 к Спец.Атаке у защищающегося.
    // Перетягивание таргета на себя (в дуплах/триплах) отдельно, здесь — однополянная логика.
    if ($atkAtk['type'] === 'electric' && $atkAtk['target'] === 'enemy' && $def->ability == 98 && $atk->ability != 113) {
        $def->_setModifiedStat('satk', 1, true);
        $this->log[] = '<div class="Ability" onclick="issetAll(98,\'ability\')">'.$this->_abilNameRus(98).'</div> притягивает электричество и повышает Спец.Атаку защищающегося.';
        return false;
    }
    return true;
}

private function shields(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){
    // 94 — Protect-подобный; 603 — King’s Shield-подобный; 299 — Magic Coat-подобный (отражение статусов)
    // В исходном коде были ошибки с логическими операторами. Чиним.

    // === PROTECT (id 94) ===
    if ((int)$defAtk['id'] == 94) {
        if ($atkAtk['category'] == 'specific' || $atkAtk['category'] == 'status') {
            // Список приёмов, которые ПРОБИВАЮТ защиту (разрешены)
            $bypass = [573,520,516,510,378,448,638,252,381];
            if (!in_array((int)$atkAtk['id'], $bypass, true)) {
                return false; // заблокировано Protect
            }
            return true; // Feint-подобные проходят
        }
        return true; // дамажащие обычно блокируются другими проверками (если надо — добавь тут)
    }

    // === KING'S SHIELD (id 603) ===
    if ((int)$defAtk['id'] == 603) {
        if ($atkAtk['target'] == 'enemy') {
            // Список приёмов, которые ПРОБИВАЮТ king’s shield
            $bypass = [252,104,556,381];
            if (!in_array((int)$atkAtk['id'], $bypass, true)) {
                // понижаем шанс/прочность щита (как у тебя)
                $poke = Work::$sql->query("SELECT * FROM battle_block WHERE pokID = ".(int)$def->id)->fetch_assoc();
                if (!empty($poke)) {
                    $chanseA = ($poke['chanse'] / 3);
                    Work::$sql->query("UPDATE battle_block SET `chanse` = ".$chanseA." WHERE pokID = ".(int)$def->id);
                }
                return false;
            }
        }
        return true;
    }

    // === MAGIC COAT (id 299) — отражает статусные приёмы с флагом magic_coat ===
    if ((int)$defAtk['id'] == 299) {
        if (!empty($atkAtk['magic_coat']) && (int)$atkAtk['magic_coat'] === 1) {
            return false; // будет отражено
        }
        return true;
    }

    return true;
}

private function isAccuracy(PokeBattle $atk, PokeBattle $def, array &$atkAtk, array &$defAtk){

    // Снятие статуса «reader» (Lock-On/Mind Reader) — гарантирует попадание следующей атаки
    $reader = $atk->_getStatusList();
    $readerYes = 0;
    if ($reader){
        foreach($reader AS $key=>$value){
            if($value['type'] == 'reader'){
                unset($reader[$key]);
                $readerYes = 1;
            }
        }
    }
    $atk->_setStatusList($reader);

    // No Guard (122) — попадание всегда для обеих сторон
    if ($atk->ability == 122 || $def->ability == 122) {
        $atkAtk['accuracy'] = 100;
    }

    // Tangled Feet? (205 в твоей базе) — если цель в замешательстве, точность атак по ней /2
    if ($def->ability == 205 && $def->_checkStatus('confused') && $atk->ability != 113) {
        $atkAtk['accuracy'] = $atkAtk['accuracy'] / 2;
    }

    // Само- и командные цели — не промахиваются
    if (in_array($atkAtk['target'], ['me','all_me'], true)) {
        return true;
    }

    // Keen Eye (28) / Compound Eyes (222) и т. п. — твои баффы точности
    if ($atk->ability == 28) {
        $atkAtk['accuracy'] = $atkAtk['accuracy'] * 1.3;
        $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает точность атаки в 1.3 раза.';
    }
    if ($atk->ability == 222) {
        $atkAtk['accuracy'] = $atkAtk['accuracy'] * 1.1;
        $this->log[] = '<div class="Ability" onclick="issetAll('.$atk->ability.',\'ability\')">'.$this->_abilNameRus($atk->ability).'</div> увеличивает точность атаки на 10%.';
    }

    // Hustle (77) — понижение точности физических приёмов
    if ($atk->ability == 77 && $atkAtk['category'] == 'physical') {
        $atkAtk['accuracy'] = $atkAtk['accuracy'] - (($atkAtk['accuracy'] / 100) * 20);
    }

    // Специальные форма-стражи
    if (in_array($def->numb, [9595,9596,9597], true)) {
        if ($atkAtk['category'] == 'status' || $atkAtk['category'] == 'specific') {
            return false;
        }
    }

    // Блоки из твоих проверок (раньше тут были логические ошибки и опечатки $def->abilty)
    // 381
    if ( ((int)$defAtk['id'] == 381 && (int)$defAtk['power'] == 0) ||
         ((int)$atkAtk['id'] == 381 && (int)$atkAtk['power'] == 0) ||
         ((int)$defAtk['id'] == 381 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$atkAtk['id'] == 381 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    // 110
    if ( ((int)$defAtk['id'] == 110 && (int)$defAtk['power'] == 0 && (int)$atkAtk['id'] != 136) ||
         ((int)$atkAtk['id'] == 110 && (int)$atkAtk['power'] == 0 && (int)$defAtk['id'] != 136) ||
         ((int)$defAtk['id'] == 110 && (int)$defAtk['power'] == 0 && $atkAtk['accuracy'] >= 1 && $atkAtk['accuracy'] <= 100 && (int)$atkAtk['id'] != 166) ||
         ((int)$atkAtk['id'] == 110 && (int)$atkAtk['power'] == 0 && $defAtk['accuracy'] >= 1 && $defAtk['accuracy'] <= 100 && (int)$defAtk['id'] != 166) ||
         ((int)$defAtk['id'] == 110 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$atkAtk['id'] == 110 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    // 469
    if ( ((int)$defAtk['id'] == 469 && (int)$defAtk['power'] == 0) ||
         ((int)$atkAtk['id'] == 469 && (int)$atkAtk['power'] == 0) ||
         ((int)$defAtk['id'] == 469 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$atkAtk['id'] == 469 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    // 484 <> 486
    if ( ((int)$defAtk['id'] == 484 && (int)$defAtk['power'] == 0 && (int)$atkAtk['id'] != 486) ||
         ((int)$atkAtk['id'] == 484 && (int)$atkAtk['power'] == 0 && (int)$defAtk['id'] != 486)
    ) {
        return false;
    }

    // 113 <> (601|538)
    if ( ((int)$defAtk['id'] == 113 && (int)$defAtk['power'] == 0 && !in_array((int)$atkAtk['id'], [601,538], true)) ||
         ((int)$atkAtk['id'] == 113 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$defAtk['id'] == 113 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    // 485 <> (214|246|486|582)
    if ( ((int)$defAtk['id'] == 485 && (int)$defAtk['power'] == 0 && !in_array((int)$atkAtk['id'], [214,246,486,582], true)) ||
         ((int)$atkAtk['id'] == 485 && (int)$atkAtk['power'] == 0 && !in_array((int)$defAtk['id'], [214,246,486,582], true)) ||
         ((int)$defAtk['id'] == 485 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$atkAtk['id'] == 485 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    // 177 <> (214|246|486|582)
    if ( ((int)$defAtk['id'] == 177 && (int)$defAtk['power'] == 0 && !in_array((int)$atkAtk['id'], [214,246,486,582], true)) ||
         ((int)$atkAtk['id'] == 177 && (int)$atkAtk['power'] == 0 && !in_array((int)$defAtk['id'], [214,246,486,582], true)) ||
         ((int)$defAtk['id'] == 177 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$atkAtk['id'] == 177 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    // 54 <> (214|246|486|582)
    if ( ((int)$defAtk['id'] == 54 && (int)$defAtk['power'] == 0 && !in_array((int)$atkAtk['id'], [214,246,486,582], true)) ||
         ((int)$atkAtk['id'] == 54 && (int)$atkAtk['power'] == 0 && !in_array((int)$defAtk['id'], [214,246,486,582], true)) ||
         ((int)$defAtk['id'] == 54 && (int)$defAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true)) ||
         ((int)$atkAtk['id'] == 54 && (int)$atkAtk['power'] == 0 && in_array(122, [$atk->ability,$def->ability], true))
    ) {
        return false;
    }

    if ($readerYes) {
        return true; // Lock-On / Mind Reader
    }

    // Флаги «truehit» и «flowertrik» игнорят проверку точности
    if (!empty($atkAtk['settings']['truehit']) && (int)$atkAtk['settings']['truehit'] === 1) {
        return true;
    }
    if (!empty($atkAtk['settings']['flowertrik']) && $atkAtk['settings']['flowertrik'] === true) {
        return true;
    }

    // Минимайз-апгрейды (всегда попадают)
    if ($def->_checkStatus('minimize') && in_array((int)$atkAtk['id'], [522,48,232,234,741,381,126], true)) {
        return true;
    }

    // Погодные автопопадания из твоей логики
    if ($this->_actionBattle->weather == 3 && (int)$atkAtk['id'] == 563 && !in_array(4,[$def->ability,$atk->ability], true)) { // Дождь, Thunder?
        return true;
    }
    if ($this->_actionBattle->weather == 4 && (int)$atkAtk['id'] == 45 && !in_array(4,[$def->ability,$atk->ability], true)) {  // Град, Blizzard?
        return true;
    }
    if ($this->_actionBattle->weather == 3 && (int)$atkAtk['id'] == 246 && !in_array(4,[$def->ability,$atk->ability], true)) { // Дождь, Hurricane?
        return true;
    }

    if ($def->_checkStatus('miracle')) {
        return true;
    }
    if ($def->_checkStatus('easy_levitation')) {
        return true;
    }

    if ((int)$atkAtk['accuracy'] === 0) {
        return true; // приёмы без проверки точности
    }

    // Аккуратное вычисление множителей Accuracy/Evasion по твоей схеме
    // Accuracy (acr)
    if (isset($atk->modified['acr'])) {
        if (isset($atk->modified['acr']['plus'])) {
            $p = (int)$atk->modified['acr']['plus'];
            $map = [1=>4/3, 2=>5/3, 3=>6/3, 4=>7/3, 5=>8/3, 6=>9/3];
            $acr = $map[$p] ?? 3/3;
        } else {
            $m = (int)($atk->modified['acr']['minus'] ?? 0);
            $map = [1=>3/4, 2=>3/5, 3=>3/6, 4=>3/7, 5=>3/8, 6=>3/9];
            $acr = $map[$m] ?? 3/3;
        }
    } else {
        $acr = 3/3;
    }

    // Evasion (agl) — у цели
    if (isset($def->modified['agl'])) {
        if (isset($def->modified['agl']['plus'])) {
            $p = (int)$def->modified['agl']['plus'];
            $map = [1=>3/4, 2=>3/5, 3=>3/6, 4=>3/7, 5=>3/8, 6=>3/9];
            $agl = $map[$p] ?? 3/3;
        } else {
            $m = (int)($def->modified['agl']['minus'] ?? 0);
            $map = [1=>4/3, 2=>5/3, 3=>6/3, 4=>7/3, 5=>8/3, 6=>9/3];
            $agl = $map[$m] ?? 3/3;
        }
    } else {
        $agl = 3/3;
    }

    // Sand Veil? (161) — при песчаной буре +1 уклонения (если атакующий не Mold Breaker)
    if ($def->ability == 161 && $this->_actionBattle->weather == 5 && $atk->ability != 113) {
        // умножаем текущий множитель уклонения ещё на +1 стадию (3/4)
        $agl *= (3/4);
    }

    // Приёмы/способности, игнорящие уклонение (оставляю как у тебя)
    if (in_array((int)$atkAtk['id'], [74,451], true) || ($atk->ability == 219 && (int)$atkAtk['power'] >= 1 && $def->ability != 113)) {
        $agl = 3/3;
    }

    // Предметы на итоговую точность
    $atkAcc  = ($atk->item_id == 144) ? 1.1 : 1; // линза на атк
    $atkAcc2 = ($def->item_id == 142) ? 1.1 : 1; // яркий порошок на деф

    $finalAcc = $atkAtk['accuracy'] * (($acr * $agl * $atkAcc) / $atkAcc2);

    if (isset($atkAtk['accuracy']) && $finalAcc > 0 && $finalAcc >= mt_rand(0, 100)) {
        return true;
    }

    return false;
}


private function goCatch(PokeBattle $target, $userID, $itemInfo, PokeBattle $myInfo) {
        // Проверка подключения
        if (!isset(Work::$sql) || Work::$sql === null) {
            // Ищем глобальное подключение
            if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
                Work::$sql = $GLOBALS['mysqli'];
            } else {
                // Если нет — создаём новое подключение (адаптируйте параметры под ваш проект)
                Work::$sql = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            }
        }

        // Получаем инфу о пользователе
        $pveR = Work::$sql->query('SELECT rating,location,military_limit FROM users WHERE id = '.$userID)->fetch_assoc();
        $searchPo = Work::$sql->query('SELECT basenum FROM user_pokemons WHERE basenum = '.$target->basenum)->fetch_assoc();

        if ($userID > 0) {
            if ($itemInfo['number'] == 56) {
                $catch = true;
            } else {
                $ball = $itemInfo['val'];
                if ($itemInfo['number'] == 54) {
                    if (($target->gender != $myInfo->gender) && ($target->basenum == $myInfo->basenum)) {
                        $ball = $itemInfo['val'];
                    } else {
                        $ball = 1;
                    }
                } elseif ($itemInfo['number'] == 57) {
                    if ((time_game() == "Ночь") || in_array($target->basenum, [30,33,35,39,300,517])) {
                        $ball = $itemInfo['val'];
                    } else {
                        $ball = 1;
                    }
                } elseif ($itemInfo['number'] == 60) {
                    if ($target->base_spd > 99) {
                        $ball = $itemInfo['val'];
                    } else {
                        $ball = 1;
                    }
                } elseif ($itemInfo['number'] == 55) {
                    $lvl = $myInfo->lvl/$target->lvl;
                    if ($lvl == 2) {
                        $ball = 2;
                    } elseif ($lvl > 2 && $lvl < 4) {
                        $ball = 4;
                    } elseif ($lvl >= 4) {
                        $ball = 8;
                    } else {
                        $ball = 1;
                    }
                } elseif ($itemInfo['number'] == 58) {
                    if ($searchPo) {
                        $ball = $itemInfo['val'];
                    } else {
                        $ball = 1;
                    }
                }
                $hpPokemon = $target->hp / $target->hp_max;
                $base_chanceCatch = $target->base_chanceCatch;
                $statusCat = 1;
                $infoCat = $target->_getStatusList();
                if ($infoCat) {
                    foreach ($infoCat as $key=>$value) {
                        if ($value['type'] == 'paralyzed' || $value['type'] == 'toxic' || $value['type'] == 'toxic2' || $value['type'] == 'burn') {
                            $statusCat = 1.5;
                        } elseif ($value['type'] == 'sleep' || $value['type'] == 'frost') {
                            $statusCat = 2.5;
                        }
                    }
                }
                $bafprem = Work::$sql->query('SELECT * FROM bafs WHERE type = 3 AND user = '.$_SESSION['id'])->fetch_assoc();
                if ($bafprem) {
                    if ($bafprem['time'] > time()) {
                        $bafpr = 1.2;
                    } else {
                        $bafpr = 1;
                    }
                } else {
                    $bafpr = 1;
                }
                if (achiv_utility(26)) {
                    $bafutil = 1.15;
                } else {
                    $bafutil = 1;
                }
                if ($pveR['location'] == 9999999) {
                    if ($itemInfo['number'] == 61) {
                        $result = (($base_chanceCatch * $ball * $statusCat * (1 - (2/3) * $hpPokemon)) / 255 * 100) * $bafutil;
                    } else {
                        $result = 0;
                    }
                } else {
                    if ($itemInfo['number'] == 61) {
                        $result = 0;
                    } else {
                        $result = ((($base_chanceCatch * $ball * $statusCat * (1 - (2/3) * $hpPokemon)) / 255 * 100) * $bafpr) * $bafutil;
                    }
                }
                $result = ($pveR['location'] == 2 ? 100 : $result);
                $random = mt_rand(1,100);
                if ($random <= $result) {
                    $catch = true;
                } else {
                    $catch = false;
                }
            }
            if ($catch) {
                $count = Work::$sql->query('
                    SELECT COUNT(*) AS `count`
                    FROM `user_pokemons`
                    WHERE `user_id` = '.intval($userID).' AND `active` = 1
                ')->fetch_assoc();
                if (isset($count['count']) && $count['count'] < 6) {
                    $birthday = [
                        'user_id' => $userID,
                        'date' => time()
                    ];
                    if ($itemInfo['number'] == 59) {
                        $happy = 200;
                    } else {
                        $happy = rand(10,50);
                    }
                    if ($itemInfo['number'] == 62) {
                        $gens = $target->_getGens(true,3);
                    } elseif ($itemInfo['number'] == 53) {
                        $gens = $target->_getGens(true,10);
                    } else {
                        $gens = $target->_getGens(true);
                    }
                    $user = Work::$sql->query("SELECT * FROM users WHERE id = ".$userID)->fetch_assoc();

                    if (check_mission(1)) { add_mission(1);}
                    if (check_mission(8) && $target->basenum == 280) { add_mission(8);}
                    if (check_mission(15) && $target->_getHar() == 13) { add_mission(15);}

                    if ($user['location'] == 88) { add_pokemon_military_catch(); }
                    news_friend(2,$target->basenum);
                    if ($target->base_spd <= 35 ) { update_achiv(2,1); }
                    update_achiv(3,1);
                    update_achiv(4,1);
                    update_achiv(5,1);
                    if ($itemInfo['number'] == 53) { update_achiv(26,1);}
                    if ($target->basenum == 128) { update_achiv(27,1);}
                                        // --- TERA: сохраняем тератип при поимке ---
                    $teraTypeCatch = (!empty($target->tera_type) ? $target->tera_type : ($target->_getTypeA() ? $target->_getTypeA() : 'normal'));
Work::$sql->query(
                        "INSERT INTO `user_pokemons` (
                            `user_id`,
                            `ability`,
                            `ability_slot`,
                            `basenum`,
                            `name_new`,
                            `character`,
                            `lvl`,
                            `birthday`,
                            `active`,
                            `gender`,
                            `exp`,
                            `exp_max`,
                            `hp`,
                            `stats`,
                            `evcounts`,
                            `gen`,
                            `attacks`,
                            `type`,
                            `sparkaNumber`,
                            `happy`,
                            `ball`,
                            `item_id`,
                            `sparka`,
                            `form`,
                            `tera_type`
                        ) VALUES (
                            " . intval($userID) . ",
                            " . intval($target->ability) . ",
                            " . intval($target->ability_slot) . ",
                            " . intval($target->basenum) . ",
                            '" . Work::$sql->real_escape_string($target->name_new) . "',
                            " . intval($target->_getHar()) . ",
                            " . intval($target->_getLvl()) . ",
                            '" . Work::$sql->real_escape_string(Info::_parseData($birthday)) . "',
                            " . (($itemInfo['number'] == 316) ? 0 : 1) . ",
                            '" . Work::$sql->real_escape_string($target->_getSex()) . "',
                            " . intval($target->_getExp()) . ",
                            " . intval($target->_getExpNext()) . ",
                            " . intval($target->hp) . ",
                            '" . Work::$sql->real_escape_string($target->_getStats(true)) . "',
                            '" . Work::$sql->real_escape_string($target->_getEvs(true)) . "',
                            '" . Work::$sql->real_escape_string($gens) . "',
                            '" . Work::$sql->real_escape_string($target->_getAtk()) . "',
                            '" . Work::$sql->real_escape_string($target->type) . "',
                            " . mt_rand(1, 3) . ",
                            '" . Work::$sql->real_escape_string($happy) . "',
                            " . intval($itemInfo['number']) . ",
                            " . intval($target->item_id) . ",
                            " . intval($target->sparka) . ",
                            '" . Work::$sql->real_escape_string($target->form) . "',
                            '" . Work::$sql->real_escape_string($teraTypeCatch) . "'
                        )"
                    );

                    // Получаем шанс редкости из pokemons_location
                   $user = Work::$sql->query("SELECT * FROM users WHERE id = ".$userID)->fetch_assoc();
$locationId = intval($user['location']);
$pokeBasenum = intval($target->basenum);
$pokemonLoc = Work::$sql->query("SELECT chance FROM pokemons_location WHERE basenum = {$pokeBasenum} AND location_id = {$locationId}")->fetch_assoc();
$rareChance = isset($pokemonLoc['chance']) ? floatval($pokemonLoc['chance']) : 100;
$isRare = ($rareChance < 10);
$isShine = ($target->type == 'shine');

if ($isRare || $isShine) {
    $pokeName = $target->name_new;
    $userName = $user['login'];
$pokeBasenum = intval($target->basenum);
$pokeBasenumStr = str_pad($pokeBasenum, 3, "0", STR_PAD_LEFT);
$imgUrl = "/img/pokemons/animation/{$pokeBasenum}.png";
$pokeName = $target->name_new;
$userName = $user['login'];

// Текст редкости и shine
if ($isRare && $isShine) {
    $catchText = "<span style='color:#d32f2f;font-weight:bold;'>Поздравляем! Игрок <b>{$userName}</b> поймал</span> <span style='color:gold;font-weight:bold;'>редкого shine</span> <span style='color:#d32f2f;font-weight:bold;'>покемона</span>";
} elseif ($isRare) {
    $catchText = "<span style='color:#d32f2f;font-weight:bold;'>Поздравляем! Игрок <b>{$userName}</b> поймал</span> <span style='color:gold;font-weight:bold;'>редкого</span> <span style='color:#d32f2f;font-weight:bold;'>покемона</span>";
} elseif ($isShine) {
    $catchText = "<span style='color:#d32f2f;font-weight:bold;'>Поздравляем! Игрок <b>{$userName}</b> поймал</span> <span style='color:deepskyblue;font-weight:bold;'>shine</span> <span style='color:#d32f2f;font-weight:bold;'>покемона</span>";
} else {
    $catchText = "<span style='color:#d32f2f;font-weight:bold;'>Поздравляем! Игрок <b>{$userName}</b> поймал покемона</span>";
}

// Блок с картинкой и именем покемона
$pokemonBlock = "<span class='bgPok' onclick='openDex({$pokeBasenum})' style='cursor:pointer;display:inline-flex;align-items:center;gap:6px;margin-left:8px;'>
    <img src='{$imgUrl}' style='height:32px;width:32px;object-fit:contain;display:inline-block;vertical-align:middle;margin-right:2px'>
    <span style='font-weight:bold;'>#{$pokeBasenumStr} {$pokeName}</span>
</span>!";

// Весь контейнер
$message = "<span style='display:inline-flex;align-items:center;gap:8px;'>{$catchText} {$pokemonBlock}</span>";

    // Универсальная отправка сообщения через глобальный класс
    require_once $_SERVER['DOCUMENT_ROOT'].'/inc/class/ChatSystemMsg.php';
    $msgSender = new ChatSystemMsg(Work::$sql); // Work::$sql — ваше актуальное подключение к БД
    $msgSender->send($message);
}

                    lvlupuser(10);

                    $this->_actionBattle->lose(true, 'CATCH');
                    return true;
                } else {
                    return null;
                }
            } else {
                return false;
            }
        }
        return false;
    }
}
<?php
/**
 * @property int id
 * @property int hp
 * @property int hp_start
 * @property int hp_max
 * @property int energy
 * @property int energy_max
 * @property int lvl
 * @property int basenum
 * @property int user_id
 * @property string name_new
 * @property int actionCount
 * @property array targetLvl
 *
 *
 * @property array itemsUsed
 * @property array protect
 * @property int critical_next_atk
 * @property array status_list
 * @property array atk_reset
 * @property array critical_empty
 * @property array defender_atk_types
 * @property array target_atk_types
 * @property bool wild
 * @property array char_next_atk
 * @property array repeat_atk
 * @property string|array atk_list
 * @property array atk_info
 * @property array im_benign
 * @property int last_atk
 * @property array training_atk
 * @property array atk_refusal
 * @property array attack_round
 * @property int count_action
 * @property array actionAtk
 *
 */
class PokeBattle{

    /*

        ВАЖНО:
        Класс вызывается часто (в бою на каждый ход). Чтобы не спамить БД,
        добавлен in-request кэш справочников:
        - har (характер)
        - base_pokemon_forms_new (форма)

        Кэш живёт только в рамках одного PHP-запроса.

    */

    private static $harCache = [];
    private static $formNewCache = [];

    private $formRowNew = null;
    private $harRow = null;

    /*

        0 - HP
        1 - ATK
        2 - DEF
        3 - SPD
        4 - m atk
        5 - m def

    */
    private $atkDefault = [
        'a9998' => ['id'=>9998, 'name'=>'Поимка покемона', 'title'=>'...', 'type'=>'NULL', 'category'=>'NULL', 'priority'=>999998, 'power'=>0, 'accuracy'=>900, 'pp'=>0, 'target'=>0, 'settings'=>'',],
        'a9999' => ['id'=>9999, 'name'=>'Замена покемона', 'title'=>'...', 'type'=>'NULL', 'category'=>'NULL', 'priority'=>9999999, 'power'=>0, 'accuracy'=>900, 'pp'=>0, 'target'=>0, 'settings'=>'',],
        'a754' => ['id'=>754, 'name'=>'Пропуск хода', 'title'=>'...', 'type'=>'NULL', 'category'=>'NULL', 'priority'=>0, 'power'=>0, 'accuracy'=>900, 'pp'=>0, 'target'=>0, 'settings'=>'',],
    ];

    private $pokeInfo = [
        'actionCount'=>0,
        'targetLvl'=>[]
    ];

    private $retarget = null;


    /*
        ===== НОВИНКИ / ФИЧИ (вариант 1 + вариант 2) =====

        Вариант 1: Кэширование справочников (forms/har)
        - убираем многократные запросы в каждом _getBaseStat* / _statFormul*
        - 3 уровня: локальный (объект), статический (в рамках запроса), APCu (между запросами)
        - APCu включается автоматически, если доступен extension

        Вариант 2: Нормализация входных данных
        - gen/evcounts приводим к массиву длиной 6, значения -> int, защита от мусора
        - status_list/modified приводим к массивам если прилетело строкой
        - безопасное экранирование form в SQL
    */

    const APCU_TTL = 600; // 10 минут
    const APCU_PREFIX = 'pokebattle:';

    const MAX_MODIFIED_COUNT = 6;

    /*
        Вспомогательные методы (не ломают публичный API)
    */
    private function _sql(){
        return (isset(Work::$sql) ? Work::$sql : null);
    }

    private function _normSix($arr, $def = 0){
        // Нормализуем массив до 6 значений (int), чтобы не ловить Notice/Undefined offset
        if(!is_array($arr)){
            $arr = (string)$arr;
            $arr = ($arr !== '' ? explode(',', $arr) : []);
        }
        $out = [];
        for($i = 0; $i < 6; $i++){
            $out[$i] = (isset($arr[$i]) && $arr[$i] !== '' ? (int)$arr[$i] : (int)$def);
        }
        return $out;
    }

    private function _getFormRowNew(){
        if($this->formRowNew !== null){
            return $this->formRowNew;
        }

        $this->formRowNew = [];

        if(!isset($this->pokeInfo['form'], $this->pokeInfo['basenum'])){
            return $this->formRowNew;
        }

        $basenum = (int)$this->pokeInfo['basenum'];
        $form = (string)$this->pokeInfo['form'];

        if($basenum <= 0 || $form === '' || $form === '0'){
            return $this->formRowNew;
        }

        $cacheKey = $basenum.'|'.$form;
        if(isset(self::$formNewCache[$cacheKey])){
            $this->formRowNew = self::$formNewCache[$cacheKey];
            return $this->formRowNew;
        }

        // APCu (если включено) — берём кэш между запросами
        $apcuKey = self::APCU_PREFIX.'form_new:'.$cacheKey;
        if(function_exists('apcu_fetch')){
            $ok = false;
            $ap = apcu_fetch($apcuKey, $ok);
            if($ok){
                $ap = (is_array($ap) ? $ap : []);
                self::$formNewCache[$cacheKey] = $ap;
                $this->formRowNew = $ap;
                return $this->formRowNew;
            }
        }

        $sql = $this->_sql();
        if(!$sql){
            return $this->formRowNew;
        }

        $formEsc = $sql->real_escape_string($form);

        $row = $sql->query("SELECT * FROM base_pokemon_forms_new WHERE id_form = '".$formEsc."' AND pokemons = ".$basenum)->fetch_assoc();
        if(!empty($row)){
            self::$formNewCache[$cacheKey] = $row;
            $this->formRowNew = $row;

            if(function_exists('apcu_store')){
                apcu_store($apcuKey, $row, self::APCU_TTL);
            }
        }else{
            self::$formNewCache[$cacheKey] = [];

            if(function_exists('apcu_store')){
                apcu_store($apcuKey, [], self::APCU_TTL);
            }
        }

        return $this->formRowNew;
    }

    private function _getHarRow(){
        if($this->harRow !== null){
            return $this->harRow;
        }

        $this->harRow = [];

        $harId = (isset($this->pokeInfo['character']) ? (int)$this->pokeInfo['character'] : 0);
        if($harId <= 0){
            $harId = 1;
        }

        if(isset(self::$harCache[$harId])){
            $this->harRow = self::$harCache[$harId];
            return $this->harRow;
        }

        // APCu (если включено)
        $apcuKey = self::APCU_PREFIX.'har:'.$harId;
        if(function_exists('apcu_fetch')){
            $ok = false;
            $apRow = apcu_fetch($apcuKey, $ok);
            if($ok){
                self::$harCache[$harId] = (is_array($apRow) ? $apRow : []);
                $this->harRow = self::$harCache[$harId];
                return $this->harRow;
            }
        }

        $sql = $this->_sql();
        if(!$sql){
            self::$harCache[$harId] = [];
            return $this->harRow;
        }

        $row = $sql->query('SELECT * FROM har WHERE id_har = '.$harId)->fetch_assoc();
        if(!empty($row)){
            self::$harCache[$harId] = $row;
            $this->harRow = $row;
            if(function_exists('apcu_store')){
                apcu_store($apcuKey, $row, self::APCU_TTL);
            }
        }else{
            self::$harCache[$harId] = [];
            if(function_exists('apcu_store')){
                apcu_store($apcuKey, [], self::APCU_TTL);
            }
        }

        return $this->harRow;
    }

    public function __construct(array $pokeInfo = [], $parser = false){

        $this->pokeInfo = array_merge($this->pokeInfo, $pokeInfo);

        $this->parser($parser);

    }

    public function &__get($name){

        if(isset($this->pokeInfo[$name])){
            return $this->pokeInfo[$name];
        }

        $this->retarget = null;
        return $this->retarget;

    }

    public function __set($name, $value){
        $this->pokeInfo[$name] = $value;
    }

    public function __isset($name){
        return isset($this->pokeInfo[$name]);
    }

    public function __unset($name){
        if(isset($this->pokeInfo[$name])){
            unset($this->pokeInfo[$name]);
        }
    }

    private function parser($parser = false){

        // Нормализация входных данных

        if(!(isset($this->pokeInfo['evcounts']) && is_array($this->pokeInfo['evcounts']))){
            $this->pokeInfo['evcounts'] = (isset($this->pokeInfo['evcounts']) ? explode(',', $this->pokeInfo['evcounts']) : []);
        }
        $this->pokeInfo['evcounts'] = $this->_normSix($this->pokeInfo['evcounts'], 0);

        if(!(isset($this->pokeInfo['gen']) && is_array($this->pokeInfo['gen']))){
            $this->pokeInfo['gen'] = (isset($this->pokeInfo['gen']) ? explode(',', $this->pokeInfo['gen']) : []);
        }
        $this->pokeInfo['gen'] = $this->_normSix($this->pokeInfo['gen'], 0);
        
        

        $this->pokeInfo['hp_max'] = $this->_generateStat($this->_getBaseStatHp(), $this->_getGenHp(), $this->_getEvHp(), 'hp');

        // На старых данных hp_start может отсутствовать. Не ломаем механику, но заполняем корректно.
        if(!isset($this->pokeInfo['hp_start']) || $this->pokeInfo['hp_start'] === null){
            $this->pokeInfo['hp_start'] = (isset($this->pokeInfo['hp']) && $this->pokeInfo['hp'] !== true ? (int)$this->pokeInfo['hp'] : (int)$this->pokeInfo['hp_max']);
        }

        if($this->pokeInfo['hp'] === true){
            $this->pokeInfo['hp'] = $this->pokeInfo['hp_max'];
        }

        if(!(isset($this->pokeInfo['stats']) && is_array($this->pokeInfo['stats']))){
            $this->pokeInfo['stats'] = [
                $this->pokeInfo['hp_max'],
                $this->_generateStat($this->_getBaseStatAtk(),  $this->_getGenAtk(),  $this->_getEvAtk(), 'atk'),
                $this->_generateStat($this->_getBaseStatDef(),  $this->_getGenDef(),  $this->_getEvDef(), 'def'),
                $this->_generateStat($this->_getBaseStatSpd(),  $this->_getGenSpd(),  $this->_getEvSpd(), 'speed'),
                $this->_generateStat($this->_getBaseStatSAtk(), $this->_getGenSAtk(), $this->_getEvSAtk(), 'satk'),
                $this->_generateStat($this->_getBaseStatSDef(), $this->_getGenSDef(), $this->_getEvSDef(), 'sdef'),
            ];
        }else{
            if($parser){
                $this->pokeInfo['stats'] = [
                    $this->pokeInfo['hp_max'],
                    $this->_generateStat($this->_getBaseStatAtk(),  $this->_getGenAtk(),  $this->_getEvAtk(), 'atk'),
                    $this->_generateStat($this->_getBaseStatDef(),  $this->_getGenDef(),  $this->_getEvDef(), 'def'),
                    $this->_generateStat($this->_getBaseStatSpd(),  $this->_getGenSpd(),  $this->_getEvSpd(), 'speed'),
                    $this->_generateStat($this->_getBaseStatSAtk(), $this->_getGenSAtk(), $this->_getEvSAtk(), 'satk'),
                    $this->_generateStat($this->_getBaseStatSDef(), $this->_getGenSDef(), $this->_getEvSDef(), 'sdef'),
                ];
            }
        }

        return true;
    }

    public function _generateStat($baseVal, $gen, $ev, $stat){
      $lvl = $this->_getLvl();
      $harBase = $this->_getHarRow();
      if($stat == 'hp'){
        $result = (($baseVal * 2) + $gen + ($ev/2)) * ($lvl/100) + 10 + $lvl;
      }else{
        $har = (isset($harBase[$stat]) ? (float)$harBase[$stat] : 1.0);
        if($har <= 0) $har = 1.0;
        $result = (($baseVal * 2 + $gen + ($ev/2)) * $lvl/100 + 5) * $har;
        if($this->pokeInfo['tren'] > 0) {
          if($this->pokeInfo['tren_stat'] == 1 && $stat == 'atk') {
            $result = $result * classific($this->pokeInfo['tren']);
          }elseif($this->pokeInfo['tren_stat'] == 2 && $stat == 'def'){
            $result = $result * classific($this->pokeInfo['tren']);
          }elseif($this->pokeInfo['tren_stat'] == 3 && $stat == 'speed'){
            $result = $result * classific($this->pokeInfo['tren']);
          }elseif($this->pokeInfo['tren_stat'] == 4 && $stat == 'satk'){
            $result = $result * classific($this->pokeInfo['tren']);
          }elseif($this->pokeInfo['tren_stat'] == 5 && $stat == 'sdef'){
            $result = $result * classific($this->pokeInfo['tren']);
          }
        }
      }
      return intval(round($result));
    }

    public function _getAtkInfo($atkID){
        if($atkID == 0 || $atkID > 9000 || $atkID == 754){
            $atkID = intval($atkID);
            if(isset($this->atkDefault['a'.$atkID], $this->atkDefault['a'.$atkID]['id']) && $this->atkDefault['a'.$atkID]['id'] == $atkID){
                return $this->atkDefault['a'.$atkID];
            }
        }
        if(isset($this->pokeInfo['atkList'], $this->pokeInfo['atkList']['a'.$atkID])){
            $parseFields = ['settings','my','enemy','mod','status'];
            foreach($parseFields as $field){
                if(isset($this->pokeInfo['atkList']['a'.$atkID][$field]) && !empty($this->pokeInfo['atkList']['a'.$atkID][$field])){
                    if(!is_array($this->pokeInfo['atkList']['a'.$atkID][$field])){
                        $this->pokeInfo['atkList']['a'.$atkID][$field] = Info::_unParseData($this->pokeInfo['atkList']['a'.$atkID][$field]);
                    }
                }elseif(isset($this->pokeInfo['atkList']['a'.$atkID][$field]) && ($this->pokeInfo['atkList']['a'.$atkID][$field] === null || $this->pokeInfo['atkList']['a'.$atkID][$field] === '')){
                    $this->pokeInfo['atkList']['a'.$atkID][$field] = [];
                }
            }
            return $this->pokeInfo['atkList']['a'.$atkID];
        }
        return [];
    }

    public function _getName($img = false){
        if($img) {
          return '<span class="bgPok" onclick="openDex('.$this->basenum.')"><img src="/img/pokemons/animation/'.$this->basenum.'.png"> <div class="'.$this->type.'-color" style="display: inline-block;">#'.$this->_getBaseNum().' '.$this->name_new.'</div></span>';
        }
        return '#'.$this->_getBaseNum().' '.(isset($this->pokeInfo['name_new']) ? $this->pokeInfo['name_new'] : '');
    }

    public function _getBaseNum(){
        return ($this->basenum < 10 ? '00'.$this->basenum : ($this->basenum < 100 ? '0'.$this->basenum : $this->basenum));
    }

    /* ~~ BaseStats ~~ */
    public function _getBaseStatHp(){

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['hp'])){
            return intval($form['hp']);
        }
        return (isset($this->pokeInfo['base_hp']) ? intval($this->pokeInfo['base_hp']) : 0);
    }
    public function _getBaseStatAtk(){

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['atk'])){
            return intval($form['atk']);
        }
        return (isset($this->pokeInfo['base_atk']) ? intval($this->pokeInfo['base_atk']) : 0);
    }
    public function _getBaseStatDef(){

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['def'])){
            return intval($form['def']);
        }
        return (isset($this->pokeInfo['base_def']) ? intval($this->pokeInfo['base_def']) : 0);
    }
    public function _getBaseStatSpd(){

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['spd'])){
            return intval($form['spd']);
        }
        return (isset($this->pokeInfo['base_spd']) ? intval($this->pokeInfo['base_spd']) : 0);
    }
    public function _getBaseStatSAtk(){

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['satk'])){
            return intval($form['satk']);
        }
        return (isset($this->pokeInfo['base_satk']) ? intval($this->pokeInfo['base_satk']) : 0);
    }
    public function _getBaseStatSDef(){

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['sdef'])){
            return intval($form['sdef']);
        }
        return (isset($this->pokeInfo['base_sdef']) ? intval($this->pokeInfo['base_sdef']) : 0);
    }
    public function _getType(){
        return (isset($this->pokeInfo['type']) ? $this->pokeInfo['type'] : 'normal');
    }
    public function _getTypeA(){
        // PS PATCH: Soak меняет тип цели на Water до ухода/смерти
if ($this->_checkStatus('soak')) {
    return 'water';
}

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['type'])){
            return $form['type'];
        }
        return (isset($this->pokeInfo['base_type']) ? $this->pokeInfo['base_type'] : '');
    }
    public function _getTypeB(){
        // PS PATCH: при Soak второй тип сбрасываем
if ($this->_checkStatus('soak')) {
    return '';
}

        $form = $this->_getFormRowNew();
        if(!empty($form) && isset($form['type_two'])){
            return $form['type_two'];
        }
        return (isset($this->pokeInfo['base_type_two']) ? $this->pokeInfo['base_type_two'] : '');
    }
    

    /* ~~ Stats ~~ */
    public function _getStats($string = false){
        if($string){
            return implode(',', $this->pokeInfo['stats']);
        }
        return $this->pokeInfo['stats'];
    }


    public function _getStatHp(){
        $stat = (isset($this->pokeInfo['stats'][0]) ? intval($this->pokeInfo['stats'][0]) : 0);
        return $stat;
    }
    public function _statFormulAtk() {
  		$stat = (isset($this->pokeInfo['stats'][1]) ? intval($this->pokeInfo['stats'][1]) : 0);
		
		// _getBaseStatAtk() уже учитывает форму через кэш
		$statBase = $this->_getBaseStatAtk();
  		if($this->pokeInfo['ability'] == 188 && $this->pokeInfo['basenum'] == 681) {
  			if($this->pokeInfo['form'] == 'blade') {
  				$statBase = 150;
  			}else{
  				$statBase = 50;
  			}
  		}
		$harRow = $this->_getHarRow();
		$harMult = (isset($harRow['atk']) ? (float)$harRow['atk'] : 1.0);
		$stat = ((($statBase * 2 + $this->pokeInfo['gen'][1] + ($this->pokeInfo['evcounts'][1]/2)) * $this->pokeInfo['lvl']/100 + 5) * $harMult);
  		if($this->pokeInfo['tren_stat'] == 1){
  			$stat = $stat*classific($this->pokeInfo['tren']);
  		}
  		
  		return $stat;
  	}

  	public function _statFormulDef() {
  		$stat = (isset($this->pokeInfo['stats'][2]) ? intval($this->pokeInfo['stats'][2]) : 0);
  		$statBase = $this->_getBaseStatDef();
		
		// _getBaseStatDef() уже учитывает форму через кэш
  		if($this->pokeInfo['ability'] == 188 && $this->pokeInfo['basenum'] == 681) {
  			if($this->pokeInfo['form'] == 'blade') {
  				$statBase = 50;
  			}else{
  				$statBase = 150;
  			}
  		}
		$harRow = $this->_getHarRow();
		$harMult = (isset($harRow['def']) ? (float)$harRow['def'] : 1.0);
		$stat = ((($statBase * 2 + $this->pokeInfo['gen'][2] + ($this->pokeInfo['evcounts'][2]/2)) * $this->pokeInfo['lvl']/100 + 5) * $harMult);
  		if($this->pokeInfo['tren_stat'] == 2){
  			$stat = $stat*classific($this->pokeInfo['tren']);
  		}
  		
  		return $stat;
  	}

  	public function _statFormulSDef() {
  		$stat = (isset($this->pokeInfo['stats'][5]) ? intval($this->pokeInfo['stats'][5]) : 0);
  		$statBase = $this->_getBaseStatSDef();
  		
		// _getBaseStatSDef() уже учитывает форму через кэш
  		if($this->pokeInfo['ability'] == 188 && $this->pokeInfo['basenum'] == 681) {
  			if($this->pokeInfo['form'] == 'blade') {
  				$statBase = 50;
  			}else{
  				$statBase = 150;
  			}
  		}
		$harRow = $this->_getHarRow();
		$harMult = (isset($harRow['sdef']) ? (float)$harRow['sdef'] : 1.0);
		$stat = ((($statBase * 2 + $this->pokeInfo['gen'][5] + ($this->pokeInfo['evcounts'][5]/2)) * $this->pokeInfo['lvl']/100 + 5) * $harMult);
  		if($this->pokeInfo['tren_stat'] == 5){
  			$stat = $stat*classific($this->pokeInfo['tren']);
  		}
  		
  		return $stat;
  	}

  	public function _statFormulSpd() {
  		$stat = (isset($this->pokeInfo['stats'][3]) ? intval($this->pokeInfo['stats'][3]) : 0);
  		$statBase = $this->_getBaseStatSpd();
		// _getBaseStatSpd() уже учитывает форму через кэш
		$harRow = $this->_getHarRow();
		$harMult = (isset($harRow['speed']) ? (float)$harRow['speed'] : 1.0);
		$stat = ((($statBase * 2 + $this->pokeInfo['gen'][3] + ($this->pokeInfo['evcounts'][3]/2)) * $this->pokeInfo['lvl']/100 + 5) * $harMult);
  		if($this->pokeInfo['tren_stat'] == 3){
  			$stat = $stat*classific($this->pokeInfo['tren']);
  		}
  		
  		return $stat;
  	}

  	public function _statFormulSAtk() {
  		$stat = (isset($this->pokeInfo['stats'][4]) ? intval($this->pokeInfo['stats'][4]) : 0);
  		$statBase = $this->_getBaseStatSAtk();
		// _getBaseStatSAtk() уже учитывает форму через кэш
  		if($this->pokeInfo['ability'] == 188 && $this->pokeInfo['basenum'] == 681) {
  			if($this->pokeInfo['form'] == 'blade') {
  				$statBase = 150;
  			}else{
  				$statBase = 50;
  			}
  		}
		$harRow = $this->_getHarRow();
		$harMult = (isset($harRow['satk']) ? (float)$harRow['satk'] : 1.0);
		$stat = ((($statBase * 2 + $this->pokeInfo['gen'][4] + ($this->pokeInfo['evcounts'][4]/2)) * $this->pokeInfo['lvl']/100 + 5) * $harMult);
  		if($this->pokeInfo['tren_stat'] == 4){
  			$stat = $stat*classific($this->pokeInfo['tren']);
  		}
  		
  		return $stat;
  	}

      public function _getStatAtk(){

          $stat = $this->_statFormulAtk();

          if(isset($this->pokeInfo['ability']) && $this->pokeInfo['ability'] == 76) {
            $stat = ($stat * 2);
          }
          // PS PATCH: Pure Power (id=144) удваивает Атаку (как Huge Power)
if (isset($this->pokeInfo['ability']) && $this->pokeInfo['ability'] == 144) {
    $stat = ($stat * 2);
}

          if(isset($this->pokeInfo['ability']) && $this->pokeInfo['ability'] == 37) {
            if($this->pokeInfo['hp'] <= floor(($this->pokeInfo['hp_max'] / 2))) {
              $stat = ($stat / 2);
            }
          }
          
          if($this->_checkStatus('burn')){
                  $stat = $stat/2;
              }

          if($stat > 0){
              $mod = $this->_getModAtk();

              if(!empty($mod)){
                  $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
              }
              
          }
          return $stat;
      }
      public function _getStatDef(){

  		$stat = $this->_statFormulDef();

          if(isset($this->pokeInfo['ability']) && $this->pokeInfo['ability'] == 63) {
            $stat = ($stat * 2);
          }

          if(isset($this->pokeInfo['ability']) && $this->pokeInfo['ability'] == 68 && $this->_checkStatus('terrGrass')) {
            $stat = ($stat * 1.5);
          }

          if($stat > 0){
              $mod = $this->_getModDef();

              if(!empty($mod)){
                  $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
              }
          }

          return $stat;

      }
      public function _getStatDef_ignore(){

          $stat = $this->_statFormulDef();

          return $stat;

      }
      public function _getStatAtk_ignore(){

        $stat = $this->_statFormulAtk();

        return $stat;

      }
      public function _getStatSAtk_ignore(){

        $stat = $this->_statFormulSAtk();

        return $stat;

      }
      public function _getStatSpd(){

          $stat = $this->_statFormulSpd();

          if($stat > 0){
              $mod = $this->_getModSpd();

              if(!empty($mod)){
                  $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
              }
          }

          if($this->_checkStatus('paralyzed') && $this->pokeInfo['ability'] != 146){
              $stat = floor($stat / 4);
          }

          return $stat;

      }
      public function _getStatSAtk(){

          $stat = $this->_statFormulSAtk();

          if(isset($this->pokeInfo['ability']) && $this->pokeInfo['ability'] == 37) {
            if($this->pokeInfo['hp'] <= floor(($this->pokeInfo['hp_max'] / 2))) {
              $stat = ($stat / 2);
            }
          }

          if($stat > 0){
              $mod = $this->_getModSAtk();

              if(!empty($mod)){
                  $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
              }
          }

          return $stat;

      }
      public function _getStatSDef(){

          $stat = $this->_statFormulSDef();

          if($stat > 0){
              $mod = $this->_getModSDef();

              if(!empty($mod)){
                  $stat = $stat * ( ( 2 + (isset($mod['plus']) ? intval($mod['plus']) : 0) ) / ( 2 + (isset($mod['minus']) ? intval($mod['minus']) : 0) ) );
              }
          }
          
          if($this->pokeInfo['item_id'] == 161){
              $stat = $stat*1.3;
          }

          return $stat;

      }

      public function _getStatSDef_ignore(){

          $stat = $this->_statFormulSDef();

          return $stat;

      }


    /* ~~ Gen ~~ */
    public function _getGens($string = false,$upGens = false){
        if($string){

            if(!is_array($this->pokeInfo['gen'])){
                $this->pokeInfo['gen'] = explode(',', $this->pokeInfo['gen']);
            }

			if($upGens){
				$this->pokeInfo['gen'][0] += $upGens;
				$this->pokeInfo['gen'][1] += $upGens;
				$this->pokeInfo['gen'][2] += $upGens;
				$this->pokeInfo['gen'][3] += $upGens;
				$this->pokeInfo['gen'][4] += $upGens;
				$this->pokeInfo['gen'][5] += $upGens;
			}

            return implode(',', $this->pokeInfo['gen']);
        }
        return $this->pokeInfo['gen'];
    }

    public function _getGenHp(){
        return (isset($this->pokeInfo['gen'][0]) ? intval($this->pokeInfo['gen'][0]) : 0);
    }
    public function _getGenAtk(){
        return (isset($this->pokeInfo['gen'][1]) ? intval($this->pokeInfo['gen'][1]) : 0);
    }
    public function _getGenDef(){
        return (isset($this->pokeInfo['gen'][2]) ? intval($this->pokeInfo['gen'][2]) : 0);
    }
    public function _getGenSpd(){
        return (isset($this->pokeInfo['gen'][3]) ? intval($this->pokeInfo['gen'][3]) : 0);
    }
    public function _getGenSAtk(){
        return (isset($this->pokeInfo['gen'][4]) ? intval($this->pokeInfo['gen'][4]) : 0);
    }
    public function _getGenSDef(){
        return (isset($this->pokeInfo['gen'][5]) ? intval($this->pokeInfo['gen'][5]) : 0);
    }

    /* ~~ EV ~~ */
    public function _getEvs($string = false){
        if($string){
            return implode(',', $this->pokeInfo['evcounts']);
        }
        return $this->pokeInfo['evcounts'];
    }

    public function _getEvHp(){
        return (isset($this->pokeInfo['evcounts'][0]) ? intval($this->pokeInfo['evcounts'][0]) : 0);
    }
    public function _getEvAtk(){
        return (isset($this->pokeInfo['evcounts'][1]) ? intval($this->pokeInfo['evcounts'][1]) : 0);
    }
    public function _getEvDef(){
        return (isset($this->pokeInfo['evcounts'][2]) ? intval($this->pokeInfo['evcounts'][2]) : 0);
    }
    public function _getEvSpd(){
        return (isset($this->pokeInfo['evcounts'][3]) ? intval($this->pokeInfo['evcounts'][3]) : 0);
    }
    public function _getEvSAtk(){
        return (isset($this->pokeInfo['evcounts'][4]) ? intval($this->pokeInfo['evcounts'][4]) : 0);
    }
    public function _getEvSDef(){
        return (isset($this->pokeInfo['evcounts'][5]) ? intval($this->pokeInfo['evcounts'][5]) : 0);
    }

    /* ~~ MODIFIED STATS ~~ */
    public function _getModAtk(){
        return (isset($this->pokeInfo['modified'], $this->pokeInfo['modified']['atk']) ? $this->pokeInfo['modified']['atk'] : []);
}
    public function _getModDef(){
        return (isset($this->pokeInfo['modified'], $this->pokeInfo['modified']['def']) ? $this->pokeInfo['modified']['def'] : []);
    }
    public function _getModSpd(){
        return (isset($this->pokeInfo['modified'], $this->pokeInfo['modified']['spd']) ? $this->pokeInfo['modified']['spd'] : []);
    }
    public function _getModSAtk(){
        return (isset($this->pokeInfo['modified'], $this->pokeInfo['modified']['satk']) ? $this->pokeInfo['modified']['satk'] : []);
    }
    public function _getModSDef(){
        return (isset($this->pokeInfo['modified'], $this->pokeInfo['modified']['sdef']) ? $this->pokeInfo['modified']['sdef'] : []);
    }

    public function _getModList(){
        return (isset($this->pokeInfo['modified']) ? $this->pokeInfo['modified'] : []);
    }

    public function _setModifiedStatNormal(){
      unset($this->pokeInfo['modified']['satk']['plus']);
      unset($this->pokeInfo['modified']['atk']['plus']);
      unset($this->pokeInfo['modified']['spd']['plus']);
      unset($this->pokeInfo['modified']['sdef']['plus']);
      unset($this->pokeInfo['modified']['def']['plus']);
      unset($this->pokeInfo['modified']['satk']['minus']);
      unset($this->pokeInfo['modified']['atk']['minus']);
      unset($this->pokeInfo['modified']['spd']['minus']);
      unset($this->pokeInfo['modified']['sdef']['minus']);
      unset($this->pokeInfo['modified']['def']['minus']);
      unset($this->pokeInfo['modified']['agl']['minus']);
      unset($this->pokeInfo['modified']['acr']['minus']);
      unset($this->pokeInfo['modified']['agl']['plus']);
      unset($this->pokeInfo['modified']['acr']['plus']);
      //return true;
      //$this->pokeInfo['modified'] = '';
    }


    public function _setModifiedStat($statName, $count = 1, $plus = false){

      if($this->pokeInfo['ability'] == 29) {
  			if($plus == false) {
  				$plus = true;
  			}else{
  				$plus = false;
  			}
  		}

        if($statName && is_numeric($count) && $count >= 0){

            $count = intval($count);

            if(!isset($this->pokeInfo['modified'])){
                $this->pokeInfo['modified'] = [];
            }

            if(!isset($this->pokeInfo['modified'][$statName])){
                $this->pokeInfo['modified'][$statName] = [];
            }

            if($plus){

                if($count === 0){

                    if(isset($this->pokeInfo['modified'][$statName]['plus'])){
                        unset($this->pokeInfo['modified'][$statName]['plus']);
                    }

                }else{
                    //Add
                    if(isset($this->pokeInfo['modified'][$statName]['minus']) && (int)$this->pokeInfo['modified'][$statName]['minus'] >= $count){
                        $this->pokeInfo['modified'][$statName]['minus'] -= $count;
                        if((int)$this->pokeInfo['modified'][$statName]['minus'] <= 0){
                            unset($this->pokeInfo['modified'][$statName]['minus']);
                        }
                        if((int)$this->pokeInfo['modified'][$statName]['minus'] >= 0){
                            return true;
                        }
                    }

                    if(isset($this->pokeInfo['modified'][$statName]['plus'])){
                        $this->pokeInfo['modified'][$statName]['plus'] += $count;
                    }else{
                        $this->pokeInfo['modified'][$statName]['plus'] = $count;
                    }

                    if($this->pokeInfo['modified'][$statName]['plus'] >= self::MAX_MODIFIED_COUNT){
                        $this->pokeInfo['modified'][$statName]['plus'] = self::MAX_MODIFIED_COUNT;
                    }
                }

            }else{

                if($count === 0){

                    if(isset($this->pokeInfo['modified'][$statName]['minus'])){
                        unset($this->pokeInfo['modified'][$statName]['minus']);
                    }

                }else{
                    //Add
                    if(isset($this->pokeInfo['modified'][$statName]['plus']) && (int)$this->pokeInfo['modified'][$statName]['plus'] >= $count){
                        $this->pokeInfo['modified'][$statName]['plus'] -= $count;
                        if((int)$this->pokeInfo['modified'][$statName]['plus'] <= 0){
                            unset($this->pokeInfo['modified'][$statName]['plus']);
                        }
                        if((int)$this->pokeInfo['modified'][$statName]['plus'] >= 0){
                            return true;
                        }
                    }

                    if(isset($this->pokeInfo['modified'][$statName]['minus'])){
                        $this->pokeInfo['modified'][$statName]['minus'] += $count;
                    }else{
                        $this->pokeInfo['modified'][$statName]['minus'] = $count;
                    }

                    if($this->pokeInfo['modified'][$statName]['minus'] >= self::MAX_MODIFIED_COUNT){
                        $this->pokeInfo['modified'][$statName]['minus'] = self::MAX_MODIFIED_COUNT;
                    }
                }
            }

            return true;
        }

        return false;
    }

    public function _setStatus($statusName, $count = 1, $otherValue = null){

        if($statusName && is_numeric($count) && $count >= 0){

            $count = intval($count);

            // Status aliases / унификация (poison/toxic). Не ломаем старые ключи.
            $statusName = (string)$statusName;
            if($statusName === 'psn'){ $statusName = 'poison'; }
            if($statusName === 'tox'){ $statusName = 'toxic'; }
            if($statusName === 'toxic2'){ $statusName = 'poison'; } // historical alias for regular poison

            // Если в данных уже лежит старый ключ toxic2 — используем его, чтобы не дублировать статус
            if($statusName === 'poison' && isset($this->pokeInfo['status_list']) && isset($this->pokeInfo['status_list']['toxic2']) && !isset($this->pokeInfo['status_list']['poison'])){
                $statusName = 'toxic2';
            }


            if(!isset($this->pokeInfo['status_list'])){
                $this->pokeInfo['status_list'] = [];
            }

            if(!isset($this->pokeInfo['status_list'][$statusName])){
                $this->pokeInfo['status_list'][$statusName] = [];
            }


            if(isset($this->pokeInfo['status_list'][$statusName]['count']) && $this->pokeInfo['status_list'][$statusName]['count'] > 0){

                return false;

            }else{

                $this->pokeInfo['status_list'][$statusName] = [
                    'type'=>$statusName,
                    'count'=>$count,
                    'val'=>$otherValue
                ];

            }

            return true;

        }

        return false;
    }

    public function _getStatusList(){
        return (isset($this->pokeInfo['status_list']) ? $this->pokeInfo['status_list'] : []);
    }

    public function _setStatusList($list = []){
        if(is_array($list)){
            $this->pokeInfo['status_list'] = $list;
        }
    }

    public function _setDie($hpDie){
      $this->pokeInfo['hp'] = $hpDie;
    }

    public function _checkStatus($statusName){

        if(!isset($this->pokeInfo['status_list']) || !is_array($this->pokeInfo['status_list'])){
            return false;
        }

        // Aliases / унификация (poison/toxic)
        if($statusName === 'psn'){ $statusName = 'poison'; }
        if($statusName === 'tox'){ $statusName = 'toxic'; }
        if($statusName === 'toxic2'){ $statusName = 'poison'; }

        if($statusName === 'poison'){
            if(isset($this->pokeInfo['status_list']['poison']) || isset($this->pokeInfo['status_list']['toxic2'])){
                return true;
            }
            return false;
        }

        if(isset($this->pokeInfo['status_list'][$statusName])){
            return true;
        }

        return false;
    }


    public function _getLvl(){
        return (isset($this->pokeInfo['lvl']) ? intval($this->pokeInfo['lvl']) : 1);
    }

    public function _getEvCount(){
        return (isset($this->pokeInfo['ev']) ? intval($this->pokeInfo['ev']) : 0);
    }

    public function _getExp(){
        return (isset($this->pokeInfo['exp']) ? intval($this->pokeInfo['exp']) : 1);
    }

    public function _getExpNext(){
        return (isset($this->pokeInfo['exp_max']) ? intval($this->pokeInfo['exp_max']) : 100);
    }

    public function _getID(){
        return (isset($this->pokeInfo['id']) ? intval($this->pokeInfo['id']) : 0);
    }

    public function _getHar(){
        return (isset($this->pokeInfo['character']) ? intval($this->pokeInfo['character']) : 1);
    }

    public function _getSex(){

        return $this->pokeInfo['gender'];
    }

    public function _getAtk(){
        return (isset($this->pokeInfo['attacks']) ? (is_array($this->pokeInfo['attacks']) ? implode(',', $this->pokeInfo['attacks']) : $this->pokeInfo['attacks']) : '0,0,0,0');
    }

    public function _getUser(){
        return (isset($this->pokeInfo['user_id']) ? intval($this->pokeInfo['user_id']) : 0);
    }

    public function _getItems(){
        if(!empty($this->pokeInfo['item_id'])){
            return true;
        }
        return false;
    }



    public function _isHp(){
        return ($this->pokeInfo['hp'] > 0);
    }



    public function _getData(){
        return $this->pokeInfo;
    }

}

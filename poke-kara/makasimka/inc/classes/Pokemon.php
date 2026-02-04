<?php
class Pokemon{
    /** @var $_ajax Ajax */
    private $_ajax;
    private $data = [];
    private $base = [];
    private $position_stats = ['hp', 'atk', 'def', 'satk', 'sdef', 'spd'];  // [hp, atk, def, satk, sdef, spd]
    private $update = [];

    const TABLE_NAME = 'users_pokemon';
    const TABLE_NAME_INF = 'base_pokemon';

    public function __construct(Ajax $ajax, $pokemon_search, $create = false){
        if(!empty($pokemon_search)){
            $this->_ajax = $ajax;
            if($create === true){
                if($this->create($pokemon_search)){
                    return $this;
                }
            }else{
                if($this->parser($pokemon_search)){
                    if(!empty($this->data) && !empty($this->data['id'])){
                        $this->parse_data();
                        return $this;
                    }
                }
            }
        }
        return $this->_ajax->_notice->_setError('ERROR_POKE_NO');
    }
    private function parser($pokemon_search){
        if(!empty($pokemon_search)){
            $arr = [];
            if(is_numeric($pokemon_search)){
                $arr['id'] = intval($pokemon_search);
            }elseif(is_array($pokemon_search)){
                $arr = array_merge($arr, $pokemon_search);
            }
            $this->data = $this->_ajax->_db->sel(self::TABLE_NAME, '*', $arr, 1)->oneLine();
            if(!empty($this->data['basenum'])){
                $this->base = $this->_ajax->_db->sel(self::TABLE_NAME_INF, '*', $this->data['basenum'], 1)->oneLine();
                if(!empty($this->base['id'])){
                    return $this->data;
                }
            }
        }
        return false;
    }
    private function parse_data(){
        if(isset($this->data['stats'])){
            $stats = (!is_array($this->data['stats']) ? explode(',', $this->data['stats']) : $this->data['stats']);
            if(isset($stats[0])){
                $this->data['stats'] = $stats;
                for($i = 0; $i <= sizeof($stats); $i++){
                    if(isset($this->position_stats[$i]) && isset($stats[$i])){
                        $this->data['inf_'.$this->position_stats[$i]] = intval($stats[$i]);
                    }
                }
            }
        }
        unset($stats, $i);
        if(isset($this->data['ev'])){
            $stats = (!is_array($this->data['ev']) ? explode(',', $this->data['ev']) : $this->data['ev']);
            if(isset($stats[0])){
                $this->data['ev'] = $stats;
                for($i = 0; $i <= sizeof($stats); $i++){
                    if(isset($this->position_stats[$i]) && isset($stats[$i])){
                        $this->data['ev_'.$this->position_stats[$i]] = intval($stats[$i]);
                    }
                }
            }
        }
        unset($stats, $i);
		if(isset($this->data['character'])){
			$har['1'] = "Веселый";
			$har['2'] = "Выносливый";
			$har['3'] = "Застенчивый";
			$har['4'] = "Кроткий";
			$har['5'] = "Мирный";
			$har['6'] = "Мягкий";
			$har['7'] = "Наглый";
			$har['8'] = "Наивный";
			$har['9'] = "Нахальный";
			$har['10'] = "Нежный";
			$har['11'] = "Непослушный";
			$har['12'] = "Непреклонный";
			$har['13'] = "Обычный";
			$har['14'] = "Одинокий";
			$har['15'] = "Озорной";
			$har['16'] = "Осторожный";
			$har['17'] = "Поспешный";
			$har['18'] = "Причудливый";
			$har['19'] = "Распущенный";
			$har['20'] = "Робкий";
			$har['21'] = "Серьезный";
			$har['22'] = "Скромный";
			$har['23'] = "Смелый";
			$har['24'] = "Спокойный";
			$har['25'] = "Стремительный";
			$har['26'] = "Тихий";
			$this->data['character'] = $har[$this->data['character']];

        }
        if(isset($this->data['gen'])){
            $stats = (!is_array($this->data['gen']) ? explode(',', $this->data['gen']) : $this->data['gen']);
            if(isset($stats[0])){
                $this->data['gen'] = $stats;
                for($i = 0; $i <= sizeof($stats); $i++){
                    if(isset($this->position_stats[$i]) && isset($stats[$i])){
                        $this->data['gen_'.$this->position_stats[$i]] = intval($stats[$i]);
                    }
                }
            }
        }
        unset($stats, $i);
		if(isset($this->data['atk'])){
            $stats = (!is_array($this->data['atk']) ? explode(',', $this->data['atk']) : $this->data['atk']);
            if(isset($stats[0])){
                $this->data['atk'] = $stats;
                for($i = 0; $i <= sizeof($stats); $i++){
                    if(isset($stats[$i])){
						$atk = $this->_ajax->_db->sel('base_attacks', '*', $stats[$i], 1)->oneLine();
                        $this->data['atk_'.$i] = $atk['atac_name'];
                        $this->data['type_'.$i] = $atk['atac_tip'];
                        $this->data['pp_max_'.$i] = intval($atk['atac_pp']);
                    }
                }
            }
        }
        unset($stats, $i);
		if(isset($this->data['pp'])){
            $stats = (!is_array($this->data['pp']) ? explode(',', $this->data['pp']) : $this->data['pp']);
            if(isset($stats[0])){
                $this->data['pp'] = $stats;
                for($i = 0; $i <= sizeof($stats); $i++){
                    if(isset($stats[$i])){
						$this->data['pp_'.$i] = intval($stats[$i]);
                    }
                }
            }
        }
        unset($stats, $i);
    }
    private function parser_info($column){
        $info = null;
        if($column){
            switch($column){
                case 'atk':
                    $info = [
                        'value'=>$this->_getAtk(),
                        'gen'=>$this->_getAtkGen(),
                        'ev'=>0
                    ];
                    break;
            }
        }
        return ($info ? $info : (isset($this->data[$column]) ? $this->data[$column] : null));
    }
    private function parse_upd($data){
        if(isset($data['exp']) && $data['exp'] >= $this->_getExpNext() && $this->_getLvl() < 100){
            $this->lvlUP($data['exp']);
        }
    }
    private function parse_stats(){
        if(!empty($this->base)){
            $this->data['inf_hp']   = intval((($this->_getHpGen() + 2 * intval($this->base['hp']) + ($this->_getHpEv()/4)) * $this->_getLvl()/100) + 10 + $this->_getLvl());
            $this->data['inf_atk']  = intval(((($this->_getAtkGen() + 2 * intval($this->base['atk']) + ($this->_getAtkEv()/4)) * $this->_getLvl()/100) + 5) * 1);
            $this->data['inf_def']  = intval(((($this->_getDefGen() + 2 * intval($this->base['def']) + ($this->_getDefEv()/4)) * $this->_getLvl()/100) + 5) * 1);
            $this->data['inf_satk'] = intval(((($this->_getSAtkGen() + 2 * intval($this->base['satk']) + ($this->_getSAtkEv()/4)) * $this->_getLvl()/100) + 5) * 1);
            $this->data['inf_sdef'] = intval(((($this->_getSDefGen() + 2 * intval($this->base['sdef']) + ($this->_getSDefEv()/4)) * $this->_getLvl()/100) + 5) * 1);
            $this->data['inf_spd']  = intval(((($this->_getSpdGen() + 2 * intval($this->base['spd']) + ($this->_getSpdEv()/4)) * $this->_getLvl()/100) + 5) * 1);
        }
    }
    private function lvlUP($exp){
        if($exp >= $this->_getExpNext() && $this->_getLvl() < 100){
            $this->update['lvl'] = $this->data['lvl'] = ($this->_getLvl() + 1);
            $this->update['exp_next'] = $this->data['exp_next'] = $this->generate_exp($this->_getLvl());
            $this->update['ev_count'] = $this->data['ev_count'] = ($this->_getEvCount() + 2);
            $this->parse_stats();
            return $this->lvlUP($exp);
        }
        return true;
    }
    private function create($inf){
        if(is_numeric($inf)){
            $inf = [
                'user'=>$this->_ajax->_user->_getId(),
                'basenum'=>intval($inf)
            ];
        }
        if(!empty($inf) && isset($inf['user'], $inf['basenum']) && $inf['user'] > 0 && $inf['basenum'] > 0){
            $this->base = $this->_ajax->_db->sel(self::TABLE_NAME_INF, '*', $inf['basenum'], 1)->oneLine();
            if(!empty($this->base['id'])){
                if(isset($inf['gen'])){
                    $gen = $inf['gen'].','.$inf['gen'].','.$inf['gen'].','.$inf['gen'].','.$inf['gen'].','.$inf['gen'];
                }else{
                    $gen = mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21);
                }
                $insert = [
                    'user'=>intval($inf['user']),
                    'name'=>$this->base['name'],
                    'basenum'=>intval($inf['basenum']),
                    'hp'=>0,
                    'lvl'=>(isset($inf['lvl']) && $inf['lvl'] > 0 ? intval($inf['lvl']) : 1),
                    'stats'=>'0,0,0,0,0,0',
                    'ev'=>'0,0,0,0,0,0',
                    'gen'=>$gen,
                    'type'=>(isset($inf['type']) ? 'shiny' : 'normal'),
                    'ev_count'=>(isset($inf['ev']) ? intval($inf['ev']) : 0),
                ];
                $insert['exp'] = $this->generate_exp($insert['lvl']);
                $insert['exp_next'] = $this->generate_exp($insert['lvl'] + 1);
                $this->data = $insert;
                $this->parse_data();
                $this->parse_stats();
                $insert['hp'] = $this->_getHp();
                $insert['stats'] = $this->_getHp().','.$this->_getAtk().','.$this->_getDef().','.$this->_getSAtk().','.$this->_getSDef().','.$this->_getSpd();
                if($id = $this->_ajax->_db->insert(self::TABLE_NAME, $insert)){
                    $this->data['id'] = $id;
                    $this->data = $insert;
                    $this->parse_data();
                    return true;
                }
            }
        }
        return false;
    }
    private function generate_exp($lvl = null){
        if(!$lvl){
            $lvl = $this->_getLvl();
        }
        if($lvl <= 0){
            $lvl = 1;
        }
        if($lvl > 100){
            $lvl = 100;
        }
        return abs(intval(($lvl * (8 * (-1 - $lvl)))*10));
    }

    public function _getAllData(){
        return $this->data;
    }
    public function _getAllInfo(){
        return $this->base;
    }
    public function _getID(){
        return intval($this->data['id']);
    }
    public function _getHp(){
        return (int)(isset($this->data['inf_'.$this->position_stats[0]]) ? $this->data['inf_'.$this->position_stats[0]] : 1);
    }
    public function _getHpGen(){
        return (int)(isset($this->data['gen_'.$this->position_stats[0]]) ? $this->data['gen_'.$this->position_stats[0]] : 1);
    }
    public function _getHpEv(){
        return (int)(isset($this->data['ev_'.$this->position_stats[0]]) ? $this->data['ev_'.$this->position_stats[0]] : 1);
    }
    public function _getAtk(){
        return (int)(isset($this->data['inf_'.$this->position_stats[1]]) ? $this->data['inf_'.$this->position_stats[1]] : 1);
    }
    public function _getAtkGen(){
        return (int)(isset($this->data['gen_'.$this->position_stats[1]]) ? $this->data['gen_'.$this->position_stats[1]] : 1);
    }
    public function _getAtkEv(){
        return (int)(isset($this->data['ev_'.$this->position_stats[1]]) ? $this->data['ev_'.$this->position_stats[1]] : 1);
    }
    public function _getDef(){
        return (int)(isset($this->data['inf_'.$this->position_stats[2]]) ? $this->data['inf_'.$this->position_stats[2]] : 1);
    }
    public function _getDefGen(){
        return (int)(isset($this->data['gen_'.$this->position_stats[2]]) ? $this->data['gen_'.$this->position_stats[2]] : 1);
    }
    public function _getDefEv(){
        return (int)(isset($this->data['ev_'.$this->position_stats[2]]) ? $this->data['ev_'.$this->position_stats[2]] : 1);
    }
    public function _getSAtk(){
        return (int)(isset($this->data['inf_'.$this->position_stats[3]]) ? $this->data['inf_'.$this->position_stats[3]] : 1);
    }
    public function _getSAtkGen(){
        return (int)(isset($this->data['gen_'.$this->position_stats[3]]) ? $this->data['gen_'.$this->position_stats[3]] : 1);
    }
    public function _getSAtkEv(){
        return (isset($this->data['ev_'.$this->position_stats[3]]) ? $this->data['ev_'.$this->position_stats[3]] : 1);
    }
    public function _getSDef(){
        return (int)(isset($this->data['inf_'.$this->position_stats[4]]) ? $this->data['inf_'.$this->position_stats[4]] : 1);
    }
    public function _getSDefGen(){
        return (int)(isset($this->data['gen_'.$this->position_stats[4]]) ? $this->data['gen_'.$this->position_stats[4]] : 1);
    }
    public function _getSDefEv(){
        return (int)(isset($this->data['ev_'.$this->position_stats[4]]) ? $this->data['ev_'.$this->position_stats[4]] : 1);
    }
    public function _getSpd(){
        return (int)(isset($this->data['inf_'.$this->position_stats[5]]) ? $this->data['inf_'.$this->position_stats[5]] : 1);
    }
    public function _getSpdGen(){
        return (int)(isset($this->data['gen_'.$this->position_stats[5]]) ? $this->data['gen_'.$this->position_stats[5]] : 1);
    }
    public function _getSpdEv(){
        return (int)(isset($this->data['ev_'.$this->position_stats[5]]) ? $this->data['ev_'.$this->position_stats[5]] : 1);
    }
    public function _getExpNext(){
        return intval($this->data['exp_next']);
    }
    public function _getLvl(){
        return intval($this->data['lvl']);
    }
    public function _getEvCount(){
        return intval($this->data['ev_count']);
    }
    public function _getInf($inf = []){
        $return = [];
        if(is_array($inf)){
            foreach($inf AS $key=>$value){
                if(!empty($value)){
                    $return[$value] = $this->parser_info($value);
                }
            }
        }
        return $return;
    }
    public function _update($upd){
        if(!empty($upd) && is_array($upd)){
            $this->update = array_merge($this->update, $upd);
            $this->parse_upd($upd);
            if(!empty($this->position_stats)){
                $this->update['stats'] = '';
                $this->update['ev'] = '';
                $this->update['gen'] = '';
                foreach($this->position_stats AS $key=>$value){
                    if($value){
                        if(isset($this->data['inf_'.$value])){
                            $this->update['stats'] .= ','.$this->data['inf_'.$value];
                        }else{
                            $this->update['stats'] .= ',0';
                        }
                        if(isset($this->data['gen_'.$value])){
                            $this->update['gen'] .= ','.$this->data['gen_'.$value];
                        }else{
                            $this->update['gen'] .= ',0';
                        }
                        if(isset($this->data['ev_'.$value])){
                            $this->update['ev'] .= ','.$this->data['ev_'.$value];
                        }else{
                            $this->update['ev'] .= ',0';
                        }
                    }
                }
                $this->update['stats'] = trim($this->update['stats'], ',');
                $this->update['ev']    = trim($this->update['ev'],    ',');
                $this->update['gen']   = trim($this->update['gen'],   ',');
            }
            if($this->_ajax->_db->upd(self::TABLE_NAME, $this->update, $this->_getID())){
                $this->data = array_merge($this->data, $this->update);
                $this->parse_data();
                $this->update = [];
            }
        }
    }
	public function _delete(){
		if($this->data['user'] != $this->_ajax->_user->_getId()){
			return $this->_ajax->_notice->_setError('ERROR_POKE_USER');
		}else{
			$responsive = $this->data['name'].' успешно отпущен!';
			$this->data = $this->_ajax->_db->del(self::TABLE_NAME, array('id'=>$this->data['id']));
			return $this->_ajax->_notice->_setError($responsive);
		}
	}
}

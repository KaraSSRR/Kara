<?php
class Info{

    const ENABLE_LOG_GAME = false;

    private static $userCache = [];

    private static $abilityIdByName = null;
    private static $abilityNameById = null;

    private static $systemCache = null;
    private static $atkCache = [];
    private static $atkCachePrivate = [];
    private static $abilityCache = [];
    private static $abilityBaseCache = []; // key => row from base_ability

    const BASE_INFO = '
            `bp`.`name_rus` AS `base_name`,
            `bp`.`hp` AS `base_hp`,
            `bp`.`atk` AS `base_atk`,
            `bp`.`satk` AS `base_satk`,
            `bp`.`def` AS `base_def`,
            `bp`.`sdef` AS `base_sdef`,
            `bp`.`spd` AS `base_spd`,
            `bp`.`type` AS `base_type`,
            `bp`.`type_two` AS `base_type_two`,
            `bp`.`power_category` AS `base_power_category`,
            `bp`.`exp_group` AS `base_exp_group`,
            `bp`.`evol_lvl` AS `base_evol_lvl`,
            `bp`.`evol_type` AS `base_evol_type`,
            `bp`.`evol_basenum` AS `base_evol_basenum`,
            `bp`.`height` AS `base_height`,
            `bp`.`weight` AS `base_weight`,

            `bp`.`tp_ev` AS `tp_ev`,
            `bp`.`effort` AS `base_effort`,
            `bp`.`chanceCatch` AS `base_chanceCatch`
    ';

    public static function _parseData(array $data = [], $void = true){
        $return = false;
        if(!empty($data)){
            try{
                if(function_exists('json_encode')){
                    $return = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                }else{
                    $return = serialize($data);
                }
            }catch (Exception $e){
                $return = '{"void":true}';
            }
        }
        return ($return ? $return : ($void ? '{"void":true}' : '{}'));
    }


    /**
     * Надійно формує uinfo для гравця в бою (id/login/group/sex/rating).
     * $override дозволяє точково перезаписати поля (наприклад catch).
     */
    public static function battleUInfo(int $userId, array $override = []): array {
        $userId = (int)$userId;
        if ($userId <= 0) return array_merge([
            'id' => 0, 'login' => 'Дикий покемон', 'user_group' => 6, 'sex' => 'm', 'rating' => '{}', 'catch' => 0
        ], $override);

        // Простий in-request cache
        if (isset(self::$userCache[$userId]) && is_array(self::$userCache[$userId])) {
            $u = self::$userCache[$userId];
        } else {
            $u = [];
            if (isset($_SESSION['id']) && (int)$_SESSION['id'] === $userId) {
                $u = [
                    'id'         => $userId,
                    'login'      => ($_SESSION['login'] ?? $_SESSION['user_login'] ?? null),
                    'user_group' => ($_SESSION['user_group'] ?? $_SESSION['group'] ?? null),
                    'sex'        => ($_SESSION['sex'] ?? null),
                    'rating'     => ($_SESSION['rating'] ?? null),
                ];
            }
            if (Work::$sql) {
                $row = Work::$sql->query("SELECT id, login, user_group, sex, rating FROM users WHERE id=".(int)$userId." LIMIT 1")->fetch_assoc();
                if (!empty($row)) {
                    $u = array_merge($u, $row);
                }
            }
            self::$userCache[$userId] = $u;
        }

        $base = [
            'id'         => $userId,
            'login'      => (isset($u['login']) ? $u['login'] : 'Тренер'),
            'user_group' => (isset($u['user_group']) ? (int)$u['user_group'] : 1),
            'sex'        => (isset($u['sex']) ? $u['sex'] : 'm'),
            'rating'     => (isset($u['rating']) ? $u['rating'] : '{}'),
            'catch'      => 0,
        ];

        return array_merge($base, $override);
    }

    /**
     * uinfo для NPC/сюжетної сторони (ловля за замовчуванням вимкнена).
     */
    public static function battleNpcUInfo(array $override = []): array {
        $base = [
            'id' => 0,
            'login' => 'Дикий покемон',
            'user_group' => 1,
            'sex' => 'm',
            'rating' => '{}',
            'catch' => 0,
        ];
        return array_merge($base, $override);
    }

    /**
     * Створює NPC/сюжетний бій і повертає battle_id.
     * Функція ідемпотентна: якщо гравець вже у бою, повертає поточний status_id.
     */
    public static function createNpcBattle(int $userId, array $enemyPokes, array $params = []): int {
        if (!Work::$sql) return 0;
        $userId = (int)$userId;
        if ($userId <= 0) return 0;

        // Ідемпотентність: якщо вже в бою — повернемо поточний
        $usr = Work::$sql->query("SELECT status, status_id FROM users WHERE id=".$userId." LIMIT 1")->fetch_assoc();
        if (!empty($usr) && ($usr['status'] ?? '') === 'battle' && (int)($usr['status_id'] ?? 0) > 0) {
            return (int)$usr['status_id'];
        }

        $type   = (isset($params['type']) ? $params['type'] : 'npc');
        $arena  = (isset($params['arena']) ? (int)$params['arena'] : 1);
        $img    = (isset($params['img']) ? (int)$params['img'] : 0);
        $weather = (isset($params['weather']) ? (int)$params['weather'] : 1);
        $weather_round = (isset($params['weather_round']) ? (int)$params['weather_round'] : 10);
        $starterLog = (isset($params['starterLog']) ? (int)$params['starterLog'] : 1);

        // За замовчуванням сюжетний бій: ловля вимкнена
        $scripted = isset($params['scripted']) ? (bool)$params['scripted'] : true;
        if ($scripted) {
            foreach ($enemyPokes as &$p) {
                if (!is_array($p)) continue;
                $p['catch'] = 0;
            }
            unset($p);
        }

        $playerInfo = self::_userInfoBattle($userId, 'pve', [
            'uinfo' => self::battleUInfo($userId, ['catch' => 0]),
        ]);

        $enemyInfo = self::_userInfoBattle(0, 'pve', [
            'npc'   => $enemyPokes,
            'uinfo' => self::battleNpcUInfo([
                'user_group' => ($scripted ? 1 : 6),
                'catch'      => ($scripted ? 0 : 1),
            ]),
        ]);

        $info_1 = self::_parseData($playerInfo);
        $info_2 = self::_parseData($enemyInfo);

        $other = isset($params['other']) && is_array($params['other']) ? $params['other'] : [];
        if ($scripted) $other['scripted'] = 1;
        if (!empty($params['npc_id'])) $other['npc_id'] = (int)$params['npc_id'];
        if (!empty($params['quest_id'])) $other['quest_id'] = (int)$params['quest_id'];
        if (!empty($params['quest_step'])) $other['quest_step'] = (int)$params['quest_step'];
        $otherStr = !empty($other) ? Work::$sql->real_escape_string(self::_parseData($other)) : '';

        $sql = "INSERT INTO `battle`
            (`round`,`user_1`,`user_2`,`info_1`,`info_2`,`other`,`type`,`weather`,`weather_round`,`img`,`arena`)
            VALUES (
                1,
                ".$userId.",
                0,
                '".Work::$sql->real_escape_string($info_1)."',
                '".Work::$sql->real_escape_string($info_2)."',
                ".($otherStr !== '' ? "'".$otherStr."'" : "NULL").",
                '".Work::$sql->real_escape_string($type)."',
                ".$weather.",
                ".$weather_round.",
                ".$img.",
                ".$arena."
            )";
        Work::$sql->query($sql);
        $battleId = (int)Work::$sql->insert_id;

        if ($battleId > 0) {
            Work::$sql->query("INSERT INTO `battle_log` (`battle`,`round`,`text`,`end`,`user`,`starter`) VALUES (".$battleId.",0,'Начало боя.<br>',0,".$userId.",".$starterLog.")");
            Work::$sql->query("UPDATE `users` SET `status`='battle', `status_id`=".$battleId." WHERE `id`=".$userId." LIMIT 1");
        }

        return $battleId;
    }

    // ======== Бойовий fallback для атак (щоб NPC ніколи не залишався без атак) ========
    private static function _fallbackAttackId(): int {
        static $fallback = null;
        if ($fallback !== null) return (int)$fallback;
        $fallback = 1;
        if (!Work::$sql) return (int)$fallback;
        $row = Work::$sql->query("SELECT id FROM base_atk WHERE pp > 0 AND power > 0 ORDER BY id ASC LIMIT 1")->fetch_assoc();
        if (!empty($row['id'])) $fallback = (int)$row['id'];
        return (int)$fallback;
    }

    private static function _fallbackAtkListStr(): string {
        return self::_fallbackAttackId().',0,0,0';
    }

    private static function _normalizeAtkListStr($atkList): string {
        $atkList = is_array($atkList) ? implode(',', $atkList) : (string)$atkList;
        $atkList = trim($atkList);
        if ($atkList === '' || $atkList === '0') return self::_fallbackAtkListStr();
        return $atkList;
    }


    public static function checkTypePokemon($num,$form) {
  		if($form == 0) {
  			$types = Work::$sql->query("SELECT type,type_two FROM base_pokemons WHERE id = ".$num)->fetch_assoc();
  		}else{
  			$types = Work::$sql->query("SELECT type,type_two FROM base_pokemon_forms WHERE id_form = ".$form." AND pokemons = ".$num)->fetch_assoc();
  		}
  		if(isset($types)) {
  			return [$types['type'],$types['type_two']];
  		}else{
  			return false;
  		}
  	}

    public static function getNumPokemonNum($id) {
      if($id >= 1 && $id <= 9) {
        return '00'.$id;
      }elseif($id >= 10 && $id <= 99) {
        return '0'.$id;
      }else{
        return $id;
      }
    }
    public static function btw($b1){
        $b1 = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $b1);
        $b1 = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $b1);
        return trim($b1);
    }

    


    public static function _getSystem($key, $default = null){
        if(!Work::$sql){
            return $default;
        }
        if(self::$systemCache === null){
            self::$systemCache = Work::$sql->query('SELECT * FROM `system` WHERE `id` = 1')->fetch_assoc();
            if(!is_array(self::$systemCache)){
                self::$systemCache = [];
            }
        }
        if(isset(self::$systemCache[$key])){
            return self::$systemCache[$key];
        }
        return $default;
    }

    public static function _intList($list){
        if(!$list){
            return [];
        }
        if(is_array($list)){
            $arr = $list;
        }else{
            $arr = explode(',', (string)$list);
        }
        $out = [];
        foreach($arr AS $v){
            $v = intval(trim($v));
            if($v > 0){
                if(!in_array($v, $out)){
                    $out[] = $v;
                }
            }
        }
        return $out;
    }

    public static function _intListToStr($list){
        $arr = self::_intList($list);
        return (!empty($arr) ? implode(',', $arr) : '');
    }

    public static function _unParseData($data = null){
        $return = [];
        if(!empty($data)){
            if(is_string($data) && (strpos($data, '{') !== false || strpos($data, '[') !== false)){
                try{
                    $data = self::btw($data);
                    if(function_exists('json_decode')){
                        $return = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
                    }else{
                        $return = unserialize($data);
                    }
                }catch (Exception $e){
                    $return = [];
                }
            }elseif(is_array($data)){
                return $data;
            }
        }
        return ($return ? $return : []);
    }

    public static function _logGame($user_id, $title, $info = [], $type = 'other'){

        if(!self::ENABLE_LOG_GAME){
            return;
        }

        if(Work::$sql){

            $typeList = ['items', 'poke', 'trade', 'other'];

            if(in_array($type, $typeList)){

                $info = array_merge([
                    'number'=>0,
                    'count'=>1
                ], $info);

                $info = self::_parseData($info);

                Work::$sql->query('
                      INSERT INTO
                        `log_game`
                             (`user_id`,`type`,`title`,`info`)
                      VALUES ('.intval($user_id).', "'.Work::$sql->real_escape_string($type).'", "'.Work::$sql->real_escape_string($title).'", "'.Work::$sql->real_escape_string($info).'")
                ');


            }

        }



    }

    
    

    private static function _initAbilityMaps(){
        if(self::$abilityIdByName !== null && self::$abilityNameById !== null){
            return;
        }
        self::$abilityIdByName = [];
        self::$abilityNameById = [];
        if(Work::$sql){
            $res = Work::$sql->query('SELECT `id`,`name` FROM `base_ability`');
            if($res){
                while($row = $res->fetch_assoc()){
                    if(isset($row['name'])){
                        $name = strtolower(trim($row['name']));
                        $id = intval($row['id']);
                        self::$abilityIdByName[$name] = $id;
                        self::$abilityNameById[$id] = $row['name'];
                    }
                }
            }
        }
    }

    public static function getAbilityIdByName($name){
        if(!$name){
            return 0;
        }
        self::_initAbilityMaps();
        $key = strtolower(trim($name));
        return (isset(self::$abilityIdByName[$key]) ? intval(self::$abilityIdByName[$key]) : 0);
    }

    public static function getAbilityNameById($id){
        $id = intval($id);
        if($id <= 0){
            return '';
        }
        self::_initAbilityMaps();
        return (isset(self::$abilityNameById[$id]) ? self::$abilityNameById[$id] : '');
    }

    public static function getAbilityBase($val = []) {

      if(!Work::$sql || empty($val[0]) || !isset($val[1], $val[2])){
          return null;
      }

      $val[1] = Work::$sql->real_escape_string($val[1]);

      $ck = 'abil_'.$val[0].'_'.$val[1];

      if(isset(self::$userCache[$ck])){
          $Ability = self::$userCache[$ck];
      }else{
          $Ability = Work::$sql->query("SELECT * FROM base_ability WHERE ".$val[0]." = '".$val[1]."'")->fetch_assoc();
          self::$userCache[$ck] = ($Ability ? $Ability : []);
      }

      return (isset($Ability[$val[2]]) ? $Ability[$val[2]] : null);
    }


    public static function getAbilityBaseId($val = []) {

        // slot index (1/2/3) по ability_num для конкретного покемона
        if(empty($val[0]) || empty($val[1])){
            return 0;
        }

        $pokeNum = intval($val[0]);
        $abilityNum = intval($val[1]);

        if($pokeNum <= 0 || $abilityNum <= 0){
            return 0;
        }

        $row = Work::$sql->query("SELECT slot1, slot2, slot3 FROM base_ability_pokemon WHERE id = ".$pokeNum)->fetch_assoc();
        if(!$row || !is_array($row)){
            return 0;
        }

        if(!empty($row['slot1']) && intval($row['slot1']) === $abilityNum){
            return 1;
        }
        if(!empty($row['slot2']) && intval($row['slot2']) === $abilityNum){
            return 2;
        }
        if(!empty($row['slot3']) && intval($row['slot3']) === $abilityNum){
            return 3;
        }

        return 0;

    }

public static function generateAbility($num){
      $num = intval($num);
      if($num <= 0){
        return [0,0];
      }

      if(isset(self::$abilityCache['p'.$num])){
        return self::$abilityCache['p'.$num];
      }

      $Ability = Work::$sql->query('SELECT * FROM base_ability_pokemon WHERE id = '.$num)->fetch_assoc();
      if(empty($Ability)){
        self::$abilityCache['p'.$num] = [0,0];
        return [0,0];
      }

      $Abil1 = (isset($Ability['slot1']) && $Ability['slot1'] != "0" ? intval($Ability['slot1']) : 0);
      $Abil2 = (isset($Ability['slot2']) && $Ability['slot2'] != "0" ? intval($Ability['slot2']) : 0);
      $Abil3 = (isset($Ability['slot3']) && $Ability['slot3'] != "0" ? intval($Ability['slot3']) : 0);

      // Hidden Ability chance (slot3), default 5%
      $haChance = intval(self::_getSystem('ha_chance', 5));
      if($haChance < 0) $haChance = 0;
      if($haChance > 100) $haChance = 100;

      // Если есть HA и сработал шанс — выдаём слот3
      if($Abil3 > 0 && $haChance > 0 && mt_rand(1,100) <= $haChance){
        self::$abilityCache['p'.$num] = [$Abil3, 3];
        return [$Abil3, 3];
      }

      $AbilArray = [];
      if($Abil1 > 0) $AbilArray[] = [$Abil1, 1];
      if($Abil2 > 0) $AbilArray[] = [$Abil2, 2];

      if(!empty($AbilArray)){
        if(count($AbilArray) == 1){
          self::$abilityCache['p'.$num] = [$AbilArray[0][0], $AbilArray[0][1]];
          return [$AbilArray[0][0], $AbilArray[0][1]];
        }else{
          $rand = mt_rand(0, count($AbilArray)-1);
          self::$abilityCache['p'.$num] = [$AbilArray[$rand][0], $AbilArray[$rand][1]];
          return [$AbilArray[$rand][0], $AbilArray[$rand][1]];
        }
      }

      self::$abilityCache['p'.$num] = [0,0];
      return [0,0];
    }


    public static function generateForm($num) {
      $TargetForm = "";
      switch($num) {
        case 666:
           $input = array('archipelago','continental','elegant','fancy','garden','highplains','icysnow','jungle','marine','modern','monsoon','ocean','pokeball','polar','river','sandstorm','savanna','sun','tundra');
           $rand_keys = array_rand($input, 2);
           $TargetForm = $input[$rand_keys[1]]; 
        break;
        case 669:
        case 670:
        case 671:
            $r = rand(1,4);
          if($r == 1) {
            $TargetForm = 'blue';
          }elseif($r == 2) {
            $TargetForm = 'orange';
          }elseif($r == 3) {
            $TargetForm = 'white';
          }else{
            $TargetForm = 'yellow';
          }
        break;
        case 931:
            $r = rand(1,4);
          if($r == 1) {
            $TargetForm = 'blue';
          }elseif($r == 2) {
            $TargetForm = '';
          }elseif($r == 3) {
            $TargetForm = 'white';
          }else{
            $TargetForm = 'yellow';
          }
        break;
        case 585:
        case 586:
          if(in_array(date("n"),[12,1,2])) {
            $TargetForm = 'winter';
          }elseif(in_array(date("n"),[3,4,5])) {
            $TargetForm = '';
          }elseif(in_array(date("n"),[6,7,8])) {
            $TargetForm = 'summer';
          }else{
            $TargetForm = 'autumn';
          }
        break;
        case 263:
        case 618:
          if(Info::getLocation(['id',Info::getStringUser(['id',$_SESSION['id'],'location']),'region']) == 3) {
            $TargetForm = "galar";
          }
        break;
        default:
          $TargetForm = '';
        break;
      }
      if($TargetForm != '') {
        return $TargetForm;
      }else{
        $Form = Work::$sql->query('SELECT * FROM base_pokemon_forms WHERE start = 1 AND pokemons = '.$num)->fetch_assoc();
        if(isset($Form)) {
          $Form = Work::$sql->query('SELECT * FROM base_pokemon_forms WHERE start = 1 AND pokemons = '.$num);
          $FormArray = [];
          while($f = $Form->fetch_assoc()) {
            $FormArray[$f['id']] = [
              'form' => $f['id_form']
            ];
          }
          shuffle($FormArray);
          $Return = $FormArray[0]['form'];
        }else{
          $Return = 0;
        }
        if($Return == NULL) {
          $Return = 0;
        }
        return $Return;
      }
    }

    
    public static function getStringUser($val = []) {

        if(!Work::$sql || empty($val[0]) || !isset($val[1], $val[2])){
            return null;
        }

        $val[1] = Work::$sql->real_escape_string($val[1]);

        $ck = 'usr_'.$val[0].'_'.$val[1];

        if(isset(self::$userCache[$ck])){
            $User = self::$userCache[$ck];
        }else{
            $User = Work::$sql->query("SELECT * FROM users WHERE ".$val[0]." = '".$val[1]."'")->fetch_assoc();
            self::$userCache[$ck] = ($User ? $User : []);
        }

        return (isset($User[$val[2]]) ? $User[$val[2]] : null);
    }


    
    public static function getLocation($val = []) {

      if(!Work::$sql || empty($val[0]) || !isset($val[1], $val[2])){
          return null;
      }

      $val[1] = Work::$sql->real_escape_string($val[1]);

      $ck = 'loc_'.$val[0].'_'.$val[1];

      if(isset(self::$userCache[$ck])){
          $Location = self::$userCache[$ck];
      }else{
          $Location = Work::$sql->query("SELECT * FROM base_location WHERE ".$val[0]." = '".$val[1]."'")->fetch_assoc();
          self::$userCache[$ck] = ($Location ? $Location : []);
      }

      return (isset($Location[$val[2]]) ? $Location[$val[2]] : null);
    }

public static function _userInfoBattle($userID, $type = 'pve', array $option = []){

        if(Work::$sql && is_numeric($userID) && $userID >= 0){

            $userID = intval($userID);

            $type = ($type ? $type : 'pve');

            $target = 0;

            if(isset($option['npc'])){
                $pokeUser = $option['npc'];
            }else{
                $pokeUser = self::_getPokeUserBattle($userID);
            }

            if(!empty($pokeUser)){

                $pokeUserInfo = [];

                if(isset($option['npc'])){
                    $id = 0;
                    foreach($option['npc'] AS $key=>$value){
                        ++$id;
                        if(isset($value['basenum'])){
                            $baseInfo = Work::$sql->query('
                                                              SELECT

                                                                `bp`.`name_rus` AS `name_new`,
                                                                `bp`.`sex_m` AS `base_sex_m`,
                                                                `bp`.`sex_f` AS `base_sex_f`,

                                                                '.self::BASE_INFO.'

                                                              FROM `base_pokemons` AS `bp`

                                                              WHERE `bp`.`id` = '.$value['basenum'].'

                                                        ')->fetch_assoc();
                            $FormPok = Info::generateForm($value['basenum']);
                                                        
                            if($value['numb'] == 9595){
                                $FormPok = 'mega';
                                if($value['basenum'] == 6 or $value['basenum'] == 150){
                                    if(rand(1,2) == 1){
                                        $FormPok = 'megax';
                                    }else{
                                        $FormPok = 'megay';
                                    }
                                }    
                            }
                                                        
                            if($FormPok != 0 && $FormPok != "") {
                              $FormBase = Work::$sql->query('SELECT * FROM base_pokemon_forms WHERE pokemons = '.$value['basenum'].' AND id_form = "'.$FormPok.'" ')->fetch_assoc();
                              if($FormBase){
                                  $baseInfo['base_type'] = $FormBase['type'];
                                  $baseInfo['base_type_two'] = $FormBase['type_two'];
                                  $baseInfo['base_hp'] = $FormBase['hp'];
                                  $baseInfo['base_atk'] = $FormBase['atk'];
                                  $baseInfo['base_satk'] = $FormBase['satk'];
                                  $baseInfo['base_def'] = $FormBase['def'];
                                  $baseInfo['base_sdef'] = $FormBase['sdef'];
                                  $baseInfo['base_spd'] = $FormBase['spd'];
                              }
                            }
                                                        
                            if($value['numb'] == 9596){
                                $FormPok = 'gmax';
                                $baseInfo['base_hp'] = $baseInfo['base_hp']*22;
                                $baseInfo['base_atk'] = $baseInfo['base_atk']*7;
                                $baseInfo['base_satk'] = $baseInfo['base_satk']*7;
                                $baseInfo['base_def'] = $baseInfo['base_def']*4;
                                $baseInfo['base_sdef'] = $baseInfo['base_sdef']*4;
                                $baseInfo['base_spd'] = $baseInfo['base_spd'];
                            }
                                                        
                            if($value['numb'] == 9597){
                                $baseInfo['base_hp'] = $baseInfo['base_hp']*15;
                                $baseInfo['base_atk'] = $baseInfo['base_atk']*6;
                                $baseInfo['base_satk'] = $baseInfo['base_satk']*6;
                                $baseInfo['base_def'] = $baseInfo['base_def']*3;
                                $baseInfo['base_sdef'] = $baseInfo['base_sdef']*3;
                                $baseInfo['base_spd'] = $baseInfo['base_spd'];
                            }

                            if(empty($baseInfo)){
                                continue;
                            }
                            
                            $atk = (isset($value['atk_list']) ? $value['atk_list'] : self::_generateAtkListPoke($value['basenum'], $value['lvl']));
                            
                            if(empty($atk) || $atk === '0'){
                                $atk = self::_fallbackAtkListStr();
                            }

                            if(!$target){
                                $target = $id;
                            }

                            /*
                              ===== Изменённый блок расчёта шанса shiny =====
                              - system.shine используется как базовое число на 10000 (напр. 3 -> 3/10000)
                              - бафы складываются (мультипликативно): 191 -> *1.30, 448 -> *1.15, etc.
                              - итоговый шанс = round(system.shine * product_of_buffs)
                              - бросок mt_rand(1,10000) <= computedChance
                              - при shiny добавляем +2 к генам и капаем по maxGene
                            */

                            $Bonus = Work::$sql->query('
                                          SELECT  `shine`
                                          FROM `system`
                                          WHERE `id` = '.intval(1).'
                            ')->fetch_assoc();

                            // базовое значение шанса (на 10000)
                            $systemShine = max(1, intval($Bonus['shine'] ?? 1)); // минимум 1 => 1/10000

                            // сессия пользователя (для выборки его бафов)
                            $userIdSession = intval($_SESSION['id'] ?? 0);

                            // Ищем активные бафы 191 и 448 (можно расширить в будущем)
                            $hasBaf191 = Work::$sql->query('
                                          SELECT `id`
                                          FROM `bafs`
                                          WHERE `baf` = 191 AND `time` > "'.time().'" AND `user` = '. $userIdSession .'
                            ')->fetch_assoc();

                            $hasBaf448 = Work::$sql->query('
                                          SELECT `id`
                                          FROM `bafs`
                                          WHERE `baf` = 448 AND `time` > "'.time().'" AND `user` = '. $userIdSession .'
                            ')->fetch_assoc();

                            // Если в будущем добавите другие бафы, добавляйте тут их обработку (умножаем multiplier)
                            $multiplier = 1.0;

                            if(!empty($hasBaf191['id'])){
                                $multiplier *= 1.30; // +30%
                            }
                            if(!empty($hasBaf448['id'])){
                                $multiplier *= 1.15; // +15%
                            }

                            // Пример: можно учитывать дополнительные бафы из таблицы bafs с полем 'shine_mult' (необязательно)
                            // $extraBafs = Work::$sql->query('SELECT `baf`,`shine_mult` FROM `bafs` WHERE `user` = '.$userIdSession.' AND `time` > "'.time().'"');
                            // while($rb = $extraBafs->fetch_assoc()){ if(isset($rb['shine_mult'])) $multiplier *= (float)$rb['shine_mult']; }

                            // вычисляем итоговый шанс (на 10000)
                            $computedChance = (int) round($systemShine * $multiplier);

                            // безопасность: минимум 1, максимум 10000
                            if($computedChance < 1) $computedChance = 1;
                            if($computedChance > 10000) $computedChance = 10000;

                            // делаем бросок 1..10000
                            $randShin = mt_rand(1, 10000);

                            // применяем catch-check как прежде
                            if(($randShin <= $computedChance) AND ($value['catch'] != 0)){
                                // SHINY: добавляем +2 к генам с капом
                                $maxGene = 31;    // максимум для гена (можно вынести в system)
                                $geneBonus = 2;   // бонус за shiny

                                if(isset($value['gen']) && $value['gen'] && is_string($value['gen'])){
                                    $parts = array_map('intval', explode(',', $value['gen']));
                                    for($i = 0; $i < count($parts); $i++){
                                        $parts[$i] = min($maxGene, $parts[$i] + $geneBonus);
                                    }
                                    $value['gen'] = implode(',', $parts);
                                }
                                $shineMax = 'shine';
                            }else{
                                $shineMax = 'normal';
                            }
				            // end of shiny block

				            // if(rand(1,120) <= 4){
				            //     $shineMax = 'lover';
				            // }

            $Abil = Info::generateAbility($value['basenum']);
                            if($baseInfo['base_sex_f'] == 0 && $baseInfo['base_sex_m'] == 0) {
                              $gender = 'Бесполый';
                            }else{
                              $gender = ($baseInfo['base_sex_f'] > 0 ? ( $baseInfo['base_sex_f'] >= mt_rand(0, 100) ? 'Девочка' : 'Мальчик' ) : 'Мальчик');
                            }

                            if(in_array($value['basenum'], [592,593,521,678,876,902,916])) {
                              if($gender == 'Мальчик') {
                                $FormPok = '';
                              }else{
                                $FormPok = 'female';
                              }
                            }
                            
                            $teraTypeId = (!empty($value['tera_type'])
                                ? self::_resolveTeraTypeId($value['tera_type'])
                                : self::_generateWildTeraType($baseInfo['base_type'] ?? 'normal', $baseInfo['base_type_two'] ?? '', $value['sparka'] ?? 0));
                            $teraTypeName = self::_resolveTeraTypeName($teraTypeId);
                            $isTerastallized = !empty($value['is_terastallized']);
                            $preTeraTypes = (isset($value['pre_tera_types']) ? self::_normalizeTypeList($value['pre_tera_types']) : []);
                            if ($isTerastallized && empty($preTeraTypes)) {
                                $preTeraTypes = array_values(array_filter([
                                    self::_resolveTeraTypeName($baseInfo['base_type'] ?? 'normal'),
                                    self::_resolveTeraTypeName($baseInfo['base_type_two'] ?? '')
                                ]));
                            }

                            $pokeUserInfo['p'.$id] = array_merge($baseInfo, [
                                'id'=>$id,
                                'user_id'=>$userID,
                                'basenum'=>$value['basenum'],
                                'form'=>$FormPok,
                                'character'=>mt_rand(1, 26),
                                'lvl'=>$value['lvl'],
                                'numb'=>$value['numb'],
                                'boss'=>$value['boss'],
                                'date_get'=>'',
                                'ability'=>$Abil[0],
                                'ability_slot'=>$Abil[1],
                                'start_pok'=>1,
                                'active'=>1,
                                'type'=>$shineMax,
                                'gender'=>$gender,
                                'exp'=>self::_getExp($value['lvl'], $baseInfo['base_exp_group']),
                                'exp_max'=>self::_getExp($value['lvl']+1, $baseInfo['base_exp_group']),
                                'ev'=>0,
                                'hp'=>true,
                                'stats'=>'0,0,0,0,0,0',
                                'evcounts'=>'0,0,0,0,0,0',
                                'gen'=>(isset($value['gen']) && $value['gen'] && is_string($value['gen']) ? $value['gen'] : mt_rand(10,21).','.mt_rand(10,21).','.mt_rand(10,21).','.mt_rand(10,21).','.mt_rand(10,21).','.mt_rand(10,21)),
                                'vitamines'=>0,
                                'owner'=>0,
                                'master'=>0,
                                'item_id'=>($a_item_id ?? (isset($value['item_id']) ? intval($value['item_id']) : 0)),
                                'startGame'=>0,
                                'sparka'=>(int)($value['sparka'] ?? 0),
                                'trn'=>0,
                                'trn_stat'=>0,
                                'happy'=>15,
                                'attacks'=>$atk,
                                'pp_attacks'=>'10,10,10,10',
                                'lastWent'=>'',
                                'trade'=>'true',
                                'sparkaNumber'=>mt_rand(0, 3),
                                'tren'=>0,
                                'tren_stat'=>0,
                                'event'=>0,
                                'atkList'=> self::_pokeAtkList($atk),
                                'catch'=>$value['catch'],
                                'pp_my'=>'2000,2000,2000,2000',
                                'disable_my'=>'0,0,0,0',
                                'tera_type'        => $teraTypeId,
                                'tera_type_name'   => $teraTypeName,
                                'tera_type_source' => ($value['tera_type_source'] ?? 'wild'),
                                'tera_active'      => ($isTerastallized ? 1 : 0),
                                'is_terastallized' => ($isTerastallized ? 1 : 0),
                                'pre_tera_types'   => $preTeraTypes,
                                'stellar_used_types' => (isset($value['stellar_used_types']) ? $value['stellar_used_types'] : []),

                                'dmg_before' => 0
                            ]);

                        }
                    }
                    unset($key,$value);
                }else{
                    while($pokeUserList = $pokeUser->fetch_assoc()){
                        if(isset($pokeUserList['id'])){
                            if(!$target){
                                $target = $pokeUserList['id'];
                            }
                            if($pokeUserList['start_pok'] > 0){
                                $target = $pokeUserList['id'];
                            }
                            $pokeUserList['atkList'] = self::_pokeAtkList($pokeUserList['attacks']);
                            $pokeUserList['pp_my'] = $pokeUserList['pp_attacks'];
                            $pokeUserList['disable_my'] = '0,0,0,0';
                            $FormMy = Work::$sql->query('SELECT type,type_two FROM base_pokemon_forms WHERE pokemons = '.$pokeUserList['basenum'].' AND id_form = "'.$pokeUserList['form'].'" ')->fetch_assoc();
                            if(isset($FormMy)) {
                              $pokeUserList['base_type'] = $FormMy['type'];
                              $pokeUserList['base_type_two'] = $FormMy['type_two'];
                            }
                            $pokeUserList['dmg_before'] = 0;
                            if (empty($pokeUserList['tera_type'])) {
                                $pokeUserList['tera_type'] = $pokeUserList['base_type'] ?? 'normal';
                            }
                            $pokeUserList['tera_type'] = self::_resolveTeraTypeId($pokeUserList['tera_type']);
                            $pokeUserList['tera_type_name'] = self::_resolveTeraTypeName($pokeUserList['tera_type']);
                            if (!isset($pokeUserList['tera_type_source'])) {
                                $pokeUserList['tera_type_source'] = 'wild';
                            }
                            if (!isset($pokeUserList['is_terastallized'])) {
                                $pokeUserList['is_terastallized'] = 0;
                            }
                            if (!isset($pokeUserList['pre_tera_types'])) {
                                $pokeUserList['pre_tera_types'] = [];
                            }
                            if (!isset($pokeUserList['stellar_used_types'])) {
                                $pokeUserList['stellar_used_types'] = [];
                            }
                            $pokeUserList['tera_active'] = (int)($pokeUserList['is_terastallized'] ?? 0);

                            $pokeUserInfo['p'.$pokeUserList['id']] = $pokeUserList;
                        }
                    }
                }


                $uInfo = (isset($option['uinfo']) ? $option['uinfo'] : []);

                // Якщо це сторона реального гравця і uinfo не передали — підтягуємо автоматично (щоб userInfo.id не був 0)
                if (empty($uInfo) && $userID > 0) {
                    $uInfo = self::battleUInfo($userID);
                }

                if(!empty($pokeUserInfo)){
                    $teraUsed = 0;
                    foreach ($pokeUserInfo as $poke) {
                        if (!empty($poke['is_terastallized'])) {
                            $teraUsed = 1;
                            break;
                        }
                    }
                    return [
    'target'=>($type == 'pvp' ? 0 : $target),
    'timer'=>[],
    'tera_used'=>$teraUsed,
    'tera'=>0,
    'pokeLIst'=>$pokeUserInfo,
    'pokeList'=>$pokeUserInfo,
    'userInfo'=>[

                            'id'    =>((isset($uInfo['id']) ? intval($uInfo['id']) : 0)),
                            'login' =>((isset($uInfo['login']) ? $uInfo['login'] : 'Дикий покемон')),
                            'group' =>((isset($uInfo['user_group']) ? intval($uInfo['user_group']) : 6)),
                            'sex'   =>((isset($uInfo['sex']) ? $uInfo['sex'] : 'm')),
                            'rating'=>((isset($uInfo['rating']) ? $uInfo['rating'] : '{}')),
                            'catch' =>((isset($uInfo['catch']) ? $uInfo['catch'] : 0))
                        ]
                    ];
                }
            }

        }

        return [];
    }

    private static function _getTeraTypeMap(): array
    {
        return [
            1 => 'normal',
            2 => 'fire',
            3 => 'water',
            4 => 'electric',
            5 => 'grass',
            6 => 'ice',
            7 => 'fighting',
            8 => 'poison',
            9 => 'ground',
            10 => 'flying',
            11 => 'psychic',
            12 => 'bug',
            13 => 'rock',
            14 => 'ghost',
            15 => 'dragon',
            16 => 'dark',
            17 => 'steel',
            18 => 'fairy',
            19 => 'stellar'
        ];
    }

    private static function _resolveTeraTypeName($teraType): string
    {
        if (is_numeric($teraType)) {
            $map = self::_getTeraTypeMap();
            $id = (int)$teraType;
            return $map[$id] ?? 'normal';
        }
        $teraType = strtolower((string)$teraType);
        if ($teraType === '') {
            return 'normal';
        }
        return $teraType;
    }

    private static function _resolveTeraTypeId($teraType): int
    {
        if (is_numeric($teraType)) {
            return (int)$teraType;
        }
        $teraType = strtolower((string)$teraType);
        $map = self::_getTeraTypeMap();
        foreach ($map as $id => $name) {
            if ($name === $teraType) {
                return $id;
            }
        }
        return 1;
    }

    private static function _normalizeTypeList($types): array
    {
        if (is_string($types)) {
            $types = array_filter(array_map('trim', explode(',', $types)));
        }
        if (!is_array($types)) {
            return [];
        }
        $normalized = [];
        foreach ($types as $type) {
            $name = self::_resolveTeraTypeName($type);
            if ($name !== '') {
                $normalized[] = $name;
            }
        }
        return array_values(array_unique($normalized));
    }

    private static function _rollWildTeraType($baseType, $baseTypeTwo, $offTypeRate, $allowStellar): int
    {
        $baseType = self::_resolveTeraTypeName($baseType ?: 'normal');
        $baseTypeTwo = self::_resolveTeraTypeName($baseTypeTwo);
        $offTypeRate = max(0, min(100, (int)$offTypeRate));

        $types = self::_getTeraTypeMap();
        $baseIds = array_values(array_filter([
            self::_resolveTeraTypeId($baseType),
            self::_resolveTeraTypeId($baseTypeTwo)
        ]));
        $baseIds = array_values(array_unique($baseIds));

        $roll = mt_rand(1, 100);
        if ($roll <= $offTypeRate) {
            $pool = array_keys($types);
            $pool = array_values(array_diff($pool, $baseIds));
            if (!$allowStellar) {
                $pool = array_values(array_diff($pool, [19]));
            }
            if (empty($pool)) {
                $pool = array_keys($types);
            }
            return (int)$pool[array_rand($pool)];
        }

        if (count($baseIds) > 1) {
            return (int)$baseIds[array_rand($baseIds)];
        }

        return (int)($baseIds[0] ?? 1);
    }

    private static function _generateWildTeraType($baseType, $baseTypeTwo = '', $sparkaChance = 0): int
    {
        $baseType = self::_resolveTeraTypeName($baseType ?: 'normal');
        $baseTypeTwo = self::_resolveTeraTypeName($baseTypeTwo);

        $types = self::_getTeraTypeMap();
        $baseIds = array_values(array_filter([
            self::_resolveTeraTypeId($baseType),
            self::_resolveTeraTypeId($baseTypeTwo)
        ]));
        $baseIds = array_values(array_unique($baseIds));

        $sparkaChance = max(0, min(100, (int)$sparkaChance));
        $offTypeRate = min(100, max(0, 20 + (int)floor($sparkaChance / 5)));
        return self::_rollWildTeraType($baseType, $baseTypeTwo, $offTypeRate, false);
    }


        public static function _generateAtkListPoke($poke_num, $poke_lvl){
        if($poke_num && $poke_num > 0 && $poke_lvl && $poke_lvl > 0){
            $info = Work::$sql->query('
                                          SELECT  *
                                          FROM `base_attacks_pokemons`
                                          WHERE `pok` = '.intval($poke_num).' AND `type` = "lvl"
                                 ')->fetch_assoc();
            if(!empty($info['attacks'])){
                $info['attacks'] = explode(',', $info['attacks']);
                $info['lvl'] = explode(',', $info['lvl']);
                $count = sizeof($info['attacks']);
                $learned = [];
                for($i = 0; $i < $count; $i++){
                    if(isset($info['attacks'][$i], $info['lvl'][$i])){
                        if($poke_lvl >= intval($info['lvl'][$i])){
                            $learned[] = intval(trim($info['attacks'][$i]));
                        }
                    }
                }

                if(empty($learned)){
                    return self::_fallbackAtkListStr();
                }

                $learned = self::_intList($learned);
                if(empty($learned)){
                    return self::_fallbackAtkListStr();
                }

                $need = (count($learned) >= 4 ? 4 : count($learned));
                shuffle($learned);
                $return = array_slice($learned, 0, $need);

                if(!empty($return)){
                    return implode(',', $return);
                }
            }
        }
        return '0';
    }


  
 
 public static function _pokeAtkList($atkList, $private = false){
        $return = [];

        if($atkList === null){
            return $return;
        }

        // '0' або порожній список — підставляємо fallback
        if($atkList === '' || $atkList === '0' || (is_array($atkList) && empty($atkList))){
            $atkList = self::_fallbackAtkListStr();
        }

        // === Cache: one request per unique (lang + ids + private) ===
        $lang = ($_SESSION['attack_lang'] ?? 'rus');

        $atkList = self::_normalizeAtkListStr($atkList);
        $cacheKey = $lang.'|'.(string)$atkList;
        if($private){
            if(isset(self::$atkCachePrivate[$cacheKey])){
                return self::$atkCachePrivate[$cacheKey];
            }
        }else{
            if(isset(self::$atkCache[$cacheKey])){
                return self::$atkCache[$cacheKey];
            }
        }

    if($atkList){

        // Безопасность: оставляем только цифры и запятые (исключаем SQL-injection)
        if(is_array($atkList)){
            $atkList = implode(',', $atkList);
        }
        $atkList = preg_replace('/[^0-9,]/', '', (string)$atkList);
        $atkList = trim($atkList, ',');

        if($atkList === ''){
            return [];
        }

        // Получаем язык пользователя (например, из сессии)
        $lang = $_SESSION['attack_lang'] ?? 'rus';
        $atk_col = ($lang == 'eng') ? 'name' : 'name_rus';

        // --- Кэширование (in-request + APCu, если доступно) ---
        if(!isset(self::$userCache['atk_cache'])){
            self::$userCache['atk_cache'] = [
                'rus'=>[],
                'eng'=>[]
            ];
        }
        if(!isset(self::$userCache['atk_cache'][$lang])){
            self::$userCache['atk_cache'][$lang] = [];
        }

        // Определяем набор доступных колонок base_atk (один раз за запрос)
        if(!isset(self::$userCache['atk_cols'])){
            self::$userCache['atk_cols'] = [];
            $colsQ = Work::$sql->query('SHOW COLUMNS FROM `base_atk`');
            if($colsQ){
                while($c = $colsQ->fetch_assoc()){
                    if(isset($c['Field'])){
                        self::$userCache['atk_cols'][$c['Field']] = 1;
                    }
                }
            }
        }

        $ids = array_values(array_filter(array_map('intval', explode(',', $atkList))));
        if(empty($ids)){
            return [];
        }

        $need = [];
        foreach($ids as $id){
            if($id <= 0) continue;

            if(isset(self::$userCache['atk_cache'][$lang][$id])){
                continue;
            }

            // APCu: индивидуальный кэш по ID+lang
            if(function_exists('apcu_fetch')){
                $k = 'base_atk_'.$lang.'_'.$id;
                $hit = false;
                $row = apcu_fetch($k, $hit);
                if($hit && is_array($row)){
                    self::$userCache['atk_cache'][$lang][$id] = $row;
                    continue;
                }
            }

            $need[] = $id;
        }

        if(!empty($need)){
            $in = implode(',', $need);

            // Базовые колонки (используются движком/интерфейсом всегда)
            $selectCols = [
                '`id`',
                '`'.$atk_col.'` AS `name`',
                '`title`',
                '`type`',
                '`category`',
                '`priority`',
                '`power`',
                '`accuracy`',
                '`pp`',
                '`target`',
                '`settings`',
                '`my`',
                '`enemy`',
                '`contact`',
                '`bite`',
                '`pulse`',
                '`punch`',
                '`sound`',
                '`magic_coat`',
                '`bullet`'
            ];

            // Доп. поля (под новые механики). Добавляем только если колонка реально существует
            $optionalCols = [
                'protect',
                'snatchable',
                'mirror_move',
                'defrost',
                'punching',
                'soundproof',
                'wind',
                'dance',
                'kings_rock',
                'movedex',
                'priority_change',
                'crit_ratio',
                'mod',
                'status'
            ];
            foreach($optionalCols as $oc){
                if(isset(self::$userCache['atk_cols'][$oc])){
                    $selectCols[] = '`'.$oc.'`';
                }
            }

            $info = Work::$sql->query('SELECT
                                        '.implode(",\n                                        ", $selectCols).'
                                    FROM `base_atk`
                                    WHERE `id` IN ('.$in.')
                                 ');

            if(!empty($info)){
                while($infoList = $info->fetch_assoc()){
                    if(!isset($infoList['id'])) continue;

                    $aid = (int)$infoList['id'];

                    // settings может быть JSON/serialized (поддерживаем оба формата)
                    if(!empty($infoList['settings']) && !is_array($infoList['settings'])){
                        $infoList['settings'] = Info::_unParseData($infoList['settings']);
                    }

                    // Кэшируем
                    self::$userCache['atk_cache'][$lang][$aid] = $infoList;

                    if(function_exists('apcu_store')){
                        apcu_store('base_atk_'.$lang.'_'.$aid, $infoList, 3600);
                    }
                }
            }
        }

        // Собираем в исходном порядке
        $i = 0;
        foreach($ids as $id){
            if(isset(self::$userCache['atk_cache'][$lang][$id])){
                $row = self::$userCache['atk_cache'][$lang][$id];
                $row['attack_num'] = $i;

                if($private){
                    unset(
                        $row['target'],
                        $row['settings'],
                        $row['my'],
                        $row['enemy'],
                        $row['priority']
                    );
                }

                $return['a'.$id] = $row;
            }
            $i++;
        }
    }
    if($private){
            self::$atkCachePrivate[$cacheKey] = $return;
        }else{
            self::$atkCache[$cacheKey] = $return;
        }

        return $return;
}

    public static function _getPokeUserBattle($user_id = null){
        return Work::$sql->query('
                  SELECT
                    `up`.*,

                    '.self::BASE_INFO.'

                  FROM `user_pokemons` AS `up`
                  INNER JOIN `base_pokemons` AS `bp`
                    ON `bp`.`id` = `up`.`basenum`
                  WHERE
                    `up`.`user_id` = '.($user_id && $user_id > 0 ? intval($user_id) : (isset($_SESSION['id']) ? $_SESSION['id'] : 0)).' AND
                    `up`.`active` = 1 AND
                    `up`. `hp` > 0
            ');
    }

public static function _generatePve(array $user_info, $location_id, $chance = null, $quest = null, $poknum = null, $lvl_boss = null)
{
    $user_id     = (int)($_SESSION['id'] ?? 0);
    $location_id = (int)$location_id;
    $quest       = ($quest === null ? null : (int)$quest);
    $poknum      = ($poknum === null ? 0 : (int)$poknum); // 0 = обычный выбор из локации
    $lvl_boss    = ($lvl_boss === null ? null : (int)$lvl_boss);

    $ulpbd = Work::$sql->query('SELECT * FROM `users` WHERE `id` = '.$user_id)->fetch_assoc();

    // Валидация параметров
    if ($location_id <= 0) return false;
    if (empty($user_info) || !isset($user_info['id'])) return false;

    // --- Лимит частоты нападений через сессию (9-18 сек) ---
    $now = time();
    if (isset($_SESSION['pve_last_time']) && $now - (int)$_SESSION['pve_last_time'] > 60) {
        unset($_SESSION['pve_last_time'], $_SESSION['pve_attack_interval']);
    }
    if (!isset($_SESSION['pve_last_time'])) {
        $_SESSION['pve_last_time'] = 0;
    }
    if (!isset($_SESSION['pve_attack_interval'])) {
        $_SESSION['pve_attack_interval'] = random_int(9, 18);
    }
    $interval = (int)$_SESSION['pve_attack_interval'];
    if ($now - (int)$_SESSION['pve_last_time'] < $interval) {
        return false;
    }
    $_SESSION['pve_last_time'] = $now;
    $_SESSION['pve_attack_interval'] = random_int(9, 18);

    // Определяем шанс нападения (обычные дикие)
    if (!($chance && $chance > 0 && $chance <= 100)) {
        $chance = self::chanseRandom(100, 5);
    }

    $timezone     = date('H:i:s') ?: '00:00:00';
    $chance_limit = random_int(10, 40);

    $BonusItemSbeg = Work::$sql->query(
        'SELECT `id` FROM `bafs` WHERE `baf` = 185 AND `time` > "'.time().'" AND `user` = '.$user_id
    )->fetch_assoc();

    // ===== SBeg =====
    $isSbeg   = false;
    $sbegPick = null;

    $sysRow = Work::$sql->query('SELECT sbeg_enabled, sbeg_multiplier FROM `system` LIMIT 1')->fetch_assoc();
    if ($poknum === 0 && empty($quest) && $sysRow && (int)$sysRow['sbeg_enabled'] === 1) {
        $baseDen = 60000;
        $mult    = (float)($sysRow['sbeg_multiplier'] ?? 1.0);

        if ($mult > 0) {
            $prob = min(1.0, max(0.0, $mult / $baseDen));
            $hit  = (mt_rand() / mt_getrandmax()) < $prob;

            if ($hit) {
                $res = Work::$sql->query('
                    SELECT id, basenum, lvl_min, lvl_max, `catch`, `weight`, deny_locations
                    FROM pve_sbeg
                    WHERE enabled = 1
                ');

                $pool = [];
                $sumW = 0;

                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        $denyCsv = trim((string)$r['deny_locations']);
                        $denyIds = $denyCsv !== '' ? array_filter(array_map('intval', explode(',', $denyCsv))) : [];
                        if (in_array($location_id, $denyIds, true)) {
                            continue;
                        }
                        $w = max(1, (int)$r['weight']);
                        $r['_w'] = $w;
                        $sumW += $w;
                        $pool[] = $r;
                    }
                }

                if ($sumW > 0) {
                    $roll = mt_rand(1, $sumW);
                    $acc  = 0;
                    foreach ($pool as $r) {
                        $acc += $r['_w'];
                        if ($roll <= $acc) {
                            $sbegPick = $r;
                            break;
                        }
                    }
                    if ($sbegPick) $isSbeg = true;
                }
            }
        }
    }
    // ===== /SBeg =====

    // --- Выбор покемона ---
    if ($poknum === 0) {
        if ($isSbeg && $sbegPick) {
            $select = [
                'id'      => 0,
                'basenum' => (int)$sbegPick['basenum'],
                'lvl'     => (int)$sbegPick['lvl_min'].','.(int)$sbegPick['lvl_max'],
                'catch'   => (int)$sbegPick['catch'],
                'type'    => 'sbeg'
            ];
        } elseif (!empty($BonusItemSbeg['id']) && (8 >= mt_rand(0, 150000))) {
            $select = Work::$sql->query('
                SELECT *
                FROM `pokemons_location`
                WHERE
                    `location_id` IN (0, '.$location_id.', 900000) AND
                    `chance` <= '.$chance.' AND
                    (
                        (`timezone` = "00:00:00" AND `timezone_b` = "00:00:00")
                        OR
                        ("'.$timezone.'" BETWEEN `timezone` AND `timezone_b`)
                    )
                ORDER BY `chance` DESC
                LIMIT '.$chance_limit
            );
        } else {
            $select = Work::$sql->query('
                SELECT *
                FROM `pokemons_location`
                WHERE
                    `location_id` IN (0, '.$location_id.', 900000) AND
                    `chance` >= '.$chance.' AND
                    (
                        (`timezone` = "00:00:00" AND `timezone_b` = "00:00:00")
                        OR
                        ("'.$timezone.'" BETWEEN `timezone` AND `timezone_b`)
                    )
                ORDER BY `chance` DESC
                LIMIT '.$chance_limit
            );
        }
    } else {
        $select = Work::$sql->query('SELECT * FROM `pokemons_location` WHERE `id` = '.$poknum)->fetch_assoc();
    }

    // --- Формируем список кандидатов ---
    $list = [];
    if (!empty($select)) {
        if ($poknum === 0 && !$isSbeg) {
            while ($selectInfo = $select->fetch_assoc()) {
                if (
                    $selectInfo && isset($selectInfo['basenum']) && (int)$selectInfo['basenum'] > 0 &&
                    (item_isset_baf($selectInfo['item']) || (int)$selectInfo['item'] === 0)
                ) {
                    if ((float)$selectInfo['chance'] >= mt_rand(0, 100)) {
                        $list[] = $selectInfo;
                    }
                }
            }
        } else {
            $list[] = $select;
        }
    }

    if (empty($list)) return false;

    shuffle($list);
    $list = reset($list);

    if (!isset($list['basenum'])) return false;

    $uInfo = self::_userInfoBattle($user_info['id'], 'pve', ['uinfo' => $user_info]);
    if (empty($uInfo)) return false;

    // --- Уровень ---
    $lvl = $list['lvl'];
    $lvl = explode(',', (string)$lvl);
    if (isset($lvl[0]) && (int)$lvl[0] > 0) {
        if (isset($lvl[1]) && (int)$lvl[1] > (int)$lvl[0]) {
            $lvl = mt_rand((int)$lvl[0], (int)$lvl[1]);
        } else {
            $lvl = (int)$lvl[0];
        }
    } else {
        $lvl = mt_rand(5, 25);
    }
    $lvl = (int)$lvl;
    if (!empty($lvl_boss)) $lvl = (int)$lvl_boss;

    $gen = mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21).','.mt_rand(10, 21);

    // =====================================================================
    // EX-BP (ОЧЕНЬ РЕДКО): единый ролл на 1 000 000 (без "_" для PHP < 7.4)
    // Пул 1: 2/1000000, Пул 2: 1/1000000
    // =====================================================================
    if (!$isSbeg && empty($quest)) {
        $roll = random_int(1, 1000000);

        if ($roll <= 2) {
            $pool1 = [58,86,211,343,412,417,509,556,632,674,829,852];
            $list['basenum'] = $pool1[array_rand($pool1)];
            $lvl = 15;
            $list['catch'] = 1;
        } elseif ($roll === 3) {
            $pool2 = [58,86,211,870,343,412,417,509,556,632,674,829,852,58,113,86,211,343,287,412,417,509,864,556,632,674,829,852,865];
            $list['basenum'] = $pool2[array_rand($pool2)];
            $lvl = 15;
            $list['catch'] = 1;
        }
    }
    // =====================================================================

    $boss_id = 0;
    $numb    = 0;

    // --- Квестовые покемоны ---
    if (!empty($quest)) {
        if ($quest == 1) { $lvl = 40;  $list['basenum'] = 182; $list['catch'] = 0; $numb = 1; }
        if ($quest == 2) { $lvl = 40;  $list['basenum'] = 59;  $list['catch'] = 0; $numb = 2; }
        if ($quest == 3) { $lvl = 130; $list['basenum'] = 144; $list['catch'] = 0; $numb = 3; }

        if ($quest >= 100) {
            $boss_base = Work::$sql->query('SELECT * FROM `base_boss` WHERE `id` = '.$quest)->fetch_assoc();
            if ($boss_base) {
                $list['basenum'] = (int)$boss_base['basenum'];
                $list['catch']   = 0;
                if ((int)$boss_base['type'] == 1) {
                    $numb = 9597;
                } elseif ((int)$boss_base['type'] == 2) {
                    $numb = 9596;
                } else {
                    $numb = 9595;
                }
            }
        }
    }

    // --- Премиальные шансы (баф 448) ---
    if (!$isSbeg) {
        $prem = Work::$sql->query('SELECT * FROM `bafs` WHERE `user` = '.$user_id.' AND `baf` = 448')->fetch_assoc();
        if ($prem && (int)$prem['time'] > time()) {
            $rand = rand(1, 7000);
            $r = rand(1, 6);
            if ($rand < 5) {
                if ($r == 1) { $list['basenum'] = 313; }
                if ($r == 2) { $list['basenum'] = 314; }
                if ($r == 3) { $list['basenum'] = 353; }
                if ($r == 4) { $list['basenum'] = 677; }
                if ($r == 5) { $list['basenum'] = 755; }
                if ($r == 6) { $list['basenum'] = 759; }
                $lvl = 15; $list['catch'] = 1;
            }
        }
    }

    // --- Квестовые этапы ---
    $d = Work::$sql->query('SELECT * FROM users WHERE id = '.$user_id)->fetch_assoc();
    $it_quest = Work::$sql->query("SELECT * FROM `user_quests` WHERE `quest_id` = 42 and `user_id` = ".$user_id)->fetch_assoc();
    if ($it_quest && (int)$d['location'] == 81) {
        if ((int)$it_quest['step'] == 2 && rand(1, 20) < 5)  { $list['basenum'] = 94;  $lvl = 50;  $numb = 420001; }
        if ((int)$it_quest['step'] == 3 && rand(1, 40) < 5)  { $list['basenum'] = 302; $lvl = 60;  $numb = 420002; }
        if ((int)$it_quest['step'] == 4 && rand(1, 60) < 5)  { $list['basenum'] = 442; $lvl = 70;  $numb = 420003; }
        if ((int)$it_quest['step'] == 5 && rand(1, 80) < 5)  { $list['basenum'] = 778; $lvl = 80;  $numb = 420004; }
        if ((int)$it_quest['step'] == 6 && rand(1, 80) < 5)  { $list['basenum'] = 356; $lvl = 90;  $numb = 420005; }
        if ((int)$it_quest['step'] == 7 && rand(1, 80) < 5)  { $list['basenum'] = 609; $lvl = 100; $numb = 420006; }
    }

    if ($d && (int)($d['location'] ?? 0) == 88 && (int)($d['military_limit'] ?? 0) == 0) {
        $list['catch'] = 0;
    }

    // --- Terastal wild encounter roll ---
    $teraWild = false;
    $teraWildForce = false;
    $teraTypeId = 0;
    $teraTypeSource = 'wild';
    $teraConfig = Work::$sql->query('SELECT * FROM `system` LIMIT 1')->fetch_assoc();
    if (!empty($teraConfig) && !empty($teraConfig['tera_wild_enabled'])) {
        $teraChance = (int)($teraConfig['tera_wild_chance'] ?? 0);
        if ($teraChance > 0) {
            if ($isSbeg) {
                $sbegMultiplier = (float)($teraConfig['tera_wild_sbeg_multiplier'] ?? $teraConfig['sbeg_multiplier'] ?? 1.0);
                if ($sbegMultiplier > 0) {
                    $teraChance = (int)min(10000, round($teraChance * $sbegMultiplier));
                }
            }

            $resonanceRemaining = (int)($_SESSION['tera_resonance_remaining'] ?? 0);
            $resonanceBonus = (int)($_SESSION['tera_resonance_bonus'] ?? 0);
            if ($resonanceRemaining > 0 && $resonanceBonus > 0) {
                $teraChance = (int)min(10000, round($teraChance * (1 + ($resonanceBonus / 100))));
                $_SESSION['tera_resonance_remaining'] = max(0, $resonanceRemaining - 1);
                if ($_SESSION['tera_resonance_remaining'] === 0) {
                    unset($_SESSION['tera_resonance_remaining'], $_SESSION['tera_resonance_bonus']);
                }
            }

            if (random_int(1, 10000) <= $teraChance) {
                $teraWild = true;
                $teraWildForce = !empty($teraConfig['tera_wild_force_terastallized']);
                $teraOfftypeRate = (int)($teraConfig['tera_wild_offtype_rate'] ?? 0);
                $allowStellar = !empty($teraConfig['tera_wild_allow_stellar']);

                $baseTypes = Work::$sql->query(
                    'SELECT `type`, `type_two` FROM `base_pokemons` WHERE `id` = '.(int)$list['basenum'].' LIMIT 1'
                )->fetch_assoc();

                $baseType = $baseTypes['type'] ?? 'normal';
                $baseTypeTwo = $baseTypes['type_two'] ?? '';
                $teraTypeId = self::_rollWildTeraType($baseType, $baseTypeTwo, $teraOfftypeRate, $allowStellar);
            }
        }
    }

    // --- Формируем pokeInfo ---
    $pokeInfo = [
        'id'       => 1,
        'location' => $location_id,
        'user'     => 0,
        'lvl'      => (int)$lvl,
        'basenum'  => (int)$list['basenum'],
        'gen'      => $gen,
        'catch'    => (!empty($list['catch']) ? 1 : 0),
        'wild'     => true,
        'id_pok'   => (int)($list['id'] ?? 0),
        'numb'     => (int)$numb,
        'boss'     => (int)$poknum,
        'type'     => (string)($list['type'] ?? ($isSbeg ? 'sbeg' : '')),
        'sparka'   => (int)($list['sparka'] ?? 0),
        'tera_type'=> ($teraWild ? $teraTypeId : (string)($list['tera_type'] ?? '')),
        'tera_type_source' => ($teraWild ? $teraTypeSource : ($list['tera_type_source'] ?? 'wild')),
        'tera_wild' => ($teraWild ? 1 : 0),
        'is_terastallized' => ($teraWildForce ? 1 : 0)
    ];
    if (!empty($list['atk_list'])) {
        $pokeInfo['atk_list'] = $list['atk_list'];
    }
    if (isset($list['chance'])) {
        $pokeInfo['chance'] = (float)$list['chance'];
    }

    $pInfo = self::_userInfoBattle(0, 'pve', [
        'npc' => [$pokeInfo],
        'uinfo' => [
            'user_group' => (!empty($list['catch']) ? 6 : 1),
            'catch'      => (!empty($list['catch']) ? 1 : 0),
        ]
    ]);

    if (empty($pInfo)) return false;

    $info_1 = Info::_parseData($uInfo);
    $info_2 = Info::_parseData($pInfo);

    $user_id_sql = (int)($uInfo['userInfo']['id'] ?? 0);
    $UserSelect = Work::$sql->query('SELECT location FROM users WHERE id = ' . $user_id_sql)->fetch_assoc();

    $location_value = (int)($UserSelect['location'] ?? 0);
    $LocationSelect = Work::$sql->query('SELECT region,img_fight,weather FROM base_location WHERE id = ' . $location_value)->fetch_assoc();

    $region_value = (int)($LocationSelect['region'] ?? 0);
    $WeatherNum = Work::$sql->query('SELECT weather FROM base_region WHERE id = ' . $region_value)->fetch_assoc();

    if (!empty($LocationSelect['weather'])) {
        $weather = $LocationSelect['weather'];
    } else {
        $weather = (string)($WeatherNum['weather'] ?? '');
    }
    $imgFight = (string)($LocationSelect['img_fight'] ?? '');

    $session_user_id = (int)($_SESSION['id'] ?? 0);
    $info_1_sql   = Work::$sql->real_escape_string($info_1);
    $info_2_sql   = Work::$sql->real_escape_string($info_2);
    $weather_sql  = Work::$sql->real_escape_string($weather);
    $imgFight_sql = Work::$sql->real_escape_string($imgFight);

    Work::$sql->query('INSERT INTO `battle`
        (`user_1`,`user_2`,`info_1`,`info_2`,`type`,`weather`,`img`)
        VALUES (
            ' . $session_user_id . ',
            0,
            "' . $info_1_sql . '",
            "' . $info_2_sql . '",
            "pve",
            "' . $weather_sql . '",
            "' . $imgFight_sql . '"
        )'
    );

    $status_id = (int)Work::$sql->insert_id;
    $update_user_id = (int)($user_info['id'] ?? 0);

    Work::$sql->query('UPDATE `users` SET
        `status` = "battle",
        `status_id` = ' . $status_id . '
        WHERE `id` = ' . $update_user_id
    );

    limit_pok_plus();

    $newBattle = Work::$sql->query('SELECT status_id FROM users WHERE id = ' . $user_id_sql)->fetch_assoc();
    $battle_status_id = (int)($newBattle['status_id'] ?? 0);

    if ($battle_status_id > 0 && $user_id_sql > 0) {
        Work::$sql->query('INSERT INTO battle_log (battle, round, text, end, user, starter)
            VALUES (' . $battle_status_id . ', 0, "", 0, ' . $user_id_sql . ', 1)');
    }

    return true;
}

public static function _pokeEXP($lvl, $exp_group, $exp, $exp_next){
    // $lvl — текущий уровень (1..100)
    // $exp — общий накопленный опыт персонажа (TOTAL XP)
    if ($lvl && $lvl > 0) {

        // Порог XP на текущем и следующем уровнях
        $xp_curr = self::_getExp($lvl,   $exp_group);
        $xp_next = self::_getExp(min($lvl + 1, 100), $exp_group);

        // Сколько нужно на ап уровня и сколько уже "влито" внутрь текущего уровня
        $need = max(0, $xp_next - $xp_curr);
        $into = max(0, min($need, $exp - $xp_curr));

        // Процент прогресса по текущему уровню
        $pct  = ($lvl < 100) ? (int) floor($need > 0 ? ($into * 100 / $need) : 100) : 100;

        // Сколько XP осталось до апа (абсолютное значение)
        $left = ($lvl < 100) ? (int) ($need - $into) : 0;

        return [
            'val'  => $pct,  // 0..100 — прогресс в % до следующего уровня
            'next' => $left  // оставшийся XP до апа
        ];
    }
    return [];
}

public static function _getExp($lvl, $group){
    // ВНИМАНИЕ: убран искусственный сдвиг уровня
    // $lvl = $lvl + 1;  <-- было неверно
    $exp = 0;

    switch($group){

        case '1': // Erratic (600,000)
            if ($lvl <= 50) {
                $exp = floor( (pow($lvl, 3) * (100 - $lvl)) / 50 );
            } elseif ($lvl <= 68) {
                $exp = floor( (pow($lvl, 3) * (150 - $lvl)) / 100 );
            } elseif ($lvl <= 98) {
                // корректное целочисленное округление дробной части:
                $t = (int) floor( (1911 - 10 * $lvl) / 3 );
                $exp = floor( (pow($lvl, 3) * $t) / 500 );
            } elseif ($lvl <= 100) {
                $exp = floor( (pow($lvl, 3) * (160 - $lvl)) / 100 );
            } else {
                $exp = floor( (pow($lvl, 3) * (160 - $lvl)) / 100 );
            }
            break;

        case '2': // Fast (800,000)
            $exp = floor( (4 * pow($lvl, 3)) / 5 );
            break;

        case '3': // Medium Fast (1,000,000)
            $exp = (int) pow($lvl, 3);
            break;

        case '4': // Medium Slow (1,059,860)
            $exp = floor( ((6/5) * pow($lvl, 3)) - (15 * pow($lvl, 2)) + (100 * $lvl) - 140 );
            break;

        case '5': // Slow (1,250,000)
            $exp = floor( (5 * pow($lvl, 3)) / 4 );
            break;

        case '6': // Fluctuating (1,640,000)
            if ($lvl <= 15) {
                $exp = floor( pow($lvl, 3) * ( (( $lvl + 1 ) / 3 + 24) / 50 ) );
            } elseif ($lvl <= 36) {
                $exp = floor( pow($lvl, 3) * ( ($lvl + 14) / 50 ) );
            } elseif ($lvl <= 100) {
                // ИСПРАВЛЕНО: было ( ( ($lvl + 2) + 32 ) / 50 )
                $exp = floor( pow($lvl, 3) * ( ($lvl + 64) / 100 ) );
            }
            break;
    }

    if ($exp <= 0) $exp = 0;
    return (int) $exp;
}

    public static function _updatePokeLose($pokeInfo){

        if($pokeInfo && isset($pokeInfo['id'])){

            
                $pokeInfo = new PokeBattle($pokeInfo, true);
                Work::$sql->query('UPDATE `user_pokemons` SET
                                            `hp` = '.intval($pokeInfo->hp).',
                                            `basenum` = '.intval($pokeInfo->basenum).',
                                            `name_new` = "'.$pokeInfo->name_new.'",
                                            `stats` = "'.$pokeInfo->_getStats(true).'",
                                            `happy` = "'.$pokeInfo->happy.'",
                                            `lvl` = '.$pokeInfo->_getLvl().',
                                            `ev` = '.$pokeInfo->_getEvCount().',
                                            `exp` = '.$pokeInfo->_getExp().',
                                            `exp_max` = '.$pokeInfo->_getExpNext().',
                                            `pp_attacks` = "'.$pokeInfo->pp_my.'"
                                          WHERE `id` = '.$pokeInfo->_getID());

                return $pokeInfo->_getData();

        }

        return $pokeInfo;
    }

public static function _updatePokeExp($pokeInfo, $Enemy = false, $onlyHP = false)
{
    if (!$pokeInfo || !isset($pokeInfo['id'])) {
        return $pokeInfo;
    }

    if (!$onlyHP) {
        if (isset($pokeInfo['lvl'], $pokeInfo['exp'], $pokeInfo['exp_max'], $pokeInfo['base_exp_group'])) {

            /* ====== ДОБАВЛЕНО: более «терпимый» триггер на начисление опыта ======
               Если actionCount не передали — считаем, что действие было (1). */
            $tookAction = (int)($pokeInfo['actionCount'] ?? 1) > 0;

            if ($tookAction) {
                /* Собираем «уровень»(и) противника: targetLvl может быть массивом или числом */
                $enemy_lvl_sum = 0;
                if (isset($pokeInfo['targetLvl'])) {
                    if (is_array($pokeInfo['targetLvl'])) {
                        foreach ($pokeInfo['targetLvl'] as $val) {
                            $v = (int)$val;
                            if ($v > 0) $enemy_lvl_sum += $v;
                        }
                    } else {
                        $v = (int)$pokeInfo['targetLvl'];
                        if ($v > 0) $enemy_lvl_sum = $v;
                    }
                }

                /* Если targetLvl не пришёл, но передали $Enemy — используем его как «вес боя»,
                   чтобы опыт всё равно начислялся. */
                $hasEnemyWeight = is_numeric($Enemy) && (float)$Enemy > 0;

                if ($enemy_lvl_sum > 0 || $hasEnemyWeight) {
                    // Множители
                    $ExpBonus = Work::$sql->query("SELECT `exp` FROM `system` WHERE `id` = 1")->fetch_assoc();
                    $expRate  = isset($ExpBonus['exp']) ? (float)$ExpBonus['exp'] : 0.0;
                    // Если в базе хранится «20» как 20%, конвертируем в 0.20
                    if ($expRate > 1.0) {
                        $expRate = $expRate / 100.0;
                    } elseif ($expRate < 0.0) {
                        $expRate = 0.0;
                    }

                    $ozg = ((int)($pokeInfo['item_id'] ?? 0) === 158) ? 1.15 : 1.0;

                    $bafprem = Work::$sql->query('SELECT * FROM `bafs` WHERE `type` = 3 AND `user` = ' . intval($_SESSION['id']))->fetch_assoc();
                    $bafpr   = ($bafprem && (int)$bafprem['time'] > time()) ? 2.0 : 1.0;

                    $us_qw = Work::$sql->query('SELECT `location` FROM `users` WHERE `id` = ' . intval($_SESSION['id']))->fetch_assoc();
                    $location = (int)($us_qw['location'] ?? 0);

                    // Блокирующие условия: предмет 154, локации 57/75, покемон мёртв
                    if (
                        (int)($pokeInfo['item_id'] ?? 0) === 154 ||
                        $location === 57 ||
                        $location === 75 ||
                        (int)($pokeInfo['hp'] ?? 0) === 0
                    ) {
                        // опыт не начисляем
                    } else {
                        $util = achiv_utility(8) ? 1.07 : 1.0;

                        // БАЗОВАЯ ФОРМУЛА EXP:
                        // если передали targetLvl — используем «уровень противников»,
                        // если только $Enemy — используем его как вес (минимум 1).
                        $lvlSelf   = max(1, (int)$pokeInfo['lvl']);
                        $lvlEnemy  = max(1, (int)$enemy_lvl_sum);
                        $weight    = $hasEnemyWeight ? (float)$Enemy : 1.0;

                        // Если нет targetLvl, но есть вес — пусть будет «условный» уровень противника
                        if ($enemy_lvl_sum <= 0 && $hasEnemyWeight) {
                            $lvlEnemy = max(1, (int)round($lvlSelf)); // не даём 0
                        }

                        // «Псевдо-покемоновская» формула — даёт не 0:
                        $base_calc = (float)floor((($weight * $lvlEnemy) / 5.0) * (pow((2 * $lvlEnemy + 10), 2.5) / pow(($lvlEnemy + $lvlSelf + 10), 2.5)) + 1.0);
                        if ($base_calc < 1.0) $base_calc = 1.0;

                        $gain = $base_calc * (1.0 + $expRate) * $ozg * $bafpr * $util;

                        // Итог: прибавляем опыт
                        $pokeInfo['exp'] = (float)$pokeInfo['exp'] + $gain;
                    }
                }

                // Чистим служебные поля, как и раньше
                if (isset($pokeInfo['targetLvl'])) unset($pokeInfo['targetLvl']);
                if (isset($pokeInfo['actionCount'])) unset($pokeInfo['actionCount']);
            }

            $lvl = intval($pokeInfo['lvl']) + 1;

            // Повышение уровней
            while ($pokeInfo['exp'] >= $pokeInfo['exp_max'] && $pokeInfo['lvl'] < 100) {
                $pokeInfo['lvl'] = intval($pokeInfo['lvl']) + 1;

                // Квест/ачивки
                if (check_mission_ivent(44) && $pokeInfo['lvl'] == 100) {
                    add_mission_ivent(44);
                }
                if ($pokeInfo['lvl'] == 100) {
                    update_achiv(8, 1);
                    update_achiv(19, 1);
                    update_achiv(20, 1);
                }

                // Счастье
                $happyAll = (int)($pokeInfo['happy'] ?? 0) + 2;
                $pokeInfo['happy'] = ($happyAll > 255) ? 255 : $happyAll;

                // EV
                if (isset($pokeInfo['ev'])) {
                    // Жёсткие проверки:
                    // - конкретно на этом покемоне должен быть предмет с item_id == 563
                    // - стартовый флаг называется startGame (проверяем именно его)
                    $hasEvCharm = isset($pokeInfo['item_id']) && ((int)$pokeInfo['item_id'] === 563);
                    $isStartGame = isset($pokeInfo['startGame']) && ((int)$pokeInfo['startGame'] === 1);

                    // Проверяем системный бонус skoba (из таблицы system для id = 1)
                    $SkobaBouns = Work::$sql->query("SELECT `skoba` FROM `system` WHERE `id` = 1")->fetch_assoc();
                    $skobaActive = (int)($SkobaBouns['skoba'] ?? 0) === 1;

                    if (!isset($pokeInfo['ev'])) $pokeInfo['ev'] = 0;

                    // По умолчанию +2 EV.
                    // +3 только при конкретных условиях (жёсткая проверка):
                    // - этот покемон — стартовый (startGame == 1)
                    // - или на этом покемоне надет предмет item_id == 563 (только на нём, не у пользователя)
                    // - или в system.skoba == 1
                    $evAdd = 2;
                    if ($isStartGame || $hasEvCharm || $skobaActive) {
                        $evAdd = 3;
                    }

                    $pokeInfo['ev'] = (int)$pokeInfo['ev'] + $evAdd;

                    if (check_mission_ivent(14)) {
                        add_mission_ivent(14);
                    }
                }

                // Новый порог опыта
                $lvlNext = intval($pokeInfo['lvl']);
                if ($lvlNext <= 100) {
                    $exp = self::_getExp($lvlNext, $pokeInfo['base_exp_group']);
                    if ($exp > 0) {
                        $pokeInfo['exp_max'] = $exp;
                    }
                }
            }
        }
    }

    // Обновление в БД
    if (Work::$sql) {
        $pokeBattle = new PokeBattle($pokeInfo, true);
        Work::$sql->query('UPDATE `user_pokemons` SET
            `hp` = ' . intval($pokeBattle->hp) . ',
            `basenum` = ' . intval($pokeBattle->basenum) . ',
            `name_new` = "' . $pokeBattle->name_new . '",
            `stats` = "' . $pokeBattle->_getStats(true) . '",
            `happy` = "' . $pokeBattle->happy . '",
            `lvl` = ' . $pokeBattle->_getLvl() . ',
            `ev` = ' . $pokeBattle->_getEvCount() . ',
            `exp` = ' . $pokeBattle->_getExp() . ',
            `exp_max` = ' . $pokeBattle->_getExpNext() . ',
            `pp_attacks` = "' . $pokeBattle->pp_my . '"
            WHERE `id` = ' . $pokeBattle->_getID()
        );

        return $pokeBattle->_getData();
    }

    return $pokeInfo;
}


    public static function addAbility($id,$abil,$slot){
      Work::$sql->query('UPDATE `user_pokemons` SET
                                  `ability` = '.$abil.',
                                  `ability_slot` = '.$slot.'
                                WHERE `id` = '.$id);
    }

    public static function addForm($id, $form){
      Work::$sql->query('UPDATE user_pokemons SET form = "'.$form.'" WHERE id = '.$id);
    }
    public static function _arrRandDefOne(array $array){
        if(!empty($array)){
            $rand_keys = array_rand($array);
            return ( (is_array($rand_keys) && isset($rand_keys[0])) ? $array[$rand_keys[0]] : $array[$rand_keys]);
        }
        return null;
    }

    // public static function _modif($led) {
    //   if(!is_array($led)){
    //       $led = self::_unParseData($led);
    //   }
    //   return [
    //       'gen'=>$led['genUp'],
    //       'char'=>$led['charEdit']
    //   ];
    // }

    public static function _pokeBirthday($birthday){
        if(!is_array($birthday)){
            $birthday = self::_unParseData($birthday);
        }

        $user = [];

        if(isset($birthday['user_id']) && $birthday['user_id'] > 0){

            if(isset(self::$userCache['u'.$birthday['user_id']])){
                $user = self::$userCache['u'.$birthday['user_id']];
            }else{
                $user = Work::$sql->query("SELECT `id`, `login`, `user_group` FROM `users` WHERE `id` = ".intval($birthday['user_id']))->fetch_assoc();

                if(isset($user['id'])){
                    self::$userCache['u'.$user['id']] = $user;
                }else{
                    $user = [];
                }
            }

        }

        return [
            'user'=>'<div class="user-link u-'.(isset($user['user_group']) ? $user['user_group'] : 6).'">'.(isset($user['login']) ? $user['login'] : '???').'</div>',
            'date'=>date('d.m.Y в H:i ', (isset($birthday['date']) ? $birthday['date'] : time()))
        ];
    }

    public static function chanseRandom($value, $value_max = 20){
        $value_min = intval(ceil($value/2));
        $value_min = ($value_min <= 0 ? 1 : $value_min);
        if(mt_rand(1, 100) <= $value_max){
            return mt_rand(1, $value);
        }
        return mt_rand($value_min, $value);
    }

    public static function _shableShop($npc_id, $uInfo = null, &$response = []){

        if($npc_id && !empty($_SESSION['id'])){

            $uInfo = (($uInfo && !empty($uInfo['id'])) ? $uInfo : Work::$sql->query('
                SELECT
                  `id`,
                  `sex`,
                  `login`,
                  `location`
                FROM `users`
                WHERE `id` = '.intval($_SESSION['id']).'
            ')->fetch_assoc());

            if(!empty($uInfo['id'])){

                if(is_array($npc_id)){

                    if(!empty($npc_id['item']) && !empty($npc_id['npc']) && $npc_id['item'] > 0 && $npc_id['npc'] > 0){

                        $npc_id['npc']    = intval($npc_id['npc']);
                        $npc_id['item']   = intval($npc_id['item']);
                        $npc_id['count']  = intval(abs($npc_id['count']));

                        if($npc_id['count'] <= 0){
                            _setError('Вы будете брать или нет?!');
                        }

                        $checkItem = Work::$sql->query('
                                        SELECT
                                          `id`,
                                          `npc_id`,
                                          `location_id`,
                                          `item_id`,
                                          `item_price`,
                                          `item_type`,
                                          `item_count`
                                        FROM `items_npc`
                                        WHERE
                                          `npc_id` = '.$npc_id['npc'].' AND
                                          `item_id` = '.$npc_id['item'].'
                                    ')->fetch_assoc();

                        if(!empty($checkItem['item_id']) && $checkItem['item_price'] > 0 && ($checkItem['location_id'] == $uInfo['location'] || $checkItem['location_id'] == 0)){

                            if($npc_id['count'] > 0 && ($checkItem['item_count'] >= $npc_id['count'] || $checkItem['item_count'] == -1)){

                                $price = ($npc_id['count'] * $checkItem['item_price']);
                                $type_item = intval($checkItem['item_type']);

                                if(item_isset($type_item, $price)){
                                    if($checkItem['item_type'] == 480){
                                        
                                    $arena = Work::$sql->query('SELECT `arena` FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
                                    $l = explode(',',$arena['arena']);
                                    $l[1] = $l[1]+$price;
                                    if($l[1] <= 130){
                                        
                                        itemAdd(intval($checkItem['item_id']), $npc_id['count']);
                                    $itemName = Work::$sql->query('SELECT name FROM base_items WHERE id = '.$checkItem['item_id'])->fetch_assoc();
                                    $itemName1 = Work::$sql->query('SELECT name FROM base_items WHERE id = '.$checkItem['item_type'])->fetch_assoc();
                                    $response['by_ok'] = 'Успешная покупка!';
                                    $response['by_minus'] = '<img src="/img/world/items/little/'.$checkItem['item_type'].'.png" class="item"> '.$itemName1['name'].' <b>x'.$price.'</b>';
                                    $response['by_plus'] = '<img src="/img/world/items/little/'.$checkItem['item_id'].'.png" class="item"> '.$itemName['name'].' <b>x'.$npc_id['count'].'</b>';
                                    $response['by_count'] = $checkItem['item_count'];
                                    
                                    $ids = implode(',',$l);
                                    Work::$sql->query('
                                            UPDATE `users` SET
                                              `arena` = "'.$ids.'"
                                            WHERE `id` = '.$_SESSION['id'].'
                                        ');

                                    return true;
                                        
                                        
                                    }else{
                                        _setError('Этой покупкой вы превысите лимит в 130 потраченых жетонов!');
                                    }
                                    }else{
                                        minus_item($type_item, $price);
                                    if(check_mission_ivent(4)){ add_mission_ivent(4,$price);}
                                    itemAdd(intval($checkItem['item_id']), $npc_id['count']);
                                    $itemName = Work::$sql->query('SELECT name FROM base_items WHERE id = '.$checkItem['item_id'])->fetch_assoc();
                                    $itemName1 = Work::$sql->query('SELECT name FROM base_items WHERE id = '.$checkItem['item_type'])->fetch_assoc();
                                    $response['by_ok'] = 'Успешная покупка!';
                                    $response['by_minus'] = '<img src="/img/world/items/little/'.$checkItem['item_type'].'.png" class="item"> '.$itemName1['name'].' <b>x'.$price.'</b>';
                                    $response['by_plus'] = '<img src="/img/world/items/little/'.$checkItem['item_id'].'.png" class="item"> '.$itemName['name'].' <b>x'.$npc_id['count'].'</b>';

                                    if($checkItem['item_count'] != -1){

                                        $checkItem['item_count'] -= $npc_id['count'];
                                        if($checkItem['item_count'] <= 0){
                                            $checkItem['item_count'] = 0;
                                        }

                                        Work::$sql->query('
                                            UPDATE `items_npc` SET
                                              `item_count` = '.intval($checkItem['item_count']).'
                                            WHERE `id` = '.intval($checkItem['id']).'
                                        ');

                                        $response['by_count'] = $checkItem['item_count'];
                                    }

                                    return true;
                                    }
                                    
                                    
                                    
                                    

                                }else{

                                    _setError('Недостаточно средств для совершения покупки!');
                                }

                            }else{

                                if(isset($checkItem['item_count']) && $checkItem['item_count'] == 0){
                                    _setError('Данный предмет уже распродан!');
                                }

                                if(isset($checkItem['item_count']) && $npc_id['count'] > $checkItem['item_count']){
                                    _setError('Запрашиваемое кол-во привышает кол-во оставшиеся у продовца.');
                                }

                                _setError('Данный предмет уже распродан!');
                            }

                        }else{
                            _setError('Что-то пошло не так как надо...');
                        }

                    }else{
                        _setError('Что-то пошло не так как надо...');
                    }

                }elseif($npc_id > 0){
					$listItemsInfo = [];
                    $listItems = Work::$sql->query('
                                    SELECT

                                      `in`.`item_id`,
                                      `in`.`item_price`,
                                      `in`.`item_count`,
                                      `in`.`item_type`,

                                      `bi_2`.`name` AS `type_name`,
									  `bi_2`.`id` AS `id_buyer`,

                                      `bi`.`name`,
                                      `bi`.`about`,
                                      `bi`.`type`

                                    FROM `items_npc` AS `in`
                                    INNER JOIN `base_items` AS `bi`
                                      ON `bi`.`id` = `in`.`item_id`
                                    INNER JOIN `base_items` AS `bi_2`
                                      ON `bi_2`.`id` = `in`.`item_type`
                                    WHERE
                                      `in`.`npc_id` = '.intval($npc_id).' AND
                                      (`in`.`location_id` = 0 OR `in`.`location_id` = '.intval($uInfo['location']).') AND
                                      `in`.`item_id` > 0
                                ');
					while($row = $listItems->fetch_assoc()){ $listItemsInfo[] = $row; }
                    if(!empty($listItemsInfo)){

                        $html = '<div class="market">';

                        foreach($listItems AS $key=>$value){
                            if(isset($value['name'])){
                                $html .=   '<div class="item __npc_item_'.$npc_id.'">
                                                <img src="/img/world/items/little/'.$value['item_id'].'.png" onclick="issetAll('.$value['item_id'].',\'shopitem\','.$npc_id.')">

                                            </div>';
                            }
                        }
                        unset($key,$value);

                        $html .= '</div>';

                        $response['by_list'] = $html;

                        return true;

                    }else{
                        _setError('Этот персонаж не торгует предметами.');
                    }

                }else{
                    _setError('Ошибка доступа.');
                }

            }else{

                _setError('Ошибка доступа.');

            }

        }

        return '';
    }
}

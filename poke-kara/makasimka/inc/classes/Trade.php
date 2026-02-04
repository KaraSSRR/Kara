<?php
class Trade{

    /**@var $response array */
    private $response;

    /**@var $response array */
    private $responseClass = [];

    /**@var $tradeInfo array */
    private $tradeInfo = [];

    /**@var $sql mysqli */
    private $sql;

    /**@var $userID int */
    private $userID;

    /**@var $opponentID int */
    private $opponentID;

    /**@var $userData array */
    private $userData = [];

    /**@var $opponentData array */
    private $opponentData = [];

    /**@var $userInfo array */
    private $userInfo = [];

    /**@var $opponentInfo array */
    private $opponentInfo = [];

    /**@var $userConfirmed integer */
    private $userConfirmed = 0;

    /**@var $opponentConfirmed integer */
    private $opponentConfirmed = 0;

    /**@var $update array */
    private $update = [];

    /**@var $pokeListId array */
    private $pokeListId = [];

    /**@var $pokeInfo array */
    private $pokeListInfo = [];


    public function __construct(mysqli $mysqli, array $userInfo = [], array &$response_makasimka){
        $this->sql = $mysqli;
        $this->userID = intval($_SESSION['id']);
        $this->response =& $response_makasimka;

        if($userInfo && isset($userInfo['status_id'])){
            $this->tradeInfo = $this->sql->query("SELECT * FROM `users_trade` WHERE `id`='".$userInfo['status_id']."'")->fetch_assoc();
        }

        if(
            $this->tradeInfo &&
            isset(
                $this->tradeInfo['id'],
                $this->tradeInfo['user1'],
                $this->tradeInfo['user2']
            ) &&
            $this->tradeInfo['user1'] > 0 &&
            $this->tradeInfo['user2'] > 0
        ){

            $this->parser();

        }else{

            $this->resetAction();

        }
    }


    private function parser(){

        $this->parserOpponent();

        if(isset($_POST['addObject'])){
            $this->addObject();
        }elseif(isset($_POST['removeObject'])){
            $this->removeObject();
        }elseif(isset($_POST['confirmed'])){
            $this->confirmed(intval($_POST['confirmed']));
        }elseif(isset($_POST['exit'])){
            $this->trades(true);
        }

        $response = [
            'my'=>$this->userData,
            'enemy'=>$this->opponentData,
            'myObj'=>$this->showObject($this->userInfo),
            'enemyObj'=>$this->showObject($this->opponentInfo, true),
            'myConfirmed'=>$this->userConfirmed,
            'enemyConfirmed'=>$this->opponentConfirmed,
            'pokeInfoList'=>$this->showPokeList(),
        ];

        if(isset($this->opponentInfo['trades'])){
            $response['trades'] = $this->opponentInfo['trades'];
        }

        if(isset($this->userInfo['trades'])){
            $response['trades'] = $this->userInfo['trades'];
        }

        $response = array_merge($response, $this->responseClass);

        $this->response['tradeInfo'] = $response;

    }

    private function confirmed($type){

        if($type <= 0){

            if($this->opponentConfirmed > 0){
                return;
            }

            $this->userConfirmed = 0;
            $this->opponentConfirmed = 0;
            $this->update['confirm_1'] = 0;
            $this->update['confirm_2'] = 0;

        }else{

            if($this->opponentConfirmed <= 0){
                $this->userConfirmed = $type;
                $this->update['confirmed'] = $type;
            }else{
                $this->trades();
            }

        }

    }

    private function trades($exit = false){

        if($this->opponentConfirmed > 0 || $exit){

            if(empty($this->userInfo['trades']) && empty($this->opponentInfo['trades']) && $this->tradeInfo['user1'] > 0 &&  $this->tradeInfo['user2'] > 0){

                if(!empty($this->userInfo['objects'])){

                    foreach($this->userInfo['objects'] AS $key=>$value){
                        $this->updateObject($value, ($exit ? $this->userID : $this->opponentID));
                    }

                    unset($key,$value);

                }

                if(!empty($this->opponentInfo['objects'])){

                    foreach($this->opponentInfo['objects'] AS $key=>$value){
                        $this->updateObject($value, ($exit ? $this->opponentID : $this->userID));
                    }

                    unset($key,$value);

                }

                if(!$exit){
                    Info::_logGame($this->opponentID, 'TRADE_CPL', [
                        'user_to'=>$this->userID,
                        'objects'=>(isset($this->userInfo['objects']) ? $this->userInfo['objects'] : ''),
                        'action_id'=>$this->tradeInfo['id'],
                    ], 'trade');
                    Info::_logGame($this->userID, 'TRADE_CPL', [
                        'user_to'=>$this->opponentID,
                        'objects'=>(isset($this->opponentInfo['objects']) ? $this->opponentInfo['objects'] : ''),
                        'action_id'=>$this->tradeInfo['id'],
                    ], 'trade');
                    
                    update_achiv(9,1,$this->userID);
                    update_achiv(9,1,$this->opponentID);
                }

                $this->update['info'] = [
                    'trades'=>($exit ? 1 : 10)
                ];

                $this->responseClass['trades'] = ($exit ? 1 : 10);

            }

            $this->resetAction(true);

        }

    }

    private function updateObject($object, $toUser){
        if($toUser > 0 && $object && is_array($object)){

            $id    = $object['id'];
            $type  = $object['type'];

            if($type === 'item'){

                if(isset($object['retarget'])){

                    $this->sql->query("
                        DELETE
                          FROM `items_users`
                          WHERE
                             `id` = '".$id."'
                    ");

                }
                 if(!empty($object['str'])) {
                   $this->sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$toUser."','".$object['number']."',1,'".$object['str']."') ");
                 }elseif(!empty($object['dop'])){
                     $this->sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`dop`) VALUES ('".$toUser."','".$object['number']."',1,'".$object['dop']."') ");
                 }else{
                   itemAdd($object['number'],intval($object['count']), $toUser);
                 }

            }elseif($type === 'egg'){
                $this->sql->query("
                            UPDATE `user_egg`
                              SET `user` = ".$toUser."
                            WHERE
                              `id` = '".$id."'
                        ");
            }elseif($type === 'poke'){
                $this->sql->query("
                            UPDATE `user_pokemons`
                              SET `user_id` = ".$toUser.", `team_id`= '0'
                            WHERE
                              `id` = '".$id."'
                        ");
            }

        }
    }

    private function removeObject(){

        if(
            isset(
                $_POST['removeObject'],
                $_POST['objectID']
            ) &&
            $_POST['objectID'] > 0
        ){

            $type = $_POST['removeObject'];
            $id   = $_POST['objectID'];
            $key  = $type.'_'.$id;

            if(isset($this->userInfo['objects'][$key])){

                $info = $this->userInfo['objects'][$key];

                if(isset($info['id'])){


                    $this->update['info'] = [
                        'update'=>((isset($this->userInfo['update']) ? $this->userInfo['update'] : 0) + 1)
                    ];


                    if($type === 'item'){

                        if(isset($info['retarget'])){

                            $this->sql->query("
                            UPDATE `items_users`
                              SET
                                `user` = ".$this->userID.",
                                `count` = ".intval($info['count'])."
                            WHERE
                              `id` = '".$info['id']."'
                        ");

                        }else{

                            $this->sql->query("
                            UPDATE `items_users`
                              SET `count` = `count` + ".intval($info['count'])."
                            WHERE
                              `id` = '".$info['id']."'
                        ");

                        }

                    }elseif($type === 'egg'){
                        $this->sql->query("
                            UPDATE `user_egg`
                              SET `user` = ".$this->userID."
                            WHERE
                              `id` = '".$info['id']."'
                        ");
                    }elseif($type === 'poke'){
                        $this->sql->query("
                            UPDATE `user_pokemons`
                              SET `user_id` = ".$this->userID.", `team_id`= '0'
                            WHERE
                              `id` = '".$info['id']."'
                        ");

                        if(isset($this->userInfo['count_poke'])){
                            $this->userInfo['count_poke'] -= 1;
                            if($this->userInfo['count_poke'] <= 0){
                                $this->userInfo['count_poke'] = 0;
                            }
                        }
                    }

                    $this->userConfirmed = 0;
                    $this->opponentConfirmed = 0;
                    $this->update['confirm_1'] = 0;
                    $this->update['confirm_2'] = 0;

                    unset($this->userInfo['objects'][$key]);

                }

            }

        }
    }

    private function addObject(){

        if(
            isset(
                $_POST['addObject'],
                $_POST['objectID'],
                $_POST['objectCount']
            ) &&
            $_POST['objectID'] > 0 &&
            $_POST['objectCount'] > 0
        ){

            $this->update['info'] = [
                'update'=>((isset($this->userInfo['update']) ? $this->userInfo['update'] : 0) + 1)
            ];

            if(!isset($this->userInfo['objects'])){
                $this->userInfo['objects'] = [];
            }

            $_POST['objectCount'] = abs(intval($_POST['objectCount']));

            $type = $_POST['addObject'];
            $id   = $_POST['objectID'];
            $key  = $type.'_'.$id;


            if($type === 'item'){
                $info = $this->sql->query("
                  SELECT
                    `iu`.*,
                    `bi`.`name`,
                    `bi`.`about`,
                    `bi`.`trade`
                  FROM `items_users` AS `iu`
                  INNER JOIN `base_items` AS `bi`
                    ON `bi`.`id` = `iu`.`item_id`
                  WHERE
                    `iu`.`id` = '".$id."' AND
                    `iu`.`user` = '".$this->userID."'
                ")->fetch_assoc();
            }elseif($type === 'egg'){
                $info = $this->sql->query("
                  SELECT
                     `ue`.*,
                     `bp`.`name_rus`
                  FROM `user_egg` AS `ue`
                  INNER JOIN `base_pokemons` AS `bp`
                    ON `bp`.`id` = `ue`.`basenum`
                  WHERE
                    `ue`.`id` = '".$id."' AND
                    `ue`.`user` = '".$this->userID."'
                ")->fetch_assoc();
            }elseif($type === 'poke'){

                if(!$this->checkCountPoke()){
                    $this->responseClass['error'] = 1;
                    $this->responseClass['text'] = 'У оппонента недостаточно места для этого покемона.';
                    return;
                }

                $info = $this->sql->query("
                  SELECT *
                  FROM `user_pokemons`
                  WHERE
                    `id` = '".$id."' AND
                    `user_id` = '".$this->userID."'
                ")->fetch_assoc();
            }

            if(!empty($info)){

                $retarget = false;

                if($type === 'item'){
                    if(!(isset($info['trade']) && $info['trade'] == 'true')){
                        $this->responseClass['error'] = 1;
                        $this->responseClass['text'] = 'Этот предмет нельзя передать.';
                        return;
                    }

                    if($_POST['objectCount'] > $info['count']){
                        $_POST['objectCount'] = $info['count'];
                    }

                    $info['count'] = ($info['count'] - $_POST['objectCount']);

                    if($info['count'] <= 0){
                        $retarget = 2;
                        $this->sql->query("
                            UPDATE `items_users`
                              SET `user` = 2
                            WHERE
                              `id` = '".$info['id']."'
                        ");
                    }else{
                        $this->sql->query("
                            UPDATE `items_users`
                              SET `count` = ".intval($info['count'])."
                            WHERE
                              `id` = '".$info['id']."'
                        ");
                    }

                }elseif($type === 'egg'){
                    $this->sql->query("
                            UPDATE `user_egg`
                              SET `user` = 2
                            WHERE
                              `id` = '".$info['id']."'
                        ");
                }elseif($type === 'poke'){
                  if(!(isset($info['trade']) && $info['trade'] == 'true')){
                      $this->responseClass['error'] = 1;
                      $this->responseClass['text'] = 'Этого покемона нельзя передать.';
                      return;
                  }
                    if(isset($info['item_id']) && $info['item_id'] > 0 && $info['item_id'] != 107){
                      if(!empty($info['item_str'])) {
                        $this->sql->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`str`) VALUES ('".$this->userID."','".$info['item_id']."',1,'".$info['item_str']."') ");
                      }else{
                        itemAdd($info['item_id'], 1, $this->userID);
                      }
						$this->sql->query("
                            UPDATE `user_pokemons`
                              SET `item_id` = 0, `item_str`= ''
                            WHERE
                              `id` = '".$info['id']."'
                        ");
                    }

                    $this->sql->query("
                            UPDATE `user_pokemons`
                              SET `user_id` = 2, `team_id`= '0', `start_pok` = '0'
                            WHERE
                              `id` = '".$info['id']."'
                        ");

                    if(!isset($this->userInfo['count_poke'])){
                        $this->userInfo['count_poke'] = 1;
                    }else{
                        $this->userInfo['count_poke'] += 1;
                    }
                }

                if(isset($this->userInfo['objects'][$key])){

                    $this->userInfo['objects'][$key]['count'] += $_POST['objectCount'];

                    if($retarget){
                        $this->userInfo['objects'][$key]['retarget'] = $retarget;
                    }

                }else{

                    $arrInfo = [
                        'type'=>$type,
                        'id'=>$info['id'],
                        'count'=>$_POST['objectCount'],
                        'name'=>(isset($info['name']) ? $info['name'] : '~ x ~')
                    ];

                    if(isset($info['item_id']) && isset($info['about'])){
                        if(!empty($info['str'])) {
                          $arrInfo['str'] = $info['str'];
                          $str = explode(',',$arrInfo['str']);
                          $arrInfo['str_exp'] = '['.$str[0].'/'.$str[1].']';
                        }else{
                          $arrInfo['str'] = '';
                          $arrInfo['str_exp'] = '';
                        }
                        if(!empty($info['dop'])) {
                          $arrInfo['dop'] = $info['dop'];
                          $arrInfo['dop_exp'] = ', '.$info['dop'].'%';
                        }else{
                          $arrInfo['dop'] = '';
                          $arrInfo['dop_exp'] = '';
                        }
                        $arrInfo['str'] = (!empty($info['str']) ? $info['str'] : '');
                        $arrInfo['dop'] = (!empty($info['dop']) ? $info['dop'] : '');
                        $arrInfo['number'] = $info['item_id'];
                        $arrInfo['about'] = $info['about'];

                        if($retarget){
                            $arrInfo['retarget'] = $retarget;
                        }
                    }

                    if(isset($info['gens'], $info['basenum'])){
                        $arrInfo['number'] = $info['basenum'];
                        $arrInfo['name'] = 'Яйцо #'.$info['basenum'].' '.$info['name_rus'];
                        $arrInfo['gens'] = $info['gens'];

                        $rebornEnd = $info['reborn'] - time();
                        $reborn = date('j', $rebornEnd);
                        $arrInfo['reborn'] = ($reborn > 0) ? 'Вылупится через '.$reborn.' дн.' : 'Готовиться к вылуплению...';
                    }

                    if(isset($info['basenum'], $info['lvl'])){
                        $arrInfo['number'] = $info['basenum'];
                        $arrInfo['name'] = $info['name_new'].' '.$info['lvl'].' ур.';
                        $arrInfo['poke_type'] = $info['type'];
                    }

                    $this->userInfo['objects'][$key] = $arrInfo;
                }

                $this->userConfirmed = 0;
                $this->opponentConfirmed = 0;
                $this->update['confirm_1'] = 0;
                $this->update['confirm_2'] = 0;
            }

        }
    }

    private function showObject($info = [], $hidden = false){
        $return = [];

        if($info && isset($info['objects']) && is_array($info['objects'])){

            foreach($info['objects'] AS $key=>$value){

                if(isset($value['type']) && $value['type'] == 'poke'){
                    $this->pokeListId[] = intval($value['id']);

                    if($hidden){
                        $value['hidden'] = true;
                    }

                }else{
                    if($hidden && isset($value['id'])) unset($value['id']);
                }

                if(isset($value['retarget'])) unset($value['retarget']);

                $return[] = $value;
            }

        }

        return $return;
    }

    private function showPokeList(){
        if(!empty($this->pokeListId)){
			$infoInfo = [];
            $info = $this->sql->query("
                  SELECT
                    `exp`,
                    `exp_max`,
                    `attacks`,
                    `basenum`,
                    `birthday`,
                    `character`,
                    `ev`,
                    `evcounts`,
                    `gen`,
                    `gender`,
                    `happy`,
                    `hp`,
                    `id`,
                    `lvl`,
                    `name_new` AS `name`,
                    `sparka`,
                    `stats`,
                    `tren`,
                    `tren_stat`,
                    `type`,
                    `vitamines`,
                    `pp_attacks`,
                    `trade`,
                    `sparkaNumber`,
                    `form`,
                    `ability`,
                    `ability_slot`,
                    `static`,
                    `tren_log`
                  FROM `user_pokemons`
                  WHERE
                    `id` IN (".implode(',', $this->pokeListId).")
                ");
			while($row = $info->fetch_assoc()){ $infoInfo[] = $row; }
            $return = [];
            if(!empty($infoInfo)){
                foreach($info AS $key=>$value){

                  $p = $this->sql->query('SELECT * FROM `base_pokemons` WHERE `id` = "'.(int)$value['basenum'].'" ')->fetch_object();

                  $explvllow = Info::_getExp($value['lvl']-1, $p->exp_group);
                  $value['exp1'] = $value['exp']-$explvllow;
                  $value['exp2'] = $value['exp_max']-$explvllow;

                    $atks = explode(",", $value['attacks']);
                    $pps = explode(",", $value['pp_attacks']);
                    
                    
                    $gen = explode(',',$value['gen']);
      $hpgen[0] = ((intval($gen[0]) % 2) == 0 ? 0 : 1); // HP
$hpgen[1] = ((intval($gen[1]) % 2) == 0 ? 0 : 1); // Atk
$hpgen[2] = ((intval($gen[2]) % 2) == 0 ? 0 : 1); // Def
$hpgen[3] = ((intval($gen[5]) % 2) == 0 ? 0 : 1); // Spe (Speed is gen[5]!)
$hpgen[4] = ((intval($gen[3]) % 2) == 0 ? 0 : 1); // SpA (Sp.Atk is gen[3]!)
$hpgen[5] = ((intval($gen[4]) % 2) == 0 ? 0 : 1); // SpD (Sp.Def is gen[4]!)

$hptype = floor((($hpgen[0] + 2 * $hpgen[1] + 4 * $hpgen[2] + 8 * $hpgen[3] + 16 * $hpgen[4] + 32 * $hpgen[5]) * 15) / 63);

$hptypenumrus = [
  'Боевой',        // 0
  'Летающий',      // 1
  'Ядовитый',      // 2
  'Земляной',      // 3
  'Каменный',      // 4
  'Насекомое',     // 5
  'Призрачный',    // 6
  'Стальной',      // 7
  'Огненный',      // 8 🔥
  'Водный',        // 9
  'Травяной',      // 10
  'Электрический', // 11
  'Психический',   // 12
  'Ледяной',       // 13
  'Дракон',        // 14
  'Темный'         // 15
];

          $value['hidden'] = 'Тип скрытой силы '.$hptypenumrus[$hptype];
          
      $tr_l = explode(',',$value['tren_log']);
          $tren_log = '<div class="tren_path">
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr1"></i>
                                </div>
                                <span>'.$tr_l[0].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr2"></i>
                                </div>
                                <span>'.$tr_l[1].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr3"></i>
                                </div>
                                <span>'.$tr_l[2].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr4"></i>
                                </div>
                                <span>'.$tr_l[3].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-angle-double-up tr5"></i>
                                </div>
                                <span>'.$tr_l[4].'</span>
                            </div>
                            <div>
                                <div>
                                    <i class="trening fas fa-crown tr6"></i>
                                </div>
                                <span>'.$tr_l[5].'</span>
                            </div>
                        </div>';
            $value['tren_log']  = $tren_log; 
                    
                    if($value['ability'] != 0){
          $value['abl'] = '<div onclick="viewDescriptionAbility('.$value['ability'].');" class="AbilityPok">'.ability_name($value['ability']).'</div>';
      }else{
          $value['abl'] = 'Отсутствует';
      }
                    if(!empty($atks[0])) {
                      $atks1 = $this->sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atks[0])->fetch_assoc();
                    }else{
                      $atks1 = '';
                    }
                    if(!empty($atks[1])) {
                      $atks2 = $this->sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atks[1])->fetch_assoc();
                    }else{
                      $atks2 = '';
                    }
                    if(!empty($atks[2])) {
                      $atks3 = $this->sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atks[2])->fetch_assoc();
                    }else{
                      $atks3 = '';
                    }
                    if(!empty($atks[3])) {
                      $atks4 = $this->sql->query("SELECT * FROM `base_atk` WHERE `id` = ".$atks[3])->fetch_assoc();
                    }else{
                      $atks4 = '';
                    }

                    if($atks1 && $atks1['category'] == 'special') {
                      $c1 = 2;
                    }elseif($atks1 && $atks1['category'] == 'physical') {
                      $c1 = 1;
                    }else{
                      $c1 = 3;
                    }
                    if($atks2 && $atks2['category'] == 'special') {
                      $c2 = 2;
                    }elseif($atks2 && $atks2['category'] == 'physical') {
                      $c2 = 1;
                    }else{
                      $c2 = 3;
                    }
                    if($atks3 && $atks3['category'] == 'special') {
                      $c3 = 2;
                    }elseif($atks3 && $atks3['category'] == 'physical') {
                      $c3 = 1;
                    }else{
                      $c3 = 3;
                    }
                    if($atks4 && $atks4['category'] == 'special') {
                      $c4 = 2;
                    }elseif($atks4 && $atks4['category'] == 'physical') {
                      $c4 = 1;
                    }else{
                      $c4 = 3;
                    }
                    if(!empty($atks1)) {
                    $value['atk1'] = '<div class="Move">
              <img src="/img/world/typs/'.$atks1['type'].'.png">
              <div class="MoveInfo">
                <div class="Name MoveCategory'.$c1.'">'.$atks1['name_rus'].'</div>
                <div class="PP">'.$pps[0].'/'.$atks1['pp'].' PP</div>
              </div>
            </div>';
          }else{
            $value['atk1'] = '<div class="Move">
      <img src="/img/world/typs/empty.png">
      <div class="MoveInfo">
        <div class="Name MoveCategory">Нет атаки</div>
        <div class="PP">0/0 PP</div>
      </div>
      </div>';
          }
            if(!empty($atks2)) {
              $value['atk2'] = '<div class="Move">
        <img src="/img/world/typs/'.$atks2['type'].'.png">
        <div class="MoveInfo">
          <div class="Name MoveCategory'.$c2.'">'.$atks2['name_rus'].'</div>
          <div class="PP">'.$pps[1].'/'.$atks2['pp'].' PP</div>
        </div>
      </div>';
    }else{
      $value['atk2'] = '<div class="Move">
<img src="/img/world/typs/empty.png">
<div class="MoveInfo">
  <div class="Name MoveCategory">Нет атаки</div>
  <div class="PP">0/0 PP</div>
</div>
</div>';
    }
if(!empty($atks3)) {
    $value['atk3'] = '<div class="Move">
<img src="/img/world/typs/'.$atks3['type'].'.png">
<div class="MoveInfo">
<div class="Name MoveCategory'.$c3.'">'.$atks3['name_rus'].'</div>
<div class="PP">'.$pps[2].'/'.$atks3['pp'].' PP</div>
</div>
</div>';
}else{
  $value['atk3'] = '<div class="Move">
<img src="/img/world/typs/empty.png">
<div class="MoveInfo">
<div class="Name MoveCategory">Нет атаки</div>
<div class="PP">0/0 PP</div>
</div>
</div>';
}
if(!empty($atks4)) {
$value['atk4'] = '<div class="Move">
<img src="/img/world/typs/'.$atks4['type'].'.png">
<div class="MoveInfo">
<div class="Name MoveCategory'.$c4.'">'.$atks4['name_rus'].'</div>
<div class="PP">'.$pps[3].'/'.$atks4['pp'].' PP</div>
</div>
</div>';
}else{
  $value['atk4'] = '<div class="Move">
<img src="/img/world/typs/empty.png">
<div class="MoveInfo">
<div class="Name MoveCategory">Нет атаки</div>
<div class="PP">0/0 PP</div>
</div>
</div>';
}

$haras = $this->sql->query("SELECT * FROM `har` WHERE `id_har` = '".$value['character']."' ")->fetch_assoc();
      if($haras['atk'] == 1.1){$value['har1'] = "Green-Color";}elseif($haras['atk'] == 0.9){$value['har1'] = "Red-Color";}else{ $value['har1'] = "";}
      if($haras['def'] == 1.1){$value['har2'] = "Green-Color";}elseif($haras['def'] == 0.9){$value['har2'] = "Red-Color";}else{ $value['har2'] = "";}
      if($haras['speed'] == 1.1){$value['har3'] = "Green-Color";}elseif($haras['speed'] == 0.9){$value['har3'] = "Red-Color";}else{ $value['har3'] = "";}
      if($haras['satk'] == 1.1){$value['har4'] = "Green-Color";}elseif($haras['satk'] == 0.9){$value['har4'] = "Red-Color";}else{ $value['har4'] = "";}
      if($haras['sdef'] == 1.1){$value['har5'] = "Green-Color";}elseif($haras['sdef'] == 0.9){$value['har5'] = "Red-Color";}else{ $value['har5'] = "";}
      
                    if(isset($value['id'])){

                        $value['character'] = haracter_pokes($value['character']);
                        $value['birthday'] = Info::_pokeBirthday($value['birthday']);

                        $return['p'.$value['id']] = $value;

                    }
                }
                unset($key,$value);
            }


            return $return;
        }
        return [];
    }

    private function parserOpponent(){

        if($this->tradeInfo['user1'] == $this->userID){

            $this->opponentID   = intval($this->tradeInfo['user2']);
            $this->opponentInfo = $this->_unParseData($this->tradeInfo['info_2']);
            $this->opponentConfirmed = intval($this->tradeInfo['confirm_2']);

            $this->userInfo      = $this->_unParseData($this->tradeInfo['info_1']);
            $this->userConfirmed = intval($this->tradeInfo['confirm_1']);

        }else{

            $this->opponentID   = intval($this->tradeInfo['user1']);
            $this->opponentInfo = $this->_unParseData($this->tradeInfo['info_1']);
            $this->opponentConfirmed = intval($this->tradeInfo['confirm_1']);

            $this->userInfo     = $this->_unParseData($this->tradeInfo['info_2']);
            $this->userConfirmed = intval($this->tradeInfo['confirm_2']);

        }

        $this->userData     = $this->getUserData($this->userID);
        $this->opponentData = $this->getUserData($this->opponentID);


        if($this->opponentID <= 0){
            $this->resetAction(true);
        }
    }

    private function unParserUpdate(array $update){
        $return = [];

        if($this->tradeInfo['user1'] == $this->userID){

            if(isset($update['info'])){
                $return['info_1'] = $this->_parseData(array_merge($this->userInfo, $update['info']));
                unset($update['info']);
            }

            if(isset($update['confirmed'])){
                $return['confirm_1'] = intval($update['confirmed']);
                unset($update['confirmed']);
            }

        }else{

            if(isset($update['info'])){
                $return['info_2'] = $this->_parseData(array_merge($this->userInfo, $update['info']));
                unset($update['info']);
            }

            if(isset($update['confirmed'])){
                $return['confirm_2'] = intval($update['confirmed']);
                unset($update['confirmed']);
            }

        }

        return array_merge($update, $return);
    }

    private function resetAction($onlyMy = false){

        if(isset($this->tradeInfo['id'])){

            if(isset($this->opponentInfo['trades']) || $this->tradeInfo['user1'] <= 0 || $this->tradeInfo['user2'] <= 0){

                $this->sql->query("
                    DELETE
                      FROM `users_trade`
                      WHERE
                        (
                            `user1` = '".$_SESSION['id']."' OR
                            `user2` = '".$_SESSION['id']."'
                        )
                ");

            }

            if($onlyMy){

                $this->sql->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$this->userID."'");


                if($this->tradeInfo['user1'] == $this->userID){
                    $this->sql->query("UPDATE `users_trade` SET `user1` = '0' WHERE `id` = '".$this->tradeInfo['id']."'");
                }else{
                    $this->sql->query("UPDATE `users_trade` SET `user2` = '0' WHERE `id` = '".$this->tradeInfo['id']."'");
                }

            }else{
                if(isset($this->tradeInfo['user1']) && $this->tradeInfo['user1'] > 0){
                    $this->sql->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$this->tradeInfo['user1']."'");
                }

                if(isset($this->tradeInfo['user2']) && $this->tradeInfo['user2'] > 0){
                    $this->sql->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$this->tradeInfo['user2']."'");
                }
            }


        }else{
            $this->sql->query("UPDATE `users` SET `status` = 'free' WHERE `id` = '".$this->userID."'");
        }

        $this->responseClass['trade'] = 0;
    }

    private function getUserData($userID){
        if($userID && $userID > 0){
            return  $this->sql->query("
              SELECT
                `id`,
                `login`,
                `user_group` AS `group`,
                `sex`,
                `rang`
              FROM `users`
              WHERE
                `id` = '".intval($userID)."'"
            )->fetch_assoc();
        }
        return [];
    }

    private function checkCountPoke(){

        $count = (isset($this->userInfo['count_poke']) ? $this->userInfo['count_poke'] : 0);

        $info = $this->sql->query("
                  SELECT COUNT(*) AS `count`
                  FROM `user_pokemons`
                  WHERE
                    `active` = 1 AND
                    `user_id` = '".$this->opponentID."'
                ")->fetch_assoc();

        if($info && isset($info['count'])){

            if($info['count'] >= 6){
                return false;
            }

            if(($info['count'] + $count) >= 6){
                return false;
            }

        }

        return true;
    }

    /**
     * @param $data array
     * @return string
     */
    private function _parseData(array $data = []){
        $return = false;
        if(!empty($data)){
            try{
                if(function_exists('json_encode')){
                    $return = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                }else{
                    $return = serialize($data);
                }
            }catch (Exception $e){
                $return = '{}';
            }
        }
        return ($return ? $return : '{}');
    }

    /**
     * @param $data string
     * @return array
     */
    private function _unParseData($data = null){
        $return = [];
        if(!empty($data)){
            try{
                if(function_exists('json_decode')){
                    $return = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
                }else{
                    $return = unserialize($data);
                }
            }catch (Exception $e){
                $return = [];
            }
        }
        return ($return ? $return : []);
    }


    public function __destruct(){

        if(!empty($this->update)){

            $this->update = $this->unParserUpdate($this->update);

            $tpl = [];

            foreach($this->update AS $key=>$value){

                $tpl[] = ' `'.$key.'` = "'. $this->sql->real_escape_string($value).'" ';

            }

            if($tpl){
                $this->sql->query("UPDATE `users_trade`
                    SET ".implode(',', $tpl)."
                    WHERE `id` = '".$this->tradeInfo['id']."'");
            }

        }

    }
}

<?php
/**
 * @property number id
 *
 * @property string login
 * @property string ban
 *
 */

class MyTalk{

    /**@var mysqli $_mysqli */
    private $_mysqli   = null;
    private $_userData = [];

    private $page_user = 0;
    private $page_user_count = 100;

    private $page_msg = 0;
    private $page_msg_count = 75;
    private $page_msg_last_id = 0;

    private $response = [];

    public function __construct($userData = [], $mysqli){
        $this->_mysqli   = $mysqli;
        $this->_userData = array_replace($this->_userData, $userData);
    }

    public function &__get($name){

        if(array_key_exists($name, $this->_userData)){
            return $this->_userData[$name];
        }

        $this->_userData[$name] = null;

        return $this->_userData[$name];

    }
    public function __set($name, $value){
        $this->_userData[$name] = $value;
    }
    public function __isset($name){
        return isset($this->_userData[$name]);
    }
    public function __unset($name){
        if(isset($this->_userData[$name]))
            unset($this->_userData[$name]);
    }
    public function __toString(){
        return '';
    }


    private function hash($user_2){

        $user_2 = intval($user_2);
        $user_1 = intval($this->id);

        if($user_1 > $user_2){
            $hash = 'start-u-'.$user_1.'-to-u-'.$user_2.'-end';
        }else{
            $hash = 'start-u-'.$user_2.'-to-u-'.$user_1.'-end';
        }

        return md5($hash);

    }

    private function target($user_id = 0, $page = null, $user_init = null){

        $user_id = ($user_id ? $user_id : ($_POST['user_target'] ?? 0));
        $user_id = intval($user_id);

        if($user_id == $this->id){
            return false;
        }

        $this->clearCounter();

        if($user_id > 0){

            $hash_talk = $this->hash($user_id);

            if(is_null($page)){

                if(!$user_init){

                    $this->_mysqli->query('
                        UPDATE `users_talk` SET
                             `target` = 0
                        WHERE `user_1` = '.$this->id
                    );

                }

                $this->_mysqli->query('
						INSERT INTO `users_talk` 
								(`user_1`, `user_2`, `target`, `count_msg`, `talk_hash`, `last_target`) 
						VALUES  ('.$this->id.', '.$user_id.', 1, 0, "'.$hash_talk.'", 0) 
						
						ON DUPLICATE KEY 
						
						UPDATE 
							 `target`    = 1,
							 `count_msg` = 0
				');

            }

            $where = '';

            if(!is_null($page)){
                $where .= ' AND `id` < '.$this->page_msg_last_id.' ';
            }

            $msgList = $this->_mysqli->query('
                SELECT 
                    `id`, `user_1`, `user_2`, `text`, `date`
                FROM `users_talk_msg` 
                WHERE 
                  `hash` = "'.$hash_talk.'" '.$where.'
                ORDER BY `id` DESC
                LIMIT '.$this->page_msg_count.' 
           ');

            for($set = []; $row = $msgList->fetch_assoc(); $set[] = $row);

            $return = [
                'user_target'=>$user_id,
                (is_null($page) ? 'msg_list' : 'msg_last')=>(!empty($set) ? $set : [])
            ];

            if($user_init){
                return $return;
            }else{
                $this->response['msgList'] = $return;
            }

            return true;

        }

        return false;

    }

    private function clearCounter(){
        $_SESSION['talkLastMsgId'] = 0;
        Work::$sql->query('DELETE FROM `users_talk_notice` WHERE `user_to` = '.intval($this->id));
    }

    private function open(){

        $_SESSION['talkMsgOpen'] = time() + 300;

        $return = [];
        $init   = intval($_POST['init_to_user'] ?? 0);

        $this->_mysqli->query('
            UPDATE `users_talk` SET
                 `target` = 0
            WHERE `user_1` = '.$this->id
        );

        $this->clearCounter();

        if($init > 0){

            $return = $this->target($init, null, true);

            if(!$return || !is_array($return)){
                $return = [];
            }

        }


        $list_users = $this->_mysqli->query('
			SELECT 
				`u`.`id` ,
                `u`.`login` ,
                `u`.`user_group` ,
                `u`.`sex`,
				`ut`.`count_msg` AS `count_msg`
			FROM `users_talk` AS `ut`
			INNER JOIN `users` AS `u`
				ON `u`.`id` = `ut`.`user_2`
			WHERE 
				`ut`.`user_1` = '.$this->id.'
			ORDER BY 
				`ut`.`count_msg` DESC,
				`ut`.`last_target` DESC
			'.Info::page($this->page_user, $this->page_user_count).'
		');

        for($set = []; $row = $list_users->fetch_assoc(); $set[] = $row);

        $return['users_list'] = (!empty($set) ? $set : []);

        $this->response['myTalk'] = $return;
    }

    private function addMsg($user_id = 0, $msg = ''){

        $_SESSION['talkMsgOpen'] = time() + 300;

        $user_id = ($user_id ? $user_id : ($_POST['user_target'] ?? 0));
        $user_id = intval($user_id);

        if($user_id == $this->id){
            return false;
        }

        $ban = ($this->ban ? Info::_unParseData($this->ban) : null);
        if($ban && isset($ban['chat']) && $ban['chat'] > time()){
            _setError('Вы будете молчать еще '.downcounter($ban['chat']));
            return false;
        }

        $msg = ($msg ? $msg : ($_POST['send_target'] ?? ''));
        $msg = strval($msg);

        if(!empty($msg)){

            $msg = preg_replace("/  +/"," ", $msg);

            $msg = trim($msg);
            $msg = stripslashes($msg);
            $msg = htmlspecialchars($msg);

        }

        $strlen = strlen($msg);

        if($strlen > 300000){
            _setError('Превышено максимально допустимое количество знаков, в одном сообщении.', 2);
            return false;
        }

        $chat = new GameChat($this->_mysqli);

        $msg = preg_replace_callback("@(https?://)?(([a-zA-Z0-9.-]+)?[a-zA-Z0-9-]+(!?\.[a-zA-Z]{2,5}))+(/[^\s]*)?@", [$chat, 'preg_link'], $msg);

        $msg = preg_replace_callback("/\#([0-9]{1,10})/",  [$chat, 'preg_poke_sprites'], $msg, 10);
        $msg = preg_replace_callback("/\#s([0-9]{1,10})/", [$chat, 'preg_poke_sprites_s'], $msg, 10);

        $msg = preg_replace_callback("/\%i([0-9]{1,10})/", [$chat, 'preg_item'], $msg, 10);

        $msg = preg_replace_callback("/\%random([0-9]{1,10})/", [$chat, 'preg_random'], $msg, 10);
        $msg = preg_replace_callback("/\%or/", [$chat, 'preg_or'], $msg, 10);

        if($user_id > 0 && !empty($msg)){

            if(!$this->filter($msg)){
                _setError('К сожалению, этим сообщением, вы нарушили правила игры!', null, 2);
                return false;
            }

            $hash_talk = $this->hash($user_id);

            $info = $this->_mysqli->query('
               SELECT  *  
               FROM `users_talk`
               WHERE 
                  `user_1` = '.$user_id.' AND `user_2` = '.$this->id. '
            ')->fetch_array(MYSQLI_ASSOC);

            $count_msg = 1;

            if(!empty($info['talk_hash'])){

                if($info['target'] <= 0 || $this->isAfk($info['last_target'])){
                    $count_msg = (intval($info['count_msg']) + 1);
                }else{
                    $count_msg = 0;
                }

                $this->_mysqli->query('
                  UPDATE `users_talk` SET
                    `count_msg`   = '.$count_msg.',
                    `last_target` = '.time().'
                  WHERE `user_1` = '.$user_id.' AND `talk_hash` = "'.$info['talk_hash'].'"
                ');

            }else{

                $this->_mysqli->query('
                    INSERT INTO
                      `users_talk`
                        (`user_1`, `user_2`, `count_msg`, `talk_hash`, `last_target`)
                    VALUE
                        ('.$user_id.', '.$this->id.', '.$count_msg.', "'.$hash_talk.'", '.time().')
                ');

            }

            $this->_mysqli->query('
                  UPDATE `users_talk` SET
                    `count_msg`   = 0,
                    `last_target` = '.time().'
                  WHERE `user_1` = '.$this->id.' AND `talk_hash` = "'.$hash_talk.'"
                ');

            $msg_data = [
                'user_1'=>intval($this->id),
                'user_2'=>$user_id,
                'text'  =>$msg,
                'date'  =>time()
            ];

            $this->_mysqli->query('
                    INSERT INTO
                      `users_talk_msg`
                        (`user_1`, `user_2`, `text`, `date`, `hash`)
                    VALUE
                        ('.$msg_data['user_1'].', '.$msg_data['user_2'].', "'.$this->_mysqli->real_escape_string($msg_data['text']).'", '.$msg_data['date'].', "'.$hash_talk.'")
                ');

            if($this->_mysqli->insert_id > 0){

                $msg_data['id'] = $this->_mysqli->insert_id;

                if($msg_data['id'] > 0){
                    $this->_mysqli->query('
                        INSERT INTO
                          `users_talk_notice`
                            (`user_to`, `user_from`, `msg_id`)
                        VALUE
                            ('.$user_id.', '.$this->id.', '.$msg_data['id'].')
                    ');
                }

                $this->response['msgInfo'] = [
                    'user_target'=>$user_id,
                    'msg_add'    =>[ $msg_data ]
                ];

                return true;

            }

        }

        return false;

    }

    private function userlist(){

        $_SESSION['talkMsgOpen'] = time() + 300;

        $login = trim($_POST['search_login'] ?? '');
        $login = preg_replace("/  +/"," ", $login);
        $login = trim($login);
        $login = stripslashes($login);
        $login = htmlspecialchars($login);

        if(!empty($login)){

            //$login = Work::$db->parse_str($login);

            $sql =  $this->_mysqli->query('
				SELECT 
					`id`,`login`,`user_group`,`online`,`sex`
				FROM `users` 
				WHERE `login` 
				LIKE "%'.$login.'%" 
				ORDER BY `login` ASC
				LIMIT 500
			');

            for($set = []; $row = $sql->fetch_assoc(); $set[] = $row);

            if(!empty($set)){
                $this->response['userListSearch'] = $set;
            }

            return;
        }

    }

    private function multineedle_stripos($haystack, $needles, $offset = 0) {

        $found = [];

        foreach($needles as $needle) {

            $pos = stripos($haystack, $needle, $offset);

            if($pos !== false){
                $found[$needle] = $pos;
            }

        }

        return (!empty($found) ? $found : false);

    }

    private function filter($msg){

        $muted = 0;

        if($msg){

            $msg = str_replace(["\r", "\n", "\s", "&amp;", "&nbsp;", " "],"", $msg);

            if($this->multineedle_stripos($msg, [
                'pokeland_online',
                'pokeland',
                'рokeland',
                'pоkeland',
                'pokеland',
                'pokeland',
                'orthrusonline',
                'parkpokemon',
                'orthrus',
                'оrthrus',
                'league17',
                'league-17',
                'лига17',
                'лигу17',
                'лиги17',
                'очрус',
                'очруса',
                'орчрус',
                'орчруса',
                'ворд18',
                'ворлд18',
                'world18',
            ])){

                $muted = 3600; // Кол-во секунд

            }

        }

        if($muted > 0){

            // Тут идет выдача авто-мута.

            $ban = Info::_unParseData($this->ban);
            $ban = Info::_parseData(array_merge($ban, [
                'chat'=> time() + $muted
            ]));

            $this->_mysqli->query("UPDATE `users` SET `ban`='".$this->_mysqli->real_escape_string($ban)."' WHERE `id`='".$this->id."'");

            return false;

        }

        return true;

    }

    private function closed(){

        $_SESSION['talkMsgOpen'] = 0;

        $this->_mysqli->query('
            UPDATE `users_talk` SET
                 `target` = 0
            WHERE `user_1` = '.$this->id
        );

    }

    private function isAfk($count){

        if(abs(time() - $count) >= 5){
            return true;
        }

        return false;

    }

    public static function myCounts(mysqli $_mysqli, $user_id = null){

        $count = $_mysqli->query('
               SELECT 
                  SUM(`count_msg`) AS `counts`   
               FROM `users_talk`
               WHERE 
                  `user_1` = ' .intval(($user_id ? $user_id : $_SESSION['id'])). '
            ')->fetch_array(MYSQLI_ASSOC);

        return intval(($count['counts'] ?? 0));

    }

    public function getInit($init = false){

        if(!isset($_SESSION['talkLastMsgId'])){
            $_SESSION['talkLastMsgId'] = 0;
        }

        $last_count = $this->_mysqli->query('
               SELECT 
                  COUNT(`id`) AS `counts`   
               FROM `users_talk_notice`
               WHERE `user_to` = '.$this->id.'
            ')->fetch_array(MYSQLI_ASSOC);

        $last_count = ($last_count['counts'] ?? 0);

        if($_SESSION['talkLastMsgId'] != $last_count &&  !$init){

            if($last_count > 0){

                $msges = $this->_mysqli->query('
                    SELECT 
                        `ut`.`id`, `ut`.`user_1`, `ut`.`user_2`, `ut`.`text`, `ut`.`date`,
                        `u2`.`id` AS `u_id`,
                        `u2`.`login` ,
                        `u2`.`user_group` ,
                        `u2`.`sex` 
                    FROM `users_talk_msg` AS `ut`
                    INNER JOIN `users` AS `u2`
                      ON `u2`.`id` = `ut`.`user_1`
                    WHERE 
                      `ut`.`id` IN (SELECT `msg_id` FROM `users_talk_notice` WHERE `user_to` = '.$this->id.')  
                    ORDER BY `ut`.`id` DESC
                    LIMIT '.$this->page_msg_count.' 
               ');

                for($set = []; $row = $msges->fetch_assoc(); $set[] = $row);

                $this->clearCounter();

                $this->response['newCountMsgList'] = $set;

            }

        }

        $this->response['newCountMsg'] = self::myCounts($this->_mysqli, $this->id);

        $_SESSION['talkLastMsgId'] = $last_count;

    }

    public function response(){

        $type = ($_POST['type'] ?? '');
        $this->page_msg = intval(abs(($_POST['page_msg'] ?? 0)));
        $this->page_msg_last_id = intval(abs($_POST['last_msg'] ?? 0));

        if(!$type){
            return [];
        }

        switch ($type){
            case 'list'  : $this->open(); break;
            case 'send'  : $this->addMsg(); break;
            case 'target': $this->target(); break;
            case 'closed':  $this->closed(); break;
            case 'userlist'   : $this->userlist(); break;
            case 'target_page': if($this->page_msg_last_id > 0)  $this->target(0, $this->page_msg); break;
        }

        return $this->response;

    }

    public function getResponse(){
        return $this->response;
    }

}
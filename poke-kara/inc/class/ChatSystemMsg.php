<?php

class ChatSystemMsg {
    private $mysqli;

    /**
     * Конструктор
     * @param mysqli $mysqli
     */
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    /**
     * Отправляет системное сообщение в чат
     * @param string $msg
     */
    public function send($msg) {
        if ($this->mysqli === null) {
            throw new Exception('Ошибка: $this->mysqli не инициализирован!');
        }
        $user_id = "1";
        $user_login = 'System';
        $user_sex = 'm';
        $user_group = "1";
        $user_msg_color = "4";
        $msg_type = 0;
        $msg_time = date('H:i');
        $msg_class = "";
        $msg_region_id = "0";
        $msg_region_name = "";
        $img = "system";

        $info = json_encode([
            'user_id'         => $user_id,
            'user_login'      => $user_login,
            'user_sex'        => $user_sex,
            'user_group'      => $user_group,
            'user_msg_color'  => $user_msg_color,
            'user_msg'        => $msg,
            'msg_type'        => $msg_type,
            'msg_time'        => $msg_time,
            'msg_class'       => $msg_class,
            'msg_region_id'   => $msg_region_id,
            'msg_region_name' => $msg_region_name,
            'img'             => $img
        ], JSON_UNESCAPED_UNICODE);

        $type = 0;
        $toUser = 0;
        $location = 0;
        $clan = 0;
        $lifetime = time() + 2 * 3600;

        $this->mysqli->query("INSERT INTO chat_new
            (`type`, `user`, `touser`, `location`, `clan`, `info`, `lifetime`, `img`, `img_to`)
            VALUES
            ($type, $user_id, $toUser, $location, $clan, '".$this->mysqli->real_escape_string($info)."', $lifetime, '$img', '')
        ");
    }
}
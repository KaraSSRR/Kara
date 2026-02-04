<?php
// === Подключение к инфраструктуре ===
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}
session_start();
if(!isset($_SESSION['id']) || !isset($_COOKIE['hash'])){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit;
}
$user = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
if($_COOKIE['hash'] != $user['hash']){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// === Функция проверки активации предмета ===
function isset_item_activation($id) {
    global $user, $mysqli;
    $res = $mysqli->query("SELECT 1 FROM `user_activ_items` WHERE `user` = {$user['id']} AND `item` = ".intval($id));
    return $res && $res->num_rows > 0;
}

// === Функция получения основных данных пользователя (для примера) ===
function getMainUser($id) {
    global $mysqli;
    $u = $mysqli->query("SELECT `id`,`login`,`user_group` FROM `users` WHERE `id` = ".intval($id))->fetch_assoc();
    return $u ?: [];
}

// === Класс для update ===
class Update {

    private $response = [];
    private $userInfo = [];
    private $updLocation = 0;

    private function goToCapital($from) {
        global $mysqli;
        require_once($_SERVER['DOCUMENT_ROOT'].'/matsuka/constants/aviableRegions.php');
        $stmt = $mysqli->prepare("SELECT region FROM base_location WHERE id = ?");
        $stmt->bind_param("i", $from);
        $stmt->execute();
        $data = $stmt->get_result();
        $region = $data->fetch_assoc();
        $loc = (isset(\matsukaConstants\aviableRegions[$region["region"]]))
            ? \matsukaConstants\aviableRegions[$region["region"]]
            : \matsukaConstants\aviableRegions[1];
        $stmtU = $mysqli->prepare("UPDATE users SET location = ? WHERE id = ?");
        $stmtU->bind_param("ii", $loc, $this->userInfo['id']);
        $stmtU->execute();
        $this->updLocation = $loc;
    }

    public function __construct($type, array &$userInfo = [], array &$response = []) {
        if(!$type) return;
        $this->response =& $response;
        $this->userInfo =& $userInfo;
        $requestType = $_POST['type'] ?? $_GET['type'] ?? '';
        switch($requestType) {
            case 'every':
                global $mysqli;
                // Уведомления
                $stmt = $mysqli->prepare("SELECT `id` FROM `notification` WHERE `checked` = 1 AND `user` = ?");
                $stmt->bind_param("i", $userInfo['id']);
                $stmt->execute();
                $data = $stmt->get_result();
                $notifications = $data->num_rows;
                // Системные настройки
                $system = $mysqli->query("SELECT * FROM `system` WHERE id = 1")->fetch_assoc();
                // find/leave логика
                if (isset($userInfo['find']) && $userInfo['find'] == 1) {
                    if ($userInfo['location'] != $userInfo['find_location']) {
                        if ($userInfo['find_leave'] == 0) {
                            $timeFind = $userInfo['find_time'] - time();
                            $A = 1;
                            $mysqli->query("UPDATE users SET find_leave = $A, find_leave_time = $timeFind WHERE id = {$userInfo['id']}");
                        }
                    }
                    if ($userInfo['location'] == $userInfo['find_location']) {
                        if ($userInfo['find_leave'] == 1) {
                            $timeFind = time() + $userInfo['find_leave_time'];
                            $A = 0;
                            $mysqli->query("UPDATE users SET find_leave = $A, find_leave_time = $A, find_time = $timeFind WHERE id = {$userInfo['id']}");
                        }
                    }
                }
                $this->updLocation = $userInfo["location"];
                // Возврат в столицу по условиям
                if ($userInfo['location'] == 87) {
                    if (!isset_item_activation(192) || (!isset_item_activation(368) && !isset_item_activation(369))) {
                        $this->goToCapital($userInfo['location']);
                    }
                }
                if(in_array($userInfo['location'], [83, 84, 86, 95])) {
                    if (!isset_item_activation(368) && !isset_item_activation(369)) {
                        $this->goToCapital($userInfo['location']);
                    }
                }
                // Чистка старых уведомлений
                $now = time();
                $res = $mysqli->query("SELECT id FROM notice_user WHERE time < $now");
                while($noty = $res->fetch_assoc()) {
                    $mysqli->query("DELETE FROM notice_user WHERE id = {$noty['id']}");
                }
                // Админ-уведомления
                $a = (time()-5);
                $adminNotify = $mysqli->query("SELECT * FROM adminNotify WHERE date > $a")->fetch_assoc();
                if(isset($adminNotify)) {
                    $admNot = [$adminNotify['id'],$adminNotify['date'],$adminNotify['text'],getMainUser($adminNotify['author'])];
                } else {
                    $admNot = [0];
                }
                // Уведомления пользователя
                $myNotify = $mysqli->query('SELECT * FROM notice_user WHERE user2 = '.$userInfo['id']);
                $simplenotyarr = [];
                while($myNotif = $myNotify->fetch_assoc()) {
                    $simplenotyarr[$myNotif['id']] = [
                        'user' => getMainUser($myNotif['user1']),
                        'id' => $myNotif['id'],
                        'type' => $myNotif['type'],
                        'dodj' => $myNotif['dodj'],
                        'text' => $myNotif['text']
                    ];
                }
                if(empty($simplenotyarr)) $simplenotyarr = 0;
                // --- ASSAULT UPDATE ---
                $isAssault = false;
                if(
                    (isset($_POST['assault']) && $_POST['assault'] === 'true')
                    || (isset($_GET['assault']) && $_GET['assault'] === 'true')
                ) $isAssault = true;
                if($isAssault) {
                    $assaultData = [
                        'fight_party_battle' => $userInfo['fight_party_battle'] ?? 0,
                        'fight_party'        => $userInfo['fight_party'] ?? 0,
                        'serverTime'         => date('G:i'),
                        'updLocation'        => $this->updLocation,
                        'bell_noty_count'    => $notifications,
                        'ver'                => $system['version'],
                        'tech'               => $userInfo['tech'] ?? "0",
                        'hash'               => $userInfo['hash'] ?? 0,
                        'log'                => $userInfo['log'] ?? 0,
                        'f'                  => $userInfo['f'] ?? 0,
                    ];
                    $this->response['response'] = $assaultData;
                    break;
                }
                // --- Эффекты ---
                $effectsList = 0;
                if (!empty($userInfo['effects'])) {
                    $effects = @json_decode($userInfo['effects'], true);
                    if(!$effects) $effects = [];
                    $effectsList = [];
                    foreach($effects as $key => $val) {
                        if(isset($val['time']) && $val['time'] >= time()) {
                            $effectsList[$key] = [
                                'name' => $key,
                                'time' => $val['time'],
                                'text' => $val['text'] ?? '',
                                'x'    => $val['x'] ?? 0
                            ];
                        }
                    }
                    if(empty($effectsList)) $effectsList = 0;
                }
                // --- Ответ ---
                $this->response['response'] = [
                    'admNot'         => $admNot,
                    'serverTime'     => date('G:i'),
                    'updLocation'    => $this->updLocation,
                    'simple_noty'    => $simplenotyarr,
                    'effects'        => $effectsList,
                    'bell_noty_count'=> $notifications,
                    'ver'            => $system['version']
                ];
            break;
            case 'socket_init':
                $user_start = $userInfo['user_start'] ?? 0;
                $this->response['response'] = [
                    'user_start' => $user_start,
                ];
            break;
            default:
                $this->response['response'] = [];
            break;
        }
    }
}

// === ОСНОВНОЙ СКРИПТ ===
header('Content-Type: application/json');
$userInfo = $user;
$response = [];
$type = $_POST['type'] ?? $_GET['type'] ?? '';
$update = new Update($type, $userInfo, $response);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
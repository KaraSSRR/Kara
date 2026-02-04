<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}

$userID = (int)$_SESSION['id'];
$response = [];

if(isset($_POST['type'])){
    switch($_POST['type']){
        // Старый кейс с командой
        case 'editTeam':
            $user = $mysqli->query("SELECT `team_open` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['team_open'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `team_open` = '$newValue' WHERE `id` = '$userID'");
            $response['error'] = 0;
            break;

        // Горячие клавиши (старый вариант)
        case 'editHotClick':
            $user = $mysqli->query("SELECT `HotClick` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['HotClick'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `HotClick` = '$newValue' WHERE `id` = '$userID'");
            $response['error'] = 0;
            break;

        // --- Универсальные современные кейсы для JS ---
        case 'audio':
            $user = $mysqli->query("SELECT `sound` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['sound'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `sound` = '$newValue' WHERE `id` = '$userID'");
            $response['error'] = 'success';
            break;

        case 'mission':
            $user = $mysqli->query("SELECT `mission_day` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['mission_day'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `mission_day` = '$newValue' WHERE `id` = '$userID'");
            $response['error'] = 'success';
            break;

        case 'hotclick':
            $user = $mysqli->query("SELECT `HotClick` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['HotClick'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `HotClick` = '$newValue' WHERE `id` = '$userID'");
            $_SESSION['HotClick'] = $newValue; // Синхронизируем в сессии
            $response['HotClick'] = $newValue;
            $response['error'] = 'success';
            break;

        case 'inv':
            $sortType = isset($_POST['set']) ? (int)$_POST['set'] : 0;
            $mysqli->query("UPDATE `users` SET `inv_sort` = '$sortType' WHERE `id` = '$userID'");
            $response['error'] = 'success';
            break;

        case 'color':
            $color = isset($_POST['set']) ? (int)$_POST['set'] : 1;
            $mysqli->query("UPDATE `users` SET `colorChat` = '$color' WHERE `id` = '$userID'");
            $response['error'] = 'success';
            break;

        case 'boss_battle':
            $user = $mysqli->query("SELECT `boss_battle` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['boss_battle'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `boss_battle` = '$newValue' WHERE `id` = '$userID'");
            $_SESSION['boss_battle'] = $newValue;
            $response['boss_battle'] = $newValue;
            $response['error'] = 'success';
            break;

        case 'team':
            $user = $mysqli->query("SELECT `team_open` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['team_open'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `team_open` = '$newValue' WHERE `id` = '$userID'");
            $_SESSION['team_open'] = $newValue;
            $response['team_open'] = $newValue;
            $response['error'] = 'success';
            break;

        case 'editColor':
            $color = isset($_POST['set']) ? (int)$_POST['set'] : 1;
            $mysqli->query("UPDATE `users` SET `colorChat` = '$color' WHERE `id` = '$userID'");
            $response['error'] = 0;
            break;

        case 'itemsFilter':
            $filter = isset($_POST['set']) ? (int)$_POST['set'] : 0;
            $mysqli->query("UPDATE `users` SET `items_filter` = '$filter' WHERE `id` = '$userID'");
            $response['error'] = 0;
            break;

        case 'pass':
            $user = $mysqli->query("SELECT `password` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $new_pass = $mysqli->query("SELECT * FROM `new_pass` WHERE `user` = '$userID'")->fetch_assoc();
            if($user['password'] == md5($_POST['pass']) || ($new_pass && $_POST['pass'] == $new_pass['pass'])){
                $password = $mysqli->real_escape_string($_POST['newPass']);
                $password = htmlspecialchars($password);
                $password = trim($password);
                $password = md5($password);
                $mysqli->query("UPDATE `users` SET `password` = '$password' WHERE `id` = '$userID'");
                $mysqli->query("DELETE FROM `new_pass` WHERE `user` = '$userID'");
                $response['error'] = 0;
            }else{
                $response['error'] = 1;
                $response['text'] = 'Неверно введён старый пароль!';
            }
            break;

        case 'team_open':
            $user = $mysqli->query("SELECT `team_open` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['team_open'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `team_open` = '$newValue' WHERE `id` = '$userID'");
            $_SESSION['team_open'] = $newValue;
            $response['team_open'] = $newValue;
            $response['error'] = 0;
            break;

        case 'closeNotifyAdmin':
            $idNotify = intval($_POST['idNotify']);
            $mysqli->query("INSERT INTO `adminNotifyCheck` (`user_id`,`id_notify`) VALUES ($userID, $idNotify)");
            $response['error'] = 0;
            break;

        case 'editSound':
            $user = $mysqli->query("SELECT `sound` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ($user['sound'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `sound` = '$newValue' WHERE `id` = '$userID'");
            $_SESSION['sound'] = $newValue;
            $response['sound'] = $newValue;
            $response['error'] = 0;
            break;

        case 'editAttackLang':
            $set = isset($_POST['set']) ? $_POST['set'] : '';
            if ($set === 'rus' || $set === 'eng') {
                $new = $set;
            } else {
                $user = $mysqli->query("SELECT `attack_lang` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
                $current = isset($user['attack_lang']) ? $user['attack_lang'] : 'rus';
                $new = ($current == 'rus') ? 'eng' : 'rus';
            }
            $mysqli->query("UPDATE `users` SET `attack_lang` = '$new' WHERE `id` = '$userID'");
            $_SESSION['attack_lang'] = $new;
            $response['attack_lang'] = $new;
            $response['html'] = "Язык атак переключён на ".($new == 'rus' ? 'Русский' : 'English').".";
            $response['error'] = "success";
            echo json_encode($response);
            exit;

        case 'editSprite':
            $user = $mysqli->query("SELECT `sprite` FROM `users` WHERE `id` = '$userID'")->fetch_assoc();
            $newValue = ((int)$user['sprite'] == 0) ? 1 : 0;
            $mysqli->query("UPDATE `users` SET `sprite` = '$newValue' WHERE `id` = '$userID'");
            $_SESSION['sprite'] = $newValue;
            $response['sprite'] = $newValue;
            $response['error'] = 0;
            break;

        case 'LangRu':
            $mysqli->query("UPDATE `users` SET `lang` = 'ru' WHERE `id` = '$userID'");
            $_SESSION['lang'] = 'ru';
            $response['lang'] = 'ru';
            $response['error'] = 0;
            break;

        case 'LangUa':
            $mysqli->query("UPDATE `users` SET `lang` = 'ua' WHERE `id` = '$userID'");
            $_SESSION['lang'] = 'ua';
            $response['lang'] = 'ua';
            $response['error'] = 0;
            break;

        default:
            $response['error'] = 1;
            $response['text'] = 'Ошибка запроса!';
            break;
    }
}

echo json_encode($response);
?>
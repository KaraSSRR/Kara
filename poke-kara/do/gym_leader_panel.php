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

$type = escapeMe($_POST['type']);
$user = $mysqli->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
$response = [];

// Только для user_group = 5 (гим-лидер)
if ($user['user_group'] != 5) {
    $response['html'] = '<div class="error">У вас нет доступа к этому разделу.</div>';
    echo json_encode($response);
    exit;
}

switch($type) {
    case "gym_leader_panel":
        // Получить все заявки на ваш стадион, которые ещё не обработаны (type=0)
        $applications = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$user['id'].' AND type = 0 ORDER BY id DESC');
        
        $tpl = '<div class="Header">
                    <div class="Name">Панель Гим-Лидера</div>
                    <div class="Close" onclick="closeLittleModal()"><i class="fas fa-times"></i></div>
                </div>
                <div class="content">';

        if ($applications->num_rows == 0) {
            $tpl .= '<p>Нет новых заявок на сражение за значок.</p>';
        } else {
            $tpl .= '<h2>Заявки на сражение</h2>
                     <p>Выберите действие для каждого тренера:</p>';
            while($app = $applications->fetch_assoc()){
                $user_app = $mysqli->query('SELECT * FROM users WHERE id = '.$app['user'])->fetch_assoc();
                $tpl .= '<div class="userApplication">
                            Тренер <b>'.$user_app['login'].'</b>
                            <div class="button">
                                <div class="btn" onclick="gym_give_badge('.$user_app['id'].')">Засчитать победу (значок)</div>
                                <div class="btn red" onclick="gym_loss('.$user_app['id'].')">Засчитать проигрыш</div>
                            </div>
                         </div>';
            }
        }
        $tpl .= '</div>';
        $response['html'] = $tpl;
        break;

    case "gym_give_badge":
        $trainer_id = intval($_POST['trainer_id']);
        // Найти заявку тренера (type=0)
        $bd = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$user['id'].' AND user = '.$trainer_id.' AND type = 0')->fetch_assoc();
        if($bd){
            // Определяем id значка для этого гим-лидера (примерная логика, подправьте если id поменялись)
            $badge_map = [
                4 => 1000014,
                1 => 1000017,
                2 => 1000018,
                262 => 1000007,
                312 => 1000010,
                410 => 1000009,
                841 => 1000005,
                917 => 1000006
            ];
            $leader_id = $user['id'];
            $znak = isset($badge_map[$leader_id]) ? $badge_map[$leader_id] : 0;
            if ($znak) {
                $mysqli->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`trophy`) VALUES ('".$trainer_id."','".$znak."','1','1') ");
                $mysqli->query("UPDATE `gym_log` SET `type` = '2'  WHERE `id` = '".$bd['id']."' ");
                $response['html'] = "Тренеру засчитана победа и выдан значок!";
                $response['error'] = "success";
            } else {
                $response['html'] = "Значок не найден!";
                $response['error'] = "error";
            }
        } else {
            $response['html'] = "Ошибка! Заявка не найдена.";
            $response['error'] = "error";
        }
        break;

    case "gym_loss":
        $trainer_id = intval($_POST['trainer_id']);
        // Найти заявку тренера (type=0)
        $bd = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$user['id'].' AND user = '.$trainer_id.' AND type = 0')->fetch_assoc();
        if($bd){
            $mysqli->query("UPDATE `gym_log` SET `type` = '1'  WHERE `id` = '".$bd['id']."' ");
            $response['html'] = "Тренеру засчитан проигрыш!";
            $response['error'] = "success";
        } else {
            $response['html'] = "Ошибка! Заявка не найдена.";
            $response['error'] = "error";
        }
        break;

    default:
        $response['html'] = "Неизвестный запрос";
        $response['error'] = "error";
        break;
}

echo json_encode($response);
?>
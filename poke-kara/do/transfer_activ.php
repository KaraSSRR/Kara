<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        _setError('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}
$_SESSION['textcode'] = '';
if(!empty($_POST['login']) and !empty($_POST['pass'])){
    $login = $mysqli->real_escape_string($_POST['login']);
		$login = escapeMe($login);
		$checkLogin = $mysqli->query("SELECT * FROM `transfer_users` WHERE `login` = '".$login."'")->fetch_assoc();
	$password = $mysqli->real_escape_string($_POST['pass']);
			$password = htmlspecialchars($password);
			$password = trim($password);
			$password = md5($password);
			$password = strrev($password);
            $password = $password."b3p6f66";
			if($checkLogin){
			    if($checkLogin['password'] == $password){
			        $_SESSION['textcode'] = '<font color=green>Ваш код переноса: '.$checkLogin['invaite'].'</font>';
			    }else{
			        $_SESSION['textcode'] = '<font color=red>Не верный пароль!</font>';
			    }
			}else{
			    $_SESSION['textcode'] = '<font color=red>Данный тренер не найден!</font>';
			}
}
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Poke Kara - Браузерная онлайн игра про Покемонов.</title>
    <style>
        body{
            display: flex;
    justify-content: center;
    align-items: center;
        }
        .content{
            width: 350px;
    height: 500px;
    text-align: center;
    padding: 40px 0;
    box-sizing: border-box;
        }
        input, button{
            width: 70%;
    height: 30px;
    border-radius: 6px;
    border: 1px solid #03A9F4;
    margin: 10px 0;
    display: inline-block;
        }
        button{
    background: #03a9f4;
    color: #fff;
    font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="content">
        <?=$_SESSION['textcode'];?>
        <form method='post' action='' id="autorizeForm">
	        <input type="text" name="login" id="uLogins" placeholder="Логин">
	        <input type="password" name="pass" id="uPasss" placeholder="Пароль">
	        <button type='submit' name='Submit'>Войти</button>
        </form>
    </div>
</body>
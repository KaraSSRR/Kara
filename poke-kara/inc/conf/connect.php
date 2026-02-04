<?

#Очищает текст от ненужных символов
function clearStr($text){
	$text = trim($text);
	$text = stripslashes($text);
	$text = htmlspecialchars($text);
	return $text;
}
#Очищает числа от ненужных символов
function clearInt($chs){
	$chs = ceil($chs);
	$chs = abs($chs);
	$chs = stripslashes($chs);
	$chs = htmlspecialchars($chs);
	$chs = trim($chs);
	return $chs;
}
function numbPok($val){
	if($val < 10){$dpl = "00";}else{
		if($val < 100 and $val >= 10){$dpl = "0";}
	else{$dpl = "";}
	}
	return $dpl.$val;
}
try {
    $mysqli = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_PASSWORD, MYSQL_DB);
    mysqli_set_charset($mysqli, "utf8");
} catch (Exception $e) {
    die($e->getMessage());
}
date_default_timezone_set("Europe/Moscow");

$dataHour = date("G");
$dataWeek = date("D");
if(date("G") >= 0 and date("G") <= 5){
	$timeday = 1;
	$constDay = 2;
}elseif(date("G") >= 6 and date("G") <= 11){
	$timeday = 2;
	$constDay = 3;
}elseif(date("G") >= 12 and date("G") <= 17){
	$timeday = 3;
	$constDay = 0;
}else{
	$timeday = 4;
	$constDay = 1;
}
if(isset($_SESSION['id']) && isset($_SESSION['login'])){
	$_SESSION['id'] = clearInt($_SESSION['id']);
	$userID = $_SESSION['id'];
	$userName = $_SESSION['login'];
	$timeOnl = time()+300;
	$time = time();
	$mysqli->query('UPDATE `users` SET `online` = '.$timeOnl.' WHERE `id` = '.$_SESSION['id']);
}
$sol = $mysqli->query("SELECT * FROM `system` WHERE `id` = 1")->fetch_assoc();

if($sol['time'] != date("Y-m-d")){
    $d = date("Y-m-d");
    $mysqli->query('UPDATE `system` SET `online` = 0,  `time` = "'.$d.'" WHERE `id` = 1');
}
error_reporting(0);

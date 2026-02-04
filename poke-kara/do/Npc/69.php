<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}
if(isset($_POST['subject']) && isset($_POST['text'])){
	$text = clearStr($_POST['text']);
	$subject = clearStr($_POST['subject']);
	$date = date("d:m:Y");
	$insert = '{"text":"'.$text.'","subject":"'.$subject.'","date":"'.$date.'"}';
	$response['text'] = 'Заявка успешно отправлена!';
	$response['error'] = 0;
	$mysqli->query("INSERT INTO `police` (`user`,`info`,`check`) VALUES ('".$_SESSION['id']."','".$insert."',0) ");
	die(json_encode($response));
}
$user = $mysqli->query("SELECT `user_group` FROM `users` WHERE `id` = '".$_SESSION['id']."'")->fetch_assoc();
$text.= '<div class="market">';
$text.= '<div class="text">Заполните заявку. Выберите тему и оставьте комментарий.</div>';
$text.= '
		<form class="evolNpcForm" onsubmit="policeNPC();return false;">
		<select style="margin: 10px;width: 95%;" required id="subjectPolice">
			<option value="1">Нарушение правил боя</option>
			<option value="2">Кража</option>
			<option value="3">Подозрения в мультоводстве</option>
			<option value="4">Подозрения в финпрокачке</option>
			<option value="4">Прочие нарушения</option>
		</select>
			<textarea required id="textPolice"></textarea>
		<input style="margin-left: 10px;" class="mn-btn" type="submit" value="Отправить" /></form>
		';
$text.= '</div>';
$response['html'] = $text;
$response['type'] = 'pokemarket';
$response['title'] = 'Офицер Джес';
?>
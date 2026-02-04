<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
		require_once($patch_func);
    }
}
$UserQuery = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
$type = escapeMe($_POST["type"]);
switch ($type) {
		case 'main':
			switch ($UserQuery['user_group']){
				case 1:
				case 2:
					$response['text'] = '
						<div class="Logs">
							<div class="Category">
								<div onclick="openPanelDL(\'trades\')">Обмены</div>
								<div onclick="openPanelDL(\'fights\')">Бои</div>
								<div onclick="openPanelDL(\'clans\')">Кланы</div>
							</div>
						</div>
					';
				break;
				default:
					$response['text'] = '
						<div class="unknown">Вы не являетесь каким-либо должностным лицом</div>
					';
				break;
			}
		break;
		case 'clans':
			switch ($UserQuery['user_group']){
				case 1:
				case 2:
					$ClanQuery = $mysqli->query('SELECT * FROM `log_game` WHERE `type` = "clan" AND `title` = "ADD_CLAN_MONEY" OR `type` = "clan" AND `title` = "TAKE_CLAN_MONEY" ORDER BY `id` DESC');
					while($clan = $ClanQuery->fetch_assoc()){
						$clanName = $mysqli->query('SELECT * FROM `base_clans` WHERE `id` = "'.$clan['user_id'].'"')->fetch_assoc();
						if($clan['title'] == "ADD_CLAN_MONEY"){$a = "положил в клан";}else{$a = "взял с клана";}
						$info = json_decode($clan['info']);
						$infoClan = json_decode($clanName['info']);
						$text .= '<div class="List"><div class="user-link u-'.$info->user_group.'">'.$info->user_new.'</div> '.$a.'<b> '.$infoClan->name.'</b> '.number_format($info->count,0,'.','.').' мон.</div>';
					}
					$response['text'] = '
						<div class="Logs">
							<div class="Category">
								<div onclick="openPanelDL(\'trades\')">Обмены</div>
								<div onclick="openPanelDL(\'fights\')">Бои</div>
								<div class="active" onclick="openPanelDL(\'clans\')">Кланы</div>
							</div>
							<div class="Content">
								'.$text.'
							</div>
						</div>
					';
				break;
				default:
					$response['text'] = '
						<div class="unknown">Вы не являетесь каким-либо должностным лицом</div>
					';
				break;
			}
		break;
		default:
			echo "Unknown error";
		break;
	}
echo json_encode($response);
?>
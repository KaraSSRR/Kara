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
$pok_l = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
$pok = $mysqli->query('SELECT * FROM `plague` ORDER BY `pok` ASC');
				while($poks = $pok->fetch_assoc()){
				    $pok_bd = $mysqli->query('SELECT * FROM `base_pokemons` WHERE `id` = '.$poks['pok'])->fetch_assoc();
				    $a .= '<img src="/img/pokemons/animation/'.NumbPok($poks['pok']).'.png"> #'.NumbPok($poks['pok']).' '.$pok_bd['name_rus'].' - <span class="Green-Color">'.$poks['count'].'</span> зараженных<br>';
				}
$html = '<div class="header">Заражение покемонов <span onclick="$(&quot;.model&quot;).remove();"><i class="fas fa-times"></i></span></div>
        <div class="content-model">
            <div class="pit">
                <div class="selec">
                    <div class="About">
                        В мире бродит странная чума, она заражает всех покемонов в мире. Используйте специальные медицинские таблетки чтобы вылечить всех покемонов. Лечение происходит автоматически в начале боя, в первом раунде вы будете кидать таблетку дикому покемону.
                        <br><b>Вы вылечили: <span class="Green-Color">'.$pok_l['virus'].'</span></b>
                    </div>
                    <div class="ListPlague">'.$a.'</div>
                </div>    
            </div>
        </div>';

$response['html'] = $html;
echo json_encode($response);
?>
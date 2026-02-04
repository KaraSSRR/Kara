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
		$html= '<div class="pit">
<div class="selec">
<div class="PokemonSel">
<div class="Pok1" id="Reproduction1" onclick="openReproductionPoks(this,1);"></div>
<div class="Btn" onclick="GetReproductionResult();">Спарить</div>
<div class="Pok2" id="Reproduction2" onclick="openReproductionPoks(this,2);"></div>
</div>
<div class="About">Выберите двух покемонов с противоположным полом и с одинаковой группой привлекательности. Если покемоны бесполые, на них должны быть обязательно надеты Амуриты. После удачной спарки Вы получите яйцо покемона, из которого вылупится более сильное потомство.</div>
</div>';
	$response['html'] = $html;
	$response['title'] = 'Разведение покемонов';
	$response['type'] = 'reproduction';
?>

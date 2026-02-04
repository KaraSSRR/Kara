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

$id = escapeMe($_POST['category']);
$sort = escapeMe($_POST['sort']);
$typ = escapeMe($_POST['typ']);

$dop = escapeMe($_POST['dop']);
if($id){
    $pok = $mysqli->query('SELECT * FROM `shop_lavk` WHERE `type` = '.$id.' ORDER BY `id` DESC LIMIT 30');
				while($poks = $pok->fetch_assoc()){
				    if($poks['type'] == 1){
				        $img = '<img src="/img/pokemons/sprite/'.$poks['okras'].'/'.numbPok($poks['num']).'.gif">';
				    }else{
				        $img = '<img src="img/world/items/little/'.$poks['num'].'.png">';
				    }
				    $tpl .= ' <div class="blockProduct ">
				                <div class="imgProduct">'.$img.'</div>
				                <div class="nameProduct '.$poks['okras'].'-color">'.$poks['name'].'</div>
				                <div class="priceProduct">Цена: '.number_format($poks['price'],0,'.','.').' </div>
				                <button class="buy">Купить</button>
				        
				    </div>';
				}
$response['html'] = $tpl;
}

if($sort){
    if($dop){ $dop = $dop; }else{$dop = "";}
    if($sort == 1){ $s ="price"; }
    elseif($sort == 2){ $s ="lvl"; }
    elseif($sort == 3){ $s ="num"; }
    elseif($sort == 4){ $s ="id"; }
    $pok = $mysqli->query('SELECT * FROM `shop_lavk` WHERE `type` = '.$typ.' '.$dop.' ORDER BY `'.$s.'` ASC LIMIT 30');
    if($pok){
        while($poks = $pok->fetch_assoc()){
				    if($typ == 1){
				        $img = '<img src="/img/pokemons/sprite/'.$poks['okras'].'/'.numbPok($poks['num']).'.gif">';
				    }else{
				        $img = '<img src="img/world/items/little/'.$poks['num'].'.png">';
				    }
				    $tpl .= ' <div class="blockProduct ">
				                <div class="imgProduct">'.$img.'</div>
				                <div class="nameProduct '.$poks['okras'].'-color">'.$poks['name'].'</div>
				                <div class="priceProduct">Цена: '.number_format($poks['price'],0,'.','.').' </div>
				                <button class="buy">Купить</button>
				        
				    </div>';
				}
    }else{
        $tpl = "Пусто";
    }
				
$response['html'] = $tpl;
}

















echo json_encode($response);

?>
<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_items = $patch_project.'/inc/function/Functions.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
		require_once($patch_items);
    }
}
if(isset($_POST['item']) && !empty($_POST['item'])){
	$count = ($_POST['count'] > 0 ? clearInt($_POST['count']) : die());
	$response['error'] = 0;
	$itemID = ($_POST['item'] > 0 ? clearInt($_POST['item']) : die());
	switch($itemID){
		case 51:
			$itemPrice = 100;
		break;
		default:
			$response['error'] = 1;
			$response['text'] = 'Ошибка запроса!';
		break;
	}
	$price = $count * $itemPrice;
	if(isset($price) && !empty($price) && item_isset(94,$price)){
			$response['text'] = 'Успешная покупка!';
			itemAdd($itemID,$count);
			minus_item(1,$price);
	}else{
		$response['error'] = 1;
		$response['text'] = 'Недостаточно средств для совершения покупки!';
	}
	die(json_encode($response));
}
	
		$response['html'] = '
	<div class="market">
		<div class="item">
			<img src="/img/world/items/little/51.png">
			<div class="name">Магмарайзер</div>
			<div class="prize">100 жет.</div>
			<input placeholder="Количество" value="1">
			<div class="buy" onclick="buyItem(51,\'fabian\',$(this).prev().val());">Купить</div>
		</div>
	</div>
	';
	$response['title'] = 'Фабиан';
	$response['type'] = 'pokemarket';
	$response['error'] = 0;
?>
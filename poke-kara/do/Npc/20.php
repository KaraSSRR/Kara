<?
	$response['name'] = 'Торговец Джо';
		switch($npcStep){
			case 1:
				$response['question'] = '{{makasimka}}';
				$response['npc_id'] = 20;
			break;
			default:
				$response['question'] = 'Добро пожаловать в Торговый центр!';
				$response['answer'] = array(
					'by' => ['title'=>'Купить предметы', 'npc_id'=>20]
				);
			break;
		}
?>

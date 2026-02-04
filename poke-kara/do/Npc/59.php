<?
	$response['name'] = 'Дженна';
		switch($npcStep){
			case 1:
				$response['question'] = '{{makasimka}}';
				$response['npc_id'] = 59;
			break;
			default:
				$response['question'] = 'Добро пожаловать в лавку колизея!';
				$response['answer'] = array(
					'by' => ['title'=>'Купить предметы', 'npc_id'=>59]
				);
			break;
		}
?>

<?php
Class Items{
	
	public function plusItem($itemID, $itemCount, $userID = false, $Lottery = false){
		if($itemID > 0 && $itemCount > 0){	
			$userID = (isset($userID) ? $userID : $_SESSION['id']);
			if($Lottery){
				for($i=0;$i<$count;$i++){
					$lotteryCount = Work::$sql->query('SELECT
														`count`
													FROM `auk`
													WHERE
														`id` = 1
													')->fetch_assoc();
					$ticketNumber = $lotteryCount['count'] + 1;
					Work::$sql->query('UPDATE `auk` SET `count` = `count` + 1 WHERE `id` = 1'); 
					Work::$sql->query('INSERT INTO `items_users`
										(`user`,`item_id`,`json`) 
									VALUES
										('.$user.','.$itemID.','.$ticketNumber.') '); 
				}
			}else{
				$itemQueryUser = Work::$sql->query('SELECT
													`count` 
												FROM `items_users`
												WHERE 
													`user` = '.$userID.' 
												AND 
													`item_id` = '.$itemID
											)->fetch_assoc();
				if($itemQueryUser['count'] > 0){
					$itemNewCount = $itemQueryUser['count'] + $itemCount;
					Work::$sql->query('UPDATE
											`items_users`
										SET
											`count` = '.$itemNewCount.'
										WHERE
											`item_id` = '.$itemID.'
										AND 
											`user` = '.$userID); 
				}else{
					Work::$sql->query('INSERT INTO `items_users`
											(`user`,`item_id`,`count`)
										VALUES
											('.$userID.','.$itemID.','.$itemCount.') ');
				}
			
			}
		}else{
			return false;
		}
	}
}
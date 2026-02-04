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
$type = $_POST['type'];
switch($type){
    case 'attack':
        $poks = $_POST['pok'];
        $atk = $_POST['atk'];
        $pok = $mysqli->query("SELECT `attacks`,`basenum`,`id` FROM `user_pokemons` WHERE `user_id` = '".$_SESSION['id']."' AND `active` = 1 AND `id` = '".$poks."' ")->fetch_assoc();//информация покемона
        $it = $mysqli->query("SELECT `id` FROM `base_items` WHERE `id` > 1000 AND `info` = '".$atk."' ")->fetch_assoc();//поиск итема по атаке
        $atkv = $mysqli->query("SELECT * FROM `user_tm_crafting` WHERE `tm` = '".$atk."' AND `user` = '".$_SESSION['id']."' ")->fetch_assoc();//информация по атаке
        $ban = $mysqli->query("SELECT * FROM `pokemon_tm` WHERE `pok` = '".$poks."' AND `attack` = '".$atk."' ");//использовал ли этот покемон эту атаку
        if($ban->num_rows == 0){
            if(!empty($it)){
                if(!empty($pok)){
                    $atac = explode(',',$pok['attacks']);
                    if(in_array($atk,$atac)){
                        $b_id = explode(',',$atkv['id_pok']);
                        $b_num = explode(',',$atkv['base_pok']);
                        $info = $atkv['info'];
                        if($info < 100){
                            if(!empty($atkv)){
                                if(in_array($pok['basenum'],$b_num)){
                                    $vb = 5;
                                }else{
                                    $vb = 20;
                                }
                                $upd_info = $info+$vb;
                                if($upd_info >= 100){
                                    news_friend(6,$atk);
                                }
                                $upd_id = $atkv['id_pok'].','.$pok['id'];
                                $upd_base = $atkv['base_pok'].','.$pok['basenum'];
                                $mysqli->query("UPDATE `user_tm_crafting` SET `info`= '".$upd_info."',`id_pok`= '".$upd_id."',`base_pok` = '".$upd_base."' WHERE `id`='".$atkv['id']."'");
                            }else{
                                $mysqli->query("INSERT INTO `user_tm_crafting` (`tm`,`user`,`info`,`id_pok`,`base_pok`) VALUES 
                                             ('".$atk."','".$_SESSION['id']."','20','".$pok['id']."','".$pok['basenum']."')");
                            }
                            $mysqli->query("INSERT INTO `pokemon_tm` (`pok`,`attack`) VALUES 
                                             ('".$pok['id']."','".$atk."')");
                            $response['text'] = 'Успешно';
                            $response['error'] = 'success';
                        }else{
                            $response['text'] = 'У вас уже обучена эта атака';
                            $response['error'] = 'error';
                        }
                    }else{
                        $response['text'] = 'У этого покемона нет этой атаки';
                        $response['error'] = 'error';
                    }
                }else{
                    $response['text'] = 'Покемон не найден';
                    $response['error'] = 'error';
                }
            }else{
                $response['text'] = 'Эта атака не является TM/TR';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'Этот покемон уже демонстрировал эту атаку';
            $response['error'] = 'error';
        }
        
    break;
    case 'create':
        $atk = $_POST['atk'];
        $atkv = $mysqli->query("SELECT * FROM `user_tm_crafting` WHERE `tm` = '".$atk."' AND `user` = '".$_SESSION['id']."' ")->fetch_assoc();//информация по атаке
        if($atkv['info'] >= 100){
            $user = $mysqli->query("SELECT `crafting_lot` FROM `users` WHERE `id` = '".$_SESSION['id']."' ")->fetch_assoc();
            $lot = $mysqli->query("SELECT * FROM `users_tm_create` WHERE `user` = '".$_SESSION['id']."' ");
            if($lot->num_rows < $user['crafting_lot']){
                $base = $mysqli->query("SELECT `craft`,`tm_id`,`id` FROM `base_items` WHERE `info` = '".$atk."' AND `id` > 1000 ")->fetch_assoc();
                $n = explode(',',$base['craft']);
                if(!empty($base)){
                    $cvb = $mysqli->query("SELECT `type` FROM `base_atk` WHERE `id` = '".$atk."' ")->fetch_assoc();
                    if($cvb['type'] == 'fighting'){$cryst = 205;}elseif($cvb['type'] == 'water'){$cryst = 206;}elseif($cvb['type'] == 'steel'){$cryst = 207;}elseif($cvb['type'] == 'dark'){$cryst = 208;}
                    elseif($cvb['type'] == 'ice'){$cryst = 209;}elseif($cvb['type'] == 'rock'){$cryst = 210;}elseif($cvb['type'] == 'dragon'){$cryst = 211;}elseif($cvb['type'] == 'electric'){$cryst = 212;}
                    elseif($cvb['type'] == 'bug'){$cryst = 213;}elseif($cvb['type'] == 'ground'){$cryst = 214;}elseif($cvb['type'] == 'fire'){$cryst = 215;}elseif($cvb['type'] == 'psychic'){$cryst = 216;}
                    elseif($cvb['type'] == 'fairy'){$cryst = 217;}elseif($cvb['type'] == 'grass'){$cryst = 218;}elseif($cvb['type'] == 'ghost'){$cryst = 219;}elseif($cvb['type'] == 'normal'){$cryst = 220;}
                    elseif($cvb['type'] == 'poison'){$cryst = 221;}elseif($cvb['type'] == 'fly'){$cryst = 222;}
                    if(item_isset(1,$n[1]) AND item_isset($cryst,1) AND item_isset(513,1) ){
                        $i = 1;
                        while($i<=$user['crafting_lot']){
                            $l = $mysqli->query("SELECT * FROM `users_tm_create` WHERE `user` = '".$_SESSION['id']."' AND `slot` = '".$i."' ")->fetch_assoc();
                            if(empty($l)){
                                $response['text'] = 'Успешно!';
                                $response['error'] = 'success';
                                $t = time()+3600*24*$n[0];
                                $mysqli->query("INSERT INTO `users_tm_create` (`atk`,`user`,`time`,`time_end`,`slot`) VALUES 
                                                     ('".$atk."','".$_SESSION['id']."','".time()."','".$t."','".$i."')");
                                $i = 10;
                            }else{
                                $i++;
                            }
                        }
                        $response['minus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x'.number_format($n[1],0,'.','.').'</b><br><img src="/img/world/items/little/'.$cryst.'.png" class="item"> Типовой кристалл <b>x1</b><br><img src="/img/world/items/little/513.png" class="item"> Заготовочный диск <b>x1</b>';
                        minus_item(1,$n[1]);
                        minus_item($cryst,1);
                        minus_item(513,1);
                    }else{
                        $response['text'] = 'У вас не хватает ингредиентов!';
                        $response['error'] = 'error';
                    }
                }else{
                    $response['text'] = 'Ошибка!';
                    $response['error'] = 'error';
                }
            }else{
                $response['text'] = 'У вас нет свободных слотов!';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'Атака еще не изучена!';
            $response['error'] = 'error';
        }
    break;
    case 'give':
        $type = $_POST['class'];
        $id = $_POST['id'];
        $l = $mysqli->query("SELECT * FROM `users_tm_create` WHERE `user` = '".$_SESSION['id']."' AND `id` = '".$id."' ")->fetch_assoc();
        $i = $mysqli->query("SELECT * FROM `base_items` WHERE `info` = '".$l['atk']."' AND `id` > 1000")->fetch_assoc();
        if(!empty($l)){
            if($type == 1){
                if(item_isset(25,20)){
                    itemAdd($i['id'],1);
                    battlepass_exp(500,5);
                    minus_item(25,20);
                    $mysqli->query('DELETE FROM `users_tm_create` WHERE `id` = '.$id);
                    $response['plus'] = '<img src="/img/world/items/little/'.$i['id'].'.png" class="item"> '.$i['name'].' <b>x1</b>';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x20</b>';
                    $response['text'] = 'Вы создали предмет!';
                    $response['error'] = 'success';
                }else{
                    $response['text'] = 'У вас не хватает камней!';
                    $response['error'] = 'error';
                }
            }else{
                if(time() > $l['time_end']){
                    itemAdd($i['id'],1);
                    battlepass_exp(500,5);
                    $mysqli->query('DELETE FROM `users_tm_create` WHERE `id` = '.$id);
                    $response['plus'] = '<img src="/img/world/items/little/'.$i['id'].'.png" class="item"> '.$i['name'].' <b>x1</b>';
                    $response['text'] = 'Вы создали предмет!';
                    $response['error'] = 'success';
                }else{
                    $response['text'] = 'Время еще не пришло!';
                    $response['error'] = 'error';
                }
            }
        }else{
            $response['text'] = 'Ошибка!';
            $response['error'] = 'error';
        }
    break;
    case 'slot':
        $type = $_POST['class'];
        $id = $_POST['id'];
        $user = $mysqli->query("SELECT `crafting_lot`,`lvl` FROM `users` WHERE `id` = '".$_SESSION['id']."' ")->fetch_assoc();
        if($id == 3){
            if($type == 2){
                if($user['crafting_lot'] == 2 AND $user['lvl'] >= 25){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '3' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #3 открыт!';
                    $response['error'] = 'success';
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }else{
                if($user['crafting_lot'] == 2 AND item_isset(25,20)){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '3' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #3 открыт!';
                    $response['error'] = 'success';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x20</b>';
                    minus_item(25,20);
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }
        }
        if($id == 4){
            if($type == 2){
                if($user['crafting_lot'] == 3 AND $user['lvl'] >= 30){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '4' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #4 открыт!';
                    $response['error'] = 'success';
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }else{
                if($user['crafting_lot'] == 3 AND item_isset(25,35)){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '4' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #4 открыт!';
                    $response['error'] = 'success';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x30</b>';
                    minus_item(25,30);
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }
        }
        if($id == 5){
            if($type == 2){
                if($user['crafting_lot'] == 4 AND $user['lvl'] >= 35){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '5' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #5 открыт!';
                    $response['error'] = 'success';
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }else{
                if($user['crafting_lot'] == 4 AND item_isset(25,35)){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '5' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #5 открыт!';
                    $response['error'] = 'success';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x40</b>';
                    minus_item(25,40);
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }
        }
        if($id == 6){
            if($type == 2){
                if($user['crafting_lot'] == 5 AND $user['lvl'] >= 45){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '6' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #6 открыт!';
                    $response['error'] = 'success';
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }else{
                if($user['crafting_lot'] == 5 AND item_isset(25,35)){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '6' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #6 открыт!';
                    $response['error'] = 'success';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x40</b>';
                    minus_item(25,40);
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
            }
        }
        if($id == 7){
                if($user['crafting_lot'] == 6 AND item_isset(25,50)){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '7' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #7 открыт!';
                    $response['error'] = 'success';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x50</b>';
                    minus_item(25,50);
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
        }
        if($id == 8){
                if($user['crafting_lot'] == 7 AND item_isset(25,50)){
                    $mysqli->query("UPDATE `users` SET `crafting_lot`= '8' WHERE `id`='".$_SESSION['id']."'");
                    $response['text'] = 'Слот #8 открыт!';
                    $response['error'] = 'success';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x50</b>';
                    minus_item(25,50);
                }else{
                    $response['text'] = 'Условия не соблюдены!';
                    $response['error'] = 'error';
                }
        }
    break;
}
echo json_encode($response);
?>




































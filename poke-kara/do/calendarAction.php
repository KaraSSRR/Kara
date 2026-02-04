<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}
if(!empty($_POST['BossPrize'])){
    $id = $_POST['BossPrize'];
    $bd = $mysqli->query('SELECT * FROM `base_boss` WHERE `user` = '.$_SESSION['id'].' AND `id` = '.$id)->fetch_assoc();
    if($bd){
        if($bd['prize'] == 0 and $bd['time'] > time()){
            switch($bd['type']){
                case 1:
                    $rand = rand(3,4);
                    $plus = '';
                    for($i=0;$i < $rand;$i++){
                        $r = rand(1,100); 
                        if($r >= 1 and $r < 15){
                            $r2 = rand(377,381);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x2</b><br>';
                            itemAdd($r2,2);
                        }elseif($r >= 15 and $r < 30){
                            $r2 = rand(27,30);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x3</b><br>';
                            itemAdd($r2,3);
                        }elseif($r >= 30 and $r < 40){
                            $r2 = rand(367,372);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x2</b><br>';
                            itemAdd($r2,2);
                        }elseif($r >= 40 and $r < 50){
                            $r2 = rand(552,554);
                            $rc = rand(1,2);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }elseif($r >= 50 and $r < 70){
                            $r2 = rand(515,532);
                            $rc = rand(1,2);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }elseif($r >= 70 and $r < 85){
                            $plus .= '<img src="/img/world/items/little/446.png" class="item" > Золотой лист <b>x1</b><br>';
                            itemAdd(446,1);
                        }elseif($r >= 85 and $r <= 100){
                            $r2 = rand(533,550);
                            $rc = rand(2,5);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }
                    }
                break;
                case 2:
                    $rand = rand(3,5);
                    $plus = '';
                    for($i=0;$i < $rand;$i++){
                        $r = rand(1,100); 
                        if($r >= 1 and $r < 15){
                            $r2 = rand(377,381);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x2</b><br>';
                            itemAdd($r2,2);
                        }elseif($r >= 15 and $r < 30){
                            $r2 = rand(27,30);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x5</b><br>';
                            itemAdd($r2,5);
                        }elseif($r >= 30 and $r < 40){
                            $r2 = rand(367,372);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x2</b><br>';
                            itemAdd($r2,2);
                        }elseif($r >= 40 and $r < 50){
                            $r2 = rand(553,555);
                            $rc = rand(1,3);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }elseif($r >= 50 and $r < 70){
                            $r2 = rand(515,532);
                            $rc = rand(2,4);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }elseif($r >= 70 and $r < 85){
                            $plus .= '<img src="/img/world/items/little/446.png" class="item" > Золотой лист <b>x1</b><br>';
                            itemAdd(446,1);
                        }elseif($r >= 85 and $r <= 100){
                            $r2 = rand(533,550);
                            $rc = rand(4,7);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }
                    }
                break;
                case 3:
                    $rand = rand(4,6);
                    $plus = '';
                    for($i=0;$i < $rand;$i++){
                        $r = rand(1,100); 
                        if($r >= 1 and $r < 15){
                            $r2 = rand(377,381);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x2</b><br>';
                            itemAdd($r2,2);
                        }elseif($r >= 15 and $r < 30){
                            $r2 = rand(27,30);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x7</b><br>';
                            itemAdd($r2,7);
                        }elseif($r >= 30 and $r < 40){
                            $r2 = rand(367,372);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x5</b><br>';
                            itemAdd($r2,2);
                        }elseif($r >= 40 and $r < 50){
                            $r2 = rand(554,556);
                            $rc = rand(1,3);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }elseif($r >= 50 and $r < 70){
                            $r2 = rand(515,532);
                            $rc = rand(3,5);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }elseif($r >= 70 and $r < 85){
                            $plus .= '<img src="/img/world/items/little/446.png" class="item" > Золотой лист <b>x2</b><br>';
                            itemAdd(446,2);
                        }elseif($r >= 85 and $r <= 100){
                            $r2 = rand(533,550);
                            $rc = rand(6,10);
                            $plus .= '<img src="/img/world/items/little/'.$r2.'.png" class="item" > '.item_info($r2,"name").' <b>x'.$rc.'</b><br>';
                            itemAdd($r2,$rc);
                        }
                    }
                break;
            }
            $mysqli->query('UPDATE `base_boss` SET `prize` = "1" WHERE `id` = '.$bd['id']);
            $response['plus'] = $plus;
            $response['text'] = "Поздравляем с победой над боссом!";
            $response['error'] = "success";
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
}
if(!empty($_POST['home_game_game'])){
    $type = $_POST['home_game_game'];
    $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_playhome` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    $ivent_game = $mysqli->query('SELECT * FROM `a_ivent_week_playhome_game` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    switch($type){
        case 'trade':
            if($ivent){
                if($ivent['point'] >= 5){
                    $c1 = floor(($ivent['point']/5)*2000);
                    if($c1 >= 1){
                        $pl .= '<img src="/img/world/items/little/1.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Монета <b>x'.number_format($c1,0,'.','.').'</b><br>';
                        itemAdd(1,$c1);
                    }
                    
                    $c2 = floor($ivent['point']/15);
                    if($c2 >= 1){
                        $pl .= '<img src="/img/world/items/little/30.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Розовая конфета <b>x'.number_format($c2,0,'.','.').'</b><br>';
                        itemAdd(30,$c2);
                    }
                    
                    $c3 = floor($ivent['point']/50);
                    if($c3 >= 1){
                        $pl .= '<img src="/img/world/items/little/246.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Сладкий кекс <b>x'.number_format($c3,0,'.','.').'</b><br>';
                        itemAdd(246,$c3);
                    }
                    
                    $c4 = floor(($ivent['point']/70)*2);
                    if($c4 >= 1){
                        $pl .= '<img src="/img/world/items/little/197.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Набор тренировки <b>x'.number_format($c4,0,'.','.').'</b><br>';
                        itemAdd(197,$c4);
                    }
                    
                    $c5 = floor(($ivent['point']/100)*2);
                    if($c5 >= 1){
                        $pl .= '<img src="/img/world/items/little/53.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Бриллиантовый покебол <b>x'.number_format($c5,0,'.','.').'</b><br>';
                        itemAdd(53,$c5);
                    }
                    
                    $c6 = floor($ivent['point']/140);
                    if($c6 >= 1){
                        $pl .= '<img src="/img/world/items/little/256.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Лекарство <b>x'.number_format($c6,0,'.','.').'</b><br>';
                        itemAdd(256,$c6);
                    }
                    
                    $c7 = floor($ivent['point']/190);
                    if($c7 >= 1){
                        $pl .= '<img src="/img/world/items/little/255.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Корень априкорна <b>x'.number_format($c7,0,'.','.').'</b><br>';
                        itemAdd(255,$c7);
                    }
                    
                    $c8 = floor($ivent['point']/250);
                    if($c8 >= 1){
                        $pl .= '<img src="/img/world/items/little/268.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Зелье памяти <b>x'.number_format($c8,0,'.','.').'</b><br>';
                        itemAdd(268,$c8);
                    }
                    
                    $c7 = floor($ivent['point']/350);
                    if($c7 >= 1){
                        $pl .= '<img src="/img/world/items/little/513.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Заготовочный диск <b>x'.number_format($c7,0,'.','.').'</b><br>';
                        itemAdd(513,$c7);
                    }
                    
                    
                    $c8 = floor($ivent['point']/500);
                    if($c8 >= 1){
                        $pl .= '<img src="/img/world/items/little/223.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Загадочный покебол <b>x'.number_format($c8,0,'.','.').'</b><br>';
                        itemAdd(223,$c8);
                    }
                
                $mysqli->query("UPDATE `a_ivent_week_playhome` SET `point`= '0' WHERE `user`='".$_SESSION['id']."'");
                $response['plus'] = $pl;
                $response['text'] = 'Призы выданы';
                $response['error'] = 'success';
            }else{
                $response['text'] = 'У вас не хватает жетонов!';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'Пользователь не найден!';
            $response['error'] = 'error';
        }
        break;
        case 'fill':
            if($ivent['ticket'] >= 1){
                $fill = $_POST['fill'];
                if(in_array($fill,['col_1','col_2','col_3'])){
                    if($ivent_game[$fill] == ''){
                        $tickupd = $ivent['ticket']-1;
                        $response['ticket'] = $tickupd;
                        $mysqli->query('UPDATE `a_ivent_week_playhome` SET `ticket` = "'.$tickupd.'" WHERE `user` = '.$_SESSION['id']);
                        $it1 = rand(493,498);
                        $it2 = rand(493,498);
                        $it3 = rand(493,498);
                        $items = $it1.','.$it2.','.$it3;
                        $mysqli->query('UPDATE `a_ivent_week_playhome_game` SET `'.$fill.'` = "'.$items.'" WHERE `user` = '.$_SESSION['id']);
                        $func = 'onclick="issetAll('.$it3.',\'playing_house\',\''.$fill.'\');"';
                        $response['html'] = '<img src="/img/world/items/little/'.$it1.'.png"><img src="/img/world/items/little/'.$it2.'.png"><img class="clickable" src="/img/world/items/little/'.$it3.'.png" '.$func.'>';
                        $response['text'] = "Предметы добавлены!";
                        $response['error'] = "success";
                    }else{
                        $response['text'] = "Ошибка!";
                        $response['error'] = "error";
                    }
                }else{
                    $response['text'] = "Ошибка!";
                    $response['error'] = "error";
                }
            }else{
                $response['text'] = "У вас недостаточно билетов!";
                $response['error'] = "error";
            }
        break;
        case 'destroy':
            $mysqli->query('UPDATE `a_ivent_week_playhome_game` SET `col_1` = "",`col_2` = "",`col_3` = "" WHERE `user` = '.$_SESSION['id']);
            $response['text'] = "Предметы разрушены!";
            $response['error'] = "success";
        break;
        case 'give':
            $item = $_POST['item'];
            $pok = $_POST['pok'];
            $col = $_POST['col'];
            if($pok >= 1 and $pok <= 4){
                $c = explode(',',$ivent_game[$col]);
                $n = count($c)-1;
                if($c[$n] == $item){
                    if($pok == 1){
                        $it = 'pok_i_1';
                        $pk = 'pok1';
                        $st = 'PokLeftTop';
                    }elseif($pok == 2){
                        $it = 'pok_i_2';
                        $pk = 'pok2';
                        $st = 'PokLeftBottom';
                    }elseif($pok == 3){
                        $it = 'pok_i_3';
                        $pk = 'pok3';
                        $st = 'PokRightTop';
                    }elseif($pok == 4){
                        $it = 'pok_i_4';
                        $pk = 'pok4';
                        $st = 'PokRightBottom';
                    }
                    $spis = explode(',',$ivent_game[$it]);
                    if(in_array($item,$spis)){
                        if($n != 0){
                            unset($c[$n]);
                            $elm = implode(',',$c);
                            $mysqli->query('UPDATE `a_ivent_week_playhome_game` SET `'.$col.'` = "'.$elm.'" WHERE `user` = '.$_SESSION['id']);
                            
                            $ivent_game2 = $mysqli->query('SELECT * FROM `a_ivent_week_playhome_game` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
                        $coles = explode(',',$ivent_game2[$col]);
                        $num = count($coles);
                            for($i=0;$i < $num;$i++){
                                if($i == $num-1){
                                    $func = 'class="clickable" onclick="issetAll('.$coles[$i].',\'playing_house\',\''.$col.'\');"';
                                }else{
                                    $func = '';
                                }
                                $tpl .= '<img '.$func.' src="/img/world/items/little/'.$coles[$i].'.png">';  	        
                            }
                            $response[$col] = $tpl;
                        }else{
                            $mysqli->query('UPDATE `a_ivent_week_playhome_game` SET `'.$col.'` = "" WHERE `user` = '.$_SESSION['id']);
                            if($col == 'col_1'){ $response['fill1'] = true; }elseif($col == 'col_2'){ $response['fill2'] = true; }else{ $response['fill3'] = true;}
                            $response[$col.'_tr'] = true;
                        }
                        
                        $vb = explode(',',$ivent_game[$it]);
                        $n = count($vb);
                        for($i=0;$i < $n;$i++){
                            if($vb[$i] == $item){
                                unset($vb[$i]);
                                break;
                            }  	        
                        }
                        $elem = implode(',',$vb);
                        $mysqli->query('UPDATE `a_ivent_week_playhome_game` SET `'.$it.'` = "'.$elem.'" WHERE `user` = '.$_SESSION['id']);
                        
                        
                        $asd = explode(',',$elem);
                        $asdas = count($asd);
                        
                        if($asd[0] != 0 and $asd[0] != ""){
                            $ivent_game4 = $mysqli->query('SELECT * FROM `a_ivent_week_playhome_game` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
                            $coles2 = explode(',',$ivent_game4[$it]);
                            $num2 = count($coles2);
                            for($i=0;$i < $num2;$i++){
                                $tpl2 .= '<img  src="/img/world/items/little/'.$coles2[$i].'.png">';  	        
                            }
                            $response['wish'] = 'update';
                            $response['items'] = $tpl2;
                            $response['pok'] = $st;
                        }else{
                            
                            $response['plus'] = '<img src="/img/world/items/little/500.png" class="item" > Жетон радости <b>x1</b><br>';
                            $input = array(104,114,116,133,172,173,174,175,177,183,190,194,216,220,231,239,240,280,285,293,298,300,311,312,322,325,333,341,358,360,363,370,396,406,417,438,439,440,446,447,495,498,501,504,506,509,511,513,515,535,541,546,548,572,574,582,585,650,653,665,669,677,682,684,694,698,704,725,728,742,744,747,753,755,759,761,764,813,816,819,824,827,831,835,840,854,856,868,872,885);
                            $rand_keys = array_rand($input, 2);
                            $poker = $input[$rand_keys[1]];
                            $bd_baf = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = 448 AND time > ".time())->fetch_assoc();
                            $it1 = rand(493,498); $it2 = rand(493,498); $it3 = rand(493,498);
                            if($bd_baf and rand(1,100) < 10){
                                $isd = $it1.','.$it2;
                                $tpl2 = '<img src="/img/world/items/little/'.$it1.'.png">
                                    <img src="/img/world/items/little/'.$it2.'.png">';
                            }else{
                                $isd = $it1.','.$it2.','.$it3;
                                $tpl2 = '<img src="/img/world/items/little/'.$it1.'.png">
                                    <img src="/img/world/items/little/'.$it2.'.png">
                                    <img src="/img/world/items/little/'.$it3.'.png">';
                            }
                            $point_upd = $ivent['point']+1;
                            $mysqli->query('UPDATE `a_ivent_week_playhome_game` SET `'.$pk.'` = "'.$poker.'", `'.$it.'` = "'.$isd.'" WHERE `user` = '.$_SESSION['id']);
                            $mysqli->query('UPDATE `a_ivent_week_playhome` SET `point` = "'.$point_upd.'" WHERE `user` = '.$_SESSION['id']);
                            
                            $std = $point_upd/500*100;
                            if($std > 100) $std = 100;
                            $response['widt'] = $std;
                          $response['ptn'] = $point_upd;  
                    $response['pok'] = $st;
                    $response['wish'] = 'switch';
                    $response['addwish'] = '
                               <div class="'.$st.'_Wish Wish">'.$tpl2.'</div>
                        <div class="'.$st.'_Icon Icon">
                            <img src="/img/pokemons/sprite/normal/'.$poker.'.gif">
                        </div>  ';
                            $response['text'] = "Покемон получил все предметы!";
                        }
                        $response['error'] = "success";
                    }else{
                        $response['text'] = "Предмет не подходит!";
                        $response['error'] = "error";
                    }
                }else{
                    $response['text'] = "Ошибка!";
                    $response['error'] = "error";
                }
            }else{
                $response['text'] = "Ошибка!";
                $response['error'] = "error";
            }
        break;
    }
}
if(!empty($_POST['home_game'])){
    $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_playhome` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    $ivent_game = $mysqli->query('SELECT * FROM `a_ivent_week_playhome_game` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    if($ivent){
        if($ivent_game){
            $tpl .= '<div class="destroyerbtn" onclick="home_destroy();">Разрушить все <i class="fas fa-trash-alt"></i></div>';
            $i1 = explode(',',$ivent_game['pok_i_1']);
            $n1 = count($i1);
            for($i=0;$i < $n1;$i++){
                $a1 .= '<img src="/img/world/items/little/'.$i1[$i].'.png">';    	        
            }
            $tpl .= '<div class="PokLeftTop Pokemon">
                        <div class="PokLeftTop_Wish Wish">'.$a1.'</div>
                        <div class="PokLeftTop_Icon Icon">
                            <img src="/img/pokemons/sprite/normal/'.$ivent_game['pok1'].'.gif">
                        </div>
                    </div>';
            $i2 = explode(',',$ivent_game['pok_i_2']);
            $n2 = count($i2);
            for($i=0;$i < $n2;$i++){
                $a2 .= '<img src="/img/world/items/little/'.$i2[$i].'.png">'; 	        
            }
            $tpl .= '<div class="PokLeftBottom Pokemon">
                        <div class="PokLeftTop_Wish Wish">'.$a2.'</div>
                        <div class="PokLeftTop_Icon Icon">
                            <img src="/img/pokemons/sprite/normal/'.$ivent_game['pok2'].'.gif">
                        </div>
                    </div>';
            $i3 = explode(',',$ivent_game['pok_i_3']);
            $n3 = count($i3);
            for($i=0;$i < $n3;$i++){
                $a3 .= '<img src="/img/world/items/little/'.$i3[$i].'.png">';    	        
            }
            $tpl .= '<div class="PokRightTop Pokemon">
                        <div class="PokLeftTop_Wish Wish">'.$a3.'</div>
                        <div class="PokLeftTop_Icon Icon">
                            <img src="/img/pokemons/sprite/normal/'.$ivent_game['pok3'].'.gif">
                        </div>
                    </div>';
            $i4 = explode(',',$ivent_game['pok_i_4']);
            $n4 = count($i4);
            for($i=0;$i < $n4;$i++){
                $a4 .= '<img src="/img/world/items/little/'.$i4[$i].'.png">';    	        
            }
            $tpl .= '<div class="PokRightBottom Pokemon">
                        <div class="PokLeftTop_Wish Wish">'.$a4.'</div>
                        <div class="PokLeftTop_Icon Icon">
                            <img src="/img/pokemons/sprite/normal/'.$ivent_game['pok4'].'.gif">
                        </div>
                    </div>';
            
            $col1 = explode(',',$ivent_game['col_1']);
            $num1 = count($col1);
            if($num1 >= 1 and $ivent_game['col_1'] != ''){
                for($i=0;$i < $num1;$i++){
                    if($i == $num1-1){
                        $func1 = 'class="clickable" onclick="issetAll('.$col1[$i].',\'playing_house\',\'col_1\');"';
                    }else{
                        $func1 = '';
                    }
                    $c1 .= '<img '.$func1.' src="/img/world/items/little/'.$col1[$i].'.png">';    	        
                }
            }else{
                $c1 = '';
                $cbtn1 = '<div class="fillbtn fill1" onclick="home_fill(\'col_1\',\'Left\',1);"><i class="fas fa-layer-plus"></i></div>';
            }
            $tpl .= '<div class="LeftPipe Pipe"><div>'.$c1.'</div></div>';
            $col2 = explode(',',$ivent_game['col_2']);
            $num2 = count($col2);
            if($num2 >= 1 and $ivent_game['col_2'] != ''){
                for($i=0;$i < $num2;$i++){
                    if($i == $num2-1){
                        $func2 = 'class="clickable" onclick="issetAll('.$col2[$i].',\'playing_house\',\'col_2\');"';
                    }else{
                        $func2 = '';
                    }
                    $c2 .= '<img '.$func2.' src="/img/world/items/little/'.$col2[$i].'.png">';  	        
                }
            }else{
                $c2 = '';
                $cbtn2 = '<div class="fillbtn fill2" onclick="home_fill(\'col_2\',\'Center\',2);"><i class="fas fa-layer-plus"></i></div>';
            }
            $tpl .= '<div class="CenterPipe Pipe"><div>'.$c2.'</div></div>';
            $col3 = explode(',',$ivent_game['col_3']);
            $num3 = count($col3);
            if($num3 >= 1 and $ivent_game['col_3'] != ''){
                for($i=0;$i < $num3;$i++){
                    if($i == $num3-1){
                        $func3 = 'class="clickable" onclick="issetAll('.$col3[$i].',\'playing_house\',\'col_3\');"';
                    }else{
                        $func3 = '';
                    }
                    $c3 .= '<img '.$func3.' src="/img/world/items/little/'.$col3[$i].'.png">';     	        
                }
            }else{
                $c3 = '';
                $cbtn3 = '<div class="fillbtn fill3" onclick="home_fill(\'col_3\',\'Right\',3);"><i class="fas fa-layer-plus"></i></div>';
            }
            $tpl .= '<div class="RightPipe Pipe"><div>'.$c3.'</div></div>';
            $tpl .= $cbtn1.$cbtn2.$cbtn3;
            $response['html'] = $tpl;    
            $response['error'] = "success";    
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
    
}
if(!empty($_POST['labirint'])){
    $col = $_POST['labirint'];
    $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_labirint` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    $game = $mysqli->query('SELECT * FROM `a_ivent_week_labirint_game` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    if($ivent){
        if($ivent['game'] == 1){
            if($col == $game['my']){
                if($game['my'] == 1){
                    $response['slot2'] = true;
                    $response['slot4'] = true;
                }elseif($game['my'] == 2){
                    $response['slot1'] = true;
                    $response['slot3'] = true;
                    $response['slot5'] = true;
                }elseif($game['my'] == 3){
                    $response['slot2'] = true;
                    $response['slot6'] = true;
                }elseif($game['my'] == 4){
                    $response['slot1'] = true;
                    $response['slot5'] = true;
                    $response['slot7'] = true;
                }elseif($game['my'] == 5){
                    $response['slot2'] = true;
                    $response['slot4'] = true;
                    $response['slot6'] = true;
                    $response['slot8'] = true;
                }elseif($game['my'] == 6){
                    $response['slot3'] = true;
                    $response['slot5'] = true;
                    $response['slot9'] = true;
                }elseif($game['my'] == 7){
                    $response['slot4'] = true;
                    $response['slot8'] = true;
                }elseif($game['my'] == 8){
                    $response['slot5'] = true;
                    $response['slot7'] = true;
                    $response['slot9'] = true;
                }elseif($game['my'] == 9){
                    $response['slot6'] = true;
                    $response['slot8'] = true;
                }
                $response['error'] = "success";
            }else{
                $vc = explode(',',$game['slot']);
                $hp = $game['hp'];
                if($vc[$col] == 1){
                    $hp = $game['hp']+6;
                    if($hp > 30){ $hp = 30;}
                }elseif($vc[$col] == 2){
                    $hp = $game['hp']-4;
                }elseif($vc[$col] == 3){
                    $hp = $game['hp'];
                }elseif($vc[$col] == 4){
                    $hp = $game['hp']-6;
                }elseif($vc[$col] == 5){
                    $response['winkey'] = true; 
                }elseif($vc[$col] == 6){
                    $response['text'] = "Вы нашли сундук!";
                    $upd = $ivent['chest']+1;
                    $mysqli->query('UPDATE `a_ivent_week_labirint` SET `chest` = "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                }
                if($hp > 0){
                    if(!$response['winkey']){
                        if($game['chest'] != 0){
                            if(rand(1,3) == 1){
                                $rand = rand(1,127);
                            }else{
                                $rand = rand(1,113);
                            }
                        }else{
                            if(rand(1,3) == 1){
                                $rand = rand(1,117);
                            }else{
                                $rand = rand(1,113);
                            }
                        }
                    
                    if($rand >= 1 and $rand < 20){
                        $r = 1;
                        $text = '<div class="point Green-Color"><i class="fas fa-heart"></i> 6</div><img src="/img/pokemons/sprite/normal/113.gif">';
                    }elseif($rand >= 20 and $rand < 65){
                        $r = 2;
                        $text = '<div class="point Red-Color"><i class="fas fa-swords"></i> 4</div><img src="/img/pokemons/sprite/normal/342.gif">';
                    }elseif($rand >= 65 and $rand < 85){
                        $r = 3;
                        $text = '<div class="point Red-Color"><i class="fas fa-swords"></i> 0</div><img src="/img/pokemons/sprite/normal/681.gif">';
                    }elseif($rand >= 85 and $rand < 114){
                        $r = 4;
                        $text = '<div class="point Red-Color"><i class="fas fa-swords"></i> 6</div><img src="/img/pokemons/sprite/normal/681_blade.gif">';
                    }elseif($rand >= 114 and $rand <= 117){
                        $r = 5;
                        $text = '<div class="Item"><img id="imgItem" src="/img/world/items/little/457.png">';
                    }elseif($rand > 117 and $rand <= 127){
                        $r = 6;
                        $text = '<i class="fas fa-treasure-chest"></i>';
                        $upd = $game['chest']-1;
                        $mysqli->query('UPDATE `a_ivent_week_labirint_game` SET `chest` = "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                    }
                    if($game['my'] == 1){
                        if($col == 2){
                            $my = 2;
                            $vc[1] = $vc[4];
                            $vc[4] = $vc[7];
                            $vc[7] = $r;
                            $vc[2] = 99;
                            $response['my'] = 2;
                            $response['s_0_1'] = 1;
                            $response['s_0_2'] = 2;
                            $response['s_1_1'] = 4;
                            $response['s_1_2'] = 1;
                            $response['s_2_1'] = 7;
                            $response['s_2_2'] = 4;
                            $response['new'] = 7;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom1';
                            $response['error'] = "success";
                        }elseif($col == 4){
                            $my = 4;
                            $vc[1] = $vc[2];
                            $vc[2] = $vc[3];
                            $vc[3] = $r;
                            $vc[4] = 99;
                            $response['my'] = 4;
                            $response['s_0_1'] = 1;
                            $response['s_0_2'] = 4;
                            $response['s_1_1'] = 2;
                            $response['s_1_2'] = 1;
                            $response['s_2_1'] = 3;
                            $response['s_2_2'] = 2;
                            $response['new'] = 3;
                            $response['sl_text'] = $text;
                            $response['type'] = 'right1';
                            $response['error'] = "success";
                        }else{
                            $response['text'] = "Ошибка!";
                            $response['error'] = "error";
                        }
                    }elseif($game['my'] == 2){
                        if($col == 1){
                            $my = 1;
                            $vc[2] = $vc[3];
                            $vc[3] = $r;
                            $vc[1] = 99;
                            $response['my'] = 1;
                            $response['s_0_1'] = 2;
                            $response['s_0_2'] = 1;
                            $response['s_1_1'] = 3;
                            $response['s_1_2'] = 2;
                            $response['new'] = 3;
                            $response['sl_text'] = $text;
                            $response['type'] = 'right1';
                            $response['error'] = "success";
                        }elseif($col == 3){
                            $my = 3;
                            $vc[2] = $vc[1];
                            $vc[1] = $r;
                            $vc[3] = 99;
                            $response['my'] = 3;
                            $response['s_0_1'] = 2;
                            $response['s_0_2'] = 3;
                            $response['s_1_1'] = 1;
                            $response['s_1_2'] = 2;
                            $response['new'] = 1;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left1';
                            $response['error'] = "success";
                        }elseif($col == 5){
                            $my = 5;
                            $vc[2] = $vc[1];
                            $vc[1] = $r;
                            $vc[5] = 99;
                            $response['my'] = 5;
                            $response['s_0_1'] = 2;
                            $response['s_0_2'] = 5;
                            $response['s_1_1'] = 1;
                            $response['s_1_2'] = 2;
                            $response['new'] = 1;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left1';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 3){
                        if($col == 2){
                            $my = 2;
                            $vc[3] = $vc[6];
                            $vc[6] = $vc[9];
                            $vc[9] = $r;
                            $vc[2] = 99;
                            $response['my'] = 2;
                            $response['s_0_1'] = 3;
                            $response['s_0_2'] = 2;
                            $response['s_1_1'] = 6;
                            $response['s_1_2'] = 3;
                            $response['s_2_1'] = 9;
                            $response['s_2_2'] = 6;
                            $response['new'] = 9;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom3';
                            $response['error'] = "success";
                        }elseif($col == 6){
                            $my = 6;
                            $vc[3] = $vc[2];
                            $vc[2] = $vc[1];
                            $vc[1] = $r;
                            $vc[6] = 99;
                            $response['my'] = 6;
                            $response['s_0_1'] = 3;
                            $response['s_0_2'] = 6;
                            $response['s_1_1'] = 2;
                            $response['s_1_2'] = 3;
                            $response['s_2_1'] = 1;
                            $response['s_2_2'] = 2;
                            $response['new'] = 1;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left1';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 4){
                        if($col == 1){
                            $my = 1;
                            $vc[4] = $vc[7];
                            $vc[7] = $r;
                            $vc[1] = 99;
                            $response['my'] = 1;
                            $response['s_0_1'] = 4;
                            $response['s_0_2'] = 1;
                            $response['s_1_1'] = 7;
                            $response['s_1_2'] = 4;
                            $response['new'] = 7;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom1';
                            $response['error'] = "success";
                        }elseif($col == 5){
                            $my = 5;
                            $vc[4] = $vc[7];
                            $vc[7] = $r;
                            $vc[5] = 99;
                            $response['my'] = 5;
                            $response['s_0_1'] = 4;
                            $response['s_0_2'] = 5;
                            $response['s_1_1'] = 7;
                            $response['s_1_2'] = 4;
                            $response['new'] = 7;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom1';
                            $response['error'] = "success";
                        }elseif($col == 7){
                            $my = 7;
                            $vc[4] = $vc[1];
                            $vc[1] = $r;
                            $vc[7] = 99;
                            $response['my'] = 7;
                            $response['s_0_1'] = 4;
                            $response['s_0_2'] = 7;
                            $response['s_1_1'] = 1;
                            $response['s_1_2'] = 4;
                            $response['new'] = 1;
                            $response['sl_text'] = $text;
                            $response['type'] = 'top1';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 5){
                        if($col == 2){
                            $my = 2;
                            $vc[5] = $vc[8];
                            $vc[8] = $r;
                            $vc[2] = 99;
                            $response['my'] = 2;
                            $response['s_0_1'] = 5;
                            $response['s_0_2'] = 2;
                            $response['s_1_1'] = 8;
                            $response['s_1_2'] = 5;
                            $response['new'] = 8;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom2';
                            $response['error'] = "success";
                        }elseif($col == 4){
                            $my = 4;
                            $vc[5] = $vc[6];
                            $vc[6] = $r;
                            $vc[4] = 99;
                            $response['my'] = 4;
                            $response['s_0_1'] = 5;
                            $response['s_0_2'] = 4;
                            $response['s_1_1'] = 6;
                            $response['s_1_2'] = 5;
                            $response['new'] = 6;
                            $response['sl_text'] = $text;
                            $response['type'] = 'right2';
                            $response['error'] = "success";
                        }elseif($col == 6){
                            $my = 6;
                            $vc[5] = $vc[4];
                            $vc[4] = $r;
                            $vc[6] = 99;
                            $response['my'] = 6;
                            $response['s_0_1'] = 5;
                            $response['s_0_2'] = 6;
                            $response['s_1_1'] = 4;
                            $response['s_1_2'] = 5;
                            $response['new'] = 4;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left2';
                            $response['error'] = "success";
                        }elseif($col == 8){
                            $my = 8;
                            $vc[5] = $vc[2];
                            $vc[2] = $r;
                            $vc[8] = 99;
                            $response['my'] = 8;
                            $response['s_0_1'] = 5;
                            $response['s_0_2'] = 8;
                            $response['s_1_1'] = 2;
                            $response['s_1_2'] = 5;
                            $response['new'] = 2;
                            $response['sl_text'] = $text;
                            $response['type'] = 'top2';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 6){
                        if($col == 3){
                            $my = 3;
                            $vc[6] = $vc[9];
                            $vc[9] = $r;
                            $vc[3] = 99;
                            $response['my'] = 3;
                            $response['s_0_1'] = 6;
                            $response['s_0_2'] = 3;
                            $response['s_1_1'] = 9;
                            $response['s_1_2'] = 6;
                            $response['new'] = 9;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom3';
                            $response['error'] = "success";
                        }elseif($col == 5){
                            $my = 5;
                            $vc[6] = $vc[9];
                            $vc[9] = $r;
                            $vc[5] = 99;
                            $response['my'] = 5;
                            $response['s_0_1'] = 6;
                            $response['s_0_2'] = 5;
                            $response['s_1_1'] = 9;
                            $response['s_1_2'] = 6;
                            $response['new'] = 9;
                            $response['sl_text'] = $text;
                            $response['type'] = 'bottom3';
                            $response['error'] = "success";
                        }elseif($col == 9){
                            $my = 9;
                            $vc[6] = $vc[3];
                            $vc[3] = $r;
                            $vc[9] = 99;
                            $response['my'] = 9;
                            $response['s_0_1'] = 6;
                            $response['s_0_2'] = 9;
                            $response['s_1_1'] = 3;
                            $response['s_1_2'] = 6;
                            $response['new'] = 3;
                            $response['sl_text'] = $text;
                            $response['type'] = 'top3';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 7){
                        if($col == 4){
                            $my = 4;
                            $vc[7] = $vc[8];
                            $vc[8] = $vc[9];
                            $vc[9] = $r;
                            $vc[4] = 99;
                            $response['my'] = 4;
                            $response['s_0_1'] = 7;
                            $response['s_0_2'] = 4;
                            $response['s_1_1'] = 8;
                            $response['s_1_2'] = 7;
                            $response['s_2_1'] = 9;
                            $response['s_2_2'] = 8;
                            $response['new'] = 9;
                            $response['sl_text'] = $text;
                            $response['type'] = 'right3';
                            $response['error'] = "success";
                        }elseif($col == 8){
                            $my = 8;
                            $vc[7] = $vc[4];
                            $vc[4] = $vc[1];
                            $vc[1] = $r;
                            $vc[8] = 99;
                            $response['my'] = 8;
                            $response['s_0_1'] = 7;
                            $response['s_0_2'] = 8;
                            $response['s_1_1'] = 4;
                            $response['s_1_2'] = 7;
                            $response['s_2_1'] = 1;
                            $response['s_2_2'] = 4;
                            $response['new'] = 1;
                            $response['sl_text'] = $text;
                            $response['type'] = 'top1';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 8){
                        if($col == 5){
                            $my = 5;
                            $vc[8] = $vc[7];
                            $vc[7] = $r;
                            $vc[5] = 99;
                            $response['my'] = 5;
                            $response['s_0_1'] = 8;
                            $response['s_0_2'] = 5;
                            $response['s_1_1'] = 7;
                            $response['s_1_2'] = 8;
                            $response['new'] = 7;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left3';
                            $response['error'] = "success";
                        }elseif($col == 7){
                            $my = 7;
                            $vc[8] = $vc[9];
                            $vc[9] = $r;
                            $vc[7] = 99;
                            $response['my'] = 7;
                            $response['s_0_1'] = 8;
                            $response['s_0_2'] = 7;
                            $response['s_1_1'] = 9;
                            $response['s_1_2'] = 8;
                            $response['new'] = 9;
                            $response['sl_text'] = $text;
                            $response['type'] = 'right3';
                            $response['error'] = "success";
                        }elseif($col == 9){
                            $my = 9;
                            $vc[8] = $vc[7];
                            $vc[7] = $r;
                            $vc[9] = 99;
                            $response['my'] = 9;
                            $response['s_0_1'] = 8;
                            $response['s_0_2'] = 9;
                            $response['s_1_1'] = 7;
                            $response['s_1_2'] = 8;
                            $response['new'] = 7;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left3';
                            $response['error'] = "success";
                        }
                    }elseif($game['my'] == 9){
                        if($col == 6){
                            $my = 6;
                            $vc[9] = $vc[8];
                            $vc[8] = $vc[7];
                            $vc[7] = $r;
                            $vc[6] = 99;
                            $response['my'] = 6;
                            $response['s_0_1'] = 9;
                            $response['s_0_2'] = 6;
                            $response['s_1_1'] = 8;
                            $response['s_1_2'] = 9;
                            $response['s_2_1'] = 7;
                            $response['s_2_2'] = 8;
                            $response['new'] = 7;
                            $response['sl_text'] = $text;
                            $response['type'] = 'left3';
                            $response['error'] = "success";
                        }elseif($col == 8){
                            $my = 8;
                            $vc[9] = $vc[6];
                            $vc[6] = $vc[3];
                            $vc[3] = $r;
                            $vc[8] = 99;
                            $response['my'] = 8;
                            $response['s_0_1'] = 9;
                            $response['s_0_2'] = 8;
                            $response['s_1_1'] = 6;
                            $response['s_1_2'] = 9;
                            $response['s_2_1'] = 3;
                            $response['s_2_2'] = 6;
                            $response['new'] = 3;
                            $response['sl_text'] = $text;
                            $response['type'] = 'top3';
                            $response['error'] = "success";
                        }
                    }
                    
                    for($x=1;$x < 10;$x++){
                        if($vc[$x] == 3){
                            $vc[$x] = 4;
                            $response[$x] = '<div class="point Red-Color"><i class="fas fa-swords"></i> 6</div><img src="/img/pokemons/sprite/normal/681_blade.gif">';
                        }elseif($vc[$x] == 4){
                            $vc[$x] = 3;
                            $response[$x] = '<div class="point Red-Color"><i class="fas fa-swords"></i> 0</div><img src="/img/pokemons/sprite/normal/681.gif">';
                        }    
                            
                        }
                    
                    if($response['error'] == "success"){
                        $vcs = implode(',',$vc);
                        $response['hp'] = $hp;
                        $mysqli->query('UPDATE `a_ivent_week_labirint_game` SET `my` = '.$my.',`hp` = '.$hp.', `slot` = "'.$vcs.'" WHERE `user` = '.$_SESSION['id']);
                    }
                    }else{
                        $mysqli->query('UPDATE `a_ivent_week_labirint` SET `game` = "0" WHERE `user` = '.$_SESSION['id']);
                        $mysqli->query('DELETE FROM a_ivent_week_labirint_game WHERE user = '.$_SESSION['id']);
                        itemAdd(457,1);
                        $response['plus'] = '<img src="/img/world/items/little/457.png" class="item">Золотой ключ <b>x1</b>';
                        $response['text'] = "Вы прошли лабиринт!";
                        $response['error'] = "error";
                    }
                }else{
                    $response['text'] = "Вы проиграли!";
                    $response['error'] = "error";
                    $response['lose'] = true;
                    $mysqli->query('UPDATE `a_ivent_week_labirint` SET `game` = "0" WHERE `user` = '.$_SESSION['id']);
                    $mysqli->query('DELETE FROM a_ivent_week_labirint_game WHERE user = '.$_SESSION['id']);
                }
            }
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
            
}
if(!empty($_POST['labirint_game'])){
    $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_labirint` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    if($ivent){
        if($ivent['ticket'] >= 3){
            if($ivent['game'] == 0){
                $tick_upd = $ivent['ticket']-3;
                $mysqli->query('UPDATE `a_ivent_week_labirint` SET `game` = 1, `ticket` = '.$tick_upd.' WHERE `user` = '.$_SESSION['id']);
                $response['text'] = "Удачи в лабиринте!";
                $response['error'] = "success";
                
                $slot = '0,'.rand(1,4).','.rand(1,4).','.rand(1,4).','.rand(1,4).',99,'.rand(1,4).','.rand(1,4).','.rand(1,4).','.rand(1,4);
                
                $mysqli->query('INSERT INTO a_ivent_week_labirint_game (user,slot) VALUES ('.$_SESSION['id'].',"'.$slot.'")');
                
            }else{
                $response['text'] = "Ошибка!";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "У вас недостаточно билетов!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
    
}

if(!empty($_POST['type'])){
$type = escapeMe($_POST['type']);
$turn = escapeMe($_POST['turn']);
if($type == "boss"){
    $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `fight_turn` = '.$turn.' and `user` = '.$_SESSION['id'])->fetch_assoc();
    if($us){
        if($us['fight_check'] == 1){
            $pri = $mysqli->query('SELECT * FROM `ivent_birthday_prize` WHERE `type` = "battle" AND  `turn` = '.$us['fight_turn'].' ')->fetch_assoc();
            $b = explode(';',$pri['prize']);
                $n = count($b);
                for($i=0;$i < $n;$i++){
                    $c = explode(',',$b[$i]);
                    if($c[0] == 151){
                        $pok = $mysqli->query('SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '.$c[1].' ')->fetch_assoc();
                        $countDay = mt_rand(13,20);
                                    $tm = time()+(3600*24*$countDay);
                                    $gens = "".rand(39,43).",".rand(39,43).",".rand(39,43).",".rand(39,43).",".rand(39,43).",".rand(39,43)."";
                                    $basenum = $c[1];
                                    plusEgg($gens,false,false,true,$tm,$basenum,false);
                        $a .= '<img src="/img/world/items/little/'.$c[0].'.png" class="item">Яйцо '.$pok['name_rus'].'<br>';
                    }else{
                        $item = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$c[0])->fetch_assoc();
                        if($c[0] == 489){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(481,492),1);
                        }
                        $a .= '<img src="/img/world/items/little/489.png" class="item">Карты коллекции Разрушение легенд <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 43){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(35,52),1);
                        }
                        $a .= '<img src="/img/world/items/little/43.png" class="item">Типовые конфеты <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 371){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(367,372),1);
                        }
                        $a .= '<img src="/img/world/items/little/371.png" class="item">Крылья <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 1001){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(1001,1099),1);
                        }
                        $a .= '<img src="/img/world/items/little/1001.png" class="item">ТМ-Атаки <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 2001){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(2000,2098),1);
                        }
                        $a .= '<img src="/img/world/items/little/2001.png" class="item">ТR-Атаки <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }else{
                            $a .= '<img src="/img/world/items/little/'.$c[0].'.png" class="item">'.$item['name'].' <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        itemAdd($c[0],$c[1]);
                        }
                        
                    }
                }
                $response['plus'] = $a;
            $t = $turn+1;
                $mysqli->query('UPDATE `ivent_birthday` SET `fight_check` = 0, `fight_turn` = '.$t.' WHERE `user` = '.$_SESSION['id']);
            $response['text'] = "Задание успешно завершено!";
            $response['error'] = "success";
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
}
if($type == "mission"){
    $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `mission_turn` = '.$turn.' and `user` = '.$_SESSION['id'])->fetch_assoc();
    if($us){
        $mission_complete = explode(',',$us['mission_complete']);
        if(in_array(1,$mission_complete) and in_array(2,$mission_complete) and in_array(3,$mission_complete)){
            $pri = $mysqli->query('SELECT * FROM `ivent_birthday_prize` WHERE `type` = "mission" AND  `turn` = '.$us['mission_turn'].' ')->fetch_assoc();
            $b = explode(';',$pri['prize']);
                $n = count($b);
                for($i=0;$i < $n;$i++){
                    $c = explode(',',$b[$i]);
                    if($c[0] == 151){
                        $pok = $mysqli->query('SELECT `name_rus` FROM `base_pokemons` WHERE `id` = '.$c[1].' ')->fetch_assoc();
                        $countDay = mt_rand(13,20);
                                    $tm = time()+(3600*24*$countDay);
                                    $gens = "".rand(39,43).",".rand(39,43).",".rand(39,43).",".rand(39,43).",".rand(39,43).",".rand(39,43)."";
                                    $basenum = $c[1];
                                    plusEgg($gens,false,false,true,$tm,$basenum,false);
                        $a .= '<img src="/img/world/items/little/'.$c[0].'.png" class="item">Яйцо '.$pok['name_rus'].'<br>';
                    }else{
                        $item = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$c[0])->fetch_assoc();
                        if($c[0] == 489){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(481,492),1);
                        }
                        $a .= '<img src="/img/world/items/little/489.png" class="item">Карты коллекции Разрушение легенд <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 43){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(35,52),1);
                        }
                        $a .= '<img src="/img/world/items/little/43.png" class="item">Типовые конфеты <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 371){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(367,372),1);
                        }
                        $a .= '<img src="/img/world/items/little/371.png" class="item">Крылья <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 1001){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(1001,1099),1);
                        }
                        $a .= '<img src="/img/world/items/little/1001.png" class="item">ТМ-Атаки <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }elseif($c[0] == 2001){
                            $cv = $c[1];
                            for($is=0;$is < $cv;$is++){
                                itemAdd(rand(2000,2098),1);
                        }
                        $a .= '<img src="/img/world/items/little/2001.png" class="item">ТR-Атаки <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }else{
                            $a .= '<img src="/img/world/items/little/'.$c[0].'.png" class="item">'.$item['name'].' <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        itemAdd($c[0],$c[1]);
                        }
                        
                    }
                }
                $response['plus'] = $a;
                
            
            $t = $turn+1;
                $mysqli->query('UPDATE `ivent_birthday` SET `mission_check` = "", `mission_complete` = "", `mission_turn` = '.$t.' WHERE `user` = '.$_SESSION['id']);
                
					$mysqli->query('DELETE FROM ivent_mission_check WHERE user = '.$_SESSION['id']);
                if($t == 2){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (4,'.$_SESSION['id'].',35000)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (5,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (6,'.$_SESSION['id'].',10)');
                }elseif($t == 3){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (7,'.$_SESSION['id'].',15)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (8,'.$_SESSION['id'].',4)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (9,'.$_SESSION['id'].',3)');
                }elseif($t == 4){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (10,'.$_SESSION['id'].',200)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (11,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (12,'.$_SESSION['id'].',5)');
                }elseif($t == 5){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (13,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (14,'.$_SESSION['id'].',75)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (15,'.$_SESSION['id'].',15)');
                }elseif($t == 6){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (16,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (17,'.$_SESSION['id'].',50000)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (18,'.$_SESSION['id'].',10)');
                }elseif($t == 7){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (19,'.$_SESSION['id'].',20)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (20,'.$_SESSION['id'].',35)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (21,'.$_SESSION['id'].',2)');
                }elseif($t == 8){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (22,'.$_SESSION['id'].',3)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (23,'.$_SESSION['id'].',25)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (24,'.$_SESSION['id'].',10)');
                }elseif($t == 9){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (25,'.$_SESSION['id'].',1000)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (26,'.$_SESSION['id'].',5)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (27,'.$_SESSION['id'].',1)');
                }elseif($t == 10){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (28,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (29,'.$_SESSION['id'].',1)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (30,'.$_SESSION['id'].',2)');
                }elseif($t == 11){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (31,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (32,'.$_SESSION['id'].',1)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (33,'.$_SESSION['id'].',50)');
                }elseif($t == 12){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (34,'.$_SESSION['id'].',5)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (35,'.$_SESSION['id'].',5)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (36,'.$_SESSION['id'].',50)');
                }elseif($t == 13){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (37,'.$_SESSION['id'].',30)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (38,'.$_SESSION['id'].',10)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (39,'.$_SESSION['id'].',20)');
                }elseif($t == 14){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (40,'.$_SESSION['id'].',1)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (41,'.$_SESSION['id'].',75)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (42,'.$_SESSION['id'].',10)');
                }elseif($t == 15){
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (43,'.$_SESSION['id'].',2000000)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (44,'.$_SESSION['id'].',2)');
                    $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (45,'.$_SESSION['id'].',1)');
                }
            $response['text'] = "Задание успешно завершено!";
            $response['error'] = "success";
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
}
if($type == "mission_exp"){
    $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    if($us){
        $mission_check = explode(',',$us['mission_check']);
        $mission_complete = explode(',',$us['mission_complete']);
        if(in_array($turn,$mission_check) and !in_array($turn,$mission_complete)){
            lvlupuser(500);
            if($us['mission_complete'] == ""){ $t = $turn;}else{ $t = $us['mission_complete'].','.$turn; }
            $mysqli->query('UPDATE `ivent_birthday` SET `mission_complete` = "'.$t.'" WHERE `user` = '.$_SESSION['id']);
            $response['text'] = "Задание завершено!";
            $response['error'] = "success";
        }else{
            $response['text'] = "Ошибка2!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
}
}
if(!empty($_POST['arheolog_open_empty'])){
    $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_arheolog` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
    $open = explode(',',$ivent['open']);
    $block = explode(',',$ivent['block']);
    if(empty($ivent['open'])){$c = 0;}else{ $c = count($open);}
    if(empty($ivent['block'])){$x = 0;}else{ $x = count($block);}
    $all = 1+$c+$x;
    if($ivent){
        if(item_isset(25,5)){
            if($all < 20){
                minus_item(25,5);
                $arc[] = $ivent['key'];
    $i = 0;
    while(true){
        $r = rand(1,24);
        if(!in_array($r, $arc) and !in_array($r, $open) and !in_array($r, $block)){
            $i++;
            $arc[] = $r;
        }else{
            continue;
        }
    if($i == 3) break;
    }
    
    if($x == 0){
        $bv = $arc[3].','.$arc[1].','.$arc[2];
    }else{
        $bv = $ivent['block'].','.$arc[3].','.$arc[1].','.$arc[2];
    }
    
    
    Work::$sql->query('UPDATE `a_ivent_week_arheolog` SET `block`= "'.$bv.'" WHERE `user` = '.$_SESSION['id']);
    
    
    
    
    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item" > Драгоценный камень <b>x5</b><br>';
    $response['text'] = "Слоты заблокированы!";
    $response['error'] = "success";
            }else{
        $response['text'] = "Вы больше не можете блокировать слоты на этой карте!";
        $response['error'] = "error";
    }
        }else{
        $response['text'] = "У вас недостаточно камней!";
        $response['error'] = "error";
    }
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
    
}
if(!empty($_POST['buy_shovel'])){
    $ivent = $mysqli->query("SELECT * FROM a_ivent_week_arheolog WHERE user = ".$_SESSION['id'])->fetch_assoc();
        if(!empty($ivent)){
            if(item_isset(25,5)){
                $upd = $ivent['shovel']+10;
                Work::$sql->query('UPDATE `a_ivent_week_arheolog` SET `shovel`= "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                $response['shovel'] = $upd;
                $response['minus'] = '<img src="/img/world/items/little/25.png" class="item" > Драгоценный камень <b>x5</b><br>';
                $response['text'] = "Лопаты добавлены!";
                $response['error'] = "success";
                minus_item(25,5);
            }else{
                $response['text'] = "У вас недостаточно камней!";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    
}
if(!empty($_POST['buy_ticket'])){
    $ivent = $mysqli->query("SELECT * FROM a_ivent_week_labirint WHERE user = ".$_SESSION['id'])->fetch_assoc();
        if(!empty($ivent)){
            if(item_isset(25,10)){
                $upd = $ivent['ticket']+6;
                Work::$sql->query('UPDATE `a_ivent_week_labirint` SET `ticket`= "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                $response['ticket'] = $upd;
                $response['minus'] = '<img src="/img/world/items/little/25.png" class="item" > Драгоценный камень <b>x10</b><br>';
                $response['text'] = "Билеты добавлены!";
                $response['error'] = "success";
                minus_item(25,10);
            }else{
                $response['text'] = "У вас недостаточно камней!";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    
}
if(!empty($_POST['open_chest'])){
    $ivent = $mysqli->query("SELECT * FROM a_ivent_week_labirint WHERE user = ".$_SESSION['id'])->fetch_assoc();
        if(!empty($ivent)){
            if($ivent['chest'] >= 1){
                $input1 = array(451,458,459,460,461,463,464,465,466,467,481,482,483,484,486,487,488,490,491,492);
                $rand_keys1 = array_rand($input1, 2);
                $prize1 = $input1[$rand_keys1[1]];
                $input2 = array(182,184,187,196,448,457,53,31,34,124,197,198,241,246,426);
                $rand_keys2 = array_rand($input2, 2);
                $prize2 = $input2[$rand_keys2[1]];
                $input3 = array(125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,148,469,148,150,153,154,155,159,158,157);
                $rand_keys3 = array_rand($input3, 2);
                $prize3 = $input3[$rand_keys3[1]];
                $r = rand(1,3);
                if($r == 1){
                    $input4 = array(367,368,369,370,371,372,377,378,379,380,381);
                    $rand_keys4 = array_rand($input4, 2);
                    $prize4 = $input4[$rand_keys4[1]];
                }elseif($r == 2){
                    $input4 = array(553,555,553,554,553,553,554);
                    $rand_keys4 = array_rand($input4, 2);
                    $prize4 = $input4[$rand_keys4[1]];
                }else{
                    $input4 = rand(515,532);
                    $prize4 = $input4;
                }
                itemAdd($prize1,3);
                itemAdd($prize2,1);
                itemAdd($prize3,1);
                itemAdd($prize4,1);
                
                
                $upd = $ivent['chest']-1;
                Work::$sql->query('UPDATE `a_ivent_week_labirint` SET `chest`= "'.$upd.'" WHERE `user` = '.$_SESSION['id']);
                $response['chest'] = $upd;
                $response['plus'] = '<img src="/img/world/items/little/'.$prize1.'.png" class="item" > '.item_info($prize1,"name").' <b>x2</b><br>
                <img src="/img/world/items/little/'.$prize2.'.png" class="item" > '.item_info($prize2,"name").' <b>x1</b><br>
                <img src="/img/world/items/little/'.$prize3.'.png" class="item" > '.item_info($prize3,"name").' <b>x1</b><br>
                <img src="/img/world/items/little/'.$prize4.'.png" class="item" > '.item_info($prize4,"name").' <b>x1</b><br>';
                $response['text'] = "Сундук успешно открыт!";
                $response['error'] = "success";
            }else{
                $response['text'] = "У вас недостаточно сундуков!";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "Ошибка!";
            $response['error'] = "error";
        }
    
}

if(!empty($_POST['arheolog_open'])){
    $s = $_POST['arheolog_open'];
    $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_arheolog` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
    $open_slot = explode(',',$ivent['open']);
    $empty_slot = explode(',',$ivent['empty']);
    $block_slot = explode(',',$ivent['block']);
    if(!in_array($s,$block_slot) or !in_array($s,$open_slot)){
        if($ivent['shovel'] >= 1){
            if(!in_array($s,$empty_slot)){
                if($s == $ivent['key']){
                    $response['plus'] = '<img src="/img/world/items/little/457.png" class="item">Золотой ключ <b>x1</b>';
                    $response['slot'] = '<div class="Item"><img id="imgItem" src="/img/world/items/little/457.png"></div>';
                    $response['text'] = "Вы нашли ключ!";
                    $response['error'] = "success";
                    itemAdd(457,1);
                    $key = true;
                }else{
                    $rand = rand(1,100);
                    if($rand >= 1 and $rand < 65){
                        $input = array(552,553,481,482,483,484,485,486,487,488,489,490,491,181,183,62,26,27,28,29,124,245,515,516,517,518,519,520,521,522,523,524,525,526,527,528,529,530,531,532,533,534,535,536,537,538,539,540,550);
                    }elseif($rand >= 65 and $rand < 90){
                        $input = array(554,555,73,74,75,76,77,78,79,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,187,30,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,150,153,154,197,199,200,201,202,203,204,246,257,258,259,260,261,262,367,368,369,370,371,372,100,99);
                    }elseif($rand >= 90 and $rand <= 100){
                      $input = array(556,196,31,34,155,241,269,270,107,108);
                    }
                    $rand_keys = array_rand($input, 2);
                    $prize = $input[$rand_keys[1]];
                    $bd_item = $mysqli->query('SELECT `name` FROM `base_items` WHERE `id` = '.$prize.' ')->fetch_assoc();
                    $response['plus'] = '<img src="/img/world/items/little/'.$prize.'.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');">'.$bd_item['name'].' <b>x1</b>';
                    $response['slot'] = '<div class="Item"><div class="blockrait"></div><img id="imgItem" src="/img/world/items/little/'.$prize.'.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"></div>';
                    $response['text'] = "Вы нашли клад!";
                    $response['error'] = "success";
                    itemAdd($prize,1);
                    $mysqli->query('INSERT INTO a_ivent_week_arheolog_prize (slot,user,item) VALUES ('.$s.','.$_SESSION['id'].','.$prize.')');
                }
            }else{
                $response['slot'] = '<i class="fas fa-empty-set"></i><div class="cell_text">Пусто</div>';
                $response['text'] = "Этот слот был пустым!";
                $response['error'] = "success";
            }
            if(empty($ivent['open'])){ $upd = $s; }else{ $upd = $ivent['open'].','.$s; }
            $upd_shovel = $ivent['shovel']-1;
            $mysqli->query('UPDATE `a_ivent_week_arheolog` SET `open` = "'.$upd.'",`shovel` = "'.$upd_shovel.'" WHERE `user` = '.$_SESSION['id']);
            $response['shovel'] = $upd_shovel;
        }else{
            $response['text'] = "У вас не хватает лопат!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Этот слот недоступен!";
        $response['error'] = "error";
    }
    if($key){
        $vc = $ivent['map']+1;
        new_arheolog_map($vc);
        $response['key'] = 1;
    }
}


if(!empty($_POST['mission'])){
$id = escapeMe($_POST['mission']);

$us = $mysqli->query('SELECT * FROM `user_mission_day` WHERE `id_mission` = '.$id.' and `user` = '.$_SESSION['id'])->fetch_assoc();
if($us){
    if($us['end'] == 0){
        if($us['this_process'] == $us['end_process']){
            $i = $mysqli->query('SELECT * FROM `base_items` WHERE `id` = '.$us['present_id'])->fetch_assoc();
                itemAdd($us['present_id'],$us['present_count']);
                battlepass_exp(50);
                $mysqli->query('UPDATE `user_mission_day` SET `end` = 1 WHERE `id_mission` = '.$id.' AND `user` = '.$_SESSION['id']);
            $response['plus'] = '<img src="/img/world/items/little/'.$us['present_id'].'.png" class="item">'.$i['name'].' <b>x'.$us['present_count'].'</b>';
            $response['text'] = "Задание успешно завершено!";
            $response['error'] = "success";
        }else{
            $response['text'] = "Вы не завершили это задание!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Это задание уже закончено!";
        $response['error'] = "error";
    }
}else{
    $response['text'] = "У вас нет этого задания!";
    $response['error'] = "error";
}
}

// if(!empty($_POST['startIvent'])){
//     $user = $mysqli->query("SELECT `id`,`user_group`,`lvl`,`login` FROM `users` WHERE `id`= ".$_SESSION['id']." ")->fetch_assoc();
//             if($user['user_group'] <= 6 or $user['user_group'] == 100){
//                 if($user['lvl'] >= 7){
//                     if(time() > 16577){
//                     //     $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (1,'.$_SESSION['id'].',100)');
//                     // $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (2,'.$_SESSION['id'].',1)');
//                     // $mysqli->query('INSERT INTO ivent_mission_check (mission,user,max) VALUES (3,'.$_SESSION['id'].',20)');
                    
//                     $mysqli->query('INSERT INTO ivent_birthday (user,fight_turn,mission_turn) VALUES ('.$_SESSION['id'].',1,1)');
                    
//                     $bd = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = 448 AND time > ".time())->fetch_assoc();
//     for($i=1;$i<=6;$i++){
//         $input = array(104,114,116,133,172,173,174,175,177,183,190,194,216,220,231,239,240,280,285,293,298,300,311,312,322,325,333,341,358,360,363,370,396,406,417,438,439,440,446,447,495,498,501,504,506,509,511,513,515,535,541,546,548,572,574,582,585,650,653,665,669,677,682,684,694,698,704,725,728,742,744,747,753,755,759,761,764,813,816,819,824,827,831,835,840,854,856,868,872,885);
//         $rand_keys = array_rand($input, 2);
//         $poker = $input[$rand_keys[1]];
//         $it1 = rand(493,498); $it2 = rand(493,498); if($bd and rand(1,100) < 20) { $it3 = 0; } else{ $it3 = rand(493,498);}
//         $mysqli->query('INSERT INTO ivent_birthday_playing (pok,user,item1,item2,item3) VALUES ('.$poker.','.$_SESSION['id'].','.$it1.','.$it2.','.$it3.')');
                    
//     }
//                         $response['text'] = "Поздравляем, ".$user['login']."! Вы успешно начали участие в новогоднем ивенте!";
//                         $response['error'] = 'success';
//                     }else{
//                         $response['text'] = "Ивент будет доступен в 21:00!";
//                         $response['error'] = 'error';
//                     }
//                 }else{
//                     $response['text'] = "Вы должны иметь минимум 7 уровень для участия в ивенте!";
//                     $response['error'] = 'error';
//                 }
//             }else{
//                 $response['text'] = "Нельзя начать пропуск, находясь в тюрьме!";
//                 $response['error'] = 'error';
//             }
// }

if(!empty($_POST['startIventWeek'])){
    $user = $mysqli->query("SELECT `id`,`user_group`,`lvl`,`login` FROM `users` WHERE `id`= ".$_SESSION['id']." ")->fetch_assoc();
    $server = $mysqli->query("SELECT * FROM `system` WHERE `id`= 1")->fetch_assoc();
            if($user['user_group'] <= 6 or $user['user_group'] == 100){
                if($user['lvl'] >= 10){
                    if(4 == 4){
                        if($server['week'] == 1){
                            $ivent = $mysqli->query("SELECT * FROM a_ivent_week_arheolog WHERE user = ".$_SESSION['id'])->fetch_assoc();
                            if(empty($ivent)){
                                $mysqli->query('INSERT INTO a_ivent_week_arheolog (user,shovel) VALUES ('.$_SESSION['id'].',5)');
                                $mysqli->query('INSERT INTO a_ivent_week_mission (user) VALUES ('.$_SESSION['id'].')');
                                new_arheolog_map(1);
                                $response['text'] = "Поздравляем, ".$user['login']."! Вы успешно начали участие в недельной акции!";
                                $response['error'] = 'success';
                            }else{
                                $response['text'] = "Вы уже участвуете!";
                                $response['error'] = 'error';
                            }
                            
                        }elseif($server['week'] == 2){
                            $ivent = $mysqli->query("SELECT * FROM a_ivent_week_labirint WHERE user = ".$_SESSION['id'])->fetch_assoc();
                            if(empty($ivent)){
                                $mysqli->query('INSERT INTO a_ivent_week_labirint (user) VALUES ('.$_SESSION['id'].')');
                                $mysqli->query('INSERT INTO a_ivent_week_mission (user) VALUES ('.$_SESSION['id'].')');
                                $response['text'] = "Поздравляем, ".$user['login']."! Вы успешно начали участие в недельной акции!";
                                $response['error'] = 'success';
                            }else{
                                $response['text'] = "Вы уже участвуете!";
                                $response['error'] = 'error';
                            }
                            
                        }elseif($server['week'] == 3){
                            $ivent = $mysqli->query("SELECT * FROM a_ivent_week_playhome WHERE user = ".$_SESSION['id'])->fetch_assoc();
                            if(empty($ivent)){
                                $mysqli->query('INSERT INTO a_ivent_week_playhome (user,ticket) VALUES ('.$_SESSION['id'].',15)');
                                $mysqli->query('INSERT INTO a_ivent_week_mission (user,fight) VALUES ('.$_SESSION['id'].',20)');
                                $col1 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                $col2 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                $col3 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                $input = array(104,114,116,133,172,173,174,175,177,183,190,194,216,220,231,239,240,280,285,293,298,300,311,312,322,325,333,341,358,360,363,370,396,406,417,438,439,440,446,447,495,498,501,504,506,509,511,513,515,535,541,546,548,572,574,582,585,650,653,665,669,677,682,684,694,698,704,725,728,742,744,747,753,755,759,761,764,813,816,819,824,827,831,835,840,854,856,868,872,885);
                                $rand_keys = array_rand($input, 2);
                                $rand_keys2 = array_rand($input, 2);
                                $rand_keys3 = array_rand($input, 2);
                                $rand_keys4 = array_rand($input, 2);
                                $poker = $input[$rand_keys[1]];
                                $poker2 = $input[$rand_keys2[1]];
                                $poker3 = $input[$rand_keys3[1]];
                                $poker4 = $input[$rand_keys4[1]];
                                $pok1 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                $pok2 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                $pok3 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                $pok4 = rand(493,498).','.rand(493,498).','.rand(493,498);
                                
                                
                                $mysqli->query('INSERT INTO a_ivent_week_playhome_game (user,col_1,col_2,col_3,pok1,pok_i_1,pok2,pok_i_2,pok3,pok_i_3,pok4,pok_i_4) VALUES 
                                ('.$_SESSION['id'].',"'.$col1.'","'.$col2.'","'.$col3.'","'.$poker.'","'.$pok1.'","'.$poker2.'","'.$pok2.'","'.$poker3.'","'.$pok3.'","'.$poker4.'","'.$pok4.'")');
                                $response['text'] = "Поздравляем, ".$user['login']."! Вы успешно начали участие в недельной акции!";
                                $response['error'] = 'success';
                            }else{
                                $response['text'] = "Вы уже участвуете!";
                                $response['error'] = 'error';
                            }
                            
                        }else{
                            $response['text'] = "Ошибка!";
                            $response['error'] = 'error';
                        }
                    }else{
                        $response['text'] = "Акция еще не доступна!";
                        $response['error'] = 'error';
                    }
                }else{
                    $response['text'] = "Вы должны иметь минимум 10 уровень для участия в акции!";
                    $response['error'] = 'error';
                }
            }else{
                $response['text'] = "Нельзя начать пропуск, находясь в тюрьме!";
                $response['error'] = 'error';
            }
}

if(!empty($_POST['bosspve'])){
    $id = escapeMe($_POST['bosspve']);
    $ivent = $mysqli->query("SELECT * FROM ivent_birthday WHERE user = ".$_SESSION['id'])->fetch_assoc();
    $user = $mysqli->query("SELECT * FROM users WHERE id = ".$_SESSION['id'])->fetch_assoc();
    $location = $mysqli->query("SELECT * FROM base_location WHERE id = ".$user['location'])->fetch_assoc();
    $base = $mysqli->query("SELECT * FROM ivent WHERE turn = ".$ivent['fight_turn']." AND id_pok_turn = ".$id)->fetch_assoc();
    $baf = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = '448' AND `time` >= '".time()."'")->fetch_assoc();
    
    $my_pok = $mysqli->query("SELECT * FROM user_pokemons WHERE user_id = ".$_SESSION['id']." AND active = 1");
    $timeOnl = $ivent['fight_time']-time();
		$timeOnl = time()+$timeOnl;
		$time = downcountermin($timeOnl);
    if($user['status'] == 'free') {
        if($location['pve'] != 0){
            if($ivent['fight_time'] < time()){
                if($ivent['fight_turn'] <= 20){
                    if($ivent['fight_check'] != 1){
                        if($my_pok->num_rows == 1){
                            $my_pok = $my_pok->fetch_assoc();
                            $lvl = $my_pok['lvl']+rand(5,9);
                            if($lvl < 45) $lvl = 45;
                            if($lvl > 100) $lvl = 100;
                            if($baf){
                                $t = time()+3600*($ivent['fight_turn']/6);
                            }else{
                                $t = time()+3600*($ivent['fight_turn']/3);
                            }
                        $mysqli->query("UPDATE `users` SET `status`='battle' WHERE `id`='".$_SESSION['id']."'");
                        $mysqli->query("UPDATE `ivent_birthday` SET `fight_time`='".$t."' WHERE `user`='".$_SESSION['id']."'");
        $location_id = $mysqli->query("SELECT `id`,`login`,`user_group`,`region`,`location`,`sex`,`ban`,`status`,`status_id`,`rating`,`rang`,`botID`,`sprite` FROM `users` WHERE `id`='".$_SESSION['id']."'")->fetch_assoc();
        
        // if($ivent['fight_turn'] <= 10){
            Info::_generatePve($location_id, $location_id['location'],null,null,$base['battle_id'],$lvl);
        // }else{
        //     Info::_generatePve($location_id, $location_id['location'],null,null,268,$lvl);
        // }
        $response['text'] = "Бой начался!";
        $response['error'] = "success";
                        }else{
                            $response['text'] = "У вас должен быть 1 покемон в команде!";
                        $response['error'] = "error";
                        }
                    }else{
                        $response['text'] = "Вы уже победили на этом этапе, заберите приз!";
                        $response['error'] = "error";
                    }
                }else{
                    $response['text'] = "Вы уже прошли все этапы!";
                    $response['error'] = "error";
                }
            }else{
                $response['text'] = "До следующего боя ".$time."!";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "Начать бой можно только на локации с дикими покемонами!";
            $response['error'] = "error";
        }
    }else{
        $response['text'] = "Вы заняты!";
        $response['error'] = "error";
    }
        
}
if(!empty($_POST['playing_add'])){
    $bd = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = 448 AND time > ".time())->fetch_assoc();
    for($i=1;$i<=6;$i++){
        $input = array(104,114,116,133,172,173,174,175,177,183,190,194,216,220,231,239,240,280,285,293,298,300,311,312,322,325,333,341,358,360,363,370,396,406,417,438,439,440,446,447,495,498,501,504,506,509,511,513,515,535,541,546,548,572,574,582,585,650,653,665,669,677,682,684,694,698,704,725,728,742,744,747,753,755,759,761,764,813,816,819,824,827,831,835,840,854,856,868,872,885);
        $rand_keys = array_rand($input, 2);
        $poker = $input[$rand_keys[1]];
        $it1 = rand(493,498); $it2 = rand(493,498); if($bd and rand(1,100) < 20) { $it3 = 0; } else{ $it3 = rand(493,498);}
        $mysqli->query('INSERT INTO ivent_birthday_playing (pok,user,item1,item2,item3) VALUES ('.$poker.','.$_SESSION['id'].','.$it1.','.$it2.','.$it3.')');
                    
    }
    $response['text'] = 'Первые покемоны получены!';
    $response['error'] = 'success';
}
if(!empty($_POST['playing'])){
    $item = $_POST['playing'];
    $pok = $_POST['pok'];
    $ivent = $mysqli->query("SELECT * FROM ivent_birthday_playing WHERE user = ".$_SESSION['id']." AND id = ".$pok)->fetch_assoc();
    $bd = $mysqli->query("SELECT * FROM ivent_birthday WHERE user = ".$_SESSION['id'])->fetch_assoc();
    if($ivent){
        if(($ivent['item1'] == $item) or ($ivent['item2'] == $item) or ($ivent['item3'] == $item)){
            if(item_isset($item,1)){
                if($item == $ivent['item1']){
                    $mysqli->query("UPDATE `ivent_birthday_playing` SET `item1` = '0' WHERE `id`='".$pok."'");
                }else{
                    if($item == $ivent['item2']){
                        $mysqli->query("UPDATE `ivent_birthday_playing` SET `item2` = '0' WHERE `id`='".$pok."'");
                    }else{
                        if($item == $ivent['item3']){
                            $mysqli->query("UPDATE `ivent_birthday_playing` SET `item3` = '0' WHERE `id`='".$pok."'");
                        }
                    }
                }
                
                minus_item($item,1);
                $ivev = $mysqli->query("SELECT * FROM ivent_birthday_playing WHERE user = ".$_SESSION['id']." AND id = ".$pok)->fetch_assoc();
                if($ivev['item1'] == 0 AND $ivev['item2'] == 0 AND $ivev['item3'] == 0){
                    $updpoint = $bd['playing_point']+1;
                    $mysqli->query("UPDATE `ivent_birthday` SET `playing_point`= '".$updpoint."' WHERE `user`='".$_SESSION['id']."'");
                    $response['wishpoint'] = number_format($updpoint,0,'.','.');
                    $response['hide'] = 1;
                    $response['plus'] = '<img src="/img/world/items/little/500.png" class="item" > Жетон радости <b>x1</b><br>';
                    $input = array(104,114,116,133,172,173,174,175,177,183,190,194,216,220,231,239,240,280,285,293,298,300,311,312,322,325,333,341,358,360,363,370,396,406,417,438,439,440,446,447,495,498,501,504,506,509,511,513,515,535,541,546,548,572,574,582,585,650,653,665,669,677,682,684,694,698,704,725,728,742,744,747,753,755,759,761,764,813,816,819,824,827,831,835,840,854,856,868,872,885);
                    $rand_keys = array_rand($input, 2);
                    $poker = $input[$rand_keys[1]];
                    $bd_baf = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = 448 AND time > ".time())->fetch_assoc();
                    $it1 = rand(493,498); $it2 = rand(493,498); if($bd_baf and rand(1,100) < 20) { $it3 = 0; } else{ $it3 = rand(493,498);}
                    $mysqli->query('INSERT INTO ivent_birthday_playing (pok,user,item1,item2,item3) VALUES ('.$poker.','.$_SESSION['id'].','.$it1.','.$it2.','.$it3.')');
                    $pok_id = $mysqli->insert_id;
                    $response['pok'] = $pok_id;
                    if($it3 != 0){
                        $vcb = '<img src="/img/world/items/little/'.$it3.'.png">';
                    }else{
                        $vcb = '';
                    }
                    $response['addwish'] = '
                                <div class="WishPok">
                                    <img src="/img/world/items/little/'.$it1.'.png">
                                    <img src="/img/world/items/little/'.$it2.'.png">
                                    '.$vcb.'</div>
                                <div class="Image"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$poker.'.gif"></div>
                            ';
                    $mysqli->query('DELETE FROM ivent_birthday_playing WHERE id = '.$pok);
                }else{
                    if(item_isset_count($item) != 0){ $func = 'onclick="issetAll('.$item.',\'playing_house\');"'; $activ = ""; }else{ $func = ''; $activ = "no-active"; }
                $response['wishitem'] = '<div '.$func.' class="'.$activ.'"><img src="/img/world/items/little/'.$item.'.png"></div>';
    
                $vc .= '<div class="WishPok">';
                                
                    if($ivev['item1'] != 0){ $vc .= '<img src="/img/world/items/little/'.$ivev['item1'].'.png">'; }
                    if($ivev['item2'] != 0){ $vc .= '<img src="/img/world/items/little/'.$ivev['item2'].'.png">'; }
                    if($ivev['item3'] != 0){ $vc .= '<img src="/img/world/items/little/'.$ivev['item3'].'.png">'; }
                                $vc .= '</div>
                                <div class="Image"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$ivev['pok'].'.gif"></div>';
                $response['wishpok'] = $vc;
                }
                $ib = $mysqli->query("SELECT name FROM base_items WHERE id = ".$item)->fetch_assoc();
                
                $response['minus'] = '<img src="/img/world/items/little/'.$item.'.png" class="item" > '.$ib['name'].' <b>x1</b><br>';
                
                $response['text'] = "Покемону понравился предмет!";
                $response['error'] = "success";
            }else{
                $response['text'] = "У вас недостаточно этого предмета";
                $response['error'] = "error";
            }
        }else{
            $response['text'] = "Этому покемону не нужен этот предмет!";
            $response['error'] = "error";
        }  
    }else{
        $response['text'] = "Ошибка!";
        $response['error'] = "error";
    }
}
if(!empty($_POST['coocking'])){
    switch($_POST['category']){
        case 'coocking':
            $ivent = $mysqli->query("SELECT * FROM ivent_birthday_coocking WHERE user = ".$_SESSION['id']." AND id = ".$_POST['id'])->fetch_assoc();
            if($ivent){
                $er = 0;
                $b = explode(';',$ivent['ingred']);
                $n = count($b);
                for($i=0;$i < $n;$i++){
                    $c = explode(',',$b[$i]);
                    if(!item_isset($c[0],$c[1])){
                        $er++;
                    }
                }
                if($er == 0){
                    $t = time()+60*$ivent['time'];
                    $mysqli->query("UPDATE `ivent_birthday_coocking` SET `starter`='".$t."', `type` = '1' WHERE `id`='".$ivent['id']."'");
                    for($i=0;$i < $n;$i++){
                            $c = explode(',',$b[$i]);
                            $it_bd_ingred = $mysqli->query("SELECT `name`,`id` FROM `base_items` WHERE `id` = '".$c[0]."' ")->fetch_assoc();
                            minus_item($c[0],$c[1]);
                            $min .= '<img src="/img/world/items/little/'.$c[0].'.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> '.$it_bd_ingred['name'].' <b>x'.number_format($c[1],0,'.','.').'</b><br>';
                        }
                        $response['minus'] = $min;
                        $response['text'] = "Приготовление началось!";
                        $response['error'] = "success";
                }else{
                    $response['text'] = "Вам не хватает ингредиентов!";
                    $response['error'] = "error";
                }
            }else{
                $response['text'] = "Рецепт не найден!";
                $response['error'] = "error";
            }
            
        break;
        case 'delete':
            $ivent = $mysqli->query("SELECT * FROM ivent_birthday_coocking WHERE user = ".$_SESSION['id']." AND id = ".$_POST['id'])->fetch_assoc();
            $t = time()+3600;
            $mysqli->query("UPDATE `ivent_birthday_coocking` SET `starter`='".$t."', `type` = '2' WHERE `id`='".$ivent['id']."'");
             $response['text'] = "Обновление началось!";
                        $response['error'] = "success";
        break;
        case 'ready':
            $ivent = $mysqli->query("SELECT * FROM ivent_birthday_coocking WHERE user = ".$_SESSION['id']." AND id = ".$_POST['id'])->fetch_assoc();
            $baf = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = '448' AND `time` > '".time()."' ")->fetch_assoc();
            $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
            if($ivent['starter'] < time()){
                $rand = rand(1,100);
                if($rand >= 1 and $rand < 10){
                    if($baf){ $time = 100;}else{ $time = 145;}
                    $gen_ingred = "501,".rand(2,4).";503,".rand(2,4).";506,".rand(2,5).";505,".rand(2,3).";504,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',512,"Ягодный торт",3700,"'.$gen_ingred.'",'.$time.',"legend")');
                }elseif($rand >= 10 and $rand < 28){
                    if($baf){ $time = 60;}else{ $time = 90;}
                    $gen_ingred = "501,".rand(2,4).";504,".rand(2,4).";505,".rand(2,4).";507,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',511,"Шоколадный пирог",2300,"'.$gen_ingred.'",'.$time.',"rare")');
                }elseif($rand >= 28 and $rand < 60){
                    if($baf){ $time = 35;}else{ $time = 50;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(2,4).";506,".rand(3,6).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',510,"Ягодный пирог",1200,"'.$gen_ingred.'",'.$time.',"medium")');
                }elseif($rand >= 60 and $rand <= 100){
                    if($baf){ $time = 20;}else{ $time = 30;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(3,5).";506,".rand(3,6)."";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',508,"Желе",650,"'.$gen_ingred.'",'.$time.',"easy")');
                }
                $response['text'] = "Рецепт успешно приготовлен!";
                $response['error'] = "success";
                $response['plus'] = 'Поинт кулинарии х<b>'.number_format($ivent['point'],0,'.','.').'</b>';
                $usupd = $us['coocking_point']+$ivent['point'];
                $mysqli->query("UPDATE `ivent_birthday` SET `coocking_point`='".$usupd."' WHERE `id`='".$us['id']."'");
                $mysqli->query("DELETE FROM `ivent_birthday_coocking` WHERE `id` = '".$ivent['id']."' ");
            }else{
                $response['text'] = "Время еще не пришло!";
                $response['error'] = "error";
            }
        break;
        case 'space':
            $ivent = $mysqli->query("SELECT * FROM ivent_birthday_coocking WHERE user = ".$_SESSION['id']." AND id = ".$_POST['id'])->fetch_assoc();
            $baf = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = '448' AND `time` > '".time()."' ")->fetch_assoc();
            if($ivent['starter'] < time()){
                $rand = rand(1,100);
                if($rand >= 1 and $rand < 10){
                    if($baf){ $time = 100;}else{ $time = 145;}
                    $gen_ingred = "501,".rand(2,4).";503,".rand(2,4).";506,".rand(2,5).";505,".rand(2,3).";504,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',512,"Ягодный торт",3700,"'.$gen_ingred.'",'.$time.',"legend")');
                }elseif($rand >= 10 and $rand < 28){
                    if($baf){ $time = 60;}else{ $time = 90;}
                    $gen_ingred = "501,".rand(2,4).";504,".rand(2,4).";505,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',511,"Шоколадный пирог",2300,"'.$gen_ingred.'",'.$time.',"rare")');
                }elseif($rand >= 28 and $rand < 60){
                    if($baf){ $time = 35;}else{ $time = 50;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(2,4).";506,".rand(3,6).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',510,"Ягодный пирог",1200,"'.$gen_ingred.'",'.$time.',"medium")');
                }elseif($rand >= 60 and $rand <= 100){
                    if($baf){ $time = 20;}else{ $time = 30;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(3,5).";506,".rand(3,6)."";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',508,"Желе",650,"'.$gen_ingred.'",'.$time.',"easy")');
                }
                $response['text'] = "Вы получили новый рецепт!";
                $response['error'] = "success";
                $mysqli->query("DELETE FROM `ivent_birthday_coocking` WHERE `id` = '".$ivent['id']."' ");
            }else{
                $response['text'] = "Время еще не пришло!";
                $response['error'] = "error";
            }
        break;
        case 'starter':
             $ivent = $mysqli->query("SELECT * FROM ivent_birthday_coocking WHERE user = ".$_SESSION['id']." AND id = ".$_POST['id'])->fetch_assoc();
            $baf = $mysqli->query("SELECT * FROM bafs WHERE user = ".$_SESSION['id']." AND baf = '448' AND `time` > '".time()."' ")->fetch_assoc();
            $rand = rand(1,100);
            $rand2 = rand(1,100);
                if($rand >= 1 and $rand < 10){
                    if($baf){ $time = 100;}else{ $time = 145;}
                    $gen_ingred = "501,".rand(2,4).";503,".rand(2,4).";506,".rand(2,5).";505,".rand(2,3).";504,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',512,"Ягодный торт",3700,"'.$gen_ingred.'",'.$time.',"legend")');
                }elseif($rand >= 10 and $rand < 28){
                    if($baf){ $time = 60;}else{ $time = 90;}
                    $gen_ingred = "501,".rand(2,4).";504,".rand(2,4).";505,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',511,"Шоколадный пирог",2300,"'.$gen_ingred.'",'.$time.',"rare")');
                }elseif($rand >= 28 and $rand < 60){
                    if($baf){ $time = 35;}else{ $time = 50;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(2,4).";506,".rand(3,6).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',510,"Ягодный пирог",1200,"'.$gen_ingred.'",'.$time.',"medium")');
                }elseif($rand >= 60 and $rand <= 100){
                    if($baf){ $time = 20;}else{ $time = 30;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(3,5).";506,".rand(3,6)."";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',508,"Желе",650,"'.$gen_ingred.'",'.$time.',"easy")');
                }
                
                if($rand2 >= 1 and $rand2 < 10){
                    if($baf){ $time = 100;}else{ $time = 145;}
                    $gen_ingred = "501,".rand(2,4).";503,".rand(2,4).";506,".rand(2,5).";505,".rand(2,3).";504,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',512,"Ягодный торт",3700,"'.$gen_ingred.'",'.$time.',"legend")');
                }elseif($rand2 >= 10 and $rand2 < 28){
                    if($baf){ $time = 60;}else{ $time = 90;}
                    $gen_ingred = "501,".rand(2,4).";504,".rand(2,4).";505,".rand(2,4).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',511,"Шоколадный пирог",2300,"'.$gen_ingred.'",'.$time.',"rare")');
                }elseif($rand2 >= 28 and $rand2 < 60){
                    if($baf){ $time = 35;}else{ $time = 50;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(2,4).";506,".rand(3,6).";509,1";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',510,"Ягодный пирог",1200,"'.$gen_ingred.'",'.$time.',"medium")');
                }elseif($rand2 >= 60 and $rand2 <= 100){
                    if($baf){ $time = 20;}else{ $time = 30;}
                    $gen_ingred = "502,".rand(2,4).";503,".rand(3,5).";506,".rand(3,6)."";
                    $mysqli->query('INSERT INTO ivent_birthday_coocking (user,coock,name,point,ingred,time,category) VALUES 
                                                                        ('.$_SESSION['id'].',508,"Желе",650,"'.$gen_ingred.'",'.$time.',"easy")');
                }
                
                $response['text'] = "Вы получили стартовые рецепты!";
                $response['error'] = "success";
        break;
        case 'prize':
                $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
                if($us['coocking_point'] >= 1000){
                    $r = rand(1,265);
                    if($r >= 1 and $r < 40){
                        $response['plus'] = '<img src="/img/world/items/little/1.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Монета <b>x100.000</b>';
                        itemAdd(1,100000);
                    }elseif($r >= 40 and $r < 55){
                        $response['plus'] = '<img src="/img/world/items/little/185.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Графитовый колокольчик <b>x1</b>';
                        itemAdd(185,1);
                    }elseif($r >= 55 and $r < 70){
                        $response['plus'] = '<img src="/img/world/items/little/191.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Старый колокольчик <b>x1</b>';
                        itemAdd(191,1);
                    }elseif($r >= 70 and $r < 75){
                        $response['plus'] = '<img src="/img/world/items/little/223.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Загадочный покебол <b>x1</b>';
                        itemAdd(223,1);
                    }elseif($r >= 75 and $r < 90){
                        $response['plus'] = '<img src="/img/world/items/little/195.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Коробка с окаменелостями <b>x1</b>';
                        itemAdd(195,1);
                    }elseif($r >= 90 and $r < 105){
                        $response['plus'] = '<img src="/img/world/items/little/448.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Премиум <b>x1</b>';
                        itemAdd(448,1);
                    }elseif($r >= 105 and $r < 130){
                        $response['plus'] = '<img src="/img/world/items/little/53.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Бриллиантовый покебол <b>x1</b>';
                        itemAdd(53,1);
                    }elseif($r >= 130 and $r < 140){
                        $response['plus'] = '<img src="/img/world/items/little/32.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Шоколадная конфета <b>x1</b>';
                        itemAdd(32,1);
                    }elseif($r >= 140 and $r < 150){
                        $response['plus'] = '<img src="/img/world/items/little/34.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Черная конфета <b>x1</b>';
                        itemAdd(34,1);
                    }elseif($r >= 150 and $r < 175){
                        $r = rand(35,52);
                        $response['plus'] = '<img src="/img/world/items/little/'.$r.'.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Типовая конфета <b>x20</b>';
                        itemAdd($r,20);
                    }elseif($r >= 175 and $r < 200){
                        $response['plus'] = '<img src="/img/world/items/little/197.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Набор тренировки <b>x3</b>';
                        itemAdd(197,3);
                    }elseif($r >= 200 and $r < 220){
                        $response['plus'] = '<img src="/img/world/items/little/246.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Сладкий кекс <b>x1</b>';
                        itemAdd(246,1);
                    }elseif($r >= 220 and $r < 235){
                        $response['plus'] = '<img src="/img/world/items/little/255.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Корень априкорна <b>x1</b>';
                        itemAdd(255,1);
                    }elseif($r >= 235 and $r < 250){
                        $response['plus'] = '<img src="/img/world/items/little/268.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Зелье памяти <b>x1</b>';
                        itemAdd(268,1);
                    }elseif($r >= 250 and $r <= 265){
                        $response['plus'] = '<img src="/img/world/items/little/513.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Заготовочный диск <b>x1</b>';
                        itemAdd(513,1);
                    }
                    $usupd = $us['coocking_point']-1000;
                    $mysqli->query("UPDATE `ivent_birthday` SET `coocking_point`='".$usupd."' WHERE `id`='".$us['id']."'");
                    $response['text'] = "Обмен прошел успешно!";
                    $response['error'] = "success";
                    $response['minus'] = 'Поинт кулинарии х<b>1.000</b>';
                }else{
                    $response['text'] = "Вам не хватает очков!";
                    $response['error'] = "error";
                }
        break;
    }
}

if(!empty($_POST['tree_chance'])){
    $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    if($us){
        if(item_isset(25,5)) {
            if($us['chance'] <= 55){
                $response['minus'] = '<img src="/img/world/items/little/25.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Драгоценный камень <b>x5</b><br>';
                $upd_user = $us['chance']+5;
                $mysqli->query("UPDATE `ivent_birthday` SET `chance`= '".$upd_user."' WHERE `user`='".$_SESSION['id']."'");
                minus_item(25,5);
                $response['text'] = "Ваш шанс увеличен!";
                $response['error'] = "success";
            }else{
                $response['text'] = 'У вас максимальный шанс!';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'У вас нет камней!';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Пользователь не найден!';
        $response['error'] = 'error';
    }
}
if(!empty($_POST['tree_prize'])){
    $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    if($us){
        $e = explode(',',$us['poluch']);
        if($_POST['t'] == 1) {
            if($e[0] == 0){
                if($us['tree'] >= 10){
                    $tp .= '<img src="/img/world/items/little/1.png" class="item" > Монета <b>x100.000</b><br>';
                    itemAdd(1,100000);
                }
                if($us['tree'] >= 25){
                    $tp .= '<img src="/img/world/items/little/30.png" class="item" > Розовая конфета <b>x20</b><br>';
                    itemAdd(30,20);
                }
                if($us['tree'] >= 50){
                    $tp .= '<img src="/img/world/items/little/53.png" class="item" > Бриллиантовый покебол <b>x10</b><br>';
                    itemAdd(53,10);
                }
                if($us['tree'] >= 75){
                    $countDay = mt_rand(7,10);
                    $tm = time()+(3600*24*$countDay);
                    $gens = "25,25,25,25,25,25";
                    plusEgg($gens,false,false,true,$tm,88,false,$_SESSION['id'],'alola');;
                    $tp .= "<img src='/img/world/items/little/151.png' class='item'> Яйцо #088 Граймер - Алола<br>";
                }
                if($us['tree'] >= 100){
                    $r = rand(35,52);
                    $tp .= '<img src="/img/world/items/little/'.$r.'.png" class="item" > Типовая конфета <b>x25</b><br>';
                    itemAdd($r,25);
                }
                if($us['tree'] >= 150){
                    $tp .= '<img src="/img/world/items/little/187.png" class="item" > Приманка <b>x10</b><br>';
                    itemAdd(187,10);
                }
                if($us['tree'] >= 175){
                    $countDay = mt_rand(7,10);
                    $tm = time()+(3600*24*$countDay);
                    $gens = "25,25,25,25,25,25";
                    plusEgg($gens,false,false,true,$tm,79,false,$_SESSION['id'],'galar');
                    $tp .= "<img src='/img/world/items/little/151.png' class='item'> Яйцо #079 Слоупок - Галар<br>";
                }
                if($us['tree'] >= 200){
                    $tp .= '<img src="/img/world/items/little/196.png" class="item" > Именной бланк <b>x5</b><br>';
                    itemAdd(196,5);
                }
                if($us['tree'] >= 250){
                    $countDay = mt_rand(7,10);
                    $tm = time()+(3600*24*$countDay);
                    $gens = "25,25,25,25,25,25";
                    plusEgg($gens,false,false,true,$tm,122,false,$_SESSION['id'],'galar');
                    $tp .= "<img src='/img/world/items/little/151.png' class='item'> Яйцо #122 Мистер Майм - Галар<br>";
                }
                if($us['tree'] >= 300){
                    $tp .= '<img src="/img/world/items/little/426.png" class="item" > Сладкая вата <b>x15</b><br>';
                    itemAdd(426,15);
                }
                if($us['tree'] >= 350){
                    $tp .= '<img src="/img/world/items/little/34.png" class="item" > Черная конфета <b>x4</b><br>';
                    itemAdd(34,4);
                }
                if($us['tree'] >= 500){
                    $countDay = mt_rand(7,10);
                    $tm = time()+(3600*24*$countDay);
                    $gens = "25,25,25,25,25,25";
                    plusEgg($gens,false,false,true,$tm,554,false,$_SESSION['id'],'galar');
                    $tp .= "<img src='/img/world/items/little/151.png' class='item'> Яйцо #554 Дарумака - Галар<br>";
                }
                if($us['tree'] >= 600){
                    if(rand(1,2) == 1){
                        $tp .= '<img src="/img/world/items/little/269.png" class="item" > Гормон тестостерон <b>x5</b><br>';
                        itemAdd(269,5);
                    }else{
                        $tp .= '<img src="/img/world/items/little/270.png" class="item" > Гормон эстроген <b>x5</b><br>';
                        itemAdd(270,5);
                    }
                }
                if($us['tree'] >= 750){
                    $tp .= '<img src="/img/world/items/little/268.png" class="item" > Зелье памяти <b>x4</b><br>';
                    itemAdd(268,4);
                }
                if($us['tree'] >= 1000){
                    $tp .= '<img src="/img/world/items/little/255.png" class="item" > Корень априкорна <b>x2</b><br>';
                    itemAdd(255,2);
                }
                if($us['tree'] >= 1250){
                    $tp .= '<img src="/img/world/items/little/32.png" class="item" > Шоколадная конфета <b>x3</b><br>';
                    itemAdd(32,3);
                }
                if($us['tree'] >= 1500){
                    $tp .= '<img src="/img/world/items/little/289.png" class="item" > Препарат Q <b>x3</b><br>';
                    itemAdd(289,3);
                }
                if($us['tree'] >= 2000){
                    $r = rand(1,4);
                    if($r == 1){$pok = 246;}elseif($r == 2){$pok = 371;}elseif($r == 3){$pok = 374;}else{$pok = 633;}
                    $countDay = mt_rand(7,10);
                    $tm = time()+(3600*24*$countDay);
                    $gens = "25,25,25,25,25,25";
                    plusEgg($gens,false,false,true,$tm,$pok,false);
                    $tp .= "<img src='/img/world/items/little/151.png' class='item'> Яйцо покемона<br>";
                }
                $e[0] = 1;
                $upd = implode(',',$e);
                $mysqli->query("UPDATE `ivent_birthday` SET `poluch`= '".$upd."' WHERE `user`='".$_SESSION['id']."'");
                $response['plus'] = $tp;
                $response['text'] = 'Спасибо за участие!';
                $response['error'] = 'success';
            }else{
                $response['text'] = 'Вы уже получили награду!';
                $response['error'] = 'error';
            }
        }elseif($_POST['t'] == 2){
            if($us['tree'] >= 300 and $e[1] == 0){
                itemAdd(196,2);
                itemAdd(34,3);
                itemAdd(197,10);
                itemAdd(256,3);
                itemAdd(268,2);
                $countDay = mt_rand(7,10);
                $tm = time()+(3600*24*$countDay);
                $gens = "25,25,25,25,25,25";
                plusEgg($gens,false,false,true,$tm,776,false);
                plusEgg($gens,false,false,true,$tm,131,false);
                $response['plus'] = 'Вы получили все призы';
                $response['text'] = 'Спасибо за участие!';
                $response['error'] = 'success';
            }else{
                $response['text'] = 'Ошибка!';
                $response['error'] = 'error';
            }
            $e[1] = 1;
            $upd = implode(',',$e);
            $mysqli->query("UPDATE `ivent_birthday` SET `poluch`= '".$upd."' WHERE `user`='".$_SESSION['id']."'");
        }else{
            $response['text'] = 'Тип не правильный!';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Пользователь не найден!';
        $response['error'] = 'error';
    }
}
if(!empty($_POST['tree_give'])){
    $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
    $sys = $mysqli->query('SELECT * FROM `system` WHERE `id` = 1 ')->fetch_assoc();
    if($us){
        if(item_isset(514,1)) {
            $count = item_isset_count(514);
            $response['minus'] = '<img src="/img/world/items/little/514.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Еловая ветка <b>x'.number_format($count,0,'.','.').'</b><br>';
            $upd_user = $us['tree']+$count;
            $upd_sys = $sys['tree']+$count;
            $mysqli->query("UPDATE `ivent_birthday` SET `tree`= '".$upd_user."' WHERE `user`='".$_SESSION['id']."'");
            $mysqli->query("UPDATE `system` SET `tree`= '".$upd_sys."' WHERE `id`= '1'");
            minus_item(514,$count);
            $response['text'] = "Ваши ветки сданы!";
            $response['error'] = "success";
        }else{
            $response['text'] = 'У вас нет веток!';
            $response['error'] = 'error';
        }
    }else{
        $response['text'] = 'Пользователь не найден!';
        $response['error'] = 'error';
    }
}






if(!empty($_POST['playing_give'])){
        $us = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'])->fetch_assoc();
        if($us){
            if($us['playing_point'] > 0){
                    $c1 = floor($us['playing_point']*4000);
                    if($c1 >= 1){
                        $pl .= '<img src="/img/world/items/little/1.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Монета <b>x'.number_format($c1,0,'.','.').'</b><br>';
                        itemAdd(1,$c1);
                    }
                    
                    $c2 = floor($us['playing_point']/5);
                    if($c2 >= 1){
                        $pl .= '<img src="/img/world/items/little/30.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Розовая конфета <b>x'.number_format($c2,0,'.','.').'</b><br>';
                        itemAdd(30,$c2);
                    }
                    
                    $c3 = floor($us['playing_point']/15);
                    if($c3 >= 1){
                        $pl .= '<img src="/img/world/items/little/246.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Кекс <b>x'.number_format($c3,0,'.','.').'</b><br>';
                        itemAdd(246,$c3);
                    }
                    
                    $c4 = floor($us['playing_point']/30);
                    if($c4 >= 1){
                        $pl .= '<img src="/img/world/items/little/197.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Набор тренировки <b>x'.number_format($c4,0,'.','.').'</b><br>';
                        itemAdd(197,$c4);
                    }
                    
                    $c5 = floor($us['playing_point']/45);
                    if($c5 >= 1){
                        $pl .= '<img src="/img/world/items/little/256.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Лекарство <b>x'.number_format($c5,0,'.','.').'</b><br>';
                        itemAdd(256,$c5);
                    }
                    
                    $c6 = floor($us['playing_point']/70);
                    if($c6 >= 1){
                        $pl .= '<img src="/img/world/items/little/34.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Черная конфета <b>x'.number_format($c6,0,'.','.').'</b><br>';
                        itemAdd(34,$c6);
                    }
                    
                    $c7 = floor($us['playing_point']/100);
                    if($c7 >= 1){
                        $pl .= '<img src="/img/world/items/little/513.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Заготовочный диск <b>x'.number_format($c7,0,'.','.').'</b><br>';
                        itemAdd(513,$c7);
                    }
                    
                    $c8 = floor($us['playing_point']/125);
                    if($c8 >= 1){
                        $pl .= '<img src="/img/world/items/little/32.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Шоколадная конфета <b>x'.number_format($c8,0,'.','.').'</b><br>';
                        itemAdd(32,$c8);
                    }
                    
                    $c9 = floor($us['playing_point']/150);
                    if($c9 >= 1){
                        $pl .= '<img src="/img/world/items/little/255.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Корень априкорна <b>x'.number_format($c9,0,'.','.').'</b><br>';
                        itemAdd(255,$c9);
                    }
                    
                    $c10 = floor($us['playing_point']/300);
                    if($c10 >= 1){
                        $pl .= '<img src="/img/world/items/little/223.png" class="item" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"> Загадочный покебол <b>x'.number_format($c10,0,'.','.').'</b><br>';
                        itemAdd(223,$c10);
                    }
                
                $mysqli->query("UPDATE `ivent_birthday` SET `playing_point`= '0' WHERE `user`='".$_SESSION['id']."'");
                $response['plus'] = $pl;
                $response['text'] = 'Призы выданы';
                $response['error'] = 'success';
            }else{
                $response['text'] = 'У вас нет жетонов!';
                $response['error'] = 'error';
            }
        }else{
            $response['text'] = 'Пользователь не найден!';
            $response['error'] = 'error';
        }

}










// --- Tracker endpoint for UI (missions HUD) ---
if(!empty($_POST['type']) && $_POST['type'] === 'tracker'){
    $uid = intval($_SESSION['id']);
    $textMap = array(
  1 => 'Поймайте 20 покемонов',
  2 => 'Получите яйцо #165 Ледиба',
  3 => 'Выбейте Острый клюв x1',
  4 => 'Выбейте Набор тренировки x1',
  5 => 'Победите 200 покемонов в PvE',
  6 => 'Потратьте 30.000 монет на лечение покемонов',
  7 => 'Найдите любые крышечки x5',
  8 => 'Поймайте #280 Ралтс',
  9 => 'Получите яйцо #016 Пиджи',
  10 => 'Получите яйцо #092 Гастли',
  11 => 'Получите яйцо #194 Вупер',
  12 => 'Выбейте Кварц x10',
  13 => 'Поучаствуйте в 8 PvP боях',
  14 => 'Получите 1.500 опыта',
  15 => 'Поймайте 5 любых покемонов с характером Обычный',
  16 => 'Получите яйцо любого покемона каменного типа',
  17 => 'Используйте в боях не менее 25 предметов',
  18 => 'Используйте на покемонов суммарно не менее 15 конфет',
  19 => 'Используйте любую пилюлю один раз',
  20 => 'Добавьте покемонам 30 EV витаминами',
  21 => 'Найдите любые априкорны x5',
  22 => 'Используйте любой эволвер',
  23 => 'Используйте Портативный инкубатор',
  24 => 'Получите яйцо водного покемона',
  25 => 'Получите яйцо травяного покемона',
  26 => 'Используйте 15 критических ударов',
  27 => 'Погуляйте с покемонами 15 раз',
  28 => 'Отправьте в пункт переработки 10 предметов',
);
    $hintMap = array(
  1 => 'Засчитывается только успешная поимка (если покемон пойман и появился в списке). Срыв шара/побег обычно не считается. Если прогресс не меняется — обновите календарь, иногда есть задержка до ~60 сек.',
  2 => 'Нужно получить яйцо #165 Ледиба (чтобы яйцо появилось в вашем списке яиц). Разведение/ивентовые способы — зависит от механики сервера. Если не засчиталось — проверьте, что задание активно сегодня, и обновите календарь (задержка до ~60 сек).',
  3 => 'Засчитывается при зачислении предмета «Острый клюв» в инвентарь. Если предмет выпал, но не был получен/зачислен — прогресс не пойдёт. Обновите календарь (иногда задержка до ~60 сек).',
  4 => 'Засчитывается при получении «Набор тренировки» в инвентарь. Если предмет выпал, но не был зачислен — прогресс не пойдёт. Возможна задержка обновления до ~60 сек.',
  5 => 'Засчитываются победы в PvE (дикие/тренеры) после завершения боя. Поражения/выход из боя обычно не учитываются. Если прогресс стоит — обновите календарь (возможна задержка).',
  6 => 'Считаются траты на лечение покемонов (как правило, именно в механике лечения/покецентре). Покупки/прочие траты обычно не засчитываются. Если не засчитало — проверьте место лечения и обновите календарь.',
  7 => 'Считается получение любых «крышечек» (за счёт находок/наград) при зачислении в инвентарь. Если прогресс не меняется — обновите календарь (задержка до ~60 сек).',
  8 => 'Нужна успешная поимка #280 Ралтс. Эволюция/обмен обычно не считаются. Обновление прогресса может быть с задержкой до ~60 сек.',
  9 => 'Нужно получить яйцо #016 Пиджи (должно появиться в списке яиц). Если не засчиталось — обновите календарь и проверьте, что задание активно.',
  10 => 'Нужно получить яйцо #092 Гастли (должно появиться в списке яиц). Возможна задержка обновления до ~60 сек.',
  11 => 'Нужно получить яйцо #194 Вупер (должно появиться в списке яиц). Если не засчиталось — обновите календарь.',
  12 => 'Считается зачисление «Кварц» в инвентарь суммарно x10. Если добываете пачками — прогресс может обновляться не мгновенно (до ~60 сек).',
  13 => 'Засчитываются завершённые PvP-бои (как правило, после результата). Отмены/выход могут не считаться. Если прогресс стоит — обновите календарь.',
  14 => 'Считается получение опыта суммарно (из боёв/наград). Иногда обновление идёт с задержкой до ~60 сек — просто обновите календарь.',
  15 => 'Считаются ПОЙМАННЫЕ сегодня покемоны с характером «Обычный». Старые покемоны не подходят. Проверьте характер в карточке и обновите календарь при задержке.',
  16 => 'Нужно получить яйцо покемона каменного типа (важно, чтобы яйцо появилось в списке яиц). Если не засчитало — обновите календарь.',
  17 => 'Считаются предметы, использованные прямо в бою (не вне боя). Если предмет «не применился» — прогресс не пойдёт. Возможна задержка обновления.',
  18 => 'Считаются конфеты, успешно применённые на покемонов суммарно (не просто покупка). Если упёрлись в ограничения — может не засчитываться. Обновите календарь.',
  19 => 'Считается успешное применение любой пилюли на покемона. Если действие отклонено (условия/лимиты) — прогресс не пойдёт. Обновите календарь.',
  20 => 'Считаются EV, добавленные витаминами суммарно на 30. Если EV не добавились из‑за лимитов — задание не продвинется. Обновление прогресса может быть с задержкой.',
  21 => 'Считаются найденные априкорны x5 при зачислении в инвентарь. Если прогресс стоит — обновите календарь (задержка до ~60 сек).',
  22 => 'Считается успешное использование эволвера (предмета эволюции). Если эволюция не произошла (условия не выполнены) — прогресс не пойдёт.',
  23 => 'Считается применение «Портативного инкубатора». Если предмет не применился из‑за условий (например, уже активен) — может не засчитаться.',
  24 => 'Нужно получить яйцо водного покемона (яйцо должно появиться в списке). Обновите календарь при задержке.',
  25 => 'Нужно получить яйцо травяного покемона (яйцо должно появиться в списке). Обновите календарь при задержке.',
  26 => 'Считаются критические удары в бою суммарно 15 (обычно после завершения боя). Если прогресс не двигается — обновите календарь.',
  27 => 'Считаются завершённые прогулки с покемонами (механика прогулки должна отработать и выдать результат). Если не засчитало — обновите календарь.',
  28 => 'Считаются предметы, отправленные в пункт переработки (не выброшенные). Если отправка не прошла — прогресс не пойдёт. Возможна задержка обновления.',
);
    $missions = array();
    $q = $mysqli->query('SELECT `id_mission`,`this_process`,`end_process`,`present_id`,`present_count`,`end` FROM `user_mission_day` WHERE `user` = '.$uid.' AND `end` = 0');
    while($m = $q->fetch_assoc()) {
        $mid = intval($m['id_mission']);
        $text = isset($textMap[$mid]) ? $textMap[$mid] : ('Задание #' . $mid);
        $hint = isset($hintMap[$mid]) ? $hintMap[$mid] : 'Если прогресс не меняется — обновите календарь. В некоторых действиях есть задержка обновления до ~60 сек.';
        $missions[] = array(
            'id' => $mid,
            'text' => $text,
            'hint' => $hint,
            'this' => intval($m['this_process']),
            'end'  => intval($m['end_process']),
            'present_id' => intval($m['present_id']),
            'present_count' => intval($m['present_count']),
            'can_claim' => (intval($m['this_process']) >= 1 && intval($m['this_process']) == intval($m['end_process'])) ? 1 : 0
        );
    }
    $response = array(
        'error' => 'success',
        'missions' => $missions,
        'ts' => time()
    );
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>
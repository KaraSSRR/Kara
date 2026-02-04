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
if(isset($_POST['op'])){
    $nursery = $mysqli->query('SELECT `id`,`basenum`,`name_new`,`gender`,`lvl`,`sparka`,`tren`,`type`,`trade`,`item_id`,`sparkaNumber`,`gen`,`vitamines` FROM `user_pokemons` WHERE `user_id` = '.$_SESSION['id'].' AND `active`= 0 ORDER BY `id` ASC');
    $total = $nursery->num_rows;
    $response['page'] = createLinks($total);
    die(json_encode($response));
}
if((isset($_POST['search']) && !empty($_POST['search'])) or (isset($_POST['dop']) && !empty($_POST['dop'])) ){
    
    if(isset($_POST['page'])){
        $page = ( $_POST['page'] - 1 ) * 20;
    }else{
        $page = 0;
    }
    $search = $mysqli->real_escape_string($_POST['search']);
    $search = clearStr($search);
    $dop = explode(',',$_POST['dop']);;
    if($dop[0] == 1){$d .= " AND `us`.`type` = 'shine' ";}elseif($dop[0] == 2){$d .= " AND `us`.`type` = 'normal' ";}else{$d .= "";}
    if($dop[1] == 1){$d .= " AND `us`.`gender` = 'Мальчик' ";}elseif($dop[1] == 2){$d .= " AND `us`.`gender` = 'Девочка' ";}elseif($dop[1] == 3){$d .= " AND `us`.`gender` = 'Бесполый' ";}else{$d .= "";}
    if($dop[2] == 1){$d .= " AND `us`.`sparka` = 1 ";}elseif($dop[2] == 2){$d .= " AND `us`.`sparka` = 0 ";}elseif($dop[2] == 3){$d .= " AND `us`.`sparka` = 0 AND `us`.`sparkaNumber` = 1 ";}elseif($dop[2] == 4){$d .= " AND `us`.`sparka` = 0 AND `us`.`sparkaNumber` = 2 ";}elseif($dop[2] == 5){$d .= " AND `us`.`sparka` = 0 AND `us`.`sparkaNumber` = 3 ";}else{$d .= "";}
    if($dop[3] >= 1){$d .= " AND `us`.`tren` = ".$dop[3]." ";}else{$d .= "";}
    if($dop[4] == 1){$d .= " AND `us`.`item_id` = 0 ";}elseif($dop[4] == 2){$d .= " AND `us`.`item_id` != 0 ";}else{$d .= "";}
    if($dop[5] >= 1){$d .= " AND `us`.`character` = ".$dop[5]." ";}else{$d .= "";}
    
    if($dop[6] >= 1){$d .= " AND `us`.`team_id` = ".$dop[6]." ";}else{$d .= "";}
    
    if($dop[7] >= 1){$d .= " AND `us`.`lvl` = ".$dop[7]." ";}else{$d .= "";}
    $d .= " ORDER BY `us`.`id` DESC ";
    $s = "LIMIT ".$page.",20 ";
    if(is_numeric($search)){
        $PokemonQuery = $mysqli->query("SELECT
                                            `us`.*,
                                            `bp`.`name_rus`
                                        FROM `user_pokemons` AS `us`
                                        INNER JOIN `base_pokemons` AS `bp`
                                        ON `bp`.`id` = `us`.`basenum`
                                        WHERE
                                            `us`.`basenum` LIKE '%".$search."%'
                                        AND
                                            `us`.`user_id` = '".$_SESSION['id']."'
                                        AND
                                            `us`.`active` = 0  ".$d.$s);
                                            $PokemonQuery_total = $mysqli->query("SELECT
                                            `us`.*,
                                            `bp`.`name_rus`
                                        FROM `user_pokemons` AS `us`
                                        INNER JOIN `base_pokemons` AS `bp`
                                        ON `bp`.`id` = `us`.`basenum`
                                        WHERE
                                            `us`.`basenum` LIKE '%".$search."%'
                                        AND
                                            `us`.`user_id` = '".$_SESSION['id']."'
                                        AND
                                            `us`.`active` = 0  ".$d);

    }elseif(is_string($search)){
      $PokemonQuery = $mysqli->query("SELECT
                                            `us`. *,
                                            `bp`.`name_rus`
                                        FROM `user_pokemons` AS `us`
                                        INNER JOIN `base_pokemons` AS `bp`
                                        ON `bp`.`id` = `us`.`basenum`
                                        WHERE
                                            (`us`.`name_new` LIKE '%".$search."%' OR `bp`.`name_rus` LIKE '%".$search."%') 
                                        AND
                                            `us`.`user_id` = '".$_SESSION['id']."'
                                        AND
                                            `us`.`active` = 0 ".$d.$s);
                                            $PokemonQuery_total = $mysqli->query("SELECT
                                            `us`. *,
                                            `bp`.`name_rus`
                                        FROM `user_pokemons` AS `us`
                                        INNER JOIN `base_pokemons` AS `bp`
                                        ON `bp`.`id` = `us`.`basenum`
                                        WHERE
                                            (`us`.`name_new` LIKE '%".$search."%'  OR `bp`.`name_rus` LIKE '%".$search."%') 
                                        AND
                                            `us`.`user_id` = '".$_SESSION['id']."'
                                        AND
                                            `us`.`active` = 0 ".$d);
    }elseif(empty($_POST['search'])){
        $PokemonQuery = $mysqli->query("SELECT
                                            `us`. *,
                                            `bp`.`name_rus`
                                        FROM `user_pokemons` AS `us`
                                        INNER JOIN `base_pokemons` AS `bp`
                                        ON `bp`.`id` = `us`.`basenum`
                                        WHERE
                                            `us`.`user_id` = '".$_SESSION['id']."'
                                        AND
                                            `us`.`active` = 0 ".$d.$s);
                                            $PokemonQuery_total = $mysqli->query("SELECT
                                            `us`. *,
                                            `bp`.`name_rus`
                                        FROM `user_pokemons` AS `us`
                                        INNER JOIN `base_pokemons` AS `bp`
                                        ON `bp`.`id` = `us`.`basenum`
                                        WHERE
                                            `us`.`user_id` = '".$_SESSION['id']."'
                                        AND
                                            `us`.`active` = 0 ".$d);
    }else{
        $response['error'] = 1;
    }
    $total = $PokemonQuery_total->num_rows;
    if($PokemonQuery->num_rows < 1){
        $response['error'] = 1;
    }else{
        $pokList = [];
        while ($pokemons = $PokemonQuery->fetch_assoc()){
      if($pokemons['tren'] == 6){
        $tr = '<i class="trening fas fa-crown tr6"></i>';
      }elseif($pokemons['tren'] == 5){
        $tr = '<i class="trening fas fa-angle-double-up tr5"></i>';
      }elseif($pokemons['tren'] == 4){
        $tr = '<i class="trening fas fa-angle-double-up tr4"></i>';
      }elseif($pokemons['tren'] == 3){
        $tr = '<i class="trening fas fa-angle-double-up tr3"></i>';
      }elseif($pokemons['tren'] == 2){
        $tr = '<i class="trening fas fa-angle-double-up tr2"></i>';
      }elseif($pokemons['tren'] == 1){
        $tr = '<i class="trening fas fa-angle-double-up tr1"></i>';
      }else{
        $tr = '';
      }

      $paired = $pokemons['sparka'] == 1 ? 'spar' : '';
      if($pokemons['gender'] == 'Девочка') {
        $gender = 'venus';
      }elseif($pokemons['gender'] == 'Мальчик') {
        $gender = 'mars';
      }else{
        $gender = 'genderless';
      }
      $sex = '<i class="fas fa-'.$gender.' '.$paired.' "></i>';
            $pokList[$pokemons['id']] = [
                                'id'=>$pokemons['id'],
                                'basenum'=>numbPok($pokemons['basenum']),
                                'type'=>$pokemons['type'],
                                'name'=>($pokemons['name_new']?$pokemons['name_new']:$pokemons['name_rus']),
                                'lvl'=>$pokemons['lvl'],
                                'gender'=>$pokemons['gender'],
                                'sparka'=>$pokemons['sparka'],
                                'sparkaNumber'=>$pokemons['sparkaNumber'],
                'trade'     =>$pokemons['trade'],
                                'item_id' =>$pokemons['item_id'],
                'tren'    =>$tr,
                                'vitamines' =>$pokemons['vitamines'],
                                'sex'  =>$sex,
                                'gen'=>$pokemons['gen'],
                                'character'=>haracter_pokes($pokemons['character']),

                            ];
        }
    }
    $pok = $pokList;
    $response['pokList'] = array_reverse($pok);
    
    
    
    $response['page'] = createLinks($total);
    die(json_encode($response));
}
// if(isset($_POST['data']) && !empty($_POST['data'])){
    
//     die(json_encode($response));
// }


    $nursery = $mysqli->query('SELECT `id`,`basenum`,`name_new`,`gender`,`lvl`,`sparka`,`tren`,`type`,`trade`,`item_id`,`sparkaNumber`,`gen`,`vitamines` FROM `user_pokemons` WHERE `user_id` = '.$_SESSION['id'].' AND `active`= 0 ORDER BY `id` DESC LIMIT 20');
    $nursery_total = $mysqli->query('SELECT `id`,`basenum`,`name_new`,`gender`,`lvl`,`sparka`,`tren`,`type`,`trade`,`item_id`,`sparkaNumber`,`gen`,`vitamines` FROM `user_pokemons` WHERE `user_id` = '.$_SESSION['id'].' AND `active`= 0 ORDER BY `id` DESC');
                                    
    $pokemons = '';
    $total = $nursery_total->num_rows;
    if($nursery->num_rows > 0){

        // Современная адаптивная обертка, не ломает связи (добавочные классы для стилей)
        $pokemons .= '<div class="nursery-wrap"><div class="nursery-grid">';

        while($n = $nursery->fetch_assoc()){
    if($n['type'] == 'shine'){ $o = "shine";}else{$o = "normal";}
    $paired = $n['sparka'] == 1 ? 'spar' : '';
                              if($n['gender'] == 'Девочка') {
                                $gender = 'venus';
                              }elseif($n['gender'] == 'Мальчик') {
                                $gender = 'mars';
                              }else{
                                $gender = 'genderless';
                              }
                                    $sex = '<i class="fas fa-'.$gender.' '.$paired.' "></i>';
                                    if($n['tren'] == 6){
                                        $tr = '<i class="trening fas fa-crown tr6"></i>';
                                    }elseif($n['tren'] == 5){
                                        $tr = '<i class="trening fas fa-angle-double-up tr5"></i>';
                                    }elseif($n['tren'] == 4){
                                        $tr = '<i class="trening fas fa-angle-double-up tr4"></i>';
                                    }elseif($n['tren'] == 3){
                                        $tr = '<i class="trening fas fa-angle-double-up tr3"></i>';
                                    }elseif($n['tren'] == 2){
                                        $tr = '<i class="trening fas fa-angle-double-up tr2"></i>';
                                    }elseif($n['tren'] == 1){
                                        $tr = '<i class="trening fas fa-angle-double-up tr1"></i>';
                                    }else{
                                        $tr = '';
                                    }
        if($n['trade'] == "false"){
          $t = ' <i class="fas fa-lock"></i>';
        }else{ $t = '';}
        if($n['item_id'] >= 1){
          $i = ' <i class="fas fa-cube"></i>';
        }else{ $i = '';}
        $gen = explode(',',$n['gen']);
            $getBasenum = $n['basenum'];
            $basenum = numbPok($getBasenum);

            // Обновленный современный вывод: добавили карточку и служебные классы, но полностью сохранили старые классы и привязки
            $pokemons.='
            <div class="divFarmPoke id'.$n['id'].'">
              <article class="poke-card '.($o=='shine'?'is-shine':'').'">
                <div class="btnBackPokemon poke-card__action" onclick="nursery(&quot;get&quot;,'.$n['id'].');" title="Забрать из питомника">
                  <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="pokemonBoxTiny size0 clickable poke-card__media" onclick="openInfoNursery('.$n['id'].');">
                  <img class="image" src="/img/pokemons/pokedex/'.$basenum.'.png" alt="#'.$basenum.'">
                  <div class="nameNur '.$o.'-color">#'.$basenum.' '.$n['name_new'].'</div>
                  <div class="shorts">'.$sex.' '.$n['lvl'].'</div>
                  <div class="extra poke-card__meta">
                    <span class="ivcode">h'.$gen[0].'a'.$gen[1].'d'.$gen[2].'s'.$gen[3].'sa'.$gen[4].'sd'.$gen[5].'.'.$n['vitamines'].' ('.$n['sparkaNumber'].') '.$tr.' '.$t.' '.$i.'</span>
                  </div>
                </div>
              </article>
            </div>
            <div class="hr id'.$n['id'].'"></div>';
        }

        $pokemons .= '</div></div>'; // закрываем .nursery-grid и .nursery-wrap

        // Кнопка «Еще» — оставляем без изменений
        $pokemons.= '<button class="DexsdBtn" onclick="pokedexload()" data-title="1">Еще</button>';
    }else{
        $pokemons.= "<center><p class='GrayError'>Ваш питомник пуст</p></center>";
    }


$pok_team = $mysqli->query('SELECT `id`,`user`,`name` FROM `user_pok_team` WHERE `user` = '.$_SESSION['id'].' ORDER BY `id` DESC');
                                    
    $team = '';
    if($pok_team->num_rows > 0){
        while($t = $pok_team->fetch_assoc()){
            $team .= '<option value="'.$t['id'].'">'.$t['name'].'</option>';
}}


    $response['page'] = createLinks($total);
    $response['team'] = $team;
    $response['html'] = $pokemons;
    $response['title'] = 'Питомник';
?>
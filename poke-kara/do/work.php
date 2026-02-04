<?
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
$category = $_POST['category'];
if(lvluser() >= 10 AND lvluser() < 30){ $lpk = 4; }elseif(lvluser() >= 30 AND lvluser() < 50){ $lpk = 5; }else{ $lpk = 6; }
$workspoklists = $mysqli->query('SELECT * FROM `base_work_pok` WHERE `cat` = "'.$category.'" AND `user` = "'.$_SESSION['id'].'" ');
if(!$workspoklists->num_rows){ $l = 0; }else{ $l = $workspoklists->num_rows; }

$works = $mysqli->query('SELECT * FROM `base_work` WHERE id = '.$category)->fetch_assoc();
  $a .= '
    <div class="WorkName">
      <div class="Name" onclick="openModal(\'work\')" >'.$works['name'].'</div>
      <div class="place">
        <i class="fas fa-user-tie"></i> '.$l.'/'.$lpk.'
      </div>
      <div class="salary">
        <i class="fas fa-coins"></i> '.number_format($works['salary'],0,'.','.').'
      </div>
    </div>
    <div class="WorkInfo">
      '.$works['info'].'
    </div>
    <div class="WorkConditions">
      <div class="TitleBox">Условия</div>
      <ol>
        <li>Минимальный уровень покемона должен быть 50.</li>
        <li>Вы не можете отправлять покемонов на работу до истечения срока отдыха.</li>
        <li>Вы не можете отправлять покемонов одного вида на одну работу.</li>
        <li>Максимум на 1 вид работы можно отправить '.$lpk.' работника.</li>
        <li>Подходящие покемоны: '.$works['pokemon_work'].'</li>
      </ol>
    </div>
    <div class="WorkSending">
      <div class="TitleBox">Отправка</div>
      <select id="pokwork">';
$workspoklist = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `active` = "1" AND `lvl` >= "50" AND `user_id` = "'.$_SESSION['id'].'" ');
while($workpl = $workspoklist->fetch_assoc()){
    $a .= '<option value="'.$workpl['id'].'">#'.NumbPok($workpl['basenum']).' '.$workpl['name_new'].'</option>';
}
      $a .= '</select>
      <div class="SendingBtn" onclick="sendPokWork('.$category.',$(this).prev().val())">Отправить</div>
    </div>
    <div class="WorkerPokemon">
      <div class="TitleBox">Ваши работники</div>';

while($workpl = $workspoklists->fetch_assoc()){
    $pbds = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `id` = '.$workpl['pok'])->fetch_assoc();
    $a .= '<div class="WorkBox" onclick="returnwork('.$category.','.$workpl['pok'].')">
        <img src="/img/pokemons/animation/'.NumbPok($pbds['basenum']).'.png">
        #'.NumbPok($pbds['basenum']).' '.$pbds['name_new'].'
        <div class="timer">
          <i class="far fa-clock"></i> '.downcountermin($workpl['time']).'
        </div>
      </div>';
}
if(!empty($_POST['pok'])){
    $pok = $_POST['pok'];
    $er = 0;
    $pbd_active = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `active` = "1" AND `user_id` = "'.$_SESSION['id'].'" ');
    $pbd = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `active` = "1" AND `lvl` >= "50" AND `id` = "'.$pok.'" AND `user_id` = "'.$_SESSION['id'].'" ')->fetch_assoc();
    $wbd = $mysqli->query('SELECT * FROM `base_work_pok` WHERE `cat` = "'.$category.'" AND `user` = "'.$_SESSION['id'].'" ');
    while($wv = $wbd->fetch_assoc()){ if($pbd['basenum'] == $wv['basenum']){ $er = 1; }}
    if($category == 1){$ms = array(40,176,184,242,358,531,576,620,683,764);
    }elseif($category == 2){$ms = array(59,405,508,628,660,673,735,773);
    }elseif($category == 3){$ms = array(6,18,22,149,227,277,334,398,521,663);
    }elseif($category == 4){$ms = array(106,107,237,297,448,538,539,675,701,768);
    }elseif($category == 5){$ms = array(57,62,68,105,115,217,534,614,689,760);
    }elseif($category == 6){$ms = array(154,189,315,416,542,549,709,711,754,763);
    }elseif($category == 7){$ms = array(122,124,301,354,424,512,514,516,730,741);
    }elseif($category == 8){$ms = array(131,134,195,350,395,565,581,593,689,693);
    }elseif($category == 9){$ms = array(26,101,125,135,310,405,462,596,604,695);
    }elseif($category == 10){$ms = array(67,115,217,277,505,508,510,523,735,773);
    }elseif($category == 11){$ms = array(28,31,34,76,112,115,473,526,689);
    }elseif($category == 12){$ms = array(65,124,303,350,424,463,542,741);
    }elseif($category == 13){$ms = array(40,131,282,295,301,358,730,743,763);
    }elseif($category == 14){$ms = array(57,68,297,534,620,625,675,750);}
    if(in_array($pbd['basenum'],$ms) AND $pbd){
        if($er == 0){
          if($pbd_active->num_rows > 1){
            if($wbd->num_rows < $lpk){
                if($pbd['kd_work'] < time()){
                    $response['error'] = "success";
                    $response['notify'] = "Покемон успешно отправлен на работу!";
                    $response['minus'] = '<img src="/img/pokemons/animation/'.NumbPok($pbd['basenum']).'.png"> #'.NumbPok($pbd['basenum']).' '.$pbd['name_new'].'<br>';
                    $timer = time()+3600*24*3;
                    if(check_mission_ivent(42)){ add_mission_ivent(42);}
                    $mysqli->query("INSERT INTO `base_work_pok` (`pok`,`user`,`cat`,`time`,`basenum`) VALUES ('".$pok."','".$_SESSION['id']."','".$category."','".$timer."','".$pbd['basenum']."') ");
                    $mysqli->query("UPDATE `user_pokemons` SET `user_id` = '2', `active` = '0' WHERE `id` = '".$pok."' ");
                    $a .= '<div class="WorkBox" onclick="returnwork('.$category.','.$pok.')"><img src="/img/pokemons/animation/'.NumbPok($pbd['basenum']).'.png">#'.NumbPok($pbd['basenum']).' '.$pbd['name_new'].'<div class="timer"><i class="far fa-clock"></i> '.downcountermin($timer).'</div></div>';
                }else{
                    $response['error'] = "error";
                    $response['notify'] = "Этот покемон еще не отдохнул от предыдущей работы! Отдыхать он будет еще ".downcountermin($pbd['kd_work']);
                }
            }else{
                $response['error'] = "error";
                $response['notify'] = "Вы не можете отправить на работу больше ".$lpk." покемонов!";
            }
          }else{
              $response['error'] = "error";
              $response['notify'] = "В вашей команде должен остаться хотя бы 1 покемон!";
          }
        }else{
            $response['error'] = "error";
            $response['notify'] = "Нельзя отправлять покемонов одного вида на работу!";
        }
    }else{
        $response['error'] = "error";
        $response['notify'] = "Покемон не подходит для данной работы!";
    }
}
$a .= '</div>';
if(!empty($_POST['retpok'])){
    $wbsc = $mysqli->query('SELECT * FROM `base_work_pok` WHERE `pok` = "'.$_POST['retpok'].'"')->fetch_assoc();
    $wvm = $mysqli->query('SELECT * FROM `base_work` WHERE `id` = "'.$wbsc['cat'].'"')->fetch_assoc();
    $pbvc = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `id` = "'.$wbsc['pok'].'"  ')->fetch_assoc();
    if(!empty($wbsc)){
        if($wbsc['time'] < time()){
          $bafprem = $mysqli->query('SELECT * FROM bafs WHERE type = 3 AND user = '.$_SESSION['id'])->fetch_assoc();
                    if($bafprem) {
                      if($bafprem['time'] > time()) {
                        $sk = 1.3;
                      }else{
                        $sk = 1;
                      }
                    }else{
                        $sk = 1;
                    }
            $mysqli->query("DELETE FROM `base_work_pok` WHERE `pok` = '".$_POST['retpok']."' ");
            $t = time()+3600*24*4;
            $mysqli->query("UPDATE `user_pokemons` SET `user_id` = '".$_SESSION['id']."', `active` = '0' , `kd_work` = '".$t."' WHERE `id` = '".$_POST['retpok']."' ");
            $money = round($wvm['salary']*$sk);
            itemAdd(1,$money);
            $response['error'] = "success";
            $response['notify'] = "Покемон вернулся с работы!";
            $response['plus'] = '<img src="/img/pokemons/animation/'.NumbPok($pbvc['basenum']).'.png"> #'.NumbPok($pbvc['basenum']).' '.$pbvc['name_new'].'<br><br><img src="/img/world/items/little/1.png" class="item"> Монета <b>x'.number_format($money,0,'.','.').'</b>';
        }else{
            $response['error'] = "error";
            $response['notify'] = "Покемон еще не закончил работу!";
        }
    }else{
        $response['error'] = "error";
        $response['notify'] = "Ошибка!";
    }
}
$response['html'] = $a;
echo json_encode($response);
?>

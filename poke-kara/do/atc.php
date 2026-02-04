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
$id = escapeMe($_POST['id']);
$cat = escapeMe($_POST['cat']);
$cate = escapeMe($_POST['cate']);

if($id and $cat and $cate){
  $l1 = $id*20;
  if($cat == "all" and $cate == "all"){
    $atc = $mysqli->query('SELECT * FROM `base_atk` ORDER BY `name_rus` ASC LIMIT '.$l1.',20');
  }elseif($cat != "all" and $cate == "all"){
    $atc = $mysqli->query('SELECT * FROM `base_atk`  WHERE `type` = "'.$cat.'"  ORDER BY `name_rus` ASC LIMIT '.$l1.',20');
  }elseif($cat == "all" and $cate != "all"){
    $atc = $mysqli->query('SELECT * FROM `base_atk`  WHERE `category` = "'.$cate.'" ORDER BY `name_rus` ASC LIMIT '.$l1.',20');
  }else{
    $atc = $mysqli->query('SELECT * FROM `base_atk` WHERE `type` = "'.$cat.'" and  `category` = "'.$cate.'"  ORDER BY `name_rus` ASC LIMIT '.$l1.',20');
  }
  while($atcs = $atc->fetch_assoc()){
    if($atcs['power'] == 0){ $power = "-"; }else{ $power = $atcs['power']; }
    if($atcs['accuracy'] == 0){ $acc = "-"; }else{ $acc = $atcs['accuracy']; }
    if($atcs['type'] == "normal"){$type = "Нормальный";}
    elseif($atcs['type'] == "fighting"){$type = "Боевой";}
    elseif($atcs['type'] == "fly"){$type = "Летающий";}
    elseif($atcs['type'] == "poison"){$type = "Ядовитый";}
    elseif($atcs['type'] == "ground"){$type = "Земляной";}
    elseif($atcs['type'] == "rock"){$type = "Каменный";}
    elseif($atcs['type'] == "bug"){$type = "Насекомый";}
    elseif($atcs['type'] == "ghost"){$type = "Призрачный";}
    elseif($atcs['type'] == "fire"){$type = "Огненный";}
    elseif($atcs['type'] == "water"){$type = "Водный";}
    elseif($atcs['type'] == "grass"){$type = "Травяной";}
    elseif($atcs['type'] == "electric"){$type = "Электрический";}
    elseif($atcs['type'] == "psychic"){$type = "Психический";}
    elseif($atcs['type'] == "ice"){$type = "Ледяной";}
    elseif($atcs['type'] == "dragon"){$type = "Драконий";}
    elseif($atcs['type'] == "dark"){$type = "Темный";}
    elseif($atcs['type'] == "steel"){$type = "Стальной";}
    elseif($atcs['type'] == "fairy"){$type = "Волшебный";}

    if($atcs['category'] == "specific"){ $cat = "Специф";}
    elseif($atcs['category'] == "physical"){ $cat = "Физ";}
    elseif($atcs['category'] == "special"){ $cat = "Спец";}
    elseif($atcs['category'] == "status"){ $cat = "Стат";}

    $tpl .= '	<div class="AtcBox" onclick="viewDescriptionAttak(this,'.$atcs['id'].');">
                <div class="InfoTop">
                  <div class="Name">'.$atcs['name_rus'].'</div>
                  <div class="Power">'.$power.'<div>Сила</div></div>
                  <div class="Accuracy">'.$acc.'<div>Точн.</div></div>
                  <div class="Pp">'.$atcs['pp'].'<div>PP</div></div>
                </div>
                <div class="InfoBottom">
                  <div class="Type type'.$atcs['type'].' "><div class="TitleType">Тип:</div><div class="TypeAtc">'.$type.'</div></div>
                  <div class="Category '.$atcs['category'].'">'.$cat.'</div>
                </div>
              </div>';
  }
$response["html"] = $tpl;
}





if(!$id and $cat and $cate){
  if($cat == "all" and $cate == "all"){
    $atc = $mysqli->query('SELECT * FROM `base_atk` ORDER BY `name_rus` ASC LIMIT 20');
  }elseif($cat != "all" and $cate == "all"){
    $atc = $mysqli->query('SELECT * FROM `base_atk`  WHERE `type` = "'.$cat.'"  ORDER BY `name_rus` ASC LIMIT 20');
  }elseif($cat == "all" and $cate != "all"){
    $atc = $mysqli->query('SELECT * FROM `base_atk`  WHERE `category` = "'.$cate.'" ORDER BY `name_rus` ASC LIMIT 20');
  }else{
    $atc = $mysqli->query('SELECT * FROM `base_atk` WHERE `type` = "'.$cat.'" and  `category` = "'.$cate.'"  ORDER BY `name_rus` ASC LIMIT 20');
  }
while($atcs = $atc->fetch_assoc()){
if($atcs['power'] == 0){ $power = "-"; }else{ $power = $atcs['power']; }
if($atcs['accuracy'] == 0){ $acc = "-"; }else{ $acc = $atcs['accuracy']; }
if($atcs['type'] == "normal"){$type = "Нормальный";}
elseif($atcs['type'] == "fighting"){$type = "Боевой";}
elseif($atcs['type'] == "fly"){$type = "Летающий";}
elseif($atcs['type'] == "poison"){$type = "Ядовитый";}
elseif($atcs['type'] == "ground"){$type = "Земляной";}
elseif($atcs['type'] == "rock"){$type = "Каменный";}
elseif($atcs['type'] == "bug"){$type = "Насекомый";}
elseif($atcs['type'] == "ghost"){$type = "Призрачный";}
elseif($atcs['type'] == "fire"){$type = "Огненный";}
elseif($atcs['type'] == "water"){$type = "Водный";}
elseif($atcs['type'] == "grass"){$type = "Травяной";}
elseif($atcs['type'] == "electric"){$type = "Электрический";}
elseif($atcs['type'] == "psychic"){$type = "Психический";}
elseif($atcs['type'] == "ice"){$type = "Ледяной";}
elseif($atcs['type'] == "dragon"){$type = "Драконий";}
elseif($atcs['type'] == "dark"){$type = "Темный";}
elseif($atcs['type'] == "steel"){$type = "Стальной";}
elseif($atcs['type'] == "fairy"){$type = "Волшебный";}

if($atcs['category'] == "specific"){ $cat = "Специф";}
elseif($atcs['category'] == "physical"){ $cat = "Физ";}
elseif($atcs['category'] == "special"){ $cat = "Спец";}
elseif($atcs['category'] == "status"){ $cat = "Стат";}
$tpl .= '	<div class="AtcBox" onclick="viewDescriptionAttak(this,'.$atcs['id'].');">
          <div class="InfoTop">
            <div class="Name">'.$atcs['name_rus'].'</div>
            <div class="Power">'.$power.'<div>Сила</div></div>
            <div class="Accuracy">'.$acc.'<div>Точн.</div></div>
            <div class="Pp">'.$atcs['pp'].'<div>PP</div></div>
          </div>
          <div class="InfoBottom">
            <div class="Type type'.$atcs['type'].' "><div class="TitleType">Тип:</div><div class="TypeAtc">'.$type.'</div></div>
            <div class="Category '.$atcs['category'].'">'.$cat.'</div>
          </div>
        </div>';
}
$response['html'] = $tpl;
}




echo json_encode($response);
?>

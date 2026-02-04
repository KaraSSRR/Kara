<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
require_once($patch_global);

/* ---------- safe helpers (без inline onclick) ---------- */
function renderCloseButton() {
    return '<button class="ViewBattle__Close" type="button" title="Закрыть" aria-label="Закрыть">&times;</button>';
}
function renderOverlay() {
    return '<div class="ViewBattle__Overlay" aria-hidden="true"></div>';
}

/* ---------- input ---------- */
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['html' => '<div class="ViewBattle__Wait">Некорректный ID боя</div>'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- data ---------- */
$LogBattle = Work::$sql->query('SELECT * FROM `battle_log` WHERE `battle`='.$id.' ORDER BY id DESC');
$Battle    = Work::$sql->query('SELECT * FROM `battle` WHERE `id`='.$id.' LIMIT 1')->fetch_assoc();
if (!$Battle) {
    echo json_encode(['html' => '<div class="ViewBattle__Wait">Бой не найден</div>'], JSON_UNESCAPED_UNICODE);
    exit;
}

$Battle1 = Info::_unParseData($Battle['info_1']);
$Battle2 = Info::_unParseData($Battle['info_2']);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fnum($n){ $n=(int)$n; if($n<10) return '00'.$n; if($n<100) return '0'.$n; return (string)$n; }

/* ---------- poke lists / targets ---------- */
$list1 = isset($Battle1['pokeLIst']) && is_array($Battle1['pokeLIst']) ? $Battle1['pokeLIst'] : [];
$list2 = isset($Battle2['pokeLIst']) && is_array($Battle2['pokeLIst']) ? $Battle2['pokeLIst'] : [];

$target1 = (!empty($list1) && isset($Battle1['target']) && isset($list1['p'.$Battle1['target']])) ? $list1['p'.$Battle1['target']] : [];
$target2 = (!empty($list2) && isset($Battle2['target']) && isset($list2['p'.$Battle2['target']])) ? $list2['p'.$Battle2['target']] : [];

/* ---------- formatting ---------- */
$basenum1 = isset($target1['basenum']) ? fnum($target1['basenum']) : '000';
$basenum2 = isset($target2['basenum']) ? fnum($target2['basenum']) : '000';
$form1    = (!empty($target1['form'])) ? '_'.(int)$target1['form'] : '';
$form2    = (!empty($target2['form'])) ? '_'.(int)$target2['form'] : '';

$sex1 = (isset($target1['gender']) && $target1['gender']=='Мальчик') ? 'mars' : ((isset($target1['gender']) && $target1['gender']=='Девочка') ? 'venus' : 'genderless');
$sex2 = (isset($target2['gender']) && $target2['gender']=='Мальчик') ? 'mars' : ((isset($target2['gender']) && $target2['gender']=='Девочка') ? 'venus' : 'genderless');

$nameS1 = (isset($target1['type']) && $target1['type']!='normal') ? h($target1['type']) : '';
$nameS2 = (isset($target2['type']) && $target2['type']!='normal') ? h($target2['type']) : '';

/* ---------- balls ---------- */
function ballsHtml($list){
  $out='';
  if ($list) foreach ($list as $p){
    if (!$p) continue;
    $dead = (isset($p['hp']) && (int)$p['hp']<=0) ? ' noHp' : '';
    $ball = (int)($p['ball'] ?? 1);
    $out .= '<div class="ViewBattle__Ball'.$dead.'" style="background-image:url(/img/world/items/little/'.$ball.'.png)"></div>';
  }
  return $out;
}
$BallsPoke1 = ballsHtml($list1);
$BallsPoke2 = ballsHtml($list2);

/* ---------- status / mods ---------- */
function statusList($arr){
  $o=''; if (!is_array($arr)) return $o;
  foreach ($arr as $s) if (!empty($s['type'])) $o.='<div class="ViewBattle__Statusimg '.h($s['type']).'"></div>';
  return $o;
}
function modsList($arr){
  $o=''; if (!is_array($arr)) return $o;
  foreach ($arr as $k=>$v){
    if (!empty($v['plus']))  $o.='<div class="ViewBattle__Status"><div class="ViewBattle__Statusimg '.h($k).'Plus"><span class="greennumber">'.(int)$v['plus'].'</span></div></div>';
    elseif (!empty($v['minus'])) $o.='<div class="ViewBattle__Status"><div class="ViewBattle__Statusimg '.h($k).'Minus"><span class="rednumber">'.(int)$v['minus'].'</span></div></div>';
  }
  return $o;
}
$lists1   = statusList($target1['status_list'] ?? []);
$lists2   = statusList($target2['status_list'] ?? []);
$listmod1 = modsList($target1['modified'] ?? []);
$listmod2 = modsList($target2['modified'] ?? []);

/* ---------- log (не экранируем внутренний HTML) ---------- */
$LogBattleSel = '';
if ($LogBattle) {
  while ($Log = $LogBattle->fetch_row()) {
    $L  = json_decode($Log[3], true);
    $LE = json_decode($Log[4], true);

    $u1 = Work::$sql->query('SELECT login,user_group FROM users WHERE id='.(int)($L[0]['user'] ?? 0).' LIMIT 1')->fetch_row();
    $u2 = Work::$sql->query('SELECT login,user_group FROM users WHERE id='.(int)($L[1]['user'] ?? 0).' LIMIT 1')->fetch_row();

    $nick1 = '<span class="ViewBattle__User u-'.(int)($u1[1] ?? 6).'">'.h($u1[0] ?? '—').'</span>';
    $nick2 = '<span class="ViewBattle__User u-'.(int)($u2[1] ?? 6).'">'.h($u2[0] ?? '—').'</span>';

    $t1=''; if (!empty($L[0]['log'])){ $t1.='<div class="ViewBattle__LogUser">'.$nick1.'</div>'; foreach($L[0]['log'] as $a){ $t1.="<span class='ViewBattle__LogText'>{$a}</span>"; } }
    $t2=''; if (!empty($L[1]['log'])){ $t2.='<div class="ViewBattle__LogUser">'.$nick2.'</div>'; foreach($L[1]['log'] as $b){ $t2.="<span class='ViewBattle__LogText'>{$b}</span>"; } }
    $te=''; if (is_array($LE)) foreach($LE as $c){ $te.="<span class='ViewBattle__LogText'>{$c}</span>"; }

    $LogBattleSel .= '<div class="ViewBattle__Step"><div class="ViewBattle__Round">Раунд '.(int)$Log[2].'</div><div class="ViewBattle__Process">'.$t1.'</div><div class="ViewBattle__Process">'.$t2.$te.'</div></div>';
  }
}

/* ---------- безопасный $btle ---------- */
$btleHtml = isset($btle) ? $btle : '';

/* ---------- html ---------- */
$html = renderOverlay() . '
<div class="ViewBattle" role="dialog" aria-label="Просмотр боя">
  ' . renderCloseButton() . '
  <div class="ViewBattle__Sides">
    <div class="ViewBattle__Side ViewBattle__Side--left">
      <div class="ViewBattle__PokemonBox">
        <div class="ViewBattle__Modif" style="background-image:url(/img/tren/'.h($target1['tren'] ?? 0).'.png)"></div>
        <div class="ViewBattle__Image"><img class="ViewBattle__ImgPok" src="/img/pokemons/sprite/'.h($target1['type'] ?? 'normal').'/'.$basenum1.$form1.'.gif" alt=""></div>
        <div class="ViewBattle__PokemonName">
          <span class="ViewBattle__PokemonNum">#'.$basenum1.'</span>
          <span class="ViewBattle__PokemonText">'.h($target1['name_new'] ?? '').'</span>
          <span class="ViewBattle__PokemonSex"><i class="fas fa-'.$sex1.'"></i></span>
        </div>
        <div class="ViewBattle__PokemonLvl">'.(isset($target1['lvl'])?(int)$target1['lvl']:'').'</div>
        <div class="ViewBattle__PokemonType '.h($target1['type'] ?? 'normal').'-color">'.$nameS1.'</div>
        <div class="ViewBattle__Item" onclick="issetAll('.(int)($target1['item_id'] ?? 0).',\'item\')" style="background-image:url(/img/world/items/little/'.(int)($target1['item_id'] ?? 0).'.png)"></div>
        <div class="ViewBattle__Bars">
          <div class="ViewBattle__Bar" data-title="HP: '.(int)($target1['hp'] ?? 0).' / '.(int)($target1['stats'][0] ?? 0).'">
            <div class="ViewBattle__HpBar" style="width: '.(((int)($target1['stats'][0] ?? 0)>0)?((int)($target1['hp'] ?? 0)/(int)$target1['stats'][0]*100):0).'%; max-width:100%"></div>
          </div>
        </div>
        <div class="ViewBattle__StatusList">'.$listmod1.$lists1.'</div>
      </div>
      <div class="ViewBattle__Team">'.$BallsPoke1.'</div>
      <div class="ViewBattle__TrainerName">'.h($Battle1['userInfo']['login'] ?? '').'</div>
    </div>

    <div class="ViewBattle__Middle">
      <div class="ViewBattle__Info">
        <div class="ViewBattle__RoundBlock">Раунд <span class="ViewBattle__RoundNum">'.(int)$Battle['round'].'</span></div>
        <div class="ViewBattle__Weather"><img src="/img/weather/'.h($Battle['weather']).'.png" alt=""></div>
        '.$btleHtml.'
      </div>
      <div class="ViewBattle__LogWrapper"><div class="ViewBattle__Log">'.$LogBattleSel.'</div></div>
    </div>

    <div class="ViewBattle__Side ViewBattle__Side--right">
      <div class="ViewBattle__PokemonBox">
        <div class="ViewBattle__Modif ViewBattle__Modif--enemy" style="background-image:url(/img/tren/'.h($target2['tren'] ?? 0).'.png)"></div>
        <div class="ViewBattle__Image"><img class="ViewBattle__ImgPok" src="/img/pokemons/sprite/'.h($target2['type'] ?? 'normal').'/'.$basenum2.$form2.'.gif" alt=""></div>
        <div class="ViewBattle__PokemonName">
          <span class="ViewBattle__PokemonNum">#'.$basenum2.'</span>
          <span class="ViewBattle__PokemonText">'.h($target2['name_new'] ?? '').'</span>
          <span class="ViewBattle__PokemonSex"><i class="fas fa-'.$sex2.'"></i></span>
        </div>
        <div class="ViewBattle__PokemonLvl">'.(isset($target2['lvl'])?(int)$target2['lvl']:'').'</div>
        <div class="ViewBattle__PokemonType '.h($target2['type'] ?? 'normal').'-color">'.$nameS2.'</div>
        <div class="ViewBattle__Item" onclick="issetAll('.(int)($target2['item_id'] ?? 0).',\'item\')" style="background-image:url(/img/world/items/little/'.(int)($target2['item_id'] ?? 0).'.png)"></div>
        <div class="ViewBattle__Bars">
          <div class="ViewBattle__Bar" data-title="HP: '.(int)($target2['hp'] ?? 0).' / '.(int)($target2['stats'][0] ?? 0).'">
            <div class="ViewBattle__HpBar" style="width: '.(((int)($target2['stats'][0] ?? 0)>0)?((int)($target2['hp'] ?? 0)/(int)$target2['stats'][0]*100):0).'%; max-width:100%"></div>
          </div>
        </div>
        <div class="ViewBattle__StatusList">'.$listmod2.$lists2.'</div>
      </div>
      <div class="ViewBattle__Team">'.$BallsPoke2.'</div>
      <div class="ViewBattle__TrainerName">'.h($Battle2['userInfo']['login'] ?? '').'</div>
    </div>
  </div>
</div>
<script>
(function(){
  // уже открыта — не плодим дубликаты
  document.querySelectorAll(".ViewBattle__Overlay, .ViewBattle").forEach(el => el.remove());

  // короткая «армировка» закрытия во избежание мгновенного клика
  var armed = false;
  setTimeout(function(){ armed = true; }, 160);

  // единый закрыватель + возврат скролла body
  var prevOverflow = document.documentElement.style.overflow || "";
  document.documentElement.style.overflow = "hidden";
  function closeViewBattle(){
    document.querySelectorAll(".ViewBattle__Overlay, .ViewBattle").forEach(el => el.remove());
    document.documentElement.style.overflow = prevOverflow;
  }
  window.closeBattleModal = closeViewBattle;

  // навесим события после вставки в DOM
  var overlay = document.querySelector(".ViewBattle__Overlay");
  var modal   = document.querySelector(".ViewBattle");
  if (!overlay || !modal) return;

  // клики внутри модалки не закрывают
  modal.addEventListener("click", function(e){ e.stopPropagation(); });

  // клик по подложке — закрыть (но только когда armed)
  overlay.addEventListener("click", function(){
    if (!armed) return;
    closeViewBattle();
  });

  // крестик
  var x = modal.querySelector(".ViewBattle__Close");
  if (x) x.addEventListener("click", closeViewBattle);

  // Esc
  document.addEventListener("keydown", function onEsc(e){
    if (e.key === "Escape") {
      closeViewBattle();
      document.removeEventListener("keydown", onEsc);
    }
  });
})();
</script>
';

echo json_encode(['html'=>$html], JSON_UNESCAPED_UNICODE);

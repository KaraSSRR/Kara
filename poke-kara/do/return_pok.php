<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project.'/inc/conf/global.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
    }
}

$tpl = <<<HTML
<div class="header return-header">
  Возврат покемонов
  <span class="header-close" onclick="$('.model').remove();" title="Закрыть">
    <i class="fas fa-times"></i>
  </span>
</div>

<div class="content-model return-content">
  <div class="return-note">
    <i class="far fa-coins"></i>
    Стоимость восстановления — <b>15 000</b> монет
  </div>

  <div class="pk-grid">
HTML;

/* ===================== СПИСОК ПОКЕМОНОВ ===================== */
$pok = $mysqli->query('SELECT * FROM `user_pokemons` WHERE `user_id` = 2 AND `del_pok` = '.$_SESSION['id'].' ORDER BY `id` ASC');

if ($pok && $pok->num_rows > 0) {
    while ($p = $pok->fetch_assoc()) {
        // Базовые значения
        $baseNum = numbPok($p['basenum']);
        $name    = htmlspecialchars($p['name_new']);
        $lvl     = (int)$p['lvl'];

        // Пол/иконки
        $paired  = ($p['sparka'] == 1 ? 'spar' : '');
        if ($p['gender'] == 'Девочка')      { $genderIcon = 'venus'; }
        elseif ($p['gender'] == 'Мальчик')  { $genderIcon = 'mars'; }
        else                                { $genderIcon = 'genderless'; }

        // Хэш генов (как на примере)
        $g = explode(',', $p['gen']);
        $hash = 'h'.($g[0] ?? 0)
              .'a'.($g[1] ?? 0)
              .'d'.($g[2] ?? 0)
              .'s'.($g[3] ?? 0)
              .'sa'.($g[4] ?? 0)
              .'sd'.($g[5] ?? 0)
              .'.'.(int)$p['vitamines'].' ('.(int)$p['sparkaNumber'].')';

        // Таймер — показываем, только если есть реальное время
        $timeHtml = '';
        if (!empty($p['del_time'])) {
            $timeLeft = $p['del_time'] - time();
            $timeLeft = time() + $timeLeft;
            $pretty   = downcountermin($timeLeft);
            if ($pretty) {
                $timeHtml = '<div class="pk-row"><span class="pk-chip pk-chip--time"><i class="far fa-stopwatch"></i>'.$pretty.'</span></div>';
            }
        }

        // Тип (не обязательно, но пригодится для классов при желании)
        $type = htmlspecialchars($p['type']);

        $tpl .= '
        <div class="divFarmPoke returnPok id'.(int)$p['id'].'">
          <div class="pk-card '.$type.'-type">

            <!-- Плавающая кнопка восстановления (оставляем ваш вызов) -->
            <button class="pk-action" title="Восстановить" onclick="return_delpok(&quot;ret&quot;,'.(int)$p['id'].');">
              <i class="fas fa-share-square"></i>
            </button>

            <!-- Аватар -->
            <div class="pk-avatar">
              <img src="/img/pokemons/animation/'.$baseNum.'.png" alt="#'.$baseNum.'">
            </div>

            <!-- Контент -->
            <div class="pk-content">
              <div class="pk-title">#'.$baseNum.' '.$name.'</div>

              <div class="pk-meta">
                <i class="fas fa-'.$genderIcon.' '.$paired.'"></i>
                <span>'.$lvl.'</span>
              </div>

              <div class="pk-row">
                <span class="pk-hash">'.$hash.( $p['sparka'] == 1 ? ' <i class="fas fa-level-up-alt pk-hash-up" title="Повышение"></i>' : '' ).'</span>
              </div>

              '.$timeHtml.'
            </div>
          </div>
        </div>
        <div class="hr id'.(int)$p['id'].'"></div>';
    }
} else {
    $tpl .= '
      <div class="pk-empty">
        <img src="/img/design/empty-box.svg" alt="">
        <h2>Нет отпущенных покемонов</h2>
        <p>Когда отпустите покемона, он появится здесь и его можно будет восстановить.</p>
      </div>';
}

$tpl .= <<<HTML
  </div>
</div>

<style>
/* ====== Общая шапка ====== */
.return-header{
  position:sticky; top:0; z-index:5;
  display:flex; align-items:center; justify-content:space-between;
  padding:12px 16px;
  background:linear-gradient(180deg, rgba(255,255,255,.95), rgba(248,250,252,.95));
  backdrop-filter:blur(8px);
  border-bottom:1px solid #edf1f6;
  font:800 16px/1.2 Inter, Nunito, Arial, sans-serif; color:#0f172a;
}
.return-header .header-close{
  cursor:pointer; padding:6px 8px; border-radius:10px; line-height:0;
  color:#64748b; transition:.15s ease;
}
.return-header .header-close:hover{ background:#eef2f7; color:#0f172a; }

.return-content{ padding:14px 16px 10px; background:linear-gradient(180deg,#fbfcfe 0%, #f6f8fb 100%); }

.return-note{
  margin:0 0 12px; padding:10px 12px;
  color:#0f172a;
  background:#fff; border:1px solid #e9eef6; border-radius:12px;
  box-shadow:0 1px 2px rgba(15,23,42,.04);
  display:inline-flex; align-items:center; gap:8px; font-weight:800;
}
.return-note i{ color:#0ea5e9; }

/* ====== Сетка ====== */
.pk-grid{
  display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
  gap:12px;
}

/* ====== Карточка в стиле примера ====== */
.pk-card{
  position:relative;
  display:grid; grid-template-columns:84px 1fr; gap:12px;
  padding:12px;
  background:#fff;
  border:1px solid #e9eef6;
  border-radius:14px;
  box-shadow:0 2px 6px rgba(15,23,42,.04);
  transition:border-color .15s ease, box-shadow .18s ease, transform .12s ease;
}
.pk-card:hover{
  border-color:#dfe6f0;
  box-shadow:0 10px 20px rgba(15,23,42,.08);
  transform:translateY(-1px);
}

/* Плавающая кнопка (правый верх) */
.pk-action{
  position:absolute; right:10px; top:10px;
  width:36px; height:36px; border-radius:10px;
  display:grid; place-items:center;
  background:#f3f0ff; color:#5b43d6;
  border:1px solid #e6e0ff; cursor:pointer;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.9);
  transition:.15s ease;
}
.pk-action:hover{ background:#ece6ff; color:#452fd0; }

/* Аватар */
.pk-avatar{
  width:84px; height:84px; display:grid; place-items:center;
  border:1px dashed #e3e9f2; border-radius:14px; background:#fbfdff;
}
.pk-avatar img{ width:70px; height:70px; object-fit:contain; filter:drop-shadow(0 2px 2px rgba(0,0,0,.06)); }

/* Контент */
.pk-content{ min-width:0; display:flex; flex-direction:column; gap:6px; }
.pk-title{
  font:900 16px/1.15 Inter, Nunito, Arial; color:#0f172a;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}

/* Пол + уровень */
.pk-meta{
  display:inline-flex; align-items:center; gap:8px;
  color:#334155; font-weight:800;
}

/* Ряд с чипами */
.pk-row{ display:flex; align-items:center; gap:8px; }

/* Фиолетовый хэш-бе́йдж (как на скрине) */
.pk-hash{
  display:inline-flex; align-items:center; gap:6px;
  padding:7px 10px; border-radius:12px;
  font:900 12px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
  color:#442ac1; background:#f3efff; border:1px solid #e4dbff;
}
.pk-hash-up{ color:#22c55e; } /* зелёная стрелка повышения */

/* Чип времени — маленький, незаметный */
.pk-chip{
  display:inline-flex; align-items:center; gap:6px;
  padding:6px 10px; border-radius:999px;
  font:800 12px/1 Inter, Nunito, Arial;
  border:1px solid transparent; color:#0f172a;
}
.pk-chip--time{ background:#eef6ff; border-color:#d7e8ff; color:#0b4da8; }
.pk-chip--time i{ color:#0ea5e9; }

/* Разделитель под карточками (сохраняю) */
.hr{ height:6px; border:none; }

/* Пусто */
.pk-empty{
  grid-column:1/-1; text-align:center;
  background:#fff; border:1px solid #e9eef6; border-radius:14px;
  padding:30px 16px; color:#64748b;
}
.pk-empty img{ width:80px; opacity:.5; margin-bottom:8px; }
.pk-empty h2{ margin:6px 0; color:#0f172a; font-weight:900; }

/* Мобильные правки */
@media (max-width: 480px){
  .pk-grid{ grid-template-columns:1fr; }
  .pk-card{ grid-template-columns:72px 1fr; gap:10px; }
  .pk-avatar{ width:72px; height:72px; }
  .pk-avatar img{ width:60px; height:60px; }
  .pk-title{ font-size:15px; }
}
</style>
HTML;

$response['html'] = $tpl;
die(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project.'/inc/conf/global.php';
if ($patch_global && file_exists($patch_global)) require_once $patch_global;

$id  = isset($_POST['id'])  ? (int)$_POST['id']  : 0;
$abl = isset($_POST['abl']) ? (int)$_POST['abl'] : 0;
$response = ['error'=>0];

/** =========================================================
 *        КЛАССИФИКАЦИЯ ТИПА СПОСОБНОСТИ (бейдж)
 *  Типы: Усиление | Вредоносность | Защита | Лечение | Погода
 *  Основано на названиях/описаниях из таблицы base_ability.
 * ========================================================= */
function abilityBadge(array $ab): array {
    // нормализация
    $nameEn = mb_strtolower(trim($ab['name']     ?? ''), 'UTF-8');
    $nameRu = mb_strtolower(trim($ab['name_rus'] ?? ''), 'UTF-8');
    $about  = mb_strtolower(trim($ab['about']    ?? ''), 'UTF-8');
    $nameAll = $nameEn.' '.$nameRu;

    // ---------- 1) Оверрайды по имени (спорные/особые) ----------
    // ключ => [text, cssClass, icon]
    $overrides = [
        // защитные «иммунитеты/не снижается/экраны»
        'clear body'                => ['Защита','tag tag--defense','fa-shield-halved'],
        'чистое тело'              => ['Защита','tag tag--defense','fa-shield-halved'],
        'white smoke'              => ['Защита','tag tag--defense','fa-shield-halved'],
        'mirror armor'             => ['Защита','tag tag--defense','fa-shield-halved'],
        'magic bounce'             => ['Защита','tag tag--defense','fa-shield-halved'],
        'magic guard'              => ['Защита','tag tag--defense','fa-shield-halved'],
        'good as gold'             => ['Защита','tag tag--defense','fa-shield-halved'],
        'prism armor'              => ['Защита','tag tag--defense','fa-shield-halved'],
        'solid rock'               => ['Защита','tag tag--defense','fa-shield-halved'],
        'filter'                   => ['Защита','tag tag--defense','fa-shield-halved'],
        'fur coat'                 => ['Защита','tag tag--defense','fa-shield-halved'],
        'ice scales'               => ['Защита','tag tag--defense','fa-shield-halved'],
        'multiscale'               => ['Защита','tag tag--defense','fa-shield-halved'],
        'sturdy'                   => ['Защита','tag tag--defense','fa-shield-halved'],
        'punk rock'                => ['Защита','tag tag--defense','fa-shield-halved'],

        // дебаффы/вред противнику
        'intimidate'               => ['Вредоносность','tag tag--debuff','fa-skull-crossbones'],
        'запугивание'              => ['Вредоносность','tag tag--debuff','fa-skull-crossbones'],
        'cotton down'              => ['Вредоносность','tag tag--debuff','fa-skull-crossbones'],
        'gooey'                    => ['Вредоносность','tag tag--debuff','fa-skull-crossbones'],
        'tangling hair'            => ['Вредоносность','tag tag--debuff','fa-skull-crossbones'],

        // лечение
        'natural cure'             => ['Лечение','tag tag--heal','fa-heart'],
        'healer'                   => ['Лечение','tag tag--heal','fa-heart'],
        'rain dish'                => ['Лечение','tag tag--heal','fa-heart'],
        'ice body'                 => ['Лечение','tag tag--heal','fa-heart'],
        'regenerator'              => ['Лечение','tag tag--heal','fa-heart'],
        'poison heal'              => ['Лечение','tag tag--heal','fa-heart'],
        'cheek pouch'              => ['Лечение','tag tag--heal','fa-heart'],
        'triage'                   => ['Лечение','tag tag--heal','fa-heart'],
        'hydration'                => ['Лечение','tag tag--heal','fa-heart'],

        // погода
        'drizzle'                  => ['Погода','tag tag--weather','fa-cloud-rain'],
        'изморось'                 => ['Погода','tag tag--weather','fa-cloud-rain'],
        'drought'                  => ['Погода','tag tag--weather','fa-sun'],
        'осушение'                 => ['Погода','tag tag--weather','fa-sun'],
        'sand stream'              => ['Погода','tag tag--weather','fa-wind'],
        'snow warning'             => ['Погода','tag tag--weather','fa-snowflake'],
        'air lock'                 => ['Погода','tag tag--weather','fa-cloud-slash'],
        'cloud nine'               => ['Погода','tag tag--weather','fa-cloud-slash'],
        'primordial sea'           => ['Погода','tag tag--weather','fa-water'],
        'desolate land'            => ['Погода','tag tag--weather','fa-fire'],
        'delta stream'             => ['Погода','tag tag--weather','fa-cloud'],

        // явные усиления (стаб/множители)
        'blaze'                    => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'torrent'                  => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'overgrow'                 => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'swarm'                    => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'adaptability'             => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'huge power'               => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'pure power'               => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'speed boost'              => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'moxie'                    => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'beast boost'              => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'serene grace'             => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'steelworker'              => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'technician'               => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'tough claws'              => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
        'strong jaw'               => ['Усиление','tag tag--buff','fa-arrow-trend-up'],
    ];
    foreach ($overrides as $k => $v) {
        if (mb_stripos($nameAll, $k, 0, 'UTF-8') !== false) {
            return ['text'=>$v[0],'cls'=>$v[1],'icon'=>$v[2]];
        }
    }

    // ---------- 2) Группы по имени ----------
    $groups = [
        'Защита' => [
            // иммунитеты/поглощения/снижение урона/запрет дебаффов
            'battle armor','shell armor','clear body','white smoke','keen eye','hyper cutter','big pecks',
            'soundproof','bulletproof','overcoat','friend guard','filter','solid rock','prism armor',
            'heatproof','thick fat','water veil','immunity','limber','own tempo','oblivious','insomnia','vital spirit',
            'levitate','lightning rod','volt absorb','water absorb','storm drain','sap sipper','flash fire',
            'marvel scale','fur coat','ice scales','multiscale','sturdy','well-baked body','mirror armor',
            'good as gold','magic bounce','magic guard','punk rock','shield dust','screen cleaner',
            'gale wings' /* ситуативная защита при полном HP */,
        ],
        'Вредоносность' => [
            // дебафф/урон по контакту/принудительные статусы/ловушки
            'intimidate','cotton down','gooey','tangling hair','bad dreams','aftermath','iron barbs','rough skin',
            'static','flame body','poison point','poison touch','effect spore','cute charm','sand spit','stench',
            'shadow tag','arena trap','magnet pull','neutralizing gas','perish body',
        ],
        'Лечение' => [
            'natural cure','healer','rain dish','ice body','regenerator','poison heal','triage','cheek pouch','hydration',
            'water absorb','volt absorb','dry skin','harvest','shell bell' /* на всякий случай */,
        ],
        'Погода' => [
            'drizzle','drought','sand stream','snow warning','air lock','cloud nine','primordial sea','desolate land',
            'delta stream','sand force','swift swim','chlorophyll','slush rush','ice body','solar power',
        ],
        'Усиление' => [
            'adaptability','analytic','anger point','beast boost','berserk','battery','chlorophyll','guts','hustle',
            'marvel scale','huge power','pure power','quick feet','galvanize','pixilate','refrigerate','aerilate',
            'iron fist','steelworker','serene grace','technician','tough claws','strong jaw','victory star','moxie',
            'speed boost','rattled','justified','motor drive','lightning rod','competitive','defiant',
            'download','mega launcher','solar power','chlorophyll','swift swim','sand rush','sand force','grassy surge',
            'psychic surge','misty surge','electric surge','terrain' /* любые terrain-баффы */,
        ],
    ];

    foreach ($groups as $label => $list) {
        foreach ($list as $needle) {
            if ($needle === '') continue;
            if (mb_stripos($nameAll, $needle, 0, 'UTF-8') !== false) {
                // отдаем найденный тип
                switch ($label) {
                    case 'Защита':      return ['text'=>'Защита','cls'=>'tag tag--defense','icon'=>'fa-shield-halved'];
                    case 'Вредоносность':return ['text'=>'Вредоносность','cls'=>'tag tag--debuff','icon'=>'fa-skull-crossbones'];
                    case 'Лечение':     return ['text'=>'Лечение','cls'=>'tag tag--heal','icon'=>'fa-heart'];
                    case 'Погода':      return ['text'=>'Погода','cls'=>'tag tag--weather','icon'=>'fa-cloud-sun'];
                    case 'Усиление':    return ['text'=>'Усиление','cls'=>'tag tag--buff','icon'=>'fa-arrow-trend-up'];
                }
            }
        }
    }

    // ---------- 3) Фолбэк по ключевым словам из описания ----------
    // Защита / иммунитет / «не понижается»
    if (preg_match('/предотвращ|блокиру|защищ|иммунитет|не\s+(?:может|будет)?\s*(?:быть\s*)?(?:понижен|снижен|уменьшен)|не\s+действует|не\s+влияет|reduce|resist|recoil/u', $about)) {
        return ['text'=>'Защита','cls'=>'tag tag--defense','icon'=>'fa-shield-halved'];
    }
    // Лечение / снятие/восстановление
    if (preg_match('/лечен|исцел|восстано|снимает.*статус|cure|heal|restore|regener/u', $about)) {
        return ['text'=>'Лечение','cls'=>'tag tag--heal','icon'=>'fa-heart'];
    }
    // Вредоносность / дебаффы / статусы / урон контактом
    if (preg_match('/понижа|снижа|ослаб|отрав|ожог|паралич|сон|confus|burn|poison|paraly|lower|foe|контакт.{0,12}урон/u', $about)) {
        return ['text'=>'Вредоносность','cls'=>'tag tag--debuff','icon'=>'fa-skull-crossbones'];
    }
    // Погода
    if (preg_match('/погод|дожд|солнц|буря|град|метел|hail|sand|rain|sun|weather/u', $about.' '.$nameAll)) {
        return ['text'=>'Погода','cls'=>'tag tag--weather','icon'=>'fa-cloud-sun'];
    }
    // Усиление (по умолчанию)
    return ['text'=>'Усиление','cls'=>'tag tag--buff','icon'=>'fa-arrow-trend-up'];
}

/* -------- Список способностей постранично -------- */
if (!$abl && $id) {
    $offset = $id * 20;
    $tpl = '';
    $q = $mysqli->query("SELECT id, name_rus, about
                         FROM base_ability
                         WHERE about != '...'
                         ORDER BY name_rus ASC
                         LIMIT $offset, 20");
    while ($row = $q->fetch_assoc()) {
        $tpl .= '
        <div class="AblBox" onclick="viewDescriptionAbility('.(int)$row['id'].', event)">
          <div class="InfoTop"><div class="Name">'.htmlspecialchars($row['name_rus']).'</div></div>
          <div class="InfoBottom"><div class="Description">'.htmlspecialchars($row['about']).'</div></div>
        </div>';
    }
    $response['html'] = $tpl ?: '<div class="AbilityCard__placeholder">Больше способностей нет.</div>';
    echo json_encode($response); exit;
}

/* -------- Одна способность — карточка -------- */
if ($abl && !$id) {
    $ab = $mysqli->query("SELECT id, name_rus, name, about FROM base_ability WHERE id = $abl")->fetch_assoc();
    if (!$ab) { $response['text'] = '<div class="AbilityCard__placeholder">Способность не найдена.</div>'; echo json_encode($response); exit; }

    $name_ru = htmlspecialchars($ab['name_rus']);
    $name_en = htmlspecialchars($ab['name']);
    $about   = nl2br(htmlspecialchars($ab['about']));

    // Тип/иконка/цвет бейджа
    $badge = abilityBadge($ab);
    $badgeHtml = '<span class="'.$badge['cls'].'"><i class="fas '.$badge['icon'].'"></i> '.$badge['text'].'</span>';

    // Нормальные слоты
    $norm = $mysqli->query("
      SELECT p.id, p.name_rus
      FROM base_pokemons p
      JOIN base_ability_pokemon ap ON ap.id = p.id
      WHERE ap.slot1 = $abl OR ap.slot2 = $abl
      ORDER BY p.id
      LIMIT 256
    ");
    // Скрытый слот
    $hid = $mysqli->query("
      SELECT p.id, p.name_rus
      FROM base_pokemons p
      JOIN base_ability_pokemon ap ON ap.id = p.id
      WHERE ap.hidden = $abl
      ORDER BY p.id
      LIMIT 256
    ");

    ob_start(); ?>
<div class="AbilityCard" data-abl-mode="desc">
  <div class="AbilityCard__title"><?= $name_ru ?></div>
  <div class="AbilityCard__meta">
    <span>Способность,</span>
    <span class="en"><?= $name_en ?></span>
    <?= $badgeHtml ?>
    <span class="AbilityCard__info js-abl-mode AbilityCard__mode is-active" data-mode="desc" title="Описание"><i class="fas fa-info-circle"></i></span>
    <span class="AbilityCard__info js-abl-mode AbilityCard__mode" data-mode="list" title="Покемоны"><i class="fas fa-th-large"></i></span>
  </div>

  <div class="AbilityCard__about"><?= $about ?></div>

  <div class="AbilityCard__lists">
    <div class="AbilityTabs">
      <div class="AbilityTab is-active" data-tab="normal">Обычная способность</div>
      <div class="AbilityTab" data-tab="hidden">Скрытая способность</div>
    </div>

    <div class="PokeGrid" data-tab="normal" style="display:grid">
      <?php if ($norm && $norm->num_rows): while ($r = $norm->fetch_assoc()):
            $pid = (int)$r['id']; $num = NumbPok($pid); ?>
        <div class="pk" data-pid="<?= $pid ?>" title="#<?= $num ?> <?= htmlspecialchars($r['name_rus']) ?>">
          <img src="/img/pokemons/animation/<?= $num ?>.png" alt="#<?= $num ?>">
        </div>
      <?php endwhile; else: ?>
        <div class="AbilityCard__placeholder">Нет покемонов с обычным слотом.</div>
      <?php endif; ?>
    </div>

    <div class="PokeGrid" data-tab="hidden" style="display:none">
      <?php if ($hid && $hid->num_rows): while ($r = $hid->fetch_assoc()):
            $pid = (int)$r['id']; $num = NumbPok($pid); ?>
        <div class="pk" data-pid="<?= $pid ?>" title="#<?= $num ?> <?= htmlspecialchars($r['name_rus']) ?>">
          <img src="/img/pokemons/animation/<?= $num ?>.png" alt="#<?= $num ?>">
        </div>
      <?php endwhile; else: ?>
        <div class="AbilityCard__placeholder">Нет покемонов со скрытым слотом.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
    <?php
    $response['text'] = ob_get_clean();
    echo json_encode($response); exit;
}

$response['error'] = 1;
$response['text']  = '<div class="AbilityCard__placeholder">Неверные параметры запроса.</div>';
echo json_encode($response);

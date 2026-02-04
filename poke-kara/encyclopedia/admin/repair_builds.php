<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

enc_require_login($encyUserId);
if (!$encyIsAdmin) {
  http_response_code(403);
  die('Forbidden');
}

if (!$encyInstalled) {
  http_response_code(400);
  die('Not installed');
}

$log = [];

// 1) rebuild enc_build_pokemon
if ($encyHasBuildIndex) {
  $mysqli->query("TRUNCATE TABLE enc_build_pokemon");

  $q = $mysqli->query("SELECT id, team_json FROM enc_builds");
  $cntBuilds = 0;
  $cntRows = 0;
  if ($q) {
    while ($b = $q->fetch_assoc()) {
      $cntBuilds++;
      $buildId = (int)$b['id'];
      $slots = enc_team_slots_from_json((string)$b['team_json']);
      foreach ($slots as $i=>$s) {
        $pid = (int)$s['pokemon_id'];
        if ($pid<=0) continue;
        $slot = $i+1;
        $mysqli->query("INSERT INTO enc_build_pokemon(build_id,pokemon_id,slot_index) VALUES (".(int)$buildId.",".(int)$pid.",".(int)$slot.")");
        $cntRows++;
      }
    }
  }
  $log[] = "enc_build_pokemon: builds={$cntBuilds}, rows={$cntRows}";
} else {
  $log[] = "enc_build_pokemon: table missing (skip)";
}

// 2) rebuild popularity
if ($encyHasPopularity && $encyHasBuildIndex) {
  $counts = [];
  $q = $mysqli->query("SELECT bp.pokemon_id, COUNT(DISTINCT bp.build_id) AS cnt
                       FROM enc_build_pokemon bp
                       JOIN enc_builds b ON b.id=bp.build_id
                       WHERE b.visibility='public'
                       GROUP BY bp.pokemon_id");
  if ($q) {
    while ($r = $q->fetch_assoc()) {
      $counts[(int)$r['pokemon_id']] = (int)$r['cnt'];
    }
  }

  $updated = 0;
  foreach ($counts as $pid=>$cnt) {
    if (enc_popularity_upsert($mysqli, $pid, $cnt)) $updated++;
  }
  $log[] = "enc_pokemon_popularity: updated={$updated}, distinct_pokemon=".count($counts);
} else {
  $log[] = "enc_pokemon_popularity: table missing (skip)";
}

$pageTitle = 'Repair builds';
require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Repair builds</h1>
  <div class="muted">Пересборка индексов и популярности.</div>
  <ul class="list" style="margin-top:10px;">
    <?php foreach ($log as $line): ?>
      <li><?=enc_h($line)?></li>
    <?php endforeach; ?>
  </ul>
  <div style="margin-top:10px;">
    <a class="btn" href="<?=enc_h(enc_url('/builds.php'))?>">К сборкам</a>
  </div>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>

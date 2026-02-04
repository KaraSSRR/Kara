  </main>

  <footer class="forum-footer">
    <div class="muted">Poke Kara</div>
  </footer>
</div>

<script>
  window.ENC_BASE = <?=json_encode(rtrim(ENC_BASE, '/'))?>;
  window.ENC_SPRITE_ANIM_DIR = <?=json_encode(rtrim(ENC_SPRITE_ANIM_DIR, '/'))?>;
  window.ENC_SPRITE_POKEDEX_DIR = <?=json_encode(rtrim(ENC_SPRITE_POKEDEX_DIR, '/'))?>;
  window.ENC_SPRITE_PLACEHOLDER = <?=json_encode(rtrim(ENC_SPRITE_PLACEHOLDER, '/'))?>;
</script>
<script src="<?=enc_h(enc_url('/assets/ency.js'))?>"></script>
<?php
  // Optional page-level scripts
  if (isset($encExtraScripts) && is_array($encExtraScripts)) {
    foreach ($encExtraScripts as $src) {
      $src = (string)$src;
      if ($src === '') continue;
      echo '<script src="' . enc_h($src) . '"></script>';
    }
  }
?>
</body>
</html>

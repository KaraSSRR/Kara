<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
    }
}

$response = [
    'error' => null,
    'text'  => '',
];

$tpl = "";

/* Лёгкий светло-серый стиль (без изменения структуры DOM) */
$tpl .= '
<style>
/* встраиваем один раз — дубли не критичны */
.divFarmPoke.returnPok{
  position:relative;
  background:#ffffff;
  border:1px solid #e6e8ec;
  border-radius:14px;
  padding:12px 12px 12px 12px;
  margin:10px 0;
  box-shadow:0 2px 6px rgba(30,41,59,.04);
  transition:transform .12s ease, box-shadow .18s ease, border-color .18s ease;
}
.divFarmPoke.returnPok:hover{
  transform:translateY(-1px);
  border-color:#dfe3ea;
  box-shadow:0 10px 24px rgba(30,41,59,.08);
}
.pokemonBoxTiny{
  display:flex;
  align-items:center;
  gap:14px;
  min-height:76px;
}
.pokemonBoxTiny .image{
  width:72px; height:72px; object-fit:contain;
  filter: drop-shadow(0 2px 2px rgba(0,0,0,.08));
}

.nameNur{
  font-weight:800;
  font-size:15px;
  letter-spacing:.2px;
  color:#1f2937;              /* базовый тёмно-серый */
  margin-bottom:4px;
  display:inline-block;
}
.shorts{
  color:#667085;              /* средне-серый */
  font-weight:700;
  font-size:13px;
}
.shorts i{ opacity:.9; margin-right:6px; }

.extra{
  margin-top:4px;
  color:#4b5563;
  font-size:12px;
  line-height:1.35;
}
.ivcode{
  display:inline-block;
  padding:2px 6px;
  border-radius:8px;
  background:#f3f4f6;
  border:1px solid #eceff4;
  color:#374151;
  font-weight:700;
}

.btnBackPokemon{
  position:absolute; right:12px; top:12px;
  background:linear-gradient(180deg, #f7f8fa, #eef1f6);
  border:1px solid #e4e7ee;
  color:#0f172a;
  padding:6px 9px;
  border-radius:10px;
  font-weight:800;
  font-size:13px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.8), 0 1px 0 rgba(0,0,0,.02);
  transition:all .12s ease;
}
.btnBackPokemon i{ margin:0; }
.btnBackPokemon:hover{
  background:#e9edf4;
  border-color:#dfe4ee;
  transform:translateY(-1px);
}

.hr{ height:8px; border:none; }

.return-caption{
  margin:8px 0 14px;
  padding:8px 12px;
  border-radius:12px;
  background:#f6f7f9;
  border:1px solid #eceff4;
  color:#374151;
  font-weight:800;
}

/* Небольшие акценты для разных типов/состояний можно оставить вашими цветами */
</style>
';

if (!empty($_POST['return_pok'])) {
    $id     = (int)$_POST['return_pok'];
    $userId = (int)($_SESSION['id'] ?? 0);

    // Ищем покемона, которого можно вернуть (лежит у user_id=2 и был отпущен текущим игроком)
    $pok_ret = $mysqli->query(
        'SELECT * FROM `user_pokemons` 
         WHERE `user_id` = 2 AND `del_pok` = ' . $userId . ' AND `id` = ' . $id . ' 
         LIMIT 1'
    )->fetch_assoc();

    if ($pok_ret) {
        // Проверяем валюту (item_id 1, 15000)
        if (item_isset(1, 15000)) {

            // Возвращаем покемона пользователю
            $mysqli->query("
                UPDATE `user_pokemons`
                SET `del_pok` = 0, `user_id` = '{$userId}', `del_time` = 0
                WHERE `id` = '{$id}' AND `user_id` = 2
            ");

            // Списываем валюту
            minus_item(1, 15000);

            $response['text']  = 'Покемон успешно восстановлен!';
            $response['error'] = 'success';

            // Блок «минус»
            $response['minus'] = '<img src="img/world/items/little/1.png" class="item"> Генкар <b>x15.000</b>';

            // Блок «плюс»
            $response['plus']  = '<img src="/img/pokemons/animation/' . numbPok($pok_ret['basenum']) . '.png"> #'
                               . numbPok($pok_ret['basenum']) . ' ' . htmlspecialchars($pok_ret['name_new']);

        } else {
            $response['text']  = 'У вас недостаточно генкар!';
            $response['error'] = 'error';
        }
    } else {
        $response['text']  = 'Покемон не найден!';
        $response['error'] = 'error';
    }

    // Обновляем список "отпущенных" этого игрока (они всё ещё у user_id=2, помечены del_pok = userId)
    $pok = $mysqli->query('
        SELECT * FROM `user_pokemons`
        WHERE `user_id` = 2 AND `del_pok` = ' . $userId . '
        ORDER BY `id` ASC
    ');

    if ($pok && $pok->num_rows > 0) {

        // Заголовок блока
        $tpl .= '<div class="return-caption">Ваши отпущенные покемоны</div>';

        while ($poks = $pok->fetch_assoc()) {
            $genArr = explode(',', $poks['gen']);
            $gen    = 'h' . ($genArr[0] ?? 0)
                    . 'a' . ($genArr[1] ?? 0)
                    . 'd' . ($genArr[2] ?? 0)
                    . 's' . ($genArr[3] ?? 0)
                    . 'sa' . ($genArr[4] ?? 0)
                    . 'sd' . ($genArr[5] ?? 0);

            $paired = ($poks['sparka'] == 1 ? 'spar' : '');

            if ($poks['gender'] == 'Девочка') {
                $gender = 'venus';
            } elseif ($poks['gender'] == 'Мальчик') {
                $gender = 'mars';
            } else {
                $gender = 'genderless';
            }

            // оставшееся время
            $timeOnl = ($poks['del_time'] - time());
            $timeOnl = time() + $timeOnl;
            $time    = downcountermin($timeOnl);

            $tpl .= '
            <div>
              <div class="divFarmPoke returnPok id' . (int)$poks['id'] . '">
                <div class="btnBackPokemon" onclick="return_delpok(&quot;ret&quot;,' . (int)$poks['id'] . ');"
                     title="Восстановить покемона">
                  <i class="fas fa-sign-out-alt"></i>
                </div>

                <div class="pokemonBoxTiny size0 clickable">
                  <img class="image" src="/img/pokemons/animation/' . numbPok($poks['basenum']) . '.png" alt="#' . numbPok($poks['basenum']) . '">
                  <div class="info">
                    <div class="nameNur ' . htmlspecialchars($poks['type']) . '-color">
                      #' . numbPok($poks['basenum']) . ' ' . htmlspecialchars($poks['name_new']) . '
                    </div>
                    <div class="shorts">
                      <i class="fas fa-' . $gender . ' ' . $paired . '"></i>
                      Уровень: ' . (int)$poks['lvl'] . '
                    </div>
                    <div class="extra">
                      <span class="ivcode">' . $gen . '.' . (int)$poks['vitamines'] . ' (' . (int)$poks['sparkaNumber'] . ')</span><br>
                      <i class="far fa-stopwatch"></i> ' . $time . '
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="hr id' . (int)$poks['id'] . '"></div>';
        }

    } else {
        $tpl .= '
        <div class="return-caption" style="text-align:center;">
          У вас нет отпущенных покемонов
        </div>';
    }
}

$response['html'] = $tpl;
die(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

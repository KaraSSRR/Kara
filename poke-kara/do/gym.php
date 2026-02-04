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

$type  = isset($_POST['type']) ? escapeMe($_POST['type']) : '';
$uid   = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$user  = $mysqli->query('SELECT * FROM users WHERE id = '.$uid)->fetch_assoc();
$response = ['html' => '', 'error' => '']; // чтобы json всегда собирался

switch ($type) {

    /* ===================== Контейнер "Секретарь" ===================== */
    case 'sekretar':
    // Права куратора
    $is_curator = isset($user['user_group']) && intval($user['user_group']) == 10;

    $tpl =
'<div class="st-panel-header">
  <div class="st-panel-title">Секретарь</div>
  <div class="st-panel-close" title="Закрыть"><i class="fas fa-times"></i></div>
</div>

<div class="st-sekretar-tabs">
  <div class="st-tab-btn active" data-tab="gyms">Гим-лидеры</div>
  <div class="st-tab-btn" data-tab="league">Лига чемпионов</div>
  <div class="st-tab-btn" data-tab="tournaments">Турниры</div>' .
  ($is_curator ? '<div class="st-tab-btn" data-tab="tournament-admin">Управление</div>' : '') .
'</div>

<div class="st-sekretar-tab-content gyms-tab active"></div>
<div class="st-sekretar-tab-content league-tab"></div>
<div class="st-sekretar-tab-content tournaments-tab"></div>' .
  ($is_curator ? '<div class="st-sekretar-tab-content tournament-admin-tab"></div>' : '') .

// Компактные стили
'<style>
  .st-panel-header{display:flex;align-items:center;justify-content:space-between}
  .st-sekretar-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0}
  .st-tab-btn{cursor:pointer;border:1px solid #dfe6ff;background:#f2f6ff;border-radius:999px;padding:6px 10px;font-weight:700}
  .st-tab-btn.active{border-color:#2f74ff;background:#eaf1ff}
  .st-sekretar-tab-content{max-height:62vh;overflow:auto}
  .st-panel-content{padding:8px}
  .st-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
  .lg-card{border:1px solid #e6eafe;border-radius:12px;padding:10px;background:#fff}
  .lg-card .top{display:flex;gap:10px;align-items:center}
  .lg-card img{width:48px;height:48px;border-radius:8px;border:1px solid #e6eafe;background:#f5f7ff}
  .lg-card .name{font-weight:800}
  .lg-card .meta{color:#6f7b95;font-size:12px}
  .lg-card .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
  .st-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #2f74ff;background:#2f74ff;color:#fff;border-radius:10px;padding:6px 10px;font-weight:800;cursor:pointer}
  .st-btn:hover{filter:brightness(1.05)}
  .st-btn.st-btn-red{border-color:#ca3a3a;background:#ca3a3a}
  .st-select{border:1px solid #e6eafe;border-radius:10px;padding:6px 10px}
  .st-hr{height:1px;background:#eef2ff;margin:10px 0}
  .st-panel-sub{color:#6f7b95;margin:6px 0;font-weight:700}
</style>

<script>
(function(){
  // --- Переключение вкладок + лениво подгружаем контент ---
  $(".st-sekretar-tabs .st-tab-btn").off("click.stSekretar").on("click.stSekretar", function(){
    var tab = $(this).data("tab");
    $(".st-sekretar-tabs .st-tab-btn").removeClass("active");
    $(this).addClass("active");
    $(".st-sekretar-tab-content").removeClass("active");
    $("." + tab + "-tab").addClass("active");
    if(tab==="gyms" && !$(".gyms-tab").html().trim())               loadGymsTab();
    if(tab==="league" && !$(".league-tab").html().trim())           loadLeagueTab();
    if(tab==="tournaments" && !$(".tournaments-tab").html().trim()) loadTournamentsTab();
    if(tab==="tournament-admin" && !$(".tournament-admin-tab").html().trim()) loadTournamentAdminTab();
  });

  function loadGymsTab(){ $.post("/do/gym.php",{type:"gyms_panel"}, function(r){ $(".gyms-tab").html(r.html); },"json"); }
  function loadLeagueTab(){ $.post("/do/gym.php",{type:"league_panel"}, function(r){ $(".league-tab").html(r.html); },"json"); }
  function loadTournamentsTab(){ $.post("/do/gym.php",{type:"tournaments_panel"}, function(r){ $(".tournaments-tab").html(r.html); },"json"); }
  function loadTournamentAdminTab(){ $.post("/do/gym.php",{type:"tournament_admin_panel"}, function(r){ $(".tournament-admin-tab").html(r.html); },"json"); }

  // По умолчанию показываем «Гим-лидеры»
  loadGymsTab();

  // --- Закрытие панели (крестик, фон, Esc) ---
  var $root = $(".LittleModal:visible").last();
  if (!$root.length) $root = $(".PanelModal:visible").last();
  if ($root.length) $root.addClass("st-sekretar-mounted");

  function stSekretarClose(){
    var $m = $(".LittleModal.st-sekretar-mounted:visible").last();
    if (!$m.length) $m = $(".PanelModal:visible").last();
    if ($m.length) $m.remove();
    $(document).off("keydown.stSekretarClose click.stSekretarBackdrop click.stSekretarBtn");
  }

  // Крестик
  $(document).off("click.stSekretarBtn").on("click.stSekretarBtn", ".st-panel-close", function(e){
    e.preventDefault(); stSekretarClose();
  });

  // Клик по подложке
  $(document).off("click.stSekretarBackdrop").on("click.stSekretarBackdrop", ".LittleModal.st-sekretar-mounted", function(e){
    if (e.target === this) stSekretarClose();
  });

  // Esc
  $(document).off("keydown.stSekretarClose").on("keydown.stSekretarClose", function(e){
    if (e.key === "Escape") stSekretarClose();
  });

  // Экспорт на всякий
  window.stSekretarClose = stSekretarClose;
})();
</script>';

    $response['html'] = $tpl;
    break;

    /* ===================== Вкладка: Гим-лидеры (как было) ===================== */
    case 'gyms_panel':
        $tpl = '<div class="st-panel-content">
                    <p class="st-panel-sub">Выберите Гим-Лидера, к которому хотите отправить заявку.</p>
                    <select id="SortGym" class="st-select">
                        <option value="4">Kara</option>
                    </select>
                    <div class="st-btn" onclick="gym_application()">Отправить заявку</div>';

        $application_bd = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$uid.' AND type = 0')->fetch_assoc();
        $application    = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$uid.' AND type = 0');

        if (isset($application_bd)) {
            $tpl .= '<div class="st-hr"></div>
                <h2 class="st-panel-h2">Заявки</h2>
                <p class="st-panel-sub">У вас есть заявки на сражение за значок, свяжитесь с кандидатом.</p>';
            while ($app = $application->fetch_assoc()) {
                $user_app = $mysqli->query('SELECT * FROM users WHERE id = '.intval($app['user']))->fetch_assoc();
                $tpl .= '<div class="st-user-application">Тренер <b>'.$user_app['login'].'</b>
                    <div class="st-panel-btns">
                        <div class="st-btn" onclick="gym_answer('.intval($user_app['id']).',1)">Значок</div>
                        <div class="st-btn st-btn-red" onclick="gym_answer('.intval($user_app['id']).',2)">Проигрыш</div>
                    </div>
                </div>';
            }
        }
        $tpl .= '</div>';
        $response['html'] = $tpl;
    break;

    /* ===================== Новая вкладка: Лига чемпионов ===================== */
    case 'league_panel':
    // Лидеры (user_group = 5)
    $leaders = $mysqli->query('SELECT id, login FROM users WHERE user_group = 5 ORDER BY login');

    // Мета из БД (см. схему gym_league_meta из прошлого ответа)
    $meta = [];
    if ($res = @$mysqli->query("SELECT user_id, format, min_lvl, max_lvl, schedule, region, description, badge_item, badge_img FROM gym_league_meta")) {
        while ($m = $res->fetch_assoc()) $meta[(int)$m['user_id']] = $m;
    }

    // Фолбэк «лидер -> item_id значка», если строки в мета нет
    $badge_map_fallback = [4=>5.11,118=>1000017,134=>1000018,262=>1000007,312=>1000010,410=>1000009,841=>1000005,917=>1000006,79=>1000014];

    $cards = '';
    while ($l = $leaders->fetch_assoc()) {
        $lid   = (int)$l['id'];
        $name  = htmlspecialchars($l['login'], ENT_QUOTES, 'UTF-8');
        $m     = $meta[$lid] ?? [];

        $fmt   = !empty($m['format']) ? htmlspecialchars($m['format'], ENT_QUOTES, 'UTF-8') : '—';
        $lvl   = (isset($m['min_lvl'],$m['max_lvl']) && $m['min_lvl']!=='' && $m['max_lvl']!=='')
                ? (intval($m['min_lvl']).'–'.intval($m['max_lvl'])) : '—';
        $time  = !empty($m['schedule']) ? htmlspecialchars($m['schedule'], ENT_QUOTES, 'UTF-8') : '—';
        $reg   = !empty($m['region']) ? htmlspecialchars($m['region'], ENT_QUOTES, 'UTF-8') : '—';
        $desc  = !empty($m['description']) ? htmlspecialchars($m['description'], ENT_QUOTES, 'UTF-8') : '—';

        // Значок
        $badgeItem = isset($m['badge_item']) ? (int)$m['badge_item'] : 0;
        if (!$badgeItem && isset($badge_map_fallback[$lid])) $badgeItem = (int)$badge_map_fallback[$lid];
        $badgeUrl = '';
        if (!empty($m['badge_img']))      $badgeUrl = htmlspecialchars($m['badge_img'], ENT_QUOTES, 'UTF-8');
        elseif ($badgeItem)               $badgeUrl = '/img/world/items/little/'.$badgeItem.'.png';
        else                              $badgeUrl = '/img/ui/badge-placeholder.png';

        $cards .= '
        <div class="lg3-card">
          <div class="lg3-top">
            <img class="lg3-ava" src="/img/avatars/mini/'.$lid.'.png"
                 onerror="this.src=\'/img/avatars/mini/no-user-img.png\'" alt="">
            <div class="lg3-main">
              <div class="lg3-name">'.$name.'</div>
              <div class="lg3-chips">
                <span class="lg3-chip">'.$fmt.'</span>
                <span class="lg3-chip">Lv '.$lvl.'</span>
                <span class="lg3-chip">'.$reg.'</span>
              </div>
            </div>
            <div class="lg3-badge" title="Значок">
              <img src="'.$badgeUrl.'" alt="">
            </div>
          </div>

          <div class="lg3-lines">
            <div class="lg3-line" title="'.$time.'"><b>Приём:</b> '.$time.'</div>
          </div>

          <div class="lg3-actions">
            <button class="lg3-btn primary" onclick="gym_application('.$lid.')">
              <i class="far fa-envelope"></i><span>Заявка</span>
            </button>
            <button class="lg3-btn ghost" onclick="lg3ToggleMore(this)">
              <i class="far fa-info-circle"></i><span>Подробнее</span>
            </button>
          </div>

          <div class="lg3-more">
            <div class="lg3-more-text">'.$desc.'</div>
          </div>
        </div>';
    }

    $tpl = '
    <style>
      /* ====== Лига: компактный современный вид ====== */
      .st-sekretar-tab-content{max-height:62vh;overflow:auto}

      .lg3-wrap{display:flex;flex-direction:column;gap:8px}
      .lg3-filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
      .lg3-filters .st-select{min-width:220px}

      .st-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}

      .lg3-card{border:1px solid #e6eafe;background:#fff;border-radius:14px;padding:10px;box-shadow:0 1px 0 #f1f4ff}
      .lg3-top{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center}
      .lg3-ava{width:44px;height:44px;border-radius:10px;border:1px solid #e6eafe;background:#f5f7ff}
      .lg3-badge{width:34px;height:34px;border:1px solid #e6eafe;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#fff}
      .lg3-badge img{width:26px;height:26px}

      .lg3-name{font-weight:900;line-height:1.05}
      .lg3-chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:4px}
      .lg3-chip{background:#eef3ff;border:1px solid #dbe6ff;border-radius:999px;padding:2px 8px;font-weight:800;font-size:11px;color:#1b2b4f}

      .lg3-lines{margin-top:6px}
      .lg3-line{font-size:12px;color:#47516b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

      .lg3-actions{display:flex;gap:6px;margin-top:8px}
      .lg3-btn{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;border-radius:10px;border:1px solid #d8e1ff;background:#fff;font-weight:900;font-size:12px;cursor:pointer}
      .lg3-btn i{font-size:14px}
      .lg3-btn.primary{background:#2f74ff;color:#fff;border-color:#2f74ff}
      .lg3-btn.ghost{color:#1b2b4f}
      .lg3-btn:hover{filter:brightness(0.98)}

      .lg3-more{display:none;margin-top:8px;padding-top:8px;border-top:1px solid #eef2ff;font-size:12px;color:#2d3b57}
      .lg3-more-text{white-space:pre-wrap;line-height:1.35}

      @media (max-width:560px){
        .st-grid{grid-template-columns:1fr}
        .lg3-btn{flex:1 1 auto;justify-content:center}
      }
    </style>

    <div class="st-panel-content lg3-wrap">
      <div class="lg3-filters">
        <input type="search" class="st-select" id="lgSearch" placeholder="Поиск по имени…">
        <select id="lgRegion" class="st-select">
          <option value="">Все регионы/типы</option>
          <option value="Канто">Канто</option><option value="Джото">Джото</option>
          <option value="Хоэнн">Хоэнн</option><option value="Синно">Синно</option>
          <option value="Унова">Унова</option><option value="Калос">Калос</option>
          <option value="Галар">Галар</option><option value="Палдея">Палдея</option>
        </select>
      </div>
      <div class="st-hr"></div>
      <div class="st-grid" id="lgGrid">'.$cards.'</div>
    </div>

    <script>
      // Заявка
      function gym_application_user(id){
          $.post("/do/gym.php",{type:"application", gym:id}, function(resp){
              if (window.Game && Game.notifications && Game.notifications.main) {
                  Game.notifications.main(resp.html, resp.error==="success"?"success":"error");
              } else { alert(resp.html); }
          },"json");
      }
      // Подробнее
      window.lg3ToggleMore = function(btn){
          var $more = $(btn).closest(".lg3-card").find(".lg3-more");
          $more.stop(true,true).slideToggle(150);
      };
      // Фильтры
      (function(){
          var $g=$("#lgGrid"), $s=$("#lgSearch"), $r=$("#lgRegion");
          function apply(){
              var q = ($s.val()||"").toLowerCase();
              var reg = $r.val()||"";
              $g.find(".lg3-card").each(function(){
                  var name = $(this).find(".lg3-name").text().toLowerCase();
                  var chips = $(this).find(".lg3-chips").text();
                  var ok = (!q || name.indexOf(q)>=0) && (!reg || chips.indexOf(reg)>=0);
                  $(this).toggle(ok);
              });
          }
          $s.on("input", apply); $r.on("change", apply);
      })();
    </script>';

    $response['html'] = $tpl;
break;

    /* ===================== Подача заявки (как было) ===================== */
    case 'application':
        $id   = intval($_POST['gym']);
        $gym  = $mysqli->query('SELECT * FROM users WHERE id = '.$id.' AND `user_group` = 5')->fetch_assoc();
        $bd   = $mysqli->query('SELECT * FROM gym_log WHERE user = '.$uid.' ORDER BY `id` DESC LIMIT 1')->fetch_assoc();
        $time = time() - 3600 * 36;

        $znak_map = [4=>1000014,118=>1000017,134=>1000018,262=>1000007,312=>1000010,410=>1000009,841=>1000005,917=>1000006];
        $znak = isset($znak_map[$id]) ? $znak_map[$id] : null;

        if ($gym && $znak) {
            if (intval($user['lvl']) >= 1) {
                if (!$bd || ($bd['type'] != 0 && $bd['time'] < $time)) {
                    $item = $mysqli->query('SELECT * FROM items_users WHERE user = '.$uid.' AND `item_id` = '.$znak)->fetch_assoc();
                    if (!$item) {
                        $t = time();
                        $mysqli->query("INSERT INTO `gym_log` (`user`,`gym`,`time`,`type`) VALUES ('".$user['id']."','".$id."','".$t."','0')");
                        $response['html']  = "Заявка успешно подана, ожидайте ответ от Гим-Лидера!";
                        $response['error'] = "success";
                        // уведомление лидеру
                        $months=[1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря'];
                        $date = date("d").' '.$months[date("n")].' '.date("Y").'г. в '.date("H:i");
                        $text = 'У вас новый кандидат на сражение на стадионе. Проверьте панель';
                        $mysqli->query("INSERT INTO `notification` (`text`,`user`,`img`,`date`) VALUES ('".$text."','".$id."','/img/world/items/little/223.png','".$date."')");
                    } else {
                        $response['html'] = "У вас уже есть этот значок!";
                        $response['error'] = "error";
                    }
                } else {
                    $response['html'] = "У вас есть не закрытая заявка или для следующей заявки не прошло 36 часов!";
                    $response['error'] = "error";
                }
            } else {
                $response['html'] = "Ваш уровень меньше необходимого!";
                $response['error'] = "error";
            }
        } else {
            $response['html'] = "Ошибка!";
            $response['error'] = "error";
        }
    break;

    /* ===================== Ответ лидера (как было) ===================== */
    case 'answer':
        $user_id = intval($_POST['user']);
        $answer  = intval($_POST['answer']);
        if ($answer == 1) {
            $bd = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$uid.' AND user = '.$user_id.' AND type = 0')->fetch_assoc();
            if ($bd) {
                $id = $uid;
                if($id == 79){$znak = 1000014;}
                elseif($id == 118){$znak = 1000017;}
                elseif($id == 134){$znak = 1000018;}
                elseif($id == 262){$znak = 1000007;}
                elseif($id == 312){$znak = 1000010;}
                elseif($id == 410){$znak = 1000009;}
                elseif($id == 841){$znak = 1000005;}
                elseif($id == 917){$znak = 1000006;}
                $mysqli->query("INSERT INTO `items_users` (`user`,`item_id`,`count`,`trophy`) VALUES ('".$user_id."','".$znak."','1','1')");
                $mysqli->query("UPDATE `gym_log` SET `type` = '2' WHERE `id` = '".$bd['id']."'");
                $response['html']  = "Тренеру засчитана победа и выдан значок!";
                $response['error'] = "success";
            } else {
                $response['html'] = "Ошибка!";
                $response['error'] = "error";
            }
        } else {
            $bd = $mysqli->query('SELECT * FROM gym_log WHERE gym = '.$uid.' AND user = '.$user_id.' AND type = 0')->fetch_assoc();
            if ($bd) {
                $mysqli->query("UPDATE `gym_log` SET `type` = '1' WHERE `id` = '".$bd['id']."'");
                $response['html']  = "Тренеру засчитан проигрыш!";
                $response['error'] = "success";
            } else {
                $response['html'] = "Ошибка!";
                $response['error'] = "error";
            }
        }
    break;

    /* ===================== Турниры (как было) ===================== */
    case 'tournaments_panel':
        $tpl = '<div class="st-panel-content">
                    <p class="st-panel-sub">Выберите турнир для участия:</p>
                    <select id="tournament_select" class="st-select">';
        $res = $mysqli->query('SELECT * FROM tournaments WHERE active = 1 AND status = 0 ORDER BY start_time ASC');
        while ($row = $res->fetch_assoc()) {
            $tpl .= '<option value="'.$row['id'].'">'.$row['name'].' ('.date("d.m H:i", $row['start_time']).')</option>';
        }
        $tpl .= '</select>
                <div class="st-btn" onclick="applyTournament()">Записаться</div>
                <div class="st-hr"></div>
                <div id="tournament-my-history"></div>
                <script>
                    function applyTournament(){
                        var id = $("#tournament_select").val();
                        $.post("/do/gym.php",{type:"tournament_apply",tournament:id},function(resp){
                            if (window.Game && Game.notifications && Game.notifications.main) {
                                Game.notifications.main(resp.html, resp.error==="success"?"success":"error");
                            } else { alert(resp.html); }
                        },"json");
                    }
                    function loadTournamentHistory(){
                        $.post("/do/gym.php",{type:"tournament_history"},function(resp){
                            $("#tournament-my-history").html(resp.html);
                        },"json");
                    }
                    loadTournamentHistory();
                </script>
                </div>';
        $response['html'] = $tpl;
    break;

    case 'tournament_apply':
        $tour_id   = intval($_POST['tournament']);
        $tournament= $mysqli->query('SELECT * FROM tournaments WHERE id = '.$tour_id.' AND active = 1 AND status = 0')->fetch_assoc();
        if (!$tournament) { $response['html']="Турнир не найден или регистрация закрыта!"; $response['error']="error"; break; }
        $already   = $mysqli->query('SELECT * FROM tournaments_users WHERE tournament_id = '.$tour_id.' AND user_id = '.$uid)->fetch_assoc();
        if ($already) { $response['html']="Вы уже записаны на этот турнир!"; $response['error']="error"; }
        else { $mysqli->query('INSERT INTO tournaments_users (tournament_id, user_id, reg_time, result) VALUES ('.$tour_id.', '.$uid.', '.time().', "участвует")');
               $response['html']="Вы успешно записаны на турнир!"; $response['error']="success"; }
    break;

    case 'tournament_history':
        $tpl = "<div class='st-panel-h2' style='margin-bottom:8px;'>Моя история турниров</div>";
        $result = $mysqli->query("SELECT t.name, t.start_time, tu.place, tu.result
            FROM tournaments_users tu
            JOIN tournaments t ON tu.tournament_id = t.id
            WHERE tu.user_id = ".$uid."
            ORDER BY t.start_time DESC LIMIT 20");
        if ($result && $result->num_rows > 0) {
            $tpl .= "<table class='st-table st-table-history'><tr><th>Турнир</th><th>Дата</th><th>Место</th><th>Результат</th></tr>";
            while ($row = $result->fetch_assoc()) {
                $tpl .= "<tr>
                            <td>".$row['name']."</td>
                            <td>".date("d.m.Y H:i",$row['start_time'])."</td>
                            <td>".($row['place'] ? $row['place'] : '-')."</td>
                            <td>".($row['result'] ? $row['result'] : '-')."</td>
                        </tr>";
            }
            $tpl .= "</table>";
        } else {
            $tpl .= "<div class='st-empty'>Вы ещё не участвовали в турнирах.</div>";
        }
        $response['html'] = $tpl;
    break;

    case 'tournament_winners':
        $tpl = "<div class='st-panel-h2' style='margin-bottom:8px;'>Победители турниров</div>";
        $res = $mysqli->query("SELECT t.name, t.start_time, u.login, tu.place
            FROM tournaments_users tu
            JOIN tournaments t ON tu.tournament_id = t.id
            JOIN users u ON u.id = tu.user_id
            WHERE tu.place = 1
            ORDER BY t.start_time DESC LIMIT 10");
        if ($res && $res->num_rows > 0) {
            $tpl .= "<table class='st-table st-table-winners'><tr><th>Турнир</th><th>Дата</th><th>Победитель</th></tr>";
            while ($row = $res->fetch_assoc()) {
                $tpl .= "<tr>
                            <td>".$row['name']."</td>
                            <td>".date("d.m.Y",$row['start_time'])."</td>
                            <td>".$row['login']."</td>
                        </tr>";
            }
            $tpl .= "</table>";
        } else {
            $tpl .= "<div class='st-empty'>Победителей пока нет.</div>";
        }
        $response['html'] = $tpl;
    break;

    case 'tournament_admin_panel':
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) {
            $response['html'] = "<div class='st-empty'>Нет доступа.</div>";
            break;
        }
        $tpl = "<div class='st-panel-content'>";
        $tpl .= "<div class='st-panel-h2'>Добавить турнир</div>
            <form id='add-tournament-form'>
                <input class='st-select' type='text' name='name' placeholder='Название турнира' required><br>
                <input class='st-select' type='datetime-local' name='start_time' required><br>
                <button type='submit' class='st-btn'>Добавить турнир</button>
            </form>
            <div class='st-hr'></div>";

        $res = $mysqli->query('SELECT * FROM tournaments WHERE active <> 2 ORDER BY start_time DESC');
        $tpl .= "<div class='st-panel-h2'>Все турниры</div>
                 <table class='st-table'><tr><th>Турнир</th><th>Старт</th><th>Управление</th></tr>";
        while ($row = $res->fetch_assoc()) {
            $tpl .= "<tr>
                        <td>{$row['name']}</td>
                        <td>".date("d.m.Y H:i", $row['start_time'])."</td>
                        <td><button class='st-btn' onclick='manageTournament({$row['id']})'>Управление</button></td>
                    </tr>";
        }
        $tpl .= "</table></div>
        <script>
        $(document).on('submit', '#add-tournament-form', function(e){
            e.preventDefault();
            $.post('/do/gym.php', {type:'add_tournament', name: this.name.value, start_time: this.start_time.value}, function(resp){
                alert(resp.html); if(resp.error==='success') location.reload();
            },'json');
        });
        window.manageTournament = function(id) {
            $.post('/do/gym.php', {type:'manage_tournament', id: id}, function(resp){
                if ($('.LittleModal').length) $('.LittleModal').html(resp.html);
                else $('<div class=\"LittleModal\" />').html(resp.html).appendTo('body');
            }, 'json');
        }
        </script>";
        $response['html'] = $tpl;
    break;

    case 'add_tournament':
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) {
            $response['html'] = "Нет доступа!"; $response['error'] = "error"; break;
        }
        $name = escapeMe($_POST['name']);
        $start_time = strtotime($_POST['start_time']);
        $mysqli->query("INSERT INTO tournaments (name, start_time, active, status) VALUES ('$name', $start_time, 1, 0)");
        $response['html']  = "Турнир добавлен!";
        $response['error'] = "success";
    break;

    case 'manage_tournament':
        $tournament_id = intval($_POST['id']);
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) { $response['html'] = "Нет доступа!"; break; }
        $tournament = $mysqli->query("SELECT * FROM tournaments WHERE id=$tournament_id")->fetch_assoc();
        if (!$tournament) { $response['html'] = "Турнир не найден!"; break; }

        $status_names   = [0=>'Неактивный',1=>'Активный',2=>'Завершён'];
        $current_status = intval($tournament['active']);
        $activate_btn   = ($current_status==0) ? "<button class='st-btn st-btn-green' id='activate-tournament-btn' data-tid='$tournament_id'>Активировать турнир</button>" : "";
        $deactivate_btn = ($current_status==1) ? "<button class='st-btn st-btn-red'   id='deactivate-tournament-btn' data-tid='$tournament_id'>Деактивировать турнир</button>" : "";
        $finish_btn     = "<button class='st-btn st-btn-red' id='finish-tournament-btn' ".($current_status==2?'disabled':'').">Завершить турнир</button>";

        $tpl = "<div class='st-panel-h2' style='display:flex;align-items:center;justify-content:space-between;'>
            <span>Участники: ".htmlspecialchars($tournament['name'], ENT_QUOTES, 'UTF-8')."</span>
            <button class='st-panel-close' onclick=\"$('.LittleModal').remove()\" title='Закрыть'>&times;</button>
        </div>
        <div class='st-admin-status'>
            <span>Статус турнира: <b>{$status_names[$current_status]}</b></span>
            $activate_btn $deactivate_btn $finish_btn
        </div>";

        $result_options = [
            "участвует" => "Участвует",
            "дисквалифицирован" => "Дисквалифицирован",
            "проиграл" => "Проиграл",
            "победил" => "Победил",
            "другое" => "Другое"
        ];

        $res = $mysqli->query("SELECT tu.id, u.login, tu.place, tu.result FROM tournaments_users tu JOIN users u ON tu.user_id=u.id WHERE tu.tournament_id=$tournament_id");
        $tpl .= "<form id='tournament-users-form'><table class='st-table'><tr><th>Участник</th><th>Место</th><th>Результат</th><th>Награда</th></tr>";
        while ($row = $res->fetch_assoc()) {
            $result_select = "<select name='result[{$row['id']}]' class='st-select st-mini'>";
            $has_custom = true;
            foreach ($result_options as $key => $val) {
                $selected = ($row['result'] === $key) ? "selected" : "";
                if ($row['result'] === $key) $has_custom = false;
                $result_select .= "<option value=\"$key\" $selected>$val</option>";
            }
            if ($has_custom && $row['result'] && !in_array($row['result'], array_keys($result_options))) {
                $result_select .= "<option value=\"{$row['result']}\" selected>{$row['result']}</option>";
            }
            $result_select .= "</select>";

            $tpl .= "<tr>
                <td>{$row['login']}</td>
                <td><input type='number' min='1' max='100' name='place[{$row['id']}]' value='".($row['place']?:'')."'></td>
                <td>$result_select</td>
                <td><button type='button' class='st-btn' onclick='giveMedal({$row['id']})'>Выдать медаль</button></td>
            </tr>";
        }
        $tpl .= "</table>
        <button type='submit' class='st-btn st-btn-green' style='float:right;margin-top:10px;'>Сохранить все изменения</button>
        <div style='clear:both;'></div>
        </form>
        <script>
            $(document).off(\"click\", \"#activate-tournament-btn\").on(\"click\", \"#activate-tournament-btn\", function(){
                var tid = $(this).data(\"tid\");
                setTournamentActive(tid, \"1\");
                setTimeout(function(){ manageTournament(tid); }, 400);
            });
            $(document).off(\"click\", \"#deactivate-tournament-btn\").on(\"click\", \"#deactivate-tournament-btn\", function(){
                var tid = $(this).data(\"tid\");
                setTournamentActive(tid, \"0\");
                setTimeout(function(){ manageTournament(tid); }, 400);
            });
            $(document).off(\"click\", \"#finish-tournament-btn\").on(\"click\", \"#finish-tournament-btn\", function(){
                var tid = '.$tournament_id.';
                if (confirm(\"Вы уверены, что хотите завершить турнир?\")) {
                    $.post('/do/gym.php', {type:'finish_tournament', id: tid}, function(resp){
                        alert(resp.html);
                        if(resp.error==='success') manageTournament(tid);
                    }, 'json');
                }
            });
            $(document).off(\"submit\", \"#tournament-users-form\").on(\"submit\", \"#tournament-users-form\", function(e){
                e.preventDefault();
                var data = $(this).serializeArray();
                data.push({name:'type', value:'save_all_users'});
                data.push({name:'tournament_id', value:'.$tournament_id.'});
                $.post('/do/gym.php', data, function(resp){ alert(resp.html); }, 'json');
            });
            window.giveMedal = function(tu_id) {
                $.post('/do/gym.php', {type:'give_medal', tu_id:tu_id}, function(resp){ alert(resp.html); }, 'json');
            }
            function setTournamentActive(id, value) {
                $.post('/do/gym.php', {type:'set_active', id:id, active:value}, function(resp){
                    if(resp.error !== 'success') alert(resp.html);
                }, 'json');
            }
        </script>";
        $response['html'] = $tpl;
    break;

    case 'set_active':
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) { $response['html']="Нет доступа!"; $response['error']="error"; break; }
        $id = intval($_POST['id']);
        $active = intval($_POST['active']);
        $mysqli->query("UPDATE tournaments SET active=$active WHERE id=$id");
        $response['html']="Статус турнира обновлён!"; $response['error']="success";
    break;

    case 'finish_tournament':
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) { $response['html']="Нет доступа!"; $response['error']="error"; break; }
        $id = intval($_POST['id']);
        $mysqli->query("UPDATE tournaments SET active=2 WHERE id=$id");
        $response['html']="Турнир завершён!"; $response['error']="success";
    break;

    case 'save_all_users':
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) { $response['html']="Нет доступа!"; $response['error']="error"; break; }
        $tournament_id = intval($_POST['tournament_id']);
        if (isset($_POST['place']) && is_array($_POST['place'])) {
            foreach ($_POST['place'] as $tu_id => $place) {
                $tu_id = intval($tu_id);
                $place = intval($place);
                $mysqli->query("UPDATE tournaments_users SET place=$place WHERE id=$tu_id AND tournament_id=$tournament_id");
                if ($place == 1) {
                    $row = $mysqli->query("SELECT user_id FROM tournaments_users WHERE id=$tu_id")->fetch_assoc();
                    if ($row) {
                        $exists = $mysqli->query("SELECT id FROM tournaments_winners WHERE tournament_id=$tournament_id AND user_id={$row['user_id']} AND place=1")->num_rows;
                        if (!$exists) {
                            $mysqli->query("INSERT INTO tournaments_winners (tournament_id, user_id, place, win_time) VALUES ($tournament_id, {$row['user_id']}, 1, UNIX_TIMESTAMP())");
                        }
                    }
                }
            }
        }
        if (isset($_POST['result']) && is_array($_POST['result'])) {
            foreach ($_POST['result'] as $tu_id => $result) {
                $tu_id = intval($tu_id);
                $result = escapeMe($result);
                $mysqli->query("UPDATE tournaments_users SET result='$result' WHERE id=$tu_id AND tournament_id=$tournament_id");
                if ($result === 'победил') {
                    $row = $mysqli->query("SELECT user_id, place FROM tournaments_users WHERE id=$tu_id")->fetch_assoc();
                    if ($row && intval($row['place']) == 1) {
                        $exists = $mysqli->query("SELECT id FROM tournaments_winners WHERE tournament_id=$tournament_id AND user_id={$row['user_id']} AND place=1")->num_rows;
                        if (!$exists) {
                            $mysqli->query("INSERT INTO tournaments_winners (tournament_id, user_id, place, win_time) VALUES ($tournament_id, {$row['user_id']}, 1, UNIX_TIMESTAMP())");
                        }
                    }
                }
            }
        }
        $response['html']="Данные успешно сохранены!"; $response['error']="success";
    break;

    case 'give_medal':
        if (!isset($user['user_group']) || intval($user['user_group']) != 10) { $response['html']="Нет доступа!"; $response['error']="error"; break; }
        $tu_id = intval($_POST['tu_id']);
        $user_row = $mysqli->query("SELECT user_id, place FROM tournaments_users WHERE id=$tu_id")->fetch_assoc();
        if ($user_row) {
            $medal_id = 1000000 + intval($user_row['place']);
            $mysqli->query("INSERT INTO items_users (user, item_id, count, trophy) VALUES ('{$user_row['user_id']}', '{$medal_id}', 1, 1)");
            $response['html']="Медаль выдана!"; $response['error']="success";
        } else {
            $response['html']="Ошибка выдачи медали!"; $response['error']="error";
        }
    break;

    default:
        $response['html']  = 'Unknown error';
        $response['error'] = 'error';
    break;
}

echo json_encode($response);

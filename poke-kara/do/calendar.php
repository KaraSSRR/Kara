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
$type = escapeMe($_POST["category"]);
switch ($type) {
		case 'today':
		    $us = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();

				    $miss = $mysqli->query('SELECT * FROM `user_mission_day` WHERE `user` = '.$_SESSION['id'].' AND `end`= 0')->fetch_assoc();
      $a .= '<div class="CalendarHead">Задания на сегодня</div>
              <div class="CalendarPrewiev">
                Выполняйте задания каждый день и получайте награду.
              </div><div class="TasksList">';

              if($us['mission_day'] == 1){
                  if($miss){
                      $mission = $mysqli->query('SELECT * FROM `user_mission_day` WHERE `user` = '.$_SESSION['id'].' AND `end`= 0');
                      while($mis = $mission->fetch_assoc()){
                          if($mis['id_mission'] == 1){ $text = "Поймайте 20 покемонов";}
                          elseif($mis['id_mission'] == 2){ $text = 'Получите яйцо <span class="intextpoke sp165" onclick="openDex(165)">#165 Ледиба</span>'; }
                          elseif($mis['id_mission'] == 3){ $text = 'Выбейте Острый клюв х1'; }
                          elseif($mis['id_mission'] == 4){ $text = 'Выбейте Набор тренировки х1'; }
                          elseif($mis['id_mission'] == 5){ $text = 'Победите 200 покемонов в pve'; }
                          elseif($mis['id_mission'] == 6){ $text = 'Потратьте 30.000 монет на лечение покемонов'; }
                          elseif($mis['id_mission'] == 7){ $text = 'Найдите любые крышечки х5'; }
                          elseif($mis['id_mission'] == 8){ $text = 'Поймайте <span class="intextpoke sp280" onclick="openDex(280)">#280 Ралтс</span>'; }
                          elseif($mis['id_mission'] == 9){ $text = 'Получите яйцо <span class="intextpoke sp16" onclick="openDex(16)">#016 Пиджи</span>'; }
                          elseif($mis['id_mission'] == 10){ $text = 'Получите яйцо <span class="intextpoke sp92" onclick="openDex(92)">#092 Гастли</span>'; }
                          elseif($mis['id_mission'] == 11){ $text = 'Получите яйцо <span class="intextpoke sp194" onclick="openDex(194)">#194 Вупер</span>'; }
                          elseif($mis['id_mission'] == 12){ $text = 'Выбейте Кварц х10'; }
                          elseif($mis['id_mission'] == 13){ $text = 'Поучаствуйте в 8 pvp боях'; }
                          elseif($mis['id_mission'] == 14){ $text = 'Получите 1.500 опыта'; }
                          elseif($mis['id_mission'] == 15){ $text = 'Поймайте 5 любых покемонов с характером Обычный'; }
                          elseif($mis['id_mission'] == 16){ $text = 'Получите яйцо любого покемона каменного типа'; }
                          elseif($mis['id_mission'] == 17){ $text = 'Используйте в боях не менее 25 предметов'; }
                          elseif($mis['id_mission'] == 18){ $text = 'Используйте на покемонов суммарно не менее 15 конфет'; }
                          elseif($mis['id_mission'] == 19){ $text = 'Используйте любую пилюлю один раз'; }
                          elseif($mis['id_mission'] == 20){ $text = 'Добавьте покемонам 30ev витаминами'; }
                          elseif($mis['id_mission'] == 21){ $text = 'Найдите любые априкорны x5'; }
                          elseif($mis['id_mission'] == 22){ $text = 'Используйте любой эволвер'; }
                          elseif($mis['id_mission'] == 23){ $text = 'Используйте Портативный инкубатор'; }
                          elseif($mis['id_mission'] == 24){ $text = 'Получите яйцо водного покемона'; }
                          elseif($mis['id_mission'] == 25){ $text = 'Получите яйцо травяного покемона'; }
                          elseif($mis['id_mission'] == 26){ $text = 'Используйте 15 критических ударов'; }
                          elseif($mis['id_mission'] == 27){ $text = 'Погуляйте с покемонами 15 раз'; }
                          elseif($mis['id_mission'] == 28){ $text = 'Отправьте в пункт переработки 10 предметов'; }





                          $hint = 'Если прогресс не меняется — обновите календарь. В некоторых действиях есть задержка обновления до ~60 сек.';
                          switch((int)$mis['id_mission']){
                            case 1: $hint = 'Засчитывается только успешная поимка (если покемон пойман и появился в списке). Срыв шара/побег обычно не считается. Если прогресс не меняется — обновите календарь, иногда есть задержка до ~60 сек.'; break;
                            case 2: $hint = 'Нужно получить яйцо #165 Ледиба (чтобы яйцо появилось в вашем списке яиц). Разведение/ивентовые способы — зависит от механики сервера. Если не засчиталось — проверьте, что задание активно сегодня, и обновите календарь (задержка до ~60 сек).'; break;
                            case 3: $hint = 'Засчитывается при зачислении предмета «Острый клюв» в инвентарь. Если предмет выпал, но не был получен/зачислен — прогресс не пойдёт. Обновите календарь (иногда задержка до ~60 сек).'; break;
                            case 4: $hint = 'Засчитывается при получении «Набор тренировки» в инвентарь. Если предмет выпал, но не был зачислен — прогресс не пойдёт. Возможна задержка обновления до ~60 сек.'; break;
                            case 5: $hint = 'Засчитываются победы в PvE (дикие/тренеры) после завершения боя. Поражения/выход из боя обычно не учитываются. Если прогресс стоит — обновите календарь (возможна задержка).'; break;
                            case 6: $hint = 'Считаются траты на лечение покемонов (как правило, именно в механике лечения/покецентре). Покупки/прочие траты обычно не засчитываются. Если не засчитало — проверьте место лечения и обновите календарь.'; break;
                            case 7: $hint = 'Считается получение любых «крышечек» (за счёт находок/наград) при зачислении в инвентарь. Если прогресс не меняется — обновите календарь (задержка до ~60 сек).'; break;
                            case 8: $hint = 'Нужна успешная поимка #280 Ралтс. Эволюция/обмен обычно не считаются. Обновление прогресса может быть с задержкой до ~60 сек.'; break;
                            case 9: $hint = 'Нужно получить яйцо #016 Пиджи (должно появиться в списке яиц). Если не засчиталось — обновите календарь и проверьте, что задание активно.'; break;
                            case 10: $hint = 'Нужно получить яйцо #092 Гастли (должно появиться в списке яиц). Возможна задержка обновления до ~60 сек.'; break;
                            case 11: $hint = 'Нужно получить яйцо #194 Вупер (должно появиться в списке яиц). Если не засчиталось — обновите календарь.'; break;
                            case 12: $hint = 'Считается зачисление «Кварц» в инвентарь суммарно x10. Если добываете пачками — прогресс может обновляться не мгновенно (до ~60 сек).'; break;
                            case 13: $hint = 'Засчитываются завершённые PvP-бои (как правило, после результата). Отмены/выход могут не считаться. Если прогресс стоит — обновите календарь.'; break;
                            case 14: $hint = 'Считается получение опыта суммарно (из боёв/наград). Иногда обновление идёт с задержкой до ~60 сек — просто обновите календарь.'; break;
                            case 15: $hint = 'Считаются ПОЙМАННЫЕ сегодня покемоны с характером «Обычный». Старые покемоны не подходят. Проверьте характер в карточке и обновите календарь при задержке.'; break;
                            case 16: $hint = 'Нужно получить яйцо покемона каменного типа (важно, чтобы яйцо появилось в списке яиц). Если не засчитало — обновите календарь.'; break;
                            case 17: $hint = 'Считаются предметы, использованные прямо в бою (не вне боя). Если предмет «не применился» — прогресс не пойдёт. Возможна задержка обновления.'; break;
                            case 18: $hint = 'Считаются конфеты, успешно применённые на покемонов суммарно (не просто покупка). Если упёрлись в ограничения — может не засчитываться. Обновите календарь.'; break;
                            case 19: $hint = 'Считается успешное применение любой пилюли на покемона. Если действие отклонено (условия/лимиты) — прогресс не пойдёт. Обновите календарь.'; break;
                            case 20: $hint = 'Считаются EV, добавленные витаминами суммарно на 30. Если EV не добавились из‑за лимитов — задание не продвинется. Обновление прогресса может быть с задержкой.'; break;
                            case 21: $hint = 'Считаются найденные априкорны x5 при зачислении в инвентарь. Если прогресс стоит — обновите календарь (задержка до ~60 сек).'; break;
                            case 22: $hint = 'Считается успешное использование эволвера (предмета эволюции). Если эволюция не произошла (условия не выполнены) — прогресс не пойдёт.'; break;
                            case 23: $hint = 'Считается применение «Портативного инкубатора». Если предмет не применился из‑за условий (например, уже активен) — может не засчитаться.'; break;
                            case 24: $hint = 'Нужно получить яйцо водного покемона (яйцо должно появиться в списке). Обновите календарь при задержке.'; break;
                            case 25: $hint = 'Нужно получить яйцо травяного покемона (яйцо должно появиться в списке). Обновите календарь при задержке.'; break;
                            case 26: $hint = 'Считаются критические удары в бою суммарно 15 (обычно после завершения боя). Если прогресс не двигается — обновите календарь.'; break;
                            case 27: $hint = 'Считаются завершённые прогулки с покемонами (механика прогулки должна отработать и выдать результат). Если не засчитало — обновите календарь.'; break;
                            case 28: $hint = 'Считаются предметы, отправленные в пункт переработки (не выброшенные). Если отправка не прошла — прогресс не пойдёт. Возможна задержка обновления.'; break;
                          }

                          if($mis['this_process'] == $mis['end_process'] and $mis['this_process'] >= 1){ $bar = '<button onclick="successMission('.$mis['id_mission'].')">Сдать</button>';}
                          elseif($mis['this_process'] != $mis['end_process'] and $mis['end_process'] > 1){ $pr = $mis['this_process']/$mis['end_process']*100; $bar = '<div class="TaskBar" data-title="'.$mis['this_process'].' / '.$mis['end_process'].'"><div style="width: '.$pr.'%"></div></div>';}
                          else{ $bar = ''; }
                          $a .= '<div class="Task">
                  <div class="TaskLeft">
                    <div class="TaskText">'.$text.' <span class="TaskHint" data-hint="'.htmlspecialchars($hint, ENT_QUOTES).'"><i class="far fa-question-circle"></i></span></div>
                    <div class="TaskProgress">
                      '.$bar.'
                    </div>
                  </div>
                  <div class="TaskImage">
                    <img src="/img/world/items/little/'.$mis['present_id'].'.png"  onclick="issetAll('.$mis['present_id'].')" onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');" ><span>x'.$mis['present_count'].'</span>
                  </div>
                </div>';
                      }






                  }else{
                      $a .= '<h2>У вас не осталось заданий на сегодня!</h2>';
                  }
              }else{
                  $a .= '<h2>Вы не включили получение заданий в настройках!</h2>';
              }
              
              $a .= '</div>';
              
                  $a .= '<div class="CalendarHead">Сражения с боссом</div>
              <div class="CalendarPrewiev">
                Сражайтесь с текущими боссами и получайте награду за победу над ними.
              </div>
              <div class="BossListCalendar">';
                  $boss = $mysqli->query('SELECT * FROM `base_boss` WHERE `user` = "'.$_SESSION['id'].'" AND `prize` != 1 AND `time` > "'.time().'" ');
                  if($boss->num_rows >= 1){
                  while($bs = $boss->fetch_assoc()){
                      if($bs['death'] != 1){
                      $star = '';
                      for($i=0;$i<$bs['type'];$i++){
                          $star .= '<i class="fas fa-star"></i>';
                      }
                      $loc = $mysqli->query('SELECT * FROM `base_location` WHERE `id` = "'.$bs['location'].'" ')->fetch_assoc();
                      $a .= ' <div class="BossBlock">
                                    <div class="imgPokBoss"><img src="/img/pokemons/animation/'.numbPok($bs['basenum']).'.png"></div>
                                    <div class="InfoPokBoss">
                                        <div class="TypeBoss">
                                            '.$star.'
                                        </div>
                                        <div class="LocationBoss">
                                            <i class="fas fa-map-marker-alt"></i> '.$loc['name'].'
                                        </div>
                                        <div class="TimeBoss">
                                            <i class="fas fa-clock"></i> до '.date("H:i",$bs['time']).'
                                        </div>
                                    </div>
                                </div>';
                      }else{
                          $a .= ' <div class="BossBlock">
                                    <div class="imgPokBoss"><img src="/img/pokemons/animation/'.numbPok($bs['basenum']).'.png"></div>
                                    <div class="InfoPokBoss">
                                        <div class="BtnBoss">
                                            <div onclick="BossPrize('.$bs['id'].')">Забрать приз</div>
                                        </div>
                                        <div class="TimeBoss">
                                            <i class="fas fa-clock"></i> до '.date("H:i",$bs['time']).'
                                        </div>
                                    </div>
                                </div>';
                      }
                  }
                  }else{
                      $a .= '<center><h2>~ Боссы отсутствуют ~</h2></center>';
                  }
                $a .= '</div>';  

              
      $response['html'] = $a;
    break;
    //case 'week':
        $server = $mysqli->query('SELECT * FROM `system` WHERE `id` = 1 ')->fetch_assoc();
        if($server['week'] == 1){
                $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_arheolog` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
                if($ivent){
                $mission = $mysqli->query('SELECT * FROM `a_ivent_week_mission` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
                $progress_1 = (5-$mission['breeding'])/5*100;
                $progress_2 = (100-$mission['fight'])/100*100;
                $progress_3 = (15-$mission['catch'])/15*100;
                $progress_4 = (10-$mission['creater'])/10*100;
                $progress_5 = (10-$mission['walk'])/10*100;
                $progress_6 = (5-$mission['evolution'])/5*100;
                $a .= '<div class="CalendarHead">Раскопки Диглетта</div>
                    <div class="CalendarPrewiev">Выполняйте задания раз в 3 часа, зарабатывайте лопаты, откапывайте клад.</div>
                    <div class="dateIvent">23.01-29.01</div>
                    <div class="listArheolog">
                        <p>Выполняя задания раз в 3 часа зарабатывайте лопаты. Тратить лопаты можно на выкапывания ячеек на карте и получать призы. Найдя на карте ключ Вы переходите на следующую карту и все ячейки обновляются. Тратить ключи можно в специальном магазине в Торговом центре Люмиуса.</p>
                        <h3>Обновление заданий каждые 3 часа</h3>
                        <div class="mission_list">
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Разведение</div>
                                    <div class="mission_shovel"><i class="fas fa-shovel"></i> 4</div>
                                    <div class="mission_about">Проведите 5 раз успешное разведение покемонов и получите яйцо</div>
                                    <div class="mission_progress"><div style="width:'.$progress_1.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-swords"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Сражения</div>
                                    <div class="mission_shovel"><i class="fas fa-shovel"></i> 4</div>
                                    <div class="mission_about">Победите в 100 PVE битвах.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_2.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="far fa-spider-web"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Ловля</div>
                                    <div class="mission_shovel"><i class="fas fa-shovel"></i> 4</div>
                                    <div class="mission_about">Поймайте 15 появляющихся на экране покемонов.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_3.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Создание</div>
                                    <div class="mission_shovel"><i class="fas fa-shovel"></i> 4</div>
                                    <div class="mission_about">Создайте предметы в крафте 10 раз.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_4.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-dog-leashed"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Прогулка</div>
                                    <div class="mission_shovel"><i class="fas fa-shovel"></i> 4</div>
                                    <div class="mission_about">Погуляйте с 10 покемонами, повышая их счастье.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_5.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-dice-d12"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Эволюция</div>
                                    <div class="mission_shovel"><i class="fas fa-shovel"></i> 4</div>
                                    <div class="mission_about">Эволюционируйте 5 покемонов по уровню</div>
                                    <div class="mission_progress"><div style="width:'.$progress_6.'%;"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="hr"></div>
                        <div class="btn_cell" onclick="arheolog_open_empty()">Заблокировать</div> <i onclick="issetAll(1,\'week\')" class="fas fa-question-square"></i>
                        <div class="my_shovel"><i class="fas fa-shovel"></i> <span>'.$ivent['shovel'].'</span> <i class="fas fa-plus plus" onclick="buy_shovel()"></i></div>
                        <h2>Карта №'.$ivent['map'].'</h2>
                        <div class="map">';
                $open_slot = explode(',',$ivent['open']);
                $empty_slot = explode(',',$ivent['empty']);
                $block_slot = explode(',',$ivent['block']);  
                for($i=1;$i < 25;$i++){
                    if(!in_array($i,$block_slot) or empty($ivent['block'])){
                        if(in_array($i,$open_slot)){
                            if(!in_array($i,$empty_slot)){
                                if($ivent['key'] != $i){
                                    $bd = $mysqli->query('SELECT * FROM `a_ivent_week_arheolog_prize` WHERE `user` = '.$_SESSION['id'].' AND `slot` = '.$i)->fetch_assoc();
                                    $a .= '<div class="cell cell_'.$i.'"><div class="Item"><div class="blockrait rare"></div><img id="imgItem" src="/img/world/items/little/'.$bd['item'].'.png"  onerror="$(this).attr(\'src\',\'/img/world/items/little/undefined.png\');"></div></div>';
                                }else{
                                    $a .= '<div class="cell cell_'.$i.'"><div class="Item"><img id="imgItem" src="/img/world/items/little/457.png"></div></div>';
                                }
                            }else{
                                $a .= '<div class="cell cell_'.$i.'"><i class="fas fa-empty-set"></i><div class="cell_text">Пусто</div></div>';
                            }
                        }else{
                            $a .= '<div class="cell cell_'.$i.' clickable" onclick="arheolog_open('.$i.');"><i class="fas fa-shovel"></i></div>';
                        }
                    }else{
                        $a .= '<div class="cell cell_'.$i.'"><i class="fas fa-times"></i><div class="cell_text">Блок</div></div>';
                    }
                    
                }
                $a .= '</div>
                    
                    </div>
                    ';
            
            }else{
                $a .= '<div class="birtday_start_page">
			                <i class="fas fa-shovel"></i>
				            <h2>Раскопки Диглетта</h2>
				            Участвуйте в раскопках на карте региона Калос, которая таит в себе кучу предметов.<br><br>
				            <b>Получайте лопаты</b><br>
				            Выполняя задания из списка, лимит которых обновляется раз в 3 часа автоматически.<br><br>
				            <b>Раскапывайте слоты</b><br>
				            Нажимая на карте на одну из еще не исследованных слотов. Вы сожете найти предмет, ключ или ничего!:)<br><br>
				            <b>Магазин ключей</b><br>
				            Тратьте полученные ключи в специальном магазине на покупку яиц покемонов.<br><br>
			                <div class="birtdayactivated" onclick="startIventWeek()">Участвовать</div></div>
			            ';
            }
            
        }elseif($server['week'] == 2){
            $ivent2 = $mysqli->query('SELECT * FROM `a_ivent_week_labirint` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
                if($ivent2){
                $mission2 = $mysqli->query('SELECT * FROM `a_ivent_week_mission` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
                $progress_1 = (5-$mission2['breeding'])/5*100;
                $progress_2 = (100-$mission2['fight'])/100*100;
                $progress_3 = (15-$mission2['catch'])/15*100;
                $progress_4 = (10-$mission2['creater'])/10*100;
                $progress_5 = (10-$mission2['walk'])/10*100;
                $progress_6 = (5-$mission2['evolution'])/5*100;
            $a .= '<div class="CalendarHead">Лабиринт Голлета</div>
                    <div class="CalendarPrewiev">Прохоидте уникальный лабиринт, получая награды.</div>
                    <div class="dateIvent">30.01-05.02</div>
                    <div class="listLabirint">
                        <p>Выполняя задания раз в 3 часа зарабатывайте билеты. Войти в лабиринт вы сможете за 3 билета. Внутри вам необходимо найти золотой ключ, который позволит вам выбраться оттуда. Также вы сможете найти в каждом лабиринте 3 сундука с наградами. Тратить ключи можно в специальном магазине в Торговом центре Люмиуса.</p>
                        <h3>Обновление заданий каждые 3 часа</h3>
                        <div class="mission_list">
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Разведение</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 1</div>
                                    <div class="mission_about">Проведите 5 раз успешное разведение покемонов и получите яйцо</div>
                                    <div class="mission_progress"><div style="width:'.$progress_1.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-swords"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Сражения</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 1</div>
                                    <div class="mission_about">Победите в 100 PVE битвах.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_2.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="far fa-spider-web"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Ловля</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 1</div>
                                    <div class="mission_about">Поймайте 15 появляющихся на экране покемонов.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_3.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Создание</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 1</div>
                                    <div class="mission_about">Создайте предметы в крафте 10 раз.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_4.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-dog-leashed"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Прогулка</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 1</div>
                                    <div class="mission_about">Погуляйте с 10 покемонами, повышая их счастье.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_5.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-dice-d12"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Эволюция</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 1</div>
                                    <div class="mission_about">Эволюционируйте 5 покемонов по уровню</div>
                                    <div class="mission_progress"><div style="width:'.$progress_6.'%;"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="hr"></div>
                        <div class="my_ticket"><i class="fas fa-ticket-alt"></i> <span>'.$ivent2['ticket'].'</span> <i class="fas fa-plus plus" onclick="buy_ticket()"></i></div>';
                if($ivent2['game'] == 0){
                    $a .= '<div class="info_lab"><h2>У вас есть <span class="chest_ivent">'.$ivent2['chest'].'</span> сундуков.</h2><br>
                                <div class="btn_chest" onclick="open_chest()">Открыть сундук</div>
                        <h2>Вы готовы войти в лабиринт?</h2><br>
                        <div class="btn_game_lab" onclick="labirint_game()">Войти</div></div>
                    ';
                }else{
                    $a .= '<div class="labirint">';
                    $games = $mysqli->query('SELECT * FROM `a_ivent_week_labirint_game` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
                    $g = explode(',',$games['slot']);
                        for($x=1;$x < 10;$x++){
                            if($g[$x] == 1){
                                $a .= '<div class="slot_lab slot_lab_'.$x.'" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><div class="point Green-Color"><i class="fas fa-heart"></i> 6</div><img src="/img/pokemons/sprite/normal/113.gif"></div>';
                            }elseif($g[$x] == 2){
                                $a .= '<div class="slot_lab slot_lab_'.$x.'" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><div class="point Red-Color"><i class="fas fa-swords"></i> 4</div><img src="/img/pokemons/sprite/normal/342.gif"></div>';
                            }elseif($g[$x] == 3){
                                $a .= '<div class="slot_lab slot_lab_'.$x.'" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><div class="point Red-Color"><i class="fas fa-swords"></i> 0</div><img src="/img/pokemons/sprite/normal/681.gif"></div>';
                            }elseif($g[$x] == 4){
                                $a .= '<div class="slot_lab slot_lab_'.$x.'" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><div class="point Red-Color"><i class="fas fa-swords"></i> 6</div><img src="/img/pokemons/sprite/normal/681_blade.gif"></div>';
                            }elseif($g[$x] == 5){
                                $a .= '<div class="slot_lab slot_lab_'.$x.'" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><div class="Item"><img id="imgItem" src="/img/world/items/little/457.png"></div></div>';
                            }elseif($g[$x] == 6){
                                $a .= '<div class="slot_lab slot_lab_'.$x.'" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><i class="fas fa-treasure-chest"></i></div>';
                            }else{
                                $a .= '<div class="slot_lab slot_lab_'.$x.' click opas" data="'.$x.'" onclick="labir(this.getAttribute(\'data\'))"><div class="point Red-Color"><i class="fas fa-heart"></i> <span id="hp_my">'.$games['hp'].'</span> / 30</div><img src="/img/pokemons/sprite/normal/622.gif"></div>';
                            }
                            
                        }
                        
                $a .= '    </div>';
                }        
                        
                        
                    
                $a .= '    </div>';
                }else{
                $a .= '<div class="birtday_start_page">
			                <i class="fab fa-squarespace"></i>
				            <h2>Лабиринт Голлета</h2>
				            Одно из самых удивительных мест в мире покемонов, который одновременно и прекрасен и опасен.<br><br>
				            <b>Получайте билеты</b><br>
				            Выполняя задания из списка, лимит которых обновляется раз в 3 часа автоматически.<br><br>
				            <b>Входите в лабиринт</b><br>
				            Нажимая на клетки, окружающие вас по горизонтали и вертикали, переходите на них и ищите выход в виде золотого ключа. Также вы можете найти сундуки и открыть их после прохождения.<br><br>
				            <b>Магазин ключей</b><br>
				            Тратьте полученные ключи в специальном магазине на покупку яиц покемонов.<br><br>
			                <div class="birtdayactivated" onclick="startIventWeek()">Участвовать</div></div>
			            ';
            }
        }elseif($server['week'] == 3){
            $ivent3 = $mysqli->query('SELECT * FROM `a_ivent_week_playhome` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
            if($ivent3){
                $std = $ivent3['point']/500*100;
                            if($std > 100) $std = 100;
                $mission2 = $mysqli->query('SELECT * FROM `a_ivent_week_mission` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
                $progress_1 = (5-$mission2['breeding'])/5*100;
                $progress_2 = (20-$mission2['fight'])/20*100;
                $progress_3 = (15-$mission2['catch'])/15*100;
                $progress_4 = (10-$mission2['creater'])/10*100;
                $progress_5 = (10-$mission2['walk'])/10*100;
                $progress_6 = (5-$mission2['evolution'])/5*100;
                $a .= '<div class="CalendarHead">Игральный дом</div>
                    <div class="CalendarPrewiev">Исполняйте желания покемонов и получайте жетоны радости</div>
                    <div class="dateIvent">13.02-20.02</div>
                    <div class="listLabirint">
                        <p>Выполняя задания раз в 3 часа зарабатывайте билеты. Открывайте игру и заполняйте колбы игрушками, раздавайте желающим их покемонам. Зарабатывайте жетоны радости и обменивайте на призы.</p>
                        <h3>Обновление заданий каждые 3 часа</h3>
                        <div class="mission_list">
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Разведение</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 3</div>
                                    <div class="mission_about">Проведите 5 раз успешное разведение покемонов и получите яйцо</div>
                                    <div class="mission_progress"><div style="width:'.$progress_1.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-swords"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Сражения</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 3</div>
                                    <div class="mission_about">Победите в 20 PVE битвах.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_2.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="far fa-spider-web"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Ловля</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 3</div>
                                    <div class="mission_about">Поймайте 15 появляющихся на экране покемонов. За каждого получаете билет.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_3.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Создание</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 3</div>
                                    <div class="mission_about">Создайте предметы в крафте 10 раз.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_4.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-dog-leashed"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Прогулка</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 3</div>
                                    <div class="mission_about">Погуляйте с 10 покемонами, повышая их счастье.</div>
                                    <div class="mission_progress"><div style="width:'.$progress_5.'%;"></div></div>
                                </div>
                            </div>
                            <div class="mission_block">
                                <div class="mission_icon">
                                    <i class="fas fa-dice-d12"></i>
                                </div>
                                <div class="mission_text">
                                    <div class="mission_name">Эволюция</div>
                                    <div class="mission_shovel"><i class="fas fa-ticket-alt"></i> 3</div>
                                    <div class="mission_about">Эволюционируйте 5 покемонов по уровню</div>
                                    <div class="mission_progress"><div style="width:'.$progress_6.'%;"></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="hr"></div>
                        <div class="my_ticket"><i class="fas fa-ticket-alt"></i> <span>'.$ivent3['ticket'].'</span> <i class="fas fa-plus plus" onclick="buy_ticket()"></i></div>
                        <div class="info_lab"><h2>У вас есть <span class="chest_ivent">'.$ivent3['point'].'</span> жетонов.</h2><br>
                                <div class="btn_chest" onclick="home_trade_jet()">Обменять жетоны</div>
                        <div class="btn_game_lab" onclick="home_game()">Войти в игру</div></div>
                        <div class="divLevels">
                            <div class="divLevelsInner">
                                <div class="divLevel" style="left: 2.5px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">5 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/1.png">
                                                    <div class="infos"><div class="amount">2.000</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel bottom" style="left: 19px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">15 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/30.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel" style="left: 76.2px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">50 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/246.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel bottom" style="left: 109.3px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">70 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/197.png">
                                                    <div class="infos"><div class="amount">2</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel" style="left: 158.5px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">100 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/53.png">
                                                    <div class="infos"><div class="amount">2</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel bottom" style="left: 224.3px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">140 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/256.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel" style="left: 306px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">190 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/255.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel bottom" style="left: 404.4px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">250 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/268.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel" style="left: 568.5px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">350 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/513.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="divLevel bottom" style="left: 812.5px;">
                                    <div class="divLevelInner">
                                        <div class="divLvl">500 жет</div>
                                        <div class="divPrizeSelector">
                                            <div class="divPrize">
                                                <div class="item size-half"><img src="/img/world/items/little/223.png">
                                                    <div class="infos"><div class="amount">1</div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="divLevelArrow" ></div>
                                </div>
                                <div class="progressbar playingprogress"><div style="width: '.$std.'%;"></div></div>
                            </div>
                        </div>
                        ';
            }else{
                $a .= '<div class="birtday_start_page">
			                <i class="fas fa-home-heart"></i>
				            <h2>Игральный дом</h2>
				            Прекрасный дом с покемонами, детьми.<br><br>
				            <b>Получайте билеты</b><br>
				            Выполняя задания из списка, лимит которых обновляется раз в 3 часа автоматически.<br><br>
				            <b>Зарабатывайте жетоны радости</b><br>
				            Заполняйте колбы за билеты и выполняйте мечты покемонов.<br><br>
				            <b>Обмен жетонов</b><br>
				            Обменивайте жетоны, призы которые вы получите вы сможете увидеть сразу перед их получением.<br><br>
			                <div class="birtdayactivated" onclick="startIventWeek()">Участвовать</div></div>
			            ';
            }
        }
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        $response['html'] = $a;
    break;
        // $ib = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = '.$_SESSION['id'].' ')->fetch_assoc();
        // if($ib){
        //     $ser = $mysqli->query('SELECT * FROM `system` WHERE `id` = 1 ')->fetch_assoc();
        //     $v .= '<div class="CalendarHead">Новогодняя Ёлка</div>
        //         <div class="CalendarPrewiev">Выбивайте еловую ветку с диких покемонов чтобы прикрепить ее к центральной новогодней ёлке.</div>
        //         <div class="dateIvent">30.12-07.01</div>
        //         <div class="christmas_tree">
        //             <div class="chance">Ваш шанс на дроп Еловой ветки <b>'.$ib['chance'].'%</b> <div class="btn" onclick="tree_chance()"><i class="fas fa-arrow-alt-up"></i></div></div>
        //             <div class="tree"><img src="/img/ivent/ch_tree.png"></div>
        //             <div class="give_tree" onclick="tree_give()">Сдать ветки</div>
        //             <div class="christmas_block">
        //                 <h2>Серверная</h2>
        //                 <h1>'.number_format($ser['tree'],0,'.','.').'</h1>
        //                 <span>Серверная ёлка для всех игроков.</span>
        //                 <div class="btn" onclick="tree_prize(2)">Получить награду</div> <i onclick="issetAll(1,\'cristmas\')" class="fas fa-question-square"></i>
        //             </div>
        //             <div class="christmas_block">
        //                 <h2>Личная</h2>
        //                 <h1>'.number_format($ib['tree'],0,'.','.').'</h1>
        //                 <span>Личная ёлка для личного прогресса игрока.</span>
        //                 <div class="btn" onclick="tree_prize(1)">Получить награду</div> <i onclick="issetAll(2,\'cristmas\')" class="fas fa-question-square"></i>
        //             </div>
                
        //         </div>
                
                
                
                
                
        //         ';
            
            
            
            
        //         if(item_isset_count(493) != 0){ $func_493 = 'onclick="issetAll(493,\'playing_house\');"'; $activ_493 = ""; }else{ $func_493 = ''; $activ_493 = "no-active"; }
        //         if(item_isset_count(494) != 0){ $func_494 = 'onclick="issetAll(494,\'playing_house\');"'; $activ_494 = ""; }else{ $func_494 = ''; $activ_494 = "no-active"; }
        //         if(item_isset_count(495) != 0){ $func_495 = 'onclick="issetAll(495,\'playing_house\');"'; $activ_495 = ""; }else{ $func_495 = ''; $activ_495 = "no-active"; }
        //         if(item_isset_count(496) != 0){ $func_496 = 'onclick="issetAll(496,\'playing_house\');"'; $activ_496 = ""; }else{ $func_496 = ''; $activ_496 = "no-active"; }
        //         if(item_isset_count(497) != 0){ $func_497 = 'onclick="issetAll(497,\'playing_house\');"'; $activ_497 = ""; }else{ $func_497 = ''; $activ_497 = "no-active"; }
        //         if(item_isset_count(498) != 0){ $func_498 = 'onclick="issetAll(498,\'playing_house\');"'; $activ_498 = ""; }else{ $func_498 = ''; $activ_498 = "no-active"; }
        //         $v .= '<div class="CalendarHead">Игральный дом</div>
        //         <div class="CalendarPrewiev">Выполняйте желания покемонов чтобы получить жетон радости и попасть в топ лучших.</div>
        //     <div class="dateIvent">30.12-13.01</div>
        //     <div class="myPointsHappy">
        //         <img src="/img/world/items/little/500.png"> <span class="wishpoint">'.number_format($ib['playing_point'],0,'.','.').'</span>';
        //         $playingds = $mysqli->query('SELECT * FROM `ivent_birthday` WHERE `user` = "'.$_SESSION['id'].'" ')->fetch_assoc();
        //         if($playingds['playing_point'] != 0){
        //                 $v .= '<div class="bvcd" onclick="playing_give_prize()">Получить приз</div> <div><i onclick="issetAll(3,\'cristmas\')" class="fas fa-question-square"></i></div>';
        //             }
        //     $v .= '</div>
            
        //     <div class="MyBag">
        //         Выберите предмет из своего пакета чтобы отдать его покемону</br>
        //         <div class="wishitem493"><div '.$func_493.' class="'.$activ_493.'"><img src="/img/world/items/little/493.png"></div></div>
        //         <div class="wishitem494"><div '.$func_494.' class="'.$activ_494.'"><img src="/img/world/items/little/494.png"></div></div>
        //         <div class="wishitem495"><div '.$func_495.' class="'.$activ_495.'"><img src="/img/world/items/little/495.png"></div></div>
        //         <div class="wishitem496"><div '.$func_496.' class="'.$activ_496.'"><img src="/img/world/items/little/496.png"></div></div>
        //         <div class="wishitem497"><div '.$func_497.' class="'.$activ_497.'"><img src="/img/world/items/little/497.png"></div></div>
        //         <div class="wishitem498"><div '.$func_498.' class="'.$activ_498.'"><img src="/img/world/items/little/498.png"></div></div>
        //     </div>
            
        //     <div class="PokemonWishList">';
        //     $playing = $mysqli->query('SELECT * FROM `ivent_birthday_playing` WHERE `user` = "'.$_SESSION['id'].'" ');
            
        //     if($playing->num_rows != 0){
                
        //         while($plg = $playing->fetch_assoc()){
        //             $v .= '<div class="pokemonWish wish'.$plg['id'].'">
        //                         <div class="WishPok">';
                                
        //             if($plg['item1'] != 0){ $v .= '<img src="/img/world/items/little/'.$plg['item1'].'.png">'; }
        //             if($plg['item2'] != 0){ $v .= '<img src="/img/world/items/little/'.$plg['item2'].'.png">'; }
        //             if($plg['item3'] != 0){ $v .= '<img src="/img/world/items/little/'.$plg['item3'].'.png">'; }
        //                         $v .= '</div>
        //                         <div class="Image"><img src="https://pokeroute.ru/img/pokemons/sprite/normal/'.$plg['pok'].'.gif"></div>
        //                     </div>';
        //         }
        //     }else{
        //         $v .= '<div class="bvcd" onclick="playing(1)">Получить покемонов</div>';
        //     }
                    
        //     $v .= '</div>';
            
        //         $sb = $mysqli->query('SELECT `snow_ball` FROM `users` WHERE `id` = "'.$_SESSION['id'].'" ')->fetch_assoc();
        //         $snow = explode(',',$sb['snow_ball']);
        //         $ch = round($snow[0]/$snow[1]*100);
        //         if($snow[1] == 0) $ch = 100;
        //     $a .= '<div class="CalendarHead">Снегопад</div>
        //         <div class="CalendarPrewiev">Снег - главный символ зимы и его выпадение означает лишь одно - начало игр в снежки.</div>
        //     <div class="dateIvent">01.01-01.02</div>
        //     <div class="listSnowBall">
        //         <p>Выбивайте снежки с диких покемонов и играйте с другими тренерами в снежки. При удачном броске Вы можете получить <b>рандомную банку витаминов х1</b>, однако если попали в Вас, то есть шанс найти счастливый снежок и получить <b>Красная конфета х1</b>.</p>
        //         <div class="BlockSnow">
        //             <h2>Кинуто снежков</h2>
        //             <i class="fas fa-circle"></i>
        //             <h1>'.$snow[1].'</h1>
        //         </div>
        //         <div class="BlockSnow">
        //             <h2>Точных попаданий</h2>
        //             <i class="fas fa-ball-pile"></i>
        //             <h1>'.$snow[0].'</h1>
        //         </div>
        //         <div class="BlockSnow">
        //             <h2>Процент попаданий</h2>
        //             <i class="fas fa-bullseye-arrow"></i>
        //             <h1>'.$ch.'%</h1>
        //         </div>
        //         <div class="BlockSnow">
        //             <h2>Счастливых снежков</h2>
        //             <i class="fas fa-trophy-alt"></i>
        //             <h1>'.$snow[2].'</h1>
        //         </div>
        //     </div>';
            
            
            
        //     $a .= '<center>
        //         <h2 class="allloc">Новогодние сбежавшие покемоны</h2>
        //         <img src="/img/pokemons/sprite/normal/238.gif">
        //         <img src="/img/pokemons/sprite/normal/333.gif">
        //         <img src="/img/pokemons/sprite/normal/378.gif">
        //         <img src="/img/pokemons/sprite/normal/580.gif">
        //         <img src="/img/pokemons/sprite/normal/872.gif">
        //         <img src="/img/pokemons/sprite/normal/875.gif">
        //     </center>';
        
        // }else{
        //         $a .= '<div class="birtday_start_page">
			     //           <i class="fas fa-tree-christmas"></i>
			                
				    //         <h2>Новый Год!</h2>
				    //         Новогоднее мероприятие, посвященное новому году. Участвуйте в новогодних акциях и получайте призы. Подробнее обо всех акциях узнаете ниже.<br><br>
				    //         <b>Новогодняя Ёлка</b><br>
				    //         Выбивайте еловую ветку с диких покемонов чтобы прикрепить ее к центральной новогодней ёлке.<br><br>
				    //         <b>Дом Санты</b><br>
				    //         Посетите огромный дом Санты и пообщайтесь с его личными Эльфами-помощниками. Разгадайте загадки и получите свой личный новогодний подарок.<br><br>
				    //         <b>Игральный дом</b><br>
				    //         Воплотите в реальность желания маленьких покемонов-детей, подарите им игрушки которые они хотят и получайте Жетоны радости.<br><br>
			     //           <div class="birtdayactivated" onclick="startIvent()">Участвовать</div></div>
			     //       ';
            
            
        // }
        
  }


echo json_encode($response);
?>
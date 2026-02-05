function rand(mi, ma) { return Math.random() * (ma - mi + 1) + mi; }
function irand(mi, ma) { return Math.floor(rand(mi, ma)); }
function isString(obj) { return typeof obj === 'string'; }
function isFunction(obj) {return obj && Object.prototype.toString.call(obj) === '[object Function]'; }
function isObject(obj) { return Object.prototype.toString.call(obj) === '[object Object]'; }
function isArray(obj) { return Object.prototype.toString.call(obj) === '[object Array]'; }

function _pokeNum(number){
    return (number < 10 ? '00'+number : (number < 100 ? '0'+number : number));
}

/**@return {Object}*/
function getElCord(ev, elem, oSet = 7, isCustomEvent = false, parentElemId = 'window_games') {
    // Определение события
    const event = isCustomEvent ? ev : ev || window.event;

    // Получение элемента и его родителя
    const el = typeof elem === 'object' ? elem : document.getElementById(elem);
    const parentEl = document.getElementById(parentElemId);

    if (!event || !el || !parentEl) {
        return {};
    }

    // Настройка отступов
    const offset = Array.isArray(oSet) ? oSet : [oSet, oSet];

    // Размеры элемента
    const box = {
        width: el.offsetWidth,
        height: el.offsetHeight,
    };

    // Размеры и позиция родителя
    const parentRect = parentEl.getBoundingClientRect();
    const parentLeft = parentRect.left + window.pageXOffset;
    const parentTop = parentRect.top + window.pageYOffset;
    const parentBox = {
        width: parentEl.offsetWidth,
        height: parentEl.offsetHeight,
    };

    // Координаты мыши относительно документа
    const mouseX = event.pageX !== undefined
        ? event.pageX
        : (event.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft));
    const mouseY = event.pageY !== undefined
        ? event.pageY
        : (event.clientY + (document.documentElement.scrollTop || document.body.scrollTop));

    // Координаты мыши относительно родителя
    let relMouseX = mouseX - parentLeft;
    let relMouseY = mouseY - parentTop;

    // Вычисление позиций
    let top = relMouseY + offset[0];
    let left = relMouseX + offset[1];

    // Ограничение позиции по высоте
    if (top + box.height > parentBox.height) {
        top = relMouseY - box.height - offset[0] / 2;
    }
    if (top < 0) {
        top = offset[0];
    }

    // Ограничение позиции по ширине
    if (left + box.width > parentBox.width) {
        left = relMouseX - box.width - offset[1] / 2;
    }
    if (left < 0) {
        left = offset[1];
    }

    // Для мобильных устройств
    if (typeof device !== "undefined" && device.mobile && device.mobile()) {
        return {
            top: `${mouseY}px`,
            left: `0px`,
            topNum: mouseY,
            leftNum: 0
        };
    }

    // Для десктопа
    return {
        top: `${top}px`,
        left: `${left}px`,
        topNum: top,
        leftNum: left
    };
}

function smile(el, text){
    var val = el.value;
    if (el.selectionStart !== undefined && el.selectionEnd !== undefined){
        var endIndex = el.selectionEnd,
            startIndex = el.selectionStart;
        el.value = val.slice(0, el.selectionStart) + text + val.slice(endIndex);
        // Перемещаем курсор после вставленного текста
        el.selectionStart = el.selectionEnd = startIndex + text.length;
        if (typeof _focus === "function") _focus(el, (startIndex + text.length));
        return true;
    } else if(document && document.selection && document.selection.createRange){
        el.focus();
        var range = document.selection.createRange();
        if(range.text){
            range.text = text;
        }
        range.select();
        return true;
    }else{
        el.value = val + text;
        el.focus();
        return true;
    }

    // --- ДОБАВЛЕНО ДЛЯ ПК/ДЕСКТОПА: "чат" с id="chat_send_desktop" ---
    if (el.id && el.id === "chat_send_desktop") {
        // Для современных браузеров вручную диспатчим событие input
        if (typeof(Event) === 'function') {
            el.dispatchEvent(new Event('input', {bubbles:true}));
        } else {
            // Для старых IE
            var evt = document.createEvent('Event');
            evt.initEvent('input', true, true);
            el.dispatchEvent(evt);
        }
    }
}

function _focus(el, start, stop){
    if(el){
        try{
            el.focus();
            if(start === undefined || start === false) start = el.value.length;
            if(stop === undefined || stop === false) stop = start;
            if(el.createTextRange){
                var range = el.createTextRange();
                range.collapse(true);
                range.moveEnd('character', stop);
                range.moveStart('character', start);
                range.select();
            }else if(el.setSelectionRange){
                el.setSelectionRange(start, stop);
            }
        }catch(e){
            el.focus();
        }
    }
}

// --- Глобальные переменные для автообновления ---
let locationUpdateTimer = null;
const LOCATION_UPDATE_INTERVAL = 3000; // 3 секунды
let battleUpdateTimer = null;
const BATTLE_UPDATE_INTERVAL = 2000; // 2 секунды
let isLocationUpdateInProgress = false; // Флаг для предотвращения параллельных запросов

// --- Основная функция обновления локации ---
function updateLocation() {
    // Предотвращаем параллельные запросы
    if (isLocationUpdateInProgress) {
        console.log('Location update already in progress, skipping...');
        return;
    }
    
    isLocationUpdateInProgress = true;

    $.ajax({
        url: '/do/updateLocation',
        type: 'POST',
        data: { userAssault: assault },
        dataType: 'json',
        timeout: 10000, // 10 секунд таймаут
        success: function(data) {
            try {
                // Проверяем, что data - это объект
                if (!data || typeof data !== 'object') {
                    console.error('Получен некорректный ответ от сервера:', data);
                    return;
                }

                // --- Генерация локаций ---
                function createLocation(val) {
                    if (!val || !val.name) return null;
                    return $('<div/>', {
                        text: val.name,
                        class: val.event == 1 ? 'active' : '',
                        click: function() { 
                            if (typeof goLocation === 'function') {
                                goLocation(val.id); 
                            }
                        }
                    });
                }

                // --- Генерация NPC ---
                function createNpc(val) {
                    if (!val || !val.name) return null;
                    return $('<div/>', {
                        text: val.name,
                        class: val.event == 1 ? 'active' : '',
                        click: function() { 
                            if (typeof NpcDialog === 'function') {
                                NpcDialog(val.id, false); 
                            }
                        }
                    });
                }

                // --- Локации ---
                const roads = Array.isArray(data.roads) ? data.roads : [];
                const tplLoc = roads.map(createLocation).filter(Boolean);

                // --- NPC ---
                const npcs = Array.isArray(data.npc) ? data.npc : [];
                const tplNpc = npcs.filter(val => val && val.name).map(createNpc).filter(Boolean);

                // --- Проверяем существование элементов перед обновлением ---
                const $locationName = $('.DivMap .Center .Name div');
                if ($locationName.length) {
                    $locationName.empty().text(data.name || '');
                    
                    // --- Контроль клана ---
                    $('.DivMap .Center .Name div .ClanControl').remove();
                    if (data.control) {
                        const clanDiv = $('<div/>', {
                            class: 'ClanControl',
                            html: `<img src="/img/world/clans/emblems/${data.control}.png" alt="Clan Control">`
                        });
                        clanDiv.appendTo($locationName);
                        if (typeof Tipped !== 'undefined' && Tipped.create) {
                            Tipped.create(clanDiv.get(0), data.control_text || '');
                        }
                    }
                    
                    // --- Цвет фона в зависимости от наличия покемона ---
                    $locationName.css('background', data.pokAtLocation == 1 ? '#b34d4d' : '#328d46');
                }

                // --- Описание локации ---
                const $aboutText = $('.ImageLoc .About .Text');
                if ($aboutText.length) {
                    $aboutText.text(data.description || '');
                }

                // --- NPC и PC ---
                const $rightSteps = $('.DivMap .Right .Steps');
                if ($rightSteps.length) {
                    $rightSteps.empty().append(tplNpc);
                    if (data.pc) {
                        $rightSteps.append(data.pc);
                    }
                }

                // --- Локации слева ---
                const $leftSteps = $('.DivMap .Left .Steps');
                if ($leftSteps.length) {
                    $leftSteps.empty().append(tplLoc);
                }

                // --- Картинка локации ---
                const $imageLoc = $('.DivMap .Center .ImageLoc');
                if ($imageLoc.length) {
                    const imgVal = (typeof data.img !== "undefined" && data.img > 0) ? data.img : 'maxresdefault';
                    $imageLoc.css('backgroundImage', `url(/img/world/location/${imgVal}.png)`);
                }

                // --- Удаляем старых NPC/модели ---
                $('.DivNpcBlock, .model').remove();

                // --- Уведомление о защите ---
                if (data.usersDefNotice && typeof updNoticeDef === 'function') {
                    updNoticeDef(data.usersDefNotice);
                }

                // --- Информация о пользователях на локации ---
                if (window.ClassInfo) {
                    if (typeof ClassInfo._upUsrLoc === 'function') {
                        ClassInfo._upUsrLoc(data.usersAtLocation, data.usersAtNotice || null);
                    }
                    // --- Обновление версии только если она изменилась ---
                    if (data.server_ver && typeof ClassInfo._upVer === 'function') {
                        if (typeof window._lastServerVer === 'undefined') {
                            window._lastServerVer = data.server_ver;
                        }
                        if (window._lastServerVer !== data.server_ver) {
                            window._lastServerVer = data.server_ver;
                            ClassInfo._upVer(data.server_ver);
                        }
                    }
                }

                // --- Бой ---
                if (data.battleInfo) {
                    if (!window.ClassBattle) {
                        if (typeof GameBattle === 'function') {
                            ClassBattle = new GameBattle(data.battleInfo);
                            if (typeof onBattleStart === 'function') onBattleStart();
                        }
                    } else {
                        if (typeof ClassBattle._open === 'function') {
                            ClassBattle._open(data.battleInfo);
                        }
                    }
                } else if (window.ClassBattle && typeof onBattleEnd === 'function') {
                    onBattleEnd();
                }

                // --- Торговля ---
                if (data.tradeInfo) {
                    if (!window.ClassTrade) {
                        if (typeof Trade === 'function') {
                            ClassTrade = new Trade(data.tradeInfo);
                        }
                    } else if (typeof ClassTrade._parseData === 'function') {
                        ClassTrade._parseData(data.tradeInfo);
                    }
                } else if (window.ClassTrade && typeof ClassTrade._close === 'function') {
                    ClassTrade._close(false);
                    ClassTrade = null;
                }

            } catch (e) {
                console.error('Ошибка в обработке локации:', e);
                // Показываем уведомление пользователю
                if (typeof showErrorNotification === 'function') {
                    showErrorNotification('Ошибка обработки данных локации!');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Ошибка загрузки локации:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                readyState: xhr.readyState
            });
            
            // Показываем уведомление пользователю только при серьезных ошибках
            if (status !== 'timeout' && xhr.readyState !== 0) {
                if (typeof showErrorNotification === 'function') {
                    showErrorNotification('Ошибка соединения с сервером!');
                }
            }
        },
        complete: function() {
            isLocationUpdateInProgress = false;
            
            // Перезапустить таймер только если он не остановлен вручную
            if (locationUpdateTimer !== null) {
                locationUpdateTimer = setTimeout(updateLocation, LOCATION_UPDATE_INTERVAL);
            }
        }
    });
}

// --- Запуск и остановка циклического обновления локации ---
function startUpdateLocationLoop() {
    if (locationUpdateTimer !== null) {
        clearTimeout(locationUpdateTimer);
    }
    locationUpdateTimer = setTimeout(updateLocation, LOCATION_UPDATE_INTERVAL);
    // Первый вызов сразу:
    updateLocation();
}

function stopUpdateLocationLoop() {
    if (locationUpdateTimer !== null) {
        clearTimeout(locationUpdateTimer);
        locationUpdateTimer = null;
    }
    isLocationUpdateInProgress = false;
}

// --- Для автообновления боя с контролем частоты ---
function autoUpdateBattle() {
    if (window.ClassBattle && typeof ClassBattle._refresh === 'function') {
        try {
            ClassBattle._refresh();
        } catch (e) {
            console.error('Ошибка обновления боя:', e);
        }
    }
    
    if (battleUpdateTimer !== null) {
        battleUpdateTimer = setTimeout(autoUpdateBattle, BATTLE_UPDATE_INTERVAL);
    }
}

function onBattleStart() {
    if (!battleUpdateTimer) {
        battleUpdateTimer = setTimeout(autoUpdateBattle, BATTLE_UPDATE_INTERVAL);
    }
}

function onBattleEnd() {
    if (battleUpdateTimer) {
        clearTimeout(battleUpdateTimer);
        battleUpdateTimer = null;
    }
    
    // Очищаем ClassBattle
    if (window.ClassBattle) {
        window.ClassBattle = null;
    }
}

// --- Функция для показа уведомлений об ошибках (если не существует) ---
if (typeof showErrorNotification !== 'function') {
    function showErrorNotification(message) {
        console.warn('Error notification:', message);
        // Можно добавить показ toast уведомления или другой UI элемент
    }
}

// --- Проверка доступности assault переменной ---
if (typeof assault === 'undefined') {
    var assault = false;
}
/**
 * Trade Users
 *
 * @author Makasimka <000_01@list.ru>
 * @version 1.0
 *
 * @class Trade
 */
var Trade = function(info){
  var _self = this;
  
    // Инициализация помощника модерации (если подключен)
    try{ if(window.ChatProfanity && typeof window.ChatProfanity.init==='function'){ window.ChatProfanity.init(uInfo, {endpoint:'/do/chat'}); } }catch(e){}
var _ref_timer = 0, _data = {}, _confirmed = 1;
  var _element_window_game, _element_window, _element_my, _element_enemy;
  var _element_my_list, _element_enemy_list, _element_button_confirmed;
  var _element_poke_info, _element_warning, _element_status;
  var _is_socket = !!(window.GameSocket && window.GameSocket.isActive && window.GameSocket.socket);
  var onSocketMessage = null;

  // --- Modern styles (once) ---
  (function ensureStyles(){
    if (document.getElementById('trade-style-modern')) return;
    var css = `
#TradeWrap {
  padding: 0 !important;
  background: none !important;
  border: none !important;
  width: 100vw;
  max-width: 100vw;
  min-width: unset;
  box-sizing: border-box;
}

/* --- ПК (основная сетка и стили) --- */
#TradeWrap .Trade {
  display: grid;
  grid-template-columns: 1fr 260px 1fr;
  gap: 14px;
  align-items: stretch;
  height: 361px;
  max-height: 361px;
  min-height: 361px;
  margin: 0 auto;
  width: 100%;
  box-sizing: border-box;
  background: none;
  position: relative;
}

#TradeWrap .Left, #TradeWrap .Right {
  min-width: 0;
  background: #fff;
  border-radius: 15px;
  border: 1.5px solid #ecebfb;
  box-shadow: 0 2px 14px #ecebff44;
  display: flex;
  flex-direction: column;
  padding-bottom: 0;
  margin: 0;
  height: 100%;
  max-height: 361px;
  min-height: 0;
  overflow: hidden;
}

#TradeWrap .Header {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 10px 15px 5px;
  border-bottom: 1px solid #f3f1fe;
  background: none;
  flex: 0 0 auto;
}
#TradeWrap .Header .Avatar {
  width: 36px; height: 36px;
  border-radius: 8px;
  box-shadow: 0 2px 8px #ecebff88;
  background-size: cover;
  background-position: center;
  background-color: #f5f5fb;
}
#TradeWrap .Header .Who {
  font-weight: 800;
  font-size: 1em;
  color: #474c5a;
}
#TradeWrap .Header .Other {
  font-size: 12px;
  color: #b3b7cd;
}

#TradeWrap .Content {
  padding: 6px 4px 0 4px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: none;
  overflow-y: auto;
  overflow-x: hidden;
  min-height: 0;
  height: 0;
  flex: 1 1 auto;
  max-height: none;
}
#TradeWrap .Empty {
  text-align: center;
  color: #b6bdd6;
  padding: 15px 0 12px 0;
  font-size: 13px;
  opacity: .65;
}

#TradeWrap .Step {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 10px 4px 4px;
  margin: 2px 0 1px 0;
  border-radius: 8px;
  background: #f8f8fd;
  border: 1px solid #f1f0f7;
  cursor: pointer;
  min-height: 30px;
  transition: box-shadow .10s, background .12s, transform .09s;
  font-size: 13px;
  box-shadow: 0 1px 2px #ecebff22;
  flex: 0 0 auto;
}
#TradeWrap .Step:hover {
  background: #efeefd;
  box-shadow: 0 2px 8px #ecebff22;
  transform: translateY(-1px) scale(1.01);
}
#TradeWrap .Step img {
  width: 24px;
  height: 24px;
  object-fit: contain;
  border-radius: 6px;
  background: #f2f4fa;
  padding: 1px;
  flex: 0 0 24px;
}
#TradeWrap .Step .meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}
#TradeWrap .Step .title {
  font-size: 12.5px;
  font-weight: 800;
  color: #5b617d;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
#TradeWrap .Step .sub {
  font-size: 10.5px;
  color: #9ba1be;
  margin-top: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
#TradeWrap .Step b.qty {
  margin-left: auto;
  font-weight: 900;
  color: #7b6cff;
  font-size: 12px;
}

#TradeWrap .Mid {
  background: #fff4fa;
  border: 1.5px solid #ffd6ea;
  border-radius: 11px;
  min-width: 0;
  padding: 7px 4px 5px 4px;
  box-shadow: 0 2px 18px #fae3f366;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 7px;
  margin: 0;
  height: 100%;
  max-height: 361px;
  min-height: 0;
  justify-content: flex-start;
}
#TradeWrap .Mid .Info {
  border-radius: 7px;
  padding: 4px 2px 4px 2px;
  background: #ffe1e5;
  border: 1px dashed #ffa3ba;
  color: #e15077;
  font-weight: 800;
  font-size: 13px;
  text-align: center;
  margin-bottom: 3px;
  letter-spacing: .01em;
  min-height: unset;
}
#TradeWrap .Mid .Info span {
  font-size: 14px;
  font-family: Nunito, Inter, Arial, sans-serif;
  font-weight: 900;
  display: block;
  margin-bottom: 1px;
  color: #ea4a64;
}
#TradeWrap .Mid .btn {
  display: block;
  width: 100%;
  padding: 8px 0;
  border-radius: 8px;
  text-align: center;
  font-weight: 900;
  font-size: 14px;
  letter-spacing: .01em;
  color: #fff;
  border: none;
  cursor: pointer;
  margin-bottom: 5px;
  box-shadow: 0 2px 5px #dac7ff33;
  background: linear-gradient(90deg, #a78bfa 10%, #7b6cff 100%);
  transition: filter .13s, transform .1s;
}
#TradeWrap .Mid .btn:active { filter: brightness(.97); }
#TradeWrap .Mid .btn.Cancel {
  background: linear-gradient(90deg, #fb7185 10%, #f472b6 100%);
  margin-top: 0;
}
#TradeWrap .Mid .Status {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 2px 0 0 0;
  border-radius: 6px;
  font-size: 11.5px;
  background: none;
}
#TradeWrap .pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 8px;
  border-radius: 999px;
  background: linear-gradient(90deg, #fff5f5 70%, #f6effc 100%);
  font-weight: 800;
  color: #e15077;
  box-shadow: 0 2px 8px #f3d6e133;
  font-size: 11.5px;
}
#TradeWrap .pill.ok {
  background: linear-gradient(90deg, #e0ffe9 60%, #c4f7cf 100%);
  color: #2dad50;
  border: 1px solid #dff7e7;
}
#TradeWrap .pill.no {
  background: linear-gradient(90deg, #fff0f4 60%, #f7c6d7 100%);
  color: #d64545;
  border: 1px solid #f7c6d7;
}
#TradeWrap .pill b {
  color: #7b6cff;
  font-weight: 900;
  margin-left: 6px;
}

/* popup and tooltip */
#TradeWrap .window.pokeInfo {
  position: absolute;
  z-index: 100000;
  background: #fff;
  color: #1a1a2f;
  border-radius: 14px;
  min-width: 320px;
  max-width: 540px;
  border: 1.5px solid #e3e8fa;
  box-shadow: 0 14px 30px #e3e8fa55;
  padding: 8px;
}
.tooltip {
  position: absolute;
  display: none;
  z-index: 90000;
  max-width: 340px;
  background: #fff;
  color: #323355;
  border: 1.5px solid #e3e8fa;
  border-radius: 10px;
  padding: 8px;
  box-shadow: 0 10px 28px #c0b6e1b0;
}
.tooltip .Buttons {
  margin-top: 7px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.tooltip .Buttons > div {
  padding: 7px 10px;
  border-radius: 8px;
  background: linear-gradient(90deg, #7b6cff 60%, #a78bfa 100%);
  cursor: pointer;
  color: #fff;
  font-weight: 700;
}

/* --- Мобильная адаптация: скролл трейда --- */
@media (max-width: 700px) {
  html, body {
    height: 100%;
    overflow: hidden;
    /* Запрет двойного скролла */
  }
  #TradeWrap {
    min-height: 100dvh;
    height: 100dvh;
    max-height: 100dvh;
    overflow: hidden;
    position: fixed;
    left: 0; top: 0; right: 0; bottom: 0;
    z-index: 1010;
    display: flex;
    flex-direction: column;
  }
  #TradeWrap .Trade {
    /* главный вертикальный скролл только внутри трейда! */
    height: 100dvh;
    min-height: 0;
    max-height: 100dvh;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    gap: 8px;
    width: 100vw;
    margin: 0;
    background: none;
    box-sizing: border-box;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
  }
  #TradeWrap .Left, #TradeWrap .Right, #TradeWrap .Mid {
    border-radius: 8px;
    box-shadow: none;
    padding: 0;
    margin: 0;
    min-width: 0;
    max-width: none;
    max-height: unset;
    height: auto;
    overflow: visible;
    flex: 0 0 auto;
  }
  #TradeWrap .Header {
    padding: 8px 6px 5px;
  }
  #TradeWrap .Content {
    padding: 7px 4px 0 4px;
    gap: 12px;
    min-height: 60px;
    max-height: 220px;
    height: 160px;
    background: none;
    overflow-y: auto;
    overflow-x: hidden;
    flex: none;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  #TradeWrap .Step {
    min-height: 44px;
    font-size: 15.5px;
    padding: 5px 9px 5px 6px;
    gap: 16px;
    border-radius: 13px;
    background: #f4f3fd;
    box-shadow: 0 2px 10px #cfcfed1d;
    align-items: center;
  }
  #TradeWrap .Step img {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    flex: 0 0 38px;
    background: #f7f7ff;
    padding: 2px;
  }
  #TradeWrap .Step .meta {
    min-width: 0;
  }
  #TradeWrap .Step .title {
    font-size: 15.5px;
    font-weight: 900;
    color: #524e75;
  }
  #TradeWrap .Step .sub {
    font-size: 13px;
    color: #a3a1be;
    margin-top: 2px;
  }
  #TradeWrap .Step b.qty {
    font-size: 16px;
    color: #7b6cff;
    font-weight: 900;
    margin-left: auto;
  }
  #TradeWrap .Mid {
    padding: 5px 2px 2px 2px;
    gap: 6px;
  }
  #TradeWrap .Mid .Info {
    font-size: 13px;
    padding: 4px 1px;
  }
  #TradeWrap .Mid .Info span {
    font-size: 14px;
    margin-bottom: 0;
  }
  #TradeWrap .Mid .btn {
    font-size: 14px;
    padding: 10px 0;
    border-radius: 10px;
  }
  #TradeWrap .Mid .Status {
    font-size: 12px;
    gap: 3px;
    padding: 2px 0 0 0;
  }
  #TradeWrap .pill {
    font-size: 12px;
    padding: 6px 7px;
    border-radius: 999px;
  }
}

/* Для очень маленьких экранов - не даём .Content схлопнуться */
@media (max-width: 400px) {
  #TradeWrap .Content {
    max-height: 140px;
    height: 90px;
  }
  #TradeWrap .Step img {
    width: 28px;
    height: 28px;
    flex: 0 0 28px;
  }
}
`;
    var s = document.createElement('style');
    s.id = 'trade-style-modern';
    s.type = 'text/css';
    s.appendChild(document.createTextNode(css));
    document.head.appendChild(s);
  })();

  // ---------- helpers ----------
  this._pokeNum = function(n){ return (n<10?'00'+n:(n<100?'0'+n:n)); };
  this._pokeRep = function(t){ return (t=='normal' ? 'normal' : 'shine'); };

  function renderStatusBox(data){
    if(!_element_status) return;
    var myOk = (data['myConfirmed'] > 0), enOk = (data['enemyConfirmed'] > 0);
    var totalMine = 0, totalEn = 0;
    if (data['myObj'])    $.each(data['myObj'],    function(_, v){ totalMine += (parseInt(v.count)||0); });
    if (data['enemyObj']) $.each(data['enemyObj'], function(_, v){ totalEn   += (parseInt(v.count)||0); });
    _element_status.empty().append(
      $('<div/>',{class:'pill '+(myOk?'ok':'no'),html:(myOk?'✔ Вы согласовали':'⏳ Вы не согласовали')}),
      $('<div/>',{class:'pill '+(enOk?'ok':'no'),html:(enOk?'✔ Партнёр согласовал':'⏳ Партнёр не согласовал')}),
      $('<div/>',{class:'pill',html:'Ваших предметов: <b>'+totalMine+'</b>'}),
      $('<div/>',{class:'pill',html:'Предметов партнёра: <b>'+totalEn+'</b>'})
    );
  }

  // ---------- API ----------
  this._action = function(data, suc, err, cpl){
    if(!data) return;
    if (_is_socket) {
      try { window.GameSocket.emit('trade_action', data); } catch(e){}
      if (typeof suc === 'function') suc.call(_self, {});
      if (typeof cpl === 'function') cpl.call(_self);
    } else if (typeof ClassInfo !== 'undefined') {
      data = $.extend({'type':'trade'}, data);
      ClassInfo._action(data, function(info){
        if(info && info['tradeInfo']) _self._parseData(info['tradeInfo']);
        if(info['tradeInfo'] && info['tradeInfo']['error'] == 1){
          Game.notifications.main(info['tradeInfo']['text'], 'error');
        }
        if (typeof suc === 'function') suc.call(_self, info);
      }, err, cpl);
    }
  };

  // основной метод + алиас для старых вызовов
  this._addObject = function(object_type, object_id, count, suc, cpl){
    if(!object_type || !object_id || !count || object_id <= 0 || count <= 0) return;

    // ВИЗУАЛ: оптимистичное добавление сразу (без ожидания сервера)
    if(_element_my_list){
      var ph = $('<div/>',{
        'class':'Step pending pulse-add',
        'html':'<img src="/img/world/items/little/'+(object_type==='egg'?151:object_id)+'.png">'+
               '<div class="meta"><div class="title">Добавление...</div><div class="sub">добавлено</div></div>'+
               '<b class="qty">x'+count+'</b>'
      });
      _element_my_list.prepend(ph);
      setTimeout(function(){ ph.remove(); }, 1500);
    }

    _self._action({
      'addObject': object_type,
      'objectID': parseInt(object_id),
      'objectCount': parseInt(count)
    }, suc, null, cpl);
  };
  this._addobject = function(){ return _self._addObject.apply(_self, arguments); };

  this._refresh = function (started) {
    if (!_element_window) return;
    if (_ref_timer) clearTimeout(_ref_timer);
    var go = function(){ _ref_timer = setTimeout(function(){ _self._refresh(); }, 3000); };
    if (!started) _self._action({ type:'view' }, null, null, go);
    else go();
  };

  this._parseData = function(data){
    if(!data) return;
    if(typeof data === "string"){ _data = JSON.parse(data); } else { _data = $.extend(_data, data); }
    _self._update();
  };

  // ---------- UI update ----------
  this._update = function(data){
    data = data ? data : _data;
    if(data['trades']){ _self._close(); return; }
    if(!data['my']) return;

    if(!_element_window_game){ _element_window_game = $('.DivWorld'); }
    if(!_element_window){
      _element_window_game.find('.DivMap').hide();
      _element_window = $('<div />', { 'id':'TradeWrap','class':'DivMap' }).appendTo(_element_window_game);
    }

    if(_element_window && !_element_my && !_element_enemy){
      // left
      _element_my = $('<div />', {'class':'Left'});
      var myHeader = $('<div/>',{class:'Header'}).append(
        $('<div/>',{class:'Avatar',css:{backgroundImage:'url(/img/avatars/mini/'+data['my']['id']+'.png)'}}),
        $('<div/>').append(
          $('<div/>',{class:'Who',text:data['my']['login']}),
          $('<div/>',{class:'Other',text:data['my']['rang']})
        )
      );
      _element_my_list = $('<div />', {'class':'Content'});
      _element_my.append(myHeader,_element_my_list);

      // right
      _element_enemy = $('<div />', {'class':'Right'});
      var enHeader = $('<div/>',{class:'Header'}).append(
        $('<div/>',{class:'Avatar',css:{backgroundImage:'url(/img/avatars/mini/'+data['enemy']['id']+'.png)'}}),
        $('<div/>').append(
          $('<div/>',{class:'Who',text:data['enemy']['login']}),
          $('<div/>',{class:'Other',text:data['enemy']['rang']})
        )
      );
      _element_enemy_list = $('<div />', {'class':'Content'});
      _element_enemy.append(enHeader,_element_enemy_list);

      // mid
      _element_warning = $('<div />', {
        'class': 'Info',
        html: '<span>Внимание!</span> Перед обменом убедитесь, что партнёр добавил всё необходимое.'
      });
      _element_button_confirmed = $('<button />', {
        'class':'btn Go',
        'html':'Согласен'
      }).click(function(){
        _self._action({'confirmed': parseInt(_confirmed)}, function(){
          if(_element_button_confirmed){
            _element_button_confirmed.html((_confirmed > 0 ? 'Не согласен' : 'Согласен'));
            _confirmed = (_confirmed > 0 ? 0 : 1);
          }
        });
      });
      _element_status = $('<div/>',{class:'Status'});
      var mid = $('<div/>',{class:'Mid'}).append(
        _element_warning,
        _element_button_confirmed,
        $('<button/>',{class:'btn Cancel',html:'Отменить'}).click(function(){ _self._close(true); }),
        _element_status
      );

      _element_window.append($('<div/>',{class:'Trade'}).append(_element_my, mid, _element_enemy));
    }

    // перерисовываем списки целиком (чтобы точно видеть добавления)
    if(_element_my_list){
      var a = [];
      if(data['myObj']) $.each(data['myObj'], function(_, v){ a.push(_self._parseObjects(v)); });
      _element_my_list.empty().append(a.length ? a : $('<div class="Empty">Пока пусто</div>'));
    }
    if(_element_enemy_list){
      var b = [];
      if(data['enemyObj']) $.each(data['enemyObj'], function(_, v){ b.push(_self._parseObjects(v)); });
      _element_enemy_list.empty().append(b.length ? b : $('<div class="Empty">Пока пусто</div>'));
    }

    // confirmed flags
    if(data['myConfirmed'] > 0){ _element_my.addClass('confirmed'); _element_button_confirmed.html('Не согласен'); _confirmed = 0; }
    else { _element_my.removeClass('confirmed'); _element_button_confirmed.html('Согласен'); _confirmed = 1; }
    if(data['enemyConfirmed'] > 0){ _element_enemy.addClass('confirmed'); }
    else { _element_enemy.removeClass('confirmed'); }
    renderStatusBox(data);
  };

  // ---------- render objects ----------
  this._parseObjects = function(info){
    if(!info || !info['type']) return '';
    if(info['type'] == 'item'){
      var sub = '';
      if (info['str_exp']) sub += info['str_exp']+' ';
      if (info['dop_exp']) sub += info['dop_exp'];
      return $('<div />', {
        'class':'Step',
        'html':'<img src="/img/world/items/little/'+info['number']+'.png">'+
               '<div class="meta"><div class="title">'+(info['name'] || '...')+'</div>'+
               (sub?'<div class="sub">'+sub+'</div>':'')+'</div>'+
               '<b class="qty">x'+info['count']+'</b>',
        'click': function(){ _self._itemFocus(this, info); }
      });
    }
    if(info['type'] == 'egg'){
      return $('<div />', {
        'class':'Step',
        'html':'<img src="/img/world/items/little/151.png">'+
               '<div class="meta"><div class="title">'+(info['name']||'Яйцо')+'</div></div>',
        'click': function(){ _self._itemFocus(this, info); }
      });
    }
    if(info['type'] == 'poke'){
      return $('<div />', {
        'class':'Step '+_self._pokeRep(info['poke_type'])+'-color',
        'html':'<img src="/img/pokemons/animation/'+info['number']+'.png">'+
               '<div class="meta"><div class="title">#'+_self._pokeNum(info['number'])+' '+info['name']+'</div>'+
               '<div class="sub">'+(info['poke_type']==='shine'?'Shiny • ':'')+(info['lvl']?'LVL '+info['lvl']:'')+'</div></div>',
        'click': function(e){ _self._pokeFocus(e, (info['id'] || 0), info); }
      });
    }
    return '';
  };

  // ---------- pokemon popup ----------
  this._pokeFocus = function(e, id, inf){
  if(!id || !_data['pokeInfoList']) return;
  var info = _data['pokeInfoList']['p'+id];
  if(!info) return;

  // контейнер попапа
  if(!_element_poke_info){ _element_poke_info = $('<div />', { 'class':'window pokeInfo' }).appendTo('#window_games'); }

  // одноразовые стили под «чистые шаги», кнопку статистики и мини-панель
  if(!document.getElementById('pk-card-css')){
    $('<style id="pk-card-css">\
      .pokeInfo .Info{position:relative}\
      .pokeInfo .Info .Step.pure{display:flex;gap:8px;align-items:center;background:transparent;border:0;padding:0;margin:6px 0 0 0}\
      .pokeInfo .Info .Step.pure .Label{font-weight:800;color:#2f4374}\
      .pokeInfo .Info .Step.pure .Other{font-weight:900;color:#1b2b4f}\
      .pokeInfo .Info .AbilityLink{border:0;background:transparent;color:#f59e0b;font-weight:900;cursor:pointer;padding:0}\
      .pokeInfo .Info .AbilityLink:hover{text-decoration:underline}\
      .pokeInfo .pkStatBtn{position:absolute;right:8px;top:6px;width:30px;height:30px;display:grid;place-items:center;border:1px solid #e6eafe;border-radius:8px;background:#fff;color:#5c6b8a;cursor:pointer}\
      .pokeInfo .pkMini{position:absolute;right:8px;top:42px;min-width:260px;max-width:320px;padding:10px 12px;border:1px solid #252b3a;border-radius:12px;background:#11141b;color:#eef3ff;box-shadow:0 16px 34px rgba(0,0,0,.35);display:none;z-index:60}\
      .pokeInfo .pkMini .row{display:flex;justify-content:space-between;padding:6px 2px;border-bottom:1px dashed rgba(255,255,255,.08)}\
      .pokeInfo .pkMini .row:last-child{border:0}.pokeInfo .pkMini .k{opacity:.78}\
    </style>').appendTo('head');
  }

  // подготовка данных
  var ev   = (info['evcounts']||'').split(','),
      stat = (info['stats']||'').split(','),
      gen  = (info['gen']||'').split(','),
      exp  = (info['lvl'] != 100 ? (info['exp1']+' / '+info['exp2']) : 'full'),
      nS   = (info['type'] == 'normal' ? '' : info['type']),
      gender = (info['gender'] == 'Мальчик' ? 'mars' : (info['gender'] == 'Девочка' ? 'venus' : 'genderless')),
      tr_b = (info['tren'] == 6 ? 'crown' : 'angle-double-up'),
      tr_n = (info['tren'] ? ('tr'+info['tren']) : ''),
      form = (info['form'] != "0" ? "_"+info['form'] : "");

  // сборка HTML
  _element_poke_info
    .empty()
    .append(
      '<div class="Close"><i class="fas fa-times"></i></div>'+
      '<div class="Pokemons">'+
        '<div class="Info">'+
          '<div class="Left">'+
            '<div class="PokemonBox">'+
              (info['tren'] >= 1 ? '<div class="Modif"><i class="trening fas fa-'+tr_b+' '+tr_n+'"></i></div>' : '')+
              '<div class="Image"><img src="/img/pokemons/sprsite/'+info['type']+'/'+_self._pokeNum(info['basenum'])+form+'.gif" alt=""></div>'+
              '<div class="Lvl">'+info['lvl']+'</div>'+
              '<div class="Unik '+info['type']+'-color">'+(nS||'')+'</div>'+
              '<div class="Name '+info['type']+'-color">'+
                '<div class="Text">#'+_self._pokeNum(info['basenum'])+' '+info['name']+'</div>'+
                '<div class="Sex '+(info['sparka'] == 0 ? '' : 'spar')+'"><i class="fas fa-'+gender+'"></i></div>'+
              '</div>'+
              '<div class="Bars">'+
                '<div class="Bar hp_proggresbar" data-title="HP: '+info['hp']+' / '+(stat[0]||0)+'"><div class="HpBar" style="width:'+((stat[0]>0)?(info['hp']/stat[0]*100):0)+'%;"></div></div>'+
                '<div class="Bar exp_progressbar" data-title="Опыт: '+exp+'"><div class="ExpBar" style="width:'+((info['exp2']?(info['exp1']/info['exp2']):0)*100)+'%;"></div></div>'+
              '</div>'+
            '</div>'+
            '<div class="MoveBox">'+((info['atk1']||'') + (info['atk2']||'') + (info['atk3']||'') + (info['atk4']||''))+'</div>'+
          '</div>'+
          '<div class="Right">'+
            '<div class="Id Id-trade'+(info['trade'] == 'true' ? 'Yes' : 'No')+'">id'+info['id']+'</div>'+
            '<div class="Info">'+
              // кнопка статистики (даём ей класс staticPok — останутся твои Tipped-настройки сладостей)
              '<button type="button" class="pkStatBtn staticPok" aria-label="Статистика"><i class="fal fa-chart-line"></i></button>'+
              '<div class="pkMini"></div>'+

              // характер (без иконки i)
              (info['character'] ? '<div class="Step pure"><div class="Label">Характер:</div><div class="Other">'+info['character']+'</div></div>' : '')+

              // способность (кликабельная, берём HTML как есть, чтобы сохранить onclick/ссылки)
              '<div class="Step pure"><div class="Label">Способность:</div><div class="Other">'+(info['abl']||'—')+'</div></div>'+

              // генокод
              '<div class="Step pure"><div class="Label">Генокод:</div><div class="Other">h'+(gen[0]||0)+'a'+(gen[1]||0)+'d'+(gen[2]||0)+'s'+(gen[3]||0)+'sa'+(gen[4]||0)+'sd'+(gen[5]||0)+'</div></div>'+

              // группа привлекательности
              '<div class="Step pure"><div class="Label">Группа привлекательности:</div><div class="Other">'+(info['sparkaNumber']||'—')+'</div></div>'+

              // разведение
              '<div class="Step pure"><div class="Label">Разведение:</div><div class="Other '+(info['sparka']==0?'Green-Color':'Red-Color')+'">'+(info['sparka']==0?'доступно':'недоступно')+'</div></div>'+

              // свободные EV
              '<div class="Step pure"><div class="Label">Свободные EV:</div><div class="Other">'+(info['ev']||'0')+'</div></div>'+

              // пойман — переносим в статистику, не показываем в основной части
            '</div>'+
          '</div>'+
        '</div>'+
      '</div>'
    );

  // статистика в мини-панели
  (function(){
    var $mini = _element_poke_info.find('.pkMini');
    var caught = (info['birthday'] ? (info['birthday']['date']+'г. тренером '+info['birthday']['user']) : 'Неизвестно');
    var vitamins = (typeof info['vitamines'] !== 'undefined' ? (info['vitamines']+' / 100') : '—');
    var st0 = +info['st0']||0, st2 = +info['st2']||0, st3 = +info['st3']||0;
    var tasty = [info['hidden'], info['st8']].filter(Boolean).join(' ').trim();

    $mini.html([
      '<div class="row"><div class="k">Пойман</div><div>'+caught+'</div></div>',
      '<div class="row"><div class="k">Потенциал</div><div>'+(info['potential']||'—')+'</div></div>',
      '<div class="row"><div class="k">Витамины</div><div>'+vitamins+'</div></div>',
      '<div class="row"><div class="k">Разведение</div><div>'+(info['sparka']==0?'доступно':'недоступно')+'</div></div>',
      '<div class="row"><div class="k">Шоколадная конфета</div><div>'+(st0?'использована':'не использована')+'</div></div>',
      '<div class="row"><div class="k">Сладкий кекс</div><div>'+(st2?'использован':'не использован')+'</div></div>',
      '<div class="row"><div class="k">Корень априкорна</div><div>'+(st3?'использован':'не использован')+'</div></div>',
      (tasty?'<div class="row"><div class="k">Тренировки</div><div>'+tasty+'</div></div>':'')
    ].join(''));

    var $btn = _element_poke_info.find('.pkStatBtn');
    $btn.off('click').on('click', function(ev){ ev.stopPropagation(); $mini.toggle(); });
    $(document).off('click.pokeMiniHide').on('click.pokeMiniHide', function(){ $mini.hide(); });
  })();

  // тултипы HP/EXP (оставляем)
  try{
    if (window.Tipped && typeof Tipped.create === 'function') {
      Tipped.create(_element_poke_info.find('.hp_proggresbar').get(0));
      Tipped.create(_element_poke_info.find('.exp_progressbar').get(0));
      // сладости/витамины — по старой схеме вешаем на кнопку статистики (класс staticPok)
      Tipped.create(_element_poke_info.find('.pkStatBtn.staticPok').get(0),
        'Шоколадная конфета <span class="'+(+info['st0']===0?'Green':'Red')+'-Color">'+(+info['st0']===0?'не использована':'использована')+'</span><br>'+
        'Сладкий кекс <span class="'+(+info['st2']===0?'Green':'Red')+'-Color">'+(+info['st2']===0?'не использован':'использован')+'</span><br>'+
        'Корень априкорна <span class="'+(+info['st3']===0?'Green':'Red')+'-Color">'+(+info['st3']===0?'не использован':'использован')+'</span><br>'+
        (info['hidden']||'')+' '+(info['st8']||'')
      );
      // айди/торговля
      var idNode = _element_poke_info.find('.Id').get(0);
      if(idNode) Tipped.create(idNode, ' '+(info['trade']=="false" ? 'Покемон приручен' : 'Покемон не приручен')+' ');
    }
  }catch(err){}

  // позиционирование попапа у курсора
  try{
    if (typeof getElCord === 'function') {
      _element_poke_info.css(getElCord(e, _element_poke_info, [-25, 6]));
    }
  }catch(err){}
  _element_poke_info.css('display','block');

  // закрытие
  _element_poke_info.find('.Close').off('click').on('click', function(){
    if(_element_poke_info){ _element_poke_info.remove(); _element_poke_info = null; }
    $('body').off('click.pokeInfoTrade').off('click.pokeMiniHide');
  });
  setTimeout(function(){
    $('body').off('click.pokeInfoTrade').on('click.pokeInfoTrade', function(){
      if(_element_poke_info){ _element_poke_info.remove(); _element_poke_info = null; }
      $('body').off('click.pokeInfoTrade').off('click.pokeMiniHide');
    });
  },0);

  if (e && e.stopImmediatePropagation) e.stopImmediatePropagation();
};


  // ---------- item tooltip ----------
  this._itemFocus = function(elm, info){
    if(info['type'] == 'poke'){ return _self._pokeFocus(window.event, (info['id'] || 0), info); }
    var tlp = $('.tooltip'); if (tlp.length === 0){ tlp = $('<div />', { 'class': 'tooltip' }).appendTo('body'); }
    var element = $(elm), offset = element.offset(), left = Math.round(offset.left+10), top  = Math.round(offset.top+50);
    tlp.css({ "left": left+'px', "top": top+'px' });
    var tpl = [];
    tpl.push('<div class="Name">'+(info['name'] || '...')+' <b>x'+info['count']+'</b></div>');
    tpl.push('<div class="Image"><img id="imgItem" style="display:inline-block;" src="/img/world/items/big/'+(info['type']=='egg'?151:info['number'])+'.png"></div>');
    if(info['about']) tpl.push('<div class="About">'+info['about']+'</div>');
    var btns = [];
    if(info['id']){
      btns.push($('<div/>',{html:'Убрать','click':function(){
        _self._action({ 'removeObject': info['type'],'objectID': parseInt(info['id']) }, function(){
          if(info['type'] != 'poke' && $('#modal').find('.ves-item').length){ Game.mdl.mf('all'); }
        });
        tlp.empty().hide();
      }}));
    }
    if(btns.length) tpl.push($('<div/>',{'class':'Buttons'}).append(btns));
    tlp.empty().append(tpl).show();
    tlp.off('click').on('click', function(){ tlp.empty().hide(); });
  };

  // ---------- close ----------
  this._close = function(reset){
    if(_ref_timer){ clearTimeout(_ref_timer); _ref_timer = 0; }
    // закрываем возможные оверлеи/панели UI
    try { $('.pkx-btl-settings, .pkx-btl-overlay').remove(); } catch(e){}
    try { $(document).off('keydown.pkxBattle'); } catch(e){}
    if (reset){ _self._action({'exit':'true'}, null, null, function(){ _self._close(false); }); return; }
    if (_is_socket && onSocketMessage) {
      try {
        if (typeof window.GameSocket.socket.off === 'function') window.GameSocket.socket.off('socket_message', onSocketMessage);
        else if (typeof window.GameSocket.socket.removeListener === 'function') window.GameSocket.socket.removeListener('socket_message', onSocketMessage);
      } catch(e){}
    }
    _data = {}; window['isTrade'] = false; window['ClassTrade'] = null;
    if(_element_window){ _element_window.remove(); }
    _element_window_game = _element_window = _element_my = _element_enemy = null;
    _element_my_list = _element_enemy_list = _element_button_confirmed = null;
    _element_warning = _element_status = _element_poke_info = null;
    $('#battleMap').remove();
    $('.DivWorld').find('.DivMap').show();
  };

  // ---------- socket subscribe ----------
  if (_is_socket) {
    var tradeId = info && info.tradeId ? info.tradeId : null;
    onSocketMessage = function(data){
      var type = Array.isArray(data) ? data[0] : data.type;
      var payload = Array.isArray(data) ? data[2] : data.data;
      if (type === 'trade') {
        if (!tradeId || (payload && payload.tradeId == tradeId) || (data[1] && data[1] == tradeId)) {
          _self._parseData(payload);
        }
      }
    };
    try { window.GameSocket.socket.on('socket_message', onSocketMessage); } catch(e){}
  }

  // ---------- start ----------
  this._started = function(data){ window['isTrade'] = true; _self._parseData(data); };
  _self._started(info);
  if (!_is_socket) setTimeout(function(){ _self._refresh(true); }, 0);
};



var GameChat = function(uInfo){

    // ChatProfanity: инициализация (только для модерации/детекта). Не меняет дизайн.
    try{
        if(window.ChatProfanity && typeof window.ChatProfanity.init === 'function'){
            // _userInfo в проекте формируется выше/ниже; если ещё нет — инициализируем позже при первом update
            if(typeof _userInfo !== 'undefined' && _userInfo){
                window.ChatProfanity.init(_userInfo, {endpoint:'/do/chat'});
            }
        }
    }catch(e){}
    var _self = this;

    var _element_move_chat = $('.DivChat .Chat .Category'),
        _element_chat_channel = $('.DivChat .Chat .Talk .Message-Block.Active'),
        _element_chat_user = $('#chat_user_to'),
        _element_chat_send = $('#chat_send'),
        _element_chat_user_desktop = $('#chat_user_to_desktop'),
        _element_chat_send_desktop = $('#chat_send_desktop'),
        _element_chat_send_smile = $('.Smiles'),
        _element_scrolling = $('input.__chat_scrolls'),
        _element_command_list = null,
        _element_smile_list = null,
        _element_poke_info = null,
        _userInfo = uInfo || {},
        _userChannel = 0,
        _loaded = false,
        _lastID = 0,
        _count_chanel = {},
        _poke_list = {},
        _renderedMsgIds = {},
        _pmTabsClosed = {};

    // --- глобальный массив объявлений ---
    window.ChatAnnouncements = window.ChatAnnouncements || [];

    // --- Сохранение и загрузка закрытых PM вкладок ---
    function saveClosedTabs() {
        try { localStorage.setItem('pmTabsClosed', JSON.stringify(_pmTabsClosed)); } catch(e){}
    }
    function loadClosedTabs() {
        try {
            var tabs = localStorage.getItem('pmTabsClosed');
            if(tabs) _pmTabsClosed = JSON.parse(tabs);
        } catch(e){}
    }
    loadClosedTabs();

    function getPrivateChannelId(user_id, userto_id) {
        var ids = [parseInt(user_id), parseInt(userto_id)].sort(function(a, b){return a-b;});
        return "id" + ids[0] + "_" + ids[1];
    }

    function isPmTabClosed(channelId) { return !!_pmTabsClosed[channelId]; }
    function closePmTab(channelId) { _pmTabsClosed[channelId] = true; saveClosedTabs(); }
    function openPmTab(channelId) { if (_pmTabsClosed[channelId]) { delete _pmTabsClosed[channelId]; saveClosedTabs(); } }

    function createPmTab(privateChannelId, interlocutorLogin, force) {
        if (force) openPmTab(privateChannelId);
        if (!force && isPmTabClosed(privateChannelId)) return;
        if (_element_move_chat.find('.chat_move_channel_' + privateChannelId).length === 0) {
            var $tabNew = $("<div />", {
                class: "Button chat_move_channel_" + privateChannelId,
                "data-channel": privateChannelId,
                html: '<i class="fas fa-user"></i> ' + interlocutorLogin +
                      '<span class="closePmTab" title="Закрыть" style="margin-left:7px; cursor:pointer;">&times;</span>'
            });
            $tabNew.on('click', function(e){
                if ($(e.target).hasClass('closePmTab')) {
                    _self._closePmTab(privateChannelId); return false;
                }
                _self._targetChannel($(this));
            });
            _element_move_chat.append($tabNew);
        }
        if ($('.Message-Block.Channel_' + privateChannelId).length === 0) {
            $("<div />", {
                class: "Message-Block Channel_" + privateChannelId,
                "data-channel": privateChannelId
            }).appendTo('.Chat .Talk');
        }
    }

    // --- вывод системных сообщений в чат ---
    this.addSystemMessage = function(text, type) {
        $('.Message-Block.Active').each(function() {
            $(this).append(
                $('<div/>', {
                    'class': 'Message system-msg chat-status-msg',
                    'html': '<span style="color:' + (type=="error"?"#d00":"#090") + ';font-weight:bold;">' + text + '</span>'
                })
            );
            this.scrollTop = this.scrollHeight;
        });
    };

    // --- объявления в начале каждого канала чата ---
// Требование: системные уведомления видны, пока не отключены, но не "спавнятся" при каждом обновлении.
// Вставка идемпотентна и выполняется один раз на канал за "старт игры" (sessionStorage).
this.renderAnnouncements = function() {
    try {
        var disabled = (localStorage && localStorage.getItem('chat_announcements_off') === '1');
        if (disabled) {
            $('.Message-Block .chat-announcement.system-msg').remove();
            return;
        }

        var list = window.ChatAnnouncements;
        if (!list || !list.length) return;

        // FNV-1a hash (быстро и достаточно для дедупа)
        var _h = function(str){
            str = String(str || '');
            var h = 2166136261;
            for (var i = 0; i < str.length; i++) {
                h ^= str.charCodeAt(i);
                h = (h + (h<<1) + (h<<4) + (h<<7) + (h<<8) + (h<<24)) >>> 0;
            }
            return 'ann_' + h.toString(16);
        };

        $('.Message-Block').each(function() {
            var $block = $(this);

            // идентификатор канала: берём из классов Channel_X или из data-id, иначе общий
            var chanId = 'common';
            var cls = ($block.attr('class') || '').match(/Channel_([0-9]+)/);
            if (cls && cls[1]) chanId = String(cls[1]);
            else if ($block.data('channel')) chanId = String($block.data('channel'));
            else if ($block.attr('data-channel')) chanId = String($block.attr('data-channel'));

            var ssKey = 'chat_ann_inited_' + chanId;
            // один раз на канал за старт игры
            if (sessionStorage && sessionStorage.getItem(ssKey) === '1') return;
            if (sessionStorage) sessionStorage.setItem(ssKey, '1');

            $.each(list, function(i, text){
                var key = _h(text);
                if ($block.find('[data-ann-key="'+key+'"]').length) return;
                $block.prepend(
                    $('<div/>', {
                        'class': 'chat-announcement system-msg',
                        'data-ann-key': key,
                        'html': '<span class="chat-announcement">[Объявление]: ' + text + '</span>'
                    })
                );
            });
        });
    } catch(e){ /* no-op */ }
};

    // --- обновление объявлений из любого места ---
window.setChatAnnouncements = function(list) {
    window.ChatAnnouncements = Array.isArray(list) ? list : [];
    try {
        // если набор объявлений изменился — разрешаем вставку заново (но всё равно один раз за канал)
        var sig = JSON.stringify(window.ChatAnnouncements || []);
        var h = 0, i = 0;
        for (i = 0; i < sig.length; i++) { h = ((h<<5)-h) + sig.charCodeAt(i); h |= 0; }
        var key = 'chat_ann_sig';
        var prev = (sessionStorage ? sessionStorage.getItem(key) : null);
        var cur = String(h);
        if (sessionStorage && prev !== cur) {
            sessionStorage.setItem(key, cur);
            // сбрасываем отметки инициализации по каналам, чтобы новые объявления показались
            for (i = 0; i < sessionStorage.length; i++) {
                var k = sessionStorage.key(i);
                if (k && k.indexOf('chat_ann_inited_') === 0) {
                    sessionStorage.removeItem(k);
                    i--;
                }
            }
        }
    } catch(e){}

    if (window.GameChat && typeof window.GameChat.renderAnnouncements === 'function') {
        window.GameChat.renderAnnouncements();
    }
};

    /* ========= ХЕЛПЕРЫ ДЛЯ ЗАГЛУШЕК ЦИТАТ ========= */
// Глобальное хранилище данных о цитатах (для разворота заглушки при отправке)
window.ChatQuoteStore = window.ChatQuoteStore || {};

// Развернуть заглушки вида: ↩ #123 @User (01:03)  →  [quote id=123 user="User" time="01:03"]…[/quote]
function expandQuoteStubs(text) {
  return String(text || '').replace(
    /↩\s*#(\d+)\s*@?([^\s(]+)?(?:\s*\(([^)]+)\))?/g,
    function (_, id, user, time) {
      var saved = (window.ChatQuoteStore || {})[String(id)] || {};
      var body  = saved.content || '';
      var u     = saved.user || user || '';
      var t     = saved.time || time || '';
      var attrs = 'id=' + id
        + (u ? ' user="' + u.replace(/"/g,'&quot;') + '"' : '')
        + (t ? ' time="' + t.replace(/"/g,'&quot;') + '"' : '');
      return '[quote ' + attrs + ']' + body + '[/quote]';
    }
  );
}

// Превращает «сырые» [quote ...]...[/quote] в аккуратную заглушку прямо в инпуте,
// одновременно кладёт данные в ChatQuoteStore, чтобы потом корректно развернуть.
function maskQuotesInInput($el) {
  var v = $el.val(); if (!v) return;
  var changed = false;
  v = v.replace(/\[quote([^\]]*)\]([\s\S]*?)\[\/quote\]/ig, function (_m, attr, body) {
    function getAttr(name) {
      var rx = new RegExp(name + '=(?:"([^"]*)"|\'([^\']*)\'|([^\\s\\]]+))','i');
      var m = (attr || '').match(rx);
      return m ? (m[1] || m[2] || m[3] || '') : '';
    }
    var id   = getAttr('id') || String(Date.now() % 100000);
    var user = getAttr('user');
    var time = getAttr('time');

    var short = String(body || '').replace(/\s+/g, ' ').trim();
    if (short.length > 80) short = short.slice(0, 76) + '…';

    window.ChatQuoteStore[String(id)] = {
      id: parseInt(id, 10) || id,
      user: user || '',
      time: time || '',
      content: short
    };
    changed = true;
    return '↩ #' + id + (user ? ' @' + user : '') + (time ? ' (' + time + ')' : '') + ' ';
  });
  if (changed) $el.val(v);
}
/* ========= КОНЕЦ ХЕЛПЕРОВ ========= */


this._send = function() {
    var msg = _element_chat_send.val();

    // Автомаска «сырых» цитат в инпуте (если игрок вставил [quote] вручную)
    maskQuotesInInput(_element_chat_send);
    msg = _element_chat_send.val();

    var pmRegExp = /^([^\s]+)\s*([=\/])\s*(.*)$/;
    var matched = msg && msg.match(pmRegExp);
    if (msg && msg.trim() === "-") { _self._viewCommand(); _element_chat_send.val(''); return false; }

    var userId = '', pmLogin = '', text = msg, pmSign = "", isPm = false;
    if (matched) {
        pmLogin = matched[1]; pmSign = matched[2]; text = matched[3]; isPm = true;
        _element_chat_user.find('option').each(function(){
            if ($(this).text().trim() === pmLogin) { userId = $(this).val(); }
        });
        if (!userId && typeof window.userListData !== "undefined" && Array.isArray(window.userListData)) {
            window.userListData.forEach(function(u){ if (u.login === pmLogin) userId = u.id; });
        }
        if (!userId && typeof _userInfo.login !== "undefined" && _userInfo.login === pmLogin) { userId = _userInfo.id; }
        if (userId && userId !== _userInfo.id) {
            var pmId = getPrivateChannelId(_userInfo.id, userId);
            createPmTab(pmId, pmLogin, true);
            _element_chat_user.val(userId);
            msg = text;
            var $tab = _element_move_chat.find('.chat_move_channel_' + pmId);
            if ($tab.length) { _self._targetChannel($tab); }
        }
    }

    // Разворачиваем заглушки ↩ #id @user (time) в настоящий [quote ...]…[/quote]
    if (msg) { msg = expandQuoteStubs(msg); }

    if (msg && msg.length) {
        _self._action({
            'type':'add',
            'msg': msg,
            'to_user': (_element_chat_user.val().length ? _element_chat_user.val() : 0)
        }, function(data){
            if (isPm && pmSign) { _element_chat_send.val(pmSign + " "); } else { _element_chat_send.val(''); }
            _self._update(data);
        });
    }
    return false;
};

this._send_desktop = function() {
    var msg = _element_chat_send_desktop.val();

    // Автомаска «сырых» цитат в инпуте (если игрок вставил [quote] вручную)
    maskQuotesInInput(_element_chat_send_desktop);
    msg = _element_chat_send_desktop.val();

    var pmRegExp = /^([^\s]+)\s*([=\/])\s*(.*)$/;
    var matched = msg && msg.match(pmRegExp);
    if (msg && msg.trim() === "-") { _self._viewCommand(); _element_chat_send_desktop.val(''); return false; }

    var userId = '', pmLogin = '', text = msg, pmSign = "", isPm = false;
    if (matched) {
        pmLogin = matched[1]; pmSign = matched[2]; text = matched[3]; isPm = true;
        _element_chat_user_desktop.find('option').each(function(){
            if ($(this).text().trim() === pmLogin) { userId = $(this).val(); }
        });
        if (!userId && typeof window.userListData !== "undefined" && Array.isArray(window.userListData)) {
            window.userListData.forEach(function(u){ if (u.login === pmLogin) userId = u.id; });
        }
        if (!userId && typeof _userInfo.login !== "undefined" && _userInfo.login === pmLogin) { userId = _userInfo.id; }
        if (userId && userId !== _userInfo.id) {
            var pmId = getPrivateChannelId(_userInfo.id, userId);
            createPmTab(pmId, pmLogin, true);
            _element_chat_user_desktop.val(userId);
            msg = text;
            var $tab = _element_move_chat.find('.chat_move_channel_' + pmId);
            if ($tab.length) { _self._targetChannel($tab); }
        }
    }

    // Разворачиваем заглушки перед отправкой
    if (msg) { msg = expandQuoteStubs(msg); }

    if (msg && msg.length) {
        _self._action({
            'type':'add',
            'msg': msg,
            'to_user': (_element_chat_user_desktop.val().length ? _element_chat_user_desktop.val() : 0)
        }, function(data){
            if (isPm && pmSign) { _element_chat_send_desktop.val(pmSign + " "); } else { _element_chat_send_desktop.val(''); }
            _self._update(data);
        });
    }
    return false;
};

// Обработчики инпутов — добавлена только маска цитат. Вид и поведение не менял.
_element_chat_send.on('input', function(){
    maskQuotesInInput($(this));
    if ($(this).val().charAt(0) == '-') { _self._viewCommand(); }
    else { _self._viewCommand(true); }
});
_element_chat_send_desktop.on('input', function(){
    maskQuotesInInput($(this));
    if ($(this).val().charAt(0) == '-') { _self._viewCommand(); }
    else { _self._viewCommand(true); }
});

    this._viewCommand = function(hide){
        if(_element_command_list && _element_command_list.length){
            if(_element_command_list.is(':visible')){
                if(hide){ _element_command_list.css('display', 'none'); }
            }else{
                if(!hide){
                    if(!_element_command_list.is(':empty')){
                        _element_command_list.css('display', 'block');
                    }
                }
            }
        }else{
            if(!hide){
                _element_command_list = $('<div />', {
                    'class':'window commandList'
                }).appendTo('.Talk');
                var tpl = [];
                $.each(window.commandList || {}, function(key, val){
                    if(
                        (val['group'] && $.inArray(parseInt(_userInfo['group']), val['group']) != -1) ||
                        (val['id'] && $.inArray(parseInt(_userInfo['id']), val['id']) != -1)
                    ){
                        tpl.push(
                            $('<form />', {
                                'action':'',
                                'method':'POST',
                                'on':{
                                    'submit': function(){
                                        var data = {'type':'console', 'console':key}, form = $(this);
                                        $.each(form.serializeArray(), function(key, val){
                                            if(val && val['name']){
                                                data[val['name']] = val['value'];
                                            }
                                        });
                                        _self._action(data, null, null, function(){});
                                        return false;
                                    }
                                }
                            }).append(
                                $('<div />', {
                                    'class':'cmd'
                                }).append(
                                    '<h3>'+val['title']+'</h3>',
                                    val['text'],
                                    '<input type="submit" value="OK" />'
                                )
                            ).on('focus', 'input', function(){
                                if($(this).val() != 'OK'){ $(this).val(''); }
                            })
                        );
                    }
                });
                if(tpl.length){
                    var msgField = $('#messageFieldBox');
                    _element_command_list.css('left',(parseInt(msgField.offset().left) - 0)+'px').append(tpl);
                }else{
                    _element_command_list.css('display', 'none');
                }
            }
        }
    };

    this._action = function(data, suc, err, cpl){
        if(window.GameSocket && window.GameSocket.isActive){
            GameSocket.emit('chat', $.extend({
                'chat':'chat',
                'lastID':_lastID,
                'chanel':_userChannel
            }, data));
            if(typeof suc === 'function') suc.call(_self, {});
            if(typeof cpl === 'function') cpl.call(_self, {});
        } else {
            suc = (suc && typeof suc === 'function') ? suc : function(){};
            err = (err && typeof err === 'function') ? err : function(){};
            cpl = (cpl && typeof cpl === 'function') ? cpl : function(){};
            if(_loaded){ cpl.call(_self, ''); return false; }
            _loaded = true;
            $.ajax({
                url: "/do/chat",
                type: "POST",
                dataType: "json",
                data: $.extend({
                    'chat':'chat',
                    'lastID':_lastID,
                    'chanel':_userChannel
                }, data),
                success: function(info, textStatus){
                    if(info){ suc.call(_self, info, textStatus); }
                    if(info['error']){
                        err.call(_self, textStatus);
                        if(window.Game && window.Game.notifications)
                            Game.notifications.main(info['text'], 'error');
                    }
                    if(info['success']){
                        if(window.Game && window.Game.notifications)
                            Game.notifications.main(info['text'], 'success');
                    }
                    cpl.call(_self, info, textStatus);
                },
                complete: function(jqXHR, textStatus){
                    _loaded = false;
                    cpl.call(_self, textStatus);
                },
                error: function(jqXHR, textStatus){
                    err.call(_self, textStatus);
                    cpl.call(_self, textStatus);
                }
            });
        }
    };

    this._start = function(){
        var cop;
        if(window.GROUP_USER >= 1 && window.GROUP_USER <= 3) {
            cop = '<div class="Button chat_move_channel_10" data-channel="10">'+Lang.chat_cop+'</div>';
        }else {
            cop = '';
        }
        if(_element_move_chat && _element_move_chat.length){
            _element_move_chat.empty();
            _element_move_chat.append(
                '<div class="Button chat_move_channel_0 Active" data-channel="0">'+Lang.chat_world+'</div>' +
                '<div class="Button chat_move_channel_8" data-channel="8">'+Lang.chat_loc+'</div>' +
                '<div class="Button chat_move_channel_9" data-channel="9">'+Lang.chat_torg+'</div>' +
                '<div class="Button chat_move_channel_21" data-channel="21">'+Lang.chat_clan+'</div>' +
                cop
            );
            $('div.Message-Block').removeClass('Active');
            _element_move_chat.off('click').on('click', 'div.Button', function(e){
                if ($(e.target).hasClass('closePmTab')) return;
                _self._targetChannel($(this));
            });
            _element_chat_channel = $('div.Message-Block.Channel_'+_userChannel);
            if(_element_chat_channel.length){
                _element_chat_channel.addClass('Active');
            }
            if(_element_chat_send_smile && _element_chat_send_smile.length){
                _element_chat_send_smile.off('click').on('click', function(){
                    _self._viewSmile();
                });
            }
        }
        // --- всегда показываем объявления после старта ---
        _self.renderAnnouncements();
    };

    this._update = function(data) {
        if (data['infoChatLastId']) {
            _lastID = data['infoChatLastId'];
        }
        if (data['infoChat']) {
            $.each(data['infoChat'], function (key, val) {
                let parsedVal = val;
                if (typeof parsedVal === "string") {
                    try { parsedVal = $.parseJSON(val); }
                    catch (e) { console.error("Ошибка JSON:", val, e); return; }
                }
                var isPrivate = parsedVal["msg_type"] == 1;
                var privateChannelId = isPrivate
                    ? (parsedVal['private_channel_id'] ? parsedVal['private_channel_id'] : getPrivateChannelId(parsedVal["user_id"], parsedVal["userto_id"]))
                    : null;
                var myId = parseInt(_userInfo.id);
                var force = false;
                if (isPrivate && privateChannelId &&
                    (parseInt(parsedVal["user_id"]) === myId || parseInt(parsedVal["userto_id"]) === myId)) {
                    if(!_renderedMsgIds[key]) {
                        force = true;
                    }
                }
                var __isNew = !_renderedMsgIds[key];
                _self._generateMsg(key, parsedVal, force);
                try{ if(__isNew && window.ChatProfanity && typeof window.ChatProfanity.onIncoming==='function'){ window.ChatProfanity.onIncoming(parsedVal, _self); } }catch(e){}
            });

            var $desktopActiveBlock = $('.ChatBox:not(.mobile) .Message-Block.Active');
            var $desktopScrollCheckbox = $('.ChatBox:not(.mobile) .__chat_scrolls');
            if ($desktopActiveBlock.length && $desktopScrollCheckbox.prop('checked')) {
                setTimeout(function() {
                    $desktopActiveBlock[0].scrollTop = $desktopActiveBlock[0].scrollHeight;
                }, 0);
            }
            if (_element_chat_channel && _element_chat_channel.length) {
                _element_chat_channel.off('click.__userpoke').on(
                    'click.__userpoke',
                    '.__info_usr_poke',
                    function (e) { _self._pokeFocus(e, $(this).attr('data-id')); }
                );
            }
        }
        // --- объявления всегда в начале канала после обновления ---
        _self.renderAnnouncements();
    };

    this._updateCount = function (chanel) {
        var elm = _element_move_chat.find(".chat_move_channel_" + chanel),
            count = _count_chanel["c" + chanel] || 0;
        if (elm.length) {
            if (count > 0 && !elm.hasClass("Active")) {
                if (elm.find(".Count").length) {
                    elm.find(".Count").html(count);
                } else {
                    elm.append(' <span class="Count">' + count + "</span>");
                }
            } else {
                elm.find(".Count").remove();
            }
        }
    };

    this._targetChannel = function(elm){
        if (elm && elm.length) {
            var channel = elm.attr('data-channel');
            if (isPmTabClosed(channel)) return;
            $('div.Message-Block').removeClass('Active');
            _element_move_chat.find('div.Button').removeClass('Active');
            _element_chat_channel = $('div.Message-Block.Channel_' + channel);
            if (_element_chat_channel.length === 0) {
                _element_chat_channel = $("<div />", {
                    class: "Message-Block Channel_" + channel,
                    "data-channel": channel
                }).appendTo('.Chat .Talk');
            }
            _element_chat_channel.addClass('Active');
            _element_move_chat.find('.chat_move_channel_' + channel).addClass('Active');
            if(channel.indexOf('id') === 0) {
                var $tab = _element_move_chat.find('.chat_move_channel_' + channel);
                if ($tab.length && $tab.find('.closePmTab').length === 0) {
                    $tab.append('<span class="closePmTab" title="Закрыть" style="margin-left:7px; cursor:pointer;">&times;</span>');
                }
            }
            var autoScroll = true;
            if (_element_scrolling && _element_scrolling.length) {
                autoScroll = _element_scrolling.prop('checked');
            }
            if (autoScroll) {
                setTimeout(function() {
                    if (_element_chat_channel.length) {
                        _element_chat_channel[0].scrollTop = _element_chat_channel[0].scrollHeight;
                    }
                }, 10);
            }
            _userChannel = channel;
            _count_chanel['c' + channel] = 0;
            if(channel.indexOf('id')===0 && !_element_chat_channel.data('pmhistory')){
                _self.loadPmHistory(channel);
                _element_chat_channel.data('pmhistory',1);
            }
            // --- объявления всегда в начале канала при переключении ---
            _self.renderAnnouncements();
        }
    };

    this._closePmTab = function(channel) {
        closePmTab(channel);
        _element_move_chat.find('.chat_move_channel_' + channel).remove();
        $('.Message-Block.Channel_' + channel).remove();
        if($('.Message-Block.Active').length === 0){
            _self._targetChannel(_element_move_chat.find('.chat_move_channel_0'));
        }
        _self.renderAnnouncements();
    };


/* ==================== ГЛОБАЛЬНЫЕ ХЕЛПЕРЫ (один раз) ==================== */
// Хранилище данных для заглушек цитат
window.ChatQuoteStore = window.ChatQuoteStore || {};

// Разворачивает заглушки вида "↩ #123 @User (01:03)" в [quote ...]...[/quote]
window.expandQuoteStubs = function expandQuoteStubs(text) {
  return String(text || '').replace(
    /↩\s*#(\d+)\s*@?([^\s(]+)?(?:\s*\(([^)]+)\))?\s*/g,
    function (_, id, user, time) {
      var saved = (window.ChatQuoteStore || {})[String(id)] || {};
      var body  = saved.content || '';
      var u     = saved.user || user || '';
      var t     = saved.time || time || '';
      var attrs = 'id=' + id
                + (u ? ' user="' + u.replace(/"/g,'&quot;') + '"' : '')
                + (t ? ' time="' + t.replace(/"/g,'&quot;') + '"' : '');
      return '[quote ' + attrs + ']' + body + '[/quote] ';
    }
  );
};


/* ================================================================
   СКРЫТЫЕ ПОЛЬЗОВАТЕЛИ: память, утилиты, CSS
   ================================================================= */
/* ================== ХРАНИЛИЩЕ MUTE (как было) ================== */
var _gcMuted = {}; // { "uid": {login:"Kara", t: 1712345678901} }
(function gcLoadMuted(){
  try{ _gcMuted = JSON.parse(localStorage.getItem('chatMutedUsers')||'{}')||{}; }catch(_){ _gcMuted={}; }
})();
function gcSaveMuted(){ try{ localStorage.setItem('chatMutedUsers', JSON.stringify(_gcMuted)); }catch(_){} }
function gcIsMuted(uid){ return !!_gcMuted[String(uid)]; }
function gcMute(uid, login){ _gcMuted[String(uid)] = { login: login||('id'+uid), t: Date.now() }; gcSaveMuted(); }
function gcUnmute(uid){ delete _gcMuted[String(uid)]; gcSaveMuted(); }
function gcMutedIds(){ return Object.keys(_gcMuted); }
function gcPlural(n){ return 'игроков'; } // «…от N игроков» — всегда «игроков»

/* ================== СТИЛИ: плашки + поповер (обновлено) ================== */
(function injectMuteCSS(){
  if (document.getElementById('gc-mute-css')) return;
  var st = document.createElement('style'); st.id='gc-mute-css';
  st.textContent = `
    /* жирное для «мной/мне» */
    .Message.gc-strong-row,
    .Message.gc-strong-row .Data,
    .Message.gc-strong-row .User,
    .Message.gc-strong-row .Post { font-weight:800 !important; }

    /* плашки внутри чата */
    .gc-muted-note, .gc-muted-summary{
      margin:6px 8px 0; padding:8px 12px; border:1px solid #e6eafe; border-radius:10px;
      background:#f6f8ff; color:#1b2b4f; font:600 12px/1.35 Nunito,Inter,Arial,sans-serif;
      display:flex; flex-wrap:wrap; align-items:center; gap:10px;
    }
    .gc-muted-note .act{ cursor:pointer; font-weight:800; text-decoration:underline; user-select:none; }
    .gc-muted-note .act:hover{ text-decoration:none; }
    .Message.gc-muted-hidden{ display:none !important; }

    /* поповер «Скрытые пользователи» — новый чистый стиль */
    .gc-mute-pop{
      --arrow-left: 24px;
      position:absolute; z-index:1000004; width:min(420px, 96vw);
      background:#fff; border:1px solid #e6eafe; border-radius:14px;
      box-shadow:0 14px 36px rgba(23,35,74,.15); overflow:hidden;
      font-family:Nunito,Inter,Arial,sans-serif; color:#0f172a;
    }
    .gc-mute-pop::after{
      content:""; position:absolute; width:0; height:0;
      border:9px solid transparent; border-bottom-color:#fff; top:-18px; left:var(--arrow-left);
      filter: drop-shadow(0 -1px 0 #e6eafe);
    }
    .gc-mute-pop.flip-y::after{
      border-bottom-color:transparent; border-top-color:#fff; top:auto; bottom:-18px;
      filter: drop-shadow(0 1px 0 #e6eafe);
    }

    .gc-mute-pop .hdr{
      position:sticky; top:0; z-index:1;
      padding:10px 12px; display:flex; gap:10px; align-items:center; justify-content:space-between;
      background:#fafcff; border-bottom:1px solid #eef2ff;
    }
    .gc-mute-pop .ttl{ display:flex; align-items:center; gap:10px; }
    .gc-mute-pop .title{ font:900 14px/1 Nunito,Inter,Arial; }
    .gc-mute-pop .count{ padding:3px 8px; border-radius:999px; background:#eef3ff; color:#0e55b6; font:800 12px/1; }
    .gc-mute-pop .tools{ display:flex; gap:8px; }
    .gc-btn{ border:1px solid #dbe3ff; padding:8px 12px; border-radius:10px; background:#fff; cursor:pointer;
             font:800 12px/1 Nunito,Inter,Arial; color:#0e55b6; }
    .gc-btn:hover{ background:#f6f8ff }
    .gc-btn-ghost{ background:transparent }
    .gc-btn-danger{ color:#b23b3b; border-color:#f0d0d0; }
    .gc-btn-danger:hover{ background:#fff6f6 }
    .gc-btn-primary{ background:#0e55b6; border-color:#0e55b6; color:#fff; }
    .gc-btn-primary:hover{ filter:brightness(.96) }

    .gc-mute-pop .body{ padding:10px; max-height:60vh; overflow:auto; }
    .gc-mute-pop .search-wrap{ position:sticky; top:0; background:#fff; padding-bottom:8px; }
    .gc-mute-pop .search{
      width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:10px;
      background:#f7f9fc; font:700 13px/1.2 Nunito,Inter,Arial; color:#171d2b;
    }

    .gc-mute-pop .row{
      display:grid; grid-template-columns:38px 1fr auto; gap:12px; align-items:center;
      padding:10px 2px; border-bottom:1px dashed #eef2ff;
    }
    .gc-mute-pop .ava{
      width:38px; height:38px; border-radius:50%; background:#eef3ff; color:#0e55b6;
      display:flex; align-items:center; justify-content:center; font:900 14px/1 Nunito,Inter,Arial;
    }
    .gc-mute-pop .login{ font:900 13px/1.1 Nunito,Inter,Arial; }
    .gc-mute-pop .pill-id{
      margin-left:6px; padding:2px 8px; border-radius:999px; background:#f1f5ff; color:#0e55b6; font:800 11px/1;
    }
    .gc-mute-pop .meta{ margin-top:2px; color:#5d6b86; font:700 12px/1.2 Nunito,Inter,Arial; }
    .gc-mute-pop .actions{ display:flex; gap:6px; }

    /* мобилки: превращаем в bottom-sheet, кнопки на отдельной строке */
    @media (max-width:540px){
      .gc-mute-pop{ position:fixed; left:0 !important; right:0 !important; bottom:0 !important; top:auto !important;
        width:100vw; border-radius:16px 16px 0 0; }
      .gc-mute-pop::after{ display:none; }
      .gc-mute-pop .body{ max-height:58vh; }
      .gc-mute-pop .row{ grid-template-columns:38px 1fr; }
      .gc-mute-pop .actions{ grid-column:1 / -1; }
    }
  `;
  document.head.appendChild(st);
})();

/* ================== ПЛАШКА В ЧАТЕ (как было) ================== */
function gcEnsureMutedNote($block, uid, login){
  if (!$block || !$block.length) return;
  var sel = '.gc-muted-note[data-uid="'+uid+'"]';
  if ($block.find(sel).length) return;

  var $note = $('<div/>', { 'class':'gc-muted-note', 'data-uid': uid }).append(
    $('<span/>').html('Сообщения <b>'+ (login||('id'+uid)) +'</b> скрыты'),
    $('<span class="act gc-show-once">Показать разово</span>'),
    $('<span class="act gc-unmute">Показать всегда</span>')
  );
  $note.on('click', '.gc-show-once', function(){
    $block.find('.Message.gc-muted-hidden[data-author-id="'+uid+'"]').removeClass('gc-muted-hidden');
  });
  $note.on('click', '.gc-unmute', function(){
    gcUnmute(uid);
    $block.find('.Message.gc-muted-hidden[data-author-id="'+uid+'"]').removeClass('gc-muted-hidden');
    $block.find('.gc-muted-note[data-uid="'+uid+'"]').remove();
    gcUpdateNotesLayout($block);
  });
  $block.append($note);
  gcUpdateNotesLayout($block);
}

/* сводка, если скрытых > 2 (кнопок в плашке нет) */
function gcUpdateNotesLayout($block){
  if (!$block || !$block.length) return;
  var $notes = $block.children('.gc-muted-note');
  $block.children('.gc-muted-summary').remove();

  if ($notes.length <= 2){ $notes.show(); return; }
  $notes.hide();
  var count = $notes.length;
  var $sum = $('<div class="gc-muted-summary"></div>').text(
    'Сообщения скрыты от ' + count + ' ' + gcPlural(count)
  );
  $block.append($sum);
}

/* ================== ПОПОВЕР «Скрытые пользователи» — обновлено ================== */
this._toggleMutedPanel = function(ev){
  var $ex = $('.gc-mute-pop'); if ($ex.length){ $ex.remove(); return; }

  var $p = $('<div class="gc-mute-pop" role="dialog" aria-label="Скрытые пользователи"></div>');
  var ids = gcMutedIds();

  // шапка
  var $hdr = $('<div class="hdr"></div>');
  $hdr.append(
    $('<div class="ttl"></div>').append(
      $('<div class="title">Скрытые пользователи</div>'),
      $('<div class="count"></div>').text(ids.length)
    ),
    $('<div class="tools"></div>').append(
      $('<button type="button" class="gc-btn gc-btn-danger">Снять все</button>').on('click', function(){
        ids.slice().forEach(gcUnmute);
        $('.Message.gc-muted-hidden').removeClass('gc-muted-hidden');
        $('.gc-muted-note,.gc-muted-summary').remove();
        $('.Message-Block').each(function(){ gcUpdateNotesLayout($(this)); });
        renderList($search.val());
        $hdr.find('.count').text('0');
      }),
      $('<button type="button" class="gc-btn gc-btn-ghost">Закрыть</button>').on('click', function(){ $p.remove(); })
    )
  );

  // тело
  var $body = $('<div class="body"></div>');
  var $searchWrap = $('<div class="search-wrap"></div>');
  var $search = $('<input type="text" class="search" placeholder="Поиск по нику или id…">');
  $searchWrap.append($search);

  var $list = $('<div class="list"></div>');

  function esc(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  function renderRow(row){
    var initial = (row.login||'U').trim().charAt(0).toUpperCase();
    return $(`
      <div class="row" data-id="${row.id}">
        <div class="ava">${esc(initial)}</div>
        <div class="info">
          <div class="login">${esc(row.login)} <span class="pill-id">id${row.id}</span></div>
          <div class="meta">скрыт: ${new Date(row.t).toLocaleDateString()} ${new Date(row.t).toLocaleTimeString().slice(0,5)}</div>
        </div>
        <div class="actions">
          <button type="button" class="gc-btn gc-btn-primary js-unmute" data-id="${row.id}">Показать</button>
          <button type="button" class="gc-btn js-show-once" data-id="${row.id}">Разово</button>
        </div>
      </div>
    `);
  }

  function renderList(filter){
    $list.empty();
    var items = gcMutedIds().map(function(id){
      var meta = _gcMuted[id]||{}; return { id:id, login: meta.login||('id'+id), t: meta.t||Date.now() };
    });
    if (filter){ filter = filter.toLowerCase();
      items = items.filter(function(r){ return (r.login||'').toLowerCase().indexOf(filter)>=0 || String(r.id).indexOf(filter)>=0; });
    }
    items.sort(function(a,b){ return a.login.localeCompare(b.login); });

    if (!items.length){
      $list.append('<div class="meta" style="padding:10px;color:#6b7280;">Пока никого не скрывали</div>');
      return;
    }
    items.forEach(function(r){ $list.append(renderRow(r)); });
  }

  // события списка
  $list.on('click', '.js-unmute', function(){
    var id = $(this).data('id');
    gcUnmute(id);
    $('.Message.gc-muted-hidden[data-author-id="'+id+'"]').removeClass('gc-muted-hidden');
    $('.gc-muted-note[data-uid="'+id+'"]').remove();
    $('.Message-Block').each(function(){ gcUpdateNotesLayout($(this)); });
    renderList($search.val());
    $hdr.find('.count').text(gcMutedIds().length);
  });
  $list.on('click', '.js-show-once', function(){
    var id = $(this).data('id');
    $('.Message-Block.Active .Message.gc-muted-hidden[data-author-id="'+id+'"]').removeClass('gc-muted-hidden');
  });

  $search.on('input', function(){ renderList($(this).val()); });

  $body.append($searchWrap, $list);
  $p.append($hdr, $body);
  $('body').append($p);

  // позиционирование у точки клика + корректная «стрелочка»
  function placePopover(clientX, clientY){
    var pad = 10, vw = window.innerWidth, vh = window.innerHeight;
    $p.removeClass('flip-y');

    // mobile sheet
    if (vw <= 540){
      $p.css({ left:0, right:0, bottom:0, top:'auto' });
      return;
    }

    // первичное измерение
    $p.css({ left:-9999, top:-9999 }); // чтобы получить реальные размеры
    var w = $p.outerWidth(), h = $p.outerHeight();
    var L = clientX + 12, T = clientY + 12;

    if (L + w + pad > vw) L = vw - w - pad;
    if (L < pad) L = pad;

    if (T + h + pad > vh){
      var tryTop = clientY - h - 12;
      if (tryTop >= pad){ T = tryTop; $p.addClass('flip-y'); }
      else { T = vh - h - pad; }
    }
    if (T < pad) T = pad;

    // позиция «стрелочки» внутри поповера
    var arrow = Math.max(16, Math.min(clientX - L, w - 16));
    $p[0].style.setProperty('--arrow-left', arrow + 'px');

    $p.css({ left:(L + pageXOffset) + 'px', top:(T + pageYOffset) + 'px' });
  }
  var ev0 = ev && (ev.originalEvent||ev);
  var x = (ev0 && typeof ev0.clientX==='number') ? ev0.clientX : window.innerWidth/2;
  var y = (ev0 && typeof ev0.clientY==='number') ? ev0.clientY : window.innerHeight/2;
  placePopover(x, y);

  // пересчёт на ресайз/скролл рядом с точкой вызова
  var onR = function(){ placePopover(x, y); };
  window.addEventListener('resize', onR);
  window.addEventListener('scroll', onR, true);
  $p.on('remove', function(){
    window.removeEventListener('resize', onR);
    window.removeEventListener('scroll', onR, true);
  });
};


/* ================================================================
   РЕНДЕР СООБЩЕНИЯ + ЖЁСТКАЯ СОРТИРОВКА ПО ID
   ================================================================= */
this._generateMsg = function (id, info, force) {
    // ---------- утилиты ----------
    function toStr(v, d) { return (v === undefined || v === null) ? (d || '') : String(v); }
    function esc(s) { return toStr(s).replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function isMobile() {
        try { return (window.matchMedia && window.matchMedia('(hover:none) and (pointer:coarse)').matches) || (window.innerWidth <= 540); }
        catch (_) { return false; }
    }

    // Построение inline-чипа цитаты
    function buildChip(q) {
        var qid=q.id||'', quser=q.user||'', qto=q.to||'', qtogr=q.togroup||'', qtime=q.time||'';
        var qtext = toStr(q.text||'').replace(/\s+/g,' ').trim();

        var headParts=[];
        if (quser) headParts.push('<span class="label u-0">'+esc(quser)+'</span>');
        if (qto){ headParts.push('<span class="user-arrow" style="margin:0 4px;">&raquo;</span>');
                  headParts.push('<span class="label '+(qtogr?('u-'+qtogr):'u-0')+'">'+esc(qto)+'</span>'); }
        if (qtime) headParts.push('<span style="color:#8b8f97;margin-left:6px;">('+esc(qtime)+')</span>');
        var header=headParts.join(' ');

        var mobile=isMobile();
        var chipStyle='cursor:pointer;display:inline-flex;'+(mobile?'flex-wrap:wrap;align-items:flex-start;':'align-items:baseline;')+
                      'gap:6px;max-width:'+(mobile?'100%':'90%')+';padding:'+(mobile?'4px 8px':'2px 6px')+
                      ';border-radius:12px;vertical-align:baseline;background:#e2e8f0;color:#334155;font-size:12px;line-height:18px;';
        var headStyle=mobile?'flex:0 0 auto;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;'
                             :'flex:0 1 auto;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:60%;';
        var bodyStyle=mobile?'flex:1 0 100%;min-width:0;white-space:normal;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;'
                             :'flex:1 1 auto;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
        var titleText=(header?header.replace(/<[^>]+>/g,'')+': ':'')+qtext;

        return ''+
        '<span class="msg-quote-inline '+(qid?('__quote_of_'+qid):'')+'" '+(qid?('data-quote-id="'+esc(qid)+'" '):'')+
            'data-def-mw="'+(mobile?'100%':'90%')+'" data-mobile="'+(mobile?'1':'0')+'" title="'+esc(titleText)+'" style="'+chipStyle+'">'+
            '<i class="fas fa-reply" style="margin-right:4px;color:#475569;flex:0 0 auto;"></i>'+
            '<span class="quote-head" style="'+headStyle+'">'+(header?(header+': '):'')+'</span>'+
            '<span class="quote-body" style="'+bodyStyle+'">'+esc(qtext)+'</span>'+
        '</span>';
    }

    // Парсер [quote]...[/quote]
    function extractAllQuoteChips(text){
        var rest=toStr(text), chips=[], hasQuotes=false, guard=0, blockRe=/\[quote([^\]]*)\]([\s\S]*?)\[\/quote\]/i;
        function getAttr(attrStr,name){
            var rx=new RegExp(name+'=(?:"([^"]*)"|\'([^\']*)\'|&quot;([^&]*)&quot;|([^\\s\\]]+))','i');
            var m=attrStr.match(rx); if(!m) return ''; return m[1]||m[2]||m[3]||m[4]||'';
        }
        while(true){
            if(++guard>2000) break;
            var m=rest.match(blockRe); if(!m) break;
            hasQuotes=true;
            chips.push(buildChip({
                id:getAttr(m[1]||'','id'), user:getAttr(m[1]||'','user'), time:getAttr(m[1]||'','time'),
                to:getAttr(m[1]||'','to'), togroup:getAttr(m[1]||'','togroup'), text:m[2]||''
            }));
            rest=rest.replace(m[0],'').replace(/^\s+/,'');
        }
        if (hasQuotes) rest=rest.replace(/\[\/?quote[^\]]*\]/ig,'').trim();
        return { chipsHtml: chips.join(' '), chipsCount: chips.length, rest: rest };
    }

    function scrollToMessageInBlock($msg){
        if(!$msg||!$msg.length) return;
        var $block=$msg.closest('.Message-Block'); if(!$block.length){ try{$msg[0].scrollIntoView({behavior:'smooth',block:'center'});}catch(_){}
            return; }
        var el=$block[0], b=el.getBoundingClientRect(), c=$msg[0].getBoundingClientRect();
        var target=el.scrollTop+(c.top-b.top)-Math.round(el.clientHeight*0.25);
        if(target<0) target=0; var max=el.scrollHeight-el.clientHeight; if(target>max) target=max;
        if(typeof el.scrollTo==='function') el.scrollTo({top:target,behavior:'smooth'}); else $($block).stop(true).animate({scrollTop:target},200);
    }

    // ---------- основной поток ----------
    id = parseInt(id,10);
    if (_renderedMsgIds[id]) return '';
    _renderedMsgIds[id] = true;

    // служебные удаление
    if (info["msg_type"] == 20) {
        try{
            info["user_msg"] = (typeof info["user_msg"] === "string") ? $.parseJSON(info["user_msg"]) : info["user_msg"];
            if (info && info["user_msg"] && info["user_msg"]["msg_del"]) $("div.__msg_id_" + info["user_msg"]["msg_del"]).remove();
            return "";
        }catch(e){ return ""; }
    }

    if (info["poke_list"]) { _poke_list = $.extend(_poke_list, info["poke_list"]); }

    // канал
    var isPrivate = info["msg_type"] == 1;
    var channel   = info["msg_type"];
    var privateChannelId = null;

    if (isPrivate){
        if (info['private_channel_id']) privateChannelId = info['private_channel_id'];
        else privateChannelId = getPrivateChannelId(info["user_id"], info["userto_id"]);
        channel = privateChannelId;

        var myId = parseInt(_userInfo["id"],10);
        var interlocutorLogin = (parseInt(info["user_id"],10)===myId)?info["userto_login"]:info["user_login"];
        if (force) createPmTab(privateChannelId, interlocutorLogin, true);
        if ($('.Message-Block.Channel_'+privateChannelId).length===0){
            $("<div/>",{class:"Message-Block Channel_"+privateChannelId,"data-channel":privateChannelId}).appendTo('.Chat .Talk');
        }
        if (parseInt(info["userto_id"],10)===myId && !!force){
            var $pmTab = _element_move_chat.find('.chat_move_channel_'+privateChannelId);
            if ($pmTab.length) _self._targetChannel($pmTab);
        }
    }

    // Объявления
    if (info["msg_class"]==="chat-announcement" && info["msg_type"]==0 &&
        (info["user_id"]==0 || info["user_id"]==="0" || info["user_id"]==="announcement")){
        if (typeof info.active!=='undefined' && (info.active===0 || info.active==="0" || info.active===false)) return '';
        var $targetBlock0=$('.Message-Block.Channel_0');
        if(!$targetBlock0.length) $targetBlock0=$("<div/>",{class:"Message-Block Channel_0","data-channel":"0"}).appendTo('.Chat .Talk');
        var $msgA=$('<div/>',{class:'Message msg-announcement'}).append(
            $('<span/>',{class:'msg-announcement__title',text:'Объявление:'}),
            $('<span/>',{class:'msg-announcement__text',html:info["user_msg"]})
        );
        $targetBlock0.append($msgA);
        if ($targetBlock0.hasClass('Active') && _element_scrolling && _element_scrolling.length && _element_scrolling.prop('checked')){
            setTimeout(function(){ $targetBlock0[0].scrollTop = $targetBlock0[0].scrollHeight; },10);
        }
        return '';
    }

    // классы/адресат
    var style="", touser="";
    var myIdNum = parseInt(_userInfo["id"],10);
    var isMine  = (parseInt(info["user_id"],10)===myIdNum);
    var isToMe  = (info["userto_id"] && parseInt(info["userto_id"],10)===myIdNum);
    if (isPrivate) style+="private ";
    if (info["msg_type"]==11) style+="pred ";
    if (info["msg_type"]==12) style+="mut ";
    if (info["msg_type"]==13) style+="alert ";
    if (info["css"]) style+=" "+info["css"];
    if (isMine || isToMe) style+=" gc-strong-row ";
    if (info["userto_id"]){
        touser = ' <span class="user-arrow">&raquo;</span> '+
                 '<span class="label u-'+info["userto_group"]+'">'+replaceEmoji(info["userto_login"])+'</span>';
    }

    var $targetBlock = isPrivate ? $('.Message-Block.Channel_'+privateChannelId) : $('.Message-Block.Channel_'+channel);
    if (!$targetBlock.length) return '';

    // текст
    var q = extractAllQuoteChips(info['user_msg']||'');
    var finalMsgHtml = q.chipsCount>0 ? q.chipsHtml + (q.rest?(' '+replaceEmoji(q.rest)):'') : replaceEmoji(toStr(info['user_msg']));
    if (!finalMsgHtml || /^\s*$/.test(finalMsgHtml)) return '';

    // нода сообщения
    var $msg = $('<div/>', { 'class':'Message __msg_id_'+id+' '+style, 'id':'gameChatMessage' })
      .append(
        $('<div/>',{ 'id':'gameChatMessageTime','class':'Data','html':info['msg_time'],
            'click':function(e){ if (info['msg_type']!=10) { _self._openHelper(e,id,info['user_login']+': '+info['user_msg']); } }}),
        $('<div/>',{ 'class':'User','html':
            '<div class="user-link">'+
              '<div onclick=showUserTooltip("'+info['user_id']+'") class="Info-Link sex'+info['user_sex']+'"><i class="fas fa-info"></i></div>'+
              '<span class="u-'+replaceEmoji(info['user_group'])+' label" onclick=user_to_chat_add("'+info['user_id']+'")>'+replaceEmoji(info['user_login'])+'</span>'+
            '</div>'+touser }),
        $('<span/>',{ 'class':'TextColor'+info['user_msg_color']+' Post '+style, 'html':finalMsgHtml })
      );

    // системные
    if (info.user_login==='System' || info.user_id==='1' || info.user_id===1 ||
        (typeof info.msg_class==='string' && info.msg_class.indexOf('system')!==-1)) {
        $msg.addClass('system-msg');
    }

    // автор/атрибуты
    var authorId = parseInt(info['user_id'],10) || 0;
    var authorLogin = info['user_login'] || ('id'+authorId);
    $msg.attr('data-author-id', authorId);

    // атрибуты для сортировки/сепараторов
    var ts = parseInt(info['msg_ts']||0,10) || 0;
    $msg.attr('data-msg-id', id).attr('data-msg-ts', ts);

    // клики по цитатам (как было)
    $msg.on('click', '.msg-quote-inline, .msg-quote', function(e){
        e.preventDefault(); e.stopPropagation();
        var $chip=$(this);
        if ($chip.hasClass('msg-quote-inline') && isMobile()){
            if ($(e.target).closest('.fa-reply').length){
                var qidM=$chip.data('quote-id'); if(!qidM) return;
                var $currentBlock=$chip.closest('.Message-Block');
                var $tM=$currentBlock.find('.__msg_id_'+qidM); if(!$tM.length) $tM=$('.__msg_id_'+qidM+':visible').first(); if(!$tM.length) $tM=$('.__msg_id_'+qidM).first(); if(!$tM.length) return;
                var $tbM=$tM.closest('.Message-Block');
                if ($tbM.length && $tbM[0]!==$currentBlock[0]){
                    var chM=$tbM.data('channel');
                    if (chM && typeof _self._targetChannel==='function' && _element_move_chat){
                        var $btnM=_element_move_chat.find('.chat_move_channel_'+chM);
                        if ($btnM.length) _self._targetChannel($btnM);
                    }
                }
                setTimeout(function(){ scrollToMessageInBlock($tM); },30);
            } else {
                var expandedM=$chip.attr('data-expanded')==='1';
                if (expandedM){
                    $chip.attr('data-expanded','0').css({maxWidth:$chip.attr('data-def-mw')||'100%'});
                    $chip.find('.quote-head').css({whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',maxWidth:'100%'});
                    $chip.find('.quote-body').css({whiteSpace:'normal',overflow:'hidden',textOverflow:'ellipsis',display:'-webkit-box',WebkitLineClamp:'2',WebkitBoxOrient:'vertical'});
                } else {
                    $chip.attr('data-expanded','1').css({maxWidth:'none'});
                    $chip.find('.quote-head').css({whiteSpace:'normal',overflow:'visible',textOverflow:'clip',maxWidth:'none'});
                    $chip.find('.quote-body').css({whiteSpace:'normal',overflow:'visible',textOverflow:'clip',display:'block'});
                }
            }
            return;
        }
        var qid=$chip.data('quote-id'); if(!qid) return;
        var $current=$chip.closest('.Message-Block');
        var $t=$current.find('.__msg_id_'+qid); if(!$t.length) $t=$('.__msg_id_'+qid+':visible').first(); if(!$t.length) $t=$('.__msg_id_'+qid).first(); if(!$t.length) return;
        var $tb=$t.closest('.Message-Block');
        if ($tb.length && $tb[0]!==$current[0]){
            var ch=$tb.data('channel');
            if (ch && typeof _self._targetChannel==='function' && _element_move_chat){
                var $btn=_element_move_chat.find('.chat_move_channel_'+ch);
                if ($btn.length) _self._targetChannel($btn);
            }
        }
        setTimeout(function(){
            scrollToMessageInBlock($t);
            var pulses=2;(function pulse(n){ if(n<=0) return; $t.stop(true).animate({opacity:0.45},130).animate({opacity:1},150,function(){ pulse(n-1); }); })(pulses);
        },30);
    });

    // добавляем в блок
    if (authorId>0 && gcIsMuted(authorId)) $msg.addClass('gc-muted-hidden');
    $targetBlock.append($msg);

    // === ЖЁСТКОЕ ДОПОРЯДОЧИВАНИЕ ПО ID (старые сверху, новые снизу) ===
    gcPlaceMessageSorted($msg);

    // плашки скрытых
    if (authorId>0 && gcIsMuted(authorId)) gcEnsureMutedNote($targetBlock, authorId, authorLogin);
    else gcUpdateNotesLayout($targetBlock);

    // автоскролл вниз (кроме «тихой» пакетной догрузки истории)
    var silent = !!window.__gc_silentInsert;
    if (!silent && $targetBlock.hasClass('Active') && _element_scrolling && _element_scrolling.length && _element_scrolling.prop('checked')){
        setTimeout(function(){ $targetBlock[0].scrollTop = $targetBlock[0].scrollHeight; },10);
    }
    return '';
};

/* === Вставка узла в правильное место по data-msg-id (ASC) === */
function gcPlaceMessageSorted($msg){
    var $block=$msg.closest('.Message-Block'); if(!$block.length) return;
    var id=parseInt($msg.attr('data-msg-id')||'0',10)||0;
    var $prev=null;
    $block.children('.Message').not($msg).each(function(){
        var sid=parseInt($(this).attr('data-msg-id')||'0',10)||0;
        if (sid<=id) $prev=$(this); else return false; // дошли до большего — выходим
    });
    if ($prev && $prev[0]!==$msg[0]) $msg.insertAfter($prev);
    else if (!$prev) $msg.prependTo($block);
}

/* ===================== СЕПАРАТОРЫ ДАТ (как было) ===================== */
(function injectDaySepCSS(){
  if (document.getElementById('gc-daysep-css')) return;
  var st=document.createElement('style'); st.id='gc-daysep-css';
  st.textContent = `
    .gc-day-sep{position:relative;margin:8px 10px;text-align:center;font-family:Nunito,Inter,Arial,sans-serif;}
    .gc-day-sep span{display:inline-block;padding:2px 10px;border:1px solid #e6eafe;border-radius:10px;background:#eef3ff;color:#0f172a;font:800 12px/1.2 Nunito,Inter,Arial;}
    .gc-day-sep:before,.gc-day-sep:after{content:"";position:absolute;top:50%;height:1px;background:#e6eafe;width:40%;}
    .gc-day-sep:before{left:8px;} .gc-day-sep:after{right:8px;}
  `;
  document.head.appendChild(st);
})();
function gcFmtDate(ts){ try{ if(!ts||ts<=0) return ''; var d=new Date(ts*1000); return String(d.getDate()).padStart(2,'0')+'.'+String(d.getMonth()+1).padStart(2,'0')+'.'+d.getFullYear(); }catch(_){ return ''; } }
function gcRebuildDaySeps($block){
  if(!$block||!$block.length) return;
  $block.children('.gc-day-sep').remove();
  var lastDay=null;
  $block.children('.Message').each(function(){
    var ts=parseInt($(this).attr('data-msg-ts')||'0',10)||0;
    var day=gcFmtDate(ts); if(!day) return;
    if(day!==lastDay){ $('<div class="gc-day-sep"><span>'+day+'</span></div>').insertBefore(this); lastDay=day; }
  });
}

/* ============================ ИСТОРИЯ ЛС ============================ */
var _gcPmHistory = window._gcPmHistory || (window._gcPmHistory = {});

(function injectHistoryCSS(){
  if (document.getElementById('gc-history-css')) return;
  var st=document.createElement('style'); st.id='gc-history-css';
  st.textContent = `
    .Message-Block .gc-hist-topbar{position:sticky;top:0;z-index:2;background:#f8faff;padding:8px 10px;border-bottom:1px dashed #e6eafe;display:flex;align-items:center;justify-content:center;gap:12px;font-family:Nunito,Inter,Arial,sans-serif;}
    .gc-hist-topbar .gc-hist-btn{border:0;padding:6px 10px;border-radius:10px;background:#eef3ff;font:800 12px;cursor:pointer;}
    .gc-hist-topbar .gc-hist-btn:hover{background:#e2e8ff;}
    .gc-hist-topbar .gc-hist-btn[disabled]{opacity:.55;cursor:default;}
    .gc-hist-topbar .gc-hist-spinner{font:700 12px;color:#6b7280;}
  `;
  document.head.appendChild(st);
})();

function gcGetPmBlock(channelId){
  var $b=$('.Message-Block.Channel_'+channelId);
  if(!$b.length) $b=$("<div/>",{class:"Message-Block Channel_"+channelId,"data-channel":channelId}).appendTo('.Chat .Talk');
  return $b;
}
function gcEnsureHistBar($block, state){
  var $tb=$block.children('.gc-hist-topbar');
  if(!$tb.length){
    $tb=$('<div class="gc-hist-topbar" role="toolbar" aria-label="История ЛС">'+
            '<button type="button" class="gc-hist-btn">Показать старые</button>'+
            '<span class="gc-hist-spinner" style="display:none">Загрузка…</span>'+
          '</div>');
    $block.prepend($tb);
  }
  $tb.toggle(!!state.hasMore);
  $tb.find('.gc-hist-btn').prop('disabled', !!state.busy);
  $tb.find('.gc-hist-spinner').toggle(!!state.busy);
}

this.loadPmHistory = function(privateChannelId, opts){
  opts = opts || {};
  var state = _gcPmHistory[privateChannelId] || { beforeId:0, hasMore:true, busy:false, inited:false };
  if (state.busy) return;

  var $block = gcGetPmBlock(privateChannelId);
  gcEnsureHistBar($block, state);

  // Узнаём минимальный уже показанный id — грузим строго старее
  if (!state.beforeId){
    var minExistingId=0;
    $block.children('.Message').each(function(){
      var m=(this.getAttribute('class')||'').match(/__msg_id_(\d+)/);
      if(m){ var id=parseInt(m[1],10); if(!minExistingId || id<minExistingId) minExistingId=id; }
    });
    if (minExistingId>0) state.beforeId=minExistingId;
  }

  var payload = { chat:'pm_history', channel:privateChannelId, limit: state.inited ? (opts.limit||150) : (opts.limit||300) };
  if (state.beforeId) payload.before_id = state.beforeId;

  state.busy = true; _gcPmHistory[privateChannelId] = state;
  gcEnsureHistBar($block, state);

  var wasActive = $block.hasClass('Active');
  var el   = $block.get(0);
  var oldH = el ? el.scrollHeight : 0;

  if (wasActive) $block.removeClass('Active'); // временно, чтобы не уехал вниз

  // «тихая» вставка — отключаем автоскролл в генераторе
  window.__gc_silentInsert = true;

  $.ajax({
    url: "/do/chat",
    type: "POST",
    dataType: "json",
    data: payload
  }).done(function(info){
    if (!info || !info.history) return;

    // в хронологическом порядке
    var arr=[]; $.each(info.history,function(k,v){ arr.push({id:parseInt(k,10), val:v}); });
    arr.sort(function(a,b){ return a.id - b.id; });

    for (var i=0;i<arr.length;i++){
      var id=arr[i].id, val=arr[i].val;
      var parsedVal=(typeof val==="object")?val:$.parseJSON(val);
      _self._generateMsg(id, parsedVal, false); // вставится и сразу отсортируется
    }

    // сепараторы дат
    gcRebuildDaySeps($block);

    // удерживаем скролл
    if (el){ var newH=el.scrollHeight, diff=newH-oldH; if (diff>0) el.scrollTop = el.scrollTop + diff; }

    state.beforeId = info.next_before_id || 0;
    state.hasMore  = !!info.has_more;
    state.inited   = true;
    _gcPmHistory[privateChannelId] = state;
    gcEnsureHistBar($block, state);
  }).always(function(){
    window.__gc_silentInsert = false;
    state.busy = false; _gcPmHistory[privateChannelId] = state;
    if (wasActive) $block.addClass('Active');
    gcEnsureHistBar($block, state);
  });
};

// Кнопка «Показать старые»
$(document).off('click.gc_hist').on('click.gc_hist', '.gc-hist-topbar .gc-hist-btn', function(){
  var $block=$(this).closest('.Message-Block'); var channel=$block.data('channel');
  if (channel) { window.GameChat.loadPmHistory(channel); }
});

/* ===================== ХЕЛПЕРЫ ДЛЯ ДАТ ===================== */
function pad2(n){ return (n<10?'0':'')+n; }
function tsToDate(ts){
  // ts может быть в секундах или мс
  var ms = (ts > 1e12) ? ts : ts*1000;
  return new Date(ms);
}
function dayKeyFromTS(ts){
  var d = tsToDate(ts);
  return d.getFullYear()+'-'+pad2(d.getMonth()+1)+'-'+pad2(d.getDate());
}
function dayLabelFromTS(ts){
  var d = tsToDate(ts);
  var today = new Date();
  var yest  = new Date(); yest.setDate(today.getDate()-1);
  function sameDay(a,b){ return a.getFullYear()==b.getFullYear() && a.getMonth()==b.getMonth() && a.getDate()==b.getDate(); }
  if (sameDay(d,today)) return 'Сегодня';
  if (sameDay(d,yest))  return 'Вчера';
  return pad2(d.getDate())+'.'+pad2(d.getMonth()+1)+'.'+d.getFullYear();
}

/** Полная пересборка датовых разделителей в блоке (без дублей). */
function gcRebuildDaySeps($block){
  if (!$block || !$block.length) return;
  $block.children('.gc-day-sep').remove();

  var lastKey = null;
  $block.children('.Message').each(function(){
    var ts = parseInt($(this).attr('data-ts')||'0',10);
    if (!ts) return;
    var key = dayKeyFromTS(ts);
    if (key !== lastKey){
      $('<div class="gc-day-sep" data-day="'+key+'"><span>'+dayLabelFromTS(ts)+'</span></div>').insertBefore(this);
      lastKey = key;
    }
  });
}

/** Первый узел-сообщение в блоке (без верхних плашек). */
function gcFirstMessageNode($block){
  return $block.children('.Message').first();
}

/* ================================================================
   МЕНЮ ДЕЙСТВИЙ / ВСТАВКА ЗАГЛУШКИ В ИНПУТ
   ================================================================= */

window.ChatQuoteStore = window.ChatQuoteStore || {};

this._openHelper = function (e, msg_id, msg_text) {
  // ——— снести предыдущие меню
  $('.chatx-menu').remove();

  // CSS меню (один раз)
  (function injectCSS(){
    if (document.getElementById('chatx-css')) return;
    const css = `
    .chatx-menu{
      position:absolute; z-index:1000003; min-width:220px; max-width:80vw;
      background:#fff; border:1px solid #e6eafe; border-radius:10px;
      box-shadow:0 12px 28px rgba(23,35,74,.12); color:#1b2b4f;
      font-family:Nunito,Inter,Arial,sans-serif; overflow:hidden;
      transform:translateY(6px); opacity:.0; transition:.12s ease;
    }
    .chatx-menu.show{ transform:translateY(0); opacity:1 }
    .chatx-head{ padding:6px 10px; font:900 11px/1 Nunito,Inter,Arial; color:#6f7b95; border-bottom:1px solid #eef2ff }
    .chatx-item{ display:flex; align-items:center; gap:8px; padding:8px 10px; cursor:pointer; font:700 13px/1.15 Nunito,Inter,Arial; background:transparent; border:0; width:100%; text-align:left }
    .chatx-item i{ width:14px; text-align:center; opacity:.75 }
    .chatx-item:hover, .chatx-item:focus{ outline:none; background:#eef3ff; color:#0e55b6 }
    .chatx-sep{ height:1px; background:#eef2ff; margin:2px 0 }
    .chatx-danger{ color:#c83535 }
    .chatx-danger:hover, .chatx-danger:focus{ background:#fff3f3; color:#b23b3b }
    `;
    const st=document.createElement('style'); st.id='chatx-css'; st.textContent=css; document.head.appendChild(st);
  })();

  // helpers
  function toInt(v,d){ var n=parseInt(v,10); return Number.isFinite(n)?n:(d||0); }
  function toStr(v,d){ return (v===undefined||v===null)?(d||''):String(v); }

  function getChatInput() {
    var $el = $('#chat_send_desktop:visible').first();
    if (!$el.length) $el = $('#chat_send:visible').first();
    return $el.length ? $el : $();
  }
  function insertAtCaret($el, str) {
    var el = $el.get(0); if (!el) return;
    el.focus();
    if (typeof el.selectionStart === 'number' && typeof el.selectionEnd === 'number') {
      var s = el.selectionStart, e = el.selectionEnd;
      el.value = el.value.slice(0,s)+str+el.value.slice(e);
      el.selectionStart = el.selectionEnd = s + str.length;
    } else if (document.selection) {
      var r = document.selection.createRange(); r.text = str; r.collapse(false); r.select();
    } else el.value += str;
  }
  function getMsgMeta(mid) {
    var $m = $('.__msg_id_' + mid);
    var userLogin='', userGroup='', time='', text='', toLogin='', toGroup='';
    if ($m.length) {
      var $lbl = $m.find('.User .user-link .label').first();
      if ($lbl.length) {
        userLogin = $lbl.text();
        var mm = ($lbl.attr('class')||'').match(/\bu-(\d+)\b/); if (mm) userGroup = mm[1];
      }
      var $time = $m.find('#gameChatMessageTime').first(); if ($time.length) time = $time.text();
      var $post = $m.find('span.Post').first(); if ($post.length) text = $post.text();
      var $to = $m.find('.User .user-arrow').first().next('.label');
      if ($to.length) {
        toLogin = $to.text();
        var mt = ($to.attr('class')||'').match(/\bu-(\d+)\b/); if (mt) toGroup = mt[1];
      }
    }
    if (!text) {
      var mt2 = toStr(msg_text).match(/^([^:]{1,32}):\s*(.*)$/s);
      if (mt2) { if (!userLogin) userLogin = mt2[1]; text = mt2[2]||''; } else { text = toStr(msg_text); }
    }
    return { userLogin, userGroup, time, text, toLogin, toGroup };
  }
  function getAuthor(mid){
    var $m = $('.__msg_id_' + mid);
    var id = parseInt($m.attr('data-author-id') || $m.data('author-id'), 10) || 0;
    var login = '';
    var $lbl = $m.find('.User .user-link .label').first();
    if ($lbl.length) login = $lbl.text();
    return { id:id, login:login||('id'+id) };
  }

  // построение меню
  var $menu = $('<div/>', {'class':'chatx-menu', role:'menu', 'aria-label':'Действия с сообщением'});
  $menu.append('<div class="chatx-head">Действие</div>');

  function addItem(label, icon, fn, extraClass){
    var $btn = $('<button/>', {'class':'chatx-item'+(extraClass?' '+extraClass:''), type:'button', html:'<i class="'+icon+'"></i> '+label, role:'menuitem', tabindex:0});
    $btn.on('click', function(ev){ ev.preventDefault(); close(); fn(ev); });
    $menu.append($btn);
  }
  function addSep(){ $menu.append('<div class="chatx-sep"></div>'); }

  // Ответить с цитатой — в инпут идёт ЗАГЛУШКА «↩ #id @user (time) »
  addItem('Ответить с цитатой', 'far fa-reply', function(){
    var meta = getMsgMeta(msg_id);
    var $inp = getChatInput();

    var short = String(meta.text || '').replace(/\s+/g,' ').trim();
    if (short.length > 80) short = short.slice(0, 76) + '…';

    var id = toInt(msg_id);
    var stub = '↩ #' + id
             + (meta.userLogin ? ' @' + meta.userLogin : '')
             + (meta.time ? ' (' + meta.time + ')' : '')
             + ' ';

    window.ChatQuoteStore[String(id)] = { id:id, user: meta.userLogin||'', time: meta.time||'', content: short };

    if ($inp.length) {
      var prependSpace = '';
      var el = $inp.get(0);
      if (typeof el.selectionStart === 'number') {
        var s = el.selectionStart;
        if (s > 0 && el.value && el.value[s-1] !== ' ') prependSpace = ' ';
      } else if ($inp.val()) {
        prependSpace = ' ';
      }
      insertAtCaret($inp, prependSpace + stub);
    } else {
      try { navigator.clipboard.writeText(stub); } catch(_) {}
    }
  });

  /* <<< НОВОЕ: кнопка «Скрыть сообщения этого автора» >>> */
  addItem('Скрыть сообщения', 'far fa-eye-slash', function(){
    var a = getAuthor(msg_id);
    if (!a.id) return;
    // сохраняем в mute-список
    gcMute(a.id, a.login);
    // прячем все сообщения автора в текущем канале
    var $msg = $('.__msg_id_'+msg_id);
    var $block = $msg.closest('.Message-Block');
    $block.find('.Message[data-author-id="'+a.id+'"]').addClass('gc-muted-hidden');
    // плашка + сводка
    gcEnsureMutedNote($block, a.id, a.login);
    gcUpdateNotesLayout($block);
  });

  addSep();

  // Окно управления скрытыми
  addItem('Скрытые пользователи…', 'far fa-users', function(ev){
    _self._toggleMutedPanel(ev);
  });

  addSep();

  // Удалить/Пожаловаться
  if ($.inArray(parseInt(_userInfo["group"]), [1,2,3,12]) !== -1) {
    addItem('Удалить сообщение', 'far fa-trash-alt', function(){
      _self._action({ type:'console', console:'mdel', msg_id:msg_id, msg_text:msg_text }, function(data){ _self._update(data); });
      $('.__msg_id_'+msg_id).remove();
    }, 'chatx-danger');
  } else {
    addItem('Пожаловаться', 'far fa-flag', function(){
      _self._action({ type:'console', console:'mreport', msg_id:msg_id, msg_text:msg_text });
    });
  }

  addItem('Скопировать текст', 'fas fa-copy', function(){
    try { navigator.clipboard.writeText(toStr(msg_text)); } catch(_){}
  });

  $('body').append($menu);

  // позиционирование у курсора/сообщения
  (function position(){
    var pad=8, vw=window.innerWidth, vh=window.innerHeight;
    var x=vw/2, y=vh/2;
    var ev0 = e && (e.originalEvent||e);
    if (ev0 && (typeof ev0.clientX==='number')) { x=ev0.clientX; y=ev0.clientY; }
    else {
      var r = $('.__msg_id_'+msg_id).get(0)?.getBoundingClientRect();
      if (r) { x = r.left + r.width-6; y = r.top + 18; }
    }
    $menu.css({ left: (x+10+pageXOffset)+'px', top: (y+pageYOffset)+'px' });
    requestAnimationFrame(function(){
      var m=$menu[0].getBoundingClientRect();
      var L = Math.min(Math.max(pad, x+10), vw - m.width - pad);
      var T = Math.min(Math.max(pad, y), vh - m.height - pad);
      $menu.css({ left:(L+pageXOffset)+'px', top:(T+pageYOffset)+'px' }).addClass('show');
    });
  })();

  function close(){ $menu.remove(); $(document).off('.chatx'); }
  setTimeout(function(){
    $(document).on('mousedown.chatx', function(ev){ if(!$(ev.target).closest('.chatx-menu').length) close(); });
    $(document).on('keydown.chatx', function(ev){ if(ev.key==='Escape') close(); });
  },0);
};


    this._upInfo = function(info){ if(info){ _userInfo = $.extend(_userInfo, info); } };

    this._pokeFocus = function (e, id) {
  if (!id || !_poke_list) return;
  var info = _poke_list['p' + id];
  if (!info) return;

  // контейнер попапа в ChatBox
  var $chatBox = $(e.target).closest('.ChatBox');
  if (!$chatBox.length) $chatBox = $('.ChatBox').first();
  if (!_element_poke_info || !_element_poke_info.closest($chatBox).length) {
    if (_element_poke_info) _element_poke_info.remove();
    _element_poke_info = $('<div/>', { 'class': 'window pokeInfo' }).appendTo($chatBox);
  }

  // одноразовые стили в "чистом" стиле карточки
  if (!document.getElementById('pk-chat-card-css')) {
    $('head').append(`
      <style id="pk-chat-card-css">
        .pokeInfo{z-index:99999}
        .pokeInfo .Info{position:relative}
        .pokeInfo .Step.pure{display:flex;align-items:center;gap:8px;margin:6px 0 0 0}
        .pokeInfo .Step.pure .Label{min-width:145px;font-weight:800;color:#2f4374}
        .pokeInfo .Step.pure .Other{font-weight:900;color:#1b2b4f}
        .pokeInfo .AbilityLink{color:#f59e0b;font-weight:900}
        .pokeInfo .AbilityLink:hover{text-decoration:underline}
        .pokeInfo .Stats .Stat .Name{font-weight:800;color:#2f4374}
        .pokeInfo .Stats .Stat .Name.pkUp,  .pokeInfo .Stats .Stat .Count.pkUp{color:#16a34a}
        .pokeInfo .Stats .Stat .Name.pkDown,.pokeInfo .Stats .Stat .Count.pkDown{color:#d14343}
        .pokeInfo .Stats .Progress{position:relative;border-radius:6px;overflow:hidden;background:#e9edf3;height:8px;display:inline-block;vertical-align:middle;width:180px;margin:0 6px}
        .pokeInfo .Stats .Progress .Bar{height:100%;background:#9db4d3}
      </style>
    `);
  }

  // данные
  var ev    = (info['evcounts']||'0,0,0,0,0,0').split(','),
      stat  = (info['stats']||'0,0,0,0,0,0').split(','),
      gen   = (info['gen']  ||'0,0,0,0,0,0').split(','),
      exp   = (info['lvl'] != 100 ? (info['exp1']+' / '+info['exp2']) : 'полное');

  var gender = (info['gender'] == 'Мальчик') ? 'mars' : (info['gender'] == 'Девочка' ? 'venus' : 'genderless');
  var tr_b = 'angle-double-up', tr_n = (info['tren'] ? 'tr'+info['tren'] : '');
  if (info['tren'] == 6) tr_b = 'crown';

  // значки тренировки по статам
  function trenIcon(n){ return (info['tren_stat']==n ? '<i class="trening fas fa-'+tr_b+' '+tr_n+'"></i>' : ''); }

  var form = (info['form'] && info['form'] != "0") ? '_'+info['form'] : '';
  var nS   = (info['type'] == 'normal' ? '' : info['type']);

  // помощь для строк статов
  function statRow(name, idx, natureClass){
    var upClass   = (natureClass === 'Green-Color') ? 'pkUp'   : '';
    var downClass = (natureClass === 'Red-Color')  ? 'pkDown' : '';
    var trenHtml  = trenIcon(idx);
    return ''+
      '<div class="Stat">'+
        '<div class="Name '+upClass+' '+downClass+'">'+name+' '+trenHtml+'</div>'+
        '<div class="Count '+upClass+' '+downClass+'">'+stat[idx]+'</div>'+
        '<div class="Progress" data-title="EV: '+(ev[idx]||0)+' / 126">'+
          '<div class="Bar" style="width:'+(Math.min(126, +ev[idx]||0)/126*100)+'%;"></div>'+
        '</div>'+
      '</div>';
  }

  // HTML
  var html = ''
    + '<div class="Close" onclick="PokeInfoClose()"><i class="fas fa-times"></i></div>'
    + '<div class="Pokemons">'
      + '<div class="Info">'
        + '<div class="Left">'
          + '<div class="PokemonBox">'
            + (info['tren']>=1 ? '<div class="Modif"><i class="trening fas fa-'+tr_b+' '+tr_n+'"></i></div>' : '')
            + '<div class="Image"><img src="/img/pokemons/sprite/'+info['type']+'/'+_pokeNum(info['basenum'])+form+'.gif" alt=""></div>'
            + '<div class="Lvl">'+info['lvl']+'</div>'
            + '<div class="Unik '+info['type']+'-color">'+(nS||'')+'</div>'
            + '<div class="Name '+info['type']+'-color">'
              + '<div class="Text">#'+_pokeNum(info['basenum'])+' '+info['name']+'</div>'
              + '<div class="Sex '+(info['sparka']==0?'':'spar')+'"><i class="fas fa-'+gender+'"></i></div>'
            + '</div>'
            + '<div class="Bars">'
              + '<div class="Bar hp_proggresbar"  data-title="HP: '+info['hp']+' / '+stat[0]+'"><div class="HpBar"   style="width:'+(stat[0]? (info['hp']/stat[0]*100) : 0)+'%;"></div></div>'
              + '<div class="Bar exp_progressbar"  data-title="Опыт: '+exp+'"><div class="ExpBar"  style="width:'+(info['exp2'] ? (info['exp1']/info['exp2']*100) : 0)+'%;"></div></div>'
              + '<div class="Bar happy_progressbar" data-title="Счастье: '+info['happy']+' / 255"><div class="HappyBar" style="width:'+(info['happy']/255*100)+'%;"></div></div>'
            + '</div>'
          + '</div>'
          + '<div class="MoveBox">'+ (info['atk1']||'') + (info['atk2']||'') + (info['atk3']||'') + (info['atk4']||'') + '</div>'
        + '</div>'

        + '<div class="Right">'
          + '<div class="Id Id-trade'+(info['trade']=='true'?'Yes':'No')+'">id'+info['id']+'</div>'

          + '<div class="Info">'

            // ——— ТЕКСТОВЫЕ РЯДЫ (в стиле карточки) ———
            + '<div class="Step pure"><div class="Label">Характер:</div><div class="Other">'+(info['character']||'—')+'</div></div>'

            + '<div class="Step pure"><div class="Label">Способность:</div>'
              + '<div class="Other AbilityLink">'+(info['abl']||'—')+'</div>'
            + '</div>'

            + '<div class="Step pure"><div class="Label">Генокод:</div>'
              + '<div class="Other">h'+gen[0]+'a'+gen[1]+'d'+gen[2]+'s'+gen[3]+'sa'+gen[4]+'sd'+gen[5]+'</div>'
            + '</div>'

            + '<div class="Step pure"><div class="Label">Группа привлекательности:</div>'
              + '<div class="Other">'+(info['sparkaNumber']||'0')+'</div>'
            + '</div>'

            + '<div class="Step pure"><div class="Label">Разведение:</div>'
              + '<div class="Other '+(info['sparka']==0?'Green-Color':'Red-Color')+'">'+(info['sparka']==0?'доступно':'недоступно')+'</div>'
            + '</div>'

            + '<div class="Step pure"><div class="Label">Свободные EV:</div>'
              + '<div class="Other">'+(info['ev']||'0')+'</div>'
            + '</div>'

          + '</div>' // .Info (правый блок)

          + '<div class="MainInfo">'
            + '<div class="Stats">'

              + statRow('Здоровье',     0, '')
              + statRow('Атака',        1, info['har1']||'')
              + statRow('Защита',       2, info['har2']||'')
              + statRow('Скорость',     3, info['har3']||'')
              + statRow('Спец. Атака',  4, info['har4']||'')
              + statRow('Спец. Защита', 5, info['har5']||'')

            + '</div>'
          + '</div>' // .MainInfo

        + '</div>' // .Right
      + '</div>' // .Info
    + '</div>'; // .Pokemons

  _element_poke_info.empty().append(html);

  // тултипы
  try {
    if (window.Tipped && typeof Tipped.create === 'function') {
      // бары
      var hpNode  = _element_poke_info.find('.hp_proggresbar').get(0);
      var expNode = _element_poke_info.find('.exp_progressbar').get(0);
      var hapNode = _element_poke_info.find('.happy_progressbar').get(0);
      if (hpNode)  Tipped.create(hpNode);
      if (expNode) Tipped.create(expNode);
      if (hapNode) Tipped.create(hapNode);

      // EV подсказки на статах
      _element_poke_info.find('.Stats .Progress').each(function(){
        var txt = $(this).attr('data-title') || '';
        Tipped.create(this, txt);
      });

      // id/trade
      var idNode = _element_poke_info.find('.Id').get(0);
      if (idNode) Tipped.create(idNode, ' ' + (info['trade']=="false" ? 'Покемон приручен' : 'Покемон не приручен') + ' ');
    }
  } catch(_) {}

  // позиционирование
  try { _element_poke_info.css(getElCord(e, _element_poke_info, [-25, 6])); } catch(_){}
  _element_poke_info.css('display','block');
  if (e && e.stopImmediatePropagation) e.stopImmediatePropagation();
};



/* ================== Stickers v4 (современный UI, внутр. прокрутка, toggle) ================== */
this._viewSmile = async function (hide) {
  /* ---- инъекция стилей (один раз) ---- */
  (function injectCSS(){
    if (document.getElementById('em4-css')) return;
    const css = `
    :root{
      --em-bg:#f6f8ff; --em-card:#ffffff; --em-br:#e6eafe; --em-text:#1b2b4f; --em-sub:#6f7b95;
      --em-ac:#2f74ff; --em-ac2:#0e55b6; --em-chip:#eef3ff; --em-chip-br:#dbe6ff; --em-shadow:0 18px 40px rgba(23,35,74,.14);
    }
    /* не меняем вашу геометрию окна (.window.smileList), добавляем только оформление внутри */
    .smileList.emx4{ font-family:Nunito,Inter,Arial,sans-serif; color:var(--em-text); background:var(--em-card); border:1px solid var(--em-br); border-radius:14px; overflow:hidden; }
    .em4-head{ display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; background:linear-gradient(180deg,#fff,#f2f6ff); border-bottom:1px solid var(--em-br); }
    .em4-title{ display:flex; align-items:center; gap:10px; font-weight:1000; font-size:14px }
    .em4-handle{ width:30px; height:8px; border-radius:8px; background:var(--em-chip); border:1px solid var(--em-chip-br) }
    .em4-close{ appearance:none; border:1px solid var(--em-br); background:#fff; width:28px; height:28px; border-radius:10px; cursor:pointer; line-height:26px; font-size:16px }
    .em4-close:hover{ box-shadow:0 10px 24px rgba(23,35,74,.12) }

    .em4-tabs{ display:flex; align-items:center; gap:8px; padding:8px 10px; background:#fff; border-bottom:1px solid var(--em-br) }
    .em4-scroll{ appearance:none; border:1px solid var(--em-br); background:#fff; width:28px; height:28px; border-radius:8px; cursor:pointer; line-height:24px; font-weight:900; color:#50608a }
    .em4-rail{ display:flex; gap:8px; overflow:auto; scrollbar-width:none; -ms-overflow-style:none; }
    .em4-rail::-webkit-scrollbar{ display:none }
    .em4-tab{ position:relative; width:46px; height:46px; border-radius:14px; border:1px solid var(--em-chip-br); background:var(--em-chip);
              display:flex; align-items:center; justify-content:center; flex:none; cursor:pointer; transition:.14s ease }
    .em4-tab.active{ border-color:var(--em-ac); box-shadow:0 10px 24px rgba(46,116,255,.18); transform:translateY(-1px) }
    .em4-tab img{ width:38px; height:38px; border-radius:10px }
    .em4-lock{ position:absolute; right:-4px; top:0px; background:#fff8e8; border:1px solid #ffd79c; color:#a05a00; font-size:10px; padding:0 6px; border-radius:999px }

    .em4-body{ display:flex; flex-direction:column; background:var(--em-bg); }
    /* верхняя «панель покупки» — всегда на виду и понятная */
    .em4-buy-top{ position:sticky; top:0; z-index:2; display:none; align-items:center; justify-content:space-between; gap:8px;
                  background:rgba(255,255,255,.96); border-bottom:1px solid var(--em-br); padding:8px 10px }
    .em4-buy-top .note{ font-size:12px; font-weight:900; color:#a05a00 }
    .em4-buy-top .price{ display:inline-flex; align-items:center; gap:8px; font-weight:1000 }
    .em4-buy-top .coin{ width:18px; height:18px; background:url("/img/world/items/little/25.png") center/contain no-repeat; display:inline-block }

    /* прокручиваемая сетка стикеров */
    .em4-scroller{ overflow:auto; max-height:28vh; padding:10px; }
    .em4-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(64px,1fr)); gap:10px }
    .em4-emoji{ width:100%; aspect-ratio:1/1; border:1px solid var(--em-br); background:#fff; border-radius:16px;
                box-shadow:var(--em-shadow); display:flex; align-items:center; justify-content:center; cursor:pointer;
                transition:transform .08s ease, box-shadow .12s ease }
    .em4-emoji img{ width:58px; height:58px }
    .em4-emoji:hover{ transform:translateY(-1px); box-shadow:0 16px 26px rgba(23,35,74,.14) }
    .em4-emoji.locked{ pointer-events:none; opacity:.55 }

    /* нижняя «подсказка/кнопка» — тоже видимая, но компактнее */
    .em4-buy-bottom{ position:sticky; bottom:0; z-index:2; display:none; justify-content:center; padding:8px; background:rgba(255,255,255,.96); border-top:1px solid var(--em-br) }

    .em4-btn{ appearance:none; border:1px solid var(--em-ac);
              background:linear-gradient(180deg,var(--em-ac),var(--em-ac2));
              color:#fff; border-radius:999px; padding:10px 16px; font-weight:1000; cursor:pointer;
              display:inline-flex; align-items:center; gap:10px; font-size:13px; box-shadow:0 16px 28px rgba(46,116,255,.28) }
    .em4-btn[disabled]{ opacity:.7; cursor:default; box-shadow:none }

    @media (max-width:640px){
      .em4-grid{ grid-template-columns:repeat(5,1fr); gap:8px }
      .em4-scroller{ max-height:21vh }
    }`;
    const st = document.createElement('style'); st.id = 'em4-css'; st.textContent = css;
    document.head.appendChild(st);
  })();

  /* ---- наборы и цены ---- */
  const packs = [
    {name:'pack1', price:0}, {name:'pack2', price:0},
    {name:'pack3', price:50}, {name:'pack4', price:50},
    {name:'pack5', price:50}, {name:'pack6', price:50},
  ];

  /* ---- купленные паки ---- */
  let ownedPacks = ['pack1','pack2'];
  try {
    const packsResp = await fetch('/get_emojis.php?action=get_packs');
    const packsArr  = await packsResp.json();
    if (Array.isArray(packsArr)) ownedPacks = packsArr;
    window.userOwnedEmojiPacks = ownedPacks;
  } catch(_) {}

  /* ---- toggle, как в вашей первой версии ---- */
  if (_element_smile_list && _element_smile_list.length) {
    if (_element_smile_list.is(':visible')) {
      _element_smile_list.css('display','none');
    } else if (!hide) {
      _element_smile_list.css('display','flex');
    }
    return;
  }
  if (hide) return;

  if (_element_smile_list) { _element_smile_list.remove(); _element_smile_list = null; }

  /* ---- создаём окно (позиционирование/размеры — ваши, класс .window .smileList) ---- */
  _element_smile_list = $('<div class="window smileList emx4"></div>').appendTo('body');

  /* ---- каркас интерфейса ---- */
  const markup = `
    <div class="em4-head">
      <div class="em4-title"><div class="em4-handle"></div> Стикеры</div>
      <button class="em4-close" aria-label="Закрыть">×</button>
    </div>

    <div class="em4-tabs">
      <button class="em4-scroll left">‹</button>
      <div class="em4-rail" id="em4-rail">
        ${packs.map((p,i)=>`
          <button class="em4-tab ${i===0?'active':''}" data-tab="${p.name}">
            <div class="ico" id="tab-ico-${p.name}"></div>
            ${ownedPacks.includes(p.name)?'':'<div class="em4-lock">🔒</div>'}
          </button>
        `).join('')}
      </div>
      <button class="em4-scroll right">›</button>
    </div>

    <div class="em4-body">
      <div class="em4-buy-top" id="em4-buy-top">
        <div class="note">Набор не приобретён</div>
        <div class="price"><span class="coin"></span><span id="em4-top-price">—</span>
          <button class="em4-btn" id="em4-top-buy" data-pack="" data-price="">Купить</button>
        </div>
      </div>
      <div class="em4-scroller">
        <div class="em4-grid" id="em4-grid"></div>
      </div>
      
    </div>`;
  _element_smile_list.html(markup);

  /* ---- «закрыть» — скрываем (toggle), не уничтожаем ---- */
  _element_smile_list.find('.em4-close').on('click', ()=> _element_smile_list.css('display','none'));

  /* ---- загрузим списки эмодзи для всех паков и проставим иконки вкладок ---- */
  const emojiLists = {};
  for (const pack of packs) {
    let emojis = [];
    try {
      const resp = await fetch(`/get_emojis.php?pack=${pack.name}`);
      const json = await resp.json();
      if (Array.isArray(json)) emojis = json;
    } catch(_) {}
    emojiLists[pack.name] = emojis;

    // иконка таба
    const icoHTML = emojis.length
      ? `<img src="/img/em/${pack.name}/${emojis[0]}" width="38" height="38" alt="">`
      : `<span style="display:inline-block;width:38px;height:38px;background:#eef2ff;border-radius:10px"></span>`;
    const holder = document.getElementById(`tab-ico-${pack.name}`);
    if (holder) holder.innerHTML = icoHTML;
  }

  /* ---- рендер содержимого пакета ---- */
  function showContent(tabName){
    const packObj = packs.find(p=>p.name===tabName) || {price:0};
    const emojis  = emojiLists[tabName] || [];
    const isOwned = ownedPacks.includes(tabName);

    const $grid = _element_smile_list.find('#em4-grid');
    $grid.empty();

    if (emojis.length){
      const lockedClass = isOwned ? '' : 'locked';
      emojis.forEach(emoji=>{
        const nm = emoji.replace(/\.(png|gif)$/,'');
        $grid.append(
          `<div class="em4-emoji ${lockedClass}" data-emoji=":${nm}:" title="${nm}">
             <img src="/img/em/${tabName}/${emoji}" alt="${nm}">
           </div>`
        );
      });
    } else {
      $grid.append(`<div style="grid-column:1/-1;text-align:center;color:#8aa;padding:8px 0">Нет стикеров</div>`);
    }

    // Верхняя панель покупки — ясно даёт понять цену и действие
    const $top = _element_smile_list.find('#em4-buy-top');
    const $bot = _element_smile_list.find('#em4-buy-bottom');
    if (!isOwned && packObj.price > 0){
      $top.css('display','flex');
      $('#em4-top-price').text(packObj.price);
      $('#em4-top-buy').attr({'data-pack':tabName,'data-price':packObj.price});

      $bot.css('display','flex');
      $('#em4-bottom-buy').attr({'data-pack':tabName,'data-price':packObj.price});
    } else {
      $top.hide(); $bot.hide();
    }
  }

  // инициализация — первый таб
  showContent(packs[0].name);

  /* ---- события: вкладки ---- */
  _element_smile_list
    .off('click.em4.tab')
    .on('click.em4.tab', '.em4-tab', function(){
      _element_smile_list.find('.em4-tab').removeClass('active');
      $(this).addClass('active');
      showContent($(this).data('tab'));
    });

  /* ---- прокрутка списка вкладок ---- */
  _element_smile_list
    .off('click.em4.left').on('click.em4.left',  '.em4-scroll.left',  ()=> _element_smile_list.find('#em4-rail').animate({scrollLeft:'-=140'},160))
    .off('click.em4.right').on('click.em4.right', '.em4-scroll.right', ()=> _element_smile_list.find('#em4-rail').animate({scrollLeft:'+=140'},160));

  /* ---- покупка (кнопки сверху и снизу) ---- */
  function buyPack(pack, price, $btn){
    $btn.prop('disabled', true).text('Покупка…');
    fetch(`/get_emojis.php?action=buy&pack=${pack}`, {method:'GET'})
      .then(r=>r.json()).then(async data=>{
        if (String(data.error)==='0'){
          if (window.Game?.notifications?.main) {
            Game.notifications.main('Набор удачно куплен. Для корректного отображения новых смайлов обновите страницу.','success');
          } else alert('Набор куплен!');
          // обновить список купленных
          try {
            const packsResp = await fetch('/get_emojis.php?action=get_packs');
            const packsArr  = await packsResp.json();
            if (Array.isArray(packsArr)) ownedPacks = packsArr;
            window.userOwnedEmojiPacks = ownedPacks;
          } catch(_){}
          // снять «замок» с таба и перерисовать
          _element_smile_list.find(`.em4-tab[data-tab="${pack}"] .em4-lock`).remove();
          showContent(pack);
        } else {
          $btn.prop('disabled', false).text('Купить');
          if (window.Game?.notifications?.main) Game.notifications.main(data.text||'Ошибка покупки!','error'); else alert(data.text||'Ошибка покупки!');
        }
      })
      .catch(()=>{
        $btn.prop('disabled', false).text('Купить');
        if (window.Game?.notifications?.main) Game.notifications.main('Ошибка соединения!','error'); else alert('Ошибка соединения!');
      });
  }

  _element_smile_list
    .off('click.em4.buytop').on('click.em4.buytop',   '#em4-top-buy', function(){ buyPack($(this).data('pack'), $(this).data('price'), $(this)); })
    .off('click.em4.buybot').on('click.em4.buybot', '#em4-bottom-buy', function(){ buyPack($(this).data('pack'), $(this).data('price'), $(this)); });

  /* ---- вставка стикера ---- */
  _element_smile_list
    .off('click.em4.pick')
    .on('click.em4.pick', '.em4-emoji', function(){
      const tab = _element_smile_list.find('.em4-tab.active').data('tab');
      if (!ownedPacks.includes(tab)) return;
      const code = $(this).attr('data-emoji');
      let $input = $('#chat_send_desktop:visible');
      if (!$input.length) $input = $('#chat_send:visible');
      if (!$input.length) $input = $('#chat_send_desktop');
      if (!$input.length) $input = $('#chat_send');
      if ($input.length) {
        smile($input[0], ' ' + code + ' ');
        $input.focus();
      }
      if ($(window).width() < 900) _element_smile_list.css('display','none');
    });
};
/* ================== /Stickers v4 ================== */

    
    if(uInfo){
        _self._upInfo(uInfo);
        _self._start();
    }
};


var GameInfo = function(uInfo){
    var _self = this;

    var _element_userlist_loc = $('.TrainerList'),
        _element_userlist_loc_count = $('.Trainers .Name'),
        _element_sion_info,
        _element_window_offers;

    var _userInfo = {},
        _loaded = false;

    var inputSnowball = null;

    // =========================
    // SOCKET INTEGRATION BLOCK
    // =========================

    // Подключаемся к глобальному GameSocket и подписываемся на игровые события
    if (window.GameSocket && window.GameSocket.socket) {
        // Универсальный обработчик игровых событий
        GameSocket.socket.on("socket_message", function(data) {
            const eventType = Array.isArray(data) ? data[0] : data.type;
            const payload = Array.isArray(data) ? data[2] : data.data;

            switch (eventType) {
                case 'game_update':
                    if (payload) _self._update(payload);
                    break;
                case 'location_users':
                    if (payload) _self._upUsrLoc(payload);
                    break;
                case 'user_info':
                    if (payload) _self._upInfo(payload);
                    break;
                case 'drop_items':
                    if (payload) _self._showDrop(payload);
                    break;
                case 'minus_items':
                    if (payload) _self._showMinus(payload);
                    break;
                case 'offers':
                    // payload: { u123: [{type: 'battle', ...}, ...], ... }
                    if (_self._lastUsers) {
                        _self._upUsrLoc(_self._lastUsers, payload);
                    }
                    break;
                case 'snowball':
                    if (payload) _self._handleSnowball(payload);
                    break;
                // Можно добавить другие типы событий...
                default:
                    // Для отладки (только для Kara)
                    if (window.LOGIN === 'Kara') console.log('[GameInfo.socket_message]', eventType, payload);
            }
        });
    }

    // =========================
    // AJAX/ACTION FUNCTIONS
    // =========================

    this._action = function(data, suc, err, cpl){
        if(data){
            suc = (suc && isFunction(suc) ? suc : function(){});
            err = (err && isFunction(err) ? err : function(){});
            cpl = (cpl && isFunction(cpl) ? cpl : function(){});

            if(_loaded){
                cpl.call(_self, '');
                err.call(_self, '');
                return false;
            }

            _loaded = true;

            $.ajax({
                url: "/do/makasimka",
                type: "POST",
                dataType: "json",
                data: $.extend({
                    'makasimka': 'true'
                }, data),
                success: function(info, textStatus){
                    _loaded = false;

                    // logDrop обработка
                    if(info['logDrop']) _self._showDrop(info['logDrop']);

                    // minusItem обработка
                    if(info['minusItem']) _self._showMinus(info['minusItem']);

                    // Обновление состояния
                    if(info) _self._update(info);

                    // Обработка ошибки
                    if(info['error']){
                        err.call(_self, textStatus);
                        Game.notifications.main(info['error']['text'],'error');
                    }else{
                        suc.call(_self, info, textStatus);
                    }

                    cpl.call(_self, info, textStatus);
                },

                complete: function(){
                    $('.loadWorld').fadeOut(100);
                    _loaded = false;
                },

                error: function(jqXHR, textStatus){
                    _loaded = false;
                    err.call(_self, textStatus);
                    cpl.call(_self, textStatus);
                }
            });
        }
    };

    this._byItem = function(item_id, npc_id, item_count){
        if(item_id > 0 && npc_id > 0 && item_count > 0){
            _self._action({
                'type': 'by_shop',
                'npc': parseInt(npc_id),
                'item': parseInt(item_id),
                'count': parseInt(item_count)
            }, function(resp){
                if(resp){
                    if('by_count' in resp){
                        $('div.__npc_item_'+npc_id).find('span.__count').html(resp['by_count']);
                    }
                    if(resp['by_ok']) Game.notifications.main(resp['by_ok'], 'success');
                    if(resp['by_plus']) Game.notifications.main(resp['by_plus'], 'plus');
                    if(resp['by_minus']) Game.notifications.main(resp['by_minus'], 'minus');
                }
            });
        }else{
            Game.notifications.main('Некорректные данные!', 'error');
        }
    };

    this._byItemWindow = function(npc_id){
        if(npc_id > 0){
            _self._action({
                'type': 'view_shop',
                'npc': parseInt(npc_id)
            }, function(resp){
                if(resp && resp['by_list']){
                    var tpl = '<div class="model"><div class="header">Продавец<span onclick=$(".model").remove();><i class="fas fa-times"></i></span></div>';
                    tpl += '<div class="content-model">';
                    tpl += resp['by_list'];
                    tpl += '</div>';
                    $("body").append(tpl);
                    if (!(window.device && typeof device.mobile === 'function' && device.mobile())) {
                        $('.model').draggabilly({
                            handle: '.header',
                            containment: true
                        });
                    }
                }
            });
        }else{
            Game.notifications.main('Некорректные данные!', 'error');
        }
    };

    // =========================
    // SOCKET EVENT HANDLERS
    // =========================

    this._showDrop = function(logDrop){
        $.each(logDrop, function(key, val){
            if(val['name']){
                Game.notifications.main(
                    '<img src="img/world/items/little/'+(val['id'] || val['num'] || '')+'.png" class="item"> ' +
                    val['name'] + (val['count'] ? ' <b>x'+val['count']+'</b>' : ''),
                    'plus'
                );
            }
        });
    };

    this._showMinus = function(minusItem){
        $.each(minusItem, function(key, val){
            if(val['name']){
                Game.notifications.main(
                    '<img src="img/world/items/little/'+val['id']+'.png" class="item"> ' +
                    val['name']+' <b>x'+val['count']+'</b>',
                    'minus'
                );
            }
        });
    };

    this._handleSnowball = function(data){
        // Можно реализовать реакцию на снежки, например визуализацию/уведомления
        if(data && data['inf_snowball']){
            // Пример: просто вывод уведомления
            Game.notifications.main('Вам доступны снежки! (' + data['inf_snowball'].length + ')', 'info');
        }
    };

    // =========================
    // USER LIST / LOCATION
    // =========================

    this._upUsrLoc = function(info, notice){
        if(info && _element_userlist_loc && _element_userlist_loc.length){
            var count = 0, tpl = [];
            $.each(info, function(key, val){
                ++count;
                if(val['isOffer']){
                    tpl.unshift(_self._generateUsrBlock(val, notice));
                }else{
                    tpl.push(_self._generateUsrBlock(val, notice));
                }
            });
            if(tpl.length){
                _element_userlist_loc.empty().append(tpl);
                _element_userlist_loc_count.html('Тренеров: '+count);
            }
            // Сохраняем последний список тренеров для офферов по сокету
            _self._lastUsers = info;
        }
    };

    // =========================
    // SNOWBALLS
    // =========================

    this._snowball_act = function(e, data, touser, name, val_id){
        _self._action({
            'type': 'snowball',
            'use': (val_id || 1),
            'touser': touser,
            'rand': (data ? 0 : 1),
            'count': (data ? (inputSnowball && inputSnowball.val() > 0 ? inputSnowball.val() : 1) : 1)
        }, function(resp){
            if(resp){
                if(data && resp['inf_snowball']){
                    _self._snowball(e, resp, touser, name);
                }
                Game.notifications.main('Снаряд успешно долетел до цели ('+name+').'+(resp['inf_count'] !== false ? ' Осталось: х'+resp['inf_count'] : '' ),'success');
            }
        });
    };

    this._snowball = function(e, data, touser, name){
        if(data){
            $('.GiveDiv').remove();
            var elm = $('<div />', {'class':'GiveDiv'}), tpl = [], tpl_list = [];
            if(data['inf_snowball']){
                tpl.push('<div id="DivAbout">Выберите снежок'+(name ? ' в '+name : '')+':</b></div>');
                $.each(data['inf_snowball'], function(key, val){
                    if(val['count'] && val['count'] > 0){
                        tpl_list.push(
                            $('<div />', {
                                'class':'PokBtn',
                                'html':val['name']+' x'+val['count'],
                                'click': function(e){
                                    _self._snowball_act(e, data, touser, name, val['id']);
                                }
                            })
                        );
                    }
                });
                if(tpl_list.length <= 0){
                    Game.notifications.main('Сейчас у вас нет комков. Попробуйте отобрать их у диких покемонов.','success');
                    return;
                }else{
                    if(inputSnowball){
                        inputSnowball.remove();
                        inputSnowball = null;
                    }
                    inputSnowball = $('<input />', {
                        'class':'no_hide',
                        'placeholder':'Введите желаемое количество',
                        'type':'number'
                    }).on('focus click', function(e){
                        e.stopImmediatePropagation();
                        e.stopPropagation();
                    });
                    tpl_list.unshift(
                        $('<div />', {
                            'class':'PokBtn no_hide'
                        }).append(
                            inputSnowball
                        )
                    );
                }
            }
            if(tpl.length && tpl_list.length){
                tpl_list.push(
                    $('<div />', {
                        'class':'PokBtn',
                        'html': 'Закрыть'
                    })
                );
                tpl.push(
                    $('<div />', {
                        'class':'wrap'
                    }).append(
                        $('<div />', {
                            'class':'PokList'
                        }).append(
                            tpl_list
                        )
                    )
                );
                elm.appendTo('body');
                elm.empty().append(tpl).css(getElCord(e, elm, [-45, -19]));
            }else{
                Game.notifications.main('Сейчас у вас нет комков. Попробуйте отобрать их у диких покемонов.','success');
            }
        }
    };

    // =========================
    // USER BLOCK GENERATION
    // =========================

    this._generateUsrBlock = function(val, notice) {
        if (val && val['id']) {
            var elm = $('<div />', {
                'id': '_list_user_id_' + val['id'],
                'class': 'Trainer'
            });

            // Кнопка снежка
            if (_userInfo['id'] != val['id']) {
                elm.append(
                    $('<div />', {
                        'class': 'snow-user'
                    }).on('click', function(e) {
                        _self._action({
                            'type': 'snowball',
                            'view': 1,
                            'touser': val['id']
                        }, function(data) {
                            if (data && data['inf_snowball']) {
                                _self._snowball(e, data, val['id'], val['login']);
                            }
                        });
                    })
                );
            }

            // Основная информация пользователя
            elm.append(
                '<div class="user-link"><div onclick="showUserTooltip(\'' + val['id'] + '\')" class="Info-Link sex' + val['sex'] + '"><i class="fa fa-info"></i></div> <div class="u-' + val['group'] + ' label" onclick=user_to_chat_add(\'' + val['id'] + '\')>' + val['login'] + '</div> ' + (val['clan'] || '') + '</div>'
            );

            // Обработка уведомлений
            if (notice && notice['u' + val['id']]) {
                $.each(notice['u' + val['id']], function(key, not) {
                    if (not['type']) {
                        switch (not['type']) {
                            case 'battle':
                                if (!$('.battle-action-notice[data-id="'+not['id']+'"]')[0]) {
                                    let $battleNoty = $(`
                                        <div class="noty info battle-action-notice" data-id="${not['id']}">
                                            <div class="alerten info">
                                                <div class="divIcon"><i class="fa fa-bolt"></i></div>
                                                <div class="divContainer">
                                                    <div class="divContent"><b>${val['login']}</b> бросает вам вызов на бой! Принять вызов?</div>
                                                    <div class="divBtns">
                                                        <button class="btn-yes">Да, принять</button>
                                                        <button class="btn-no">Отклонить</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `);
                                    $battleNoty.find('.btn-yes').on('click', function () {
                                        _self._action({
                                            'type': 'offers',
                                            'offerID': not['id'],
                                            'confirmed': 1
                                        });
                                        $battleNoty.fadeOut(300, function () { $(this).remove(); });
                                        return false;
                                    });
                                    $battleNoty.find('.btn-no').on('click', function () {
                                        _self._action({
                                            'type': 'offers',
                                            'offerID': not['id'],
                                            'confirmed': 0
                                        });
                                        $battleNoty.fadeOut(300, function () { $(this).remove(); });
                                        return false;
                                    });
                                    $('.DivNotification').append($battleNoty);
                                }
                                elm.append(
                                    $('<div />', {
                                        'class': 'bt_battle __interval_effect',
                                        html: '<i class="fa fa-bolt"></i>'
                                    }).on('click', function(e) {
                                        var el_this = $(this);
                                        _self._viewOffers(e, {
                                            'text': '<b>' + val['login'] + '</b> вызывает вас на бой! Принять вызов?',
                                            'yes': function() {
                                                _self._action({
                                                    'type': 'offers',
                                                    'offerID': not['id'],
                                                    'confirmed': 1
                                                });
                                                el_this.remove();
                                                return false;
                                            },
                                            'no': function() {
                                                _self._action({
                                                    'type': 'offers',
                                                    'offerID': not['id'],
                                                    'confirmed': 0
                                                });
                                                el_this.remove();
                                                return false;
                                            }
                                        });
                                    })
                                );
                                break;
                            case 'trade':
                                if (!$('.trade-action-notice[data-id="'+not['id']+'"]')[0]) {
                                    let $tradeNoty = $(`
                                        <div class="noty info trade-action-notice" data-id="${not['id']}">
                                            <div class="alerten info">
                                                <div class="divIcon"><i class="fa fa-handshake"></i></div>
                                                <div class="divContainer">
                                                    <div class="divContent"><b>${val['login']}</b> предлагает вам обмен! Принять предложение?</div>
                                                    <div class="divBtns">
                                                        <button class="btn-yes">Да, принять</button>
                                                        <button class="btn-no">Отклонить</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `);
                                    $tradeNoty.find('.btn-yes').on('click', function () {
                                        _self._action({
                                            'type': 'offers',
                                            'offerID': not['id'],
                                            'confirmed': 1
                                        });
                                        $tradeNoty.fadeOut(300, function () { $(this).remove(); });
                                        return false;
                                    });
                                    $tradeNoty.find('.btn-no').on('click', function () {
                                        _self._action({
                                            'type': 'offers',
                                            'offerID': not['id'],
                                            'confirmed': 0
                                        });
                                        $tradeNoty.fadeOut(300, function () { $(this).remove(); });
                                        return false;
                                    });
                                    $('.DivNotification').append($tradeNoty);
                                }
                                elm.append(
                                    $('<div />', {
                                        'class': 'bt_trade __interval_effect',
                                        html: '<i class="fa fa-handshake"></i>'
                                    }).on('click', function(e) {
                                        var el_this = $(this);
                                        _self._viewOffers(e, {
                                            'text': '<b>' + val['login'] + '</b> предлагает вам обмен! Принять предложение?',
                                            'yes': function() {
                                                _self._action({
                                                    'type': 'offers',
                                                    'offerID': not['id'],
                                                    'confirmed': 1
                                                });
                                                el_this.remove();
                                                return false;
                                            },
                                            'no': function() {
                                                _self._action({
                                                    'type': 'offers',
                                                    'offerID': not['id'],
                                                    'confirmed': 0
                                                });
                                                el_this.remove();
                                                return false;
                                            }
                                        });
                                    })
                                );
                                break;
                        }
                    }
                });
            }

            return elm;
        }
        return '';
    };

    // =========================
    // OFFERS VIEW
    // =========================

    this._viewOffers = function(e, info) {
        if (info && info['close']) {
            if (_element_window_offers && _element_window_offers.length) {
                _element_window_offers.css('display', 'none');
            }
            return;
        }
        // Создаем окно, если не было создано ранее
        if (!_element_window_offers) {
            if (window.device && device.mobile()) {
                _element_window_offers = $('<div />', {
                    'class': 'window offers'
                }).appendTo('#window_games_mobile');
            } else {
                _element_window_offers = $('<div />', {
                    'class': 'window offers'
                }).appendTo('#window_games');
            }
            // Клик вне окна — закрыть
            $('body')
                .off('click._viewOffers')
                .on('click._viewOffers', function () {
                    if (_element_window_offers) {
                        _element_window_offers.css('display', 'none');
                    }
                    $('body').off('click._viewOffers');
                });
        }
        if (_element_window_offers.length && info && info['text']) {
            _element_window_offers.empty().append(
                $('<div />', { 'class': 'off-info' }).append(info['text'])
            ).append(
                $('<div />', { 'class': 'off-btns' }).append(
                    $('<div />', {
                        'class': 'button accept',
                        'html': 'Принять',
                        'on': {
                            'click': function (e) {
                                e.stopPropagation();
                                if (info['yes'] && typeof info['yes'] === 'function') {
                                    if (info['yes'].call(null, e) === false) {
                                        _element_window_offers.css('display', 'none');
                                    }
                                } else {
                                    _element_window_offers.css('display', 'none');
                                }
                            }
                        }
                    }),
                    $('<div />', {
                        'class': 'button decline',
                        'html': 'Отказаться',
                        'on': {
                            'click': function (e) {
                                e.stopPropagation();
                                if (info['no'] && typeof info['no'] === 'function') {
                                    if (info['no'].call(null, e) === false) {
                                        _element_window_offers.css('display', 'none');
                                    }
                                } else {
                                    _element_window_offers.css('display', 'none');
                                }
                            }
                        }
                    })
                )
            );
            _element_window_offers.css('display', 'block');

            // Позиционирование окна: не вылезает за экран
            var ww = $(window).width(), wh = $(window).height();
            var ew = _element_window_offers.outerWidth(), eh = _element_window_offers.outerHeight();
            var coords = { left: 0, top: 0 };

            if (window.device && device.mobile()) {
                _element_window_offers.css({
                    left: '2vw',
                    top: wh * 0.22 + 'px',
                    width: '96vw',
                    maxWidth: '96vw'
                });
            } else {
                var x = (e && e.pageX !== undefined) ? e.pageX - 40 : ww / 2 - ew / 2;
                var y = (e && e.pageY !== undefined) ? e.pageY - 30 : wh / 2 - eh / 2;
                if (x + ew > ww) x = ww - ew - 8;
                if (x < 0) x = 0;
                if (y + eh > wh) y = wh - eh - 8;
                if (y < 0) y = 0;
                coords = { left: x, top: y };
                _element_window_offers.css(coords);
            }
            e && e.stopPropagation();
        }
    };

    // =========================
    // UPDATE & INFO
    // =========================

    this._update = function(data){
        // Здесь обработай обновление состояния игры (location, battle, etc)
        // Можно прокидывать в другие классы/методы
        // Например: if(data.location) window.updateLocation(data.location);
    };

    this._upInfo = function(info){
        if(info){
            _userInfo = $.extend(_userInfo, info);
        }
    };

    // =========================
    // INIT
    // =========================

    if(uInfo){
        _self._upInfo(uInfo);
    }
};

// =========================
// GameBattle (полный; с командным боем/гаунтлетом)
// Совместим с ActionBattle и текущими backend-экшенами
// =========================
var GameBattle = function(info){
  'use strict';

  var _self = this;

  // -----------------------------
  // Helpers (адаптив/совместимость)
  // -----------------------------
  function _isMobile(){
    try {
      if (window.device && typeof device.mobile === 'function') return !!device.mobile();
    } catch(e){}
    try {
      return !!(window.matchMedia && window.matchMedia('(max-width: 768px)').matches);
    } catch(e){}
    return false;
  }

  // -----------------------------
  // Переменные/DOM ссылки
  // -----------------------------
  var _element_window,
      _element_window_wrap,
      _element_window_started,
      _element_window_game = $('.DivWorld'),

      _element_poke_info_my,
      _element_poke_info_my_img,
      _element_poke_info_my_name,
      _element_poke_info_my_lvl,
      _element_poke_info_my_ball,
      _element_poke_info_my_unik,
      _element_poke_info_my_tren,
      _element_poke_info_my_item,
      _element_poke_info_my_move,
      _element_poke_info_my_mega,
      _element_poke_info_my_stat,
      _element_poke_info_my_stat_a,

      _element_poke_info_enemy,
      _element_poke_info_enemy_img,
      _element_poke_info_enemy_name,
      _element_poke_info_enemy_lvl,
      _element_poke_info_enemy_ball,
      _element_poke_info_enemy_unik,
      _element_poke_info_enemy_tren,
      _element_poke_info_enemy_item,
      _element_poke_info_enemy_stat,
      _element_poke_info_enemy_stat_a,
      _element_enemy_ball,

      _element_my_name,
      _element_enemy_name,
      _element_round_time,
      _element_round_time_count,
      _element_round_time_buttons,
      _element_round_count,
      _element_round_weather,
      _element_content_log,
      _element_button_exit;

  // Командный бой — визуальный бейдж и кнопка
  var _element_team_badge = null;
  var _element_team_button = null;

  // ======= Элементы «шапки хода» (как на вашем примере) =======
  var _element_turn_zone = null;
  var _element_turn_team_left = null;
  var _element_turn_team_right = null;
  var _element_turn_center = null;

  // Bars с текстами (для быстрого апдейта)
  var _element_poke_info_my_hp_bar,
      _element_poke_info_my_hp_text,
      _element_poke_info_my_exp_bar,
      _element_poke_info_my_exp_text,
      _element_poke_info_enemy_hp_bar,
      _element_poke_info_enemy_hp_text,
      _element_poke_info_enemy_exp_bar,
      _element_poke_info_enemy_exp_text;

  // -----------------------------
  // Состояние
  // -----------------------------
  var _open = false,
      _selected = false,
      _battle_end = false,
      _data = {};
  var _isTeamBattle = false; // вычисляется из battleInfo.isCommandBattle | battleInfo.team

  // Служебные метки (устойчивость/производительность)
  var _lastUpdateAt = 0;
  var _lastBattleLog = null;


  // -----------------------------
  // UI настройки (Modern v2) + Event Bus (заготовка на будущее)
  // -----------------------------
  var _uiKey = 'pkx_battle_ui_v2';
  var _ui = {
    autoScrollLog: true,
    collapseLog: _isMobile(),
    compactCards: false,
    reduceMotion: false
  };
  (function(){
    try {
      var saved = JSON.parse(localStorage.getItem(_uiKey) || '{}');
      if (saved && typeof saved === 'object') {
        for (var k in saved) {
          if (Object.prototype.hasOwnProperty.call(saved, k)) _ui[k] = saved[k];
        }
      }
    } catch(e){}
  })();
  function _saveUi(){
    try { localStorage.setItem(_uiKey, JSON.stringify(_ui)); } catch(e){}
  }

  // Future-ready: лёгкий event bus (не ломает текущую логику, но даёт хуки на будущее)
  var _evt = {};
  function _emit(name, payload){
    try {
      var list = _evt[name];
      if (!list || !list.length) return;
      for (var i=0; i<list.length; i++){
        try { list[i].call(_self, payload); } catch(e){}
      }
    } catch(e){}
  }
  this.on = function(name, fn){
    if (!name || typeof fn !== 'function') return _self;
    if (!_evt[name]) _evt[name] = [];
    _evt[name].push(fn);
    return _self;
  };
  this.off = function(name, fn){
    if (!name || !_evt[name]) return _self;
    if (!fn) { _evt[name] = []; return _self; }
    _evt[name] = _evt[name].filter(function(f){ return f !== fn; });
    return _self;
  };

  // Для плавного UI: предыдущее значение HP/EXP (для анимаций/дельт)
  var _prevHp = { my: null, enemy: null };
  var _prevExp = { my: null, enemy: null };
  var _prevPokeId = { my: null, enemy: null };
  var _logHasNew = false;

  function _applyRootUi(){
    if(!_element_window) return;
    _element_window.toggleClass('pkx-compact', !!_ui.compactCards);
  }
  function _applyMotionUi(){
    if(!_element_window) return;
    _element_window.toggleClass('pkx-reduce-motion', !!_ui.reduceMotion);
  }
  function _applyLogUi(){
    if(!_element_content_log) return;
    _element_content_log.toggleClass('collapsed', !!_ui.collapseLog);
    _element_content_log.toggleClass('autoscroll-off', !_ui.autoScrollLog);
    _element_content_log.toggleClass('has-new', !!_logHasNew);

    try {
      var $auto = _element_content_log.find('.LogHead .tool.__auto');
      if ($auto.length) $auto.toggleClass('active', !!_ui.autoScrollLog);

      var $col = _element_content_log.find('.LogHead .tool.__collapse');
      if ($col.length) {
        $col.toggleClass('active', !!_ui.collapseLog);

        // иконка: вверх = свернуть, вниз = развернуть
        var $i = $col.find('i');
        if ($i.length) {
          if (_ui.collapseLog) $i.attr('class','fas fa-chevron-down');
          else $i.attr('class','fas fa-chevron-up');
        }
      }

      var $dot = _element_content_log.find('.LogHead .dot');
      if ($dot.length) $dot.toggle(!!_logHasNew);
    } catch(e){}
  }
  function _scrollLogToBottom(){
    if(!_element_content_log || !_element_content_log.length) return;
    try {
      var el = _element_content_log[0];
      el.scrollTop = el.scrollHeight;
    } catch(e){}
    _logHasNew = false;
    _applyLogUi();
    _emit('log_scroll_bottom', {});
  }

  // -----------------------------
  // Hotkeys (не меняют текущую логику, только вызывают существующие клики)
  // 1-4 — атаки, L — свернуть/развернуть лог, End — вниз, S — настройки
  // -----------------------------
  function _installHotkeys(){
    try {
      $(document).off('keydown.pkxBattleHotkeys').on('keydown.pkxBattleHotkeys', function(ev){
        if(!_open) return;
        if(ev.ctrlKey || ev.altKey || ev.metaKey) return;

        var $t = $(ev.target);
        if($t.is('input,textarea,select') || $t.prop('contenteditable') === 'true') return;

        var k = ev.key;

        // 1-4: атаки
        if(k >= '1' && k <= '4'){
          var $mv = $();
          if(_element_poke_info_my_move && _element_poke_info_my_move.length){
            $mv = _element_poke_info_my_move.find('.Move[data-hotkey="'+k+'"]').first();
            if(!$mv.length) $mv = _element_poke_info_my_move.find('.Move').eq(parseInt(k,10) - 1);
          }
          if($mv.length && !$mv.hasClass('Off')){
            ev.preventDefault();
            $mv.trigger('click');
          }
          return;
        }

        // End: к последним сообщениям
        if(k === 'End'){
          ev.preventDefault();
          _scrollLogToBottom();
          return;
        }

        // L: свернуть лог
        if(k === 'l' || k === 'L'){
          ev.preventDefault();
          _ui.collapseLog = !_ui.collapseLog;
          _saveUi();
          _applyLogUi();
          if(!_ui.collapseLog) _scrollLogToBottom();
          return;
        }

        // S: настройки
        if(k === 's' || k === 'S'){
          var $btn = _element_content_log ? _element_content_log.find('.LogHead .tool.__settings') : $();
          if($btn && $btn.length){
            ev.preventDefault();
            $btn.trigger('click');
          }
          return;
        }
      });
    } catch(e){}
  }

  function _showFloat(side, text, cls){
    if(!_ui || _ui.reduceMotion) return;
    var $root = (side === 'enemy') ? _element_poke_info_enemy : _element_poke_info_my;
    if(!$root || !$root.length) return;
    var $box = $root.find('.PokemonBox').first();
    if(!$box.length) return;

    var $layer = $box.children('.pkx-float-layer');
    if(!$layer.length){
      $layer = $('<div/>', {'class':'pkx-float-layer'});
      $box.append($layer);
    }

    var $f = $('<div/>', {'class':'pkx-float ' + (cls||''), text:text});
    $layer.append($f);
    setTimeout(function(){ $f.addClass('go'); }, 10);
    setTimeout(function(){ $f.remove(); }, 1500);
  }



  // Автоподстраховка обновлений: включается только когда нет сокетов и давно не было _update
  var _autoPollTimer = 0;
  var _autoPollEveryMs = 3000;
  var _autoPollThresholdMs = 3500;
  var _autoPollEnabled = true;

  // Таймер истёк (чтобы не спамить check_timer)
  var _turnExpiredSent = false;

  // -----------------------------
  // Плавный таймер (RAF)
  // -----------------------------
  var _turnRAF = null;
  var _turnEndMs = 0;
  var _turnTotalSec = 0;
  var _turnStarted = false;

  var _raf = (window.requestAnimationFrame || function(cb){ return setTimeout(cb, 16); });
  var _caf = (window.cancelAnimationFrame || function(id){ clearTimeout(id); });

  function _stopTurnTimer(){
    _turnStarted = false;
    if (_turnRAF != null) _caf(_turnRAF);
    _turnRAF = null;
  }
  function _renderTurnExpired(){
    if (_element_round_time_count) {
      _element_round_time_count.html('<div class="turn-label">Время соперника истекло.</div>');
    }

    // Визуально показываем истечение времени прямо в «шапке хода»
    try {
      if (_element_turn_center) {
        var $state = _element_turn_center.find('.state');
        if ($state && $state.length) {
          $state
            .removeClass('battle-turn-active battle-turn-wait')
            .addClass('battle-turn-wait');
          $state.find('.title').text('Время истекло');
        }
      }
      var bar = document.getElementById('timer_battle');
      if (bar) bar.style.width = '0%';
      var t = document.getElementById('timer_battle_secs');
      if (t) t.textContent = '0';
    } catch(e){}

    if (_element_round_time_buttons) _element_round_time_buttons.css('display','block');
    $('#cont_timer_battle').remove();
    _stopTurnTimer();

    // check_timer должен уйти один раз на истечение, иначе сервер будет получать дубликаты
    if (!_turnExpiredSent) {
      _turnExpiredSent = true;
      _self._action({'check_timer':1});
    }
  }
  function _startTurnTimer(totalSec, elapsedSec){
    _stopTurnTimer();
    _turnExpiredSent = false;
    _turnTotalSec = Math.max(1, Number(totalSec||0));

    var left = Math.max(0, _turnTotalSec - Math.max(0, Number(elapsedSec||0)));
    _turnEndMs = Date.now() + left*1000;
    _turnStarted = true;

    function formatTime(sec, total){
      sec = Math.max(0, Math.ceil(sec));
      if (total >= 60) {
        var m = Math.floor(sec/60);
        var s = sec % 60;
        return m + ':' + (s < 10 ? '0'+s : s);
      }
      return String(sec);
    }
    function setLabel(secLeft){
      var el = document.getElementById('timer_battle_secs');
      if (el) el.textContent = formatTime(secLeft, _turnTotalSec);
    }

    function frame(){
      var now = Date.now();
      var msLeft = _turnEndMs - now;
      var secsLeft = Math.max(0, msLeft/1000);
      var percent = Math.max(0, Math.min(100, (secsLeft/_turnTotalSec)*100));

      var bar = document.getElementById('timer_battle');
      if (bar) bar.style.width = percent + '%';

      setLabel(secsLeft);

      if (msLeft > 0 && _turnStarted) {
        _turnRAF = _raf(frame);
      } else {
        _turnRAF = null;
        _renderTurnExpired();
      }
    }
    // первичный рендер лейбла
    setLabel(left);
    _turnRAF = _raf(frame);
  }

    // -----------------------------
  // Сокеты (входящие) - интеграция с GameSocket
  // -----------------------------
  var SOCKETS = {
    enabled: true,
    debug: false
  };
  var _battleId = null;
  var _socketHandlers = {};
  var _socketAttached = false;
  var _socketReadyListener = null;

  function slog(){ 
    if(!SOCKETS.debug) return; 
    console.log.apply(console, ['[GameBattle]'].concat([].slice.call(arguments))); 
  }

  function _attachSocket(initialInfo){
    if(!SOCKETS.enabled || !window.GameSocket) {
      slog('GameSocket недоступен или отключен');
      return;
    }

    // Избегаем повторного подключения
    if(_socketAttached) {
      slog('Сокет уже подключен');
      return;
    }

    _battleId = String((initialInfo && initialInfo.id) || (_data && _data.id) || '');
    if(!_battleId) {
      slog('Нет ID битвы для подключения');
      return;
    }

    try {
      slog('Подключение к битве:', _battleId);

      // Проверяем готовность GameSocket
      if (window.GameSocket && window.GameSocket.disabled === true) {
        // В проекте сокеты могут быть намеренно отключены — тогда не держим «ожидание готовности»
        slog('GameSocket.disabled=true — работаем без сокетов (AJAX)');
        return;
      }

      if(!window.GameSocket.isActive || !window.GameSocket.socket) {
        slog('GameSocket не активен, ждем подключения...');

        // Не спамим слушателями (и не держим их после закрытия боя)
        if (_socketReadyListener) return;
        _socketReadyListener = function onSocketReady() {
          try { document.removeEventListener('GameSocketReady', _socketReadyListener); } catch(e){}
          _socketReadyListener = null;
          _attachSocket(initialInfo);
        };
        document.addEventListener('GameSocketReady', _socketReadyListener);
        return;
      }


      // Определяем обработчики событий
      _socketHandlers = {
        'battle_update': function(payload) {
          slog('battle_update', payload);
          if(payload && String(payload.battle_id) === String(_battleId)) {
            if(payload.battleInfo) {
              _self._update(payload.battleInfo);
            } else if(payload.data) {
              _self._update(payload.data);
            } else if(payload) {
              _self._update(payload);
            }
          }
        },

        'battle_timer': function(payload) {
          slog('battle_timer', payload);
          if(payload && String(payload.battle_id) === String(_battleId)) {
            if (typeof payload.endTs !== 'undefined') {
              var total = Number(payload.total || 60);
              var now = Math.floor(Date.now() / 1000);
              var left = Math.max(0, payload.endTs - now);
              _self._viewTime(total, {time: total - left, timeout: {atk: 1}});
            } else {
              _self._viewTime(Number(payload.seconds || 60), {time: Number(payload.elapsed || 0), timeout: {atk: 1}});
            }
          }
        },

        'battle_end': function(payload) {
          slog('battle_end', payload);
          if(payload && String(payload.battle_id) === String(_battleId)) {
            if (payload.battleInfo) _self._update(payload.battleInfo);
            _battle_end = true;
          }
        },

        'battle_team': function(payload) {
          slog('battle_team', payload);
          if(payload && String(payload.battle_id) === String(_battleId)) {
            if (payload.battleInfo) { 
              _self._update(payload.battleInfo); 
            } else if (payload.team) { 
              _data.team = payload.team; 
              if (_self._openTeamModal) {
                _self._openTeamModal(window._extractTeam(_data) || null, _data); 
              }
            }
          }
        },

        // Дополнительные события для совместимости с существующей системой
        'battle_log.givelog': function(payload) {
          if(payload && String(payload.battle_id) === String(_battleId) && payload.log) {
            if (window.ClassBattle && typeof window.ClassBattle._parserLog === 'function') {
              window.ClassBattle._parserLog(payload.log, true, true);
            }
          }
        },

        'status_update.givelog': function(payload) {
          if(payload && String(payload.battle_id) === String(_battleId) && payload.logStatus) {
            if (window.ClassBattle && typeof window.ClassBattle._parserLogStatus === 'function') {
              window.ClassBattle._parserLogStatus(payload.logStatus);
            }
          }
        },

        'battle_time.givetimer': function(payload) {
          if(payload && String(payload.battle_id) === String(_battleId)) {
            if (window.ClassBattle && typeof window.ClassBattle._viewTime === 'function') {
              window.ClassBattle._viewTime(payload.timeLeft, payload.info || {});
            }
          }
        },

        'poke_update.updatepoke': function(payload) {
          if(payload && String(payload.battle_id) === String(_battleId) && payload.info) {
            if (window.ClassBattle && typeof window.ClassBattle._updatePoke === 'function') {
              var el = (payload.side === "my")
                ? window.ClassBattle._element_poke_info_my
                : window.ClassBattle._element_poke_info_enemy;
              window.ClassBattle._updatePoke(el, payload.info, payload.side === "my");
            }
          }
        },

        'team_update.givediv': function(payload) {
          if(payload && String(payload.battle_id) === String(_battleId)) {
            if (window.ClassBattle && typeof window.ClassBattle._viewMyTeam === 'function') {
              window.ClassBattle._viewMyTeam(payload);
            }
          }
        },

        'poke_action_result.givediv': function(payload) {
          if(payload && String(payload.battle_id) === String(_battleId) && payload.message) {
            if(window.GameSocket && typeof window.GameSocket._safeNotify === 'function') {
              window.GameSocket._safeNotify(payload.message, payload.result ? 'success' : 'error');
            }
          }
        }
      };

      // Подписываемся на события через GameSocket
      Object.keys(_socketHandlers).forEach(function(eventName) {
        window.GameSocket.socket.on(eventName, _socketHandlers[eventName]);
      });

      // Уведомляем сервер о входе в битву
      if(window.GameSocket.socket && typeof window.GameSocket.socket.emit === 'function') {
        window.GameSocket.socket.emit('battle_join', {
          battle_id: _battleId,
          user_id: window.USER_ID || '',
          token: window.USER_TOKEN || ''
        });
      }

      _socketAttached = true;
      slog('socket connected to battle:', _battleId);

      try { _stopAutoPoll(); } catch(e){}

    } catch(e) {
      slog('socket error', e);
      _socketAttached = false;
    }
  }

  function _detachSocket(){
    // Если ждали готовности сокета — снимаем ожидание (иначе будут утечки слушателей)
    if (_socketReadyListener) {
      try { document.removeEventListener('GameSocketReady', _socketReadyListener); } catch(e){}
      _socketReadyListener = null;
    }

    if(!_socketAttached) return;

    slog('Отключение от битвы:', _battleId);

    try {
      // Уведомляем сервер о выходе из битвы
      if(window.GameSocket && window.GameSocket.socket && _battleId) {
        window.GameSocket.socket.emit('battle_leave', {
          battle_id: _battleId,
          user_id: window.USER_ID || ''
        });
      }

      // Отписываемся от событий
      if(window.GameSocket && window.GameSocket.socket && _socketHandlers) {
        Object.keys(_socketHandlers).forEach(function(eventName) {
          window.GameSocket.socket.off(eventName, _socketHandlers[eventName]);
        });
      }

      slog('socket disconnected from battle:', _battleId);
    } catch(e) {
      slog('detach socket error', e);
    }

    // Очищаем состояние
    _socketHandlers = {};
    _battleId = null;
    _socketAttached = false;
  }

  // Функция для отправки действий через GameSocket
  function _emitBattleAction(actionData, callback) {
    if(!window.GameSocket || !window.GameSocket.socket || !_battleId || !_socketAttached) {
      slog('Сокет недоступен для отправки действия');
      return false; // fallback на AJAX
    }

    try {
      var payload = {
        battle_id: _battleId,
        user_id: window.USER_ID || '',
        action: actionData,
        timestamp: Date.now()
      };

      slog('Отправка действия через сокет:', payload);
      
      window.GameSocket.socket.emit('battle_action', payload, function(response) {
        slog('Ответ на действие:', response);
        if(typeof callback === 'function') {
          callback(response);
        }
      });

      return true; // успешно отправлено
    } catch(e) {
      slog('Ошибка отправки действия:', e);
      return false; // fallback на AJAX
    }
  }

  
  // -----------------------------
  // Авто-подстраховка обновлений (когда сокетов нет/они отключены)
  // -----------------------------
  function _startAutoPoll(){
    if (!_autoPollEnabled) return;
    if (_autoPollTimer) return;

    _autoPollTimer = setInterval(function(){
      try {
        if (!_open) return;
        if (_socketAttached) return; // при активных сокетах не дублируем
        if (_selected) return;       // не мешаем выбору замены

        var now = Date.now();
        if (_lastUpdateAt && (now - _lastUpdateAt) <= _autoPollThresholdMs) return;

        // Мягкий fallback: дергаем view, только если давно не было _update
        if (typeof _self._refresh === 'function') {
          _self._refresh();
        } else {
          _self._action({ type:'view' }, function(resp){
            if (resp && resp.battleInfo) _self._update(resp.battleInfo);
          });
        }
      } catch(e){}
    }, _autoPollEveryMs);
  }

  function _stopAutoPoll(){
    if (_autoPollTimer) {
      clearInterval(_autoPollTimer);
      _autoPollTimer = 0;
    }
  }

// -----------------------------
  // Транспорт действий (совместимо с ActionBattle)
  // -----------------------------
  this._action = function(data, suc, err, cpl){
    if (typeof ClassInfo === 'undefined' || !ClassInfo || !ClassInfo._action) return;
    data = $.extend({'type':'battle'}, data);
    ClassInfo._action(
      data,
      function(payload){
        var info = payload;
        if (typeof info === 'string') {
          try { info = JSON.parse(info); }
          catch(e){
            if (window.Game && Game.notifications && Game.notifications.main) {
              Game.notifications.main('Ошибка ответа сервера (не JSON). Проверьте логи.', 'error');
            }
            return;
          }
        }
        if(info && info['battleInfo']){
          _self._update(info['battleInfo']);
        }

        if(info && info['error']){
          if (window.Game && Game.notifications && Game.notifications.main) {
            Game.notifications.main(info['text'] || info['error'] || 'Ошибка', 'error');
          }
        }
        if(info && info['notice']){
          if (window.Game && Game.notifications && Game.notifications.main) {
            Game.notifications.main(info['notice'], 'success');
          }
        }
        if(suc && typeof suc === 'function'){
          try { suc.call(_self, info); } catch(e){}
        }
      },
      err,
      cpl
    );
  };

  // -----------------------------
  // Закрытие боя
  // -----------------------------
  this._close = function(){
    if(_open){
      _stopTurnTimer();
      _detachSocket();
      _stopAutoPoll();

      // Чистим всплывающие окна/обработчики, если бой закрыли во время выбора/попапа
      try {
        $(document)
          .off('.pkxBall')
          .off('.pkxPoke')
          .off('.pkxTeam')
          .off('.pkxContext')
          .off('.pkxTeamCfg')
          .off('.pkxTeamModal')
          .off('.pkxBattleHotkeys');
      } catch(e){}
      $('.pkx-context-pop,.BattlePop,.pkx-quick-selector').remove();

      if(_element_window){
        _element_window.empty().remove();
        _element_window = null;
      }
      if(_element_window_game){
        $('#battleMap').remove();
        _element_window_game.find('.DivMap').show();
      }
      _selected = false;
      _data = {};
      _open = false;
      _isTeamBattle = false;
      document.title = 'Poke Kara';
      $('.pkx-team-modal,.pkx-ball-pop,.pkx-poke-pop,.pkx-team-pop,.pkx-teamcfg').remove();
    }
  };

  // -----------------------------
  // Хелперы
  // -----------------------------
  function _extractTeam(obj){
    if (!obj) return null;
    if (obj.team) return obj.team;
    if (obj.other && obj.other.team) return obj.other.team;
    if (obj.isCommandBattle === true || obj.isCommandBattle == 1) return { mode:'gauntlet', sides:{ 1:{}, 2:{} } };
    return null;
  }
  function _loginSpan(login){ return $('<span/>',{text:login||'—'}); }
  function _userRow(login, badge){
    var $r = $('<div/>',{'class':'user'}).append(_loginSpan(login));
    if (badge) $r.append($('<span/>',{text:badge}).css({fontSize:'11px',opacity:.7}));
    return $r;
  }
  function _joinTeam(side){
    _self._action({ action:'join_team', side: Number(side)||1 }, function(resp){
      if (resp && resp.battleInfo){ _self._update(resp.battleInfo); if (_self._openTeamModal) _self._openTeamModal(window._extractTeam(_data)||null,_data);}
      if (window.Game?.notifications?.main) Game.notifications.main('Вы добавлены в очередь стороны '+side+'.','success');
    });
  }

  // -----------------------------
  // Открытие (создание UI)
  // -----------------------------
  this._open = function(data){
    if(_open){ _self._update(data); return; }

    // ===== СТИЛИ =====
    if (!document.getElementById('battle-skin')) {
      var css = `
      .Battle { align-items: flex-start }
      .Battle .Info .Buttons{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
      .Battle .Info .Buttons .buttonFight.Button{
        width:36px;height:36px;padding:0;border-radius:8px;
        background:#eef2f7;border:1px solid #dbe2ea;
        box-shadow:0 1px 0 rgba(0,0,0,.03), inset 0 -2px 0 rgba(0,0,0,.04);
        display:flex;align-items:center;justify-content:center;
        cursor:pointer;transition:transform .06s ease, background .2s ease, box-shadow .2s ease;
      }
      .Battle .Info .Buttons .buttonFight.Button i{font-size:16px;color:#334155;opacity:.92}
      .Battle .Info .Buttons .buttonFight.Button:hover{transform:translateY(-1px);background:#e7edf5;box-shadow:0 2px 8px rgba(0,0,0,.06), inset 0 -2px 0 rgba(0,0,0,.05)}
      .Battle .Info .Buttons .buttonFight.Button.danger{background:#fde8e8;border-color:#f5c2c0}
      .Battle .Info .Buttons .buttonFight.Button.danger i{color:#b91c1c}
      .Battle .Info .Buttons .buttonFight.Button.danger:hover{background:#fbdada}
      .Battle .Info .Buttons .buttonFight.Button.LeaveButton,
      .Battle .Info .Buttons .buttonFight.Button.leave{background:#fff0f1;border-color:#f5c2c0}
      .Battle .Info .Buttons .buttonFight.Button.LeaveButton i,
      .Battle .Info .Buttons .buttonFight.Button.leave i{color:#c2410c}
      .Battle .Info .Buttons .buttonFight.Button.LeaveButton:hover,
      .Battle .Info .Buttons .buttonFight.Button.leave:hover{background:#ffe3e6}

      /* ===== Командный бой: бейдж ===== */
      .battle-team-badge {
        display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;
        background:#e9f2ff;border:1px solid #cfe2ff;color:#0b5ed7;font-weight:600;font-size:12px;cursor:pointer;
      }
      .Battle .Info .Buttons .buttonFight.Button.team { background:#e9f2ff;border-color:#cfe2ff }
      .Battle .Info .Buttons .buttonFight.Button.team i{color:#0b5ed7}
      .Battle .Info .Buttons .buttonFight.Button.team[disabled]{opacity:.6;cursor:default}

      /* ====== Обновлённая «Zone» и шапка хода ====== */
      .Battle > .Content > .Zone{
        min-height: 70px; height:auto; padding: 6px 10px 4px;
        background:#fbfbfb; border:1px solid #e4e4e4; border-bottom:none; border-radius:3px 3px 0 0;
        position:relative; overflow:visible;
      }
      .Battle .TurnBar{
        display:flex; align-items:center; justify-content:space-between;
        background:#f7f9fc; border:1px solid #e2e8f0; border-radius:10px;
        padding:6px 10px; gap:10px; box-shadow:0 2px 8px rgba(0,0,0,.04) inset;
      }
      .Battle .turn-team{display:flex; align-items:center; gap:6px; flex:0 1 45%; overflow:hidden}
      .Battle .turn-team.right{justify-content:flex-end}
      .Battle .turn-slot{
        position:relative; width:36px; height:36px; border-radius:50%;
        background:#fff; border:1px solid #dbe2ea; display:flex;align-items:center;justify-content:center;
        box-shadow:0 1px 2px rgba(0,0,0,.03); flex:0 0 auto; opacity:1;
        transition:transform .08s ease, box-shadow .12s ease, opacity .12s ease;
      }
      .Battle .turn-slot img{ width:30px; height:30px; object-fit:contain; }
      .Battle .turn-slot .hp{ position:absolute; left:4px; right:4px; top:-6px; height:4px; border-radius:3px; background:#e9eef6; overflow:hidden }
      .Battle .turn-slot .hp .in{ height:100%; width:100%; background:#36c97b }
      .Battle .turn-slot.low .hp .in{ background:#eab308 }
      .Battle .turn-slot.dead{ filter:grayscale(1); opacity:.5 }
      .Battle .turn-slot.active{ box-shadow:0 0 0 2px #7b6cff inset }

      .Battle .turn-center{flex:0 1 320px; display:flex; align-items:center; justify-content:center; gap:8px}
      .Battle .turn-center .state{
        min-width:240px; max-width:360px; width:100%;
        background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:6px 8px;
        display:flex; align-items:center; gap:8px; justify-content:center;
      }
      .Battle .turn-center .state .title{font-weight:900; font-size:13px}
      .Battle .turn-center .turn-progress{ position:relative; height:8px; flex:1; border-radius:6px; background:#f0f3f8; overflow:hidden }
      .Battle .turn-center .turn-progress-inner{ height:100%; width:100%; background:#e25a5a }
      .Battle .turn-center .turn-remaining{ font-size:11px; opacity:.8 }

      .Battle .Round{ margin-top:6px; text-align:center; pointer-events:none }
      .Battle .Round .Text{ display:inline-block; font-family:'Hagin','Nunito',sans-serif; font-size:17px; font-weight:700; color:#d34237;
                            background:#fff; padding:6px 14px; border-radius:12px; border:1px solid #e0e0e0; box-shadow:0 2px 6px rgba(0,0,0,.05); }
      .Battle .Round .timeBattle{ display:block; margin-top:4px; font-size:10px; font-family:'Nunito',sans-serif; color:#8b8b8b }

      /* Обновлённый лог */
      .Battle > .Content > .Log{
        background:#ffffff; height: 225px; border:1px solid #e4e4e4; border-top:none; overflow:auto;
      }
      .Battle > .Content > .Log > .Wrap{ padding:8px 10px 12px; }
      .Battle > .Content > .Log > .Wrap > .Step{
        margin:8px 0; border:1px solid #eef1f5; border-radius:12px; padding:10px;
        background:#f8fafc; box-shadow:0 6px 14px rgba(15,23,42,.05);
      }
      .Battle > .Content > .Log > .Wrap > .Step.latest{
        border-color:#c7d2fe; background:#eef2ff; box-shadow:0 10px 22px rgba(79,70,229,.12);
      }
      .Battle > .Content > .Log > .Wrap > .Step > .Round{ font-weight:800; color:#334155; margin-bottom:6px; }
      .Battle > .Content > .Log > .Wrap > .Step > .Process{
        display:flex; flex-wrap:wrap; gap:4px 6px; line-height:1.4; color:#1f2937;
      }
      .Battle > .Content > .Log > .Wrap > .Step > .Process > span{ word-break:break-word; }
      .Battle > .Content > .Log > .Wrap > .Step > .Process:not(:last-child){ margin-bottom:6px; }

      /* team badge в зоне */
      .TeamBadgeWrap.mobile{ display:flex; justify-content:center; margin-top:6px }

      /* Вспомогательные классы для таймера */
      .battle-turn-active .turn-progress-inner{ background:#2ebf6a }
      .battle-turn-wait   .turn-progress-inner{ background:#e25a5a }
      .battle-turn-active .title{ color:#1fa02b }
      .battle-turn-wait   .title{ color:#8a1e1e }
      `;
      $('head').append('<style id="battle-skin">'+css+'</style>');
    }

    if (!document.getElementById('battle-skin-v2')) {
      var css2 = `
      /* ===== Modern Battle UI v2 (scoped) ===== */
      .pkx-battle-v2{
        --pkx-bg:#f4f6fb;
        --pkx-card:#ffffff;
        --pkx-bd:#e2e8f0;
        --pkx-txt:#0f172a;
        --pkx-muted:#64748b;
        --pkx-accent:#2f74ff;
        --pkx-danger:#ef4444;
        font-family: Nunito, Inter, Arial, sans-serif;
        gap:14px;
      }
      .pkx-battle-v2 .PokemonA, .pkx-battle-v2 .PokemonB{ background:transparent; }
      .pkx-battle-v2 .PokemonBox{
        background:var(--pkx-card);
        border:1px solid var(--pkx-bd);
        border-radius:16px;
        padding:12px 10px;
        box-shadow:0 14px 28px rgba(15,23,42,.06);
        position:relative;
        overflow:visible;
        isolation:isolate;
      }
      .pkx-battle-v2 .PokemonBox:before{
        content:'';
        position:absolute;
        width:160px;height:160px;
        right:-70px; top:-70px;
        border-radius:50%;
        background:rgba(47,116,255,.08);
        filter:blur(0px);
      }
      .pkx-battle-v2 .PokemonBox:after{
        content:'';
        position:absolute;
        width:120px;height:120px;
        left:-60px; bottom:-60px;
        border-radius:50%;
        background:rgba(34,197,94,.06);
      }

      .pkx-battle-v2 .PokemonBox:before,
      .pkx-battle-v2 .PokemonBox:after{ z-index:-1; }

      /* HP/EXP bars */
      .pkx-battle-v2 .Bars .Hp,
      .pkx-battle-v2 .Bars .Exp{
        position:relative;
        height:14px;
        border-radius:999px;
        background:#eef2ff;
        border:1px solid rgba(15,23,42,.10);
        overflow:hidden;
      }
      .pkx-battle-v2 .Bars .Hp .HpBar,
      .pkx-battle-v2 .Bars .Exp .ExpBar{
        height:100%;
        border-radius:999px;
        transition:width .35s ease;
      }
      .pkx-battle-v2 .Bars .Hp .HpBar{ background:#22c55e; }
      .pkx-battle-v2 .Bars .Hp .HpBar.mid{ background:#eab308; }
      .pkx-battle-v2 .Bars .Hp .HpBar.low{ background:#ef4444; }
      .pkx-battle-v2 .Bars .Exp .ExpBar{ background:#60a5fa; transition:width .35s ease; }

      .pkx-battle-v2 .HpText, .pkx-battle-v2 .ExpText{
        position:absolute;
        left:0; right:0;
        top:50%;
        transform:translateY(-50%);
        text-align:center;
        font-size:11px;
        font-weight:800;
        color:var(--pkx-txt);
        text-shadow:0 1px 0 rgba(255,255,255,.65);
        pointer-events:none;
      }

      /* Move cards */
      .pkx-battle-v2 .MoveBox{ display:flex; flex-direction:column; gap:8px; margin-top:10px; }
      .pkx-battle-v2 .MoveBox.your-turn{ filter:drop-shadow(0 10px 22px rgba(47,116,255,.12)); }
      .pkx-battle-v2 .MoveBox .Move{
        display:flex; align-items:center; gap:10px;
        padding:8px 40px 8px 10px;
        border-radius:14px;
        border:1px solid var(--pkx-bd);
        background:#fff;
        cursor:pointer;
        transition:transform .08s ease, box-shadow .15s ease, border-color .15s ease, opacity .15s ease;
        position:relative;
      }
      .pkx-battle-v2 .MoveBox .Move:hover{ transform:translateY(-1px); box-shadow:0 12px 20px rgba(15,23,42,.08); border-color:rgba(47,116,255,.35); }
      .pkx-battle-v2 .MoveBox .Move.Off{ opacity:.45; cursor:not-allowed; }
      .pkx-battle-v2 .MoveBox .Move .img{
        width:40px;height:40px;border-radius:12px;
        background:#f1f5f9;border:1px solid rgba(15,23,42,.10);
        display:flex;align-items:center;justify-content:center; flex:0 0 auto;
      }
      .pkx-battle-v2 .MoveBox .Move .img img{ width:24px; height:24px; object-fit:contain; }
.pkx-battle-v2 .MoveBox .Move{ position:relative; padding-right:36px; }
.pkx-battle-v2 .MoveBox .Move .MoveInfo{ display:flex; flex-direction:column; min-width:0; flex:1 1 auto; }
.pkx-battle-v2 .MoveBox .Move .MoveInfo .Name,
.pkx-battle-v2 .MoveBox .Move .nameAtk{
  display:block; font-weight:900; color:var(--pkx-txt); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.pkx-battle-v2 .MoveBox .Move .MoveInfo .PP{ font-size:11px; font-weight:800; color:var(--pkx-muted); margin-top:2px; }
.pkx-battle-v2 .MoveBox .Move .key{
  position:absolute; right:8px; top:8px;
  width:18px; height:18px; border-radius:7px;
  display:flex; align-items:center; justify-content:center;
  background:#fff; border:1px solid rgba(15,23,42,.10);
  font-size:11px; font-weight:900; color:var(--pkx-txt);
}

      .pkx-battle-v2 .MoveBox .Move .nameAtk{ font-weight:900; color:var(--pkx-txt); }
      .pkx-battle-v2 .MoveBox .Move .infos{ display:flex; gap:10px; margin-top:2px; align-items:center; flex-wrap:wrap; }
      .pkx-battle-v2 .MoveBox .Move .infos .pp{ font-size:11px; font-weight:800; color:var(--pkx-muted); }
      .pkx-battle-v2 .MoveBox .Move .infos .type{ display:flex; align-items:center; gap:6px; }
      .pkx-battle-v2 .MoveBox .Move .infos .type .image{ width:18px; height:18px; border-radius:6px; overflow:hidden; border:1px solid rgba(15,23,42,.10); background:#fff; }
      .pkx-battle-v2 .MoveBox .Move .infos .type .val{ font-size:11px; color:var(--pkx-muted); font-weight:800; }

      /* Log head + collapse */
      .pkx-battle-v2 .Content .Log{
        border:1px solid var(--pkx-bd);
        border-top:none;
        border-radius:0 0 14px 14px;
        background:#fff;
        overflow:auto;
        position:relative;
      }
      .pkx-battle-v2 .Content .Log .LogHead{
        position:sticky; top:0; z-index:3;
        display:flex; align-items:center; justify-content:space-between;
        padding:8px 10px;
        background:rgba(248,250,252,.92);
        backdrop-filter: blur(10px);
        border-bottom:1px solid rgba(15,23,42,.08);
      }
      .pkx-battle-v2 .Content .Log .LogHead .ttl{ font-weight:900; color:var(--pkx-txt); font-size:13px; display:flex; align-items:center; gap:8px; }
      .pkx-battle-v2 .Content .Log .LogHead .ttl .dot{ display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--pkx-danger); box-shadow:0 0 0 3px rgba(239,68,68,.12); }
      .pkx-battle-v2 .Content .Log .LogHead .tools{ display:flex; align-items:center; gap:6px; }
      .pkx-battle-v2 .Content .Log .LogHead .tool{
        width:32px; height:32px;
        border-radius:10px;
        border:1px solid rgba(15,23,42,.10);
        background:#fff;
        display:flex; align-items:center; justify-content:center;
        cursor:pointer;
        transition:transform .08s ease, box-shadow .15s ease, border-color .15s ease;
      }
      .pkx-battle-v2 .Content .Log .LogHead .tool:hover{ transform:translateY(-1px); box-shadow:0 10px 18px rgba(15,23,42,.08); border-color:rgba(47,116,255,.35); }
      .pkx-battle-v2 .Content .Log .LogHead .tool.active{ border-color:rgba(47,116,255,.75); box-shadow:0 0 0 3px rgba(47,116,255,.14); }
      .pkx-battle-v2 .Content .Log.collapsed{ height:92px !important; }
      .pkx-battle-v2 .Content .Log.collapsed .Wrap{ max-height:48px; overflow:hidden; }
      .pkx-battle-v2 .Content .Log.has-new .LogHead{ border-bottom-color:rgba(239,68,68,.35); }

      /* Floating deltas */
      .pkx-battle-v2 .pkx-float-layer{ position:absolute; inset:0; pointer-events:none; z-index:5; }
      .pkx-battle-v2 .pkx-float{
        position:absolute;
        left:50%; top:42%;
        transform:translate(-50%,-50%);
        padding:4px 10px;
        border-radius:999px;
        background:rgba(255,255,255,.92);
        border:1px solid rgba(15,23,42,.12);
        box-shadow:0 10px 22px rgba(15,23,42,.16);
        font-weight:900;
        font-size:13px;
        opacity:0;
        white-space:nowrap;
      }
      .pkx-battle-v2 .pkx-float.go{ animation: pkxFloatUp 1.4s ease forwards; }
      .pkx-battle-v2 .pkx-float.dmg{ color:#b91c1c; }
      .pkx-battle-v2 .pkx-float.heal{ color:#15803d; }
      @keyframes pkxFloatUp{
        0%{ opacity:0; transform:translate(-50%,-20%) scale(.96); }
        10%{ opacity:1; }
        100%{ opacity:0; transform:translate(-50%,-140%) scale(1); }
      }

      /* Settings modal */
      .pkx-btl-overlay{ position:fixed; inset:0; background:rgba(15,23,42,.35); z-index:350000; }
      .pkx-btl-settings{ position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); z-index:350001;
        width:360px; max-width:92vw;
        background:#fff; border:1px solid var(--pkx-bd); border-radius:16px;
        box-shadow:0 30px 80px rgba(15,23,42,.25);
        overflow:hidden;
      }
      .pkx-btl-settings .head{ display:flex; align-items:center; justify-content:space-between; padding:10px 12px;
        background:#f8fafc; border-bottom:1px solid rgba(15,23,42,.08);
      }
      .pkx-btl-settings .ttl{ font-weight:900; color:var(--pkx-txt); }
      .pkx-btl-settings .x{ width:32px; height:32px; border-radius:10px; border:1px solid rgba(15,23,42,.10); background:#fff;
        display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:18px; line-height:18px;
      }
      .pkx-btl-settings .body{ padding:10px 12px; display:flex; flex-direction:column; gap:10px; }
      .pkx-btl-settings .row{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
      .pkx-btl-settings .lbl{ font-weight:800; color:#334155; font-size:13px; }
      .pkx-btl-settings .foot{ padding:10px 12px; border-top:1px solid rgba(15,23,42,.08); display:flex; justify-content:flex-end; }
      .pkx-btl-settings .btn{ padding:8px 12px; border-radius:12px; background:var(--pkx-accent); color:#fff; font-weight:900; cursor:pointer; }

      /* switch */
      .pkx-btl-settings .sw{ position:relative; width:44px; height:26px; flex:0 0 auto; }
      .pkx-btl-settings .sw input{ display:none; }
      .pkx-btl-settings .sw .slider{ position:absolute; inset:0; border-radius:999px; background:#e2e8f0; transition:.2s; border:1px solid rgba(15,23,42,.10); }
      .pkx-btl-settings .sw .slider:before{ content:''; position:absolute; width:20px; height:20px; left:3px; top:2px; border-radius:50%;
        background:#fff; box-shadow:0 4px 10px rgba(15,23,42,.15); transition:.2s;
      }
      .pkx-btl-settings .sw input:checked + .slider{ background:rgba(47,116,255,.35); border-color:rgba(47,116,255,.45); }
      .pkx-btl-settings .sw input:checked + .slider:before{ transform:translateX(18px); }

      /* Compact + reduce motion */
      .pkx-battle-v2.pkx-compact .PokemonBox{ padding:10px 9px; border-radius:14px; }
      .pkx-battle-v2.pkx-reduce-motion .HpBar,
      .pkx-battle-v2.pkx-reduce-motion .ExpBar,
      .pkx-battle-v2.pkx-reduce-motion .Move,
      .pkx-battle-v2.pkx-reduce-motion .tool,
      .pkx-battle-v2.pkx-reduce-motion .pkx-float{ transition:none !important; animation:none !important; }

      /* Mobile layout improvements */
      @media (max-width: 768px){
        .pkx-battle-v2{ gap:10px; }
        .pkx-battle-v2 .PokemonBox{ padding:10px 8px; }
        .pkx-battle-v2 .PokemonA .MoveBox{
          display:grid;
          grid-template-columns: repeat(2, minmax(0,1fr));
          gap:10px;
        }
        .pkx-battle-v2 .PokemonA .MoveBox .Move{ padding:10px 8px; }
        .pkx-battle-v2 .PokemonA .MoveBox .Move .infos{ flex-direction:column; align-items:flex-start; gap:4px; }
        .pkx-battle-v2 .Content .Log.collapsed{ height:84px !important; }
      }
      `;
      $('head').append('<style id="battle-skin-v2">'+css2+'</style>');
    }

    
    // Modern v3 skin (визуал ближе к «примеру»)
    if (!document.getElementById('battle-skin-v3')) {
      var css3 = `
      .Battle.pkx-battle-v3{ position:relative; background:linear-gradient(180deg,#f8fafc 0%, #ffffff 70%); }
      .Battle.pkx-battle-v3 .PokemonBox{ background:linear-gradient(180deg,#ffffff 0%, #f8fafc 100%); border:1px solid rgba(148,163,184,.55); box-shadow:0 18px 38px rgba(15,23,42,.06); border-radius:16px; }
      .Battle.pkx-battle-v3 .PokemonBox:before{ background:linear-gradient(120deg,rgba(47,116,255,.07),rgba(34,197,94,.05)); }
      .Battle.pkx-battle-v3 .Content{ border-radius:16px; overflow:visible; box-shadow:0 18px 42px rgba(15,23,42,.06); }
      .Battle.pkx-battle-v3 .Content .Zone{ background:linear-gradient(180deg,#f8fafc 0%, #ffffff 100%); border-bottom:1px solid rgba(148,163,184,.35); }

      /* Turn bar */
      .Battle.pkx-battle-v3 .TurnBar{ background:rgba(255,255,255,.92); border:1px solid rgba(148,163,184,.45); border-radius:14px; padding:6px 10px; box-shadow:0 12px 28px rgba(15,23,42,.05); }
      .Battle.pkx-battle-v3 .turn-center .state{ border:none; background:transparent; padding:0; }
      .Battle.pkx-battle-v3 .turn-center .state .title{
        display:inline-block;
        padding:6px 14px;
        border-radius:999px;
        border:1px solid rgba(211,66,55,.25);
        background:linear-gradient(180deg,#fff 0%, #fff3f2 100%);
        color:#b42318;
        font-weight:900;
        letter-spacing:.6px;
        text-transform:uppercase;
      }
      .Battle.pkx-battle-v3 .battle-turn-wait .turn-center .state .title{
        border-color:rgba(148,163,184,.35);
        background:linear-gradient(180deg,#fff 0%, #f5f7fb 100%);
        color:#475569;
      }
      .Battle.pkx-battle-v3 .turn-center .turn-progress{
        height:6px;
        margin-top:6px;
        background:#f0d4d4;
        border-radius:999px;
        border:1px solid rgba(0,0,0,.06);
        overflow:hidden;
      }
      .Battle.pkx-battle-v3 .turn-center .turn-progress-inner{ height:100%; background:#d34237; }
      .Battle.pkx-battle-v3 .battle-turn-wait .turn-center .turn-progress{ background:#eef2f7; }
      .Battle.pkx-battle-v3 .battle-turn-wait .turn-center .turn-progress-inner{ background:#cbd5e1; }
      .Battle.pkx-battle-v3 .turn-center .turn-remaining{ margin-top:4px; font-size:11px; color:#6b7280; }

      /* team mini-slots like in screenshot */
      .Battle.pkx-battle-v3 .turn-team{ gap:6px; }
      .Battle.pkx-battle-v3 .turn-team .turn-slot{
        width:24px; height:24px; border-radius:8px;
        border:1px solid rgba(148,163,184,.45);
        background:#fff;
        box-shadow:0 8px 16px rgba(15,23,42,.04);
      }
      .Battle.pkx-battle-v3 .turn-team .turn-slot img{ width:20px; height:20px; }
      .Battle.pkx-battle-v3 .turn-team .turn-slot .hp{
        top:auto; bottom:-5px; left:3px; right:3px; height:4px;
        border-radius:999px;
        border:1px solid rgba(15,23,42,.10);
        background:#eef2f7;
        overflow:hidden;
      }
      .Battle.pkx-battle-v3 .turn-team .turn-slot .hp .in{ background:#22c55e; }
      .Battle.pkx-battle-v3 .turn-team .turn-slot.low .hp .in{ background:#ef4444; }
      .Battle.pkx-battle-v3 .turn-team .turn-slot.active{ border-color:rgba(211,66,55,.55); box-shadow:0 0 0 3px rgba(211,66,55,.14),0 10px 22px rgba(15,23,42,.06); }

      /* Moves */
      .Battle.pkx-battle-v3 .MoveBox{ margin-top:-18px; gap:10px; }
      .Battle.pkx-battle-v3 .MoveBox .Move{
        position:relative;
        padding:8px 40px 8px 10px;
        border-radius:14px;
        background:linear-gradient(180deg,#f8fafc 0%, #eef2f7 100%);
        border:1px solid rgba(148,163,184,.45);
        box-shadow:0 10px 22px rgba(15,23,42,.06);
        display:flex;
        align-items:center;
        gap:10px;
        user-select:none;
      }
      .Battle.pkx-battle-v3 .MoveBox .Move .key{
        position:absolute;
        right:8px; top:8px;
        width:20px; height:20px;
        border-radius:8px;
        display:flex; align-items:center; justify-content:center;
        background:#fff;
        border:1px solid rgba(148,163,184,.45);
        color:#0f172a;
        font-weight:900;
        font-size:12px;
        box-shadow:0 10px 16px rgba(15,23,42,.06);
      }
      .Battle.pkx-battle-v3 .MoveBox .Move .img{
        width:46px; height:46px;
        border-radius:12px;
        background:#fff;
        border:1px solid rgba(148,163,184,.45);
        display:flex; align-items:center; justify-content:center;
        box-shadow:inset 0 -2px 0 rgba(15,23,42,.04);
        flex:0 0 auto;
      }
      .Battle.pkx-battle-v3 .MoveBox .Move .img img{ width:28px; height:28px; object-fit:contain; }
      .Battle.pkx-battle-v3 .MoveBox .Move .MoveInfo{ display:flex; flex-direction:column; min-width:0; flex:1 1 auto; }
      .Battle.pkx-battle-v3 .MoveBox .Move .MoveInfo .Name,
      .Battle.pkx-battle-v3 .MoveBox .Move .nameAtk{
        display:block; font-weight:900; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#0f172a;
      }
      .Battle.pkx-battle-v3 .MoveBox .Move .MoveInfo .PP{ margin-top:3px; font-size:12px; font-weight:800; color:#6b7280; }
      .Battle.pkx-battle-v3 .MoveBox .Move.pp-low .MoveInfo .PP{ color:#b42318; }
      .Battle.pkx-battle-v3 .MoveBox .Move.Off{ opacity:.45; cursor:not-allowed; transform:none; }
      .Battle.pkx-battle-v3 .MoveBox .Move:hover{ transform:translateY(-1px); border-color:rgba(211,66,55,.40); box-shadow:0 14px 28px rgba(15,23,42,.10); }

      /* Log */
      .Battle.pkx-battle-v3 .Content .Log{
        border:1px solid rgba(148,163,184,.35);
        border-radius:16px;
        background:linear-gradient(180deg,#ffffff 0%, #f8fafc 100%);
      }
      .Battle.pkx-battle-v3 .Content .Log .LogHead{ background:rgba(255,255,255,.85); }
/* Surrender button — вернуть в панель кнопок (как было) */
.Battle.pkx-battle-v3 .buttonFight.Button.danger{
  position:static !important;
  left:auto !important;
  bottom:auto !important;
  transform:none !important;
  width:36px !important;
  height:36px !important;
  border-radius:8px !important;
  padding:0 !important;
  z-index:auto !important;
  justify-content:center;
  gap:0;
  box-shadow:0 1px 0 rgba(0,0,0,.03), inset 0 -2px 0 rgba(0,0,0,.04) !important;
  background:#fde8e8 !important;
  border:1px solid #f5c2c0 !important;
}
.Battle.pkx-battle-v3 .buttonFight.Button.danger .txt{ display:none !important; }



      @media (max-width: 768px){
        .Battle.pkx-battle-v3{ padding-bottom:0; }
        .Battle.pkx-battle-v3 .MoveBox{ margin-top:-10px; }        .Battle.pkx-battle-v3 .MoveBox .Move{ padding:10px 10px; }
        .Battle.pkx-battle-v3 .MoveBox .Move .MoveInfo .Name{ font-size:13px; }
        .Battle.pkx-battle-v3 .MoveBox .Move{ padding:8px 38px 8px 10px; }
        .Battle.pkx-battle-v3 .MoveBox .Move .MoveInfo .Name{ white-space:normal; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

      }
      `;
      $('head').append('<style id="battle-skin-v3">'+css3+'</style>');
    }
// ===== /СТИЛИ =====

    if(_element_window_game && _element_window_game.length){
      _open = true;
      _installHotkeys();
      if(window.UserAudio == 1){
        var a = $('#battleAudio')[0];
        if (a && a.play) { try { a.play(); } catch(e){} }
      }
      if($('.TopWorldMobile .Right i').attr('class') == 'fa fa-map-signs') {
        $('.TopWorldMobile .Right i').css('color','#9e3e3e');
        $('.TopWorldMobile .Right span').css('color','#9e3e3e');
      }
      document.title = 'Poke Kara - Бой!';

      _element_window = $('<div />', {'class':'Battle pkx-battle-v2 pkx-battle-v3'}).css('display', 'none');
      _applyRootUi();
      _applyMotionUi();
      _element_window_started = $('<div />', {'class':'started', 'style':'display:none'});
      _element_window_wrap = $('<div />', {'class':'DivMap', 'id':'battleMap'});

      // ---------- MY ----------
      _element_poke_info_my_img = $('<div />', {'class':'imgPok Image blank'});
      _element_poke_info_my_name = $('<div />', {'class':'namePokemon Name __name','html':''});
      _element_poke_info_my_lvl  = $('<div />', {'class':'lvlPokemonOne Lvl __lvl','html':''});

      // Контекстное меню у точки клика (с возможностью смены быстрого предмета)
      _element_poke_info_my_ball = $('<div />', {'class':'ballPokemonOne Ball __ball'})
        .click(function(e){
          e.preventDefault();
          e.stopPropagation();
          
          // Удаляем все существующие попапы
          $('.pkx-context-pop, .BattlePop').remove();
          
          // Инжектим CSS только один раз
          (function injectContextCSS(){
            if (document.getElementById('pkx-context-css')) return;
            var css =
              '.pkx-context-pop{position:fixed;z-index:300000;left:0;top:0;transform:none;min-width:240px;width:240px;max-width:92vw;background:#fff;border:1px solid #e6eafe;border-radius:14px;box-shadow:0 18px 46px rgba(23,35,74,.18),0 3px 10px rgba(106,98,140,.12);font-family:Nunito,Inter,Arial,sans-serif;color:#1b2b4f;overflow:hidden}'+
              '.pkx-context-head{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f0eef9;border-bottom:1px solid #d7d3ea}'+
              '.pkx-context-title{font-weight:800;color:#6b5aa3;font-size:14px}'+
              '.pkx-context-title i{font-size:14px}'+
              '.pkx-context-close{border:1px solid #d7d3ea;background:#f7f6fd;border-radius:9px;width:26px;height:26px;line-height:24px;text-align:center;color:#8f76c1;font-weight:900;cursor:pointer}'+
              '.pkx-context-close:hover{background:#ece8fb}'+
              '.pkx-context-body{padding:6px}'+
              '.pkx-context-actions{display:flex;flex-direction:column;gap:2px}'+
              '.pkx-context-btn{display:flex;align-items:center;gap:10px;width:100%;border:1px solid #e6eafe;background:#fff;border-radius:10px;padding:10px 12px;cursor:pointer;text-align:left;font-size:13px;transition:all 0.2s ease;position:relative}'+
              '.pkx-context-btn:hover{border-color:#2f74ff;background:#f8f9ff}'+
              '.pkx-context-btn .ico{color:#6b5aa3;font-size:14px;width:16px;text-align:center;flex-shrink:0}'+
              '.pkx-context-btn .item-ico{width:24px;height:24px;flex-shrink:0;border-radius:6px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;overflow:hidden}'+
              '.pkx-context-btn .item-ico img{max-width:20px;max-height:20px;object-fit:contain}'+
              '.pkx-context-btn .txt{color:#333;font-weight:normal;flex:1}'+
              '.pkx-context-btn .change-btn{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:20px;height:20px;border-radius:50%;background:#ffa726;border:none;color:#fff;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;z-index:1}'+
              '.pkx-context-btn .change-btn:hover{background:#ff9800;transform:translateY(-50%) scale(1.1)}'+
              '.pkx-context-btn.quick-item{background:#f0f8ff;border-color:#b3d9ff;padding-right:35px}'+
              '.pkx-context-btn.quick-item:hover{background:#e6f3ff;border-color:#66c2ff}'+
              '.pkx-context-btn.quick-item .ico{color:#1976d2}'+
              '.pkx-context-btn.set-quick{background:#fff8e1;border-color:#ffcc02;border-style:dashed}'+
              '.pkx-context-btn.set-quick:hover{background:#fff3c4;border-color:#ffa000}'+
              '.pkx-context-btn.set-quick .ico{color:#f57c00}'+
              '.pkx-context-btn.set-quick .txt{color:#e65100;font-style:italic}'+
              '.pkx-quick-selector{width:280px!important;max-width:90vw!important}'+
              '.pkx-quick-body{padding:4px!important;max-height:300px;overflow-y:auto}'+
              '.pkx-quick-actions{gap:1px!important}'+
              '.pkx-quick-actions .pkx-context-btn{padding:8px 10px!important;font-size:12px!important}'+
              '.pkx-quick-actions .item-ico{width:20px!important;height:20px!important}'+
              '.pkx-quick-actions .item-ico img{max-width:16px!important;max-height:16px!important}';
            var st = document.createElement('style');
            st.id = 'pkx-context-css';
            st.type = 'text/css';
            st.appendChild(document.createTextNode(css));
            document.head.appendChild(st);
          })();

          var $pop = $('<div/>', {'class':'pkx-context-pop'});
          
          // Заголовок
          var $head = $('<div/>', {'class':'pkx-context-head'}).appendTo($pop);
          $('<div/>', {'class':'pkx-context-title'})
            .append('<i class="fas fa-cog"></i>')
            .append('<span> Действия с покемоном</span>')
            .appendTo($head);
          $('<button/>', {'type':'button','class':'pkx-context-close','html':'×'})
            .appendTo($head)
            .on('click', function(ev){ 
              ev.stopPropagation(); 
              $pop.remove(); 
              $(document).off('.pkxContext'); 
            });

          // Тело меню
          var $body = $('<div/>', {'class':'pkx-context-body'}).appendTo($pop);
          var $actions = $('<div/>', {'class':'pkx-context-actions'}).appendTo($body);

          // Кнопка замены покемона
          var $swapBtn = $('<button/>', {'class':'pkx-context-btn', 'type':'button'})
            .append(
              $('<i/>', {'class':'ico fas fa-sync-alt'}),
              $('<span/>', {'class':'txt', text:'Заменить покемона'})
            )
            .on('click', function(ev){
              ev.stopPropagation();
              if (_data && _data['myTarget']) {
                _selected = true;
                _self._viewMyTeam(
                  (_data['myTeam'] || null), 
                  _data['myTarget']['id'], 
                  'Выберите покемона для замены:', 
                  ev, 
                  1
                );
              }
              $pop.remove();
              $(document).off('.pkxContext');
            });

          // Кнопка инвентаря
          var $inventoryBtn = $('<button/>', {'class':'pkx-context-btn', 'type':'button'})
            .append(
              $('<i/>', {'class':'ico fas fa-briefcase'}),
              $('<span/>', {'class':'txt', text:'Использовать инвентарь'})
            )
            .on('click', function(ev){
              ev.stopPropagation();
              _self._openBall(ev);
              $pop.remove();
              $(document).off('.pkxContext');
            });

          // Получаем быстрый предмет
          var quickItem = window.localStorage ? localStorage.getItem('battle_quick_item') : null;
          var quickItemData = null;
          
          if (quickItem) {
            try {
              quickItemData = JSON.parse(quickItem);
            } catch(e) {
              quickItemData = null;
            }
          }

          // Добавляем основные кнопки
          $actions.append($swapBtn, $inventoryBtn);

          // Кнопка быстрого предмета (если установлен)
          if (quickItemData && quickItemData.id && quickItemData.name) {
            var itemImageUrl = '/img/world/items/little/' + (quickItemData.item_id || quickItemData.id) + '.png';
            
            var $quickBtn = $('<button/>', {'class':'pkx-context-btn quick-item', 'type':'button'})
              .append(
                $('<div/>', {'class':'item-ico'}).append(
                  $('<img/>', {'src': itemImageUrl, 'alt': quickItemData.name, 'onerror': 'this.style.display="none"'})
                ),
                $('<span/>', {'class':'txt', text: quickItemData.name}),
                $('<button/>', {'class':'change-btn', 'type':'button', 'title':'Сменить быстрый предмет'})
                  .append('<i class="fas fa-edit"></i>')
                  .on('click', function(ev){
                    ev.stopPropagation();
                    ev.preventDefault();
                    _openQuickItemSelector(ev);
                    $pop.remove();
                    $(document).off('.pkxContext');
                  })
              )
              .on('click', function(ev){
                // Проверяем, не был ли клик по кнопке смены
                if ($(ev.target).closest('.change-btn').length) {
                  return;
                }
                
                ev.stopPropagation();
                
                // Используем быстрый предмет
                try {
                  _self._action({ 'catch': quickItemData.id });
                } catch(e) {
                  console.error('Ошибка использования быстрого предмета:', e);
                }
                
                $pop.remove();
                $(document).off('.pkxContext');
              });
            
            $actions.append($quickBtn);
          } else {
            // Кнопка установки быстрого предмета с иконкой предмета по умолчанию
            var $setQuickBtn = $('<button/>', {'class':'pkx-context-btn set-quick', 'type':'button'})
              .append(
                $('<div/>', {'class':'item-ico'}).append(
                  $('<img/>', {'src': '/img/world/items/little/4.png', 'alt': 'Покебол', 'onerror': 'this.style.display="none"'})
                ),
                $('<span/>', {'class':'txt', text:'Установить быстрый предмет'})
              )
              .on('click', function(ev){
                ev.stopPropagation();
                _openQuickItemSelector(ev);
                $pop.remove();
                $(document).off('.pkxContext');
              });
            
            $actions.append($setQuickBtn);
          }

          // Добавляем в DOM
          $('body').append($pop);

          // Позиционирование возле точки клика
          try{
            var vx = (e.clientX != null ? e.clientX : e.pageX);
            var vy = (e.clientY != null ? e.clientY : e.pageY);
            
            requestAnimationFrame(function(){
              var w = $pop.outerWidth();
              var h = $pop.outerHeight();
              var x = vx + 12;
              var y = vy + 12;
              var mx = (window.innerWidth || document.documentElement.clientWidth) - 10;
              var my = (window.innerHeight || document.documentElement.clientHeight) - 10;
              
              if (x + w > mx) x = Math.max(10, mx - w);
              if (y + h > my) y = Math.max(10, my - h);
              
              $pop.css({left: x + 'px', top: y + 'px'});
            });
          } catch(_) {}

          // Закрытие меню
          setTimeout(function(){
            $(document).on('mousedown.pkxContext', function(ev){
              if (!$(ev.target).closest('.pkx-context-pop').length) {
                $pop.remove();
                $(document).off('.pkxContext');
              }
            });
          }, 20);
          
          $pop.on('keydown', function(ev){
            if(ev.key === 'Escape'){ 
              $pop.remove(); 
              $(document).off('.pkxContext'); 
            }
          });
        });

      // Функция для выбора быстрого предмета
      function _openQuickItemSelector(e) {
        if (typeof ClassInfo !== 'undefined' && ClassInfo && typeof ClassInfo._action === 'function') {
          ClassInfo._action({ type:'items', cat:'ball' }, (info) => {
            if (info && info.ballList) { 
              _showQuickItemList(e, info.ballList); 
            }
          });
        }
      }

      // Показать список для выбора быстрого предмета (компактное окно со скроллом)
      function _showQuickItemList(e, data) {
        $('.pkx-quick-selector').remove();

        var $pop = $('<div/>', {'class':'pkx-quick-selector pkx-context-pop'});
        
        // Заголовок
        var $head = $('<div/>', {'class':'pkx-context-head'}).appendTo($pop);
        $('<div/>', {'class':'pkx-context-title'})
          .append('<i class="fas fa-bolt"></i>')
          .append('<span> Выберите быстрый предмет</span>')
          .appendTo($head);
        $('<button/>', {'type':'button','class':'pkx-context-close','html':'×'})
          .appendTo($head)
          .on('click', function(ev){ 
            ev.stopPropagation(); 
            $pop.remove(); 
            $(document).off('.pkxQuick'); 
          });

        // Тело меню с прокруткой
        var $body = $('<div/>', {'class':'pkx-context-body pkx-quick-body'}).appendTo($pop);
        var $actions = $('<div/>', {'class':'pkx-context-actions pkx-quick-actions'}).appendTo($body);

        // Кнопка сброса
        var $resetBtn = $('<button/>', {'class':'pkx-context-btn', 'type':'button'})
          .append(
            $('<i/>', {'class':'ico fas fa-times'}),
            $('<span/>', {'class':'txt', text:'Убрать быстрый предмет'})
          )
          .on('click', function(ev){
            ev.stopPropagation();
            if (window.localStorage) {
              localStorage.removeItem('battle_quick_item');
            }
            $pop.remove();
            $(document).off('.pkxQuick');
          });
        
        $actions.append($resetBtn);

        // Список предметов с картинками
        if (data && data.length) {
          $.each(data, function(i, val) {
            var itemImageUrl = '/img/world/items/little/' + (val.item_id || val.id) + '.png';
            
            var $item = $('<button/>', {'class':'pkx-context-btn', 'type':'button'})
              .append(
                $('<div/>', {'class':'item-ico'}).append(
                  $('<img/>', {'src': itemImageUrl, 'alt': val.name, 'onerror': 'this.style.display="none"'})
                ),
                $('<span/>', {'class':'txt', text: val.name + ' (x' + val.count + ')'})
              )
              .on('click', function(ev){
                ev.stopPropagation();
                
                // Сохраняем как быстрый предмет
                if (window.localStorage) {
                  try {
                    localStorage.setItem('battle_quick_item', JSON.stringify({
                      id: val.id,
                      name: val.name,
                      item_id: val.item_id || val.id,
                      count: val.count || 1,
                      timestamp: Date.now()
                    }));
                    
                    // Показываем уведомление об успешной смене
                    if (window.GameSocket && typeof window.GameSocket._safeNotify === 'function') {
                      window.GameSocket._safeNotify('Быстрый предмет изменен на: ' + val.name, 'success');
                    }
                  } catch(e) {
                    console.error('Не удалось сохранить быстрый предмет:', e);
                    if (window.GameSocket && typeof window.GameSocket._safeNotify === 'function') {
                      window.GameSocket._safeNotify('Ошибка сохранения быстрого предмета', 'error');
                    }
                  }
                }
                
                $pop.remove();
                $(document).off('.pkxQuick');
              });
            
            $actions.append($item);
          });
        }

        // Добавляем в DOM
        $('body').append($pop);

        // Позиционирование
        try{
          $pop.css({
            left: '50%',
            top: '50%',
            transform: 'translate(-50%, -50%)'
          });
        } catch(_) {}

        // Закрытие меню
        setTimeout(function(){
          $(document).on('mousedown.pkxQuick', function(ev){
            if (!$(ev.target).closest('.pkx-quick-selector').length) {
              $pop.remove();
              $(document).off('.pkxQuick');
            }
          });
        }, 20);
        
        $pop.on('keydown', function(ev){
          if(ev.key === 'Escape'){ 
            $pop.remove(); 
            $(document).off('.pkxQuick'); 
          }
        });
      }
      _element_poke_info_my_unik = $('<div />', {'class':'unikPokemonOne Unik __unik','html':''});
      _element_poke_info_my_item = $('<div />', {'class':'itemPokemonOne Item __item'});
      _element_poke_info_my_move = $('<div />', {'class':'MoveBox'});
      _element_poke_info_my_mega = $('<div />', {'class':'MegaBox'});
      _element_poke_info_my_tren = $('<div />', {'class':'Modif _modif_my'});
      _element_my_name = $('<div />', {'class':'Left'});

      // HP/EXP (с текстом поверх)
      _element_poke_info_my_hp_bar = $('<div />', {'class':'HpBar __hpW', 'style':'width:100%;'});
      _element_poke_info_my_hp_text = $('<div />', {'class':'HpText'})
        .css({'position':'absolute','width':'100%','text-align':'center','font-weight':'bold','color':'#fff','pointer-events':'none'});
      var my_hp_bar_wrap = $('<div />', {'class':'Bar hp_proggresbar', 'data-title':''})
        .css({'position':'relative','cursor':'pointer'})
        .append(_element_poke_info_my_hp_bar, _element_poke_info_my_hp_text)
        .on('mouseenter', ()=> $(my_hp_bar_wrap).attr('title', _self._getHPValue('my')));

      _element_poke_info_my_exp_bar = $('<div />', {'class':'ExpBar __expW','style':'width:100%;'});
      _element_poke_info_my_exp_text = $('<div />', {'class':'ExpText'})
        .css({'position':'absolute','width':'100%','text-align':'center','font-weight':'bold','color':'#fff','pointer-events':'none'});
      var my_exp_bar_wrap = $('<div />', {'class':'Bar exp_progressbar', 'data-title':''})
        .css({'position':'relative','cursor':'pointer'})
        .append(_element_poke_info_my_exp_bar, _element_poke_info_my_exp_text)
        .on('mouseenter', ()=> $(my_exp_bar_wrap).attr('title', _self._getEXPValue('my')));

      _element_poke_info_my = $('<div />', {'class':'PokemonA'}).append(
        $('<div />', {'class':'PokemonBox'}).append(
          _element_poke_info_my_img,
          _element_poke_info_my_name,
          _element_poke_info_my_lvl,
          _element_poke_info_my_ball,
          _element_poke_info_my_unik,
          _element_poke_info_my_tren,
          _element_poke_info_my_item,
          $('<div />', {'class':'Bars'}).append(my_hp_bar_wrap, my_exp_bar_wrap)
        ),
        _element_poke_info_my_move,
        _element_poke_info_my_mega
      );

      // ---------- ENEMY ----------
      _element_poke_info_enemy_hp_bar = $('<div />', {'class':'HpBar __hpW','style':'width:100%;'});
      _element_poke_info_enemy_hp_text = $('<div />', {'class':'HpText'})
        .css({'position':'absolute','width':'100%','text-align':'center','font-weight':'bold','color':'#fff','pointer-events':'none'});
      var enemy_hp_bar_wrap = $('<div />', {'class':'Bar hp_proggresbar', 'data-title':''})
        .css({'position':'relative','cursor':'pointer'})
        .append(_element_poke_info_enemy_hp_bar, _element_poke_info_enemy_hp_text)
        .on('mouseenter', ()=> $(enemy_hp_bar_wrap).attr('title', _self._getHPValue('enemy')));

      _element_poke_info_enemy_exp_bar = $('<div />', {'class':'ExpBar __expW','style':'width:100%;'});
      _element_poke_info_enemy_exp_text = $('<div />', {'class':'ExpText'})
        .css({'position':'absolute','width':'100%','text-align':'center','font-weight':'bold','color':'#fff','pointer-events':'none'});
      var enemy_exp_bar_wrap = $('<div />', {'class':'Bar exp_progressbar', 'data-title':''})
        .css({'position':'relative','cursor':'pointer'})
        .append(_element_poke_info_enemy_exp_bar, _element_poke_info_enemy_exp_text)
        .on('mouseenter', ()=> $(enemy_exp_bar_wrap).attr('title', _self._getEXPValue('enemy')));

      _element_poke_info_enemy_img  = $('<div />', {'class':'imgPok Image blank'});
      _element_poke_info_enemy_name = $('<div />', {'class':'namePokemon Name __name','html':''});
      _element_poke_info_enemy_lvl  = $('<div />', {'class':'lvlPokemonTwo Lvl __lvl','html':'','style':'left:5px;'});
      _element_poke_info_enemy_ball = $('<div />', {'class':'ballPokemonTwo Ball __ball','style':'left:-15px;'});
      _element_poke_info_enemy_unik = $('<div />', {'class':'unikPokemonOne Unik __unik','html':''});
      _element_poke_info_enemy_item = $('<div />', {'class':'itemPokemonTwo Item __item'});
      _element_poke_info_enemy_tren = $('<div />', {'class':'Modif _modif_enemy'});
      _element_enemy_name = $('<div />', {'class':'Partner'});

      function createEnemyTeamBalls(enemyTeam) {
        return $('<div />', { 'class': 'TeamB' });
      }
      _element_enemy_ball = createEnemyTeamBalls(data && data.enemyTeam);

      // Кнопки
      var _btnSwap = $('<div />', {
        'class':'buttonFight Button', 'title':'Замена покемона',
        'html':'<i class="fas fa-sync-alt"></i>'
      }).click(function(ev){
        if (_data['myTarget']) {
          _selected = true;
          _self._viewMyTeam((_data['myTeam'] || null), _data['myTarget']['id'], 'Выберите покемона для замены:', ev, 1);
        }
      });

      var _btnInventory = $('<div />', {
        'class':'buttonFight Button', 'title':'Инвентарь',
        'html':'<i class="fas fa-briefcase"></i>'
      }).click(function(ev){ _self._openBall(ev); });

      // КНОПКА КОМАНДНОГО БОЯ
      var _btnTeam = $('<div />', {
        'class':'buttonFight Button team', 'title':'Командный бой',
        'html':'<i class="fas fa-users"></i>'
      }).on('click', function(){
        if (!_data || _data.teamAllowed === false) {
          if (window.Game?.notifications?.main) Game.notifications.main('Командный бой доступен только в PvP.', 'warning');
          return;
        }
        if (_isTeamBattle && _self._openTeamModal) {
          return _self._openTeamModal((_data && _data.team) || null, _data);
        }
        var $b = $(this);
        $b.attr('disabled', true);
        _self._action({ action:'make_team' }, function(resp){
          $b.removeAttr('disabled');
          if (resp && resp.battleInfo) {
            _self._update(resp.battleInfo);
            _applyTeamUi(resp.battleInfo);
            if (_self._openTeamModal) _self._openTeamModal(resp.battleInfo.team || null, resp.battleInfo);
            if (window.Game?.notifications?.main) Game.notifications.main('Командный режим активирован. Игроки могут присоединяться!', 'success');
          }
        }, function(){ $b.removeAttr('disabled'); });
      });
      _element_team_button = _btnTeam;

      var _btnSurrender = $('<div />', {
        'class':'buttonFight Button danger', 'title':'Сдаться',
        'html':'<i class="fas fa-flag"></i>'
      }).click(function(){
        if(confirm("Вы уверены, что хотите сдаться?") === true) {
          _self._action({'coward':1});
        }
      });

      // КНОПКА «УЙТИ» — фикс видимости на весь бой
      _element_button_exit  = $('<div />', {
        'class':'buttonFight Button leave',
        'html':'<i class="fas fa-sign-out-alt"></i>',
        'style':'display:none;',
        'title':'Уйти'
      }).click(()=>_self._close())
       .data('lockedVisible', false);

      _element_poke_info_enemy = $('<div />', {'class':'PokemonB'}).append(
        $('<div />', {'class':'PokemonBox'}).append(
          _element_poke_info_enemy_img,
          _element_poke_info_enemy_name,
          _element_poke_info_enemy_lvl,
          _element_poke_info_enemy_ball,
          _element_poke_info_enemy_unik,
          _element_poke_info_enemy_tren,
          _element_poke_info_enemy_item,
          $('<div />', {'class':'Bars'}).append(
            $('<div />', {'class':'Text __hp','html':''}),
            enemy_hp_bar_wrap,
            enemy_exp_bar_wrap
          )
        ),
        $('<div />', {'class':'Info'}).append(
          _element_enemy_name,
          _element_enemy_ball,
          $('<div />', {'class':'Buttons'}).append(
            _btnSwap,
            _btnInventory,
            _btnTeam,
            _btnSurrender,
            _element_button_exit
          )
        )
      );

      // --- ЗОНА КОНТЕНТА/РАУНДА ---
      _element_window_game.find('.DivMap').hide();
            // Лог боя (Modern v2: панель управления + wrap)
      function _openBattleSettings(){
        $('.pkx-btl-settings, .pkx-btl-overlay').remove();

        var $ov = $('<div/>', {'class':'pkx-btl-overlay'}).appendTo('body').on('click', function(){
          $('.pkx-btl-settings, .pkx-btl-overlay').remove();
        });

        var $p = $('<div/>', {'class':'pkx-btl-settings'}).appendTo('body');
        var $head = $('<div/>', {'class':'head'}).append(
          $('<div/>', {'class':'ttl', text:'Настройки боя'}),
          $('<div/>', {'class':'x', html:'&times;'}).on('click', function(){ $('.pkx-btl-settings, .pkx-btl-overlay').remove(); })
        );
        var $body = $('<div/>', {'class':'body'});
        var $foot = $('<div/>', {'class':'foot'}).append(
          $('<div/>', {'class':'btn', text:'Готово'}).on('click', function(){ $('.pkx-btl-settings, .pkx-btl-overlay').remove(); })
        );

        function _mkToggle(label, key){
          var $row = $('<div/>', {'class':'row'});
          var $sw = $('<label/>', {'class':'sw'}).append(
            $('<input/>', {type:'checkbox'}).prop('checked', !!_ui[key]).on('change', function(){
              _ui[key] = $(this).is(':checked');
              _saveUi();
              _applyLogUi();
              _applyRootUi();
              _applyMotionUi();
              if (_ui.autoScrollLog) _scrollLogToBottom();
              _emit('ui_change', {key:key, value:_ui[key]});
            }),
            $('<span/>', {'class':'slider'})
          );
          $row.append($('<div/>', {'class':'lbl', text:label}), $sw);
          return $row;
        }

        $body.append(
          _mkToggle('Автопрокрутка лога', 'autoScrollLog'),
          _mkToggle('Свернуть лог', 'collapseLog'),
          _mkToggle('Компактные карточки', 'compactCards'),
          _mkToggle('Уменьшить анимации', 'reduceMotion')
        );

        $p.append($head, $body, $foot);

        // prevent click-through
        $p.on('click', function(e){ e.stopPropagation(); });

        _emit('ui_settings_open', {});
      }

      var $logHead = $('<div/>', {'class':'LogHead'}).append(
        $('<div/>', {'class':'ttl'}).append(
          $('<span/>', {text:'Лог боя'}),
          $('<span/>', {'class':'dot', 'style':'display:none;'})
        ),
        $('<div/>', {'class':'tools'}).append(
          $('<div/>', {'class':'tool __down', 'title':'Вниз'}).html('<i class="fas fa-angle-double-down"></i>').on('click', function(){ _scrollLogToBottom(); }),
          $('<div/>', {'class':'tool __auto', 'title':'Автопрокрутка'}).html('<i class="fas fa-stream"></i>').on('click', function(){
            _ui.autoScrollLog = !_ui.autoScrollLog;
            _saveUi();
            _applyLogUi();
            if (_ui.autoScrollLog) _scrollLogToBottom();
            _emit('ui_autoscroll', {enabled:_ui.autoScrollLog});
          }),
          $('<div/>', {'class':'tool __collapse', 'title':'Свернуть/Развернуть'}).html('<i class="fas fa-chevron-up"></i>').on('click', function(){
            _ui.collapseLog = !_ui.collapseLog;
            _saveUi();
            _applyLogUi();
            if (!_ui.collapseLog) _scrollLogToBottom();
            _emit('ui_log_collapse', {collapsed:_ui.collapseLog});
          }),
          $('<div/>', {'class':'tool __copy', 'title':'Копировать лог'}).html('<i class="fas fa-copy"></i>').on('click', function(){
            try {
              var txt = '';
              if(_element_content_log && _element_content_log.length){
                txt = _element_content_log.find('.Wrap').text() || '';
              }
              txt = (txt || '').trim();
              if(!txt) return;

              if(navigator.clipboard && navigator.clipboard.writeText){
                navigator.clipboard.writeText(txt);
              } else {
                var $ta = $('<textarea/>').val(txt).appendTo('body').select();
                try { document.execCommand('copy'); } catch(e){}
                $ta.remove();
              }

              var $btn = $(this);
              $btn.addClass('active');
              setTimeout(function(){ $btn.removeClass('active'); }, 700);
              _emit('log_copy', {});
            } catch(e){}
          }),
          $('<div/>', {'class':'tool __settings', 'title':'Настройки'}).html('<i class="fas fa-sliders-h"></i>').on('click', function(){ _openBattleSettings(); })
        )
      );

      _element_content_log = $('<div />', {'class':'Log'})
        .append($logHead, $('<div />', {'class':'Wrap'}));
      _applyLogUi();
      // сброс «новых событий» при ручной прокрутке в конец
      _element_content_log.on('scroll', function(){
        try {
          var el = this;
          var atBottom = (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 30);
          if (atBottom && _logHasNew) { _logHasNew = false; _applyLogUi(); }
        } catch(e){}
      });
      _element_poke_info_my_stat = $('<div />', {'class':'StatusStatsA'});
      _element_poke_info_enemy_stat = $('<div />', {'class':'StatusStatsB'});
      _element_poke_info_my_stat_a = $('<div />', {'class':'StatusStatusesA'});
      _element_poke_info_enemy_stat_a = $('<div />', {'class':'StatusStatusesB'});

      // «шапка хода»
      _element_turn_team_left  = $('<div/>', { 'class':'turn-team left'  });
      _element_turn_center     = $('<div/>', { 'class':'turn-center' })
                                  .append(
                                    $('<div/>',{'class':'state'}).append(
                                      $('<div/>',{'class':'title', text:'Ход'}),
                                      $('<div/>',{'class':'turn-progress'}).append($('<div/>',{'id':'timer_battle','class':'turn-progress-inner'})),
                                      $('<div/>',{'class':'turn-remaining', html:'Осталось <span id="timer_battle_secs">--</span>'})
                                    )
                                  );
      _element_turn_team_right = $('<div/>', { 'class':'turn-team right' });
      _element_turn_zone       = $('<div/>', { 'class':'TurnBar' })
                                  .append(_element_turn_team_left, _element_turn_center, _element_turn_team_right);

      // Погода/Раунд/Таймер (счётчик раунда и кнопка «присвоить»)
      _element_round_weather = $('<span />');
      _element_round_count = $('<span />', {'html':'1'});
      _element_round_time_count = $('<span />', {'html':''});
      _element_round_time = $('<div />', {'class':'timeBattle'}); // сам визуал таймера рендерится в _element_turn_center
      _element_round_time_buttons = $('<div />', {'class':'Buttons', 'style':'display:none;'})
        .append(
          $('<div />', {'class':'btnAssign','html':'Присвоить победу'}).click(function(){
            _self._action({'win_time':1});
          })
        );

      // Вёрстка (mobile/desktop)
      if(_isMobile()) {
        _element_window_game.append(
          _element_window_wrap.append(
            _element_window.append(
              _element_window_started,
              _element_poke_info_my,
              _element_poke_info_enemy,
              $('<div />', {'class':'Content'}).append(
                $('<div />', {'class':'Zone'}).append(
                  _element_turn_zone,
                  $('<div />', {'class':'StatusA'}).append(_element_poke_info_my_stat, _element_poke_info_my_stat_a),
                  $('<div />', {'class':'StatusB'}).append(_element_poke_info_enemy_stat, _element_poke_info_enemy_stat_a),
                  $('<div />', {'class':'Weath'}).append(_element_round_weather),
                  $('<div />', {'class':'Round'}).append(
                    $('<div />', {'class':'Text','html':'Раунд '}).append(_element_round_count),
                    _element_round_time,
                    _element_round_time_buttons
                  ),
                  $('<div />', {'class':'TeamBadgeWrap mobile'}).append(
                    $('<span />', {'id':'battle-team-badge', 'class':'battle-team-badge', 'style':'display:none;', text:'Командный бой'})
                  )
                ),
                _element_content_log
              )
            )
          )
        );
      } else {
        _element_window_game.append(
          _element_window_wrap.append(
            _element_window.append(
              _element_window_started,
              _element_poke_info_my,
              $('<div />', {'class':'Content'}).append(
                $('<div />', {'class':'Zone'}).append(
                  _element_turn_zone,
                  $('<div />', {'class':'StatusA'}).append(_element_poke_info_my_stat, _element_poke_info_my_stat_a),
                  $('<div />', {'class':'StatusB'}).append(_element_poke_info_enemy_stat, _element_poke_info_enemy_stat_a),
                  $('<div />', {'class':'Weath'}).append(_element_round_weather),
                  $('<div />', {'class':'Round'}).append(
                    $('<div />', {'class':'Text','html':'Раунд '}).append(_element_round_count),
                    _element_round_time,
                    _element_round_time_buttons,
                    $('<span />', {'id':'battle-team-badge', 'class':'battle-team-badge', 'style':'display:none;', text:'Командный бой'})
                  )
                ),
                _element_content_log
              ),
              _element_poke_info_enemy
            )
          )
        );
      }

      // Ссылка на бейдж + клик = модалка, только когда режим включен
      _element_team_badge = $('#battle-team-badge');
      if (_element_team_badge && !_element_team_badge.attr('data-tip')) {
        _element_team_badge
          .attr('data-tip','Включён командный режим: к сторонам могут присоединяться игроки.')
          .attr('title','Командный бой (гаунтлет)')
          .on('click', function(){
            if (_isTeamBattle && _self._openTeamModal) _self._openTeamModal((_data && _data.team) || null, _data);
          });
      }

      function _applyTeamUi(info){
        var isCmd = !!(info && (info.isCommandBattle === true || info.isCommandBattle == 1));
        _isTeamBattle = isCmd;
        if (_element_team_badge) _element_team_badge.toggle(isCmd);
        if (_element_team_button) {
          _element_team_button.toggle(!!(info && info.teamAllowed));
          _element_team_button.toggleClass('active', isCmd);
          _element_team_button.attr('title', isCmd ? 'Показать состав команд' : 'Сделать бой командным');
        }
      }

      _applyTeamUi(data);
      _self._update(data, true);
      _self._updateBars();
      _element_window.css('display', 'flex');

      // Hotkeys (заготовка на будущее): 1-4 атаки, L — свернуть/развернуть лог
      try {
        $(document).off('keydown.pkxBattle');
        $(document).on('keydown.pkxBattle', function(ev){
          try {
            if(!_open || !_element_window || !_element_window.length) return;
            if(!_element_window.is(':visible')) return;

            var tag = (ev.target && ev.target.tagName) ? ev.target.tagName.toLowerCase() : '';
            if(tag === 'input' || tag === 'textarea' || $(ev.target).is('[contenteditable="true"]')) return;

            var k = ev.key;
            if(k >= '1' && k <= '4'){
              var idx = parseInt(k, 10) - 1;
              var $moves = (_element_poke_info_my_move && _element_poke_info_my_move.length)
                ? _element_poke_info_my_move.find('.Move').not('.Off')
                : $();
              if($moves.length > idx){
                $moves.eq(idx).trigger('click');
                ev.preventDefault();
                return;
              }
            }

            if(k === 'l' || k === 'L'){
              _ui.collapseLog = !_ui.collapseLog;
              _saveUi();
              _applyLogUi();
              if(!_ui.collapseLog) _scrollLogToBottom();
              ev.preventDefault();
              return;
            }
          } catch(e){}
        });
      } catch(e){}

      // Лёгкая подстраховка: если мир перестал присылать обновления, периодически дергаем view
      try { _startAutoPoll(); } catch(e){}

      // подключаем сокеты
      _attachSocket(data);
    }
  };

  // -----------------------------
  // Инвентарь (GiveDiv) — покеболы (современный попап, не урезан)
  // -----------------------------
  this._openBall = function(e, data){
    if(!data){
      if (typeof ClassInfo !== 'undefined' && ClassInfo && typeof ClassInfo._action === 'function') {
        ClassInfo._action({ type:'items', cat:'ball' }, (info)=>{
          if (info && info.ballList) { _self._openBall(e, info.ballList); }
        });
      }
      return;
    }

    $('.pkx-ball-pop, .GiveDiv').remove();

    (function injectCSS(){
      if (document.getElementById('pkx-ball-css')) return;
      var css =
        '.pkx-ball-pop{position:fixed;z-index:300000;left:0;top:0;transform:none;min-width:260px;width:300px;max-width:92vw;background:#fff;border:1px solid #e6eafe;border-radius:14px;box-shadow:0 18px 46px rgba(23,35,74,.18),0 3px 10px rgba(106,98,140,.12);font-family:Nunito,Inter,Arial,sans-serif;color:#1b2b4f;overflow:hidden}'+
        '.pkx-ball-head{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f0eef9;border-bottom:1px solid #d7d3ea}'+
        '.pkx-ball-title{font-weight:800;color:#6b5aa3;font-size:14px}'+
        '.pkx-ball-close{border:1px solid #d7d3ea;background:#f7f6fd;border-radius:9px;width:26px;height:26px;line-height:24px;text-align:center;color:#8f76c1;font-weight:900;cursor:pointer}'+
        '.pkx-ball-close:hover{background:#ece8fb}'+
        '.pkx-ball-body{padding:6px}'+
        '.pkx-ball-list{max-height:52vh;overflow:auto;display:flex;flex-direction:column;gap:6px}'+
        '.pkx-ball-item{display:flex;align-items:center;gap:10px;width:100%;border:1px solid #e6eafe;background:#fff;border-radius:12px;padding:8px 10px;cursor:pointer;text-align:left}'+
        '.pkx-ball-item:hover{border-color:#2f74ff;box-shadow:0 10px 22px rgba(46,116,255,.14)}'+
        '.pkx-ball-item .ico{width:40px;height:40px;border:1px solid #dbe6ff;border-radius:10px;background:#eef3ff;display:grid;place-items:center;flex:0 0 auto}'+
        '.pkx-ball-item .ico img{max-width:32px;max-height:32px}'+
        '.pkx-ball-item .txt{display:flex;flex-direction:column;min-width:0}'+
        '.pkx-ball-item .name{font-weight:800;font-size:13px;white-space:nowrap;overflow:hidden;color: #6f7b95;text-overflow:ellipsis}'+
        '.pkx-ball-item .sub{font-size:12px;color:#6f7b95}'+
        '.pkx-ball-empty{padding:10px 6px;color:#6f7b95;font-size:13px}'+
        '@media (max-width:768px){.pkx-ball-list{max-height:60vh}}';
      var st = document.createElement('style');
      st.id = 'pkx-ball-css';
      st.type = 'text/css';
      st.appendChild(document.createTextNode(css));
      document.head.appendChild(st);
    })();

    var $pop = $('<div/>', {'class':'pkx-ball-pop', 'role':'dialog', 'aria-label':'Выбор покебола'});
    var $head = $('<div/>', {'class':'pkx-ball-head'}).appendTo($pop);
    $('<div/>', {'class':'pkx-ball-title', text:'Выберите предмет'}).appendTo($head);
    $('<button/>', {'type':'button','class':'pkx-ball-close','html':'&times;'})
      .appendTo($head)
      .on('click', function(ev){ ev.stopPropagation(); $pop.remove(); $(document).off('.pkxBall'); });

    var $body = $('<div/>', {'class':'pkx-ball-body'}).appendTo($pop);
    var $list = $('<div/>', {'class':'pkx-ball-list'}).appendTo($body);

    if (data && data.length){
      $.each(data, function(i, val){
        var $item = $('<button/>', {'class':'pkx-ball-item', 'type':'button'}).append(
          $('<div/>', {'class':'ico'}).append(
            $('<img/>', { src:'/img/world/items/little/'+val.item_id+'.png', alt:'' })
          ),
          $('<div/>', {'class':'txt'}).append(
            $('<div/>', {'class':'name', text: val.name }),
            $('<div/>', {'class':'sub', html: 'Кол-во: <b>x'+val.count+'</b>' })
          )
        ).on('click', function(ev){
          ev.stopPropagation();
          try { _self._action({ 'catch': val.id }); } finally {
            $pop.remove(); $(document).off('.pkxBall');
          }
        });
        $list.append($item);
      });
    } else {
      $list.append($('<div/>', {'class':'pkx-ball-empty', text:'Предметов нет'}));
    }

    $('body').append($pop);

    function centerPopover(el){
      el.style.left = '50%';
      el.style.top = '50%';
      el.style.transform = 'translate(-50%, -50%)';
    }
    function placeNearPoint(el, pageX, pageY){
      var cx = pageX - (window.pageXOffset || 0);
      var cy = pageY - (window.pageYOffset || 0);
      var r = el.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight, pad = 8;
      var left = Math.min(Math.max(pad, cx + 10), vw - r.width - pad);
      var top  = Math.min(Math.max(pad, cy + 10), vh - r.height - pad);
      el.style.left = left + 'px';
      el.style.top  = top  + 'px';
      el.style.transform = 'none';
    }

    var isCoarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    var isSmall  = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;

    if (typeof getElCord === 'function' && e && typeof e.pageX === 'number'){
      $pop.css(getElCord(e, $pop, [-25, 6]));
      var rr = $pop[0].getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight, pad = 8;
      var l = Math.min(Math.max(pad, rr.left), vw - rr.width - pad);
      var t = Math.min(Math.max(pad, rr.top ), vh - rr.height - pad);
      $pop.css({ left: l, top: t, transform:'none' });
    } else if (!isCoarse && !isSmall && e && typeof e.pageX === 'number'){
      placeNearPoint($pop[0], e.pageX, e.pageY);
    } else {
      centerPopover($pop[0]);
    }

    setTimeout(function(){
      $(document)
        .on('mousedown.pkxBall', function(ev){
          if (!$(ev.target).closest('.pkx-ball-pop').length){
            $pop.remove();
            $(document).off('.pkxBall');
          }
        })
        .on('keydown.pkxBall', function(ev){
          if (ev.key === 'Escape'){
            $pop.remove();
            $(document).off('.pkxBall');
          }
        });
    }, 0);
  };

  // -----------------------------
  // Утилиты HP/EXP
  // -----------------------------
  this._getHPValue = function(side){
    var data = (_data && _data[side === 'my' ? 'myTarget' : 'enemyTarget']) ? _data[side === 'my' ? 'myTarget' : 'enemyTarget'] : null;
    if(!data) return '';
    var hp = Number(data.hp||0), hpmax = Number(data.hp_max||0);
    return hp + ' / ' + hpmax + ' HP';
  };
  this._getEXPValue = function(side){
    var data = (_data && _data[side === 'my' ? 'myTarget' : 'enemyTarget']) ? _data[side === 'my' ? 'myTarget' : 'enemyTarget'] : null;
    if(!data) return '';
    if (typeof data.exp_max !== 'undefined') {
      return Number(data.exp||0) + ' / ' + Number(data.exp_max||0) + ' EXP';
    } else if (data.exp && typeof data.exp.val !== 'undefined' && typeof data.exp.next !== 'undefined') {
      return Number(data.exp.val||0) + ' / ' + Number(data.exp.next||0) + ' EXP';
    }
    return '0 / 0 EXP';
  };

  // Обновление текстов/ширины баров из _data
  this._updateBars = function() {
    var my = (_data && _data.myTarget) ? _data.myTarget : null;
    if(my){
      var myHp = Number(my.hp||0);
      var myHpMax = Number(my.hp_max||0);
      var hp_perc = myHpMax ? Math.max(0, Math.min(100, Math.round(100*myHp/myHpMax))) : 0;

      // Смена покемона — сбрасываем «дельту», чтобы не рисовать лишние анимации
      if(!_prevPokeId) _prevPokeId = { my:null, enemy:null };
      if(_prevPokeId.my !== my.id){
        _prevPokeId.my = my.id;
        _prevHp.my = myHp;
        _prevExp.my = (typeof my.exp !== 'undefined') ? Number(my.exp||0) : null;
      }else{
        if(_prevHp.my !== null && myHp !== _prevHp.my){
          var dMy = myHp - _prevHp.my;
          if(dMy){
            _showFloat('my', (dMy>0?'+':'')+Math.abs(dMy)+' HP', (dMy>0?'heal':'dmg'));
            _emit('hp_change', {side:'my', delta:dMy, hp:myHp, hpMax:myHpMax, id:my.id});
          }
          _prevHp.my = myHp;
        }
      }

      _element_poke_info_my_hp_bar
        .css('width', hp_perc+'%')
        .removeClass('low mid')
        .addClass((hp_perc<=25)?'low':((hp_perc<=50)?'mid':''));

      _element_poke_info_my_hp_text.html(myHp + ' / ' + myHpMax);

      // EXP
      var expPerc = 0, expText = '0 / 0';
      if (typeof my.exp_max !== 'undefined') {
        var myExp = Number(my.exp||0);
        var myExpMax = Number(my.exp_max||0);
        expPerc = myExpMax ? Math.max(0, Math.min(100, Math.round(100*myExp/myExpMax))) : 0;
        expText = myExp + ' / ' + myExpMax;
        _prevExp.my = myExp;
      }
      _element_poke_info_my_exp_bar.css('width', expPerc+'%');
      _element_poke_info_my_exp_text.html(expText);
    }

    var en = (_data && _data.enemyTarget) ? _data.enemyTarget : null;
    if(en){
      var enHp = Number(en.hp||0);
      var enHpMax = Number(en.hp_max||0);
      var en_hp_perc = enHpMax ? Math.max(0, Math.min(100, Math.round(100*enHp/enHpMax))) : 0;

      if(!_prevPokeId) _prevPokeId = { my:null, enemy:null };
      if(_prevPokeId.enemy !== en.id){
        _prevPokeId.enemy = en.id;
        _prevHp.enemy = enHp;
        _prevExp.enemy = (typeof en.exp !== 'undefined') ? Number(en.exp||0) : null;
      }else{
        if(_prevHp.enemy !== null && enHp !== _prevHp.enemy){
          var dEn = enHp - _prevHp.enemy;
          if(dEn){
            // Для противника инвертируем визуал: потеря HP — красным
            _showFloat('enemy', (dEn>0?'+':'')+Math.abs(dEn)+' HP', (dEn>0?'heal':'dmg'));
            _emit('hp_change', {side:'enemy', delta:dEn, hp:enHp, hpMax:enHpMax, id:en.id});
          }
          _prevHp.enemy = enHp;
        }
      }

      _element_poke_info_enemy_hp_bar
        .css('width', en_hp_perc+'%')
        .removeClass('low mid')
        .addClass((en_hp_perc<=25)?'low':((en_hp_perc<=50)?'mid':''));

      _element_poke_info_enemy_hp_text.html(enHp + ' / ' + enHpMax);

      // EXP (иногда в PvP может отсутствовать)
      var eExpPerc = 0, eExpText = '0 / 0';
      if (typeof en.exp_max !== 'undefined') {
        var enExp = Number(en.exp||0);
        var enExpMax = Number(en.exp_max||0);
        eExpPerc = enExpMax ? Math.max(0, Math.min(100, Math.round(100*enExp/enExpMax))) : 0;
        eExpText = enExp + ' / ' + enExpMax;
        _prevExp.enemy = enExp;
      }
      _element_poke_info_enemy_exp_bar.css('width', eExpPerc+'%');
      _element_poke_info_enemy_exp_text.html(eExpText);
    }
  };

  // -----------------------------
  // ГЛАВНОЕ ОБНОВЛЕНИЕ
  // -----------------------------
  this._update = function(info, first){
    if(!(_element_window && _element_window.length) || !info) return;

    if (typeof info === 'string') {
      try { info = JSON.parse(info); } catch(e){ return; }
    }

    _lastUpdateAt = Date.now();

    // локальный хелпер: управляет видимостью кнопки «Уйти»
    function _applyExitButtonVisibility(curInfo){
      if(!_element_button_exit) return;

      var locked = _element_button_exit.data('lockedVisible') === true;
      var show = locked || _battle_end === true;

      // подсказки от сервера о завершении или разрешении на выход
      if (!show && curInfo) {
        if (curInfo.state === 'finished' || curInfo.status === 'end' || curInfo.battle_end === true) show = true;
        if (curInfo.answer && (curInfo.answer.battleEND || curInfo.answer.state === 'finished')) show = true;
        if (curInfo.log && curInfo.log.battleEND) show = true;
        if (curInfo.allowLeave || curInfo.canLeave || curInfo.leaveAllowed) show = true;
      }

      if (show) {
        _element_button_exit
          .css('display', 'block')
          .removeClass('CowardButton')
          .addClass('LeaveButton')
          .html('<i class="fas fa-sign-out-alt"></i>')
          .data('lockedVisible', true); // зафиксировали видимость до конца боя
      } else {
        _element_button_exit.css('display', 'none').removeClass('LeaveButton');
      }
    }

    if(_element_window_started && _element_window_started.length){

      // если зашёл новый бой — сбросим лок на видимость «Уйти»
      if(!(_data['id'] && _data['id'] == info['id'])){
        // Новый бой (или смена ID) — сбрасываем локальные маркеры, таймер и сокеты
        _lastBattleLog = null;
        _turnExpiredSent = false;
        _stopTurnTimer();
        _detachSocket();

        _data = {};
        _battle_end = false;

        // сброс анимационных дельт (новый бой)
        _prevHp = { my: null, enemy: null };
        _prevExp = { my: null, enemy: null };
        _prevPokeId = { my: null, enemy: null };

        if (_element_enemy_name) {
          _element_enemy_name.empty().append(_self._viewName(info['enemy'], info['enemyTarget'], info));
        }

        if(_element_content_log){
          var $w0 = _element_content_log.children('.Wrap');
          if($w0.length) $w0.empty();
          else _element_content_log.empty();
          _logHasNew = false;
          _applyLogUi();
        }

        if(_element_poke_info_my_move){
          _element_poke_info_my_move.css('display', 'block');
        }

        _element_poke_info_enemy.find('div.buttonFight').not('.leave').show().css('display','');
        if (_element_button_exit){
          _element_button_exit.hide().removeClass('LeaveButton').data('lockedVisible', false);
        }
      }

      _data = info;

      // На случай позднего подключения сокетов или смены боя — пытаемся подключиться повторно
      _attachSocket(info);

      _isTeamBattle = !!(info && (info.isCommandBattle === true || info.isCommandBattle == 1));
      if (_element_team_badge) _element_team_badge.toggle(_isTeamBattle);
      if (_element_team_button) {
        _element_team_button.toggle(!!info.teamAllowed);
        _element_team_button.toggleClass('active', _isTeamBattle);
        _element_team_button.attr('title', _isTeamBattle ? 'Показать состав команд' : 'Сделать бой командным');
      }

      // выбор стартового
      if(!(info['myTarget'] && info['myTarget']['id'])){
        _self._viewMyTeam((info['myTeam'] || null));
      }else{
        if(info['enemyTarget'] && info['enemyTarget']['id']){
          _self._updatePoke(_element_poke_info_enemy, info['enemyTarget']);
        }
        if(!_selected){
          _element_window_started.css('display', 'none');
        }
      }

      // Рендер мини-команд в «шапке хода»
      _renderTurnTeams(info);

      // Раунд/погода
      if(_element_round_count && info['round']){
        _element_round_count.html(info['round']);
      }
      if(_element_round_weather && typeof info['weather'] !== 'undefined'){
        var rw = (info['weather'] == 1) ? "" : "<span>"+(info['weather_round']||info['weather_count']||'')+"</span>";
        _element_round_weather.html('<img onclick="issetAll('+info['weather']+',\'weather\')" src="/img/weather/'+info['weather']+'.png">'+rw);
      }

      // Логи (ActionBattle кладёт HTML в battleLog)
      (function(){
        var newLog = (info['battleLog'] || '');
        var $log = (_element_content_log && _element_content_log.length)
          ? _element_content_log
          : $('#battleMap .Battle .Content .Log');
        if (!$log || !$log.length) return;

        var el = $log[0];

        // Был ли пользователь «внизу» до обновления
        var atBottom = false;
        try { atBottom = (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 30); } catch(e){}

        var changed = (_lastBattleLog !== newLog);
        if (changed) {
          var $wrap = $log.children('.Wrap');
          if (!$wrap.length) {
            // Не сбрасываем шапку, если она есть — просто добавляем Wrap
            $wrap = $('<div class="Wrap"></div>');
            $log.append($wrap);
          }
          $wrap.html(newLog);
          $wrap.find('.Step').removeClass('latest').last().addClass('latest');
          _lastBattleLog = newLog;

          // Если пользователь не внизу — отмечаем новые события
          _logHasNew = !atBottom;
          _applyLogUi();

          _emit('log_update', {changed:true});
        }

        // Автоскролл — только если включен и пользователь был внизу
        try {
          if (_ui && _ui.autoScrollLog && atBottom) el.scrollTop = el.scrollHeight;
        } catch(e){}
      })();

      // Таймер
      var timer = 0;
      if(info['timeout']){
        $.each(info['timeout'], function(key, val){
          if(val > 0){
            if(timer <= 0 || timer <= val){
              timer = val;
            }
          }
        });
      }
      _self._viewTime(timer, info);

      // Мой покемон (и атаки)
      _self._updatePoke(_element_poke_info_my, info['myTarget'], true);
      _self._updateBars();

      // Конец боя
      (function () {
        var btlEnd = null;
        var myId = info && info.my ? parseInt(info.my.id, 10) : 0;

        if (info && info.log && info.log.battleEND) {
          btlEnd = info.log.battleEND;
        } else if (info && info.answer) {
          if (info.answer.battleEND) {
            btlEnd = info.answer.battleEND;
          } else if (myId && info.answer['u' + myId] && info.answer['u' + myId].battleEND) {
            btlEnd = info.answer['u' + myId].battleEND;
          }
        }

        if (btlEnd) {
          _element_poke_info_enemy.find('div.buttonFight').not('.leave').hide();

          if (_element_button_exit) {
            _element_button_exit
              .show()
              .removeClass('CowardButton')
              .addClass('LeaveButton')
              .html('<i class="fas fa-sign-out-alt"></i>')
              .data('lockedVisible', true);
          }

          var isDraw = (btlEnd.winner === false && btlEnd.loser === false);
          var msg;
          if (isDraw) {
            msg = (btlEnd.title === 'CATCH')
              ? 'Вы успешный ловец!'
              : 'Оба соперника были равными.';
          } else {
            var youWin = (myId && String(myId) === String(btlEnd.winner));
            var winnerName = youWin
              ? ((info.my && info.my.login) || 'Вы')
              : ((info.enemy && info.enemy.login) || 'Соперник');

            if (btlEnd.title === 'COWARD') {
              msg = youWin ? 'Соперник сдался. Победа за вами!' : 'Вы сдались.';
            } else {
              msg = 'Победа за ' + winnerName + '!';
            }
          }

          if (!_battle_end) {
            _element_content_log.prepend(
              $('<div />', { 'class': 'endFight' }).text('Бой окончен! ' + msg)
            );
          }

          _battle_end = true;
          _stopTurnTimer();

          _applyExitButtonVisibility(info);
        } else {
          if (info['myTarget'] && info['myTarget']['hp'] <= 0) {
            _self._viewMyTeam((_data['myTeam'] || null), info['myTarget']['id'], 'Выберите покемона для замены:', false, 2);
            _element_window_started.find('div.pokeList').css('display', 'block');
          }
          _applyExitButtonVisibility(info);
        }
      })();
    }
  };

  // -----------------------------
  // Таймер (визуал) — отрисовываем в центре «шапки хода»
  // -----------------------------
  this._viewTime = function(timer, info) {
    if (!_element_turn_center) return;

    var myTurn = !!(info && (info.myTurn === true || info.myTurn == 1 || info.my_turn == 1));


    if (timer > 0) {
      var $state = _element_turn_center.find('.state');
      $state
        .removeClass('battle-turn-active battle-turn-wait')
        .addClass(myTurn ? 'battle-turn-active' : 'battle-turn-wait');

      // меняем заголовок
      $state.find('.title').text(myTurn ? 'Ваш ход' : 'Ход соперника');

      _element_round_time.css('display','none'); // старый текстовый блок прячем (всё в шапке)
      _element_round_time_buttons.css('display','none');

      var elapsed = 0;
      if (info && typeof info['time'] !== 'undefined') {
        var now = Math.floor(Date.now() / 1000);
        if (info['time'] > 1000000000) elapsed = Math.max(0, now - info['time']);
        else elapsed = Math.max(0, Number(info['time']||0));
      }

      _startTurnTimer(Number(timer)||0, elapsed);

      if (myTurn) _element_poke_info_my_move.addClass('your-turn');
      else _element_poke_info_my_move.removeClass('your-turn');

    } else {
      _stopTurnTimer();
      _element_round_time.css('display', 'none').removeClass('danger');
      _element_round_time_buttons.css('display','none');
      _element_poke_info_my_move.removeClass('your-turn');

      var $state = _element_turn_center.find('.state');
      $state.removeClass('battle-turn-active battle-turn-wait');
      $state.find('.title').text('Ход');
      var bar = document.getElementById('timer_battle');
      if (bar) bar.style.width = '0%';
      var t = document.getElementById('timer_battle_secs');
      if (t) t.textContent = '--';
    }
  };

  // -----------------------------
  // Мини-слоты команд (как на образце)
  // -----------------------------
  function _statusIconUrl(type){
    // Базовый набор, при отсутствии — универсальная «семечка»/яд
    var map = {
      burn:'/img/world/status/burn.png',
      toxic:'/img/world/status/toxic.png',
      toxic2:'/img/world/status/toxic2.png',
      sleep:'/img/world/status/sleep.png',
      frost:'/img/world/status/frost.png',
      seed:'/img/world/status/seed.png',
      paralyzed:'/img/world/status/paralyzed.png',
      confused:'/img/world/status/confused.png'
    };
    return map[type] || '/img/world/status/seed.png';
  }
  function _spriteSmall(num){
    var n = Number(num||0); n = (n<10?'00'+n:(n<100?'0'+n:n));
    return '/img/pokemons/mini/'+n+'.png';
  }
  function _mkSlot(p, activeId){
    var id = p.id || p.poke_id || p.pid || 0;
    var basenum = p.basenum2 || p.basenum || p.number || id || 0;
    var hp = +p.hp || 0, hpmax = +p.hp_max || +p.max_hp || Math.max(hp,1);

    var w = $('<div/>', {'class':'turn-slot','title': '#'+(p.basenum2||basenum)+' '+(p.name||'')+(p.lvl?(' • '+p.lvl+' ур.'):'')});
    var img = $('<img/>', {src:_spriteSmall(basenum), alt:(p.name||'')}).appendTo(w);

    var perc = Math.max(0, Math.min(100, Math.round(hp/hpmax*100)));
    var bar = $('<div/>', {'class':'hp'}).append($('<div/>',{'class':'in'}).css('width', perc+'%'));
    w.append(bar);

    if (perc <= 25) w.addClass('low');
    if (hp <= 0) w.addClass('dead');
    if (activeId && String(activeId) === String(id)) w.addClass('active');

    // статус-пин
    var lst = p.statusList || p.status || p.statuses || null;
    var st = null;
    if (lst && lst.length){
      var s0 = lst[0];
      var t  = s0.type || s0.name || '';
      st = $('<div/>', {'class':'st'}).css({
        position:'absolute', right:'-2px', bottom:'-2px', width:'14px', height:'14px',
        background:'url('+_statusIconUrl(t)+') center/contain no-repeat'
      });
    } else if (p.leech || p.seeded){
      st = $('<div/>', {'class':'st'}).css({
        position:'absolute', right:'-2px', bottom:'-2px', width:'14px', height:'14px',
        background:'url('+_statusIconUrl('seed')+') center/contain no-repeat'
      });
    }
    if (st) w.append(st);
    return w;
  }
  function _renderTurnTeams(info){
    if (!_element_turn_team_left || !_element_turn_team_right) return;

    var myList = info && (info.myTeam || info.my_team || []);
    var enList = info && (info.enemyTeam || info.enemy_team || []);
    var myActive = info && info.myTarget ? info.myTarget.id : 0;
    var enActive = info && info.enemyTarget ? info.enemyTarget.id : 0;

    _element_turn_team_left.empty();
    (Array.isArray(myList)?myList:[]).forEach(function(p){ _element_turn_team_left.append(_mkSlot(p, myActive)); });

    _element_turn_team_right.empty();
    (Array.isArray(enList)?enList:[]).forEach(function(p){ _element_turn_team_right.append(_mkSlot(p, enActive)); });
  }

  // -----------------------------
  // Парсер «лог в логах» (оставлен для совместимости)
  // -----------------------------
  this._parserLogStatus = function(info) {
    var tpl = [];
    $.each(info, function(key, val) {
      tpl.push(
        $('<div />', {'class': 'sub','html': $('<div>').text(val).html(),'title': typeof key === 'string' ? key : ''})
      );
    });
    if (tpl.length) {
      return $('<div />', {'class': 'pokLogBattle'}).append(
        $('<div />', { 'class': 'text' }).append(tpl)
      );
    }
    return '';
  };

  this._parserLog = function(info, recurse, forceRedraw) {
    if (!recurse) {
      var tpl = [];
      if (!Array.isArray(info) && typeof info !== 'object') return tpl;
      $.each(info, function(key, val) {
        tpl.push(_self._parserLog(val, true, forceRedraw));
      });
      return tpl;
    }
    return '';
  };

  // -----------------------------
  // Обновление покемона + атаки
  // -----------------------------
  this._updatePoke = function(element, info, my) {
    if (!(element && element.length && info)) return;

    var sex, num = (info['basenum'] || 0),
        typePok = (info['type'] != 'normal') ? info['type'] : '',
        plaguePok = (info['virus'] == '1') ? '<span class="Red-Color">заражен</span>' : '',
        pb, namePok, tr = '';

    num = (num < 10 ? '00' + num : num < 100 ? '0' + num : num);

    if (info['basenum'] != 10000) {
      if (info['sex'] == 'Мальчик') sex = 'mars';
      else if (info['sex'] == 'Девочка') sex = 'venus';
      else sex = 'genderless';
      pb = 'url(/img/world/items/little/' + info.ball + '.png)';
      namePok = '#' + num + ' ' + info['name'];
    } else {
      sex = '';
      pb = '';
      namePok = info['name'];
    }

    if (info.tren >= 1) {
      tr = (info.tren == 6)
        ? '<i class="trening fas fa-crown tr' + info.tren + '"></i>'
        : '<i class="trening fas fa-angle-double-up tr' + info.tren + '"></i>';
    }

    element.find('div.__ball').css('background-image', pb);
    element.find('div.__item')
      .css('background-image', 'url(/img/world/items/little/' + info.item + '.png)')
      .attr('onclick', 'issetAll(' + info.item + ',"item")');
    element.find('div.Modif').html(tr);
    element.find('div._modif_enemy').html(tr);

    element.find('div.__unik')
      .html(typePok + (typePok && plaguePok ? ' ' : '') + plaguePok)
      .attr('class', 'unikPokemonOne Unik __unik ' + info['type'] + '-color');

    element.find('.imgPok')
      .removeClass('blank')
      .html(info.sprite)
      .attr('onclick', 'openDex(' + parseInt(info.basenum2, 10) + ')');

    element.find('.Classific').attr('class', 'Classific pow' + info['tren']);
    element.find('.ClassificEnemy').attr('class', 'ClassificEnemy pow' + info['tren']);
    element.find('div.__lvl').html((info['lvl'] || '0'));
    element.find('div.__name')
      .html('<div class="Text">' + namePok + '</div><div class="Sex"><i class="fas fa-' + sex + '"></i></div>')
      .attr('class', 'namePokemon Name __name ' + info['type'] + '-color');

    // HP
    var hpVal = Number(info['hp'] || 0);
    var hpMax = Number(info['hp_max'] || 0);
    var hpPerc = (hpMax > 0) ? Math.floor((hpVal / hpMax) * 100) : 0;
    hpPerc = Math.max(0, Math.min(100, hpPerc));

    element.find('div.hp_proggresbar').attr('data-title', 'HP: ' + hpVal + ' / ' + hpMax);
    element.find('div.__hpW').width(hpPerc + '%');

    // EXP (оба формата)
    if (info['exp'] && typeof info['exp']['val'] !== 'undefined' && typeof info['exp']['next'] !== 'undefined') {
      var expVal = Number(info['exp']['val'] || 0);
      var expNext = Number(info['exp']['next'] || 0);
      var expPerc = (expNext > 0) ? Math.floor((expVal / expNext) * 100) : 0;
      expPerc = Math.max(0, Math.min(100, expPerc));
      element.find('div.exp_progressbar').attr('data-title', 'Опыт: ' + expVal + ' / ' + expNext);
      element.find('div.__expW').width(expPerc + '%');
    } else if (typeof info['exp'] !== 'undefined' && typeof info['exp_max'] !== 'undefined') {
      var expVal2 = Number(info['exp'] || 0);
      var expMax2 = Number(info['exp_max'] || 0);
      var expPerc2 = (expMax2 > 0) ? Math.floor((expVal2 / expMax2) * 100) : 0;
      expPerc2 = Math.max(0, Math.min(100, expPerc2));
      element.find('div.exp_progressbar').attr('data-title', 'Опыт: ' + expVal2 + ' / ' + expMax2);
      element.find('div.__expW').width(expPerc2 + '%');
    } else {
      element.find('div.exp_progressbar').attr('data-title', 'Опыт: 0 / 0');
      element.find('div.__expW').width('0%');
    }

    // Атаки
    if (my && _element_poke_info_my_move) {
      if (info['atkList']) {
        var tpl = [];
        var pp_atk = (info['pp_my'] || '').split(',');
        $.each(info['atkList'], function(key, val) {
          var atkc = (val['category'] == 'physical') ? 1 : (val['category'] == 'special') ? 2 : 3;
          tpl.push(
            (function(){
              var hk = 0;
              try { hk = (typeof val['attack_num'] !== 'undefined') ? (parseInt(val['attack_num'], 10) + 1) : 0; } catch(e){ hk = 0; }
              if (!hk || hk < 1) hk = (tpl.length + 1);

              var curPP = 0;
              try { curPP = parseInt(pp_atk[val['attack_num']], 10) || 0; } catch(e){ curPP = 0; }
              var maxPP = parseInt(val['pp'], 10) || 0;

              var moveName = val['name'] || val['name_ru'] || val['nameRus'] || val['title'] || val['atk_name'] || val['attack_name'] || val['ru'] || val['rus'] || val['text'] || '';
              moveName = String(moveName || '').trim();
              if (!moveName) moveName = 'Неизвестный приём';

              var $mv = $('<div />', {'class':'Move', 'data-hotkey': hk, 'title':'['+hk+'] ' + moveName})
                .append(
                  $('<div/>', {'class':'key', text: hk}),
                  $('<div/>', {'class':'img'}).append(
                    $('<img/>', {src:'/img/world/typs/' + (val['type'] || 'empty') + '.png', alt:(val['type']||'')})
                  ),
                  $('<div/>', {'class':'MoveInfo'}).append(
                    $('<div/>', {'class':'Name MoveCategory' + atkc, text: moveName}),
                    $('<div/>', {'class':'PP'}).text(curPP + '/' + maxPP + ' pp')
                  )
                );

              if (maxPP && curPP <= 2) $mv.addClass('pp-low');

              $mv.on('click', function(ev) {
                if (val['target'] == 'ban') { alert('Атака не работает.'); return; }
                if (val['id'] == 235) { _self._aHH((_data['myTeam'] || null), ev); }
                else if (val['id'] == 21)  { _self._aAM((_data['myTeam'] || null), ev); }
                else if (val['id'] == 374) { _self._aPS((_data['myTeam'] || null), ev); }
                else if (val['id'] == 583) { _self._aUT((_data['myTeam'] || null), ev); }
                else if (val['id'] == 592) { _self._aWS((_data['myTeam'] || null), ev); }
                else if (val['id'] == 229) { _self._aHW((_data['myTeam'] || null), ev); }
                else if (val['id'] == 34)  { _self._aBP((_data['myTeam'] || null), ev); }
                else {
                  $('.MoveBox').html('<center><img src="/img/loader/loader.gif" width="100"></center>');
                  _self._action({'targetAtk': val['id']});
                }
              });

              return $mv;
            })()
          );
        });
        if (tpl.length) {
          _element_poke_info_my_move.empty().append(tpl);
        } else if (typeof info['atkList'] !== 'undefined') {
          _element_poke_info_my_move.empty();
        }
      } else if (info['atk1'] || info['atk2'] || info['atk3'] || info['atk4']) {
        _element_poke_info_my_move.html((info['atk1']||'') + (info['atk2']||'') + (info['atk3']||'') + (info['atk4']||''));
      } else if (typeof info['atkList'] !== 'undefined') {
        _element_poke_info_my_move.empty();
      }
    }

    // Шары врага (если сервер отдаёт готовый HTML)
    if (info['BallsPoke'] && _element_enemy_ball) {
      _element_enemy_ball.html(info['BallsPoke']);
    }

    // Статы
    if (info['statMod']) {
      var tplS = [];
      $.each(info['statMod'], function(key, val) {
        var cnt, c;
        if (val['plus']) { cnt = 'Plus';  c = "+" + val['plus']; }
        else if (val['minus']) { cnt = 'Minus'; c = "-" + val['minus']; }
        else { cnt = ''; c = ''; }
        tplS.push(
          $('<div />', {'class': 'Status'}).append(
            (val['plus'] ? '<div class="statusimg ' + key + cnt + '"><span class="greennumber">' + val['plus'] + '</span></div>' : ''),
            (val['minus'] ? '<div class="statusimg ' + key + cnt + '"><span class="rednumber">' + val['minus'] + '</span></div>' : '')
          )
        );
      });
      if (my && _element_poke_info_my_stat) {
        _element_poke_info_my_stat.empty().append(tplS);
      } else if (_element_poke_info_enemy_stat) {
        _element_poke_info_enemy_stat.empty().append(tplS);
      }
    } else {
      if (_element_poke_info_my_stat) _element_poke_info_my_stat.empty();
      if (_element_poke_info_enemy_stat) _element_poke_info_enemy_stat.empty();
    }

    // Статусы
    if (info['statusList']) {
      var tplA = [];
      $.each(info['statusList'], function(x, y) {
        var s = (y.type == 'item') ? '<span class="rednumber">' + info['item_battle'] + '</span>' : '';
        tplA.push(
          $('<div />', {'class': 'Status'}).append(
            '<div class="statusimg ' + y.type + '" style="background-position: -90px -90px;">' + s + '</div>'
          )
        );
      });
      if (my && _element_poke_info_my_stat_a) {
        _element_poke_info_my_stat_a.empty().append(tplA);
      } else if (_element_poke_info_enemy_stat_a) {
        _element_poke_info_enemy_stat_a.empty().append(tplA);
      }
    }
  };

  // -----------------------------
  // Имя соперника (дикий/пользователь)
  // -----------------------------
  this._viewName = function(enemyOrUser, enemyTarget, allInfo) {
    if (allInfo && allInfo['npc'] && allInfo['npc'] != "0") {
      return $('<div/>', {'class': 'BlackText','html' : $('<div/>', { 'class': 'wildpokbattle', 'text': allInfo['npc']['name'] })});
    }

    var isWild   = !(enemyOrUser && enemyOrUser['id'] > 0);
    var canCatch = !!(enemyOrUser && (enemyOrUser['catch'] === 1 || enemyOrUser['catch'] === '1' || enemyOrUser['catch'] === true));
    var isShiny  = !!(enemyOrUser && (enemyOrUser['type'] === 'shine' || enemyOrUser['type_text'] === 'shine') ||
                      (enemyTarget && enemyTarget['type'] === 'shine'));
    var rare     = Number(enemyTarget && enemyTarget['rare'] || 0);

    var $wrap = $('<div/>', {
      'class': isWild ? ('wild ' + (canCatch ? '' : 'red')) : ('user-link u-' + (enemyOrUser['group'] || 6))
    }).css({display:'flex', flexDirection:'column', alignItems:'flex-start', gap:'4px', fontSize:'12px', lineHeight:'16px'});

    var titleText = isWild ? 'Дикий покемон' : (enemyOrUser && enemyOrUser['login'] ? enemyOrUser['login'] : '');
    var $title = $('<span/>', { text: titleText }).css({ fontWeight: isWild ? 600 : 500, color: isWild ? '#2b7a0b' : '' });

    function pill(text, bg, fg) {
      return $('<span/>', { text })
        .css({display:'inline-block', padding:'1px 6px', borderRadius:'999px', fontSize:'11px', lineHeight:'14px', background:bg, color:fg, border:'1px solid rgba(0,0,0,.08)'});
    }
    function iconPill(iconHtml, title, bg, fg) {
      return $('<span/>', { html: iconHtml, title })
        .css({display:'inline-flex', alignItems:'center', justifyContent:'center', width:'18px', height:'18px', borderRadius:'50%', fontSize:'11px', lineHeight:'18px', background:bg, color:fg, border:'1px solid rgba(0,0,0,.08)'});
    }

    if (isWild) {
      var $metaLine = $('<div/>').css({display:'inline-flex', gap:'6px', alignItems:'center', flexWrap:'wrap'});
      var catchTitle = canCatch ? 'Можно поймать' : 'Нельзя поймать';
      $metaLine.append( pill(catchTitle, canCatch ? '#e9f8ec' : '#fdeeed', canCatch ? '#137333' : '#a5282c').attr('title', catchTitle) );

      if (rare && window.Lang && window.Lang.pokemon_chanse_location && window.Lang.pokemon_chanse_location[rare]) {
        var rareText = window.Lang.pokemon_chanse_location[rare][1];
        $metaLine.append( pill('R'+rare, '#f3f4f6', '#374151').attr('title','Редкость: '+rareText) );
      }

      if (isShiny) $metaLine.append( iconPill('<i class="fa fa-star"></i>','Shiny','#faf5ff','#7c3aed') );

      $wrap.append($title, $metaLine);
    } else {
      $wrap.append($title);
    }

    return $wrap;
  };

  // -----------------------------
  // Спрайт (если нужен)
  // -----------------------------
  this._imgPoke = function(pokeInfo, mini){
    var num = (pokeInfo['basenum'] || 0);
    num = (num < 10 ? '00'+num : num < 100 ? '0'+num : num);
    var form = (pokeInfo['form'] != "0") ? "_"+pokeInfo['form'] : "d";
    return '/img/pokemons/sprite/'+pokeInfo['type']+'/'+num+form+'.gif';
  };

  // -----------------------------
  // Универсальная модалка для спец-атак (выбор союзника)
  // -----------------------------
  this._showPokeActionDiv = function (team, e, actionKey, title) {
    $('.pkx-poke-pop, .GiveDiv').remove();

    (function injectCSS(){
      if (document.getElementById('pkx-poke-css')) return;
      var css =
        '.pkx-poke-pop{position:fixed;z-index:300000;min-width:280px;max-width:92vw;width:320px;background:#fff;border:1px solid #e6eafe;border-radius:14px;box-shadow:0 18px 46px rgba(23,35,74,.18),0 3px 10px rgba(106,98,140,.12);font-family:Nunito,Inter,Arial,sans-serif;color:#1b2b4f;overflow:hidden}'+
        '.pkx-poke-head{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f0eef9;border-bottom:1px solid #d7d3ea}'+
        '.pkx-poke-title{font-weight:800;color:#6b5aa3;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'+
        '.pkx-poke-close{border:1px solid #d7d3ea;background:#f7f6fd;border-radius:9px;width:26px;height:26px;line-height:24px;text-align:center;color:#8f76c1;font-weight:900;cursor:pointer}'+
        '.pkx-poke-close:hover{background:#ece8fb}'+
        '.pkx-poke-body{padding:8px}'+
        '.pkx-poke-list{max-height:56vh;overflow:auto;display:flex;flex-direction:column;gap:6px}'+
        '.pkx-poke-item{display:flex;align-items:center;gap:10px;width:100%;border:1px solid #e6eafe;background:#fff;border-radius:12px;padding:8px 10px;cursor:pointer;text-align:left}'+
        '.pkx-poke-item:hover{border-color:#2f74ff;box-shadow:0 10px 22px rgba(46,116,255,.14)}'+
        '.pkx-poke-item .ico{width:44px;height:44px;border:1px solid #dbe6ff;border-radius:10px;background:#eef3ff;display:grid;place-items:center;flex:0 0 auto}'+
        '.pkx-poke-item .ico img{max-width:36px;max-height:36px}'+
        '.pkx-poke-item .txt{display:flex;flex-direction:column;min-width:0}'+
        '.pkx-poke-item .name{font-weight:800;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'+
        '.pkx-poke-item .sub{font-size:12px;color:#6f7b95;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}';
      var st = document.createElement('style');
      st.id = 'pkx-poke-css';
      st.type = 'text/css';
      st.appendChild(document.createTextNode(css));
      document.head.appendChild(st);
    })();

    var $pop = $('<div/>', {'class':'pkx-poke-pop', role:'dialog', 'aria-label':'Выбор покемона'});
    var $head = $('<div/>', {'class':'pkx-poke-head'}).appendTo($pop);
    $('<div/>', {'class':'pkx-poke-title', text: (title ? (title+' на') : 'Выберите покемона')}).appendTo($head);
    $('<button/>', {type:'button', 'class':'pkx-poke-close', html:'&times;'})
      .appendTo($head)
      .on('click', function(ev){ ev.stopPropagation(); $pop.remove(); $(document).off('.pkxPoke'); });

    var $body = $('<div/>', {'class':'pkx-poke-body'}).appendTo($pop);
    var $list = $('<div/>', {'class':'pkx-poke-list'}).appendTo($body);

    var hasAny = false;
    $.each(team || [], function (key, val) {
      if (val && +val.hp > 0) {
        hasAny = true;
        var typeClass = (val.type ? (val.type + '-color') : '');
        var num = val.basenum2 || val.basenum || val.id || '?';
        var name = val.name || ('#'+num);
        var $item = $('<button/>', {'class':'pkx-poke-item', type:'button'}).append(
          $('<div/>', {'class':'ico'}).append(
            $('<img/>', { src:'/img/pokemons/animation/'+ num +'.png', alt:'' })
              .on('error', function(){ this.src='/img/pokemons/mini/0.png'; })
          ),
          $('<div/>', {'class':'txt'}).append(
            $('<div/>', {'class':'name '+typeClass, html:'#'+num+' '+name}),
            $('<div/>', {'class':'sub', text:(val.type ? ('Тип: '+val.type) : 'Готов к действию')})
          )
        ).on('click', function(ev){
          ev.stopPropagation();
          try{
            var actionObj = {}; actionObj[actionKey] = val.id;
            _self._action(actionObj);
          } finally {
            $pop.remove();
            $(document).off('.pkxPoke');
          }
        });
        $list.append($item);
      }
    });

    if (!hasAny) {
      $list.append($('<div/>', {'class':'pkx-poke-empty', text:'Нет покемонов, доступных для этого действия.'}));
    }

    $('body').append($pop);

    function centerPopover(el){
      el.style.left = '50%';
      el.style.top  = '50%';
      el.style.transform = 'translate(-50%, -50%)';
    }
    function placeNearPoint(el, pageX, pageY){
      var cx = pageX - (window.pageXOffset || 0);
      var cy = pageY - (window.pageYOffset || 0);
      var r = el.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight, pad = 8;
      var left = Math.min(Math.max(pad, cx + 10), vw - r.width  - pad);
      var top  = Math.min(Math.max(pad, cy + 10), vh - r.height - pad);
      el.style.left = left + 'px';
      el.style.top  = top  + 'px';
      el.style.transform = 'none';
    }

    var isCoarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    var isSmall  = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;

    if (typeof getElCord === 'function' && e && typeof e.pageX === 'number') {
      $pop.css(getElCord(e, $pop, [-25, 6]));
      var rr = $pop[0].getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight, pad = 8;
      var l = Math.min(Math.max(pad, rr.left), vw - rr.width  - pad);
      var t = Math.min(Math.max(pad, rr.top ), vh - rr.height - pad);
      $pop.css({ left:l, top:t, transform:'none' });
    } else if (!isCoarse && !isSmall && e && typeof e.pageX === 'number') {
      placeNearPoint($pop[0], e.pageX, e.pageY);
    } else {
      centerPopover($pop[0]);
    }

    setTimeout(function(){
      $(document)
        .on('mousedown.pkxPoke', function(ev){
          if (!$(ev.target).closest('.pkx-poke-pop').length){
            $pop.remove(); $(document).off('.pkxPoke');
          }
        })
        .on('keydown.pkxPoke', function(ev){
          if (ev.key === 'Escape'){
            $pop.remove(); $(document).off('.pkxPoke');
          }
        });
    }, 0);
  };

  // Индивидуальные хелперы для спец-атак
  this._aPS = function (team, e) { this._showPokeActionDiv(team, e, 'aPS', 'Прощальный удар'); };
  this._aUT = function (team, e) { this._showPokeActionDiv(team, e, 'aUT', 'Подставной ход'); };
  this._aWS = function (team, e) { this._showPokeActionDiv(team, e, 'aWS', 'Разнонаправленный ток'); };
  this._aHW = function (team, e) { this._showPokeActionDiv(team, e, 'aHW', 'Молитву'); };
  this._aHH = function (team, e) { this._showPokeActionDiv(team, e, 'aHH', 'Рука помощи'); };
  this._aBP = function (team, e) { this._showPokeActionDiv(team, e, 'aBP', 'Эстафета'); };
  this._aAM = function (team, e) { this._showPokeActionDiv(team, e, 'aAM', 'Ароматный туман'); };

  // -----------------------------
  // Выбор своей команды (модалка замены) — без урезаний
  // -----------------------------
  this._viewMyTeam = function(team, delID, title, e, type = false) {
    if (type === false) type = 3;
    if (!(team && _element_window_started)) return;
    delID = delID || 0;

    (function injectCSS(){
      if (document.getElementById('pkx-team-css')) return;
      var css =
        '.pkx-team-pop{position:fixed;z-index:300000;left:0;top:0;transform:none;min-width:260px;width:300px;max-width:92vw;background:#fff;border:1px solid #e6eafe;border-radius:14px;box-shadow:0 18px 46px rgba(23,35,74,.18),0 3px 10px rgba(106,98,140,.12);font-family:Nunito,Inter,Arial,sans-serif;color:#1b2b4f;overflow:hidden}'+
        '.pkx-team-head{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f0eef9;border-bottom:1px solid #d7d3ea}'+
        '.pkx-team-title{font-weight:800;color:#6b5aa3;font-size:14px}'+
        '.pkx-team-close{border:1px solid #d7d3ea;background:#f7f6fd;border-radius:9px;width:26px;height:26px;line-height:24px;text-align:center;color:#8f76c1;font-weight:900;cursor:pointer}'+
        '.pkx-team-close:hover{background:#ece8fb}'+
        '.pkx-team-body{padding:6px}'+
        '.pkx-team-list{max-height:52vh;overflow:auto;display:flex;flex-direction:column;gap:6px}'+
        '.pkx-team-item{display:flex;align-items:center;gap:10px;width:100%;border:1px solid #e6eafe;background:#fff;border-radius:12px;padding:8px 10px;cursor:pointer;text-align:left}'+
        '.pkx-team-item:hover{border-color:#2f74ff;box-shadow:0 10px 22px rgba(46,116,255,.14)}'+
        '.pkx-team-item .ico{width:40px;height:40px;border:1px solid #dbe6ff;border-radius:10px;background:#eef3ff;display:grid;place-items:center;flex:0 0 auto}'+
        '.pkx-team-item .ico img{max-width:32px;max-height:32px}'+
        '.pkx-team-item .txt{display:flex;flex-direction:column;min-width:0}'+
        '.pkx-team-item .name{font-weight:800;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'+
        '.pkx-team-item .sub{font-size:12px;color:#6f7b95}'+
        '.normal-color{color:#52606d}.fire-color{color:#e25822}.water-color{color:#1d6ee3}.grass-color{color:#268f3a}'+
        '.electric-color{color:#caa700}.ice-color{color:#2aa5b3}.fighting-color{color:#b04343}.poison-color{color:#7a2fb0}'+
        '.ground-color{color:#8b6b2e}.fly-color{color:#4c7dd1}.psychic-color{color:#cc2c7a}.bug-color{color:#608a18}'+
        '.rock-color{color:#6a5a3a}.ghost-color{color:#5a52a3}.dragon-color{color:#3b57c4}.dark-color{color:#3b3b3b}'+
        '.steel-color{color:#6a7c8f}.fairy-color{color:#a951c3}'+
        '@media (max-width:768px){.pkx-team-list{max-height:60vh}}';
      var st=document.createElement('style'); st.id='pkx-team-css'; st.type='text/css';
      st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
    })();

    var $list = $('<div/>', {'class':'pkx-team-list'});
    $.each(team, function(_, val){
      if (!(val.id && delID != val.id && val.hp > 0)) return;
      var imgNum = val.basenum || val.basenum2 || val.id || 0;
      var typeCls = (val.type ? (val.type+'-color') : '');

      $('<button/>', {'class':'pkx-team-item', 'type':'button'})
        .append(
          $('<div/>', {'class':'ico'}).append(
            $('<img/>', { src:'/img/pokemons/animation/'+imgNum+'.png', alt:'' })
          ),
          $('<div/>', {'class':'txt'}).append(
            $('<div/>', {'class':'name '+typeCls, text:'#'+(val.basenum2||imgNum)+' '+(val.name||'')+' '+(val.lvl||'')+' ур.'}),
            $('<div/>', {'class':'sub',  text:'HP: '+val.hp+' / '+(val.hp_max||val.hp)})
          )
        )
        .on('click', function(ev){
          ev.stopPropagation();
          _selected = false;
          if (typeof ClassInfo !== 'undefined' && ClassInfo) {
            ClassInfo._action({type:'battle', targetPoke: val.id}, function(info){
              _self._update((info && info.battleInfo) || null);
            });
          }
          $pop.remove(); $(document).off('.pkxTeam');
        })
        .appendTo($list);
    });
    if (!$list.children().length) return;

    $('.pkx-team-pop, .GiveDiv').remove();
    var $pop = $('<div/>', {'class':'pkx-team-pop', 'role':'dialog', 'aria-label':'Выбор покемона'});
    var $head = $('<div/>', {'class':'pkx-team-head'}).appendTo($pop);
    $('<div/>', {'class':'pkx-team-title', text: (type==1 ? 'Замена покемона' : (title || 'Выберите покемона'))}).appendTo($head);
    $('<button/>', {'type':'button','class':'pkx-team-close','html':'&times;'})
      .appendTo($head)
      .on('click', function(ev){ ev.stopPropagation(); $pop.remove(); $(document).off('.pkxTeam'); });

    $('<div/>', {'class':'pkx-team-body'}).append($list).appendTo($pop);
    $('body').append($pop);

    function centerPopover(el){
      el.style.left = '50%';
      el.style.top  = '50%';
      el.style.transform = 'translate(-50%, -50%)';
    }
    function placeNearPoint(el, pageX, pageY){
      var cx = pageX - (window.pageXOffset || 0);
      var cy = pageY - (window.pageYOffset || 0);
      var r = el.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight, pad = 8;
      var left = Math.min(Math.max(pad, cx + 10), vw - r.width  - pad);
      var top  = Math.min(Math.max(pad, cy + 10), vh - r.height - pad);
      el.style.left = left + 'px';
      el.style.top  = top  + 'px';
      el.style.transform = 'none';
    }
    var isCoarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    var isSmall  = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;

    if (typeof getElCord === 'function' && e && typeof e.pageX === 'number'){
      $pop.css(getElCord(e, $pop, [-25, 6]));
      var rr = $pop[0].getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight, pad = 8;
      var l = Math.min(Math.max(pad, rr.left), vw - rr.width  - pad);
      var t = Math.min(Math.max(pad, rr.top ), vh - rr.height - pad);
      $pop.css({ left:l, top:t, transform:'none' });
    } else if (!isCoarse && !isSmall && e && typeof e.pageX === 'number'){
      placeNearPoint($pop[0], e.pageX, e.pageY);
    } else {
      centerPopover($pop[0]);
    }

    setTimeout(function(){
      $(document)
        .on('mousedown.pkxTeam', function(ev){
          if (!$(ev.target).closest('.pkx-team-pop').length){
            $pop.remove(); $(document).off('.pkxTeam');
          }
        })
        .on('keydown.pkxTeam', function(ev){
          if (ev.key === 'Escape'){
            $pop.remove(); $(document).off('.pkxTeam');
          }
        });
    }, 0);
  };

  // -----------------------------
  // Обновление раз в цикл (если используется автообновление)
  // -----------------------------
  this._refresh = function () {
    if(!_open) return;
    _self._action({ type:'view' }, function(resp){
      if (resp && resp.battleInfo) _self._update(resp.battleInfo);
    });
  };

  // -----------------------------
  // Автостарт
  // -----------------------------
  if(info){
    _self._open(info);
  }
};
// END of GameBattle
/* =========================
 * GameBattle — ЧАСТЬ 3 (надстройки/хуки и командный UX)
 * Подключать ПОСЛЕ основного класса (Части 1–2)
 * ========================= */
(function(){
  if (typeof GameBattle !== 'function') return;

  // Фичефлаги (задать до создания инстанса при необходимости)
  window.BATTLE_SUPPORT_TEAM_CREATE = (window.BATTLE_SUPPORT_TEAM_CREATE === true);
  window.BATTLE_SUPPORT_TEAM_LEAVE  = (window.BATTLE_SUPPORT_TEAM_LEAVE === true);

  // ---- Детектор PvE ----
  function _isPvE(info){
    if (!info) return false;
    if (info.npc && info.npc !== "0") return true;             // NPC бой
    if (!info.enemy || !(+info.enemy.id > 0)) return true;      // Дикий покемон
    return false;
  }

  // ---- Универсальный парсер состава команд ----
  window._extractTeam = window._extractTeam || function(info){
    if (!info) return null;

    var t = info.team || info.teams || info.command || info.commandTeam || info.teamBattle || null;
    var hinted = (info && (info.isCommandBattle === true || info.isCommandBattle == 1)) || !!t;

    var left  = t && (t.A || t.left || t.team1 || t.my || t.sideA || t.allies);
    var right = t && (t.B || t.right|| t.team2 || t.enemy|| t.sideB || t.opponents);
    function toArr(x){ return Array.isArray(x) ? x : (x ? Object.keys(x).map(k=>x[k]) : []); }
    left  = toArr(left);
    right = toArr(right);

    var orgGlobal = (t && (t.organizers || t.organizer || t.owner || t.host)) || info.organizers || info.organizer || info.owner || null;
    var orgIds = new Set();
    (Array.isArray(orgGlobal) ? orgGlobal : [orgGlobal]).forEach(function(o){
      var id = o && (o.id || o.user_id);
      if (id) orgIds.add(String(id));
    });

    function norm(m, sideTag){
      if (!m) return null;
      var poke = m.poke || m.active || {};
      var id   = m.id || m.user_id || (m.user && m.user.id) || null;
      var login= m.login || (m.user && m.user.login) || m.name || ('User#'+(id||'?'));
      var group= m.group || (m.user && m.user.group) || 6;
      var leaderLike = m.is_leader || m.leader || m.captain || m.owner || false;
      var isGlobalOrg = id ? orgIds.has(String(id)) : false;
      return {
        id: id,
        login: login,
        group: group,
        sprite: (m.sprite || poke.sprite || null),
        lvl: poke.lvl || m.lvl || null,
        hp:  poke.hp  || m.hp  || null,
        side: m.side || sideTag || null,
        isLeader: !!(leaderLike || isGlobalOrg)
      };
    }

    var A = left.map(function(x){ return norm(x,'A'); }).filter(Boolean);
    var B = right.map(function(x){ return norm(x,'B'); }).filter(Boolean);

    function normLimit(val){
      var n = parseInt(val, 10);
      return (Number.isFinite(n) && n > 0) ? n : null;
    }
    var limitA = normLimit((t && (t.limitA || t.maxA || t.teamLimitA || t.slotsA || t.capacityA)) || info.team_limit_a || info.teamLimitA || null);
    var limitB = normLimit((t && (t.limitB || t.maxB || t.teamLimitB || t.slotsB || t.capacityB)) || info.team_limit_b || info.teamLimitB || null);
    var limitBoth = normLimit((t && (t.limit || t.max || t.teamLimit || t.slots || t.capacity)) || info.team_limit || info.teamLimit || null);
    if (!limitA && limitBoth) limitA = limitBoth;
    if (!limitB && limitBoth) limitB = limitBoth;

    var nameA = (t && (t.nameA || t.teamALabel || t.leftLabel  || 'Сторона A'));
    var nameB = (t && (t.nameB || t.teamBLabel || t.rightLabel || 'Сторона B'));
    var token = (info.team_token || (t && (t.token || t.invite || t.code))) || null;
    var mySide = info.mySide || (info.my && info.my.side) || (t && (t.mySide || t.sideOfMe)) || null;

    if (!A.length && !B.length && !hinted) return null;

    var organizer = null;
    if (orgIds.size){
      var pick = function(list){
        for (var i=0;i<list.length;i++){
          if (list[i].id && orgIds.has(String(list[i].id))) return list[i];
        }
        return null;
      };
      organizer = pick(A) || pick(B) || null;
    }

    if (!mySide && info.my && info.my.id) {
      var myId = String(info.my.id);
      if (A.some(function(u){ return u.id && String(u.id) === myId; })) mySide = 'A';
      else if (B.some(function(u){ return u.id && String(u.id) === myId; })) mySide = 'B';
    }

    return {
      A, B, nameA, nameB, token,
      limitA: limitA, limitB: limitB,
      countA: A.length, countB: B.length,
      isTeam:true, organizer: organizer, mySide: mySide
    };
  };

  // ---------- Синхронизация бейджей рядом с «Раунд» ----------
  GameBattle.prototype._syncTeamBadges = function(){
    var info = this._data || {};
    var team = window._extractTeam(info);
    var isTeam = !!(info && (((info.isCommandBattle === true || info.isCommandBattle == 1)) || team));
    var isPve  = _isPvE(info);

    var $badge = $('#battle-team-badge');
    if ($badge.length){
      $badge.toggle(isTeam && !isPve);
      if (isTeam && !isPve) $badge.text('Командный бой');
    }
  };

  // ---------- КНОПКА «Командный бой» + МОДАЛКА СОСТАВА ----------
  GameBattle.prototype._installTeamUI = function(){
    var info = this._data || {};
    var isPve = _isPvE(info);

    var $bar = $('.Battle .PokemonB .Info .Buttons').first();
    if (!$bar.length) return this;

    if (!$bar.find('.__teamBtn').length) {
      var self = this;
      var $btn = $('<div/>', {
        'class':'buttonFight Button __teamBtn',
        'title':'Сделать бой командным',
        'html':'<i class="fas fa-users"></i>'
      }).on('click', function(){ self._onTeamButtonClick(); });

      var $after = $bar.find('.buttonFight.Button').eq(1);
      if ($after.length) $btn.insertAfter($after); else $bar.prepend($btn);
      this.__team_button__ = $btn;
    }
    // где угодно после рендера/обновления UI, например в _installTeamUI():
$('#battle-team-badge')
  .off('click.gbteam')
  .on('click.gbteam', () => this._onTeamButtonClick());

    var team = window._extractTeam(info);
    var isTeam = !!(info && (((info.isCommandBattle === true || info.isCommandBattle == 1)) || team));

    if (this.__team_button__) {
      this.__team_button__.toggle(!isPve);
      this.__team_button__.toggleClass('active', isTeam && !isPve);
      this.__team_button__.attr('title', (isTeam && !isPve) ? 'Показать состав команд' : 'Сделать бой командным');
    }

    this._syncTeamBadges();
    return this;
  };

  GameBattle.prototype._onTeamButtonClick = function(){
    var info = this._data || {};
    var isPve = _isPvE(info);
    var team = window._extractTeam(info);

    if (isPve) {
      if (window.Game?.notifications?.main){
        Game.notifications.main('Командные бои недоступны в PvE.', 'error');
      }
      return;
    }

    if (team && (team.A.length || team.B.length || (info && (info.isCommandBattle === true || info.isCommandBattle == 1)))) {
      this._openTeamModal(team, info);
      return;
    }

    if (window.BATTLE_SUPPORT_TEAM_CREATE) {
      var self = this;
      this._action({ action:'make_team' }, function(resp){
        if (resp && resp.battleInfo) self._update(resp.battleInfo);
        else self.refresh();
        if (resp && resp.error && window.Game?.notifications?.main){
          Game.notifications.main(resp.error, 'error');
        } else if (window.Game?.notifications?.main){
          Game.notifications.main('Командный бой создан. Пригласите союзников!', 'success');
        }
      });
    } else {
      this._openTeamModal({A:[],B:[],nameA:'Сторона A',nameB:'Сторона B', token:null, isTeam:false}, info, { hintCreate:true });
    }
  };

  GameBattle.prototype._openTeamModal = function(team, info, opts){
    opts = opts || {};
    (function injectCSS(){
      if (document.getElementById('pkx-teamcfg-css')) return;
      var css =
        '.pkx-teamcfg{position:fixed;z-index:300000;left:50%;top:50%;transform:translate(-50%,-50%);'+
        'background:#fff;border:1px solid #e6eafe;border-radius:16px;min-width:320px;width:560px;max-width:90vw;'+
        'box-shadow:0 24px 64px rgba(23,35,74,.18),0 4px 12px rgba(106,98,140,.12);font-family:Nunito,Inter,Arial,sans-serif;overflow:hidden}'+
        '.pkx-teamcfg-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#eff2fb;border-bottom:1px solid #dae2ff}'+
        '.pkx-teamcfg-title{font-weight:900;color:#3e4a7a;font-size:15px}'+
        '.pkx-teamcfg-close{border:1px solid #d7d3ea;background:#f7f6fd;border-radius:9px;width:28px;height:28px;line-height:26px;text-align:center;color:#8f76c1;font-weight:900;cursor:pointer}'+
        '.pkx-teamcfg-close:hover{background:#ece8fb}'+
        '.pkx-teamcfg-body{padding:12px}'+
        '.pkx-teamcfg-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}'+
        '.pkx-teamcfg-col{border:1px dashed #e2e8ff;border-radius:12px;padding:10px;background:#fbfcff}'+
        '.pkx-teamcfg-col h4{margin:0 0 8px;font-size:13px;color:#4a5aa1}'+
        '.pkx-teamcfg-user{display:flex;gap:8px;align-items:center;border:1px solid #e6eafe;background:#fff;border-radius:10px;padding:6px 8px}'+
        '.pkx-teamcfg-user .avatar{width:34px;height:34px;border-radius:8px;background:#eef3ff;border:1px solid #dbe6ff;display:grid;place-items:center;font-weight:800;color:#7381b6}'+
        '.pkx-teamcfg-user .meta{display:flex;flex-direction:column;min-width:0}'+
        '.pkx-teamcfg-user .login{font-size:13px;font-weight:800;color:#2b3a6e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}'+
        '.pkx-teamcfg-user .sub{font-size:12px;color:#6f7b95}'+
        '.pkx-teamcfg-empty{padding:8px;border:1px dashed #d9dff7;background:#fff;border-radius:10px;color:#6f7b95;font-size:12px;text-align:center}'+
        '.pkx-teamcfg-footer{display:flex;gap:8px;justify-content:flex-end;padding:10px;border-top:1px solid #eaeefe;background:#fbfbfe}'+
        '.pkx-teamcfg-btn{border:1px solid #d7dff9;background:#f1f5ff;border-radius:10px;padding:8px 10px;font-weight:700;color:#3659a7;cursor:pointer}'+
        '.pkx-teamcfg-btn[disabled]{opacity:.55;cursor:default}'+
        '.pkx-teamcfg-btn:hover{background:#e6edff}'+
        '.pkx-teamcfg-badge{display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;background:#e9f2ff;border:1px solid #cfe2ff;color:#0b5ed7;font-weight:700;font-size:12px}'+
        '.pkx-teamcfg-crown{margin-left:6px;color:#c58f18}'+
        '.pkx-teamcfg-chip{display:inline-block;margin-left:8px;padding:1px 6px;border-radius:999px;background:#f3f4f6;border:1px solid #e5e7eb;font-size:11px;color:#374151}'+
        '@media (max-width:560px){.pkx-teamcfg{width:96vw}.pkx-teamcfg-grid{grid-template-columns:1fr}}';
      var st=document.createElement('style'); st.id='pkx-teamcfg-css'; st.type='text/css';
      st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
    })();

    $('.pkx-teamcfg').remove();

    var $root = $('<div/>', {'class':'pkx-teamcfg','role':'dialog','aria-label':'Командный бой'});
    var $head = $('<div/>', {'class':'pkx-teamcfg-head'}).appendTo($root);

    var titleHTML = 'Командный бой';
    if (team && team.organizer){
      titleHTML += ' <span class="pkx-teamcfg-chip">Организатор: '+ $('<div>').text(team.organizer.login).html() +'</span>';
    }
    $('<div/>', {'class':'pkx-teamcfg-title', html:titleHTML}).appendTo($head);
    $('<button/>', {'class':'pkx-teamcfg-close', html:'&times;'}).appendTo($head).on('click', function(){ $root.remove(); });

    var $body = $('<div/>', {'class':'pkx-teamcfg-body'}).appendTo($root);

    var $status = $('<div/>').css({display:'flex',alignItems:'center',gap:'8px',margin:'0 0 10px'});
    var isTeam = !!(team && (team.isTeam || (info && (info.isCommandBattle === true || info.isCommandBattle == 1))));
    $status.append($('<span/>', {text: 'Режим:'}));
    $status.append($('<span/>', {'class':'pkx-teamcfg-badge', text: isTeam ? 'Командный' : 'Обычный'}));
    if (team && team.mySide) {
      $status.append($('<span/>', {'class':'pkx-teamcfg-chip', text:'Ваша сторона: '+team.mySide}));
    }
    if (team && team.token) {
      var $copy = $('<button/>', {'class':'pkx-teamcfg-btn', text:'Скопировать приглашение'}).on('click', function(){
        try { navigator.clipboard.writeText(String(team.token)); } catch(_){}
        if (window.Game?.notifications?.main) Game.notifications.main('Токен приглашения скопирован.', 'success');
      });
      $status.append($copy);
    } else if (opts.hintCreate) {
      $status.append($('<div/>').css({fontSize:'12px',color:'#6f7b95'}).text('Обновите бэкенд и включите BATTLE_SUPPORT_TEAM_CREATE для приглашений.'));
    }
    $body.append($status);

    var $grid = $('<div/>', {'class':'pkx-teamcfg-grid'}).appendTo($body);

    function userCard(u){
      var letter = (u.login||'?').slice(0,1).toUpperCase();
      var $c = $('<div/>', {'class':'pkx-teamcfg-user'});
      var $ava = $('<div/>', {'class':'avatar', text:letter});
      if (u.sprite) $ava.empty().append($(u.sprite).attr('width',32).attr('height',32));
      var $name = $('<div/>', {'class':'login', text:u.login});
      if (u.isLeader) $name.append(' <i class="fas fa-crown pkx-teamcfg-crown"></i>');
      $c.append($ava, $('<div/>', {'class':'meta'}).append(
        $name,
        $('<div/>', {'class':'sub', text:(u.lvl ? ('Ур. '+u.lvl) : 'Участник')})
      ));
      return $c;
    }
    function fillCol($col, title, list, limit){
      var count = (list && list.length) ? list.length : 0;
      var suffix = (limit ? (' (' + count + '/' + limit + ')') : (' (' + count + ')'));
      $('<h4/>', {text:title + suffix}).appendTo($col);
      if (list && list.length) {
        list.forEach(function(u){ $col.append(userCard(u)); });
      } else {
        $col.append($('<div/>', {'class':'pkx-teamcfg-empty', text:'Пока пусто'}));
      }
    }

    var $colA = $('<div/>', {'class':'pkx-teamcfg-col'}).appendTo($grid);
    var $colB = $('<div/>', {'class':'pkx-teamcfg-col'}).appendTo($grid);
    fillCol($colA, (team && team.nameA) || 'Сторона A', (team && team.A) || [], team && team.limitA);
    fillCol($colB, (team && team.nameB) || 'Сторона B', (team && team.B) || [], team && team.limitB);

    var $footer = $('<div/>', {'class':'pkx-teamcfg-footer'}).appendTo($root);
    var mySide = (team && team.mySide) || null;
    var limitA = team && team.limitA;
    var limitB = team && team.limitB;
    var countA = (team && typeof team.countA !== 'undefined') ? team.countA : ((team && team.A) ? team.A.length : 0);
    var countB = (team && typeof team.countB !== 'undefined') ? team.countB : ((team && team.B) ? team.B.length : 0);
    var isFullA = !!(limitA && countA >= limitA);
    var isFullB = !!(limitB && countB >= limitB);

    if (isTeam && !mySide && !_isPvE(info)) {
      var selfJoin = this;
      $('<button/>', {'class':'pkx-teamcfg-btn', text:'Вступить за сторону A', disabled: isFullA})
        .appendTo($footer)
        .on('click', function(){
          if (isFullA) return;
          selfJoin._action({action:'join_team', side: 1}, function(resp){
            if (resp && resp.battleInfo) selfJoin._update(resp.battleInfo);
            else selfJoin.refresh();
            if (window.Game?.notifications?.main) Game.notifications.main('Вы присоединились к стороне A.', 'success');
          });
          $root.remove();
        });
      $('<button/>', {'class':'pkx-teamcfg-btn', text:'Вступить за сторону B', disabled: isFullB})
        .appendTo($footer)
        .on('click', function(){
          if (isFullB) return;
          selfJoin._action({action:'join_team', side: 2}, function(resp){
            if (resp && resp.battleInfo) selfJoin._update(resp.battleInfo);
            else selfJoin.refresh();
            if (window.Game?.notifications?.main) Game.notifications.main('Вы присоединились к стороне B.', 'success');
          });
          $root.remove();
        });
    } else if (!isTeam && window.BATTLE_SUPPORT_TEAM_CREATE && !_isPvE(info)) {
      var self = this;
      $('<button/>', {'class':'pkx-teamcfg-btn', text:'Сделать командным'})
        .appendTo($footer)
        .on('click', function(){ $root.remove(); self._onTeamButtonClick(); });
    } else if (isTeam && window.BATTLE_SUPPORT_TEAM_LEAVE) {
      var self = this;
      $('<button/>', {'class':'pkx-teamcfg-btn', text:'Покинуть команду'})
        .appendTo($footer)
        .on('click', function(){
          self._action({action:'leave_team'}, function(resp){
            if (resp && resp.battleInfo) self._update(resp.battleInfo);
            else self.refresh();
          });
          $root.remove();
        });
    }
    $('<button/>', {'class':'pkx-teamcfg-btn', text:'Закрыть'}).appendTo($footer).on('click', function(){ $root.remove(); });

    $('body').append($root);

    setTimeout(function(){
      $(document)
        .on('mousedown.pkxTeamCfg', function(ev){
          if (!$(ev.target).closest('.pkx-teamcfg').length){ $root.remove(); $(document).off('.pkxTeamCfg'); }
        })
        .on('keydown.pkxTeamCfg', function(ev){
          if (ev.key === 'Escape'){ $root.remove(); $(document).off('.pkxTeamCfg'); }
        });
    }, 0);
  };

  // ---------- Обёртки _open/_update для установки UI/бэйджа ----------
  if (!GameBattle.__wrappedUpdateV2) {
    var __origUpdate = GameBattle.prototype._update;
    GameBattle.prototype._update = function(info, first){
      __origUpdate.call(this, info, first);
      try { this._installTeamUI(); } catch(_){}
      try { this._syncTeamBadges(); } catch(_){}
    };
    GameBattle.__wrappedUpdateV2 = true;
  }

  if (!GameBattle.__wrappedOpenTeamV2) {
    var __origOpen = GameBattle.prototype._open;
    GameBattle.prototype._open = function(data){
      __origOpen.call(this, data);
      try { this._installTeamUI(); } catch(_){}
      try { this._syncTeamBadges(); } catch(_){}
    };
    GameBattle.__wrappedOpenTeamV2 = true;
  }

  // ---------- Экстра: публичные методы ----------
  GameBattle.prototype.refresh = function(){
    if (typeof ClassInfo !== 'undefined' && ClassInfo && typeof ClassInfo._action === 'function'){
      ClassInfo._action({type:'battle'}, (resp)=>{
        if (resp && resp.battleInfo) this._update(resp.battleInfo);
      });
    }
    return this;
  };
  GameBattle.prototype.pauseTimer = function(){ if (this._stopTurnTimer) this._stopTurnTimer(); return this; };
  GameBattle.prototype.resumeTimer = function(){ return this.refresh(); };
  GameBattle.prototype.destroy = function(){ if (this._close) this._close(); };

  // ---------- Экспорт геттеров состояния ----------
  Object.defineProperties(GameBattle.prototype, {
    data: { get(){ return this._data || {}; } },
    isOpen: { get(){ return !!this._open; } },
    isTeamBattle: {
      get(){
        var t = window._extractTeam(this._data);
        if (_isPvE(this._data)) return false;
        return !!(this._data && (((this._data.isCommandBattle === true || this._data.isCommandBattle == 1)) || t));
      }
    }
  });

})();




var i = {
    "stats":[
        ["sdef", 1, "minus", 50]
    ]
};

/*
var i = {
    "status":[
        ["chance", "name", "count", "val"]
    ]
};

/*
*/
function PokeInfoClose(){
  $(".window.pokeInfo").toggle();
};
function SmileListClose() {
  $(".window.smileList").remove(); // Полностью удаляет окно
  _element_smile_list = null; // Обнуляет переменную, чтобы окно можно было открыть заново
};
// Центрирует окно в центре, если оно не было передвинуто
function showTooltipAtCenter(tooltipSelector = '.tooltip') {
  const tooltip = document.querySelector(tooltipSelector);
  if (!tooltip) return;
  // Центрируем только если окно НЕ было перемещено пользователем
  if (!tooltip._moved) {
    tooltip.classList.add('centered');
    tooltip.style.display = 'block';
  } else {
    tooltip.style.display = 'block';
  }
}

// Универсальная функция drag с поддержкой снятия центрирования
function makeDraggableExceptButtons(selector, headerSelector = null) {
  document.querySelectorAll(selector).forEach(function(modal) {
    const dragHandle = headerSelector ? (modal.querySelector(headerSelector) || modal) : modal;
    dragHandle.style.cursor = 'grab';

    dragHandle.onmousedown = function(e) {
      // Не даём drag если клик по интерактивному элементу
      if (
        ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON', 'LABEL'].includes(e.target.tagName) ||
        e.target.closest('.Buttons, button, input, select, textarea, label, [contenteditable="true"]')
      ) return;
      if (e.button !== 0 || window.innerWidth < 1024) return;

      // Снимаем центрирование только при первом drag
      if (modal.classList.contains('centered')) {
        modal.classList.remove('centered');
        // Сохраняем текущую позицию как фиксированную
        const rect = modal.getBoundingClientRect();
        modal.style.left = rect.left + 'px';
        modal.style.top = rect.top + 'px';
        modal.style.transform = 'none';
        modal._moved = true; // помечаем, что окно было сдвинуто
      }

      e.preventDefault();
      dragHandle.style.cursor = 'grabbing';
      let shiftX = e.clientX - modal.getBoundingClientRect().left;
      let shiftY = e.clientY - modal.getBoundingClientRect().top;

      function moveAt(pageX, pageY) {
        let newLeft = pageX - shiftX;
        let newTop = pageY - shiftY;
        const ww = window.innerWidth, wh = window.innerHeight;
        const mw = modal.offsetWidth, mh = modal.offsetHeight;
        newLeft = Math.max(0, Math.min(newLeft, ww - mw));
        newTop = Math.max(0, Math.min(newTop, wh - mh));
        modal.style.left = newLeft + 'px';
        modal.style.top = newTop + 'px';
        modal.style.right = '';
        modal.style.bottom = '';
        modal.style.margin = '0';
        modal.style.position = 'fixed';
        modal.style.transform = 'none';
        modal._moved = true;
      }

      function onMouseMove(e) { moveAt(e.clientX, e.clientY); }
      document.addEventListener('mousemove', onMouseMove);
      document.onmouseup = function() {
        dragHandle.style.cursor = 'grab';
        document.removeEventListener('mousemove', onMouseMove);
        document.onmouseup = null;
      };
    };
    dragHandle.ondragstart = () => false;
  });
}
function swapTagMobile(a) {
    if (a == 1) {
        $('.TopWorldMobile .Right').html('<i class="fa fa-map-signs"></i>');
        $('.TopWorldMobile .Right').attr('onclick', 'swapTagMobile(0)');
        openChatPopover();
    } else {
        $('.TopWorldMobile .Right').html('<i class="fa fa-comment-dots"></i>');
        $('.TopWorldMobile .Right').attr('onclick', 'swapTagMobile(1)');
        closeChatPopover();
    }
    $('.Trainers').hide();
    $('.TopMenu').hide();

    // Перемещение el_wild при переключении (чтобы не пропадала)
    if (window.device && device.mobile && device.mobile()) {
        $('.el_wild').insertAfter('.TopWorldMobile .Right');
    }
}

// Открыть чат-поповер (поверх остального)
function openChatPopover() {
    $('.ChatBox.mobile').show();
    $('body').css('overflow', 'hidden'); // чтобы фон не скроллился (по желанию)
}

// Закрыть чат-поповер
function closeChatPopover() {
    $('.ChatBox.mobile').hide();
    $('body').css('overflow', ''); // вернуть скролл фону
}

// По умолчанию скрываем чат на старте страницы
$(function() {
    $('.ChatBox.mobile').hide();
});
// Функция автоскролла для любого контейнера чата
function scrollChatToBottom($container, smooth = true) {
    if (!$container || !$container.length) return;
    // Проверяем, включён ли автоскролл
    var autoScroll = true;
    if (_element_scrolling.length) {
        autoScroll = _element_scrolling.prop('checked');
    }
    if (!autoScroll) return;

    if (smooth) {
        $container.stop().animate({ scrollTop: $container[0].scrollHeight }, 300);
    } else {
        $container[0].scrollTop = $container[0].scrollHeight;
    }
}

// Глобальная функция для вставки смайлов и стикеров
function insertEmojiToChatField(emojiName) {
    // 1. Фокус на активном поле ввода (десктоп в приоритете)
    let field = document.activeElement;
    // Если активное поле не chat_send или chat_send_desktop — выбираем по видимости
    if (!field || (field.id !== "chat_send" && field.id !== "chat_send_desktop")) {
        // Проверяем видимость десктопного поля
        let desktopField = document.getElementById('chat_send_desktop');
        if (desktopField && $(desktopField).is(':visible')) {
            field = desktopField;
        } else {
            // Если десктопный не виден — используем мобильный
            let mobileField = document.getElementById('chat_send');
            if (mobileField && $(mobileField).is(':visible')) {
                field = mobileField;
            }
        }
    }
    // Инъекция смайла в найденное поле
    if (field) {
        smile(field, ' ' + emojiName + ' ');
    }
}

// Пример инициализации:
makeDraggableExceptButtons('.tooltip', '.Header'); // если есть шапка
makeDraggableExceptButtons('.tooltip');            // если перетаскивание по всему окну
function toggleThemeFromModal(btn) {
    document.body.classList.toggle('dark-theme');
    // Обновляем текст и иконку на кнопке
    var icon = btn.querySelector('i');
    var txt = btn.querySelector('.theme-switcher-text');
    if(document.body.classList.contains('dark-theme')) {
        localStorage.setItem('site-theme', 'dark');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        txt.textContent = 'Светлая тема';
    } else {
        localStorage.setItem('site-theme', 'light');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        txt.textContent = 'Тёмная тема';
    }
}
// При загрузке страницы — восстановление темы и текста
(function () {
    var saved = localStorage.getItem('site-theme');
    if(saved === 'dark') document.body.classList.add('dark-theme');
    else document.body.classList.remove('dark-theme');
    // Если модальное уже открыто — обновить текст иконки
    setTimeout(function(){
        var btn = document.querySelector('.theme-switcher-btn');
        if(btn) toggleThemeFromModal(btn);
    }, 100);
})();
function scrollChatToBottom() {
    const chat = document.querySelector('.DivChat > .Chat');
    if(chat) {
        chat.scrollTop = chat.scrollHeight;
    }
}
// Выдать покемонов (например, при нажатии на кнопку)
function rbGivePokemons() {
  $.post('/rb/rb_handler.php', { action: 'give' }, function(resp) {
    if (resp.success) {
      alert('Покемоны выданы!');
      // Можно обновить UI, вызвать обновление команды и т.п.
    } else {
      alert('Ошибка: ' + resp.error);
    }
  }, 'json');
}

// Функция для удаления временных (РБ) покемонов пользователя через AJAX
// Вызывать после боя или при выходе с арены

function removeRbPokemons(userId) {
    fetch('/rb/remove_rb_pokemons.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'user_id=' + encodeURIComponent(userId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Временные покемоны успешно удалены.');
        } else {
            alert('Ошибка удаления временных покемонов: ' + (data.error || 'Неизвестная ошибка'));
        }
    })
    .catch(e => {
        alert('Ошибка соединения: ' + e.message);
    });
}
function centerGiveDiv() {
    var $giveDiv = $('.GiveDiv');
    if ($giveDiv.length) {
        $giveDiv.css({
            position: 'fixed',
            left: '50%',
            top: '50%',
            transform: 'translate(-50%, -50%)',
            zIndex: 99999
        });
    }
}
/* =========================================================
   makasimka.js — SOCKET BRIDGE + ADAPTERS
   Подключение к уже существующему GameSocket (из world.js)
   ========================================================= */
(function (win, $) {
  'use strict';

  // --- МАЛЕНЬКИЙ ИНДИКАТОР СОСТОЯНИЯ СОКЕТА В ВЕРХУ САЙТА ---
  // Появляется 1 раз и обновляется, не мешая твоему UI
  function ensureSocketIndicator() {
    if (document.getElementById('socket-indicator')) return;
    var css = '\
      #socket-indicator{position:fixed;z-index:99999;top:8px;right:10px;display:flex;align-items:center;gap:8px;padding:6px 8px;border:1px solid rgba(0,0,0,.06);border-radius:10px;background:#ffffffCC;backdrop-filter:saturate(130%) blur(6px);box-shadow:0 6px 16px rgba(15,23,42,.12)}\
      #socket-indicator .dot{width:10px;height:10px;border-radius:50%;border:1px solid rgba(0,0,0,.08)}\
      #socket-indicator .dot.ok{background:#22c55e}\
      #socket-indicator .dot.wait{background:#f59e0b}\
      #socket-indicator .dot.bad{background:#ef4444}\
      #socket-indicator .tx{font-size:12px;color:#334155}\
      @media(max-width:720px){#socket-indicator{top:auto;bottom:10px;right:10px;padding:5px 7px;border-radius:9px}}';
    var st = document.createElement('style');
    st.id = 'socket-indicator-style';
    st.textContent = css;
    document.head.appendChild(st);
    var box = document.createElement('div');
    box.id = 'socket-indicator';
    box.innerHTML = '<span class="dot bad"></span><span class="tx">offline</span>';
    document.body.appendChild(box);
  }
  function setSocketIndicator(state, msg) {
    ensureSocketIndicator();
    var box = document.getElementById('socket-indicator');
    if (!box) return;
    var dot = box.querySelector('.dot');
    var tx  = box.querySelector('.tx');
    dot.classList.remove('ok','wait','bad');
    if (state === 'ok') dot.classList.add('ok');
    else if (state === 'wait') dot.classList.add('wait');
    else dot.classList.add('bad');
    tx.textContent = msg || (state === 'ok' ? 'online' : (state === 'wait' ? 'reconnecting…' : 'offline'));
  }

  // --- БЕЗОПАСНЫЕ НОП-ФУНКЦИИ ДЛЯ СТАРЫХ МЕСТ ---
  function nop(){}

  // --- ADAPTER ДЛЯ GameChat: если уже есть, расширяем, если нет — создаём каркас
  if (!win.GameChat) {
    win.GameChat = function(){};
  }
  if (typeof win.GameChat.prototype !== 'object') {
    win.GameChat.prototype = {};
  }

  // Дополняем: системные сообщения, баннер-объявления, апдейты и отправка
  if (typeof win.GameChat.addSystemMessage !== 'function') {
    win.GameChat.addSystemMessage = function (text, type) {
      try {
        var $talk = $('.Chat .Talk .Message-Block.active');
        if (!$talk.length) $talk = $('.Chat .Talk .Message-Block').first();
        var cls = (type === 'success' ? 'sys-ok' : type === 'error' ? 'sys-err' : 'sys');
        var html = '<div class="msg '+cls+'"><span class="t">'+(text||'')+'</span></div>';
        $talk.append(html);
        // автоскролл если чекбокс включен
        var auto = $('.__chat_scrolls:checked').length > 0;
        if (auto) $talk.parent().scrollTop($talk.parent()[0].scrollHeight);
      } catch (e) { /* тихо */ }
    };
  }
  if (typeof win.GameChat.renderAnnouncement !== 'function') {
    win.GameChat.renderAnnouncement = function () {
      var text = (win.GameSocket && win.GameSocket.chatAnnouncement) || '';
      var id = 'chat-announcement';
      if (!text) { $('#'+id).remove(); return; }
      if (!document.getElementById(id)) {
        var css = '\
          .chat-announce{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px dashed #c7cde5;background:#f7f9ff;border-radius:10px;margin:6px 8px;color:#334155;font-size:13px}\
          .chat-announce i{opacity:.8}';
        if (!document.getElementById('chat-announce-style')) {
          $('<style id="chat-announce-style">'+css+'</style>').appendTo('head');
        }
        var $bar = $('<div id="'+id+'" class="chat-announce"><i class="fa fa-bullhorn"></i><span></span></div>');
        $('.Chat .Category').before($bar);
      }
      $('#'+id+' span').text(text);
    };
  }
  if (typeof win.GameChat._update !== 'function') {
    // ожидается GameSocket.handleEvent('chat', payload)
    win.GameChat._update = function (payload) {
      // payload: {channel, messages:[{html/text/...}], pmTab, ...}
      try {
        var channel = payload && payload.channel != null ? payload.channel : 0;
        var $blk = $('.Chat .Talk .Message-Block[data-channel="'+channel+'"]');
        if (!$blk.length) $blk = $('.Chat .Talk .Message-Block').first();
        if (payload && Array.isArray(payload.messages)) {
          payload.messages.forEach(function(m) {
            if (m && m.html) $blk.append(m.html);
            else if (m && m.text) $blk.append('<div class="msg"><span>'+m.text+'</span></div>');
          });
          var auto = $('.__chat_scrolls:checked').length > 0;
          if (auto) $blk.parent().scrollTop($blk.parent()[0].scrollHeight);
        }
      } catch (e) { /* нет критичности */ }
    };
  }
  // Отправка сообщений (мобилка и десктоп) -> в сокет
  if (typeof win.GameChat._send !== 'function') {
    win.GameChat._send = function () {
      var $inp = $('#chat_send');
      var $to  = $('#chat_user_to');
      var text = ($inp.val() || '').trim();
      var to   = ($to.val() || '').trim();
      if (!text) return;
      if (win.GameSocket) {
        win.GameSocket.emit('chat:send', { text: text, to: to || null, source: 'mobile' });
      }
      $inp.val('');
    };
  }
  if (typeof win.GameChat._send_desktop !== 'function') {
    win.GameChat._send_desktop = function () {
      var $inp = $('#chat_send_desktop');
      var $to  = $('#chat_user_to_desktop');
      var text = ($inp.val() || '').trim();
      var to   = ($to.val() || '').trim();
      if (!text) return;
      if (win.GameSocket) {
        win.GameSocket.emit('chat:send', { text: text, to: to || null, source: 'desktop' });
      }
      $inp.val('');
    };
  }

  // --- ADAPTER ДЛЯ GameBattle: аккуратно расширяем ожидаемыми методами
  if (!win.GameBattle) {
    // Если класс не прогружен — создаём тонкую оболочку, чтобы не падать
    win.GameBattle = function(){};
    win.GameBattle.prototype = {};
  } else if (typeof win.GameBattle.prototype !== 'object') {
    win.GameBattle.prototype = {};
  }

  // Внутренние ссылки на элементы (если у твоего класса уже есть — используем их)
  var GB = win.GameBattle.prototype;

  // Метод для живого обновления UI «сверху» (универсальные payload)
  if (typeof GB._update !== 'function') {
    GB._update = nop;
  }
  if (typeof GB._parserLog !== 'function') {
    GB._parserLog = function (log, autoscroll, fromSocket) {
      // ожидается массив строк/HTML или одна строка
      try {
        var $wrap = $('.ContentBattle .Log .Wrap');
        if (!$wrap.length) $wrap = $('.Battle .Content .Log .Wrap');
        if (!$wrap.length) return;
        var appendOne = function (line) {
          if (!line) return;
          var html = (typeof line === 'string' ? line : line.html || '');
          if (!html) return;
          $wrap.append(html);
        };
        if (Array.isArray(log)) log.forEach(appendOne);
        else appendOne(log);
        // прокрутка вниз, лог опускаем ниже как просили ранее
        var $log = $wrap.closest('.Log');
        $log.scrollTop($log[0].scrollHeight);
      } catch (e) {}
    };
  }
  if (typeof GB._parserLogStatus !== 'function') {
    GB._parserLogStatus = function (statusLog) {
      // обновление статусов (A/B) — сюда приходит уже сформированная разметка
      try {
        if (!statusLog) return;
        var $zone = $('.ContentBattle .Zone');
        if (!$zone.length) return;
        if (statusLog.side === 'my' && statusLog.html) {
          $zone.find('.StatusA').html(statusLog.html);
        }
        if (statusLog.side === 'enemy' && statusLog.html) {
          $zone.find('.StatusB').html(statusLog.html);
        }
      } catch (e) {}
    };
  }
  if (typeof GB._viewTime !== 'function') {
    GB._viewTime = function (timeLeft, info) {
      try {
        var $tb = $('.ContentBattle .Zone .timeBattle');
        if (!$tb.length) return;
        var secs = Math.max(0, Math.floor(+timeLeft || 0));
        var mm = String(Math.floor(secs/60)).padStart(2,'0');
        var ss = String(secs%60).padStart(2,'0');
        var bar = '<div><div><div style="width:'+Math.min(100, (info && info.progress) || 100)+'%"></div></div></div>';
        $tb.html('<span><span>'+mm+':'+ss+'</span>'+bar+'</span>');
      } catch (e) {}
    };
  }
  if (typeof GB._updatePoke !== 'function') {
    GB._updatePoke = function ($root, info, isMy) {
      // $root — контейнер покемона; info — {name, hpW, expW, img, lvl, item, ...}
      try {
        if (!$root || !$root.length || !info) return;
        if (info.name) $root.find('.Name.__name').text(info.name);
        if (info.lvl != null) $root.find('.Lvl.__lvl').text(info.lvl);
        if (info.img) $root.find('.ImagePokBattle').attr('src', info.img);
        if (info.hpW != null) $root.find('.HpBar.__hpW').css('width', info.hpW + '%');
        if (info.expW != null) $root.find('.ExpBar.__expW').css('width', info.expW + '%');
        if (info.item != null) $root.find('.Item.__item').html(info.item);
        if (info.unik != null) $root.find('.Unik.__unik').html(info.unik);
      } catch (e) {}
    };
  }
  if (typeof GB._viewMyTeam !== 'function') {
    GB._viewMyTeam = function(team, targetId, title, ev, mode) {
      // отрисовка выпадающего списка команды (минимальный UI, оставляем существующую реализацию если есть)
      // если у твоего класса есть собственное окно команды — эта заглушка не помешает.
    };
  }
  if (typeof GB._refresh !== 'function') {
    GB._refresh = function () {
      // полная перерисовка при автообновлении (если нужно), иначе no-op
    };
  }

  // --- SOCKET BRIDGE ---
  var SocketBridge = {
    inited: false,
    // активные комнаты для отладки и корректного leave
    rooms: {
      location: null,
      battle: null,
      trade: null
    },

    init: function () {
      if (this.inited) return;
      this.inited = true;

      // Интеграция с твоим GameSocket
      var gs = win.GameSocket;
      if (!gs || !gs.socket) {
        // подождём готовности
        document.addEventListener('GameSocketReady', this._lateInit.bind(this), { once: true });
        return;
      }
      this._wire(gs);
    },

    _lateInit: function () {
      var gs = win.GameSocket;
      if (gs && gs.socket) this._wire(gs);
    },

    _wire: function (gs) {
      // Индикатор
      try { setSocketIndicator(gs.isActive ? 'ok' : 'bad'); } catch(e){}

      // Снимаем предыдущие слушатели, если перевызвали
      if (this._bound) {
        try {
          gs.socket.off('connect', this._onConnect);
          gs.socket.off('disconnect', this._onDisconnect);
          gs.socket.off('connect_error', this._onConnectError);
        } catch(e){}
      }

      // Привязываем
      this._onConnect = this.onConnect.bind(this);
      this._onDisconnect = this.onDisconnect.bind(this);
      this._onConnectError = this.onConnectError.bind(this);

      gs.socket.on('connect', this._onConnect);
      gs.socket.on('disconnect', this._onDisconnect);
      gs.socket.on('connect_error', this._onConnectError);

      // Покажем объявление в чате, если есть
      try { gs.renderAnnouncementInChat(); } catch(e){}

      // Если мы уже на локации/в бою — джойним соответствующие комнаты
      // (только если ты эмитишь такие события на сервер)
      if (typeof win.ClassInfo === 'object' && win.ClassInfo.locationId) {
        this.joinLocation(win.ClassInfo.locationId);
      }
      if (win.ClassBattle && win.ClassBattle.battleId) {
        this.joinBattle(win.ClassBattle.battleId);
      }

      this._bound = true;
    },

    onConnect: function () {
      setSocketIndicator('ok', 'online');
    },
    onDisconnect: function (reason) {
      setSocketIndicator('bad', 'offline: ' + (reason || ''));
    },
    onConnectError: function (err) {
      setSocketIndicator('wait', 'reconnecting…');
    },

    // --- Rooms: локация/бой/трейд
    joinLocation: function (locationId) {
      if (!win.GameSocket || !win.GameSocket.socket) return;
      if (this.rooms.location === locationId) return;
      if (this.rooms.location) {
        win.GameSocket.emit('room:leave', { type: 'location', id: this.rooms.location });
      }
      this.rooms.location = locationId;
      win.GameSocket.emit('room:join', { type: 'location', id: locationId });
    },
    joinBattle: function (battleId) {
      if (!win.GameSocket || !win.GameSocket.socket) return;
      if (this.rooms.battle === battleId) return;
      if (this.rooms.battle) {
        win.GameSocket.emit('room:leave', { type: 'battle', id: this.rooms.battle });
      }
      this.rooms.battle = battleId;
      win.GameSocket.emit('room:join', { type: 'battle', id: battleId });
    },
    leaveBattle: function () {
      if (!win.GameSocket || !win.GameSocket.socket) return;
      if (this.rooms.battle) {
        win.GameSocket.emit('room:leave', { type: 'battle', id: this.rooms.battle });
        this.rooms.battle = null;
      }
    },
    joinTrade: function (tradeId) {
      if (!win.GameSocket || !win.GameSocket.socket) return;
      if (this.rooms.trade === tradeId) return;
      if (this.rooms.trade) {
        win.GameSocket.emit('room:leave', { type: 'trade', id: this.rooms.trade });
      }
      this.rooms.trade = tradeId;
      win.GameSocket.emit('room:join', { type: 'trade', id: tradeId });
    },
    leaveTrade: function () {
      if (!win.GameSocket || !win.GameSocket.socket) return;
      if (this.rooms.trade) {
        win.GameSocket.emit('room:leave', { type: 'trade', id: this.rooms.trade });
        this.rooms.trade = null;
      }
    },

    // --- Хелперы отправки, которыми удобно пользоваться из makasimka.js
    sendChat: function (text, to) {
      if (!text) return;
      if (win.GameSocket) win.GameSocket.emit('chat:send', { text: text, to: to || null });
    },
    sendBattleAction: function (payload) {
      // payload: {action:'move'|'switch'|'item'|..., args:{...}, battleId}
      if (win.GameSocket) win.GameSocket.emit('battle:action', payload);
    },
    sendTradeAction: function (payload) {
      if (win.GameSocket) win.GameSocket.emit('trade:action', payload);
    }
  };

  // Экспортируем мост глобально
  win.SocketBridge = SocketBridge;

  // ПРИСОЕДИНЯЕМСЯ К СОКЕТАМ, КОГДА ГЛОБАЛЬНЫЙ GameSocket ГОТОВ
  // В твоём world.js после connect хорошо бы зажечь кастомное событие:
  // document.dispatchEvent(new CustomEvent('GameSocketReady'));
  if (win.GameSocket && win.GameSocket.socket) {
    SocketBridge.init();
  } else {
    document.addEventListener('GameSocketReady', function () {
      SocketBridge.init();
    }, { once: true });
  }

  // --- ДОП. ПАТЧИ ДЛЯ ПРОИГРЫША/ПОБЕДЫ БОЯ (leave room) ---
  // Если в твоём GameBattle есть колбеки onEnd/onStart — подпишемся:
  try {
    if (typeof win.GameBattle.prototype.onStart !== 'function') {
      win.GameBattle.prototype.onStart = function (battleId) {
        if (battleId) SocketBridge.joinBattle(battleId);
      };
    }
    if (typeof win.GameBattle.prototype.onEnd !== 'function') {
      win.GameBattle.prototype.onEnd = function () {
        SocketBridge.leaveBattle();
      };
    }
  } catch (e) {}

})(window, window.jQuery);

/* ===========================================================
 *  NEW YEAR SERVER EVENT — UI/CLIENT
 * =========================================================== */
(function(){
    function nyAjax(t, d, cb){
        if(typeof $ === 'undefined') return;
        d = d || {};
        d.type = t;
        $.ajax({
            url: "/do/trainers",
            type: "POST",
            data: d,
            success: function(r){
                try {
                    if(typeof r === 'string') r = JSON.parse(r);
                } catch(e){}
                if(cb) cb(r);
            }
        });
    }

    function nyCloseModal(){
        if(typeof startGame === 'function'){
            startGame();
        }else{
            $('.GlassModalBg').remove();
            $('.GlassModal').remove();
        }
    }

    function nyOpenModal(html){
        nyCloseModal();
        $('body').append('<div class="GlassModalBg" id="NewYearEventBg"></div>');
        $('#NewYearEventBg').append('<div class="GlassModal" id="NewYearEventModal"></div>');
        $('#NewYearEventModal').html(html);

        $('#NewYearEventBg').on('click', function(e){
            if(e.target === this) nyCloseModal();
        });
    }

    function nyNotify(text){
        // В проекте может быть свой notify/alert — здесь безопасный fallback.
        if(typeof pushNotify === 'function'){
            pushNotify(text);
        }else if(typeof error === 'function'){
            error(text);
        }else{
            alert(text);
        }
    }

    window.openNewYearEvent = function(){
        nyAjax('newyear_event', {}, function(r){
            if(!r) return;
            if(r.html){
                nyOpenModal(r.html);
            }else if(r.text){
                nyNotify(r.text);
            }
        });
    };

    window.newyearExchange = function(pack){
        nyAjax('newyear_exchange', {pack:pack}, function(r){
            if(!r) return;
            if(r.text) nyNotify(r.text);
            window.openNewYearEvent();
        });
    };

    window.newyearTierClaim = function(tier){
        nyAjax('newyear_tier_claim', {tier:tier}, function(r){
            if(!r) return;
            if(r.text) nyNotify(r.text);
            window.openNewYearEvent();
        });
    };

    window.newyearDailyClaim = function(){
        nyAjax('newyear_daily_claim', {}, function(r){
            if(!r) return;
            if(r.text) nyNotify(r.text);
            window.openNewYearEvent();
        });
    };

    function nyEnsureButton(){
        nyAjax('newyear_ping', {}, function(r){
            if(!r || !r.active) return;

            if($('#NewYearEventButton').length === 0){
                $('body').append(
                    '<div id="NewYearEventButton" style="position:fixed;right:12px;bottom:12px;z-index:99999;background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.18);color:#fff;border-radius:14px;padding:10px 12px;font-weight:700;cursor:pointer;">NY</div>'
                );
                $('#NewYearEventButton').on('click', function(){
                    window.openNewYearEvent();
                });
            }
        });
    }

    $(document).ready(function(){
        setTimeout(nyEnsureButton, 1800);
        setInterval(nyEnsureButton, 60000);
    });
})();


/* ===========================
   ChatProfanity — помощник модерации (детект мата) + словарь с сервера
   - не меняет дизайн
   - не добавляет команды всем игрокам
   - выдаёт pred/mut только если у текущего пользователя есть явные права в commandList
   =========================== */
(function(){
    var _cfg = {
        endpoint: '/do/chat',
        dictStorageKey: 'chat_profanity_dict_v2',
        cfgStorageKey: 'chat_profanity_cfg',
        dictTtlSec: 3600,
        windowSec: 180,
        punishCooldownSec: 25,
        includePM: false,
        warnCmd: 'pred',
        muteCmd: 'mut'
    };

    var _user = null;
    var _dict = { version: 1, updated_at: 0, roots: [], regex: [], whitelist: [] };
    var _state = { lastPeerPm: 0, punish: {}, inited: false };

    function nowSec(){ return Math.floor(Date.now()/1000); }

    function _loadCfg(){
        try{
            var raw = localStorage.getItem(_cfg.cfgStorageKey);
            if(raw){ var o = JSON.parse(raw); if(o && typeof o==='object'){ for(var k in o){ _cfg[k]=o[k]; } } }
        }catch(e){}
    }
    function _saveCfg(){
        try{ localStorage.setItem(_cfg.cfgStorageKey, JSON.stringify(_cfg)); }catch(e){}
    }

    function _loadDict(){
        try{
            var raw = localStorage.getItem(_cfg.dictStorageKey);
            if(raw){
                var o = JSON.parse(raw);
                if(o && typeof o==='object' && o.dict){
                    _dict = o.dict;
                    _dict._ts = o.ts || 0;
                }
            }
        }catch(e){}
    }
    function _saveDict(){
        try{ localStorage.setItem(_cfg.dictStorageKey, JSON.stringify({ts: nowSec(), dict: _dict})); }catch(e){}
    }

    function _normText(s){
        s = (s == null) ? '' : String(s);
        s = s.toLowerCase().replace(/ё/g,'е');
        // убираем частую маскировку символами
        s = s.replace(/[\u200B-\u200D\uFEFF]/g,''); // zero-width
        s = s.replace(/[^\wа-яa-z0-9]+/gi,' ');
        return s;
    }

    function _hasPerm(cmd){
        if(!_user || !window.commandList) return false;
        var val = window.commandList[cmd];
        if(!val) return false;

        // ВАЖНО: если у команды нет ограничений group/id, считаем что прав нет,
        // чтобы не "раздать" модераторку всем при ошибке конфигурации.
        var hasAnyRule = !!(val['group'] || val['id']);
        if(!hasAnyRule) return false;

        var uid = parseInt(_user['id']||0), grp = parseInt(_user['group']||0);
        if(val['group'] && Array.isArray(val['group']) && val['group'].indexOf(grp) !== -1) return true;
        if(val['id'] && Array.isArray(val['id']) && val['id'].indexOf(uid) !== -1) return true;
        return false;
    }

    function canModerate(){
        return _hasPerm(_cfg.warnCmd) || _hasPerm(_cfg.muteCmd);
    }

    function _extractInputsForCmd(cmd){
        // пытаемся разобрать window.commandList[cmd].text, чтобы узнать реальные имена инпутов
        try{
            var val = window.commandList && window.commandList[cmd];
            if(!val || !val['text']) return [];
            var $tmp = $('<div/>').html(val['text']);
            var names = [];
            $tmp.find('input,select,textarea').each(function(){
                var n = $(this).attr('name');
                if(n && names.indexOf(n)===-1) names.push(n);
            });
            return names;
        }catch(e){ return []; }
    }

    var _cmdInputsCache = {};
    function _getCmdInputs(cmd){
        if(_cmdInputsCache[cmd]) return _cmdInputsCache[cmd];
        _cmdInputsCache[cmd] = _extractInputsForCmd(cmd);
        return _cmdInputsCache[cmd];
    }

    function _buildConsolePayload(cmd, offenderId, offenderLogin, minutes, reason){
        var names = _getCmdInputs(cmd);
        var data = { type:'console', console: cmd };

        // максимально совместимый маппинг по именам полей, если они есть
        var mapUser = ['id','user','user_id','uid','trainer','player','target','login','name','nick'];
        var mapReason = ['text','reason','msg','comment','why','cause','message'];
        var mapTime = ['time','min','mins','minutes','t','m','long','duration'];

        for(var i=0;i<names.length;i++){
            var n = names[i];
            if(mapUser.indexOf(n)!==-1){
                if(n==='login' || n==='name' || n==='nick') data[n] = offenderLogin || String(offenderId);
                else data[n] = offenderId;
            } else if(mapReason.indexOf(n)!==-1){
                data[n] = reason;
            } else if(mapTime.indexOf(n)!==-1){
                data[n] = minutes;
            }
        }

        // дополнительные поля на всякий случай (если сервер читает их напрямую)
        data['id'] = offenderId;
        data['user_id'] = offenderId;
        if(offenderLogin) data['login'] = offenderLogin;
        if(reason) data['text'] = reason;
        if(minutes) data['time'] = minutes;

        return data;
    }

    function test(text){
        var s = _normText(text);

        // whitelist
        if(_dict.whitelist && _dict.whitelist.length){
            for(var i=0;i<_dict.whitelist.length;i++){
                var w = _normText(_dict.whitelist[i]);
                if(w && s.indexOf(w)!==-1) return false;
            }
        }

        // roots (быстро)
        if(_dict.roots && _dict.roots.length){
            for(var j=0;j<_dict.roots.length;j++){
                var r = _normText(_dict.roots[j]).trim();
                if(!r) continue;
                if(s.indexOf(r)!==-1) return true;
            }
        }

        // regex (точно, но дороже)
        if(_dict.regex && _dict.regex.length){
            for(var k=0;k<_dict.regex.length;k++){
                try{
                    var rx = new RegExp(_dict.regex[k], 'i');
                    if(rx.test(text)) return true;
                }catch(e){}
            }
        }

        return false;
    }

    function _shouldPunish(offenderId){
        var t = nowSec();
        var st = _state.punish[offenderId];
        if(!st){ st = _state.punish[offenderId] = {cnt:0, ts0:t, last:0}; }
        // окно подсчёта
        if(t - st.ts0 > _cfg.windowSec){
            st.cnt = 0;
            st.ts0 = t;
        }
        // антиспам по действиям
        if(t - st.last < _cfg.punishCooldownSec){
            return null;
        }
        st.cnt++;
        st.last = t;

        // эскалация
        if(st.cnt >= 7) return {type:'mute', min:60};
        if(st.cnt >= 5) return {type:'mute', min:15};
        if(st.cnt >= 3) return {type:'mute', min:5};
        return {type:'warn'};
    }

    function onIncoming(msg, chatInstance){
        if(!_state.inited) return;
        if(!msg) return;

        // игнорим историю / системные сообщения
        var text = msg['msg_text'] || msg['text'] || msg['message'] || '';
        if(!text) return;

        // PM по умолчанию не модерируем (чтобы не лезть в ЛС)
        var isPrivate = (parseInt(msg['msg_type']||0) === 1);
        if(isPrivate && !_cfg.includePM) return;

        // пользователь-отправитель
        var uid = parseInt(msg['user_id'] || msg['user'] || 0);
        if(!uid) return;

        // себя не наказываем
        if(_user && parseInt(_user['id']||0) === uid) return;

        // нет прав — ничего не делаем (важно: не отправлять команды)
        if(!canModerate()) return;

        // вырезаем цитаты, чтобы не банить за цитирование
        var noQuote = String(text).replace(/\[quote[\s\S]*?\[\/quote\]/ig, ' ');
        if(!test(noQuote)) return;

        var act = _shouldPunish(uid);
        if(!act) return;

        var reason = 'Нецензурная лексика';

        // отправка через _action(type=console), чтобы сработали серверные проверки прав
        if(chatInstance && typeof chatInstance._action === 'function'){
            if(act.type === 'warn' && _hasPerm(_cfg.warnCmd)){
                chatInstance._action(_buildConsolePayload(_cfg.warnCmd, uid, msg['user_login']||msg['login']||'', 0, reason), null, null, function(){});
            } else if(act.type === 'mute' && _hasPerm(_cfg.muteCmd)){
                chatInstance._action(_buildConsolePayload(_cfg.muteCmd, uid, msg['user_login']||msg['login']||'', act.min, reason), null, null, function(){});
            }
        }
    }

    
    function refreshDict(){
        if(!_cfg.endpoint) return;

        // миграция: если в кеше словарь "пустой", форсим обновление
        try{
            if(_dict && (!_dict.roots || !_dict.roots.length) && (!_dict.regex || !_dict.regex.length)){
                // сбрасываем ver/ua, чтобы сервер вернул полноценный словарь
                _dict.version = 0;
                _dict.updated_at = 0;
                _saveDict();
            }
        }catch(e){}

        var ver = _dict && _dict.version ? parseInt(_dict.version) : 0;
        var ua  = _dict && _dict.updated_at ? parseInt(_dict.updated_at) : 0;

        function _doPost(url){
            return $.post(url, { chat:'profanity_dict', ver: ver, ua: ua });
        }

        // основной вызов
        var req = _doPost(_cfg.endpoint);

        req.done(function(resp){
            try{ if(typeof resp === 'string') resp = $.parseJSON(resp); }catch(e){}
            if(!resp || resp.error) return;
            if(resp.changed && resp.dict){
                _dict = resp.dict;
                _saveDict();
            }else if(resp.version && resp.updated_at){
                // обновим метаданные, чтобы корректно сравнивать дальше
                _dict.version = parseInt(resp.version) || _dict.version;
                _dict.updated_at = parseInt(resp.updated_at) || _dict.updated_at;
                _saveDict();
            }
        });

        // fallback: если endpoint без .php не отвечает
        req.fail(function(){
            if(_cfg.endpoint && _cfg.endpoint.indexOf('.php') === -1){
                _doPost(_cfg.endpoint + '.php').done(function(resp){
                    try{ if(typeof resp === 'string') resp = $.parseJSON(resp); }catch(e){}
                    if(!resp || resp.error) return;
                    if(resp.changed && resp.dict){
                        _dict = resp.dict;
                        _saveDict();
                    }else if(resp.version && resp.updated_at){
                        _dict.version = parseInt(resp.version) || _dict.version;
                        _dict.updated_at = parseInt(resp.updated_at) || _dict.updated_at;
                        _saveDict();
                    }
                });
            }
        });
    }


    function init(userInfo, opts){
        _loadCfg();
        _loadDict();

        _user = userInfo || _user || null;
        if(opts && typeof opts==='object'){
            for(var k in opts){ _cfg[k]=opts[k]; }
        }

        _state.inited = true;

        // словарь обновляем лениво, чтобы не тормозить старт
        try{
            var ts = _dict._ts || 0;
            if(!ts || (nowSec() - ts) > _cfg.dictTtlSec){
                setTimeout(refreshDict, 600);
            }
        }catch(e){}
    }

    // public API
    window.ChatProfanity = {
        init: init,
        refreshDict: refreshDict,
        test: test,
        canModerate: canModerate,
        getDict: function(){ return _dict; },
        setCfg: function(o){ if(o && typeof o==='object'){ for(var k in o){ _cfg[k]=o[k]; } _saveCfg(); } },
        getCfg: function(){ return _cfg; },
        onIncoming: onIncoming
    };
})();
/* =====================================================================
   GameChat v4.1: Inbox ЛС + поиск в ЛС + фильтр мата
   Требует endpoints в /do/chat.php: pm_inbox, user_lookup, profanity_dict.
   ===================================================================== */
(function(){
  if (window.__gc_v4_1_init) return;
  window.__gc_v4_1_init = true;

  function isMobile(){
    try{ return !!(document.querySelector('.ChatBox') && document.querySelector('.ChatBox').classList.contains('mobile')); }catch(_){ return false; }
  }

  function ajaxChat(data, ok, fail){
    try{
      $.ajax({ url:'/do/chat', type:'POST', dataType:'json', data:data })
        .done(function(resp){ if(ok) ok(resp); })
        .fail(function(){ if(fail) fail(); });
    }catch(_){ if(fail) fail(); }
  }

  function lsGet(k, d){ try{ var v=localStorage.getItem(k); return (v===null||v===undefined)?d:v; }catch(_){ return d; } }
  function lsSet(k, v){ try{ localStorage.setItem(k, v); }catch(_){ } }
  function lsGetJSON(k, d){ try{ var v=localStorage.getItem(k); return v?JSON.parse(v):d; }catch(_){ return d; } }
  function lsSetJSON(k, v){ try{ localStorage.setItem(k, JSON.stringify(v)); }catch(_){ } }

  // ---------- profanity ----------
  var Prof = { ready:false, rx:null, allowRx:null };
  function escRx(s){ return String(s).replace(/[.*+?^${}()|[\]\\]/g,'\\$&'); }
  function buildProf(dict){
    try{
      var bad = (dict && dict.bad_words) ? dict.bad_words : [];
      var allow = (dict && dict.allow) ? dict.allow : [];
      if(!bad.length){ Prof.rx=null; Prof.allowRx=null; Prof.ready=true; return; }
      var badRe = bad.map(escRx).join('|');
      Prof.rx = new RegExp('(?:^|[^\\p{L}\\p{N}_])(' + badRe + ')(?=$|[^\\p{L}\\p{N}_])','iu');
      if(allow.length){
        var aRe = allow.map(escRx).join('|');
        Prof.allowRx = new RegExp(aRe,'iu');
      } else Prof.allowRx = null;
      Prof.ready=true;
    }catch(_){ Prof.rx=null; Prof.allowRx=null; Prof.ready=true; }
  }
  function profTest(text){
    try{
      if(!Prof.ready) return false;
      if(!Prof.rx) return false;
      if(Prof.allowRx && Prof.allowRx.test(text)) return false;
      return Prof.rx.test(text);
    }catch(_){ return false; }
  }
  function profEnabled(){ return lsGet('gc_profanity_hide','1')==='1'; }
  function profSetEnabled(v){ lsSet('gc_profanity_hide', v?'1':'0'); applyProfanityToAll(); sync18(); }

  function maskMsg($m){
    var $p=$m.find('.Post'); if(!$p.length) return;
    if(!$p.data('gc-orig-html')) $p.data('gc-orig-html', $p.html());
    $m.addClass('gc-msg-masked');
    $p.html('<span class="gc-msg-hidden" title="Нажмите, чтобы показать">Сообщение скрыто (18+)</span>');
  }
  function unmaskMsg($m){
    var $p=$m.find('.Post'); if(!$p.length) return;
    var orig=$p.data('gc-orig-html');
    if(orig){ $p.html(orig); $p.removeData('gc-orig-html'); }
    $m.removeClass('gc-msg-masked');
  }
  function applyProfanityToMessage($m){
    if(!$m || !$m.length) return;
    if(!profEnabled()){ unmaskMsg($m); return; }
    var $p=$m.find('.Post'); if(!$p.length) return;
    var t=$p.text(); if(!t) return;
    if(profTest(t)) maskMsg($m); else unmaskMsg($m);
  }
  function applyProfanityToAll(){
    $('.DivChat .Chat .Talk .Message-Block .Message').each(function(){ applyProfanityToMessage($(this)); });
  }
  function observeNewMessages(){
    var root=document.querySelector('.DivChat .Chat .Talk');
    if(!root || root.__gcProfObs) return;
    root.__gcProfObs=true;
    try{
      var mo=new MutationObserver(function(muts){
        muts.forEach(function(m){
          if(!m.addedNodes) return;
          $(m.addedNodes).each(function(){
            var $n=$(this);
            if($n.hasClass('Message')) applyProfanityToMessage($n);
            else $n.find('.Message').each(function(){ applyProfanityToMessage($(this)); });
          });
        });
      });
      mo.observe(root,{childList:true,subtree:true});
    }catch(_){ }
  }

  function ensure18(){
    var $wrap=$('.DivChat > .DivRightButtons > .Wrap');
    if($wrap.length && !$wrap.find('.gc-btn-18').length){
      $wrap.prepend('<div class="Button gc-btn-18" title="18+ (показать/скрыть мат)"><span style="font-weight:900">18+</span></div>');
    }
    sync18();
  }
  function sync18(){ $('.gc-btn-18').toggleClass('on', !profEnabled()); }

  // ---------- inbox ----------
  var st={ inited:false, me:0, items:[], pins:lsGetJSON('gc_pm_pins',{}), lastRead:lsGetJSON('gc_pm_last_read',{}), activeChannel:null, q:'', onlyUnread:false, timer:0, syncTimer:0, $div:null, $inbox:null, $overlay:null };
  function savePins(){ lsSetJSON('gc_pm_pins', st.pins||{}); }
  function saveRead(){ lsSetJSON('gc_pm_last_read', st.lastRead||{}); }
  function isPinned(ch){ return !!(st.pins && st.pins[String(ch)]); }
  function togglePin(ch){ ch=String(ch); if(!st.pins) st.pins={}; if(st.pins[ch]) delete st.pins[ch]; else st.pins[ch]=1; savePins(); renderInbox(); }
  function markRead(ch){
    var $b=$('.Message-Block.Channel_'+ch); if(!$b.length) return;
    var last=0;
    $b.find('.Message').each(function(){
      var m=(this.className||'').match(/__msg_id_(\d+)/);
      if(m){ var id=parseInt(m[1],10)||0; if(id>last) last=id; }
    });
    if(last>0){ st.lastRead[String(ch)]=last; saveRead(); }
  }
  function isUnread(it){
    var ch=String(it.channel||'');
    var last=parseInt(it.last_id||0,10)||0;
    var read=parseInt((st.lastRead && st.lastRead[ch])||0,10)||0;
    return last>read;
  }

  function ensureLayout(){
    var $div=$('.DivChat').first();
    if(!$div.length) return false;
    st.$div=$div;
    if(!$div.hasClass('gc-v4')) $div.addClass('gc-v4');

    if($div.children('.GcInbox').length===0){ $div.prepend('<div class="GcInbox" aria-label="Личные сообщения"></div>'); }
    st.$inbox=$div.children('.GcInbox').first();

    if($('.gc-inbox-overlay').length===0){
      $('body').append('<div class="gc-inbox-overlay"><div class="gc-inbox-overlay__backdrop" style="position:absolute;left:0;right:0;top:0;bottom:0"></div><div class="gc-inbox-overlay__panel" style="position:relative;height:100%"></div></div>');
    }
    st.$overlay=$('.gc-inbox-overlay').first();

    // FAB on mobile
    if($('.gc-fab-inbox').length===0){ $('body').append('<button type="button" class="gc-fab-inbox" aria-label="ЛС"><i class="fas fa-comments"></i></button>'); }

    return true;
  }

  function buildInboxSkeleton($root){
    if(!$root || !$root.length) return;
    if($root.data('gc-built')) return;
    $root.data('gc-built',1);
    var html=''+
      '<div class="gc-inbox__head">'+
        '<div class="gc-inbox__title">ЛС</div>'+
        '<div class="gc-inbox__head-actions">'+
          '<button class="gc-inbox__btn gc-new" type="button" title="Новый диалог"><i class="fas fa-plus"></i></button>'+
          '<button class="gc-inbox__btn gc-refresh" type="button" title="Обновить"><i class="fas fa-sync"></i></button>'+
        '</div>'+
      '</div>'+
      '<div class="gc-inbox__search">'+
        '<input class="gc-inbox__q" type="text" placeholder="Поиск диалогов" autocomplete="off" />'+
        '<button class="gc-inbox__btn gc-clear" type="button" title="Очистить"><i class="fas fa-times"></i></button>'+
      '</div>'+
      '<div class="gc-inbox__filters">'+
        '<button class="gc-inbox__chip gc-all active" type="button">Все</button>'+
        '<button class="gc-inbox__chip gc-unread" type="button">Непрочитанные</button>'+
      '</div>'+
      '<div class="gc-inbox__list" role="list"></div>'+
      '<div class="gc-inbox__foot">'+
        '<div class="gc-inbox__hint">Подсказка: можно начать ЛС через <b>Логин = текст</b> в поле ввода.</div>'+
      '</div>';
    $root.html(html);
  }

  function openInboxOverlay(){
    if(!st.$overlay || !st.$overlay.length || !st.$inbox || !st.$inbox.length) return;
    if(st.$overlay.hasClass('open')) return;
    var $panel=st.$overlay.find('.gc-inbox-overlay__panel');
    $panel.empty().append(st.$inbox.addClass('gc-inbox--overlay'));
    st.$overlay.addClass('open');
    try{ st.$inbox.find('.gc-inbox__q').focus(); }catch(_){ }
  }
  function closeInboxOverlay(){
    if(!st.$overlay || !st.$overlay.length || !st.$inbox || !st.$inbox.length) return;
    st.$overlay.removeClass('open');
    st.$inbox.removeClass('gc-inbox--overlay');
    if(st.$div && st.$div.length) st.$div.prepend(st.$inbox);
    else $('.DivChat').first().prepend(st.$inbox);
  }

  // recipient helpers
  function parsePairFromChannel(channel){
    var m=String(channel||'').match(/^id(\d+)_(\d+)$/);
    if(!m) return null;
    return {a:parseInt(m[1],10), b:parseInt(m[2],10)};
  }
  function otherIdFromChannel(channel){
    var p=parsePairFromChannel(channel); if(!p) return 0;
    if(p.a===st.me) return p.b;
    if(p.b===st.me) return p.a;
    return 0;
  }
  function ensureSelectOption($sel, id, login){
    if(!$sel || !$sel.length) return;
    var sid=String(id);
    if($sel.find('option[value="'+sid.replace(/"/g,'')+'"]').length===0){
      $sel.append('<option value="'+sid+'">'+String(login||('id'+sid)).replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</option>');
    }
  }
  function setRecipient(channel, login){
    var other=otherIdFromChannel(channel);
    if(!other) return;
    var $m=$('#chat_user_to');
    var $d=$('#chat_user_to_desktop');
    ensureSelectOption($m, other, login);
    ensureSelectOption($d, other, login);
    try{ $m.val(String(other)); }catch(_){ }
    try{ $d.val(String(other)); }catch(_){ }
    try{ $('#chat_send').attr('placeholder','Сообщение в ЛС: '+login); }catch(_){ }
    try{ $('#chat_send_desktop').attr('placeholder','Сообщение в ЛС: '+login); }catch(_){ }
  }
  function clearRecipient(){
    try{ $('#chat_user_to').val('0'); }catch(_){ }
    try{ $('#chat_user_to_desktop').val('0'); }catch(_){ }
    try{ $('#chat_send').attr('placeholder','Сообщение...'); }catch(_){ }
    try{ $('#chat_send_desktop').attr('placeholder','Сообщение...'); }catch(_){ }
  }

  function ensurePmTab(channel, login){
    var $cat=$('.DivChat .Chat .Category');
    if(!$cat.length) return;
    var sel='.chat_move_channel_'+String(channel);
    if($cat.find(sel).length) return;
    var safe=String(login||channel).replace(/</g,'&lt;').replace(/>/g,'&gt;');
    $cat.append('<div class="Button chat_move_channel_'+channel+'" data-channel="'+channel+'"><i class="fas fa-user"></i> '+safe+' <span class="closePmTab" title="Закрыть" style="margin-left:7px; cursor:pointer;">&times;</span></div>');
    if($('.Message-Block.Channel_'+channel).length===0){
      $('.Chat .Talk').append('<div class="Message-Block Channel_'+channel+'" data-channel="'+channel+'"></div>');
    }
  }

  function openDialog(it){
    if(!it) return;
    var ch=String(it.channel||'');
    var login=String(it.login||'');
    if(!ch) return;
    ensurePmTab(ch, login);
    var $btn=$('.DivChat .Chat .Category .chat_move_channel_'+ch);
    if($btn.length){ $btn.trigger('click'); }
    setRecipient(ch, login);
    st.activeChannel=ch;
    markRead(ch);
    renderInbox();
    ensurePmSearchBar();
    if(isMobile()) closeInboxOverlay();
  }

  function refreshInbox(){
    ajaxChat({chat:'pm_inbox', limit:120}, function(resp){
      if(resp && resp.error===0 && resp.items){ st.items=resp.items||[]; renderInbox(); }
    });
  }

  function renderInbox(){
    if(!st.$inbox || !st.$inbox.length) return;
    var $list=st.$inbox.find('.gc-inbox__list');
    if(!$list.length) return;
    var q=(st.q||'').toLowerCase();
    var items=(st.items||[]).slice(0);
    items.sort(function(a,b){
      var ap=isPinned(a.channel)?1:0; var bp=isPinned(b.channel)?1:0;
      if(ap!==bp) return bp-ap;
      var at=parseInt(a.last_ts||0,10)||0; var bt=parseInt(b.last_ts||0,10)||0;
      return bt-at;
    });
    var out=[]; var shown=0;
    for(var i=0;i<items.length;i++){
      var it=items[i];
      var login=String(it.login||'');
      var ch=String(it.channel||'');
      var text=String(it.last_text||'');
      var time=String(it.last_time||'');
      if(q){ var hay=(login+' '+text).toLowerCase(); if(hay.indexOf(q)===-1) continue; }
      var unread=isUnread(it); if(st.onlyUnread && !unread) continue;
      var active=(st.activeChannel && String(st.activeChannel)===ch);
      var pin=isPinned(ch);
      out.push(''+
        '<div class="gc-inbox__item '+(active?'active ':'')+(unread?'unread ':'')+'" role="listitem" data-channel="'+ch+'" data-id="'+it.other_id+'" data-login="'+login.replace(/</g,'&lt;').replace(/>/g,'&gt;')+'">'+
          '<div class="gc-inbox__avatar">'+(login.substring(0,1).toUpperCase()||'?')+'</div>'+
          '<div class="gc-inbox__main">'+
            '<div class="gc-inbox__row">'+
              '<div class="gc-inbox__login">'+login.replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>'+
              '<div class="gc-inbox__time">'+time+'</div>'+
            '</div>'+
            '<div class="gc-inbox__row">'+
              '<div class="gc-inbox__preview">'+text.replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>'+
              '<div class="gc-inbox__meta">'+
                (unread?'<span class="gc-inbox__badge"></span>':'')+
                '<span class="gc-inbox__pin '+(pin?'on':'')+'" title="Закрепить"><i class="fas fa-thumbtack"></i></span>'+
              '</div>'+
            '</div>'+
          '</div>'+
        '</div>'
      );
      shown++; if(shown>=120) break;
    }
    if(!out.length) $list.html('<div class="gc-inbox__empty">Диалогов не найдено</div>');
    else $list.html(out.join(''));
  }

  // ---------- PM search ----------
  function ensurePmSearchBar(){
    var ch=st.activeChannel;
    if(!ch || String(ch).indexOf('id')!==0){ $('.gc-pm-searchbar').remove(); return; }
    var $block=$('.Message-Block.Channel_'+ch);
    if(!$block.length) return;
    if($block.find('> .gc-pm-searchbar').length) return;

    $block.prepend(''+
      '<div class="gc-pm-searchbar">'+
        '<div class="gc-pm-searchbar__left">'+
          '<i class="fas fa-search"></i>'+
          '<input class="gc-pm-q" type="text" placeholder="Поиск в ЛС" autocomplete="off" />'+
        '</div>'+
        '<div class="gc-pm-searchbar__right">'+
          '<button class="gc-pm-btn gc-prev" type="button" title="Предыдущее"><i class="fas fa-chevron-up"></i></button>'+
          '<button class="gc-pm-btn gc-next" type="button" title="Следующее"><i class="fas fa-chevron-down"></i></button>'+
          '<button class="gc-pm-btn gc-close" type="button" title="Закрыть"><i class="fas fa-times"></i></button>'+
        '</div>'+
      '</div>'
    );

    var hits=[]; var cur=-1;
    function clearMarks(){
      hits.forEach(function($m){
        try{ $m.removeClass('gc-search-hit gc-search-cur'); var $p=$m.find('.Post'); if($p.data('gc-orig')){ $p.html($p.data('gc-orig')); $p.removeData('gc-orig'); } }catch(_){ }
      });
      hits=[]; cur=-1;
    }
    function focusHit(i){
      if(!hits.length) return;
      if(i<0) i=hits.length-1;
      if(i>=hits.length) i=0;
      hits.forEach(function($m){ $m.removeClass('gc-search-cur'); });
      var $m=hits[i]; $m.addClass('gc-search-cur');
      try{ $m[0].scrollIntoView({behavior:'smooth', block:'center'}); }catch(_){ }
      cur=i;
    }
    function apply(term){
      clearMarks();
      term=(term||'').trim();
      if(!term) return;
      var rx=null;
      try{ rx=new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'ig'); }catch(_){ rx=null; }
      $block.find('.Message').each(function(){
        var $m=$(this); var $p=$m.find('.Post'); if(!$p.length) return;
        var txt=$p.text(); if(!txt) return;
        if(txt.toLowerCase().indexOf(term.toLowerCase())===-1) return;
        if(!$p.data('gc-orig')) $p.data('gc-orig', $p.html());
        if(rx){ $p.html($p.html().replace(rx,function(m){ return '<mark class="gc-mark">'+m+'</mark>'; })); }
        $m.addClass('gc-search-hit'); hits.push($m);
      });
      if(hits.length){ cur=0; focusHit(0); }
    }

    $block.on('input', '.gc-pm-q', function(){ apply($(this).val()); });
    $block.on('click', '.gc-prev', function(){ focusHit(cur-1); });
    $block.on('click', '.gc-next', function(){ focusHit(cur+1); });
    $block.on('click', '.gc-close', function(){ clearMarks(); $block.find('> .gc-pm-searchbar').remove(); });
  }

  // ---------- sync active channel ----------
  function syncActiveChannel(){
    var $a=$('.DivChat .Chat .Category .Button.Active');
    if(!$a.length) return;
    var ch=$a.attr('data-channel');
    if(!ch) return;
    if(st.activeChannel===ch) return;
    st.activeChannel=ch;
    if(String(ch).indexOf('id')===0){
      var login='';
      (st.items||[]).some(function(it){ if(String(it.channel)===String(ch)){ login=String(it.login||''); return true; } return false; });
      if(!login){ login=$a.clone().children().remove().end().text().replace(/\s+/g,' ').trim(); }
      setRecipient(ch, login);
      markRead(ch);
      ensurePmSearchBar();
    } else {
      clearRecipient();
      $('.gc-pm-searchbar').remove();
    }
    renderInbox();
  }

  // ---------- user lookup modal (new dialog) ----------
  function openNewDialog(){
    var $m=$('.gc-newdlg');
    if($m.length){ $m.show(); $m.find('input').focus(); return; }
    var modal=''+
      '<div class="gc-newdlg" style="display:block;position:fixed;left:0;right:0;top:0;bottom:0;z-index:3010">'+
        '<div class="gc-newdlg__back" style="position:absolute;left:0;right:0;top:0;bottom:0;background:rgba(15,23,42,.45)"></div>'+
        '<div class="gc-newdlg__panel" style="position:relative;max-width:420px;margin:10vh auto;background:#fff;border-radius:14px;border:1px solid #ececec;box-shadow:0 18px 60px rgba(15,23,42,.25);overflow:hidden">'+
          '<div class="gc-newdlg__head" style="padding:12px 14px;font:900 13px/1 Nunito,Inter,Arial;color:#0f172a;border-bottom:1px solid #ececec;background:linear-gradient(180deg,#ffffff,#f6f8ff)">Новый диалог</div>'+
          '<div class="gc-newdlg__body" style="padding:12px 14px">'+
            '<input class="gc-newdlg__q" type="text" placeholder="Начните вводить логин (мин. 2 символа)" autocomplete="off" style="width:100%;box-sizing:border-box;border:1px solid #e6eafe;border-radius:10px;padding:10px 12px;font:800 12px/1.2 Nunito,Inter,Arial;outline:none" />'+
            '<div class="gc-newdlg__list" style="margin-top:10px;max-height:260px;overflow:auto"></div>'+
          '</div>'+
          '<div class="gc-newdlg__foot" style="padding:10px 14px;border-top:1px solid #ececec;display:flex;justify-content:flex-end">'+
            '<button class="gc-newdlg__close" type="button" style="cursor:pointer;border:1px solid #dbe6ff;background:#eef3ff;color:#24408f;border-radius:10px;padding:8px 10px;font:900 12px/1 Nunito,Inter,Arial">Закрыть</button>'+
          '</div>'+
        '</div>'+
      '</div>';
    $('body').append(modal);
    var $dlg=$('.gc-newdlg').last();
    var $q=$dlg.find('.gc-newdlg__q');
    var $list=$dlg.find('.gc-newdlg__list');

    function render(items){
      if(!items || !items.length){ $list.html('<div style="padding:10px;color:#64748b;font:800 12px/1.2 Nunito,Inter,Arial">Ничего не найдено</div>'); return; }
      var out=[];
      items.forEach(function(u){
        out.push('<div class="gc-newdlg__item" data-id="'+u.id+'" data-login="'+String(u.login).replace(/</g,'&lt;').replace(/>/g,'&gt;')+'" style="padding:10px;border:1px solid #eef2ff;border-radius:12px;margin:6px 0;cursor:pointer">'+
          '<div style="font:900 13px/1.1 Nunito,Inter,Arial;color:#0f172a">'+String(u.login).replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>'+
          '<div style="margin-top:2px;font:800 11px/1 Nunito,Inter,Arial;color:#64748b">ID '+u.id+'</div>'+
        '</div>');
      });
      $list.html(out.join(''));
    }

    var t=0;
    $q.on('input', function(){
      var val=($.trim($q.val()||''));
      clearTimeout(t);
      if(val.length<2){ $list.empty(); return; }
      t=setTimeout(function(){
        ajaxChat({chat:'user_lookup', q:val, limit:20}, function(resp){
          if(resp && resp.error===0) render(resp.items||[]);
          else render([]);
        }, function(){ render([]); });
      }, 250);
    });

    $dlg.on('click', '.gc-newdlg__item', function(){
      var id=parseInt($(this).attr('data-id'),10)||0;
      var login=String($(this).attr('data-login')||'');
      if(id>0 && login){
        var ch = (st.me<id) ? ('id'+st.me+'_'+id) : ('id'+id+'_'+st.me);
        openDialog({other_id:id, login:login, channel:ch});
        $dlg.hide();
      }
    });

    $dlg.on('click', '.gc-newdlg__close, .gc-newdlg__back', function(){ $dlg.hide(); });
    setTimeout(function(){ $q.focus(); }, 0);
  }

  // ---------- wire ----------
  function wire(){
    if(st.inited) return;
    st.inited=true;

    // inbox controls
    $('.GcInbox').on('click', '.gc-refresh', function(){ refreshInbox(); });
    $('.GcInbox').on('click', '.gc-clear', function(){ st.q=''; $('.GcInbox .gc-inbox__q').val(''); renderInbox(); });
    $('.GcInbox').on('input', '.gc-inbox__q', function(){ st.q=($(this).val()||'').trim(); renderInbox(); });

    $('.GcInbox').on('click', '.gc-inbox__chip.gc-all', function(){ st.onlyUnread=false; $('.GcInbox .gc-inbox__chip').removeClass('active'); $(this).addClass('active'); renderInbox(); });
    $('.GcInbox').on('click', '.gc-inbox__chip.gc-unread', function(){ st.onlyUnread=true; $('.GcInbox .gc-inbox__chip').removeClass('active'); $(this).addClass('active'); renderInbox(); });

    $('.GcInbox').on('click', '.gc-inbox__item', function(e){
      if($(e.target).closest('.gc-inbox__pin').length) return;
      var $it=$(this);
      openDialog({channel:$it.attr('data-channel'), other_id:parseInt($it.attr('data-id'),10)||0, login:$it.attr('data-login')||''});
    });
    $('.GcInbox').on('click', '.gc-inbox__pin', function(e){ e.stopPropagation(); var ch=$(this).closest('.gc-inbox__item').attr('data-channel'); togglePin(ch); });
    $('.GcInbox').on('click', '.gc-new', function(){ openNewDialog(); });

    // overlay
    $('body').on('click', '.gc-inbox-overlay__backdrop', function(){ closeInboxOverlay(); });
    $('body').on('click', '.gc-fab-inbox', function(){ openInboxOverlay(); });

    // on channel clicks
    $(document).on('click', '.DivChat .Chat .Category .Button', function(){ setTimeout(syncActiveChannel,0); });

    // profanity
    ensure18();
    observeNewMessages();
    $(document).on('click', '.gc-btn-18', function(){ profSetEnabled(!profEnabled()); });
    $(document).on('click', '.gc-msg-hidden', function(){ var $m=$(this).closest('.Message'); unmaskMsg($m); });

    // close overlay by ESC
    $(document).on('keydown', function(e){ if(e.key==='Escape') closeInboxOverlay(); });

    // prevent closePmTab from killing inbox
    $(document).on('click', '.closePmTab', function(){ setTimeout(refreshInbox, 500); });
  }

  function bootstrap(){
    // wait user id
    var me=0;
    try{ me = parseInt((window.userInfo && window.userInfo.id) || (window.USER && window.USER.id) || 0, 10) || 0; }catch(_){ me=0; }
    if(!me) return false;
    st.me=me;

    if(!ensureLayout()) return false;
    buildInboxSkeleton(st.$inbox);
    wire();

    // load profanity dict once
    if(!Prof.ready){
      ajaxChat({chat:'profanity_dict'}, function(resp){ buildProf(resp); applyProfanityToAll(); }, function(){ buildProf(null); });
    }

    refreshInbox();

    if(st.timer) clearInterval(st.timer);
    st.timer=setInterval(refreshInbox, 15000);

    if(st.syncTimer) clearInterval(st.syncTimer);
    st.syncTimer=setInterval(syncActiveChannel, 700);

    return true;
  }

  // try bootstrap a few times
  var tries=0;
  var t=setInterval(function(){
    tries++;
    if(bootstrap() || tries>40) clearInterval(t);
  }, 500);
})();

// --- Глобальные переменные ---
var mainLoader = '<img height="80px" src="/img/loader/loader.gif" class="imgLoader">',
    closeGame = false,
    isTrade = false,
    ClassTrade = null,
    ClassBattle = null,
    assault = false,
    UserPokList = [],
    UserPokedex = null,
    md5locList = '',
    battleInfoRage = 0;

// --- Clan CSRF (minimal) ---
window.CLAN_CSRF = window.CLAN_CSRF || '';
function clanCsrfToken() {
    return window.CLAN_CSRF || '';
}
function clanUpdateCsrf(resp) {
    if (resp && resp.csrf_token) {
        window.CLAN_CSRF = resp.csrf_token;
    }
}
if (typeof jQuery !== 'undefined') {
    $(function(){
        $.post('/do/clanAction.php', {object:'csrf'}, function(r){ clanUpdateCsrf(r); }, 'json');
    });
    $(document).ajaxPrefilter(function(options, originalOptions){
        if (!options || !options.url) return;
        if (options.url.indexOf('/do/clanAction.php') === -1) return;
        if (typeof originalOptions.data === 'string') {
            if (originalOptions.data.indexOf('csrf_token=') === -1) {
                originalOptions.data += (originalOptions.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(clanCsrfToken());
            }
        } else if (typeof originalOptions.data === 'object' && originalOptions.data) {
            if (!originalOptions.data.csrf_token) {
                originalOptions.data.csrf_token = clanCsrfToken();
            }
        }
    });
}

/* =========================================================
   REALTIME TRANSPORT — sockets removed
   ---------------------------------------------------------
   По запросу: полностью убраны сокеты из world.js.
   GameSocket оставлен как совместимый "заглушечный" интерфейс,
   чтобы остальной код не падал, но реального подключения нет.
   Все обновления происходят через существующие AJAX-механизмы
   (updateLocation.php и другие ваши /do/* обработчики).
   ========================================================= */
const GameSocket = {
    socket: null,
    isActive: false,
    disabled: true,
    chatAnnouncement: window.ChatAnnouncement || "",

    setAnnouncement: function (text) {
        GameSocket.chatAnnouncement = text;
        window.ChatAnnouncement = text;
        if (window.GameChat && typeof window.GameChat.renderAnnouncement === 'function') {
            window.GameChat.renderAnnouncement();
        }
    },

    init: function () {
        // Сокеты отключены намеренно.
        GameSocket.isActive = false;
        GameSocket.socket = null;
    },

    emit: function () {
        // no-op (сокеты отключены)
    },

    updateAuthToken: function () {
        // no-op (сокеты отключены)
    },

    notify: function (text, type) {
        // оставляем для совместимости: уведомления в игре/чате
        if (window.Game && window.Game.notifications && typeof window.Game.notifications.main === 'function') {
            window.Game.notifications.main(text, type || 'info');
        } else {
            try { console.log('[' + (type || 'info') + ']', text); } catch (_) {}
        }
        if (window.GameChat && typeof window.GameChat.addSystemMessage === 'function') {
            window.GameChat.addSystemMessage(text, type);
        }
    },

    _safeNotify: function (text, type) {
        // совместимость — без троттлинга, т.к. реконнектов больше нет
        GameSocket.notify(text, type);
    }
};

// Экспортируем в window, как было принято в коде
window.GameSocket = GameSocket;

/* ---------------- АВТОЗАПУСК ---------------- */
$(document).ready(function () {
    GameSocket.init();
});

var Aqua = {
  struct: {
    preloader: $('.Modal div'),
    locationPreLoader: $('#locationPreloader')
    //model: $('<div />', {'class': 'model'})
  },
  notifications: {
    mainAlert: function() {
      Game.notifications.main('Неверный запрос!', 'error');
    }
  },
  modals: {
    creating: {
      model: function(name) {
        $('.model').remove();
        $('<div />', {
          'class':'model'
        }).appendTo('body');
        $('<div />', {
          'class':'header',
          'id': 'drgModel',
          html: name
        }).appendTo('.model');
        $('<span />', {
          html: '<i class="fas fa-times"></i>',
          click: function() {
            $('.model').remove();
          }
        }).appendTo('.model .header');
        $('<div />', {
          'class':'content-model',
          html: typeof mainLoader !== 'undefined' ? mainLoader : ''
        }).appendTo('.model');
        if(typeof $.fn.draggabilly !== 'undefined') {
          $('.model').draggabilly({
            handle: '#drgModel',
            containment: true,
            dragEnabled: window.innerWidth > 600 // Отключаем drag на мобильных
          });
        }
      }
    }
  },
  users: {
    edit: {
      open: function() {
        alert(123);
      },
      redact: function(type, val = false) {
        switch(type){
          case 'pass':
            var oldPass = $('#oldPass').val(),
                newPass = $('#newPass').val(),
                dblNewPass = $('#dblNewPass').val();
            if(oldPass.length != 0 || newPass.length != 0 || dblNewPass.length != 0) {
              val = [oldPass, newPass, dblNewPass];
            }else{
              val = 2;
            }
          break;
          case 'audio':
            val = (val ? [val] : [2]);
          break;
          case 'mission':
            val = (val ? [val] : [2]);
          break;
          case 'hotclick':
            val = (val ? [val] : [2]);
          break;
          case 'boss_battle':
            val = (val ? [val] : [2]);
          break;
          case 'team':
            val = (val ? [val] : [2]);
          break;
          case 'color':
            val = (val ? [val] : [100]);
          break;
          case 'inv':
            val = (val ? [val] : [100]);
          break;
        }
        if(type && val) {
          $.ajax({
            url: "/do/aqua",
            type: "POST",
            data: {
                id: 'edit',
                type: type,
                val: val
            },
            success: function (response){
              response = (typeof response === 'string') ? JSON.parse(response) : response;
              if(response.error == 1) {
                Game.notifications.main(response.text,"error");
              }else{
                settings();
                Game.notifications.main("Изменения прошли успешно.","success");
              }
            }
          });
        }else{
          Aqua.notifications.mainAlert();
        }
      }
    }
  },
  loaders: {
    main: function(a) {
      Aqua.struct.preloader.find('span').html(a);
      Aqua.struct.locationPreLoader.delay(500).fadeOut(200);
    }
  },
  autoLoad: function() {
    Aqua.loaders.main('Загрузка...');
  }
}


function isNumber(n) {
   return /^-?[\d.]+(?:e-?\d+)?$/.test(n);
}




function editStatus(t,e,s){
  if(!t){
    $("#statusUser").html(
      '<textarea type="text" ' +
      'onkeydown="if(event.keyCode == 13){editStatus(\'status\',this.value);}" ' +
      '>'+ (s ? String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : '') + '</textarea>'
    );
    $("#statusUser textarea").focus();
  }else{
    $.ajax({
      url:"/do/trainers",
      type:"POST",
      data:{type:t,value:e},
      success:function(t){
        t=JSON.parse(t);
        $("#statusUser").html(
          "<div class='Text'>" + 
          $('<div/>').text(t.text).html() + 
          "</div><div class='Pen' onclick=\"editStatus(false,false,'" + 
          t.text.replace(/'/g,"&#39;").replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
          "');\"><i class='fa fa-pen-square'></i></div>"
        );
      }
    })
  }
}

// =========================================================
// HOTKEYS (централизовано в world.js)
// ---------------------------------------------------------
// - Один обработчик на весь клиент
// - Поддержка RU/EN раскладки (и по символу, и по физической клавише)
// - Переключатель через глобальную переменную HotClick (1 = включено)
// - Каталог используется для отображения справки в Настройках
// =========================================================
(function(){
  if (window.GameHotkeys && window.GameHotkeys.__inited) return;

  // Визуальная/транслит-совместимость (исторически использовалось в проекте)
  var RU_TO_EN = {
    'Р':'P','И':'I','С':'C','Е':'T','Й':'Q','Н':'H','Ы':'S','М':'V','Ь':'M','В':'D','Б':'B','Х':'X','А':'F',
    'р':'p','и':'i','с':'c','е':'t','й':'q','н':'h','ы':'s','м':'v','ь':'m','в':'d','б':'b','х':'x','а':'f'
  };

  function _isEnabled(){
    return (typeof window.HotClick !== 'undefined' && window.HotClick == 1);
  }

  function _notify(text, type){
    try{
      if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
        Game.notifications.main(text, type || 'info');
      } else if (window.GameSocket && typeof GameSocket.notify === 'function') {
        GameSocket.notify(text, type || 'info');
      } else {
        console.log('['+(type||'info')+']', text);
      }
    }catch(e){}
  }

  function _isTextInput(el){
    try{
      if(!el) return false;
      var t = (el.tagName || '').toLowerCase();
      if(t === 'input' || t === 'textarea' || t === 'select') return true;
      if(el.isContentEditable) return true;
      // иногда target может быть внутри contenteditable
      var p = el.closest && el.closest('[contenteditable="true"]');
      return !!p;
    }catch(e){ return false; }
  }

  function _hasBattle(){
    try{ return $('.Battle').length > 0; }catch(e){ return false; }
  }

  function _focusChat(){
    try{
      if ($("#chat_send_desktop").length && $("#chat_send_desktop").is(":visible")) {
        $("#chat_send_desktop").focus();
        return true;
      }
      if ($("#chat_send").length && $("#chat_send").is(":visible")) {
        $("#chat_send").focus();
        return true;
      }
    }catch(e){}
    return false;
  }

  function _focusChatTo(){
    try{
      if ($("#chat_user_to_desktop").length && $("#chat_user_to_desktop").is(":visible")) {
        $("#chat_user_to_desktop").focus();
        return true;
      }
      if ($("#chat_user_to").length && $("#chat_user_to").is(":visible")) {
        $("#chat_user_to").focus();
        return true;
      }
    }catch(e){}
    return false;
  }

  function _getChatInput(){
    try{
      var $i = $('#chat_send_desktop:visible').first();
      if($i.length) return $i;
      $i = $('#chat_send:visible').first();
      if($i.length) return $i;
      // fallback
      $i = $('#chat_send_desktop').first();
      if($i.length) return $i;
      $i = $('#chat_send').first();
      return $i;
    }catch(e){ return $(); }
  }

  function _sendChat(){
    try{
      var $inp = _getChatInput();
      if(!$inp.length) return false;

      // если фокуса нет — просто фокусируем
      if(document.activeElement !== $inp[0]){
        $inp.focus();
        return true;
      }

      // отправляем только если есть текст
      var msg = ($inp.val() || '') + '';
      if(!msg.trim()){
        return true;
      }

      if (window.GameChat) {
        if ($inp.attr('id') === 'chat_send_desktop' && typeof window.GameChat._send_desktop === 'function') {
          window.GameChat._send_desktop();
          return true;
        }
        if ($inp.attr('id') === 'chat_send' && typeof window.GameChat._send === 'function') {
          window.GameChat._send();
          return true;
        }
      }

      // fallback: если есть кнопка отправки
      var $btn = $('.DivChat .SendButton:visible, .DivChat button[type="submit"]:visible').first();
      if($btn.length){ $btn.trigger('click'); return true; }
    }catch(e){}
    return false;
  }

  function _clearChatInput(){
    try{
      var $inp = _getChatInput();
      if(!$inp.length) return false;
      $inp.val('');
      $inp.trigger('input');
      return true;
    }catch(e){}
    return false;
  }

  function _toggleSmile(){
    try{
      var $w = $('.window.smileList');
      if($w.length && $w.is(':visible')){ $w.hide(); return true; }
      var $btn = $('.el_smile:visible').first();
      if($btn.length){ $btn.trigger('click'); return true; }
      // fallback: попробовать метод чата
      if(window.GameChat && typeof window.GameChat._viewSmile === 'function'){ window.GameChat._viewSmile(); return true; }
    }catch(e){}
    return false;
  }

  function _toggleAutoscroll(){
    try{
      var $chk = $('.__chat_scrolls:visible').first();
      if(!$chk.length) $chk = $('.__chat_scrolls').first();
      if(!$chk.length) return false;

      var on = !$chk.prop('checked');
      $chk.prop('checked', on).trigger('change');
      _notify(on ? 'Чат: автопрокрутка включена' : 'Чат: автопрокрутка выключена', 'info');
      return true;
    }catch(e){}
    return false;
  }

  function _switchChatChannelById(id){
    try{
      var $btn = $('.DivChat .Chat .Category .chat_move_channel_'+id+':visible').first();
      if(!$btn.length) $btn = $('.DivChat .Chat .Category [data-channel="'+id+'"]:visible').first();
      if($btn.length){ $btn.trigger('click'); return true; }
    }catch(e){}
    return false;
  }

  function _switchChatTabByIndex(idx1){
    try{
      var idx = (idx1|0) - 1;
      if(idx < 0) return false;
      var $tabs = $('.DivChat .Chat .Category .Button:visible');
      if(!$tabs.length) $tabs = $('.DivChat .Chat .Category .Button');
      if(idx >= $tabs.length) return false;
      $tabs.eq(idx).trigger('click');
      return true;
    }catch(e){}
    return false;
  }

  function _cycleChatTab(dir){
    try{
      var $tabs = $('.DivChat .Chat .Category .Button:visible');
      if(!$tabs.length) $tabs = $('.DivChat .Chat .Category .Button');
      if(!$tabs.length) return false;

      var $cur = $tabs.filter('.Active').first();
      var i = $cur.length ? $tabs.index($cur) : 0;
      var n = $tabs.length;
      var next = (i + (dir>0?1:-1) + n) % n;
      $tabs.eq(next).trigger('click');
      return true;
    }catch(e){}
    return false;
  }

  var _clearChatArmAt = 0;
  function _clearChatWithArm(){
    try{
      var now = Date.now();
      if(now - _clearChatArmAt > 1200){
        _clearChatArmAt = now;
        _notify('Чат: нажмите ещё раз для очистки', 'info');
        return true;
      }
      _clearChatArmAt = 0;
      var $btn = $('.el_clear_chat:visible').first();
      if($btn.length){ $btn.trigger('click'); _notify('Чат очищен', 'success'); return true; }
    }catch(e){}
    return false;
  }

  function _inventory(type){
    try{
      if(window.Game && Game.modals && typeof Game.modals.inventory === 'function'){
        Game.modals.inventory(type);
        return true;
      }
    }catch(e){}
    return false;
  }

  function _openModalSafe(name){
    try{
      if(typeof openModal === 'function'){ openModal(name); return true; }
    }catch(e){}
    return false;
  }

  // ---------------------------------------------------------
  // Единый каталог хоткеев (используется и для обработки, и для справки)
  // combo: alt|ctrl|shift|meta + key (буква/цифра/esc/enter/space/arrowleft/…)
  //
  // Важно:
  // - "always: true"  -> работает всегда (даже при HotClick=0 и в полях ввода)
  // - "inInput: true" -> работает в полях ввода, но зависит от HotClick
  // ---------------------------------------------------------
  var CATALOG = [
    // Навигация
    {
      id:'esc',
      group:'Навигация',
      combo:'esc',
      title:'Закрыть уведомления/окна',
      note:'Работает всегда (включая поля ввода).',
      always:true,
      prevent:false,
      run:function(){
        try{
          if ($('.DivNotification').html() !== '') { $('.DivNotification .noty').remove(); return; }
          if ($('.LittleModal').length && typeof closeLittleModal === 'function') { closeLittleModal(); return; }
          if (typeof closeModal === 'function') closeModal();
        }catch(e){}
      }
    },
    {
      id:'help',
      group:'Навигация',
      combo:'ctrl+/',
      title:'Справка по хоткеям',
      note:'Откроет Настройки и сфокусирует поиск.',
      run:function(){
        try{
          if (typeof settings === 'function') settings();
          setTimeout(function(){
            try{
              var $m = $('.LittleModal.settings');
              if(!$m.length) return;
              var d = $m.find('.hk-details');
              if(d.length) d.prop('open', true);
              var inp = $m.find('.hk-search');
              if(inp.length){ inp.focus(); try{ inp[0].select(); }catch(e){} }
              var step = $m.find('.hkStep')[0];
              if(step && step.scrollIntoView) step.scrollIntoView({block:'start', behavior:'smooth'});
            }catch(e){}
          }, 350);
        }catch(e){}
      }
    },

    // Чат — фокус/отправка/панели
    { id:'chat_focus', group:'Чат', combo:'ctrl+k', title:'Фокус на ввод сообщения', note:'Удобно, если курсор в игре/меню.', run:function(){ _focusChat(); } },
    { id:'chat_to',    group:'Чат', combo:'ctrl+l', title:'Фокус на получателя (ЛС)', note:'Переключение адресата в выпадающем списке.', run:function(){ _focusChatTo(); } },
    { id:'chat_send',  group:'Чат', combo:'ctrl+enter', title:'Отправить сообщение', note:'Если поле ввода не в фокусе — просто сфокусирует.', inInput:true, run:function(){ _sendChat(); } },
    { id:'chat_cmd',   group:'Чат', combo:'ctrl+space', title:'Показать список команд', note:'Работает в поле ввода.', inInput:true, run:function(){ try{ if(window.GameChat && typeof GameChat._viewCommand==='function') GameChat._viewCommand(); }catch(e){} } },
    { id:'chat_emoji', group:'Чат', combo:'ctrl+e', title:'Эмодзи/смайлы', note:'Открыть/закрыть панель смайлов.', inInput:true, run:function(){ _toggleSmile(); } },
    { id:'chat_autoscroll', group:'Чат', combo:'ctrl+m', title:'Автопрокрутка чата', note:'Вкл/выкл автоскролл к последнему сообщению.', run:function(){ _toggleAutoscroll(); } },
    { id:'chat_prevtab', group:'Чат', combo:'ctrl+arrowleft', title:'Предыдущая вкладка чата', note:'Циклическое переключение вкладок.', run:function(){ _cycleChatTab(-1); } },
    { id:'chat_nexttab', group:'Чат', combo:'ctrl+arrowright', title:'Следующая вкладка чата', note:'Циклическое переключение вкладок.', run:function(){ _cycleChatTab(1); } },

    // Чат — быстрые каналы (фиксированные)
    { id:'ch_world', group:'Чат', combo:'ctrl+1', title:'Канал: Мир', note:'Открывает вкладку «Мир».', run:function(){ _switchChatChannelById(0); } },
    { id:'ch_loc',   group:'Чат', combo:'ctrl+2', title:'Канал: Локация', note:'Открывает вкладку «Локация».', run:function(){ _switchChatChannelById(8); } },
    { id:'ch_trade', group:'Чат', combo:'ctrl+3', title:'Канал: Торг', note:'Открывает вкладку «Торг».', run:function(){ _switchChatChannelById(9); } },
    { id:'ch_clan',  group:'Чат', combo:'ctrl+4', title:'Канал: Клан', note:'Открывает вкладку «Клан».', run:function(){ _switchChatChannelById(21); } },
    { id:'ch_cop',   group:'Чат', combo:'ctrl+5', title:'Канал: Полиция', note:'Если доступен (группа 1–3).', run:function(){ _switchChatChannelById(10); } },

    // Чат — доступ к PM-вкладкам по позиции
    { id:'chat_tab1', group:'Чат', combo:'ctrl+shift+1', title:'Вкладка чата №1', note:'Первая вкладка слева (включая ЛС/PM).', run:function(){ _switchChatTabByIndex(1); } },
    { id:'chat_tab2', group:'Чат', combo:'ctrl+shift+2', title:'Вкладка чата №2', note:'Вторая вкладка слева.', run:function(){ _switchChatTabByIndex(2); } },
    { id:'chat_tab3', group:'Чат', combo:'ctrl+shift+3', title:'Вкладка чата №3', note:'Третья вкладка слева.', run:function(){ _switchChatTabByIndex(3); } },
    { id:'chat_tab4', group:'Чат', combo:'ctrl+shift+4', title:'Вкладка чата №4', note:'Четвёртая вкладка слева.', run:function(){ _switchChatTabByIndex(4); } },
    { id:'chat_tab5', group:'Чат', combo:'ctrl+shift+5', title:'Вкладка чата №5', note:'Пятая вкладка слева.', run:function(){ _switchChatTabByIndex(5); } },
    { id:'chat_tab6', group:'Чат', combo:'ctrl+shift+6', title:'Вкладка чата №6', note:'Шестая вкладка слева.', run:function(){ _switchChatTabByIndex(6); } },
    { id:'chat_tab7', group:'Чат', combo:'ctrl+shift+7', title:'Вкладка чата №7', note:'Седьмая вкладка слева.', run:function(){ _switchChatTabByIndex(7); } },
    { id:'chat_tab8', group:'Чат', combo:'ctrl+shift+8', title:'Вкладка чата №8', note:'Восьмая вкладка слева.', run:function(){ _switchChatTabByIndex(8); } },
    { id:'chat_tab9', group:'Чат', combo:'ctrl+shift+9', title:'Вкладка чата №9', note:'Девятая вкладка слева.', run:function(){ _switchChatTabByIndex(9); } },

    // Чат — очистка
    { id:'chat_clear_input', group:'Чат', combo:'ctrl+backspace', title:'Очистить поле ввода', note:'Удаляет текст из поля ввода.', inInput:true, run:function(){ _clearChatInput(); } },
    { id:'chat_clear', group:'Чат', combo:'ctrl+shift+backspace', title:'Очистить сообщения (двойное нажатие)', note:'Защита от случайного нажатия.', run:function(){ _clearChatWithArm(); } },

    // Меню — быстрое открытие основных разделов (альтернатива Alt+…)
    { id:'m_pok',    group:'Меню', combo:'ctrl+alt+p', title:'Раздел: Покемоны', run:function(){ try{ if (Game.modals && Game.modals.pokemons) Game.modals.pokemons(); }catch(e){} } },
    { id:'m_inv',    group:'Меню', combo:'ctrl+alt+i', title:'Раздел: Инвентарь', run:function(){ _inventory('all'); } },
    { id:'m_quests', group:'Меню', combo:'ctrl+alt+q', title:'Раздел: Квесты / дневник', run:function(){ try{ if (Game.modals && Game.modals.diary) Game.modals.diary('quests'); }catch(e){} } },
    { id:'m_map',    group:'Меню', combo:'ctrl+alt+m', title:'Раздел: Карта', run:function(){ _openModalSafe('map'); } },
    { id:'m_shop',   group:'Меню', combo:'ctrl+alt+j', title:'Раздел: Магазин', run:function(){ _openModalSafe('shop'); } },
    { id:'m_market', group:'Меню', combo:'ctrl+alt+k', title:'Раздел: Рынок', run:function(){ try{ if (Game.modals && Game.modals.market) Game.modals.market(); }catch(e){} } },
    { id:'m_clan',   group:'Меню', combo:'ctrl+alt+g', title:'Раздел: Кланы / гильдии', run:function(){ try{ if (Game.modals && Game.modals.clans) Game.modals.clans(); }catch(e){} } },
    { id:'m_settings',group:'Меню', combo:'ctrl+alt+s', title:'Раздел: Настройки', run:function(){ try{ if (typeof settings === 'function') settings(); }catch(e){} } },

    // Инвентарь — категории (Ctrl+Alt+Shift+…)
    { id:'inv_all', group:'Инвентарь', combo:'ctrl+alt+shift+a', title:'Категория: Всё', note:'Открывает инвентарь и выбирает «Всё».', run:function(){ _inventory('all'); } },
    { id:'inv_mod', group:'Инвентарь', combo:'ctrl+alt+shift+m', title:'Категория: Модификаторы', run:function(){ _inventory('modificator'); } },
    { id:'inv_egg', group:'Инвентарь', combo:'ctrl+alt+shift+e', title:'Категория: Яйца', run:function(){ _inventory('egg'); } },
    { id:'inv_evo', group:'Инвентарь', combo:'ctrl+alt+shift+v', title:'Категория: Эволюция', run:function(){ _inventory('evolver'); } },
    { id:'inv_craft',group:'Инвентарь', combo:'ctrl+alt+shift+c', title:'Категория: Крафт', run:function(){ _inventory('craft'); } },
    { id:'inv_potion',group:'Инвентарь', combo:'ctrl+alt+shift+r', title:'Категория: Лечение', note:'Зелья/восстановление.', run:function(){ _inventory('potion'); } },
    { id:'inv_ball', group:'Инвентарь', combo:'ctrl+alt+shift+b', title:'Категория: Покеболы', run:function(){ _inventory('ball'); } },
    { id:'inv_tm',   group:'Инвентарь', combo:'ctrl+alt+shift+t', title:'Категория: ТМ/ТR', run:function(){ _inventory('tm'); } },
    { id:'inv_quest',group:'Инвентарь', combo:'ctrl+alt+shift+q', title:'Категория: Квестовые', run:function(){ _inventory('quest'); } },
    { id:'inv_berry',group:'Инвентарь', combo:'ctrl+alt+shift+y', title:'Категория: Ягоды', run:function(){ _inventory('berry'); } },
    { id:'inv_other',group:'Инвентарь', combo:'ctrl+alt+shift+o', title:'Категория: Другое', run:function(){ _inventory('other'); } },

    // Окна (основные)
    { id:'pokemons',      group:'Окна', combo:'alt+p', title:'Покемоны', run:function(){ try{ if (Game.modals && Game.modals.pokemons) Game.modals.pokemons(); }catch(e){} } },
    { id:'inventory_all', group:'Окна', combo:'alt+i', title:'Инвентарь (всё)', run:function(){ _inventory('all'); } },
    { id:'bag',           group:'Окна', combo:'alt+b', title:'Инвентарь (сумка)', run:function(){ _inventory('bag'); } },
    { id:'craft',         group:'Окна', combo:'alt+c', title:'Крафт', run:function(){ _openModalSafe('craft'); } },
    { id:'trainers',      group:'Окна', combo:'alt+t', title:'Тренеры', run:function(){ _openModalSafe('trainers'); } },
    { id:'quests',        group:'Окна', combo:'alt+q', title:'Квесты / дневник', run:function(){ try{ if (Game.modals && Game.modals.diary) Game.modals.diary('quests'); }catch(e){} } },
    { id:'recover',       group:'Окна', combo:'alt+h', title:'Восстановление', run:function(){ try{ if (typeof recover === 'function') recover(); }catch(e){} } },
    { id:'settings',      group:'Окна', combo:'alt+s', title:'Настройки', run:function(){ try{ if (typeof settings === 'function') settings(); }catch(e){} } },
    { id:'teleport',      group:'Окна', combo:'alt+v', title:'Телепорт (локация 3)', run:function(){ try{ if (typeof goLocationTelep === 'function') goLocationTelep(3); }catch(e){} } },
    { id:'map',           group:'Окна', combo:'alt+m', title:'Карта', run:function(){ _openModalSafe('map'); } },
    { id:'pokedex',       group:'Окна', combo:'alt+d', title:'Покедекс', run:function(){ _openModalSafe('pokedex'); } },

    // Окна (дополнительно)
    { id:'clans',      group:'Окна', combo:'alt+g', title:'Кланы / гильдии', run:function(){ try{ if (Game.modals && Game.modals.clans) Game.modals.clans(); }catch(e){} } },
    { id:'market',     group:'Окна', combo:'alt+k', title:'Рынок', run:function(){ try{ if (Game.modals && Game.modals.market) Game.modals.market(); }catch(e){} } },
    { id:'lombard',    group:'Окна', combo:'alt+l', title:'Ломбард', run:function(){ try{ if (Game.modals && Game.modals.lombard) Game.modals.lombard(); }catch(e){} } },
    { id:'shop',       group:'Окна', combo:'alt+j', title:'Магазин', run:function(){ _openModalSafe('shop'); } },
    { id:'transfer',   group:'Окна', combo:'alt+u', title:'Передача', run:function(){ _openModalSafe('transfer'); } },
    { id:'repair',     group:'Окна', combo:'alt+r', title:'Ремонт', run:function(){ _openModalSafe('repair'); } },
    { id:'berry',      group:'Окна', combo:'alt+y', title:'Ягоды', run:function(){ _openModalSafe('berry'); } },
    { id:'battlepass', group:'Окна', combo:'alt+z', title:'Боевой пропуск', run:function(){ _openModalSafe('battlepass'); } },
    { id:'calendar',   group:'Окна', combo:'alt+n', title:'Календарь', run:function(){ _openModalSafe('calendar'); } },
    { id:'work',       group:'Окна', combo:'alt+o', title:'Работа', run:function(){ _openModalSafe('work'); } },
    { id:'lvlpr',      group:'Окна', combo:'alt+e', title:'Прокачка / уровни', run:function(){ _openModalSafe('lvlpr'); } },
    { id:'web_offline',group:'Окна', combo:'alt+w', title:'Оффлайн-страница', run:function(){ _openModalSafe('web_offline'); } },

    // Бой (располагаем ниже окон/чата; но комбинации уникальны для боя)

    // Бой — быстрые (без модификаторов)
    // Работают только в бою и НЕ срабатывают при наборе текста (в полях ввода/textarea).
    { id:'btl_fast_move_1', group:'Бой', combo:'1', title:'Атака 1', note:'Быстро: 1–4 (без Alt).', when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(0)').trigger('click'); }catch(e){} } },
    { id:'btl_fast_move_2', group:'Бой', combo:'2', title:'Атака 2', when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(1)').trigger('click'); }catch(e){} } },
    { id:'btl_fast_move_3', group:'Бой', combo:'3', title:'Атака 3', when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(2)').trigger('click'); }catch(e){} } },
    { id:'btl_fast_move_4', group:'Бой', combo:'4', title:'Атака 4', when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(3)').trigger('click'); }catch(e){} } },

    { id:'btl_fast_item_1', group:'Бой', combo:'q', title:'Предмет 1', note:'Быстро: Q/W/E/R — предметы 1–4.', when:_hasBattle, run:function(){ try{ $('.BattleItems .Item:eq(0)').trigger('click'); }catch(e){} } },
    { id:'btl_fast_item_2', group:'Бой', combo:'w', title:'Предмет 2', when:_hasBattle, run:function(){ try{ $('.BattleItems .Item:eq(1)').trigger('click'); }catch(e){} } },
    { id:'btl_fast_item_3', group:'Бой', combo:'e', title:'Предмет 3', when:_hasBattle, run:function(){ try{ $('.BattleItems .Item:eq(2)').trigger('click'); }catch(e){} } },
    { id:'btl_fast_item_4', group:'Бой', combo:'r', title:'Предмет 4', when:_hasBattle, run:function(){ try{ $('.BattleItems .Item:eq(3)').trigger('click'); }catch(e){} } },

    { id:'btl_fast_leave', group:'Бой', combo:'x', title:'Выйти/сдаться', note:'Быстро: X.', when:_hasBattle, run:function(){ try{ $('.ButtonFight.Button.LeaveButton').trigger('click'); }catch(e){} } },

    { id:'btl_move_1', group:'Бой', combo:'alt+1', title:'Атака 1', note:'Работает только в бою.', inInput:true, when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(0)').trigger('click'); }catch(e){} } },
    { id:'btl_move_2', group:'Бой', combo:'alt+2', title:'Атака 2', note:'Работает только в бою.', inInput:true, when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(1)').trigger('click'); }catch(e){} } },
    { id:'btl_move_3', group:'Бой', combo:'alt+3', title:'Атака 3', note:'Работает только в бою.', inInput:true, when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(2)').trigger('click'); }catch(e){} } },
    { id:'btl_move_4', group:'Бой', combo:'alt+4', title:'Атака 4', note:'Работает только в бою.', inInput:true, when:_hasBattle, run:function(){ try{ $('.MoveBox .Move:eq(3)').trigger('click'); }catch(e){} } },
    { id:'btl_item_1', group:'Бой', combo:'alt+f', title:'Использовать 1-й предмет', note:'Работает только в бою.', inInput:true, when:_hasBattle, run:function(){ try{ $('.BattleItems .Item:eq(0)').trigger('click'); }catch(e){} } },
    { id:'btl_leave',  group:'Бой', combo:'alt+x', title:'Сдаться / выйти из боя', note:'Работает только в бою.', inInput:true, when:_hasBattle, run:function(){ try{ $('.ButtonFight.Button.LeaveButton').trigger('click'); }catch(e){} } }
  ];

  // ---------------------------------------------------------
  // Комбо: нормализация, рендер, матчинг
  // ---------------------------------------------------------
  var _parsed = Object.create(null);

  function _parseCombo(combo){
    if(_parsed[combo]) return _parsed[combo];
    var out = {alt:false,ctrl:false,shift:false,meta:false,key:''};
    var parts = (combo || '').toLowerCase().split('+').map(function(p){ return (p||'').trim(); }).filter(Boolean);
    parts.forEach(function(p){
      if(p === 'alt') out.alt = true;
      else if(p === 'ctrl' || p === 'control') out.ctrl = true;
      else if(p === 'shift') out.shift = true;
      else if(p === 'meta' || p === 'win' || p === 'cmd') out.meta = true;
      else out.key = p;
    });
    _parsed[combo] = out;
    return out;
  }

  function _keyFromEvent(e){
    var k = (e.key || '').toLowerCase();
    var code = (e.code || '');

    if(k === 'escape' || k === 'esc') return 'esc';
    if(k === 'enter') return 'enter';
    if(k === ' ' || k === 'spacebar') return 'space';
    if(k === 'tab') return 'tab';
    if(k === 'backspace') return 'backspace';
    if(k === 'delete') return 'delete';
    if(k === 'arrowleft') return 'arrowleft';
    if(k === 'arrowright') return 'arrowright';
    if(k === 'arrowup') return 'arrowup';
    if(k === 'arrowdown') return 'arrowdown';
    if(k === 'pageup') return 'pageup';
    if(k === 'pagedown') return 'pagedown';
    if(k === '/') return '/';
    if(k === '?') return '/';

    // RU -> EN по символу
    if(k && k.length === 1){
      var mapped = RU_TO_EN[k];
      if(mapped) return mapped.toLowerCase();
      return k.toLowerCase();
    }

    // fallback по физической клавише
    if(code.indexOf('Key') === 0) return code.slice(3).toLowerCase();
    if(code.indexOf('Digit') === 0) return code.slice(5).toLowerCase();
    if(code === 'Slash') return '/';

    return k;
  }

  function _prettyPart(p){
    p = (p || '').toLowerCase();
    if(p === 'alt') return 'Alt';
    if(p === 'ctrl') return 'Ctrl';
    if(p === 'shift') return 'Shift';
    if(p === 'meta' || p === 'win') return 'Win';
    if(p === 'esc') return 'Esc';
    if(p === 'enter') return 'Enter';
    if(p === 'space') return 'Space';
    if(p === 'tab') return 'Tab';
    if(p === 'backspace') return 'Backspace';
    if(p === 'delete') return 'Del';
    if(p === 'arrowleft') return '←';
    if(p === 'arrowright') return '→';
    if(p === 'arrowup') return '↑';
    if(p === 'arrowdown') return '↓';
    if(p === 'pageup') return 'PgUp';
    if(p === 'pagedown') return 'PgDn';
    if(p === '/') return '/';
    if(p.length === 1) return p.toUpperCase();
    return p;
  }

  function _formatComboHTML(combo){
    if(!combo) return '';
    var parts = combo.split('+').map(function(p){ return (p||'').trim(); }).filter(Boolean);
    var html = '';
    parts.forEach(function(p, i){
      if(i) html += '<span class="hk-plus">+</span>';
      html += '<kbd>'+_prettyPart(p)+'</kbd>';
    });
    return html;
  }

  function _formatComboText(combo){
    if(!combo) return '';
    return combo.split('+').map(function(p){ return _prettyPart((p||'').trim()); }).join(' + ');
  }

  function renderHelpHTML(){
    // Возвращает ТОЛЬКО список хоткеев (без заголовка/поиска) — UI строится в settings()
    var order = ['Бой','Чат','Меню','Инвентарь','Окна','Навигация'];
    var byGroup = {};
    CATALOG.forEach(function(h){
      var g = h.group || 'Другое';
      if(!byGroup[g]) byGroup[g] = [];
      byGroup[g].push(h);
    });

    Object.keys(byGroup).forEach(function(g){
      byGroup[g].sort(function(a,b){
        return (a.combo||'').localeCompare((b.combo||''));
      });
    });

    var groups = order.filter(function(g){ return byGroup[g] && byGroup[g].length; });
    Object.keys(byGroup).forEach(function(g){
      if(groups.indexOf(g) === -1) groups.push(g);
    });

    var html = '<div class="hk-help">';
    groups.forEach(function(g){
      html += '<div class="hk-group" data-group="'+g+'">';
      html += '  <div class="hk-group-title">'+g+'</div>';
      byGroup[g].forEach(function(h){
        var text = ((_formatComboText(h.combo)||'')+' '+(h.title||'')+' '+(h.note||'')).toLowerCase();
        html += '<div class="hk-row" data-hk="'+text.replace(/"/g,'&quot;')+'">';
        html += '  <div class="hk-kbd">'+_formatComboHTML(h.combo)+'</div>';
        html += '  <div class="hk-desc">';
        html += '    <div class="hk-action">'+(h.title||'')+'</div>';
        if(h.note) html += '    <div class="hk-note">'+h.note+'</div>';
        html += '  </div>';
        html += '</div>';
      });
      html += '</div>';
    });
    html += '</div>';
    return html;
  }

  function _matches(e, hk){
    var p = _parseCombo(hk.combo);
    if(!!p.alt  !== !!e.altKey) return false;
    if(!!p.ctrl !== !!e.ctrlKey) return false;
    if(!!p.shift!== !!e.shiftKey) return false;
    // metaKey учитываем только если явно задано
    if(p.meta && !e.metaKey) return false;
    if(!p.key) return false;

    var k = _keyFromEvent(e);
    return k === p.key;
  }

  function _canRun(hk, e){
    if(hk.when && typeof hk.when === 'function'){
      try{ if(!hk.when()) return false; }catch(err){ return false; }
    }
    if(!hk.always && !_isEnabled()) return false;
    if(!hk.always && !hk.inInput && _isTextInput(e.target)) return false;
    return true;
  }

  function _onKeyDown(e){
    try{
      // ESC обрабатываем даже если repeat
      var k = _keyFromEvent(e);
      if(k !== 'esc' && e.repeat) return;

      for(var i=0;i<CATALOG.length;i++){
        var hk = CATALOG[i];
        if(!_canRun(hk, e)) continue;
        if(!_matches(e, hk)) continue;

        if(hk.prevent !== false){
          try{ e.preventDefault(); }catch(err){}
          try{ e.stopPropagation(); }catch(err){}
        }
        try{ hk.run(e); }catch(err){}
        return;
      }
    }catch(err){}
  }

  function bind(){
    if (window.GameHotkeys && window.GameHotkeys.__bound) return;
    document.addEventListener('keydown', _onKeyDown, true);
    if (window.GameHotkeys) window.GameHotkeys.__bound = true;
  }

  function unbind(){
    try{ document.removeEventListener('keydown', _onKeyDown, true); }catch(e){}
    if (window.GameHotkeys) window.GameHotkeys.__bound = false;
  }

  function getTooltipText(){
    return 'Горячие клавиши: в бою 1–4 атаки, Q/W/E/R предметы, X выход; Ctrl+/ — справка; Ctrl+K — чат.';
  }

  window.GameHotkeys = {
    __inited:true,
    __bound:false,
    catalog:CATALOG,
    renderHelpHTML:renderHelpHTML,
    getTooltipText:getTooltipText,
    bind:bind,
    unbind:unbind
  };

  bind();
})();;

// Один обработчик для всех "клик-вне" модалок/тултипов
$(document).mouseup(function (e) {
    // Pokemon Info
    var container = $(".window.pokeInfo");
    if (container.has(e.target).length === 0) {
        container.hide();
    }
    // BlockOtherContent
    container = $(".BlockOtherContent");
    if (container.has(e.target).length === 0) {
        container.hide();
    }
    // smileList
    container = $(".smileList");
    if (container.has(e.target).length === 0) {
        container.hide();
    }
    // Tooltip — скрытие (hide), а не удаление!
    var tooltip = $(".tooltip:visible");
    if (tooltip.length && !tooltip.is(e.target) && tooltip.has(e.target).length === 0) {
        tooltip.hide();
    }
    // MiniModal — удаление
    var miniModal = $(".MiniModal:visible");
    if (miniModal.length && !miniModal.is(e.target) && miniModal.has(e.target).length === 0) {
        miniModal.remove();
    }
    // GiveDiv — удаление
    var giveDiv = $(".GiveDiv:visible");
    if (giveDiv.length && !giveDiv.is(e.target) && giveDiv.has(e.target).length === 0) {
        giveDiv.remove();
    }
    // TrainerGive — удаление
    var trainerGive = $(".TrainerGive:visible");
    if (trainerGive.length && !trainerGive.is(e.target) && trainerGive.has(e.target).length === 0) {
        trainerGive.remove();
    }
});

// Кнопка закрытия в Pokemon-списке
$(document).on('click', '.GiveDiv .wrap .PokList .PokBtn', function(e) {
    $(".GiveDiv").remove();
});
// Создание MiniModal с обязательным сбросом инлайновых left/top для адаптивности
function market_go(id, type, val = false) {
    let modalHtml = '';
    if (type == 'stavka_item') {
        modalHtml = '<div class="Name" id="drgMini">Добавить лот</div><div class="Content"><div class="Settings"><center><b>Внимание, прочитайте!</b><br> За каждый лот вы платите 10.000 генкаров.<br>Введите значения <b>через запятую без пробелов</b> (Сначала начальную цену, затем количество предметов, затем цену шага, затем цену выкупа (если хотите без выкупа, то ставьте значение <b>0</b>), затем срок в днях).</center><div class="Step"><input placeholder="Начальная цена, кол-во, шаг, выкуп, срок" onkeydown="if(event.keyCode == 13){market_go(' + id + ',\'stavka_item_go\',$(this).val());}"></div></div></div>';
    } else if (type == 'stavka_egg') {
        modalHtml = '<div class="Name" id="drgMini">Добавить лот</div><div class="Content"><div class="Settings"><center><b>Внимание, прочитайте!</b><br> За каждый лот вы платите 10.000 генкаров.<br>Введите значения <b>через запятую без пробелов</b> (Сначала начальную цену, затем цену шага, затем цену выкупа (если хотите без выкупа, то ставьте значение <b>0</b>), затем срок в днях).</center><div class="Step"><input placeholder="Начальная цена, шаг, выкуп, срок" onkeydown="if(event.keyCode == 13){market_go(' + id + ',\'stavka_egg_go\',$(this).val());}"></div></div></div>';
    } else if (type == 'stavka_pokemon') {
        modalHtml = '<div class="Name" id="drgMini">Добавить лот</div><div class="Content"><div class="Settings"><center><b>Внимание, прочитайте!</b><br> За каждый лот вы платите 10.000 генкаров.<br>Введите значения <b>через запятую без пробелов</b> (Сначала начальную цену, затем цену шага, затем цену выкупа (если хотите без выкупа, то ставьте значение <b>0</b>), затем срок в днях).</center><div class="Step"><input placeholder="Начальная цена, шаг, выкуп, срок" onkeydown="if(event.keyCode == 13){market_go(' + id + ',\'stavka_pokemon_go\',$(this).val());}"></div></div></div>';
    } else if (type == 'stavka') {
        modalHtml = '<div class="Name" id="drgMini">Добавить ставку, лот №' + id + '</div><div class="Content"><div class="Settings"><div class="Step"><input placeholder="Количество генкаров..." onkeydown="if(event.keyCode == 13){market_go(' + id + ',\'stavka_go\',$(this).val());}"></div></div></div>';
    }
    if (modalHtml) {
        // вставка и сброс инлайновых координат
        $('<div />', {
            "class": 'MiniModal',
            html: modalHtml
        }).appendTo('body');
        $('.MiniModal').css({
            left: '', top: '', right: '', bottom: '', transform: ''
        }); // сброс координат для CSS-адаптивности
        $('.MiniModal').draggabilly({
            handle: '#drgMini',
            containment: true
        });
    
  }else{
    $.ajax({
      url: "/do/marketgo",
      type: "POST",
      data: {
          id: id,
          type: type,
          val: val
      },
      success: function (response){
        response = (typeof response === 'string') ? JSON.parse(response) : response;
        if(response['minus']) {
          Game.notifications.main(response['minus'], 'minus');
        }
        if(response['plus']) {
          Game.notifications.main(response['plus'], 'plus');
        }
        if(response['error'] == 1) {
          Game.notifications.main(response['text'], 'error');
        }else{
          openMarket();
          Game.notifications.main(response['text'], 'success');
        }
      }
    });
  }
}
function startGame() {
  document.querySelectorAll('.GlassModalBg, .Waiter').forEach(el => el.remove());
}
//function evenOn(){
  //$('.Events .Steps').toggle();
//}
function flipper(){
  $('.Events .Steps').toggle();
}
function createView(id) {
  // Удаляем старое окно этого боя, если есть
  $('.ViewBattle.' + id).remove();

  // Создаём новое окно боя
  $('<div />', {
    class: 'ViewBattle ' + id,
    html: function() {
      let tpl = '';
      tpl += '<div class="Name" id="drgView">Бой на локации' +
        '<div class="Close" onclick="$(\'.ViewBattle.' + id + '\').remove()">' +
        '<i class="fas fa-times"></i></div></div>';
      tpl += '<div class="Content"></div>';
      return tpl;
    }
  }).appendTo('body');

  // Делаем окно перетаскиваемым, если не мобильное устройство
  if (!device.mobile()) {
    $('.ViewBattle.' + id).draggabilly({
      handle: '#drgView',
      containment: true
    });
  }

  // Запускаем обновление окна боя
  setFightView(id);
}
let fightViewPolling = {};

function setFightView(id) {
  // Флаг, чтобы не было двойных запросов на один бой
  if (fightViewPolling[id]) return;
  fightViewPolling[id] = true;

  $.ajax({
    url: "/do/viewBattleView",
    type: "POST",
    data: "id=" + encodeURIComponent(id),
    timeout: 1000, // 10 секунд, чтобы избежать зависания
    success: function (response) {
      try {
        response = typeof response === "string" ? JSON.parse(response) : response;
      } catch (e) {
        // Можно обработать ошибку парсинга
        $('.ViewBattle.' + id + ' .Content').html('<div class="error">Ошибка загрузки боя. Попробуйте обновить страницу.</div>');
        fightViewPolling[id] = false;
        return;
      }

      $('.ViewBattle.' + id + ' .Content').html(response['html']);

      if (!response['battle_end']) {
        // Повторно запускаем обновление с небольшим интервалом (1 секунда)
        setTimeout(function() {
          fightViewPolling[id] = false;
          setFightView(id);
        }, 100);
      } else {
        fightViewPolling[id] = false;
        // Можно показать уведомление о завершении боя
        // alert('Бой завершён!');
      }
    },
    error: function () {
      // В случае ошибки — пробуем обновить через 3 секунды
      setTimeout(function() {
        fightViewPolling[id] = false;
        setFightView(id);
      }, 300);
    }
  });
}
// ——— Стили (подключаются один раз) ———
function ensureFightStyles(){
  if(document.getElementById('fight-modal-css')) return;
  const css = `
  /* контейнер без затемнения */
  .pk-overlay{position:fixed;inset:0;z-index:9999;background:transparent;display:flex;align-items:flex-start;justify-content:center}
  /* компактная модалка */
  .pk-modal{--radius:14px;--pad:14px;width:min(720px,92vw);max-height:86vh;margin:7vh 0;background:#ffffff;border-radius:var(--radius);
            box-shadow:0 14px 38px rgba(0,0,0,.18);display:flex;flex-direction:column;overflow:hidden;border:1px solid #edf1f7}
  .pk-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #eff3f9}
  .pk-title{display:flex;flex-direction:column;gap:4px;cursor:move}
  .pk-title h3{margin:0;font:600 20px/1.15 system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .pk-title small{color:#6c7a92;font:500 12px/1 system-ui}
  .pk-close{appearance:none;border:0;background:#f3f6fb;border-radius:10px;width:34px;height:34px;display:grid;place-items:center;cursor:pointer;
            -webkit-tap-highlight-color:transparent;touch-action:manipulation;position:relative;z-index:2}
  .pk-close:active{transform:scale(.98)}
  .pk-close:hover{background:#e9eef7}
  .pk-tabs{display:flex;gap:8px;padding:10px 12px 6px}
  .pk-tab{padding:7px 12px;border-radius:999px;border:1px solid #e6ebf4;background:#f7f9fd;font:600 13px/1 system-ui;color:#556683;cursor:pointer}
  .pk-tab.is-active{background:#ecf3ff;border-color:#d7e6ff;color:#2154ff}
  .pk-body{padding:8px 12px 12px;overflow:auto}
  .pk-section{display:none}
  .pk-section.is-active{display:block}
  .pk-empty{padding:20px;border:1px dashed #e6ecf5;border-radius:12px;background:#fafcff;color:#6a7b95;text-align:center}

  /* — Правки твоей разметки внутри модалки — */
  .pk-list{display:grid;gap:10px}
  .pk-list .Step{margin:0 !important;padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important}
  .pk-list .Step .fS{
      display:flex;align-items:center;gap:12px;
      background:#fff;border:1px solid #e6eefc;border-radius:12px;
      padding:10px 12px;min-height:42px
  }
  .pk-list .Step .fS > span{color:#7b8ba6;font-weight:600;margin:0 6px;cursor:pointer}
  .pk-list .user-link{display:flex;align-items:center;gap:6px}
  .pk-list .Info-Link{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;background:#e9f0ff;color:#1f4cff}
  .pk-list .label{padding:2px 8px;border-radius:8px;background:#f5f7fc}
  .pk-body *{user-select:none}

  @media (max-width:560px){
    .pk-modal{width:100vw;height:100vh;max-height:100vh;margin:0;border-radius:0}
    .pk-title{cursor:default}
  }
  `;
  const style = document.createElement('style');
  style.id = 'fight-modal-css';
  style.textContent = css;
  document.head.appendChild(style);
}

// ——— Модалка «Бои на локации» ———
function setFight(){
  ensureFightStyles();

  $.ajax({
    url: "/do/viewBattle",
    type: "POST",
    success: function (raw){
      let resp;
      try{ resp = (typeof raw === 'object') ? raw : JSON.parse(raw); }
      catch(e){ resp = {count:0, gym:0, html:'', error:true}; }

      // пересоздаём модалку
      $('#pk-fight-overlay').remove();

      const $ov    = $('<div/>',{id:'pk-fight-overlay',class:'pk-overlay'}).appendTo('body');
      const $modal = $('<div/>',{id:'pk-fight-modal',class:'pk-modal pk-fight','aria-modal':true,role:'dialog'}).appendTo($ov);

      // Шапка (ручка перетаскивания — только .pk-title, крестик вне handle)
      const $header = $(`
        <div class="pk-header">
          <div class="pk-title" id="pkDragHandle">
            <h3>Бои на локации</h3>
            <small>Следите за активными дуэлями поблизости</small>
          </div>
          <button class="pk-close" type="button" aria-label="Закрыть">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M6 6l12 12M18 6L6 18" stroke="#1e293b" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      `).appendTo($modal);

      // Табы
      const $tabs = $(`
        <div class="pk-tabs" role="tablist" aria-label="Навигация по разделам боёв">
          <button class="pk-tab is-active" data-tab="active"  role="tab" aria-selected="true"  aria-controls="pk-panel-active"  type="button">Активные</button>
          <button class="pk-tab"          data-tab="queue"   role="tab" aria-selected="false" aria-controls="pk-panel-queue"   type="button">Ожидание</button>
          <button class="pk-tab"          data-tab="history" role="tab" aria-selected="false" aria-controls="pk-panel-history" type="button">История</button>
        </div>
      `).appendTo($modal);

      // Контент
      const $body   = $('<div class="pk-body"/>').appendTo($modal);
      const $active = $('<section class="pk-section is-active" id="pk-panel-active"  data-panel="active"  role="tabpanel" aria-labelledby="tab-active"/>').appendTo($body);
      const $queue  = $('<section class="pk-section"            id="pk-panel-queue"   data-panel="queue"   role="tabpanel" aria-labelledby="tab-queue"  hidden/>').appendTo($body);
      const $hist   = $('<section class="pk-section"            id="pk-panel-history" data-panel="history" role="tabpanel" aria-labelledby="tab-history" hidden/>').appendTo($body);

      // Активные бои
      if(resp['count'] == 0){
        if(resp['gym'] == 1){
          $active.append('<div class="pk-empty">На данной локации просмотр боёв недоступен.</div>');
        }else{
          $active.append('<div class="pk-empty">Пока активных боёв нет.</div>');
        }
      }else{
        if(resp['html']){
          $active.append('<div class="pk-list">'+resp['html']+'</div>');
        }else{
          $active.append('<div class="pk-empty">Пока активных боёв нет.</div>');
        }
      }

      // Очередь (если сервер отдаёт queue_html — покажем)
      if(resp['queue_html']){
        $queue.append('<div class="pk-list">'+resp['queue_html']+'</div>');
      }else{
        $queue.append('<div class="pk-empty">Очередь пуста или скрыта. Зайдите к NPC арены, чтобы встать в очередь.</div>');
      }

      // История — заглушка
      $hist.append('<div class="pk-empty">История недоступна на этой локации.</div>');

      // ——— Функции UI ———
      function switchTab(tab){
        // вкладки
        $tabs.find('.pk-tab')
          .removeClass('is-active')
          .attr('aria-selected','false');
        $tabs.find(`.pk-tab[data-tab="${tab}"]`)
          .addClass('is-active')
          .attr('aria-selected','true');

        // панели
        $body.find('.pk-section')
          .removeClass('is-active')
          .attr('hidden', true);
        $body.find(`[data-panel="${tab}"]`)
          .addClass('is-active')
          .removeAttr('hidden');
      }

      // Переключение вкладок (клик и клавиатура)
      $tabs.on('click', '.pk-tab', function(){
        switchTab($(this).data('tab'));
      });
      $tabs.on('keydown', '.pk-tab', function(e){
        if(e.key === 'Enter' || e.key === ' '){
          e.preventDefault();
          switchTab($(this).data('tab'));
        }
      });

      // ——— Закрытие ———
      function closeFight(){
        $('#pk-fight-overlay').remove();
        $(document).off('keydown.pkFight');
      }
      // крестик: click + touchend
      $modal.on('click','.pk-close', closeFight);
      $modal.on('touchend','.pk-close', function(e){ e.preventDefault(); closeFight(); });

      // фон
      $ov.on('click', function(e){ if(e.target === this) closeFight(); });

      // Esc
      $(document).on('keydown.pkFight', function(e){ if(e.key === 'Escape') closeFight(); });

      // ——— Перетаскивание только на десктопах ———
      if(typeof $.fn.draggabilly === 'function' && window.matchMedia('(pointer:fine)').matches){
        $modal.draggabilly({ handle: '#pkDragHandle', containment: true });
      }
    }
  });
}

function showUserTooltip(o, e) {
    e = e || window.event;

    $.ajax({
        url: "/do/tooltip",
        type: "POST",
        data: "user=" + o,
        success: function(s) {
            response = JSON.parse(s);
            if (s && ((s = $.parseJSON(s)).userTooltip && s.userTooltip.login)) {
                var i = $("<div />", {
                        class: "TrainerGive"
                    }),
                    t = ['<div id="TrainerGiveUser">' + s.userTooltip.login + ' </div>'];
                t.push('<div id="btnCardUser" onclick=\'Game.trenercard.opencard("' + s.userTooltip.login + "\");'><i class='fa fa-user'></i><span>Тренеркарта</span></div>"),
                
                s.userTooltip.my ? t.push('<div id="btnSetUser" onclick="settings()"><i class="fa fa-cogs"></i>Настройки</div>') : (
                t.push($("<div />", {
                    "id": 'btnTradeUser',
                    html: '<i class="fa fa-handshake"></i><span>Обменяться</span>'
                }).on("click", function() {
                    ClassInfo && ClassInfo._action({
                        type: "offers",
                        target: "trade",
                        offerID: -1,
                        userID: parseInt(s.userTooltip.id)
                    }, function(o) {
                        o.offersCreate && Game.notifications.main(Lang.notify_success_wait, "success")
                    })
                })),
                t.push($("<div />", {
                    "id": 'btnFightUser',
                    html: '<i class="fa fa-hand-rock"></i><span>Вызвать на бой</span>'
                }).on("click", function() {
                    ClassInfo && ClassInfo._action({
                        type: "offers",
                        target: "battle",
                        offerID: -1,
                        userID: parseInt(s.userTooltip.id)
                    }, function(o) {
                        o.offersCreate && Game.notifications.main(Lang.notify_success_wait, "success")
                    })
                })),
                s.userTooltip.friend
  ? t.push(
      // Передаём userId друга, а не текущего пользователя!
      '<div id="btnGiftUser" onclick=\'openGiftShopForFriend(' + s.userTooltip.id + ', "' + s.userTooltip.login + '");\'><i class="fa fa-gift"></i><span>Подарить подарок</span></div>' +
      '<div id="btnFrndDelUser" onclick=\'userAction("' + s.userTooltip.login + '","DeleteFriend");\'><i class="fa fa-user-times"></i><span>Удалить из друзей</span></div>'
    )
  : t.push(
      '<div id="btnFrndPlusUser" onclick=\'userAction("' + s.userTooltip.login + '", "friend");\'><i class="fa fa-user-plus"></i><span>Добавить в друзья</span></div>'
    )
                ),
                s.userTooltip.delClan && t.push("<div id='btnClanDelUser' onclick='userAction(\"" + s.userTooltip.login + '","DeleteClan");\'><i class="fa fa-minus-circle"></i><span>Удалить из клана</span></div>'), 
                s.userTooltip.addClan && t.push("<div id='btnClanPlusUser' onclick='userAction(\"" + s.userTooltip.login + '","AddClan");\'><i class="fa fa-plus-circle"></i><span>Пригласить в клан</span></div>'), 
                i.append('<div class="Avatar" style="background-image: url(/img/avatars/mini/'+s.userTooltip.id+'.png);"></div>',
                $("<div />", {
                    class: "Title"
                }).append(t)), i.appendTo("body");

                // Новый блок: позиционирование возле элемента (адаптивно + для мобильных)
                var callerElem = e.target || e.srcElement;
                var rect = callerElem.getBoundingClientRect();

                var $tooltip = i;
                var tooltipWidth = $tooltip.outerWidth();
                var tooltipHeight = $tooltip.outerHeight();

                var winWidth = window.innerWidth;
                var winHeight = window.innerHeight;

                var left, top;

                // Для мобильных — всегда по центру экрана
                if (winWidth <= 600) {
                    left = (winWidth - tooltipWidth) / 2 + window.scrollX;
                    top = (winHeight - tooltipHeight) / 2 + window.scrollY;
                } else {
                    // Ставим справа и чуть ниже вызывающей кнопки
                    left = rect.left + window.scrollX + rect.width + 10;
                    top = rect.top + window.scrollY;

                    // Если не помещается справа — ставим слева от кнопки
                    if (left + tooltipWidth > winWidth) {
                        left = rect.left + window.scrollX - tooltipWidth - 10;
                        if (left < 0) left = 0;
                    }
                    // Если не помещается снизу — двигаем вверх
                    if (top + tooltipHeight > winHeight) {
                        top = winHeight - tooltipHeight - 10 + window.scrollY;
                        if (top < 0) top = 0;
                    }
                }

                $('.TrainerGive').css({"left": left + 'px', "top": top + 'px'});
            }
        }
    })
}

function user_to_chat_add(user, event) {
    // event — можно использовать, если нужно (например, event.preventDefault())
    $.ajax({
        url: "/do/tooltip",
        type: "POST",
        data: { user: user },
        dataType: "json",
        success: function(response) {
            if (response && response.userTooltip && response.userTooltip.login) {
                // Проставляем имя тренера сразу в оба поля (для мобильного и десктопа, если есть)
                $("#chat_user_to").val(response.userTooltip.login);
                $("#chat_user_to_desktop").val(response.userTooltip.login);

                // Фокус на поле ввода сообщения (сначала на мобильное, если есть, иначе на десктоп)
                if ($("#chat_send").length && $("#chat_send").is(":visible")) {
                    $("#chat_send").focus();
                } else if ($("#chat_send_desktop").length && $("#chat_send_desktop").is(":visible")) {
                    $("#chat_send_desktop").focus();
                }
            }
        }
    });
}
function pokemonChat(id) {
    // Сначала ищем desktop-версию поля
    var $desktop = $("#chat_send_desktop");
    // Потом мобильную версию
    var $mobile = $("#chat_send");
    var $chatInput = null;

    // Если desktop-версия видима и существует — используем её
    if ($desktop.length && $desktop.is(":visible")) {
        $chatInput = $desktop;
    }
    // Если мобильная версия видима и существует — используем её
    else if ($mobile.length && $mobile.is(":visible")) {
        $chatInput = $mobile;
    }
    // Если ни одно не видно (например, в тестах) — просто выберем то, что есть
    else if ($desktop.length) {
        $chatInput = $desktop;
    }
    else if ($mobile.length) {
        $chatInput = $mobile;
    }

    if ($chatInput && $chatInput.length) {
        var chatAt = $chatInput.val() || "";
        // Добавляем идентификатор покемона (с пробелом, если нужно)
        $chatInput.val(chatAt + (chatAt && !chatAt.endsWith(" ") ? " " : "") + "%p" + id);
        $chatInput.focus();
    }
}

// Универсальное позиционирование (для тултипов, всплывашек и т.д.)
function positionElement(targetSelector, elementSelector) {
    var $target = $(targetSelector);
    var $element = $(elementSelector);
    if ($target.length && $element.length) {
        var offset = $target.offset();
        // Смещение: можно изменить по вашему желанию (например, 45px вниз/вправо)
        var left = Math.round(offset.left) + 45;
        var top = Math.round(offset.top) + 45;
        $element.css({
            left: left + "px",
            top: top + "px"
        });
    }
}
function userAction(t,e){$.ajax({url:"/do/trainers",type:"POST",data:"type="+e+"&user="+t,success:function(t){1==(t=JSON.parse(t)).error?Game.notifications.main(t.text,"error"):Game.notifications.main(t.text,"success")}})}
function setTrenerBlock(s,c){
  $(".Tabses .Table").hide(),
  $('.Tabses .Tabs div').removeClass('active'),
  $("#"+s).show(),
  $(c).addClass('active')
}
function closeMudol(t){mudol=$(".mudol"),1==t?($("#pokedex").css("display","none"),mudol.attr("id",""),$("#DexBtn").attr("class","flt")):2==t&&($(".CraftModal").css("display","none"),$("#CraftBtn").attr("class","flt"))}
function goLocation(o){if(o<=0)return!1;$("#locationPreloader").show(),$.ajax({url:"/do/goLocation",type:"POST",data:{location_id:o},success:function(o){1==(o=JSON.parse(o)).error?($("#locationPreloader").delay(100).fadeOut(100),Game.notifications.main(o.text,"error")):($(".preloader span").html(Lang.loader_location),$("#locationPreloader").delay(100).fadeOut(100),updateLocation())}})}
function goLocationTelep(o){if(confirm("Вы уверены, что хотите телепортироваться?") == true) {if(o<=0)return!1;$("#locationPreloader").show(),$.ajax({url:"/do/goLocation",type:"POST",data:{location_id_telep:o},success:function(o){1==(o=JSON.parse(o)).error?($("#locationPreloader").delay(100).fadeOut(100),Game.notifications.main(o.text,"error")):($(".preloader span").html(Lang.loader_location),$("#locationPreloader").delay(100).fadeOut(100),updateLocation())}})}}

function openMarket() {
  dex = $(".mudol"), 0 !== dex.length && dex.html(""), dexBtn = $("#MarketBtn"), dexBtn.attr("class", ""), dex.attr("id", "mark"), dex.css("display", "block"), dex.html('<center>' + mainLoader + '</center>');
  $.ajax({
    url: "/do/market",
    type: "POST",
    dataType: "json",
    success: function(d) {
      dex.html(d.html);
    }
  })
}

function openInfoNursery(i) {
  if (!i) return;

  $.ajax({
    url: "/do/pp",
    type: "POST",
    data: { nursery: i },
    dataType: "json",
    success: function (d) {
      if (d.error == 1) {
        if (typeof closeMudol === 'function') closeMudol(1);
        return Game.notifications.main("Системная ошибка!", "error");
      }

      // ---- модалка с подклассом для гибкого размера
      if (typeof Game !== 'undefined' && Game.modals && typeof Game.modals.modalLoad === 'function') {
        Game.modals.modalLoad(5);
      }
      const $modal = $('.Modal');
      $modal.addClass('modal--pokemon-info');
      const left = Math.max(1, Math.round(($('.MidMenu').offset() || { left: 100 }).left) - 100);
      $modal.css('left', left + 'px');

      // одноразовые стили (те же, что в openInfoLombard)
      if (!document.getElementById('pk-lombard-css')) {
        $('<style id="pk-lombard-css">\
          .Modal.modal--pokemon-info{width:min(630px,96vw);max-height:92vh;overflow:hidden;z-index: 99999}\
          .Modal.modal--pokemon-info .Pokemons{max-height:calc(92vh - 48px);overflow:auto;padding:12px}\
          .Modal.modal--pokemon-info .Info{position:relative}\
          .Modal.modal--pokemon-info .Info .Step.pure{display:flex;gap:8px;align-items:center;background:transparent;border:0;padding:0;margin:6px 0 0 0}\
          .Modal.modal--pokemon-info .Info .Step.pure .Label{font-weight:800;color:#2f4374}\
          .Modal.modal--pokemon-info .Info .Step.pure .Other{font-weight:900;color:#1b2b4f}\
          .Modal.modal--pokemon-info .Info .AbilityLink{border:0;background:transparent;color:#f59e0b;font-weight:900;cursor:pointer;padding:0}\
          .Modal.modal--pokemon-info .Info .AbilityLink:hover{text-decoration:underline}\
          .Modal.modal--pokemon-info .pkStatBtn{position:absolute;right:8px;top:6px;width:30px;height:30px;display:grid;place-items:center;border:1px solid #e6eafe;border-radius:8px;background:#fff;color:#5c6b8a;cursor:pointer}\
          .Modal.modal--pokemon-info .pkMini{position:absolute;right:8px;top:42px;min-width:260px;max-width:320px;padding:10px 12px;border:1px solid #252b3a;border-radius:12px;background:#11141b;color:#eef3ff;box-shadow:0 16px 34px rgba(0,0,0,.35);display:none;z-index:60}\
          .Modal.modal--pokemon-info .pkMini .row{display:flex;justify-content:space-between;padding:6px 2px;border-bottom:1px dashed rgba(255,255,255,.08)}\
          .Modal.modal--pokemon-info .pkMini .row:last-child{border:0}.Modal.modal--pokemon-info .pkMini .k{opacity:.78}\
        </style>').appendTo('head');
      }

      // серверный HTML
      const $wrap = $('<div/>', { "class": "Pokemons", html: d.html }).appendTo($modal);
      const $info = $wrap.find('.Right .Info').first();

      // ——— утилиты
      const findRowStarts = (label) =>
        $info.find('*').filter(function () { return $(this).text().trim().indexOf(label) === 0; }).first();
      const getLine = (label) => {
        let v = '';
        $info.find('*').each(function () {
          if (v) return;
          const t = $(this).text().trim();
          if (t.indexOf(label) === 0) v = t.replace(label, '').trim();
        });
        return v;
      };

      // Характер
      const $charOrig = findRowStarts('Характер:');
      const charVal   = getLine('Характер:');
      if ($charOrig.length) { $charOrig.find('i').remove(); }
      if (charVal) {
        $('<div class="Step pure"><div class="Label">Характер:</div><div class="Other">' + charVal + '</div></div>')
          .insertBefore($charOrig);
        $charOrig.hide();
      }

      // Способность (сохраняем кликабельность)
      const $abilOrig = findRowStarts('Способность:');
      if ($abilOrig.length) {
        const $clone = $abilOrig.clone(true);
        $clone.contents().each(function () {
          if (this.nodeType === 3) {
            this.nodeValue = (this.nodeValue || '').replace(/^(\s*Способность:\s*)/, '');
          } else {
            const $el = $(this);
            const txt = $el.text().trim();
            if (txt.indexOf('Способность:') === 0) {
              const rest = txt.replace(/^Способность:\s*/, '');
              if (rest) { $el.text(rest); } else { $el.remove(); }
            }
          }
        });
        const abilityInner = $('<div>').append($clone.contents()).html().trim();
        const $row = $('<div class="Step pure"><div class="Label">Способность:</div><div class="Other"></div></div>');
        $row.find('.Other').html(abilityInner);
        $row.find('a,button,[onclick]').first().addClass('AbilityLink');
        $row.insertBefore($abilOrig);
        $abilOrig.hide();
      }

      // Генокод
      const $geneOrig = findRowStarts('Генокод:');
      const geneVal   = getLine('Генокод:');
      if ($geneOrig.length && geneVal) {
        $('<div class="Step pure"><div class="Label">Генокод:</div><div class="Other">' + geneVal + '</div></div>')
          .insertBefore($geneOrig);
        $geneOrig.hide();
      }

      // Группа привлекательности — под Генокодом
      const $genderStep = $info.find('.Step.genderPok');
      if ($genderStep.length) {
        const val = $genderStep.find('.Other').text().trim() || '—';
        const $row = $('<div class="Step pure"><div class="Label">Группа привлекательности:</div><div class="Other">' + val + '</div></div>');
        const $geneRowNew = $info.find('.Step.pure .Label').filter(function () { return $(this).text() === 'Генокод:'; }).first().parent();
        if ($geneRowNew.length) $row.insertAfter($geneRowNew);
        $genderStep.hide();
      }

      // Разведение
      const breedOk = Number(d.sparka || 0) === 0;
      const $breedRow = $('<div class="Step pure"><div class="Label">Разведение:</div><div class="Other ' + (breedOk ? 'Green-Color' : 'Red-Color') + '">' + (breedOk ? 'доступно' : 'недоступно') + '</div></div>');
      const $afterGender = $info.find('.Step.pure .Label').filter(function () { return $(this).text() === 'Группа привлекательности:'; }).first().parent();
      if ($afterGender.length) $breedRow.insertAfter($afterGender);

      // Свободные EV (из шага evPok либо из JSON)
      let evFree = ($info.find('.Step.evPok .Other').text().trim()) || (typeof d.ev !== 'undefined' ? String(d.ev) : '0');
      $info.find('.Step.evPok').hide();
      $info.find('*').filter(function () { return /^Свободн(ые|ых)\s*EV/i.test($(this).text().trim()); }).hide();
      $('<div class="Step pure"><div class="Label">Свободные EV:</div><div class="Other">' + (evFree || '0') + '</div></div>').insertAfter($breedRow);

      // Витамины — только в статистике
      let vitaminsText = '';
      const $vitStep = $info.find('.Step.vitaminesPok');
      if ($vitStep.length) { vitaminsText = $vitStep.find('.Other').text().trim(); $vitStep.hide(); }
      $info.find('*').filter(function () { return $(this).text().trim().indexOf('Витамины:') === 0; }).hide();

      // Пойман/Потенциал — только в статистике
      const caught    = getLine('Пойман:')    || (d.birthday ? (d.birthday.date + 'г. тренером ' + d.birthday.user) : 'Неизвестно');
      const potential = getLine('Потенциал:') || (d.potential || '—');
      $info.find('*').filter(function () {
        const t = $(this).text().trim();
        return t.indexOf('Пойман:') === 0 || t.indexOf('Потенциал:') === 0;
      }).hide();

      // Кнопка статистики + мини-панель
      const $btn  = $('<button type="button" class="pkStatBtn staticPok" aria-label="Статистика"><i class="fal fa-chart-line"></i></button>');
      const $mini = $('<div class="pkMini"></div>');
      $info.append($btn, $mini);

      const st0 = +d.st0 || 0, st2 = +d.st2 || 0, st3 = +d.st3 || 0;
      const tasty = [d.hidden, d.st8].filter(Boolean).join(' ').trim();

      $mini.html([
        '<div class="row"><div class="k">Пойман</div><div>' + caught + '</div></div>',
        '<div class="row"><div class="k">Потенциал</div><div>' + potential + '</div></div>',
        (vitaminsText ? '<div class="row"><div class="k">Витамины</div><div>' + vitaminsText + '</div></div>' : ''),
        '<div class="row"><div class="k">Разведение</div><div>' + (breedOk ? 'доступно' : 'недоступно') + '</div></div>',
        '<div class="row"><div class="k">Шоколадная конфета</div><div>' + (st0 ? 'использована' : 'не использована') + '</div></div>',
        '<div class="row"><div class="k">Сладкий кекс</div><div>' + (st2 ? 'использован' : 'не использован') + '</div></div>',
        '<div class="row"><div class="k">Корень априкорна</div><div>' + (st3 ? 'использован' : 'не использован') + '</div></div>',
        (tasty ? '<div class="row"><div class="k">Тренировки</div><div>' + tasty + '</div></div>' : '')
      ].join(''));

      $btn.on('click', function (ev) { ev.stopPropagation(); $mini.toggle(); });
      $(document).off('click.pkMiniHide').on('click.pkMiniHide', function () { $mini.hide(); });

      // Тултипы (как в openInfoLombard)
      try {
        if (window.Tipped && typeof Tipped.create === 'function') {
          try {
            Tipped.remove('.staticPok'); Tipped.remove('.genderPok'); Tipped.remove('.idPok'); Tipped.remove('.vitaminesPok'); Tipped.remove('.evPok');
          } catch (_) {}

          Tipped.create($btn.get(0),
            'Шоколадная конфета <span class="' + (st0 === 0 ? 'Green' : 'Red') + '-Color">' + (st0 === 0 ? 'не использована' : 'использована') + '</span><br>' +
            'Сладкий кекс <span class="' + (st2 === 0 ? 'Green' : 'Red') + '-Color">' + (st2 === 0 ? 'не использован' : 'использован') + '</span><br>' +
            'Корень априкорна <span class="' + (st3 === 0 ? 'Green' : 'Red') + '-Color">' + (st3 === 0 ? 'не использован' : 'использован') + '</span><br>' +
            (d.hidden || '') + ' ' + (d.st8 || '')
          );

          const idNode  = $wrap.find('.Id').get(0);
          if (idNode) Tipped.create(idNode, ' ' + (d.trade == "false" ? 'Покемон приручен' : 'Покемон не приручен') + ' ');
          const hpNode  = $wrap.find('.hp_proggresbar').get(0);
          const expNode = $wrap.find('.exp_progressbar').get(0);
          if (hpNode) Tipped.create(hpNode);
          if (expNode) Tipped.create(expNode);
        }
      } catch (_) {}
    }
  });
}



function openInfoLombard(i) {
  if (!i) return;

  // Загружаем данные
  $.ajax({
    url: "/do/pp",
    type: "POST",
    data: { lombard: i },
    dataType: "json",
    success: function (d) {
      if (d.error == 1) {
        if (typeof closeMudol === 'function') closeMudol(1);
        return Game.notifications.main("Системная ошибка!", "error");
      }

      // ---- модалка с подклассом для гибкого размера
      if (typeof Game !== 'undefined' && Game.modals && typeof Game.modals.modalLoad === 'function') {
        Game.modals.modalLoad(5);
      }
      const $modal = $('.Modal');
      $modal.addClass('modal--pokemon-info'); // поднастройка размеров
      const left = Math.max(1, Math.round(($('.MidMenu').offset() || { left: 100 }).left) - 100);
      $modal.css('left', left + 'px');

      // одноразовые стили для модалки/шагов/мини-статистики
      if (!document.getElementById('pk-lombard-css')) {
        $('<style id="pk-lombard-css">\
          .Modal.modal--pokemon-info{width:min(630px,96vw);max-height:92vh;overflow:hidden}\
          .Modal.modal--pokemon-info .Pokemons{max-height:calc(92vh - 48px);overflow:auto;padding:12px}\
          .Modal.modal--pokemon-info .Info{position:relative}\
          .Modal.modal--pokemon-info .Info .Step.pure{display:flex;gap:8px;align-items:center;background:transparent;border:0;padding:0;margin:6px 0 0 0}\
          .Modal.modal--pokemon-info .Info .Step.pure .Label{font-weight:800;color:#2f4374}\
          .Modal.modal--pokemon-info .Info .Step.pure .Other{font-weight:900;color:#1b2b4f}\
          .Modal.modal--pokemon-info .Info .AbilityLink{border:0;background:transparent;color:#f59e0b;font-weight:900;cursor:pointer;padding:0}\
          .Modal.modal--pokemon-info .Info .AbilityLink:hover{text-decoration:underline}\
          .Modal.modal--pokemon-info .pkStatBtn{position:absolute;right:8px;top:6px;width:30px;height:30px;display:grid;place-items:center;border:1px solid #e6eafe;border-radius:8px;background:#fff;color:#5c6b8a;cursor:pointer}\
          .Modal.modal--pokemon-info .pkMini{position:absolute;right:8px;top:42px;min-width:260px;max-width:320px;padding:10px 12px;border:1px solid #252b3a;border-radius:12px;background:#11141b;color:#eef3ff;box-shadow:0 16px 34px rgba(0,0,0,.35);display:none;z-index:60}\
          .Modal.modal--pokemon-info .pkMini .row{display:flex;justify-content:space-between;padding:6px 2px;border-bottom:1px dashed rgba(255,255,255,.08)}\
          .Modal.modal--pokemon-info .pkMini .row:last-child{border:0}.Modal.modal--pokemon-info .pkMini .k{opacity:.78}\
        </style>').appendTo('head');
      }

      // добавляем HTML карточки из бэка
      const $wrap = $('<div/>', { "class": "Pokemons", html: d.html }).appendTo($modal);
      const $info = $wrap.find('.Right .Info').first();

      // —— утилиты
      const findRowStarts = (label) =>
        $info.find('*').filter(function () { return $(this).text().trim().indexOf(label) === 0; }).first();
      const getLine = (label) => {
        let v = '';
        $info.find('*').each(function () {
          if (v) return;
          const t = $(this).text().trim();
          if (t.indexOf(label) === 0) v = t.replace(label, '').trim();
        });
        return v;
      };

      // Убираем «i» возле характера и делаем «чистый» шаг
      const $charOrig = findRowStarts('Характер:');
      const charVal = getLine('Характер:');
      if ($charOrig.length) { $charOrig.find('i').remove(); }
      if (charVal) {
        $('<div class="Step pure"><div class="Label">Характер:</div><div class="Other">' + charVal + '</div></div>')
          .insertBefore($charOrig);
        $charOrig.hide();
      }

      // Способность: переносим значение, сохраняя кликабельность/onclick из исходного HTML
      const $abilOrig = findRowStarts('Способность:');
      if ($abilOrig.length) {
        const $clone = $abilOrig.clone(true);
        // вычищаем метку «Способность:» в клоне
        $clone.contents().each(function () {
          if (this.nodeType === 3) {
            this.nodeValue = (this.nodeValue || '').replace(/^(\s*Способность:\s*)/, '');
          } else {
            const $el = $(this);
            const txt = $el.text().trim();
            if (txt.indexOf('Способность:') === 0) {
              const rest = txt.replace(/^Способность:\s*/, '');
              if (rest) { $el.text(rest); } else { $el.remove(); }
            }
          }
        });
        const abilityInner = $('<div>').append($clone.contents()).html().trim();
        const $row = $('<div class="Step pure"><div class="Label">Способность:</div><div class="Other"></div></div>');
        $row.find('.Other').html(abilityInner);
        $row.find('a,button,[onclick]').first().addClass('AbilityLink');
        $row.insertBefore($abilOrig);
        $abilOrig.hide();
      }

      // Генокод
      const $geneOrig = findRowStarts('Генокод:');
      const geneVal = getLine('Генокод:');
      if ($geneOrig.length && geneVal) {
        $('<div class="Step pure"><div class="Label">Генокод:</div><div class="Other">' + geneVal + '</div></div>')
          .insertBefore($geneOrig);
        $geneOrig.hide();
      }

      // Группа привлекательности — под Генокодом
      const $genderStep = $info.find('.Step.genderPok');
      if ($genderStep.length) {
        const val = $genderStep.find('.Other').text().trim() || '—';
        const $row = $('<div class="Step pure"><div class="Label">Группа привлекательности:</div><div class="Other">' + val + '</div></div>');
        const $geneRowNew = $info.find('.Step.pure .Label').filter(function () { return $(this).text() === 'Генокод:'; }).first().parent();
        if ($geneRowNew.length) $row.insertAfter($geneRowNew);
        $genderStep.hide();
      }

      // Разведение (в основную карточку)
      const breedOk = Number(d.sparka || 0) === 0;
      const $breedRow = $('<div class="Step pure"><div class="Label">Разведение:</div><div class="Other ' + (breedOk ? 'Green-Color' : 'Red-Color') + '">' + (breedOk ? 'доступно' : 'недоступно') + '</div></div>');
      const $afterGender = $info.find('.Step.pure .Label').filter(function () { return $(this).text() === 'Группа привлекательности:'; }).first().parent();
      if ($afterGender.length) $breedRow.insertAfter($afterGender);

      // Свободные EV — под Разведением; берём из шага или из JSON
      let evFree = ($info.find('.Step.evPok .Other').text().trim()) || (typeof d.ev !== 'undefined' ? String(d.ev) : '0');
      $info.find('.Step.evPok').hide();
      $info.find('*').filter(function () { return /^Свободн(ые|ых)\s*EV/i.test($(this).text().trim()); }).hide();
      $('<div class="Step pure"><div class="Label">Свободные EV:</div><div class="Other">' + (evFree || '0') + '</div></div>').insertAfter($breedRow);

      // Витамины — ТОЛЬКО в статистику
      let vitaminsText = '';
      const $vitStep = $info.find('.Step.vitaminesPok');
      if ($vitStep.length) { vitaminsText = $vitStep.find('.Other').text().trim(); $vitStep.hide(); }
      $info.find('*').filter(function () { return $(this).text().trim().indexOf('Витамины:') === 0; }).hide();

      // Пойман/Потенциал — только в статистику
      const caught = getLine('Пойман:') || (d.birthday ? (d.birthday.date + 'г. тренером ' + d.birthday.user) : 'Неизвестно');
      const potential = getLine('Потенциал:') || (d.potential || '—');
      $info.find('*').filter(function () {
        const t = $(this).text().trim();
        return t.indexOf('Пойман:') === 0 || t.indexOf('Потенциал:') === 0;
      }).hide();

      // Кнопка статистики + мини-панель
      const $btn = $('<button type="button" class="pkStatBtn staticPok" aria-label="Статистика"><i class="fal fa-chart-line"></i></button>');
      const $mini = $('<div class="pkMini"></div>');
      $info.append($btn, $mini);

      const st0 = +d.st0 || 0, st2 = +d.st2 || 0, st3 = +d.st3 || 0;
      const tasty = [d.hidden, d.st8].filter(Boolean).join(' ').trim();

      $mini.html([
        '<div class="row"><div class="k">Пойман</div><div>' + caught + '</div></div>',
        '<div class="row"><div class="k">Потенциал</div><div>' + potential + '</div></div>',
        (vitaminsText ? '<div class="row"><div class="k">Витамины</div><div>' + vitaminsText + '</div></div>' : ''),
        '<div class="row"><div class="k">Разведение</div><div>' + (breedOk ? 'доступно' : 'недоступно') + '</div></div>',
        '<div class="row"><div class="k">Шоколадная конфета</div><div>' + (st0 ? 'использована' : 'не использована') + '</div></div>',
        '<div class="row"><div class="k">Сладкий кекс</div><div>' + (st2 ? 'использован' : 'не использован') + '</div></div>',
        '<div class="row"><div class="k">Корень априкорна</div><div>' + (st3 ? 'использован' : 'не использован') + '</div></div>',
        (tasty ? '<div class="row"><div class="k">Тренировки</div><div>' + tasty + '</div></div>' : '')
      ].join(''));

      $btn.on('click', function (ev) { ev.stopPropagation(); $mini.toggle(); });
      $(document).off('click.pkMiniHide').on('click.pkMiniHide', function () { $mini.hide(); });

      // Тултипы
      try {
        if (window.Tipped && typeof Tipped.create === 'function') {
          // сносим старые (как было)
          try {
            Tipped.remove('.staticPok'); Tipped.remove('.genderPok'); Tipped.remove('.idPok'); Tipped.remove('.vitaminesPok'); Tipped.remove('.evPok');
          } catch (_) {}

          // сладости — на кнопку статистики (как и раньше делали)
          Tipped.create($btn.get(0),
            'Шоколадная конфета <span class="' + (st0 === 0 ? 'Green' : 'Red') + '-Color">' + (st0 === 0 ? 'не использована' : 'использована') + '</span><br>' +
            'Сладкий кекс <span class="' + (st2 === 0 ? 'Green' : 'Red') + '-Color">' + (st2 === 0 ? 'не использован' : 'использован') + '</span><br>' +
            'Корень априкорна <span class="' + (st3 === 0 ? 'Green' : 'Red') + '-Color">' + (st3 === 0 ? 'не использован' : 'использован') + '</span><br>' +
            (d.hidden || '') + ' ' + (d.st8 || '')
          );

          // id/trade
          const idNode = $wrap.find('.Id').get(0);
          if (idNode) Tipped.create(idNode, ' ' + (d.trade == "false" ? 'Покемон приручен' : 'Покемон не приручен') + ' ');

          // HP/EXP
          const hpNode = $wrap.find('.hp_proggresbar').get(0);
          const expNode = $wrap.find('.exp_progressbar').get(0);
          if (hpNode) Tipped.create(hpNode);
          if (expNode) Tipped.create(expNode);
        }
      } catch (_) {}
    }
  });
}

function searchDex(pok) {
    // Берём значение из инпута, если аргумент не передан
    if (typeof pok === 'undefined' || pok === null) {
        pok = $('#inputDexSearch').val();
    }
    // Проверка на число (поддержка строк с ведущими/конечными пробелами)
    if (pok && !isNaN(Number(pok)) && Number.isFinite(Number(pok))) {
        openDex(Number(pok));
    } else if (pok && pok.length > 0) {
        $.ajax({
            url: "/do/pp",
            type: "POST",
            data: {
                id: 'pokedex',
                type: 'search',
                pok: pok
            },
            beforeSend: function() {
                $('.DexListPok').html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');
            },
            success: function(response) {
                if (typeof response === "string") {
                    try {
                        response = (typeof response === 'string') ? JSON.parse(response) : response;
                    } catch (e) {
                        $('.DexListPok').html('<div class="txtcnt">Ошибка загрузки поиска</div>');
                        return;
                    }
                }
                $('.DexListPok').html('');
                if (response && response['pok_list'] && response['pok_list'].length > 0) {
                    $.each(response['pok_list'], function(x, y) {
                        $('<div />').append(
                            $('<span />', {
                                "class": 'bgPok',
                                "html": '<img src="/img/pokemons/animation/' + y['num'] + '.png"> #' + y['basenum'] + ' ' + y['name'],
                                "click": function() {
                                    openDex(y['num']);
                                }
                            })
                        ).appendTo('.DexListPok');
                    });
                } else {
                    $('.DexListPok').html('<div class="txtcnt">Покемон не найден</div>');
                }
            },
            error: function() {
                $('.DexListPok').html('<div class="txtcnt">Ошибка соединения с сервером</div>');
            }
        });
    } else {
        $('.DexListPok').html('<div class="txtcnt">Введите имя или номер покемона</div>');
    }
}

function formDex(pok) {
    // Проверка на валидность pok
    if (!pok) {
        $('.DexListPok').html('<div class="txtcnt">Некорректный номер покемона</div>');
        return;
    }
    $.ajax({
        url: "/do/pp",
        type: "POST",
        data: {
            id: 'pokedex',
            type: 'form',
            pok: pok
        },
        beforeSend: function() {
            $('.DexListPok').html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');
        },
        success: function(response) {
            if (typeof response === "string") {
                try {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (e) {
                    $('.DexListPok').html('<div class="txtcnt">Ошибка загрузки форм</div>');
                    return;
                }
            }
            if (response && response['html']) {
                $('.DexListPok').html(response['html']);
            } else {
                $('.DexListPok').html('<div class="txtcnt">Формы не найдены</div>');
            }
        },
        error: function() {
            $('.DexListPok').html('<div class="txtcnt">Ошибка соединения с сервером</div>');
        }
    });
}

function colizeum(){
				$.ajax({
					url: "/do/colizeum",
					type: "POST",
					data: "type=open",
					success: function(response) {
					    $(".model").remove();
						response = (typeof response === 'string') ? JSON.parse(response) : response;
						$('<div />', {
                            "class": 'model',
                            "html": function() {
                                $('<div />', {
                                    "class": 'header',
                                    "html": 'Система колизея <span onclick="$(&quot;.model&quot;).remove();"><i class="fas fa-times"></i></span>'
                                }).appendTo(this);
                                $('<div />', {
                                    "class": 'content-model',
                                    "html": response['html']
                                }).appendTo(this);
                            }
                        }).appendTo('body');
                        $(".model").css("position", "relative");
                        $('.model').draggabilly({
        					handle: '.header',
        					containment: true
        				});
					}
				});

}

function itemOpenMore(id){
    var uscont = $('.MoreItems'+id).html();

if (uscont !== "") {
  $('.MoreItems'+id).html('');
} else {
				$.ajax({
					url: "/do/pp",
					type: "POST",
					data: "itemsplash="+id,
					success: function(response) {
						response = (typeof response === 'string') ? JSON.parse(response) : response;
						$('.MoreItems'+id).html(response['html']);
					}
				});
}

}
class PokedexWindow {
  static show(i = 0, form = 0) {
    // Скрыть тултипы, если есть
    if (typeof $(".tooltip").hide === "function") {
      $(".tooltip").hide();
    }

    // Создаем или получаем контейнер для покедекса
    let $modal = $('.pokedex-modal');
    if ($modal.length === 0) {
      $modal = $('<div/>', { class: 'pokedex-modal', id: 'pokedex' }).appendTo('body');
    } else {
      $modal.empty(); // очищаем, чтобы не дублировать кнопку
    }

    // Кнопка закрытия
    const $closeBtn = $('<button/>', {
      class: 'pokedex-close-btn',
      html: '&times;',
      click: function() {
        $modal.hide();
      },
      'aria-label': 'Закрыть'
    }).css({
      position: 'absolute',
      top: '18px',
      right: '18px',
      'font-size': '28px',
      color: '#a184ca',
      background: 'transparent',
      border: 'none',
      'border-radius': '7px',
      'box-shadow': 'none',
      cursor: 'pointer',
      transition: 'background 0.18s, color 0.18s'
    }).hover(function() {
      $(this).css({ background: '#ede7fa', color: '#7648b7' });
    }, function() {
      $(this).css({ background: 'transparent', color: '#a184ca' });
    });

    // Добавляем кнопку закрытия, если её ещё нет
    $modal.append($closeBtn);

    $modal.show();
    // Если нет номера покемона — показываем дефолтную страницу с фильтрами и классами
    if (!i || i == 0) {
      let html = '';
      html += '<div class="Header">Покедекс<div class="Close" onclick="PokedexWindow.close()">×</div></div>';
      html += '<div class="Content">';
      html += '<div class="Pokedex">';
      html += '<div class="DexListPok"></div>';
      html += '<div class="Dex-Inputs">';
      html += '  <div onclick="PokedexWindow.show(1)">‹</div>';
      html += '  <input type="text" id="inputDexSearch" placeholder="Найти покемона..." onkeydown="if(event.keyCode == 13){PokedexWindow.search();}">';
      html += '  <div onclick="PokedexWindow.show(1)">›</div>';
      html += '  <button class="btnResetFilters" onclick="PokedexWindow.resetFilters()" title="Сбросить фильтры">× Сброс фильтров</button>';
      html += '</div>';
      html += '<div class="txtcnt">Покедекс - универсальный помощник тренера.</div>';

      // Типы покемонов
      html += '<div class="PokedexGroup"><div class="Title">Типы покемонов</div>';
      html += '<img src="/img/world/typs/bug.png" class="typeBug" onclick="PokedexWindow.group(\'tip bug\')" data-title="tip bug">';
      html += '<img src="/img/world/typs/dark.png" class="typeDark" onclick="PokedexWindow.group(\'tip dark\')" data-title="tip dark">';
      html += '<img src="/img/world/typs/dragon.png" class="typeDragon" onclick="PokedexWindow.group(\'tip dragon\')" data-title="tip dragon">';
      html += '<img src="/img/world/typs/electric.png" class="typeElectric" onclick="PokedexWindow.group(\'tip electric\')" data-title="tip electric">';
      html += '<img src="/img/world/typs/fairy.png" class="typeFairy" onclick="PokedexWindow.group(\'tip fairy\')" data-title="tip fairy">';
      html += '<img src="/img/world/typs/fighting.png" class="typeFighting" onclick="PokedexWindow.group(\'tip fighting\')" data-title="tip fighting">';
      html += '<img src="/img/world/typs/fire.png" class="typeFire" onclick="PokedexWindow.group(\'tip fire\')" data-title="tip fire">';
      html += '<img src="/img/world/typs/fly.png" class="typeFly" onclick="PokedexWindow.group(\'tip fly\')" data-title="tip fly">';
      html += '<img src="/img/world/typs/ghost.png" class="typeGhost" onclick="PokedexWindow.group(\'tip ghost\')" data-title="tip ghost">';
      html += '<img src="/img/world/typs/grass.png" class="typeGrass" onclick="PokedexWindow.group(\'tip grass\')" data-title="tip grass">';
      html += '<img src="/img/world/typs/ground.png" class="typeGround" onclick="PokedexWindow.group(\'tip ground\')" data-title="tip ground">';
      html += '<img src="/img/world/typs/ice.png" class="typeIce" onclick="PokedexWindow.group(\'tip ice\')" data-title="tip ice">';
      html += '<img src="/img/world/typs/normal.png" class="typeNormal" onclick="PokedexWindow.group(\'tip normal\')" data-title="tip normal">';
      html += '<img src="/img/world/typs/poison.png" class="typePoison" onclick="PokedexWindow.group(\'tip poison\')" data-title="tip poison">';
      html += '<img src="/img/world/typs/psychic.png" class="typePsychic" onclick="PokedexWindow.group(\'tip psychic\')" data-title="tip psychic">';
      html += '<img src="/img/world/typs/rock.png" class="typeRock" onclick="PokedexWindow.group(\'tip rock\')" data-title="tip rock">';
      html += '<img src="/img/world/typs/steel.png" class="typeSteel" onclick="PokedexWindow.group(\'tip steel\')" data-title="tip steel">';
      html += '<img src="/img/world/typs/water.png" class="typeWater" onclick="PokedexWindow.group(\'tip water\')" data-title="tip water">';
      html += '</div>';

      // Особые классы
      html += '<div class="PokedexGroup"><div class="Title">Особые классы</div>';
      html += '<div class="uberness uberness1" onclick="PokedexWindow.group(\'class start\')" data-title="class start">Стартовый</div>';
      html += '<div class="uberness uberness2" onclick="PokedexWindow.group(\'class unique\')" data-title="class unique">Уникальный</div>';
      html += '<div class="uberness uberness3" onclick="PokedexWindow.group(\'class mythical\')" data-title="class mythical">Мифический</div>';
      html += '<div class="uberness uberness4" onclick="PokedexWindow.group(\'class legendary\')" data-title="class legendary">Легендарный</div>';
      html += '<div class="uberness uberness5" onclick="PokedexWindow.group(\'class beasts\')" data-title="class beasts">Ультра-звери</div>';
      html += '<div class="uberness uberness6" onclick="PokedexWindow.group(\'class ancient\')" data-title="class ancient">Древние</div>';
      html += '<div class="uberness uberness7" onclick="PokedexWindow.group(\'class paradox\')" data-title="class paradox">Парадоксальные</div>';
      html += '<div class="uberness uberness8" onclick="PokedexWindow.group(\'class future\')" data-title="class future">Роботизированные</div>';
      html += '</div>';

      // Поколения
      html += '<div class="PokedexGroup"><div class="Title">Поколения</div>';
      html += '<div class="generation generation1" onclick="PokedexWindow.group(\'generation one\')" data-title="generation one">I поколение</div>';
      html += '<div class="generation generation2" onclick="PokedexWindow.group(\'generation two\')" data-title="generation two">II поколение</div>';
      html += '<div class="generation generation3" onclick="PokedexWindow.group(\'generation three\')" data-title="generation three">III поколение</div>';
      html += '<div class="generation generation4" onclick="PokedexWindow.group(\'generation four\')" data-title="generation four">IV поколение</div>';
      html += '<div class="generation generation5" onclick="PokedexWindow.group(\'generation five\')" data-title="generation five">V поколение</div>';
      html += '<div class="generation generation6" onclick="PokedexWindow.group(\'generation six\')" data-title="generation six">VI поколение</div>';
      html += '<div class="generation generation7" onclick="PokedexWindow.group(\'generation seven\')" data-title="generation seven">VII поколение</div>';
      html += '<div class="generation generation8" onclick="PokedexWindow.group(\'generation eight\')" data-title="generation eight">VIII поколение</div>';
      html += '<div class="generation generation9" onclick="PokedexWindow.group(\'generation nine\')" data-title="generation nine">IX поколение</div>';
      html += '</div>';

      // Рандом
      html += '<center><div class="btnRandom" onclick="PokedexWindow.show(' + (Math.floor(Math.random() * 1008) + 1) + ')" data-title="random random">Рандомный покемон</div></center>';
      html += '</div></div></div>';
      $modal.html(html);
      PokedexWindow.initUX();

      // Подсказки для типов
      Tipped.create('.typeBug', 'Насекомый'); Tipped.create('.typeDark', 'Темный'); Tipped.create('.typeDragon', 'Дракон');
      Tipped.create('.typeElectric', 'Электрический'); Tipped.create('.typeFairy', 'Волшебный'); Tipped.create('.typeFighting', 'Боевой');
      Tipped.create('.typeFire', 'Огненный'); Tipped.create('.typeFly', 'Летающий'); Tipped.create('.typeGhost', 'Призрак');
      Tipped.create('.typeGrass', 'Травяной'); Tipped.create('.typeGround', 'Земляной'); Tipped.create('.typeIce', 'Ледяной');
      Tipped.create('.typeNormal', 'Нормальный'); Tipped.create('.typePoison', 'Ядовитый'); Tipped.create('.typePsychic', 'Психический');
      Tipped.create('.typeRock', 'Каменный'); Tipped.create('.typeSteel', 'Стальной'); Tipped.create('.typeWater', 'Водный');
      return;
    }

    // Если выбран покемон — стандартная логика
    let num = parseInt(i);
    if (num < 1) num = 1008;
    if (num > 1008) num = 1;

    // Глобальная переменная для текущего номера покемона
    window.UserPokedex = num;

    $modal.html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');

    $.ajax({
      url: "/do/Pokedex",
      type: "POST",
      data: { dex: num, form: form },
      dataType: "json",
      success: function (d) {
        let html = '';
        html += `<div class="Header">Покедекс
          <div class="Close" onclick="PokedexWindow.close()">×</div>
        </div><div class="Content">`;

        if (d.error == 0) {
          const nextId = (parseInt(d.id) + 1 > 1008) ? 1 : parseInt(d.id) + 1;
          const prevId = (parseInt(d.id) - 1 < 1) ? 1008 : parseInt(d.id) - 1;
          // ВАЖНО: data-pokid="${d.id}" для корректной работы вкладок!
          html += `<div class="Pokedex" data-pokid="${d.id}" data-prev="${prevId}" data-next="${nextId}">
            <div class="DexListPok"></div>
            <div class="Dex-Inputs">
              <div onclick="PokedexWindow.show(${prevId})">‹</div>
              <input type="text" id="inputDexSearch" placeholder="Найти покемона..." onkeydown="if(event.keyCode == 13){PokedexWindow.search();}">
              <div onclick="PokedexWindow.show(${nextId})">›</div>
              <button class="btnResetFilters" onclick="PokedexWindow.resetFilters()" title="Сбросить фильтры">× Сброс фильтров</button>
            </div>
            <div class="DexImgPokemonBox" data-type="${d.typeOne}${d.typeTwo !== 'not' ? ',' + d.typeTwo : ''}">
              <div class="DexImgPokemon">
                <div class="DexImgPokemonWarp ${d.click}">
                  <img class="pokNormalpkd" src="/img/pokemons/pokedex/${d.numb}${d.form_eng}.png" ${d.func}>
                  <div class="PokSize">
                    <div><span><i class="fal fa-weight"></i></span> ${d.weight} kg</div>
                    <div><span><i class="fal fa-arrow-square-up"></i></span> ${d.height} m</div>
                  </div>
                  <div class="PokGender">
                    <div><span><i class="fal fa-mars"></i></span> ${d.m}%</div>
                    <div><span><i class="fal fa-venus"></i></span> ${d.d}%</div>
                    ${d.form ? `<br><div class="form" id="form">${d.form}</div>` : ""}
                  </div>
                  <div class="PokUberness"> ${d.class} </div>
                  <div class="typs">
                    ${d.typeOne !== "not" ? `<img src="/img/world/typs/${d.typeOne}.png" onclick="PokedexWindow.group('tip ${d.typeOne}')" data-title="tip ${d.typeOne}">&nbsp;` : ""}
                    ${d.typeTwo !== "not" ? `<img src="/img/world/typs/${d.typeTwo}.png" onclick="PokedexWindow.group('tip ${d.typeTwo}')" data-title="tip ${d.typeTwo}">&nbsp;` : ""}
                  </div>
                </div>
              </div>
            </div>
            <div class="DexInfoPokemon">
              <div class="Name">#${d.numv} ${d.name} <button class="comparisonPok" onclick="comparison(${d.id});">${d.txt}</button> ${d.por} </div>
              <div class="Info">
                <div class="DexMetaRow" role="list" aria-label="Параметры">
                  <div class="DexMetaItem info_badge" role="listitem">
                    <i class="fal fa-badge"></i>
                    <div class="DexMetaText">
                      <div class="DexMetaLabel">Группа опыта</div>
                      <div class="DexMetaValue">${d.go}</div>
                    </div>
                  </div>
                  <div class="DexMetaItem info_star" role="listitem">
                    <i class="fal fa-star"></i>
                    <div class="DexMetaText">
                      <div class="DexMetaLabel">Категория силы</div>
                      <div class="DexMetaValue">${d.pwr}</div>
                    </div>
                  </div>
                  <div class="DexMetaItem info_effort" role="listitem">
                    <i class="fal fa-brain"></i>
                    <div class="DexMetaText">
                      <div class="DexMetaLabel">Базовый опыт</div>
                      <div class="DexMetaValue">${d.effort}</div>
                    </div>
                  </div>
                  <div class="DexMetaItem info_shield-check" role="listitem">
                    <i class="fal fa-shield-check"></i>
                    <div class="DexMetaText">
                      <div class="DexMetaLabel">Частота поимки</div>
                      <div class="DexMetaValue">${d.cc}</div>
                    </div>
                  </div>
                </div>
                <div class="DexStats" data-hp="${d.hp}" data-atk="${d.atk}" data-def="${d.def}" data-sa="${d.sa}" data-sd="${d.sd}" data-sp="${d.sp}" data-total="${d.total}">
                  <div class="DexStatsTop">
                    <div class="DexStatsTitle">Статы</div>
                    <div class="DexStatsSwitcher" role="tablist" aria-label="Модель статов">
                      <button type="button" class="DexStatViewBtn is-active" data-view="hex">Шестигранник</button>
                      <button type="button" class="DexStatViewBtn" data-view="bars">Полосы</button>
                      <button type="button" class="DexStatViewBtn" data-view="table">Таблица</button>
                    </div>
                  </div>
                  <div class="DexStatsViews">
                    <div class="DexStatsView DexStatsView--hex is-active">
                      <div class="hexagon">
                                        ${d.por !== '' ? `<div class="polygon2" style="clip-path: polygon(${d.hpbar_left_r}px ${d.hpbar_top_r}px,${d.atkbar_left_r}px ${d.atkbar_top_r}px,${d.defbar_left_r}px ${d.defbar_top_r}px,${d.spdbar_left_r}px ${d.spdbar_top_r}px,${d.sdefbar_left_r}px ${d.sdefbar_top_r}px,${d.satkbar_left_r}px ${d.satkbar_top_r}px);"></div>` : ""}
                                        <div class="polygon" style="clip-path: polygon(${d.hpbar_left}px ${d.hpbar_top}px,${d.atkbar_left}px ${d.atkbar_top}px,${d.defbar_left}px ${d.defbar_top}px,${d.spdbar_left}px ${d.spdbar_top}px,${d.sdefbar_left}px ${d.sdefbar_top}px,${d.satkbar_left}px ${d.satkbar_top}px);"></div>
                                        <div class="label" style="top: 10px;left: 135px;">HP ${d.hp}</div>
                                        <div class="label" style="top: 65px;left: 228.5px;">A ${d.atk}</div>
                                        <div class="label" style="top: 173px;left: 228.5px;">D ${d.def}</div>
                                        <div class="label" style="top: 227px;left: 135px;">S ${d.sp}</div>
                                        <div class="label" style="top: 65px;left: 41px;">SA ${d.sa}</div>
                                        <div class="label" style="top: 173px;left: 41px;">SD ${d.sd}</div>
                                        <div class="label" style="top: 120px;left: 135px;">total ${d.total}</div>
                                      </div>
                    </div>
                    <div class="DexStatsHint">Таблица показана ниже</div>
                    <div class="DexStatsView DexStatsView--bars"></div>
                    <div class="DexStatsView DexStatsView--table"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="DivDex">
              <div class="DexCategory">
                <div class="Button active" onclick="setTabDex(this,1);">Информация</div>
                <div class="Button" onclick="setTabDex(this,2);">Уровень</div>
                <div class="Button" onclick="setTabDex(this,3);">TM/TR</div>
                <div class="Button" onclick="setTabDex(this,4);">Разведение</div>
                <div class="Button" onclick="setTabDex(this,5);">Обитание</div>
              </div>
              <div class="DexStatsBelow" aria-label="Таблица статов"></div>
              <div class="DexContent">
                <div class="DexAbout">${d.info}</div>
                <div class="DexEvolv">${d.evolutions}</div>
                <div class="DexbtnEtc">${d.btn_w}</div>
                <div class="DexEtc">
                  В игре: <b>${d.typeNormal}</b> ( шайни: <b>${d.typeUnik}</b>)
                  <br> Способности: <small>x</small>${d.a1} <small>y</small>${d.a2} <br> Скрытая способность: ${d.a3} ${d.nal} ${d.nal_w}
                </div>
              </div>
            </div>
          </div>`;
          html += '</div>';
          $modal.html(html);
          PokedexWindow.initUX();

          // Подсказки для статов
          Tipped.create('.info_badge', 'Группа опыта');
          Tipped.create('.info_star', 'Категория силы');
          Tipped.create('.info_shield-check', 'Частота поимки');
          Tipped.create('.info_effort', 'Базовый опыт');
        } else {
          // Если не найден — дефолтная страница
          PokedexWindow.show(0);
        }
      }
    });
  }

  // Группы (тип, класс, поколение)
  static group(group) {
    let $modal = $('.pokedex-modal');
    $modal.find('.Pokedex').html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');
    $.ajax({
      url: "/do/Pokedex",
      type: "POST",
      data: { group: group },
      success: function (response) {
        let resp = typeof response === 'string' ? JSON.parse(response) : response;
        $modal.find('.Pokedex').html(resp['html']);
      }
    });
  }

  // Сброс фильтров
  static resetFilters() {
    PokedexWindow.show(0);
  }

 // Поиск по имени или номеру
// Поиск по имени или номеру
static search() {
  const val = $('#inputDexSearch').val().trim();
  if (!val) return;
  $('.pokedex-modal .DexListPok').html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');
  
  // Если номер — открываем покемона
  if (/^\d+$/.test(val) && Number(val) > 0 && Number(val) <= 1008) {
    PokedexWindow.show(Number(val));
    return;
  }

  // Ищем по имени (регистр не важен)
  $.ajax({
    url: "/do/pp",
    type: "POST",
    data: {
      id: 'pokedex',
      type: 'search',
      pok: val // <-- ВАЖНО: именно pok!
    },
    success: function(response) {
      if (typeof response === "string") response = (typeof response === 'string') ? JSON.parse(response) : response;
      $('.pokedex-modal .DexListPok').html('');
      if (response && response['pok_list'] && Object.keys(response['pok_list']).length > 0) {
        $.each(response['pok_list'], function (x, y) {
          $('<div />').append(
            $('<span />', {
              "class": 'bgPok',
              "html": '<img src="/img/pokemons/animation/' + y['num'] + '.png"> #' + y['basenum'] + ' ' + y['name'],
              "click": function () {
                PokedexWindow.show(y['num']);
              }
            })
          ).appendTo('.pokedex-modal .DexListPok');
        });
      } else {
        $('.pokedex-modal .DexListPok').html('<div class="txtcnt">Покемон не найден</div>');
      }
    }
  });
}

  // Инициализация улучшенного UX (мобильная навигация, переключатель статов, хоткеи)
  static initUX() {
    const $modal = $('.pokedex-modal');
    if (!$modal.length) return;

    PokedexWindow._applyViewportClass();
    PokedexWindow._bindViewportResize();

    // Не множим обработчики
    PokedexWindow.initHotkeys();
    PokedexWindow.initSwipeNav();
    PokedexWindow.initStatsView();
    PokedexWindow.enhanceMovesLayout();

    // Автофокус на поиск (если пользователь не кликнул по другому полю)
    const $search = $modal.find('#inputDexSearch');
    if ($search.length && !/^(input|textarea|select)$/i.test(document.activeElement?.tagName || '')) {
      setTimeout(function () {
        try { $search[0].focus(); } catch (e) {}
      }, 0);
    }
  }

  static initStatsView() {
    const $wrap = $('.pokedex-modal .DexStats');
    if (!$wrap.length) return;

    const stats = PokedexWindow._getStats($wrap);

    const $bars = $wrap.find('.DexStatsView--bars');
    if ($bars.length && $bars.is(':empty')) {
      $bars.html(PokedexWindow._renderStatsBars(stats));
    }

    const $table = $wrap.find('.DexStatsView--table');
    if ($table.length && $table.is(':empty')) {
      $table.html(PokedexWindow._renderStatsTable(stats));
    }

    // Восстанавливаем последний выбранный вид
    let view = 'hex';
    try {
      const saved = localStorage.getItem('dexStatsView');
      if (saved) view = saved;
    } catch (e) {}

    PokedexWindow.setStatsView(view);

    $wrap.find('.DexStatViewBtn').off('click.dexstats').on('click.dexstats', function () {
      const v = $(this).data('view');
      PokedexWindow.setStatsView(v);
    });
  }


  static enhanceMovesLayout() {
    const $modal = $('.pokedex-modal');
    if (!$modal.length) return;

    // DexContent: если там список атак/ТМ в виде .Move — включаем сетку (моб. будет в 2 колонки)
    $modal.find('.DexContent').each(function () {
      const $c = $(this);
      const directMoves = $c.children('.Move').length;
      const anyMoves = $c.find('> .Move').length;
      if ((directMoves + anyMoves) > 0) {
        $c.addClass('DexContent--moves');
      } else {
        $c.removeClass('DexContent--moves');
      }
    });

    // DexAtk: в некоторых вкладках атаки рендерятся сюда
    $modal.find('.DexAtk').each(function () {
      const $c = $(this);
      const hasMoves = $c.find('> .Move').length > 0 || $c.children('.Move').length > 0;
      if (hasMoves) $c.addClass('DexAtk--moves'); else $c.removeClass('DexAtk--moves');
    });
    // Универсально: если .Move обёрнуты контейнерами — помечаем их родителя как DexMovesGrid
    // (на мобилке будет 2 колонки; на десктопе — аккуратный flex-wrap)
    $modal.find('.DexMovesGrid').removeClass('DexMovesGrid');
    const parents = $modal.find('.DexContent .Move, .DexAtk .Move').map(function () {
      return this && this.parentElement ? this.parentElement : null;
    }).get().filter(Boolean);

    // Уникальные родители
    const uniq = Array.from(new Set(parents));
    uniq.forEach(function (el) {
      const $p = $(el);
      if ($p.children('.Move').length >= 2) {
        $p.addClass('DexMovesGrid');
      }
    });

  }

  static setStatsView(view) {
    const allowed = { hex: 1, bars: 1, table: 1 };
    if (!allowed[view]) view = 'hex';

    const $modal = $('.pokedex-modal');
    const $wrap = $modal.find('.DexStats');
    if (!$wrap.length) return;

    PokedexWindow._applyViewportClass();

    $wrap.attr('data-view', view);
    $wrap.find('.DexStatViewBtn')
      .removeClass('is-active')
      .attr('aria-selected', 'false')
      .filter('[data-view="' + view + '"]')
      .addClass('is-active')
      .attr('aria-selected', 'true');

    // В v2.7 таблица не переносится ниже — но оставляем вызов на случай старых состояний DOM.
    PokedexWindow._syncTablePlacement(view);

    // Скрываем/показываем только внутри карточки статов, чтобы не ловить “дубли”.
    $wrap.find('.DexStatsView').removeClass('is-active').hide();
    $wrap.find('.DexStatsView--' + view).addClass('is-active').show();

    // Подсказка "Таблица ниже" больше не используется.
    $modal.find('.DexStatsBelow').hide();
    $modal.find('.DexStatsHint').hide();

    try { localStorage.setItem('dexStatsView', view); } catch (e) {}
  }

  static _applyViewportClass() {
    const $modal = $('.pokedex-modal');
    if (!$modal.length) return;
    const isDesktop = window.matchMedia && window.matchMedia('(min-width: 901px)').matches;
    $modal.toggleClass('dex-desktop', !!isDesktop);
    $modal.toggleClass('dex-mobile', !isDesktop);
  }

  static _bindViewportResize() {
    // Один обработчик на окно: обновляет классы и позицию таблицы при смене размеров.
    $(window).off('resize.dexViewport').on('resize.dexViewport', function () {
      PokedexWindow._applyViewportClass();
      const $wrap = $('.pokedex-modal .DexStats');
      const view = $wrap.attr('data-view') || 'hex';
      PokedexWindow._syncTablePlacement(view);
      // Пере-применим видимость контейнеров (особенно при переходе desktop <-> mobile)
      const $modal = $('.pokedex-modal');
      const isDesktop = $modal.hasClass('dex-desktop');
      $modal.find('.DexStatsBelow').toggle(view === 'table' && isDesktop);
      $modal.find('.DexStatsHint').toggle(view === 'table' && isDesktop);
    });
  }

  static _syncTablePlacement(view) {
    // v2.7: таблица всегда остаётся внутри карточки статов.
    // Этот метод оставлен для совместимости (старые вызовы), но больше ничего не переносит.
    const $modal = $('.pokedex-modal');
    if (!$modal.length) return;

    const $wrap = $modal.find('.DexStats');
    const $views = $wrap.find('.DexStatsViews');
    const $table = $wrap.find('.DexStatsView--table');

    if (!$views.length || !$table.length) return;

    // Если по какой-то причине таблица была перенесена наружу, возвращаем обратно.
    if ($table.parent()[0] !== $views[0]) {
      $table.detach().appendTo($views);
    }
  }

  static _getStats($wrap) {
    const toInt = (v) => {
      const n = parseInt(v, 10);
      return isNaN(n) ? 0 : n;
    };
    return {
      hp: toInt($wrap.data('hp')),
      atk: toInt($wrap.data('atk')),
      def: toInt($wrap.data('def')),
      sa: toInt($wrap.data('sa')),
      sd: toInt($wrap.data('sd')),
      sp: toInt($wrap.data('sp')),
      total: toInt($wrap.data('total'))
    };
  }

  static _clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  static _renderStatsBars(s) {
    const maxStat = 255;
    const maxTotal = 780;
    const rows = [
      { k: 'hp', name: 'HP', max: maxStat },
      { k: 'atk', name: 'ATK', max: maxStat },
      { k: 'def', name: 'DEF', max: maxStat },
      { k: 'sa', name: 'SpA', max: maxStat },
      { k: 'sd', name: 'SpD', max: maxStat },
      { k: 'sp', name: 'SPE', max: maxStat },
      { k: 'total', name: 'TOTAL', max: maxTotal }
    ];

    let html = '<div class="DexStatBars">';
    for (let i = 0; i < rows.length; i++) {
      const r = rows[i];
      const val = s[r.k] || 0;
      const pct = PokedexWindow._clamp(Math.round((val / r.max) * 100), 0, 100);
      html += '<div class="DexStatRow" data-stat="' + r.k + '">' +
        '<div class="DexStatName">' + r.name + '</div>' +
        '<div class="DexStatValue">' + val + '</div>' +
        '<div class="DexStatBar" aria-label="' + r.name + '" role="progressbar" aria-valuenow="' + val + '" aria-valuemin="0" aria-valuemax="' + r.max + '">' +
          '<div class="DexStatBarFill" style="width:' + pct + '%"></div>' +
        '</div>' +
      '</div>';
    }
    html += '</div>';
    return html;
  }

  static _renderStatsTable(s) {
    // v2.7: оборачиваем таблицу в скроллируемый контейнер, чтобы она была компактнее
    // и не “толкала” остальную разметку.
    return '' +
      '<div class="DexStatTableWrap" aria-label="Таблица статов">' +
        '<table class="DexStatTable">' +
          '<thead><tr><th>Стат</th><th>Значение</th></tr></thead>' +
          '<tbody>' +
            '<tr><td>HP</td><td>' + s.hp + '</td></tr>' +
            '<tr><td>ATK</td><td>' + s.atk + '</td></tr>' +
            '<tr><td>DEF</td><td>' + s.def + '</td></tr>' +
            '<tr><td>SpA</td><td>' + s.sa + '</td></tr>' +
            '<tr><td>SpD</td><td>' + s.sd + '</td></tr>' +
            '<tr><td>SPE</td><td>' + s.sp + '</td></tr>' +
            '<tr class="is-total"><td>TOTAL</td><td>' + s.total + '</td></tr>' +
          '</tbody>' +
        '</table>' +
      '</div>';
  }

  static initSwipeNav() {
    const $modal = $('.pokedex-modal');
    if (!$modal.length) return;

    let startX = 0, startY = 0, startTime = 0, targetIsField = false;

    $modal.off('touchstart.dexswipe touchend.dexswipe');
    $modal.on('touchstart.dexswipe', function (e) {
      const t = e.originalEvent && e.originalEvent.touches ? e.originalEvent.touches[0] : null;
      if (!t) return;
      startX = t.clientX;
      startY = t.clientY;
      startTime = Date.now();

      const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
      targetIsField = (tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button');
    });

    $modal.on('touchend.dexswipe', function (e) {
      if (targetIsField) return;
      const t = e.originalEvent && e.originalEvent.changedTouches ? e.originalEvent.changedTouches[0] : null;
      if (!t) return;

      const dx = t.clientX - startX;
      const dy = t.clientY - startY;
      const dt = Date.now() - startTime;

      // Игнорируем медленные/вертикальные жесты и прокрутку
      if (dt > 800) return;
      if (Math.abs(dx) < 70) return;
      if (Math.abs(dx) < Math.abs(dy) * 1.5) return;

      const $p = $modal.find('.Pokedex[data-pokid]');
      const nextId = parseInt($p.data('next'), 10);
      const prevId = parseInt($p.data('prev'), 10);
      if (!nextId || !prevId) return;

      if (dx < 0) {
        // свайп влево — следующий
        PokedexWindow.show(nextId);
      } else {
        // свайп вправо — предыдущий
        PokedexWindow.show(prevId);
      }
    });
  }

  static initHotkeys() {
    $(document).off('keydown.dexhotkeys').on('keydown.dexhotkeys', function (e) {
      const $modal = $('.pokedex-modal:visible');
      if (!$modal.length) return;

      // если фокус в поле ввода — не перехватываем стрелки
      const tag = (document.activeElement && document.activeElement.tagName) ? document.activeElement.tagName.toLowerCase() : '';
      const isField = (tag === 'input' || tag === 'textarea' || tag === 'select');

      if (e.key === 'Escape') {
        e.preventDefault();
        PokedexWindow.close();
        return;
      }

      if (!isField && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
        const $p = $modal.find('.Pokedex[data-pokid]');
        const nextId = parseInt($p.data('next'), 10);
        const prevId = parseInt($p.data('prev'), 10);
        if (!nextId || !prevId) return;

        e.preventDefault();
        if (e.key === 'ArrowRight') PokedexWindow.show(nextId);
        else PokedexWindow.show(prevId);
      }
    });
  }

  // Закрытие окна
  static close() {
    $('.pokedex-modal').fadeOut(120, function () { $(this).remove(); });
  }
}

// Глобальный вызов для совместимости (например, для старых обработчиков)
function openDex(i, form = 0) {
  PokedexWindow.show(i, form);
}

// Исправленная функция для выбора вкладки с учетом текущего покемона
function setTabDex(e, i) {
  // Получаем id покемона из data-pokid ближайшего контейнера .Pokedex
  var pok = $(e).closest('.Pokedex').data('pokid') || 1;
  // Получаем форму, если есть
  var form = $('#form').length ? $('#form').html() : '';
  var dop = form || 0;

  $.ajax({
      url: "/do/Pokedex",
      type: "POST",
      data: { setTab: i, pok: pok, form: dop },
      beforeSend: function() {
          $(".DexContent").html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');
      },
      success: function(response) {
          if (typeof response === "string") {
              try {
                  response = (typeof response === 'string') ? JSON.parse(response) : response;
              } catch (err) {
                  $(".DexContent").html('<div class="txtcnt">Ошибка загрузки данных</div>');
                  return;
              }
          }
          $(e).siblings('.Button').removeClass("active");
          $(e).addClass("active");
          if(response && response.html) {
              $(".DexContent").html(response.html);
          
              try { if (typeof PokedexWindow !== "undefined" && PokedexWindow.initUX) PokedexWindow.initUX(); } catch(e) {}
} else {
              $(".DexContent").html('<div class="txtcnt">Нет данных для вкладки</div>');
          }
      },
      error: function() {
          $(".DexContent").html('<div class="txtcnt">Ошибка соединения с сервером</div>');
      }
  });
}
var md5=new function(){var l="length",h=["0123456789abcdef",15,128,65535,1732584193,4023233417,2562383102,271733878],x=[[0,1,[7,12,17,22]],[1,5,[5,9,14,20]],[5,3,[4,11,16,23]],[0,7,[6,10,15,21]]],A=function(n,r,t){return(n>>16)+(r>>16)+((t=(n&h[3])+(r&h[3]))>>16)<<16|t&h[3]},B=function(n){for(var r=1+(n[l]+8>>6),t=new Array(1+16*r).join("0").split(""),u=0;u<n[l];u++)t[u>>2]|=n.charCodeAt(u)<<u%4*8;return t[u>>2]|=h[2]<<u%4*8,t[16*r-2]=8*n[l],t},R=function(n,r){return n<<r|n>>>32-r},C=function(n,r,t,u,o,f){return A(R(A(A(r,n),A(u,f)),o),t)},F=function(n,r,t,u,o,f,i){return C(r&t|~r&u,n,r,o,f,i)},G=function(n,r,t,u,o,f,i){return C(r&u|t&~u,n,r,o,f,i)},H=function(n,r,t,u,o,f,i){return C(r^t^u,n,r,o,f,i)},I=function(n,r,t,u,o,f,i){return C(t^(r|~u),n,r,o,f,i)},_=[function(n,r,t,u,o,f,i){return C(r&t|~r&u,n,r,o,f,i)},function(n,r,t,u,o,f,i){return C(r&u|t&~u,n,r,o,f,i)},function(n,r,t,u,o,f,i){return C(r^t^u,n,r,o,f,i)},function(n,r,t,u,o,f,i){return C(t^(r|~u),n,r,o,f,i)}],S=function(){with(Math)for(var i=0,a=[],x=pow(2,32);i<64;a[i]=floor(abs(sin(++i))*x));return a}(),X=function(n){for(var r=0,t="";r<4;r++)t+=h[0].charAt(n>>8*r+4&h[1])+h[0].charAt(n>>8*r&h[1]);return t};return function(n){for(var r,t,u,o=B(""+n),f=[0,1,2,3],i=[0,3,2,1],c=[h[4],h[5],h[6],h[7]],e=0,a=0,C=[].concat(c);e<o[l];e+=16,C=[].concat(c),a=0){for(r=0;r<4;r++)for(t=0;t<4;t++)for(u=0;u<4;u++,f.unshift(f.pop()))c[i[u]]=_[r](c[f[0]],c[f[1]],c[f[2]],c[f[3]],o[e+((4*t+u)*x[r][1]+x[r][0])%16],x[r][2][u],S[a++]);for(r=0;r<4;r++)c[r]=A(c[r],C[r])}return X(c[0])+X(c[1])+X(c[2])+X(c[3])}};

function groupDex(e) {
    // Получаем значение фильтра из data-title
    var tip = typeof e === "string" ? e : $(e).attr("data-title");
    // Если это кнопка или элемент, берём data-title как строку, иначе напрямую как строку

    $.ajax({
        url: "/do/Pokedex",
        type: "POST",
        data: { group: tip },
        beforeSend: function() {
            // Поиск .Pokedex в любом окне (универсально)
            $(".Pokedex").html('<center>' + (typeof mainLoader !== "undefined" ? mainLoader : "Загрузка...") + '</center>');
        },
        success: function(response) {
            // Если ответ строка — парсим, если уже объект — используем как есть
            if (typeof response === "string") {
                try {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (e) {
                    $(".Pokedex").html('<div class="txtcnt">Ошибка загрузки данных</div>');
                    return;
                }
            }
            if (response && response.html) {
                $(".Pokedex").html(response.html);
                if (typeof PokedexWindow !== 'undefined') { PokedexWindow.initUX(); }
            } else {
                $(".Pokedex").html('<div class="txtcnt">Нет данных для выбранной группы</div>');
            }
        },
        error: function() {
            $(".Pokedex").html('<div class="txtcnt">Ошибка соединения с сервером</div>');
        }
    });
}
function wishuserpok(pok){
    $.ajax({
        url: "/do/trainers",
        type: "POST",
        data: "type=wish&pok="+pok,
        success: function(response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            openDex(pok);
            Game.notifications.main(response['text'], 'success');
        }
    })
}
function select_attack_tmcrafting(pok,atk){
    $.ajax({
        url: "/do/tm_crafting",
        type: "POST",
        data: "type=attack&pok="+pok+"&atk="+atk,
        success: function(response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            Game.notifications.main(response['text'], response['error']);
            if(response['error'] == 'success'){
                $(".tooltip").html("").hide();
                openModal('crafttm');
            }
        }
    })
}
function create_attack_tmcrafting(atk){
    $.ajax({
        url: "/do/tm_crafting",
        type: "POST",
        data: "type=create&atk="+atk,
        success: function(response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            Game.notifications.main(response['text'], response['error']);
            if(response['error'] == 'success'){
                openModal('crafttm');
                Game.notifications.main(response['minus'], 'minus');
            }
        }
    })
}
function give_tm(id,type){
    if(type == 1){
        if(confirm("Вы уверены, что хотите ускорить создание за 20 камней?") == true){
            $.ajax({
                url: "/do/tm_crafting",
                type: "POST",
                data: "type=give&id="+id+"&class="+type,
                success: function(response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    Game.notifications.main(response['text'], response['error']);
                    if(response['error'] == 'success'){
                        openModal('crafttm');
                        Game.notifications.main(response['minus'], 'minus');
                        Game.notifications.main(response['plus'], 'plus');
                    }
                }
            })
        }
    }else{
        $.ajax({
                url: "/do/tm_crafting",
                type: "POST",
                data: "type=give&id="+id+"&class="+type,
                success: function(response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    Game.notifications.main(response['text'], response['error']);
                    if(response['error'] == 'success'){
                        openModal('crafttm');
                        Game.notifications.main(response['plus'], 'plus');
                    }
                }
            })
    }
}
function slot_tm(id,type){
    if(type == 1){
        if(confirm("Вы уверены, что хотите открыть слот сейчас?") == true){
            $.ajax({
                url: "/do/tm_crafting",
                type: "POST",
                data: "type=slot&id="+id+"&class="+type,
                success: function(response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    Game.notifications.main(response['text'], response['error']);
                    if(response['error'] == 'success'){
                        openModal('crafttm');
                        Game.notifications.main(response['minus'], 'minus');
                    }
                }
            })
        }
    }else{
        $.ajax({
                url: "/do/tm_crafting",
                type: "POST",
                data: "type=slot&id="+id+"&class="+type,
                success: function(response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    Game.notifications.main(response['text'], response['error']);
                    if(response['error'] == 'success'){
                        openModal('crafttm');
                    }
                }
            })
    }
}
function slot_web(id,type){
    if(type == 1){
        if(confirm("Вы уверены, что хотите открыть слот?") == true){
            $.ajax({
                url: "/do/pp",
                type: "POST",
                data: "web="+id+"&type="+type,
                success: function(response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    Game.notifications.main(response['text'], response['error']);
                    if(response['error'] == 'success'){
                        openModal('web_offline');
                        Game.notifications.main(response['minus'], 'minus');
                    }
                }
            })
        }
    }else{
        $.ajax({
                url: "/do/pp",
                type: "POST",
                data: "web="+id+"&type="+type,
                success: function(response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    Game.notifications.main(response['text'], response['error']);
                    if(response['error'] == 'success'){
                        openModal('web_offline');
                    }
                }
            })
    }
}

function byMax(e){$(".DivNpcBlock").remove(),$(".model").length&&$(".model").remove(),ClassInfo._byItemWindow(parseInt(e))}
function closeModal(){$(".Modal").remove()}
function closeLittleModal(){$(".LittleModal").remove()}
function setTab(e, t) {
    $(e).addClass("active"), t = parseInt(t);
    $.ajax({
        url: "/do/modal",
        type: "POST",
        data: {
            tab: t,
            type: "trainers"
        },
        beforeSend: function() {
            $(".Trainers .List").html('<center>' + mainLoader + '</center>')
        },
        success: function(e) {
            e = JSON.parse(e), $(".Modal").html(e.html)
        }
    })
}

function searchUser() {
    var e = $("#searchUser").val();
    $.ajax({
        url: "/do/modal",
        type: "POST",
        data: {
            tab: 9,
            type: "trainers",
            text: e
        },
        beforeSend: function() {
            $(".Trainers .List").html('<center>' + mainLoader + '</center>')
        },
        success: function(e) {
            e = JSON.parse(e), $(".Trainers .List").html(e.html)
        }
    })
}
function goPok(o){UserPokList.length;var s=UserPokList.indexOf(o),e=UserPokList[s-1]?UserPokList[s-1]:UserPokList[UserPokList.length-1],k=UserPokList[s+1]?UserPokList[s+1]:UserPokList[0];$(".prevPok").on("click",function(){Game.modals.pokemons()}),$(".nextPok").on("click",function(){Game.modals.pokemons()})}
function PokemonTeamOpen(){pokemon=$(".BlockOtherContent"),pokemon.removeClass("updated"),pokemon.toggle(),pokemon.html('<center>'+mainLoader+'</center>'),pokemon.load("/do/PokemonTeam")}function openCraft() {
    const craft = $(".CraftModal"); // Получение модального окна крафта
    const craftBtn = $("#CraftBtn"); // Получение кнопки крафта

    // Переключение видимости модального окна
    craft.toggle();

    // Сброс классов для кнопки
    craftBtn.attr("class", "");

    // Загрузка контента крафта с обработкой ошибок
    craft.load("/do/Craft", function(response, status, xhr) {
        if (status === "error") {
            console.error("Ошибка загрузки крафта: ", xhr.status, xhr.statusText);
            craft.html("<p>Не удалось загрузить крафт. Пожалуйста, попробуйте позже.</p>");
        }
    });
}


/* ================== CSS (компактно и адаптивно) ================== */
(function injectMOVXCSS(){
  if (document.getElementById('movx-css')) return;
  const css = `
  :root{
    --mv-bg:#111418; --mv-br:#242b35; --mv-t:#e8edf7; --mv-sub:#a9b4c7;
    --mv-chip:#2a313d; --mv-chip-br:#364152; --mv-good:#42c46a; --mv-bad:#e25555;
    --mv-blue:#2f74ff; --mv-blue-2:#0e55b6; --mv-shadow:0 18px 42px rgba(0,0,0,.35);
  }
  .movx-card{
    position:absolute; z-index:1000002; width:340px; max-width:92vw; max-height:72vh;
    background:var(--mv-bg); border:1px solid var(--mv-br); border-radius:10px;
    box-shadow:var(--mv-shadow); color:var(--mv-t); overflow:auto;
    transform:translateY(-6px) scale(.985); opacity:.0; transition:opacity .16s ease, transform .16s ease;
    font-family:Inter,Nunito,Arial,sans-serif;
  }
  .movx-card.show{opacity:1}
  .nomotion{ transition:none !important }
  .is-hidden{ display:none !important }

  .movx-head{ display:flex; align-items:center; gap:8px; padding:8px 10px; border-bottom:1px solid var(--mv-br); cursor:grab }
  .movx-ico{ width:30px; height:30px; border-radius:8px; border:1px solid var(--mv-br); background:#0b1016; display:flex; align-items:center; justify-content:center; overflow:hidden }
  .movx-ico img{ width:100%; height:100%; object-fit:contain }
  .movx-title{ flex:1; min-width:0 }
  .movx-title .ru{ font-weight:900; font-size:15px; line-height:1.1 }
  .movx-title .en{ display:block; margin-top:2px; color:var(--mv-sub); font-weight:700; font-size:12px }
  .movx-close{ appearance:none; border:0; background:transparent; cursor:pointer; color:#9aa6bd; padding:2px 6px; font-size:18px }
  .movx-close:hover{ color:#cfd7e6 }

  .movx-body{ padding:8px 10px 10px }
  .movx-chips{ display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; align-items:center }
  .movx-chip{ display:inline-flex; align-items:center; gap:6px; padding:5px 9px; border-radius:999px;
              background:var(--mv-chip); border:1px solid var(--mv-chip-br); color:#d7deea; font-weight:800; font-size:12px }
  .movx-chip.cat-phys{ background:#571e1e; border-color:#7a2a2a }
  .movx-chip.cat-spec{ background:#20395e; border-color:#30528a }
  .movx-chip.cat-stat{ background:#273a2b; border-color:#3b5536 }
  .movx-chip.badge{ background:#1e2632; border-color:#2c394d }

  .movx-i{ margin-left:auto; appearance:none; border:1px solid #2c394d; background:#1a222d; color:#cfd7e6;
           width:24px; height:24px; line-height:22px; text-align:center; border-radius:6px; font-weight:900; cursor:pointer }
  .movx-i:hover{ filter:brightness(1.06) }

  .movx-desc{ color:#d0d7e6; font-weight:700; font-size:13px; line-height:1.5; margin-bottom:8px }
  .movx-tech{ color:#94a1b8; font-weight:700; font-size:12px; line-height:1.35; margin:-2px 0 8px }
  .movx-tech.ok{ color:var(--mv-good) } .movx-tech.bad{ color:var(--mv-bad) }

  .movx-btn{ appearance:none; border:1px solid var(--mv-blue); background:linear-gradient(180deg,var(--mv-blue),var(--mv-blue-2));
             color:#fff; font-weight:900; font-size:12.5px; padding:8px 10px; border-radius:8px; width:100%;
             box-shadow:0 12px 26px rgba(46,96,220,.28); cursor:pointer; margin-bottom:6px }
  .movx-btn:disabled{ opacity:.6; cursor:not-allowed }

  /* «Кто знает?» — компактные табы */
  .movx-who{ display:flex; align-items:center; gap:8px; margin-top:6px; padding-top:6px; border-top:1px solid var(--mv-br) }
  .movx-who .ttl{ color:#a9b4c7; font-weight:800; font-size:12px; margin-right:2px }
  .movx-who .tab{ appearance:none; border:1px solid #2c394d; background:#1a222d; color:#cfd7e6;
                  font-weight:900; font-size:11.5px; padding:4px 8px; border-radius:999px; cursor:pointer }
  .movx-who .tab.active{ background:#2a3545; color:#fff; border-color:#3a4a62 }

  /* контейнер для результата из /do/issets */
  .movx-grid{ margin-top:6px; max-height:210px; overflow:auto; }
  .movx-legacy .Name{font-weight:900;font-size:13px;margin-bottom:6px;color:#e8edf7}
  .movx-legacy .About{color:#a9b4c7;font-weight:700;font-size:12px;margin-bottom:6px}
  .movx-legacy .blockpok{display:block;max-height:200px;overflow:auto}
  .movx-legacy .linepok{display:flex;align-items:center;gap:8px;padding:5px 6px;border:1px solid #1d2531;border-radius:8px;background:#0c1218;margin-bottom:6px;color:#d7deea;cursor:pointer}
  .movx-legacy .linepok img{width:26px;height:26px;image-rendering:pixelated;border-radius:6px}
  .movx-legacy .linepok:hover{filter:brightness(1.03)}

  /* chooser (список атак) */
  .movx-chooser{
    position:absolute; z-index:1000000; width:320px; max-width:92vw; max-height:50vh;
    background:#fff; border:1px solid #e6eafe; border-radius:10px; box-shadow:0 18px 42px rgba(0,0,0,.2);
    color:#1b2b4f; overflow:auto; transform:translateY(-6px) scale(.985); opacity:.0; transition:opacity .16s ease, transform .16s ease;
    font-family:Inter,Nunito,Arial,sans-serif;
  }
  .movx-chooser.show{ transform:translateY(0) scale(1); opacity:1 }
  .movx-ch-head{ display:flex; align-items:center; justify-content:space-between; padding:7px 9px; background:linear-gradient(180deg,#f9fbff,#f5f7ff); border-bottom:1px solid #e6eafe }
  .movx-ch-title{ font-weight:900; font-size:13px; color:#21345e }
  .movx-ch-close{ appearance:none; border:0; background:transparent; cursor:pointer; color:#9aa6bd; padding:2px 6px; font-size:18px }
  .movx-ch-body{ padding:7px }
  .movx-move{ display:flex; align-items:center; gap:8px; padding:8px; border:1px solid #e6eafe; border-radius:10px; background:#fbfcff; margin-bottom:7px }
  .movx-move .ico{ width:32px; height:32px; border-radius:8px; border:1px solid #e6eafe; background:#fff; display:flex; align-items:center; justify-content:center; overflow:hidden }
  .movx-move .ico img{ width:100%; height:100%; object-fit:contain }
  .movx-move .info{ flex:1 1 auto; min-width:0 }
  .movx-move .name{ font-weight:900; font-size:13px; color:#22345e }
  .movx-move .pp{ font-weight:800; font-size:11.2px; color:#6f7b95 }
  .movx-move .plus{ margin-left:auto; appearance:none; border:1px solid #cde8d5; background:#f2fff7; color:#1e7a3a; width:30px; height:30px;
                    border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:900 }
  .movx-move .plus:hover{ filter:brightness(.97) }

  @media (max-width:700px){
    .movx-card{ width:96vw; left:50% !important; transform:translate(-50%, -6px) scale(.985); }
    .movx-chooser{ width:96vw }
  }`;
  const st=document.createElement('style'); st.id='movx-css'; st.textContent=css; document.head.appendChild(st);
})();

/* ================== Утилиты ================== */
function movxEscape(s){ return String(s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m])); }

/* позиционирование без дёрганий + автозеркало вверх */
function movxPlaceNear(anchor, el, opts, isReposition){
  opts = opts || {};
  const pad=8, vw=innerWidth, vh=innerHeight;
  const rect = anchor && anchor.getBoundingClientRect ? anchor.getBoundingClientRect()
             : {left:vw/2, right:vw/2, top:vh/2, bottom:vh/2, width:0, height:0};

  el.classList.add('nomotion');
  const prevVis = el.style.visibility;
  el.style.visibility = 'hidden';
  el.style.left = '-99999px'; el.style.top = '-99999px';

  const w = el.offsetWidth || 340;
  const h = el.offsetHeight || 200;

  let left = rect.left + (opts.dx||0);
  let top  = rect.bottom + (opts.dy||8);

  if (top + h > vh - pad) top = Math.max(pad, rect.top - h - (opts.dy||8));
  if (left + w > vw - pad) left = Math.max(pad, rect.right - w);
  if (left < pad) left = pad;

  if (matchMedia('(max-width:700px)').matches){
    left = Math.max(pad, (vw - w)/2);
    let want = rect.bottom + 10;
    if (want + h > vh - pad) want = Math.max(pad, rect.top - h - 10);
    top = Math.max(pad, Math.min(want, vh - h - pad));
  }

  el.style.left = (left + pageXOffset) + 'px';
  el.style.top  = (top  + pageYOffset) + 'px';
  el.style.visibility = prevVis || 'visible';
  setTimeout(()=> el.classList.remove('nomotion'), isReposition ? 0 : 60);
}
function movxRepositionCard($dlg){ const a=$dlg&&$dlg.data('anchor'); if(a&&$dlg[0]) movxPlaceNear(a,$dlg[0],{dy:8},true); }
function movxRepositionChooser($box){ const a=$box&&$box.data('anchor'); if(a&&$box[0]) movxPlaceNear(a,$box[0],{dy:8,dx:12},true); }

function movxDraggable($dlg, $handle){
  let drag=false, dx=0, dy=0;
  $handle.on('mousedown.movxdrag touchstart.movxdrag', e=>{
    drag=true;
    const r=$dlg[0].getBoundingClientRect();
    const x=(e.touches?e.touches[0].clientX:e.clientX), y=(e.touches?e.touches[0].clientY:e.clientY);
    dx=x-r.left; dy=y-r.top;
    $(document).on('mousemove.movxdrag touchmove.movxdrag', onMove);
    $(document).on('mouseup.movxdrag touchend.movxdrag touchcancel.movxdrag', onUp);
  });
  function onMove(e){
    if(!drag) return;
    const x=(e.touches?e.touches[0].clientX:e.clientX), y=(e.touches?e.touches[0].clientY:e.clientY);
    $dlg.addClass('nomotion').css({ left:x-dx+pageXOffset, top:y-dy+pageYOffset });
  }
  function onUp(){ drag=false; $dlg.removeClass('nomotion'); $(document).off('.movxdrag'); }
}
function movxCloseCards(){ $('.movx-card').remove(); $(document).off('.movxcard'); }
function movxCloseChooser(){ $('.movx-chooser').remove(); $(document).off('.movxchooser'); }

/* очистка <font color> → аккуратный текст с цветом */
function movxTech(html){
  if(!html) return '';
  let s = String(html);
  const m = s.match(/<font[^>]*color=["']?([^"'> ]+)["']?[^>]*>([\s\S]*?)<\/font>/i);
  if (m){
    const col = String(m[1]).toLowerCase();
    const txt = m[2].replace(/<[^>]+>/g,'');
    const cls = /green|#0f|#2|lime|success/.test(col) ? 'ok' : /red|#f|error|danger/.test(col) ? 'bad' : '';
    return `<div class="movx-tech ${cls}">${movxEscape(txt)}</div>`;
  }
  s = s.replace(/<(?!br\s*\/?\s*>)[^>]+>/gi,'');
  return `<div class="movx-tech">${s}</div>`;
}

/* ====== Грузим «кто изучает» — как раньше (через /do/issets) ====== */
function movxLoadLearnersHTML(attackId, mode, $grid, $dlg){
  $grid.html('<div class="movx-tech">Загрузка…</div>');
  $.ajax({
    url: "/do/Issets",
    type: "POST",
    data: { type:"learnpok", pokID: attackId, mode: mode },
    success: function(res){
      let html = '';
      try{
        const j = (typeof res === 'string') ? JSON.parse(res) : res;
        if (j && typeof j.text === 'string')      html = j.text;
        else if (j && typeof j.html === 'string') html = j.html;
        else                                       html = String(res || '');
      }catch(_){ html = String(res || ''); }

      const $wrap = $('<div class="movx-legacy"/>').html(html);
      $grid.empty().append($wrap).data('loaded', true);
      movxRepositionCard($dlg);
    },
    error: function(){
      $grid.html('<div class="movx-tech">Не удалось загрузить список.</div>');
      movxRepositionCard($dlg);
    }
  });
}

/* ================== Карточка атаки (вместо .tooltip) ================== */
function viewDescriptionAttak(anchorEl, attackId, posSlot, pokId){
  $(".tooltip").hide().empty();
  movxCloseCards(); // список (chooser) не трогаем

  $.ajax({
    url: "/do/pokemonsAction",
    type: "POST",
    data: { pokID: attackId, type: "attackInfo" },
    success: function(resp) {
      let a = {};
      try{ a = JSON.parse(resp) || {}; }catch(_){}

      const nameRU = a.nameRus || a.name_rus || a.name || '';
      const nameEN = a.nameEng || a.name || '';
      const desc   = a.title || '';
      const type   = a.type  || 'normal';
      const pp     = +a.pp || 0;
      const pow    = +a.power || 0;
      const acc    = +a.accuracy || 0;
      const isContact = (parseInt(a.contact,10)===1);
      const key = (a.category_key||'').toLowerCase();
      const catLbl = a.category || '';
      const catCls = /phys/.test(key) ? 'cat-phys' : /spec/.test(key) ? 'cat-spec' : 'cat-stat';

      const $dlg = $(`
        <div class="movx-card" role="dialog" aria-modal="false">
          <div class="movx-head">
            <div class="movx-ico"><img src="/img/world/typs/${movxEscape(type)}.png" alt="${movxEscape(type)}"></div>
            <div class="movx-title">
              <div class="ru">${movxEscape(nameRU)}</div>
              <div class="en">${movxEscape(nameEN)}${pp?`, ${pp} PP`:''}${isContact?', контактная':''}</div>
            </div>
            <button class="movx-close" aria-label="Закрыть">×</button>
          </div>
          <div class="movx-body">
            <div class="movx-chips">
              <span class="movx-chip ${catCls}">${movxEscape(catLbl)}</span>
              ${acc?`<span class="movx-chip badge">Точность&nbsp;${acc}</span>`:''}
              ${pow?`<span class="movx-chip badge">Мощность&nbsp;${pow}</span>`:''}
              <button class="movx-i" title="Кто изучает?">i</button>
            </div>

            ${desc?`<div class="movx-desc">${movxEscape(desc)}</div>`:''}
            ${movxTech(a.tech)}

            ${posSlot ? `<button class="movx-btn" data-act="swap">${movxEscape((window.Lang&&Lang.button_attack_swap)||'Сменить атаку')}</button>` : ''}

            <div class="movx-who is-hidden">
              <span class="ttl">Кто знает?</span>
              <button class="tab active" data-learn="1">Уров.</button>
              <button class="tab" data-learn="2">TM/TR</button>
              <button class="tab" data-learn="3">Разв.</button>
              <button class="tab" data-learn="4">Обуч.</button>
            </div>
            <div class="movx-grid is-hidden"></div>
          </div>
        </div>
      `);

      $('body').append($dlg[0]);
      $dlg.data('anchor', anchorEl);
      movxPlaceNear(anchorEl, $dlg[0], { dy: 8 });
      requestAnimationFrame(()=> $dlg.addClass('show'));
      movxDraggable($dlg, $dlg.find('.movx-head'));

      $dlg.on('click', '.movx-close', movxCloseCards);
      setTimeout(()=>{
        $(document).on('mousedown.movxcard', ev=>{ if(!$(ev.target).closest('.movx-card').length) movxCloseCards(); });
        $(document).on('keydown.movxcard', ev=>{ if(ev.key==='Escape') movxCloseCards(); });
        $(window).on('resize.movxcard scroll.movxcard', ()=> movxRepositionCard($dlg));
      },0);

      // Показ «кто знает?» по клику на i
      $dlg.on('click', '.movx-i', function(){
        const $who  = $dlg.find('.movx-who');
        const $grid = $dlg.find('.movx-grid');
        const firstOpen = $grid.hasClass('is-hidden') && !$grid.data('loaded');

        $who.toggleClass('is-hidden');
        $grid.toggleClass('is-hidden');

        if (firstOpen) {
          // как раньше — через /do/issets
          movxLoadLearnersHTML(attackId, 1, $grid, $dlg);
        }
        movxRepositionCard($dlg);
      });

      // Переключение компактных кнопок «кто знает»
      $dlg.on('click','.movx-who .tab', function(){
        $dlg.find('.movx-who .tab').removeClass('active');
        $(this).addClass('active');
        const mode = +$(this).data('learn') || 1;
        const $grid = $dlg.find('.movx-grid');
        movxLoadLearnersHTML(attackId, mode, $grid, $dlg);
      });

      // «Сменить атаку» — открываем chooser, карточку закрываем
      $dlg.on('click','[data-act="swap"]', ()=> { movxCloseCards(); addAttacks(anchorEl, "open", posSlot, pokId); });
    }
  });
}

/* ================== Окно «Изучение атак» ================== */
function addAttacks(anchorEl, type, id, atkIndex){
  if (type !== 'open') {
    // Добавление — закрываем chooser в любом случае (даже если «уже изучена»)
    $.ajax({
      url: "/do/pokemonsAction",
      type: "POST",
      data: { positionAtk: anchorEl, pokID: id, attackID: atkIndex, type: type },
      success: function (response) {
        try{ response = (typeof response === 'string') ? JSON.parse(response) : response; }catch(_){}
        if (response && response['error'] == 1){
          Game.notifications.main(response['text']||'Ошибка','error');
          movxCloseChooser();
        } else {
          Game.notifications.main((response && response['text'])||'Готово','success');
          movxCloseChooser();
          movxCloseCards();
        }
        Game.modals.pokemons();
      },
      error: function(){ movxCloseChooser(); }
    });
    return;
  }

  // Открытие списка (chooser)
  movxCloseChooser();
  $.ajax({
    url: "/do/pokemonsAction",
    type: "POST",
    data: { pokID: id, attackID: atkIndex, type: type },
    success: function (response) {
      try{ response = (typeof response === 'string') ? JSON.parse(response) : response; }catch(_){}
      if (response && response['error'] == 1){
        Game.notifications.main(response['text']||'Ошибка','error'); return;
      }

      let listHTML = '';
      $.each(response['attacks'], function(x,y){
        const d = y.split(','); // 0=name,1=atkID?,2=pos,3=type,4=pp,5=category,6=attackId(real)
        const aid = d[6] ? +d[6] : +d[1];
        const name = d[0], pos=d[2], typ=d[3], pp=d[4];

        listHTML += `
          <div class="movx-move" onclick="viewDescriptionAttak(this, ${aid})">
            <div class="ico"><img src="/img/world/typs/${movxEscape(typ)}.png" alt="${movxEscape(typ)}"></div>
            <div class="info">
              <div class="name">${movxEscape(name)}</div>
              <div class="pp">${movxEscape(pp)}/${movxEscape(pp)} PP</div>
            </div>
            <button class="plus" title="Добавить"
                    onclick="event.stopPropagation(); addAttacks(${pos},'add',${id},${x})">+</button>
          </div>`;
      });

      const $box = $(`
        <div class="movx-chooser" role="dialog" aria-modal="false">
          <div class="movx-ch-head">
            <div class="movx-ch-title">${movxEscape((window.Lang && Lang.text_atk_learn) || 'Изучение атак')}</div>
            <button class="movx-ch-close" aria-label="Закрыть">×</button>
          </div>
          <div class="movx-ch-body">${listHTML || '<div style="color:#6f7b95;font-weight:800">Нет доступных атак</div>'}</div>
        </div>
      `);

      $('body').append($box);
      $box.data('anchor', anchorEl);
      movxPlaceNear(anchorEl, $box[0], { dy: 8, dx: 12 });
      requestAnimationFrame(()=> $box.addClass('show'));

      $box.on('click','.movx-ch-close', movxCloseChooser);
      setTimeout(()=>{
        $(document).on('mousedown.movxchooser', (ev)=>{ if(!$(ev.target).closest('.movx-chooser,.movx-card').length) movxCloseChooser(); });
        $(document).on('keydown.movxchooser', (ev)=>{ if(ev.key==='Escape') movxCloseChooser(); });
        $(window).on('resize.movxchooser scroll.movxchooser', ()=> movxRepositionChooser($box));
      },0);
    }
  });
}



/* ===== Isolated narrow Ability window (no overlay, z-index=1000000) ===== */
(function injectAblxCSS(){
  if (document.getElementById('ablxCssFloat')) return;
  var css = `
/* floating window (no background blur/overlay) */
.ablx-float{
  position:fixed; left:50%; top:50%; transform:translate(-50%,-50%);
  width:420px; max-width:92vw; max-height:84vh;
  background:#0f1117; border:1px solid #1f2937; border-radius:14px;
  box-shadow:0 18px 42px rgba(0,0,0,.35);
  display:none; overflow:hidden; z-index:1000000;
}

/* базовая капсула бейджа (без цветов!) */
.ablx .AbilityCard__meta .tag{
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 10px; border-radius:999px; font:800 11px/1 Inter,Arial;
  border:1px solid transparent;
}
.ablx .AbilityCard__meta .tag i{font-size:12px}

/* разновидности с цветами */
.ablx .tag--buff    { color:#67e8f9; background:#052a31; border-color:#0e4f56; }   /* Усиление — циан */
.ablx .tag--debuff  { color:#f0abfc; background:#2a0b37; border-color:#5b2a6e; }   /* Вредоносность — фиолетовый */
.ablx .tag--defense { color:#93c5fd; background:#0a2436; border-color:#103a57; }   /* Защита — синий */
.ablx .tag--heal    { color:#86efac; background:#062d19; border-color:#115e37; }   /* Лечение — зелёный */
.ablx .tag--weather { color:#fbc56c; background:#2a1a08; border-color:#5a3b12; }   /* Погода — янтарный */

.ablx-float--sm{width:360px} .ablx-float--md{width:420px} .ablx-float--lg{width:560px}
.ablx-body{padding:12px; overflow:auto; max-height:84vh; scrollbar-width:thin; scrollbar-color:#293241 transparent}
.ablx-close{position:absolute; right:8px; top:6px; background:transparent; border:0; color:#b6c2d9; cursor:pointer; font-size:18px; opacity:.85}
@media (max-width:700px){
  .ablx-float{left:50%; top:52%; transform:translate(-50%,-52%); width:92vw; max-height:88vh}
  .ablx-body{max-height:88vh; padding:10px}
}

/* scoped card styles */
.ablx .AbilityCard__title{font:900 20px/1.1 Inter,Nunito,Arial;letter-spacing:.2px;color:#e5e7eb}
.ablx .AbilityCard__meta{margin-top:4px;font:600 12.5px/1.2 Inter,Arial;color:#b6c2d9;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ablx .AbilityCard__meta .en{opacity:.9}
.ablx .AbilityCard__info{margin-left:auto;opacity:.8;cursor:pointer}
.ablx .AbilityCard__about{margin-top:10px;font:600 13.5px/1.55 Inter,Arial;color:#d8dee9}
.ablx .AbilityCard__modes{display:flex;gap:18px;margin-top:10px;border-bottom:1px solid #1f2937}
.ablx .AbilityCard__mode{padding:8px 0;font:700 13px/1 Inter;color:#9aa6bd;cursor:pointer;position:relative}
.ablx .AbilityCard__mode.is-active{color:#e5e7eb}
.ablx .AbilityCard__mode.is-active:after{content:"";position:absolute;left:0;bottom:-1px;width:100%;height:2px;background:#e5e7eb;border-radius:2px}
.ablx .AbilityTabs{display:flex;gap:18px;margin-top:8px;border-bottom:1px solid #1f2937}
.ablx .AbilityTab{padding:8px 0;font:700 13px/1 Inter;color:#9aa6bd;cursor:pointer;position:relative}
.ablx .AbilityTab.is-active{color:#e5e7eb}
.ablx .AbilityTab.is-active:after{content:"";position:absolute;left:0;bottom:-1px;width:100%;height:2px;background:#e5e7eb;border-radius:2px}

/* grid of pokemon as buttons */
.ablx .PokeGrid{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:8px;margin-top:10px}
.ablx .PokeGrid .pk{appearance:none;width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:#0b121a;
  border:1px solid #1f2937;border-radius:8px;cursor:pointer;transition:transform .08s ease, box-shadow .12s ease}
.ablx .PokeGrid .pk:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(0,0,0,.35)}
.ablx .PokeGrid .pk:focus{outline:2px solid #647eea;outline-offset:2px}
.ablx .PokeGrid .pk img{width:28px;height:28px;image-rendering:pixelated;object-fit:contain}
.ablx .AbilityCard__placeholder{color:#8fa1b7;font:600 12.5px Inter;margin:12px 0}
.ablx [data-abl-mode="desc"] .AbilityCard__lists{display:none}
.ablx [data-abl-mode="list"] .AbilityCard__about{display:none}
`;
  var s=document.createElement('style'); s.id='ablxCssFloat'; s.textContent=css; document.head.appendChild(s);
})();

/* container (single floating window) */
(function ensureAblxFloat(){
  if (!$('.ablx-float').length) {
    $('body').append(
      '<div class="ablx-float ablx-float--md" role="dialog" aria-modal="false" aria-hidden="true">'+
        '<button class="ablx-close" type="button" aria-label="Закрыть">✕</button>'+
        '<div class="ablx-body ablx"></div>'+
      '</div>'
    );
  }
})();

/* enhance: make .pk tiles clickable -> openDex(pid) */
function ablxEnhance($root){
  $root.find('.PokeGrid .pk').each(function(){
    var $el=$(this);
    if(!$el.attr('role')) $el.attr('role','button');
    if(!$el.attr('tabindex')) $el.attr('tabindex','0');
    var pid=$el.data('pid')||$el.data('num');
    if(!pid){
      var t=$el.attr('title')||''; var m=t.match(/#\s?(\d{1,4})/); if(m) pid=+m[1];
    }
    if(!pid){
      var img=$el.find('img')[0]; if(img&&img.src){ var m2=img.src.match(/\/(\d+)\.png/i); if(m2) pid=+m2[1]; }
    }
    if(pid){ $el.attr('data-pid',pid).attr('onclick','openDex('+pid+')'); }
  });
}

/* open/close floating window (no overlay) */
function ablxOpen(html, size){
  var $dlg=$('.ablx-float'), $body=$('.ablx-body');
  $dlg.removeClass('ablx-float--sm ablx-float--md ablx-float--lg').addClass('ablx-float--'+(size||'md'));
  $body.html(html); ablxEnhance($body);
  $dlg.show().attr('aria-hidden','false').focus();
}
function ablxClose(){ $('.ablx-float').hide().attr('aria-hidden','true'); }
$(document).on('click', '.ablx-close', ablxClose);

/* close on outside click (since no overlay) */
$(document).on('mousedown.ablxOutside', function(e){
  var $dlg=$('.ablx-float:visible');
  if($dlg.length && !$(e.target).closest('.ablx-float').length){ ablxClose(); }
});
$(document).on('keyup', function(e){ if(e.key==='Escape') ablxClose(); });

/* ========= PUBLIC: keep the same function name ========= */
function viewDescriptionAbility(abilityId, ev, opts){
  opts = opts || {};
  var size = opts.size || 'md'; // узкое — по умолчанию

  // cache
  viewDescriptionAbility._cache = viewDescriptionAbility._cache || new Map();
  var key = 'abl:'+abilityId;

  ablxOpen('<div class="AbilityCard__placeholder">Загрузка…</div>', size);

  if (viewDescriptionAbility._cache.has(key)){
    ablxOpen(viewDescriptionAbility._cache.get(key), size);
    return;
  }

  $.ajax({
    url: "/do/abl", type: "POST", dataType: "json", data: { abl: abilityId },
    success: function(resp){
      var html = (resp && (resp.text||resp.html)) ? (resp.text||resp.html)
               : '<div class="AbilityCard__placeholder">Способность не найдена.</div>';
      viewDescriptionAbility._cache.set(key, html);
      ablxOpen(html, size);
    },
    error: function(){
      ablxOpen('<div class="AbilityCard__placeholder">Ошибка соединения.</div>', size);
    }
  });
}

/* internal interactions inside the window */
$(document).on('click', '.ablx .AbilityCard .js-abl-mode', function(){
  var $card=$(this).closest('.AbilityCard'); var mode=$(this).data('mode');
  $card.attr('data-abl-mode', mode); $card.find('.AbilityCard__mode').removeClass('is-active'); $(this).addClass('is-active');
});
$(document).on('click', '.ablx .AbilityCard .AbilityTab', function(){
  var $wrap=$(this).closest('.AbilityCard'); var tab=$(this).data('tab');
  $wrap.find('.AbilityTab').removeClass('is-active'); $(this).addClass('is-active');
  $wrap.find('.PokeGrid[data-tab]').hide(); $wrap.find('.PokeGrid[data-tab="'+tab+'"]').show();
});
$(document).on('keydown', '.ablx .PokeGrid .pk', function(e){
  if(e.key==='Enter'||e.key===' '){ e.preventDefault(); var pid=$(this).data('pid'); if(typeof openDex==='function'&&pid) openDex(pid); }
});

function comparison(id){
    $.ajax({
  url: "/do/pp",
  type: "POST",
  data: 'comparison='+id,
   success: function (response) {
       response = (typeof response === 'string') ? JSON.parse(response) : response;
       Game.notifications.main(response['text'], 'info');
   }
  });
}

function evol_lvl(id){
    if(confirm("Вы уверены, что хотите эволюционировать покемона?") == true){
        $.ajax({
  url: "/do/pp",
  type: "POST",
  data: 'evolution_lvl='+id,
   success: function (response) {
       response = (typeof response === 'string') ? JSON.parse(response) : response;
       if(response['er'] == 2){
           $('<div />', {
              "class": 'GiftOnline evolution',
              "html": function() {
                $('<div />', {
                  "class": 'Text',
                  "html": 'Выберите покемона для эволюции:'
                }).appendTo(this);
                $('<div />', {
                  "class": 'ListEvolution',
                  "html": response['html']
                }).appendTo(this);
                $('<div />', {
                  "class": 'Button',
                  "html": 'Закрыть',
                  "click": function() {
                    $('.GiftOnline').remove();
                  }
                }).appendTo(this);
              }
            }).appendTo('body');
       }else{
           Game.notifications.main(response['text'], 'info');
           if(response['er'] == 1){
               Game.pokemonTeamTabs(id,'info');
           }
       }
       
       
   }
  });
    }
}
function selectTeam(id,pok){
  $(".tooltip").hide();
    $.ajax({
  url: "/do/pp",
  type: "POST",
  data: 'selectTeam='+id+'&idpok='+pok,
   success: function (response) {
       response = (typeof response === 'string') ? JSON.parse(response) : response;
       Game.notifications.main(response['text'], 'info');
   }
  });
}
function delTeam(pok){
  $(".tooltip").hide();
    $.ajax({
  url: "/do/pp",
  type: "POST",
  data: 'delTeam='+pok,
   success: function (response) {
       response = (typeof response === 'string') ? JSON.parse(response) : response;
       Game.notifications.main(response['text'], 'info');
   }
  });
}
function delTeamUser(id){
  $(".tooltip").hide();
    $.ajax({
  url: "/do/pp",
  type: "POST",
  data: 'delTeamUser='+id,
   success: function (response) {
       response = (typeof response === 'string') ? JSON.parse(response) : response;
       Game.notifications.main(response['text'], 'info');
   }
  });
}
function addTeam(pok,name){
  $(".tooltip").hide();
    $.ajax({
  url: "/do/pp",
  type: "POST",
  data: 'addTeam='+pok+'&name='+name,
   success: function (response) {
       response = (typeof response === 'string') ? JSON.parse(response) : response;
       Game.notifications.main(response['text'], 'info');
   }
  });
}
function opros() {
   var a = $("#opros").val();
  $.ajax({
    url: "/do/opros",
    type: "POST",
    data: 'op=1&text='+a,
    success: function (){
      $('.Opros').remove();
      Game.notifications.main('Отлично! Спасибо за ваш отзыв!', 'info');
    }
  });
}
function oprosper() {
    $('.Opros').remove();
}
function oprosdel() {
  $.ajax({
    url: "/do/opros",
    type: "POST",
    data: 'op=2',
    success: function (){
      $('.Opros').remove();
      Game.notifications.main('Больше вы не увидите этот опрос!', 'info');
    }
  });
}
function setHunt(a){$(a).hasClass("NoActive")?($(a).removeClass("NoActive"),assault=!0,$(a).attr("data","\u0412\u044B\u043A\u043B\u044E\u0447\u0438\u0442\u044C \u043D\u0430\u043F\u0430\u0434\u0435\u043D\u0438\u044F")):($(a).addClass("NoActive"),assault=!1,$(a).attr("data","\u0412\u043A\u043B\u044E\u0447\u0438\u0442\u044C \u043D\u0430\u043F\u0430\u0434\u0435\u043D\u0438\u044F"))}
function setStart(t){$.ajax({url:"/do/pokemonsAction",type:"POST",data:{pokID:t,type:"setStart"},success:function(o){1==(o=JSON.parse(o)).error?Game.notifications.main(o.text,"error"):(Game.notifications.main(o.text,"success"),Game.modals.pokemons())}})}

/* ===================== Fancy walk toast (isolated, chat-safe) ===================== */
(function injectWalkToastCSS(){
  if (document.getElementById('walkToastCSS')) return;
  const css = `
  .walk-toast{
    position:fixed; z-index:2147483000; /* выше всего, но не мешаем кликам */
    width:280px; max-width:92vw;
    background:#fff; border:1px solid #e6eafe; border-radius:12px;
    box-shadow:0 18px 32px rgba(23,35,74,.12); overflow:hidden;
    font-family:Nunito,Inter,Arial,sans-serif; color:#1b2b4f;
    transform:translateY(12px); opacity:.0; transition:.18s ease;
    /* не блокируем элементы под тостом (чат и т.д.) */
    pointer-events:none;

    /* адаптивный отступ снизу: 16px + динамический safe-offset + вырез устройства */
    bottom: calc(16px + var(--wt-bottom-offset, 0px) + env(safe-area-inset-bottom));
    /* горизонталь выбираем классами: .pos-left или .pos-right */
  }
  .walk-toast.show{ transform:translateY(0); opacity:1 }

  .walk-toast.pos-left{ left:16px; right:auto }
  .walk-toast.pos-right{ right:16px; left:auto }

  .walk-toast__row{ display:flex; gap:10px; padding:10px 12px; align-items:center }
  .walk-toast__img{ width:52px; height:52px; border-radius:10px; flex:none;
    background:#f6f9ff center/cover no-repeat; border:1px solid #e6eafe }
  .walk-toast__body{ flex:1 1 auto; min-width:0 }
  .walk-toast__title{ font-weight:900; font-size:14px; line-height:1.15; margin:0 0 4px 0; color:#22345e }
  .walk-toast__text{ font-weight:700; font-size:12.5px; line-height:1.35; color:#6f7b95; }
  .walk-toast__bar{ height:4px; background:#eef3ff }
  .walk-toast__bar > i{ display:block; height:100%; width:30%; background:linear-gradient(90deg,#2f74ff,#0e55b6); border-radius:0 8px 8px 0; animation:wt-run 1.2s infinite }
  @keyframes wt-run{ 0%{width:0%} 50%{width:70%} 100%{width:0%} }
  .walk-toast.success .walk-toast__bar > i{ animation:none; width:100%; background:#27ae60 }
  .walk-toast.error   .walk-toast__bar > i{ animation:none; width:100%; background:#c83535 }
  .walk-toast.success .walk-toast__text{ color:#2e7d32 }
  .walk-toast.error   .walk-toast__text{ color:#b23b3b }

  /* На очень узких экранах смещаем вправо по умолчанию */
  @media (max-width: 700px){
    .walk-toast.pos-left{ left:10px }
    .walk-toast.pos-right{ right:10px }
  }
  `;
  const st = document.createElement('style');
  st.id = 'walkToastCSS';
  st.textContent = css;
  document.head.appendChild(st);
})();

(function ensureWalkToast(){
  if (!$('.walk-toast').length) {
    $('body').append(
      '<div class="walk-toast pos-left" aria-live="polite" aria-atomic="true">'+
        '<div class="walk-toast__row">'+
          '<div class="walk-toast__img" id="walkToastImg"></div>'+
          '<div class="walk-toast__body">'+
            '<div class="walk-toast__title" id="walkToastTitle">Прогулка…</div>'+
            '<div class="walk-toast__text" id="walkToastText">Готовим поводок и хорошее настроение!</div>'+
          '</div>'+
        '</div>'+
        '<div class="walk-toast__bar"><i></i></div>'+
      '</div>'
    );
  }
  walkToastPlace();         // сразу подобрать безопасное место
  addEventListener('resize', walkToastPlace, {passive:true});
})();

/* ===================== безопасное позиционирование рядом с чатом ===================== */
function walkToastPlace(){
  const t = document.querySelector('.walk-toast');
  if (!t) return;

  // Базовые настройки
  t.style.setProperty('--wt-bottom-offset','0px');
  t.classList.remove('pos-left','pos-right');

  // Селекторы возможных чатов/панелей внизу экрана
  const candidates = [
    '#chat', '.chat', '.chat-container', '.chatbox', '.chat-wrap',
    '.bottom-chat', '.footer-chat', '.messages__footer', '.MessagerFooter'
  ];

  let collideLeft = false, collideRight = false, bottomOffset = 0;

  for (const sel of candidates){
    const el = document.querySelector(sel);
    if (!el) continue;

    const cs = getComputedStyle(el);
    if (cs.visibility === 'hidden' || cs.display === 'none') continue;

    const r = el.getBoundingClientRect();
    // Элемент прилип к низу? Берём его высоту как «запретную» зону
    const nearBottom = (innerHeight - r.bottom) < 120 || cs.position === 'fixed';
    if (!nearBottom) continue;

    if (r.left < 160) collideLeft = true;
    if ((innerWidth - r.right) < 160) collideRight = true;

    // Минимальный безопасный отступ от низа
    bottomOffset = Math.max(bottomOffset, Math.max(0, innerHeight - r.top) + 8);
  }

  // Если слева чат — переносим тост вправо, иначе остаёмся слева
  if (collideLeft && !collideRight) t.classList.add('pos-right');
  else                               t.classList.add('pos-left');

  // Доп. вертикальный отступ, если снизу есть панель/чат
  if (bottomOffset > 0){
    bottomOffset = Math.min(bottomOffset, 220); // не улетать слишком высоко
    t.style.setProperty('--wt-bottom-offset', bottomOffset + 'px');
  }
}

/* универсальный апдейтер мини-карточки */
function walkToastShow(state, title, text, gifUrl){
  const $t = $('.walk-toast');
  const $img = $('#walkToastImg');
  const $tt  = $('#walkToastTitle');
  const $tx  = $('#walkToastText');

  const FALLBACK_GIF = '/img/ui/poke-walk.gif';
  const SAFE_PLACEH  = 'data:image/svg+xml;utf8,' + encodeURIComponent('💫');

  $t.removeClass('success error');
  if (state === 'success') $t.addClass('success');
  if (state === 'error')   $t.addClass('error');

  $img.css('background-image', `url("${gifUrl || FALLBACK_GIF}"), url("${SAFE_PLACEH}")`);
  $tt.text(title || 'Прогулка…');
  $tx.text(text || 'Покемон отправился на прогулку.');

  walkToastPlace();            // перед показом проверяем коллизию с чатом
  requestAnimationFrame(()=> $t.addClass('show'));
}

/* авто-скрытие */
function walkToastHide(delayMs){
  const $t = $('.walk-toast');
  setTimeout(()=> $t.removeClass('show'), delayMs || 1600);
}

/* ========================== wentPok (визуал улучшен) ========================== */
function wentPok(o){
  $.ajax({
    url: "/do/pokemonsAction",
    type: "POST",
    data: { pokID: o, type: "wentPok" },
    beforeSend: function () {
      $("#locationPreloader span").html(Lang?.loader_pokemon_walk || 'Покемон отправляется гулять…');
      $("#locationPreloader").hide();

      walkToastShow(
        'loading',
        'Идём гулять!',
        Lang?.loader_pokemon_walk || 'Покемон отправляется на прогулку.',
        '/img/ui/poke-walk.gif'
      );
    },
    success: function (response) {
      try { response = (typeof response === 'string') ? JSON.parse(response) : response; } catch(e){}
      if (response?.error == 1) {
        walkToastShow('error', 'Не удалось прогуляться', response.text || 'Ошибка', '/img/ui/poke-walk.gif');
        walkToastHide(1800);
        Game.notifications.main(response.text || 'Ошибка', 'error');
      } else {
        Game.modals.pokemons();
        walkToastShow('success', 'Прогулка началась!', response?.text || 'Удачи!', '/img/ui/poke-walk.gif');
        walkToastHide(1600);
        Game.notifications.main(response?.text || 'Удачи!', 'success');
      }
    },
    error: function () {
      walkToastShow('error', 'Ошибка соединения', 'Попробуйте ещё раз позднее.', '/img/ui/poke-walk.gif');
      walkToastHide(2000);
      Game.notifications.main('Ошибка соединения', 'error');
    }
  });
}


function openReproductionPoks(a,b){
  $('.GiveDiv').remove();
  var c='<div class="GiveDiv">';
  c+='<div id="DivAbout">\u0420\u0430\u0437\u0432\u0435\u0434\u0435\u043D\u0438\u0435 \u043F\u043E\u043A\u0435\u043C\u043E\u043D\u043E\u0432</b></div>',
  c+='<div class="wrap"><div class="PokList">',
  $.ajax({
    url:'/do/itemsAction',
    type:'POST',
    data:'type=reproductionList',
    success:function(d){
    if(0!=d){
      d=JSON.parse(d);
      var f='';
      $.each(d.pokList,function(l,m){
      var n=m.gen.split(',');
      f+='<div class="PokeUse" onclick=AddReproductionPok('+m.id+','+b+',"'+m.type+'","'+m.basenum+'","'+m.gender+'");><img src="/img/pokemons/animation/'+m.basenum+'.png"></img> <div class="NameUse '+m.type+'-color">#'+m.basenum+' '+m.name+' <small><b>'+m.gender.charAt(0)+' ('+m.sparkaNumber+')</b></small><br /><small><u><i>h'+n[0]+'a'+n[1]+'d'+n[2]+'s'+n[3]+'sa'+n[4]+'sd'+n[5]+'</u></i></small></div></div>'}),c+=f}c+='</div></div></div>',$(c).appendTo('body');var g=$(a).position(),h=$(a).offset(),i=Math.round(h.left)+10,j=Math.round(g.top/10),k=Math.round(h.top+g.top/j+100);$('.GiveDiv').css({left:i+'px',top:k+'px'})}})}
function issetAll(m,o=false,l=false,e){
  e = window.event;
  var a = $('.tooltip');
  if(!o){
    o = 'item';
  }
  $.ajax({
  url: "/do/Issets",
  type: "POST",
  data: 'type='+o+'&id='+m+'&other='+l,
  beforeSend: function(){
    if(device.mobile()){
      if(o == 'atcdex'){
        var left = (e.clientX - 234), top = (e.clientY - 400);
      }else if(o == 'atcdex2'){
        var left = (e.clientX - 234), top = (e.clientY - 200);
      }else{
        var left = (e.clientX - 134), top = (e.clientY + 15);
      }
    }else{
      if(o == 'dex'){
        var left = (e.clientX - 234), top = (e.clientY - 300);
      }else if(o == 'atcdex'){
        var left = (e.clientX - 234), top = (e.clientY - 350);
      }else if(o == 'atcdex2'){
        var left = (e.clientX - 234), top = (e.clientY - 150);
      }else if(o == 'codeloto'){
        var left = (e.clientX - 50), top = (e.clientY - 50);
      }else{
        var left = (e.clientX - 134), top = (e.clientY + 15);
      }
    }
    if(left < 0) {
      left = 0;
    }
    if(top < 0) {
      top = 0;
    }
    if(top > 350){
      top = 350;
    }
    a.css({"left": left+'px',"top": top+'px'});
    a.html('<center>'+mainLoader+'</center>');a.show();},success: function (response) {response = (typeof response === 'string') ? JSON.parse(response) : response;a.html(response['text']);}});}
function btnCreateClan() {
    /* --- inject amethyst UI styles once --- */
    (function injectClanCSS() {
        if (document.getElementById('clanCreateCSS')) return;
        const css = `
:root{
  --bg:#f6f7fb;
  --panel:#ffffff;
  --ink:#2f2f39;
  --muted:#8c8fa1;
  --line:#e7e7ef;
  --amethyst-050:#faf9ff;
  --amethyst-100:#f2f0ff;
  --amethyst-200:#e1dcff;
  --amethyst-400:#b0a3e6;
  --amethyst-600:#7e71c7;
  --amethyst-700:#6c5bb9;
  --success:#2fb57c;
  --error:#e0627e;
}

/* контейнер формы */
.createClan{
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:16px;
  padding:18px 18px 16px;
  box-shadow:0 10px 24px rgba(108,91,185,.08);
  max-width:720px;
}
.createClan .title{
  margin:0 0 12px;
  font:600 22px/1.2 system-ui,-apple-system,Segoe UI,Roboto,Arial;
  color:var(--ink);
}
.createClan .field{margin-top:12px;}
.createClan .label{
  display:block;
  color:var(--muted);
  font:600 12px/1.1 system-ui;
  text-transform:uppercase;
  letter-spacing:.06em;
  margin-bottom:8px;
}

/* инпуты */
#createClanName, #clanCreateName, #clanEmblemUpload{
  appearance:none;
  outline:none;
  width:100%;
  box-sizing:border-box;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid var(--line);
  background:linear-gradient(0deg,var(--bg),var(--bg));
  color:var(--ink);
  font:500 14px/1.3 system-ui,-apple-system,Segoe UI,Roboto,Arial;
  transition:border-color .2s, box-shadow .2s, background-color .2s;
}
#clanCreateName::placeholder{color:#a4a7b7;}
#clanCreateName:focus{
  border-color:var(--amethyst-400);
  box-shadow:0 0 0 4px var(--amethyst-100);
  background:#fff;
}

/* загрузчик файла (нативный + кнопка) */
#clanEmblemUpload{
  padding:10px 12px;
  background:var(--amethyst-050);
  border:1px dashed var(--amethyst-400);
  cursor:pointer;
}
#clanEmblemUpload:hover{background:var(--amethyst-100);}
#clanEmblemUpload:focus{
  border-color:var(--amethyst-600);
  box-shadow:0 0 0 4px var(--amethyst-100);
}
/* стили кнопки выбора файла */
#clanEmblemUpload::-webkit-file-upload-button{
  background:linear-gradient(180deg,var(--amethyst-600),var(--amethyst-700));
  color:#fff;
  border:none;
  border-radius:10px;
  padding:8px 12px;
  margin-right:10px;
  font-weight:600;
  cursor:pointer;
}
#clanEmblemUpload::file-selector-button{
  background:linear-gradient(180deg,var(--amethyst-600),var(--amethyst-700));
  color:#fff;
  border:none;
  border-radius:10px;
  padding:8px 12px;
  margin-right:10px;
  font-weight:600;
  cursor:pointer;
}

/* кнопка отправки */
.createClanBtn{
  position:relative;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  padding:12px 18px;
  margin-top:14px;
  border:none;
  border-radius:12px;
  background:linear-gradient(180deg,var(--amethyst-600),var(--amethyst-700));
  color:#fff;
  font:700 14px/1 system-ui,-apple-system,Segoe UI,Roboto,Arial;
  letter-spacing:.02em;
  box-shadow:0 10px 18px rgba(108,91,185,.25), inset 0 -1px 0 rgba(0,0,0,.15);
  cursor:pointer;
  transition:transform .15s ease, box-shadow .2s ease, opacity .2s;
}
.createClanBtn:hover:not([disabled]){
  transform:translateY(-1px);
  box-shadow:0 14px 24px rgba(108,91,185,.3), inset 0 -1px 0 rgba(0,0,0,.15);
}
.createClanBtn:active:not([disabled]){transform:translateY(0);}
.createClanBtn[disabled]{
  cursor:not-allowed;
  opacity:.6;
  filter:grayscale(.15);
}

/* спиннер внутри кнопки (можно показывать/скрывать классом .is-loading на кнопке) */
.createClanBtn .btn__spinner{
  width:16px;height:16px;border-radius:50%;
  border:2px solid rgba(255,255,255,.35);
  border-top-color:#fff;
  display:none;
  animation:spin .8s linear infinite;
}
.createClanBtn.is-loading .btn__spinner{display:inline-block;}
.createClanBtn.is-loading .btn__text{opacity:.9}

@keyframes spin{to{transform:rotate(360deg)}}

/* уведомления / итоговый блок */
.okCM{
  background:linear-gradient(0deg,#f2fff8,#ffffff);
  border:1px solid #d8f4e6;
  color:#0e7f56;
  border-radius:12px;
  padding:14px 16px;
  font:600 14px/1.3 system-ui;
  box-shadow:0 8px 20px rgba(15,135,96,.07);
}
.errCM{
  background:linear-gradient(0deg,#fff2f4,#ffffff);
  border:1px solid #f3ccd5;
  color:#9d2f49;
  border-radius:12px;
  padding:14px 16px;
  font:600 14px/1.3 system-ui;
  box-shadow:0 8px 20px rgba(157,47,73,.07);
}

/* вспомогательные отступы для простых разметок */
.createClan .mb-8{margin-bottom:8px}
.createClan .mb-12{margin-bottom:12px}
.createClan .mb-16{margin-bottom:16px}


/* ---- Hotkeys UI ---- */
.hkStep .Buttons{display:flex;align-items:center;gap:10px}
.hk-switch{position:relative;display:inline-flex;align-items:center;cursor:pointer;user-select:none}
.hk-switch input{display:none}
.hk-slider{width:46px;height:26px;border-radius:999px;background:var(--br);position:relative;transition:all .2s ease}
.hk-slider:before{content:'';position:absolute;left:3px;top:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.15);transition:all .2s ease}
.hk-switch input:checked + .hk-slider{background:var(--ac)}
.hk-switch input:checked + .hk-slider:before{transform:translateX(20px)}
.hk-details{margin-top:8px;border:1px solid var(--br);border-radius:12px;background:#fff;padding:10px 12px}
.hk-details > summary{cursor:pointer;font-weight:800;color:var(--txt);outline:none}
.hk-groups{display:flex;flex-direction:column;gap:10px;margin-top:10px}
.hk-group-title{font-weight:800;color:var(--txt);margin-bottom:6px}
.hk-grid{display:grid;grid-template-columns:120px 1fr;gap:6px 10px;align-items:start}
.hk-kbd span{display:inline-block;padding:4px 8px;border:1px solid var(--br);border-radius:10px;background:var(--chip);font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;font-size:12px}
.hk-desc{font-size:12px;color:var(--txt)}
.hk-muted{font-size:11px;color:var(--sub);margin-top:2px}

        `;
        const style = document.createElement('style');
        style.id = 'clanCreateCSS';
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    })();
    /* --- end inject --- */

    var clanName = $("#clanCreateName").val().trim();
    var emblemInput = $("#clanEmblemUpload")[0];

    // Проверяем наличие названия и файла эмблемы
    if (clanName === "") {
        if (window.Game?.notifications?.main) {
            Game.notifications.main("Введите название и выберите эмблему клана!", "error");
        } else {
            alert("Введите название и выберите эмблему клана!");
        }
        return;
    }
    var emblemFile = (emblemInput && emblemInput.files && emblemInput.files[0]) ? emblemInput.files[0] : null;

    // Проверка типа файла
    if (!/^image\/(png|jpeg)$/.test(emblemFile.type)) {
        Game.notifications?.main?.("Эмблема должна быть PNG или JPG!", "error");
        return;
    }
    // Проверка размера файла (до 10 МБ)
    if (emblemFile.size > 10 * 1024 * 1024) {
        Game.notifications?.main?.("Файл эмблемы слишком большой (максимум 10 МБ)!", "error");
        return;
    }

    var formData = new FormData();
    formData.append("object", "createClan");
    formData.append("name", clanName);
    formData.append("csrf_token", clanCsrfToken());
    if (emblemFile) { formData.append("emblem", emblemFile); }

    // Отключаем кнопку, чтобы избежать двойной отправки
    var $btn = $(".createClanBtn");
    $btn.addClass("is-loading").prop("disabled", true).css("opacity", 0.9);

    $.ajax({
        url: "/do/clanAction.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        xhr: function () {
            // Добавим трекинг прогресса, если есть <progress> или кастом
            var xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener("progress", function (e) {
                    // здесь можно обновлять прогрессбар, если он есть в разметке
                }, false);
            }
            return xhr;
        },
        success: function (response) {
            let data;
            try {
                data = typeof response === "string" ? JSON.parse(response) : response;
            } catch (e) {
                Game.notifications?.main?.("Ошибка сервера или неверный формат ответа.", "error");
                $btn.removeClass("is-loading").prop("disabled", false).css("opacity", 1);
                return;
            }
            if (data["minus"]) {
                Game.notifications?.main?.(data["minus"], "minus");
            }
            Game.notifications?.main?.(data["text"] || data["message"], data["error"] === 0 ? "success" : (data["status"] || "error"));

            // При успешном создании клана
            if (data["error"] === 0 || data["status"] === "success") {
                $(".createClan").hide();
                $(".txtCM").html('<div class="okCM">Клан успешно создан!</div>');
                if (data["clanId"]) { try { openClanCard(parseInt(data["clanId"])); } catch(e){} }
            } else {
                $btn.removeClass("is-loading").prop("disabled", false).css("opacity", 1);
            }
        },
        error: function () {
            Game.notifications?.main?.("Ошибка соединения с сервером. Попробуйте позже.", "error");
            $btn.removeClass("is-loading").prop("disabled", false).css("opacity", 1);
        }
    });
}

function sendPokWork(cat,pok){
     $.ajax({
    url: "/do/work",
    type: "POST",
    data: 'category='+cat+'&pok='+pok,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["minus"]) {
        Game.notifications.main(response["minus"], 'minus');
      }if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["notify"], response["error"]);
      if(response["error"] == "success"){
          $('.WorkPokemon').html(response["html"]);
      }
    }
  });
}
function utility_activ(id){
    $.ajax({
    url: "/do/pp",
    type: "POST",
    data: 'utility='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
          settings();
      }
    }
  });
}
function calendar_gift(id){
    $.ajax({
    url: "/do/pp",
    type: "POST",
    data: 'calendar='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      Game.notifications.main(response["text"], response["error"]);
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      if(response["error"] == "success"){
          openModal('calendar_day');
      }
    }
  });
}
function returnwork(cat,pok){
     $.ajax({
    url: "/do/work",
    type: "POST",
    data: 'category='+cat+'&retpok='+pok,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["notify"], response["error"]);
      if(response["error"] == "success"){
          openModal('work');
      }
    }
  });
}
function by_item_shop(item, count) {
  $.ajax({
    url: "/do/shop_di",
    type: "POST",
    data: { item: item, count: count }, // Используем объект для передачи параметров
    success: function (response) {
      // Попробуем безопасно распарсить JSON
      let resp = {};
      try {
        resp = typeof response === "object" ? response : JSON.parse(response);
      } catch (e) {
        Game.notifications.main("Ошибка обработки ответа сервера.", "error");
        return;
      }

      // Показываем сообщения о плюсах/миносах
      if (resp.minus) {
        Game.notifications.main(resp.minus, "minus");
      }
      if (resp.plus) {
        Game.notifications.main(resp.plus, "plus");
      }

      // Основной текст (например, ошибка или успех)
      if (resp.text) {
        Game.notifications.main(resp.text, resp.error || "info");
      }

      // Если успех — обновляем магазин и баланс
      if (resp.error === "success") {
        if (typeof updateEmeraldBalance === "function" && resp.balance !== undefined) {
          updateEmeraldBalance(resp.balance); // если сервер вернёт новый баланс
        }
        openModal("shop");
      }

      // Если были дополнительные действия (например, закрытие попапа)
      if (resp.close_modal) {
        closeModal();
      }
    },
    error: function () {
      Game.notifications.main("Ошибка соединения с сервером.", "error");
    }
  });
}
function BossPrize(id){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'BossPrize='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
          openModal('calendar');
      }
    }
  });
}
function successMission(id){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'mission='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
          openModal('calendar');
      }
    }
  });
}
function labir(id){
    var i = $( ".slot_lab_"+id).hasClass("click");
    if(i){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'labirint='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["text"]){
      Game.notifications.main(response["text"], response["error"]);
      }
      if(response["plus"]){
      Game.notifications.main(response["plus"], 'plus');
      }
      if(response['winkey']){
          calendarCategory('week');
      }
      if(response['lose']){
          calendarCategory('week');
      }
      if(response['error'] == "success" && !response['winkey'] && !response['lose']){
          if(response['slot1']){$('.slot_lab_1').addClass("click");}
          if(response['slot2']){$('.slot_lab_2').addClass("click");}
          if(response['slot3']){$('.slot_lab_3').addClass("click");}
          if(response['slot4']){$('.slot_lab_4').addClass("click");}
          if(response['slot5']){$('.slot_lab_5').addClass("click");}
          if(response['slot6']){$('.slot_lab_6').addClass("click");}
          if(response['slot7']){$('.slot_lab_7').addClass("click");}
          if(response['slot8']){$('.slot_lab_8').addClass("click");}
          if(response['slot9']){$('.slot_lab_9').addClass("click");}
          
          if(response['my']){
              $('.slot_lab_'+response['my']).remove();
              if(response['s_0_1']){
                  $('.slot_lab_'+response['s_0_1']).addClass("slot_lab_"+response['s_0_2']).removeClass("slot_lab_"+response['s_0_1']).attr('data',response['s_0_2']);
              }
              if(response['s_1_1']){
                  $('.slot_lab_'+response['s_1_1']).addClass("slot_lab_"+response['s_1_2']).removeClass("slot_lab_"+response['s_1_1']).attr('data',response['s_1_2']);
              }
              if(response['s_2_1']){
                  $('.slot_lab_'+response['s_2_1']).addClass("slot_lab_"+response['s_2_2']).removeClass("slot_lab_"+response['s_2_1']).attr('data',response['s_2_2']);
              }
              if(response['s_3_1']){
                  $('.slot_lab_'+response['s_3_1']).addClass("slot_lab_"+response['s_3_2']).removeClass("slot_lab_"+response['s_3_1']).attr('data',response['s_3_2']);
              }
              if(response['s_4_1']){
                  $('.slot_lab_'+response['s_4_1']).addClass("slot_lab_"+response['s_4_2']).removeClass("slot_lab_"+response['s_4_1']).attr('data',response['s_4_2']);
              }
              
              $('<div />', {
			"class": 'slot_lab slot_lab_'+response['new']+' '+response['type']+' ',
			html: response["sl_text"],
			'click': function() {
				labir(this.getAttribute('data'));
			}
		}).appendTo('.labirint');
		setTimeout(function(){
					$('.slot_lab_'+response['new']).removeClass(response['type']).attr('data',response['new']);
				}, 10);
				
				$('#hp_my').html(response['hp']);
			if(response['1']){ $('.slot_lab_1').html(response['1']);}
			if(response['2']){ $('.slot_lab_2').html(response['2']);}
			if(response['3']){ $('.slot_lab_3').html(response['3']);}
			if(response['4']){ $('.slot_lab_4').html(response['4']);}
			if(response['5']){ $('.slot_lab_5').html(response['5']);}
			if(response['6']){ $('.slot_lab_6').html(response['6']);}
			if(response['7']){ $('.slot_lab_7').html(response['7']);}
			if(response['8']){ $('.slot_lab_8').html(response['8']);}
			if(response['9']){ $('.slot_lab_9').html(response['9']);}
				
              $('.slot_lab').removeClass("click");
              $('.slot_lab_'+response['my']).addClass("click");
          }
		
      }
    }
  });
    }else{
        Game.notifications.main("Ошибка!", "error");
    }
}
function labirint_game(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'labirint_game=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      Game.notifications.main(response["text"], response["error"]);
      if(response['error'] == "success"){
          calendarCategory('week');
      }
    }
  });
    
}
function home_game(){
    $('.divChest').remove();
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'home_game=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response['text']){
      Game.notifications.main(response["text"], response["error"]);
      }
      if(response['error'] == "success"){
          $('<div />', {
			"class": 'divChest ',
			html: response["html"],
		}).appendTo('body');
		$('<div />', {
			"class": 'Close',
			html: '<i class="fas fa-times"></i>',
			'click': function() {
				$('.divChest').remove();
			}
		}).appendTo('.divChest');
      }
    }
  });
    
}
function home_fill(type,id,fi){
    
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'home_game_game=fill&fill='+type,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response['text']){
      Game.notifications.main(response["text"], response["error"]);
      }
      if(response['error'] == "success"){
          $('.my_ticket span').html(response["ticket"]);
          $('.fill'+fi).remove();
          $('.'+id+'Pipe div').css('bottom','230px');
          setTimeout(function(){
					$('.'+id+'Pipe div').html(response['html']);
					$('.'+id+'Pipe div').css('bottom','20px');
				}, 600);
          
          
		
      }
    }
  });
    
}
function home_trade_jet(){
    
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'home_game_game=trade',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response['text']){
      Game.notifications.main(response["text"], response["error"]);
      }
      if(response['error'] == 'success'){
      $('.playingprogress div').css('width','0%');
              $('.chest_ivent').html('0');
              Game.notifications.main(response["plus"], 'plus');
      }
    }
  });
    
}
function home_destroy(type,id,fi){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'home_game_game=destroy',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response['text']){
      Game.notifications.main(response["text"], response["error"]);
      }
      if(response['error'] == "success"){
          $('.Pipe div').css('bottom','280px');
          
          $('.fillbtn').remove();
          setTimeout(function(){
              $('.Pipe div').html('');
				$('<div />', {
			        "class": 'fillbtn fill1',
			        html: '<i class="fas fa-layer-plus"></i>',
			        'click': function() {
				        home_fill('col_1','Left',1);
			        }
		        }).appendTo('.divChest');
		        $('<div />', {
			        "class": 'fillbtn fill2',
			        html: '<i class="fas fa-layer-plus"></i>',
			        'click': function() {
				        home_fill('col_2','Center',2);
			        }
		        }).appendTo('.divChest');
		        $('<div />', {
			        "class": 'fillbtn fill3',
			        html: '<i class="fas fa-layer-plus"></i>',
			        'click': function() {
				        home_fill('col_3','Right',3);
			        }
		        }).appendTo('.divChest');
		   }, 800);

      }
    }
  });
    
}
function playing_give(item,col,pok){
    $('.tooltip').hide();
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'home_game_game=give&item='+item+'&pok='+pok+'&col='+col,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          if(response["text"]){
              
          Game.notifications.main(response["text"], response["error"]);
          }
          if(response['fill1']){
              $('<div />', {
			        "class": 'fillbtn fill1',
			        html: '<i class="fas fa-layer-plus"></i>',
			        'click': function() {
				        home_fill('col_1','Left',1);
			        }
		        }).appendTo('.divChest');
          }
          if(response['fill2']){
              $('<div />', {
			        "class": 'fillbtn fill2',
			        html: '<i class="fas fa-layer-plus"></i>',
			        'click': function() {
				        home_fill('col_2','Center',2);
			        }
		        }).appendTo('.divChest');
          }
          if(response['fill3']){
              $('<div />', {
			        "class": 'fillbtn fill3',
			        html: '<i class="fas fa-layer-plus"></i>',
			        'click': function() {
				        home_fill('col_3','Right',3);
			        }
		        }).appendTo('.divChest');
          }
          
          if(response['col_1']){
                  $('.LeftPipe div').html(response['col_1']);
          }
          if(response['col_1_tr']){
                  $('.LeftPipe div').html(" ");
          }
          if(response['col_2']){
              $('.CenterPipe div').html(response['col_2']);
          }
          if(response['col_2_tr']){
                  $('.CenterPipe div').html(" ");
          }
          if(response['col_3']){
              $('.RightPipe div').html(response['col_3']);
          }
          if(response['col_3_tr']){
                  $('.RightPipe div').html(" ");
          }
          
          if(response['wish'] == 'update'){
              $('.'+response['pok']+' .Wish').html(response['items']);
          }else if(response['wish'] == 'switch'){
              $('.playingprogress div').css('width',response['widt']+'%');
              $('.chest_ivent').html(response['ptn']);
              $('.'+response['pok']).html(response['addwish']);
          }
          if(response["plus"]){ Game.notifications.main(response["plus"], 'plus'); }
          if(response["minus"]){ Game.notifications.main(response["minus"], 'minus'); }
      }
    }
  });
}
function arheolog_open(id){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'arheolog_open='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["slot"]){
          $('.cell_'+id).html(response["slot"]);
          $('.cell_'+id).removeClass("clickable");
          $('.my_shovel span').html(response["shovel"]);
          if(response["key"]){
              calendarCategory('week');
          }
      }
    }
  });
}
function arheolog_open_empty(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'arheolog_open_empty=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["minus"]) {
        Game.notifications.main(response["minus"], 'minus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
        calendarCategory('week');
      }
    }
  });
}
function startIventWeek(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'startIventWeek=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
        calendarCategory('week');
      }
    }
  });
}
function buy_shovel(){
    if(confirm("Вы уверены, что хотите добавить 10 лопат за 5 аметистов?") == true){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'buy_shovel=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["minus"]) {
        Game.notifications.main(response["minus"], 'minus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
        $('.my_shovel span').html(response["shovel"]);
      }
    }
  });
    }
}
function buy_ticket(){
    if(confirm("Вы уверены, что хотите добавить 6 билетов за 10 аметистов?") == true){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'buy_ticket=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["minus"]) {
        Game.notifications.main(response["minus"], 'minus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
        $('.my_ticket span').html(response["ticket"]);
      }
    }
  });
    }
}
function open_chest(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'open_chest=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
        $('.chest_ivent').html(response["chest"]);
      }
    }
  });
}
function getpresentIvent(type,turn){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'type='+type+'&turn='+turn,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["plus"]) {
        Game.notifications.main(response["plus"], 'plus');
      }
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
          calendarCategory('ivent');
      }
    }
  });
}

function startIvent(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'startIvent=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
          calendarCategory('ivent');
      }
    }
  });
}
function coocking(type,id){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'coocking=1&category='+type+'&id='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      Game.notifications.main(response["text"], response["error"]);
      if(response["error"] == "success"){
          if(response["minus"]){
              Game.notifications.main(response["minus"], 'minus');
          }
          if(response["plus"]){
              Game.notifications.main(response["plus"], 'plus');
          }
          calendarCategory('ivent');
      }
    }
  });
}
function PveBoss(id) {
    // Убедимся, что id валидный
    if (!id) {
        Game.notifications.main("Некорректный идентификатор босса!", "error");
        return;
    }

    // Отправка запроса
    $.ajax({
        url: "/do/calendarAction",
        type: "POST",
        data: { bosspve: id }, // Передача данных как объекта
        success: function (response) {
            try {
                response = (typeof response === 'string') ? JSON.parse(response) : response;

                if (response.error === "error") {
                    // Показываем сообщение об ошибке
                    Game.notifications.main(response.text, "error");
                } else {
                    // Показываем успешное сообщение
                    Game.notifications.main(response.text, "success");
                    closeModal(); // Закрываем модальное окно
                }
            } catch (e) {
                // Ошибка парсинга ответа
                Game.notifications.main("Ошибка обработки ответа сервера.", "error");
                console.error("Parse error:", e);
            }
        },
        error: function (xhr, status, error) {
            // Обработка сетевых ошибок
            Game.notifications.main("Не удалось связаться с сервером. Попробуйте позже.", "error");
            console.error("AJAX error:", status, error);
        }
    });
}

function playing(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'playing_add=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          Game.notifications.main(response["text"], response["error"]);
          calendarCategory('ivent');
      }
    }
  });
}
function playing_give_prize(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'playing_give=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          Game.notifications.main(response["plus"], 'plus');
          Game.notifications.main(response["text"], response["error"]);
          calendarCategory('ivent');
      }
    }
  });
}
function tree_give(){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'tree_give=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          Game.notifications.main(response["minus"], 'minus');
          Game.notifications.main(response["text"], response["error"]);
          calendarCategory('ivent');
      }
    }
  });
}
function tree_prize(id){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'tree_prize=1&t='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          Game.notifications.main(response["plus"], 'plus');
          Game.notifications.main(response["text"], response["error"]);
          calendarCategory('ivent');
      }
    }
  });
}
function tree_chance(){
    if(confirm("Вы уверены, что хотите добавить 5% за 5 аметистов?") == true){
    $.ajax({
    url: "/do/calendarAction",
    type: "POST",
    data: 'tree_chance=1',
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          Game.notifications.main(response["minus"], 'minus');
          Game.notifications.main(response["text"], response["error"]);
          calendarCategory('ivent');
      }
    }
  });
    }
}

function BossBattle(id){
    $.ajax({
    url: "/do/pp",
    type: "POST",
    data: 'boss='+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      if(response["error"] == "error"){
          Game.notifications.main(response["text"], response["error"]);
      }else{
          Game.notifications.main(response["text"], response["error"]);
          closeModal();
      }
    }
  });
}

function AddReproductionPok(a,b,c,d,f){
  $('#Reproduction'+b).css('background-image','url(/img/pokemons/sprite/'+c+'/'+d+'.gif)').attr('data-pok',a)
}
function GetReproductionResult() {
    var a = $('#Reproduction1').attr('data-pok'),
        b = $('#Reproduction2').attr('data-pok');
    a && b ? a == b ? Game.notifications.main(Lang.error_pokemon_different, 'error') : $.ajax({
        url: '/do/reproduction',
        type: 'POST',
        data: 'pok1=' + a + '&pok2=' + b,
        success: function(c) {

            c = JSON.parse(c), 1 == c.error ? Game.notifications.main(c.text, 'error') : Game.notifications.main(c.text, 'success');
        }
    }) : Game.notifications.main(Lang.error_pokemon_choose, 'error')
}
function setTrade(a,b){$.ajax({url:"/do/trade",type:"POST",data:"type="+a+"&id="+b,success:function(c){c=JSON.parse(c),1==c.error?Game.notifications.main(c.text,"error"):Game.notifications.main(c.text,"success")}})}
function updNoticeDef(t){t&&$.each(t,function(t,o){o&&o.text&&Game.notifications.main(o.text,"success")})}
function uploadImg() {
    var fileInput = $('#upload_file #file_name')[0];
    if (!fileInput || !fileInput.files[0]) {
        Game.notifications.main('Выберите файл для загрузки', 'error');
        return false;
    }
    var u = fileInput.files[0];
    var s = new FormData();
    s.append('file', u);
    var formOriginal = $('#upload_file').html();
    $.ajax({
        url: '/do/upload',
        dataType: 'json', // лучше возвращать JSON на сервере!
        cache: false,
        contentType: false,
        processData: false,
        data: s,
        type: 'POST',
        beforeSend: function() {
            $('#upload_file').html('<center><img height="20px" src="/img/loader/1.gif"></center>');
        },
        success: function(resp){
            // resp должен быть объектом: {success: true/false, message: "..."}
            if(resp && resp.success) {
                $('#upload_file').html('<center><span style="color: green;font-weight: bold;">'+(Lang.success_load_file || 'Файл успешно загружен')+'</span></center>');
                Game.notifications.main(resp.message || (Lang.success_load_file || 'Файл успешно загружен'), 'success');
            } else {
                Game.notifications.main(resp && resp.message ? resp.message : 'Ошибка при загрузке файла', 'error');
                $('#upload_file').html(formOriginal);
            }
        },
        error: function(xhr) {
            Game.notifications.main('Ошибка при загрузке файла', 'error');
            $('#upload_file').html(formOriginal);
        }
    });
    return false;
}
var Game = {
    title_tooltip: function() {
        $('<div />', {
            "class": 'Button',
            html: '<i class="far fa-paw"></i>',
            onload: function() { Tipped.create(this, 'Мои покемоны'); },
            click: function() { Game.modals.pokemons(); }
        }).appendTo('.MidMenu');
        $('<div />', {
            "class": 'Button',
            html: '<i class="far fa-backpack"></i>',
            onload: function() { Tipped.create(this, 'Мой инвентарь'); },
            click: function() { Game.modals.inventory('all'); }
        }).appendTo('.MidMenu');
        $('<div />', {
            "class": 'Button',
            html: '<i class="far fa-users"></i>',
            onload: function() { Tipped.create(this, 'Тренеры'); },
            click: function() { openModal('trainers'); }
        }).appendTo('.MidMenu');
        $('<div />', {
            "class": 'Button',
            html: '<i class="far fa-bookmark"></i>',
            onload: function() { Tipped.create(this, 'Кланы'); },
            click: function() { Game.modals.clans(); }
        }).appendTo('.MidMenu');
        $('<div />', {
            "class": 'Button',
            html: '<i class="far fa-tools"></i>',
            onload: function() { Tipped.create(this, 'Мастерская'); },
            click: function() { openModal('craft'); }
        }).appendTo('.MidMenu');
        $('<div />', {
            "class": 'Button',
            html: '<i class="far fa-book"></i>',
            onload: function() { Tipped.create(this, 'Дневник'); },
            click: function() { Game.modals.diary('location'); }
        }).appendTo('.MidMenu');
        $('<div />', {
            "class": 'Button bp',
            html: '<i class="fab fa-empire"></i>',
            onload: function() { Tipped.create(this, 'Боевой пропуск'); },
            click: function() { openModal('battlepass'); }
        }).appendTo('.MidMenu');

        /* ===== СЕРВЕРНОЕ ВРЕМЯ: ПОСЛЕ .MidMenu, НЕ ЗАДЕВАЯ .RightMenu ===== */
(function mountServerClockAfterMid(){
  // не показываем на телефоне
  if (window.device && device.mobile()) return;

  // стили один раз
  if (!document.getElementById('topclock-style')){
    const css = `
.TopMenu.has-topclock{position:relative}
.topclock{
  position:absolute; top:60%; transform:translateY(-50%);
  right:calc(var(--rmw, 0px) + 12px); z-index:2;
  width: 65px;
    height: 35px;

  display:inline-flex; align-items:center; gap:6px;
  padding:6px 10px; border-radius:12px;
  background:rgba(255,255,255,.6); border:1px solid rgba(108,140,255,.35);
  backdrop-filter:saturate(130%) blur(6px); -webkit-backdrop-filter:saturate(130%) blur(6px);
  font:600 13px/1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; color:#2f3b5a
}
.topclock i{font-size:14px;opacity:.9}
@media (max-width:800px){ .topclock{display:none!important} }
    `;
    const st = document.createElement('style');
    st.id = 'topclock-style';
    st.appendChild(document.createTextNode(css));
    document.head.appendChild(st);
  }

  const $top = $('.TopMenu'); if (!$top.length) return;
  $top.addClass('has-topclock');

  // вставляем после .MidMenu
  if (!$('.topclock').length){
    const $pill = $('<div/>', {
      "class": 'topclock',
      html: '<i class="far fa-clock"></i><span class="t">00:00:00</span>'
    });
    $('.MidMenu').after($pill);
  }

  // вычисляем ширину правого блока и прокладываем зазор
  function place(){
    const $rm = $('.TopMenu .RightMenu');
    const w = ($rm && $rm.length) ? $rm.outerWidth(true) || 0 : 0;
    document.documentElement.style.setProperty('--rmw', w + 'px');
  }
  place();
  window.addEventListener('resize', place);

  // базовое серверное время (сек/мс)
  var base = window.__SERVER_EPOCH__ || window.SERVER_UNIX || window.serverTime || window.SERVER_TIME;
  if (typeof base === 'string' && /^\d+$/.test(base)) base = Number(base);
  if (typeof base === 'number' && base < 1e12) base *= 1000; // в мс
  var offset = (typeof base === 'number') ? (base - Date.now()) : 0;

  function pad(n){ return (n<10?'0':'')+n; }
  function tick(){
    var d = new Date(Date.now() + offset);
    var t = [pad(d.getHours()), pad(d.getMinutes()), pad(d.getSeconds())].join(':');
    var $p = $('.topclock'); if ($p.length) $p.find('.t').text(t);
  }
  clearInterval(window.__serverClockIntervalTop);
  tick(); window.__serverClockIntervalTop = setInterval(tick, 1000);
})();


        Tipped.create('.TopMenu .RightMenu .Buttons .notif', Lang.hint_notify);
        Tipped.create('.TopMenu .RightMenu .Buttons .el_prize', 'Ежедневные призы');
        Tipped.create('.TopMenu .RightMenu .Buttons .el_teleport', 'Телепортация');
        Tipped.create('.TopMenu .RightMenu .Buttons .el_calendar', 'Календарь мероприятий');
        Tipped.create('.el_dex', 'Помощник');
        Tipped.create('.el_fight', 'Бои на локации');
        Tipped.create('.el_clear_chat', 'Очистить чат');
        Tipped.create('.el_smile', 'Смайлики');
        Tipped.create('.el_wild', 'Дикие покемоны');
    },

    BuffsDropdown: {
        render: function(data) {
            var buffs = [];
            var sysBafs = [
                {key: 'ev_sys', class: 'ev_sys', title: '2 ев за уровень'},
                {key: 'money_sys', class: 'money_sys', title: 'Повышенный дроп генкар'},
                {key: 'drop_sys', class: 'drop_sys', title: 'Повышенный дроп предметов'},
                {key: 'exp_sys', class: 'exp_sys', title: 'Повышенное получение опыта'},
                {key: 'shine_sys', class: 'shine_sys', title: 'Повышенный шанс встречи шайни'},
                {key: 'tren_sys', class: 'tren_sys', title: 'Повышенный дроп тренировок'},
            ];
            sysBafs.forEach(function(baf){
                if(data[baf.key]){
                    buffs.push(
                        '<div class="BuffsDropdown-item '+baf.class+'" title="'+baf.title+'">'
                        + data[baf.key]
                        + '<span class="BuffsDropdown-item-title">'+baf.title+'</span>'
                        + '</div>'
                    );
                }
            });

            if (data.bafs) {
                if (Array.isArray(data.bafs)) {
                    data.bafs.forEach(function(customBaf) {
                        if (typeof customBaf === 'object' && customBaf.id) {
                            buffs.push(
                                '<div class="BuffsDropdown-item" onclick="issetAll(' + customBaf.id + ',\'baf\')" style="background-image:url(/img/world/items/little/' + customBaf.id + '.png);" title="' + (customBaf.title || '') + '">' +
                                    '<span class="BuffsDropdown-item-title">' + (customBaf.title || '') + '</span>' +
                                '</div>'
                            );
                        } else if (typeof customBaf === 'string' && customBaf.indexOf('BuffsDropdown-item') !== -1) {
                            buffs.push(customBaf);
                        }
                    });
                } else if (typeof data.bafs === 'string' && data.bafs.indexOf('BuffsDropdown-item') !== -1) {
                    buffs.push(data.bafs);
                }
            }

            $('#BuffsDropdownList').html(buffs.length ? buffs.join('') : '<div class="BuffsDropdown-empty">Нет активных эффектов</div>');
            $('#BuffsCount').text(buffs.length);
        },
        bind: function() {
            $(document).off('.BuffsDropdown');
            $(document).on('click.BuffsDropdown', '#BuffsDropdownBtn', function(e){
                e.stopPropagation();
                $('#BuffsDropdownList').toggleClass('open');
            });
            $(document).on('click.BuffsDropdown', function(){
                $('#BuffsDropdownList').removeClass('open');
            });
            $(document).on('click.BuffsDropdown', '#BuffsDropdownList', function(e){
                e.stopPropagation();
            });
        }
    },
    updateUserLocation: function() {
        $.post('/do/updateLocation', {updateUsers: true, userAssault: assault}, function(data){

            var techWorkEl = $('.TechWork');
            var techWorkHtml = techWorkEl.html();
            if(data.tw == 1) {
                techWorkEl.css('display','block');
                if(!techWorkHtml) {
                    $('<div />', {
                        "class": 'TechWork',
                        html: '<span>Технические работы в игре!</span>'
                    }).appendTo('body');
                }
            } else if(techWorkHtml) {
                window.location.reload();
            }

            if(typeof VERSION !== "undefined" && VERSION != data.ver){
                setTimeout(function(){
                    window.location.reload();
                }, 15000);
                if(!$('.DivNotification').find('.version').length){
                    Game.notifications.main('<b>Системное оповещение</b><br>Внимание! Игра обновлена до версии '+data.ver+'. Через несколько секунд произойдет автоматическая перезагрузка страницы.','info');
                }
            }

            if(data.adminNotify){
                $('<div />', {
                    "class": 'noty info',
                    "id" : 'notyId1',
                    html: '<div class="icon"><i class="fas fa-info"></i></div><div class="content"><b>'+data.adminNotify_author+'</b><br>'+data.adminNotify+'<div class="date">'+data.adminNotify_date+'</div></div>',
                    click: function() { $(this).remove(); }
                }).appendTo('.DivNotification');
            }

            if(data.usersDefNotice){
                updNoticeDef(data.usersDefNotice);
            }

            if(typeof md5locList === "undefined" || md5locList != data.usersAtLocationHash){
                md5locList = data.usersAtLocationHash;
                if(typeof ClassInfo !== "undefined" && ClassInfo){
                    ClassInfo._upUsrLoc(data.usersAtLocation, (data.usersAtNotice || null));
                }
            }

            if($('.BuffsDropdown').length === 0) {
                $('.TopMenu .LeftMenu').html(
                    '<div class="BuffsDropdown">'+
                        '<button class="BuffsDropdown-btn" id="BuffsDropdownBtn" title="Ваши активные эффекты">'+
                          '<i class="fas fa-flask"></i>'+
                          '<span class="BuffsDropdown-count" id="BuffsCount">0</span>'+
                        '</button>'+
                        '<div class="BuffsDropdown-list" id="BuffsDropdownList"></div>'+
                    '</div>'
                );
                Game.BuffsDropdown.bind();
            }
            Game.BuffsDropdown.render(data);

            if(data.infoChat && typeof ClassChat !== "undefined" && ClassChat){
                if(typeof ClassChat.core !== "undefined" && typeof ClassChat.core._update === "function") {
                    ClassChat.core._update(data);
                } else if(typeof ClassChat._update === "function") {
                    ClassChat._update(data);
                }
            }

            if(data.hashc){
                Game.notifications.main('<b>Системное оповещение</b><br>Внимание! Вы зашли с другого устройства, сейчас будет закончена эта сессия!','info');
                setTimeout(function() {
                    window.location.replace('/?route=exitdouble');
                }, 5000);
            }

            if(data.snow_ball){
                var snowBallId = 'id'+data.snow_ball;
                if($('#'+snowBallId).length === 0){
                    $('<div />', {
                        "id": snowBallId,
                        "class": 'Snow_ball',
                        click: function() { snow_ball(data.snow_ball); }
                    }).appendTo('body');
                    var left, top;
                    if(!device.mobile()){
                        left = rand(1,75); top = rand(1,50);
                    } else {
                        left = rand(1,30); top = rand(1,58);
                    }
                    $('#'+snowBallId).css({"left": left+'%',"top": top+'%'});
                }
            }

            var hel_c = $('.Hell_candy');
            if(data.hell_candy && hel_c.length === 0){
                if(data.hell_candy != '0'){
                    var r = Math.floor(rand(35,52));
                    $('<div />', {
                        "class": 'Hell_candy',
                        html : '<img src="/img/world/items/little/'+r+'.png">',
                        click: function() { hell_candy(r); }
                    }).appendTo('body');
                    var left, top;
                    if(!device.mobile()){
                        left = rand(1,75); top = rand(1,50);
                    } else {
                        left = rand(1,30); top = rand(1,58);
                    }
                    $('.Hell_candy').attr("data-type",r).css({"left": left+'%',"top": top+'%'});
                }
            } else if(data.hell_candy == '0') {
                hel_c.remove();
            }

            var hel_team = $('.Hell_team');
            if(data.hell_team && hel_team.length === 0){
                $('<div />', {
                    "class": 'Hell_team',
                    html : '<h2>На Вас напали!</h2><br><img src="/img/ivent/hell_team.png"><br>'
                }).appendTo('body');
                $('<div />', {
                    "class": 'buttonHell_Team',
                    html: 'Сразиться!',
                    click: function() { hell_team(1); }
                }).appendTo('.Hell_team');
                $('<div />', {
                    "class": 'buttonHell_Team vikup',
                    html: 'Откупиться за '+data.hell_team+'мон.',
                    click: function() { hell_team(2); }
                }).appendTo('.Hell_team');
            }

            if(data.present_box == 1 && $('.GiftOnline').length == 0) {
                $('<div />', {
                    "class": 'GiftOnline',
                    html: function() {
                        $('<div />', {"class": 'background blue'}).appendTo(this);
                        $('<div />', {"class": 'background purple'}).appendTo(this);
                        $('<div />', {"class": 'Text', html: 'Вы замечаете загадочный предмет...'}).appendTo(this);
                        $('<img />', {"src": '/img/world/question_neon.png'}).appendTo(this);
                        $('<div />', {
                            "class": 'Button',
                            html: 'Подобрать',
                            click: function() { GiftOnline(); }
                        }).appendTo(this);
                    }
                }).appendTo('body');
            }

            if(data.birthday && $('.GiftOnline').length == 0) {
                $('<div />', {
                    "class": 'GiftOnline',
                    html: function() {
                        $('<div />', {"class": 'background blue'}).appendTo(this);
                        $('<div />', {"class": 'background purple'}).appendTo(this);
                        $('<div />', {"class": 'Text birthday', html: 'С днем рождения, Poke Kara!'}).appendTo(this);
                        $('<img />', {
                            "class": 'birthday',
                            "src": 'https://www.clipartmax.com/png/full/179-1799796_clipart-pink-cake-png-image-gallery-yopriceville-high-birthday-cakes-clip-art.png'
                        }).appendTo(this);
                        $('<div />', {
                            "class": 'Button birthday',
                            html: 'Забрать подарок',
                            click: function() { GiftBirthday(); }
                        }).appendTo(this);
                    }
                }).appendTo('body');
            }

            if(data.serverTime){
                $('#map_time').html(data.serverTime);
            }
            var lvl = $('#LVLUSER').html();
            if(data.LVLuser && (data.LVLuser != lvl)){
                $('#LVLUSER').html(data.LVLuser);
            }
            if(data.WIDTHuser){
                $('#WIDTHLVLUSER, #WIDTHLVLUSER_TO').css('width',data.WIDTHuser+'%');
            }

            if(typeof ClassInfo !== "undefined" && data.server_ver){
                ClassInfo._upVer(data.server_ver);
            }

            if(data.battleInfo){
                if(typeof ClassBattle === "undefined" || !ClassBattle){
                    ClassBattle = new GameBattle(data.battleInfo);
                } else {
                    ClassBattle._open(data.battleInfo);
                }
            }

            if(data.tradeInfo){
                if(typeof ClassTrade === "undefined" || !ClassTrade){
                    ClassTrade = new Trade(data.tradeInfo);
                } else {
                    ClassTrade._parseData(data.tradeInfo);
                }
            } else if(typeof ClassTrade !== "undefined" && ClassTrade){
                ClassTrade._close(false);
                ClassTrade = null;
            }

            setTimeout(Game.updateUserLocation, 1000);
        }, 'json');
    },
	modals: {
		modalLoad: function(n) {
			$('.Modal').remove();
			$('<div />', {
				"class": 'Modal',
				html: '<div class="Title"><div class="Name">'+Lang.title[n]+'</div><div class="Info">'+Lang.titleInf[n]+'</div></div>'
			}).appendTo('body');
			$('<div />', {
				"class": 'Close',
				html: '<i class="fas fa-times"></i>',
				'click': function() {
					$('.Modal').remove();
				}
			}).appendTo('.Modal .Title');
		},
		modelLoad: function(d){
			$('.model').remove();
			$('<div />', {
				'class':'model'
			}).appendTo('body');
			$('<div />', {
				'class':'header',
				html: d
			}).appendTo('.model');
			$('<span />', {
				html: "<i class='fas fa-times'></i>",
				"click": function() {
					$('.model').remove();
				}
			}).appendTo('.model .header');
			$('<div />', {
				'class':'content-model',
			}).appendTo('.model');
      if(!device.mobile()){
				$('.model').draggabilly({
					handle: '.header',
					containment: true
				});
			}
		},
		itemsCategory: function() {
			var el_qw = $('<div />', {
							'class':'Button',
							'id': 'allItems',
							html: '<i class="far fa-bars"></i>',
							'onload': function() {
						        Tipped.create(this, 'Все');
					        },
							'click': function() {
								Game.modals.inventory('all'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qr = $('<div />', {
							'class':'Button',
							'id': 'modificatorItems',
							html: '<i class="far fa-arrow-alt-up"></i>',
							'onload': function() {
						        Tipped.create(this, 'Модификаторы');
					        },
							'click': function() {
								Game.modals.inventory('modificator'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qt = $('<div />', {
							'class':'Button',
							'id': 'eggItems',
							html: '<i class="far fa-egg"></i>',
							'onload': function() {
						        Tipped.create(this, 'Яйца');
					        },
							'click': function() {
								Game.modals.inventory('egg'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qy = $('<div />', {
							'class':'Button',
							'id': 'evolverItems',
							html: '<i class="far fa-dice-d12"></i>',
							'onload': function() {
						        Tipped.create(this, 'Эволверы');
					        },
							'click': function() {
								Game.modals.inventory('evolver'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qi = $('<div />', {
							'class':'Button',
							'id': 'craftItems',
							html: '<i class="far fa-hammer-war"></i>',
							'onload': function() {
						        Tipped.create(this, 'Крафтовые');
					        },
							'click': function() {
								Game.modals.inventory('craft'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qo = $('<div />', {
							'class':'Button',
							'id': 'potionItems',
							html: '<i class="far fa-flask-potion"></i>',
							'onload': function() {
						        Tipped.create(this, 'Регенераторы');
					        },
							'click': function() {
								Game.modals.inventory('potion'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qp = $('<div />', {
							'class':'Button',
							'id': 'ballItems',
							html: '<i class="far fa-dot-circle"></i>',
							'onload': function() {
						        Tipped.create(this, 'Покеболы');
					        },
							'click': function() {
								Game.modals.inventory('ball'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qa = $('<div />', {
							'class':'Button',
							'id': 'tmItems',
							html: '<i class="far fa-album-collection"></i>',
							'onload': function() {
						        Tipped.create(this, 'TM/TR');
					        },
							'click': function() {
								Game.modals.inventory('tm'),
								$('.tpd-tooltip').remove();
							}
						}),
				el_qd = $('<div />', {
							'class':'Button',
							'id': 'questItems',
							html: '<i class="far fa-ticket"></i>',
							'onload': function() {
						        Tipped.create(this, 'Квестовые');
					        },
							'click': function() {
								Game.modals.inventory('quest'),
								$('.tpd-tooltip').remove();
							}
						}),
						el_qu = $('<div />', {
    							'class':'Button',
    							'id': 'berryItems',
    							html: '<i class="far fa-apple-alt"></i>',
    							'onload': function() {
						        Tipped.create(this, 'Ягоды');
					        },
    							'click': function() {
    								Game.modals.inventory('berry');
    							}
    						}),
				el_qg = $('<div />', {
							'class':'Button',
							'id': 'otherItems',
							html: '<i class="far fa-ellipsis-h-alt"></i>',
							'onload': function() {
						        Tipped.create(this, 'Прочее');
					        },
							'click': function() {
								Game.modals.inventory('other'),
								$('.tpd-tooltip').remove();
							}
						}),
			el_zz = [el_qw, el_qr, el_qt, el_qy,  el_qi, el_qo, el_qp, el_qa, el_qd, el_qu, el_qg];
			$('.Inventory .Category').append(el_zz);
		},
		lombardCategory: function () {
  var $wrap = $('.Inventory.Lombard .Category');
  if (!$wrap.length) return;

  // если уже вставляли — выходим (не плодим дубли)
  if ($wrap.data('pk-lombard-cats-inited')) return;
  $wrap.data('pk-lombard-cats-inited', true);

  // набор категорий: [код, иконка, подпись, id]
  var CATS = [
    ['all',         'far fa-bars',               'Все',          'allItems'],
    ['pokemon',     'far fa-paw',                'Покемоны',     'pokemonItems'],
    ['modificator', 'far fa-arrow-alt-up',       'Модификаторы', 'modificatorItems'],
    ['egg',         'far fa-egg',                'Яйца',         'eggItems'],
    ['evolver',     'far fa-dice-d12',           'Эволверы',     'evolverItems'],
    ['craft',       'far fa-hammer-war',         'Крафтовые',    'craftItems'],
    ['potion',      'far fa-flask-potion',       'Регенераторы', 'potionItems'],
    ['ball',        'far fa-dot-circle',         'Покеболы',     'ballItems'],
    ['tm',          'far fa-album-collection',   'TM/TR',        'tmItems'],
    ['quest',       'far fa-ticket',             'Квестовые',    'questItems'],
    ['other',       'far fa-ellipsis-h-alt',     'Прочее',       'otherItems']
  ];

  // вспомогалки
  function saveCat(cat){ try{ localStorage.setItem('pk_lombard_cat', cat); }catch(_){ } }
  function loadCat(){ try{ return localStorage.getItem('pk_lombard_cat') || 'all'; }catch(_){ return 'all'; } }
  function activate(cat){
    $wrap.find('.Button').removeClass('Active active selected');
    $wrap.find('.Button[data-cat="'+cat+'"]').addClass('Active');
  }
  function run(cat){
    saveCat(cat);
    try { Game.modals.lombard(cat); } catch(_) { Game.modals.lombard('all'); }
    $('.tpd-tooltip').remove();
    activate(cat);
  }

  function makeBtn(cat, icon, label, id){
    var $b = $('<div/>', {
      id: id,
      'class': 'Button',
      'data-cat': cat,
      'role': 'button',
      'tabindex': 0,
      'title': label,                 // подсказка по умолчанию
      html: '<i class="'+icon+'"></i>'
    });

    // Tooltip Tipped — по первому наведению (если подключен)
    if (window.Tipped && typeof Tipped.create === 'function') {
      $b.one('mouseenter', function(){ try { Tipped.create(this, label); } catch(_){} });
    }

    // Клик
    $b.on('click', function(){ run($(this).data('cat')); });

    // Клавиатура (Enter / Space)
    $b.on('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); run($(this).data('cat')); }
    });

    return $b;
  }

  // Рендер кнопок
  var $frag = $(document.createDocumentFragment());
  for (var i=0; i<CATS.length; i++){
    $frag.append( makeBtn(CATS[i][0], CATS[i][1], CATS[i][2], CATS[i][3]) );
  }
  $wrap.append($frag);

  // Подсветить последнюю выбранную и НЕ открывать модал заново тут.
  // Модал открывается твоей кнопкой «Аукцион», которая прочитает сохранённую категорию.
  activate(loadCat());
},
questsCategory: function () {
  // стили "чипов" (как в мастерской)
  (function injectStyles () {
    var css = `
.AquaBook .Category .DiaryTabs{display:flex;flex-wrap:wrap;gap:9px;background:#fff;border:1px solid #e6eafe;border-radius:14px;padding:10px;box-shadow:0 12px 28px rgba(20,35,80,.10);margin-bottom:12px}
.AquaBook .Category .Button.Button--chip{appearance:none;border:1px solid #e6eafe;background:#f1f5ff;color:#2f4477;border-radius:999px;padding:7px 12px;font:800 13px/1 Nunito,Arial,sans-serif;cursor:pointer;user-select:none;transition:.12s ease}
.AquaBook .Category .Button.Button--chip:hover{transform:translateY(-1px)}
.AquaBook .Category .Button.Button--chip.is-active{background:#eaf1ff;border-color:#cfe0ff;color:#0e55b6;box-shadow:0 0 0 2px rgba(14,85,182,.08) inset}
@media (max-width:640px){.AquaBook .Category .Button.Button--chip{padding:6px 10px;font-size:12.5px}}
    `;
    var el = document.getElementById('diaryTabsStyles');
    if (el) el.textContent = css; else $('head').append('<style id="diaryTabsStyles">'+css+'</style>');
  })();

  // карта соответствия tab -> id кнопки
  var TAB_TO_ID = {
    location: 'locationBook',
    quests: 'questsBook',
    notes: 'notesBook',
    news: 'newsBook',
    stickers: 'stickersBook',
    // mission: 'missionBook' // если вернёшь "Задания"
  };

  var $cat = $('.AquaBook .Category');
  $cat.find('.DiaryTabs').remove();

  // установка активного с сохранением
  function setActiveByTab(tab){
    try { localStorage.setItem('diary.tab', tab); } catch(e){}
    var id = TAB_TO_ID[tab];
    if (!id) return;
    var $btn = $('#'+id);
    if ($btn.length){
      $btn.addClass('is-active').siblings('.Button--chip').removeClass('is-active');
    }
  }

  // чип-фабрика (id/клики как у тебя)
  function chip(opts){
    var $el = $('<div/>', {
      'class': 'Button Button--chip',
      'id': opts.id,
      'text': opts.title,
      'role': 'button',
      'tabindex': 0
    });
    $el.on('click', function(){
      setActiveByTab(opts.tabKey);
      opts.onClick();
    });
    $el.on('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $el.click(); }});
    return $el;
  }

  // кнопки (без "Задания")
  var el_wq = chip({ id:'locationBook', title:'Регион', tabKey:'location', onClick:function(){ Game.modals.diary('location'); }});
  var el_we = chip({ id:'questsBook',   title:'Квесты',  tabKey:'quests',   onClick:function(){ Game.modals.diary('quests'); }});
  var el_wg = chip({ id:'notesBook',    title:'Заметки',  tabKey:'notes',    onClick:function(){ Game.modals.diary('notes'); }});
  var el_wj = chip({ id:'newsBook',     title:'Новости друзей', tabKey:'news', onClick:function(){ Game.modals.diary('news'); }});
  var el_st = chip({ id:'stickersBook', title:'Коллекция', tabKey:'stickers', onClick:function(){ Game.modals.diary('stickers'); }});

  var $tabs = $('<div class="DiaryTabs"/>').append([el_wq, el_we, el_wg, el_wj, el_st]);
  $cat.append($tabs);

  // --- ХУК: перехватываем переключение вкладок, чтобы подсвечивать даже при внешних вызовах ---
  if (Game && Game.modals && typeof Game.modals.diary === 'function' && !Game.modals._diaryPatched) {
    Game.modals._diaryOrig = Game.modals.diary;
    Game.modals.diary = function(tab){
      try { localStorage.setItem('diary.tab', tab); } catch(e){}
      // отрисовка контента
      var res = Game.modals._diaryOrig.apply(this, arguments);
      // подсветка после вызова
      setTimeout(function(){ setActiveByTab(tab); }, 0);
      return res;
    };
    Game.modals._diaryPatched = true;
  }

  // при первичной отрисовке подсветим «текущую» вкладку:
  // 1) то, что запомнили в localStorage; 2) иначе таб по умолчанию 'location'
  var initial = (function(){
    try { return localStorage.getItem('diary.tab') || 'location'; } catch(e){ return 'location'; }
  })();
  setActiveByTab(initial);
},
		pokemons: function(){
  Game.modals.modalLoad(5);
  if((Math.round(offset = $('.MidMenu').offset().left) - 180) < 0) {
    var left = 0;
  }else{
    var left = (Math.round(offset = $('.MidMenu').offset().left) - 180);
  }
  $('.Modal').css('left',left+'px');
  
  $('<div />', {
    "class": 'modalLoad',
    html: '<center>'+mainLoader+'</center>'
  }).appendTo('.Modal');

  // Определение мобильного устройства
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                  ('ontouchstart' in window) || 
                  (navigator.maxTouchPoints > 0);

  $.post('/do/PokemonTeam', {type: 'load'}, function(data){
    // Убираем лоадер
    $('.modalLoad').remove();
    
    // Создаем контейнер для покемонов
    $('<div/>', {'class': 'Pokemons'}).appendTo('.Modal');
    
    // Добавляем покемонов
    $.each(data, function(){
      $('<div />',{
        'html'	: this.html,
        'class': 'PokemonBox '+this.start,
        'id': 'PokemonBox'+this.id,
        'data-pokemon-id': this.id
      }).appendTo('.Modal .Pokemons');
    });

    // Добавляем CSS стили для тултипов
    if (!$('#pokemon-list-tooltips-css').length) {
      $('<style id="pokemon-list-tooltips-css">\
        .tooltip-processed{}\
        .pokemon-tooltip-bar{cursor:help !important;position:relative;transition:opacity 0.2s ease}\
        .pokemon-tooltip-bar:hover{opacity:0.8}\
        @media (max-width: 768px), (pointer: coarse) {\
          .pokemon-tooltip-bar{cursor:pointer !important}\
          .pokemon-tooltip-bar::after{content:" 📊";font-size:10px;opacity:0.6}\
        }\
      </style>').appendTo('head');
    }

    // ДОБАВЛЯЕМ ТУЛТИПЫ после загрузки всех покемонов
    setTimeout(function() {
      $('.Modal .Pokemons .PokemonBox').each(function() {
        const $pokemonBox = $(this);
        const pokemonId = $pokemonBox.data('pokemon-id');
        
        // Найти данные покемона в исходном массиве
        let pokemonData = null;
        $.each(data, function() {
          if (this.id == pokemonId) {
            pokemonData = this;
            return false; // break
          }
        });

        if (!pokemonData) return;

        // ТУЛТИПЫ ДЛЯ HP
        const hpSelectors = [
          '.Bar.hp_proggresbar',
          '.hp_proggresbar', 
          '.HpBar',
          '[data-title*="HP"]',
          '.Bar:first'
        ];

        let hpTooltipAdded = false;
        hpSelectors.forEach(function(selector) {
          if (!hpTooltipAdded) {
            const $hpBars = $pokemonBox.find(selector).filter(':visible').not('.tooltip-processed');
            $hpBars.each(function() {
              const $hpBar = $(this);
              if ($hpBar.width() > 30) {
                $hpBar.addClass('tooltip-processed pokemon-tooltip-bar');
                
                // Извлекаем HP из разных источников
                let hpCurrent = pokemonData.hp || 0;
                let hpMax = pokemonData.maxHP || pokemonData.hpMax || 100;
                
                // Пытаемся извлечь из data-title
                const dataTitle = $hpBar.attr('data-title') || $hpBar.parent().attr('data-title') || '';
                const hpMatch = dataTitle.match(/HP:\s*(\d+)\s*\/\s*(\d+)/i);
                if (hpMatch) {
                  hpCurrent = parseInt(hpMatch[1]) || hpCurrent;
                  hpMax = parseInt(hpMatch[2]) || hpMax;
                }
                
                // Пытаемся извлечь из HTML текста
                if (!hpMatch) {
                  const $hpText = $pokemonBox.find('.HpText, .hp-text, [class*="hp"]').first();
                  if ($hpText.length) {
                    const hpTextMatch = $hpText.text().match(/(\d+)\s*\/\s*(\d+)/);
                    if (hpTextMatch) {
                      hpCurrent = parseInt(hpTextMatch[1]) || hpCurrent;
                      hpMax = parseInt(hpTextMatch[2]) || hpMax;
                    }
                  }
                }

                const hpPercent = Math.round((hpCurrent / hpMax) * 100);
                const hpTooltip = `HP: ${hpCurrent} / ${hpMax} (${hpPercent}%)`;
                
                if (isMobile) {
                  $hpBar.on('click.hpTooltip touchstart.hpTooltip', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    window.ModalShell && ModalShell.tip ? ModalShell.tip(hpTooltip, 'HP') : alert(hpTooltip);
                  });
                } else {
                  $hpBar.on('mouseenter.hpTooltip', function() {
                    $(this).attr('title', hpTooltip);
                  }).css('cursor', 'help');
                }
                
                hpTooltipAdded = true;
                console.log('✅ HP tooltip added:', hpTooltip, 'for pokemon', pokemonId);
              }
            });
          }
        });

        // ТУЛТИПЫ ДЛЯ EXP
        const expSelectors = [
          '.Bar.exp_progressbar',
          '.exp_progressbar',
          '.ExpBar',
          '[data-title*="Опыт"]',
          '[data-title*="EXP"]',
          '.Bar:eq(1)'
        ];

        let expTooltipAdded = false;
        expSelectors.forEach(function(selector) {
          if (!expTooltipAdded) {
            const $expBars = $pokemonBox.find(selector).filter(':visible').not('.tooltip-processed');
            $expBars.each(function() {
              const $expBar = $(this);
              if ($expBar.width() > 30) {
                $expBar.addClass('tooltip-processed pokemon-tooltip-bar');
                
                // Извлекаем EXP данные
                let expCurrent = pokemonData.exp || 0;
                let expNext = pokemonData.expNext || pokemonData.expToNext || pokemonData.exp_max || 1000;
                
                // Пытаемся извлечь из data-title
                const dataTitle = $expBar.attr('data-title') || $expBar.parent().attr('data-title') || '';
                const expMatch = dataTitle.match(/Опыт:\s*(\d+)\s*\/\s*(\d+)/i) || dataTitle.match(/EXP:\s*(\d+)\s*\/\s*(\d+)/i);
                if (expMatch) {
                  expCurrent = parseInt(expMatch[1]) || expCurrent;
                  expNext = parseInt(expMatch[2]) || expNext;
                }
                
                const expNeed = Math.max(0, expNext - expCurrent);
                const expPercent = Math.round((expCurrent / expNext) * 100);
                const expTooltip = `Опыт: ${expCurrent} / ${expNext} (${expPercent}%, нужно: ${expNeed})`;
                
                if (isMobile) {
                  $expBar.on('click.expTooltip touchstart.expTooltip', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    window.ModalShell && ModalShell.tip ? ModalShell.tip(expTooltip, 'Опыт') : alert(expTooltip);
                  });
                } else {
                  $expBar.on('mouseenter.expTooltip', function() {
                    $(this).attr('title', expTooltip);
                  }).css('cursor', 'help');
                }
                
                expTooltipAdded = true;
                console.log('✅ EXP tooltip added:', expTooltip, 'for pokemon', pokemonId);
              }
            });
          }
        });

        // ТУЛТИПЫ ДЛЯ HAPPINESS
        const happinessSelectors = [
          '.Bar.happy_progressbar',
          '.happy_progressbar',
          '.HappyBar',
          '.ProgressHappiness',
          '.ProgressHappy',
          '[data-title*="Счастье"]',
          '[data-title*="Happy"]',
          '.Bar:eq(2)'
        ];

        let happyTooltipAdded = false;
        happinessSelectors.forEach(function(selector) {
          if (!happyTooltipAdded) {
            const $happyBars = $pokemonBox.find(selector).filter(':visible').not('.tooltip-processed');
            $happyBars.each(function() {
              const $happyBar = $(this);
              $happyBar.addClass('tooltip-processed pokemon-tooltip-bar');
              
              // Извлекаем Happiness данные
              let happiness = pokemonData.happiness || pokemonData.happy || 0;
              
              // Пытаемся извлечь из data-title
              const dataTitle = $happyBar.attr('data-title') || $happyBar.parent().attr('data-title') || '';
              const happyMatch = dataTitle.match(/Счастье:\s*(\d+)\s*\/\s*255/i) || dataTitle.match(/Happy:\s*(\d+)\s*\/\s*255/i);
              if (happyMatch) {
                happiness = parseInt(happyMatch[1]) || happiness;
              }
              
              const happinessPercent = Math.round((happiness / 255) * 100);
              const happyTooltip = `Счастье: ${happiness} / 255 (${happinessPercent}%)`;
              
              if (isMobile) {
                $happyBar.on('click.happyTooltip touchstart.happyTooltip', function(e) {
                  e.stopPropagation();
                  e.preventDefault();
                  window.ModalShell && ModalShell.tip ? ModalShell.tip(happyTooltip, 'Счастье') : alert(happyTooltip);
                });
              } else {
                $happyBar.on('mouseenter.happyTooltip', function() {
                  $(this).attr('title', happyTooltip);
                }).css('cursor', 'help');
              }
              
              happyTooltipAdded = true;
              console.log('✅ Happiness tooltip added:', happyTooltip, 'for pokemon', pokemonId);
            });
          }
        });

        // УНИВЕРСАЛЬНЫЙ ПОИСК полосок прогресса как fallback
        if (!hpTooltipAdded || !expTooltipAdded || !happyTooltipAdded) {
          $pokemonBox.find('.Bar, .Progress, [style*="width"], [class*="bar"], [class*="progress"]')
            .filter(':visible')
            .not('.tooltip-processed')
            .each(function(index) {
              const $bar = $(this);
              const width = $bar.width();
              const height = $bar.height();
              
              // Определяем тип полоски по размеру и позиции
              if (width > 30 && height >= 4 && height <= 25) {
                $bar.addClass('tooltip-processed pokemon-tooltip-bar');
                
                let tooltip = '';
                if (index === 0 && !hpTooltipAdded) {
                  // Первая полоска - HP
                  const hpCurrent = pokemonData.hp || 0;
                  const hpMax = pokemonData.maxHP || pokemonData.hpMax || 100;
                  const hpPercent = Math.round((hpCurrent / hpMax) * 100);
                  tooltip = `HP: ${hpCurrent} / ${hpMax} (${hpPercent}%)`;
                } else if (index === 1 && !expTooltipAdded) {
                  // Вторая полоска - EXP
                  const expCurrent = pokemonData.exp || 0;
                  const expNext = pokemonData.expNext || pokemonData.exp_max || 1000;
                  const expNeed = Math.max(0, expNext - expCurrent);
                  const expPercent = Math.round((expCurrent / expNext) * 100);
                  tooltip = `Опыт: ${expCurrent} / ${expNext} (${expPercent}%, нужно: ${expNeed})`;
                } else if (index === 2 && !happyTooltipAdded) {
                  // Третья полоска - Happiness
                  const happiness = pokemonData.happiness || pokemonData.happy || 0;
                  const happinessPercent = Math.round((happiness / 255) * 100);
                  tooltip = `Счастье: ${happiness} / 255 (${happinessPercent}%)`;
                }
                
                if (tooltip) {
                  if (isMobile) {
                    $bar.on('click.universalTooltip touchstart.universalTooltip', function(e) {
                      e.stopPropagation();
                      e.preventDefault();
                      alert(tooltip);
                    });
                  } else {
                    $bar.on('mouseenter.universalTooltip', function() {
                      $(this).attr('title', tooltip);
                    }).css('cursor', 'help');
                  }
                  console.log('✅ Universal tooltip added:', tooltip, 'for pokemon', pokemonId);
                }
              }
            });
        }
      });

      console.log('🔧 Pokemon list tooltips processing completed for', Object.keys(data).length, 'pokemon');
      
    }, 400); // Задержка для полной загрузки HTML

    // Добавляем обработчик клика на покемонов для переключения
    $('.Modal .Pokemons').on('click.pokemonSwitch', '.PokemonBox', function(e) {
      // Проверяем что клик не по полоскам с тултипами
      if ($(e.target).hasClass('pokemon-tooltip-bar') || $(e.target).closest('.pokemon-tooltip-bar').length) {
        return;
      }
      
      const pokemonId = $(this).data('pokemon-id');
      if (pokemonId) {
        Game.pokemonTeamTabs(pokemonId, 'info');
      }
    });

    // Обработчик закрытия модального окна
    $('.Modal').on('click.modalClose', function(e) {
      if ($(e.target).hasClass('Modal')) {
        Game.loaders.worldClose();
      }
    });

    console.log('✅ Pokemon list loaded successfully with', Object.keys(data).length, 'pokemon');
    
  }, 'json').fail(function(xhr, status, error) {
    // Обработка ошибок загрузки
    $('.modalLoad').remove();
    console.error('❌ Failed to load pokemon list:', error);
    Game.notifications.main('Ошибка загрузки списка покемонов', 'error');
    Game.loaders.worldClose();
  });
},
		inventory: function(l) {
			$.ajax({
				url: "/do/modal",
				type: "POST",
				data: "type=inventory&category="+l,
				beforeSend: function(){
					Game.modals.modalLoad(0);
					$('.Modal').css('left',(Math.round(offset = $('.MidMenu').offset().left) - 35)+'px');
					$('<div />', {
        "class": 'modalLoad',
        html: '<center>'+mainLoader+'</center>'
        }).appendTo('.Modal');
				},
				success: function (response) {
				    $('.modalLoad').hide();
						response = (typeof response === 'string') ? JSON.parse(response) : response;
						$('<div />', {
							"class": 'Inventory'
						}).appendTo('.Modal');
						$('<div />', {
							"class": 'Category'
						}).appendTo('.Inventory');
						$('<div />', {
							"class": 'Items',
              "id": 'Items'
						}).appendTo('.Inventory');
						$.each(response['eggList'], function(x,y){
							$('<div />', {
								"class": 'Item',
								"click": function() {
									itemOpen(this,y['ctgMdl'],y['id'],'Яйцо '+y['name'],y['gen']+'<br />'+y['reborn'],151,1,0,false,false,false,true,false,false,y['ustatus'],true,y['loc'],false,false,y['basenum']);
								},
								html: "<div class='blockrait egg'></div><img id='imgItem' src='/img/world/items/little/151.png'><div class='Name'>"+y['name']+"</div>"
							}).appendTo('.Inventory .Items');
						});
						// $.each(response['itemList'], function(x,y){
							// $('<div />', {
								// "class": 'Item',
								// "click": function() {
									// itemOpen(this,y['ctgMdl'],y['id'],y['name'],y['about'],y['img'],y['count'],y['itemWeight'],y['use'],y['dress'],a,y['trade'],y['give'],false,y['ustatus']);
								// },
								// html: '<img id="imgItem" src="/img/world/items/little/'+y['img']+'.png" onerror="$(this).attr(\'src\',\'/img/world/items/little/0.png\');"><span class="Bot">'+y['count2']+'</span>'
							// }).appendTo('.InvItems');
						// });
						$('.Inventory .Items').append(response['html']);
            $('<div />', {
							"class": 'AquaButton'
						}).appendTo('.Inventory');
            $('<div />', {
							"class": 'Button LEFT',
              html: '<i class="fal fa-shopping-cart"></i> Shop',
              "click": function() {
                openModal('shop');
              }
              }).appendTo('.AquaButton');
              //$('<div />', {
							//"class": 'Button LEFT',
             // html: '<i class="fal fa-hammer"></i> Починка',
             // "click": function() {
             //   openModal('repair');
             // }
             // }).appendTo('.AquaButton');

             /* === Кнопка "Торговая площадка" — всегда на панели === */
(function ($) {
  if (window.__pkMarketBtnMounted) return;
  window.__pkMarketBtnMounted = true;

  var BTN_ID = 'btn-market';

  // Создаём (или берём уже созданную) кнопку
  function buildButton() {
    var $btn = $('#' + BTN_ID);
    if ($btn.length) return $btn;

    $btn = $('<div/>', {
      id: BTN_ID,
      class: 'Button LEFT',
      html: '<i class="far fa-store"></i> Торговая площадка',
      title: 'Торговая площадка'
    }).on('click', function () {
      try {
        var cat = localStorage.getItem('market_cat')
               || localStorage.getItem('pk_lombard_cat')
               || 'all';
        if (window.Game && Game.modals && typeof Game.modals.market === 'function') {
          Game.modals.market(cat);
        }
      } catch (e) {
        if (window.Game && Game.modals && typeof Game.modals.market === 'function') {
          Game.modals.market('all');
        }
      }
      $('.tpd-tooltip').remove();
    });

    return $btn;
  }

  // Вставляем кнопку в первую найденную .AquaButton
  function placeButton() {
    var $bar = $('.AquaButton').first();
    if (!$bar.length) return;

    var $btn = buildButton();
    if (!$btn.parent().is($bar)) {
      $btn.detach().appendTo($bar);
    }
  }

  // Ставим сразу как только можем
  $(placeButton);            // DOM ready
  $(window).on('load', placeButton); // на всякий случай после загрузки

  // Держим кнопку «приклеенной» без setInterval
  var obs = new MutationObserver(function (list) {
    var need = false;
    for (var i = 0; i < list.length; i++) {
      var m = list[i];
      if (m.type !== 'childList') continue;

      // Появилась/изменилась панель или пропала кнопка
      if ($(m.addedNodes).filter('.AquaButton').length ||
          $(m.removedNodes).filter('#' + BTN_ID).length ||
          $(m.target).is('.AquaButton')) {
        need = true; break;
      }
    }
    if (need) placeButton();
  });
  obs.observe(document.body, { childList: true, subtree: true });
})(jQuery);



              $('<div />', {
							"class": 'Money RIGHT',
              html: '<span>'+response['moneyyoy']+'</span><div></div>',
              "onload": function() {
                Tipped.create(this,'Ваше количество генкар');
              }
              }).appendTo('.AquaButton');

						Game.modals.itemsCategory();
						$('.Inventory .Category').find('#'+l+'Items').addClass('active');

						Game.loaders.worldClose();
				}
			});
		},
		lombard: function(l) {
			$.ajax({
				url: "/do/lombard",
				type: "POST",
				data: "type=open&category="+l,
				beforeSend: function(){
					Game.modals.modalLoad(7);
					$('.Modal').css('left',(Math.round(offset = $('.MidMenu').offset().left) - 35)+'px');
					$('<div />', {
        "class": 'modalLoad',
        html: '<center>'+mainLoader+'</center>'
        }).appendTo('.Modal');
				},
				success: function (response) {
				    $('.modalLoad').hide();
						response = (typeof response === 'string') ? JSON.parse(response) : response;
						$('<div />', {
							"class": 'Inventory Lombard'
						}).appendTo('.Modal');
						$('<div />', {
							"class": 'Category'
						}).appendTo('.Inventory');
						$('<div />', {
							"class": 'Items',
              "id": 'Items'
						}).appendTo('.Inventory');

						$('.Inventory.Lombard .Items').append(response['html']);

                        $('<div />', {
							"class": 'AquaButton'
						}).appendTo('.Inventory');
            $('<div />', {
							"class": 'Button LEFT',
              html: '<i class="fal fa-shopping-cart"></i> Донат',
              "click": function() {
                openModal('shop');
              }
              }).appendTo('.AquaButton');
              $('<div />', {
							"class": 'Button LEFT',
              html: '<i class="fal fa-backpack"></i> Инвентарь',
              "click": function() {
                Game.modals.inventory('all');
              }
              }).appendTo('.AquaButton');

              $('<div />', {
							"class": 'Money RIGHT',
              html: '<span>'+response['money']+'</span><div></div>',
              "onload": function() {
                Tipped.create(this,'Ваше количество генкар');
              }
              }).appendTo('.AquaButton');

						Game.modals.lombardCategory();
						$('.Inventory.Lombard .Category').find('#'+l+'Items').addClass('active');

						Game.loaders.worldClose();
				}
			});
		},
		diary: function(i,s=false) {
			$.ajax({
				url: "/do/modal",
				type: "POST",
				data: "type=diary&category="+i+"&pokID="+s,
				beforeSend: function(){
					Game.modals.modalLoad(1);
					$('.Modal').css('left',(Math.round(offset = $('.MidMenu').offset().left) + 300)+'px');
					$('<div />', {
        "class": 'modalLoad',
        html: '<center>'+mainLoader+'</center>'
}).appendTo('.Modal');
},
success: function (response) {
  response = (typeof response === 'string') ? JSON.parse(response) : response;
  $('<div />', {
    "class": 'AquaBook'
  }).appendTo('.Modal');

  $('<div />', {
    "class": 'Category'
  }).appendTo('.AquaBook');

// Функция открытия разных разделов дневника
Game.modals.questsCategory();

$('.Quests').remove(); // Удаляем прежний контейнер, чтобы не было дубликатов
$('<div />', {
    "class": 'Quests'
}).appendTo('.AquaBook');

// --- Обработка Лиги чемпионов ---
if (i === 'championsLeague') {
    $('.Quests').html('<div class="loader">Загрузка...</div>');
    $.post('/do/champions_league.php', { type: 'champions_league' }, function(data) {
        var resp;
        try { resp = typeof data === 'string' ? JSON.parse(data) : data; } catch(e) { resp = { html: data }; }
        $('.Quests').html(resp.html);
    });
    $('.AquaBook .Category .Button').removeClass('active');
    $('.AquaBook .Category').find('#championsLeagueBook').addClass('active');
    $('.modalLoad').hide();
    return;
}

// --- Коллекция стикеров ---
if (i === 'stickers') {
    $('.Quests').html('<div class="loader">Загрузка коллекции...</div>');
    // Можно изменить collection_id на нужный или получать динамически
    $.post('/do/stickers.php', { action: 'stickers', collection_id: 1 }, function(data) {
        var resp;
        try { resp = typeof data === 'string' ? JSON.parse(data) : data; } catch(e) { resp = { html: data }; }
        $('.Quests').html(resp.html);

        // После вставки альбома навесить обработчики на табы коллекций
        if (typeof bindAlbumTabs === "function") bindAlbumTabs();
    });
    $('.AquaBook .Category .Button').removeClass('active');
    $('.AquaBook .Category').find('#stickersBook').addClass('active');
    $('.modalLoad').hide();
    return;
}


/* ========== Quests UI v9.2 (регионы + независимый tooltip + награды через questChest) ========== */
/* 1) Стили (подключаются один раз) */
(function injectQuestsCss(){
  if (document.getElementById('quests-css-v9')) return;
  const css = `
  :root { --q-c1:#14233b; --q-c2:#37527e; --q-ac:#0e55b6; --q-b:#e8eefc; --q-bg:#f6f8ff; --q-card:#ffffff; }

  /* --- Регионы --- */
  .q-region{margin:28px 0}
  .q-region + .q-region{border-top:1px dashed #e8ecff;padding-top:22px}
  .q-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:0 6px 14px}
  .q-title{font-weight:900;font-size:22px;color:var(--q-c1);text-transform:uppercase;letter-spacing:.2px}
  .q-tabs{display:flex;gap:10px;flex-wrap:wrap}
  .q-tab{background:var(--q-bg);border:1px solid #e4e9ff;color:var(--q-c2);padding:6px 10px;border-radius:10px;
         font-weight:700;font-size:13px;cursor:pointer;transition:.12s}
  .q-tab:hover{background:#eef4ff}
  .q-tab.active{background:#eaf1ff;border-color:#cfe0ff;color:var(--q-ac);box-shadow:0 0 0 2px rgba(15,85,182,.06) inset}
  .q-tab .cnt{opacity:.65;margin-left:4px}

  /* --- Сетка и карточки --- */
  .q-grid{display:grid;grid-template-columns:repeat(4,140px);gap:16px}
  @media(max-width:980px){.q-grid{grid-template-columns:repeat(3,140px)}}
  @media(max-width:680px){.q-grid{grid-template-columns:repeat(2,140px)}}
  .q-card{position:relative;background:var(--q-card);border:1px solid var(--q-b);border-radius:12px;overflow:hidden;
          height:140px;cursor:pointer;transition:transform .12s, box-shadow .12s}
  .q-card:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(25,40,72,.12)}
  .q-img,.q-img img{width:100%;height:100%;object-fit:cover;display:block}
  .q-overlay{position:absolute;inset:0;background:linear-gradient(to top, rgba(10,17,34,.22), rgba(10,17,34,0) 60%)}
  .q-diff{position:absolute;left:8px;bottom:8px;font-size:12px;font-weight:800;padding:2px 8px;border-radius:999px;background:#fff;
          border:1px solid #e6eefb;color:#2b3a55;box-shadow:0 1px 0 rgba(0,0,0,.03)}
  .q-diff.easy{background:#eafff3;border-color:#c9f1de;color:#1d9657}
  .q-diff.mid{background:#fff6e8;border-color:#ffe5be;color:#b86e00}
  .q-diff.hard{background:#ffecee;border-color:#ffd2d7;color:#c13a3a}

  /* статусы */
  .q-done{position:absolute;right:8px;top:8px;width:24px;height:24px;border-radius:50%;background:#27c36a;border:1px solid #bfead3;
          display:flex;align-items:center;justify-content:center;box-shadow:0 1px 0 rgba(0,0,0,.05)}
  .q-done i{color:#fff;font-size:13px}
  .q-badge{position:absolute;right:8px;top:8px;font-size:11px;font-weight:800;border-radius:999px;padding:2px 8px;border:1px solid}
  .q-badge.new{background:#f6f8ff;border-color:#e4e9ff;color:#5c6781}
  .q-badge.progress{background:#eaf2ff;border-color:#cfe0ff;color:#0e55b6}

  /* --- Tooltip (независимый) --- */
  .q-tooltip{position:fixed;z-index:100000;background:#fff;border:1px solid var(--q-b);border-radius:10px;
             box-shadow:0 10px 24px rgba(15,20,50,.16);padding:10px;max-width:320px;pointer-events:none}
  .q-tooltip .t{font-weight:900;margin-bottom:6px;color:var(--q-c1)}
  .q-tooltip .m{font-size:12px;color:#51617d;margin-top:3px;display:flex;gap:6px;align-items:center}
  .q-tooltip .dot{width:8px;height:8px;border-radius:50%}
  .q-tooltip .dot.easy{background:#1d9657}.q-tooltip .dot.mid{background:#b86e00}.q-tooltip .dot.hard{background:#c13a3a}

  /* --- Детали (questsList) --- */
  .qt-top{display:flex;align-items:center;gap:10px;margin:6px 0 14px}
  .qt-back{cursor:pointer;font-weight:900;color:var(--q-ac)} .qt-back:hover{text-decoration:underline}
  .qt-name{font-weight:900;font-size:22px;color:var(--q-c1);margin-left:6px}
  .qt-progress{margin-left:auto}
  .qt-reward.is-item .ico{ cursor:pointer; }


  .qt-hero{display:grid;grid-template-columns:200px 1fr;gap:16px;margin-bottom:12px}
  @media(max-width:900px){.qt-hero{grid-template-columns:180px 1fr}}
  @media(max-width:720px){.qt-hero{grid-template-columns:150px 1fr}}
  @media(max-width:540px){.qt-hero{grid-template-columns:1fr}}
  .qt-hero .img{border:1px solid var(--q-b);border-radius:14px;overflow:hidden;background:#fff;width: 200px;max-height:133px}
  .qt-hero .img img{width:100%;height:100%;object-fit:contain;display:block;background:#fff}

  .qt-block{background:#fff;border:1px solid var(--q-b);border-radius:14px;padding:14px}
  .qt-block + .qt-block{margin-top:10px}
  .qt-block .ttl{font-weight:900;color:var(--q-c1);margin-bottom:8px}

  /* описание */
  .qt-desc{line-height:1.55;max-height:160px;overflow:auto}
  .qt-desc::-webkit-scrollbar{width:8px;height:8px}
  .qt-desc::-webkit-scrollbar-thumb{background:#e0e6fb;border-radius:6px}

  /* сведения (чипы) */
  .qt-meta{display:flex;flex-wrap:wrap;gap:8px}
  .qt-chip{display:inline-flex;align-items:center;gap:8px;background:#f7f9ff;border:1px solid var(--q-b);color:#29405f;
           font-size:12px;font-weight:700;border-radius:999px;padding:6px 10px}
  .qt-chip.easy{background:#eafff3;border-color:#c9f1de;color:#1d9657}
  .qt-chip.mid{background:#fff6e8;border-color:#ffe5be;color:#b86e00}
  .qt-chip.hard{background:#ffecee;border-color:#ffd2d7;color:#c13a3a}

  /* награды (плитки) */
  .qt-rewards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  @media(max-width:560px){.qt-rewards{grid-template-columns:1fr}}
  .qt-reward{display:flex;align-items:center;gap:10px;border:1px dashed var(--q-b);border-radius:12px;padding:10px;background:#fafcff}
  .qt-reward .ico{width:32px;height:32px;display:grid;place-items:center;border-radius:8px;background:#f1f5ff;border:1px solid var(--q-b)}
  .qt-reward .ico img{width:24px;height:24px}
  .qt-reward .txt{flex:1 1 auto;color:#2b3a55}
  .qt-reward .cnt{font-weight:900;color:#1d9657}
  .qt-reward.cta{justify-content:center;cursor:pointer;background:#f7fbff;border-style:solid;border-color:#cfe0ff;color:#0e55b6}
  .qt-reward.cta:hover{box-shadow:0 4px 14px rgba(15,85,182,.12)}
  .qt-reward.cta i{margin-right:8px}

  /* шаги — лента */
  .qt-steps{position:relative;margin-top:12px}
  .qt-steps:before{content:"";position:absolute;left:12px;top:0;bottom:0;width:2px;background:#e8eefc}
  .qt-step{position:relative;margin-left:24px;margin-bottom:10px}
  .qt-step:last-child{margin-bottom:0}
  .qt-step:before{content:"";position:absolute;left:-14px;top:10px;width:10px;height:10px;border-radius:50%;background:#d4defb;border:2px solid #fff;box-shadow:0 0 0 2px #e8eefc}
  .qt-step .box{background:#fff;border:1px solid var(--q-b);border-radius:12px;padding:12px}
  .qt-step .title{font-weight:800;margin-bottom:6px;color:#2b3a55}
  `;
  const s = document.createElement('style');
  s.id = 'quests-css-v9';
  s.textContent = css;
  document.head.appendChild(s);
})();

/* 2) Утилиты */
const qIsTouch = 'ontouchstart' in window || navigator.maxTouchPoints>0;
function qEsc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
function qDiffInfo(lvl=''){const t=(''+lvl).toLowerCase(); if(t.includes('лег')||t==='1')return{txt:'легко',cls:'easy'};
  if(t.includes('сред')||t==='2')return{txt:'средне',cls:'mid'}; return{txt:'сложно',cls:'hard'};}
function qRegionFrom(loc=''){const s=(loc||'').split(/[-–—>→]/)[0].trim(); return s || 'ПРОЧЕЕ';}

/* 2.1) Независимый tooltip */
window.__questsTipEl = window.__questsTipEl || null;
function qHideTip(){ if (window.__questsTipEl){ window.__questsTipEl.remove(); window.__questsTipEl = null; } }
function qShowTip(el, html){
  qHideTip();
  const $t = $('<div class="q-tooltip"></div>').html(html).appendTo(document.body);
  const r = el.getBoundingClientRect();
  const x = Math.min(window.innerWidth - $t.outerWidth() - 8, Math.max(8, r.left + (r.width-$t.outerWidth())/2));
  const y = r.bottom + 10;
  $t.css({left:x+'px', top:y+'px'});
  window.__questsTipEl = $t;
}
$(window).on('scroll resize', qHideTip);
$(document).on('click', qHideTip);

/* 3) СПИСОК КВЕСТОВ */
if (i === 'quests') {
  const list = response['questList'] || {};
  const groups = {};
  Object.values(list).forEach(q => (groups[qRegionFrom(q.location)] ||= []).push(q));
  const $root = $('.Quests').empty();

  Object.entries(groups).forEach(([region, arr])=>{
    arr.sort((a,b)=>a.id-b.id);
    const counts = {all:arr.length, new:0, progress:0, done:0};
    arr.forEach(q => q.check===1 ? counts.done++ : q.check===2 ? counts.progress++ : counts.new++);

    const $region = $(`
      <div class="q-region">
        <div class="q-head">
          <div class="q-title">${region}</div>
          <div class="q-tabs">
            <button class="q-tab active" data-filter="all">Все задания <span class="cnt">(${counts.all})</span></button>
            <button class="q-tab" data-filter="new">Не начатые <span class="cnt">(${counts.new})</span></button>
            <button class="q-tab" data-filter="progress">Выполняемые <span class="cnt">(${counts.progress})</span></button>
            <button class="q-tab" data-filter="done">Пройденные <span class="cnt">(${counts.done})</span></button>
          </div>
        </div>
        <div class="q-grid"></div>
      </div>
    `).appendTo($root);

    const $grid = $region.find('.q-grid');

    arr.forEach(q=>{
      const st = (q.check===1?'done':(q.check===2?'progress':'new'));
      const d  = qDiffInfo(q.lvl);
      const badge = (st==='done')
        ? '<div class="q-done"><i class="fas fa-check"></i></div>'
        : `<div class="q-badge ${st}">${st==='progress'?'В процессе':'Не начат'}</div>`;

      const tipHtml = `
        <div class="t">${qEsc(q.name)}</div>
        <div class="m">⭐ ${qEsc(q.exp)} опыта</div>
        <div class="m">📍 ${qEsc(q.location||'')}</div>
        <div class="m">⏱ ~${qEsc(q.time||'')}</div>
        <div class="m"><span class="dot ${d.cls}"></span> ${d.txt}</div>`;

      const $card = $(`
        <div class="q-card ${st}" data-status="${st}" title="${qEsc(q.name)}">
          <div class="q-img">
            <img src="/img/quests/${q.id}.png" onerror="this.src='/img/quests/0.png'">
            <div class="q-overlay"></div>
          </div>
          <div class="q-diff ${d.cls}">${d.txt}</div>
          ${badge}
        </div>
      `).appendTo($grid);

      if (!qIsTouch) {
        const el = $card.get(0);
        let hideTimer=null;
        $card.on('mouseenter', ()=>{ clearTimeout(hideTimer); qShowTip(el, tipHtml); });
        $card.on('mouseleave', ()=>{ hideTimer=setTimeout(qHideTip, 80); });
      }
      $card.on('click', ()=>{ qHideTip(); Game.modals.diary('questsList', q.id); });
    });

    $region.find('.q-tab').on('click', function(){
      const f = $(this).data('filter');
      $(this).siblings().removeClass('active'); $(this).addClass('active');
      $grid.children('.q-card').each(function(){ $(this).toggle( f==='all' || $(this).data('status')===f ); });
    });
  });
}

/* 4) ДЕТАЛИ КВЕСТА (questsList): компактная картинка, описание, сведения, награды под описанием (через questChest), лента шагов */
else if (i === 'questsList') {
  qHideTip();

  const name   = response['name'] || 'Квест';
  const img    = response['img']  || '/img/quests/0.png';
  const about  = response['about'] || '';
  const steps  = response['questList'] || {};
  const meta   = response['meta'] || null;     // {exp, time, location, difficulty}
  const rewards = response['rewards'] || [];   // если сервер уже отдал состав — рисуем сразу

  const $root = $('.Quests').empty();
  const $wrap = $('<div class="QuestOne"></div>').appendTo($root);

  /* Шапка */
  const $top = $(`
    <div class="qt-top">
      <div class="qt-back">&laquo;&laquo; Назад</div>
      <div class="qt-name">${qEsc(name)}</div>
      <div class="qt-progress">${response['progress'] || ''}</div>
    </div>
  `).appendTo($wrap);
  $top.find('.qt-back').on('click', ()=> { qHideTip(); Game.modals.diary('quests'); });

  /* Хедер */
  const $hero = $(`
    <div class="qt-hero">
      <div class="img"><img src="${img}" alt="${qEsc(name)}" onerror="this.src='/img/quests/0.png'"></div>
      <div>
        <div class="qt-block">
          <div class="ttl">Описание</div>
          <div class="qt-desc">${about}</div>
        </div>
        <div class="qt-block">
          <div class="ttl">Награды</div>
          <div class="qt-rewards"></div>
        </div>
      </div>
    </div>
  `).appendTo($wrap);

  /* Сведения (чипы) */
  const $meta = $hero.find('.qt-meta');
  if (meta && (meta.exp || meta.location || meta.time || meta.difficulty)) {
    if (meta.exp)      $meta.append(`<span class="qt-chip" title="Опыт"><i class="fas fa-star"></i> ${qEsc(meta.exp)} опыта</span>`);
    if (meta.location) $meta.append(`<span class="qt-chip" title="Локация"><i class="fas fa-map-marker-alt"></i> ${qEsc(meta.location)}</span>`);
    if (meta.time)     $meta.append(`<span class="qt-chip" title="Время"><i class="fas fa-clock"></i> ~${qEsc(meta.time)}</span>`);
    if (meta.difficulty){
      const d = qDiffInfo(meta.difficulty);
      $meta.append(`<span class="qt-chip ${d.cls}" title="Сложность"><i class="fas fa-trophy"></i> ${d.txt}</span>`);
    }
  }

  /* Награды */
  const $rewards = $hero.find('.qt-rewards');
  const addReward = (icoHtml, text, count) => {
    $rewards.append(`
      <div class="qt-reward">
        <div class="ico">${icoHtml}</div>
        <div class="txt">${qEsc(text)}</div>
        ${count ? `<div class="cnt">x${qEsc(count)}</div>` : ``}
      </div>
    `);
  };

  if (Array.isArray(rewards) && rewards.length) {
  rewards.forEach(r=>{
    if (r && r.item_id) {
      // предмет с картинкой и кликом
      const $card = $(`
        <div class="qt-reward is-item" data-item="${r.item_id}">
          <div class="ico">
            <img src="/img/world/items/little/${r.item_id}.png" onerror="this.src='/img/world/items/little/undefined.png'">
          </div>
          <div class="txt">${qEsc(r.text || '')}</div>
          ${r.count ? `<div class="cnt">x${qEsc(r.count)}</div>` : ``}
        </div>
      `);
      // клик только по иконке
      $card.find('.ico').on('click', (e)=>{
        e.stopPropagation();
        if (typeof itemOpenMore === 'function') {
          itemOpenMore(r.item_id);
        } else if (typeof issetAll === 'function') {
          issetAll(r.item_id, 'itemInfo'); // мягкий фолбэк
        }
      });
      $rewards.append($card);
    } else if (r && r.icon) {
      const iconMap = {star:'<i class="fas fa-star"></i>', coin:'<i class="fas fa-coins"></i>', gift:'<i class="fas fa-gift"></i>', trophy:'<i class="fas fa-trophy"></i>'};
      addReward(iconMap[r.icon] || '<i class="fas fa-gift"></i>', r.text || '', r.count || '');
    } else if (typeof r === 'string') {
      addReward('<i class="fas fa-gift"></i>', r, '');
    } else {
      addReward('<i class="fas fa-gift"></i>', 'Награда', '');
    }
  });
} else {
  // плашка "Показать состав наград" остаётся как раньше
  const $cta = $(`
    <div class="qt-reward cta" role="button" tabindex="0">
      <i class="fas fa-box-open"></i> Показать состав наград
    </div>
  `);
  $cta.on('click keypress', (e)=>{
    if (e.type==='click' || e.key==='Enter' || e.key===' ') {
      issetAll(response['id'], 'questChest');
    }
  });
  $rewards.append($cta);
}


  /* Лента шагов */
  const $steps = $('<div class="qt-steps"></div>').appendTo($wrap);
  Object.values(steps).forEach(st=>{
    $steps.append(`
      <div class="qt-step">
        <div class="box">
          <div class="title">Запись ${qEsc(st.step)}</div>
          <div class="text">${st.text}</div>
        </div>
      </div>
    `);
  });



// ... (Остальные ветки else if для других типов i — news, turnir, location, notes, drop и т.д.)
// Не забудьте выделять активную кнопку для каждого типа:
$('.AquaBook .Category .Button').removeClass('active');
var btnId = (i === 'questsList') ? '#questsBook' : '#' + i + 'Book';
$('.AquaBook .Category').find(btnId).addClass('active');
$('.modalLoad').hide();



						}else if(i == 'news'){
              $('<div />', {
								"class": 'News',
                html: response['newsBook']
							}).appendTo('.Quests');
              // $.each(response['newsList'], function(x,y){
							// 	$('<div />', {
							// 		"class": 'New',
							// 		html: ''
							// 	}).appendTo('.Quests .News');
							// });
						}
						else if(i == 'turnir'){
              $('<div />', {
								"class": 'Turnir',
                html: response['turnBook']
							}).appendTo('.Quests');
              // $.each(response['newsList'], function(x,y){
							// 	$('<div />', {
							// 		"class": 'New',
							// 		html: ''
							// 	}).appendTo('.Quests .News');
							// });
						}else if(i == 'location'){
              $('<div />', {
								"class": 'LocWeather',
                html: response['htmlWeather']
							}).appendTo('.Quests');
              
                  Tipped.create('.fa-sunrise', 'Утро');
                  Tipped.create('.fa-sun', 'День');
                  Tipped.create('.fa-sunset', 'Вечер');
                  Tipped.create('.fa-moon', 'Ночь');
                  Tipped.create('.fa-eclipse', 'Круглосуточно');
                
							
						}else if(i == 'notes') {
              $('<div />', {
								"class": 'Notes',
                html: response['html']
							}).appendTo('.Quests');
							$('#notesUsers').val(response['notes']);
            }else if(i == 'drop') {
              $('<div />', {
								"class": 'Drop',
                html: response['html']
							}).appendTo('.Quests');
            }
            if(i == 'questsList') {
              i = 'quests';
            }
						$('.AquaBook .Category').find('#'+i+'Book').addClass('active');

						$('.modalLoad').hide();
				}
			});
		},liveInit: function() {
        if (!window.GameSocket || !GameSocket.socket) return;

        // --- LIVE: пользователи на локации ---
        GameSocket.socket.on("location_users", function(payload){
            // payload: { location_id, users, hash }
            if(typeof md5locList === "undefined" || md5locList != payload.hash){
                md5locList = payload.hash;
                if(typeof ClassInfo !== "undefined" && ClassInfo){
                    ClassInfo._upUsrLoc(payload.users, (window.lastUsersAtNotice || null));
                }
            }
        });

        // --- LIVE: бафы ---
        GameSocket.socket.on("buffs_update", function(data){
            if($('.BuffsDropdown').length === 0) {
                $('.TopMenu .LeftMenu').html(
                    '<div class="BuffsDropdown">'+
                        '<button class="BuffsDropdown-btn" id="BuffsDropdownBtn" title="Ваши активные эффекты">'+
                          '<i class="fas fa-flask"></i>'+
                          '<span class="BuffsDropdown-count" id="BuffsCount">0</span>'+
                        '</button>'+
                        '<div class="BuffsDropdown-list" id="BuffsDropdownList"></div>'+
                    '</div>'
                );
                Game.BuffsDropdown.bind();
            }
            Game.BuffsDropdown.render(data);
        });

        // --- LIVE: инвентарь и деньги ---
        GameSocket.socket.on("inventory_update", function(data){
            if ($('.AquaButton .Money.RIGHT span').length) {
                $('.AquaButton .Money.RIGHT span').text(data.money);
            }
        });

        // --- LIVE: погода ---
        GameSocket.socket.on("weather_update", function(data){
            if (window.updateWeather && typeof window.updateWeather === 'function') {
                window.updateWeather(data);
            }
        });

        // --- LIVE: обновление денег (если отдельное событие) ---
        GameSocket.socket.on("money_update", function(data){
            if ($('.AquaButton .Money.RIGHT span').length) {
                $('.AquaButton .Money.RIGHT span').text(data.money);
            }
        });
		},
		clans: function() {
    $.ajax({
        url: "/do/clanAction.php",
        type: "POST",
        data: {object: "clansList"},
        beforeSend: function(){
            Game.modals.modalLoad(3);
        },
        success: function (response) {
            if (typeof response === "string") {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    response = { error: 1, text: "Ошибка загрузки списка кланов." };
                }
            }
            if (response && response.error) {
                Game.modals.modalLoad(0);
                $('.Modal').html('<div style="padding:14px;color:#b91c1c;"><b>Ошибка:</b> ' + (response.text || 'Не удалось загрузить кланы.') + '</div>');
                return;
            }

            // Удаляем предыдущие блоки, если есть
            $('.Clans').remove();

            // Основной контейнер
            $('<div />', {
                "class": 'Clans'
            }).appendTo('.Modal');

            // Список кланов
            $('<div />', {
                "class": 'ClanList'
            }).appendTo('.Clans');

            // Рендерим каждый клан
            $.each(response['clansList'], function(x, y) {
                $('<div />', {
                    "class": 'Clan',
                    "click": function() {
                        openClanCard(y['id']);
                    },
                    html:
    '<div class="img"><img src="' + (y['emblem'] ? y['emblem'] : ('/img/world/clans/emblems/' + y['id'] + '.png')) + '" alt="Эмблема"></div>' +
    '<div class="info">' +
        '<div class="name">' + y['name'] + '</div>' +
        '<div class="level"><i class="far fa-bahai"></i><span>' + y['level'] + '</span></div>' +
        '<div class="raiting"><i class="far fa-trophy"></i><span>' + y['rating'] + '</span></div>' +
    '</div>'
                }).appendTo('.ClanList');
            });

            // Нижняя панель
            $('<div />', {
                "class": 'BottomClans'
            }).appendTo('.Clans');

            // Кнопки
            $('<div />', {
                "class": 'Buttons'
            }).appendTo('.BottomClans');

            // Кнопка создания клана
            $('<div />', {
                "class": 'Button',
                html: 'Создать клан',
                "click": function() {
                    if (typeof Game.clan.createClan === "function") {
                        Game.clan.createClan();
                    } else if (typeof clan.createClan === "function") {
                        clan.createClan();
                    } else {
                        alert("Функция создания клана не найдена!");
                    }
                }
            }).appendTo('.BottomClans .Buttons');

            // Центрирование модального окна относительно .MidMenu (если она есть)
            var offset = $('.MidMenu').length ? $('.MidMenu').offset().left : 0;
            $('.Modal').css('left', (Math.round(offset) + 462) + 'px');

            Game.loaders.worldClose();
        },
        error: function() {
            Game.loaders.worldClose();
            Game.notifications.main("Ошибка загрузки списка кланов.", "error");
        }
    });
}
	},
pokemonTeamTabs: function(i, r){
  $.ajax({
    url: "/do/PokemonTeam",
    type: "POST",
    data: "type="+r+"&other="+i,
    beforeSend: function(){
      $('<div/>',{"class":"Hidden",id:'Hidden'+i,html:'<div id="HiddenLoad"></div>'}).appendTo('#pokemonDivs'+i);
    },
    success: function(response){

      // ===================== INFO (карточка с командой) =====================
      if (r === 'info') {
        response = (typeof response === 'string') ? JSON.parse(response) : response;

        Game.modals.modalLoad(5);
        const $modal = $('.Modal');
        $('<div/>',{class:'Pokemons',html:response.html}).appendTo($modal);

        const left = Math.max(1, Math.round(($('.MidMenu').offset()||{left:100}).left) - 100);
        $modal.css('left', left+'px');

        const $wrap = $modal.find('.Pokemons');
        const $info = $wrap.find('.Right .Info').first();

        // Определение мобильного устройства
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                        ('ontouchstart' in window) || 
                        (navigator.maxTouchPoints > 0);

        // Стили
        if (!$('#pk-card-css').length){
          $('<style id="pk-card-css">\
            .Info{position:relative}\
            .Info .Step.pure{display:flex;gap:8px;align-items:center;background:transparent;border:0;padding:0;margin:6px 0 0 0}\
            .Info .Step.pure .Label{font-weight:700;color:#2f4374}\
            .Info .Step.pure .Other{font-weight:800;color:#1b2b4f}\
            .AbilityLink{border:0;background:transparent;color:#f59e0b;font-weight:900;cursor:pointer;padding:0}\
            .AbilityLink:hover{text-decoration:underline}\
            .pkStatBtn{position:absolute;right:8px;top:6px;width:30px;height:30px;display:grid;place-items:center;border:1px solid #e6eafe;border-radius:8px;background:#fff;color:#5c6b8a;cursor:pointer}\
            .pkTeamBtn{position:absolute;right:45px;top:6px;width:30px;height:30px;display:grid;place-items:center;border:1px solid #e6eafe;border-radius:8px;background:#fff;color:#5c6b8a;cursor:pointer}\
            .pkTeamBtn:hover,.pkStatBtn:hover{border-color:#2f74ff;color:#2f74ff}\
            .pkMini{position:absolute;right:8px;top:42px;min-width:260px;max-width:320px;padding:10px 12px;border:1px solid #252b3a;border-radius:12px;background:#11141b;color:#eef3ff;box-shadow:0 16px 34px rgba(0,0,0,.35);display:none;z-index:60}\
            .pkMini .row{display:flex;justify-content:space-between;padding:6px 2px;border-bottom:1px dashed rgba(255,255,255,.08)}\
            .pkMini .row:last-child{border:0}.pkMini .k{opacity:.78}\
            \
            /* Панель команды */\
            .pk-team-panel{position:absolute;right:8px;top:78px;width:320px;max-width:90vw;background:#fff;border:1px solid #e6eafe;border-radius:12px;box-shadow:0 16px 34px rgba(0,0,0,.15);display:none;z-index:70;max-height:400px;overflow:hidden}\
            .pk-team-header{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#f8f9ff;border-bottom:1px solid #e6eafe}\
            .pk-team-title{font-weight:700;color:#2f4374;font-size:13px;display:flex;align-items:center;gap:6px}\
            .pk-team-close{width:22px;height:22px;border:1px solid #e6eafe;background:#fff;border-radius:5px;display:grid;place-items:center;color:#8f76c1;cursor:pointer;font-size:12px}\
            .pk-team-close:hover{background:#f0eef9}\
            \
            /* Список команды */\
            .pk-team-list{padding:8px;max-height:320px;overflow-y:auto}\
            .pk-team-item{display:flex;align-items:center;gap:10px;padding:8px;border:1px solid #f0f0f0;border-radius:8px;margin-bottom:6px;cursor:pointer;transition:all 0.2s ease;background:#fafbfc}\
            .pk-team-item:last-child{margin-bottom:0}\
            .pk-team-item:hover{border-color:#2f74ff;background:#f8f9ff}\
            .pk-team-item.active{border-color:#10b981;background:#f0fdf4}\
            .pk-team-item.active::after{content:"✓";position:absolute;right:8px;color:#10b981;font-weight:bold}\
            .pk-team-item.in-battle{border-color:#f59e0b;background:#fffbeb}\
            .pk-team-item.in-battle::after{content:"⚔";position:absolute;right:8px;color:#f59e0b}\
            \
            /* Аватар покемона */\
            .pk-team-avatar{width:40px;height:40px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;display:grid;place-items:center;overflow:hidden;flex-shrink:0}\
            .pk-team-avatar img{max-width:36px;max-height:36px;object-fit:contain}\
            \
            /* Информация о покемоне */\
            .pk-team-info{flex:1;min-width:0}\
            .pk-team-name{font-weight:700;font-size:12px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\
            .pk-team-details{display:flex;align-items:center;gap:8px;margin-top:2px}\
            .pk-team-level{font-size:11px;color:#64748b;font-weight:600}\
            .pk-team-type{font-size:10px;padding:1px 4px;border-radius:3px;font-weight:600}\
            .pk-team-type.shine{background:#fbbf24;color:#fff}\
            .pk-team-type.shadow{background:#6b7280;color:#fff}\
            .pk-team-type.NewYear{background:#10b981;color:#fff}\
            \
            /* HP полоска */\
            .pk-team-hp{width:100%;height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;margin-top:4px;cursor:help}\
            .pk-team-hp-bar{height:100%;background:#22c55e;border-radius:2px;transition:width 0.3s ease}\
            .pk-team-hp-bar.low{background:#f59e0b}\
            .pk-team-hp-bar.critical{background:#ef4444}\
            \
            /* Статы — акцент */\
            .StatsPokemon .StatPokemon.pkUp{background:rgba(22,163,74,.07);border-radius:10px}\
            .StatsPokemon .StatPokemon.pkDown{background:rgba(214,69,69,.07);border-radius:10px}\
            .StatsPokemon .Stat.pkUp{color:#16a34a;font-weight:900}\
            .StatsPokemon .Stat.pkDown{color:#d14343;font-weight:900}\
            \
            /* ИСПРАВЛЕННЫЕ стили для EV Progress */\
            .Progress[data-title*="EV"]{cursor:help !important;position:relative;transition:all 0.2s ease}\
            .Progress[data-title*="EV"]:hover{opacity:0.8;transform:scale(1.02)}\
            .Progress[data-title*="EV"] .Bar{cursor:help !important}\
            @media (max-width: 768px), (pointer: coarse) {\
              .Progress[data-title*="EV"]::after{content:position:absolute;right:2px;top:50%;transform:translateY(-50%);font-size:8px;opacity:0.6}\
              .Progress[data-title*="EV"]{cursor:pointer !important}\
            }\
            \
            /* Тултип характера */\
            .nature-tooltip{position:fixed;background:#2a2d3a;color:#fff;padding:8px 10px;border-radius:6px;font-size:11px;line-height:1.3;box-shadow:0 8px 20px rgba(0,0,0,.5);z-index:99999;opacity:0;pointer-events:none;transition:opacity 0.2s ease;width:140px;transform:translateX(-50%);user-select:none;-webkit-user-select:none}\
            .nature-tooltip::before{content:"";position:absolute;bottom:-4px;left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#2a2d3a}\
            .nature-tooltip.bottom::before{top:-4px;bottom:auto;border:4px solid transparent;border-bottom-color:#2a2d3a}\
            .nature-tooltip.show{opacity:1;pointer-events:auto}\
            .nature-tooltip-header{font-weight:600;font-size:11px;margin-bottom:6px;color:#e2e8f0;text-align:center;padding-bottom:4px;border-bottom:1px solid rgba(255,255,255,.15)}\
            .nature-tooltip-stats{display:flex;flex-direction:column;gap:2px}\
            .nature-tooltip-stat{display:flex;justify-content:space-between;align-items:center;padding:1px 0;font-size:10px}\
            .nature-tooltip-stat-name{font-weight:500;opacity:.9}\
            .nature-tooltip-stat-effect{font-weight:600;font-size:10px;padding:1px 4px;border-radius:3px;min-width:30px;text-align:center}\
            .nature-tooltip-stat-effect.positive{background:rgba(34,197,94,.25);color:#4ade80}\
            .nature-tooltip-stat-effect.negative{background:rgba(239,68,68,.25);color:#f87171}\
            .nature-tooltip-stat-effect.neutral{background:rgba(156,163,175,.15);color:#9ca3af}\
            \
            /* Кнопка закрытия для мобильных */\
            .nature-tooltip-close{position:absolute;top:-8px;right:-8px;width:20px;height:20px;background:#ef4444;color:#fff;border:none;border-radius:50%;font-size:12px;font-weight:bold;cursor:pointer;display:none;line-height:1;box-shadow:0 2px 6px rgba(0,0,0,.3)}\
            .nature-tooltip-close:hover{background:#dc2626}\
            \
            /* Мобильные стили */\
            @media (max-width: 768px), (pointer: coarse) {\
              .nature-tooltip{width:160px;padding:10px 12px;font-size:12px}\
              .nature-tooltip-close{display:block}\
              .nature-tooltip-stat{padding:2px 0;font-size:11px}\
              .nature-tooltip-header{font-size:12px;margin-bottom:8px}\
            }\
            \
            /* Стили для элементов с тултипами */\
            [data-tooltip-ready]{cursor:help}\
            .nature-trigger{cursor:pointer;position:relative;transition:background-color 0.2s ease}\
            .nature-trigger:hover{background-color:rgba(47,116,255,.1);border-radius:3px}\
            .nature-trigger.mobile-active{background-color:rgba(47,116,255,.15);border-radius:3px}\
            \
            /* Индикатор для мобильных */\
            @media (max-width: 768px), (pointer: coarse) {\
              .nature-trigger::after{content:" ⓘ";color:#6b7280;font-size:10px;margin-left:2px}\
              .nature-trigger.mobile-active::after{color:#2f74ff}\
            }\
            \
            /* Предотвращение дублирования обработчиков */\
            .tooltip-processed{}\
          </style>').appendTo('head');
        }

        // ИСПРАВЛЕННАЯ база данных характеров (точно по таблице har)
        const natureDatabase = {
          'Веселый': { atk: 1.0, def: 1.0, satk: 0.9, sdef: 1, speed: 1.1 },        // ID 1
          'Выносливый': { atk: 1, def: 1, satk: 1, sdef: 1, speed: 1 },          // ID 2
          'Застенчивый': { atk: 1, def: 1, satk: 1, sdef: 1, speed: 1 },         // ID 3
          'Кроткий': { atk: 1, def: 1, satk: 1, sdef: 1, speed: 1 },             // ID 4
          'Мирный': { atk: 0.9, def: 1, satk: 1.1, sdef: 1, speed: 1 },          // ID 5
          'Мягкий': { atk: 1, def: 0.9, satk: 1.1, sdef: 1, speed: 1 },          // ID 6
          'Наглый': { atk: 0.9, def: 1.1, satk: 1, sdef: 1, speed: 1 },          // ID 7
          'Наивный': { atk: 1, def: 1, satk: 1, sdef: 0.9, speed: 1.1 },         // ID 8
          'Нахальный': { atk: 1, def: 1, satk: 1.1, sdef: 0.9, speed: 1 },       // ID 9
          'Нежный': { atk: 1, def: 0.9, satk: 1.1, sdef: 1, speed: 1 },          // ID 10
          'Непослушный': { atk: 1.1, def: 1, satk: 1, sdef: 0.9, speed: 1 },     // ID 11
          'Непреклонный': { atk: 1.1, def: 1, satk: 0.9, sdef: 1, speed: 1 },    // ID 12
          'Обычный': { atk: 1, def: 1, satk: 1, sdef: 1, speed: 1 },             // ID 13
          'Одинокий': { atk: 1.1, def: 0.9, satk: 1, sdef: 1, speed: 1 },        // ID 14
          'Озорной': { atk: 1, def: 1.1, satk: 0.9, sdef: 1, speed: 1 },         // ID 15
          'Осторожный': { atk: 1, def: 1, satk: 0.9, sdef: 1.1, speed: 1 },      // ID 16
          'Поспешный': { atk: 1, def: 0.9, satk: 1, sdef: 1, speed: 1.1 },       // ID 17
          'Причудливый': { atk: 1, def: 1, satk: 1, sdef: 1, speed: 1 },         // ID 18
          'Распущенный': { atk: 1, def: 1.1, satk: 1, sdef: 0.9, speed: 1 },     // ID 19
          'Робкий': { atk: 0.9, def: 1, satk: 1, sdef: 1, speed: 1.1 },          // ID 20
          'Серьезный': { atk: 1, def: 1, satk: 1, sdef: 1, speed: 1 },           // ID 21
          'Скромный': { atk: 0.9, def: 1, satk: 1.1, sdef: 1, speed: 1 },        // ID 22
          'Смелый': { atk: 1.1, def: 1, satk: 1, sdef: 1, speed: 0.9 },          // ID 23
          'Спокойный': { atk: 1, def: 1.1, satk: 1, sdef: 1, speed: 0.9 },       // ID 24
          'Стремительный': { atk: 1, def: 1.1, satk: 0.9, sdef: 1, speed: 1 },   // ID 25
          'Тихий': { atk: 1, def: 1.1, satk: 1, sdef: 0.9, speed: 1 }            // ID 26
        };

        // Глобальная переменная для отслеживания активных тултипов
        window.activeTooltips = window.activeTooltips || {
          nature: null,
          other: []
        };

        // ИСПРАВЛЕННАЯ функция создания тултипа
        function createNatureTooltip(natureName, $target) {
          const nature = natureDatabase[natureName];
          if (!nature) {
            console.warn('Характер не найден:', natureName);
            return;
          }

          // Закрываем существующий тултип характера
          if (window.activeTooltips.nature) {
            window.activeTooltips.nature.removeClass('show');
            setTimeout(() => {
              if (window.activeTooltips.nature) {
                window.activeTooltips.nature.remove();
                window.activeTooltips.nature = null;
              }
            }, 200);
          }

          const statNames = {
            atk: 'Атака',
            def: 'Защита', 
            satk: 'Сп.Атк',
            sdef: 'Сп.Защ',
            speed: 'Скорость'
          };

          let tooltipHtml = `<div class="nature-tooltip-header">${natureName}</div>`;
          tooltipHtml += '<div class="nature-tooltip-stats">';

          // Показываем только статы с эффектом
          let hasEffects = false;
          Object.entries(statNames).forEach(([key, name]) => {
            const multiplier = nature[key];
            if (multiplier !== 1) {
              hasEffects = true;
              let effectClass, effectText;
              
              if (multiplier > 1) {
                effectClass = 'positive';
                effectText = '+10%';
              } else {
                effectClass = 'negative'; 
                effectText = '-10%';
              }

              tooltipHtml += `
                <div class="nature-tooltip-stat">
                  <span class="nature-tooltip-stat-name">${name}</span>
                  <span class="nature-tooltip-stat-effect ${effectClass}">${effectText}</span>
                </div>
              `;
            }
          });

          // Если нет изменений
          if (!hasEffects) {
            tooltipHtml += '<div class="nature-tooltip-stat"><span style="text-align:center;width:100%;opacity:0.7;font-size:10px">Нейтральный характер</span></div>';
          }

          tooltipHtml += '</div>';

          // Добавляем кнопку закрытия для мобильных
          if (isMobile) {
            tooltipHtml += '<button class="nature-tooltip-close" type="button">×</button>';
          }

          // Создаем тултип
          const $tooltip = $('<div class="nature-tooltip"></div>').html(tooltipHtml);
          $('body').append($tooltip);

          // Сохраняем ссылку
          window.activeTooltips.nature = $tooltip;

          // Позиционирование
          const targetOffset = $target.offset();
          const targetWidth = $target.outerWidth();
          const targetHeight = $target.outerHeight();
          const tooltipWidth = $tooltip.outerWidth();
          const tooltipHeight = $tooltip.outerHeight();
          
          let left = targetOffset.left + (targetWidth / 2);
          let top = targetOffset.top - tooltipHeight - 8;
          
          if (left - tooltipWidth/2 < 10) {
            left = 10 + tooltipWidth/2;
          }
          if (left + tooltipWidth/2 > $(window).width() - 10) {
            left = $(window).width() - tooltipWidth/2 - 10;
          }
          
          if (top < 10) {
            top = targetOffset.top + targetHeight + 8;
            $tooltip.addClass('bottom');
          }

          $tooltip.css({
            left: left + 'px',
            top: top + 'px'
          });

          // Обработчик кнопки закрытия
          $tooltip.find('.nature-tooltip-close').on('click touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideNatureTooltip();
          });

          // Показываем тултип
          setTimeout(() => $tooltip.addClass('show'), 10);

          return $tooltip;
        }

        // Функция скрытия тултипа характера
        function hideNatureTooltip() {
          if (window.activeTooltips.nature) {
            window.activeTooltips.nature.removeClass('show');
            setTimeout(() => {
              if (window.activeTooltips.nature) {
                window.activeTooltips.nature.remove();
                window.activeTooltips.nature = null;
              }
            }, 200);
          }
        }

        // Утилиты
        const findRowStarts = (label)=> $info.find('*').filter(function(){return $(this).text().trim().indexOf(label)===0;}).first();
        const getLine = (label)=>{ let v=''; $info.find('*').each(function(){ if(v) return; const t=$(this).text().trim(); if(t.indexOf(label)===0) v=t.replace(label,'').trim(); }); return v; };

        // Извлекаем данные
        const hpCurrent = parseInt(response.curHP) || 0;
        const hpMax = parseInt(response.maxHP) || 1;
        
        let expCurrent = 0, expNext = 0, happiness = 0;
        
        const expMatch = response.html.match(/data-title="Опыт:\s*(\d+)\s*\/\s*(\d+)/);
        if (expMatch) {
            expCurrent = parseInt(expMatch[1]) || 0;
            expNext = parseInt(expMatch[2]) || 0;
        }
        
        const happyMatch = response.html.match(/data-title="Счастье:\s*(\d+)\s*\/\s*255/);
        if (happyMatch) {
            happiness = parseInt(happyMatch[1]) || 0;
        }
        
        const evMatches = response.html.match(/data-title="EV:\s*(\d+)\s*\/\s*126"/g) || [];
        const evValues = evMatches.map(match => {
            const evMatch = match.match(/data-title="EV:\s*(\d+)/);
            return evMatch ? parseInt(evMatch[1]) || 0 : 0;
        });
        
        while (evValues.length < 6) {
            evValues.push(0);
        }

        // ОБРАБОТКА ХАРАКТЕРА
        const $charOrig = findRowStarts('Характер:'); 
        if($charOrig.length){ $charOrig.find('i').remove(); $charOrig.parent().children('i').first().remove(); }

        const charVal = getLine('Характер:');
        if (charVal){
          const $charRow = $('<div class="Step pure"><div class="Label">Характер:</div><div class="Other nature-trigger">'+charVal+'</div></div>');
          $charRow.insertBefore($charOrig);
          $charOrig.hide();
          
          const $charTrigger = $charRow.find('.nature-trigger');
          let hoverTimeout = null;

          if (isMobile) {
            // МОБИЛЬНАЯ ЛОГИКА
            $charTrigger.on('click.natureTooltip touchstart.natureTooltip', function(e) {
              e.preventDefault();
              e.stopPropagation();
              
              if (window.activeTooltips.nature) {
                hideNatureTooltip();
                $(this).removeClass('mobile-active');
              } else {
                createNatureTooltip(charVal, $(this));
                $(this).addClass('mobile-active');
              }
            });
          } else {
            // ДЕСКТОПНАЯ ЛОГИКА
            $charTrigger.on('mouseenter.natureTooltip', function() {
              clearTimeout(hoverTimeout);
              if (!window.activeTooltips.nature) {
                hoverTimeout = setTimeout(() => {
                  createNatureTooltip(charVal, $(this));
                }, 300);
              }
            }).on('mouseleave.natureTooltip', function() {
              clearTimeout(hoverTimeout);
              if (window.activeTooltips.nature && !$(this).hasClass('mobile-active')) {
                hideNatureTooltip();
              }
            });
          }
        }

        // Остальная обработка полей (сокращено)
        const $abilOrig = (function(){
          return $info.find('*').filter(function(){
            return $(this).text().trim().indexOf('Способность:') === 0;
          }).first();
        })();

        if ($abilOrig.length) {
          const $clone = $abilOrig.clone(true);
          $clone.contents().each(function(){
            if (this.nodeType === 3) {
              this.nodeValue = (this.nodeValue || '').replace(/^(\s*Способность:\s*)/,'');
            } else {
              const $el = $(this);
              const txt = $el.text().trim();
              if (txt.indexOf('Способность:') === 0) {
                const rest = txt.replace(/^Способность:\s*/,'');
                if (rest) { $el.text(rest); }
                else { $el.remove(); }
              }
            }
          });

          const abilityInner = $('<div>').append($clone.contents()).html().trim();
          const $row = $('<div class="Step pure"><div class="Label">Способность:</div><div class="Other"></div></div>');
          $row.find('.Other').html(abilityInner);
          $row.find('a,button,[onclick]').first().addClass('AbilityLink');
          $row.insertBefore($abilOrig);
          $abilOrig.hide();
        }

        // Остальные поля
        const geneOrig = findRowStarts('Генокод:');
        const geneVal  = getLine('Генокод:');
        if (geneVal){
          $('<div class="Step pure"><div class="Label">Генокод:</div><div class="Other">'+geneVal+'</div></div>')
            .insertBefore(geneOrig);
          geneOrig.hide();
        }

        let vitaminsText = '';
        const $vitStep = $info.find('.Step.vitaminesPok');
        if($vitStep.length){ vitaminsText = $vitStep.find('.Other').text().trim(); $vitStep.hide(); }
        $info.find('*').filter(function(){ return $(this).text().trim().indexOf('Витамины:')===0; }).hide();

        const $genderStep = $info.find('.Step.genderPok');
        if($genderStep.length){
          const val = $genderStep.find('.Other').text().trim() || '—';
          const $row = $('<div class="Step pure"><div class="Label">Группа привлекательности:</div><div class="Other">'+val+'</div></div>');
          const $geneRowNew = $info.find('.Step.pure .Label').filter(function(){return $(this).text()==='Генокод:';}).first().parent();
          if($geneRowNew.length) $row.insertAfter($geneRowNew); else if(geneOrig.length) $row.insertAfter(geneOrig);
          $genderStep.hide();
        }

        const breedOk = Number(response.sparka||0)===0;
        const $breedRow = $('<div class="Step pure"><div class="Label">Разведение:</div><div class="Other '+(breedOk?'Green-Color':'Red-Color')+'">'+(breedOk?'доступно':'недоступно')+'</div></div>');
        const $anchorForBreed = $info.find('.Step.pure .Label').filter(function(){return $(this).text()==='Группа привлекательности:';}).first().parent();
        if($anchorForBreed.length) $breedRow.insertAfter($anchorForBreed);

        let evFree = '';
        const $evStep = $info.find('.Step.evPok');
        if ($evStep.length) evFree = $evStep.find('.Other').text().trim();
        $info.find('.Step.evPok').hide();
        $info.find('*').filter(function(){ return /^Свободн(ые|ых)\s*EV/i.test($(this).text().trim()); }).hide();
        $('<div class="Step pure"><div class="Label">Свободные EV:</div><div class="Other">'+(evFree||'0')+'</div></div>').insertAfter($breedRow);

        $info.find('*').filter(function(){ const t=$(this).text().trim(); return t.indexOf('Пойман:')===0 || t.indexOf('Потенциал:')===0; }).hide();

        
        // Кнопки
        const $btnStat = $('<button type="button" class="pkStatBtn" aria-label="Статистика" title="Дополнительная статистика"><i class="fal fa-chart-line"></i></button>');
        const $btnTeam = $('<button type="button" class="pkTeamBtn" aria-label="Команда" title="Показать команду"><i class="fas fa-users"></i></button>');
        const $btnPreset = $('<button type="button" class="pkPresetBtn" aria-label="Пресеты атак" title="Пресеты атак"><i class="fas fa-bookmark"></i></button>');
        const $mini = $('<div class="pkMini"></div>');
        const $teamPanel = $('<div class="pk-team-panel"></div>');
        const $presetPanel = $('<div class="pk-preset-panel" aria-hidden="true"></div>');

        $info.append($btnStat, $btnTeam, $btnPreset, $mini, $teamPanel, $presetPanel);

        // Поповер статистики
        const caught    = getLine('Пойман:')     || '—';
        const potential = getLine('Потенциал:')  || '—';
        const st0 = +response.st0||0, st2 = +response.st2||0, st3 = +response.st3||0;
        const tasty = [response.st8,response.st9].filter(Boolean).join(' ').trim();

        $mini.html([
          '<div class="row"><div class="k">Пойман</div><div>'+caught+'</div></div>',
          '<div class="row"><div class="k">Потенциал</div><div>'+potential+'</div></div>',
          (vitaminsText?'<div class="row"><div class="k">Витамины</div><div>'+vitaminsText+'</div></div>':''),
          '<div class="row"><div class="k">Разведение</div><div>'+(breedOk?'доступно':'недоступно')+'</div></div>',
          '<div class="row"><div class="k">Шоколадная конфета</div><div>'+(st0?'использована':'не использована')+'</div></div>',
          '<div class="row"><div class="k">Сладкий кекс</div><div>'+(st2?'использован':'не использован')+'</div></div>',
          '<div class="row"><div class="k">Корень априкорна</div><div>'+(st3?'использован':'не использован')+'</div></div>',
          (tasty?'<div class="row"><div class="k">Тренировки</div><div>'+tasty+'</div></div>':'')
        ].join(''));

        // Упрощенная панель команды
        const $teamHeader = $('<div class="pk-team-header"></div>');
        const $teamTitle = $('<div class="pk-team-title"><i class="fas fa-users"></i>Команда покемонов</div>');
        const $teamClose = $('<button class="pk-team-close" title="Закрыть">×</button>');
        const $teamList = $('<div class="pk-team-list"></div>');
        
        $teamHeader.append($teamTitle, $teamClose);
        $teamPanel.append($teamHeader, $teamList);

        function loadTeam() {
          $teamList.html('<div class="pk-team-loading"><i class="fas fa-spinner"></i>Загрузка команды...</div>');
          
          $.ajax({
            url: "/do/PokemonTeam",
            type: "POST", 
            data: "type=load",
            success: function(teamResponse) {
              try {
                const teamData = typeof teamResponse === 'string' ? JSON.parse(teamResponse) : teamResponse;
                renderTeam(teamData);
              } catch(e) {
                $teamList.html('<div class="pk-team-empty">Ошибка загрузки команды</div>');
              }
            },
            error: function() {
              $teamList.html('<div class="pk-team-empty">Не удалось загрузить команду</div>');
            }
          });
        }

        function renderTeam(teamData) {
          if (!teamData || Object.keys(teamData).length === 0) {
            $teamList.html('<div class="pk-team-empty">Команда пуста</div>');
            return;
          }

          $teamList.empty();
          
          Object.values(teamData).forEach(function(pokemon) {
            const isActive = pokemon.id == i;
            const hpPercent = Math.max(0, Math.min(100, (pokemon.hp / pokemon.maxHP) * 100));
            const hpClass = hpPercent > 60 ? '' : hpPercent > 30 ? 'low' : 'critical';
            
            let typeClass = '';
            let typeText = '';
            if (pokemon.html.includes('shine-color')) {
              typeClass = 'shine';
              typeText = 'shine';
            } else if (pokemon.html.includes('shadow-color')) {
              typeClass = 'shadow';
              typeText = 'shadow';
            } else if (pokemon.html.includes('NewYear-color')) {
              typeClass = 'NewYear';
              typeText = 'NewYear';
            }

            const nameMatch = pokemon.html.match(/#\d+\s+([^<]+)/);
            const pokemonName = nameMatch ? nameMatch[1].trim() : 'Неизвестный';
            
            const imgMatch = pokemon.html.match(/src="([^"]*\.gif)"/);
            const avatarUrl = imgMatch ? imgMatch[1] : '';

            const levelMatch = pokemon.html.match(/<div class="Lvl">(\d+)<\/div>/);
            const level = levelMatch ? levelMatch[1] : '?';

            const $item = $('<div class="pk-team-item' + 
              (isActive ? ' active' : '') + 
              (pokemon.inBattle ? ' in-battle' : '') + 
              '" data-pokemon-id="' + pokemon.id + '"></div>');
            
            let itemTitle = pokemonName + ' (Ур. ' + level + ')';
            if (isActive) itemTitle += ' - Текущий покемон';
            else if (pokemon.inBattle) itemTitle += ' - В бою';
            else itemTitle += ' - Нажмите для переключения';
            $item.attr('title', itemTitle);
            
            const $avatar = $('<div class="pk-team-avatar"></div>');
            if (avatarUrl) {
              $avatar.append('<img src="' + avatarUrl + '" alt="' + pokemonName + '" onerror="this.style.display=\'none\'">');
            } else {
              $avatar.append('<i class="fas fa-question" style="color:#94a3b8;"></i>');
            }
            
            const $info = $('<div class="pk-team-info"></div>');
            const $name = $('<div class="pk-team-name">' + pokemonName + '</div>');
            const $details = $('<div class="pk-team-details"></div>');
            const $level = $('<div class="pk-team-level">Ур. ' + level + '</div>');
            
            $details.append($level);
            
            if (typeText) {
              const $type = $('<div class="pk-team-type ' + typeClass + '">' + typeText + '</div>');
              $details.append($type);
            }
            
            const $hp = $('<div class="pk-team-hp"><div class="pk-team-hp-bar ' + hpClass + '" style="width:' + hpPercent + '%"></div></div>');
            $hp.attr('title', 'HP: ' + pokemon.hp + ' / ' + pokemon.maxHP + ' (' + Math.round(hpPercent) + '%)');
            
            const $status = $('<div class="pk-team-status"></div>');
            
            $info.append($name, $details, $hp, $status);
            $item.append($avatar, $info);
            
            if (!isActive) {
              $item.on('click', function() {
                const pokemonId = $(this).data('pokemon-id');
                Game.pokemonTeamTabs(pokemonId, 'info');
                $teamPanel.hide();
              });
            }
            
            $teamList.append($item);
          });
        }


        /* ===== Presets UI (per pokID) ===== */
        const pokID = (typeof i !== 'undefined') ? i : 0;

        // CSS (separate, to avoid touching main CSS string)
        if (!document.getElementById('pkPresetStyle')) {
          $('head').append(
            '<style id="pkPresetStyle">' +
            '.pkPresetBtn{position:absolute;right:82px;top:6px;width:30px;height:30px;display:grid;place-items:center;border:1px solid #e6eafe;border-radius:8px;background:#fff;color:#5c6b8a;cursor:pointer}' +
            '.pkPresetBtn:hover{border-color:#2f74ff;color:#2f74ff}' +
            '.pk-preset-panel{position:absolute;right:8px;top:44px;width:290px;max-width:calc(100% - 16px);background:#fff;border:1px solid #e6eafe;border-radius:12px;box-shadow:0 14px 40px rgba(15,23,42,.18);padding:10px;display:none;z-index:50}' +
            '.pk-preset-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px}' +
            '.pk-preset-title{font-weight:600;color:#334155;font-size:13px;display:flex;align-items:center;gap:6px}' +
            '.pk-preset-close{border:0;background:transparent;font-size:18px;line-height:18px;color:#64748b;cursor:pointer;padding:2px 6px;border-radius:8px}' +
            '.pk-preset-close:hover{background:#f1f5f9;color:#334155}' +
            '.pk-preset-actions{display:flex;gap:8px;margin-bottom:8px}' +
            '.pk-preset-input{flex:1;border:1px solid #e6eafe;border-radius:10px;height:34px;padding:0 10px;font-size:12px;outline:none}' +
            '.pk-preset-input:focus{border-color:#2f74ff}' +
            '.pk-preset-save{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;border:1px solid #e6eafe;background:#f8fafc;color:#334155;border-radius:10px;height:34px;cursor:pointer;font-size:12px}' +
            '.pk-preset-save:hover{border-color:#2f74ff;color:#2f74ff;background:#fff}' +
            '.pk-preset-list{max-height:260px;overflow:auto;padding-right:2px}' +
            '.pk-preset-item{display:flex;align-items:center;justify-content:space-between;gap:10px;border:1px solid #eef2ff;border-radius:10px;padding:8px 8px;margin-bottom:8px}' +
            '.pk-preset-name{font-weight:600;font-size:12px;color:#334155;line-height:14px}' +
            '.pk-preset-preview{font-size:11px;color:#64748b;line-height:13px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px}' +
            '.pk-preset-btns{display:flex;gap:6px;align-items:center}' +
            '.pk-preset-apply{border:1px solid #e6eafe;background:#fff;color:#334155;border-radius:10px;height:30px;padding:0 10px;cursor:pointer;font-size:12px}' +
            '.pk-preset-apply:hover{border-color:#2f74ff;color:#2f74ff}' +
            '.pk-preset-del{border:1px solid #e6eafe;background:#fff;color:#ef4444;border-radius:10px;height:30px;width:30px;display:grid;place-items:center;cursor:pointer;font-size:14px}' +
            '.pk-preset-del:hover{border-color:#ef4444;background:#fff5f5}' +
            '.pk-preset-empty{color:#64748b;font-size:12px;padding:10px;text-align:center}' +
            '.pk-preset-backdrop{position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(15,23,42,.45);z-index:9998;display:none}' +
            '@media (max-width:720px){' +
              '.pk-preset-panel{position:fixed;left:50%;transform:translateX(-50%);right:auto;top:auto;bottom:12px;width:calc(100% - 24px);max-width:520px;z-index:9999;border-radius:14px}' +
              '.pk-preset-list{max-height:42vh}' +
              '.pk-preset-save,.pk-preset-apply{height:38px;font-size:13px}' +
              '.pk-preset-del{height:38px;width:38px}' +
            '}' +
            '</style>'
          );
        }

        if (!window.__pkPresetBackdrop) {
          window.__pkPresetBackdrop = $('<div class="pk-preset-backdrop"></div>').appendTo('body').hide();
        }
        const $presetBackdrop = window.__pkPresetBackdrop;

        function presetsClose() {
          $presetPanel.hide().attr('aria-hidden','true');
          $presetBackdrop.hide();
        }
        function presetsOpen() {
          $mini.hide();
          $teamPanel.hide();
          $presetPanel.show().attr('aria-hidden','false');
          if (isMobile) $presetBackdrop.show();
          loadPresets();
        }

        function loadPresets() {
          if (!pokID) return;
          $presetPanel.addClass('loading');
          $.ajax({
            url: "/do/pokemonsAction",
            type: "POST",
            data: "type=presetList&pokID=" + pokID,
            success: function(r) {
              let data = r;
              try { data = (typeof r === 'string') ? JSON.parse(r) : r; } catch(e) { data = null; }
              renderPresets(data && data.presets ? data.presets : []);
            },
            error: function() {
              renderPresets([], 'Ошибка загрузки пресетов');
            }
          });
        }

        function renderPresets(list, errText) {
          const safe = Array.isArray(list) ? list : [];
          const head = [
            '<div class="pk-preset-head">',
              '<div class="pk-preset-title"><i class="fas fa-bookmark"></i>Пресеты атак</div>',
              '<button type="button" class="pk-preset-close" aria-label="Закрыть">×</button>',
            '</div>',
            '<div class="pk-preset-actions">',
              '<input type="text" class="pk-preset-input" placeholder="Название..." maxlength="40">',
              '<button type="button" class="pk-preset-save" title="Сохранить текущие атаки"><i class="fas fa-star"></i></button>',
            '</div>',
            '<div class="pk-preset-list"></div>'
          ].join('');
          $presetPanel.html(head);

          const $list = $presetPanel.find('.pk-preset-list');
          if (errText) {
            $list.html('<div class="pk-preset-empty">' + errText + '</div>');
            return;
          }
          if (!safe.length) {
            $list.html('<div class="pk-preset-empty">Пресетов нет. Нажмите «Сохранить».</div>');
            return;
          }
          $list.empty();
          safe.forEach(function(p) {
            const key = 'u_' + p.id;
            const name = (p.name || 'Без названия').toString();
            const preview = (p.preview || '').toString();
            const moves = (p && Array.isArray(p.moves)) ? p.moves : null;
            const $it = $('<div class="pk-preset-item" data-key="'+key+'"></div>');
            const $left = $('<div style="min-width:0"></div>');
            $left.append('<div class="pk-preset-name">'+escapeHtml(name)+'</div>');
            $left.append((function(){
              var t = '';
              if (moves && moves.length) {
                t = moves.map(function(x){ return String(x||'—'); }).join('\\n');
                var h = moves.map(function(x){ return '<div>'+escapeHtml(String(x||'—'))+'</div>'; }).join('');
                return '<div class="pk-preset-preview" title="'+escapeHtml(t)+'">'+h+'</div>';
              }
              return '<div class="pk-preset-preview" title="'+escapeHtml(preview)+'">'+escapeHtml(preview)+'</div>';
            })());
            const $btns = $('<div class="pk-preset-btns"></div>');
            $btns.append('<button type="button" class="pk-preset-apply">Применить</button>');
            $btns.append('<button type="button" class="pk-preset-del" title="Удалить">×</button>');
            $it.append($left, $btns);
            $list.append($it);
          });
        }

        function escapeHtml(str) {
          return String(str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
        }

        // Events inside panel
        $presetPanel.off('click.pkPreset')
          .on('click.pkPreset', '.pk-preset-close', function(e){ e.preventDefault(); presetsClose(); })
          .on('click.pkPreset', '.pk-preset-save', function(e){
            e.preventDefault();
            if (!pokID) return;
            const name = ($presetPanel.find('.pk-preset-input').val() || '').toString().trim();
            if (!name) { $presetPanel.find('.pk-preset-input').focus(); return; }
            $.ajax({
              url: "/do/pokemonsAction",
              type: "POST",
              data: "type=presetSave&pokID="+pokID+"&name="+encodeURIComponent(name),
              success: function(r){
                let data=r; try{data=(typeof r==='string')?JSON.parse(r):r;}catch(e){data=null;}
                if (data && (data.error==0 || data.error==='0')) {
                  $presetPanel.find('.pk-preset-input').val('');
                  loadPresets();
                } else {
                  alert((data && data.text) ? data.text : 'Не удалось сохранить пресет');
                }
              },
              error: function(){ alert('Не удалось сохранить пресет'); }
            });
          })
          .on('click.pkPreset', '.pk-preset-apply', function(e){
            e.preventDefault();
            const key = $(this).closest('.pk-preset-item').data('key');
            if (!key) return;
            $.ajax({
              url: "/do/pokemonsAction",
              type: "POST",
              data: "type=applyPreset&pokID="+pokID+"&preset="+encodeURIComponent(key),
              success: function(r){
                let data=r; try{data=(typeof r==='string')?JSON.parse(r):r;}catch(e){data=null;}
                if (data && (data.error==0 || data.error==='0')) {
                  presetsClose();
                  if (window.Game && typeof Game.pokemonTeamTabs === 'function') {
                    Game.pokemonTeamTabs(pokID, 'info');
                  }
                } else {
                  alert((data && data.text) ? data.text : 'Не удалось применить пресет');
                }
              },
              error: function(){ alert('Не удалось применить пресет'); }
            });
          })
          .on('click.pkPreset', '.pk-preset-del', function(e){
            e.preventDefault();
            const key = $(this).closest('.pk-preset-item').data('key');
            if (!key) return;
            if (!confirm('Удалить пресет?')) return;
            $.ajax({
              url: "/do/pokemonsAction",
              type: "POST",
              data: "type=presetDelete&pokID="+pokID+"&preset="+encodeURIComponent(key),
              success: function(r){
                let data=r; try{data=(typeof r==='string')?JSON.parse(r):r;}catch(e){data=null;}
                if (data && (data.error==0 || data.error==='0')) {
                  $presetPanel.find('.pk-preset-input').val('');
                  loadPresets();
                } else {
                  alert((data && data.text) ? data.text : 'Не удалось удалить пресет');
                }
              },
              error: function(){ alert('Не удалось удалить пресет'); }
            });
          });

        // Backdrop close (mobile)
        $presetBackdrop.off('click.pkPreset').on('click.pkPreset', function(){ presetsClose(); });

        
        // События для кнопок
        $btnStat.on('click',function(e){ e.stopPropagation(); $mini.toggle(); $teamPanel.hide(); presetsClose(); });
        $btnTeam.on('click', function(e) {
          e.stopPropagation();
          $mini.hide();
          presetsClose();
          $teamPanel.toggle();
          if ($teamPanel.is(':visible')) {
            loadTeam();
          }
        });
        $btnPreset.on('click', function(e){
          e.stopPropagation();
          if ($presetPanel.is(':visible')) presetsClose();
          else presetsOpen();
        });
        $teamClose.on('click', function() { $teamPanel.hide(); });

        // Обработчик глобальных кликов
        $(document).off('click.pkPanels touchstart.pkPanels').on('click.pkPanels touchstart.pkPanels', function(e) {
          const $target = $(e.target);
          
          if ($target.closest('.nature-tooltip, .nature-trigger').length) {
            return;
          }
          
          if (!$target.closest('.pkMini, .pkStatBtn, .pk-team-panel, .pkTeamBtn, .pk-preset-panel, .pkPresetBtn').length) {
            $mini.hide();
            $teamPanel.hide();
            
            if (!isMobile || !$target.closest('.nature-trigger').length) {
              hideNatureTooltip();
              $('.nature-trigger').removeClass('mobile-active');
            }
          }
        });

        // ИСПРАВЛЕННЫЕ тултипы HP/EXP/Happiness - БЕЗ ДУБЛИРОВАНИЯ
        setTimeout(function() {
          // HP тултипы - проверяем что не обработано
          const hpSelectors = ['.Bar.hp_proggresbar', '.hp_proggresbar', '.HpBar'];
          hpSelectors.forEach(function(selector) {
            const $hpBars = $modal.find(selector).filter(':visible').not('.tooltip-processed');
            $hpBars.each(function() {
              const $hpBar = $(this);
              if ($hpBar.width() > 50) {
                $hpBar.addClass('tooltip-processed'); // Помечаем как обработанный
                
                if (isMobile) {
                  $hpBar.on('click.hpTooltip', function(e) {
                    e.stopPropagation();
                    alert('HP: ' + hpCurrent + ' / ' + hpMax);
                  });
                } else {
                  $hpBar.on('mouseenter.hpTooltip', function() {
                    $(this).attr('title', 'HP: ' + hpCurrent + ' / ' + hpMax);
                  }).css('cursor', 'help');
                }
              }
            });
          });

          // EXP тултипы - аналогично
          const expSelectors = ['.Bar.exp_progressbar', '.exp_progressbar', '.ExpBar'];
          expSelectors.forEach(function(selector) {
            const $expBars = $modal.find(selector).filter(':visible').not('.tooltip-processed');
            $expBars.each(function() {
              const $expBar = $(this);
              if ($expBar.width() > 50) {
                $expBar.addClass('tooltip-processed');
                const expNeed = Math.max(0, expNext - expCurrent);
                
                if (isMobile) {
                  $expBar.on('click.expTooltip', function(e) {
                    e.stopPropagation();
                    alert('Опыт: ' + expCurrent + ' / ' + expNext + ' (нужно: ' + expNeed + ')');
                  });
                } else {
                  $expBar.on('mouseenter.expTooltip', function() {
                    $(this).attr('title', 'Опыт: ' + expCurrent + ' / ' + expNext + ' (нужно: ' + expNeed + ')');
                  }).css('cursor', 'help');
                }
              }
            });
          });

          // Happiness тултипы - аналогично
          const happinessSelectors = ['.Bar.happy_progressbar', '.happy_progressbar', '.HappyBar'];
          happinessSelectors.forEach(function(selector) {
            const $happyBars = $modal.find(selector).filter(':visible').not('.tooltip-processed');
            $happyBars.each(function() {
              const $happyBar = $(this);
              $happyBar.addClass('tooltip-processed');
              
              if (isMobile) {
                $happyBar.on('click.happyTooltip', function(e) {
                  e.stopPropagation();
                  alert('Счастье: ' + happiness + ' / 255');
                });
              } else {
                $happyBar.on('mouseenter.happyTooltip', function() {
                  $(this).attr('title', 'Счастье: ' + happiness + ' / 255');
                }).css('cursor', 'help');
              }
            });
          });
        }, 600);

        // ИСПРАВЛЕННЫЕ EV тултипы - ТОЛЬКО ДЛЯ .Progress элементов
        setTimeout(function() {
          const statNames = ['Здоровье', 'Атака', 'Защита', 'Скорость', 'Спец. Атака', 'Спец. Защита'];
          
          // Ищем ТОЛЬКО элементы .Progress с data-title содержащим "EV"
          $modal.find('.Progress[data-title*="EV"]').not('.tooltip-processed').each(function(idx) {
            const $progressBar = $(this);
            $progressBar.addClass('tooltip-processed');
            
            // Извлекаем EV значение из data-title
            const dataTitle = $progressBar.attr('data-title') || '';
            const evMatch = dataTitle.match(/EV:\s*(\d+)\s*\/\s*126/);
            const evValue = evMatch ? parseInt(evMatch[1]) || 0 : 0;
            
            // Определяем название стата по позиции
            const $statContainer = $progressBar.closest('.Stat');
            let statName = 'Неизвестный стат';
            
            if ($statContainer.length) {
              const $nameEl = $statContainer.find('.Name');
              if ($nameEl.length) {
                const nameText = $nameEl.text().trim();
                // Убираем иконки и лишний текст
                statName = nameText.replace(/[^а-яё\s\.]/gi, '').trim();
              }
            }
            
            // Если не удалось определить из контейнера, используем индекс
            if (statName === 'Неизвестный стат' && idx < statNames.length) {
              statName = statNames[idx];
            }
            
            console.log('EV Tooltip for:', statName, 'Value:', evValue);
            
            const tooltipText = `${statName} EV: ${evValue} / 126`;
            
            if (isMobile) {
              $progressBar.on('click.evTooltip touchstart.evTooltip', function(e) {
                e.stopPropagation();
                e.preventDefault();
                window.ModalShell && ModalShell.tip ? ModalShell.tip(tooltipText, 'EV') : alert(tooltipText);
              });
            } else {
              $progressBar.on('mouseenter.evTooltip', function() {
                $(this).attr('title', tooltipText);
              });
            }
          });
          
          console.log('EV tooltips processed for', $modal.find('.Progress[data-title*="EV"]').length, 'progress bars');
          
        }, 800);

        const nat = (window.PKNATURE||{up:[],down:[]});
        $info.find('.Green-Color, .Red-Color').each(function(){
          const t=$(this).text().trim(); 
          const statNames = ['Здоровье','Атака','Защита','Скорость','Спец. Атака','Спец. Защита'];
          if(statNames.indexOf(t)>-1){
            if($(this).hasClass('Green-Color')) nat.up.push(t); 
            else nat.down.push(t);
          }
        });
        window.PKNATURE = nat;

        Game.loaders.worldClose();
        return;
      }

      // Остальные case'ы (сокращено)
      if (r === 'attack'){
        response = (typeof response === 'string') ? JSON.parse(response) : response;
        $('#HiddenLoad').remove();
        $('<div/>',{"class":"Name", html:'<i class="fas fa-times"></i> Атаки',
          click:function(){ $('#Hidden'+i).remove(); }}).appendTo('#Hidden'+i);
        $('<div/>',{"class":"Attacks", html: response.html}).appendTo('#Hidden'+i);
        return;
      }

      if (r === 'stats'){
        response = (typeof response === 'string') ? JSON.parse(response) : response;
        $('#HiddenLoad').remove();
        $('<div/>',{"class":"Name", html:'<i class="fas fa-times"></i> Статы',
          click:function(){ $('#Hidden'+i).remove(); }}).appendTo('#Hidden'+i);
        // Добавляем статы без дублирования обработчиков
        return;
      }

      Game.notifications.main('Ошибка. Попробуйте снова.', "error");
    }
  });
},
	buyThingsAquarits: function(t) {
		$.ajax({
			url:"/do/itemsAction",
			type:"POST",
			data:"itemID="+t+"&type=buyDonat",
			success:function(response){
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				Game.notifications.main((response['error'] == 0 ? 'Предмет удачно куплен.' : 'Недостаточно аметистов.'),(response['error'] == 0 ? 'success' : 'error'));
			}
		});
	},
	setColor: function(t) {
		$.ajax({
			url:"/do/trainers",
			type:"POST",
			data:"type=setColor&color="+t,
			success:function(t){
				Game.notifications.main('Вы успешно изменили цвет.',"success");
			}
		});
	},
trenercard: {
  _activeRq: null,

  /* ---------- utils ---------- */
  _escMap: {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#x60;'},
  _esc: function(v){
    if(v===undefined||v===null) return '';
    return String(v).replace(/[&<>"'`]/g, s => this._escMap[s]);
  },

  /* ---------- prefs (theme & art) ---------- */
  _prefKey: function(uid){ return 'trainercard:prefs:'+uid; },
  _loadPrefs: function(uid){
    try{ return JSON.parse(localStorage.getItem(this._prefKey(uid)) || '{}'); }catch(_){ return {}; }
  },
  _savePrefs: function(uid, prefs){
    try{ localStorage.setItem(this._prefKey(uid), JSON.stringify(prefs || {})); }catch(_){}
  },

  /* Темы (градиент прогресса + вуаль справа) */
  _themes: {
    default: {name:'Default', grad:'linear-gradient(90deg,#fbbf24,#f59e0b,#84cc16)', veil:'linear-gradient(180deg, rgba(255,255,255,.12), rgba(255,255,255,.25))'},
    water:   {name:'Water',   grad:'linear-gradient(90deg,#60a5fa,#3b82f6,#06b6d4)', veil:'linear-gradient(180deg, rgba(219,234,254,.15), rgba(147,197,253,.30))'},
    fire:    {name:'Fire',    grad:'linear-gradient(90deg,#f97316,#ef4444,#ef4444)', veil:'linear-gradient(180deg, rgba(254,226,226,.15), rgba(252,165,165,.30))'},
    grass:   {name:'Grass',   grad:'linear-gradient(90deg,#22c55e,#84cc16,#65a30d)', veil:'linear-gradient(180deg, rgba(220,252,231,.15), rgba(187,247,208,.30))'},
    electric:{name:'Electric',grad:'linear-gradient(90deg,#fde047,#facc15,#f59e0b)', veil:'linear-gradient(180deg, rgba(254,249,195,.15), rgba(253,224,71,.30))'},
    dark:    {name:'Dark',    grad:'linear-gradient(90deg,#a78bfa,#22d3ee,#38bdf8)', veil:'linear-gradient(180deg, rgba(238,242,255,.15), rgba(196,181,253,.30))'}
  },
  _applyTheme: function($wrap, themeId, artUrl){
    var th = this._themes[themeId] || this._themes.default;
    $wrap.css({'--tc-grad': th.grad});
    $wrap.find('.tcRightVeil').css('background', th.veil);
    var url = (artUrl && String(artUrl).trim()) ? String(artUrl).trim() : '';
    if (url){
      $wrap.find('.tcRightBg').attr('style', '--tc-right-bg:url("'+url.replace(/"/g,'\\"')+'")');
    } else {
      $wrap.find('.tcRightBg').attr('style','');
    }
  },

  /* ---------- styles ---------- */
  _ensureCSS: function(){
    if (document.getElementById('tcGlassCSS')) return;
    var css = `
/* ===================== TrainerCard • Light Glass UI (tcModal) ===================== */
.tcModal{
  position:fixed; left:50%; top:50%; transform:translate(-50%,-50%);
  width:min(1120px,91vw); max-height:90vh; overflow:auto; z-index:10000;
  border-radius:24px;
}
.tcModal.tcGlass{display:block}
.tcModal.tcGlass .Header{
  position:sticky; top:0; z-index:2; padding:12px 16px 14px 18px;
  font-weight:800; 
  background: linear-gradient(135deg, 
    rgba(255,255,255,0.25) 0%, 
    rgba(255,255,255,0.18) 50%, 
    rgba(248,250,252,0.20) 100%
  ), 
  radial-gradient(800px 400px at -5% 0%, rgba(99,102,241,0.08), transparent),
  radial-gradient(600px 300px at 105% 20%, rgba(16,185,129,0.06), transparent),
  #f8fafc;
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.3);
  border-bottom: 1px solid rgba(226,232,240,0.4);
  font-size:15px; color:#334155; letter-spacing:.3px;
  box-shadow: 0 1px 3px rgba(15,23,42,0.1);
}
.tcModal.tcGlass .Close{
  position:absolute; top:10px; right:10px; z-index:3; width:32px; height:32px;
  display:flex; align-items:center; justify-content:center; color:#64748b;
  cursor:pointer; border-radius:12px; transition:.2s; 
  background:rgba(255,255,255,0.6);
  border:1px solid rgba(255,255,255,0.4); 
  backdrop-filter:blur(10px);
  box-shadow: 0 2px 8px rgba(15,23,42,0.1);
}
.tcModal.tcGlass .Close:hover{
  color:#1e293b;
  background:rgba(255,255,255,0.8);
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(15,23,42,0.15);
}

/* Контейнер фона */
.tcGlassWrap{
  --tc-border: rgba(255,255,255,0.3);
  background: linear-gradient(135deg, 
    rgba(255,255,255,0.25) 0%, 
    rgba(255,255,255,0.18) 50%, 
    rgba(248,250,252,0.20) 100%
  ), 
  radial-gradient(1200px 600px at -8% 0%, rgba(99,102,241,0.08), transparent),
  radial-gradient(800px 400px at 108% 25%, rgba(16,185,129,0.06), transparent),
  #f1f5f9;
  border:1px solid rgba(255,255,255,0.3);
  box-shadow:0 25px 50px -12px rgba(15,23,42,0.25), 
             0 8px 16px rgba(15,23,42,0.1),
             inset 0 1px 0 rgba(255,255,255,0.4);
  backdrop-filter: blur(20px);
  overflow:hidden; 
}

/* ===== СЕТКА: слева аватар, справа инфо ===== */
.tcGlassGrid{
  display:grid; gap:16px; padding:16px;
  grid-template-columns: 360px minmax(620px, 1fr);
  align-items:start; justify-content:center;
}
@media (max-width:1100px){ .tcGlassGrid{ grid-template-columns: 300px minmax(520px, 1fr); gap:12px; } }
@media (max-width:980px){ 
  .tcGlassGrid{ 
    grid-template-columns:1fr; 
    gap:12px; 
    padding:12px; 
  } 
}
@media (max-width:480px){ 
  .tcGlassGrid{ 
    gap:8px; 
    padding:8px; 
  } 
}

/* ===== ЛЕВАЯ КОЛОНКА ===== */
.tcLeft{
  position:relative; overflow:hidden; border-radius:20px;
  backdrop-filter: blur(15px);
  min-height:577px;
  width:360px; min-width:360px;
}
.tcLeft .leftGlassBg{position:absolute; inset:0}
.tcLeftInner{position:relative; z-index:2}

/* Аватар */
.big-ava{
  width:100%; height:577px;
  background-size:contain; background-repeat:no-repeat; background-position:center;
  border-radius:16px; cursor:pointer;
  box-shadow: 0 8px 25px rgba(15,23,42,0.15), 
              inset 0 1px 0 rgba(255,255,255,0.2);
}
.tcLeft .okTick{
  position:absolute; left:24px; bottom:24px; width:56px; height:56px; z-index:3;
  display:flex; align-items:center; justify-content:center; color:#fff; 
  background:linear-gradient(135deg, #10b981, #059669);
  border-radius:50%; 
  box-shadow:0 10px 24px rgba(16,185,129,.35), 
             0 4px 12px rgba(16,185,129,.2); 
  border:3px solid rgba(255,255,255,.8);
}

/* Клан — левый верх */
.tcLeft .ClanBadge{
  position:absolute; left:12px; top:12px; width:44px; height:44px;
  background-size:contain;background-position:center; background-repeat:no-repeat;
  filter: drop-shadow(0 6px 12px rgba(15,23,42,.2)); cursor:pointer; z-index:4;
  border-radius: 12px;
  border: 2px solid rgba(255,255,255,0.6);
  backdrop-filter: blur(8px);
}


/* Клан + фракция (плашка 18px рядом) */
.tcLeft .clan-badges-wrap{
  position:absolute; left:12px; top:12px;
  display:flex; align-items:center; gap:8px;
  z-index:4;
}
.tcLeft .clan-badges-wrap .ClanBadge{
  position:relative; left:auto; top:auto;
}
.tcLeft .ClanFactionBadge{
  height:18px;
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:0 8px 0 7px;
  border-radius:9px;
  background:rgba(243,244,246,.95);
  color:#111827;
  border:1px solid rgba(0,0,0,0.08);
  box-shadow:0 6px 14px rgba(15,23,42,.18);
  font-size:11px;
  line-height:18px;
  font-weight:800;
  letter-spacing:0.2px;
  cursor:pointer;
  user-select:none;
  white-space:nowrap;
}
.tcLeft .ClanFactionBadge:hover{ background:rgba(236,239,243,.98); }
.tcLeft .ClanFactionBadge .f-dot{
  width:9px; height:9px;
  border-radius:3px;
  background:var(--faction-color);
  box-shadow:inset 0 0 0 1px rgba(0,0,0,0.12);
}
.tcLeft .ClanFactionBadge .f-text{ transform: translateY(-0.5px); }
/* Кнопка выбора покемона — правый верх */
.tcLeft .cornerPaw{
  position:absolute; right:12px; top:12px; width:40px; height:40px; z-index:4;
  display:flex; align-items:center; justify-content:center;
  background:rgba(255,255,255,0.6); 
  border:1px solid rgba(255,255,255,0.4);
  color:#475569; border-radius:14px; cursor:pointer; 
  backdrop-filter: blur(12px);
  box-shadow: 0 4px 12px rgba(15,23,42,0.1);
  transition: all 0.2s ease;
}
.tcLeft .cornerPaw:hover{ 
  background:rgba(255,255,255,0.8);
  transform: scale(1.05);
  color:#1e293b;
}

/* Покемон — фикс справа от тренера */
.trainercard-pokemon-img{
  position:absolute; right:1px; bottom:10px;
  width:190px; height:200px; object-fit:contain; z-index:3;
  filter: drop-shadow(0 12px 20px rgba(15,23,42,.15));
}
.trainercard-tera-badge{
  position:absolute;
  right:12px;
  bottom:220px;
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:4px 10px;
  border-radius:999px;
  background:rgba(255,255,255,.92);
  border:1px solid rgba(148,163,184,.5);
  color:#0f172a;
  font-size:11px;
  font-weight:800;
  z-index:5;
  box-shadow:0 6px 16px rgba(15,23,42,.12);
}
.trainercard-tera-badge img{
  width:16px;
  height:16px;
}

/* ===== ПРАВАЯ КОЛОНКА ===== */
.tcRight{ 
  position:relative; border-radius:20px; overflow:hidden; height:577px;
  border:1px solid var(--tc-border);
  background: linear-gradient(135deg, 
    rgba(255,255,255,0.4) 0%, 
    rgba(255,255,255,0.25) 50%, 
    rgba(248,250,252,0.3) 100%
  );
  backdrop-filter: blur(15px);
  box-shadow: 0 8px 32px rgba(15,23,42,0.1), 
              inset 0 1px 0 rgba(255,255,255,0.6);
}
.tcRightBg{
  position:absolute; inset:0; 
  background:var(--tc-right-bg, url('/img/ui/trainer_right_bg.jpg')) center/cover no-repeat; 
  filter:saturate(0.9) contrast(0.95) brightness(1.1);
  opacity: 0.8;
}
.tcRightVeil{
  position:absolute; inset:0; 
  background:linear-gradient(180deg, rgba(255,255,255,.12), rgba(255,255,255,.25));
}
.tcRightInner{position:relative; z-index:2; padding:18px}

/* ===== Инфо-карточка ===== */
.tcInfo{ position:relative; color:#1e293b; }
.tcInfo .designTopBtn{
  position:absolute; right:12px; top:-1px;
  padding:8px 14px; border-radius:12px; 
  border:1px solid rgba(99,102,241,0.3); 
  background:linear-gradient(135deg, rgba(255,255,255,0.8), rgba(248,250,252,0.9)); 
  color:#4338ca; font-weight:800; cursor:pointer;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(99,102,241,0.15);
  transition: all 0.2s ease;
}
.tcInfo .designTopBtn:hover{
  background:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(243,244,246,1));
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(99,102,241,0.2);
}
.tcInfo .nameRow{display:flex; align-items:center; gap:10px; flex-wrap:wrap;}
.tcInfo .nameRow .dot{
  width:12px;height:12px;
  border-radius:50%;
}
.tcInfo .subRow{
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  color:#475569; opacity:.9; margin-top:8px; font-weight:700
}
.tcInfo .pill{
  display:inline-flex; align-items:center; border-radius:999px; 
  padding:6px 12px; 
  background:rgba(255,255,255,0.7); 
  color:#4338ca; font-weight:800; 
  border:1px solid rgba(99,102,241,0.2); 
  font-size:12px;
  backdrop-filter: blur(8px);
  box-shadow: 0 2px 8px rgba(15,23,42,0.08);
}
.tcInfo .pill.green{
  background:rgba(220,252,231,0.8); 
  border-color:rgba(16,185,129,0.3); 
  color:#065f46;
}
.tcInfo .pill.soft{
  background:rgba(248,250,252,0.8); 
  border-color:rgba(203,213,225,0.4); 
  color:#334155;
}

/* level/rank/hours */
.ltRow{display:grid; grid-template-columns:repeat(3,minmax(120px,1fr)); gap:14px; margin:6px 0}
.lt{
  background:linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.95)); 
  border:1px solid rgba(255,255,255,0.4); 
  border-radius:16px; padding:18px; text-align:center; 
  box-shadow:0 8px 25px rgba(15,23,42,.08),
             inset 0 1px 0 rgba(255,255,255,0.6);
  backdrop-filter: blur(10px);
}
.lt .lbl{font-size:11px; letter-spacing:.1em; color:#64748b; font-weight:800; text-transform: uppercase;}
.lt .val{margin-top:8px; font-size:22px; font-weight:900; color:#1e293b}

/* xp */
.xpBlock .cap{font-weight:800; color:#475569; margin-bottom:8px}
.xpRow{display:grid; grid-template-columns: 1fr auto; align-items:center; gap:14px}
.xpBar{
  height:12px; 
  background:rgba(226,232,240,0.8); 
  border-radius:999px; overflow:hidden;
  border: 1px solid rgba(255,255,255,0.3);
  box-shadow: inset 0 2px 4px rgba(15,23,42,0.06);
}
.xpBar>div{
  height:100%; 
  background:var(--tc-grad);
  border-radius:999px;
  box-shadow: 0 0 8px rgba(251,191,36,0.3);
}

/* summary 4 tiles */
.sum4{margin-top:5px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px}
.sum4 .s{
  background:linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.95));
  border:1px solid rgba(255,255,255,0.4);
  border-radius:16px;padding:8px;text-align:center;
  box-shadow:0 8px 25px rgba(15,23,42,.08),
             inset 0 1px 0 rgba(255,255,255,0.6);
  backdrop-filter: blur(10px);
}
.sum4 .s .t{font-size:11px;letter-spacing:.1em;color:#64748b;font-weight:800;text-transform: uppercase;}
.sum4 .s .v{margin-top:6px;font-size:20px;font-weight:900;color:#1e293b}

/* tabs */
.Tabses{margin-top:16px}
.Tabs{display:flex; gap:12px; flex-wrap:wrap}
.tcInfo .Tabs>div{
  padding:10px 16px; border-radius:999px; 
  border:1px solid rgba(203,213,225,0.4); 
  background:linear-gradient(135deg, rgba(255,255,255,0.8), rgba(248,250,252,0.9));
  color:#334155; font-weight:800; font-size:13px; cursor:pointer;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(15,23,42,0.06);
  transition: all 0.2s ease;
}
.tcInfo .Tabs>div:hover{
  background:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(243,244,246,1));
  transform: translateY(-1px);
}
.tcInfo .Tabs>div.active{
  background:linear-gradient(135deg, #4f46e5, #6366f1); 
  color:#fff; 
  border-color:rgba(99,102,241,0.5);
  box-shadow: 0 6px 16px rgba(99,102,241,0.3);
}
.Table{margin-top:14px}

/* ======= Превью-блоки ======= */
.previewBox{
  background:linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.95));
  border-radius:18px;padding:14px; 
  backdrop-filter: blur(10px);
}
.previewHead{display:grid; grid-template-columns:1fr auto; align-items:center; margin-bottom:12px}
.previewHead .ttl{font-weight:900; font-size:16px; color:#1e293b;}
.previewHead .viewAll{
  font-weight:800; color:#4338ca; cursor:pointer; 
  padding:8px 12px; border-radius:10px;
  transition: all 0.2s ease;
}
.previewHead .viewAll:hover{
  background:rgba(99,102,241,0.1);
  transform: translateY(-1px);
}

/* Badges превью */
.badgesGrid{display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); gap:14px}
.badge{
  height:90px;border-radius:18px;
  border:1px solid rgba(203,213,225,0.4);
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg, rgba(255,255,255,0.8), rgba(248,250,252,0.9));
  cursor:pointer;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(15,23,42,0.06);
  transition: all 0.2s ease;
  position: relative;
}
.badge:hover{
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(15,23,42,0.12);
}
.badge img{max-width:74px; max-height:74px; object-fit:contain;}

/* Подарки / Achieves / Друзья / Желания превью */
.Friends,.Achivs,.Gift,.Wish{display:grid; gap:12px}
.gift-card{
  display:grid; grid-template-columns:60px 1fr; gap:10px; align-items:center; 
  padding:12px; border-radius:14px; 
  border:1px solid rgba(203,213,225,0.4); 
  background:linear-gradient(135deg, rgba(255,255,255,0.8), rgba(248,250,252,0.9)); 
  color:#1e293b; cursor:pointer; 
  box-shadow:0 4px 12px rgba(15,23,42,.06);
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
}
.gift-card:hover{
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(15,23,42,0.12);
}
.gift-card-thumb .gift-img{
  width:80px;height:80px;object-fit:contain;border-radius:12px;
  background:rgba(248,250,252,0.8);
}
.gift-title{font-weight:900; color:#1e293b;}
.gift-from,.gift-date{font-size:12px; opacity:.8; color:#64748b;}

/* Модалка "View all" */
.seeAllModal{
  position:fixed; inset:0; 
  background:rgba(15,23,42,.4); 
  backdrop-filter:blur(8px); 
  display:flex; align-items:center; justify-content:center; z-index:210000
}
.seeAllCard{
  width:min(980px,94vw); max-height:88vh; overflow:auto; 
  background:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.98)); 
  color:#1e293b; 
  border:1px solid rgba(255,255,255,0.4); 
  border-radius:20px; 
  box-shadow:0 25px 50px -12px rgba(15,23,42,.25),
             inset 0 1px 0 rgba(255,255,255,0.6); 
  padding:16px 16px 18px 16px; position:relative;
  backdrop-filter: blur(20px);
}
.seeAllClose{
  position:absolute; right:14px; top:12px; font-size:22px; 
  color:#64748b; cursor:pointer;
  transition: all 0.2s ease;
}
.seeAllClose:hover{
  color:#1e293b;
  transform: scale(1.1);
}
.seeAllTitle{font-size:20px; font-weight:900; margin:4px 32px 14px 8px; color:#1e293b;}
.seeAllGridBadges{display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:14px}
.seeAllGridBadges .badge{height:100px; border-radius:18px}
.seeAllGridBadges .badge img{max-width:82px; max-height:82px}
.seeAllList{display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px}
.seeAllList .gift-card{width:100%}

/* skeleton */
.trainercard-skeleton{
  display:grid; grid-template-columns:360px minmax(620px, 1fr); gap:16px; padding:16px; justify-content:center
}
@media(max-width:980px){.trainercard-skeleton{grid-template-columns:1fr}}
.tc-skel-left,.tc-skel-right{
  border-radius:20px; 
  border:1px solid rgba(255,255,255,.3); 
  background:linear-gradient(135deg, rgba(255,255,255,0.4), rgba(248,250,252,0.5)); 
  backdrop-filter:blur(15px); 
  padding:14px; min-height:200px
}
.skel-ava{
  width:100%; height:420px; border-radius:16px; 
  background:linear-gradient(90deg,rgba(203,213,225,.3),rgba(226,232,240,.5),rgba(203,213,225,.3)); 
  background-size:300% 100%; animation:skel 1.4s infinite
}
.skel-line{
  height:14px; border-radius:999px; margin:8px 0; 
  background:linear-gradient(90deg,rgba(203,213,225,.3),rgba(226,232,240,.5),rgba(203,213,225,.3)); 
  background-size:300% 100%; animation:skel 1.4s infinite
}
.skel-line.w1{width:65%}.skel-line.w2{width:45%}.skel-line.w3{width:85%}
@keyframes skel{0%{background-position:0 0}100%{background-position:300% 0}}

/* мини-панель */
#miniPanel{
  display:block; position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); z-index:200000;
  background:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.98)); 
  color:#1e293b;
  border:1px solid rgba(255,255,255,0.4); 
  border-radius:18px; padding:24px 22px; 
  box-shadow:0 25px 50px -12px rgba(15,23,42,.3),
             inset 0 1px 0 rgba(255,255,255,0.6);
  backdrop-filter: blur(20px);
}
#miniPanel .miniPanelBtn{
  appearance:none; 
  border:1px solid rgba(203,213,225,0.4); 
  background:linear-gradient(135deg, rgba(255,255,255,0.8), rgba(248,250,252,0.9)); 
  font-weight:700; font-size:13px; padding:10px 16px; 
  border-radius:12px; cursor:pointer; color:#475569;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(15,23,42,0.06);
  transition: all 0.2s ease;
}
#miniPanel .miniPanelBtn:hover{
  background:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(243,244,246,1));
  transform: translateY(-1px);
}

/* модалки выбора покемона и оформления */
.pokemon-modal, .design-modal{
  position:fixed; inset:0; 
  background:rgba(15,23,42,.4); 
  backdrop-filter:blur(8px); 
  display:flex; align-items:center; justify-content:center; z-index:200000
}
.pokemon-modal-inner, .design-modal-inner{
  width:min(920px,92vw); max-height:86vh; overflow:auto; 
  background:linear-gradient(135deg, rgba(30,41,59,0.95), rgba(15,23,42,0.98)); 
  border:1px solid rgba(255,255,255,.15); 
  border-radius:20px; 
  box-shadow:0 25px 50px -12px rgba(15,23,42,.4),
             0 8px 18px rgba(15,23,42,.2),
             inset 0 1px 0 rgba(255,255,255,0.1); 
  padding:16px; color:#e2e8f0; position:relative;
  backdrop-filter: blur(20px);
}
.modal-close, .design-close{
  position:absolute; right:16px; top:12px; cursor:pointer; 
  font-size:22px; color:#94a3b8;
  transition: all 0.2s ease;
}
.modal-close:hover, .design-close:hover{
  color:#f1f5f9;
  transform: scale(1.1);
}
.pokemon-modal-header{padding:8px 10px 12px}
.pm-title{font-weight:900; font-size:18px; color:#f1f5f9;}
.pm-sub{font-size:12px; opacity:.8; color:#cbd5e1;}
.pokemon-choice-list{display:grid; grid-template-columns:repeat(auto-fill,minmax(92px,1fr)); gap:10px; padding:10px}
.pokemon-choice{
  border:1px solid rgba(255,255,255,.18); 
  background:rgba(255,255,255,.08); 
  border-radius:16px; padding:10px; cursor:pointer; 
  position:relative; transition:.2s;
  backdrop-filter: blur(8px);
}
.pokemon-choice:hover{
  background:rgba(255,255,255,.15);
  transform: translateY(-2px);
}
.pm-img-wrap{display:flex; align-items:center; justify-content:center}
.pm-img-wrap img{width:76px; height:76px; object-fit:contain}
.pm-sel-check{
  position:absolute; right:8px; top:8px; width:22px; height:22px; 
  border-radius:50%; display:none; align-items:center; justify-content:center; 
  background:linear-gradient(135deg, #10b981, #059669); color:#fff;
  box-shadow: 0 4px 12px rgba(16,185,129,0.4);
}
.pokemon-choice.selected .pm-sel-check{display:flex}
.pokemon-choice.is-loading{opacity:.6; cursor:progress}
.pm-num{text-align:center; font-size:12px; opacity:.9; margin-top:8px; color:#cbd5e1;}
.pm-inline-error{
  margin:8px 12px 0; 
  background:rgba(254,226,226,0.9); 
  color:#dc2626; 
  border:1px solid rgba(248,113,113,0.5); 
  border-radius:12px; padding:10px 12px; font-weight:800;
  backdrop-filter: blur(8px);
}

/* Модалка «оформления» (арт + тема) */
.design-grid{display:grid; grid-template-columns:340px 1fr; gap:14px}
@media(max-width:760px){.design-grid{grid-template-columns:1fr}}
.design-preview{
  border:1px solid rgba(255,255,255,.2); 
  border-radius:18px; overflow:hidden;
  background:rgba(255,255,255,.05);
}
.design-preview .pv-header{
  padding:10px 12px; font-weight:900; 
  background:rgba(255,255,255,.12);
  color:#f1f5f9;
}
.design-preview .pv-body{
  position:relative; min-height:240px; 
  background:linear-gradient(135deg, #334155, #1e293b);
}
.design-preview .pv-art{
  position:absolute; inset:0; 
  background-size:cover; background-position:center; opacity:.4
}
.design-preview .pv-veil{position:absolute; inset:0; pointer-events:none}
.design-controls{
  border:1px solid rgba(255,255,255,.2); 
  border-radius:18px; padding:12px;
  background:rgba(255,255,255,.05);
}
.theme-list{
  display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); 
  gap:10px; margin-top:10px
}
.theme-card{
  border:1px solid rgba(255,255,255,.2); 
  border-radius:16px; padding:12px; cursor:pointer; 
  background:rgba(255,255,255,.08);
  transition: all 0.2s ease;
  backdrop-filter: blur(8px);
}
.theme-card:hover{
  background:rgba(255,255,255,.15);
  transform: translateY(-1px);
}
.theme-swatch{height:24px; border-radius:999px; margin-top:8px}
.design-row{
  display:grid; grid-template-columns:1fr auto; gap:10px; 
  align-items:center; margin-top:12px
}
.design-input{
  width:100%; padding:10px 12px; border-radius:12px; 
  border:1px solid rgba(255,255,255,.25); 
  background:rgba(255,255,255,.08); 
  color:#e2e8f0;
  backdrop-filter: blur(8px);
}
.design-btn{
  padding:10px 14px; border-radius:12px; 
  background:rgba(255,255,255,.12); 
  border:1px solid rgba(255,255,255,.25); 
  color:#e2e8f0; cursor:pointer; font-weight:800;
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
}
.design-btn:hover{
  background:rgba(255,255,255,.2);
  transform: translateY(-1px);
}

/* ===== МОБИЛЬНАЯ АДАПТАЦИЯ ===== */

/* Планшеты и маленькие ноутбуки */
@media (max-width:1100px){
  .tcModal{width:min(900px,94vw)}
  .tcLeft{width:300px; min-width:300px; min-height:480px}
  .big-ava{height:480px}
  .trainercard-pokemon-img{width:150px; height:160px; right:8px; bottom:8px}
  .trainercard-tera-badge{bottom:176px; right:8px; font-size:10px}
  .tcRight{height:480px}
  .ltRow{gap:10px}
  .lt{padding:14px}
  .lt .val{font-size:20px}
  .sum4{gap:10px}
  .sum4 .s{padding:12px}
  .sum4 .s .v{font-size:18px}
}

/* Планшеты портретная ориентация */
@media (max-width:980px){
  .tcModal{width:min(600px,96vw)}
  .tcLeft{width:100%; min-width:auto; min-height:400px}
  .big-ava{height:400px}
  .trainercard-pokemon-img{width:130px; height:140px; right:6px; bottom:6px}
  .trainercard-tera-badge{bottom:156px; right:6px; font-size:10px}
  .tcRight{height:auto; min-height:400px}
  .tcRightInner{padding:14px}
  .designTopBtn{right:8px; top:8px; padding:6px 10px; font-size:12px}
  .ltRow{grid-template-columns:repeat(3,1fr); gap:8px}
  .lt{padding:12px}
  .lt .val{font-size:18px}
  .sum4{grid-template-columns:repeat(2,1fr); gap:8px}
  .Tabs{gap:8px}
  .tcInfo .Tabs>div{padding:8px 12px; font-size:12px}
  .previewBox{padding:10px}
  
  /* Планшетная адаптация для наград */
  .badgesGrid{grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); gap:10px}
  .badge{height:80px; border-radius:14px}
  .badge img{max-width:65px; max-height:65px}
}

/* Мобильные телефоны */
@media (max-width:640px){
  .tcModal{width:98vw; max-height:95vh; border-radius:16px}
  .tcModal.tcGlass .Header{padding:8px 12px 10px 14px; font-size:14px}
  .tcModal.tcGlass .Close{width:28px; height:28px; top:8px; right:8px}
  .tcGlassGrid{padding:8px; gap:8px}
  .tcLeft{border-radius:16px; min-height:320px}
  .big-ava{height:320px; border-radius:12px}
  .trainercard-pokemon-img{width:130px; height:130px; right:4px; bottom:4px}
  .trainercard-tera-badge{bottom:140px; right:4px; font-size:10px; padding:3px 8px}
  .tcLeft .ClanBadge{width:36px; height:36px; left:8px; top:8px}
  .tcLeft .cornerPaw{width:36px; height:36px; right:8px; top:8px}
  .tcLeft .okTick{width:48px; height:48px; left:16px; bottom:16px}
  .tcRight{border-radius:16px}
  .tcRightInner{padding:10px}
  .designTopBtn{right:6px; top:6px; padding:5px 8px; font-size:11px}
  .tcInfo .nameRow{gap:6px}
  .tcInfo .nameRow div:first-child{font-size:22px!important}
  .tcInfo .subRow{gap:6px; margin-top:6px; flex-wrap:wrap}
  .tcInfo .pill{padding:4px 8px; font-size:11px}
  .ltRow{grid-template-columns:1fr; gap:6px; margin:8px 0}
  .lt{padding:10px}
  .lt .lbl{font-size:10px}
  .lt .val{font-size:16px; margin-top:4px}
  .xpBlock .cap{font-size:12px; margin-bottom:6px}
  .xpRow{gap:8px}
  .xpBar{height:10px}
  .sum4{grid-template-columns:repeat(2,1fr); gap:6px; margin-top:8px}
  .sum4 .s{padding:8px}
  .sum4 .s .t{font-size:10px}
  .sum4 .s .v{font-size:16px; margin-top:4px}
  .Tabses{margin-top:10px}
  .Tabs{gap:6px}
  .tcInfo .Tabs>div{padding:6px 10px; font-size:11px}
  .Table{margin-top:8px}
  .previewBox{padding:8px; border-radius:14px}
  .previewHead{margin-bottom:8px}
  .previewHead .ttl{font-size:14px}
  .previewHead .viewAll{padding:6px 8px; font-size:12px}
  
  /* Улучшенная мобильная адаптация для наград */
  .badgesGrid{
    grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); 
    gap:8px;
  }
  .badge{
    height:80px; 
    border-radius:12px;
    padding:6px;
    box-shadow: 0 2px 8px rgba(15,23,42,0.08);
  }
  .badge img{
    max-width:68px; 
    max-height:68px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
  }
  .badge:hover{
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(15,23,42,0.15);
  }
  
  /* Остальные элементы */
  .gift-card{padding:8px; gap:8px; border-radius:10px}
  .gift-card-thumb .gift-img{width:50px; height:50px}
  .gift-title{font-size:13px}
  .gift-from, .gift-date{font-size:11px}
}

/* Очень маленькие экраны */
@media (max-width:480px){
  .tcModal{width:100vw; max-height:92vh; border-radius:12px}
  .tcGlassGrid{padding:6px; gap:6px}
  .tcLeft{min-height:280px; border-radius:12px}
  .big-ava{height:280px; border-radius:10px}
  .trainercard-pokemon-img{width:140px; height:140px; right:2px; bottom:8px}
  .trainercard-tera-badge{bottom:152px; right:4px; font-size:10px; padding:3px 8px}
  .tcRight{border-radius:12px}
  .tcRightInner{padding:8px}
  .tcInfo .nameRow div:first-child{font-size:20px!important}
  .ltRow{gap:4px}
  .lt{padding:8px; border-radius:12px}
  .lt .val{font-size:14px}
  .sum4{gap:4px}
  .sum4 .s{padding:6px; border-radius:12px}
  .sum4 .s .v{font-size:14px}
  .previewBox{padding:6px; border-radius:12px}
  
  /* Оптимизация наград для очень маленьких экранов */
  .badgesGrid{
    grid-template-columns:repeat(auto-fill,minmax(75px,1fr)); 
    gap:6px;
  }
  .badge{
    height:75px; 
    border-radius:10px;
    padding:4px;
    box-shadow: 0 2px 6px rgba(15,23,42,0.08);
  }
  .badge img{
    max-width:67px; 
    max-height:67px;
    filter: drop-shadow(0 1px 3px rgba(0,0,0,0.1));
  }
  
  .gift-card{padding:6px; gap:6px; border-radius:8px}
  .gift-card-thumb .gift-img{width:40px; height:40px; border-radius:8px}
  .gift-title{font-size:12px}
  .gift-from, .gift-date{font-size:10px}
}

/* Специальная адаптация для экранов шириной 320-375px */
@media (max-width:375px){
  .badgesGrid{
    grid-template-columns:repeat(4,1fr); 
    gap:4px;
  }
  .badge{
    height:70px; 
    padding:3px;
  }
  .badge img{
    max-width:64px; 
    max-height:64px;
  }
}

/* Модальные окна на мобильных */
@media (max-width:640px){
  .seeAllCard{width:96vw; padding:12px; border-radius:16px}
  .seeAllTitle{font-size:18px; margin:2px 28px 10px 6px}
  
  /* Улучшенная сетка наград в модальном окне */
  .seeAllGridBadges{
    grid-template-columns:repeat(auto-fill,minmax(90px,1fr)); 
    gap:10px;
  }
  .seeAllGridBadges .badge{
    height:90px; 
    border-radius:16px;
    padding:6px;
  }
  .seeAllGridBadges .badge img{
    max-width:78px; 
    max-height:78px;
  }
  
  .seeAllList{grid-template-columns:1fr; gap:8px}
  
  .pokemon-modal-inner, .design-modal-inner{width:96vw; padding:12px; border-radius:16px}
  .pokemon-choice-list{grid-template-columns:repeat(auto-fill,minmax(70px,1fr)); gap:6px; padding:6px}
  .pokemon-choice{padding:6px; border-radius:12px}
  .pm-img-wrap img{width:60px; height:60px}
  .pm-sel-check{width:18px; height:18px; right:4px; top:4px}
  .pm-num{font-size:11px; margin-top:4px}
  
  .design-grid{grid-template-columns:1fr; gap:10px}
  .design-preview .pv-body{min-height:180px}
  .theme-list{grid-template-columns:repeat(auto-fit,minmax(100px,1fr)); gap:6px}
  .theme-card{padding:8px; border-radius:12px}
  .theme-swatch{height:20px; margin-top:6px}
  .design-row{grid-template-columns:1fr; gap:8px}
  .design-input, .design-btn{padding:8px 10px; border-radius:10px}
  
  #miniPanel{padding:16px 14px; border-radius:14px}
  #miniPanel .miniPanelBtn{padding:8px 12px; font-size:12px; border-radius:10px}
}

/* Горизонтальная ориентация на маленьких экранах */
@media (max-width:640px) and (orientation:landscape){
  .tcModal{max-height:100vh}
  .tcLeft{min-height:240px}
  .big-ava{height:240px}
  .trainercard-pokemon-img{width:75px; height:85px}
  .trainercard-tera-badge{bottom:100px; right:6px; font-size:9px; padding:2px 6px}
  .ltRow{grid-template-columns:repeat(3,1fr)}
  .sum4{grid-template-columns:repeat(4,1fr)}
  
  /* Компактные награды в горизонтальной ориентации */
  .badgesGrid{
    grid-template-columns:repeat(auto-fill,minmax(70px,1fr)); 
    gap:6px;
  }
  .badge{
    height:70px;
  }
  .badge img{
    max-width:60px; 
    max-height:60px;
  }
}

/* Дополнительная адаптация для очень узких экранов */
@media (max-width:320px){
  .badgesGrid{
    grid-template-columns:repeat(3,1fr); 
    gap:3px;
  }
  .badge{
    height:65px; 
    padding:2px;
  }
  .badge img{
    max-width:61px; 
    max-height:61px;
  }
}
`;
    var st = document.createElement('style');
    st.id = 'tcGlassCSS';
    st.textContent = css;
    document.head.appendChild(st);
  },

  /* ---------- main ---------- */
  opencard: function(n, c){
    var self = this;
    if (typeof c === 'undefined' || c === false) c = 'trainercard';

    // создаём/находим новую модалку НЕ .mudol
    var modal = $('.tcModal');
    if (!modal.length){
      modal = $('<div/>', {class:'tcModal tcGlass', role:'dialog', 'aria-modal':'true'}).appendTo('body');
    }

    // отмена активного запроса
    if (this._activeRq && this._activeRq.readyState !== 4) {
      try { this._activeRq.abort(); } catch(_) {}
    }

    // применяем стили и показываем модалку
    this._ensureCSS();
    modal.show().empty();

    // скелетон
    $('<div/>', {
      class: 'trainercard-skeleton',
      html:
        '<div class="tc-skel-left"><div class="skel-ava"></div></div>'+
        '<div class="tc-skel-right"><div class="skel-line w1"></div><div class="skel-line w2"></div><div class="skel-line w3"></div></div>'
    }).appendTo(modal);

    var esc = this._esc.bind(this);

    // Закрытие мини-панели и модалок
    function closeMiniPanel(){ $('#miniPanel').fadeOut(120,function(){ $(this).remove(); }); }
    function closeModal(){
      modal.hide();
      closeMiniPanel();
      $('.design-modal').remove();
      $('#choosePokemonModal').remove();
      $('#giftInfoModal').remove();
      $('.seeAllModal').remove();
    }

    // ESC
    $(document).off('keydown.trenerESC').on('keydown.trenerESC', function(e){
      if (e.key === 'Escape') {
        if ($('#miniPanel').length) closeMiniPanel();
        else if ($('.design-modal:visible').length) $('.design-modal').fadeOut(120,function(){ $(this).remove(); });
        else if ($('#choosePokemonModal:visible').length) $('#choosePokemonModal').fadeOut(120,function(){ $(this).remove(); });
        else if ($('#giftInfoModal:visible').length) $('#giftInfoModal').fadeOut(120,function(){ $(this).remove(); });
        else if ($('.seeAllModal:visible').length) $('.seeAllModal').fadeOut(120,function(){ $(this).remove(); });
        else if (modal.is(':visible')) closeModal();
      }
    });

    // helpers
    function resolvePokemonBasenum(resp){
      return resp.trainerPokemonBasenum ||
             resp.trainer_pokemon_basenum ||
             resp.trainerPokemonBaseNum ||
             resp.trainer_pokemon_base_num || '';
    }
    function buildPokemonImgSrc(bn){ bn=String(bn||'').padStart(3,'0'); return '/img/pokemons/pokedex/'+bn+'.png'; }

    /* ===== модалка "View all" (универсальная) ===== */
    function openSeeAllModal(title, body, type){
      $('.seeAllModal').remove();
      var box = $('<div/>',{class:'seeAllModal'}).appendTo('body');
      var card = $('<div/>',{class:'seeAllCard'}).appendTo(box);
      $('<div/>',{class:'seeAllClose', html:'&times;'}).appendTo(card).on('click',function(){ box.fadeOut(120,function(){ box.remove(); }); });
      $('<div/>',{class:'seeAllTitle', text:title}).appendTo(card);
      var cont = $('<div/>',{class:(type==='badges'?'seeAllGridBadges':'seeAllList')}).appendTo(card);
      if (typeof body === 'string'){ cont.append(body); }
      else if (body && body.jquery){ cont.append(body); }
      else if (Array.isArray(body)){ body.forEach(function(el){ cont.append(el); }); }
      box.show();
    }

    function makePreviewHeader($host, title, count, onViewAll){
      var head = $('<div/>',{class:'previewHead'}).prependTo($host);
      $('<div/>',{class:'ttl', text:title}).appendTo(head);
      var btn = $('<div/>',{class:'viewAll', text:'Показать все →'}).appendTo(head);
      if (count<=5) btn.hide();
      btn.on('click', onViewAll);
    }

    // запрос данных
    this._activeRq = $.post('/do/trainers', { type: c, user: n }, function(response){
      modal.empty(); // убираем скелетон

      if (response && response.error){
        $('<div/>',{class:'trainercard-error', html:'<b>Ошибка:</b> '+esc(response.error)}).appendTo(modal);
        return;
      }

      /* ---------- Header + Close ---------- */
      $('<div/>',{class:'Header'}).appendTo(modal);
      $('<div/>',{class:'Close', html:'<i class="fas fa-times"></i>', click:function(){ closeModal(); }}).appendTo('.tcModal .Header');

      /* ---------- Content ---------- */
      $('<div/>',{class:'Content'}).appendTo(modal);
      var wrap = $('<div/>',{class:'tcGlassWrap trenerCard'}).appendTo('.tcModal .Content');
      var grid = $('<div/>',{class:'tcGlassGrid layout-row'}).appendTo(wrap);

      /* -------------------------------- LEFT -------------------------------- */
      var left = $('<div/>',{class:'tcLeft'}).appendTo(grid);
      $('<div/>',{class:'leftGlassBg'}).appendTo(left);
      var leftInner = $('<div/>',{class:'tcLeftInner'}).appendTo(left);

      // аватар (большой)
      var bigAvaPath = response.bigAva || 'img/avatars/big/0.png';
      if (bigAvaPath.startsWith('/')) bigAvaPath = bigAvaPath.slice(1);
      var $bigAva = $('<div/>',{
        class:'big-ava',
        id:'bigAva',
        css:{'background-image':'url(/'+esc(bigAvaPath)+')'}
      }).appendTo(leftInner);

      // клик по bigAva — панель действий
      if (Number(response.editStatus) === 1){
        $bigAva.off('click').on('click', function(){
          $('#miniPanel').remove();
          var skinText = (response.skinName || (response.model && response.skin ? (response.model+'/'+response.skin+(response.color?(' ('+response.color+')'):'') ) : null));
          var hatText  = response.hatName || null;
          var html =
            '<div id="miniPanel">'+
              '<div class="miniHeader" style="font-weight:900;font-size:16px;margin-bottom:12px;color:#1e293b;">Действия с аватаром</div>'+
              (skinText ? '<div style="margin-bottom:10px;color:#475569;">Скин: <b>'+esc(skinText)+'</b> <a href="#" id="removeSkin" style="margin-left:8px;color:#dc2626;">удалить</a></div>' : '<div style="margin-bottom:10px;color:#475569;">Скин не выбран</div>')+
              (hatText ?  '<div style="margin-bottom:10px;color:#475569;">Шапка: <b>'+esc(hatText)+'</b> <a href="#" id="removeHat" style="margin-left:8px;color:#dc2626;">удалить</a></div>' : '<div style="margin-bottom:10px;color:#475569;">Шапка не выбрана</div>')+
              '<div style="margin-top:12px;">'+
                '<button class="miniPanelBtn" id="refreshCard">Обновить</button> '+
                '<button class="miniPanelBtn" id="closePanel">Закрыть</button>'+
              '</div>'+
            '</div>';
          $('body').append(html);
          $('#closePanel').on('click', function(e){ e.preventDefault(); closeMiniPanel(); });
          $('#refreshCard').on('click', function(e){ e.preventDefault(); self.opencard(n, c); });
          $('#removeSkin').on('click', function(e){
            e.preventDefault();
            $.post('/do/itemsAction', {type:'remove_skin', user:n}, function(r){
              if (typeof showMessage==='function' && r && r.text) showMessage(r.text, r.error?'error':'success');
              closeMiniPanel(); self.opencard(n,c);
            },'json');
          });
          $('#removeHat').on('click', function(e){
            e.preventDefault();
            $.post('/do/itemsAction', {type:'remove_hat', user:n}, function(r){
              if (typeof showMessage==='function' && r && r.text) showMessage(r.text, r.error?'error':'success');
              closeMiniPanel(); self.opencard(n,c);
            },'json');
          });
        });
      }

      // эмблема клана + фракция (плашка 18px рядом отдельным элементом)
      if (response['clanUserCheck'] == 1 && response['clanUser']) {
        var wrap = $('<div/>', { class: 'clan-badges-wrap', css:{position:'absolute', bottom:'10px', left:'10px', display:'flex', alignItems:'center', gap:'8px', zIndex:11} }).appendTo(left);

        $('<div/>',{
          class:'ClanBadge',
          css:{'background-image':'url(/img/world/clans/emblems/'+esc(response['clanUser'])+'.png)', position:'relative', bottom:'auto', left:'auto'},
          click:function(){ if (typeof openClanCard==='function') openClanCard(response['clanUser']); }
        }).appendTo(wrap);

        // Ожидаем либо объект clanFaction {code/abbr,name,badge_color/color}, либо плоские поля
        var f = response['clanFaction'] || null;
        var fCode = '';
        var fName = '';
        var fColor = '';

        if (f && typeof f === 'object') {
          fCode = String(f.code || f.abbr || f.short || '');
          fName = String(f.name || '');
          fColor = String(f.badge_color || f.color || '');
        } else {
          fCode = String(response['clanFactionCode'] || response['clanFactionAbbr'] || '');
          fName = String(response['clanFactionName'] || '');
          fColor = String(response['clanFactionColor'] || '');
        }

        if (fCode || fName) {
          if (!fColor) fColor = '#9CA3AF';
          $('<div/>',{
            class:'ClanFactionBadge',
            title: fName ? ('Фракция: ' + fName) : 'Фракция',
            css: {'--faction-color': fColor, height:'18px', display:'inline-flex', alignItems:'center', gap:'6px', padding:'0 8px 0 7px', borderRadius:'9px', background:'#F3F4F6', color:'#111827', border:'1px solid rgba(0,0,0,0.08)', boxShadow:'0 4px 10px rgba(0,0,0,0.18)', fontSize:'11px', lineHeight:'18px', fontWeight:800, letterSpacing:'0.2px', cursor:'pointer', userSelect:'none', whiteSpace:'nowrap'},
            html: '<span class="f-dot" style="width:9px;height:9px;border-radius:3px;background:var(--faction-color);box-shadow:inset 0 0 0 1px rgba(0,0,0,0.12);"></span><span class="f-text" style="transform:translateY(-0.5px);">' + esc(fCode || fName) + '</span>',
            click: function(e){
              e.stopPropagation();
              if (typeof openClanFactions === 'function') openClanFactions(response['clanUser']);
            }
          }).appendTo(wrap);
        }
      }
// кнопка выбора покемона (только владелец)
      if (Number(response.editStatus) === 1) {
        $('<div/>',{class:'cornerPaw', html:'<i class="fas fa-paw"></i>', title:'Поставить покемона рядом'}).appendTo(left)
          .on('click', function(e){
            e.stopPropagation();
            var btn=$(this); btn.addClass('loading');
            $.post('/do/PokemonTeam',{type:'load'},function(list){
              btn.removeClass('loading');

              var modalChoose=$('#choosePokemonModal');
              if(!modalChoose.length){
                modalChoose=$('<div/>',{id:'choosePokemonModal',class:'pokemon-modal','aria-modal':'true',role:'dialog'}).appendTo('body');
              }
              modalChoose.empty().show().html(
                '<div class="pokemon-modal-inner">'+
                  '<span class="modal-close" role="button" aria-label="Закрыть">&times;</span>'+
                  '<div class="pokemon-modal-header">'+
                    '<div class="pm-title"><i class="fas fa-paw"></i> Выберите покемона</div>'+
                    '<div class="pm-sub">Будет отображаться рядом с вашим аватаром</div>'+
                  '</div>'+
                  '<div id="pokemonChoiceList" class="pokemon-choice-list"></div>'+
                '</div>'
              );
              $('.modal-close', modalChoose).on('click',function(){ modalChoose.hide(); });

              var $list=$('#pokemonChoiceList');
              var pokemons = list.pokemons ? Object.values(list.pokemons) : Object.values(list);
              pokemons.sort(function(a,b){ return (parseInt(a.basenum,10)||0)-(parseInt(b.basenum,10)||0); });

              pokemons.forEach(function(pok){
                var bn=esc(pok.basenum); var imgPath='/img/pokemons/pokedex/'+bn+'.png';
                var title=pok.html ? pok.html.replace(/<[^>]+>/g,'') : ('#'+bn);
                $('<div/>',{
                  class:'pokemon-choice',
                  html:'<div class="pm-img-wrap"><img loading="lazy" src="'+imgPath+'" onerror="this.src=\'/img/pokemons/pokedex/0.png\'"><div class="pm-sel-check"><i class="fas fa-check"></i></div></div><div class="pm-num">#'+bn+'</div>',
                  title:title
                }).on('click', function(){
                  var card=$(this); if(card.hasClass('is-loading')) return;
                  $('.pokemon-choice.selected').removeClass('selected'); card.addClass('selected is-loading');
                  $.post('/do/PokemonTeam',{type:'set_trainer_pokemon',pokemon_id:pok.id},function(resp){
                    if(resp && resp.code==='premium_required'){
                      showSimplePremiumNotice(resp.text); card.removeClass('is-loading selected'); return;
                    }
                    if(resp && resp.success){
                      setTimeout(function(){ modalChoose.hide(); self.opencard(n, c); },220);
                    } else {
                      showInlineError(resp && resp.text ? resp.text : 'Ошибка сохранения.'); card.removeClass('is-loading selected');
                    }
                  },'json').fail(function(){ showInlineError('Ошибка связи с сервером.'); card.removeClass('is-loading selected'); });
                }).appendTo($list);
              });

            },'json').fail(function(){
              btn.removeClass('loading'); if (typeof showMessage==='function') showMessage('Ошибка загрузки списка покемонов','error');
            });
          });
      }

      // «покемон рядом»
      var trainerPokemonBasenum = resolvePokemonBasenum(response);
      if (trainerPokemonBasenum && trainerPokemonBasenum !== '000') {
        var pokemonImg = $('<img/>',{
          class:'trainercard-pokemon-img',
          src: buildPokemonImgSrc(trainerPokemonBasenum),
          alt:'Покемон',
          'data-basenum': trainerPokemonBasenum
        }).on('error', function(){ this.src='/img/pokemons/pokedex/000.png'; }).appendTo(left);

        var trainerTera = response.trainerPokemonTeraType || response.trainer_pokemon_tera_type || '';
        if (trainerTera !== '' && /^[0-9]+$/.test(String(trainerTera))) {
          var teraMap = {
            1:'normal',2:'fire',3:'water',4:'electric',5:'grass',6:'ice',7:'fighting',8:'poison',9:'ground',10:'flying',
            11:'psychic',12:'bug',13:'rock',14:'ghost',15:'dragon',16:'dark',17:'steel',18:'fairy',19:'stellar'
          };
          trainerTera = teraMap[parseInt(trainerTera, 10)] || '';
        }
        if (trainerTera) {
          $('<div/>', {
            class: 'trainercard-tera-badge',
            title: 'Тератип: ' + esc(trainerTera),
            html: '<img src="/img/world/typs/' + esc(trainerTera) + '.png" alt="' + esc(trainerTera) + '"><span>' + esc(trainerTera) + '</span>'
          }).appendTo(left);
        }

        if (Number(response.editStatus) === 1) {
          pokemonImg.on('contextmenu', function(e){
            e.preventDefault();
            if (!confirm('Убрать покемона рядом с тренером?')) return;
            $.post('/do/PokemonTeam', { type: 'clear_trainer_pokemon' }, function(){ self.opencard(n, c); });
          });
          var pressTimer;
          pokemonImg.on('touchstart', function(){
            pressTimer=setTimeout(function(){
              if (confirm('Убрать покемона рядом с тренером?')) {
                $.post('/do/PokemonTeam', { type:'clear_trainer_pokemon' }, function(){ self.opencard(n, c); });
              }
            },800);
          }).on('touchend touchmove', function(){ clearTimeout(pressTimer); });
        }
      }

      /* -------------------------------- RIGHT -------------------------------- */
      var right = $('<div/>',{class:'tcRight info-side'}).appendTo(grid);
      $('<div/>',{class:'tcRightBg'}).appendTo(right);
      $('<div/>',{class:'tcRightVeil'}).appendTo(right);
      var ri = $('<div/>',{class:'tcRightInner'}).appendTo(right);

      var infoCard = $('<div/>',{class:'tcInfo'}).appendTo(ri);

      // Кнопка оформления — ПРАВЫЙ ВЕРХ (только владелец)
      if (Number(response.editStatus) === 1){
        $('<button/>',{class:'designTopBtn', text:'🎨 Оформление'}).appendTo(infoCard).on('click', function(){ openDesignModal(); });
      }

     // Верх: имя + онлайн
var nameRow = $('<div/>',{class:'nameRow'}).appendTo(infoCard);
$('<div/>',{text:(response.login||'—'), style:'font-size:28px;font-weight:900;color:#1e293b;'}).appendTo(nameRow);

// Определяем статус онлайн на основе данных ответа
var isOnline = response.classOnl === 'onl';
var statusTitle = response.textOnl || (isOnline ? 'В сети' : 'Не в сети');

$('<div/>',{
    class: 'dot ' + (isOnline ? 'online' : 'offline'), 
    title: statusTitle
}).appendTo(nameRow);

      // Подзаголовок (мягкий чип группы)
      var subRow = $('<div/>',{class:'subRow'}).appendTo(infoCard);
      $('<div/>',{text:(response.inGame ? ('В игре • '+response.inGame) : ' ')}).appendTo(subRow);
      if (response.clanUser) $('<div/>',{class:'pill', html:'Clan: '+esc(response.clanUser)}).appendTo(subRow);
      
      // Исправлено: выводим user_group как текст
var groupText = response.userGroupText || '';
if (!groupText && response.user_group) {
    // Если user_group - это число, преобразуем его в текстовое значение
    var userGroupNum = parseInt(response.user_group, 10);
    var groupNames = {
        1: 'Администрация',
        2: 'Полицейский', 
        3: 'Модератор',
        4: 'Наставник',
        5: 'Лидер стадиона',
        6: 'Игрок',
        8: 'Заключенный',
        9: 'Художник',
        100: 'Куратор турниров'
    };
    groupText = groupNames[userGroupNum] || 'Игрок';
}

if (groupText) $('<div/>',{class:'pill soft', text: String(groupText)}).appendTo(subRow);
$('<div/>',{class:'pill green', text: (response.readyText || 'Готов')}).appendTo(subRow);

      // Три белых блока: Level / Rank / Hours
      var ltRow = $('<div/>',{class:'ltRow'}).appendTo(infoCard);
      $('<div/>',{class:'lt', html:'<div class="lbl">УРОВЕНЬ</div><div class="val">'+esc(response.lvluser||'1')+'</div>'}).appendTo(ltRow);
      $('<div/>',{
    class:'lt', 
    html:'<div class="lbl">РАНГ</div><div class="val">'+esc(response.rang || 'Новичок')+'</div>'
}).appendTo(ltRow);
      $('<div/>',{class:'lt', html:'<div class="lbl">ЧАСОВ В ИГРЕ</div><div class="val">'+esc(response.hours||0)+'</div>'}).appendTo(ltRow);

      // Опыт
      var xpBlock = $('<div/>',{class:'xpBlock'}).appendTo(infoCard);
      $('<div/>',{class:'cap', text:'Опыт'}).appendTo(xpBlock);
      var xpRow = $('<div/>',{class:'xpRow'}).appendTo(xpBlock);
      $('<div/>',{class:'xpBar', html:'<div style="width:'+Number(response.widthlvluser||0)+'%"></div>'}).appendTo(xpRow);
      $('<div/>',{text:esc(response.explvl||0)+' / '+esc(response.explvl_to||0), style:'color:#64748b;font-weight:800'}).appendTo(xpRow);

      // Сводка 4 карточки
      var sum = $('<div/>',{class:'sum4'}).appendTo(infoCard);
      $('<div/>',{class:'s', html:'<div class="t">Покедекс</div><div class="v">'+esc(response.dex||0)+'</div>'}).appendTo(sum);
      $('<div/>',{class:'s', html:'<div class="t">Побед </div><div class="v">'+esc(response.wins_percent||0)+'%</div>'}).appendTo(sum);
      $('<div/>',{class:'s', html:'<div class="t">Категория</div><div class="v">'+esc(response.ratingCategory||'—')+'</div>'}).appendTo(sum);
      $('<div/>',{class:'s', html:'<div class="t">Друзей</div><div class="v">'+esc(response.friends||0)+'</div>'}).appendTo(sum);

      // Табы
      $('<div/>',{class:'Tabses'}).appendTo(infoCard);
      var tabsHTML =
          '<div id="trophyTab" class="active" onclick=setTrenerBlock("trophyBlock",this);>Награды</div>'+
          '<div id="friendsTab" onclick=setTrenerBlock("friendsBlock",this);>Друзья</div>'+
          '<div id="wishTab" onclick=setTrenerBlock("wishBlock",this);>Желания</div>'+
          '<div id="achivTab" onclick=setTrenerBlock("achivBlock",this);>Достижения</div>'+
          '<div id="giftTab" onclick=setTrenerBlock("giftBlock",this);>Подарки</div>';
      $('<div/>',{class:'Tabs', html:tabsHTML}).appendTo('.Tabses');

      // Контейнеры секций
      ['trophyBlock','friendsBlock','wishBlock','achivBlock','giftBlock'].forEach(function(id){
        $('<div/>',{class:'Table',id:id,style:(id==='trophyBlock'?'':'display:none')}).appendTo('.Tabses');
      });

      /* ===== Badges — первые 5 + View all ===== */
      var trophies = response.trophyList || response.trophylist || [];
      var troArr = Array.isArray(trophies) ? trophies : Object.values(trophies);
      var badgesBox = $('<div/>',{class:'previewBox'}).appendTo('#trophyBlock');
      makePreviewHeader(badgesBox, '', troArr.length, function(){
        var full = $('<div/>');
        troArr.forEach(function(y){
          var $b = $('<div/>',{class:'badge', title:(y.title||'')});
          $('<img/>',{src:'/img/world/items/little/'+esc(y.trophy)+'.png', alt:esc(y.title||'badge')}).appendTo($b);
          $b.on('click', function(){ if (typeof issetAll==='function') issetAll(y.info,'item'); });
          full.append($b);
        });
        openSeeAllModal('Все награды', full, 'badges');
      });
      var gridBadges = $('<div/>',{class:'badgesGrid'}).appendTo(badgesBox);
      if (troArr.length){
        troArr.slice(0,5).forEach(function(y){
          var $b = $('<div/>',{class:'badge', title:(y.title||'')}).appendTo(gridBadges);
          $('<img/>',{src:'/img/world/items/little/'+esc(y.trophy)+'.png', alt:esc(y.title||'badge')}).appendTo($b);
          $b.on('click', function(){ if (typeof issetAll==='function') issetAll(y.info,'item'); });
        });
      } else {
        $('<div/>',{text:'Нет значков', style:'color:#64748b;text-align:center;padding:20px;'}).appendTo(gridBadges);
      }

      /* ===== Friends — первые 5 + View all ===== */
      var friendsHTML = response.friendsList || response.pokedexList || '';
      var friendsBox = $('<div/>',{class:'previewBox'}).appendTo('#friendsBlock');
      var friendsTmp = $('<div/>').html(friendsHTML);
      var friendsChildren = friendsTmp.children();
      makePreviewHeader(friendsBox, '', friendsChildren.length, function(){
        var all = friendsChildren.clone();
        var holder = $('<div/>',{class:'seeAllList'}); holder.append(all);
        openSeeAllModal('Friends', holder, 'list');
      });
      var friendsPrev = $('<div/>',{class:'Friends'}).appendTo(friendsBox);
      if (friendsChildren.length){
        friendsChildren.slice(0,5).each(function(){ friendsPrev.append($(this)); });
      } else {
        friendsPrev.append('<div class="empty" style="color:#64748b;text-align:center;padding:20px;">Нет друзей</div>');
      }

            /* ===== Wishes — первые 5 + View all ===== */
      var wishHTML = response.wishList || '';
      var wishBox = $('<div/>',{class:'previewBox'}).appendTo('#wishBlock');
      var wishTmp = $('<div/>').html(wishHTML);
      var wishChildren = wishTmp.children();
      makePreviewHeader(wishBox, '', wishChildren.length, function(){
        var all = wishChildren.clone();
        var holder = $('<div/>',{class:'seeAllList'}); holder.append(all);
        openSeeAllModal('Wishes', holder, 'list');
      });
      var wishPrev = $('<div/>',{class:'Wish'}).appendTo(wishBox);
      if (wishChildren.length){
        wishChildren.slice(0,5).each(function(){ wishPrev.append($(this)); });
      } else {
        wishPrev.append('<div class="empty" style="color:#64748b;text-align:center;padding:20px;">Нет желаний</div>');
      }

      /* ===== Achievements — первые 5 + View all ===== */
      var achHTML = response.achivmentsList || '';
      var achBox = $('<div/>',{class:'previewBox'}).appendTo('#achivBlock');
      var achTmp = $('<div/>').html(achHTML);
      var achChildren = achTmp.children();
      makePreviewHeader(achBox, '', achChildren.length, function(){
        var all = achChildren.clone();
        var holder = $('<div/>',{class:'seeAllList'}); holder.append(all);
        openSeeAllModal('Achievements', holder, 'list');
      });
      var achPrev = $('<div/>',{class:'Achivs'}).appendTo(achBox);
      if (achChildren.length){
        achChildren.slice(0,5).each(function(){ achPrev.append($(this)); }); 
      } else {
        achPrev.append('<div class="empty" style="color:#64748b;text-align:center;padding:20px;">Нет достижений</div>');
      }

      /* ===== Gifts — первые 5 + View all ===== */
      if (typeof window.makeGiftCards!=='function'){
        window.makeGiftCards=function(list){
          if(!Array.isArray(list)||!list.length) return '<div class="gift-empty" style="color:#64748b;text-align:center;padding:20px;">Нет полученных подарков</div>';
          return list.map(function(g){
            return '<div class="gift-card" data-gift-id="'+esc(g.id)+'">'+
              '<div class="gift-card-thumb"><img class="gift-img" src="'+esc(g.img||'/images/giftshop/default.png')+'" alt="'+esc(g.title||'Подарок')+'"></div>'+
              '<div>'+
                '<div class="gift-title">'+esc(g.title||'Подарок')+'</div>'+
                '<div class="gift-from">От: '+esc(g.from_user_login||'—')+'</div>'+
                (g.date?'<div class="gift-date">'+esc(g.date)+'</div>':'')+
              '</div>'+
            '</div>';
          }).join('');
        };
      }
      var giftsList = response.receivedGifts||[];
      var giftBox = $('<div/>',{class:'previewBox'}).appendTo('#giftBlock');
      makePreviewHeader(giftBox, '', giftsList.length, function(){
        var fullHtml = $(makeGiftCards(giftsList));
        var holder = $('<div/>',{class:'seeAllList'}); holder.append(fullHtml);
        openSeeAllModal('Gifts', holder, 'list');
      });
      var giftPrev = $('<div/>',{class:'Gift'}).appendTo(giftBox);
      if (giftsList.length){
        giftPrev.append(makeGiftCards(giftsList.slice(0,5)));
      } else {
        giftPrev.append('<div class="gift-empty" style="color:#64748b;text-align:center;padding:20px;">Нет полученных подарков</div>');
      }

      /* ===== Gift modals infra ===== */
      var giftsMap = response.receivedGiftsMap || {};
      ensureGiftModalInfra();

      $(document).off('click.openGift').on('click.openGift','.Gift .gift-card',function(e){
        e.preventDefault(); e.stopPropagation();
        var rawId=$(this).data('gift-id');
        if(!rawId){ giftShowError('Нет ID подарка'); return; }
        if(!/^[0-9]+$/.test(String(rawId))){ giftShowError('Некорректный ID подарка'); return; }
        openGiftInfo(rawId);
      });

      function openGiftInfo(id){
        id=parseInt(id,10);
        if(giftsMap[id]){ openGiftInfoLocal(giftsMap[id]); return; }
        $.getJSON('/do/trainers.php',{action:'giftinfo',id:id})
          .done(function(info){
            if(!info||info.error){ giftShowError(info&&info.error?info.error:'Нет данных о подарке'); return;}
            openGiftInfoLocal(info);
          })
          .fail(function(_,text){ giftShowError('Ошибка связи: '+text); });
      }
      function buildGiftInfoModalHTML(info){
        function esc2(v){ if(v===undefined||v===null) return ''; return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
        var title=esc2((info.title||info.name||'Подарок').trim());
        var rawMsg=(info.message||'').trim();
        var msgHtml=esc2(rawMsg).replace(/\n/g,'<br>');
        var img=esc2(info.img||'/images/giftshop/default.png');
        var from=esc2(info.from_user_login||'Неизвестно');
        var priceShow='';
        if(info.price!==undefined && info.price!==null && String(info.price).trim()!==''){
          var val=parseFloat(String(info.price).replace(',','.')); if(!isNaN(val)&&val>0) priceShow=esc2(String(val));
        }
        var date=info.date?esc2(info.date):'';
        var userIcon='<svg class="meta-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.76 0 5-2.69 5-6s-2.24-6-5-6-5 2.69-5 6 2.24 6 5 6zm0 2c-3.87 0-7 2.91-7 6.5V22h14v-1.5C19 16.91 15.87 14 12 14z"/></svg>';
        var emeraldIcon='<svg class="meta-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M9.17 3h5.66L21 8.5 12 21 3 8.5 9.17 3zm.83 2L7 8.5 12 17l5-8.5L14 5H10z"/></svg>';
        return (
          '<div id="giftInfoModal" class="gift-info-modal" role="dialog" aria-modal="true">'+
            '<div class="gift-info-content">'+
              '<button type="button" class="gift-info-close" aria-label="Закрыть">×</button>'+
              '<div class="gift-info-body">'+
                '<div class="gift-info-grid">'+
                  '<div class="gift-info-image-wrap">'+
                    '<img src="'+img+'" alt="'+title+'" class="gift-info-img" onerror="this.onerror=null;this.src=\''+(typeof GIFT_FALLBACK_IMG!=='undefined'?GIFT_FALLBACK_IMG:'/images/giftshop/default.png')+'\';">'+
                  '</div>'+
                  '<div class="gift-info-main">'+
                    '<div class="gift-title">'+
                      '<div class="gift-title-label">Подарок</div>'+
                      '<div class="gift-title-name">'+title+'</div>'+
                    '</div>'+
                    (rawMsg?'<div class="gift-msg"><div class="gift-msg-label">Сообщение</div><div class="gift-msg-text">'+msgHtml+'</div></div>':'')+
                    '<div class="gift-meta">'+
                      '<div class="gift-meta-item">'+userIcon+'<span>'+from+'</span></div>'+
                      (priceShow?'<div class="gift-meta-item emerald" title="Цена">'+emeraldIcon+'<span>'+priceShow+'</span></div>':'')+
                    '</div>'+
                    (date?'<div class="gift-info-date">'+date+'</div>':'')+
                  '</div>'+
                '</div>'+
              '</div>'+
            '</div>'+
          '</div>'
        );
      }
      function openGiftInfoLocal(info){
        var m = buildGiftInfoModalHTML(info);
        $('body').append(m);
        $('#giftInfoModal .gift-info-close, #giftInfoModal').on('click', function(ev){
          if($(ev.target).is('.gift-info-close, #giftInfoModal')) $('#giftInfoModal').fadeOut(140,function(){ $(this).remove(); });
        });
        $(document).off('keydown.giftModal').on('keydown.giftModal',function(ev){
          if(ev.key==='Escape'){ $('#giftInfoModal').fadeOut(140,function(){ $(this).remove(); }); $(document).off('keydown.giftModal'); }
        });
      }
      function giftShowError(text){
        var html =
          '<div id="giftInfoModal" class="gift-info-modal" role="dialog" aria-modal="true">'+
            '<div class="gift-info-content">'+
              '<button type="button" class="gift-info-close" aria-label="Закрыть">×</button>'+
              '<div class="gift-info-body error">'+
                '<div class="gift-info-error">'+esc(text)+'</div>'+
                '<div class="gift-info-actions"><button type="button" class="gift-info-close-btn">Закрыть</button></div>'+
              '</div>'+
            '</div>'+
          '</div>';
        $('body').append(html);
        $('#giftInfoModal .gift-info-close, #giftInfoModal .gift-info-close-btn').on('click',function(){ $('#giftInfoModal').fadeOut(120,function(){ $(this).remove(); }); });
      }
      function ensureGiftModalInfra(){
        var css = `
          :root{--gift-overlay:rgba(15,23,42,.45);--gift-bg:linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.98));--gift-border:rgba(255,255,255,0.4);--gift-shadow:0 25px 50px -12px rgba(15,23,42,.25),inset 0 1px 0 rgba(255,255,255,0.6);--gift-text:#1e293b;--gift-muted:#64748b}
          .gift-info-modal{position:fixed;inset:0;z-index:14000;display:flex;align-items:center;justify-content:center;background:var(--gift-overlay);backdrop-filter:blur(8px);animation:giftFadeIn .2s ease}
          @keyframes giftFadeIn{from{opacity:0}to{opacity:1}}
          .gift-info-content{width:clamp(360px,54vw,580px);max-width:96%;background:var(--gift-bg);border:1px solid var(--gift-border);border-radius:20px;box-shadow:var(--gift-shadow);position:relative;padding:16px;animation:giftScaleIn .22s ease;color:var(--gift-text);backdrop-filter:blur(20px)}
          @keyframes giftScaleIn{from{transform:scale(.96);opacity:.8}to{transform:scale(1);opacity:1}}
          .gift-info-close{position:absolute;top:10px;right:10px;appearance:none;background:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.4);color:#64748b;font-size:18px;line-height:1;padding:8px;border-radius:12px;cursor:pointer;transition:.2s;backdrop-filter:blur(8px)}
          .gift-info-close:hover{color:#1e293b;background:rgba(255,255,255,0.8);transform:scale(1.05)}
          .gift-info-grid{display:grid;grid-template-columns:140px 1fr;gap:14px;align-items:start}
          @media(max-width:520px){.gift-info-grid{grid-template-columns:1fr;gap:12px}}
          .gift-info-image-wrap{display:flex;align-items:center;justify-content:center}
          .gift-info-img{width:136px;height:136px;object-fit:contain;display:block;background:linear-gradient(135deg,rgba(248,250,252,0.9),rgba(241,245,249,0.95));border:1px solid rgba(203,213,225,0.4);border-radius:18px;padding:14px;box-shadow:0 4px 12px rgba(15,23,42,0.06)}
          .gift-title{display:flex;flex-direction:column;gap:6px;padding:10px 12px;border:1px solid rgba(203,213,225,0.4);border-radius:14px;background:linear-gradient(135deg,rgba(255,255,255,0.9),rgba(248,250,252,0.95));box-shadow:0 4px 16px rgba(15,23,42,.08);margin-bottom:10px;backdrop-filter:blur(8px)}
          .gift-title-label{font-size:11px;letter-spacing:.3px;text-transform:uppercase;color:#64748b;font-weight:800}
          .gift-title-name{font-size:18px;line-height:1.4;font-weight:800;overflow-wrap:anywhere;color:#1e293b}
          .gift-msg{border:1px solid rgba(203,213,225,0.4);border-radius:14px;padding:10px 12px;background:linear-gradient(135deg,rgba(255,255,255,0.8),rgba(248,250,252,0.9));box-shadow:0 3px 12px rgba(15,23,42,.06);margin-bottom:10px;backdrop-filter:blur(8px)}
          .gift-msg-label{font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;margin-bottom:6px}
          .gift-msg-text{font-size:16px;line-height:1.4;font-weight:700;color:#1e293b;overflow-wrap:anywhere}
          .gift-meta{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:4px 0}
          .gift-meta-item{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:rgba(248,250,252,0.8);color:#1e293b;border:1px solid rgba(203,213,225,0.4);font-size:13px;font-weight:700;backdrop-filter:blur(8px)}
          .gift-info-date{margin-top:6px;font-size:12.5px;color:#64748b;font-weight:600}
          .gift-info-body.error{text-align:center;padding:18px 8px 10px}
          .gift-info-error{font-size:14px;font-weight:800;color:#dc2626;margin-bottom:12px}
          .gift-info-actions button,.gift-info-close-btn{appearance:none;border:1px solid rgba(99,102,241,0.3);padding:10px 16px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:800;font-size:13px;cursor:pointer;transition:all 0.2s ease}
          .gift-info-actions button:hover,.gift-info-close-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(99,102,241,0.3)}
        `;
        var ex=document.getElementById('giftModalBaseCSS');
        if(ex){ ex.textContent=css; } else { var st=document.createElement('style'); st.id='giftModalBaseCSS'; st.textContent=css; document.head.appendChild(st); }
      }

      // Покеболы (если есть)
      $('<div/>',{class:'Pokeball'}).appendTo(wrap);
      $.each(response['ballList'] || [], function(_, y){
        $('<'+esc(y['styleball']||'div')+'/>',{style:'background-image:url('+esc(y['typeball']+''+y['ball'])+'.png);'}).appendTo('.Pokeball');
      });

      // Переключатель табов
      if (typeof window.setTrenerBlock !== 'function') {
        window.setTrenerBlock = function(id, el){
          var $host = $(el).closest('.tcInfo');
          $(el).addClass('active').siblings().removeClass('active');
          $host.find('.Table').hide();
          $('#'+id).show();
        };
      }

      /* ===== ОФОРМЛЕНИЕ (только владелец тренеркарты) ===== */
      var uid = parseInt(response.id || 0, 10) || 0;
      var prefs = self._loadPrefs(uid);
      var startTheme = prefs.theme || 'default';
      if(!self._themes[startTheme]) startTheme = 'default'; // нормализация
      var startArt   = prefs.art   || (response.rightBg || '');

      self._applyTheme(wrap, startTheme, startArt);

      function openDesignModal(){
        if (Number(response.editStatus)!==1) return; // доступ только владельцу
        var themeObj = self._themes[startTheme] || self._themes.default;
        var d = $('.design-modal');
        if (!d.length) d = $('<div/>',{class:'design-modal',role:'dialog','aria-modal':'true'}).appendTo('body');
        d.empty().show().html(
          '<div class="design-modal-inner">'+
            '<span class="design-close" aria-label="Закрыть">&times;</span>'+
            '<div class="design-grid">'+
              '<div class="design-preview">'+
                '<div class="pv-header">Предпросмотр</div>'+
                '<div class="pv-body">'+
                  '<div class="pv-art" style="background-image:'+(startArt?('url(\''+esc(startArt)+'\')'):'none')+'"></div>'+
                  '<div class="pv-veil" style="background:'+themeObj.veil+'"></div>'+
                '</div>'+
              '</div>'+
              '<div class="design-controls">'+
                '<div style="color:#e2e8f0;font-weight:900;margin-bottom:8px;"><b>Тема</b></div>'+
                '<div class="theme-list" id="themeList"></div>'+
                '<div class="design-row"><input id="artUrl" class="design-input" placeholder="URL фона (арт)" value="'+esc(startArt)+'"><button class="design-btn" id="applyArt">Применить арт</button></div>'+
                '<div class="design-row"><button class="design-btn" id="resetDesign">Сбросить</button><div></div></div>'+
              '</div>'+
            '</div>'+
          '</div>'
        );
        $('.design-close', d).on('click', function(){ d.fadeOut(120,function(){ d.remove(); }); });

        var $tl = $('#themeList');
        Object.keys(self._themes).forEach(function(k){
          var t = self._themes[k];
          var card = $('<div/>',{
            class:'theme-card',
            html:'<div style="color:#e2e8f0;font-weight:800;"><b>'+esc(t.name)+'</b></div><div class="theme-swatch" style="background:'+t.grad+'"></div>'
          }).on('click', function(){
            startTheme = k;
            var tNow = self._themes[startTheme] || self._themes.default;
            $('.pv-veil').css('background', tNow.veil);
            self._applyTheme(wrap, startTheme, $('#artUrl').val());
            prefs.theme = startTheme;
            self._savePrefs(uid, prefs);
            // подсветка выбранной
            $tl.find('.theme-card').css('box-shadow','none');
            $(this).css('box-shadow','0 0 0 2px rgba(255,255,255,.35) inset');
          });
          if (k===startTheme) card.css('box-shadow','0 0 0 2px rgba(255,255,255,.35) inset');
          $tl.append(card);
        });

        $('#applyArt').on('click', function(){
          var v = String($('#artUrl').val() || '').trim();
          if (!v){ $('#artUrl').focus(); return; }
          $('.pv-art').css('background-image', 'url("'+v.replace(/"/g,'\\"')+'")');
          self._applyTheme(wrap, startTheme, v);
          prefs.art = v; self._savePrefs(uid, prefs);
        });

        $('#resetDesign').on('click', function(){
          startTheme = 'default';
          startArt = (response.rightBg || '');
          $('#artUrl').val(startArt);
          var tDef = self._themes[startTheme] || self._themes.default;
          $('.pv-veil').css('background', tDef.veil);
          $('.pv-art').css('background-image', startArt ? 'url("'+startArt.replace(/"/g,'\\"')+'")' : 'none');
          self._applyTheme(wrap, startTheme, startArt);
          prefs = {}; self._savePrefs(uid, prefs);
          $tl.find('.theme-card').css('box-shadow','none');
          $tl.find('.theme-card').first().css('box-shadow','0 0 0 2px rgba(255,255,255,.35) inset');
        });
      }

      /* --- локальные уведомления для выбора покемона (если нет глобальных) --- */
      function showSimplePremiumNotice(text){
        var $host = $('#choosePokemonModal .pokemon-modal-inner');
        if(!$host.length){ if(typeof showMessage==='function') showMessage(text||'Нужен премиум.','error'); return; }
        $('#pokemonChoiceList, .pokemon-modal-header').hide();
        $('#premiumTrainerNotice').remove();
        var html =
          '<div id="premiumTrainerNotice" style="display:flex;gap:16px;padding:18px;border:1px solid rgba(255,255,255,.18);border-radius:18px;background:rgba(255,255,255,.08);backdrop-filter:blur(8px);">'+
            '<div style="flex:0 0 56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#6366f1,#22c55e);display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px">🔒</div>'+
            '<div style="flex:1">'+
              '<div style="font-weight:900;font-size:16px;margin-bottom:6px;color:#f1f5f9;">Нужен премиум</div>'+
              '<div style="font-size:14px;opacity:.9;margin-bottom:12px;color:#cbd5e1;">'+(text||'Для размещения покемона рядом с аватаром требуется активный премиум (#448).')+'</div>'+
              '<button type="button" id="tpSpBackBtn" class="design-btn" style="border:1px solid rgba(255,255,255,.2)">Назад</button>'+
            '</div>'+
          '</div>';
        $host.prepend(html);
        $('#tpSpBackBtn').on('click', function(){
          $('#premiumTrainerNotice').remove();
          $('#pokemonChoiceList, .pokemon-modal-header').fadeIn(160);
        });
      }
      function showInlineError(msg){
        var $ex = $('#pokemonChoiceInlineError');
        if (!$ex.length) $ex = $('<div id="pokemonChoiceInlineError" class="pm-inline-error"></div>').prependTo('.pokemon-modal-inner');
        $ex.html('<i class="fas fa-exclamation-triangle"></i> '+esc(msg));
        setTimeout(function(){ $ex.fadeOut(200,function(){ $(this).remove(); }); }, 3200);
      }

    }, 'json').fail(function(xhr,status,error){
      $('.tcModal').empty().append($('<div/>',{class:'trainercard-error',html:'Ошибка загрузки: '+(status||error)}));
    });
  }
},
clan: {
  createClan: function() {
      // Удаляем старые модальные окна
      $('.model').remove();
      Game.loaders.world();
      Game.modals.modelLoad('Создание клана');
      $('.Modal').remove();

      // Информационный блок
      $('<div />', {
          'class': 'txtCM',
          html: `
              <div class="clan-create-desc">
                  <span class="clan-create-price">
                      <b>Стоимость создания клана: 1.500.000 Генкаров</b>
                  </span>
                  <br>
                  <span class="clan-create-info">
                      
                  </span>
              </div>
          `
      }).appendTo('.content-model');

      // Форма: название + загрузка эмблемы
      $('<div />', {
          'class': 'createClan',
          html: `
              <input type='text' class='createClanInput' id='clanCreateName' maxlength='22' placeholder='Название клана (до 22 символов)'><br>
              <div class="clan-emblem-upload-title">Загрузите эмблему (100x100, PNG/JPG, до 10 МБ):</div>
              <input type="file" id="clanEmblemUpload" accept="image/png,image/jpeg" style="margin-bottom: 8px;">
              <div class="clan-emblem-preview-block" style="display:none;margin-bottom:10px;">
                  <img id="clanEmblemPreview" src="" alt="Превью эмблемы" style="max-width:100px;max-height:100px;border-radius:10px;border:2px solid #eaf0fa;box-shadow:0 1px 8px #197acf20;">
              </div>
              <input type='hidden' id='clanCreateEmblem' value=''>
          `
      }).appendTo('.content-model');

      // Кнопка отправки
      $('<div />', {
          'class': 'createClanBtn',
          'tabindex': 0,
          'role': 'button',
          'click': function() {
              btnCreateClan();
          },
          html: 'Создать клан'
      }).appendTo('.createClan');

      // Стили с адаптивностью для мобильных
      $('<style/>', {
          html: `
.clan-create-desc {
    text-align: left;
    font-size: 1.08rem;
    color: #2d4169;
    margin-bottom: 12px;
    border-radius: 8px;
    background: #fafdff;
    padding: 10px 12px;
    box-shadow: 0 1px 7px #b6eafc22;
}
.clan-create-price {
    color: #1da46e;
    font-size: 1.15rem;
}
.clan-create-info {
    color: #5873a1;
    font-size: 1rem;
    line-height: 1.6;
}
.clan-create-warning {
    color: #b24e4e;
    font-size: .96em;
}
.createClanInput {
    border-radius: 7px;
    border: 1.3px solid #c7e2ff;
    background: #f8fbff;
    color: #21405c;
    font-size: 1.06rem;
    width: 100%;
    max-width: 340px;
    padding: 8px 10px;
    margin-bottom: 8px;
    transition: border .12s;
    box-sizing: border-box;
}
.createClanInput:focus {
    border: 1.5px solid #339be0;
    outline: none;
}
.clan-emblem-upload-title {
    font-weight: bold;
    margin-bottom: 4px;
    color: #2475d1;
}
.clan-emblem-preview-block {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-top: 4px;
    margin-bottom: 10px;
}
.clan-emblem-preview-block img {
    max-width: 100px;
    max-height: 100px;
    border-radius: 10px;
    border: 2px solid #eaf0fa;
    box-shadow: 0 1px 8px #197acf20;
}
.createClanBtn {
    display: inline-block;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    border: none;
    font-size: 1.12rem;
    background: linear-gradient(90deg,#21a1e0 0%, #21d8b6 100%);
    min-width: 160px;
    padding: 10px 0;
    font-weight: 800;
    text-align: center;
    margin: 6px 0 0 0;
    transition: background .13s, box-shadow .13s;
    box-shadow: 0 1.5px 7px #249ae617;
    outline: none;
    user-select: none;
}
.createClanBtn:hover, .createClanBtn:focus {
    background: linear-gradient(90deg,#21d8b6 0%, #21a1e0 100%);
}
@media (max-width: 700px) {
    .txtCM, .createClan {
        padding-left: 0 !important;
        padding-right: 0 !important;
        width: 98vw !important;
        box-sizing: border-box;
    }
    .clan-create-desc {
        font-size: 1rem;
        padding: 8px 6px;
    }
    .createClanInput {
        font-size: 1rem;
        max-width: 99vw;
        padding: 8px 8px;
    }
    .clan-emblem-upload-title {
        font-size: .98rem;
    }
    .clan-emblem-preview-block img {
        max-width: 75vw;
        max-height: 75vw;
    }
    .createClanBtn {
        width: 100%;
        min-width: 0;
        font-size: 1.08rem;
        margin: 12px 0 0 0;
    }
    .clan-emblem-preview-block {
        justify-content: center;
    }
}
@media (max-width: 450px) {
    .clan-create-desc {
        font-size: .96rem;
    }
    .createClanInput {
        font-size: .98rem;
        padding: 7px 6px;
    }
    .createClanBtn {
        font-size: 1.01rem;
        padding: 8px 0;
    }
}
`
      }).appendTo('head');

      // JS обработка загрузки эмблемы и предпросмотра
      $('.content-model').on('change', '#clanEmblemUpload', function(e){
          var file = this.files[0];
          if(!file) return;

          // Проверка размера (до 10 МБ)
          if(file.size > 10 * 1024 * 1024) {
              alert('Файл слишком большой. Максимум 10 МБ.');
              $(this).val('');
              $('.clan-emblem-preview-block').hide();
              $('#clanCreateEmblem').val('');
              return;
          }

          // Проверка типа
          if(!/^image\/(png|jpeg)$/.test(file.type)) {
              alert('Эмблема должна быть PNG или JPG!');
              $(this).val('');
              $('.clan-emblem-preview-block').hide();
              $('#clanCreateEmblem').val('');
              return;
          }

          // Предпросмотр и сохранение base64 (или blob, если нужно)
          var reader = new FileReader();
          reader.onload = function(ev) {
              $('#clanEmblemPreview').attr('src', ev.target.result);
              $('.clan-emblem-preview-block').show();
              // base64 (если надо отправлять на сервер)
              $('#clanCreateEmblem').val(ev.target.result);
          };
          reader.readAsDataURL(file);
      });

      Game.loaders.worldClose();
  }
},
random: {
  mainRand: function(min,max) {
    var rand = min + Math.random() * (max - min)
    rand = Math.round(rand);
    return rand;
  }
},
autoLoadAtWorld: function() {
    Game.notifications.count();
    Game.notifications.countGift();

    // Кнопка смайлы
    $('<div />', {
        "class": 'Button Smiles el_smile',
        html: '<i class="fas fa-smile"></i>'
    }).appendTo('.MiniButtons');

    // Кнопка очистка чата
    $('<div />', {
        "class": 'Button el_clear_chat',
        html: '<i class="fas fa-eraser"></i>',
        click: function() {
            $('.Message-Block').html('');
        }
    }).appendTo('.MiniButtons');

    // Кнопка "Покемоны на локации"
    $('<div />', {
        "class": 'Button el_loc_poke',
        html: '<i class="fas fa-map-marker-alt"></i>',
        title: 'Покемоны на локации',
        click: function () {
            if ($('.LocPokeModal').length) return;

            let $modal = $('<div class="LocPokeModal"><div class="LocPokeModal-content">Загрузка...</div></div>');
            $('body').append($modal);

            $modal.css({
                position: 'fixed',
                left: '50%',
                top: '22%',
                'z-index': 9999,
                transform: 'translate(-50%, 0)',
                background: 'rgba(255,255,255,0.97)',
                'border-radius': '11px',
                'box-shadow': '0 6px 32px 0 rgba(50,60,90,0.20)',
                'min-width': '260px',
                'max-width': '96vw',
                'max-height': '64vh',
                overflow: 'auto',
                padding: '13px 10px 10px 10px'
            });
            $modal.find('.LocPokeModal-content').css({
                'max-height': '52vh',
                overflow: 'auto'
            });

            $('<div class="LocPokeModal-close" title="Закрыть">&#10006;</div>').css({
                position: 'absolute',
                top: '3px',
                right: '10px',
                cursor: 'pointer',
                color: '#888',
                'font-size': '19px'
            }).appendTo($modal).on('click', function() {
                $modal.remove();
            });

            // AJAX загрузка
            $.get('/do/location_pokemons.php', function(html) {
                $modal.find('.LocPokeModal-content').html(html);
            });
        }
    }).appendTo('.MiniButtons');

    // КНОПКА: Панель (только для групп 1, 2, 3)
    $.ajax({
        url: "/do/init",
        type: "POST",
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            const user_group = Number(response['data']['user_group']);
            if ([1, 2, 3, 9].includes(user_group)) {
                $('<div />', {
                    "class": 'Button el_panel',
                    html: '<i class="fas fa-user-cog"></i>',
                    title: 'Панель'
                })
                .on('click', openUniversalPanel)
                .appendTo('.MiniButtons');
            }
        }
    });

    // Функция открытия основной панели
    function openUniversalPanel() {
        if ($('.PanelModal').length) return;

        let $modal = $(`
            <div class="PanelModal">
                <div class="PanelModal-content">
                    <div class="panel-main-loader">
                        <i class="fas fa-spinner fa-spin"></i> Загрузка панели...
                    </div>
                </div>
            </div>
        `);

        $('body').append($modal);

        $modal.css('z-index', 20000000);

        $modal.find('.PanelModal-close').on('click', function() {
            $modal.remove();
        });

        $.post('/do/panel.php', {}, function(resp) {
            if (resp && resp.html) {
                $modal.find('.PanelModal-content').html(resp.html);

                $modal.find('.admin-panel-tab-btn').off('click').on('click', function() {
                    var tab = $(this).data('tab');
                    $(this).closest('.admin-panel-nav').find('.admin-panel-tab-btn').removeClass('active');
                    $(this).addClass('active');
                    var $panel = $(this).closest('.admin-panel-full');
                    $panel.find('.admin-panel-tab').removeClass('active');
                    $panel.find('.' + tab + '-tab').addClass('active');
                });

                $modal.find('.admin-action-form').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    var $panel = $form.closest('.admin-panel-full');
                    var action = $form.data('action');
                    var data = $form.serializeArray();
                    data.push({name: 'type', value: action});
                    $panel.find('.admin-panel-msg').removeClass('error success').text('Запрос...');
                    $.post('/do/panel.php', data, function(resp) {
                        if(resp && resp.error) {
                            $panel.find('.admin-panel-msg').addClass('error').text(resp.msg || 'Ошибка');
                        } else if(resp && resp.msg) {
                            $panel.find('.admin-panel-msg').addClass('success').text(resp.msg);
                        } else {
                            $panel.find('.admin-panel-msg').addClass('error').text('Неизвестный ответ');
                        }
                    }, 'json').fail(function(){
                        $panel.find('.admin-panel-msg').addClass('error').text('Ошибка соединения');
                    });
                });

            } else if (typeof resp === "string") {
                $modal.find('.PanelModal-content').html(resp);
            } else {
                $modal.find('.PanelModal-content').html('<div style="color:red">Ошибка загрузки панели.</div>');
            }
        }, 'json').fail(function() {
            $modal.find('.PanelModal-content').html('<div style="color:red">Ошибка загрузки панели.</div>');
        });
    }

    if (device.mobile()) {
        $('<div />', {
            "class": 'Button NoActive Dex el_dex',
            html: '<i class="fas fa-book-open"></i>',
            click: function () {
                issetAll(1, 'dex');
            }
        }).appendTo('.TopMenu > .MidMenu');

        $('<div />', {
            "class": 'Button el_fight',
            html: '<i class="fas fa-eye"></i>',
            click: function () {
                setFight();
            }
        }).appendTo('.MiniButtons');
    } else {
        $('<div />', {
            "class": 'Button NoActive el_wild',
            html: '<i class="fas fa-paw"></i>',
            click: function () {
                el_wild(this);
            }
        }).appendTo('.DivRightButtons .Wrap');

        $('<div />', {
            "class": 'Button NoActive Dex el_dex',
            html: '<i class="fas fa-book-open"></i>',
            click: function () {
                issetAll(1, 'dex');
            }
        }).appendTo('.DivRightButtons .Wrap');

        $('<div />', {
            "class": 'Button NoActive el_fight',
            html: '<i class="fas fa-eye"></i>',
            click: function () {
                setFight();
            }
        }).appendTo('.DivRightButtons .Wrap');
    }
},
loadAtWorld: function () {
  $.ajax({
    url: "/do/init",
    type: "POST",
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;

      // ── вставка текста/аватара (как было)
      $('<div />', {
        'class': 'Text',
        html:
          ' <div class="u-' + response.data.user_group + '">' + response.data.login + '</div> ' +
          '<span><i class="fal fa-star"></i> <span id="LVLUSER"></span></span>' +
          '<div class="Bar"><div id="WIDTHLVLUSER_TO"></div><div id="WIDTHLVLUSER"></div></div>'
      }).prependTo('.TopMenu .LeftMenu');

      $('<div />', {
        'class': 'Avatar',
        'style': 'background-image: url(/img/avatars/mini/' + response.data.img + '.png)'
      }).prependTo('.TopMenu .LeftMenu');

      // =============== СОВРЕМЕННЫЙ ПОПОВЕР-ТОЛТИП =================
      (function ensureUserPopoverStyles(){
        if (document.getElementById('user-popover-style-v5')) return;
        const css = `
:root{
  --up-bg: rgba(22,16,40,.86);
  --up-bd: rgba(255,255,255,.14);
  --up-txt:#f6f4ff;
  --up-hover: rgba(255,255,255,.10);
  --up-active: rgba(255,255,255,.14);
}
.user-popover{
  position:fixed;
  z-index:2147482000;
  min-width:220px;
  max-width:min(320px, calc(100vw - 16px));
  background:var(--up-bg);
  color:var(--up-txt);
  backdrop-filter:saturate(140%) blur(10px);
  -webkit-backdrop-filter:saturate(140%) blur(10px);
  border:1px solid var(--up-bd);
  border-radius:14px;
  box-shadow:0 18px 50px rgba(0,0,0,.45);
  overflow:hidden;

  opacity:0;
  transform:translateY(8px) scale(.98);
  transition:opacity .14s ease, transform .14s ease;
  pointer-events:auto;
  font-size:13.5px;
}
.user-popover.show{opacity:1;transform:none}

.user-popover .up-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:10px 12px;
  border-bottom:1px solid rgba(255,255,255,.08);
}
.user-popover .up-title{
  font-weight:700;
  letter-spacing:.2px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.user-popover .up-menu{
  display:flex;
  flex-direction:column;
  padding:6px;
}
.user-popover .up-item{
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 10px;
  border-radius:10px;
  cursor:pointer;
  text-decoration:none;
  color:inherit;
  line-height:1.2;
  user-select:none;
  touch-action:manipulation;
}
.user-popover .up-item:hover{background:var(--up-hover)}
.user-popover .up-item:active{background:var(--up-active)}
.user-popover .up-item i{width:18px;text-align:center;opacity:.9}

.user-popover .up-sep{
  height:1px;
  margin:6px 8px;
  background:rgba(255,255,255,.08);
  border:0;
}

.user-popover .up-tip{
  position:absolute;
  width:10px;
  height:10px;
  background:var(--up-bg);
  border-left:1px solid var(--up-bd);
  border-top:1px solid var(--up-bd);
  transform:rotate(45deg);
}

/* overlay for click-trigger and mobile usability */
.user-popover-overlay{
  position:fixed;
  inset:0;
  z-index:2147481990;
  background:rgba(0,0,0,.22);
}

/* Mobile: превращаем поповер в «bottom sheet» */
@media (max-width:520px){
  .user-popover{
    left:8px !important;
    right:8px !important;
    top:auto !important;
    bottom:calc(8px + env(safe-area-inset-bottom)) !important;
    min-width:0;
    max-width:none;
    width:auto;
    border-radius:18px;
    font-size:15px;
    transform:translateY(14px);
  }
  .user-popover .up-menu{
    max-height:min(60vh, calc(100vh - 140px));
    overflow:auto;
    -webkit-overflow-scrolling:touch;
    padding:8px;
  }
  .user-popover .up-item{
    padding:14px 12px;
    border-radius:14px;
  }
  .user-popover .up-item i{
    width:22px;
    font-size:18px;
  }
  .user-popover .up-tip{display:none}
  .user-popover-overlay{background:rgba(0,0,0,.42)}
}
        `;
        const st = document.createElement('style'); st.id='user-popover-style-v5';
        st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
      })();

      const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints>0);
      const $avatar = $('.TopMenu .LeftMenu .Avatar');

      // === Телепорт на арену (доступен всем) ===
      window.teleportToArena = function () {
        $.ajax({
          url: "/do/goLocation.php",
          type: "POST",
          dataType: "json",
          data: { location_id_arena: 1 }, // backend выставит location_id=8009 и telep=1
          beforeSend: function () { $('.loadWorld').css('display', 'block'); },
          complete:   function () { $('.loadWorld').hide(); },
          success: function (resp) {
            if (resp.error) {
              if (window.Game?.notifications?.main) {
                Game.notifications.main(resp.text || 'Ошибка телепортации.', 'error');
              } else { alert(resp.text || 'Ошибка телепортации.'); }
              return;
            }
            if (resp.text && window.Game?.notifications?.main) {
              Game.notifications.main(resp.text, 'plus');
            }
            try {
              if (resp.action === 'updateLocation') updateLocation();
              else updateLocation();
            } catch (e) { location.reload(); }
          },
          error: function () {
            $('.loadWorld').hide();
            alert('Сервер недоступен. Телепорт на арену не выполнен.');
          }
        });
      };

      // Контент (кнопка «На арену» СРАЗУ под «Тренеркарта»)
      function commonMenu(login){
        return `
          <a class="up-item" data-close><i class="fal fa-id-card"></i>Тренеркарта</a>
          <a class="up-item" data-close><i class="fal fa-user-secret"></i>Подозреваемые</a>
          <a class="up-item" data-close><i class="fal fa-bolt"></i>На арену</a>
          <a class="up-item" data-close><i class="fal fa-gift"></i>Награды за уровень</a>
          <a class="up-item" data-close><i class="fal fa-cog"></i>Настройки</a>
          <a class="up-item" data-close><i class="fal fa-user-tie"></i>Секретарь</a>
          <hr class="up-sep">
          <a class="up-item" data-close href=".." target="_blank"><i class="fal fa-home"></i>Главная страница</a>
          <a class="up-item" data-close href="https://poke-kara.ru/forum" target="_blank" rel="noopener noreferrer"><i class="fal fa-comments"></i>Форум</a>
          <a class="up-item" data-close href="/?route=exit"><i class="fal fa-sign-out"></i>Выход</a>
        `;
      }
      function leaderExtra(){
        return `<a class="up-item" data-close><i class="fal fa-shield-check"></i>Панель Гим-Лидера</a><hr class="up-sep">`;
      }

      // Глобальные обработчики действий (как раньше)
      window.showGymLeaderPanel = function() {
        $.post('/do/gym_leader_panel.php', {type:'gym_leader_panel'}, function(resp){
          $('.LittleModal').remove();
          $('<div />', {"class":"LittleModal", html:resp.html}).appendTo('body');
        }, 'json');
      };
      window.showSekretarPanel = function() {
        $.post('/do/gym.php', {type:'sekretar'}, function(resp){
          $('.LittleModal').remove();
          $('<div />', {"class":"LittleModal", html:resp.html}).appendTo('body');
        }, 'json');
      };

      // Фабрика поповера
      function createUserPopover(anchor, opts, cfg){
        cfg = Object.assign({useOverlay:false, trigger:'click'}, cfg||{});
        const root = document.createElement('div'); root.className='user-popover'; root.setAttribute('role','menu');
        const arrow = document.createElement('div'); arrow.className='up-tip';
        root.innerHTML = `
          <div class="up-head"><div class="up-title">${opts.title||''}</div></div>
          <div class="up-menu">${opts.content||''}</div>
        `;
        root.appendChild(arrow);
        let overlay = null;
        if (cfg.useOverlay){ overlay = document.createElement('div'); overlay.className='user-popover-overlay'; }

        function place(){
          const r = anchor.getBoundingClientRect();
          const vw = window.innerWidth, vh = window.innerHeight;
          const w = root.offsetWidth || 240, h = root.offsetHeight || 180;
          const cand = [
            {x:r.right+10, y:Math.max(8,r.top-4), side:'left'},
            {x:Math.max(8,r.left-4), y:r.bottom+10, side:'top'},
            {x:Math.max(8,r.left-w-10), y:Math.max(8,r.top-4), side:'right'}
          ];
          let best = cand[0];
          for (let c of cand){
            if (c.x+w <= vw-8 && c.y+h <= vh-8){ best=c; break; }
          }
          root.style.left = Math.min(best.x, vw-w-8) + 'px';
          root.style.top  = Math.min(best.y, vh-h-8) + 'px';

          const rb = root.getBoundingClientRect();
          if (best.side==='left'){
            arrow.style.left='-5px';
            arrow.style.top = Math.min(Math.max(10, r.top + r.height/2 - 5 - rb.top), rb.height-16) + 'px';
          } else if (best.side==='right'){
            arrow.style.left=(rb.width-5)+'px';
            arrow.style.top = Math.min(Math.max(10, r.top + r.height/2 - 5 - rb.top), rb.height-16) + 'px';
          } else {
            arrow.style.top='-5px';
            arrow.style.left = Math.min(Math.max(10, r.left + r.width/2 - 5 - rb.left), rb.width-16) + 'px';
          }
        }

        function show(){
          if (overlay) document.body.appendChild(overlay);
          document.body.appendChild(root);
          root.classList.add('show');
          place(); setTimeout(place, 0);
        }
        function hide(){
          root.remove(); overlay && overlay.remove();
          window.removeEventListener('resize', place, true);
          window.removeEventListener('scroll', place, true);
          document.removeEventListener('keydown', onEsc, true);
          document.removeEventListener('click', onOutside, true);
          if (cfg.onClose) cfg.onClose();
        }

        function onEsc(e){ if (e.key==='Escape') hide(); }
        function onOutside(e){ if (!root.contains(e.target) && e.target!==anchor) hide(); }

        // закрытие по клику на пункты меню
        root.addEventListener('click', function(ev){
          const item = ev.target.closest('.up-item');
          if (!item) return;
          const txt = (item.textContent||'').trim();
          setTimeout(()=>{ hide(); }, 0);

          if (/На арену/i.test(txt))              { teleportToArena(); return; }
          if (/Тренеркарта/i.test(txt))          { Game.trenercard.opencard(response.data.login); return; }
          if (/Подозреваемые/i.test(txt))        { 
                      const ug = Number((response && response.data && response.data.user_group) || 0);
                      if (ug !== 1) {
                        if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
                          Game.notifications.main('Доступно только администрации.', 'error');
                        } else {
                          alert('Доступно только администрации.');
                        }
                        return;
                      }
                      (function loadAntiBotSuspects(){
                        var src = '/js/antibot_suspects.js?v=1';
                        if (document.querySelector('script[data-ab-suspects="1"]')) {
                          if (window.AntiBotSuspects && typeof window.AntiBotSuspects.open === 'function') window.AntiBotSuspects.open();
                          return;
                        }
                        var s = document.createElement('script');
                        s.src = src;
                        s.async = true;
                        s.setAttribute('data-ab-suspects','1');
                        s.onload = function(){ if (window.AntiBotSuspects && typeof window.AntiBotSuspects.open === 'function') window.AntiBotSuspects.open(); };
                        s.onerror = function(){
                          if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
                            Game.notifications.main('Не удалось загрузить модуль "Подозреваемые".', 'error');
                          } else { alert('Не удалось загрузить модуль "Подозреваемые".'); }
                        };
                        document.head.appendChild(s);
                      })();
                      return; 
                    }
          if (/Награды за уровень/i.test(txt))   { openModal('lvlpr'); return; }
          if (/Настройки/i.test(txt))            { settings(); return; }
          if (/Секретарь/i.test(txt))            { showSekretarPanel(); return; }
          if (/Панель Гим-Лидера/i.test(txt))    { showGymLeaderPanel(); return; }
        });

        window.addEventListener('resize', place, true);
        window.addEventListener('scroll', place, true);
        document.addEventListener('keydown', onEsc, true);
        if (cfg.useOverlay){
          overlay.addEventListener('click', hide);
          document.addEventListener('click', onOutside, true);
        }

        return {root, show, hide, place, trigger: cfg.trigger};
      }

      let pop = null;
      function openPopover(trigger){
        const isLeader = Number(response.data.user_group) === 5;
        const content = (isLeader ? leaderExtra() : '') + commonMenu(response.data.login);
        if (pop && trigger==='click'){ pop.hide(); pop=null; return; }
        if (pop) { pop.hide(); pop=null; }
        pop = createUserPopover($avatar[0], { title: response.data.login, content }, { useOverlay: (trigger==='click'), trigger, onClose:()=>{ pop=null; } });
        if (trigger==='hover'){
          pop.root.addEventListener('mouseenter', ()=> clearTimeout(hoverCloseTO));
          pop.root.addEventListener('mouseleave', ()=> scheduleHoverClose());
        }
        pop.show();
      }

      // анти-дребезг для hover
      let hoverOpenTO=null, hoverCloseTO=null;
      function scheduleHoverOpen(){ clearTimeout(hoverOpenTO); hoverOpenTO=setTimeout(()=>openPopover('hover'),120); }
      function scheduleHoverClose(){ clearTimeout(hoverCloseTO); hoverCloseTO=setTimeout(()=>{ if (pop && pop.trigger==='hover'){ pop.hide(); pop=null; } },220); }

      // привязка событий
      $avatar.off('.userPopover');
      if (isTouch){
        $avatar.on('touchstart.userPopover click.userPopover', function(e){ e.preventDefault(); e.stopPropagation(); openPopover('click'); });
      } else {
        $avatar.on('mouseenter.userPopover', function(){ scheduleHoverOpen(); })
               .on('mouseleave.userPopover', function(){ scheduleHoverClose(); })
               .on('click.userPopover', function(e){ e.preventDefault(); e.stopPropagation(); openPopover('click'); });
      }
      // ============================================================
    }
  });
},

	loaders: {
		main: function() {
// 			$(".preloader").find('span').html(Lang.loader_load);
			$("#locationPreloader").delay(500).fadeOut(200);
		},
		world: function() {
			$('.loadWorld').fadeIn(0);
		},
		worldClose: function() {
			$('.loadWorld').fadeOut(100);
		},
		mudol: function() {
			$('.mudol').html('<center id="mainLoader">'+mainLoader+'</center>');
		},
		mudolClose: function() {
			$('#mainLoader').remove();
		}
	},
	notifications: {
		main: function(a,b){
			var rand = Game.random.mainRand(1,1000000),
				rand = 'notyId'+rand,
				icon;
			if(b == 'success'){
				icon = 'fa fa-check';
			}else if(b == 'error'){
				icon = 'fas fa-times';
			}else if(b == 'info' || b == 'admin' || b == 'version'){
				icon = 'fas fa-info';
			}else if(b == 'warning'){
				icon = 'fas fa-exclamation-triangle';
			}else if(b == 'plus'){
				icon = 'fas fa-plus';
			}else if(b == 'minus'){
				icon = 'fas fa-minus';
			}else if(b == 'quest'){
				icon = 'fas fa-info';
			}
			$('<div />', {
				"class": 'noty '+b,
				"id": rand,
				html: '<div class="icon"><i class="'+icon+'"></i></div><div class="content">'+a+'</div>',
				"click": function() {
					$('#'+rand).fadeOut(500, function() {
						$('#'+rand).remove();
					});
				}
			}).appendTo('.DivNotification');
			setTimeout(function() {
				$('#'+rand).fadeOut(500, function() {
					$('#'+rand).remove();
				});
			}, 5000);
		},
		count: function() {
			$.ajax({
				url: "/do/notifications",
				type: "POST",
				data: "type=count",
				success: function (data){
					$('#countNotif').html(data);
					if($('#countNotif').html() > 0){
						$('.TopMenu .RightMenu .Buttons .ntUs').css('background','#e06771');
					}else{
						$('.TopMenu .RightMenu .Buttons .ntUs').css('background','#f2f2f2');
					}
				},
				complete: function(){
					setTimeout(function(){
						Game.notifications.count();
					}, 3000);
				}
			});
		},
		countGift: function() {
			$.ajax({
				url: "/do/pp",
				type: "POST",
				data: "giftFriend=1",
				success: function (response){
				    
				        response = (typeof response === 'string') ? JSON.parse(response) : response;
    					$('#countNotifFr').html(response['text']);
    					if($('#countNotifFr').html() > 0){
    						$('.TopMenu .RightMenu .Buttons .ntGf').css('background','#e06771');
    					}else{
    						$('.TopMenu .RightMenu .Buttons .ntGf').css('background','#f2f2f2');
    					}
				    
				    
				},
				complete: function(){
					setTimeout(function(){
						Game.notifications.countGift();
					}, 3000);
				}
			});
		}
	},
  init: function(){
    if(REITS == 1) {
      $('.DivNotification').append('<div class="noty reits" onclick=$(this).fadeOut(500);><div class="icon"><i class="fas fa-level-up"></i></div><div class="content">'+REITS_TEXT+'</div></div>');
    }
		Aqua.loaders.main('Загрузка...');
		Game.updateUserLocation();
		updateLocation();

		Game.autoLoadAtWorld();
		Game.loadAtWorld();
		Game.title_tooltip();
	}
}
/*function captcha(){
    var is = $('#InputCaptcha').val();
        $.ajax({
				url: "/do/pp",
				type: "POST",
				data: "captcha="+is,
				success: function (response){
response = (typeof response === 'string') ? JSON.parse(response) : response;
Game.notifications.main(response['text'],response['error']);
if(response['error'] == "success"){
    $('.WindowCaptcha').detach();
}else{
    $('#InputCaptcha').val("");
}
				}
			});
    }*/
    
function revival_openModal(){
    
			$.ajax({
                url: "/do/ItemsUpdate",
                type: "POST",
                data: "type=open",
                success: function (response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    if(response['error'] == 1){
                        Game.notifications.main(response['text'],'error');
                    }else{
                        $('.model').remove();
			Game.modals.modelLoad('Усиление предмета');
                        $('<div />', {
				'class': 'txtCM',
				html: response['html']
			}).appendTo('.content-model');
                    }
                }
            });
}
function submitResponse(user,type,data){
    $('.BlockOtherContent').hide();
    if(type == 'friend' || type == 'clan'){
        $('.BlockOtherContent').removeClass('updated');
    }
    $.ajax({
        url: "/do/submitResponse",
        type: "POST",
        data: {
            type: type,
            data: data,
            user: user
        },
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if(response['error'] == 1){
                Game.notifications.main(response['text'],'error');
            }else{
                Game.notifications.main(response['text'],'success');
                if(response['type'] == 'trade'){
                    openTrade();
                }
            }
        }
    });
}
function admin(id){
    $.ajax({
        url: "/do/admin",
        type: "POST",
        data: "type=admin",
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if(response['code'] == id){
                openadmintab();
            }
        }
    });
}
function addpok(info){
        $.ajax({
				url: "/do/pokemonr",
				type: "POST",
				data: "id="+info,
				success: function (response){

            alert(response['text']);
				}
			});
    }

function funcadm() {
    var func = $('.cmd').val();
    $.ajax({
        url: "/do/admin",
        type: "POST",
        data: "type=func&func="+func,
        success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				$('.ConsoleLog').append('> '+func+'<br>');
				$('.ConsoleLog').append(response["html"]);
				$('.cmd').val("");
        }
    });
}

function return_delpok(type,id){
    $.ajax({
            url: "/do/return_pokAction",
            type: "POST",
            data: "type="+type+"&return_pok="+id,
		beforeSend: function(){

           $('.pok-1').html('<center>'+mainLoader+'</center>');
        },
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			$('.pok-1').html(response['html']);
			if(response['minus']) {
              Game.notifications.main(response['minus'],'minus');
            }
            if(response['plus']) {
              Game.notifications.main(response['plus'],'plus');
            }
            if(response['text']) {
            Game.notifications.main(response['text'],response['error']);
            }
		}
        });
}

function return_del_pok(){
    CloseModel();
    $.ajax({
            url: "/do/return_pok",
            type: "POST",
            data: "type=open",
		beforeSend: function(){
			$('<div />', {
					'class':'model'
				}).appendTo('body');
           $('.model').html('<center>'+mainLoader+'</center>');
        },
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			$('.model').html(response['html']);
			if(!device.mobile()){
      						$('.model').draggabilly({
      							handle: '.header',
      							containment: true
      						});
      					}
		}
        });
}

function nursClear(){
    $('#SortType').prop('selectedIndex',0);
    $('#SortGender').prop('selectedIndex',0);
    $('#SortSparka').prop('selectedIndex',0);
    $('#SortTren').prop('selectedIndex',0);
    $('#SortItem').prop('selectedIndex',0);
    $('#SortCharacter').prop('selectedIndex',0);
    $('#SortTeam').prop('selectedIndex',0);
    $('#SortLvl').val('');
}
// Nursery UI: compact unified markup, same endpoints and bindings.
// - Do not change existing structure/handlers, only improve HTML inside.
// - Cards are rendered as .divFarmPoke with extra class .poke-card for unified CSS.
// - Uses optional grid wrappers (.nursery-wrap/.nursery-grid) to look compact.

function nursery(type, basenum = false, e = false, sort = false){
  if(type=="back"){
    $('.pok-2').remove();
    $('.back').remove();
    $('#sortNursery').remove();
    $('.pok-1').show();
    $('#SortType').prop('selectedIndex',0);
    $.ajax({
      url: "/do/Npc/nursery",
      type: "POST",
      data: "op=1",
      success: function (response) {
        response = safeJson(response);
        $('.pageDex').html(response['page']);
      }
    });

  }else if(type=="open"){
    $.ajax({
      url: "/do/modal",
      type: "POST",
      data: "type=nurseryList&basenum="+basenum+"&sort="+(sort||0),
      beforeSend: function(){
        $('.pok-1').hide();
      },
      success: function (response) {
        response = safeJson(response);

        var tpl = '';
        tpl+= '<div class="pok-2">';
        // compact grid wrappers (do not break bindings)
        tpl+= '<div class="nursery-wrap"><div class="nursery-grid">';

        var res = (response['pokList'] || []).slice().reverse();
        $.each(res, function(x,y){
          tpl += renderNurseryItem(y, 'open'); // tooltip action
          // hr kept (hidden in grid via CSS)
          tpl += ' <div class="hr"></div>';
        });

        tpl+= '</div></div>'; // grid wrappers
        tpl+= '</div>';

        $('.pit').append(tpl);
        $('.pageDex').html(response['page']);

        if(sort){
          $("select [value='"+sort+"']").attr("selected", "selected");
        }
        $('select').on('change', function (){
          $('.pok-2').remove();
          $('.back').remove();
          $('#sortNursery').remove();
          nursery("open",basenum,false,this.value);
        });
      }
    });

  }else if(type=="search"){
    var search = $('#pitInput').val();
    var sortType = $('#SortType').val();
    var sortGender = $('#SortGender').val();
    var sortSparka = $('#SortSparka').val();
    var sortTren = $('#SortTren').val();
    var sortItem = $('#SortItem').val();
    var sortCharacter = $('#SortCharacter').val();
    var sortTeam = $('#SortTeam').val();
    var sortLvl = $('#SortLvl').val();
    var dop = sortType+','+sortGender+','+sortSparka+','+sortTren+','+sortItem+','+sortCharacter+','+sortTeam+','+sortLvl;
    if(search=='' && dop=="0,0,0,0,0,0,0,0"){
      return Game.notifications.main(Lang.text_query_null,"error");
    }

    $.ajax({
      url: "/do/Npc/nursery",
      type: "POST",
      data: "search="+encodeURIComponent(search)+"&dop="+dop,
      success: function (response) {
        response = safeJson(response);
        $('.back').remove();
        $('.pok-2').remove();
        $('.pok-1').hide();

        var tpl = '';
        tpl+= '<div class="pok-2">';

        if(response['error'] == 1){
          tpl+= '<center>'+Lang.text_category_null+'</center>';
        }else{
          tpl+= '<div class="nursery-wrap"><div class="nursery-grid">';

          var res = (response['pokList'] || []).slice().reverse();
          $.each(res, function(x,y){
            tpl += renderNurseryItem(y, 'list'); // direct get action
            tpl += ' <div class="hr id'+y['id']+'"></div>';
          });

          tpl+= '</div></div>';
        }

        tpl+= '</div>';
        $('.pit').append(tpl);
        $('.pageDex').html(response['page']);
      }
    });

  }else if(type=="page"){
    var search = $('#pitInput').val();
    var sortType = $('#SortType').val();
    var sortGender = $('#SortGender').val();
    var sortSparka = $('#SortSparka').val();
    var sortTren = $('#SortTren').val();
    var sortItem = $('#SortItem').val();
    var sortCharacter = $('#SortCharacter').val();
    var sortTeam = $('#SortTeam').val();
    var sortLvl = $('#SortLvl').val();
    var dop = sortType+','+sortGender+','+sortSparka+','+sortTren+','+sortItem+','+sortCharacter+','+sortTeam+','+sortLvl;
    if(search=='' && dop=="0,0,0,0,0,0,0,0"){
      return Game.notifications.main(Lang.text_query_null,"error");
    }

    $.ajax({
      url: "/do/Npc/nursery",
      type: "POST",
      data: "search="+encodeURIComponent(search)+"&dop="+dop+"&page="+(sort||1),
      success: function (response) {
        response = safeJson(response);
        $('.back').remove();
        $('.pok-2').remove();
        $('.pok-1').hide();

        var tpl = '';
        tpl+= '<div class="pok-2">';

        if(response['error'] == 1){
          tpl+= '<center>'+Lang.text_category_null+'</center>';
        }else{
          tpl+= '<div class="nursery-wrap"><div class="nursery-grid">';

          var res = (response['pokList'] || []).slice().reverse();
          $.each(res, function(x,y){
            tpl += renderNurseryItem(y, 'list');
            tpl += ' <div class="hr id'+y['id']+'"></div>';
          });

          tpl+= '</div></div>';
        }

        tpl+= '</div>';
        $('.pit').append(tpl);

        $('.pageDex div').removeClass('active');
        $('.pageDex div:nth-child('+sort+')').addClass('active');
      }
    });

  }else if(type=="tooltip"){
    $('.GiveDiv').remove();
    var tpl = '<div class="GiveDiv">';
    tpl+= '<div id="DivAbout">'+Lang.text_info+'</b></div>';
    tpl+= '<div class="wrap"><div class="PokList">';
    tpl+= '<div class="PokBtn" onclick=nursery("get",'+basenum+');>'+Lang.button_take_pokemon_pit+'</div>';
    tpl+= '</div></div></div>';
    $(tpl).appendTo('body');
    var offset = $(e).offset();
    var left = Math.round(offset.left+30);
    var top = Math.round(offset.top+15);
    $('.GiveDiv').css({ "left": left+'px', "top": top+'px' });

  }else if(type=="get"){
    $.ajax({
      url: "/do/modal",
      type: "POST",
      data: "type=nurseryGet&basenum="+basenum,
      success: function (response) {
        response = safeJson(response);
        if(response['error'] == 1){
          Game.notifications.main(response['html'],'error');
        }else{
          Game.notifications.main(response['html'],'success');
          $('.divFarmPoke.id'+basenum).hide();
          $('.hr.id'+basenum).hide();
        }
      }
    });
  }else{
    // noop
  }
}

/* Renders one item, keeping legacy structure, adding compact classes for unified CSS.
   mode: 'open' => action shows tooltip; 'list' => action performs get.
*/
function renderNurseryItem(y, mode){
  var gen = String(y['gen']||'').split(',');
  var g0 = gen[0]||0, g1 = gen[1]||0, g2 = gen[2]||0, g3 = gen[3]||0, g4 = gen[4]||0, g5 = gen[5]||0;

  var action = (mode === 'open')
    ? 'nursery(&quot;tooltip&quot;,'+y['id']+',this);'
    : 'nursery(&quot;get&quot;,'+y['id']+');';

  var name = escapeHtml(y['name']||'');
  var alt  = '#'+y['basenum']+' '+name;

  var ivLine = 'h'+g0+'a'+g1+'d'+g2+'s'+g3+'sa'+g4+'sd'+g5+'.'+(y['vitamines']||0)+' ('+(y['sparkaNumber']||0)+') '+(y['tren']||'');

  var extras = '';
  if(y['trade'] == "false"){ extras += ' <i class="fas fa-lock"></i>'; }
  if(Number(y['item_id']) >= 1){ extras += ' <i class="fas fa-cube"></i>'; }

  // Root has both legacy and modern class to apply unified compact card styling
  var html = '';
  html += '<div class="divFarmPoke poke-card id'+y['id']+'">';
  html += '  <div class="btnBackPokemon poke-card__action" onclick='+action+' title="'+(window.Lang && Lang.button_take_pokemon_pit ? Lang.button_take_pokemon_pit : 'Забрать из питомника')+'">';
  html += '    <i class="fas fa-sign-out-alt"></i>';
  html += '  </div>';
  html += '  <div class="pokemonBoxTiny size0 clickable" onclick="openInfoNursery('+y['id']+');">';
  html += '    <img class="image" loading="lazy" decoding="async" src="/img/pokemons/pokedex/'+y['basenum']+'.png" alt="'+alt+'">';
  html += '    <div class="nameNur '+y['type']+'-color poke-card__name"><span class="dex">#'+y['basenum']+'</span> '+name+'</div>';
  html += '    <div class="shorts">'+(y['sex']||'')+' '+(y['lvl']||'')+'</div>';
  html += '    <div class="extra poke-card__meta"><span class="ivcode">'+ivLine+extras+'</span></div>';
  html += '  </div>';
  html += '</div>';

  return html;
}

// Helpers
function safeJson(resp){
  try{
    if(typeof resp === 'string') return JSON.parse(resp);
    return resp || {};
  }catch(e){ return {}; }
}
function escapeHtml(str){
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}
function water_ball(id){
    var op = $('.Water_ball#id'+id).css('opacity');
    var sl = op-0.25;

					$('.Water_ball#id'+id).css('opacity',sl);
    if(sl == 0){
        $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "water_ball="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.Water_ball#id'+id).remove();
          			Game.notifications.main(response['html'],response['error']);

          			if(response['plus']){
					    Game.notifications.main(response['plus'],'plus');
					}
        		}
      });
    }

}
function pokemon_catch(id){
    $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "pokemon_catch="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.Pokemon_fly#id'+id).remove();
          			

          			if(response['html']){
          			    Game.notifications.main(response['html'],response['error']);
					}
        		}
      });
}


(function($){
  if (window.__podomarket) return; window.__podomarket = true;

  /* ===================== CSS ===================== */
  (function injectCSS(){
    if (document.getElementById('podomarket-css')) return;
    var css =
    '.pmarket{--bg:#f7f9ff;--card:#fff;--br:#e7ecff;--t:#1b2b4f;--sub:#6f7b95;--chip:#eef3ff;--ac:#2f74ff;--ac2:#0e55b6;--shadow:0 10px 26px rgba(23,35,74,.1)}'+
    '.pmarket-modal{position:fixed;z-index:1000;left:24px;top:24px;width:1020px;max-width:96vw;height:86vh;background:var(--bg);border:1px solid var(--br);border-radius:16px;box-shadow:var(--shadow);color:var(--t);overflow:hidden;transform:translateY(6px) scale(.985);opacity:.98;transition:.16s;font-family:Nunito,Inter,Arial,sans-serif}'+
    '.pmarket-modal.show{transform:translateY(0) scale(1);opacity:1}'+
    '.pm-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:linear-gradient(180deg,var(--card),transparent);border-bottom:1px solid var(--br)}'+
    '.pm-left{display:flex;align-items:center;gap:10px}.pm-grip{width:16px;height:16px;border-radius:6px;background:var(--card);border:1px solid var(--br);cursor:grab}'+
    '.pm-ttl{font-weight:900;font-size:18px}.pm-sub{font-size:12px;font-weight:800;color:var(--sub)}'+
    '.pm-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}'+
    '.pm-search{display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--br);border-radius:12px;padding:6px 10px}'+
    '.pm-search input{border:0;outline:0;background:transparent;font-weight:800;width:260px;color:var(--t)}'+
    '.pm-btn{border:1px solid var(--br);background:var(--card);border-radius:10px;padding:6px 8px;font-weight:800;color:var(--t);cursor:pointer}'+
    '.pm-close{width:32px;height:32px;border-radius:10px}'+
    '.pm-cats{display:flex;gap:8px;flex-wrap:wrap;padding:8px 12px;background:var(--card);border-bottom:1px solid var(--br)}'+
    '.pm-cat{border:1px solid var(--br);background:var(--chip);color:var(--t);padding:6px 10px;border-radius:999px;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap}'+
    '.pm-cat.active{border-color:var(--ac);box-shadow:0 6px 18px rgba(46,116,255,.18)}'+
    '.pm-bar{display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--card);border-bottom:1px solid var(--br);flex-wrap:wrap}'+
    '.pm-select,.pm-ctrl{border:1px solid var(--br);background:var(--card);border-radius:10px;padding:6px 8px;font-weight:300;color:var(--t)}'+

    /* ВНУТРЕННИЙ СКРОЛЛ */
    '.pm-body{height:calc(86vh - 206px);overflow:auto;padding:12px}'+

    /* GRID карточек */
    '#pm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}'+
    '#pm-grid, #pm-grid *{box-sizing:border-box}'+
    '#pm-grid .lot{float:none!important;clear:none!important;position:relative!important;margin:0!important;display:flex!important;flex-direction:column;gap:6px;background:var(--card);border:1px solid var(--br);border-radius:14px;box-shadow:var(--shadow);padding:8px;min-height:120px;overflow:visible}'+
    '#pm-grid .top{display:flex;align-items:center;gap:8px}'+
    '#pm-grid .item{width:52px;height:52px;border:1px solid var(--br);border-radius:12px;background:rgba(255,255,255,.05);display:grid;place-items:center;flex:0 0 auto}'+
    '#pm-grid img.items,#pm-grid img.pokemons{max-width:42px;max-height:42px}'+
    '#pm-grid .name{font-weight:900;line-height:1.2;flex:1;white-space:normal;font-size:13px}'+
    '#pm-grid .info{display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:13px;color:var(--sub);font-weight:800}'+
    '#pm-grid .remain{margin-left:auto;white-space:nowrap;font-variant-numeric:tabular-nums}'+
    '#pm-grid .buttons{display:flex;gap:6px;flex-wrap:wrap;margin-top:auto}'+
    '#pm-grid .buttons .button{border-radius:10px;padding:5px 7px;font-weight:900;background:linear-gradient(180deg,var(--ac),var(--ac2));border:1px solid var(--ac);color:#fff;cursor:pointer;white-space:nowrap;font-size:11px}'+
    '#pm-grid .buttons .button + .button{background:var(--card);color:var(--t);border:1px solid var(--br)}'+
    '#pm-grid .lot.has-buy:before{content:"BUY";position:absolute;top:6px;left:6px;background:#e9f3ff;color:#0e55b6;border:1px solid var(--br);border-radius:8px;padding:2px 6px;font-size:10px;font-weight:900}'+
    '#pm-grid .lot.admin:after{content:"ADM";position:absolute;top:6px;left:6px;background:#fff6e6;color:#8b5a00;border:1px solid #ffd9a8;border-radius:8px;padding:2px 6px;font-size:10px;font-weight:900}'+

    /* Поповер */
    '.pm-pop{position:absolute;z-index:1486;min-width:260px;max-width:330px;background:var(--card);border:1px solid var(--br);border-radius:12px;box-shadow:0 16px 34px rgba(23,35,74,.18);padding:10px;display:none}'+
    '.pm-pop .head{display:flex;gap:10px;align-items:center;margin-bottom:6px}'+
    '.pm-pop .head .icon{width:44px;height:44px;border:1px solid var(--br);border-radius:10px;display:grid;place-items:center;background:rgba(255,255,255,.04)}'+
    '.pm-pop .head img{max-width:40px;max-height:40px}'+
    '.pm-pop .badges{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px}'+
    '.pm-pop .badge{border:1px solid var(--br);background:var(--chip);border-radius:999px;padding:2px 8px;font-weight:900;font-size:10px}'+
    '.pm-pop .kv{display:grid;grid-template-columns:98px 1fr;gap:6px;font-size:12px}.pm-pop .kv b{font-weight:900}'+
    '.pm-pop .history{margin-top:8px;border-top:1px dashed var(--br);padding-top:6px;max-height:160px;overflow:auto;font-size:12px}'+
    '.pm-pop .history .row{display:flex;justify-content:space-between;gap:8px;padding:3px 0;border-bottom:1px dashed rgba(127,147,185,.15)}'+
    '.pm-pop .history .row:last-child{border:0}'+
    '.pm-pop .foot{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}'+
    '.pm-pop .btn{border:1px solid var(--ac);background:linear-gradient(180deg,var(--ac),var(--ac2));border-radius:10px;color:#fff;font-weight:900;padding:6px 8px;font-size:12px;cursor:pointer}'+
    '.pm-pop .btn.buy{background:#fff;color:var(--t);border:1px solid var(--br)}'+
    '.pm-pop .close{position:absolute;right:6px;top:6px;border:1px solid var(--br);background:var(--card);border-radius:8px;width:22px;height:22px;cursor:pointer}'+
    '.pm-pop .pk-btn{border:1px dashed var(--ac);background:transparent;border-radius:10px;padding:6px 8px;font-weight:900;color:var(--ac2);cursor:pointer;font-size:12px}'+

    /* Пагинация */
    '.pm-pager{display:flex;gap:6px;justify-content:center;align-items:center;margin:12px 0 4px}'+
    '.pm-page,.pm-prev,.pm-next{border:1px solid var(--br);background:var(--card);border-radius:8px;padding:4px 8px;font-weight:800;cursor:pointer;font-size:12px}'+
    '.pm-page.active{background:var(--ac);color:#fff;border-color:var(--ac)}'+
    '.pm-ellipsis{padding:0 4px;font-weight:900;color:var(--sub)}'+

    '@media (max-width:760px){.pmarket-modal{z-index:99999}.pm-pop{z-index:100000}.pm-search input{width:175px}.pm-body{height:calc(86vh - 236px)}#pm-grid{grid-template-columns:repeat(auto-fill,minmax(170px,1fr))}}';
    var st=document.createElement('style'); st.id='podomarket-css'; st.type='text/css'; st.appendChild(document.createTextNode(css));
    document.head.appendChild(st);
  })();

  /* ===================== helpers ===================== */
  function place($b){ var vw=window.innerWidth||document.documentElement.clientWidth; $b.css({left:(vw-$b.outerWidth())/2+window.pageXOffset, top:24+window.pageYOffset}); }
  function drag($box,$handle){
    var dragging=false,dx=0,dy=0;
    $handle.on('mousedown.pm touchstart.pm', function(e){
      if (!$(e.target).closest('.pm-grip').length) return;
      var r=$box[0].getBoundingClientRect();
      var x=(e.touches?e.touches[0].clientX:e.clientX), y=(e.touches?e.touches[0].clientY:e.clientY);
      dx=x-r.left; dy=y-r.top; dragging=true;
      $(document).on('mousemove.pm touchmove.pm', move);
      $(document).on('mouseup.pm touchend.pm touchcancel.pm', up);
      e.preventDefault();
    });
    function move(e){
      if(!dragging) return;
      var pad=6, vw=window.innerWidth||document.documentElement.clientWidth, vh=window.innerHeight||document.documentElement.clientHeight, r=$box[0].getBoundingClientRect();
      var x=(e.touches?e.touches[0].clientX:e.clientX), y=(e.touches?e.touches[0].clientY:e.clientY);
      var l=x-dx, t=y-dy; l=Math.min(Math.max(pad,l),vw-r.width-pad); t=Math.min(Math.max(pad,t),vh-40);
      $box.css({left:l+window.pageXOffset, top:t+window.pageYOffset});
    }
    function up(){ dragging=false; $(document).off('.pm'); }
  }
  function debounce(fn,t){ var tm; return function(){ var a=arguments,th=this; clearTimeout(tm); tm=setTimeout(function(){ fn.apply(th,a); },t); }; }
  function normalize(resp){ if(typeof resp==='string'){ try{ var j=$.parseJSON(resp); return j&&j.html?j.html:resp; }catch(e){ return resp; } } if(resp&&resp.html) return resp.html; return ''; }
  function fmtMoney(n){ n=+n||0; try{ return new Intl.NumberFormat('ru-RU').format(n)+' гк.'; }catch(_){ return String(n)+' гк.'; } }
  function fmtLeft(ms){ if(!ms) return '—'; var s=Math.max(0,Math.floor(ms/1000)), m=Math.floor(s/60), h=Math.floor(m/60); s%=60; m%=60; return h? (h+'ч '+m+'м') : (m+':'+(s<10?'0'+s:s)); }

  /* ===================== атрибуты лота ===================== */
  function ensureAttrs($lot){
    if ($lot.data('attrsOk')) return;
    var $ownBtns = $lot.find('> .bottom .buttons .button');
    function numFrom($el){ var t=($el.text()||'').replace(/[^\d]/g,''); return t?parseInt(t,10):0; }
    var bid  = +($lot.attr('data-bid')||0)  || numFrom($ownBtns.eq(0));
    var buy  = +($lot.attr('data-buyprice')||0) || numFrom($ownBtns.eq(1));
    var type = $lot.attr('data-type') || ($lot.find('img.pokemons').length? 'pokemon' : ($lot.find('.name').text().match(/яйц|egg/i)?'egg':'item'));
    var endts= +($lot.attr('data-endts')||0);
    var bids = +($lot.attr('data-bids')||0);
    var seller = $lot.attr('data-seller-name') || $lot.find('.user-link .label').text().trim();
    $lot.attr({'data-bid':bid,'data-buyprice':buy,'data-type':type,'data-endts':endts,'data-bids':bids,'data-seller-name':seller});
    if (buy>0) $lot.addClass('has-buy');
    $lot.data('attrsOk',1);
  }
// Открыть современную LittleModal со ставкой
window.mkxOpenLotModal = function(lotId){
  // гасим старые окна этой модалки
  $('.LittleModal.mkx-auction').remove();

  $.ajax({
    url: '/do/pp',
    type: 'POST',
    dataType: 'json',
    data: { type: 'lot_stavk', id: lotId },
    success: function(resp){
      var html = (resp && (resp.html || resp.text)) ? (resp.html || resp.text) : resp;
      if (!html) return;
      $('body').append(html);
    },
    error: function(){
      alert('Не смог открыть окно ставки. Попробуй ещё раз.');
    }
  });
};

  /* ===================== поповер ===================== */
  var $openPop=null;
  function closePop(){ if($openPop){ $openPop.remove(); $openPop=null; } }

  function openPop($lot){
    closePop(); ensureAttrs($lot);
    var lotId = $lot.attr('data-lot') || (($lot.find('.lotid').text()||'').replace(/\D/g,'')); if(!lotId) return;

    var $modal = $lot.closest('.pmarket-modal');

    $.post('/do/lombard.php', { get_lot: lotId }, function(payload){
      try{ if(typeof payload==='string') payload=JSON.parse(payload); }catch(e){}

      var name  = (payload && payload.name_html) ? payload.name_html : ($lot.find('.name').clone().children().remove().end().html()||'').trim();
      var img   = $lot.find('img.pokemons, img.items').first().attr('src')||'';
      var seller= (payload && payload.seller) || $lot.attr('data-seller-name')||'';
      var endts = (payload && payload.dateEnd*1000) || +($lot.attr('data-endts')||0);
      var left  = endts? (endts - Date.now()) : 0;
      var priceNow = (payload && +payload.priceNow) || +($lot.attr('data-pricenow')||0);
      var buy   = (payload && +payload.priceBuy) || +($lot.attr('data-buyprice')||0);
      var bids  = (payload && +payload.bids_cnt) || +($lot.attr('data-bids')||0);
      var adm   = ($lot.attr('data-admin')==='1');
      var pro   = ($lot.attr('data-pro')==='1');
      var type  = $lot.attr('data-type')||'';

      var $pop = $('<div class="pm-pop">'+
        '<button class="close">×</button>'+
        '<div class="head">'+
          '<div class="icon">'+ (img?('<img src="'+img+'" alt="">'):'') +'</div>'+
          '<div style="font-weight:900;line-height:1.15;font-size:13px">'+name+'</div>'+
        '</div>'+
        '<div class="badges">'+
          (adm?'<span class="badge">ADM</span>':'')+
          (pro?'<span class="badge">PRO</span>':'')+
          (type?'<span class="badge">'+type+'</span>':'')+
        '</div>'+
        '<div class="kv">'+
          '<div>Лот:</div><div><b>lot'+lotId+'</b></div>'+
          '<div>Продавец:</div><div>'+seller+'</div>'+
          '<div>Ставка:</div><div><b>'+ (priceNow?fmtMoney(priceNow):'—') +'</b></div>'+
          '<div>Выкуп:</div><div>'+(buy?('<b>'+fmtMoney(buy)+'</b>'):'—')+'</div>'+
          '<div>Осталось:</div><div>'+(left>0?('<b>'+fmtLeft(left)+'</b>'):'—')+'</div>'+
          '<div>Ставок:</div><div>'+bids+'</div>'+
        '</div>'+
        '<div class="history"></div>'+
        '<div class="foot"></div>'+
      '</div>');

      if (payload && $.isArray(payload.bids)) {
        var rows = payload.bids.map(function(b){
          return '<div class="row"><div>'+b.when+'</div><div><b>'+fmtMoney(b.money)+'</b> — '+b.user+'</div></div>';
        }).join('');
        $pop.find('.history').html(rows || '<div class="row"><div>—</div><div>Ставок ещё нет</div></div>');
      }

      $('<button class="btn"><i class="far fa-gavel"></i> Сделать ставку</button>')
  .on('click', function(e){
    e.stopPropagation();
    // закрыть поповер и открыть новую LittleModal
    if ($openPop) { $openPop.remove(); $openPop = null; }
    if (window.mkxOpenLotModal) mkxOpenLotModal(lotId);
  })
  .appendTo($pop.find('.foot'));

      if (buy>0){
        $('<button class="btn buy"><i class="far fa-shopping-cart"></i> Выкупить</button>')
          .on('click', function(e){ e.stopPropagation(); if(window.vikup) return vikup(lotId); })
          .appendTo($pop.find('.foot'));
      }

      if ((payload && payload.category==='pokemon') || type==='pokemon'){
        $('<button class="pk-btn"><i class="far fa-info-circle"></i> Информация о покемоне</button>')
          .on('click', function(e){
            e.stopPropagation();
            $.post('/do/lombard.php', { resolve_pokemon: lotId }, function(r){
              try{ if(typeof r==='string') r=JSON.parse(r);}catch(e){}
              var pid = r && r.pokemon_id;
              if (pid && typeof window.openInfoLombard === 'function'){ openInfoLombard(pid); }
            });
          })
          .appendTo($pop.find('.foot'));
      }

      $modal.append($pop); $openPop=$pop;

      var lotR = $lot[0].getBoundingClientRect();
      var modR = $modal[0].getBoundingClientRect();
      var leftPx = (lotR.right - modR.left) + 8;
      var topPx  = (lotR.top   - modR.top)  + 6;

      $pop.css({left:leftPx, top:topPx, display:'block'});
      var W = $pop.outerWidth(), H = $pop.outerHeight();
      var maxL = modR.width  - W - 8;
      var maxT = modR.height - H - 8;
      if (leftPx > maxL) leftPx = Math.max(8, (lotR.left - modR.left) - W - 8);
      if (topPx  > maxT) topPx  = Math.max(8, modR.height - H - 8);
      $pop.css({left:leftPx, top:topPx});

      $pop.on('click', function(e){ e.stopPropagation(); });
      $pop.on('click','.close', function(e){ e.stopPropagation(); closePop(); });

      setTimeout(function(){
        $modal.off('click.pmClose').on('click.pmClose', function(ev){
          if ($(ev.target).closest('.pm-pop').length) return;
          closePop(); $modal.off('click.pmClose');
        });
      },0);
    });
  }

  /* ===================== выравнивание карточек ===================== */
  function flattenLots($grid){
    $grid.find('.lot .lot').each(function(){ $grid.append(this); });
    $grid.children('.lot').each(function(){
      var $lot=$(this), $c=$lot.children();
      if ($c.length===1 && $c.eq(0).is('div') && !$c.eq(0).attr('class')) $lot.html($c.eq(0).html());
    });
  }

  /* ===================== Пагинация (клиент) ===================== */
  var PER_PAGE = 20;
  var allLotsNodes = [];   // DOM-элементы .lot (detached)
  var totalPages = 1;
  var currentPage = 1;

  function buildPager($body){
    var $pager = $body.find('.pm-pager');
    if (!$pager.length) $pager = $('<div class="pm-pager"/>').appendTo($body);

    $pager.empty();
    if (totalPages <= 1) { $pager.hide(); return; }
    $pager.show();

    function pageBtn(p, txt){
      var $b = $('<button class="pm-page"/>').text(txt||p);
      if (p===currentPage) $b.addClass('active');
      $b.on('click', function(){ if (p!==currentPage){ currentPage=p; renderPage(); }});
      return $b;
    }

    var $prev = $('<button class="pm-prev">‹</button>').on('click', function(){ if (currentPage>1){ currentPage--; renderPage(); }});
    var $next = $('<button class="pm-next">›</button>').on('click', function(){ if (currentPage<totalPages){ currentPage++; renderPage(); }});

    $pager.append($prev);

    var maxBtns = 5;
    var start = Math.max(1, currentPage - 2);
    var end   = Math.min(totalPages, start + maxBtns - 1);
    start = Math.max(1, end - maxBtns + 1);

    if (start > 1) { $pager.append(pageBtn(1)); if (start > 2) $pager.append('<span class="pm-ellipsis">…</span>'); }
    for (var p=start; p<=end; p++) $pager.append(pageBtn(p));
    if (end < totalPages) { if (end < totalPages-1) $pager.append('<span class="pm-ellipsis">…</span>'); $pager.append(pageBtn(totalPages)); }

    $pager.append($next);
  }

  function initLotsInGrid($grid){
    $grid.children('.lot').each(function(){
      var $lot=$(this);
      if(!$lot.children('.top').length){
        var $item=$lot.children('.item').first(); if(!$item.length) $item=$lot.find('> .item, .item').first();
        var $name=$lot.children('.name').first(); if(!$name.length) $name=$lot.find('> .name, .name').first();
        var $wrap=$('<div class="top"></div>');
        if($item.length) $item.appendTo($wrap);
        if($name.length) $name.appendTo($wrap);
        $wrap.prependTo($lot);
      }
      ensureAttrs($lot);

      $lot.off('click.pm').on('click.pm', function(e){
        if($(e.target).closest('.buttons,.button,a,.Info-Link').length) return;
        openPop($lot);
      });
      $lot.find('img.pokemons').css('cursor','pointer').off('click.pm').on('click.pm', function(e){
        e.stopPropagation();
        var lotId = $lot.attr('data-lot') || (($lot.find('.lotid').text()||'').replace(/\D/g,'')); if(!lotId) return;
        $.post('/do/lombard.php', { resolve_pokemon: lotId }, function(r){
          try{ if(typeof r==='string') r=JSON.parse(r);}catch(e){}
          var pid = r && r.pokemon_id;
          if (pid && typeof window.openInfoLombard === 'function'){ openInfoLombard(pid); }
        });
      });
    });
  }

  function renderPage(){
    var $body = $('.pmarket-modal .pm-body');
    var $grid = $('#pm-grid');
    $grid.empty();

    var start = (currentPage-1)*PER_PAGE;
    var end = Math.min(start + PER_PAGE, allLotsNodes.length);
    for (var i=start; i<end; i++) $grid.append(allLotsNodes[i]);

    initLotsInGrid($grid);
    buildPager($body);
  }

  /* ===================== загрузка ===================== */
  function skeleton($grid,n){ $grid.empty(); for(var i=0;i<n;i++) $grid.append('<div class="pm-skel"></div>'); }

  function loadLots(opt){
    closePop();
    var $grid = $('#pm-grid'); skeleton($grid, 10);
    var $body = $grid.closest('.pm-body');

    $.post('/do/lombard.php', {
      category: opt.category || 'all',
      q: (opt.q||''),
      seller: (opt.seller||''),
      sort: (opt.sort||'promoted'),
      only_buy: opt.only_buy?1:0,
      admin_only: opt.admin_only?1:0
    }, function(r){
      var html = normalize(r);
      $grid.html(html);
      flattenLots($grid);

      // отделяем все лоты и делаем клиентскую пагинацию
      var $lots = $grid.children('.lot').detach();
      allLotsNodes = $lots.toArray();
      totalPages = Math.max(1, Math.ceil(allLotsNodes.length / PER_PAGE));
      currentPage = 1;

      renderPage(); // покажем первую страницу
    }).fail(function(){
      $grid.html('<div class="pm-sub" style="padding:8px">Ошибка загрузки</div>');
      $body.find('.pm-pager').remove();
    });
  }

  /* ===================== модалка ===================== */
  function saveCat(c){ try{ localStorage.setItem('market_cat', c); }catch(_){ } }
  function loadCat(){ try{ return localStorage.getItem('market_cat') || localStorage.getItem('pk_lombard_cat') || 'all'; }catch(_){ return 'all'; } }

  window.Game = window.Game || {}; Game.modals = Game.modals || {};
  Game.modals.market = function(initialCat){
    var category = initialCat || loadCat() || 'all';
    var $old = $('.pmarket-modal'); if($old.length){ $old.remove(); $(window).off('.pm'); closePop(); }

    var html =
    '<div class="pmarket-modal pmarket" role="dialog" aria-modal="false">'+
      '<div class="pm-head">'+
        '<div class="pm-left"><div class="pm-grip" title="Перетащи"></div><div><div class="pm-ttl">Торговая площадка</div><div class="pm-sub">Современный аукцион</div></div></div>'+
        '<div class="pm-right">'+
          '<label class="pm-search"><i class="far fa-search"></i><input id="pm-q" type="text" placeholder="Лот, предмет, @ник автора…"></label>'+
          '<button id="pm-refresh" class="pm-btn" title="Обновить">↻</button>'+
          '<button class="pm-close pm-btn" aria-label="Закрыть">×</button>'+
        '</div>'+
      '</div>'+
      '<div class="pm-cats" id="pm-cats"></div>'+
      '<div class="pm-bar">'+
        '<select id="pm-sort" class="pm-select">'+
          '<option value="newest">самые новые</option>'+
          '<option value="end_asc">скоро завершающиеся</option>'+
          '<option value="promoted">по популярности</option>'+
          '<option value="price_asc">самые низкие ставки</option>'+
          '<option value="price_desc">самые высокие ставки</option>'+
        '</select>'+
        '<label class="pm-ctrl"><input type="checkbox" id="pm-onlybuy"> только «Выкуп»</label>'+
        '<label class="pm-ctrl"><input type="checkbox" id="pm-onlyadmin"> лоты администрации</label>'+
      '</div>'+
      '<div class="pm-body"><div id="pm-grid"></div><!-- pager появится тут --></div>'+
    '</div>';

    var $el=$(html).appendTo('body');
    place($el); setTimeout(function(){ $el.addClass('show'); },10);
    drag($el,$el.find('.pm-head'));
    $(window).on('resize.pm scroll.pm', function(){ place($el); closePop(); });
    $el.on('click','.pm-close', function(){ $el.remove(); $(window).off('.pm'); closePop(); });

    var CATS=[
      ['all','<i class="far fa-bars"></i> Все'],
      ['pokemon','<i class="far fa-paw"></i> Покемоны'],
      ['modificator','<i class="far fa-arrow-alt-up"></i> Модификаторы'],
      ['egg','<i class="far fa-egg"></i> Яйца'],
      ['evolver','<i class="far fa-dice-d12"></i> Эволверы'],
      ['craft','<i class="far fa-hammer-war"></i> Крафтовые'],
      ['potion','<i class="far fa-flask-potion"></i> Регенераторы'],
      ['ball','<i class="far fa-dot-circle"></i> Покеболы'],
      ['tm','<i class="far fa-album-collection"></i> TM/TR'],
      ['quest','<i class="far fa-ticket"></i> Квестовые'],
      ['other','<i class="far fa-ellipsis-h-alt"></i> Прочее']
    ];
    var $cats=$('#pm-cats'); CATS.forEach(function(c){ $cats.append($('<button/>',{'class':'pm-cat','data-cat':c[0],html:c[1]})); });
    function mark(cat){ $cats.find('.pm-cat').removeClass('active'); $cats.find('.pm-cat[data-cat="'+cat+'"]').addClass('active'); }
    mark(category);

    $cats.on('click','.pm-cat',function(){ category=$(this).data('cat'); mark(category); saveCat(category); apply(); closePop(); });
    $('#pm-refresh').on('click',function(){ apply(); closePop(); });
    $('#pm-q').on('input',debounce(apply,200));
    $('#pm-sort,#pm-onlybuy,#pm-onlyadmin').on('change',apply);

    function parseSearch(){
      var raw = ($('#pm-q').val()||'').trim();
      if (raw.startsWith('@')) return { q:'', seller: raw.slice(1) };
      var m = raw.match(/^seller\s*:\s*(.+)$/i);
      if (m) return { q:'', seller: m[1] };
      return { q: raw, seller: '' };
    }
    function apply(){
      var p = parseSearch();
      loadLots({
        category: category,
        q: p.q,
        seller: p.seller,
        sort: $('#pm-sort').val(),
        only_buy: $('#pm-onlybuy').is(':checked'),
        admin_only: $('#pm-onlyadmin').is(':checked')
      });
    }
    apply();
  };
})(jQuery);


function snow_ball(id){
    var op = $('.Snow_ball#id'+id).css('opacity');
    var sl = op-0.25;

					$('.Snow_ball#id'+id).css('opacity',sl);
    if(sl == 0){
        $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "snow_ball="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.Snow_ball#id'+id).remove();
          			

          			if(response['html']){
          			    Game.notifications.main(response['html'],response['error']);
					    Game.notifications.main(response['plus'],'plus');
					}
        		}
      });
    }

}
function hell_candy(id){
    var op = $('.Hell_candy').css('opacity');
    var sl = op-0.25;
    $('.Hell_candy').css('opacity',sl);
    if(sl == 0){
        $.ajax({
				url: "/do/pp",
				type: "POST",
                data: "hell_candy="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.Hell_candy').remove();
          			Game.notifications.main(response['html'],response['error']);

          			if(response['plus']){
					    Game.notifications.main(response['plus'],'plus');
					}
        		}
      });
    }

}
function hell_team(id){

        $.ajax({
				url: "/do/pp",
				type: "POST",
                data: "hell_team="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.Hell_team').remove();
          			Game.notifications.main(response['html'],response['error']);

          			if(response['minus']){
					    Game.notifications.main(response['minus'],'minus');
					}
        		}
      });

}

/* =========================
   Универсальная модалка подтверждения (в твоём стиле)
   ========================= */
(function ($) {
  if (window.__pkConfirmInstalled) return;
  window.__pkConfirmInstalled = true;

  // ---- CSS один раз ----
  var css = `
  .pkc-backdrop{position:fixed;inset:0;background:rgba(12,18,32,.35);backdrop-filter:saturate(100%) blur(1px);
    z-index:20000010;display:flex;align-items:center;justify-content:center;padding:16px}
  .pkc-window{width:420px;max-width:94vw;background:#fff;border:1px solid #e6eafe;border-radius:16px;
    box-shadow:0 18px 42px rgba(23,35,74,.18);font-family:Nunito,Inter,Arial,sans-serif;color:#1b2b4f}
  .pkc-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #eef2ff;
    background:linear-gradient(180deg,#ffffff,#f6f8ff);border-radius:16px 16px 0 0}
  .pkc-title{font-weight:600;font-size:15px}
  .pkc-close{appearance:none;border:1px solid #e6eafe;background:#fff;width:28px;height:28px;border-radius:8px;
    cursor:pointer;line-height:26px;font-size:16px;color:#6f7b95}
  .pkc-close:hover{box-shadow:0 6px 18px rgba(23,35,74,.12)}
  .pkc-body{padding:14px 14px 6px 14px;font-size:14px;color:#2b3856}
  .pkc-hint{color:#6f7b95;font-size:12px;margin-top:6px}
  .pkc-actions{display:flex;gap:8px;justify-content:flex-end;padding:12px 14px 14px 14px}
  .pkc-btn{appearance:none;border:1px solid #dfe6ff;background:#fff;color:#223456;border-radius:10px;
    padding:8px 12px;font-weight:700;cursor:pointer}
  .pkc-btn:hover{border-color:#a9c1ff}
  .pkc-btn.primary{background:#2f74ff;border-color:#2f74ff;color:#fff}
  .pkc-btn[disabled]{opacity:.65;cursor:default}
  .pkc-danger .pkc-btn.primary{background:#d73b3b;border-color:#d73b3b}
  @media (max-width:560px){
    .pkc-window{width:96vw}
  }`;
  if (!document.getElementById('pkc-css')) {
    $('<style id="pkc-css"/>').text(css).appendTo('head');
  }

  // ---- Вспомогательное форматирование чисел ----
  function fmt(n) {
    n = Number(n || 0);
    return n.toLocaleString('ru-RU');
  }

  // ---- Модалка подтверждения ----
  window.pkConfirm = function (opts) {
    opts = opts || {};
    var title = opts.title || 'Подтвердите действие';
    var html = opts.html || (opts.message || '');
    var okText = opts.okText || 'Ок';
    var cancelText = opts.cancelText || 'Отмена';
    var danger = !!opts.danger; // красная основная кнопка

    // каркас
    var $wrap = $('<div class="pkc-backdrop" />');
    var $win = $('<div class="pkc-window' + (danger ? ' pkc-danger' : '') + '"/>').appendTo($wrap);
    var $head = $('<div class="pkc-head"/>').appendTo($win);
    $('<div class="pkc-title"/>').text(title).appendTo($head);
    var $btnClose = $('<button class="pkc-close" aria-label="Закрыть">×</button>').appendTo($head);
    var $body = $('<div class="pkc-body"/>').appendTo($win);
    $body.append($('<div/>').html(html));
    if (opts.hint) $('<div class="pkc-hint"/>').text(opts.hint).appendTo($body);
    var $act = $('<div class="pkc-actions"/>').appendTo($win);
    var $cancel = $('<button class="pkc-btn"/>').text(cancelText).appendTo($act);
    var $ok = $('<button class="pkc-btn primary"/>').text(okText).appendTo($act);

    $('body').append($wrap);

    // закр. функции
    var resolveFn, resolved = false, pending = false;
    function close(ret) {
      if (resolved) return;
      resolved = true;
      $(document).off('keydown.pkc esc');
      $wrap.remove();
      resolveFn(!!ret);
    }
    function setPending(v) {
      pending = v;
      $ok.prop('disabled', v).html(v ? '<i class="fas fa-spinner fa-spin"></i>' : okText);
      $cancel.prop('disabled', v);
      $btnClose.prop('disabled', v);
    }

    // события
    $btnClose.on('click', function () { if (!pending) close(false); });
    $cancel.on('click', function () { if (!pending) close(false); });
    $ok.on('click', function () {
      if (pending) return;
      if (typeof opts.onBeforeConfirm === 'function') {
        // если нужно – асинхронный хук
        var res = opts.onBeforeConfirm(setPending);
        if (res === false) return; // отменили
      }
      close(true);
    });
    // клик по фону
    $wrap.on('click', function (e) { if (e.target === this && !pending) close(false); });
    // esc
    $(document).on('keydown.pkc', function (e) {
      if (e.key === 'Escape' && !pending) close(false);
    });

    // фокус
    setTimeout(function () { $ok.trigger('focus'); }, 0);

    return new Promise(function (resolve) { resolveFn = resolve; });
  };

  // ====== Обновлённые функции ставок / выкупа ======

  // Ставка
  window.lotstavka = function (id) {
    var stavk = $('#countStavkInput').val();
    var sumTxt = (stavk ? (' на сумму <b>' + fmt(stavk) + ' м.</b>') : '');
    pkConfirm({
      title: 'Подтверждение ставки',
      html: 'Вы уверены, что хотите сделать ставку' + sumTxt + ' на лот <b>#' + id + '</b>?',
      okText: 'Сделать ставку',
      cancelText: 'Отмена'
    }).then(function (ok) {
      if (!ok) return;

      $.ajax({
        url: '/do/pp',
        type: 'POST',
        dataType: 'json',
        data: { lot: id, stav: stavk },
        success: function (response) {
          // safety: если сервер вернул строку
          try { if (typeof response === 'string') response = (typeof response === 'string') ? JSON.parse(response) : response; } catch (e) {}
          Game.notifications.main(response['html'], response['info']);
          if (response['minus']) {
            Game.notifications.main(response['minus'], 'minus');
          }
          if (response['info'] === 'success') {
            $('.tooltip').hide();
            if (Game.modals && Game.modals.lombard) Game.modals.lombard('all');
          }
        },
        error: function (xhr) {
          var msg = 'Ошибка запроса';
          if (xhr && xhr.responseText) msg += ': ' + xhr.responseText.substring(0, 200);
          Game.notifications.main(msg, 'error');
        }
      });
    });
  };

  // Выкуп
  window.vikup = function (id) {
    pkConfirm({
      title: 'Подтверждение выкупа',
      html: 'Вы уверены, что хотите выкупить лот <b>#' + id + '</b>? С вашего счёта будет списана полная стоимость выкупа.',
      okText: 'Выкупить лот',
      cancelText: 'Отмена',
      danger: false
    }).then(function (ok) {
      if (!ok) return;

      $.ajax({
        url: '/do/pp',
        type: 'POST',
        dataType: 'json',
        data: { vikup: id },
        success: function (response) {
          try { if (typeof response === 'string') response = (typeof response === 'string') ? JSON.parse(response) : response; } catch (e) {}
          Game.notifications.main(response['html'], response['info']);
          if (response['minus']) {
            Game.notifications.main(response['minus'], 'minus');
          }
          if (response['info'] === 'success') {
            if (Game.modals && Game.modals.lombard) Game.modals.lombard('all');
          }
        },
        error: function (xhr) {
          var msg = 'Ошибка запроса';
          if (xhr && xhr.responseText) msg += ': ' + xhr.responseText.substring(0, 200);
          Game.notifications.main(msg, 'error');
        }
      });
    });
  };

})(jQuery);


function addLotItem(id){
    var prCo = $('#lot_count').val();
    var prSt = $('#lot_priceStart').val();
    var prHd = $('#lot_priceHod').val();
    var prDa = $('#lot_day').val();
    var prBu = $('#lot_priceBuy').val();
    var prPr = $('#lot_prodv').val();
        $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "lotitem="+id+"&count="+prCo+"&start="+prSt+"&hod="+prHd+"&day="+prDa+"&buy="+prBu+"&prodv="+prPr,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
          			Game.notifications.main(response['html'],response['info']);
					if(response['minus']){
					    Game.notifications.main(response['minus'],'minus');
					}
					if(response['info'] == "success"){
					    $('.tooltip').hide();
					    Game.modals.inventory('all');
					}
        		}
      });

}
function addLotPokemon(id){
    var prSt = $('#lot_priceStart').val();
    var prHd = $('#lot_priceHod').val();
    var prDa = $('#lot_day').val();
    var prBu = $('#lot_priceBuy').val();
    var prPr = $('#lot_prodv').val();
        $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "lotpok="+id+"&start="+prSt+"&hod="+prHd+"&day="+prDa+"&buy="+prBu+"&prodv="+prPr,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
          			Game.notifications.main(response['html'],response['info']);
					if(response['minus']){
					    Game.notifications.main(response['minus'],'minus');
					}
					if(response['info'] == "success"){
					    $('.tooltip').hide();
					    Game.modals.pokemons();
					}
        		}
      });

}





function water_ball_drop(id){
    $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "water_ball_drop="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
          			Game.notifications.main(response['html'],response['error']);
          			if(response['plus']){
					    Game.notifications.main(response['plus'],'plus');
					}
					if(response['minus']){
					    Game.notifications.main(response['minus'],'minus');
					}
        		}
      });
}
function snow_ball_drop(id){
    $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "snow_ball_drop="+id,
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
          			Game.notifications.main(response['html'],response['error']);
          			if(response['plus']){
					    Game.notifications.main(response['plus'],'plus');
					}
					if(response['minus']){
					    Game.notifications.main(response['minus'],'minus');
					}
        		}
      });
}
function GiftOnline(){
    $.ajax({
				url: "/do/pp",
				type: "POST",

            data: "GiftOnline=1",
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.GiftOnline').remove();
          			Game.notifications.main(response['plus'],'plus');
        		}
      });
}
function GiftBirthday(){
    $.ajax({
				url: "/do/pp",
				type: "POST",
            data: "Birthday=1",
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					$('.GiftOnline').remove();
					Game.notifications.main(response['text'],response['error']);
					if(response['plus']){
					    Game.notifications.main(response['plus'],'plus');
					}

        		}
      });
}
function recover(){
    $.ajax({
				url: "/do/pp",
				type: "POST",
            data: "recover=1",
				success: function (response){
					response = (typeof response === 'string') ? JSON.parse(response) : response;
					Game.notifications.main(response['html'],response['error']);
					if(response['minus']){
					    Game.notifications.main(response['minus'],'minus');
					}

        		}
      });
}
// Новый Боевой Пропуск: Полный JS с обработкой ошибок, загрузкой, AJAX и всеми категориями
// Гибкая обработка любых неполадок на сервере (в том числе если приходит не JSON).

function safeParseJSON(data) {
    try {
        if (typeof data === "object") return data;
        return JSON.parse(data);
    } catch (e) {
        // Показываем ошибку для отладки, можно убрать в релизе
        console.error("Ошибка разбора JSON:", e, data);
        Game.notifications.main("Ошибка соединения с сервером. Попробуйте позже.", "error");
        return { error: "Ошибка соединения или форматирования ответа." };
    }
}

function showLoader(show) {
    // Если нужен loader — раскомментируйте и реализуйте под свой UI
    // if (show) $("#battlepass-loader").show();
    // else $("#battlepass-loader").hide();
}

// ===== Активация бесплатного Battle Pass
function battlepassactivated() {
    showLoader(true);
    $.ajax({
        url: "/do/battlepass",
        type: "POST",
        data: { type: "activatedfree" },
        success: function (data) {
            showLoader(false);
            let response = safeParseJSON(data);
            if (!response.error) {
                openModal('battlepass');
                Game.notifications.main(response.html, 'success');
            } else {
                Game.notifications.main(response.error, 'error');
            }
        },
        error: function () {
            showLoader(false);
            Game.notifications.main("Ошибка соединения с сервером.", "error");
        }
    });
}

// ===== Смена задания (за генкары)
function battlepasswitch(id) {
    if (confirm("Вы уверены, что хотите сменить задание? Это займет 4 часа и 30.000 генкар!")) {
        showLoader(true);
        $.ajax({
            url: "/do/battlepass",
            type: "POST",
            data: { type: "switch", id: id },
            success: function (data) {
                showLoader(false);
                let response = safeParseJSON(data);
                if (!response.error) {
                    openModal('battlepass');
                    Game.notifications.main(response.html, 'success');
                    if (response.minus) Game.notifications.main(response.minus, 'minus');
                } else {
                    Game.notifications.main(response.error, 'error');
                }
            },
            error: function () {
                showLoader(false);
                Game.notifications.main("Ошибка соединения с сервером.", "error");
            }
        });
    }
}

// ===== Получение нового задания после смены
function battlepasswitchactiv(id) {
    showLoader(true);
    $.ajax({
        url: "/do/battlepass",
        type: "POST",
        data: { type: "switchactiv", id: id },
        success: function (data) {
            showLoader(false);
            let response = safeParseJSON(data);
            if (!response.error) {
                openModal('battlepass');
                Game.notifications.main(response.html, 'success');
            } else {
                Game.notifications.main(response.error, 'error');
            }
        },
        error: function () {
            showLoader(false);
            Game.notifications.main("Ошибка соединения с сервером.", "error");
        }
    });
}

// ===== Получение приза за уровень (лента наград)
function battlepass_giveprize(id) {
    showLoader(true);
    $.ajax({
        url: "/do/battlepass",
        type: "POST",
        data: { type: "giveprize", id: id },
        success: function (data) {
            showLoader(false);
            let response = safeParseJSON(data);
            if (!response.error) {
                passCategory('track');
                Game.notifications.main(response.html, 'success');
                if (response.plus) Game.notifications.main(response.plus, 'plus');
            } else {
                Game.notifications.main(response.error, 'error');
            }
        },
        error: function () {
            showLoader(false);
            Game.notifications.main("Ошибка соединения с сервером.", "error");
        }
    });
}

// ===== Покупка в магазине жетонов
function battlepass_emblemshop(id) {
    showLoader(true);
    $.ajax({
        url: "/do/battlepass",
        type: "POST",
        data: { type: "emblemshop", id: id },
        success: function (data) {
            showLoader(false);
            let response = safeParseJSON(data);
            if (!response.error) {
                passCategory('shop');
                Game.notifications.main(response.html, 'success');
                if (response.plus) Game.notifications.main(response.plus, 'plus');
            } else {
                Game.notifications.main(response.error, 'error');
            }
        },
        error: function () {
            showLoader(false);
            Game.notifications.main("Ошибка соединения с сервером.", "error");
        }
    });
}

// ===== Покупка полной версии Battle Pass
function activatedfull() {
    if (confirm("Вы уверены, что хотите купить полную версию?")) {
        showLoader(true);
        $.ajax({
            url: "/do/battlepass",
            type: "POST",
            data: { type: "activatedfull" },
            success: function (data) {
                showLoader(false);
                let response = safeParseJSON(data);
                if (!response.error) {
                    passCategory('track');
                    Game.notifications.main(response.html, 'success');
                    if (response.minus) Game.notifications.main(response.minus, 'minus');
                    // Добавить кнопку "Эмблема", если еще нет
                    if ($('.bp-newCategory .emblem').length === 0) {
                        $("<div/>", {
                            "class": "bpNewBtn emblem",
                            "html": '<i class="fab fa-empire"></i>',
                            "click": function () {
                                passCategory('shop');
                            }
                        }).appendTo(".bp-newCategory");
                    }
                } else {
                    Game.notifications.main(response.error, 'error');
                }
            },
            error: function () {
                showLoader(false);
                Game.notifications.main("Ошибка соединения с сервером.", "error");
            }
        });
    }
}
function openBattlePassNpc() {
    $.post('/do/Npc/55.php', {step:0}, function(resp){
        showModalNpc(resp.question, resp.answer);
    }, 'json');
}
function npcBattlePassChooseMission(id) {
    $.post('/do/Npc/55.php', {step:1, mission_id:id}, function(resp){
        showModalNpc(resp.question);
    }, 'json');
}
function npcBattlePassSubmit(mission_id, selected_id) {
    $.post('/do/Npc/55.php', {step:2, mission_id:mission_id, selected_id:selected_id}, function(resp){
        showModalNpc(resp.question);
        // можно обновить BP-интерфейс после сдачи!
    }, 'json');
}
// ====== Переключение вкладок Battle Pass (track, mission, info, shop, stat, history)
function passCategory(type) {
    $('.bp-newCategory .bpNewBtn').removeClass('active');
    $('.bp-newCategory .' + type).addClass('active');
    $.ajax({
        url: "/do/battlepass",
        type: "POST",
        data: { type: "category", category: type },
        success: function (data) {
            let response = typeof data === 'object' ? data : JSON.parse(data);
            if (!response.error) {
                $('.bp-newContent').html(response.html);
            } else {
                Game.notifications.main(response.error, 'error');
            }
        }
    });
}



function openModal(type, id) {
    

    // Удаляем существующие модальные окна
    const modal = $('.Modal');
    $('.BlockOtherContent').hide();
    modal.remove();

    // AJAX-запрос на получение содержимого модального окна
    $.ajax({
        url: "/do/modal",
        type: "POST",
        data: { type: type, pokID: id }, // Используем объект данных для повышения читаемости
        beforeSend: function () {
            // Удаляем старые модальные окна
            $('.Modal').remove();

            // Создаем новое модальное окно
            $("<div/>", { class: "Modal" }).appendTo("body");

            // Добавляем индикатор загрузки
            $('<div/>', {
                class: 'modalLoad',
                html: `<center>${mainLoader}</center>`
            }).appendTo('.Modal');
        },
        success: function (response) {
            try {
                // Парсим JSON-ответ
                response = (typeof response === 'string') ? JSON.parse(response) : response;

                // Вычисляем позицию модального окна
                const offset = $('.MidMenu').offset();
                const left = Math.round(offset.left);
                let leftA;

                if (type === 'trainers') {
                    leftA = left + 300;
                } else if (['craft', 'discovery', 'work'].includes(type)) {
                    leftA = left + 358;
                } else {
                    leftA = left + 200; // Значение по умолчанию
                }

                // Устанавливаем позицию модального окна
                $('.Modal').css('left', `${leftA}px`);

                // Добавляем содержимое модального окна
                $('.Modal').append(response.html);

                // Дополнительная логика для покемонов
                if (type === 'pokemons') {
                    goPok(id);
                }

                // Скрываем индикатор загрузки
                $('.modalLoad').hide();
            } catch (error) {
                console.error("Ошибка при обработке ответа:", error);
                $('.Modal').html('<p>Произошла ошибка. Попробуйте позже.</p>');
            }
        },
        error: function (xhr, status, error) {
            console.error("Ошибка AJAX-запроса:", status, error);
            $('.Modal').html('<p>Не удалось загрузить данные. Попробуйте позже.</p>');
        }
    });
}

function mission(id){
            $.ajax({
				url: "/do/mission",
				type: "POST",
				data: "id="+id,
				success: function (response){
				    response = (typeof response === 'string') ? JSON.parse(response) : response;
				    $('#mission'+id).html(response['text']);
				    $('#mission'+id).toggle();
				    
				    
				}
			});
}
// ИНИЦИАЛИЗАЦИЯ (по желанию): подтянуть текущую заметку и отрисовать предпросмотр
async function initNotes() {
  const ta = document.querySelector('#notesUsers');
  if (!ta) return;

  try {
    const res  = await fetch('/do/notes', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      credentials: 'same-origin'
    });
    const data = await res.json().catch(() => ({}));

    if (typeof data.value === 'string') ta.value = data.value;
    if (data.html && document.querySelector('#notesPreview')) {
      document.querySelector('#notesPreview').innerHTML = data.html;
    }
    if (typeof data.length === 'number' && document.querySelector('#notesCount')) {
      document.querySelector('#notesCount').textContent = data.length;
    }
  } catch (e) {
    // тихая инициализация — без тостов
    console.warn('initNotes:', e);
  }
}

// ОСНОВНАЯ ФУНКЦИЯ СОХРАНЕНИЯ
async function notesUsers(el) {
  const ta  = document.querySelector('#notesUsers') || (window.$ ? $('#notesUsers')[0] : null);
  if (!ta) return;

  const val = ta.value.replace(/\r\n?/g, '\n'); // нормализация переносов

  // Кнопка-лоадер (если есть)
  const btn = el instanceof Element ? el : document.querySelector('#notesSave');
  if (btn) {
    btn.disabled = true;
    btn.classList.add('is-loading');
  }

  // основная ветка — современный fetch
  if (window.fetch) {
    try {
      const body = new URLSearchParams({ val });
      const res  = await fetch('/do/notes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body
      });

      // пробуем json, если нет — парсим текст
      let data;
      try { data = await res.json(); } catch (_) { data = JSON.parse(await res.text()); }

      if (window.Game?.notifications?.main) {
        Game.notifications.main(data.text || 'Готово', data.error || 'success');
      }

      // обновим предпросмотр/счётчик, если узлы существуют
      if (data.html && document.querySelector('#notesPreview')) {
        document.querySelector('#notesPreview').innerHTML = data.html;
      }
      if (typeof data.length === 'number' && document.querySelector('#notesCount')) {
        document.querySelector('#notesCount').textContent = data.length;
      }
    } catch (err) {
      console.error(err);
      if (window.Game?.notifications?.main) {
        Game.notifications.main('Не удалось сохранить заметку', 'error');
      }
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.classList.remove('is-loading');
      }
    }
    return;
  }

  // ФОЛЛБЕК ДЛЯ jQuery (если fetch недоступен)
  if (window.$ && $.ajax) {
    $.ajax({
      url: '/do/notes',
      type: 'POST',
      data: $.param({ val }),
      success: function (response) {
        let data = {};
        try { data = JSON.parse(response); } catch (e) {}

        if (window.Game?.notifications?.main) {
          Game.notifications.main(data.text || 'Готово', data.error || 'success');
        }
        if (data.html && document.querySelector('#notesPreview')) {
          document.querySelector('#notesPreview').innerHTML = data.html;
        }
        if (typeof data.length === 'number' && document.querySelector('#notesCount')) {
          document.querySelector('#notesCount').textContent = data.length;
        }
      },
      error: function () {
        if (window.Game?.notifications?.main) {
          Game.notifications.main('Не удалось сохранить заметку', 'error');
        }
      },
      complete: function () {
        if (btn) {
          btn.disabled = false;
          btn.classList.remove('is-loading');
        }
      }
    });
  }
}

// УДОБНОЕ АВТОСОХРАНЕНИЕ С ДЕБАУНСОМ (опционально)
(function bindNotesAutosave(){
  const ta = document.querySelector('#notesUsers');
  if (!ta) return;
  let t = null;
  ta.addEventListener('input', function() {
    const counter = document.querySelector('#notesCount');
    if (counter) counter.textContent = this.value.length;
    clearTimeout(t);
    t = setTimeout(() => notesUsers(), 600); // автосейв через 600мс после последнего ввода
  });
})();

function editInfo(type, other = false){
	switch(type){
		case 'pass':
			var oldPass = $('#oldPass').val(),
				newPass = $('#newPass').val(),
				dblNewPass = $('#dblNewPass').val();
			if(oldPass == newPass){
				return Game.notifications.main(Lang.error_password_is_used, "error");
			}else if(newPass !== dblNewPass){
        return Game.notifications.main(Lang.error_password_do_not_match, "error");
			}else if($('#oldPass').length == 0 || $('#dblNewPass').length == 0 || $('#newPass').length == 0){
        return Game.notifications.main('Поля не должны оставаться пустыми', "error");
			}else{
				$.post('/do/edit', {type: type, pass: oldPass, newPass: newPass, dblNewPass: dblNewPass}, function(data){
					if(data.error == 1){
						return Game.notifications.main(data.text);
					}else{
						$('#oldPass').val('');
						$('#newPass').val('');
						$('#dblNewPass').val('');
						Game.notifications.main(Lang.success_password_changed, "success");
					}
				}, 'json')
			}
			break;
		case 'closeNotifyAdmin':
			$.post('/do/edit', {type: type, idNotify: other}, function(data){
				$('.notya-'+other+'').remove();
			}, 'json')
			break;
      case 'editSound':
  			$.post('/do/edit', {type: type, set: other}, function(data){
  				UserAudio = other;
  				settings();
  			}, 'json')
  			break;
        case 'editColor':
    			$.post('/do/edit', {type: type, set: other}, function(data){
    				Game.notifications.main('Цвет успешно изменен.', "success");
    				settings();
    			}, 'json')
    			break;
          case 'editTeam':
      			$.post('/do/edit', {type: type, set: other}, function(data){
      				Game.notifications.main('Изменения прошли успешно.', "success");
      				settings();
      			}, 'json')
      			break;
      			case 'editAttackLang':
    $.post('/do/edit', {type: type, set: other}, function(data){
        Game.notifications.main(
            (data.attack_lang === "eng" ? "Attack language switched to English." : "Язык атак переключён на Русский."),
            "success"
        );
        settings();
    }, 'json');
    break;
		case 'editSprite':
			$.post('/do/edit', {type: type, set: other}, function(data){
				UserAudio = other;
				settings();
			}, 'json')
			break;
	}

}
function editLang(lang){
	$.ajax({
		url: "/do/edit",
		type: "POST",
		data: "type="+lang,
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			Game.notifications.main(Lang.success_edit_lang, 'success');
		}
	});
}
function invaitehide(){
    $('.invaiteModal').hide();
}
function itemOpen(
  e, typeM, item_id, name, description, id, count, weight, use, dress, drop, trade, give,
  type = false, status = false, egg, npc, typeIt, dop, basenum, lombard, itemType = false
){
  // ---------- helpers ----------
  function esc(s){
    s = (s == null ? '' : String(s));
    return s.replace(/[&<>"'`]/g, m =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'}[m])
    );
  }
  function isTrue(v){ return v === true || v === 'true' || v === 1 || v === '1'; }

  // ---------- tooltip element ----------
  var tlp = $('.tooltip');
  if (!tlp.length) tlp = $('<div class="tooltip"></div>').appendTo('body');

  // ---------- старое позиционирование + коррекция по краям ----------
  var ev = (e && typeof e.clientX === 'number') ? e : (window.event || {});
  var $target = $(ev.target || e.target || this);

  // старт — «как раньше»
  var left = (ev.clientX || 0) - 134;
  var top  = (ev.clientY || 0) + 15;
  if (left < 0) left = 0;

  tlp.css({ left: left + 'px', top: top + 'px' });
  tlp.html('<center>' + (typeof mainLoader !== 'undefined' ? mainLoader : '') + '</center>');

  // ---------- контент ----------
  var rawCount = $target.find('.Count').text();
  var cntAvail = parseInt(rawCount || count, 10);
  if (!Number.isFinite(cntAvail) || cntAvail < 1) cntAvail = 1;
  if (egg) dop = "";

  var html = '';
  html += '<div class="Name" id="drgItem">'+ esc(name) + (dop ? ' '+esc(dop) : '') +' <b>x'+ cntAvail +'</b></div>';
  html += '<div class="Image"><img id="imgItem" src="/img/world/items/little/'+ esc(id) +'.png"></div>';
  html += '<div class="About">'+ esc(description) +'<br><span class="id_item_ab">id предмета: '+ esc(id) +'</span></div>';

  var $btns = $('<div class="Buttons"></div>');

  // инпут количества (важно для трейда)
  var needInput = (isTrue(drop) || isTrue(dress) || isTrue(give) || egg);
  if (needInput){
    $btns.append($('<input/>', {
      id: 'countItemInput',
      type: 'number',
      value: 1,
      min: 1,
      max: cntAvail,
      placeholder: (window.Lang && Lang.text_count) ? Lang.text_count : 'Количество',
      class: 'countItemInput'
    }));
  }

  // кнопки действий
  if (isTrue(give)) {
    $btns.append(
      '<div onclick=\'itemAction('+ id +',"give","'+ esc(name) +'","'+ esc(typeM) +'");\'>' +
      (window.Lang ? Lang.button_item_pokemon : 'Передать покемону') +
      '</div>'
    );
  }
  if (isTrue(dress)) {
    $btns.append(
      $('<div/>',{ html: (window.Lang ? Lang.button_item_dress : 'Надеть') })
        .on('click', function(){ itemAction(item_id, 'dress', name, ''+typeM+''); })
    );
  }
  if (isTrue(use)) {
    $btns.append(
      '<div onclick=itemAction('+ id +',"use",false,"'+ esc(typeM) +'");>' +
      (window.Lang ? Lang.button_item_use : 'Использовать') +
      '</div>'
    );
  }

  // билеты (если используется)
  var planeTicketIds = [600, 601];
  if (planeTicketIds.indexOf(Number(id)) !== -1){
    $btns.append(
      $('<div/>',{ html:'Использовать билет', class:'plane-ticket-btn' })
        .on('click', function(){ if (typeof usePlaneTicket === 'function') usePlaneTicket(item_id, 1); })
    );
  }

  // скины (если используется)
  if (itemType === 'skin' || typeIt === 'skin' || type === 'skin') {
    $btns.append(
      $('<div/>',{ html:'Применить скин', class:'skin-apply-btn' })
        .on('click', function(){ itemAction(item_id, 'UseSkin', false, ''+typeM+''); })
    );
    $btns.append(
      $('<button/>',{ html:'Предпросмотр', class:'skin-apply-btn skin-preview-btn','data-item':item_id })
        .on('click', function(){ if (typeof previewSkinInv === 'function') previewSkinInv(this); })
    );
  }

  // NPC/карточки/ломбард
  if (!egg && npc > 0) {
    $btns.append('<div onclick=itemAction('+ item_id +',"giveEgg",true);>'+ (window.Lang ? Lang.button_item_npc_give : 'Отдать NPC') +'</div>');
  }
  if (typeIt === 'card') {
    $btns.append('<div onclick=itemAction('+ item_id +',"card",true,"'+ esc(typeM) +'");>Отправить в коллекцию</div>');
  }
  if (isTrue(lombard)) {
  $btns.append(
    $('<div class="lombard">Выставить на аукцион</div>')
      .on('click', function(){
        // передаём id, имя и доступное количество
        if (typeof openItemLotDialog === 'function') openItemLotDialog(item_id, name, cntAvail);
        else if (typeof issetAll === 'function') issetAll(item_id, 'item_lot'); // фолбэк
      })
  );
}

  // удаление / яйцо
  if (isTrue(drop) && !window.isTrade) {
    $btns.append('<div onclick=itemAction('+ item_id +',"drop",false,"'+ esc(typeM) +'"); class="drop">'+ (window.Lang ? Lang.button_drop : 'Выбросить') +'</div>');
  } else if (egg) {
    $btns.append('<div onclick=itemAction('+ item_id +',"incubEgg",false,"'+ esc(typeM) +'");>Использовать инкубатор</div>');
    $btns.append('<div onclick=openDex('+ basenum +');>Открыть покедекс</div>');
    if (npc > 0) $btns.append('<div onclick=itemAction('+ item_id +',"giveEgg");>'+ (window.Lang ? Lang.button_item_npc_give : 'Отдать NPC') +'</div>');
    $btns.append('<div onclick=itemAction('+ item_id +',"dropEgg",false,"'+ esc(typeM) +'"); class="drop">'+ (window.Lang ? Lang.button_drop_egg : 'Выбросить яйцо') +'</div>');
  }

  // добавить в обмен: совместимость с _addObject/_addobject + защита от дабл-кликов
  if (isTrue(trade) && status == 'trade' && window.ClassTrade){
    var $btnTrade = $('<div/>',{ html: (window.Lang ? Lang.button_trade_add : 'Добавить в обмен') });
    $btnTrade.on('click', function(){
      if ($btnTrade.data('busy')) return;
      $btnTrade.data('busy', true).addClass('disabled');

      if (window.Game && Game.modals && typeof Game.modals.inventory === 'function') {
        Game.modals.inventory('all');
      }
      var qty = parseInt($('.tooltip .countItemInput').val(), 10) || 1;
      if (qty < 1) qty = 1;
      if (qty > cntAvail) qty = cntAvail;

      var api = ClassTrade._addObject || ClassTrade._addobject;
      if (!api){
        if (window.Game && Game.notifications) Game.notifications.main('Функция обмена недоступна','error');
        $btnTrade.data('busy', false).removeClass('disabled');
        return;
      }

      api.call(ClassTrade, (egg ? 'egg' : 'item'), item_id, qty,
        // success
        function(){
          if ($target && $target.length){
            var eCount = parseInt($target.find('span').text(), 10);
            if (Number.isFinite(eCount) && eCount > 0){
              eCount -= qty;
              if (eCount > 0){
                $target.find('span').text(eCount);
              } else {
                $target.remove();
              }
            }
          }
        },
        // complete
        function(){
          tlp.hide();
          $btnTrade.data('busy', false).removeClass('disabled');
        }
      );
    });
    $btns.append($btnTrade);
  }

  // отрисовка
  tlp.html(html).append($btns).show();

  // коррекция, если вылезли за правый/нижний край (сохраняем «старое» позиционирование как базу)
  try {
    var ww = $(window).width(), wh = $(window).height();
    var tw = tlp.outerWidth(), th = tlp.outerHeight();
    var newLeft = Math.max(0, Math.min(left, ww - tw - 4));
    var newTop  = Math.max(0, Math.min(top,  wh - th - 4));
    if (newLeft !== left || newTop !== top) tlp.css({ left: newLeft+'px', top: newTop+'px' });
  } catch(_){}

  // drag по заголовку — только если библиотека есть
  try { $('.tooltip').draggabilly({ handle: '#drgItem', containment: 'window', scroll: false }); } catch(_){}

  // закрытие по ESC/клик-вне
  $(document)
    .off('keydown.itemTooltip')
    .on('keydown.itemTooltip', function(ev){
      if ((ev.key || ev.code) === 'Escape'){
        tlp.hide();
        $(document).off('keydown.itemTooltip mousedown.itemTooltip');
      }
    });
  $(document)
    .off('mousedown.itemTooltip')
    .on('mousedown.itemTooltip', function(ev2){
      if (!$(ev2.target).closest('.tooltip').length){
        tlp.hide();
        $(document).off('keydown.itemTooltip mousedown.itemTooltip');
      }
    });
}
// ---------- LOTX core (если уже есть — этот блок просто пропустится) ----------
(function ensureLotxCore(){
  if (!document.getElementById('lotx-css')) {
    const css = `
    :root{--pkx-card:#fff;--pkx-br:#e6eafe;--pkx-t:#1b2b4f;--pkx-sub:#6f7b95;--pkx-blue:#2f74ff;--pkx-blue-2:#0e55b6;--pkx-shadow:0 16px 32px rgba(20,35,80,.12)}
    .lotx-card{position:absolute;z-index:1000003;width:360px;max-width:96vw;background:var(--pkx-card);border:1px solid var(--pkx-br);border-radius:10px;box-shadow:var(--pkx-shadow);color:var(--pkx-t);transform:translateY(-3px) scale(.985);opacity:0;transition:opacity .14s ease,transform .14s ease;font-family:Nunito,Inter,Arial,sans-serif}
    .lotx-card.show{transform:translateY(0) scale(1);opacity:1}
    .lotx-card.lotx--drag{cursor:grabbing;user-select:none}
    .lotx-head{cursor:grab;display:flex;align-items:center;justify-content:space-between;padding:6px 8px;background:linear-gradient(180deg,#f9fbff,#f5f7ff);border-bottom:1px solid var(--pkx-br)}
    .lotx-title{font-weight:900;font-size:13.5px;color:#21345e;line-height:1.1}
    .lotx-sub{font-weight:800;font-size:12.5px;color:var(--pkx-sub);margin-top:1px}
    .lotx-close{appearance:none;border:0;background:transparent;cursor:pointer;color:#9aa6bd;padding:2px 6px;font-size:18px}
    .lotx-close:hover{color:#5c6b8a}
    .lotx-body{padding:8px}
    .lotx-grid{display:flex;flex-direction:column;gap:3px}
    .lotx-field{display:flex;align-items:center;gap:6px;padding:2px;border:1px solid var(--pkx-br);border-radius:8px;background:#fbfcff}
    .lotx-lab{min-width:115px;font-weight:900;font-size:13.5px;color:#21345e}
    .lotx-ctrl{position:relative;flex:1 1 auto}
    .lotx-ctrl input[type=number]{width:82%;height:34px;background:#fff;color:var(--pkx-t);border:1px solid var(--pkx-br);border-radius:8px;padding:6px 30px 6px 8px;font-weight:600;font-size:12.5px;outline:none}
    .lotx-ctrl input[type=number]:focus{border-color:var(--pkx-blue)}
    .lotx-ctrl input[type=number].is-bad{border-color:#e25555;box-shadow:0 0 0 2px rgba(226,85,85,.12)}
    .lotx-ctrl input[type=number]::-webkit-outer-spin-button,.lotx-ctrl input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
    .lotx-ctrl input[type=number]{-moz-appearance:textfield}
    .lotx-suf{position:absolute;right:8px;top:50%;transform:translateY(-50%);color:#8a96ac;font-weight:800;font-size:10.5px}
    .lotx-help{color:#6f7b95;font-weight:800;font-size:11.5px;line-height:1.35;padding:4px 6px}
    .lotx-badge{display:inline-block;padding:1px 6px;border-radius:999px;background:#eef3ff;border:1px solid var(--pkx-br);color:#21345e;font-weight:900;font-size:10.5px}
    .lotx-fee{margin-top:2px;background:#f8faff;border:1px dashed var(--pkx-br);color:#22345e;font-weight:800;font-size:12px;padding:6px 8px;border-radius:8px}
    .lotx-actions{display:flex;gap:6px;margin-top:6px}
    .lotx-btn{appearance:none;border:1px solid var(--pkx-blue);background:linear-gradient(180deg,var(--pkx-blue),var(--pkx-blue-2));color:#fff;font-weight:900;font-size:12px;padding:8px 10px;border-radius:8px;cursor:pointer;box-shadow:0 10px 22px rgba(46,96,220,.22)}
    .lotx-btn.ghost{background:#fff;border-color:var(--pkx-br);color:#21345e;box-shadow:none}
    .lotx-btn:disabled{opacity:.6;cursor:not-allowed}
    @media (max-width:420px){.lotx-card{width:92vw}.lotx-lab{min-width:100px}}
    `;
    const st=document.createElement('style'); st.id='lotx-css'; st.textContent=css; document.head.appendChild(st);
  }
  if (!window.__pkxMouseTrack){
    window.__pkxMouseTrack={x:innerWidth/2,y:innerHeight/3};
    document.addEventListener('mousemove',e=>{__pkxMouseTrack.x=e.clientX;__pkxMouseTrack.y=e.clientY;},{passive:true});
  }
  window.lotxPositionCard = window.lotxPositionCard || function($card){
    const pad=8,vw=innerWidth,vh=innerHeight,a=window.__pkxMouseTrack||{x:vw/2,y:vh/3};
    $card.css({left:a.x+12+pageXOffset,top:a.y+8+pageYOffset});
    requestAnimationFrame(()=>{
      const r=$card[0].getBoundingClientRect();
      let l=a.x+12;if(l+r.width>vw-pad) l=a.x-r.width-12;l=Math.max(pad,Math.min(l,vw-r.width-pad));
      let t=a.y+8; if(t+r.height>vh-pad) t=Math.max(pad,a.y-r.height-8); t=Math.max(pad,Math.min(t,vh-pad));
      $card.css({left:l+pageXOffset,top:t+pageYOffset});
    });
  };
  window.lotxMakeDraggable = window.lotxMakeDraggable || function($box,$handle){
    let drag=false,dx=0,dy=0;
    function down(e){drag=true;$box.addClass('lotx--drag');
      const r=$box[0].getBoundingClientRect();
      const x=(e.touches?e.touches[0].clientX:e.clientX),y=(e.touches?e.touches[0].clientY:e.clientY);
      dx=x-r.left; dy=y-r.top;
      $(document).on('mousemove.lotxdrag touchmove.lotxdrag',move);
      $(document).on('mouseup.lotxdrag touchend.lotxdrag touchcancel.lotxdrag',up);
      e.preventDefault();
    }
    function move(e){if(!drag) return;
      const pad=4,vw=innerWidth,vh=innerHeight,r=$box[0].getBoundingClientRect();
      const x=(e.touches?e.touches[0].clientX:e.clientX),y=(e.touches?e.touches[0].clientY:e.clientY);
      let l=x-dx,t=y-dy; l=Math.min(Math.max(pad,l),vw-r.width-pad); t=Math.min(Math.max(pad,t),vh-36);
      $box.css({left:l+pageXOffset,top:t+pageYOffset});
    }
    function up(){drag=false;$box.removeClass('lotx--drag');$(document).off('.lotxdrag');}
    $handle.on('mousedown.lotxdrag touchstart.lotxdrag',down);
  };
  window.lotxCloseOnSuccessOnce = window.lotxCloseOnSuccessOnce || function(closeFn){
    const ns=(window.Game && Game.notifications && typeof Game.notifications.main==='function')?Game.notifications:null;
    if(!ns) return;
    const orig=ns.main; let done=false;
    ns.main=function(msg,type){try{if(!done && String(type).toLowerCase()==='success'){done=true;setTimeout(closeFn,0);}}catch(_){} return orig.apply(this,arguments);};
    setTimeout(()=>{ if(ns.main!==orig) ns.main=orig; }, 3000);
  };
})();

// ---------- «Выставить предмет» (есть поле Количество) ----------
function openItemLotDialog(itemId, itemName, maxCount){
  closeItemLotDialog();

  const $card = $(`
  <div class="lotx-card" role="dialog" aria-modal="false" aria-label="Выставление лота (предмет)">
    <div class="lotx-head">
      <div>
        <div class="lotx-title">Выставить предмет${itemId ? ' #'+itemId : ''}</div>
        <div class="lotx-sub">${itemName ? $('<div>').text(itemName).html() : 'Аукцион предмета'}</div>
      </div>
      <button class="lotx-close" aria-label="Закрыть">×</button>
    </div>
    <div class="lotx-body">
      <div class="lotx-grid">

        <div class="lotx-field">
          <div class="lotx-lab">Количество</div>
          <div class="lotx-ctrl">
            <input id="lot_count" type="number" inputmode="numeric" min="1" ${maxCount?`max="${maxCount}"`:''} step="1" value="1" placeholder="1">
            <span class="lotx-suf">шт.</span>
          </div>
        </div>

        <div class="lotx-field">
          <div class="lotx-lab">Стартовая цена</div>
          <div class="lotx-ctrl">
            <input id="lot_priceStart" type="number" inputmode="numeric" min="0" step="1" placeholder="например, 1000">
            <span class="lotx-suf">гк.</span>
          </div>
        </div>

        <div class="lotx-field">
          <div class="lotx-lab">Шаг</div>
          <div class="lotx-ctrl">
            <input id="lot_priceHod" type="number" inputmode="numeric" min="1" step="1" placeholder="например, 50">
            <span class="lotx-suf">гк.</span>
          </div>
        </div>

        <div class="lotx-field">
          <div class="lotx-lab">Срок</div>
          <div class="lotx-ctrl">
            <input id="lot_day" value="3" type="number" inputmode="numeric" min="1" max="14" step="1">
            <span class="lotx-suf">дней</span>
          </div>
        </div>

        <div class="lotx-field">
          <div class="lotx-lab">Цена выкупа</div>
          <div class="lotx-ctrl">
            <input id="lot_priceBuy" type="number" inputmode="numeric" min="0" step="1" placeholder="необязательно">
            <span class="lotx-suf">гк.</span>
          </div>
        </div>

        <div class="lotx-field">
          <div class="lotx-lab">Продвижение</div>
          <div class="lotx-ctrl">
            <input id="lot_prodv" type="number" inputmode="numeric" min="0" max="1" step="1" placeholder="0 — без продвижения">
            <span class="lotx-suf">0/1</span>
          </div>
        </div>

        <div class="lotx-help">
          Продвижение <b>1</b> подсветит лот и поднимет его выше. Стоимость —
          <span class="lotx-badge">25&nbsp;000 генкар</span>.
        </div>
        <div class="lotx-fee">Стоимость добавления лота: <b>40&nbsp;000 гк.</b></div>

        <div class="lotx-actions">
          <button class="lotx-btn" data-act="submit">Добавить лот</button>
          <button class="lotx-btn ghost" data-act="cancel">Отмена</button>
        </div>
      </div>
    </div>
  </div>`);

  $('body').append($card);
  lotxPositionCard($card);
  requestAnimationFrame(()=> $card.addClass('show'));

  lotxMakeDraggable($card, $card.find('.lotx-head'));
  const onRelocate = ()=> lotxPositionCard($card);
  $(window).on('resize.lotx_i scroll.lotx_i', onRelocate);

  $card.on('click','.lotx-close,[data-act="cancel"]', closeItemLotDialog);
  setTimeout(()=>{ $(document).on('keydown.lotx_i', e=>{ if(e.key==='Escape') closeItemLotDialog(); }); },0);
  setTimeout(()=>{ $(document).on('mousedown.lotx_i', e=>{ if(!$(e.target).closest('.lotx-card').length) closeItemLotDialog(); }); },0);

  // submit
  $card.on('click','[data-act="submit"]', function(){
    const req = [$('#lot_count'), $('#lot_priceStart'), $('#lot_priceHod'), $('#lot_day')];
    req.forEach($i=> $i.removeClass('is-bad'));

    const vCount = +req[0].val()||0;
    const vStart = +req[1].val()||0;
    const vHod   = +req[2].val()||0;
    const vDay   = +req[3].val()||0;

    if (vCount<1 || (maxCount && vCount>maxCount)) req[0].addClass('is-bad');
    if (vStart<0) req[1].addClass('is-bad');
    if (vHod<1)   req[2].addClass('is-bad');
    if (vDay<1)   req[3].addClass('is-bad');

    if ($('.is-bad', $card).length) return;

    // Автозакрытие по успешному уведомлению
    lotxCloseOnSuccessOnce(closeItemLotDialog);

    // Ваш текущий обработчик
    if (typeof addLotItem === 'function') addLotItem(itemId);
    else if (typeof issetAll === 'function') issetAll(itemId, 'item_lot');
  });
}

function closeItemLotDialog(){
  $(window).off('.lotx_i'); $(document).off('.lotx_i'); $('.lotx-card').remove();
}
function craft_tm_type(type){
    $.ajax({
                url: "/do/craft_tm",
                type: "POST",
                data: {
                    category: 'selectType',
                    type: type
                },
                success: function (response) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    if(response['error'] == 1){
                        Game.notifications.main(response['text'],'error');
                    }else{
                        $('.craft_tm').html(response['html']);
                    }
                }
            });
}
function renamePok(type, e, id, name) {
    if (type == "open") {
        $('<div />', {
            "class": 'MiniModal',
            html: function() {
                var tpl = '';
                tpl += '<div class="Name" id="drgMini">Дать имя покемону</div>';
                tpl += '<div class="Content">';
                tpl += '<div class="Settings"><div class="Step"><input placeholder="'+Lang.text_name_it+'" type="text" maxlength="15" onkeydown="if(event.keyCode == 13){renamePok(\'rename\',this,'+id+',$(this).val());}"></div></div>';
                tpl += '</div>';
                return tpl;
            }
        }).appendTo('body');
        $('.MiniModal').draggabilly({
            handle: '#drgMini',
            containment: true
        });
        $('#namePokemon'+id).html('<input placeholder="'+Lang.text_name_it+'" type="text" maxlength="15" onkeydown="if(event.keyCode == 13){renamePok(\'rename\',this,'+id+',$(this).val());}">').focus();
        $('#namePokemon'+id+' input').focus();
    } else {
        // Корректная проверка длины строки
        if (!name || name.length < 1 || name.length > 15) {
            Game.notifications.main(Lang.error_name_it, "error");
        } else {
            $.ajax({
                url: "/do/pokemonsAction",
                type: "POST",
                data: {
                    pokID: id,
                    type: 'renamePok',
                    name: name
                },
                success: function (response) {
                    let parsed;
                    try {
                        parsed = typeof response === "object" ? response : JSON.parse(response);
                    } catch (err) {
                        Game.notifications.main("Ошибка разбора ответа сервера: " + err, "error");
                        return;
                    }
                    if (typeof parsed !== "object" || parsed === null) {
                        Game.notifications.main("Ответ сервера в неверном формате!", "error");
                        return;
                    }
                    if (parsed['error']) {
                        Game.notifications.main(parsed['text'] || "Неизвестная ошибка!", "error");
                    } else {
                        Game.notifications.main(parsed['text'] || "Имя изменено.", "success");
                        $('#namePokemon'+id).html(parsed['name']);
                        $('.MiniModal').remove();
                        Game.modals.pokemons();
                    }
                },
                error: function (xhr, status, error) {
                    let msg = "Ошибка сети: ";
                    if (xhr && xhr.responseText) {
                        try {
                            let parsed = JSON.parse(xhr.responseText);
                            msg += (parsed['text'] ? parsed['text'] : xhr.responseText);
                        } catch (e) {
                            msg += xhr.responseText;
                        }
                    } else {
                        msg += error || status;
                    }
                    Game.notifications.main(msg, "error");
                }
            });
        }
    }
}

// category: 1 — отпустить выбранного (по умолчанию), 99 — отпустить остальных
function deletePok(id, category = 1) {
    // Текст подтверждения
    const confirmText = (category === 99)
        ? (Lang.confirm_drop_pokemon_others || 'Отпустить всех остальных покемонов? Это действие необратимо.')
        : Lang.confirm_drop_pokemon;

    if (!confirm(confirmText)) return false;

    // Защита от двойного клика
    if (deletePok._busy) return false;
    deletePok._busy = true;

    $.ajax({
        url: "/do/pokemonsAction",
        type: "POST",
        dataType: "json", // пусть jQuery сам парсит JSON
        data: {
            pokID: id,        // оставляем pokID для совместимости с текущим бекендом
            category: category,
            type: 'deletePok'
        },
        beforeSend: function () {
            // опционально: индикатор загрузки
            $('body').addClass('is-loading');
        },
        complete: function () {
            $('body').removeClass('is-loading');
            deletePok._busy = false;
        },
        success: function (response) {
            // Безопасные геттеры
            const err  = Number(response && response.error) || 0;
            const text = (response && response.text) || (err ? 'Ошибка' : 'Готово');

            if (err === 1) {
                Game.notifications.main(text, 'error');
                return;
            }

            Game.notifications.main(text, 'success');

            // Для одиночного отпуска сервер присылает pokId/pokName — показываем карточку
            if (category === 1 && response && response.pokId && response.pokName) {
                Game.notifications.main(
                    '<img src="/img/pokemons/animation/' + response.pokId + '.png"> ' + response.pokName,
                    'minus'
                );
            }

            // Закрываем модалку и, если есть, перерисовываем список
            if (typeof closeModal === 'function') closeModal();
            if (Game && Game.modals && typeof Game.modals.pokemons === 'function') {
                Game.modals.pokemons();
            }
        },
        error: function (xhr) {
            let msg = 'Ошибка соединения. Попробуйте ещё раз.';
            if (xhr && xhr.responseText) {
                try {
                    const j = JSON.parse(xhr.responseText);
                    if (j && j.text) msg = j.text;
                } catch (e) { /* игнор */ }
            }
            Game.notifications.main(msg, 'error');
        }
    });

    return true;
}

function putPok(id,type){
    $.ajax({
        url: "/do/pokemonsAction",
        type: "POST",
        data: {
            pokID: id,
            type: 'putPok',
            category: type
        },
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if(response['error'] == 1){
                Game.notifications.main(response['text'],'error');
            }else{
                Game.notifications.main(response['text'],'success');
                Game.modals.pokemons();
            }
        }
    });
}
function npcGiveItem(id,npc,type=false){
    var count = ($('#countItemInput').val()?$('#countItemInput').val():1);
	var ctg = (type == false ? "egg" : "item");
    $.ajax({
        url: "/do/npcGiveItem",
        type: "POST",
        data: {
            npc: npc,
            category: ctg,
			id: id,
			count: count
        },
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if(response['minus']) {
              Game.notifications.main(response['minus'],'minus');
            }
            if(response['plus']) {
              Game.notifications.main(response['plus'],'plus');
            }
            if(response['text']) {
            Game.notifications.main(response['text'],response['error']);
            }
			Game.modals.inventory('all');
        }
    });
}

/**
 * Показывает красивое модальное меню действий с покемоном.
 * Улучшено: структура, читаемость, стили, aria, плавность, расширяемость.
 * Без сокращений и без смены логики привязки!
 */
/* ================= Ultra-compact Pokemon menu (anchored + draggable) ================= */
/* 1) Изолированные стили (.pkx-*) */
(function injectPKXCSS(){
  if (document.getElementById('pkx-css')) return;
  const css = `
  :root{
    --pkx-card:#fff; --pkx-br:#e6eafe; --pkx-t:#1b2b4f; --pkx-ac:#0e55b6;
    --pkx-danger:#c83535; --pkx-danger-bg:#fff3f3; --pkx-shadow:0 16px 32px rgba(20,35,80,.12);
  }
  .pkx-menu{
    position:absolute; z-index:1000000; width:198px; max-width:92vw;
    background:var(--pkx-card); border:1px solid var(--pkx-br); border-radius:9px; box-shadow:var(--pkx-shadow);
    color:var(--pkx-t); overflow:hidden; opacity:0; transform:translateY(-4px) scale(.985);
    transition:opacity .12s ease, transform .14s ease; font-family:Nunito,Inter,Arial,sans-serif;
  }
  .pkx-menu.is-shown{ opacity:1; transform:translateY(0) scale(1); }
  .pkx-menu.pkx--dragging{ cursor:grabbing; user-select:none }

  .pkx-head{ cursor:grab; display:flex; align-items:center; justify-content:space-between;
             padding:5px 7px; background:linear-gradient(180deg,#f9fbff,#f5f7ff); border-bottom:1px solid var(--pkx-br); }
  .pkx-title{ font-weight:900; font-size:11px; letter-spacing:.15px; text-transform:uppercase; color:#21345e; }
  .pkx-close{ appearance:none; border:0; background:transparent; cursor:pointer; padding:2px 4px; line-height:1; color:#9aa6bd; }
  .pkx-close:hover{ color:#5c6b8a; }

  .pkx-list{ padding:3px; }
  .pkx-item{
    width:100%; display:flex; align-items:center; gap:6px; padding:5px 6px;
    background:transparent; border:0; text-align:left; cursor:pointer; border-radius:7px;
    font-weight:300; font-size:11.75px; line-height:1.1; color:#21345e;
  }
  .pkx-item i{ width:13px; text-align:center; opacity:.95; font-size:12px }
  .pkx-item:hover, .pkx-item:focus{ background:#eef3ff; color:#0e55b6; outline:none }
  .pkx-item.is-danger{ color:var(--pkx-danger) }
  .pkx-item.is-danger:hover, .pkx-item.is-danger:focus{ background:var(--pkx-danger-bg) }
  .pkx-item.is-disabled{ opacity:.55; pointer-events:none }

  .pkx-sep{ height:1px; background:var(--pkx-br); margin:3px 2px; }

  @media (max-width:600px){ .pkx-menu{ width:192px } }
  `;
  const st=document.createElement('style'); st.id='pkx-css'; st.textContent=css; document.head.appendChild(st);
})();

/* 2) Трекер мыши (надёжный fallback для якоря) */
(function pkxSetupMouseTracker(){
  if (window.__pkxMouseTrack) return;
  window.__pkxMouseTrack = { x: innerWidth/2, y: innerHeight/3 };
  document.addEventListener('mousemove', function(ev){
    window.__pkxMouseTrack.x = ev.clientX;
    window.__pkxMouseTrack.y = ev.clientY;
  }, { passive:true });
})();

/* 3) Универсальный извлекатель координат */
function pkxGetAnchor(ev){
  const e = ev && (ev.originalEvent || ev) || null;

  if (e && typeof e.clientX === 'number' && typeof e.clientY === 'number') {
    return { x: e.clientX, y: e.clientY, el: (e.currentTarget || e.target) || null };
  }
  const tgt = ev && (ev.currentTarget || ev.target);
  if (tgt && tgt.getBoundingClientRect) {
    const r = tgt.getBoundingClientRect();
    return { x: r.left + r.width/2, y: r.top + r.height/2, el: tgt };
  }
  if (window.__pkxMouseTrack) {
    return { x: window.__pkxMouseTrack.x, y: window.__pkxMouseTrack.y, el: null };
  }
  return { x: innerWidth/2, y: innerHeight/3, el: null };
}

/* 4) Позиционирование: top = точка клика, зеркалирование слева, max-height до низа экрана */
function pkxPositionAt(ev, el){
  const pad = 8, vw = innerWidth, vh = innerHeight;
  const a = pkxGetAnchor(ev); // {x, y}

  el.style.top  = (a.y + pageYOffset) + 'px';
  el.style.left = (a.x + 12 + pageXOffset) + 'px';

  requestAnimationFrame(()=>{
    const r = el.getBoundingClientRect();

    let left = (a.x + 12);
    if (left + r.width > vw - pad) left = a.x - r.width - 12;
    left = Math.min(Math.max(pad, left), vw - r.width - pad);
    el.style.left = (left + pageXOffset) + 'px';

    const availH = Math.max(120, vh - a.y - pad);
    el.style.maxHeight = availH + 'px';
    el.style.overflow  = 'auto';
  });
}

/* 5) Перетаскивание за шапку */
function pkxMakeDraggable($menu, $handle){
  let dragging=false, dx=0, dy=0;
  function onDown(e){
    dragging=true; $menu.addClass('pkx--dragging');
    const rect=$menu[0].getBoundingClientRect();
    const px=(e.touches? e.touches[0].clientX : e.clientX);
    const py=(e.touches? e.touches[0].clientY : e.clientY);
    dx=px-rect.left; dy=py-rect.top;
    $(document).on('mousemove.pkxdrag touchmove.pkxdrag', onMove);
    $(document).on('mouseup.pkxdrag touchend.pkxdrag touchcancel.pkxdrag', onUp);
  }
  function onMove(e){
    if(!dragging) return;
    const pad=4, vw=innerWidth, vh=innerHeight;
    const px=(e.touches? e.touches[0].clientX : e.clientX);
    const py=(e.touches? e.touches[0].clientY : e.clientY);
    let left=px-dx, top=py-dy;
    const r=$menu[0].getBoundingClientRect();
    left=Math.min(Math.max(pad, left), vw-r.width-pad);
    top =Math.min(Math.max(pad, top ), vh-40);
    $menu.css({ left:left+pageXOffset, top:top+pageYOffset });
  }
  function onUp(){ dragging=false; $menu.removeClass('pkx--dragging'); $(document).off('.pkxdrag'); }
  $handle.on('mousedown.pkxdrag touchstart.pkxdrag', onDown);
}

/* 6) Публичная функция (сигнатура сохранена) */
function pokAction(e, id, start, newName, num){
  $('.pkx-menu').remove();
  $(document).off('.pkx');

  const titleText=(window.Lang&&Lang.text_info)||'ИНФОРМАЦИЯ';
  const $m=$('<div/>',{'class':'pkx-menu',role:'menu',tabindex:-1,'aria-label':titleText});
  const $hdr=$('<div/>',{'class':'pkx-head'})
    .append($('<div/>',{'class':'pkx-title',text:titleText}))
    .append($('<button/>',{'class':'pkx-close','aria-label':'Закрыть',html:'&times;'}).on('click', close));
  const $lst=$('<div/>',{'class':'pkx-list'});

  function add(label, icon, onClick, opts){
    opts=opts||{};
    const $b=$('<button/>',{'class':'pkx-item'+(opts.danger?' is-danger':'')+(opts.disabled?' is-disabled':''),'type':'button',role:'menuitem',tabindex:0})
      .append($('<i/>',{'class':icon}), $('<span/>',{text:label}));
    if(!opts.disabled) $b.on('click', function(ev){ ev.preventDefault(); ev.stopPropagation(); close(); onClick.call(this, ev); });
    $lst.append($b); return $b;
  }
  const sep=()=> $lst.append('<div class="pkx-sep" role="separator"></div>');

  /* Пункты меню — используем ваши существующие функции */
  if (window.ClassTrade){
    add(Lang?.button_trade_add||'Трейд','fas fa-retweet',()=>{
      ClassTrade._addObject('poke', id, 1,
        function(){ $('#pokeTeam_'+id).remove(); Game.modals?.pokemons?.(); },
        function(){ close(); }
      );
    });
  }
  add(Lang?.button_pokedex||'Покедекс','fa fa-info',()=>openDex(num));
  add(Lang?.button_pokemon_name_add||'Дать имя','fa fa-edit',function(){ renamePok('open', this, id); });

  if (typeof openPokPreview==='function'){
    add('Открыть просмотр','fa fa-eye',()=>openPokPreview(id));
  } else if (typeof pokemonChat==='function'){
    add('Добавить в чат','fa fa-angle-double-down',()=>pokemonChat(id));
  }

  add('Сделать главным','fa fa-star',()=>setStart(id),{disabled:String(start)!=='0'});
  add(Lang?.button_pokemon_walk||'Погулять с покемоном','fa fa-smile',()=>wentPok(id));
  add('Выставить на торговую площадку','fas fa-shopping-cart',()=>openLotDialog(id, newName));

  add('Команды','fas fa-bookmark',()=>issetAll(id,'team'));

  sep();
  add('Отправить в питомник','fa fa-sign-in-alt',()=>putPok(id,1));
  add('Отправить в питомник остальных','fa fa-sign-in-alt',()=>putPok(id,99));

  sep();
  add('Эволюционировать','fas fa-sparkles',()=>evol_lvl(id));

  sep();
  add(Lang?.button_let_go||'Отпустить','fa fa-minus-circle',()=>deletePok(id,1),{danger:true});
  add('Отпустить остальных','fa fa-times',()=>deletePok(id,99),{danger:true});

  $m.append($hdr,$lst).appendTo('body');

  // якорим строго по клику
  pkxPositionAt(e, $m[0]);
  // перетаскивание за шапку
  pkxMakeDraggable($m, $hdr);

  setTimeout(()=>{ $m.addClass('is-shown').focus(); }, 10);

  // закрытия/навигация
  setTimeout(()=>{
    $(document).on('mousedown.pkx',(evt)=>{ if(!$(evt.target).closest('.pkx-menu').length) close(); });
    $(document).on('keydown.pkx',(evt)=>{
      if (evt.key==='Escape'){ close(); return; }
      if (evt.key==='ArrowDown'||evt.key==='ArrowUp'){
        const $it=$m.find('.pkx-item:not(.is-disabled)');
        const i=Math.max(0,$it.index(document.activeElement));
        let next=evt.key==='ArrowDown' ? (i+1)%$it.length : (i-1+$it.length)%$it.length;
        $it.eq(next).focus(); evt.preventDefault();
      }
      if (evt.key==='Enter' && $(document.activeElement).hasClass('pkx-item')) $(document.activeElement).trigger('click');
    });
  },0);

  function close(){ $m.remove(); $(document).off('.pkx'); }
}

/* ================== LOTX COMPACT — карточка «Выставить лот» ================== */
(function injectLOTXCSS(){
  if (document.getElementById('lotx-css')) return;
  const css = `
  :root{
    --pkx-card:#fff; --pkx-br:#e6eafe; --pkx-t:#1b2b4f; --pkx-sub:#6f7b95;
    --pkx-blue:#2f74ff; --pkx-blue-2:#0e55b6; --pkx-shadow:0 16px 32px rgba(20,35,80,.12);
  }
  .lotx-card{
    position:absolute; z-index:1000003; width:360px; max-width:96vw;
    background:var(--pkx-card); border:1px solid var(--pkx-br); border-radius:10px;
    box-shadow:var(--pkx-shadow); color:var(--pkx-t);
    transform:translateY(-3px) scale(.985); opacity:.0; transition:opacity .14s ease, transform .14s ease;
    font-family:Nunito,Inter,Arial,sans-serif;
  }
  .lotx-card.show{ transform:translateY(0) scale(1); opacity:1 }
  .lotx-card.lotx--drag{ cursor:grabbing; user-select:none }

  .lotx-head{ cursor:grab; display:flex; align-items:center; justify-content:space-between;
              padding:6px 8px; background:linear-gradient(180deg,#f9fbff,#f5f7ff); border-bottom:1px solid var(--pkx-br) }
  .lotx-title{ font-weight:900; font-size:13.5px; color:#21345e; line-height:1.1 }
  .lotx-sub{ font-weight:800; font-size:12.5px; color:var(--pkx-sub); margin-top:1px }
  .lotx-close{ appearance:none; border:0; background:transparent; cursor:pointer; color:#9aa6bd; padding:2px 6px; font-size:18px }
  .lotx-close:hover{ color:#5c6b8a }

  .lotx-body{ padding:8px }
  .lotx-grid{ display:flex; flex-direction:column; gap:3px }
  .lotx-field{ display:flex; align-items:center; gap:6px; padding:1px; border:1px solid var(--pkx-br); border-radius:8px; background:#fbfcff }
  .lotx-lab{ min-width:115px; font-weight:900; font-size:13.5px; color:#21345e }
  .lotx-ctrl{ position:relative; flex:1 1 auto }
  .lotx-ctrl input[type=number]{ width:82%; height:34px; background:#fff; color:var(--pkx-t);
    border:1px solid var(--pkx-br); border-radius:8px; padding:6px 30px 6px 8px; font-weight:600; font-size:12.5px; outline:none }
  .lotx-ctrl input[type=number]:focus{ border-color:var(--pkx-blue) }
  .lotx-ctrl input[type=number].is-bad{ border-color:#e25555; box-shadow:0 0 0 2px rgba(226,85,85,.12) }
  .lotx-ctrl input[type=number]::-webkit-outer-spin-button,
  .lotx-ctrl input[type=number]::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0 }
  .lotx-ctrl input[type=number]{ -moz-appearance:textfield }
  .lotx-suf{ position:absolute; right:8px; top:50%; transform:translateY(-50%); color:#8a96ac; font-weight:800; font-size:10.5px }

  .lotx-help{ color:#6f7b95; font-weight:800; font-size:11.5px; line-height:1.35; padding:4px 6px }
  .lotx-badge{ display:inline-block; padding:1px 6px; border-radius:999px; background:#eef3ff; border:1px solid var(--pkx-br);
               color:#21345e; font-weight:900; font-size:10.5px }
  .lotx-fee{ margin-top:2px; background:#f8faff; border:1px dashed var(--pkx-br); color:#22345e; font-weight:800;
             font-size:12px; padding:6px 8px; border-radius:8px }

  .lotx-actions{ display:flex; gap:6px; margin-top:6px }
  .lotx-btn{ appearance:none; border:1px solid var(--pkx-blue); background:linear-gradient(180deg,var(--pkx-blue),var(--pkx-blue-2));
             color:#fff; font-weight:900; font-size:12px; padding:8px 10px; border-radius:8px; cursor:pointer;
             box-shadow:0 10px 22px rgba(46,96,220,.22) }
  .lotx-btn.ghost{ background:#fff; border-color:var(--pkx-br); color:#21345e; box-shadow:none }
  .lotx-btn:disabled{ opacity:.6; cursor:not-allowed }

  @media (max-width:420px){
    .lotx-card{ width:92vw }
    .lotx-lab{ min-width:100px }
  }`;
  const st=document.createElement('style'); st.id='lotx-css'; st.textContent=css; document.head.appendChild(st);
})();

/* якорь по курсору — как в pokAction */
(function ensurePkxMouseTracker(){
  if (window.__pkxMouseTrack) return;
  window.__pkxMouseTrack = { x: innerWidth/2, y: innerHeight/3 };
  document.addEventListener('mousemove', e=>{
    __pkxMouseTrack.x = e.clientX; __pkxMouseTrack.y = e.clientY;
  }, { passive:true });
})();

/* позиционирование и перетаскивание */
function lotxPositionCard($card){
  const pad=8, vw=innerWidth, vh=innerHeight;
  const a = window.__pkxMouseTrack || {x: vw/2, y: vh/3};
  $card.css({ left: a.x + 12 + pageXOffset, top: a.y + 8 + pageYOffset });
  requestAnimationFrame(()=>{
    const r = $card[0].getBoundingClientRect();
    let left = a.x + 12; if (left + r.width > vw - pad) left = a.x - r.width - 12;
    left = Math.max(pad, Math.min(left, vw - r.width - pad));
    let top  = a.y + 8;  if (top + r.height > vh - pad) top  = Math.max(pad, a.y - r.height - 8);
    top  = Math.max(pad, Math.min(top, vh - pad));
    $card.css({ left:left + pageXOffset, top:top + pageYOffset });
  });
}
function lotxMakeDraggable($box, $handle){
  let drag=false, dx=0, dy=0;
  function onDown(e){
    drag=true; $box.addClass('lotx--drag');
    const r=$box[0].getBoundingClientRect();
    const x=(e.touches? e.touches[0].clientX : e.clientX);
    const y=(e.touches? e.touches[0].clientY : e.clientY);
    dx=x-r.left; dy=y-r.top;
    $(document).on('mousemove.lotxdrag touchmove.lotxdrag', onMove);
    $(document).on('mouseup.lotxdrag touchend.lotxdrag touchcancel.lotxdrag', onUp);
    e.preventDefault();
  }
  function onMove(e){
    if(!drag) return;
    const pad=4, vw=innerWidth, vh=innerHeight;
    const x=(e.touches? e.touches[0].clientX : e.clientX);
    const y=(e.touches? e.touches[0].clientY : e.clientY);
    const r=$box[0].getBoundingClientRect();
    let left=x-dx, top=y-dy;
    left=Math.min(Math.max(pad, left), vw-r.width-pad);
    top =Math.min(Math.max(pad, top ), vh-36);
    $box.css({ left:left+pageXOffset, top:top+pageYOffset });
  }
  function onUp(){ drag=false; $box.removeClass('lotx--drag'); $(document).off('.lotxdrag'); }
  $handle.on('mousedown.lotxdrag touchstart.lotxdrag', onDown);
}

/* авто-закрытие по «success» */
function lotxCloseOnSuccessOnce(closeFn){
  const ns = (window.Game && Game.notifications && typeof Game.notifications.main === 'function') ? Game.notifications : null;
  if (!ns) return;
  const orig = ns.main; let done=false;
  ns.main = function(msg, type){
    try{ if(!done && String(type).toLowerCase()==='success'){ done=true; setTimeout(closeFn,0); } }catch(_){}
    return orig.apply(this, arguments);
  };
  setTimeout(()=>{ if(ns.main!==orig) ns.main = orig; }, 3000);
}

/* публичный API */
function openLotDialog(pokId, pokName){
  closeLotDialog();

  const title = 'Выставить ' + (pokName ? pokName : ('покемона #'+pokId));
  const $card = $(`
    <div class="lotx-card" role="dialog" aria-modal="false" aria-label="Выставление лота">
      <div class="lotx-head">
        <div>
          <div class="lotx-title">${title}</div>
          <div class="lotx-sub">Аукцион покемона</div>
        </div>
        <button class="lotx-close" aria-label="Закрыть">×</button>
      </div>
      <div class="lotx-body">
        <div class="lotx-grid">
          <div class="lotx-field">
            <div class="lotx-lab">Стартовая цена</div>
            <div class="lotx-ctrl">
              <input id="lot_priceStart" type="number" inputmode="numeric" min="0" step="1" placeholder="например, 10000">
              <span class="lotx-suf">гк.</span>
            </div>
          </div>
          <div class="lotx-field">
            <div class="lotx-lab">Шаг</div>
            <div class="lotx-ctrl">
              <input id="lot_priceHod" type="number" inputmode="numeric" min="1" step="1" placeholder="например, 500">
              <span class="lotx-suf">гк.</span>
            </div>
          </div>
          <div class="lotx-field">
            <div class="lotx-lab">Срок</div>
            <div class="lotx-ctrl">
              <input id="lot_day" value="3" type="number" inputmode="numeric" min="1" max="14" step="1">
              <span class="lotx-suf">дней</span>
            </div>
          </div>
          <div class="lotx-field">
            <div class="lotx-lab">Цена выкупа</div>
            <div class="lotx-ctrl">
              <input id="lot_priceBuy" type="number" inputmode="numeric" min="0" step="1" placeholder="необязательно">
              <span class="lotx-suf">гк.</span>
            </div>
          </div>
          <div class="lotx-field">
            <div class="lotx-lab">Продвижение</div>
            <div class="lotx-ctrl">
              <input id="lot_prodv" type="number" inputmode="numeric" min="0" max="1" step="1" placeholder="0 — без продвижения">
              <span class="lotx-suf">0/1</span>
            </div>
          </div>

          <div class="lotx-help">
            Продвижение <b>1</b> подсветит лот и поднимет его выше. Цена — <span class="lotx-badge">25 000 генкар</span>.
          </div>
          <div class="lotx-fee">Стоимость добавления лота: <b>40 000 гк.</b></div>

          <div class="lotx-actions">
            <button class="lotx-btn" data-act="submit">Добавить лот</button>
            <button class="lotx-btn ghost" data-act="cancel">Отмена</button>
          </div>
        </div>
      </div>
    </div>
  `);

  $('body').append($card);
  lotxPositionCard($card);
  requestAnimationFrame(()=> $card.addClass('show'));

  lotxMakeDraggable($card, $card.find('.lotx-head'));
  const onRelocate = ()=> lotxPositionCard($card);
  $(window).on('resize.lotx scroll.lotx', onRelocate);

  $card.on('click','.lotx-close,[data-act="cancel"]', closeLotDialog);
  setTimeout(()=>{ $(document).on('keydown.lotx', e=>{ if(e.key==='Escape') closeLotDialog(); }); },0);
  setTimeout(()=>{ $(document).on('mousedown.lotx', e=>{ if(!$(e.target).closest('.lotx-card').length) closeLotDialog(); }); },0);

  // компактная проверка + автозакрытие при success
  $card.on('click','[data-act="submit"]', function(){
    const $start = $('#lot_priceStart'), $step=$('#lot_priceHod'), $days=$('#lot_day');
    [$start,$step,$days].forEach($i=> $i.removeClass('is-bad'));
    if( (+$start.val()||0) < 0 ) $start.addClass('is-bad');
    if( (+$step.val() ||0) <=0 ) $step.addClass('is-bad');
    if( (+$days.val() ||0) < 1 ) $days.addClass('is-bad');

    lotxCloseOnSuccessOnce(closeLotDialog);
    if (typeof addLotPokemon === 'function') addLotPokemon(pokId);
  });
}

function closeLotDialog(){ $(window).off('.lotx'); $(document).off('.lotx'); $('.lotx-card').remove(); }
function itemAction(id, type, name, typeM, pok, opt = {}) {
    // Получаем нужное количество (count) из поля ввода, если оно есть
    let count = 1;
    const $input = $('#countItemInput');
    if ($input.length) {
        const val = parseInt($input.val(), 10);
        if (!isNaN(val) && val > 0) count = val;
    }

    $('.GiveDiv').remove();

    // Если используется тип giveEgg — отдельная логика
    if (type === 'giveEgg') {
        let tpl = '<div class="GiveDiv">';
        tpl += '<div id="DivAbout"><b>' + Lang.text_npc_add_item + '</b></div>';
        tpl += '<div class="wrap"><div class="PokList">';
        $.ajax({
            url: "/do/modal",
            type: "POST",
            data: 'type=giveEggNpc',
            success: function (response) {
                response = (typeof response === 'string') ? JSON.parse(response) : response;
                if (!response['error']) {
                    let NPC = '';
                    $.each(response['npc'], function (x, y) {
                        NPC += '<div class="PokeUse" onmouseover="$(this).css(\'background\',\'#e8e8e8\');" onclick="npcGiveItem(' + id + ',' + y['id'] + ',' + name + ');"><i class="far fa-user-alt npcgive"></i><div class="NameUse" style="padding: 5px;color: #5f5f5f;">' + y['name'] + '</div></div>';
                    });
                    tpl += NPC;
                }
                tpl += '</div></div></div>';
                $(tpl).appendTo('body');
                centerGiveDiv();
            }
        });
    } else if (type === 'give' || type === 'dress') {
        let typeAction = (type === 'give') ? 'GivePok' : 'DressPok';
        let Action = (type === 'give') ? Lang.button_item_use : Lang.button_item_dress;

        $.ajax({
            url: "/do/itemsAction",
            type: "POST",
            data: 'type=pokList',
            success: function (response) {
                if (response != 0) {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                    let team = '';
                    $.each(response['pokList'], function (x, y) {
                        team += `
                            <div class="PokeUse ${y.class}" onclick="itemAction(${id},'${typeAction}',false,'${typeM}',${y.id});">
                                <img src="/img/pokemons/animation/${y.basenum}.png">
                                <div class="NameUse ${y.type}-color">#${y.basenum} ${y.name}</div>
                            </div>
                        `;
                    });
                    let tpl = `
                        <div class="GiveDiv">
                            <div id="DivAbout">${Action}</div>
                            <div class="wrap">
                                <div class="PokList">
                                    ${team}
                                </div>
                            </div>
                            <div class="countInputWrap" style="margin: 10px 0; text-align:center;">
                                <input id="countItemInput" type="number" min="1" value="${count}" style="width:60px; text-align:center;" placeholder="Кол-во">
                            </div>
                        </div>
                    `;
                    $(tpl).appendTo('body');

                    // Вставляем текущее значение count в input, если оно > 1
                    $('#countItemInput').val(count);

                    centerGiveDiv();
                }
            }
        });
    } else if (type === 'eggDrop') {
        $.ajax({
            url: "/do/itemsAction",
            type: "POST",
            data: {
                itemID: id
            },
            beforeSend: function () {
                $('.tooltip').hide();
            },
            success: function (response) {
                Game.notifications.main(response['text'], 'success');
            }
        });
    } else {
        $.ajax({
    url: "/do/itemsAction",
    type: "POST",
    data: {
        itemID: id,
        type: type,
        count: count,
        pokID: pok
    },
    beforeSend: function () {
        $('.tooltip').hide();
    },
    success: function (response) {
        // Попытка парсинга если строка
        if (typeof response === "string") {
            try { response = (typeof response === 'string') ? JSON.parse(response) : response; } catch(e) { response = { error: 1, text: response }; }
        }

        // === Обработка для скинов ===
        if (type === 'UseSkin') {
            if (response && response.error === 0) {
                showNotification(response.text || 'Скин успешно применён!', 'success');
            } else if (response && response.text) {
                showNotification(response.text, 'error');
            } else {
                showNotification('Неизвестный ответ от сервера.', 'warning');
            }
            Game.modals.inventory(typeM);
            return;
        }

                // --- Стандартная обработка для остальных предметов ---
                if (response['text'] && (response['error'] == 0 || typeof response['error'] === 'undefined')) {
                    if (id == 456 && type == 'use') {
                        $.post('/do/Case', { idCase: id }, function (data) {
                            if (!data.error) {
                                $('#case-modal-overlay, #case-modal, .case-reward-popup').remove();
                                $('body').append('<div id="case-modal-overlay"></div>');
                                var $modal = $('<div />', { id: 'case-modal', tabindex: -1 }).appendTo('body');
                                $modal.append(`
                                    <div class="case-modal__header">
                                        <h1>Открытие кейса кейса<br>&lt;&lt;${data.nameCase || ''}&gt;&gt;</h1>
                                        <h2>Открыть этот кейс можно лишь 1 раз</h2>
                                    </div>
                                `);

                                // --- Flip-карточки ---
                                var cardCount = 3;
                                var allPrizes = data.carusel;
                                var $flipWrap = $('<div />', { class: 'case-flip-cards' }).appendTo($modal);

                                // --- Призы ---
                                var $listWrap = $('<div />', { class: 'case-modal__prizes' }).appendTo($modal);
                                $('<div />', {
                                    class: 'case-modal__prizes-label',
                                    html: 'Предметы, которые могут быть в этом кейсе:'
                                }).appendTo($listWrap);
                                var $list = $('<div />', { class: 'case-modal__prizes-list' }).appendTo($listWrap);

                                $.each(data.list, function () {
                                    var imgSrc = (this.type === 'item')
                                        ? '/img/world/items/little/' + this.id + '.png'
                                        : '/img/pokemons/sprite/normal/' + this.id + '.gif';
                                    var minmax = (this.min_amount && this.max_amount && this.min_amount !== this.max_amount)
                                        ? 'x' + this.min_amount + '-' + this.max_amount
                                        : (this.count > 1 ? 'x' + this.count : '');
                                    var rarity = this.rarity ? ' ' + this.rarity : '';
                                    var item =
                                        `<div class="case-modal__prize${rarity}">
                                            ${this.rarity ? `<div class="case-modal__prize-rarity ${this.rarity}"></div>` : ''}
                                            <img src="${imgSrc}" alt="">
                                            ${minmax ? `<span class="case-modal__prize-count">${minmax}</span>` : ''}
                                            ${this.name ? `<span class="case-modal__prize-name">${this.name}</span>` : ''}
                                        </div>`;
                                    $('<div />', {
                                        html: item,
                                        class: 'case-modal__prize-box'
                                    }).appendTo($list);
                                });

                                // --- Кнопки ---
                                var $btns = $('<div />', { class: 'case-modal__buttons' }).appendTo($modal);

                                // ОТКРЫТЬ КЕЙС (Flip анимация)
                                $('<button />', {
                                    html: 'ОТКРЫТЬ КЕЙС',
                                    class: 'case-modal__btn case-modal__btn--open',
                                    click: function () {
                                        if ($(this).prop('disabled')) return;
                                        $(this).prop('disabled', true);

                                        // Получаем выигрыш от сервера и анимируем flip
                                        $.post('/do/CaseAction', function (resp) {
                                            try { resp = typeof resp === 'string' ? JSON.parse(resp) : resp; } catch(e){}
                                            var reward = {};
                                            if (resp.reward) {
                                                let r = Array.isArray(resp.reward) ? resp.reward[0] : resp.reward;
                                                reward = {
                                                    img: r.img || r.image || null,
                                                    name: r.name || r.text || r.plus || 'Приз',
                                                    count: r.count || r.amount || 1,
                                                    id: r.id,
                                                    type: r.type,
                                                    rarity: r.rarity || null
                                                };
                                            } else {
                                                reward = {
                                                    img: resp.img || null,
                                                    name: resp.plus || resp.text || 'Приз',
                                                    count: 1,
                                                    id: resp.id || null,
                                                    type: resp.type || null,
                                                    rarity: resp.rarity || null
                                                };
                                            }

                                            // ГАРАНТИРУЕМ, что reward.rarity всегда заполнено
                                            if (!reward.rarity) {
                                                var foundPrize = allPrizes.find(function (p) {
                                                    return String(p.id) == String(reward.id) && String(p.type) == String(reward.type);
                                                });
                                                if (foundPrize && foundPrize.rarity) {
                                                    reward.rarity = foundPrize.rarity;
                                                } else {
                                                    reward.rarity = ""; // или "normal"
                                                }
                                            }

                                            // Собираем карточки: выигрыш обязательно среди них
                                            let nonWinPrizes = allPrizes.filter(p => !(String(p.id) == String(reward.id) && String(p.type) == String(reward.type)));
                                            let randomPrizes = [];
                                            while (randomPrizes.length < cardCount - 1 && nonWinPrizes.length) {
                                                let i = Math.floor(Math.random() * nonWinPrizes.length);
                                                randomPrizes.push(nonWinPrizes.splice(i, 1)[0]);
                                            }
                                            let prizeList = randomPrizes.concat([{
                                                id: reward.id,
                                                type: reward.type,
                                                img: reward.img ? reward.img
                                                    : (reward.type === 'item'
                                                        ? '/img/world/items/little/' + reward.id + '.png'
                                                        : '/img/pokemons/sprite/normal/' + reward.id + '.gif'),
                                                name: reward.name,
                                                rarity: reward.rarity
                                            }]);
                                            prizeList = prizeList.sort(() => Math.random() - 0.5);

                                            // Рисуем карточки
                                            $flipWrap.empty();
                                            prizeList.forEach(function(item, idx) {
                                                var imgSrc = item.img ? item.img :
                                                    (item.type === 'item'
                                                        ? '/img/world/items/little/' + item.id + '.png'
                                                        : '/img/pokemons/sprite/normal/' + item.id + '.gif');
                                                var $card = $(`
                                                    <div class="flip-card" data-idx="${idx}" data-id="${item.id}" data-type="${item.type}" data-rarity="${item.rarity || ''}" data-win="false">
                                                        <div class="flip-inner">
                                                            <div class="flip-front">?</div>
                                                            <div class="flip-back"><img src="${imgSrc}" alt=""></div>
                                                        </div>
                                                    </div>
                                                `);
                                                $flipWrap.append($card);
                                            });

                                            // Выделяем выигрышную карточку
                                            var winIdx = 0;
                                            $flipWrap.find('.flip-card').each(function(idx, el) {
                                                var $el = $(el);
                                                if (
                                                    String($el.attr('data-id')) == String(reward.id) &&
                                                    String($el.attr('data-type')) == String(reward.type)
                                                ) {
                                                    winIdx = idx;
                                                }
                                            });

                                            $flipWrap.find('.flip-card').attr('data-win', 'false');
                                            var $winCard = $flipWrap.find('.flip-card').eq(winIdx).attr('data-win', 'true');

                                            // Анимация flip — все по очереди, выигрышная последней и с эффектом по редкости
                                            $flipWrap.find('.flip-card').each(function(idx, el){
                                                setTimeout(function() {
                                                    $(el).addClass('flipped');
                                                    if (idx === winIdx) {
                                                        $(el).addClass('flip-win');
                                                        // Снимаем старые классы редкости (страховка)
                                                        $(el).removeClass('rarity-legend rarity-epic rarity-rare');
                                                        // Добавляем нужный класс редкости, если он не пустой и не обычный
                                                        var rarity = $(el).attr('data-rarity');
                                                        if (rarity && rarity !== 'normal' && rarity !== 'common') {
                                                            $(el).addClass('rarity-' + rarity);
                                                        }
                                                        setTimeout(function() {
                                                            showCaseRewardPopup(reward.img, reward.name + (reward.count > 1 ? " x" + reward.count : ""));
                                                            scrollBox(resp, typeM);
                                                        }, 800);
                                                    }
                                                }, 400 + idx * 420);
                                            });
                                        }, 'json');
                                    }
                                }).appendTo($btns);

                                // ЗАКРЫТЬ
                                $('<button />', {
                                    html: 'ЗАКРЫТЬ',
                                    class: 'case-modal__btn case-modal__btn--close',
                                    click: function () {
                                        $('#case-modal, #case-modal-overlay, .case-reward-popup').fadeOut("slow", function () {
                                            $('#case-modal, #case-modal-overlay, .case-reward-popup').remove();
                                        });
                                    }
                                }).appendTo($btns);
                            } else {
                                Game.notifications.main(data.error, 'error');
                            }
                        }, 'json'); 
                    } else if (type == 'GivePok' && id >= 80 && id <= 123) {
                        if (response['other'] != 0) {
                            var ip = response['other'] <= 9 ? '00' + response['other'] :
                                (response['other'] >= 10 && response['other'] <= 99 ? '0' + response['other'] : response['other']);
                            var ip2 = response['other2'] <= 9 ? '00' + response['other2'] :
                                (response['other2'] >= 10 && response['other2'] <= 99 ? '0' + response['other2'] : response['other2']);
                            $('body').append('<div class="animationImage"><div class="evol" style="background-image: url(/img/pokemons/pokedex/' + ip + '.png);"></div></div>');
                            $(".animationImage .evol").fadeOut(3000, function () {
                                $('.evol').remove();
                            });
                            setTimeout(function () {
                                $('.animationImage').append('<div class="evol" style="background-image: url(/img/pokemons/pokedex/' + ip2 + '.png);"></div>');
                                setTimeout(function () {
                                    $('.animationImage').remove();
                                    Game.notifications.main(response['text'], 'success');
                                }, 3000);
                            }, 3100);
                        } else {
                            Game.notifications.main(response['text'], 'success');
                        }
                    } else if ((id == 197 || id == 198 || id == 245 || id == 246 || id == 196) && type == 'GivePok') {
                        if (response['other'] == 1) {
                            Game.notifications.main(response['text'], 'success');
                        } else {
                            Game.notifications.main(response['text'], 'error');
                        }
                    } else {
                        if ($.isArray(response['text'])) {
                            var countInf = 0;
                            $.each(response['text'], function (key, val) {
                                ++countInf;
                                if (val && countInf < 50) {
                                    Game.notifications.main(val, 'success');
                                }
                            });
                        } else {
                            Game.notifications.main(response['text'], 'success');
                        }
                    }
                    if (type == 'remove') {
                        Game.notifications.main('<img src="img/world/items/little/' + id + '.png" class="item"> ' + response['nameItem'] + ' <b>x1</b>', 'plus');
                        Game.modals.pokemons();
                    } else {
                        Game.modals.inventory(typeM);
                    }
                    if (response['minus']) {
                        Game.notifications.main(response['minus'], 'minus');
                    }
                    if (response['plus']) {
                        Game.notifications.main(response['plus'], 'plus');
                    }
                } else {
                    Game.notifications.main(response['text'], 'error');
                } 
            }
        });
    }
}

// Popup с выигрышем (в центре экрана)
function showCaseRewardPopup(img, text) {
    $('.case-reward-popup').stop(true, true).remove();
    let html = `<div class="case-reward-popup">${img ? `<img src="${img}" alt="">` : ''}<div>${text || 'Вы выиграли приз!'}</div></div>`;
    $(html).appendTo('body').hide().fadeIn(220);
    setTimeout(() => {
        $('.case-reward-popup').fadeOut(850, function(){ $(this).remove(); });
    }, 2100);
}

function caseRollAnimation3x(wrapBoxSelector, visibleCount, winIndex, onEnd, instant) {
    showRollMarker();
    let $wrap = $(wrapBoxSelector);
    let $inner = $wrap.find('.case-modal__roll-list');
    let $items = $inner.find('li');
    let itemWidth = $items.outerWidth(true);
    let centerIdx = Math.floor(visibleCount / 2);

    // Клонируем элементы, если их мало
    let minItems = 18;
    if ($items.length < minItems) {
        let clones = [];
        for (let i = 0; i < minItems - $items.length; ++i) {
            clones.push($items.eq(i % $items.length).clone(true));
        }
        $inner.append(clones);
        $items = $inner.find('li');
    }

    // Сброс к начальному положению
    $inner.css({ transition: 'none', transform: 'translateX(0)' });
    $items.removeClass('case-roll-win');

    // Вычисляем смещение так, чтобы выигрыш оказался по центру!
    let totalItems = $items.length;
    let fullSpins = 3;

    // Индекс выигрышного элемента в расширенном массиве
    // (например, если у нас 7 элементов, после клонирования их стало 18 — winIndex должен быть среди этих 18)
    // НАДЁЖНО: просто берём winIndex из оригинальных, прибавляем N*items.length (N=кол-во оборотов)
    let targetIdx = winIndex + fullSpins * $items.length;

    let scrollTo = (targetIdx - centerIdx) * itemWidth;

    if (instant) {
        // Без анимации: выигрыш по центру
        $inner.css({ transition: 'none', transform: `translateX(-${(winIndex - centerIdx) * itemWidth}px)` });
        $items.removeClass('case-roll-win');
        $items.eq(winIndex).addClass('case-roll-win');
        if (typeof onEnd === 'function') onEnd(centerIdx);
        return;
    }

    setTimeout(function () {
        $inner.css('transition', 'transform 2600ms cubic-bezier(.22,1,.36,1)');
        setTimeout(function () {
            $inner.css('transform', `translateX(-${scrollTo}px)`);
        }, 35);

        setTimeout(function () {
            // После анимации: подсвечиваем элемент по центру
            $items = $inner.find('li'); // обновляем коллекцию
            $items.removeClass('case-roll-win');
            $items.eq(centerIdx).addClass('case-roll-win');
            // Фиксируем transform, чтобы центр не двигался
            $inner.css({ transition: 'none', transform: `translateX(-${(targetIdx - centerIdx) * itemWidth}px)` });
            if (typeof onEnd === 'function') onEnd(centerIdx);
        }, 2600 + 80);
    }, 30);
}

// Показать маркер по центру рулетки (треугольник)
function showRollMarker() {
    if (!$('.case-modal__roll-marker').length) {
        $('.case-modal__roll').append('<div class="case-modal__roll-marker"></div>');
    }
}
function addEV(type, stat, e, count = false, pokID) {
    if (type == 'open') {
        // Закрываем существующие модальные окна
        $('.MiniModal.ev-modal').remove();
        
        // Определяем мобильное устройство
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                        ('ontouchstart' in window) || 
                        (navigator.maxTouchPoints > 0) ||
                        window.innerWidth <= 768;
        
        // Сначала получаем актуальные данные покемона
        $.ajax({
            url: "/do/PokemonTeam",
            type: "POST",
            data: {
                type: 'info',
                other: pokID
            },
            success: function(response) {
                try {
                    if (typeof response === 'string') {
                        response = (typeof response === 'string') ? JSON.parse(response) : response;
                    }
                    
                    // Извлекаем данные из HTML ответа
                    const $html = $(response.html);
                    
                    // Получаем общие доступные EV
                    let availableEV = 0;
                    const $evStep = $html.find('.Step.evPok .Other');
                    if ($evStep.length) {
                        availableEV = parseInt($evStep.text()) || 0;
                    }
                    
                    // Получаем текущие EV для конкретного стата из data-title
                    let currentStatEV = 0;
                    const $statElements = $html.find('.Stat .Progress[data-title]');
                    if ($statElements.length > stat) {
                        const dataTitle = $statElements.eq(stat).attr('data-title');
                        const evMatch = dataTitle.match(/EV:\s*(\d+)\s*\/\s*126/);
                        if (evMatch) {
                            currentStatEV = parseInt(evMatch[1]) || 0;
                        }
                    }
                    
                    // Названия статов
                    const statNames = ['HP', 'Атака', 'Защита', 'Скорость', 'Сп.Атк', 'Сп.Защ'];
                    const statName = statNames[stat] || `Стат ${stat}`;
                    
                    // Расчеты для кнопок
                    const maxForStat = 126 - currentStatEV; // Сколько можно добавить до максимума стата
                    const maxCanAdd = Math.min(maxForStat, availableEV); // Реально можно добавить
                    const quick10 = Math.min(10, maxCanAdd);
                    const quickAll = Math.min(availableEV, maxCanAdd);
                    const quickMax = Math.min(maxForStat, availableEV); // MAX кнопка
                    
                    console.log('EV Data:', {
                        statName,
                        currentStatEV,
                        availableEV,
                        maxForStat,
                        maxCanAdd,
                        quickMax,
                        isMobile
                    });
                    
                    // Компактные стили с мобильной адаптацией
                    if (!$('#ev-compact-mobile-css').length) {
                        $('<style id="ev-compact-mobile-css">\
                            .MiniModal.ev-modal{background:#fff;border:1px solid #ddd;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,0.12);width:240px;font-family:system-ui,-apple-system,sans-serif;font-size:12px;position:fixed;z-index:910000}\
                            .MiniModal.ev-modal .Name{background:#f5f5f5;color:#333;padding:10px 14px;border-radius:6px 6px 0 0;font-weight:600;font-size:13px;cursor:move;border-bottom:1px solid #eee;text-align:center;box-sizing:border-box}\
                            .MiniModal.ev-modal .Content{padding:0;background:#fff;border-radius:0 0 6px 6px}\
                            .MiniModal.ev-modal .Settings{padding:14px;box-sizing:border-box}\
                            \
                            .ev-info{display:flex;justify-content:space-between;margin-bottom:12px;text-align:center;font-size:11px}\
                            .ev-info-item{flex:1;padding:6px 4px;background:#f9f9f9;border-radius:4px;margin:0 2px}\
                            .ev-info-label{color:#666;display:block;margin-bottom:2px;font-size:10px;text-transform:uppercase}\
                            .ev-info-value{font-weight:700;color:#333;font-size:12px}\
                            \
                            .ev-buttons{display:flex;gap:4px;margin-bottom:12px}\
                            .ev-btn{flex:1;background:#f8f9fa;border:1px solid #ddd;color:#333;padding:6px 4px;border-radius:4px;cursor:pointer;font-weight:600;font-size:11px;transition:all 0.15s;text-align:center}\
                            .ev-btn:hover:not(.disabled),.ev-btn:active:not(.disabled){background:#007bff;color:#fff;border-color:#007bff}\
                            .ev-btn.disabled{opacity:0.4;cursor:not-allowed;background:#f5f5f5}\
                            \
                            .ev-input{display:flex;gap:6px}\
                            .MiniModal.ev-modal .Step{margin:0;flex:1;display:flex}\
                            .MiniModal.ev-modal .Step input{width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;font-size:11px;box-sizing:border-box}\
                            .MiniModal.ev-modal .Step input:focus{outline:none;border-color:#007bff}\
                            .ev-add{background:#007bff;color:#fff;border:none;padding:6px 10px;border-radius:4px;cursor:pointer;font-weight:600;font-size:11px;white-space:nowrap}\
                            .ev-add:hover,.ev-add:active{background:#0056b3}\
                            \
                            /* Мобильные стили */\
                            @media (max-width: 768px), (pointer: coarse) {\
                                .MiniModal.ev-modal{width:260px;max-width:90vw}\
                                .MiniModal.ev-modal .Name{padding:12px 16px;width: 260px;font-size:14px;touch-action:manipulation}\
                                .MiniModal.ev-modal .Settings{padding:16px}\
                                .ev-info{margin-bottom:14px}\
                                .ev-info-item{padding:8px 6px}\
                                .ev-info-label{font-size:11px}\
                                .ev-info-value{font-size:13px}\
                                .ev-buttons{gap:6px;margin-bottom:14px}\
                                .ev-btn{padding:10px 6px;font-size:12px;touch-action:manipulation}\
                                .ev-input{gap:8px}\
                                .MiniModal.ev-modal .Step input{padding:10px 12px;font-size:14px}\
                                .ev-add{padding:10px 14px;font-size:12px;touch-action:manipulation}\
                            }\
                            \
                            /* Предотвращение выделения текста при перетаскивании */\
                            .MiniModal.ev-modal .Name{user-select:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none}\
                        </style>').appendTo('head');
                    }
                    
                    // Создаем модальное окно
                    const modalHtml = `
                        <div class="MiniModal ev-modal">
                            <div class="Name" id="drgMini">${statName} EV</div>
                            <div class="Content">
                                <div class="Settings">
                                    <div class="ev-info">
                                        <div class="ev-info-item">
                                            <span class="ev-info-label">В стате</span>
                                            <span class="ev-info-value">${currentStatEV}/126</span>
                                        </div>
                                        <div class="ev-info-item">
                                            <span class="ev-info-label">Доступно</span>
                                            <span class="ev-info-value">${availableEV}</span>
                                        </div>
                                        <div class="ev-info-item">
                                            <span class="ev-info-label">Можно</span>
                                            <span class="ev-info-value">${maxCanAdd}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="ev-buttons">
                                        <button class="ev-btn ${quick10 <= 0 ? 'disabled' : ''}" 
                                                onclick="if(!$(this).hasClass('disabled')){addEV('add',${stat},this,10,${pokID});}">
                                            +10
                                        </button>
                                        <button class="ev-btn ${quickAll <= 0 ? 'disabled' : ''}" 
                                                onclick="if(!$(this).hasClass('disabled')){addEV('add',${stat},this,${quickAll},${pokID});}">
                                            +${quickAll}
                                        </button>
                                        <button class="ev-btn ${quickMax <= 0 ? 'disabled' : ''}" 
                                                onclick="if(!$(this).hasClass('disabled')){addEV('add',${stat},this,${quickMax},${pokID});}">
                                            MAX
                                        </button>
                                    </div>
                                    
                                    <div class="ev-input">
                                        <div class="Step">
                                            <input type="number" 
                                                   placeholder="1-${maxCanAdd}" 
                                                   min="1" 
                                                   max="${maxCanAdd}"
                                                   onkeydown="if(event.keyCode == 13){addEV('add',${stat},this,$(this).val(),${pokID});}">
                                        </div>
                                        <button class="ev-add" 
                                                onclick="addEV('add',${stat},this,$(this).prev().find('input').val(),${pokID});">
                                            Добавить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    const $modal = $(modalHtml);
                    $('body').append($modal);
                    
                    // Делаем перетаскиваемым
                    if (typeof $.fn.draggabilly !== 'undefined') {
                        $modal.draggabilly({
                            handle: '#drgMini',
                            containment: true
                        });
                    }
                    
                    // АДАПТИВНОЕ ПОЗИЦИОНИРОВАНИЕ
                    const modalWidth = $modal.outerWidth();
                    const modalHeight = $modal.outerHeight();
                    const windowWidth = $(window).width();
                    const windowHeight = $(window).height();
                    
                    if (isMobile) {
                        // НА МОБИЛЬНЫХ: позиционируем по центру
                        const left = (windowWidth - modalWidth) / 2;
                        const top = (windowHeight - modalHeight) / 2;
                        
                        $modal.css({
                            left: Math.max(10, left) + 'px',
                            top: Math.max(10, top) + 'px'
                        });
                        
                        console.log('Mobile positioning:', { left, top, modalWidth, modalHeight });
                        
                    } else {
                        // НА ДЕСКТОПЕ: позиционируем возле триггера
                        try {
                            const $trigger = $(e);
                            const triggerOffset = $trigger.offset();
                            if (triggerOffset) {
                                let left = triggerOffset.left + $trigger.outerWidth() + 10;
                                let top = triggerOffset.top - 20;
                                
                                // Проверяем границы экрана
                                if (left + modalWidth > windowWidth - 20) {
                                    left = triggerOffset.left - modalWidth - 10;
                                }
                                if (left < 20) left = 20;
                                if (top < 20) top = 20;
                                if (top + modalHeight > windowHeight - 20) {
                                    top = windowHeight - modalHeight - 20;
                                }
                                
                                $modal.css({
                                    left: left + 'px',
                                    top: top + 'px'
                                });
                                
                                console.log('Desktop positioning:', { left, top, triggerOffset });
                            }
                        } catch (err) {
                            console.log('Fallback to center positioning');
                            // Fallback: центрируем
                            const left = (windowWidth - modalWidth) / 2;
                            const top = (windowHeight - modalHeight) / 2;
                            
                            $modal.css({
                                left: Math.max(10, left) + 'px',
                                top: Math.max(10, top) + 'px'
                            });
                        }
                    }
                    
                    // Закрытие по клику вне модального окна (только для десктопа)
                    if (!isMobile) {
                        setTimeout(function() {
                            $(document).on('click.evModal', function(event) {
                                if (!$(event.target).closest('.MiniModal.ev-modal').length) {
                                    $('.MiniModal.ev-modal').remove();
                                    $(document).off('click.evModal');
                                }
                            });
                        }, 100);
                    }
                    
                    // Фокус на input (только для десктопа)
                    if (!isMobile) {
                        setTimeout(function() {
                            $modal.find('input').focus();
                        }, 100);
                    }
                    
                } catch (e) {
                    console.error('Error parsing pokemon data:', e);
                    Game.notifications.main('Ошибка получения данных покемона', 'error');
                }
            },
            error: function() {
                Game.notifications.main('Ошибка загрузки данных покемона', 'error');
            }
        });
        
    } else if (type == "add") {
        $('.MiniModal.ev-modal').remove();
        $(document).off('click.evModal');
        
        count = parseInt(count);
        
        if (!count || count <= 0) {
            Game.notifications.main('Введите корректное количество EV', 'error');
            return;
        }
        
        if (count > 126) {
            Game.notifications.main('Максимальное количество EV за раз: 126', 'error');
            return;
        }
        
        $.ajax({
            url: "/do/pokemonsAction",
            type: "POST",
            data: {
                type: 'addEV',
                stat: stat,
                pokID: pokID,
                count: count
            },
            success: function (response) {
                try {
                    if (typeof response === 'string') {
                        response = (typeof response === 'string') ? JSON.parse(response) : response;
                    }
                    
                    if (response && response.error == 0) {
                        Game.notifications.main(response.text, 'success');
                        
                        // Обновляем отображение доступных EV в интерфейсе
                        if (response.ev !== undefined) {
                            $('.Step.evPok .Other').text(response.ev);
                        }
                        
                        // Обновляем статы если открыт детальный просмотр
                        if ($('#StatsPokemon' + pokID).length) {
                            setTimeout(function() {
                                Game.pokemonTeamTabs(pokID, 'stats');
                            }, 100);
                        }
                        
                        // Обновляем основное окно покемона
                        setTimeout(function() {
                            Game.pokemonTeamTabs(pokID, 'info');
                        }, 100);
                        
                    } else {
                        Game.notifications.main(response.text || 'Ошибка добавления EV', 'error');
                    }
                } catch (e) {
                    console.error('Error parsing EV response:', e);
                    Game.notifications.main('Ошибка обработки ответа сервера', 'error');
                }
            },
            error: function() {
                Game.notifications.main('Ошибка соединения с сервером', 'error');
            }
        });
    }
}
/* =========================
   ГЛОБАЛЬНЫЙ СПОТЛАЙТ-ГИД
   ========================= */
(function GuideBoot(){
  if (window.__GUIDE__) return;

  /* ---------- ИДЕНТИФИКАЦИЯ ИГРОКА + Неймспейс ключей ---------- */
  function readCookie(n){
    try{
      const m = document.cookie.match(new RegExp('(?:^|; )'+n.replace(/([.$?*|{}()[\]\\/+^])/g,'\\$1')+'=([^;]*)'));
      return m ? decodeURIComponent(m[1]) : null;
    }catch(e){ return null; }
  }
  function guessPlayerToken(){
    try{
      const candList = [
        (window.PLAYER_ID),
        (window.playerId),
        (window.USER_ID),
        (window.userId),
        (window.USER && (USER.id || USER.user_id)),
        (window.User && (User.id || User.user_id)),
        (window.Auth && Auth.user && (Auth.user.id || Auth.user.uid)),
        (window.Game && Game.user && (Game.user.id || Game.user.uid || Game.user.user_id)),
        (window.Game && Game.profile && (Game.profile.id || Game.profile.uid)),
        (document.body && (document.body.getAttribute('data-user-id') || (document.body.dataset && document.body.dataset.userId))),
        readCookie('user_id') || readCookie('userid') || readCookie('uid') || readCookie('player_id')
      ].filter(Boolean);
      let tok = candList.length ? String(candList[0]) : null;
      if (!tok && window.Game && Game.user && (Game.user.login || Game.user.username || Game.user.name)){
        tok = String(Game.user.login || Game.user.username || Game.user.name);
      }
      if (!tok){
        tok = localStorage.getItem('pp:guide:uid');
        if (!tok){
          tok = 'guest-' + Math.random().toString(36).slice(2,8);
          localStorage.setItem('pp:guide:uid', tok);
        }
      }
      return String(tok).replace(/[^a-z0-9_-]/gi,'').toLowerCase();
    }catch(e){
      return 'guest';
    }
  }
  const PLAYER_TOKEN = guessPlayerToken();
  const K = (name)=> `pp:guide:${PLAYER_TOKEN}:${name}`;

  // миграция старых общих ключей в персональные
  (function migrateLegacy(){
    try{
      const legacy = [
        ['q1done',        'pp:guide:q1done'],
        ['q1progress',    'pp:guide:q1progress'],
        ['arm-on-enter',  'pp:guide:arm-on-enter'],
        ['mom-done',      'pp:guide:mom-done'],
        ['oak-final',     'pp:guide:oak-final']
      ];
      for (const [n, oldKey] of legacy){
        const newKey = K(n);
        if (localStorage.getItem(oldKey)!=null && localStorage.getItem(newKey)==null){
          localStorage.setItem(newKey, localStorage.getItem(oldKey));
        }
      }
    }catch(e){}
  })();

  /* ---------- Персональные ключи ---------- */
  const LS_Q1_DONE      = K('q1done');
  const LS_Q1_STATE     = K('q1state');
  const LS_Q1_PROG      = K('q1progress');
  const LS_MOM_DONE     = K('mom-done');     // после диалога с Мамой запускаем гид
  const LS_OAK_FINAL    = K('oak-final');    // встреча с Оуком на финальной стадии (вторая по маршруту)

  const QUEST_ROUTE = [
    {type:'npc',      slug:'mom',       name:'Мама'},
    {type:'location', slug:'alabastia', name:'Алабастия'},
    {type:'location', slug:'academy',   name:'Академия'},
    {type:'npc',      slug:'jack',      name:'Куратор Джек'},
    {type:'location', slug:'alabastia', name:'Алабастия'},
    {type:'npc',      slug:'passerby',  name:'Прохожий'},
    {type:'location', slug:'road_1',    name:'Дорога 1'},
    {type:'npc',      slug:'prof_oak',  name:'Профессор Оук'},
    {type:'npc',      slug:'bag',       name:'Сумка'},
    {type:'npc',      slug:'prof_oak',  name:'Профессор Оук'},
    {type:'location', slug:'alabastia', name:'Алабастия'},
    {type:'location', slug:'academy',   name:'Академия'},
    {type:'npc',      slug:'jack',      name:'Куратор Джек'} // финал: после возврата от Оука
  ];

  const SCOPES = {
    tabWorld: ['button[data-ui="tab-world"]','button#tabWorld','.tabs .tab-world','[data-tab="world"]','.bottom-tabs .tab-world'],
    leftBox:  ['.DivMap .Left .Steps','.available-locations','[data-ui="locations-list"]','.locations','.locations-list'],
    rightBox: ['.DivMap .Right .Steps','.npc-list','[data-ui="npc-list"]','.right-characters'],
    locationTargets: ['.DivMap .Left .Steps *','.available-locations *','[data-ui="locations-list"] *','.locations *'],
    npcTargets: ['.DivMap .Right .Steps *','.npc-list *','[data-ui="npc-list"] *','.right-characters *'],
    worldRoot: ['.DivMap']
  };
  const LOC = { academy:['Академия','Academy'], alabastia:['Алабастия','Alabastia','Pallet','Паллет'], home:['Дом','Home'], road_1:['Дорога 1','Маршрут 1','Route 1'], route_1:['Route 1','Маршрут 1','Дорога 1'], doroga_1:['Дорога 1','Маршрут 1','Route 1'] };
  const NPC = { mom:['Мама','Mother'], jack:['Куратор Джек','Джек','Researcher Jack','Jack'], passerby:['Прохожий','Passerby'], prof_oak:['Профессор Оук','Оук','Professor Oak','Oak'], bag:['Сумка','Bag'] };

  /* ---------- Стили: сглаживание анимаций спотлайта ---------- */
  (function ensureGuideStyles(){
    if (document.getElementById('guide-style-v4')) return;
    const css = `
:root{--tour-color:#8a2be2;--tour-text:#f6f3ff;--tour-dim:rgba(16,10,31,.55)}
.ui-tour-root{position:fixed;inset:0;z-index:2147483000;pointer-events:none;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,'Noto Sans',sans-serif}
.ui-tour-mask{position:fixed;background:var(--tour-dim);pointer-events:auto;opacity:0;transition:opacity .18s cubic-bezier(.2,.7,.2,1), left .18s cubic-bezier(.2,.7,.2,1), top .18s cubic-bezier(.2,.7,.2,1), width .18s cubic-bezier(.2,.7,.2,1), height .18s cubic-bezier(.2,.7,.2,1);will-change:left,top,width,height,opacity}
.ui-tour-root.show .ui-tour-mask{opacity:1}
.ui-tour-ring{position:fixed;border:3px solid #fff;border-radius:14px;box-shadow:0 0 0 2px var(--tour-color),0 0 26px rgba(138,43,226,.55);transition:left .18s cubic-bezier(.2,.7,.2,1), top .18s cubic-bezier(.2,.7,.2,1), width .18s cubic-bezier(.2,.7,.2,1), height .18s cubic-bezier(.2,.7,.2,1);will-change:left,top,width,height;pointer-events:none}
.ui-tour-tooltip{position:fixed;max-width:min(380px, calc(100vw - 24px));background:linear-gradient(180deg,#22183e,#1a1232);color:var(--tour-text);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:12px 12px 10px;box-shadow:0 14px 40px rgba(0,0,0,.35);pointer-events:auto;opacity:0;transform:translateY(4px);transition:opacity .16s ease,transform .16s ease}
.ui-tour-tooltip.show{opacity:1;transform:none}
.ui-tour-tip-arrow{position:absolute;width:12px;height:12px;background:linear-gradient(180deg,#22183e,#1a1232);transform:rotate(45deg);border-left:1px solid rgba(255,255,255,.12);border-top:1px solid rgba(255,255,255,.12)}
.ui-tour-actions{display:flex;gap:8px;align-items:center;justify-content:flex-end;margin-top:8px}
.ui-tour-btn{appearance:none;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.08);color:#fff;border-radius:10px;font-size:13px;padding:7px 10px;cursor:pointer}
.ui-tour-btn:hover{background:rgba(255,255,255,.14)}
.ui-tour-btn.primary{border-color:transparent;background:var(--tour-color)}
.ui-tour-progress{position:fixed;right:10px;top:10px;font-size:12px;color:#fff;background:rgba(0,0,0,.35);padding:6px 8px;border-radius:10px;transition:opacity .16s ease}
@keyframes tour-tap{0%{transform:translate(0,0) scale(1)}50%{transform:translate(2px,2px) scale(1.06)}100%{transform:translate(0,0) scale(1)}}
.ui-tour-finger{position:fixed;pointer-events:none;transition:left .18s cubic-bezier(.2,.7,.2,1), top .18s cubic-bezier(.2,.7,.2,1);will-change:left,top}
.ui-tour-finger svg{width:26px;height:26px;animation:tour-tap 1s ease-in-out infinite;filter:drop-shadow(0 2px 6px rgba(0,0,0,.35))}
@media (max-width:800px){.ui-tour-tooltip{max-width:calc(100vw - 16px)}}
`;
    const st = document.createElement('style');
    st.id = 'guide-style-v4';
    st.appendChild(document.createTextNode(css));
    document.head.appendChild(st);
  })();

  /* ---------- Утилиты ---------- */
  const norm = s => String(s||'').replace(/\s+/g,' ').trim().toLowerCase();
  function firstVisible(sel){
    const arr = Array.isArray(sel)?sel:[sel];
    for (let s of arr){
      let nodes=[]; try{ nodes=[...document.querySelectorAll(s)] }catch(e){ nodes=[]; }
      for (let el of nodes){
        const r = el.getBoundingClientRect();
        if (r.width>1 && r.height>1 && r.bottom>0 && r.top<innerHeight) return el;
      }
    }
    return null;
  }
  function findByText(scopeSelectors, texts){
    const want = (Array.isArray(texts)?texts:[texts]).map(norm);
    const scopes = Array.isArray(scopeSelectors)?scopeSelectors:[scopeSelectors];
    for (let s of scopes){
      let nodes=[]; try{ nodes=[...document.querySelectorAll(s)] }catch(e){ nodes=[]; }
      for (let el of nodes){
        const txt = norm(el.textContent||'');
        if (!txt) continue;
        for (let w of want){ if (txt.includes(w)){ const r=el.getBoundingClientRect(); if(r.width>1 && r.height>1) return el; } }
      }
    }
    return null;
  }
  function modalIsOpen(){ return !!document.querySelector('.DivNpcBlock'); }
  function getProg(){ try{ return Math.max(0, parseInt(localStorage.getItem(LS_Q1_PROG)||'0',10)); }catch(e){ return 0; } }
  function setProg(i){ try{ localStorage.setItem(LS_Q1_PROG, String(Math.max(0,i))); }catch(e){} }
  function isDone(){ try{ return localStorage.getItem(LS_Q1_DONE)==='1'; }catch(e){ return false; } }

  /* ---------- Тур ---------- */
  class Tour {
    constructor(){ this.root=null; this.steps=[]; this.idx=0; this.activeEl=null; this.waiter=null; this.observers=[]; this.clickHandler=null; this.paused=false; }
    ui(){
      if (this.root) return this.root;
      const root = document.createElement('div'); root.className='ui-tour-root'; root.style.display='none';
      root.innerHTML = `
        <div class="ui-tour-mask" data-mask="top"></div>
        <div class="ui-tour-mask" data-mask="left"></div>
        <div class="ui-tour-mask" data-mask="right"></div>
        <div class="ui-tour-mask" data-mask="bottom"></div>
        <div class="ui-tour-ring"></div>
        <div class="ui-tour-finger"><svg viewBox="0 0 24 24" fill="white"><path d="M9 11V6a2 2 0 1 1 4 0v5h1.5V8.5a2 2 0 1 1 4 0V11H19a2 2 0 0 1 2 2v2.5a5.5 5.5 0 0 1-5.5 5.5H12a6 6 0 0 1-6-6V13a2 2 0 0 1 2-2h1z"/></svg></div>
        <div class="ui-tour-tooltip"><div class="ui-tour-tip-arrow"></div><div class="ui-tour-text"></div><div class="ui-tour-actions"><button class="ui-tour-btn" data-act="skip">Пропустить</button><span style="flex:1"></span><button class="ui-tour-btn" data-act="back">Назад</button><button class="ui-tour-btn primary" data-act="next">Далее</button></div></div>
        <div class="ui-tour-progress"></div>`;
      document.body.appendChild(root);
      root.querySelector('[data-act="skip"]').addEventListener('click', ()=>this.stop());
      root.querySelector('[data-act="back"]').addEventListener('click', ()=>this.prev());
      root.querySelector('[data-act="next"]').addEventListener('click', ()=>this.next());
      this.root = root; return root;
    }
    start(steps, startIndex=0){
      if (!steps || !steps.length) return;
      this.steps = steps.map(s=>Object.assign({advanceOn:'clickTarget', margin:12, timeoutMs:30000}, s));
      this.idx = Math.min(Math.max(0,startIndex), this.steps.length-1);
      this.paused = false;
      this.ui().style.display='block';
      this.update();
    }
    stop(){
      if (!this.root) return;
      this.teardown();
      this.root.classList.remove('show');
      this.root.style.display='none';
    }
    pause(){ this.paused = true; this.stop(); }
    resume(){ if (!this.paused) return; this.paused = false; this.ui().style.display='block'; this.update(); }
    next(){ if (this.idx < this.steps.length-1){ this.idx++; this.update(); } else { this.stop(); } }
    prev(){ if (this.idx > 0){ this.idx--; this.update(); } }
    resolve(step){
      let el = null;
      if (step.selector) el = firstVisible(step.selector);
      if (!el && step.finder && step.finder.scope && step.finder.text) el = findByText(step.finder.scope, step.finder.text);
      if (!el && step.fallbackSelector) el = firstVisible(step.fallbackSelector);
      return el;
    }
    waitFor(step, cb){
      const t0 = performance.now();
      const tryFind = ()=>{
        if (this.paused || modalIsOpen()) { this.stop(); return; }
        const el = this.resolve(step);
        if (el){ cb(el); return; }
        if (performance.now()-t0 > (step.timeoutMs||30000)) return;
        this.waiter = requestAnimationFrame(tryFind);
      };
      this.waiter = requestAnimationFrame(tryFind);
    }
    update(){
      if (this.paused || modalIsOpen()) { this.stop(); return; }
      const step = this.steps[this.idx]; if (!step) return;
      this.root.querySelector('.ui-tour-progress').textContent = (this.idx+1)+' / '+this.steps.length;

      if (this.waiter) cancelAnimationFrame(this.waiter);
      this.teardown();

      this.waitFor(step, (el)=>{
        this.activeEl = el;
        this.root.classList.add('show');
        this.position(el, step);
        this.makeInteractive(el, step);
        this.observe(el, step);
        scrollParentToReveal(el);
      });
    }
    position(el, step){
      const r = el.getBoundingClientRect(), m = step.margin||12;
      const x = Math.max(0, r.left - m), y = Math.max(0, r.top - m), w = r.width + m*2, h = r.height + m*2;
      const vw = innerWidth, vh = innerHeight;

      const masks = {
        top: this.root.querySelector('[data-mask="top"]'),
        left: this.root.querySelector('[data-mask="left"]'),
        right: this.root.querySelector('[data-mask="right"]'),
        bottom: this.root.querySelector('[data-mask="bottom"]')
      };
      masks.top.style.cssText    = `left:0;top:0;width:${vw}px;height:${y}px`;
      masks.left.style.cssText   = `left:0;top:${y}px;width:${x}px;height:${h}px`;
      masks.right.style.cssText  = `left:${x+w}px;top:${y}px;width:${Math.max(0,vw-(x+w))}px;height:${h}px`;
      masks.bottom.style.cssText = `left:0;top:${y+h}px;width:${vw}px;height:${Math.max(0,vh-(y+h))}px`;

      const ring = this.root.querySelector('.ui-tour-ring');
      ring.style.cssText = `left:${x}px;top:${y}px;width:${w}px;height:${h}px`;

      const finger = this.root.querySelector('.ui-tour-finger');
      finger.style.cssText = `left:${x+w-30}px;top:${y+h-30}px`;

      const tip = this.root.querySelector('.ui-tour-tooltip');
      const arrow = tip.querySelector('.ui-tour-tip-arrow');
      tip.querySelector('.ui-tour-text').textContent = step.text || step.label || '';
      tip.classList.add('show');

      const spaces = {bottom: vh-(y+h), top: y, right: vw-(x+w), left: x};
      const side = Object.entries(spaces).sort((a,b)=>b[1]-a[1])[0][0];
      const pad = 12; let tx=0, ty=0, aw=12;
      if (side==='bottom'){ ty=y+h+pad; tx=Math.min(Math.max(6,x), vw-6-tip.offsetWidth); arrow.style.cssText=`left:${Math.min(Math.max(x+w/2-aw/2,18),vw-18)}px;top:${ty-pad+2}px`; }
      else if (side==='top'){ ty=Math.max(10,y-tip.offsetHeight-pad); tx=Math.min(Math.max(6,x), vw-6-tip.offsetWidth); arrow.style.cssText=`left:${Math.min(Math.max(x+w/2-aw/2,18),vw-18)}px;top:${ty+tip.offsetHeight-6}px`; }
      else if (side==='right'){ tx=x+w+pad; ty=Math.min(Math.max(8,y), vh-8-tip.offsetHeight); arrow.style.cssText=`top:${Math.min(Math.max(y+h/2-aw/2,18),vh-18)}px;left:${tx-pad+2}px`; }
      else { tx=Math.max(8,x-tip.offsetWidth-pad); ty=Math.min(Math.max(8,y), vh-8-tip.offsetHeight); arrow.style.cssText=`top:${Math.min(Math.max(y+h/2-aw/2,18),vh-18)}px;left:${tx+tip.offsetWidth-6}px`; }
      tip.style.left = tx+'px'; tip.style.top = ty+'px';
    }
    makeInteractive(el, step){
      const backBtn = this.root.querySelector('[data-act="back"]');
      backBtn.disabled = (this.idx===0);
      if (this.clickHandler) { el.removeEventListener('click', this.clickHandler, true); this.clickHandler=null; }
      if (step.advanceOn==='clickTarget'){
        this.clickHandler = ()=>setTimeout(()=>this.next(),0);
        el.addEventListener('click', this.clickHandler, true);
      }
    }
    observe(el, step){
      this.observers = [];
      const ro = new ResizeObserver(()=>this.position(el, step));
      ro.observe(el); this.observers.push(ro);
      const onScrollResize = ()=>this.position(el, step);
      window.addEventListener('scroll', onScrollResize, true);
      window.addEventListener('resize', onScrollResize, true);
      this.observers.push({disconnect(){ window.removeEventListener('scroll', onScrollResize, true); window.removeEventListener('resize', onScrollResize, true); }});
    }
    teardown(){
      if (this.waiter) cancelAnimationFrame(this.waiter);
      if (this.clickHandler && this.activeEl){ this.activeEl.removeEventListener('click', this.clickHandler, true); this.clickHandler=null; }
      for (let o of this.observers){ try{o.disconnect && o.disconnect();}catch(e){} }
      this.observers = [];
    }
  }
  const guide = new Tour();

  function stepFromItem(item){
    const isNpc = (item.type||'location').toLowerCase()==='npc';
    const names = isNpc ? (NPC[item.slug]||[item.slug]) : (LOC[item.slug]||[item.slug]);
    const scope = isNpc ? SCOPES.npcTargets : SCOPES.locationTargets;
    const fallback = isNpc ? SCOPES.rightBox : SCOPES.leftBox;
    const verb = isNpc ? 'Поговорите' : 'Перейдите';
    return {
      finder:{scope, text:names},
      fallbackSelector:fallback,
      text:`${verb}: ${item.name || names[0]}`,
      advanceOn: isNpc ? 'manual' : 'clickTarget'
    };
  }
  function buildStepsFrom(index){
    const start = Math.max(0, index|0);
    return QUEST_ROUTE.slice(start).map(stepFromItem);
  }
  function startWhenNoModal(run){
    if (!modalIsOpen()) { run(); return; }
    const mo = new MutationObserver(()=>{ if (!modalIsOpen()){ try{mo.disconnect()}catch(e){}; run(); } });
    mo.observe(document.body, {childList:true, subtree:true});
  }
  function lastIndexBySlug(slug){
    for (let i=QUEST_ROUTE.length-1;i>=0;i--){ if (QUEST_ROUTE[i].slug===slug) return i; }
    return -1;
  }
  function awaitWorldThenStartFromProg(){
    const tryStart = ()=>{
      const world = !!document.querySelector('.DivMap');
      if (world){ __GUIDE__.hardStart(__GUIDE__.getProg()); return true; }
      return false;
    };
    if (tryStart()) return;
    const mo = new MutationObserver(()=>{ if (tryStart()){ try{mo.disconnect();}catch(e){} } });
    mo.observe(document.documentElement, {childList:true, subtree:true});
    setTimeout(()=>{ try{mo.disconnect();}catch(e){}; tryStart(); }, 30000);
  }

  window.__GUIDE__ = {
    isDone,
    getProg, setProg,
    route: QUEST_ROUTE.slice(),
    lastIndexBySlug,
    playerToken: PLAYER_TOKEN,
    startFromIndex(i=0){ startWhenNoModal(()=>guide.start(buildStepsFrom(i), 0)); },
    hardStart(i){
      if (isDone()) return;
      const idx = Math.max(0, i==null?getProg():i);
      setProg(idx);
      startWhenNoModal(()=>guide.start(buildStepsFrom(idx), 0));
    },
    next(){ guide.next(); },
    stop(){ guide.stop(); },
    pause(){ guide.pause(); },
    resumeFromProg(){ if (!isDone()) startWhenNoModal(()=>guide.start(buildStepsFrom(getProg()), 0)); },
    debugReset(){
      try{
        localStorage.removeItem(LS_Q1_DONE);
        localStorage.removeItem(LS_Q1_PROG);
        localStorage.removeItem(LS_Q1_STATE);
        localStorage.removeItem(LS_MOM_DONE);
        localStorage.removeItem(LS_OAK_FINAL);
      }catch(e){}
    }
  };

  // Пауза/резюм при открытии/закрытии NPC-модалки
  (function observeNpcModal(){
    let wasOpen = !!document.querySelector('.DivNpcBlock');
    const mo = new MutationObserver(()=>{
      const now = !!document.querySelector('.DivNpcBlock');
      if (now && !wasOpen) { guide.pause(); }
      if (!now && wasOpen) { if (!isDone() && localStorage.getItem(LS_MOM_DONE)==='1') __GUIDE__.resumeFromProg(); }
      wasOpen = now;
    });
    mo.observe(document.body, {childList:true, subtree:true});
  })();

  // АВТО-ЗАПУСК ПОСЛЕ МАМЫ (и после перезагрузки, если мама уже пройдена)
  (function bootAfterMom(){
    try{
      if (!isDone() && localStorage.getItem(LS_MOM_DONE)==='1'){
        awaitWorldThenStartFromProg();
      }
    }catch(e){}
  })();

  // вспомог прокрутка
  function scrollParentToReveal(el){
    if (!el) return;
    let p = el.parentElement, i=0, scroller=null;
    while(p && i<6){
      const cs=getComputedStyle(p);
      if (/(auto|scroll)/.test(cs.overflow+cs.overflowY+cs.overflowX)){ scroller=p; break; }
      p=p.parentElement; i++;
    }
    if (!scroller){ el.scrollIntoView({block:'center',inline:'nearest'}); return; }
    const box=scroller.getBoundingClientRect(), r=el.getBoundingClientRect();
    const topWanted = scroller.scrollTop + (r.top - box.top) - 8;
    const bottomWanted = scroller.scrollTop + (r.bottom - box.bottom) + 8;
    if (r.top < box.top) scroller.scrollTo({top:topWanted, behavior:'smooth'});
    else if (r.bottom > box.bottom) scroller.scrollTo({top:bottomWanted, behavior:'smooth'});
  }
})();

/* =========================
   NPC DIALOG (твоя модалка)
   ========================= */
(function(){
  // ——— вспомогательное: обновление кнопки арены РБ (молния/часики)
  function updateRbArenaButton(state){
    var $btn = $('#rbArenaBtn');
    if (!$btn.length) $btn = $('[data-rb-npc="1"]').first();
    if (!$btn.length) return;

    if (state === 'queued'){
      $btn.attr('onclick','NpcDialog(666,3,event);')
          .attr('title','Вы в очереди РБ (нажмите, чтобы отменить)')
          .html('<i class="fas fa-hourglass-half"></i>');
    } else if (state === 'idle'){
      $btn.attr('onclick','NpcDialog(666,2,event);')
          .attr('title','Арена РБ: быстрый старт')
          .html('<i class="fas fa-bolt"></i>');
    }
  }
  window.updateRbArenaButton = updateRbArenaButton;

  // ——— на всякий: перехватываем любые успешные ответы НПС и подруливаем кнопку
  $(document).ajaxSuccess(function(e, xhr, settings){
    var url = (settings && settings.url) ? String(settings.url) : '';
    if (!/\/do\/Npc\/?/i.test(url) && !/\/rb\/666\.php/i.test(url)) return;
    var resp=null; try{ resp = JSON.parse(xhr.responseText); }catch(_){}
    if (resp && resp.name === 'NPC Арена РБ' && resp.rb_state){
      updateRbArenaButton(resp.rb_state);
    }
  });

  // ——— базовые стили модалки
  (function ensureStyles() {
    var old10 = document.getElementById('npc-dialog-style-v10');
    if (old10) old10.remove();
    var old11 = document.getElementById('npc-dialog-style-v11');
    if (old11) old11.remove();
    var old12 = document.getElementById('npc-dialog-style-v12');
    if (old12) old12.remove();
    var old13 = document.getElementById('npc-dialog-style-v13');
    if (old13) old13.remove();
    var old14 = document.getElementById('npc-dialog-style-v14');
    if (old14) old14.remove();
    var old15 = document.getElementById('npc-dialog-style-v15');
    if (old15) old15.remove();
    var old16 = document.getElementById('npc-dialog-style-v16');
    if (old16) old16.remove();
    var old17 = document.getElementById('npc-dialog-style-v17');
    if (old17) old17.remove();
    var old18 = document.getElementById('npc-dialog-style-v18');
    if (old18) old18.remove();
    var old19 = document.getElementById('npc-dialog-style-v19');
    if (old19) old19.remove();

    const css = `
/* Root overlay */
.DivNpcBlock{position:fixed!important;inset:0!important;left:0!important;top:0!important;transform:none!important;width:100vw!important;height:100vh!important;max-width:100vw!important;max-height:100vh!important;border-radius:0!important;z-index:2147483646!important;}
.npc-modal-root{position:relative;width:100%;height:100%;}
.npc-overlay{position:absolute;inset:0;background:transparent;backdrop-filter:none;}

/* Sheet */
.npc-sheet{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:760px;max-width:calc(100vw - 24px);height:min(80vh,740px);max-height:calc(100vh - 24px);min-height:460px;display:flex;flex-direction:column;overflow:hidden;border-radius:20px;background:#f2f3f6;border:1px solid #d5d8df;box-shadow:0 24px 70px rgba(0,0,0,.18);}
@media (max-width:800px){
  /* Mobile: inset near-fullscreen; single scroll container */
  .npc-sheet{left:6px;right:6px;top:6px;bottom:calc(6px + env(safe-area-inset-bottom));transform:none;width:auto;max-width:none;height:auto;max-height:none;min-height:0;border-radius:16px;overflow:hidden;}
}

/* Header */
.npc-header{position:relative;z-index:3;display:flex;align-items:center;gap:12px;padding:12px 12px;background:#f6f7f9;border-bottom:1px solid #d5d8df;}
@media (max-width:800px){ .npc-header{position:sticky;top:0;} }
.npc-avatar{width:56px;height:56px;border-radius:14px;object-fit:cover;background:#e6e8ee;box-shadow:0 6px 16px rgba(0,0,0,.12);cursor:pointer;flex:0 0 auto;}
@media (max-width:800px){ .npc-header{padding:9px 9px;gap:9px;} .npc-avatar{width:40px;height:40px;border-radius:12px;} .npc-title{font-size:14px;} }
.npc-titlewrap{display:flex;flex-direction:column;min-width:0;flex:1;}
.npc-title{font-weight:850;letter-spacing:.2px;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:16px;line-height:1.2;}
.npc-subtitle{font-size:12px;color:#6b7280;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.npc-close{border:0;background:#e7e9ee;border-radius:12px;width:36px;height:36px;display:grid;place-items:center;cursor:pointer}
.npc-close:hover{background:#dee1e8}
.npc-close svg path{stroke:#111827}

/* Content layout */
.npc-content{flex:1 1 auto;min-height:0;overflow:hidden;display:grid;grid-template-columns:1fr 300px;gap:12px;padding:12px;}
@media (max-width:800px){
  /* Mobile: chat only; actions in drawer */
  .npc-content{display:flex;flex-direction:column;gap:8px;flex:1 1 auto;min-height:0;overflow:hidden;padding:8px;padding-bottom:76px;}
  .npc-side{display:none;}
}

.npc-main{min-height:0;overflow:hidden;}
.npc-main-scroll{height:100%;overflow:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;touch-action:pan-y;}
@media (max-width:800px){
  .npc-main{flex:1 1 auto;min-height:0;overflow:hidden;}
  .npc-main-scroll{height:100%;overflow:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;touch-action:pan-y;}
}

/* Chat bubbles */
.npc-chat{display:flex;flex-direction:column;gap:10px;padding:2px 2px 10px 2px;}
@media (max-width:800px){ .npc-chat{padding-bottom:86px;} }
.npc-bubble{display:flex;}
.npc-bubble.npc{justify-content:flex-start;}
.npc-bubble.player{justify-content:flex-end;}
.npc-bubble-inner{max-width:min(520px, 92%);padding:12px 12px;border:1px solid #d5d8df;border-radius:16px;background:#ffffff;box-shadow:0 6px 18px rgba(0,0,0,.06);color:#111827;font-size:14px;line-height:1.45;}
.npc-bubble.player .npc-bubble-inner{background:#f3f4f6;border-color:#d5d8df;}
.npc-bubble-rich{max-width:100%;width:100%;}
@media (max-width:800px){ .npc-bubble-inner{font-size:12.5px;padding:9px 9px;border-radius:14px;} }

/* Legacy single-question container (still supported) */
.npc-question{color:#111827;font-size:15px;line-height:1.45;padding:12px 12px;border:1px solid #d5d8df;border-radius:16px;background:#ffffff;box-shadow:0 6px 18px rgba(0,0,0,.06);}
@media (max-width:800px){ .npc-question{font-size:12px;padding:9px 9px;border-radius:14px;} }

/* Side actions */
.npc-side{min-height:0;overflow:hidden;border:1px solid #d5d8df;border-radius:16px;background:#f6f7f9;display:flex;flex-direction:column;}
.npc-side-head{padding:10px 12px;font-size:12px;font-weight:850;color:#111827;border-bottom:1px solid #d5d8df;display:flex;align-items:center;justify-content:space-between;gap:8px;}
.npc-side-count{display:none;}
.npc-side-scroll{flex:1 1 auto;min-height:0;overflow:auto;padding:10px 12px;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;touch-action:pan-y;}
@media (max-width:800px){
  .npc-side{border:1px solid #d5d8df;background:#f6f7f9;border-radius:16px;max-height:34vh;min-height:140px;flex:0 0 auto;}
  .npc-side-head{display:flex;position:sticky;top:0;background:#f6f7f9;}
  .npc-side-scroll{padding:8px 8px;overflow:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;touch-action:pan-y;}
}

.npc-actions{display:grid;grid-template-columns:1fr;gap:8px;}

/* Mobile actions drawer */
.npc-mobile-actionsbar{display:none;}
.npc-mobile-actionsbtn{width:100%;text-align:center;padding:10px 12px;border-radius:14px;border:1px solid #d5d8df;background:#ffffff;color:#111827;cursor:pointer;font-size:13px;font-weight:850;box-shadow:0 6px 18px rgba(0,0,0,.06);}
.npc-mobile-actionsbtn:active{transform:translateY(1px);}

.npc-drawer-backdrop{display:none;position:absolute;inset:0;z-index:5;background:transparent;}
.npc-drawer{display:none;position:absolute;left:8px;right:8px;bottom:calc(12px + env(safe-area-inset-bottom) + 48px);z-index:6;height:min(80vh, calc(100vh - 120px));max-height:calc(100vh - 80px);background:#f6f7f9;border:1px solid #d5d8df;border-radius:16px;box-shadow:0 24px 70px rgba(0,0,0,.18);overflow:hidden;transform:translateY(110%);transition:transform .22s ease;}
.npc-drawer-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 10px;border-bottom:1px solid #d5d8df;background:#f6f7f9;}
.npc-drawer-title{font-weight:850;font-size:12px;color:#111827;}
.npc-drawer-close{border:0;background:#e7e9ee;border-radius:10px;width:32px;height:32px;display:grid;place-items:center;cursor:pointer;color:#111827;font-size:18px;line-height:1;}
.npc-drawer-scroll{flex:1 1 auto;min-height:0;overflow:auto;padding:10px;padding-bottom:calc(12px + env(safe-area-inset-bottom));-webkit-overflow-scrolling:touch;overscroll-behavior:contain;touch-action:pan-y;}

@media (max-width:800px){
  .npc-mobile-actionsbar{position:absolute;left:8px;right:8px;bottom:calc(8px + env(safe-area-inset-bottom));z-index:4;display:flex;gap:10px;}
  .npc-drawer{display:flex;flex-direction:column;}
  .npc-drawer.open{transform:translateY(0);}
  .npc-drawer-backdrop.open{display:block;}
  .npc-sheet.drawer-open .npc-mobile-actionsbar{display:none;}
}


.npc-btn{width:100%;text-align:left;padding:11px 12px;border-radius:14px;border:1px solid #d5d8df!important;background:#ffffff!important;color:#111827!important;cursor:pointer;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.05)!important;}
.npc-btn:hover{background:#f3f4f6!important}
.npc-btn:focus{outline:2px solid #111827;outline-offset:2px}
@media (max-width:800px){ .npc-btn{font-size:12px;padding:9px 9px;border-radius:12px} }
@media (max-width:400px){ .npc-btn{font-size:11px;padding:8px 8px;border-radius:12px} }

/* Shop (Pokemarket) */
.npc-shop{display:flex;flex-direction:column;gap:10px;margin-top:10px;}
.npc-shop-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;position:sticky;top:0;z-index:2;padding:8px;border:1px solid #e3e5eb;border-radius:12px;background:#f7f8fb;}
.npc-shop-tabs{display:flex;gap:8px;}
.npc-tab{border:1px solid #d5d8df;background:#ffffff;border-radius:999px;padding:8px 10px;font-size:13px;cursor:pointer;}
.npc-tab.active{background:#111827;color:#ffffff;border-color:#111827;}
.npc-shop-search{flex:1;min-width:180px;}
.npc-shop-search input{width:100%;padding:9px 10px;border:1px solid #d5d8df;border-radius:12px;background:#fff;}
.npc-shop-balances{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
.npc-shop-balance{display:flex;gap:6px;align-items:center;font-size:12px;color:#374151;background:#ffffff;border:1px solid #d5d8df;border-radius:999px;padding:6px 8px;}
.npc-shop-balance img{width:18px;height:18px;border-radius:6px;object-fit:cover;}

.npc-shop-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
@media (max-width:800px){ .npc-shop-grid{grid-template-columns:1fr;} }
@media (max-width:800px){ .npc-shop-toolbar{padding:6px;gap:8px;} .npc-tab{padding:6px 8px;font-size:12px;} .npc-shop-search input{padding:8px 9px;border-radius:12px;} .npc-shop-card{padding:8px;gap:8px;border-radius:14px;} .npc-shop-img{width:38px;height:38px;border-radius:10px;} .npc-shop-name{font-size:12px;} .npc-shop-sub{font-size:11px;} .npc-shop-qty input{width:60px;padding:6px 7px;border-radius:10px;} .npc-shop-action{padding:7px 9px;font-size:12px;border-radius:12px;} }
.npc-shop[data-shop-mode="sell"] .npc-shop-grid{grid-template-columns:1fr;}
.npc-shop-card{border:1px solid #d5d8df;border-radius:14px;background:#ffffff;box-shadow:0 4px 12px rgba(0,0,0,.05);padding:10px;display:flex;gap:10px;align-items:flex-start;}
.npc-shop-select{display:none;align-items:center;}
.npc-shop-select input{width:18px;height:18px;}
.npc-shop.selecting .npc-shop-select{display:flex;}
.npc-shop-img{width:44px;height:44px;border-radius:10px;background:#f3f4f6;object-fit:contain;flex:0 0 auto;}
.npc-shop-meta{min-width:0;flex:1;}
.npc-shop-name{font-weight:800;font-size:13px;color:#111827;line-height:1.2;}
.npc-shop-sub{font-size:12px;color:#6b7280;margin-top:2px;}
.npc-shop-row{display:flex;align-items:center;justify-content:space-between;margin-top:8px;gap:8px;flex-wrap:wrap;}
.npc-shop-price{font-size:13px;color:#111827;font-weight:800;display:flex;align-items:center;gap:6px;}
.npc-shop-price img{width:18px;height:18px;border-radius:6px;object-fit:cover;}
.npc-shop-qty{display:flex;align-items:center;gap:6px;}
.npc-shop-qty input{width:72px;padding:7px 8px;border:1px solid #d5d8df;border-radius:10px;background:#fff;}
.npc-shop-action{border:1px solid #111827;background:#111827;color:#fff;border-radius:12px;padding:8px 10px;font-size:13px;cursor:pointer;}
.npc-shop-action:disabled{opacity:.5;cursor:not-allowed;}
.npc-shop-action.secondary{background:#fff;color:#111827;}
.npc-shop-note{margin-top:6px;font-size:12px;color:#374151;background:#f3f4f6;border:1px dashed #d5d8df;padding:8px 10px;border-radius:12px;}
/* Shop mobile compact */
@media (max-width:800px){
  .npc-shop-toolbar{gap:8px;padding:7px;border-radius:12px;}
  .npc-tab{padding:7px 9px;font-size:12px;}
  .npc-shop-search input{padding:8px 9px;border-radius:12px;}
  .npc-shop-card{padding:10px;border-radius:14px;}
  .npc-shop-img{width:40px;height:40px;border-radius:10px;}
  .npc-shop-name{font-size:12.5px;}
  .npc-shop-sub{font-size:11.5px;}
  .npc-shop-qty input{width:64px;padding:6px 7px;border-radius:10px;}
  .npc-shop-action{padding:7px 9px;border-radius:12px;font-size:12.5px;}
}


.npc-shop-cartbar{position:sticky;bottom:0;z-index:3;display:none;align-items:center;justify-content:space-between;gap:10px;margin-top:10px;padding:10px;border:1px solid #d5d8df;border-radius:14px;background:#f6f7f9;box-shadow:0 -10px 20px rgba(0,0,0,.05);}
@media (max-width:420px){ .npc-shop-cartbar{flex-direction:column;align-items:stretch;} }
.npc-shop.selecting .npc-shop-cartbar{display:flex;}
.npc-shop-cartmeta{font-size:12px;color:#374151;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;}

/* Portrait overlay (no global dim; keep focus on portrait card only) */
.npc-portrait-overlay{position:absolute;inset:0;z-index:5;display:none;align-items:center;justify-content:center;background:transparent;}
.npc-portrait-overlay.open{display:flex;}
.npc-portrait-card{max-width:92vw;max-height:92vh;background:transparent;border-radius:18px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.35);}
.npc-portrait-img{display:block;max-width:92vw;max-height:92vh;object-fit:contain;background:#111827;}


`;

    const st = document.createElement('style');
    st.id = 'npc-dialog-style-v19';
    st.type = 'text/css';
    st.appendChild(document.createTextNode(css));
    document.head.appendChild(st);
  })();

  // таймеры автозакрытия
  window.__NPC_AUTO_CLOSE_TMR = null;
  window.__NPC_AUTO_CLOSE_INT = null;

  // ——— глобальное закрытие модалки
  window.NpcDialogClose = function(){
    if (window.__NPC_AUTO_CLOSE_TMR){ clearTimeout(window.__NPC_AUTO_CLOSE_TMR); window.__NPC_AUTO_CLOSE_TMR = null; }
    if (window.__NPC_AUTO_CLOSE_INT){ clearInterval(window.__NPC_AUTO_CLOSE_INT); window.__NPC_AUTO_CLOSE_INT = null; }
    $(document).off('keydown.npcModal');
    $('.DivNpcBlock').remove();

    try{
      const { playerToken } = window.__GUIDE__ || {};
      const K = (name)=> `pp:guide:${playerToken||'guest'}:${name}`;
      const LS_Q1_DONE  = K('q1done');
      const LS_MOM_DONE = K('mom-done');
      if (localStorage.getItem(LS_Q1_DONE)==='1') return;
      if (localStorage.getItem(LS_MOM_DONE)==='1'){
        window.__GUIDE__ && __GUIDE__.resumeFromProg && __GUIDE__.resumeFromProg();
      }
    }catch(e){}
  };

  // ——— вспомогательные утилиты
  function slugFromName(nm){
    const t = (nm||'').toLowerCase();
    if (t.includes('мама')) return 'mom';
    if (t.includes('джек')) return 'jack';
    if (t.includes('прохож')) return 'passerby';
    if (t.includes('сумк')) return 'bag';
    if (t.includes('профессор') || t.includes('оук')) return 'prof_oak';
    return null;
  }
  function positionDialog() {
    // фиксируем контейнер модалки на весь экран (само окно — .npc-sheet)
    $('.DivNpcBlock').css({ left:0, top:0, right:0, bottom:0, width:"100vw", height:"100vh", transform:"none" });
  }
  

  // Mobile scroll fallback: works even if game uses global touchmove preventDefault
  // We manually adjust scrollTop for npc scroll regions using window capture listeners.
  window.__npcBindManualScroll = window.__npcBindManualScroll || (function(){
    let bound = false;
    let active = null;
    let startY = 0;
    let startScroll = 0;

    const pickScrollable = (target) => {
      const main = document.getElementById('npc-main-scroll');
      const side = document.getElementById('npc-side-scroll');
      const drawer = document.getElementById('npc-drawer-scroll');
      if (drawer && drawer.contains(target)) return drawer;
      if (main && main.contains(target)) return main;
      if (side && side.contains(target)) return side;
      return null;
    };

    const onStart = (e) => {
      if (!document.querySelector('.DivNpcBlock')) return;
      if (!e || !e.touches || e.touches.length !== 1) return;
      const el = pickScrollable(e.target);
      if (!el) return;
      active = el;
      startY = e.touches[0].clientY;
      startScroll = el.scrollTop || 0;
      try{ e.stopImmediatePropagation(); }catch(_){ }
      try{ e.stopPropagation(); }catch(_){ }
    };

    const onMove = (e) => {
      if (!active) return;
      if (!e || !e.touches || e.touches.length !== 1) return;
      const y = e.touches[0].clientY;
      const dy = y - startY;
      const maxScroll = Math.max(0, (active.scrollHeight || 0) - (active.clientHeight || 0));
      let next = startScroll - dy;
      if (next < 0) next = 0;
      if (next > maxScroll) next = maxScroll;
      active.scrollTop = next;
      try{ e.preventDefault(); }catch(_){ }
      try{ e.stopImmediatePropagation(); }catch(_){ }
      try{ e.stopPropagation(); }catch(_){ }
    };

    const onEnd = () => { active = null; };

    const onPStart = (e) => {
      if (!document.querySelector('.DivNpcBlock')) return;
      if (!e || e.isPrimary === false) return;
      const pt = String(e.pointerType || '').toLowerCase();
      if (pt === 'mouse') return;
      const el = pickScrollable(e.target);
      if (!el) return;
      active = el;
      startY = e.clientY;
      startScroll = el.scrollTop || 0;
      try{ e.preventDefault(); }catch(_){ }
      try{ e.stopImmediatePropagation(); }catch(_){ }
      try{ e.stopPropagation(); }catch(_){ }
    };

    const onPMove = (e) => {
      if (!active) return;
      const pt = String(e.pointerType || '').toLowerCase();
      if (pt === 'mouse') return;
      const dy = (e.clientY - startY);
      const maxScroll = Math.max(0, (active.scrollHeight || 0) - (active.clientHeight || 0));
      let next = startScroll - dy;
      if (next < 0) next = 0;
      if (next > maxScroll) next = maxScroll;
      active.scrollTop = next;
      try{ e.preventDefault(); }catch(_){ }
      try{ e.stopImmediatePropagation(); }catch(_){ }
      try{ e.stopPropagation(); }catch(_){ }
    };

    const onPEnd = () => { active = null; };

    return function bind(){
      if (bound) return;
      bound = true;
      try{
        window.addEventListener('touchstart', onStart, { capture: true, passive: false });
        window.addEventListener('touchmove',  onMove,  { capture: true, passive: false });
        window.addEventListener('touchend',   onEnd,   { capture: true, passive: true  });
        window.addEventListener('touchcancel',onEnd,   { capture: true, passive: true  });
        window.addEventListener('pointerdown', onPStart, { capture: true, passive: false });
        window.addEventListener('pointermove',  onPMove,  { capture: true, passive: false });
        window.addEventListener('pointerup',    onPEnd,   { capture: true, passive: true  });
        window.addEventListener('pointercancel',onPEnd,   { capture: true, passive: true  });
      }catch(e){}
    };
  })();
function bindScrollShadows($el){
    if (!$el || !$el.length) return;
    const el = $el[0];
    const upd = ()=>{
      const hasTop = el.scrollTop>4;
      const hasBot = el.scrollHeight - el.clientHeight - el.scrollTop > 4;
      $el.toggleClass('has-top', hasTop).toggleClass('has-bottom', hasBot);
    };
    $el.on('scroll', upd); setTimeout(upd,0);
  }

  function __npcStripTags(s){
    return String(s || '').replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim();
  }
  function __npcEscapeHtml(s){
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }
  function __npcEscapeJsSingle(s){
    return String(s || '').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\r?\n/g,' ').trim();
  }

  // Player choice -> show as message bubble, then запрос к НПС
  window.__NPC_CHAT_STATE = window.__NPC_CHAT_STATE || {};
  window.NpcDialogPick = function(npcId, stepIdx, label, extra){
    try{ window.__npcCloseDrawer && window.__npcCloseDrawer(); }catch(e){}
    try{
      const key = String(npcId);
      const st = window.__NPC_CHAT_STATE[key] || (window.__NPC_CHAT_STATE[key] = { messages: [] });
      st.messages = st.messages || [];
      const txt = __npcStripTags(label);
      if (txt) {
        st.messages.push({ from: 'player', text: txt });
        // Показать выбор сразу, не дожидаясь ответа сервера
        const chatEl = document.querySelector('.npc-chat');
        if (chatEl) {
          const wrap = document.createElement('div');
          wrap.className = 'npc-bubble player';
          wrap.innerHTML = '<div class="npc-bubble-inner">' + __npcEscapeHtml(txt) + '</div>';
          chatEl.appendChild(wrap);
          const m = document.getElementById('npc-main-scroll');
          if (m) m.scrollTop = m.scrollHeight;
        }
      }
    }catch(e){}
    try{ NpcDialog(npcId, stepIdx, null, extra || {}); }catch(e){ try{ NpcDialog(npcId, stepIdx); }catch(_){ } }
  };



  function __npcFormatNum(n){
    n = parseInt(n, 10);
    if (!isFinite(n)) n = 0;
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function updateShopCartSummary(shopRoot){
    try{
      const shop = (shopRoot instanceof HTMLElement) ? shopRoot : document.querySelector('.npc-shop[data-shop-mode="sell"]');
      if (!shop) return;
      const bar = shop.querySelector('[data-shop-cartbar]');
      if (!bar) return;

      let total = 0;
      let selected = 0;

      shop.querySelectorAll('.npc-shop-card').forEach(card => {
        const cb = card.querySelector('input[data-sell-check]');
        if (!cb || !cb.checked) return;

        const per = parseInt(card.getAttribute('data-sell-per') || '0', 10) || 0;
        const inp = card.querySelector('input[data-qty]');
        let qty = inp ? (parseInt(inp.value || '1', 10) || 1) : 1;
        if (qty < 1) qty = 1;
        const max = inp ? (parseInt(inp.getAttribute('max') || '0', 10) || 0) : 0;
        if (max > 0 && qty > max) qty = max;
        if (inp && String(inp.value) !== String(qty)) inp.value = qty;

        total += per * qty;
        selected += 1;
      });

      const cEl = bar.querySelector('[data-cart-count]');
      const tEl = bar.querySelector('[data-cart-total]');
      if (cEl) cEl.textContent = __npcFormatNum(selected);
      if (tEl) tEl.textContent = __npcFormatNum(total);

      const btn = bar.querySelector('button[data-shop-action="sell_bulk"]');
      if (btn) btn.disabled = (selected === 0);
    }catch(e){}
  }

  function applyShopPatch(patch){
    try{
      const shop = document.querySelector('.npc-shop');
      if (!shop) return false;

      const shopNpcId = shop.getAttribute('data-npc-id');
      if (patch && patch.npc_id && shopNpcId && String(patch.npc_id) !== String(shopNpcId)) return false;

      if (patch.toast){
        let note = shop.querySelector('[data-shop-note]');
        if (!note){
          note = document.createElement('div');
          note.className = 'npc-shop-note';
          note.setAttribute('data-shop-note','');
          shop.insertBefore(note, shop.firstChild);
        }
        note.style.display = '';
        note.textContent = patch.toast;
      }

      if (patch.balances){
        Object.keys(patch.balances).forEach(curId => {
          const el = shop.querySelector('.npc-shop-balance[data-cur="' + curId + '"] b[data-balance]');
          if (el) el.textContent = __npcFormatNum(patch.balances[curId]);
        });
      }

      if (Array.isArray(patch.cards)){
        patch.cards.forEach(c => {
          const card = shop.querySelector('.npc-shop-card[data-item-id="' + c.item_id + '"]');
          if (!card) return;

          const stockEl = card.querySelector('b[data-stock]');
          if (stockEl && typeof c.stock !== 'undefined') stockEl.textContent = String(c.stock);

          const ownEl = card.querySelector('b[data-own]');
          if (ownEl && typeof c.own !== 'undefined') ownEl.textContent = __npcFormatNum(c.own);

          const mode = shop.getAttribute('data-shop-mode');
          const qtyInp = card.querySelector('input[data-qty]');
          if (qtyInp && mode === 'sell' && typeof c.own !== 'undefined'){
            const max = Math.max(1, parseInt(c.own || '1', 10) || 1);
            qtyInp.setAttribute('max', String(max));
            const cur = parseInt(qtyInp.value || '1', 10) || 1;
            if (cur > max) qtyInp.value = String(max);
            if ((parseInt(c.own, 10) || 0) <= 0) card.style.display = 'none';
          }

          const buyBtn = card.querySelector('button[data-shop-action="buy"]');
          if (buyBtn && typeof c.buy_disabled !== 'undefined') buyBtn.disabled = !!c.buy_disabled;
        });
      }

      updateShopCartSummary(shop);
      return true;
    }catch(e){
      return false;
    }
  }
  // ——— основной вызов НПС
  window.NpcDialog = function NpcDialog(id, step = false, e, extra = {}) {
    e = e || window.event || event;

    // гарантируем контейнер до запроса
    if (!$('.DivNpcBlock').length) $('body').append("<div class='DivNpcBlock'></div>");
    positionDialog();

    const { playerToken } = window.__GUIDE__ || {};
    const K = (name)=> `pp:guide:${playerToken||'guest'}:${name}`;
    const LS_Q1_PROG   = K('q1progress');
    const LS_MOM_DONE  = K('mom-done');
    const LS_OAK_FINAL = K('oak-final');
    const LS_Q1_DONE   = K('q1done');

    let postData = { npc: id, step: step };
    if (extra && typeof extra === 'object') { for (let k in extra) postData[k] = extra[k]; }

    $.ajax({
      url: "/do/Npc/",
      type: "post",
      data: postData,
      beforeSend: function () {
        $('.loadWorld').css('display', 'block');
        positionDialog();
      },
      success: function (rawResponse) {
        $('.loadWorld').hide();
        let response = null;
        try { response = (typeof rawResponse==='object') ? rawResponse : JSON.parse(rawResponse); }
        catch (e) {
          $('.DivNpcBlock').remove();
          alert("Ошибка NPC: Некорректный ответ от сервера.\n\n" + String(rawResponse).substring(0,256));
          return;
        }
        try { window.__GUIDE__ && __GUIDE__.pause(); } catch(e){}

        if (response.error) { alert(response.error == 1 ? Lang.error_npc_unavailable : Lang.error_npc_unlocation); $('.DivNpcBlock').remove(); return; }
        if (response.action === 'updateLocation') { $('.DivNpcBlock').remove(); updateLocation(); return; }
        if (response.action === 'close' || response.close === 1) { try { NpcDialogClose(); } catch(e) { $('.DivNpcBlock').remove(); } return; }

        if (response.actionQuest) Game.notifications.main(response.actionQuest, 'quest');
        if (response.actionQuestPlus) Game.notifications.main(response.actionQuestPlus, 'plus');
        if (response.actionQuestMinus) Game.notifications.main(response.actionQuestMinus, 'minus');

        if (response.question === '{{makasimka}}') { byMax(response.npc_id); return; }
        if (response.question === '{{new}}') { $('.DivNpcBlock').remove(); if ($('.model').length) $('.model').remove(); NpcObject(response.type); return; }

        // Fast-path: apply UI patches without re-rendering (shop actions)
        if (response && response.ui_patch && response.ui_patch.type === 'shop') {
          if (applyShopPatch(response.ui_patch)) { return; }
        }

        // === построение ответов
        const isCloseLabel = (t)=>{
          t = String(t || '').trim().toLowerCase();
          if (!t) return false;
          return (t === 'закрыть' || t === 'закрити' || t === 'close' || t.startsWith('закрыть ') || t.startsWith('закрити '));
        };
        let answer = '';
        if (response.answer) {
          if (Array.isArray(response.answer)) {
            response.answer.forEach(function(y, idx) {
              if (typeof y === 'object' && y.type === 'by') {
                answer += `<div><button class="npc-btn" role="option" onclick="byMax(${y.npc_id});">${y.title}</button></div>`;
              } else {
                const answerText = typeof y === "string" ? y : (y.title || y);
                if (isCloseLabel(answerText)) {
                  answer += `<div><button class="npc-btn" role="option" onclick="NpcDialogClose();">${answerText}</button></div>`;
                } else {
                  const esc = __npcEscapeJsSingle(answerText);
                  answer += `<div><button class="npc-btn" role="option" onclick="NpcDialogPick(${id},${idx},'${esc}',null);">${answerText}</button></div>`;
                }
              }
            });
          } else {
            $.each(response.answer, function(x, y) {
              if (x === 'by' && typeof y === 'object') {
                answer += `<div><button class="npc-btn" role="option" onclick="byMax(${y.npc_id});">${y.title}</button></div>`;
              } else if (!isNaN(Number(x))) {
                if (isCloseLabel(y)) {
                  answer += `<div><button class="npc-btn" role="option" onclick="NpcDialogClose();">${y}</button></div>`;
                } else {
                  const esc = __npcEscapeJsSingle(y);
                  answer += `<div><button class="npc-btn" role="option" onclick="NpcDialogPick(${id},${x},'${esc}',null);">${y}</button></div>`;
                }
              } else {
                const esc = __npcEscapeJsSingle(y);
                answer += `<div><button class="npc-btn" role="option" onclick="NpcDialogPick(${id},1,'${esc}',{mission_id:${x}});">${y}</button></div>`;
              }
            });
          }
        }

        const npcImage = response.image ? response.image : '/img/default-npc.png';
        const npcBg    = response.bg    || null;
        const npcName  = response.name  || 'Персонаж';
        const npcQ     = response.question || '';
        const isDesktop = !(window.device && device.mobile());
        const actionsHtml = answer ? answer : `<div><button class="npc-btn" role="option" onclick="NpcDialogClose();">Закрыть</button></div>`;

        const mobileDrawerHtml = isDesktop ? '' : `
          <div class="npc-mobile-actionsbar">
            <button class="npc-mobile-actionsbtn" type="button" id="npc-actions-open">Действия</button>
          </div>
          <div class="npc-drawer-backdrop" id="npc-drawer-backdrop"></div>
          <div class="npc-drawer" id="npc-drawer" aria-hidden="true">
            <div class="npc-drawer-head">
              <div class="npc-drawer-title">Действия</div>
              <button class="npc-drawer-close" type="button" aria-label="Закрыть" id="npc-drawer-close">×</button>
            </div>
            <div class="npc-drawer-scroll" id="npc-drawer-scroll">
              <div class="npc-actions npc-actions-drawer" role="listbox">${actionsHtml}</div>
            </div>
          </div>
        `;

        // === Chat history (NPC + player) ===
        const chatKey = String(id);
        const chatState = window.__NPC_CHAT_STATE || (window.__NPC_CHAT_STATE = {});
        let chat = (chatState[chatKey] && step !== false) ? (chatState[chatKey].messages || []) : [];
        if (step === false) chat = [];
        chatState[chatKey] = chatState[chatKey] || {};
        chatState[chatKey].messages = chat;
        chatState[chatKey].name = npcName;
        chatState[chatKey].image = npcImage;

        if (npcQ) {
          const last = chat.length ? chat[chat.length - 1] : null;
          if (!last || last.from !== 'npc' || String(last.html || '') !== String(npcQ)) {
            chat.push({ from: 'npc', html: npcQ });
          }
        }

        const chatHtml = (chat || []).map(m => {
          if (!m) return '';
          if (m.from === 'player') {
            return `<div class="npc-bubble player"><div class="npc-bubble-inner">${__npcEscapeHtml(m.text || '')}</div></div>`;
          }
          const html = String(m.html || '');
          const isRich = (html.indexOf('npc-shop') !== -1);
          const cls = isRich ? 'npc-bubble-inner npc-bubble-rich' : 'npc-bubble-inner';
          return `<div class="npc-bubble npc"><div class="${cls}">${html}</div></div>`;
        }).join('');

        const bodyHtml = `
          <div class="npc-modal-root" role="dialog" aria-modal="true" aria-labelledby="npc-title">
            <div class="npc-overlay" onclick="NpcDialogClose()"></div>

            <div class="npc-sheet ${isDesktop ? 'desktop' : 'mobile'}">
              <div class="npc-header">
                <img class="npc-avatar" src="${npcImage}" alt="${npcName}">
                <div class="npc-titlewrap">
                  <div class="npc-title" id="npc-title">${npcName}</div>
                </div>
                <button class="npc-close" aria-label="Закрыть" onclick="NpcDialogClose()">
                  <svg viewBox="0 0 20 20" width="18" height="18"><path d="M6 6l8 8M14 6l-8 8" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
              </div>

              <div class="npc-content" id="npc-content">
                <div class="npc-main">
                  <div class="npc-main-scroll" id="npc-main-scroll" tabindex="-1">
                    <div class="npc-chat" aria-live="polite">${chatHtml}</div>
                  </div>
                </div>

                <div class="npc-side">
                  <div class="npc-side-head"><div>Действия</div></div>
                  <div class="npc-side-scroll" id="npc-side-scroll">
                    <div class="npc-actions" role="listbox">${actionsHtml}</div>
                  </div>
                </div>
              </div>

              ${mobileDrawerHtml}
            </div>

            <div class="npc-portrait-overlay" aria-hidden="true">
              <div class="npc-portrait-card" role="button" tabindex="0" aria-label="Закрыть фото">
                <img class="npc-portrait-img" src="${npcImage}" alt="">
              </div>
            </div>
          </div>`;

        $(".DivNpcBlock").html(bodyHtml);
        positionDialog();

        if (isDesktop) { try { $(".npc-sheet.desktop").draggabilly({ handle: '.npc-header', containment: '.DivNpcBlock' }); } catch(e){} }

        const $main = $('#npc-main-scroll');
        const $content = $('#npc-content');
        const $btns  = isDesktop ? $('.npc-side .npc-btn') : $();
        if ($btns.length) $btns[0].focus(); else $main.focus();
        if (isDesktop) {
          bindScrollShadows($main);
          bindScrollShadows($('#npc-side-scroll'));
        } else {
          bindScrollShadows($main);
          bindScrollShadows($('#npc-drawer-scroll'));
        }

        // mobile/touch scroll: bind manual scroll fallback
        try{ window.__npcBindManualScroll && window.__npcBindManualScroll(); }catch(e){}

        // Mobile actions drawer open/close
        let __npcCloseDrawerLocal = null;
        try{
          if (!isDesktop) {
            const sheetEl = document.querySelector('.npc-sheet');
            const drawerEl = document.getElementById('npc-drawer');
            const backdropEl = document.getElementById('npc-drawer-backdrop');
            const openBtn = document.getElementById('npc-actions-open');
            const closeBtn = document.getElementById('npc-drawer-close');
            const openDrawer = ()=>{
              if (!drawerEl) return;
              drawerEl.classList.add('open');
              if (backdropEl) backdropEl.classList.add('open');
              if (sheetEl) sheetEl.classList.add('drawer-open');
              drawerEl.setAttribute('aria-hidden','false');
            };
            const closeDrawer = ()=>{
              if (!drawerEl) return;
              drawerEl.classList.remove('open');
              if (backdropEl) backdropEl.classList.remove('open');
              if (sheetEl) sheetEl.classList.remove('drawer-open');
              drawerEl.setAttribute('aria-hidden','true');
            };
            __npcCloseDrawerLocal = closeDrawer;
            window.__npcCloseDrawer = closeDrawer;
            window.__npcOpenDrawer = openDrawer;
            if (openBtn) openBtn.addEventListener('click', (ev)=>{ ev.preventDefault(); ev.stopPropagation(); openDrawer(); }, { passive:false });
            if (closeBtn) closeBtn.addEventListener('click', (ev)=>{ ev.preventDefault(); ev.stopPropagation(); closeDrawer(); }, { passive:false });
            if (backdropEl) backdropEl.addEventListener('click', (ev)=>{ ev.preventDefault(); ev.stopPropagation(); closeDrawer(); }, { passive:false });
          }
        }catch(e){}

        // автопрокрутка к последнему сообщению
        try{ setTimeout(()=>{ const el = document.getElementById('npc-main-scroll'); if (el) el.scrollTop = el.scrollHeight; }, 0); }catch(e){}

        // portrait open/close
        const $portrait = $('.npc-portrait-overlay');
        const closePortrait = ()=>{ $portrait.removeClass('open').attr('aria-hidden','true'); };
        const openPortrait  = ()=>{ $portrait.addClass('open').attr('aria-hidden','false'); };
        $('.npc-avatar').on('click', openPortrait);
        $portrait.on('click', function(ev){ if (ev.target === this) closePortrait(); });
        $('.npc-portrait-card').on('click', closePortrait).on('keydown', function(ev){
          const k = ev.key.toLowerCase();
          if (k === 'enter' || k === ' ') { ev.preventDefault(); closePortrait(); }
        });


        // Pokemarket (buy/sell) UI inside NPC chat
        (function bindPokeMarketUI(){
          const $q = $('.npc-sheet');

          const ensureSellSelectToggle = () => {
            const shop = document.querySelector('.npc-shop[data-shop-mode="sell"]');
            if (!shop) return;
            if (shop.getAttribute('data-select-init') === '1') return;
            shop.setAttribute('data-select-init','1');
            // По умолчанию — без режима выбора (красивее на ПК/моб)
            shop.classList.remove('selecting');

            const toolbar = shop.querySelector('.npc-shop-toolbar');
            if (!toolbar) return;
            if (toolbar.querySelector('[data-shop-toggle-select]')) return;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'npc-tab';
            btn.textContent = 'Выбор';
            btn.setAttribute('data-shop-toggle-select','1');
            toolbar.insertBefore(btn, toolbar.firstChild);
          };

          const refreshCart = () => {
            ensureSellSelectToggle();
            const shop = document.querySelector('.npc-shop[data-shop-mode="sell"]');
            if (shop) updateShopCartSummary(shop);
          };

          $q.off('click.npcShopSelect').on('click.npcShopSelect','[data-shop-toggle-select]', function(ev){
            ev.preventDefault();
            const shop = document.querySelector('.npc-shop[data-shop-mode="sell"]');
            if (!shop) return;
            const now = !shop.classList.contains('selecting');
            shop.classList.toggle('selecting', now);
            this.classList.toggle('active', now);
            this.textContent = now ? 'Готово' : 'Выбор';
            refreshCart();
          });

          $q.off('input.npcShop').on('input.npcShop','input[data-shop-search]', function(){
            const term = (this.value || '').toString().toLowerCase();
            $(this).closest('.npc-shop').find('.npc-shop-card').each(function(){
              const nm = (this.getAttribute('data-name') || '').toLowerCase();
              this.style.display = (!term || nm.indexOf(term) !== -1) ? '' : 'none';
            });
            refreshCart();
          });

          // Cart interactions (sell mode)
          $q.off('change.npcShop').on('change.npcShop','input[data-sell-check], input[data-qty]', function(){
            refreshCart();
          });
          $q.off('input.npcShopQty').on('input.npcShopQty','input[data-qty]', function(){
            refreshCart();
          });

          $q.off('click.npcShop').on('click.npcShop','button[data-shop-action]', function(ev){
            ev.preventDefault();
            const action = this.getAttribute('data-shop-action');
            const curStep = (step === false || step === null) ? 0 : step;

            if (action === 'sell_bulk') {
              const shop = this.closest('.npc-shop');
              if (!shop) return;
              const items = [];
              shop.querySelectorAll('.npc-shop-card').forEach(card => {
                const cb = card.querySelector('input[data-sell-check]');
                if (!cb || !cb.checked) return;
                const itemId = parseInt(card.getAttribute('data-item-id') || '0', 10) || 0;
                if (!itemId) return;
                const inp = card.querySelector('input[data-qty]');
                let qty = inp ? (parseInt(inp.value || '1', 10) || 1) : 1;
                if (qty < 1) qty = 1;
                const max = inp ? (parseInt(inp.getAttribute('max') || '0', 10) || 0) : 0;
                if (max > 0 && qty > max) qty = max;
                if (inp && String(inp.value) !== String(qty)) inp.value = qty;
                items.push({ item_id: itemId, count: qty });
              });

              if (!items.length) {
                const note = shop.querySelector('[data-shop-note]');
                if (note) { note.style.display = ''; note.textContent = 'Выберите предметы для продажи.'; }
                return;
              }

              NpcDialog(id, curStep, null, { shop_action: 'sell_bulk', items: JSON.stringify(items) });
              return;
            }

            const itemId = parseInt(this.getAttribute('data-item-id') || '0', 10) || 0;
            let qty = 1;
            const card = this.closest('.npc-shop-card');
            if (card) {
              const inp = card.querySelector('input[data-qty]');
              if (inp) {
                qty = parseInt(inp.value || '1', 10) || 1;
                const max = inp.getAttribute('max') ? (parseInt(inp.getAttribute('max'), 10) || 0) : 0;
                if (max > 0 && qty > max) qty = max;
                if (qty < 1) qty = 1;
                if (String(inp.value) !== String(qty)) inp.value = qty;
              }
            }
            if (qty < 1) qty = 1;
            if (qty > 9999) qty = 9999;

            const extra = { shop_action: action, item_id: itemId, count: qty };
            NpcDialog(id, curStep, null, extra);
          });

          setTimeout(refreshCart, 0);
        })();

        $(document).on('keydown.npcModal', function(ev){
          const code = ev.key.toLowerCase();
          if (code === 'escape') {
            if ($portrait.hasClass('open')) { closePortrait(); return; }
            try{
              const d = document.getElementById('npc-drawer');
              if (d && d.classList.contains('open')) { window.__npcCloseDrawer && window.__npcCloseDrawer(); return; }
            }catch(e){}
            NpcDialogClose();
            return;
          }
          if (!isDesktop) return;
          if (!$btns.length) return;
          let idx = $btns.index(document.activeElement);
          if (code === 'arrowdown' || (code === 'tab' && !ev.shiftKey)) {
            ev.preventDefault(); idx = (idx + 1 + $btns.length) % $btns.length;
            $btns[idx].focus(); $btns[idx].scrollIntoView({block:'nearest', inline:'nearest'});
          }
          if (code === 'arrowup' || (code === 'tab' && ev.shiftKey)) {
            ev.preventDefault(); idx = (idx - 1 + $btns.length) % $btns.length;
            $btns[idx].focus(); $btns[idx].scrollIntoView({block:'nearest', inline:'nearest'});
          }
          if (code === 'enter' && document.activeElement && $(document.activeElement).hasClass('npc-btn')) {
            document.activeElement.click();
          }
        });

        /* === авто-продвижение + логика финала === */
        (function advanceByNpc(){
          const nm = (response.name||'');
          const slug = slugFromName(nm);
          window.__GUIDE_LAST_NPC_SLUG = slug || null;

          if (slug && window.__GUIDE__){
            const route = __GUIDE__.route;
            const lastIndexBySlug = __GUIDE__.lastIndexBySlug;

            const progBefore = (function(){ try{ return parseInt(localStorage.getItem(LS_Q1_PROG)||'0',10);}catch(e){return 0;} })();
            const nextIndexForSlug = (sg, fromIdx)=>{
              for (let i=Math.max(0,fromIdx); i<route.length; i++){
                if (route[i].slug===sg) return i;
              }
              return -1;
            };
            let idx = nextIndexForSlug(slug, progBefore);
            if (idx < 0) idx = route.findIndex(r=>r.slug===slug);

            if (slug==='mom'){
              try{
                localStorage.setItem(LS_MOM_DONE,'1');
                const momIdx = route.findIndex(i=>i.slug==='mom');
                if (momIdx>=0) localStorage.setItem(LS_Q1_PROG, String(Math.max(progBefore, momIdx+1)));
              }catch(e){}
            }

            if (idx>=0 && progBefore <= idx){
              try{ localStorage.setItem(LS_Q1_PROG, String(idx+1)); }catch(e){}
            }

            if (slug==='prof_oak'){
              const lastOak = lastIndexBySlug('prof_oak');
              if (idx === lastOak){
                try{ localStorage.setItem(LS_OAK_FINAL,'1'); }catch(e){}
              }
            }

            if (slug==='jack'){
              const jackIdxFinal = lastIndexBySlug('jack');
              const oakDone = (function(){ try{ return localStorage.getItem(LS_OAK_FINAL)==='1'; }catch(e){ return false; } })();
              if (oakDone && idx === jackIdxFinal){
                try{
                  localStorage.setItem(LS_Q1_DONE,'1');
                  localStorage.removeItem(LS_OAK_FINAL);
                }catch(e){}
              }
            }
          }

          if (response.questCompleted===1 || response.quest1Completed===1){
            try { localStorage.setItem(LS_Q1_DONE,'1'); } catch(e){}
          }
        })();

        // ——— авто-закрытие по флагу + обновление кнопки РБ и задержка
        if (response.name === 'NPC Арена РБ' && response.rb_state){
          updateRbArenaButton(response.rb_state);
        }

        if (response.closeDialog){
          if (window.__NPC_AUTO_CLOSE_TMR){ clearTimeout(window.__NPC_AUTO_CLOSE_TMR); window.__NPC_AUTO_CLOSE_TMR = null; }
          if (window.__NPC_AUTO_CLOSE_INT){ clearInterval(window.__NPC_AUTO_CLOSE_INT); window.__NPC_AUTO_CLOSE_INT = null; }

          if (response.battle_id){
            window.__NPC_AUTO_CLOSE_TMR = setTimeout(function(){
              window.__NPC_AUTO_CLOSE_TMR = null;
              NpcDialogClose();
            }, 300);
          } else {
            const DELAY = 5000;
            const $q = $('.npc-chat .npc-bubble.npc .npc-bubble-inner').last().length
              ? $('.npc-chat .npc-bubble.npc .npc-bubble-inner').last()
              : $('#npc-main-scroll');
            const $auto = $('<div class="npc-autoclose" style="opacity:.8;margin-top:6px;font-size:12px;"></div>');
            $q.append($auto);

            let left = Math.ceil(DELAY/1000);
            const render = ()=> $auto.text('Окно закроется через ' + left + ' сек…');
            render();

            window.__NPC_AUTO_CLOSE_INT = setInterval(()=>{
              if (!$('.DivNpcBlock').length){
                clearInterval(window.__NPC_AUTO_CLOSE_INT);
                window.__NPC_AUTO_CLOSE_INT = null;
                return;
              }
              left--;
              if (left <= 0){
                clearInterval(window.__NPC_AUTO_CLOSE_INT);
                window.__NPC_AUTO_CLOSE_INT = null;
              } else {
                render();
              }
            }, 1000);

            window.__NPC_AUTO_CLOSE_TMR = setTimeout(function(){
              if (window.__NPC_AUTO_CLOSE_INT){
                clearInterval(window.__NPC_AUTO_CLOSE_INT);
                window.__NPC_AUTO_CLOSE_INT = null;
              }
              window.__NPC_AUTO_CLOSE_TMR = null;
              NpcDialogClose();
            }, DELAY);
          }
        }
      },
      error: function (xhr) {
        $('.loadWorld').hide(); $('.DivNpcBlock').remove();
        alert("Ошибка NPC: Сервер недоступен или вернул ошибку.\n\nStatus: " + xhr.status + "\nResponse: " + (xhr.responseText||'').toString().substring(0,256));
      }
    });
  };

  /* === СБРОС ЛОКАЛЬНОЙ ПАМЯТИ ГИДА === */
  (function attachGuideReset(){
    function removeKeySafe(k){ try{ localStorage.removeItem(k); }catch(e){} }
    function clearByPrefix(prefix){
      try{
        for (let i = localStorage.length - 1; i >= 0; i--){
          const k = localStorage.key(i);
          if (k && k.indexOf(prefix) === 0){ localStorage.removeItem(k); }
        }
      }catch(e){}
    }
    function killGuideUI(){
      try{
        if (window.__GUIDE__) { try{ __GUIDE__.stop(); }catch(e){} }
        document.querySelectorAll('.ui-tour-root').forEach(n=>n.remove());
      }catch(e){}
    }

    const LEGACY_KEYS = [
      'pp:guide:q1done','pp:guide:q1progress','pp:guide:q1state',
      'pp:guide:arm-on-enter','pp:guide:mom-done','pp:guide:oak-final'
    ];

    function resetCurrentPlayer(){
      const pt = (window.__GUIDE__ && __GUIDE__.playerToken) ? __GUIDE__.playerToken : 'guest';
      const prefix = `pp:guide:${pt}:`;
      clearByPrefix(prefix);
      LEGACY_KEYS.forEach(removeKeySafe);
      killGuideUI();
      console.info('[GUIDE] Local state cleared for current player');
    }
    function resetAllPlayers(){
      clearByPrefix('pp:guide:');
      killGuideUI();
      console.info('[GUIDE] Local state cleared for ALL players on this device.');
    }
    function hardReinit(){
      resetAllPlayers();
      removeKeySafe('pp:guide:uid');
      console.info('[GUIDE] Hard reinit done (identity removed).');
    }

    if (window.__GUIDE__){
      window.__GUIDE__.debugReset      = resetCurrentPlayer;
      window.__GUIDE__.resetLocal      = resetCurrentPlayer;
      window.__GUIDE__.resetAllPlayers = resetAllPlayers;
      window.__GUIDE__.hardReinit      = hardReinit;
    }
  })();
})();

function NpcObject(type){
    $.ajax({
        url: "/do/Npc/",
        type: "post",
        data: { type: type },
        dataType: "json",                // ← пусть jQuery сам парсит JSON
        headers: { "Accept": "application/json" },
        success: function (response) {
            try {
                // Иногда сервер может вернуть строку с JSON — подстрахуемся:
                if (typeof response === "string") {
                    try { response = (typeof response === 'string') ? JSON.parse(response) : response; } catch(_) {
                        throw new Error("Сервер вернул не-JSON.");
                    }
                }

                if (response && response.error) {
                    // сервер прислал осмысленную ошибку
                    alert(response.message || response.error || "Ошибка NPC");
                    return;
                }

                // Определяем случай Питомника: НЕ использовать .model
                var isNursery = (response['title'] && (response['title']+'').toLowerCase() === 'питомник')
                                || (response['type'] && response['type'] === 'nursery');

                // Фолбэк, если нет ensureNurseryPanel (чтобы не падало)
                if (typeof ensureNurseryPanel !== 'function') {
                    window.ensureNurseryPanel = function(title){
                        var id = 'nursery-panel';
                        var $panel = $('#'+id);
                        if (!$panel.length) {
                            $panel = $('<div id="'+id+'" class="nursery-panel"></div>')
                                .css({
                                    position:'fixed', right:'24px', top:'72px',
                                    width:'720px', maxWidth:'95vw',
                                    maxHeight:'80vh', overflow:'auto',
                                    background:'#1d1b2a', color:'#fff',
                                    borderRadius:'12px', boxShadow:'0 12px 24px rgba(0,0,0,.35)',
                                    padding:'16px', zIndex: 9999
                                })
                                .appendTo('body');
                        }
                        document.title = title || document.title;
                        return $panel;
                    };
                }

                if (isNursery) {
                    // закрываем возможные старые модалки
                    if ($('.model').length) $('.model').remove();

                    var $body = ensureNurseryPanel(response['title'] || 'Питомник');

                    var tpl = '';
                    tpl+= '<div class="pit">';
                    tpl+=   '<input id="pitInput" placeholder="'+(window.Lang?.search_pokemon || 'Поиск покемона')+'" onkeypress=if(event.keyCode==13)nursery("search"); type="text">';
                    tpl+=   '<span class="showoptions" onclick="showfilters()">Параметры поиска</span>';

                    tpl+=   '<div class="optionsDex" style="display: none;">';
                    tpl+=     '<span class="LabelDexFilters">Окрас:</span><select size="1" id="SortType"><option value="0">-Не выбрано-</option><option value="1">Шайни</option><option value="2">Обычный</option></select>';
                    if (window.device && device.mobile()){ tpl+= '<br>'; }
                    tpl+=     '<span class="LabelDexFilters">Пол:</span><select size="1" id="SortGender"><option value="0">-Не выбрано-</option><option value="1">Мальчик</option><option value="2">Девочка</option><option value="3">Бесполый</option></select><br>';
                    tpl+=     '<span class="LabelDexFilters">Раведение:</span><select size="1" id="SortSparka"><option value="0">-Не выбрано-</option><option value="1">Недоступно</option><option value="2">Доступно</option><option value="3">Доступно 1</option><option value="4">Доступно 2</option><option value="5">Доступно 3</option></select>';
                    if (window.device && device.mobile()){ tpl+= '<br>'; }
                    tpl+=     '<span class="LabelDexFilters">Тренировка:</span><select size="1" id="SortTren"><option value="0">-Не выбрано-</option><option value="0">Отсутствует</option><option value="1">Начальная</option><option value="2">Расширенная</option><option value="3">Мастерская</option><option value="4">Знаменитая</option><option value="5">Легендарная</option><option value="6">Именная</option></select><br>';
                    tpl+=     '<span class="LabelDexFilters">Предмет:</span><select size="1" id="SortItem"><option value="0">-Не выбрано-</option><option value="1">Отсутствует</option><option value="2">Присутствует</option></select>';
                    if (window.device && device.mobile()){ tpl+= '<br>'; }
                    tpl+=     '<span class="LabelDexFilters">Команда:</span><select size="1" id="SortTeam"><option value="0">-Не выбрано-</option>'+(response['team']||'')+'</select><br>';
                    tpl+=     '<span class="LabelDexFilters">Характер:</span><select size="1" id="SortCharacter"><option value="0">-Не выбрано-</option><option value="1">Веселый</option><option value="2">Выносливый</option><option value="3">Застенчивый</option><option value="4">Кроткий</option><option value="5">Мирный</option><option value="6">Мягкий</option><option value="7">Наглый</option><option value="8">Наивный</option><option value="9">Нахальный</option><option value="10">Нежный</option>';
                    tpl+=       '<option value="11">Непослушный</option><option value="12">Непреклонный</option><option value="13">Обычный</option><option value="14">Одинокий</option><option value="15">Озорной</option><option value="16">Осторожный</option><option value="17">Поспешный</option><option value="18">Причудливый</option><option value="19">Распущенный</option><option value="20">Робкий</option>';
                    tpl+=       '<option value="21">Серьеный</option><option value="22">Скромный</option><option value="23">Смелый</option><option value="24">Спокойный</option><option value="25">Стремительный</option><option value="26">Тихий</option></select>';
                    if (window.device && device.mobile()){ tpl+= '<br>'; }
                    tpl+=     '<span class="LabelDexFilters">Уровень:</span><input type="text" id="SortLvl"><br>';
                    tpl+=     '<div class="Subs" onclick="nursery(\'search\')">Применить</div> <div class="Subs" onclick="nursClear()">Сбросить</div>';
                    tpl+=   '</div>';

                    tpl+=   '<div class="pok-1">'+(response['html']||'')+'</div>';
                    tpl+= '</div>';
                    tpl+= '<div class="pageDex">'+(response['page']||'')+'</div>';

                    $body.html(tpl);
                    return; // без .model
                }

                // ======== Обычная логика для прочих типов (.model) ========
                var tpl = '<div class="model"><div class="header">'+(response['title']||'NPC')+'<span onclick=$(".model").remove();><i class="fas fa-times"></i></span></div>';
                tpl += '<div class="content-model">';
                if(response['type'] == 'pokemarket' || response['type'] == 'reproduction' || response['type'] == 'lombard'){
                    tpl+= (response['html']||'');
                }else{
                    tpl+= '<div class="pit"><input id="pitInput" placeholder="'+(window.Lang?.search_pokemon || 'Поиск покемона')+'" onkeypress=if(event.keyCode==13)nursery("search"); type="text"><span class="showoptions" onclick="showfilters()">Параметры поиска</span>';
                    tpl+= '<div class="optionsDex" style="display: none;">';
                    tpl+= '<span class="LabelDexFilters">Окрас:</span><select size="1" id="SortType"><option value="0">-Не выбрано-</option><option value="1">Шайни</option><option value="2">Обычный</option></select>';
                    if(window.device && device.mobile()){ tpl+= '<br>';}
                    tpl+= '<span class="LabelDexFilters">Пол:</span><select size="1" id="SortGender"><option value="0">-Не выбрано-</option><option value="1">Мальчик</option><option value="2">Девочка</option><option value="3">Бесполый</option></select><br>';
                    tpl+= '<span class="LabelDexFilters">Раведение:</span><select size="1" id="SortSparka"><option value="0">-Не выбрано-</option><option value="1">Недоступно</option><option value="2">Доступно</option><option value="3">Доступно 1</option><option value="4">Доступно 2</option><option value="5">Доступно 3</option></select>';
                    if(window.device && device.mobile()){ tpl+= '<br>';}
                    tpl+= '<span class="LabelDexFilters">Тренировка:</span><select size="1" id="SortTren"><option value="0">-Не выбрано-</option><option value="0">Отсутствует</option><option value="1">Начальная</option><option value="2">Расширенная</option><option value="3">Мастерская</option><option value="4">Знаменитая</option><option value="5">Легендарная</option><option value="6">Именная</option></select><br>';
                    tpl+= '<span class="LabelDexFilters">Предмет:</span><select size="1" id="SortItem"><option value="0">-Не выбрано-</option><option value="1">Отсутствует</option><option value="2">Присутствует</option></select>';
                    if(window.device && device.mobile()){ tpl+= '<br>';}
                    tpl+= '<span class="LabelDexFilters">Команда:</span><select size="1" id="SortTeam"><option value="0">-Не выбрано-</option>'+(response['team']||'')+'</select><br>';
                    tpl+= '<span class="LabelDexFilters">Характер:</span><select size="1" id="SortCharacter"><option value="0">-Не выбрано-</option><option value="1">Веселый</option><option value="2">Выносливый</option><option value="3">Застенчивый</option><option value="4">Кроткий</option><option value="5">Мирный</option><option value="6">Мягкий</option><option value="7">Наглый</option><option value="8">Наивный</option><option value="9">Нахальный</option><option value="10">Нежный</option>';
                    tpl+= '<option value="11">Непослушный</option><option value="12">Непреклонный</option><option value="13">Обычный</option><option value="14">Одинокий</option><option value="15">Озорной</option><option value="16">Осторожный</option><option value="17">Поспешный</option><option value="18">Причудливый</option><option value="19">Распущенный</option><option value="20">Робкий</option>';
                    tpl+= '<option value="21">Серьеный</option><option value="22">Скромный</option><option value="23">Смелый</option><option value="24">Спокойный</option><option value="25">Стремительный</option><option value="26">Тихий</option></select>';
                    if(window.device && device.mobile()){ tpl+= '<br>';}
                    tpl+= '<span class="LabelDexFilters">Уровень:</span><input type="text" id="SortLvl"><br>';
                    tpl += '<div class="Subs" onclick="nursery(\'search\')">Применить</div> <div class="Subs" onclick="nursClear()">Сбросить</div>';
                    tpl+= '</div>';
                    tpl+= '<div class="pok-1">'+(response['html']||'')+'</div>';
                    tpl+= '</div></div><div class="pageDex">'+(response['page']||'')+'</div>';
                }
                tpl += '</div>';
                $("body").append(tpl);

                if (window.device && !device.mobile()){
                    $('.model').draggabilly({ handle: '.header', containment: true });
                }
            } catch (e) {
                console.error(e);
                alert('Ошибка NPC: Некорректный ответ от сервера.\n'+(e && e.message ? e.message : ''));
            }
        },
        error: function(xhr){
            let msg = 'Ошибка NPC: сервер недоступен.';
            if (xhr && xhr.responseText) {
                // если сервер всё же шлёт JSON-ошибку
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j && (j.message || j.error)) msg = j.message || j.error;
                } catch(_){}
            }
            alert(msg);
        }
    });
}


/* ===== Панель Питомника (особый класс), НЕ .model =====
   Возвращает jQuery-объект на тело панели, куда можно рендерить контент.
*/
function ensureNurseryPanel(title) {
    let $panel = $(".ui-nursery");
    if (!$panel.length) {
        const html = `
          <div class="ui-nursery is-visible" role="dialog" aria-modal="true">
            <div class="ui-nursery__backdrop" onclick="closeNurseryPanel()"></div>
            <div class="ui-nursery__panel">
              <div class="ui-nursery__head">
                <div class="ui-nursery__title">${escapeHtml(String(title || 'Питомник'))}</div>
                <button class="ui-nursery__close" type="button" aria-label="Закрыть" onclick="closeNurseryPanel()">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div class="ui-nursery__body"></div>
            </div>
          </div>`;
        $("body").append(html);
        $panel = $(".ui-nursery");
    } else {
        $panel.addClass("is-visible");
        $panel.find(".ui-nursery__title").text(String(title || 'Питомник'));
        $panel.find(".ui-nursery__body").empty();
    }
    return $panel.find(".ui-nursery__body");
}

function closeNurseryPanel() {
    $(".ui-nursery").removeClass("is-visible").remove();
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function showfilters(){
    $('.showoptions').hide();
    $('.optionsDex').css('display','block');
}
// компактный поповер возле триггера
function showNotify(e){
  const target = e?.currentTarget || e || document.querySelector('[data-open-notify]');
  if (!target) return;

  // закрыть уже открытый
  const opened = document.querySelector('.LittleModal.notify-pop');
  if (opened){ opened.remove(); return; }

  // CSS (один раз)
  (function injectCSS(){
    if (document.getElementById('notify-pop-css')) return;
    const css = `
:root{ --np-bg:#fff; --np-br:#e7ecff; --np-chip:#eef3ff; --np-t:#1b2b4f; --np-sub:#7483a6; --np-ac:#5b6cff; }

}
.LittleModal.notify-pop{
  position:absolute; z-index:620;
  width:420px; max-width:min(92vw,420px);
  background:var(--np-bg); border:1px solid var(--np-br); color:var(--np-t);
  border-radius:14px; box-shadow:0 12px 32px rgba(23,35,74,.18); overflow:hidden;
  font-family:Inter, Nunito, Arial, sans-serif;
}
.LittleModal.notify-pop .Head{
  display:flex; align-items:center; justify-content:space-between;
  padding:8px 10px; border-bottom:1px solid var(--np-br); background:linear-gradient(180deg,#f7f9ff22,transparent);
}
.LittleModal.notify-pop .Head .ttl{ font-weight:900; font-size:16px; }
.LittleModal.notify-pop .Head .close{ width:28px; height:28px; border-radius:9px; border:1px solid var(--np-br); background:var(--np-bg); cursor:pointer; display:grid; place-items:center; }

.LittleModal.notify-pop .Panel{ padding:8px; }
.notify-filters-grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:6px; margin-bottom:8px;
}
.notify-filter-btn{
  display:flex; align-items:center; justify-content:space-between; gap:6px;
  padding:6px 8px; border:1px solid var(--np-br); background:var(--np-chip); color:var(--np-t);
  border-radius:10px; font-weight:800; font-size:12px; cursor:pointer;
}
.notify-filter-btn .notify-count{ padding:0 6px; border-radius:999px; background:#e9eeff; font-weight:900; }
.notify-filter-btn .notify-count.active{ background:#dfe7ff; }
.notify-filter-btn.active{ border-color:var(--np-ac); box-shadow:0 0 0 2px rgba(91,108,255,.15); }

.notify-actions{ display:flex; align-items:center; gap:6px; }
.notify-search{
  flex:1; min-width:0; height:30px; padding:6px 8px; border-radius:10px; border:1px solid var(--np-br);
  background:var(--np-bg); color:var(--np-t); font-size:12px;
}
.notify-actions .icon-btn{
  width:30px; height:30px; border-radius:10px; border:1px solid var(--np-br); background:var(--np-bg); cursor:pointer;
  display:grid; place-items:center; font-size:13px;
}

.notify-list{ max-height:340px; overflow:auto; padding:8px; }
.DivNotify{
  display:grid; grid-template-columns:40px 1fr; gap:8px; padding:8px;
  border:1px solid var(--np-br); border-radius:12px; background:var(--np-bg); margin-bottom:6px;
}
.DivNotify img{ width:40px; height:40px; border-radius:10px; border:1px solid var(--np-br); background:#ffffff0f; object-fit:cover; }
.DivNotify .Text{ display:flex; flex-direction:column; gap:4px; }
.DivNotify .Words{ font-size:13px; line-height:1.35; }
.DivNotify .Data{ font-size:11px; color:var(--np-sub); }

.NotifyLoading,.NotifyEmpty,.NotifyError{ padding:14px; text-align:center; color:var(--np-sub); }
@media (max-width:760px){
  .LittleModal.notify-pop{
    position:fixed; left:8px !important; right:8px; top:calc(env(safe-area-inset-top,0px) + 8px) !important;
    width:auto; max-width:none; z-index:99999;
  }
  .notify-list{ max-height:calc(100vh - 220px); }
}
    `;
    const st = document.createElement('style'); st.id='notify-pop-css'; st.textContent = css;
    document.head.appendChild(st);
  })();

  // каркас
  const box = document.createElement('div');
  box.className = 'LittleModal notify-pop';
  box.innerHTML = `
    <div class="Head">
      <div class="ttl">Уведомления</div>
      <button class="close" aria-label="Закрыть">✕</button>
    </div>
    <div class="Panel">
      <div class="notify-filters-grid" id="npFilters"></div>
      <div class="notify-actions">
        <input class="notify-search" id="notifySearchInput" placeholder="Поиск...">
        <button class="icon-btn" id="npRead" title="Отметить всё как прочитанное">✓</button>
        <button class="icon-btn" id="npClear" title="Очистить">🧹</button>
      </div>
    </div>
    <div class="notify-list" id="notifyList"><div class="NotifyLoading">Загрузка...</div></div>
  `;
  document.body.appendChild(box);

  // позиционируем около триггера (правая сторона и сдвиг вниз)
  (function place(){
    const r = target.getBoundingClientRect();
    const w = box.offsetWidth, h = box.offsetHeight;
    let top = r.bottom + window.scrollY + 8;
    let left = (r.right + window.scrollX) - w;            // прижать к правому краю кнопки
    left = Math.max(8 + window.scrollX, left);            // не уходить за левый край
    box.style.top = top + 'px';
    box.style.left = left + 'px';
  })();

  // закрытие
  box.querySelector('.close').onclick = () => box.remove();
  setTimeout(()=>{                                // «клик мимо»
    const closeOutside = (ev)=>{
      if (!box.contains(ev.target) && ev.target!==target){ box.remove(); document.removeEventListener('mousedown', closeOutside); }
    };
    document.addEventListener('mousedown', closeOutside);
  },0);

  // загрузка HTML
  $.ajax({
    url:'/do/notifications',
    type:'POST',
    data:{type:'load'},
    dataType:'html',
    success:function(html){
      // ожидаем, что PHP отдаёт весь блок панели фильтров + notify-list
      // но для компактного поповера нам достаточно вытащить куски:
      const tmp = document.createElement('div');
      tmp.innerHTML = html;

      // фильтры
      const filters = tmp.querySelector('.notify-filters-grid');
      const panel = box.querySelector('#npFilters');
      if (filters) panel.replaceWith(filters);

      // список
      const list = tmp.querySelector('.notify-list') || tmp;
      box.querySelector('#notifyList').replaceWith(list);

      // кнопки «прочитать всё» / «очистить»
      box.querySelector('#npRead').onclick = notifyMarkAllRead;
      box.querySelector('#npClear').onclick = clearNotifications;

      // поиск
      const input = box.querySelector('#notifySearchInput');
      input.oninput = notifySearch;
    },
    error:function(xhr){
      box.querySelector('#notifyList').innerHTML =
        `<div class="NotifyError">Ошибка загрузки ${xhr?.status?`(HTTP ${xhr.status})`:''}</div>`;
    }
  });
}

/* --- вспомогалки, совместимы с версткой PHP --- */
function notifyFilter(type, btn){
  // переключение кнопки
  const wrap = btn.closest('.notify-filters-grid');
  wrap.querySelectorAll('.notify-filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');

  // фильтрация карточек
  const list = document.querySelector('.LittleModal.notify-pop .notify-list');
  if (!list) return;
  list.querySelectorAll('.DivNotify').forEach(n=>{
    const t = n.getAttribute('data-type') || 'system';
    const checked = n.getAttribute('data-checked') === '1';
    let show=true;
    if (type!=='all' && type!=='new') show = (t===type);
    if (type==='new') show = !checked;
    n.style.display = show ? '' : 'none';
  });
}
function notifySearch(){
  const q = (document.getElementById('notifySearchInput')?.value||'').toLowerCase();
  const list = document.querySelector('.LittleModal.notify-pop .notify-list');
  if (!list) return;
  list.querySelectorAll('.DivNotify').forEach(n=>{
    const text = (n.textContent||'').toLowerCase();
    n.style.display = text.includes(q) ? '' : 'none';
  });
}
function notifyMarkAllRead(){
  $.post('/do/notifications', {type:'mark_all_read'}, function(){ 
    document.querySelectorAll('.DivNotify').forEach(n=>n.setAttribute('data-checked','1'));
  }, 'json');
}
function clearNotifications(){
  var $btn = $('#clearNotifications');
  if ($btn.prop('disabled')) return;

  $btn.prop('disabled', true).addClass('is-loading');

  $.post('/do/notifications', { type: 'clear_notifications' }, function(res){
    // jQuery уже парсит JSON, но на всякий случай…
    if (typeof res === 'string') {
      try { res = JSON.parse(res); } catch(_) { res = {}; }
    }

    if (res && res.success){
      // очистить список
      var list = document.querySelector('.LittleModal.notify-pop .notify-list') || document.getElementById('notifyList');
      if (list) list.innerHTML = '';

      // опционально показать пустую заглушку
      if (list){
        list.innerHTML = (
          '<div class="NotifyEmpty">' +
            '<div class="NotifyEmptyIcon"><i class="far fa-bell-slash"></i></div>' +
            '<div class="NotifyEmptyText">Нет уведомлений</div>' +
          '</div>'
        );
      }

      // обновить счётчики, если пришли
      if (res.counts){
        try {
          // пример: обновите ваши бейджи, если они есть
          $('.notify-filter-btn .notify-count').removeClass('active').text('0');
          // общий бейдж (если у вас есть глобальная «точка» в шапке):
          if (typeof window.updateNotifyBadge === 'function'){
            window.updateNotifyBadge(res.counts.total_unread || 0);
          }
        } catch(_){}
      }
    } else {
      console.warn('clearNotifications: server returned error', res && res.error);
    }
  }, 'json')
  .fail(function(xhr){
    console.error('clearNotifications: AJAX failed', xhr && xhr.status);
  })
  .always(function(){
    $btn.prop('disabled', false).removeClass('is-loading');
  });
}


function openTrade(){

    if(ClassTrade){
        ClassTrade._close(false);

        return setTimeout(function(){
            ClassTrade = null;

            openTrade();

        }, 1000);
    }

    $.ajax({

        url: "/do/trade",
        type: "POST",
        data: {
            type:'view'
        },

        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if(response['error'] == 1){
                Game.notifications.main(response['text'],'error');
            }else{
                ClassTrade = new Trade(response);
            }
        }
    });
}
function addTrade(id,type){
    var count = ($('#countItemInput').val()?$('#countItemInput').val():1);
    $.ajax({
        url: "/do/trade",
        type: "POST",
        data: "id="+id+"&typePut="+type+"&type=add&count="+count,
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if(response['error'] == 1){
                Game.notifications.main(response['text'],'error');
            }else{
                if(response['update'] == 0){
                    var tpl = '<div class="slot" id=slot_'+response['data']['id']+'>';
                    tpl+= '<img src="/img/world/items/little/'+response['data']['id']+'.png">';
                    tpl+= '<div class="text">'+response['data']['name']+' <b>x'+response['data']['count']+'</b></div>';
                    tpl+= '</div>';
                    $('.userOne .itemsTrade').append(tpl);
                }else{
                    alert(response['data']['count']);
                    var tpl = '<img src="/img/world/items/little/'+response['data']['id']+'.png">';
                    tpl+= '<div class="text">'+response['data']['name']+' <b>x'+response['data']['count']+'</b></div>';
                    $('#slot_'+response['data']['id']).html(tpl);
                }
            }
        }
    });
}
function evolutionPok(id){
    var pokID = parseInt($("#pokID").val());
     $.ajax({
        url: "/do/Npc/"+id+".php",
        type: "POST",
        data: 'pokID='+pokID,
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            response['error'] == 1 ? Game.notifications.main(response['text'],'error') : Game.notifications.main(response['text'],'success');
            if(response['minus']) {
              Game.notifications.main(response['minus'],'minus');
            }
        }
    });
}
function discoveryGo(type){
   var pokID = parseInt($("#pokID").val());

    $.ajax({
       url: "/do/DiscoveryItem",
       type: "POST",
       data: 'pokID='+pokID+'&type='+type,
       success: function (response) {
           response = (typeof response === 'string') ? JSON.parse(response) : response;
           Game.notifications.main(response["html"], response["error"]);
       }
   });
}
function berryGo(type,slot=false){
   var berryID = parseInt($("#berryID").val());
   $(".tooltip").hide();
    $.ajax({
       url: "/do/berry",
       type: "POST",
       data: 'berryID='+berryID+'&type='+type+'&slot='+slot,
       success: function (response) {
           response = (typeof response === 'string') ? JSON.parse(response) : response;
           Game.notifications.main(response["html"], response["error"]);
           if(type == "FarmAdd"){
             openModal('berry');
           }else{
             $(".ItemFarm.slot"+slot).html(response['text']);
             if(type == "pick"){
               $(".ItemFarm.slot"+slot).attr("onclick","issetAll('"+slot+"','FarmInfo2')");
             }else{
               $(".ItemFarm.slot"+slot).attr("onclick","issetAll('"+slot+"','FarmInfo')");
             }
           }
       }
   });
}
function getReferalReward(e, referalId, level) {
  if (e) e.preventDefault(); // поддержка вызова как submit и обычной кнопкой
  $(".tooltip").hide();
  $.ajax({
      url: "/do/pp",
      type: "POST",
      data: { referal: referalId, level: level },
      success: function (response) {
          try { response = (typeof response === 'string') ? JSON.parse(response) : response; } catch(e){}
          Game.notifications.main(response["html"], response["error"]);
          if(response["plus"]){
              Game.notifications.main(response["plus"], "plus");
          }
          // Если хотите — можно обновить блок рефералов без перезагрузки
          // location.reload();
      }
  });
}
function GiveGift(id){
  $(".tooltip").hide();
  $.ajax({
      url: "/do/pp",
      type: "POST",
      data: 'GiveGift='+id,
      success: function (response) {
          response = (typeof response === 'string') ? JSON.parse(response) : response;
          Game.notifications.main(response["html"], response["error"]);
          if(response["plus"]){
              Game.notifications.main(response["plus"], "plus");
          }
      }
  });

}
//function CodeActive(id,type){
  //var pokID = parseInt($("#pokID").val());
  //if(type == 1){
      //var harID = parseInt($("#harID").val());
      //var dop = '&har='+harID;
  //}else if(type == 2){
      //var dop = '';
  //}else if(type == 3){
      //var statID = parseInt($("#statID").val());
      //var dop = '&stat='+statID;
  //}else if(type == 4){
      //var dop = '';
  //}else if(type == 5){
      //var dop = '';
  //}
  //$(".tooltip").hide();
  //$.ajax({
      //url: "/do/loterey",
      //type: "POST",
      //data: 'category=modification&id='+id+'&pok='+pokID+dop,
      //success: function (response) {
          //response = (typeof response === 'string') ? JSON.parse(response) : response;
          //Game.notifications.main(response["html"], response['error']);
          //$('.codelotinput').val('');
          
      //}
  //});
//}
function RepItemGo(){
  var itemID = parseInt($("#itemID").val());
  $(".tooltip").hide();
  $.ajax({
      url: "/do/pp",
      type: "POST",
      data: 'repair=1&type=check&id='+itemID,
      success: function (response) {
          response = (typeof response === 'string') ? JSON.parse(response) : response;
          Game.notifications.main(response["html"], response["error"]);
          if(response["error"] == "success"){
              $("#itemRep").html(response["text"]);
              $('#itemRep').attr('data-title',itemID);
          }
      }
  });

}
function RepPotionGo(){
  var potionID = parseInt($("#potionID").val());
  $(".tooltip").hide();
  $.ajax({
      url: "/do/pp",
      type: "POST",
      data: 'repair=1&type=check&id='+potionID,
      success: function (response) {
          response = (typeof response === 'string') ? JSON.parse(response) : response;
          Game.notifications.main(response["html"], response["error"]);
          if(response["error"] == "success"){
              $("#potionRep").html(response["text"]);
              $('#potionRep').attr('data-title',potionID);
          }
      }
  });

}
function repair(){
    var potion = $('#potionRep').attr('data-title');
    var item = $('#itemRep').attr('data-title');
    if(potion == 0 || item == 0){
        return Game.notifications.main('Не выбран какой-то из предметов',"error");
    }
    $.ajax({
      url: "/do/pp",
      type: "POST",
      data: 'repair=1&type=repair&item='+item+'&potion='+potion,
      success: function (response) {
          response = (typeof response === 'string') ? JSON.parse(response) : response;
          Game.notifications.main(response["html"], response["error"]);
          if(response["error"] == "success"){
              openModal('repair');
          }
      }
  });
}
function ngBox(type, refresh, rare){
    if(!rare) rare = 0;
    $.ajax({
        url: "/do/ngbox",
        type: "POST",
        data: 'type='+type+'&ref='+(refresh || 0)+'&rare='+rare,
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;

            if(refresh){
                $('.model').empty().append(response['html']);
            }else{
                $('.model').remove();
                $('body').append(response['html']);
                $('.model').draggabilly({
                    handle: '.header',
                    containment: true
                });
            }


        }
    });
}
function giveDiscovery(id){
    $.ajax({
       url: "/do/Discovery",
       type: "POST",
       data: 'id_discovery='+id,
       success: function (response) {
           response = (typeof response === 'string') ? JSON.parse(response) : response;
           Game.notifications.main(response["html"], response["error"]);
			if(response["error"] == 'success'){
				$('.CraftContent').html('<div class="discovery"><span>'+response['prize']+'</span></div>');
			}
       }
   });
}

function policeNPC(){
	var subject = $('#subjectPolice').val();
	var text = $('#textPolice').val();
		$.ajax({
			url: "/do/Npc/jess",
			type: "POST",
			data: {
				subject: subject,
				text: text
			},
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				response['error'] == 1 ? Game.notifications.main(response['text'],'error') : Game.notifications.main(response['text'],'success');
			}
		});
}
function upgradeClan(type){
	if(type == 'goLeaderClan'){
		var a = $("#goLeaderClan").val();
	}else if(type == 'goUnleaderClan'){
		var a = $("#goLeaderClan").val();
	}else if(type == 'goDeleteClan'){
		var a = $("#goDeleteClan").val();
	}else if(type == 'goStatusClan'){
		var a = $("#goStatusClanLogin").val(),
			b = $("#goStatusClanText").val();
	}else if(type == 'goNotifyClan'){
		var a = $("#goNotifyClan").val();
	}
	$.ajax({
		url: "/do/clanAction.php",
		type: "POST",
		data: 'object='+type+'&name='+a+'&other='+b,
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			Game.notifications.main(response["text"], 'info');
		}
	});
}
function openClanCard(id) {
    function decodeUnicode(str) {
        // Декодирует uXXXX → символ
        return str.replace(/u([0-9a-f]{4})/gi, function (match, grp) {
            return String.fromCharCode(parseInt(grp, 16));
        });
    }

    id = parseInt(id);
    if ($('.mudol').length) {
        $('.mudol').remove();
    }
    $.ajax({
        url: "/do/clanAction.php",
        type: "POST",
        data: 'object=' + encodeURIComponent('clanCard,' + id),
        beforeSend: function () {
            $('.Modal').remove();
            $('<div />', {
                'class': 'mudol'
            }).prependTo('body');
            $('.mudol').html('<center>' + mainLoader + '</center>');
        },
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            if (response['text']) {
                $('.mudol').remove();
                return Game.notifications.main(response['text'], 'error');
            }
            var info = response['info'];
            if (typeof info === 'string') { try { info = JSON.parse(info); } catch (e) { info = {}; } }

            var member = false, leader = false;

            // --- HEADER (лого, инфо, уровень) ---
            var header = `
<div class="clancard-header">
  <div class="clancard-header-inner">
    <div class="clancard-logo" style="background-image:url('/img/world/clans/emblems/${id}.png');"></div>
    <div class="clancard-header-info">
      <div class="clancard-title">${info['name']}</div>
      <div class="clancard-meta">
        <span><i class="far fa-star"></i>${response['position']}</span>
        <span><i class="far fa-arrow-alt-up"></i>${response['rating']}</span>
        <span><i class="far fa-user"></i>${response['countUsers']}</span>
        <span class="est">est. ${info['dateCreate']}</span>
      </div>
      <div class="clancard-level-block">
        <div class="clancard-level-label">Ур. клана: ${response['clan_level'] || 1}</div>
        <div class="clancard-progressbar-bg">
          <div class="clancard-progressbar-bar" style="width: ${Math.floor((response['clan_exp'] || 0) / (response['clan_exp_next'] || 1) * 100)}%"></div>
          <div class="clancard-progressbar-text">
            ${response['clan_exp'] || 0} / ${response['clan_exp_next'] || 1000} опыта (${Math.floor((response['clan_exp'] || 0) / (response['clan_exp_next'] || 1) * 100)}%)
          </div>
        </div>
      </div>
    </div>
    <div class="clancard-close" onclick="$('.mudol').remove();"><i class="fas fa-times"></i></div>
  </div>
</div>
`;

            // --- FLEX: слева - участники + события, справа - кнопки ---
            // Участники
            var usersHtml = '';
            $.each(response['users'], function (x, y) {
                var data = y.split(',');
                var raiting = (data[2].indexOf('-') === -1 ? `<span class="plus">${data[2]}</span>` : `<span class="minus">${data[2]}</span>`);
                var classUser = (data[4] == 1 ? 'lead' : (data[4] == 2 ? 'moder' : ''));
                if (data[5] == data[6]) {
                    member = true;
                    if (data[7] == 1) leader = true;
                }
                usersHtml += `
<div class="clancard-member ${classUser}">
  <span class="user-link">
    <div onclick="showUserTooltip(${data[8]})" class="Info-Link sexm" style="display:inline"><i class="fa fa-info"></i></div>
    <span class="u-${data[1]} label" onclick="user_to_chat_add(${data[8]})">${data[0]}</span>
  </span>
  <span class="zvan">${data[3]}</span>
  ${raiting}
  ${(data[8] == info['Creater'] ? '<span class="gl"></span>' : '')}
</div>
                `;
            });

            // События
            var logHtml = '';
            $.each(response['log'], function (x, y) {
                var data = y['info'];
                if (typeof data === 'string') { try { data = JSON.parse(data); } catch (e) { data = {}; } }

                var clan_action;
                if (y['type'] === 'ADD_CLAN_USER') {
                    clan_action = Lang.clan_user_entered;
                } else if (y['type'] === 'ADD_CLAN_MONEY') {
                    clan_action = (member ? Lang.clan_user_add_money + ' <b>' + data['count'] + '</b> ' + Lang.currency_money : true);
                } else if (y['type'] === 'TAKE_CLAN_MONEY') {
                    clan_action = (member ? Lang.clan_user_take_money + ' <b>' + data['count'] + '</b> ' + Lang.currency_money : true);
                } else if (y['type'] === 'TAKE_CLAN_REITING') {
                    clan_action = Lang.clan_user_take_rating;
                } else if (y['type'] === 'CLAN_CREATE') {
                    clan_action = Lang.clan_user_create;
                } else if (y['type'] === 'ADD_CLAN_STATUS') {
                    clan_action = Lang.clan_user_status + ' <b>' + data['status'] + '</b>';
                } else if (y['type'] === 'ADD_CLAN_NOTICE') {
                    clan_action = (member ? Lang.clan_user_notify + ' <b>' + decodeUnicode(data['notice']) + '</b>' : true); // ! исправление тут
                } else if (y['type'] === 'ADD_CLAN_ADMIN') {
                    clan_action = Lang.clan_user_leader;
                } else if (y['type'] === 'DELETE_CLAN_ADMIN') {
                    clan_action = Lang.clan_user_unleader;
                } else if (y['type'] === 'LEFT_CLAN') {
                    clan_action = Lang.clan_user_leave;
                } else if (y['type'] === 'LEFT_CLAN_ALERT') {
                    clan_action = Lang.clan_user_excluded;
                } else if (y['type'] === 'ABOUT_CLAN_ALERT') {
                    clan_action = Lang.clan_user_clan_status;
                }
                if (clan_action == true) {
                    return;
                }
                logHtml += `<div class="clancard-log-entry">
                    <span class="time">${data['date']}</span>
                    <span class="user-link"><span class="u-${data['user_group']}">${data['user_new']}</span></span>
                    <span>${clan_action}</span>
                </div>`;
            });

            // Кнопки и действия (справа)
            var actionsHtml = '';
if (member) {
    actionsHtml += `
      <div class="clancard-actions-block">
        <div class="clancard-balance-bar">Счет клана ${info['Money']} гк.</div>
        <input type="number" class="text InpMoneyClan" id="clanMoneyInput" placeholder="Кол-во" value="1000">
        <button class="clancard-btn" onclick="actionClan('addMoney')">Внести на счет</button>
        ${leader ? `<button class="clancard-btn" onclick="actionClan('minusMoney')">Снять со счета</button>` : ''}
        ${leader ? `<button class="clancard-btn" onclick="openClanControl(${id})">Управление</button>` : ''}
        <button class="clancard-btn" onclick="openClanStorage(${id})">Склад клана</button>
        <button class="clancard-btn" onclick="openClanShop(${id})">Магазин клана</button>
        <button class="clancard-btn" onclick="openClanFactions(${id})">Фракции</button>
        <button class="clancard-btn danger" onclick="actionClan('left');">${Lang.button_clan_leave}</button>
      </div>
    `;
}
            // Основная FLEX-сетка
            var mainFlex = `
<div class="clancard-main">
  <div class="clancard-left">
    <div class="clancard-block-title">Участники</div>
    <div class="clancard-members-list">${usersHtml}</div>
    <div class="clancard-block-title" style="margin-top:18px;">События</div>
    <div class="clancard-log-list">${logHtml}</div>
  </div>
  <div class="clancard-right">
    ${actionsHtml}
  </div>
</div>
`;

            // Финальная сборка
            var modalHeader = '<div class="Header"><span>' + (typeof esc==='function' ? esc(info['name'] || 'Клан') : (info['name'] || 'Клан')) + '</span><div class="Close" onclick="$(\".mudol\").remove();"><i class=\"fas fa-times\"></i></div></div>' ;
            var tpl = modalHeader + '<div class="Content">' + header + mainFlex + '</div>';
            $('.mudol').html(tpl);
        }
    });
}
function plague(){
    if($('.model').length){
		$('.model').remove();
	}
	$.ajax({
		url: "/do/Npc/plague",
		type: "POST",
		beforeSend: function(){
			$('<div />', {
					'class':'model'
				}).appendTo('body');
           $('.model').html('<center>'+mainLoader+'</center>');
        },
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			$('.model').html(response['html']);
			
			if(!device.mobile()){
    $('.model').draggabilly({
				handle: '.header',
				containment: true
			});
  }
		}
	});
}
// Версия модалки с другим контейнером (не .model), чтобы применялись свои CSS (.modal--clan-control)
// Открыть окно управления кланом в новом контейнере (.modal--clan-control) с корректной обёрткой диалога
function openClanControl(id) {
  id = parseInt(id, 10) || 0;

  // Сносим предыдущие модалки любого типа
  $('.model, .modal--clan-control').remove();
  $('body').addClass('modal-open');

  $.ajax({
    url: '/do/clanAction.php',
    type: 'POST',
    data: 'object=' + encodeURIComponent('clanCardControl,' + id),
    beforeSend: function () {
      const $overlay = $('<div/>', {
        class: 'modal modal--clan-control',
        'data-modal-id': 'clan-control',
        'aria-hidden': 'true'
      }).appendTo('body');

      $overlay.html('<center>' + (window.mainLoader || '<div class="loader"></div>') + '</center>');

      (function ensureCloseModelPatch() {
        const prev = window.CloseModel;
        window.CloseModel = function () {
          if (typeof prev === 'function') {
            try { prev(); } catch (e) {}
          }
          cleanupModal();
        };
      })();
    },
    success: function (raw) {
      const $overlay = $('.modal--clan-control').first();
      if (!$overlay.length) return;

      let resp;
      try { resp = (typeof raw === 'object') ? raw : JSON.parse(raw); }
      catch (e) { resp = { error: 1, text: 'Ошибка разбора ответа сервера' }; }

      if (resp && resp.error) {
        $overlay.html('<div style="padding:12px;color:#b44c4c;">' + (resp.text || 'Ошибка') + '</div>');
        return;
      }

      $overlay.html(resp.html || '<div style="padding:12px;">Нет данных</div>');

      // Закрытие по клику на затемнение
      $overlay.off('click.clanControl').on('click.clanControl', function (e) {
        if (e.target === this) cleanupModal();
      });

      // Закрытие по ESC
      $(document).off('keydown.clanControl').on('keydown.clanControl', function (e) {
        if (e.key === 'Escape') cleanupModal();
      });

      // Drag on desktop
      if (typeof device !== 'undefined' && device && typeof device.mobile === 'function' && !device.mobile()) {
        const $dialog = $overlay.find('.modal--clan-control__dialog');
        if ($dialog.length && typeof $dialog.draggabilly === 'function') {
          $dialog.draggabilly({ handle: '.header', containment: true });
        }
      }
    },
    error: function () {
      const $overlay = $('.modal--clan-control').first();
      if ($overlay.length) {
        $overlay.html('<div style="padding:12px;color:#b44c4c;">Ошибка соединения</div>');
      }
    }
  });
}


function actionClan(action) {
    // Универсальная функция для действий с кланом
    let dataToSend = '';
    // Для удобства — флаги
    const $input = $("#clanMoneyInput");
    const $clanAbout = $("#clanAbout");

    // Обработка разных действий
    switch (action) {
        case 'openMoney':
            $('#addMoney').hide();
            $('#countMoney').show();
            $('#getMoney').replaceWith(
                `<input type="button" class="btnA" value="${Lang.button_clan_add_money}" onclick='actionClan("addMoney");'>`
            );
            return false;
        case 'addMoneys':
            $('#addMoney').hide();
            $('#countMoney').show();
            $('#getMoney').replaceWith(
                `<input type="button" class="btnA" value="${Lang.text_get}" onclick='actionClan("minusMoney");'>`
            );
            return false;
        case 'addMoney':
        case 'minusMoney': {
            let count = $input.val();
            count = /^\d+$/.test(count) && parseInt(count) > 0 ? parseInt(count) : null;
            if (!count) {
                Game.notifications.main("Введите корректное положительное число!", "error");
                $input.focus();
                return false;
            }
            dataToSend = action + ',' + count;
            break;
        }
        case 'clanAbout': {
            let about = $clanAbout.val();
            if (!about || !about.trim()) {
                Game.notifications.main("Введите текст объявления!", "error");
                $clanAbout.focus();
                return false;
            }
            dataToSend = action + ',' + about.trim();
            break;
        }
        default:
            dataToSend = action;
    }

    $.ajax({
        url: "/do/clanAction.php",
        type: "POST",
        data: 'object=' + encodeURIComponent(dataToSend),
        success: function (response) {
            let data;
            try {
                data = typeof response === "string" ? JSON.parse(response) : response;
            } catch (e) {
                Game.notifications.main("Ошибка сервера или неверный формат ответа.", "error");
                return;
            }
            if (data['error'] == 1) {
                Game.notifications.main(data['text'], 'error');
            } else {
                Game.notifications.main(data['text'], 'success');
            }
            // Если действие повлияло на состав, обновим карточку клана
            if (data['action'] == 'updateClan') {
                openClanCard(data['id']);
            }
            // Обновление прогресс-бара уровня и опыта
            if (
                data['clan_level'] !== undefined &&
                data['clan_exp'] !== undefined &&
                data['clan_exp_next'] !== undefined
            ) {
                $('.model .clan-level-block').remove();
                var expPercent = Math.floor(data['clan_exp'] / data['clan_exp_next'] * 100);
                var clanLevelBar = `
                  <div class="clan-level-block" style="margin:10px 0;">
                    <div style="display:flex;align-items:center;gap:10px;">
                      <span style="font-weight:bold;font-size:18px;">Ур. клана: ${data['clan_level']}</span>
                      <div style="flex:1;">
                        <div style="height:14px;background:#eee;border-radius:6px;overflow:hidden;position:relative;">
                          <div style="background:#8ecf3c;height:100%;width:${expPercent}%;transition:width .4s"></div>
                          <div style="position:absolute;left:0;top:0;width:100%;text-align:center;font-size:13px;line-height:14px;color:#333;">
                            ${data['clan_exp']} / ${data['clan_exp_next']} опыта (${expPercent}%)
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                `;
                $('.model .content-model').prepend(clanLevelBar);
            }
        },
        error: function () {
            Game.notifications.main("Ошибка соединения с сервером. Попробуйте позже.", "error");
        }
    });
}

function clanAdmin(type, id) {
    $.ajax({
        url: "/do/clanAction.php",
        type: "POST",
        data: 'object=' + type + '&name=' + id,
        success: function (response) {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
            response['error'] == 1
                ? Game.notifications.main(response['text'], 'error')
                : Game.notifications.main(response['text'], 'success');
        }
    });
}
function calendarCategory(type,e){
		$('.CraftCategory .Button').removeClass('active');
		$('.'+type+'').addClass('active');
		$.ajax({
			url: "/do/calendar",
			type: "POST",
			data: "category="+type,
			beforeSend: function(){
				$('.CraftContent').html('<center>'+mainLoader+'</center>');
			},
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				$('.CraftContent').html(response["html"]);
			}
		});
}
function craftCategory(type, id_isset = false, e) {
    var tlp = $('.tooltip');
    var event = e || window.event;

    // Показываем tooltip только если запрошен конкретный рецепт (id_isset)
    if (id_isset) {
        // Если e не передан, берем координаты курсора мыши с помощью jQuery
        var left = 100, top = 100;
        if (event && event.clientX) {
            left = (event.clientX - 134);
            top = (event.clientY + 15);
        }
        if (left < 0) left = 0;

        tlp.css({
            "left": left + 'px',
            "top": top + 'px'
        });

        tlp.html('<center>' + mainLoader + '</center>').show();

        $.ajax({
            url: "/do/craft",
            type: "POST",
            data: { t: "ItemOpen", item: type },
            success: function (response) {
                response = (typeof response === 'string') ? JSON.parse(response) : response;
                tlp.html(response["html"]).show();
            }
        });
    } else {
        // Смена категории: сбрасываем active и ставим на выбранную
        $('.CraftCategory .Button').removeClass('active');
        $('.CraftCategory .Button.' + type).addClass('active');

        $.ajax({
            url: "/do/craft",
            type: "POST",
            data: { t: "category", category: type },
            beforeSend: function () {
                $('.CraftContent').html('<center>' + mainLoader + '</center>');
            },
            success: function (response) {
                response = (typeof response === 'string') ? JSON.parse(response) : response;
                $('.CraftContent').html(response["html"]);
                // Если есть craftList и craftRecipeDetail, очищаем детали рецепта
                if ($('#craftRecipeDetail').length) {
                    $('#craftRecipeDetail').html('');
                }
            }
        });
    }
}

function discoveryCategory(type,id_isset=false,e){
  var tlp = $('.tooltip'),
  e = window.event,
  element = $(e),
  left = (e.clientX - 134),
  top = (e.clientY - 100);
  if(left < 0) {
    left = 0;
  }
  $(tlp).css({
      "left": left+'px',
      "top": top+'px'
  });
    tlp.html('<center>'+mainLoader+'</center>');
	if(id_isset){
		$.ajax({
			url: "/do/Discovery",
			type: "POST",
			data: "category="+type,
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				tlp.html(response["html"]).show();
			}
		});
	}else{
		$('.CraftCategory .Button').removeClass('active');
		$('.'+type+'').addClass('active');
		$.ajax({
			url: "/do/Discovery",
			type: "POST",
			data: "category="+type,
			beforeSend: function(){
				$('.CraftContent').html('<center>'+mainLoader+'</center>');
			},
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				$('.CraftContent').html(response["html"]);
			}
		});
	}
}
function workCategory(type,e){
		$.ajax({
			url: "/do/work",
			type: "POST",
			data: "category="+type,
			beforeSend: function(){
				$('.WorkPokemon').html('<center>'+mainLoader+'</center>');
			},
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				$('.WorkPokemon').html(response["html"]);
			}
		});
}
function shoplavktype(){
    var t2 = $('.typeProduct').attr('data-title');
    if(t2 == "1"){
        $('.typeProduct').attr('data-title',2);
        $('.typeProduct').html("Инвентарь");
    }else{
        $('.typeProduct').attr('data-title',1);
        $('.typeProduct').html("Покемоны");
    }
    $.ajax({
			url: "/do/shoplavk",
			type: "POST",
			data: "category="+t2,
			beforeSend: function(){
				$('.productList').html('<center>'+mainLoader+'</center>');
			},
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				$('.productList').html(response["html"]);
			}
		});
}
function sortPr(id){
    $('.sort').removeClass('active');
	$('.s'+id+'').addClass('active');
    var t2 = $('.typeProduct').attr('data-title');
    if(t2 == 1){
        var type = 2;
    }else{
        var type = 1;
    }

    var num = $('#searchNumber').val();
    if(num != ""){ var d1 = "AND `num` = "+num;}else{ d1 = ""; }

    var char = $('#searchCharacter').val();
    if(char != "0"){ var d2 = "AND `charac` = "+char;}else{ d2 = ""; }

    var lvl = $('#searchLvl').val();
    if(lvl != ""){ var d3 = "AND `lvl` = "+lvl;}else{ d3 = ""; }

    var okr = $('#searchOkras').val();
    if(okr != "0"){ var d4 = "AND `okras` = "+okr;}else{ d4 = ""; }

    var dop = d1+" "+d2+" "+d3+" "+d4;
    $.ajax({
			url: "/do/shoplavk",
			type: "POST",
			data: "sort="+id+"&typ="+type+"&dop="+dop,
			beforeSend: function(){
				$('.productList').html('<center>'+mainLoader+'</center>');
			},
			success: function (response) {
				response = (typeof response === 'string') ? JSON.parse(response) : response;
				$('.productList').html(response["html"]);
			}
		});
}

function sortProduct(){
    var t2 = $('.typeProduct').attr('data-title');
    if(t2 == 1){
        issetAll(2,'sortProduct');
    }else{
        issetAll(1,'sortProduct');
    }
}
function catatcdex(type,b=false){
  if(type == "normal"){var tip = "Нормальный";}
  else if(type == "fighting"){var tip = "Боевой";}
  else if(type == "fly"){var tip = "Летающий";}
  else if(type == "poison"){var tip = "Ядовитый";}
  else if(type == "ground"){var tip = "Земляной";}
  else if(type == "rock"){var tip = "Каменный";}
  else if(type == "bug"){var tip = "Насекомый";}
  else if(type == "ghost"){var tip = "Призрачный";}
  else if(type == "fire"){var tip = "Огненный";}
  else if(type == "water"){var tip = "Водный";}
  else if(type == "grass"){var tip = "Травяной";}
  else if(type == "electric"){var tip = "Электрический";}
  else if(type == "psychic"){var tip = "Психический";}
  else if(type == "ice"){var tip = "Ледяной";}
  else if(type == "dragon"){var tip = "Драконий";}
  else if(type == "dark"){var tip = "Темный";}
  else if(type == "steel"){var tip = "Стальной";}
  else if(type == "fairy"){var tip = "Волшебный";}
  else if(type == "all"){var tip = "Все";}
  else if(type == "physical"){var tip = "Физические";}
  else if(type == "special"){var tip = "Специальные";}
  else if(type == "status"){var tip = "Статусные";}
  else if(type == "specific"){var tip = "Специфические";}
  var t1 = $('.catatack').attr('data-title');
  var t2 = $('.catcatack').attr('data-title');
  $(".tooltip").hide();
  $('.AtcBtn').attr('data-title',1);
  if(b == 1){
    $('.catatack').attr('data-title',type);
      $('.catatack').attr('class','catatack type'+type);
    $('.catatack').html(tip);
    $.ajax({
      url: "/do/atc",
      type: "POST",
      data: "cate="+type+"&cat="+t2,
      success: function (response) {
        response = (typeof response === 'string') ? JSON.parse(response) : response;
        $('.ListAtack').html(response["html"]);
      }
    });
  }else{
    $('.catcatack').attr('data-title',type);
      $('.catcatack').attr('class','catcatack type'+type);
    $('.catcatack').html(tip);
    $.ajax({
      url: "/do/atc",
      type: "POST",
      data: "cat="+type+"&cate="+t1,
      success: function (response) {
        response = (typeof response === 'string') ? JSON.parse(response) : response;
        $('.ListAtack').html(response["html"]);
      }
    });
  }
}
function atcdexload(){
  var cat = $('.catcatack').attr('data-title');
  var cate = $('.catatack').attr('data-title');
  var id = $('.AtcBtn').attr('data-title');
  var data_title = Number.parseInt(id);
  $('.AtcBtn').attr('data-title',data_title+1);
  $.ajax({
    url: "/do/atc",
    type: "POST",
    data: "id="+id+"&cat="+cat+"&cate="+cate,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      $('.ListAtack').append(response["html"]);
    }
  });
}
function abldexload(){
  var id = $('.AblBtn').attr('data-title');
  var data_title = Number.parseInt(id);
  $('.AblBtn').attr('data-title',data_title+1);
  $.ajax({
    url: "/do/abl",
    type: "POST",
    data: "id="+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      $('.ListAbility').append(response["html"]);
    }
  });
}
function transferload(){
  var id = $('.DopBtn').attr('data-title');
  var data_title = Number.parseInt(id);
  $('.DopBtn').attr('data-title',data_title+1);
  $.ajax({
    url: "/do/transfer",
    type: "POST",
    data: "id="+id,
    success: function (response) {
      response = (typeof response === 'string') ? JSON.parse(response) : response;
      $('.list').append(response["html"]);
    }
  });
}
function trophyuserlvl(lvl){

    $.ajax({
  			url: "/do/trophylvluser",
  			type: "POST",
  			data: "trophy_lvl="+lvl,
  			success: function (response) {
  				response = (typeof response === 'string') ? JSON.parse(response) : response;
  				Game.notifications.main(response["html"], response["error"]);
                  if(response['plus']) {
                    Game.notifications.main(response["plus"], 'plus');
                  }
                  if(response['minus']) {
                    Game.notifications.main(response["minus"], 'minus');
                  }
                  openModal('lvlpr');
  			}
  		});
}
function transfPokM(id,tr){

  		$.ajax({
  			url: "/do/transfer",
  			type: "POST",
  			data: "pt="+id+"&tr="+tr,
  			success: function (response) {
  				response = (typeof response === 'string') ? JSON.parse(response) : response;
  				Game.notifications.main(response["html"], response["error"]);
  				if(response["error"] == "success"){
  				    $('.id'+id+'').hide();
  				    $('.Modal .TransferContent .PokemonVeiw').html('');
  				}
                  if(response['plus']) {
                    Game.notifications.main(response["plus"], 'plus');
                  }
                  if(response['minus']) {
                    Game.notifications.main(response["minus"], 'minus');
                  }
  			}
  		});
}

function deletePokM(id){
  		$.ajax({
  			url: "/do/transfer",
  			type: "POST",
  			data: "del="+id,
  			success: function (response) {
  				response = (typeof response === 'string') ? JSON.parse(response) : response;
  				Game.notifications.main(response["html"], response["error"]);
  				if(response["error"] == "success"){
  				    $('.id'+id+'').hide();
  				    $('.Modal .TransferContent .PokemonVeiw').html('');
  				}
  			}
  		});
}

function transfItem(){
  		openModal('transfer');
  		$.ajax({
  			url: "/do/transfer",
  			type: "POST",
  			data: "it=1",
  			success: function (response) {
  				response = (typeof response === 'string') ? JSON.parse(response) : response;
  				Game.notifications.main(response["html"], response["error"]);
                  if(response['plus']) {
                    Game.notifications.main(response["plus"], 'plus');
                  }
                  if(response['minus']) {
                    Game.notifications.main(response["minus"], 'minus');
                  }
  			}
  		});
}
function transfPok(pok){
  		$('.listPok').removeClass('active');
  		$('.id'+pok+'').addClass('active');
  		$.ajax({
  			url: "/do/transfer",
  			type: "POST",
  			data: "pok="+pok,
  			beforeSend: function(){
  				$('.PokemonVeiw').html('<center>'+mainLoader+'</center>');
  			},
  			success: function (response) {
  				response = (typeof response === 'string') ? JSON.parse(response) : response;
  				$('.PokemonVeiw').html(response["html"]);
  			}
  		});
}
function craftItem(id, category) {
    $(".tooltip").hide();
    var count = $('#CountCraft').val();

    // Проверка на корректность количества (число, не меньше 1)
    count = parseInt(count, 10);
    if (isNaN(count) || count < 1) count = 1;

    $.ajax({
        url: "/do/CraftItems",
        type: "POST",
        data: {
            t: "craft",
            item: id,
            count: count,
            category: category // всегда передаем категорию!
        },
        success: function (response) {
            // Если response — строка, парсим
            if (typeof response === "string") {
                try {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (e) {
                    Game.notifications.main("Ошибка обработки ответа сервера!", "error");
                    return;
                }
            }
            // Главное уведомление (успех или ошибка)
            Game.notifications.main(response["html"], response["error"]);

            // Если есть html с минусом (что списано) — отдельное уведомление
            if (response['minus']) {
                Game.notifications.main(response["minus"], 'minus');
            }

            // Если есть html с плюсом (что получено) — отдельное уведомление
            if (response['plus']) {
                Game.notifications.main(response["plus"], 'plus');
            }

            // Используем категорию из ответа, если она есть, иначе ту, что отправляли
            var cat = response.category || category;

            // Перезагрузка текущей категории крафта (чтобы обновить список и доступные предметы)
            if (typeof craftCategory === "function") {
                craftCategory(cat);
            }
            // Если есть функция обновления инвентаря, её тоже можно вызвать здесь
            if (typeof updateInventory === "function") {
                updateInventory();
            }
        },
        error: function () {
            Game.notifications.main("Ошибка соединения с сервером.", "error");
        }
    });
}

function craftItemComplete(id, category) {
    $(".tooltip").hide();

    $.ajax({
        url: "/do/CraftItems",
        type: "POST",
        data: {
            t: "give",
            item: id,
            category: category // всегда передаем категорию!
        },
        success: function (response) {
            // Если response — строка, парсим
            if (typeof response === "string") {
                try {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (e) {
                    Game.notifications.main("Ошибка обработки ответа сервера!", "error");
                    return;
                }
            }
            Game.notifications.main(response["html"], response["error"]);
            if (response['plus']) {
                Game.notifications.main(response["plus"], 'plus');
            }

            var cat = response.category || category;

            // После получения предмета тоже обновим инвентарь и список, если нужно
            if (typeof craftCategory === "function") {
                craftCategory(cat);
            }
            if (typeof updateInventory === "function") {
                updateInventory();
            }
        },
        error: function () {
            Game.notifications.main("Ошибка соединения с сервером.", "error");
        }
    });
}

// Функция добавления/удаления рецепта в избранное через AJAX
function toggleFavorite(itemId, el) {
    $.ajax({
        url: "/do/craft.php",
        type: "POST",
        data: { toggleFavorite: 1, item_id: itemId },
        dataType: "json",
        success: function (response) {
            if (response.result === "added") {
                $(el).addClass("active").html("★");
            } else if (response.result === "removed") {
                $(el).removeClass("active").html("☆");
            } else if (response.result === "error") {
                alert("Ошибка: вы не авторизованы или нет user_id в сессии!");
            }
        },
        error: function () {
            alert("Ошибка при изменении избранного.");
        }
    });
}
function rand(min, max){
      var r = Math.random() * (max - min) + min;
      return r;
}

function scrollGift(response){
	var gift_width  = $('.gifts .innerGift').outerHeight();

	$('<li />', {'html': '<img src="/img/world/items/little/'+response['id']+'.png">'}).appendTo('.innerGift');
		$('.innerGift li:first').animate({
								'marginTop':'-'+(gift_width+10)+'px'
							}, rand(9000,12000)).queue(function(){
					$('.goRoll').html('<p style="color: #0e39b9c7;">Вы получили: '+response['name']+' <b>x'+response['count']+'</b></p>');
					Game.notifications.main('<img src="img/world/items/little/'+response['id']+'.png" class="item"> '+response['name']+' <b>x'+response['count']+'</b>','plus');
		});
}
function scrollBox(response,t){
    if(!response['error']){

        $('.buttonCase').remove();
	var box_width  = $('.animationImage .innerBox').outerHeight();

	$('<li />', {'html': '<img src="/img/world/items/little/'+response['id']+'.png">'}).appendTo('.innerBox');
		$('.innerBox li:first').animate({
								'marginTop':'-'+(box_width+10)+'px'
							}, rand(9000,12000)).queue(function(){
							    if(response['type'] == 'item'){
							        Game.notifications.main('<img src="img/world/items/little/'+response['id']+'.png" class="item"> '+response['name']+' <b>x'+response['count']+'</b>','plus');
							    }else{
							        Game.notifications.main('<img src="img/pokemons/animation/'+response['pok']+'.png"> '+response['name']+' ','plus');
							    }

		});
		setTimeout(function(){
	    $( ".animationImage" ).fadeOut( "slow", function() {
								$('.animationImage').remove();
								Game.notifications.main(response['minus'],'minus');
								Game.notifications.main(response['text'],'success');

    Game.modals.inventory(t);
							});
						}, 13000);
    }else{

								Game.notifications.main(response['error'],'error');
    }
}
function uploadImg() {
    var u = $('#upload_file #file_name').prop('files')[0];
    if (!u) {
        Game.notifications.main('Выберите файл для загрузки', "error");
        return false;
    }
    var s = new FormData();
    s.append('file', u);
    var j = $('#upload_file').html();
    $.ajax({
        url: '/do/upload',
        dataType: 'json', // обязательно JSON!
        cache: false,
        contentType: false,
        processData: false,
        data: s,
        type: 'POST',
        beforeSend: function() {
            $('#upload_file').html('<center><img height="40px" src="/img/loader/loader.gif"></center>');
        },
        success: function(resp){
            $('#upload_file').html('');
            if (typeof resp === "string") {
                try { resp = JSON.parse(resp); } catch(e) { resp = {}; }
            }
            if(resp && resp.notify) {
                Game.notifications.main(resp.notify.text, resp.notify.type);
            } else if(resp && resp.message) {
                Game.notifications.main(resp.message, resp.status === "success" ? "success" : "error");
            } else {
                Game.notifications.main("Неизвестный ответ сервера.", "error");
            }
        },
        error: function(xhr){
            $('#upload_file').html(j);
            Game.notifications.main("Ошибка загрузки файла", "error");
        }
    });
    return false;
}
function CloseModel(){
  $('.model').remove();
}
function copyStringToClipboard (str) {
  var el = document.createElement('textarea');
  el.value = str;
  el.setAttribute('readonly', '');
  el.style.position = 'absolute';
  el.style.left = '-9999px';
  document.body.appendChild(el);
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  Game.notifications.main("Код скопирован", "success");
}

function gym_battle() {
  $.ajax({
		url: "/do/gym",
		type: "POST",
		data: "type=sekretar",
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
        $('.LittleModal').remove();
        $('<div />', {
          "class": 'LittleModal',
          html: response['html']
        }).appendTo('body');
        $('.LittleModal').draggabilly({
          handle: '#drgMini',
          containment: true
        });
		}
	});
}
function gym_application(){
	var id = $('#SortGym').val();
	$.ajax({
		url: "/do/gym",
		type: "POST",
		data: "type=application&gym="+id,
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			Game.notifications.main(response["html"], response["error"]);
		}
	});
}
function gym_answer(user,id){
	$.ajax({
		url: "/do/gym",
		type: "POST",
		data: "type=answer&user="+user+"&answer="+id,
		success: function (response) {
			response = (typeof response === 'string') ? JSON.parse(response) : response;
			Game.notifications.main(response["html"], response["error"]);
			gym_battle();
		}
	});
}
function settings(){
  $.ajax({
    url: "/do/trainers",
    type: "POST",
    data: "type=setting",
    success: function (raw){
      var response = {};
      try{ response = JSON.parse(raw); }catch(e){ response = {}; }

      /* ---------- CSS (один раз) ---------- */
      (function injectCSS(){
        if (document.getElementById('settings-modern-css')) return;
        var css = `
:root{
  --card:#fff; --bg:#f7f9ff; --br:#e7ecff; --txt:#1b2b4f; --sub:#6f7b95; --chip:#eef3ff;
  --ac:#5b6cff; --ac2:#3646d6;
  --tab-h: 36px;        /* desktop */
  --tab-h-m: 32px;      /* mobile  */
  --tab-w-m: 112px;     /* mobile width */
  --tab-fs: 12px;
  --nav-h-m: 44px;      /* mobile nav height */
}

body.modal-open--settings{ overflow:hidden; }

/* ---- Modal ---- */
.LittleModal.settings{
  position: fixed; z-index:520; left:50%; top:48px; transform:translateX(-50%);
  width: 760px; max-width: 94vw; max-height: calc(100vh - 64px);
  background: var(--card); color: var(--txt); border: 1px solid var(--br);
  border-radius: 16px; box-shadow: 0 10px 26px rgba(23,35,74,.12);
  display:flex; flex-direction:column; font-family: Inter, Nunito, Arial, sans-serif;
}
.LittleModal .Header{
  display:flex; align-items:center; justify-content:space-between;
  padding: 10px 12px; background:linear-gradient(180deg,var(--bg),transparent);
  border-bottom: 1px solid var(--br);
}
.LittleModal .Header .Name{ font-weight:900; font-size:18px; }
.LittleModal .Header .Close{
  width:34px; height:34px; border-radius:10px; display:grid; place-items:center; cursor:pointer;
  border:1px solid var(--br); background:var(--card);
}

/* ---- Desktop layout (grid) ---- */
.LittleModal .Body{
  display:grid; grid-template-columns: 210px 1fr; gap:12px; padding:10px; flex:1 1 auto; min-height:0;
}
.LittleModal .Nav{
  border:1px solid var(--br); border-radius:12px; background:var(--bg); padding:8px;
  overflow:auto; min-height:0; will-change:transform;
}
.LittleModal .Pane{ border:1px solid var(--br); border-radius:12px; background:var(--bg); display:flex; flex-direction:column; min-height:0; }
.LittleModal .Scroll{ padding:10px; overflow:auto; min-height:0; }

/* ---- Tabs (stable size) ---- */
.LittleModal .catSet{
  height: var(--tab-h); line-height: calc(var(--tab-h) - 2px);
  padding: 0 10px; display:flex; align-items:center; gap:6px;
  border-radius:10px; cursor:pointer; user-select:none; box-sizing:border-box;
  border:1px solid var(--br); background:var(--chip); color:var(--txt);
  font-weight:800; font-size: var(--tab-fs); margin-bottom:8px;
  transition: border-color .15s, box-shadow .15s, transform .02s, background .15s, color .15s;
}
.LittleModal .catSet i{ width:16px; text-align:center; font-size:13px; opacity:.9; }
.LittleModal .catSet span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100%; }
.LittleModal .catSet.Active{ border-color:#5b6cff; background:#eef5ff; box-shadow:0 0 0 2px rgba(46,116,255,.16); }
.LittleModal .catSet:active{ transform: translateY(.5px); }

/* ---- Rows ---- */
.LittleModal .BlockContent{ display:none; }
.LittleModal .BlockContent.Active{ display:block; }
.LittleModal .Step{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding:10px; border-radius:12px; background:var(--card); border:1px solid var(--br); margin-bottom:10px;
}
.LittleModal .Step .Name{ font-weight:800; display:flex; align-items:center; gap:6px; }
.LittleModal .Buttons{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.LittleModal .Buttons > div, .LittleModal .Buttons button{
  border:1px solid var(--br); background:var(--card); border-radius:10px; padding:7px 10px; cursor:pointer; font-weight:800;
}
.LittleModal input[type="password"], .LittleModal input[type="text"], .LittleModal select{
  border:1px solid var(--br); background:var(--card); color:var(--txt); border-radius:10px; padding:8px 10px; min-width:220px;
}
.LittleModal .Color{ width:30px; height:30px; border-radius:10px; border:1px solid var(--br); cursor:pointer; }
.LittleModal .attack-lang-switcher{ border:1px dashed #5b6cff; color:#3646d6; }
.LittleModal .theme-switcher-btn{ border:1px dashed var(--br); }

/* ---- Mobile: switch grid -> flex (fix) ---- */
@media (max-width:760px){
  .LittleModal.settings{
    left: 8px; right: 8px; top: calc(env(safe-area-inset-top,0px) + 8px); transform:none;
    width:auto; max-width:none; max-height: calc(100vh - 16px - env(safe-area-inset-top,0px) - env(safe-area-inset-bottom,0px));
    z-index: 99999;
  }
  /* ВАЖНО: убираем grid полностью */
  .LittleModal .Body{
    display:flex; flex-direction:column; gap:8px; padding:8px;
    min-height:0; flex:1 1 auto;
  }
  .LittleModal .Nav{
    display:flex; align-items:center; gap:6px; padding:6px 8px;
    height: var(--nav-h-m); min-height: var(--nav-h-m); flex:0 0 var(--nav-h-m);
    box-sizing:border-box; border-right:0; border-bottom:1px solid var(--br);
    overflow-x:auto; overflow-y:hidden; white-space:nowrap; -webkit-overflow-scrolling:touch;
    scrollbar-gutter: stable both-edges; contain: layout paint;
  }
  .LittleModal .catSet{
    height: var(--tab-h-m); line-height: calc(var(--tab-h-m) - 2px);
    flex: 0 0 var(--tab-w-m); width: var(--tab-w-m); margin:0; padding:0 8px; border-radius:9px; font-size:11px;
  }
  .LittleModal .catSet i{ width:14px; font-size:12px; }
  .LittleModal .Pane{ flex:1 1 auto; min-height:0; display:flex; }
  .LittleModal .Scroll{ flex:1 1 auto; padding:10px; overflow:auto; min-height:0; }
  .LittleModal .Step{ flex-direction:column; align-items:stretch; gap:8px; }
  .LittleModal .Buttons > div, .LittleModal .Buttons button{ min-height:16px; padding:8px 10px; }
  .LittleModal input[type="password"], .LittleModal input[type="text"], .LittleModal select{ min-width:100%; width:100%; }
}
        
/* ---- Hotkeys (v3) ---- */
.hk-top{display:flex;align-items:center;justify-content:space-between;gap:12px}
.hk-badge{display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;font-size:12px;line-height:18px;background:rgba(0,0,0,.16);border:1px solid rgba(255,255,255,.10);opacity:.9}
.hk-switch{gap:10px}
.hk-switch-text{font-size:13px;opacity:.85;white-space:nowrap}
.hk-hint{margin-top:8px;font-size:12px;opacity:.75}
.hk-inline-kbd{display:inline-block;padding:2px 6px;border-radius:7px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.14);font-size:12px;line-height:1.3}

.hk-details{margin-top:10px;border:1px solid rgba(255,255,255,.10);border-radius:14px;padding:10px 12px;background:rgba(0,0,0,.10)}
.hk-details > summary{cursor:pointer;list-style:none;font-weight:800;outline:none;user-select:none}
.hk-details > summary::-webkit-details-marker{display:none}
.hk-details > summary:after{content:'▾';float:right;opacity:.6}
.hk-details[open] > summary:after{content:'▴'}

.hk-help{margin-top:10px}
.hk-search{width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.10);background:rgba(0,0,0,.14);color:inherit;outline:none}
.hk-search::placeholder{opacity:.55}

.hk-group{margin-top:14px}
.hk-group-title{font-weight:900;margin:10px 0 8px 0;opacity:.95;letter-spacing:.2px}

.hk-row{
  display:grid;
  grid-template-columns: 230px 1fr;
  gap:12px;
  align-items:center;
  padding:10px 10px;
  border-radius:14px;
}
.hk-row:nth-child(odd){background:rgba(255,255,255,.04)}
.hk-row:hover{background:rgba(255,255,255,.06)}

.hk-kbd{display:flex;flex-wrap:wrap;gap:6px;align-items:center;min-width:0}
.hk-kbd kbd{
  padding:4px 9px;
  border-radius:10px;
  border:1px solid rgba(255,255,255,.16);
  background:rgba(0,0,0,.16);
  font-size:12px;
  line-height:1;
  font-weight:800;
}
.hk-plus{opacity:.55;font-size:12px;padding:0 2px}

.hk-desc{min-width:0}
.hk-desc .hk-action{font-weight:800}
.hk-desc .hk-note{margin-top:3px;font-size:12px;opacity:.72}

.hk-empty{margin-top:10px;font-size:12px;opacity:.7}

@media (max-width:760px){
  .hk-row{grid-template-columns: 1fr; gap:8px}
  .hk-kbd{min-width:0}
}
`;
        var st = document.createElement('style'); st.id='settings-modern-css'; st.textContent = css;
        document.head.appendChild(st);
      })();

      /* ---------- HTML ---------- */
      $('.LittleModal').remove();
      var snd = (response['sound'] == 0 ? 'Включить' : 'Выключить'),
          mis = (response['mission'] == 0 ? 'Получать' : 'Отключить'),
          hcl = (response['HotClick'] == 0 ? 'Включить' : 'Отключить'),
          atkLang = response['attack_lang'] == 'eng' ? 'English' : 'Русский',
          atkLangSwitch = response['attack_lang'] == 'eng' ? 'rus' : 'eng',
          atkLangBtn = response['attack_lang'] == 'eng' ? 'Русский' : 'English';

      var hkHelp = '';
      try{ hkHelp = (window.GameHotkeys && GameHotkeys.renderHelpHTML) ? GameHotkeys.renderHelpHTML() : ''; }catch(e){ hkHelp = ''; }
      if(!hkHelp){ hkHelp = '<div class="hk-muted">Список хоткеев недоступен.</div>'; }
      /* ---------- Hotkeys: FIX CSS (адаптив + нормальная ширина) ---------- */
      (function injectHotkeysFixCSS(){
        if (document.getElementById('hotkeys-fix-css')) return;
        var st = document.createElement('style');
        st.id = 'hotkeys-fix-css';
        st.textContent = `
/* Hotkeys help layout fix */
.hkStep{display:block !important}
.hkStep .hk-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.hkStep .hk-head .Name{flex:1;min-width:0}
.hkStep .hk-head .Buttons{flex:0 0 auto;display:flex;align-items:center;gap:10px}
.hk-details{width:100%;box-sizing:border-box}
.hk-panel{margin-top:10px}
.hk-help{margin-top:10px}

/* Rows: flex instead of fixed grid (лучше на узких экранах) */
.hk-row{display:flex;align-items:flex-start;gap:12px;padding:10px 10px;border-radius:14px}
.hk-row:nth-child(odd){background:rgba(0,0,0,.03)}
.hk-row:hover{background:rgba(0,0,0,.05)}
.hk-kbd{flex:0 0 auto;max-width:45%;display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.hk-desc{flex:1 1 auto;min-width:0}
.hk-action{font-weight:800;line-height:1.25}
.hk-note{margin-top:4px;font-size:12px;opacity:.75;line-height:1.25}
.hk-group-title{margin:12px 0 8px 0}

/* Search */
.hk-search{width:100%;box-sizing:border-box}

/* Mobile */
@media (max-width: 760px){
  .hk-row{flex-direction:column;gap:8px}
  .hk-kbd{max-width:100%}
}
        `;
        document.head.appendChild(st);
      })();



      var $m = $('<div class="LittleModal settings" role="dialog" aria-modal="true"/>');
      var html = '';

      html += '<div class="Header">';
      html += '  <div class="Name">Настройки</div>';
      html += '  <div class="Close" onclick="closeLittleModal()"><i class="fas fa-times"></i></div>';
      html += '</div>';

      html += '<div class="Body">';
      html += '  <div class="Nav">';
      html += '    <div class="catSet Active" data-tab="1"><i class="fa fa-user"></i><span>Профиль</span></div>';
      html += '    <div class="catSet" data-tab="2"><i class="fas fa-gamepad"></i><span>Игра</span></div>';
      html += '    <div class="catSet" data-tab="3"><i class="fa fa-lock"></i><span>Безопасность</span></div>';
      html += '    <div class="catSet" data-tab="4"><i class="fa fa-ellipsis-h"></i><span>Прочее</span></div>';
      html += '  </div>';

      html += '  <div class="Pane"><div class="Scroll">';

      /* TAB 1 */
      html += '    <div class="BlockContent Active" data-id="1">';
      html += '      <div class="Step"><div class="Name">E-mail</div><div class="Buttons"><font color="green">'+(response['mail']||'—')+'</font></div></div>';
      html += '      <div class="Step"><div class="Name">Аватарка <i class="fas fa-question-circle avatarsGame"></i></div><div class="Buttons"><form enctype="multipart/form-data" action="do/upload" method="post" id="upload_file" name="MAX_FILE_SIZE" value="1"><input type="file" name="file_name" id="file_name"/></form><div onclick="uploadImg();">Сохранить</div></div></div>';
      html += '      <div class="Step"><div class="Name">Ваш код <i class="fas fa-question-circle promoCode"></i></div><div class="Buttons"><b>'+(response['promo']||'—')+'</b></div></div>';
      html += '      <div class="Step"><div class="Name">Реферальный код <i class="fas fa-question-circle referalCode"></i></div><div class="Buttons"><b>'+(response['referal']||'—')+'</b> <i class="clickable fas fa-copy" onclick="copyStringToClipboard(\''+(response['referal']||'')+'\')"></i></div></div>';
      html += '      <div class="Step"><div class="Name">Вас пригласил</div><div class="Buttons">'+ (response['referal_you'] ? ('<b>'+response['referal_you']+'</b>') : '<span style="color:#999">Не указан</span>') +'</div></div>';
      html += '      <div class="Step"><div class="Name">Ваши рефералы <i class="fas fa-question-circle referalListTool"></i></div><div class="Buttons"><div onclick="issetAll(1,\'referal\');">Показать</div></div></div>';
      html += '    </div>';

      /* TAB 2 */
      html += '    <div class="BlockContent" data-id="2">';
      html += '      <div class="Step"><div class="Name">Уведомления <i class="fas fa-question-circle audioGame"></i></div><div class="Buttons"><div onclick="Aqua.users.edit.redact(\'audio\','+(response['soundTwo'])+')">'+snd+'</div></div></div>';
      html += '      <div class="Step"><div class="Name">Ежедневные задания <i class="fas fa-question-circle missionGame"></i></div><div class="Buttons"><div onclick="Aqua.users.edit.redact(\'mission\','+(response['missionTwo'])+')">'+mis+'</div></div></div>';
      
      // Hotkeys (переключатель + справка)
      html += '      <div class="Step hkStep">';
      html += '        <div class="hk-head">';
      html += '          <div class="Name">Горячие клавиши <i class="fas fa-question-circle HotClick" title="Сочетания клавиш для быстрого управления."></i></div>';
      html += '          <div class="Buttons">';
      html += '            <label class="hk-switch"><input type="checkbox" class="hk-toggle" '+(response['HotClick']==0?'':'checked')+'><span class="hk-slider"></span></label>';
      html += '            <span class="hk-badge">'+(response['HotClick']==0?'выкл':'вкл')+'</span>';
      html += '          </div>';
      html += '        </div>';
      html += '        <details class="hk-details">';
      html += '          <summary>Справка и список комбинаций</summary>';
      html += '          <div class="hk-panel">';
      html += '            <div class="hk-hint">Совет: нажмите <span class="hk-inline-kbd">Ctrl</span>+<span class="hk-inline-kbd">/</span>, чтобы открыть справку и сразу перейти к поиску.</div>';
      html += '            <input class="hk-search" type="text" placeholder="Поиск: «бой», «чат», «инвентарь», «Alt+P», «1..4»">';
      html +=              hkHelp;
      html += '          </div>';
      html += '        </details>';
      html += '      </div>';
html += '      <div class="Step"><div class="Name">Сортировка инвентаря</div><div class="Buttons"><select size="1" id="invType" onchange="Aqua.users.edit.redact(\'inv\',this.value);"><option value="0">-Не выбрано-</option><option value="0">По id предмета</option><option value="1">Сначала старые</option><option value="2">Сначала новые</option><option value="3">По категории</option><option value="4">По количеству</option></select></div></div>';
      html += '      <div class="Step"><div class="Name">Цвет в чате</div><div class="Buttons"><div class="Color" onclick="Aqua.users.edit.redact(\'color\',1)" style="background:#d1d1d1"></div><div class="Color" onclick="Aqua.users.edit.redact(\'color\',2)" style="background:#923838"></div><div class="Color" onclick="Aqua.users.edit.redact(\'color\',3)" style="background:#257b34"></div><div class="Color" onclick="Aqua.users.edit.redact(\'color\',4)" style="background:#252b7b"></div><div class="Color" onclick="Aqua.users.edit.redact(\'color\',5)" style="background:#891f85"></div><div class="Color" onclick="Aqua.users.edit.redact(\'color\',6)" style="background:#896520"></div></div></div>';
      html += '      <div class="Step Perk"><div class="Name">Умения</div><div class="Buttons">'+(response['utility']||'')+'</div></div>';
      html += '    </div>';

      /* TAB 3 */
      html += '    <div class="BlockContent" data-id="3">';
      html += '      <div class="Step"><div class="Name">Смена пароля</div><div class="Buttons"><input type="password" id="oldPass" placeholder="Старый пароль"><input type="password" id="newPass" placeholder="Новый пароль"><input type="password" id="dblNewPass" placeholder="Новый пароль"><div onclick="Aqua.users.edit.redact(\'pass\')">Сменить</div></div></div>';
      html += '    </div>';

      /* TAB 4 */
      html += '    <div class="BlockContent" data-id="4">';
      html += '      <div class="Step"><div class="Name">Возврат покемонов</div><div class="Buttons"><div onclick="return_del_pok();closeLittleModal();">Открыть</div></div></div>';
      html += '      <div class="Step"><div class="Name">Язык атак</div><div class="Buttons"><div class="attack-lang-switcher" data-lang="'+atkLangSwitch+'">'+atkLangBtn+'</div><span style="margin-left:10px;">Текущий: <b>'+atkLang+'</b></span></div></div>';
      html += '      <div class="Step"><div class="Name">Тема сайта</div><div class="Buttons"><div class="theme-switcher-btn" onclick="toggleThemeFromModal(this)"><i class="far fa-moon"></i> <span class="theme-switcher-text">Тёмная тема</span></div></div></div>';
      html += '      <div class="Step"><div class="Name">Версия игры <i class="fas fa-info-circle versionGame" onclick="openModal(\'versionGame\');closeLittleModal();"></i></div><div class="Buttons">ver '+(response['version']||'')+'</div></div>';
      html += '    </div>';

      html += '  </div></div>'; // Scroll + Pane
      html += '</div>'; // Body

      $m.html(html).appendTo('body');

      /* Hotkeys: фильтр по поиску (в справке) */
      try{
        $m.off('input.hk').on('input.hk', '.hk-search', function(){
          var q = (this.value||'').toLowerCase().trim();
          var rows = $m.find('.hk-row');
          if(!q){
            rows.show();
            $m.find('.hk-group').show();
            return;
          }
          rows.each(function(){
            var t = (this.getAttribute('data-hk') || '').toLowerCase();
            this.style.display = (t.indexOf(q) !== -1) ? '' : 'none';
          });
          $m.find('.hk-group').each(function(){
            var any = $(this).find('.hk-row:visible').length > 0;
            this.style.display = any ? '' : 'none';
          });
        });
      }catch(e){}
      $('body').addClass('modal-open--settings');

      /* тултипы */
      if (window.Tipped && Tipped.create) {
        Tipped.create('.referalCode', 'Поделитесь этим кодом с другом когда приглашаете его в игру...');
        Tipped.create('.promoCode', 'Используйте промокоды в чате: %code...');
        Tipped.create('.audioGame', 'При отключении звуков перезагрузите страницу.');
        Tipped.create('.missionGame', 'В 00:00 выдаются новые задания.');
        var hkTip = 'Горячие клавиши.';
        try{ if (window.GameHotkeys && GameHotkeys.getTooltipText) hkTip = GameHotkeys.getTooltipText(); }catch(e){}
        Tipped.create('.HotClick', hkTip);
        Tipped.create('.avatarsGame', 'PNG 100x100. Может обновляться не сразу.');
        Tipped.create('.referalListTool', 'Список ваших приглашённых игроков.');
      }

      /* табы */
      $m.on('click','.catSet', function(){
        var id = $(this).data('tab');
        $m.find('.catSet').removeClass('Active'); $(this).addClass('Active');
        $m.find('.BlockContent').removeClass('Active');
        $m.find('.BlockContent[data-id="'+id+'"]').addClass('Active');
      });

/* переключатель хоткеев */
$m.on('change', '.hk-toggle', function(){
  var on = $(this).is(':checked');
  try{ window.HotClick = on ? 1 : 0; }catch(e){}
  try{
    $m.find('.hk-badge').text(on ? 'вкл' : 'выкл');
    $m.find('.hk-switch-text').text(on ? 'Включены' : 'Выключены');
  }catch(e){}
  try{
    if (window.Aqua && Aqua.users && Aqua.users.edit && Aqua.users.edit.redact) {
      Aqua.users.edit.redact('hotclick', on ? 1 : 0);
    }
  }catch(e){}
});

/* поиск по хоткеям */
$m.on('input', '.hk-search', function(){
  var q = (($(this).val() || '') + '').toLowerCase().trim();
  var $rows = $m.find('.hk-row');
  var visible = 0;

  $rows.each(function(){
    var t = (($(this).attr('data-hk') || '') + '').toLowerCase();
    var ok = (!q || t.indexOf(q) !== -1);
    $(this).toggle(ok);
    if(ok) visible++;
  });

  $m.find('.hk-group').each(function(){
    // Если включён поиск — скрываем пустые группы. Без поиска — показываем всё.
    if(!q){ $(this).show(); return; }
    var has = $(this).find('.hk-row:visible').length > 0;
    $(this).toggle(has);
  });

  var $empty = $m.find('.hk-empty');
  if(q && visible === 0){
    if(!$empty.length) $m.find('.hk-help').append('<div class="hk-empty">Ничего не найдено.</div>');
  } else {
    $empty.remove();
  }
});




      /* переключатель языка атак */
      $(document).off('click.settings-lang','.attack-lang-switcher').on('click.settings-lang','.attack-lang-switcher', function(){
        var newLang = $(this).data('lang'); // rus|eng
        if (window.Aqua && Aqua.users && Aqua.users.edit && Aqua.users.edit.redact) {
          Aqua.users.edit.redact('attackLang', newLang);
        }
        $(this).data('lang', newLang === 'eng' ? 'rus' : 'eng')
               .text(newLang === 'eng' ? 'Русский' : 'English');
        $(this).next('span').html('Текущий: <b>'+(newLang==='eng'?'English':'Русский')+'</b>');
      });

      settingsPlaceModal($m);
      $(window).off('resize.settings scroll.settings').on('resize.settings scroll.settings', function(){ settingsPlaceModal($m); });
    }
  });
}

/* позиционирование */
function settingsPlaceModal($m){
  var vw = window.innerWidth || document.documentElement.clientWidth;
  if (vw <= 760){
    $m.css({
      left: '8px', right:'8px',
      top: 'calc(env(safe-area-inset-top,0px) + 8px)',
      transform:'none', width:'auto', maxWidth:'none',
      maxHeight: 'calc(100vh - 16px - env(safe-area-inset-top,0px) - env(safe-area-inset-bottom,0px))',
      zIndex: 99999
    });
  }else{
    $m.css({
      left:'50%', right:'auto', top:'48px', transform:'translateX(-50%)',
      width:'760px', maxWidth:'94vw', maxHeight:'calc(100vh - 64px)',
      zIndex: 520
    });
  }
}

/* закрытие */
function closeLittleModal(){
  $('.LittleModal.settings').remove();
  $('body').removeClass('modal-open--settings');
}

/* fallback переключатель темы */
if (typeof window.toggleThemeFromModal !== 'function'){
  window.toggleThemeFromModal = function(el){
    var root = document.documentElement;
    var dark = root.getAttribute('data-theme') === 'dark';
    root.setAttribute('data-theme', dark ? 'light' : 'dark');
    var span = el.querySelector('.theme-switcher-text');
    if (span) span.textContent = dark ? 'Тёмная тема' : 'Светлая тема';
  };
}

function sendItem(event) {
    event.preventDefault();
    const username = document.getElementById('username').value.trim();
    const itemID = document.getElementById('itemID').value;
    const quantity = document.getElementById('quantity').value;

    const postArgument = `item|${username}|${itemID}|${quantity}`;
    const formData = new FormData();
    formData.append('postArgument', postArgument);

    fetch('do/npc/777.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            const message = document.getElementById('responseMessage');
            message.textContent = data.text || 'Ошибка';
            if (data.error) {
                message.style.color = 'red';
            } else {
                message.style.color = 'green';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}
function clearNotifications() {
    $.ajax({
        url: "/do/notifications",
        type: "POST",
        data: { type: "clear_notifications" },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    $('.DivNotify').remove(); // Удаляем все уведомления из DOM
                    $('#countNotif').html("0");
                    $('.TopMenu .RightMenu .Buttons .ntUs').css('background', '#f2f2f2');
                    alert(result.success);
                } else {
                    alert("Ошибка: " + (result.error || "Неизвестная ошибка."));
                }
            } catch (e) {
                alert("Ошибка при обработке ответа сервера.");
            }
        },
        error: function() {
            alert("Ошибка при очистке уведомлений.");
        }
    });
}


// Универсальный ajax-обработчик для всех форм с классом .ajax-form
$(document).ready(function () {
  // Делегируем обработку submit для динамически добавляемых форм!
  $(document).on('submit', '.ajax-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var form_data = $form.serialize();
    var url = $form.data('url') || $form.attr('action') || '/recharge.php';
    var responseSelector = $form.data('response') || '#rechargeResponse';

    $.ajax({
      type: "POST",
      url: url,
      data: form_data,
      xhrFields: { withCredentials: true }, // обязательно для передачи сессии!
      success: function(txt) {
        // Если ответ JSON — разбираем и показываем ошибку или ссылку
        try {
          var data = typeof txt === "string" ? JSON.parse(txt) : txt;
          if (data.ok && data.url) {
            // Если успешно — закрываем модальное окно и открываем новое с оплатой
            closeRechargeModal();
            openPaymentModal(data.url);
          } else if (data.error) {
            if (typeof data.error === 'object' && data.error.text) {
              $(responseSelector).html(`<div style="color:red">${data.error.text}</div>`);
            } else {
              $(responseSelector).html(`<div style="color:red">${data.error}</div>`);
            }
          } else {
            $(responseSelector).html(`<div style="color:red">Неизвестный ответ сервера</div>`);
          }
        } catch (e) {
          $(responseSelector).html(txt); // fallback для не-JSON
        }
      },
      error: function(xhr, status, error) {
        $(responseSelector).html(`<div style="color:red">Ошибка: ${error}</div>`);
      }
    });
  });

  // Открытие модального окна пополнения баланса (делегировано, чтобы работало и для динамических кнопок)
  $(document).on('click', '.recharge-button, #openRechargeModal', function (e) {
    e.preventDefault();
    openRechargeModal();
  });
});

// ===== Настройки =====
var RECHARGE_CURRENCY = '₽';
var RECHARGE_MIN      = 10;
var RECHARGE_STEP     = 10;
var RECHARGE_POPULAR  = [100, 250, 500, 1000, 2000];
var MORE_METHODS_FROM = 500;

// Фоны (лучше локальные пути/URL)
var POKEBG_URLS = [
 'https://i.pinimg.com/736x/0d/80/4c/0d804c73f06bec2df43f93be909772bd.jpg', 
 'https://i.pinimg.com/1200x/cd/74/cf/cd74cf6e2da45dd8544fe7c025105fbb.jpg',
 'https://i.pinimg.com/736x/90/73/67/9073678e6769493be0da9c8a14237925.jpg',
 'https://i.pinimg.com/736x/9d/6b/51/9d6b51f675b59e20a9b2a33635bbc49f.jpg', 
 'https://i.pinimg.com/736x/c2/ab/24/c2ab24f431d1d5482e84f0e97c364825.jpg'
];

// ===== Модалка пополнения =====
function openRechargeModal() {
  if ($('#modalRecharge').length) return;

  var bg = POKEBG_URLS[Math.floor(Math.random()*POKEBG_URLS.length)] || '';
  var chips = RECHARGE_POPULAR.map(function(v){
    return '<button type="button" class="chip" data-amount="'+v+'">'+
           v.toString().replace(/\B(?=(\d{3})+(?!\d))/g,' ')+'</button>';
  }).join('');

  var html =
    '<div class="modal-bg" id="modalRechargeBg"></div>' +
    '<div class="modal-window compact" id="modalRecharge" role="dialog" aria-modal="true" aria-labelledby="modalRechargeTitle" tabindex="-1">' +
      '<style>' +
        '#modalRechargeBg{position:fixed;inset:0;background:rgba(8,10,14,.72);backdrop-filter:blur(3px);z-index:9998}' +
        '#modalRecharge{position:fixed;z-index:9999;left:50%;top:50%;transform:translate(-50%,-50%);' +
          'width:min(430px,calc(100% - 20px));border-radius:16px;overflow:hidden;color:#eef5ff;' +
          'background:#0e131a;box-shadow:0 22px 60px rgba(0,0,0,.55)}' +
        '#modalRecharge .bg{position:absolute;inset:0;background:url('+bg+') center/cover no-repeat;opacity:.32;filter:saturate(1.05)}' +
        '#modalRecharge .veil{position:absolute;inset:0;background:linear-gradient(180deg, rgba(14,19,26,.45), rgba(14,19,26,.92))}' +
        '#modalRecharge .content{position:relative;padding:12px}' +
        '#modalRecharge .hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}' +
        '#modalRecharge .ttl{font-weight:800;font-size:16px}' +
        '#modalRecharge .badge{font-size:11px;padding:4px 8px;border-radius:999px;background:rgba(255,255,255,.1);' +
          'border:1px solid rgba(255,255,255,.15);color:#d6e4ff;margin-left:6px}' +
        '#modalRecharge .x{background:transparent;border:0;color:#c2cee0;font-size:20px;line-height:1;cursor:pointer;border-radius:10px;padding:4px}' +
        '#modalRecharge .x:hover{background:rgba(255,255,255,.12);color:#fff}' +
        '#modalRecharge .muted{color:#9fb3c8;font-size:12px;margin:6px 0}' +
        '#modalRecharge .chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}' +
        '#modalRecharge .chip{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.08);color:#eef5ff;padding:6px 10px;border-radius:999px;font-weight:700;cursor:pointer}' +
        '#modalRecharge .chip.active{background:#2a7df6;border-color:#2a7df6;box-shadow:0 0 0 3px rgba(42,125,246,.18)}' +
        '#modalRecharge .row{display:flex;gap:6px;align-items:center}' +
        '#modalRecharge .step{width:38px;height:38px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.08);color:#eef5ff;font-size:18px;cursor:pointer}' +
        '#modalRecharge .inp{flex:1;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.28);color:#eef5ff;font-size:15px;outline:none}' +
        '#modalRecharge .inp:focus{border-color:#2a7df6;box-shadow:0 0 0 3px rgba(42,125,246,.2)}' +
        '#modalRecharge .hint{font-size:12px;min-height:14px;margin-top:4px;color:#8fa4bb}' +
        '#modalRecharge .hint.error{color:#ff8e8e}' +
        '#modalRecharge .promo{margin-top:8px;border-radius:14px;padding:10px 12px;background:linear-gradient(90deg, rgba(42,125,246,.25), rgba(42,125,246,.1));' +
          'border:1px solid rgba(42,125,246,.35);display:flex;gap:10px;align-items:flex-start}' +
        '#modalRecharge .promo .emoji{font-size:18px;line-height:1.1}' +
        '#modalRecharge .promo .txt{font-size:13px;color:#e9f2ff}' +
        '#modalRecharge .promo .txt .dim{color:#c7d6f2}' +
        '#modalRecharge .promo.ok{background:linear-gradient(90deg, rgba(52,199,89,.25), rgba(52,199,89,.12));border-color:rgba(52,199,89,.45)}' +
        '#modalRecharge .sum{display:flex;justify-content:space-between;gap:8px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:10px 12px;margin:8px 0}' +
        '#modalRecharge .btn{width:100%;padding:11px 12px;border:0;border-radius:12px;font-weight:800;cursor:pointer;background:#2a7df6;color:#fff;box-shadow:0 12px 28px rgba(42,125,246,.22)}' +
        '#modalRecharge .btn[disabled]{opacity:.6;cursor:not-allowed;box-shadow:none}' +
        '@media(max-width:480px){#modalRecharge{top:auto;bottom:0;transform:translate(-50%,0);width:100%;border-bottom-left-radius:0;border-bottom-right-radius:0}}' +
      '</style>' +

      '<div class="bg"></div><div class="veil"></div>' +

      '<div class="content">' +
        '<div class="hdr">' +
          '<div class="ttl" id="modalRechargeTitle">Пополнение баланса <span class="badge">Надёжная оплата</span></div>' +
          '<button type="button" class="x" onclick="closeRechargeModal()" aria-label="Закрыть">×</button>' +
        '</div>' +

        '<div class="muted">Быстрый выбор или своя сумма (мин. '+RECHARGE_MIN+', шаг '+RECHARGE_STEP+').</div>' +
        '<div class="chips" id="rechargeChips">'+chips+'</div>' +

        // Твой обработчик .ajax-form
        '<form class="ajax-form" id="rechargeForm" method="post" autocomplete="off" data-url="/recharge.php" data-response="#rechargeResponse">' +
          '<div class="row">' +
            '<button type="button" class="step" id="stepMinus">−</button>' +
            '<input type="number" class="inp" id="rechargeAmount" name="amount" min="'+RECHARGE_MIN+'" step="'+RECHARGE_STEP+'" placeholder="Например, 500" required>' +
            '<button type="button" class="step" id="stepPlus">+</button>' +
          '</div>' +
          '<div id="rechargeHint" class="hint"></div>' +

          // Информационный баннер (без выбора методов)
          '<div class="promo" id="rechargePromo">' +
            '<div class="emoji">💳</div>' +
            '<div class="txt">' +
              '<strong>Больше способов оплаты</strong> доступны при сумме от <strong>'+MORE_METHODS_FROM+' '+RECHARGE_CURRENCY+'</strong>.' +
              '<div class="dim">Карты, онлайн-банк, СБП, Steam и др.</div>' +
            '</div>' +
          '</div>' +

          '<div class="sum"><div>Итого</div><div><strong><span id="rechargeTotal">0</span> '+RECHARGE_CURRENCY+'</strong></div></div>' +
          '<div class="hint">Комиссия провайдера учитывается на следующем шаге.</div>' +
          '<button type="submit" class="btn" id="rechargeSubmit" disabled>Оплатить</button>' +
          '<div id="rechargeResponse" class="hint" style="margin-top:6px"></div>' +
        '</form>' +
      '</div>' +
    '</div>';

  $('body').append(html);

  // Закрытия
  $('#modalRechargeBg').on('click', closeRechargeModal);
  $(document).on('keydown.recharge', function(e){ if (e.key === 'Escape') closeRechargeModal(); });

  // ===== UI-логика =====
  var $form   = $('#rechargeForm');
  var $amount = $('#rechargeAmount');
  var $hint   = $('#rechargeHint');
  var $total  = $('#rechargeTotal');
  var $promo  = $('#rechargePromo');
  var $submit = $('#rechargeSubmit');

  // восстановим прошлую сумму
  var last = parseInt(localStorage.getItem('recharge_last')||'',10);
  if (Number.isFinite(last)) $amount.val(last);

  // быстрые суммы
  $('#rechargeChips').on('click', '.chip', function(){
    $amount.val($(this).data('amount'));
    validate();
    $amount.trigger('focus');
  });

  // степперы
  $('#stepMinus').on('click', function(){
    var v = Math.max(RECHARGE_MIN, (parseInt($amount.val(),10)||RECHARGE_MIN) - RECHARGE_STEP);
    $amount.val(v); validate();
  });
  $('#stepPlus').on('click', function(){
    var v = Math.max(RECHARGE_MIN, (parseInt($amount.val(),10)||RECHARGE_MIN) + RECHARGE_STEP);
    $amount.val(v); validate();
  });

  function validate() {
    var v = parseInt($amount.val(), 10);
    var err = '';
    if (!Number.isFinite(v)) err = 'Укажите сумму.';
    else if (v < RECHARGE_MIN) err = 'Минимальная сумма — '+RECHARGE_MIN+'.';
    else if (v % RECHARGE_STEP !== 0) err = 'Сумма должна быть кратна '+RECHARGE_STEP+'.';

    $hint.toggleClass('error', !!err).text(err || '');
    $submit.prop('disabled', !!err);
    $total.text(err ? '0' : v.toString().replace(/\B(?=(\d{3})+(?!\d))/g,' '));

    // Подсветка чипа и промо-баннера
    $('#rechargeChips .chip').each(function(){
      $(this).toggleClass('active', parseInt($(this).data('amount'),10) === v);
    });
    $promo.toggleClass('ok', Number.isFinite(v) && v >= MORE_METHODS_FROM);

    if (!err) localStorage.setItem('recharge_last', String(v));
  }

  $amount.on('input', validate);
  setTimeout(function(){ $amount.trigger('focus'); validate(); }, 30);

  // не перехватываем submit — остаётся твой глобальный .ajax-form
  $form.on('submit', function(){
    $submit.prop('disabled', true).text('Создаём платёж…');
    // Если бэкенд вернёт ошибку в #rechargeResponse (например, «слишком часто»),
    // вернём кнопку в активное состояние
    setTimeout(function(){
      if ($('#rechargeResponse').text().trim().length) {
        $submit.prop('disabled', false).text('Оплатить');
      }
    }, 1200);
  });
}

function closeRechargeModal() {
  $(document).off('keydown.recharge');
  $('#modalRecharge, #modalRechargeBg').remove();
}

// ===== Модалка со ссылкой на оплату (вызывай из ответа бэкенда) =====
function openPaymentModal(url) {
  if ($('#modalPayment').length) return;

  var html =
    '<div class="modal-bg" id="modalPaymentBg"></div>' +
    '<div class="modal-window compact" id="modalPayment" role="dialog" aria-modal="true" aria-labelledby="modalPaymentTitle" tabindex="-1">' +
      '<style>' +
        '#modalPaymentBg{position:fixed;inset:0;background:rgba(8,10,14,.72);backdrop-filter:blur(3px);z-index:9998}' +
        '#modalPayment{position:fixed;z-index:9999;left:50%;top:50%;transform:translate(-50%,-50%);' +
          'width:min(400px,calc(100% - 20px));border-radius:16px;overflow:hidden;color:#eef5ff;background:#0e131a}' +
        '#modalPayment .hdr{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.1)}' +
        '#modalPayment .ttl{font-weight:800;font-size:16px}' +
        '#modalPayment .x{background:transparent;border:0;color:#c2cee0;font-size:20px;line-height:1;cursor:pointer;border-radius:10px;padding:4px}' +
        '#modalPayment .x:hover{background:rgba(255,255,255,.12);color:#fff}' +
        '#modalPayment .body{padding:12px}' +
        '#modalPayment .muted{color:#9fb3c8;font-size:12px;margin-bottom:8px}' +
        '#modalPayment .btns{display:flex;gap:8px;margin:8px 0}' +
        '#modalPayment .btn{flex:1;padding:9px 10px;border-radius:10px;border:1px solid rgba(255,255,255,.14);cursor:pointer;font-weight:800}' +
        '#modalPayment .btn-primary{background:#2a7df6;border-color:#2a7df6;color:#fff}' +
        '#modalPayment .btn-secondary{background:rgba(255,255,255,.08);color:#eef5ff}' +
        '#modalPayment .link{word-break:break-all;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);padding:10px 12px;border-radius:10px}' +
        '@media(max-width:480px){#modalPayment{top:auto;bottom:0;transform:translate(-50%,0);width:100%;border-bottom-left-radius:0;border-bottom-right-radius:0}}' +
      '</style>' +
      '<div class="hdr"><div class="ttl" id="modalPaymentTitle">Оплата</div><button type="button" class="x" onclick="closePaymentModal()" aria-label="Закрыть">×</button></div>' +
      '<div class="body">' +
        '<div class="muted">Ссылка откроется в новой вкладке. Можно скопировать вручную.</div>' +
        '<div class="btns">' +
          '<button type="button" class="btn btn-primary" id="payOpen">Перейти</button>' +
          '<button type="button" class="btn btn-secondary" id="payCopy">Скопировать</button>' +
        '</div>' +
        '<div class="link">'+ url +'</div>' +
      '</div>' +
    '</div>';

  $('body').append(html);

  $('#modalPaymentBg').on('click', closePaymentModal);
  $(document).on('keydown.payment', function(e){ if (e.key === 'Escape') closePaymentModal(); });

  $('#payOpen').on('click', function(){ window.open(url, '_blank', 'noopener'); });
  $('#payCopy').on('click', function(){
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function(){ $('#payCopy').text('Скопировано'); });
    } else {
      var $tmp = $('<input>').val(url).appendTo('body').select();
      try { document.execCommand('copy'); $('#payCopy').text('Скопировано'); } catch(_) {}
      $tmp.remove();
    }
  });
}

function closePaymentModal() {
  $(document).off('keydown.payment');
  $('#modalPayment, #modalPaymentBg').remove();
}

function switchCategory(category) {
    // Убираем класс "active" у всех категорий
    document.querySelectorAll('.Category').forEach(cat => cat.classList.remove('active'));
    document.querySelectorAll('.CategoryContent').forEach(content => content.classList.remove('active'));

    // Добавляем класс "active" только к нужной категории
    document.querySelector(`.Category[data-category="${category}"]`).classList.add('active');
    document.getElementById(category).classList.add('active');
}
$(document).on('click', '.el_wild', function() {
    $(this).toggleClass('active'); // Переключает состояние кнопки (активация/деактивация)
});
// Глобальная переменная
let _element_smile_list = null;

// ==== Доступные паки: pack1, pack2 всегда, остальные — только купившим ====
function getOwnedEmojiPacks() {
    // pack1 и pack2 доступны всем
    let owned = ["pack1", "pack2"];
    // window.userOwnedEmojiPacks должен быть массивом с купленными (пример: ["pack4"])
    if (window.userOwnedEmojiPacks && Array.isArray(window.userOwnedEmojiPacks)) {
        window.userOwnedEmojiPacks.forEach(pack => {
            if (!owned.includes(pack)) owned.push(pack);
        });
    }
    return owned;
}

this._viewSmile = async function (hide) {
    // Все паки и их цены
    let packs = [
        {name: "pack1", price: 0},
        {name: "pack2", price: 0},
        {name: "pack3", price: 50},
        {name: "pack4", price: 50},
        {name: "pack5", price: 50},
        {name: "pack6", price: 50}
    ];

    // Получить купленные паки
    let ownedPacks = ["pack1", "pack2"];
    try {
        let packsResp = await fetch('/get_emojis.php?action=get_packs');
        let packsArr = await packsResp.json();
        ownedPacks = Array.isArray(packsArr) ? packsArr : ["pack1", "pack2"];
        window.userOwnedEmojiPacks = ownedPacks;
    } catch (e) {}

    // Если окно уже есть — показать/скрыть
    if (_element_smile_list && _element_smile_list.length) {
        if (_element_smile_list.is(':visible')) {
            _element_smile_list.css('display', 'none');
        } else if (!hide) {
            _element_smile_list.css('display', 'flex');
        }
        return;
    }
    if (hide) return;
    if (_element_smile_list) {
        _element_smile_list.remove();
        _element_smile_list = null;
    }
    _element_smile_list = $('<div class="window smileList"></div>').appendTo('body');

    // --- Вкладки ---
    let tabs = `
        <div class="emoji-tabs-wrapper">
            <button class="emoji-scroll-btn left">&#10094;</button>
            <div class="emoji-tabs-container">
                ${packs.map((pack, i) => `
                    <button class="emoji-tab ${i === 0 ? 'active' : ''}" data-tab="${pack.name}">
                        <div class="emoji-tab-icon" id="tab-ico-${pack.name}"></div>
                        ${ownedPacks.includes(pack.name) ? '' : '<div class="emoji-tab-lock"><i class="fas fa-lock"></i></div>'}
                    </button>
                `).join('')}
            </div>
            <button class="emoji-scroll-btn right">&#10095;</button>
        </div>
    `;

    let content = '<div class="emoji-content"></div>';
    _element_smile_list.append(tabs + content);

    // --- Загружаем смайлы для всех паков ---
    let emojiLists = {};
    for (let pack of packs) {
        let emojis = [];
        try {
            let resp = await fetch(`/get_emojis.php?pack=${pack.name}`);
            let json = await resp.json();
            if (Array.isArray(json)) emojis = json;
        } catch (e) {}
        emojiLists[pack.name] = emojis;
        // Вставляем иконку в таб
        if (emojis.length) {
            $(`#tab-ico-${pack.name}`).html(`<img src="/img/em/${pack.name}/${emojis[0]}" width="40" height="40">`);
        } else {
            $(`#tab-ico-${pack.name}`).html(`<span style="display:inline-block;width:40px;height:40px;background:#eee;border-radius:8px;"></span>`);
        }
    }

    // --- Заполнение контента ---
    let showContent = function(tabName) {
        let packObj = packs.find(p => p.name === tabName);
        let emojis = emojiLists[tabName] || [];
        let isOwned = ownedPacks.includes(tabName);
        let html = `<div class="emoji-list ${tabName}" style="display:flex;flex-wrap:wrap;">`;
        if (emojis.length) {
            html += emojis.map(emoji => {
                let emojiName = emoji.replace(/\.(png|gif)$/,'');
                // Не куплен — просто визуально отключаем, но показываем
                return `<div class="emoji" title="${emojiName}" data-emoji=":${emojiName}:" ${isOwned ? "" : "style='pointer-events:none;opacity:0.5;'"} >
                    <img src="/img/em/${tabName}/${emoji}" width="40" height="40">
                </div>`;
            }).join('');
        } else {
            html += `<div style="margin:20px auto;color:#888;text-align:center;">Нет смайликов в этом наборе</div>`;
        }

        // Если не куплен, показываем кнопку купить
        if (!isOwned && packObj.price && packObj.price > 0) {
            html += `<div class="emoji-pack-buy-block" style="width:100%;text-align:center;margin-top:20px;">
                <button class="emoji-buy-btn" data-pack="${tabName}" data-price="${packObj.price}">
                    Купить за ${packObj.price} <img src="/img/world/items/little/25.png" width="20" height="20">
                </button>
            </div>`;
        }
        html += '</div>';
        _element_smile_list.find('.emoji-content').html(html);
    };

    // Инициализация: показываем первый пак
    showContent(packs[0].name);

    // --- Переключение вкладок ---
    _element_smile_list.off('click.tab').on('click.tab', '.emoji-tab', function () {
        _element_smile_list.find('.emoji-tab').removeClass('active');
        $(this).addClass('active');
        let tab = $(this).data('tab');
        showContent(tab);
    });

    // --- Кнопка купить ---
    _element_smile_list.off('click.emojiBuy').on('click.emojiBuy', '.emoji-buy-btn', function () {
        let pack = $(this).data('pack');
        let price = $(this).data('price');
        let btn = $(this);
        btn.prop('disabled', true).text('Покупка...');
        fetch(`/get_emojis.php?action=buy&pack=${pack}`, {method: 'GET'})
            .then(async resp => {
                let data = await resp.json();
                if (data.error === 0) {
                    // Покупка успешна — показываем уведомление как в магазине!
                    if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
                        Game.notifications.main(
                            'Набор удачно куплен. Для корректного отображения новых смайлов, пожалуйста, обновите страницу.',
                            'success'
                        );
                    } else {
                        alert('Набор удачно куплен. Для корректного отображения новых смайлов, пожалуйста, обновите страницу.');
                    }
                    // Обновить паки и вкладки
                    let packsResp = await fetch('/get_emojis.php?action=get_packs');
                    let packsArr = await packsResp.json();
                    window.userOwnedEmojiPacks = packsArr.filter(p => p !== "pack1" && p !== "pack2");
                    ownedPacks = Array.isArray(packsArr) ? packsArr : ["pack1", "pack2"];
                    showContent(pack); // Перерисовать текущий таб!
                    for (let packObj of packs) {
                        let isOwnedUpdate = ownedPacks.includes(packObj.name);
                        $(`#tab-ico-${packObj.name}`).parent().find('.emoji-tab-lock').toggle(!isOwnedUpdate);
                    }
                } else {
                    btn.prop('disabled', false).html(`Купить за ${price} <img src="/img/world/items/little/25.png" width="20" height="20">`);
                    if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
                        Game.notifications.main(
                            data.text || "Ошибка покупки!",
                            'error'
                        );
                    } else {
                        alert(data.text || "Ошибка покупки!");
                    }
                }
            })
            .catch(() => {
                btn.prop('disabled', false).html(`Купить за ${price} <img src="/img/world/items/little/25.png" width="20" height="20">`);
                if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
                    Game.notifications.main(
                        "Ошибка соединения!",
                        'error'
                    );
                } else {
                    alert("Ошибка соединения!");
                }
            });
    });

    // --- Клик по смайлу (только если набор куплен) ---
    _element_smile_list.off('click.emojiPick').on('click.emojiPick', '.emoji', function () {
        let tab = _element_smile_list.find('.emoji-tab.active').data('tab');
        if (!ownedPacks.includes(tab)) return;
        let emojiName = $(this).attr('data-emoji');
        let $input = $('#chat_send_desktop:visible');
        if (!$input.length) $input = $('#chat_send:visible');
        if (!$input.length) $input = $('#chat_send_desktop');
        if (!$input.length) $input = $('#chat_send');
        if ($input.length) {
            smile($input[0], ' ' + emojiName + ' ');
            $input.focus();
        }
        if ($(window).width() < 900) {
            if (_element_smile_list) _element_smile_list.css('display', 'none');
        }
    });

    // --- Прокрутка вкладок ---
    _element_smile_list.off('click.emojiScrollLeft').on('click.emojiScrollLeft', '.emoji-scroll-btn.left', function () {
        _element_smile_list.find('.emoji-tabs-container').animate({ scrollLeft: '-=100px' }, 200);
    });
    _element_smile_list.off('click.emojiScrollRight').on('click.emojiScrollRight', '.emoji-scroll-btn.right', function () {
        _element_smile_list.find('.emoji-tabs-container').animate({ scrollLeft: '+=100px' }, 200);
    });
};
// Функция скрытия окна смайлов
function SmileListClose() {
    $(".window.smileList").hide();
}

// Функция загрузки всех смайлов из пака
async function loadEmojiPack(pack) {
    let allowedFormats = ['png', 'gif'];
    let response = await fetch(`/get_emojis.php?pack=${pack}`);
    let files = await response.json();
    return files.filter(file => allowedFormats.some(ext => file.endsWith('.' + ext)));
}
function setTrenerBlock(id, el) {
  $('.Tabs > div').removeClass('active');
  $(el).addClass('active');

  $('.Table').hide();
  $('#' + id).show();

  // Прячем или показываем .el_wild в зависимости от выбранной вкладки
  if (id !== 'trophyBlock') {
    $('.el_wild').addClass('hidden');
  } else {
    $('.el_wild').removeClass('hidden');
  }
}
function loadAutoBreeding() {
  fetch('/do/breed_helper.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ type: 'massBreed' })
  })
  .then(response => response.json())
  .then(data => {
    const container = document.getElementById('mass-breed-results');
    if (!data.results) return (container.innerHTML = 'Немає результатів.');

    container.innerHTML = data.results.map(r => `
      <div class="${r.error === 0 ? 'ok' : 'fail'}">[${r.text}]</div>
    `).join('');
  });
}
function el_wild(elem) {
    // Если вызов произошёл без передачи элемента, ищем первый элемент с классом el_wild
    if (!elem) elem = document.querySelector('.el_wild');
    setHunt(elem); // ваша логика для кнопки
}
function championApply(leaderId) {
    $.post('/champions_league.php', { type: 'champion_apply', leader_id: leaderId }, function(data){
        var resp = JSON.parse(data);
        alert(resp.html);
        Game.modals.diary('championsLeague'); // Обновить список
    });
}
function teleportToStadium(leaderId) {
    $.post('/champions_league.php', { type: 'teleport', leader_id: leaderId }, function(data){
        var resp = JSON.parse(data);
        if (resp.url) window.location.href = resp.url;
    });
}
function openGymLeaderPanel() {
    $.post('/do/gym_leader_panel.php', {type: 'gym_leader_panel'}, function(resp){
        // Показываете как модалку
        openModal(resp.html); // или ваш метод показа модалки
    }, 'json');
}
// Выдать значок тренеру
function gym_give_badge(trainer_id) {
    $.post('/do/gym_leader_panel.php', {type: 'gym_give_badge', trainer_id: trainer_id}, function(resp){
        // Обновить панель после действия
        if (typeof Game !== 'undefined' && Game.notifications && Game.notifications.main) {
            Game.notifications.main(resp.html, resp.error);
        } else {
            // fallback: обновить содержимое модалки напрямую
            $('.LittleModal').html(resp.html);
        }
    }, 'json');
}

// Засчитать проигрыш тренеру
function gym_loss(trainer_id) {
    $.post('/do/gym_leader_panel.php', {type: 'gym_loss', trainer_id: trainer_id}, function(resp){
        // Обновить панель после действия
        if (typeof Game !== 'undefined' && Game.notifications && Game.notifications.main) {
            Game.notifications.main(resp.html, resp.error);
        } else {
            $('.LittleModal').html(resp.html);
        }
    }, 'json');
}
function clearNotifications() {
    if (!confirm("Вы уверены, что хотите очистить все уведомления?")) return;
    $.post('/do/notifications.php', {type: 'clear_notifications'}, function(resp){
        try { resp = JSON.parse(resp); } catch(e){}
        if(resp.success){
            // После очистки — обновить список уведомлений
            Game.notifications.load();
        } else if(resp.error){
            alert(resp.error);
        }
    });
}
function actionClan(action) {
    // Получаем сумму из поля
    var money = parseInt(document.getElementById('clanMoneyInput').value, 10);
    if (isNaN(money) || money < 1000) {
        alert('Минимальная сумма для взноса 1000');
        return;
    }
    $.post('/do/clanAction.php', {
        object: action, // Например, 'addMoney'
        money: money    // Сумма взноса
    }, function(resp) {
        if (typeof resp === 'string') resp = JSON.parse(resp);
        if (resp.error === 0 || resp.error === 'success') {
            alert(resp.text);
            // ...обновить счет клана и интерфейс при необходимости
        } else {
            alert(resp.text);
        }
    });
}
// Открыть основную панель Секретаря
function openSekretarPanel() {
    $.post('/do/gym.php', {type: 'sekretar'}, function(resp){
        openModal('<div class="SekretarWrap">' + resp.html + '</div>');
        bindSekretarTabs();
    }, 'json');
}

// Привязка вкладок
function bindSekretarTabs() {
    $('.SekretarTabs .tab-btn').off('click').on('click', function(){
        let tab = $(this).data('tab');
        $('.SekretarTabs .tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.SekretarTabContent').removeClass('active');
        $('.' + tab + '-tab').addClass('active');
        // Загружаем контент, если надо
        if(tab === 'tournaments') loadTournamentPanel();
        if(tab === 'history') loadTournamentHistory();
        if(tab === 'winners') loadTournamentWinners();
    });
}

// Запись на турнир (внутри вкладки)
function loadTournamentPanel() {
    if($('.tournaments-tab').html().trim()) return; // Уже загружено
    $.post('/do/gym.php', {type: 'tournament'}, function(resp){
        $('.tournaments-tab').html(resp.html);
    }, 'json');
}
function applyTournament() {
    var tournamentId = $('#tournament_select').val();
    $.post('/do/gym.php', {type: 'tournament_apply', tournament: tournamentId}, function(resp){
        alert(resp.html);
        if(resp.error === "success") $('.tournaments-tab').html('Вы успешно записаны!');
    }, 'json');
}

// История турниров
function loadTournamentHistory() {
    if($('.history-tab').html().trim()) return;
    $.post('/do/gym.php', {type: 'tournament_history'}, function(resp){
        $('.history-tab').html(resp.html);
    }, 'json');
}

// Победители
function loadTournamentWinners() {
    if($('.winners-tab').html().trim()) return;
    $.post('/do/gym.php', {type: 'tournament_winners'}, function(resp){
        $('.winners-tab').html(resp.html);
    }, 'json');
}

// Вызови openSekretarPanel() чтобы открыть панель!
// Place this script once, on your main page or in a global JS file:

// tournament_admin_panel.js
// Все JS-функции для панели куратора турниров

// Открыть управление участниками турнира (модальное окно)
// Открыть управление участниками турнира (модальное окно)
function manageTournament(id) {
    $.post('/do/gym.php', {type: 'manage_tournament', id: id}, function(resp){
        // openModal — функция для показа модального окна, замените на свою если нужно
        if ($('.LittleModal').length) {
            $('.LittleModal').html(resp.html);
        } else {
            $('<div />', {
                "class": "LittleModal",
                html: resp.html
            }).appendTo('body');
        }
    }, 'json');
}

function setTournamentActive(id, value) {
    $.post('/do/gym.php', {type: 'set_active', id: id, active: value}, function(resp){
        if(resp.error !== 'success') alert(resp.html);
    }, 'json');
}

// Добавление нового турнира
$(document).on('submit', '#add-tournament-form', function(e){
    e.preventDefault();
    $.post('/do/gym.php', {
        type: 'add_tournament',
        name: this.name.value,
        start_time: this.start_time.value
    }, function(resp){
        alert(resp.html);
        if(resp.error === 'success') location.reload();
    }, 'json');
});

// Выставить место участнику
function setPlace(tu_id, val) {
    $.post('/do/gym.php', {type: 'set_place', tu_id: tu_id, place: val}, function(resp){
        if(resp.error) alert(resp.html);
    }, 'json');
}

// Изменить результат участника
function setResult(tu_id, val) {
    $.post('/do/gym.php', {type: 'set_result', tu_id: tu_id, result: val}, function(resp){
        if(resp.error) alert(resp.html);
    }, 'json');
}

// Выдать медаль участнику
function giveMedal(tu_id) {
    $.post('/do/gym.php', {type: 'give_medal', tu_id: tu_id}, function(resp){
        alert(resp.html);
    }, 'json');
}

// Показать панель управления турнирами (главная)
window.showTournamentAdminPanel = function() {
    $.post('/do/gym.php', {type: 'tournament_admin_panel'}, function(resp){
        $('.LittleModal').remove();
        $('<div />', {
            "class": "LittleModal",
            html: resp.html
        }).appendTo('body');
    }, 'json');
}

// Завершить турнир
function finishTournament(id) {
    if (!confirm('Вы уверены, что хотите завершить турнир?')) return;
    $.post('/do/gym.php', {type: 'finish_tournament', id: id}, function(resp){
        alert(resp.html);
        if (resp.error === 'success') location.reload();
    }, 'json');
}
// Универсальный скрипт для смены языка атак на сайте

/**
 * Сохраняет новый язык атак пользователя и автоматически обновляет все элементы,
 * где выводятся названия атак, на выбранный язык.
 * Вызывать эту функцию после смены языка в настройках или через быстрый переключатель.
 *
 * Требования:
 * - Сервер должен возвращать успешный статус (response.error === "success")
 * - Элементы с названиями атак должны иметь класс .atk-name и data-аттрибут data-atk-id
 * - Для динамической подгрузки названий атак нужен API-эндпоинт, например:
 *     GET /api/atk_name.php?id=123&lang=eng|rus
 */

function switchAttackLanguage(newLang, callback) {
    $.post('/do/edit', {type: 'editAttackLang'}, function(response){
        if(response.error === "success") {
            // Сохраняем в локальное хранилище для фронта, если нужно
            window.user_attack_lang = newLang;
            localStorage.setItem('attack_lang', newLang);

            // Обновляем названия атак на всех страницах
            updateAllAttackNames(newLang);

            // Кастомное уведомление
            if (typeof Game !== 'undefined' && Game.notifications && Game.notifications.main) {
                Game.notifications.main(
                    (newLang === "eng" ? "Attack language switched to English." : "Язык атак переключён на Русский."),
                    "success"
                );
            } else {
                alert(newLang === "eng" ? "Attack language switched to English." : "Язык атак переключён на Русский.");
            }

            if (typeof callback === "function") callback(response);
        } else {
            alert(response.html || "Ошибка при смене языка атак.");
        }
    }, 'json');
}

/**
 * Обновляет все элементы с классом .atk-name на выбранный язык.
 * Для этого отправляется AJAX-запрос для каждого аттрибута data-atk-id.
 */
Aqua = window.Aqua || {};
Aqua.users = Aqua.users || {};
Aqua.users.edit = Aqua.users.edit || {};

// Универсальный обработчик настроек пользователя
Aqua.users.edit.redact = function(type, set) {
    // 1. Смена языка атак — отдельная ветка
    if (type === 'attackLang') {
        $.post('/do/edit', {type: 'editAttackLang', set: set}, function(data){
            if (typeof data === "string") data = JSON.parse(data);
            if (data.error == 'success') {
                if (typeof Game !== "undefined" && Game.notifications && Game.notifications.main) {
                    Game.notifications.main(
                        (data.attack_lang === "eng" ? "Attack language switched to English." : "Язык атак переключён на Русский."),
                        "success"
                    );
                } else {
                    alert(data.attack_lang === "eng" ? "Attack language switched to English." : "Язык атак переключён на Русский.");
                }
                if (typeof updateAllAttackNames === "function") updateAllAttackNames(set);
                if (typeof settings === "function") settings();
            } else {
                if (typeof Game !== "undefined" && Game.notifications && Game.notifications.main) {
                    Game.notifications.main(data.html || "Ошибка при смене языка атак.", "error");
                } else {
                    alert(data.html || "Ошибка при смене языка атак.");
                }
            }
        }, 'json');
        return;
    }

    // 2. Обработка остальных настроек (audio, mission, hotclick, inv, color и т.д.)
    $.post('/do/edit', {type: type, set: set}, function(data){
        if (typeof data === "string") data = JSON.parse(data);
        if (data.error == 'success') {
            if (typeof Game !== "undefined" && Game.notifications && Game.notifications.main) {
                Game.notifications.main("Изменения сохранены", "success");
            } else {
                alert("Изменения сохранены");
            }
            if (typeof settings === "function") settings();
        } else {
            if (typeof Game !== "undefined" && Game.notifications && Game.notifications.main) {
                Game.notifications.main(data.html || "Ошибка", "error");
            } else {
                alert(data.html || "Ошибка");
            }
        }
    }, 'json');
};

// Обработчик для смены языка атак (только для attackLang)
$(document).off('click', '.attack-lang-switcher').on('click', '.attack-lang-switcher', function(){
    var newLang = $(this).data('lang'); // 'eng' или 'rus'
    Aqua.users.edit.redact('attackLang', newLang);
});

// Функция обновления названий атак на странице
function updateAllAttackNames(lang) {
    $('.atk-name[data-atk-id]').each(function(){
        var el = $(this);
        var atkId = el.data('atk-id');
        $.get('/api/atk_name.php', {id: atkId, lang: lang}, function(data){
            if(data && data.atk_name) {
                el.text(data.atk_name);
            }
        }, 'json');
    });
}

function lockBodyScroll() {
  // Сохраняем текущую позицию прокрутки
  const scrollY = window.scrollY || document.documentElement.scrollTop;
  document.body.style.position = 'fixed';
  document.body.style.top = `-${scrollY}px`;
  document.body.style.left = '0';
  document.body.style.right = '0';
  document.body.style.width = '100vw';
  document.body.style.overflowY = 'scroll';
  document.body.dataset.scrollY = scrollY; // для разблокировки
}

function unlockBodyScroll() {
  const scrollY = document.body.dataset.scrollY ? parseInt(document.body.dataset.scrollY, 10) : 0;
  document.body.style.position = '';
  document.body.style.top = '';
  document.body.style.left = '';
  document.body.style.right = '';
  document.body.style.width = '';
  document.body.style.overflowY = '';
  window.scrollTo(0, scrollY);
  delete document.body.dataset.scrollY;
}

// Показываем tooltip и блокируем фон
function showTooltip() {
  // ...твой код показа tooltip...
  if (window.innerWidth <= 600) {
    lockBodyScroll();
  }
}

// Скрываем tooltip и возвращаем скролл
function hideTooltip() {
  // ...твой код скрытия tooltip...
  if (window.innerWidth <= 600) {
    unlockBodyScroll();
  }
}
// Функция для добавления кнопки в любой контейнер
function addCloseButton(modalSelector) {
  const el = document.querySelector(modalSelector);
  if (el && !el.querySelector('.close-btn')) {
    const btn = document.createElement('button');
    btn.className = 'close-btn';
    btn.innerHTML = '&times;';
    btn.onclick = function() { hideTooltip(this); };
    el.prepend(btn);
  }
}

// Для всех окон:
['.LittleModal', '#mainTooltip', '.tooltip-block', '.mudol'].forEach(sel => addCloseButton(sel));
function showTooltipAtCenter(tooltipSelector = '.tooltip') {
  const tooltip = document.querySelector(tooltipSelector);
  if (!tooltip) return;
  tooltip.style.display = 'block';
  tooltip.style.visibility = 'hidden'; // Сначала скрываем, чтобы получить размеры

  // Сброс позиционирования
  tooltip.style.left = '';
  tooltip.style.top = '';
  tooltip.style.right = '';
  tooltip.style.bottom = '';
  tooltip.style.transform = '';

  // Получаем размеры окна и тултипа
  const ww = window.innerWidth;
  const wh = window.innerHeight;
  const tw = tooltip.offsetWidth;
  const th = tooltip.offsetHeight;

  // Координаты по центру
  let left = (ww - tw) / 2;
  let top = (wh - th) / 2;

  // Не даём выйти за края (с запасом 10px)
  left = Math.max(10, Math.min(left, ww - tw - 10));
  top = Math.max(10, Math.min(top, wh - th - 10));

  tooltip.style.left = left + 'px';
  tooltip.style.top = top + 'px';
  tooltip.style.right = 'auto';
  tooltip.style.bottom = 'auto';
  tooltip.style.transform = 'none';
  tooltip.style.visibility = 'visible';
}

// Вызови showTooltipAtCenter() при открытии tooltip на ПК (не на мобильных)
// Пример:
function openTooltipPC() {
  if (window.innerWidth > 600) {
    showTooltipAtCenter('#mainTooltip');
  }
  // ... показать тултип, добавить lockBodyScroll на мобиле, и т.д.
}
// Кнопка открытия/закрытия чата
// Кнопка открытия/закрытия чата
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
    $('.ChatBox.mobile').show();// <-- БЕЗ пробела!
    $('body').css('overflow', 'hidden');
}

// Закрыть чат-поповер
function closeChatPopover() {
    $('.ChatBox.mobile').hide();// <-- БЕЗ пробела!
    $('body').css('overflow', '');
}

// По умолчанию скрываем чат на старте страницы
$(function() {
    $('.ChatBox.mobile').hide(); // <-- БЕЗ пробела!
});

function getCurrentMessageBlock(channel) {
    if ($('.ChatBox.mobile:visible').length) {
        // Мобильный чат открыт
        return $('.ChatBox.mobile .Message-Block.Channel_' + channel);
    } else {
        // Десктопный/основной чат
        return $('.ChatBox:not(.mobile) .Message-Block.Channel_' + channel);
    }
}
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
// Пример: вызов после ajax/fetch:
fetch('/upload.php', { /* ... */ }).then(r=>r.json()).then(showNotify);
function reloadWorld(extraParams = {}) {
  // Собираем параметры запроса (можно добавить доп. параметры через extraParams)
  var params = Object.assign({
    updateUsers: true
  }, extraParams);

  // Визуальный индикатор загрузки (например, прелоадер)
  $('#divWorld').addClass('loading');

  $.ajax({
    url: '/do/updateLocation.php',
    type: 'POST',
    data: params,
    dataType: 'json',
    success: function(response) {
      // Снимаем индикатор загрузки
      $('#divWorld').removeClass('loading');

      // Доп. обработка ошибок
      if (response.error) {
        $('#divWorld').html('<div class="error-block">' + $('<div>').text(response.error).html() + '</div>');
        return;
      }

      if (response.status === 'battle') {
        $('#divWorld').html(renderBattleBlock(response));
      } else {
        $('#divWorld').html(renderLocationBlock(response));
      }

      // Фокусировка на divWorld для accessibility
      $('#divWorld').attr('tabindex', -1).focus();

      // Кастомный эвент для других скриптов (можно слушать: $(document).on('worldReload', fn) )
      $(document).trigger('worldReload', [response]);
    },
    error: function(xhr, status, error) {
      $('#divWorld').removeClass('loading');
      $('#divWorld').html('<div class="error-block">Ошибка загрузки локации. Попробуйте позже.</div>');
    },
    timeout: 1000 // 15 секунд на запрос
  });
}



function showMiniPanel(skin, hat, userId) {
  // Удаляем предыдущую панель, если была
  $('#miniPanel').remove();

  // Сборка HTML
  let panel = $(`
    <div id="miniPanel">
      <span class="close-btn" title="Закрыть">&times;</span>
      <div style="font-size: 20px; margin-bottom:14px;">Мини-панель аватара</div>
      <div>
        <div class="item" id="skinItem" style="cursor: ${skin ? "pointer" : "default"};">
          ${skin ? 'Скин: ' + skin.name + ' <span class="remove-skin-btn" title="Снять скин" style="color:#c00;cursor:pointer;">[снять]</span>'
                 : 'Скин не выбран'}
        </div>
        <div class="item" id="hatItem" style="cursor: ${hat ? "pointer" : "default"};">
          ${hat ? 'Шапка: ' + hat.name + ' <span class="remove-hat-btn" title="Снять шапку" style="color:#c00;cursor:pointer;">[снять]</span>'
                : 'Шапка не выбрана'}
        </div>
      </div>
    </div>
  `);

  // Добавляем панель в DOM
  $('body').append(panel);

  // Закрытие по крестику
  $('#miniPanel .close-btn').on('click', function() {
    $('#miniPanel').fadeOut(120, function(){ $(this).remove(); });
  });

  // Обработка "снять скин"
  if (skin) {
    $('#skinItem .remove-skin-btn').on('click', function(e) {
      e.stopPropagation();
      removeSkin(userId, function(resp) {
        $('#miniPanel').fadeOut(120, function(){ $(this).remove(); });
        if(resp && resp.text) showMessage(resp.text, resp.error ? 'error' : 'success');
        if(typeof updateAvatarPanel === 'function') updateAvatarPanel();
      });
    });
  }

  // Обработка "снять шапку"
  if (hat) {
    $('#hatItem .remove-hat-btn').on('click', function(e) {
      e.stopPropagation();
      removeHat(userId, function(resp) {
        $('#miniPanel').fadeOut(120, function(){ $(this).remove(); });
        if(resp && resp.text) showMessage(resp.text, resp.error ? 'error' : 'success');
        if(typeof updateAvatarPanel === 'function') updateAvatarPanel();
      });
    });
  }
}

// Пример вызова из обработчика нажатия на bigAva
$('#bigAva').on('click', function() {
  // Подставь актуальные данные о скине/шапке и userId из своего бекенда/response
  showMiniPanel(currentSkin, currentHat, currentUserId);
});

/**
 * Снять скин с персонажа через itemsAction
 * @param {number|string} userId - ID пользователя или логин
 * @param {function} callback - Функция, вызываемая после снятия (опционально)
 */
function removeSkin(userId, callback) {
  $.post('/do/itemsAction', {type: 'remove_skin', user: userId}, function(resp) {
    if (typeof callback === 'function') callback(resp);
  }, 'json')
  .fail(function(xhr, status, error) {
    if (typeof callback === 'function') callback({
      error: 1,
      text: 'Ошибка связи с сервером: ' + error
    });
  });
}

/**
 * Снять шапку с персонажа через itemsAction
 * @param {number|string} userId - ID пользователя или логин
 * @param {function} callback - Функция, вызываемая после снятия (опционально)
 */
function removeHat(userId, callback) {
  $.post('/do/itemsAction', {type: 'remove_hat', user: userId}, function(resp) {
    if (typeof callback === 'function') callback(resp);
  }, 'json')
  .fail(function(xhr, status, error) {
    if (typeof callback === 'function') callback({
      error: 1,
      text: 'Ошибка связи с сервером: ' + error
    });
  });
}

/**
 * Показать сообщение пользователю
 * @param {string} text
 * @param {string} type 'success' | 'error'
 */
function showMessage(text, type) {
  // Можно заменить на свой обработчик, например, красивый toast
  alert((type === 'error' ? 'Ошибка: ' : '') + text);
}
/**
 * Показать сообщение пользователю
 * @param {string} text
 * @param {string} type 'success' | 'error'
 */

document.addEventListener('keydown', function(e) {
    // Не срабатывает в input, textarea, contenteditable
    var tag = (document.activeElement && document.activeElement.tagName) ? document.activeElement.tagName.toUpperCase() : '';
    if (tag === 'INPUT' || tag === 'TEXTAREA' || document.activeElement.isContentEditable) return;

    if (typeof HotClick !== "undefined" && HotClick == 1) {
        // ESC
        if (e.key === "Escape" || e.keyCode === 27) {
            if ($('.DivNotification').html() !== '') {
                $('.DivNotification .noty').remove();
            } else {
                if (typeof closeModal === "function") closeModal();
            }
            e.preventDefault();
            return false;
        }

        // Русско-английская поддержка
        var ruToEn = {
            'Р':'P','И':'I','С':'C','Е':'T','Й':'Q','Н':'H','Ы':'S','М':'V','Ь':'M','В':'D','Б':'B','Х':'X','А':'F'
        };
        var key = (e.key || '').toUpperCase();
        if (ruToEn[key]) key = ruToEn[key];

        if (e.altKey) {
            // P - Покемоны
            if (key === 'P') { if (Game.modals.pokemons) Game.modals.pokemons(); e.preventDefault(); return false;}
            // I - Инвентарь
            if (key === 'I') { if (Game.modals.inventory) Game.modals.inventory('all'); e.preventDefault(); return false;}
            // C - Крафт
            if (key === 'C') { if (typeof openModal === "function") openModal('craft'); e.preventDefault(); return false;}
            // T - Тренеры
            if (key === 'T') { if (typeof openModal === "function") openModal('trainers'); e.preventDefault(); return false;}
            // Q - Квесты/Дневник
            if (key === 'Q') { if (Game.modals.diary) Game.modals.diary('quests'); e.preventDefault(); return false;}
            // H - Восстановление
            if (key === 'H') { if (typeof recover === "function") recover(); e.preventDefault(); return false;}
            // S - Настройки
            if (key === 'S') { if (typeof settings === "function") settings(); e.preventDefault(); return false;}
            // V - Телепорт
            if (key === 'V') { if (typeof goLocationTelep === "function") goLocationTelep(3); e.preventDefault(); return false;}
            // M - Карта
            if (key === 'M') { if (typeof openModal === "function") openModal('map'); e.preventDefault(); return false;}
            // D - Pokedex
            if (key === 'D') { if (typeof openModal === "function") openModal('pokedex'); e.preventDefault(); return false;}
            // B - Сумка/рюкзак
            if (key === 'B') { if (Game.modals.inventory) Game.modals.inventory('bag'); e.preventDefault(); return false;}

            // Для боя
            var elBattle = $('.Battle').length > 0;
            if (elBattle) {
                if (key === '1') { $('.MoveBox .Move:eq(0)').trigger('click'); e.preventDefault(); return false;}
                if (key === '2') { $('.MoveBox .Move:eq(1)').trigger('click'); e.preventDefault(); return false;}
                if (key === '3') { $('.MoveBox .Move:eq(2)').trigger('click'); e.preventDefault(); return false;}
                if (key === '4') { $('.MoveBox .Move:eq(3)').trigger('click'); e.preventDefault(); return false;}
                if (key === 'X') { $('.buttonFight.Button.LeaveButton').trigger('click'); e.preventDefault(); return false;}
                if (key === 'F') { $('.BattleItems .Item:eq(0)').trigger('click'); e.preventDefault(); return false;}
            }
        }
    }
});
// clanStorage.js — фронтенд для склада клана (инвентаря)

// --- Клановый склад: Глобальные функции (НЕ объект!) ---

var lastClanStorageId = null;

function openClanStorage(clanId) {
    lastClanStorageId = clanId;
    drawClanStorageModal();
    loadClanStorage(clanId);
}

function drawClanStorageModal() {
    $('.model, .Modal').remove();
    $('body').append(`
        <div class="Modal clan-storage-modal">
            <div class="clan-storage-modal-inner">
                <div class="clan-storage-header">
                    <span>Склад клана</span>
                    <span class="close" onclick="$('.clan-storage-modal').remove();">&times;</span>
                </div>
                <div class="clan-storage-body">
                    <div class="clan-storage-list"></div>
                    <div class="clan-storage-actions">
                        <button class="add-item-btn" onclick="openClanStorageAddForm()">Положить предмет</button>
                    </div>
                    <div class="clan-storage-add-form" style="display:none;"></div>
                </div>
            </div>
        </div>
    `);
}

function loadClanStorage(clanId) {
    $.post('/do/clanAction.php', {object: 'openStorage', clan_id: clanId}, function(res){
        var data = typeof res === 'string' ? JSON.parse(res) : res;
        var items = data.storage || data.items || [];
        var html = '';
        if (data.error) {
            html = '<div style="color:#b44c4c;">' + (data.text || 'Ошибка загрузки склада') + '</div>';
        } else if (!items.length) {
            html = '<div style="color:#8e9aad;">Склад пуст.</div>';
        } else {
            html = buildClanStorageList(items);
        }
        $('.clan-storage-list').html(html);
    });
}

function buildClanStorageList(items) {
    var html = '<div class="clan-storage-items-container">';
    items.forEach(function(item) {
        var img = (item.icon && item.icon !== '' ? item.icon : item.item_id) + '.png';
        html += `
            <div class="clan-storage-item" title="${item.name ? escapeHtml(item.name) : ''}">
                <img src="/img/world/items/little/${img}" alt="${item.name ? escapeHtml(item.name) : ''}">
                <span class="amount">x${item.amount}</span>
                <span class="item-name">${item.name ? escapeHtml(item.name) : ''}</span>
                <button class="take-btn" onclick="takeFromClanStorage(${item.item_id}, 1)">Забрать</button>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

function takeFromClanStorage(item_id, amount) {
    if (!confirm('Забрать этот предмет со склада?')) return;
    $.post('/do/clanAction.php', {
        object: 'clanStorageTake',
        clan_id: lastClanStorageId,
        item_id: item_id,
        amount: amount
    }, function(res){
        var data = typeof res === 'string' ? JSON.parse(res) : res;
        if (data.error) {
            alert(data.text || 'Ошибка');
        } else {
            loadClanStorage(lastClanStorageId);
            alert(data.text || 'Предмет получен!');
        }
    });
}

function openClanStorageAddForm() {
    var $form = $('.clan-storage-add-form');
    $form.show();
    $form.html(`
        <div style="margin-bottom:6px;color:#2d4169;font-weight:700;">Положить предмет на склад</div>
        <div id="clan-storage-add-selects" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <select id="clan-add-item-id"><option>Загрузка...</option></select>
            <input type="number" id="clan-add-item-amount" placeholder="Кол-во" min="1" value="1">
            <div class="clan-storage-add-form-buttons">
                <button class="add-item-btn" style="min-width:70px;" onclick="addToClanStorage()">Положить</button>
                <button class="add-item-btn" style="min-width:70px;background:#e7eaf0;color:#6e7b8e;" onclick="$('.clan-storage-add-form').hide();">Отмена</button>
            </div>
        </div>
    `);
    // Подгружаем предметы игрока
    $.post('/do/clanAction.php', {object: 'getUserItemsForClanStorage'}, function(res){
        var data = typeof res === 'string' ? JSON.parse(res) : res;
        var opts = '';
        if (data.items && data.items.length) {
            data.items.forEach(function(it){
                opts += `<option value="${it.item_id}">${escapeHtml(it.name)} x${it.count}</option>`;
            });
        } else {
            opts = '<option disabled>Нет предметов</option>';
        }
        $('#clan-add-item-id').html(opts);
    });
}
function addToClanStorage() {
    var item_id = parseInt($('#clan-add-item-id').val());
    var amount = parseInt($('#clan-add-item-amount').val()) || 1;
    if (!item_id || !amount) {
        alert('Выберите предмет и введите количество');
        return;
    }
    $.post('/do/clanAction.php', {
        object: 'clanStorageAdd',
        clan_id: lastClanStorageId,
        item_id: item_id,
        amount: amount
    }, function(res){
        var data = typeof res === 'string' ? JSON.parse(res) : res;
        if (data.error) {
            alert(data.text || 'Ошибка');
        } else {
            loadClanStorage(lastClanStorageId);
            $('.clan-storage-add-form').hide();
            alert(data.text || 'Предмет добавлен!');
        }
    });
}
// -------------------------
// Clan Shop (modal)
// -------------------------

// --- Фракции клана ---
function openClanFactions(clanId) {
    clanId = parseInt(clanId, 10) || 0;

    // создаём модал
    $('.model, .Modal').remove();
    $('body').append(`
      <div class="Modal clan-factions-modal">
        <div class="clan-storage-modal-inner" style="max-width:840px;">
          <div class="clan-storage-header">
            <span>Фракции клана</span>
            <span class="close" onclick="$('.clan-factions-modal').remove();">&times;</span>
          </div>
          <div class="clan-storage-body">
            <div class="clan-factions-hint" style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;">
              <div style="font-weight:800;color:#2d4169;">Выберите фракцию (если у вас есть права)</div>
              <div style="color:#8e9aad;font-size:12px;">Изменение фракции влияет на бонусы клана</div>
            </div>
            <div class="clan-factions-list" style="min-height:60px;"></div>
          </div>
        </div>
      </div>
    `);

    // грузим список
    $.post('/do/clanAction.php', {object:'factionsList'}, function(res){
        var data = (typeof res === 'string') ? JSON.parse(res) : res;
        if (!data || data.error) {
            $('.clan-factions-list').html('<div style="color:#b44c4c;">' + (data && data.text ? data.text : 'Ошибка загрузки фракций') + '</div>');
            return;
        }

        var items = data.items || [];
        if (!items.length) {
            $('.clan-factions-list').html('<div style="color:#8e9aad;">Фракции не настроены.</div>');
            return;
        }

        var html = '<div class="clan-factions-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;">';
        items.forEach(function(it){
            var code = it.code || it.abbr || it.short || '';
            var name = it.name || '';
            var color = it.badge_color || it.color || '#9CA3AF';
            html += `
              <div class="clan-faction-card" style="background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:12px;padding:10px;box-shadow:0 6px 14px rgba(15,23,42,.08);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:12px;height:12px;border-radius:4px;background:${color};box-shadow:inset 0 0 0 1px rgba(0,0,0,.12);"></span>
                    <div style="font-weight:900;color:#1f2a44;letter-spacing:.2px;">${esc(code || name)}</div>
                  </div>
                  <button class="clancard-btn" style="padding:6px 10px;font-size:12px;" onclick="clanSetFaction(${parseInt(it.id,10)||0}, ${clanId});">Выбрать</button>
                </div>
                <div style="margin-top:6px;color:#6b7280;font-size:12px;line-height:1.25;">${esc(name)}</div>
              </div>
            `;
        });
        html += '</div>';
        $('.clan-factions-list').html(html);
    });
}

function clanSetFaction(factionId, clanId){
    factionId = parseInt(factionId, 10) || 0;
    if (!factionId) return;

    $.post('/do/clanAction.php', {object:'setFaction', faction_id: factionId}, function(res){
        var data = (typeof res === 'string') ? JSON.parse(res) : res;
        if (data && data.text) {
            Game.notifications.main(data.text, data.error ? 'error' : 'success');
        }
        // обновим карточку клана, если она открыта
        try {
            $('.clan-factions-modal').remove();
            if (clanId) openClanCard(clanId);
        } catch (e) {}
    });
}

function openClanShop(clanId) {
    lastClanShopId = clanId;
    drawClanShopModal();
    loadClanShop(clanId);
}
function drawClanShopModal() {
    $('.model, .Modal').remove();
    $('body').append(`
        <div class="Modal clan-shop-modal">
            <div class="clan-storage-modal-inner" style="max-width:920px;">
                <div class="clan-storage-header">
                    <span>Магазин клана</span>
                    <span class="close" onclick="$('.clan-shop-modal').remove();">&times;</span>
                </div>
                <div class="clan-storage-body">
                    <div class="clan-shop-top" style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div style="font-weight:800;color:#2d4169;">Баланс клана: <span class="clan-shop-balance">...</span></div>
                        <div style="color:#8e9aad;font-size:12px;">Покупка списывает средства со счёта клана и выдаёт предмет вам.</div>
                    </div>
                    <div class="clan-shop-list"></div>
                </div>
            </div>
        </div>
    `);
}
function loadClanShop(clanId) {
    $.post('/do/clanAction.php', {object:'shopList', clan_id: clanId}, function(res){
        var data = typeof res === 'string' ? JSON.parse(res) : res;
        if (data.error) {
            $('.clan-shop-list').html('<div style="color:#b44c4c;">' + (data.text || 'Ошибка магазина') + '</div>');
            $('.clan-shop-balance').text('0');
            return;
        }
        $('.clan-shop-balance').text(data.balance || 0);

        var items = data.items || [];
        if (!items.length) {
            $('.clan-shop-list').html('<div style="color:#8e9aad;">Магазин пуст.</div>');
            return;
        }

        var html = '<div class="clan-shop-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;">';
        items.forEach(function(it){
            var icon = it.icon || '';
            var imgSrc = '';
            if (icon) {
                imgSrc = icon.match(/\.(png|jpg|jpeg|webp)$/i) ? icon : (icon + '.png');
                if (imgSrc.indexOf('/') === -1) imgSrc = '/img/world/items/' + imgSrc;
            } else {
                imgSrc = '/img/world/items/' + it.item_id + '.png';
            }

            var disabled = (it.remaining !== undefined && it.remaining <= 0) ? 'disabled' : '';
            var remTxt = (it.weekly_limit_clan && it.weekly_limit_clan > 0) ? ('Лимит недели: ' + it.remaining + '/' + it.weekly_limit_clan) : 'Без лимита';
            html += `
              <div class="clan-shop-item" style="background:#fff;border:1px solid #e6eafe;border-radius:12px;padding:12px;display:flex;gap:10px;">
                <div style="width:52px;height:52px;border-radius:12px;background:#f7f9ff;border:1px solid #e6eafe;display:grid;place-items:center;">
                  <img src="${imgSrc}" style="max-width:42px;max-height:42px;" onerror="this.style.display='none';">
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-weight:900;color:#2d4169;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(it.name || ('#'+it.item_id))}</div>
                  <div style="margin-top:4px;color:#8e9aad;font-size:12px;">Цена: <b>${it.price}</b> (со счёта клана)</div>
                  <div style="margin-top:2px;color:#8e9aad;font-size:12px;">${remTxt}</div>
                  <div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                    <input type="number" min="1" value="1" class="clan-shop-amount" data-catalog="${it.catalog_id}" style="width:80px;border-radius:10px;border:1px solid #e6eafe;padding:6px 8px;">
                    <button class="add-item-btn" ${disabled} onclick="buyClanShopItem(${it.catalog_id})" style="flex:1;">Купить</button>
                  </div>
                </div>
              </div>
            `;
        });
        html += '</div>';
        $('.clan-shop-list').html(html);
    });
}
function buyClanShopItem(catalogId) {
    var $inp = $('.clan-shop-amount[data-catalog="'+catalogId+'"]');
    var amount = parseInt($inp.val()) || 1;
    if (amount < 1) amount = 1;

    $.post('/do/clanAction.php', {object:'shopBuy', clan_id:lastClanShopId, catalog_id:catalogId, amount:amount}, function(res){
        var data = typeof res === 'string' ? JSON.parse(res) : res;
        if (data.error) {
            Game.notifications?.main?.(data.text || 'Ошибка покупки', 'error');
            return;
        }
        Game.notifications?.main?.(data.text || 'Покупка успешна!', 'success');
        if (data.balance !== undefined) $('.clan-shop-balance').text(data.balance);
        loadClanShop(lastClanShopId);
    });
}

// Экранирование HTML (чтобы избежать XSS через имена предметов)
function escapeHtml(text) {
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
if (response.closeDialog) {
    $('.DivNpcBlock').remove(); // или любой ваш метод закрытия окна
}
// Подключайте этот файл после вставки .Quests на страницу

function bindAlbumTabs() {
    document.querySelectorAll('.album-tab').forEach(function (btn) {
        btn.onclick = function (e) {
            e.preventDefault();
            var colid = this.getAttribute('data-colid');
            btn.disabled = true;
            fetch('/do/stickers.php?col_id=' + encodeURIComponent(colid), {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.html) {
                    // Вставляем в контейнер коллекций .Quests
                    var container = document.querySelector('.Quests');
                    if (container) {
                        container.innerHTML = data.html;
                        bindAlbumTabs(); // Повторно навесить обработчики!
                    }
                } else {
                    alert('Ошибка: пустой ответ сервера');
                }
            })
            .catch(function () {
                alert('Ошибка загрузки коллекции');
            })
            .finally(function () {
                btn.disabled = false;
            });
        };
    });
}
$.post('rb_arena_npc.php', data, function(response){
    // Ваш рендер диалога...
    if (response.closeDialog) {
        $('.DivNpcBlock').remove();
    }
    // ...остальной код
}, 'json');
// Вызывайте эту функцию после первой загрузки коллекции и после смены вкладки на "Коллекция"
document.addEventListener('DOMContentLoaded', function() {
    bindAlbumTabs();
});
/**
 * Универсальная функция для удаления временной команды RB
 * Можно вызывать после боя или после смены локации
 */
/**
 * Универсальная функция для удаления временной команды RB
 * Можно вызывать после боя или после смены локации
 */
function rbCleanupTeam() {
    $.ajax({
        url: '/rb/cleanup.php', // Этот PHP-скрипт должен вызывать rb_removePokemons для текущего пользователя
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response && response.success) {
                showNotification('Временная команда успешно удалена.', 'success');
            } else if(response && response.error) {
                showNotification('Ошибка: ' + response.error, 'error');
            } else {
                showNotification('Не удалось удалить временную команду.', 'warning');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Ошибка соединения с сервером: ' + error, 'error');
        }
    });
}

/**
 * Универсальное уведомление (замените на свою реализацию, если есть)
 * type: 'success', 'error', 'warning', 'info'
 */
function showNotification(message, type = 'info') {
    // Bootstrap 5 Toast, или любая ваша библиотека уведомлений
    if (window.toastr) {
        toastr[type](message);
        return;
    }
    // Примитивное уведомление (замените на кастомное оформление)
    let color = {
        success: '#4caf50',
        error: '#f44336',
        warning: '#ff9800',
        info: '#2196f3'
    }[type] || '#2196f3';

    let notif = document.createElement('div');
    notif.style.position = 'fixed';
    notif.style.top = '30px';
    notif.style.right = '30px';
    notif.style.background = color;
    notif.style.color = '#fff';
    notif.style.padding = '12px 24px';
    notif.style.borderRadius = '8px';
    notif.style.boxShadow = '0 2px 12px rgba(0,0,0,0.2)';
    notif.style.zIndex = 9999;
    notif.style.fontSize = '16px';
    notif.textContent = message;

    document.body.appendChild(notif);

    setTimeout(() => {
        notif.style.transition = 'opacity 0.5s';
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 500);
    }, 2500);
}
function showSkinNotify(response) {
    // На случай, если по ошибке response строка
    if (typeof response === "string") {
        try {
            response = (typeof response === 'string') ? JSON.parse(response) : response;
        } catch (e) {
            response = { text: response, error: 1 };
        }
    }

    var msg = response && response.text ? response.text : 'Неизвестный ответ от сервера';
    var notifyType = (response && response.error === 0) ? "success" : "error";

    if (typeof Game !== 'undefined' && Game.notifications && Game.notifications.main) {
        Game.notifications.main(msg, notifyType);
    } else {
        alert(msg);
    }
}

// ========== ВСПОМОГАТЕЛЬНЫЕ УТИЛИТЫ ==========
function escapeHTML(str){
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}
function formatPrice(num){
  if (isNaN(num)) return '0';
  var n = Number(num);
  return n.toLocaleString('ru-RU');
}
function showLoading(host, text){
  $(host).html(
    '<div class="giftshop-loading">' +
      '<div class="spinner"></div>' +
      '<div class="giftshop-loading-text">'+ escapeHTML(text || 'Загрузка...') +'</div>' +
    '</div>'
  );
}

// ========== 1. Получить список подарков магазина ==========
function loadGiftShop(callback) {
  $.post('/do/giftshop.php', { type: 'giftshop_list' }, function(resp) {
    if (resp && resp.error === 0) {
      if (typeof callback === 'function') callback(resp.items || []);
    } else {
      showMessage((resp && resp.text) || 'Ошибка загрузки магазина подарков', 'error');
    }
  }, 'json')
  .fail(function(){
    showMessage('Сеть недоступна (магазин подарков)', 'error');
  });
}

// ========== 2. Открыть окно магазина подарков для отправки другу ==========
function openGiftShopForFriend(friendId, friendLogin) {
  $('#giftShopPanel').remove();

  loadGiftShop(function(items) {
    var escLogin = escapeHTML(friendLogin);
    var cards = '<div class="giftshop-grid" id="giftshopGrid">';

    if (!items || !items.length) {
      cards += '<div class="giftshop-empty">Нет доступных подарков</div>';
    } else {
      items.forEach(function(it) {
        var id          = escapeHTML(it.id);
        var name        = escapeHTML(it.name);
        var desc        = it.description ? escapeHTML(it.description) : '';
        var price       = it.price;
        var priceLabel  = formatPrice(price);
        var img         = it.img ? '<img src="' + escapeHTML(it.img) + '" class="giftshop-card-img" alt="' + name + '">' : '';
        cards +=
          '<div class="giftshop-card" tabindex="0" role="button" aria-label="Подарок ' + name +
            '" data-gift-id="' + id + '" data-price="' + escapeHTML(price) + '">' +
            img +
            '<div class="giftshop-card-title" title="' + name + '">' + name + '</div>' +
            (desc ? '<div class="giftshop-card-desc" title="' + desc + '">' + desc + '</div>' : '') +
            '<div class="giftshop-card-footer">' +
              '<span class="giftshop-card-price" title="Цена: ' + priceLabel + '"><i class="fa fa-gem"></i> ' + priceLabel + '</span>' +
            '</div>' +
          '</div>';
      });
    }
    cards += '</div>';

    var html =
      '<div id="giftShopPanel" data-friend-id="' + escapeHTML(friendId) + '">' +
        '<span class="close-btn" title="Закрыть">&times;</span>' +
        '<div class="giftshop-title">Подарок для <b>' + escLogin + '</b></div>' +
        '<div class="giftshop-list">' + cards + '</div>' +
        '<div class="giftshop-form" style="margin-top:18px;">' +
          '<label for="giftShopMsg">Сообщение:</label>' +
          '<input type="text" id="giftShopMsg" style="width:96%;" maxlength="100" placeholder="Добавьте пожелание (до 100 символов)">' +
          '<div class="giftshop-msg-counter" id="giftshopMsgCounter">0 / 100</div>' +
        '</div>' +
        '<div style="margin-top:18px;">' +
          '<button id="giftShopSendBtn" disabled>Отправить подарок</button>' +
        '</div>' +
        '<div id="giftShopResult" style="margin-top:12px;"></div>' +
      '</div>';
    $('body').append(html);

    var selectedGiftId = null;
    var $panel = $('#giftShopPanel');

    function selectGift($card){
      $('.giftshop-card').removeClass('selected').attr('data-selected', '0');
      $card.addClass('selected').attr('data-selected', '1');
      selectedGiftId = $card.data('gift-id');
      $('#giftShopSendBtn').prop('disabled', false);
    }

    $('.giftshop-card').on('click', function(){ selectGift($(this)); })
      .on('keypress', function(e){
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          selectGift($(this));
        }
      });

    $('#giftShopPanel .close-btn').on('click', closeGiftShopPanel);
    $(document).on('keydown.giftshopEsc', function(e){
      if (e.key === 'Escape') closeGiftShopPanel();
    });
    function closeGiftShopPanel(){
      $(document).off('keydown.giftshopEsc');
      $('#giftShopPanel').fadeOut(120, function(){ $(this).remove(); });
    }

    $('#giftShopMsg').on('input', function(){
      var len = $(this).val().length;
      $('#giftshopMsgCounter').text(len + ' / 100')
        .toggleClass('limit-near', len >= 90);
    }).trigger('input');

    $('#giftShopMsg').on('keypress', function(e){
      if (e.key === 'Enter') {
        if (!$('#giftShopSendBtn').prop('disabled')) {
          $('#giftShopSendBtn').trigger('click');
        }
      }
    });

    $('#giftShopSendBtn').on('click', function() {
      if (!selectedGiftId) return;
      var $btn = $(this);
      if ($btn.data('loading')) return;

      var msg = $('#giftShopMsg').val();
      $btn.data('loading', true)
          .prop('disabled', true)
          .addClass('loading')
          .text('Отправка...');

      $.post('/do/giftshop.php', {
        type: 'send_gift',
        to_user: friendId,
        gift_id: selectedGiftId,
        message: msg
      }, function(resp) {
        if (resp && resp.error === 0) {
          $('#giftShopResult').html('<span class="giftshop-result-ok">' + escapeHTML(resp.text) + '</span>');
          updateGiftInboxCount(); // обновим счётчик входящих
          // Сброс выбора (OPTIONAL)
          $('.giftshop-card.selected').removeClass('selected');
          selectedGiftId = null;
        } else {
          $('#giftShopResult').html('<span class="giftshop-result-err">' + escapeHTML((resp && resp.text) || 'Ошибка отправки') + '</span>');
        }
      }, 'json')
      .fail(function(){
        $('#giftShopResult').html('<span class="giftshop-result-err">Сеть недоступна (отправка)</span>');
      })
      .always(function(){
        $btn.data('loading', false)
            .removeClass('loading')
            .text('Отправить подарок')
            .prop('disabled', !selectedGiftId);
      });
    });
  });
}

// ========== 3. Получить входящие подарки ==========
function loadGiftInbox(callback) {
  $.post('/do/giftshop.php', { type: 'list_gifts' }, function(resp) {
    if (resp && resp.error === 0) {
      if (typeof callback === 'function') callback(resp.gifts || []);
    } else {
      showMessage((resp && resp.text) || 'Ошибка загрузки подарков', 'error');
    }
  }, 'json')
  .fail(function(){
    showMessage('Сеть недоступна (входящие подарки)', 'error');
  });
}

// ========== 4. Открыть окно входящих подарков ==========
function showGiftInboxPanel() {
  $('#giftInboxPanel').remove();

  // Шаблон окна (покажем сразу оболочку с лоудером)
  var baseHtml =
    '<div id="giftInboxPanel">' +
      '<span class="close-btn" title="Закрыть">&times;</span>' +
      '<div class="giftshop-title">Ваши подарки</div>' +
      '<div id="giftInboxList"></div>' +
    '</div>';
  $('body').append(baseHtml);
  var $list = $('#giftInboxList');
  showLoading($list, 'Загрузка подарков');

  loadGiftInbox(function(gifts) {
    if (!gifts || !gifts.length) {
      $list.html('<div class="giftshop-empty">Нет новых подарков</div>');
    } else {
      var html = '';
      gifts.forEach(function(gift) {
        var giftId = escapeHTML(gift.id);
        var from   = escapeHTML(gift.from_login || 'Неизвестно');
        var gname  = escapeHTML(gift.gift_name || 'Неизвестно');
        var desc   = gift.description ? '<div class="gift-inbox-desc">' + escapeHTML(gift.description) + '</div>' : '';
        var msg    = escapeHTML(gift.message || '');
        var img    = gift.img ? '<img src="' + escapeHTML(gift.img) + '" class="gift-inbox-img" alt="gift">' : '';

        html +=
          '<div class="gift-inbox-item" data-gift-id="' + giftId + '">' +
            '<div class="gift-inbox-row">' +
              img +
              '<div class="gift-inbox-info">' +
                '<div class="gift-inbox-from"><b>От:</b> ' + from + '</div>' +
                '<div class="gift-inbox-name"><b>Подарок:</b> ' + gname + '</div>' +
                desc +
                '<div class="gift-inbox-message"><b>Сообщение:</b> ' + msg + '</div>' +
                '<button class="receiveGiftBtn" data-id="' + giftId + '">Забрать</button>' +
              '</div>' +
            '</div>' +
          '</div>';
      });
      $list.html(html);
    }

    $('#giftInboxPanel .close-btn').on('click', closeGiftInbox);
    $(document).on('keydown.giftInboxEsc', function(e){
      if (e.key === 'Escape') closeGiftInbox();
    });
    function closeGiftInbox(){
      $(document).off('keydown.giftInboxEsc');
      $('#giftInboxPanel').fadeOut(120, function(){ $(this).remove(); });
    }

    // Обработчик получения подарка
    $('.receiveGiftBtn').on('click', function() {
      var $btn  = $(this);
      var giftId = $btn.data('id');
      var $item = $btn.closest('.gift-inbox-item');

      if ($btn.data('loading')) return;
      $btn.data('loading', true).prop('disabled', true).text('Забираю...');

      $.post('/do/giftshop.php', { type: 'receive_gift', gift_id: giftId }, function(resp) {
        showMessage(resp.text, resp.error ? 'error' : 'success');
        if (resp.error === 0) {
          // Конфетти (если библиотека есть)
          if (typeof confetti === 'function') {
            var $canvas = $('<canvas class="gift-confetti-canvas"></canvas>').css({
              position: 'absolute',
              left: 0,
              top: 0,
              width: $item.outerWidth(),
              height: $item.outerHeight(),
              pointerEvents: 'none',
              zIndex: 10
            }).appendTo($item);

            try {
              confetti.create($canvas[0], {resize: false, useWorker: true})({
                particleCount: 70,
                spread: 88,
                startVelocity: 24,
                origin: { y: 0.7 },
                colors: ['#995fc4','#b08ae6','#f6f7fa','#fdceff','#fff'],
                shapes: ['circle','square']
              });
            } catch(e){ /* fail silently */ }

            setTimeout(function(){ $canvas.remove(); }, 1500);
          }

            // Удаляем элемент плавно
          $item.addClass('animating');
          setTimeout(function(){
            $item.fadeOut(260, function(){ 
              $(this).remove();
              // Обновим счётчик входящих на панели
              updateGiftInboxCount();
              // Если пусто всё – показываем заглушку
              if (!$('#giftInboxList .gift-inbox-item').length) {
                $('#giftInboxList').html('<div class="giftshop-empty">Нет новых подарков</div>');
              }
            });
          }, 900);
        } else {
          $btn.prop('disabled', false);
        }
      }, 'json')
      .fail(function(){
        showMessage('Сеть недоступна (получение подарка)', 'error');
        $btn.prop('disabled', false);
      })
      .always(function(){
        $btn.data('loading', false).text('Забрать');
      });
    });
  });
}

// ========== 5. Вспомогательная функция для сообщений ==========
function showMessage(text, type) {
  var color;
  switch (type) {
    case 'success': color = '#2b0'; break;
    case 'error': color = '#c00'; break;
    default: color = '#555';
  }
  var $msg = $('<div class="giftshop-toast" role="alert" aria-live="assertive"></div>')
    .css({
      position:'fixed',
      top:'30px',
      left:'50%',
      transform:'translateX(-50%)',
      padding:'10px 22px',
      background:'#fff',
      border:'2px solid '+color,
      color: color,
      zIndex: 99999,
      fontSize:'16px',
      fontFamily:'inherit',
      borderRadius:'6px',
      boxShadow:'0 4px 14px rgba(0,0,0,0.15)',
      maxWidth:'420px',
      textAlign:'center'
    })
    .text(text)
    .appendTo('body');

  setTimeout(function(){
    $msg.fadeOut(400, function(){ $(this).remove(); });
  }, 1800);
}

// ========== 6. Обновление счётчика входящих ==========
function updateGiftInboxCount() {
  $.post('/do/giftshop.php', { type: 'list_gifts' }, function(resp) {
    if (resp && resp.error === 0) {
      var cnt = (resp.gifts || []).length;
      $('#giftInboxCount').text(cnt || '');
    }
  }, 'json');
}
// ================== Конфигурация ==================
var GIFT_INFO_ENDPOINT = '/do/trainers.php'; // при необходимости поменять
var GIFT_INFO_ACTION   = 'giftinfo';
var GIFT_FALLBACK_IMG  = '/images/giftshop/default.png';

// ================== Вспомогательные ==================
function giftEscape(str){
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

function giftSafeUrl(url){
  if (!url) return GIFT_FALLBACK_IMG;
  // Ограничим протоколы
  if (!/^https?:\/\//i.test(url) && !url.startsWith('/') ) {
    return GIFT_FALLBACK_IMG;
  }
  return url;
}

function formatDateTime(dt){
  if (!dt) return '';
  // Если в БД DATETIME формата "YYYY-MM-DD HH:MM:SS" — можно слегка оформить
  // Позже можно заменить на локализацию.
  return dt;
}

/* ====== CONFIG ====== */
var GIFT_INFO_ENDPOINT = typeof GIFT_INFO_ENDPOINT !== 'undefined' ? GIFT_INFO_ENDPOINT : '/do/trainers.php';
var GIFT_INFO_ACTION   = typeof GIFT_INFO_ACTION   !== 'undefined' ? GIFT_INFO_ACTION   : 'giftinfo';
var GIFT_FALLBACK_IMG  = typeof GIFT_FALLBACK_IMG  !== 'undefined' ? GIFT_FALLBACK_IMG  : '/images/giftshop/default.png';
var DEBUG_GIFTS        = true;

/* ====== HELPERS ====== */
function giftEscape(str){
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}
function giftSafeUrl(u){
  if (!u) return GIFT_FALLBACK_IMG;
  if (!/^https?:\/\//i.test(u) && u.charAt(0) !== '/') return GIFT_FALLBACK_IMG;
  return u;
}
function formatDateTime(dt){ return dt || ''; }
function dbg(){ if (DEBUG_GIFTS) try{ console.log.apply(console, arguments); }catch(e){} }
function dbgWarn(){ if (DEBUG_GIFTS) try{ console.warn.apply(console, arguments); }catch(e){} }

/* ====== РЕНДЕР КАРТОЧЕК (как у вас, чуть подчистил) ====== */
function makeGiftCards(receivedGifts, asDOM) {
  if (!Array.isArray(receivedGifts) || receivedGifts.length === 0){
    var emptyHtml = '<div class="gift-empty">Нет полученных подарков</div>';
    return asDOM ? [ $(emptyHtml) ] : emptyHtml;
  }
  if (asDOM){
    return receivedGifts.map(buildGiftCardElement);
  } else {
    return receivedGifts.map(function(gift){
      var id    = giftEscape(gift.id);
      var img   = giftSafeUrl(gift.img);
      var title = giftEscape(gift.title || gift.name || 'Без названия');
      var from  = giftEscape(gift.from_user_login || gift.from || '—');
      var msg   = gift.message ? giftEscape(gift.message) : '';
      var date  = formatDateTime(giftEscape(gift.date || gift.created_at || ''));
      return '' +
        '<div class="gift-card" data-gift-id="'+id+'" tabindex="0" role="button" aria-label="Подарок '+title+' от '+from+'">'+
          '<div class="gift-card-thumb">'+
            '<img src="'+img+'" alt="'+title+'" class="gift-img" onerror="this.onerror=null;this.src=\''+giftEscape(GIFT_FALLBACK_IMG)+'\';">'+
          '</div>'+
          '<div class="gift-title" title="'+title+'">'+title+'</div>'+
          '<div class="gift-from" title="Отправитель">'+from+'</div>'+
          (msg ? '<div class="gift-message-inline" title="'+msg+'">'+ (msg.length>28? giftEscape(msg.slice(0,25))+'…':msg) +'</div>' : '')+
          (date ? '<div class="gift-date" title="Получен">'+date+'</div>' : '')+
        '</div>';
    }).join('');
  }
}

function buildGiftCardElement(gift){
  var id    = giftEscape(gift.id);
  var img   = giftSafeUrl(gift.img);
  var title = giftEscape(gift.title || gift.name || 'Без названия');
  var from  = giftEscape(gift.from_user_login || gift.from || '—');
  var msg   = gift.message ? giftEscape(gift.message) : '';
  var date  = formatDateTime(giftEscape(gift.date || gift.created_at || ''));
  return $('<div/>',{
    'class':'gift-card',
    'data-gift-id': id,
    'tabindex':0,
    'role':'button',
    'aria-label':'Подарок '+title+' от '+from
  }).append(
    $('<div/>',{'class':'gift-card-thumb'}).append(
      $('<img/>',{
        'class':'gift-img',
        'src': img,
        'alt': title,
        'error': function(){ this.onerror=null; this.src = GIFT_FALLBACK_IMG; }
      })
    ),
    $('<div/>',{'class':'gift-title','title':title,text:title}),
    $('<div/>',{'class':'gift-from', 'title':'Отправитель', text:from}),
    msg ? $('<div/>',{'class':'gift-message-inline','title':msg,text:(msg.length>28? msg.slice(0,25)+'…':msg)}) : '',
    date? $('<div/>',{'class':'gift-date','title':'Получен',text:date}) : ''
  );
}

/* ====== ОТРИСОВКА СПИСКА ====== */
function showGiftCards(receivedGifts) {
  var $container = $('.Gift');
  if (!$container.length) {
    dbgWarn('.Gift контейнер не найден — создаю заново');
    $container = $('<div/>', {'class':'Gift'}).appendTo('#giftBlock');
  }
  $container.empty().html(makeGiftCards(receivedGifts, false));
  initGiftCardDelegation(); // гарантируем обработчики
  dbg('Отрисовано подарков:', $('.Gift .gift-card').length);
}

/* ====== ДЕЛЕГИРОВАНИЕ (устойчивое) ====== */
function initGiftCardDelegation(){
  // Снимаем прежние global биндинги
  $(document).off('click.giftCardOpen keydown.giftCardOpen');
  // Делегирование клика на документ (он не пересоздаётся)
  $(document).on('click.giftCardOpen', '.gift-card', function(e){
    // Если родитель — ссылка, отменим переход
    e.preventDefault();
    e.stopPropagation();
    var giftId = $(this).data('gift-id');
    dbg('[delegated click] gift-card -> id:', giftId, this);
    if (!giftId){
      showGiftInfoError('Нет ID подарка');
      return;
    }
    if (!/^[0-9]+$/.test(String(giftId))){
      showGiftInfoError('ID подарка некорректен');
      return;
    }
    showGiftInfo(giftId);
  });
  // Дополнительное делегирование на изображение (если вдруг стили мешают)
  $(document).on('click.giftCardOpen', '.gift-card .gift-img', function(e){
    e.preventDefault(); e.stopPropagation();
    var giftId = $(this).closest('.gift-card').data('gift-id');
    dbg('[delegated click IMG] id:', giftId);
    if (giftId) showGiftInfo(giftId);
  });
  // Клавиатура
  $(document).on('keydown.giftCardOpen', '.gift-card', function(e){
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).trigger('click');
    }
  });
}

/* ====== ДЛЯ ТЕСТА: INLINE onclick (если очень хотите увидеть в DOM) ====== */
function enableInlineGiftOnclick(){
  $('.gift-card').each(function(){
    var id = $(this).data('gift-id');
    if (id && /^[0-9]+$/.test(String(id))){
      this.setAttribute('onclick','giftCardClickInline('+id+')');
    }
  });
  dbg('Inline onclick добавлены');
}
function giftCardClickInline(id){
  dbg('[inline onclick] id=', id);
  showGiftInfo(id);
}

/* ====== МОДАЛКА ====== */
function showGiftInfo(giftId) {
  giftId = parseInt(giftId,10);
  if (isNaN(giftId) || giftId <= 0) {
    showGiftInfoError('Некорректный идентификатор подарка');
    return;
  }
  dbg('[showGiftInfo] ->', giftId);
  $('#giftInfoModal').remove();
  var skeleton = buildGiftInfoSkeleton();
  $('body').append(skeleton);

  if (typeof GIFT_INFO_ENDPOINT === 'undefined'){
    showGiftInfoError('GIFT_INFO_ENDPOINT не задан');
    return;
  }

  $.ajax({
    url: GIFT_INFO_ENDPOINT,
    method: 'GET',
    data: { action: GIFT_INFO_ACTION, id: giftId },
    dataType: 'json',
    cache: false
  }).done(function(info){
    dbg('[giftinfo OK]', info);
    if (!info || info.error){
      showGiftInfoError(info && info.error ? info.error : 'Нет данных о подарке');
      return;
    }
    fillGiftInfoModal(info);
  }).fail(function(jq, text, err){
    dbgWarn('[giftinfo FAIL]', text, err, jq && jq.responseText);
    showGiftInfoError('Ошибка связи: '+text);
  });
}

function buildGiftInfoSkeleton(){
  return (
    '<div id="giftInfoModal" class="gift-info-modal" role="dialog" aria-modal="true">'+
      '<div class="gift-info-content">'+
        '<button type="button" class="gift-info-close" aria-label="Закрыть">×</button>'+
        '<div class="gift-info-body loading">'+
          '<div class="gift-info-loader">'+
            '<div class="gift-spinner"></div>'+
            '<div class="gift-loading-text">Загрузка...</div>'+
          '</div>'+
        '</div>'+
      '</div>'+
    '</div>'
  );
}

function fillGiftInfoModal(info){
  var img   = giftSafeUrl(info.img);
  var title = giftEscape(info.title || info.name || 'Без названия');
  var from  = giftEscape(info.from_user_login || 'Неизвестно');
  var message = (info.message ? giftEscape(info.message) : '');
  var desc  = info.description ? giftEscape(info.description) : '';
  var price = (info.price !== null && info.price !== undefined && info.price !== '') ? giftEscape(info.price) : '—';
  var date  = info.date ? giftEscape(info.date) : '';
  var status= info.status ? giftEscape(info.status) : '';

  var bodyHtml =
    '<div class="gift-info-main">'+
      '<div class="gift-info-image-wrap">'+
        '<img src="'+img+'" alt="'+title+'" class="gift-info-img" onerror="this.onerror=null;this.src=\''+giftEscape(GIFT_FALLBACK_IMG)+'\';">'+
      '</div>'+
      '<div class="gift-info-title">'+title+'</div>'+
      '<div class="gift-info-from"><span class="label">От:</span> '+from+'</div>'+
      (message ? '<div class="gift-info-message"><span class="label">Сообщение:</span> '+message+'</div>' : '')+
      (desc ? '<div class="gift-info-desc">'+desc+'</div>' : '')+
      '<div class="gift-info-price"><span class="label">Цена:</span> '+price+'</div>'+
      (status ? '<div class="gift-info-status"><span class="label">Статус:</span> '+status+'</div>' : '')+
      (date ? '<div class="gift-info-date">'+date+'</div>' : '')+
    '</div>';

  var $modal = $('#giftInfoModal');
  $modal.find('.gift-info-body').removeClass('loading error').html(bodyHtml);

  // Bind close
  $(document).off('keydown.giftInfo').on('keydown.giftInfo', function(e){
    if (e.key === 'Escape') closeGiftModal();
  });
  $modal.find('.gift-info-close').on('click', closeGiftModal);
  $modal.on('mousedown', function(e){ if (e.target === this) closeGiftModal(); });

  function closeGiftModal(){
    $(document).off('keydown.giftInfo');
    $modal.fadeOut(140, function(){ $modal.remove(); });
  }
}

function showGiftInfoError(msg){
  var $modal = $('#giftInfoModal');
  if (!$modal.length) {
    $('body').append(buildGiftInfoSkeleton());
    $modal = $('#giftInfoModal');
  }
  $modal.find('.gift-info-body')
    .removeClass('loading')
    .addClass('error')
    .html('<div class="gift-info-error">'+giftEscape(msg)+'</div><div class="gift-info-actions"><button type="button" class="gift-info-close-btn">Закрыть</button></div>');
  $modal.find('.gift-info-close, .gift-info-close-btn').on('click', function(){
    $(document).off('keydown.giftInfo');
    $modal.fadeOut(100, function(){ $modal.remove(); });
  });
}

/* ====== ИНИЦИАЛИЗАЦИЯ (пример) ====== */
function initGiftSection(response){
  var gifts = (response && response.receivedGifts) ? response.receivedGifts : [];
  if (!$('.Gift').length){
    $('<div/>', {'class':'Gift'}).appendTo('#giftBlock');
  }
  showGiftCards(gifts);
  // Для проверки inline (раскомментируйте):
  // enableInlineGiftOnclick();
}

/* ====== Авто debug helper ====== */
if (DEBUG_GIFTS){
  window.__giftDbg = {
    initGiftSection,
    showGiftInfo,
    enableInlineGiftOnclick,
    version: 'click-fix-1'
  };
  dbg('Gift click module loaded. Helpers: window.__giftDbg');
}
function getAvatarPreviewPath(cloth, sex) {
    let model = (cloth.model && !isNaN(cloth.model) && parseInt(cloth.model) > 0) ? parseInt(cloth.model) : null;
    let skin = (cloth.skin && !isNaN(cloth.skin) && parseInt(cloth.skin) > 0) ? parseInt(cloth.skin) : null;
    let color = (cloth.color !== undefined && cloth.color !== '' && cloth.color !== '0') ? cloth.color : null;
    let hat = (cloth.hat !== undefined && cloth.hat !== '' && cloth.hat !== '0') ? cloth.hat : null;

    // Определяем пол по model, если не передан
    if (!sex) {
        sex = (model !== null && model >= 1 && model <= 4) ? 'f' : 'm';
    }
    if (model === null)  model = (sex === 'f') ? 1 : 5;
    if (skin === null)   skin = model;
    if (color === null)  color = 'a';

    let folder = model + 'a' + skin;
    let filename = model + 'a' + skin + color;

    let path;
    if (hat) {
        path = `/img/avatars/model/ava/${model}/hat/${filename}${hat}.png`;
    } else {
        if (skin === model && color === 'a') {
            path = `/img/avatars/model/ava/${model}/${model}/${model}a.png`;
        } else {
            path = `/img/avatars/model/ava/${model}/${folder}/${filename}.png`;
        }
    }

    return path;
}

function previewSkin(btn) {
    const item = btn.getAttribute('data-item');
    const previewMode = btn.getAttribute('data-mode') || 'auto';
    
    showSkinPreviewModal('', { loading: true });
    
    fetch('/do/Issets', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=skin_preview&item=${encodeURIComponent(item)}&mode=${encodeURIComponent(previewMode)}`
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success && data.preview) {
            showSkinPreviewModal(data.preview, {
                skinInfo: data.skin_info || {},
                loading: false,
                itemId: item
            });
        } else {
            showSkinPreviewModal('', {
                error: data.error || 'Ошибка загрузки предпросмотра',
                loading: false
            });
        }
    })
    .catch(error => {
        console.error('Ошибка предпросмотра:', error);
        showSkinPreviewModal('', {
            error: 'Произошла ошибка при загрузке',
            loading: false
        });
    });
}

function previewSkinInv(btn) {
    const item_id = btn.getAttribute('data-item');
    const previewMode = btn.getAttribute('data-mode') || 'auto';
    
    showSkinPreviewModal('', { loading: true });
    
    fetch('/do/Issets', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=skin_preview_inv&item=${encodeURIComponent(item_id)}&mode=${encodeURIComponent(previewMode)}`
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success && data.preview) {
            showSkinPreviewModal(data.preview, {
                skinInfo: data.skin_info || {},
                loading: false,
                itemId: item_id,
                isInventory: true
            });
        } else {
            showSkinPreviewModal('', {
                error: data.error || 'Ошибка загрузки предпросмотра',
                loading: false
            });
        }
    })
    .catch(error => {
        console.error('Ошибка предпросмотра:', error);
        showSkinPreviewModal('', {
            error: 'Произошла ошибка при загрузке',
            loading: false
        });
    });
}

function showSkinPreviewModal(imgPath, options = {}) {
    const { loading = false, error = null, skinInfo = {}, itemId = null, isInventory = false } = options;
    let modal = document.getElementById('skin-preview-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'skin-preview-modal';
        modal.style = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;z-index:1000;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.15);min-height:400px;width:350px;max-width:90vw;font-family:Arial,sans-serif';
        
        modal.innerHTML = `
            <div style="text-align:center">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:1px solid #eee;padding-bottom:10px;">
                    <h3 style="margin:0;color:#333;font-size:18px;">Предпросмотр скина</h3>
                    <button id="skin-preview-close" style="background:none;border:none;font-size:20px;cursor:pointer;color:#999;padding:0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;">&times;</button>
                </div>
                
                <div id="skin-preview-content">
                    <div id="skin-preview-loader" style="margin:40px 0;color:#666;display:none;">
                        <div style="width:40px;height:40px;border:3px solid #f3f3f3;border-top:3px solid #3498db;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 10px;"></div>
                        Загрузка...
                    </div>
                    
                    <div id="skin-preview-error" style="color:#e74c3c;margin:20px 0;display:none;"></div>
                    
                    <div id="skin-preview-image-container" style="margin-bottom:15px;display:none;">
                        <img src="" id="skin-preview-img" style="max-width:250px;max-height:280px;border-radius:8px;border:2px solid #eee;">
                    </div>
                    
                    <div id="skin-preview-info" style="text-align:left;background:#f8f9fa;padding:10px;border-radius:5px;font-size:14px;display:none;">
                        <div id="skin-compatibility" style="margin-bottom:8px;"></div>
                        <div id="skin-sex-info" style="margin-bottom:8px;"></div>
                        <div id="skin-model-info" style="color:#666;"></div>
                    </div>
                    
                    <div id="model-selector" style="margin:15px 0;padding:10px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:5px;display:none;">
                        <div style="margin-bottom:10px;color:#856404;font-weight:bold;">
                            ⚠ Скин не подходит вашему полу
                        </div>
                        <div style="margin-bottom:10px;color:#856404;font-size:14px;">
                            Выберите модель для предпросмотра:
                        </div>
                        <div style="display:flex;gap:10px;justify-content:center;">
                            <button class="model-btn" data-model="1" style="padding:8px 12px;border:1px solid #ddd;background:#f8f9fa;border-radius:4px;cursor:pointer;font-size:12px;">Модель 1</button>
                            <button class="model-btn" data-model="2" style="padding:8px 12px;border:1px solid #ddd;background:#f8f9fa;border-radius:4px;cursor:pointer;font-size:12px;">Модель 2</button>
                            <button class="model-btn" data-model="3" style="padding:8px 12px;border:1px solid #ddd;background:#f8f9fa;border-radius:4px;cursor:pointer;font-size:12px;">Модель 3</button>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top:15px;">
                    <button id="skin-preview-close-btn" style="background:#6c757d;color:white;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">Закрыть</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);

        // Добавляем CSS стили
        if (!document.getElementById('skin-preview-styles')) {
            const style = document.createElement('style');
            style.id = 'skin-preview-styles';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                #skin-preview-modal {
                    animation: modalFadeIn 0.2s ease-out;
                }
                @keyframes modalFadeIn {
                    from { opacity: 0; transform: translate(-50%, -60%); }
                    to { opacity: 1; transform: translate(-50%, -50%); }
                }
                .model-btn:hover {
                    background: #e9ecef !important;
                    border-color: #adb5bd !important;
                }
                .model-btn.active {
                    background: #007bff !important;
                    color: white !important;
                    border-color: #007bff !important;
                }
            `;
            document.head.appendChild(style);
        }

        // Обработчики закрытия
        const closeModal = () => {
            modal.style.display = 'none';
        };

        document.getElementById('skin-preview-close').onclick = closeModal;
        document.getElementById('skin-preview-close-btn').onclick = closeModal;

        // Закрытие по Escape
        document.addEventListener('keydown', function escListener(e) {
            if (e.key === "Escape" && modal.style.display !== 'none') {
                closeModal();
            }
        });

        // Закрытие по клику вне модального окна
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    modal.style.display = 'block';

    // Получаем элементы
    const loader = document.getElementById('skin-preview-loader');
    const errorDiv = document.getElementById('skin-preview-error');
    const imgContainer = document.getElementById('skin-preview-image-container');
    const modalImg = document.getElementById('skin-preview-img');
    const infoDiv = document.getElementById('skin-preview-info');
    const modelSelector = document.getElementById('model-selector');

    // Сброс состояния
    loader.style.display = 'none';
    errorDiv.style.display = 'none';
    imgContainer.style.display = 'none';
    infoDiv.style.display = 'none';
    modelSelector.style.display = 'none';

    if (loading) {
        loader.style.display = 'block';
        return;
    }

    if (error) {
        errorDiv.textContent = error;
        errorDiv.style.display = 'block';
        return;
    }

    if (imgPath) {
        imgContainer.style.display = 'block';
        loader.style.display = 'block';
        modalImg.style.display = 'none';
        modalImg.src = imgPath;

        modalImg.onload = function() {
            loader.style.display = 'none';
            modalImg.style.display = 'block';
            
            // Показываем информацию о скине, если есть
            if (skinInfo && Object.keys(skinInfo).length > 0) {
                updateSkinInfo(skinInfo);
                infoDiv.style.display = 'block';
                
                // Показываем селектор моделей для несовместимых скинов
                if (skinInfo.is_compatible === false && itemId) {
                    setupModelSelector(itemId, isInventory, skinInfo);
                    modelSelector.style.display = 'block';
                }
            }
        };

        modalImg.onerror = function() {
            loader.style.display = 'none';
            errorDiv.textContent = 'Ошибка загрузки изображения';
            errorDiv.style.display = 'block';
        };
    }
}

function setupModelSelector(itemId, isInventory, currentSkinInfo) {
    const modelButtons = document.querySelectorAll('.model-btn');
    
    // Убираем предыдущие обработчики
    modelButtons.forEach(btn => {
        btn.replaceWith(btn.cloneNode(true));
    });
    
    // Добавляем новые обработчики
    const newModelButtons = document.querySelectorAll('.model-btn');
    newModelButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedModel = this.getAttribute('data-model');
            
            // Выделяем активную кнопку
            newModelButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Запрашиваем превью с выбранной моделью
            previewSkinWithModel(itemId, selectedModel, isInventory);
        });
    });
}

function previewSkinWithModel(itemId, model, isInventory = false) {
    const loader = document.getElementById('skin-preview-loader');
    const imgContainer = document.getElementById('skin-preview-image-container');
    const modalImg = document.getElementById('skin-preview-img');
    const errorDiv = document.getElementById('skin-preview-error');
    
    // Скрываем ошибки
    errorDiv.style.display = 'none';
    
    // Показываем загрузку
    loader.style.display = 'block';
    imgContainer.style.display = 'none';
    
    const requestType = isInventory ? 'skin_preview_inv' : 'skin_preview';
    
    fetch('/do/Issets', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=${requestType}&item=${encodeURIComponent(itemId)}&mode=force_model&force_model=${encodeURIComponent(model)}`
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success && data.preview) {
            modalImg.src = data.preview;
            modalImg.onload = function() {
                loader.style.display = 'none';
                imgContainer.style.display = 'block';
                
                // Обновляем информацию о модели
                const modelInfoDiv = document.getElementById('skin-model-info');
                if (modelInfoDiv) {
                    modelInfoDiv.innerHTML = `<span style="color:#17a2b8;">📋 Предпросмотр на модели ${model}</span>`;
                }
            };
        } else {
            loader.style.display = 'none';
            errorDiv.textContent = data.error || 'Ошибка загрузки модели';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        loader.style.display = 'none';
        errorDiv.textContent = 'Произошла ошибка';
        errorDiv.style.display = 'block';
    });
}

function updateSkinInfo(skinInfo) {
    const compatibilityDiv = document.getElementById('skin-compatibility');
    const sexInfoDiv = document.getElementById('skin-sex-info');
    const modelInfoDiv = document.getElementById('skin-model-info');

    // Информация о совместимости
    if (skinInfo.is_compatible !== undefined) {
        const compatStatus = skinInfo.is_compatible ? 
            '<span style="color:#28a745;">✓ Подходит вашему полу</span>' : 
            '<span style="color:#dc3545;">✗ Не подходит вашему полу</span>';
        compatibilityDiv.innerHTML = `<strong>Совместимость:</strong> ${compatStatus}`;
    }

    // Информация о поле
    if (skinInfo.skin_sex && skinInfo.user_sex) {
        const skinSexName = getSexDisplayName(skinInfo.skin_sex);
        const userSexName = getSexDisplayName(skinInfo.user_sex);
        sexInfoDiv.innerHTML = `<strong>Пол скина:</strong> ${skinSexName}<br><strong>Ваш пол:</strong> ${userSexName}`;
    }

    // Информация о модели
    if (skinInfo.used_base_model) {
        modelInfoDiv.innerHTML = '<span style="color:#ffc107;">📋 Показана ваша модель</span>';
    } else if (skinInfo.preview_mode && skinInfo.preview_mode !== 'force_model') {
        modelInfoDiv.innerHTML = `Режим: ${getPreviewModeDisplayName(skinInfo.preview_mode)}`;
    }
}

function getSexDisplayName(sex) {
    switch(sex) {
        case 'm': return 'Мужской ♂';
        case 'f': return 'Женский ♀';
        case 'all': return 'Универсальный ◎';
        default: return 'Неизвестно';
    }
}

function getPreviewModeDisplayName(mode) {
    switch(mode) {
        case 'auto': return 'Автоматический';
        case 'strict': return 'Строгий';
        case 'force_base': return 'Базовая модель';
        case 'force_model': return 'Выбранная модель';
        default: return mode;
    }
}
function usePlaneTicket(itemRowId, count = 1) {
    $.ajax({
        url: '/do/itemsAction.php',
        method: 'POST',
        data: {
            itemID: itemRowId,    // id строки из items_users!
            type: 'plane_ticket',
            count: count
        },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                alert(response.text);
                return;
            }
            alert(response.text);
            // Если был телепорт, можно обновить локацию или перезагрузить страницу
            if (response.teleported) {
                // location.reload(); // простой вариант
                // Либо вызвать свою функцию для обновления карты/локации:
                if (typeof updateMap === 'function') {
                    updateMap(response.new_location);
                } else {
                    location.reload();
                }
            }
        },
        error: function() {
            alert('Ошибка соединения с сервером!');
        }
    });
}

function closePanelModal(btn) {
    // Найти ближайший родительский .PanelModal и удалить его
    var panel = btn.closest('.PanelModal');
    if(panel) panel.remove();
}
console.log('user_group:', window.user_group, typeof window.user_group);
// notify.js — простой фронтенд для всех функций уведомлений
function notifyFilter(type, btn) {
    document.querySelectorAll('.notify-filter-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.notify-list .DivNotify').forEach(div=>{
        if(type==='all') div.style.display='';
        else if(type==='new') div.style.display=div.dataset.checked==='0'?'':'none';
        else div.style.display=(div.dataset.type===type)?'':'none';
    });
}
function notifySearch() {
    let val = document.getElementById('notifySearchInput').value.toLowerCase();
    document.querySelectorAll('.notify-list .DivNotify').forEach(div=>{
        div.style.display = div.innerText.toLowerCase().indexOf(val)===-1?'none':'';
    });
}
function notifyHideAll() {
    document.getElementById('notifyList').style.display='none';
    // Можно добавить кнопку "Показать все" для возврата
}
function notifyMarkAllRead() {
    fetch('/do/notifitcations', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'type=mark_all_read'
    }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload();
        else alert(d.error);
    });
}
function clearNotifications(){
  var $btn = $('#clearNotifications');
  if ($btn.prop('disabled')) return;

  $btn.prop('disabled', true).addClass('is-loading');

  $.post('/do/notifications', { type: 'clear_notifications' }, function(res){
    // jQuery уже парсит JSON, но на всякий случай…
    if (typeof res === 'string') {
      try { res = JSON.parse(res); } catch(_) { res = {}; }
    }

    if (res && res.success){
      // очистить список
      var list = document.querySelector('.LittleModal.notify-pop .notify-list') || document.getElementById('notifyList');
      if (list) list.innerHTML = '';

      // опционально показать пустую заглушку
      if (list){
        list.innerHTML = (
          '<div class="NotifyEmpty">' +
            '<div class="NotifyEmptyIcon"><i class="far fa-bell-slash"></i></div>' +
            '<div class="NotifyEmptyText">Нет уведомлений</div>' +
          '</div>'
        );
      }

      // обновить счётчики, если пришли
      if (res.counts){
        try {
          // пример: обновите ваши бейджи, если они есть
          $('.notify-filter-btn .notify-count').removeClass('active').text('0');
          // общий бейдж (если у вас есть глобальная «точка» в шапке):
          if (typeof window.updateNotifyBadge === 'function'){
            window.updateNotifyBadge(res.counts.total_unread || 0);
          }
        } catch(_){}
      }
    } else {
      console.warn('clearNotifications: server returned error', res && res.error);
    }
  }, 'json')
  .fail(function(xhr){
    console.error('clearNotifications: AJAX failed', xhr && xhr.status);
  })
  .always(function(){
    $btn.prop('disabled', false).removeClass('is-loading');
  });
}


document.addEventListener("DOMContentLoaded", function() {
  const form = document.getElementById('upload-location-form');
  const msgBox = document.getElementById('location-upload-msg');
  if (!form || !msgBox) return;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    msgBox.innerHTML = '<span style="color:#888;">Загрузка...</span>';
    const formData = new FormData(form);

    fetch(form.action, {
      method: "POST",
      body: formData
    })
    .then(resp => resp.json())
    .then(data => {
      // Проверяем, что загрузка успешна и есть ссылка на картинку
      if (data.error) {
        msgBox.innerHTML = '<span style="color:crimson;">' + (data.msg || 'Ошибка!') + '</span>';
        return;
      }
      msgBox.innerHTML = '<span style="color:seagreen;">' + (data.msg || 'Успех!') + '</span>';
      // Проверяем, что есть путь к картинке
      if (data.data && data.data.src) {
        // Выводим картинку
        msgBox.innerHTML += `<div><img src="${data.data.src}?t=${Date.now()}" style="max-width:220px;max-height:220px;border:1px solid #aaa;border-radius:5px;margin:10px 0;"></div>`;
      }
    })
    .catch(err => {
      msgBox.innerHTML = '<span style="color:crimson;">Ошибка: ' + err + '</span>';
    });
  });
});

/**
 * Файл: js/updateLocation.js
 * Функция для регулярного и событийного обновления состояния локации игрока на основе /do/updateLocation.php
 * Подключать после jQuery.
 */

function bindBuffsDropdown() {
    // Снимаем все предыдущие обработчики, чтобы не было дублей
    $(document).off('.BuffsDropdown');

    // Открыть/закрыть по клику на кнопку
    $(document).on('click.BuffsDropdown', '#BuffsDropdownBtn', function(e) {
        e.stopPropagation();
        $('#BuffsDropdownList').toggleClass('open');
    });

    // Закрыть по клику вне списка
    $(document).on('click.BuffsDropdown', function() {
        $('#BuffsDropdownList').removeClass('open');
    });

    // Не закрывать при клике внутри выпадашки
    $(document).on('click.BuffsDropdown', '#BuffsDropdownList', function(e) {
        e.stopPropagation();
    });
}

// Вызовите эту функцию после вставки блока .BuffsDropdown в DOM (например, после render или при инициализации)
bindBuffsDropdown();
function TrainerCollection(action, value) {
    window._trainerCollection = window._trainerCollection || {
        filterStatus: 'all',
        searchQuery: ''
    };

    if (action === 'filter') {
        window._trainerCollection.filterStatus = value;
        TrainerCollection('update');
        document.querySelectorAll('.TrainerFilterBtn').forEach(btn => btn.classList.remove('active'));
        var btn = document.getElementById('filter' + value.charAt(0).toUpperCase() + value.slice(1));
        if (btn) btn.classList.add('active');
    }
    else if (action === 'search') {
        window._trainerCollection.searchQuery = value;
        TrainerCollection('update');
    }
    else if (action === 'update') {
        $.ajax({
            url: "/do/Pokedex",
            type: "POST",
            data: {
                setTab: 'trainercollection',
                filter: window._trainerCollection.filterStatus,
                search: window._trainerCollection.searchQuery
            },
            success: function(response) {
                var bar = document.querySelector('.trainer-pokedex-bar');
                if(bar) bar.outerHTML = response.html;
            }
        });
    }
}

// При первом открытии коллекции — активировать "Все"
document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById('filterAll')) {
        TrainerCollection('filter', 'all');
        document.getElementById('trainer-search').value = '';
    }
});
function showTrainerCollectionSwitch(filter = 'all', search = '') {
    // Удаляем старое окно, если оно есть
    document.querySelectorAll('.NintendoDexModal').forEach(e => e.remove());

    // Основное окно
    let modal = document.createElement('div');
    modal.className = 'NintendoDexModal';

    // Больше нет NintendoDexBG!

    // Контент внутри окна
    let content = document.createElement('div');
    content.className = 'NintendoDexContent';

    // Кнопка закрытия
    let closeBtn = document.createElement('button');
    closeBtn.className = 'NintendoDexCloseBtn';
    closeBtn.innerHTML = '&times;';
    closeBtn.onclick = function () {
        document.body.removeChild(modal);
    };
    content.appendChild(closeBtn);

    // Функциональные кнопки (фильтры)
    let funcBtns = document.createElement('div');
    funcBtns.className = 'NintendoDexFuncs';
    funcBtns.innerHTML = `
        <button class="NintendoDexFuncBtn" onclick="TrainerCollectionSwitchFilter('all')">Все</button>
        <button class="NintendoDexFuncBtn" onclick="TrainerCollectionSwitchFilter('caught')">Пойманные</button>
        <button class="NintendoDexFuncBtn" onclick="TrainerCollectionSwitchFilter('shiny')">Шайни</button>
        <button class="NintendoDexFuncBtn" onclick="TrainerCollectionSwitchFilter('uncaught')">Не пойманные</button>
    `;
    content.appendChild(funcBtns);

    // Список покемонов
    let listArea = document.createElement('div');
    listArea.className = 'NintendoDexListArea';

    // AJAX для получения коллекции
    $.ajax({
        url: "/do/trainercollection.php",
        type: "POST",
        data: { filter: filter, search: search },
        success: function(response) {
            let temp = document.createElement('div');
            temp.innerHTML = response;

            // Преобразуем старые карточки в новые
            listArea.innerHTML = '';
            temp.querySelectorAll('.trainer-pokebar-item').forEach(function(card) {
                let id = card.querySelector('.trainer-pokid')?.textContent || '';
                let name = card.querySelector('.trainer-pokename')?.textContent || '';
                let img = card.querySelector('img')?.src || '';
                let caught = card.classList.contains('caught');
                let shiny = card.classList.contains('shiny');

                let pk = document.createElement('div');
                pk.className = 'nintendo-pokebar-item';
                if (caught) pk.classList.add('caught');
                if (shiny) pk.classList.add('shiny');

                pk.onclick = function() { showDexModal(id); };

                let pkimg = document.createElement('img');
                pkimg.src = img;
                pkimg.className = 'nintendo-pokebar-img';
                pk.appendChild(pkimg);

                let info = document.createElement('div');
                info.className = 'nintendo-pokebar-info';
                let pkname = document.createElement('div');
                pkname.className = 'nintendo-pokebar-name';
                pkname.textContent = name;
                let pkid = document.createElement('div');
                pkid.className = 'nintendo-pokebar-id';
                pkid.textContent = id;
                info.appendChild(pkname);
                info.appendChild(pkid);
                pk.appendChild(info);

                // Статусы
                if (caught) {
                    let st = document.createElement('div');
                    st.className = 'nintendo-pokstatus caught';
                    st.title = "Пойман";
                    st.textContent = '✓';
                    pk.appendChild(st);
                }
                if (shiny) {
                    let st = document.createElement('div');
                    st.className = 'nintendo-pokstatus shiny';
                    st.title = "Шайни";
                    st.textContent = '★ Shiny';
                    pk.appendChild(st);
                }

                listArea.appendChild(pk);
            });

            if (!listArea.children.length) {
                listArea.innerHTML = '<div class="no-pokemons">По вашему запросу ничего не найдено.</div>';
            }
        }
    });

    content.appendChild(listArea);
    modal.appendChild(content);
    document.body.appendChild(modal);

    // Для фильтров
    window.currentTrainerFilter = filter;
}
function TrainerCollectionSwitchFilter(filter, search = '') {
    window.currentTrainerFilter = filter;
    showTrainerCollectionSwitch(filter, search);
}
// Универсальное экранирование
function escGift(s){
  if (s === null || s === undefined) return '';
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

// Рендер списка в .Gift
function showGiftCards(receivedGifts) {
  var $c = $('.Gift');
  $c.empty();

  if (!Array.isArray(receivedGifts) || !receivedGifts.length) {
    $c.html('<div class="gift-empty">Нет полученных подарков</div>');
    return;
  }

  var html = '';
  receivedGifts.forEach(function(g){
    // ВАЖНО: g.id должен быть ID строки в gifts
    if (!g.id) console.warn('Gift item без id (нужен ID из таблицы gifts):', g);
    html +=
      '<div class="gift-card" data-gift-id="'+ escGift(g.id) +'" tabindex="0" role="button">' +
        '<img class="gift-img" src="'+ escGift(g.img || '/images/giftshop/default.png') +'" alt="Подарок">' +
        '<div class="gift-title">'+ escGift(g.title || g.name || 'Без названия') +'</div>' +
        '<div class="gift-from">От: '+ escGift(g.from_user_login || g.from || '—') +'</div>' +
        (g.message ? '<div class="gift-message-inline" title="'+ escGift(g.message) +'">'+ escGift(g.message.length>28? g.message.slice(0,25)+'…':g.message) +'</div>':'')+
        (g.date ? '<div class="gift-date">'+ escGift(g.date) +'</div>' : '')+
      '</div>';
  });

  $c.html(html);
}

// Делегирование клика/клавиш по карточке
$(document).off('click.showGiftCard').on('click.showGiftCard', '.Gift .gift-card', function(){
  var id = $(this).data('gift-id');
  showGiftInfo(id);
});
$(document).off('keydown.showGiftCard').on('keydown.showGiftCard', '.Gift .gift-card', function(e){
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    showGiftInfo($(this).data('gift-id'));
  }
});

// Модалка (упрощённо оставляем ваш стиль)
function showGiftInfo(giftId) {
  giftId = parseInt(giftId,10);
  if (!giftId) {
    alert('Некорректный ID подарка');
    return;
  }

  $('#mainTooltip').remove();
  var loading =
    '<div id="mainTooltip" class="tooltip" style="left:50%;top:50%;transform:translate(-50%,-60%);position:fixed;z-index:12000;min-width:220px;cursor:default;">' +
      '<div style="background:#f8f7ff;padding:16px 18px 20px;border-radius:12px;max-width:240px;box-shadow:0 4px 18px rgba(153,95,196,0.18);position:relative;font-family:inherit;">' +
        '<span style="position:absolute;top:7px;right:13px;cursor:pointer;font-size:17px;color:#995fc4;" onclick="$(\'#mainTooltip\').remove()">×</span>' +
        '<div style="text-align:center;font-size:13px;color:#653fa3;">Загрузка...</div>' +
      '</div>' +
    '</div>';
  $('body').append(loading);

  $.getJSON('/do/trainers.php', { action:'giftinfo', id: giftId })
    .done(function(info){
      if (!info || info.error) {
        renderGiftInfoError(info && info.error ? info.error : 'Нет данных');
        return;
      }
      renderGiftInfoModal(info);
    })
    .fail(function(a,b,c){
      console.error('giftinfo fail', b, c);
      renderGiftInfoError('Ошибка запроса ('+b+')');
    });
}

function renderGiftInfoError(msg){
  var safe = escGift(msg);
  $('#mainTooltip .tooltip-body').remove();
  $('#mainTooltip').find('div:first').append(
    '<div class="tooltip-body" style="margin-top:10px;text-align:center;font-size:12.5px;color:#b14b4b;font-weight:600;">'+safe+'</div>'
  );
}

function renderGiftInfoModal(info){
  var img   = escGift(info.img || '/images/giftshop/default.png');
  var title = escGift(info.title || 'Без названия');
  var from  = escGift(info.from_user_login || 'Неизвестно');
  var msg   = info.message ? escGift(info.message) : '';
  var desc  = info.description ? escGift(info.description) : '';
  var price = (info.price !== null && info.price !== undefined && info.price !== '') ? escGift(info.price) : '—';
  var date  = info.date ? escGift(info.date) : '';
  var st    = info.status ? escGift(info.status) : '';

  var html =
    '<div style="background:#f8f7ff;padding:14px 16px 16px;border-radius:12px;max-width:260px;box-shadow:0 4px 18px rgba(153,95,196,0.18);position:relative;font-family:inherit;">' +
      '<span style="position:absolute;top:7px;right:13px;cursor:pointer;font-size:17px;color:#995fc4;" onclick="$(\'#mainTooltip\').remove()">×</span>' +
      '<img src="'+img+'" alt="'+title+'" style="width:64px;height:64px;border-radius:10px;background:#edeaf3;margin:0 auto 8px;display:block;object-fit:contain;">' +
      '<div style="font-size:15px;font-weight:600;color:#653fa3;text-align:center;margin-bottom:6px;">'+title+'</div>' +
      '<div style="font-size:12.5px;color:#8e6eb4;text-align:center;margin-bottom:4px;">От: '+from+'</div>' +
      (msg ? '<div style="font-size:12px;color:#995fc4;text-align:center;margin-bottom:5px;">Сообщение: '+msg+'</div>' : '') +
      (desc? '<div style="font-size:12px;color:#555;text-align:center;margin-bottom:7px;">'+desc+'</div>' : '') +
      '<div style="font-size:12px;color:#b08ae6;text-align:center;margin-bottom:4px;">Цена: '+price+'</div>' +
      (st ? '<div style="font-size:11px;color:#aaa;text-align:center;margin-bottom:4px;">Статус: '+st+'</div>' : '') +
      (date? '<div style="font-size:11px;color:#aaa;text-align:center;">'+date+'</div>' : '') +
    '</div>';

  $('#mainTooltip').html(html);
}
// Развернуть/свернуть секции
document.addEventListener('click', function(e){
  var t = e.target.closest('.gr-toggle');
  if (!t) return;
  var action = t.getAttribute('data-action');
  var details = document.querySelectorAll('#game-rules details');
  if (action === 'expand')  details.forEach(function(d){ d.open = true;  });
  if (action === 'collapse') details.forEach(function(d){ d.open = false; });
});

// Плавная прокрутка к секциям внутри блока
document.querySelectorAll('#game-rules .gr-nav a[href^="#"]').forEach(function(a){
  a.addEventListener('click', function(e){
    e.preventDefault();
    var id = a.getAttribute('href').slice(1);
    var target = document.getElementById(id);
    if (!target) return;
    if (target.tagName.toLowerCase() === 'details') target.open = true;
    // скроллим контейнер .GameRules, а не всю страницу
    var container = document.getElementById('game-rules');
    var top = target.offsetTop - container.offsetTop - 6;
    container.scrollTo({ top: top, behavior: 'smooth' });
  });
});
// ===== Мировые боссы (Полная финальная версия) =====
function openWorldBossesModal() {
    console.log('Opening World Bosses Modal...');
    
    // Показываем загрузку
    if ($('.loadWorld').length) {
        $('.loadWorld').show();
    }
    
    // Загружаем список боссов
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'POST',
        data: { action: 'list' },
        dataType: 'json',
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            if (response.success) {
                if (response.bosses && response.bosses.length > 0) {
                    showWorldBossesModal(response.bosses, null, response);
                } else {
                    showWorldBossesModal([], null, response);
                }
            } else {
                showWorldBossesModal(null, response.error || 'Ошибка загрузки данных о боссах');
            }
        },
        error: function(xhr, status, error) {
            console.error('World Bosses AJAX Error:', status, error);
            
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            showWorldBossesModal(null, 'Ошибка соединения с сервером');
        }
    });
}

// Создание тестового босса
function createTestBoss() {
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'POST',
        data: { action: 'create_test' },
        dataType: 'json',
        beforeSend: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').show();
            }
        },
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            if (response.success) {
                if (window.Game?.notifications?.main) {
                    Game.notifications.main('🔥 ' + response.message, 'plus');
                } else {
                    alert('✅ ' + response.message);
                }
                
                // Обновляем список после создания
                setTimeout(() => {
                    openWorldBossesModal();
                }, 500);
            } else {
                if (window.Game?.notifications?.main) {
                    Game.notifications.main('Ошибка создания босса: ' + response.error, 'error');
                } else {
                    alert('❌ Ошибка создания босса: ' + response.error);
                }
            }
        },
        error: function(xhr, status, error) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            alert('❌ Ошибка соединения при создании босса');
        }
    });
}

// Очистка просроченных боссов
function cleanupBosses() {
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'POST',
        data: { action: 'cleanup' },
        dataType: 'json',
        beforeSend: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').show();
            }
        },
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            if (response.success) {
                if (window.Game?.notifications?.main) {
                    Game.notifications.main('🧹 ' + response.message, 'plus');
                } else {
                    alert('✅ ' + response.message);
                }
                
                // Обновляем список после очистки
                setTimeout(() => {
                    openWorldBossesModal();
                }, 500);
            } else {
                alert('❌ Ошибка очистки: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            alert('❌ Ошибка соединения при очистке');
        }
    });
}

// Проверка участия пользователя в рейде
function checkUserParticipation(instanceId, callback) {
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'GET',
        data: {
            action: 'instance',
            instance_id: instanceId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && typeof callback === 'function') {
                callback(response.user_participating || false);
            }
        },
        error: function() {
            if (typeof callback === 'function') {
                callback(false);
            }
        }
    });
}

// Модальное окно со списком боссов
function showWorldBossesModal(bosses, errorMessage, fullResponse) {
    let modalHtml = `
        <div class="LittleModal" style="max-width: 800px; z-index: 9999;">
            <div class="LittleModalTitle">
                <span>🔥 Мировые боссы</span>
                <span class="LittleModalClose" onclick="$('.LittleModal').remove();">×</span>
            </div>
            <div class="LittleModalBody" style="max-height: 600px; overflow-y: auto; padding: 20px;">
    `;
    
    if (errorMessage) {
        modalHtml += `
            <div style="text-align: center; padding: 50px 20px;">
                <div style="font-size: 64px; margin-bottom: 20px;">😞</div>
                <h3 style="margin: 0 0 15px 0; color: #dc3545; font-size: 18px;">${errorMessage}</h3>
                <p style="color: #6c757d; margin: 0 0 20px 0;">Проверьте подключение к интернету или попробуйте позже</p>
                <button onclick="openWorldBossesModal()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px;">
                    🔄 Попробовать снова
                </button>
            </div>
        `;
    } else if (!bosses || bosses.length === 0) {
        modalHtml += `
            <div style="text-align: center; padding: 50px 20px;">
                <div style="font-size: 64px; margin-bottom: 20px;">😴</div>
                <h3 style="margin: 0 0 15px 0; color: #6c757d; font-size: 18px;">Нет активных боссов</h3>
                <p style="color: #6c757d; margin: 0 0 25px 0;">Мировые боссы появляются по расписанию.<br>Следите за уведомлениями в игре!</p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <button onclick="createTestBoss()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px;">
                        🆕 Создать тестового босса
                    </button>
                    <button onclick="openWorldBossesModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px;">
                        🔄 Обновить список
                    </button>
                    <button onclick="cleanupBosses()" style="background: #ffc107; color: black; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px;">
                        🧹 Очистить просроченных
                    </button>
                </div>
            </div>
        `;
    } else {
        modalHtml += `
            <div style="margin-bottom: 20px; text-align: center;">
                <h4 style="margin: 0 0 10px 0; color: #495057;">Найдено активных боссов: ${bosses.length}</h4>
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 15px;">
                    <button onclick="openWorldBossesModal()" style="background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                        🔄 Обновить
                    </button>
                    <button onclick="createTestBoss()" style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                        🆕 Создать босса
                    </button>
                    <button onclick="cleanupBosses()" style="background: #ffc107; color: black; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">
                        🧹 Очистка
                    </button>
                </div>
            </div>
        `;
        
        modalHtml += `<div style="display: grid; gap: 20px;">`;
        
        bosses.forEach(function(boss) {
            const hpPercent = boss.hp_max > 0 ? Math.round((boss.hp_current / boss.hp_max) * 100) : 0;
            
            // Обработка времени
            let timeLeftFormatted = 'Неизвестно';
            if (boss.expire_time) {
                try {
                    const expireDate = new Date(boss.expire_time);
                    if (!isNaN(expireDate.getTime())) {
                        const timeLeftMs = expireDate.getTime() - Date.now();
                        timeLeftFormatted = formatTimeLeft(timeLeftMs);
                    }
                } catch (e) {
                    timeLeftFormatted = 'Ошибка времени';
                }
            }
            
            const hpColor = hpPercent > 60 ? "#28a745" : (hpPercent > 30 ? "#ffc107" : "#dc3545");
            const statusIcon = boss.status === "defeated" ? "💀" : (boss.status === "active" ? "⚔️" : "❓");
            
            // Проверяем доступность по уровню
            const userLevel = fullResponse ? fullResponse.user_level : 100;
            const isAvailable = userLevel >= (boss.level_min || 1) && userLevel <= (boss.level_max || 100);
            const isInUserLocation = boss.location_id == (fullResponse?.user_location || 1);
            
            let cardBorder = '#dee2e6';
            let cardBg = '#ffffff';
            
            if (!isAvailable) {
                cardBorder = '#ffc107';
                cardBg = '#fffbf0';
            } else if (isInUserLocation) {
                cardBorder = '#28a745';
                cardBg = '#f8fff8';
            }
            
            modalHtml += `
                <div id="boss-card-${boss.id}" style="border: 2px solid ${cardBorder}; border-radius: 12px; padding: 20px; background: ${cardBg}; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #212529; font-size: 18px; font-weight: bold;">
                                ${statusIcon} ${boss.name || 'Неизвестный босс'}
                                ${!isAvailable ? ' ⚠️' : ''}
                                ${isInUserLocation ? ' 📍' : ''}
                            </h4>
                            <div style="font-size: 12px; color: #6c757d;">ID: ${boss.id} • Создан: ${formatDateTime(boss.spawn_time)}</div>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="background: ${isInUserLocation ? '#d4edda' : '#f8f9fa'}; padding: 4px 10px; border-radius: 15px; font-size: 12px; color: #495057; font-weight: 500;">
                                📍 ${boss.location_name || `Локация ${boss.location_id}`}
                            </span>
                            <span style="background: ${isAvailable ? '#e3f2fd' : '#fff3cd'}; padding: 4px 10px; border-radius: 15px; font-size: 12px; color: ${isAvailable ? '#1976d2' : '#856404'}; font-weight: 500;">
                                ⭐ ${boss.level_min}-${boss.level_max}
                            </span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                            <span style="color: #6c757d; font-weight: 500;">❤️ Здоровье</span>
                            <span style="font-weight: bold; color: ${hpColor};">${numberWithSpaces(boss.hp_current)} / ${numberWithSpaces(boss.hp_max)} (${hpPercent}%)</span>
                        </div>
                        <div style="background: #e9ecef; border-radius: 12px; height: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="background: linear-gradient(90deg, ${hpColor}, ${hpColor}dd); height: 100%; width: ${hpPercent}%; transition: width 0.5s ease; border-radius: 12px;"></div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; color: #6c757d; margin-bottom: 2px;">👥 Участники</div>
                            <div style="font-weight: bold; color: #495057;">${boss.total_participants || 0} / ${boss.max_participants || 30}</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; color: #6c757d; margin-bottom: 2px;">⏰ Осталось времени</div>
                            <div style="font-weight: bold; color: #495057;">${timeLeftFormatted}</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; color: #6c757d; margin-bottom: 2px;">💥 Общий урон</div>
                            <div style="font-weight: bold; color: #495057;">${numberWithSpaces(boss.total_damage_dealt || 0)}</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; color: #6c757d; margin-bottom: 2px;">🎯 Статус</div>
                            <div style="font-weight: bold; color: #495057;">${getStatusText(boss.status)}</div>
                        </div>
                    </div>
                    
                    <div id="boss-actions-${boss.id}">
                        ${getBossActionButtons(boss, isAvailable, isInUserLocation, userLevel)}
                    </div>
                </div>
            `;
        });
        
        modalHtml += `</div>`;
    }
    
    modalHtml += `
            </div>
        </div>
    `;
    
    // Удаляем старые модалки и добавляем новую
    $('.LittleModal').remove();
    $('body').append(modalHtml);
    
    // Проверяем участие пользователя в каждом рейде
    if (bosses && bosses.length > 0) {
        bosses.forEach(function(boss) {
            checkUserParticipation(boss.id, function(isParticipating) {
                updateBossActionButtons(boss.id, boss, isParticipating, fullResponse);
            });
        });
    }
}

// Генерация кнопок действий для босса
function getBossActionButtons(boss, isAvailable, isInUserLocation, userLevel) {
    if (boss.status !== "active") {
        return `
            <div style="text-align: center; padding: 10px;">
                <div style="background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; border: 1px solid #f5c6cb; font-size: 14px;">
                    ${boss.status === 'defeated' ? '💀 Босс побеждён' : '⏰ Рейд недоступен'}
                </div>
            </div>
        `;
    }
    
    if (!isAvailable) {
        return `
            <div style="text-align: center; padding: 10px;">
                <div style="background: #fff3cd; color: #856404; padding: 12px 20px; border-radius: 8px; border: 1px solid #ffeaa7; font-size: 14px;">
                    ⚠️ Недоступен для вашего уровня ${userLevel} (требуется ${boss.level_min}-${boss.level_max})
                </div>
            </div>
        `;
    }
    
    // По умолчанию показываем кнопку присоединения (будет обновлена после проверки участия)
    return `
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <button onclick="joinWorldBoss(${boss.id})" 
                    style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(0,123,255,0.3); transition: all 0.3s;">
                ⚔️ Присоединиться к рейду
            </button>
            ${!isInUserLocation ? `
                <button onclick="teleportToBoss(${boss.location_id})"
                        style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(40,167,69,0.3); transition: all 0.3s;">
                    🌀 Телепорт в локацию
                </button>
            ` : ''}
            <button onclick="viewBossDetails(${boss.id})"
                    style="background: linear-gradient(135deg, #6c757d, #495057); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(108,117,125,0.3); transition: all 0.3s;">
                📊 Подробности
            </button>
        </div>
    `;
}

// Обновление кнопок действий после проверки участия
function updateBossActionButtons(bossId, boss, isParticipating, fullResponse) {
    const actionsContainer = document.getElementById(`boss-actions-${bossId}`);
    if (!actionsContainer) return;
    
    const userLevel = fullResponse ? fullResponse.user_level : 100;
    const isAvailable = userLevel >= (boss.level_min || 1) && userLevel <= (boss.level_max || 100);
    const isInUserLocation = boss.location_id == (fullResponse?.user_location || 1);
    
    if (boss.status !== "active" || !isAvailable) {
        return; // Оставляем как есть
    }
    
    let buttonsHtml = `<div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">`;
    
    if (isParticipating) {
        // Пользователь участвует в рейде
        buttonsHtml += `
            <button onclick="startBossBattle(${boss.id})" 
                    style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(220,53,69,0.3); transition: all 0.3s;">
                ⚔️ СРАЖАТЬСЯ С БОССОМ
            </button>
            <button onclick="leaveBossRaid(${boss.id})" 
                    style="background: linear-gradient(135deg, #6c757d, #495057); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                🚪 Покинуть рейд
            </button>
        `;
    } else {
        // Пользователь не участвует в рейде
        buttonsHtml += `
            <button onclick="joinWorldBoss(${boss.id})" 
                    style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(0,123,255,0.3); transition: all 0.3s;">
                ⚔️ Присоединиться к рейду
            </button>
        `;
    }
    
    if (!isInUserLocation) {
        buttonsHtml += `
            <button onclick="teleportToBoss(${boss.location_id})"
                    style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(40,167,69,0.3); transition: all 0.3s;">
                🌀 Телепорт в локацию
            </button>
        `;
    }
    
    buttonsHtml += `
        <button onclick="viewBossDetails(${boss.id})"
                style="background: linear-gradient(135deg, #6c757d, #495057); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(108,117,125,0.3); transition: all 0.3s;">
            📊 Подробности
        </button>
    </div>`;
    
    actionsContainer.innerHTML = buttonsHtml;
}

// Присоединение к рейду
function joinWorldBoss(instanceId) {
    if (!confirm('🔥 Присоединиться к рейду против мирового босса?\n\nВы будете переведены в статус участника рейда.')) return;
    
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'POST',
        data: {
            action: 'join',
            instance_id: instanceId
        },
        dataType: 'json',
        beforeSend: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').show();
            }
        },
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            try {
                if (typeof response === 'string') {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                }
                
                if (response.success) {
                    if (window.Game?.notifications?.main) {
                        Game.notifications.main('⚔️ ' + response.message, 'plus');
                    } else {
                        alert('✅ ' + response.message);
                    }
                    
                    // Обновляем кнопки для этого босса
                    setTimeout(() => {
                        const bossCard = document.getElementById(`boss-card-${instanceId}`);
                        if (bossCard) {
                            // Находим босса в данных и обновляем кнопки
                            $.ajax({
                                url: '/do/WorldBossTest.php',
                                type: 'GET',
                                data: { action: 'instance', instance_id: instanceId },
                                dataType: 'json',
                                success: function(instanceData) {
                                    if (instanceData.success) {
                                        // Перестраиваем кнопки для участника рейда
                                        const actionsContainer = document.getElementById(`boss-actions-${instanceId}`);
                                        if (actionsContainer) {
                                            actionsContainer.innerHTML = `
                                                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                                    <button onclick="startBossBattle(${instanceId})" 
                                                            style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(220,53,69,0.3); transition: all 0.3s;">
                                                        ⚔️ СРАЖАТЬСЯ С БОССОМ
                                                    </button>
                                                    <button onclick="leaveBossRaid(${instanceId})" 
                                                            style="background: linear-gradient(135deg, #6c757d, #495057); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                                        🚪 Покинуть рейд
                                                    </button>
                                                    <button onclick="viewBossDetails(${instanceId})"
                                                            style="background: linear-gradient(135deg, #6c757d, #495057); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; box-shadow: 0 2px 8px rgba(108,117,125,0.3); transition: all 0.3s;">
                                                        📊 Подробности
                                                    </button>
                                                </div>
                                            `;
                                        }
                                    }
                                }
                            });
                        }
                    }, 500);
                    
                } else {
                    if (window.Game?.notifications?.main) {
                        Game.notifications.main('❌ ' + response.error, 'error');
                    } else {
                        alert('❌ ' + response.error);
                    }
                }
            } catch (e) {
                console.error('Error parsing join response:', e);
                alert('❌ Ошибка обработки ответа сервера');
            }
        },
        error: function(xhr, status, error) {
            console.error('Join AJAX Error:', status, error);
            
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            alert('❌ Ошибка соединения с сервером');
        }
    });
}

// Начать бой с боссом
function startBossBattle(instanceId) {
    if (!confirm('🔥 Начать бой с мировым боссом?\n\nВы войдёте в боевой режим.')) return;
    
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'POST',
        data: {
            action: 'start_battle',
            instance_id: instanceId
        },
        dataType: 'json',
        beforeSend: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').show();
            }
        },
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            if (response.success) {
                if (window.Game?.notifications?.main) {
                    Game.notifications.main('⚔️ ' + response.message, 'plus');
                }
                
                $('.LittleModal').remove();
                
                // Переходим в бой
                if (response.battle_url) {
                    setTimeout(() => {
                        window.location.href = response.battle_url;
                    }, 1000);
                } else if (response.battle_id) {
                    setTimeout(() => {
                        window.location.href = '/battle?id=' + response.battle_id;
                    }, 1000);
                } else {
                    alert('🎉 Бой начался! ID: ' + (response.battle_id || 'неизвестно'));
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            } else {
                alert('❌ ' + response.error);
            }
        },
        error: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            alert('❌ Ошибка запуска боя');
        }
    });
}

// Покинуть рейд
function leaveBossRaid(instanceId) {
    if (!confirm('🚪 Покинуть рейд против мирового босса?')) return;
    
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'POST',
        data: {
            action: 'leave',
            instance_id: instanceId
        },
        dataType: 'json',
        beforeSend: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').show();
            }
        },
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            if (response.success) {
                if (window.Game?.notifications?.main) {
                    Game.notifications.main('🚪 ' + response.message, 'plus');
                }
                
                // Обновляем кнопки
                setTimeout(() => {
                    openWorldBossesModal();
                }, 500);
            } else {
                alert('❌ ' + response.error);
            }
        },
        error: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            alert('❌ Ошибка при покидании рейда');
        }
    });
}

// Телепорт к боссу
function teleportToBoss(locationId) {
    if (!confirm(`🌀 Телепортироваться в локацию ${locationId}?\n\nЭто может стоить энергии или денег.`)) return;
    
    $.ajax({
        url: '/do/goLocation.php',
        type: 'POST',
        data: {
            location_id: locationId
        },
        beforeSend: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').show();
            }
        },
        success: function(response) {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            
            try {
                if (typeof response === 'string') {
                    response = (typeof response === 'string') ? JSON.parse(response) : response;
                }
                
                if (response.success || !response.error) {
                    if (window.Game?.notifications?.main) {
                        Game.notifications.main('🌀 Телепортация выполнена успешно!', 'plus');
                    }
                    
                    $('.LittleModal').remove();
                    
                    setTimeout(function() {
                        if (typeof updateLocation === 'function') {
                            updateLocation();
                        } else {
                            location.reload();
                        }
                    }, 1000);
                } else {
                    if (window.Game?.notifications?.main) {
                        Game.notifications.main('❌ ' + (response.error || 'Ошибка телепортации'), 'error');
                    } else {
                        alert('❌ ' + (response.error || 'Ошибка телепортации'));
                    }
                }
            } catch (e) {
                console.error('Error parsing teleport response:', e);
                alert('❌ Ошибка обработки ответа телепортации');
            }
        },
        error: function() {
            if ($('.loadWorld').length) {
                $('.loadWorld').hide();
            }
            alert('❌ Ошибка соединения при телепортации');
        }
    });
}

// Просмотр подробностей босса
function viewBossDetails(instanceId) {
    $.ajax({
        url: '/do/WorldBossTest.php',
        type: 'GET',
        data: {
            action: 'instance',
            instance_id: instanceId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const instance = response.instance;
                const participants = response.participants || [];
                
                let detailsHtml = `
                    <div class="LittleModal" style="max-width: 600px; z-index: 10000;">
                        <div class="LittleModalTitle">
                            <span>📊 ${instance.name} - Подробности</span>
                            <span class="LittleModalClose" onclick="$('.LittleModal').last().remove();">×</span>
                        </div>
                        <div class="LittleModalBody" style="max-height: 500px; overflow-y: auto; padding: 20px;">
                            <div style="margin-bottom: 20px;">
                                <h4>Информация о рейде</h4>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                    <p><strong>Название:</strong> ${instance.name}</p>
                                    <p><strong>Локация:</strong> ${instance.location_id}</p>
                                    <p><strong>Здоровье:</strong> ${numberWithSpaces(instance.hp_current)} / ${numberWithSpaces(instance.hp_max)}</p>
                                    <p><strong>Участников:</strong> ${instance.total_participants} / ${instance.max_participants}</p>
                                    <p><strong>Общий урон:</strong> ${numberWithSpaces(instance.total_damage_dealt || 0)}</p>
                                    <p><strong>Создан:</strong> ${formatDateTime(instance.spawn_time)}</p>
                                    <p><strong>Истекает:</strong> ${formatDateTime(instance.expire_time)}</p>
                                </div>
                            </div>
                `;
                
                if (participants.length > 0) {
                    detailsHtml += `
                        <div>
                            <h4>Участники рейда (${participants.length})</h4>
                            <div style="max-height: 200px; overflow-y: auto;">
                    `;
                    
                    participants.forEach((participant, index) => {
                        detailsHtml += `
                            <div style="display: flex; justify-content: space-between; padding: 8px; background: ${index % 2 === 0 ? '#f8f9fa' : 'white'}; border-radius: 4px; margin-bottom: 2px;">
                                <span>${participant.login} ${participant.user_id == response.current_user_id ? '(Вы)' : ''}</span>
                                <span>Урон: ${numberWithSpaces(participant.damage_dealt || 0)}</span>
                            </div>
                        `;
                    });
                    
                    detailsHtml += `
                            </div>
                        </div>
                    `;
                } else {
                    detailsHtml += `
                        <div style="text-align: center; padding: 20px; color: #6c757d;">
                            <p>Пока нет участников в рейде</p>
                        </div>
                    `;
                }
                
                detailsHtml += `
                        </div>
                    </div>
                `;
                
                $('body').append(detailsHtml);
            } else {
                alert('❌ Ошибка получения подробностей: ' + response.error);
            }
        },
        error: function() {
            alert('❌ Ошибка соединения при получении подробностей');
        }
    });
}

// Утилитарные функции
function numberWithSpaces(num) {
    if (!num && num !== 0) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

function formatTimeLeft(milliseconds) {
    if (milliseconds <= 0) return '⏰ Истёк';
    
    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) {
        return `${days}д ${hours % 24}ч`;
    } else if (hours > 0) {
        return `${hours}ч ${minutes % 60}м`;
    } else if (minutes > 0) {
        return `${minutes}м ${seconds % 60}с`;
    } else {
        return `${seconds}с`;
    }
}

function formatDateTime(dateStr) {
    if (!dateStr) return 'Неизвестно';
    try {
        const date = new Date(dateStr);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return 'Ошибка даты';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'active': return 'Активен';
        case 'defeated': return 'Побеждён';
        case 'expired': return 'Истёк';
        case 'spawning': return 'Появляется';
        default: return 'Неизвестно';
    }
}

console.log('🔥 World Bosses System Loaded Successfully - Full Version');

/* ===========================================================
 *  Новогодний ивент: интерактивная Ёлка (клиент)
 *  - При входе проверяет доступность бесплатного действия
 *  - Показывает небольшое окно с кнопкой "Потрясти ёлку"
 *  - Запросы идут в /do/trainers (trainers.php) типами:
 *      newyear_tree_status
 *      newyear_tree_free_action
 * =========================================================== */
window.NewYearTree = window.NewYearTree || (function(){
    var api = {};

    api._shownToday = false;
    api._lastDayKey = null;

    api._modalHtml = function(status){
        var pct = (status && typeof status.server_percent !== 'undefined') ? status.server_percent : 0;
        var tier = (status && typeof status.server_tier !== 'undefined') ? status.server_tier : 0;
        var free = (status && status.free_available) ? 1 : 0;

        var btn = free
            ? '<div class="Button Green" style="margin-top:10px;" onclick="NewYearTree.freeAction();">Потрясти ёлку (бесплатно)</div>'
            : '<div class="Button Gray" style="margin-top:10px;opacity:.7;cursor:default;">Сегодня уже было</div>';

        return ''
            + '<div class="ny-tree-wrap" style="position:fixed;z-index:99999;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;">'
            + '  <div class="ny-tree-modal" style="width:420px;max-width:95vw;background:#111;border:1px solid rgba(255,255,255,.15);border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.55);padding:16px;color:#fff;">'
            + '    <div style="display:flex;align-items:center;justify-content:space-between;">'
            + '      <div style="font-size:16px;font-weight:700;">Ёлка сервера</div>'
            + '      <div style="cursor:pointer;font-size:18px;opacity:.8;" onclick="NewYearTree.close();">&times;</div>'
            + '    </div>'
            + '    <div style="margin-top:8px;font-size:13px;opacity:.9;">Общий прогресс: <b>'+pct+'%</b> · Тир: <b>'+tier+'</b></div>'
            + '    <div style="margin-top:10px;height:10px;border-radius:8px;background:rgba(255,255,255,.12);overflow:hidden;">'
            + '      <div style="height:10px;width:'+pct+'%;background:rgba(255,255,255,.55);"></div>'
            + '    </div>'
            + '    <div style="margin-top:12px;font-size:13px;opacity:.95;">'
            + '      Взаимодействуйте с ёлкой раз в день бесплатно и получайте снежинки.'
            + '    </div>'
            + btn
            + '    <div id="nyTreeMsg" style="margin-top:10px;font-size:13px;opacity:.95;"></div>'
            + '  </div>'
            + '</div>';
    };

    api.open = function(status){
        api.close();
        var html = api._modalHtml(status || {});
        var div = document.createElement('div');
        div.id = 'nyTreeModal';
        div.innerHTML = html;
        document.body.appendChild(div);
    };

    api.close = function(){
        var el = document.getElementById('nyTreeModal');
        if(el) el.remove();
    };

    api.status = function(cb){
        $.ajax({
            url: "/do/trainers",
            type: "POST",
            data: { type: "newyear_tree_status" },
            success: function(resp){
                try { resp = (typeof resp === 'string') ? JSON.parse(resp) : resp; } catch(e) { resp = null; }
                if(cb) cb(resp);
            }
        });
    };

    api.freeAction = function(){
        $('#nyTreeMsg').html('Загрузка...');
        $.ajax({
            url: "/do/trainers",
            type: "POST",
            data: { type: "newyear_tree_free_action" },
            success: function(resp){
                try { resp = (typeof resp === 'string') ? JSON.parse(resp) : resp; } catch(e) { resp = {error:1,text:'Ошибка ответа сервера'}; }

                if(resp && resp.error == 0){
                    var extraText = '';
                    if(resp.extra && resp.extra.length){
                        extraText = '<div style="margin-top:6px;opacity:.9;">Дополнительно: ' + resp.extra.map(function(x){
                            return 'предмет #' + x.item + ' x' + x.count;
                        }).join(', ') + '</div>';
                    }
                    $('#nyTreeMsg').html('<span style="color:#6fe36f;">'+resp.text+'</span>' + extraText);

                    // обновим статус, чтобы кнопка стала недоступной
                    api.status(function(st){
                        if(st && st.active){
                            api.open(st);
                            $('#nyTreeMsg').html('<span style="color:#6fe36f;">'+resp.text+'</span>' + extraText);
                        }
                    });
                }else{
                    $('#nyTreeMsg').html('<span style="color:#ff6a6a;">'+(resp && resp.text ? resp.text : 'Ошибка')+'</span>');
                }
            }
        });
    };

    api.checkOnLogin = function(){
        // не показываем часто
        api.status(function(st){
            if(!st || !st.active) return;

            // дневной анти-спам на клиенте
            if(api._lastDayKey && api._lastDayKey == st.day_key && api._shownToday) return;

            api._lastDayKey = st.day_key;

            if(st.free_available){
                api._shownToday = true;
                api.open(st);
            }
        });
    };

    return api;
})();

// Инициализация при входе: ждём, когда игра "поднимется"
(function(){
    var tries = 0;
    var t = setInterval(function(){
        tries++;
        if(tries > 120){ clearInterval(t); return; } // ~24 сек
        if(window.Game){
            clearInterval(t);
            try { window.NewYearTree.checkOnLogin(); } catch(e){}
        }
    }, 200);
})();

/* =====================================================================
   Winter Wonder Event UI (Region 7) — injected patch (Alabastia)
   Opens /do/event_winterwonder.php and renders quests/tiers/shop/boss.
   Safe to append to world.js. Does not depend on other patches.
===================================================================== */
(function(){
  if (window.__WW_UI__) return; // prevent double init
  window.__WW_UI__ = { version: 'v2', endpoint: '/do/event_winterwonder.php' };

  function wwToast(msg, type){
    try{
      if (typeof showNotification === 'function') return showNotification(msg, type || 'info');
      if (window.toastr) return toastr[type||'info'](msg);
    }catch(e){}
    alert(msg);
  }

  function wwAjax(action, data, ok, fail){
    data = data || {};
    data.action = action;
    $.ajax({
      url: window.__WW_UI__.endpoint,
      type: 'POST',
      data: data,
      dataType: 'json',
      success: function(res){
        if (!res || typeof res !== 'object') return (fail?fail('Пустой ответ сервера'):wwToast('Пустой ответ сервера','error'));
        if (res.ok && res.data) return ok(res);
        if (res.ok && res.active !== undefined) return ok(res);
        if (res.ok) return ok(res);
        var err = res.error || 'Ошибка сервера';
        if (fail) fail(err, res); else wwToast(err,'error');
      },
      error: function(xhr){
        var t = 'Server error';
        try{ if (xhr && xhr.responseText) t = xhr.responseText.slice(0, 300); }catch(e){}
        if (fail) fail(t); else wwToast('Ошибка соединения: '+t,'error');
      }
    });
  }

  function wwEnsureStyle(){
    if (document.getElementById('ww-style')) return;
    var css = `
      .ww-overlay{position:fixed;inset:0;background:rgba(7,10,20,.62);backdrop-filter:blur(6px);z-index:99998;display:flex;align-items:center;justify-content:center;padding:14px;}
      .ww-modal{width:min(980px,96vw);max-height:92vh;background:linear-gradient(180deg,rgba(23,18,46,.96),rgba(16,14,30,.96));border:1px solid rgba(255,255,255,.10);border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.45);overflow:hidden;color:#eef;}
      .ww-head{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;padding:14px 14px 10px;border-bottom:1px solid rgba(255,255,255,.08);}
      .ww-title{font-weight:900;font-size:16px;letter-spacing:.2px;margin:0;}
      .ww-sub{opacity:.75;font-size:12px;margin-top:4px;}
      .ww-close{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#fff;display:grid;place-items:center;cursor:pointer;}
      .ww-tabs{display:flex;gap:8px;flex-wrap:wrap;padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.08);}
      .ww-tab{padding:8px 10px;border-radius:12px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);color:#fff;font-weight:800;font-size:12px;cursor:pointer;user-select:none}
      .ww-tab.active{background:rgba(167,139,250,.22);border-color:rgba(167,139,250,.35);}
      .ww-body{padding:14px;overflow:auto;max-height:calc(92vh - 120px);}
      .ww-grid{display:grid;grid-template-columns:1.35fr .85fr;gap:12px}
      @media(max-width:860px){.ww-grid{grid-template-columns:1fr}}
      .ww-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:16px;padding:12px}
      .ww-card h3{margin:0 0 8px;font-size:13px;font-weight:900}
      .ww-line{display:flex;gap:10px;align-items:center;justify-content:space-between;margin:6px 0}
      .ww-pill{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);font-size:12px;font-weight:800}
      .ww-btn{padding:10px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(167,139,250,.25);color:#fff;font-weight:900;cursor:pointer}
      .ww-btn.secondary{background:rgba(255,255,255,.07)}
      .ww-btn.danger{background:rgba(239,68,68,.22)}
      .ww-btn:disabled{opacity:.5;cursor:not-allowed}
      .ww-kv{display:flex;gap:10px;flex-wrap:wrap}
      .ww-muted{opacity:.72;font-size:12px;line-height:1.35}
      .ww-progress{height:10px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;border:1px solid rgba(255,255,255,.10)}
      .ww-bar{height:100%;background:linear-gradient(90deg,rgba(59,130,246,.85),rgba(167,139,250,.9));width:0%}
      .ww-quest{display:grid;gap:10px}
      .ww-step{padding:12px;border-radius:16px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09)}
      .ww-step-top{display:flex;gap:10px;align-items:flex-start;justify-content:space-between}
      .ww-step-name{font-weight:950}
      .ww-badge{padding:5px 8px;border-radius:999px;font-size:11px;font-weight:900;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06)}
      .ww-badge.ok{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.35)}
      .ww-badge.no{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.30)}
      .ww-list{margin:8px 0 0;padding:0;list-style:none;display:grid;gap:6px}
      .ww-list li{display:flex;gap:10px;align-items:center;justify-content:space-between;font-size:12px}
      .ww-fab{position:fixed;right:14px;bottom:14px;z-index:99997;background:rgba(167,139,250,.95);color:#fff;border:none;border-radius:999px;padding:12px 14px;font-weight:950;box-shadow:0 14px 40px rgba(0,0,0,.35);cursor:pointer}
      @media(max-width:520px){.ww-modal{width:96vw}.ww-body{max-height:calc(92vh - 140px)}}
    `;
    var st=document.createElement('style');
    st.id='ww-style';
    st.textContent=css;
    document.head.appendChild(st);
  }

  function wwFmtTime(ts){
    if (!ts) return '—';
    try{
      var d=new Date(ts*1000);
      var dd=String(d.getDate()).padStart(2,'0');
      var mm=String(d.getMonth()+1).padStart(2,'0');
      var yy=d.getFullYear();
      return dd+'.'+mm+'.'+yy+' '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');
    }catch(e){ return '—'; }
  }

  function wwClamp(n, a, b){ n = Number(n||0); if (n<a) return a; if (n>b) return b; return n; }
  function wwPct(cur, max){ cur=Number(cur||0); max=Number(max||0); if (max<=0) return 0; return wwClamp((cur/max)*100,0,100); }

  function wwBuildModal(){
    wwEnsureStyle();

    var overlay=document.createElement('div');
    overlay.className='ww-overlay';
    overlay.id='ww-overlay';
    overlay.innerHTML = `
      <div class="ww-modal" role="dialog" aria-modal="true">
        <div class="ww-head">
          <div>
            <div class="ww-title">Зимнее Чудо — Ивент-центр</div>
            <div class="ww-sub" id="ww-sub">Загрузка…</div>
          </div>
          <div class="ww-close" id="ww-close">✕</div>
        </div>
        <div class="ww-tabs" id="ww-tabs"></div>
        <div class="ww-body" id="ww-body">
          <div class="ww-card"><div class="ww-muted">Загрузка данных…</div></div>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function(e){
      if (e.target === overlay) wwClose();
    });
    overlay.querySelector('#ww-close').addEventListener('click', wwClose);
    document.addEventListener('keydown', wwEscClose, { passive:true });

    return overlay;
  }

  function wwEscClose(e){
    if (e && e.key === 'Escape') wwClose();
  }

  function wwClose(){
    var o=document.getElementById('ww-overlay');
    if (o) o.remove();
    document.removeEventListener('keydown', wwEscClose, { passive:true });
  }

  function wwTabList(data){
    var tabs = [
      {k:'home', t:'Ивент'},
      {k:'quests', t:'Задания'},
      {k:'tiers', t:'Тиры'},
      {k:'shop', t:'Обменник'},
      {k:'boss', t:'Босс'},
      {k:'stats', t:'Статистика'},
    ];
    if (data && data.is_admin) tabs.push({k:'admin', t:'Админ'});
    return tabs;
  }

  function wwRenderTabs(root, data, activeKey){
    var tabs = wwTabList(data);
    var el = root.querySelector('#ww-tabs');
    el.innerHTML='';
    tabs.forEach(function(tb){
      var b=document.createElement('div');
      b.className='ww-tab' + (tb.k===activeKey?' active':'');
      b.textContent=tb.t;
      b.dataset.key=tb.k;
      b.addEventListener('click', function(){
        window.__WW_UI__.activeTab = tb.k;
        wwRender(root, data);
      });
      el.appendChild(b);
    });
  }

  function wwOpenNpc(){
    if (typeof NpcDialog === 'function') {
      NpcDialog(9906, 0);
    } else {
      wwToast('Открой: Дом → Доступные персонажи → Ивент → Зимний Дух (Алабастия).','info');
    }
  }

  function wwRender(root, payload){
    var data = payload && payload.data ? payload.data : payload;
    if (!data) return;

    var activeKey = window.__WW_UI__.activeTab || 'home';
    wwRenderTabs(root, data, activeKey);

    // subtitle
    var sub = root.querySelector('#ww-sub');
    try{
      var st=data.state || {};
      var active = data.active ? 'Активен' : 'Не активен';
      sub.textContent = active+' • ' + (st.name || 'Winter Wonder') + ' • ' + (st.start_ts?('с '+wwFmtTime(st.start_ts)):'') + (st.end_ts?(' до '+wwFmtTime(st.end_ts)):'');
    }catch(e){ sub.textContent=''; }

    var body = root.querySelector('#ww-body');
    var counts = (data.counts || {});
    var user = (data.user || {});
    var server = (data.server || {});

    // helper: quest status
    function badge(ok){ return ok ? '<span class="ww-badge ok">Сдано</span>' : '<span class="ww-badge no">Не сдано</span>'; }

    if (activeKey === 'home') {
      var started = (user.started_at && Number(user.started_at)>0);
      body.innerHTML = `
        <div class="ww-grid">
          <div class="ww-card">
            <h3>История</h3>
            <div class="ww-muted">
              В Алабастии ударили неожиданные морозы. Зимний Дух прибыл в город, чтобы удержать холод под контролем — 
              но кто‑то растащил “ингредиенты праздника”, а озорные покемоны мешают восстановить магию.
              Помоги Духу — собери предметы, выполни этапы и верни сияние праздника в PokeKara.
            </div>
            <div style="height:10px"></div>
            <div class="ww-kv">
              <div class="ww-pill">Колокольчики: <b>${counts.bells||0}</b></div>
              <div class="ww-pill">Украшения (сервер): <b>${server.ornaments_total||0}</b></div>
              <div class="ww-pill">Колокольчики (сервер): <b>${server.bells_total||0}</b></div>
            </div>
            <div style="height:12px"></div>
            <div class="ww-card" style="padding:10px">
              <div class="ww-muted">
                Старт и сдача этапов — у NPC <b>«Зимний Дух»</b> в <b>Алабастии</b>:
                <b>Дом → Доступные персонажи → Ивент → Зимний Дух</b>, затем нажми <b>«Запустить ивент»</b>.
              </div>
            </div>
            <div style="height:12px"></div>
            <div class="ww-line">
              <button class="ww-btn secondary" id="ww-open-npc">Открыть Зимнего Духа</button>
              <button class="ww-btn" id="ww-start" ${(!data.active?'disabled':'')}>${started?'Ивент уже начат':'Запустить ивент'}</button>
            </div>
          </div>

          <div class="ww-card">
            <h3>Быстрые действия</h3>
            <div class="ww-line"><div class="ww-muted">Ежедневно</div><button class="ww-btn secondary" id="ww-daily">Получить +3</button></div>
            <div class="ww-muted">Ежедневная награда выдаёт <b>Колокольчик ×3</b> (1 раз в день).</div>
            <div style="height:12px"></div>
            <div class="ww-line"><div class="ww-muted">Босс</div><button class="ww-btn secondary" id="ww-boss">Начать бой</button></div>
            <div class="ww-muted">Босс доступен после сдачи этапа 3.</div>
          </div>
        </div>
      `;

      body.querySelector('#ww-open-npc').onclick = wwOpenNpc;
      body.querySelector('#ww-start').onclick = function(){
        wwAjax('start', {}, function(res){
          wwToast(res.msg || 'Ивент начат','success');
          wwRender(root, res);
        });
      };
      body.querySelector('#ww-daily').onclick = function(){
        wwAjax('daily', {}, function(res){
          wwToast(res.msg || 'Получено','success');
          wwRender(root, res);
        });
      };
      body.querySelector('#ww-boss').onclick = function(){
        wwAjax('boss_start', {}, function(res){
          wwToast(res.msg || 'Бой начат','success');
          wwRender(root, res);
        });
      };
      return;
    }

    if (activeKey === 'quests') {
      var s1_ok = (Number(user.step1_done||0)===1);
      var s2_ok = (Number(user.step2_done||0)===1);
      var s3_ok = (Number(user.step3_done||0)===1);
      var s4_ok = (Number(user.boss_done||0)===1);

      body.innerHTML = `
        <div class="ww-card" style="margin-bottom:12px">
          <h3>Цепочка заданий</h3>
          <div class="ww-muted">
            Задания выполняются за счёт дропа в регионе 7. Сдача этапов — у <b>Зимнего Духа в Алабастии</b>.
          </div>
          <div style="height:10px"></div>
          <button class="ww-btn secondary" id="ww-open-npc2">Открыть Зимнего Духа</button>
        </div>

        <div class="ww-quest">
          <div class="ww-step">
            <div class="ww-step-top">
              <div>
                <div class="ww-step-name">Этап 1 — Ингредиенты для праздника</div>
                <div class="ww-muted">Собери предметы в регионе 7 (Oddish / Gastly / Vulpix/Ponyta).</div>
              </div>
              ${badge(s1_ok)}
            </div>
            <ul class="ww-list">
              <li><span>Ледяной лист</span><b>${counts.ice_leaf||0}/3</b></li>
              <li><span>Призрачная искра</span><b>${counts.ghost_spark||0}/2</b></li>
              <li><span>Тёплый уголь</span><b>${counts.warm_coal||0}/2</b></li>
            </ul>
          </div>

          <div class="ww-step">
            <div class="ww-step-top">
              <div>
                <div class="ww-step-name">Этап 2 — Озорники в снегу</div>
                <div class="ww-muted">Убийства считаются по <b>первому типу</b> (список покемонов региона 7).</div>
              </div>
              ${badge(s2_ok)}
            </div>
            <ul class="ww-list">
              <li><span>Grass (1-й тип)</span><b>${counts.grass_mark||0}/200</b></li>
              <li><span>Poison (1-й тип)</span><b>${counts.poison_mark||0}/200</b></li>
            </ul>
          </div>

          <div class="ww-step">
            <div class="ww-step-top">
              <div>
                <div class="ww-step-name">Этап 3 — Укрась ёлку</div>
                <div class="ww-muted">Украшения выпадают с любых диких в регионе 7 (20%).</div>
              </div>
              ${badge(s3_ok)}
            </div>
            <ul class="ww-list">
              <li><span>Новогоднее украшение</span><b>${counts.ornament||0}/15</b></li>
            </ul>
          </div>

          <div class="ww-step">
            <div class="ww-step-top">
              <div>
                <div class="ww-step-name">Этап 4 — Морозный страж</div>
                <div class="ww-muted">Босс Aggron lvl 50. После победы получи Сердце стража.</div>
              </div>
              ${badge(s4_ok)}
            </div>
            <ul class="ww-list">
              <li><span>Сердце Морозного стража</span><b>${counts.boss_heart||0}/1</b></li>
            </ul>
            <div style="height:10px"></div>
            <button class="ww-btn" id="ww-boss2">Начать бой с боссом</button>
          </div>
        </div>
      `;

      body.querySelector('#ww-open-npc2').onclick = wwOpenNpc;
      body.querySelector('#ww-boss2').onclick = function(){
        wwAjax('boss_start', {}, function(res){
          wwToast(res.msg || 'Бой начат','success');
          wwRender(root, res);
        });
      };
      return;
    }

    if (activeKey === 'tiers') {
      // Using ornaments_total as a “server tree” progress
      var target = 15000;
      var cur = Number(server.ornaments_total||0);
      var pct = wwPct(cur, target);
      var tiers = [
        {t:1, need: 1000, reward:'Колокольчик ×3 (всем)'},
        {t:2, need: 3000, reward:'Конфета ×1 (всем)'},
        {t:3, need: 6000, reward:'Треня ×1 (розыгрыш)'},
        {t:4, need:10000, reward:'Колокольчик ×10 (всем)'},
        {t:5, need:15000, reward:'Сюрприз‑пак (ивент)'}
      ];
      var tier_now = 0;
      for (var i=0;i<tiers.length;i++){ if (cur>=tiers[i].need) tier_now=tiers[i].t; }

      body.innerHTML = `
        <div class="ww-card">
          <h3>Серверные тиры</h3>
          <div class="ww-muted">Общий прогресс строится по количеству <b>украшений</b>, добытых игроками в регионе 7.</div>
          <div style="height:10px"></div>
          <div class="ww-line">
            <div class="ww-pill">Прогресс: <b>${cur}</b> / ${target}</div>
            <div class="ww-pill">Текущий тир: <b>${tier_now}</b></div>
          </div>
          <div style="height:10px"></div>
          <div class="ww-progress"><div class="ww-bar" style="width:${pct}%"></div></div>
          <div style="height:12px"></div>
          <div class="ww-card" style="padding:10px">
            <div class="ww-muted">
              Примечание: Тиры — это “витрина” прогресса для игроков. Награды по тирам выдаются администратором вручную/скриптом, если вы включите авто‑раздачу.
            </div>
          </div>
        </div>

        <div style="height:12px"></div>
        <div class="ww-card">
          <h3>Пороги</h3>
          <ul class="ww-list">
            ${tiers.map(function(x){
              var ok = cur>=x.need;
              return `<li><span>Тир ${x.t} — ${x.need}</span><span class="ww-badge ${ok?'ok':'no'}">${ok?'Открыто':'Закрыто'}</span></li>
                      <li style="opacity:.85"><span class="ww-muted">${x.reward}</span><span></span></li>`;
            }).join('')}
          </ul>
        </div>
      `;
      return;
    }

    if (activeKey === 'shop') {
      body.innerHTML = `
        <div class="ww-grid">
          <div class="ww-card">
            <h3>Обменник колокольчиков</h3>
            <div class="ww-muted">Трать колокольчики на награды. Частично может выпадать Alola Vulpix.</div>
            <div style="height:10px"></div>
            <div class="ww-pill">Твои колокольчики: <b>${counts.bells||0}</b></div>
            <div style="height:12px"></div>

            <div class="ww-line"><div><b>Зелёная конфета</b><div class="ww-muted">Цена: 1</div></div><button class="ww-btn" data-sku="candy_green">Купить</button></div>
            <div class="ww-line"><div><b>Фиолетовая конфета</b><div class="ww-muted">Цена: 1</div></div><button class="ww-btn" data-sku="candy_purple">Купить</button></div>
            <div class="ww-line"><div><b>Набор тренировки</b><div class="ww-muted">Цена: 5</div></div><button class="ww-btn" data-sku="training">Купить</button></div>
            <div class="ww-line"><div><b>Покемон (гены 16–21)</b><div class="ww-muted">Цена: 15</div></div><button class="ww-btn" data-sku="genes_16_21">Купить</button></div>
          </div>

          <div class="ww-card">
            <h3>Подсказка</h3>
            <div class="ww-muted">
              Если кнопки “Купить” не работают — сначала запусти ивент у Зимнего Духа (Алабастия).
            </div>
            <div style="height:10px"></div>
            <button class="ww-btn secondary" id="ww-open-npc3">Открыть Зимнего Духа</button>
          </div>
        </div>
      `;
      body.querySelector('#ww-open-npc3').onclick = wwOpenNpc;
      body.querySelectorAll('button[data-sku]').forEach(function(btn){
        btn.onclick = function(){
          var sku = btn.getAttribute('data-sku');
          wwAjax('exchange_buy', { sku: sku }, function(res){
            wwToast(res.msg || 'Готово','success');
            wwRender(root, res);
          });
        };
      });
      return;
    }

    if (activeKey === 'boss') {
      body.innerHTML = `
        <div class="ww-grid">
          <div class="ww-card">
            <h3>Морозный страж</h3>
            <div class="ww-muted">Aggron • уровень 50 • увеличенное HP. Доступен после сдачи этапа 3.</div>
            <div style="height:10px"></div>
            <div class="ww-line">
              <div class="ww-pill">Этап 3: <b>${Number(user.step3_done||0)===1?'сдан':'не сдан'}</b></div>
              <div class="ww-pill">Сердце: <b>${counts.boss_heart||0}</b></div>
            </div>
            <div style="height:12px"></div>
            <button class="ww-btn" id="ww-boss3">Начать бой</button>
            <div style="height:10px"></div>
            <div class="ww-muted">
              После победы вернись к Зимнему Духу в Алабастию и сдай этап 4.
            </div>
          </div>

          <div class="ww-card">
            <h3>Запуск и сдача</h3>
            <div class="ww-muted">
              Босс запускается отсюда. Сдача этапа — у NPC «Зимний Дух».
            </div>
            <div style="height:10px"></div>
            <button class="ww-btn secondary" id="ww-open-npc4">Открыть Зимнего Духа</button>
          </div>
        </div>
      `;
      body.querySelector('#ww-open-npc4').onclick = wwOpenNpc;
      body.querySelector('#ww-boss3').onclick = function(){
        wwAjax('boss_start', {}, function(res){
          wwToast(res.msg || 'Бой начат','success');
          wwRender(root, res);
        });
      };
      return;
    }

    if (activeKey === 'stats') {
      var st=data.state || {};
      body.innerHTML = `
        <div class="ww-grid">
          <div class="ww-card">
            <h3>Сервер</h3>
            <div class="ww-line"><span class="ww-muted">Колокольчики всего</span><b>${server.bells_total||0}</b></div>
            <div class="ww-line"><span class="ww-muted">Украшения всего</span><b>${server.ornaments_total||0}</b></div>
            <div class="ww-line"><span class="ww-muted">Старт</span><b>${wwFmtTime(st.start_ts||0)}</b></div>
            <div class="ww-line"><span class="ww-muted">Окончание</span><b>${wwFmtTime(st.end_ts||0)}</b></div>
          </div>

          <div class="ww-card">
            <h3>Твой прогресс</h3>
            <div class="ww-line"><span class="ww-muted">Ивент начат</span><b>${(user.started_at&&Number(user.started_at)>0)?'да':'нет'}</b></div>
            <div class="ww-line"><span class="ww-muted">Этап 1</span><b>${Number(user.step1_done||0)===1?'сдан':'—'}</b></div>
            <div class="ww-line"><span class="ww-muted">Этап 2</span><b>${Number(user.step2_done||0)===1?'сдан':'—'}</b></div>
            <div class="ww-line"><span class="ww-muted">Этап 3</span><b>${Number(user.step3_done||0)===1?'сдан':'—'}</b></div>
            <div class="ww-line"><span class="ww-muted">Босс</span><b>${Number(user.boss_done||0)===1?'побеждён':'—'}</b></div>
            <div class="ww-line"><span class="ww-muted">Колокольчики</span><b>${counts.bells||0}</b></div>
          </div>
        </div>
      `;
      return;
    }

    if (activeKey === 'admin') {
      body.innerHTML = `
        <div class="ww-card">
          <h3>Админ-управление</h3>
          <div class="ww-muted">Только для администратора (id=4). Здесь можно включать/выключать ивент и задавать даты.</div>
          <div style="height:10px"></div>
          <div class="ww-line">
            <button class="ww-btn" id="ww-admin-load">Обновить данные</button>
            <button class="ww-btn secondary" id="ww-admin-on">Включить на 14 дней</button>
            <button class="ww-btn danger" id="ww-admin-off">Остановить</button>
          </div>
          <div style="height:10px"></div>
          <div class="ww-muted" id="ww-admin-info"></div>
        </div>
      `;

      function adminLoad(){
        wwAjax('admin_get', {}, function(res){
          wwToast('Данные обновлены','success');
          wwRender(root, res);
        });
      }
      body.querySelector('#ww-admin-load').onclick = adminLoad;

      body.querySelector('#ww-admin-on').onclick = function(){
        wwAjax('admin_set', { mode:'on', days:14 }, function(res){
          wwToast('Ивент включён','success');
          wwRender(root, res);
        });
      };
      body.querySelector('#ww-admin-off').onclick = function(){
        wwAjax('admin_set', { mode:'off' }, function(res){
          wwToast('Ивент остановлен','warning');
          wwRender(root, res);
        });
      };
      return;
    }

    body.innerHTML = `<div class="ww-card"><div class="ww-muted">Раздел в разработке.</div></div>`;
  }

  function wwOpen(){
    if (document.getElementById('ww-overlay')) return;
    var root = wwBuildModal();
    window.__WW_UI__.activeTab = 'home';
    wwAjax('open', {}, function(res){
      wwRender(root, res);
    }, function(err){
      wwToast('Не удалось загрузить ивент: '+err, 'error');
      wwClose();
    });
  }

  function wwEnsureFab(){
    if (document.getElementById('ww-fab')) return;
    var btn=document.createElement('button');
    btn.id='ww-fab';
    btn.className='ww-fab';
    btn.type='button';
    btn.textContent='Ивент';
    btn.addEventListener('click', wwOpen);
    document.body.appendChild(btn);
  }

  // Hotkey Alt+E
  document.addEventListener('keydown', function(e){
    try{
      if (e && e.altKey && (e.key === 'e' || e.key === 'E')) { e.preventDefault(); wwOpen(); }
    }catch(_){}
  });

  // Boot when DOM ready
  $(function(){
    wwEnsureFab();
  });

  // expose for debug
  window.WWOpen = wwOpen;
})();



/* === UI_PATCH_MODAL_SHELL_V1 === */
(function(){
  'use strict';
  // ---- Debug control (prod: silence noisy logs, keep errors) ----
  function _hasDebugFlag(){
    try{
      if (new URLSearchParams(location.search).get('debug') === '1') return true;
      if (localStorage && (localStorage.getItem('debug') === '1' || localStorage.getItem('debug') === 'true')) return true;
    }catch(e){}
    return false;
  }
  var UI_DEBUG = _hasDebugFlag();
  try{
    if(!UI_DEBUG && window.console){
      var _c = window.console;
      _c._log = _c._log || _c.log;
      _c._debug = _c._debug || _c.debug;
      _c._info = _c._info || _c.info;
      _c.log = function(){};
      _c.debug = function(){};
      _c.info = function(){};
    }
  }catch(e){}

  function isMobile(){
    try{
      return (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) ||
             (/Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i.test(navigator.userAgent));
    }catch(e){ return window.innerWidth <= 768; }
  }

  // ---- ModalShell: unified overlay + ESC + scroll-lock + mobile fullscreen ----
  var ModalShell = window.ModalShell || {};
  window.ModalShell = ModalShell;

  var root, overlay, windowEl, headerEl, titleEl, closeBtn, bodyEl;
  var locked = false;
  var lockScrollY = 0;

  function injectCSS(){
    if(document.getElementById('mshell-css')) return;
    var css = `
#mshell-root{position:fixed;inset:0;z-index:999999;display:none;}
#mshell-root.ms-active{display:block;}
#mshell-root .mshell-overlay{position:absolute;inset:0;background:rgba(0,0,0,.55);}
#mshell-root .mshell-window{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(760px,calc(100vw - 24px));max-height:calc(100vh - 24px);border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 10px 30px rgba(0,0,0,.45);background:var(--mshell-bg, rgba(30,30,30,.98));color:inherit;}
#mshell-root .mshell-header{flex:0 0 auto;display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.08);}
#mshell-root .mshell-title{font-weight:700;font-size:15px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
#mshell-root .mshell-close{margin-left:auto;border:0;background:rgba(255,255,255,.10);color:inherit;width:40px;height:40px;border-radius:12px;font-size:22px;line-height:1;display:flex;align-items:center;justify-content:center;cursor:pointer;}
#mshell-root .mshell-body{flex:1 1 auto;overflow:auto;-webkit-overflow-scrolling:touch;padding:0;}
#mshell-root .mshell-body .Modal,
#mshell-root .mshell-body .LittleModal,
#mshell-root .mshell-body .model{position:static !important;left:auto !important;top:auto !important;margin:0 auto !important;transform:none !important;width:100% !important;max-width:none !important;box-shadow:none !important;}
#mshell-root .mshell-body .Modal{min-height:0 !important;}
#mshell-root .mshell-body .Title{position:sticky;top:0;z-index:2;}
#mshell-root .mshell-body .Close{display:none !important;} /* use unified close button */
#mshell-root .mshell-body button,
#mshell-root .mshell-body .Button,
#mshell-root .mshell-body input[type="button"],
#mshell-root .mshell-body input[type="submit"]{min-height:40px;border-radius:12px;padding:10px 12px;}
#mshell-root .mshell-body input, #mshell-root .mshell-body select, #mshell-root .mshell-body textarea{min-height:40px;border-radius:12px;padding:10px 12px;}
/* Mobile fullscreen modal */
@media (max-width:768px){
  #mshell-root .mshell-window{left:0;top:0;transform:none;width:100vw;max-height:100vh;border-radius:0;}
  #mshell-root .mshell-header{padding:12px 12px;}
  #mshell-root .mshell-title{font-size:16px;}
  #mshell-root .mshell-close{width:44px;height:44px;border-radius:14px;}
}
/* Bottom-sheet mode */
#mshell-root.ms-sheet .mshell-window{left:0;right:0;top:auto;bottom:0;transform:none;width:100vw;max-height:75vh;border-radius:16px 16px 0 0;}
#mshell-root.ms-sheet .mshell-header{border-bottom:1px solid rgba(255,255,255,.08);}
@media (max-width:768px){
  #mshell-root.ms-sheet .mshell-window{max-height:80vh;}
}
/* Small tip content */
#mshell-root .mshell-tip{padding:14px 14px 16px;}
#mshell-root .mshell-tip h3{margin:0 0 6px;font-size:16px;}
#mshell-root .mshell-tip .mshell-tip-text{opacity:.95;line-height:1.35;white-space:pre-wrap;}
#mshell-root .mshell-tip .mshell-tip-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px;}
#mshell-root .mshell-tip .mshell-tip-actions button{min-width:110px;}

/* Calendar/task layout improvements on mobile */
#mshell-root .TaskHint{display:inline-flex;align-items:center;justify-content:center;gap:6px;margin-left:8px;opacity:.85;cursor:pointer;}
#mshell-root .TaskHint i{font-size:16px;}
@media (max-width:768px){
  #mshell-root .TasksList{padding:10px 10px 14px;}
  #mshell-root .CalendarHead{padding:12px 10px 6px;font-size:16px;}
  #mshell-root .CalendarPrewiev{padding:0 10px 10px;opacity:.9;line-height:1.35;}
  #mshell-root .TasksList .Task{display:flex;flex-direction:column;align-items:stretch;gap:10px;padding:12px;border-radius:16px;}
  #mshell-root .TasksList .TaskLeft{width:100%;}
  #mshell-root .TasksList .TaskImage{align-self:flex-end;display:flex;align-items:center;gap:10px;}
  #mshell-root .TasksList .TaskImage img{width:46px;height:46px;object-fit:contain;}
  #mshell-root .TasksList .TaskText{font-size:15px;line-height:1.35;}
  #mshell-root .TasksList .TaskBar{height:10px;border-radius:999px;overflow:hidden;}
  #mshell-root .TasksList .TaskBar > div{height:100%;}
}

/* Mobile quick calendar button */
#ms-quick-calendar{position:fixed;right:14px;bottom:86px;z-index:999998;border:0;background:rgba(0,0,0,.55);color:#fff;width:52px;height:52px;border-radius:16px;display:none;align-items:center;justify-content:center;font-size:22px;box-shadow:0 8px 20px rgba(0,0,0,.35);backdrop-filter:saturate(140%) blur(6px);}
@media (max-width:768px){ #ms-quick-calendar{display:flex;} }
`;
    var st=document.createElement('style');
    st.id='mshell-css';
    st.textContent=css;
    document.head.appendChild(st);
  }

  function ensure(){
    injectCSS();
    if(root) return;
    root=document.createElement('div');
    root.id='mshell-root';
    overlay=document.createElement('div');
    overlay.className='mshell-overlay';
    windowEl=document.createElement('div');
    windowEl.className='mshell-window';
    headerEl=document.createElement('div');
    headerEl.className='mshell-header';
    titleEl=document.createElement('div');
    titleEl.className='mshell-title';
    titleEl.textContent='';
    closeBtn=document.createElement('button');
    closeBtn.className='mshell-close';
    closeBtn.type='button';
    closeBtn.setAttribute('aria-label','Close');
    closeBtn.innerHTML='&times;';
    bodyEl=document.createElement('div');
    bodyEl.className='mshell-body';

    headerEl.appendChild(titleEl);
    headerEl.appendChild(closeBtn);
    windowEl.appendChild(headerEl);
    windowEl.appendChild(bodyEl);
    root.appendChild(overlay);
    root.appendChild(windowEl);
    document.body.appendChild(root);

    overlay.addEventListener('click', function(){ ModalShell.close(); }, {passive:true});
    closeBtn.addEventListener('click', function(){ ModalShell.close(); });

    document.addEventListener('keydown', function(e){
      if(!root || !root.classList.contains('ms-active')) return;
      if(e.key === 'Escape'){ e.preventDefault(); ModalShell.close(); }
    });
  }

  function lockScroll(){
    if(locked) return;
    locked=true;
    try{
      lockScrollY = window.scrollY || document.documentElement.scrollTop || 0;
      document.body.style.position='fixed';
      document.body.style.top = (-lockScrollY) + 'px';
      document.body.style.left='0';
      document.body.style.right='0';
      document.body.style.width='100%';
    }catch(e){}
  }
  function unlockScroll(){
    if(!locked) return;
    locked=false;
    try{
      document.body.style.position='';
      document.body.style.top='';
      document.body.style.left='';
      document.body.style.right='';
      document.body.style.width='';
      window.scrollTo(0, lockScrollY || 0);
    }catch(e){}
  }

  function setTitleFromModal(modalNode){
    // Try to take title from common selectors
    var t = '';
    try{
      var $m = window.jQuery ? window.jQuery(modalNode) : null;
      if($m){
        var $title = $m.find('.Title .Name:first, .Title:first .Name:first, .Title:first');
        if($title && $title.length) t = $title.text().trim();
      }
    }catch(e){}
    titleEl.textContent = t || 'Окно';
  }

  function adopt(modalNode, opts){
    ensure();
    opts = opts || {};
    // clear previous
    while(bodyEl.firstChild) bodyEl.removeChild(bodyEl.firstChild);
    bodyEl.appendChild(modalNode);

    root.classList.add('ms-active');
    root.classList.toggle('ms-sheet', !!opts.sheet);
    setTitleFromModal(modalNode);

    // On mobile: neutralize any inline positioning that breaks layout
    try{
      if(isMobile()){
        if(modalNode && modalNode.style){
          modalNode.style.left='auto';
          modalNode.style.top='auto';
          modalNode.style.right='auto';
          modalNode.style.bottom='auto';
          modalNode.style.transform='none';
        }
      }
    }catch(e){}

    lockScroll();
  }

  ModalShell.openExisting = function(modalNode, opts){
    if(!modalNode) return;
    adopt(modalNode, opts);
  };

  ModalShell.close = function(){
    ensure();
    try{ if(window.jQuery){ window.jQuery('.BlockOtherContent').show(); } }catch(e){}
    // remove any modals inside
    try{
      if(window.jQuery){
        window.jQuery('.Modal, .LittleModal, .model').remove();
      }else{
        var nodes = bodyEl.querySelectorAll('.Modal, .LittleModal, .model');
        nodes.forEach(function(n){ n.remove(); });
      }
    }catch(e){}
    while(bodyEl.firstChild) bodyEl.removeChild(bodyEl.firstChild);
    root.classList.remove('ms-active');
    root.classList.remove('ms-sheet');
    unlockScroll();
  };

  ModalShell.tip = function(text, title){
    ensure();
    var wrap=document.createElement('div');
    wrap.className='mshell-tip';
    var h=document.createElement('h3');
    h.textContent = title || 'Подсказка';
    var p=document.createElement('div');
    p.className='mshell-tip-text';
    p.textContent = (text || '').toString();
    var actions=document.createElement('div');
    actions.className='mshell-tip-actions';
    var ok=document.createElement('button');
    ok.type='button';
    ok.textContent='OK';
    ok.addEventListener('click', function(){ ModalShell.close(); });
    actions.appendChild(ok);
    wrap.appendChild(h);
    wrap.appendChild(p);
    wrap.appendChild(actions);

    // Create temp container similar to Modal
    var modal=document.createElement('div');
    modal.className='Modal';
    modal.appendChild(wrap);

    adopt(modal, {sheet: isMobile()});
  };

  // Auto-adopt any modals created by old code
  function setupObservers(){
    ensure();
    var obs = new MutationObserver(function(muts){
      muts.forEach(function(m){
        m.addedNodes && m.addedNodes.forEach(function(node){
          if(!(node instanceof HTMLElement)) return;
          if(node.classList && (node.classList.contains('Modal') || node.classList.contains('LittleModal') || node.classList.contains('model'))){
            // Determine sheet mode only for tips; regular modals use fullscreen/center
            adopt(node, {sheet:false});
          }else{
            // Sometimes modal is nested in wrapper
            var found = node.querySelector && node.querySelector('.Modal, .LittleModal, .model');
            if(found && found instanceof HTMLElement){
              adopt(found, {sheet:false});
            }
          }
        });
        // if modals removed from DOM -> close shell
        if(m.removedNodes && root && root.classList.contains('ms-active')){
          var any = bodyEl.querySelector('.Modal, .LittleModal, .model');
          if(!any){
            root.classList.remove('ms-active');
            root.classList.remove('ms-sheet');
            unlockScroll();
          }
        }
      });
    });
    obs.observe(document.body, {childList:true, subtree:true});
  }
  setupObservers();

  // Ensure calendar button works (desktop + mobile)
  if(window.jQuery){
    window.jQuery(document).off('click.msCalendar').on('click.msCalendar', '.el_calendar', function(e){
      e.preventDefault();
      if(typeof openModal === 'function') openModal('calendar');
      else ModalShell.tip('Не удалось открыть календарь: openModal не найден.', 'Календарь');
    });
    window.jQuery(document).off('click.msTaskHint').on('click.msTaskHint', '.TaskHint', function(e){
      e.preventDefault();
      var hint = window.jQuery(this).data('hint') || window.jQuery(this).attr('data-hint') || '';
      ModalShell.tip(hint || 'Подсказка недоступна.', 'Почему не засчиталось');
    });
  }else{
    document.addEventListener('click', function(e){
      var t=e.target.closest && e.target.closest('.el_calendar');
      if(t){
        e.preventDefault();
        if(typeof openModal === 'function') openModal('calendar');
        else ModalShell.tip('Не удалось открыть календарь: openModal не найден.', 'Календарь');
      }
      var h=e.target.closest && e.target.closest('.TaskHint');
      if(h){
        e.preventDefault();
        ModalShell.tip(h.getAttribute('data-hint')||'', 'Почему не засчиталось');
      }
    });
  }

  // Mobile quick calendar floating button (fallback if top menu hidden)
  function ensureQuickCalendar(){
    if(document.getElementById('ms-quick-calendar')) return;
    var b=document.createElement('button');
    b.id='ms-quick-calendar';
    b.type='button';
    b.title='Календарь';
    b.textContent='📅';
    b.addEventListener('click', function(){
      if(typeof openModal === 'function') openModal('calendar');
      else ModalShell.tip('Не удалось открыть календарь: openModal не найден.', 'Календарь');
    });
    document.body.appendChild(b);
  }
  ensureQuickCalendar();

})();
 /* === UI_PATCH_MODAL_SHELL_V1_END === */

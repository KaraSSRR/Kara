var Chat = function(){
    var _self = this,
        _last_id = 0,
        _timeout_id = 0,
        _time_refresh = 1200,
        _time_refresh_min = 500,
        _time_refresh_max = 5000;

    // API endpoint (на проде часто есть rewrite /do/chat -> /do/chat.php)
    var _apiUrl = '/do/chat';

    // Быстрый селектор
    var $cat = $('.Chat .Category'),
        $talk = $('.Chat .Talk'),
        $chat_send = $('#chat_send'),
        $chat_user = $('#chat_user_to');

    // Быстрее: кэшировать jQuery-элементы и уменьшить повторяющиеся обращения

    // Текущий пользователь (нужен для подсветки/лейблов ЛС)
    var _myId = parseInt($('#user_id').val() || '0', 10);

    // Общая функция: безопасный key для CSS-класса
    var _clsKey = function(key){
        return String(key).replace(/[^a-zA-Z0-9_\-]/g, '_');
    };

    // Общая функция: id канала ЛС (idMIN_idMAX)
    var _pmChannelId = function(a, b){
        a = parseInt(a || 0, 10);
        b = parseInt(b || 0, 10);
        if(!a || !b) return '';
        return 'id' + Math.min(a,b) + '_' + Math.max(a,b);
    };

    // --- Отправка сообщения ---
    this._send = function(){
        var msg = $chat_send.val().trim(),
            toUser = $chat_user.val().trim();

        if(!msg) return;

        // ВАЖНО: приватность на бекенде включается только когда msg начинается с '=' или '/' (GameChat::add)
        // поэтому '=' НЕ отрезаем на фронте.
        const isPrivate = (msg[0] === '=' || msg[0] === '/');

        // to_user должен быть числом (id). Если тут логин — ЛС не сработают корректно.
        var toUserId = parseInt(toUser || '0', 10);

        // Оптимистично открываем вкладку ЛС (если есть адресат)
        if(isPrivate && toUserId){
            var pmChan = _pmChannelId(_myId, toUserId);
            if(pmChan){
                _self._ensureChannel(pmChan, null);
                _self._targetChannel($cat.find('[data-channel="'+pmChan+'"]'));
            }
        }

        _self._action({
            'chat': 'chat',
            'type': 'add',
            'msg': msg,
            'to_user': (toUserId ? toUserId : 0)
        }, function(){
            // после отправки сразу подтягиваем новые сообщения
            _time_refresh = _time_refresh_min;
            _self._read();
        });

        $chat_send.val(isPrivate ? '=' : '');
    };

    // --- Быстрое чтение чата ---
    this._read = function(){
        $.ajax({
            url: _apiUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                'chat': 'chat',
                'type': 'read',
                'lastID': _last_id
            },
            success: function (data) {
                if(!data) return;
                if(data.error == 1){
                    if(data.text) $.notify(data.text, 'error');
                    return;
                }
                _self._response(data);
            },
            error: function(){
                // деградация при ошибках сети
                _time_refresh = Math.min(_time_refresh_max, _time_refresh + 800);
            },
            complete: function(){
                _self._refresh();
            }
        });
    };

    // --- Парсим и рендерим сообщения чата ---
    this._parse_sends = function(data){
        if(!data) return;

        // data может быть объектом {id: msgObj} или массивом
        $.each(data, function(id, val){
            if(!val) return;
            if(typeof val === 'string'){
                try { val = JSON.parse(val); } catch(e){ return; }
            }

            var msgType = (val.msg_type !== undefined) ? val.msg_type : (val.type !== undefined ? val.type : 0);
            var chKey = (parseInt(msgType,10) === 1 && val.private_channel_id) ? val.private_channel_id : msgType;

            _self._ensureChannel(chKey, val);
            _self._generateMsg(id, val, chKey);

            // lastID обновляем по response (ниже), но на всякий случай
            var nid = parseInt(id, 10);
            if(nid && nid > _last_id) _last_id = nid;
        });
    };

    // --- Быстрый обработчик ответа сервера ---
    this._response = function(data){
        if(!data) return;

        if(data.infoChatLastId){
            _last_id = parseInt(data.infoChatLastId, 10) || _last_id;
        }
        if(data.infoChat){
            _self._parse_sends(data.infoChat);
            // если был трафик — ускоряем, если тишина — замедляем
            _time_refresh = _time_refresh_min;
        } else {
            _time_refresh = Math.min(_time_refresh_max, _time_refresh + 300);
        }
    }; 

    // --- Таймер авто-обновления ---
    this._refresh = function(clear){
        if(_timeout_id){
            clearTimeout(_timeout_id);
        }
        if(!clear){
            _timeout_id = setTimeout(function(){
                _self._read();
            }, _time_refresh);
        }
    };

    // --- Быстрое переключение вкладок ---
    this._targetChannel = function($tab){
        var chanId = $tab.data('channel');
        $('.Button').removeClass('Active');
        $tab.addClass('Active');
        $('.Message-Block').removeClass('Active');
        $('.Channel_'+_clsKey(chanId)).addClass('Active');
    };

    // --- Загрузка истории канала ---
    this._loadHistory = function(chanId){
        // ЛС история — отдельным endpoint'ом pm_history
        $.ajax({
            url: _apiUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                'chat': 'pm_history',
                'channel': chanId,
                'limit': 200
            },
            success: function(data){
                if(!data || !data.history) return;
                // История возвращается ASC или DESC — на всякий случай сортируем
                var ids = Object.keys(data.history).map(function(x){ return parseInt(x,10); }).filter(Boolean).sort(function(a,b){ return a-b; });
                ids.forEach(function(mid){
                    var row = data.history[mid];
                    if(!row) return;
                    if(typeof row === 'string'){
                        try { row = JSON.parse(row); } catch(e){ return; }
                    }
                    _self._ensureChannel(chanId, row);
                    _self._generateMsg(mid, row, chanId);
                });
            }
        });
    };

    // --- Генерация одного сообщения (ускоренная) ---
    this._generateMsg = function(id, val, channelKey){
        var $element = $('.Channel_'+_clsKey(channelKey));
        if(!$element.length) return;

        var msgType = (val.msg_type !== undefined) ? val.msg_type : (val.type !== undefined ? val.type : 0);
        var isPrivate = (parseInt(msgType,10) === 1);

        var userLogin = val.user_login || val.user_name || '';
        var userId = val.user_id || val.user || 0;
        var userGroup = val.user_group || 0;

        var toLogin = val.userto_login || val.userto_name || '';
        var toId = val.userto_id || val.touser || 0;
        var toGroup = val.userto_group || 0;

        // note: user_msg уже приходит как HTML из бекенда (parseChatMessage)
        var msgHtml = val.user_msg || val.msg || '';
        var msgClass = val.msg_class || '';
        var msgTime = val.msg_time || '';

        // автоскролл: только если пользователь и так внизу
        var shouldScroll = ($element[0].scrollHeight - ($element[0].scrollTop + $element[0].clientHeight) < 60);

        var html = '';
        html += '<div class="Message __msg_id_'+id+' '+(isPrivate ? 'private' : '')+'">';
        html +=   '<div class="Data">['+msgTime+']</div>';
        html +=   '<div class="User">';
        html +=     '<span class="user-link u-'+userGroup+'" data-user-id="'+userId+'">'+userLogin+'</span>';
        if(toId){
            html +=   '<i> » </i>';
            html +=   '<span class="user-link u-'+toGroup+'" data-user-id="'+toId+'">'+toLogin+'</span>';
        }
        html +=     '<span class="DblDot">:</span>';
        html +=   '</div>';
        html +=   '<div class="Post"><span class="'+msgClass+'">'+msgHtml+'</span></div>';
        html += '</div>';

        $element.append(html);
        if(shouldScroll) $element[0].scrollTop = $element[0].scrollHeight;
    };

    // --- Действие (отправка сообщения) ---
    this._action = function(data, done){
        $.ajax({
            url: _apiUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(resp){
                if(resp && resp.error == 1 && resp.text) $.notify(resp.text, 'error');
            },
            complete: function(){
                if(typeof done === 'function') done();
            }
        });
    };

    // Создание вкладки/блока для канала при необходимости
    this._ensureChannel = function(channelKey, sampleMsg){
        channelKey = String(channelKey);
        var cls = _clsKey(channelKey);
        var $block = $('.Channel_'+cls);
        if(!$block.length){
            $block = $('<div>', {
                class: 'Message-Block Channel_' + cls,
                'data-channel': channelKey
            });
            $talk.append($block);
        }

        var $tab = $cat.find('[data-channel="'+channelKey+'"]');
        // попытка переиспользовать существующую кнопку, если разметка старая
        if(!$tab.length){
            $tab = $cat.find('.chat_move_channel_' + cls);
            if($tab.length){
                $tab.attr('data-channel', channelKey)
                    .off('click.chatv2')
                    .on('click.chatv2', function(){
                        _self._targetChannel($(this));
                    });
            }
        }

        if(!$tab.length){
            // Читабельный заголовок
            var title = channelKey;
            var numeric = parseInt(channelKey, 10);
            if(channelKey === '0' || numeric === 0) title = 'Общий';
            if(numeric === 2) title = 'Локация';
            if(numeric === 3) title = 'Клан';
            if(numeric === 4) title = 'Система';
            // ЛС: подставим логин собеседника из sampleMsg
            if(channelKey.indexOf('id') === 0 && sampleMsg){
                var otherLogin = null;
                if(parseInt(sampleMsg.user_id || sampleMsg.user || 0, 10) === _myId){
                    otherLogin = sampleMsg.userto_login || sampleMsg.userto_name || null;
                } else {
                    otherLogin = sampleMsg.user_login || sampleMsg.user_name || null;
                }
                if(otherLogin) title = otherLogin;
            }

            $tab = $('<div>', {
                class: 'Button chat_move_channel_' + cls,
                'data-channel': channelKey,
                html: title
            }).on('click', function(){
                _self._targetChannel($(this));
            });

            $cat.append($tab);
        }
    };

    // --- Инициализация авто-обновления чата ---
    $(function(){
        // Делегированное меню по пользователю: быстро открыть ЛС
        $talk.on('contextmenu', '.user-link', function(e){
            e.preventDefault();
            var uid = parseInt($(this).data('user-id') || '0', 10);
            if(!uid || uid === _myId) return false;

            $chat_user.val(uid);
            if(!$chat_send.val() || $chat_send.val()[0] !== '='){
                $chat_send.val('=' + $chat_send.val());
            }
            $chat_send.focus();

            var pmChan = _pmChannelId(_myId, uid);
            if(pmChan){
                _self._ensureChannel(pmChan, null);
                _self._targetChannel($cat.find('[data-channel="'+pmChan+'"]'));
                _self._loadHistory(pmChan);
            }
            return false;
        });

        // подберем лучший endpoint
        if(window.GAME_CHAT_URL) _apiUrl = window.GAME_CHAT_URL;
        if(_apiUrl === '/do/chat'){
            // если есть /do/chat.php — предпочтём его
            $.ajax({url:'/do/chat.php', type:'HEAD'}).done(function(){ _apiUrl = '/do/chat.php'; }).always(function(){
                _self._read();
            });
            return;
        }
        _self._read();
    });
};

var WorldChat = new Chat();

// Быстрая отправка по Enter
$('#chat_send').on('keydown', function(e){
    if(e.key === "Enter" || e.keyCode === 13){
        WorldChat._send();
        return false;
    }
});
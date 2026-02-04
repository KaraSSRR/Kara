// Новый модульный чат с сохранением полной функциональности и совместимости
// Вставьте этот код в отдельный файл и подключите вместо прошлого GameChat

(function(global, $){
    // === ChatCore: ядро чата ===
    function ChatCore(uInfo, options) {
        var _self = this;
        this.userInfo = uInfo || {};
        this.options = options || {};

        this.state = {
            channels: {},          // {id: {name, ...}}
            activeChannel: 0,
            messages: {},          // {channelId: [messages]}
            pokeList: {},
            commandList: getDefaultCommands(),
            lastID: 0,
            readID: 0,
            countByChannel: {},
            loaded: false
        };

        this.eventHandlers = {}; // {event: [handlers]}
        this._init();
    }

    ChatCore.prototype = {
        on: function(event, handler) {
            if (!this.eventHandlers[event]) this.eventHandlers[event] = [];
            this.eventHandlers[event].push(handler);
        },
        emit: function(event, data) {
            (this.eventHandlers[event] || []).forEach(function(h){ h(data); });
        },
        _init: function() {
            // Первичная инициализация, если надо
        },
        sendMessage: function(msg, toUser) {
            var _self = this;
            if (!msg || !msg.length) return;
            this._action({
                type: 'add',
                msg: msg,
                to_user: (toUser && toUser.length ? toUser : 0)
            });
        },
        _action: function(data, suc, err, cpl) {
            var _self = this;
            if (!data) return false;
            suc = (suc && typeof suc === 'function') ? suc : function(){};
            err = (err && typeof err === 'function') ? err : function(){};
            cpl = (cpl && typeof cpl === 'function') ? cpl : function(){};
            if(this.state.loaded){
                cpl.call(_self, '');
                return false;
            }
            this.state.loaded = true;
            $.ajax({
                url: "/do/chat",
                type: "POST",
                dataType: "json",
                data: $.extend({
                    'chat':'chat',
                    'lastID':this.state.lastID,
                    'chanel':this.state.activeChannel
                }, data),
                success: function(info, textStatus){
                    if(info){ _self._update(info); }
                    if(info['error']){
                        err.call(_self, textStatus);
                        if(global.Game && global.Game.notifications) global.Game.notifications.main(info['text'], 'error');
                    }else{
                        suc.call(_self, info, textStatus);
                    }
                    if(info['success']){
                        if(global.Game && global.Game.notifications) global.Game.notifications.main(info['text'], 'success');
                    }
                    cpl.call(_self, info, textStatus);
                },
                complete: function(jqXHR, textStatus){
                    _self.state.loaded = false;
                    cpl.call(_self, textStatus);
                },
                error: function(jqXHR, textStatus){
                    err.call(_self, textStatus);
                    cpl.call(_self, textStatus);
                }
            });
        },
        _update: function(data) {
            var _self = this;
            if (data['infoChatLastId']) this.state.lastID = data['infoChatLastId'];
            if (data['infoChat']) {
                $.each(data['infoChat'], function(key, val) {
                    if (!val) return;
                    var parsedVal = val;
                    if (typeof val === 'string') {
                        try { parsedVal = $.parseJSON(val); }
                        catch (e) { console.error("Ошибка JSON:", val, e); return; }
                    }
                    _self._addMessage(key, parsedVal);
                });
                this.emit('messagesUpdated', this.state.messages);
            }
        },
        _addMessage: function(id, info) {
            id = parseInt(id);
            if (id <= this.state.readID) return;
            this.state.readID = id;
            var channel = info['msg_type'] == 1 ? 1 : (info['msg_type'] || 0);
            if (!this.state.messages[channel]) this.state.messages[channel] = [];
            this.state.messages[channel].push($.extend({id: id}, info));
            if (channel != this.state.activeChannel) {
                this.state.countByChannel[channel] = (this.state.countByChannel[channel] || 0) + 1;
                this.emit('channelCountUpdated', { channel: channel, count: this.state.countByChannel[channel] });
            }
        },
        switchChannel: function(channelId) {
            this.state.activeChannel = channelId;
            this.state.countByChannel[channelId] = 0;
            this.emit('channelSwitched', channelId);
        },
        updateUserInfo: function(info) {
            this.userInfo = $.extend(this.userInfo, info);
            this.emit('userInfoUpdated', this.userInfo);
        }
    };

    function getDefaultCommands() {
        return {
            's':{'group':[1,2,3],'title':'Выдать кляп','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь"/><input class="time" name="time" autocomplete="off" placeholder="Минуты"/><input class="title" name="title" autocomplete="off" placeholder="Причина"/>'},
            'us':{'group':[1,2,3],'title':'Снять кляп','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь" style="width: 170px;"/><input class="title" name="title" autocomplete="off" placeholder="Причина"/>'},
            'm':{'group':[1,2,3],'title':'Предупреждение в приват','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь" style="width: 170px;"/><input class="title" name="title" autocomplete="off" placeholder="Текст предупреждения"/>'},
            'mm':{'group':[1,2,3],'title':'Предупреждение в «МИР»','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь" style="width: 170px;"/><input class="title" name="title" autocomplete="off" placeholder="Текст предупреждения"/>'},
            'clear':{'group':[1,2,3],'title':'Очистить статус','text':'<input class="big" name="user" autocomplete="off" placeholder="Пользователь"/>'},
            'free':{'group':[1,2],'title':'Освободить из тюрьмы','text':'<input class="big" name="user" autocomplete="off" placeholder="Пользователь"/>'},
            'system':{'group':[1],'title':'Системное сообщение','text':'<input class="big" name="text" autocomplete="off" placeholder="Текст"/>'},
            'prison':{'group':[1,2],'title':'Посадить в тюрьму','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь"/><input class="time" name="time" autocomplete="off" placeholder="Дни"/><input class="title" name="title" autocomplete="off" placeholder="Причина"/>'},
            'fine':{'group':[1,2],'title':'Штраф','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь"/><input class="sum" name="sum" autocomplete="off" placeholder="Сумма"/><input class="title" name="title" autocomplete="off" placeholder="Причина" style="width:220px;"/>'},
            'transf':{'group':[1,2],'title':'Изьятие пока','text':'<input class="big" name="pokeID" autocomplete="off" placeholder="ID Покемона"/>'},
            'comment':{'group':[1],'title':'Комментарий','text':'<input class="big" name="title" autocomplete="off" placeholder="Текст комментария"/>'},
            'ban':{'group':[1,2],'title':'Забанить пользователя','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь" style="width: 170px;"/><input class="title" name="title" autocomplete="off" placeholder="Причина"/>'},
            'tp':{'group':[1],'title':'Телепортироваться на локу','text':'<input class="big" name="loc_name" autocomplete="off" placeholder="Название локации"/>'},
            'moder':{'group':[1,2,3],'title':'Сделать модератором','text':'<input class="big" name="user" autocomplete="off" placeholder="Пользователь"/>'},
            'moderDel':{'group':[1,2,3],'title':'Удалить из модераторов','text':'<input class="big" name="user" autocomplete="off" placeholder="Пользователь"/>'},
            'nast':{'group':[1,4],'title':'Сделать наставником','text':'<input class="big" name="user" autocomplete="off" placeholder="Пользователь"/>'},
            'nastDel':{'group':[1,4],'title':'Удалить из наставников','text':'<input class="big" name="user" autocomplete="off" placeholder="Пользователь"/>'},
            'upGroup':{'group':[1],'title':'Назначить должность','text':'<input class="big" name="user" autocomplete="off" placeholder="Ник" style="width: 300px;"/><input class="group" name="group" autocomplete="off" placeholder="Должность" style="width: 170px;"/>'},
            'aqua':{'group':[1],'title':'Выдать изумруды','text':'<input class="user" name="user" autocomplete="off" placeholder="Пользователь" style="width: 170px;"/><input class="sum" name="sum" autocomplete="off" placeholder="Количество" style="width: 300px;"/>'}
        };
    }

    // === ChatUI: слой отображения и событий ===
    function ChatUI(core) {
        var _self = this;
        this.core = core;
        this.$moveChat = $('.DivChat .Chat .Category');
        this.$chatChannel = $('.DivChat .Chat .Talk .Message-Block.Active');
        this.$chatUser = $('#chat_user_to');
        this.$chatSend = $('#chat_send');
        this.$chatUserDesktop = $('#chat_user_to_desktop');
        this.$chatSendDesktop = $('#chat_send_desktop');
        this.$chatSendSmile = $('.Smiles');
        this.$scrolling = $('input.__chat_scrolls');
        this.$commandList = null;
        this.$smileList = null;
        this.$pokeInfo = null;

        this._bindEvents();
        this._initUI();

        // Подписка на события ядра
        core.on('messagesUpdated', function(messages){ _self.renderMessages(messages); });
        core.on('channelSwitched', function(channelId){ _self.activateChannel(channelId); });
        core.on('channelCountUpdated', function(obj){ _self.updateChannelCount(obj.channel, obj.count); });
        core.on('userInfoUpdated', function(userInfo){ _self.updateUserInfo(userInfo); });
    }
    ChatUI.prototype = {
        _bindEvents: function() {
            var _self = this;
            this.$chatSend.off('keyup').on('keyup', function(e){
                if(e.key === "Enter" || e.keyCode === 13){ _self._onSend(); }
                if($(this).val().charAt(0) == '-'){ _self._viewCommand(); }
                else{ _self._viewCommand(true); }
            });
            this.$chatSendDesktop.off('keyup').on('keyup', function(e){
                if(e.key === "Enter" || e.keyCode === 13){ _self._onSendDesktop(); }
                if($(this).val().charAt(0) == '-'){ _self._viewCommand(); }
                else{ _self._viewCommand(true); }
            });
            if(this.$chatSendSmile && this.$chatSendSmile.length){
                this.$chatSendSmile.on('click', function(){ _self._viewSmile(); });
            }
            this.$moveChat.on('click', '.Button', function(){ _self.core.switchChannel($(this).data('channel')); });
        },
        _initUI: function() {
            var _self = this;
            // Стандартные вкладки чата
            var defaultChannels = [
                {id: 0, label: typeof Lang !== 'undefined' && Lang.chat_world ? Lang.chat_world : 'Мир'},
                {id: 8, label: typeof Lang !== 'undefined' && Lang.chat_loc ? Lang.chat_loc : 'Локация'},
                {id: 9, label: typeof Lang !== 'undefined' && Lang.chat_torg ? Lang.chat_torg : 'Торг'},
                {id: 21, label: typeof Lang !== 'undefined' && Lang.chat_clan ? Lang.chat_clan : 'Клан'},
                {id: 10, label: typeof Lang !== 'undefined' && Lang.chat_cop ? Lang.chat_cop : 'Коп'}
            ];
            // Добавляем кнопки каналов
            this.$moveChat.empty();
            defaultChannels.forEach(function(ch, idx){
                var $btn = $('<div/>', {
                    class: 'Button chat_move_channel_'+ch.id + (ch.id === 0 ? ' Active' : ''),
                    'data-channel': ch.id,
                    html: ch.label,
                    click: function(){ _self.core.switchChannel(ch.id); }
                });
                _self.$moveChat.append($btn);
            });
            // Автоматически активируем первую вкладку
            _self.core.switchChannel(0);
        },
        _onSend: function() {
            var msg = this.$chatSend.val(),
                toUser = this.$chatUser.val();
            if(msg.length){
                this.core.sendMessage(msg, toUser);
                if(msg.substr(0,1) == '/' || msg.substr(0,1) == '='){
                    // Приватный канал
                    var newChat = $("<div />", {
                            class: "Active Button chat_move_channel_5"+toUser,
                            html: ''+toUser+'',
                            "data-channel": '5'+toUser,
                            click: function () { this.core.switchChannel('5'+toUser); }.bind(this)
                        }),
                        newChatBlock = $("<div />", {
                            class: "Active Message-Block Channel_5"+toUser,
                            "data-channel": '5'+toUser
                        });
                    newChat.appendTo('.Chat .Category');
                    newChatBlock.appendTo('.Chat .Talk');
                    this.core.switchChannel('5'+toUser);
                    this.$chatSend.val(msg.substr(0,1));
                }else{
                    this.$chatSend.val('');
                }
            }
            return false;
        },
        _onSendDesktop: function() {
            var msg = this.$chatSendDesktop.val(),
                toUser = this.$chatUserDesktop.val();
            if(msg.length){
                this.core.sendMessage(msg, toUser);
                if(msg.substr(0,1) == '/' || msg.substr(0,1) == '='){
                    var newChat = $("<div />", {
                            class: "Active Button chat_move_channel_5"+toUser,
                            html: ''+toUser+'',
                            "data-channel": '5'+toUser,
                            click: function () { this.core.switchChannel('5'+toUser); }.bind(this)
                        }),
                        newChatBlock = $("<div />", {
                            class: "Active Message-Block Channel_5"+toUser,
                            "data-channel": '5'+toUser
                        });
                    newChat.appendTo('.Chat .Category');
                    newChatBlock.appendTo('.Chat .Talk');
                    this.core.switchChannel('5'+toUser);
                    this.$chatSendDesktop.val(msg.substr(0,1));
                }else{
                    this.$chatSendDesktop.val('');
                }
            }
            return false;
        },
        // Рендер сообщений
        renderMessages: function(messages) {
            var _self = this;
            var channelId = this.core.state.activeChannel;
            var $targetBlock = $('div.Message-Block.Channel_' + channelId);
            if(!$targetBlock.length) $targetBlock = $('div.Message-Block.Active');
            $targetBlock.empty();
            if (messages[channelId]) {
                $.each(messages[channelId], function(idx, info){
                    var style = "";
                    if (info["user_id"] == _self.core.userInfo["id"] ||
                        (info["userto_id"] && info["userto_id"] == _self.core.userInfo["id"])) {
                        style = "me ";
                    }
                    if (info["msg_type"] == 1) style = "private ";
                    if (info["msg_type"] == 11) style = "pred ";
                    if (info["msg_type"] == 12) style = "mut ";
                    if (info["msg_type"] == 13) style = "alert ";
                    if (info["css"]) style += " " + info["css"];
                    var touser = "";
                    if (info["userto_id"]) {
                        touser =
                            ' » <div class="label u-' +
                            info["userto_group"] +
                            '">' +
                            info["userto_login"] +
                            "</div>";
                    }
                    var $msg = $('<div />', {
                        'class':'Message __msg_id_'+info.id+' '+style,
                        'id':'gameChatMessage'
                    }).append(
                        $('<div />', {
                            'id':'gameChatMessageTime',
                            'class':'Data',
                            'html':info['msg_time']
                        }),
                        '<div class="User"><div class="user-link"> <div onclick=showUserTooltip("'+info['user_id']+'") class="Info-Link sex'+info['user_sex']+'"><i class="fas fa-info"></i></div> <div class="u-'+replaceEmoji(info['user_group'])+' label" onclick=user_to_chat_add("'+info['user_id']+'")>'+replaceEmoji(info['user_login'])+'</div> </div>'+touser+' </div> ' +
                        '<span class="TextColor'+info['user_msg_color']+' Post '+style+'">'+replaceEmoji(info['user_msg'])+'</span>'
                    );
                    $targetBlock.append($msg);
                });
            }
            // Автоскролл
            if(this.$scrolling && this.$scrolling.length && this.$scrolling.prop('checked')){
                window.requestAnimationFrame(function() {
                    if ($targetBlock.length) $targetBlock[0].scrollTop = $targetBlock[0].scrollHeight;
                });
            }
        },
        activateChannel: function(channelId) {
            $('div.Message-Block').removeClass('Active');
            this.$moveChat.find('div.Button').removeClass('Active');
            $('div.Message-Block.Channel_' + channelId).addClass('Active');
            this.$moveChat.find('div.chat_move_channel_' + channelId).addClass('Active').find('span').remove();
            // Автоскролл
            var $targetBlock = $('div.Message-Block.Channel_' + channelId);
            if(this.$scrolling && this.$scrolling.length && this.$scrolling.prop('checked')){
                window.requestAnimationFrame(function() {
                    if ($targetBlock.length) $targetBlock[0].scrollTop = $targetBlock[0].scrollHeight;
                });
            }
        },
        updateChannelCount: function(channel, count) {
            var elm = this.$moveChat.find(".chat_move_channel_" + channel);
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
        },
        updateUserInfo: function(userInfo) {
            // Можно обновлять UI, если нужно
        },
        // Вьюха команд
        _viewCommand: function(hide){
            var _self = this;
            if(this.$commandList && this.$commandList.length){
                if(this.$commandList.is(':visible')){
                    if(hide){ this.$commandList.css('display', 'none'); }
                }else{
                    if(!hide){
                        if(!this.$commandList.is(':empty')) this.$commandList.css('display', 'block');
                    }
                }
            }else{
                if(!hide){
                    this.$commandList = $('<div />', {
                        'class':'window commandList'
                    }).appendTo('.Talk');
                    var tpl = [];
                    $.each(this.core.state.commandList, function(key, val){
                        if(
                            (val['group'] && $.inArray(parseInt(_self.core.userInfo['group']), val['group']) != -1) ||
                            (val['id'] && $.inArray(parseInt(_self.core.userInfo['id']), val['id']) != -1)
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
                                            _self.core._action(data, null, null, function(){});
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
                                    if($(this).val() != 'OK'){
                                        $(this).val('');
                                    }
                                })
                            );
                        }
                    });
                    if(tpl.length){
                        var msgField = $('#messageFieldBox');
                        this.$commandList.css('left',(parseInt(msgField.offset().left) - 0)+'px').append(tpl);
                    }else{
                        this.$commandList.css('display', 'none');
                    }
                }
            }
        },
        // Вьюха смайлов
        _viewSmile: async function (hide) {
            var _self = this;
            if (this.$smileList && this.$smileList.length) {
                if (this.$smileList.is(':visible')) {
                    this.$smileList.css('display', 'none');
                } else if (!hide) {
                    this.$smileList.css('display', 'flex');
                }
                return;
            }
            if (hide) return;
            if (this.$smileList) {
                this.$smileList.remove();
                this.$smileList = null;
            }
            this.$smileList = $('<div class="window smileList"></div>').appendTo('body');
            let packs = ["pack1", "pack2", "pack3", "pack4", "pack5", "pack6"];
            let tabs = `
                <div class="emoji-tabs-wrapper">
                    <button class="emoji-scroll-btn left">&#10094;</button>
                    <div class="emoji-tabs-container">
            `;
            let content = '<div class="emoji-content">';
            for (let i = 0; i < packs.length; i++) {
                let pack = packs[i];
                let emojis = await loadEmojiPack(pack);
                if (!emojis.length) continue;
                let firstEmoji = emojis[0];
                let displayStyle = i === 0 ? "grid" : "none";
                tabs += `
                    <button class="emoji-tab ${i === 0 ? 'active' : ''}" data-tab="${pack}">
                        <div class="emoji-tab-icon">
                            <img src="/img/em/${pack}/${firstEmoji}" alt="${pack}" width="40" height="40">
                        </div>
                    </button>
                `;
                content += `
                    <div class="emoji-list ${pack}" style="display: ${displayStyle};">
                        ${emojis.map(emoji => {
                            let emojiName = emoji.replace(/\.(png|gif)$/, '');
                            return `<div class="emoji" title="${emojiName}" data-emoji=":${emojiName}:">
                                <i class="em em-${emojiName}"></i>
                                <img src="/img/em/${pack}/${emoji}" width="40" height="40">
                            </div>`;
                        }).join('')}
                    </div>
                `;
            }
            tabs += '</div><button class="emoji-scroll-btn right">&#10095;</button></div>';
            content += '</div>';
            this.$smileList.append(tabs + content);
            this.$smileList.off('click.emojiTab').on('click.emojiTab', '.emoji-tab', function () {
                _self.$smileList.find('.emoji-tab').removeClass('active');
                $(this).addClass('active');
                let tab = $(this).data('tab');
                _self.$smileList.find('.emoji-list').hide();
                _self.$smileList.find('.emoji-list.' + tab).show();
            });
            this.$smileList.off('click.emojiScrollLeft').on('click.emojiScrollLeft', '.emoji-scroll-btn.left', function () {
                _self.$smileList.find('.emoji-tabs-container').animate({ scrollLeft: '-=100px' }, 200);
            });
            this.$smileList.off('click.emojiScrollRight').on('click.emojiScrollRight', '.emoji-scroll-btn.right', function () {
                _self.$smileList.find('.emoji-tabs-container').animate({ scrollLeft: '+=100px' }, 200);
            });
            this.$smileList.off('click.emojiPick').on('click.emojiPick', '.emoji', function () {
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
                    if (_self.$smileList) _self.$smileList.css('display', 'none');
                }
            });
        }
    };

    // Функция загрузки всех смайлов из папки
    async function loadEmojiPack(pack) {
        let allowedFormats = ['png', 'gif'];
        let response = await fetch(`/get_emojis.php?pack=${pack}`);
        let files = await response.json();
        return files.filter(file => allowedFormats.some(ext => file.endsWith('.' + ext)));
    }

    // === Интеграция ===
    function GameChat(uInfo){
        var core = new ChatCore(uInfo);
        var ui = new ChatUI(core);
        this.core = core;
        this.ui = ui;
        // Для обратной совместимости:
        this._send = function(){ ui._onSend(); };
        this._send_desktop = function(){ ui._onSendDesktop(); };
        this._action = function(){ core._action.apply(core, arguments); };
        this._upInfo = function(info){ core.updateUserInfo(info); };
        this._viewCommand = function(hide){ ui._viewCommand(hide); };
        this._viewSmile = function(hide){ ui._viewSmile(hide); };
        // и т.д. для всех внешних методов, если они вам нужны
    }
    global.GameChat = GameChat;

    // === Вспомогательные функции для совместимости ===
    function replaceEmoji(str){ return str; }
    function smile(input, emoji){ 
        // вставляет emoji в input на место курсора
        var val = $(input).val();
        var start = input.selectionStart, end = input.selectionEnd;
        $(input).val(val.substring(0, start) + emoji + val.substring(end));
        input.selectionStart = input.selectionEnd = start + emoji.length;
    }
    // showUserTooltip, user_to_chat_add и другие функции должны существовать в глобале

})(window, jQuery);
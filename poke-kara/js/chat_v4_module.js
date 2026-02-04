/*
  Chat UI v4 (separate module)
  ------------------------------------------------------------
  Goal: apply the agreed mockup-style layout and PM improvements
  WITHOUT editing core /js/makasimka.js.

  Features:
  - Variant B (full): PM inbox list on the left (desktop), search + pinned + unread.
  - PM search inside a dialog (no reactions).
  - Variant C: profanity display filter (toggle "18+") - hides message content.

  Requirements:
  - jQuery
  - Backend: /do/chat.php supports:
      chat=pm_history
      chat=profanity_dict
    Optional (if you add it): chat=pm_inbox, chat=user_lookup

  Config:
  - window.GC_CHAT_ENDPOINT = '/do/chat.php'

  Version marker:
  - window.GC_CHAT_V4_VERSION
  - <body data-gc-chat-v4="..."></body>
*/
(function (w, $) {
  'use strict';

  if (!$) return;

  var VERSION = '4.3.1-module';
  w.GC_CHAT_V4_VERSION = VERSION;

  var STORAGE_CFG = 'gc_chat_v4_cfg_v1';
  var STORAGE_PM_INDEX = 'gc_pm_index_v1';
  var STORAGE_PROF_DICT = 'chat_profanity_dict_v2';
  var STORAGE_PROF_CFG = 'chat_profanity_cfg';

  var DEFAULT_CFG = {
    enabled: true,
    redesign: true,
    inbox_enabled: true,
    pm_search_enabled: true,
    profanity_enabled: true,
    endpoint: '/do/chat.php'
  };

  function safeJsonParse(s, fallback) {
    try {
      var x = JSON.parse(s);
      return (x && typeof x === 'object') ? x : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function loadCfg() {
    var raw = null;
    try { raw = localStorage.getItem(STORAGE_CFG); } catch (e) {}
    var cfg = raw ? safeJsonParse(raw, {}) : {};
    var out = $.extend({}, DEFAULT_CFG, cfg);
    // Allow overriding endpoint globally.
    if (typeof w.GC_CHAT_ENDPOINT === 'string' && w.GC_CHAT_ENDPOINT) out.endpoint = w.GC_CHAT_ENDPOINT;
    w.GC_CHAT_ENDPOINT = out.endpoint;
    return out;
  }

  function saveCfg(cfg) {
    try { localStorage.setItem(STORAGE_CFG, JSON.stringify(cfg)); } catch (e) {}
  }

  var CFG = loadCfg();

  function fixChatUrl(url) {
    if (!url || typeof url !== 'string') return url;
    if (url === '/do/chat' || url === '/do/chat/') return CFG.endpoint;
    if (url.indexOf('/do/chat?') === 0) return CFG.endpoint + url.slice('/do/chat'.length);
    return url;
  }

  // Hardening: if old code still calls /do/chat, transparently route to /do/chat.php
  if ($.ajaxPrefilter) {
    $.ajaxPrefilter(function (options) {
      options.url = fixChatUrl(options.url);
    });
  }

  // Patch $.post (some code paths bypass ajaxPrefilter in older jQuery builds)
  (function patchPost() {
    var _post = $.post;
    $.post = function (url, data, success, dataType) {
      return _post.call(this, fixChatUrl(url), data, success, dataType);
    };
  })();

  function debounce(fn, wait) {
    var t = null;
    return function () {
      var ctx = this;
      var args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getUserId() {
    try {
      if (typeof w._userInfo !== 'undefined' && w._userInfo && w._userInfo.id) return parseInt(w._userInfo.id, 10) || 0;
    } catch (e) {}
    return 0;
  }

  function isMobile() {
    return $('.ChatBox.mobile:visible').length > 0 || (w.matchMedia && w.matchMedia('(max-width: 800px)').matches);
  }

  function findRoot() {
    var $root = $('.ChatBox:visible .DivChat').first();
    if (!$root.length) $root = $('.DivChat').first();
    return $root;
  }

  function ensureChatClass($root) {
    var $chat = $root.children('.Chat');
    if ($chat.length) return $chat;
    var $lower = $root.children('.chat');
    if ($lower.length) {
      $lower.addClass('Chat');
      return $lower;
    }
    // fallback: search deep
    $chat = $root.find('> .Chat').first();
    if ($chat.length) return $chat;
    $lower = $root.find('> .chat').first();
    if ($lower.length) {
      $lower.addClass('Chat');
      return $lower;
    }
    return $();
  }

  function getCategory($root) {
    return $root.find('> .Chat > .Category').first();
  }

  function getTalk($root) {
    return $root.find('> .Chat > .Talk').first();
  }

  function getActiveMessageBlock($root) {
    return getTalk($root).find('> .Message-Block.Active').first();
  }

  function getActiveChannelId($root) {
    var $mb = getActiveMessageBlock($root);
    if (!$mb.length) return '';
    return ($mb.attr('data-channel') || '') + '';
  }


  // Some pages may start without an .Active message block due to CSS selector mismatches.
  // Ensure at least one Message-Block is visible so messages never disappear.
  function ensureAnyActiveBlock($root) {
    var $talk = getTalk($root);
    if (!$talk.length) return;
    var $active = $talk.find('> .Message-Block.Active');
    if (!$active.length) $active = $talk.find('.Message-Block.Active').first();
    if ($active.length) return;
    var $first = $talk.find('> .Message-Block').first();
    if (!$first.length) $first = $talk.find('.Message-Block').first();
    if ($first.length) {
      $first.addClass('Active');
      // Make it visible even if legacy CSS keeps it display:none
      $first.css('display', 'block');
    }
  }

  function isPmChannelId(ch) {
    return /^id\d+_\d+$/.test(String(ch || ''));
  }

  function loadPmIndex() {
    var raw = null;
    try { raw = localStorage.getItem(STORAGE_PM_INDEX); } catch (e) {}
    var idx = raw ? safeJsonParse(raw, {}) : {};
    if (!idx || typeof idx !== 'object') idx = {};
    return idx;
  }

  function savePmIndex(idx) {
    try { localStorage.setItem(STORAGE_PM_INDEX, JSON.stringify(idx)); } catch (e) {}
  }

  function normalizeDialog(d) {
    var out = {
      channel: String(d.channel || ''),
      peerLogin: String(d.peerLogin || d.login || ''),
      peerId: d.peerId ? parseInt(d.peerId, 10) : 0,
      lastText: String(d.lastText || ''),
      lastTs: d.lastTs ? parseInt(d.lastTs, 10) : 0,
      unread: d.unread ? parseInt(d.unread, 10) : 0,
      pinned: !!d.pinned
    };
    return out;
  }

  function parsePmPeerFromChannel(channel) {
    var uid = getUserId();
    var m = String(channel || '').match(/^id(\d+)_(\d+)$/);
    if (!m) return { peerId: 0 };
    var a = parseInt(m[1], 10) || 0;
    var b = parseInt(m[2], 10) || 0;
    if (!uid) return { peerId: 0 };
    return { peerId: (uid === a ? b : a) };
  }

  // ----------------- UI: Inbox -----------------
  function inboxTemplate() {
    return (
      '<div class="GcInbox" aria-label="Личные сообщения">' +
        '<div class="GcInboxHeader">' +
          '<div class="GcInboxTitle">ЛС</div>' +
          '<div class="GcInboxHeaderRight">' +
            '<button type="button" class="GcInboxBtn" data-act="toggle" title="Скрыть/показать">⟷</button>' +
            '<button type="button" class="GcInboxBtn" data-act="new" title="Новый диалог">＋</button>' +
          '</div>' +
        '</div>' +
        '<div class="GcInboxSearch">' +
          '<input type="text" class="GcInboxSearchInput" placeholder="Поиск по ЛС" autocomplete="off" />' +
        '</div>' +
        '<div class="GcInboxFilters">' +
          '<button type="button" class="GcInboxPill is-active" data-filter="all">Все</button>' +
          '<button type="button" class="GcInboxPill" data-filter="unread">Непрочитанные</button>' +
        '</div>' +
        '<div class="GcInboxList" role="list"></div>' +
        '<div class="GcInboxFooter">' +
          '<span class="GcInboxHint">Подсказка: закреп — ★</span>' +
        '</div>' +
      '</div>'
    );
  }

  function renderInboxList($root, dialogs, q, filter) {
    var $inbox = $root.children('.GcInbox');
    if (!$inbox.length) return;
    var $list = $inbox.find('.GcInboxList');
    $list.empty();

    var query = (q || '').trim().toLowerCase();

    var items = dialogs
      .map(normalizeDialog)
      .filter(function (d) {
        if (!d.channel) return false;
        if (filter === 'unread' && !(d.unread > 0)) return false;
        if (!query) return true;
        return (d.peerLogin || '').toLowerCase().indexOf(query) >= 0 || (d.lastText || '').toLowerCase().indexOf(query) >= 0;
      })
      .sort(function (a, b) {
        if (a.pinned !== b.pinned) return a.pinned ? -1 : 1;
        if (a.unread !== b.unread) return (b.unread - a.unread);
        return (b.lastTs - a.lastTs);
      });

    if (!items.length) {
      $list.append('<div class="GcInboxEmpty">Нет диалогов</div>');
      return;
    }

    var active = getActiveChannelId($root);

    items.forEach(function (d) {
      var cls = 'GcInboxItem' + (d.channel === active ? ' is-active' : '') + (d.unread > 0 ? ' is-unread' : '');
      var badge = d.unread > 0 ? '<span class="GcInboxBadge">' + d.unread + '</span>' : '';
      var pin = d.pinned ? ' is-pinned' : '';
      var time = d.lastTs ? new Date(d.lastTs * 1000) : null;
      var hm = time ? String(time.getHours()).padStart(2,'0') + ':' + String(time.getMinutes()).padStart(2,'0') : '';

      var html =
        '<div class="' + cls + '" role="listitem" data-channel="' + escapeHtml(d.channel) + '">' +
          '<div class="GcInboxItemTop">' +
            '<div class="GcInboxName">' + escapeHtml(d.peerLogin || d.channel) + '</div>' +
            '<div class="GcInboxTopRight">' +
              (hm ? '<span class="GcInboxTime">' + hm + '</span>' : '') +
              badge +
            '</div>' +
          '</div>' +
          '<div class="GcInboxItemBottom">' +
            '<div class="GcInboxPreview">' + escapeHtml(d.lastText || '') + '</div>' +
            '<button type="button" class="GcInboxPin' + pin + '" title="Закреп" data-act="pin">★</button>' +
          '</div>' +
        '</div>';

      $list.append(html);
    });
  }

  function ensureInboxMounted($root) {
    if (!$root.length) return;
    if (!CFG.inbox_enabled) return;

    if (!$root.children('.GcInbox').length) {
      $root.prepend(inboxTemplate());
    }

    var $inbox = $root.children('.GcInbox');

    // Desktop collapse state
    $inbox.off('click.gcInbox').on('click.gcInbox', '[data-act="toggle"]', function () {
      $inbox.toggleClass('is-collapsed');
      try { localStorage.setItem('gc_inbox_collapsed', $inbox.hasClass('is-collapsed') ? '1' : '0'); } catch (e) {}
    });

    try {
      if (localStorage.getItem('gc_inbox_collapsed') === '1') $inbox.addClass('is-collapsed');
    } catch (e) {}

    // New dialog (minimal UX)
    $inbox.on('click.gcInbox', '[data-act="new"]', function () {
      var login = w.prompt('Логин игрока для ЛС:');
      if (!login) return;
      // We cannot reliably resolve userId without optional endpoint; open by creating a tab using login + generated channel (temporary).
      // If optional user_lookup is installed, resolve and compute channel.
      openPmByLogin($root, login);
    });

    // Filters
    $inbox.on('click.gcInbox', '.GcInboxPill', function () {
      $inbox.find('.GcInboxPill').removeClass('is-active');
      $(this).addClass('is-active');
      updateInbox($root);
    });

    // Search
    $inbox.on('input.gcInbox', '.GcInboxSearchInput', debounce(function () {
      updateInbox($root);
    }, 120));

    // Click item -> open
    $inbox.on('click.gcInbox', '.GcInboxItem', function (e) {
      if ($(e.target).closest('[data-act="pin"]').length) return;
      var ch = $(this).attr('data-channel');
      var idx = loadPmIndex();
      var d = idx[ch] ? normalizeDialog(idx[ch]) : { channel: ch, peerLogin: $(this).find('.GcInboxName').text() };
      openPm($root, d.channel, d.peerLogin);
    });

    // Pin toggle
    $inbox.on('click.gcInbox', '[data-act="pin"]', function () {
      var $item = $(this).closest('.GcInboxItem');
      var ch = $item.attr('data-channel');
      var idx = loadPmIndex();
      if (!idx[ch]) idx[ch] = { channel: ch };
      idx[ch].pinned = !idx[ch].pinned;
      savePmIndex(idx);
      updateInbox($root);
    });
  }

  // ----------------- PM open / sync -----------------

  function setRecipientLogin(login) {
    if (!login) return;
    try {
      var $a = $('#chat_user_to, #chat_user_to_desktop');
      $a.val(login);
    } catch (e) {}
  }

  function ensurePmTab($root, channel, login) {
    if (!channel) return;
    var $cat = getCategory($root);
    var $talk = getTalk($root);
    if (!$cat.length || !$talk.length) return;

    var tabCls = 'chat_move_channel_' + channel;
    if ($cat.find('.' + tabCls).length === 0) {
      var html =
        '<div class="Button ' + tabCls + '" data-channel="' + escapeHtml(channel) + '">' +
          '<i class="fas fa-user"></i> ' + escapeHtml(login || channel) +
          '<span class="closePmTab" title="Закрыть" style="margin-left:7px; cursor:pointer;">&times;</span>' +
        '</div>';
      $cat.append(html);
    }

    if ($talk.find('.Message-Block.Channel_' + channel).length === 0 && $talk.find('.Message-Block[data-channel="' + channel + '"]').length === 0) {
      $talk.append('<div class="Message-Block Channel_' + escapeHtml(channel) + '" data-channel="' + escapeHtml(channel) + '"></div>');
    }
  }

  function activateChannel($root, channel) {
    if (!channel) return;
    var $cat = getCategory($root);
    var $btn = $cat.find('.chat_move_channel_' + channel);
    if ($btn.length) {
      // Let core handler do its work
      $btn.trigger('click');
    }

    // Fallback: class-only activation
    $cat.find('.Button').removeClass('Active');
    $btn.addClass('Active');

    var $talk = getTalk($root);
    $talk.find('> .Message-Block').removeClass('Active').hide();
    var $mb = $talk.find('> .Message-Block.Channel_' + channel + ', > .Message-Block[data-channel="' + channel + '"]');
    $mb.addClass('Active').show();
  }

  function markRead($root, channel) {
    var idx = loadPmIndex();
    if (idx[channel]) {
      idx[channel].unread = 0;
      savePmIndex(idx);
    }
    // Also clear badge in category tab if exists
    var $cat = getCategory($root);
    $cat.find('.chat_move_channel_' + channel + ' > span').text('');
  }

  function openPm($root, channel, login) {
    ensurePmTab($root, channel, login);
    setRecipientLogin(login);
    activateChannel($root, channel);
    markRead($root, channel);
    updatePmSearchVisibility($root);
  }

  function openPmByLogin($root, login) {
    // Optional: try server lookup to compute channel deterministically
    var uid = getUserId();
    if (!uid) {
      // create a temporary channel
      var tmp = 'id0_0';
      openPm($root, tmp, login);
      return;
    }

    // If user_lookup exists, use it
    $.post(CFG.endpoint, { chat: 'user_lookup', q: login, limit: 5 })
      .done(function (resp) {
        if (!resp || resp.error) throw new Error('no');
        if (resp && resp.users && resp.users.length) {
          var u = resp.users[0];
          var peerId = parseInt(u.id, 10) || 0;
          var peerLogin = u.login || login;
          if (peerId) {
            var a = Math.min(uid, peerId);
            var b = Math.max(uid, peerId);
            var ch = 'id' + a + '_' + b;
            var idx = loadPmIndex();
            idx[ch] = normalizeDialog({ channel: ch, peerId: peerId, peerLogin: peerLogin, lastTs: Math.floor(Date.now() / 1000), lastText: '' });
            savePmIndex(idx);
            updateInbox($root);
            openPm($root, ch, peerLogin);
            return;
          }
        }
        // fallback
        openPm($root, 'id0_0', login);
      })
      .fail(function () {
        // fallback
        openPm($root, 'id0_0', login);
      });
  }

  // ----------------- PM Search -----------------

  function pmSearchTemplate() {
    return (
      '<div class="GcPmSearch" style="display:none">' +
        '<div class="GcPmSearchLeft">' +
          '<input type="text" class="GcPmSearchInput" placeholder="Поиск по ЛС" autocomplete="off" />' +
          '<span class="GcPmSearchCount">0/0</span>' +
        '</div>' +
        '<div class="GcPmSearchRight">' +
          '<button type="button" class="GcPmBtn" data-act="prev" title="Предыдущее">←</button>' +
          '<button type="button" class="GcPmBtn" data-act="next" title="Следующее">→</button>' +
          '<label class="GcPmChk" title="Скрывать не совпадающие">' +
            '<input type="checkbox" class="GcPmSearchFilter" /> Фильтр' +
          '</label>' +
          '<button type="button" class="GcPmBtn" data-act="deeper" title="Подгрузить старые и искать">Глубже</button>' +
          '<button type="button" class="GcPmBtn" data-act="clear" title="Очистить">Очистить</button>' +
        '</div>' +
      '</div>'
    );
  }

  var pmSearchState = {
    matches: [],
    idx: -1,
    query: '',
    filter: false,
    channel: ''
  };

  function ensurePmSearchMounted($root) {
    if (!$root.length) return;
    if (!CFG.pm_search_enabled) return;

    var $chat = ensureChatClass($root);
    if (!$chat.length) return;

    if ($chat.children('.GcPmSearch').length === 0) {
      // Place search bar under Category
      var $cat = getCategory($root);
      if ($cat.length) {
        $(pmSearchTemplate()).insertAfter($cat);
      } else {
        $chat.prepend(pmSearchTemplate());
      }
    }

    var $bar = $chat.find('> .GcPmSearch');

    $bar.off('input.gcPm').on('input.gcPm', '.GcPmSearchInput', debounce(function () {
      runPmSearch($root);
    }, 120));

    $bar.off('change.gcPm').on('change.gcPm', '.GcPmSearchFilter', function () {
      pmSearchState.filter = !!$(this).is(':checked');
      applyPmFilter($root);
    });

    $bar.off('click.gcPm').on('click.gcPm', '[data-act="prev"]', function () {
      jumpMatch($root, -1);
    });

    $bar.on('click.gcPm', '[data-act="next"]', function () {
      jumpMatch($root, +1);
    });

    $bar.on('click.gcPm', '[data-act="clear"]', function () {
      $bar.find('.GcPmSearchInput').val('');
      pmSearchState.query = '';
      clearHighlights($root);
      updatePmCount($root);
      applyPmFilter($root);
    });

    $bar.on('click.gcPm', '[data-act="deeper"]', function () {
      loadMoreHistoryAndSearch($root);
    });
  }

  function updatePmSearchVisibility($root) {
    var $chat = ensureChatClass($root);
    var $bar = $chat.find('> .GcPmSearch');
    if (!$bar.length) return;

    var ch = getActiveChannelId($root);
    pmSearchState.channel = ch;
    if (isPmChannelId(ch)) {
      $bar.show();
    } else {
      $bar.hide();
      // reset state when leaving PM
      pmSearchState.matches = [];
      pmSearchState.idx = -1;
      pmSearchState.query = '';
    }
  }

  function walkTextNodes(rootEl, cb) {
    var walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_TEXT, null, false);
    var node;
    while ((node = walker.nextNode())) {
      cb(node);
    }
  }

  function clearHighlights($root) {
    var $mb = getActiveMessageBlock($root);
    if (!$mb.length) return;

    $mb.find('.Post').each(function () {
      var el = this;
      if (el && el.dataset && el.dataset.gcOrigHtml) {
        el.innerHTML = el.dataset.gcOrigHtml;
        delete el.dataset.gcOrigHtml;
      }
    });

    $mb.find('.Message').removeClass('gc-pm-match gc-pm-match-active').removeClass('gc-pm-hide');
  }

  function highlightElement(el, q) {
    if (!el || !q) return;
    if (!el.dataset.gcOrigHtml) el.dataset.gcOrigHtml = el.innerHTML;

    // Remove previous marks by restoring first
    el.innerHTML = el.dataset.gcOrigHtml;

    var qq = String(q);
    var qLower = qq.toLowerCase();

    // Do not try to highlight if element contains images (to reduce risk of breaking smile markup)
    if (el.querySelector && el.querySelector('img')) return;

    walkTextNodes(el, function (textNode) {
      var text = textNode.nodeValue;
      if (!text) return;
      var idx = text.toLowerCase().indexOf(qLower);
      if (idx < 0) return;

      var frag = document.createDocumentFragment();
      var cur = 0;
      while (true) {
        idx = text.toLowerCase().indexOf(qLower, cur);
        if (idx < 0) break;
        if (idx > cur) frag.appendChild(document.createTextNode(text.slice(cur, idx)));
        var mark = document.createElement('mark');
        mark.className = 'GcHl';
        mark.textContent = text.slice(idx, idx + qq.length);
        frag.appendChild(mark);
        cur = idx + qq.length;
      }
      if (cur < text.length) frag.appendChild(document.createTextNode(text.slice(cur)));
      textNode.parentNode.replaceChild(frag, textNode);
    });
  }

  function applyPmFilter($root) {
    var $mb = getActiveMessageBlock($root);
    if (!$mb.length) return;

    if (!pmSearchState.filter || !pmSearchState.query) {
      $mb.find('.Message').removeClass('gc-pm-hide');
      return;
    }

    $mb.find('.Message').each(function () {
      var $m = $(this);
      if ($m.hasClass('gc-pm-match')) $m.removeClass('gc-pm-hide');
      else $m.addClass('gc-pm-hide');
    });
  }

  function updatePmCount($root) {
    var $chat = ensureChatClass($root);
    var $bar = $chat.find('> .GcPmSearch');
    if (!$bar.length) return;

    var total = pmSearchState.matches.length;
    var cur = (pmSearchState.idx >= 0 && total > 0) ? (pmSearchState.idx + 1) : 0;
    $bar.find('.GcPmSearchCount').text(cur + '/' + total);
  }

  function setActiveMatch($root, idx) {
    var $mb = getActiveMessageBlock($root);
    if (!$mb.length) return;

    $mb.find('.Message').removeClass('gc-pm-match-active');
    if (idx < 0 || idx >= pmSearchState.matches.length) {
      pmSearchState.idx = -1;
      updatePmCount($root);
      return;
    }

    pmSearchState.idx = idx;
    var el = pmSearchState.matches[idx];
    if (el) {
      $(el).addClass('gc-pm-match-active');
      try { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (e) {
        el.scrollIntoView(true);
      }
    }

    updatePmCount($root);
  }

  function jumpMatch($root, dir) {
    var total = pmSearchState.matches.length;
    if (!total) {
      setActiveMatch($root, -1);
      return;
    }

    var next = pmSearchState.idx;
    if (next < 0) next = 0;
    else next = (next + dir + total) % total;
    setActiveMatch($root, next);
  }

  function runPmSearch($root) {
    var $chat = ensureChatClass($root);
    var $bar = $chat.find('> .GcPmSearch');
    if (!$bar.length) return;

    var ch = getActiveChannelId($root);
    if (!isPmChannelId(ch)) {
      clearHighlights($root);
      return;
    }

    var q = ($bar.find('.GcPmSearchInput').val() || '').trim();
    pmSearchState.query = q;

    clearHighlights($root);

    if (!q) {
      pmSearchState.matches = [];
      pmSearchState.idx = -1;
      updatePmCount($root);
      applyPmFilter($root);
      return;
    }

    var $mb = getActiveMessageBlock($root);
    var matches = [];

    $mb.find('> .Message').each(function () {
      var $msg = $(this);
      var $post = $msg.find('> .Post').first();
      var txt = ($post.text() || '').toLowerCase();
      if (txt.indexOf(q.toLowerCase()) >= 0) {
        $msg.addClass('gc-pm-match');
        matches.push(this);
        if ($post.length) highlightElement($post.get(0), q);
      }
    });

    pmSearchState.matches = matches;
    pmSearchState.idx = matches.length ? 0 : -1;

    applyPmFilter($root);
    updatePmCount($root);
    if (matches.length) setActiveMatch($root, 0);
  }

  // ----------------- PM history load (optional) -----------------
  var pmHistoryState = {}; // channel -> {inited, beforeId, hasMore}

  function ensureHistoryState(channel) {
    if (!pmHistoryState[channel]) pmHistoryState[channel] = { inited: false, beforeId: 0, hasMore: true };
    return pmHistoryState[channel];
  }

  function renderHistoryMessage(m, uid) {
    var fromMe = (parseInt(m.user_id, 10) || 0) === uid;
    var login = fromMe ? (m.user_login || 'Вы') : (m.user_login || '');
    var time = m.msg_time || '';
    var text = m.user_msg || '';

    return (
      '<div class="Message' + (m.private_channel_id ? ' private' : '') + '" data-mid="' + escapeHtml(m.msg_id || m.id || '') + '">' +
        '<span class="Data">' + escapeHtml(time) + '</span>' +
        '<span class="User"><i class="fas fa-user"></i> ' + escapeHtml(login) + '<span class="DblDot">:</span></span>' +
        '<span class="Post">' + escapeHtml(text) + '</span>' +
      '</div>'
    );
  }

  function loadMoreHistoryAndSearch($root) {
    var ch = getActiveChannelId($root);
    if (!isPmChannelId(ch)) return;

    var st = ensureHistoryState(ch);
    if (!st.hasMore) return;

    var payload = { chat: 'pm_history', channel: ch, limit: 200 };
    if (st.inited && st.beforeId > 0) payload.before_id = st.beforeId;

    $.post(CFG.endpoint, payload)
      .done(function (resp) {
        if (!resp || typeof resp !== 'object') return;
        var hist = resp.history || {};
        var hasMore = !!resp.has_more;
        var nextBefore = parseInt(resp.next_before_id, 10) || 0;

        st.inited = true;
        st.hasMore = hasMore;
        st.beforeId = nextBefore;

        var $mb = getActiveMessageBlock($root);
        if (!$mb.length) return;

        var uid = getUserId();
        var html = '';
        Object.keys(hist).forEach(function (k) {
          var mm = hist[k];
          if (!mm) return;
          // Avoid duplicates
          if ($mb.find('[data-mid="' + k + '"]').length) return;
          mm.msg_id = k;
          html += renderHistoryMessage(mm, uid);
        });

        if (html) {
          $mb.prepend('<div class="Message system-msg gc-history-sep"><span class="Post">— загружено из истории —</span></div>');
          $mb.prepend(html);
          // Apply profanity filter to newly inserted messages
          applyProfanityToBlock($mb);
        }

        runPmSearch($root);
      })
      .fail(function () {
        // ignore
      });
  }

  // ----------------- Profanity filter -----------------
  var profanity = {
    enabled: true,
    dict: null,
    rx: null
  };

  function loadProfanityCfg() {
    var raw = null;
    try { raw = localStorage.getItem(STORAGE_PROF_CFG); } catch (e) {}
    var c = raw ? safeJsonParse(raw, {}) : {};
    if (!c || typeof c !== 'object') c = {};
    if (typeof c.enabled === 'boolean') profanity.enabled = c.enabled;
    else profanity.enabled = true;
  }

  function saveProfanityCfg() {
    try { localStorage.setItem(STORAGE_PROF_CFG, JSON.stringify({ enabled: profanity.enabled })); } catch (e) {}
  }

  function compileProfanity(dict) {
    if (!dict || typeof dict !== 'object') return null;
    var roots = Array.isArray(dict.roots) ? dict.roots : [];
    var regex = Array.isArray(dict.regex) ? dict.regex : [];
    var parts = [];

    roots.forEach(function (r) {
      r = String(r || '').trim();
      if (!r) return;
      // allow inside words
      parts.push(r.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    });

    regex.forEach(function (r) {
      r = String(r || '').trim();
      if (!r) return;
      parts.push('(?:' + r + ')');
    });

    if (!parts.length) return null;

    try {
      return new RegExp('(' + parts.join('|') + ')', 'i');
    } catch (e) {
      return null;
    }
  }

  function fetchProfanityDict() {
    var cached = null;
    try { cached = localStorage.getItem(STORAGE_PROF_DICT); } catch (e) {}
    var c = cached ? safeJsonParse(cached, null) : null;

    var ver = (c && c.version) ? parseInt(c.version, 10) : 0;
    var ua = (c && c.updated_at) ? parseInt(c.updated_at, 10) : 0;

    return $.post(CFG.endpoint, { chat: 'profanity_dict', ver: ver, ua: ua })
      .then(function (resp) {
        if (resp && resp.changed === 0 && c) {
          profanity.dict = c;
        } else if (resp && resp.dict) {
          profanity.dict = resp.dict;
          try { localStorage.setItem(STORAGE_PROF_DICT, JSON.stringify(resp.dict)); } catch (e) {}
        }
        profanity.rx = compileProfanity(profanity.dict);
      }, function () {
        // use cached only
        profanity.dict = c;
        profanity.rx = compileProfanity(profanity.dict);
      });
  }

  function shouldHideText(text) {
    if (!profanity.enabled) return false;
    if (!profanity.rx) return false;
    return profanity.rx.test(String(text || ''));
  }

  function hidePost($post) {
    var el = $post.get(0);
    if (!el) return;
    if (!el.dataset.gcProfOrig) el.dataset.gcProfOrig = el.innerHTML;
    el.innerHTML = '<span class="gc-profanity-placeholder">Сообщение скрыто фильтром мата</span>';
    $post.addClass('gc-profanity-hidden');
  }

  function restorePost($post) {
    var el = $post.get(0);
    if (!el) return;
    if (el.dataset.gcProfOrig) {
      el.innerHTML = el.dataset.gcProfOrig;
      delete el.dataset.gcProfOrig;
    }
    $post.removeClass('gc-profanity-hidden');
  }

  function applyProfanityToBlock($mb) {
    if (!$mb || !$mb.length) return;
    $mb.find('> .Message > .Post').each(function () {
      var $post = $(this);
      var txt = $post.text() || '';
      if (shouldHideText(txt)) hidePost($post);
      else restorePost($post);
    });
  }

  function ensureProfanityButton($root) {
    if (!CFG.profanity_enabled) return;

    var $wrap = $root.children('.DivRightButtons').find('> .Wrap').first();
    if (!$wrap.length) return;

    if ($wrap.find('.gc-prof-btn').length) return;

    // Insert a separator line and our button near the bottom
    var $line = $('<div class="Line"></div>');
    var $btn = $('<div class="Button gc-prof-btn" title="Фильтр мата (18+)">18+</div>');

    $wrap.append($line).append($btn);

    function syncBtn() {
      if (profanity.enabled) $btn.removeClass('NoActive');
      else $btn.addClass('NoActive');
    }

    syncBtn();

    $btn.on('click', function () {
      profanity.enabled = !profanity.enabled;
      saveProfanityCfg();
      syncBtn();
      // Re-apply across all blocks
      $root.find('.Talk > .Message-Block').each(function () {
        applyProfanityToBlock($(this));
      });
    });
  }

  function observeNewMessages($root) {
    var $talk = getTalk($root);
    if (!$talk.length || !w.MutationObserver) return;

    var obs = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        if (!m.addedNodes || !m.addedNodes.length) return;
        // Update inbox and apply profanity on newly inserted messages
        $(m.addedNodes).each(function () {
          var $n = $(this);
          if ($n.hasClass('Message')) {
            var $mb = $n.closest('.Message-Block');
            if ($mb.length) {
              // index update
              tryUpdateIndexFromMessage($root, $n, $mb);
              // profanity
              applyProfanityToBlock($mb);
            }
          } else {
            // Might be a wrapper
            var $mb2 = $n.find('.Message').closest('.Message-Block');
            if ($mb2.length) {
              applyProfanityToBlock($mb2);
            }
          }
        });
      });
    });

    obs.observe($talk.get(0), { childList: true, subtree: true });
  }

  // ----------------- Index updates -----------------
  function tryUpdateIndexFromTabs($root) {
    var $cat = getCategory($root);
    if (!$cat.length) return;

    var idx = loadPmIndex();

    $cat.find('.Button[data-channel]').each(function () {
      var $b = $(this);
      var ch = ($b.attr('data-channel') || '') + '';
      if (!isPmChannelId(ch)) return;

      var t = ($b.clone().children().remove().end().text() || '').trim();
      // remove close cross if present
      t = t.replace('×', '').trim();

      if (!idx[ch]) idx[ch] = { channel: ch };
      idx[ch].channel = ch;
      if (t) idx[ch].peerLogin = t;

      var unread = 0;
      var $sp = $b.find('> span').first();
      if ($sp.length) unread = parseInt(($sp.text() || '0'), 10) || 0;
      if (unread) idx[ch].unread = unread;
    });

    savePmIndex(idx);
  }

  function getLastMessagePreview($mb) {
    var $last = $mb.find('> .Message').last();
    if (!$last.length) return { text: '', ts: 0 };
    var text = ($last.find('> .Post').text() || '').trim();
    text = text.replace(/\s+/g, ' ');
    if (text.length > 70) text = text.slice(0, 70) + '…';
    // try to parse time
    var ts = Math.floor(Date.now() / 1000);
    return { text: text, ts: ts };
  }

  function tryUpdateIndexFromMessage($root, $msg, $mb) {
    var ch = ($mb.attr('data-channel') || '') + '';
    if (!isPmChannelId(ch)) return;

    var idx = loadPmIndex();
    if (!idx[ch]) idx[ch] = { channel: ch };

    // login from existing tab if possible
    var $tab = getCategory($root).find('.chat_move_channel_' + ch);
    if ($tab.length) {
      var t = ($tab.clone().children().remove().end().text() || '').trim();
      t = t.replace('×', '').trim();
      if (t) idx[ch].peerLogin = t;
    }

    var preview = getLastMessagePreview($mb);
    idx[ch].lastText = preview.text;
    idx[ch].lastTs = preview.ts;

    // unread: if message came in for non-active channel -> +1
    var active = getActiveChannelId($root);
    if (active !== ch) {
      idx[ch].unread = (parseInt(idx[ch].unread, 10) || 0) + 1;
    }

    // peerId derivation (optional)
    var p = parsePmPeerFromChannel(ch);
    if (p.peerId) idx[ch].peerId = p.peerId;

    savePmIndex(idx);
    updateInbox($root);
  }

  // ----------------- Inbox data sources -----------------
  function fetchInboxFromServer() {
    // optional endpoint
    return $.post(CFG.endpoint, { chat: 'pm_inbox', limit: 120 })
      .then(function (resp) {
        if (!resp || resp.error) throw new Error('no');
        if (!resp.dialogs || !Array.isArray(resp.dialogs)) throw new Error('no');
        return resp.dialogs;
      });
  }

  function mergeDialogsToIndex(dialogs) {
    var idx = loadPmIndex();
    dialogs.forEach(function (d) {
      var ch = String(d.channel || d.private_channel_id || '');
      if (!ch) return;
      idx[ch] = normalizeDialog({
        channel: ch,
        peerId: d.peer_id || d.peerId,
        peerLogin: d.peer_login || d.peerLogin || d.login,
        lastText: d.last_text || d.lastText || d.text,
        lastTs: d.last_ts || d.lastTs || d.ts,
        unread: d.unread || 0,
        pinned: !!(idx[ch] && idx[ch].pinned) // keep local pin
      });
    });
    savePmIndex(idx);
  }

  function getDialogsForRender($root) {
    tryUpdateIndexFromTabs($root);
    var idx = loadPmIndex();
    var arr = Object.keys(idx).map(function (k) {
      var d = idx[k];
      d.channel = k;
      return normalizeDialog(d);
    });
    // remove invalid
    arr = arr.filter(function (d) { return isPmChannelId(d.channel) || d.channel === 'id0_0'; });
    return arr;
  }

  function updateInbox($root) {
    var $inbox = $root.children('.GcInbox');
    if (!$inbox.length) return;

    var q = $inbox.find('.GcInboxSearchInput').val() || '';
    var filter = $inbox.find('.GcInboxPill.is-active').attr('data-filter') || 'all';

    var dialogs = getDialogsForRender($root);
    renderInboxList($root, dialogs, q, filter);
  }

  // ----------------- Redesign (layout toggles) -----------------
  function applyRedesign($root) {
    if (!CFG.redesign) return;
    $root.addClass('gc-v4');
  }

  function mountFabForMobile($root) {
    if ($root.find('.GcFabPm').length) return;

    var $fab = $('<button type="button" class="GcFabPm" title="ЛС"><span>ЛС</span></button>');
    $root.append($fab);

    $fab.on('click', function () {
      $root.toggleClass('gc-inbox-open');
    });

    // Close inbox when clicking outside (mobile)
    $(document).on('click.gcInboxOverlay', function (e) {
      if (!$root.hasClass('gc-inbox-open')) return;
      var $t = $(e.target);
      if ($t.closest('.GcInbox').length) return;
      if ($t.closest('.GcFabPm').length) return;
      $root.removeClass('gc-inbox-open');
    });
  }

  

  // Keep-alive: some pages rerender chat DOM; re-ensure critical classes.
  var gcV4Tick = null;
  function startKeepAlive() {
    if (gcV4Tick) return;
    gcV4Tick = setInterval(function(){
      try {
        var $root = findRoot();
        if (!$root.length) return;
        ensureChatClass($root);
        if (CFG.redesign) $root.addClass('gc-v4');
        // Safety: if active message block exists but hidden by legacy CSS, force display via inline style.
        var $mb = $root.find('> .Chat > .Talk .Message-Block.Active').first();
        if ($mb.length) $mb.css('display','block');
      } catch(e){}
    }, 800);
  }

// ----------------- Boot -----------------
  function mountOnce() {
    if (!CFG.enabled) return;

    var $root = findRoot();
    if (!$root.length) return;

    $('body').attr('data-gc-chat-v4', VERSION);

    ensureChatClass($root);
    applyRedesign($root);

    ensureAnyActiveBlock($root);

    ensureInboxMounted($root);
    ensurePmSearchMounted($root);

    loadProfanityCfg();
    fetchProfanityDict().always(function () {
      ensureProfanityButton($root);
      // Apply to existing
      $root.find('.Talk > .Message-Block').each(function () {
        applyProfanityToBlock($(this));
      });
    });

    // Observe channel switching and show/hide PM search
    var $cat = getCategory($root);
    $cat.on('click.gcV4', '.Button', debounce(function () {
      updateInbox($root);
      updatePmSearchVisibility($root);
      ensureAnyActiveBlock($root);
      // When switching into PM, re-run search if query exists
      var $chat = ensureChatClass($root);
      var $bar = $chat.find('> .GcPmSearch');
      if ($bar.length && $bar.is(':visible') && ($bar.find('.GcPmSearchInput').val() || '').trim()) {
        runPmSearch($root);
      }
    }, 50));

    // Update inbox initially
    updateInbox($root);
    updatePmSearchVisibility($root);

    // Try server inbox once (optional)
    fetchInboxFromServer()
      .done(function (dialogs) {
        mergeDialogsToIndex(dialogs);
        updateInbox($root);
      })
      .fail(function () {
        // ignore
      });

    // Observe new messages to update inbox/unread and apply profanity
    observeNewMessages($root);

    // Mobile FAB / overlay
    if (isMobile()) {
      mountFabForMobile($root);
    }

    startKeepAlive();

    // Extra safety: sync ChatProfanity helper if it exists
    try {
      if (w.ChatProfanity && typeof w.ChatProfanity.init === 'function') {
        if (typeof w._userInfo !== 'undefined' && w._userInfo) {
          w.ChatProfanity.init(w._userInfo, { endpoint: CFG.endpoint });
        }
      }
    } catch (e) {}

    // expose minimal debug helpers
    w.GC_CHAT_V4_DEBUG = {
      version: VERSION,
      cfg: function () { return CFG; },
      refreshInbox: function () { updateInbox(findRoot()); },
      openPm: function (channel, login) { openPm(findRoot(), channel, login); }
    };
  }

  // Mount now and also when chat appears later
  $(function () {
    mountOnce();

    if (w.MutationObserver) {
      var obs = new MutationObserver(debounce(function () {
        mountOnce();
      }, 200));
      obs.observe(document.documentElement, { childList: true, subtree: true });
    }
  });

})(window, window.jQuery);

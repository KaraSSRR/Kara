(function(){
  const app = document.getElementById('app');
  const flashArea = document.getElementById('flash-area');
  const modalRoot = document.getElementById('modal-root');
  const modalContent = document.getElementById('modal-content');
  const modalTitle = document.getElementById('modal-title');

  const shell = (document.body && document.body.dataset && document.body.dataset.shell) ? document.body.dataset.shell : 'vitrine';

  function escapeHtml(s){
    return String(s)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function isSameOrigin(href){
    if(!href) return false;
    if(href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    const u = new URL(href, window.location.href);
    return u.origin === window.location.origin;
  }

  async function fetchJson(url, options){
    const res = await fetch(url, options);
    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if(!ct.includes('application/json')){
      return { res, json: null };
    }
    let json = null;
    try { json = await res.json(); } catch (_) { json = null; }
    return { res, json };
  }

  function showFlash(type, message){
    if(!flashArea) return;
    flashArea.innerHTML = '<div class="flash '+(type==='ok'?'ok':'err')+'">'+escapeHtml(message)+'</div>';
    setTimeout(()=>{ flashArea.innerHTML=''; }, 3500);
  }

  function applyPayloadTo(targetEl, payload){
    if(!payload || typeof payload !== 'object') return;
    if(typeof payload.title === 'string'){
      document.title = payload.title;
      if(modalTitle && targetEl === modalContent) modalTitle.textContent = payload.title;
    }
    if(typeof payload.html === 'string' && targetEl){
      targetEl.innerHTML = payload.html;
    }

    if(payload.flash && typeof payload.flash === 'object'){
      const t = payload.flash.type || 'ok';
      const m = payload.flash.message || '';
      if(m) showFlash(t, m);
    }
  }

  function openModalShell(kind){
    if(!modalRoot) return;
    modalRoot.classList.add('open');
    modalRoot.setAttribute('aria-hidden','false');

    modalRoot.classList.toggle('kind-battle', kind === 'battle');
  }
  function closeModalShell(){
    if(!modalRoot) return;
    modalRoot.classList.remove('open','kind-battle');
    modalRoot.setAttribute('aria-hidden','true');
    if(modalContent) modalContent.innerHTML = '';
    if(modalTitle) modalTitle.textContent = '...';
  }

  async function openModal(url, push=true, kind=null){
    if(!modalRoot || !modalContent) return;

    const u = new URL(url, window.location.href);
    const path = u.pathname || '/';
    const inferredKind = kind || (path.startsWith('/battle') ? 'battle' : 'panel');

    openModalShell(inferredKind);

    const headers = {
      'X-Partial': '1',
      'X-Modal': '1',
      'Accept': 'application/json'
    };

    const { res, json } = await fetchJson(u.href, { headers, credentials: 'same-origin' });

    if(json && json.redirect){
      // follow redirect inside modal
      if(String(json.redirect) === '/locations'){
        closeModal(true);
        return;
      }
      return openModal(String(json.redirect), push, inferredKind);
    }

    if(!json){
      // fallback: full load
      window.location.href = u.href;
      return;
    }

    applyPayloadTo(modalContent, json);

    if(push){
      history.pushState({ modalUrl: u.href, base: '/locations' }, '', u.href);
    }
  }

  function closeModal(push=true){
    closeModalShell();
    if(shell === 'game' && push){
      history.pushState({ base: '/locations' }, '', '/locations');
    }
  }

  async function navigateApp(url, push=true){
    if(!app) { window.location.href = url; return; }

    const headers = { 'X-Partial': '1', 'Accept': 'application/json' };
    const { res, json } = await fetchJson(url, { headers, credentials: 'same-origin' });

    if(json && json.redirect){
      return navigateApp(json.redirect, push);
    }
    if(!json){
      window.location.href = url;
      return;
    }
    applyPayloadTo(app, json);

    if(push) history.pushState({}, '', url);
  }

  async function submitForm(form, target){
    const action = form.action || window.location.href;
    const method = (form.method || 'GET').toUpperCase();

    const fd = new FormData(form);
    const headers = {
      'X-Partial': '1',
      'Accept': 'application/json'
    };
    if(target === 'modal') headers['X-Modal'] = '1';

    const opts = { method, headers, credentials: 'same-origin' };
    if(method !== 'GET'){
      opts.body = fd;
    }

    const { res, json } = await fetchJson(action, opts);

    // Errors in unified format
    if(json && json.ok === false && json.error){
      const msg = json.error.message || 'Ошибка';
      showFlash('err', msg);
      return;
    }

    if(json && json.redirect){
      const to = String(json.redirect);
      if(target === 'modal'){
        if(to === '/locations'){ closeModal(true); return; }
        return openModal(to, true);
      }
      return navigateApp(to, true);
    }

    if(target === 'modal'){
      if(json) applyPayloadTo(modalContent, json);
      return;
    }

    if(json) applyPayloadTo(app, json);
  }

  // Click handling (links + modal)
  document.addEventListener('click', function(e){
    const a = e.target && e.target.closest ? e.target.closest('a') : null;
    if(!a) return;
    if(a.dataset.noSpa === '1') return;
    if(!isSameOrigin(a.getAttribute('href'))) return;

    const href = a.href;
    const u = new URL(href, window.location.href);
    const path = u.pathname || '/';

    // Game shell: everything (except /locations) opens as modal
    if(shell === 'game'){
      const isBase = (path === '/locations');
      const modalKind = a.dataset.modalKind || (path.startsWith('/battle') ? 'battle' : null);

      if(!isBase){
        e.preventDefault();
        openModal(href, true, modalKind);
        return;
      }

      // Base navigation (rare)
      e.preventDefault();
      navigateApp(href, true);
      return;
    }

    // Vitrine shell: normal SPA navigation
    e.preventDefault();
    navigateApp(href, true);
  }, true);

  // Buttons inside world to open modals
  document.addEventListener('click', function(e){
    const btn = e.target && e.target.closest ? e.target.closest('[data-open-modal]') : null;
    if(!btn) return;
    const url = btn.getAttribute('data-open-modal');
    if(!url) return;
    const kind = btn.getAttribute('data-modal-kind') || null;
    e.preventDefault();
    openModal(url, true, kind);
  }, true);

  // Modal close
  document.addEventListener('click', function(e){
    const el = e.target && e.target.closest ? e.target.closest('[data-modal-close="1"]') : null;
    if(!el) return;
    e.preventDefault();
    closeModal(true);
  }, true);

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && modalRoot && modalRoot.classList.contains('open')){
      closeModal(true);
    }
  });

  // Form submit (modal-aware)
  document.addEventListener('submit', function(e){
    const form = e.target;
    if(!(form instanceof HTMLFormElement)) return;
    if(form.dataset.noSpa === '1') return;

    // If in modal (or modal is open), keep inside modal
    const inModal = !!(modalRoot && modalRoot.classList.contains('open') && form.closest('#modal-root'));
    const target = (shell === 'game' && inModal) ? 'modal' : 'app';

    e.preventDefault();
    submitForm(form, target);
  }, true);

  // Popstate: game shell => open/close modal, keep /locations as base
  window.addEventListener('popstate', function(ev){
    if(shell !== 'game'){
      navigateApp(window.location.href, false);
      return;
    }

    const state = ev.state || {};
    const path = window.location.pathname || '/';

    if(path === '/locations'){
      closeModal(false);
      return;
    }

    // Modal URL in bar — open it without pushing
    openModal(window.location.href, false);
  });

  // On game base load: handle ?open=... (deep-link entry)
  if(shell === 'game'){
    const openEl = document.getElementById('open-modal-onload');
    let openUrl = null;

    if(openEl && openEl.dataset && openEl.dataset.url){
      try { openUrl = decodeURIComponent(openEl.dataset.url); } catch (_) { openUrl = openEl.dataset.url; }
    } else {
      const sp = new URLSearchParams(window.location.search);
      if(sp.has('open')){
        openUrl = sp.get('open');
        try { openUrl = decodeURIComponent(openUrl); } catch (_) {}
      }
    }

    if(openUrl){
      // Clean base URL first
      history.replaceState({ base: '/locations' }, '', '/locations');
      openModal(openUrl, true);
    }
  }

  // --------------------
  // Chat (game shell)
  // --------------------
  const chatRoot = document.getElementById('chat');
  if(shell === 'game' && chatRoot){
    const csrf = chatRoot.dataset.csrf || '';
    const tabs = Array.from(chatRoot.querySelectorAll('.chat-tab'));
    const messagesEl = document.getElementById('chat-messages');
    const formEl = document.getElementById('chat-form');
    const inputEl = document.getElementById('chat-input');
    const channelEl = document.getElementById('chat-channel');
    const toEl = document.getElementById('chat-to');
    const metaEl = document.getElementById('chat-meta');
    const peersEl = document.getElementById('chat-peers');
    const peerIdEl = document.getElementById('chat-peer-id');
    const clanTab = chatRoot.querySelector('.chat-tab[data-channel="clan"]');
    const footnoteEl = document.getElementById('chat-footnote');

    let activeChannel = 'global';
    let lastIds = { global: 0, trade: 0, clan: 0 };
    let dm = { peerId: null, afterId: 0 };
    let dmPeers = [];

    function fmtTime(dt){
      // dt is "YYYY-MM-DD HH:MM:SS"
      if(!dt) return '';
      const m = String(dt).match(/(\d{2}):(\d{2}):\d{2}$/);
      if(m) return m[1]+':'+m[2];
      return '';
    }

    function appendMessages(list){
      if(!messagesEl) return;
      const atBottom = (messagesEl.scrollTop + messagesEl.clientHeight) >= (messagesEl.scrollHeight - 30);

      for(const m of list){
        const who = (m.from && m.from.username) ? m.from.username : '...';
        const time = fmtTime(m.created_at);
        const cls = (m.kind === 'bot' || m.kind === 'system') ? 'msg bot' : 'msg';
        const html = `
          <div class="${cls}" data-id="${m.id}">
            <div class="msg-head">
              <span class="msg-who">${escapeHtml(who)}</span>
              <span class="msg-time muted">${escapeHtml(time)}</span>
            </div>
            <div class="msg-body">${escapeHtml(m.body)}</div>
          </div>
        `;
        messagesEl.insertAdjacentHTML('beforeend', html);
      }

      // keep last 300
      const nodes = messagesEl.querySelectorAll('.msg');
      if(nodes.length > 300){
        for(let i=0;i<nodes.length-300;i++) nodes[i].remove();
      }

      if(atBottom){
        messagesEl.scrollTop = messagesEl.scrollHeight;
      }
    }

    async function poll(){
      try {
        let url = '/api/chat/poll?channel=' + encodeURIComponent(activeChannel) + '&limit=50';

        if(activeChannel === 'dm'){
          if(dm.peerId === null || dm.peerId === undefined) return;
          url += '&peer_id=' + encodeURIComponent(dm.peerId) + '&after_id=' + encodeURIComponent(dm.afterId || 0);
        } else {
          url += '&after_id=' + encodeURIComponent(lastIds[activeChannel] || 0);
        }

        const { json } = await fetchJson(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if(!json || json.ok !== true) return;
        const data = (json && typeof json === 'object' && json.data) ? json.data : json;
        const msgs = (data && Array.isArray(data.messages)) ? data.messages : [];
        if(msgs.length){
          if(activeChannel === 'dm'){
            dm.afterId = msgs[msgs.length-1].id;
          } else {
            lastIds[activeChannel] = msgs[msgs.length-1].id;
          }
          appendMessages(msgs);
        }
      } catch (_) {}
    }

    function resetView(){
      if(messagesEl) messagesEl.innerHTML = '';
    }

    function renderPeers(){
      if(!peersEl) return;
      peersEl.innerHTML = '';
      if(!Array.isArray(dmPeers) || dmPeers.length === 0){
        peersEl.innerHTML = '<span class="muted">Пока нет диалогов.</span>';
        return;
      }
      for(const p of dmPeers){
        const pid = Number(p.user_id);
        const name = String(p.username || ('#'+pid));
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chat-peer' + ((dm.peerId === pid) ? ' active' : '') + ((pid === 0) ? ' bot' : '');
        b.textContent = name;
        b.addEventListener('click', () => selectPeer(pid, name));
        peersEl.appendChild(b);
      }
    }

    async function loadPeers(){
      if(!peersEl) return;
      try {
        const { json } = await fetchJson('/api/chat/dm/peers', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const data = (json && typeof json === 'object' && json.data) ? json.data : json;
        dmPeers = (json && json.ok === true && data && Array.isArray(data.peers)) ? data.peers : [];
        renderPeers();
      } catch (_) {}
    }

    function selectPeer(peerId, peerName){
      dm.peerId = (peerId === null || peerId === undefined) ? null : Number(peerId);
      dm.afterId = 0;
      if(peerIdEl) peerIdEl.value = (dm.peerId === null ? '' : String(dm.peerId));

      if(toEl){
        if(dm.peerId === 0){
          toEl.value = peerName || 'TradeBot';
          toEl.disabled = true;
        } else {
          toEl.disabled = false;
          toEl.value = peerName || '';
        }
      }
      resetView();
      renderPeers();
      poll();
    }

    function setChannel(ch){
      activeChannel = ch;
      if(channelEl) channelEl.value = ch;
      tabs.forEach(t => t.classList.toggle('active', t.dataset.channel === ch));

      if(metaEl) metaEl.hidden = (ch !== 'dm');
      if(footnoteEl){
        if(ch === 'trade') footnoteEl.textContent = 'Торговля: 1 сообщение / 30 сек и 30 / сутки. TradeBot подскажет правила.';
        else if(ch === 'dm') footnoteEl.textContent = 'ЛС: введите ник получателя. Диалоги сохраняются.';
        else if(ch === 'clan') footnoteEl.textContent = 'Клановый чат доступен только участникам клана.';
        else footnoteEl.textContent = 'Мир: общий чат. Пожалуйста, без спама.';
      }

      resetView();
      // reset cursor per channel (show last N starting now)
      if(ch === 'dm'){
        dm.afterId = 0;
        if(toEl) toEl.disabled = false;
        loadPeers();
        if(dm.peerId === null || dm.peerId === undefined){
          if(messagesEl) messagesEl.innerHTML = '<div class="muted">Выбери диалог выше или отправь первое сообщение.</div>';
        } else {
          renderPeers();
        }
      } else {
        lastIds[ch] = 0;
        if(toEl) { toEl.disabled = false; }
      }
      if(ch !== 'dm' || (dm.peerId !== null && dm.peerId !== undefined)) poll();
    }

    tabs.forEach(btn => {
      btn.addEventListener('click', () => {
        if(btn.disabled) return;
        setChannel(btn.dataset.channel || 'global');
      });
    });

    if(formEl){
      formEl.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = (inputEl && inputEl.value) ? inputEl.value.trim() : '';
        if(!msg) return;

        const fd = new FormData();
        fd.set('_csrf', csrf);
        fd.set('channel', activeChannel);
        fd.set('message', msg);

        if(activeChannel === 'dm'){
          const to = (toEl && toEl.value) ? toEl.value.trim() : '';
          if(!to){
            showFlash('err', 'Для ЛС укажи ник получателя.');
            return;
          }
          fd.set('to_username', to);
        }

        const { json } = await fetchJson('/api/chat/send', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        if(!json) return;

        if(json.ok === false && json.error){
          showFlash('err', json.error.message || 'Ошибка');
          return;
        }

        if(inputEl) inputEl.value = '';

        const data = (json && typeof json === 'object' && json.data) ? json.data : json;

        if(activeChannel === 'dm'){
          if(data && data.peer_id !== undefined && data.peer_id !== null){
            dm.peerId = Number(data.peer_id);
            dm.afterId = 0;
            if(peerIdEl) peerIdEl.value = String(dm.peerId);
            if(toEl && data.peer_username){
              toEl.disabled = false;
              toEl.value = String(data.peer_username);
            }
            await loadPeers();
            resetView();
          }
        }

        // pull new
        poll();
      });
    }

    // Meta (enable clan tab if in clan)
    (async function(){
      try{
        const { json } = await fetchJson('/api/chat/meta', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if(!json || json.ok !== true) return;
        const data = (json && typeof json === 'object' && json.data) ? json.data : json;
        if(clanTab){
          clanTab.disabled = !(data && data.clan);
        }
      } catch(_) {}
    })();

    // Start polling loop
    setChannel('global');
    setInterval(poll, 2000);
  }
})();
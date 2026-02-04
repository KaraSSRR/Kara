(function(){
  if (window.AntiBotSuspects) return;

  const CSS_ID = 'ab-suspects-style-v1';

  function notify(msg, type){
    try{
      if (window.Game && Game.notifications && typeof Game.notifications.main === 'function') {
        Game.notifications.main(msg, type || 'info');
        return;
      }
    }catch(e){}
    alert(msg);
  }

  function injectCss(){
    if (document.getElementById(CSS_ID)) return;
    const css = `
.ab-suspects-modal{ position:fixed; inset:0; z-index:2147482005; display:flex; align-items:center; justify-content:center; }
.ab-suspects-backdrop{ position:absolute; inset:0; background:rgba(0,0,0,.55); }
.ab-suspects-card{ position:relative; width:min(980px, calc(100vw - 18px)); max-height: min(720px, calc(100vh - 18px));
  background: rgba(22,16,40,.92); border:1px solid rgba(255,255,255,.14); border-radius:18px; color:#f6f4ff;
  box-shadow: 0 16px 50px rgba(0,0,0,.45); overflow:hidden; display:flex; flex-direction:column;
}
.ab-suspects-header{ padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px;
  border-bottom:1px solid rgba(255,255,255,.12);
}
.ab-suspects-title{ font-weight:700; letter-spacing:.2px; display:flex; align-items:center; gap:10px; }
.ab-suspects-close{ cursor:pointer; opacity:.85; font-size:22px; line-height:1; padding:4px 10px; border-radius:12px; }
.ab-suspects-close:hover{ background:rgba(255,255,255,.10); opacity:1; }
.ab-suspects-controls{ padding:10px 16px; display:flex; flex-wrap:wrap; gap:8px; border-bottom:1px solid rgba(255,255,255,.10); }
.ab-suspects-controls input, .ab-suspects-controls select{
  background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14);
  color:#f6f4ff; border-radius:12px; padding:8px 10px; outline:none;
}
.ab-suspects-controls button{
  background: rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.16);
  color:#f6f4ff; border-radius:12px; padding:8px 10px; cursor:pointer;
}
.ab-suspects-controls button:hover{ background: rgba(255,255,255,.14); }
.ab-suspects-body{ display:grid; grid-template-columns: 360px 1fr; min-height: 420px; }
.ab-suspects-list{ border-right:1px solid rgba(255,255,255,.10); overflow:auto; }
.ab-suspects-item{ padding:12px 14px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,.08); }
.ab-suspects-item:hover{ background: rgba(255,255,255,.06); }
.ab-suspects-item.active{ background: rgba(255,255,255,.10); }
.ab-suspects-item-top{ display:flex; justify-content:space-between; gap:10px; }
.ab-suspects-badge{ font-size:12px; padding:2px 8px; border-radius:999px; border:1px solid rgba(255,255,255,.18); opacity:.95; }
.ab-suspects-meta{ margin-top:6px; font-size:12px; opacity:.9; display:flex; gap:10px; flex-wrap:wrap; }
.ab-suspects-details{ padding:12px 14px; overflow:auto; }
.ab-kv{ display:grid; grid-template-columns: 220px 1fr; gap:8px 12px; margin:10px 0; }
.ab-kv div{ padding:6px 8px; background: rgba(255,255,255,.06); border-radius:12px; border:1px solid rgba(255,255,255,.10); }
.ab-kv .k{ opacity:.85; }
.ab-actions{ display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.ab-actions button{ background: rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.16); color:#f6f4ff; border-radius:12px; padding:8px 10px; cursor:pointer; }
.ab-actions button:hover{ background: rgba(255,255,255,.14); }
.ab-small{ font-size:12px; opacity:.9; }
.ab-divider{ height:1px; background:rgba(255,255,255,.10); margin:10px 0; }
.ab-mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:12px; white-space:pre-wrap; word-break:break-word; }
`;
    const st = document.createElement('style');
    st.id = CSS_ID;
    st.appendChild(document.createTextNode(css));
    document.head.appendChild(st);
  }

  function fmtTs(ts){
    if (!ts) return '';
    const d = new Date(ts * 1000);
    const pad = n => (n<10?'0':'')+n;
    return `${pad(d.getDate())}.${pad(d.getMonth()+1)} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }

  function badgeHtml(conf){
    const c = (conf || '').toUpperCase();
    return `<span class="ab-suspects-badge">${c}</span>`;
  }

  function safeJson(obj){
    try{ return JSON.stringify(obj, null, 2); }catch(e){ return String(obj); }
  }

  async function api(payload){
    return new Promise((resolve, reject)=>{
      $.ajax({
        url: '/do/antibot_suspects.php',
        type: 'POST',
        dataType: 'json',
        data: payload,
        success: resolve,
        error: function(xhr){
          let msg = 'Ошибка запроса.';
          try{
            const j = xhr.responseJSON;
            if (j && j.error) msg = j.error;
          }catch(e){}
          reject(new Error(msg));
        }
      });
    });
  }

  function buildModal(){
    injectCss();
    const $m = $(`
<div class="Modal ab-suspects-modal">
  <div class="ab-suspects-backdrop" data-close></div>
  <div class="ab-suspects-card">
    <div class="ab-suspects-header">
      <div class="ab-suspects-title"><i class="fal fa-user-secret"></i> Подозреваемые</div>
      <div class="ab-suspects-close" data-close title="Закрыть">&times;</div>
    </div>
    <div class="ab-suspects-controls">
      <select data-f="hours">
        <option value="6">6 часов</option>
        <option value="24" selected>24 часа</option>
        <option value="72">3 дня</option>
        <option value="168">7 дней</option>
      </select>
      <select data-f="confidence">
        <option value="ALL" selected>Все уровни</option>
        <option value="HIGH">HIGH</option>
        <option value="MED">MED</option>
        <option value="LOW">LOW</option>
      </select>
      <select data-f="status">
        <option value="ALL" selected>Все статусы</option>
        <option value="NEW">NEW</option>
        <option value="ACK">ACK</option>
        <option value="CLOSED">CLOSED</option>
      </select>
      <input data-f="q" type="text" placeholder="ID или логин…" style="flex:1; min-width:160px;">
      <button data-act="refresh"><i class="fal fa-sync"></i> Обновить</button>
    </div>
    <div class="ab-suspects-body">
      <div class="ab-suspects-list"></div>
      <div class="ab-suspects-details">
        <div class="ab-small">Выберите игрока слева, чтобы увидеть детали.</div>
      </div>
    </div>
  </div>
</div>`);
    $('body').append($m);

    $m.on('click', '[data-close]', function(){
      $m.remove();
    });

    return $m;
  }

  async function loadList($m){
    const hours = Number($m.find('[data-f="hours"]').val() || 24);
    const confidence = $m.find('[data-f="confidence"]').val() || 'ALL';
    const status = $m.find('[data-f="status"]').val() || 'ALL';
    const q = ($m.find('[data-f="q"]').val() || '').trim();

    $m.find('.ab-suspects-list').html('<div class="ab-small" style="padding:12px 14px;">Загрузка…</div>');
    $m.find('.ab-suspects-details').html('<div class="ab-small">Загрузка…</div>');

    try{
      const resp = await api({action:'list', hours, confidence, status, q});
      const items = resp.items || [];
      renderList($m, items);
      if (!items.length) {
        $m.find('.ab-suspects-details').html('<div class="ab-small">Нет данных за выбранный период.</div>');
      }
    }catch(e){
      notify('Не удалось загрузить список: ' + e.message, 'error');
      $m.find('.ab-suspects-list').html('<div class="ab-small" style="padding:12px 14px;">Ошибка загрузки.</div>');
      $m.find('.ab-suspects-details').html('<div class="ab-small">Ошибка загрузки.</div>');
    }
  }

  function renderList($m, items){
    const $list = $m.find('.ab-suspects-list');
    if (!items.length){
      $list.html('<div class="ab-small" style="padding:12px 14px;">Пусто.</div>');
      return;
    }

    const html = items.map(it=>{
      const reasons = (it.reasons || []).join(', ');
      return `
        <div class="ab-suspects-item" data-user="${it.user_id}">
          <div class="ab-suspects-item-top">
            <div><b>${it.login}</b> <span class="ab-small">#${it.user_id}</span></div>
            ${badgeHtml(it.confidence)}
          </div>
          <div class="ab-suspects-meta">
            <span>sev: <b>${it.severity}</b></span>
            <span>status: <b>${it.status}</b></span>
            <span>seen: ${fmtTs(it.last_seen_ts)}</span>
          </div>
          <div class="ab-small" style="margin-top:6px; opacity:.85;">${reasons ? ('Причины: ' + reasons) : ''}</div>
        </div>`;
    }).join('');

    $list.html(html);

    $list.off('click.ab').on('click.ab', '.ab-suspects-item', async function(){
      const uid = Number($(this).data('user'));
      $list.find('.ab-suspects-item').removeClass('active');
      $(this).addClass('active');
      await loadDetails($m, uid);
    });

    // auto-select first
    const firstId = Number(items[0].user_id);
    $list.find(`.ab-suspects-item[data-user="${firstId}"]`).addClass('active');
    loadDetails($m, firstId);
  }

  async function loadDetails($m, userId){
    const hours = Number($m.find('[data-f="hours"]').val() || 24);
    const $d = $m.find('.ab-suspects-details');
    $d.html('<div class="ab-small">Загрузка деталей…</div>');

    try{
      const resp = await api({action:'details', user_id:userId, hours});
      renderDetails($m, resp.case, resp.snapshots || []);
    }catch(e){
      $d.html('<div class="ab-small">Ошибка загрузки деталей.</div>');
      notify('Не удалось загрузить детали: ' + e.message, 'error');
    }
  }

  function renderDetails($m, c, snaps){
    const $d = $m.find('.ab-suspects-details');
    if (!c){
      $d.html('<div class="ab-small">Нет данных.</div>');
      return;
    }

    const reasons = (c.reasons || []).join(', ');
    const evidence = c.evidence || {};

    const lastSnap = snaps && snaps.length ? snaps[0] : null;

    const html = `
      <div>
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
          <div>
            <div style="font-size:18px; font-weight:800;">${c.login} <span class="ab-small">#${c.user_id}</span></div>
            <div class="ab-small">first_seen: ${fmtTs(c.first_seen_ts)} · last_seen: ${fmtTs(c.last_seen_ts)}</div>
          </div>
          <div style="display:flex; gap:8px; align-items:center;">
            ${badgeHtml(c.confidence)}
            <span class="ab-suspects-badge">sev ${c.severity}</span>
            <span class="ab-suspects-badge">${c.status}</span>
          </div>
        </div>

        <div class="ab-divider"></div>

        <div class="ab-small"><b>Причины:</b> ${reasons || '—'}</div>

        <div class="ab-kv" style="margin-top:10px;">
          <div class="k">snapshot_id</div><div>${c.snapshot_id || '—'}</div>
          <div class="k">evidence</div><div class="ab-mono">${safeJson(evidence)}</div>
        </div>

        ${lastSnap ? `
        <div class="ab-divider"></div>
        <div style="font-weight:700;">Последний snapshot</div>
        <div class="ab-kv">
          <div class="k">bucket</div><div>${fmtTs(lastSnap.bucket_start_ts)} (+${lastSnap.bucket_len_sec}s)</div>
          <div class="k">reqs</div><div>${lastSnap.reqs}</div>
          <div class="k">scores</div><div>avg=${lastSnap.suspicious_avg} · max=${lastSnap.suspicious_max}</div>
          <div class="k">trap_total</div><div>${lastSnap.trap_total}</div>
          <div class="k">flags</div><div>webdriver=${lastSnap.webdriver_flag} · headless=${lastSnap.headless_flag}</div>
          <div class="k">last_url</div><div class="ab-mono">${lastSnap.last_url || ''}</div>
          <div class="k">ip</div><div class="ab-mono">${lastSnap.ip || ''}</div>
        </div>
        ` : ''}

        <div class="ab-divider"></div>

        <div class="ab-actions">
          <button data-set="ACK"><i class="fal fa-check"></i> ACK</button>
          <button data-set="CLOSED"><i class="fal fa-times"></i> CLOSED</button>
          <button data-set="NEW"><i class="fal fa-undo"></i> вернуть в NEW</button>
        </div>

        <div class="ab-small" style="margin-top:10px;">Snapshots в периоде: <b>${snaps.length}</b></div>
        ${snaps.length ? `<div class="ab-mono" style="margin-top:8px;">${snaps.slice(0,20).map(s=>`${fmtTs(s.bucket_start_ts)} | avg=${s.suspicious_avg} max=${s.suspicious_max} trap=${s.trap_total} wd=${s.webdriver_flag} reqs=${s.reqs}`).join('\n')}</div>` : ''}
      </div>
    `;

    $d.html(html);

    $d.off('click.abset').on('click.abset', '[data-set]', async function(){
      const st = $(this).data('set');
      try{
        await api({action:'set_status', user_id:c.user_id, status:st});
        notify('Статус обновлён: ' + st, 'success');
        // перезагрузим список, чтобы обновился статус
        loadList($m);
      }catch(e){
        notify('Не удалось обновить статус: ' + e.message, 'error');
      }
    });
  }

  window.AntiBotSuspects = {
    open: function(){
      // если уже открыто — не дублируем
      if (document.querySelector('.ab-suspects-modal')) return;
      const $m = buildModal();
      $m.on('click', '[data-act="refresh"]', function(){ loadList($m); });
      // enter in search
      $m.on('keydown', '[data-f="q"]', function(e){
        if (e.key === 'Enter') loadList($m);
      });
      loadList($m);
    }
  };
})();
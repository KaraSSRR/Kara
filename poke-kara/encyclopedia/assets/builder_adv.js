(function(){
  if (!document.querySelector('.js-builder-v2')) return;

  const BASE = (window.ENC_BASE || '');
  const cache = new Map();

  function clamp(n, min, max){
    n = Number.isFinite(n) ? n : 0;
    if (n < min) return min;
    if (n > max) return max;
    return n;
  }
  function normEv(v){
    v = parseInt(v||'0',10) || 0;
    v = clamp(v, 0, 252);
    v = Math.round(v / 4) * 4;
    v = clamp(v, 0, 252);
    return v;
  }
  function normIv(v){
    v = parseInt(v||'0',10) || 0;
    return clamp(v, 0, 31);
  }

  function showMsg(text){
    const box = document.querySelector('.js-builder-msg');
    if (!box) return;
    if (!text){
      box.style.display = 'none';
      box.textContent = '';
      return;
    }
    box.style.display = 'block';
    box.textContent = text;
  }

  async function fetchPokemonData(id, form){
    id = parseInt(id || '0', 10) || 0;
    if (!id) return null;
    const f = (form || '').trim();
    const key = `${id}|${f}`;
    if (cache.has(key)) return cache.get(key);

    const url = `${BASE}/api/pokemon_data.php?id=${encodeURIComponent(String(id))}&form=${encodeURIComponent(f)}`;
    const p = fetch(url, {headers:{'Accept':'application/json'}})
      .then(r=>r.json())
      .then(j=>{
        if (!j || !j.ok) return null;
        return j;
      })
      .catch(()=>null);

    cache.set(key, p);
    return p;
  }

  function getNatureMult(nature, statKey){
    const n = (window.ENC_NATURES && window.ENC_NATURES[nature]) ? window.ENC_NATURES[nature] : null;
    if (!n || !n.plus || !n.minus) return 1.0;
    if (statKey === n.plus) return 1.1;
    if (statKey === n.minus) return 0.9;
    return 1.0;
  }

  function calcStat(base, iv, ev, level, nature, statKey){
    base = parseInt(base||0,10) || 0;
    iv = parseInt(iv||0,10) || 0;
    ev = parseInt(ev||0,10) || 0;
    level = parseInt(level||100,10) || 100;
    level = clamp(level, 1, 100);

    const evPart = Math.floor(ev / 4);
    if (statKey === 'hp') {
      return Math.floor(((2*base + iv + evPart) * level) / 100) + level + 10;
    }
    const val = Math.floor(((2*base + iv + evPart) * level) / 100) + 5;
    const mult = getNatureMult(nature, statKey);
    return Math.floor(val * mult);
  }

  function parseIvCode(code){
    code = (code || '').trim();
    if (!code) return null;
    const parts = code.split('/').map(s=>s.trim()).filter(Boolean);
    if (parts.length !== 6) return null;
    const nums = parts.map(p=>{
      if (!/^-?\d+$/.test(p)) return null;
      return normIv(parseInt(p,10)||0);
    });
    if (nums.some(v=>v===null)) return null;
    return {hp:nums[0], atk:nums[1], def:nums[2], satk:nums[3], sdef:nums[4], spd:nums[5]};
  }

  function buildIvCode(iv){
    const keys=['hp','atk','def','satk','sdef','spd'];
    return keys.map(k=>String(normIv(iv[k]))).join('/');
  }

  function statKeys(){
    return ['hp','atk','def','satk','sdef','spd'];
  }

  function readEvIv(slotEl, kind){
    const out = {hp:0, atk:0, def:0, satk:0, sdef:0, spd:0};
    slotEl.querySelectorAll(`.js-${kind}`).forEach(inp=>{
      const k = inp.dataset.stat;
      if (!k || !(k in out)) return;
      out[k] = parseInt(inp.value||'0',10) || 0;
    });
    return out;
  }

  function writeEvIv(slotEl, kind, data){
    // numeric inputs
    slotEl.querySelectorAll(`.js-${kind}`).forEach(inp=>{
      const k = inp.dataset.stat;
      if (!k || !(k in data)) return;
      inp.value = String(data[k]);
    });
    // ranges
    slotEl.querySelectorAll(`.js-${kind}-range`).forEach(inp=>{
      const k = inp.dataset.stat;
      if (!k || !(k in data)) return;
      inp.value = String(data[k]);
    });
  }

  function sumStats(obj){
    return Object.values(obj).reduce((a,b)=>a+(parseInt(b||0,10)||0),0);
  }

  function enforceEv(slotEl, activeInput){
    const ev = readEvIv(slotEl, 'ev');
    for (const k of statKeys()) ev[k] = normEv(ev[k]);

    let total = sumStats(ev);
    if (total > 510 && activeInput){
      const k = activeInput.dataset.stat;
      if (k && (k in ev)) {
        const over = total - 510;
        ev[k] = normEv(ev[k] - over);
        total = sumStats(ev);
      }
    }

    writeEvIv(slotEl, 'ev', ev);

    const leftEl = slotEl.querySelector('.js-ev-left');
    const totalEl = slotEl.querySelector('.js-ev-total');
    if (leftEl) leftEl.textContent = String(510 - total);
    if (totalEl) totalEl.textContent = String(total);
  }

  function enforceIv(slotEl){
    const iv = readEvIv(slotEl, 'iv');
    for (const k of statKeys()) iv[k] = normIv(iv[k]);
    writeEvIv(slotEl, 'iv', iv);

    const code = buildIvCode(iv);
    const codeInput = slotEl.querySelector('.js-iv-code');
    if (codeInput && document.activeElement !== codeInput) {
      codeInput.value = code;
    }
  }

  function updateSprite(slotEl, pokemonId, form){
    const img = slotEl.querySelector('.js-sprite');
    if (!img) return;

    const id = parseInt(pokemonId||0,10)||0;
    img.dataset.fallbackIndex = '0';

    if (!id) {
      img.onerror = null;
      img.src = (window.ENC_SPRITE_PLACEHOLDER || '');
      img.style.opacity = '0.35';
      return;
    }

    const p = String(id).padStart(3,'0');
    const f = (form || '').trim();
    const fileForm = f ? `${p}_${f}.png` : `${p}.png`;

    const animUrl = `${(window.ENC_SPRITE_ANIM_DIR || '')}/${fileForm}`;
    const pokedexForm = `${(window.ENC_SPRITE_POKEDEX_DIR || '')}/${fileForm}`;
    const pokedexBase = `${(window.ENC_SPRITE_POKEDEX_DIR || '')}/${p}.png`;
    const placeholder = (window.ENC_SPRITE_PLACEHOLDER || '');

    const fallbacks = [];
    if (f) fallbacks.push(pokedexForm);
    fallbacks.push(pokedexBase);
    if (placeholder) fallbacks.push(placeholder);

    img.onerror = function(){ if (window.encImgFallback) window.encImgFallback(img, fallbacks); };
    img.src = animUrl;
    img.style.opacity = '1';
  }

  function updateTeamItem(slotEl){
    const slotNum = String(slotEl.dataset.slot || '');
    if (!slotNum) return;

    const btn = document.querySelector(`.js-team-item[data-slot="${slotNum}"]`);
    if (!btn) return;

    const pid = parseInt(slotEl.querySelector('.js-pokemon-id')?.value||'0',10)||0;
    const form = (slotEl.querySelector('.js-form')?.value||'').trim();
    const name = (slotEl.querySelector('.js-pokemon-name')?.value||'').trim();

    const labelEl = btn.querySelector('.js-team-name');
    const subEl = btn.querySelector('.js-team-sub');
    if (labelEl) labelEl.textContent = name ? name : (`Слот ${slotNum}`);

    const formSel = slotEl.querySelector('.js-form');
    const formLabel = formSel && formSel.options && formSel.selectedIndex >= 0 ? (formSel.options[formSel.selectedIndex].textContent || '') : '';
    if (subEl) subEl.textContent = (pid ? (formLabel || 'Обычная форма') : 'Пусто');

    const img = btn.querySelector('.js-team-sprite');
    if (!img) return;

    img.dataset.fallbackIndex = '0';
    if (!pid){
      img.onerror = null;
      img.src = (window.ENC_SPRITE_PLACEHOLDER || '');
      img.style.opacity = '0.35';
      return;
    }

    const p = String(pid).padStart(3,'0');
    const f = (form || '').trim();
    const fileForm = f ? `${p}_${f}.png` : `${p}.png`;

    const pokedexForm = `${(window.ENC_SPRITE_POKEDEX_DIR || '')}/${fileForm}`;
    const pokedexBase = `${(window.ENC_SPRITE_POKEDEX_DIR || '')}/${p}.png`;
    const placeholder = (window.ENC_SPRITE_PLACEHOLDER || '');

    const fallbacks = [];
    if (f) fallbacks.push(pokedexForm);
    fallbacks.push(pokedexBase);
    if (placeholder) fallbacks.push(placeholder);

    img.onerror = function(){ if (window.encImgFallback) window.encImgFallback(img, fallbacks); };
    // start with form if present, else base
    img.src = f ? pokedexForm : pokedexBase;
    img.style.opacity = '1';
  }

  function setSelectOptions(select, options){
    if (!select) return;
    const old = String(select.value||'');
    select.innerHTML = '';
    for (const opt of options){
      select.appendChild(opt);
    }
    const exists = Array.from(select.options).some(o=>o.value===old);
    select.value = exists ? old : (select.options[0] ? select.options[0].value : '');
  }

  function renderForms(slotEl, forms){
    const sel = slotEl.querySelector('.js-form');
    if (!sel) return;

    const opts=[];
    for (const f of (forms||[])){
      const o=document.createElement('option');
      o.value = f.form || '';
      let txt = (f.label || (f.form||'')) || 'Обычная';
      if (parseInt(f.start||1,10)===0) txt += ' (Mega)';
      o.textContent = txt;
      opts.push(o);
    }
    if (!opts.length) {
      const o=document.createElement('option');
      o.value='';
      o.textContent='Обычная';
      opts.push(o);
    }
    setSelectOptions(sel, opts);
  }

  function renderAbilities(slotEl, abilities){
    const sel = slotEl.querySelector('.js-ability');
    if (!sel) return;

    const opts=[];
    const o0=document.createElement('option');
    o0.value='0';
    o0.textContent='—';
    opts.push(o0);

    const labels={slot1:'Обычная 1',slot2:'Обычная 2',hidden:'Скрытая'};
    for (const k of ['slot1','slot2','hidden']){
      const a = abilities && abilities[k] ? abilities[k] : null;
      if (!a || !a.id) continue;
      const o=document.createElement('option');
      o.value = String(a.id);
      const nm = (a.name_rus || a.name || ('#'+a.id));
      o.textContent = `${labels[k]}: ${nm}`;
      opts.push(o);
    }

    setSelectOptions(sel, opts);
  }

  function moveLabel(move){
    if (!move) return 'Неизвестная атака';
    const nm = (move.name_rus || move.name || '');
    return nm ? nm : 'Неизвестная атака';
  }

  function typeRu(t){
    const m = {
      normal:'Нормал', fire:'Огонь', water:'Вода', electric:'Электро', grass:'Трава', ice:'Лёд',
      fighting:'Бой', poison:'Яд', ground:'Земля', flying:'Летающий', psychic:'Психо', bug:'Жук',
      rock:'Камень', ghost:'Призрак', dragon:'Дракон', dark:'Тьма', steel:'Сталь', fairy:'Фея'
    };
    const k = String(t||'').toLowerCase().trim();
    return m[k] || (t ? String(t) : '—');
  }

  function moveCatRu(cat){
    const c = String(cat||'').toLowerCase().trim();
    if (c === 'physical' || c === 'phys' || c === '1') return 'Физ.';
    if (c === 'special' || c === 'spec' || c === '2') return 'Спец.';
    if (c === 'status' || c === 'stat' || c === '0') return 'Стат.';
    return (cat ? String(cat) : '—');
  }

  function renderMoves(slotEl, moves){
    const map = (moves && moves.map) ? moves.map : {};
    const lvl = (moves && moves.lvl) ? moves.lvl : [];
    const tm = (moves && moves.tm) ? moves.tm : [];
    const hm = (moves && moves.hm) ? moves.hm : [];

    slotEl.querySelectorAll('.js-move').forEach(sel=>{
      const prev = String(sel.value||'0');

      sel.innerHTML='';
      const o0=document.createElement('option');
      o0.value='0';
      o0.textContent='—';
      sel.appendChild(o0);

      const grpLvl=document.createElement('optgroup');
      grpLvl.label='По уровню';
      lvl.forEach(it=>{
        const mid = String(it.id);
        const m = map[mid];
        if (!m) return;
        const o=document.createElement('option');
        o.value = String(m.id);
        const lv = parseInt(it.lvl||0,10) || 0;
        o.textContent = (lv ? `Ур. ${lv} • ` : '') + moveLabel(m);
        grpLvl.appendChild(o);
      });
      if (grpLvl.children.length) sel.appendChild(grpLvl);

      const grpTm=document.createElement('optgroup');
      grpTm.label='ТМ';
      tm.forEach(mid=>{
        const m = map[String(mid)];
        if (!m) return;
        const o=document.createElement('option');
        o.value=String(m.id);
        o.textContent = moveLabel(m);
        grpTm.appendChild(o);
      });
      if (grpTm.children.length) sel.appendChild(grpTm);

      const grpHm=document.createElement('optgroup');
      grpHm.label='НМ';
      hm.forEach(mid=>{
        const m = map[String(mid)];
        if (!m) return;
        const o=document.createElement('option');
        o.value=String(m.id);
        o.textContent = moveLabel(m);
        grpHm.appendChild(o);
      });
      if (grpHm.children.length) sel.appendChild(grpHm);

      // restore
      const exists = Array.from(sel.options).some(o=>o.value===prev);
      if (exists) {
        sel.value = prev;
      } else if (prev !== '0' && /^\d+$/.test(prev)) {
        const o=document.createElement('option');
        o.value = prev;
        o.textContent = 'Неизвестная атака';
        o.title = `ID атаки: ${prev}`;
        sel.appendChild(o);
        sel.value = prev;
      } else {
        sel.value = '0';
      }
    });
  }

  function renderMoveDex(slotEl, moves){
    const body = slotEl.querySelector('.js-move-dex-body');
    if (!body) return;

    body.innerHTML = '';
    const map = (moves && moves.map) ? moves.map : {};
    const lvl = (moves && moves.lvl) ? moves.lvl : [];
    const tm  = (moves && moves.tm) ? moves.tm : [];
    const hm  = (moves && moves.hm) ? moves.hm : [];

    const rows = [];

    lvl.forEach(it=>{
      const mid = String(it.id);
      const m = map[mid];
      if (!m) return;
      const lv = parseInt(it.lvl||0,10)||1;
      rows.push({kind:'lvl', order: 1, lvl: lv, src:`Ур. ${lv}`, move:m});
    });
    tm.forEach(id=>{
      const m = map[String(id)];
      if (!m) return;
      rows.push({kind:'tm', order: 2, lvl: 0, src:'ТМ', move:m});
    });
    hm.forEach(id=>{
      const m = map[String(id)];
      if (!m) return;
      rows.push({kind:'hm', order: 3, lvl: 0, src:'НМ', move:m});
    });

    rows.sort((a,b)=>{
      if (a.order !== b.order) return a.order - b.order;
      if (a.lvl !== b.lvl) return a.lvl - b.lvl;
      const na = moveLabel(a.move), nb = moveLabel(b.move);
      return na.localeCompare(nb, 'ru');
    });

    const targetRu = (t)=>{
      if (t === null || t === undefined) return '';
      const s = String(t).trim();
      if (!s) return '';
      const key = s.toLowerCase();
      const map = {
        'self':'Себя','ally':'Союзник','adjacentally':'Соседний союзник','adjacentfoe':'Соседний враг',
        'adjacentfoesorally':'Соседняя цель','alladjacentfoes':'Все соседние враги','alladjacent':'Все рядом',
        'all':'Все','allies':'Все союзники','foes':'Все враги','foeside':'Сторона врагов','allyside':'Сторона союзников',
        'randomnormal':'Случайная цель','any':'Любая цель','normal':'Одна цель'
      };
      if (map[key]) return map[key];
      if (/^\d+$/.test(key)) return '';
      return s;
    };

    const mkTypeBadge = (type)=>{
      const t = String(type||'').toLowerCase().trim();
      if (!t) return null;
      const cls = (t === 'fly') ? 'flying' : t;
      const sp = document.createElement('span');
      sp.className = `type-badge t-${cls}`;
      sp.textContent = typeRu(cls);
      return sp;
    };

    const mkCatBadge = (cat)=>{
      const c = String(cat||'').toLowerCase().trim();
      const sp = document.createElement('span');
      let extra = '';
      if (c === 'physical' || c === 'phys') extra = ' enc-badge--physical';
      else if (c === 'special' || c === 'spec') extra = ' enc-badge--special';
      else if (c === 'status') extra = ' enc-badge--status';
      sp.className = `enc-badge enc-badge--cat${extra}`;
      sp.textContent = moveCatRu(c);
      return sp;
    };

    const addChip = (rowEl, label, value, cls)=>{
      const sp = document.createElement('span');
      sp.className = 'enc-chip' + (cls ? (' ' + cls) : '');
      sp.appendChild(document.createTextNode(label + ' '));
      const b = document.createElement('b');
      b.textContent = value;
      sp.appendChild(b);
      rowEl.appendChild(sp);
    };

    const addFlag = (rowEl, icon, title)=>{
      const sp = document.createElement('span');
      sp.className = 'enc-chip enc-chip--flag';
      sp.title = title;
      sp.textContent = icon;
      rowEl.appendChild(sp);
    };

    const mkParams = (m)=>{
      const wrap = document.createElement('div');
      wrap.className = 'enc-chip-row';

      const p = parseInt(m.power||0,10)||0;
      const a = parseInt(m.accuracy||0,10)||0;
      const pp = parseInt(m.pp||0,10)||0;
      addChip(wrap, 'Сила', (p>0?String(p):'—'));
      addChip(wrap, 'Точн.', (a>0?(String(a)+'%'):'—'));
      addChip(wrap, 'PP', (pp>0?String(pp):'—'));

      const pr = parseInt(m.priority||0,10)||0;
      if (pr !== 0) addChip(wrap, 'Приор.', (pr>0?('+'+pr):String(pr)));

      const t = targetRu(m.target);
      if (t) {
        const sp = document.createElement('span');
        sp.className = 'enc-chip enc-chip--muted';
        sp.textContent = 'Цель: ' + t;
        wrap.appendChild(sp);
      }

      // Flags (if present in schema)
      const f = (k)=> (m[k]===1 || m[k]==='1' || m[k]===true);
      if (f('contact')) addFlag(wrap, '✋', 'Контакт');
      if (f('sound'))   addFlag(wrap, '🔊', 'Звуковая');
      if (f('punch'))   addFlag(wrap, '👊', 'Ударная');
      if (f('bite'))    addFlag(wrap, '🦷', 'Укус');
      if (f('bullet'))  addFlag(wrap, '🔫', 'Снарядная');
      if (f('pulse'))   addFlag(wrap, '〰', 'Импульс');

      return wrap;
    };

    for (const r of rows){
      const m = r.move;
      const tr = document.createElement('tr');
      tr.dataset.search = `${r.src} ${moveLabel(m)} ${(m.type||'')} ${typeRu(m.type)} ${(m.category||'')}`.toLowerCase();

      const tdSrc = document.createElement('td');
      tdSrc.textContent = r.src;

      const tdName = document.createElement('td');
      const a = document.createElement('a');
      a.className = 'link';
      a.href = 'move.php?id=' + encodeURIComponent(m.id);
      a.textContent = moveLabel(m);
      tdName.appendChild(a);

      const meta = document.createElement('div');
      meta.className = 'enc-muted enc-small';
      const tb = mkTypeBadge(m.type);
      if (tb) meta.appendChild(tb);
      meta.appendChild(document.createTextNode(' '));
      meta.appendChild(mkCatBadge(m.category));
      tdName.appendChild(meta);

      const tdParams = document.createElement('td');
      tdParams.appendChild(mkParams(m));

      tr.appendChild(tdSrc);
      tr.appendChild(tdName);
      tr.appendChild(tdParams);

      body.appendChild(tr);
    }

    // attach filter
    const q = slotEl.querySelector('.js-move-dex-q');
    if (q && !q._encBound){
      q._encBound = true;
      q.addEventListener('input', ()=>{
        const needle = (q.value||'').trim().toLowerCase();
        const trs = body.querySelectorAll('tr');
        for (const tr of trs){
          if (!needle) { tr.style.display=''; continue; }
          const hay = tr.dataset.search || '';
          tr.style.display = hay.indexOf(needle) !== -1 ? '' : 'none';
        }
      });
    }
  }

  function updateStats(slotEl, data){
    const pid = parseInt(slotEl.querySelector('.js-pokemon-id')?.value||'0',10)||0;
    const form = (slotEl.querySelector('.js-form')?.value||'').trim();
    const level = parseInt(slotEl.querySelector('.js-level')?.value||'100',10)||100;
    const nature = (slotEl.querySelector('.js-nature')?.value||'hardy').trim();

    if (!pid || !data){
      slotEl.querySelectorAll('.js-stat-out').forEach(el=>el.textContent='—');
      slotEl.querySelectorAll('.js-base-stat').forEach(el=>el.textContent='—');
      slotEl.querySelectorAll('.js-stat-bar').forEach(el=>el.style.width='0%');
      updateSprite(slotEl, 0, '');
      updateTeamItem(slotEl);
      return;
    }

    const forms = (data.forms||[]);
    const f = forms.find(x=>String(x.form||'')===String(form)) || forms.find(x=>String(x.form||'')==='') || null;
    const stats = f && f.base_stats ? f.base_stats : null;

    if (!stats){
      slotEl.querySelectorAll('.js-stat-out').forEach(el=>el.textContent='—');
      slotEl.querySelectorAll('.js-base-stat').forEach(el=>el.textContent='—');
      slotEl.querySelectorAll('.js-stat-bar').forEach(el=>el.style.width='0%');
      updateSprite(slotEl, pid, form);
      updateTeamItem(slotEl);
      return;
    }

    const ev = readEvIv(slotEl, 'ev');
    const iv = readEvIv(slotEl, 'iv');

    const out={};
    for (const k of statKeys()){
      out[k] = calcStat(stats[k], iv[k], ev[k], level, nature, k);
    }

    slotEl.querySelectorAll('.js-stat-out').forEach(el=>{
      const k = el.dataset.stat;
      if (!k || !(k in out)) return;
      el.textContent = String(out[k]);
    });
    slotEl.querySelectorAll('.js-base-stat').forEach(el=>{
      const k = el.dataset.stat;
      if (!k || !(k in stats)) return;
      el.textContent = String(parseInt(stats[k]||0,10)||0);
    });

    // Bars
    slotEl.querySelectorAll('.js-stat-bar').forEach(el=>{
      const k = el.dataset.stat;
      if (!k || !(k in out)) return;
      const max = (k === 'hp') ? 720 : 504;
      const pct = Math.max(0, Math.min(1, (out[k] / max))) * 100;
      el.style.width = pct.toFixed(1) + '%';
    });

    updateSprite(slotEl, pid, form);
    updateTeamItem(slotEl);
  }

  async function refreshSlot(slotEl){
    showMsg('');

    const pid = parseInt(slotEl.querySelector('.js-pokemon-id')?.value||'0',10)||0;
    const formSel = slotEl.querySelector('.js-form');
    const form = (formSel ? formSel.value : '').trim();

    if (!pid){
      if (formSel) formSel.innerHTML = '<option value="">Обычная</option>';
      const abilitySel = slotEl.querySelector('.js-ability');
      if (abilitySel) abilitySel.innerHTML = '<option value="0">—</option>';
      slotEl.querySelectorAll('.js-move').forEach(s=>{ s.innerHTML='<option value="0">—</option>'; });
      const body = slotEl.querySelector('.js-move-dex-body');
      if (body) body.innerHTML = '';
      updateStats(slotEl, null);
      slotEl._encPokemonData = null;
      return;
    }

    const data = await fetchPokemonData(pid, form);
    if (!data){
      showMsg('Не удалось загрузить данные покемона. Попробуйте ещё раз.');
      updateStats(slotEl, null);
      slotEl._encPokemonData = null;
      return;
    }

    // forms list: render then restore selected
    renderForms(slotEl, data.forms||[]);
    if (formSel) formSel.value = form;

    renderAbilities(slotEl, data.abilities||{});
    renderMoves(slotEl, data.moves||{});
    renderMoveDex(slotEl, data.moves||{});

    enforceEv(slotEl);
    enforceIv(slotEl);
    updateStats(slotEl, data);

    slotEl._encPokemonData = data;
  }

  function attachSlot(slotEl){
    const pidInput = slotEl.querySelector('.js-pokemon-id');
    const nameInput = slotEl.querySelector('.js-pokemon-name');
    const formSel = slotEl.querySelector('.js-form');

    function onPokemonChanged(){
      const pid = parseInt(pidInput?.value||'0',10)||0;
      if (!pid && pidInput) pidInput.value = '0';
      refreshSlot(slotEl);
    }

    if (nameInput) nameInput.addEventListener('change', onPokemonChanged);
    if (pidInput) pidInput.addEventListener('change', onPokemonChanged);

    if (formSel){
      formSel.addEventListener('change', ()=>{
        refreshSlot(slotEl);
      });
    }

    // EV numeric
    slotEl.querySelectorAll('.js-ev').forEach(inp=>{
      inp.addEventListener('input', ()=>{
        inp.value = String(normEv(inp.value));
        enforceEv(slotEl, inp);
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
      inp.addEventListener('blur', ()=>{
        inp.value = String(normEv(inp.value));
        enforceEv(slotEl, inp);
      });
    });
    // EV range
    slotEl.querySelectorAll('.js-ev-range').forEach(inp=>{
      inp.addEventListener('input', ()=>{
        const k = inp.dataset.stat;
        const num = slotEl.querySelector(`.js-ev[data-stat="${k}"]`);
        if (num) num.value = String(normEv(inp.value));
        enforceEv(slotEl, inp);
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
    });

    // IV numeric
    slotEl.querySelectorAll('.js-iv').forEach(inp=>{
      inp.addEventListener('input', ()=>{
        inp.value = String(normIv(inp.value));
        enforceIv(slotEl);
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
      inp.addEventListener('blur', ()=>{
        inp.value = String(normIv(inp.value));
        enforceIv(slotEl);
      });
    });
    // IV range
    slotEl.querySelectorAll('.js-iv-range').forEach(inp=>{
      inp.addEventListener('input', ()=>{
        const k = inp.dataset.stat;
        const num = slotEl.querySelector(`.js-iv[data-stat="${k}"]`);
        if (num) num.value = String(normIv(inp.value));
        enforceIv(slotEl);
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
    });

    // IV code
    const codeInput = slotEl.querySelector('.js-iv-code');
    if (codeInput){
      codeInput.addEventListener('blur', ()=>{
        const parsed = parseIvCode(codeInput.value);
        if (!parsed) {
          enforceIv(slotEl);
          return;
        }
        writeEvIv(slotEl, 'iv', parsed);
        enforceIv(slotEl);
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
    }

    // Copy IV
    const copyBtn = slotEl.querySelector('.js-copy-iv');
    if (copyBtn && codeInput){
      copyBtn.addEventListener('click', async ()=>{
        try{
          await navigator.clipboard.writeText(codeInput.value || '');
          copyBtn.textContent = 'Скопировано';
          setTimeout(()=>{ copyBtn.textContent = 'Копировать'; }, 1200);
        }catch(e){
          // ignore
        }
      });
    }

    // Presets
    slotEl.querySelectorAll('[data-iv-preset]').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        e.preventDefault();
        const preset = btn.getAttribute('data-iv-preset');
        let iv;
        if (preset === 'all31') iv={hp:31,atk:31,def:31,satk:31,sdef:31,spd:31};
        else if (preset === 'all0') iv={hp:0,atk:0,def:0,satk:0,sdef:0,spd:0};
        else if (preset === '0atk') iv={...readEvIv(slotEl,'iv'), atk:0};
        else if (preset === '0spe') iv={...readEvIv(slotEl,'iv'), spd:0};
        else return;
        writeEvIv(slotEl, 'iv', iv);
        enforceIv(slotEl);
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
    });

    // Level / Nature
    const levelInput = slotEl.querySelector('.js-level');
    if (levelInput){
      levelInput.addEventListener('input', ()=>{
        levelInput.value = String(clamp(parseInt(levelInput.value||'100',10)||100, 1, 100));
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
      levelInput.addEventListener('blur', ()=>{
        levelInput.value = String(clamp(parseInt(levelInput.value||'100',10)||100, 1, 100));
      });
    }

    const natureSel = slotEl.querySelector('.js-nature');
    if (natureSel){
      natureSel.addEventListener('change', ()=>{
        if (slotEl._encPokemonData) updateStats(slotEl, slotEl._encPokemonData);
      });
    }

    // init (keep any posted values)
    enforceEv(slotEl);
    enforceIv(slotEl);
    refreshSlot(slotEl);
  }

  // Sidebar / tabs
  function setActiveSlot(slotNum){
    const s = String(slotNum||'');
    document.querySelectorAll('.js-builder-slot').forEach(p=>{
      p.classList.toggle('is-active', String(p.dataset.slot||'') === s);
    });
    document.querySelectorAll('.js-team-item').forEach(b=>{
      b.classList.toggle('is-active', String(b.dataset.slot||'') === s);
    });
    // scroll editor to top on narrow screens
    const editor = document.querySelector('.builder-editor');
    if (editor) editor.scrollIntoView({block:'start', behavior:'smooth'});
  }

  document.querySelectorAll('.js-team-item').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const slot = btn.getAttribute('data-slot');
      if (slot) setActiveSlot(slot);
    });
  });

  // Attach slots
  document.querySelectorAll('.js-builder-slot').forEach(attachSlot);

  // Default active slot: first non-empty, else 1
  let active = '1';
  const firstFilled = Array.from(document.querySelectorAll('.js-builder-slot')).find(p=>{
    const pid = parseInt(p.querySelector('.js-pokemon-id')?.value||'0',10)||0;
    return pid > 0;
  });
  if (firstFilled) active = String(firstFilled.dataset.slot||'1');

  setActiveSlot(active);

  // Submit warning for empty slots
  const form = document.querySelector('.js-builder-v2');
  if (form){
    form.addEventListener('submit', (e)=>{
      const slots = Array.from(document.querySelectorAll('.js-builder-slot'));
      const empties = slots.filter(s=> (parseInt(s.querySelector('.js-pokemon-id')?.value||'0',10)||0) === 0);
      if (empties.length){
        const ok = confirm(`В сборке есть незаполненные слоты (${empties.length} из 6). Сохранить всё равно?`);
        if (!ok){
          e.preventDefault();
          return false;
        }
      }
      return true;
    });
  }
})();
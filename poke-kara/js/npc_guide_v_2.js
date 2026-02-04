/*
  NPC UI Guide v2 (spotlight coachmarks + stepper)
  - Modern, accessible, mobile-friendly
  - Data-driven steps + smart element resolution (selector | finder)
  - Spotlight overlay with click-through hole; optional ring/pulse
  - Next/Back/Skip controls, progress, and auto-advance on click
  - Resilient to DOM changes (Mutation/Resize/Scroll observers)
  - LocalStorage gating for Quest 1 (compatible with your keys)
*/
(function(){
  const GUIDE_DEBUG = false;
  const Z = 100001;
  const LS_Q1_DONE  = 'pp:guide:q1done';
  const LS_Q1_STATE = 'pp:guide:q1state';

  // ========================= Styles ======================================
  function ensureStyles(){
    if (document.getElementById('ui-tour-style-v2')) return;
    const css = `
:root{--tour-color:#8a2be2;--tour-bg:#1b1230;--tour-text:#f6f3ff;--tour-dim:rgba(16,10,31,.65)}
@keyframes tour-ring {0%{box-shadow:0 0 0 0 rgba(138,43,226,.45)}50%{box-shadow:0 0 20px 8px rgba(138,43,226,.55)}100%{box-shadow:0 0 0 0 rgba(138,43,226,.45)}}
@keyframes tour-pulse{0%{transform:scale(1)}50%{transform:scale(1.02)}100%{transform:scale(1)}}

.ui-tour-root{position:fixed;inset:0;z-index:${Z};pointer-events:none;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,'Noto Sans',sans-serif}
.ui-tour-dim{position:fixed;inset:0;background:var(--tour-dim);transition:opacity .18s ease;}
.ui-tour-dim.hidden{opacity:0}

/* 4-piece mask to create a click-through hole */
.ui-tour-mask{position:fixed;pointer-events:auto}
.ui-tour-mask.top,.ui-tour-mask.bottom{left:0;right:0}
.ui-tour-mask.left,.ui-tour-mask.right{top:0;bottom:0}
.ui-tour-mask{background:var(--tour-dim)}

.ui-tour-ring{position:fixed;border:2px solid var(--tour-color);border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.35);animation:tour-ring 1.25s ease-in-out infinite;pointer-events:none}

.ui-tour-tooltip{position:fixed;max-width:min(360px, calc(100vw - 24px));background:linear-gradient(180deg,#22183e,#1a1232);color:var(--tour-text);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:12px 12px 10px;box-shadow:0 14px 40px rgba(0,0,0,.35);pointer-events:auto}
.ui-tour-tooltip h4{margin:0 0 6px;font-size:15px;line-height:1.25;color:white}
.ui-tour-tooltip p{margin:0 0 10px;font-size:13px;line-height:1.4;opacity:.95}
.ui-tour-tip-arrow{position:absolute;width:12px;height:12px;background:linear-gradient(180deg,#22183e,#1a1232);transform:rotate(45deg);border-left:1px solid rgba(255,255,255,.12);border-top:1px solid rgba(255,255,255,.12)}

.ui-tour-actions{display:flex;gap:8px;align-items:center;justify-content:flex-end}
.ui-tour-btn{appearance:none;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.08);color:#fff;border-radius:10px;font-size:13px;padding:8px 10px;cursor:pointer}
.ui-tour-btn:hover{background:rgba(255,255,255,.14)}
.ui-tour-btn.primary{border-color:transparent;background:var(--tour-color)}

.ui-tour-progress{position:fixed;right:10px;top:10px;font-size:12px;color:#fff;background:rgba(0,0,0,.4);padding:6px 8px;border-radius:10px;backdrop-filter:saturate(110%) blur(2px)}

@media (prefers-reduced-motion: reduce){
  .ui-tour-ring{animation:none}
}
`;
    const s = document.createElement('style');
    s.id = 'ui-tour-style-v2';
    s.type = 'text/css';
    s.appendChild(document.createTextNode(css));
    document.head.appendChild(s);
  }

  // ========================= Utilities ====================================
  function log(...a){ if (GUIDE_DEBUG) console.log('[Guide]', ...a); }
  function clamp(v,min,max){ return Math.max(min, Math.min(max, v)); }
  function normText(t){ return String(t||'').replace(/\s+/g,' ').trim().toLowerCase(); }
  function scrollIntoViewIfNeeded(el, margin){
    if (!el) return;
    const r = el.getBoundingClientRect();
    const m = margin||10; const top = r.top - m; const bottom = r.bottom + m;
    const needTop = top < 0; const needBottom = bottom > window.innerHeight;
    if (needTop) window.scrollBy({top: top, behavior:'smooth'});
    else if (needBottom) window.scrollBy({top: bottom - window.innerHeight, behavior:'smooth'});
  }

  // ========================= Resolver (selectors & finders) ================
  const SCOPES = {
    tabWorld: ['button[data-ui="tab-world"]','button#tabWorld','.tabs .tab-world','[data-tab="world"]','.bottom-tabs .tab-world'],
    leftBox:  ['.DivMap .Left .Steps','.available-locations','[data-ui="locations-list"]','.locations','.locations-list'],
    rightBox: ['.DivMap .Right .Steps','.npc-list','[data-ui="npc-list"]','.right-characters'],
    locationTargets: [
      '.DivMap .Left .Steps *','.available-locations *','[data-ui="locations-list"] *','.locations-list *','.locations *'
    ],
    npcTargets: [
      '.DivMap .Right .Steps *','.npc-list *','[data-ui="npc-list"] *','.right-characters *'
    ]
  };

  const LOC_NAMES = {
    academy:   ['Академия','Academy'],
    alabastia: ['Алабастия','Alabastia','Pallet','Паллет'],
    home:      ['Дом','Home'],
    road_1:    ['Дорога 1','Маршрут 1','Route 1'],
    route_1:   ['Route 1','Маршрут 1','Дорога 1'],
    doroga_1:  ['Дорога 1','Маршрут 1','Route 1'],
  };
  const NPC_NAMES = {
    mom:       ['Мама','Mother'],
    jack:      ['Куратор Джек','Джек','Researcher Jack','Jack'],
    passerby:  ['Прохожий','Passerby'],
    prof_oak:  ['Профессор Оук','Оук','Professor Oak','Oak'],
    bag:       ['Сумка','Bag']
  };

  function findByText(scopeSelectors, texts){
    const scopes = Array.isArray(scopeSelectors)?scopeSelectors:[scopeSelectors];
    const want = (Array.isArray(texts)?texts:[texts]).map(normText);
    for (let s of scopes){
      let nodes = [];
      try { nodes = Array.from(document.querySelectorAll(s)); } catch(e){ nodes = []; }
      for (let el of nodes){
        const t = normText(el.textContent||'');
        if (!t) continue;
        for (let w of want){ if (t.includes(w)) return el; }
      }
    }
    return null;
  }

  // ========================= Guide Core ====================================
  class Tour {
    constructor(){
      ensureStyles();
      this.steps = [];
      this.idx = 0;
      this.root = null;
      this.observers = [];
      this.waitClickHandler = null;
      this.activeEl = null;
    }

    buildRoot(){
      if (this.root) return this.root;
      const root = document.createElement('div'); root.className='ui-tour-root';
      root.innerHTML = `
        <div class="ui-tour-dim hidden"></div>
        <div class="ui-tour-mask top"></div>
        <div class="ui-tour-mask right"></div>
        <div class="ui-tour-mask bottom"></div>
        <div class="ui-tour-mask left"></div>
        <div class="ui-tour-ring" style="display:none"></div>
        <div class="ui-tour-tooltip" style="display:none">
          <div class="ui-tour-tip-arrow" aria-hidden="true"></div>
          <h4></h4>
          <p></p>
          <div class="ui-tour-actions">
            <button class="ui-tour-btn" data-act="skip" type="button">Пропустить</button>
            <span style="flex:1"></span>
            <button class="ui-tour-btn" data-act="back" type="button">Назад</button>
            <button class="ui-tour-btn primary" data-act="next" type="button">Далее</button>
          </div>
        </div>
        <div class="ui-tour-progress" style="display:none"></div>
      `;
      document.body.appendChild(root);
      const dim = root.querySelector('.ui-tour-dim');
      dim.addEventListener('click', ()=> this.shake());
      root.querySelector('[data-act="skip"]').addEventListener('click', ()=>this.stop('skip'));
      root.querySelector('[data-act="back"]').addEventListener('click', ()=>this.prev());
      root.querySelector('[data-act="next"]').addEventListener('click', ()=>this.next());
      this.root = root; return root;
    }

    start(steps){
      if (!Array.isArray(steps) || !steps.length) return;
      this.steps = steps.map(s=>Object.assign({mode:'spotlight', margin:10, advanceOn:null}, s));
      this.idx = 0;
      this.buildRoot();
      this.root.style.display='block';
      this.root.querySelector('.ui-tour-dim').classList.remove('hidden');
      this.update();
    }

    stop(reason){
      if (!this.root) return;
      if (this.waitClickHandler && this.activeEl) {
        this.activeEl.removeEventListener('click', this.waitClickHandler, true);
      }
      this.teardownObservers();
      this.root.style.display='none';
      this.root.querySelector('.ui-tour-dim').classList.add('hidden');
      log('stopped', reason||'');
    }

    next(){ if (this.idx < this.steps.length-1){ this.idx++; this.update(); } else { this.stop('done'); } }
    prev(){ if (this.idx > 0){ this.idx--; this.update(); } }

    update(){
      const step = this.steps[this.idx]; if (!step) return;
      const el = this.resolveTarget(step);
      if (!el){
        // Wait with MutationObserver for up to 10s
        this.teardownObservers();
        const mo = new MutationObserver(()=>{ const found = this.resolveTarget(step); if (found){ mo.disconnect(); this.update(); } });
        mo.observe(document.documentElement, {childList:true, subtree:true});
        this.observers.push(mo);
        return;
      }
      this.activeEl = el;
      scrollIntoViewIfNeeded(el, 12);
      this.position(el, step);
      this.makeInteractive(el, step);
      this.observe(el, step);
    }

    resolveTarget(step){
      let el = null;
      if (step.selector){
        const sels = Array.isArray(step.selector)?step.selector:[step.selector];
        for (let s of sels){ try{ el = document.querySelector(s); } catch(e){ el=null; } if (el) break; }
      }
      if (!el && step.finder && step.finder.scope && step.finder.text){
        el = findByText(step.finder.scope, step.finder.text);
      }
      if (!el && step.fallbackSelector){
        const sels = Array.isArray(step.fallbackSelector)?step.fallbackSelector:[step.fallbackSelector];
        for (let s of sels){ try{ el = document.querySelector(s); } catch(e){ el=null; } if (el) break; }
      }
      return el;
    }

    position(el, step){
      const r = el.getBoundingClientRect();
      const m = step.margin||10;
      const x = r.left - m, y = r.top - m, w = r.width + m*2, h = r.height + m*2;
      const vw = window.innerWidth, vh = window.innerHeight;

      const [top,left,bottom,right] = [
        this.root.querySelector('.ui-tour-mask.top'),
        this.root.querySelector('.ui-tour-mask.left'),
        this.root.querySelector('.ui-tour-mask.bottom'),
        this.root.querySelector('.ui-tour-mask.right'),
      ];
      top.style.top = '0px'; top.style.left='0px'; top.style.width = vw+'px'; top.style.height = Math.max(0,y)+'px';
      left.style.left = '0px'; left.style.top = Math.max(0,y)+'px'; left.style.width = Math.max(0,x)+'px'; left.style.height = Math.max(0,h)+'px';
      right.style.left = clamp(x+w,0,vw)+'px'; right.style.top = Math.max(0,y)+'px'; right.style.width = Math.max(0, vw-(x+w))+'px'; right.style.height = Math.max(0,h)+'px';
      bottom.style.left = '0px'; bottom.style.top = clamp(y+h,0,vh)+'px'; bottom.style.width = vw+'px'; bottom.style.height = Math.max(0, vh-(y+h))+'px';

      const ring = this.root.querySelector('.ui-tour-ring');
      ring.style.display='block'; ring.style.left = x+'px'; ring.style.top=y+'px'; ring.style.width=w+'px'; ring.style.height=h+'px';
      if (step.color) ring.style.borderColor=step.color;

      const tip = this.root.querySelector('.ui-tour-tooltip');
      const arrow = tip.querySelector('.ui-tour-tip-arrow');
      tip.style.display='block';
      tip.querySelector('h4').textContent = step.label || '';
      tip.querySelector('p').textContent = step.sublabel || '';

      // choose best side for tooltip: bottom, top, right, left
      const spaces = {bottom: vh-(y+h), top: y, right: vw-(x+w), left: x};
      const side = Object.entries(spaces).sort((a,b)=>b[1]-a[1])[0][0];
      const pad = 12;
      let tx=0, ty=0; let aw=12; arrow.style.display='block';
      if (side==='bottom'){
        ty = y+h + pad; tx = clamp(x, 6, vw - tip.offsetWidth - 6); arrow.style.left = clamp(x + (w/2) - aw/2, 18, vw-18)+'px'; arrow.style.top = (ty-pad+2)+'px';
      } else if (side==='top'){
        ty = Math.max(10, y - tip.offsetHeight - pad); tx = clamp(x, 6, vw - tip.offsetWidth - 6); arrow.style.left = clamp(x + (w/2) - aw/2, 18, vw-18)+'px'; arrow.style.top = (ty + tip.offsetHeight - 6)+'px';
      } else if (side==='right'){
        tx = x+w + pad; ty = clamp(y, 8, vh - tip.offsetHeight - 8); arrow.style.top = clamp(y + (h/2) - aw/2, 18, vh-18)+'px'; arrow.style.left = (tx-pad+2)+'px';
      } else { // left
        tx = Math.max(8, x - tip.offsetWidth - pad); ty = clamp(y, 8, vh - tip.offsetHeight - 8); arrow.style.top = clamp(y + (h/2) - aw/2, 18, vh-18)+'px'; arrow.style.left = (tx + tip.offsetWidth - 6)+'px';
      }
      tip.style.left = tx+'px'; tip.style.top = ty+'px';

      const prog = this.root.querySelector('.ui-tour-progress');
      prog.style.display='block'; prog.textContent = `${this.idx+1} / ${this.steps.length}`;
    }

    makeInteractive(el, step){
      const nextBtn = this.root.querySelector('[data-act="next"]');
      const backBtn = this.root.querySelector('[data-act="back"]');
      backBtn.disabled = (this.idx===0);
      nextBtn.textContent = step.advanceOn==='clickTarget' ? 'Нажмите на элемент' : 'Далее';

      if (this.waitClickHandler && this.activeEl) {
        this.activeEl.removeEventListener('click', this.waitClickHandler, true);
        this.waitClickHandler = null;
      }
      if (step.advanceOn==='clickTarget'){
        this.waitClickHandler = (ev)=>{ setTimeout(()=>this.next(), 0); };
        el.addEventListener('click', this.waitClickHandler, true);
      }
    }

    observe(el, step){
      this.teardownObservers();
      const ro = new ResizeObserver(()=>this.position(el, step));
      ro.observe(el); this.observers.push(ro);
      const so = ()=>this.position(el, step);
      window.addEventListener('scroll', so, true); window.addEventListener('resize', so, true);
      this.observers.push({disconnect(){ window.removeEventListener('scroll', so, true); window.removeEventListener('resize', so, true); }});
    }

    teardownObservers(){
      for (let o of this.observers){ try{ o.disconnect && o.disconnect(); }catch(e){} }
      this.observers = [];
    }

    shake(){
      const ring = this.root.querySelector('.ui-tour-ring');
      ring.style.animation = 'none'; ring.offsetHeight; ring.style.animation='tour-ring 1.25s ease-in-out infinite';
    }
  }

  const Guide = new Tour();

  // ========================= Quest helpers + public API ====================
  function getQ1State(){
    try { return JSON.parse(localStorage.getItem(LS_Q1_STATE)) || { metOak:false, metJackAfterOak:false, lastNpc:'' }; }
    catch(e){ return { metOak:false, metJackAfterOak:false, lastNpc:'' }; }
  }
  function isQ1Done(){ try { return localStorage.getItem(LS_Q1_DONE)==='1'; } catch(e){ return false; } }

  function shouldGuide(response){
    if (isQ1Done()) return false;
    if (response && (response.questCompleted===1 || response.quest1Completed===1)) return false;
    return true;
  }

  // Map nav -> step list for Guide
  function buildStepsFromNav(nav){
    const steps = [];
    function stepFor(type, slug, label){
      if (type==='npc'){
        steps.push({ finder:{scope:SCOPES.npcTargets, text:(NPC_NAMES[slug]||[slug])}, fallbackSelector:SCOPES.rightBox, label:`Поговорите: ${label|| (NPC_NAMES[slug]||[slug])[0]}`, sublabel:'Нажмите, чтобы открыть диалог', advanceOn:'clickTarget' });
      } else {
        steps.push({ finder:{scope:SCOPES.locationTargets, text:(LOC_NAMES[slug]||[slug])}, fallbackSelector:SCOPES.leftBox, label:`Перейдите: ${label || (LOC_NAMES[slug]||[slug])[0]}`, sublabel:'Нажмите, чтобы переместиться', advanceOn:'clickTarget' });
      }
    }
    function tabWorld(){ steps.unshift({ selector:SCOPES.tabWorld, label:'Откройте раздел «Мир»', sublabel:'Здесь список локаций и персонажей', advanceOn:'clickTarget' }); }

    if (Array.isArray(nav.route) && nav.route.length){
      tabWorld();
      nav.route.forEach(s=> stepFor((s.type||'location').toLowerCase(), String(s.slug||'').toLowerCase(), s.name||''));
      return steps;
    }
    // Fallback target
    const tType = (nav.target && nav.target.type) ? String(nav.target.type).toLowerCase() : 'location';
    const tSlug = (nav.target && nav.target.slug) ? String(nav.target.slug).toLowerCase() : 'academy';
    tabWorld(); stepFor(tType, tSlug, (nav.target && nav.target.name)||'');
    return steps;
  }

  function inferNav(response){
    const name = (response.name||'').toLowerCase();
    const q    = (response.question||'').toLowerCase();
    if (name.includes('мама')){
      return { route:[ {type:'npc',slug:'mom',name:'Мама'}, {type:'location',slug:'alabastia',name:'Алабастия'}, {type:'location',slug:'academy',name:'Академия'}, {type:'npc',slug:'jack',name:'Куратор Джек'} ] };
    }
    if (name.includes('джек')){
      return { route:[ {type:'location',slug:'alabastia',name:'Алабастия'}, {type:'npc',slug:'passerby',name:'Прохожий'} ] };
    }
    if (name.includes('прохож')){
      return { route:[ {type:'location',slug:'road_1',name:'Дорога 1'}, {type:'npc',slug:'prof_oak',name:'Профессор Оук'} ] };
    }
    if (name.includes('сумка')){
      return { route:[ {type:'npc',slug:'prof_oak',name:'Профессор Оук'} ] };
    }
    if (name.includes('профессор') || name.includes('оук')){
      if (q.includes('сумк')) return { route:[ {type:'npc',slug:'bag',name:'Сумка'} ] };
      return { route:[ {type:'location',slug:'alabastia',name:'Алабастия'}, {type:'location',slug:'academy',name:'Академия'}, {type:'npc',slug:'jack',name:'Куратор Джек'} ] };
    }
    return { target:{ type:'location', slug:'academy', name:'Академия' } };
  }

  // Public helpers to wire inside NpcDialog
  window.UITour = {
    shouldGuide,
    startFromNav(nav){ const steps = buildStepsFromNav(nav||{target:{type:'location',slug:'academy',name:'Академия'}}); Guide.start(steps); },
    startForResponse(response){ if (!shouldGuide(response)) return; const nav = response.nav || inferNav(response); this.startFromNav(nav); },
    stop(){ Guide.stop('external'); }
  };
})();

/* ===================== Integration (example) ===============================
Inside your NpcDialog AJAX success handler, replace the old highlight block with:

  if (UITour.shouldGuide(response)) {
    UITour.startForResponse(response);
  } else {
    UITour.stop();
  }

This module does not depend on jQuery and coexists with your modal styling.
*/

(function(){
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  function api(url){
    return fetch(url, {credentials:'same-origin'})
      .then(r => r.json());
  }

  function escapeHtml(str){
    return String(str || '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }



  /* ---------------- UI HELPERS (toasts/errors/modals) ---------------- */
  function toast(msg, type='info', timeout=3200){
    let root = document.getElementById('pkToastRoot');
    if(!root){
      root = document.createElement('div');
      root.id = 'pkToastRoot';
      document.body.appendChild(root);
    }
    const el = document.createElement('div');
    el.className = 'pkToast ' + String(type||'info');
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live','polite');
    el.innerHTML = `<div class="pkToastMsg">${escapeHtml(msg)}</div>`;
    root.appendChild(el);
    setTimeout(() => { el.classList.add('show'); }, 10);
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 220);
    }, timeout);
  }

  function clearFieldError(inputEl){
    if(!inputEl) return;
    inputEl.classList.remove('isError');
    const err = document.getElementById('err-' + inputEl.id);
    if(err){ err.textContent = ''; err.style.display = 'none'; }
  }

  function setFieldError(inputEl, message){
    if(!inputEl) return;
    inputEl.classList.add('isError');
    const err = document.getElementById('err-' + inputEl.id);
    if(err){ err.textContent = message; err.style.display = 'block'; }
  }

  function clearErrors(ids){
    (ids||[]).forEach(id => {
      const el = document.getElementById(id);
      clearFieldError(el);
    });
  }

  function showFormError(blockId, message){
    const b = document.getElementById(blockId);
    if(!b) return;
    if(message){
      b.textContent = message;
      b.style.display = 'block';
    } else {
      b.textContent = '';
      b.style.display = 'none';
    }
  }

  // Info modal (универсальный для успешной регистрации и других сообщений)
  const infoOverlay = document.getElementById('pkInfoOverlay');
  const infoModal = document.getElementById('pkInfoModal');
  let infoOkCb = null;

  function openInfo(title, html, okCb){
    if(!infoOverlay || !infoModal) {
      // fallback
      toast(title ? (title + ': ' + (html||'')) : (html||''), 'info', 4500);
      if(typeof okCb === 'function') okCb();
      return;
    }
    infoOkCb = (typeof okCb === 'function') ? okCb : null;
    const t = document.getElementById('pkInfoTitle');
    const c = document.getElementById('pkInfoBody');
    if(t) t.textContent = title || 'Сообщение';
    if(c) c.innerHTML = html || '';
    infoOverlay.style.display = 'block';
    infoModal.style.display = 'block';
  }

  function closeInfo(){
    if(!infoOverlay || !infoModal) return;
    infoModal.style.display = 'none';
    infoOverlay.style.display = 'none';
  }

  document.getElementById('pkInfoOk')?.addEventListener('click', () => {
    closeInfo();
    if(infoOkCb){
      const cb = infoOkCb; infoOkCb = null;
      cb();
    }
  });
  infoOverlay?.addEventListener('click', closeInfo);

  /* ---------------- NAV ---------------- */
  function show(view){
    ['home','rating','registration'].forEach(v => {
      const el = document.getElementById('view-'+v);
      if(el) el.classList.toggle('active', v===view);
    });

    $$('.nav a').forEach(a => a.classList.toggle('active', a.dataset.nav===view));

    closeSheet();

    if(view === 'registration'){
      // на мобиле гарантируем, что пользователь видит блок модели
      setTimeout(() => {
        const modelCard = $('.pkModelCard');
        if(modelCard && window.innerWidth <= 980) modelCard.scrollIntoView({behavior:'smooth', block:'start'});
      }, 50);
    } else {
      window.scrollTo({top:0, behavior:'smooth'});
    }
  }

  document.addEventListener('click', (e) => {
    const nav = e.target.closest('[data-nav]');
    if(nav){
      e.preventDefault();
      const target = nav.dataset.nav;
      if(['home','rating','registration'].includes(target)) show(target);
      return;
    }

    const act = e.target.closest('[data-action]');
    if(act){
      e.preventDefault();
      const a = act.dataset.action;

      if(a === 'scroll-news'){
        const news = document.getElementById('news');
        if(news) news.scrollIntoView({behavior:'smooth'});
      }
      return;
    }
  });

  /* ---------------- MOBILE SHEET ---------------- */
  const overlay = $('#sheetOverlay');
  const sheet = $('#sheet');

  function openSheet(){
    if(!overlay || !sheet) return;
    overlay.style.display = 'block';
    requestAnimationFrame(() => { sheet.style.transform = 'translateY(0)'; });
  }

  function closeSheet(){
    if(!overlay || !sheet) return;
    sheet.style.transform = 'translateY(110%)';
    setTimeout(() => { overlay.style.display = 'none'; }, 180);
  }

  $('#burger')?.addEventListener('click', openSheet);
  $('#closeSheet')?.addEventListener('click', closeSheet);
  overlay?.addEventListener('click', closeSheet);

  /* ---------------- KPI ---------------- */
  function loadKpi(){
    api('/?ajax=kpi').then(d => {
      if($('#kpiOnline')) $('#kpiOnline').textContent = d.online ?? 0;
      if($('#kpiDay')) $('#kpiDay').textContent = d.day ?? 0;
    }).catch(()=>{});
  }

  /* ---------------- NEWS ---------------- */
  let newsPage = 1;
  const perPage = 3;
  let newsHasMore = true;
  let newsLoading = false;

  const newsTextCache = new Map();
  const newsTextInFlight = new Map();

  function fetchNewsText(newsId){
    const id = String(newsId || '');
    if(!id) return Promise.reject(new Error('bad_id'));

    if(newsTextCache.has(id)) return Promise.resolve(newsTextCache.get(id));
    if(newsTextInFlight.has(id)) return newsTextInFlight.get(id);

    const p = api('/?ajax=news_text&id=' + encodeURIComponent(id))
      .then(d => {
        if(!d || d.ok === false) throw new Error((d && d.error) ? d.error : 'load_failed');
        const html = String(d.html || '');
        newsTextCache.set(id, html);
        return html;
      })
      .finally(() => {
        newsTextInFlight.delete(id);
      });

    newsTextInFlight.set(id, p);
    return p;
  }

  function reactionButtonsHtml(r){
    const u = (r && r.userReaction) ? String(r.userReaction) : '';
    const btn = (emoji, key, val) => {
      const active = (u === key) ? ' active' : '';
      return `<div class="reaction${active}" data-react="${key}">
        <i>${emoji}</i><span>${val||0}</span>
      </div>`;
    };
    return `<div class="reactions">
      ${btn('❤️','love', r?.love)}
      ${btn('👍','like', r?.like)}
      ${btn('😆','haha', r?.haha)}
      ${btn('😢','sad',  r?.sad)}
    </div>`;
  }

  function stripDuplicateImg(fullHtml, previewImg){
    if(!fullHtml) return '';
    if(!previewImg) return fullHtml;

    // удаляем только те <img>, которые совпадают с превью (по src включает previewImg)
    // безопаснее через DOM
    const box = document.createElement('div');
    box.innerHTML = fullHtml;

    const imgs = box.querySelectorAll('img');
    imgs.forEach(img => {
      const src = img.getAttribute('src') || '';
      if(src && (src === previewImg || src.indexOf(previewImg) !== -1 || previewImg.indexOf(src) !== -1)){
        img.remove();
      }
    });

    return box.innerHTML;
  }

  function renderNewsCard(n){
    const imgHtml = n.img ? `<div class="thumb"><img src="${escapeHtml(n.img)}" alt=""></div>` : `<div class="thumb"></div>`;

    const a = n.author || {};
    const authorLogin = a.login || '';
    const authorAvatar = a.avatar || '/img/avatars/mini/6.png';
    const authorHtml = authorLogin
      ? `<span class="author"><img class="authorAvatar" src="${escapeHtml(authorAvatar)}" alt=""><span class="authorName">${escapeHtml(authorLogin)}</span></span>`
      : '';


    return `
      <article class="card" data-news="${n.id}">
        <div class="cardTop">
          ${imgHtml}
          <div style="min-width:0; flex:1">
            <div class="meta">
              <span class="date">${escapeHtml(n.date)}</span>
              ${authorHtml}
              <span class="tag">${escapeHtml(n.tag)}</span>
            </div>
            <div class="title">${escapeHtml(n.title)}</div>
            <p class="excerpt">${escapeHtml(n.excerpt)}</p>
          </div>
        </div>

        <div class="cardBody">
          ${reactionButtonsHtml(n.reactions || {})}
          <div class="cardActions">
            <button class="linkBtn" type="button" data-toggle>Подробнее</button>
            <span style="font-size:12px; color:var(--muted)">#${n.id}</span>
          </div>
        </div>

        <div class="fullText" data-loaded="0"></div>
      </article>
    `;
  }

  function bindNewsInteractions(root){
    // toggle “Подробнее” — НЕ добавляем контент повторно
    root.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-toggle]');
      if(btn){
        const card = btn.closest('.card');
        if(!card) return;

        const full = card.querySelector('.fullText');
        if(!full) return;

        const newsId = card.getAttribute('data-news') || '';
        const previewImg = card.getAttribute('data-img') || '';

        const isOpen = full.classList.contains('open');
        if(isOpen){
          full.classList.remove('open');
          btn.textContent = 'Подробнее';
          return;
        }

        full.classList.add('open');
        btn.textContent = 'Свернуть';

        const loaded = full.getAttribute('data-loaded') === '1';
        if(loaded) return;

        const cached = card.getAttribute('data-full') || '';
        if(cached){
          const clean = stripDuplicateImg(cached, previewImg);
          full.innerHTML = clean;
          full.setAttribute('data-loaded','1');
          return;
        }

        full.innerHTML = '<div class="newsLoading">Загрузка…</div>';

        fetchNewsText(newsId).then(html => {
          const clean = stripDuplicateImg(html, previewImg);
          card.setAttribute('data-full', html);
          full.innerHTML = clean || '';
          full.setAttribute('data-loaded','1');
        }).catch(() => {
          full.innerHTML = '<div class="newsLoading error">Не удалось загрузить текст. Попробуйте ещё раз.</div>';
        }).finally(() => {
          btn.textContent = full.classList.contains('open') ? 'Свернуть' : 'Подробнее';
        });

        return;
      }

      // reactions
      const r = e.target.closest('.reaction');
      if(r){
        const card = r.closest('.card');
        const newsId = card ? card.getAttribute('data-news') : '';
        const react = r.getAttribute('data-react');

        // UI optimistic
        $$('.reaction', card).forEach(x => x.classList.remove('active'));
        r.classList.add('active');

        // backend (если у вас реально есть endpoint)
        const endpoint = window.PK?.endpoints?.reactNews;
        if(endpoint && newsId){
          fetch(endpoint, {
            method:'POST',
            credentials:'same-origin',
            headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body: 'news_id=' + encodeURIComponent(newsId) + '&reaction=' + encodeURIComponent(react)
          }).catch(()=>{});
        }
      }
    });
  }

  function loadMoreNews(){
    if(!newsHasMore || newsLoading) return;
    newsLoading = true;

    api('/?ajax=news&page=' + newsPage + '&perPage=' + perPage).then(d => {
      const grid = $('#newsGrid');
      if(!grid) return;

      (d.items || []).forEach(n => {
        const wrap = document.createElement('div');
        wrap.innerHTML = renderNewsCard(n);

        const node = wrap.firstElementChild;
        if(!node) return;

        // кладем img в data-атрибуты; полный текст подгружается лениво по клику “Подробнее”
        node.setAttribute('data-full', '');
        node.setAttribute('data-img', n.img || '');

        grid.appendChild(node);
      });

      newsHasMore = !!d.hasMore;
      if(newsHasMore) newsPage++;

      const moreBtn = $('#moreNews');
      if(moreBtn){
        if(!newsHasMore){
          moreBtn.textContent = 'Больше новостей нет';
          moreBtn.disabled = true;
          moreBtn.style.opacity = .65;
          moreBtn.style.cursor = 'default';
        }
      }

    }).catch(()=>{})
      .finally(()=>{ newsLoading = false; });
  }

  $('#moreNews')?.addEventListener('click', (e) => {
    e.preventDefault();
    loadMoreNews();
  });

  $('#scrollNews')?.addEventListener('click', () => {
    $('#news')?.scrollIntoView({behavior:'smooth'});
  });

  /* ---------------- RATING ---------------- */
  let ratingType = 'pvp';
  let ratingQuery = '';

  function placeClass(p){
    if(p===1) return 'top1';
    if(p===2) return 'top2';
    if(p===3) return 'top3';
    return '';
  }

  function renderRating(items){
    const wrap = $('#rankList');
    if(!wrap) return;
    wrap.innerHTML = '';

    (items || []).forEach(x => {
      const row = document.createElement('div');
      row.className = 'rankRow';
      row.innerHTML = `
        <div class="rankLeft">
          <div class="place ${placeClass(x.place)}">${x.place}</div>
          <img class="ava" src="${escapeHtml(x.avatar)}" alt="">
          <div class="name">
            <b>${escapeHtml(x.login)}</b>
            <span>Очки: ${x.score}</span>
          </div>
        </div>
        <div class="rankRight">
          <div class="scorePill">${x.score}</div>
        </div>
      `;
      wrap.appendChild(row);
    });
  }

  function loadRating(){
    api('/?ajax=rating&type=' + encodeURIComponent(ratingType) + '&q=' + encodeURIComponent(ratingQuery))
      .then(d => renderRating(d.items || []))
      .catch(()=>{});
  }

  $$('#tabs .segTab').forEach(t => {
    t.addEventListener('click', () => {
      $$('#tabs .segTab').forEach(x => x.classList.toggle('active', x===t));
      ratingType = t.dataset.tab || 'pvp';
      loadRating();
    });
  });

  $('#search')?.addEventListener('input', (e) => {
    ratingQuery = String(e.target.value||'').trim();
    loadRating();
  });

  $('#myRank')?.addEventListener('click', () => {
    const first = $('#rankList .rankRow');
    if(first) first.scrollIntoView({behavior:'smooth', block:'start'});
  });

  /* ---------------- LOGIN / FORGOT MODALS ---------------- */
  const loginOverlay = $('#pkLoginOverlay');
  const loginModal = $('#pkLoginModal');
  const forgotOverlay = $('#pkForgotOverlay');
  const forgotModal = $('#pkForgotModal');

  function openLogin(){
    if(!loginOverlay || !loginModal) return;
    loginOverlay.style.display = 'block';
    loginModal.style.display = 'block';
    showFormError('loginErr', '');
    setTimeout(() => $('#loginName')?.focus(), 10);
  }

  function closeLogin(){
    if(!loginOverlay || !loginModal) return;
    loginModal.style.display = 'none';
    loginOverlay.style.display = 'none';
  }

  function openForgot(){
    if(!forgotOverlay || !forgotModal) {
      toast('Функция восстановления пароля пока недоступна.', 'error');
      return;
    }
    // если пользователь пришёл из логина — закрываем логин
    closeLogin();
    forgotOverlay.style.display = 'block';
    forgotModal.style.display = 'block';
    showFormError('forgotErr', '');
    setTimeout(() => $('#forgotEmail')?.focus(), 10);
  }

  function closeForgot(){
    if(!forgotOverlay || !forgotModal) return;
    forgotModal.style.display = 'none';
    forgotOverlay.style.display = 'none';
  }

  $('#btnLogin')?.addEventListener('click', (e) => { e.preventDefault(); openLogin(); });
  $('#pkCloseLogin')?.addEventListener('click', closeLogin);
  loginOverlay?.addEventListener('click', closeLogin);

  $('#pkCloseForgot')?.addEventListener('click', closeForgot);
  forgotOverlay?.addEventListener('click', closeForgot);

  $('#pkForgotLink')?.addEventListener('click', (e) => { e.preventDefault(); openForgot(); });
  $('#backToLogin')?.addEventListener('click', (e) => { e.preventDefault(); closeForgot(); openLogin(); });

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape'){
      if(forgotModal && forgotModal.style.display === 'block') closeForgot();
      if(loginModal && loginModal.style.display === 'block') closeLogin();
    }
  });

  function parseMaybeJson(text){
    try { return JSON.parse(text); } catch(_) { return null; }
  }

  async function doLogin(){
    const endpoint = window.PK?.endpoints?.login;
    const login = String($('#loginName')?.value || '').trim();
    const pass  = String($('#loginPass')?.value || '');

    showFormError('loginErr', '');
    clearErrors(['loginName','loginPass']);

    if(!login){
      setFieldError($('#loginName'), 'Введите логин.');
      return;
    }
    if(!pass){
      setFieldError($('#loginPass'), 'Введите пароль.');
      return;
    }
    if(!endpoint){
      toast('Endpoint входа не настроен.', 'error');
      return;
    }

    const btn = $('#doLogin');
    if(btn) { btn.disabled = true; btn.dataset._txt = btn.textContent; btn.textContent = 'Входим…'; }

    try{
      const resp = await fetch(endpoint, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: 'login=' + encodeURIComponent(login) + '&password=' + encodeURIComponent(pass)
      });

      const text = await resp.text();
      const data = parseMaybeJson(text);

      // если backend отдаёт JSON — используем его
      if(data && typeof data === 'object'){
        if(String(data.error) === '0'){
          location.href = data.redirect || '/';
          return;
        }
        const msg = data.message || data.text || 'Неверный логин или пароль.';
        showFormError('loginErr', msg);
        toast(msg, 'error');
        return;
      }

      // fallback: если это не JSON, то, как раньше, просто перезагружаемся
      location.reload();

    }catch(_){
      toast('Не удалось выполнить вход. Проверьте соединение и повторите.', 'error');
    }finally{
      if(btn){ btn.disabled = false; btn.textContent = btn.dataset._txt || 'Войти'; }
    }
  }

  $('#doLogin')?.addEventListener('click', (e) => { e.preventDefault(); doLogin(); });

  // Enter в полях входа
  ['loginName','loginPass'].forEach(id => {
    document.getElementById(id)?.addEventListener('keydown', (e) => {
      if(e.key === 'Enter'){
        e.preventDefault();
        doLogin();
      }
    });
  });

  async function doForgot(){
    const endpoint = window.PK?.endpoints?.forgot;
    const email = String($('#forgotEmail')?.value || '').trim();

    showFormError('forgotErr', '');
    clearErrors(['forgotEmail']);

    if(!email){
      setFieldError($('#forgotEmail'), 'Введите почту.');
      return;
    }

    if(!endpoint){
      toast('Endpoint восстановления пароля не настроен.', 'error');
      return;
    }

    const btn = $('#doForgot');
    if(btn) { btn.disabled = true; btn.dataset._txt = btn.textContent; btn.textContent = 'Отправляем…'; }

    try{
      const resp = await fetch(endpoint, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: 'mail=' + encodeURIComponent(email)
      });
      const text = await resp.text();
      const data = parseMaybeJson(text);

      if(data && typeof data === 'object'){
        if(String(data.error) === '0'){
          const msg = data.message || data.text || 'Инструкция отправлена на почту.';
          closeForgot();
          openInfo('Проверьте почту', `<p>${escapeHtml(msg)}</p>`, () => openLogin());
          return;
        }
        const msg = data.message || data.text || 'Не удалось отправить письмо. Проверьте адрес и повторите.';
        showFormError('forgotErr', msg);
        toast(msg, 'error');
        return;
      }

      // fallback
      closeForgot();
      openInfo('Проверьте почту', '<p>Если адрес существует, мы отправили письмо с инструкцией.</p>', () => openLogin());

    }catch(_){
      toast('Не удалось выполнить запрос. Проверьте соединение и повторите.', 'error');
    }finally{
      if(btn){ btn.disabled = false; btn.textContent = btn.dataset._txt || 'Отправить'; }
    }
  }

  $('#doForgot')?.addEventListener('click', (e) => { e.preventDefault(); doForgot(); });
  document.getElementById('forgotEmail')?.addEventListener('keydown', (e) => {
    if(e.key === 'Enter'){
      e.preventDefault();
      doForgot();
    }
  });


  /* ---------------- REGISTRATION (MODEL) ---------------- */
  let gender = 'm';
  let model = 4;
  let modelPicked = false;

  function modelRange(){
    return (gender==='m') ? [4,5,6] : [1,2,3];
  }

  function currentModelPath(id){
    if(window.PK && typeof window.PK.modelPath === 'function') return window.PK.modelPath(id);
    return '/img/avatars/model/ava/' + id + '/' + id + '/' + id + 'a.png';
  }

  function resetModelPickUI(){
    modelPicked = false;
    const bm = $('#baseModel');
    if(bm) bm.value = '0';
    const card = $('.pkModelCard');
    if(card) card.classList.add('pkNeedPick');
    const err = document.getElementById('err-model');
    if(err){ err.textContent = ''; err.style.display = 'none'; }

    const btn = document.getElementById('confirmModel');
    if(btn){
      btn.disabled = false;
      btn.textContent = 'Выбрать этот образ';
    }
  }

  function setPicked(){
    modelPicked = true;
    const bm = $('#baseModel');
    if(bm) bm.value = String(model);
    const card = $('.pkModelCard');
    if(card) card.classList.remove('pkNeedPick');

    const btn = document.getElementById('confirmModel');
    if(btn){
      btn.disabled = true;
      btn.textContent = 'Образ выбран';
    }

    const l = $('#modelLabel');
    if(l) l.textContent = 'Модель #' + model + ' — выбрано';

    toast('Стартовый образ выбран.', 'success');
  }

  function setModel(val){
    const r = modelRange();
    model = r.includes(val) ? val : r[0];

    // смена модели требует повторного подтверждения
    resetModelPickUI();

    const l = $('#modelLabel');
    if(l) l.textContent = 'Модель #' + model;

    api('/?ajax=model&id=' + model).then(d => {
      const url = d && d.url ? d.url : currentModelPath(model);
      const img = $('#modelImg');
      if(img){
        img.src = url;
        img.style.display = 'block';
      }
      const p = $('#modelPreview');
      if(p) p.classList.add('pkModelReady');
    }).catch(()=> {
      const img = $('#modelImg');
      if(img){
        img.src = currentModelPath(model);
        img.style.display = 'block';
      }
    });
  }

  $('#genderRow')?.addEventListener('click', (e) => {
    const seg = e.target.closest('.seg');
    if(!seg) return;

    $$('#genderRow .seg').forEach(s => s.classList.toggle('active', s===seg));
    gender = seg.dataset.g === 'f' ? 'f' : 'm';

    setModel(modelRange()[0]);
  });

  $('#prev')?.addEventListener('click', () => {
    const r = modelRange();
    const idx = r.indexOf(model);
    setModel(r[(idx-1+r.length)%r.length]);
  });

  $('#next')?.addEventListener('click', () => {
    const r = modelRange();
    const idx = r.indexOf(model);
    setModel(r[(idx+1)%r.length]);
  });

  // подтверждение выбора — через кнопку или клик по превью
  $('#confirmModel')?.addEventListener('click', (e) => { e.preventDefault(); setPicked(); });
  $('#modelPreview')?.addEventListener('click', () => { setPicked(); });

  function validateRegistration(){
    clearErrors(['regLogin','regPass','regDblPass','regMail','refCode']);

    const login = String($('#regLogin')?.value || '').trim();
    const pass  = String($('#regPass')?.value || '');
    const pass2 = String($('#regDblPass')?.value || '');
    const mail  = String($('#regMail')?.value || '').trim();

    if(!modelPicked || ($('#baseModel')?.value || '0') === '0'){
      const card = $('.pkModelCard');
      if(card){
        card.classList.add('pkNeedPick');
        card.scrollIntoView({behavior:'smooth', block:'start'});
      }
      const err = document.getElementById('err-model');
      if(err){ err.textContent = 'Подтвердите выбор стартового образа.'; err.style.display = 'block'; }
      toast('Сначала подтвердите стартовый образ.', 'error');
      return null;
    }

    if(login.length < 4 || login.length > 16){
      setFieldError($('#regLogin'), 'Длина логина должна быть от 4 до 16 символов.');
      toast('Проверьте логин.', 'error');
      return null;
    }
    if(!/^[a-zA-Z0-9_]+$/.test(login)){
      setFieldError($('#regLogin'), 'Логин: латиница, цифры, подчёркивание.');
      toast('Проверьте логин.', 'error');
      return null;
    }

    if(pass.length < 6 || pass.length > 20){
      setFieldError($('#regPass'), 'Длина пароля должна быть от 6 до 20 символов.');
      toast('Проверьте пароль.', 'error');
      return null;
    }

    if(pass2 !== pass){
      setFieldError($('#regDblPass'), 'Пароли не совпадают.');
      toast('Пароли не совпадают.', 'error');
      return null;
    }

    if(!mail){
      setFieldError($('#regMail'), 'Введите почту.');
      toast('Введите почту.', 'error');
      return null;
    }
    // базовая проверка, детальная — на сервере
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)){
      setFieldError($('#regMail'), 'Введите корректную почту.');
      toast('Проверьте почту.', 'error');
      return null;
    }

    return {login, pass, pass2, mail};
  }

  $('#doRegister')?.addEventListener('click', (e) => {
    e.preventDefault();

    const v = validateRegistration();
    if(!v) return;

    const endpoint = window.PK?.endpoints?.registration || '/registration.php';

    const ref   = String($('#refCode')?.value || '').trim();
    const baseModel = String($('#baseModel')?.value || '0');

    const body = new URLSearchParams();
    body.set('login', v.login);
    body.set('password', v.pass);
    body.set('dbl_password', v.pass2);
    body.set('mail', v.mail);
    body.set('refCode', ref);
    body.set('gender', gender);
    body.set('baseModel', baseModel);

    const btn = $('#doRegister');
    if(btn){ btn.disabled = true; btn.dataset._txt = btn.textContent; btn.textContent = 'Создаём…'; }

    fetch(endpoint, {
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: body.toString()
    }).then(r => r.json()).then(d => {
      if(d && String(d.error) === '0'){
        openInfo(d.title || 'Регистрация завершена', d.message || '<p>Аккаунт создан.</p>', () => {
          openLogin();
          const ln = $('#loginName');
          if(ln) ln.value = d.login || v.login;
          setTimeout(() => $('#loginPass')?.focus(), 10);
        });
        return;
      }

      const msg = (d && (d.message || d.text)) ? (d.message || d.text) : 'Не удалось зарегистрироваться. Попробуйте снова.';

      // маппинг полей backend -> элементы формы
      if(d && d.field){
        if(d.field === 'model'){
          const card = $('.pkModelCard');
          if(card){ card.classList.add('pkNeedPick'); card.scrollIntoView({behavior:'smooth', block:'start'}); }
          const err = document.getElementById('err-model');
          if(err){ err.textContent = d.message || 'Подтвердите выбор модели.'; err.style.display = 'block'; }
        }
        if(d.field === 'login') setFieldError($('#regLogin'), d.message || msg);
        if(d.field === 'password') setFieldError($('#regPass'), d.message || msg);
        if(d.field === 'dbl_password') setFieldError($('#regDblPass'), d.message || msg);
        if(d.field === 'mail') setFieldError($('#regMail'), d.message || msg);
      }

      toast(msg, 'error');

    }).catch(()=>{
      toast('Сервер недоступен. Попробуйте позже.', 'error');
    }).finally(()=>{
      if(btn){ btn.disabled = false; btn.textContent = btn.dataset._txt || 'Создать аккаунт'; }
    });
  });


  /* ---------------- INIT ---------------- */
  function init(){
    // route=forgot (или явный флаг в конфиге)
    if(window.PK && window.PK.openForgotOnLoad){
      openForgot();
    }

    // стартовые данные
    loadKpi();

    // новости: загрузить первую страницу и навесить обработчики
    loadMoreNews();
    const grid = $('#newsGrid');
    if(grid) bindNewsInteractions(grid);

    // рейтинг: загрузить начальный
    loadRating();

    // модель: показать текущую, но НЕ подтверждать выбор автоматически
    $('.pkModelCard')?.classList.add('pkNeedPick');
    setModel(model, false);
  }

  init();
})();

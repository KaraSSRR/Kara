(function(){
  const app = document.getElementById('app');
  const flashArea = document.getElementById('flash-area');
  const topbar = document.getElementById('topbar');
  if(!app) return;

  function escapeHtml(s){
    return String(s)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;');
  }

  function setFlash(type, message){
    if(!flashArea) return;
    if(!message){
      flashArea.innerHTML = '';
      return;
    }
    const cls = (type === 'ok') ? 'ok' : 'err';
    flashArea.innerHTML = `<div class="flash ${cls}">${escapeHtml(message)}</div>`;
  }

  function setLoading(isLoading){
    document.documentElement.dataset.loading = isLoading ? '1' : '0';
  }

  function updateActiveNav(){
    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    document.querySelectorAll('.nav a.nav-link').forEach(a => {
      const href = (a.getAttribute('href') || '').split('?')[0];
      const h = href.replace(/\/+$/, '') || '/';
      if(h === path){
        a.setAttribute('aria-current', 'page');
        a.classList.add('active');
      } else {
        a.removeAttribute('aria-current');
        a.classList.remove('active');
      }
    });
  }

  function isSameOrigin(url){
    try {
      const u = new URL(url, window.location.href);
      return u.origin === window.location.origin;
    } catch (_) {
      return false;
    }
  }

  function shouldHandleLink(a, evt){
    if(!a) return false;
    if(evt.defaultPrevented) return false;
    if(evt.button !== 0) return false;
    if(evt.metaKey || evt.ctrlKey || evt.shiftKey || evt.altKey) return false;

    if(a.target && a.target !== '') return false;
    if(a.hasAttribute('download')) return false;
    if(a.dataset.noSpa === '1') return false;

    const href = a.getAttribute('href');
    if(!href) return false;
    if(href.startsWith('#')) return false;
    if(href.startsWith('mailto:') || href.startsWith('tel:')) return false;

    const u = new URL(href, window.location.href);
    if(u.origin !== window.location.origin) return false;

    return true;
  }

  async function fetchJson(url, options){
    const res = await fetch(url, options);
    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if(!ct.includes('application/json')){
      return { res, json: null };
    }
    let json = null;
    try {
      json = await res.json();
    } catch (_) {
      json = null;
    }
    return { res, json };
  }

  function applyPayload(payload){
    if(typeof payload.title === 'string') document.title = payload.title;
    if(typeof payload.html === 'string') app.innerHTML = payload.html;

    if(payload.flash && typeof payload.flash === 'object'){
      setFlash(payload.flash.type, payload.flash.message);
    } else {
      setFlash('', '');
    }

    updateActiveNav();
    window.scrollTo({ top: 0, behavior: 'instant' });
  }

  async function navigate(url, push){
    setLoading(true);
    try {
      const { res, json } = await fetchJson(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Partial': '1'
        }
      });

      if(res.status === 401){
        setFlash('err', 'Нужно войти в аккаунт.');
        const target = '/login';
        if(push) history.pushState({}, '', target);
        return await navigate(target, false);
      }

      if(!json){
        window.location.href = url;
        return;
      }

      if(!json.ok){
        setFlash('err', (json.error && json.error.message) ? json.error.message : 'Ошибка');
        return;
      }

      const data = json.data || {};

      if(data.redirect){
        const target = String(data.redirect);
        if(push) history.pushState({}, '', target);
        return await navigate(target, false);
      }

      if(push) history.pushState({}, '', url);
      applyPayload(data);
    } finally {
      setLoading(false);
    }
  }

  function formDataToQuery(fd){
    const params = new URLSearchParams();
    for(const [k, v] of fd.entries()){
      if(v instanceof File) continue;
      params.append(k, String(v));
    }
    return params.toString();
  }

  async function submitForm(form){
    const method = (form.method || 'GET').toUpperCase();
    const action = form.action || window.location.href;

    if(!isSameOrigin(action)) return window.location.href = action;
    if(form.dataset.noSpa === '1') return form.submit();

    const enc = (form.enctype || '').toLowerCase();
    if(enc.includes('multipart/form-data')) return form.submit();

    const btns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    btns.forEach(b => { b.disabled = true; });

    setLoading(true);
    try {
      if(method === 'GET'){
        const fd = new FormData(form);
        const qs = formDataToQuery(fd);
        const base = action.split('?')[0];
        const url = qs ? (base + '?' + qs) : base;
        return await navigate(url, true);
      }

      const body = new FormData(form);
      const { res, json } = await fetchJson(action, {
        method,
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Partial': '1'
        },
        body
      });

      if(res.status === 401){
        setFlash('err', 'Нужно войти в аккаунт.');
        history.pushState({}, '', '/login');
        return await navigate('/login', false);
      }

      if(!json){
        window.location.href = action;
        return;
      }

      if(!json.ok){
        setFlash('err', (json.error && json.error.message) ? json.error.message : 'Ошибка');
        return;
      }

      const data = json.data || {};
      if(data.redirect){
        const target = String(data.redirect);
        history.pushState({}, '', target);
        return await navigate(target, false);
      }

      applyPayload(data);
    } finally {
      setLoading(false);
      setTimeout(()=>btns.forEach(b=>{b.disabled=false;}), 600);
    }
  }

  document.addEventListener('click', function(e){
    const a = e.target.closest ? e.target.closest('a') : null;
    if(!a) return;
    if(!shouldHandleLink(a, e)) return;
    e.preventDefault();
    navigate(a.href, true);
  }, true);

  document.addEventListener('submit', function(e){
    const form = e.target;
    if(!(form instanceof HTMLFormElement)) return;
    if(form.dataset.noSpa === '1') return;
    e.preventDefault();
    submitForm(form);
  }, true);

  window.addEventListener('popstate', function(){
    navigate(window.location.href, false);
  });

  updateActiveNav();
})();
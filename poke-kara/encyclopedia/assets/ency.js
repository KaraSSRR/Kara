(function(){
  const burger = document.getElementById('burgerBtn');
  const nav = document.getElementById('mobileNav');
  if (burger && nav){
    burger.addEventListener('click', ()=>{ nav.classList.toggle('open'); });
  }

  // Generic autocomplete for inputs with data-ac="pokemon|move|ability|item|user"
  async function fetchSuggest(type, q){
    const url = `${window.ENC_BASE || ''}/api/search.php?type=${encodeURIComponent(type)}&q=${encodeURIComponent(q)}`;
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    return await res.json();
  }

  function attachAutocomplete(input){
    const type = input.getAttribute('data-ac');
    if (!type) return;
    const box = document.createElement('div');
    box.className = 'ac-box';
    box.style.position='absolute';
    box.style.zIndex='50';
    box.style.left='0';
    box.style.right='0';
    box.style.top='calc(100% + 4px)';
    box.style.display='none';
    box.style.maxHeight='260px';
    box.style.overflow='auto';

    const wrap = input.parentElement;
    if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';
    wrap.appendChild(box);

    let last = '';
    let timer = null;

    function hide(){ box.style.display='none'; box.innerHTML=''; }
    function show(items){
      box.innerHTML='';
      if (!items || !items.length){ hide(); return; }
      items.slice(0,15).forEach(it=>{
        const btn = document.createElement('button');
        btn.type='button';
        const hideId = (input.getAttribute('data-ac-hide-id') === '1');
        const strip = (s)=>String(s||'').replace(/^#\d+\s+/,'').trim();
        const label = hideId ? strip(it.value || it.label) : String(it.label || it.value || '');
        btn.innerHTML = hideId ? `${label}` : `<b>#${it.id}</b> ${label}`;
        btn.addEventListener('click', ()=>{
          input.value = label;
          if (input.dataset.targetId){
            const hidden = document.getElementById(input.dataset.targetId);
            if (hidden) hidden.value = String(it.id);
          }
          hide();
          input.dispatchEvent(new Event('change'));
        });
        box.appendChild(btn);
      });
      box.style.display='block';
    }

    input.addEventListener('input', ()=>{
      const q = input.value.trim();
      if (q.length < 2){ hide(); return; }
      if (q === last) return;
      last = q;
      if (timer) clearTimeout(timer);
      timer = setTimeout(async ()=>{
        try{
          const data = await fetchSuggest(type, q);
          if (data && data.ok){
            show(data.items || []);
          } else hide();
        }catch(e){ hide(); }
      }, 180);
    });
    input.addEventListener('blur', ()=> setTimeout(hide, 200));
  }

  document.querySelectorAll('input[data-ac]').forEach(attachAutocomplete);

  // Build visibility toggles
  const visSel = document.getElementById('buildVisibility');
  const sharedWrap = document.getElementById('sharedToWrap') || document.getElementById('sharedUserBlock');
  if (visSel && sharedWrap){
    function sync(){
      sharedWrap.style.display = (visSel.value === 'shared') ? 'block' : 'none';
    }
    visSel.addEventListener('change', sync);
    sync();
  }

  // Image fallback chain. Used from <img onerror="..."> attributes.
  window.encImgFallback = function(img, urls){
    try{
      if (!img || !urls || !urls.length) return;
      let i = parseInt(img.dataset.fallbackIndex || '0', 10);
      if (!isFinite(i) || i < 0) i = 0;
      if (i >= urls.length) { img.onerror = null; return; }
      img.dataset.fallbackIndex = String(i + 1);
      img.src = urls[i];
    }catch(e){}
  };

  // Copy helper (fallback when navigator.clipboard is unavailable)
  window.encCopyText = async function(text){
    try{
      if (navigator.clipboard && navigator.clipboard.writeText){
        await navigator.clipboard.writeText(text);
        return true;
      }
    }catch(e){}
    try{
      const ta = document.createElement('textarea');
      ta.value = String(text);
      ta.style.position='fixed';
      ta.style.left='-9999px';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      return true;
    }catch(e){}
    return false;
  };

})();
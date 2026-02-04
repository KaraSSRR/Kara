(function(){
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }

  function insertAtCursor(el, text){
    if (!el) return;
    const start = el.selectionStart ?? el.value.length;
    const end = el.selectionEnd ?? el.value.length;
    const before = el.value.substring(0, start);
    const after = el.value.substring(end);
    el.value = before + text + after;
    const pos = start + text.length;
    el.setSelectionRange(pos, pos);
    el.focus();
  }

  function wrapSelection(el, open, close){
    if (!el) return;
    const start = el.selectionStart ?? 0;
    const end = el.selectionEnd ?? 0;
    const sel = el.value.substring(start, end);
    const before = el.value.substring(0, start);
    const after = el.value.substring(end);
    el.value = before + open + sel + close + after;
    el.setSelectionRange(start + open.length, end + open.length);
    el.focus();
  }

  function apiUrl(path){
    const base = (window.FORUM && FORUM.base) ? FORUM.base : '/forum';
    return base + path;
  }

  async function apiPost(path, data){
    const res = await fetch(apiUrl(path), {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: data
    });
    return res.json();
  }

  function initToolbar(){
    qsa('.bb-toolbar').forEach(toolbar=>{
      let targetId = toolbar.getAttribute('data-target');
      if (targetId && targetId.startsWith('#')) targetId = targetId.slice(1);
      const ta = targetId ? qs('#'+CSS.escape(targetId)) : qs('textarea', toolbar.closest('form'));

      toolbar.addEventListener('click', async (e)=>{
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        e.preventDefault();
        const action = btn.getAttribute('data-action');

        // поддерживаем оба набора action: b/i/u/s и bold/italic/underline/strike
        if (action === 'b' || action === 'bold') return wrapSelection(ta, '[b]', '[/b]');
        if (action === 'i' || action === 'italic') return wrapSelection(ta, '[i]', '[/i]');
        if (action === 'u' || action === 'underline') return wrapSelection(ta, '[u]', '[/u]');
        if (action === 's' || action === 'strike') return wrapSelection(ta, '[s]', '[/s]');
        if (action === 'quote') return wrapSelection(ta, '[quote]', '[/quote]');
        if (action === 'code') return wrapSelection(ta, '[code]', '[/code]');

        if (action === 'url' || action === 'link'){
          const link = prompt('Ссылка (http/https или /path):');
          if (!link) return;
          const label = prompt('Текст ссылки (можно пусто):') || '';
          if (label.trim() === '') insertAtCursor(ta, `[url]${link}[/url]`);
          else insertAtCursor(ta, `[url=${link}]${label}[/url]`);
          return;
        }

        if (action === 'img'){
          const link = prompt('Ссылка на изображение (http/https или /...):');
          if (!link) return;
          insertAtCursor(ta, `[img]${link}[/img]`);
          return;
        }

        if (action === 'upload'){
          if (!(window.FORUM && FORUM.canUpload)){
            alert('Загрузка недоступна.');
            return;
          }
          const fileInput = document.createElement('input');
          fileInput.type = 'file';
          fileInput.accept = 'image/*';
          fileInput.onchange = async ()=>{
            const file = fileInput.files && fileInput.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('csrf', (window.FORUM && FORUM.csrf) ? FORUM.csrf : '');
            fd.append('file', file);
            try {
              btn.disabled = true;
              const j = await apiPost('/api/upload.php', fd);
              if (j && j.ok){
                insertAtCursor(ta, `[img]${j.url}[/img]`);
              } else {
                alert((j && (j.text || j.error)) ? (j.text || j.error) : 'Ошибка загрузки');
              }
            } catch(err){
              alert('Ошибка загрузки');
            } finally {
              btn.disabled = false;
            }
          };
          fileInput.click();
          return;
        }
      });
    });
  }

  function initQuoteReply(){
    // Quote button per post
    qsa('[data-quote-post]').forEach(a=>{
      a.addEventListener('click', async (e)=>{
        e.preventDefault();
        const pid = a.getAttribute('data-quote-post');
        const ta = qs('#reply_text') || qs('#replyContent');
        if (!ta) return;
        try {
          const u = apiUrl('/api/quote.php?post_id=' + encodeURIComponent(pid) + '&csrf=' + encodeURIComponent((window.FORUM && FORUM.csrf) ? FORUM.csrf : ''));
          const res = await fetch(u, { headers: { 'Accept': 'application/json' } });
          const j = await res.json();
          if (j && j.ok){
            insertAtCursor(ta, j.quote + "\n");
          } else {
            alert(j && (j.text || j.error) ? (j.text || j.error) : 'Ошибка');
          }
        } catch(err){
          alert('Ошибка');
        }
      });
    });

    // Reply button per post
    qsa('[data-reply-post]').forEach(a=>{
      a.addEventListener('click', (e)=>{
        e.preventDefault();
        const pid = a.getAttribute('data-reply-post');
        const ta = qs('#reply_text') || qs('#replyContent');
        const hidden = qs('#reply_to') || qs('#replyTo');
        if (hidden) hidden.value = pid;
        if (ta){
          insertAtCursor(ta, `>>#${pid} `);
        }
        const form = qs('#reply_form');
        if (form) form.scrollIntoView({behavior:'smooth', block:'center'});
      });
    });
  }

  function initReactions(){
    qsa('.forum-reactions').forEach(box=>{
      box.addEventListener('click', async (e)=>{
        const btn = e.target.closest('button.react-btn');
        if (!btn) return;
        if (!(window.FORUM && FORUM.canReact)) return;
        e.preventDefault();
        const postId = box.getAttribute('data-post-id');
        const reaction = btn.getAttribute('data-reaction');
        const fd = new FormData();
        fd.append('csrf', (window.FORUM && FORUM.csrf) ? FORUM.csrf : '');
        fd.append('post_id', postId);
        fd.append('reaction', reaction);
        try {
          const j = await apiPost('/api/react.php', fd);
          if (j && j.ok && j.html){
            box.outerHTML = j.html;
            initReactions();
          } else {
            alert(j && (j.text||j.error) ? (j.text||j.error) : 'Ошибка');
          }
        } catch(err){
          alert('Ошибка');
        }
      });
    });
  }

  function highlightAnchor(){
    const hash = window.location.hash;
    if (!hash) return;
    const el = qs(hash);
    if (!el) return;
    el.classList.add('highlight');
    setTimeout(()=>el.classList.remove('highlight'), 1800);
  }

  document.addEventListener('DOMContentLoaded', function(){
    initToolbar();
    initQuoteReply();
  initImageZoom();
    initReactions();
    highlightAnchor();
  });
})();


function initImageZoom(){
  document.addEventListener('click', function(e){
    var t = e.target;
    if (!t) return;
    // поддержка клика по картинкам из [img]
    if (t.tagName === 'IMG' && t.classList.contains('bbcode-img')) {
      if (t.closest && t.closest('a')) return;
      window.open(t.src, '_blank');
    }
  });
}

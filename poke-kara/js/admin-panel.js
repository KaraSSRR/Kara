// Универсальный обработчик для всех форм админ-панели (AJAX, без перезагрузки)
// Версия: расширенная (type из data-action, toast, modal, fill, event status box)
document.addEventListener("DOMContentLoaded", function() {

  // =========================
  //         UI helpers
  // =========================
  function ensureToastRoot() {
    let root = document.getElementById('admin-toast-root');
    if (root) return root;
    root = document.createElement('div');
    root.id = 'admin-toast-root';
    root.style.position = 'fixed';
    root.style.right = '18px';
    root.style.bottom = '18px';
    root.style.zIndex = '2147483600';
    root.style.display = 'flex';
    root.style.flexDirection = 'column';
    root.style.gap = '10px';
    document.body.appendChild(root);
    return root;
  }

  function toast(text, isError) {
    const root = ensureToastRoot();
    const el = document.createElement('div');
    el.style.minWidth = '240px';
    el.style.maxWidth = '460px';
    el.style.padding = '12px 14px';
    el.style.borderRadius = '10px';
    el.style.boxShadow = '0 8px 24px rgba(0,0,0,.15)';
    el.style.fontSize = '14px';
    el.style.lineHeight = '1.35';
    el.style.background = isError ? '#ffe7ea' : '#e8fff1';
    el.style.color = isError ? '#b00020' : '#0c6a35';
    el.style.border = isError ? '1px solid #ffb7c1' : '1px solid #a8f0c2';
    el.style.whiteSpace = 'pre-wrap';
    el.textContent = text || (isError ? 'Ошибка' : 'Успех');

    const close = document.createElement('span');
    close.textContent = '×';
    close.style.float = 'right';
    close.style.marginLeft = '12px';
    close.style.cursor = 'pointer';
    close.style.fontWeight = '700';
    close.onclick = function() {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    };
    el.appendChild(close);

    root.appendChild(el);
    setTimeout(function() {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    }, 4500);
  }

  function ensureModal() {
    let overlay = document.getElementById('admin-modal-overlay');
    let modal = document.getElementById('admin-modal');

    if (overlay && modal) return { overlay, modal };

    overlay = document.createElement('div');
    overlay.id = 'admin-modal-overlay';
    overlay.style.position = 'fixed';
    overlay.style.left = '0';
    overlay.style.top = '0';
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.background = 'rgba(0,0,0,.55)';
    overlay.style.display = 'none';
    overlay.style.zIndex = '2147483646';

    modal = document.createElement('div');
    modal.id = 'admin-modal';
    modal.style.position = 'fixed';
    modal.style.left = '50%';
    modal.style.top = '50%';
    modal.style.transform = 'translate(-50%,-50%)';
    modal.style.width = 'min(920px, calc(100vw - 36px))';
    modal.style.maxHeight = 'calc(100vh - 36px)';
    modal.style.background = '#fff';
    modal.style.borderRadius = '14px';
    modal.style.boxShadow = '0 18px 50px rgba(0,0,0,.25)';
    modal.style.display = 'none';
    modal.style.flexDirection = 'column';
    modal.style.overflow = 'hidden';
    modal.style.zIndex = '2147483647';

    const head = document.createElement('div');
    head.style.display = 'flex';
    head.style.alignItems = 'center';
    head.style.justifyContent = 'space-between';
    head.style.padding = '12px 14px';
    head.style.borderBottom = '1px solid #eee';

    const title = document.createElement('div');
    title.id = 'admin-modal-title';
    title.style.fontWeight = '700';
    title.style.fontSize = '15px';
    title.textContent = 'Информация';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Закрыть';
    btn.style.padding = '7px 10px';
    btn.style.borderRadius = '10px';
    btn.style.border = '1px solid #ddd';
    btn.style.background = '#fafafa';
    btn.style.cursor = 'pointer';

    const body = document.createElement('div');
    body.id = 'admin-modal-body';
    body.style.padding = '14px';
    body.style.overflow = 'auto';

    head.appendChild(title);
    head.appendChild(btn);
    modal.appendChild(head);
    modal.appendChild(body);

    function closeModal() {
      overlay.style.display = 'none';
      modal.style.display = 'none';
    }

    btn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });

    document.body.appendChild(overlay);
    document.body.appendChild(modal);

    return { overlay, modal };
  }

  function showModal(title, html) {
    const m = ensureModal();
    const t = document.getElementById('admin-modal-title');
    const b = document.getElementById('admin-modal-body');
    if (t) t.textContent = title || 'Информация';
    if (b) b.innerHTML = html || '';
    m.overlay.style.display = 'block';
    m.modal.style.display = 'flex';
  }

  // =========================
  //         Data helpers
  // =========================
  function getMsgBox(form) {
    return (
      form.querySelector('.panel-msg') ||
      form.querySelector('#location-upload-msg') ||
      document.getElementById('location-upload-msg') ||
      document.querySelector('.admin-panel-msg') ||
      null
    );
  }

  function setMsg(msgBox, text, isError) {
    if (!msgBox) return;
    msgBox.innerHTML = '<span style="color:' + (isError ? 'crimson' : 'seagreen') + ';">' + (text || '') + '</span>';
  }

  function tryParseJson(text) {
    try { return JSON.parse(text); } catch (e) { return null; }
  }

  function applyFill(form, fill) {
    if (!fill || typeof fill !== 'object') return;

    // Ищем не только в форме, но и в ближайшем контейнере вкладки,
    // чтобы systemDbLoad мог заполнить соседнюю форму systemDbSave
    let scope = form;
    const tab = form.closest('.admin-tab') || form.closest('.tab') || form.parentElement;
    if (tab) scope = tab;

    Object.keys(fill).forEach(function(k) {
      const v = fill[k];

      const el = scope.querySelector('[name="' + CSS.escape(k) + '"]');
      if (!el) return;
      if (el.disabled) return;

      if (el.type === 'checkbox') {
        el.checked = !!v;
        return;
      }
      if (el.tagName === 'SELECT') {
        el.value = String(v);
        return;
      }
      el.value = (v === null || typeof v === 'undefined') ? '' : String(v);
    });
  }

  function updateEventStatusBox(form, html) {
    if (!html) return;
    const tab = form.closest('.admin-tab') || document;
    const box = tab.querySelector('#event_status_box') || document.getElementById('event_status_box');
    if (box) box.innerHTML = html;
  }

  // =========================
  //         Main handler
  // =========================

  // Делегирование событий: подходит для динамически вставленных форм
  document.addEventListener('submit', function(e) {
    // Только для форм с классом admin-action-form или id upload-location-form
    const form = e.target;
    if (
      form.classList &&
      (form.classList.contains('admin-action-form') || form.id === 'upload-location-form')
    ) {
      e.preventDefault();

      // Сообщение выводим в panel-msg или location-upload-msg, если есть
      const msgBox = getMsgBox(form);
      if (msgBox) msgBox.innerHTML = '<span style="color:#888;">Загрузка...</span>';

      const formData = new FormData(form);

      // -------------------------
      // ВАЖНО: type из data-action
      // -------------------------
      const action = (form.dataset && form.dataset.action) ? String(form.dataset.action) : (form.getAttribute('data-action') || '');
      const curType = formData.get('type');
      if (!curType || String(curType).trim() === '') {
        if (action) formData.set('type', action);
      }
      // для совместимости: некоторые панели ждут action
      const curAction = formData.get('action');
      if ((!curAction || String(curAction).trim() === '') && action) {
        formData.set('action', action);
      }

      let actionUrl = form.getAttribute('action') || '/do/panel.php';

      fetch(actionUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      })
      .then(async resp => {
        const text = await resp.text();
        if (!resp.ok) {
          // даже при 500 сервер мог вернуть html/php error
          throw new Error('HTTP ' + resp.status + ': ' + (text || '').slice(0, 400));
        }
        const json = tryParseJson(text);
        if (!json) {
          // сервер вернул не json
          throw new Error('Ответ не JSON: ' + (text || '').slice(0, 400));
        }
        return json;
      })
      .then(data => {
        const isErr = !!data.error;
        const msg = data.msg || (isErr ? 'Ошибка!' : 'Успех!');
        setMsg(msgBox, msg, isErr);
        toast(msg, isErr);

        // поддержка data.data: объединяем корневые ключи и data-ключи
        const payload = Object.assign({}, data || {}, (data && data.data) ? data.data : {});

        // fill (автозаполнение)
        if (payload && payload.fill) applyFill(form, payload.fill);

        // event status html
        if (payload && payload.event_status_html) updateEventStatusBox(form, payload.event_status_html);

        // Если сервер прислал статус, но не прислал modal_html — показываем статус в модалке принудительно
        if (payload && payload.event_status_html && !payload.modal_html) {
          if (action === 'eventStatus' || action === 'eventSta' || action === 'event_status' || action === 'event_monitor') {
            showModal(payload.modal_title || 'Статус ивента', payload.event_status_html);
          }
        }

        // modal html
        if (payload && payload.modal_html) {
          const title = payload.modal_title || 'Информация';
          showModal(title, payload.modal_html);
        }

        // Для загрузки картинки — выводим превью, если есть src
        if (
          (action === 'upload_location_image' || form.id === 'upload-location-form')
          && payload && payload.src
        ) {
          const img = document.createElement('img');
          img.src = payload.src + "?t=" + Date.now();
          img.style.maxWidth = "220px";
          img.style.display = "block";
          img.style.margin = "10px 0";
          if (msgBox) msgBox.appendChild(img);
        }

        // Частный кейс: сервер вернул html для вставки
        if (payload && payload.html) {
          // если есть целевой контейнер
          const tgt = form.getAttribute('data-target');
          if (tgt) {
            const node = document.querySelector(tgt);
            if (node) node.innerHTML = payload.html;
          }
        }
      })
      .catch(err => {
        const text = (err && err.message) ? err.message : String(err);
        if (msgBox) msgBox.innerHTML = '<span style="color:crimson;">Ошибка: ' + text + '</span>';
        toast(text, true);
      });
    }
  });

});

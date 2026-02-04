// UI сдачи без NPC 55
// Подключите этот файл на странице с БП

function bpPost(url, data) {
  return fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(data)
  }).then(r => r.json());
}

function bpEnsureModal() {
  let m = document.getElementById('bpModal');
  if (!m) {
    m = document.createElement('div');
    m.id = 'bpModal';
    m.innerHTML = '<div id="bpModalBackdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9998;"></div>'
      + '<div id="bpModalBox" style="position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.3);z-index:9999;width:360px;max-width:92vw;"><div id="bpModalBody" style="padding:14px 14px 10px 14px;"></div></div>';
    document.body.appendChild(m);
    m.addEventListener('click', (e) => {
      if (e.target.id === 'bpModalBackdrop') bpModalClose();
    });
  }
  return m;
}
function bpModalOpen(html) {
  bpEnsureModal();
  document.getElementById('bpModalBody').innerHTML = html;
  document.getElementById('bpModal').style.display = 'block';
}
function bpModalClose() {
  const m = document.getElementById('bpModal');
  if (m) m.style.display = 'none';
}

function bpMissionPick(missionId) {
  bpPost('/do/BattlePassMission.php', { action: 'pokelist', mission_id: missionId })
    .then(res => {
      if (res.error) { alert(res.error); return; }
      const content = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <div style="font-weight:800;color:#5958e8;">Выбор покемона</div>
          <button onclick="bpModalClose()" style="border:0;background:#eef1ff;border-radius:6px;padding:4px 8px;color:#6d6de3;cursor:pointer;">Закрыть</button>
        </div>
        ${res.html || '<div>Нет подходящих покемонов.</div>'}
      `;
      bpModalOpen(content);
    })
    .catch(() => alert('Ошибка связи с сервером'));
}

function bpMissionSubmit(missionId, pokeId) {
  if (!confirm('Сдать выбранного покемона для выполнения задания? Это действие необратимо.')) return;
  bpPost('/do/BattlePassMission.php', { action: 'submit', mission_id: missionId, selected_id: pokeId })
    .then(res => {
      if (res.error) { alert(res.error); return; }
      alert(res.message || 'Готово');
      bpModalClose();
      // Обновить раздел миссий, если есть функция перерисовки
      if (typeof battlepass_category === 'function') {
        battlepass_category('mission');
      } else {
        location.reload();
      }
    })
    .catch(() => alert('Ошибка связи с сервером'));
}
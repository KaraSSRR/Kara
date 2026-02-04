<!-- =======  С Т И Л Ь  ======= -->
<style>
.Registration {
  max-width: 600px;
  margin: 2rem auto;
  padding: 2rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 6px 20px rgba(155, 89, 182, 0.15);
  font-family: "Segoe UI", sans-serif;
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.Step {
  margin-bottom: 1.5rem;
  position: relative;
  transition: 0.3s ease;
}

.Step.error input {
  border-color: #e74c3c;
  background: #fff6f6;
  animation: shake 0.3s ease-in-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}

label {
  display: block;
  margin-bottom: .4rem;
  font-weight: 600;
  color: #884ea0;
}

input {
  width: 100%;
  padding: .6rem 1rem;
  border: 1px solid #ccc;
  border-radius: 8px;
  transition: 0.25s ease;
  font-size: 1rem;
  background-color: #fafafa;
}

input:focus {
  border-color: #9b59b6;
  box-shadow: 0 0 6px rgba(155, 89, 182, 0.3);
  outline: none;
  background-color: #fff;
}

input::placeholder {
  color: #bbb;
  opacity: 1;
  transition: color 0.3s;
}

input:focus::placeholder {
  color: transparent;
}

.Sub {
  font-size: .85rem;
  color: #777;
  margin-top: .3rem;
}

.error-text {
  font-size: .8rem;
  color: #e74c3c;
  margin-top: .3rem;
}

.Gender {
  display: flex;
  align-items: center;
  gap: .8rem;
  font-weight: 600;
  color: #884ea0;
  user-select: none;
  font-size: 1.1rem;
}

.Gender .Arrow {
  cursor: pointer;
  font-size: 1.5rem;
  color: #af7ac5;
  transition: color 0.3s;
}

.Gender .Arrow:hover {
  color: #9b59b6;
}

.accept-rule {
  font-size: .85rem;
  color: #555;
  margin-bottom: .8rem;
}

.accept-rule a {
  color: #9b59b6;
  text-decoration: underline;
}

.divReady {
  text-align: center;
  margin-top: 1.8rem;
}

.GoReg {
  padding: .6rem 1.5rem;
  font-weight: bold;
  border: none;
  border-radius: 8px;
  background: linear-gradient(to right, #9b59b6, #af7ac5);
  color: #fff;
  transition: background 0.3s, transform 0.2s;
  font-size: 1rem;
}

.GoReg:hover {
  background: linear-gradient(to right, #884ea0, #a569bd);
  transform: scale(1.04);
  cursor: pointer;
}

/* Mobile-friendly */
@media (max-width: 480px) {
  .Registration {
    padding: 1rem;
    margin: 1rem;
  }

  .Gender {
    justify-content: space-between;
  }

  .GoReg {
    width: 100%;
  }
}
.custom-modal {
  position: fixed;
  z-index: 9999;
  left: 0; top: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
}
.custom-modal-content {
  background: #fff;
  color: #222;
  border-radius: 16px;
  min-width: 320px;
  max-width: 92vw;
  padding: 2em 1.5em 1.5em 1.5em;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0,0,0,0.25);
  position: relative;
  font-family: inherit;
}
.custom-modal-close {
  position: absolute; right: 20px; top: 20px; font-size: 2em; cursor: pointer; color: #888;
}
#successModalTitle {
  font-size: 1.6em;
  margin-bottom: 0.6em;
  color: #27ae60;
}
#successModalText {
  font-size: 1.13em;
  margin-bottom: 1.2em;
  margin-top: 0.2em;
}
#successModalOk {
  margin-top: 0.5em;
  padding: 0.7em 2em;
  border: none;
  background: #27ae60;
  color:#fff;
  border-radius: 8px;
  font-size: 1.1em;
  font-weight: bold;
  cursor:pointer;
  transition: background 0.2s;
}
#successModalOk:hover { background: #219150; }
</style>

<div class="Registration" id="registration-container">
  <form id="regForm" onsubmit="return false;">
    <div class="Step" id="step-login">
      <label>Имя персонажа*</label>
      <input type="text" id="regLogin" placeholder="Имя персонажа" autocomplete="username">
      <div class="Sub">Придумайте имя для своего персонажа.</div>
      <div class="error-text" id="err-login"></div>
    </div>
    <div class="Step" id="step-pass">
      <label>Пароль*</label>
      <input type="password" id="regPass" placeholder="Пароль" autocomplete="new-password">
      <div class="Sub">Придумайте надежный пароль.</div>
      <div class="error-text" id="err-pass"></div>
    </div>
    <div class="Step" id="step-pass2">
      <label>Повтор пароля*</label>
      <input type="password" id="regDblPass" placeholder="Пароль еще раз" autocomplete="new-password">
      <div class="Sub">Повторите пароль, чтобы избежать ошибок.</div>
      <div class="error-text" id="err-pass2"></div>
    </div>
    <div class="Step" id="step-mail">
      <label>Электронная почта*</label>
      <input type="email" id="regMail" placeholder="Электронная почта" autocomplete="email">
      <div class="Sub">Нужна для восстановления доступа.</div>
      <div class="error-text" id="err-mail"></div>
    </div>
    <div class="Step">
      <label>Пол персонажа*</label>
      <div class="Gender">
        <span class="Arrow" tabindex="0" role="button" aria-label="Предыдущий пол" onclick="editGender()">«</span>
        <span id="GenderDiv" data-patch="m" onclick="editGender()" tabindex="0" role="button" aria-label="Сменить пол">Мужской</span>
        <span class="Arrow" tabindex="0" role="button" aria-label="Следующий пол" onclick="editGender()">»</span>
      </div>
    </div>
    <!-- Карусель выбора базовой модели -->
    <div class="Step" id="step-model">
      <label>Выбор базовой модели*</label>
      <div class="model-carousel">
        <span class="model-arrow" id="modelPrev" tabindex="0" role="button" aria-label="Предыдущая модель">&lt;</span>
        <div class="model-view" id="modelView">
          <!-- сюда будет подставлено изображение -->
        </div>
        <span class="model-arrow" id="modelNext" tabindex="0" role="button" aria-label="Следующая модель">&gt;</span>
      </div>
      <input type="hidden" id="baseModel" value="4">
      <div class="Sub">Выберите внешний вид вашего персонажа.</div>
      <div class="error-text" id="err-model"></div>
    </div>
    <div class="Step">
      <label>Реферальный код</label>
      <input type="text" id="refCode" placeholder="Реферальный код" autocomplete="off">
    </div>
    <div class="accept-rule">Поля с <b>*</b> обязательны к заполнению.</div>
    <div class="accept-rule">Нажимая «Готово», вы соглашаетесь с
      <a href="/?route=rules#rules" target="_blank" rel="noopener noreferrer">правилами игры</a>.
    </div>
    <div class="divReady">
      <input type="button" class="GoReg" id="regSubmit" value="Готово!">
    </div>
    <div class="Inputs"></div>
  </form>
  <!-- Кастомное модальное окно для успеха регистрации -->
  <div id="successModal" class="custom-modal" style="display:none;">
    <div class="custom-modal-content">
      <span class="custom-modal-close" id="successModalClose">&times;</span>
      <h2 id="successModalTitle"></h2>
      <div id="successModalText"></div>
      <button id="successModalOk">OK</button>
    </div>
  </div>
</div>

<style>
.model-carousel {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 24px 0;
  gap: 36px;
}

.model-arrow {
  font-size: 54px;
  cursor: pointer;
  user-select: none;
  padding: 0 18px;
  color: #435176;
  transition: color 0.18s, transform 0.14s;
  line-height: 1;
  filter: drop-shadow(0 2px 0 #dde7f7);
}
.model-arrow:hover {
  color: #ffb947;
  transform: scale(1.17);
}

.model-view {
  width: 314px;
  height: 550px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #d8e7fa 0%, #fafdff 100%);
  border-radius: 28px;
  box-shadow: 0 6px 44px 0 rgba(80, 110, 180, 0.13), 0 1.5px 7px 0 #e0e8f7;
  border: 3px solid #c3d3ef;
  position: relative;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.model-view img {
  width: 314px;
  height: 550px;
  object-fit: contain;
  border-radius: 24px;
  border: 5px solid #e6eafa;
  background: #f5fafd;
  box-shadow: 0 4px 28px rgba(80, 110, 180, 0.16);
  transition: transform 0.13s;
}

.model-view img:active {
  transform: scale(0.97);
}

/* Современная анимация появления */
.model-view img {
  animation: modelFadeIn 0.35s cubic-bezier(0.23,0.62,0.23,0.95);
}
@keyframes modelFadeIn {
  from { opacity: 0; transform: scale(0.92);}
  to   { opacity: 1; transform: scale(1);}
}

@media (max-width: 1024px) {
  .model-view {
    width: 220px;
    height: 386px;
    border-radius: 18px;
  }
  .model-view img {
    width: 220px;
    height: 386px;
    border-radius: 14px;
  }
  .model-arrow {
    font-size: 40px;
    padding: 0 10px;
  }
}

@media (max-width: 700px) {
  .model-carousel {
    gap: 10px;
    margin: 14px 0;
  }
  .model-view {
    width: 48vw;
    height: 80vw;
    max-width: 340px;
    max-height: 560px;
    min-width: 120px;
    min-height: 190px;
    border-radius: 14px;
  }
  .model-view img {
    width: 48vw;
    height: 80vw;
    max-width: 314px;
    max-height: 550px;
    min-width: 120px;
    min-height: 190px;
    border-radius: 10px;
  }
  .model-arrow {
    font-size: 32px;
    padding: 0 2vw;
  }
}

@media (max-width: 480px) {
  .model-view {
    width: 220px;
    height: 450px;
    max-width: 220px;
    max-height: 450px;
    min-width: 120px;
    min-height: 150px;
    border-radius: 10px;
  }
  .model-view img {
    width: 220px;
    height: 450px;
    max-width: 220px;
    max-height: 450px;
    min-width: 100px;
    min-height: 150px;
    border-radius: 8px;
  }
  .model-arrow {
    font-size: 26px;
    padding: 0 1vw;
  }
}
</style>

<script>
const baseModels = {
  'm': [4, 5, 6],
  'f': [1, 2, 3]
};
let currentModelIdx = 0;

function updateModelCarousel() {
  const gender = document.getElementById('GenderDiv').dataset.patch;
  const models = baseModels[gender];
  if (!models) return;
  const idx = currentModelIdx % models.length;
  const modelId = models[idx];
  document.getElementById('baseModel').value = modelId;
  // Формируем путь к аватарке: img/avatars/model/ava/{model}/{model}/{model}a.png
  const imgPath = `img/avatars/model/ava/${modelId}/${modelId}/${modelId}a.png`;
  document.getElementById('modelView').innerHTML =
    `<img src="/${imgPath}" alt="Base model ${modelId}">`;
}

function switchModel(dir) {
  const gender = document.getElementById('GenderDiv').dataset.patch;
  const models = baseModels[gender];
  if (!models) return;
  currentModelIdx = (currentModelIdx + dir + models.length) % models.length;
  updateModelCarousel();
}

function editGender() {
  const el = document.getElementById('GenderDiv');
  if (!el) return;
  const val = el.dataset.patch === 'm' ? 'f' : 'm';
  el.dataset.patch = val;
  el.textContent = val === 'm' ? 'Мужской' : 'Женский';
  // Обновляем модель при смене пола
  if (typeof currentModelIdx !== 'undefined') currentModelIdx = 0;
  if (typeof updateModelCarousel === 'function') updateModelCarousel();
}

document.addEventListener('DOMContentLoaded', function () {
  ['regLogin', 'regPass', 'regDblPass', 'regMail'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', clearAllErrors);
  });

  // Карусель управления
  if (document.getElementById('modelPrev')) {
    document.getElementById('modelPrev').addEventListener('click', function() {
      if (typeof switchModel === 'function') switchModel(-1);
    });
  }
  if (document.getElementById('modelNext')) {
    document.getElementById('modelNext').addEventListener('click', function() {
      if (typeof switchModel === 'function') switchModel(1);
    });
  }

  // Начальное отображение модели
  if (typeof updateModelCarousel === 'function') updateModelCarousel();

  // Регистрация
  const btn = document.getElementById('regSubmit');
  if (btn) {
    btn.addEventListener('click', registration);
  }
});

function setError(field, msg) {
  const step = document.getElementById('step-' + field);
  const err = document.getElementById('err-' + field);
  if (step) step.classList.add('error');
  if (err) err.textContent = msg;
  // Прокрутка к ошибке (только к первой)
  if (step && !setError._scrolled) {
    setError._scrolled = true;
    step.scrollIntoView({behavior: "smooth", block: "center"});
    setTimeout(() => { setError._scrolled = false; }, 500); // Сбросить флаг через полсекунды
  }
}

function clearError(field) {
  const step = document.getElementById('step-' + field);
  const err = document.getElementById('err-' + field);
  if (step) step.classList.remove('error');
  if (err) err.textContent = '';
}

function clearAllErrors() {
  ['login', 'pass', 'pass2', 'mail', 'model'].forEach(clearError);
  setError._scrolled = false; // Сбросить флаг при очистке ошибок
}
function validate() {
  clearAllErrors();
  let ok = true;

  const login = document.getElementById('regLogin')?.value.trim() || '';
  const pass = document.getElementById('regPass')?.value.trim() || '';
  const pass2 = document.getElementById('regDblPass')?.value.trim() || '';
  const mail = document.getElementById('regMail')?.value.trim() || '';
  const baseModel = document.getElementById('baseModel')?.value.trim();

  if (!login) { setError('login', 'Введите имя персонажа.'); ok = false; }
  if (!pass) { setError('pass', 'Введите пароль.'); ok = false; }
  if (pass !== pass2) { setError('pass2', 'Пароли не совпадают.'); ok = false; }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(mail)) {
    setError('mail', 'Введите корректный email.');
    ok = false;
  }

  if (!baseModel || isNaN(parseInt(baseModel))) {
    setError('model', 'Выберите базовую модель.');
    ok = false;
  }

  return ok;
}

function registration() {
  if (!validate()) return;

  const formData = new FormData();
  formData.append('login', document.getElementById('regLogin').value.trim());
  formData.append('password', document.getElementById('regPass').value.trim());
  formData.append('mail', document.getElementById('regMail').value.trim());
  formData.append('gender', document.getElementById('GenderDiv').dataset.patch || 'm');
  formData.append('baseModel', document.getElementById('baseModel').value.trim());
  formData.append('refCode', document.getElementById('refCode')?.value.trim() || '');
  formData.append('regCode', document.getElementById('regCode')?.value.trim() || ''); // если поле есть

  $.ajax({
    url: '/do/registration.php',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    dataType: 'json',
    success: function (res) {
      console.log(res);
      if (res.error === 0 && res.redirect) {
        alert(res.message || 'Регистрация прошла успешно!');
        window.location.href = res.redirect;
      } else if (res.field) {
        setError(res.field, res.message || 'Ошибка в поле.');
      } else {
        setError('login', res.text || 'Неизвестная ошибка регистрации.');
      }
    },
    error: function () {
      setError('login', 'Ошибка соединения с сервером.');
    }
  });
}
</script>

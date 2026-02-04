// New

var Index = {
  news: {
    reactNews: function(id, emoji) {
      $.ajax({
        url: '/do/reactNews.php',
        type: 'POST',
        data: {
          id: id,
          reaction: emoji
        },
        dataType: 'json',
        success: function(res) {
          if (res.error === 1) {
            // Покажи попап-повідомлення (можна замінити на кастом)
            alert(res.text || 'Ошибка!');
          } else if (res.error === 0 && res.reactions) {
            // Генеруємо HTML з оновленими даними та выделяем активную реакцию
            let html = '';
            const emojis = {
              like: '❤️',
              love: '😍',
              haha: '😄',
              sad: '😢'
            };
            for (let key in emojis) {
              let isActive = res.userReaction === key ? 'active' : '';
              html += `<button class="reaction-btn${isActive ? ' ' + isActive : ''}" onclick="Index.news.reactNews(${id}, '${key}')">${emojis[key]} <span id="count-${key}-${id}">${res.reactions[key] || 0}</span></button> `;
            }
            $('#reactions-' + id).html(html);
          }
        },
        error: function() {
          alert('Ошибка соединения с сервером.');
        }
      });
    }
  }
};


// New end

function forgot() {
  var mail = $('#uEmail');
  $.ajax({
  url: '/do/forgot',
  type: 'POST',
  data: 'mail=' + mail.val(),
  beforeSend: function(){
    $('#forgotForm').css('display','none');
    $('#forgotForm').prepend('<img src="/img/loader/loader.gif" height="80" />');
  },
  success: function(data) {
    $('#forgotForm img').remove();
    $('#forgotForm').css('display','block');
    data = JSON.parse(data);
    if (data['error'] == 1) {
      $('.AuthError').css('display','flex');
      $('.AuthError').html($.notify(data['text']));
    } else {
      $('.AuthError').remove();
      $('#autorizeForm').remove();
      $('.Auth a').remove();
      var tpl = '<div class="AuthNow">Новый пароль выслан на почту.</div>';
      $('.Auth').html(tpl);
    }
  }
  });
}
function sign() {
  const login = $('#modalLogin').val().trim();
  const password = $('#modalPass').val().trim();

  if (!login || !password) {
    $('#modalAuthError').text('Введите логин и пароль').show();
    return;
  }

  $('#modalAuthError').hide();

  $.ajax({
    url: '/do/sign',
    type: 'POST',
    data: { login, password },
    dataType: 'json',
    beforeSend: function () {
      $('#modalLoginForm button[type=submit]').prop('disabled', true).text('Загрузка...');
    },
    success: function (data) {
      $('#modalLoginForm button[type=submit]').prop('disabled', false).text('Войти');

      if (data.error === 1) {
        $('#modalAuthError').text(data.text || 'Ошибка авторизации').show();
      } else {
        $('#loginModal').hide();

        // Detect mobile (simple method)
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
        if (isMobile) {
          location.reload();
          return;
        }

        // Динамическое обновление DOM после входа для ПК
        const loginName = data.text?.[0] || 'Пользователь';
        const avatarId = data.text?.[3] || 'no-user-img';
        const avatarSrc = `/img/avatars/mini/${avatarId}.png`;

        $('.Auth').html(`
          <div class="auth-content d-flex align-items-center justify-content-end w-100">
            <div class="auth-user d-flex align-items-center">
              <img src="${avatarSrc}" alt="Аватар" class="auth-avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: #fff; border: 2px solid #a97bff; margin-right: 10px;" />
              <span class="user-name" style="color: #fff; font-weight: 700; font-size: 1.15rem;">${loginName}</span>
            </div>
            <div class="auth-actions d-flex align-items-center" style="gap:12px; margin-left:18px;">
              <a href="/world" class="btn btn-sm btn-outline-success">В мир</a>
              <a href="/?route=exit" class="btn btn-sm btn-outline-danger">Выйти</a>
            </div>
          </div>
        `);
        // Можно добавить дополнительную логику для бургер-меню и т.д.
      }
    },
    error: function () {
      $('#modalLoginForm button[type=submit]').prop('disabled', false).text('Войти');
      $('#modalAuthError').text('Ошибка соединения с сервером').show();
    }
  });
}

function renderAuthHtml(data) {
  return `
    <div class="auth-inline">
      <div class="username"><i class="fas fa-user-circle"></i> ${data.text[0]}</div>
      <a class="btn btn-outline-success btn-sm" href="/world">В игру <i class="fas fa-sign-in-alt"></i></a>
      <a class="btn btn-outline-danger btn-sm" href="/?route=exit">Выйти <i class="fas fa-times-circle"></i></a>
    </div>
  `;
}

function renderAuthBox(userData) {
  if (!document.getElementById('auth-inline-style')) {
    const style = document.createElement('style');
    style.id = 'auth-inline-style';
    style.textContent = `
      .auth-inline {
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        font-size: 15px;
        padding: 0.5rem 1rem;
        flex-direction: row;
        flex-wrap: wrap;
      }
      .auth-inline .username {
        font-weight: bold;
        color: #8a2be2;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 1rem;
        white-space: nowrap;
      }
      .auth-inline .username i {
        color: #1abc9c;
      }
      .auth-inline .btn {
        padding: 0.4rem 0.9rem;
        font-size: 0.85rem;
        white-space: nowrap;
      }
      @media (max-width: 576px) {
        .auth-inline {
          justify-content: center;
        }
      }
    `;
    document.head.appendChild(style);
  }

  const [username] = Array.isArray(userData) ? userData : [userData || 'Пользователь'];

  const tpl = `
    <div class="auth-inline">
      <div class="username"><i class="fas fa-user-circle"></i> ${username}</div>
      <a class="btn btn-outline-success btn-sm" href="${DOMAIN}/world">В игру <i class="fas fa-sign-in-alt"></i></a>
      <a class="btn btn-outline-danger btn-sm" href="${DOMAIN}/?route=exit">Выйти <i class="fas fa-times-circle"></i></a>
    </div>
  `;

  $('.Auth').html(tpl);
}

function newPass() {
    $('.Auth').html(`
        <div class="Wrap">
            <form onsubmit="forgot(); return false;" id="autorizeForm" class="form-inline my-2 my-lg-0">
                <input type="text" id="uEmail" placeholder="Электронная почта" class="form-control btn-sm mr-sm-1" />
                <button class="btn btn-sm btn-outline-success my-2 my-sm-0">Отправить</button>
                <button class="btn btn-sm btn-outline-primary my-2 my-sm-0" onclick="backAuth()">Назад</button>
            </form>
            <div class="AuthError"></div>
        </div>
    `);
}

function backAuth() {
    $('.Auth').html(`
        <div class="Wrap">
            <form onsubmit="sign(); return false;" id="autorizeForm" class="form-inline my-2 my-lg-0">
                <input type="text" id="uLogin" placeholder="Логин" class="form-control btn-sm mr-sm-1" />
                <input type="password" id="uPass" placeholder="Пароль" class="form-control btn-sm mr-sm-1" />
                <button class="btn btn-sm btn-outline-success my-2 my-sm-0">Войти</button>
                <button class="btn btn-sm btn-outline-primary my-2 my-sm-0" onclick="newPass()">Забыл пароль</button>
            </form>
            <div class="AuthError"></div>
        </div>
    `);
}

/* === ОТПРАВКА ФОРМЫ РЕГИСТРАЦИИ === */
function registration() {
  const login       = $('#regLogin');
  const password    = $('#regPass');
  const dblPassword = $('#regDblPass');
  const mail        = $('#regMail');
  const regCode     = $('#regCode');
  const refCode     = $('#refCode');
  const gender      = ($('#GenderDiv').attr('data-patch') === 'm' ? 'm' : 'f');
  const baseModel   = $('#baseModel').val(); // Базовая модель

  if (!login.val())                      return login.notify('Введите логин!');
  if (login.val().length < 4 || login.val().length > 16)
                                         return login.notify('Длина логина должна быть от 4 до 16 символов!');
  if (!password.val())                   return password.notify('Введите пароль!');
  if (password.val().length < 6 || password.val().length > 20)
                                         return password.notify('Длина пароля должна быть от 6 до 20 символов!');
  if (!dblPassword.val())                return dblPassword.notify('Повторите пароль!');
  if (password.val() !== dblPassword.val())
                                         return dblPassword.notify('Пароли не совпадают!');
  if (!mail.val())                       return mail.notify('Введите почту!');
  if (!/^([a-z0-9_\.-])+@[a-z0-9-]+(\.[a-z]{2,4}){1,2}$/i.test(mail.val()))
                                         return mail.notify('Введите корректную почту!');
  if (!baseModel || isNaN(parseInt(baseModel)))
                                         return $('#modelView').notify('Выберите базовую модель!');

  $.ajax({
    url         : '/do/registration',
    type        : 'POST',
    dataType    : 'json',
    data        : {
      login   : login.val(),
      password: password.val(),
      mail    : mail.val(),
      gender  : gender,
      baseModel: baseModel, // Передаем выбранную модель
      regCode : regCode.val(),
      refCode : refCode.val()
    },
    beforeSend  : function () {
      $('.Inputs').append('<div class="loader">Регистрация…</div>');
    },
    complete    : function () {
      $('.loader').remove();
    },
    success     : function (resp) {
      console.log('Ответ сервера:', resp);
      if (+resp.error === 0) {
        // Показываем красивое модальное окно вместо alert
        showSuccessModal(resp.title || 'Регистрация успешна!', resp.message || 'Регистрация прошла успешно!', resp.redirect || '/');
      } else if (resp.field) {
        const fieldMap = {
          password: '#regPass',
          mail: '#regMail',
          login: '#regLogin',
          model: '#modelView'
        };
        const selector = fieldMap[resp.field] || '#regLogin';
        $(selector).notify(resp.message || 'Ошибка');
      } else {
        // Для прочих ошибок показываем обычное уведомление
        alert(resp.text || 'Неизвестная ошибка регистрации.');
      }
    },
    error       : function (xhr, status, err) {
      console.error('AJAX-ошибка:', status, err);
      alert('Не удалось отправить запрос. Попробуйте ещё раз.');
    }
  });
}

function editGender(g) {
    var gender = ($('#GenderDiv').attr('data-patch') === 'm' ? 'f' : 'm');
    var genderText = (gender === 'm' ? 'Мужской' : 'Женский');
    $('#GenderDiv').attr('data-patch', gender).html(genderText);

    // Сбросить выбор модели на первую доступную для пола и обновить карусель
    window.currentModelIdx = 0;
    if (typeof updateModelCarousel === 'function') updateModelCarousel();
}

function openLoginModal() {
  $('#loginModal').fadeIn(200);
}
function closeLoginModal() {
  $('#loginModal').fadeOut(200);
}

/**
 * Красивая модалка для уведомления о регистрации
 * @param {string} title - Заголовок модального окна
 * @param {string} message - Сообщение (разрешён HTML)
 * @param {string} redirectUrl - Куда перейти после закрытия модалки
 */
function showSuccessModal(title, message, redirectUrl) {
  document.getElementById('successModalTitle').textContent = title || 'Успех!';
  // Разрешаем HTML для message, чтобы строки с <br> и жирным были красивыми
  document.getElementById('successModalText').innerHTML = message || '';
  document.getElementById('successModal').style.display = 'flex';

  const closeModal = () => {
    document.getElementById('successModal').style.display = 'none';
    if (redirectUrl) window.location.href = redirectUrl;
  };

  document.getElementById('successModalOk').onclick = closeModal;
  document.getElementById('successModalClose').onclick = closeModal;
  // Закрытие по ESC
  document.onkeydown = function(e) {
    if (e.key === "Escape") closeModal();
  }
}
// Открыть модальное окно входа



// Закрыть модальное окно входа



// ========== Сценарии и реплики бота ==============
const guideBotScenarios = {
  main: {
    message: "Привет! Я Гид-Бот — всегда помогу советом. Что подсказать?",
    buttons: [
      { text: "Что делать дальше?", action: "nextStep" },
      { text: "Где ловить редких покемонов?", action: "rarePokemon" },
      { text: "FAQ", action: "faq" },
      { text: "Новости", action: "news" },
      { text: "Анализ моей команды", action: "showTeamBuilder" }
    ]
  },
  nextStep: {
    message: "Проверь свои ежедневные задания — за них дают награды! А ещё можешь посетить арену или поучаствовать в событии недели.",
    buttons: [
      { text: "Назад", action: "main" }
    ]
  },
  rarePokemon: {
    message: "Редкие покемоны чаще появляются в особых зонах (лес, озеро, на рассвете и ночью). Заглядывай туда чаще и используй специальные приманки!",
    buttons: [
      { text: "Назад", action: "main" }
    ]
  },
  faq: {
    message: "Выбери интересующий вопрос:",
    buttons: [
      { text: "Как поймать покемона?", action: "faqCatch" },
      { text: "Как участвовать в турнире?", action: "faqTournament" },
      { text: "Как обмениваться покемонами?", action: "faqTrade" },
      { text: "Назад", action: "main" }
    ]
  },
  faqCatch: {
    message: "Чтобы поймать покемона, кликни по нему на карте и используй покебол. Чем лучше покебол — тем выше шанс успеха!",
    buttons: [
      { text: "Назад к FAQ", action: "faq" }
    ]
  },
  faqTournament: {
    message: "Чтобы принять участие в турнире, перейди в раздел 'Турниры' и выбери доступное событие. Собери команду и сразись с другими тренерами!",
    buttons: [
      { text: "Назад к FAQ", action: "faq" }
    ]
  },
  faqTrade: {
    message: "Обмен покемонами доступен через меню профиля другого игрока. Найди тренера, с которым хочешь обменяться, и отправь ему предложение.",
    buttons: [
      { text: "Назад к FAQ", action: "faq" }
    ]
  },
  news: {
    message: "Сегодня действует ивент: увеличение шанса поймать shiny-покемонов! Не пропусти. Подробнее — в новостях на форуме.",
    buttons: [
      { text: "Назад", action: "main" }
    ]
  }
};

// ========== Открытие/закрытие окна бота ===========
function toggleGuideChat() {
  const chat = document.getElementById('guide-chat');
  if (chat.style.display === 'none' || !chat.style.display) {
    chat.style.display = 'flex';
    if (!chat.dataset.inited) {
      showGuideScenario('main');
      chat.dataset.inited = "1";
    }
  } else {
    chat.style.display = 'none';
  }
}

// ========== Показ сценария и кнопок ===========
function showGuideScenario(scenarioKey) {
  // Скрываем Team Builder при любом переходе сценария, кроме его показа явно
  if (scenarioKey !== "showTeamBuilder") showTeamBuilder(false);

  // Специальный сценарий: Team Builder
  if (scenarioKey === "showTeamBuilder") {
    showTeamBuilder(true);
    const messagesDiv = document.getElementById('guide-messages');
    messagesDiv.innerHTML = `<div class="bot-msg">Укажи цель и состав своей команды — я дам подробный совет по тимбилдингу!</div>`;
    const actionsDiv = document.getElementById('guide-actions');
    actionsDiv.innerHTML = '';
    return;
  }

  const scenario = guideBotScenarios[scenarioKey];
  const messagesDiv = document.getElementById('guide-messages');
  const actionsDiv = document.getElementById('guide-actions');
  if (!scenario) return;

  messagesDiv.innerHTML = `<div class="bot-msg">${scenario.message}</div>`;
  actionsDiv.innerHTML = '';
  scenario.buttons.forEach(btn => {
    const button = document.createElement('button');
    button.innerText = btn.text;
    button.onclick = () => showGuideScenario(btn.action);
    actionsDiv.appendChild(button);
  });
}

// ========== Скрытие/показ Team Builder ===========
function showTeamBuilder(show = true) {
  document.getElementById('guide-team-builder').style.display = show ? 'block' : 'none';
  if (show) {
    // Очищаем поля и совет
    document.getElementById('team-purpose').value = '';
    document.getElementById('team-pokemon').value = '';
    document.getElementById('copilot-advice').innerText = '';
  }
}

// ========== Обработка пользовательского ввода ===========
function sendGuideUserInput(event) {
  event.preventDefault();
  const input = document.getElementById('guide-user-input');
  const value = input.value.trim();
  if (!value) return false;
  input.value = '';

  const messagesDiv = document.getElementById('guide-messages');
  messagesDiv.innerHTML += `<div class="user-msg">${escapeHtml(value)}</div>`;
  messagesDiv.scrollTop = messagesDiv.scrollHeight;

  // Если вопрос про команду — показать Team Builder
  if (/команд|тим|team/i.test(value)) {
    showTeamBuilder(true);
    messagesDiv.innerHTML += `<div class="bot-msg">Давай подберём тебе команду! Укажи цель и покемонов ниже 👇</div>`;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    return false;
  }

  // Иначе — отправка на PHP-обработчик
  fetch('/openai-teambuild.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ purpose: '', mons: '', question: value })
  })
    .then(r => r.json())
    .then(data => {
      messagesDiv.innerHTML += `<div class="bot-msg">${escapeHtml(data.advice || "Ответ не получен. Попробуйте иначе сформулировать вопрос.")}</div>`;
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    })
    .catch(() => {
      messagesDiv.innerHTML += `<div class="bot-msg">Ошибка соединения с Copilot :(</div>`;
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    });
  return false;
}

// ========== Получить совет по тимбилдингу через Team Builder ===========
function getCopilotAdvice() {
  const purpose = document.getElementById('team-purpose').value.trim();
  const mons = document.getElementById('team-pokemon').value.trim();
  const adviceDiv = document.getElementById('copilot-advice');
  if (!purpose && !mons) {
    adviceDiv.innerText = "Пожалуйста, укажи цель и хотя бы одного покемона!";
    return;
  }
  adviceDiv.innerText = "Анализирую...";
  fetch('/openai-teambuild.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ purpose, mons })
  })
    .then(r => r.json())
    .then(data => {
      adviceDiv.innerText = data.advice || "Совет не получен. Попробуйте еще раз!";
    })
    .catch(() => {
      adviceDiv.innerText = "Ошибка сервера Copilot!";
    });
}

// ========== Экранирование html для безопасности ===========
function escapeHtml(str) {
  if (!str) return "";
  return str.replace(/[&<>"']/g, function (m) {
    return ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    })[m];
  });
}

// ========== Инициализация ===========
document.addEventListener('DOMContentLoaded', () => {
  // showGuideScenario('main'); // Раскомментируй если нужно автоприветствие
});

/* PokeKara UI helpers (news + registration model picker) */

document.addEventListener('click', function(e){
  const btn = e.target.closest('.pk-news-more');
  if(!btn) return;

  const id = btn.getAttribute('data-news');
  const full = document.getElementById('pk-news-full-' + id);
  if(!full) return;

  const isOpen = full.style.display !== 'none';
  full.style.display = isOpen ? 'none' : 'block';
  btn.textContent = isOpen ? 'Подробнее' : 'Свернуть';
});

(function(){
  function $(sel){ return window.jQuery ? window.jQuery(sel) : null; }

  window.setGender = function(g){
    const wrap = document.getElementById('GenderDiv');
    if(!wrap) return;
    wrap.setAttribute('data-patch', g === 'm' ? 'm' : 'f');

    const btns = wrap.querySelectorAll('.pk-segBtn');
    btns.forEach(b => b.classList.remove('active'));
    if(btns[0] && g === 'm') btns[0].classList.add('active');
    if(btns[1] && g === 'f') btns[1].classList.add('active');

    // Подстройка модели под диапазон
    const base = document.getElementById('baseModel');
    const view = document.getElementById('modelView');
    if(!base || !view) return;

    const range = (g === 'm') ? [4,6] : [1,3];
    let val = parseInt(base.value || '0', 10) || range[0];
    if(val < range[0] || val > range[1]) val = range[0];
    base.value = String(val);
    view.textContent = 'Модель #' + val;
  };

  function getRange(){
    const wrap = document.getElementById('GenderDiv');
    const g = wrap && wrap.getAttribute('data-patch') === 'f' ? 'f' : 'm';
    return (g === 'm') ? [4,6] : [1,3];
  }
  function setModel(val){
    const base = document.getElementById('baseModel');
    const view = document.getElementById('modelView');
    if(!base || !view) return;
    base.value = String(val);
    view.textContent = 'Модель #' + val;
  }

  window.modelPrev = function(){
    const [min,max] = getRange();
    const base = document.getElementById('baseModel');
    if(!base) return;
    let v = parseInt(base.value || String(min), 10) || min;
    v = (v <= min) ? max : (v - 1);
    setModel(v);
  };

  window.modelNext = function(){
    const [min,max] = getRange();
    const base = document.getElementById('baseModel');
    if(!base) return;
    let v = parseInt(base.value || String(min), 10) || min;
    v = (v >= max) ? min : (v + 1);
    setModel(v);
  };

  // Инициализация при загрузке страницы регистрации (если элементы есть)
  document.addEventListener('DOMContentLoaded', function(){
    const wrap = document.getElementById('GenderDiv');
    const base = document.getElementById('baseModel');
    const view = document.getElementById('modelView');
    if(wrap && base && view){
      const g = wrap.getAttribute('data-patch') === 'f' ? 'f' : 'm';
      window.setGender(g);
    }
  });
})();

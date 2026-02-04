<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['id']) || !isset($_SESSION['login'])) {
    header('Location: /');
    exit;
}

// ВАЖНО: Не указываем 'domain' в setcookie! Это часто вызывает проблемы сессий между страницами.
// Фикс для работы сессии после регистрации на некоторых хостингах: 
// Принудительно обновляем идентификатор сессии и сохраняем куки сессии на корень домена
if (session_status() === PHP_SESSION_ACTIVE) {
    setcookie(session_name(), session_id(), [
        'expires' => time() + 86400, // 1 день
        'path' => '/',
        // 'domain' => $_SERVER['HTTP_HOST'], // НЕ УКАЗЫВАТЬ!
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Получаем логин пользователя из сессии
$name = htmlspecialchars($_SESSION['login']);

// Формируем путь к аватару
$avatar = "/img/avatars/mini/" . $_SESSION['id'] . ".png";
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $avatar)) {
    $avatar = "/img/avatars/mini/no-user-img.png";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Добро пожаловать!</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom, #e0fff8, #d0f0ec);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-image: url('/img/main/pokemon_bg.jpg');
      background-size: cover;
      background-position: center;
    }

    .welcome-box {
      background: rgba(255, 255, 255, 0.92);
      padding: 2rem 3rem;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      text-align: center;
      max-width: 600px;
    }

    .welcome-box img.avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-bottom: 1rem;
    }

    .welcome-box h1 {
      font-size: 2rem;
      color: #138d75;
      margin-bottom: 0.5rem;
    }

    .welcome-box p {
      font-size: 1.15rem;
      color: #444;
      margin-bottom: 1.5rem;
    }

    .welcome-box a {
      display: inline-block;
      padding: 0.7rem 1.5rem;
      background: #1abc9c;
      color: #fff;
      text-decoration: none;
      border-radius: 10px;
      font-weight: bold;
      font-size: 1rem;
      transition: background 0.3s;
    }

    .welcome-box a:hover {
      background: #17a589;
    }

    @media (max-width: 600px) {
      .welcome-box {
        padding: 1.5rem;
        border-radius: 12px;
      }

      .welcome-box h1 {
        font-size: 1.5rem;
      }

      .welcome-box p {
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="welcome-box">
    <img src="<?= $avatar ?>" alt="Аватар" class="avatar">
    <h1>🎉 Добро пожаловать, <?= $name ?>!</h1>
    <p>Вы успешно зарегистрировались в мире <b>Pokémon Emerald</b>.</p>
    <a href="/world">Перейти в игру</a>
  </div>
</body>
</html>
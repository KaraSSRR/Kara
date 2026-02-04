<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Получаем данные о последнем пополнении
$data = $_SESSION['last_recharge'] ?? null;

// Если нет данных — редирект на главную/ошибку
if (
    !$data ||
    $data['created_at'] < time()-600 || // Срок жизни 10 минут
    empty($data['url']) ||
    empty($data['amount'])
) {
    header('Location: /');
    exit;
}

$amount = intval($data['amount']);
$payUrl = $data['url'];
$order_id = htmlspecialchars($data['order_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Подтверждение оплаты</title>
  <style>
    body {
      background: #f2f2fb url('/img/bg-pokemon.jpg') center no-repeat;
      background-size: cover;
      margin: 0;
      min-height: 100vh;
    }
    .recharge-confirm-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 6vh auto 0 auto;
      background: rgba(255,255,255,0.85);
      border-radius: 24px;
      max-width: 420px;
      box-shadow: 0 4px 28px 0 #7b2ff255;
      padding: 40px 28px 30px 28px;
    }
    .logo img {
      height: 64px;
      margin-bottom: 18px;
    }
    h1 {
      font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
      font-size: 2.1em;
      color: #6f2ba1;
      letter-spacing: 1.5px;
      margin-bottom: 12px;
      text-align: center;
      text-shadow: 0 2px 12px #d9b8ff70;
    }
    .desc {
      font-size: 1.15em;
      color: #3e2465;
      margin-bottom: 28px;
      text-align: center;
    }
    .btn-confirm {
      background: linear-gradient(90deg, #7b2ff2 0%, #f357a8 100%);
      color: #fff;
      font-size: 1.18em;
      font-weight: bold;
      padding: 13px 40px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      box-shadow: 0 1px 16px 0 #b09bd6aa;
      transition: background 0.16s, box-shadow 0.16s, transform 0.1s;
      margin-top: 10px;
      margin-bottom: 10px;
      letter-spacing: 1.2px;
    }
    .btn-confirm:hover {
      background: linear-gradient(90deg, #f357a8 0%, #7b2ff2 100%);
      box-shadow: 0 2px 32px 0 #f357a866;
      transform: translateY(-2px) scale(1.04);
    }
    .order-id {
      font-size: 12px;
      color: #888;
      margin-top: 10px;
      letter-spacing: 0.4px;
      user-select: text;
      background: #f7eaff80;
      padding: 2px 10px;
      border-radius: 7px;
    }
    a.cancel-link {
      display: block;
      margin-top: 18px;
      color: #7b2ff2;
      text-decoration: underline;
      font-size: 15px;
      transition: color 0.18s;
      text-align: center;
    }
    a.cancel-link:hover {
      color: #f357a8;
    }
    @media (max-width: 600px) {
      .recharge-confirm-wrap {
        max-width: 99vw;
        padding: 7vw 2vw 5vw 2vw;
      }
      .logo img {
        height: 54px;
      }
      h1 { font-size: 1.35em; }
      .desc { font-size: 1em; }
      .btn-confirm { font-size: 1em; padding: 11px 0; width: 95vw; max-width: 290px; }
    }
  </style>
  <script>
    // Можно добавить JS для UX (например, предупреждение о закрытии страницы)
    window.onload = function() {
      const form = document.querySelector('form');
      form.addEventListener('submit', function() {
        // Можно отправить событие аналитики или заблокировать двойной клик
        document.querySelector('.btn-confirm').disabled = true;
        document.querySelector('.btn-confirm').innerText = 'Переход...';
      });
    };
    // Предупреждение при попытке уйти до оплаты (опционально)
    // window.onbeforeunload = function() {
    //   return "Вы уверены, что хотите покинуть страницу? Оплата не будет завершена.";
    // };
  </script>
</head>
<body>
  <div class="recharge-confirm-wrap">
    <div class="logo"><img src="/img/logo-pokemon.svg" alt="Логотип"></div>
    <h1>Пополнение баланса</h1>
    <div class="desc">
      Вы собираетесь пополнить баланс на сумму <b><?= $amount ?> ₽</b>
    </div>
    <form action="<?= htmlspecialchars($payUrl) ?>" method="get">
      <button type="submit" class="btn-confirm">Оплатить</button>
    </form>
    <div class="order-id">ID заказа: <?= $order_id ?></div>
    <a href="/" class="cancel-link">Отмена</a>
  </div>
</body>
</html>
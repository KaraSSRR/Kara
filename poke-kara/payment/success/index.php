<?php
// public/payment/success/index.php

// 4K арт Генгар и аметист (свободная иллюстрация)
$gengar_img = "https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Fwallpapers-clan.com%2Fwp-content%2Fuploads%2F2024%2F04%2Fpokemon-gengar-aesthetic-desktop-wallpaper-preview.jpg&f=1&nofb=1&ipt=a2b30805448d126bafca084c092f075ec2490c27f4835f19461f5f75e3c0cc6a"; // Можешь заменить на другой арт, если найдёшь лучше!
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Платёж успешно завершён!</title>
    <meta name="viewport" content="width=450">
    <style>
        body {
            background: linear-gradient(135deg, #7c3aed 0%, #1b003a 100%);
            font-family: 'Nunito', Arial, sans-serif;
            color: #fff;
            text-align: center;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .container {
            background: rgba(30,22,64,0.85);
            border-radius: 24px;
            display: inline-block;
            padding: 42px 32px 32px 32px;
            margin: 32px auto;
            box-shadow: 0 8px 40px #56008860, 0 1.5px 7px #b3b2c648;
        }
        .success-title {
            font-size: 2.3rem;
            font-weight: 900;
            margin-bottom: 20px;
            color: #9e7cff;
            letter-spacing: 2px;
            text-shadow: 0 2px 16px #a45eea88;
        }
        .success-message {
            font-size: 1.09rem;
            margin-bottom: 26px;
            color: #eae2ff;
        }
        .poke-img {
            max-width: 420px;
            width: 100%;
            border-radius: 18px;
            box-shadow: 0 4px 24px #7c3aed77, 0 1.5px 7px #1b003a28;
            margin-bottom: 32px;
            margin-top: 4px;
            border: 3px solid #a57eea;
        }
        .contact-support {
            margin-top: 16px;
            color: #ad7bee;
        }
        @media (max-width: 600px) {
            .container { padding: 24px 8px 18px 8px; }
            .poke-img { max-width: 98vw; }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="<?=$gengar_img?>" alt="" class="poke-img">
        <div class="success-title">Платёж прошёл успешно!</div>
        <div class="success-message">
            Если аметисты не начислены — напишите в поддержку.<br>
            <span style="font-size:1.4em; color:#ff89d5;">&#10024;Спасибо за покупку &#10024;</span>
        </div>
        <div class="contact-support">
            <b>Поддержка:</b> <a href="mailto:t.me/ushakov_t" style="color:#b8a4f8;text-decoration:underline;">Kara</a>
        </div>
    </div>
</body>
</html>
<head>
	<title>Poke Kara - Мир</title>
	<meta name="theme-color" content="#9d4edd">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable = no">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="/css/world.css">
    <link rel="stylesheet" type="text/css" href="/css/makasimka.css">
    <link rel="stylesheet" type="text/css" href="/css/emoji.css">
	<link href="/fontawesome/css/all.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="/css/tipped/tipped.css">
  <link rel="stylesheet" type="text/css" href="/css/adaptive.css">
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
</head>

<srcipt src="/js/world.js"></srcipt>
<style>
.opensticker{
    display: none;
}
img.sti{
    width: 200px;
    animation-iteration-count:   infinite;
    
  animation-name: tada;
  animation-duration: 1.5s;
  animation-fill-mode: both;
  transition: 3s;
}
.opensticker{
    position: absolute;
    z-index: 1000;
    background-color: #000000c2;
    width: 100%;
    height: 100vh;
    text-align: center;
    top: 0;
    left: 0;
    overflow: auto;
    padding: 150px;
    box-sizing: border-box;
    
      transition: 2s;
}
.stick{
    position: relative;
}
.open{
    padding: 10px;
    background: #5091d9;
    font-size: 29px;
    border-radius: 5px;
    color: #ffffff;
    cursor: pointer;
    font-family: 'nunito';
    letter-spacing: 2px;
    width: 250px;
    margin: 25px auto;
}
@keyframes tada {
  0% {
  -webkit-transform: scale3d(1, 1, 1);
  transform: scale3d(1, 1, 1);
  }
  10%, 20% {
  -webkit-transform: scale3d(.9, .9, .9) rotate3d(0, 0, 1, -3deg);
  transform: scale3d(.9, .9, .9) rotate3d(0, 0, 1, -3deg);
  }
  30%, 50%, 70%, 90% {
  -webkit-transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
  transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
  }
  40%, 60%, 80% {
  -webkit-transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
  transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
  }
  100% {
  -webkit-transform: scale3d(1, 1, 1);
  transform: scale3d(1, 1, 1);
  }
  } 
  .prez{
      display: none;
      width: 175px;
    height: 133px;
    background: #99bfe9;
    border-radius: 34px;
    box-sizing: border-box;
    margin: auto;
    position: absolute;
    top: 14px;
    left: 313px;
  }
  .progressbar{
  width: 100px;
  height: 300px;
  overflow-y: auto;
  overflow-x: hidden;
  transform:rotate(-90deg) translateY(-100px);
  transform-origin: right top;
  }
  .progress{
      width: 15px;
      height: 1500px;
      background: #000;
      transform: rotate(90deg);
  transform-origin: right top;
  }
  
  .blockCode{
      margin: 30px;
    height: 575px;
    width: 700px;
    background: url(https://legends.pokemon.com/assets/header/bg-menu-mobile.jpg);
    color: #fff;
    font-family: nunito;
    background-size: contain;
    position: relative;
  }
  .prev{
      position: absolute;
    top: 20px;
    left: 20px;
    font-size: 15px;
    text-transform: uppercase;
    width: 280px;
    text-align: center;
  }
  .prev > span{
      display: block;
    background: #e74c3c;
    padding: 5px 25px;
    border-radius: 19px;
  }
  .logo{
      position: absolute;
    top: 10px;
    right: 10px;
  }
  .logo > img{
      width: 100px;
  }
  .content{
      margin-top: 150px;
    position: absolute;
    width: 100%;
    text-align: center;
  }
  .code{
     font-size: 40px;
    background: url(https://legends.pokemon.com/assets/gameplay/preorder-button-bg.png),#35639d;
    width: 408px;
    padding: 10px;
    margin: auto;
    border-radius: 41px;
    letter-spacing: 2px;
    font-weight: bold;
  }
  .prezents{
          width: 80%;
    margin: auto;
    height: 120px;
  }
  .icon{
      float: left;
  }
  .icon > img{
      width: 100px;
  }
  .cont{
      float: right;
  }
  .cont > div{
      display: inline-block;
      margin: 0 27px;
  }
  .cont > div > span{
          display: block;
    margin-top: 5px;
  }
  .howuse{
      
    float: left;
    width: 377px;
    margin: 10px;
    height: 152px;
    background: #213244;
    border-radius: 20px;

  }
  .howuse > .pre{
      
    width: 142px;
    margin: -15px auto 0 auto;
    padding: 10px;
    background: #f1c40f;
    text-transform: uppercase;
    font-weight: bold;
    color: black;
    border-radius: 15px;

  }
  p{
      
    font-size: 17px;
    line-height: 24px;

  }
  .example{
      
    width: 81%;
    margin-top: 10px;
    background: #fff;
    padding: 5px 15px;
    margin-left: 15px;
    border-radius: 20px;
    color: #263b4e;
    text-align: left;

  }
  .start{
      
    float: right;
    width: 283px;
    margin: 10px;
    height: 152px;
    background: #213244;
    border-radius: 20px;
    box-sizing: border-box;
    padding: 10px;

  }
  .start > .pre{
      
    width: 142px;
    margin: -24px auto 0 auto;
    padding: 10px;
    background: #f1c40f;
    text-transform: uppercase;
    font-weight: bold;
    color: black;
    border-radius: 15px;

  }
</style>
<body>
    <div class="blockCode">
        <div class="prev">
            новый промо-код<br><br>
            <span>действует до 15 января 2023</span>
        </div>
        <div class="logo">
            <img src="/img/main/x.png">
        </div>
        <div class="content">
            <div class="code">new_year2023</div>
            <h2>Содержание:</h2>
            <div class="prezents">
                <div class="icon">
                    <img src="https://icon-library.com/images/gift-box-icon-png/gift-box-icon-png-7.jpg">
                </div>
                <div class="cont">
                    <div>
                        <img src="/img/world/items/little/151.png"><span>Яйцо покемона</span>
                    </div>
                    <div>
                        <img src="/img/world/items/little/197.png"><span>x15</span>
                    </div>
                    <div>
                        <img src="/img/world/items/little/1.png"><span>x250.000</span>
                    </div>
                </div>
            </div>
            <div class="sear">
                <div class="howuse">
                    <div class="pre">Как использовать</div>
                    <p>Введите в чате игры <font color="red">%code</font> и через пробел введите промо-код.</p>
                    <span>пример:</span>
                    <div class="example">%code new_year2023</div>
                </div>
                <div class="start">
                    <div class="pre">Постоянный</div>
                    <p>Введите в чате игры <font color="red">%code poke-route2021</font> и получите <b>Монета х25.000</b> и <b>Зеленая конфета х15</b>.</p>
                </div>
            </div>
        </div>
    </div>
    <!--<div class="DivNotification"></div>-->
    <!--<button onclick="openst();">Открыть стикер</button>-->
    
    <!--<div class="opensticker">-->
    <!--    <div class="stick">-->
    <!--        <img src="/img/sticker.png" class="sti">-->
    <!--        <div class="prez">-->
    <!--            приз-->
    <!--        </div>-->
    <!--        <div class="open" onclick="cadsop();">Открыть</div>-->
    <!--    </div>-->
    <!--</div>-->
    
    <!--<div class="progressbar">-->
    <!--    <div class="progress"></div>-->
    <!--</div>-->
    <script src="/js/jquery/jquery.js?<?=$versionGame;?>" type="text/javascript"></script>
  <script type="text/JavaScript" src="/js/device.js?<?=$versionGame;?>"></script>
	<script src="/js/jquery/draggabilly.js?<?=$versionGame;?>" type="text/javascript"></script>
	<script src="/js/emoji.js?<?=$versionGame;?>" type="text/javascript"></script>
	<script src="/js/world.js?<?=microtime(true);?>" type="text/javascript"></script>
	<script src="/js/makasimka.js?<?=microtime(true);?>" type="text/javascript"></script>

	<script src="/js/tipped/tipped.js?<?=$versionGame;?>" type="text/javascript"></script>
	
</body>
<script>
    function openst(){
        // $('.opensticker').show();
        Game.notifications.main("Системная ошибка!", "error");
    }
    function cadsop(){
        $('.sti').css("animation-name","none");
        $('.sti').css("filter","brightness(6)");
        setTimeout(function() {
            $('.prez').show();
            setTimeout(function() {
                $('.opensticker').hide();
                $('.prez').hide();
                $('.sti').css("animation-name","tada");
                $('.sti').css("filter","brightness(1)");
                Game.notifications.main("Системная ошибка!", "error");
            }, 2000);
        }, 2000);
        
    }
</script>
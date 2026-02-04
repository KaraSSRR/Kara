<html><head>
    <meta charset="utf-8">
    <title>jQuery UI</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/themes/sunny/jquery-ui.css">
    
<link href="/fontawesome/css/all.css" rel="stylesheet">
    <style type="text/css">
        body{
            width: 300px;
            height: 300px;
        }
        .draggable, #droppable {font-size: large; border: thin solid black; padding: 10px;
            width: 100px; text-align: center; background-color: lightgray; margin: 4px;}
        #droppable {padding: 20px; position: absolute; right: 5px;}
        
        #droppable2 {padding: 20px; position: absolute; right: 5px; bottom: 0px;}
        
        .divChest{
            position: absolute;
    background: url(/img/testing/DrakonoZakaz.png) no-repeat;
    background-size: 100%;
    width: 906px;
    height: 460px;
    z-index: 3;
    border: 0 solid green;
        }
        .divChest > .Pokemon{
            position: absolute;
            width: 123px;
            height: 123px;
            text-align: center;
        }
        .divChest > .PokLeftTop{
            top: 87px;
            left: 170px;
        }
        .divChest > .PokLeftBottom{
            top: 234px;
            left: 170px;
        }
        .divChest > .PokRightTop{
            top: 87px;
            left: 613px;
        }
        .divChest > .PokRightBottom{
            top: 234px;
            left: 613px;
        }
        .divChest > .Pokemon > .Wish{
            background: #c1c1b5;
    display: inline-block;
    padding: 0px 5px;
    border-radius: 10px;
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: fit-content;
    z-index: 3;
        }
        .divChest > .Pokemon > .Wish > img{
            width: 30px;
        }
        .divChest > .Pokemon > .Icon{
            max-width: 123px;
    max-height: 123px;
    position: absolute;
    z-index: 2;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
        }
        .divChest > .Pokemon > .Ret{
            width: 30px;
    height: 30px;
    background: #72a07d;
    border-radius: 10px;
    position: absolute;
    color: #fff;
    line-height: 30px;
    text-align: center;
    padding: 5px;
    font-size: 19px;
    box-sizing: border-box;
    cursor: pointer;
    bottom: 0;
    right: 0;
        }
        .divChest > .LeftPipe{
            position: absolute;
    top: 151px;
    left: 345px;
    width: 58px;
    height: 213px;
        }
        .divChest > .CenterPipe{
            position: absolute;
    top: 151px;
    left: 424px;
    width: 58px;
    height: 213px;
        }
        .divChest > .RightPipe{
            position: absolute;
    top: 151px;
    left: 500px;
    width: 58px;
    height: 213px;
        }
        .divChest > .Pipe > div{
            position: absolute;
    bottom: 20px;
        }
        .divChest > .Pipe > div > img{
            width: 58px;
    display: block;
    margin: 5px 0;
    opacity: 0.7;
        }
        .divChest > .Pipe > div > img:last-child{
            opacity:1;
        }
        
    </style>
    <script type="text/javascript">
$(function() {
	
    $('.Pipe div img:last-child').draggable({
        containment: '.divChest',
        helper: "clone"
    });
    
    $('.Egg').droppable({
        drop: function(event, ui) {
            ui.draggable.detach();
            $(this).removeClass('Egg');
            $(this).removeClass('ui-droppable');
            $('.PokLeftTop .Wish .Egg').detach();
        },
        accept: '.Egg'
    });
    
    
	
});
    </script> 
</head>
<body style="">
    <!--<div id="droppable" class="ui-droppable">-->
    <!--    Оставь здесь-->
    <!--</div> -->
    <!--<div id="droppable2" class="ui-droppable">-->
    <!--    Оставь здесь-->
    <!--</div> -->
    <!--<div id="drag1" class="draggable ui-widget ui-corner-all ui-state-error ui-draggable" style="position: relative;">-->
    <!--    Элемент 1-->
    <!--</div>-->
    <!--<div id="drag2" class="draggable ui-widget ui-corner-all ui-state-error ui-draggable" style="position: relative;">-->
    <!--    Элемент 2-->
    <!--</div> -->
    
    <div class="divChest" style="left: 200px; top: 32px;">
        <div class="PokLeftTop Pokemon Egg Candy">
            <div class="PokLeftTop_Wish Wish">
                <img src="/img/testing/Egg.png" class="Egg">
                <img src="/img/testing/Candy.png" class="Candy">
            </div>
            <div class="PokLeftTop_Icon Icon">
                <img src="/img/pokemons/sprite/normal/001.gif">
            </div>
            <div class="PokLeftTop_Ret Ret">
                <i class="fas fa-undo-alt"></i>
            </div>
        </div>
        <div class="PokLeftBottom Pokemon">
            <div class="PokLeftBottom_Wish Wish">
                <img src="/img/testing/Egg.png">
            </div>
            <div class="PokLeftBottom_Icon Icon">
                <img src="/img/pokemons/sprite/normal/004.gif">
            </div>
            <div class="PokLeftBottom_Ret Ret">
                <i class="fas fa-undo-alt"></i>
            </div>
        </div>
        <div class="PokRightTop Pokemon Candy">
            <div class="PokRigthTop_wish Wish">
                <img src="/img/testing/Candy.png" class="Candy">
            </div>
            <div class="PokRightTop_Icon Icon">
                <img src="/img/pokemons/sprite/normal/007.gif">
            </div>
            <div class="PokRighthTop_Ret Ret">
                <i class="fas fa-undo-alt"></i>
            </div>
        </div>
        <div class="PokRightBottom Pokemon Candy Candy Candy">
            <div class="PokRigthBottom_Wish Wish">
                <img src="/img/testing/Candy.png">
                <img src="/img/testing/Candy.png">
                <img src="/img/testing/Candy.png">
            </div>
            <div class="PokRightBottom_Icon Icon">
                <img src="/img/pokemons/sprite/normal/025.gif">
            </div>
            <div class="PokRightBottom_Ret Ret">
                <i class="fas fa-undo-alt"></i>
            </div>
        </div>
        <div class="LeftPipe Pipe">
            <div>
                <img src="/img/testing/Egg.png" class="Egg">
                <img src="/img/testing/Egg.png" class="Egg">
                <img src="/img/testing/Egg.png" class="Egg">
            </div>
        </div>
        <div class="CenterPipe Pipe">
            <div>
                <img src="/img/testing/Egg.png"  class="Egg">
                <img src="/img/testing/Egg.png"  class="Egg">
            </div>
        </div>
        <div class="RightPipe Pipe">
            <div>
                <img src="/img/testing/Candy.png"  class="Candy">
            </div>
        </div>
    </div>
</body></html>

<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}
// $it = $mysqli->query('SELECT * FROM `base_atk` ORDER BY `id` ASC');
// while($its = $it->fetch_assoc()){
//     echo 'id'.$its['id'].' '.$its['name_rus'].' ('.$its['name'].') - '.$its['type'].'<br>';
// }





?>
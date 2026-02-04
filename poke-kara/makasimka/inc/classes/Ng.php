<?php

/*Class Ng {

  private $response = [];
  private $userInfo = [];

  public function __construct($type, $val = [], array &$userInfo = [], array &$response = []) {

    if($type) {

      $this->response =& $response;

      switch($_POST['type']){

        case 'mandarin':
          if($userInfo['mandarin'] != 0 && $userInfo['mandarin'] < time()) {
            $rand = mt_rand(7200,9000);
            $rand2 = mt_rand(1,150);
            if($rand2 >= 1 && $rand2 <= 4) {
              $randEgg = [369,285,556];
              shuffle($randEgg);
              plusEgg('28,28,28,28,28,28',false,false,false,false,$randEgg[0],true);
              $plus = Items::arrayItem([54,[1,1]]);
            }else{
              $randItem = Array([160,1],[115,1],[174,1],[185,1],[186,1],[187,1],[33,2]);
              shuffle($randItem);
              itemAdd($randItem[0][0],$randItem[0][1]);
              $plus = Items::arrayItem([$randItem[0][0],[$randItem[0][1],1]]);
            }
            Work::$sql->query("UPDATE users SET mandarin = ".(time() + $rand)." WHERE id = ".$userInfo['id']);
            $this->response['response'] = array(
              'plus' => $plus
            );
          }
        break;*/

//
//         case 'open':
//           $Ng = Work::$sql->query('SELECT ng FROM users WHERE id = '.$userInfo['id'])->fetch_assoc();
//           $Bonus = json_decode($Ng['ng']);
//           $html = '
//     <div class="Duh">Новогодний дух Канто<span>'.($Bonus->elka + $Bonus->mandarin + $Bonus->ukrashenie + $Bonus->igrushki + $Bonus->frost).'</span></div>
// <div class="Text"><b>Новогодний дух Канто</b> - это то, сколько вклада вы внесли в создание Новогоднего настроения и атмосферы в регионе Канто. В конце Новогоднего мероприятия, игроки получат награды в зависимости от их Новогоднего духа. Чем больше духа, тем ценнее награды. Новогодний дух можно получить различными способами:</div>
// <div class="DuhCat">
//     <div>
//         <i class="fa fa-tree-christmas"></i><div>Новогодняя Ёлка</div>
// <span>Собирайте ёлку в Паллете и получайте <b>1 нов. дух</b> за каждую Еловую ветку.<br>Заработано духа: <b>'.$Bonus->elka.'</b></span>
//     </div><div>
//         <i class="fa fa-apple-crate"></i><div>Мандаринки</div>
// <span>Каждая съеденная вашим покемоном мандаринка дает <b>1 нов. дух</b>.<br>Заработано духа: <b>'.$Bonus->mandarin.'</b></span>
//     </div><div>
//         <i class="fa fa-snowman"></i><div>Frost покемоны</div>
// <span>Побеждайте Frost покемонов и получайте <b>1 нов. дух</b>.<br>Заработано духа: <b>'.$Bonus->frost.'</b></span>
//     </div><div>
//         <i class="fa fa-lights-holiday"></i><div>Украшайте Ёлку</div>
// <span>Украшайте Новогоднюю Ёлку в Паллете и получайте <b>нов. дух</b> в зависимости от редкости украшения.<br>Заработано духа: <b>'.$Bonus->ukrashenie.'</b></span>
//     </div>
// </div>
// <div class="Buttons"><a href="https://pokepower.forum2x2.ru/t8-topic" target="_blank" class="BtnMain" style="
//     background: #3f87b4;
// ">Подробнее о Новогоднем ивенте</a></div>
//           ';
//           $this->response['response'] = array(
//             'html' => $html
//           );
//         break;

/*      }

    }

  }*/

/*}*/

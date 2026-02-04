function Box($id){
		if($id == 242) {
			$_SESSION['other'] = 1;
			$rand = mt_rand(1,1000);
			if($rand >= 1 && $rand <= 550) {
				$rand1 = mt_rand(1,13);
				if($rand1 == 1) {
					$rand1_1 = mt_rand(100000,1000000);
					itemAdd(1,$rand1_1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/1.webp" class="item"> Монета x'.$rand1_1;
					$nagrada = [1,'item',$rand1_1];
				}elseif($rand1 == 2) {
					$rand1_2 = mt_rand(2,5);
					itemAdd(5,$rand1_2);
					$_SESSION['plus'] = '<img src="/img/world/items/little/5.webp" class="item"> Мастербол x'.$rand1_2;
					$nagrada = [5,'item',$rand1_2];
				}elseif($rand1 == 3) {
					$rand1_3 = mt_rand(5,10);
					itemAdd(149,$rand1_3);
					$_SESSION['plus'] = '<img src="/img/world/items/little/149.webp" class="item"> Металлическая роза x'.$rand1_3;
					$nagrada = [149,'item',$rand1_3];
				}elseif($rand1 == 4) {
					$rand1_4 = mt_rand(5,10);
					itemAdd(17,$rand1_4);
					$_SESSION['plus'] = '<img src="/img/world/items/little/17.webp" class="item"> Фиолетовая конфета x'.$rand1_4;
					$nagrada = [17,'item',$rand1_4];
				}elseif($rand1 == 5) {
					$rand1_5 = mt_rand(5,10);
					itemAdd(32,$rand1_5);
					$_SESSION['plus'] = '<img src="/img/world/items/little/32.webp" class="item"> Красная конфета x'.$rand1_5;
					$nagrada = [32,'item',$rand1_5];
				}elseif($rand1 == 6) {
					$rand1_6 = mt_rand(5,10);
					itemAdd(33,$rand1_6);
					$_SESSION['plus'] = '<img src="/img/world/items/little/33.webp" class="item"> Зеленая конфета x'.$rand1_6;
					$nagrada = [33,'item',$rand1_6];
				}elseif($rand1 == 7) {
					$rand1_7 = mt_rand(5,10);
					itemAdd(37,$rand1_7);
					$_SESSION['plus'] = '<img src="/img/world/items/little/37.webp" class="item"> Банка цинка x'.$rand1_7;
					$nagrada = [37,'item',$rand1_7];
				}elseif($rand1 == 8) {
					$rand1_8 = mt_rand(5,10);
					itemAdd(38,$rand1_8);
					$_SESSION['plus'] = '<img src="/img/world/items/little/38.webp" class="item"> Банка кальция x'.$rand1_8;
					$nagrada = [38,'item',$rand1_8];
				}elseif($rand1 == 9) {
					$rand1_9 = mt_rand(5,10);
					itemAdd(39,$rand1_9);
					$_SESSION['plus'] = '<img src="/img/world/items/little/39.webp" class="item"> Банка железа x'.$rand1_9;
					$nagrada = [39,'item',$rand1_9];
				}elseif($rand1 == 10) {
					$rand1_10 = mt_rand(5,10);
					itemAdd(40,$rand1_10);
					$_SESSION['plus'] = '<img src="/img/world/items/little/40.webp" class="item"> Банка йода x'.$rand1_10;
					$nagrada = [40,'item',$rand1_10];
				}elseif($rand1 == 11) {
					$rand1_11 = mt_rand(5,10);
					itemAdd(41,$rand1_11);
					$_SESSION['plus'] = '<img src="/img/world/items/little/41.webp" class="item"> Банка углеводов x'.$rand1_11;
					$nagrada = [41,'item',$rand1_11];
				}elseif($rand1 == 12) {
					itemAdd(127,2);
					$_SESSION['plus'] = '<img src="/img/world/items/little/127.webp" class="item"> Набор вкусностей x2';
					$nagrada = [127,'item',2];
				}else{
					$rand1_12 = mt_rand(5,10);
					itemAdd(42,$rand1_12);
					$_SESSION['plus'] = '<img src="/img/world/items/little/42.webp" class="item"> Банка цинка x'.$rand1_12;
					$nagrada = [42,'item',$rand1_12];
				}
			}elseif($rand >= 551 && $rand <= 809) {
				$rand2 = mt_rand(1,7);
				if($rand2 == 1) {
					itemAdd(12,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/12.webp" class="item"> Инкубатор x1';
					$nagrada = [12,'item',1];
				}else if($rand2 == 2) {
					itemAdd(103,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/103.webp" class="item"> Блестки x1';
					$nagrada = [103,'item',1];
				}else if($rand2 == 3) {
					itemAdd(104,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/104.webp" class="item"> Лупа x1';
					$nagrada = [104,'item',1];
				}else if($rand2 == 4) {
					itemAdd(105,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/105.webp" class="item"> Линзы x1';
					$nagrada = [105,'item',1];
				}else if($rand2 == 5) {
					itemAdd(127,3);
					$_SESSION['plus'] = '<img src="/img/world/items/little/127.webp" class="item"> Набор вкусностей x3';
					$nagrada = [127,'item',3];
				}else if($rand2 == 6) {
					itemAdd(129,2);
					$_SESSION['plus'] = '<img src="/img/world/items/little/129.webp" class="item"> Билет на экспресс рейс Канто - Калос x2';
					$nagrada = [129,'item',2];
				}else{
					itemAdd(106,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/106.webp" class="item"> Объедки x1';
					$nagrada = [106,'item',1];
				}
			}elseif($rand >= 810 && $rand <= 924) {
				$rand3 = mt_rand(1,15);
				if($rand3 == 1) {
					itemAdd(19,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/19.webp" class="item"> Сумрачный камень x1';
					$nagrada = [19,'item',1];
				}elseif($rand3 == 2) {
					itemAdd(20,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/20.webp" class="item"> Солнечный камень x1';
					$nagrada = [20,'item',1];
				}elseif($rand3 == 3) {
					itemAdd(21,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/21.webp" class="item"> Рассветный камень x1';
					$nagrada = [21,'item',1];
				}elseif($rand3 == 4) {
					itemAdd(22,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/22.webp" class="item"> Ткань жнеца x1';
					$nagrada = [22,'item',1];
				}elseif($rand3 == 5) {
					itemAdd(36,1);
					$_SESSION['plus'] = '<img src="/img/world/items/little/36.webp" class="item"> Сияющий камень x1';
					$nagrada = [36,'item',1];
				}elseif($rand3 == 6) {
					plusEgg(false,false,false,false,false,556,true);
					$_SESSION['plus'] = '<img src="/img/world/items/little/54.webp" class="item"> Яйцо x1';
					$nagrada = [1,'egg',556];
				}elseif($rand3 == 7) {
					plusEgg(false,false,false,false,false,293,true);
					$_SESSION['plus'] = '<img src="/img/world/items/little/54.webp" class="item"> Яйцо x1';
					$nagrada = [1,'egg',293];
				}elseif($rand3 == 8) {
					plusEgg(false,false,false,false,false,359,true);
					$_SESSION['plus'] = '<img src="/img/world/items/little/54.webp" class="item"> Яйцо x1';
					$nagrada = [1,'egg',359];
				}elseif($rand3 == 9) {
					plusEgg(false,false,false,false,false,120,true);
					$_SESSION['plus'] = '<img src="/img/world/items/little/54.webp" class="item"> Яйцо x1';
					$nagrada = [1,'egg',120];
				}elseif($rand3 == 10) {
					plusEgg(false,false,false,false,false,712,true);
					$_SESSION['plus'] = '<img src="/img/world/items/little/54.webp" class="item"> Яйцо x1';
					$nagrada = [1,'egg',712];
				}elseif($rand3 == 11) {
					plusEgg(false,false,false,false,false,621,true);
					$_SESSION['plus'] = '<img src="/img/world/items/little/54.webp" class="item"> Яйцо x1';
					$nagrada = [1,'egg',621];
                                        }
			$_SESSION['text'] = 'Вы открыли подарок.';
			$_SESSION['error'] = 0;
			minus_item(11,1);
			achievment_update(5,1);
		}else{
			$_SESSION['text'] = 'Ошибка!';
			$_SESSION['error'] = 1;
 		}
}

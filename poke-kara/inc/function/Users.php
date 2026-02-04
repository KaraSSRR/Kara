<?
#Функция определяет популярность игрока
function population($population){
   if($population >= 0 && $population < 400)$rang = "";
   if($population >= 401 && $population <= 1000)$rang = "Начинающий";
   if($population >= 1001 && $population <= 5000)$rang = "Неизвестный";
   if($population >= 5001 && $population <= 15000)$rang = "Известный";
   if($population >= 15001 && $population <= 30000)$rang = "Знаменитый";
   if($population >= 30001 && $population <= 80000)$rang = "Величайший";
   if($population >= 80001 && $population <= 150000)$rang = "Прославленный";
   if($population >= 150001 && $population <= 400000)$rang = "Выдающийся";
   if($population >= 400001 && $population <= 10000001)$rang = "Легендарный";
   if($population > 1000001){$rang = "Могущественный";}
   return $rang;
}
#Функция определяет репутацию игрока
function reputation($reputation, $battleCount){
  $countLose = $battleCount - round($reputation/2);
  $percentWin = ($battleCount - $countLose)*10;
  if($percentWin >= 0 && $percentWin < 10)$rang = "Ученик";
  if($percentWin >= 11 && $percentWin <= 20)$rang = "Тренер";
  if($percentWin >= 21 && $percentWin <= 35)$rang = "Мастер";
  if($percentWin >= 35 && $percentWin <= 49)$rang = "Профи";
  if($percentWin >= 50 && $percentWin <= 68)$rang = "Мудрец";
  if($percentWin >= 69 && $percentWin <= 79)$rang = "Отверженец";
  if($percentWin >= 80 && $percentWin <= 90)$rang = "Чемпион";
  if($percentWin >= 91 && $percentWin <= 100)$rang = "Гуру";
  return $rang;
}
?>
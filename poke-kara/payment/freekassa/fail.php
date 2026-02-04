<?php
// payment/freekassa/fail.php
header('Location: /payment/fail?order=' . urlencode($_GET['MERCHANT_ORDER_ID'] ?? ''));
exit;
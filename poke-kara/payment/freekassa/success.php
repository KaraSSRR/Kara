<?php
// payment/freekassa/success.php
header('Location: /payment/success?order=' . urlencode($_GET['MERCHANT_ORDER_ID'] ?? ''));
exit;
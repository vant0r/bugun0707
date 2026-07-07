<?php
// Register sahifasi login.php ichiga birlashtirildi
// Bu fayl faqat eski havolalar uchun redirect qiladi
$ref = isset($_GET['ref']) ? '&ref=' . urlencode($_GET['ref']) : '';
$tarif = isset($_GET['tarif']) ? '&tarif=' . urlencode($_GET['tarif']) : '';
header('Location: /login.php?mode=register' . $ref . $tarif);
exit;

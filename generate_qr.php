<?php
// How to use: <img src="generate_qr.php?data=123" />
$data = $_GET['data'] ?? 'empty';
$data = substr($data,0,255); // basic length guard
$size = $_GET['s'] ?? 200;
header('Content-Type: image/png');
// Use QRServer free API (no external lib needed).
readfile("https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=".urlencode($data));
exit;
?>
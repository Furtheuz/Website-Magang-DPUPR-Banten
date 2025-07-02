<?php
include "config/auth.php";
include "config/db.php";
checkLogin();
$id  = intval($_GET['id'] ?? 0);
$ps  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peserta WHERE id=$id"));
if(!$ps){die('Data tidak ditemukan');}
$roleColors = [
    'admin'      => ['primary' => '#dc2626'],
    'pembimbing' => ['primary' => '#059669'],
    'user'       => ['primary' => '#2563eb']
];
$role = $_SESSION['user']['role'] ?? 'user';
$primary = $roleColors[$role]['primary'];
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preview ID Card <?=$ps['nama']?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>.idcard{width:312px;height:494px;border:2px solid <?=$primary?>;border-radius:12px;overflow:hidden;margin:20px auto;} .header{background:<?=$primary?>;color:#fff;text-align:center;padding:12px;font-size:18px;font-weight:600;} .photo{width:100%;height:224px;object-fit:cover;} .info{padding:10px 12px;font-size:14px;line-height:1.35;} .info b{display:block;} .qr{position:absolute;right:10px;bottom:10px;width:72px;height:72px;}</style>
</head><body class="p-4">
<div class="idcard position-relative">
    <div class="header">ID CARD PESERTA</div>
    <img src="../foto/<?=$ps['foto']?:'default.png'?>" alt="foto" class="photo">
    <div class="info">
        <b><?=$ps['nama']?></b>
        Jurusan: <?=$ps['jurusan']?><br>
        Instansi: <?=$ps['instansi']?><br>
        <?=date('d/m/Y',strtotime($ps['mulai']))?> ➜ <?=date('d/m/Y',strtotime($ps['selesai']))?>
    </div>
    <img src="generate_qr.php?data=<?=$ps['id']?>" class="qr" alt="QR">
</div>
<div class="text-center"><a onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print</a></div>
</body></html>
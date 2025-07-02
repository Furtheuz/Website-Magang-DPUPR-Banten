<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

/* 🎨  Warna per‑role (biar konsisten sama halaman lain) */
$role = $_SESSION['user']['role'] ?? 'user';
$roleColors = [
    'admin'      => ['primary' => '#dc2626', 'secondary' => '#fef2f2'],
    'pembimbing' => ['primary' => '#059669', 'secondary' => '#f0fdf4'],
    'user'       => ['primary' => '#2563eb', 'secondary' => '#eff6ff']
];
$colors = $roleColors[$role] ?? $roleColors['user'];

/* 🔍  Ambil riwayat cetak + nama peserta + nama user */
$sql = "
    SELECT r.*, 
           p.nama AS peserta_nama,
           u.nama AS user_nama
    FROM riwayat_cetak_idcard r
    LEFT JOIN peserta p ON p.id = r.peserta_id
    LEFT JOIN users   u ON u.id = r.user_id
    ORDER BY r.created_at DESC
";
$log = mysqli_query($conn, $sql) or die('SQL error: '.mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Riwayat Cetak ID Card</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>body{background:<?= $colors['secondary'] ?>}</style>
</head>
<body class="p-4">
  <h1 class="h4 fw-bold mb-3"><i class="fa fa-clock"></i> Riwayat Cetak ID Card</h1>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Nama Peserta</th>
          <th>Dicetak Oleh</th>
          <th>Waktu</th>
          <th>Jumlah</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; while ($r = mysqli_fetch_assoc($log)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($r['peserta_nama']) ?></td>
          <td><?= htmlspecialchars($r['user_nama']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
          <td><?= $r['jumlah'] ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

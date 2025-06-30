<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$peserta = mysqli_query($conn, "SELECT p.*, u.nama as nama_user FROM peserta p JOIN users u ON p.user_id=u.id");
?>
<h2>ID Card Peserta</h2>
<table border="1">
<tr><th>Nama</th><th>Status</th><th>Aksi</th></tr>
<?php while($p = mysqli_fetch_assoc($peserta)): ?>
<tr>
  <td><?= $p['nama'] ?></td>
  <td><?= $p['status'] ?></td>
  <td><a href="cetak_idcard.php?id=<?= $p['id'] ?>" target="_blank">Cetak</a></td>
</tr>
<?php endwhile; ?>
</table>

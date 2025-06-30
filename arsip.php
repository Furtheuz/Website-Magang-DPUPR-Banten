<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

if (isset($_GET['arsipkan'])) {
  $peserta_id = $_GET['arsipkan'];
  mysqli_query($conn, "INSERT INTO arsip(peserta_id, keterangan, tanggal_arsip) VALUES($peserta_id, 'Selesai', NOW())");
  mysqli_query($conn, "UPDATE peserta SET status='selesai' WHERE id=$peserta_id");
}

$arsip = mysqli_query($conn, "SELECT a.*, p.nama FROM arsip a JOIN peserta p ON a.peserta_id=p.id");
$peserta = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif'");
?>
<h2>Arsip Magang</h2>
<form method="get">
  <select name="arsipkan">
    <option disabled selected>Pilih Peserta</option>
    <?php while($p = mysqli_fetch_assoc($peserta)) echo "<option value='{$p['id']}'>{$p['nama']}</option>"; ?>
  </select>
  <button>Arsipkan</button>
</form>
<hr>
<table border="1">
<tr><th>Nama</th><th>Keterangan</th><th>Tanggal</th></tr>
<?php while($a = mysqli_fetch_assoc($arsip)): ?>
<tr>
  <td><?= $a['nama'] ?></td>
  <td><?= $a['keterangan'] ?></td>
  <td><?= $a['tanggal_arsip'] ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

if ($_SESSION['user']['role'] == 'admin' && isset($_POST['tambah'])) {
  $peserta_id = $_POST['peserta_id'];
  $tanggal = $_POST['tanggal'];
  $tugas = $_POST['tugas'];
  $pembimbing_id = $_POST['pembimbing_id'];
  mysqli_query($conn, "INSERT INTO jadwal(peserta_id, tanggal, tugas, pembimbing_id) VALUES('$peserta_id','$tanggal','$tugas','$pembimbing_id')");
}

$jadwal = mysqli_query($conn, "SELECT j.*, p.nama as peserta, u.nama as pembimbing FROM jadwal j JOIN peserta p ON j.peserta_id=p.id JOIN users u ON j.pembimbing_id=u.id");
$peserta = mysqli_query($conn, "SELECT * FROM peserta");
$pembimbing = mysqli_query($conn, "SELECT * FROM users WHERE role='pembimbing'");
?>
<h2>Jadwal Magang</h2>
<?php if ($_SESSION['user']['role'] == 'admin'): ?>
<form method="post">
  <select name="peserta_id">
    <?php while($p = mysqli_fetch_assoc($peserta)) echo "<option value='{$p['id']}'>{$p['nama']}</option>"; ?>
  </select>
  <input type="date" name="tanggal" required>
  <input name="tugas" placeholder="Tugas">
  <select name="pembimbing_id">
    <?php while($u = mysqli_fetch_assoc($pembimbing)) echo "<option value='{$u['id']}'>{$u['nama']}</option>"; ?>
  </select>
  <button name="tambah">Tambah</button>
</form>
<?php endif; ?>
<hr>
<table border="1">
<tr><th>Peserta</th><th>Tanggal</th><th>Tugas</th><th>Pembimbing</th></tr>
<?php mysqli_data_seek($jadwal, 0); while($j = mysqli_fetch_assoc($jadwal)): ?>
<tr>
  <td><?= $j['peserta'] ?></td>
  <td><?= $j['tanggal'] ?></td>
  <td><?= $j['tugas'] ?></td>
  <td><?= $j['pembimbing'] ?></td>
</tr>
<?php endwhile; ?>
</table>
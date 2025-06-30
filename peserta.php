<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// Tambah peserta
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $institusi_id = $_POST['institusi_id'];
    $user_id = $_POST['user_id'];
    mysqli_query($conn, "INSERT INTO peserta(nama, institusi_id, user_id) VALUES('$nama','$institusi_id','$user_id')");
}

// Hapus peserta
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM peserta WHERE id=$id");
}

$peserta = mysqli_query($conn, "SELECT p.*, i.nama as institusi FROM peserta p JOIN institusi i ON p.institusi_id = i.id");
$institusi = mysqli_query($conn, "SELECT * FROM institusi");
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user'");
?>
<h2>Data Peserta</h2>
<form method="post">
  <input name="nama" placeholder="Nama" required>
  <select name="institusi_id" required>
    <option disabled selected>Pilih Institusi</option>
    <?php while($i = mysqli_fetch_assoc($institusi)) echo "<option value='{$i['id']}'>{$i['nama']}</option>"; ?>
  </select>
  <select name="user_id" required>
    <option disabled selected>Pilih User</option>
    <?php while($u = mysqli_fetch_assoc($users)) echo "<option value='{$u['id']}'>{$u['nama']}</option>"; ?>
  </select>
  <button name="tambah">Tambah</button>
</form>
<hr>
<table border="1">
<tr><th>Nama</th><th>Institusi</th><th>Aksi</th></tr>
<?php while($p = mysqli_fetch_assoc($peserta)): ?>
<tr>
  <td><?= $p['nama'] ?></td>
  <td><?= $p['institusi'] ?></td>
  <td><a href="?hapus=<?= $p['id'] ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
</tr>
<?php endwhile; ?>
</table>
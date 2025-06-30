<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    mysqli_query($conn, "INSERT INTO institusi(nama, alamat) VALUES('$nama','$alamat')");
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM institusi WHERE id=$id");
}

$data = mysqli_query($conn, "SELECT * FROM institusi");
?>
<h2>Data Institusi</h2>
<form method="post">
  <input name="nama" placeholder="Nama" required>
  <input name="alamat" placeholder="Alamat">
  <button name="tambah">Tambah</button>
</form>
<hr>
<table border="1">
<tr><th>Nama</th><th>Alamat</th><th>Aksi</th></tr>
<?php while($i = mysqli_fetch_assoc($data)): ?>
<tr>
  <td><?= $i['nama'] ?></td>
  <td><?= $i['alamat'] ?></td>
  <td><a href="?hapus=<?= $i['id'] ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
</tr>
<?php endwhile; ?>
</table>

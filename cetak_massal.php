
<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// SETTINGS
$ids   = isset($_POST['ids']) ? $_POST['ids'] : [];  // array dari form
if (isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);               // dari querystring
}
$ids = array_filter($ids, 'intval');                 // sanitize → int only

// Query peserta yang dipilih
if ($ids) {
    $in  = implode(',', array_map('intval', $ids));
    $sql = mysqli_query($conn, "
    SELECT p.*, i.nama AS instansi
    FROM peserta p
    LEFT JOIN institusi i ON i.id = p.institusi_id
    WHERE p.id IN ($in)
");

} else {
    // Jika belum ada pilihan, tampilkan list centang
    $sql = false;
}

// ====== ROLE‑BASED COLOR PALETTE (copy from your idcard.php) ======
$role = $_SESSION['user']['role'] ?? 'user';
$roleColors = [
    'admin'      => ['primary' => '#dc2626', 'secondary' => '#fef2f2', 'accent' => '#b91c1c'],
    'pembimbing' => ['primary' => '#059669', 'secondary' => '#f0fdf4', 'accent' => '#047857'],
    'user'       => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];
$colors = $roleColors[$role] ?? $roleColors['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Massal ID Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body{background: <?=$colors['secondary']?>; color:#111; font-family: 'Segoe UI',sans-serif;}
        .idcard{width: 312px; height: 494px; position: relative; border:2px solid <?=$colors['primary']?>; border-radius:12px; overflow:hidden; margin:8px auto; page-break-inside:avoid;}
        .idcard .header{background: <?=$colors['primary']?>; color:#fff; text-align:center; padding:12px 4px; font-size:18px; font-weight:600;}
        .idcard img.photo{width:100%; height:224px; object-fit:cover;}
        .idcard .info{padding:10px 12px; font-size:14px; line-height:1.35;}
        .idcard .info b{display:block;}
        .idcard .qr{position:absolute; right:10px; bottom:10px; width:72px; height:72px;}
    </style>
</head>
<body class="p-4">
<h1 class="h4 fw-bold mb-3"><i class="fa fa-id-card"></i> Cetak Massal ID Card</h1>
<?php if(!$ids): ?>
    <!-- STEP 1 : PILIH PESERTA DULU -->
    <form method="post" class="card shadow-sm p-3">
        <div class="table-responsive" style="max-height:65vh;overflow-y:auto;">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light sticky-top">
                <tr>
                    <th><input type="checkbox" id="checkAll"></th>
                    <th>Nama</th>
                    <th>Jurusan</th>
                    <th>Instansi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $q = mysqli_query($conn, "SELECT id,nama,institusi_id,tahun,bulan FROM peserta ORDER BY nama");
            while($p=mysqli_fetch_assoc($q)):
            ?>
                <tr>
                    <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?=$p['id']?>"></td>
                    <td><?=$p['nama']?></td>
                    <td><?=$p['institusi_id']?></td>
                    <td><?=$p['tahun']?></td>
                    <td><?=$p['bulan']?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn btn-primary mt-2"><i class="fa fa-print"></i> Cetak Terpilih</button>
    </form>
    <script>
        document.getElementById('checkAll').addEventListener('change',e=>{
            document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb=>cb.checked=e.target.checked);
        });
    </script>
<?php else: ?>
    <!-- STEP 2 : TAMPILKAN KARTU-KARTU -->
    <div class="d-flex flex-wrap justify-content-center gap-3">
    <?php while($row=mysqli_fetch_assoc($sql)): ?>
        <div class="idcard">
            <div class="header">ID CARD PESERTA</div>
            <img src="foto/<?=$row['foto']?:'default.png'?>" class="photo" alt="Foto <?=$row['nama']?>">
            <div class="info">
    <b><?=$row['nama']?></b>
    Jurusan: <?=$row['jurusan']?><br>
    Instansi: <?=$row['instansi']?><br>
   Lama Magang: <?=$row['tanggal_masuk']?> ➜ <?=$row['tanggal_keluar']?>

</div>

            <img src="generate_qr.php?data=<?=$row['id']?>" class="qr" alt="QR <?=$row['nama']?>">
        </div>
    <?php endwhile; ?>
    </div>
    <script>window.print();</script>
<?php endif; ?>
</body>
</html>
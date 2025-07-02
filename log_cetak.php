<?php
include "config/auth.php";
include "config/db.php";
checkLogin();
$qlog = mysqli_query($conn, "SELECT * FROM log_cetak ORDER BY waktu DESC LIMIT 500");
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Log Cetak</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body class="p-4">
<h1 class="h4 fw-bold mb-3"><i class="fa fa-list"></i> Log Aktivitas Cetak</h1>
<div class="table-responsive">
<table class="table table-bordered table-striped align-middle"><thead class="table-light"><tr><th>#</th><th>User</th><th>Aktivitas</th><th>Waktu</th><th>IP</th></tr></thead><tbody>
<?php $i=1;while($l=mysqli_fetch_assoc($qlog)):?>
<tr><td><?=$i++?></td><td><?=$l['user']?></td><td><?=$l['aksi']?></td><td><?=date('d/m/Y H:i',strtotime($l['waktu']))?></td><td><?=$l['ip']?></td></tr>
<?php endwhile;?></tbody></table></div></body></html>
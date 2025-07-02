<?php
include "config/auth.php";
include "config/db.php";
checkLogin();
// Ambil template aktif
$tpl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM template_idcard LIMIT 1"));
if(!$tpl){$tpl=['primary'=>'#2563eb','logo'=>'logo.png'];}
// Simpan perubahan
if(isset($_POST['save'])){
    $primary = mysqli_real_escape_string($conn,$_POST['primary']);
    $logo    = $_FILES['logo']['name']?:$tpl['logo'];
    if(isset($_FILES['logo']['tmp_name']) && $_FILES['logo']['tmp_name']){
        move_uploaded_file($_FILES['logo']['tmp_name'], 'logo/'.$logo);
    }
    if($tpl){
        mysqli_query($conn, "UPDATE template_idcard SET primary='$primary', logo='$logo'");
    }else{
        mysqli_query($conn, "INSERT INTO template_idcard(primary,logo) VALUES('$primary','$logo')");
    }
    header('Location: template_idcard.php?success=1');exit;
}
$role = $_SESSION['user']['role']??'user';
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kelola Template ID Card</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body class="p-4">
<h1 class="h4 fw-bold mb-3"><i class="fa fa-palette"></i> Template ID Card</h1>
<?php if(isset($_GET['success'])): ?><div class="alert alert-success">Perubahan tersimpan!</div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card p-3 shadow-sm" style="max-width:420px;">
    <div class="mb-3">
        <label class="form-label">Warna Primer</label>
        <input type="color" name="primary" value="<?=$tpl['primary']?>" class="form-control form-control-color">
    </div>
    <div class="mb-3">
        <label class="form-label">Logo (PNG, max 500kb)</label>
        <input class="form-control" type="file" name="logo" accept="image/png">
        <?php if($tpl['logo']): ?>
            <img src="logo/<?=$tpl['logo']?>" alt="logo" class="mt-2" style="max-height:80px;">
        <?php endif; ?>
    </div>
    <button class="btn btn-primary" name="save"><i class="fa fa-save"></i> Simpan</button>
</form>
</body></html>
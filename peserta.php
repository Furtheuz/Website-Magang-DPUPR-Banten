<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

// Initialize variables
$message = '';
$messageType = '';

$role = $_SESSION['user']['role'];
$userName = $_SESSION['user']['name'] ?? 'User';

// Role-based styling
$roleColors = [
    'admin' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'pembimbing' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'user' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];

$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => '👨‍🎓'
];

$currentTheme = ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'];

// Available bidang options
$bidang_options = [
    'Sekretariat',
    'Bina Marga',
    'Tata Ruang',
    'Umum',
    'Jasa Konstruksi'
];

// Function to upload photo
function uploadFoto($file, $peserta_id) {
    $target_dir = "Uploads/peserta/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = "peserta_" . $peserta_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar yang valid'];
    }
    
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif") {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diizinkan'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Automatic archiving for completed internships
$today = date('Y-m-d');
$completed_peserta = mysqli_query($conn, "SELECT id FROM peserta WHERE tanggal_keluar < '$today' AND status = 'aktif'");
while ($row = mysqli_fetch_assoc($completed_peserta)) {
    $peserta_id = $row['id'];
    $keterangan = 'Selesai';
    $insertArsip = mysqli_query($conn, "INSERT INTO arsip(peserta_id, keterangan, tanggal_arsip) VALUES($peserta_id, '$keterangan', NOW())");
    $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='selesai' WHERE id=$peserta_id");
    if ($insertArsip && $updatePeserta) {
        $message = 'Peserta dengan ID ' . $peserta_id . ' telah diarsipkan otomatis!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengarsipkan peserta otomatis: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Handle new user registration
if (isset($_POST['register'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $universitas = mysqli_real_escape_string($conn, $_POST['universitas']);
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];

    $query = "INSERT INTO register (nama, email, password, nim, universitas, jurusan, no_hp, alamat, tanggal_masuk, tanggal_keluar, status, created_at, updated_at) 
              VALUES ('$nama', '$email', '$password', '$nim', '$universitas', '$jurusan', '$no_hp', '$alamat', '$tanggal_masuk', '$tanggal_keluar', 'pending', NOW(), NOW())";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Registrasi berhasil, menunggu verifikasi admin!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mendaftar: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Admin verification of new registrations with bidang selection
if (isset($_POST['verify_registration']) && $role == 'admin') {
    $register_id = (int)$_POST['register_id'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);
    $register = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM register WHERE id = $register_id AND status = 'pending'"));
    
    if ($register) {
        $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, user_id, alamat, tanggal_masuk, tanggal_keluar, status_verifikasi, foto, bidang) 
                  VALUES ('$register[nama]', '$register[email]', '$register[no_hp]', NULL, NULL, '$register[alamat]', '$register[tanggal_masuk]', '$register[tanggal_keluar]', 'verified', NULL, '$bidang')";
        
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "DELETE FROM register WHERE id = $register_id");
            $message = 'Registrasi berhasil diverifikasi dan ditambahkan ke peserta dengan bidang ' . htmlspecialchars($bidang) . '!';
            $messageType = 'success';
        } else {
            $message = 'Gagal memverifikasi registrasi: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Tambah peserta
if (isset($_POST['tambah']) && $role == 'admin') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $institusi_id = (int)$_POST['institusi_id'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);
    $create_institusi = isset($_POST['create_institusi']) ? true : false;
    $institusi_nama = $create_institusi ? mysqli_real_escape_string($conn, $_POST['institusi_nama']) : '';

    if ($create_institusi && $institusi_nama) {
        $institusi_query = "INSERT INTO institusi (nama, created_at) VALUES ('$institusi_nama', NOW())";
        if (mysqli_query($conn, $institusi_query)) {
            $institusi_id = mysqli_insert_id($conn);
        } else {
            $message = 'Peserta berhasil ditambahkan, tapi gagal membuat institusi: ' . mysqli_error($conn);
            $messageType = 'warning';
        }
    }

    $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, user_id, alamat, tanggal_masuk, tanggal_keluar, status_verifikasi, status, bidang) 
              VALUES ('$nama', '$email', '$telepon', '$institusi_id', NULL, '$alamat', '$tanggal_masuk', '$tanggal_keluar', 'verified', 'aktif', '$bidang')";
    
    if (mysqli_query($conn, $query)) {
        $peserta_id = mysqli_insert_id($conn);
        $foto_filename = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_result = uploadFoto($_FILES['foto'], $peserta_id);
            if ($upload_result['success']) {
                $foto_filename = $upload_result['filename'];
                mysqli_query($conn, "UPDATE peserta SET foto = '$foto_filename' WHERE id = $peserta_id");
            }
        }
        $message = 'Peserta berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Tambah user untuk peserta
if (isset($_POST['tambah_user']) && $role == 'admin') {
    $peserta_id = (int)$_POST['peserta_id'];
    $user_nama = mysqli_real_escape_string($conn, $_POST['user_nama']);
    $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
    $user_password = password_hash($_POST['user_password'], PASSWORD_BCRYPT);
    $user_role = 'user';

    $user_query = "INSERT INTO users (nama, email, password, role, created_at) 
                   VALUES ('$user_nama', '$user_email', '$user_password', '$user_role', NOW())";
    if (mysqli_query($conn, $user_query)) {
        $user_id = mysqli_insert_id($conn);
        mysqli_query($conn, "UPDATE peserta SET user_id = $user_id WHERE id = $peserta_id");
        $message = 'Akun user berhasil dibuat untuk peserta!';
        $messageType = 'success';
    } else {
        $message = 'Gagal membuat akun user: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Update peserta
if (isset($_POST['update_peserta']) && $role == 'admin') {
    $peserta_id = (int)$_POST['peserta_id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $institusi_id = (int)$_POST['institusi_id'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);

    $query = "UPDATE peserta SET 
              nama = '$nama', 
              email = '$email', 
              telepon = '$telepon', 
              institusi_id = $institusi_id, 
              alamat = '$alamat', 
              tanggal_masuk = '$tanggal_masuk',
              tanggal_keluar = '$tanggal_keluar',
              bidang = '$bidang'
              WHERE id = $peserta_id";
    
    if (mysqli_query($conn, $query)) {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_result = uploadFoto($_FILES['foto'], $peserta_id);
            if ($upload_result['success']) {
                $foto_filename = $upload_result['filename'];
                mysqli_query($conn, "UPDATE peserta SET foto = '$foto_filename' WHERE id = $peserta_id");
            }
        }
        $message = 'Data peserta berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdate data: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Handle manual archiving
if (isset($_GET['arsipkan']) && $role == 'admin') {
    $peserta_id = (int)$_GET['arsipkan'];
    $keterangan = mysqli_real_escape_string($conn, $_GET['keterangan'] ?? 'Selesai');
    
    $insertArsip = mysqli_query($conn, "INSERT INTO arsip(peserta_id, keterangan, tanggal_arsip) VALUES($peserta_id, '$keterangan', NOW())");
    $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='selesai' WHERE id=$peserta_id");
    
    if ($insertArsip && $updatePeserta) {
        $message = 'Peserta berhasil diarsipkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengarsipkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Restore from archive
if (isset($_GET['restore']) && $role == 'admin') {
    $arsip_id = (int)$_GET['restore'];
    $arsipData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT peserta_id FROM arsip WHERE id = $arsip_id"));
    
    if ($arsipData) {
        $peserta_id = $arsipData['peserta_id'];
        $deleteArsip = mysqli_query($conn, "DELETE FROM arsip WHERE id = $arsip_id");
        $updatePeserta = mysqli_query($conn, "UPDATE peserta SET status='aktif' WHERE id = $peserta_id");
        
        if ($deleteArsip && $updatePeserta) {
            $message = 'Peserta berhasil dikembalikan dari arsip!';
            $messageType = 'success';
        } else {
            $message = 'Gagal mengembalikan peserta: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Delete archive permanently
if (isset($_GET['hapus_arsip']) && $role == 'admin') {
    $arsip_id = (int)$_GET['hapus_arsip'];
    
    if (mysqli_query($conn, "DELETE FROM arsip WHERE id = $arsip_id")) {
        $message = 'Data arsip berhasil dihapus!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menghapus data arsip: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// CRUD Institusi
if (isset($_POST['tambah_institusi']) && $role == 'admin') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_institusi']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_institusi']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon_institusi']);
    
    $query = "INSERT INTO institusi (nama, alamat, telepon) VALUES ('$nama', '$alamat', '$telepon')";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

if (isset($_POST['update_institusi']) && $role == 'admin') {
    $id = (int)$_POST['institusi_id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_institusi']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_institusi']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon_institusi']);
    
    $query = "UPDATE institusi SET nama='$nama', alamat='$alamat', telepon='$telepon' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Institusi berhasil diupdate!';
        $messageType = 'success';
    } else {
        $message = 'Gagal mengupdate institusi: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

if (isset($_GET['hapus_institusi']) && $role == 'admin') {
    $id = (int)$_GET['hapus_institusi'];
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM peserta WHERE institusi_id = $id"));
    
    if ($check['count'] > 0) {
        $message = 'Institusi tidak dapat dihapus karena masih digunakan oleh peserta!';
        $messageType = 'error';
    } else {
        if (mysqli_query($conn, "DELETE FROM institusi WHERE id = $id")) {
            $message = 'Institusi berhasil dihapus!';
            $messageType = 'success';
        } else {
            $message = 'Gagal menghapus institusi: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Get data
$peserta = mysqli_query($conn, "SELECT p.*, i.nama as institusi 
                                FROM peserta p 
                                LEFT JOIN institusi i ON p.institusi_id = i.id 
                                WHERE p.status = 'aktif' 
                                ORDER BY p.tanggal_masuk DESC");
$institusi = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama");
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY nama");
$pending_registrations = mysqli_query($conn, "SELECT * FROM register WHERE status = 'pending' ORDER BY created_at DESC");
$arsip = mysqli_query($conn, "SELECT a.*, p.nama, p.email, i.nama as institusi 
                             FROM arsip a 
                             JOIN peserta p ON a.peserta_id = p.id 
                             LEFT JOIN institusi i ON p.institusi_id = i.id 
                             ORDER BY a.tanggal_arsip DESC");
$peserta_aktif = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif' ORDER BY nama");
$peserta_no_user = mysqli_query($conn, "SELECT * FROM peserta WHERE user_id IS NULL AND status='aktif' ORDER BY nama");

// Get statistics
$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta"))['total'];
$peserta_verified = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'verified'"))['total'];
$peserta_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'pending'"))['total'];
$peserta_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'rejected'"))['total'];
$total_arsip = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip"))['total'];
$arsip_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip WHERE MONTH(tanggal_arsip) = MONTH(NOW()) AND YEAR(tanggal_arsip) = YEAR(NOW())"))['total'];
$peserta_aktif_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status='aktif'"))['total'];
$total_institusi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM institusi"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta - <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?= $currentTheme['primary'] ?>;
            --secondary-color: <?= $currentTheme['secondary'] ?>;
            --accent-color: <?= $currentTheme['accent'] ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
            min-height: 100vh;
            color: #1f2937;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 0;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .user-profile {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .user-role {
            font-size: 0.875rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
        }
        
        .nav-menu {
            padding: 1rem 0;
            position: relative;
            z-index: 1;
        }
        
        .nav-item {
            margin: 0.25rem 1rem;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            text-decoration: none;
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateX(5px);
            backdrop-filter: blur(10px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .logout-link {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 1rem;
        }
        
        .logout-link .nav-link {
            color: #fecaca !important;
            background: rgba(239, 68, 68, 0.1);
        }
        
        .logout-link .nav-link:hover {
            background: rgba(239, 68, 68, 0.2);
            color: white !important;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .header h1 {
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-weight: 400;
            margin-top: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .stat-title {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
        }
        
        .card-header h3 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color), 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .table tbody tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-verified {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fecaca;
            color: #991b1b;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completed {
            background: #fecaca;
            color: #991b1b;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        
        .alert-error {
            background: #fecaca;
            color: #991b1b;
            border-color: #ef4444;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
            border-radius: 16px 16px 0 0;
        }
        
        .modal-header h4 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .close {
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            color: #6b7280;
        }
        
        .close:hover {
            color: var(--primary-color);
        }
        
        .collapse-section {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .collapse-header {
            padding: 0.75rem 1rem;
            background: #f9fafb;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .collapse-content {
            padding: 1rem;
            display: none;
        }
        
        .collapse-content.active {
            display: block;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            cursor: pointer;
        }
        
        .action-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .action-btn:hover {
            background: var(--secondary-color);
            border-color: var(--primary-color);
        }
        
        .section-content {
            display: none;
        }
        
        .section-content.active {
            display: block;
        }
        
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 500;
            clan
            border-radius: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .bg-secondary {
            background-color: #6b7280 !important;
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6b7280 !important;
        }
        
        .py-4 {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.75rem;
        }
        
        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding: 0 0.75rem;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 0.75rem;
        }
        
        .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
            padding: 0 0.75rem;
        }
        
        .col-md-12 {
            flex: 0 0 100%;
            max-width: 100%;
            padding: 0 0.75rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .table-container {
                font-size: 0.875rem;
            }
            
            .col-md-4, .col-md-6, .col-md-8, .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <?= $roleIcons[$role] ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= ucfirst($role) ?></div>
            </div>

            <div class="nav-menu">
                <?php if ($role == 'admin'): ?>
                    <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></div>
                    <div class="nav-item"><a class="nav-link active" href="peserta.php"><i class="fas fa-users"></i> Data Peserta</a></div>
                    <div class="nav-item"><a class="nav-link" href="schedule_report.php"><i class="fas fa-calendar"></i> Jadwal & Laporan</a></div>
                    <div class="nav-item"><a class="nav-link" href="idcard.php"><i class="fas fa-id-card"></i> Cetak ID Card</a></div>
                    <div class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profil</a></div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></div>
                  <div class="nav-item"><a class="nav-link" href="schedule_report.php"><i class="fas fa-calendar"></i> Jadwal & Laporan</a></div>
                    <div class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profil</a></div>
                <?php elseif ($role == 'user'): ?>
                    <div class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></div>
 <div class="nav-item"><a class="nav-link" href="schedule_report.php"><i class="fas fa-calendar"></i> Jadwal & Laporan</a></div>
                    <div class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profil</a></div>
                <?php endif; ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <main class="main-content">
            <div class="header">
                <h1>
                    <i class="fas fa-users"></i>
                    Data Peserta
                </h1>
                <p class="subtitle">Kelola data peserta, verifikasi, dan arsip magang</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Quick Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn active" onclick="showSection('verifikasi')"><i class="fas fa-check-circle"></i> Verifikasi & Daftar Peserta</button>
                <button class="action-btn" onclick="showSection('tambah_peserta')"><i class="fas fa-user-plus"></i> Tambah Peserta</button>
                <button class="action-btn" onclick="showSection('tambah_user')"><i class="fas fa-user-plus"></i> Tambah Akun User</button>
                <button class="action-btn" onclick="showSection('tambah_institusi')"><i class="fas fa-building"></i> Tambah Institusi</button>
                <button class="action-btn" onclick="showSection('daftar_institusi')"><i class="fas fa-list"></i> Daftar Institusi</button>
                <button class="action-btn" onclick="showSection('arsip')"><i class="fas fa-archive"></i> Arsip Peserta</button>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Total Peserta</div>
                    <div class="stat-value"><?= $total_peserta ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-title">Terverifikasi</div>
                    <div class="stat-value"><?= $peserta_verified ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="stat-title">Total Arsip</div>
                    <div class="stat-value"><?= $total_arsip ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-title">Total Institusi</div>
                    <div class="stat-value"><?= $total_institusi ?></div>
                </div>
            </div>

            <!-- Section: Verifikasi -->
            <div id="verifikasi-section" class="section-content active">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-bell"></i>
                                Registrasi Menunggu Verifikasi
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Tanggal Masuk</th>
                                            <th>Tanggal Keluar</th>
                                            <th>Bidang</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($r = mysqli_fetch_assoc($pending_registrations)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['nama']) ?></td>
                                            <td><?= htmlspecialchars($r['email']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($r['tanggal_masuk'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($r['tanggal_keluar'])) ?></td>
                                            <td>
                                                <form method="post" id="verifyForm_<?= $r['id'] ?>">
                                                    <input type="hidden" name="register_id" value="<?= $r['id'] ?>">
                                                    <select name="bidang" class="form-control" required>
                                                        <option value="" disabled selected>Pilih Bidang</option>
                                                        <?php foreach($bidang_options as $bidang): ?>
                                                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                            </td>
                                            <td>
                                                <button type="submit" name="verify_registration" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Verifikasi
                                                </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($pending_registrations) == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div style="padding: 2rem;">
                                                    <i class="fas fa-bell" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                    <p>Tidak ada registrasi pending</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-list"></i>
                            Daftar Peserta Aktif
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Institusi</th>
                                        <th>Bidang</th>
                                        <th>Tanggal Masuk</th>
                                        <th>Tanggal Keluar</th>
                                        <th>Status Magang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($p = mysqli_fetch_assoc($peserta)): 
                                        $today = date('Y-m-d');
                                        $status_magang = (strtotime($p['tanggal_keluar']) < strtotime($today)) ? 'completed' : 'active';
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                                        <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($p['institusi'] ?? 'Tidak ada institusi') ?></td>
                                        <td><?= htmlspecialchars($p['bidang'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y', strtotime($p['tanggal_masuk'] ?? 'now')) ?></td>
                                        <td><?= date('d/m/Y', strtotime($p['tanggal_keluar'] ?? 'now')) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $status_magang ?>">
                                                <?= ucfirst($status_magang) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" onclick="showDetail(<?= $p['id'] ?>)" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($role == 'admin'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="showEditModal(<?= $p['id'] ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php if ($status_magang == 'active'): ?>
                                                        <a href="?arsipkan=<?= $p['id'] ?>&keterangan=Selesai" class="btn btn-sm btn-warning" onclick="return confirm('Yakin ingin mengarsipkan peserta \"<?= htmlspecialchars($p['nama']) ?>\" dengan keterangan \"Selesai\"?')">
                                                            <i class="fas fa-archive"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if (mysqli_num_rows($peserta) == 0): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div style="padding: 2rem;">
                                                <i class="fas fa-users" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                                                <p>Belum ada data peserta</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Tambah Peserta -->
            <div id="tambah_peserta-section" class="section-content">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-user-plus"></i>
                                Tambah Peserta Baru
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Foto Profil</label>
                                            <div class="foto-upload-container" style="text-align: center; margin-bottom: 1rem;">
                                                <div class="foto-preview" style="width: 150px; height: 150px; border: 2px dashed #d1d5db; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; background: #f9fafb; position: relative; overflow: hidden;">
                                                    <div class="foto-placeholder" id="fotoPlaceholder">
                                                        <i class="fas fa-camera" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.5rem;"></i>
                                                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Upload Foto</p>
                                                    </div>
                                                    <img id="fotoPreview" style="width: 100%; height: 100%; object-fit: cover; display: none;" alt="Preview Foto">
                                                </div>
                                                <input type="file" name="foto" id="fotoInput" accept="image/*" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('fotoInput').click()">
                                                    <i class="fas fa-upload"></i> Pilih Foto
                                                </button>
                                                <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem;">Max 5MB (JPG, PNG, GIF)</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Nama Lengkap *</label>
                                                    <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Email *</label>
                                                    <input type="email" name="email" class="form-control" placeholder="email@domain.com" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Telepon</label>
                                                    <input type="tel" name="telepon" class="form-control" placeholder="08xxxxxxxxxx">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Tanggal Masuk *</label>
                                                    <input type="date" name="tanggal_masuk" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Tanggal Keluar *</label>
                                                    <input type="date" name="tanggal_keluar" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Bidang *</label>
                                                    <select name="bidang" class="form-control" required>
                                                        <option value="" disabled selected>Pilih Bidang</option>
                                                        <?php foreach($bidang_options as $bidang): ?>
                                                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Institusi *</label>
                                            <select name="institusi_id" class="form-control" required>
                                                <option value="" disabled selected>Pilih Institusi</option>
                                                <?php 
                                                mysqli_data_seek($institusi, 0);
                                                while($i = mysqli_fetch_assoc($institusi)): ?>
                                                    <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['nama']) ?></option>
                                                <?php endwhile; ?>
                                                <option value="new">Buat Institusi Baru</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap"></textarea>
                                </div>

                                <div class="collapse-section">
                                    <div class="collapse-header" onclick="toggleCollapse(this)">
                                        <span><i class="fas fa-building"></i> Buat Institusi Baru (Opsional)</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="collapse-content">
                                        <input type="checkbox" name="create_institusi" id="create_institusi" style="margin-bottom: 1rem;">
                                        <label for="create_institusi" style="margin-left: 0.5rem;">Buat institusi baru untuk peserta ini</label>
                                        <div class="row" style="margin-top: 1rem;">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label">Nama Institusi *</label>
                                                    <input type="text" name="institusi_nama" class="form-control" placeholder="Masukkan nama institusi" disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <p style="font-size: 0.75rem; color: #6b7280;">Catatan: Institusi baru akan ditambahkan ke daftar institusi.</p>
                                    </div>
                                </div>

                                <button type="submit" name="tambah" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Tambah Peserta
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section: Tambah Akun User -->
            <div id="tambah_user-section" class="section-content">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-user-plus"></i>
                                Tambah Akun User untuk Peserta
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Pilih Peserta *</label>
                                            <select name="peserta_id" class="form-control" required>
                                                <option value="" disabled selected>Pilih Peserta</option>
                                                <?php 
                                                mysqli_data_seek($peserta_no_user, 0);
                                                while($p = mysqli_fetch_assoc($peserta_no_user)): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['email']) ?>)</option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Nama User *</label>
                                            <input type="text" name="user_nama" class="form-control" placeholder="Masukkan nama user" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Email User *</label>
                                            <input type="email" name="user_email" class="form-control" placeholder="email@domain.com" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Password User *</label>
                                            <input type="password" name="user_password" class="form-control" placeholder="Masukkan password" required>
                                        </div>
                                    </div>
                                </div>
                                <p style="font-size: 0.75rem; color: #6b7280;">Catatan: Akun akan dibuat dengan peran 'user' secara default.</p>
                                <button type="submit" name="tambah_user" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Tambah Akun User
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section: Tambah Institusi -->
            <div id="tambah_institusi-section" class="section-content">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-building"></i>
                                Tambah Institusi Baru
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Nama Institusi *</label>
                                            <input type="text" name="nama_institusi" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Telepon</label>
                                            <input type="text" name="telepon_institusi" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="alamat_institusi" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="tambah_institusi" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Tambah Institusi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section: Daftar Institusi -->
            <div id="daftar_institusi-section" class="section-content">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-list"></i>
                                Daftar Institusi
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Institusi</th>
                                            <th>Alamat</th>
                                            <th>Telepon</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        mysqli_data_seek($institusi, 0);
                                        while($row = mysqli_fetch_assoc($institusi)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                                <td><?= htmlspecialchars($row['alamat']) ?></td>
                                                <td><?= htmlspecialchars($row['telepon']) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button onclick="editInstitusi(<?= $row['id'] ?>, '<?= addslashes($row['nama']) ?>', '<?= addslashes($row['alamat']) ?>', '<?= addslashes($row['telepon']) ?>')" 
                                                                class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                            Edit
                                                        </button>
                                                        <a href="?hapus_institusi=<?= $row['id'] ?>" 
                                                           class="btn btn-danger btn-sm"
                                                           onclick="return confirm('Yakin ingin menghapus institusi ini?')">
                                                            <i class="fas fa-trash"></i>
                                                            Hapus
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($institusi) == 0): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-building fa-3x mb-3"></i>
                                                    <br>
                                                    Belum ada data institusi
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section: Arsip -->
            <div id="arsip-section" class="section-content">
                <?php if ($role == 'admin'): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-archive"></i>
                                Arsipkan Peserta
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="get">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Pilih Peserta *</label>
                                            <select name="arsipkan" class="form-control" required>
                                                <option value="" disabled selected>Pilih Peserta</option>
                                                <?php 
                                                mysqli_data_seek($peserta_aktif, 0);
                                                while($p = mysqli_fetch_assoc($peserta_aktif)): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Keterangan</label>
                                            <select name="keterangan" class="form-control">
                                                <option value="Selesai">Magang Selesai</option>
                                                <option value="Lulus">Lulus Evaluasi</option>
                                                <option value="Berhenti">Berhenti</option>
                                                <option value="Pindah">Pindah Institusi</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-archive"></i>
                                        Arsipkan Peserta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-list"></i>
                            Daftar Arsip Peserta
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Peserta</th>
                                        <th>Email</th>
                                        <th>Institusi</th>
                                        <th>Keterangan</th>
                                        <th>Tanggal Arsip</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($arsip)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['institusi'] ?? 'Tidak ada') ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['keterangan']) ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($row['tanggal_arsip'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($role == 'admin'): ?>
                                                        <a href="?restore=<?= $row['id'] ?>" 
                                                           class="btn btn-success btn-sm"
                                                           onclick="return confirm('Yakin ingin mengembalikan peserta ini dari arsip?')">
                                                            <i class="fas fa-undo"></i>
                                                            Restore
                                                        </a>
                                                        <a href="?hapus_arsip=<?= $row['id'] ?>" 
                                                           class="btn btn-danger btn-sm"
                                                           onclick="return confirm('Yakin ingin menghapus data arsip ini secara permanen?')">
                                                            <i class="fas fa-trash"></i>
                                                            Hapus
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if (mysqli_num_rows($arsip) == 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <br>
                                                Belum ada data arsip
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit Peserta -->
                <div id="editModal" class="modal">
                    <div class="modal-content" style="max-width: 800px;">
                        <div class="modal-header">
                            <h4>Edit Data Peserta</h4>
                            <span class="close" onclick="closeModal('editModal')">×</span>
                        </div>
                        <div class="modal-body">
                            <form method="post" id="editForm" enctype="multipart/form-data">
                                <input type="hidden" name="peserta_id" id="editPesertaId">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Foto Profil</label>
                                            <div class="foto-upload-container" style="text-align: center; margin-bottom: 1rem;">
                                                <div class="foto-preview" style="width: 150px; height: 150px; border: 2px dashed #d1d5db; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; background: #f9fafb; position: relative; overflow: hidden;">
                                                    <div class="foto-placeholder" id="editFotoPlaceholder">
                                                        <i class="fas fa-camera" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.5rem;"></i>
                                                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Upload Foto</p>
                                                    </div>
                                                    <img id="editFotoPreview" style="width: 100%; height: 100%; object-fit: cover; display: none;" alt="Preview Foto Edit">
                                                </div>
                                                <input type="file" name="foto" id="editFotoInput" accept="image/*" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('editFotoInput').click()">
                                                    <i class="fas fa-upload"></i> Ubah Foto
                                                </button>
                                                <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.5rem;">Max 5MB (JPG, PNG, GIF)</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Nama Lengkap *</label>
                                                    <input type="text" name="nama" id="editNama" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Email *</label>
                                                    <input type="email" name="email" id="editEmail" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Telepon</label>
                                                    <input type="tel" name="telepon" id="editTelepon" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Tanggal Masuk *</label>
                                                    <input type="date" name="tanggal_masuk" id="editTanggalMasuk" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Tanggal Keluar *</label>
                                                    <input type="date" name="tanggal_keluar" id="editTanggalKeluar" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Bidang *</label>
                                                    <select name="bidang" id="editBidang" class="form-control" required>
                                                        <option value="" disabled>Pilih Bidang</option>
                                                        <?php foreach($bidang_options as $bidang): ?>
                                                            <option value="<?= $bidang ?>"><?= $bidang ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label">Institusi *</label>
                                                    <select name="institusi_id" id="editInstitusiId" class="form-control" required>
                                                        <option value="" disabled>Pilih Institusi</option>
                                                        <?php 
                                                        mysqli_data_seek($institusi, 0);
                                                        while($i = mysqli_fetch_assoc($institusi)): ?>
                                                            <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['nama']) ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="alamat" id="editAlamat" class="form-control" rows="3"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name="update_peserta" class="btn btn-primary">
                                                <i class="fas fa-save"></i>
                                                Simpan Perubahan
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">
                                                <i class="fas fa-times"></i>
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Detail Peserta -->
                <div id="detailModal" class="modal">
                    <div class="modal-content" style="max-width: 600px;">
                        <div class="modal-header">
                            <h4>Detail Peserta</h4>
                            <span class="close" onclick="closeModal('detailModal')">×</span>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img id="detailFoto" src="" alt="Foto Peserta" style="width: 150px; height: 150px; border-radius: 12px; object-fit: cover; margin-bottom: 1rem;">
                                </div>
                                <div class="col-md-8">
                                    <p><strong>Nama:</strong> <span id="detailNama"></span></p>
                                    <p><strong>Email:</strong> <span id="detailEmail"></span></p>
                                    <p><strong>Telepon:</strong> <span id="detailTelepon"></span></p>
                                    <p><strong>Institusi:</strong> <span id="detailInstitusi"></span></p>
                                    <p><strong>Bidang:</strong> <span id="detailBidang"></span></p>
                                    <p><strong>Tanggal Masuk:</strong> <span id="detailTanggalMasuk"></span></p>
                                    <p><strong>Tanggal Keluar:</strong> <span id="detailTanggalKeluar"></span></p>
                                    <p><strong>Alamat:</strong> <span id="detailAlamat"></span></p>
                                    <p><strong>Status Verifikasi:</strong> <span id="detailStatusVerifikasi"></span></p>
                                    <p><strong>Status Magang:</strong> <span id="detailStatusMagang"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit Institusi -->
                <div id="editInstitusiModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>Edit Institusi</h4>
                            <span class="close" onclick="closeModal('editInstitusiModal')">×</span>
                        </div>
                        <div class="modal-body">
                            <form method="post">
                                <input type="hidden" name="institusi_id" id="editInstitusiId">
                                <div class="form-group">
                                    <label class="form-label">Nama Institusi *</label>
                                    <input type="text" name="nama_institusi" id="editNamaInstitusi" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat_institusi" id="editAlamatInstitusi" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" name="telepon_institusi" id="editTeleponInstitusi" class="form-control">
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="update_institusi" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        Simpan
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="closeModal('editInstitusiModal')">
                                        <i class="fas fa-times"></i>
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <script>
            // Show specific section
            function showSection(sectionId) {
                document.querySelectorAll('.section-content').forEach(section => {
                    section.classList.remove('active');
                });
                document.querySelectorAll('.action-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.getElementById(sectionId + '-section').classList.add('active');
                document.querySelector(`button[onclick="showSection('${sectionId}')"]`).classList.add('active');
            }

            // Toggle collapse section
            function toggleCollapse(element) {
                const content = element.nextElementSibling;
                content.classList.toggle('active');
                const chevron = element.querySelector('.fa-chevron-down');
                chevron.classList.toggle('fa-chevron-up');
            }

            // Enable/disable institusi input based on checkbox
            document.getElementById('create_institusi').addEventListener('change', function() {
                document.querySelector('input[name="institusi_nama"]').disabled = !this.checked;
            });

            // Foto preview
            function setupFotoPreview(inputId, previewId, placeholderId) {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);
                const placeholder = document.getElementById(placeholderId);

                input.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            placeholder.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                        placeholder.style.display = 'block';
                    }
                });
            }

            setupFotoPreview('fotoInput', 'fotoPreview', 'fotoPlaceholder');
            setupFotoPreview('editFotoInput', 'editFotoPreview', 'editFotoPlaceholder');

            // Show edit modal
            function showEditModal(id) {
                fetch('get_peserta.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('editPesertaId').value = data.id;
                        document.getElementById('editNama').value = data.nama;
                        document.getElementById('editEmail').value = data.email || '';
                        document.getElementById('editTelepon').value = data.telepon || '';
                        document.getElementById('editTanggalMasuk').value = data.tanggal_masuk || '';
                        document.getElementById('editTanggalKeluar').value = data.tanggal_keluar || '';
                        document.getElementById('editBidang').value = data.bidang || '';
                        document.getElementById('editInstitusiId').value = data.institusi_id || '';
                        document.getElementById('editAlamat').value = data.alamat || '';
                        const editFotoPreview = document.getElementById('editFotoPreview');
                        const editFotoPlaceholder = document.getElementById('editFotoPlaceholder');
                        if (data.foto) {
                            editFotoPreview.src = 'Uploads/peserta/' + data.foto;
                            editFotoPreview.style.display = 'block';
                            editFotoPlaceholder.style.display = 'none';
                        } else {
                            editFotoPreview.style.display = 'none';
                            editFotoPlaceholder.style.display = 'block';
                        }
                        document.getElementById('editModal').style.display = 'block';
                    })
                    .catch(error => {
                        alert('Gagal memuat data peserta: ' + error);
                    });
            }

            // Show detail modal
            function showDetail(id) {
                fetch('get_peserta.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('detailNama').textContent = data.nama;
                        document.getElementById('detailEmail').textContent = data.email || '-';
                        document.getElementById('detailTelepon').textContent = data.telepon || '-';
                        document.getElementById('detailInstitusi').textContent = data.institusi || 'Tidak ada';
                        document.getElementById('detailBidang').textContent = data.bidang || '-';
                        document.getElementById('detailTanggalMasuk').textContent = data.tanggal_masuk ? new Date(data.tanggal_masuk).toLocaleDateString('id-ID') : '-';
                        document.getElementById('detailTanggalKeluar').textContent = data.tanggal_keluar ? new Date(data.tanggal_keluar).toLocaleDateString('id-ID') : '-';
                        document.getElementById('detailAlamat').textContent = data.alamat || '-';
                        document.getElementById('detailStatusVerifikasi').textContent = data.status_verifikasi || '-';
                        document.getElementById('detailStatusMagang').textContent = data.status || '-';
                        const detailFoto = document.getElementById('detailFoto');
                        detailFoto.src = data.foto ? 'Uploads/peserta/' + data.foto : 'https://via.placeholder.com/150';
                        document.getElementById('detailModal').style.display = 'block';
                    })
                    .catch(error => {
                        alert('Gagal memuat data peserta: ' + error);
                    });
            }

            // Close modal
            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
                if (modalId === 'editModal') {
                    document.getElementById('editForm').reset();
                    document.getElementById('editFotoPreview').style.display = 'none';
                    document.getElementById('editFotoPlaceholder').style.display = 'block';
                }
            }

            // Edit institusi
            function editInstitusi(id, nama, alamat, telepon) {
                document.getElementById('editInstitusiId').value = id;
                document.getElementById('editNamaInstitusi').value = nama;
                document.getElementById('editAlamatInstitusi').value = alamat;
                document.getElementById('editTeleponInstitusi').value = telepon;
                document.getElementById('editInstitusiModal').style.display = 'block';
            }

            // Confirm delete
            function confirmDelete(id, nama) {
                if (confirm(`Yakin ingin menghapus peserta "${nama}"?`)) {
                    window.location.href = `delete_peserta.php?id=${id}`;
                }
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modals = document.getElementsByClassName('modal');
                for (let modal of modals) {
                    if (event.target == modal) {
                        closeModal(modal.id);
                    }
                }
            }
        </script>
    </body>
</html>
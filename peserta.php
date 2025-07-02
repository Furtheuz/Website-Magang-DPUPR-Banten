<?php 
include "config/auth.php"; 
include "config/db.php"; 
checkLogin();

$role = $_SESSION['user']['role']; 
$userName = $_SESSION['user']['name'] ?? 'User';

// Role-based styling
$roleColors = [
    'admin' => ['primary' => '#dc2626', 'secondary' => '#fef2f2', 'accent' => '#b91c1c'],
    'pembimbing' => ['primary' => '#059669', 'secondary' => '#f0fdf4', 'accent' => '#047857'],
    'user' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];

$roleIcons = [
    'admin' => '👑',
    'pembimbing' => '👨‍🏫',
    'user' => '👨‍🎓'
];

$currentTheme = $roleColors[$role];

// Handle actions
$message = '';
$messageType = '';

// Tambah peserta
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $institusi_id = (int)$_POST['institusi_id'];
    $user_id = (int)$_POST['user_id'];
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    
    $query = "INSERT INTO peserta (nama, email, telepon, institusi_id, user_id, alamat, tanggal_masuk, status_verifikasi) 
              VALUES ('$nama', '$email', '$telepon', '$institusi_id', '$user_id', '$alamat', '$tanggal_masuk', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        $message = 'Peserta berhasil ditambahkan!';
        $messageType = 'success';
    } else {
        $message = 'Gagal menambahkan peserta: ' . mysqli_error($conn);
        $messageType = 'error';
    }
}

// Update status verifikasi
if (isset($_POST['update_status'])) {
    $peserta_id = (int)$_POST['peserta_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE peserta SET status_verifikasi = '$status' WHERE id = $peserta_id";
    if (mysqli_query($conn, $query)) {
        $message = 'Status verifikasi berhasil diupdate!';
        $messageType = 'success';
    }
}

// Hapus peserta
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if (mysqli_query($conn, "DELETE FROM peserta WHERE id=$id")) {
        $message = 'Peserta berhasil dihapus!';
        $messageType = 'success';
    }
}

// Get data
$peserta = mysqli_query($conn, "SELECT p.*, i.nama as institusi, u.nama as user_name 
                                FROM peserta p 
                                LEFT JOIN institusi i ON p.institusi_id = i.id 
                                LEFT JOIN users u ON p.user_id = u.id 
                                ORDER BY p.tanggal_masuk DESC");

$institusi = mysqli_query($conn, "SELECT * FROM institusi ORDER BY nama");
$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY nama");

// Get statistics
$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta"))['total'];
$peserta_verified = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'verified'"))['total'];
$peserta_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'pending'"))['total'];
$peserta_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'rejected'"))['total'];
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
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <?= $roleIcons[$role] ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= ucfirst($role) ?></div>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </div>
                <?php if ($role == 'admin'): ?>
                    <div class="nav-item">
                        <a class="nav-link active" href="peserta.php">
                            <i class="fas fa-users"></i>
                            Data Peserta
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="jadwal.php">
                            <i class="fas fa-calendar-alt"></i>
                            Jadwal
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="laporan.php">
                            <i class="fas fa-chart-line"></i>
                            Laporan
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="idcard.php">
                            <i class="fas fa-id-card"></i>
                            Cetak ID Card
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="arsip.php">
                            <i class="fas fa-archive"></i>
                            Arsip
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i>
                            Pengaturan
                        </a>
                    </div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="nav-item">
                        <a class="nav-link" href="jadwal.php">
                            <i class="fas fa-calendar-check"></i>
                            Lihat Jadwal
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link active" href="peserta.php">
                            <i class="fas fa-user-graduate"></i>
                            Peserta Bimbingan
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="nav-item logout-link">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>
                    <i class="fas fa-users"></i>
                    Data Peserta
                </h1>
                <p class="subtitle">Kelola data peserta dengan mudah dan efisien</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

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
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-title">Pending</div>
                    <div class="stat-value"><?= $peserta_pending ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-title">Ditolak</div>
                    <div class="stat-value"><?= $peserta_rejected ?></div>
                </div>
            </div>

            <?php if ($role == 'admin'): ?>
            <!-- Form Tambah Peserta -->
            <div class="content-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-plus"></i>
                        Tambah Peserta Baru
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post">
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
                                    <label class="form-label">Institusi *</label>
                                    <select name="institusi_id" class="form-control" required>
                                        <option value="" disabled selected>Pilih Institusi</option>
                                        <?php 
                                        mysqli_data_seek($institusi, 0);
                                        while($i = mysqli_fetch_assoc($institusi)): ?>
                                            <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['nama']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Akun User *</label>
                                    <select name="user_id" class="form-control" required>
                                        <option value="" disabled selected>Pilih User</option>
                                        <?php 
                                        mysqli_data_seek($users, 0);
                                        while($u = mysqli_fetch_assoc($users)): ?>
                                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap"></textarea>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Tambah Peserta
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Daftar Peserta -->
            <div class="content-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Daftar Peserta
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
                                    <th>Tanggal Masuk</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = mysqli_fetch_assoc($peserta)): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nama']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($p['user_name'] ?? 'Tidak ada user') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['institusi'] ?? 'Tidak ada institusi') ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['tanggal_masuk'] ?? 'now')) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $p['status_verifikasi'] ?? 'pending' ?>">
                                            <?= ucfirst($p['status_verifikasi'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showDetail(<?= $p['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($role == 'admin'): ?>
                                        <button class="btn btn-sm btn-warning" onclick="showStatusModal(<?= $p['id'] ?>, '<?= $p['status_verifikasi'] ?? 'pending' ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus peserta ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if (mysqli_num_rows($peserta) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center">
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
        </main>
    </div>

    <!-- Modal Detail Peserta -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Detail Peserta</h4>
                <span class="close" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Update Status -->
    <?php if ($role == 'admin'): ?>
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Update Status Verifikasi</h4>
                <span class="close" onclick="closeModal('statusModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" id="statusForm">
                    <input type="hidden" name="peserta_id" id="statusPesertaId">
                    <div class="form-group">
                        <label class="form-label">Status Verifikasi</label>
                        <select name="status" id="statusSelect" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div style="text-align: right; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')" style="margin-right: 0.5rem;">
                            Batal
                        </button>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Show detail modal
        function showDetail(pesertaId) {
            showModal('detailModal');
            
            // Fetch detail data via AJAX
            fetch(`get_peserta_detail.php?id=${pesertaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const peserta = data.peserta;
                        const statusClass = peserta.status_verifikasi || 'pending';
                        const statusText = peserta.status_verifikasi ? peserta.status_verifikasi.charAt(0).toUpperCase() + peserta.status_verifikasi.slice(1) : 'Pending';
                        
                        document.getElementById('detailContent').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 style="color: var(--primary-color); margin-bottom: 0.5rem;">Informasi Pribadi</h6>
                                    <table style="width: 100%; margin-bottom: 1.5rem;">
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Nama:</td><td style="padding: 0.25rem 0;">${peserta.nama || '-'}</td></tr>
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Email:</td><td style="padding: 0.25rem 0;">${peserta.email || '-'}</td></tr>
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Telepon:</td><td style="padding: 0.25rem 0;">${peserta.telepon || '-'}</td></tr>
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Alamat:</td><td style="padding: 0.25rem 0;">${peserta.alamat || '-'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 style="color: var(--primary-color); margin-bottom: 0.5rem;">Informasi Akademik</h6>
                                    <table style="width: 100%; margin-bottom: 1.5rem;">
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Institusi:</td><td style="padding: 0.25rem 0;">${peserta.institusi || '-'}</td></tr>
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">User Account:</td><td style="padding: 0.25rem 0;">${peserta.user_name || '-'}</td></tr>
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Tanggal Masuk:</td><td style="padding: 0.25rem 0;">${peserta.tanggal_masuk ? new Date(peserta.tanggal_masuk).toLocaleDateString('id-ID') : '-'}</td></tr>
                                        <tr><td style="padding: 0.25rem 0; font-weight: 500;">Status:</td><td style="padding: 0.25rem 0;"><span class="status-badge status-${statusClass}">${statusText}</span></td></tr>
                                    </table>
                                </div>
                            </div>
                        `;
                    } else {
                        document.getElementById('detailContent').innerHTML = `
                            <div class="alert alert-error">
                                Gagal memuat detail peserta: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('detailContent').innerHTML = `
                        <div class="alert alert-error">
                            Terjadi kesalahan saat memuat data
                        </div>
                    `;
                });
        }

        // Show status modal
        function showStatusModal(pesertaId, currentStatus) {
            document.getElementById('statusPesertaId').value = pesertaId;
            document.getElementById('statusSelect').value = currentStatus;
            showModal('statusModal');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Add smooth transitions
        document.querySelectorAll('.stat-card, .btn').forEach(element => {
            element.addEventListener('mouseenter', function() {
                if (this.classList.contains('stat-card')) {
                    this.style.transform = 'translateY(-5px)';
                }
            });
            
            element.addEventListener('mouseleave', function() {
                if (this.classList.contains('stat-card')) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });

        // Auto hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Search functionality (if needed)
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.querySelector('.table tbody');
            const rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - 1; j++) { // -1 to exclude action column
                    if (cells[j] && cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#ef4444';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#d1d5db';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Mohon lengkapi semua field yang wajib diisi!');
                }
            });
        });
    </script>
</body>
</html>
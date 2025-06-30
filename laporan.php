<?php 
include "config/auth.php"; 
include "config/db.php"; 
checkLogin();  

$role = $_SESSION['user']['role']; 
$user_id = $_SESSION['user']['id']; 
$userName = $_SESSION['user']['name'] ?? 'User';
$pesan = "";  

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

// Input laporan oleh user 
if ($role == 'user' && isset($_POST['isi'])) {   
    $tanggal = $_POST['tanggal'];   
    $kegiatan = $_POST['kegiatan'];    
    
    $q = mysqli_query($conn, "SELECT id FROM peserta WHERE user_id = $user_id LIMIT 1");   
    $peserta = mysqli_fetch_assoc($q);    
    
    if ($peserta) {     
        $peserta_id = $peserta['id'];     
        mysqli_query($conn, "INSERT INTO laporan (peserta_id, tanggal, kegiatan) VALUES ('$peserta_id','$tanggal','$kegiatan')");     
        $pesan = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Laporan berhasil disimpan.</div>";   
    } else {     
        $pesan = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Gagal mengisi laporan: Data peserta tidak ditemukan.</div>";   
    } 
}  

// Validasi oleh pembimbing 
if ($role == 'pembimbing' && isset($_GET['validasi'])) {   
    $id = $_GET['validasi'];   
    mysqli_query($conn, "UPDATE laporan SET validasi='valid' WHERE id=$id");   
    header("Location: laporan.php");   
    exit; 
}  

// Hapus laporan (untuk user dan admin)
if (($role == 'user' || $role == 'admin') && isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    if ($role == 'user') {
        // User hanya bisa hapus laporan sendiri yang belum divalidasi
        mysqli_query($conn, "DELETE FROM laporan WHERE id=$id AND peserta_id IN (SELECT id FROM peserta WHERE user_id=$user_id) AND validasi='belum'");
    } else {
        // Admin bisa hapus semua laporan
        mysqli_query($conn, "DELETE FROM laporan WHERE id=$id");
    }
    header("Location: laporan.php");
    exit;
}

// Ambil data laporan 
if ($role == 'user') {   
    $laporan = mysqli_query($conn, "SELECT * FROM laporan l JOIN peserta p ON l.peserta_id=p.id WHERE p.user_id=$user_id ORDER BY l.tanggal DESC"); 
} else {   
    $laporan = mysqli_query($conn, "SELECT l.*, u.nama FROM laporan l JOIN peserta p ON l.peserta_id=p.id JOIN users u ON p.user_id=u.id ORDER BY l.tanggal DESC"); 
} 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kegiatan - <?= ucfirst($role) ?></title>
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
        
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .form-card h3 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color), 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(var(--primary-color), 0.3);
        }
        
        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-card-header h3 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .table-responsive {
            margin: 0;
        }
        
        .table {
            margin: 0;
            border: none;
        }
        
        .table th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1rem;
            border: none;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: var(--secondary-color);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-belum {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-valid {
            background: #d1fae5;
            color: #059669;
        }
        
        .btn-validate {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-validate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-left: 0.5rem;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-mini {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-mini-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-mini-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .btn-close {
            filter: invert(1);
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
                padding: 1rem;
            }
            
            .nav-item {
                flex-shrink: 0;
                margin: 0 0.25rem;
            }
            
            .main-content {
                padding: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn-validate, .btn-delete {
                width: 100%;
                margin-left: 0;
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
                <?php if ($role == 'admin'): ?>
                    <div class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="peserta.php">
                            <i class="fas fa-users"></i>
                            Data Peserta
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="institusi.php">
                            <i class="fas fa-building"></i>
                            Institusi
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="jadwal.php">
                            <i class="fas fa-calendar-alt"></i>
                            Jadwal
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link active" href="laporan.php">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="jadwal.php">
                            <i class="fas fa-calendar-check"></i>
                            Lihat Jadwal
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link active" href="laporan.php">
                            <i class="fas fa-clipboard-check"></i>
                            Validasi Laporan
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="peserta.php">
                            <i class="fas fa-user-graduate"></i>
                            Peserta Bimbingan
                        </a>
                    </div>
                <?php elseif ($role == 'user'): ?>
                    <div class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="jadwal.php">
                            <i class="fas fa-calendar"></i>
                            Jadwal Saya
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link active" href="laporan.php">
                            <i class="fas fa-file-alt"></i>
                            Laporan Saya
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i>
                            Profil
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="tugas.php">
                            <i class="fas fa-tasks"></i>
                            Tugas
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
                    <i class="fas fa-file-alt"></i>
                    <?php if ($role == 'user'): ?>
                        Laporan Kegiatan Saya
                    <?php elseif ($role == 'pembimbing'): ?>
                        Validasi Laporan Kegiatan
                    <?php else: ?>
                        Manajemen Laporan Kegiatan
                    <?php endif; ?>
                </h1>
                <p class="subtitle">
                    <?php if ($role == 'user'): ?>
                        Catat dan kelola laporan kegiatan harian Anda
                    <?php elseif ($role == 'pembimbing'): ?>
                        Review dan validasi laporan kegiatan peserta
                    <?php else: ?>
                        Monitor dan kelola semua laporan kegiatan
                    <?php endif; ?>
                </p>
            </div>

            <?= $pesan ?>

            <!-- Stats Row -->
            <div class="stats-row">
                <?php 
                $total_laporan = mysqli_num_rows($laporan);
                mysqli_data_seek($laporan, 0); // Reset pointer
                
                $belum_validasi = 0;
                $sudah_validasi = 0;
                while($l = mysqli_fetch_assoc($laporan)) {
                    if($l['validasi'] == 'belum') $belum_validasi++;
                    else $sudah_validasi++;
                }
                mysqli_data_seek($laporan, 0); // Reset pointer again
                ?>
                
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $total_laporan ?></div>
                    <div class="stat-mini-label">Total Laporan</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $sudah_validasi ?></div>
                    <div class="stat-mini-label">Sudah Validasi</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $belum_validasi ?></div>
                    <div class="stat-mini-label">Belum Validasi</div>
                </div>
            </div>

            <!-- Form Input (Only for User) -->
            <?php if ($role == 'user'): ?>
            <div class="form-card">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Tambah Laporan Kegiatan
                </h3>
                <form method="post" id="reportForm">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tanggal Kegiatan</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Deskripsi Kegiatan</label>
                            <textarea name="kegiatan" class="form-control" rows="3" placeholder="Jelaskan kegiatan yang telah dilakukan..." required></textarea>
                        </div>
                    </div>
                    <button name="isi" type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Laporan
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Table Card -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Daftar Laporan Kegiatan
                    </h3>
                    <span class="badge bg-light text-dark"><?= $total_laporan ?> laporan</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <?php if ($role != 'user'): ?>
                                <th>Nama Peserta</th>
                                <?php endif; ?>
                                <th>Tanggal</th>
                                <th>Kegiatan</th>
                                <th>Status</th>
                                <?php if($role == 'pembimbing' || $role == 'admin' || $role == 'user'): ?>
                                <th>Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($l = mysqli_fetch_assoc($laporan)): 
                            ?>
                            <tr>
                                <td><strong><?= $no++ ?></strong></td>
                                <?php if ($role != 'user'): ?>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="fas fa-user text-muted"></i>
                                        </div>
                                        <?= htmlspecialchars($l['nama']) ?>
                                    </div>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <span class="fw-semibold"><?= date('d/m/Y', strtotime($l['tanggal'])) ?></span><br>
                                    <small class="text-muted"><?= date('l', strtotime($l['tanggal'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($l['kegiatan']) ?></td>
                                <td>
                                    <?php if ($l['validasi'] == 'belum'): ?>
                                        <span class="status-badge status-belum">
                                            <i class="fas fa-clock"></i> Belum Validasi
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-valid">
                                            <i class="fas fa-check-circle"></i> Valid
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($role == 'pembimbing' && $l['validasi'] == 'belum'): ?>
                                            <a class="btn btn-validate" href="?validasi=<?= $l['id'] ?>" onclick="return confirmValidation(<?= $l['id'] ?>)">
                                                <i class="fas fa-check"></i> Validasi
                                            </a>
                                        <?php elseif ($role == 'pembimbing' && $l['validasi'] == 'valid'): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i> Tervalidasi
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($role == 'user' && $l['validasi'] == 'belum'): ?>
                                            <a class="btn btn-delete" href="?hapus=<?= $l['id'] ?>" onclick="return confirmDelete(<?= $l['id'] ?>)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($role == 'admin'): ?>
                                            <a class="btn btn-delete" href="?hapus=<?= $l['id'] ?>" onclick="return confirmDelete(<?= $l['id'] ?>)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($total_laporan == 0): ?>
                            <tr>
                                <td colspan="<?= $role == 'user' ? '5' : '6' ?>" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <h5>Belum ada laporan</h5>
                                        <p>
                                            <?php if ($role == 'user'): ?>
                                                Mulai buat laporan kegiatan harian Anda dengan mengisi form di atas.
                                            <?php else: ?>
                                                Belum ada laporan kegiatan yang tersedia.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Confirmation for validation
        function confirmValidation(id) {
            return confirm('Apakah Anda yakin ingin memvalidasi laporan ini?');
        }
        
        // Confirmation for deletion
        function confirmDelete(id) {
            return confirm('Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.');
        }
        
        // Form validation
        document.getElementById('reportForm')?.addEventListener('submit', function(e) {
            const tanggal = this.querySelector('input[name="tanggal"]').value;
            const kegiatan = this.querySelector('textarea[name="kegiatan"]').value.trim();
            
            if (!tanggal) {
                alert('Tanggal kegiatan harus diisi!');
                e.preventDefault();
                return false;
            }
            
            if (!kegiatan) {
                alert('Deskripsi kegiatan harus diisi!');
                e.preventDefault();
                return false;
            }
            
            if (kegiatan.length < 10) {
                alert('Deskripsi kegiatan minimal 10 karakter!');
                e.preventDefault();
                return false;
            }
            
            // Check if date is not in future
            const selectedDate = new Date(tanggal);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate > today) {
                alert('Tanggal kegiatan tidak boleh lebih dari hari ini!');
                e.preventDefault();
                return false;
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
        
        // Add animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(function(row, index) {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'all 0.3s ease';
                
                setTimeout(function() {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Add smooth scrolling to form after submission
        <?php if ($role == 'user' && $pesan): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reportForm');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        <?php endif; ?>
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to submit form (for textarea)
            if (e.ctrlKey && e.key === 'Enter') {
                const form = document.getElementById('reportForm');
                if (form) {
                    form.submit();
                }
            }
        });
        
        // Auto-resize textarea
        const textarea = document.querySelector('textarea[name="kegiatan"]');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
        
        // Character counter for textarea
        if (textarea) {
            const maxLength = 500;
            const counterDiv = document.createElement('div');
            counterDiv.className = 'text-muted mt-1';
            counterDiv.style.fontSize = '0.875rem';
            textarea.parentNode.appendChild(counterDiv);
            
            function updateCounter() {
                const remaining = maxLength - textarea.value.length;
                counterDiv.textContent = `${textarea.value.length}/${maxLength} karakter`;
                counterDiv.style.color = remaining < 50 ? '#dc2626' : '#6b7280';
            }
            
            textarea.addEventListener('input', updateCounter);
            textarea.setAttribute('maxlength', maxLength);
            updateCounter();
        }
    </script>
</body>
</html>
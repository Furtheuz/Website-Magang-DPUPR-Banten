<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];
$userName = $user['nama'] ?? 'User';
$pesan = '';

// Ambil data peserta untuk user (foto profil)
$peserta_data = null;
if ($role === 'user') {
    $stmt = $conn->prepare("SELECT p.*, i.nama AS institusi_nama FROM peserta p LEFT JOIN institusi i ON p.institusi_id = i.id WHERE p.user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $peserta_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Role-based styling
$roleColors = [
    'admin' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'pembimbing' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8'],
    'user' => ['primary' => '#2563eb', 'secondary' => '#eff6ff', 'accent' => '#1d4ed8']
];
$roleIcons = ['admin' => '👑', 'pembimbing' => '👨‍🏫', 'user' => ''];

// Tentukan tab aktif
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['jadwal', 'laporan']) ? $_GET['tab'] : 'jadwal';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_jadwal']) && $role == 'pembimbing') {
        $peserta_id = (int)($_POST['peserta_id'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? '';
        $tugas = trim($_POST['tugas'] ?? '');
        $minggu = (int)($_POST['minggu'] ?? 0);

        if ($peserta_id && $tanggal && $tugas && $minggu) {
            $check_stmt = $conn->prepare("SELECT id FROM pasangan WHERE peserta_id = ? AND pembimbing_id = ? AND status = 'accepted'");
            $check_stmt->bind_param("ii", $peserta_id, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO jadwal (peserta_id, tanggal, tugas, pembimbing_id, minggu) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssi", $peserta_id, $tanggal, $tugas, $user_id, $minggu);
                $pesan = $stmt->execute() 
                    ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal berhasil ditambahkan!</div>'
                    : '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan jadwal!</div>';
                $stmt->close();
            } else {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Izin ditolak untuk peserta ini!</div>';
            }
            $check_stmt->close();
        } else {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Data jadwal tidak lengkap!</div>';
        }
    }

    if (isset($_POST['hapus_jadwal']) && $role == 'pembimbing') {
        $jadwal_id = (int)($_POST['jadwal_id'] ?? 0);
        if ($jadwal_id > 0) {
            $check_stmt = $conn->prepare("SELECT id FROM jadwal WHERE id = ? AND pembimbing_id = ?");
            $check_stmt->bind_param("ii", $jadwal_id, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM jadwal WHERE id = ?");
                $stmt->bind_param("i", $jadwal_id);
                $pesan = $stmt->execute() && $stmt->affected_rows > 0
                    ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Jadwal dihapus!</div>'
                    : '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menghapus jadwal!</div>';
                $stmt->close();
            } else {
                $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Izin ditolak untuk menghapus!</div>';
            }
            $check_stmt->close();
        }
        header("Location: schedule_report.php?tab=jadwal");
        exit();
    }

    if (isset($_POST['hapus_pasangan']) && $role == 'admin') {
        $pasangan_id = (int)($_POST['pasangan_id'] ?? 0);
        if ($pasangan_id > 0) {
            $stmt = $conn->prepare("DELETE FROM pasangan WHERE id = ?");
            $stmt->bind_param("i", $pasangan_id);
            $pesan = $stmt->execute() && $stmt->affected_rows > 0
                ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pasangan dihapus!</div>'
                : '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menghapus pasangan!</div>';
            $stmt->close();
        }
        header("Location: schedule_report.php?tab=jadwal");
        exit();
    }

    if (isset($_POST['tambah_pasangan']) && $role == 'admin') {
        $peserta_id = (int)($_POST['peserta_id'] ?? 0);
        $pembimbing_id = (int)($_POST['pembimbing_id'] ?? 0);
        if ($peserta_id && $pembimbing_id) {
            $stmt = $conn->prepare("INSERT INTO pasangan (peserta_id, pembimbing_id, status) VALUES (?, ?, 'accepted')");
            $stmt->bind_param("ii", $peserta_id, $pembimbing_id);
            $pesan = $stmt->execute()
                ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Pasangan berhasil ditambahkan!</div>'
                : '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menambahkan pasangan!</div>';
            $stmt->close();
        } else {
            $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Data pasangan tidak lengkap!</div>';
        }
    }

    

    if ($role == 'user' && isset($_POST['isi'])) {
        $tanggal = $_POST['tanggal'] ?? '';
        $kegiatan = $_POST['kegiatan'] ?? '';
        $q = mysqli_query($conn, "SELECT id FROM peserta WHERE user_id = $user_id LIMIT 1");
        $peserta = mysqli_fetch_assoc($q);
        if ($peserta && $tanggal && $kegiatan) {
            $peserta_id = $peserta['id'];
            mysqli_query($conn, "INSERT INTO laporan (peserta_id, tanggal, kegiatan) VALUES ('$peserta_id', '$tanggal', '$kegiatan')");
            $pesan = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Laporan disimpan.</div>";
        } else {
            $pesan = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Gagal menyimpan laporan.</div>";
        }
    }
}

if (isset($_GET['validasi']) && $role == 'pembimbing') {
    $id = (int)$_GET['validasi'];
    mysqli_query($conn, "UPDATE laporan SET validasi='valid' WHERE id=$id");
    header("Location: schedule_report.php?tab=laporan");
    exit();
}

if (isset($_GET['hapus']) && ($role == 'user' || $role == 'admin')) {
    $id = (int)$_GET['hapus'];
    $condition = $role == 'user' 
        ? "WHERE id=$id AND peserta_id IN (SELECT id FROM peserta WHERE user_id=$user_id) AND validasi='belum'"
        : "WHERE id=$id";
    mysqli_query($conn, "DELETE FROM laporan $condition");
    header("Location: schedule_report.php?tab=laporan");
    exit();
}

// Query data berdasarkan tab
if ($activeTab == 'jadwal') {
    if ($role == 'admin') {
        $peserta = mysqli_query($conn, "SELECT * FROM peserta ORDER BY nama");
        $pembimbing = mysqli_query($conn, "SELECT * FROM users WHERE role = 'pembimbing' ORDER BY nama");
        $pasangan = mysqli_query($conn, "SELECT pa.id, p.nama AS peserta_nama, p.bidang, u.nama AS pembimbing_nama FROM pasangan pa JOIN peserta p ON pa.peserta_id = p.id JOIN users u ON pa.pembimbing_id = u.id WHERE pa.status = 'accepted' ORDER BY p.nama");
    
    } elseif ($role == 'pembimbing') {
        $peserta = mysqli_query($conn, "SELECT p.* FROM pasangan pa JOIN peserta p ON pa.peserta_id = p.id WHERE pa.pembimbing_id = '$user_id' AND pa.status = 'accepted' ORDER BY p.nama");
        $jadwal = mysqli_query($conn, "SELECT j.*, p.nama AS peserta FROM jadwal j JOIN peserta p ON j.peserta_id = p.id WHERE j.pembimbing_id = '$user_id' ORDER BY j.minggu, j.tanggal");
    } else {
        $peserta_id = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM peserta WHERE user_id = '$user_id'"))['id'] ?? 0;
        $jadwal = $peserta_id 
            ? mysqli_query($conn, "SELECT j.*, u.nama AS pembimbing FROM jadwal j JOIN users u ON j.pembimbing_id = u.id WHERE j.peserta_id = '$peserta_id' ORDER BY j.minggu, j.tanggal")
            : false;
    }
} elseif ($activeTab == 'laporan') {
    if ($role == 'user') {
        $laporan_query = "SELECT l.*, p.nama FROM laporan l JOIN peserta p ON l.peserta_id = p.id WHERE p.user_id = $user_id ORDER BY l.tanggal DESC";
    } elseif ($role == 'pembimbing') {
        $laporan_query = "SELECT l.*, u.nama 
                         FROM laporan l 
                         JOIN peserta p ON l.peserta_id = p.id 
                         JOIN users u ON p.user_id = u.id 
                         JOIN pasangan pa ON p.id = pa.peserta_id 
                         WHERE pa.pembimbing_id = $user_id AND pa.status = 'accepted' 
                         ORDER BY l.tanggal DESC";
    } else { // admin
        $laporan_query = "SELECT l.*, u.nama 
                         FROM laporan l 
                         JOIN peserta p ON l.peserta_id = p.id 
                         JOIN users u ON p.user_id = u.id 
                         ORDER BY l.tanggal DESC";
    }
    $laporan = mysqli_query($conn, $laporan_query);
    $total_laporan = mysqli_num_rows($laporan);
    $belum_validasi = $sudah_validasi = 0;
    while ($l = mysqli_fetch_assoc($laporan)) {
        if ($l['validasi'] == 'belum') $belum_validasi++;
        else $sudah_validasi++;
    }
    mysqli_data_seek($laporan, 0);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal & Laporan - <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: <?= $roleColors[$role]['primary'] ?>; --secondary: <?= $roleColors[$role]['secondary'] ?>; --accent: <?= $roleColors[$role]['accent'] ?>; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, var(--secondary), #fff); min-height: 100vh; color: #1f2937; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: linear-gradient(180deg, var(--primary), var(--accent)); color: white; padding: 0; box-shadow: 4px 0 20px rgba(0,0,0,0.1); position: relative; }
        .sidebar::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>'); pointer-events: none; }
        .user-profile { padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); position: relative; z-index: 1; }
        .user-avatar { width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; backdrop-filter: blur(10px); border: 3px solid rgba(255,255,255,0.3); }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .user-name { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; }
       .user-role {
            font-size: 0.875rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: inline-block;
        }
        .nav-menu { padding: 1rem 0; position: relative; z-index: 1; }
        .nav-item { margin: 0.25rem 1rem; }
        .nav-link { color: rgba(255,255,255,0.9) !important; text-decoration: none; padding: 0.875rem 1.25rem; border-radius: 12px; display: flex; align-items: center; font-weight: 500; transition: all 0.3s ease; }
        .nav-link:hover { background: rgba(255,255,255,0.15); color: white !important; transform: translateX(5px); }
        .nav-link.active { background: rgba(255,255,255,0.2); color: white !important; }
        .nav-link i { width: 20px; margin-right: 0.75rem; text-align: center; }
        .logout-link { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; }
        .logout-link .nav-link { color: #fecaca !important; background: rgba(239, 68, 68, 0.1); }
        .logout-link .nav-link:hover { background: rgba(239, 68, 68, 0.2); color: white !important; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { background: white; padding: 1.5rem 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 2rem; border: 1px solid rgba(0,0,0,0.05); }
        .header h1 { font-weight: 700; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.75rem; }
        .tab-buttons { margin-bottom: 2rem; }
        .tab-btn { background: none; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; color: #6b7280; cursor: pointer; transition: all 0.3s ease; }
        .tab-btn.active { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(var(--primary), 0.3); }
        .tab-btn:hover:not(.active) { background: #e5e7eb; color: #1f2937; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card-header { background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1.5rem; border: none; font-weight: 600; display: flex; align-items: center; gap: 0.75rem; }
        .card-body { padding: 2rem; }
        .form-control { border: 2px solid #e5e7eb; border-radius: 12px; padding: 0.75rem 1rem; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(var(--primary), 0.1); }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(var(--primary), 0.3); }
        .btn-success, .btn-danger { padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3); }
        .table-responsive { border-radius: 12px; overflow: hidden; }
        .table { margin: 0; font-size: 0.95rem; width: 100%; }
        .table th { background: var(--primary); color: white; font-weight: 600; padding: 1rem; border: none; text-align: center; }
        .table td { padding: 1rem; vertical-align: middle; border-color: #f3f4f6; text-align: center; }
        .table tbody tr:hover { background-color: var(--secondary); }
        .empty-state { text-align: center; padding: 3rem; color: #6b7280; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
        .alert { border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .stats-row { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .stat-mini { flex: 1; background: white; padding: 1rem; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-mini-value { font-size: 1.5rem; font-weight: 600; color: var(--primary); }
        .stat-mini-label { font-size: 0.875rem; color: #6b7280; }
        @media (max-width: 768px) { .sidebar { width: 100%; } .nav-menu { display: flex; overflow-x: auto; } .nav-item { flex-shrink: 0; margin: 0 0.25rem; } .main-content { padding: 1rem; } .tab-btn { padding: 0.5rem 1rem; font-size: 0.9rem; } }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if ($role === 'user' && $peserta_data && !empty($peserta_data['foto'])): ?>
                        <img src="<?= htmlspecialchars($peserta_data['foto']) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?= $roleIcons[$role] ?>
                    <?php endif; ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= ucfirst($role) ?></div>
            </div>
            <div class="nav-menu">
                <?php
                $navItems = [
                    'admin' => [
                        ['dashboard.php', 'fas fa-home', 'Dashboard'],
                        ['peserta.php', 'fas fa-users', 'Data Peserta'],
                        ['schedule_report.php?tab=jadwal', 'fas fa-calendar-alt', 'Jadwal & Laporan'],
                        ['idcard.php', 'fas fa-id-card', 'Cetak ID Card'],
                        ['profile.php', 'fas fa-user', 'Profil']
                    ],
                    'pembimbing' => [
                        ['dashboard.php', 'fas fa-home', 'Dashboard'],
                        ['schedule_report.php?tab=jadwal', 'fas fa-calendar-check', 'Jadwal & Laporan'],
                        ['profile.php', 'fas fa-user', 'Profil']
                    ],
                    'user' => [
                        ['dashboard.php', 'fas fa-home', 'Dashboard'],
                        ['schedule_report.php?tab=jadwal', 'fas fa-calendar', 'Jadwal & Laporan'],
                        ['profile.php', 'fas fa-user', 'Profil']
                    ]
                ];
                foreach ($navItems[$role] as $item) {
                    list($href, $icon, $title) = $item;
                    $active = strpos($href, $activeTab) !== false ? 'active' : '';
                    echo "<div class='nav-item'><a class='nav-link $active' href='$href'><i class='$icon'></i> $title</a></div>";
                }
                ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-calendar-alt"></i> Jadwal & Laporan</h1>
            </div>

            <?= $pesan ?>

            <!-- Tab Buttons -->
            <div class="tab-buttons">
                <button class="tab-btn <?= $activeTab == 'jadwal' ? 'active' : '' ?>" onclick="location.href='?tab=jadwal'">Jadwal</button>
                <button class="tab-btn <?= $activeTab == 'laporan' ? 'active' : '' ?>" onclick="location.href='?tab=laporan'">Laporan</button>
            </div>

            <!-- Jadwal Content -->
            <?php if ($activeTab == 'jadwal'): ?>
                <?php if ($role == 'admin'): ?>
                    <div class="card">
                        <div class="card-header"><i class="fas fa-plus-circle"></i> Tambah Pasangan</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Peserta</label>
                                        <select name="peserta_id" class="form-control" required>
                                            <option value="">Pilih Peserta</option>
                                            <?php while ($p = mysqli_fetch_assoc($peserta)): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Pembimbing</label>
                                        <select name="pembimbing_id" class="form-control" required>
                                            <option value="">Pilih Pembimbing</option>
                                            <?php while ($u = mysqli_fetch_assoc($pembimbing)): ?>
                                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="tambah_pasangan" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><i class="fas fa-users"></i> Daftar Pasangan</div>
                        <div class="card-body">
                            <?php if (!$pasangan || mysqli_num_rows($pasangan) == 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h5>Tidak Ada Pasangan</h5>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Peserta</th>
                                                <th>Bidang</th>
                                                <th>Pembimbing</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; while ($p = mysqli_fetch_assoc($pasangan)): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= htmlspecialchars($p['peserta_nama']) ?></td>
                                                    <td><?= htmlspecialchars($p['bidang']) ?></td>
                                                    <td><?= htmlspecialchars($p['pembimbing_nama']) ?></td>
                                                    <td>
                                                        <button class="btn btn-danger btn-sm" onclick="hapusPasangan(<?= $p['id'] ?>, '<?= addslashes($p['peserta_nama']) ?>', '<?= addslashes($p['pembimbing_nama']) ?>')"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="card">
                        <div class="card-header"><i class="fas fa-plus-circle"></i> Buat Jadwal</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Peserta</label>
                                        <select name="peserta_id" class="form-control" required>
                                            <option value="">Pilih Peserta</option>
                                            <?php while ($p = mysqli_fetch_assoc($peserta)): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">Tanggal</label>
                                        <input type="date" name="tanggal" class="form-control" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label fw-semibold">Minggu</label>
                                        <select name="minggu" class="form-control" required>
                                            <option value="">Pilih</option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?= $i ?>">Minggu <?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-semibold">Tugas/Kegiatan</label>
                                        <input type="text" name="tugas" class="form-control" placeholder="Masukkan tugas" required>
                                    </div>
                                </div>
                                <button type="submit" name="tambah_jadwal" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><i class="fas fa-calendar-check"></i> Daftar Jadwal</div>
                        <div class="card-body">
                            <?php if (!$jadwal || mysqli_num_rows($jadwal) == 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h5>Tidak Ada Jadwal</h5>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Peserta</th>
                                                <th>Minggu</th>
                                                <th>Tugas</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= date('d/m/Y', strtotime($j['tanggal'])) ?></td>
                                                    <td><?= htmlspecialchars($j['peserta']) ?></td>
                                                    <td>Minggu <?= $j['minggu'] ?></td>
                                                    <td><?= htmlspecialchars($j['tugas']) ?></td>
                                                    <td>
                                                        <button class="btn btn-danger btn-sm" onclick="hapusJadwal(<?= $j['id'] ?>, '<?= addslashes($j['peserta']) ?>', '<?= $j['tanggal'] ?>')"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($role == 'user'): ?>
                    <div class="card">
                        <div class="card-header"><i class="fas fa-calendar-check"></i> Daftar Jadwal</div>
                        <div class="card-body">
                            <?php if (!$jadwal || mysqli_num_rows($jadwal) == 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h5>Tidak Ada Jadwal</h5>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Pembimbing</th>
                                                <th>Minggu</th>
                                                <th>Tugas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= date('d/m/Y', strtotime($j['tanggal'])) ?></td>
                                                    <td><?= htmlspecialchars($j['pembimbing']) ?></td>
                                                    <td>Minggu <?= $j['minggu'] ?></td>
                                                    <td><?= htmlspecialchars($j['tugas']) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <!-- Laporan Content -->
            <?php elseif ($activeTab == 'laporan'): ?>
                <div class="stats-row">
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?= number_format($total_laporan) ?></div>
                        <div class="stat-mini-label">Total Laporan</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?= number_format($sudah_validasi) ?></div>
                        <div class="stat-mini-label">Sudah Validasi</div>
                    </div>
                    <div class="stat-mini">
                        <div class="stat-mini-value"><?= number_format($belum_validasi) ?></div>
                        <div class="stat-mini-label">Belum Validasi</div>
                    </div>
                </div>

                <?php if ($role == 'user'): ?>
                    <div class="card">
                        <div class="card-header"><i class="fas fa-plus-circle"></i> Tambah Laporan</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">Tanggal</label>
                                        <input type="date" name="tanggal" class="form-control" required>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label fw-semibold">Kegiatan</label>
                                        <textarea name="kegiatan" class="form-control" rows="3" placeholder="Deskripsi kegiatan" required></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="isi" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"><i class="fas fa-list"></i> Daftar Laporan <span class="badge bg-light text-dark"><?= number_format($total_laporan) ?> Laporan</span></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <?php if ($role != 'user'): ?><th>Peserta</th><?php endif; ?>
                                        <th>Tanggal</th>
                                        <th>Kegiatan</th>
                                        <th>Status</th>
                                        <?php if ($role == 'pembimbing' || $role == 'admin' || $role == 'user'): ?><th>Aksi</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; while ($l = mysqli_fetch_assoc($laporan)): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <?php if ($role != 'user'): ?>
                                                <td><?= htmlspecialchars($l['nama']) ?></td>
                                            <?php endif; ?>
                                            <td><?= date('d/m/Y', strtotime($l['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($l['kegiatan']) ?></td>
                                            <td>
                                                <span class="badge <?= $l['validasi'] == 'belum' ? 'bg-warning' : 'bg-success' ?>">
                                                    <?= $l['validasi'] == 'belum' ? 'Belum Validasi' : 'Valid' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($role == 'pembimbing' && $l['validasi'] == 'belum'): ?>
                                                    <a href="?validasi=<?= $l['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Validasi laporan?')"><i class="fas fa-check"></i></a>
                                                <?php elseif ($role == 'user' && $l['validasi'] == 'belum'): ?>
                                                    <a href="?hapus=<?= $l['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus laporan?')"><i class="fas fa-trash"></i></a>
                                                <?php elseif ($role == 'admin'): ?>
                                                    <a href="?hapus=<?= $l['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus laporan?')"><i class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($total_laporan == 0): ?>
                                        <tr><td colspan="<?= $role == 'user' ? 5 : 6 ?>" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><h5>Tidak Ada Laporan</h5></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Hapus Jadwal -->
    <?php if ($role == 'pembimbing'): ?>
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="jadwal_id" id="delete_jadwal_id">
                        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Hapus jadwal ini?</div>
                        <p class="text-center"><strong>Peserta:</strong> <span id="delete_peserta"></span></p>
                        <p class="text-center"><strong>Tanggal:</strong> <span id="delete_tanggal"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus_jadwal" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Hapus Pasangan -->
    <?php if ($role == 'admin'): ?>
    <div class="modal fade" id="deletePasanganModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Konfirmasi Hapus Pasangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="pasangan_id" id="delete_pasangan_id">
                        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Hapus pasangan ini?</div>
                        <p class="text-center"><strong>Peserta:</strong> <span id="delete_pasangan_peserta"></span></p>
                        <p class="text-center"><strong>Pembimbing:</strong> <span id="delete_pasangan_pembimbing"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus_pasangan" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => document.querySelectorAll('.alert').forEach(alert => (alert.style.opacity = '0', setTimeout(() => alert.remove(), 500))), 5000);
        function hapusJadwal(id, peserta, tanggal) {
            document.getElementById('delete_jadwal_id').value = id;
            document.getElementById('delete_peserta').textContent = peserta;
            document.getElementById('delete_tanggal').textContent = new Date(tanggal).toLocaleDateString('id-ID');
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        function hapusPasangan(id, peserta, pembimbing) {
            document.getElementById('delete_pasangan_id').value = id;
            document.getElementById('delete_pasangan_peserta').textContent = peserta;
            document.getElementById('delete_pasangan_pembimbing').textContent = pembimbing;
            new bootstrap.Modal(document.getElementById('deletePasanganModal')).show();
        }
    </script>
</body>
</html>
<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$role = $_SESSION['user']['role'];
$userName = $_SESSION['user']['nama'] ?? 'User';
$pesan = '';

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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_peserta']) && $role == 'admin') {
    $peserta_id = (int)($_POST['peserta_id'] ?? 0);
    if ($peserta_id > 0) {
        $stmt = $conn->prepare("DELETE FROM peserta WHERE id = ?");
        $stmt->bind_param("i", $peserta_id);
        $pesan = $stmt->execute() && $stmt->affected_rows > 0
            ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Peserta berhasil dihapus!</div>'
            : '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Gagal menghapus peserta!</div>';
        $stmt->close();
    }
}

// Query untuk mendapatkan data peserta
$peserta = mysqli_query($conn, "SELECT p.* FROM peserta p JOIN users u ON p.user_id=u.id ORDER BY p.nama ASC");

// Check if query was successful before using mysqli_num_rows
if ($peserta) {
    $totalPeserta = mysqli_num_rows($peserta);
} else {
    $totalPeserta = 0;
    $pesan = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Error in peserta query: " . mysqli_error($conn) . "</div>";
}

// Query untuk statistik with error checking
$pesertaAktifQuery = mysqli_query($conn, "SELECT * FROM peserta WHERE status='aktif'");
$pesertaAktif = $pesertaAktifQuery ? mysqli_num_rows($pesertaAktifQuery) : 0;

$pesertaSelesaiQuery = mysqli_query($conn, "SELECT * FROM peserta WHERE status='selesai'");
$pesertaSelesai = $pesertaSelesaiQuery ? mysqli_num_rows($pesertaSelesaiQuery) : 0;

$riwayatCetakQuery = mysqli_query($conn, "SELECT * FROM riwayat_cetak_idcard");
$riwayatCetak = $riwayatCetakQuery ? mysqli_num_rows($riwayatCetakQuery) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Peserta - <?= ucfirst($role) ?></title>
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
        
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .search-input::placeholder {
            color: rgba(255,255,255,0.8);
        }
        
        .table {
            margin: 0;
            width: 100%;
        }
        
        .table thead th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            padding: 1rem;
            border: none;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            text-align: center;
        }
        
        .table tbody td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
            text-align: center;
        }
        
        .table tbody tr:hover {
            background: var(--secondary-color);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-aktif {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-selesai {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-nonaktif {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-group {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }
        
        .pagination-wrapper {
            padding: 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }
        
        .alert {
            border-radius: 12px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                overflow-x: auto;
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
                    $active = strpos($href, 'idcard.php') !== false ? 'active' : '';
                    echo "<div class='nav-item'><a class='nav-link $active' href='$href'><i class='$icon'></i> $title</a></div>";
                }
                ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-id-card"></i> ID Card Peserta</h1>
                <p class="subtitle">Kelola dan cetak ID Card untuk semua peserta dengan mudah</p>
            </div>

            <?= $pesan ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-title">Total Peserta</div>
                    <div class="stat-value"><?= $totalPeserta ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                    <div class="stat-title">Peserta Aktif</div>
                    <div class="stat-value"><?= $pesertaAktif ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-title">Peserta Selesai</div>
                    <div class="stat-value"><?= $pesertaSelesai ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-print"></i></div>
                    <div class="stat-title">Riwayat Cetak</div>
                    <div class="stat-value"><?= $riwayatCetak ?></div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Daftar Peserta</h3>
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Cari peserta..." id="searchInput">
                        <button class="btn btn-light btn-sm" onclick="searchPeserta()"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleAllCheckbox()"></th>
                                <th>Nama Peserta</th>
                                <th>Status</th>
                                <th>Terakhir Dicetak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pesertaTable">
                            <?php 
                            if ($peserta && mysqli_num_rows($peserta) > 0) {
                                mysqli_data_seek($peserta, 0);
                                while ($p = mysqli_fetch_assoc($peserta)):
                                    $lastPrintQuery = mysqli_query($conn, "SELECT created_at FROM riwayat_cetak_idcard WHERE peserta_id = '" . $p['id'] . "' ORDER BY created_at DESC LIMIT 1");
                                    $lastPrintData = $lastPrintQuery ? mysqli_fetch_assoc($lastPrintQuery) : null;
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_peserta[]" value="<?= $p['id'] ?>" class="peserta-checkbox"></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3" style="width: 40px; height: 40px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                            <?= strtoupper(substr($p['nama'], 0, 1)) ?>
                                        </div>
                                        <div class="fw-semibold"><?= htmlspecialchars($p['nama']) ?></div>
                                    </div>
                                </td>
                                <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                                <td>
                                    <?php if ($lastPrintData && isset($lastPrintData['created_at'])): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($lastPrintData['created_at'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Belum pernah</small>
                                    <?php endif; ?>
                                </td>
                                <td class="action-group">
                                    <?php if ($role == 'admin'): ?>
                                        <button class="btn btn-danger btn-sm" onclick="hapusPeserta(<?= $p['id'] ?>, '<?= addslashes($p['nama']) ?>')"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php } else { ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><h5>Tidak ada data peserta</h5></td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination-wrapper">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="cetakTerpilih()" id="btnCetakTerpilih" disabled>
                                <i class="fas fa-print"></i> Cetak Terpilih (<span id="jumlahTerpilih">0</span>)
                            </button>
                            <a href="riwayat_cetak.php" class="btn btn-warning">
                                <i class="fas fa-history"></i> Riwayat Cetak
                            </a>
                        </div>
                        <div>
                            <small class="text-muted">Total: <?= $totalPeserta ?> peserta</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Hapus Peserta -->
    <?php if ($role == 'admin'): ?>
    <div class="modal fade" id="deletePesertaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="peserta_id" id="delete_peserta_id">
                        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Hapus peserta ini?</div>
                        <p class="text-center"><strong>Nama:</strong> <span id="delete_peserta_nama"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus_peserta" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        function searchPeserta() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('pesertaTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[1];
                if (nameCell) {
                    const textValue = nameCell.textContent || nameCell.innerText;
                    rows[i].style.display = textValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
                }
            }
        }

        // Real-time search
        document.getElementById('searchInput').addEventListener('keyup', searchPeserta);

        // Checkbox functionality
        function toggleAllCheckbox() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.peserta-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = selectAll.checked);
            updateCetakButton();
        }

        function updateCetakButton() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            const btnCetak = document.getElementById('btnCetakTerpilih');
            const jumlahSpan = document.getElementById('jumlahTerpilih');
            jumlahSpan.textContent = checkboxes.length;
            btnCetak.disabled = checkboxes.length === 0;
            btnCetak.classList.toggle('btn-primary', checkboxes.length > 0);
            btnCetak.classList.toggle('btn-secondary', checkboxes.length === 0);
        }

        // Add event listeners to all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox');
            checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateCetakButton));
            updateCetakButton();
        });

        // Cetak terpilih function
        function cetakTerpilih() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu peserta untuk dicetak!');
                return;
            }
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            window.open('cetak_massal.php?ids=' + selectedIds.join(','), '_blank');
            fetch('log_cetak.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ peserta_ids: selectedIds, jenis_cetak: 'massal' })
            }).then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      alert('Riwayat cetak berhasil disimpan!');
                      location.reload(); // Refresh untuk update statistik
                  } else {
                      alert('Gagal menyimpan riwayat cetak: ' + data.message);
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  alert('Terjadi kesalahan saat menyimpan riwayat cetak');
              });
        }

        // Delete peserta function
        function hapusPeserta(id, nama) {
            document.getElementById('delete_peserta_id').value = id;
            document.getElementById('delete_peserta_nama').textContent = nama;
            new bootstrap.Modal(document.getElementById('deletePesertaModal')).show();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                document.getElementById('selectAll').checked = true;
                toggleAllCheckbox();
            }
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                cetakTerpilih();
            }
            if (e.key === 'Escape') {
                document.getElementById('selectAll').checked = false;
                toggleAllCheckbox();
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
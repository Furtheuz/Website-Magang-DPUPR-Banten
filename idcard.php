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

// Query untuk mendapatkan data peserta
$peserta = mysqli_query($conn, "SELECT p.*, u.nama as nama_user FROM peserta p JOIN users u ON p.user_id=u.id ORDER BY p.nama ASC");

// Check if query was successful before using mysqli_num_rows
if ($peserta) {
    $totalPeserta = mysqli_num_rows($peserta);
} else {
    $totalPeserta = 0;
    echo "Error in peserta query: " . mysqli_error($conn);
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
        
        .actions-section {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .actions-section h3 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn {
            background: var(--secondary-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem 0.5rem 0.25rem 0;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .action-btn.success {
            background: #f0fdf4;
            color: #059669;
            border-color: #059669;
        }
        
        .action-btn.success:hover {
            background: #059669;
            color: white;
        }
        
        .action-btn.warning {
            background: #fffbeb;
            color: #d97706;
            border-color: #d97706;
        }
        
        .action-btn.warning:hover {
            background: #d97706;
            color: white;
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
            justify-content: between;
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
        }
        
        .table tbody td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
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
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-color);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #059669;
            color: white;
        }
        
        .btn-success:hover {
            background: #047857;
            transform: translateY(-1px);
        }
        
        .btn-info {
            background: #0891b2;
            color: white;
        }
        
        .btn-info:hover {
            background: #0e7490;
            transform: translateY(-1px);
        }
        
        .pagination-wrapper {
            padding: 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
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
            
            .action-btn {
                width: 100%;
                margin: 0.25rem 0;
                justify-content: center;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (same as dashboard) -->
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
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <?php if ($role == 'admin'): ?>
                    <div class="nav-item">
                        <a class="nav-link" href="peserta.php">
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
                        <a class="nav-link active" href="idcard.php">
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
            <!-- Header -->
            <div class="header">
                <h1>
                    <i class="fas fa-id-card"></i>
                    ID Card Peserta
                </h1>
                <p class="subtitle">Kelola dan cetak ID Card untuk semua peserta dengan mudah</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Total Peserta</div>
                    <div class="stat-value"><?= $totalPeserta ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-title">Peserta Aktif</div>
                    <div class="stat-value"><?= $pesertaAktif ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-title">Peserta Selesai</div>
                    <div class="stat-value"><?= $pesertaSelesai ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="stat-title">Riwayat Cetak</div>
                    <div class="stat-value"><?= $riwayatCetak ?></div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="actions-section">
                <h3>
                    <i class="fas fa-tools"></i>
                    Aksi Cetak ID Card
                </h3>
                <div>
                    <a href="generate_qr.php" class="action-btn">
                        <i class="fas fa-qrcode"></i>
                        Generate QR Code
                    </a>
                    <a href="riwayat_cetak.php" class="action-btn warning">
                        <i class="fas fa-history"></i>
                        Riwayat Cetak
                    </a>
                    <a href="template_idcard.php" class="action-btn">
                        <i class="fas fa-palette"></i>
                        Template ID Card
                    </a>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Daftar Peserta
                    </h3>
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Cari peserta..." id="searchInput">
                        <button class="btn btn-light btn-sm" onclick="searchPeserta()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleAllCheckbox()">
                                </th>
                                <th>Nama Peserta</th>
                                <th>Status</th>
                                <th>QR Code</th>
                                <th>Terakhir Dicetak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                      <tbody id="pesertaTable">
                            <?php 
                            // Reset pointer and check if $peserta is valid
                            if ($peserta && mysqli_num_rows($peserta) > 0) {
                                mysqli_data_seek($peserta, 0); // Reset pointer
                                while($p = mysqli_fetch_assoc($peserta)): 
                                    // Check riwayat cetak with error handling
                                    $lastPrintQuery = mysqli_query($conn, "SELECT created_at FROM riwayat_cetak_idcard WHERE peserta_id = '".$p['id']."' ORDER BY created_at DESC LIMIT 1");
                                    $lastPrintData = null;
                                    if ($lastPrintQuery) {
                                        $lastPrintData = mysqli_fetch_assoc($lastPrintQuery);
                                    }
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_peserta[]" value="<?= $p['id'] ?>" class="peserta-checkbox">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3" style="width: 40px; height: 40px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                            <?= strtoupper(substr($p['nama'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($p['nama']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['nama_user'] ?? 'N/A') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $p['status'] ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if(file_exists("qr_codes/peserta_".$p['id'].".png")): ?>
                                        <i class="fas fa-check-circle text-success"></i> Tersedia
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger"></i> Belum Ada
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($lastPrintData && isset($lastPrintData['created_at'])): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($lastPrintData['created_at'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Belum pernah</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="print_idcard.php?id=<?= $p['id'] ?>" target="_blank" class="btn-sm btn-primary" title="Cetak Satuan">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="preview_idcard.php?id=<?= $p['id'] ?>" class="btn-sm btn-info" title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="generate_qr.php?id=<?= $p['id'] ?>" class="btn-sm btn-success" title="Generate QR">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                // No data or query failed
                                echo "<tr><td colspan='6' class='text-center text-muted'>Tidak ada data peserta atau terjadi kesalahan query</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination-wrapper">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button class="btn btn-primary" onclick="cetakTerpilih()" id="btnCetakTerpilih" disabled>
                                <i class="fas fa-print"></i>
                                Cetak Terpilih (<span id="jumlahTerpilih">0</span>)
                            </button>
                        </div>
                        <div>
                            <small class="text-muted">Total: <?= $totalPeserta ?> peserta</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

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
                    if (textValue.toUpperCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }

        // Real-time search
        document.getElementById('searchInput').addEventListener('keyup', searchPeserta);

        // Checkbox functionality
        function toggleAllCheckbox() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.peserta-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateCetakButton();
        }

        function updateCetakButton() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            const btnCetak = document.getElementById('btnCetakTerpilih');
            const jumlahSpan = document.getElementById('jumlahTerpilih');
            
            jumlahSpan.textContent = checkboxes.length;
            
            if (checkboxes.length > 0) {
                btnCetak.disabled = false;
                btnCetak.classList.remove('btn-secondary');
                btnCetak.classList.add('btn-primary');
            } else {
                btnCetak.disabled = true;
                btnCetak.classList.remove('btn-primary');
                btnCetak.classList.add('btn-secondary');
            }
        }

        // Add event listeners to all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCetakButton);
            });
        });

        // Cetak terpilih function
        function cetakTerpilih() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu peserta untuk dicetak!');
                return;
            }

            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            const url = 'cetak_massal.php?ids=' + selectedIds.join(',');
            window.open(url, '_blank');
            
            // Log riwayat cetak
            fetch('log_cetak.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    peserta_ids: selectedIds,
                    jenis_cetak: 'massal'
                })
            });
        }

        // Add active class to current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                }
            });
        });
        
        // Add smooth transitions
        document.querySelectorAll('.stat-card, .action-btn').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            element.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Preview ID Card function
        function previewIdCard(id) {
            window.open('preview_idcard.php?id=' + id, '_blank', 'width=800,height=600');
        }

        // Generate QR Code function
        function generateQR(id) {
            fetch('generate_qr.php?ajax=1&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal generate QR Code: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat generate QR Code');
                });
        }

        // Export functions
        function exportToExcel() {
            window.open('export_peserta.php?format=excel', '_blank');
        }

        function exportToPDF() {
            window.open('export_peserta.php?format=pdf', '_blank');
        }

        // Print statistics
        function printStatistics() {
            const printContent = `
                <div style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2>Statistik ID Card Peserta</h2>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;"><strong>Total Peserta</strong></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= $totalPeserta ?></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;"><strong>Peserta Aktif</strong></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= $pesertaAktif ?></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;"><strong>Peserta Selesai</strong></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= $pesertaSelesai ?></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;"><strong>Riwayat Cetak</strong></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= $riwayatCetak ?></td>
                        </tr>
                    </table>
                    <p style="margin-top: 20px; font-size: 12px; color: #666;">
                        Dicetak pada: ${new Date().toLocaleDateString('id-ID')}
                    </p>
                </div>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        // Bulk actions
        function bulkGenerateQR() {
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Pilih minimal satu peserta untuk generate QR Code!');
                return;
            }

            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (confirm(`Generate QR Code untuk ${selectedIds.length} peserta?`)) {
                fetch('generate_qr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ids: selectedIds,
                        bulk: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Berhasil generate QR Code untuk ${data.count} peserta`);
                        location.reload();
                    } else {
                        alert('Gagal generate QR Code: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat generate QR Code');
                });
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + A untuk select all
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                document.getElementById('selectAll').checked = true;
                toggleAllCheckbox();
            }
            
            // Ctrl + P untuk cetak terpilih
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                cetakTerpilih();
            }
            
            // Escape untuk clear selection
            if (e.key === 'Escape') {
                document.getElementById('selectAll').checked = false;
                toggleAllCheckbox();
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            // Only refresh if no checkboxes are selected
            const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
            if (checkboxes.length === 0) {
                location.reload();
            }
        }, 30000);
    </script>

    <!-- Additional Action Buttons (Floating) -->
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <div class="btn-group-vertical" role="group">
            <button type="button" class="btn btn-primary btn-sm" onclick="bulkGenerateQR()" title="Bulk Generate QR">
                <i class="fas fa-qrcode"></i>
            </button>
            <button type="button" class="btn btn-info btn-sm" onclick="exportToExcel()" title="Export ke Excel">
                <i class="fas fa-file-excel"></i>
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="exportToPDF()" title="Export ke PDF">
                <i class="fas fa-file-pdf"></i>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="printStatistics()" title="Print Statistik">
                <i class="fas fa-chart-bar"></i>
            </button>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast" role="alert">
            <div class="toast-header">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong class="me-auto">Berhasil</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Operasi berhasil dilakukan!
            </div>
        </div>
        
        <div id="errorToast" class="toast" role="alert">
            <div class="toast-header">
                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Terjadi kesalahan!
            </div>
        </div>
    </div>
</body>
</html>
<?php 
include "config/auth.php"; 
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= ucfirst($role) ?></title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .quick-actions h3 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
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
            margin: 0.25rem;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
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
                        <a class="nav-link" href="laporan.php">
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
                        <a class="nav-link" href="jadwal.php">
                            <i class="fas fa-calendar"></i>
                            Jadwal Saya
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="laporan.php">
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
                    <span style="font-size: 2rem;"><?= $roleIcons[$role] ?></span>
                    Dashboard <?= ucfirst($role) ?>
                </h1>
                <p class="subtitle">Selamat datang, <?= htmlspecialchars($userName) ?>! Mari kelola aktivitas Anda dengan efisien.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <?php if ($role == 'admin'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-title">Total Peserta</div>
                        <div class="stat-value">150</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-title">Institusi</div>
                        <div class="stat-value">25</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-title">Jadwal Aktif</div>
                        <div class="stat-value">12</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-title">Laporan Pending</div>
                        <div class="stat-value">8</div>
                    </div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-title">Peserta Bimbingan</div>
                        <div class="stat-value">15</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-title">Laporan Perlu Review</div>
                        <div class="stat-value">5</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-title">Jadwal Hari Ini</div>
                        <div class="stat-value">3</div>
                    </div>
                <?php elseif ($role == 'user'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-title">Jadwal Minggu Ini</div>
                        <div class="stat-value">4</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-title">Laporan Submitted</div>
                        <div class="stat-value">12</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-title">Tugas Pending</div>
                        <div class="stat-value">2</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Aksi Cepat</h3>
                <div>
                    <?php if ($role == 'admin'): ?>
                        <a href="peserta.php?action=add" class="action-btn">
                            <i class="fas fa-plus"></i>
                            Tambah Peserta
                        </a>
                        <a href="laporan.php?status=pending" class="action-btn">
                            <i class="fas fa-eye"></i>
                            Review Laporan
                        </a>
                        <a href="idcard.php" class="action-btn">
                            <i class="fas fa-print"></i>
                            Cetak ID Card
                        </a>
                    <?php elseif ($role == 'pembimbing'): ?>
                        <a href="jadwal.php?date=today" class="action-btn">
                            <i class="fas fa-calendar-day"></i>
                            Jadwal Hari Ini
                        </a>
                        <a href="laporan.php?action=review" class="action-btn">
                            <i class="fas fa-check"></i>
                            Review Laporan
                        </a>
                    <?php elseif ($role == 'user'): ?>
                        <a href="laporan.php?action=create" class="action-btn">
                            <i class="fas fa-plus"></i>
                            Buat Laporan
                        </a>
                        <a href="jadwal.php" class="action-btn">
                            <i class="fas fa-calendar"></i>
                            Lihat Jadwal
                        </a>
                        <a href="profile.php" class="action-btn">
                            <i class="fas fa-edit"></i>
                            Edit Profil
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>
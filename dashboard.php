<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

$role = $_SESSION['user']['role'];
$userName = $_SESSION['user']['nama'] ?? 'User';

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

// Get statistics with error handling
function fetchCount($conn, $query, $label) {
    $result = mysqli_query($conn, $query);
    if ($result === false) {
        die("Query failed ($label): " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result)['total'];
}

$total_peserta = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta", "Total Peserta");
$peserta_verified = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'verified'", "Peserta Verified");
$peserta_pending = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'pending'", "Peserta Pending");
$peserta_rejected = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status_verifikasi = 'rejected'", "Peserta Rejected");
$total_institusi = fetchCount($conn, "SELECT COUNT(*) as total FROM institusi", "Total Institusi");
$total_arsip = fetchCount($conn, "SELECT COUNT(*) as total FROM arsip", "Total Arsip");
$arsip_bulan_ini = fetchCount($conn, "SELECT COUNT(*) as total FROM arsip WHERE MONTH(tanggal_arsip) = MONTH(NOW()) AND YEAR(tanggal_arsip) = YEAR(NOW())", "Arsip Bulan Ini");
$peserta_aktif = fetchCount($conn, "SELECT COUNT(*) as total FROM peserta WHERE status='aktif'", "Peserta Aktif");
$peserta_alumni = fetchCount($conn, "SELECT COUNT(DISTINCT peserta_id) as total FROM arsip", "Peserta Alumni");

// User-specific data
if ($role == 'user') {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user']['id']);
    
    // Fetch last report date
    $last_report_query = mysqli_query($conn, "SELECT tanggal FROM laporan WHERE peserta_id = '$user_id' ORDER BY tanggal DESC LIMIT 1");
    if ($last_report_query === false) {
        $last_report_date = 'Error fetching report: ' . mysqli_error($conn);
    } else {
        $last_report = mysqli_fetch_assoc($last_report_query);
        $last_report_date = $last_report ? date('d-m-Y', strtotime($last_report['tanggal'])) : 'Belum ada laporan';
    }
    
    // Fetch schedule
    $schedule_query = mysqli_query($conn, "SELECT tugas, tanggal FROM jadwal WHERE peserta_id = '$user_id' ORDER BY tanggal DESC LIMIT 1");
    if ($schedule_query === false) {
        $schedule_title = 'Error fetching schedule: ' . mysqli_error($conn);
        $schedule_time = '';
    } else {
        $schedule = mysqli_fetch_assoc($schedule_query);
        $schedule_title = $schedule ? $schedule['tugas'] : 'Tidak ada kegiatan';
        $schedule_time = $schedule ? date('H:i', strtotime($schedule['tanggal'])) : '';
    }
    
    // Fetch report submitted
    $report_today_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan WHERE peserta_id = '$user_id'");
    if ($report_today_query === false) {
        $report_submitted_today = false;
        error_log("Report today query failed: " . mysqli_error($conn));
    } else {
        $report_submitted_today = mysqli_fetch_assoc($report_today_query)['total'] > 0;
    }
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #eff6ff;
            --accent-color: #1d4ed8;
            --text-color: #1f2937;
            --muted-color: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #ffffff 100%);
            min-height: 100vh;
            color: var(--text-color);
            overflow-x: hidden;
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
            transition: transform 0.3s ease;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
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
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255,255,255,0.3);
            transition: transform 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
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
            backdrop-filter: blur(5px);
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
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
            backdrop-filter: blur(5px);
        }

        .header h1 {
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .header .subtitle {
            color: var(--muted-color);
            font-weight: 400;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .stats-table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(5px);
        }

        .stats-table-container .table-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background: var(--secondary-color);
            font-weight: 600;
            color: var(--primary-color);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .chart-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(5px);
        }

        .chart-container .chart-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container canvas {
            max-height: 400px;
        }

        .user-welcome-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(5px);
        }

        .user-welcome-section h2 {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-welcome-section p {
            font-size: 0.95rem;
            color: var(--text-color);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .user-welcome-section .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .user-welcome-section .info-item i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .user-welcome-section .warning {
            color: #ef4444;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                position: static;
            }

            .stats-table-container, .chart-container, .user-welcome-section {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.25rem;
            }

            .table th, .table td {
                font-size: 0.85rem;
                padding: 0.5rem;
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
                    $active = strpos($href, 'dashboard.php') !== false ? 'active' : '';
                    echo "<div class='nav-item'><a class='nav-link $active' href='$href'><i class='$icon'></i> $title</a></div>";
                }
                ?>
                <div class="nav-item logout-link"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </h1>
                <p class="subtitle">Ikhtisar statistik magang untuk <?= ucfirst($role) ?></p>
            </div>

            <!-- User Welcome Section -->
            <?php if ($role == 'user'): ?>
                <div class="user-welcome-section">
                    <h2><i class="fas fa-user-circle"></i> Selamat Datang</h2>
                    <p>Halo, <?= htmlspecialchars($userName) ?>! Selamat datang di dashboard Anda.</p>
                    <div class="info-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Laporan Terakhir: Laporan terakhir dikirim pada <?= $last_report_date ?>.</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-day"></i>
                        <span>Jadwal Hari Ini: <?= $schedule_title ?><?php if ($schedule_time): ?>, pukul <?= $schedule_time ?>.<?php endif; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="<?= $report_submitted_today ? '' : 'warning' ?>">
                            Status Hari Ini: <?= $report_submitted_today ? 'Laporan hari ini telah dikirim.' : 'Anda belum mengirim laporan hari ini. Jangan lupa untuk mengisi ya!' ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Table and Chart for Admin and Pembimbing -->
            <?php if ($role == 'admin' || $role == 'pembimbing'): ?>
                <div class="stats-table-container">
                    <div class="table-title">
                        <i class="fas fa-table"></i>
                        Statistik Magang
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metrics</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Peserta</td>
                                <td><?= $total_peserta ?></td>
                            </tr>
                            <tr>
                                <td>Peserta Terverifikasi</td>
                                <td><?= $peserta_verified ?></td>
                            </tr>
                            <tr>
                                <td>Peserta Pending</td>
                                <td><?= $peserta_pending ?></td>
                            </tr>
                            <tr>
                                <td>Peserta Ditolak</td>
                                <td><?= $peserta_rejected ?></td>
                            </tr>
                            <tr>
                                <td>Total Institusi</td>
                                <td><?= $total_institusi ?></td>
                            </tr>
                            <tr>
                                <td>Total Arsip</td>
                                <td><?= $total_arsip ?></td>
                            </tr>
                            <tr>
                                <td>Arsip Bulan Ini</td>
                                <td><?= $arsip_bulan_ini ?></td>
                            </tr>
                            <tr>
                                <td>Peserta Aktif</td>
                                <td><?= $peserta_aktif ?></td>
                            </tr>
                            <tr>
                                <td>Peserta Alumni</td>
                                <td><?= $peserta_alumni ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="chart-container">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Statistik Magang (Candlestick-Like)
                    </div>
                    <canvas id="statsLineChart"></canvas>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($role == 'admin' || $role == 'pembimbing'): ?>
            // Create gradient for chart
            function createGradient(ctx, color1, color2) {
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, color1);
                gradient.addColorStop(1, color2);
                return gradient;
            }

            // Simulate candlestick-like data (open, high, low, close) for each metric
            const metrics = [
                'Total Peserta', 'Terverifikasi', 'Pending', 'Ditolak',
                'Total Institusi', 'Total Arsip', 'Arsip Bulan Ini',
                'Peserta Aktif', 'Peserta Alumni'
            ];
            const values = [
                <?= $total_peserta ?>, <?= $peserta_verified ?>, <?= $peserta_pending ?>,
                <?= $peserta_rejected ?>, <?= $total_institusi ?>, <?= $total_arsip ?>,
                <?= $arsip_bulan_ini ?>, <?= $peserta_aktif ?>, <?= $peserta_alumni ?>
            ];

            // Generate candlestick-like data
            const candlestickData = metrics.map((metric, index) => {
                const value = values[index];
                return {
                    x: metric,
                    o: value * 0.9,  // Open: 90% of value
                    h: value * 1.1,  // High: 110% of value
                    l: value * 0.8,  // Low: 80% of value
                    c: value         // Close: actual value
                };
            });

            // Line Chart with Candlestick-like representation
            const statsLineChart = new Chart(document.getElementById('statsLineChart'), {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Statistik Magang',
                        data: candlestickData,
                        borderColor: createGradient(
                            document.getElementById('statsLineChart').getContext('2d'),
                            '#2563eb',
                            '#1d4ed8'
                        ),
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                        parsing: {
                            yAxisKey: 'c' // Use 'close' value for the line
                        }
                    }, {
                        label: 'High-Low Range',
                        data: candlestickData,
                        type: 'line',
                        borderColor: 'rgba(0,0,0,0.2)',
                        borderWidth: 1,
                        pointRadius: 0,
                        segment: {
                            borderColor: ctx => ctx.p0.parsed.y < ctx.p1.parsed.y ? '#ef4444' : '#10b981',
                        },
                        parsing: {
                            yAxisKey: 'h' // Plot high values
                        }
                    }, {
                        label: 'Low',
                        data: candlestickData,
                        type: 'line',
                        borderColor: 'rgba(0,0,0,0.2)',
                        borderWidth: 1,
                        pointRadius: 0,
                        parsing: {
                            yAxisKey: 'l' // Plot low values
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 12, family: 'Inter' },
                                padding: 15
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'nearest',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    const data = context.raw;
                                    return [
                                        `Metric: ${context.label}`,
                                        `Open: ${data.o}`,
                                        `High: ${data.h}`,
                                        `Low: ${data.l}`,
                                        `Close: ${data.c}`
                                    ];
                                }
                            },
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleFont: { size: 12, family: 'Inter' },
                            bodyFont: { size: 10, family: 'Inter' },
                            padding: 8
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Metrics',
                                font: { size: 12, family: 'Inter' }
                            },
                            grid: { display: false }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Jumlah',
                                font: { size: 12, family: 'Inter' }
                            },
                            beginAtZero: true,
                            grid: { color: '#e5e7eb' }
                        }
                    },
                    animation: {
                        duration: 800,
                        easing: 'easeOutCubic'
                    }
                }
            });

            // Animate table and chart on load
            document.addEventListener('DOMContentLoaded', function() {
                const containers = document.querySelectorAll('.stats-table-container, .chart-container');
                containers.forEach((container, index) => {
                    setTimeout(() => {
                        container.style.opacity = '0';
                        container.style.transform = 'translateY(15px)';
                        container.style.transition = 'all 0.5s ease';
                        setTimeout(() => {
                            container.style.opacity = '1';
                            container.style.transform = 'translateY(0)';
                        }, 50);
                    }, index * 100);
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>
<?php 
include "config/auth.php"; 
include "config/db.php"; 
checkLogin();  

$user = $_SESSION['user']; 
$role = $user['role']; 
$user_id = $user['id']; 
$userName = $user['nama'] ?? 'User';
$pesan = '';  

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

// Ganti password 
if(isset($_POST['ganti_pass'])){     
    $pw_lama = $_POST['pw_lama'];     
    $pw_baru = $_POST['pw_baru'];      
    
    // cek cocok md5 atau password_hash     
    $match = password_verify($pw_lama, $user['password']) || md5($pw_lama) === $user['password'];     
    if($match){         
        $hash_baru = password_hash($pw_baru, PASSWORD_DEFAULT);         
        mysqli_query($conn, "UPDATE users SET password='$hash_baru' WHERE id={$user['id']}");         
        $_SESSION['user']['password'] = $hash_baru;         
        $pesan = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Password berhasil diganti.</div>';     
    }else{         
        $pesan = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Password lama salah.</div>';     
    } 
} 

// Get additional user data if exists
$peserta_data = null;
if($role === 'user') {
    $res = mysqli_query($conn, "SELECT p.*, i.nama as institusi_nama FROM peserta p LEFT JOIN institusi i ON p.institusi_id = i.id WHERE p.user_id = $user_id LIMIT 1");
    $peserta_data = mysqli_fetch_assoc($res);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - <?= ucfirst($role) ?></title>
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
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            pointer-events: none;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            backdrop-filter: blur(10px);
            border: 4px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-role {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: inline-block;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
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
        
        .id-card-section {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .id-card-section h4 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            background: #ef4444;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
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
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                padding: 1.5rem;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
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
                        <a class="nav-link active" href="settings.php">
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
                    <div class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user"></i>
                            Profil
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
                        <a class="nav-link" href="laporan.php">
                            <i class="fas fa-file-alt"></i>
                            Laporan Saya
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user"></i>
                            Profil
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
                    <i class="fas fa-user-circle"></i>
                    Profil Saya
                </h1>
                <p class="subtitle">Kelola informasi akun dan keamanan profil Anda</p>
            </div>

            <?= $pesan ?>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?= $roleIcons[$role] ?>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($user['nama']) ?></div>
                    <div class="profile-role"><?= ucfirst($role) ?></div>
                </div>
                
                <div class="profile-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Nama Lengkap</div>
                            <div class="info-value">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($user['nama']) ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($user['email']) ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Role</div>
                            <div class="info-value">
                                <i class="fas fa-shield-alt"></i>
                                <?= ucfirst($user['role']) ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Status Akun</div>
                            <div class="info-value">
                                <i class="fas fa-check-circle text-success"></i>
                                Aktif
                            </div>
                        </div>
                        
                        <?php if($peserta_data): ?>
                        <div class="info-item">
                            <div class="info-label">Institusi</div>
                            <div class="info-value">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($peserta_data['institusi_nama'] ?? 'Belum ditentukan') ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Nomor Telepon</div>
                            <div class="info-value">
                                <i class="fas fa-phone"></i>
                                <?= htmlspecialchars($peserta_data['telepon'] ?? 'Belum diisi') ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ID Card Section (Only for User) -->
            <?php if($user['role'] === 'user' && $peserta_data): ?>
            <div class="id-card-section">
                <h4>
                    <i class="fas fa-id-card"></i>
                    ID Card Digital
                </h4>
                <p>Cetak kartu identitas digital Anda untuk keperluan magang</p>
                <a href="print_idcard.php?id=<?= $peserta_data['id'] ?>" target="_blank" class="btn btn-success">
                    <i class="fas fa-print"></i>
                    Cetak ID Card
                </a>
            </div>
            <?php endif; ?>

            <!-- Change Password Form -->
            <div class="form-card">
                <h3>
                    <i class="fas fa-lock"></i>
                    Ganti Password
                </h3>
                <form method="post" id="passwordForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password Lama</label>
                            <div class="position-relative">
                                <input type="password" name="pw_lama" class="form-control" placeholder="Masukkan password lama" required>
                                <button type="button" class="btn btn-outline-secondary position-absolute end-0 top-0 h-100 px-3" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <div class="position-relative">
                                <input type="password" name="pw_baru" id="newPassword" class="form-control" placeholder="Masukkan password baru" required>
                                <button type="button" class="btn btn-outline-secondary position-absolute end-0 top-0 h-100 px-3" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-strength-text" id="strengthText"></div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button name="ganti_pass" type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Password
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(button) {
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = '';
            
            // Length check
            if (password.length >= 8) score += 1;
            if (password.length >= 12) score += 1;
            
            // Character variety
            if (/[a-z]/.test(password)) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^A-Za-z0-9]/.test(password)) score += 1;
            
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (score <= 2) {
                strengthBar.style.width = '33%';
                strengthBar.style.background = '#ef4444';
                feedback = 'Lemah';
            } else if (score <= 4) {
                strengthBar.style.width = '66%';
                strengthBar.style.background = '#f59e0b';
                feedback = 'Sedang';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.background = '#10b981';
                feedback = 'Kuat';
            }
            
            strengthText.textContent = feedback;
        }
        
        // Event listener for password input
        document.getElementById('newPassword').addEventListener('input', function(e) {
            checkPasswordStrength(e.target.value);
        });
        
        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const oldPassword = document.querySelector('input[name="pw_lama"]').value;
            const newPassword = document.querySelector('input[name="pw_baru"]').value;
            
            if (oldPassword === newPassword) {
                e.preventDefault();
                alert('Password baru harus berbeda dengan password lama!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password baru minimal 6 karakter!');
                return false;
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Smooth scrolling for navigation
        document.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Add loading state
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 200);
            });
        });
        
        // Add ripple effect to buttons
        document.querySelectorAll('.btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            .btn {
                position: relative;
                overflow: hidden;
            }
            
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                pointer-events: none;
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .alert {
                transition: all 0.3s ease;
            }
            
            .form-control:focus {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1), 0 0 0 3px var(--primary-color)20;
            }
            
            .info-item {
                transition: all 0.3s ease;
            }
            
            .info-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            .profile-card {
                transition: all 0.3s ease;
            }
            
            .profile-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            }
            
            .sidebar .nav-link {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .sidebar .nav-link:hover {
                padding-left: 1.5rem;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
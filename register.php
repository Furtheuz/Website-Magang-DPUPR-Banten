<?php 
include "config/db.php"; 
session_start();  

// Handle Register
if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nim = $_POST['nim'];
    $universitas = $_POST['universitas'];
    $jurusan = $_POST['jurusan'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $foto = $_FILES['foto'];

    // Validasi
    if ($password !== $confirm_password) {
        $register_error = "Password dan konfirmasi password tidak cocok.";
    } else {
        // Cek email sudah ada
        $check_email = mysqli_prepare($conn, "SELECT * FROM register WHERE email = ?");
        mysqli_stmt_bind_param($check_email, "s", $email);
        mysqli_stmt_execute($check_email);
        if (mysqli_stmt_get_result($check_email)->num_rows > 0) {
            $register_error = "Email sudah terdaftar.";
        } else {
            // Cek NIM sudah ada
            $check_nim = mysqli_prepare($conn, "SELECT * FROM register WHERE nim = ?");
            mysqli_stmt_bind_param($check_nim, "s", $nim);
            mysqli_stmt_execute($check_nim);
            if (mysqli_stmt_get_result($check_nim)->num_rows > 0) {
                $register_error = "NIM sudah terdaftar.";
            } else {
                $today = date('Y-m-d');
                if ($tanggal_masuk < $today) {
                    $register_error = "Tanggal masuk tidak boleh kurang dari hari ini.";
                } elseif ($tanggal_keluar <= $tanggal_masuk) {
                    $register_error = "Tanggal keluar harus lebih besar dari tanggal masuk.";
                } else {
                    // Validasi file foto
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if (!in_array($foto['type'], $allowed_types)) {
                        $register_error = "Format file foto harus JPG atau PNG.";
                    } elseif ($foto['size'] > $max_size) {
                        $register_error = "Ukuran file foto maksimal 2MB.";
                    } else {
                        // Upload file
                        $foto_name = uniqid() . '_' . $foto['name'];
                        $foto_path = "uploads/" . $foto_name;
                        if (!move_uploaded_file($foto['tmp_name'], $foto_path)) {
                            $register_error = "Gagal mengunggah foto.";
                        } else {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert ke tabel register dengan prepared statement
                            $query = "INSERT INTO register (nama, email, password, nim, universitas, jurusan, no_hp, alamat, tanggal_masuk, tanggal_keluar, foto, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "sssssssssss", $nama, $email, $hashed_password, $nim, $universitas, $jurusan, $no_hp, $alamat, $tanggal_masuk, $tanggal_keluar, $foto_path);
                            if (mysqli_stmt_execute($stmt)) {
                                $register_success = "Pendaftaran berhasil! Menunggu persetujuan admin.";
                            } else {
                                $register_error = "Terjadi kesalahan saat pendaftaran.";
                                // Hapus file jika gagal insert
                                if (file_exists($foto_path)) {
                                    unlink($foto_path);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
       <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #eff6ff;
            --accent-color: #1d4ed8;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: 
                linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                url('images/dpupr.webp') center/cover no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .floating-shapes::before,
        .floating-shapes::after {
            content: '';
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-shapes::before {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
            animation-delay: -2s;
        }
        
        .floating-shapes::after {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -100px;
            animation-delay: -4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px); 
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            padding: 0;
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            position: relative;
            z-index: 10;
            margin: 2rem 0;
        }

        .register-header {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.3), rgba(29, 78, 216, 0.3));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            pointer-events: none;
        }
        
        .register-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
        }

        .register-title, .register-subtitle {
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .register-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .register-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .register-body {
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group:last-of-type {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #ffffff !important;
            margin-bottom: 0.5rem;
            display: block;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        
        .form-control {
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.1);
            width: 100%;
            color: white;
            backdrop-filter: blur(10px);
        }

        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .form-control:focus {
            border-color: rgba(255,255,255,0.6);
            box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            outline: none;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-right: 3rem;
        }
        
        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .input-icon:hover {
            color: white;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            margin-top: 1rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.3);
            color: #ffffff !important;
            font-size: 0.875rem;
        }

        .login-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-text {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .form-control[type="file"] {
            padding: 0.5rem;
            color: white;
        }
        
        .form-control[type="file"]::-webkit-file-upload-button {
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            margin-right: 1rem;
            cursor: pointer;
        }
        
        .login-link:hover {
            color: #93c5fd;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .register-container {
                margin: 1rem;
                width: calc(100% - 2rem);
                max-width: 400px;
            }
            
            .register-header {
                padding: 1.5rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
            
            .register-body {
                padding: 1.5rem;
            }
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>

</head>
<body>
    <div class="floating-shapes"></div>
    
    <div class="register-container">
        <div class="register-header">
            <div class="register-icon">
                <img src="images/LogoBanten.png" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
            </div>
            <h1 class="register-title">SPAM</h1>
            <p class="register-subtitle">Sistem Pengelolaan Administrasi Magang DPUPR Banten</p>
        </div>
        
        <div class="register-body">
            <?php if(isset($register_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $register_error ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($register_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $register_success ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="registerForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user me-2"></i>
                        Nama Lengkap
                    </label>
                    <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope me-2"></i>
                        Email
                    </label>
                    <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-id-card me-2"></i>
                        NIM/NISN
                    </label>
                    <input type="text" name="nim" class="form-control" placeholder="Masukkan NIM (Mahasiswa) atau NISN (Siswa)" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-university me-2"></i>
                        Universitas/Sekolah
                    </label>
                    <input type="text" name="universitas" class="form-control" placeholder="Masukkan nama universitas atau sekolah" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Jurusan
                    </label>
                    <input type="text" name="jurusan" class="form-control" placeholder="Masukkan jurusan/program studi" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-camera me-2"></i>
                        Foto Pas Foto (Format 4x6 atau 1x1)
                    </label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                    <small class="form-text text-white-50 mt-1">
                        <i class="fas fa-info-circle me-1"></i>
                        Upload pas foto dengan ukuran 4x6 atau 1x1, format JPG/PNG, maksimal 2MB
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Alamat
                    </label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-check me-2"></i>
                        Tanggal Masuk
                    </label>
                    <input type="date" name="tanggal_masuk" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-times me-2"></i>
                        Tanggal Keluar
                    </label>
                    <input type="date" name="tanggal_keluar" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone me-2"></i>
                        No. HP
                    </label>
                    <input type="tel" name="no_hp" class="form-control" placeholder="Masukkan nomor HP" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock me-2"></i>
                        Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="registerPassword" class="form-control" placeholder="Masukkan password" required>
                        <span class="input-icon" onclick="togglePassword('registerPassword', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock me-2"></i>
                        Konfirmasi Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Konfirmasi password" required>
                        <span class="input-icon" onclick="togglePassword('confirmPassword', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i>
                    Daftar Sekarang
                </button>
            </form>
            
            <div class="form-footer">
                <p>Sudah memiliki akun? <a href="login.php" class="login-link">Login di sini</a></p>
                <p>© 2025 Sistem Pengelolaan Administrasi Magang DPUPR Banten.</p>
            </div>
        </div>
    </div>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
        
        // Form animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Auto-hide alerts
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
        
        // Button ripple effect
        document.querySelectorAll('.btn-register').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    pointer-events: none;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                `;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Set minimum date to today for date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.min = today;
            });
        });
    </script>
</body>
</html>
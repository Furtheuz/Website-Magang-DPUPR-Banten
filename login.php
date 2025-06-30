<?php
include "config/db.php";
session_start();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $q = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if ($u = mysqli_fetch_assoc($q)) {
        if (password_verify($password, $u['password']) || md5($password) === $u['password']) {
    $_SESSION['user'] = $u;
    header("Location: dashboard.php");
    exit;
} else {
    $error = "Password salah.";
}

    } else {
        $error = "User tidak ditemukan.";
    }
}

if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users(nama,email,password,role) VALUES('$nama','$email','$password','user')");
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container p-5">
<h2>Login</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="post">
    <input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
    <input class="form-control mb-2" type="password" name="password" placeholder="Password" required>
    <button class="btn btn-primary" name="login">Login</button>
</form>
<hr>
<h4>Register (User)</h4>
<form method="post">
    <input class="form-control mb-2" type="text" name="nama" placeholder="Nama" required>
    <input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
    <input class="form-control mb-2" type="password" name="password" placeholder="Password" required>
    <button class="btn btn-success" name="register">Daftar</button>
</form>
</body>
</html>

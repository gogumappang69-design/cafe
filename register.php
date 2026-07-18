<?php
require_once 'config.php';

// Jika sudah login, langsung arahkan ke dashboard masing-masing
if (isLoggedIn()) {
    redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ----- VALIDASI FORM -----
    if ($name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        // Cek apakah email sudah terdaftar
        $stmt = $koneksi->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $koneksi->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "user")');
            $stmt2->bind_param('sss', $name, $email, $hashedPassword);

            if ($stmt2->execute()) {
                $success = 'Registrasi berhasil! Silakan login menggunakan akun Anda.';
            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Coba lagi.';
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun - Perpustakaan Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="container">
        <a href="index.php" class="brand">📚 Perpustakaan Online</a>
        <nav>
            <a href="index.php">Beranda</a>
            <a href="login.php">Login</a>
        </nav>
    </div>
</div>

<div class="form-wrapper">
    <h2>Buat Akun Baru</h2>
    <p class="subtitle">Daftar untuk mulai meminjam buku favoritmu</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?> <a href="login.php">Login sekarang</a></div>
    <?php endif; ?>

    <form method="POST" action="register.php" novalidate>
        <div class="form-group">
            <label for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" placeholder="Masukkan nama lengkap" value="<?= isset($_POST['name']) ? clean($_POST['name']) : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
        </div>
        <button type="submit" class="btn btn-primary">Daftar</button>
    </form>

    <div class="form-footer">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Perpustakaan Online. Semua hak cipta dilindungi.
</footer>

</body>
</html>

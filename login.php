<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Login sukses, set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                redirect($user['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_user.php');
            } else {
                $error = 'Password salah. Silakan coba lagi.';
            }
        } else {
            $error = 'Email tidak ditemukan.';
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
<title>Login - Perpustakaan Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="container">
        <a href="index.php" class="brand">📚 Perpustakaan Online</a>
        <nav>
            <a href="index.php">Beranda</a>
            <a href="register.php">Daftar</a>
        </nav>
    </div>
</div>

<div class="form-wrapper">
    <h2>Masuk ke Akun</h2>
    <p class="subtitle">Silakan login untuk melanjutkan</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>

    <div class="form-footer">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>

    <div class="form-footer" style="margin-top:10px; font-size:0.8rem;">
        Demo admin: admin@perpus.com / admin123<br>
        Demo user: budi@mail.com / user123
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Perpustakaan Online. Semua hak cipta dilindungi.
</footer>

</body>
</html>

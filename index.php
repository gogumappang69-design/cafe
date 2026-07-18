<?php
require_once 'config.php';

// Ambil data buku untuk ditampilkan di beranda
$books = [];
$result = $koneksi->query('SELECT * FROM books ORDER BY created_at DESC LIMIT 8');
if ($result) {
    $books = $result->fetch_all(MYSQLI_ASSOC);
}

$totalBuku = $koneksi->query('SELECT COUNT(*) as total FROM books')->fetch_assoc()['total'];
$totalUser = $koneksi->query('SELECT COUNT(*) as total FROM users WHERE role = "user"')->fetch_assoc()['total'];
$totalPinjam = $koneksi->query('SELECT COUNT(*) as total FROM borrows WHERE status = "dipinjam"')->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perpustakaan Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="container">
        <a href="index.php" class="brand">📚 Perpustakaan Online</a>
        <nav>
            <a href="index.php">Beranda</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php' ?>">Dashboard</a>
                <span class="badge-role"><?= strtoupper($_SESSION['role']) ?></span>
                <span>Hai, <?= clean($_SESSION['name']) ?></span>
                <a href="logout.php"><button class="btn-logout">Logout</button></a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php"><button class="btn btn-sm btn-primary" style="padding:8px 18px;">Daftar</button></a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div class="hero">
    <div class="container">
        <h1>Selamat Datang di Perpustakaan Online</h1>
        <p>Temukan, pinjam, dan nikmati ribuan koleksi buku pilihan langsung dari rumah. Mudah, cepat, dan gratis.</p>
        <div class="hero-buttons">
            <?php if (!isLoggedIn()): ?>
                <a href="register.php"><button class="btn btn-primary">Daftar Sekarang</button></a>
                <a href="login.php"><button class="btn btn-outline">Login</button></a>
            <?php else: ?>
                <a href="<?= isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php' ?>"><button class="btn btn-primary">Buka Dashboard</button></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="stats-row" style="margin-top:40px;">
        <div class="stat-card">
            <div class="number"><?= $totalBuku ?></div>
            <div class="label">Total Koleksi Buku</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $totalUser ?></div>
            <div class="label">Anggota Terdaftar</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $totalPinjam ?></div>
            <div class="label">Buku Sedang Dipinjam</div>
        </div>
    </div>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">Koleksi Buku Terbaru</h2>

        <?php if (count($books) === 0): ?>
            <div class="empty-state">Belum ada buku yang tersedia.</div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <div class="cover">📖</div>
                        <div class="info">
                            <h3><?= clean($book['title']) ?></h3>
                            <div class="author">oleh <?= clean($book['author']) ?></div>
                            <span class="category"><?= clean($book['category']) ?></span>
                            <div class="stock <?= $book['stock'] <= 0 ? 'habis' : '' ?>">
                                <?= $book['stock'] > 0 ? 'Stok tersedia: ' . $book['stock'] : 'Stok habis' ?>
                            </div>
                            <div class="actions">
                                <?php if (!isLoggedIn()): ?>
                                    <a href="login.php"><button class="btn btn-sm btn-green">Login untuk pinjam</button></a>
                                <?php elseif (!isAdmin()): ?>
                                    <a href="dashboard_user.php"><button class="btn btn-sm btn-green">Lihat di Dashboard</button></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Perpustakaan Online. Semua hak cipta dilindungi.
</footer>

</body>
</html>

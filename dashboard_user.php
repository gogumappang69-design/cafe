<?php
require_once 'config.php';
requireLogin();

// Jika admin nyasar ke sini, arahkan ke dashboard admin
if (isAdmin()) {
    redirect('dashboard_admin.php');
}

$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'katalog';
$user_id = $_SESSION['user_id'];

// =========================================================
// PROSES: PINJAM BUKU
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'borrow_book') {
    $book_id = (int)($_POST['book_id'] ?? 0);

    // Cek stok buku
    $stmt = $koneksi->prepare('SELECT stock FROM books WHERE id=?');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Cek apakah user sedang meminjam buku yang sama dan belum dikembalikan
    $stmtCheck = $koneksi->prepare('SELECT id FROM borrows WHERE user_id=? AND book_id=? AND status="dipinjam"');
    $stmtCheck->bind_param('ii', $user_id, $book_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    $alreadyBorrowed = $stmtCheck->num_rows > 0;
    $stmtCheck->close();

    if (!$book || $book['stock'] <= 0) {
        $error = 'Maaf, stok buku ini sedang habis.';
    } elseif ($alreadyBorrowed) {
        $error = 'Anda masih meminjam buku ini dan belum mengembalikannya.';
    } else {
        $koneksi->begin_transaction();
        try {
            $stmt1 = $koneksi->prepare('INSERT INTO borrows (user_id, book_id, borrow_date, status) VALUES (?, ?, CURDATE(), "dipinjam")');
            $stmt1->bind_param('ii', $user_id, $book_id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $koneksi->prepare('UPDATE books SET stock = stock - 1 WHERE id=?');
            $stmt2->bind_param('i', $book_id);
            $stmt2->execute();
            $stmt2->close();

            $koneksi->commit();
            $success = 'Buku berhasil dipinjam! Selamat membaca.';
        } catch (Exception $e) {
            $koneksi->rollback();
            $error = 'Terjadi kesalahan saat memproses peminjaman.';
        }
    }
    $tab = 'katalog';
}

// =========================================================
// PROSES: BERI ULASAN
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_review') {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = clean($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) $rating = 5;

    if ($comment === '') {
        $error = 'Komentar ulasan tidak boleh kosong.';
    } else {
        $stmt = $koneksi->prepare('INSERT INTO reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiis', $user_id, $book_id, $rating, $comment);
        $stmt->execute();
        $stmt->close();
        $success = 'Terima kasih, ulasan Anda berhasil dikirim.';
    }
    $tab = 'riwayat';
}

// =========================================================
// AMBIL DATA
// =========================================================
$books = $koneksi->query('SELECT * FROM books ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

$stmtHist = $koneksi->prepare('
    SELECT b.id, bk.id as book_id, bk.title, bk.author, b.borrow_date, b.return_date, b.status
    FROM borrows b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ?
    ORDER BY b.status ASC, b.borrow_date DESC
');
$stmtHist->bind_param('i', $user_id);
$stmtHist->execute();
$history = $stmtHist->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtHist->close();

$sedangDipinjam = count(array_filter($history, fn($h) => $h['status'] === 'dipinjam'));
$sudahDikembalikan = count(array_filter($history, fn($h) => $h['status'] === 'dikembalikan'));

// Buku yang pernah dipinjam user (boleh diberi ulasan)
$borrowedBookIds = array_unique(array_column($history, 'book_id'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Saya - Perpustakaan Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="container">
        <a href="index.php" class="brand">📚 Perpustakaan Online</a>
        <nav>
            <a href="index.php">Beranda</a>
            <a href="dashboard_user.php">Dashboard</a>
            <span class="badge-role">USER</span>
            <span>Hai, <?= clean($_SESSION['name']) ?></span>
            <a href="logout.php"><button class="btn-logout">Logout</button></a>
        </nav>
    </div>
</div>

<div class="dash-header">
    <div class="container">
        <h1>Dashboard Saya</h1>
        <p>Jelajahi katalog buku, pinjam buku favoritmu, dan berikan ulasan.</p>
    </div>
</div>

<div class="container">

    <div class="stats-row">
        <div class="stat-card">
            <div class="number"><?= count($books) ?></div>
            <div class="label">Buku Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $sedangDipinjam ?></div>
            <div class="label">Sedang Saya Pinjam</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $sudahDikembalikan ?></div>
            <div class="label">Sudah Dikembalikan</div>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="tab-menu">
        <a href="?tab=katalog" class="<?= $tab === 'katalog' ? 'active' : '' ?>">📖 Katalog Buku</a>
        <a href="?tab=riwayat" class="<?= $tab === 'riwayat' ? 'active' : '' ?>">🕘 Riwayat Peminjaman</a>
    </div>

    <?php if ($tab === 'katalog'): ?>

        <div class="book-grid">
            <?php if (count($books) === 0): ?>
                <div class="empty-state">Belum ada buku tersedia.</div>
            <?php else: foreach ($books as $book): ?>
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
                            <form method="POST" action="dashboard_user.php">
                                <input type="hidden" name="form_action" value="borrow_book">
                                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-green" <?= $book['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <?= $book['stock'] <= 0 ? 'Stok Habis' : 'Pinjam Buku' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

    <?php elseif ($tab === 'riwayat'): ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Judul Buku</th>
                        <th>Penulis</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history) === 0): ?>
                        <tr><td colspan="5" class="empty-state">Anda belum pernah meminjam buku.</td></tr>
                    <?php else: foreach ($history as $h): ?>
                        <tr>
                            <td><?= clean($h['title']) ?></td>
                            <td><?= clean($h['author']) ?></td>
                            <td><?= clean($h['borrow_date']) ?></td>
                            <td><?= $h['return_date'] ? clean($h['return_date']) : '-' ?></td>
                            <td><span class="status-pill status-<?= $h['status'] ?>"><?= ucfirst($h['status']) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($borrowedBookIds) > 0): ?>
        <div class="card-box">
            <h2>Beri Ulasan Buku</h2>
            <form method="POST" action="dashboard_user.php">
                <input type="hidden" name="form_action" value="add_review">

                <div class="form-group">
                    <label>Pilih Buku</label>
                    <select name="book_id" required>
                        <?php foreach ($history as $h): ?>
                            <option value="<?= $h['book_id'] ?>"><?= clean($h['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" required>
                        <option value="5">★★★★★ (5 - Sangat Bagus)</option>
                        <option value="4">★★★★☆ (4 - Bagus)</option>
                        <option value="3">★★★☆☆ (3 - Cukup)</option>
                        <option value="2">★★☆☆☆ (2 - Kurang)</option>
                        <option value="1">★☆☆☆☆ (1 - Buruk)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Komentar</label>
                    <textarea name="comment" rows="3" placeholder="Bagaimana pendapatmu tentang buku ini?" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
            </form>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<footer>
    &copy; <?= date('Y') ?> Perpustakaan Online. Semua hak cipta dilindungi.
</footer>

</body>
</html>

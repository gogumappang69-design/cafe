<?php
require_once 'config.php';
requireAdmin();

$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'buku';
$editBook = null;

// =========================================================
// PROSES AKSI: TAMBAH / EDIT / HAPUS BUKU
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {

    if ($_POST['form_action'] === 'save_book') {
        $title = clean($_POST['title'] ?? '');
        $author = clean($_POST['author'] ?? '');
        $category = clean($_POST['category'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $description = clean($_POST['description'] ?? '');
        $book_id = (int)($_POST['book_id'] ?? 0);

        if ($title === '' || $author === '' || $category === '' || $stock < 0) {
            $error = 'Semua kolom buku wajib diisi dengan benar.';
        } else {
            if ($book_id > 0) {
                // UPDATE buku
                $stmt = $koneksi->prepare('UPDATE books SET title=?, author=?, category=?, stock=?, description=? WHERE id=?');
                $stmt->bind_param('sssisi', $title, $author, $category, $stock, $description, $book_id);
                $stmt->execute();
                $stmt->close();
                $success = 'Buku berhasil diperbarui.';
            } else {
                // INSERT buku baru
                $stmt = $koneksi->prepare('INSERT INTO books (title, author, category, stock, description) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssis', $title, $author, $category, $stock, $description);
                $stmt->execute();
                $stmt->close();
                $success = 'Buku baru berhasil ditambahkan.';
            }
        }
        $tab = 'buku';
    }

    // Update status peminjaman (dikembalikan)
    if ($_POST['form_action'] === 'update_borrow') {
        $borrow_id = (int)($_POST['borrow_id'] ?? 0);
        $book_id = (int)($_POST['book_id'] ?? 0);

        $stmt = $koneksi->prepare('UPDATE borrows SET status="dikembalikan", return_date=CURDATE() WHERE id=?');
        $stmt->bind_param('i', $borrow_id);
        $stmt->execute();
        $stmt->close();

        // Kembalikan stok buku
        $stmt2 = $koneksi->prepare('UPDATE books SET stock = stock + 1 WHERE id=?');
        $stmt2->bind_param('i', $book_id);
        $stmt2->execute();
        $stmt2->close();

        $success = 'Status peminjaman diperbarui menjadi dikembalikan.';
        $tab = 'peminjaman';
    }
}

// =========================================================
// PROSES AKSI: HAPUS BUKU (GET, dengan konfirmasi JS)
// =========================================================
if (isset($_GET['delete_book'])) {
    $book_id = (int)$_GET['delete_book'];
    $stmt = $koneksi->prepare('DELETE FROM books WHERE id=?');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $stmt->close();
    $success = 'Buku berhasil dihapus.';
    $tab = 'buku';
}

// Ambil data buku untuk form edit
if (isset($_GET['edit_book'])) {
    $book_id = (int)$_GET['edit_book'];
    $stmt = $koneksi->prepare('SELECT * FROM books WHERE id=?');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $editBook = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $tab = 'buku';
}

// =========================================================
// PROSES AKSI: HAPUS USER
// =========================================================
if (isset($_GET['delete_user'])) {
    $user_id = (int)$_GET['delete_user'];
    if ($user_id !== (int)$_SESSION['user_id']) { // admin tidak bisa hapus diri sendiri
        $stmt = $koneksi->prepare('DELETE FROM users WHERE id=? AND role="user"');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Anggota berhasil dihapus.';
    } else {
        $error = 'Tidak dapat menghapus akun sendiri.';
    }
    $tab = 'anggota';
}

// =========================================================
// AMBIL DATA UNTUK DITAMPILKAN
// =========================================================
$books = $koneksi->query('SELECT * FROM books ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

$borrows = $koneksi->query('
    SELECT b.id, u.name AS user_name, bk.title AS book_title, bk.id as book_id,
           b.borrow_date, b.return_date, b.status
    FROM borrows b
    JOIN users u ON b.user_id = u.id
    JOIN books bk ON b.book_id = bk.id
    ORDER BY b.status ASC, b.borrow_date DESC
')->fetch_all(MYSQLI_ASSOC);

$users = $koneksi->query('SELECT * FROM users WHERE role="user" ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

$reviews = $koneksi->query('
    SELECT r.id, u.name AS user_name, bk.title AS book_title, r.rating, r.comment, r.created_at
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN books bk ON r.book_id = bk.id
    ORDER BY r.created_at DESC
')->fetch_all(MYSQLI_ASSOC);

$totalBuku = count($books);
$totalUser = count($users);
$totalDipinjam = count(array_filter($borrows, fn($b) => $b['status'] === 'dipinjam'));
$totalUlasan = count($reviews);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Perpustakaan Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="navbar">
    <div class="container">
        <a href="index.php" class="brand">📚 Perpustakaan Online</a>
        <nav>
            <a href="index.php">Beranda</a>
            <a href="dashboard_admin.php">Dashboard</a>
            <span class="badge-role">ADMIN</span>
            <span>Hai, <?= clean($_SESSION['name']) ?></span>
            <a href="logout.php"><button class="btn-logout">Logout</button></a>
        </nav>
    </div>
</div>

<div class="dash-header">
    <div class="container">
        <h1>Dashboard Admin</h1>
        <p>Kelola seluruh data perpustakaan: buku, peminjaman, anggota, dan ulasan.</p>
    </div>
</div>

<div class="container">

    <div class="stats-row">
        <div class="stat-card">
            <div class="number"><?= $totalBuku ?></div>
            <div class="label">Total Buku</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $totalUser ?></div>
            <div class="label">Total Anggota</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $totalDipinjam ?></div>
            <div class="label">Sedang Dipinjam</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $totalUlasan ?></div>
            <div class="label">Total Ulasan</div>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="tab-menu">
        <a href="?tab=buku" class="<?= $tab === 'buku' ? 'active' : '' ?>">📚 Kelola Buku</a>
        <a href="?tab=peminjaman" class="<?= $tab === 'peminjaman' ? 'active' : '' ?>">🔄 Peminjaman</a>
        <a href="?tab=anggota" class="<?= $tab === 'anggota' ? 'active' : '' ?>">👥 Anggota</a>
        <a href="?tab=ulasan" class="<?= $tab === 'ulasan' ? 'active' : '' ?>">⭐ Ulasan</a>
    </div>

    <?php if ($tab === 'buku'): ?>

        <div class="card-box">
            <h2><?= $editBook ? 'Edit Buku' : 'Tambah Buku Baru' ?></h2>
            <form method="POST" action="dashboard_admin.php">
                <input type="hidden" name="form_action" value="save_book">
                <input type="hidden" name="book_id" value="<?= $editBook['id'] ?? 0 ?>">

                <div class="form-group">
                    <label>Judul Buku</label>
                    <input type="text" name="title" required value="<?= $editBook ? clean($editBook['title']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Penulis</label>
                    <input type="text" name="author" required value="<?= $editBook ? clean($editBook['author']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="category" required value="<?= $editBook ? clean($editBook['category']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stock" min="0" required value="<?= $editBook ? (int)$editBook['stock'] : 0 ?>">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"><?= $editBook ? clean($editBook['description']) : '' ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary"><?= $editBook ? 'Simpan Perubahan' : 'Tambah Buku' ?></button>
                <?php if ($editBook): ?>
                    <a href="dashboard_admin.php?tab=buku"><button type="button" class="btn btn-gray">Batal</button></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($books) === 0): ?>
                        <tr><td colspan="5" class="empty-state">Belum ada data buku.</td></tr>
                    <?php else: foreach ($books as $book): ?>
                        <tr>
                            <td><?= clean($book['title']) ?></td>
                            <td><?= clean($book['author']) ?></td>
                            <td><?= clean($book['category']) ?></td>
                            <td><?= (int)$book['stock'] ?></td>
                            <td class="table-actions">
                                <a href="?tab=buku&edit_book=<?= $book['id'] ?>"><button class="btn btn-sm btn-green">Edit</button></a>
                                <a href="?delete_book=<?= $book['id'] ?>" onclick="return confirm('Yakin ingin menghapus buku ini?')"><button class="btn btn-sm btn-danger">Hapus</button></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($tab === 'peminjaman'): ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($borrows) === 0): ?>
                        <tr><td colspan="6" class="empty-state">Belum ada data peminjaman.</td></tr>
                    <?php else: foreach ($borrows as $b): ?>
                        <tr>
                            <td><?= clean($b['user_name']) ?></td>
                            <td><?= clean($b['book_title']) ?></td>
                            <td><?= clean($b['borrow_date']) ?></td>
                            <td><?= $b['return_date'] ? clean($b['return_date']) : '-' ?></td>
                            <td><span class="status-pill status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td>
                                <?php if ($b['status'] === 'dipinjam'): ?>
                                    <form method="POST" action="dashboard_admin.php" style="display:inline;">
                                        <input type="hidden" name="form_action" value="update_borrow">
                                        <input type="hidden" name="borrow_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-green">Tandai Dikembalikan</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($tab === 'anggota'): ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Bergabung Sejak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) === 0): ?>
                        <tr><td colspan="4" class="empty-state">Belum ada anggota terdaftar.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                        <tr>
                            <td><?= clean($u['name']) ?></td>
                            <td><?= clean($u['email']) ?></td>
                            <td><?= clean($u['created_at']) ?></td>
                            <td>
                                <a href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Yakin ingin menghapus anggota ini? Semua data peminjaman & ulasan terkait akan ikut terhapus.')"><button class="btn btn-sm btn-danger">Hapus</button></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($tab === 'ulasan'): ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Rating</th>
                        <th>Komentar</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) === 0): ?>
                        <tr><td colspan="5" class="empty-state">Belum ada ulasan.</td></tr>
                    <?php else: foreach ($reviews as $r): ?>
                        <tr>
                            <td><?= clean($r['user_name']) ?></td>
                            <td><?= clean($r['book_title']) ?></td>
                            <td class="rating-stars"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></td>
                            <td><?= clean($r['comment']) ?></td>
                            <td><?= clean($r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

<footer>
    &copy; <?= date('Y') ?> Perpustakaan Online. Semua hak cipta dilindungi.
</footer>

</body>
</html>

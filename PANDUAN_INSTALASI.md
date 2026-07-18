# Perpustakaan Online — Panduan Instalasi (XAMPP)

Website sistem perpustakaan online dengan PHP + MySQL, lengkap dengan
login/register, role admin & user, serta fitur pinjam buku & ulasan.

## Struktur Folder

```
perpustakaan/
├── index.php               -> Beranda publik (katalog buku)
├── login.php                -> Halaman login
├── register.php             -> Halaman daftar akun baru
├── logout.php                -> Proses logout (hapus session)
├── dashboard_admin.php     -> Dashboard admin (CRUD buku, kelola peminjaman/anggota/ulasan)
├── dashboard_user.php       -> Dashboard user (katalog, pinjam buku, riwayat, ulasan)
├── config.php                 -> Koneksi database & helper session
├── assets/
│   ├── css/style.css        -> Semua styling website
│   └── img/                  -> Folder gambar (opsional)
└── database/
    └── perpustakaan.sql     -> File SQL lengkap (struktur + data contoh)
```

## Langkah Instalasi

1. **Salin folder project**
   Copy seluruh folder `perpustakaan` ke dalam folder `htdocs` XAMPP, contoh:
   `C:\xampp\htdocs\perpustakaan`

2. **Aktifkan Apache & MySQL**
   Buka XAMPP Control Panel, klik **Start** pada modul **Apache** dan **MySQL**.

3. **Import Database**
   - Buka browser, akses `http://localhost/phpmyadmin`
   - Klik tab **Import**
   - Pilih file `database/perpustakaan.sql`
   - Klik **Go / Kirim**
   - Database `perpustakaan` beserta tabel, relasi, dan data contoh akan
     otomatis terbuat.

   Alternatif lewat SQL tab: buka phpMyAdmin, buat database baru bernama
   `perpustakaan`, lalu pada tab SQL, copy-paste seluruh isi file
   `perpustakaan.sql` lalu jalankan (Go).

4. **Cek Konfigurasi Database**
   Buka `config.php`, pastikan sudah sesuai pengaturan default XAMPP:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'perpustakaan');
   ```
   Jika MySQL XAMPP Anda memakai password custom, sesuaikan `DB_PASS`.

5. **Jalankan Website**
   Buka browser dan akses:
   ```
   http://localhost/perpustakaan/
   ```

## Akun Bawaan (Default)

| Role  | Email               | Password  |
|-------|---------------------|-----------|
| Admin | admin@perpus.com    | admin123  |
| User  | budi@mail.com       | user123   |

Anda juga bisa mendaftar akun user baru sendiri lewat halaman **Register**.

## Alur Fitur

**Publik (belum login):**
- Melihat beranda & katalog buku
- Register akun baru
- Login

**User (setelah login):**
- Melihat katalog buku lengkap dengan status stok
- Meminjam buku (stok otomatis berkurang)
- Melihat riwayat peminjaman sendiri (status: dipinjam / dikembalikan)
- Memberi ulasan & rating untuk buku yang pernah dipinjam
- Logout

**Admin (setelah login):**
- Melihat statistik ringkas (jumlah buku, anggota, peminjaman aktif, ulasan)
- Tambah, edit, hapus data buku
- Melihat seluruh data peminjaman semua user & menandai buku sebagai
  "dikembalikan" (stok otomatis bertambah kembali)
- Melihat & menghapus data anggota (user)
- Melihat seluruh ulasan yang diberikan user
- Logout

## Keamanan yang Diterapkan

- Password disimpan dengan `password_hash()` (bcrypt), diverifikasi dengan `password_verify()`
- Login memakai PHP Session (`$_SESSION`)
- Semua halaman dashboard dilindungi `requireLogin()` / `requireAdmin()` —
  tidak bisa diakses tanpa login atau tanpa role yang sesuai
- Input form dibersihkan dengan `htmlspecialchars()` untuk mencegah XSS dasar
- Semua query database memakai **Prepared Statement** (mysqli) untuk mencegah SQL Injection
- Logout menghapus seluruh data session (`session_destroy()`)

## Troubleshooting

- **"Koneksi database gagal"** → pastikan modul MySQL di XAMPP sudah Start
  dan database `perpustakaan` sudah diimport.
- **Halaman blank / error 500** → pastikan versi PHP di XAMPP minimal PHP 7.4
  (disarankan PHP 8.x), karena project menggunakan fungsi `password_hash`,
  `fetch_all`, dan null coalescing (`??`).
- **CSS tidak muncul** → pastikan mengakses website lewat
  `http://localhost/perpustakaan/` (bukan langsung membuka file .php),
  agar path `assets/css/style.css` terbaca dengan benar.

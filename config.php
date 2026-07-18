<?php
/**
 * config.php
 * File konfigurasi koneksi database & session.
 * Wajib di-include di paling atas setiap halaman PHP.
 */

// Mulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------- KONFIGURASI DATABASE -----------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       // default XAMPP: password kosong
define('DB_NAME', 'perpustakaan');

// Koneksi menggunakan MySQLi
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($koneksi->connect_error) {
    die('Koneksi database gagal: ' . $koneksi->connect_error .
        '<br>Pastikan MySQL di XAMPP aktif dan database "perpustakaan" sudah diimport.');
}
$koneksi->set_charset('utf8mb4');

// ----------- HELPER FUNCTIONS -----------

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah user yang login adalah admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Redirect ke halaman lain lalu hentikan eksekusi
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Wajibkan login, jika belum login redirect ke login.php
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Wajibkan role admin, jika bukan admin redirect ke dashboard_user.php
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('dashboard_user.php');
    }
}

/**
 * Membersihkan input string dari karakter berbahaya (XSS dasar)
 */
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

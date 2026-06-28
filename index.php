<?php
/**
 * Gerbang utama aplikasi Panglong ERP
 * Mengarahkan ke dashboard jika sudah login, atau ke halaman login jika belum
 */

session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    // Sudah login - arahkan ke dashboard
    header('Location: frontend/index.php');
    exit;
}

// Belum login - arahkan ke halaman login
header('Location: frontend/login.php');
exit;

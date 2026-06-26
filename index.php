<?php
// Root index.php — gerbang utama aplikasi Panglong ERP
// Mengarahkan ke frontend/login.php atau frontend/index.php

session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    // Sudah login → arahkan ke dashboard frontend
    header('Location: frontend/index.php');
    exit;
}

// Belum login → arahkan ke halaman login
header('Location: frontend/login.php');
exit;

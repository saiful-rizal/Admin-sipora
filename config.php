<?php
// config.php

// Aktifkan mode exception untuk MySQLi agar error bisa ditangkap dengan try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sipora_db'); // Nama database sesuai skema Anda

// Mencoba membuat koneksi ke database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    // Set charset ke utf8mb4
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
    die("Koneksi database gagal: " . $e->getMessage());
}
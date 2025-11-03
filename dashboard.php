<?php
session_start();
require_once "config.php";

// ===================================================================
// PERINGATAN: PEMERIKSAAN LOGIN DINONAKTIFKAN
// ===================================================================
// Hapus komentar pada blok di bawah ini untuk mengaktifkan kembali
// pemeriksaan login di lingkungan produksi.
//
// if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login.php');
//     exit();
// }
// ===================================================================

class Dashboard {
    private $conn;
    
    public function __construct($db_connection) {
        if ($db_connection instanceof mysqli) {
            $this->conn = $db_connection;
        } else {
            throw new Exception("Koneksi database tidak valid");
        }
    }
    
    public function getTotalMahasiswa() {
        try {
            $query = "SELECT COUNT(*) as total FROM users WHERE role = 'mahasiswa'";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalMahasiswa: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalAdmin() {
        try {
            $query = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalAdmin: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalJurusan() {
        try {
            $query = "SELECT COUNT(*) as total FROM jurusan";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalJurusan: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalProdi() {
        try {
            $query = "SELECT COUNT(*) as total FROM prodi";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalProdi: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalDokumen() {
        try {
            $query = "SELECT COUNT(*) as total FROM dokumen";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalDokumen: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalDokumenByStatus($status) {
        try {
            $query = "SELECT COUNT(*) as total FROM dokumen WHERE status_dokumen = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Query error: " . $stmt->error);
            }
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalDokumenByStatus: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalDokumenByKategori($kategori) {
        try {
            $query = "SELECT COUNT(*) as total FROM dokumen d 
                      JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori 
                      WHERE k.nama_kategori = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $kategori);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Query error: " . $stmt->error);
            }
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalDokumenByKategori: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTotalLibrary() {
        try {
            $query = "SELECT COUNT(*) as total FROM library_mahasiswa";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalLibrary: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getLatestActivities() {
        try {
            $query = "SELECT 'User' as type, nama_lengkap as name, 'Ditambahkan' as action, created_at 
                      FROM users 
                      UNION ALL
                      SELECT 'Dokumen' as type, judul as name, 'Diunggah' as action, tanggal_upload as created_at 
                      FROM dokumen 
                      UNION ALL
                      SELECT 'Library' as type, d.judul as name, 'Ditambahkan ke library' as action, l.tanggal_masuk as created_at 
                      FROM library_mahasiswa l
                      JOIN dokumen d ON l.id_dokumen = d.id_dokumen
                      ORDER BY created_at DESC 
                      LIMIT 5";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            return $activities;
        } catch (Exception $e) {
            error_log("Error getLatestActivities: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMahasiswaPerProdi() {
        try {
            // PERHATIAN: Query ini mengasumsikan ada kolom id_prodi di tabel users.
            // Jika kolom ini tidak ada, query ini akan gagal.
            // Solusi: Jalankan perintah SQL berikut di database Anda:
            // ALTER TABLE `users` ADD COLUMN `id_prodi` INT NULL AFTER `status`;
            $query = "SELECT p.nama_prodi, j.nama_jurusan, COUNT(u.id_user) as total 
                      FROM prodi p 
                      LEFT JOIN jurusan j ON p.id_jurusan = j.id_jurusan
                      LEFT JOIN users u ON p.id_prodi = u.id_prodi AND u.role = 'mahasiswa'
                      GROUP BY p.id_prodi 
                      ORDER BY total DESC";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getMahasiswaPerProdi: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDokumenPerJurusan() {
        try {
            $query = "SELECT j.nama_jurusan, 
                             COUNT(DISTINCT d.id_dokumen) as total_dokumen,
                             COUNT(DISTINCT CASE WHEN k.nama_kategori = 'Skripsi' THEN d.id_dokumen END) as total_skripsi,
                             COUNT(DISTINCT CASE WHEN k.nama_kategori = 'Tugas Akhir' THEN d.id_dokumen END) as total_tugas_akhir,
                             COUNT(DISTINCT CASE WHEN k.nama_kategori = 'Tesis' THEN d.id_dokumen END) as total_tesis,
                             COUNT(DISTINCT CASE WHEN k.nama_kategori = 'Disertasi' THEN d.id_dokumen END) as total_disertasi,
                             COUNT(DISTINCT CASE WHEN k.nama_kategori = 'Penelitian' THEN d.id_dokumen END) as total_penelitian
                      FROM jurusan j
                      LEFT JOIN distribusi_dokumen dd ON j.id_jurusan = dd.id_jurusan
                      LEFT JOIN dokumen d ON dd.id_dokumen = d.id_dokumen
                      LEFT JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori
                      GROUP BY j.id_jurusan
                      ORDER BY j.nama_jurusan";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getDokumenPerJurusan: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingUsers() {
        try {
            // Menggunakan view yang sudah ada di database
            $query = "SELECT * FROM v_mahasiswa_pending ORDER BY created_at DESC LIMIT 5";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getPendingUsers: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDocumentsPendingReview() {
        try {
            // Menggunakan view yang sudah ada di database
            $query = "SELECT * FROM v_dokumen_sedang_dikoreksi ORDER BY tanggal_upload DESC LIMIT 5";
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        } catch (Exception $e) {
            error_log("Error getDocumentsPendingReview: " . $e->getMessage());
            return [];
        }
    }
}

try {
    $dashboard = new Dashboard($conn);
    
    $totalMahasiswa = $dashboard->getTotalMahasiswa();
    $totalAdmin = $dashboard->getTotalAdmin();
    $totalJurusan = $dashboard->getTotalJurusan();
    $totalProdi = $dashboard->getTotalProdi();
    $totalDokumen = $dashboard->getTotalDokumen();
    $totalDokumenPending = $dashboard->getTotalDokumenByStatus('sedang_dikoreksi');
    $totalDokumenApproved = $dashboard->getTotalDokumenByStatus('berhasil');
    $totalDokumenRejected = $dashboard->getTotalDokumenByStatus('gagal');
    $totalSkripsi = $dashboard->getTotalDokumenByKategori('Skripsi');
    $totalTugasAkhir = $dashboard->getTotalDokumenByKategori('Tugas Akhir');
    $totalTesis = $dashboard->getTotalDokumenByKategori('Tesis');
    $totalDisertasi = $dashboard->getTotalDokumenByKategori('Disertasi');
    $totalPenelitian = $dashboard->getTotalDokumenByKategori('Penelitian');
    $totalLibrary = $dashboard->getTotalLibrary();
    $latestActivities = $dashboard->getLatestActivities();
    $mahasiswaPerProdi = $dashboard->getMahasiswaPerProdi();
    $dokumenPerJurusan = $dashboard->getDokumenPerJurusan();
    $pendingUsers = $dashboard->getPendingUsers();
    $documentsPendingReview = $dashboard->getDocumentsPendingReview();
} catch (Exception $e) {
    error_log("Dashboard initialization error: " . $e->getMessage());
    // Set default values to prevent errors in the view
    $totalMahasiswa = 0; $totalAdmin = 0; $totalJurusan = 0; $totalProdi = 0;
    $totalDokumen = 0; $totalDokumenPending = 0; $totalDokumenApproved = 0;
    $totalDokumenRejected = 0; $totalSkripsi = 0; $totalTugasAkhir = 0; $totalTesis = 0;
    $totalDisertasi = 0; $totalPenelitian = 0; $totalLibrary = 0;
    $latestActivities = []; $mahasiswaPerProdi = []; $dokumenPerJurusan = [];
    $pendingUsers = []; $documentsPendingReview = [];
}

function isCurrentPage($page) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    return $currentFile === $page;
}

// Tambahkan variabel untuk menangani username yang tidak ada di session
 $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Tamu';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Sistem Informasi Perpustakaan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary-color: #03a9f4;
      --primary-dark: #0288d1;
      --primary-light: #4fc3f7;
      --bg-dark: #f5f5f5;
      --bg-card: #ffffff;
      --bg-hover: #e3f2fd;
      --text-light: #212121;
      --text-muted: #757575;
      --border-color: #e0e0e0;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg-dark);
      color: var(--text-light);
      min-height: 100vh;
    }

    .top-navbar {
      background: var(--bg-card);
      border-bottom: 2px solid var(--primary-color);
      padding: 12px 0;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      text-decoration: none;
      margin-left: -10px;
    }

    .brand-logo {
      width: 35px;
      height: 35px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .brand-logo-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .brand-text {
      font-weight: 700;
      font-size: 20px;
      color: var(--primary-color);
      letter-spacing: -0.5px;
    }

    .search-container {
      position: relative;
      max-width: 400px;
      flex: 1;
      margin: 0 30px;
    }

    .search-input {
      width: 100%;
      padding: 10px 18px 10px 45px;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      background: var(--bg-hover);
      color: var(--text-light);
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--primary-color);
      background: white;
      box-shadow: 0 0 0 2px rgba(3, 169, 244, 0.2);
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
    }

    .navbar-actions {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .notification-icon, .messages-icon {
      position: relative;
      color: var(--text-muted);
      font-size: 20px;
      cursor: pointer;
      transition: color 0.2s;
    }

    .notification-icon:hover, .messages-icon:hover {
      color: var(--primary-color);
    }

    .badge-notification {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #f44336;
      color: white;
      font-size: 10px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 15px;
      background: var(--bg-hover);
      border-radius: 20px;
      border: 1px solid var(--border-color);
      cursor: pointer;
    }

    .user-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 12px;
    }

    .user-name {
      font-weight: 600;
      color: var(--text-light);
      font-size: 14px;
    }

    .dropdown-menu {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 8px 0;
      min-width: 220px;
    }

    .dropdown-item {
      color: var(--text-light);
      padding: 10px 20px;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .dropdown-item:hover {
      background: var(--bg-hover);
      color: var(--primary-color);
    }

    .dropdown-item i {
      font-size: 16px;
      width: 20px;
      text-align: center;
    }

    .dropdown-divider {
      border-color: var(--border-color);
      margin: 8px 0;
    }

    .notification-dropdown {
      width: 320px;
      padding: 0;
    }

    .notification-header {
      padding: 12px 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .notification-title {
      font-weight: 600;
      margin: 0;
      font-size: 16px;
    }

    .notification-list {
      max-height: 300px;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: var(--primary-color) var(--bg-hover);
    }

    .notification-list::-webkit-scrollbar {
      width: 6px;
    }

    .notification-list::-webkit-scrollbar-track {
      background: var(--bg-hover);
    }

    .notification-list::-webkit-scrollbar-thumb {
      background-color: var(--primary-color);
      border-radius: 3px;
    }

    .notification-item {
      padding: 12px 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      gap: 12px;
      transition: background 0.2s;
    }

    .notification-item:hover {
      background: var(--bg-hover);
    }

    .notification-item:last-child {
      border-bottom: none;
    }

    .notification-icon-small {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .notification-content {
      flex: 1;
    }

    .notification-text {
      font-size: 14px;
      margin: 0 0 4px 0;
    }

    .notification-time {
      font-size: 12px;
      color: var(--text-muted);
    }

    .notification-footer {
      padding: 10px 20px;
      text-align: center;
      border-top: 1px solid var(--border-color);
    }

    .notification-footer a {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
    }

    .main-container {
      display: flex;
      margin-top: 65px;
      min-height: calc(100vh - 65px);
    }

    .sidebar {
      width: 250px;
      background: var(--bg-card);
      padding: 20px 0;
      border-right: 1px solid var(--border-color);
      position: fixed;
      top: 65px;
      left: 0;
      bottom: 0;
      height: calc(100vh - 65px);
      overflow-y: auto;
      z-index: 999;
      scrollbar-width: thin;
      scrollbar-color: var(--primary-color) var(--bg-hover);
      transition: transform 0.3s ease;
    }

    .sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
      background: var(--bg-hover);
    }

    .sidebar::-webkit-scrollbar-thumb {
      background-color: var(--primary-color);
      border-radius: 3px;
    }

    .sidebar-menu {
      padding: 0 15px;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .menu-section {
      margin-bottom: 25px;
    }

    .menu-title {
      color: var(--text-muted);
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin: 0 0 10px 15px;
    }

    .menu-item {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      margin: 3px 0;
      border-radius: 8px;
      text-decoration: none;
      color: var(--text-muted);
      transition: all 0.2s ease;
      font-weight: 500;
      font-size: 14px;
      border-left: 3px solid transparent;
    }

    .menu-item:hover {
      background: var(--bg-hover);
      color: var(--primary-color);
      border-left-color: var(--primary-color);
      transform: translateX(3px);
    }

    .menu-item.active {
      background: var(--bg-hover);
      color: var(--primary-color);
      border-left-color: var(--primary-color);
    }

    .menu-item i {
      margin-right: 10px;
      font-size: 16px;
      width: 18px;
      text-align: center;
    }

    .logout-item {
      color: #f44336 !important;
      margin-top: auto;
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
    }

    .logout-item:hover {
      color: #ff6b6b !important;
      border-left-color: #f44336;
    }

    .main-content {
      flex: 1;
      padding: 25px;
      background: var(--bg-dark);
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: var(--primary-color) var(--bg-hover);
      margin-left: 250px;
      transition: margin-left 0.3s ease;
    }

    .main-content::-webkit-scrollbar {
      width: 8px;
    }

    .main-content::-webkit-scrollbar-track {
      background: var(--bg-hover);
    }

    .main-content::-webkit-scrollbar-thumb {
      background-color: var(--primary-color);
      border-radius: 4px;
    }

    .content-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .page-title {
      font-size: 28px;
      font-weight: 600;
      color: var(--primary-color);
      margin: 0;
    }

    .page-subtitle {
      color: var(--text-muted);
      font-size: 14px;
      margin-top: 5px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 25px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: var(--accent-color);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      border-color: var(--accent-color);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .stat-icon {
      width: 45px;
      height: 45px;
      border-radius: 10px;
      background: var(--accent-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      margin-bottom: 15px;
    }

    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: var(--text-light);
      margin-bottom: 5px;
      line-height: 1;
    }

    .stat-label {
      color: var(--text-muted);
      font-size: 14px;
      font-weight: 500;
      margin: 0;
    }

    .stat-trend {
      position: absolute;
      top: 15px;
      right: 15px;
      background: rgba(3, 169, 244, 0.1);
      color: var(--primary-color);
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
    }

    .content-row {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      margin-top: 25px;
    }

    .content-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 25px;
    }

    .card-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-light);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .chart-container {
      width: 100%;
      height: 250px;
      position: relative;
    }

    .activity-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid var(--border-color);
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-time {
      background: var(--primary-color);
      color: white;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      margin-right: 12px;
      min-width: 60px;
      text-align: center;
    }

    .activity-text {
      color: var(--text-light);
      font-weight: 500;
      font-size: 14px;
    }

    .quick-actions {
      display: flex;
      gap: 8px;
      margin-bottom: 25px;
      align-items: center;
      margin-top: -28px;
      flex-wrap: wrap;
    }

    .action-btn {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      color: var(--text-light);
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      min-height: 28px;
      line-height: 1;
      vertical-align: middle;
      white-space: nowrap;
      position: relative;
      overflow: hidden;
    }

    .action-btn:hover {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
      text-decoration: none;
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(3, 169, 244, 0.3);
    }

    .action-btn.primary {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
      font-weight: 600;
    }
    .action-btn.primary:hover {
      background: var(--primary-light);
      transform: translateY(-1px);
      box-shadow: 0 3px 12px rgba(3, 169, 244, 0.4);
    }

    .nav-tabs {
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 20px;
      margin-top: -30px;
    }

    .nav-tabs .nav-link {
      color: var(--text-muted);
      border: none;
      border-bottom: 2px solid transparent;
      padding: 10px 15px;
      font-weight: 500;
    }

    .nav-tabs .nav-link:hover {
      color: var(--text-light);
      border-bottom-color: var(--border-color);
    }

    .nav-tabs .nav-link.active {
      color: var(--primary-color);
      background: transparent;
      border-bottom-color: var(--primary-color);
    }

    .tab-content {
      background: transparent;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
    }

    .data-table th {
      background: var(--bg-hover);
      color: var(--primary-color);
      font-weight: 600;
      text-align: left;
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .data-table td {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .data-table tr:hover {
      background: var(--bg-hover);
    }

    .badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
    }

    .badge-success {
      background: rgba(76, 175, 80, 0.2);
      color: #4caf50;
    }

    .badge-warning {
      background: rgba(255, 193, 7, 0.2);
      color: var(--primary-color);
    }

    .badge-danger {
      background: rgba(244, 67, 54, 0.2);
      color: #f44336;
    }

    .sidebar-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--primary-color);
      font-size: 24px;
      cursor: pointer;
    }

    @media (max-width: 992px) {
      .content-row {
        grid-template-columns: 1fr;
      }
      
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .sidebar-toggle {
        display: block;
      }
      
      .sidebar {
        transform: translateX(-100%);
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .search-container {
        display: none;
      }
      
      .brand-text {
        display: none;
      }
      
      .notification-dropdown {
        width: 280px;
      }
      
      .quick-actions {
        flex-direction: column;
        align-items: stretch;
      }
      
      .action-btn {
        justify-content: center;
      }
      
      /* Mobile stats grid - 3 columns */
      .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
      }
      
      .stat-card {
        padding: 15px;
      }
      
      .stat-icon {
        width: 35px;
        height: 35px;
        font-size: 16px;
        margin-bottom: 10px;
      }
      
      .stat-number {
        font-size: 24px;
      }
      
      .stat-label {
        font-size: 12px;
      }
      
      .stat-trend {
        font-size: 9px;
        padding: 2px 6px;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .navbar-content {
        padding: 0 15px;
      }
      
      .user-name {
        display: none;
      }
      
      .data-table {
        font-size: 14px;
      }
      
      .data-table th,
      .data-table td {
        padding: 8px 10px;
      }
    }
  </style>
</head>
<body>

  <nav class="top-navbar">
    <div class="navbar-content">
      <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
      </button>
      
      <a href="dashboard.php" class="brand">
        <div class="brand-logo">
          <img src="assets/ic_polije.png" alt="Polije Logo" class="brand-logo-img">
        </div>
        <div class="brand-text">SIPORA POLIJE</div>
      </a>
      
      <div class="search-container">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Cari data...">
      </div>
      
      <div class="navbar-actions">
        <div class="dropdown">
          <div class="notification-icon" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <span class="badge-notification"><?php echo count($pendingUsers) + count($documentsPendingReview); ?></span>
          </div>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown">
            <div class="notification-header">
              <h6 class="notification-title">Notifikasi</h6>
              <a href="#" class="text-warning">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <?php if (!empty($pendingUsers)): ?>
                <?php foreach ($pendingUsers as $user): ?>
                  <div class="notification-item">
                    <div class="notification-icon-small bg-warning text-dark">
                      <i class="bi bi-person-plus"></i>
                    </div>
                    <div class="notification-content">
                      <p class="notification-text">User <?php echo htmlspecialchars($user['nama_lengkap']); ?> menunggu persetujuan</p>
                      <span class="notification-time"><?php echo date('H:i', strtotime($user['created_at'])); ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
              
              <?php if (!empty($documentsPendingReview)): ?>
                <?php foreach ($documentsPendingReview as $doc): ?>
                  <div class="notification-item">
                    <div class="notification-icon-small bg-info text-white">
                      <i class="bi bi-file-text"></i>
                    </div>
                    <div class="notification-content">
                      <p class="notification-text">Dokumen "<?php echo htmlspecialchars($doc['judul']); ?>" menunggu verifikasi</p>
                      <span class="notification-time"><?php echo date('H:i', strtotime($doc['tanggal_upload'])); ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
              
              <?php if (empty($pendingUsers) && empty($documentsPendingReview)): ?>
                <div class="notification-item">
                  <div class="notification-content">
                    <p class="notification-text">Tidak ada notifikasi baru</p>
                  </div>
                </div>
              <?php endif; ?>
            </div>
            <div class="notification-footer">
              <a href="#">Lihat semua notifikasi</a>
            </div>
          </div>
        </div>
        
        <div class="dropdown">
          <div class="messages-icon" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-envelope"></i>
            <span class="badge-notification">0</span>
          </div>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown">
            <div class="notification-header">
              <h6 class="notification-title">Pesan</h6>
              <a href="#" class="text-warning">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <div class="notification-item">
                <div class="notification-content">
                  <p class="notification-text">Tidak ada pesan baru</p>
                </div>
              </div>
            </div>
            <div class="notification-footer">
              <a href="#">Lihat semua pesan</a>
            </div>
          </div>
        </div>
        
        <div class="dropdown">
          <div class="user-info" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
            <span class="user-name">Halo, <?php echo htmlspecialchars($username); ?></span>
            <i class="bi bi-chevron-down" style="font-size: 12px; margin-left: 5px;"></i>
          </div>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle"></i> Profil Saya</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-shield-lock"></i> Keamanan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-question-circle"></i> Bantuan</a></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="main-container">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-menu">
        <div class="menu-section">
          <div class="menu-title">Menu Utama</div>
          <a href="dashboard.php" class="menu-item <?php echo isCurrentPage('dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
          </a>
          <a href="users.php" class="menu-item <?php echo isCurrentPage('users.php') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            Users
          </a>
          <a href="jurusan.php" class="menu-item <?php echo isCurrentPage('jurusan.php') ? 'active' : ''; ?>">
            <i class="bi bi-building"></i>
            Jurusan
          </a>
          <a href="prodi.php" class="menu-item <?php echo isCurrentPage('prodi.php') ? 'active' : ''; ?>">
            <i class="bi bi-building"></i>
            Prodi
          </a>
        </div>
        
        <div class="menu-section">
          <div class="menu-title">Perpustakaan</div>
          <a href="dokumen.php" class="menu-item <?php echo isCurrentPage('dokumen.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            Dokumen
          </a>
          <a href="verifikasi_dokumen.php" class="menu-item <?php echo isCurrentPage('verifikasi_dokumen.php') ? 'active' : ''; ?>">
            <i class="bi bi-check-circle"></i>
            Verifikasi Dokumen
          </a>
          <a href="library_mahasiswa.php" class="menu-item <?php echo isCurrentPage('library_mahasiswa.php') ? 'active' : ''; ?>">
            <i class="bi bi-book"></i>
            Library Mahasiswa
          </a>
          <a href="distribusi_dokumen.php" class="menu-item <?php echo isCurrentPage('distribusi_dokumen.php') ? 'active' : ''; ?>">
            <i class="bi bi-share"></i>
            Distribusi Dokumen
          </a>
          <a href="kategori_perpustakaan.php" class="menu-item <?php echo isCurrentPage('kategori_perpustakaan.php') ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i>
            Kategori Perpustakaan
          </a>
        </div>
               
        <div class="menu-section">
          <div class="menu-title">Sistem</div>
          <a href="pengaturan.php" class="menu-item <?php echo isCurrentPage('pengaturan.php') ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i>
            Pengaturan
          </a>
          <a href="backup.php" class="menu-item <?php echo isCurrentPage('backup.php') ? 'active' : ''; ?>">
            <i class="bi bi-database-down"></i>
            Backup
          </a>
          <a href="logout.php" class="menu-item logout-item">
            <i class="bi bi-box-arrow-right"></i>
            Logout
          </a>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="content-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">Selamat datang di Sistem Informasi Perpustakaan</p>
        </div>
        <div class="dropdown">
          <button class="action-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="bi bi-arrow-clockwise"></i> Refresh Data</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-download"></i> Export Data</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-printer"></i> Cetak Laporan</a></li>
          </ul>
        </div>
      </div>

      <div class="quick-actions">
        <button class="action-btn primary" onclick="window.location.href='users.php'">
          <i class="bi bi-plus-circle me-2"></i>Tambah User
        </button>
        <button class="action-btn" onclick="window.location.href='dokumen.php'">
          <i class="bi bi-file-earmark-text me-2"></i>Upload Dokumen
        </button>
        <button class="action-btn" onclick="window.location.href='verifikasi_dokumen.php'">
          <i class="bi bi-check-circle me-2"></i>Verifikasi Dokumen
        </button>
        <button class="action-btn" onclick="window.location.href='distribusi_dokumen.php'">
          <i class="bi bi-share me-2"></i>Distribusi Dokumen
        </button>
        <button class="action-btn">
          <i class="bi bi-envelope me-2"></i>Kirim Pengumuman
        </button>
      </div>

      <div class="stats-grid">
        <div class="stat-card" style="--accent-color: #03a9f4;">
          <div class="stat-trend">+12%</div>
          <div class="stat-icon">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="stat-number"><?php echo $totalMahasiswa; ?></div>
          <p class="stat-label">Total Mahasiswa</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #4caf50;">
          <div class="stat-trend">+3</div>
          <div class="stat-icon">
            <i class="bi bi-person-badge-fill"></i>
          </div>
          <div class="stat-number"><?php echo $totalAdmin; ?></div>
          <p class="stat-label">Total Admin</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #ff9800;">
          <div class="stat-trend">+1</div>
          <div class="stat-icon">
            <i class="bi bi-building"></i>
          </div>
          <div class="stat-number"><?php echo $totalJurusan; ?></div>
          <p class="stat-label">Total Jurusan</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #08f6aa;">
          <div class="stat-trend">+1</div>
          <div class="stat-icon">
            <i class="bi bi-building"></i>
          </div>
          <div class="stat-number"><?php echo $totalProdi; ?></div>
          <p class="stat-label">Total Prodi</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #607d8b;">
          <div class="stat-trend">+7</div>
          <div class="stat-icon">
            <i class="bi bi-file-earmark-text-fill"></i>
          </div>
          <div class="stat-number"><?php echo $totalDokumen; ?></div>
          <p class="stat-label">Total Dokumen</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #ff5722;">
          <div class="stat-trend">+5</div>
          <div class="stat-icon">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="stat-number"><?php echo $totalDokumenPending; ?></div>
          <p class="stat-label">Dokumen Pending</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #00bcd4;">
          <div class="stat-trend">+2</div>
          <div class="stat-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="stat-number"><?php echo $totalDokumenApproved; ?></div>
          <p class="stat-label">Dokumen Disetujui</p>
        </div>
        
        <div class="stat-card" style="--accent-color: #e91e63;">
          <div class="stat-trend">+3</div>
          <div class="stat-icon">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <div class="stat-number"><?php echo $totalDokumenRejected; ?></div>
          <p class="stat-label">Dokumen Ditolak</p>
        </div>
      </div>

      <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="statistik-tab" data-bs-toggle="tab" data-bs-target="#statistik" type="button" role="tab" aria-controls="statistik" aria-selected="true">Statistik</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="aktivitas-tab" data-bs-toggle="tab" data-bs-target="#aktivitas" type="button" role="tab" aria-controls="aktivitas" aria-selected="false">Aktivitas</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="dokumen-tab" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false">Dokumen Perpustakaan</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="mahasiswa-tab" data-bs-toggle="tab" data-bs-target="#mahasiswa" type="button" role="tab" aria-controls="mahasiswa" aria-selected="false">Data Mahasiswa</button>
        </li>
      </ul>
      
      <div class="tab-content" id="dashboardTabsContent">
        <div class="tab-pane fade show active" id="statistik" role="tabpanel" aria-labelledby="statistik-tab">
          <div class="content-row">
            <div class="content-card">
              <h3 class="card-title">
                <i class="bi bi-bar-chart-fill"></i>
                Statistik Mahasiswa Per Prodi
              </h3>
              <div class="chart-container">
                <canvas id="mahasiswaPerProdiChart"></canvas>
              </div>
            </div>
            
            <div class="content-card">
              <h3 class="card-title">
                <i class="bi bi-clock-fill"></i>
                Aktivitas Terbaru
              </h3>
              <?php if (!empty($latestActivities)): ?>
                <?php foreach ($latestActivities as $activity): ?>
                  <div class="activity-item">
                    <div class="activity-time"><?php echo date('H:i', strtotime($activity['created_at'])); ?></div>
                    <div class="activity-text"><?php echo htmlspecialchars($activity['type'] . ' ' . $activity['name'] . ' ' . $activity['action']); ?></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="activity-item">
                  <div class="activity-text">Tidak ada aktivitas terbaru</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <div class="tab-pane fade" id="aktivitas" role="tabpanel" aria-labelledby="aktivitas-tab">
          <div class="content-card">
            <h3 class="card-title">
              <i class="bi bi-list-check"></i>
              Log Aktivitas Sistem
            </h3>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Aktivitas</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>09:15:23</td>
                    <td>Admin</td>
                    <td>Login sistem</td>
                    <td><span class="badge badge-success">Sukses</span></td>
                  </tr>
                  <tr>
                    <td>10:30:45</td>
                    <td>Budi</td>
                    <td>Update data mahasiswa</td>
                    <td><span class="badge badge-success">Sukses</span></td>
                  </tr>
                  <tr>
                    <td>11:45:12</td>
                    <td>Admin</td>
                    <td>Generate laporan</td>
                    <td><span class="badge badge-success">Sukses</span></td>
                  </tr>
                  <tr>
                    <td>13:20:33</td>
                    <td>System</td>
                    <td>Backup database</td>
                    <td><span class="badge badge-success">Sukses</span></td>
                  </tr>
                  <tr>
                    <td>14:35:18</td>
                    <td>Admin</td>
                    <td>Tambah user baru</td>
                    <td><span class="badge badge-warning">Pending</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="dokumen-tab">
          <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="card-title mb-0">
                <i class="bi bi-journal-text"></i>
                Dokumen Perpustakaan Per Jurusan
              </h3>
              <div>
                <button class="action-btn" onclick="window.location.href='dokumen.php'">
                  <i class="bi bi-plus-circle"></i> Tambah Dokumen
                </button>
                <button class="action-btn" onclick="window.location.href='verifikasi_dokumen.php'">
                  <i class="bi bi-check-circle"></i> Verifikasi Dokumen
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Jurusan</th>
                    <th>Total Dokumen</th>
                    <th>Skripsi</th>
                    <th>Tugas Akhir</th>
                    <th>Tesis</th>
                    <th>Disertasi</th>
                    <th>Penelitian</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($dokumenPerJurusan)): ?>
                    <?php foreach ($dokumenPerJurusan as $dokumen): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($dokumen['nama_jurusan']); ?></td>
                        <td><?php echo $dokumen['total_dokumen']; ?></td>
                        <td><?php echo $dokumen['total_skripsi']; ?></td>
                        <td><?php echo $dokumen['total_tugas_akhir']; ?></td>
                        <td><?php echo $dokumen['total_tesis']; ?></td>
                        <td><?php echo $dokumen['total_disertasi']; ?></td>
                        <td><?php echo $dokumen['total_penelitian']; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7">Tidak ada data dokumen perpustakaan</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <div class="tab-pane fade" id="mahasiswa" role="tabpanel" aria-labelledby="mahasiswa-tab">
          <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="card-title mb-0">
                <i class="bi bi-people"></i>
                Data Mahasiswa Terbaru
              </h3>
              <div>
                <button class="action-btn" onclick="window.location.href='users.php'">
                  <i class="bi bi-plus-circle"></i> Tambah Mahasiswa
                </button>
                <button class="action-btn">
                  <i class="bi bi-funnel"></i> Filter
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($pendingUsers)): ?>
                    <?php foreach ($pendingUsers as $user): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($user['nim'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge badge-warning">Pending</span></td>
                        <td>
                          <button class="action-btn" onclick="window.location.href='approve_user.php?id=<?php echo $user['id_user']; ?>'">
                            <i class="bi bi-check-circle"></i> Approve
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5">Tidak ada data mahasiswa pending</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar toggle
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      
      if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('active');
        });
      }
      
      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !sidebarToggle.contains(event.target) && 
            sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
        }
      });
      
      // Search functionality
      const searchInput = document.querySelector('.search-input');
      if (searchInput) {
        searchInput.addEventListener('focus', function() {
          this.parentElement.style.transform = 'scale(1.02)';
        });
        
        searchInput.addEventListener('blur', function() {
          this.parentElement.style.transform = 'scale(1)';
        });
      }

      // Menu item interactions
      const menuItems = document.querySelectorAll('.menu-item');
      menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
          menuItems.forEach(i => i.classList.remove('active'));
          this.classList.add('active');
          
          // Close sidebar on mobile after clicking a menu item
          if (window.innerWidth <= 768) {
            sidebar.classList.remove('active');
          }
        });
      });

      // Card hover effects
      const statCards = document.querySelectorAll('.stat-card');
      statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });

      // Quick action buttons
      const actionBtns = document.querySelectorAll('.action-btn');
      actionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          this.style.transform = 'scale(0.95)';
          setTimeout(() => {
            this.style.transform = 'scale(1)';
          }, 150);
        });
      });

      // Create chart
      const ctx = document.getElementById('mahasiswaPerProdiChart').getContext('2d');
      
      // Data from PHP
      const chartData = <?php
        $data = [];
        $labels = [];
        if (!empty($mahasiswaPerProdi)) {
          foreach ($mahasiswaPerProdi as $row) {
            $labels[] = $row['nama_prodi'] . ' - ' . $row['nama_jurusan'];
            $data[] = $row['total'];
          }
        }
        echo json_encode([
          'labels' => $labels,
          'data' => $data
        ]);
      ?>;
      
      const chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: chartData.labels,
          datasets: [{
            label: 'Jumlah Mahasiswa',
            data: chartData.data,
            backgroundColor: 'rgba(3, 169, 244, 0.6)',
            borderColor: 'rgba(3, 169, 244, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                color: '#757575'
              }
            },
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                color: '#757575'
              }
            }
          },
          plugins: {
            legend: {
              labels: {
                color: '#757575'
              }
            }
          }
        }
      });
    });
  </script>
</body>
</html>
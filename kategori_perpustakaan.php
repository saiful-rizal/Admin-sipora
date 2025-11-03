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

class KategoriPerpustakaanManager {
    private $conn;
    
    public function __construct($db_connection) {
        if ($db_connection instanceof mysqli) {
            $this->conn = $db_connection;
        } else {
            throw new Exception("Koneksi database tidak valid");
        }
    }
    
    public function getKategori() {
        try {
            $query = "SELECT * FROM kategori_perpustakaan ORDER BY nama_kategori ASC";
            $result = $this->conn->query($query);
            
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            
            $kategori = [];
            while ($row = $result->fetch_assoc()) {
                $kategori[] = $row;
            }
            return $kategori;
        } catch (Exception $e) {
            error_log("Error getKategori: " . $e->getMessage());
            return [];
        }
    }
    
    public function getKategoriById($id) {
        try {
            $query = "SELECT * FROM kategori_perpustakaan WHERE id_kategori = ?";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $kategori = $result->fetch_assoc();
            $stmt->close();
            
            return $kategori;
        } catch (Exception $e) {
            error_log("Error getKategoriById: " . $e->getMessage());
            return null;
        }
    }
    
    public function addKategori($nama_kategori) {
        try {
            // Cek apakah kategori sudah ada
            $checkQuery = "SELECT id_kategori FROM kategori_perpustakaan WHERE nama_kategori = ?";
            $stmt = $this->conn->prepare($checkQuery);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("s", $nama_kategori);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Kategori sudah ada'];
            }
            $stmt->close();
            
            // Tambah kategori baru
            $query = "INSERT INTO kategori_perpustakaan (nama_kategori) VALUES (?)";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("s", $nama_kategori);
            $result = $stmt->execute();
            $stmt->close();
            
            return ['success' => $result, 'message' => $result ? 'Kategori berhasil ditambahkan' : 'Gagal menambahkan kategori'];
        } catch (Exception $e) {
            error_log("Error addKategori: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
    
    public function updateKategori($id, $nama_kategori) {
        try {
            // Cek apakah kategori sudah ada (kecuali kategori yang sedang diedit)
            $checkQuery = "SELECT id_kategori FROM kategori_perpustakaan WHERE nama_kategori = ? AND id_kategori != ?";
            $stmt = $this->conn->prepare($checkQuery);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("si", $nama_kategori, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Kategori sudah ada'];
            }
            $stmt->close();
            
            // Update kategori
            $query = "UPDATE kategori_perpustakaan SET nama_kategori = ? WHERE id_kategori = ?";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("si", $nama_kategori, $id);
            $result = $stmt->execute();
            $stmt->close();
            
            return ['success' => $result, 'message' => $result ? 'Kategori berhasil diperbarui' : 'Gagal memperbarui kategori'];
        } catch (Exception $e) {
            error_log("Error updateKategori: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
    
    public function deleteKategori($id) {
        try {
            // Cek apakah kategori digunakan oleh dokumen
            $checkQuery = "SELECT COUNT(*) as count FROM dokumen WHERE id_kategori = ?";
            $stmt = $this->conn->prepare($checkQuery);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row['count'] > 0) {
                return ['success' => false, 'message' => 'Kategori tidak dapat dihapus karena digunakan oleh dokumen'];
            }
            
            // Hapus kategori
            $query = "DELETE FROM kategori_perpustakaan WHERE id_kategori = ?";
            $stmt = $this->conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            
            return ['success' => $result, 'message' => $result ? 'Kategori berhasil dihapus' : 'Gagal menghapus kategori'];
        } catch (Exception $e) {
            error_log("Error deleteKategori: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}

// Proses form submission
 $message = '';
 $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $kategoriManager = new KategoriPerpustakaanManager($conn);
        
        if (isset($_POST['action']) && $_POST['action'] == 'add') {
            $nama_kategori = trim($_POST['nama_kategori']);
            $result = $kategoriManager->addKategori($nama_kategori);
            
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'danger';
            header('Location: kategori_perpustakaan.php');
            exit();
        }
        
        if (isset($_POST['action']) && $_POST['action'] == 'edit') {
            $id = $_POST['id_kategori'];
            $nama_kategori = trim($_POST['nama_kategori']);
            $result = $kategoriManager->updateKategori($id, $nama_kategori);
            
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'danger';
            header('Location: kategori_perpustakaan.php');
            exit();
        }
        
        if (isset($_POST['action']) && $_POST['action'] == 'delete') {
            $id = $_POST['id_kategori'];
            $result = $kategoriManager->deleteKategori($id);
            
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'warning';
            header('Location: kategori_perpustakaan.php');
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header('Location: kategori_perpustakaan.php');
        exit();
    }
}

// Ambil pesan dari session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get data
try {
    $kategoriManager = new KategoriPerpustakaanManager($conn);
    $kategoriList = $kategoriManager->getKategori();
} catch (Exception $e) {
    error_log("Error initializing KategoriPerpustakaanManager: " . $e->getMessage());
    $kategoriList = [];
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
  <title>Kategori Perpustakaan - Sistem Informasi Perpustakaan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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

    .content-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
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

    .action-btn {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      color: var(--text-light);
      padding: 10px 15px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      white-space: nowrap;
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

    .action-btn.success {
      background: #4caf50;
      color: white;
      border-color: #4caf50;
    }

    .action-btn.success:hover {
      background: #5cbf60;
    }

    .action-btn.warning {
      background: #ff9800;
      color: white;
      border-color: #ff9800;
    }

    .action-btn.warning:hover {
      background: #ffa726;
    }

    .action-btn.danger {
      background: #f44336;
      color: white;
      border-color: #f44336;
    }

    .action-btn.danger:hover {
      background: #ef5350;
    }

    .table-container {
      overflow-x: auto;
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
      white-space: nowrap;
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
      color: #ff9800;
    }

    .badge-danger {
      background: rgba(244, 67, 54, 0.2);
      color: #f44336;
    }

    .badge-info {
      background: rgba(3, 169, 244, 0.2);
      color: var(--primary-color);
    }

    .sidebar-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--primary-color);
      font-size: 24px;
      cursor: pointer;
    }

    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
      border-bottom: 1px solid var(--border-color);
      padding: 20px 25px;
    }

    .modal-title {
      font-weight: 600;
      color: var(--text-light);
    }

    .modal-body {
      padding: 25px;
    }

    .modal-footer {
      border-top: 1px solid var(--border-color);
      padding: 15px 25px;
    }

    .alert {
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 20px;
      border: none;
    }

    .alert-success {
      background: rgba(76, 175, 80, 0.1);
      color: #2e7d32;
    }

    .alert-danger {
      background: rgba(244, 67, 54, 0.1);
      color: #c62828;
    }

    .alert-warning {
      background: rgba(255, 193, 7, 0.1);
      color: #f57f17;
    }

    .table-actions {
      display: flex;
      gap: 5px;
    }

    .btn-action {
      padding: 5px 8px;
      border-radius: 5px;
      border: none;
      background: none;
      color: var(--text-muted);
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-action:hover {
      color: var(--primary-color);
      background: var(--bg-hover);
    }

    .btn-action.edit:hover {
      color: #ff9800;
    }

    .btn-action.delete:hover {
      color: #f44336;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      font-size: 14px;
      color: var(--text-light);
    }

    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      background: var(--bg-card);
      color: var(--text-light);
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgba(3, 169, 244, 0.2);
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
      
      .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .table-container {
        font-size: 14px;
      }
      
      .data-table th,
      .data-table td {
        padding: 8px 10px;
      }
    }

    @media (max-width: 480px) {
      .navbar-content {
        padding: 0 15px;
      }
      
      .user-name {
        display: none;
      }
      
      .table-actions {
        flex-direction: column;
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
            <span class="badge-notification">0</span>
          </div>
          <div class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="bi bi-bell"></i> Notifikasi</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Tidak ada notifikasi baru</a></li>
          </div>
        </div>
        
        <div class="dropdown">
          <div class="messages-icon" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-envelope"></i>
            <span class="badge-notification">0</span>
          </div>
          <div class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#"><i class="bi bi-envelope"></i> Pesan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Tidak ada pesan baru</a></li>
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
          <a href="dashboard.php" class="menu-item">
            <i class="bi bi-speedometer2"></i>
            Dashboard
          </a>
          <a href="users.php" class="menu-item">
            <i class="bi bi-people"></i>
            Users
          </a>
          <a href="jurusan.php" class="menu-item">
            <i class="bi bi-building"></i>
            Jurusan
          </a>
          <a href="prodi.php" class="menu-item">
            <i class="bi bi-building"></i>
            Prodi
          </a>
        </div>
        
        <div class="menu-section">
          <div class="menu-title">Perpustakaan</div>
          <a href="dokumen.php" class="menu-item">
            <i class="bi bi-file-earmark-text"></i>
            Dokumen
          </a>
          <a href="verifikasi_dokumen.php" class="menu-item">
            <i class="bi bi-check-circle"></i>
            Verifikasi Dokumen
          </a>
          <a href="library_mahasiswa.php" class="menu-item">
            <i class="bi bi-book"></i>
            Library Mahasiswa
          </a>
          <a href="distribusi_dokumen.php" class="menu-item">
            <i class="bi bi-share"></i>
            Distribusi Dokumen
          </a>
          <a href="kategori_perpustakaan.php" class="menu-item active">
            <i class="bi bi-tags"></i>
            Kategori Perpustakaan
          </a>
        </div>
               
        <div class="menu-section">
          <div class="menu-title">Sistem</div>
          <a href="pengaturan.php" class="menu-item">
            <i class="bi bi-gear"></i>
            Pengaturan
          </a>
          <a href="backup.php" class="menu-item">
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
          <h1 class="page-title">Kategori Perpustakaan</h1>
          <p class="page-subtitle">Kelola kategori dokumen untuk perpustakaan</p>
        </div>
        <button type="button" class="action-btn primary" data-bs-toggle="modal" data-bs-target="#addKategoriModal">
          <i class="bi bi-plus-circle me-2"></i>Tambah Kategori
        </button>
      </div>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
          <?= $message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="content-card">
        <h3 class="card-title">
          <i class="bi bi-tags"></i>
          Daftar Kategori
        </h3>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Kategori</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($kategoriList)): ?>
                <?php $no = 1; ?>
                <?php foreach ($kategoriList as $kategori): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($kategori['nama_kategori']) ?></td>
                    <td>
                      <div class="table-actions">
                        <button class="btn-action edit" data-bs-toggle="modal" data-bs-target="#editKategoriModal" 
                                data-id="<?= $kategori['id_kategori'] ?>" 
                                data-nama="<?= htmlspecialchars($kategori['nama_kategori']) ?>"
                                title="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-action delete" data-bs-toggle="modal" data-bs-target="#deleteKategoriModal" 
                                data-id="<?= $kategori['id_kategori'] ?>" 
                                data-nama="<?= htmlspecialchars($kategori['nama_kategori']) ?>"
                                title="Hapus">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="text-center">Tidak ada kategori</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Add Kategori Modal -->
  <div class="modal fade" id="addKategoriModal" tabindex="-1" aria-labelledby="addKategoriModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addKategoriModalLabel">Tambah Kategori Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="kategori_perpustakaan.php">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
              <label class="form-label">Nama Kategori</label>
              <input type="text" name="nama_kategori" class="form-control" placeholder="Masukkan nama kategori" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Kategori Modal -->
  <div class="modal fade" id="editKategoriModal" tabindex="-1" aria-labelledby="editKategoriModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editKategoriModalLabel">Edit Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="kategori_perpustakaan.php">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_kategori" id="editId">
            
            <div class="form-group">
              <label class="form-label">Nama Kategori</label>
              <input type="text" name="nama_kategori" class="form-control" id="editNama" placeholder="Masukkan nama kategori" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Perbarui</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Kategori Modal -->
  <div class="modal fade" id="deleteKategoriModal" tabindex="-1" aria-labelledby="deleteKategoriModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteKategoriModalLabel">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="kategori_perpustakaan.php">
          <div class="modal-body">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_kategori" id="deleteId">
            <p>Apakah Anda yakin ingin menghapus kategori <strong id="deleteNama"></strong>?</p>
            <p class="text-warning">Perhatian: Kategori tidak dapat dihapus jika sedang digunakan oleh dokumen.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn danger">Hapus</button>
          </div>
        </form>
      </div>
    </div>
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

      // Edit Kategori Modal
      const editKategoriModal = document.getElementById('editKategoriModal');
      if (editKategoriModal) {
        editKategoriModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const nama = button.getAttribute('data-nama');
          
          document.getElementById('editId').value = id;
          document.getElementById('editNama').value = nama;
        });
      }
      
      // Delete Kategori Modal
      const deleteKategoriModal = document.getElementById('deleteKategoriModal');
      if (deleteKategoriModal) {
        deleteKategoriModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const nama = button.getAttribute('data-nama');
          
          document.getElementById('deleteId').value = id;
          document.getElementById('deleteNama').textContent = nama;
        });
      }
    });
  </script>
</body>
</html>
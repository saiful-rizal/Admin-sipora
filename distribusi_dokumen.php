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

class DistribusiDokumenManager {
    private $conn;
    
    public function __construct($db_connection) {
        if ($db_connection instanceof mysqli) {
            $this->conn = $db_connection;
        } else {
            throw new Exception("Koneksi database tidak valid");
        }
    }
    
    public function getDistributedDocuments($search = '', $jurusan = '', $prodi = '', $limit = 10, $offset = 0) {
        try {
            $query = "SELECT dd.id_distribusi, dd.tanggal_kirim, d.id_dokumen, d.judul, d.file_nama, d.file_path,
                             u.nama_lengkap as admin_nama, j.nama_jurusan, p.nama_prodi
                      FROM distribusi_dokumen dd
                      JOIN dokumen d ON dd.id_dokumen = d.id_dokumen
                      JOIN users u ON dd.id_admin = u.id_user
                      JOIN jurusan j ON dd.id_jurusan = j.id_jurusan
                      JOIN prodi p ON dd.id_prodi = p.id_prodi
                      WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if (!empty($search)) {
                $query .= " AND (d.judul LIKE ? OR u.nama_lengkap LIKE ? OR d.kata_kunci LIKE ?)";
                $searchParam = "%{$search}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
                $types .= "sss";
            }
            
            if (!empty($jurusan)) {
                $query .= " AND dd.id_jurusan = ?";
                $params[] = $jurusan;
                $types .= "i";
            }
            
            if (!empty($prodi)) {
                $query .= " AND dd.id_prodi = ?";
                $params[] = $prodi;
                $types .= "i";
            }
            
            $query .= " ORDER BY dd.tanggal_kirim DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $distributedDocs = [];
            while ($row = $result->fetch_assoc()) {
                $distributedDocs[] = $row;
            }
            
            $stmt->close();
            return $distributedDocs;
        } catch (Exception $e) {
            error_log("Error getDistributedDocuments: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalDistributedDocuments($search = '', $jurusan = '', $prodi = '') {
        try {
            $query = "SELECT COUNT(*) as total
                      FROM distribusi_dokumen dd
                      JOIN dokumen d ON dd.id_dokumen = d.id_dokumen
                      JOIN users u ON dd.id_admin = u.id_user
                      JOIN jurusan j ON dd.id_jurusan = j.id_jurusan
                      JOIN prodi p ON dd.id_prodi = p.id_prodi
                      WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if (!empty($search)) {
                $query .= " AND (d.judul LIKE ? OR u.nama_lengkap LIKE ? OR d.kata_kunci LIKE ?)";
                $searchParam = "%{$search}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
                $types .= "sss";
            }
            
            if (!empty($jurusan)) {
                $query .= " AND dd.id_jurusan = ?";
                $params[] = $jurusan;
                $types .= "i";
            }
            
            if (!empty($prodi)) {
                $query .= " AND dd.id_prodi = ?";
                $params[] = $prodi;
                $types .= "i";
            }
            
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalDistributedDocuments: " . $e->getMessage());
            return 0;
        }
    }
    
    public function deleteDistribution($id) {
        try {
            $query = "DELETE FROM distribusi_dokumen WHERE id_distribusi = ?";
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("Error deleteDistribution: " . $e->getMessage());
            return false;
        }
    }
    
    public function getVerifiedDocuments() {
        try {
            $query = "SELECT d.id_dokumen, d.judul, d.file_nama, u.nama_lengkap, k.nama_kategori
                      FROM dokumen d
                      JOIN users u ON d.id_user = u.id_user
                      JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori
                      WHERE d.status_dokumen = 'berhasil'
                      ORDER BY d.tanggal_upload DESC";
            
            $result = $this->conn->query($query);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            
            $documents = [];
            while ($row = $result->fetch_assoc()) {
                $documents[] = $row;
            }
            return $documents;
        } catch (Exception $e) {
            error_log("Error getVerifiedDocuments: " . $e->getMessage());
            return [];
        }
    }
    
    public function getJurusan() {
        try {
            $query = "SELECT * FROM jurusan ORDER BY nama_jurusan ASC";
            $result = $this->conn->query($query);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            
            $jurusan = [];
            while ($row = $result->fetch_assoc()) {
                $jurusan[] = $row;
            }
            return $jurusan;
        } catch (Exception $e) {
            error_log("Error getJurusan: " . $e->getMessage());
            return [];
        }
    }
    
    public function getProdi($id_jurusan = null) {
        try {
            $query = "SELECT p.id_prodi, p.nama_prodi, p.id_jurusan, j.nama_jurusan
                      FROM prodi p
                      JOIN jurusan j ON p.id_jurusan = j.id_jurusan";
            
            if ($id_jurusan) {
                $query .= " WHERE p.id_jurusan = ?";
                $stmt = $this->conn->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Prepare statement failed: " . $this->conn->error);
                }
                
                $stmt->bind_param("i", $id_jurusan);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $stmt->close();
            } else {
                $result = $this->conn->query($query);
                if ($result === false) {
                    throw new Exception("Query failed: " . $this->conn->error);
                }
            }
            
            $prodi = [];
            while ($row = $result->fetch_assoc()) {
                $prodi[] = $row;
            }
            return $prodi;
        } catch (Exception $e) {
            error_log("Error getProdi: " . $e->getMessage());
            return [];
        }
    }
    
    public function distributeDocument($id_dokumen, $id_admin, $id_jurusan, $id_prodi) {
        try {
            // Validasi input
            if (empty($id_dokumen) || empty($id_admin) || empty($id_jurusan) || empty($id_prodi)) {
                throw new Exception("Semua field harus diisi");
            }
            
            // Cek apakah dokumen sudah didistribusikan ke jurusan dan prodi yang sama
            $checkQuery = "SELECT id_distribusi FROM distribusi_dokumen 
                          WHERE id_dokumen = ? AND id_jurusan = ? AND id_prodi = ?";
            $stmt = $this->conn->prepare($checkQuery);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("iii", $id_dokumen, $id_jurusan, $id_prodi);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                return false; // Dokumen sudah didistribusikan
            }
            $stmt->close();
            
            // Distribusikan dokumen
            $query = "INSERT INTO distribusi_dokumen (id_dokumen, id_admin, id_jurusan, id_prodi) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("iiii", $id_dokumen, $id_admin, $id_jurusan, $id_prodi);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("Error distributeDocument: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAdminId() {
        try {
            // Jika session tersedia, gunakan ID dari session
            if (isset($_SESSION['id_user']) && $_SESSION['role'] === 'admin') {
                return $_SESSION['id_user'];
            }
            
            // Jika tidak, cari admin pertama di database
            $query = "SELECT id_user FROM users WHERE role = 'admin' LIMIT 1";
            $result = $this->conn->query($query);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['id_user'];
            }
            
            // Jika tidak ada admin, return 1 sebagai default
            return 1;
        } catch (Exception $e) {
            error_log("Error getAdminId: " . $e->getMessage());
            return 1;
        }
    }
}

// Proses form submission
 $message = '';
 $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $distribusiManager = new DistribusiDokumenManager($conn);
        
        if (isset($_POST['action']) && $_POST['action'] == 'distribute') {
            $id_dokumen = $_POST['id_dokumen'] ?? '';
            $id_admin = $distribusiManager->getAdminId();
            $id_jurusan = $_POST['id_jurusan'] ?? '';
            $id_prodi = $_POST['id_prodi'] ?? '';
            
            if ($distribusiManager->distributeDocument($id_dokumen, $id_admin, $id_jurusan, $id_prodi)) {
                $_SESSION['message'] = 'Dokumen berhasil didistribusikan';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Dokumen sudah pernah didistribusikan ke jurusan dan prodi ini';
                $_SESSION['message_type'] = 'warning';
            }
            header('Location: distribusi_dokumen.php');
            exit();
        }
        
        if (isset($_POST['action']) && $_POST['action'] == 'delete') {
            $id = $_POST['id_distribusi'] ?? '';
            if ($distribusiManager->deleteDistribution($id)) {
                $_SESSION['message'] = 'Distribusi dokumen berhasil dihapus';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Gagal menghapus distribusi dokumen';
                $_SESSION['message_type'] = 'danger';
            }
            header('Location: distribusi_dokumen.php');
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header('Location: distribusi_dokumen.php');
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

// Pagination setup
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $limit = 10;
 $offset = ($page - 1) * $limit;

// Filter parameters
 $search = isset($_GET['search']) ? $_GET['search'] : '';
 $jurusan = isset($_GET['jurusan']) ? $_GET['jurusan'] : '';
 $prodi = isset($_GET['prodi']) ? $_GET['prodi'] : '';

// Get data
try {
    $distribusiManager = new DistribusiDokumenManager($conn);
    $distributedDocs = $distribusiManager->getDistributedDocuments($search, $jurusan, $prodi, $limit, $offset);
    $totalItems = $distribusiManager->getTotalDistributedDocuments($search, $jurusan, $prodi);
    $totalPages = ceil($totalItems / $limit);
    $verifiedDocs = $distribusiManager->getVerifiedDocuments();
    $jurusanList = $distribusiManager->getJurusan();
    $prodiList = $distribusiManager->getProdi();
} catch (Exception $e) {
    error_log("Error initializing DistribusiDokumenManager: " . $e->getMessage());
    $distributedDocs = [];
    $totalItems = 0;
    $totalPages = 0;
    $verifiedDocs = [];
    $jurusanList = [];
    $prodiList = [];
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
  <title>Distribusi Dokumen - Sistem Informasi Perpustakaan</title>
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

    .filter-section {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }

    .filter-group {
      flex: 1;
      min-width: 200px;
    }

    .filter-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      font-size: 14px;
      color: var(--text-muted);
    }

    .filter-input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      background: var(--bg-card);
      color: var(--text-light);
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .filter-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgba(3, 169, 244, 0.2);
    }

    .filter-select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23757575' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 16px;
      padding-right: 40px;
    }

    .filter-actions {
      display: flex;
      align-items: flex-end;
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

    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }

    .pagination .page-link {
      color: var(--primary-color);
      border: 1px solid var(--border-color);
      margin: 0 2px;
      border-radius: 5px;
    }

    .pagination .page-item.active .page-link {
      background: var(--primary-color);
      border-color: var(--primary-color);
    }

    .pagination .page-link:hover {
      color: var(--primary-dark);
      background: var(--bg-hover);
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

    .btn-action.view:hover {
      color: #4caf50;
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

    .form-select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23757575' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 16px;
      padding-right: 40px;
    }

    .loading {
      opacity: 0.6;
      pointer-events: none;
    }

    @media (max-width: 992px) {
      .filter-section {
        flex-direction: column;
      }
      
      .filter-actions {
        justify-content: flex-start;
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
          <a href="distribusi_dokumen.php" class="menu-item active">
            <i class="bi bi-share"></i>
            Distribusi Dokumen
          </a>
          <a href="kategori_perpustakaan.php" class="menu-item">
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
          <h1 class="page-title">Distribusi Dokumen</h1>
          <p class="page-subtitle">Kelola distribusi dokumen ke berbagai jurusan dan program studi</p>
        </div>
        <button type="button" class="action-btn primary" data-bs-toggle="modal" data-bs-target="#distributeModal">
          <i class="bi bi-plus-circle me-2"></i>Distribusikan Dokumen
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
          <i class="bi bi-funnel"></i>
          Filter Distribusi
        </h3>
        <form method="GET" action="distribusi_dokumen.php">
          <div class="filter-section">
            <div class="filter-group">
              <label class="filter-label">Pencarian</label>
              <input type="text" name="search" class="filter-input" placeholder="Judul atau admin" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
              <label class="filter-label">Jurusan</label>
              <select name="jurusan" class="filter-input filter-select" id="filterJurusan">
                <option value="">Semua Jurusan</option>
                <?php if (!empty($jurusanList)): ?>
                  <?php foreach ($jurusanList as $j): ?>
                    <option value="<?= $j['id_jurusan'] ?>" <?= $jurusan == $j['id_jurusan'] ? 'selected' : '' ?>><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">Program Studi</label>
              <select name="prodi" class="filter-input filter-select" id="filterProdi">
                <option value="">Semua Prodi</option>
                <?php if (!empty($prodiList)): ?>
                  <?php foreach ($prodiList as $p): ?>
                    <option value="<?= $p['id_prodi'] ?>" <?= $prodi == $p['id_prodi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_prodi']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="filter-actions">
              <button type="submit" class="action-btn">
                <i class="bi bi-search me-2"></i>Cari
              </button>
              <a href="distribusi_dokumen.php" class="action-btn">
                <i class="bi bi-arrow-clockwise me-2"></i>Reset
              </a>
            </div>
          </div>
        </form>
      </div>

      <div class="content-card">
        <h3 class="card-title">
          <i class="bi bi-share"></i>
          Dokumen Terdistribusi
        </h3>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Judul Dokumen</th>
                <th>Admin</th>
                <th>Jurusan</th>
                <th>Program Studi</th>
                <th>Tanggal Kirim</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($distributedDocs)): ?>
                <?php $no = ($page - 1) * $limit + 1; ?>
                <?php foreach ($distributedDocs as $doc): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($doc['judul']) ?></td>
                    <td><?= htmlspecialchars($doc['admin_nama']) ?></td>
                    <td><?= htmlspecialchars($doc['nama_jurusan']) ?></td>
                    <td><?= htmlspecialchars($doc['nama_prodi']) ?></td>
                    <td><?= date('d M Y', strtotime($doc['tanggal_kirim'])) ?></td>
                    <td>
                      <div class="table-actions">
                        <a href="<?= htmlspecialchars($doc['file_path']) ?>" class="btn-action view" target="_blank" title="Lihat/Unduh">
                          <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn-action delete" data-bs-toggle="modal" data-bs-target="#deleteDistribusiModal" 
                                data-id="<?= $doc['id_distribusi'] ?>" 
                                data-judul="<?= htmlspecialchars($doc['judul']) ?>"
                                title="Hapus Distribusi">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center">Tidak ada dokumen yang didistribusikan</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&jurusan=<?= urlencode($jurusan) ?>&prodi=<?= urlencode($prodi) ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&jurusan=<?= urlencode($jurusan) ?>&prodi=<?= urlencode($prodi) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&jurusan=<?= urlencode($jurusan) ?>&prodi=<?= urlencode($prodi) ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Distribute Document Modal -->
  <div class="modal fade" id="distributeModal" tabindex="-1" aria-labelledby="distributeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="distributeModalLabel">Distribusikan Dokumen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="distribusi_dokumen.php" id="distributeForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="distribute">
            
            <div class="form-group">
              <label class="form-label">Pilih Dokumen</label>
              <select name="id_dokumen" class="form-control form-select" required>
                <option value="">-- Pilih Dokumen --</option>
                <?php if (!empty($verifiedDocs)): ?>
                  <?php foreach ($verifiedDocs as $doc): ?>
                    <option value="<?= $doc['id_dokumen'] ?>"><?= htmlspecialchars($doc['judul']) ?> (<?= htmlspecialchars($doc['nama_lengkap']) ?>)</option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Jurusan</label>
              <select name="id_jurusan" class="form-control form-select" id="modalJurusan" required>
                <option value="">-- Pilih Jurusan --</option>
                <?php if (!empty($jurusanList)): ?>
                  <?php foreach ($jurusanList as $j): ?>
                    <option value="<?= $j['id_jurusan'] ?>"><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Program Studi</label>
              <select name="id_prodi" class="form-control form-select" id="modalProdi" required>
                <option value="">-- Pilih Program Studi --</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Distribusikan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Distribution Modal -->
  <div class="modal fade" id="deleteDistribusiModal" tabindex="-1" aria-labelledby="deleteDistribusiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteDistribusiModalLabel">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="distribusi_dokumen.php">
          <div class="modal-body">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_distribusi" id="deleteDistribusiId">
            <p>Apakah Anda yakin ingin menghapus distribusi dokumen <strong id="deleteDistribusiJudul"></strong>?</p>
            <p class="text-danger">Tindakan ini akan menghapus distribusi dokumen, tetapi dokumen asli akan tetap tersimpan.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn danger">Hapus Distribusi</button>
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

      // Delete Distribution Modal
      const deleteDistribusiModal = document.getElementById('deleteDistribusiModal');
      if (deleteDistribusiModal) {
        deleteDistribusiModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const judul = button.getAttribute('data-judul');
          
          document.getElementById('deleteDistribusiId').value = id;
          document.getElementById('deleteDistribusiJudul').textContent = judul;
        });
      }
      
      // Filter Prodi based on Jurusan selection
      const filterJurusan = document.getElementById('filterJurusan');
      const filterProdi = document.getElementById('filterProdi');
      
      if (filterJurusan) {
        filterJurusan.addEventListener('change', function() {
          const jurusanId = this.value;
          updateProdiOptions(jurusanId, filterProdi);
        });
      }
      
      // Modal Prodi based on Jurusan selection
      const modalJurusan = document.getElementById('modalJurusan');
      const modalProdi = document.getElementById('modalProdi');
      
      if (modalJurusan) {
        modalJurusan.addEventListener('change', function() {
          const jurusanId = this.value;
          updateProdiOptions(jurusanId, modalProdi);
        });
      }
      
      // Function to update Prodi options based on Jurusan
      function updateProdiOptions(jurusanId, prodiSelect) {
        // Clear existing options
        prodiSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';
        
        if (!jurusanId) return;
        
        // Show loading state
        prodiSelect.classList.add('loading');
        
        // Fetch prodi data based on jurusan
        fetch('get_prodi.php?jurusan=' + encodeURIComponent(jurusanId))
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            if (data.success && data.prodi) {
              data.prodi.forEach(prodi => {
                const option = document.createElement('option');
                option.value = prodi.id_prodi;
                option.textContent = prodi.nama_prodi;
                prodiSelect.appendChild(option);
              });
            } else {
              console.error('Error in response:', data.message || 'Unknown error');
            }
          })
          .catch(error => {
            console.error('Error fetching prodi data:', error);
            // Fallback: load all prodi if API fails
            loadAllProdi(prodiSelect);
          })
          .finally(() => {
            prodiSelect.classList.remove('loading');
          });
      }
      
      // Fallback function to load all prodi
      function loadAllProdi(prodiSelect) {
        <?php if (!empty($prodiList)): ?>
          const allProdi = <?php echo json_encode($prodiList); ?>;
          allProdi.forEach(prodi => {
            const option = document.createElement('option');
            option.value = prodi.id_prodi;
            option.textContent = prodi.nama_prodi;
            prodiSelect.appendChild(option);
          });
        <?php endif; ?>
      }
      
      // Form submission handling
      const distributeForm = document.getElementById('distributeForm');
      if (distributeForm) {
        distributeForm.addEventListener('submit', function(e) {
          const jurusan = document.getElementById('modalJurusan').value;
          const prodi = document.getElementById('modalProdi').value;
          const dokumen = document.querySelector('select[name="id_dokumen"]').value;
          
          if (!jurusan || !prodi || !dokumen) {
            e.preventDefault();
            alert('Mohon lengkapi semua field yang diperlukan');
            return false;
          }
        });
      }
    });
  </script>
</body>
</html>
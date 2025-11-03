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

class DokumenManager {
    private $conn;
    private $uploadDir = 'uploads/dokumen/';
    
    public function __construct($db_connection) {
        if ($db_connection instanceof mysqli) {
            $this->conn = $db_connection;
        } else {
            throw new Exception("Koneksi database tidak valid");
        }
        
        // Buat folder upload jika belum ada
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    public function getDokumen($search = '', $kategori = '', $status = '', $limit = 10, $offset = 0) {
        try {
            $query = "SELECT d.id_dokumen, d.judul, d.abstrak, d.kata_kunci, d.file_nama, d.tanggal_upload, d.status_dokumen,
                             u.nama_lengkap, u.nim, k.nama_kategori
                      FROM dokumen d
                      JOIN users u ON d.id_user = u.id_user
                      JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori
                      WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if (!empty($search)) {
                $query .= " AND (d.judul LIKE ? OR u.nama_lengkap LIKE ? OR d.kata_kunci LIKE ?)";
                $searchParam = "%{$search}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
                $types .= "sss";
            }
            
            if (!empty($kategori)) {
                $query .= " AND d.id_kategori = ?";
                $params[] = $kategori;
                $types .= "i";
            }
            
            if (!empty($status)) {
                $query .= " AND d.status_dokumen = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            $query .= " ORDER BY d.tanggal_upload DESC LIMIT ? OFFSET ?";
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
            
            $dokumenList = [];
            while ($row = $result->fetch_assoc()) {
                $dokumenList[] = $row;
            }
            
            $stmt->close();
            return $dokumenList;
        } catch (Exception $e) {
            error_log("Error getDokumen: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalDokumen($search = '', $kategori = '', $status = '') {
        try {
            $query = "SELECT COUNT(*) as total
                      FROM dokumen d
                      JOIN users u ON d.id_user = u.id_user
                      JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori
                      WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if (!empty($search)) {
                $query .= " AND (d.judul LIKE ? OR u.nama_lengkap LIKE ? OR d.kata_kunci LIKE ?)";
                $searchParam = "%{$search}%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
                $types .= "sss";
            }
            
            if (!empty($kategori)) {
                $query .= " AND d.id_kategori = ?";
                $params[] = $kategori;
                $types .= "i";
            }
            
            if (!empty($status)) {
                $query .= " AND d.status_dokumen = ?";
                $params[] = $status;
                $types .= "s";
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
            error_log("Error getTotalDokumen: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getDokumenById($id) {
        try {
            $query = "SELECT * FROM dokumen WHERE id_dokumen = ?";
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $dokumen = $result->fetch_assoc();
            $stmt->close();
            return $dokumen;
        } catch (Exception $e) {
            error_log("Error getDokumenById: " . $e->getMessage());
            return null;
        }
    }
    
    public function createDokumen($data, $file) {
        try {
            // Proses upload file
            $fileName = $this->uploadFile($file);
            if (!$fileName) {
                return false;
            }
            
            $query = "INSERT INTO dokumen (id_user, judul, abstrak, kata_kunci, id_kategori, file_nama, file_path, status_dokumen) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("isssisss", 
                $data['id_user'], 
                $data['judul'], 
                $data['abstrak'], 
                $data['kata_kunci'], 
                $data['id_kategori'], 
                $fileName, 
                $this->uploadDir . $fileName,
                $data['status_dokumen']
            );
            
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("Error createDokumen: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateDokumen($id, $data, $file = null) {
        try {
            // Jika ada file baru, hapus yang lama dan upload yang baru
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $dokumenLama = $this->getDokumenById($id);
                if ($dokumenLama && file_exists($dokumenLama['file_path'])) {
                    unlink($dokumenLama['file_path']);
                }
                
                $fileName = $this->uploadFile($file);
                if (!$fileName) {
                    return false;
                }
                
                $query = "UPDATE dokumen SET judul = ?, abstrak = ?, kata_kunci = ?, id_kategori = ?, file_nama = ?, file_path = ?, status_dokumen = ? WHERE id_dokumen = ?";
                $stmt = $this->conn->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Prepare statement failed: " . $this->conn->error);
                }
                
                $stmt->bind_param("sssisssi", 
                    $data['judul'], 
                    $data['abstrak'], 
                    $data['kata_kunci'], 
                    $data['id_kategori'], 
                    $fileName, 
                    $this->uploadDir . $fileName,
                    $data['status_dokumen'],
                    $id
                );
            } else {
                // Update tanpa mengubah file
                $query = "UPDATE dokumen SET judul = ?, abstrak = ?, kata_kunci = ?, id_kategori = ?, status_dokumen = ? WHERE id_dokumen = ?";
                $stmt = $this->conn->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Prepare statement failed: " . $this->conn->error);
                }
                
                $stmt->bind_param("sssssi", 
                    $data['judul'], 
                    $data['abstrak'], 
                    $data['kata_kunci'], 
                    $data['id_kategori'], 
                    $data['status_dokumen'],
                    $id
                );
            }
            
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("Error updateDokumen: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteDokumen($id) {
        try {
            $dokumen = $this->getDokumenById($id);
            if (!$dokumen) {
                return false;
            }
            
            // Hapus file dari server
            if (file_exists($dokumen['file_path'])) {
                unlink($dokumen['file_path']);
            }
            
            // Hapus record dari database
            $query = "DELETE FROM dokumen WHERE id_dokumen = ?";
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare statement failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("Error deleteDokumen: " . $e->getMessage());
            return false;
        }
    }
    
    private function uploadFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $allowedTypes = ['application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['message'] = 'Hanya file PDF yang diizinkan.';
            $_SESSION['message_type'] = 'danger';
            return null;
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $this->uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
        
        return null;
    }
    
    public function getUsers() {
        try {
            $query = "SELECT id_user, nama_lengkap, nim FROM users WHERE role = 'mahasiswa' ORDER BY nama_lengkap ASC";
            $result = $this->conn->query($query);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            return $users;
        } catch (Exception $e) {
            error_log("Error getUsers: " . $e->getMessage());
            return [];
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
}

// Proses form submission
 $message = '';
 $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dokumenManager = new DokumenManager($conn);
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Validasi input
                    if (empty($_POST['judul']) || empty($_POST['id_user']) || empty($_POST['id_kategori']) || empty($_POST['status_dokumen'])) {
                        $_SESSION['message'] = 'Semua field wajib diisi';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: dokumen.php');
                        exit();
                    }
                    
                    // Validasi file
                    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                        $_SESSION['message'] = 'File harus diunggah';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: dokumen.php');
                        exit();
                    }
                    
                    $data = [
                        'id_user' => $_POST['id_user'],
                        'judul' => $_POST['judul'],
                        'abstrak' => $_POST['abstrak'] ?? '',
                        'kata_kunci' => $_POST['kata_kunci'] ?? '',
                        'id_kategori' => $_POST['id_kategori'],
                        'status_dokumen' => $_POST['status_dokumen']
                    ];
                    
                    if ($dokumenManager->createDokumen($data, $_FILES['file'])) {
                        $_SESSION['message'] = 'Dokumen berhasil ditambahkan';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Gagal menambahkan dokumen';
                        $_SESSION['message_type'] = 'danger';
                    }
                    header('Location: dokumen.php');
                    exit();
                    break;
                    
                case 'edit':
                    // Validasi input
                    if (empty($_POST['id_dokumen']) || empty($_POST['judul']) || empty($_POST['id_user']) || empty($_POST['id_kategori']) || empty($_POST['status_dokumen'])) {
                        $_SESSION['message'] = 'Semua field wajib diisi';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: dokumen.php');
                        exit();
                    }
                    
                    $id = $_POST['id_dokumen'];
                    $data = [
                        'id_user' => $_POST['id_user'],
                        'judul' => $_POST['judul'],
                        'abstrak' => $_POST['abstrak'] ?? '',
                        'kata_kunci' => $_POST['kata_kunci'] ?? '',
                        'id_kategori' => $_POST['id_kategori'],
                        'status_dokumen' => $_POST['status_dokumen']
                    ];
                    
                    $file = (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) ? $_FILES['file'] : null;
                    
                    if ($dokumenManager->updateDokumen($id, $data, $file)) {
                        $_SESSION['message'] = 'Dokumen berhasil diperbarui';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Gagal memperbarui dokumen';
                        $_SESSION['message_type'] = 'danger';
                    }
                    header('Location: dokumen.php');
                    exit();
                    break;
                    
                case 'delete':
                    if (empty($_POST['id_dokumen'])) {
                        $_SESSION['message'] = 'ID dokumen tidak valid';
                        $_SESSION['message_type'] = 'danger';
                        header('Location: dokumen.php');
                        exit();
                    }
                    
                    $id = $_POST['id_dokumen'];
                    if ($dokumenManager->deleteDokumen($id)) {
                        $_SESSION['message'] = 'Dokumen berhasil dihapus';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Gagal menghapus dokumen';
                        $_SESSION['message_type'] = 'danger';
                    }
                    header('Location: dokumen.php');
                    exit();
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header('Location: dokumen.php');
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
 $kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
 $status = isset($_GET['status']) ? $_GET['status'] : '';

// Get data
try {
    $dokumenManager = new DokumenManager($conn);
    $dokumenList = $dokumenManager->getDokumen($search, $kategori, $status, $limit, $offset);
    $totalDokumen = $dokumenManager->getTotalDokumen($search, $kategori, $status);
    $totalPages = ceil($totalDokumen / $limit);
    $usersList = $dokumenManager->getUsers();
    $kategoriList = $dokumenManager->getKategori();
} catch (Exception $e) {
    error_log("Error initializing DokumenManager: " . $e->getMessage());
    $dokumenList = [];
    $totalDokumen = 0;
    $totalPages = 0;
    $usersList = [];
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
  <title>Manajemen Dokumen - Sistem Informasi Perpustakaan</title>
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

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
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
      color: #4caf50;
    }

    .btn-action.delete:hover {
      color: #f44336;
    }

    .btn-action.view:hover {
      color: #ff9800;
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
          <a href="dokumen.php" class="menu-item active">
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
          <h1 class="page-title">Manajemen Dokumen</h1>
          <p class="page-subtitle">Kelola dokumen mahasiswa yang diunggah ke sistem</p>
        </div>
        <div>
          <button class="action-btn primary" data-bs-toggle="modal" data-bs-target="#addDokumenModal">
            <i class="bi bi-plus-circle me-2"></i>Upload Dokumen
          </button>
        </div>
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
          Filter Data
        </h3>
        <form method="GET" action="dokumen.php">
          <div class="filter-section">
            <div class="filter-group">
              <label class="filter-label">Pencarian</label>
              <input type="text" name="search" class="filter-input" placeholder="Judul, pengunggah, atau kata kunci" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
              <label class="filter-label">Kategori</label>
              <select name="kategori" class="filter-input filter-select">
                <option value="">Semua Kategori</option>
                <?php if (!empty($kategoriList)): ?>
                  <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?= $kat['id_kategori'] ?>" <?= $kategori == $kat['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">Status</label>
              <select name="status" class="filter-input filter-select">
                <option value="">Semua Status</option>
                <option value="sedang_dikoreksi" <?= $status == 'sedang_dikoreksi' ? 'selected' : '' ?>>Sedang Dikoreksi</option>
                <option value="berhasil" <?= $status == 'berhasil' ? 'selected' : '' ?>>Berhasil</option>
                <option value="gagal" <?= $status == 'gagal' ? 'selected' : '' ?>>Gagal</option>
              </select>
            </div>
            <div class="filter-actions">
              <button type="submit" class="action-btn">
                <i class="bi bi-search me-2"></i>Cari
              </button>
              <a href="dokumen.php" class="action-btn">
                <i class="bi bi-arrow-clockwise me-2"></i>Reset
              </a>
            </div>
          </div>
        </form>
      </div>

      <div class="content-card">
        <h3 class="card-title">
          <i class="bi bi-file-earmark-text"></i>
          Data Dokumen
        </h3>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Judul</th>
                <th>Pengunggah</th>
                <th>Kategori</th>
                <th>Tanggal Upload</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($dokumenList)): ?>
                <?php $no = ($page - 1) * $limit + 1; ?>
                <?php foreach ($dokumenList as $dokumen): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($dokumen['judul']) ?></td>
                    <td><?= htmlspecialchars($dokumen['nama_lengkap']) ?> (<?= htmlspecialchars($dokumen['nim']) ?>)</td>
                    <td><?= htmlspecialchars($dokumen['nama_kategori']) ?></td>
                    <td><?= date('d M Y', strtotime($dokumen['tanggal_upload'])) ?></td>
                    <td>
                      <span class="badge badge-<?= 
                        $dokumen['status_dokumen'] == 'berhasil' ? 'success' : 
                        ($dokumen['status_dokumen'] == 'sedang_dikoreksi' ? 'warning' : 'danger') ?>">
                        <?= htmlspecialchars($dokumen['status_dokumen']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="table-actions">
                        <a href="<?= htmlspecialchars($dokumen['file_path']) ?>" class="btn-action view" target="_blank" title="Lihat/Unduh">
                          <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn-action edit" data-bs-toggle="modal" data-bs-target="#editDokumenModal" 
                                data-id="<?= $dokumen['id_dokumen'] ?>" 
                                data-judul="<?= htmlspecialchars($dokumen['judul']) ?>"
                                data-abstrak="<?= htmlspecialchars($dokumen['abstrak']) ?>"
                                data-kunci="<?= htmlspecialchars($dokumen['kata_kunci']) ?>"
                                data-user="<?= $dokumen['id_user'] ?>"
                                data-kategori="<?= $dokumen['id_kategori'] ?>"
                                data-status="<?= htmlspecialchars($dokumen['status_dokumen']) ?>"
                                title="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-action delete" data-bs-toggle="modal" data-bs-target="#deleteDokumenModal" 
                                data-id="<?= $dokumen['id_dokumen'] ?>" 
                                data-judul="<?= htmlspecialchars($dokumen['judul']) ?>"
                                title="Hapus">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center">Tidak ada data dokumen</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>&status=<?= urlencode($status) ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>&status=<?= urlencode($status) ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Add Dokumen Modal -->
  <div class="modal fade" id="addDokumenModal" tabindex="-1" aria-labelledby="addDokumenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addDokumenModalLabel">Upload Dokumen Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="dokumen.php" enctype="multipart/form-data" id="addDokumenForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Judul Dokumen <span class="text-danger">*</span></label>
                  <input type="text" name="judul" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Pengunggah <span class="text-danger">*</span></label>
                  <select name="id_user" class="form-control form-select" required>
                    <option value="">Pilih Mahasiswa</option>
                    <?php if (!empty($usersList)): ?>
                      <?php foreach ($usersList as $user): ?>
                        <option value="<?= $user['id_user'] ?>"><?= htmlspecialchars($user['nama_lengkap']) ?> (<?= htmlspecialchars($user['nim']) ?>)</option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Abstrak</label>
              <textarea name="abstrak" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Kata Kunci</label>
              <input type="text" name="kata_kunci" class="form-control" placeholder="Pisahkan dengan koma">
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Kategori <span class="text-danger">*</span></label>
                  <select name="id_kategori" class="form-control form-select" required>
                    <option value="">Pilih Kategori</option>
                    <?php if (!empty($kategoriList)): ?>
                      <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Status Dokumen <span class="text-danger">*</span></label>
                  <select name="status_dokumen" class="form-control form-select" required>
                    <option value="sedang_dikoreksi">Sedang Dikoreksi</option>
                    <option value="berhasil">Berhasil</option>
                    <option value="gagal">Gagal</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">File Dokumen (PDF) <span class="text-danger">*</span></label>
              <input type="file" name="file" class="form-control" accept=".pdf" required>
              <div class="form-text">Hanya file PDF yang diizinkan dengan ukuran maksimal 10MB</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Upload</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Dokumen Modal -->
  <div class="modal fade" id="editDokumenModal" tabindex="-1" aria-labelledby="editDokumenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDokumenModalLabel">Edit Data Dokumen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="dokumen.php" enctype="multipart/form-data" id="editDokumenForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_dokumen" id="editDokumenId">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Judul Dokumen <span class="text-danger">*</span></label>
                  <input type="text" name="judul" id="editJudul" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Pengunggah <span class="text-danger">*</span></label>
                  <select name="id_user" id="editUser" class="form-control form-select" required>
                    <option value="">Pilih Mahasiswa</option>
                    <?php if (!empty($usersList)): ?>
                      <?php foreach ($usersList as $user): ?>
                        <option value="<?= $user['id_user'] ?>"><?= htmlspecialchars($user['nama_lengkap']) ?> (<?= htmlspecialchars($user['nim']) ?>)</option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Abstrak</label>
              <textarea name="abstrak" id="editAbstrak" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Kata Kunci</label>
              <input type="text" name="kata_kunci" id="editKunci" class="form-control" placeholder="Pisahkan dengan koma">
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Kategori <span class="text-danger">*</span></label>
                  <select name="id_kategori" id="editKategori" class="form-control form-select" required>
                    <option value="">Pilih Kategori</option>
                    <?php if (!empty($kategoriList)): ?>
                      <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Status Dokumen <span class="text-danger">*</span></label>
                  <select name="status_dokumen" id="editStatus" class="form-control form-select" required>
                    <option value="sedang_dikoreksi">Sedang Dikoreksi</option>
                    <option value="berhasil">Berhasil</option>
                    <option value="gagal">Gagal</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Ganti File Dokumen (Opsional)</label>
              <input type="file" name="file" class="form-control" accept=".pdf">
              <div class="form-text">Biarkan kosong jika tidak ingin mengubah file. Hanya file PDF yang diizinkan.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Dokumen Modal -->
  <div class="modal fade" id="deleteDokumenModal" tabindex="-1" aria-labelledby="deleteDokumenModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteDokumenModalLabel">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="dokumen.php" id="deleteDokumenForm">
          <div class="modal-body">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_dokumen" id="deleteDokumenId">
            <p>Apakah Anda yakin ingin menghapus dokumen <strong id="deleteDokumenJudul"></strong>?</p>
            <p class="text-danger">Tindakan ini tidak dapat dibatalkan dan file akan dihapus permanen.</p>
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

      // Edit Dokumen Modal
      const editDokumenModal = document.getElementById('editDokumenModal');
      if (editDokumenModal) {
        editDokumenModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const judul = button.getAttribute('data-judul');
          const abstrak = button.getAttribute('data-abstrak');
          const kunci = button.getAttribute('data-kunci');
          const user = button.getAttribute('data-user');
          const kategori = button.getAttribute('data-kategori');
          const status = button.getAttribute('data-status');
          
          document.getElementById('editDokumenId').value = id;
          document.getElementById('editJudul').value = judul;
          document.getElementById('editAbstrak').value = abstrak;
          document.getElementById('editKunci').value = kunci;
          document.getElementById('editUser').value = user;
          document.getElementById('editKategori').value = kategori;
          document.getElementById('editStatus').value = status;
        });
      }

      // Delete Dokumen Modal
      const deleteDokumenModal = document.getElementById('deleteDokumenModal');
      if (deleteDokumenModal) {
        deleteDokumenModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const judul = button.getAttribute('data-judul');
          
          document.getElementById('deleteDokumenId').value = id;
          document.getElementById('deleteDokumenJudul').textContent = judul;
        });
      }
      
      // Form validation
      const addDokumenForm = document.getElementById('addDokumenForm');
      if (addDokumenForm) {
        addDokumenForm.addEventListener('submit', function(e) {
          const judul = document.querySelector('input[name="judul"]').value.trim();
          const user = document.querySelector('select[name="id_user"]').value;
          const kategori = document.querySelector('select[name="id_kategori"]').value;
          const status = document.querySelector('select[name="status_dokumen"]').value;
          const file = document.querySelector('input[name="file"]').files.length;
          
          if (!judul || !user || !kategori || !status || !file) {
            e.preventDefault();
            alert('Mohon lengkapi semua field yang wajib diisi');
            return false;
          }
        });
      }
      
      const editDokumenForm = document.getElementById('editDokumenForm');
      if (editDokumenForm) {
        editDokumenForm.addEventListener('submit', function(e) {
          const judul = document.querySelector('#editJudul').value.trim();
          const user = document.querySelector('#editUser').value;
          const kategori = document.querySelector('#editKategori').value;
          const status = document.querySelector('#editStatus').value;
          
          if (!judul || !user || !kategori || !status) {
            e.preventDefault();
            alert('Mohon lengkapi semua field yang wajib diisi');
            return false;
          }
        });
      }
    });
  </script>
</body>
</html>
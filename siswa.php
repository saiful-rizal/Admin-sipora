<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
class Siswa {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function tambah($nis, $nama, $kelas_id, $alamat, $no_hp) {
        $stmt = $this->conn->prepare("INSERT INTO siswa (nis, nama, kelas_id, alamat, no_hp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $nis, $nama, $kelas_id, $alamat, $no_hp);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function update($id, $nis, $nama, $kelas_id, $alamat, $no_hp) {
        $stmt = $this->conn->prepare("UPDATE siswa SET nis=?, nama=?, kelas_id=?, alamat=?, no_hp=? WHERE id=?");
        $stmt->bind_param("ssissi", $nis, $nama, $kelas_id, $alamat, $no_hp, $id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function hapus($id) {
        $stmt = $this->conn->prepare("DELETE FROM siswa WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
  
    public function getAll() {
        $query = "SELECT s.*, k.nama_kelas, k.jurusan 
                  FROM siswa s 
                  LEFT JOIN kelas k ON s.kelas_id = k.id 
                  ORDER BY s.nama ASC";
        $result = $this->conn->query($query);
        
        $data = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM siswa WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }
}

// Inisialisasi objek Siswa
$siswa = new Siswa($conn);

// Proses tambah siswa
if (isset($_POST['tambah_siswa'])) {
    $nis = $_POST['nis'];
    $nama = $_POST['nama'];
    $kelas_id = $_POST['kelas_id'];
    $alamat = $_POST['alamat'];
    $no_hp = $_POST['no_hp'];
    
    if ($siswa->tambah($nis, $nama, $kelas_id, $alamat, $no_hp)) {
        echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data siswa berhasil ditambahkan',
                    confirmButtonColor: '#ffc107',
                    background: '#1a1a1a',
                    color: '#f8f9fa'
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Data siswa gagal ditambahkan',
                    confirmButtonColor: '#ffc107',
                    background: '#1a1a1a',
                    color: '#f8f9fa'
                });
              </script>";
    }
}

// Proses edit siswa
if (isset($_POST['edit_siswa'])) {
    $id = $_POST['id'];
    $nis = $_POST['nis'];
    $nama = $_POST['nama'];
    $kelas_id = $_POST['kelas_id'];
    $alamat = $_POST['alamat'];
    $no_hp = $_POST['no_hp'];
    
    if ($siswa->update($id, $nis, $nama, $kelas_id, $alamat, $no_hp)) {
        echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data siswa berhasil diperbarui',
                    confirmButtonColor: '#ffc107',
                    background: '#1a1a1a',
                    color: '#f8f9fa'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'siswa.php';
                    }
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Data siswa gagal diperbarui',
                    confirmButtonColor: '#ffc107',
                    background: '#1a1a1a',
                    color: '#f8f9fa'
                });
              </script>";
    }
}

// Proses hapus siswa
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    if ($siswa->hapus($id)) {
        echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data siswa berhasil dihapus',
                    confirmButtonColor: '#ffc107',
                    background: '#1a1a1a',
                    color: '#f8f9fa'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'siswa.php';
                    }
                });
              </script>";
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Data siswa gagal dihapus',
                    confirmButtonColor: '#ffc107',
                    background: '#1a1a1a',
                    color: '#f8f9fa'
                });
              </script>";
    }
}

// Ambil data siswa
$data_siswa = $siswa->getAll();

// Ambil data kelas
$query_kelas = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
$result_kelas = $conn->query($query_kelas);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Siswa - SI DISKA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #0f0f0f;
      color: #f8f9fa;
      min-height: 100vh;
      overflow: hidden;
    }

    /* Top Navbar */
    .top-navbar {
      background: #1a1a1a;
      border-bottom: 2px solid #ffc107;
      padding: 12px 0;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
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
      gap: 12px;
    }

    .brand-logo {
      background: linear-gradient(135deg, #ffc107, #ffca28);
      color: #111;
      width: 35px;
      height: 35px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 16px;
    }

    .brand-text {
      font-weight: 700;
      font-size: 20px;
      color: #ffc107;
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
      border: 1px solid #333;
      border-radius: 20px;
      background: #222;
      color: #f8f9fa;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .search-input:focus {
      outline: none;
      border-color: #ffc107;
      background: #2a2a2a;
      box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
    }

    .search-input::placeholder {
      color: #666;
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
    }

    .navbar-actions {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .notification-icon, .messages-icon {
      position: relative;
      color: #ccc;
      font-size: 20px;
      cursor: pointer;
      transition: color 0.2s;
    }

    .notification-icon:hover, .messages-icon:hover {
      color: #ffc107;
    }

    .badge-notification {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #dc3545;
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
      background: #222;
      border-radius: 20px;
      border: 1px solid #333;
      cursor: pointer;
    }

    .user-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ffc107, #ffca28);
      color: #111;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 12px;
    }

    .user-name {
      font-weight: 600;
      color: #f8f9fa;
      font-size: 14px;
    }

    /* Dropdown Menu Styles */
    .dropdown-menu {
      background: #2a2a2a;
      border: 1px solid #333;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      padding: 8px 0;
      min-width: 220px;
    }

    .dropdown-item {
      color: #f8f9fa;
      padding: 10px 20px;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .dropdown-item:hover {
      background: #333;
      color: #ffc107;
    }

    .dropdown-item i {
      font-size: 16px;
      width: 20px;
      text-align: center;
    }

    .dropdown-divider {
      border-color: #333;
      margin: 8px 0;
    }

    .notification-dropdown {
      width: 320px;
      padding: 0;
    }

    .notification-header {
      padding: 12px 20px;
      border-bottom: 1px solid #333;
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
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }

    .notification-list::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }

    .notification-item {
      padding: 12px 20px;
      border-bottom: 1px solid #333;
      display: flex;
      gap: 12px;
      transition: background 0.2s;
    }

    .notification-item:hover {
      background: #222;
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
      color: #888;
    }

    .notification-footer {
      padding: 10px 20px;
      text-align: center;
      border-top: 1px solid #333;
    }

    .notification-footer a {
      color: #ffc107;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
    }

    /* Main Layout */
    .main-container {
      display: flex;
      margin-top: 65px;
      min-height: calc(100vh - 65px);
      overflow: hidden;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background: #1a1a1a;
      padding: 20px 0;
      border-right: 1px solid #333;
      position: sticky;
      top: 65px;
      height: calc(100vh - 65px);
      overflow-y: auto;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }

    .sidebar::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }

    .sidebar-menu {
      padding: 0 15px;
    }

    .menu-section {
      margin-bottom: 25px;
    }

    .menu-title {
      color: #666;
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
      color: #ccc;
      transition: all 0.2s ease;
      font-weight: 500;
      font-size: 14px;
      border-left: 3px solid transparent;
    }

    .menu-item:hover {
      background: #222;
      color: #ffc107;
      border-left-color: #ffc107;
      transform: translateX(3px);
    }

    .menu-item.active {
      background: #222;
      color: #ffc107;
      border-left-color: #ffc107;
    }

    .menu-item i {
      margin-right: 10px;
      font-size: 16px;
      width: 18px;
      text-align: center;
    }

    .logout-item {
      color: #dc3545 !important;
      margin-top: 20px;
      border-top: 1px solid #333;
      padding-top: 20px;
    }

    .logout-item:hover {
      color: #ff6b6b !important;
      border-left-color: #dc3545;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 25px;
      background: #111;
      overflow-y: auto;
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }

    .main-content::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
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
      color: #ffc107;
      margin: 0;
    }

    .page-subtitle {
      color: #888;
      font-size: 14px;
      margin-top: 5px;
    }

    /* Quick Actions */
    .quick-actions {
      display: flex;
      gap: 10px;
      margin-bottom: 25px;
    }

    .action-btn {
      background: #222;
      border: 1px solid #333;
      color: #f8f9fa;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .action-btn:hover {
      background: #ffc107;
      color: #111;
      border-color: #ffc107;
    }

    .action-btn.primary {
      background: #ffc107;
      color: #111;
      border-color: #ffc107;
    }

    .action-btn.primary:hover {
      background: #ffca28;
    }

    /* Content Cards */
    .content-card {
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
    }

    .card-title {
      font-size: 18px;
      font-weight: 600;
      color: #f8f9fa;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Table Styles */
    .data-table {
      width: 100%;
      border-collapse: collapse;
    }

    .data-table th {
      background: #222;
      color: #ffc107;
      font-weight: 600;
      text-align: left;
      padding: 12px 15px;
      border-bottom: 1px solid #333;
    }

    .data-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #333;
    }

    .data-table tr:hover {
      background: #1a1a1a;
    }

    .badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
    }

    .badge-success {
      background: rgba(40, 167, 69, 0.2);
      color: #28a745;
    }

    .badge-warning {
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
    }

    .badge-danger {
      background: rgba(220, 53, 69, 0.2);
      color: #dc3545;
    }

    /* Form Styles */
    .form-label {
      color: #f8f9fa;
      font-weight: 500;
      margin-bottom: 8px;
    }

    .form-control, .form-select {
      background: #222;
      border: 1px solid #333;
      color: #f8f9fa;
      border-radius: 8px;
      padding: 10px 15px;
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      outline: none;
      border-color: #ffc107;
      background: #2a2a2a;
      box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
      color: #f8f9fa;
    }

    .form-select option {
      background: #2a2a2a;
      color: #f8f9fa;
    }

    /* Modal Styles */
    .modal-content {
      background: #1a1a1a;
      border: 1px solid #333;
      color: #f8f9fa;
    }

    .modal-header {
      border-bottom: 1px solid #333;
    }

    .modal-footer {
      border-top: 1px solid #333;
    }

    .btn-close {
      filter: invert(1);
    }

    .btn-primary {
      background: #ffc107;
      border-color: #ffc107;
      color: #111;
    }

    .btn-primary:hover {
      background: #ffca28;
      border-color: #ffca28;
      color: #111;
    }

    .btn-secondary {
      background: #222;
      border-color: #333;
      color: #f8f9fa;
    }

    .btn-secondary:hover {
      background: #333;
      border-color: #444;
      color: #f8f9fa;
    }

    /* Custom Styles for Siswa Page */
    .siswa-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4e73df, #36b9cc);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 16px;
    }

    .kelas-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      background: rgba(78, 115, 223, 0.2);
      color: #4e73df;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .main-container {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        height: auto;
        position: static;
      }
      
      .search-container {
        display: none;
      }
      
      .quick-actions {
        flex-wrap: wrap;
      }
      
      .navbar-content {
        padding: 0 15px;
      }
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <nav class="top-navbar">
    <div class="navbar-content">
      <div class="brand">
        <div class="brand-logo">SD</div>
        <div class="brand-text">SI DISKA</div>
      </div>
      
      <div class="search-container">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Cari data siswa...">
      </div>
      
      <div class="navbar-actions">
        <!-- Notification Dropdown -->
        <div class="dropdown">
          <div class="notification-icon" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <span class="badge-notification">3</span>
          </div>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown">
            <div class="notification-header">
              <h6 class="notification-title">Notifikasi</h6>
              <a href="#" class="text-warning">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <div class="notification-item">
                <div class="notification-icon-small bg-warning text-dark">
                  <i class="bi bi-person-plus"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-text">Siswa baru telah ditambahkan</p>
                  <span class="notification-time">10 menit yang lalu</span>
                </div>
              </div>
              <div class="notification-item">
                <div class="notification-icon-small bg-success text-white">
                  <i class="bi bi-check-circle"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-text">Backup database berhasil</p>
                  <span class="notification-time">1 jam yang lalu</span>
                </div>
              </div>
              <div class="notification-item">
                <div class="notification-icon-small bg-danger text-white">
                  <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-text">Sistem akan maintenance besok</p>
                  <span class="notification-time">3 jam yang lalu</span>
                </div>
              </div>
            </div>
            <div class="notification-footer">
              <a href="#">Lihat semua notifikasi</a>
            </div>
          </div>
        </div>
        
        <!-- Messages Dropdown -->
        <div class="dropdown">
          <div class="messages-icon" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-envelope"></i>
            <span class="badge-notification">5</span>
          </div>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown">
            <div class="notification-header">
              <h6 class="notification-title">Pesan</h6>
              <a href="#" class="text-warning">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <div class="notification-item">
                <div class="notification-icon-small bg-info text-white">
                  <i class="bi bi-person"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-text">Pesan dari Bapak Ahmad</p>
                  <span class="notification-time">15 menit yang lalu</span>
                </div>
              </div>
              <div class="notification-item">
                <div class="notification-icon-small bg-primary text-white">
                  <i class="bi bi-file-text"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-text">Laporan bulanan tersedia</p>
                  <span class="notification-time">2 jam yang lalu</span>
                </div>
              </div>
            </div>
            <div class="notification-footer">
              <a href="#">Lihat semua pesan</a>
            </div>
          </div>
        </div>
        
        <!-- User Dropdown -->
        <div class="dropdown">
          <div class="user-info" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <span class="user-name">Halo, <?php echo $_SESSION['username']; ?></span>
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

  <!-- Main Container -->
  <div class="main-container">
    
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-menu">
        <div class="menu-section">
          <div class="menu-title">Menu Utama</div>
          <a href="dashboard.php" class="menu-item">
            <i class="bi bi-speedometer2"></i>
            Dashboard
          </a>
          <a href="siswa.php" class="menu-item active">
            <i class="bi bi-people"></i>
            Siswa
          </a>
          <a href="guru.php" class="menu-item">
            <i class="bi bi-person-badge"></i>
            Guru
          </a>
          <a href="kelas.php" class="menu-item">
            <i class="bi bi-door-open"></i>
            Kelas
          </a>
          <a href="users.php" class="menu-item">
            <i class="bi bi-person-gear"></i>
            Users
          </a>
        </div>
        
        <div class="menu-section">
          <div class="menu-title">Akademik</div>
          <a href="jadwal.php" class="menu-item">
            <i class="bi bi-calendar-week"></i>
            Jadwal Pelajaran
          </a>
          <a href="mata_pelajaran.php" class="menu-item">
            <i class="bi bi-book"></i>
            Mata Pelajaran
          </a>
          <a href="nilai.php" class="menu-item">
            <i class="bi bi-clipboard-data"></i>
            Nilai
          </a>
          <a href="absensi.php" class="menu-item">
            <i class="bi bi-calendar-check"></i>
            Absensi
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

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-header">
        <div>
          <h1 class="page-title">Data Siswa</h1>
          <p class="page-subtitle">Kelola data siswa di sekolah</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <button class="action-btn primary" data-bs-toggle="modal" data-bs-target="#tambahSiswaModal">
          <i class="bi bi-plus-circle me-2"></i>Tambah Siswa
        </button>
        <button class="action-btn">
          <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </button>
        <button class="action-btn">
          <i class="bi bi-printer me-2"></i>Cetak Data
        </button>
      </div>

      <!-- Data Table -->
      <div class="content-card">
        <h3 class="card-title">
          <i class="bi bi-table"></i>
          Daftar Siswa
        </h3>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Alamat</th>
                <th>No. HP</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_siswa) > 0): ?>
                <?php foreach ($data_siswa as $row): ?>
                  <tr>
                    <td><?php echo $row['nis']; ?></td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="siswa-avatar me-3">
                          <?php echo strtoupper(substr($row['nama'], 0, 1)); ?>
                        </div>
                        <div>
                          <div class="fw-bold"><?php echo $row['nama']; ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($row['nama_kelas']): ?>
                        <span class="kelas-badge"><?php echo $row['nama_kelas']; ?> - <?php echo $row['jurusan']; ?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $row['alamat'] ? substr($row['alamat'], 0, 30) . '...' : '-'; ?></td>
                    <td><?php echo $row['no_hp'] ? $row['no_hp'] : '-'; ?></td>
                    <td>
                      <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSiswaModal<?php echo $row['id']; ?>">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['nama']; ?>')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  
                  <!-- Edit Modal -->
                  <div class="modal fade" id="editSiswaModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editSiswaModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="editSiswaModalLabel<?php echo $row['id']; ?>">Edit Data Siswa</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="">
                          <div class="modal-body">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <div class="mb-3">
                              <label for="nis_edit<?php echo $row['id']; ?>" class="form-label">NIS</label>
                              <input type="text" class="form-control" id="nis_edit<?php echo $row['id']; ?>" name="nis" value="<?php echo $row['nis']; ?>" required>
                            </div>
                            <div class="mb-3">
                              <label for="nama_edit<?php echo $row['id']; ?>" class="form-label">Nama</label>
                              <input type="text" class="form-control" id="nama_edit<?php echo $row['id']; ?>" name="nama" value="<?php echo $row['nama']; ?>" required>
                            </div>
                            <div class="mb-3">
                              <label for="kelas_id_edit<?php echo $row['id']; ?>" class="form-label">Kelas</label>
                              <select class="form-select" id="kelas_id_edit<?php echo $row['id']; ?>" name="kelas_id">
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $result_kelas_edit = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
                                while ($kelas = $result_kelas_edit->fetch_assoc()) {
                                  $selected = ($row['kelas_id'] == $kelas['id']) ? 'selected' : '';
                                  echo "<option value='{$kelas['id']}' {$selected}>{$kelas['nama_kelas']} - {$kelas['jurusan']}</option>";
                                }
                                ?>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label for="alamat_edit<?php echo $row['id']; ?>" class="form-label">Alamat</label>
                              <textarea class="form-control" id="alamat_edit<?php echo $row['id']; ?>" name="alamat" rows="3"><?php echo $row['alamat']; ?></textarea>
                            </div>
                            <div class="mb-3">
                              <label for="no_hp_edit<?php echo $row['id']; ?>" class="form-label">No. HP</label>
                              <input type="text" class="form-control" id="no_hp_edit<?php echo $row['id']; ?>" name="no_hp" value="<?php echo $row['no_hp']; ?>">
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="edit_siswa" class="btn btn-primary">Simpan Perubahan</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4">Tidak ada data siswa</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Tambah Siswa Modal -->
  <div class="modal fade" id="tambahSiswaModal" tabindex="-1" aria-labelledby="tambahSiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tambahSiswaModalLabel">Tambah Siswa Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <div class="mb-3">
              <label for="nis" class="form-label">NIS</label>
              <input type="text" class="form-control" id="nis" name="nis" required>
            </div>
            <div class="mb-3">
              <label for="nama" class="form-label">Nama</label>
              <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="mb-3">
              <label for="kelas_id" class="form-label">Kelas</label>
              <select class="form-select" id="kelas_id" name="kelas_id">
                <option value="">-- Pilih Kelas --</option>
                <?php
                $result_kelas_add = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
                while ($kelas = $result_kelas_add->fetch_assoc()) {
                  echo "<option value='{$kelas['id']}'>{$kelas['nama_kelas']} - {$kelas['jurusan']}</option>";
                }
                ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="alamat" class="form-label">Alamat</label>
              <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label for="no_hp" class="form-label">No. HP</label>
              <input type="text" class="form-control" id="no_hp" name="no_hp">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="tambah_siswa" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
      
      // Auto-close dropdowns when clicking outside
      document.addEventListener('click', function(event) {
        if (!event.target.matches('.notification-icon, .messages-icon, .user-info, .user-info *')) {
          const dropdowns = document.querySelectorAll('.dropdown-menu.show');
          dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
          });
        }
      });
    });

    // Function to confirm delete with SweetAlert
    function confirmDelete(id, name) {
      Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Data siswa "${name}" akan dihapus!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#333',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        background: '#1a1a1a',
        color: '#f8f9fa'
      }).then((result) => {
        if (result.isConfirmed) {
          // First confirmation passed, proceed to delete
          window.location.href = 'siswa.php?hapus=' + id;
        }
      });
    }
  </script>

</body>
</html>
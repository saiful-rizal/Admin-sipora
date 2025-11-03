<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

class Mahasiswa {
    private $conn;
    
    public function __construct($db_connection) {
        if ($db_connection instanceof mysqli) {
            $this->conn = $db_connection;
        } else {
            throw new Exception("Koneksi database tidak valid");
        }
    }
    
    public function getAllMahasiswa($limit = 10, $offset = 0, $search = '', $filter_jurusan = '', $filter_prodi = '') {
        try {
            $query = "SELECT m.*, p.nama_prodi, j.jurusan 
                      FROM mahasiswa m 
                      LEFT JOIN prodi p ON m.prodi_id = p.id 
                      LEFT JOIN jurusan j ON p.jurusan_id = j.id";
            
            $conditions = [];
            if (!empty($search)) {
                $conditions[] = "(m.nim LIKE '%$search%' OR m.nama LIKE '%$search%' OR m.email LIKE '%$search%')";
            }
            
            if (!empty($filter_jurusan)) {
                $conditions[] = "j.id = $filter_jurusan";
            }
            
            if (!empty($filter_prodi)) {
                $conditions[] = "p.id = $filter_prodi";
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY m.nama ASC LIMIT $limit OFFSET $offset";
            
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $mahasiswa = [];
            while ($row = $result->fetch_assoc()) {
                $mahasiswa[] = $row;
            }
            return $mahasiswa;
        } catch (Exception $e) {
            error_log("Error getAllMahasiswa: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalMahasiswa($search = '', $filter_jurusan = '', $filter_prodi = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM mahasiswa m
                      LEFT JOIN prodi p ON m.prodi_id = p.id 
                      LEFT JOIN jurusan j ON p.jurusan_id = j.id";
            
            $conditions = [];
            if (!empty($search)) {
                $conditions[] = "(m.nim LIKE '%$search%' OR m.nama LIKE '%$search%' OR m.email LIKE '%$search%')";
            }
            
            if (!empty($filter_jurusan)) {
                $conditions[] = "j.id = $filter_jurusan";
            }
            
            if (!empty($filter_prodi)) {
                $conditions[] = "p.id = $filter_prodi";
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
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
    
    public function getMahasiswaById($id) {
        try {
            $query = "SELECT m.*, p.nama_prodi, j.jurusan 
                      FROM mahasiswa m 
                      LEFT JOIN prodi p ON m.prodi_id = p.id 
                      LEFT JOIN jurusan j ON p.jurusan_id = j.id 
                      WHERE m.id = $id";
            
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            return $result->fetch_assoc() ?: null;
        } catch (Exception $e) {
            error_log("Error getMahasiswaById: " . $e->getMessage());
            return null;
        }
    }
    
    public function isNimExists($nim, $exclude_id = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM mahasiswa WHERE nim = '$nim'";
            
            if ($exclude_id) {
                $query .= " AND id != $exclude_id";
            }
            
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error isNimExists: " . $e->getMessage());
            return false;
        }
    }
    
    public function addMahasiswa($data) {
        try {
            if ($this->isNimExists($data['nim'])) {
                throw new Exception("NIM sudah terdaftar");
            }
            
            $nim = $this->conn->real_escape_string($data['nim']);
            $nama = $this->conn->real_escape_string($data['nama']);
            $email = $this->conn->real_escape_string($data['email']);
            $jenis_kelamin = $this->conn->real_escape_string($data['jenis_kelamin']);
            $alamat = $this->conn->real_escape_string($data['alamat']);
            $no_hp = $this->conn->real_escape_string($data['no_hp']);
            $prodi_id = (int)$data['prodi_id'];
            $status = $this->conn->real_escape_string($data['status']);
            
            $query = "INSERT INTO mahasiswa (nim, nama, email, jenis_kelamin, alamat, no_hp, prodi_id, status, created_at) 
                      VALUES ('$nim', '$nama', '$email', '$jenis_kelamin', '$alamat', '$no_hp', $prodi_id, '$status', NOW())";
            
            return $this->conn->query($query);
        } catch (Exception $e) {
            error_log("Error addMahasiswa: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateMahasiswa($id, $data) {
        try {
            if ($this->isNimExists($data['nim'], $id)) {
                throw new Exception("NIM sudah terdaftar");
            }
            
            $nim = $this->conn->real_escape_string($data['nim']);
            $nama = $this->conn->real_escape_string($data['nama']);
            $email = $this->conn->real_escape_string($data['email']);
            $jenis_kelamin = $this->conn->real_escape_string($data['jenis_kelamin']);
            $alamat = $this->conn->real_escape_string($data['alamat']);
            $no_hp = $this->conn->real_escape_string($data['no_hp']);
            $prodi_id = (int)$data['prodi_id'];
            $status = $this->conn->real_escape_string($data['status']);
            
            $query = "UPDATE mahasiswa 
                      SET nim = '$nim', nama = '$nama', email = '$email', 
                          jenis_kelamin = '$jenis_kelamin', alamat = '$alamat', 
                          no_hp = '$no_hp', prodi_id = $prodi_id, status = '$status' 
                      WHERE id = $id";
            
            return $this->conn->query($query);
        } catch (Exception $e) {
            error_log("Error updateMahasiswa: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function deleteMahasiswa($id) {
        try {
            $query = "DELETE FROM mahasiswa WHERE id = $id";
            return $this->conn->query($query);
        } catch (Exception $e) {
            error_log("Error deleteMahasiswa: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getProdiForFilter() {
        try {
            $query = "SELECT p.id, p.nama_prodi, j.jurusan 
                      FROM prodi p 
                      JOIN jurusan j ON p.jurusan_id = j.id 
                      ORDER BY j.jurusan, p.nama_prodi";
            
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $prodi = [];
            while ($row = $result->fetch_assoc()) {
                $prodi[] = $row;
            }
            return $prodi;
        } catch (Exception $e) {
            error_log("Error getProdiForFilter: " . $e->getMessage());
            return [];
        }
    }
    
    public function getJurusanForFilter() {
        try {
            $query = "SELECT id, jurusan FROM jurusan ORDER BY jurusan";
            
            $result = $this->conn->query($query);
            if (!$result) {
                throw new Exception("Query error: " . $this->conn->error);
            }
            
            $jurusan = [];
            while ($row = $result->fetch_assoc()) {
                $jurusan[] = $row;
            }
            return $jurusan;
        } catch (Exception $e) {
            error_log("Error getJurusanForFilter: " . $e->getMessage());
            return [];
        }
    }
}

try {
    $mahasiswaObj = new Mahasiswa($conn);
} catch (Exception $e) {
    error_log("Mahasiswa initialization error: " . $e->getMessage());
    die("Terjadi kesalahan saat memuat data mahasiswa");
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_jurusan = isset($_GET['filter_jurusan']) ? $_GET['filter_jurusan'] : '';
$filter_prodi = isset($_GET['filter_prodi']) ? $_GET['filter_prodi'] : '';

$mahasiswaList = $mahasiswaObj->getAllMahasiswa($limit, $offset, $search, $filter_jurusan, $filter_prodi);
$totalMahasiswa = $mahasiswaObj->getTotalMahasiswa($search, $filter_jurusan, $filter_prodi);
$totalPages = ceil($totalMahasiswa / $limit);

$prodiList = $mahasiswaObj->getProdiForFilter();
$jurusanList = $mahasiswaObj->getJurusanForFilter();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        if ($action === 'add') {
            $data = [
                'nim' => $_POST['nim'],
                'nama' => $_POST['nama'],
                'email' => $_POST['email'],
                'jenis_kelamin' => $_POST['jenis_kelamin'],
                'alamat' => $_POST['alamat'],
                'no_hp' => $_POST['no_hp'],
                'prodi_id' => $_POST['prodi_id'],
                'status' => $_POST['status']
            ];
            
            if ($mahasiswaObj->addMahasiswa($data)) {
                $_SESSION['success'] = "Data mahasiswa berhasil ditambahkan";
                header("Location: mahasiswa.php");
                exit;
            }
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $data = [
                'nim' => $_POST['nim'],
                'nama' => $_POST['nama'],
                'email' => $_POST['email'],
                'jenis_kelamin' => $_POST['jenis_kelamin'],
                'alamat' => $_POST['alamat'],
                'no_hp' => $_POST['no_hp'],
                'prodi_id' => $_POST['prodi_id'],
                'status' => $_POST['status']
            ];
            
            if ($mahasiswaObj->updateMahasiswa($id, $data)) {
                $_SESSION['success'] = "Data mahasiswa berhasil diperbarui";
                header("Location: mahasiswa.php");
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            if ($mahasiswaObj->deleteMahasiswa($id)) {
                $_SESSION['success'] = "Data mahasiswa berhasil dihapus";
                header("Location: mahasiswa.php");
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editData = $mahasiswaObj->getMahasiswaById($id);
}

function isCurrentPage($page) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    return $currentFile === $page;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Mahasiswa - Sistem Informasi Kampus</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css/mahasiswa.css">
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
                  <p class="notification-text">Mahasiswa baru telah ditambahkan</p>
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
        
        <div class="dropdown">
          <div class="user-info" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <span class="user-name">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
          <a href="mahasiswa.php" class="menu-item <?php echo isCurrentPage('mahasiswa.php') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            Mahasiswa
          </a>
          <a href="dosen.php" class="menu-item <?php echo isCurrentPage('dosen.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i>
            Dosen
          </a>
          <a href="teknisi.php" class="menu-item <?php echo isCurrentPage('teknisi.php') ? 'active' : ''; ?>">
            <i class="bi bi-tools"></i>
            Teknisi
          </a>
          <a href="jurusan.php" class="menu-item <?php echo isCurrentPage('jurusan.php') ? 'active' : ''; ?>">
            <i class="bi bi-building"></i>
            Jurusan
          </a>
          <a href="prodi.php" class="menu-item <?php echo isCurrentPage('prodi.php') ? 'active' : ''; ?>">
            <i class="bi bi-building"></i>
            Prodi
          </a>
          <a href="users.php" class="menu-item <?php echo isCurrentPage('users.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-gear"></i>
            Users
          </a>
        </div>
        
        <div class="menu-section">
          <div class="menu-title">Perpustakaan</div>
          <a href="buku.php" class="menu-item <?php echo isCurrentPage('buku.php') ? 'active' : ''; ?>">
            <i class="bi bi-book"></i>
            Buku
          </a>
          <a href="jurnal.php" class="menu-item <?php echo isCurrentPage('jurnal.php') ? 'active' : ''; ?>">
            <i class="bi bi-journal-text"></i>
            Jurnal
          </a>
          <a href="skripsi.php" class="menu-item <?php echo isCurrentPage('skripsi.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            Skripsi
          </a>
          <a href="arsip.php" class="menu-item <?php echo isCurrentPage('arsip.php') ? 'active' : ''; ?>">
            <i class="bi bi-archive"></i>
            Arsip Penting
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
          <h1 class="page-title">Data Mahasiswa</h1>
          <p class="page-subtitle">Kelola data mahasiswa kampus</p>
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

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
          <i class="bi bi-check-circle-fill me-2"></i>
          <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <div class="quick-actions">
        <button class="action-btn primary" data-bs-toggle="modal" data-bs-target="#addMahasiswaModal">
          <i class="bi bi-plus-circle me-2"></i>Tambah Mahasiswa
        </button>
        <button class="action-btn" onclick="window.location.href='mahasiswa.php?export=excel'">
          <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </button>
        <button class="action-btn" onclick="window.location.href='mahasiswa.php?export=pdf'">
          <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
        </button>
        <button class="action-btn" onclick="window.location.href='buku.php'">
          <i class="bi bi-book me-2"></i>Data Buku
        </button>
      </div>

      <div class="content-card">
        <h3 class="card-title">
          <i class="bi bi-funnel"></i>
          Filter Data
        </h3>
        <form method="GET" action="mahasiswa.php" class="filter-section">
          <div class="filter-group">
            <label class="filter-label">Cari Mahasiswa</label>
            <input type="text" name="search" class="filter-input" placeholder="NIM, Nama, atau Email" value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="filter-group">
            <label class="filter-label">Filter berdasarkan Jurusan</label>
            <select name="filter_jurusan" class="filter-input">
              <option value="">Semua Jurusan</option>
              <?php foreach ($jurusanList as $jurusan): ?>
                <option value="<?php echo $jurusan['id']; ?>" <?php echo ($filter_jurusan == $jurusan['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($jurusan['jurusan']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label">Filter berdasarkan Prodi</label>
            <select name="filter_prodi" class="filter-input">
              <option value="">Semua Prodi</option>
              <?php foreach ($prodiList as $prodi): ?>
                <option value="<?php echo $prodi['id']; ?>" <?php echo ($filter_prodi == $prodi['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($prodi['nama_prodi'] . ' - ' . $prodi['jurusan']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <button type="submit" class="action-btn filter-btn">
              <i class="bi bi-search me-2"></i>Filter
            </button>
          </div>
        </form>
      </div>

      <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="card-title mb-0">
            <i class="bi bi-table"></i>
            Data Mahasiswa
          </h3>
          <div>
            <span class="pagination-info">
              Menampilkan <?php echo count($mahasiswaList); ?> dari <?php echo $totalMahasiswa; ?> data
            </span>
          </div>
        </div>
        
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Jenis Kelamin</th>
                <th>Jurusan</th>
                <th>Prodi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($mahasiswaList)): ?>
                <?php foreach ($mahasiswaList as $mahasiswa): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($mahasiswa['nim']); ?></td>
                    <td><?php echo htmlspecialchars($mahasiswa['nama']); ?></td>
                    <td><?php echo htmlspecialchars($mahasiswa['email']); ?></td>
                    <td><?php echo htmlspecialchars($mahasiswa['jenis_kelamin']); ?></td>
                    <td><?php echo htmlspecialchars($mahasiswa['jurusan']); ?></td>
                    <td><?php echo htmlspecialchars($mahasiswa['nama_prodi']); ?></td>
                    <td>
                      <?php if ($mahasiswa['status'] == 'Aktif'): ?>
                        <span class="badge badge-success">Aktif</span>
                      <?php elseif ($mahasiswa['status'] == 'Nonaktif'): ?>
                        <span class="badge badge-danger">Nonaktif</span>
                      <?php else: ?>
                        <span class="badge badge-warning"><?php echo htmlspecialchars($mahasiswa['status']); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="action-btn action-btn-sm btn-edit" onclick="editMahasiswa(<?php echo $mahasiswa['id']; ?>)">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="action-btn action-btn-sm btn-delete" onclick="confirmDelete(<?php echo $mahasiswa['id']; ?>)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center">Tidak ada data mahasiswa</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Mobile Cards View -->
        <div class="mobile-cards">
          <?php if (!empty($mahasiswaList)): ?>
            <?php foreach ($mahasiswaList as $mahasiswa): ?>
              <div class="mobile-card">
                <div class="mobile-card-header">
                  <div class="mobile-card-title"><?php echo htmlspecialchars($mahasiswa['nama']); ?></div>
                  <?php if ($mahasiswa['status'] == 'Aktif'): ?>
                    <span class="badge badge-success">Aktif</span>
                  <?php elseif ($mahasiswa['status'] == 'Nonaktif'): ?>
                    <span class="badge badge-danger">Nonaktif</span>
                  <?php else: ?>
                    <span class="badge badge-warning"><?php echo htmlspecialchars($mahasiswa['status']); ?></span>
                  <?php endif; ?>
                </div>
                <div class="mobile-card-body">
                  <div class="mobile-card-row">
                    <div class="mobile-card-label">NIM</div>
                    <div class="mobile-card-value"><?php echo htmlspecialchars($mahasiswa['nim']); ?></div>
                  </div>
                  <div class="mobile-card-row">
                    <div class="mobile-card-label">Email</div>
                    <div class="mobile-card-value"><?php echo htmlspecialchars($mahasiswa['email']); ?></div>
                  </div>
                  <div class="mobile-card-row">
                    <div class="mobile-card-label">Jenis Kelamin</div>
                    <div class="mobile-card-value"><?php echo htmlspecialchars($mahasiswa['jenis_kelamin']); ?></div>
                  </div>
                  <div class="mobile-card-row">
                    <div class="mobile-card-label">Jurusan</div>
                    <div class="mobile-card-value"><?php echo htmlspecialchars($mahasiswa['jurusan']); ?></div>
                  </div>
                  <div class="mobile-card-row">
                    <div class="mobile-card-label">Prodi</div>
                    <div class="mobile-card-value"><?php echo htmlspecialchars($mahasiswa['nama_prodi']); ?></div>
                  </div>
                </div>
                <div class="mobile-card-actions">
                  <button class="action-btn action-btn-sm btn-edit" onclick="editMahasiswa(<?php echo $mahasiswa['id']; ?>)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="action-btn action-btn-sm btn-delete" onclick="confirmDelete(<?php echo $mahasiswa['id']; ?>)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center p-4">Tidak ada data mahasiswa</div>
          <?php endif; ?>
        </div>
        
        <div class="pagination-container">
          <div class="pagination-info">
            Menampilkan halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
          </div>
          <nav>
            <ul class="pagination">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter_jurusan=<?php echo urlencode($filter_jurusan); ?>&filter_prodi=<?php echo urlencode($filter_prodi); ?>">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                  <li class="page-item active">
                    <span class="page-link"><?php echo $i; ?></span>
                  </li>
                <?php else: ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_jurusan=<?php echo urlencode($filter_jurusan); ?>&filter_prodi=<?php echo urlencode($filter_prodi); ?>">
                      <?php echo $i; ?>
                    </a>
                  </li>
                <?php endif; ?>
              <?php endfor; ?>
              
              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter_jurusan=<?php echo urlencode($filter_jurusan); ?>&filter_prodi=<?php echo urlencode($filter_prodi); ?>">
                    <i class="bi bi-chevron-right"></i>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      </div>
    </main>
  </div>

  <div class="modal fade" id="addMahasiswaModal" tabindex="-1" aria-labelledby="addMahasiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addMahasiswaModalLabel">
            <i class="bi bi-person-plus me-2"></i>Tambah Data Mahasiswa
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="mahasiswa.php">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">NIM <span class="text-danger">*</span></label>
                  <input type="text" name="nim" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                  <input type="text" name="nama" class="form-control" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                  <select name="jenis_kelamin" class="form-control" required>
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">No. HP</label>
                  <input type="text" name="no_hp" class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Prodi <span class="text-danger">*</span></label>
                  <select name="prodi_id" class="form-control" required>
                    <option value="">Pilih Prodi</option>
                    <?php foreach ($prodiList as $prodi): ?>
                      <option value="<?php echo $prodi['id']; ?>">
                        <?php echo htmlspecialchars($prodi['nama_prodi'] . ' - ' . $prodi['jurusan']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label class="form-label">Alamat</label>
                  <textarea name="alamat" class="form-control" rows="2"></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Status <span class="text-danger">*</span></label>
                  <select name="status" class="form-control" required>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                    <option value="Cuti">Cuti</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Simpan Data</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editMahasiswaModal" tabindex="-1" aria-labelledby="editMahasiswaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editMahasiswaModalLabel">
            <i class="bi bi-pencil-square me-2"></i>Edit Data Mahasiswa
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="mahasiswa.php">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">NIM <span class="text-danger">*</span></label>
                  <input type="text" name="nim" id="edit_nim" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                  <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                  <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-control" required>
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">No. HP</label>
                  <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Prodi <span class="text-danger">*</span></label>
                  <select name="prodi_id" id="edit_prodi_id" class="form-control" required>
                    <option value="">Pilih Prodi</option>
                    <?php foreach ($prodiList as $prodi): ?>
                      <option value="<?php echo $prodi['id']; ?>">
                        <?php echo htmlspecialchars($prodi['nama_prodi'] . ' - ' . $prodi['jurusan']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label class="form-label">Alamat</label>
                  <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Status <span class="text-danger">*</span></label>
                  <select name="status" id="edit_status" class="form-control" required>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                    <option value="Cuti">Cuti</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="action-btn primary">Update Data</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteConfirmationModalLabel">
            <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus data mahasiswa ini?</p>
          <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="action-btn" data-bs-dismiss="modal">Batal</button>
          <form method="POST" action="mahasiswa.php" style="display: inline;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <button type="submit" class="action-btn btn-delete">Hapus Data</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      
      if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('active');
        });
      }
      
      document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !sidebarToggle.contains(event.target) && 
            sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
        }
      });
      
      const searchInput = document.querySelector('.search-input');
      if (searchInput) {
        searchInput.addEventListener('focus', function() {
          this.parentElement.style.transform = 'scale(1.02)';
        });
        
        searchInput.addEventListener('blur', function() {
          this.parentElement.style.transform = 'scale(1)';
        });
      }

      const menuItems = document.querySelectorAll('.menu-item');
      menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
          menuItems.forEach(i => i.classList.remove('active'));
          this.classList.add('active');
          
          if (window.innerWidth <= 768) {
            sidebar.classList.remove('active');
          }
        });
      });

      const actionBtns = document.querySelectorAll('.action-btn');
      actionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          this.style.transform = 'scale(0.95)';
          setTimeout(() => {
            this.style.transform = 'scale(1)';
          }, 150);
        });
      });
      
      document.addEventListener('click', function(event) {
        if (!event.target.matches('.notification-icon, .messages-icon, .user-info, .user-info *')) {
          const dropdowns = document.querySelectorAll('.dropdown-menu.show');
          dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
          });
        }
      });
    });
    
    function editMahasiswa(id) {
      window.location.href = 'mahasiswa.php?edit=' + id;
    }
    
    function confirmDelete(id) {
      document.getElementById('delete_id').value = id;
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
      deleteModal.show();
    }
    
    <?php if ($editData): ?>
      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('edit_id').value = '<?php echo $editData['id']; ?>';
        document.getElementById('edit_nim').value = '<?php echo htmlspecialchars($editData['nim']); ?>';
        document.getElementById('edit_nama').value = '<?php echo htmlspecialchars($editData['nama']); ?>';
        document.getElementById('edit_email').value = '<?php echo htmlspecialchars($editData['email']); ?>';
        document.getElementById('edit_jenis_kelamin').value = '<?php echo htmlspecialchars($editData['jenis_kelamin']); ?>';
        document.getElementById('edit_no_hp').value = '<?php echo htmlspecialchars($editData['no_hp']); ?>';
        document.getElementById('edit_alamat').value = '<?php echo htmlspecialchars($editData['alamat']); ?>';
        document.getElementById('edit_prodi_id').value = '<?php echo $editData['prodi_id']; ?>';
        document.getElementById('edit_status').value = '<?php echo htmlspecialchars($editData['status']); ?>';
        
        const editModal = new bootstrap.Modal(document.getElementById('editMahasiswaModal'));
        editModal.show();
      });
    <?php endif; ?>
  </script>
</body>
</html>
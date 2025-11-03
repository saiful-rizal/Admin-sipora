<?php
session_start();
require_once "config.php";

// Cek apakah user sudah login dan role-nya admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Ambil ID user dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php');
    exit();
}
 $id_user = (int)$_GET['id'];

// Ambil data user dari database
 $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
 $stmt->bind_param("i", $id_user);
 $stmt->execute();
 $result = $stmt->get_result();
 $user = $result->fetch_assoc();
 $stmt->close();

// Jika user tidak ditemukan
if (!$user) {
    header('Location: users.php');
    exit();
}

 $message = '';
 $message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = $_POST['nama_lengkap'];
    $nim = !empty($_POST['nim']) ? $_POST['nim'] : null;
    $email = $_POST['email'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Prepared statement untuk update
    $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, nim = ?, email = ?, username = ?, role = ?, status = ? WHERE id_user = ?");
    $stmt->bind_param("ssssssi", $nama_lengkap, $nim, $email, $username, $role, $status, $id_user);

    if ($stmt->execute()) {
        $message = "Data user berhasil diperbarui.";
        $message_type = "success";
        // Redirect ke halaman users setelah berhasil
        header("Location: users.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
        exit();
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "danger";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User - SIPORA POLIJE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css/dashboard.css">
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
    </div>
  </nav>

  <div class="main-container">
    <aside class="sidebar" id="sidebar">
        <!-- Salin sidebar dari users.php -->
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="menu-title">Menu Utama</div>
                <a href="dashboard.php" class="menu-item">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                <a href="users.php" class="menu-item active">
                    <i class="bi bi-people"></i>
                    Users
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
          <h1 class="page-title">Edit User</h1>
          <p class="page-subtitle">Perbarui data user: <?php echo htmlspecialchars($user['nama_lengkap']); ?></p>
        </div>
        <div>
            <a href="users.php" class="action-btn">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </div>
      </div>

      <div class="content-card">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="edit_user.php?id=<?php echo $user['id_user']; ?>" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nim" class="form-label">NIM (Opsional, kosongkan jika Admin)</label>
                    <input type="text" class="form-control" id="nim" name="nim" value="<?php echo htmlspecialchars($user['nim'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="mahasiswa" <?php echo ($user['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="">Pilih Status</option>
                        <option value="pending" <?php echo ($user['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($user['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($user['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <small class="text-muted">Catatan: Password tidak dapat diubah dari halaman ini.</small>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="users.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Salin script sidebar dari users.php
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('active');
        });
      }
    });
  </script>
</body>
</html>
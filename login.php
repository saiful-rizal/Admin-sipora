<?php
// Memulai session. Harus berada di paling atas.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memuat file konfigurasi database
require_once "config.php";

// Inisialisasi variabel pesan
 $error = "";
 $success = "";

// Proses login hanya jika form dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil dan bersihkan input dari form
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi input agar tidak kosong
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi.";
    } else {
        // Blok try-catch untuk menangani error database
        try {
            // Periksa apakah objek koneksi $conn valid dan terhubung
            if (!$conn || $conn->connect_error) {
                throw new Exception("Koneksi ke database gagal. Hubungi administrator.");
            }

            // Siapkan query untuk mengambil data user
            $sql = "SELECT id_user, username, password_hash, role, status, nama_lengkap FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);

            // Jika prepare gagal, lempar exception
            if ($stmt === false) {
                throw new Exception("Query database tidak valid.");
            }

            // Bind parameter username ke query dan eksekusi
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // Periksa apakah user ditemukan
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Verifikasi password yang dimasukkan dengan hash di database
                if (password_verify($password, $user['password_hash'])) {

                    // Periksa status akun user
                    if ($user['status'] === 'approved') {
                        // Set session data untuk user yang berhasil login
                        $_SESSION['id_user'] = $user['id_user'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['status'] = $user['status'];

                        $success = "Login berhasil! Mengalihkan ke dashboard...";
                        // Alihkan ke dashboard setelah 1.5 detik
                        header("refresh:1.5; url=dashboard.php");
                        exit(); // Penting: hentikan eksekusi script setelah redirect

                    } else {
                        // Pesan error jika akun belum disetujui
                        $error = "Akun Anda belum disetujui. Silakan hubungi administrator.";
                    }
                } else {
                    // Pesan error jika password salah
                    $error = "Username atau password salah.";
                }
            } else {
                // Pesan error jika username tidak ditemukan
                $error = "Username atau password salah.";
            }
            // Tutup statement
            $stmt->close();

        } catch (Exception $e) {
            // Catat error asli ke log server untuk debugging
            error_log("Login Error: " . $e->getMessage());
            // Tampilkan pesan error generik ke user
            $error = "Terjadi masalah pada sistem. Silakan coba lagi nanti.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login - SIPORA POLIJE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background:#111;
      color:#f8f9fa;
      display:flex;
      justify-content:center;
      align-items:center;
      min-height:100vh;
      margin:0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
      background:#1c1c1c;
      border:1px solid #ffc107;
      border-radius:15px;
      padding:30px;
      width:100%;
      max-width:400px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.5);
    }
    .form-control {
      background:#2a2a2a;
      border:1px solid #444;
      color:#fff;
      transition:0.3s;
    }
    .form-control:focus {
      border-color:#ffc107;
      box-shadow:0 0 8px rgba(255,193,7,0.6);
      background:#2a2a2a;
      color:#fff;
    }
    .btn-yellow {
      background:#ffc107;
      border:none;
      color:#000;
      font-weight:600;
      transition:0.3s;
    }
    .btn-yellow:hover {
      background:#e0a800;
      transform:scale(1.02);
    }
    a { color:#ffc107; text-decoration:none; }
    a:hover { text-decoration:underline; }
    .logo-container {
      text-align: center;
      margin-bottom: 20px;
    }
    .logo-img {
      height: 60px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

  <div class="card shadow">
    <div class="logo-container">
      <!-- Pastikan path logo benar, jika tidak ada, ganti dengan teks atau hapus -->
      <img src="assets/ic_polije.png" alt="Polije Logo" class="logo-img" onerror="this.style.display='none'">
      <h3 class="fw-bold text-warning">SIPORA POLIJE</h3>
      <p class="text-light">Sistem Informasi Perpustakaan</p>
    </div>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="username" class="form-label text-light">Username</label>
        <input type="text" name="username" id="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label text-light">Password</label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" required>
          <button class="btn btn-outline-warning" type="button" onclick="togglePassword()">
            <i class="bi bi-eye" id="toggleIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-yellow w-100">Login</button>
    </form>
    <div class="text-center mt-3">
      <small class="text-light">Belum punya akun? <a href="register.php" class="text-warning">Daftar di sini</a></small>
    </div>
  </div>

  <script>
    function togglePassword() {
      const pass = document.getElementById("password");
      const icon = document.getElementById("toggleIcon");
      
      if (pass.type === "password") {
        pass.type = "text";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        pass.type = "password";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    // Konfigurasi SweetAlert2
    const swalConfig = {
      showClass: { popup: '' },
      hideClass: { popup: '' },
      confirmButtonColor: "#ffc107",
      allowOutsideClick: false,
      timer: 3000,
      timerProgressBar: true
    };

    // Tampilkan notifikasi menggunakan SweetAlert2
    <?php if (!empty($error)): ?>
      Swal.fire({
        ...swalConfig,
        icon: "error",
        title: "Login Gagal",
        text: <?php echo json_encode($error); ?> // Menggunakan json_encode untuk keamanan
      });
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      Swal.fire({
        ...swalConfig,
        icon: "success",
        title: "Berhasil",
        text: <?php echo json_encode($success); ?> // Menggunakan json_encode untuk keamanan
      });
    <?php endif; ?>
  </script>
</body>
</html>
<?php
session_start();
require_once "config.php";

 $error = "";
 $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);
    $full_name = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $role     = isset($_POST['role']) ? trim($_POST['role']) : 'siswa';
    
    // Validasi input
    if (empty($username) || empty($password) || empty($confirm) || empty($full_name) || empty($email)) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($username) < 4) {
        $error = "Username minimal 4 karakter!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif ($password !== $confirm) {
        $error = "Password dan konfirmasi tidak sama!";
    } else {
        // Cek apakah username sudah ada
        $checkQuery = "SELECT username FROM users WHERE username = ?";
        $stmt = $conn->prepare($checkQuery);
        
        if ($stmt === false) {
            $error = "Terjadi kesalahan pada persiapan query: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username sudah terpakai!";
            } else {
                // Cek apakah email sudah terdaftar
                $checkEmailQuery = "SELECT email FROM users WHERE email = ?";
                $stmt = $conn->prepare($checkEmailQuery);
                
                if ($stmt === false) {
                    $error = "Terjadi kesalahan pada persiapan query: " . $conn->error;
                } else {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $emailResult = $stmt->get_result();
                    
                    if ($emailResult->num_rows > 0) {
                        $error = "Email sudah terdaftar!";
                    } else {
                        // Gunakan password_hash yang lebih aman
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        
                        $insertQuery = "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertQuery);
                        
                        if ($stmt === false) {
                            $error = "Terjadi kesalahan pada persiapan query: " . $conn->error;
                        } else {
                            $stmt->bind_param("sssss", $username, $passwordHash, $full_name, $email, $role);
                            
                            if ($stmt->execute()) {
                                $success = "Registrasi berhasil! Silakan login.";
                                // Redirect setelah 2 detik
                                echo "<script>
                                        setTimeout(function(){ 
                                            window.location='login.php'; 
                                        }, 2000);
                                      </script>";
                            } else {
                                $error = "Terjadi kesalahan saat registrasi: " . $stmt->error;
                            }
                        }
                    }
                }
            }
            $stmt->close();
        }
    }
}
 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - SI DISKA</title>
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
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }

    .register-container {
      width: 100%;
      max-width: 450px;
      padding: 20px;
    }

    .register-card {
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
      position: relative;
      overflow: hidden;
    }

    .register-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, #ffc107, #ffca28);
    }

    .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 25px;
    }

    .brand-logo {
      background: linear-gradient(135deg, #ffc107, #ffca28);
      color: #111;
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 18px;
    }

    .brand-text {
      font-weight: 700;
      font-size: 24px;
      color: #ffc107;
      letter-spacing: -0.5px;
    }

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
      padding: 12px 15px;
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

    .btn-register {
      background: #ffc107;
      color: #111;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-weight: 600;
      width: 100%;
      transition: all 0.2s ease;
      margin-top: 10px;
    }

    .btn-register:hover {
      background: #ffca28;
      transform: translateY(-2px);
    }

    .login-link {
      text-align: center;
      margin-top: 20px;
      color: #888;
    }

    .login-link a {
      color: #ffc107;
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    .input-group-text {
      background: #222;
      border: 1px solid #333;
      color: #888;
      border-right: none;
    }

    .input-group .form-control {
      border-left: none;
    }

    .input-group .form-control:focus {
      border-left: none;
    }

    .role-info {
      font-size: 12px;
      color: #888;
      margin-top: 5px;
    }

    /* Responsive */
    @media (max-width: 480px) {
      .register-container {
        padding: 15px;
      }
      
      .register-card {
        padding: 20px;
      }
    }
    
    /* Password strength indicator */
    .password-strength {
      height: 5px;
      margin-top: 5px;
      border-radius: 3px;
      background-color: #333;
      transition: all 0.3s ease;
    }
    
    .password-strength.weak {
      background-color: #dc3545;
      width: 33%;
    }
    
    .password-strength.medium {
      background-color: #ffc107;
      width: 66%;
    }
    
    .password-strength.strong {
      background-color: #198754;
      width: 100%;
    }
  </style>
</head>
<body>

  <div class="register-container">
    <div class="register-card">
      <div class="brand">
        <div class="brand-logo">SD</div>
        <div class="brand-text">SI DISKA</div>
      </div>
      
      <h3 class="text-center mb-4" style="color: #f8f9fa; font-weight: 600;">Daftar Akun</h3>
      
      <form method="POST" action="" id="registerForm">
        <div class="mb-3">
          <label for="full_name" class="form-label">Nama Lengkap</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
            <input type="text" name="full_name" class="form-control" id="full_name" required>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="username" class="form-control" id="username" required minlength="4">
          </div>
          <div class="form-text text-muted">Minimal 4 karakter</div>
        </div>
        
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" id="email" required>
          </div>
        </div>
        
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" id="password" required minlength="6">
          </div>
          <div class="password-strength" id="passwordStrength"></div>
          <div class="form-text text-muted">Minimal 6 karakter</div>
        </div>
        
        <div class="mb-3">
          <label for="confirm" class="form-label">Konfirmasi Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="confirm" class="form-control" id="confirm" required>
          </div>
        </div>
        
        <div class="mb-4">
          <label for="role" class="form-label">Role</label>
          <select name="role" class="form-select" id="role">
            <option value="siswa" selected>Siswa</option>
            <option value="guru">Guru</option>
            <option value="admin">Admin</option>
          </select>
          <div class="role-info">Pilih role sesuai dengan posisi Anda di sekolah</div>
        </div>
        
        <button type="submit" class="btn btn-register">Daftar</button>
      </form>
      
      <div class="login-link">
        <small>Sudah punya akun? <a href="login.php">Login di sini</a></small>
      </div>
    </div>
  </div>

  <script>
    const swalConfig = {
      showClass: { popup: '' },
      hideClass: { popup: '' },
      confirmButtonColor: "#ffc107",
      background: '#1a1a1a',
      color: '#f8f9fa'
    };

    <?php if ($error != ""): ?>
      Swal.fire({...swalConfig, icon:"error", title:"Registrasi Gagal", text:"<?= $error ?>"});
    <?php endif; ?>
    <?php if ($success != ""): ?>
      Swal.fire({...swalConfig, icon:"success", title:"Berhasil", text:"<?= $success ?>"});
    <?php endif; ?>
    
    // Password strength checker
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthIndicator = document.getElementById('passwordStrength');
      
      // Reset classes
      strengthIndicator.classList.remove('weak', 'medium', 'strong');
      
      if (password.length === 0) {
        return;
      }
      
      // Simple strength calculation
      let strength = 0;
      
      // Length check
      if (password.length >= 6) strength++;
      if (password.length >= 10) strength++;
      
      // Complexity checks
      if (/[A-Z]/.test(password)) strength++;
      if (/[0-9]/.test(password)) strength++;
      if (/[^A-Za-z0-9]/.test(password)) strength++;
      
      // Set strength class
      if (strength <= 2) {
        strengthIndicator.classList.add('weak');
      } else if (strength <= 4) {
        strengthIndicator.classList.add('medium');
      } else {
        strengthIndicator.classList.add('strong');
      }
    });
    
    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const full_name = document.getElementById('full_name').value;
      const username = document.getElementById('username').value;
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const confirm = document.getElementById('confirm').value;
      
      if (full_name.trim() === '') {
        e.preventDefault();
        Swal.fire({...swalConfig, icon:"error", title:"Error", text:"Nama lengkap harus diisi!"});
      } else if (username.length < 4) {
        e.preventDefault();
        Swal.fire({...swalConfig, icon:"error", title:"Error", text:"Username minimal 4 karakter!"});
      } else if (!/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
        e.preventDefault();
        Swal.fire({...swalConfig, icon:"error", title:"Error", text:"Format email tidak valid!"});
      } else if (password.length < 6) {
        e.preventDefault();
        Swal.fire({...swalConfig, icon:"error", title:"Error", text:"Password minimal 6 karakter!"});
      } else if (password !== confirm) {
        e.preventDefault();
        Swal.fire({...swalConfig, icon:"error", title:"Error", text:"Password dan konfirmasi tidak sama!"});
      }
    });
  </script>
</body>
</html>
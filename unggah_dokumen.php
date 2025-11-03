<?php
session_start();
require_once __DIR__ . '/config.php'; // Menggunakan koneksi MySQLi

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('username', '', time() - 3600, "/");
    header("Location: auth.php");
    exit();
}

 $user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Pastikan hanya mahasiswa yang dapat mengakses halaman ini
if ($user_data['role'] !== 'mahasiswa') {
    header("Location: auth.php");
    exit();
}

// Proses upload dokumen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    try {
        $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
        $abstrak = isset($_POST['abstrak']) ? trim($_POST['abstrak']) : '';
        $id_kategori = isset($_POST['id_kategori']) ? $_POST['id_kategori'] : '';
        $id_jurusan = isset($_POST['id_jurusan']) ? $_POST['id_jurusan'] : '';
        $id_prodi = isset($_POST['id_prodi']) ? $_POST['id_prodi'] : '';
        $kata_kunci = isset($_POST['kata_kunci']) ? trim($_POST['kata_kunci']) : '';
        $tahun = isset($_POST['tahun']) ? $_POST['tahun'] : date('Y');
        
        // Validasi input
        if (empty($judul)) {
            $upload_error = "Judul dokumen wajib diisi";
        } elseif (empty($abstrak)) {
            $upload_error = "Abstrak wajib diisi";
        } elseif (empty($id_kategori)) {
            $upload_error = "Kategori dokumen wajib dipilih";
        } else {
            // Proses upload file
            if (isset($_FILES['dokumen_file']) && $_FILES['dokumen_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['dokumen_file'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                
                // Validasi file
                $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $maxFileSize = 10 * 1024 * 1024; // 10MB
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $upload_error = "Hanya file PDF, DOC, DOCX, PPT, PPTX, XLS, dan XLSX yang diperbolehkan";
                } elseif ($fileSize > $maxFileSize) {
                    $upload_error = "Ukuran file maksimal 10MB";
                } else {
                    // Buat nama file unik
                    $newFileName = uniqid('doc_') . '.' . $fileExtension;
                    $uploadDir = __DIR__ . '/uploads/documents/';
                    
                    // Buat direktori jika belum ada
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $targetPath = $uploadDir . $newFileName;
                    
                    // Pindahkan file
                    if (move_uploaded_file($fileTmpName, $targetPath)) {
                        // Simpan ke database
                        $tanggal_upload = date('Y-m-d H:i:s');
                        $status_dokumen = 'sedang_dikoreksi'; // Status awal
                        
                        $stmt = $conn->prepare("
                            INSERT INTO dokumen 
                            (judul, abstrak, id_kategori, id_jurusan, id_prodi, kata_kunci, file_nama, id_user, tanggal_upload, status_dokumen, tahun) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->bind_param("ssiiisssssi", $judul, $abstrak, $id_kategori, $id_jurusan, $id_prodi, $kata_kunci, $newFileName, $user_id, $tanggal_upload, $status_dokumen, $tahun);
                        $stmt->execute();
                        
                        $upload_success = "Dokumen berhasil diunggah dan akan melalui proses review sebelum dipublikasikan.";
                        
                        // Reset form
                        $_POST = array();
                    } else {
                        $upload_error = "Gagal mengunggah file. Silakan coba lagi.";
                    }
                }
            } else {
                $upload_error = "File dokumen wajib dipilih";
            }
        }
    } catch (Exception $e) {
        $upload_error = "Error uploading document: " . $e->getMessage();
    }
}

// Ambil data untuk dropdown
try {
    $kategori_data = $conn->query("SELECT id_kategori, nama_kategori FROM kategori_perpustakaan ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
    $jurusan_data = $conn->query("SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan")->fetch_all(MYSQLI_ASSOC);
    $prodi_data = $conn->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetch_all(MYSQLI_ASSOC);
    
    // Ambil tahun dari dokumen yang ada
    $tahun_result = $conn->query("SELECT DISTINCT YEAR(tanggal_upload) AS tahun FROM dokumen ORDER BY tahun DESC");
    $tahun_data = $tahun_result->fetch_all(MYSQLI_ASSOC);
    
    // Tambahkan tahun saat ini jika belum ada
    $current_year = date('Y');
    $year_exists = false;
    foreach ($tahun_data as $tahun) {
        if ($tahun['tahun'] == $current_year) {
            $year_exists = true;
            break;
        }
    }
    if (!$year_exists) {
        $tahun_data[] = ['tahun' => $current_year];
    }
} catch (Exception $e) {
    $kategori_data = [];
    $jurusan_data = [];
    $prodi_data = [];
    $tahun_data = [];
}

// Ambil dokumen yang telah diunggah oleh user
try {
    $stmt = $conn->prepare("
        SELECT 
            d.id_dokumen,
            d.judul,
            d.abstrak,
            d.tanggal_upload,
            d.status_dokumen,
            d.file_nama,
            k.nama_kategori,
            j.nama_jurusan,
            p.nama_prodi,
            d.tahun
        FROM dokumen d
        LEFT JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori
        LEFT JOIN jurusan j ON d.id_jurusan = j.id_jurusan
        LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
        WHERE d.id_user = ?
        ORDER BY d.tanggal_upload DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $my_documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $my_documents = [];
}

 $initials = '';
if (!empty($user_data['username'])) {
    $username_parts = explode('_', $user_data['username']);
    if (count($username_parts) > 1) {
        $initials = strtoupper(substr($username_parts[0], 0, 1) . substr(end($username_parts), 0, 1));
    } else {
        $initials = strtoupper(substr($user_data['username'], 0, 2));
    }
}

function getInitialsBackgroundColor($username) {
    $colors = [
        '#4285F4', '#1E88E5', '#039BE5', '#00ACC1', '#00BCD4', '#26C6DA', 
        '#26A69A', '#42A5F5', '#5C6BC0', '#7E57C2', '#9575CD', '#64B5F6'
    ];
    
    $index = 0;
    for ($i = 0; $i < strlen($username); $i++) {
        $index += ord($username[$i]);
    }
    
    return $colors[$index % count($colors)];
}

function getContrastColor($hexColor) {
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));
    
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

function hasProfilePhoto($user_id) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    return file_exists($photo_path);
}

function getProfilePhotoUrl($user_id, $email, $username) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    if (file_exists($photo_path)) {
        return 'uploads/profile_photos/' . $user_id . '.jpg?t=' . time();
    } else {
        return 'profile_image.php?id=' . $user_id . '&email=' . urlencode($email) . '&name=' . urlencode($username) . '&t=' . time();
    }
}

function getInitialsHtml($username, $size = 'normal') {
    $username_parts = explode('_', $username);
    if (count($username_parts) > 1) {
        $initials = strtoupper(substr($username_parts[0], 0, 1) . substr(end($username_parts), 0, 1));
    } else {
        $initials = strtoupper(substr($username, 0, 2));
    }
    
    $bgColor = getInitialsBackgroundColor($username);
    $textColor = getContrastColor($bgColor);
    
    $sizeClass = '';
    $style = '';
    
    switch($size) {
        case 'small':
            $sizeClass = 'initials-small';
            $style = "width: 40px; height: 40px; font-size: 16px;";
            break;
        case 'large':
            $sizeClass = 'initials-large';
            $style = "width: 100px; height: 100px; font-size: 36px;";
            break;
        case 'normal':
        default:
            $sizeClass = 'initials-normal';
            $style = "width: 68px; height: 68px; font-size: 24px;";
            break;
    }
    
    return "<div class='user-initials {$sizeClass}' style='background-color: {$bgColor}; color: {$textColor}; {$style}'>{$initials}</div>";
}

function getRoleName($role) {
    switch($role) {
        case 'admin': return 'Admin';
        case 'mahasiswa': return 'Mahasiswa';
        default: return 'Pengguna';
    }
}

function getStatusBadge($status) {
    switch($status) {
        case 'berhasil': return 'badge-success';
        case 'sedang_dikoreksi': return 'badge-warning';
        case 'gagal': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

function getStatusName($status) {
    switch($status) {
        case 'berhasil': return 'Diterbitkan';
        case 'sedang_dikoreksi': return 'Review';
        case 'gagal': return 'Ditolak';
        default: return 'Unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Unggah Dokumen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-blue: #0058e4;
      --primary-light: #e9f0ff;
      --light-blue: #64B5F6;
      --background-page: #f5f7fa;
      --white: #ffffff;
      --text-primary: #222222;
      --text-secondary: #666666;
      --text-muted: #555555;
      --border-color: #dcdcdc;
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
      --student-color: #4a6fdc;
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--background-page);
      color: var(--text-primary);
      position: relative;
    }

    .user-initials {
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
      background-color: var(--student-color);
      color: white;
    }
    
    .user-initials:hover {
      transform: scale(1.05);
    }
    
    .user-initials-small {
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      background-color: var(--student-color);
      color: white;
    }

    .bg-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
      pointer-events: none;
    }

    .bg-circle {
      position: absolute;
      border-radius: 50%;
      opacity: 0.03;
      animation: float 25s infinite ease-in-out;
    }

    .bg-circle:nth-child(1) {
      width: 300px;
      height: 300px;
      background: var(--student-color);
      top: -150px;
      right: -100px;
      animation-delay: 0s;
    }

    .bg-circle:nth-child(2) {
      width: 250px;
      height: 250px;
      background: var(--student-color);
      bottom: -120px;
      left: -80px;
      animation-delay: 5s;
    }

    .bg-circle:nth-child(3) {
      width: 200px;
      height: 200px;
      background: var(--student-color);
      top: 40%;
      left: 5%;
      animation-delay: 10s;
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0) rotate(0deg);
      }
      50% {
        transform: translateY(-20px) rotate(5deg);
      }
    }

    nav {
      background-color: var(--white);
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    
    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .brand img {
      height: 44px;
    }
    
    .brand span {
      font-weight: 600;
      font-size: 16px;
      color: var(--text-primary);
    }
    
    .nav-links {
      display: flex;
      align-items: center;
      gap: 26px;
    }
    
    .nav-links a {
      text-decoration: none;
      color: var(--text-secondary);
      font-weight: 500;
      font-size: 15px;
      transition: color 0.25s ease;
    }
    
    .nav-links a:hover, .nav-links a.active {
      color: var(--student-color);
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      position: relative;
    }
    
    .user-info span {
      font-weight: 500;
      font-size: 15px;
      color: var(--text-primary);
    }
    
    .user-info img, .user-info .user-initials {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid #eee;
      cursor: pointer;
      transition: transform 0.2s ease;
      object-fit: cover;
    }
    
    .user-info img:hover, .user-info .user-initials:hover {
      transform: scale(1.05);
    }

    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-primary);
      cursor: pointer;
    }

    .user-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 10px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      min-width: 200px;
      z-index: 1001;
      display: none;
      overflow: hidden;
    }
    
    .user-dropdown.active {
      display: block;
      animation: fadeIn 0.2s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .user-dropdown-header {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-dropdown-header img, .user-dropdown-header .user-initials {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
    }
    
    .user-dropdown-header div {
      display: flex;
      flex-direction: column;
    }
    
    .user-dropdown-header .name {
      font-weight: 600;
      font-size: 14px;
    }
    
    .user-dropdown-header .role {
      font-size: 12px;
      color: var(--text-secondary);
    }
    
    .user-dropdown-item {
      padding: 10px 15px;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--text-primary);
      transition: background-color 0.2s ease;
    }
    
    .user-dropdown-item:hover {
      background-color: #f8f9fa;
    }
    
    .user-dropdown-item i {
      font-size: 16px;
      color: var(--text-secondary);
    }
    
    .user-dropdown-divider {
      height: 1px;
      background-color: var(--border-color);
      margin: 5px 0;
    }
    
    .user-dropdown-logout {
      color: #dc3545;
    }
    
    .user-dropdown-logout i {
      color: #dc3545;
    }

    .notification-icon {
      position: relative;
      cursor: pointer;
      margin-right: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      transition: all 0.2s ease;
    }
    
    .notification-icon:hover {
      background-color: #f8f9fa;
    }
    
    .notification-icon i {
      font-size: 20px;
      color: var(--text-secondary);
      transition: color 0.2s ease;
    }
    
    .notification-icon:hover i {
      color: var(--student-color);
    }
    
    .notification-badge {
      position: absolute;
      top: 0;
      right: 0;
      background-color: #dc3545;
      color: white;
      font-size: 10px;
      font-weight: 600;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid var(--white);
    }
    
    .notification-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 10px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      width: 320px;
      max-height: 400px;
      z-index: 1001;
      display: none;
      overflow: hidden;
    }
    
    .notification-dropdown.active {
      display: block;
      animation: fadeIn 0.2s ease;
    }
    
    .notification-header {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .notification-header h5 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
    }
    
    .notification-header a {
      font-size: 12px;
      color: var(--student-color);
      text-decoration: none;
    }
    
    .notification-list {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .notification-item {
      padding: 12px 15px;
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s ease;
    }
    
    .notification-item:hover {
      background-color: #f8f9fa;
    }
    
    .notification-item.unread {
      background-color: #f0f7ff;
    }
    
    .notification-content {
      display: flex;
      gap: 10px;
    }
    
    .notification-icon-wrapper {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .notification-icon-wrapper.info {
      background-color: #e3f2fd;
      color: #1976d2;
    }
    
    .notification-icon-wrapper.success {
      background-color: #e8f5e9;
      color: #388e3c;
    }
    
    .notification-icon-wrapper.warning {
      background-color: #fff8e1;
      color: #f57c00;
    }
    
    .notification-icon-wrapper.error {
      background-color: #ffebee;
      color: #d32f2f;
    }
    
    .notification-text {
      flex: 1;
    }
    
    .notification-title {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 4px;
    }
    
    .notification-message {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 4px;
    }
    
    .notification-time {
      font-size: 11px;
      color: var(--text-muted);
    }
    
    .notification-footer {
      padding: 10px 15px;
      text-align: center;
      border-top: 1px solid var(--border-color);
    }
    
    .notification-footer a {
      font-size: 13px;
      color: var(--student-color);
      text-decoration: none;
    }

    .page-header {
      max-width: 1200px;
      margin: 32px auto;
      background: linear-gradient(90deg, var(--student-color), #7b96e8);
      border-radius: 14px;
      color: var(--white);
      padding: 32px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    }
    
    .page-header h3 {
      font-weight: 600;
      font-size: 20px;
      margin-bottom: 10px;
    }
    
    .page-header small {
      font-size: 14.6px;
      opacity: 0.95;
    }
    
    .page-header img, .page-header .user-initials {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      border: 2px solid var(--white);
      object-fit: cover;
    }

    .main-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .upload-section {
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      margin-bottom: 30px;
    }

    .section-header {
      background-color: var(--primary-light);
      padding: 20px 30px;
      border-bottom: 1px solid var(--border-color);
    }

    .section-header h4 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: var(--student-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-header p {
      margin: 5px 0 0;
      color: var(--text-secondary);
      font-size: 14px;
    }

    .section-body {
      padding: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-primary);
    }

    .form-label .required {
      color: var(--danger-color);
    }

    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--student-color);
      box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.15);
    }

    .form-control.is-invalid {
      border-color: var(--danger-color);
    }

    .invalid-feedback {
      display: none;
      width: 100%;
      margin-top: 0.25rem;
      font-size: 0.875em;
      color: var(--danger-color);
    }

    .form-control.is-invalid ~ .invalid-feedback {
      display: block;
    }

    textarea.form-control {
      resize: vertical;
      min-height: 120px;
    }

    .upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 40px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 20px;
      background-color: #fafafa;
    }

    .upload-area:hover {
      border-color: var(--student-color);
      background-color: #f5f7ff;
    }

    .upload-area.dragover {
      border-color: var(--student-color);
      background-color: #e9f0ff;
    }

    .upload-area i {
      font-size: 48px;
      color: var(--text-secondary);
      margin-bottom: 15px;
    }

    .upload-text {
      color: var(--text-primary);
      font-size: 16px;
      margin-bottom: 10px;
      font-weight: 500;
    }

    .upload-subtext {
      color: var(--text-muted);
      font-size: 14px;
    }

    .file-input {
      display: none;
    }

    .file-info {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      display: none;
      border: 1px solid var(--border-color);
    }

    .file-info.active {
      display: block;
    }

    .file-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .file-name {
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .file-name i {
      color: var(--student-color);
    }

    .file-remove {
      background: none;
      border: none;
      color: var(--danger-color);
      cursor: pointer;
      font-size: 18px;
      transition: color 0.2s;
    }

    .file-remove:hover {
      color: #c82333;
    }

    .file-details {
      display: flex;
      gap: 20px;
      font-size: 14px;
      color: var(--text-secondary);
    }

    .file-size, .file-type {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .progress {
      height: 8px;
      margin-top: 10px;
      background-color: #e9ecef;
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-bar {
      background-color: var(--student-color);
      height: 100%;
      width: 0%;
      transition: width 0.3s ease;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background-color: var(--student-color);
      color: var(--white);
    }

    .btn-primary:hover {
      background-color: #3a5bb5;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background-color: #e9ecef;
      color: var(--text-primary);
    }

    .btn-secondary:hover {
      background-color: #dee2e6;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .form-actions {
      display: flex;
      gap: 15px;
      justify-content: flex-end;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid var(--border-color);
    }

    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert i {
      font-size: 20px;
    }

    .guidelines {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 30px;
      border-left: 4px solid var(--student-color);
    }

    .guidelines h5 {
      margin: 0 0 15px;
      font-size: 16px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .guidelines ul {
      margin: 0;
      padding-left: 20px;
    }

    .guidelines li {
      margin-bottom: 8px;
      color: var(--text-secondary);
      font-size: 14px;
    }

    .guidelines li:last-child {
      margin-bottom: 0;
    }

    .documents-section {
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }

    .document-list {
      max-height: 600px;
      overflow-y: auto;
    }

    .document-item {
      padding: 20px;
      border-bottom: 1px solid var(--border-color);
      transition: background-color 0.2s ease;
    }

    .document-item:hover {
      background-color: #f8f9fa;
    }

    .document-item:last-child {
      border-bottom: none;
    }

    .document-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
    }

    .document-title {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 18px;
      margin-bottom: 8px;
    }

    .document-meta {
      display: flex;
      gap: 20px;
      font-size: 14px;
      color: var(--text-secondary);
      margin-bottom: 15px;
    }

    .document-meta span {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .document-abstract {
      color: var(--text-secondary);
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 15px;
    }

    .document-actions {
      display: flex;
      gap: 12px;
    }

    .badge {
      font-size: 12px;
      padding: 6px 12px;
      border-radius: 6px;
      font-weight: 500;
    }

    .badge-success { background: #d1f7c4; color: #2e7d32; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-secondary);
    }

    .empty-state i {
      font-size: 64px;
      color: var(--border-color);
      margin-bottom: 20px;
    }

    .empty-state h5 {
      margin-bottom: 15px;
      color: var(--text-primary);
      font-size: 20px;
    }

    .empty-state p {
      font-size: 16px;
      margin-bottom: 25px;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--shadow-sm);
      text-align: center;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }

    .stat-card i {
      font-size: 36px;
      color: var(--student-color);
      margin-bottom: 15px;
    }

    .stat-card h5 {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-primary);
      margin: 0 0 5px;
    }

    .stat-card p {
      color: var(--text-secondary);
      margin: 0;
      font-size: 14px;
    }

    .notification-toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      display: none;
      z-index: 9999;
      min-width: 300px;
      transform: translateX(400px);
      transition: transform 0.3s ease;
    }

    .notification-toast.show {
      display: block;
      transform: translateX(0);
    }

    .notification-toast.success {
      border-left: 4px solid var(--success-color);
    }

    .notification-toast.error {
      border-left: 4px solid var(--danger-color);
    }

    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .notification-title {
      font-weight: 600;
      font-size: 16px;
    }

    .notification-close {
      background: none;
      border: none;
      font-size: 18px;
      color: var(--text-muted);
      cursor: pointer;
    }

    .notification-body {
      font-size: 14px;
      color: var(--text-secondary);
    }

    footer {
      text-align: center;
      color: #777;
      font-size: 0.93rem;
      margin-top: 80px;
      padding: 25px 0;
      border-top: 1px solid #ddd;
    }

    @media (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }
      
      .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: var(--white);
        flex-direction: column;
        padding: 15px 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
      
      .nav-links.active {
        display: flex;
      }
      
      .nav-links a {
        padding: 10px 20px;
        width: 100%;
      }
      
      .user-info span {
        display: none;
      }
      
      .page-header {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
      }
      
      .page-header div {
        margin-bottom: 15px;
      }
      
      .main-container {
        margin: 20px auto;
        padding: 0 15px;
      }
      
      .section-body {
        padding: 20px;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }

      .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }
    }

    @media (max-width: 576px) {
      .nav-container {
        padding: 10px 15px;
      }
      
      .brand img {
        height: 36px;
      }
      
      .brand span {
        font-size: 14px;
      }
      
      .page-header {
        margin: 20px 15px;
        padding: 20px 15px;
      }
      
      .page-header h3 {
        font-size: 18px;
      }
      
      .page-header small {
        font-size: 13px;
      }
      
      .page-header img, .page-header .user-initials {
        width: 50px;
        height: 50px;
      }
      
      .upload-area {
        padding: 30px 20px;
      }
      
      .upload-area i {
        font-size: 36px;
      }
      
      .upload-text {
        font-size: 15px;
      }
      
      .upload-subtext {
        font-size: 13px;
      }
      
      .document-item {
        padding: 15px;
      }

      .document-title {
        font-size: 16px;
      }

      .document-meta {
        flex-direction: column;
        gap: 8px;
      }

      .document-actions {
        flex-direction: column;
        gap: 8px;
      }
      
      footer {
        margin-top: 60px;
        padding: 20px 15px;
        font-size: 0.85rem;
      }

      .stats-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <nav>
    <div class="nav-container">
      <div class="brand">
        <img src="assets/logo_polije.png" alt="Logo">
        <span>SIPORA</span>
      </div>
      <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="bi bi-list"></i>
      </button>
      <div class="nav-links" id="navLinks">
        <a href="home.php">Beranda</a>
        <a href="upload.php" class="active">Unggah Dokumen</a>
        <a href="browser.php">Jelajah</a>
        <a href="search.php">Pencarian</a>
        <a href="my_documents.php">Dokumen Saya</a>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($user_data['username']); ?></span>
        
        <div class="notification-icon" id="notificationIcon">
          <i class="bi bi-bell-fill"></i>
          <span class="notification-badge">3</span>
          
          <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
              <h5>Notifikasi</h5>
              <a href="#" onclick="markAllAsRead()">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon-wrapper info">
                    <i class="bi bi-info-circle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Dokumen Baru</div>
                    <div class="notification-message">Dokumen "Analisis Data dengan Machine Learning" telah ditambahkan ke repository.</div>
                    <div class="notification-time">2 jam yang lalu</div>
                  </div>
                </div>
              </div>
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon-wrapper success">
                    <i class="bi bi-check-circle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Upload Berhasil</div>
                    <div class="notification-message">Dokumen "Skripsi Teknik Informatika" Anda telah berhasil diunggah.</div>
                    <div class="notification-time">5 jam yang lalu</div>
                  </div>
                </div>
              </div>
              <div class="notification-item unread">
                <div class="notification-content">
                  <div class="notification-icon-wrapper warning">
                    <i class="bi bi-exclamation-triangle"></i>
                  </div>
                  <div class="notification-text">
                    <div class="notification-title">Pengingat</div>
                    <div class="notification-message">Jangan lupa untuk mengunggah laporan akhir Anda sebelum deadline.</div>
                    <div class="notification-time">1 hari yang lalu</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="notification-footer">
              <a href="#">Lihat semua notifikasi</a>
            </div>
          </div>
        </div>
        
        <div id="userAvatarContainer">
          <?php 
          if (hasProfilePhoto($user_id)) {
              echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" id="userAvatar">';
          } else {
              echo getInitialsHtml($user_data['username'], 'small');
          }
          ?>
        </div>
        
        <div class="user-dropdown" id="userDropdown">
          <div class="user-dropdown-header">
            <div id="dropdownAvatarContainer">
              <?php 
              if (hasProfilePhoto($user_id)) {
                  echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
              } else {
                  echo getInitialsHtml($user_data['username'], 'small');
              }
              ?>
            </div>
            <div>
              <div class="name"><?php echo htmlspecialchars($user_data['username']); ?></div>
              <div class="role"><?php echo getRoleName($user_data['role']); ?></div>
            </div>
          </div>
          <a href="#" class="user-dropdown-item" onclick="openProfileModal()">
            <i class="bi bi-person"></i>
            <span>Profil Saya</span>
          </a>
          <a href="#" class="user-dropdown-item" onclick="openSettingsModal()">
            <i class="bi bi-gear"></i>
            <span>Pengaturan</span>
          </a>
          <a href="#" class="user-dropdown-item" onclick="openHelpModal()">
            <i class="bi bi-question-circle"></i>
            <span>Bantuan</span>
          </a>
          <div class="user-dropdown-divider"></div>
          <a href="?logout=true" class="user-dropdown-item user-dropdown-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="page-header">
    <div>
      <h3>Unggah Dokumen</h3>
      <small>Bagikan karya akademik Anda ke repository POLITEKNIK NEGERI JEMBER</small>
    </div>
    <div id="headerAvatarContainer">
      <?php 
      if (hasProfilePhoto($user_id)) {
          echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar">';
      } else {
          echo getInitialsHtml($user_data['username'], 'normal');
      }
      ?>
    </div>
  </div>

  <div class="main-container">
    <!-- Statistics Cards -->
    <div class="stats-row">
      <div class="stat-card">
        <i class="bi bi-file-earmark-text"></i>
        <h5><?php echo count($my_documents); ?></h5>
        <p>Total Dokumen</p>
      </div>
      <div class="stat-card">
        <i class="bi bi-clock-history"></i>
        <h5><?php echo count(array_filter($my_documents, function($doc) { return $doc['status_dokumen'] === 'sedang_dikoreksi'; })); ?></h5>
        <p>Sedang Review</p>
      </div>
      <div class="stat-card">
        <i class="bi bi-check-circle"></i>
        <h5><?php echo count(array_filter($my_documents, function($doc) { return $doc['status_dokumen'] === 'berhasil'; })); ?></h5>
        <p>Diterbitkan</p>
      </div>
      <div class="stat-card">
        <i class="bi bi-cloud-upload"></i>
        <h5><?php echo count(array_filter($my_documents, function($doc) { return date('Y-m', strtotime($doc['tanggal_upload'])) === date('Y-m'); })); ?></h5>
        <p>Upload Bulan Ini</p>
      </div>
    </div>

    <!-- Upload Section -->
    <div class="upload-section">
      <div class="section-header">
        <h4><i class="bi bi-cloud-upload"></i> Form Unggah Dokumen</h4>
        <p>Lengkapi form di bawah ini untuk mengunggah dokumen Anda</p>
      </div>
      
      <div class="section-body">
        <?php if (isset($upload_success)): ?>
          <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo htmlspecialchars($upload_success); ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($upload_error)): ?>
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo htmlspecialchars($upload_error); ?>
          </div>
        <?php endif; ?>
        
        <div class="guidelines">
          <h5><i class="bi bi-info-circle"></i> Panduan Unggah Dokumen</h5>
          <ul>
            <li>Pastikan dokumen yang diunggah adalah karya asli Anda</li>
            <li>Dokumen akan melalui proses review sebelum dipublikasikan</li>
            <li>Format file yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX</li>
            <li>Ukuran file maksimal: 10MB</li>
            <li>Isi informasi dokumen dengan lengkap dan akurat</li>
          </ul>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="upload_document" value="1">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label" for="judul">
                  Judul Dokumen <span class="required">*</span>
                </label>
                <input type="text" class="form-control" id="judul" name="judul" 
                       value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>" 
                       required placeholder="Masukkan judul dokumen">
                <div class="invalid-feedback">Judul dokumen wajib diisi</div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label" for="tahun">
                  Tahun <span class="required">*</span>
                </label>
                <select class="form-control" id="tahun" name="tahun" required>
                  <option value="">Pilih Tahun</option>
                  <?php foreach ($tahun_data as $tahun): ?>
                    <option value="<?php echo $tahun['tahun']; ?>" 
                            <?php echo (isset($_POST['tahun']) && $_POST['tahun'] == $tahun['tahun']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($tahun['tahun']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Tahun wajib dipilih</div>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="id_kategori">
              Kategori Dokumen <span class="required">*</span>
            </label>
            <select class="form-control" id="id_kategori" name="id_kategori" required>
              <option value="">Pilih Kategori</option>
              <?php foreach ($kategori_data as $kategori): ?>
                <option value="<?php echo $kategori['id_kategori']; ?>" 
                        <?php echo (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $kategori['id_kategori']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Kategori dokumen wajib dipilih</div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="abstrak">
              Abstrak <span class="required">*</span>
            </label>
            <textarea class="form-control" id="abstrak" name="abstrak" rows="5" required 
                      placeholder="Tuliskan abstrak atau ringkasan dokumen Anda"><?php echo isset($_POST['abstrak']) ? htmlspecialchars($_POST['abstrak']) : ''; ?></textarea>
            <div class="invalid-feedback">Abstrak wajib diisi</div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label" for="id_jurusan">Jurusan</label>
                <select class="form-control" id="id_jurusan" name="id_jurusan">
                  <option value="">Pilih Jurusan</option>
                  <?php foreach ($jurusan_data as $jurusan): ?>
                    <option value="<?php echo $jurusan['id_jurusan']; ?>" 
                            <?php echo (isset($_POST['id_jurusan']) && $_POST['id_jurusan'] == $jurusan['id_jurusan']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($jurusan['nama_jurusan']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label" for="id_prodi">Program Studi</label>
                <select class="form-control" id="id_prodi" name="id_prodi">
                  <option value="">Pilih Program Studi</option>
                  <?php foreach ($prodi_data as $prodi): ?>
                    <option value="<?php echo $prodi['id_prodi']; ?>" 
                            <?php echo (isset($_POST['id_prodi']) && $_POST['id_prodi'] == $prodi['id_prodi']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($prodi['nama_prodi']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="kata_kunci">Kata Kunci</label>
            <input type="text" class="form-control" id="kata_kunci" name="kata_kunci" 
                   value="<?php echo isset($_POST['kata_kunci']) ? htmlspecialchars($_POST['kata_kunci']) : ''; ?>" 
                   placeholder="Pisahkan dengan koma (contoh: machine learning, AI, data science)">
            <small class="text-muted">Masukkan kata kunci yang relevan dengan dokumen Anda</small>
          </div>
          
          <div class="form-group">
            <label class="form-label">
              File Dokumen <span class="required">*</span>
            </label>
            <div class="upload-area" id="uploadArea">
              <i class="bi bi-cloud-upload"></i>
              <p class="upload-text">Klik untuk memilih file atau drag and drop</p>
              <p class="upload-subtext">Maksimal ukuran file: 10MB (PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX)</p>
            </div>
            <input type="file" id="dokumen_file" name="dokumen_file" class="file-input" 
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" required>
            
            <div id="fileInfo" class="file-info">
              <div class="file-header">
                <div class="file-name">
                  <i class="bi bi-file-earmark-text"></i>
                  <span id="fileName"></span>
                </div>
                <button type="button" class="file-remove" onclick="removeFile()">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
              <div class="file-details">
                <div class="file-size">
                  <i class="bi bi-hdd"></i>
                  <span id="fileSize"></span>
                </div>
                <div class="file-type">
                  <i class="bi bi-filetype"></i>
                  <span id="fileType"></span>
                </div>
              </div>
              <div class="progress">
                <div class="progress-bar" id="uploadProgress"></div>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">
              <i class="bi bi-arrow-clockwise"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <i class="bi bi-cloud-upload"></i> Unggah Dokumen
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Documents Section -->
    <div class="documents-section">
      <div class="section-header">
        <h4><i class="bi bi-folder"></i> Dokumen Saya</h4>
        <p>Dokumen yang telah Anda unggah</p>
      </div>
      
      <div class="section-body">
        <?php if (empty($my_documents)): ?>
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>Belum Ada Dokumen</h5>
            <p>Anda belum mengunggah dokumen apapun. Mulai dengan mengunggah dokumen pertama Anda!</p>
            <a href="#" onclick="document.getElementById('uploadArea').scrollIntoView({behavior: 'smooth'})" class="btn btn-primary">
              <i class="bi bi-cloud-upload"></i> Unggah Dokumen Pertama
            </a>
          </div>
        <?php else: ?>
          <div class="document-list">
            <?php foreach ($my_documents as $doc): ?>
              <div class="document-item">
                <div class="document-header">
                  <div>
                    <div class="document-title"><?php echo htmlspecialchars($doc['judul']); ?></div>
                    <div class="document-meta">
                      <span><i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($doc['tanggal_upload'])); ?></span>
                      <span><i class="bi bi-tag"></i> <?php echo htmlspecialchars($doc['nama_kategori']); ?></span>
                      <?php if ($doc['tahun']): ?>
                        <span><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($doc['tahun']); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <span class="badge <?php echo getStatusBadge($doc['status_dokumen']); ?>">
                    <?php echo getStatusName($doc['status_dokumen']); ?>
                  </span>
                </div>
                
                <?php if (!empty($doc['abstrak'])): ?>
                  <div class="document-abstract">
                    <?php echo htmlspecialchars($doc['abstrak']); ?>
                  </div>
                <?php endif; ?>
                
                <div class="document-actions">
                  <button class="btn btn-primary" onclick="viewDocument(<?php echo $doc['id_dokumen']; ?>)">
                    <i class="bi bi-eye"></i> Lihat Detail
                  </button>
                  <?php if ($doc['status_dokumen'] === 'berhasil'): ?>
                    <button class="btn btn-secondary" onclick="downloadDocument(<?php echo $doc['id_dokumen']; ?>)">
                      <i class="bi bi-download"></i> Unduh
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="notification-toast" id="notificationToast">
    <div class="notification-header">
      <div class="notification-title" id="notificationTitle">Notifikasi</div>
      <button class="notification-close" onclick="hideNotification()">&times;</button>
    </div>
    <div class="notification-body" id="notificationBody">
      Pesan notifikasi
    </div>
  </div>

  <footer>© 2025 SIPORA - Sistem Informasi Portal Repository Akademik POLITEKNIK NEGERI JEMBER</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('active');
    });

    document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('userDropdown').classList.toggle('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    document.getElementById('notificationIcon').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('notificationDropdown').classList.toggle('active');
      document.getElementById('userDropdown').classList.remove('active');
    });

    document.addEventListener('click', function() {
      document.getElementById('userDropdown').classList.remove('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    document.getElementById('userDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    document.getElementById('notificationDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // Upload area functionality
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('dokumen_file');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    const uploadProgress = document.getElementById('uploadProgress');
    const submitBtn = document.getElementById('submitBtn');
    const uploadForm = document.getElementById('uploadForm');

    // Click to upload
    uploadArea.addEventListener('click', () => {
      fileInput.click();
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
      uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(files[0]);
      }
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
      }
    });

    function handleFileSelect(file) {
      // Validate file type
      const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                           'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                           'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
      
      if (!allowedTypes.includes(file.type)) {
        showNotification('error', 'Error', 'Tipe file tidak didukung. Silakan pilih file PDF, DOC, DOCX, PPT, PPTX, XLS, atau XLSX.');
        fileInput.value = '';
        return;
      }

      // Validate file size
      const maxSize = 10 * 1024 * 1024; // 10MB
      if (file.size > maxSize) {
        showNotification('error', 'Error', 'Ukuran file terlalu besar. Maksimal ukuran file adalah 10MB.');
        fileInput.value = '';
        return;
      }

      // Display file info
      fileName.textContent = file.name;
      fileSize.textContent = formatFileSize(file.size);
      fileType.textContent = getFileTypeLabel(file.type);
      fileInfo.classList.add('active');
      uploadProgress.style.width = '0%';
    }

    function removeFile() {
      fileInput.value = '';
      fileInfo.classList.remove('active');
      uploadProgress.style.width = '0%';
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileTypeLabel(mimeType) {
      const typeMap = {
        'application/pdf': 'PDF',
        'application/msword': 'DOC',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
        'application/vnd.ms-powerpoint': 'PPT',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PPTX',
        'application/vnd.ms-excel': 'XLS',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX'
      };
      return typeMap[mimeType] || 'Unknown';
    }

    // Form validation
    uploadForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Reset validation states
      document.querySelectorAll('.form-control').forEach(el => {
        el.classList.remove('is-invalid');
      });
      
      let isValid = true;
      
      // Validate required fields
      const requiredFields = ['judul', 'tahun', 'id_kategori', 'abstrak'];
      requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
          field.classList.add('is-invalid');
          isValid = false;
        }
      });
      
      // Validate file
      if (!fileInput.files.length) {
        showNotification('error', 'Error', 'File dokumen wajib dipilih');
        isValid = false;
      }
      
      if (!isValid) {
        return;
      }
      
      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengunggah...';
      
      // Simulate upload progress
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        uploadProgress.style.width = progress + '%';
      }, 200);
      
      // Create FormData
      const formData = new FormData(uploadForm);
      
      // Submit form
      fetch('upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        clearInterval(progressInterval);
        uploadProgress.style.width = '100%';
        
        setTimeout(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Unggah Dokumen';
          
          // Check if upload was successful by looking for success message in response
          if (data.includes('alert-success')) {
            showNotification('success', 'Berhasil', 'Dokumen berhasil diunggah dan akan melalui proses review.');
            setTimeout(() => {
              window.location.reload();
            }, 2000);
          } else {
            showNotification('error', 'Error', 'Terjadi kesalahan saat mengunggah dokumen. Silakan coba lagi.');
          }
        }, 500);
      })
      .catch(error => {
        clearInterval(progressInterval);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Unggah Dokumen';
        showNotification('error', 'Error', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
      });
    });

    function resetForm() {
      uploadForm.reset();
      removeFile();
      document.querySelectorAll('.form-control').forEach(el => {
        el.classList.remove('is-invalid');
      });
    }

    function viewDocument(docId) {
      window.location.href = 'view_document.php?id=' + docId;
    }

    function downloadDocument(docId) {
      window.location.href = 'home.php?download=' + docId;
    }

    function showNotification(type, title, message) {
      const notification = document.getElementById('notificationToast');
      const notificationTitle = document.getElementById('notificationTitle');
      const notificationBody = document.getElementById('notificationBody');
      
      notification.className = `notification-toast ${type}`;
      notificationTitle.textContent = title;
      notificationBody.textContent = message;
      
      notification.classList.add('show');
      
      setTimeout(() => {
        hideNotification();
      }, 5000);
    }

    function hideNotification() {
      const notification = document.getElementById('notificationToast');
      notification.classList.remove('show');
    }

    function markAllAsRead() {
      document.querySelectorAll('.notification-item').forEach(item => {
        item.classList.remove('unread');
      });
      
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        badge.style.display = 'none';
      }
      
      return false;
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
  </script>
</body>
</html>
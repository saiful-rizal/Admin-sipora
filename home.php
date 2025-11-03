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
        $judul = isset($_POST['judul']) ? $_POST['judul'] : '';
        $abstrak = isset($_POST['abstrak']) ? $_POST['abstrak'] : '';
        $id_kategori = isset($_POST['id_kategori']) ? $_POST['id_kategori'] : '';
        $id_jurusan = isset($_POST['id_jurusan']) ? $_POST['id_jurusan'] : '';
        $id_prodi = isset($_POST['id_prodi']) ? $_POST['id_prodi'] : '';
        $kata_kunci = isset($_POST['kata_kunci']) ? $_POST['kata_kunci'] : '';
        
        // Validasi input
        if (empty($judul) || empty($abstrak) || empty($id_kategori)) {
            $upload_error = "Judul, abstrak, dan kategori wajib diisi";
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
                            (judul, abstrak, id_kategori, id_jurusan, id_prodi, kata_kunci, file_nama, id_user, tanggal_upload, status_dokumen) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->bind_param("ssiiisssss", $judul, $abstrak, $id_kategori, $id_jurusan, $id_prodi, $kata_kunci, $newFileName, $user_id, $tanggal_upload, $status_dokumen);
                        $stmt->execute();
                        
                        $upload_success = "Dokumen berhasil diunggah dan akan melalui proses review sebelum dipublikasikan.";
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

// Proses download dokumen
if (isset($_GET['download']) && !empty($_GET['download'])) {
    try {
        $doc_id = $_GET['download'];
        
        // Ambil informasi dokumen
        $stmt = $conn->prepare("SELECT * FROM dokumen WHERE id_dokumen = ? AND status_dokumen = 'berhasil'");
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $document = $result->fetch_assoc();
            $filePath = __DIR__ . '/uploads/documents/' . $document['file_nama'];
            
            if (file_exists($filePath)) {
                // Catat aktivitas download
                $tanggal_masuk = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO library_mahasiswa (id_user, id_dokumen, tanggal_masuk) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $user_id, $doc_id, $tanggal_masuk);
                $stmt->execute();
                
                // Mulai download
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($document['judul']) . '.' . pathinfo($document['file_nama'], PATHINFO_EXTENSION) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                $download_error = "File tidak ditemukan";
            }
        } else {
            $download_error = "Dokumen tidak tersedia atau belum disetujui";
        }
    } catch (Exception $e) {
        $download_error = "Error downloading document: " . $e->getMessage();
    }
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

try {
    // Menghitung statistik dari database untuk mahasiswa
    $total_dokumen = $conn->query("SELECT COUNT(*) FROM dokumen")->fetch_row()[0];
    $total_skripsi = $conn->query("SELECT COUNT(*) FROM dokumen WHERE id_kategori = 1")->fetch_row()[0];
    $total_tugas_akhir = $conn->query("SELECT COUNT(*) FROM dokumen WHERE id_kategori = 2")->fetch_row()[0];
    
    // Menghitung statistik download mahasiswa ini bulan ini
    $total_downloads = $conn->query("SELECT COUNT(*) FROM library_mahasiswa WHERE id_user = $user_id AND MONTH(tanggal_masuk) = MONTH(CURRENT_DATE) AND YEAR(tanggal_masuk) = YEAR(CURRENT_DATE)")->fetch_row()[0];
    
    // Menghitung statistik upload mahasiswa ini bulan ini
    $total_uploads = $conn->query("SELECT COUNT(*) FROM dokumen WHERE id_user = $user_id AND MONTH(tanggal_upload) = MONTH(CURRENT_DATE) AND YEAR(tanggal_upload) = YEAR(CURRENT_DATE)")->fetch_row()[0];
    
    // Total download mahasiswa ini
    $total_my_downloads = $conn->query("SELECT COUNT(*) FROM library_mahasiswa WHERE id_user = $user_id")->fetch_row()[0];
    
    // Total upload mahasiswa ini
    $total_my_uploads = $conn->query("SELECT COUNT(*) FROM dokumen WHERE id_user = $user_id")->fetch_row()[0];
    
    $stats_data = [
        'total_dokumen' => $total_dokumen,
        'total_skripsi' => $total_skripsi,
        'total_tugas_akhir' => $total_tugas_akhir,
        'total_downloads' => $total_downloads,
        'total_uploads' => $total_uploads,
        'total_my_downloads' => $total_my_downloads,
        'total_my_uploads' => $total_my_uploads
    ];
} catch (Exception $e) {
    $stats_data = [
        'total_dokumen' => 0,
        'total_skripsi' => 0,
        'total_tugas_akhir' => 0,
        'total_downloads' => 0,
        'total_uploads' => 0,
        'total_my_downloads' => 0,
        'total_my_uploads' => 0
    ];
}

try {
    $jurusan_data = $conn->query("SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan")->fetch_all(MYSQLI_ASSOC);
    $prodi_data = $conn->query("SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi")->fetch_all(MYSQLI_ASSOC);
    $tahun_data = $conn->query("SELECT DISTINCT YEAR(tanggal_upload) AS tahun FROM dokumen ORDER BY tahun DESC")->fetch_all(MYSQLI_ASSOC);
    $kategori_data = $conn->query("SELECT id_kategori, nama_kategori FROM kategori_perpustakaan ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $jurusan_data = [];
    $prodi_data = [];
    $tahun_data = [];
    $kategori_data = [];
}

 $filter_jurusan = isset($_GET['filter_jurusan']) ? $_GET['filter_jurusan'] : '';
 $filter_prodi = isset($_GET['filter_prodi']) ? $_GET['filter_prodi'] : '';
 $filter_tahun = isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : '';

 $query = "
SELECT 
   d.id_dokumen AS id_book, 
   d.judul AS title, 
   k.nama_kategori AS type,
   d.abstrak AS abstract,
   j.nama_jurusan AS department,
   p.nama_prodi AS prodi,
   YEAR(d.tanggal_upload) AS year,
   d.file_nama AS file_name,
   d.tanggal_upload AS upload_date,
   d.status_dokumen AS status_id
FROM dokumen d
LEFT JOIN kategori_perpustakaan k ON d.id_kategori = k.id_kategori
LEFT JOIN jurusan j ON d.id_jurusan = j.id_jurusan
LEFT JOIN prodi p ON d.id_prodi = p.id_prodi
WHERE 1=1
";

 $params = [];
 $types = '';

if (!empty($filter_jurusan)) {
    $query .= " AND d.id_jurusan = ?";
    $params[] = $filter_jurusan;
    $types .= 'i';
}
if (!empty($filter_prodi)) {
    $query .= " AND d.id_prodi = ?";
    $params[] = $filter_prodi;
    $types .= 'i';
}
if (!empty($filter_tahun)) {
    $query .= " AND YEAR(d.tanggal_upload) = ?";
    $params[] = $filter_tahun;
    $types .= 'i';
}

 $query .= " ORDER BY d.tanggal_upload DESC LIMIT 10";

try {
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $documents_data = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $documents_data = [];
}

try {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM dokumen WHERE id_user = u.id_user) AS uploaded_docs,
            (SELECT COUNT(*) FROM library_mahasiswa WHERE id_user = u.id_user) AS downloaded_docs,
            (SELECT COUNT(*) FROM dokumen WHERE id_user = u.id_user AND MONTH(tanggal_upload) = MONTH(CURRENT_DATE) AND YEAR(tanggal_upload) = YEAR(CURRENT_DATE)) AS monthly_uploads
        FROM users u
        WHERE u.id_user = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile_data = $result->fetch_assoc();
} catch (Exception $e) {
    $profile_data = [];
}

function getStatusBadge($status_id) {
    switch($status_id) {
        case 'berhasil': return 'badge-success';
        case 'sedang_dikoreksi': return 'badge-warning';
        case 'gagal': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

function getStatusName($status_id) {
    switch($status_id) {
        case 'berhasil': return 'Diterbitkan';
        case 'sedang_dikoreksi': return 'Review';
        case 'gagal': return 'Ditolak';
        default: return 'Unknown';
    }
}

function getDocumentTypeName($type) {
    switch($type) {
        case 'Skripsi': return 'Skripsi';
        case 'Tugas Akhir': return 'Tugas Akhir';
        case 'Tesis': return 'Tesis';
        case 'Disertasi': return 'Disertasi';
        case 'Penelitian': return 'Penelitian';
        default: return $type;
    }
}

function getRoleName($role) {
    switch($role) {
        case 'admin': return 'Admin';
        case 'mahasiswa': return 'Mahasiswa';
        default: return 'Pengguna';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $nama_lengkap = isset($_POST['nama_lengkap']) ? $_POST['nama_lengkap'] : '';
        $nim = isset($_POST['nim']) ? $_POST['nim'] : '';
        
        if (!empty($username)) {
            $stmt = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()) {
                $profile_error = "Username sudah digunakan oleh pengguna lain";
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, 
                        nama_lengkap = ?, 
                        nim = ?
                    WHERE id_user = ?
                ");
                
                $stmt->bind_param("sssi", $username, $nama_lengkap, $nim, $user_id);
                $stmt->execute();
                
                $_SESSION['username'] = $username;
                
                $stmt = $conn->prepare("SELECT id_user, username, email, role FROM users WHERE id_user = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                
                $stmt = $conn->prepare("
                    SELECT 
                        u.*,
                        (SELECT COUNT(*) FROM dokumen WHERE id_user = u.id_user) AS uploaded_docs,
                        (SELECT COUNT(*) FROM library_mahasiswa WHERE id_user = u.id_user) AS downloaded_docs,
                        (SELECT COUNT(*) FROM dokumen WHERE id_user = u.id_user AND MONTH(tanggal_upload) = MONTH(CURRENT_DATE) AND YEAR(tanggal_upload) = YEAR(CURRENT_DATE)) AS monthly_uploads
                    FROM users u
                    WHERE u.id_user = ?
                    LIMIT 1
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $profile_data = $result->fetch_assoc();
                
                $profile_updated = true;
            }
        } else {
            $profile_error = "Username tidak boleh kosong";
        }
    } catch (Exception $e) {
        $profile_error = "Error updating profile: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    try {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $photo_error = "Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan";
            } elseif ($fileSize > 2097152) {
                $photo_error = "Ukuran file maksimal 2MB";
            } else {
                $uploadDir = __DIR__ . '/uploads/profile_photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $targetPath = $uploadDir . $user_id . '.jpg';
                
                if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                    move_uploaded_file($fileTmpName, $targetPath);
                } else {
                    if ($fileExtension === 'png') {
                        $image = imagecreatefrompng($fileTmpName);
                    } else {
                        $image = imagecreatefromgif($fileTmpName);
                    }
                    
                    $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    
                    imagejpeg($bg, $targetPath, 90);
                    imagedestroy($image);
                    imagedestroy($bg);
                }
                
                $photo_updated = true;
            }
        } else {
            $photo_error = "Tidak ada file yang dipilih atau terjadi kesalahan";
        }
    } catch (Exception $e) {
        $photo_error = "Error uploading photo: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password_hash'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                
                $password_updated = true;
            } else {
                $password_error = "Password baru dan konfirmasi tidak cocok";
            }
        } else {
            $password_error = "Password saat ini salah";
        }
    } catch (Exception $e) {
        $password_error = "Error updating password: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Portal Mahasiswa</title>
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
    
    .user-initials-large {
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      overflow-x: hidden;
      overflow-y: auto;
      opacity: 0;
      transition: opacity 0.15s ease;
    }

    .modal.show {
      opacity: 1;
    }

    .modal-dialog {
      position: relative;
      width: auto;
      max-width: 500px;
      margin: 1.75rem auto;
      transform: translate(0, -50px);
      transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
      transform: translate(0, 0);
    }

    .modal-content {
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      padding: 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-muted);
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .modal-close:hover {
      color: var(--text-primary);
    }

    .modal-body {
      padding: 20px;
    }

    .profile-header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }

    .profile-avatar-container {
      position: relative;
    }

    .profile-avatar, .profile-avatar .user-initials {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary-light);
    }

    .profile-avatar-edit {
      position: absolute;
      bottom: 0;
      right: 0;
      background-color: var(--student-color);
      color: white;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color 0.2s;
      border: 2px solid white;
    }

    .profile-avatar-edit:hover {
      background-color: #3a5bb5;
    }

    .profile-info h4 {
      margin: 0 0 5px;
      font-size: 18px;
      font-weight: 600;
    }

    .profile-info p {
      margin: 0 0 5px;
      color: var(--text-secondary);
      font-size: 14px;
    }

    .profile-stats {
      display: flex;
      justify-content: space-around;
      margin: 20px 0;
      padding: 15px 0;
      border-top: 1px solid var(--border-color);
      border-bottom: 1px solid var(--border-color);
    }

    .profile-stat {
      text-align: center;
    }

    .profile-stat-value {
      font-size: 18px;
      font-weight: 600;
      color: var(--student-color);
    }

    .profile-stat-label {
      font-size: 12px;
      color: var(--text-secondary);
      margin-top: 5px;
    }

    .profile-details {
      margin-top: 20px;
    }

    .profile-details h5 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .profile-detail-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .profile-detail-item:last-child {
      border-bottom: none;
    }

    .profile-detail-label {
      font-weight: 500;
      color: var(--text-secondary);
    }

    .profile-detail-value {
      color: var(--text-primary);
    }

    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
    }

    .btn-primary {
      background-color: var(--student-color);
      color: var(--white);
    }

    .btn-primary:hover {
      background-color: #3a5bb5;
    }

    .btn-secondary {
      background-color: #e9ecef;
      color: var(--text-primary);
    }

    .btn-secondary:hover {
      background-color: #dee2e6;
    }

    .btn-danger {
      background-color: #dc3545;
      color: var(--white);
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    .edit-profile-form {
      display: none;
    }

    .edit-profile-form.active {
      display: block;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: var(--text-primary);
    }

    .form-control {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--student-color);
      box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.15);
    }

    .photo-upload-container {
      margin-bottom: 20px;
    }

    .photo-upload-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 500;
      color: var(--text-primary);
    }

    .photo-upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s;
    }

    .photo-upload-area:hover {
      border-color: var(--student-color);
    }

    .photo-upload-area i {
      font-size: 32px;
      color: var(--text-secondary);
      margin-bottom: 10px;
    }

    .photo-upload-text {
      color: var(--text-secondary);
      font-size: 14px;
    }

    .photo-upload-input {
      display: none;
    }

    .photo-preview {
      margin-top: 15px;
      text-align: center;
    }

    .photo-preview img {
      max-width: 150px;
      max-height: 150px;
      border-radius: 8px;
      box-shadow: var(--shadow-sm);
    }

    .settings-tabs {
      display: flex;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 20px;
    }

    .settings-tab {
      padding: 10px 15px;
      cursor: pointer;
      font-weight: 500;
      color: var(--text-secondary);
      border-bottom: 2px solid transparent;
      transition: all 0.2s ease;
    }

    .settings-tab.active {
      color: var(--student-color);
      border-bottom-color: var(--student-color);
    }

    .settings-tab-content {
      display: none;
    }

    .settings-tab-content.active {
      display: block;
      animation: fadeIn 0.3s ease;
    }

    .settings-group {
      margin-bottom: 20px;
    }

    .settings-group-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .settings-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .settings-item:last-child {
      border-bottom: none;
    }

    .settings-item-label {
      font-weight: 500;
      color: var(--text-primary);
    }

    .settings-item-description {
      font-size: 12px;
      color: var(--text-secondary);
      margin-top: 3px;
    }

    .toggle-switch {
      position: relative;
      width: 50px;
      height: 24px;
      background-color: #ccc;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .toggle-switch.active {
      background-color: var(--student-color);
    }

    .toggle-switch-slider {
      position: absolute;
      top: 2px;
      left: 2px;
      width: 20px;
      height: 20px;
      background-color: white;
      border-radius: 50%;
      transition: transform 0.3s;
    }

    .toggle-switch.active .toggle-switch-slider {
      transform: translateX(26px);
    }

    .settings-select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
      background-color: var(--white);
    }

    .settings-input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 14px;
    }

    .password-form {
      display: none;
    }

    .password-form.active {
      display: block;
    }

    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 20px;
      background-color: var(--white);
      border-radius: 8px;
      box-shadow: var(--shadow-md);
      display: none;
      z-index: 3000;
      max-width: 350px;
      transform: translateX(400px);
      transition: transform 0.3s ease;
    }

    .notification.show {
      display: block;
      transform: translateX(0);
    }

    .notification.success {
      border-left: 4px solid #28a745;
    }

    .notification.error {
      border-left: 4px solid #dc3545;
    }

    .notification.info {
      border-left: 4px solid #17a2b8;
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

    .header {
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
    
    .header h3 {
      font-weight: 600;
      font-size: 20px;
      margin-bottom: 10px;
    }
    
    .header small {
      font-size: 14.6px;
      opacity: 0.95;
    }
    
    .header img, .header .user-initials {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      border: 2px solid var(--white);
      object-fit: cover;
    }

    .search-box {
      max-width: 1200px;
      margin: 26px auto;
      padding: 0 20px;
    }
    
    .search-box input {
      width: 100%;
      padding: 14px 18px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-size: 14.8px;
      outline: none;
      background-color: var(--white);
      transition: all 0.2s ease;
    }
    
    .search-box input:focus {
      border-color: var(--student-color);
      box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.15);
    }

    .stats {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 24px;
    }
    
    .stat-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 26px 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }
    
    .stat-card i {
      font-size: 30px;
      background-color: var(--primary-light);
      color: var(--student-color);
      padding: 10px;
      border-radius: 10px;
    }
    
    .stat-card h4 {
      font-weight: 700;
      color: var(--text-primary);
      font-size: 21px;
      margin: 0;
    }
    
    .stat-card p {
      margin-top: 5px;
      color: var(--text-secondary);
      font-size: 14.4px;
    }

    .section-header {
      max-width: 1200px;
      margin: 45px auto 16px;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .section-header h5 {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 17px;
    }
    
    .section-header a {
      text-decoration: none;
      color: var(--student-color);
      font-weight: 500;
      font-size: 14.5px;
    }

    .filter-section {
      max-width: 1200px;
      margin: 0 auto 20px;
      padding: 0 20px;
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
      padding: 20px;
    }
    
    .filter-title {
      font-weight: 600;
      font-size: 16px;
      margin-bottom: 15px;
      color: var(--text-primary);
    }
    
    .filter-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
    }
    
    .filter-group {
      flex: 1;
      min-width: 200px;
    }
    
    .filter-label {
      display: block;
      font-weight: 500;
      margin-bottom: 8px;
      color: var(--text-secondary);
      font-size: 14px;
    }
    
    .filter-select {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      background-color: var(--white);
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    .filter-select:focus {
      outline: none;
      border-color: var(--student-color);
      box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.15);
    }
    
    .filter-actions {
      display: flex;
      align-items: flex-end;
      gap: 10px;
    }
    
    .btn-filter {
      background-color: var(--student-color);
      color: var(--white);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .btn-filter:hover {
      background-color: #3a5bb5;
    }
    
    .btn-reset {
      background-color: #e9ecef;
      color: var(--text-primary);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .btn-reset:hover {
      background-color: #dee2e6;
    }

    .document-card {
      max-width: 1200px;
      background-color: var(--white);
      border-radius: 12px;
      padding: 25px 28px;
      margin: 0 auto 20px;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    
    .document-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
    
    .badge {
      font-size: 12.5px;
      padding: 6px 11px;
      border-radius: 6px;
      margin-right: 6px;
      font-weight: 500;
    }
    .badge-success { background: #d1f7c4; color: #2e7d32; }
    .badge-info { background: #cce5ff; color: #004085; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    
    .document-card h6 {
      font-weight: 600;
      margin: 12px 0 10px;
      line-height: 1.55;
      color: var(--text-primary);
      font-size: 15.5px;
    }
    
    .document-card p {
      font-size: 14.3px;
      color: var(--text-secondary);
      margin-bottom: 14px;
      line-height: 1.65;
    }
    
    .doc-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13.4px;
      color: var(--text-muted);
      margin-top: 6px;
    }
    
    .doc-footer small i {
      margin-right: 5px;
    }
    
    .btn-download {
      background-color: var(--student-color);
      color: var(--white);
      border: none;
      padding: 7px 15px;
      border-radius: 7px;
      cursor: pointer;
      transition: background-color 0.25s ease;
      font-size: 13.2px;
      font-weight: 500;
    }
    
    .btn-download:hover {
      background-color: #3a5bb5;
    }

    .empty-state {
      max-width: 1200px;
      margin: 0 auto 20px;
      padding: 0 20px;
    }
    
    .empty-state-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 40px 30px;
      text-align: center;
      box-shadow: var(--shadow-sm);
    }
    
    .empty-state-icon {
      font-size: 64px;
      color: var(--primary-light);
      margin-bottom: 20px;
    }
    
    .empty-state-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 10px;
    }
    
    .empty-state-description {
      font-size: 16px;
      color: var(--text-secondary);
      margin-bottom: 20px;
    }
    
    .empty-state-action {
      display: inline-block;
      padding: 10px 20px;
      background-color: var(--student-color);
      color: var(--white);
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.3s;
    }
    
    .empty-state-action:hover {
      background-color: #3a5bb5;
    }

    .quick-actions {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 24px;
    }
    
    .quick-action-card {
      background-color: var(--white);
      border-radius: 12px;
      padding: 26px 22px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      text-decoration: none;
      color: var(--text-primary);
    }
    
    .quick-action-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
      text-decoration: none;
      color: var(--text-primary);
    }
    
    .quick-action-card i {
      font-size: 36px;
      background-color: var(--primary-light);
      color: var(--student-color);
      padding: 15px;
      border-radius: 50%;
      margin-bottom: 15px;
    }
    
    .quick-action-card h4 {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 18px;
      margin: 0 0 8px;
    }
    
    .quick-action-card p {
      margin: 0;
      color: var(--text-secondary);
      font-size: 14px;
    }

    .upload-modal {
      max-width: 700px;
    }

    .upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 30px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s;
      margin-bottom: 20px;
    }

    .upload-area:hover {
      border-color: var(--student-color);
    }

    .upload-area i {
      font-size: 48px;
      color: var(--text-secondary);
      margin-bottom: 15px;
    }

    .upload-text {
      color: var(--text-secondary);
      font-size: 16px;
      margin-bottom: 10px;
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
    }

    .file-info.active {
      display: block;
    }

    .file-name {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .file-size {
      color: var(--text-secondary);
      font-size: 14px;
    }

    .progress {
      height: 8px;
      margin-top: 10px;
    }

    .progress-bar {
      background-color: var(--student-color);
    }

    footer {
      text-align: center;
      color: #777;
      font-size: 0.93rem;
      margin-top: 55px;
      padding: 25px 0;
      border-top: 1px solid #ddd;
    }

    @media (max-width: 992px) {
      .header {
        padding: 25px 30px;
      }
      
      .header h3 {
        font-size: 18px;
      }
      
      .header small {
        font-size: 13px;
      }
      
      .header img, .header .user-initials {
        width: 60px;
        height: 60px;
      }
      
      .stats {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .filter-container {
        flex-direction: column;
      }
      
      .filter-actions {
        justify-content: flex-start;
        margin-top: 10px;
      }
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
      
      .header {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
      }
      
      .header div {
        margin-bottom: 15px;
      }
      
      .stats {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .stat-card {
        padding: 20px 15px;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .document-card {
        padding: 20px;
      }
      
      .doc-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      
      .doc-footer div {
        display: flex;
        justify-content: space-between;
        width: 100%;
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
      
      .header {
        margin: 20px 15px;
        padding: 20px 15px;
      }
      
      .header h3 {
        font-size: 16px;
      }
      
      .header small {
        font-size: 12px;
      }
      
      .header img, .header .user-initials {
        width: 50px;
        height: 50px;
      }
      
      .search-box {
        margin: 20px 15px;
        padding: 0;
      }
      
      .stats {
        margin: 20px 15px;
        padding: 0;
      }
      
      .stat-card {
        padding: 15px;
      }
      
      .stat-card i {
        font-size: 24px;
        padding: 8px;
      }
      
      .stat-card h4 {
        font-size: 18px;
      }
      
      .stat-card p {
        font-size: 13px;
      }
      
      .section-header {
        margin: 30px 15px 10px;
        padding: 0;
      }
      
      .section-header h5 {
        font-size: 16px;
      }
      
      .filter-section {
        margin: 0 15px 15px;
        padding: 15px;
      }
      
      .filter-group {
        min-width: 100%;
      }
      
      .document-card {
        margin: 0 15px 15px;
        padding: 15px;
      }
      
      .document-card h6 {
        font-size: 14px;
      }
      
      .document-card p {
        font-size: 13px;
      }
      
      .doc-footer {
        font-size: 12px;
      }
      
      .btn-download {
        padding: 6px 12px;
        font-size: 12px;
      }
      
      footer {
        margin-top: 40px;
        padding: 20px 15px;
        font-size: 0.85rem;
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
        <a href="home.php" class="active">Beranda</a>
        <a href="#" onclick="openUploadModal()">Unggah Dokumen</a>
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

  <div class="header">
    <div>
      <h3>Selamat Datang, <?php echo htmlspecialchars($user_data['username']); ?></h3>
      <small>Portal Repository Akademik Mahasiswa POLITEKNIK NEGERI JEMBER</small>
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

  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Cari dokumen, subjek, atau kata kunci...">
  </div>

  <div class="stats">
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($stats_data['total_my_downloads']); ?></h4>
        <p>Dokumen Diunduh</p>
      </div>
      <i class="bi bi-cloud-arrow-down"></i>
    </div>
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($stats_data['total_my_uploads']); ?></h4>
        <p>Dokumen Diunggah</p>
      </div>
      <i class="bi bi-cloud-arrow-up"></i>
    </div>
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($stats_data['total_downloads']); ?></h4>
        <p>Download Bulan Ini</p>
      </div>
      <i class="bi bi-calendar-check"></i>
    </div>
    <div class="stat-card">
      <div>
        <h4><?php echo number_format($stats_data['total_uploads']); ?></h4>
        <p>Upload Bulan Ini</p>
      </div>
      <i class="bi bi-file-earmark-plus"></i>
    </div>
  </div>
  <div class="section-header">
    <h5>Dokumen Terbaru</h5>
    <a href="browser.php">Lihat Semua</a>
  </div>

  <div class="filter-section">
    <div class="filter-title">
      <i class="bi bi-funnel"></i> Filter Dokumen
    </div>
    <form method="GET" action="">
      <div class="filter-container">
        <div class="filter-group">
          <label class="filter-label" for="filter_jurusan">Jurusan</label>
          <select class="filter-select" id="filter_jurusan" name="filter_jurusan">
            <option value="">Semua Jurusan</option>
            <?php foreach ($jurusan_data as $jurusan): ?>
              <option value="<?php echo $jurusan['id_jurusan']; ?>" <?php echo ($filter_jurusan == $jurusan['id_jurusan']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($jurusan['nama_jurusan']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label" for="filter_prodi">Program Studi</label>
          <select class="filter-select" id="filter_prodi" name="filter_prodi">
            <option value="">Semua Program Studi</option>
            <?php foreach ($prodi_data as $prodi): ?>
              <option value="<?php echo $prodi['id_prodi']; ?>" <?php echo ($filter_prodi == $prodi['id_prodi']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($prodi['nama_prodi']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label" for="filter_tahun">Tahun</label>
          <select class="filter-select" id="filter_tahun" name="filter_tahun">
            <option value="">Semua Tahun</option>
            <?php foreach ($tahun_data as $tahun): ?>
              <option value="<?php echo $tahun['tahun']; ?>" <?php echo ($filter_tahun == $tahun['tahun']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tahun['tahun']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn-filter">
            <i class="bi bi-search"></i> Terapkan
          </button>
          <a href="home.php" class="btn-reset">
            <i class="bi-arrow-clockwise"></i> Reset
          </a>
        </div>
      </div>
    </form>
  </div>

  <?php if (empty($documents_data)): ?>
    <div class="empty-state">
      <div class="empty-state-card">
        <div class="empty-state-icon">
          <i class="bi bi-inbox"></i>
        </div>
        <h3 class="empty-state-title">Tidak ada dokumen ditemukan</h3>
        <p class="empty-state-description">Belum ada dokumen yang tersedia di repository dengan filter yang dipilih.</p>
        <a href="#" onclick="openUploadModal()" class="empty-state-action">
          <i class="bi bi-cloud-upload"></i> Unggah Dokumen
        </a>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($documents_data as $document): ?>
      <div class="document-card">
        <span class="badge <?php echo getStatusBadge($document['status_id']); ?>"><?php echo getStatusName($document['status_id']); ?></span>
        <span class="badge badge-danger"><?php echo getDocumentTypeName($document['type']); ?></span>
        <h6><?php echo htmlspecialchars($document['title']); ?></h6>
        <p><?php echo htmlspecialchars($document['abstract']); ?></p>
        <div class="doc-footer">
          <small>
            <i class="bi bi-calendar"></i> <?php echo date('d F Y', strtotime($document['upload_date'])); ?>
            <?php if ($document['department']): ?>
              • <i class="bi bi-building"></i> <?php echo htmlspecialchars($document['department']); ?>
            <?php endif; ?>
            <?php if ($document['prodi']): ?>
              • <i class="bi bi-book"></i> <?php echo htmlspecialchars($document['prodi']); ?>
            <?php endif; ?>
            <?php if ($document['year']): ?>
              • <i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($document['year']); ?>
            <?php endif; ?>
          </small>
          <div>
            <small><i class="bi bi-download"></i> <?php echo rand(100, 500); ?> Download</small>
            <button class="btn-download" onclick="viewDocument(<?php echo $document['id_book']; ?>)">Lihat</button>
            <?php if ($document['status_id'] === 'berhasil'): ?>
              <button class="btn-download" onclick="downloadDocument(<?php echo $document['id_book']; ?>)">
                <i class="bi bi-download"></i> Unduh
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <footer>© 2025 SIPORA - Sistem Informasi Portal Repository Akademik POLITEKNIK NEGERI JEMBER</footer>

  <!-- Modal Upload Dokumen -->
  <div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered upload-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Unggah Dokumen</h5>
          <button type="button" class="modal-close" onclick="closeModal('uploadModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="upload_document" value="1">
            
            <div class="form-group">
              <label class="form-label" for="judul">Judul Dokumen</label>
              <input type="text" class="form-control" id="judul" name="judul" required>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="id_kategori">Kategori</label>
              <select class="form-control" id="id_kategori" name="id_kategori" required>
                <option value="">Pilih Kategori</option>
                <?php foreach ($kategori_data as $kategori): ?>
                  <option value="<?php echo $kategori['id_kategori']; ?>"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="abstrak">Abstrak</label>
              <textarea class="form-control" id="abstrak" name="abstrak" rows="4" required></textarea>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label" for="id_jurusan">Jurusan</label>
                  <select class="form-control" id="id_jurusan" name="id_jurusan">
                    <option value="">Pilih Jurusan</option>
                    <?php foreach ($jurusan_data as $jurusan): ?>
                      <option value="<?php echo $jurusan['id_jurusan']; ?>"><?php echo htmlspecialchars($jurusan['nama_jurusan']); ?></option>
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
                      <option value="<?php echo $prodi['id_prodi']; ?>"><?php echo htmlspecialchars($prodi['nama_prodi']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="kata_kunci">Kata Kunci</label>
              <input type="text" class="form-control" id="kata_kunci" name="kata_kunci" placeholder="Pisahkan dengan koma">
            </div>
            
            <div class="upload-area" onclick="document.getElementById('dokumen_file').click()">
              <i class="bi bi-cloud-upload"></i>
              <p class="upload-text">Klik untuk memilih file atau drag and drop</p>
              <p class="upload-subtext">Maksimal ukuran file: 10MB (PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX)</p>
            </div>
            
            <input type="file" id="dokumen_file" name="dokumen_file" class="file-input" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" onchange="handleFileSelect(this)">
            
            <div id="fileInfo" class="file-info">
              <div class="file-name" id="fileName"></div>
              <div class="file-size" id="fileSize"></div>
              <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%" id="uploadProgress"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">Batal</button>
          <button type="submit" form="uploadForm" class="btn btn-primary">Unggah Dokumen</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Profil -->
  <div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Profil Mahasiswa</h5>
          <button type="button" class="modal-close" onclick="closeModal('profileModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div id="profileView">
            <div class="profile-header">
              <div class="profile-avatar-container">
                <div id="modalAvatarContainer">
                  <?php 
                  if (hasProfilePhoto($user_id)) {
                      echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" class="profile-avatar" id="profileAvatarImg">';
                  } else {
                      echo getInitialsHtml($user_data['username'], 'large');
                  }
                  ?>
                </div>
                <div class="profile-avatar-edit" onclick="openPhotoUpload()">
                  <i class="bi bi-camera"></i>
                </div>
              </div>
              <div class="profile-info">
                <h4><?php echo htmlspecialchars($user_data['username']); ?></h4>
                <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                <p><?php echo getRoleName($user_data['role']); ?></p>
              </div>
            </div>
            
            <div class="profile-stats">
              <div class="profile-stat">
                <div class="profile-stat-value"><?php echo isset($profile_data['uploaded_docs']) ? $profile_data['uploaded_docs'] : 0; ?></div>
                <div class="profile-stat-label">Dokumen Diunggah</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-value"><?php echo isset($profile_data['downloaded_docs']) ? $profile_data['downloaded_docs'] : 0; ?></div>
                <div class="profile-stat-label">Dokumen Diunduh</div>
              </div>
              <div class="profile-stat">
                <div class="profile-stat-value"><?php echo isset($profile_data['monthly_uploads']) ? $profile_data['monthly_uploads'] : 0; ?></div>
                <div class="profile-stat-label">Upload Bulan Ini</div>
              </div>
            </div>
            
            <div class="profile-details">
              <h5>Informasi Pribadi</h5>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Username</span>
                <span class="profile-detail-value"><?php echo htmlspecialchars($user_data['username']); ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Nama Lengkap</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['nama_lengkap']) && !empty($profile_data['nama_lengkap']) ? htmlspecialchars($profile_data['nama_lengkap']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">NIM</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['nim']) && !empty($profile_data['nim']) ? htmlspecialchars($profile_data['nim']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Tanggal Bergabung</span>
                <span class="profile-detail-value">
                  <?php echo isset($profile_data['created_at']) 
                      ? date('d F Y', strtotime($profile_data['created_at'])) 
                      : '15 September 2021'; ?>
                </span>
              </div>
            </div>
          </div>
          
          <div id="photoUploadForm" style="display: none;">
            <div class="photo-upload-container">
              <label class="photo-upload-label">Ubah Foto Profil</label>
              <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                <i class="bi bi-cloud-upload"></i>
                <p class="photo-upload-text">Klik untuk memilih foto atau drag and drop</p>
                <p class="photo-upload-text">Maksimal ukuran file: 2MB (JPG, PNG, GIF)</p>
              </div>
              <input type="file" id="photoInput" class="photo-upload-input" accept="image/*" onchange="previewPhoto(event)">
              <div id="photoPreview" class="photo-preview"></div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" onclick="closePhotoUpload()">Batal</button>
              <button type="button" class="btn btn-primary" onclick="uploadPhoto()">Unggah Foto</button>
            </div>
          </div>
          
          <div id="editProfileForm" class="edit-profile-form">
            <form method="POST" action="">
              <input type="hidden" name="update_profile" value="1">
              
              <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                <small class="text-muted">Username unik untuk identifikasi akun Anda</small>
              </div>
              
              <div class="form-group">
                <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo isset($profile_data['nama_lengkap']) ? htmlspecialchars($profile_data['nama_lengkap']) : ''; ?>">
              </div>
              
              <div class="form-group">
                <label class="form-label" for="nim">NIM</label>
                <input type="text" class="form-control" id="nim" name="nim" value="<?php echo isset($profile_data['nim']) ? htmlspecialchars($profile_data['nim']) : ''; ?>">
              </div>
            </form>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('profileModal')">Tutup</button>
          <button type="button" class="btn btn-primary" id="editProfileBtn" onclick="toggleEditProfile()">Edit Profil</button>
          <button type="submit" class="btn btn-primary" id="saveProfileBtn" form="editProfileForm" style="display: none;">Simpan</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Pengaturan -->
  <div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Pengaturan</h5>
          <button type="button" class="modal-close" onclick="closeModal('settingsModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="settings-tabs">
            <div class="settings-tab active" onclick="switchSettingsTab('general')">Umum</div>
            <div class="settings-tab" onclick="switchSettingsTab('notifications')">Notifikasi</div>
            <div class="settings-tab" onclick="switchSettingsTab('account')">Akun</div>
          </div>
          
          <div id="general-settings" class="settings-tab-content active">
            <div class="settings-group">
              <div class="settings-group-title">Tampilan</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Bahasa</div>
                  <div class="settings-item-description">Pilih bahasa yang Anda inginkan</div>
                </div>
                <select class="settings-select">
                  <option value="id" selected>Bahasa Indonesia</option>
                  <option value="en">English</option>
                </select>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Preferensi</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Halaman Beranda</div>
                  <div class="settings-item-description">Pilih halaman yang akan ditampilkan saat membuka aplikasi</div>
                </div>
                <select class="settings-select">
                  <option value="dashboard" selected>Dashboard</option>
                  <option value="browser">Browser Dokumen</option>
                  <option value="upload">Upload Dokumen</option>
                </select>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Jumlah Dokumen per Halaman</div>
                  <div class="settings-item-description">Atur jumlah dokumen yang ditampilkan per halaman</div>
                </div>
                <select class="settings-select">
                  <option value="10" selected>10</option>
                  <option value="20">20</option>
                  <option value="50">50</option>
                </select>
              </div>
            </div>
          </div>
          
          <div id="notifications-settings" class="settings-tab-content">
            <div class="settings-group">
              <div class="settings-group-title">Notifikasi Email</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Dokumen Baru</div>
                  <div class="settings-item-description">Terima notifikasi saat ada dokumen baru diunggah</div>
                </div>
                <div class="toggle-switch active" id="newDocToggle" onclick="toggleSwitch('newDocToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Pembaruan Sistem</div>
                  <div class="settings-item-description">Terima notifikasi tentang pembaruan sistem</div>
                </div>
                <div class="toggle-switch active" id="updateToggle" onclick="toggleSwitch('updateToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Aktivitas Akun</div>
                  <div class="settings-item-description">Terima notifikasi tentang aktivitas akun Anda</div>
                </div>
                <div class="toggle-switch" id="activityToggle" onclick="toggleSwitch('activityToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Notifikasi Browser</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Notifikasi Desktop</div>
                  <div class="settings-item-description">Tampilkan notifikasi desktop saat browser terbuka</div>
                </div>
                <div class="toggle-switch" id="desktopToggle" onclick="toggleSwitch('desktopToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Suara Notifikasi</div>
                  <div class="settings-item-description">Mainkan suara saat ada notifikasi baru</div>
                </div>
                <div class="toggle-switch active" id="soundToggle" onclick="toggleSwitch('soundToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
          </div>
          
          <div id="account-settings" class="settings-tab-content">
            <div class="settings-group">
              <div class="settings-group-title">Informasi Akun</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Username</div>
                  <div class="settings-item-description">Username unik untuk akun Anda</div>
                </div>
                <input type="text" class="settings-input" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Email</div>
                  <div class="settings-item-description">Email terkait dengan akun Anda</div>
                </div>
                <input type="email" class="settings-input" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Keamanan</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Ubah Kata Sandi</div>
                  <div class="settings-item-description">Perbarui kata sandi akun Anda secara berkala</div>
                </div>
                <button class="btn btn-primary" onclick="togglePasswordForm()">Ubah</button>
              </div>
            </div>
            
            <div id="passwordForm" class="password-form">
              <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                  <label class="form-label" for="current_password">Password Saat Ini</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="new_password">Password Baru</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
              </form>
              
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="togglePasswordForm()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Password</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Bantuan -->
  <div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Bantuan Mahasiswa</h5>
          <button type="button" class="modal-close" onclick="closeModal('helpModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="accordion" id="helpAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                  Cara Mengunggah Dokumen
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Klik menu "Unggah Dokumen" di navigasi atas</li>
                    <li>Isi form yang tersedia dengan informasi dokumen</li>
                    <li>Pilih file dokumen yang akan diunggah</li>
                    <li>Klik tombol "Unggah" untuk mengunggah dokumen</li>
                    <li>Tunggu hingga proses unggah selesai</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Cara Mencari Dokumen
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Gunakan kotak pencarian di halaman beranda</li>
                    <li>Masukkan kata kunci terkait dokumen yang dicari</li>
                    <li>Gunakan filter untuk mempersempit hasil pencarian</li>
                    <li>Klik dokumen yang diinginkan untuk melihat detailnya</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  Cara Mengunduh Dokumen
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <ol>
                    <li>Buka halaman detail dokumen</li>
                    <li>Klik tombol "Unduh" yang tersedia</li>
                    <li>Tunggu hingga file terunduh ke perangkat Anda</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                  Format Dokumen yang Didukung
                </button>
              </h2>
              <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Sistem kami mendukung berbagai format dokumen, antara lain:</p>
                  <ul>
                    <li>PDF (.pdf)</li>
                    <li>Microsoft Word (.doc, .docx)</li>
                    <li>Microsoft PowerPoint (.ppt, .pptx)</li>
                    <li>Microsoft Excel (.xls, .xlsx)</li>
                    <li>Format gambar (.jpg, .jpeg, .png)</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="notification" class="notification">
    <div class="notification-header">
      <div class="notification-title" id="notificationTitle">Notifikasi</div>
      <button class="notification-close" onclick="hideNotification()">&times;</button>
    </div>
    <div class="notification-body" id="notificationBody">
      Pesan notifikasi
    </div>
  </div>

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

    function openUploadModal() {
      const modal = document.getElementById('uploadModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
    }

    function openProfileModal() {
      const modal = document.getElementById('profileModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
      
      document.getElementById('profileView').style.display = 'block';
      document.getElementById('photoUploadForm').style.display = 'none';
      document.getElementById('editProfileForm').classList.remove('active');
      document.getElementById('editProfileBtn').style.display = 'inline-block';
      document.getElementById('saveProfileBtn').style.display = 'none';
    }

    function openSettingsModal() {
      const modal = document.getElementById('settingsModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
    }

    function openHelpModal() {
      const modal = document.getElementById('helpModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    function handleFileSelect(input) {
      const file = input.files[0];
      if (file) {
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        
        fileInfo.classList.add('active');
      }
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function openPhotoUpload() {
      document.getElementById('profileView').style.display = 'none';
      document.getElementById('photoUploadForm').style.display = 'block';
      document.getElementById('editProfileForm').classList.remove('active');
      document.getElementById('editProfileBtn').style.display = 'none';
      document.getElementById('saveProfileBtn').style.display = 'none';
    }

    function closePhotoUpload() {
      document.getElementById('profileView').style.display = 'block';
      document.getElementById('photoUploadForm').style.display = 'none';
      document.getElementById('editProfileBtn').style.display = 'inline-block';
      document.getElementById('photoPreview').innerHTML = '';
      document.getElementById('photoInput').value = '';
    }

    function previewPhoto(event) {
      const file = event.target.files[0];
      const preview = document.getElementById('photoPreview');
      
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(file);
      }
    }

    function uploadPhoto() {
      const fileInput = document.getElementById('photoInput');
      const file = fileInput.files[0];
      
      if (!file) {
        showNotification('error', 'Error', 'Silakan pilih foto terlebih dahulu');
        return;
      }
      
      if (file.size > 2097152) {
        showNotification('error', 'Error', 'Ukuran file maksimal 2MB');
        return;
      }
      
      const formData = new FormData();
      formData.append('profile_photo', file);
      formData.append('upload_photo', '1');
      
      fetch('home.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        // Refresh profile images
        refreshProfileImages();
        closePhotoUpload();
        showNotification('success', 'Berhasil', 'Foto profil berhasil diperbarui');
      })
      .catch(error => {
        showNotification('error', 'Error', 'Terjadi kesalahan saat mengunggah foto');
      });
    }

    function refreshProfileImages() {
      const timestamp = new Date().getTime();
      const newImageUrl = `uploads/profile_photos/<?php echo $user_id; ?>.jpg?t=${timestamp}`;
      
      const userAvatar = document.getElementById('userAvatar');
      if (userAvatar) {
          userAvatar.src = newImageUrl;
      }
      
      const profileAvatarImg = document.getElementById('profileAvatarImg');
      if (profileAvatarImg) {
          profileAvatarImg.src = newImageUrl;
      }
      
      const dropdownAvatar = document.querySelector('.user-dropdown-header img');
      if (dropdownAvatar) {
          dropdownAvatar.src = newImageUrl;
      }
      
      const headerAvatar = document.querySelector('.header img');
      if (headerAvatar) {
          headerAvatar.src = newImageUrl;
      }
    }

    function toggleEditProfile() {
      const profileView = document.getElementById('profileView');
      const editProfileForm = document.getElementById('editProfileForm');
      const editProfileBtn = document.getElementById('editProfileBtn');
      const saveProfileBtn = document.getElementById('saveProfileBtn');
      
      if (editProfileForm.classList.contains('active')) {
        profileView.style.display = 'block';
        editProfileForm.classList.remove('active');
        editProfileBtn.style.display = 'inline-block';
        saveProfileBtn.style.display = 'none';
      } else {
        profileView.style.display = 'none';
        editProfileForm.classList.add('active');
        editProfileBtn.style.display = 'none';
        saveProfileBtn.style.display = 'inline-block';
      }
    }

    function togglePasswordForm() {
      const passwordForm = document.getElementById('passwordForm');
      passwordForm.classList.toggle('active');
    }

    function switchSettingsTab(tabName) {
      document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.settings-tab-content').forEach(content => {
        content.classList.remove('active');
      });
      
      event.target.classList.add('active');
      document.getElementById(tabName + '-settings').classList.add('active');
    }

    function toggleSwitch(switchId) {
      const toggleSwitch = document.getElementById(switchId);
      toggleSwitch.classList.toggle('active');
    }

    function saveSettings() {
      showNotification('success', 'Pengaturan Disimpan', 'Pengaturan Anda telah berhasil disimpan.');
      closeModal('settingsModal');
    }

    function showNotification(type, title, message) {
      const notification = document.getElementById('notification');
      const notificationTitle = document.getElementById('notificationTitle');
      const notificationBody = document.getElementById('notificationBody');
      
      notification.className = `notification ${type}`;
      
      notificationTitle.textContent = title;
      notificationBody.textContent = message;
      
      notification.style.display = 'block';
      
      setTimeout(() => {
        hideNotification();
      }, 5000);
    }

    function hideNotification() {
      const notification = document.getElementById('notification');
      notification.style.display = 'none';
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

    function viewDocument(docId) {
      window.location.href = 'view_document.php?id=' + docId;
    }

    function downloadDocument(docId) {
      window.location.href = 'home.php?download=' + docId;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const documentCards = document.querySelectorAll('.document-card');
      
      documentCards.forEach(card => {
        const title = card.querySelector('h6').textContent.toLowerCase();
        const description = card.querySelector('p').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });

    document.querySelectorAll('.btn-download').forEach(button => {
      button.addEventListener('click', function() {
        const documentTitle = this.closest('.document-card').querySelector('h6').textContent;
        showNotification('success', 'Download Berhasil', `Dokumen "${documentTitle}" telah diunduh.`);
      });
    });

    // Form submission with progress bar
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const progressBar = document.getElementById('uploadProgress');
      
      // Simulate upload progress
      let progress = 0;
      const interval = setInterval(() => {
        progress += 5;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
          clearInterval(interval);
          
          // Submit the form when progress is complete
          fetch('home.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            closeModal('uploadModal');
            showNotification('success', 'Upload Berhasil', 'Dokumen Anda telah berhasil diunggah dan akan melalui proses review.');
            
            // Reset form
            document.getElementById('uploadForm').reset();
            document.getElementById('fileInfo').classList.remove('active');
            
            // Refresh page after a short delay
            setTimeout(() => {
              window.location.reload();
            }, 2000);
          })
          .catch(error => {
            showNotification('error', 'Error', 'Terjadi kesalahan saat mengunggah dokumen');
          });
        }
      }, 100);
    });

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    <?php if (isset($upload_success)): ?>
      showNotification('success', 'Upload Berhasil', '<?php echo addslashes($upload_success); ?>');
    <?php endif; ?>

    <?php if (isset($upload_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($upload_error); ?>');
    <?php endif; ?>

    <?php if (isset($download_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($download_error); ?>');
    <?php endif; ?>

    <?php if (isset($profile_updated) && $profile_updated): ?>
      showNotification('success', 'Profil Diperbarui', 'Profil Anda telah berhasil diperbarui.');
    <?php endif; ?>

    <?php if (isset($password_updated) && $password_updated): ?>
      showNotification('success', 'Password Diubah', 'Password Anda telah berhasil diubah.');
    <?php endif; ?>

    <?php if (isset($photo_updated) && $photo_updated): ?>
      showNotification('success', 'Foto Profil Diperbarui', 'Foto profil Anda telah berhasil diperbarui.');
    <?php endif; ?>

    <?php if (isset($profile_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($profile_error); ?>');
    <?php endif; ?>

    <?php if (isset($password_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($password_error); ?>');
    <?php endif; ?>
  </script>
</body>
</html>
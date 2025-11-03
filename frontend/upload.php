<?php
session_start();
require_once __DIR__ . '/config/db.php';

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
    $stmt = $pdo->prepare("SELECT id_user, username, email, role_id FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
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

// Fungsi untuk menghasilkan warna background berdasarkan username
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

// Fungsi untuk menentukan warna teks (putih atau hitam) berdasarkan warna background
function getContrastColor($hexColor) {
    $r = hexdec(substr($hexColor, 1, 2));
    $g = hexdec(substr($hexColor, 3, 2));
    $b = hexdec(substr($hexColor, 5, 2));
    
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#000000' : '#FFFFFF';
}

// Fungsi untuk memeriksa apakah pengguna memiliki foto profil
function hasProfilePhoto($user_id) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    return file_exists($photo_path);
}

// Fungsi untuk mendapatkan URL foto profil
function getProfilePhotoUrl($user_id, $email, $username) {
    $photo_path = __DIR__ . '/uploads/profile_photos/' . $user_id . '.jpg';
    if (file_exists($photo_path)) {
        return 'uploads/profile_photos/' . $user_id . '.jpg?t=' . time();
    } else {
        return 'profile_image.php?id=' . $user_id . '&email=' . urlencode($email) . '&name=' . urlencode($username) . '&t=' . time();
    }
}

// Fungsi untuk mendapatkan inisial dengan desain yang bagus
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

function getRoleName($role_id) {
    switch($role_id) {
        case 1: return 'Admin';
        case 2: return 'Mahasiswa';
        case 3: return 'Dosen';
        default: return 'Pengguna';
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user) AS uploaded_docs,
            (SELECT COUNT(*) FROM download_history WHERE user_id = u.id_user) AS downloaded_docs,
            (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE)) AS monthly_uploads
        FROM users u
        WHERE u.id_user = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $profile_data = [];
}

// Get notifications
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute(['user_id' => $user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count unread notifications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM notifications 
        WHERE user_id = :user_id AND is_read = 0
    ");
    $stmt->execute(['user_id' => $user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $notifications = [];
    $unread_count = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $nama_lengkap = isset($_POST['nama_lengkap']) ? $_POST['nama_lengkap'] : '';
        $nomor_induk = isset($_POST['nomor_induk']) ? $_POST['nomor_induk'] : '';
        $program_studi = isset($_POST['program_studi']) ? $_POST['program_studi'] : '';
        $semester = isset($_POST['semester']) ? $_POST['semester'] : '';
        
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = :username AND id_user != :id");
            $stmt->execute(['username' => $username, 'id' => $user_id]);
            if ($stmt->fetch()) {
                $profile_error = "Username sudah digunakan oleh pengguna lain";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = :username, 
                        nama_lengkap = :nama_lengkap, 
                        nomor_induk = :nomor_induk, 
                        program_studi = :program_studi, 
                        semester = :semester
                    WHERE id_user = :id
                ");
                
                $stmt->execute([
                    'username' => $username,
                    'nama_lengkap' => $nama_lengkap,
                    'nomor_induk' => $nomor_induk,
                    'program_studi' => $program_studi,
                    'semester' => $semester,
                    'id' => $user_id
                ]);
                
                $_SESSION['username'] = $username;
                
                $stmt = $pdo->prepare("SELECT id_user, username, email, role_id FROM users WHERE id_user = :id LIMIT 1");
                $stmt->execute(['id' => $user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("
                    SELECT 
                        u.*,
                        (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user) AS uploaded_docs,
                        (SELECT COUNT(*) FROM download_history WHERE user_id = u.id_user) AS downloaded_docs,
                        (SELECT COUNT(*) FROM dokumen WHERE uploader_id = u.id_user AND MONTH(tgl_unggah) = MONTH(CURRENT_DATE) AND YEAR(tgl_unggah) = YEAR(CURRENT_DATE)) AS monthly_uploads
                    FROM users u
                    WHERE u.id_user = :id
                    LIMIT 1
                ");
                $stmt->execute(['id' => $user_id]);
                $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $profile_updated = true;
            }
        } else {
            $profile_error = "Username tidak boleh kosong";
        }
    } catch (PDOException $e) {
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
                // Create directory if it doesn't exist
                $uploadDir = __DIR__ . '/uploads/profile_photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Convert to JPG and save
                $targetPath = $uploadDir . $user_id . '.jpg';
                
                if ($fileExtension === 'jpg' || $fileExtension === 'jpeg') {
                    // Move the file directly
                    move_uploaded_file($fileTmpName, $targetPath);
                } else {
                    // Convert PNG/GIF to JPG
                    if ($fileExtension === 'png') {
                        $image = imagecreatefrompng($fileTmpName);
                    } else {
                        $image = imagecreatefromgif($fileTmpName);
                    }
                    
                    // Create a white background
                    $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    
                    // Save as JPG
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
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id_user = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id_user = :id");
                $stmt->execute([
                    'password' => $hashed_password,
                    'id' => $user_id
                ]);
                
                $password_updated = true;
            } else {
                $password_error = "Password baru dan konfirmasi tidak cocok";
            }
        } else {
            $password_error = "Password saat ini salah";
        }
    } catch (PDOException $e) {
        $password_error = "Error updating password: " . $e->getMessage();
    }
}

function handleFileUpload($file, $target_dir = "uploads/documents/") {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if ($file["size"] > 10000000) {
        return ["success" => false, "message" => "File terlalu besar, maksimal 10MB"];
    }
    
    $allowed_types = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        return ["success" => false, "message" => "Tipe file tidak didukung"];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            "success" => true, 
            "file_path" => $target_file, 
            "file_size" => $file["size"],
            "original_name" => $file["name"]
        ];
    } else {
        return ["success" => false, "message" => "Gagal mengunggah file"];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $tipe_dokumen = htmlspecialchars($_POST['tipe_dokumen']);
    $judul = htmlspecialchars($_POST['judul']);
    $abstrak = htmlspecialchars($_POST['abstrak']);
    $id_tema = htmlspecialchars($_POST['id_tema']);
    $id_jurusan = htmlspecialchars($_POST['id_jurusan']);
    $id_prodi = htmlspecialchars($_POST['id_prodi']);
    $year_id = htmlspecialchars($_POST['year_id']);
    $status_id = htmlspecialchars($_POST['status_id']);
    $format_id = htmlspecialchars($_POST['format_id']);
    $policy_id = htmlspecialchars($_POST['policy_id']);
    
    // Get authors and keywords
    $authors = isset($_POST['authors']) ? $_POST['authors'] : [];
    $keywords = isset($_POST['keywords']) ? $_POST['keywords'] : [];
    
    $response = ['success' => false, 'message' => 'Terjadi kesalahan.'];
    
    if (!empty($tipe_dokumen) && !empty($judul) && !empty($id_tema) && !empty($id_jurusan) && !empty($id_prodi) && !empty($year_id) && !empty($status_id) && !empty($format_id) && !empty($policy_id)) {
        if (isset($_FILES['dokumen_file']) && $_FILES['dokumen_file']['error'] == 0) {
            $file_result = handleFileUpload($_FILES['dokumen_file']);
            
            if ($file_result['success']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO dokumen 
    (tipe_dokumen, judul, abstrak, file_path, file_size, uploader_id, id_tema, id_jurusan, id_prodi, year_id, status_id, format_id, policy_id) 
    VALUES 
    (:tipe_dokumen, :judul, :abstrak, :file_path, :file_size, :uploader_id, :id_tema, :id_jurusan, :id_prodi, :year_id, :status_id, :format_id, :policy_id)");

                    $stmt->execute([
                        'tipe_dokumen' => $tipe_dokumen,
                        'judul' => $judul,
                        'abstrak' => $abstrak,
                        'file_path' => $file_result['file_path'],
                        'file_size' => $file_result['file_size'],
                        'uploader_id' => $user_id,
                        'id_tema' => $id_tema,
                        'id_jurusan' => $id_jurusan,
                        'id_prodi' => $id_prodi,
                        'year_id' => $year_id,
                        'status_id' => $status_id,
                        'format_id' => $format_id,
                        'policy_id' => $policy_id
                    ]);

                    $dokumen_id = $pdo->lastInsertId();
                    
                    // Add authors
                    if (!empty($authors)) {
                        foreach ($authors as $author) {
                            if (!empty(trim($author))) {
                                $stmt = $pdo->prepare("INSERT INTO dokumen_authors (dokumen_id, author_name) VALUES (:dokumen_id, :author_name)");
                                $stmt->execute([
                                    'dokumen_id' => $dokumen_id,
                                    'author_name' => trim($author)
                                ]);
                            }
                        }
                    }
                    
                    // Add keywords
                    if (!empty($keywords)) {
                        foreach ($keywords as $keyword) {
                            if (!empty(trim($keyword))) {
                                $stmt = $pdo->prepare("INSERT INTO dokumen_keywords (dokumen_id, keyword) VALUES (:dokumen_id, :keyword)");
                                $stmt->execute([
                                    'dokumen_id' => $dokumen_id,
                                    'keyword' => trim($keyword)
                                ]);
                            }
                        }
                    }
                    
                    // Add notification for admins
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, related_id) 
                        SELECT id_user, 'Dokumen Baru', :message, 'document', :dokumen_id 
                        FROM users WHERE role_id = 1
                    ");
                    $stmt->execute([
                        'message' => "Dokumen '{$judul}' telah diunggah dan menunggu verifikasi.",
                        'dokumen_id' => $dokumen_id
                    ]);
                    
                    // Add notification for uploader
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, related_id) 
                        VALUES (:user_id, 'Upload Berhasil', :message, 'document', :dokumen_id)
                    ");
                    $stmt->execute([
                        'user_id' => $user_id,
                        'message' => "Dokumen '{$judul}' telah berhasil diunggah dan sedang dalam proses verifikasi.",
                        'dokumen_id' => $dokumen_id
                    ]);

                    $response['success'] = true;
                    $response['message'] = 'Dokumen berhasil diunggah!';
                    $response['redirect'] = 'dashboard.php';

                } catch (PDOException $e) {
                    $response['message'] = 'Kesalahan database: ' . $e->getMessage();
                }
            } else {
                $response['message'] = $file_result['message'];
            }
        } else {
            $response['message'] = 'File tidak dipilih atau ada kesalahan saat mengunggah.';
        }
    } else {
        $response['message'] = 'Semua field yang ditandai * harus diisi!';
    }
    
    echo json_encode($response);
    exit();
}

// Mark notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_notification_read'])) {
    $notification_id = $_POST['notification_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $notification_id, 'user_id' => $user_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA | Upload Dokumen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* --- Global Variables & Styles --- */
    :root {
      --primary-blue: #0058e4;
      --primary-light: #e9f0ff;
      --light-blue: #64B5F6; /* Biru muda untuk user initials */
      --background-page: #f5f7fa;
      --white: #ffffff;
      --text-primary: #222222;
      --text-secondary: #666666;
      --text-muted: #555555;
      --border-color: #dcdcdc;
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
      --primary-hover: #0044b3;
      --warning-bg: #fff3cd;
      --warning-border: #ffeeba;
      --warning-text: #856404;
      --light-gray-bg: #f5f7fa;
      --success-bg: #d4edda;
      --success-border: #c3e6cb;
      --success-text: #155724;
      --error-bg: #f8d7da;
      --error-border: #f5c6cb;
      --error-text: #721c24;
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

    /* --- User Initials Styles --- */
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
      /* Default biru muda jika tidak ada warna khusus */
      background-color: var(--light-blue);
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
      /* Default biru muda jika tidak ada warna khusus */
      background-color: var(--light-blue);
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
      /* Default biru muda jika tidak ada warna khusus */
      background-color: var(--light-blue);
      color: white;
    }

    /* --- Subtle Background Animation --- */
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
      background: var(--primary-blue);
      top: -150px;
      right: -100px;
      animation-delay: 0s;
    }

    .bg-circle:nth-child(2) {
      width: 250px;
      height: 250px;
      background: var(--primary-blue);
      bottom: -120px;
      left: -80px;
      animation-delay: 5s;
    }

    .bg-circle:nth-child(3) {
      width: 200px;
      height: 200px;
      background: var(--primary-blue);
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

   /* --- Navigation --- */
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
      color: var(--primary-blue);
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

    /* Mobile menu button */
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-primary);
      cursor: pointer;
    }

    /* --- Notification Icon --- */
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
      color: var(--primary-blue);
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
      color: var(--primary-blue);
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
      color: var(--primary-blue);
      text-decoration: none;
    }

    /* --- User Dropdown --- */
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

    /* --- Modal --- */
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
      background-color: var(--primary-blue);
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
      background-color: #0044b3;
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
      color: var(--primary-blue);
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

    /* --- Badge Styles --- */
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
    .badge-secondary { background: #e9ecef; color: #495057; }

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
      background-color: var(--primary-blue);
      color: var(--white);
    }

    .btn-primary:hover {
      background-color: #0044b3;
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

    /* --- Edit Profile Form --- */
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
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }

    /* --- Photo Upload --- */
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
      border-color: var(--primary-blue);
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

    /* --- Settings Modal --- */
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
      color: var(--primary-blue);
      border-bottom-color: var(--primary-blue);
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
      background-color: var(--primary-blue);
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

    /* --- Password Change Form --- */
    .password-form {
      display: none;
    }

    .password-form.active {
      display: block;
    }

    /* --- Notification --- */
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

    /* Section Header */
    .section-header {
      max-width: 1100px;
      margin: 40px auto 20px;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .section-header h2 {
      font-size: 24px;
      font-weight: 600;
      color: var(--text-primary);
    }

    /* Upload Form Container */
    .upload-container {
      max-width: 1100px;
      margin: 0 auto 40px;
      padding: 40px;
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      margin-bottom: 8px;
      color: var(--text-primary);
    }

    .form-group label .required {
      color: #dc3545;
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 15px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(0, 88, 228, 0.15);
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    /* Dynamic Fields (Authors & Keywords) */
    .dynamic-field-container {
      margin-bottom: 10px;
    }
    
    .input-group {
      display: flex;
      gap: 10px;
      margin-bottom: 10px;
    }

    .input-group .form-control {
      flex-grow: 1;
    }

    .btn-remove {
      padding: 0 12px;
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 18px;
      line-height: 1;
      transition: background-color 0.2s;
    }
    .btn-remove:hover {
      background-color: #c82333;
    }

    .btn-add {
      background: none;
      border: none;
      color: var(--primary-blue);
      font-weight: 500;
      cursor: pointer;
      padding: 5px 0;
      font-size: 15px;
    }
    .btn-add:hover {
      text-decoration: underline;
    }
    .btn-add i {
      margin-right: 5px;
    }

    /* File Upload Area */
    .file-upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 30px;
      text-align: center;
      background-color: #fafafa;
      transition: border-color 0.3s;
    }
    .file-upload-area.dragover {
      border-color: var(--primary-blue);
      background-color: rgba(0, 88, 228, 0.05);
    }

    .file-upload-area input[type="file"] {
      display: none;
    }

    .file-upload-label {
      display: inline-block;
      padding: 10px 20px;
      background-color: #e9ecef;
      color: var(--text-primary);
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background-color 0.3s;
    }
    .file-upload-label:hover {
      background-color: #dee2e6;
    }

    .file-name-display {
      margin-top: 15px;
      font-size: 14px;
      color: var(--text-muted);
    }

    /* Alert Box */
    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .alert-warning {
      background-color: var(--warning-bg);
      border: 1px solid var(--warning-border);
      color: var(--warning-text);
    }

    .alert-success {
      background-color: var(--success-bg);
      border: 1px solid var(--success-border);
      color: var(--success-text);
    }

    .alert-error {
      background-color: var(--error-bg);
      border: 1px solid var(--error-border);
      color: var(--error-text);
    }

    .alert i {
      font-size: 20px;
      margin-top: 2px;
    }

    /* Submit Button */
    .btn-submit {
      width: 100%;
      padding: 14px;
      background-color: var(--primary-blue);
      color: var(--white);
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .btn-submit:hover {
      background-color: var(--primary-hover);
    }

    .btn-submit:disabled {
      background-color: #6c757d;
      cursor: not-allowed;
    }

    /* Progress Bar */
    .progress-container {
      margin-top: 20px;
      display: none;
    }

    .progress-bar {
      height: 6px;
      background-color: #e9ecef;
      border-radius: 3px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background-color: var(--primary-blue);
      width: 0%;
      transition: width 0.3s ease;
    }

    .progress-text {
      display: flex;
      justify-content: space-between;
      margin-top: 8px;
      font-size: 12px;
      color: var(--text-secondary);
    }

    /* --- Footer --- */
    footer {
      text-align: center;
      color: #777;
      font-size: 0.93rem;
      margin-top: 55px;
      padding: 25px 0;
      border-top: 1px solid #ddd;
    }

    /* --- Responsive Design --- */
    @media (max-width: 992px) {
      .upload-container {
        padding: 30px;
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
      
      .upload-container {
        padding: 20px;
      }
      
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
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
      
      .upload-container {
        margin: 0 15px 40px;
        padding: 15px;
      }
      
      .section-header {
        margin: 30px 15px 10px;
        padding: 0;
      }
      
      .section-header h2 {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Subtle Background Animation -->
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
        <a href="dashboard.php">Beranda</a>
        <a href="#" class="active">Upload</a>
        <a href="browser.php">Browser</a>
        <a href="#">Search</a>
        <a href="#">Download</a>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($user_data['username']); ?></span>
        
        <!-- Notification Icon -->
        <div class="notification-icon" id="notificationIcon">
          <i class="bi bi-bell-fill"></i>
          <?php if ($unread_count > 0): ?>
            <span class="notification-badge"><?php echo $unread_count; ?></span>
          <?php endif; ?>
          
          <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
              <h5>Notifikasi</h5>
              <a href="#" onclick="markAllAsRead()">Tandai semua dibaca</a>
            </div>
            <div class="notification-list">
              <?php if (empty($notifications)): ?>
                <div class="notification-item">
                  <div class="notification-content">
                    <div class="notification-text">
                      <div class="notification-title">Tidak Ada Notifikasi</div>
                      <div class="notification-message">Anda tidak memiliki notifikasi saat ini.</div>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                  <div class="notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                    <div class="notification-content">
                      <div class="notification-icon-wrapper <?php echo $notification['type']; ?>">
                        <?php if ($notification['type'] == 'info'): ?>
                          <i class="bi bi-info-circle"></i>
                        <?php elseif ($notification['type'] == 'success'): ?>
                          <i class="bi bi-check-circle"></i>
                        <?php elseif ($notification['type'] == 'warning'): ?>
                          <i class="bi bi-exclamation-triangle"></i>
                        <?php elseif ($notification['type'] == 'error'): ?>
                          <i class="bi bi-x-circle"></i>
                        <?php else: ?>
                          <i class="bi bi-info-circle"></i>
                        <?php endif; ?>
                      </div>
                      <div class="notification-text">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time"><?php echo formatTimeAgo($notification['created_at']); ?></div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="notification-footer">
              <a href="notifications.php">Lihat semua notifikasi</a>
            </div>
          </div>
        </div>
        
        <div id="userAvatarContainer">
          <?php 
          // Check if user has profile photo
          if (hasProfilePhoto($user_id)) {
              echo '<img src="' . getProfilePhotoUrl($user_id, $user_data['email'], $user_data['username']) . '" alt="User Avatar" id="userAvatar">';
          } else {
              echo getInitialsHtml($user_data['username'], 'small');
          }
          ?>
        </div>
        
        <!-- User Dropdown Menu -->
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
              <div class="role"><?php echo getRoleName($user_data['role_id']); ?></div>
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

  <div class="section-header">
    <h2>Upload Dokumen Baru</h2>
  </div>

  <main class="upload-container">
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <div>
          <strong>Sukses:</strong> Dokumen berhasil diunggah dan akan melalui proses verifikasi.
        </div>
      </div>
    <?php endif; ?>

    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div>
        <strong>Perhatian:</strong> Pastikan dokumen yang Anda upload telah memenuhi ketentuan format (PDF, DOC, DOCX) dan tidak melanggar hak cipta. Dokumen akan melalui proses verifikasi oleh admin sebelum dipublikasi.
      </div>
    </div>

    <form id="uploadForm">
      <input type="hidden" name="action" value="upload">
      
      <div class="form-group">
        <label for="tipe_dokumen">Tipe Dokumen <span class="required">*</span></label>
        <select id="tipe_dokumen" name="tipe_dokumen" class="form-control" required>
          <option value="" disabled selected>-- Pilih Tipe Dokumen --</option>
          <option value="book">Buku</option>
          <option value="journal">Jurnal</option>
          <option value="thesis">Tesis</option>
          <option value="final_project">Tugas Akhir</option>
          <option value="research">Penelitian</option>
          <option value="ebook">E-Book</option>
          <option value="other">Lainnya</option>
        </select>
      </div>

      <div class="form-group">
        <label for="judul">Judul Dokumen <span class="required">*</span></label>
        <input type="text" id="judul" name="judul" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="abstrak">Abstrak <span class="required">*</span></label>
        <textarea id="abstrak" name="abstrak" class="form-control" required></textarea>
      </div>
      
      <div class="form-group">
        <label for="id_tema">Tema/Topik <span class="required">*</span></label>
        <select id="id_tema" name="id_tema" class="form-control" required>
          <option value="" disabled selected>-- Pilih Tema --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT id_tema, nama_tema FROM master_tema ORDER BY nama_tema");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['id_tema'] . '">' . htmlspecialchars($row['nama_tema']) . '</option>';
            }
          } catch (PDOException $e) {
            echo '<option value="">Error memuat tema</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="id_jurusan">Jurusan <span class="required">*</span></label>
        <select id="id_jurusan" name="id_jurusan" class="form-control" required>
          <option value="" disabled selected>-- Pilih Jurusan --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['id_jurusan'] . '">' . htmlspecialchars($row['nama_jurusan']) . '</option>';
            }
          } catch (PDOException $e) {
            echo '<option value="">Error memuat jurusan</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="id_prodi">Program Studi <span class="required">*</span></label>
        <select id="id_prodi" name="id_prodi" class="form-control" required>
          <option value="" disabled selected>-- Pilih Program Studi --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['id_prodi'] . '">' . htmlspecialchars($row['nama_prodi']) . '</option>';
            }
          } catch (PDOException $e) {
            echo '<option value="">Error memuat program studi</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="year_id">Tahun <span class="required">*</span></label>
        <select id="year_id" name="year_id" class="form-control" required>
          <option value="" disabled selected>-- Pilih Tahun --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT year_id, tahun FROM master_tahun ORDER BY tahun DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['year_id'] . '">' . htmlspecialchars($row['tahun']) . '</option>';
            }
          } catch (PDOException $e) {
            for($i = date('Y'); $i >= 2020; $i--) {
              echo '<option value="' . $i . '">' . $i . '</option>';
            }
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="status_id">Status <span class="required">*</span></label>
        <select id="status_id" name="status_id" class="form-control" required>
          <option value="" disabled selected>-- Pilih Status --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT status_id, nama_status FROM master_status_dokumen ORDER BY nama_status");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['status_id'] . '">' . htmlspecialchars($row['nama_status']) . '</option>';
            }
          } catch (PDOException $e) {
            echo '<option value="1">Aktif</option>';
            echo '<option value="2">Tidak Aktif</option>';
            echo '<option value="3">Menunggu</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="format_id">Format <span class="required">*</span></label>
        <select id="format_id" name="format_id" class="form-control" required>
          <option value="" disabled selected>-- Pilih Format --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT format_id, nama_format FROM master_dokumen ORDER BY nama_format");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['format_id'] . '">' . htmlspecialchars($row['nama_format']) . '</option>';
            }
          } catch (PDOException $e) {
            echo '<option value="1">PDF</option>';
            echo '<option value="2">DOC/DOCX</option>';
            echo '<option value="3">PPT/PPTX</option>';
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="policy_id">Kebijakan <span class="required">*</span></label>
        <select id="policy_id" name="policy_id" class="form-control" required>
          <option value="" disabled selected>-- Pilih Kebijakan --</option>
          <?php
          try {
            $stmt = $pdo->query("SELECT policy_id, nama_policy FROM master_policy ORDER BY nama_policy");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo '<option value="' . $row['policy_id'] . '">' . htmlspecialchars($row['nama_policy']) . '</option>';
            }
          } catch (PDOException $e) {
            echo '<option value="1">Akses Terbuka</option>';
            echo '<option value="2">Akses Terbatas</option>';
          }
          ?>
        </select>
      </div>

      <div class="form-group">
        <label>Penulis <span class="required">*</span></label>
        <div id="authors-container" class="dynamic-field-container">
          <div class="input-group">
            <input type="text" class="form-control" name="authors[]" placeholder="Nama penulis" required>
            <button type="button" class="btn-remove" style="display: none;">&times;</button>
          </div>
        </div>
        <button type="button" class="btn-add" id="add-author-btn"><i class="bi bi-plus-circle"></i> Tambah penulis</button>
      </div>

      <div class="form-group">
        <label>Kata Kunci <span class="required">*</span></label>
        <div id="keywords-container" class="dynamic-field-container">
          <div class="input-group">
            <input type="text" class="form-control" name="keywords[]" placeholder="Masukkan kata kunci" required>
            <button type="button" class="btn-remove" style="display: none;">&times;</button>
          </div>
        </div>
        <button type="button" class="btn-add" id="add-keyword-btn"><i class="bi bi-plus-circle"></i> Tambah kata kunci</button>
      </div>

      <div class="form-group">
        <label>Upload File <span class="required">*</span></label>
        <div class="file-upload-area" id="file-upload-area">
          <i class="bi bi-cloud-upload" style="font-size: 48px; color: var(--text-muted);"></i>
          <p style="margin: 15px 0 5px; color: var(--text-primary);">Drag and drop file Anda di sini atau</p>
          <label for="dokumen_file" class="file-upload-label">Pilih File</label>
          <input type="file" id="dokumen_file" name="dokumen_file" accept=".pdf,.doc,.docx" required>
          <div class="file-name-display" id="file-name-display">Belum ada file yang dipilih.</div>
        </div>
      </div>

      <div class="progress-container" id="progressContainer">
        <div class="progress-bar">
          <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="progress-text">
          <span id="progressPercent">0%</span>
          <span id="progressStatus">Mengunggah...</span>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">Upload Dokumen</button>
    </form>
  </main>

  <!-- Profile Modal -->
  <div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Profil Pengguna</h5>
          <button type="button" class="modal-close" onclick="closeModal('profileModal')">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Profile View -->
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
                <p><?php echo getRoleName($user_data['role_id']); ?></p>
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
                <span class="profile-detail-value"><?php echo isset($profile_data['nomor_induk']) && !empty($profile_data['nomor_induk']) ? htmlspecialchars($profile_data['nomor_induk']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Program Studi</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['program_studi']) && !empty($profile_data['program_studi']) ? htmlspecialchars($profile_data['program_studi']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Semester</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['semester']) && !empty($profile_data['semester']) ? htmlspecialchars($profile_data['semester']) : '<span class="badge bg-secondary">Belum diisi</span>'; ?></span>
              </div>
              <div class="profile-detail-item">
                <span class="profile-detail-label">Tanggal Bergabung</span>
                <span class="profile-detail-value"><?php echo isset($profile_data['created_at']) ? date('d F Y', strtotime($profile_data['created_at'])) : '15 September 2021'; ?></span>
              </div>
            </div>
          </div>
          
          <!-- Photo Upload Form -->
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
          
          <!-- Edit Profile Form -->
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
                <label class="form-label" for="nomor_induk">NIM</label>
                <input type="text" class="form-control" id="nomor_induk" name="nomor_induk" value="<?php echo isset($profile_data['nomor_induk']) ? htmlspecialchars($profile_data['nomor_induk']) : ''; ?>">
              </div>
              
              <div class="form-group">
                <label class="form-label" for="program_studi">Program Studi</label>
                <input type="text" class="form-control" id="program_studi" name="program_studi" value="<?php echo isset($profile_data['program_studi']) ? htmlspecialchars($profile_data['program_studi']) : ''; ?>">
              </div>
              
              <div class="form-group">
                <label class="form-label" for="semester">Semester</label>
                <select class="form-control" id="semester" name="semester">
                  <option value="">Pilih Semester</option>
                  <option value="1" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '1') ? 'selected' : ''; ?>>1 (Ganjil)</option>
                  <option value="2" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '2') ? 'selected' : ''; ?>>2 (Genap)</option>
                  <option value="3" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '3') ? 'selected' : ''; ?>>3 (Ganjil)</option>
                  <option value="4" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '4') ? 'selected' : ''; ?>>4 (Genap)</option>
                  <option value="5" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '5') ? 'selected' : ''; ?>>5 (Ganjil)</option>
                  <option value="6" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '6') ? 'selected' : ''; ?>>6 (Genap)</option>
                  <option value="7" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '7') ? 'selected' : ''; ?>>7 (Ganjil)</option>
                  <option value="8" <?php echo (isset($profile_data['semester']) && $profile_data['semester'] == '8') ? 'selected' : ''; ?>>8 (Genap)</option>
                </select>
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

  <!-- Settings Modal -->
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
            <div class="settings-tab" onclick="switchSettingsTab('privacy')">Privasi</div>
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
          
          <div id="privacy-settings" class="settings-tab-content">
            <div class="settings-group">
              <div class="settings-group-title">Profil Publik</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Tampilkan Profil Publik</div>
                  <div class="settings-item-description">Izinkan pengguna lain melihat profil Anda</div>
                </div>
                <div class="toggle-switch active" id="publicProfileToggle" onclick="toggleSwitch('publicProfileToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Tampilkan Dokumen Saya</div>
                  <div class="settings-item-description">Izinkan pengguna lain melihat dokumen yang Anda unggah</div>
                </div>
                <div class="toggle-switch active" id="publicDocsToggle" onclick="toggleSwitch('publicDocsToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Data Pribadi</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Bagikan Data Analitik</div>
                  <div class="settings-item-description">Bantu kami meningkatkan layanan dengan berbagi data penggunaan anonim</div>
                </div>
                <div class="toggle-switch" id="analyticsToggle" onclick="toggleSwitch('analyticsToggle')">
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
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Autentikasi Dua Faktor</div>
                  <div class="settings-item-description">Tambahkan lapisan keamanan ekstra ke akun Anda</div>
                </div>
                <div class="toggle-switch" id="twoFactorToggle" onclick="toggleSwitch('twoFactorToggle')">
                  <div class="toggle-switch-slider"></div>
                </div>
              </div>
            </div>
            
            <!-- Password Change Form -->
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
                
                <div class="d-flex justify-content-end gap-2">
                  <button type="button" class="btn btn-secondary" onclick="togglePasswordForm()">Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan Password</button>
                </div>
              </form>
            </div>
            
            <div class="settings-group">
              <div class="settings-group-title">Bahaya</div>
              <div class="settings-item">
                <div>
                  <div class="settings-item-label">Hapus Akun</div>
                  <div class="settings-item-description">Hapus permanen akun dan semua data terkait</div>
                </div>
                <button class="btn btn-danger" onclick="deleteAccount()">Hapus</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('settingsModal')">Batal</button>
          <button type="button" class="btn btn-primary" onclick="saveSettings()">Simpan Pengaturan</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Help Modal -->
  <div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Bantuan</h5>
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
                    <li>Isi semua field yang diperlukan pada form upload</li>
                    <li>Pilih file dokumen yang akan diunggah</li>
                    <li>Klik tombol "Upload Dokumen"</li>
                    <li>Tunggu hingga proses unggah selesai</li>
                  </ol>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Format Dokumen yang Didukung
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Sistem kami mendukung berbagai format dokumen, antara lain:</p>
                  <ul>
                    <li>PDF (.pdf)</li>
                    <li>Microsoft Word (.doc, .docx)</li>
                    <li>Microsoft PowerPoint (.ppt, .pptx)</li>
                    <li>Format gambar (.jpg, .jpeg, .png)</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  Ukuran File Maksimal
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                <div class="accordion-body">
                  <p>Ukuran file maksimal yang dapat diunggah adalah 10MB. Jika file Anda lebih besar dari itu, pertimbangkan untuk mengompres file atau membaginya menjadi beberapa bagian.</p>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <h6>Butuh bantuan lebih lanjut?</h6>
            <p>Hubungi tim dukungan kami melalui:</p>
            <ul>
              <li>Email: support@sipora.polije.ac.id</li>
              <li>Telepon: (0331) 123456</li>
              <li>WhatsApp: +62 812-3456-7890</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('helpModal')">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification -->
  <div id="notification" class="notification">
    <div class="notification-header">
      <div class="notification-title" id="notificationTitle">Notifikasi</div>
      <button class="notification-close" onclick="hideNotification()">&times;</button>
    </div>
    <div class="notification-body" id="notificationBody">
      Pesan notifikasi
    </div>
  </div>

  <!-- Footer -->
  <footer>© 2025 SIPORA - Sistem Informasi Portal Repository Akademik POLITEKNIK NEGERI JEMBER</footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('active');
    });

    // User dropdown toggle
    document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('userDropdown').classList.toggle('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    // Notification dropdown toggle
    document.getElementById('notificationIcon').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('notificationDropdown').classList.toggle('active');
      document.getElementById('userDropdown').classList.remove('active');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
      document.getElementById('userDropdown').classList.remove('active');
      document.getElementById('notificationDropdown').classList.remove('active');
    });

    // Prevent dropdowns from closing when clicking inside them
    document.getElementById('userDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    document.getElementById('notificationDropdown').addEventListener('click', function(e) {
      e.stopPropagation();
    });

    // Profile Modal Functions
    function openProfileModal() {
      const modal = document.getElementById('profileModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
      
      // Reset to view mode
      document.getElementById('profileView').style.display = 'block';
      document.getElementById('photoUploadForm').style.display = 'none';
      document.getElementById('editProfileForm').classList.remove('active');
      document.getElementById('editProfileBtn').style.display = 'inline-block';
      document.getElementById('saveProfileBtn').style.display = 'none';
    }

    // Settings Modal Functions
    function openSettingsModal() {
      const modal = document.getElementById('settingsModal');
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      document.getElementById('userDropdown').classList.remove('active');
    }

    // Help Modal Functions
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

    // Photo Upload Functions
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
      
      // Validate file size (2MB max)
      if (file.size > 2097152) {
        showNotification('error', 'Error', 'Ukuran file maksimal 2MB');
        return;
      }
      
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(file.type)) {
        showNotification('error', 'Error', 'Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan');
        return;
      }
      
      // Create form data
      const formData = new FormData();
      formData.append('profile_photo', file);
      formData.append('upload_photo', '1');
      
      // Show loading notification
      showNotification('info', 'Mengunggah', 'Sedang mengunggah foto...');
      
      // Send AJAX request
      fetch('upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        // Refresh images with cache busting
        refreshProfileImages();
        
        // Show success notification
        showNotification('success', 'Foto Profil Diperbarui', 'Foto profil Anda telah berhasil diperbarui.');
        
        // Close photo upload form
        closePhotoUpload();
      })
      .catch(error => {
        showNotification('error', 'Error', 'Gagal mengupload foto');
      });
    }

    // Function to refresh profile images with cache busting
    function refreshProfileImages() {
      const timestamp = new Date().getTime();
      const newImageUrl = `uploads/profile_photos/<?php echo $user_id; ?>.jpg?t=${timestamp}`;
      
      // Update all profile images on the page
      const userAvatar = document.getElementById('userAvatar');
      if (userAvatar) {
        userAvatar.src = newImageUrl;
      }
      
      const profileAvatarImg = document.getElementById('profileAvatarImg');
      if (profileAvatarImg) {
        profileAvatarImg.src = newImageUrl;
      }
      
      // Update dropdown avatar
      const dropdownAvatar = document.querySelector('.user-dropdown-header img');
      if (dropdownAvatar) {
        dropdownAvatar.src = newImageUrl;
      }
    }

    // Toggle Edit Profile Form
    function toggleEditProfile() {
      const profileView = document.getElementById('profileView');
      const editProfileForm = document.getElementById('editProfileForm');
      const editProfileBtn = document.getElementById('editProfileBtn');
      const saveProfileBtn = document.getElementById('saveProfileBtn');
      
      if (editProfileForm.classList.contains('active')) {
        // Switch to view mode
        profileView.style.display = 'block';
        editProfileForm.classList.remove('active');
        editProfileBtn.style.display = 'inline-block';
        saveProfileBtn.style.display = 'none';
      } else {
        // Switch to edit mode
        profileView.style.display = 'none';
        editProfileForm.classList.add('active');
        editProfileBtn.style.display = 'none';
        saveProfileBtn.style.display = 'inline-block';
      }
    }

    // Toggle Password Form
    function togglePasswordForm() {
      const passwordForm = document.getElementById('passwordForm');
      passwordForm.classList.toggle('active');
    }

    // Settings Tab Functions
    function switchSettingsTab(tabName) {
      // Remove active class from all tabs and contents
      document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.settings-tab-content').forEach(content => {
        content.classList.remove('active');
      });
      
      // Add active class to selected tab and content
      event.target.classList.add('active');
      document.getElementById(tabName + '-settings').classList.add('active');
    }

    // Toggle Switch Function
    function toggleSwitch(switchId) {
      const toggleSwitch = document.getElementById(switchId);
      toggleSwitch.classList.toggle('active');
    }

    // Save Settings Function
    function saveSettings() {
      showNotification('success', 'Pengaturan Disimpan', 'Pengaturan Anda telah berhasil disimpan.');
      closeModal('settingsModal');
    }

    // Delete Account Function
    function deleteAccount() {
      if (confirm('Apakah Anda yakin ingin menghapus akun Anda? Tindakan ini tidak dapat dibatalkan.')) {
        showNotification('error', 'Akun Dihapus', 'Akun Anda telah dihapus. Anda akan diarahkan ke halaman beranda.');
        setTimeout(() => {
          window.location.href = 'auth.php';
        }, 2000);
      }
    }

    // Notification functions
    function showNotification(type, title, message) {
      const notification = document.getElementById('notification');
      const notificationTitle = document.getElementById('notificationTitle');
      const notificationBody = document.getElementById('notificationBody');
      
      // Set notification type
      notification.className = `notification ${type}`;
      
      // Set content
      notificationTitle.textContent = title;
      notificationBody.textContent = message;
      
      // Show notification
      notification.style.display = 'block';
      
      // Auto hide after 5 seconds
      setTimeout(() => {
        hideNotification();
      }, 5000);
    }

    function hideNotification() {
      const notification = document.getElementById('notification');
      notification.style.display = 'none';
    }

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
      const formData = new FormData();
      formData.append('mark_notification_read', '1');
      formData.append('notification_id', notificationId);
      
      fetch('upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Remove unread class from notification
          const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
          if (notificationItem) {
            notificationItem.classList.remove('unread');
          }
          
          // Update unread count
          const badge = document.querySelector('.notification-badge');
          if (badge) {
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 1) {
              badge.textContent = currentCount - 1;
            } else {
              badge.style.display = 'none';
            }
          }
        }
      })
      .catch(error => {
        console.error('Error marking notification as read:', error);
      });
    }

    // Mark all notifications as read
    function markAllAsRead() {
      const formData = new FormData();
      formData.append('mark_all_read', '1');
      
      fetch('upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Remove unread class from all notifications
          document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.classList.remove('unread');
          });
          
          // Hide badge
          const badge = document.querySelector('.notification-badge');
          if (badge) {
            badge.style.display = 'none';
          }
        }
      })
      .catch(error => {
        console.error('Error marking all notifications as read:', error);
      });
      
      return false; // Prevent default link behavior
    }

    document.addEventListener('DOMContentLoaded', function() {
      const addAuthorBtn = document.getElementById('add-author-btn');
      const authorsContainer = document.getElementById('authors-container');
      const addKeywordBtn = document.getElementById('add-keyword-btn');
      const keywordsContainer = document.getElementById('keywords-container');
      const fileUploadInput = document.getElementById('dokumen_file');
      const fileNameDisplay = document.getElementById('file-name-display');
      const fileUploadArea = document.getElementById('file-upload-area');
      const uploadForm = document.getElementById('uploadForm');
      const submitBtn = document.getElementById('submitBtn');
      const progressContainer = document.getElementById('progressContainer');
      const progressFill = document.getElementById('progressFill');
      const progressPercent = document.getElementById('progressPercent');
      const progressStatus = document.getElementById('progressStatus');

      // Function to add a new input field
      function addField(container, placeholder, name) {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';

        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.className = 'form-control';
        newInput.name = name;
        newInput.placeholder = placeholder;
        newInput.required = true;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = function() {
          inputGroup.remove();
          updateRemoveButtons(container);
        };

        inputGroup.appendChild(newInput);
        inputGroup.appendChild(removeBtn);
        container.appendChild(inputGroup);
        updateRemoveButtons(container);
      }

      // Function to show/hide remove buttons
      function updateRemoveButtons(container) {
        const inputGroups = container.querySelectorAll('.input-group');
        inputGroups.forEach((group, index) => {
          const removeBtn = group.querySelector('.btn-remove');
          if (inputGroups.length > 1) {
            removeBtn.style.display = 'block';
          } else {
            removeBtn.style.display = 'none';
          }
        });
      }

      addAuthorBtn.addEventListener('click', () => addField(authorsContainer, 'Nama penulis', 'authors[]'));
      addKeywordBtn.addEventListener('click', () => addField(keywordsContainer, 'Masukkan kata kunci', 'keywords[]'));

      // File upload display
      fileUploadInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
          fileNameDisplay.textContent = `File yang dipilih: ${this.files[0].name}`;
        } else {
          fileNameDisplay.textContent = 'Belum ada file yang dipilih.';
        }
      });

      // Drag and drop functionality
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, preventDefaults, false);
      });

      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }

      ['dragenter', 'dragover'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('dragover'), false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('dragover'), false);
      });

      fileUploadArea.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
          fileUploadInput.files = files;
          const event = new Event('change', { bubbles: true });
          fileUploadInput.dispatchEvent(event);
        }
      });

      // Form submission
      uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show progress
        progressContainer.style.display = 'block';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengunggah...';
        
        // Create FormData object
        const formData = new FormData(uploadForm);
        
        // Simulate progress
        let progress = 0;
        const interval = setInterval(() => {
          progress += 5;
          if (progress > 90) {
            clearInterval(interval);
          }
          progressFill.style.width = progress + '%';
          progressPercent.textContent = progress + '%';
        }, 200);
        
        // Send form data to server
        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          clearInterval(interval);
          progressFill.style.width = '100%';
          progressPercent.textContent = '100%';
          progressStatus.textContent = 'Selesai!';
          
          if (data.success) {
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = `
              <i class="bi bi-check-circle-fill"></i>
              <div>
                <strong>Sukses:</strong> ${data.message}
              </div>
            `;
            
            // Insert alert at the top of the container
            const container = document.querySelector('.upload-container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Reset form after a delay
            setTimeout(() => {
              uploadForm.reset();
              fileNameDisplay.textContent = 'Belum ada file yang dipilih.';
              progressContainer.style.display = 'none';
              progressFill.style.width = '0%';
              progressPercent.textContent = '0%';
              progressStatus.textContent = 'Mengunggah...';
              
              // Redirect to dashboard if specified
              if (data.redirect) {
                window.location.href = data.redirect;
              }
            }, 2000);
          } else {
            // Show error message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.innerHTML = `
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div>
                <strong>Error:</strong> ${data.message}
              </div>
            `;
            
            // Insert alert at the top of the container
            const container = document.querySelector('.upload-container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Reset progress
            progressContainer.style.display = 'none';
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';
            progressStatus.textContent = 'Mengunggah...';
          }
        })
        .catch(error => {
          clearInterval(interval);
          console.error('Error:', error);
          
          // Show error message
          const alertDiv = document.createElement('div');
          alertDiv.className = 'alert alert-error';
          alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
              <strong>Error:</strong> Terjadi kesalahan saat mengunggah dokumen. Silakan coba lagi.
            </div>
          `;
          
          // Insert alert at the top of the container
          const container = document.querySelector('.upload-container');
          container.insertBefore(alertDiv, container.firstChild);
          
          // Reset progress
          progressContainer.style.display = 'none';
          progressFill.style.width = '0%';
          progressPercent.textContent = '0%';
          progressStatus.textContent = 'Mengunggah...';
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Upload Dokumen';
        });
      });
      
      // Mark notification as read when clicked
      document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
          const notificationId = this.getAttribute('data-id');
          if (notificationId && this.classList.contains('unread')) {
            markNotificationAsRead(notificationId);
          }
        });
      });
    });

    // Show notification if profile was updated
    <?php if (isset($profile_updated) && $profile_updated): ?>
      showNotification('success', 'Profil Diperbarui', 'Profil Anda telah berhasil diperbarui.');
    <?php endif; ?>

    // Show notification if password was updated
    <?php if (isset($password_updated) && $password_updated): ?>
      showNotification('success', 'Password Diubah', 'Password Anda telah berhasil diubah.');
    <?php endif; ?>

    // Show notification if photo was updated
    <?php if (isset($photo_updated) && $photo_updated): ?>
      showNotification('success', 'Foto Profil Diperbarui', 'Foto profil Anda telah berhasil diperbarui.');
    <?php endif; ?>

    // Show notification if there was an error updating profile
    <?php if (isset($profile_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($profile_error); ?>');
    <?php endif; ?>

    // Show notification if there was an error updating password
    <?php if (isset($password_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($password_error); ?>');
    <?php endif; ?>

    // Show notification if there was an error uploading photo
    <?php if (isset($photo_error)): ?>
      showNotification('error', 'Error', '<?php echo addslashes($photo_error); ?>');
    <?php endif; ?>
  </script>
</body>
</html>
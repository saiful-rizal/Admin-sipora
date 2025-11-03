<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/search/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

// Proses logout
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('username', '', time() - 3600, "/");
    header("Location: auth.php");
    exit();
}

// Ambil data user dari database
 $user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT id_user, username, email, role_id FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Ambil data profil lengkap user
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

// Ambil data filter (Jurusan, Prodi, Tahun) untuk dropdown
try {
    $jurusan_data = $pdo->query("SELECT id_jurusan, nama_jurusan FROM master_jurusan ORDER BY nama_jurusan")->fetchAll(PDO::FETCH_ASSOC);
    $prodi_data = $pdo->query("SELECT id_prodi, nama_prodi FROM master_prodi ORDER BY nama_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $tahun_data = $pdo->query("SELECT DISTINCT year_id FROM dokumen ORDER BY year_id DESC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $jurusan_data = [];
    $prodi_data = [];
    $tahun_data = [];
}

// Mendapatkan nilai filter dari GET
 $filter_jurusan = isset($_GET['filter_jurusan']) ? $_GET['filter_jurusan'] : '';
 $filter_prodi = isset($_GET['filter_prodi']) ? $_GET['filter_prodi'] : '';
 $filter_tahun = isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : '';
 $search_term = isset($_GET['search']) ? $_GET['search'] : '';
 $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
 $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
 $sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Membangun query dasar untuk mengambil dokumen
 $query = "
SELECT 
   d.dokumen_id AS id_book, 
   d.judul AS title, 
   d.tipe_dokumen AS type,
   d.abstrak AS abstract,
   (SELECT nama_jurusan FROM master_jurusan WHERE id_jurusan = d.id_jurusan) AS department,
   (SELECT nama_prodi FROM master_prodi WHERE id_prodi = d.id_prodi) AS prodi,
   d.year_id AS year,
   d.file_path AS file_path,
   d.tgl_unggah AS upload_date,
   d.status_id AS status_id,
   u.username AS penulis,
   (SELECT COUNT(*) FROM download_history WHERE dokumen_id = d.dokumen_id) AS download_count
FROM dokumen d
LEFT JOIN users u ON d.uploader_id = u.id_user
WHERE 1=1
";

// Menambahkan filter ke query secara dinamis
 $params = [];
if (!empty($filter_jurusan)) {
   $query .= " AND d.id_jurusan = :jurusan";
   $params['jurusan'] = $filter_jurusan;
}
if (!empty($filter_prodi)) {
   $query .= " AND d.id_prodi = :prodi";
   $params['prodi'] = $filter_prodi;
}
if (!empty($filter_tahun)) {
   $query .= " AND d.year_id = :tahun";
   $params['tahun'] = $filter_tahun;
}
if (!empty($search_term)) {
   $query .= " AND (d.judul LIKE :search OR d.abstrak LIKE :search)";
   $params['search'] = '%' . $search_term . '%';
}
if (!empty($category_filter)) {
   // Mapping kategori dari dropdown ke tipe dokumen di database
   switch($category_filter) {
       case 'Skripsi':
           $query .= " AND d.tipe_dokumen = 'thesis'";
           break;
       case 'Analisis Data':
           $query .= " AND d.tipe_dokumen = 'research'";
           break;
       case 'Review':
           $query .= " AND d.tipe_dokumen = 'journal'";
           break;
   }
}

// Filter berdasarkan status
if (!empty($status_filter)) {
   switch($status_filter) {
       case 'semua':
           // Tidak ada filter, tampilkan semua
           break;
       case 'diterbitkan':
           $query .= " AND d.status_id = 1";
           break;
       case 'review':
           $query .= " AND d.status_id = 2";
           break;
   }
}

// Pengurutan
switch($sort_filter) {
   case 'terbaru':
       $query .= " ORDER BY d.tgl_unggah DESC";
       break;
   case 'terlama':
       $query .= " ORDER BY d.tgl_unggah ASC";
       break;
   case 'terpopuler':
       $query .= " ORDER BY download_count DESC";
       break;
   case 'abjad':
       $query .= " ORDER BY d.judul ASC";
       break;
}

// Eksekusi query untuk mengambil data dokumen
try {
   $stmt = $pdo->prepare($query);
   $stmt->execute($params);
   $documents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
   $total_documents = count($documents_data);
} catch (PDOException $e) {
   // Jika terjadi error, set $documents_data menjadi array kosong
   $documents_data = [];
   $total_documents = 0;
}

// Data kata kunci populer (bisa diambil dari database atau ditentukan secara manual)
 $popular_keywords = [
   'Machine Learning', 'Data Mining', 'Analisis Sentimen', 'Deep Learning', 
   'Sistem Pakar', 'Jaringan Saraf Tiruan', 'Klasifikasi', 'Clustering'
];

// Data dokumen terbaru (bisa diambil dari database atau ditentukan secara manual)
 $latest_documents = [
   [
       'id' => 1,
       'title' => 'Implementasi Machine Learning untuk Prediksi Hasil Belajar Mahasiswa',
       'abstract' => 'Penelitian tentang penggunaan algoritma machine learning dalam memprediksi hasil belajar mahasiswa berdasarkan data akademik.',
       'author' => 'M. Anang Maaruf',
       'type' => 'thesis'
   ],
   [
       'id' => 2,
       'title' => 'Analisis Sentimen pada Ulasan Produk Menggunakan Naive Bayes',
       'abstract' => 'Penelitian tentang penerapan algoritma Naive Bayes untuk menganalisis sentimen pada ulasan produk e-commerce.',
       'author' => 'Siti Nurhaliza',
       'type' => 'research'
   ],
   [
       'id' => 3,
       'title' => 'Sistem Pakar Diagnosa Penyakit Kulit Berbasis Web',
       'abstract' => 'Pengembangan sistem pakar untuk membantu mendiagnosis penyakit kulit berdasarkan gejala yang dialami pasien.',
       'author' => 'Ahmad Fauzi',
       'type' => 'final_project'
   ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browser Dokumen | SIPORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/search.css" rel="stylesheet">
</head>
<body>
  <!-- Subtle Background Animation -->
  <div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
  </div>

  <!-- Navbar (sama persis seperti di dashboard) -->
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
        <a href="upload.php">Upload</a>
        <a href="browser.php">Browser</a>
        <a href="search.php" class="active">Search</a>
        <a href="download.php">Download</a>
      </div>
      <div class="user-info">
        <span><?php echo htmlspecialchars($user_data['username']); ?></span>
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
          <div class="user-dropdown-divider"></div>
          <a href="?logout=true" class="user-dropdown-item user-dropdown-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Konten Halaman Browser -->
  <div class="search-container">
    <div class="search-header">
      <h4>Browser Dokumen</h4>
      <p>Jelajahi repository dokumen akademik POLITEKNIK NEGERI JEMBER</p>
    </div>
    
    <!-- Search Box -->
    <div class="search-box">
      <form method="GET" action="" id="searchForm">
        <input type="text" name="search" id="searchInput" placeholder="Cari judul, penulis, atau kata kunci..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit">Cari</button>
        <!-- Hidden fields untuk mempertahankan filter -->
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
        <input type="hidden" name="filter_jurusan" value="<?php echo htmlspecialchars($filter_jurusan); ?>">
        <input type="hidden" name="filter_prodi" value="<?php echo htmlspecialchars($filter_prodi); ?>">
        <input type="hidden" name="filter_tahun" value="<?php echo htmlspecialchars($filter_tahun); ?>">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_filter); ?>">
      </form>
    </div>
    
    
    <!-- Document Count -->
    <div class="document-count">
      Menampilkan <strong><?php echo $total_documents; ?></strong> dokumen
      <?php if (!empty($search_term)): ?>
        untuk '<strong><?php echo htmlspecialchars($search_term); ?></strong>'
      <?php endif; ?>
    </div>
    
    <!-- Search Sections (hanya muncul jika tidak ada pencarian) -->
    <div class="search-sections" id="searchSections">
      <!-- Popular Keywords Section -->
      <div class="section-container">
        <div class="section-title">
          <i class="bi bi-fire"></i>
          <span>Pencarian Populer</span>
        </div>
        <div class="keywords-container">
          <?php foreach ($popular_keywords as $keyword): ?>
            <div class="keyword-tag" onclick="searchKeyword('<?php echo htmlspecialchars($keyword); ?>')">
              <?php echo htmlspecialchars($keyword); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- Latest Documents Section -->
      <div class="section-container">
        <div class="section-title">
          <i class="bi bi-clock-history"></i>
          <span>Dokumen Terbaru</span>
        </div>
        <div class="document-list">
          <?php foreach ($latest_documents as $document): ?>
            <div class="document-card">
              <div class="document-card-header">
                <div class="document-title-section">
                  <div class="document-badges">
                    <span class="badge <?php echo getStatusBadge(1); ?>"><?php echo getStatusName(1); ?></span>
                    <span class="badge badge-danger"><?php echo getDocumentTypeName($document['type']); ?></span>
                  </div>
                  <h6><?php echo htmlspecialchars($document['title']); ?></h6>
                </div>
              </div>
              <div class="document-card-body">
                <p class="document-abstract"><?php echo htmlspecialchars($document['abstract']); ?></p>
                <div class="document-meta">
                  <div class="document-meta-item">
                    <i class="bi bi-person"></i>
                    <span><?php echo htmlspecialchars($document['author']); ?></span>
                  </div>
                  <div class="document-meta-item">
                    <i class="bi bi-calendar3"></i>
                    <span>2023</span>
                  </div>
                </div>
              </div>
              <div class="document-card-footer">
                <div class="document-date">
                  <i class="bi bi-calendar"></i>
                  <span>15 Oktober 2023</span>
                </div>
                <div class="document-actions">
                  <div class="document-stats">
                    <i class="bi bi-download"></i>
                    <span>124 Download</span>
                  </div>
                  <div class="document-actions-buttons">
                    <button class="btn-view" onclick="viewDocument(<?php echo $document['id']; ?>)">
                      <i class="bi bi-eye"></i>
                      Lihat
                    </button>
                    <a href="download.php?id=<?php echo $document['id']; ?>" class="btn-download" onclick="handleDownload(event, <?php echo $document['id']; ?>)">
                      <i class="bi bi-download"></i>
                      Download
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    
    <!-- Search Results (hanya muncul jika ada pencarian) -->
    <div class="search-results-header" id="searchResultsHeader" style="<?php echo empty($search_term) ? 'display: none;' : ''; ?>">
      <div class="search-results-title">Hasil Pencarian</div>
      <div class="search-results-count">Ditemukan <?php echo $total_documents; ?> dokumen untuk '<?php echo htmlspecialchars($search_term); ?>'</div>
    </div>
    
    <!-- Document List -->
    <?php if (empty($documents_data)): ?>
      <div class="document-card" id="noResults" style="<?php echo empty($search_term) ? 'display: none;' : ''; ?>">
        <div class="document-card-header">
          <div class="document-title-section">
            <h6>Tidak ada dokumen ditemukan</h6>
            <div class="document-badges">
              <span class="badge badge-info">Info</span>
            </div>
          </div>
        </div>
        <div class="document-card-body">
          <p class="document-abstract">Coba ubah filter atau kata kunci pencarian Anda.</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($documents_data as $document): ?>
        <div class="document-card" id="searchResults" style="<?php echo empty($search_term) ? 'display: none;' : ''; ?>">
          <div class="document-card-header">
            <div class="document-title-section">
              <div class="document-badges">
                <span class="badge <?php echo getStatusBadge($document['status_id']); ?>"><?php echo getStatusName($document['status_id']); ?></span>
                <span class="badge badge-danger"><?php echo getDocumentTypeName($document['type']); ?></span>
              </div>
              <h6><?php echo htmlspecialchars($document['title']); ?></h6>
            </div>
          </div>
          <div class="document-card-body">
            <p class="document-abstract"><?php echo htmlspecialchars($document['abstract']); ?></p>
            <div class="document-meta">
              <div class="document-meta-item">
                <i class="bi bi-person"></i>
                <span><?php echo htmlspecialchars($document['penulis'] ?? 'Tidak diketahui'); ?></span>
              </div>
              <?php if ($document['department']): ?>
                <div class="document-meta-item">
                  <i class="bi bi-building"></i>
                  <span><?php echo htmlspecialchars($document['department']); ?></span>
                </div>
              <?php endif; ?>
              <?php if ($document['prodi']): ?>
                <div class="document-meta-item">
                  <i class="bi bi-book"></i>
                  <span><?php echo htmlspecialchars($document['prodi']); ?></span>
                </div>
              <?php endif; ?>
              <?php if ($document['year']): ?>
                <div class="document-meta-item">
                  <i class="bi bi-calendar3"></i>
                  <span><?php echo htmlspecialchars($document['year']); ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="document-card-footer">
            <div class="document-date">
              <i class="bi bi-calendar"></i>
              <span><?php echo date('d F Y', strtotime($document['upload_date'])); ?></span>
            </div>
            <div class="document-actions">
              <div class="document-stats">
                <i class="bi bi-download"></i>
                <span><?php echo $document['download_count']; ?> Download</span>
              </div>
              <div class="document-actions-buttons">
                <button class="btn-view" onclick="viewDocument(<?php echo $document['id_book']; ?>)">
                  <i class="bi bi-eye"></i>
                  Lihat
                </button>
                <a href="download.php?id=<?php echo $document['id_book']; ?>" class="btn-download" onclick="handleDownload(event, <?php echo $document['id_book']; ?>)">
                  <i class="bi bi-download"></i>
                  Download
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/search.js"></script>
</body>
</html>
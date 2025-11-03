-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 02, 2025 at 01:47 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sipora_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `distribusi_dokumen`
--

CREATE TABLE `distribusi_dokumen` (
  `id_distribusi` int NOT NULL,
  `id_dokumen` int NOT NULL,
  `id_admin` int NOT NULL,
  `id_jurusan` int NOT NULL,
  `id_prodi` int NOT NULL,
  `tanggal_kirim` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id_dokumen` int NOT NULL,
  `id_user` int NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `abstrak` text COLLATE utf8mb4_general_ci,
  `kata_kunci` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kategori` int NOT NULL,
  `file_nama` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_upload` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status_dokumen` enum('sedang_dikoreksi','berhasil','gagal') COLLATE utf8mb4_general_ci DEFAULT 'sedang_dikoreksi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jurusan`
--

CREATE TABLE `jurusan` (
  `id_jurusan` int NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jurusan`
--

INSERT INTO `jurusan` (`id_jurusan`, `nama_jurusan`) VALUES
(3, 'teknik');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_perpustakaan`
--

CREATE TABLE `kategori_perpustakaan` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_perpustakaan`
--

INSERT INTO `kategori_perpustakaan` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Skripsi'),
(2, 'Tugas Akhir'),
(3, 'Tesis'),
(4, 'Disertasi'),
(5, 'Penelitian');

-- --------------------------------------------------------

--
-- Table structure for table `library_mahasiswa`
--

CREATE TABLE `library_mahasiswa` (
  `id_library` int NOT NULL,
  `id_user` int NOT NULL,
  `id_dokumen` int NOT NULL,
  `id_kategori` int NOT NULL,
  `tanggal_masuk` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prodi`
--

CREATE TABLE `prodi` (
  `id_prodi` int NOT NULL,
  `id_jurusan` int NOT NULL,
  `nama_prodi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nim` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') DEFAULT 'mahasiswa',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `nim`, `email`, `username`, `password_hash`, `role`, `status`, `created_at`) VALUES
(1, 'admin', '123123', 'admin@gmail.com', 'admin', 'admin', 'admin', 'approved', '2025-11-02 10:16:32'),
(2, 'saiful rizal', 'E41240390', 'rizalsaiful230206@gmail.com', 'Saiful Rizal', '$2y$10$0PIOHb1.O8D.DEFRTa.W0O5nraU8tXaNSgUr3.Z5YRuvJvx/yqgBq', 'mahasiswa', 'approved', '2025-11-02 13:18:30');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_approved` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
  IF NEW.status = 'approved' AND OLD.status <> 'approved' THEN
    INSERT INTO `user_verification` (`id_user`, `token`)
    VALUES (NEW.id_user, UUID());
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_verification`
--

CREATE TABLE `user_verification` (
  `id_verifikasi` int NOT NULL,
  `id_user` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `tanggal_kirim` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verifikasi_dokumen`
--

CREATE TABLE `verifikasi_dokumen` (
  `id_verifikasi_dokumen` int NOT NULL,
  `id_dokumen` int NOT NULL,
  `id_admin` int NOT NULL,
  `status` enum('sedang_dikoreksi','berhasil','gagal') COLLATE utf8mb4_general_ci DEFAULT 'sedang_dikoreksi',
  `catatan_admin` text COLLATE utf8mb4_general_ci,
  `tanggal_verifikasi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `verifikasi_dokumen`
--
DELIMITER $$
CREATE TRIGGER `after_koreksi_dokumen` AFTER UPDATE ON `verifikasi_dokumen` FOR EACH ROW BEGIN
  DECLARE v_user INT;
  DECLARE v_kategori INT;
  SELECT id_user, id_kategori INTO v_user, v_kategori
  FROM dokumen WHERE id_dokumen = NEW.id_dokumen;
  
  IF NEW.status = 'berhasil' THEN
    UPDATE dokumen SET status_dokumen = 'berhasil' WHERE id_dokumen = NEW.id_dokumen;
    INSERT INTO library_mahasiswa (id_user, id_dokumen, id_kategori)
    VALUES (v_user, NEW.id_dokumen, v_kategori);
  ELSEIF NEW.status = 'gagal' THEN
    UPDATE dokumen SET status_dokumen = 'gagal' WHERE id_dokumen = NEW.id_dokumen;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_dokumen_sedang_dikoreksi`
-- (See below for the actual view)
--
CREATE TABLE `v_dokumen_sedang_dikoreksi` (
`id_dokumen` int
,`judul` varchar(150)
,`nama_lengkap` varchar(100)
,`status_dokumen` enum('sedang_dikoreksi','berhasil','gagal')
,`tanggal_upload` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_mahasiswa_pending`
-- (See below for the actual view)
--
CREATE TABLE `v_mahasiswa_pending` (
`created_at` timestamp
,`email` varchar(100)
,`id_user` int
,`nama_lengkap` varchar(100)
,`status` enum('pending','approved','rejected')
,`username` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure for view `v_dokumen_sedang_dikoreksi`
--
DROP TABLE IF EXISTS `v_dokumen_sedang_dikoreksi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dokumen_sedang_dikoreksi`  AS SELECT `d`.`id_dokumen` AS `id_dokumen`, `d`.`judul` AS `judul`, `u`.`nama_lengkap` AS `nama_lengkap`, `d`.`status_dokumen` AS `status_dokumen`, `d`.`tanggal_upload` AS `tanggal_upload` FROM (`dokumen` `d` join `users` `u` on((`d`.`id_user` = `u`.`id_user`))) WHERE (`d`.`status_dokumen` = 'sedang_dikoreksi')  ;

-- --------------------------------------------------------

--
-- Structure for view `v_mahasiswa_pending`
--
DROP TABLE IF EXISTS `v_mahasiswa_pending`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_mahasiswa_pending`  AS SELECT `users`.`id_user` AS `id_user`, `users`.`nama_lengkap` AS `nama_lengkap`, `users`.`email` AS `email`, `users`.`username` AS `username`, `users`.`status` AS `status`, `users`.`created_at` AS `created_at` FROM `users` WHERE (`users`.`status` = 'pending')  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `distribusi_dokumen`
--
ALTER TABLE `distribusi_dokumen`
  ADD PRIMARY KEY (`id_distribusi`),
  ADD KEY `id_dokumen` (`id_dokumen`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `id_jurusan` (`id_jurusan`),
  ADD KEY `id_prodi` (`id_prodi`);

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`id_jurusan`);

--
-- Indexes for table `kategori_perpustakaan`
--
ALTER TABLE `kategori_perpustakaan`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `library_mahasiswa`
--
ALTER TABLE `library_mahasiswa`
  ADD PRIMARY KEY (`id_library`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_dokumen` (`id_dokumen`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `prodi`
--
ALTER TABLE `prodi`
  ADD PRIMARY KEY (`id_prodi`),
  ADD KEY `id_jurusan` (`id_jurusan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- Indexes for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD PRIMARY KEY (`id_verifikasi`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `verifikasi_dokumen`
--
ALTER TABLE `verifikasi_dokumen`
  ADD PRIMARY KEY (`id_verifikasi_dokumen`),
  ADD KEY `id_dokumen` (`id_dokumen`),
  ADD KEY `id_admin` (`id_admin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `distribusi_dokumen`
--
ALTER TABLE `distribusi_dokumen`
  MODIFY `id_distribusi` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id_dokumen` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `id_jurusan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kategori_perpustakaan`
--
ALTER TABLE `kategori_perpustakaan`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `library_mahasiswa`
--
ALTER TABLE `library_mahasiswa`
  MODIFY `id_library` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prodi`
--
ALTER TABLE `prodi`
  MODIFY `id_prodi` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_verification`
--
ALTER TABLE `user_verification`
  MODIFY `id_verifikasi` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verifikasi_dokumen`
--
ALTER TABLE `verifikasi_dokumen`
  MODIFY `id_verifikasi_dokumen` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `distribusi_dokumen`
--
ALTER TABLE `distribusi_dokumen`
  ADD CONSTRAINT `distribusi_dokumen_ibfk_1` FOREIGN KEY (`id_dokumen`) REFERENCES `dokumen` (`id_dokumen`) ON DELETE CASCADE,
  ADD CONSTRAINT `distribusi_dokumen_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `distribusi_dokumen_ibfk_3` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE CASCADE,
  ADD CONSTRAINT `distribusi_dokumen_ibfk_4` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_perpustakaan` (`id_kategori`) ON DELETE CASCADE;

--
-- Constraints for table `library_mahasiswa`
--
ALTER TABLE `library_mahasiswa`
  ADD CONSTRAINT `library_mahasiswa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_mahasiswa_ibfk_2` FOREIGN KEY (`id_dokumen`) REFERENCES `dokumen` (`id_dokumen`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_mahasiswa_ibfk_3` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_perpustakaan` (`id_kategori`) ON DELETE CASCADE;

--
-- Constraints for table `prodi`
--
ALTER TABLE `prodi`
  ADD CONSTRAINT `prodi_ibfk_1` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE CASCADE;

--
-- Constraints for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD CONSTRAINT `user_verification_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `verifikasi_dokumen`
--
ALTER TABLE `verifikasi_dokumen`
  ADD CONSTRAINT `verifikasi_dokumen_ibfk_1` FOREIGN KEY (`id_dokumen`) REFERENCES `dokumen` (`id_dokumen`) ON DELETE CASCADE,
  ADD CONSTRAINT `verifikasi_dokumen_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `hapus_token_kadaluarsa` ON SCHEDULE EVERY 1 DAY STARTS '2025-11-02 16:57:48' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM `user_verification`
  WHERE is_verified = 0 AND tanggal_kirim < (NOW() - INTERVAL 1 DAY)$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================================
--  Basis data Sistem Perpustakaan (INLISLite) - MySQL / MariaDB
--  Cara pakai: impor file ini lewat phpMyAdmin atau  mysql -u root < inlislite.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS `inlislite` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inlislite`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `log_aktivitas`, `pengaturan`, `laporan_kinerja`, `dashboard_manfaat`,
    `basis_pengetahuan`, `masalah`, `tiket_riwayat`, `tiket_insiden`, `transaksi`,
    `koleksi`, `bibliografi`, `anggota`, `kategori_layanan`, `jenis_layanan`, `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- users : akun login (Admin, Pemustaka, Pimpinan)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id_user`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100) NOT NULL,
  `username`   VARCHAR(50)  NOT NULL,
  `email`      VARCHAR(100) DEFAULT NULL,
  `password`   VARCHAR(255) NOT NULL COMMENT 'hash bcrypt (password_hash)',
  `peran`      ENUM('Admin','Pemustaka','Pimpinan') NOT NULL,
  `status`     ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_peran` (`peran`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- anggota : data keanggotaan (0..1 relasi ke users)
-- ---------------------------------------------------------------------
CREATE TABLE `anggota` (
  `id_anggota`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user`       INT UNSIGNED DEFAULT NULL,
  `nomor_anggota` VARCHAR(30)  NOT NULL,
  `nama`          VARCHAR(100) NOT NULL,
  `jenis_anggota` ENUM('Umum','Pelajar','Mahasiswa','Pegawai') NOT NULL DEFAULT 'Umum',
  `jenis_kelamin` ENUM('Laki-laki','Perempuan') DEFAULT NULL,
  `alamat`        VARCHAR(255) DEFAULT NULL,
  `telepon`       VARCHAR(20)  DEFAULT NULL,
  `email`         VARCHAR(100) DEFAULT NULL,
  `tanggal_daftar` DATE NOT NULL,
  `masa_berlaku`  DATE NOT NULL,
  `status`        ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_anggota`),
  UNIQUE KEY `uq_anggota_nomor` (`nomor_anggota`),
  UNIQUE KEY `uq_anggota_user` (`id_user`),
  CONSTRAINT `fk_anggota_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- bibliografi : data bibliografis (judul) untuk katalog & OPAC
-- ---------------------------------------------------------------------
CREATE TABLE `bibliografi` (
  `id_bibliografi` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul`          VARCHAR(255) NOT NULL,
  `pengarang`      VARCHAR(150) NOT NULL,
  `penerbit`       VARCHAR(150) DEFAULT NULL,
  `kota_terbit`    VARCHAR(80)  DEFAULT NULL,
  `tahun_terbit`   SMALLINT UNSIGNED DEFAULT NULL,
  `isbn`           VARCHAR(30)  DEFAULT NULL,
  `subjek`         VARCHAR(255) DEFAULT NULL,
  `nomor_panggil`  VARCHAR(60)  DEFAULT NULL,
  `jenis_bahan`    VARCHAR(40)  NOT NULL DEFAULT 'Monograf',
  `deskripsi_fisik` VARCHAR(120) DEFAULT NULL,
  `cover`          VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bibliografi`),
  UNIQUE KEY `uq_bibliografi_isbn` (`isbn`),
  KEY `idx_bibliografi_judul` (`judul`),
  KEY `idx_bibliografi_pengarang` (`pengarang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- koleksi : eksemplar (1 bibliografi punya banyak koleksi)
-- ---------------------------------------------------------------------
CREATE TABLE `koleksi` (
  `id_koleksi`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_bibliografi` INT UNSIGNED NOT NULL,
  `barcode`        VARCHAR(40)  NOT NULL,
  `lokasi`         VARCHAR(100) NOT NULL,
  `status`         ENUM('Tersedia','Dipinjam','Rusak','Hilang') NOT NULL DEFAULT 'Tersedia',
  `tanggal_pengadaan` DATE DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_koleksi`),
  UNIQUE KEY `uq_koleksi_barcode` (`barcode`),
  KEY `idx_koleksi_bibliografi` (`id_bibliografi`),
  CONSTRAINT `fk_koleksi_bibliografi` FOREIGN KEY (`id_bibliografi`) REFERENCES `bibliografi` (`id_bibliografi`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- transaksi : sirkulasi (peminjaman, perpanjangan, pengembalian, denda)
-- ---------------------------------------------------------------------
CREATE TABLE `transaksi` (
  `id_transaksi`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_anggota`          INT UNSIGNED NOT NULL,
  `id_koleksi`          INT UNSIGNED NOT NULL,
  `id_petugas`          INT UNSIGNED DEFAULT NULL,
  `tanggal_pinjam`      DATE NOT NULL,
  `tanggal_jatuh_tempo` DATE NOT NULL,
  `tanggal_kembali`     DATE DEFAULT NULL,
  `jumlah_perpanjang`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `denda`               INT UNSIGNED NOT NULL DEFAULT 0,
  `status`              ENUM('Dipinjam','Dikembalikan') NOT NULL DEFAULT 'Dipinjam',
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `idx_transaksi_anggota` (`id_anggota`, `status`),
  KEY `idx_transaksi_koleksi` (`id_koleksi`, `status`),
  KEY `idx_transaksi_tanggal` (`tanggal_pinjam`),
  CONSTRAINT `fk_transaksi_anggota` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transaksi_koleksi` FOREIGN KEY (`id_koleksi`) REFERENCES `koleksi` (`id_koleksi`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transaksi_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- jenis_layanan & kategori_layanan : referensi pilihan pada formulir keluhan
-- (dikelola admin lewat menu Kategori & Jenis; tiket mengacu ke kolom nama)
-- ---------------------------------------------------------------------
CREATE TABLE `jenis_layanan` (
  `id_jenis`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(60) NOT NULL,
  `aktif`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_jenis`),
  UNIQUE KEY `uq_jenis_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kategori_layanan` (
  `id_kategori` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`        VARCHAR(60) NOT NULL,
  `aktif`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kategori`),
  UNIQUE KEY `uq_kategori_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- tiket_insiden : permintaan / keluhan layanan pemustaka (tbl_tiket_insiden)
-- ---------------------------------------------------------------------
CREATE TABLE `tiket_insiden` (
  `id_tiket`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomor_tiket`     VARCHAR(20)  NOT NULL,
  `id_pelapor`      INT UNSIGNED NOT NULL,
  `id_admin`        INT UNSIGNED DEFAULT NULL,
  `jenis`           VARCHAR(60)  NOT NULL,
  `kategori`        VARCHAR(60)  NOT NULL,
  `urgensi`         ENUM('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Rendah',
  `deskripsi`       TEXT NOT NULL,
  `kontak`          VARCHAR(100) DEFAULT NULL,
  `status`          ENUM('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  `penanganan`      TEXT DEFAULT NULL COMMENT 'catatan penanganan / solusi',
  `tanggal_diproses` DATETIME DEFAULT NULL,
  `tanggal_selesai` DATETIME DEFAULT NULL,
  `rating`          TINYINT UNSIGNED DEFAULT NULL COMMENT 'kepuasan pemustaka 1-5',
  `ulasan`          VARCHAR(255) DEFAULT NULL,
  `tanggal_dibuat`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tiket`),
  UNIQUE KEY `uq_tiket_nomor` (`nomor_tiket`),
  KEY `idx_tiket_pelapor` (`id_pelapor`),
  KEY `idx_tiket_admin` (`id_admin`),
  KEY `idx_tiket_status` (`status`, `urgensi`),
  KEY `idx_tiket_tanggal` (`tanggal_dibuat`),
  CONSTRAINT `fk_tiket_jenis` FOREIGN KEY (`jenis`) REFERENCES `jenis_layanan` (`nama`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tiket_kategori` FOREIGN KEY (`kategori`) REFERENCES `kategori_layanan` (`nama`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tiket_pelapor` FOREIGN KEY (`id_pelapor`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tiket_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_tiket_rating` CHECK (`rating` IS NULL OR (`rating` BETWEEN 1 AND 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- tiket_riwayat : log perubahan status tiket (jejak penanganan)
-- ---------------------------------------------------------------------
CREATE TABLE `tiket_riwayat` (
  `id_riwayat`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tiket`    INT UNSIGNED NOT NULL,
  `id_user`     INT UNSIGNED DEFAULT NULL,
  `status_lama` ENUM('Baru','Diproses','Selesai') DEFAULT NULL,
  `status_baru` ENUM('Baru','Diproses','Selesai') NOT NULL,
  `catatan`     TEXT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_riwayat`),
  KEY `idx_riwayat_tiket` (`id_tiket`, `created_at`),
  CONSTRAINT `fk_riwayat_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `tiket_insiden` (`id_tiket`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_riwayat_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- masalah : analisis akar masalah dari tiket yang dieskalasi (1 tiket : 0..1 masalah)
-- ---------------------------------------------------------------------
CREATE TABLE `masalah` (
  `id_masalah`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tiket`           INT UNSIGNED NOT NULL,
  `id_admin`           INT UNSIGNED DEFAULT NULL,
  `akar_masalah`       TEXT NOT NULL,
  `tindakan_perbaikan` TEXT DEFAULT NULL,
  `langkah_pencegahan` TEXT DEFAULT NULL,
  `status`             ENUM('Dianalisis','Selesai') NOT NULL DEFAULT 'Dianalisis',
  `tanggal_analisis`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_masalah`),
  UNIQUE KEY `uq_masalah_tiket` (`id_tiket`),
  CONSTRAINT `fk_masalah_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `tiket_insiden` (`id_tiket`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_masalah_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- basis_pengetahuan : dokumentasi solusi permanen (1 masalah : 1 solusi)
-- ---------------------------------------------------------------------
CREATE TABLE `basis_pengetahuan` (
  `id_solusi`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_masalah`       INT UNSIGNED NOT NULL,
  `judul_solusi`     VARCHAR(150) NOT NULL,
  `deskripsi_solusi` TEXT NOT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_solusi`),
  UNIQUE KEY `uq_solusi_masalah` (`id_masalah`),
  CONSTRAINT `fk_solusi_masalah` FOREIGN KEY (`id_masalah`) REFERENCES `masalah` (`id_masalah`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- dashboard_manfaat : tabel ringkasan (agregasi bulanan dari tiket_insiden)
-- ---------------------------------------------------------------------
CREATE TABLE `dashboard_manfaat` (
  `id_indikator`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_indikator` VARCHAR(100) NOT NULL,
  `nilai_capaian`  DECIMAL(7,2) NOT NULL,
  `periode`        DATE NOT NULL COMMENT 'tanggal awal bulan pelaporan',
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_indikator`),
  UNIQUE KEY `uq_manfaat_indikator_periode` (`nama_indikator`, `periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- laporan_kinerja : laporan yang disimpan admin
-- ---------------------------------------------------------------------
CREATE TABLE `laporan_kinerja` (
  `id_laporan`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_admin`    INT UNSIGNED DEFAULT NULL,
  `periode`     DATE NOT NULL COMMENT 'tanggal awal bulan laporan',
  `isi_laporan` TEXT NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_laporan`),
  KEY `idx_laporan_periode` (`periode`),
  CONSTRAINT `fk_laporan_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- pengaturan : konfigurasi aplikasi (key-value)
-- ---------------------------------------------------------------------
CREATE TABLE `pengaturan` (
  `kunci`      VARCHAR(50)  NOT NULL,
  `nilai`      VARCHAR(255) NOT NULL,
  `keterangan` VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- log_aktivitas : audit log seluruh aktivitas penting
-- ---------------------------------------------------------------------
CREATE TABLE `log_aktivitas` (
  `id_log`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user`      INT UNSIGNED DEFAULT NULL,
  `aksi`         VARCHAR(30)  NOT NULL,
  `modul`        VARCHAR(30)  NOT NULL,
  `id_referensi` INT UNSIGNED DEFAULT NULL,
  `keterangan`   VARCHAR(255) DEFAULT NULL,
  `ip_address`   VARCHAR(45)  DEFAULT NULL,
  `user_agent`   VARCHAR(255) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_user` (`id_user`),
  KEY `idx_log_aksi` (`aksi`, `created_at`),
  KEY `idx_log_waktu` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Data awal: referensi layanan
INSERT INTO `jenis_layanan` (`nama`) VALUES ('Gangguan'), ('Permintaan Layanan');
INSERT INTO `kategori_layanan` (`nama`) VALUES ('Login & Sesi Akses'), ('Pencarian Katalog / OPAC'), ('Sirkulasi & Peminjaman Buku'), ('Lainnya / Fasilitas Fisik');

-- Data awal: users (password: admin123 / pimpinan123 / pemustaka123)
INSERT INTO `users` (`id_user`, `nama`, `username`, `email`, `password`, `peran`, `status`) VALUES
(1, 'Asista Petugas', 'admin', 'admin@dispusip-sumut.go.id', '$2y$10$A02B9nQgS/4FklpvebuG1eJAxjsttMiLj.Z0h1qoyF7r6tTVYoHIy', 'Admin', 'Aktif'),
(2, 'Drs. H. M. Effendy Pohan, M.Si', 'pimpinan', 'kadis@dispusip-sumut.go.id', '$2y$10$5UZoLR1Vqg6iG4tI0YHTSOcKgh3nCUkRYgCzZv/vTTPqe17KWzinu', 'Pimpinan', 'Aktif'),
(3, 'Siti Nurhaliza Siregar', 'siti', 'siti.siregar@usu.ac.id', '$2y$10$KxB5IiZmcLDj3XsxSRye8u5CnEdvIyYm/q01gZmhkxe7SVIw2eXKm', 'Pemustaka', 'Aktif'),
(4, 'Budi Pratama Hasibuan', 'budi', 'budi.hasibuan@gmail.com', '$2y$10$KxB5IiZmcLDj3XsxSRye8u5CnEdvIyYm/q01gZmhkxe7SVIw2eXKm', 'Pemustaka', 'Aktif'),
(5, 'Rahmat Hidayat Lubis', 'rahmat', 'rahmat.lubis@sumutprov.go.id', '$2y$10$KxB5IiZmcLDj3XsxSRye8u5CnEdvIyYm/q01gZmhkxe7SVIw2eXKm', 'Pemustaka', 'Aktif');

-- Data awal: anggota
INSERT INTO `anggota` (`id_anggota`, `id_user`, `nomor_anggota`, `nama`, `jenis_anggota`, `jenis_kelamin`, `alamat`, `telepon`, `email`, `tanggal_daftar`, `masa_berlaku`, `status`) VALUES
(1, 5, 'SU-2023-00101', 'Rahmat Hidayat Lubis', 'Pegawai', 'Laki-laki', 'Jl. Brigjend Katamso No. 45, Kp. Baru, Medan', '081260112233', 'rahmat.lubis@sumutprov.go.id', '2023-03-15', '2028-03-15', 'Aktif'),
(2, 3, 'SU-2024-00205', 'Siti Nurhaliza Siregar', 'Mahasiswa', 'Perempuan', 'Jl. Dr. Mansyur No. 12, Padang Bulan, Medan', '082165449988', 'siti.siregar@usu.ac.id', '2024-01-10', '2029-01-10', 'Aktif'),
(3, 4, 'SU-2025-00330', 'Budi Pratama Hasibuan', 'Pelajar', 'Laki-laki', 'Jl. Sisingamangaraja No. 88, Medan', '085270114455', 'budi.hasibuan@gmail.com', '2025-05-20', '2030-05-20', 'Aktif'),
(4, NULL, 'SU-2022-00045', 'Rina Wulandari', 'Umum', 'Perempuan', 'Jl. Gatot Subroto No. 21, Medan', '081370221133', 'rina.w@gmail.com', '2022-08-02', '2027-08-02', 'Aktif'),
(5, NULL, 'SU-2024-00412', 'Andika Putra Nasution', 'Mahasiswa', 'Laki-laki', 'Jl. Setia Budi No. 7, Medan', '082273441100', 'andika.n@gmail.com', '2024-09-05', '2029-09-05', 'Aktif'),
(6, NULL, 'SU-2021-00018', 'Dewi Lestari Simanjuntak', 'Pegawai', 'Perempuan', 'Jl. Pancing No. 3, Medan', '081265009911', 'dewi.s@sumutprov.go.id', '2021-02-14', '2026-02-14', 'Nonaktif'),
(7, NULL, 'SU-2025-00501', 'Fajar Ramadhan', 'Pelajar', 'Laki-laki', 'Jl. Amaliun No. 15, Medan', '085360772299', 'fajar.r@gmail.com', '2025-07-11', '2030-07-11', 'Aktif'),
(8, NULL, 'SU-2026-00077', 'Maya Sari Tarigan', 'Umum', 'Perempuan', 'Jl. Ringroad No. 90, Medan', '081397112244', 'maya.t@gmail.com', '2026-03-03', '2031-03-03', 'Aktif');

-- Data awal: bibliografi
INSERT INTO `bibliografi` (`id_bibliografi`, `judul`, `pengarang`, `penerbit`, `kota_terbit`, `tahun_terbit`, `isbn`, `subjek`, `nomor_panggil`, `jenis_bahan`, `deskripsi_fisik`, `cover`) VALUES
(1, 'Teori peluang', 'Prof. Dr. Asmin, M.Pd', 'Perdana Publishing', 'Medan', 2023, '978-623-411-098-2', 'Teori Peluang; Matematika; Statistika', '519.2 ASM t', 'Monograf', 'xi, 422 halaman : Ilustrasi ; 23 cm.', 'assets/images/covers/teori_peluang.jpg'),
(2, 'Pengantar Riset Operasi', 'Parapat Gultom', 'Perdana Publishing', 'Medan', 2024, '978-623-411-125-5', 'Riset Operasi', '658.403 4 GUL p', 'Monograf', 'xxi, 134 halaman : Ilustrasi ; 20 cm Buku', 'assets/images/covers/12831.jpg'),
(3, 'Personal branding Perempuan Dalam Berbagai Aktifitas', 'Nurbani', 'USU Press', 'Medan', 2022, '978-602-465-420-1', 'Personal branding', '658.8 NUR p', 'Monograf', 'ii, 69 Halaman : Ilustrasi ; 25 cm Buku', 'assets/images/covers/114949.jpg'),
(4, 'Sorgum : Alternatif pangan masa depan', 'Ameilia Zuliyanti Siregar', 'USU Press', 'Medan', 2022, '978-602-465-419-5', 'sorgva', '633.62 SIR s', 'Monograf', 'iv,72 Halaman : Ilustrasi ; 25 cm Buku', 'assets/images/covers/114885.jpg'),
(5, 'Plants vs zombies komic sains robot 5 : Pulau kitchen', 'Xiao Jiang Nan; pengalih bahasa Wenti Violi', 'Gramedia Populer', 'Jakarta', 2021, '978-602-06-4521-3', 'Sains - Komik; Komputer & Robotika', '500 XIA p', 'Monograf', '160 hlm. : ilus. ; 24 cm.', 'assets/images/covers/122149.jpg'),
(6, 'Detektif sains anak - CSI Volume 7 : Rencana terakhir si pelaku', 'Ko Hee-Jung; illustrator Seo Yong-Nam', 'BIP (Bhuana Ilmu Populer)', 'Jakarta', 2020, '978-623-216-981-4', 'Detektif Sains; Penyelidikan Ilmiah', '507 KO d', 'Monograf', '172 hlm. : ilus. ; 24 cm.', 'assets/images/covers/120016.jpg'),
(7, 'Komik sains plants vs zombies : Tumbuhan', 'Xiao Jiang Nan, Penerjemah Adistia Kartika Rini', 'M&C!', 'Jakarta', 2021, '978-602-480-879-1', 'Botani; Tumbuhan - Komik', '580 XIA k', 'Monograf', '152 hlm. ; 24 cm.', 'assets/images/covers/122145.png'),
(8, 'PLANTS VS ZOMBIES : rahasia tersembunyi di dunia', 'Xiao Jiang Nan', 'Gramedia', 'Jakarta', 2019, '978-602-06-2311-2', 'Sains Populer; Fenomena Dunia', '500 XIA r', 'Monograf', '148 hlm. ; 24 cm.', 'assets/images/covers/117010.jpg'),
(9, 'Keluarga super irit belajar sains : Coding - Taman entry', 'Storybox; Ilustrations, Ryu, Soo-Hyung', 'Elex Media Komputindo', 'Jakarta', 2022, '978-623-00-3011-5', 'Pemrograman Komputer; Coding Anak', '005.1 STO k', 'Monograf', '180 hlm. : ilus. ; 24 cm.', 'assets/images/covers/124350.jpg'),
(10, 'Di Sini Ada Setan : Sekolah Berdarah', 'Justin C', 'GagasMedia', 'Jakarta', 2019, '978-979-780-945-2', 'Novel Horor; Cerita Misteri', '813 JUS d', 'Monograf', '212 hlm. ; 19 cm.', 'assets/images/covers/117330.jpg'),
(11, 'Mukjizat Para Nabi : nabi yunus, nabi zakaria, nabi yahya, nabi isa, nabi muhammad', 'Hafez Achda', 'Diva Press', 'Yogyakarta', 2019, '978-602-391-715-0', 'Kisah Nabi; Agama Islam', '297.24 HAF m', 'Monograf', '164 hlm. : ilus. ; 20 cm.', 'assets/images/covers/115792.png'),
(12, 'Plants vs Zombies : Komik Dinosaurus : Dinosaurus dan Kerajaan lautan pasir', 'Xiao Jiang Nan; Pengalih bahasa, Wenti', 'Gramedia Pustaka Utama', 'Jakarta', 2021, '978-602-06-5120-7', 'Dinosaurus; Sains Komik', '567.9 XIA p', 'Monograf', '168 hlm. ; 24 cm.', 'assets/images/covers/124355.jpg'),
(13, 'Plants vs zombies komic dinosaurus : Penyerbuan ke kota dinosaurus', 'Xiao, Jiang Nan', 'Gramedia Pustaka Utama', 'Jakarta', 2020, '978-602-06-4411-7', 'Dinosaurus; Paleontologi Komik', '567.9 XIA k', 'Monograf', '164 hlm. ; 24 cm.', 'assets/images/covers/122319.png'),
(14, 'Ayahku bukan pembohong', 'Tere Liye', 'Gramedia Pustaka Utama', 'Jakarta', 2011, '978-979-22-6805-8', 'Novel Indonesia; Fiksi', '813 TER a', 'Monograf', '299 hlm. ; 20 cm.', 'assets/images/covers/106104.jpg'),
(15, 'Atomic habits : Perubahan kecil yang memberikan hasil yang luar biasa', 'James Clear; alih bahasa Alex Tri Kantjono Widodo', 'Gramedia Pustaka Utama', 'Jakarta', 2020, '978-602-06-3317-6', 'Pengembangan Diri; Motivasi; Psikologi Praktis', '158.1 CLE a', 'Monograf', 'xxv, 351 hlm. ; 23 cm.', 'assets/images/covers/121849.png'),
(16, 'Plants vs zombies komic dinosaurus : Dinosaurus di pulau terpencil', 'Xiao, Jiang Nan', 'Gramedia Pustaka Utama', 'Jakarta', 2021, '978-602-06-4780-4', 'Dinosaurus; Petualangan Komik', '567.9 XIA d', 'Monograf', '160 hlm. ; 24 cm.', 'assets/images/covers/122146.jpg'),
(17, 'Papua Berkisah', 'Swastika Nohara', 'Gramedia Pustaka Utama', 'Jakarta', 2018, '978-602-03-8201-9', 'Kebudayaan Papua; Antropologi; Catatan Perjalanan', '959.87 SWA p', 'Monograf', 'xiv, 240 hlm. : ilus. ; 21 cm.', 'assets/images/covers/98780.jpg'),
(18, 'ANTROPOLOGI & PEMBANGUNAN INDONESIA', 'Koentjaraningrat', 'Rajawali Pers', 'Jakarta', 2017, '978-979-421-081-8', 'Antropologi Sosial; Pembangunan Nasional', '301 KOE a', 'Monograf', 'xviii, 342 hlm. ; 23 cm.', 'assets/images/covers/92773.jpg'),
(19, 'Manajemen keselamatan kerja & kesehatan kerja', 'Sutrisno & Kusmawan Ruswandi', 'Pustaka Setia', 'Bandung', 2017, '978-979-076-634-1', 'K3; Keselamatan Kerja; Manajemen Industri', '658.38 SUT m', 'Monograf', '260 hlm. ; 24 cm.', 'assets/images/covers/103572.jpg'),
(20, 'MENGHANCURKAN BENTENG-BENTENG IBLIS', 'C. Peter Wagner', 'Immanuel', 'Jakarta', 2016, '978-979-8871-34-4', 'Teologi; Spiritualitas Agama', '230 WAG m', 'Monograf', '210 hlm. ; 21 cm.', 'assets/images/covers/100374.jpg'),
(21, 'JEJAK PEMBUNUHAN BERANTAI', 'Hermawan Aksan', 'Grasindo', 'Jakarta', 2021, '978-602-05-2415-3', 'Kriminologi; Investigasi Kriminal', '364.15 AKS j', 'Monograf', '216 hlm. ; 20 cm.', 'assets/images/covers/124493.jpg'),
(22, 'PENDIDIKAN ISLAM TRADISI DAN MODERNISASI DITENGAH TANTANGAN MILENIUM III', 'Azyumardi Azra', 'Kencana', 'Jakarta', 2018, '978-602-422-215-4', 'Pendidikan Islam; Modernisasi', '297.77 AZR p', 'Monograf', 'xii, 288 hlm. ; 21 cm.', 'assets/images/covers/101179.jpg'),
(23, 'BINTANG', 'Tere Liye', 'Gramedia Pustaka Utama', 'Jakarta', 2017, '978-602-03-5117-6', 'Novel Petualangan; Fantasi Indonesia', '813 TER b', 'Monograf', '392 hlm. ; 20 cm.', 'assets/images/covers/112776.jpg'),
(24, 'Kritik Sastra Indonesia Modern', 'Rachmat Djoko Pradopo', 'Gama Media', 'Yogyakarta', 2002, '979-9552-05-2', 'Kritik Sastra; Kesusastraan Indonesia', '810.9 RAC k', 'Monograf', 'xii, 488 hlm. ; 23 cm.', NULL),
(25, 'Pedoman pelajaran tata hukum Indonesia', 'Kusumadi Pudjosewojo', 'Sinar Grafika', 'Jakarta', 2008, '979-8767-42-X', 'Tata Hukum Indonesia; Pengantar Ilmu Hukum', '340 PUJ p', 'Monograf', 'x, 198 hlm. ; 21 cm.', 'assets/images/covers/5483.jpg'),
(26, 'Statistik ekonomi-keuangan sumatera utara = north sumatera financial statistics', 'Bank Indonesia', 'Bank Indonesia', 'Medan', 1988, '0126-1983', 'Statistik Ekonomi; Keuangan Daerah Sumatera Utara', '330.315 598 12 STA', 'Monograf', 'viii, 142 hlm. ; 28 cm.', NULL);

-- Data awal: koleksi (eksemplar)
INSERT INTO `koleksi` (`id_koleksi`, `id_bibliografi`, `barcode`, `lokasi`, `status`, `tanggal_pengadaan`) VALUES
(1, 1, 'B00001-1', 'Koleksi Deposit Sumut', 'Tersedia', '2026-02-02'),
(2, 2, 'B00002-1', 'Layanan Referensi', 'Tersedia', '2026-03-03'),
(3, 3, 'B00003-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-04-04'),
(4, 4, 'B00004-1', 'Layanan Anak', 'Tersedia', '2026-05-05'),
(5, 5, 'B00005-1', 'Layanan Referensi', 'Dipinjam', '2026-06-06'),
(6, 5, 'B00005-2', 'Layanan Referensi', 'Tersedia', '2026-06-06'),
(7, 6, 'B00006-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-07-07'),
(8, 7, 'B00007-1', 'Layanan Anak', 'Tersedia', '2026-08-08'),
(9, 7, 'B00007-2', 'Layanan Anak', 'Tersedia', '2026-08-08'),
(10, 8, 'B00008-1', 'Layanan Referensi', 'Tersedia', '2026-01-09'),
(11, 9, 'B00009-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-02-10'),
(12, 9, 'B00009-2', 'Layanan Umum Lt. 2', 'Tersedia', '2026-02-10'),
(13, 10, 'B00010-1', 'Layanan Anak', 'Dipinjam', '2026-03-11'),
(14, 11, 'B00011-1', 'Layanan Referensi', 'Tersedia', '2026-04-12'),
(15, 12, 'B00012-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-05-13'),
(16, 13, 'B00013-1', 'Layanan Anak', 'Tersedia', '2026-06-14'),
(17, 14, 'B00014-1', 'Layanan Referensi', 'Tersedia', '2026-07-15'),
(18, 15, 'B00015-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-08-16'),
(19, 15, 'B00015-2', 'Layanan Umum Lt. 2', 'Dipinjam', '2026-08-16'),
(20, 15, 'B00015-3', 'Layanan Umum Lt. 2', 'Tersedia', '2026-08-16'),
(21, 16, 'B00016-1', 'Layanan Anak', 'Dipinjam', '2026-01-17'),
(22, 17, 'B00017-1', 'Layanan Referensi', 'Tersedia', '2026-02-18'),
(23, 18, 'B00018-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-03-19'),
(24, 18, 'B00018-2', 'Layanan Umum Lt. 2', 'Tersedia', '2026-03-19'),
(25, 19, 'B00019-1', 'Layanan Anak', 'Tersedia', '2026-04-20'),
(26, 20, 'B00020-1', 'Layanan Referensi', 'Tersedia', '2026-05-21'),
(27, 21, 'B00021-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-06-22'),
(28, 22, 'B00022-1', 'Layanan Anak', 'Tersedia', '2026-07-23'),
(29, 22, 'B00022-2', 'Layanan Anak', 'Tersedia', '2026-07-23'),
(30, 23, 'B00023-1', 'Layanan Referensi', 'Tersedia', '2026-08-24'),
(31, 23, 'B00023-2', 'Layanan Referensi', 'Tersedia', '2026-08-24'),
(32, 24, 'B00024-1', 'Koleksi Deposit Sumut', 'Tersedia', '2026-01-25'),
(33, 25, 'B00025-1', 'Layanan Anak', 'Tersedia', '2026-02-26'),
(34, 25, 'B00025-2', 'Layanan Anak', 'Tersedia', '2026-02-26'),
(35, 26, 'B00026-1', 'Koleksi Deposit Sumut', 'Tersedia', '2026-03-27');

UPDATE `koleksi` SET `status`='Rusak' WHERE `barcode`='B00020-1';

-- Data awal: transaksi sirkulasi
INSERT INTO `transaksi` (`id_transaksi`, `id_anggota`, `id_koleksi`, `id_petugas`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `tanggal_kembali`, `jumlah_perpanjang`, `denda`, `status`) VALUES
(1, 1, 18, 1, '2026-08-03', '2026-08-10', '2026-08-09', 0, 0, 'Dikembalikan'),
(2, 2, 1, 1, '2026-08-05', '2026-08-12', '2026-08-14', 0, 1000, 'Dikembalikan'),
(3, 3, 8, 1, '2026-08-11', '2026-08-18', '2026-08-17', 0, 0, 'Dikembalikan'),
(4, 4, 15, 1, '2026-08-18', '2026-08-25', '2026-08-25', 0, 0, 'Dikembalikan'),
(5, 5, 25, 1, '2026-08-20', '2026-09-03', '2026-09-05', 1, 1000, 'Dikembalikan'),
(6, 2, 32, 1, '2026-09-02', '2026-09-09', '2026-09-08', 0, 0, 'Dikembalikan'),
(7, 1, 3, 1, '2026-09-07', '2026-09-14', '2026-09-13', 0, 0, 'Dikembalikan'),
(8, 7, 11, 1, '2026-09-14', '2026-09-21', '2026-09-30', 0, 4500, 'Dikembalikan'),
(9, 3, 18, 1, '2026-09-16', '2026-09-23', '2026-09-22', 0, 0, 'Dikembalikan'),
(10, 8, 22, 1, '2026-09-21', '2026-09-28', '2026-09-27', 0, 0, 'Dikembalikan'),
(11, 4, 2, 1, '2026-10-01', '2026-10-08', '2026-10-07', 0, 0, 'Dikembalikan'),
(12, 5, 28, 1, '2026-10-05', '2026-10-19', '2026-10-18', 1, 0, 'Dikembalikan'),
(13, 1, 19, 1, '2026-10-19', '2026-10-26', NULL, 0, 0, 'Dipinjam'),
(14, 2, 5, 1, '2026-10-24', '2026-10-31', NULL, 0, 0, 'Dipinjam'),
(15, 3, 21, 1, '2026-10-27', '2026-11-03', NULL, 0, 0, 'Dipinjam'),
(16, 4, 13, 1, '2026-10-28', '2026-11-04', NULL, 0, 0, 'Dipinjam');

-- Data awal: tiket_insiden (keluhan / permintaan layanan pemustaka)
INSERT INTO `tiket_insiden` (`id_tiket`, `nomor_tiket`, `id_pelapor`, `id_admin`, `jenis`, `kategori`, `urgensi`, `deskripsi`, `kontak`, `status`, `penanganan`, `tanggal_diproses`, `tanggal_selesai`, `rating`, `ulasan`, `tanggal_dibuat`) VALUES
(1, 'TK-0001', 3, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'Denda keterlambatan yang tertera tidak sesuai dengan tanggal pengembalian.', 'siti.siregar@usu.ac.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-03-02 15:25:00', '2026-03-03 22:42:55.363817', 5, 'Penanganan cepat dan jelas.', '2026-03-02 12:25:00'),
(2, 'TK-0002', 4, 1, 'Permintaan Layanan', 'Login & Sesi Akses', 'Sedang', 'Lupa kata sandi akun pemustaka dan tidak bisa masuk ke sistem.', 'budi.hasibuan@gmail.com', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-03-08 18:50:00', '2026-03-12 11:10:36.200195', NULL, NULL, '2026-03-08 15:50:00'),
(3, 'TK-0003', 5, 1, 'Permintaan Layanan', 'Pencarian Katalog / OPAC', 'Rendah', 'Mohon ditambahkan filter pencarian berdasarkan tahun terbit.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-03-11 15:25:00', '2026-03-19 04:33:46.456296', 5, 'Penanganan cepat dan jelas.', '2026-03-11 11:25:00'),
(4, 'TK-0004', 4, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'budi.hasibuan@gmail.com', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-03-14 15:25:00', '2026-03-24 01:26:51.918715', 5, 'Penanganan cepat dan jelas.', '2026-03-14 10:25:00'),
(5, 'TK-0005', 4, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'Buku yang sudah dikembalikan masih tercatat dipinjam pada akun saya.', 'budi.hasibuan@gmail.com', 'Diproses', NULL, '2026-03-18 13:40:00', NULL, NULL, NULL, '2026-03-18 09:40:00'),
(6, 'TK-0006', 3, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'siti.siregar@usu.ac.id', 'Diproses', NULL, '2026-03-27 15:00:00', NULL, NULL, NULL, '2026-03-27 11:00:00'),
(7, 'TK-0007', 4, 1, 'Permintaan Layanan', 'Login & Sesi Akses', 'Sedang', 'Lupa kata sandi akun pemustaka dan tidak bisa masuk ke sistem.', 'budi.hasibuan@gmail.com', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-04-03 13:10:00', '2026-04-07 08:21:10.753881', 3, 'Sudah selesai, tetapi agak lama.', '2026-04-03 12:10:00'),
(8, 'TK-0008', 4, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'budi.hasibuan@gmail.com', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-04-12 14:25:00', '2026-04-21 04:13:02.680900', 2, 'Terlalu lama ditangani.', '2026-04-12 08:25:00'),
(9, 'TK-0009', 3, 1, 'Permintaan Layanan', 'Sirkulasi & Peminjaman Buku', 'Rendah', 'Permintaan perpanjangan peminjaman karena buku masih diperlukan.', 'siti.siregar@usu.ac.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-04-21 18:40:00', '2026-05-02 06:35:05.083149', 5, 'Penanganan cepat dan jelas.', '2026-04-21 15:40:00'),
(10, 'TK-0010', 3, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'Buku yang sudah dikembalikan masih tercatat dipinjam pada akun saya.', 'siti.siregar@usu.ac.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-04-22 19:25:00', '2026-04-24 13:59:40.981400', 5, 'Penanganan cepat dan jelas.', '2026-04-22 13:25:00'),
(11, 'TK-0011', 3, 1, 'Permintaan Layanan', 'Login & Sesi Akses', 'Sedang', 'Lupa kata sandi akun pemustaka dan tidak bisa masuk ke sistem.', 'siti.siregar@usu.ac.id', 'Diproses', NULL, '2026-04-23 17:50:00', NULL, NULL, NULL, '2026-04-23 14:50:00'),
(12, 'TK-0012', 5, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'rahmat.lubis@sumutprov.go.id', 'Diproses', NULL, '2026-04-24 18:25:00', NULL, NULL, NULL, '2026-04-24 15:25:00'),
(13, 'TK-0013', 3, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'Denda keterlambatan yang tertera tidak sesuai dengan tanggal pengembalian.', 'siti.siregar@usu.ac.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-05-04 17:25:00', '2026-05-05 17:54:10.216706', 5, 'Penanganan cepat dan jelas.', '2026-05-04 13:25:00'),
(14, 'TK-0014', 3, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Sedang', 'Pencarian dengan kata kunci pengarang tidak menampilkan buku yang seharusnya ada.', 'siti.siregar@usu.ac.id', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-05-06 13:00:00', '2026-05-10 17:13:21.253927', 5, 'Penanganan cepat dan jelas.', '2026-05-06 11:00:00'),
(15, 'TK-0015', 5, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-05-08 17:40:00', '2026-05-16 23:08:20.299391', 5, 'Penanganan cepat dan jelas.', '2026-05-08 14:40:00'),
(16, 'TK-0016', 5, 1, 'Permintaan Layanan', 'Sirkulasi & Peminjaman Buku', 'Rendah', 'Permintaan perpanjangan peminjaman karena buku masih diperlukan.', 'rahmat.lubis@sumutprov.go.id', 'Diproses', NULL, '2026-05-09 09:00:00', NULL, NULL, NULL, '2026-05-09 08:00:00'),
(17, 'TK-0017', 5, 1, 'Gangguan', 'Login & Sesi Akses', 'Tinggi', 'Tidak dapat login ke OPAC; muncul pesan sesi telah berakhir padahal sandi sudah benar.', 'rahmat.lubis@sumutprov.go.id', 'Diproses', NULL, '2026-05-11 16:00:00', NULL, NULL, NULL, '2026-05-11 10:00:00'),
(18, 'TK-0018', 4, 1, 'Gangguan', 'Login & Sesi Akses', 'Tinggi', 'Tidak dapat login ke OPAC; muncul pesan sesi telah berakhir padahal sandi sudah benar.', 'budi.hasibuan@gmail.com', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-06-03 19:50:00', '2026-06-04 11:43:44.781133', 5, 'Penanganan cepat dan jelas.', '2026-06-03 15:50:00'),
(19, 'TK-0019', 4, 1, 'Permintaan Layanan', 'Sirkulasi & Peminjaman Buku', 'Rendah', 'Permintaan perpanjangan peminjaman karena buku masih diperlukan.', 'budi.hasibuan@gmail.com', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-06-08 19:10:00', '2026-06-15 03:57:03.521675', 5, 'Penanganan cepat dan jelas.', '2026-06-08 13:10:00'),
(20, 'TK-0020', 3, 1, 'Gangguan', 'Login & Sesi Akses', 'Tinggi', 'Tidak dapat login ke OPAC; muncul pesan sesi telah berakhir padahal sandi sudah benar.', 'siti.siregar@usu.ac.id', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-06-09 09:25:00', '2026-06-09 19:02:21.186893', 4, 'Cukup baik.', '2026-06-09 08:25:00'),
(21, 'TK-0021', 4, 1, 'Permintaan Layanan', 'Lainnya / Fasilitas Fisik', 'Rendah', 'Mohon informasi jam layanan pada hari libur nasional.', 'budi.hasibuan@gmail.com', 'Selesai', 'Petugas telah menindaklanjuti laporan dan menyampaikan hasilnya kepada pemustaka.', '2026-06-10 11:10:00', '2026-06-16 07:05:45.179568', 2, 'Terlalu lama ditangani.', '2026-06-10 08:10:00'),
(22, 'TK-0022', 3, 1, 'Permintaan Layanan', 'Lainnya / Fasilitas Fisik', 'Rendah', 'Mohon informasi jam layanan pada hari libur nasional.', 'siti.siregar@usu.ac.id', 'Selesai', 'Petugas telah menindaklanjuti laporan dan menyampaikan hasilnya kepada pemustaka.', '2026-06-17 21:25:00', '2026-06-27 20:13:24.886960', NULL, NULL, '2026-06-17 15:25:00'),
(23, 'TK-0023', 4, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'Denda keterlambatan yang tertera tidak sesuai dengan tanggal pengembalian.', 'budi.hasibuan@gmail.com', 'Diproses', NULL, '2026-06-19 13:10:00', NULL, NULL, NULL, '2026-06-19 08:10:00'),
(24, 'TK-0024', 5, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'Buku yang sudah dikembalikan masih tercatat dipinjam pada akun saya.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-07-01 19:40:00', '2026-07-03 11:40:29.788891', 5, 'Penanganan cepat dan jelas.', '2026-07-01 15:40:00'),
(25, 'TK-0025', 4, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'budi.hasibuan@gmail.com', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-07-02 13:00:00', '2026-07-08 15:15:15.990946', 3, 'Sudah selesai, tetapi agak lama.', '2026-07-02 10:00:00'),
(26, 'TK-0026', 3, 1, 'Permintaan Layanan', 'Login & Sesi Akses', 'Sedang', 'Lupa kata sandi akun pemustaka dan tidak bisa masuk ke sistem.', 'siti.siregar@usu.ac.id', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-07-15 12:40:00', '2026-07-17 20:25:12.862345', 5, 'Penanganan cepat dan jelas.', '2026-07-15 09:40:00'),
(27, 'TK-0027', 5, 1, 'Permintaan Layanan', 'Sirkulasi & Peminjaman Buku', 'Rendah', 'Permintaan perpanjangan peminjaman karena buku masih diperlukan.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-07-21 13:50:00', '2026-07-27 04:08:43.051893', 5, 'Penanganan cepat dan jelas.', '2026-07-21 11:50:00'),
(28, 'TK-0028', 3, 1, 'Permintaan Layanan', 'Lainnya / Fasilitas Fisik', 'Rendah', 'Mohon informasi jam layanan pada hari libur nasional.', 'siti.siregar@usu.ac.id', 'Diproses', NULL, '2026-07-24 16:00:00', NULL, NULL, NULL, '2026-07-24 14:00:00'),
(29, 'TK-0029', 5, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Sedang', 'Pencarian dengan kata kunci pengarang tidak menampilkan buku yang seharusnya ada.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-08-03 15:40:00', '2026-08-05 09:53:31.926489', NULL, NULL, '2026-08-03 13:40:00'),
(30, 'TK-0030', 3, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'Buku yang sudah dikembalikan masih tercatat dipinjam pada akun saya.', 'siti.siregar@usu.ac.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-08-06 19:50:00', '2026-08-11 06:51:52.552925', 4, 'Cukup baik.', '2026-08-06 14:50:00'),
(31, 'TK-0031', 4, 1, 'Gangguan', 'Pencarian Katalog / OPAC', 'Rendah', 'Hasil pencarian subjek naskah kuno tidak menampilkan gambar cover buku.', 'budi.hasibuan@gmail.com', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-08-08 14:25:00', '2026-08-11 05:18:13.530985', 5, 'Penanganan cepat dan jelas.', '2026-08-08 09:25:00'),
(32, 'TK-0032', 4, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'Buku yang sudah dikembalikan masih tercatat dipinjam pada akun saya.', 'budi.hasibuan@gmail.com', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-08-11 17:00:00', '2026-08-14 22:21:49.739819', 5, 'Penanganan cepat dan jelas.', '2026-08-11 15:00:00'),
(33, 'TK-0033', 4, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'Buku yang sudah dikembalikan masih tercatat dipinjam pada akun saya.', 'budi.hasibuan@gmail.com', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-08-13 11:00:00', '2026-08-15 12:33:07.991983', NULL, NULL, '2026-08-13 10:00:00'),
(34, 'TK-0034', 5, 1, 'Gangguan', 'Login & Sesi Akses', 'Sedang', 'Sesi login sering terputus ketika membuka beberapa tab katalog.', 'rahmat.lubis@sumutprov.go.id', 'Diproses', NULL, '2026-08-26 16:50:00', NULL, NULL, NULL, '2026-08-26 11:50:00'),
(35, 'TK-0035', 5, 1, 'Permintaan Layanan', 'Pencarian Katalog / OPAC', 'Rendah', 'Mohon ditambahkan filter pencarian berdasarkan tahun terbit.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-09-04 15:10:00', '2026-09-07 12:58:14.446557', 5, 'Penanganan cepat dan jelas.', '2026-09-04 13:10:00'),
(36, 'TK-0036', 3, 1, 'Gangguan', 'Login & Sesi Akses', 'Tinggi', 'Tidak dapat login ke OPAC; muncul pesan sesi telah berakhir padahal sandi sudah benar.', 'siti.siregar@usu.ac.id', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-09-05 17:00:00', '2026-09-06 03:07:46.110063', NULL, NULL, '2026-09-05 13:00:00'),
(37, 'TK-0037', 4, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'Denda keterlambatan yang tertera tidak sesuai dengan tanggal pengembalian.', 'budi.hasibuan@gmail.com', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-09-09 17:10:00', '2026-09-10 02:45:00.853341', 5, 'Penanganan cepat dan jelas.', '2026-09-09 15:10:00'),
(38, 'TK-0038', 4, 1, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'Denda keterlambatan yang tertera tidak sesuai dengan tanggal pengembalian.', 'budi.hasibuan@gmail.com', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-09-14 16:00:00', '2026-09-15 18:34:59.197809', 5, 'Penanganan cepat dan jelas.', '2026-09-14 15:00:00'),
(39, 'TK-0039', 5, 1, 'Permintaan Layanan', 'Sirkulasi & Peminjaman Buku', 'Rendah', 'Permintaan perpanjangan peminjaman karena buku masih diperlukan.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-09-18 18:40:00', '2026-09-29 03:12:06.138305', 4, 'Cukup baik.', '2026-09-18 12:40:00'),
(40, 'TK-0040', 5, 1, 'Gangguan', 'Login & Sesi Akses', 'Sedang', 'Sesi login sering terputus ketika membuka beberapa tab katalog.', 'rahmat.lubis@sumutprov.go.id', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-09-25 20:10:00', '2026-09-27 20:15:42.330470', 5, 'Penanganan cepat dan jelas.', '2026-09-25 14:10:00'),
(41, 'TK-0041', 4, 1, 'Gangguan', 'Login & Sesi Akses', 'Sedang', 'Sesi login sering terputus ketika membuka beberapa tab katalog.', 'budi.hasibuan@gmail.com', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-10-01 19:10:00', '2026-10-05 11:26:10.921387', 5, 'Penanganan cepat dan jelas.', '2026-10-01 15:10:00'),
(42, 'TK-0042', 4, 1, 'Gangguan', 'Login & Sesi Akses', 'Tinggi', 'Tidak dapat login ke OPAC; muncul pesan sesi telah berakhir padahal sandi sudah benar.', 'budi.hasibuan@gmail.com', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-10-03 13:25:00', '2026-10-03 18:59:29.788905', 5, 'Penanganan cepat dan jelas.', '2026-10-03 12:25:00'),
(43, 'TK-0043', 4, 1, 'Permintaan Layanan', 'Login & Sesi Akses', 'Sedang', 'Lupa kata sandi akun pemustaka dan tidak bisa masuk ke sistem.', 'budi.hasibuan@gmail.com', 'Diproses', NULL, '2026-10-22 12:50:00', NULL, NULL, NULL, '2026-10-22 08:50:00'),
(44, 'TK-0044', 3, NULL, 'Permintaan Layanan', 'Sirkulasi & Peminjaman Buku', 'Rendah', 'Permintaan perpanjangan peminjaman karena buku masih diperlukan.', 'siti.siregar@usu.ac.id', 'Baru', NULL, NULL, NULL, NULL, NULL, '2026-10-23 13:25:00'),
(45, 'TK-0045', 5, NULL, 'Permintaan Layanan', 'Pencarian Katalog / OPAC', 'Rendah', 'Mohon ditambahkan filter pencarian berdasarkan tahun terbit.', 'rahmat.lubis@sumutprov.go.id', 'Baru', NULL, NULL, NULL, NULL, NULL, '2026-10-24 12:00:00');

-- Data awal: tiket_riwayat
INSERT INTO `tiket_riwayat` (`id_tiket`, `id_user`, `status_lama`, `status_baru`, `catatan`, `created_at`) VALUES
(1, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-03-02 12:25:00'),
(1, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-03-02 15:25:00'),
(1, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-03-03 22:42:55.363817'),
(2, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-03-08 15:50:00'),
(2, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-03-08 18:50:00'),
(2, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-03-12 11:10:36.200195'),
(3, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-03-11 11:25:00'),
(3, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-03-11 15:25:00'),
(3, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-03-19 04:33:46.456296'),
(4, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-03-14 10:25:00'),
(4, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-03-14 15:25:00'),
(4, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-03-24 01:26:51.918715'),
(5, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-03-18 09:40:00'),
(5, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-03-18 13:40:00'),
(6, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-03-27 11:00:00'),
(6, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-03-27 15:00:00'),
(7, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-04-03 12:10:00'),
(7, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-04-03 13:10:00'),
(7, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-04-07 08:21:10.753881'),
(8, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-04-12 08:25:00'),
(8, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-04-12 14:25:00'),
(8, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-04-21 04:13:02.680900'),
(9, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-04-21 15:40:00'),
(9, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-04-21 18:40:00'),
(9, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-05-02 06:35:05.083149'),
(10, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-04-22 13:25:00'),
(10, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-04-22 19:25:00'),
(10, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-04-24 13:59:40.981400'),
(11, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-04-23 14:50:00'),
(11, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-04-23 17:50:00'),
(12, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-04-24 15:25:00'),
(12, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-04-24 18:25:00'),
(13, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-05-04 13:25:00'),
(13, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-05-04 17:25:00'),
(13, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-05-05 17:54:10.216706'),
(14, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-05-06 11:00:00'),
(14, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-05-06 13:00:00'),
(14, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-05-10 17:13:21.253927'),
(15, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-05-08 14:40:00'),
(15, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-05-08 17:40:00'),
(15, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-05-16 23:08:20.299391'),
(16, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-05-09 08:00:00'),
(16, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-05-09 09:00:00'),
(17, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-05-11 10:00:00'),
(17, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-05-11 16:00:00'),
(18, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-06-03 15:50:00'),
(18, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-06-03 19:50:00'),
(18, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-06-04 11:43:44.781133'),
(19, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-06-08 13:10:00'),
(19, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-06-08 19:10:00'),
(19, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-06-15 03:57:03.521675'),
(20, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-06-09 08:25:00'),
(20, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-06-09 09:25:00'),
(20, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-06-09 19:02:21.186893'),
(21, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-06-10 08:10:00'),
(21, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-06-10 11:10:00'),
(21, 1, 'Diproses', 'Selesai', 'Petugas telah menindaklanjuti laporan dan menyampaikan hasilnya kepada pemustaka.', '2026-06-16 07:05:45.179568'),
(22, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-06-17 15:25:00'),
(22, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-06-17 21:25:00'),
(22, 1, 'Diproses', 'Selesai', 'Petugas telah menindaklanjuti laporan dan menyampaikan hasilnya kepada pemustaka.', '2026-06-27 20:13:24.886960'),
(23, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-06-19 08:10:00'),
(23, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-06-19 13:10:00'),
(24, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-07-01 15:40:00'),
(24, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-07-01 19:40:00'),
(24, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-07-03 11:40:29.788891'),
(25, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-07-02 10:00:00'),
(25, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-07-02 13:00:00'),
(25, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-07-08 15:15:15.990946'),
(26, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-07-15 09:40:00'),
(26, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-07-15 12:40:00'),
(26, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-07-17 20:25:12.862345'),
(27, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-07-21 11:50:00'),
(27, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-07-21 13:50:00'),
(27, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-07-27 04:08:43.051893'),
(28, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-07-24 14:00:00'),
(28, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-07-24 16:00:00'),
(29, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-08-03 13:40:00'),
(29, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-08-03 15:40:00'),
(29, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-08-05 09:53:31.926489'),
(30, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-08-06 14:50:00'),
(30, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-08-06 19:50:00'),
(30, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-08-11 06:51:52.552925'),
(31, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-08-08 09:25:00'),
(31, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-08-08 14:25:00'),
(31, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-08-11 05:18:13.530985'),
(32, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-08-11 15:00:00'),
(32, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-08-11 17:00:00'),
(32, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-08-14 22:21:49.739819'),
(33, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-08-13 10:00:00'),
(33, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-08-13 11:00:00'),
(33, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-08-15 12:33:07.991983'),
(34, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-08-26 11:50:00'),
(34, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-08-26 16:50:00'),
(35, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-09-04 13:10:00'),
(35, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-09-04 15:10:00'),
(35, 1, 'Diproses', 'Selesai', 'Data bibliografi diperiksa dan diperbarui; cover diunggah ulang sehingga hasil pencarian tampil sesuai.', '2026-09-07 12:58:14.446557'),
(36, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-09-05 13:00:00'),
(36, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-09-05 17:00:00'),
(36, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-09-06 03:07:46.110063'),
(37, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-09-09 15:10:00'),
(37, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-09-09 17:10:00'),
(37, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-09-10 02:45:00.853341'),
(38, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-09-14 15:00:00'),
(38, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-09-14 16:00:00'),
(38, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-09-15 18:34:59.197809'),
(39, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-09-18 12:40:00'),
(39, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-09-18 18:40:00'),
(39, 1, 'Diproses', 'Selesai', 'Transaksi diverifikasi oleh petugas sirkulasi dan status koleksi diperbaiki pada sistem.', '2026-09-29 03:12:06.138305'),
(40, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-09-25 14:10:00'),
(40, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-09-25 20:10:00'),
(40, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-09-27 20:15:42.330470'),
(41, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-01 15:10:00'),
(41, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-10-01 19:10:00'),
(41, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-10-05 11:26:10.921387'),
(42, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-03 12:25:00'),
(42, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-10-03 13:25:00'),
(42, 1, 'Diproses', 'Selesai', 'Sesi akun direset, cache peramban dibersihkan, dan pemustaka diminta login ulang. Login kembali normal.', '2026-10-03 18:59:29.788905'),
(43, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-22 08:50:00'),
(43, 1, 'Baru', 'Diproses', 'Tiket diperiksa dan mulai ditangani oleh petugas.', '2026-10-22 12:50:00'),
(44, 3, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-23 13:25:00'),
(45, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-24 12:00:00');

-- Data awal: masalah & basis_pengetahuan
INSERT INTO `masalah` (`id_masalah`, `id_tiket`, `id_admin`, `akar_masalah`, `tindakan_perbaikan`, `langkah_pencegahan`, `status`, `tanggal_analisis`) VALUES
(1, 2, 1, 'Durasi session timeout PHP (15 menit) lebih singkat dari kebutuhan layanan sehingga sesi pemustaka berakhir lebih cepat.', 'Naikkan session.gc_maxlifetime menjadi 3600 detik dan sesuaikan konfigurasi web server.', 'Pantau konfigurasi sesi setiap pembaruan server dan sosialisasikan cara membersihkan cache peramban.', 'Selesai', '2026-03-08 20:50:00'),
(2, 7, 1, 'Status koleksi pada transaksi pengembalian tidak tersinkron karena proses pembaruan status terhenti saat jaringan terputus.', 'Perbaiki status koleksi secara manual dan jalankan verifikasi data transaksi.', 'Lakukan pengecekan harian transaksi yang statusnya tidak sesuai dan catat gangguan jaringan.', 'Selesai', '2026-04-03 15:10:00'),
(3, 43, 1, 'Dugaan awal: indeks pencarian katalog tidak diperbarui setelah entri bibliografi baru.', '', '', 'Dianalisis', '2026-10-22 15:50:00');

INSERT INTO `basis_pengetahuan` (`id_solusi`, `id_masalah`, `judul_solusi`, `deskripsi_solusi`) VALUES
(1, 1, 'Optimalisasi Durasi Sesi dan Cache Peramban Pemustaka', 'Tingkatkan gc_maxlifetime sesi PHP menjadi 3600 detik, lalu minta pemustaka membersihkan cache peramban dan login ulang.'),
(2, 2, 'Verifikasi Sinkronisasi Status Koleksi Pascapengembalian', 'Cek transaksi pengembalian pada menu Sirkulasi, perbaiki status eksemplar menjadi Tersedia, dan catat penyebab gangguan jaringan.');

-- Data awal: pengaturan
INSERT INTO `pengaturan` (`kunci`,`nilai`,`keterangan`) VALUES
('nama_perpustakaan','Dinas Perpustakaan dan Arsip Provinsi Sumatera Utara','Nama perpustakaan'),
('alamat_perpustakaan','Jl. Brigjend Katamso No. 45 Kp. Baru, Medan','Alamat perpustakaan'),
('lama_pinjam_hari','7','Lama peminjaman (hari)'),
('maks_pinjam','3','Maksimal buku dipinjam per anggota'),
('maks_perpanjang','2','Maksimal perpanjangan per transaksi'),
('denda_per_hari','500','Denda keterlambatan per hari (Rp)'),
('target_tinggi_jam','24','Target penyelesaian tiket urgensi Tinggi (jam)'),
('target_sedang_jam','72','Target penyelesaian tiket urgensi Sedang (jam)'),
('target_rendah_jam','168','Target penyelesaian tiket urgensi Rendah (jam)');

-- Data awal: log_aktivitas
INSERT INTO `log_aktivitas` (`id_user`,`aksi`,`modul`,`id_referensi`,`keterangan`,`ip_address`,`created_at`) VALUES
(1, 'LOGIN', 'auth', NULL, 'Login berhasil: admin', '127.0.0.1', '2026-10-29 08:01:12'),
(3, 'TIKET_BARU', 'tiket', 45, 'Pengajuan tiket baru', '127.0.0.1', '2026-10-28 10:12:00'),
(1, 'PINJAM', 'sirkulasi', 16, 'Peminjaman koleksi', '127.0.0.1', '2026-10-28 09:40:00'),
(2, 'LOGIN', 'auth', NULL, 'Login berhasil: pimpinan', '127.0.0.1', '2026-10-29 09:15:30');


-- ---------------------------------------------------------------------
-- Agregasi awal dashboard_manfaat (rumus sama dengan kelas DashboardManfaat)
-- ---------------------------------------------------------------------
INSERT INTO `dashboard_manfaat` (`nama_indikator`, `nilai_capaian`, `periode`)
SELECT 'Tingkat Penyelesaian Insiden',
       ROUND(100 * SUM(t.status = 'Selesai') / COUNT(*), 2),
       DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01')
FROM tiket_insiden t GROUP BY DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01');

INSERT INTO `dashboard_manfaat` (`nama_indikator`, `nilai_capaian`, `periode`)
SELECT 'Rata-rata Waktu Penanganan (jam)',
       ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.tanggal_dibuat, t.tanggal_selesai)) / 60, 2),
       DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01')
FROM tiket_insiden t WHERE t.status = 'Selesai' AND t.tanggal_selesai IS NOT NULL
GROUP BY DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01');

INSERT INTO `dashboard_manfaat` (`nama_indikator`, `nilai_capaian`, `periode`)
SELECT 'Kepuasan Pemustaka',
       ROUND(AVG(t.rating), 2),
       DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01')
FROM tiket_insiden t WHERE t.rating IS NOT NULL
GROUP BY DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01');

INSERT INTO `dashboard_manfaat` (`nama_indikator`, `nilai_capaian`, `periode`)
SELECT 'Capaian Target Layanan',
       ROUND(100 * SUM(TIMESTAMPDIFF(MINUTE, t.tanggal_dibuat, t.tanggal_selesai) <=
            60 * CAST(p.nilai AS UNSIGNED)) / COUNT(*), 2),
       DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01')
FROM tiket_insiden t
JOIN pengaturan p ON p.kunci = CONCAT('target_', LOWER(t.urgensi), '_jam')
WHERE t.status = 'Selesai' AND t.tanggal_selesai IS NOT NULL
GROUP BY DATE_FORMAT(t.tanggal_dibuat, '%Y-%m-01');

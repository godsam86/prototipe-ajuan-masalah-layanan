-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2026 at 01:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inlislite`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `id_anggota` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `nomor_anggota` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_anggota` enum('Umum','Pelajar','Mahasiswa','Pegawai') NOT NULL DEFAULT 'Umum',
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tanggal_daftar` date NOT NULL,
  `masa_berlaku` date NOT NULL,
  `status` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id_anggota`, `id_user`, `nomor_anggota`, `nama`, `jenis_anggota`, `jenis_kelamin`, `alamat`, `telepon`, `email`, `tanggal_daftar`, `masa_berlaku`, `status`, `created_at`, `updated_at`) VALUES
(2, 4, 'SU-2026-00002', 'asista', 'Umum', 'Perempuan', NULL, NULL, 'asista87@gmail.com', '2026-10-31', '2031-10-31', 'Aktif', '2026-10-31 19:02:38', '2026-10-31 19:04:15'),
(3, 6, 'SU-2026-00003', 'Fika', 'Umum', 'Perempuan', NULL, NULL, NULL, '2026-10-31', '2031-10-31', 'Aktif', '2026-10-31 19:03:00', '2026-10-31 19:05:14'),
(4, 5, 'SU-2026-00004', 'Feni', 'Umum', 'Perempuan', NULL, NULL, NULL, '2026-10-31', '2031-10-31', 'Aktif', '2026-10-31 19:03:12', '2026-10-31 19:04:46'),
(5, 7, 'SU-2026-00005', 'Maya', 'Umum', 'Perempuan', NULL, NULL, NULL, '2026-10-31', '2031-10-31', 'Aktif', '2026-10-31 19:03:29', '2026-10-31 19:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `basis_pengetahuan`
--

CREATE TABLE `basis_pengetahuan` (
  `id_solusi` int(10) UNSIGNED NOT NULL,
  `id_masalah` int(10) UNSIGNED NOT NULL,
  `judul_solusi` varchar(150) NOT NULL,
  `deskripsi_solusi` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bibliografi`
--

CREATE TABLE `bibliografi` (
  `id_bibliografi` int(10) UNSIGNED NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pengarang` varchar(150) NOT NULL,
  `penerbit` varchar(150) DEFAULT NULL,
  `kota_terbit` varchar(80) DEFAULT NULL,
  `tahun_terbit` smallint(5) UNSIGNED DEFAULT NULL,
  `isbn` varchar(30) DEFAULT NULL,
  `subjek` varchar(255) DEFAULT NULL,
  `nomor_panggil` varchar(60) DEFAULT NULL,
  `jenis_bahan` varchar(40) NOT NULL DEFAULT 'Monograf',
  `deskripsi_fisik` varchar(120) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bibliografi`
--

INSERT INTO `bibliografi` (`id_bibliografi`, `judul`, `pengarang`, `penerbit`, `kota_terbit`, `tahun_terbit`, `isbn`, `subjek`, `nomor_panggil`, `jenis_bahan`, `deskripsi_fisik`, `cover`, `created_at`, `updated_at`) VALUES
(1, 'Teori peluang', 'Prof. Dr. Asmin, M.Pd', 'Perdana Publishing', 'Medan', 2023, '978-623-411-098-2', 'Teori Peluang; Matematika; Statistika', '519.2 ASM t', 'Monograf', 'xi, 422 halaman : Ilustrasi ; 23 cm.', 'assets/images/covers/teori_peluang.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(2, 'Pengantar Riset Operasi', 'Parapat Gultom', 'Perdana Publishing', 'Medan', 2024, '978-623-411-125-5', 'Riset Operasi', '658.403 4 GUL p', 'Monograf', 'xxi, 134 halaman : Ilustrasi ; 20 cm Buku', 'assets/images/covers/12831.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(3, 'Personal branding Perempuan Dalam Berbagai Aktifitas', 'Nurbani', 'USU Press', 'Medan', 2022, '978-602-465-420-1', 'Personal branding', '658.8 NUR p', 'Monograf', 'ii, 69 Halaman : Ilustrasi ; 25 cm Buku', 'assets/images/covers/114949.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(4, 'Sorgum : Alternatif pangan masa depan', 'Ameilia Zuliyanti Siregar', 'USU Press', 'Medan', 2022, '978-602-465-419-5', 'sorgva', '633.62 SIR s', 'Monograf', 'iv,72 Halaman : Ilustrasi ; 25 cm Buku', 'assets/images/covers/114885.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(5, 'Plants vs zombies komic sains robot 5 : Pulau kitchen', 'Xiao Jiang Nan; pengalih bahasa Wenti Violi', 'Gramedia Populer', 'Jakarta', 2021, '978-602-06-4521-3', 'Sains - Komik; Komputer & Robotika', '500 XIA p', 'Monograf', '160 hlm. : ilus. ; 24 cm.', 'assets/images/covers/122149.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(6, 'Detektif sains anak - CSI Volume 7 : Rencana terakhir si pelaku', 'Ko Hee-Jung; illustrator Seo Yong-Nam', 'BIP (Bhuana Ilmu Populer)', 'Jakarta', 2020, '978-623-216-981-4', 'Detektif Sains; Penyelidikan Ilmiah', '507 KO d', 'Monograf', '172 hlm. : ilus. ; 24 cm.', 'assets/images/covers/120016.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(7, 'Komik sains plants vs zombies : Tumbuhan', 'Xiao Jiang Nan, Penerjemah Adistia Kartika Rini', 'M&C!', 'Jakarta', 2021, '978-602-480-879-1', 'Botani; Tumbuhan - Komik', '580 XIA k', 'Monograf', '152 hlm. ; 24 cm.', 'assets/images/covers/122145.png', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(8, 'PLANTS VS ZOMBIES : rahasia tersembunyi di dunia', 'Xiao Jiang Nan', 'Gramedia', 'Jakarta', 2019, '978-602-06-2311-2', 'Sains Populer; Fenomena Dunia', '500 XIA r', 'Monograf', '148 hlm. ; 24 cm.', 'assets/images/covers/117010.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(9, 'Keluarga super irit belajar sains : Coding - Taman entry', 'Storybox; Ilustrations, Ryu, Soo-Hyung', 'Elex Media Komputindo', 'Jakarta', 2022, '978-623-00-3011-5', 'Pemrograman Komputer; Coding Anak', '005.1 STO k', 'Monograf', '180 hlm. : ilus. ; 24 cm.', 'assets/images/covers/124350.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(10, 'Di Sini Ada Setan : Sekolah Berdarah', 'Justin C', 'GagasMedia', 'Jakarta', 2019, '978-979-780-945-2', 'Novel Horor; Cerita Misteri', '813 JUS d', 'Monograf', '212 hlm. ; 19 cm.', 'assets/images/covers/117330.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(11, 'Mukjizat Para Nabi : nabi yunus, nabi zakaria, nabi yahya, nabi isa, nabi muhammad', 'Hafez Achda', 'Diva Press', 'Yogyakarta', 2019, '978-602-391-715-0', 'Kisah Nabi; Agama Islam', '297.24 HAF m', 'Monograf', '164 hlm. : ilus. ; 20 cm.', 'assets/images/covers/115792.png', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(12, 'Plants vs Zombies : Komik Dinosaurus : Dinosaurus dan Kerajaan lautan pasir', 'Xiao Jiang Nan; Pengalih bahasa, Wenti', 'Gramedia Pustaka Utama', 'Jakarta', 2021, '978-602-06-5120-7', 'Dinosaurus; Sains Komik', '567.9 XIA p', 'Monograf', '168 hlm. ; 24 cm.', 'assets/images/covers/124355.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(13, 'Plants vs zombies komic dinosaurus : Penyerbuan ke kota dinosaurus', 'Xiao, Jiang Nan', 'Gramedia Pustaka Utama', 'Jakarta', 2020, '978-602-06-4411-7', 'Dinosaurus; Paleontologi Komik', '567.9 XIA k', 'Monograf', '164 hlm. ; 24 cm.', 'assets/images/covers/122319.png', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(14, 'Ayahku bukan pembohong', 'Tere Liye', 'Gramedia Pustaka Utama', 'Jakarta', 2011, '978-979-22-6805-8', 'Novel Indonesia; Fiksi', '813 TER a', 'Monograf', '299 hlm. ; 20 cm.', 'assets/images/covers/106104.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(15, 'Atomic habits : Perubahan kecil yang memberikan hasil yang luar biasa', 'James Clear; alih bahasa Alex Tri Kantjono Widodo', 'Gramedia Pustaka Utama', 'Jakarta', 2020, '978-602-06-3317-6', 'Pengembangan Diri; Motivasi; Psikologi Praktis', '158.1 CLE a', 'Monograf', 'xxv, 351 hlm. ; 23 cm.', 'assets/images/covers/121849.png', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(16, 'Plants vs zombies komic dinosaurus : Dinosaurus di pulau terpencil', 'Xiao, Jiang Nan', 'Gramedia Pustaka Utama', 'Jakarta', 2021, '978-602-06-4780-4', 'Dinosaurus; Petualangan Komik', '567.9 XIA d', 'Monograf', '160 hlm. ; 24 cm.', 'assets/images/covers/122146.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(17, 'Papua Berkisah', 'Swastika Nohara', 'Gramedia Pustaka Utama', 'Jakarta', 2018, '978-602-03-8201-9', 'Kebudayaan Papua; Antropologi; Catatan Perjalanan', '959.87 SWA p', 'Monograf', 'xiv, 240 hlm. : ilus. ; 21 cm.', 'assets/images/covers/98780.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(18, 'ANTROPOLOGI & PEMBANGUNAN INDONESIA', 'Koentjaraningrat', 'Rajawali Pers', 'Jakarta', 2017, '978-979-421-081-8', 'Antropologi Sosial; Pembangunan Nasional', '301 KOE a', 'Monograf', 'xviii, 342 hlm. ; 23 cm.', 'assets/images/covers/92773.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(19, 'Manajemen keselamatan kerja & kesehatan kerja', 'Sutrisno & Kusmawan Ruswandi', 'Pustaka Setia', 'Bandung', 2017, '978-979-076-634-1', 'K3; Keselamatan Kerja; Manajemen Industri', '658.38 SUT m', 'Monograf', '260 hlm. ; 24 cm.', 'assets/images/covers/103572.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(20, 'MENGHANCURKAN BENTENG-BENTENG IBLIS', 'C. Peter Wagner', 'Immanuel', 'Jakarta', 2016, '978-979-8871-34-4', 'Teologi; Spiritualitas Agama', '230 WAG m', 'Monograf', '210 hlm. ; 21 cm.', 'assets/images/covers/100374.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(21, 'JEJAK PEMBUNUHAN BERANTAI', 'Hermawan Aksan', 'Grasindo', 'Jakarta', 2021, '978-602-05-2415-3', 'Kriminologi; Investigasi Kriminal', '364.15 AKS j', 'Monograf', '216 hlm. ; 20 cm.', 'assets/images/covers/124493.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(22, 'PENDIDIKAN ISLAM TRADISI DAN MODERNISASI DITENGAH TANTANGAN MILENIUM III', 'Azyumardi Azra', 'Kencana', 'Jakarta', 2018, '978-602-422-215-4', 'Pendidikan Islam; Modernisasi', '297.77 AZR p', 'Monograf', 'xii, 288 hlm. ; 21 cm.', 'assets/images/covers/101179.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(23, 'BINTANG', 'Tere Liye', 'Gramedia Pustaka Utama', 'Jakarta', 2017, '978-602-03-5117-6', 'Novel Petualangan; Fantasi Indonesia', '813 TER b', 'Monograf', '392 hlm. ; 20 cm.', 'assets/images/covers/112776.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(24, 'Kritik Sastra Indonesia Modern', 'Rachmat Djoko Pradopo', 'Gama Media', 'Yogyakarta', 2002, '979-9552-05-2', 'Kritik Sastra; Kesusastraan Indonesia', '810.9 RAC k', 'Monograf', 'xii, 488 hlm. ; 23 cm.', NULL, '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(25, 'Pedoman pelajaran tata hukum Indonesia', 'Kusumadi Pudjosewojo', 'Sinar Grafika', 'Jakarta', 2008, '979-8767-42-X', 'Tata Hukum Indonesia; Pengantar Ilmu Hukum', '340 PUJ p', 'Monograf', 'x, 198 hlm. ; 21 cm.', 'assets/images/covers/5483.jpg', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(26, 'Statistik ekonomi-keuangan sumatera utara = north sumatera financial statistics', 'Bank Indonesia', 'Bank Indonesia', 'Medan', 1988, '0126-1983', 'Statistik Ekonomi; Keuangan Daerah Sumatera Utara', '330.315 598 12 STA', 'Monograf', 'viii, 142 hlm. ; 28 cm.', NULL, '2026-10-31 16:17:04', '2026-10-31 16:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_manfaat`
--

CREATE TABLE `dashboard_manfaat` (
  `id_indikator` int(10) UNSIGNED NOT NULL,
  `nama_indikator` varchar(100) NOT NULL,
  `nilai_capaian` decimal(7,2) NOT NULL,
  `periode` date NOT NULL COMMENT 'tanggal awal bulan pelaporan',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dashboard_manfaat`
--

INSERT INTO `dashboard_manfaat` (`id_indikator`, `nama_indikator`, `nilai_capaian`, `periode`, `updated_at`) VALUES
(1, 'Tingkat Penyelesaian Insiden', 0.00, '2026-10-01', '2026-10-31 16:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_layanan`
--

CREATE TABLE `jenis_layanan` (
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `nama` varchar(60) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jenis_layanan`
--

INSERT INTO `jenis_layanan` (`id_jenis`, `nama`, `aktif`, `created_at`) VALUES
(1, 'Gangguan', 1, '2026-10-31 16:17:04'),
(2, 'Permintaan Layanan', 1, '2026-10-31 16:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_layanan`
--

CREATE TABLE `kategori_layanan` (
  `id_kategori` int(10) UNSIGNED NOT NULL,
  `nama` varchar(60) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_layanan`
--

INSERT INTO `kategori_layanan` (`id_kategori`, `nama`, `aktif`, `created_at`) VALUES
(1, 'Login & Sesi Akses', 1, '2026-10-31 16:17:04'),
(2, 'Pencarian Katalog / OPAC', 1, '2026-10-31 16:17:04'),
(3, 'Sirkulasi & Peminjaman Buku', 1, '2026-10-31 16:17:04'),
(4, 'Lainnya / Fasilitas Fisik', 1, '2026-10-31 16:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `koleksi`
--

CREATE TABLE `koleksi` (
  `id_koleksi` int(10) UNSIGNED NOT NULL,
  `id_bibliografi` int(10) UNSIGNED NOT NULL,
  `barcode` varchar(40) NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `status` enum('Tersedia','Dipinjam','Rusak','Hilang') NOT NULL DEFAULT 'Tersedia',
  `tanggal_pengadaan` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `koleksi`
--

INSERT INTO `koleksi` (`id_koleksi`, `id_bibliografi`, `barcode`, `lokasi`, `status`, `tanggal_pengadaan`, `created_at`, `updated_at`) VALUES
(1, 1, 'B00001-1', 'Koleksi Deposit Sumut', 'Tersedia', '2026-02-02', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(2, 2, 'B00002-1', 'Layanan Referensi', 'Tersedia', '2026-03-03', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(3, 3, 'B00003-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-04-04', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(4, 4, 'B00004-1', 'Layanan Anak', 'Tersedia', '2026-05-05', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(5, 5, 'B00005-1', 'Layanan Referensi', 'Tersedia', '2026-06-06', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(6, 5, 'B00005-2', 'Layanan Referensi', 'Tersedia', '2026-06-06', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(7, 6, 'B00006-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-07-07', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(8, 7, 'B00007-1', 'Layanan Anak', 'Tersedia', '2026-08-08', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(9, 7, 'B00007-2', 'Layanan Anak', 'Tersedia', '2026-08-08', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(10, 8, 'B00008-1', 'Layanan Referensi', 'Tersedia', '2026-01-09', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(11, 9, 'B00009-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-02-10', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(12, 9, 'B00009-2', 'Layanan Umum Lt. 2', 'Tersedia', '2026-02-10', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(13, 10, 'B00010-1', 'Layanan Anak', 'Tersedia', '2026-03-11', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(14, 11, 'B00011-1', 'Layanan Referensi', 'Tersedia', '2026-04-12', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(15, 12, 'B00012-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-05-13', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(16, 13, 'B00013-1', 'Layanan Anak', 'Tersedia', '2026-06-14', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(17, 14, 'B00014-1', 'Layanan Referensi', 'Tersedia', '2026-07-15', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(18, 15, 'B00015-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-08-16', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(19, 15, 'B00015-2', 'Layanan Umum Lt. 2', 'Tersedia', '2026-08-16', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(20, 15, 'B00015-3', 'Layanan Umum Lt. 2', 'Tersedia', '2026-08-16', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(21, 16, 'B00016-1', 'Layanan Anak', 'Tersedia', '2026-01-17', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(22, 17, 'B00017-1', 'Layanan Referensi', 'Tersedia', '2026-02-18', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(23, 18, 'B00018-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-03-19', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(24, 18, 'B00018-2', 'Layanan Umum Lt. 2', 'Tersedia', '2026-03-19', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(25, 19, 'B00019-1', 'Layanan Anak', 'Tersedia', '2026-04-20', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(26, 20, 'B00020-1', 'Layanan Referensi', 'Rusak', '2026-05-21', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(27, 21, 'B00021-1', 'Layanan Umum Lt. 2', 'Tersedia', '2026-06-22', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(28, 22, 'B00022-1', 'Layanan Anak', 'Tersedia', '2026-07-23', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(29, 22, 'B00022-2', 'Layanan Anak', 'Tersedia', '2026-07-23', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(30, 23, 'B00023-1', 'Layanan Referensi', 'Tersedia', '2026-08-24', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(31, 23, 'B00023-2', 'Layanan Referensi', 'Tersedia', '2026-08-24', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(32, 24, 'B00024-1', 'Koleksi Deposit Sumut', 'Tersedia', '2026-01-25', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(33, 25, 'B00025-1', 'Layanan Anak', 'Tersedia', '2026-02-26', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(34, 25, 'B00025-2', 'Layanan Anak', 'Tersedia', '2026-02-26', '2026-10-31 16:17:04', '2026-10-31 16:17:04'),
(35, 26, 'B00026-1', 'Koleksi Deposit Sumut', 'Tersedia', '2026-03-27', '2026-10-31 16:17:04', '2026-10-31 16:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_kinerja`
--

CREATE TABLE `laporan_kinerja` (
  `id_laporan` int(10) UNSIGNED NOT NULL,
  `id_admin` int(10) UNSIGNED DEFAULT NULL,
  `periode` date NOT NULL COMMENT 'tanggal awal bulan laporan',
  `isi_laporan` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` bigint(20) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `aksi` varchar(30) NOT NULL,
  `modul` varchar(30) NOT NULL,
  `id_referensi` int(10) UNSIGNED DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_user`, `aksi`, `modul`, `id_referensi`, `keterangan`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'LOGOUT', 'auth', NULL, 'Logout: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:17:24'),
(2, NULL, 'LOGIN_GAGAL', 'auth', NULL, 'Login gagal: eci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:17:49'),
(3, 1, 'LOGIN', 'auth', NULL, 'Login berhasil: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:17:58'),
(4, 1, 'TAMBAH', 'anggota', 1, 'Tambah anggota: SU-2026-00001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:18:52'),
(5, 1, 'TAMBAH', 'pengguna', 3, 'Tambah akun: eci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:19:21'),
(6, 1, 'LOGOUT', 'auth', NULL, 'Logout: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:19:26'),
(7, NULL, 'LOGIN', 'auth', NULL, 'Login berhasil: eci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:19:34'),
(8, NULL, 'LOGOUT', 'auth', NULL, 'Logout: eci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:20:08'),
(9, 1, 'LOGIN', 'auth', NULL, 'Login berhasil: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:20:15'),
(10, 1, 'LOGOUT', 'auth', NULL, 'Logout: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:21:39'),
(11, NULL, 'LOGIN', 'auth', NULL, 'Login berhasil: eci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:21:54'),
(12, NULL, 'TIKET_BARU', 'tiket', 1, 'Pengajuan TK-0001 (Sirkulasi & Peminjaman Buku, urgensi Tinggi)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:22:19'),
(13, NULL, 'LOGOUT', 'auth', NULL, 'Logout: eci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:22:24'),
(14, 1, 'LOGIN', 'auth', NULL, 'Login berhasil: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:22:30'),
(15, 1, 'UBAH', 'pengguna', 1, 'Ubah akun: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:23:08'),
(16, 1, 'LOGOUT', 'auth', NULL, 'Logout: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:23:42'),
(17, 2, 'LOGIN', 'auth', NULL, 'Login berhasil: pimpinan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 16:23:51'),
(18, 1, 'LOGIN_GAGAL', 'auth', NULL, 'Login gagal: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:01:42'),
(19, 1, 'LOGIN', 'auth', NULL, 'Login berhasil: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:01:49'),
(20, 1, 'TAMBAH', 'anggota', 2, 'Tambah anggota: SU-2026-00002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:02:38'),
(21, 1, 'HAPUS', 'anggota', 1, 'Hapus anggota: SU-2026-00001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:02:45'),
(22, 1, 'TAMBAH', 'anggota', 3, 'Tambah anggota: SU-2026-00003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:03:00'),
(23, 1, 'TAMBAH', 'anggota', 4, 'Tambah anggota: SU-2026-00004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:03:12'),
(24, 1, 'TAMBAH', 'anggota', 5, 'Tambah anggota: SU-2026-00005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:03:29'),
(25, 1, 'TAMBAH', 'pengguna', 4, 'Tambah akun: asista', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:04:15'),
(26, 1, 'TAMBAH', 'pengguna', 5, 'Tambah akun: Feni', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:04:46'),
(27, 1, 'TAMBAH', 'pengguna', 6, 'Tambah akun: fika', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:05:14'),
(28, 1, 'TAMBAH', 'pengguna', 7, 'Tambah akun: maya', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:05:50'),
(29, 1, 'LOGOUT', 'auth', NULL, 'Logout: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:06:03'),
(30, 5, 'LOGIN', 'auth', NULL, 'Login berhasil: feni', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:06:13'),
(31, 5, 'TIKET_BARU', 'tiket', 2, 'Pengajuan TK-0002 (Sirkulasi & Peminjaman Buku, urgensi Sedang)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:07:01'),
(32, 5, 'LOGOUT', 'auth', NULL, 'Logout: Feni', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:07:05'),
(33, 6, 'LOGIN', 'auth', NULL, 'Login berhasil: fika', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:07:12'),
(34, 6, 'TIKET_BARU', 'tiket', 3, 'Pengajuan TK-0003 (Pencarian Katalog / OPAC, urgensi Tinggi)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:07:41'),
(35, 6, 'LOGOUT', 'auth', NULL, 'Logout: fika', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:07:44'),
(36, NULL, 'LOGIN_GAGAL', 'auth', NULL, 'Login gagal: asita', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:07:52'),
(37, 4, 'LOGIN', 'auth', NULL, 'Login berhasil: asista', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:08:03'),
(38, 4, 'TIKET_BARU', 'tiket', 4, 'Pengajuan TK-0004 (Sirkulasi & Peminjaman Buku, urgensi Tinggi)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:08:35'),
(39, 4, 'LOGOUT', 'auth', NULL, 'Logout: asista', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:08:39'),
(40, 7, 'LOGIN', 'auth', NULL, 'Login berhasil: maya', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:08:48'),
(41, 7, 'TIKET_BARU', 'tiket', 5, 'Pengajuan TK-0005 (Sirkulasi & Peminjaman Buku, urgensi Tinggi)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:09:17'),
(42, 7, 'LOGOUT', 'auth', NULL, 'Logout: maya', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:09:20'),
(43, 1, 'LOGIN', 'auth', NULL, 'Login berhasil: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36 Edg/156.0.0.0', '2026-10-31 19:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `masalah`
--

CREATE TABLE `masalah` (
  `id_masalah` int(10) UNSIGNED NOT NULL,
  `id_tiket` int(10) UNSIGNED NOT NULL,
  `id_admin` int(10) UNSIGNED DEFAULT NULL,
  `akar_masalah` text NOT NULL,
  `tindakan_perbaikan` text DEFAULT NULL,
  `langkah_pencegahan` text DEFAULT NULL,
  `status` enum('Dianalisis','Selesai') NOT NULL DEFAULT 'Dianalisis',
  `tanggal_analisis` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(50) NOT NULL,
  `nilai` varchar(255) NOT NULL,
  `keterangan` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`, `keterangan`) VALUES
('alamat_perpustakaan', 'Jl. Brigjend Katamso No. 45 Kp. Baru, Medan', 'Alamat perpustakaan'),
('denda_per_hari', '500', 'Denda keterlambatan per hari (Rp)'),
('lama_pinjam_hari', '7', 'Lama peminjaman (hari)'),
('maks_perpanjang', '2', 'Maksimal perpanjangan per transaksi'),
('maks_pinjam', '3', 'Maksimal buku dipinjam per anggota'),
('nama_perpustakaan', 'Dinas Perpustakaan dan Arsip Provinsi Sumatera Utara', 'Nama perpustakaan'),
('target_rendah_jam', '168', 'Target penyelesaian tiket urgensi Rendah (jam)'),
('target_sedang_jam', '72', 'Target penyelesaian tiket urgensi Sedang (jam)'),
('target_tinggi_jam', '24', 'Target penyelesaian tiket urgensi Tinggi (jam)');

-- --------------------------------------------------------

--
-- Table structure for table `tiket_insiden`
--

CREATE TABLE `tiket_insiden` (
  `id_tiket` int(10) UNSIGNED NOT NULL,
  `nomor_tiket` varchar(20) NOT NULL,
  `id_pelapor` int(10) UNSIGNED NOT NULL,
  `id_admin` int(10) UNSIGNED DEFAULT NULL,
  `jenis` varchar(60) NOT NULL,
  `kategori` varchar(60) NOT NULL,
  `urgensi` enum('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Rendah',
  `deskripsi` text NOT NULL,
  `kontak` varchar(100) DEFAULT NULL,
  `status` enum('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  `penanganan` text DEFAULT NULL COMMENT 'catatan penanganan / solusi',
  `tanggal_diproses` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'kepuasan pemustaka 1-5',
  `ulasan` varchar(255) DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `tiket_insiden`
--

INSERT INTO `tiket_insiden` (`id_tiket`, `nomor_tiket`, `id_pelapor`, `id_admin`, `jenis`, `kategori`, `urgensi`, `deskripsi`, `kontak`, `status`, `penanganan`, `tanggal_diproses`, `tanggal_selesai`, `rating`, `ulasan`, `tanggal_dibuat`, `updated_at`) VALUES
(2, 'TK-0002', 5, NULL, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Sedang', 'peminjaman tidak bisa di ajukan', NULL, 'Baru', NULL, NULL, NULL, NULL, NULL, '2026-10-31 19:07:01', '2026-10-31 19:07:01'),
(3, 'TK-0003', 6, NULL, 'Gangguan', 'Pencarian Katalog / OPAC', 'Tinggi', 'fitur pencarian tidak menampilkan hasil yang relevan', NULL, 'Baru', NULL, NULL, NULL, NULL, NULL, '2026-10-31 19:07:41', '2026-10-31 19:07:41'),
(4, 'TK-0004', 4, NULL, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'kendala pengembalian buku', 'asista87@gmail.com', 'Baru', NULL, NULL, NULL, NULL, NULL, '2026-10-31 19:08:35', '2026-10-31 19:08:35'),
(5, 'TK-0005', 7, NULL, 'Gangguan', 'Sirkulasi & Peminjaman Buku', 'Tinggi', 'pengembalian buku tidak bisa dilakukan', NULL, 'Baru', NULL, NULL, NULL, NULL, NULL, '2026-10-31 19:09:17', '2026-10-31 19:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `tiket_riwayat`
--

CREATE TABLE `tiket_riwayat` (
  `id_riwayat` int(10) UNSIGNED NOT NULL,
  `id_tiket` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `status_lama` enum('Baru','Diproses','Selesai') DEFAULT NULL,
  `status_baru` enum('Baru','Diproses','Selesai') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tiket_riwayat`
--

INSERT INTO `tiket_riwayat` (`id_riwayat`, `id_tiket`, `id_user`, `status_lama`, `status_baru`, `catatan`, `created_at`) VALUES
(2, 2, 5, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-31 19:07:01'),
(3, 3, 6, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-31 19:07:41'),
(4, 4, 4, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-31 19:08:35'),
(5, 5, 7, NULL, 'Baru', 'Tiket dibuat oleh pemustaka.', '2026-10-31 19:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `id_anggota` int(10) UNSIGNED NOT NULL,
  `id_koleksi` int(10) UNSIGNED NOT NULL,
  `id_petugas` int(10) UNSIGNED DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `jumlah_perpanjang` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `denda` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('Dipinjam','Dikembalikan') NOT NULL DEFAULT 'Dipinjam',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL COMMENT 'hash bcrypt (password_hash)',
  `peran` enum('Admin','Pemustaka','Pimpinan') NOT NULL,
  `status` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `email`, `password`, `peran`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin', 'admin@dispusip-sumut.go.id', '$2y$10$A02B9nQgS/4FklpvebuG1eJAxjsttMiLj.Z0h1qoyF7r6tTVYoHIy', 'Admin', 'Aktif', '2026-10-31 19:09:27', '2026-10-31 16:17:04', '2026-10-31 19:09:27'),
(2, 'Drs. H. M. Effendy Pohan, M.Si', 'pimpinan', 'kadis@dispusip-sumut.go.id', '$2y$10$5UZoLR1Vqg6iG4tI0YHTSOcKgh3nCUkRYgCzZv/vTTPqe17KWzinu', 'Pimpinan', 'Aktif', '2026-10-31 16:23:51', '2026-10-31 16:17:04', '2026-10-31 16:23:51'),
(4, 'Asista', 'asista', 'asista87@gmail.com', '$2y$10$zEUGQiZaWaN4Jts7wFnoD.XUST.ctICN/2RL4r7PX3OI4x/hGNpxu', 'Pemustaka', 'Aktif', '2026-10-31 19:08:03', '2026-10-31 19:04:15', '2026-10-31 19:08:03'),
(5, 'Feni', 'Feni', NULL, '$2y$10$I3flok2S/1i.MnaBVjkfW.D6f7VHc1Qbbtpb5NuRPTGA5cDbfJLDe', 'Pemustaka', 'Aktif', '2026-10-31 19:06:13', '2026-10-31 19:04:46', '2026-10-31 19:06:13'),
(6, 'Fika', 'fika', NULL, '$2y$10$IZsy3rX/W/xl/8PCVSPD7OAvSSzvPWsw2lDGMByvEDPWgGP0LHbgW', 'Pemustaka', 'Aktif', '2026-10-31 19:07:12', '2026-10-31 19:05:14', '2026-10-31 19:07:12'),
(7, 'Maya', 'maya', NULL, '$2y$10$sYrqNXrfhQnnStFFPhU5reCNkhPlBfXjs3De8sVkMyt/Xf.oXKfum', 'Pemustaka', 'Aktif', '2026-10-31 19:08:48', '2026-10-31 19:05:50', '2026-10-31 19:08:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id_anggota`),
  ADD UNIQUE KEY `uq_anggota_nomor` (`nomor_anggota`),
  ADD UNIQUE KEY `uq_anggota_user` (`id_user`);

--
-- Indexes for table `basis_pengetahuan`
--
ALTER TABLE `basis_pengetahuan`
  ADD PRIMARY KEY (`id_solusi`),
  ADD UNIQUE KEY `uq_solusi_masalah` (`id_masalah`);

--
-- Indexes for table `bibliografi`
--
ALTER TABLE `bibliografi`
  ADD PRIMARY KEY (`id_bibliografi`),
  ADD UNIQUE KEY `uq_bibliografi_isbn` (`isbn`),
  ADD KEY `idx_bibliografi_judul` (`judul`),
  ADD KEY `idx_bibliografi_pengarang` (`pengarang`);

--
-- Indexes for table `dashboard_manfaat`
--
ALTER TABLE `dashboard_manfaat`
  ADD PRIMARY KEY (`id_indikator`),
  ADD UNIQUE KEY `uq_manfaat_indikator_periode` (`nama_indikator`,`periode`);

--
-- Indexes for table `jenis_layanan`
--
ALTER TABLE `jenis_layanan`
  ADD PRIMARY KEY (`id_jenis`),
  ADD UNIQUE KEY `uq_jenis_nama` (`nama`);

--
-- Indexes for table `kategori_layanan`
--
ALTER TABLE `kategori_layanan`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `uq_kategori_nama` (`nama`);

--
-- Indexes for table `koleksi`
--
ALTER TABLE `koleksi`
  ADD PRIMARY KEY (`id_koleksi`),
  ADD UNIQUE KEY `uq_koleksi_barcode` (`barcode`),
  ADD KEY `idx_koleksi_bibliografi` (`id_bibliografi`);

--
-- Indexes for table `laporan_kinerja`
--
ALTER TABLE `laporan_kinerja`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `idx_laporan_periode` (`periode`),
  ADD KEY `fk_laporan_admin` (`id_admin`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_log_user` (`id_user`),
  ADD KEY `idx_log_aksi` (`aksi`,`created_at`),
  ADD KEY `idx_log_waktu` (`created_at`);

--
-- Indexes for table `masalah`
--
ALTER TABLE `masalah`
  ADD PRIMARY KEY (`id_masalah`),
  ADD UNIQUE KEY `uq_masalah_tiket` (`id_tiket`),
  ADD KEY `fk_masalah_admin` (`id_admin`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `tiket_insiden`
--
ALTER TABLE `tiket_insiden`
  ADD PRIMARY KEY (`id_tiket`),
  ADD UNIQUE KEY `uq_tiket_nomor` (`nomor_tiket`),
  ADD KEY `idx_tiket_pelapor` (`id_pelapor`),
  ADD KEY `idx_tiket_admin` (`id_admin`),
  ADD KEY `idx_tiket_status` (`status`,`urgensi`),
  ADD KEY `idx_tiket_tanggal` (`tanggal_dibuat`),
  ADD KEY `fk_tiket_jenis` (`jenis`),
  ADD KEY `fk_tiket_kategori` (`kategori`);

--
-- Indexes for table `tiket_riwayat`
--
ALTER TABLE `tiket_riwayat`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `idx_riwayat_tiket` (`id_tiket`,`created_at`),
  ADD KEY `fk_riwayat_user` (`id_user`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `idx_transaksi_anggota` (`id_anggota`,`status`),
  ADD KEY `idx_transaksi_koleksi` (`id_koleksi`,`status`),
  ADD KEY `idx_transaksi_tanggal` (`tanggal_pinjam`),
  ADD KEY `fk_transaksi_petugas` (`id_petugas`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_peran` (`peran`,`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id_anggota` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `basis_pengetahuan`
--
ALTER TABLE `basis_pengetahuan`
  MODIFY `id_solusi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bibliografi`
--
ALTER TABLE `bibliografi`
  MODIFY `id_bibliografi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `dashboard_manfaat`
--
ALTER TABLE `dashboard_manfaat`
  MODIFY `id_indikator` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jenis_layanan`
--
ALTER TABLE `jenis_layanan`
  MODIFY `id_jenis` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kategori_layanan`
--
ALTER TABLE `kategori_layanan`
  MODIFY `id_kategori` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `koleksi`
--
ALTER TABLE `koleksi`
  MODIFY `id_koleksi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `laporan_kinerja`
--
ALTER TABLE `laporan_kinerja`
  MODIFY `id_laporan` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `masalah`
--
ALTER TABLE `masalah`
  MODIFY `id_masalah` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tiket_insiden`
--
ALTER TABLE `tiket_insiden`
  MODIFY `id_tiket` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tiket_riwayat`
--
ALTER TABLE `tiket_riwayat`
  MODIFY `id_riwayat` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `anggota`
--
ALTER TABLE `anggota`
  ADD CONSTRAINT `fk_anggota_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `basis_pengetahuan`
--
ALTER TABLE `basis_pengetahuan`
  ADD CONSTRAINT `fk_solusi_masalah` FOREIGN KEY (`id_masalah`) REFERENCES `masalah` (`id_masalah`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `koleksi`
--
ALTER TABLE `koleksi`
  ADD CONSTRAINT `fk_koleksi_bibliografi` FOREIGN KEY (`id_bibliografi`) REFERENCES `bibliografi` (`id_bibliografi`) ON UPDATE CASCADE;

--
-- Constraints for table `laporan_kinerja`
--
ALTER TABLE `laporan_kinerja`
  ADD CONSTRAINT `fk_laporan_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `masalah`
--
ALTER TABLE `masalah`
  ADD CONSTRAINT `fk_masalah_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_masalah_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `tiket_insiden` (`id_tiket`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tiket_insiden`
--
ALTER TABLE `tiket_insiden`
  ADD CONSTRAINT `fk_tiket_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tiket_jenis` FOREIGN KEY (`jenis`) REFERENCES `jenis_layanan` (`nama`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tiket_kategori` FOREIGN KEY (`kategori`) REFERENCES `kategori_layanan` (`nama`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tiket_pelapor` FOREIGN KEY (`id_pelapor`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `tiket_riwayat`
--
ALTER TABLE `tiket_riwayat`
  ADD CONSTRAINT `fk_riwayat_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `tiket_insiden` (`id_tiket`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_anggota` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_koleksi` FOREIGN KEY (`id_koleksi`) REFERENCES `koleksi` (`id_koleksi`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2026 at 05:19 AM
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
-- Database: `db_parkir`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_area_parkir`
--

CREATE TABLE `tb_area_parkir` (
  `id_area` int(11) NOT NULL,
  `nama_area` varchar(50) NOT NULL,
  `kapasitas` int(5) NOT NULL,
  `terisi` int(5) DEFAULT 0,
  `jenis_kendaraan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_area_parkir`
--

INSERT INTO `tb_area_parkir` (`id_area`, `nama_area`, `kapasitas`, `terisi`, `jenis_kendaraan`) VALUES
(9, 'basmen bawah', 2, 0, 'Mobil'),
(10, 'lapangan', 15, 0, 'Mobil'),
(11, 'lantai 1', 30, 0, 'Motor'),
(13, 'lapangan2', 30, 0, 'Sepeda');

-- --------------------------------------------------------

--
-- Table structure for table `tb_booking`
--

CREATE TABLE `tb_booking` (
  `id` int(11) NOT NULL,
  `nama_pemilik` varchar(100) NOT NULL,
  `nomor_plat` varchar(20) NOT NULL,
  `jenis_kendaraan` varchar(50) NOT NULL,
  `tanggal_booking` datetime NOT NULL,
  `total_biaya` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `durasi_hari` int(11) DEFAULT 1,
  `status` varchar(50) DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_booking`
--

INSERT INTO `tb_booking` (`id`, `nama_pemilik`, `nomor_plat`, `jenis_kendaraan`, `tanggal_booking`, `total_biaya`, `id_user`, `id_area`, `durasi_hari`, `status`) VALUES
(68, 'faisal', 'AB AHAHH', 'Motor', '2026-09-23 11:50:53', 15000, 37, 11, 1, 'Lunas');

-- --------------------------------------------------------

--
-- Table structure for table `tb_datang_langsung`
--

CREATE TABLE `tb_datang_langsung` (
  `id` int(11) NOT NULL,
  `nomor_plat` varchar(20) NOT NULL,
  `jenis_kendaraan` varchar(50) NOT NULL,
  `tanggal_masuk` datetime NOT NULL,
  `total_biaya` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kendaraan`
--

CREATE TABLE `tb_kendaraan` (
  `id_kendaraan` int(11) NOT NULL,
  `plat_nomor` varchar(15) NOT NULL,
  `jenis_kendaraan` varchar(50) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `pemilik` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kendaraan`
--

INSERT INTO `tb_kendaraan` (`id_kendaraan`, `plat_nomor`, `jenis_kendaraan`, `warna`, `pemilik`) VALUES
(111, 'DWCE', 'mobil', NULL, NULL),
(112, 'HHUH', 'motor', NULL, NULL),
(113, 'PKKK', 'mobil', NULL, NULL),
(114, 'NKN', 'mobil', NULL, NULL),
(115, 'OJIJ', 'motor', NULL, NULL),
(116, 'PLLP', 'motor', NULL, NULL),
(117, ' , CMILJMG', 'mobil', NULL, NULL),
(118, 'DDGTJ', 'mobil', NULL, NULL),
(119, 'ML,;,', 'motor', NULL, NULL),
(120, 'KLKK', 'mobil', NULL, NULL),
(121, 'KPK', 'mobil', NULL, NULL),
(122, 'AB ADG3 H7', 'mobil', NULL, NULL),
(123, 'AB HJJKL E5', 'mobil', NULL, NULL),
(124, 'AB GHJN H3', 'mobil', NULL, NULL),
(125, 'JDOK3ODJ', 'mobil', NULL, NULL),
(126, 'IJIJEIN', 'mobil', NULL, NULL),
(127, 'KOEJOIEJ', 'motor', NULL, NULL),
(128, 'OJIEJ', 'mobil', NULL, NULL),
(129, 'AB HJK4 J5', 'mobil', NULL, NULL),
(130, 'AD HJK6 N5', 'motor', NULL, NULL),
(131, 'H HJKII3 G7', 'mobil', NULL, NULL),
(132, 'B HJK9 M9', 'mobil', NULL, NULL),
(133, 'AB HJG3 B6', 'mobil', NULL, NULL),
(134, 'JJIJIJ', 'mobil', NULL, NULL),
(135, ',L,;,;KPKPKJMLL', 'mobil', NULL, NULL),
(136, 'LCPLPCL', 'mobil', NULL, NULL),
(137, 'IDIIDI', 'mobil', NULL, NULL),
(138, ' GJNBJN LBKPBLG', 'mobil', NULL, NULL),
(139, ',;.B;.,', 'mobil', NULL, NULL),
(140, 'VBB', 'mobil', NULL, NULL),
(141, 'OKOK', 'mobil', NULL, NULL),
(142, '\';\'L.', 'mobil', NULL, NULL),
(143, '\'L.,LMM', 'mobil', NULL, NULL),
(144, 'JKKHJL', 'mobil', NULL, NULL),
(145, 'LKL..,LKJL,LJ', 'mobil', NULL, NULL),
(146, 'M;KOJOPIJ', 'mobil', NULL, NULL),
(147, 'FGFHJHG', 'mobil', NULL, NULL),
(148, 'AB QHK4 JJ9', 'mobil', NULL, NULL),
(149, ';[;[;', 'motor', NULL, NULL),
(150, '...;', 'motor', NULL, NULL),
(151, 'LL[L', 'motor', NULL, NULL),
(152, 'OJJTFUKO9', 'bus/truk', NULL, NULL),
(153, 'LLL', 'bus/truk', NULL, NULL),
(154, ',,,', 'bus/truk', NULL, NULL),
(155, 'JJIHUI', 'motor', NULL, NULL),
(156, ',,', 'Mobil', NULL, NULL),
(157, 'AB AHAHH', 'Motor', NULL, NULL),
(158, 'AB JJJ', 'mobil', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_komentar`
--

CREATE TABLE `tb_komentar` (
  `id_komentar` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_log_aktivitas`
--

CREATE TABLE `tb_log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `aktivitas` varchar(100) NOT NULL,
  `waktu_aktivitas` datetime DEFAULT current_timestamp(),
  `username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_log_aktivitas`
--

INSERT INTO `tb_log_aktivitas` (`id_log`, `id_user`, `aktivitas`, `waktu_aktivitas`, `username`) VALUES
(121, NULL, 'User rafa (Member) berhasil login ke sistem.', '2026-08-10 21:31:11', 'rafa'),
(122, NULL, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-10 21:31:27', 'petugas'),
(123, NULL, 'User admin (Admin) berhasil login ke sistem.', '2026-08-10 21:31:58', 'admin'),
(124, NULL, 'User owner (Owner) berhasil login ke sistem.', '2026-08-10 21:33:43', 'owner'),
(125, NULL, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-10 21:33:58', 'petugas'),
(126, NULL, 'User rafa (Member) berhasil login ke sistem.', '2026-08-10 21:34:10', 'rafa'),
(127, NULL, 'User admin (Admin) berhasil login ke sistem.', '2026-08-10 21:34:23', 'admin'),
(154, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 09:35:47', NULL),
(155, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 09:40:33', NULL),
(156, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-11 09:46:41', NULL),
(157, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 09:47:05', NULL),
(158, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 10:18:33', NULL),
(162, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-11 10:34:05', NULL),
(163, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-11 10:34:31', NULL),
(164, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 11:12:45', NULL),
(166, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-11 11:14:09', NULL),
(168, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 11:15:28', NULL),
(169, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 11:20:20', NULL),
(170, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-11 11:37:07', NULL),
(172, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-11 12:21:05', NULL),
(173, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-11 12:23:38', NULL),
(177, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-11 13:59:23', NULL),
(178, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 07:21:05', NULL),
(179, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 11:48:33', NULL),
(180, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 11:48:58', NULL),
(181, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 11:50:34', NULL),
(182, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 11:51:41', NULL),
(183, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 11:53:00', NULL),
(184, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 11:55:00', NULL),
(185, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 11:55:30', NULL),
(186, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 11:56:41', NULL),
(187, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 11:57:16', NULL),
(188, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 12:01:39', NULL),
(189, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:02:42', NULL),
(190, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:04:46', NULL),
(191, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:07:28', NULL),
(192, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:12:51', NULL),
(193, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:18:41', NULL),
(194, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:24:30', NULL),
(195, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 12:25:04', NULL),
(196, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-12 12:26:34', NULL),
(197, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:26:58', NULL),
(198, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-12 12:32:39', NULL),
(199, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 12:34:02', NULL),
(200, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-12 12:34:40', NULL),
(201, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:35:00', NULL),
(202, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-12 12:38:10', NULL),
(203, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:38:37', NULL),
(204, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-12 12:39:11', NULL),
(205, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:39:37', NULL),
(206, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:44:16', NULL),
(207, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 12:50:32', NULL),
(208, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 12:54:51', NULL),
(209, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 13:11:45', NULL),
(210, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 13:12:18', NULL),
(211, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 13:19:31', NULL),
(212, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 13:24:46', NULL),
(213, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-12 13:39:35', NULL),
(214, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 14:02:30', NULL),
(215, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-12 14:12:49', NULL),
(216, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 07:31:01', NULL),
(217, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-13 07:36:45', NULL),
(218, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-13 07:37:50', NULL),
(219, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-13 08:19:24', NULL),
(221, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 09:23:11', NULL),
(223, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 10:03:13', NULL),
(225, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 12:24:31', NULL),
(226, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-13 12:26:31', NULL),
(227, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-13 12:27:17', NULL),
(228, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-13 12:43:30', NULL),
(229, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-13 12:47:43', NULL),
(230, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-13 13:14:12', NULL),
(231, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 13:28:00', NULL),
(232, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-13 13:29:40', NULL),
(233, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-08-13 13:31:11', NULL),
(234, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 13:32:04', NULL),
(235, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-13 13:33:21', NULL),
(236, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 13:33:55', NULL),
(237, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-13 13:34:30', NULL),
(238, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-13 13:39:33', NULL),
(239, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-13 13:41:36', NULL),
(240, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-14 11:26:17', NULL),
(241, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-14 12:35:52', NULL),
(242, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-14 13:01:07', NULL),
(243, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-18 08:31:28', NULL),
(244, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-18 08:59:09', NULL),
(245, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-18 10:25:01', NULL),
(246, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-18 10:32:23', NULL),
(247, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-18 10:33:21', NULL),
(248, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-18 10:34:32', NULL),
(249, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-08-18 10:35:19', NULL),
(250, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-18 10:40:00', NULL),
(251, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-18 10:43:18', NULL),
(252, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-19 08:17:52', NULL),
(253, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-20 07:50:31', NULL),
(254, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-08-20 07:51:32', NULL),
(255, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 07:56:59', NULL),
(256, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 08:12:00', NULL),
(257, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 08:22:35', NULL),
(258, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 08:26:27', NULL),
(259, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 08:29:08', NULL),
(260, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 08:31:48', NULL),
(261, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 08:32:13', NULL),
(262, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 08:33:53', NULL),
(263, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 09:24:19', NULL),
(264, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 09:28:40', NULL),
(265, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 10:07:51', NULL),
(266, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 12:15:16', NULL),
(267, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-20 12:28:50', NULL),
(268, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-08-20 12:29:43', NULL),
(269, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-08-20 12:32:08', NULL),
(270, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-08-20 12:32:44', NULL),
(271, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-07 11:17:11', NULL),
(272, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-07 11:49:45', NULL),
(273, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-07 11:50:35', NULL),
(274, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-07 19:03:00', NULL),
(275, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-07 19:03:22', NULL),
(276, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-07 19:54:34', NULL),
(277, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-07 20:07:20', NULL),
(278, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-07 21:10:55', NULL),
(279, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-07 21:17:02', NULL),
(280, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-07 21:28:57', NULL),
(281, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-08 08:53:50', NULL),
(282, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-16 09:02:01', NULL),
(283, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-16 09:04:00', NULL),
(284, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-16 09:24:15', NULL),
(285, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-16 09:25:01', NULL),
(286, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-16 09:38:45', NULL),
(287, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-16 09:41:58', NULL),
(288, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-16 09:43:01', NULL),
(289, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-16 09:45:52', NULL),
(290, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-16 10:29:13', NULL),
(291, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-18 09:48:12', NULL),
(292, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-18 20:31:38', NULL),
(293, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-18 20:32:17', NULL),
(294, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-18 20:32:48', NULL),
(295, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-18 20:33:25', NULL),
(296, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-18 20:37:02', NULL),
(297, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-18 20:39:23', NULL),
(298, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-18 20:43:37', NULL),
(299, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-18 20:45:25', NULL),
(300, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-21 09:27:22', NULL),
(301, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-21 09:40:44', NULL),
(302, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-21 09:52:18', NULL),
(303, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-21 09:53:08', NULL),
(304, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-21 09:54:51', NULL),
(305, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-21 09:57:27', NULL),
(306, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-21 10:38:32', NULL),
(307, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-21 10:38:53', NULL),
(308, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-21 10:43:43', NULL),
(309, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-22 14:09:26', NULL),
(310, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-22 14:15:22', NULL),
(311, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-23 07:01:33', NULL),
(312, 36, 'User shiko (Member) berhasil login ke sistem.', '2026-09-23 11:13:58', NULL),
(313, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-23 11:14:28', NULL),
(314, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-23 11:22:51', NULL),
(315, 37, 'User sal (Member) berhasil login ke sistem.', '2026-09-23 11:50:32', NULL),
(316, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-23 11:51:35', NULL),
(317, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-23 11:53:13', NULL),
(318, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-23 11:54:40', NULL),
(319, 37, 'User sal (Member) berhasil login ke sistem.', '2026-09-23 11:55:20', NULL),
(320, 25, 'User admin (Admin) berhasil login ke sistem.', '2026-09-23 11:55:45', NULL),
(321, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-23 11:56:09', NULL),
(322, 28, 'User owner (Owner) berhasil login ke sistem.', '2026-09-23 11:56:44', NULL),
(323, 27, 'User petugas (Petugas) berhasil login ke sistem.', '2026-09-23 11:57:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_riwayat_login`
--

CREATE TABLE `tb_riwayat_login` (
  `id_login` int(11) NOT NULL,
  `id_user` varchar(50) NOT NULL,
  `waktu_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_tarif`
--

CREATE TABLE `tb_tarif` (
  `id_tarif` int(11) NOT NULL,
  `jenis_kendaraan` varchar(50) DEFAULT NULL,
  `tarif_per_jam` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_tarif`
--

INSERT INTO `tb_tarif` (`id_tarif`, `jenis_kendaraan`, `tarif_per_jam`) VALUES
(1, 'motor', 15000),
(2, 'mobil', 25000),
(6, 'Sepeda', 2000);

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_parkir` int(11) DEFAULT NULL,
  `id_kendaraan` int(11) DEFAULT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `id_tarif` int(11) DEFAULT NULL,
  `durasi_jam` int(5) DEFAULT 0,
  `biaya_total` decimal(10,0) DEFAULT 0,
  `status` enum('masuk','keluar') DEFAULT 'masuk',
  `id_user` int(11) DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `tampil` int(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_transaksi`, `id_parkir`, `id_kendaraan`, `waktu_masuk`, `waktu_keluar`, `id_tarif`, `durasi_jam`, `biaya_total`, `status`, `id_user`, `id_area`, `tampil`) VALUES
(133, 830716, 129, '2026-08-18 10:26:30', '2026-08-18 10:42:03', 1, 3, 75000, 'keluar', 27, 10, 0),
(134, 436376, 129, '2026-08-18 10:27:05', '2026-08-18 10:41:57', 1, 3, 75000, 'keluar', 27, 10, 0),
(135, 864869, 130, '2026-08-18 10:28:03', '2026-08-18 10:41:52', 1, 1, 15000, 'keluar', 27, 9, 0),
(136, 702446, 131, '2026-08-18 10:40:21', '2026-08-18 10:41:45', 1, 3, 75000, 'keluar', 36, 10, 0),
(137, 205617, 132, '2026-08-18 10:40:29', '2026-08-18 10:41:39', 1, 3, 75000, 'keluar', 36, 10, 0),
(138, 644898, 133, '2026-08-20 08:09:14', '2026-08-20 08:33:26', 1, 1, 25000, 'keluar', 36, 9, 0),
(139, 735069, 134, '2026-08-20 08:29:25', '2026-08-20 08:33:21', 1, 1, 25000, 'keluar', 27, 9, 0),
(140, 610258, 135, '2026-08-20 08:29:46', '2026-08-20 08:33:17', 1, 1, 25000, 'keluar', 27, 9, 0),
(141, 484021, 136, '2026-08-20 08:32:29', '2026-08-20 08:33:12', 1, 1, 25000, 'keluar', 27, 9, 0),
(142, 767249, 137, '2026-08-20 08:32:42', '2026-08-20 08:32:56', 1, 1, 25000, 'keluar', 27, 9, 0),
(143, 758917, 138, '2026-08-20 09:24:37', '2026-08-20 09:27:15', 1, 1, 25000, 'keluar', 27, 9, 1),
(144, 832354, 139, '2026-08-20 09:24:49', '2026-08-20 09:27:24', 1, 1, 25000, 'keluar', 27, 9, 1),
(145, 194776, 140, '2026-08-20 09:25:07', '2026-08-20 09:28:15', 1, 1, 25000, 'keluar', 27, 9, 1),
(146, 819369, 141, '2026-08-20 09:25:20', '2026-08-20 09:28:08', 1, 1, 25000, 'keluar', 27, 9, 1),
(147, 381692, 142, '2026-08-20 09:25:38', '2026-08-20 09:28:03', 1, 1, 25000, 'keluar', 27, 9, 1),
(148, 160466, 143, '2026-08-20 09:25:55', '2026-08-20 09:27:51', 1, 1, 25000, 'keluar', 27, 9, 1),
(149, 829129, 144, '2026-08-20 09:26:09', '2026-08-20 09:27:45', 1, 1, 25000, 'keluar', 27, 9, 0),
(150, 722300, 145, '2026-08-20 09:26:24', '2026-08-20 09:27:40', 1, 1, 25000, 'keluar', 27, 9, 0),
(151, 898097, 146, '2026-08-20 09:26:38', '2026-08-20 09:27:35', 1, 1, 25000, 'keluar', 27, 9, 0),
(152, 268086, 147, '2026-08-20 09:26:57', '2026-08-20 09:27:29', 1, 1, 25000, 'keluar', 27, 9, 0),
(153, 673891, 148, '2026-08-20 12:31:50', '2026-09-07 11:49:54', 1, 1, 1725000, 'keluar', 27, 9, 1),
(156, 269543, 151, '2026-09-08 09:19:31', NULL, 1, 1, 0, 'masuk', 27, 11, 1),
(160, 940775, 155, '2026-09-18 10:48:52', NULL, 1, 1, 15000, 'masuk', 27, 11, 1),
(161, 919628, 156, '2026-09-22 14:29:31', NULL, 1, 1, 25000, 'masuk', 36, 9, 1),
(162, 746868, 157, '2026-09-23 11:51:49', NULL, 1, 1, 15000, 'masuk', 37, 11, 1),
(163, 971422, 158, '2026-09-23 11:52:51', NULL, 2, 3, 75000, 'masuk', 27, 9, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_ulasan`
--

CREATE TABLE `tb_ulasan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `tanggal` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_ulasan`
--

INSERT INTO `tb_ulasan` (`id`, `nama`, `rating`, `komentar`, `tanggal`) VALUES
(10, 'faisal', 5, 'bagus cuyyy', '2026-08-18 08:19:12'),
(11, 'alan', 4, 'lumayan lah', '2026-08-18 08:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','petugas','owner','member') DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `status_aktif`) VALUES
(25, 'Administrator Utama', 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 1),
(27, 'petugas', 'petugas', '202cb962ac59075b964b07152d234b70', 'petugas', 1),
(28, 'pemilik stasiun', 'owner', '202cb962ac59075b964b07152d234b70', 'owner', 1),
(36, 'shikomori', 'shiko', '$2y$10$wY4ysDko/R6XhgCiGa0HYuKWHfbIGwchf94MCYEu8ltIGhNX/Yhva', 'member', 1),
(37, 'faisal', 'sal', '$2y$10$9ssptUG4DEdLqk2K4yQrOO.8TmU0SdeFHv2ZxCmAPWnmGY2hzaG8.', 'member', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_area_parkir`
--
ALTER TABLE `tb_area_parkir`
  ADD PRIMARY KEY (`id_area`);

--
-- Indexes for table `tb_booking`
--
ALTER TABLE `tb_booking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_datang_langsung`
--
ALTER TABLE `tb_datang_langsung`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  ADD PRIMARY KEY (`id_kendaraan`);

--
-- Indexes for table `tb_komentar`
--
ALTER TABLE `tb_komentar`
  ADD PRIMARY KEY (`id_komentar`);

--
-- Indexes for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_riwayat_login`
--
ALTER TABLE `tb_riwayat_login`
  ADD PRIMARY KEY (`id_login`);

--
-- Indexes for table `tb_tarif`
--
ALTER TABLE `tb_tarif`
  ADD PRIMARY KEY (`id_tarif`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_tarif` (`id_tarif`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_area` (`id_area`);

--
-- Indexes for table `tb_ulasan`
--
ALTER TABLE `tb_ulasan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_area_parkir`
--
ALTER TABLE `tb_area_parkir`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tb_booking`
--
ALTER TABLE `tb_booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `tb_datang_langsung`
--
ALTER TABLE `tb_datang_langsung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  MODIFY `id_kendaraan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `tb_komentar`
--
ALTER TABLE `tb_komentar`
  MODIFY `id_komentar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- AUTO_INCREMENT for table `tb_riwayat_login`
--
ALTER TABLE `tb_riwayat_login`
  MODIFY `id_login` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_tarif`
--
ALTER TABLE `tb_tarif`
  MODIFY `id_tarif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `tb_ulasan`
--
ALTER TABLE `tb_ulasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  ADD CONSTRAINT `tb_log_aktivitas_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `tb_transaksi_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_transaksi_ibfk_2` FOREIGN KEY (`id_tarif`) REFERENCES `tb_tarif` (`id_tarif`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_transaksi_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_transaksi_ibfk_4` FOREIGN KEY (`id_area`) REFERENCES `tb_area_parkir` (`id_area`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

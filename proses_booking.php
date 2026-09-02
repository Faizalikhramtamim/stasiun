<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login sebagai member
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'member') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_SESSION['id_user'];
    
    $nama_pemilik = isset($_SESSION['nama']) ? mysqli_real_escape_string($conn, $_SESSION['nama']) : 'Member';
    $nomor_plat = isset($_POST['nomor_plat']) ? mysqli_real_escape_string($conn, $_POST['nomor_plat']) : '-';
    
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $id_area = intval($_POST['id_area']);
    
    // Ambil durasi dari form (mendukung nama durasi_hari maupun durasi_jam jika masih ada sisa cache)
    $durasi_hari = intval($_POST['durasi_hari'] ?? $_POST['durasi_jam'] ?? 1);
    if ($durasi_hari < 1) { $durasi_hari = 1; } // Minimal 1 hari

    // Tentukan tarif harian (Mobil: 20.000, Motor: 15.000)
    $tarif_harian = (strtolower($jenis_kendaraan) == 'mobil') ? 20000 : 15000;
    $total_biaya = $tarif_harian * $durasi_hari;

    // Simpan ke database tb_booking
    $query = "INSERT INTO tb_booking (id_user, nama_pemilik, nomor_plat, jenis_kendaraan, id_area, durasi_hari, tanggal_booking, total_biaya) 
              VALUES ('$id_user', '$nama_pemilik', '$nomor_plat', '$jenis_kendaraan', '$id_area', '$durasi_hari', NOW(), '$total_biaya')";
    
    if (mysqli_query($conn, $query)) {
        $id_booking_baru = mysqli_insert_id($conn);
        header("Location: pembayaran.php?id=" . $id_booking_baru);
        exit;
    } else {
        echo "<script>alert('Gagal melakukan booking: " . mysqli_error($conn) . "'); window.location='member.php';</script>";
    }
} else {
    header("Location: member.php");
    exit;
}
?>
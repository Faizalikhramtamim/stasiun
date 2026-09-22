<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (isset($conn)) {
    mysqli_select_db($conn, 'db_parkir'); 
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: index.php");
    exit;
}

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Petugas Stasiun');

// ==========================================
// A. PROSES AKSI BOOKING (Setujui / Batal / Hapus)
// ==========================================
if (isset($_GET['aksi_booking']) && isset($_GET['id_b'])) {
    $id_b = intval($_GET['id_b']);
    $aksi_b = $_GET['aksi_booking'];
    
    $cek_pk = mysqli_query($conn, "SHOW COLUMNS FROM tb_booking");
    $pk_booking = 'id_booking';
    if ($cek_pk && mysqli_num_rows($cek_pk) > 0) {
        while ($d_pk = mysqli_fetch_assoc($cek_pk)) {
            if (isset($d_pk['Key']) && $d_pk['Key'] == 'PRI') {
                $pk_booking = $d_pk['Field'];
                break;
            }
        }
    }

    if ($aksi_b == 'hapus') {
        mysqli_query($conn, "DELETE FROM tb_booking WHERE $pk_booking = '$id_b'");
        header("Location: petugas.php?status=sukses_hapus");
        exit;
    } elseif ($aksi_b == 'batal') {
        mysqli_query($conn, "UPDATE tb_booking SET status = 'Batal' WHERE $pk_booking = '$id_b'");
        header("Location: petugas.php?status=sukses_batal");
        exit;
    } elseif ($aksi_b == 'setujui') {
        $q_b = mysqli_query($conn, "SELECT * FROM tb_booking WHERE $pk_booking = '$id_b' LIMIT 1");
        if ($q_b && mysqli_num_rows($q_b) > 0) {
            $book = mysqli_fetch_assoc($q_b);
            $plat = mysqli_real_escape_string($conn, $book['nomor_plat']);
            $jenis = mysqli_real_escape_string($conn, $book['jenis_kendaraan']);
            $id_area = intval($book['id_area']);
            $durasi_hari = isset($book['durasi_hari']) ? intval($book['durasi_hari']) : 1;
            $id_user_member = intval($book['id_user']);
            
            // Validasi kuota saat setujui booking
            $q_cek_kuota_b = mysqli_query($conn, "
                SELECT (a.kapasitas - (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')) AS sisa_kuota 
                FROM tb_area_parkir a WHERE a.id_area = '$id_area' LIMIT 1
            ");
            if ($q_cek_kuota_b && mysqli_num_rows($q_cek_kuota_b) > 0) {
                $dt_kb = mysqli_fetch_assoc($q_cek_kuota_b);
                if (intval($dt_kb['sisa_kuota']) <= 0) {
                    // Otomatis ubah status booking user menjadi Batal jika area penuh
                    mysqli_query($conn, "UPDATE tb_booking SET status = 'Batal' WHERE $pk_booking = '$id_b'");
                    
                    header("Location: petugas.php?status=error_penuh");
                    exit;
                }
            }

            // Ambil tarif dari database berdasarkan jenis kendaraan untuk menghitung total biaya transaksi
            $q_trf_booking = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE LOWER(jenis_kendaraan) = LOWER('$jenis') LIMIT 1");
            $tarif_harian_b = 15000;
            if ($q_trf_booking && mysqli_num_rows($q_trf_booking) > 0) {
                $dt_trf_b = mysqli_fetch_assoc($q_trf_booking);
                if (isset($dt_trf_b['tarif_per_jam'])) { $tarif_harian_b = intval($dt_trf_b['tarif_per_jam']); }
                elseif (isset($dt_trf_b['tarif_harian'])) { $tarif_harian_b = intval($dt_trf_b['tarif_harian']); }
                elseif (isset($dt_trf_b['tarif'])) { $tarif_harian_b = intval($dt_trf_b['tarif']); }
                elseif (isset($dt_trf_b['harga'])) { $tarif_harian_b = intval($dt_trf_b['harga']); }
                elseif (isset($dt_trf_b['biaya'])) { $tarif_harian_b = intval($dt_trf_b['biaya']); }
            }
            $total_biaya_booking = $tarif_harian_b * $durasi_hari;

            $waktu_masuk = date('Y-m-d H:i:s');
            $id_parkir = rand(100000, 999999);

            $q_cek = mysqli_query($conn, "SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = '$plat' LIMIT 1");
            if ($q_cek && mysqli_num_rows($q_cek) > 0) {
                $d_k = mysqli_fetch_assoc($q_cek);
                $id_kendaraan = $d_k['id_kendaraan'];
            } else {
                mysqli_query($conn, "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan) VALUES ('$plat', '$jenis')");
                $id_kendaraan = mysqli_insert_id($conn);
            }

            $q_tarif = mysqli_query($conn, "SELECT id_tarif FROM tb_tarif LIMIT 1");
            $id_tarif = 1;
            if ($q_tarif && mysqli_num_rows($q_tarif) > 0) {
                $dt = mysqli_fetch_assoc($q_tarif);
                $id_tarif = $dt['id_tarif'];
            }

            $cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM tb_transaksi LIKE 'durasi_jam'");
            if ($cek_kolom && mysqli_num_rows($cek_kolom) > 0) {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam, biaya_total) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user_member', '$id_area', '$durasi_hari', '$total_biaya_booking')");
            } else {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, biaya_total) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user_member', '$id_area', '$total_biaya_booking')");
            }

            mysqli_query($conn, "UPDATE tb_booking SET status = 'Lunas' WHERE $pk_booking = '$id_b'");
        }
        header("Location: petugas.php?status=sukses_setujui");
        exit;
    }
}

// ==========================================
// B. PROSES KENDARAAN MASUK INAP MANUAL
// ==========================================
if (isset($_POST['masuk'])) {
    $plat = strtoupper(mysqli_real_escape_string($conn, $_POST['plat_nomor']));
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']); 
    $id_area = intval($_POST['id_area']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']); 
    
    $estimasi_hari = intval($_POST['estimasi_hari']);
    if ($estimasi_hari < 1) { $estimasi_hari = 1; }
    if ($estimasi_hari > 3) { $estimasi_hari = 3; }

    // === PROTEKSI CEK KUOTA AREA PENUH ===
    $q_cek_kuota = mysqli_query($conn, "
        SELECT (a.kapasitas - (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')) AS sisa_kuota 
        FROM tb_area_parkir a WHERE a.id_area = '$id_area' LIMIT 1
    ");
    if ($q_cek_kuota && mysqli_num_rows($q_cek_kuota) > 0) {
        $dt_k = mysqli_fetch_assoc($q_cek_kuota);
        if (intval($dt_k['sisa_kuota']) <= 0) {
            header("Location: petugas.php?status=error_penuh");
            exit;
        }
    }
    // ===================================

    $id_user = intval($_SESSION['id_user']);
    $waktu_masuk = date('Y-m-d H:i:s');
    $id_parkir = rand(100000, 999999);

    $q_cek = mysqli_query($conn, "SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = '$plat' LIMIT 1");
    if ($q_cek && mysqli_num_rows($q_cek) > 0) {
        $d_k = mysqli_fetch_assoc($q_cek);
        $id_kendaraan = $d_k['id_kendaraan'];
    } else {
        mysqli_query($conn, "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan) VALUES ('$plat', '$jenis')");
        $id_kendaraan = mysqli_insert_id($conn);
    }

    $q_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE LOWER(jenis_kendaraan) = '$jenis' LIMIT 1");
    $id_tarif = 1;
    $tarif_harian = 15000;
    if ($q_tarif && mysqli_num_rows($q_tarif) > 0) {
        $dt = mysqli_fetch_assoc($q_tarif);
        $id_tarif = $dt['id_tarif'];
        if (isset($dt['tarif_per_jam'])) { $tarif_harian = intval($dt['tarif_per_jam']); }
        elseif (isset($dt['tarif_harian'])) { $tarif_harian = intval($dt['tarif_harian']); }
        elseif (isset($dt['tarif'])) { $tarif_harian = intval($dt['tarif']); }
        elseif (isset($dt['harga'])) { $tarif_harian = intval($dt['harga']); }
        elseif (isset($dt['biaya'])) { $tarif_harian = intval($dt['biaya']); }
    } else {
        $q_tarif_all = mysqli_query($conn, "SELECT * FROM tb_tarif LIMIT 1");
        if ($q_tarif_all && mysqli_num_rows($q_tarif_all) > 0) {
            $dt_all = mysqli_fetch_assoc($q_tarif_all);
            $id_tarif = $dt_all['id_tarif'];
            if (isset($dt_all['tarif_per_jam'])) { $tarif_harian = intval($dt_all['tarif_per_jam']); }
            elseif (isset($dt_all['tarif_harian'])) { $tarif_harian = intval($dt_all['tarif_harian']); }
            elseif (isset($dt_all['tarif'])) { $tarif_harian = intval($dt_all['tarif']); }
            elseif (isset($dt_all['harga'])) { $tarif_harian = intval($dt_all['harga']); }
            elseif (isset($dt_all['biaya'])) { $tarif_harian = intval($dt_all['biaya']); }
        }
    }

    $total_biaya_masuk = $tarif_harian * $estimasi_hari;

    $cek_kolom_metode = mysqli_query($conn, "SHOW COLUMNS FROM tb_transaksi LIKE 'metode_pembayaran'");
    $cek_kolom_durasi = mysqli_query($conn, "SHOW COLUMNS FROM tb_transaksi LIKE 'durasi_jam'");
    $cek_kolom_biaya = mysqli_query($conn, "SHOW COLUMNS FROM tb_transaksi LIKE 'biaya_total'");

    if ($cek_kolom_metode && mysqli_num_rows($cek_kolom_metode) > 0) {
        if ($cek_kolom_durasi && mysqli_num_rows($cek_kolom_durasi) > 0) {
            if ($cek_kolom_biaya && mysqli_num_rows($cek_kolom_biaya) > 0) {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam, metode_pembayaran, biaya_total) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$estimasi_hari', '$metode_pembayaran', '$total_biaya_masuk')");
            } else {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam, metode_pembayaran) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$estimasi_hari', '$metode_pembayaran')");
            }
        } else {
            if ($cek_kolom_biaya && mysqli_num_rows($cek_kolom_biaya) > 0) {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, metode_pembayaran, biaya_total) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$metode_pembayaran', '$total_biaya_masuk')");
            } else {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, metode_pembayaran) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$metode_pembayaran')");
            }
        }
    } else {
        if ($cek_kolom_durasi && mysqli_num_rows($cek_kolom_durasi) > 0) {
            if ($cek_kolom_biaya && mysqli_num_rows($cek_kolom_biaya) > 0) {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam, biaya_total) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$estimasi_hari', '$total_biaya_masuk')");
            } else {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$estimasi_hari')");
            }
        } else {
            if ($cek_kolom_biaya && mysqli_num_rows($cek_kolom_biaya) > 0) {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, biaya_total) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$total_biaya_masuk')");
            } else {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area')");
            }
        }
    }
    
    $q_nm_area = mysqli_query($conn, "SELECT nama_area FROM tb_area_parkir WHERE id_area = '$id_area' LIMIT 1");
    $dt_nm_area = ($q_nm_area && mysqli_num_rows($q_nm_area) > 0) ? mysqli_fetch_assoc($q_nm_area)['nama_area'] : 'Area Parkir';

    $url_redirect = "petugas.php?status=sukses_masuk&id_parkir=" . $id_parkir . 
                    "&plat=" . urlencode($plat) . 
                    "&jenis=" . urlencode(ucfirst($jenis)) . 
                    "&area=" . urlencode($dt_nm_area) . 
                    "&estimasi=" . $estimasi_hari .
                    "&metode=" . urlencode($metode_pembayaran) .
                    "&nominal=" . $total_biaya_masuk;
    header("Location: " . $url_redirect);
    exit;
}

// ==========================================
// C. PROSES KENDARAAN KELUAR / SELESAI INAP
// ==========================================
if (isset($_POST['id_parkir']) && !isset($_POST['masuk'])) {
    $id_parkir = mysqli_real_escape_string($conn, $_POST['id_parkir']);
    $waktu_keluar = date('Y-m-d H:i:s');
    
    $q_trx = mysqli_query($conn, "SELECT t.*, k.jenis_kendaraan FROM tb_transaksi t JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan WHERE t.id_parkir = '$id_parkir' LIMIT 1");
    if ($q_trx && mysqli_num_rows($q_trx) > 0) {
        $trx = mysqli_fetch_assoc($q_trx);
        $waktu_masuk = $trx['waktu_masuk'];
        $jenis = strtolower($trx['jenis_kendaraan']);
        $estimasi_pilihan = isset($trx['durasi_jam']) && intval($trx['durasi_jam']) > 0 ? intval($trx['durasi_jam']) : 1;

        $waktu_masuk_ts = strtotime($waktu_masuk);
        $sekarang_ts = time();
        $selisih_detik = $sekarang_ts - $waktu_masuk_ts;
        $lama_hari = ceil($selisih_detik / (60 * 60 * 24));
        if ($lama_hari < 1) { $lama_hari = 1; }

        $q_tarif_keluar = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE LOWER(jenis_kendaraan) = '$jenis' LIMIT 1");
        $tarif_harian = 15000;
        if ($q_tarif_keluar && mysqli_num_rows($q_tarif_keluar) > 0) {
            $dt_t = mysqli_fetch_assoc($q_tarif_keluar);
            if (isset($dt_t['tarif_per_jam'])) { $tarif_harian = intval($dt_t['tarif_per_jam']); }
            elseif (isset($dt_t['tarif_harian'])) { $tarif_harian = intval($dt_t['tarif_harian']); }
            elseif (isset($dt_t['tarif'])) { $tarif_harian = intval($dt_t['tarif']); }
            elseif (isset($dt_t['harga'])) { $tarif_harian = intval($dt_t['harga']); }
            elseif (isset($dt_t['biaya'])) { $tarif_harian = intval($dt_t['biaya']); }
        }

        $biaya_dasar = $tarif_harian * $estimasi_pilihan;

        $denda = 0;
        if ($lama_hari > $estimasi_pilihan) {
            $kelebihan_hari = $lama_hari - $estimasi_pilihan;
            $denda = $kelebihan_hari * 100000; 
        }

        $total_bayar = $biaya_dasar + $denda;

        $cek_kolom_keluar = mysqli_query($conn, "SHOW COLUMNS FROM tb_transaksi LIKE 'waktu_keluar'");
        if ($cek_kolom_keluar && mysqli_num_rows($cek_kolom_keluar) > 0) {
            mysqli_query($conn, "UPDATE tb_transaksi SET status = 'keluar', waktu_keluar = '$waktu_keluar', biaya_total = '$total_bayar' WHERE id_parkir = '$id_parkir'");
        } else {
            mysqli_query($conn, "UPDATE tb_transaksi SET status = 'keluar', biaya_total = '$total_bayar' WHERE id_parkir = '$id_parkir'");
        }

        header("Location: petugas.php?status=sukses_keluar");
        exit;
    }
}

// ==========================================
// PENCEGAHAN ERROR OTOMATIS TABEL & KOLOM
// ==========================================
$nama_tabel_user = 'tb_user'; 
$kolom_nama = 'nama_lengkap';

$cek_kolom_booking = mysqli_query($conn, "SHOW COLUMNS FROM tb_booking LIKE 'id_booking'");
$order_by_booking = ($cek_kolom_booking && mysqli_num_rows($cek_kolom_booking) > 0) ? 'b.id_booking' : 'b.id_user';

$query_booking = mysqli_query($conn, "SELECT b.*, u.$kolom_nama AS nama_lengkap, a.nama_area 
                                     FROM tb_booking b 
                                     LEFT JOIN $nama_tabel_user u ON b.id_user = u.id_user 
                                     LEFT JOIN tb_area_parkir a ON b.id_area = a.id_area 
                                     ORDER BY $order_by_booking DESC");

$query_area_sisa = mysqli_query($conn, "
    SELECT a.*, 
    (a.kapasitas - (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')) AS sisa_kuota 
    FROM tb_area_parkir a
");

$query_transaksi_inap = mysqli_query($conn, "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
                                           FROM tb_transaksi t 
                                           JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                                           LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
                                           WHERE t.status = 'masuk' 
                                           ORDER BY t.waktu_masuk DESC");

$query_daftar_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title>Dashboard Petugas - Stasiun Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.88)), 
                        url('https://images.unsplash.com/photo-1474487548417-781cb71495f3?q=80&w=1920&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            color: #e2e8f0; 
            min-height: 100vh;
        }
        .navbar-custom {
            background: rgba(15, 23, 42, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .card { 
            border-radius: 1rem; 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3); 
            margin-bottom: 1.5rem; 
            color: #f1f5f9;
        }
        .card-header {
            background: rgba(15, 23, 42, 0.6) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc;
            border-top-left-radius: 1rem !important;
            border-top-right-radius: 1rem !important;
        }
        .table { color: #f1f5f9; }
        .table-custom th { background-color: #0f172a !important; color: #ffffff; text-transform: uppercase; font-size: 0.75rem; padding: 0.85rem 1rem; border-color: rgba(255,255,255,0.05); }
        .table-custom td { padding: 0.85rem 1rem; vertical-align: middle; border-color: rgba(255,255,255,0.05); position: relative; }
        .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.04); color: #ffffff; }
        .btn-proses-keluar { position: relative; z-index: 10; cursor: pointer; }
        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }
        .form-control::placeholder { color: #94a3b8; }
        @media print {
            body * { visibility: hidden; }
            #print-area, #print-area *, #print-karcis-area, #print-karcis-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; color: #000; }
            #print-karcis-area { position: absolute; left: 0; top: 0; width: 100%; color: #000; }
        }
    </style>
</head>
<body>
    <audio id="sound-click" src="img/click.mp3" preload="auto"></audio>
    <audio id="sound-success" src="img/berhasil.MPEG" preload="auto"></audio>
    <audio id="sound-batal" src="img/salah.MPEG" preload="auto"></audio>
    <audio id="sound-hapus" src="img/salah.MPEG" preload="auto"></audio>

    <nav class="navbar navbar-dark navbar-custom px-4 py-3 shadow-sm sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold fs-5">🚆 PETUGAS AREA — <span class="text-warning"><?= htmlspecialchars($nama_petugas); ?></span></span>
            <a href="logout.php" id="btn-logout" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
        </div>
    </nav>

    <div class="container py-5">
        <!-- BAGIAN 1: KONFIRMASI BOOKING MEMBER -->
        <div class="card">
            <div class="card-header py-3 px-4 fw-bold">📋 Konfirmasi Booking Pre-Order Member</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Nama Member</th>
                                <th>Kendaraan & Plat</th>
                                <th>Area Inap</th>
                                <th>Durasi</th>
                                <th>Total Biaya</th>
                                <th>Status</th>
                                <th class="text-center">Aksi (Setuju / Batal / Hapus)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($query_booking && mysqli_num_rows($query_booking) > 0) {
                                while($row = mysqli_fetch_assoc($query_booking)) {
                                    $id_b = isset($row['id_booking']) ? $row['id_booking'] : (isset($row['id_b']) ? $row['id_b'] : reset($row));
                                    $nama_m = isset($row['nama_lengkap']) ? $row['nama_lengkap'] : 'Member';
                                    $plat_m = isset($row['nomor_plat']) ? $row['nomor_plat'] : '-';
                                    $jenis_m = isset($row['jenis_kendaraan']) ? $row['jenis_kendaraan'] : '-';
                                    $area_m = isset($row['nama_area']) ? $row['nama_area'] : '-';
                                    $durasi_m = isset($row['durasi_hari']) ? $row['durasi_hari'] : 1;
                                    $total_m = isset($row['total_biaya']) ? $row['total_biaya'] : 0;
                                    $status_m = isset($row['status']) ? $row['status'] : 'Menunggu';
                            ?>
                            <tr>
                                <td class="ps-4"><?= $id_b; ?></td>
                                <td><?= htmlspecialchars($nama_m); ?></td>
                                <td>
                                    <strong><?= strtoupper($plat_m); ?></strong><br>
                                    <small class="text-muted"><?= ucfirst($jenis_m); ?></small>
                                </td>
                                <td><?= htmlspecialchars($area_m); ?></td>
                                <td><?= $durasi_m; ?> Hari</td>
                                <td class="fw-bold text-info">Rp <?= number_format($total_m, 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?= ($status_m == 'Lunas') ? 'bg-success' : (($status_m == 'Batal') ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                        <?= ucfirst(strtolower($status_m)); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($status_m == 'Menunggu'): ?>
                                        <a href="petugas.php?aksi_booking=setujui&id_b=<?= $id_b; ?>" class="btn btn-sm btn-success rounded-pill px-3 btn-aksi-setuju">✔️ Setujui</a>
                                        <a href="petugas.php?aksi_booking=batal&id_b=<?= $id_b; ?>" class="btn btn-sm btn-warning text-dark rounded-pill px-3 btn-aksi-batal">❌ Batal</a>
                                    <?php else: ?>
                                        <span class="text-muted small">Selesai / Diproses</span>
                                    <?php endif; ?>
                                    <a href="petugas.php?aksi_booking=hapus&id_b=<?= $id_b; ?>" class="btn btn-sm btn-danger rounded-pill ms-1 btn-aksi-hapus">🗑️ Hapus</a>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data booking pre-order.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BAGIAN 2: INPUT KENDARAAN INAP MANUAL -->
        <div class="card">
            <div class="card-header py-3 px-4 fw-bold">🚗 Input Kendaraan Inap Masuk Manual (Tarif Per Hari Sesuai Database)</div>
            <div class="card-body p-4">
                <form action="petugas.php" method="POST" class="row g-3" id="form-masuk-inap">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">PLAT NOMOR</label>
                        <input type="text" name="plat_nomor" class="form-control text-uppercase" placeholder="Contoh: B 1234 XYZ" required autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">JENIS KENDARAAN</label>
                        <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-select" required>
                            <option value="">-- Pilih Jenis --</option>
                            <?php 
                            if ($query_daftar_tarif && mysqli_num_rows($query_daftar_tarif) > 0) {
                                mysqli_data_seek($query_daftar_tarif, 0);
                                while($trf = mysqli_fetch_assoc($query_daftar_tarif)) {
                                    $jns = strtolower($trf['jenis_kendaraan']);
                                    $harga = 0;
                                    if (isset($trf['tarif_per_jam'])) { $harga = $trf['tarif_per_jam']; }
                                    elseif (isset($trf['tarif_harian'])) { $harga = $trf['tarif_harian']; }
                                    elseif (isset($trf['tarif'])) { $harga = $trf['tarif']; }
                                    elseif (isset($trf['harga'])) { $harga = $trf['harga']; }
                                    elseif (isset($trf['biaya'])) { $harga = $trf['biaya']; }
                            ?>
                                <option value="<?= $jns; ?>" data-tarif="<?= $harga; ?>">
                                    <?= ucfirst($jns); ?> (Rp <?= number_format($harga, 0, ',', '.'); ?> / hari)
                                </option>
                            <?php 
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">AREA PARKIR INAP & SISA KUOTA</label>
                        <select name="id_area" id="id_area" class="form-select" required>
                            <option value="">-- Pilih Area & Sisa Kuota --</option>
                            <?php 
                            if ($query_area_sisa && mysqli_num_rows($query_area_sisa) > 0) {
                                while($a = mysqli_fetch_assoc($query_area_sisa)) { 
                                    $sisa = max(0, $a['sisa_kuota']);
                                    $jenis_area_db = isset($a['jenis_kendaraan']) ? strtolower(trim($a['jenis_kendaraan'])) : '';
                            ?>
                                <option value="<?= $a['id_area']; ?>" data-jenis="<?= $jenis_area_db; ?>">
                                    <?= htmlspecialchars($a['nama_area']); ?> <?= $jenis_area_db ? '(' . ucfirst($jenis_area_db) . ')' : ''; ?> (Sisa: <?= $sisa; ?> dari <?= $a['kapasitas']; ?>)
                                </option>
                            <?php 
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">ESTIMASI INAP (1 - 3 HARI)</label>
                        <input type="number" name="estimasi_hari" id="estimasi_hari" class="form-control" value="1" min="1" max="3" required>
                    </div>

                    <div class="col-md-4 mt-3">
                        <label class="form-label fw-semibold small text-info">METODE PEMBAYARAN</label>
                        <select name="metode_pembayaran" id="metode_pembayaran" class="form-select" required>
                            <option value="Cash">Cash (Tunai)</option>
                            <option value="Digital">Digital (QRIS / QR Code)</option>
                        </select>
                    </div>

                    <div class="col-md-8 mt-3" id="area-qr-container" style="display: none;">
                        <label class="form-label fw-semibold small text-warning">SCAN QR CODE PEMBAYARAN DIGITAL</label>
                        <div class="bg-dark p-3 rounded border border-secondary d-inline-block">
                            <img src="img/qr.jpeg" alt="QR Code Pembayaran" class="img-fluid rounded bg-white p-2" style="max-width: 210px; max-height: 210px;" onerror="this.onerror=null; this.src='img/qr.jpg';">
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" name="masuk" value="1" class="btn btn-info px-4 rounded-pill fw-semibold text-dark btn-aksi-simpan">Simpan Masuk Inap</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- BAGIAN 3: DAFTAR KENDARAAN SEDANG INAP -->
        <div class="card">
            <div class="card-header py-3 px-4 fw-bold">📋 Daftar Kendaraan Sedang Inap & Hitung Mundur</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Plat Nomor</th>
                                <th>Kendaraan</th>
                                <th>Area Inap</th>
                                <th>Waktu Masuk</th>
                                <th>Hitung Mundur Sisa Waktu</th>
                                <th>Pilihan Inap</th>
                                <th>Estimasi Denda</th>
                                <th>Total Estimasi</th>
                                <th class="text-center">Aksi / Cetak / Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($query_transaksi_inap && mysqli_num_rows($query_transaksi_inap) > 0) {
                                while($t = mysqli_fetch_assoc($query_transaksi_inap)) {
                                    $waktu_masuk = $t['waktu_masuk'];
                                    $estimasi_pilihan = isset($t['durasi_jam']) && intval($t['durasi_jam']) > 0 ? intval($t['durasi_jam']) : 1;
                                    $total_jam_target = $estimasi_pilihan * 24; 

                                    $jns_t = strtolower($t['jenis_kendaraan']);
                                    $q_trf_row = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE LOWER(jenis_kendaraan) = '$jns_t' LIMIT 1");
                                    $tarif_harian_row = 15000;
                                    if ($q_trf_row && mysqli_num_rows($q_trf_row) > 0) {
                                        $dt_trf_row = mysqli_fetch_assoc($q_trf_row);
                                        if (isset($dt_trf_row['tarif_per_jam'])) { $tarif_harian_row = intval($dt_trf_row['tarif_per_jam']); }
                                        elseif (isset($dt_trf_row['tarif_harian'])) { $tarif_harian_row = intval($dt_trf_row['tarif_harian']); }
                                        elseif (isset($dt_trf_row['tarif'])) { $tarif_harian_row = intval($dt_trf_row['tarif']); }
                                        elseif (isset($dt_trf_row['harga'])) { $tarif_harian_row = intval($dt_trf_row['harga']); }
                                        elseif (isset($dt_trf_row['biaya'])) { $tarif_harian_row = intval($dt_trf_row['biaya']); }
                                    }

                                    $id_parkir_val = $t['id_parkir'];
                                    $plat_val = $t['plat_nomor'];
                                    $jenis_val = ucfirst($t['jenis_kendaraan']);
                                    $area_val = isset($t['nama_area']) ? $t['nama_area'] : 'Umum';
                                    $metode_val = isset($t['metode_pembayaran']) ? $t['metode_pembayaran'] : 'Cash';
                                    
                                    $nominal_db_struk = isset($t['biaya_total']) && intval($t['biaya_total']) > 0 ? intval($t['biaya_total']) : ($tarif_harian_row * $estimasi_pilihan);
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($t['plat_nomor']); ?></td>
                                <td class="text-muted"><?= ucfirst($t['jenis_kendaraan']); ?></td>
                                <td class="td-area"><?= htmlspecialchars($t['nama_area'] ?? 'Umum'); ?></td>
                                <td class="td-waktu"><?= $waktu_masuk; ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark fw-bold countdown-timer" data-masuk="<?= $waktu_masuk; ?>" data-target-jam="<?= $total_jam_target; ?>">
                                        Menghitung...
                                    </span>
                                </td>
                                <td><span class="td-durasi" data-durasi="<?= $estimasi_pilihan; ?>"><?= $estimasi_pilihan; ?></span> Hari (<?= $total_jam_target; ?> Jam)</td>
                                <td>
                                    <span class="badge bg-success denda-badge" data-masuk="<?= $waktu_masuk; ?>" data-target-jam="<?= $total_jam_target; ?>">Rp 0 (Aman)</span>
                                </td>
                                <td class="fw-bold text-info total-tagihan-badge" data-masuk="<?= $waktu_masuk; ?>" data-jenis="<?= $t['jenis_kendaraan']; ?>" data-durasi="<?= $estimasi_pilihan; ?>" data-tarif="<?= $tarif_harian_row; ?>">Rp 0</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- Tombol Cetak Karcis Masuk -->
                                        <button type="button" class="btn btn-sm btn-info text-dark rounded-pill px-2" title="Cetak Karcis Masuk" onclick="tampilkanKarcisMasuk('<?= $id_parkir_val; ?>', '<?= $plat_val; ?>', '<?= $jenis_val; ?>', '<?= $area_val; ?>', '<?= $waktu_masuk; ?>', '<?= $estimasi_pilihan; ?>')">
                                            <i class="fa-solid fa-ticket"></i>
                                        </button>

                                        <!-- Tombol Cetak Bukti Pembayaran / Struk (Dengan Rincian Denda Realtime) -->
                                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-2 btn-struk-inap" title="Cetak Bukti Pembayaran / Struk" data-id="<?= $id_parkir_val; ?>" data-plat="<?= $plat_val; ?>" data-jenis="<?= $jenis_val; ?>" data-area="<?= $area_val; ?>" data-waktu="<?= $waktu_masuk; ?>" data-estimasi="<?= $estimasi_pilihan; ?>" data-metode="<?= $metode_val; ?>" data-tarif="<?= $tarif_harian_row; ?>">
                                            <i class="fa-solid fa-print"></i>
                                        </button>
                                        
                                        <form action="petugas.php" method="POST" class="d-inline">
                                            <input type="hidden" name="id_parkir" value="<?= $t['id_parkir']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 btn-proses-keluar">🚗 Proses Keluar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada kendaraan yang sedang inap.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CETAK STRUK MASUK & BUKTI PEMBAYARAN -->
    <div class="modal fade" id="modalStruk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fs-6 fw-bold">Bukti Pembayaran & Struk Parkir</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="print-area">
                    <div class="text-center mb-3">
                        <h6 class="fw-bold mb-0">STASIUN PARKING SYSTEM</h6>
                        <small class="text-muted">Bukti Pembayaran & Masuk Parkir</small>
                    </div>
                    <hr class="border-secondary">
                    <table class="w-100 small text-light">
                        <tr><td>ID Parkir</td><td>: <span id="s-id"></span></td></tr>
                        <tr><td>Plat Nomor</td><td>: <strong id="s-plat"></strong></td></tr>
                        <tr><td>Kendaraan</td><td>: <span id="s-jenis"></span></td></tr>
                        <tr><td>Area Inap</td><td>: <span id="s-area"></span></td></tr>
                        <tr><td>Waktu Masuk</td><td>: <span id="s-waktu"></span></td></tr>
                        <tr><td>Durasi Rencana</td><td>: <strong id="s-estimasi"></strong> Hari</td></tr>
                        <tr><td>Biaya Dasar</td><td>: <span id="s-biaya-dasar"></span></td></tr>
                        <tr><td>Keterlambatan</td><td>: <span id="s-keterlambatan"></span></td></tr>
                        <tr><td>Denda Keterlambatan</td><td>: <strong class="text-danger" id="s-denda"></strong></td></tr>
                        <tr><td>Pembayaran</td><td>: <strong id="s-metode"></strong></td></tr>
                        <tr><td>Total Bayar</td><td>: <strong class="text-warning" id="s-nominal"></strong></td></tr>
                        <tr><td>Status Bayar</td><td>: <span class="badge bg-success">LUNAS / AKTIF</span></td></tr>
                    </table>
                    <hr class="border-secondary">
                    <div class="text-center text-muted" style="font-size: 0.75rem;">Simpan bukti ini sebagai tanda pembayaran sah.</div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-info btn-sm text-dark fw-semibold" onclick="window.print()">Cetak Bukti Bayar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CETAK KARCIS MASUK -->
    <div class="modal fade" id="modalKarcis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fs-6 fw-bold">Karcis Masuk Parkir</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="print-karcis-area">
                    <div class="text-center mb-3">
                        <h6 class="fw-bold mb-0">KARCIS MASUK STASIUN</h6>
                        <small class="text-muted">Harap Simpan Karcis Ini Dengan Baik</small>
                    </div>
                    <hr class="border-secondary">
                    <table class="w-100 small text-light">
                        <tr><td>ID Karcis</td><td>: <span id="k-id"></span></td></tr>
                        <tr><td>Plat Nomor</td><td>: <strong id="k-plat"></strong></td></tr>
                        <tr><td>Jenis</td><td>: <span id="k-jenis"></span></td></tr>
                        <tr><td>Area Inap</td><td>: <span id="k-area"></span></td></tr>
                        <tr><td>Waktu Masuk</td><td>: <span id="k-waktu"></span></td></tr>
                        <tr><td>Durasi Inap</td><td>: <strong id="k-estimasi"></strong> Hari</td></tr>
                    </table>
                    <hr class="border-secondary">
                    <div class="text-center text-muted" style="font-size: 0.75rem;">Kehilangan karcis dikenakan denda administrasi.</div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-info btn-sm text-dark fw-semibold" onclick="window.print()">Cetak Karcis</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function playAudio(type) {
        let audioId = 'sound-click';
        if (type === 'success') audioId = 'sound-success';
        if (type === 'batal') audioId = 'sound-batal';
        if (type === 'logout') audioId = 'sound-hapus';
        const audio = document.getElementById(audioId);
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => {});
        }
    }

    function hitungEstimasiLive() {
        const selectJenis = document.getElementById('jenis_kendaraan');
        const inputEstimasi = document.getElementById('estimasi_hari');
        const labelBiaya = document.getElementById('live-estimasi-biaya');

        if (selectJenis && selectJenis.selectedIndex > 0 && inputEstimasi) {
            const selectedOption = selectJenis.options[selectJenis.selectedIndex];
            const tarifPerHari = parseInt(selectedOption.getAttribute('data-tarif')) || 0;
            const jumlahHari = parseInt(inputEstimasi.value) || 1;
            const total = tarifPerHari * jumlahHari;

            if(labelBiaya) labelBiaya.innerText = "Rp " + total.toLocaleString('id-ID');
        } else {
            if(labelBiaya) labelBiaya.innerText = "Rp 0";
        }
    }

    const elemJenisKendaraan = document.getElementById('jenis_kendaraan');
    if (elemJenisKendaraan) {
        elemJenisKendaraan.addEventListener('change', function() {
            var jenisPilih = this.value.toLowerCase(); 
            var selectArea = document.getElementById('id_area');
            var options = selectArea.options;

            selectArea.value = ""; 
            var firstMatchIndex = -1;

            for (var i = 0; i < options.length; i++) {
                var opt = options[i];
                if (opt.value === "") continue; 

                var jenisArea = opt.getAttribute('data-jenis') ? opt.getAttribute('data-jenis').toLowerCase() : '';

                if (jenisArea === "" || jenisArea === jenisPilih) {
                    opt.style.display = "block";
                    if (jenisArea === jenisPilih && firstMatchIndex === -1) {
                        firstMatchIndex = i;
                    }
                } else {
                    opt.style.display = "none";
                }
            }

            if (firstMatchIndex !== -1) {
                selectArea.selectedIndex = firstMatchIndex;
            }

            hitungEstimasiLive();
        });
    }

    const elemEstimasiHari = document.getElementById('estimasi_hari');
    if (elemEstimasiHari) {
        elemEstimasiHari.addEventListener('input', hitungEstimasiLive);
    }

    const elemMetodePembayaran = document.getElementById('metode_pembayaran');
    if (elemMetodePembayaran) {
        elemMetodePembayaran.addEventListener('change', function() {
            const qrContainer = document.getElementById('area-qr-container');
            if (this.value === 'Digital') {
                qrContainer.style.display = 'block';
            } else {
                qrContainer.style.display = 'none';
            }
        });
    }

    const formMasukInap = document.getElementById('form-masuk-inap');
    if (formMasukInap) {
        formMasukInap.addEventListener('submit', function(e) {
            playAudio('success');
        });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-proses-keluar');
        if (btn) {
            e.preventDefault();
            const form = btn.closest('form');
            playAudio('warning');

            Swal.fire({
                title: 'Proses Kendaraan Keluar?',
                text: 'Pastikan pembayaran dan denda (jika ada) telah diselesaikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Proses Keluar',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    playAudio('success');
                    form.submit();
                }
            });
        }
    });

    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            const logoutUrl = this.getAttribute('href');
            
            playAudio('logout');

            Swal.fire({
                title: 'Keluar dari Sesi?',
                text: 'Anda akan mengakhiri sesi petugas saat ini.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    playAudio('success');
                    setTimeout(() => {
                        window.location.href = logoutUrl;
                    }, 700);
                }
            });
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const statusParam = urlParams.get('status');

    if (statusParam) {
        if (statusParam.includes('sukses')) {
            playAudio('success');
            let pesan = "Aksi berhasil diproses!";
            if (statusParam === 'sukses_setujui') pesan = "Booking berhasil disetujui dan dimasukkan ke area inap!";
            if (statusParam === 'sukses_batal') pesan = "Booking berhasil dibatalkan.";
            if (statusParam === 'sukses_hapus') pesan = "Data booking berhasil dihapus.";
            if (statusParam === 'sukses_masuk') {
                pesan = "Kendaraan inap berhasil dicatat!";
            }
            if (statusParam === 'sukses_keluar') pesan = "Kendaraan berhasil diproses keluar.";

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: pesan,
                timer: 3000,
                showConfirmButton: false
            });
        } else if (statusParam === 'error_penuh') {
            playAudio('batal');
            Swal.fire({
                icon: 'error',
                title: 'Area Penuh!',
                text: 'Maaf, sisa kuota untuk area parkir yang dipilih sudah habis (0). Kendaraan tidak dapat dimasukkan.',
                confirmButtonColor: '#d33'
            });
        }

        window.history.replaceState({}, document.title, window.location.pathname);
    }

    function updateTimers() {
        const timers = document.querySelectorAll('.countdown-timer');
        const dendaBadges = document.querySelectorAll('.denda-badge');
        const tagihanBadges = document.querySelectorAll('.total-tagihan-badge');

        timers.forEach((el, index) => {
            const waktuMasukStr = el.getAttribute('data-masuk');
            const targetJam = parseInt(el.getAttribute('data-target-jam'));
            
            const masukDate = new Date(waktuMasukStr.replace(/-/g, "/"));
            const targetDate = new Date(masukDate.getTime() + (targetJam * 60 * 60 * 1000));
            const sekarang = new Date();

            const selisihMs = targetDate - sekarang;

            if (selisihMs > 0) {
                const totalDetik = Math.floor(selisihMs / 1000);
                const jam = Math.floor(totalDetik / 3600);
                const menit = Math.floor((totalDetik % 3600) / 60);
                const detik = totalDetik % 60;

                el.className = "badge bg-warning text-dark fw-bold countdown-timer";
                el.innerText = `⏳ ${jam}j ${menit}m ${detik}d lagi`;

                if (dendaBadges[index]) {
                    dendaBadges[index].className = "badge bg-success denda-badge";
                    dendaBadges[index].innerText = "Rp 0 (Aman)";
                }
            } else {
                const lewatMs = Math.abs(selisihMs);
                const lewatJam = Math.floor(lewatMs / (1000 * 60 * 60));
                const lewatHari = Math.ceil(lewatMs / (1000 * 60 * 60 * 24));

                el.className = "badge bg-danger text-white fw-bold countdown-timer";
                el.innerText = `⚠️ Terlambat (${lewatJam} Jam)`;

                const denda = lewatHari * 100000;
                if (dendaBadges[index]) {
                    dendaBadges[index].className = "badge bg-danger denda-badge";
                    dendaBadges[index].innerText = `Rp ${denda.toLocaleString('id-ID')}`;
                }
            }

            if (tagihanBadges[index]) {
                const durasi = parseInt(tagihanBadges[index].getAttribute('data-durasi'));
                const tarifHarian = parseInt(tagihanBadges[index].getAttribute('data-tarif'));
                const biayaDasar = tarifHarian * durasi;

                const selisihKeluarMs = sekarang - masukDate;
                const lamaHariBerjalan = Math.ceil(selisihKeluarMs / (1000 * 60 * 60 * 24));
                const realHari = lamaHariBerjalan < 1 ? 1 : lamaHariBerjalan;

                let dendaLive = 0;
                if (realHari > durasi) {
                    dendaLive = (realHari - durasi) * 100000;
                }

                const totalTagihanLive = biayaDasar + dendaLive;
                tagihanBadges[index].innerText = `Rp ${totalTagihanLive.toLocaleString('id-ID')}`;
            }
        });
    }

    setInterval(updateTimers, 1000);
    updateTimers();

    document.addEventListener('click', function(e) {
        const btnStruk = e.target.closest('.btn-struk-inap');
        if (btnStruk) {
            const id = btnStruk.getAttribute('data-id');
            const plat = btnStruk.getAttribute('data-plat');
            const jenis = btnStruk.getAttribute('data-jenis');
            const area = btnStruk.getAttribute('data-area');
            const waktuMasukStr = btnStruk.getAttribute('data-waktu');
            const durasiRencana = parseInt(btnStruk.getAttribute('data-estimasi')) || 1;
            const metode = btnStruk.getAttribute('data-metode');
            const tarifHarian = parseInt(btnStruk.getAttribute('data-tarif')) || 15000;

            const biayaDasar = tarifHarian * durasiRencana;
            
            const masukDate = new Date(waktuMasukStr.replace(/-/g, "/"));
            const sekarang = new Date();
            const selisihKeluarMs = sekarang - masukDate;
            const lamaHariBerjalan = Math.ceil(selisihKeluarMs / (1000 * 60 * 60 * 24));
            const realHari = lamaHariBerjalan < 1 ? 1 : lamaHariBerjalan;

            let denda = 0;
            let hariTerlambat = 0;
            if (realHari > durasiRencana) {
                hariTerlambat = realHari - durasiRencana;
                denda = hariTerlambat * 100000; 
            }

            const totalBayar = biayaDasar + denda;

            document.getElementById('s-id').innerText = id;
            document.getElementById('s-plat').innerText = plat.toUpperCase();
            document.getElementById('s-jenis').innerText = jenis;
            document.getElementById('s-area').innerText = area ? area : 'Umum';
            document.getElementById('s-waktu').innerText = waktuMasukStr;
            document.getElementById('s-estimasi').innerText = durasiRencana;
            document.getElementById('s-biaya-dasar').innerText = "Rp " + biayaDasar.toLocaleString('id-ID');
            document.getElementById('s-keterlambatan').innerText = hariTerlambat > 0 ? `${hariTerlambat} Hari` : "Tidak ada";
            document.getElementById('s-denda').innerText = "Rp " + denda.toLocaleString('id-ID');
            document.getElementById('s-metode').innerText = metode;
            document.getElementById('s-nominal').innerText = "Rp " + totalBayar.toLocaleString('id-ID');

            var myModal = new bootstrap.Modal(document.getElementById('modalStruk'));
            myModal.show();
        }
    });

    function tampilkanKarcisMasuk(id, plat, jenis, area, waktu, estimasi) {
        document.getElementById('k-id').innerText = id;
        document.getElementById('k-plat').innerText = plat.toUpperCase();
        document.getElementById('k-jenis').innerText = jenis;
        document.getElementById('k-area').innerText = area ? area : 'Umum';
        document.getElementById('k-waktu').innerText = waktu;
        document.getElementById('k-estimasi').innerText = estimasi;

        var karcisModal = new bootstrap.Modal(document.getElementById('modalKarcis'));
        karcisModal.show();
    }
    </script>
</body>
</html>

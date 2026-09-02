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
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user_member', '$id_area', '$durasi_hari')");
            } else {
                mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) 
                                     VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user_member', '$id_area')");
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
    
    $estimasi_hari = intval($_POST['estimasi_hari']);
    if ($estimasi_hari < 1) { $estimasi_hari = 1; }
    if ($estimasi_hari > 3) { $estimasi_hari = 3; }

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

    $q_tarif = mysqli_query($conn, "SELECT id_tarif FROM tb_tarif LIMIT 1");
    $id_tarif = 1;
    if ($q_tarif && mysqli_num_rows($q_tarif) > 0) {
        $dt = mysqli_fetch_assoc($q_tarif);
        $id_tarif = $dt['id_tarif'];
    }

    $cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM tb_transaksi LIKE 'durasi_jam'");
    if ($cek_kolom && mysqli_num_rows($cek_kolom) > 0) {
        mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area, durasi_jam) 
                             VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area', '$estimasi_hari')");
    } else {
        mysqli_query($conn, "INSERT INTO tb_transaksi (id_parkir, id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) 
                             VALUES ('$id_parkir', '$id_kendaraan', '$waktu_masuk', '$id_tarif', 'masuk', '$id_user', '$id_area')");
    }
    
    $q_nm_area = mysqli_query($conn, "SELECT nama_area FROM tb_area_parkir WHERE id_area = '$id_area' LIMIT 1");
    $dt_nm_area = ($q_nm_area && mysqli_num_rows($q_nm_area) > 0) ? mysqli_fetch_assoc($q_nm_area)['nama_area'] : 'Area Parkir';

    $url_redirect = "petugas.php?status=sukses_masuk&id_parkir=" . $id_parkir . 
                    "&plat=" . urlencode($plat) . 
                    "&jenis=" . urlencode(ucfirst($jenis)) . 
                    "&area=" . urlencode($dt_nm_area) . 
                    "&estimasi=" . $estimasi_hari;
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

        $tarif_harian = ($jenis == 'mobil') ? 25000 : 15000;
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
        
        .btn-proses-keluar {
            position: relative;
            z-index: 10;
            cursor: pointer;
        }

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
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; color: #000; }
        }
    </style>
</head>
<body>
    <!-- PANGGIL FILE AUDIO DARI FOLDER -->
    <audio id="sound-click" src="img/click.mp3" preload="auto"></audio>
    <audio id="sound-success" src="img/berhasil.MPEG" preload="auto"></audio>
    <audio id="sound-batal" src="img/salah.MPEG" preload="auto"></audio>
    <audio id="sound-hapus" src="img/salah.MPEG" preload="auto"></audio>

    <script>
    function playAudio(type) {
        try {
            let audioElem = null;
            if (type === 'success') {
                audioElem = document.getElementById('sound-success');
            } else if (type === 'batal' || type === 'warning') {
                audioElem = document.getElementById('sound-batal');
            } else if (type === 'hapus') {
                audioElem = document.getElementById('sound-hapus');
            } else if (type === 'logout') {
                audioElem = document.getElementById('sound-logout');
            } else {
                audioElem = document.getElementById('sound-click');
            }

            if (audioElem) {
                audioElem.currentTime = 0;
                audioElem.play().catch(e => {
                    console.log("Audio play blocked or error: ", e);
                });
            }
        } catch(e) {
            console.log("Audio Error: ", e);
        }
    }
    </script>

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
            <div class="card-header py-3 px-4 fw-bold">🚗 Input Kendaraan Inap Masuk Manual (Mobil: Rp 25k/hari | Motor: Rp 15k/hari)</div>
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
                            <option value="motor">Motor (Rp 15.000 / hari)</option>
                            <option value="mobil">Mobil (Rp 25.000 / hari)</option>
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
                            ?>
                                <option value="<?= $a['id_area']; ?>">
                                    <?= htmlspecialchars($a['nama_area']); ?> (Sisa: <?= $sisa; ?> dari <?= $a['kapasitas']; ?>)
                                </option>
                            <?php 
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">ESTIMASI INAP (1 - 3 HARI)</label>
                        <input type="number" name="estimasi_hari" class="form-control" value="1" min="1" max="3" required>
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
                                <th class="text-center">Aksi / Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($query_transaksi_inap && mysqli_num_rows($query_transaksi_inap) > 0) {
                                while($t = mysqli_fetch_assoc($query_transaksi_inap)) {
                                    $waktu_masuk = $t['waktu_masuk'];
                                    $estimasi_pilihan = isset($t['durasi_jam']) && intval($t['durasi_jam']) > 0 ? intval($t['durasi_jam']) : 1;
                                    $total_jam_target = $estimasi_pilihan * 24; 
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($t['plat_nomor']); ?></td>
                                <td class="text-muted"><?= ucfirst($t['jenis_kendaraan']); ?></td>
                                <td><?= htmlspecialchars($t['nama_area'] ?? 'Umum'); ?></td>
                                <td><?= $waktu_masuk; ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark fw-bold countdown-timer" data-masuk="<?= $waktu_masuk; ?>" data-target-jam="<?= $total_jam_target; ?>">
                                        Menghitung...
                                    </span>
                                </td>
                                <td><?= $estimasi_pilihan; ?> Hari (<?= $total_jam_target; ?> Jam)</td>
                                <td>
                                    <span class="badge bg-success denda-badge" data-masuk="<?= $waktu_masuk; ?>" data-target-jam="<?= $total_jam_target; ?>">Rp 0 (Aman)</span>
                                </td>
                                <td class="fw-bold text-info total-tagihan-badge" data-masuk="<?= $waktu_masuk; ?>" data-jenis="<?= $t['jenis_kendaraan']; ?>" data-durasi="<?= $estimasi_pilihan; ?>">Rp 0</td>
                                <td class="text-center">
                                    <form action="petugas.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id_parkir" value="<?= $t['id_parkir']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 btn-proses-keluar">🚗 Proses Keluar</button>
                                    </form>
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

    <!-- MODAL CETAK STRUK MASUK -->
    <div class="modal fade" id="modalStruk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fs-6 fw-bold">Struk Parkir Inap Stasiun</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="print-area">
                    <div class="text-center mb-3">
                        <h6 class="fw-bold mb-0">STASIUN PARKING SYSTEM</h6>
                        <small class="text-muted">Bukti Masuk Parkir Inap</small>
                    </div>
                    <hr class="border-secondary">
                    <table class="w-100 small text-light">
                        <tr><td>ID Parkir</td><td>: <span id="s-id"></span></td></tr>
                        <tr><td>Plat Nomor</td><td>: <strong id="s-plat"></strong></td></tr>
                        <tr><td>Kendaraan</td><td>: <span id="s-jenis"></span></td></tr>
                        <tr><td>Area Inap</td><td>: <span id="s-area"></span></td></tr>
                        <tr><td>Waktu Masuk</td><td>: <span id="s-waktu"></span></td></tr>
                        <tr><td>Estimasi Inap</td><td>: <strong id="s-estimasi"></strong> Hari</td></tr>
                    </table>
                    <hr class="border-secondary">
                    <div class="text-center text-muted" style="font-size: 0.75rem;">Simpan struk ini untuk pengambilan kendaraan.</div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-info btn-sm text-dark fw-semibold" onclick="window.print()">Cetak Struk</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // 1. Tombol SETUJU
    document.querySelectorAll('.btn-aksi-setuju').forEach(btn => {
        btn.addEventListener('click', function(e) {
            playAudio('success');
        });
    });

    // 2. Tombol BATAL
    document.querySelectorAll('.btn-aksi-batal').forEach(btn => {
        btn.addEventListener('click', function(e) {
            playAudio('batal');
        });
    });

    // 3. Tombol HAPUS
    document.querySelectorAll('.btn-aksi-hapus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            playAudio('hapus');
        });
    });

    // 4. Tombol SIMPAN MASUK INAP
    const formMasukInap = document.getElementById('form-masuk-inap');
    if (formMasukInap) {
        formMasukInap.addEventListener('submit', function(e) {
            playAudio('success');
        });
    }

    // 5. Tombol AKSI KELUAR / PROSES KELUAR
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

    // 6. Tombol LOGOUT
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

    // Notifikasi status sukses dari URL parameter
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
                const idParkirBaru = urlParams.get('id_parkir');
                const platBaru = urlParams.get('plat');
                const jenisBaru = urlParams.get('jenis');
                const areaBaru = urlParams.get('area');
                const estimasiBaru = urlParams.get('estimasi');

                if(idParkirBaru) {
                    const d = new Date();
                    const waktuFormatted = d.getFullYear() + '-' + 
                        String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(d.getDate()).padStart(2, '0') + ' ' + 
                        String(d.getHours()).padStart(2, '0') + ':' + 
                        String(d.getMinutes()).padStart(2, '0') + ':' + 
                        String(d.getSeconds()).padStart(2, '0');

                    tampilkanStrukMasuk(
                        idParkirBaru, 
                        platBaru ? platBaru : '-', 
                        jenisBaru ? jenisBaru : '-', 
                        areaBaru ? areaBaru : 'Umum', 
                        waktuFormatted, 
                        estimasiBaru ? estimasiBaru : '1'
                    );
                }
            }
            if (statusParam === 'sukses_keluar') pesan = "Kendaraan berhasil diproses keluar.";

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: pesan,
                timer: 3000,
                showConfirmButton: false
            });

            window.history.replaceState({}, document.title, window.location.pathname);
        }
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
                const jenis = tagihanBadges[index].getAttribute('data-jenis').toLowerCase();
                const tarifHarian = (jenis === 'mobil') ? 25000 : 15000;
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

    function tampilkanStrukMasuk(id, plat, jenis, area, waktu, estimasi) {
        document.getElementById('s-id').innerText = id;
        document.getElementById('s-plat').innerText = plat.toUpperCase();
        document.getElementById('s-jenis').innerText = jenis;
        document.getElementById('s-area').innerText = area ? area : 'Umum';
        document.getElementById('s-waktu').innerText = waktu;
        document.getElementById('s-estimasi').innerText = estimasi;

        var myModal = new bootstrap.Modal(document.getElementById('modalStruk'));
        myModal.show();
    }
    </script>
</body>
</html>
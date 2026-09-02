<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'owner') {
    header("Location: index.php");
    exit;
}

// Proses Hapus Riwayat (Soft Delete: Mengubah status tampil jadi 0 agar baris hilang dari tabel tapi uang tetap utuh)
if (isset($_GET['aksi'])) {
    $dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-d');
    $sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
    $bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

    if ($_GET['aksi'] == 'hapus_satu' && isset($_GET['id'])) {
        $id_transaksi = intval($_GET['id']);
        $query_hapus = "UPDATE tb_transaksi SET tampil = 0 WHERE id_transaksi = $id_transaksi AND status = 'keluar'";
        if (mysqli_query($conn, $query_hapus)) {
            $_SESSION['notif'] = "Riwayat transaksi berhasil disembunyikan dari tabel (Pendapatan tetap utuh)!";
            $_SESSION['notif_type'] = "success";
        } else {
            $_SESSION['notif'] = "Gagal memperbarui data!";
            $_SESSION['notif_type'] = "danger";
        }
    } elseif ($_GET['aksi'] == 'hapus_semua') {
        $query_hapus_semua = "UPDATE tb_transaksi SET tampil = 0 WHERE status = 'keluar' AND DATE(waktu_keluar) BETWEEN '$dari' AND '$sampai'";
        if (mysqli_query($conn, $query_hapus_semua)) {
            $_SESSION['notif'] = "Semua riwayat periode ($dari s/d $sampai) berhasil dibersihkan dari tabel!";
            $_SESSION['notif_type'] = "success";
        } else {
            $_SESSION['notif'] = "Gagal memperbarui data!";
            $_SESSION['notif_type'] = "danger";
        }
    }
    header("Location: owner.php?dari=$dari&sampai=$sampai&bulan=$bulan_pilih");
    exit;
}

// Filter Tanggal
$dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-d');
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

// Filter Bulan (Format: YYYY-MM)
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun_bulanan = date('Y', strtotime($bulan_pilih));
$angka_bulan = date('m', strtotime($bulan_pilih));

// Query Kendaraan Keluar (Tabel Riwayat - Hanya menampilkan data dengan tampil = 1)
$query = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
          FROM tb_transaksi t 
          JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
          JOIN tb_area_parkir a ON t.id_area = a.id_area 
          WHERE t.status = 'keluar' 
          AND (t.tampil = 1 OR t.tampil IS NULL)
          AND DATE(t.waktu_keluar) BETWEEN '$dari' AND '$sampai' 
          ORDER BY t.id_transaksi DESC";
$laporan = mysqli_query($conn, $query);

// Query Kendaraan di Dalam (Masih Parkir)
$query_didalam = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
                  FROM tb_transaksi t 
                  JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                  JOIN tb_area_parkir a ON t.id_area = a.id_area 
                  WHERE t.status = 'masuk' 
                  ORDER BY t.waktu_masuk DESC";
$laporan_didalam = mysqli_query($conn, $query_didalam);

// STATISTIK KARTU ATAS: PENDAPATAN AKUMULASI UTUH
$q_total_asli = mysqli_query($conn, "SELECT SUM(biaya_total) as total FROM tb_transaksi WHERE status = 'keluar'");
$d_total_asli = mysqli_fetch_assoc($q_total_asli);
$total_pendapatan_akumulasi = $d_total_asli['total'] ? $d_total_asli['total'] : 0;

// Total Kendaraan Keluar Berdasarkan Filter Tanggal di Tabel
$q_total = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM tb_transaksi WHERE status = 'keluar' AND (tampil = 1 OR tampil IS NULL) AND DATE(waktu_keluar) BETWEEN '$dari' AND '$sampai'");
$d_total = mysqli_fetch_assoc($q_total);
$total_kendaraan = $d_total['jumlah'] ? $d_total['jumlah'] : 0;

// Hitung Total Pendapatan Khusus Rekap Bulanan
$q_bulanan = mysqli_query($conn, "SELECT SUM(biaya_total) as total, COUNT(*) as jumlah FROM tb_transaksi WHERE status = 'keluar' AND MONTH(waktu_keluar) = '$angka_bulan' AND YEAR(waktu_keluar) = '$tahun_bulanan'");
$d_bulanan = mysqli_fetch_assoc($q_bulanan);
$total_pendapatan_bulan = $d_bulanan['total'] ? $d_bulanan['total'] : 0;
$total_kendaraan_bulan = $d_bulanan['jumlah'] ? $d_bulanan['jumlah'] : 0;

// Hitung Total Kendaraan di Dalam
$q_didalam_count = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM tb_transaksi WHERE status = 'masuk'");
$d_didalam_count = mysqli_fetch_assoc($q_didalam_count);
$total_didalam = $d_didalam_count['jumlah'] ? $d_didalam_count['jumlah'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - Stasiun Parking Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-station: #0f172a;
            --accent-yellow: #f59e0b;
            --card-glass: rgba(255, 255, 255, 0.95);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(ellipse at 80% 10%, rgba(245, 158, 11, 0.08) 0%, transparent 40%),
                radial-gradient(ellipse at 10% 90%, rgba(15, 23, 42, 0.06) 0%, transparent 40%),
                linear-gradient(rgba(241, 245, 249, 0.7), rgba(241, 245, 249, 0.7)),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%2394a3b8' fill-opacity='0.05' fill-rule='evenodd'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E");
            color: #1e293b;
            min-height: 100vh;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #090d16 0%, #1e293b 100%);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
            border-bottom: 3px solid var(--accent-yellow);
        }
        .card {
            border-radius: 1.25rem;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
            transition: all 0.3s ease;
            background: var(--card-glass);
            backdrop-filter: blur(10px);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.1);
        }
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            font-size: 0.925rem;
            transition: all 0.2s;
            background-color: #ffffff;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
            border-color: var(--accent-yellow);
        }
        .table-custom th {
            background-color: #0f172a !important;
            color: #f8fafc;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            padding: 1.2rem 1rem;
            border: none;
        }
        .table-custom td {
            padding: 1.1rem 1rem;
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(245, 158, 11, 0.03);
        }
        .badge {
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body>

    <!-- ELEMEN AUDIO DIJAMIN DIBACA BROWSER -->
    <audio id="sound-success" src="img/berhasil.MPEG" preload="auto"></audio

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-3 sticky-top no-print">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center gap-3" href="#">
                <span class="bg-warning text-dark p-2.5 rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;"><i class="fa-solid fa-train-subway fs-5"></i></span>
                <div>
                    <span class="d-block tracking-wide">STASIUN PARKING</span>
                    <small class="text-warning fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.15em;">COMMAND CENTER OWNER</small>
                </div>
            </a>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <div class="text-white fw-semibold small"><i class="fa-solid fa-user-shield text-warning me-1"></i> Administrator Owner</div>
                    <div class="text-white-50" style="font-size: 0.72rem;">Sistem Verifikasi Aktif</div>
                </div>
                <a href="logout.php" class="btn btn-outline-warning btn-sm px-4 rounded-pill fw-semibold shadow-sm">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <?php if (isset($_SESSION['notif'])): ?>
            <div class="alert alert-<?= $_SESSION['notif_type']; ?> alert-dismissible fade show no-print shadow-sm border-0 rounded-4 p-4 mb-4" role="alert" style="background: <?= $_SESSION['notif_type'] == 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;'; ?>">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid <?= $_SESSION['notif_type'] == 'success' ? 'fa-circle-check fs-4' : 'fa-triangle-exclamation fs-4'; ?>"></i>
                    <div class="fw-medium"><?= $_SESSION['notif']; ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php if ($_SESSION['notif_type'] == 'success'): ?>
                <script>
                    window.addEventListener('DOMContentLoaded', () => {
                        const audioOwner = document.getElementById('sound-success');
                        if (audioOwner) {
                            audioOwner.currentTime = 0;
                            audioOwner.play().catch(e => console.log("Audio diblokir browser."));
                        }
                    });
                </script>
            <?php endif; ?>
            <?php 
                unset($_SESSION['notif']);
                unset($_SESSION['notif_type']);
            ?>
        <?php endif; ?>

        <div class="row mb-4 align-items-center no-print">
            <div class="col-md-7">
                <h2 class="fw-extrabold text-dark mb-1 d-flex align-items-center gap-2">
                    <span>Terminal Laporan & Monitoring</span>
                    <span class="badge bg-dark text-warning fs-6 border border-warning border-opacity-25 px-2.5 py-1">LIVE</span>
                </h2>
                <p class="text-secondary small mb-0">Pusat kendali rekapitulasi transaksi real-time, status kapasitas parkir internal stasiun, dan audit keuangan.</p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <button type="button" onclick="window.print()" class="btn btn-warning text-dark px-4 py-2.5 rounded-pill shadow-sm fw-bold">
                    <i class="fa-solid fa-print me-2"></i> Cetak Laporan Stasiun
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4 no-print">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <form method="GET" class="row g-3 align-items-end">
                            <input type="hidden" name="bulan" value="<?= $bulan_pilih; ?>">
                            <div class="col-12">
                                <span class="fw-bold text-dark small text-uppercase tracking-wider"><i class="fa-regular fa-calendar-days text-warning me-2"></i> Filter Berdasarkan Rentang Tanggal</span>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-secondary fw-semibold">DARI</label>
                                <input type="date" name="dari" value="<?= $dari; ?>" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-secondary fw-semibold">SAMPAI</label>
                                <input type="date" name="sampai" value="<?= $sampai; ?>" class="form-control" required>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-dark w-100 fw-semibold shadow-sm rounded-3 py-2">Filter</button>
                                <a href="owner.php" class="btn btn-light w-50 fw-semibold text-secondary border rounded-3 d-flex align-items-center justify-content-center">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
                    <div class="card-body p-4">
                        <form method="GET" class="row g-3 align-items-end">
                            <input type="hidden" name="dari" value="<?= $dari; ?>">
                            <input type="hidden" name="sampai" value="<?= $sampai; ?>">
                            <div class="col-12">
                                <span class="fw-bold text-warning small text-uppercase tracking-wider"><i class="fa-solid fa-chart-pie me-2"></i> Rekapitulasi Per Bulan</span>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small text-white-50 fw-semibold">PILIH BULAN & TAHUN</label>
                                <input type="month" name="bulan" value="<?= $bulan_pilih; ?>" class="form-control bg-dark text-white border-secondary" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-warning text-dark fw-bold w-100 shadow-sm rounded-3 py-2">Cek Bulan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card text-white mb-4 shadow-sm border-0 no-print" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-left: 5px solid var(--accent-yellow) !important;">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-3 mb-md-0">
                    <span class="badge bg-warning text-dark mb-2 px-3 py-1 rounded-pill fw-bold"><i class="fa-solid fa-receipt me-1"></i> LAPORAN BULANAN AKTIF</span>
                    <h4 class="fw-bold mb-1 text-white">Akumulasi Bulan <?= date('F Y', strtotime($bulan_pilih . '-01')); ?></h4>
                    <p class="text-light opacity-75 small mb-0">Total volume kendaraan keluar pada bulan terpilih: <strong class="text-warning"><?= number_format($total_kendaraan_bulan, 0, ',', '.'); ?> Unit</strong></p>
                </div>
                <div class="text-md-end bg-white bg-opacity-10 px-4 py-3 rounded-4 border border-light border-opacity-10">
                    <div class="small text-white-50 text-uppercase tracking-wider mb-1">Pendapatan Bersih Bulan Ini</div>
                    <h2 class="fw-extrabold text-warning mb-0">Rp <?= number_format($total_pendapatan_bulan, 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-white border-start border-success border-4 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-uppercase fw-bold text-success small tracking-wider">Total Pendapatan Stasiun</span>
                                <h3 class="fw-extrabold text-dark mt-1 mb-0">Rp <?= number_format($total_pendapatan_akumulasi, 0, ',', '.'); ?></h3>
                            </div>
                            <div class="fs-3 text-success bg-success bg-opacity-10 p-3 rounded-4"><i class="fa-solid fa-vault"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-white border-start border-primary border-4 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-uppercase fw-bold text-primary small tracking-wider">Kendaraan Keluar (Filter)</span>
                                <h3 class="fw-extrabold text-dark mt-1 mb-0"><?= number_format($total_kendaraan, 0, ',', '.'); ?> <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                            </div>
                            <div class="fs-3 text-primary bg-primary bg-opacity-10 p-3 rounded-4"><i class="fa-solid fa-right-from-bracket"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-white border-start border-warning border-4 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="text-uppercase fw-bold text-warning small tracking-wider">Kendaraan di Dalam</span>
                                <h3 class="fw-extrabold text-dark mt-1 mb-0"><?= number_format($total_didalam, 0, ',', '.'); ?> <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                            </div>
                            <div class="fs-3 text-warning bg-warning bg-opacity-10 p-3 rounded-4"><i class="fa-solid fa-square-parking"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-5 border-0">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-location-arrow text-warning"></i> Kendaraan yang Masih di Dalam Area Parkir (Real-Time)
                </span>
                <span class="badge bg-warning bg-opacity-15 text-dark px-3 py-2 rounded-pill fw-bold border border-warning">Total Aktif: <?= $total_didalam; ?> Unit</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Plat Nomor</th>
                                <th>Jenis Kendaraan</th>
                                <th>Area Parkir</th>
                                <th class="pe-4">Waktu Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_in = 1;
                            if(mysqli_num_rows($laporan_didalam) > 0):
                                while($row_in = mysqli_fetch_assoc($laporan_didalam)): 
                            ?>
                            <tr>
                                <td class="ps-4 text-muted fw-semibold"><?= $no_in++; ?></td>
                                <td><span class="fw-bold text-dark badge bg-light border px-2.5 py-1.5 rounded-2"><?= htmlspecialchars($row_in['plat_nomor']); ?></span></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1.5 rounded-2"><?= ucwords(str_replace('_', ' ', $row_in['jenis_kendaraan'])); ?></span></td>
                                <td class="fw-medium text-dark"><?= htmlspecialchars($row_in['nama_area']); ?></td>
                                <td class="pe-4 text-muted small"><i class="fa-regular fa-clock me-1 text-secondary"></i> <?= $row_in['waktu_masuk']; ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <div class="mb-2 fs-3 text-secondary opacity-50"><i class="fa-regular fa-folder-open"></i></div>
                                    Tidak ada kendaraan di dalam area parkir saat ini.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="fw-bold text-dark me-2 d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-invoice-dollar text-success"></i> Detail Riwayat Transaksi Parkir Keluar
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill small">Periode: <?= $dari; ?> s/d <?= $sampai; ?></span>
                </div>
                <?php if(mysqli_num_rows($laporan) > 0): ?>
                    <a href="owner.php?aksi=hapus_semua&dari=<?= $dari; ?>&sampai=<?= $sampai; ?>&bulan=<?= $bulan_pilih; ?>" 
                       onclick="return confirmHapusSemua(event, this.href);" 
                       class="btn btn-outline-danger btn-sm rounded-pill px-3 no-print fw-semibold">
                       <i class="fa-solid fa-trash-can me-1"></i> Bersihkan Tabel Periode Ini
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Plat Nomor</th>
                                <th>Jenis Kendaraan</th>
                                <th>Area Parkir</th>
                                <th>Waktu Masuk</th>
                                <th>Waktu Keluar</th>
                                <th>Durasi</th>
                                <th>Biaya Total</th>
                                <th class="pe-4 text-end no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($laporan) > 0):
                                while($row = mysqli_fetch_assoc($laporan)): 
                            ?>
                            <tr>
                                <td class="ps-4 text-muted fw-semibold"><?= $no++; ?></td>
                                <td><span class="fw-bold text-dark badge bg-light border px-2.5 py-1.5 rounded-2"><?= htmlspecialchars($row['plat_nomor']); ?></span></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1.5 rounded-2"><?= ucwords(str_replace('_', ' ', $row['jenis_kendaraan'])); ?></span></td>
                                <td class="fw-medium text-dark"><?= htmlspecialchars($row['nama_area']); ?></td>
                                <td class="text-muted small"><?= $row['waktu_masuk']; ?></td>
                                <td class="text-muted small"><?= $row['waktu_keluar']; ?></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-2"><?= isset($row['durasi_jam']) ? $row['durasi_jam'] : '-'; ?> Hari</span></td>
                                <td class="fw-bold text-success">Rp <?= number_format($row['biaya_total'], 0, ',', '.'); ?></td>
                                <td class="pe-4 text-end no-print">
                                    <a href="owner.php?aksi=hapus_satu&id=<?= $row['id_transaksi']; ?>&dari=<?= $dari; ?>&sampai=<?= $sampai; ?>&bulan=<?= $bulan_pilih; ?>" 
                                       onclick="return confirmHapus(event, this.href);" 
                                       class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-medium">
                                       <i class="fa-solid fa-eye-slash me-1"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <div class="mb-2 fs-3 text-secondary opacity-50"><i class="fa-regular fa-folder-open"></i></div>
                                    Tidak ada data transaksi keluar pada rentang tanggal tersebut.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- SCRIPT AUDIO TERBARU (DENGAN JEDA DELAY REDIRECT) -->
    <script>
        function confirmHapus(event, url) {
            event.preventDefault(); // Tahan dulu redirect bawaan
            
            if (confirm("Sembunyikan baris ini dari tabel? (Total pendapatan uang stasiun akan tetap UTUH)")) {
                const audio = document.getElementById('sound-hapus');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log("Gagal membunyikan audio:", e));
                }
                
                // Beri jeda 400 milidetik agar suara sempat berbunyi sebelum halaman berpindah
                setTimeout(() => {
                    window.location.href = url;
                }, 400);
            }
        }

        function confirmHapusSemua(event, url) {
            event.preventDefault(); // Tahan dulu redirect bawaan
            
            if (confirm("Bersihkan seluruh riwayat pada periode ini dari tabel? (Total pendapatan uang stasiun akan tetap UTUH)")) {
                const audio = document.getElementById('sound-hapus');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log("Gagal membunyikan audio:", e));
                }
                
                // Beri jeda 400 milidetik agar suara sempat berbunyi sebelum halaman berpindah
                setTimeout(() => {
                    window.location.href = url;
                }, 400);
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
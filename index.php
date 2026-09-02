<?php
include 'koneksi.php';

// =========================================================
// AUTO-CREATE TABEL AMAN (Mencegah error tabel tidak ditemukan)
// =========================================================
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `tb_area_parkir` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_area` VARCHAR(100) NOT NULL,
  `jenis_kendaraan` VARCHAR(50) DEFAULT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `tb_tarif` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jenis_kendaraan` VARCHAR(50) NOT NULL,
  `tarif_per_jam` INT NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `tb_ulasan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL,
  `komentar` TEXT NOT NULL,
  `tanggal` DATETIME NOT NULL
)");

// Cek data area parkir
$cek_area = mysqli_query($conn, "SELECT COUNT(*) as jml FROM tb_area_parkir");
$r_area = mysqli_fetch_assoc($cek_area);
if ($r_area['jml'] == 0) {
    mysqli_query($conn, "INSERT INTO tb_area_parkir (nama_area, jenis_kendaraan) VALUES ('Area Parkir Selatan (Utama)', 'Mobil'), ('Area Parkir Utara (Gedung)', 'Motor'), ('Area VIP / Khusus', 'Semua Kendaraan')");
}

// Cek data tarif
$cek_tarif = mysqli_query($conn, "SELECT COUNT(*) as jml FROM tb_tarif");
$r_tarif = mysqli_fetch_assoc($cek_tarif);
if ($r_tarif['jml'] == 0) {
    mysqli_query($conn, "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES ('Motor', 15000), ('Mobil', 25000), ('Bus / Truk', 50000)");
}

$area_parkir = mysqli_query($conn, "SELECT * FROM tb_area_parkir");
$tarif_parkir = mysqli_query($conn, "SELECT * FROM tb_tarif");

// =========================================================================
// KONEKSI GRAFIK 12 BULAN PENUH (Agar grafik tidak polos/hanya 1 titik)
// =========================================================================
$tahun_aktif = date('Y'); // Mengambil tahun berjalan (2026)

// Inisialisasi 12 bulan dengan nilai 0 agar garis grafik terbentuk penuh dari Jan - Des
$data_bulanan = [
    '01' => 0, '02' => 0, '03' => 0, '04' => 0, 
    '05' => 0, '06' => 0, '07' => 0, '08' => 0, 
    '09' => 0, '10' => 0, '11' => 0, '12' => 0
];

$q_transaksi = mysqli_query($conn, "SELECT waktu_keluar, biaya_total FROM tb_transaksi WHERE status = 'keluar'");
if ($q_transaksi) {
    while ($row = mysqli_fetch_assoc($q_transaksi)) {
        if (!empty($row['waktu_keluar']) && $row['waktu_keluar'] != '0000-00-00 00:00:00') {
            $timestamp = strtotime($row['waktu_keluar']);
            if ($timestamp) {
                $thn = date('Y', $timestamp);
                $bln = date('m', $timestamp);
                
                // Jika transaksi di tahun ini, masukkan ke bulan yang sesuai
                if ($thn == $tahun_aktif && isset($data_bulanan[$bln])) {
                    $data_bulanan[$bln] += (int)$row['biaya_total'];
                }
            }
        }
    }
}

$grafik_label = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$grafik_data = array_values($data_bulanan);

// Proses Ulasan
$pesan_sukses = "";
if (isset($_POST['kirim_ulasan'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $rating = (int)$_POST['rating'];
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    
    $insert_ulasan = mysqli_query($conn, "INSERT INTO tb_ulasan (nama, rating, komentar, tanggal) VALUES ('$nama', '$rating', '$komentar', NOW())");
    if ($insert_ulasan) {
        $pesan_sukses = "Terima kasih! Ulasan Anda berhasil dikirim.";
    }
}

// HITUNG RATA-RATA RATING SECARA DINAMIS
$q_avg_rating = mysqli_query($conn, "SELECT AVG(rating) as rata_rata, COUNT(*) as total_ulasan FROM tb_ulasan");
$d_avg = mysqli_fetch_assoc($q_avg_rating);
$rating_angka = $d_avg['rata_rata'] ? number_format($d_avg['rata_rata'], 1, '.', '') : '5.0';
$jumlah_ulasan = $d_avg['total_ulasan'] ? $d_avg['total_ulasan'] : 0;

$q_ulasan = mysqli_query($conn, "SELECT * FROM tb_ulasan ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id" style="scroll-behavior: smooth;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Parkir - Stasiun Balapan Solo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            background-attachment: fixed;
            color: #e2e8f0;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
        }

        .sidebar-brand {
            font-size: 1.15rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }

        .sidebar-menu .nav-link {
            color: #94a3b8;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
        }

        .sidebar-menu .nav-link:hover, 
        .sidebar-menu .nav-link.active {
            color: #fff;
            background: rgba(59, 130, 246, 0.15);
            border-left: 4px solid #3b82f6;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            .sidebar-brand span, .sidebar-menu span, .sidebar-footer span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
                padding: 15px;
            }
        }
        
        .card { 
            border-radius: 1rem; 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            transition: transform 0.2s, box-shadow 0.2s;
            color: #e2e8f0;
        }
        .card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .table {
            color: #e2e8f0 !important;
            margin-bottom: 0;
        }
        .table-light, .table > :not(caption) > * > * {
            background-color: transparent !important;
            color: #e2e8f0 !important;
        }
        .table-light th {
            color: #94a3b8 !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: rgba(15, 23, 42, 0.4) !important;
        }
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table-hover > tbody > tr:hover {
            background-color: rgba(255, 255, 255, 0.04) !important;
            color: #fff;
        }
        .table>:not(caption)>*>* {
            border-bottom-color: rgba(255, 255, 255, 0.06);
            padding: 1rem 0.75rem;
        }

        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #3b82f6;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        .form-control::placeholder {
            color: #64748b;
        }

        .accordion-item {
            background-color: rgba(30, 41, 59, 0.8) !important;
            color: #e2e8f0 !important;
        }
        .accordion-button {
            background-color: rgba(30, 41, 59, 0.9) !important;
            color: #fff !important;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(15, 23, 42, 0.9) !important;
            color: #60a5fa !important;
        }
        .accordion-body {
            background-color: rgba(15, 23, 42, 0.5) !important;
            color: #94a3b8 !important;
        }

        .list-group-item {
            background-color: transparent !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #e2e8f0 !important;
        }

        .hero-img-container { position: relative; overflow: hidden; max-height: 350px; border-radius: 1.25rem; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .hero-img-container img { width: 100%; height: 350px; object-fit: cover; filter: brightness(75%); transition: transform 0.5s ease; }
        .hero-img-container:hover img { transform: scale(1.03); }
        .hero-text-overlay { position: absolute; bottom: 30px; left: 30px; color: white; text-shadow: 0 2px 6px rgba(0,0,0,0.8); }
        
        .hero-video-container { position: relative; overflow: hidden; border-radius: 1.25rem; background: #000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .hero-video-container video { width: 100%; max-height: 400px; object-fit: cover; display: block; filter: brightness(95%); }

        .help-float-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            z-index: 1050;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .help-float-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.6);
            color: white;
        }
        .help-modal-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .modal-content {
            background-color: #1e293b;
            color: #e2e8f0;
        }
        .bg-light {
            background-color: rgba(15, 23, 42, 0.6) !important;
        }
        .text-dark {
            color: #f8fafc !important;
        }
        .text-muted {
            color: #94a3b8 !important;
        }
        .border {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div>
            <a class="sidebar-brand" href="index.php">
                <i class="fas fa-parking text-primary fa-lg"></i> <span>Parkir Balapan</span>
            </a>
            
            <ul class="sidebar-menu">
                <li><a class="nav-link active" href="index.php"><i class="fas fa-home"></i> <span>Beranda</span></a></li>
                <li><a class="nav-link" href="#area-parkir"><i class="fas fa-map-marker-alt"></i> <span>Area Parkir</span></a></li>
                <li><a class="nav-link" href="#tarif-parkir"><i class="fas fa-tags"></i> <span>Tarif</span></a></li>
                <li><a class="nav-link" href="#pendapatan"><i class="fas fa-chart-line"></i> <span>Grafik</span></a></li>
                <li><a class="nav-link" href="#ulasan"><i class="fas fa-star"></i> <span>Ulasan</span></a></li>
                <li><a class="nav-link" href="#kontak"><i class="fas fa-address-book"></i> <span>Kontak</span></a></li>
            </ul>
        </div>

        <div class="sidebar-footer pt-3 border-top border-secondary border-opacity-25">
            <a href="login.php" class="btn btn-outline-light btn-sm w-100 rounded-pill fw-semibold text-center">
                <i class="fas fa-lock"></i> <span class="ms-1">Login Sistem</span>
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid px-0 mb-4">
            <div class="hero-img-container">
                <img src="img/parkirinap.JPG" alt="Area Parkir Stasiun Solo Balapan">
                <div class="hero-text-overlay">
                    <span class="badge bg-primary mb-2 px-3 py-2 rounded-pill fw-semibold">Official Parking System</span>
                    <h1 class="display-6 fw-bold mb-1">Stasiun Balapan Solo</h1>
                    <p class="lead mb-0 fs-6 text-white-50">Cek ketersediaan area dan tarif harian secara transparan, cepat & aman.</p>
                </div>
            </div>
        </div>

        <div class="container-fluid px-0 mb-4">
            <div class="card p-3">
                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-video text-danger me-2"></i> Video Profil & Fasilitas Parkir</h5>
                <div class="hero-video-container">
                    <video controls autoplay muted loop playsinline>
                        <source src="img/Pov Parkir Stasiun.mp4" type="video/mp4">
                        Maaf, browser Anda tidak mendukung pemutaran video HTML5.
                    </video>
                </div>
            </div>
        </div>

        <div class="container-fluid px-0 mb-5">
            <div class="row g-4">
                <div class="col-lg-7" id="area-parkir">
                    <div class="card h-100">
                        <div class="card-header py-3 border-0">
                            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-map-marker-alt me-2"></i> Daftar Area Parkir Tersedia</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Area</th>
                                            <th>Keterangan / Jenis</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1; 
                                        if($area_parkir && mysqli_num_rows($area_parkir) > 0) {
                                            while($row = mysqli_fetch_assoc($area_parkir)): 
                                        ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><strong><?= htmlspecialchars($row['nama_area']); ?></strong></td>
                                            <td><?= isset($row['jenis_kendaraan']) ? htmlspecialchars(ucfirst($row['jenis_kendaraan'])) : '-'; ?></td>
                                            <td><span class="badge bg-success text-white px-3 py-2 rounded-pill fw-semibold shadow-sm">Tersedia</span></td>
                                        </tr>
                                        <?php 
                                            endwhile; 
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted'>Belum ada area parkir.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5" id="tarif-parkir">
                    <div class="card h-100">
                        <div class="card-header py-3 border-0">
                            <h5 class="mb-0 fw-bold text-success"><i class="fas fa-tag me-2"></i> Informasi Tarif Resmi</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Jenis Kendaraan</th>
                                            <th>Tarif / Hari</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if($tarif_parkir && mysqli_num_rows($tarif_parkir) > 0) {
                                            while($t = mysqli_fetch_assoc($tarif_parkir)): 
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars(ucfirst($t['jenis_kendaraan'])); ?></strong></td>
                                            <td class="text-success fw-bold">Rp <?= number_format($t['tarif_per_jam'], 0, ',', '.'); ?></td>
                                        </tr>
                                        <?php 
                                            endwhile; 
                                        } else {
                                            echo "<tr><td colspan='2' class='text-center text-muted'>Belum ada tarif.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info border-0 bg-info bg-opacity-10 mt-3 small mb-0 text-info">
                                <i class="fas fa-info-circle me-1"></i> Tarif dihitung secara flat / reguler harian di area parkir stasiun.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4" id="pendapatan">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header py-3 border-0">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary me-2"></i> Grafik Pendapatan Parkir Bulanan (<?= $tahun_aktif; ?>)</h5>
                            <small class="text-muted">Akumulasi total pendapatan bulanan berdasarkan data transaksi keluar stasiun</small>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div style="position: relative; height: 320px; width: 100%;">
                                <canvas id="grafikPendapatan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4" id="ulasan">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header py-3 border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-warning"><i class="fas fa-star me-2"></i> Berikan Ulasan & Rating</h5>
                            <div class="text-end">
                                <span class="fs-4 fw-bold text-warning"><?= $rating_angka; ?></span>
                                <small class="text-muted d-block" style="font-size: 11px;">(<?= $jumlah_ulasan; ?> Ulasan)</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if($pesan_sukses != ""): ?>
                                <div class="alert alert-success py-2 border-0 bg-success bg-opacity-15 text-success fw-semibold"><?= htmlspecialchars($pesan_sukses); ?></div>
                                <audio id="soundLoncengUlasan" src="img/lonceng.mp3" preload="auto"></audio>
                                <script>
                                    window.addEventListener('DOMContentLoaded', () => {
                                        const audio = document.getElementById('soundLoncengUlasan');
                                        if(audio) {
                                            audio.play().catch(e => console.log("Audio diblokir browser."));
                                        }
                                    });
                                </script>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Anda</label>
                                    <input type="text" name="nama" class="form-control" required placeholder="Masukkan nama Anda">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Rating Pelayanan</label>
                                    <select name="rating" class="form-select" required>
                                        <option value="5" style="background: #1e293b; color: white;">⭐⭐⭐⭐⭐ (Sangat Puas - 5)</option>
                                        <option value="4" style="background: #1e293b; color: white;">⭐⭐⭐⭐ (Puas - 4)</option>
                                        <option value="3" style="background: #1e293b; color: white;">⭐⭐⭐ (Cukup - 3)</option>
                                        <option value="2" style="background: #1e293b; color: white;">⭐⭐ (Kurang - 2)</option>
                                        <option value="1" style="background: #1e293b; color: white;">⭐ (Sangat Kurang - 1)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Komentar / Saran</label>
                                    <textarea name="komentar" class="form-control" rows="3" required placeholder="Tuliskan ulasan atau masukan fasilitas parkir..."></textarea>
                                </div>
                                <button type="submit" name="kirim_ulasan" class="btn btn-primary w-100 fw-semibold py-2">Kirim Ulasan</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header py-3 border-0">
                            <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-comments me-2"></i> Ulasan Pengguna Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php 
                                if ($q_ulasan && mysqli_num_rows($q_ulasan) > 0) {
                                    while($u = mysqli_fetch_assoc($q_ulasan)) {
                                        $bintang = str_repeat('⭐', (int)$u['rating']);
                                        echo '<div class="list-group-item px-0">';
                                        echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                        echo '<h6 class="mb-0 fw-bold text-white">'.htmlspecialchars($u['nama']).'</h6>';
                                        echo '<small class="text-muted">'.$bintang.'</small>';
                                        echo '</div>';
                                        echo '<p class="mb-1 text-muted small">"'.htmlspecialchars($u['komentar']).'"</p>';
                                        echo '<small class="text-secondary" style="font-size: 11px;">'.htmlspecialchars($u['tanggal']).'</small>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p class="text-muted text-center my-4">Belum ada ulasan yang dikirimkan. Jadilah yang pertama memberikan ulasan!</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="help-float-btn" data-bs-toggle="modal" data-bs-target="#helpModal">
            <i class="fas fa-headset fa-lg"></i> <span>Pusat Bantuan</span>
        </button>

        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header help-modal-header py-3 px-4">
                        <h5 class="modal-title fw-bold" id="helpModalLabel"><i class="fas fa-question-circle me-2"></i> Pusat Bantuan (Help Center)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-search text-primary me-2"></i> Pertanyaan Populer (FAQ)</h6>
                            <div class="accordion shadow-sm rounded-3 overflow-hidden" id="faqAccordion">
                                <div class="accordion-item border-0 border-bottom">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            Bagaimana cara melakukan login ke sistem admin parkir?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body small">
                                            Anda dapat mengklik tombol <strong>"🔐 Login Sistem"</strong> di bagian bawah menu sidebar kiri. Masukkan akun yang telah terdaftar oleh pengelola stasiun.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0 border-bottom">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            Bagaimana sistem perhitungan tarif parkir harian?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body small">
                                            Tarif dihitung berdasarkan jenis kendaraan secara flat harian (Reguler). Rincian tarif lengkap dapat dilihat pada tabel bagian "Informasi Tarif Resmi" di beranda.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            Apa yang harus dilakukan jika mengalami kendala sistem?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body small">
                                            Jika Anda mengalami kendala teknis, Anda bisa langsung menghubungi tim dukungan operasional melalui kontak resmi WhatsApp atau Gmail di bawah ini.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-3 p-4">
                            <h6 class="fw-bold mb-3 text-dark text-center"><i class="fas fa-headset text-primary me-1"></i> Layanan Kontak Resmi Bantuan</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 h-100 d-flex flex-column justify-content-between" style="background: rgba(15, 23, 42, 0.4);">
                                        <div>
                                            <h6 class="fw-bold text-success mb-1"><i class="fab fa-whatsapp me-1"></i> WhatsApp Layanan</h6>
                                            <p class="text-muted small mb-2">+62 812-3456-7890<br>(Call Center Parkir)</p>
                                        </div>
                                        <a href="https://wa.me/6281234567890?text=Halo%20Admin%20Parkir%20Stasiun%20Balapan,%20saya%20butuh%20bantuan%20terkait%20sistem." target="_blank" class="btn btn-success btn-sm fw-semibold rounded-pill w-100">
                                            <i class="fab fa-whatsapp"></i> Chat WhatsApp
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 h-100 d-flex flex-column justify-content-between" style="background: rgba(15, 23, 42, 0.4);">
                                        <div>
                                            <h6 class="fw-bold text-danger mb-1"><i class="fas fa-envelope me-1"></i> Email / Gmail</h6>
                                            <p class="text-muted small mb-2">parkir.balapan@kai.id<br>support@parkirstasiun.com</p>
                                        </div>
                                        <a href="mailto:parkir.balapan@kai.id?subject=Kendala%20Sistem%20Parkir%20Stasiun%20Balapan" class="btn btn-outline-danger btn-sm fw-semibold rounded-pill w-100">
                                            <i class="fas fa-paper-plane"></i> Kirim Email
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0 py-3" style="background-color: #1e293b;">
                        <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-white pt-5 pb-3 border-top mt-5" style="border-color: rgba(255,255,255,0.08) !important;" id="kontak">
            <div class="container-fluid px-0">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5 class="fw-bold mb-3 text-white">🅿️ Sistem Informasi Parkir Stasiun</h5>
                        <p class="text-white-50 small">
                            Portal resmi sistem manajemen parkir area Stasiun Balapan Solo. Menyediakan informasi ketersediaan tempat, tarif resmi, serta transparansi pendapatan bulanan secara real-time dari data transaksi keluar stasiun.
                        </p>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h5 class="fw-bold mb-3 text-white">📍 Kontak & Lokasi Resmi</h5>
                        <p class="mb-2 text-white-50 small">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i> <strong>Alamat Stasiun:</strong> Jl. Wolter Monginsidi, Setabelan, Kec. Banjarsari, Kota Surakarta, Jawa Tengah 57139 (Stasiun Balapan Solo)
                        </p>
                        <p class="mb-2 text-white-50 small">
                            <i class="fas fa-envelope text-warning me-2"></i> <strong>Email / Gmail:</strong> parkir.stasiunbalapansolo@kai.id / support@parkirstasiun.com
                        </p>
                        <p class="mb-0 text-white-50 small">
                            <i class="fab fa-whatsapp text-success me-2"></i> <strong>WhatsApp Layanan:</strong> +62 812-3456-7890 (Call Center Parkir)
                        </p>
                    </div>
                </div>
                <hr class="border-secondary my-4" style="opacity: 0.2;">
                <div class="text-center text-white-50 small">
                    <p class="mb-0">&copy; 2026 Sistem Informasi Parkir Stasiun Balapan Solo. All rights reserved.</p>
                    <br>
                    BY Faizal Ikhram 
                    <br>
                    SMK N1 SANDEN  
                </div>
            </div>
        </footer>
    </div>

    <script>
        const ctx = document.getElementById('grafikPendapatan').getContext('2d');
        
        const grafikPendapatan = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($grafik_label); ?>,
                datasets: [{
                    label: 'Pendapatan Riil Bulanan (<?= $tahun_aktif; ?>)',
                    data: <?= json_encode($grafik_data); ?>,
                    borderColor: '#3b82f6',
                    borderWidth: 2.5,
                    tension: 0.3, // Membuat garis melengkung naik turun seperti gelombang/gunung
                    fill: false,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#1e293b',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: '#e2e8f0',
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { 
                            display: true,
                            color: 'rgba(255, 255, 255, 0.08)'
                        },
                        ticks: { color: '#94a3b8' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { 
                            display: true,
                            color: 'rgba(255, 255, 255, 0.08)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
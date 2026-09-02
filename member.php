<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

if (isset($conn)) {
    mysqli_select_db($conn, 'db_parkir'); 
}

// Validasi hak akses khusus member
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'member') {
    header("Location: index.php");
    exit;
}

$id_user_login = $_SESSION['id_user'];
$nama_member = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Member');

$trigger_sound = false;
$show_qr_modal_id = 0; // Untuk mendeteksi ID booking baru yang harus memunculkan QR

// ==========================================
// DETEKSI OTOMATIS PRIMARY KEY TABEL tb_booking
// ==========================================
$pk_booking = 'id_booking'; // Default fallback
$cek_pk = mysqli_query($conn, "SHOW COLUMNS FROM tb_booking");
if ($cek_pk && mysqli_num_rows($cek_pk) > 0) {
    while ($d_pk = mysqli_fetch_assoc($cek_pk)) {
        if (isset($d_pk['Key']) && $d_pk['Key'] == 'PRI') {
            $pk_booking = $d_pk['Field'];
            break;
        }
    }
}

// ==========================================
// PROSES TAMBAH BOOKING BARU
// ==========================================
if (isset($_POST['tambah_booking'])) {
    $plat_nomor = strtoupper(mysqli_real_escape_string($conn, $_POST['nomor_plat']));
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $id_area = intval($_POST['id_area']);
    $durasi_hari = intval($_POST['durasi_hari']);

    if ($durasi_hari < 1) { $durasi_hari = 1; }
    if ($durasi_hari > 3) { $durasi_hari = 3; }

    // Hitung estimasi biaya dasar otomatis
    $tarif_harian = (strtolower($jenis_kendaraan) == 'mobil') ? 25000 : 15000;
    $total_biaya = $tarif_harian * $durasi_hari;

    $query_insert = mysqli_query($conn, "INSERT INTO tb_booking (id_user, nomor_plat, jenis_kendaraan, id_area, durasi_hari, total_biaya, status) 
                                         VALUES ('$id_user_login', '$plat_nomor', '$jenis_kendaraan', '$id_area', '$durasi_hari', '$total_biaya', 'Menunggu')");

    if ($query_insert) {
        // Ambil ID booking yang baru saja dimasukkan agar langsung memunculkan QR
        $last_id = mysqli_insert_id($conn);
        header("Location: member.php?status=sukses_booking&play_sound=1&show_qr=" . $last_id);
        exit;
    } else {
        header("Location: member.php?status=gagal_booking&play_sound=1");
        exit;
    }
}

if (isset($_GET['play_sound']) && $_GET['play_sound'] == '1') {
    $trigger_sound = true;
}

if (isset($_GET['show_qr'])) {
    $show_qr_modal_id = intval($_GET['show_qr']);
}

// ==========================================
// AMBIL DATA AREA PARKIR & RIWAYAT BOOKING
// ==========================================
$query_area = mysqli_query($conn, "SELECT * FROM tb_area_parkir");

$query_riwayat = mysqli_query($conn, "SELECT b.*, a.nama_area 
                                     FROM tb_booking b 
                                     LEFT JOIN tb_area_parkir a ON b.id_area = a.id_area 
                                     WHERE b.id_user = '$id_user_login' 
                                     ORDER BY b.$pk_booking DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Member - Stasiun Parking</title>
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
        .table-custom td { padding: 0.85rem 1rem; vertical-align: middle; border-color: rgba(255,255,255,0.05); }
        .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.04); color: #ffffff; }
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
        .modal-content { background-color: #1e293b; color: #e2e8f0; }
    </style>
</head>
<body>

    <!-- Audio Efek Suara dari Folder img/ -->
    <audio id="soundRafa" preload="auto">
        <source src="img/berhasil.MPEG" type="audio/MPEG">
        <source src="img/berhasil.MPEG" type="audio/MPEG">
        <source src="img/salah.MPEG " type="audio/MPEG">
    </audio>

    <nav class="navbar navbar-dark navbar-custom px-4 py-3 shadow-sm sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold fs-5">🚆 MEMBER AREA — <span class="text-info"><?= htmlspecialchars($nama_member); ?></span></span>
            <a href="logout.php" id="btn-logout" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
        </div>
    </nav>

    <div class="container py-5">

        <!-- BAGIAN 1: FORM BUAT BOOKING PARKIR INAP -->
        <div class="card">
            <div class="card-header py-3 px-4 fw-bold">📝 Form Pre-Order / Booking Parkir Inap Stasiun</div>
            <div class="card-body p-4">
                <form action="member.php" method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">NOMOR PLAT KENDARAAN</label>
                        <input type="text" name="nomor_plat" class="form-control text-uppercase" placeholder="Contoh: B 1234 XYZ" required autocomplete="off">
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
                        <label class="form-label fw-semibold small text-info">PILIH AREA PARKIR</label>
                        <select name="id_area" id="id_area" class="form-select" required>
                            <option value="">-- Pilih Area Stasiun --</option>
                            <?php 
                            if ($query_area && mysqli_num_rows($query_area) > 0) {
                                while($area = mysqli_fetch_assoc($query_area)) {
                                    $kapasitas_info = isset($area['kapasitas']) ? " (Kapasitas: {$area['kapasitas']})" : "";
                                    echo "<option value='{$area['id_area']}' data-nama='" . strtolower($area['nama_area']) . "'>{$area['nama_area']}{$kapasitas_info}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-info">DURASI INAP (HARI)</label>
                        <input type="number" name="durasi_hari" class="form-control" value="1" min="1" max="3" required>
                        <small class="text-muted" style="font-size: 0.7rem;">Maksimal pre-order 3 hari.</small>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" name="tambah_booking" class="btn btn-info px-4 rounded-pill fw-semibold text-dark">Kirim Booking Sekarang</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- BAGIAN 2: RIWAYAT BOOKING MEMBER -->
        <div class="card">
            <div class="card-header py-3 px-4 fw-bold">📋 Riwayat Booking & Pembayaran QR Parkir Anda</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID Booking</th>
                                <th>Plat Nomor</th>
                                <th>Kendaraan</th>
                                <th>Area Parkir</th>
                                <th>Durasi</th>
                                <th>Total Biaya</th>
                                <th>Status</th>
                                <th class="text-center">Aksi / Lihat QR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $modal_qr_list = [];

                            if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) {
                                while($row = mysqli_fetch_assoc($query_riwayat)) {
                                    $status = $row['status'];
                                    $badge_color = 'bg-warning text-dark';
                                    if ($status == 'Lunas') $badge_color = 'bg-success';
                                    else if ($status == 'Batal') $badge_color = 'bg-danger';

                                    $current_id_booking = isset($row[$pk_booking]) ? $row[$pk_booking] : '-';

                                    $modal_qr_list[] = [
                                        'id' => $current_id_booking,
                                        'total' => $row['total_biaya'],
                                        'status' => $status
                                    ];
                            ?>
                            <tr>
                                <td class="ps-4">#<?= $current_id_booking; ?></td>
                                <td class="fw-bold"><?= strtoupper($row['nomor_plat']); ?></td>
                                <td style="text-transform: capitalize;"><?= $row['jenis_kendaraan']; ?></td>
                                <td class="text-white"><?= htmlspecialchars($row['nama_area'] ?? '-'); ?></td>
                                <td><?= $row['durasi_hari']; ?> Hari</td>
                                <td class="fw-bold text-info">Rp <?= number_format($row['total_biaya'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?= $badge_color; ?>">
                                        <?= ucfirst(strtolower($status)); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if(strtolower($status) != 'lunas'): ?>
                                        <button type="button" class="btn btn-warning btn-sm rounded-pill fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalQr<?= $current_id_booking; ?>">
                                            <i class="fas fa-qrcode me-1"></i> Lihat QR
                                        </button>
                                    <?php else: ?>
                                        <span class="text-success small fw-semibold"><i class="fas fa-check-circle me-1"></i>Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat booking parkir.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- SEMUA MODAL QR -->
    <?php foreach($modal_qr_list as $m): ?>
    <div class="modal fade text-start" id="modalQr<?= $m['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold text-white"><i class="fas fa-qrcode text-warning me-2"></i>Scan QR Pembayaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="small text-muted mb-3">Silakan scan kode QR di bawah ini melalui aplikasi m-Banking / E-Wallet Anda untuk melunasi tagihan sebesar <strong class="text-info">Rp <?= number_format($m['total'], 0, ',', '.'); ?></strong></p>
                    
                    <!-- Menampilkan Gambar QR dari file JPEG di folder img/ dengan fallback API online -->
                    <div class="p-3 bg-white rounded d-inline-block shadow-sm mb-3">
                        <img src="img/qr.JPEG" alt="qr Code Pembayaran" class="JPEG-fluid" style="max-width: 200px;">
                    </div>
                    <p class="small text-warning mb-0">Status akan otomatis diperbarui oleh petugas setelah pembayaran diterima.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- FontAwesome & Bootstrap JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function playRafaSound() {
            var audio = document.getElementById("soundRafa");
            if (audio) {
                audio.currentTime = 0;
                var playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        console.log("Audio diblokir browser: ", error);
                    });
                }
            }
        }

        <?php if ($trigger_sound): ?>
        window.addEventListener('DOMContentLoaded', (event) => {
            playRafaSound();
        });
        <?php endif; ?>

        // Otomatis Munculkan Modal QR Jika Baru Saja Kirim Booking
        <?php if ($show_qr_modal_id > 0): ?>
        window.addEventListener('DOMContentLoaded', (event) => {
            var myModalEl = document.getElementById('modalQr' + <?= $show_qr_modal_id; ?>);
            if (myModalEl) {
                var modalInstance = new bootstrap.Modal(myModalEl);
                modalInstance.show();
            }

            if (window.history.replaceState) {
                var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        });
        <?php endif; ?>

        // Skrip Pilih Area Berdasarkan Jenis Kendaraan
        document.getElementById('jenis_kendaraan').addEventListener('change', function() {
            const jenis = this.value.toLowerCase();
            const selectArea = document.getElementById('id_area');
            let targetKeyword = '';

            if (jenis === 'motor') {
                targetKeyword = 'motor';
            } else if (jenis === 'mobil') {
                targetKeyword = 'mobil';
            }

            if (targetKeyword) {
                for (let i = 0; i < selectArea.options.length; i++) {
                    const optionText = selectArea.options[i].text.toLowerCase();
                    if (optionText.includes(targetKeyword)) {
                        selectArea.selectedIndex = i;
                        break;
                    }
                }
            } else {
                selectArea.selectedIndex = 0;
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const statusParam = urlParams.get('status');

        if (statusParam) {
            const cleanUrl = window.location.origin + 
                             window.location.pathname + 
                             (window.location.search
                                .replace(/([?&])status=[^&]*(&?)/, function(match, p1, p2) {
                                    return p2 ? p1 : '';
                                })
                                .replace(/[?&]$/, '')
                             );

            window.history.replaceState({}, document.title, cleanUrl);

            if (statusParam === 'sukses_booking') {
                Swal.fire({
                    icon: 'success',
                    title: 'Booking Berhasil!',
                    text: 'Silakan lakukan pembayaran melalui QR code yang muncul.',
                    timer: 3500,
                    showConfirmButton: false
                });
            } else if (statusParam === 'gagal_booking') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: 'Gagal membuat booking. Silakan coba kembali.'
                });
            }
        }

        const btnLogout = document.getElementById('btn-logout');
        if (btnLogout) {
            btnLogout.addEventListener('click', function(e) {
                e.preventDefault();
                const logoutUrl = this.getAttribute('href');
                playRafaSound();

                Swal.fire({
                    title: 'Keluar dari Sesi?',
                    text: 'Anda akan mengakhiri sesi member saat ini.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Logout',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        setTimeout(() => {
                            window.location.href = logoutUrl;
                        }, 500);
                    }
                });
            });
        }
    </script>
</body>
</html>
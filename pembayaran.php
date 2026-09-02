<?php
session_start();
include 'koneksi.php';

// Mendukung session ID user
$id_user = $_SESSION['id_user'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
if (!$id_user) {
    header("Location: login.php");
    exit;
}

// Ambil ID booking dari parameter URL
$id_booking = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_booking']) ? intval($_GET['id_booking']) : 0);

// Proses ketika tombol konfirmasi pembayaran diklik (ditaruh di atas sebelum HTML dimuat)
if (isset($_POST['konfirmasi_bayar'])) {
    if ($id_booking > 0) {
        // Update status booking menjadi 'Menunggu' atau sesuai kebutuhan sistem Anda
        mysqli_query($conn, "UPDATE tb_booking SET status = 'Menunggu' WHERE id = '$id_booking'");
    }
    // Langsung arahkan kembali ke halaman member/booking
    header("Location: member.php?status=sukses");
    exit;
}

// Query data booking
$query = "SELECT b.*, a.nama_area FROM tb_booking b JOIN tb_area_parkir a ON b.id_area = a.id_area WHERE b.id = '$id_booking'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

// Jika data tidak ditemukan
if (!$data) {
    echo "<script>alert('Data booking dengan ID $id_booking tidak ditemukan!'); window.location='member.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran Booking - Parkir Stasiun</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
        .card { border-radius: 1rem; border: none; }
        .btn-custom { padding: 0.75rem; border-radius: 0.50rem; font-weight: 600; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 shadow-sm py-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fs-4" href="member.php">🅿️ Parkir Stasiun - Pembayaran</a>
            <a href="member.php" class="btn btn-outline-light btn-sm px-3 rounded-pill">Kembali ke Member</a>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">Konfirmasi & Pembayaran</h3>
                            <p class="text-muted small">Selesaikan pembayaran untuk pesanan parkir inap Anda</p>
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-4">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">ID Booking</td>
                                    <td class="fw-bold text-end">#<?= $data['id']; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kendaraan</td>
                                    <td class="fw-bold text-end"><?= ucfirst($data['jenis_kendaraan']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nomor Plat</td>
                                    <td class="fw-bold text-end"><?= htmlspecialchars($data['nomor_plat']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Area Parkir</td>
                                    <td class="fw-bold text-end"><?= htmlspecialchars($data['nama_area']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Durasi Inap</td>
                                    <td class="fw-bold text-end"><?= $data['durasi_hari']; ?> Hari</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Mulai</td>
                                    <td class="fw-bold text-end"><?= $data['tanggal_booking']; ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold text-dark pt-2">Total Biaya</td>
                                    <td class="fw-bold text-success fs-5 text-end pt-2">Rp <?= number_format($data['total_biaya'], 0, ',', '.'); ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Simulasi QRIS -->
                        <div class="text-center mb-4">
                            <p class="text-muted small mb-2">Scan QRIS di bawah ini untuk membayar:</p>
                            <div class="border p-3 d-inline-block bg-white rounded-3 shadow-sm">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=PARKIR-STASIUN-ID-<?= $data['id']; ?>" alt="QRIS Pembayaran" class="img-fluid">
                            </div>
                            <p class="text-muted small mt-2">NPSN / Merchant: Parkir Stasiun Official</p>
                        </div>

                        <!-- Form dengan tombol konfirmasi yang membawa input tersembunyi -->
                        <form action="" method="POST" id="formPembayaran" onsubmit="putarSuaraNotifikasi(event)">
                            <input type="hidden" name="konfirmasi_bayar" value="1">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark btn-custom">Saya Sudah Bayar / Selesai</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Efek Suara dan Redirect Otomatis -->
    <script>
    function putarSuaraNotifikasi(event) {
        event.preventDefault();
        var form = event.target;

        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            
            // Nada pertama (ting)
            const osc1 = audioCtx.createOscillator();
            const gain1 = audioCtx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, audioCtx.currentTime); 
            gain1.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.5);
            osc1.connect(gain1);
            gain1.connect(audioCtx.destination);
            osc1.start();
            osc1.stop(audioCtx.currentTime + 0.5);

            // Nada kedua (dong)
            const osc2 = audioCtx.createOscillator();
            const gain2 = audioCtx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880, audioCtx.currentTime + 0.15); 
            gain2.gain.setValueAtTime(0.3, audioCtx.currentTime + 0.15);
            gain2.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.7);
            osc2.connect(gain2);
            gain2.connect(audioCtx.destination);
            osc2.start(audioCtx.currentTime + 0.15);
            osc2.stop(audioCtx.currentTime + 0.7);

            // Tunggu suara selesai lalu submit form agar proses PHP dijalankan
            setTimeout(function() {
                form.submit();
            }, 600);

        } catch (e) {
            form.submit();
        }
    }
    </script>
</body>
</html>
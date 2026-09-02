<?php
session_start();
include 'koneksi.php';
$pesan = "";

if (isset($_POST['daftar'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password_input = $_POST['password'];
    $password = password_hash($password_input, PASSWORD_DEFAULT); 
    
    // Cek apakah username sudah terdaftar
    $cek = mysqli_query($conn, "SELECT * FROM tb_user WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "<div class='alert alert-danger'>Username sudah digunakan!</div>";
    } else {
        // Masukkan data ke tabel user (status_aktif langsung 1 agar bisa langsung masuk)
        $query_user = "INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES ('$nama', '$username', '$password', 'member', 1)";
        
        if (mysqli_query($conn, $query_user)) {
            // Ambil id user yang baru saja mendaftar
            $id_user_baru = mysqli_insert_id($conn);

            // OTOMATIS LOGIN (Membuat session aktif)
            $_SESSION['id_user'] = $id_user_baru;
            $_SESSION['role'] = 'member';
            $_SESSION['nama_lengkap'] = $nama;

            // Catat juga ke tb_booking agar grafik pendapatan otomatis bertambah
            $nomor_plat = "AD " . rand(1000, 9999) . " XX";
            $jenis_kendaraan = "Mobil";
            $total_biaya = 25000; 
            
            // Cek apakah kolom id_user ada di tb_booking
            @mysqli_query($conn, "INSERT INTO tb_booking (id_user, nama_pemilik, nomor_plat, jenis_kendaraan, tanggal_booking, total_biaya) VALUES ('$id_user_baru', '$nama', '$nomor_plat', '$jenis_kendaraan', NOW(), '$total_biaya')");

            // Langsung arahkan ke halaman member (tidak perlu login manual lagi)
            header("Location: member.php");
            exit;
        } else {
            $pesan = "<div class='alert alert-danger'>Terjadi kesalahan sistem.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Akun Member Parkir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 450px;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-bold text-center">Registrasi Member Parkir</div>
            <div class="card-body">
                <?= $pesan; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="daftar" class="btn btn-primary w-100">Daftar & Masuk Sistem</button>
                    <a href="login.php" class="btn btn-secondary w-100 mt-2">Sudah punya akun? Login Sistem</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
session_start();
include 'koneksi.php';

$error = false;
$error_msg = "";
$success = false;
$success_msg = "";
$redirect_url = "";

if (isset($_POST['login'])) {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password_input = trim(mysqli_real_escape_string($conn, $_POST['password']));
    
    if (empty($username) || empty($password_input)) {
        $error_msg = "Username atau Password tidak boleh kosong!";
        $error = true;
    } else {
        // Ambil data user berdasarkan username saja terlebih dahulu
        $query = mysqli_query($conn, "SELECT * FROM tb_user WHERE username='$username'");
        
        if ($query && mysqli_num_rows($query) === 1) {
            $user = mysqli_fetch_assoc($query);

            // Verifikasi password (mendukung teks biasa, MD5, maupun password_hash standar PHP)
            $cek_pass = false;
            if ($user['password'] === $password_input || $user['password'] === md5($password_input) || password_verify($password_input, $user['password'])) {
                $cek_pass = true;
            }

            if (!$cek_pass) {
                $error_msg = "Username atau Password salah!";
                $error = true;
            } elseif ($user['status_aktif'] == 0) {
                $error_msg = "Akun Anda belum diaktifkan oleh Admin!";
                $error = true;
            } else {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];

                // Rekam Log Aktivitas
                $id_user_log = $user['id_user'];
                $user_log = $user['username'];
                $role_log = ucfirst($user['role']);
                $aktivitas_text = "User " . $user_log . " (" . $role_log . ") berhasil login ke sistem.";
                mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$id_user_log', '$aktivitas_text')");

                $role_uc = ucfirst($user['role']);
                $success_msg = "🎉 Hore, Login Berhasil!<br><b>Selamat Datang Kembali, " . $user['nama_lengkap'] . "</b><br><span class='badge bg-success mt-1'>Akses sebagai: " . $role_uc . "</span><br><small class='text-muted mt-2 d-block'>Menyiapkan halaman dashboard Anda...</small>";
                $success = true;

                $role_lowercase = strtolower($user['role']);
                if ($role_lowercase == 'admin') {
                    $redirect_url = "admin.php";
                } elseif ($role_lowercase == 'petugas') {
                    $redirect_url = "petugas.php";
                } elseif ($role_lowercase == 'owner') {
                    $redirect_url = "owner.php";
                } elseif ($role_lowercase == 'member') {
                    $redirect_url = "member.php";
                } else {
                    $redirect_url = "index.php";
                }
            }
        } else {
            $error_msg = "Username atau Password salah!";
            $error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Parkir Stasiun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.85)), 
                        url('https://images.unsplash.com/photo-1506521781263-d8422e82f27a?q=80&w=1920&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
        }
        .form-control {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
        }
        .btn-primary {
            background-color: #2563eb;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-outline-secondary {
            border-radius: 0.75rem;
            padding: 0.6rem;
            font-weight: 500;
        }
        .welcome-alert {
            animation: fadeInScale 0.4s ease-in-out forwards;
        }
        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="fs-1 mb-2">🅿️🚆</div>
            <h4 class="fw-bold text-dark mb-1">Login Sistem</h4>
            <p class="text-muted small">Silakan masuk sesuai akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 text-center small alert-dismissible fade show" role="alert">
                <?= $error_msg; ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <audio id="soundGagal" src="img/salah.MPEG" preload="auto"></audio>
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    const audio = document.getElementById('soundGagal');
                    if(audio) { audio.play().catch(e => console.log("Audio diblokir browser.")); }
                });
            </script>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success p-3 text-center welcome-alert shadow-sm border-0" role="alert">
                <?= $success_msg; ?>
            </div>
            <audio id="soundSukses" src="img/berhasil.MPEG" preload="auto"></audio>
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    const audio = document.getElementById('soundSukses');
                    if(audio) { audio.play().catch(e => console.log("Audio diblokir browser.")); }

                    setTimeout(() => {
                        window.location.href = "<?= $redirect_url; ?>";
                    }, 3500);
                });
            </script>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold small text-secondary">Username</label>
                <input type="text" name="username" class="form-control" autocomplete="off" placeholder="Masukkan username" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small text-secondary">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 mb-2">Masuk</button>
            <a href="index.php" class="btn btn-outline-secondary w-100 btn-sm text-decoration-none">← Kembali ke Beranda</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
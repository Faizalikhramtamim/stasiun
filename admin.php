<?php
session_start();
include 'koneksi.php';

$notif = "";
$trigger_sound = false;

// 1. Tambah Area Parkir (Dengan Kapasitas)
if (isset($_POST['tambah_area'])) {
    $nama_area = mysqli_real_escape_string($conn, $_POST['nama_area']);
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $kapasitas = (int)$_POST['kapasitas'];
    
    $query = mysqli_query($conn, "INSERT INTO tb_area_parkir (nama_area, jenis_kendaraan, kapasitas) VALUES ('$nama_area', '$jenis_kendaraan', $kapasitas)");
    if ($query) {
        header("Location: admin.php?play_sound=1#area-parkir");
        exit();
    }
}

// 2. Hapus Area Parkir
if (isset($_GET['hapus_area'])) {
    $id = (int)$_GET['hapus_area'];
    $query = mysqli_query($conn, "DELETE FROM tb_area_parkir WHERE id_area = $id");
    if ($query) {
        header("Location: admin.php?play_sound=1#area-parkir");
        exit();
    }
}

// 3. Tambah Tarif
if (isset($_POST['tambah_tarif'])) {
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $tarif_per_jam = (int)$_POST['tarif_per_jam'];
    $query = mysqli_query($conn, "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES ('$jenis_kendaraan', '$tarif_per_jam')");
    if ($query) {
        header("Location: admin.php?play_sound=1#tarif-parkir");
        exit();
    }
}

// 4. Hapus Tarif
if (isset($_GET['hapus_tarif'])) {
    $id = (int)$_GET['hapus_tarif'];
    $cek_pk = mysqli_query($conn, "SHOW COLUMNS FROM tb_tarif LIKE 'id%'");
    $pk_name = 'id';
    while($col = mysqli_fetch_assoc($cek_pk)){
        $pk_name = $col['Field'];
        break;
    }
    $query = mysqli_query($conn, "DELETE FROM tb_tarif WHERE $pk_name = $id");
    if ($query) {
        header("Location: admin.php?play_sound=1#tarif-parkir");
        exit();
    }
}

// 5. Hapus Ulasan
if (isset($_GET['hapus_ulasan'])) {
    $id = (int)$_GET['hapus_ulasan'];
    $cek_pk = mysqli_query($conn, "SHOW COLUMNS FROM tb_ulasan LIKE 'id%'");
    $pk_name = 'id';
    while($col = mysqli_fetch_assoc($cek_pk)){
        $pk_name = $col['Field'];
        break;
    }
    $query = mysqli_query($conn, "DELETE FROM tb_ulasan WHERE $pk_name = $id");
    if ($query) {
        header("Location: admin.php?play_sound=1#ulasan");
        exit();
    }
}

// 6. Tambah User (ID Dibuat Otomatis dan Acak)
if (isset($_POST['tambah_user'])) {
    $id_user = 'USR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $query = mysqli_query($conn, "INSERT INTO tb_user (id_user, nama_lengkap, username, password, role) VALUES ('$id_user', '$nama_lengkap', '$username', '$password', '$role')");
    if ($query) {
        header("Location: admin.php?play_sound=1#kelola-user");
        exit();
    }
}

// 7. Hapus User
if (isset($_GET['hapus_user'])) {
    $id_user = mysqli_real_escape_string($conn, $_GET['hapus_user']);
    $query = mysqli_query($conn, "DELETE FROM tb_user WHERE id_user = '$id_user'");
    if ($query) {
        header("Location: admin.php?play_sound=1#kelola-user");
        exit();
    }
}

if (isset($_GET['play_sound']) && $_GET['play_sound'] == '1') {
    $trigger_sound = true;
    $notif = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                <i class='fas fa-check-circle me-2'></i>Aksi berhasil dijalankan!
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
}

$q_area = mysqli_query($conn, "SELECT * FROM tb_area_parkir");
$q_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif");
$q_transaksi_keluar = mysqli_query($conn, "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
                                           FROM tb_transaksi t 
                                           JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                                           JOIN tb_area_parkir a ON t.id_area = a.id_area 
                                           WHERE t.status = 'keluar' ORDER BY t.id_transaksi DESC LIMIT 20");

$q_transaksi_masuk = mysqli_query($conn, "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
                                          FROM tb_transaksi t 
                                          JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                                          JOIN tb_area_parkir a ON t.id_area = a.id_area 
                                          WHERE t.status = 'masuk' ORDER BY t.waktu_masuk DESC");

$q_ulasan = @mysqli_query($conn, "SELECT * FROM tb_ulasan ORDER BY id DESC");
$q_users = @mysqli_query($conn, "SELECT * FROM tb_user ORDER BY nama_lengkap ASC");

$total_area = mysqli_num_rows($q_area);
$total_tarif = mysqli_num_rows($q_tarif);
$total_ulasan = $q_ulasan ? mysqli_num_rows($q_ulasan) : 0;
$total_user = $q_users ? mysqli_num_rows($q_users) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Sistem Parkir Stasiun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            background-attachment: fixed;
            color: #e2e8f0;
            overflow-x: hidden;
        }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 260px;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.08); z-index: 1040;
            display: flex; flex-direction: column; justify-content: space-between; padding: 20px;
        }
        .sidebar-brand {
            font-size: 1.15rem; font-weight: bold; color: #fff; text-decoration: none;
            display: flex; align-items: center; gap: 10px; padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .sidebar-menu { list-style: none; padding: 0; margin: 20px 0; display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
        .sidebar-menu .nav-link {
            color: #94a3b8; padding: 10px 15px; border-radius: 8px; font-weight: 500;
            display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all 0.2s;
        }
        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active {
            color: #fff; background: rgba(59, 130, 246, 0.15); border-left: 4px solid #3b82f6;
        }
        .main-content { margin-left: 260px; padding: 30px; min-height: 100vh; }
        @media (max-width: 992px) {
            .sidebar { width: 70px; padding: 15px 10px; }
            .sidebar-brand span, .sidebar-menu span, .sidebar-footer span { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
        }
        .card { 
            border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.08); 
            background: rgba(30, 41, 59, 0.75); backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); color: #e2e8f0;
        }
        .table { color: #e2e8f0 !important; margin-bottom: 0; }
        .table-light th {
            color: #94a3b8 !important; font-weight: 600; text-transform: uppercase; font-size: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important; background: rgba(15, 23, 42, 0.4) !important;
        }
        .table-hover > tbody > tr:hover { background-color: rgba(255, 255, 255, 0.04) !important; color: #fff; }
        .table>:not(caption)>*>* { border-bottom-color: rgba(255, 255, 255, 0.06); padding: 0.85rem 0.75rem; }
        .form-control, .form-select { background-color: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.15); color: #fff; }
        .form-control:focus, .form-select:focus { background-color: rgba(15, 23, 42, 0.8); border-color: #3b82f6; color: #fff; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25); }
        .modal-content { background-color: #1e293b; color: #e2e8f0; }
    </style>
</head>
<body>

    <audio id="soundRafa" preload="auto">
        <source src="img/berhasil.MPEG" type="audio/MPEG">
        <source src="img/berhasil.wav" type="audio/wav">
    </audio>

    <nav class="sidebar">
        <div>
            <a class="sidebar-brand" href="admin.php">
                <i class="fas fa-user-shield text-warning fa-lg"></i> <span>Admin Parkir</span>
            </a>
            <ul class="sidebar-menu">
                <li><a class="nav-link active" href="#dashboard"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a></li>
                <li><a class="nav-link" href="#kelola-user"><i class="fas fa-users"></i> <span>Kelola User</span></a></li>
                <li><a class="nav-link" href="#area-parkir"><i class="fas fa-map-marker-alt"></i> <span>Kelola Area</span></a></li>
                <li><a class="nav-link" href="#tarif-parkir"><i class="fas fa-tags"></i> <span>Kelola Tarif</span></a></li>
                <li><a class="nav-link" href="#transaksi"><i class="fas fa-exchange-alt"></i> <span>Data Transaksi</span></a></li>
                <li><a class="nav-link" href="#ulasan"><i class="fas fa-star"></i> <span>Kelola Ulasan</span></a></li>
            </ul>
        </div>
        <div class="sidebar-footer pt-3 border-top border-secondary border-opacity-25">
            <a href="index.php" class="btn btn-outline-light btn-sm w-100 rounded-pill fw-semibold text-center">
                <i class="fas fa-globe"></i> <span class="ms-1">Lihat Beranda</span>
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">Panel Kontrol Administrator</h3>
                <p class="text-muted small mb-0">Kelola area, tarif, transaksi, ulasan, dan pengguna sistem stasiun</p>
            </div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill font-monospace"><i class="fas fa-shield-alt me-1"></i> Admin Mode</span>
        </div>

        <?= $notif; ?>

        <div class="row g-3 mb-4" id="dashboard">
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Total Area Parkir</small>
                            <h3 class="fw-bold mb-0 text-primary"><?= $total_area; ?></h3>
                        </div>
                        <i class="fas fa-parking fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Jenis Tarif Parkir</small>
                            <h3 class="fw-bold mb-0 text-success"><?= $total_tarif; ?></h3>
                        </div>
                        <i class="fas fa-tags fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Total Pengguna</small>
                            <h3 class="fw-bold mb-0 text-info"><?= $total_user; ?></h3>
                        </div>
                        <i class="fas fa-users fa-2x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Total Ulasan</small>
                            <h3 class="fw-bold mb-0 text-warning"><?= $total_ulasan; ?></h3>
                        </div>
                        <i class="fas fa-star fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KELOLA USER -->
        <div class="row mb-4" id="kelola-user">
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center border-0">
                        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-users me-2"></i> Kelola Data Pengguna (User)</h5>
                        <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                            <i class="fas fa-user-plus me-1"></i> Tambah User Baru
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>ID User</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Role / Level</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no_u = 1;
                                    if($q_users && mysqli_num_rows($q_users) > 0):
                                        while($u = mysqli_fetch_assoc($q_users)):
                                            $role = $u['role'] ?? 'member';
                                            $badge_bg = 'secondary';
                                            if($role == 'owner') $badge_bg = 'warning text-dark';
                                            elseif($role == 'petugas') $badge_bg = 'info text-dark';
                                            elseif($role == 'admin') $badge_bg = 'danger';
                                            elseif($role == 'member') $badge_bg = 'success';
                                    ?>
                                    <tr>
                                        <td><?= $no_u++; ?></td>
                                        <td><code><?= htmlspecialchars($u['id_user'] ?? '-'); ?></code></td>
                                        <td><strong><?= htmlspecialchars($u['nama_lengkap'] ?? '-'); ?></strong></td>
                                        <td><?= htmlspecialchars($u['username'] ?? '-'); ?></td>
                                        <td><span class="badge bg-<?= $badge_bg; ?> px-2 py-1 text-uppercase"><?= $role; ?></span></td>
                                        <td class="text-center">
                                            <a href="admin.php?hapus_user=<?= $u['id_user']; ?>" onclick="return confirm('Yakin ingin menghapus user ini?')" class="btn btn-danger btn-sm rounded-circle shadow-sm" title="Hapus User">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr><td colspan="6" class="text-center text-muted">Belum ada data user.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KELOLA AREA PARKIR & TARIF -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6" id="area-parkir">
                <div class="card h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center border-0">
                        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-map-marker-alt me-2"></i> Kelola Area Parkir</h5>
                        <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahArea">
                            <i class="fas fa-plus me-1"></i> Tambah Area
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Area</th>
                                        <th>Kendaraan</th>
                                        <th>Kapasitas</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1; 
                                    mysqli_data_seek($q_area, 0); 
                                    while($r = mysqli_fetch_assoc($q_area)): 
                                        $id_area = $r['id_area'] ?? 1;
                                        $kapasitas_val = $r['kapasitas'] ?? 0;
                                    ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><strong><?= htmlspecialchars($r['nama_area'] ?? '-'); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($r['jenis_kendaraan'] ?? '-')); ?></span></td>
                                        <td><span class="badge bg-info text-dark fw-bold"><?= $kapasitas_val; ?> Unit</span></td>
                                        <td class="text-center">
                                            <a href="admin.php?hapus_area=<?= $id_area; ?>" onclick="return confirm('Yakin hapus area parkir ini?')" class="btn btn-danger btn-sm rounded-circle shadow-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6" id="tarif-parkir">
                <div class="card h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center border-0">
                        <h5 class="mb-0 fw-bold text-success"><i class="fas fa-tag me-2"></i> Kelola Tarif Resmi</h5>
                        <button class="btn btn-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahTarif">
                            <i class="fas fa-plus me-1"></i> Tambah Tarif
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Jenis Kendaraan</th>
                                        <th>Tarif / Hari</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1; 
                                    mysqli_data_seek($q_tarif, 0); 
                                    while($t = mysqli_fetch_assoc($q_tarif)): 
                                        $id_tarif = $t['id'] ?? $t['id_tarif'] ?? 1;
                                    ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><strong><?= htmlspecialchars(ucfirst($t['jenis_kendaraan'] ?? '-')); ?></strong></td>
                                        <td class="text-success fw-bold">Rp <?= number_format($t['tarif_per_jam'] ?? 0, 0, ',', '.'); ?></td>
                                        <td class="text-center">
                                            <a href="admin.php?hapus_tarif=<?= $id_tarif; ?>" onclick="return confirm('Yakin hapus tarif ini?')" class="btn btn-danger btn-sm rounded-circle shadow-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TRANSAKSI -->
        <div class="row mb-5" id="transaksi">
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-3 border-0">
                        <h5 class="mb-0 fw-bold text-info"><i class="fas fa-exchange-alt me-2"></i> Riwayat Transaksi Kendaraan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active btn-sm rounded-pill me-2" id="pills-keluar-tab" data-bs-toggle="pill" data-bs-target="#pills-keluar" type="button">Kendaraan Keluar (Selesai)</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn-sm rounded-pill" id="pills-masuk-tab" data-bs-toggle="pill" data-bs-target="#pills-masuk" type="button">Masih di Dalam (Aktif)</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-keluar">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nomor Plat</th>
                                                <th>Jenis</th>
                                                <th>Area</th>
                                                <th>Waktu Keluar</th>
                                                <th>Biaya Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no_k = 1;
                                            if(mysqli_num_rows($q_transaksi_keluar) > 0): 
                                                while($k = mysqli_fetch_assoc($q_transaksi_keluar)): 
                                            ?>
                                            <tr>
                                                <td><?= $no_k++; ?></td>
                                                <td><span class="badge bg-dark border text-light px-2 py-1"><?= htmlspecialchars($k['plat_nomor'] ?? '-'); ?></span></td>
                                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $k['jenis_kendaraan'] ?? '-'))); ?></td>
                                                <td><?= htmlspecialchars($k['nama_area'] ?? '-'); ?></td>
                                                <td><small class="text-muted"><?= $k['waktu_keluar'] ?? '-'; ?></small></td>
                                                <td class="text-success fw-bold">Rp <?= number_format($k['biaya_total'] ?? 0, 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endwhile; else: ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi keluar.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-masuk">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nomor Plat</th>
                                                <th>Jenis</th>
                                                <th>Area</th>
                                                <th>Waktu Masuk</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no_m = 1;
                                            if(mysqli_num_rows($q_transaksi_masuk) > 0): 
                                                while($m = mysqli_fetch_assoc($q_transaksi_masuk)): 
                                            ?>
                                            <tr>
                                                <td><?= $no_m++; ?></td>
                                                <td><span class="badge bg-dark border text-light px-2 py-1"><?= htmlspecialchars($m['plat_nomor'] ?? '-'); ?></span></td>
                                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $m['jenis_kendaraan'] ?? '-'))); ?></td>
                                                <td><?= htmlspecialchars($m['nama_area'] ?? '-'); ?></td>
                                                <td><small class="text-muted"><?= $m['waktu_masuk'] ?? '-'; ?></small></td>
                                            </tr>
                                            <?php endwhile; else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada kendaraan di dalam area parkir saat ini.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KELOLA ULASAN -->
        <div class="row mb-4" id="ulasan">
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-3 border-0">
                        <h5 class="mb-0 fw-bold text-warning"><i class="fas fa-comments me-2"></i> Moderasi Ulasan Pengguna</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Rating</th>
                                        <th>Komentar</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if($q_ulasan && mysqli_num_rows($q_ulasan) > 0): 
                                        while($u = mysqli_fetch_assoc($q_ulasan)): 
                                            $id_ulasan = $u['id'] ?? $u['id_ulasan'] ?? 1;
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($u['nama'] ?? '-'); ?></strong></td>
                                        <td><?= str_repeat('⭐', (int)($u['rating'] ?? 0)); ?></td>
                                        <td><?= htmlspecialchars($u['komentar'] ?? '-'); ?></td>
                                        <td><small class="text-muted"><?= $u['tanggal'] ?? '-'; ?></small></td>
                                        <td class="text-center">
                                            <a href="admin.php?hapus_ulasan=<?= $id_ulasan; ?>" onclick="return confirm('Hapus ulasan ini?')" class="btn btn-danger btn-sm rounded-circle shadow-sm">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center text-muted">Belum ada ulasan dari pengguna.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL TAMBAH USER -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold text-white"><i class="fas fa-user-plus text-primary me-2"></i>Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ID Pengguna (Otomatis & Acak)</label>
                            <input type="text" class="form-control font-monospace bg-dark text-warning" value="USR-<?php echo strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5)); ?>" readonly>
                            <small class="text-muted" style="font-size: 11px;">*ID ini digenerate otomatis dan acak oleh sistem saat disimpan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" required placeholder="Masukkan nama lengkap">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Role / Level</label>
                            <select name="role" class="form-select" required>
                                <option value="admin" style="background:#1e293b;">Admin</option>
                                <option value="petugas" style="background:#1e293b;">Petugas</option>
                                <option value="owner" style="background:#1e293b;">Owner</option>
                                <option value="member" style="background:#1e293b;">Member</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_user" class="btn btn-primary btn-sm rounded-pill px-4">Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH AREA PARKIR -->
    <div class="modal fade" id="modalTambahArea" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold text-white"><i class="fas fa-plus-circle text-primary me-2"></i>Tambah Area Parkir & Kapasitas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Area Parkir</label>
                            <input type="text" name="nama_area" class="form-control" required placeholder="Contoh: Area A - Mobil / Area B - Motor">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Kendaraan Khusus Area</label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="Mobil" style="background:#1e293b;">Mobil</option>
                                <option value="Motor" style="background:#1e293b;">Motor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kapasitas Maksimal (Unit)</label>
                            <input type="number" name="kapasitas" class="form-control" required placeholder="Contoh: 50 untuk Mobil, 30 untuk Motor">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_area" class="btn btn-primary btn-sm rounded-pill px-4">Simpan Area</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH TARIF -->
    <div class="modal fade" id="modalTambahTarif" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold text-white"><i class="fas fa-plus-circle text-success me-2"></i>Tambah Tarif Resmi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Kendaraan</label>
                            <select name="jenis_kendaraan" class="form-select" required>
                                <option value="Mobil" style="background:#1e293b;">Mobil</option>
                                <option value="Motor" style="background:#1e293b;">Motor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tarif Per Hari (Rp)</label>
                            <input type="number" name="tarif_per_jam" class="form-control" required placeholder="Contoh: 5000">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_tarif" class="btn btn-success btn-sm rounded-pill px-4">Simpan Tarif</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
            // Membersihkan parameter URL setelah aksi sukses
            if (window.history.replaceState) {
                var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + window.location.hash;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        });
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
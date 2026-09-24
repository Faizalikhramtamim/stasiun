# 🅿️ Sistem Parkir Stasiun (ParkirSolo)

Sistem Informasi Manajemen Parkir berbasis web yang dirancang khusus untuk mengelola area parkir stasiun, mendukung pencatatan kendaraan masuk/keluar, pengelolaan tarif, moderasi ulasan, manajemen multi-user, serta pemantauan kapasitas area parkir secara *real-time*.

**Flowchart:** [Flowchart](https://raw.githubusercontent.com/Faizalikhramtamim/stasiun/refs/heads/main/ChatGPT%20Image%202%20Sep%202026%2C%2011.32.06.png)  
**Mockup:** [Mockup](https://raw.githubusercontent.com/Faizalikhramtamim/stasiun/refs/heads/main/ChatGPT%20Image%202%20Sep%202026%2C%2011.13.22.png)  
**Algoritma:** [Algoritma](https://canva.link/4804dt83ke99y4s)

**aset code:** https://github.com/Faizalikhramtamim/stasiun/tree/master
---

## ✨ Fitur Utama

* **Panel Kontrol Administrator (`admin.php`)**: 
  * Dashboard statistik lengkap (Total Area Parkir, Jenis Tarif, Total Pengguna, dan Total Ulasan).
  * Kelola Data Pengguna (Tambah, Edit detail/password, Atur *role* level seperti Admin, Petugas, Owner, dan Member).
  * Kelola Area Parkir & Kapasitas (Pengaturan nama area, jenis kendaraan khusus, dan batas maksimal unit).
  * Kelola Tarif Resmi (Pengaturan nominal biaya parkir berdasarkan jenis kendaraan).
  * Riwayat & Moderasi Transaksi (Pemisahan tab kendaraan keluar dan kendaraan aktif, serta fitur hapus riwayat).
  * Moderasi Ulasan Pengguna (Pengecekan rating bintang dan komentar dari pengguna).
* **Manajemen Petugas & Autentikasi**: Hak akses khusus bagi petugas untuk mengelola transaksi harian di lapangan.
* **Kendaraan Masuk & Keluar**: Pencatatan data kendaraan (mobil/motor) dengan perhitungan biaya otomatis.
* **Kapasitas Area Parkir Dinamis**: Pemantauan sisa kuota kapasitas secara langsung (*real-time*) di tiap area parkir stasiun.
* **Pencegahan Error & Kompatibilitas Otomatis**: Dilengkapi pelaporan error (*debugging*) dan pengecekan struktur tabel/kolom database secara dinamis agar fleksibel di berbagai versi database.

---

## 🛠️ Teknologi yang Digunakan

* **Backend / Logika**: PHP (Modular dengan ekstensi `MySQLi`)
* **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, dan Font Awesome
* **Database**: MySQL / MariaDB
* **Server Lokal**: XAMPP / Laragon (PHP versi 7.x atau 8.x)
* **Hosting**: InfinityFree (atau layanan web hosting berbasis cPanel / VistaPanel)

---

## 📂 Struktur Direktori Proyek

```text
/
├── admin.php         # Panel kontrol utama administrator sistem
├── koneksi.php       # Konfigurasi koneksi database
├── petugas.php       # Halaman utama & proses logika backend petugas
├── index.php         # Halaman utama / beranda & login sistem
└── README.md         # Dokumentasi proyek

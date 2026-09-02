# 🅿️ Sistem Parkir Stasiun (ParkirSolo)

Sistem Informasi Manajemen Parkir berbasis web yang dirancang khusus untuk mengelola area parkir stasiun, mendukung pencatatan kendaraan masuk/keluar manual, sistem inap, serta manajemen pemotongan kuota area dan *booking* tempat secara *real-time*.
**Flowchart:**[Flowchart](https://raw.githubusercontent.com/Faizalikhramtamim/stasiun/refs/heads/main/ChatGPT%20Image%202%20Sep%202026%2C%2011.32.06.png)
**Mockup:**[Mockup](https://raw.githubusercontent.com/Faizalikhramtamim/stasiun/refs/heads/main/ChatGPT%20Image%202%20Sep%202026%2C%2011.13.22.png)
**Algoritma:**[Algoritma](https://canva.link/4804dt83ke99y4s)

---

## ✨ Fitur Utama

* **Manajemen Petugas & Autentikasi**: Hak akses khusus untuk petugas lapangan guna mengelola transaksi harian dan validasi data.
* **Kendaraan Masuk & Keluar Inap**: Fitur pencatatan kendaraan masuk (mobil/motor) dengan estimasi durasi inap serta perhitungan otomatis biaya dasar dan denda keterlambatan saat kendaraan keluar.
* **Persetujuan Booking (`tb_booking`)**: Petugas dapat menyetujui, membatalkan, atau menghapus data pemesanan tempat parkir dari *member*. Data booking yang disetujui akan otomatis masuk ke tabel transaksi aktif.
* **Kapasitas Area Parkir Dinamis**: Pemantauan sisa kuota kapasitas secara langsung (*real-time*) di tiap area parkir stasiun.
* **Pencegahan Error Otomatis**: Dilengkapi pengecekan struktur tabel dan kolom database secara otomatis agar tetap fleksibel terhadap beberapa versi database yang digunakan.

---

## 🛠️ Teknologi yang Digunakan

* **Backend / Logika**: PHP (Modular dengan ekstensi `MySQLi`)
* **Database**: MySQL / MariaDB
* **Server Lokal**: XAMPP / Laragon (PHP versi 7.x atau 8.x)
* **Hosting**: InfinityFree (atau layanan web hosting berbasis cPanel / VistaPanel)

---

## 📂 Struktur Direktori Proyek

```text
/
├── koneksi.php       # Konfigurasi koneksi database
├── petugas.php       # Halaman utama & proses logika backend petugas
├── index.php         # Halaman login sistem
└── README.md         # Dokumentasi proyek

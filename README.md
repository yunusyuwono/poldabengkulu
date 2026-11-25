# Portal Resmi Polda Bengkulu (Polri Presisi)

Portal informasi publik dan layanan kepolisian resmi berbasis web untuk **Kepolisian Daerah Bengkulu (Polda Bengkulu)**. Dibangun dengan fokus pada kecepatan, keamanan (*security hardening*), standarisasi modern PHP 8.2+, dan arsitektur modular yang responsif.

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=flat&logo=mysql&logoColor=white)
![Security](https://img.shields.io/badge/Security-CSRF%20%7C%20Rate%20Limit%20%7C%20BCrypt-2ea44f?style=flat)
![License](https://img.shields.io/badge/License-Proprietary-blue.svg)

---

## 🏛️ Fitur Utama Sistem

1. **Portal Publik & Informasi Terkini:**
   - Berita Presisi, Pengumuman Resmi, Laporan Kamtibmas, Agenda Pimpinan.
   - Profil Kapolda, Pejabat Utama (PJU), dan Satuan Kerja (Satker) jajaran Polres se-Provinsi Bengkulu.
2. **Sentra Pelayanan Kepolisian Publik:**
   - Informasi terintegrasi layanan SPKT, SKCK Online, Satpas SIM, Pelayanan Izin Keramaian, dan Pengaduan Propam.
3. **Security Hardening (Keamanan Standar Instansi):**
   - **CSRF Token Protection:** Perlindungan token anti-forgery pada semua formulir input data.
   - **Rate Limiting Middleware:** Mencegah serangan brute-force dan spam request ke endpoint krusial.
   - **Secure Session Management:** HTTP-only cookies, SameSite Strict, session timeout otomatis.
   - **Password Hashing:** Enkripsi kata sandi menggunakan algoritma BCrypt dengan cost factor terkalibrasi.
4. **Panel Administrasi Terintegrasi:**
   - Manajemen konten berita, media/galeri, publikasi pengumuman, dan analitik statistik kunjungan masyarakat.

---

## 🛠️ Tech Stack & Arsitektur

- **Backend:** PHP 8.2+ (OOP & Singleton Pattern untuk Database Manager dengan PDO Prepared Statements).
- **Database:** MySQL / MariaDB (Optimized schema UTF-8 MB4 Unicode).
- **Frontend:** Responsive Semantic HTML5, Modern CSS Variables, Vanilla JS (ringan, performa tinggi tanpa library berat pihak ketiga).
- **Security Middleware:** Custom Native Middleware untuk Anti-CSRF, Rate Limiting, dan Request Filtering.

---

## 🚀 Panduan Instalasi Lokal

### Prasyarat
- Web Server: Apache / Nginx (Laragon / XAMPP)
- PHP: Versi 8.2 ke atas
- Ekstensi PHP: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`

### Langkah-langkah
1. Clone repositori ke folder root server Anda:
   ```bash
   git clone https://github.com/yunusyuwono/poldabengkulu.git
   ```
2. Import skema basis data:
   - Buat database MySQL dengan nama `poldabengkulu`.
   - Jalankan impor file `data/dump.sql` atau gunakan auto-seed skema.
3. Konfigurasi Database:
   - Buka `config/config.php` dan sesuaikan kredensial database lokal Anda:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'poldabengkulu');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
4. Jalankan aplikasi di browser:
   - Akses: `http://localhost/poldabengkulu`
   - Panel Admin: `http://localhost/poldabengkulu/admin/`

---

## 📄 Lisensi & Hak Cipta
Hak Cipta © 2026 Kepolisian Daerah Bengkulu / Yunus Yuwono. Dikembangkan untuk transparansi informasi publik dan pelayanan kepolisian presisi.

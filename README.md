# 🪙 CuanLog - Smart POS & Financial Flow for Indonesian SMEs
> **Karya Kompetisi Pemrograman Web Nasional**  
> **Sub-Tema:** *Accelerating Digital Transformation for Business and SMEs*  
> **Tema Pilihan:** Sistem Point of Sale (POS) & Manajemen Keuangan UMKM

---

## 🌟 Tentang CuanLog

**CuanLog** adalah aplikasi web sistem kasir cerdas (*Point of Sale*) dan manajemen arus keuntungan (*financial flow*) berbasis **PHP Natif (Native PHP)** yang dirancang khusus untuk memecahkan tantangan operasional dan mengakselerasi transformasi digital pada sektor Usaha Mikro, Kecil, dan Menengah (UMKM) serta Ekonomi Digital di Indonesia.

Melalui perpaduan arsitektur sistem yang ringan tanpa beban framework yang berat, antarmuka modern yang memanjakan mata (*Emerald-Jade & Amber Palette*), serta integrasi fitur otomasi cerdas, CuanLog membantu pemilik usaha mencatat transaksi kasir secara kilat, mengontrol persediaan stok secara preventif, menganalisis margin keuntungan (cuan) bersih secara otomatis, serta menghadirkan sistem pembayaran digital nirsentuh (QRIS dinamis).

---

## 🚀 Fitur Unggulan & Otomasi Cerdas (Smart Features)

### 1. ⚡ Kasir POS Pintar (High-Speed Cashier Interface)
- **Katalog Cepat & Filter Kategori:** Penyaringan kategori satu klik (Makanan, Minuman, Sembako, Snack, Bumbu, dll.) dan pencarian produk *real-time*.
- **Integrasi Barcode Scanner Ganda:**
  - Mendukung alat pemindai fisik (*USB/Bluetooth barcode scanner*) dengan auto-focus cepat (Tombol pintas `F2`).
  - Pemindai kamera bawaan (*HTML5 Camera Barcode Scanner*) langsung dari laptop, tablet, atau smartphone kasir.
- **Efek Suara Kasir Nyata (Web Audio API Synthesizer):** Bunyi *beep* audio interaktif saat produk dimasukkan ke keranjang dan lonceng sukses saat transaksi selesai (tanpa dependensi file MP3 eksternal).
- **Kalkulasi Otomatis Kembalian & Diskon:** Tombol pecahan uang cepat (*Uang Pas, 10k, 20k, 50k, 100k, 200k*) dan indikator kecukupan uang tunai secara langsung.
- **Simulasi QRIS Dinamis BI:** Menghasilkan QR Code pembayaran QRIS dinamis otomatis sesuai dengan nominal akhir transaksi untuk mendukung ekosistem *cashless* UMKM.

### 2. 🧾 Struk Pembelian Fleksibel & Digital
- **Format Kertas Termal (Thermal 58mm / 80mm):** Siap cetak langsung ke printer kasir (*thermal receipt printer*) dengan sekali klik.
- **Kirim Struk via WhatsApp:** Tombol terintegrasi untuk mengirim ringkasan nota digital ke nomor WhatsApp pelanggan tanpa perlu kertas fisik (*paperless transaction*).

### 3. 📦 Manajemen Inventaris & Peringatan Stok Kritis (Preventive Low-Stock Alert)
- **CRUD Lengkap Produk:** Pengelolaan harga beli (modal), harga jual, sisa stok, satuan, dan deskripsi.
- **Kalkulator Margin Cuan Real-time:** Menampilkan nominal laba kotor dan persentase *markup* otomatis ketika input harga modal dan harga jual.
- **Peringatan Stok Otomatis:** Lonceng notifikasi dan lencana darurat (*warning badge*) ketika jumlah stok mendekati atau berada di bawah ambang batas minimum (*min_stock*).
- **Cetak Label Barcode Harga:** Fitur bawaan untuk mencetak stiker barcode produk lengkap dengan harga jual dan nama toko untuk ditempel di rak barang.

### 4. 👥 CRM & Program Loyalitas Pelanggan (Customer Retention)
- Pencatatan identitas pelanggan (Nama, No WhatsApp, Alamat).
- **Sistem Poin Belanja Otomatis:** Setiap transaksi kelipatan Rp 10.000 otomatis menghadiahkan poin loyalitas bagi pelanggan untuk meningkatkan retensi dan *repeat order*.
- Tombol langsung terhubung ke obrolan WhatsApp pelanggan untuk layanan purna jual atau promosi UMKM.

### 5. 📊 Laporan Keuangan & Analisis Laba/Rugi (Profit & Loss Statement)
- Perhitungan akurat: **Penjualan Bersih &minus; Beban Pokok Modal (HPP) = Laba Bersih (Cuan)**.
- **Grafik Interaktif (Chart.js):** Tren penjualan 7 hari terakhir, perbandingan harian omset vs modal vs keuntungan, serta diagram donat komposisi metode pembayaran (Tunai vs QRIS vs Transfer).
- **Leaderboard Produk Paling Cuan:** Peringkat 10 produk yang menghasilkan laba bersih terbesar bagi toko.
- **Ekspor Data ke CSV/Excel:** Unduh pembukuan kasir dalam format lembar sebar (spreadsheet) yang rapi untuk arsip akuntansi dan perpajakan.

### 6. 📒 Buku Kasbon & Piutang Pelanggan (Smart Debt Book)
- Digitalisasi budaya kasbon warung/toko UMKM di Indonesia: jika pembeli memilih metode pembayaran **Kasbon / Hutang** di kasir, nota tercatat dengan status *Belum Lunas*.
- **Tombol Pelunasan 1-Klik:** Kasir dapat mencatat pelunasan kasbon saat pelanggan membayar di kemudian hari.
- **Pengingat Tagihan via WhatsApp:** Sistem secara otomatis menyusun pesan rincian hutang terformat rapi untuk dikirimkan langsung ke nomor WhatsApp pembeli.

### 7. 📦 Rekomendasi Restock Pintar & Purchase Order (PO) Distributor
- **Algoritma Restock Cerdas:** Otomatis menghitung saran kuantitas kulakan barang saat stok menipis berdasarkan ambang batas minimum.
- **Estimasi Anggaran Modal:** Menghitung total dana yang dibutuhkan untuk kulakan barang ke supplier.
- **Generator Surat Pesanan (PO) WhatsApp:** Menghasilkan draf surat pesanan barang resmi lengkap dengan nama toko, tanggal, item barang, dan jumlah order untuk dikirimkan ke mitra distributor dengan 1 klik.

### 8. 🛡️ Keamanan, Audit Log Sesi & Backup Database 1-Klik
- Pembagian peran pengguna:
  - **Administrator / Owner:** Memiliki akses menyeluruh (laporan laba rugi, pengaturan toko, kelola user kasir).
  - **Kasir:** Terfokus pada transaksi penjualan, cetak nota, dan pencarian produk tanpa akses ke rahasia modal pokok toko.
- Enkripsi kata sandi kuat menggunakan `password_hash()` (Bcrypt).
- **Audit Log Sesi:** Mencatat jejak digital (*audit trail*) setiap aktivitas login, perubahan harga, penambahan stok, dan pelunasan kasbon lengkap dengan IP address.
- **1-Click SQL Backup:** Fitur bawaan untuk mengunduh seluruh skema dan data toko dalam bentuk file `.sql` langsung dari antarmuka web.

---

## 🛠️ Spesifikasi Teknologi

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend** | Native PHP (PHP 7.4 - 8.2+) | Bersih, cepat, modular, tanpa dependensi vendor framework yang berat |
| **Database** | MySQL / MariaDB (PDO) | Query aman dengan *prepared statements* & transaksi database berintegritas |
| **Desain & UI** | Tailwind CSS (CDN) + Custom CSS | Palet warna emerald/giok & emas amber yang elegan, modern, dan profesional |
| **Ikonografi** | FontAwesome 6 + Plus Jakarta Sans | Tipografi modern yang nyaman dibaca |
| **Grafik Data** | Chart.js | Visualisasi finansial interaktif dan responsif |
| **Notifikasi** | SweetAlert2 | Umpan balik aksi pengguna yang interaktif dan ramah pengguna |
| **Barcode & QR** | JsBarcode & QRCode.js | Pembuatan kode barcode dan QRIS langsung di sisi klien |
| **Scanner** | Html5-QRCode | Pemindai barcode berbasis kamera web/ponsel |

---

## 💻 Panduan Instalasi & Menjalankan di Localhost (XAMPP)

1. **Pastikan Web Server dan Database Berjalan:**
   - Buka aplikasi **XAMPP Control Panel**.
   - Klik tombol **Start** pada modul **Apache** dan **MySQL**.

2. **Letakkan File Proyek:**
   - Pastikan folder proyek ini berada di dalam direktori `htdocs`, misalnya:  
     `C:\xampp\htdocs\lomba-26\` (atau `C:\xampp7\htdocs\lomba-26\`).

3. **Inisialisasi Database Otomatis (1-Click Setup Wizard):**
   - Buka peramban (browser) dan akses alamat:
     ```
     http://localhost/lomba-26/install.php
     ```
   - Halaman wizard akan mendeteksi status MySQL Anda.
   - Klik tombol **"Inisialisasi Database Otomatis"**. Sistem akan otomatis membuat database `cuanlog_db`, merangkai seluruh tabel relasional, serta mengisi data demo realistis UMKM Indonesia.
   - *(Alternatif Manual):* Anda juga dapat membuat database bernama `cuanlog_db` di phpMyAdmin lalu mengimpor file `database.sql`.

4. **Akses Aplikasi CuanLog:**
   - Kunjungi:
     ```
     http://localhost/lomba-26/
     ```
   - Anda akan diarahkan ke halaman login yang estetis.

---

## 🔑 Akun Demo Pengujian Dewan Juri

Tersedia tombol pintas *1-Click Demo Login* pada halaman login untuk kemudahan evaluasi dewan juri:

| Role / Peran | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator (Owner)** | `admin` | `admin123` | Akses penuh: Dashboard finansial, Laporan Cuan, Pengaturan Toko, Kelola Pengguna |
| **Kasir Toko** | `kasir` | `kasir123` | Antarmuka kasir POS cepat, transaksi, cetak struk |

---

## 🌐 Panduan Deployment ke Live Hosting Publik

Aplikasi CuanLog dirancang dengan path dinamis yang siap di-deploy ke berbagai layanan hosting:

1. **Menggunakan cPanel / Shared Hosting / VPS:**
   - Ekspor database lokal Anda atau impor file `database.sql` ke database hosting.
   - Unggah seluruh berkas ke folder `public_html`.
   - Sesuaikan konfigurasi database pada file `config/database.php` (DB_HOST, DB_USER, DB_PASS, DB_NAME) atau gunakan *Environment Variables*.
2. **Menggunakan Platform Cloud (Render / Railway):**
   - Buat layanan *MySQL Database*.
   - Pasang *Web Service* PHP dengan *root directory* mengarah ke folder aplikasi.
   - Isi variabel lingkungan:
     - `DB_HOST`: host database hosting
     - `DB_PORT`: port database (default 3306)
     - `DB_NAME`: nama database
     - `DB_USER`: username database
     - `DB_PASS`: password database
   - Jalankan `/install.php` sekali untuk menyiapkan tabel.

---

## 🏆 Kepatuhan terhadap Kriteria Penilaian Kompetisi

1. **Orisinalitas & Etika (100% Original):**
   Kode dibangun dari dasar (*from scratch*) dengan struktur native PHP yang rapi, bersih dari duplikasi kode repositori pihak ketiga, dan tanpa konten yang melanggar etika.
2. **Kesesuaian Tema UMKM & Digital Economy:**
   Menyelesaikan masalah nyata UMKM Indonesia dalam pencatatan keuangan manual, kehilangan jejak barang akibat stok tidak terkontrol, serta mempermudah adopsi transaksi digital nirsentuh.
3. **Responsivitas Perangkat:**
   Telah dioptimasi secara proporsional untuk tampilan Desktop (PC/Laptop), Tablet (iPad/Android POS), dan Ponsel Pintar (Smartphone).
4. **Fungsionalitas Interaktif:**
   Bukan sekadar halaman statis; seluruh formulir CRUD, kalkulasi keranjang kasir, pencetakan struk termal, filter tanggal dinamis, dan ekspor data berjalan 100% fungsional.

---
*Dikembangkan dengan dedikasi untuk memajukan UMKM Indonesia.*  
**CuanLog &bull; Solusi Kasir Pintar & Akuntansi UMKM**


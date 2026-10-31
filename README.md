# INLISLite — Layanan Pemustaka, Penanganan, dan Dashboard Manfaat (PHP + MySQL)

Aplikasi web PHP (native, OOP) + MySQL/MariaDB berdasarkan prototipe INLISLite dan rekomendasi perbaikan skripsi (hal. 170; Gambar 5.1–5.3; Use Case Gambar 4.8).
Tiga fokus utama:

1. **Layanan Pemustaka** – pemustaka mengajukan keluhan/permintaan layanan (Gambar 5.1) dan memantau status serta memberi penilaian.
2. **Penanganan Layanan (Admin)** – admin memproses tiket: solusi standar, atau eskalasi ke manajemen masalah (akar masalah, tindakan, pencegahan) lalu didokumentasikan ke basis pengetahuan (Gambar 5.2).
3. **Manfaat Layanan (Pimpinan)** – dashboard KPI: tingkat penyelesaian, rata-rata waktu penanganan, kepuasan, capaian target SLA, tren bulanan, kategori terbanyak, unduh laporan (Gambar 5.3).

Fitur pendukung (disesuaikan skripsi): login berbasis tabel `users`, katalog/OPAC, bibliografi, koleksi, anggota, sirkulasi, laporan, pengaturan, dan log aktivitas.

## Kebutuhan
PHP 8.1+ (ekstensi pdo_mysql, fileinfo, mbstring, gd/exif opsional) dan MySQL 5.7+/MariaDB 10.4+.

## Instalasi (XAMPP / Laragon)
1. Salin folder ini ke `htdocs` (mis. `C:\xampp\htdocs\inlislite`).
2. Buka phpMyAdmin → **Import** salah satu file di folder `database/` (database `inlislite` dibuat otomatis):
   - `inlislite.sql` — **bersih**: akun admin & pimpinan, data buku (bibliografi & koleksi), pengaturan, serta pilihan jenis/kategori layanan. Tanpa anggota, peminjaman, dan tiket.
   - `inlislite_dengan_data_contoh.sql` — sama, ditambah data contoh (anggota, peminjaman, 45 tiket, masalah, dll.) untuk demo dashboard.
   (Atau: `mysql -u root < database/inlislite.sql`.)
3. Bila kredensial DB bukan `root` tanpa password: salin `config/config.local.php.contoh` menjadi `config/config.local.php` dan ubah isinya (atau gunakan env `DB_HOST DB_PORT DB_NAME DB_USER DB_PASS`).
4. Buka `http://localhost/inlislite/` — halaman utama (beranda) publik; tombol **Masuk** menuju login.

Tanpa XAMPP: `php -S localhost:8080` dari folder ini, lalu buka `http://localhost:8080/`.
Pastikan folder `assets/images/covers/` dapat ditulis (untuk unggah sampul).

## Akun bawaan
| Peran | Username | Password |
|---|---|---|
| Admin | admin | admin123 |
| Pimpinan | pimpinan | pimpinan123 |

Akun pemustaka dibuat oleh admin (menu Keanggotaan lalu Pengguna). Pada file data contoh tersedia juga `siti`, `budi`, `rahmat` (password `pemustaka123`).
**Ganti semua password setelah instalasi** (menu Profil / Pengguna).

## Struktur
```
config/      konfigurasi (dilindungi .htaccess)
app/         bootstrap, helpers, views/layout, classes/ (Database, Auth, Log, Pengguna, Anggota,
             Bibliografi, Koleksi, Transaksi, PermintaanLayanan, Masalah, DashboardManfaat, Laporan, ...)
database/    inlislite.sql (skema + data contoh)
admin/ pemustaka/ pimpinan/   halaman per peran
assets/      CSS/JS/gambar dari prototipe
```

## Basis data
`users`, `jenis_layanan`, `kategori_layanan`, `anggota`, `bibliografi`, `koleksi`, `transaksi`, `tiket_insiden`, `tiket_riwayat`, `masalah`, `basis_pengetahuan`, `dashboard_manfaat`, `laporan_kinerja`, `pengaturan`, `log_aktivitas` (InnoDB, utf8mb4, foreign key).

## Alur tiket
`Baru → Diproses → Selesai`. Solusi standar menyelesaikan tiket langsung; jika tidak ada, tiket dieskalasi menjadi *masalah*, diselesaikan, dicatat ke basis pengetahuan, lalu tiket selesai. Pemustaka memberi rating 1–5. Target waktu (SLA) per urgensi diatur di menu Pengaturan (default 24/72/168 jam).

## Keamanan
Password bcrypt, CSRF pada semua form POST, prepared statements, escape output, pembatasan percobaan login, pembatasan 5 tiket/jam, kontrol akses per peran, validasi unggahan gambar.

## Halaman publik, keluar, dan hapus data
- `index.php` = beranda publik (statistik, layanan, koleksi terbaru, pencarian); `katalog.php` = katalog publik tanpa login.
- Menu akun di pojok kanan atas (ikon pengguna + nama) berisi **Lihat Profil** dan **Log Out**; setelah keluar pengguna kembali ke beranda.
- Admin mengelola pilihan **Jenis** dan **Kategori** keluhan di menu *Kategori & Jenis* (tersimpan di database, langsung muncul di formulir pemustaka; yang sudah dipakai tiket cukup dinonaktifkan).
- Admin dapat menghapus permanen pemustaka lewat menu Pengguna atau Keanggotaan: akun, data anggota, riwayat peminjaman, dan tiket keluhannya ikut terhapus (dikonfirmasi dulu). Ditolak bila anggota masih meminjam buku. Admin tidak dapat menghapus akunnya sendiri atau Admin aktif terakhir.
- Tampilan responsif untuk ponsel (menu samping geser, tabel dapat digulir, input tidak memicu zoom di iOS).

## Catatan
Pimpinan hanya dapat melihat data pengguna (read-only). Modul di luar fokus skripsi pada prototipe (buku tamu, survei, peminjaman mandiri, dst.) tidak disertakan. Versi dan hak cipta INLISLite dihapus dari antarmuka dan logo.

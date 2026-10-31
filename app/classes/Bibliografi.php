<?php
declare(strict_types=1);

/** Kelas Bibliografi: data bibliografis untuk katalog dan OPAC. */
final class Bibliografi
{
    public const BAHAN = ['Monograf', 'Terbitan Berkala', 'Skripsi', 'Tesis', 'Bahan Kartografis', 'Rekaman Suara', 'Sumber Elektronik'];
    public const RUAS = [
        'semua' => 'Sembarang', 'judul' => 'Judul', 'pengarang' => 'Pengarang', 'penerbit' => 'Penerbit',
        'subjek' => 'Subjek', 'nomor_panggil' => 'Nomor Panggil', 'isbn' => 'ISBN',
    ];
    private const SEL = "b.*,
        (SELECT COUNT(*) FROM koleksi k WHERE k.id_bibliografi = b.id_bibliografi) AS total_eks,
        (SELECT COUNT(*) FROM koleksi k WHERE k.id_bibliografi = b.id_bibliografi AND k.status = 'Tersedia') AS tersedia";

    public static function cari(int $id): ?array
    {
        return Database::row('SELECT ' . self::SEL . ' FROM bibliografi b WHERE b.id_bibliografi = ?', [$id]);
    }

    /** Kondisi pencarian (dipakai admin dan OPAC). */
    private static function filter(string $q, string $ruas, string $bahan): array
    {
        $w = ['1=1'];
        $p = [];
        if ($q !== '') {
            $like = '%' . like_escape($q) . '%';
            if ($ruas === 'semua' || !isset(self::RUAS[$ruas])) {
                $w[] = '(b.judul LIKE ? OR b.pengarang LIKE ? OR b.penerbit LIKE ? OR b.subjek LIKE ? OR b.nomor_panggil LIKE ? OR b.isbn LIKE ?)';
                array_push($p, $like, $like, $like, $like, $like, $like);
            } else {
                $w[] = "b.$ruas LIKE ?";   // $ruas dibatasi whitelist RUAS
                $p[] = $like;
            }
        }
        if ($bahan !== '' && in_array($bahan, self::BAHAN, true)) {
            $w[] = 'b.jenis_bahan = ?';
            $p[] = $bahan;
        }
        return [implode(' AND ', $w), $p];
    }

    public static function hitung(string $q = '', string $ruas = 'semua', string $bahan = ''): int
    {
        [$w, $p] = self::filter($q, $ruas, $bahan);
        return (int)Database::value("SELECT COUNT(*) FROM bibliografi b WHERE $w", $p);
    }

    public static function daftar(string $q, string $ruas, string $bahan, int $limit, int $offset, string $urut = 'judul'): array
    {
        [$w, $p] = self::filter($q, $ruas, $bahan);
        $order = $urut === 'terbaru' ? 'b.id_bibliografi DESC' : 'b.judul ASC';
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        return Database::all('SELECT ' . self::SEL . " FROM bibliografi b WHERE $w ORDER BY $order LIMIT $limit OFFSET $offset", $p);
    }

    /** Koleksi yang paling sering dipinjam. */
    public static function populer(int $n = 8): array
    {
        $n = max(1, $n);
        return Database::all(
            'SELECT ' . self::SEL . ',
                    (SELECT COUNT(*) FROM transaksi t JOIN koleksi kk ON kk.id_koleksi = t.id_koleksi WHERE kk.id_bibliografi = b.id_bibliografi) AS jml_pinjam
             FROM bibliografi b ORDER BY jml_pinjam DESC, b.judul ASC LIMIT ' . $n
        );
    }

    public static function terbaru(int $n = 8): array
    {
        return self::daftar('', 'semua', '', $n, 0, 'terbaru');
    }

    public static function eksemplar(int $idBib): array
    {
        return Database::all('SELECT * FROM koleksi WHERE id_bibliografi = ? ORDER BY barcode', [$idBib]);
    }

    public static function validasi(array $d, ?int $id): array
    {
        $err = [];
        if ($d['judul'] === '' || mb_strlen($d['judul']) > 255) {
            $err['judul'] = 'Judul wajib diisi (maksimal 255 karakter).';
        }
        if ($d['pengarang'] === '' || mb_strlen($d['pengarang']) > 150) {
            $err['pengarang'] = 'Pengarang wajib diisi (maksimal 150 karakter).';
        }
        if (mb_strlen($d['penerbit']) > 150) {
            $err['penerbit'] = 'Penerbit maksimal 150 karakter.';
        }
        if (mb_strlen($d['kota_terbit']) > 80) {
            $err['kota_terbit'] = 'Kota terbit maksimal 80 karakter.';
        }
        if ($d['tahun_terbit'] !== '' && (!ctype_digit($d['tahun_terbit']) || (int)$d['tahun_terbit'] < 1000 || (int)$d['tahun_terbit'] > (int)date('Y') + 1)) {
            $err['tahun_terbit'] = 'Tahun terbit tidak valid.';
        }
        if ($d['isbn'] !== '') {
            if (!preg_match('/^[0-9Xx\-]{8,30}$/', $d['isbn'])) {
                $err['isbn'] = 'Format ISBN/ISSN tidak valid (angka dan tanda hubung).';
            } elseif (Database::value('SELECT COUNT(*) FROM bibliografi WHERE isbn = ? AND id_bibliografi <> ?', [$d['isbn'], $id ?? 0])) {
                $err['isbn'] = 'ISBN sudah terdaftar pada bibliografi lain.';
            }
        }
        if (!isset($err['judul']) && !isset($err['pengarang'])
            && Database::value('SELECT COUNT(*) FROM bibliografi WHERE judul = ? AND pengarang = ? AND id_bibliografi <> ?', [$d['judul'], $d['pengarang'], $id ?? 0])) {
            $err['judul'] = 'Judul dengan pengarang yang sama sudah terdaftar.';
        }
        if (!in_array($d['jenis_bahan'], self::BAHAN, true)) {
            $err['jenis_bahan'] = 'Jenis bahan tidak valid.';
        }
        if (mb_strlen($d['subjek']) > 255) {
            $err['subjek'] = 'Subjek maksimal 255 karakter.';
        }
        if (mb_strlen($d['nomor_panggil']) > 60) {
            $err['nomor_panggil'] = 'Nomor panggil maksimal 60 karakter.';
        }
        if (mb_strlen($d['deskripsi_fisik']) > 120) {
            $err['deskripsi_fisik'] = 'Deskripsi fisik maksimal 120 karakter.';
        }
        return $err;
    }

    /**
     * Simpan file cover yang diunggah. Mengembalikan path relatif (mis. images/covers/xxx.jpg) atau null bila tidak ada file.
     * @throws AturanBisnis bila file tidak valid
     */
    public static function simpanCover(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new AturanBisnis('Gagal mengunggah cover (kode ' . (int)$file['error'] . ').');
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new AturanBisnis('Ukuran cover maksimal 2 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'][$mime] ?? null;
        if ($ext === null || @getimagesize($file['tmp_name']) === false) {
            throw new AturanBisnis('Cover harus berupa gambar JPG, PNG, atau GIF.');
        }
        $dir = APP_ROOT . '/assets/images/covers';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new AturanBisnis('Folder cover tidak dapat dibuat.');
        }
        $nama = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) {
            throw new AturanBisnis('Cover tidak dapat disimpan. Periksa izin tulis folder assets/images/covers.');
        }
        return 'images/covers/' . $nama;
    }

    public static function simpan(array $d, ?int $id, ?string $coverBaru): int
    {
        $v = [
            $d['judul'], $d['pengarang'], $d['penerbit'] ?: null, $d['kota_terbit'] ?: null,
            $d['tahun_terbit'] === '' ? null : (int)$d['tahun_terbit'], $d['isbn'] ?: null, $d['subjek'] ?: null,
            $d['nomor_panggil'] ?: null, $d['jenis_bahan'], $d['deskripsi_fisik'] ?: null,
        ];
        if ($id === null) {
            $v[] = $coverBaru;
            Database::run(
                'INSERT INTO bibliografi (judul, pengarang, penerbit, kota_terbit, tahun_terbit, isbn, subjek, nomor_panggil, jenis_bahan, deskripsi_fisik, cover)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                $v
            );
            return Database::lastId();
        }
        $sql = 'UPDATE bibliografi SET judul=?, pengarang=?, penerbit=?, kota_terbit=?, tahun_terbit=?, isbn=?, subjek=?, nomor_panggil=?, jenis_bahan=?, deskripsi_fisik=?';
        if ($coverBaru !== null) {
            $sql .= ', cover=?';
            $v[] = $coverBaru;
        }
        $sql .= ' WHERE id_bibliografi=?';
        $v[] = $id;
        Database::run($sql, $v);
        return $id;
    }

    public static function hapus(int $id): void
    {
        if (!self::cari($id)) {
            throw new AturanBisnis('Bibliografi tidak ditemukan.');
        }
        if (Database::value('SELECT COUNT(*) FROM koleksi WHERE id_bibliografi = ?', [$id])) {
            throw new AturanBisnis('Bibliografi masih memiliki eksemplar koleksi. Hapus eksemplarnya terlebih dahulu.');
        }
        Database::run('DELETE FROM bibliografi WHERE id_bibliografi = ?', [$id]);
    }

    /** URL gambar cover (atau gambar bawaan). */
    public static function urlCover(?string $cover): string
    {
        if ($cover) {
            $cover = preg_replace('#^assets/#', '', $cover);
            if (is_file(APP_ROOT . '/assets/' . $cover)) {
                return asset($cover);
            }
        }
        return asset('images/tdkada.gif');
    }
}

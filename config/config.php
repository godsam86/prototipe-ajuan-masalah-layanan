<?php
/**
 * Konfigurasi aplikasi.
 * Nilai dapat diubah langsung di sini, atau ditimpa lewat file config/config.local.php
 * (salin dari config.local.php.contoh) atau variabel lingkungan DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
 */
$config = [
    'app_name'  => 'INLISLite',
    'timezone'  => 'Asia/Jakarta',
    // Kosongkan agar terdeteksi otomatis. Isi jika aplikasi ditaruh di sub-folder tertentu, mis. '/inlislite'
    'base_url'  => '',
    'debug'     => false,
    'session_name' => 'INLISSESSID',
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => (int)(getenv('DB_PORT') ?: 3306),
        'name'    => getenv('DB_NAME') ?: 'inlislite',
        'user'    => getenv('DB_USER') !== false ? getenv('DB_USER') : 'root',
        'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, (array)require $local);
}
return $config;

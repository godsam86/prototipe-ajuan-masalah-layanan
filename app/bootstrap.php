<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
$GLOBALS['config'] = require APP_ROOT . '/config/config.php';
$cfg = $GLOBALS['config'];

date_default_timezone_set($cfg['timezone']);
mb_internal_encoding('UTF-8');

if (!empty($cfg['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
}

// Base URL (path) aplikasi, mis. "" atau "/inlislite"
if ($cfg['base_url'] !== '') {
    define('BASE_URL', rtrim($cfg['base_url'], '/'));
} else {
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $app = realpath(APP_ROOT);
    $base = '';
    if ($doc && $app && strpos($app, $doc) === 0) {
        $base = str_replace('\\', '/', substr($app, strlen($doc)));
    }
    define('BASE_URL', rtrim($base, '/'));
}

spl_autoload_register(function (string $class): void {
    $file = APP_ROOT . '/app/classes/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($cfg['session_name']);
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '7200');
    session_start();
}

set_exception_handler(function (Throwable $e): void {
    error_log('[INLISLite] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    $debug = !empty($GLOBALS['config']['debug']);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Terjadi Kesalahan</title></head>'
       . '<body style="font-family:sans-serif;padding:40px"><h2>Terjadi kesalahan pada sistem</h2>'
       . '<p>Silakan coba lagi. Jika masalah berlanjut, hubungi administrator. '
       . '(Pastikan basis data sudah diimpor dan konfigurasi di <code>config/config.php</code> benar.)</p>';
    if ($debug) {
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
    exit;
});

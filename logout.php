<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if (is_post()) {
    csrf_cek();
    Auth::keluar();
    session_start();
    flash('success', 'Anda telah keluar dari sistem.');
}
redirect('index.php');

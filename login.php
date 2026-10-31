<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if (Auth::user()) {
    redirect(Auth::beranda());
}

$galat = '';
if (is_post()) {
    csrf_cek();
    [$ok, $pesan] = Auth::masuk(post('username'), $_POST['password'] ?? '');
    if ($ok) {
        redirect(Auth::beranda());
    }
    $galat = $pesan;
}
$nmPerpus = Pengaturan::ambil('nama_perpustakaan', 'Perpustakaan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= e(asset('images/favicon.png')) ?>">
    <title>Login - <?= e($nmPerpus) ?></title>
    <link href="<?= e(asset('css/bootstrap.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/font-awesome.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/login.css')) ?>" rel="stylesheet">
    <style>
        body { background: url(<?= e(asset('images/bg_bodylogin.png')) ?>) repeat-x top #5d812f !important; font-family: 'Candara', Arial, sans-serif; }
        #content .cloud { display:block; width:383px; max-width:90%; height:200px; top:40px; position:relative;
            background:url(<?= e(asset('images/logo-inlislite_bo.png')) ?>) no-repeat center / contain; margin:0 auto 15px auto;
            filter:drop-shadow(5px 5px 5px #0a212f); }
        .login-box { width:460px; max-width:96%; margin:0 auto; }
        .login-card { margin-top:20px; }
        #content .button_login { margin:0; height:42px; width:113px; background:url(<?= e(asset('images/bt_signin.png')) ?>); border:0; cursor:pointer; display:inline-block; }
        .login-error { background:rgba(185,28,28,.92); color:#fff; border-radius:4px; padding:10px 14px; margin:0 auto 14px; max-width:420px; font-size:14px; }
        .login-ok { background:rgba(21,128,61,.92); color:#fff; border-radius:4px; padding:10px 14px; margin:0 auto 14px; max-width:420px; font-size:14px; }
        .login-footer { margin-top:40px; color:#fff; font-size:13px; text-shadow:0 0 3px #000; padding:0 10px; }
        .field-row { display:flex; justify-content:center; gap:0; flex-wrap:wrap; }
        .field-row input { height:42px; width:160px; font-size:14px; border-radius:0; }
        .field-row input:first-child { border-radius:3px 0 0 3px; }
    </style>
</head>
<body class="login-page">
<div id="content" align="center">
    <div class="login-box">
        <div class="login-logo"><div class="cloud" role="img" aria-label="INLISLite"></div></div>
        <div style="opacity:.9;text-shadow:1px 1px 2px #027fa5,0 0 25px #e3e3e459,0 0 5px darkblue;">
            <span style="font-weight:bold;font-size:28px;color:#fff;">LOGIN</span>
        </div>
        <div style="opacity:.9;text-shadow:2px 2px #333;margin-bottom:20px;">
            <span style="font-weight:bold;font-size:17px;color:#fff;"><?= e(mb_strtoupper($nmPerpus)) ?></span>
        </div>

        <div class="login-card">
            <?php foreach (flash_ambil() as $f): ?>
                <div class="<?= $f['type'] === 'success' ? 'login-ok' : 'login-error' ?>"><?= e($f['msg']) ?></div>
            <?php endforeach; ?>
            <?php if ($galat !== ''): ?><div class="login-error" role="alert"><?= e($galat) ?></div><?php endif; ?>
            <form method="post" action="<?= e(url('login.php')) ?>" autocomplete="off">
                <?= csrf_field() ?>
                <div class="field-row">
                    <input type="text" name="username" class="form-control" placeholder="Username" value="<?= val('username') ?>" maxlength="50" required autofocus>
                    <input type="password" name="password" class="form-control" placeholder="Password" maxlength="200" required>
                    <button type="submit" class="btn button_login" title="Masuk"></button>
                </div>
            </form>
        </div>
    </div>
    <div style="margin-top:14px"><a href="<?= e(url('index.php')) ?>" style="color:#fff;text-shadow:0 0 3px #000"><i class="fa fa-arrow-left"></i> Kembali ke Beranda</a></div>
    <div class="login-footer"><?= e($nmPerpus) ?></div>
</div>
</body>
</html>

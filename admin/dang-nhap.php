<?php
// Trang đăng nhập của thủ thư / admin.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/xac-thuc.php';
require_once __DIR__ . '/../db.php';
khoiDongSession();

// Đã đăng nhập rồi thì vào thẳng trang chủ admin
if (daDangNhap()) {
    header('Location: index.php');
    exit;
}

$tenDangNhap = '';
$loi         = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();
    $tenDangNhap = layChuoiPost('ten_dang_nhap');
    $matKhau     = isset($_POST['mat_khau']) && is_string($_POST['mat_khau']) ? $_POST['mat_khau'] : '';   // không trim mật khẩu

    $ketQua = dangNhapNhanVien($conn, $tenDangNhap, $matKhau);
    if ($ketQua['thanh_cong']) {
        header('Location: index.php');
        exit;
    }
    $loi = $ketQua['loi'];   // thông báo chung, không lộ tài khoản nào tồn tại
}

// Lời nhắn "đã đăng xuất" chỉ hiện khi MỞ trang (GET). Gửi form đăng nhập sai (POST về cùng địa chỉ
// còn ?da_thoat=1) thì không hiện, tránh dòng xanh "đã đăng xuất" nằm chung với dòng đỏ báo lỗi.
$moTrang = ($_SERVER['REQUEST_METHOD'] !== 'POST');
$daThoat = $moTrang && (layChuoiGet('da_thoat') === '1');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - Quản lý thư viện</title>
    <link rel="stylesheet" href="../assets/fonts/fonts.css">
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="auth-body">
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-head">
            <img class="ic" src="../assets/icons/trang/book.svg" alt="" width="30" height="30">
            <div>
                <span class="logo">Thư viện</span>
                <small>Khu quản lý dành cho thủ thư</small>
            </div>
        </div>
        <div class="auth-main">
            <h1>Đăng nhập</h1>
            <p class="text-muted mb-4">Nhập tên đăng nhập và mật khẩu của bạn.</p>

            <?php if ($daThoat): ?>
                <div class="alert alert-success alert-thong-bao py-2">
                    <img class="ic" src="../assets/icons/den/check-circle.svg" alt="" width="20" height="20">
                    <span>Bạn đã đăng xuất.</span>
                </div>
            <?php endif; ?>
            <?php if ($loi !== ''): ?>
                <div class="alert alert-danger alert-thong-bao py-2" role="alert">
                    <img class="ic" src="../assets/icons/den/x-circle.svg" alt="" width="20" height="20">
                    <span><?= e($loi) ?></span>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                <div class="mb-3">
                    <label for="ten_dang_nhap" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="ten_dang_nhap" name="ten_dang_nhap"
                           value="<?= e($tenDangNhap) ?>" maxlength="30" autocomplete="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="mat_khau" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="mat_khau" name="mat_khau"
                           autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>
    <p class="auth-help">
        <a class="auth-back" href="../index.php">
            <img class="ic" src="../assets/icons/den/chevron-left.svg" alt="" width="14" height="14">Về trang chọn
        </a>
    </p>
</main>
</body>
</html>

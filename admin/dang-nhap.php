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
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<main class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-1">Quản lý thư viện</h1>
                    <p class="text-center text-muted mb-4">Đăng nhập dành cho thủ thư</p>

                    <?php if ($daThoat): ?>
                        <div class="alert alert-success py-2">Bạn đã đăng xuất.</div>
                    <?php endif; ?>
                    <?php if ($loi !== ''): ?>
                        <div class="alert alert-danger py-2" role="alert"><?= e($loi) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                        <div class="mb-3">
                            <label for="ten_dang_nhap" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="ten_dang_nhap" name="ten_dang_nhap"
                                   value="<?= e($tenDangNhap) ?>" maxlength="30" autocomplete="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="mat_khau" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="mat_khau" name="mat_khau"
                                   autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                    </form>
                    <p class="text-center small text-muted mt-3 mb-0">
                        <a href="../index.php">&larr; Về trang chọn</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

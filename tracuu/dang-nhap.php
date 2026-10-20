<?php
// Trang đăng nhập của độc giả: nhập mã độc giả + mật khẩu để tra cứu sách đang mượn và lịch sử.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/xac-thuc-doc-gia.php';
require_once __DIR__ . '/../db.php';
khoiDongSession();

// Đã đăng nhập rồi thì vào thẳng trang tra cứu
if (daDangNhapDocGia()) {
    header('Location: index.php');
    exit;
}

$maDocGia = '';
$loi      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();
    $maDocGia = layChuoiPost('ma_doc_gia');
    $matKhau  = isset($_POST['mat_khau']) && is_string($_POST['mat_khau']) ? $_POST['mat_khau'] : '';   // không trim mật khẩu

    $ketQua = dangNhapDocGia($conn, $maDocGia, $matKhau);
    if ($ketQua['thanh_cong']) {
        header('Location: index.php');
        exit;
    }
    $loi = $ketQua['loi'];
}

$daThoat    = (layChuoiGet('da_thoat') === '1');        // vừa đăng xuất xong
$hetHieuLuc = (layChuoiGet('het_hieu_luc') === '1');    // tài khoản bị khóa / xóa khi đang dùng
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - Tra cứu thư viện</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<main class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-1">Tra cứu thư viện</h1>
                    <p class="text-center text-muted mb-4">Đăng nhập dành cho độc giả</p>

                    <?php if ($daThoat): ?>
                        <div class="alert alert-success py-2">Bạn đã đăng xuất.</div>
                    <?php endif; ?>
                    <?php if ($hetHieuLuc): ?>
                        <div class="alert alert-warning py-2">Tài khoản của bạn hiện không dùng được. Hãy liên hệ thủ thư.</div>
                    <?php endif; ?>
                    <?php if ($loi !== ''): ?>
                        <div class="alert alert-danger py-2" role="alert"><?= e($loi) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                        <div class="mb-3">
                            <label for="ma_doc_gia" class="form-label">Mã độc giả</label>
                            <input type="text" class="form-control" id="ma_doc_gia" name="ma_doc_gia"
                                   value="<?= e($maDocGia) ?>" maxlength="20" autocomplete="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="mat_khau" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="mat_khau" name="mat_khau"
                                   autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Đăng nhập</button>
                    </form>
                    <p class="text-center small text-muted mt-3 mb-0">
                        Nhập đúng mà vẫn không vào được? Hãy hỏi thủ thư.<br>
                        <a href="../index.php">&larr; Về trang chọn</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

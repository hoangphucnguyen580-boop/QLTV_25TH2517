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
                <span class="logo">Tra cứu thư viện</span>
                <small>Dành cho độc giả</small>
            </div>
        </div>
        <div class="auth-main">
            <h1>Đăng nhập</h1>
            <p class="text-muted mb-4">Nhập mã độc giả và mật khẩu để xem sách bạn đang mượn.</p>

            <?php if ($daThoat): ?>
                <div class="alert alert-success alert-thong-bao py-2">
                    <img class="ic" src="../assets/icons/den/check-circle.svg" alt="" width="20" height="20">
                    <span>Bạn đã đăng xuất.</span>
                </div>
            <?php endif; ?>
            <?php if ($hetHieuLuc): ?>
                <div class="alert alert-warning alert-thong-bao py-2">
                    <img class="ic" src="../assets/icons/den/exclamation-triangle.svg" alt="" width="20" height="20">
                    <span>Tài khoản của bạn hiện không dùng được. Hãy liên hệ thủ thư.</span>
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
                    <label for="ma_doc_gia" class="form-label">Mã độc giả</label>
                    <input type="text" class="form-control" id="ma_doc_gia" name="ma_doc_gia"
                           value="<?= e($maDocGia) ?>" maxlength="20" autocomplete="username" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="mat_khau" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="mat_khau" name="mat_khau"
                           autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
            <p class="auth-help mb-0">Nhập đúng mà vẫn không vào được? Hãy hỏi thủ thư.</p>
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

<?php
// Phần đầu HTML chung của trang tra cứu độc giả: thẻ head, thanh menu, khung thông báo.
// Trang gọi phải đặt trước khi require file này: $tieuDe (chuỗi) và $docGia (từ khoi-tao-tracuu.php).
$thongBao  = layThongBao();
$loaiHopLe = ['success', 'danger', 'warning', 'info'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> - Tra cứu thư viện</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">Tra cứu thư viện</a>
        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <?= e($docGia['ho_ten']) ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-3">
                <p class="small text-muted mb-2"><?= e($docGia['ma_doc_gia']) ?> &middot; <?= e($docGia['ten_loai']) ?></p>
                <form method="post" action="dang-xuat.php">
                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                    <button type="submit" class="btn btn-danger btn-sm w-100">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
<?php if ($thongBao !== null): ?>
    <?php $loaiAlert = in_array($thongBao['loai'], $loaiHopLe, true) ? $thongBao['loai'] : 'info'; ?>
    <div class="alert alert-<?= $loaiAlert ?>" role="alert"><?= e($thongBao['noi_dung']) ?></div>
<?php endif; ?>

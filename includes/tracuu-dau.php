<?php
// Phần đầu HTML chung của trang tra cứu độc giả: thẻ head, thanh trên cùng, khung thông báo.
// Trang gọi phải đặt trước khi require file này: $tieuDe (chuỗi) và $docGia (từ khoi-tao-tracuu.php).
// Cặp với tracuu-cuoi.php: file này MỞ <main>, tracuu-cuoi.php ĐÓNG nó (tracuu-cuoi.php không đổi).
$thongBao  = layThongBao();
$loaiHopLe = ['success', 'danger', 'warning', 'info'];
// Icon cho từng loại thông báo
$iconThongBao = [
    'success' => 'check-circle',
    'danger'  => 'x-circle',
    'warning' => 'exclamation-triangle',
    'info'    => 'info-circle',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> - Tra cứu thư viện</title>
    <link rel="stylesheet" href="../assets/fonts/fonts.css">
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#noidung">Bỏ qua, tới nội dung</a>

<header class="tc-bar">
    <div class="container d-flex justify-content-between align-items-center gap-3">
        <a class="tc-brand" href="index.php">
            <img class="ic" src="../assets/icons/trang/book.svg" alt="" width="24" height="24">Tra cứu thư viện
        </a>
        <div class="dropdown">
            <button class="btn btn-sm dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <img class="ic" src="../assets/icons/trang/person.svg" alt="" width="16" height="16">
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
</header>

<main id="noidung" class="container py-4">
<?php if ($thongBao !== null): ?>
    <?php $loaiAlert = in_array($thongBao['loai'], $loaiHopLe, true) ? $thongBao['loai'] : 'info'; ?>
    <div class="alert alert-<?= $loaiAlert ?> alert-thong-bao" role="alert">
        <img class="ic" src="../assets/icons/den/<?= e($iconThongBao[$loaiAlert]) ?>.svg" alt="" width="20" height="20">
        <span><?= e($thongBao['noi_dung']) ?></span>
    </div>
<?php endif; ?>

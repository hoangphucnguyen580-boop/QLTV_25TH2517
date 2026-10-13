<?php
// Phần đầu HTML chung của trang admin: thẻ head, thanh menu, khung thông báo.
// Trang gọi phải đặt trước khi require file này:
//   $tieuDe       : tiêu đề trang (chuỗi)
//   $menuHienTai  : 'trangchu' hoặc 'sach' (để tô sáng mục menu đang đứng)
$nhanVien = nhanVienHienTai();
$thongBao = layThongBao();
$loaiHopLe = ['success', 'danger', 'warning', 'info'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tieuDe) ?> - Quản lý thư viện</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Quản lý thư viện</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuChinh"
                aria-controls="menuChinh" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuChinh">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link<?= $menuHienTai === 'trangchu' ? ' active' : '' ?>" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $menuHienTai === 'sach' ? ' active' : '' ?>" href="sach.php">Sách</a>
                </li>
            </ul>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <?= e($nhanVien['ho_ten']) ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3">
                    <p class="small text-muted mb-2"><?= e(tenVaiTro($nhanVien['vai_tro'])) ?></p>
                    <form method="post" action="dang-xuat.php">
                        <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                        <button type="submit" class="btn btn-danger btn-sm w-100">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
<?php if ($thongBao !== null): ?>
    <?php $loaiAlert = in_array($thongBao['loai'], $loaiHopLe, true) ? $thongBao['loai'] : 'info'; ?>
    <div class="alert alert-<?= $loaiAlert ?>" role="alert"><?= e($thongBao['noi_dung']) ?></div>
<?php endif; ?>

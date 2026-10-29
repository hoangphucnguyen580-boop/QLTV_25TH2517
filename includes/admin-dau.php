<?php
// Phần đầu HTML chung của trang admin: thẻ head, sidebar (máy tính) / menu trượt (điện thoại), khung thông báo.
// Trang gọi phải đặt trước khi require file này:
//   $tieuDe       : tiêu đề trang (chuỗi)
//   $menuHienTai  : 'trangchu' | 'phieumuon' | 'sach' | 'docgia' | 'theloai' | 'nhaxuatban' | 'thongke'
//                   (để tô sáng mục menu đang đứng)
// Cặp với admin-cuoi.php: file này MỞ <div class="app">, <div class="content-col"> và <main>,
// admin-cuoi.php ĐÓNG chúng lại.
$nhanVien  = nhanVienHienTai();
$thongBao  = layThongBao();
$loaiHopLe = ['success', 'danger', 'warning', 'info'];

// Các mục menu: khóa => [file đích, tên file icon (không có đuôi .svg), nhãn hiển thị]
$cacMucMenu = [
    'trangchu'   => ['index.php',      'house',        'Trang chủ'],
    'phieumuon'  => ['phieumuon.php',  'journal-text', 'Phiếu mượn'],
    'sach'       => ['sach.php',       'book',         'Sách'],
    'docgia'     => ['docgia.php',     'people',       'Độc giả'],
    'theloai'    => ['theloai.php',    'tags',         'Thể loại'],
    'nhaxuatban' => ['nhaxuatban.php', 'building',     'Nhà xuất bản'],
    'thongke'    => ['thongke.php',    'bar-chart',    'Thống kê'],
];
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
    <title><?= e($tieuDe) ?> - Quản lý thư viện</title>
    <link rel="stylesheet" href="../assets/fonts/fonts.css">
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#noidung">Bỏ qua menu, tới nội dung</a>

<div class="app">
    <div class="side-col">
        <!-- offcanvas-lg: từ màn hình lớn (>= 992px) là sidebar cố định, nhỏ hơn thì là menu trượt ra từ bên trái -->
        <aside class="side offcanvas-lg offcanvas-start" tabindex="-1" id="menuChinh" aria-label="Menu chính">
            <div class="offcanvas-header">
                <span class="logo"><img class="ic" src="../assets/icons/trang/book.svg" alt="" width="26" height="26">Thư viện</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                        data-bs-target="#menuChinh" aria-label="Đóng menu"></button>
            </div>
            <div class="offcanvas-body">
                <a class="logo d-none d-lg-flex" href="index.php">
                    <img class="ic" src="../assets/icons/trang/book.svg" alt="" width="26" height="26">Thư viện
                </a>

                <nav aria-label="Các mục quản lý">
                    <ul class="side-nav">
                    <?php foreach ($cacMucMenu as $khoaMuc => $mucMenu): ?>
                        <li>
                            <a href="<?= e($mucMenu[0]) ?>"<?= $menuHienTai === $khoaMuc ? ' aria-current="page"' : '' ?>>
                                <img class="ic" src="../assets/icons/trang/<?= e($mucMenu[1]) ?>.svg" alt="" width="20" height="20">
                                <?= e($mucMenu[2]) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </nav>

                <div class="side-who">
                    <b><?= e($nhanVien['ho_ten']) ?></b>
                    <span><?= e(tenVaiTro($nhanVien['vai_tro'])) ?></span>
                    <form method="post" action="dang-xuat.php">
                        <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                        <button type="submit" class="side-out">
                            <img class="ic" src="../assets/icons/trang/box-arrow-right.svg" alt="" width="16" height="16">Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    </div>

    <div class="content-col">
        <!-- Thanh trên cùng, chỉ hiện trên điện thoại: chứa nút mở menu -->
        <header class="mbar d-lg-none">
            <button type="button" class="mbtn" data-bs-toggle="offcanvas" data-bs-target="#menuChinh"
                    aria-controls="menuChinh" aria-label="Mở menu">
                <img class="ic" src="../assets/icons/trang/list.svg" alt="" width="22" height="22">
            </button>
            <span class="logo">Thư viện</span>
        </header>

        <main id="noidung" class="page">
<?php if ($thongBao !== null): ?>
    <?php $loaiAlert = in_array($thongBao['loai'], $loaiHopLe, true) ? $thongBao['loai'] : 'info'; ?>
            <div class="alert alert-<?= $loaiAlert ?> alert-thong-bao" role="alert">
                <img class="ic" src="../assets/icons/den/<?= e($iconThongBao[$loaiAlert]) ?>.svg" alt="" width="20" height="20">
                <span><?= e($thongBao['noi_dung']) ?></span>
            </div>
<?php endif; ?>

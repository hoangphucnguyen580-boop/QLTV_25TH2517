<?php
// Đăng xuất độc giả: chỉ nhận POST có token CSRF (để một đường link lạ không thể ép người dùng đăng xuất).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/xac-thuc-doc-gia.php';
khoiDongSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
batBuocCsrf();
dangXuatDocGia();
header('Location: dang-nhap.php?da_thoat=1');
exit;

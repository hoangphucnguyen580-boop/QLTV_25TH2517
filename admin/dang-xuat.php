<?php
// Đăng xuất: chỉ nhận POST có token CSRF (để một đường link lạ không thể ép người dùng đăng xuất).
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/xac-thuc.php';
khoiDongSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
batBuocCsrf();
dangXuat();
header('Location: dang-nhap.php?da_thoat=1');
exit;

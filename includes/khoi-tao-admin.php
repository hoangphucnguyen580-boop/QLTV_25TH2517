<?php
// Khởi tạo dùng chung cho mọi trang admin (trừ trang đăng nhập):
// nạp thư viện, kết nối CSDL, bật session, bắt buộc đã đăng nhập.
// Mỗi trang admin viết dòng require_once file này ĐẦU TIÊN, trước khi in bất kỳ HTML nào.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csdl.php';
require_once __DIR__ . '/xac-thuc.php';
require_once __DIR__ . '/../db.php';

khoiDongSession();
yeuCauDangNhap();

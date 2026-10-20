<?php
// Khởi tạo dùng chung cho mọi trang tra cứu của độc giả (trừ trang đăng nhập):
// nạp thư viện, kết nối CSDL, bật session, bắt buộc độc giả đã đăng nhập.
// Sau file này có sẵn: $conn, $cauHinh, và $docGia (thông tin độc giả đang đăng nhập).
// Mỗi trang viết dòng require_once file này ĐẦU TIÊN, trước khi in bất kỳ HTML nào.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csdl.php';
require_once __DIR__ . '/muon-tra.php';
require_once __DIR__ . '/xac-thuc-doc-gia.php';
require_once __DIR__ . '/../db.php';

khoiDongSession();
$docGia = yeuCauDangNhapDocGia($conn);

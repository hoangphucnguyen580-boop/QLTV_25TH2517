<?php
// Kết nối CSDL bằng mysqli. Cấu hình đọc từ config.php (không ghi cứng trong code).
$cauHinh = require __DIR__ . '/config.php';

// Cho mysqli ném ngoại lệ khi lỗi. Từ PHP 8.1 đây là mặc định, đặt rõ để chạy giống nhau ở mọi bản PHP.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect(
        $cauHinh['host'],
        $cauHinh['user'],
        $cauHinh['pass'],
        $cauHinh['db'],
        $cauHinh['port']
    );
    // utf8mb4 để tiếng Việt không bị lỗi font
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $loi) {
    // Ghi chi tiết vào log, chỉ báo chung cho người dùng (không lộ thông tin máy chủ)
    error_log('Lỗi kết nối CSDL: ' . $loi->getMessage());
    http_response_code(500);
    exit('Không kết nối được cơ sở dữ liệu.');
}

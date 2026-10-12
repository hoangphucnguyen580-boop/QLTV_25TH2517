<?php
// Sao chép file này thành config.php rồi sửa nếu cần.
// config.php KHÔNG được đưa lên Git (đã có trong .gitignore).
return [
    // --- Kết nối CSDL (XAMPP mặc định: root, không mật khẩu, chỉ dùng khi học trên máy cá nhân) ---
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'root',
    'pass' => '',
    'db'   => 'qltv',

    // --- Quy định nghiệp vụ dùng chung ---
    'tien_phat_moi_ngay' => 3000,   // đồng / cuốn / ngày trễ hạn
    'he_so_boi_thuong_mat' => 2,    // sách mất: bồi thường gấp đôi gia_tien
];

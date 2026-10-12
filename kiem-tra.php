<?php
// Trang kiểm tra nhanh: kết nối được CSDL chưa, đã import đủ dữ liệu mẫu chưa.
// File tạm để kiểm tra môi trường. XÓA file này trước khi nộp bài.
require __DIR__ . '/db.php';

// Danh sách bảng cần đếm (tên cố định do mình đặt, không lấy từ người dùng nên nối chuỗi an toàn)
$danhSachBang = ['loaidocgia', 'theloai', 'nhaxuatban', 'nhanvien', 'docgia', 'sach', 'phieumuon', 'chitietphieumuon'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kiểm tra kết nối QLTV</title>
</head>
<body>
    <h1>Kiểm tra kết nối QLTV</h1>
    <p>Kết nối CSDL: <strong>OK</strong></p>
    <table border="1" cellpadding="6">
        <tr><th>Bảng</th><th>Số dòng</th></tr>
        <?php foreach ($danhSachBang as $tenBang): ?>
            <?php
            $ketQua = mysqli_query($conn, "SELECT COUNT(*) AS tong FROM $tenBang");
            $dong = mysqli_fetch_assoc($ketQua);
            ?>
            <tr>
                <td><?php echo htmlspecialchars($tenBang, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $dong['tong']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

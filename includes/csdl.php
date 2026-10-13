<?php
// Hàm truy vấn CSDL dùng chung.

// Chuẩn bị, gắn tham số và chạy một câu lệnh có dữ liệu người dùng (prepared statement).
//   $sql    : câu SQL, chỗ nào là giá trị thì để dấu ?
//   $kieu   : chuỗi kiểu của từng tham số theo thứ tự: s = chuỗi, i = số nguyên, d = số thực
//   $thamSo : mảng giá trị tương ứng, đúng thứ tự với các dấu ?
// Trả về mysqli_stmt đã chạy xong. Nơi gọi tự đọc kết quả rồi gọi mysqli_stmt_close().
// Ví dụ: truyVan($conn, 'SELECT * FROM sach WHERE id = ?', 'i', [$id]);
function truyVan(mysqli $conn, string $sql, string $kieu = '', array $thamSo = []): mysqli_stmt
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($kieu !== '') {
        // Dấu ... trải mảng $thamSo thành từng đối số riêng cho bind_param
        mysqli_stmt_bind_param($stmt, $kieu, ...$thamSo);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

// Đếm nhanh bằng câu SQL cố định, KHÔNG có dữ liệu người dùng (SELECT COUNT(*) ...).
function demDong(mysqli $conn, string $sql): int
{
    $ketQua = mysqli_query($conn, $sql);
    $hang   = mysqli_fetch_row($ketQua);
    return (int) $hang[0];
}

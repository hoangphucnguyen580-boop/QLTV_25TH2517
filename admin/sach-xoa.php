<?php
// Xóa sách: chỉ nhận POST có token CSRF. Sách đã từng được mượn thì KHÔNG xóa
// (xóa sẽ làm mất lịch sử mượn - trả, và khóa ngoại cũng chặn việc này).
// Xóa sách thành công thì xóa luôn file ảnh bìa của sách đó trong uploads/.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/anh-bia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sach.php');
    exit;
}
batBuocCsrf();

$idSach = filter_var(layChuoiPost('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idSach === false) {
    datThongBao('danger', 'Mã sách không hợp lệ.');
    header('Location: sach.php');
    exit;
}

// Tìm sách để lấy tên hiện trong thông báo, đường dẫn ảnh bìa và kiểm tra có tồn tại không
$stmt = truyVan($conn, 'SELECT ten_sach, hinh_bia FROM sach WHERE id = ?', 'i', [$idSach]);
$sach = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$sach) {
    datThongBao('danger', 'Cuốn sách này không còn tồn tại.');
    header('Location: sach.php');
    exit;
}

// Đếm số lượt mượn có liên quan (kể cả đã trả)
$stmt = truyVan($conn, 'SELECT COUNT(*) FROM chitietphieumuon WHERE id_sach = ?', 'i', [$idSach]);
$soLuotMuon = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
mysqli_stmt_close($stmt);

if ($soLuotMuon > 0) {
    datThongBao('warning', 'Không xóa được "' . $sach['ten_sach'] . '": sách đã xuất hiện trong ' . $soLuotMuon . ' lượt mượn.');
    header('Location: sach.php');
    exit;
}

try {
    $stmt = truyVan($conn, 'DELETE FROM sach WHERE id = ?', 'i', [$idSach]);
    mysqli_stmt_close($stmt);
    // Xóa dòng trong CSDL thành công rồi mới xóa file ảnh (xóa file trước mà CSDL lỗi thì mất ảnh oan)
    xoaAnhBia($sach['hinh_bia']);
    datThongBao('success', 'Đã xóa sách "' . $sach['ten_sach'] . '".');
} catch (mysqli_sql_exception $ngoaiLe) {
    if ($ngoaiLe->getCode() === 1451) {
        // 1451 = còn dòng khác trỏ tới sách này (phòng khi có người vừa lập phiếu giữa hai bước kiểm tra)
        datThongBao('warning', 'Không xóa được "' . $sach['ten_sach'] . '": sách đang được dùng ở nơi khác.');
    } else {
        throw $ngoaiLe;
    }
}
header('Location: sach.php');
exit;

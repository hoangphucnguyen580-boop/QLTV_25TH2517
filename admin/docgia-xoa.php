<?php
// Xóa độc giả: chỉ nhận POST có token CSRF. Độc giả đã từng có phiếu mượn thì KHÔNG xóa
// (xóa sẽ làm mất lịch sử mượn - trả, và khóa ngoại cũng chặn việc này). Muốn ngừng phục vụ thì khóa tài khoản.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: docgia.php');
    exit;
}
batBuocCsrf();

$idDocGia = filter_var(layChuoiPost('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idDocGia === false) {
    datThongBao('danger', 'Mã độc giả không hợp lệ.');
    header('Location: docgia.php');
    exit;
}

// Tìm độc giả để lấy tên hiện trong thông báo và kiểm tra có tồn tại không
$stmt   = truyVan($conn, 'SELECT ho_ten FROM docgia WHERE id = ?', 'i', [$idDocGia]);
$docGia = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$docGia) {
    datThongBao('danger', 'Độc giả này không còn tồn tại.');
    header('Location: docgia.php');
    exit;
}

// Đếm số phiếu mượn có liên quan (kể cả phiếu đã trả)
$stmt = truyVan($conn, 'SELECT COUNT(*) FROM phieumuon WHERE id_doc_gia = ?', 'i', [$idDocGia]);
$soPhieu = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
mysqli_stmt_close($stmt);

if ($soPhieu > 0) {
    datThongBao('warning', 'Không xóa được "' . $docGia['ho_ten'] . '": độc giả đã có ' . $soPhieu
        . ' phiếu mượn. Hãy khóa tài khoản thay vì xóa.');
    header('Location: docgia.php');
    exit;
}

try {
    $stmt = truyVan($conn, 'DELETE FROM docgia WHERE id = ?', 'i', [$idDocGia]);
    mysqli_stmt_close($stmt);
    datThongBao('success', 'Đã xóa độc giả "' . $docGia['ho_ten'] . '".');
} catch (mysqli_sql_exception $ngoaiLe) {
    if ($ngoaiLe->getCode() === 1451) {
        // 1451 = còn dòng khác trỏ tới độc giả này (phòng khi vừa có người lập phiếu giữa hai bước kiểm tra)
        datThongBao('warning', 'Không xóa được "' . $docGia['ho_ten'] . '": độc giả đang được dùng ở nơi khác.');
    } else {
        throw $ngoaiLe;
    }
}
header('Location: docgia.php');
exit;

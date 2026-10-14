<?php
// Khóa / mở khóa độc giả: chỉ nhận POST có token CSRF.
// Trang gửi lên trạng thái muốn đặt ('khoa' hoặc 'hoat_dong'), không "đảo ngược" trạng thái hiện tại,
// nên bấm hai lần liên tiếp vẫn cho cùng một kết quả.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: docgia.php');
    exit;
}
batBuocCsrf();

$idDocGia  = filter_var(layChuoiPost('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$trangThai = layChuoiPost('trang_thai');

if ($idDocGia === false || !in_array($trangThai, ['hoat_dong', 'khoa'], true)) {
    datThongBao('danger', 'Yêu cầu không hợp lệ.');
    header('Location: docgia.php');
    exit;
}

$stmt   = truyVan($conn, 'SELECT ho_ten FROM docgia WHERE id = ?', 'i', [$idDocGia]);
$docGia = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$docGia) {
    datThongBao('danger', 'Độc giả này không còn tồn tại.');
    header('Location: docgia.php');
    exit;
}

$stmt = truyVan($conn, 'UPDATE docgia SET trang_thai = ? WHERE id = ?', 'si', [$trangThai, $idDocGia]);
mysqli_stmt_close($stmt);

if ($trangThai === 'khoa') {
    datThongBao('success', 'Đã khóa độc giả "' . $docGia['ho_ten'] . '".');
} else {
    datThongBao('success', 'Đã mở khóa độc giả "' . $docGia['ho_ten'] . '".');
}
header('Location: docgia.php');
exit;

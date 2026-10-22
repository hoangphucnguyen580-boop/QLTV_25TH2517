<?php
// Gia hạn phiếu mượn: chỉ nhận POST có token CSRF.
// Các điều kiện gia hạn (chưa quá hạn, còn lượt, độc giả không bị khóa...) nằm trong hàm giaHanPhieu().
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/muon-tra.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: phieumuon.php');
    exit;
}
batBuocCsrf();

$idPhieu = filter_var(layChuoiPost('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idPhieu === false) {
    datThongBao('danger', 'Mã phiếu không hợp lệ.');
    header('Location: phieumuon.php');
    exit;
}

// Số lần đã gia hạn mà trang chi tiết đang hiển thị lúc thủ thư bấm nút (ô ẩn lan_da_dung trong form).
// Nếu số này không còn khớp với CSDL thì hàm giaHanPhieu() từ chối: bấm đúp hay mở hai tab
// cũng không thể gia hạn phiếu hai lần chỉ với một lần bấm.
$lanDaDung = filter_var(layChuoiPost('lan_da_dung'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($lanDaDung === false) {
    datThongBao('danger', 'Yêu cầu không hợp lệ. Hãy mở lại phiếu rồi bấm Gia hạn.');
    header('Location: phieumuon-chitiet.php?id=' . $idPhieu);
    exit;
}

$ketQua = giaHanPhieu($conn, $idPhieu, $lanDaDung);
if ($ketQua['thanh_cong']) {
    datThongBao('success', 'Đã gia hạn phiếu ' . $ketQua['ma_phieu'] . ' đến ' . ngayVN($ketQua['ngay_hen_tra'])
        . ' (lần ' . $ketQua['lan_da_dung'] . '/' . $ketQua['lan_toi_da'] . ').');
} else {
    datThongBao('danger', $ketQua['loi']);
}

// Không tìm thấy phiếu thì về danh sách, còn lại quay về đúng phiếu đó
$tonTai = layMotDong($conn, 'SELECT id FROM phieumuon WHERE id = ?', 'i', [$idPhieu]);
header('Location: ' . ($tonTai !== null ? 'phieumuon-chitiet.php?id=' . $idPhieu : 'phieumuon.php'));
exit;

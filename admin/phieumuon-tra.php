<?php
// Nhận trả sách: chỉ nhận POST có token CSRF.
//   hanh_dong = tra_mot : trả một cuốn (id_chi_tiet + tinh_trang)
//   hanh_dong = tra_het : trả hết các cuốn còn lại của phiếu (id_phieu), tất cả nguyên vẹn
// Việc tính phạt, bồi thường và cập nhật số lượng sách nằm trong hàm traSach() (includes/muon-tra.php).
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/muon-tra.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: phieumuon.php');
    exit;
}
batBuocCsrf();

$tienPhatMoiNgay = (int) ($cauHinh['tien_phat_moi_ngay'] ?? 3000);
$heSoMat         = (int) ($cauHinh['he_so_boi_thuong_mat'] ?? 2);

$hanhDong = layChuoiPost('hanh_dong');
$yeuCau   = [];   // danh sách [id chitietphieumuon, tình trang]
$idPhieu  = 0;    // phiếu để quay lại sau khi xử lý (0 = chưa biết)

if ($hanhDong === 'tra_mot') {
    $idChiTiet = filter_var(layChuoiPost('id_chi_tiet'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $tinhTrang = layChuoiPost('tinh_trang');
    if ($idChiTiet === false || !isset(TINH_TRANG_TRA[$tinhTrang])) {
        datThongBao('danger', 'Yêu cầu không hợp lệ.');
        header('Location: phieumuon.php');
        exit;
    }
    $yeuCau = [[$idChiTiet, $tinhTrang]];
} elseif ($hanhDong === 'tra_het') {
    $idPhieu = filter_var(layChuoiPost('id_phieu'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idPhieu === false) {
        datThongBao('danger', 'Mã phiếu không hợp lệ.');
        header('Location: phieumuon.php');
        exit;
    }
    $stmt = truyVan(
        $conn,
        'SELECT id FROM chitietphieumuon WHERE id_phieu_muon = ? AND ngay_tra_thuc_te IS NULL ORDER BY id',
        'i',
        [$idPhieu]
    );
    foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC) as $dong) {
        $yeuCau[] = [(int) $dong['id'], 'nguyen_ven'];
    }
    mysqli_stmt_close($stmt);

    if (count($yeuCau) === 0) {
        datThongBao('warning', 'Phiếu này không còn cuốn nào chưa trả.');
        header('Location: phieumuon-chitiet.php?id=' . $idPhieu);
        exit;
    }
} else {
    datThongBao('danger', 'Yêu cầu không hợp lệ.');
    header('Location: phieumuon.php');
    exit;
}

$ketQua = traSach($conn, $yeuCau, $tienPhatMoiNgay, $heSoMat);
if ($ketQua['id_phieu'] > 0) {
    $idPhieu = $ketQua['id_phieu'];
}

if ($ketQua['thanh_cong']) {
    $coTien = false;
    foreach ($ketQua['ket_qua'] as $mot) {
        if ($mot['tien_phat'] > 0 || $mot['tien_boi_thuong'] > 0) {
            $coTien = true;
        }
    }
    // Có thu tiền thì dùng màu vàng để thủ thư không bỏ sót
    datThongBao($coTien ? 'warning' : 'success', taoThongBaoTra($ketQua['ket_qua']));
} else {
    datThongBao('danger', $ketQua['loi']);
}

header('Location: ' . ($idPhieu > 0 ? 'phieumuon-chitiet.php?id=' . $idPhieu : 'phieumuon.php'));
exit;

<?php
// Nghiệp vụ mượn - trả - gia hạn sách.
//
// Mọi thao tác ghi dữ liệu nhiều bảng đều chạy trong MỘT transaction: hoặc xong hết, hoặc hoàn tác hết.
// Nhờ vậy không bao giờ có phiếu thiếu sách, hay số lượng sách bị lệch so với phiếu mượn.
//
// Quy ước chung của các hàm nghiệp vụ: KHÔNG in gì ra màn hình và KHÔNG chuyển trang.
// Chúng trả về một mảng kết quả, trang gọi tự quyết định hiện thông báo gì.
require_once __DIR__ . '/csdl.php';

// Các tình trạng khi trả sách: mã lưu trong CSDL => nhãn hiển thị
const TINH_TRANG_TRA = [
    'nguyen_ven' => 'Nguyên vẹn',
    'hu_hong'    => 'Hư hỏng',
    'mat'        => 'Mất',
];

// ---------------------------------------------------------------------
// Hàm nhỏ dùng chung
// ---------------------------------------------------------------------

// Chạy một câu SELECT và lấy đúng một dòng (hoặc null nếu không có dòng nào)
function layMotDong(mysqli $conn, string $sql, string $kieu = '', array $thamSo = []): ?array
{
    $stmt = truyVan($conn, $sql, $kieu, $thamSo);
    $dong = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $dong ?: null;
}

// Chạy một câu INSERT / UPDATE / DELETE, trả về số dòng bị ảnh hưởng
function chayLenhGhi(mysqli $conn, string $sql, string $kieu = '', array $thamSo = []): int
{
    $stmt   = truyVan($conn, $sql, $kieu, $thamSo);
    $soDong = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $soDong;
}

// Hai transaction khóa chéo nhau thì MySQL hủy một bên (mã lỗi 1213, gọi là deadlock).
// Đây là chuyện bình thường khi nhiều người thao tác cùng lúc: hoàn tác rồi làm lại là đúng cách xử lý.
function chayGiaoDichCoThuLai(callable $viec, int $soLanToiDa = 3)
{
    for ($lan = 1; ; $lan++) {
        try {
            return $viec();
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() !== 1213 || $lan >= $soLanToiDa) {
                throw $ngoaiLe;
            }
        }
    }
}

// Định dạng tiền: 150000 -> "150.000đ"
function tienVND($so): string
{
    return number_format((float) $so, 0, ',', '.') . 'đ';
}

// Định dạng ngày: "2026-10-16" -> "16/10/2026"
function ngayVN(?string $ngay): string
{
    if ($ngay === null || $ngay === '') {
        return '';
    }
    return date('d/m/Y', strtotime($ngay));
}

// Nhãn và màu badge của phiếu mượn, TÍNH THEO NGÀY chứ không đọc cột phieumuon.trang_thai
// (cột đó chỉ là giá trị lưu sẵn, sẽ cũ đi khi thời gian trôi qua).
//   $soCuonChuaTra : số cuốn của phiếu chưa trả
//   $soNgayTre     : DATEDIFF(CURDATE(), ngay_hen_tra), dương nghĩa là đã quá hạn
function nhanTrangThaiPhieu(int $soCuonChuaTra, int $soNgayTre): array
{
    if ($soCuonChuaTra === 0) {
        return ['Đã trả', 'success'];
    }
    if ($soNgayTre > 0) {
        return ['Quá hạn ' . $soNgayTre . ' ngày', 'danger'];
    }
    return ['Đang mượn', 'primary'];
}

// ---------------------------------------------------------------------
// Tính tiền
// ---------------------------------------------------------------------

// Phạt trả trễ: số ngày trễ x tiền phạt mỗi ngày, tính cho TỪNG cuốn
function tinhTienPhat(int $soNgayTre, int $tienMoiNgay): int
{
    return max(0, $soNgayTre) * $tienMoiNgay;
}

// Bồi thường: nguyên vẹn không đền, hư hỏng đền 1 lần giá sách, mất đền gấp đôi (hệ số lấy từ config.php)
function tinhTienBoiThuong(string $tinhTrang, int $giaTien, int $heSoMat): int
{
    if ($tinhTrang === 'hu_hong') {
        return $giaTien;
    }
    if ($tinhTrang === 'mat') {
        return $giaTien * $heSoMat;
    }
    return 0;
}

// ---------------------------------------------------------------------
// Mã phiếu: PM0001, PM0002, ...
// ---------------------------------------------------------------------

function dinhDangMaPhieu(int $so): string
{
    return 'PM' . str_pad((string) $so, 4, '0', STR_PAD_LEFT);
}

// Số thứ tự lớn nhất đang có + 1 (đọc từ chính mã phiếu, không dựa vào cột id vì hai số này không trùng nhau)
function laySoPhieuTiepTheo(mysqli $conn): int
{
    $dong = layMotDong(
        $conn,
        "SELECT IFNULL(MAX(CAST(SUBSTRING(ma_phieu, 3) AS UNSIGNED)), 0) AS lon_nhat
         FROM phieumuon
         WHERE ma_phieu REGEXP '^PM[0-9]+\$'"
    );
    return (int) $dong['lon_nhat'] + 1;
}

// Cập nhật cột phieumuon.trang_thai theo tình hình hiện tại của các cuốn trong phiếu
function capNhatTrangThaiPhieu(mysqli $conn, int $idPhieu): void
{
    chayLenhGhi(
        $conn,
        "UPDATE phieumuon
         SET trang_thai = CASE
             WHEN NOT EXISTS (SELECT 1 FROM chitietphieumuon ct
                              WHERE ct.id_phieu_muon = phieumuon.id AND ct.ngay_tra_thuc_te IS NULL) THEN 'da_tra'
             WHEN ngay_hen_tra < CURDATE() THEN 'qua_han'
             ELSE 'dang_muon'
         END
         WHERE id = ?",
        'i',
        [$idPhieu]
    );
}

// ---------------------------------------------------------------------
// 1. LẬP PHIẾU MƯỢN
// ---------------------------------------------------------------------
//   $idNhanVien : thủ thư đang lập phiếu
//   $maDocGia   : mã độc giả (đã trim)
//   $dsMaSach   : danh sách mã sách muốn mượn, KHÔNG có phần tử rỗng. Khóa mảng là số thứ tự ô nhập,
//                 để báo lỗi đúng ô.
// Trả về:
//   thanh_cong, id_phieu, ma_phieu, ngay_hen_tra  (khi thành công)
//   loi_doc_gia  : lỗi gắn với ô mã độc giả
//   loi_chung    : lỗi chung (quá hạn, vượt số cuốn tối đa)
//   loi_sach     : [số thứ tự ô => lỗi] cho từng mã sách
function taoPhieuMuon(mysqli $conn, int $idNhanVien, string $maDocGia, array $dsMaSach): array
{
    return chayGiaoDichCoThuLai(function () use ($conn, $idNhanVien, $maDocGia, $dsMaSach) {
        return thucHienTaoPhieuMuon($conn, $idNhanVien, $maDocGia, $dsMaSach);
    });
}

function thucHienTaoPhieuMuon(mysqli $conn, int $idNhanVien, string $maDocGia, array $dsMaSach): array
{
    $kq = [
        'thanh_cong'   => false,
        'id_phieu'     => 0,
        'ma_phieu'     => '',
        'ngay_hen_tra' => '',
        'loi_doc_gia'  => '',
        'loi_chung'    => '',
        'loi_sach'     => [],
    ];

    mysqli_begin_transaction($conn);
    try {
        // Khóa dòng độc giả TRƯỚC TIÊN: hai yêu cầu lập phiếu cùng lúc cho một người sẽ xếp hàng,
        // yêu cầu sau phải chờ yêu cầu trước xong rồi mới kiểm tra, nên không lọt qua giới hạn số cuốn.
        $docGia = layMotDong(
            $conn,
            'SELECT id, ho_ten, trang_thai, id_loai FROM docgia WHERE ma_doc_gia = ? FOR UPDATE',
            's',
            [$maDocGia]
        );

        $idDocGia   = 0;
        $soNgayMuon = 0;
        $cacSach    = [];   // [số thứ tự ô => dòng sách] chỉ gồm các sách đã qua mọi kiểm tra

        if ($docGia === null) {
            $kq['loi_doc_gia'] = 'Không tìm thấy độc giả có mã "' . $maDocGia . '".';
        } elseif ($docGia['trang_thai'] !== 'hoat_dong') {
            $kq['loi_doc_gia'] = 'Độc giả "' . $docGia['ho_ten'] . '" đang bị khóa, không được mượn sách.';
        } else {
            $idDocGia = (int) $docGia['id'];
            $loai     = layMotDong(
                $conn,
                'SELECT ten_loai, so_sach_toi_da, so_ngay_muon FROM loaidocgia WHERE id = ?',
                'i',
                [(int) $docGia['id_loai']]
            );
            $soNgayMuon = (int) $loai['so_ngay_muon'];

            // Số cuốn đang giữ, và trong đó có cuốn nào đã quá hạn không
            $dangGiu = layMotDong(
                $conn,
                'SELECT COUNT(*) AS so_cuon, IFNULL(SUM(pm.ngay_hen_tra < CURDATE()), 0) AS so_qua_han
                 FROM chitietphieumuon ct
                 JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
                 WHERE pm.id_doc_gia = ? AND ct.ngay_tra_thuc_te IS NULL',
                'i',
                [$idDocGia]
            );
            $soDangGiu = (int) $dangGiu['so_cuon'];
            $soQuaHan  = (int) $dangGiu['so_qua_han'];
            $soMuonThem = count($dsMaSach);

            if ($soQuaHan > 0) {
                $kq['loi_chung'] = 'Độc giả "' . $docGia['ho_ten'] . '" đang giữ ' . $soQuaHan
                    . ' cuốn đã quá hạn, phải trả sách trước khi mượn thêm.';
            } elseif ($soDangGiu + $soMuonThem > (int) $loai['so_sach_toi_da']) {
                $kq['loi_chung'] = $loai['ten_loai'] . ' chỉ được mượn tối đa ' . (int) $loai['so_sach_toi_da']
                    . ' cuốn cùng lúc: đang giữ ' . $soDangGiu . ', muốn mượn thêm ' . $soMuonThem . '.';
            }

            // Kiểm tra từng mã sách
            $daThay = [];   // id sách đã gặp trong phiếu này, để phát hiện nhập trùng
            foreach ($dsMaSach as $chiSo => $maSach) {
                $sach = layMotDong(
                    $conn,
                    'SELECT id, ma_sach, ten_sach, so_con_lai FROM sach WHERE ma_sach = ?',
                    's',
                    [$maSach]
                );
                if ($sach === null) {
                    $kq['loi_sach'][$chiSo] = 'Không có sách nào mã "' . $maSach . '".';
                    continue;
                }
                $idSach = (int) $sach['id'];
                if (isset($daThay[$idSach])) {
                    $kq['loi_sach'][$chiSo] = 'Sách "' . $sach['ten_sach'] . '" bị nhập hai lần trong cùng một phiếu.';
                    continue;
                }
                $daThay[$idSach] = true;

                if ((int) $sach['so_con_lai'] < 1) {
                    $kq['loi_sach'][$chiSo] = 'Sách "' . $sach['ten_sach'] . '" đã hết, không còn bản nào trên kệ.';
                    continue;
                }

                // Đã đang giữ chính đầu sách này (ở phiếu khác) thì không cho mượn thêm bản thứ hai
                $dangGiuSachNay = layMotDong(
                    $conn,
                    'SELECT COUNT(*) AS so
                     FROM chitietphieumuon ct
                     JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
                     WHERE pm.id_doc_gia = ? AND ct.id_sach = ? AND ct.ngay_tra_thuc_te IS NULL',
                    'ii',
                    [$idDocGia, $idSach]
                );
                if ((int) $dangGiuSachNay['so'] > 0) {
                    $kq['loi_sach'][$chiSo] = 'Độc giả đang giữ cuốn "' . $sach['ten_sach'] . '" rồi, không mượn thêm bản thứ hai.';
                    continue;
                }
                $cacSach[$chiSo] = $sach;
            }
        }

        $coLoi = ($kq['loi_doc_gia'] !== '' || $kq['loi_chung'] !== '' || count($kq['loi_sach']) > 0);
        if ($coLoi) {
            mysqli_rollback($conn);
            return $kq;
        }

        // Trừ số bản còn lại. Câu UPDATE có điều kiện "so_con_lai > 0" nên tự nó chặn việc bán lố:
        // nếu giữa lúc kiểm tra và lúc trừ có người khác mượn mất bản cuối cùng, câu này không đổi dòng nào.
        // Luôn trừ theo thứ tự id sách tăng dần để hai phiếu cùng mượn nhiều cuốn không khóa chéo nhau.
        $chiSoTheoIdSach = [];
        foreach ($cacSach as $chiSo => $sach) {
            $chiSoTheoIdSach[(int) $sach['id']] = $chiSo;
        }
        ksort($chiSoTheoIdSach);
        foreach ($chiSoTheoIdSach as $idSach => $chiSo) {
            $soDong = chayLenhGhi(
                $conn,
                'UPDATE sach SET so_con_lai = so_con_lai - 1 WHERE id = ? AND so_con_lai > 0',
                'i',
                [$idSach]
            );
            if ($soDong === 0) {
                $kq['loi_sach'][$chiSo] = 'Sách "' . $cacSach[$chiSo]['ten_sach'] . '" vừa hết bản trên kệ (có người khác vừa mượn).';
            }
        }
        if (count($kq['loi_sach']) > 0) {
            mysqli_rollback($conn);   // hoàn tác luôn các lần trừ đã làm trước đó
            return $kq;
        }

        // Tạo phiếu. Mã phiếu có thể trùng nếu hai người lập phiếu đúng lúc: gặp lỗi 1062 (trùng) thì thử số kế tiếp.
        $soPhieu = laySoPhieuTiepTheo($conn);
        $idPhieu = 0;
        $maPhieu = '';
        for ($lan = 0; $lan < 10 && $idPhieu === 0; $lan++) {
            $maThu = dinhDangMaPhieu($soPhieu + $lan);
            try {
                chayLenhGhi(
                    $conn,
                    "INSERT INTO phieumuon (ma_phieu, id_doc_gia, id_nhan_vien, ngay_muon, ngay_hen_tra,
                                            so_lan_gia_han_da_dung, trang_thai)
                     VALUES (?, ?, ?, CURDATE(), CURDATE() + INTERVAL ? DAY, 0, 'dang_muon')",
                    'siii',
                    [$maThu, $idDocGia, $idNhanVien, $soNgayMuon]
                );
                $idPhieu = (int) mysqli_insert_id($conn);
                $maPhieu = $maThu;
            } catch (mysqli_sql_exception $ngoaiLe) {
                if ($ngoaiLe->getCode() !== 1062) {
                    throw $ngoaiLe;
                }
            }
        }
        if ($idPhieu === 0) {
            throw new RuntimeException('Không sinh được mã phiếu mượn sau nhiều lần thử.');
        }

        foreach ($cacSach as $sach) {
            chayLenhGhi(
                $conn,
                'INSERT INTO chitietphieumuon (id_phieu_muon, id_sach) VALUES (?, ?)',
                'ii',
                [$idPhieu, (int) $sach['id']]
            );
        }

        $phieu = layMotDong($conn, 'SELECT ngay_hen_tra FROM phieumuon WHERE id = ?', 'i', [$idPhieu]);
        mysqli_commit($conn);

        $kq['thanh_cong']   = true;
        $kq['id_phieu']     = $idPhieu;
        $kq['ma_phieu']     = $maPhieu;
        $kq['ngay_hen_tra'] = $phieu['ngay_hen_tra'];
        return $kq;
    } catch (Throwable $loi) {
        mysqli_rollback($conn);
        throw $loi;
    }
}

// ---------------------------------------------------------------------
// 2. TRẢ SÁCH
// ---------------------------------------------------------------------
//   $yeuCau : danh sách các cuốn cần trả, mỗi phần tử là [id chitietphieumuon, tình trạng]
//             (tình trạng: nguyen_ven | hu_hong | mat)
// Trả về: thanh_cong, loi, id_phieu (phiếu của cuốn cuối cùng được xử lý), ket_qua (từng cuốn đã trả:
//         ten_sach, tinh_trang, so_ngay_tre, tien_phat, tien_boi_thuong)
//
// Quy tắc:
//   - Trễ hạn: phạt (số ngày trễ x tiền mỗi ngày) cho từng cuốn, kể cả cuốn hỏng hay mất.
//   - Nguyên vẹn hoặc hư hỏng: sách về lại kệ (so_con_lai + 1). Hư hỏng thì độc giả đền 1 lần giá sách.
//   - Mất: độc giả đền gấp đôi giá sách, và tổng số bản của sách giảm 1 (bản đó không còn nữa).
function traSach(mysqli $conn, array $yeuCau, int $tienMoiNgay, int $heSoMat): array
{
    return chayGiaoDichCoThuLai(function () use ($conn, $yeuCau, $tienMoiNgay, $heSoMat) {
        return thucHienTraSach($conn, $yeuCau, $tienMoiNgay, $heSoMat);
    });
}

function thucHienTraSach(mysqli $conn, array $yeuCau, int $tienMoiNgay, int $heSoMat): array
{
    $kq = ['thanh_cong' => false, 'loi' => '', 'id_phieu' => 0, 'ket_qua' => []];

    if (count($yeuCau) === 0) {
        $kq['loi'] = 'Không có cuốn nào để trả.';
        return $kq;
    }
    foreach ($yeuCau as $mucYeuCau) {
        if (!isset(TINH_TRANG_TRA[$mucYeuCau[1]])) {
            $kq['loi'] = 'Tình trạng khi trả không hợp lệ.';
            return $kq;
        }
    }

    // Xử lý theo thứ tự id tăng dần để hai yêu cầu trả cùng lúc không khóa chéo nhau
    usort($yeuCau, function (array $a, array $b): int {
        return $a[0] <=> $b[0];
    });

    mysqli_begin_transaction($conn);
    try {
        $cacPhieu = [];   // các phiếu bị đụng tới, để cập nhật trạng thái ở cuối

        foreach ($yeuCau as [$idChiTiet, $tinhTrang]) {
            // Khóa dòng chi tiết trước: hai yêu cầu trả cùng một cuốn sẽ xếp hàng,
            // người đến sau thấy cuốn đã được trả và bị từ chối, nên sách không bị cộng lại hai lần.
            $ct = layMotDong(
                $conn,
                'SELECT id, id_phieu_muon, id_sach, ngay_tra_thuc_te FROM chitietphieumuon WHERE id = ? FOR UPDATE',
                'i',
                [$idChiTiet]
            );
            if ($ct === null) {
                $kq['loi'] = 'Không tìm thấy cuốn sách cần trả.';
                break;
            }
            $kq['id_phieu'] = (int) $ct['id_phieu_muon'];

            $thongTin = layMotDong(
                $conn,
                'SELECT s.ten_sach, s.gia_tien, DATEDIFF(CURDATE(), pm.ngay_hen_tra) AS so_ngay_tre
                 FROM chitietphieumuon ct
                 JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
                 JOIN sach s       ON s.id = ct.id_sach
                 WHERE ct.id = ?',
                'i',
                [$idChiTiet]
            );

            if ($ct['ngay_tra_thuc_te'] !== null) {
                $kq['loi'] = 'Cuốn "' . $thongTin['ten_sach'] . '" đã được trả rồi.';
                break;
            }

            $soNgayTre     = max(0, (int) $thongTin['so_ngay_tre']);
            $tienPhat      = tinhTienPhat($soNgayTre, $tienMoiNgay);
            $tienBoiThuong = tinhTienBoiThuong($tinhTrang, (int) $thongTin['gia_tien'], $heSoMat);

            chayLenhGhi(
                $conn,
                'UPDATE chitietphieumuon
                 SET ngay_tra_thuc_te = CURDATE(), tinh_trang_tra = ?, tien_phat = ?, tien_boi_thuong = ?
                 WHERE id = ?',
                'siii',
                [$tinhTrang, $tienPhat, $tienBoiThuong, $idChiTiet]
            );

            if ($tinhTrang === 'mat') {
                // Bản đã mất: bỏ khỏi tổng số bản. so_con_lai giữ nguyên vì lúc mượn đã trừ rồi.
                chayLenhGhi($conn, 'UPDATE sach SET so_luong = so_luong - 1 WHERE id = ?', 'i', [(int) $ct['id_sach']]);
            } else {
                chayLenhGhi($conn, 'UPDATE sach SET so_con_lai = so_con_lai + 1 WHERE id = ?', 'i', [(int) $ct['id_sach']]);
            }

            $cacPhieu[(int) $ct['id_phieu_muon']] = true;
            $kq['ket_qua'][] = [
                'ten_sach'        => $thongTin['ten_sach'],
                'tinh_trang'      => $tinhTrang,
                'so_ngay_tre'     => $soNgayTre,
                'tien_phat'       => $tienPhat,
                'tien_boi_thuong' => $tienBoiThuong,
            ];
        }

        if ($kq['loi'] !== '') {
            mysqli_rollback($conn);   // có một cuốn lỗi thì hoàn tác cả lượt, không trả dở dang
            $kq['ket_qua'] = [];
            return $kq;
        }

        foreach (array_keys($cacPhieu) as $idPhieu) {
            capNhatTrangThaiPhieu($conn, $idPhieu);
        }
        mysqli_commit($conn);
        $kq['thanh_cong'] = true;
        return $kq;
    } catch (Throwable $loi) {
        mysqli_rollback($conn);
        throw $loi;
    }
}

// Câu thông báo sau khi trả: tên sách, tiền phạt, tiền bồi thường
function taoThongBaoTra(array $dsKetQua): string
{
    $tongPhat = 0;
    $tongDen  = 0;
    foreach ($dsKetQua as $mot) {
        $tongPhat += $mot['tien_phat'];
        $tongDen  += $mot['tien_boi_thuong'];
    }

    if (count($dsKetQua) === 1) {
        $cauChu = 'Đã nhận trả "' . $dsKetQua[0]['ten_sach'] . '" (' . TINH_TRANG_TRA[$dsKetQua[0]['tinh_trang']] . ').';
    } else {
        $cauChu = 'Đã nhận trả ' . count($dsKetQua) . ' cuốn.';
    }
    if ($tongPhat > 0) {
        $cauChu .= ' Phạt trả trễ: ' . tienVND($tongPhat) . '.';
    }
    if ($tongDen > 0) {
        $cauChu .= ' Tiền bồi thường: ' . tienVND($tongDen) . '.';
    }
    return $cauChu;
}

// ---------------------------------------------------------------------
// 3. GIA HẠN
// ---------------------------------------------------------------------
// Gia hạn cả phiếu: ngày hẹn trả cộng thêm đúng số ngày mượn của loại độc giả (sinh viên +7, giảng viên +15).
// Chỉ gia hạn khi: phiếu còn sách chưa trả, độc giả không bị khóa, phiếu CHƯA quá hạn, còn lượt gia hạn.
// Trả về: thanh_cong, loi, ma_phieu, ngay_hen_tra (mới), lan_da_dung, lan_toi_da
function giaHanPhieu(mysqli $conn, int $idPhieu): array
{
    return chayGiaoDichCoThuLai(function () use ($conn, $idPhieu) {
        return thucHienGiaHanPhieu($conn, $idPhieu);
    });
}

function thucHienGiaHanPhieu(mysqli $conn, int $idPhieu): array
{
    $kq = ['thanh_cong' => false, 'loi' => '', 'ma_phieu' => '', 'ngay_hen_tra' => '', 'lan_da_dung' => 0, 'lan_toi_da' => 0];

    mysqli_begin_transaction($conn);
    try {
        // Khóa dòng phiếu: bấm "Gia hạn" hai lần liên tiếp thì lần hai phải chờ rồi thấy số lượt đã đổi
        $phieu = layMotDong(
            $conn,
            'SELECT id, ma_phieu, id_doc_gia, so_lan_gia_han_da_dung FROM phieumuon WHERE id = ? FOR UPDATE',
            'i',
            [$idPhieu]
        );
        if ($phieu === null) {
            mysqli_rollback($conn);
            $kq['loi'] = 'Không tìm thấy phiếu mượn.';
            return $kq;
        }
        $kq['ma_phieu'] = $phieu['ma_phieu'];

        $thongTin = layMotDong(
            $conn,
            'SELECT dg.ho_ten, dg.trang_thai, ld.so_lan_gia_han, ld.so_ngay_muon, ld.ten_loai,
                    DATEDIFF(CURDATE(), pm.ngay_hen_tra) AS so_ngay_tre,
                    (SELECT COUNT(*) FROM chitietphieumuon ct
                     WHERE ct.id_phieu_muon = pm.id AND ct.ngay_tra_thuc_te IS NULL) AS chua_tra
             FROM phieumuon pm
             JOIN docgia dg      ON dg.id = pm.id_doc_gia
             JOIN loaidocgia ld  ON ld.id = dg.id_loai
             WHERE pm.id = ?',
            'i',
            [$idPhieu]
        );
        $lanDaDung = (int) $phieu['so_lan_gia_han_da_dung'];
        $lanToiDa  = (int) $thongTin['so_lan_gia_han'];
        $kq['lan_toi_da'] = $lanToiDa;

        if ((int) $thongTin['chua_tra'] === 0) {
            $kq['loi'] = 'Phiếu ' . $phieu['ma_phieu'] . ' đã trả hết sách, không thể gia hạn.';
        } elseif ($thongTin['trang_thai'] !== 'hoat_dong') {
            $kq['loi'] = 'Độc giả "' . $thongTin['ho_ten'] . '" đang bị khóa, không được gia hạn.';
        } elseif ((int) $thongTin['so_ngay_tre'] > 0) {
            $kq['loi'] = 'Phiếu ' . $phieu['ma_phieu'] . ' đã quá hạn ' . (int) $thongTin['so_ngay_tre']
                . ' ngày, không thể gia hạn. Hãy nhận trả sách.';
        } elseif ($lanToiDa === 0) {
            $kq['loi'] = $thongTin['ten_loai'] . ' không được gia hạn.';
        } elseif ($lanDaDung >= $lanToiDa) {
            $kq['loi'] = 'Phiếu ' . $phieu['ma_phieu'] . ' đã dùng hết lượt gia hạn (' . $lanDaDung . '/' . $lanToiDa . ').';
        }

        if ($kq['loi'] !== '') {
            mysqli_rollback($conn);
            return $kq;
        }

        chayLenhGhi(
            $conn,
            "UPDATE phieumuon
             SET ngay_hen_tra = ngay_hen_tra + INTERVAL ? DAY,
                 so_lan_gia_han_da_dung = so_lan_gia_han_da_dung + 1,
                 trang_thai = 'dang_muon'
             WHERE id = ?",
            'ii',
            [(int) $thongTin['so_ngay_muon'], $idPhieu]
        );
        $moi = layMotDong($conn, 'SELECT ngay_hen_tra FROM phieumuon WHERE id = ?', 'i', [$idPhieu]);
        mysqli_commit($conn);

        $kq['thanh_cong']   = true;
        $kq['ngay_hen_tra'] = $moi['ngay_hen_tra'];
        $kq['lan_da_dung']  = $lanDaDung + 1;
        return $kq;
    } catch (Throwable $loi) {
        mysqli_rollback($conn);
        throw $loi;
    }
}

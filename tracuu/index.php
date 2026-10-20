<?php
// Trang tra cứu của độc giả: sách đang mượn và lịch sử mượn - trả (kèm tiền phạt, tiền đền).
// Trang chỉ ĐỌC dữ liệu của CHÍNH độc giả đang đăng nhập: id luôn lấy từ session (qua $docGia),
// tuyệt đối không nhận id nào từ URL hay form, nên không ai xem được dữ liệu của người khác.
require_once __DIR__ . '/../includes/khoi-tao-tracuu.php';

$soDongMoiTrang  = 10;
$tienPhatMoiNgay = (int) ($cauHinh['tien_phat_moi_ngay'] ?? 3000);
$idDocGia        = (int) $docGia['id'];

// Nhãn và màu badge cho hạn trả của một cuốn đang mượn.
// $soNgayCon = DATEDIFF(ngay_hen_tra, CURDATE()): dương là còn hạn, 0 là hạn đúng hôm nay, âm là đã trễ.
function nhanHanTra(int $soNgayCon): array
{
    if ($soNgayCon < 0) {
        return ['Trễ ' . abs($soNgayCon) . ' ngày', 'danger'];
    }
    if ($soNgayCon === 0) {
        return ['Hạn trả hôm nay', 'warning'];
    }
    if ($soNgayCon <= 2) {
        return ['Còn ' . $soNgayCon . ' ngày', 'warning'];
    }
    return ['Còn ' . $soNgayCon . ' ngày', 'success'];
}

// ---------------------------------------------------------------------
// 1. Sách đang mượn (chưa trả), cuốn nào hết hạn sớm nhất lên đầu
// ---------------------------------------------------------------------
$stmt = truyVan(
    $conn,
    'SELECT pm.ma_phieu, pm.ngay_muon, pm.ngay_hen_tra, pm.so_lan_gia_han_da_dung,
            DATEDIFF(pm.ngay_hen_tra, CURDATE()) AS so_ngay_con,
            s.ma_sach, s.ten_sach, s.tac_gia
     FROM chitietphieumuon ct
     JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
     JOIN sach s       ON s.id = ct.id_sach
     WHERE pm.id_doc_gia = ? AND ct.ngay_tra_thuc_te IS NULL
     ORDER BY pm.ngay_hen_tra, pm.id, ct.id',
    'i',
    [$idDocGia]
);
$dsDangMuon = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Tiền phạt dự kiến của các cuốn đang trễ (dùng đúng hàm lúc trả sách để hai con số luôn khớp)
$soCuonTre      = 0;
$tongPhatDuKien = 0;
foreach ($dsDangMuon as $chiSo => $cuon) {
    $phatDuKien = 0;
    if ((int) $cuon['so_ngay_con'] < 0) {
        $soCuonTre++;
        $phatDuKien = tinhTienPhat(abs((int) $cuon['so_ngay_con']), $tienPhatMoiNgay);
    }
    $dsDangMuon[$chiSo]['phat_du_kien'] = $phatDuKien;
    $tongPhatDuKien += $phatDuKien;
}

// ---------------------------------------------------------------------
// 2. Lịch sử mượn - trả (các cuốn ĐÃ trả), phân trang, mới nhất lên đầu
// ---------------------------------------------------------------------
$pageNum = filter_var(layChuoiGet('page'), FILTER_VALIDATE_INT);
$pageNum = ($pageNum !== false && $pageNum > 0) ? $pageNum : 1;

// Tổng số dòng và tổng tiền của TOÀN BỘ lịch sử (không chỉ trang đang xem)
$stmt = truyVan(
    $conn,
    'SELECT COUNT(*) AS so_dong, IFNULL(SUM(ct.tien_phat), 0) AS tong_phat, IFNULL(SUM(ct.tien_boi_thuong), 0) AS tong_den
     FROM chitietphieumuon ct
     JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
     WHERE pm.id_doc_gia = ? AND ct.ngay_tra_thuc_te IS NOT NULL',
    'i',
    [$idDocGia]
);
$tong = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$numRows       = (int) $tong['so_dong'];
$tongPhatLichSu = (int) $tong['tong_phat'];
$tongDenLichSu  = (int) $tong['tong_den'];

$maxPage = max(1, (int) ceil($numRows / $soDongMoiTrang));
$pageNum = min($pageNum, $maxPage);
$offset  = ($pageNum - 1) * $soDongMoiTrang;

$stmt = truyVan(
    $conn,
    'SELECT pm.ma_phieu, pm.ngay_muon, pm.ngay_hen_tra,
            ct.ngay_tra_thuc_te, ct.tinh_trang_tra, ct.tien_phat, ct.tien_boi_thuong,
            GREATEST(0, DATEDIFF(ct.ngay_tra_thuc_te, pm.ngay_hen_tra)) AS so_ngay_tre,
            s.ma_sach, s.ten_sach
     FROM chitietphieumuon ct
     JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
     JOIN sach s       ON s.id = ct.id_sach
     WHERE pm.id_doc_gia = ? AND ct.ngay_tra_thuc_te IS NOT NULL
     ORDER BY ct.ngay_tra_thuc_te DESC, ct.id DESC
     LIMIT ? OFFSET ?',
    'iii',
    [$idDocGia, $soDongMoiTrang, $offset]
);
$dsLichSu = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Liên kết tới một trang của lịch sử (kèm #lich-su để trình duyệt cuộn thẳng xuống bảng)
function lienKetTrangLichSu(int $page): string
{
    return '?' . e(http_build_query(['page' => $page])) . '#lich-su';
}

$tieuDe = 'Tra cứu của tôi';
require __DIR__ . '/../includes/tracuu-dau.php';
?>
<h1 class="h3 mb-1">Xin chào, <?= e($docGia['ho_ten']) ?></h1>
<p class="text-muted mb-4">
    <?= e($docGia['ma_doc_gia']) ?> &middot; <?= e($docGia['ten_loai']) ?>
    &middot; đang giữ <strong><?= count($dsDangMuon) ?></strong> / <?= (int) $docGia['so_sach_toi_da'] ?> cuốn
    &middot; mỗi lượt mượn <?= (int) $docGia['so_ngay_muon'] ?> ngày
</p>

<?php if ($soCuonTre > 0): ?>
    <div class="alert alert-danger" role="alert">
        Bạn đang giữ <strong><?= $soCuonTre ?></strong> cuốn đã quá hạn, phạt dự kiến
        <strong><?= e(tienVND($tongPhatDuKien)) ?></strong>. Hãy mang sách trả cho thủ thư sớm.
        Khi còn sách quá hạn bạn chưa mượn thêm được.
    </div>
<?php endif; ?>

<!-- ============ 1. Sách đang mượn ============ -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Sách đang mượn</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sách</th>
                    <th>Phiếu</th>
                    <th>Ngày mượn</th>
                    <th>Hạn trả</th>
                    <th>Tình trạng</th>
                    <th class="text-end">Phạt dự kiến</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsDangMuon as $cuon): ?>
                <?php [$nhan, $mau] = nhanHanTra((int) $cuon['so_ngay_con']); ?>
                <tr>
                    <td>
                        <?= e($cuon['ten_sach']) ?>
                        <div class="small text-muted"><?= e($cuon['ma_sach']) ?> &middot; <?= e($cuon['tac_gia']) ?></div>
                    </td>
                    <td><?= e($cuon['ma_phieu']) ?></td>
                    <td><?= e(ngayVN($cuon['ngay_muon'])) ?></td>
                    <td>
                        <?= e(ngayVN($cuon['ngay_hen_tra'])) ?>
                        <?php if ((int) $cuon['so_lan_gia_han_da_dung'] > 0): ?>
                            <div class="small text-muted">đã gia hạn <?= (int) $cuon['so_lan_gia_han_da_dung'] ?>/<?= (int) $docGia['so_lan_gia_han'] ?> lần</div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge text-bg-<?= $mau ?>"><?= e($nhan) ?></span></td>
                    <td class="text-end"><?= (int) $cuon['phat_du_kien'] > 0 ? e(tienVND($cuon['phat_du_kien'])) : '&mdash;' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($dsDangMuon) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Bạn không đang mượn cuốn nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">
        Muốn gia hạn hoặc trả sách, hãy đến quầy thủ thư. Phạt dự kiến = số ngày trễ x <?= e(tienVND($tienPhatMoiNgay)) ?> mỗi cuốn;
        số tiền thật được chốt lúc trả sách.
    </div>
</div>

<!-- ============ 2. Lịch sử mượn - trả ============ -->
<div class="card mb-4" id="lich-su">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 mb-0">Lịch sử mượn - trả</h2>
        <span class="text-muted">
            Tổng phạt trễ <strong><?= e(tienVND($tongPhatLichSu)) ?></strong>
            &middot; tổng bồi thường <strong><?= e(tienVND($tongDenLichSu)) ?></strong>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sách</th>
                    <th>Phiếu</th>
                    <th>Ngày mượn</th>
                    <th>Hẹn trả</th>
                    <th>Ngày trả</th>
                    <th>Tình trạng</th>
                    <th class="text-end">Phạt trễ</th>
                    <th class="text-end">Bồi thường</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsLichSu as $dong): ?>
                <tr>
                    <td>
                        <?= e($dong['ten_sach']) ?>
                        <div class="small text-muted"><?= e($dong['ma_sach']) ?></div>
                    </td>
                    <td><?= e($dong['ma_phieu']) ?></td>
                    <td><?= e(ngayVN($dong['ngay_muon'])) ?></td>
                    <td><?= e(ngayVN($dong['ngay_hen_tra'])) ?></td>
                    <td>
                        <?= e(ngayVN($dong['ngay_tra_thuc_te'])) ?>
                        <?php if ((int) $dong['so_ngay_tre'] > 0): ?>
                            <div class="small text-danger">trễ <?= (int) $dong['so_ngay_tre'] ?> ngày</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $tinhTra = (string) $dong['tinh_trang_tra'];
                        $mauTinhTra = ['nguyen_ven' => 'success', 'hu_hong' => 'warning', 'mat' => 'danger'][$tinhTra] ?? 'secondary';
                        ?>
                        <span class="badge text-bg-<?= $mauTinhTra ?>"><?= e(TINH_TRANG_TRA[$tinhTra] ?? '') ?></span>
                    </td>
                    <td class="text-end"><?= e(tienVND($dong['tien_phat'])) ?></td>
                    <td class="text-end"><?= e(tienVND($dong['tien_boi_thuong'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($numRows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Bạn chưa trả cuốn nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="text-muted">Trang <?= $pageNum ?>/<?= $maxPage ?> (<?= $numRows ?> lượt)</div>
        <?php if ($maxPage > 1): ?>
        <nav aria-label="Phân trang lịch sử">
            <ul class="pagination mb-0">
                <li class="page-item<?= $pageNum <= 1 ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= lienKetTrangLichSu(max(1, $pageNum - 1)) ?>">Trước</a>
                </li>
                <?php for ($i = max(1, $pageNum - 2); $i <= min($maxPage, $pageNum + 2); $i++): ?>
                    <li class="page-item<?= $i === $pageNum ? ' active' : '' ?>">
                        <a class="page-link" href="<?= lienKetTrangLichSu($i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item<?= $pageNum >= $maxPage ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= lienKetTrangLichSu(min($maxPage, $pageNum + 1)) ?>">Sau</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/tracuu-cuoi.php'; ?>

<?php
// Chi tiết một phiếu mượn: thông tin phiếu, các cuốn sách, nút trả từng cuốn / trả hết phiếu / gia hạn.
// Trang này chỉ hiển thị. Việc trả sách và gia hạn do phieumuon-tra.php và phieumuon-giahan.php xử lý (chỉ nhận POST).
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/muon-tra.php';

$idPhieu = filter_var(layChuoiGet('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$phieu   = null;
if ($idPhieu !== false) {
    $phieu = layMotDong(
        $conn,
        'SELECT pm.id, pm.ma_phieu, pm.ngay_muon, pm.ngay_hen_tra, pm.so_lan_gia_han_da_dung,
                dg.ma_doc_gia, dg.ho_ten, dg.trang_thai AS trang_thai_doc_gia,
                ld.ten_loai, ld.so_lan_gia_han, ld.so_ngay_muon,
                nv.ho_ten AS ten_nhan_vien,
                DATEDIFF(CURDATE(), pm.ngay_hen_tra) AS so_ngay_tre
         FROM phieumuon pm
         JOIN docgia dg     ON dg.id = pm.id_doc_gia
         JOIN loaidocgia ld ON ld.id = dg.id_loai
         JOIN nhanvien nv   ON nv.id = pm.id_nhan_vien
         WHERE pm.id = ?',
        'i',
        [$idPhieu]
    );
}
if ($phieu === null) {
    datThongBao('danger', 'Không tìm thấy phiếu mượn.');
    header('Location: phieumuon.php');
    exit;
}

$stmt = truyVan(
    $conn,
    'SELECT ct.id, ct.ngay_tra_thuc_te, ct.tinh_trang_tra, ct.tien_phat, ct.tien_boi_thuong,
            s.ma_sach, s.ten_sach, s.gia_tien
     FROM chitietphieumuon ct
     JOIN sach s ON s.id = ct.id_sach
     WHERE ct.id_phieu_muon = ?
     ORDER BY ct.id',
    'i',
    [(int) $phieu['id']]
);
$dsCuon = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$tienPhatMoiNgay = (int) ($cauHinh['tien_phat_moi_ngay'] ?? 3000);
$soNgayTre       = max(0, (int) $phieu['so_ngay_tre']);

$soChuaTra = 0;
$tongPhat  = 0;
$tongDen   = 0;
foreach ($dsCuon as $cuon) {
    if ($cuon['ngay_tra_thuc_te'] === null) {
        $soChuaTra++;
    }
    $tongPhat += (int) $cuon['tien_phat'];
    $tongDen  += (int) $cuon['tien_boi_thuong'];
}
[$nhanTrangThai, $mauTrangThai] = nhanTrangThaiPhieu($soChuaTra, (int) $phieu['so_ngay_tre']);

// Có cho bấm "Gia hạn" không? (máy chủ vẫn kiểm tra lại đầy đủ khi xử lý, đây chỉ để tắt nút và giải thích trước)
$lyDoKhongGiaHan = '';
if ($soChuaTra === 0) {
    $lyDoKhongGiaHan = 'Phiếu đã trả hết sách.';
} elseif ($phieu['trang_thai_doc_gia'] !== 'hoat_dong') {
    $lyDoKhongGiaHan = 'Độc giả đang bị khóa.';
} elseif ($soNgayTre > 0) {
    $lyDoKhongGiaHan = 'Phiếu đã quá hạn, không thể gia hạn.';
} elseif ((int) $phieu['so_lan_gia_han'] === 0) {
    $lyDoKhongGiaHan = 'Loại độc giả này không được gia hạn.';
} elseif ((int) $phieu['so_lan_gia_han_da_dung'] >= (int) $phieu['so_lan_gia_han']) {
    $lyDoKhongGiaHan = 'Đã dùng hết lượt gia hạn.';
}

$tieuDe      = 'Phiếu ' . $phieu['ma_phieu'];
$menuHienTai = 'phieumuon';
require __DIR__ . '/../includes/admin-dau.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="phieumuon.php">Phiếu mượn</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($phieu['ma_phieu']) ?></li>
    </ol>
</nav>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <h1 class="h4 mb-0">Phiếu <?= e($phieu['ma_phieu']) ?>
                <span class="badge text-bg-<?= $mauTrangThai ?> ms-2"><?= e($nhanTrangThai) ?></span>
            </h1>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="phieumuon-giahan.php">
                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $phieu['id'] ?>">
                    <?php if ($lyDoKhongGiaHan === ''): ?>
                        <button type="submit" class="btn btn-outline-primary"
                                onclick="return confirm('Gia hạn thêm <?= (int) $phieu['so_ngay_muon'] ?> ngày cho phiếu này?');">Gia hạn</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-primary" disabled
                                title="<?= e($lyDoKhongGiaHan) ?>">Gia hạn</button>
                    <?php endif; ?>
                </form>
                <?php if ($soChuaTra > 0): ?>
                    <form method="post" action="phieumuon-tra.php"
                          onsubmit="return confirm('Nhận trả hết <?= $soChuaTra ?> cuốn còn lại, tất cả nguyên vẹn?');">
                        <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                        <input type="hidden" name="hanh_dong" value="tra_het">
                        <input type="hidden" name="id_phieu" value="<?= (int) $phieu['id'] ?>">
                        <button type="submit" class="btn btn-success">Trả hết phiếu (nguyên vẹn)</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Độc giả</dt>
                    <dd class="col-sm-8"><?= e($phieu['ho_ten']) ?> (<?= e($phieu['ma_doc_gia']) ?>)</dd>
                    <dt class="col-sm-4">Loại</dt>
                    <dd class="col-sm-8"><?= e($phieu['ten_loai']) ?></dd>
                    <dt class="col-sm-4">Thủ thư lập</dt>
                    <dd class="col-sm-8"><?= e($phieu['ten_nhan_vien']) ?></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Ngày mượn</dt>
                    <dd class="col-sm-8"><?= e(ngayVN($phieu['ngay_muon'])) ?></dd>
                    <dt class="col-sm-4">Hẹn trả</dt>
                    <dd class="col-sm-8"><?= e(ngayVN($phieu['ngay_hen_tra'])) ?></dd>
                    <dt class="col-sm-4">Gia hạn</dt>
                    <dd class="col-sm-8"><?= (int) $phieu['so_lan_gia_han_da_dung'] ?> / <?= (int) $phieu['so_lan_gia_han'] ?> lần
                        <?php if ($lyDoKhongGiaHan !== '' && $soChuaTra > 0): ?>
                            <span class="text-muted small">(<?= e($lyDoKhongGiaHan) ?>)</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã</th>
                    <th>Tên sách</th>
                    <th>Tình trạng</th>
                    <th class="text-end">Phạt trễ</th>
                    <th class="text-end">Bồi thường</th>
                    <th class="text-end">Nhận trả</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsCuon as $cuon): ?>
                <tr>
                    <td><?= e($cuon['ma_sach']) ?></td>
                    <td><?= e($cuon['ten_sach']) ?></td>
                    <?php if ($cuon['ngay_tra_thuc_te'] === null): ?>
                        <td>
                            <span class="badge text-bg-<?= $soNgayTre > 0 ? 'danger' : 'primary' ?>">Chưa trả</span>
                            <?php if ($soNgayTre > 0): ?>
                                <div class="small text-danger">Trễ <?= $soNgayTre ?> ngày</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($soNgayTre > 0): ?>
                                <span class="text-muted small">dự kiến</span> <?= e(tienVND(tinhTienPhat($soNgayTre, $tienPhatMoiNgay))) ?>
                            <?php else: ?>
                                0đ
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted">&mdash;</td>
                        <td class="text-end">
                            <form method="post" action="phieumuon-tra.php" class="d-inline-flex gap-1 justify-content-end"
                                  onsubmit="return this.tinh_trang.value !== 'mat' || confirm('Xác nhận cuốn này bị MẤT? Độc giả đền gấp đôi giá sách và tổng số bản của sách giảm 1.');">
                                <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                                <input type="hidden" name="hanh_dong" value="tra_mot">
                                <input type="hidden" name="id_chi_tiet" value="<?= (int) $cuon['id'] ?>">
                                <select name="tinh_trang" class="form-select form-select-sm w-auto" aria-label="Tình trạng khi trả">
                                    <?php foreach (TINH_TRANG_TRA as $ma => $nhan): ?>
                                        <option value="<?= e($ma) ?>"><?= e($nhan) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-success">Trả</button>
                            </form>
                        </td>
                    <?php else: ?>
                        <td>
                            <span class="badge text-bg-<?= $cuon['tinh_trang_tra'] === 'nguyen_ven' ? 'success' : 'warning' ?>">
                                <?= e(TINH_TRANG_TRA[$cuon['tinh_trang_tra']] ?? '') ?>
                            </span>
                            <div class="small text-muted">Trả ngày <?= e(ngayVN($cuon['ngay_tra_thuc_te'])) ?></div>
                        </td>
                        <td class="text-end"><?= e(tienVND($cuon['tien_phat'])) ?></td>
                        <td class="text-end"><?= e(tienVND($cuon['tien_boi_thuong'])) ?></td>
                        <td class="text-end text-muted">Đã trả</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Tổng đã phát sinh</th>
                    <th class="text-end"><?= e(tienVND($tongPhat)) ?></th>
                    <th class="text-end"><?= e(tienVND($tongDen)) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

<?php
// Thống kê: sách mượn nhiều nhất, phiếu đang trễ hạn, tiền phạt / bồi thường theo tháng.
// Trang chỉ ĐỌC dữ liệu, không ghi gì. "Quá hạn" tính theo ngày hẹn trả so với hôm nay
// và theo các cuốn chưa trả, không đọc cột phieumuon.trang_thai (cột đó có thể đã cũ).
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/muon-tra.php';

$soSachHienThi   = 10;   // số dòng của bảng "sách mượn nhiều nhất"
$tienPhatMoiNgay = (int) ($cauHinh['tien_phat_moi_ngay'] ?? 3000);

// ---------------------------------------------------------------------
// Hàm nhỏ chỉ dùng trong trang này
// ---------------------------------------------------------------------

// Đọc một ngày lọc từ URL (dạng năm-tháng-ngày).
// Trả về [chuỗi ngày hoặc '', thông báo lỗi hoặc null]. Để trống thì không có lỗi.
function docNgayLoc(string $ten): array
{
    $chuoi = layChuoiGet($ten);
    if ($chuoi === '') {
        return ['', null];
    }
    $ngay = DateTime::createFromFormat('Y-m-d', $chuoi);
    // So sánh lại chuỗi để loại ngày không có thật (vd 2026-02-30 sẽ bị PHP tự đổi sang 2026-03-02)
    if ($ngay === false || $ngay->format('Y-m-d') !== $chuoi) {
        return ['', 'Ngày không hợp lệ.'];
    }
    return [$chuoi, null];
}

// Ghép điều kiện "cột >= từ ngày" và "cột <= đến ngày" cho câu SQL.
// $cot là tên cột do chính code này truyền vào (không lấy từ người dùng) nên nối chuỗi an toàn;
// giá trị ngày thì đi qua dấu ? như mọi chỗ khác.
// Trả về [mảng điều kiện, chuỗi kiểu bind_param, mảng giá trị].
function ghepLocNgay(string $cot, string $tuNgay, string $denNgay): array
{
    $dieuKien = [];
    $kieu     = '';
    $thamSo   = [];
    if ($tuNgay !== '') {
        $dieuKien[] = "$cot >= ?";
        $kieu      .= 's';
        $thamSo[]   = $tuNgay;
    }
    if ($denNgay !== '') {
        $dieuKien[] = "$cot <= ?";
        $kieu      .= 's';
        $thamSo[]   = $denNgay;
    }
    return [$dieuKien, $kieu, $thamSo];
}

// Chiều rộng thanh ngang (phần trăm) của $so so với giá trị lớn nhất $toiDa, dạng "37.5"
function phanTram(int $so, int $toiDa): string
{
    if ($toiDa <= 0) {
        return '0';
    }
    return number_format($so * 100 / $toiDa, 1, '.', '');
}

// ---------------------------------------------------------------------
// 1. Đọc và kiểm tra bộ lọc ngày
// ---------------------------------------------------------------------
[$tuNgay, $loiTu]   = docNgayLoc('tu');
[$denNgay, $loiDen] = docNgayLoc('den');

$loiLoc = [];   // chỉ chứa ô thật sự có lỗi (lopLoi/hienLoi xét theo SỰ TỒN TẠI của khóa)
if ($loiTu !== null) {
    $loiLoc['tu'] = $loiTu;
}
if ($loiDen !== null) {
    $loiLoc['den'] = $loiDen;
}
if (count($loiLoc) === 0 && $tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay) {
    $loiLoc['den'] = 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.';
}
if (count($loiLoc) > 0) {
    // Bộ lọc sai thì không áp dụng bộ lọc nào, hiện toàn bộ dữ liệu kèm thông báo
    $tuNgay  = '';
    $denNgay = '';
}
$dangLoc = ($tuNgay !== '' || $denNgay !== '');

// ---------------------------------------------------------------------
// 2. Sách mượn nhiều nhất (lọc theo ngày mượn của phiếu)
// ---------------------------------------------------------------------
[$dieuKien, $kieu, $thamSo] = ghepLocNgay('pm.ngay_muon', $tuNgay, $denNgay);
$menhDeWhere = count($dieuKien) > 0 ? 'WHERE ' . implode(' AND ', $dieuKien) : '';

$stmt = truyVan(
    $conn,
    "SELECT s.id, s.ma_sach, s.ten_sach, s.tac_gia, COUNT(ct.id) AS luot_muon
     FROM chitietphieumuon ct
     JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
     JOIN sach s       ON s.id = ct.id_sach
     $menhDeWhere
     GROUP BY s.id, s.ma_sach, s.ten_sach, s.tac_gia
     ORDER BY luot_muon DESC, s.ma_sach
     LIMIT ?",
    $kieu . 'i',
    array_merge($thamSo, [$soSachHienThi])
);
$dsSachNhieu = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
$luotLonNhat = count($dsSachNhieu) > 0 ? (int) $dsSachNhieu[0]['luot_muon'] : 0;

// ---------------------------------------------------------------------
// 3. Phiếu đang trễ hạn (luôn tính theo hôm nay, không lọc theo ngày)
// ---------------------------------------------------------------------
// Mỗi dòng là một phiếu còn cuốn chưa trả mà đã quá ngày hẹn.
$dsTreHan = mysqli_fetch_all(
    mysqli_query(
        $conn,
        'SELECT pm.id, pm.ma_phieu, pm.ngay_hen_tra, dg.ma_doc_gia, dg.ho_ten, dg.sdt,
                DATEDIFF(CURDATE(), pm.ngay_hen_tra) AS so_ngay_tre,
                COUNT(ct.id) AS so_cuon_tre
         FROM phieumuon pm
         JOIN docgia dg           ON dg.id = pm.id_doc_gia
         JOIN chitietphieumuon ct ON ct.id_phieu_muon = pm.id
         WHERE ct.ngay_tra_thuc_te IS NULL AND pm.ngay_hen_tra < CURDATE()
         GROUP BY pm.id, pm.ma_phieu, pm.ngay_hen_tra, dg.ma_doc_gia, dg.ho_ten, dg.sdt
         ORDER BY so_ngay_tre DESC, pm.ma_phieu'
    ),
    MYSQLI_ASSOC
);

// Tiền phạt dự kiến = số cuốn trễ x số ngày trễ x tiền phạt mỗi ngày
// (dùng đúng hàm tinhTienPhat() của lúc trả sách để hai con số luôn khớp nhau)
$tongPhatDuKien = 0;
$docGiaTre      = [];   // các mã độc giả khác nhau đang trễ, để đếm số người
foreach ($dsTreHan as $chiSo => $phieu) {
    $phatMotCuon = tinhTienPhat((int) $phieu['so_ngay_tre'], $tienPhatMoiNgay);
    $dsTreHan[$chiSo]['phat_du_kien'] = $phatMotCuon * (int) $phieu['so_cuon_tre'];
    $tongPhatDuKien += $dsTreHan[$chiSo]['phat_du_kien'];
    $docGiaTre[$phieu['ma_doc_gia']] = true;
}

// ---------------------------------------------------------------------
// 4. Tiền phạt và bồi thường theo tháng (chỉ tính cuốn ĐÃ TRẢ, gom theo tháng của ngày trả)
// ---------------------------------------------------------------------
[$dieuKien, $kieu, $thamSo] = ghepLocNgay('ct.ngay_tra_thuc_te', $tuNgay, $denNgay);
array_unshift($dieuKien, 'ct.ngay_tra_thuc_te IS NOT NULL');

$stmt = truyVan(
    $conn,
    "SELECT DATE_FORMAT(ct.ngay_tra_thuc_te, '%Y-%m') AS thang,
            COUNT(*) AS so_cuon,
            SUM(ct.tien_phat) AS tong_phat,
            SUM(ct.tien_boi_thuong) AS tong_boi_thuong
     FROM chitietphieumuon ct
     WHERE " . implode(' AND ', $dieuKien) . "
     GROUP BY thang
     ORDER BY thang DESC",
    $kieu,
    $thamSo
);
$dsTheoThang = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$tongCuonDaTra = 0;
$tongPhat      = 0;
$tongBoiThuong = 0;
$thangLonNhat  = 0;   // tổng (phạt + bồi thường) lớn nhất của một tháng, để chia tỉ lệ thanh ngang
foreach ($dsTheoThang as $dong) {
    $tongCuonDaTra += (int) $dong['so_cuon'];
    $tongPhat      += (int) $dong['tong_phat'];
    $tongBoiThuong += (int) $dong['tong_boi_thuong'];
    $thangLonNhat   = max($thangLonNhat, (int) $dong['tong_phat'] + (int) $dong['tong_boi_thuong']);
}

$tieuDe      = 'Thống kê';
$menuHienTai = 'thongke';
require __DIR__ . '/../includes/admin-dau.php';
?>
<h1 class="h3 mb-3">Thống kê</h1>

<!-- Bộ lọc dùng GET nên đường link có thể chép/gửi cho người khác -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end" novalidate>
            <div class="col-6 col-md-3">
                <label for="tu" class="form-label mb-1">Từ ngày</label>
                <input type="date" class="form-control<?= lopLoi($loiLoc, 'tu') ?>" id="tu" name="tu"
                       value="<?= e($tuNgay) ?>">
                <?php hienLoi($loiLoc, 'tu'); ?>
            </div>
            <div class="col-6 col-md-3">
                <label for="den" class="form-label mb-1">Đến ngày</label>
                <input type="date" class="form-control<?= lopLoi($loiLoc, 'den') ?>" id="den" name="den"
                       value="<?= e($denNgay) ?>">
                <?php hienLoi($loiLoc, 'den'); ?>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Lọc</button>
                <?php if ($dangLoc || count($loiLoc) > 0): ?>
                    <a href="thongke.php" class="btn btn-outline-secondary">Bỏ lọc</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="form-text mt-2">
            Bộ lọc áp dụng cho mục 1 (theo ngày mượn) và mục 3 (theo ngày trả).
            Mục 2 luôn tính theo hôm nay. Để trống cả hai ô là xem toàn bộ.
        </div>
    </div>
</div>

<!-- ============ 1. Sách mượn nhiều nhất ============ -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">1. Sách được mượn nhiều nhất <span class="text-muted fs-6">(top <?= $soSachHienThi ?>)</span></h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:3rem">#</th>
                    <th>Mã</th>
                    <th>Tên sách</th>
                    <th>Tác giả</th>
                    <th class="text-end">Lượt mượn</th>
                    <th style="width:25%">Biểu đồ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsSachNhieu as $thuTu => $sach): ?>
                <tr>
                    <td><?= $thuTu + 1 ?></td>
                    <td><?= e($sach['ma_sach']) ?></td>
                    <td><?= e($sach['ten_sach']) ?></td>
                    <td><?= e($sach['tac_gia']) ?></td>
                    <td class="text-end"><?= (int) $sach['luot_muon'] ?></td>
                    <td>
                        <div class="progress" style="height:.9rem" role="img"
                             aria-label="<?= (int) $sach['luot_muon'] ?> lượt mượn">
                            <div class="progress-bar" style="width:<?= phanTram((int) $sach['luot_muon'], $luotLonNhat) ?>%"></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($dsSachNhieu) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có lượt mượn nào<?= $dangLoc ? ' trong khoảng ngày đã chọn' : '' ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ 2. Phiếu đang trễ hạn ============ -->
<div class="card mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 mb-0">2. Độc giả đang trễ hạn</h2>
        <span class="text-muted">
            <?= count($docGiaTre) ?> độc giả, <?= count($dsTreHan) ?> phiếu &middot;
            phạt dự kiến <strong class="text-danger"><?= e(tienVND($tongPhatDuKien)) ?></strong>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Độc giả</th>
                    <th>Phiếu</th>
                    <th>Hẹn trả</th>
                    <th class="text-center">Cuốn trễ</th>
                    <th class="text-center">Trễ (ngày)</th>
                    <th class="text-end">Phạt dự kiến</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsTreHan as $phieu): ?>
                <tr>
                    <td>
                        <?= e($phieu['ho_ten']) ?>
                        <div class="small text-muted">
                            <?= e($phieu['ma_doc_gia']) ?><?php if ($phieu['sdt'] !== null && $phieu['sdt'] !== ''): ?> &middot; <?= e($phieu['sdt']) ?><?php endif; ?>
                        </div>
                    </td>
                    <td><a href="phieumuon-chitiet.php?id=<?= (int) $phieu['id'] ?>"><?= e($phieu['ma_phieu']) ?></a></td>
                    <td><?= e(ngayVN($phieu['ngay_hen_tra'])) ?></td>
                    <td class="text-center"><?= (int) $phieu['so_cuon_tre'] ?></td>
                    <td class="text-center"><span class="badge text-bg-danger"><?= (int) $phieu['so_ngay_tre'] ?></span></td>
                    <td class="text-end"><?= e(tienVND($phieu['phat_du_kien'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($dsTreHan) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Hiện không có phiếu nào trễ hạn.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">
        Phạt dự kiến = số cuốn trễ x số ngày trễ x <?= e(tienVND($tienPhatMoiNgay)) ?>.
        Số tiền thật được chốt lúc nhận trả sách.
    </div>
</div>

<!-- ============ 3. Tiền phạt và bồi thường theo tháng ============ -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">3. Tiền phạt và bồi thường theo tháng</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tháng</th>
                    <th class="text-end">Cuốn đã trả</th>
                    <th class="text-end">Phạt trễ</th>
                    <th class="text-end">Bồi thường</th>
                    <th class="text-end">Tổng</th>
                    <th style="width:25%">Biểu đồ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsTheoThang as $dong): ?>
                <?php
                $phat     = (int) $dong['tong_phat'];
                $boiThuong = (int) $dong['tong_boi_thuong'];
                // 'YYYY-MM' -> 'MM/YYYY'
                [$nam, $thang] = explode('-', $dong['thang']);
                ?>
                <tr>
                    <td><?= e($thang . '/' . $nam) ?></td>
                    <td class="text-end"><?= (int) $dong['so_cuon'] ?></td>
                    <td class="text-end"><?= e(tienVND($phat)) ?></td>
                    <td class="text-end"><?= e(tienVND($boiThuong)) ?></td>
                    <td class="text-end fw-semibold"><?= e(tienVND($phat + $boiThuong)) ?></td>
                    <td>
                        <div class="progress" style="height:.9rem" role="img"
                             aria-label="Phạt <?= e(tienVND($phat)) ?>, bồi thường <?= e(tienVND($boiThuong)) ?>">
                            <div class="progress-bar bg-warning" style="width:<?= phanTram($phat, $thangLonNhat) ?>%"></div>
                            <div class="progress-bar bg-danger" style="width:<?= phanTram($boiThuong, $thangLonNhat) ?>%"></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($dsTheoThang) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có cuốn nào được trả<?= $dangLoc ? ' trong khoảng ngày đã chọn' : '' ?>.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if (count($dsTheoThang) > 0): ?>
            <tfoot class="table-light">
                <tr>
                    <th>Cộng</th>
                    <th class="text-end"><?= $tongCuonDaTra ?></th>
                    <th class="text-end"><?= e(tienVND($tongPhat)) ?></th>
                    <th class="text-end"><?= e(tienVND($tongBoiThuong)) ?></th>
                    <th class="text-end"><?= e(tienVND($tongPhat + $tongBoiThuong)) ?></th>
                    <th></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">
        <span class="badge text-bg-warning">&nbsp;</span> Phạt trả trễ
        &nbsp;&nbsp;
        <span class="badge text-bg-danger">&nbsp;</span> Bồi thường (hỏng / mất).
        Chỉ tính các cuốn đã trả, gom theo tháng của ngày trả.
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

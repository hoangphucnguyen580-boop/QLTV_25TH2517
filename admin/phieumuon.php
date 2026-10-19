<?php
// Danh sách phiếu mượn: tìm theo mã phiếu / mã độc giả / họ tên, lọc theo trạng thái, phân trang.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/muon-tra.php';

$soDongMoiTrang = 10;

// ---- 1. Đọc điều kiện lọc từ URL (ép kiểu, không tin dữ liệu người dùng) ----
$tuKhoa = layChuoiGet('q');

$trangThai = layChuoiGet('trangthai');
if (!in_array($trangThai, ['dang_muon', 'qua_han', 'da_tra'], true)) {
    $trangThai = '';                                                  // '' = tất cả
}

$pageNum = filter_var(layChuoiGet('page'), FILTER_VALIDATE_INT);
$pageNum = ($pageNum !== false && $pageNum > 0) ? $pageNum : 1;

// ---- 2. Ghép mệnh đề WHERE ----
// Trạng thái được TÍNH THEO NGÀY và theo các cuốn chưa trả, không đọc cột phieumuon.trang_thai (cột đó có thể đã cũ).
$conCuonChuaTra = 'EXISTS (SELECT 1 FROM chitietphieumuon ct
                           WHERE ct.id_phieu_muon = pm.id AND ct.ngay_tra_thuc_te IS NULL)';

$dieuKien = [];
$kieu     = '';
$thamSo   = [];

if ($tuKhoa !== '') {
    // Ký tự % và _ có nghĩa đặc biệt trong LIKE nên phải thoát trước khi ghép
    $mau        = '%' . addcslashes($tuKhoa, '%_\\') . '%';
    $dieuKien[] = '(pm.ma_phieu LIKE ? OR dg.ma_doc_gia LIKE ? OR dg.ho_ten LIKE ?)';
    $kieu      .= 'sss';
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
}
if ($trangThai === 'dang_muon') {
    $dieuKien[] = "$conCuonChuaTra AND pm.ngay_hen_tra >= CURDATE()";
} elseif ($trangThai === 'qua_han') {
    $dieuKien[] = "$conCuonChuaTra AND pm.ngay_hen_tra < CURDATE()";
} elseif ($trangThai === 'da_tra') {
    $dieuKien[] = "NOT $conCuonChuaTra";
}
$menhDeWhere = count($dieuKien) > 0 ? 'WHERE ' . implode(' AND ', $dieuKien) : '';

// ---- 3. Đếm tổng số dòng khớp để tính số trang ----
$stmt = truyVan(
    $conn,
    "SELECT COUNT(*) FROM phieumuon pm JOIN docgia dg ON dg.id = pm.id_doc_gia $menhDeWhere",
    $kieu,
    $thamSo
);
$numRows = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
mysqli_stmt_close($stmt);

$maxPage = max(1, (int) ceil($numRows / $soDongMoiTrang));
$pageNum = min($pageNum, $maxPage);
$offset  = ($pageNum - 1) * $soDongMoiTrang;

// ---- 4. Lấy các dòng của trang hiện tại (phiếu mới nhất lên đầu) ----
$sql = "SELECT pm.id, pm.ma_phieu, pm.ngay_muon, pm.ngay_hen_tra, dg.ma_doc_gia, dg.ho_ten,
               DATEDIFF(CURDATE(), pm.ngay_hen_tra) AS so_ngay_tre,
               (SELECT COUNT(*) FROM chitietphieumuon ct WHERE ct.id_phieu_muon = pm.id) AS tong_cuon,
               (SELECT COUNT(*) FROM chitietphieumuon ct
                WHERE ct.id_phieu_muon = pm.id AND ct.ngay_tra_thuc_te IS NULL) AS chua_tra
        FROM phieumuon pm
        JOIN docgia dg ON dg.id = pm.id_doc_gia
        $menhDeWhere
        ORDER BY pm.id DESC
        LIMIT ? OFFSET ?";
$stmt = truyVan(
    $conn,
    $sql,
    $kieu . 'ii',
    array_merge($thamSo, [$soDongMoiTrang, $offset])
);
$dsPhieu = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Tạo liên kết tới một trang, giữ nguyên từ khóa và trạng thái đang lọc
function lienKetTrang(int $page, string $tuKhoa, string $trangThai): string
{
    $thamSoUrl = ['page' => $page];
    if ($tuKhoa !== '') {
        $thamSoUrl['q'] = $tuKhoa;
    }
    if ($trangThai !== '') {
        $thamSoUrl['trangthai'] = $trangThai;
    }
    return '?' . e(http_build_query($thamSoUrl));
}

$tieuDe      = 'Phiếu mượn';
$menuHienTai = 'phieumuon';
require __DIR__ . '/../includes/admin-dau.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Phiếu mượn</h1>
    <a href="phieumuon-them.php" class="btn btn-primary">+ Lập phiếu mượn</a>
</div>

<!-- Form tìm kiếm dùng GET nên đường link tìm kiếm có thể chép/gửi cho người khác -->
<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-6">
        <input type="text" class="form-control" name="q" value="<?= e($tuKhoa) ?>"
               placeholder="Tìm theo mã phiếu, mã độc giả hoặc họ tên">
    </div>
    <div class="col-8 col-md-4">
        <select class="form-select" name="trangthai">
            <option value="">Mọi trạng thái</option>
            <option value="dang_muon"<?= $trangThai === 'dang_muon' ? ' selected' : '' ?>>Đang mượn (còn hạn)</option>
            <option value="qua_han"<?= $trangThai === 'qua_han' ? ' selected' : '' ?>>Quá hạn</option>
            <option value="da_tra"<?= $trangThai === 'da_tra' ? ' selected' : '' ?>>Đã trả hết</option>
        </select>
    </div>
    <div class="col-4 col-md-2 d-grid">
        <button type="submit" class="btn btn-outline-primary">Tìm</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã phiếu</th>
                    <th>Độc giả</th>
                    <th>Ngày mượn</th>
                    <th>Hẹn trả</th>
                    <th class="text-center">Chưa trả / Tổng</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsPhieu as $phieu): ?>
                <?php [$nhan, $mau] = nhanTrangThaiPhieu((int) $phieu['chua_tra'], (int) $phieu['so_ngay_tre']); ?>
                <tr>
                    <td><?= e($phieu['ma_phieu']) ?></td>
                    <td>
                        <?= e($phieu['ho_ten']) ?>
                        <div class="small text-muted"><?= e($phieu['ma_doc_gia']) ?></div>
                    </td>
                    <td><?= e(ngayVN($phieu['ngay_muon'])) ?></td>
                    <td><?= e(ngayVN($phieu['ngay_hen_tra'])) ?></td>
                    <td class="text-center"><?= (int) $phieu['chua_tra'] ?> / <?= (int) $phieu['tong_cuon'] ?></td>
                    <td><span class="badge text-bg-<?= $mau ?>"><?= e($nhan) ?></span></td>
                    <td class="text-end text-nowrap">
                        <a href="phieumuon-chitiet.php?id=<?= (int) $phieu['id'] ?>" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($numRows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Không có phiếu mượn nào khớp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
    <div class="text-muted">Trang <?= $pageNum ?>/<?= $maxPage ?> (<?= $numRows ?> kết quả)</div>
    <?php if ($maxPage > 1): ?>
    <nav aria-label="Phân trang">
        <ul class="pagination mb-0">
            <li class="page-item<?= $pageNum <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= lienKetTrang(max(1, $pageNum - 1), $tuKhoa, $trangThai) ?>">Trước</a>
            </li>
            <?php for ($i = max(1, $pageNum - 2); $i <= min($maxPage, $pageNum + 2); $i++): ?>
                <li class="page-item<?= $i === $pageNum ? ' active' : '' ?>">
                    <a class="page-link" href="<?= lienKetTrang($i, $tuKhoa, $trangThai) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item<?= $pageNum >= $maxPage ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= lienKetTrang(min($maxPage, $pageNum + 1), $tuKhoa, $trangThai) ?>">Sau</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

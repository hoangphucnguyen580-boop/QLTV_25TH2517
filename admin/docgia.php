<?php
// Danh sách độc giả: tìm kiếm theo mã/họ tên/SĐT/email, lọc theo loại và trạng thái, phân trang.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

$soDongMoiTrang = 10;

// ---- 1. Đọc điều kiện lọc từ URL (ép kiểu, không tin dữ liệu người dùng) ----
$tuKhoa = layChuoiGet('q');

$idLoai = filter_var(layChuoiGet('loai'), FILTER_VALIDATE_INT);
$idLoai = ($idLoai !== false && $idLoai > 0) ? $idLoai : 0;          // 0 = tất cả loại

$trangThai = layChuoiGet('trangthai');
if (!in_array($trangThai, ['hoat_dong', 'khoa'], true)) {
    $trangThai = '';                                                  // '' = tất cả trạng thái
}

$pageNum = filter_var(layChuoiGet('page'), FILTER_VALIDATE_INT);
$pageNum = ($pageNum !== false && $pageNum > 0) ? $pageNum : 1;

// ---- 2. Ghép mệnh đề WHERE theo điều kiện nào đang có ----
$dieuKien = [];
$kieu     = '';
$thamSo   = [];

if ($tuKhoa !== '') {
    // Ký tự % và _ có nghĩa đặc biệt trong LIKE nên phải thoát trước khi ghép
    $mau        = '%' . addcslashes($tuKhoa, '%_\\') . '%';
    $dieuKien[] = '(dg.ma_doc_gia LIKE ? OR dg.ho_ten LIKE ? OR dg.sdt LIKE ? OR dg.email LIKE ?)';
    $kieu      .= 'ssss';
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
}
if ($idLoai > 0) {
    $dieuKien[] = 'dg.id_loai = ?';
    $kieu      .= 'i';
    $thamSo[]   = $idLoai;
}
if ($trangThai !== '') {
    $dieuKien[] = 'dg.trang_thai = ?';
    $kieu      .= 's';
    $thamSo[]   = $trangThai;
}
$menhDeWhere = count($dieuKien) > 0 ? 'WHERE ' . implode(' AND ', $dieuKien) : '';

// ---- 3. Đếm tổng số dòng khớp để tính số trang ----
$stmt    = truyVan($conn, "SELECT COUNT(*) FROM docgia dg $menhDeWhere", $kieu, $thamSo);
$numRows = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
mysqli_stmt_close($stmt);

$maxPage = max(1, (int) ceil($numRows / $soDongMoiTrang));
$pageNum = min($pageNum, $maxPage);
$offset  = ($pageNum - 1) * $soDongMoiTrang;

// ---- 4. Lấy các dòng của trang hiện tại ----
// dang_muon: số cuốn chưa trả. qua_han: số cuốn chưa trả mà đã quá ngày hẹn
// (tính theo ngày hẹn, không dựa vào cột phieumuon.trang_thai).
$sql = "SELECT dg.id, dg.ma_doc_gia, dg.ho_ten, dg.sdt, dg.email, dg.trang_thai, ld.ten_loai,
               (SELECT COUNT(*)
                FROM chitietphieumuon ct
                JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
                WHERE pm.id_doc_gia = dg.id AND ct.ngay_tra_thuc_te IS NULL) AS dang_muon,
               (SELECT COUNT(*)
                FROM chitietphieumuon ct
                JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
                WHERE pm.id_doc_gia = dg.id AND ct.ngay_tra_thuc_te IS NULL
                  AND pm.ngay_hen_tra < CURDATE()) AS qua_han
        FROM docgia dg
        JOIN loaidocgia ld ON ld.id = dg.id_loai
        $menhDeWhere
        ORDER BY dg.ma_doc_gia
        LIMIT ? OFFSET ?";
$stmt = truyVan(
    $conn,
    $sql,
    $kieu . 'ii',
    array_merge($thamSo, [$soDongMoiTrang, $offset])
);
$dsDocGia = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$dsLoai = mysqli_fetch_all(
    mysqli_query($conn, 'SELECT id, ten_loai FROM loaidocgia ORDER BY id'),
    MYSQLI_ASSOC
);

// Tạo liên kết tới một trang, giữ nguyên từ khóa và các bộ lọc đang chọn
function lienKetTrang(int $page, string $tuKhoa, int $idLoai, string $trangThai): string
{
    $thamSoUrl = ['page' => $page];
    if ($tuKhoa !== '') {
        $thamSoUrl['q'] = $tuKhoa;
    }
    if ($idLoai > 0) {
        $thamSoUrl['loai'] = $idLoai;
    }
    if ($trangThai !== '') {
        $thamSoUrl['trangthai'] = $trangThai;
    }
    return '?' . e(http_build_query($thamSoUrl));
}

$tieuDe      = 'Quản lý độc giả';
$menuHienTai = 'docgia';
require __DIR__ . '/../includes/admin-dau.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Quản lý độc giả</h1>
    <a href="docgia-form.php" class="btn btn-primary"><img class="ic" src="../assets/icons/trang/plus-lg.svg" alt="" width="16" height="16">Thêm độc giả</a>
</div>

<!-- Form tìm kiếm dùng GET nên đường link tìm kiếm có thể chép/gửi cho người khác -->
<form method="get" class="row g-2 mb-3" role="search">
    <div class="col-12 col-md-4">
        <input type="text" class="form-control" name="q" value="<?= e($tuKhoa) ?>" aria-label="Tìm độc giả"
               placeholder="Tìm theo mã, họ tên, số điện thoại hoặc email">
    </div>
    <div class="col-6 col-md-2">
        <select class="form-select" name="loai" aria-label="Lọc theo loại độc giả">
            <option value="0">Tất cả loại</option>
            <?php foreach ($dsLoai as $loai): ?>
                <option value="<?= (int) $loai['id'] ?>"<?= ((int) $loai['id'] === $idLoai) ? ' selected' : '' ?>>
                    <?= e($loai['ten_loai']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select class="form-select" name="trangthai" aria-label="Lọc theo trạng thái">
            <option value="">Mọi trạng thái</option>
            <option value="hoat_dong"<?= $trangThai === 'hoat_dong' ? ' selected' : '' ?>>Hoạt động</option>
            <option value="khoa"<?= $trangThai === 'khoa' ? ' selected' : '' ?>>Đã khóa</option>
        </select>
    </div>
    <div class="col-12 col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary flex-fill"><img class="ic" src="../assets/icons/den/search.svg" alt="" width="16" height="16">Tìm</button>
        <?php if ($tuKhoa !== '' || $idLoai > 0 || $trangThai !== ''): ?>
            <!-- Chỉ hiện khi đang có bộ lọc; bấm vào là về danh sách đầy đủ -->
            <a href="docgia.php" class="btn btn-outline-secondary flex-fill"><img class="ic" src="../assets/icons/den/x-circle.svg" alt="" width="16" height="16">Xóa lọc</a>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã</th>
                    <th>Họ tên</th>
                    <th>Loại</th>
                    <th>Liên hệ</th>
                    <th class="text-center">Đang mượn</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsDocGia as $docGia): ?>
                <tr>
                    <td><?= e($docGia['ma_doc_gia']) ?></td>
                    <td><?= e($docGia['ho_ten']) ?></td>
                    <td><?= e($docGia['ten_loai']) ?></td>
                    <td>
                        <?= e($docGia['sdt'] ?? '') ?>
                        <?php if ($docGia['email'] !== null && $docGia['email'] !== ''): ?>
                            <div class="small text-muted"><?= e($docGia['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?= (int) $docGia['dang_muon'] ?>
                        <?php if ((int) $docGia['qua_han'] > 0): ?>
                            <span class="badge text-bg-danger">Quá hạn <?= (int) $docGia['qua_han'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($docGia['trang_thai'] === 'hoat_dong'): ?>
                            <span class="badge text-bg-success">Hoạt động</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Đã khóa</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="docgia-form.php?id=<?= (int) $docGia['id'] ?>" class="btn btn-sm btn-outline-secondary"><img class="ic" src="../assets/icons/den/pencil-square.svg" alt="" width="14" height="14">Sửa</a>

                        <!-- Khóa / mở khóa: gửi rõ trạng thái muốn đặt, không "đảo" để bấm đúp không bị lệch -->
                        <form method="post" action="docgia-khoa.php" class="d-inline">
                            <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $docGia['id'] ?>">
                            <?php if ($docGia['trang_thai'] === 'hoat_dong'): ?>
                                <input type="hidden" name="trang_thai" value="khoa">
                                <button type="submit" class="btn btn-sm btn-outline-warning"><img class="ic" src="../assets/icons/den/lock.svg" alt="" width="14" height="14">Khóa</button>
                            <?php else: ?>
                                <input type="hidden" name="trang_thai" value="hoat_dong">
                                <button type="submit" class="btn btn-sm btn-outline-success"><img class="ic" src="../assets/icons/den/unlock.svg" alt="" width="14" height="14">Mở khóa</button>
                            <?php endif; ?>
                        </form>

                        <form method="post" action="docgia-xoa.php" class="d-inline"
                              onsubmit="return confirm('Xóa độc giả này?');">
                            <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $docGia['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><img class="ic" src="../assets/icons/den/trash.svg" alt="" width="14" height="14">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($numRows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Không có độc giả nào khớp.</td></tr>
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
                <a class="page-link" href="<?= lienKetTrang(max(1, $pageNum - 1), $tuKhoa, $idLoai, $trangThai) ?>">Trước</a>
            </li>
            <?php for ($i = max(1, $pageNum - 2); $i <= min($maxPage, $pageNum + 2); $i++): ?>
                <li class="page-item<?= $i === $pageNum ? ' active' : '' ?>">
                    <a class="page-link" href="<?= lienKetTrang($i, $tuKhoa, $idLoai, $trangThai) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item<?= $pageNum >= $maxPage ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= lienKetTrang(min($maxPage, $pageNum + 1), $tuKhoa, $idLoai, $trangThai) ?>">Sau</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

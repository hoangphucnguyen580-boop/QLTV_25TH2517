<?php
// Danh sách sách: tìm kiếm theo mã/tên/tác giả, lọc theo thể loại, phân trang.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

$soDongMoiTrang = 10;

// ---- 1. Đọc điều kiện lọc từ URL (ép kiểu, không tin dữ liệu người dùng) ----
$tuKhoa    = layChuoiGet('q');
$idTheLoai = filter_var(layChuoiGet('theloai'), FILTER_VALIDATE_INT);
$idTheLoai = ($idTheLoai !== false && $idTheLoai > 0) ? $idTheLoai : 0;   // 0 = tất cả thể loại
$pageNum   = filter_var(layChuoiGet('page'), FILTER_VALIDATE_INT);
$pageNum   = ($pageNum !== false && $pageNum > 0) ? $pageNum : 1;

// ---- 2. Ghép mệnh đề WHERE theo điều kiện nào đang có ----
$dieuKien = [];
$kieu     = '';
$thamSo   = [];

if ($tuKhoa !== '') {
    // Ký tự % và _ có nghĩa đặc biệt trong LIKE nên phải thoát trước khi ghép
    $mau        = '%' . addcslashes($tuKhoa, '%_\\') . '%';
    $dieuKien[] = '(s.ma_sach LIKE ? OR s.ten_sach LIKE ? OR s.tac_gia LIKE ?)';
    $kieu      .= 'sss';
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
    $thamSo[]   = $mau;
}
if ($idTheLoai > 0) {
    $dieuKien[] = 's.id_theloai = ?';
    $kieu      .= 'i';
    $thamSo[]   = $idTheLoai;
}
$menhDeWhere = count($dieuKien) > 0 ? 'WHERE ' . implode(' AND ', $dieuKien) : '';

// ---- 3. Đếm tổng số dòng khớp để tính số trang ----
$stmt    = truyVan($conn, "SELECT COUNT(*) FROM sach s $menhDeWhere", $kieu, $thamSo);
$numRows = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
mysqli_stmt_close($stmt);

$maxPage = max(1, (int) ceil($numRows / $soDongMoiTrang));
$pageNum = min($pageNum, $maxPage);                    // không vượt quá trang cuối
$offset  = ($pageNum - 1) * $soDongMoiTrang;           // vị trí dòng đầu của trang

// ---- 4. Lấy đúng các dòng của trang hiện tại ----
$sql = "SELECT s.id, s.ma_sach, s.ten_sach, s.tac_gia, s.gia_tien, s.so_luong, s.so_con_lai,
               tl.ten_theloai, nxb.ten_nxb
        FROM sach s
        JOIN theloai tl    ON tl.id = s.id_theloai
        JOIN nhaxuatban nxb ON nxb.id = s.id_nxb
        $menhDeWhere
        ORDER BY s.ma_sach
        LIMIT ? OFFSET ?";
$stmt = truyVan(
    $conn,
    $sql,
    $kieu . 'ii',
    array_merge($thamSo, [$soDongMoiTrang, $offset])
);
$ketQua = mysqli_stmt_get_result($stmt);
$dsSach = mysqli_fetch_all($ketQua, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$dsTheLoai = mysqli_fetch_all(
    mysqli_query($conn, 'SELECT id, ten_theloai FROM theloai ORDER BY ten_theloai'),
    MYSQLI_ASSOC
);

// Tạo liên kết tới một trang, giữ nguyên từ khóa và thể loại đang lọc
function lienKetTrang(int $page, string $tuKhoa, int $idTheLoai): string
{
    $thamSoUrl = ['page' => $page];
    if ($tuKhoa !== '') {
        $thamSoUrl['q'] = $tuKhoa;
    }
    if ($idTheLoai > 0) {
        $thamSoUrl['theloai'] = $idTheLoai;
    }
    return '?' . e(http_build_query($thamSoUrl));
}

$tieuDe      = 'Quản lý sách';
$menuHienTai = 'sach';
require __DIR__ . '/../includes/admin-dau.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Quản lý sách</h1>
    <a href="sach-form.php" class="btn btn-primary">+ Thêm sách</a>
</div>

<!-- Form tìm kiếm dùng GET nên đường link tìm kiếm có thể chép/gửi cho người khác -->
<form method="get" class="row g-2 mb-3">
    <div class="col-12 col-md-6">
        <input type="text" class="form-control" name="q" value="<?= e($tuKhoa) ?>"
               placeholder="Tìm theo mã sách, tên sách hoặc tác giả">
    </div>
    <div class="col-8 col-md-4">
        <select class="form-select" name="theloai">
            <option value="0">Tất cả thể loại</option>
            <?php foreach ($dsTheLoai as $theLoai): ?>
                <option value="<?= (int) $theLoai['id'] ?>"<?= ((int) $theLoai['id'] === $idTheLoai) ? ' selected' : '' ?>>
                    <?= e($theLoai['ten_theloai']) ?>
                </option>
            <?php endforeach; ?>
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
                    <th>Mã</th>
                    <th>Tên sách</th>
                    <th>Tác giả</th>
                    <th>Thể loại</th>
                    <th class="text-end">Giá</th>
                    <th class="text-center">Còn / Tổng</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dsSach as $sach): ?>
                <tr>
                    <td><?= e($sach['ma_sach']) ?></td>
                    <td><?= e($sach['ten_sach']) ?></td>
                    <td><?= e($sach['tac_gia']) ?></td>
                    <td><?= e($sach['ten_theloai']) ?></td>
                    <td class="text-end"><?= e(number_format((float) $sach['gia_tien'], 0, ',', '.')) ?>đ</td>
                    <td class="text-center">
                        <?php if ((int) $sach['so_con_lai'] === 0): ?>
                            <span class="badge text-bg-danger">Hết (0/<?= (int) $sach['so_luong'] ?>)</span>
                        <?php else: ?>
                            <?= (int) $sach['so_con_lai'] ?> / <?= (int) $sach['so_luong'] ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="sach-form.php?id=<?= (int) $sach['id'] ?>" class="btn btn-sm btn-outline-secondary">Sửa</a>
                        <form method="post" action="sach-xoa.php" class="d-inline"
                              onsubmit="return confirm('Xóa cuốn sách này?');">
                            <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $sach['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($numRows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Không có sách nào khớp.</td></tr>
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
                <a class="page-link" href="<?= lienKetTrang(max(1, $pageNum - 1), $tuKhoa, $idTheLoai) ?>">Trước</a>
            </li>
            <?php for ($i = max(1, $pageNum - 2); $i <= min($maxPage, $pageNum + 2); $i++): ?>
                <li class="page-item<?= $i === $pageNum ? ' active' : '' ?>">
                    <a class="page-link" href="<?= lienKetTrang($i, $tuKhoa, $idTheLoai) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item<?= $pageNum >= $maxPage ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= lienKetTrang(min($maxPage, $pageNum + 1), $tuKhoa, $idTheLoai) ?>">Sau</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

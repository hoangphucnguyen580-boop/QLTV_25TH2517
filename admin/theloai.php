<?php
// Quản lý thể loại: danh sách và form thêm/sửa nằm trên cùng một trang.
//   theloai.php            -> form thêm mới
//   theloai.php?sua=ID     -> form sửa thể loại có id đó
//   POST hanh_dong=luu     -> thêm (không có id) hoặc cập nhật (có id)
//   POST hanh_dong=xoa     -> xóa; chặn nếu còn sách thuộc thể loại này
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

$idSua  = 0;                          // 0 = đang thêm mới, > 0 = đang sửa thể loại có id này
$giaTri = ['ten_theloai' => ''];
$loi    = [];                         // lỗi theo từng ô: $loi['ten_theloai'] = '...'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();
    $hanhDong = layChuoiPost('hanh_dong');

    // ---------------- Xóa ----------------
    if ($hanhDong === 'xoa') {
        $idXoa = filter_var(layChuoiPost('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idXoa === false) {
            datThongBao('danger', 'Mã thể loại không hợp lệ.');
            header('Location: theloai.php');
            exit;
        }

        $stmt    = truyVan($conn, 'SELECT ten_theloai FROM theloai WHERE id = ?', 'i', [$idXoa]);
        $theLoai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$theLoai) {
            datThongBao('danger', 'Thể loại này không còn tồn tại.');
            header('Location: theloai.php');
            exit;
        }

        $stmt   = truyVan($conn, 'SELECT COUNT(*) FROM sach WHERE id_theloai = ?', 'i', [$idXoa]);
        $soSach = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
        mysqli_stmt_close($stmt);
        if ($soSach > 0) {
            datThongBao('warning', 'Không xóa được "' . $theLoai['ten_theloai'] . '": còn ' . $soSach
                . ' đầu sách thuộc thể loại này. Hãy chuyển các sách đó sang thể loại khác trước.');
            header('Location: theloai.php');
            exit;
        }

        try {
            $stmt = truyVan($conn, 'DELETE FROM theloai WHERE id = ?', 'i', [$idXoa]);
            mysqli_stmt_close($stmt);
            datThongBao('success', 'Đã xóa thể loại "' . $theLoai['ten_theloai'] . '".');
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() === 1451) {
                // 1451 = vừa có sách được gán vào thể loại này giữa hai bước kiểm tra
                datThongBao('warning', 'Không xóa được "' . $theLoai['ten_theloai'] . '": thể loại đang được dùng ở nơi khác.');
            } else {
                throw $ngoaiLe;
            }
        }
        header('Location: theloai.php');
        exit;
    }

    // ---------------- Thêm / sửa ----------------
    if ($hanhDong !== 'luu') {
        datThongBao('danger', 'Yêu cầu không hợp lệ.');
        header('Location: theloai.php');
        exit;
    }

    // Có id gửi lên nghĩa là đang sửa: id phải hợp lệ và thể loại đó phải còn tồn tại
    $idGui = layChuoiPost('id');
    if ($idGui !== '') {
        $idSua = filter_var($idGui, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idSua !== false) {
            $stmt = truyVan($conn, 'SELECT id FROM theloai WHERE id = ?', 'i', [$idSua]);
            $tonTai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }
        if ($idSua === false || !$tonTai) {
            datThongBao('danger', 'Không tìm thấy thể loại cần sửa.');
            header('Location: theloai.php');
            exit;
        }
    }

    $giaTri['ten_theloai'] = layChuoiPost('ten_theloai');
    if ($giaTri['ten_theloai'] === '') {
        $loi['ten_theloai'] = 'Vui lòng nhập tên thể loại.';
    } elseif (doDaiChuoi($giaTri['ten_theloai']) > 100) {
        $loi['ten_theloai'] = 'Tên thể loại tối đa 100 ký tự.';
    }

    if (count($loi) === 0) {
        try {
            if ($idSua > 0) {
                $stmt = truyVan($conn, 'UPDATE theloai SET ten_theloai = ? WHERE id = ?', 'si', [$giaTri['ten_theloai'], $idSua]);
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã cập nhật thể loại "' . $giaTri['ten_theloai'] . '".');
            } else {
                $stmt = truyVan($conn, 'INSERT INTO theloai (ten_theloai) VALUES (?)', 's', [$giaTri['ten_theloai']]);
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã thêm thể loại "' . $giaTri['ten_theloai'] . '".');
            }
            header('Location: theloai.php');
            exit;
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() === 1062) {
                // 1062 = trùng cột UNIQUE. Cột dùng collation utf8mb4_unicode_ci nên "van hoc" cũng bị coi là trùng "Văn học".
                $loi['ten_theloai'] = 'Thể loại này đã tồn tại (không phân biệt hoa thường và dấu).';
            } else {
                throw $ngoaiLe;
            }
        }
    }
    // Có lỗi thì không chuyển trang: rơi xuống phần hiển thị bên dưới, form giữ nguyên chế độ thêm/sửa và nội dung đã nhập
} elseif (isset($_GET['sua'])) {
    $idSua = filter_var(layChuoiGet('sua'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $hang  = null;
    if ($idSua !== false) {
        $stmt = truyVan($conn, 'SELECT ten_theloai FROM theloai WHERE id = ?', 'i', [$idSua]);
        $hang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
    if (!$hang) {
        datThongBao('danger', 'Không tìm thấy thể loại cần sửa.');
        header('Location: theloai.php');
        exit;
    }
    $giaTri['ten_theloai'] = $hang['ten_theloai'];
}

// ---- Danh sách thể loại kèm số sách đang thuộc thể loại ----
$dsTheLoai = mysqli_fetch_all(
    mysqli_query(
        $conn,
        'SELECT tl.id, tl.ten_theloai, COUNT(s.id) AS so_sach
         FROM theloai tl
         LEFT JOIN sach s ON s.id_theloai = tl.id
         GROUP BY tl.id, tl.ten_theloai
         ORDER BY tl.ten_theloai'
    ),
    MYSQLI_ASSOC
);

$tieuDe      = 'Quản lý thể loại';
$menuHienTai = 'theloai';
require __DIR__ . '/../includes/admin-dau.php';
?>
<h1 class="h3 mb-3">Quản lý thể loại</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="h5 mb-3"><?= $idSua > 0 ? 'Sửa thể loại' : 'Thêm thể loại' ?></h2>

                <!-- Không đặt action: form gửi về chính URL hiện tại. id nằm trong ô ẩn nên không phụ thuộc vào ?sua= -->
                <form method="post" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                    <input type="hidden" name="hanh_dong" value="luu">
                    <?php if ($idSua > 0): ?>
                        <input type="hidden" name="id" value="<?= (int) $idSua ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="ten_theloai" class="form-label">Tên thể loại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?= lopLoi($loi, 'ten_theloai') ?>" id="ten_theloai"
                               name="ten_theloai" value="<?= e($giaTri['ten_theloai']) ?>" maxlength="100">
                        <?php hienLoi($loi, 'ten_theloai'); ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><img class="ic" src="../assets/icons/trang/check-circle.svg" alt="" width="16" height="16">Lưu</button>
                        <?php if ($idSua > 0): ?>
                            <a href="theloai.php" class="btn btn-outline-secondary">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tên thể loại</th>
                            <th class="text-center">Số sách</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dsTheLoai as $theLoai): ?>
                        <tr<?= ((int) $theLoai['id'] === $idSua) ? ' class="table-active"' : '' ?>>
                            <td><?= e($theLoai['ten_theloai']) ?></td>
                            <td class="text-center">
                                <?php if ((int) $theLoai['so_sach'] > 0): ?>
                                    <a href="sach.php?theloai=<?= (int) $theLoai['id'] ?>"><?= (int) $theLoai['so_sach'] ?></a>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="theloai.php?sua=<?= (int) $theLoai['id'] ?>" class="btn btn-sm btn-outline-secondary"><img class="ic" src="../assets/icons/den/pencil-square.svg" alt="" width="14" height="14">Sửa</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xóa thể loại này?');">
                                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                                    <input type="hidden" name="hanh_dong" value="xoa">
                                    <input type="hidden" name="id" value="<?= (int) $theLoai['id'] ?>">
                                    <?php if ((int) $theLoai['so_sach'] > 0): ?>
                                        <!-- Chỉ để người dùng biết trước; phía máy chủ vẫn kiểm tra lại khi xóa -->
                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled
                                                title="Còn sách thuộc thể loại này"><img class="ic" src="../assets/icons/den/trash.svg" alt="" width="14" height="14">Xóa</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><img class="ic" src="../assets/icons/den/trash.svg" alt="" width="14" height="14">Xóa</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($dsTheLoai) === 0): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Chưa có thể loại nào.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

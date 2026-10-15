<?php
// Quản lý nhà xuất bản: danh sách và form thêm/sửa nằm trên cùng một trang.
//   nhaxuatban.php            -> form thêm mới
//   nhaxuatban.php?sua=ID     -> form sửa nhà xuất bản có id đó
//   POST hanh_dong=luu        -> thêm (không có id) hoặc cập nhật (có id)
//   POST hanh_dong=xoa        -> xóa; chặn nếu còn sách thuộc nhà xuất bản này
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

$idSua  = 0;                          // 0 = đang thêm mới, > 0 = đang sửa nhà xuất bản có id này
$giaTri = ['ten_nxb' => '', 'dia_chi' => ''];
$loi    = [];                         // lỗi theo từng ô: $loi['ten_nxb'] = '...'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();
    $hanhDong = layChuoiPost('hanh_dong');

    // ---------------- Xóa ----------------
    if ($hanhDong === 'xoa') {
        $idXoa = filter_var(layChuoiPost('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idXoa === false) {
            datThongBao('danger', 'Mã nhà xuất bản không hợp lệ.');
            header('Location: nhaxuatban.php');
            exit;
        }

        $stmt = truyVan($conn, 'SELECT ten_nxb FROM nhaxuatban WHERE id = ?', 'i', [$idXoa]);
        $nxb  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$nxb) {
            datThongBao('danger', 'Nhà xuất bản này không còn tồn tại.');
            header('Location: nhaxuatban.php');
            exit;
        }

        $stmt   = truyVan($conn, 'SELECT COUNT(*) FROM sach WHERE id_nxb = ?', 'i', [$idXoa]);
        $soSach = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
        mysqli_stmt_close($stmt);
        if ($soSach > 0) {
            datThongBao('warning', 'Không xóa được "' . $nxb['ten_nxb'] . '": còn ' . $soSach
                . ' đầu sách thuộc nhà xuất bản này. Hãy chuyển các sách đó sang nhà xuất bản khác trước.');
            header('Location: nhaxuatban.php');
            exit;
        }

        try {
            $stmt = truyVan($conn, 'DELETE FROM nhaxuatban WHERE id = ?', 'i', [$idXoa]);
            mysqli_stmt_close($stmt);
            datThongBao('success', 'Đã xóa nhà xuất bản "' . $nxb['ten_nxb'] . '".');
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() === 1451) {
                // 1451 = vừa có sách được gán vào nhà xuất bản này giữa hai bước kiểm tra
                datThongBao('warning', 'Không xóa được "' . $nxb['ten_nxb'] . '": nhà xuất bản đang được dùng ở nơi khác.');
            } else {
                throw $ngoaiLe;
            }
        }
        header('Location: nhaxuatban.php');
        exit;
    }

    // ---------------- Thêm / sửa ----------------
    if ($hanhDong !== 'luu') {
        datThongBao('danger', 'Yêu cầu không hợp lệ.');
        header('Location: nhaxuatban.php');
        exit;
    }

    // Có id gửi lên nghĩa là đang sửa: id phải hợp lệ và nhà xuất bản đó phải còn tồn tại
    $idGui = layChuoiPost('id');
    if ($idGui !== '') {
        $idSua = filter_var($idGui, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idSua !== false) {
            $stmt = truyVan($conn, 'SELECT id FROM nhaxuatban WHERE id = ?', 'i', [$idSua]);
            $tonTai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }
        if ($idSua === false || !$tonTai) {
            datThongBao('danger', 'Không tìm thấy nhà xuất bản cần sửa.');
            header('Location: nhaxuatban.php');
            exit;
        }
    }

    $giaTri['ten_nxb'] = layChuoiPost('ten_nxb');
    $giaTri['dia_chi'] = layChuoiPost('dia_chi');

    if ($giaTri['ten_nxb'] === '') {
        $loi['ten_nxb'] = 'Vui lòng nhập tên nhà xuất bản.';
    } elseif (doDaiChuoi($giaTri['ten_nxb']) > 150) {
        $loi['ten_nxb'] = 'Tên nhà xuất bản tối đa 150 ký tự.';
    }
    if (doDaiChuoi($giaTri['dia_chi']) > 255) {
        $loi['dia_chi'] = 'Địa chỉ tối đa 255 ký tự.';
    }

    if (count($loi) === 0) {
        $diaChi = ($giaTri['dia_chi'] === '') ? null : $giaTri['dia_chi'];   // để trống thì lưu NULL
        try {
            if ($idSua > 0) {
                $stmt = truyVan(
                    $conn,
                    'UPDATE nhaxuatban SET ten_nxb = ?, dia_chi = ? WHERE id = ?',
                    'ssi',
                    [$giaTri['ten_nxb'], $diaChi, $idSua]
                );
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã cập nhật nhà xuất bản "' . $giaTri['ten_nxb'] . '".');
            } else {
                $stmt = truyVan(
                    $conn,
                    'INSERT INTO nhaxuatban (ten_nxb, dia_chi) VALUES (?, ?)',
                    'ss',
                    [$giaTri['ten_nxb'], $diaChi]
                );
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã thêm nhà xuất bản "' . $giaTri['ten_nxb'] . '".');
            }
            header('Location: nhaxuatban.php');
            exit;
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() === 1062) {
                // 1062 = trùng cột UNIQUE (ten_nxb). Collation utf8mb4_unicode_ci không phân biệt hoa thường và dấu.
                $loi['ten_nxb'] = 'Nhà xuất bản này đã tồn tại (không phân biệt hoa thường và dấu).';
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
        $stmt = truyVan($conn, 'SELECT ten_nxb, dia_chi FROM nhaxuatban WHERE id = ?', 'i', [$idSua]);
        $hang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
    if (!$hang) {
        datThongBao('danger', 'Không tìm thấy nhà xuất bản cần sửa.');
        header('Location: nhaxuatban.php');
        exit;
    }
    $giaTri['ten_nxb'] = $hang['ten_nxb'];
    $giaTri['dia_chi'] = (string) ($hang['dia_chi'] ?? '');
}

// ---- Danh sách nhà xuất bản kèm số sách đang thuộc nhà xuất bản ----
$dsNxb = mysqli_fetch_all(
    mysqli_query(
        $conn,
        'SELECT n.id, n.ten_nxb, n.dia_chi, COUNT(s.id) AS so_sach
         FROM nhaxuatban n
         LEFT JOIN sach s ON s.id_nxb = n.id
         GROUP BY n.id, n.ten_nxb, n.dia_chi
         ORDER BY n.ten_nxb'
    ),
    MYSQLI_ASSOC
);

$tieuDe      = 'Quản lý nhà xuất bản';
$menuHienTai = 'nhaxuatban';
require __DIR__ . '/../includes/admin-dau.php';
?>
<h1 class="h3 mb-3">Quản lý nhà xuất bản</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $idSua > 0 ? 'Sửa nhà xuất bản' : 'Thêm nhà xuất bản' ?></h2>

                <!-- Không đặt action: form gửi về chính URL hiện tại. id nằm trong ô ẩn nên không phụ thuộc vào ?sua= -->
                <form method="post" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                    <input type="hidden" name="hanh_dong" value="luu">
                    <?php if ($idSua > 0): ?>
                        <input type="hidden" name="id" value="<?= (int) $idSua ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="ten_nxb" class="form-label">Tên nhà xuất bản <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?= lopLoi($loi, 'ten_nxb') ?>" id="ten_nxb"
                               name="ten_nxb" value="<?= e($giaTri['ten_nxb']) ?>" maxlength="150">
                        <?php hienLoi($loi, 'ten_nxb'); ?>
                    </div>
                    <div class="mb-3">
                        <label for="dia_chi" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control<?= lopLoi($loi, 'dia_chi') ?>" id="dia_chi"
                               name="dia_chi" value="<?= e($giaTri['dia_chi']) ?>" maxlength="255">
                        <?php hienLoi($loi, 'dia_chi'); ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Lưu</button>
                        <?php if ($idSua > 0): ?>
                            <a href="nhaxuatban.php" class="btn btn-outline-secondary">Hủy</a>
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
                            <th>Tên nhà xuất bản</th>
                            <th>Địa chỉ</th>
                            <th class="text-center">Số sách</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dsNxb as $nxb): ?>
                        <tr<?= ((int) $nxb['id'] === $idSua) ? ' class="table-active"' : '' ?>>
                            <td><?= e($nxb['ten_nxb']) ?></td>
                            <td><?= e($nxb['dia_chi'] ?? '') ?></td>
                            <td class="text-center"><?= (int) $nxb['so_sach'] ?></td>
                            <td class="text-end text-nowrap">
                                <a href="nhaxuatban.php?sua=<?= (int) $nxb['id'] ?>" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xóa nhà xuất bản này?');">
                                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">
                                    <input type="hidden" name="hanh_dong" value="xoa">
                                    <input type="hidden" name="id" value="<?= (int) $nxb['id'] ?>">
                                    <?php if ((int) $nxb['so_sach'] > 0): ?>
                                        <!-- Chỉ để người dùng biết trước; phía máy chủ vẫn kiểm tra lại khi xóa -->
                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled
                                                title="Còn sách thuộc nhà xuất bản này">Xóa</button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($dsNxb) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Chưa có nhà xuất bản nào.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

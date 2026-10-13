<?php
// Form thêm sách mới (không có ?id=) và sửa sách (có ?id=...). Cả hai dùng chung một trang.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

// ---- 1. Xác định đang thêm hay sửa ----
$dangSua    = isset($_GET['id']);
$idSach     = 0;
$sachHienTai = null;
$dangMuon   = 0;   // số bản của sách này đang nằm ngoài kệ (chưa trả)

if ($dangSua) {
    $idSach = filter_var(layChuoiGet('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idSach !== false) {
        $stmt = truyVan($conn, 'SELECT * FROM sach WHERE id = ?', 'i', [$idSach]);
        $sachHienTai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
    if (!$sachHienTai) {
        datThongBao('danger', 'Không tìm thấy cuốn sách cần sửa.');
        header('Location: sach.php');
        exit;
    }
    $stmt = truyVan(
        $conn,
        'SELECT COUNT(*) FROM chitietphieumuon WHERE id_sach = ? AND ngay_tra_thuc_te IS NULL',
        'i',
        [$idSach]
    );
    $dangMuon = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
    mysqli_stmt_close($stmt);
}

// ---- 2. Dữ liệu cho hai ô chọn ----
$dsTheLoai = mysqli_fetch_all(mysqli_query($conn, 'SELECT id, ten_theloai FROM theloai ORDER BY ten_theloai'), MYSQLI_ASSOC);
$dsNxb     = mysqli_fetch_all(mysqli_query($conn, 'SELECT id, ten_nxb FROM nhaxuatban ORDER BY ten_nxb'), MYSQLI_ASSOC);
$idTheLoaiHopLe = array_map('intval', array_column($dsTheLoai, 'id'));
$idNxbHopLe     = array_map('intval', array_column($dsNxb, 'id'));

// ---- 3. Giá trị hiện trong các ô (dạng chuỗi) ----
$cacTruong = ['ma_sach', 'ten_sach', 'tac_gia', 'id_theloai', 'id_nxb', 'nam_xuat_ban', 'so_trang', 'gia_tien', 'so_luong', 'mo_ta'];
$giaTri    = array_fill_keys($cacTruong, '');
$giaTri['so_luong'] = '1';
$giaTri['gia_tien'] = '0';
if ($dangSua) {
    foreach ($cacTruong as $truong) {
        $giaTri[$truong] = (string) ($sachHienTai[$truong] ?? '');
    }
}

// ---- 4. Xử lý khi bấm Lưu ----
$loi = [];   // lỗi theo từng ô: $loi['ten_sach'] = '...'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();

    // Giữ lại những gì người dùng vừa nhập để hiện lại nếu có lỗi (sticky form)
    foreach ($cacTruong as $truong) {
        $giaTri[$truong] = layChuoiPost($truong);
    }

    // Mã sách
    if ($giaTri['ma_sach'] === '') {
        $loi['ma_sach'] = 'Vui lòng nhập mã sách.';
    } elseif (doDaiChuoi($giaTri['ma_sach']) > 20) {
        $loi['ma_sach'] = 'Mã sách tối đa 20 ký tự.';
    } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $giaTri['ma_sach'])) {
        $loi['ma_sach'] = 'Mã sách chỉ gồm chữ không dấu, số, gạch ngang và gạch dưới.';
    }

    // Tên sách, tác giả
    if ($giaTri['ten_sach'] === '') {
        $loi['ten_sach'] = 'Vui lòng nhập tên sách.';
    } elseif (doDaiChuoi($giaTri['ten_sach']) > 200) {
        $loi['ten_sach'] = 'Tên sách tối đa 200 ký tự.';
    }
    if ($giaTri['tac_gia'] === '') {
        $loi['tac_gia'] = 'Vui lòng nhập tác giả.';
    } elseif (doDaiChuoi($giaTri['tac_gia']) > 150) {
        $loi['tac_gia'] = 'Tác giả tối đa 150 ký tự.';
    }

    // Thể loại, nhà xuất bản: phải là số và phải thuộc danh sách có thật
    $idTheLoai = filter_var($giaTri['id_theloai'], FILTER_VALIDATE_INT);
    if ($idTheLoai === false || !in_array($idTheLoai, $idTheLoaiHopLe, true)) {
        $loi['id_theloai'] = 'Vui lòng chọn thể loại.';
    }
    $idNxb = filter_var($giaTri['id_nxb'], FILTER_VALIDATE_INT);
    if ($idNxb === false || !in_array($idNxb, $idNxbHopLe, true)) {
        $loi['id_nxb'] = 'Vui lòng chọn nhà xuất bản.';
    }

    // Năm xuất bản, số trang: không bắt buộc (để trống = NULL)
    $namXuatBan = null;
    if ($giaTri['nam_xuat_ban'] !== '') {
        $namToiDa   = (int) date('Y') + 1;
        $namXuatBan = filter_var($giaTri['nam_xuat_ban'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => $namToiDa]]);
        if ($namXuatBan === false) {
            $loi['nam_xuat_ban'] = "Năm xuất bản phải là số từ 1000 đến $namToiDa.";
        }
    }
    $soTrang = null;
    if ($giaTri['so_trang'] !== '') {
        $soTrang = filter_var($giaTri['so_trang'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($soTrang === false) {
            $loi['so_trang'] = 'Số trang phải là số nguyên từ 1 đến 65535.';
        }
    }

    // Giá tiền (dùng để tính bồi thường)
    $giaTien = filter_var($giaTri['gia_tien'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000000]]);
    if ($giaTien === false) {
        $loi['gia_tien'] = 'Giá tiền phải là số nguyên không âm (đơn vị đồng).';
    }

    // Tổng số bản
    $soLuong = filter_var($giaTri['so_luong'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    if ($soLuong === false) {
        $loi['so_luong'] = 'Số lượng phải là số nguyên từ 1 đến 10000.';
    } elseif ($soLuong < $dangMuon) {
        $loi['so_luong'] = "Đang có $dangMuon bản được mượn, tổng số bản không thể nhỏ hơn $dangMuon.";
    }

    if (doDaiChuoi($giaTri['mo_ta']) > 2000) {
        $loi['mo_ta'] = 'Mô tả tối đa 2000 ký tự.';
    }

    // ---- Không có lỗi nào thì ghi vào CSDL ----
    if (count($loi) === 0) {
        $moTa     = ($giaTri['mo_ta'] === '') ? null : $giaTri['mo_ta'];
        $soConLai = $soLuong - $dangMuon;   // thêm mới thì $dangMuon = 0 nên còn lại = tổng

        try {
            if ($dangSua) {
                $sql = 'UPDATE sach SET ma_sach = ?, ten_sach = ?, tac_gia = ?, id_theloai = ?, id_nxb = ?,
                               nam_xuat_ban = ?, so_trang = ?, gia_tien = ?, so_luong = ?, so_con_lai = ?, mo_ta = ?
                        WHERE id = ?';
                $stmt = truyVan($conn, $sql, 'sssiiiiiiisi', [
                    $giaTri['ma_sach'], $giaTri['ten_sach'], $giaTri['tac_gia'], $idTheLoai, $idNxb,
                    $namXuatBan, $soTrang, $giaTien, $soLuong, $soConLai, $moTa, $idSach,
                ]);
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã cập nhật sách "' . $giaTri['ten_sach'] . '".');
            } else {
                $sql = 'INSERT INTO sach (ma_sach, ten_sach, tac_gia, id_theloai, id_nxb,
                               nam_xuat_ban, so_trang, gia_tien, so_luong, so_con_lai, mo_ta)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = truyVan($conn, $sql, 'sssiiiiiiis', [
                    $giaTri['ma_sach'], $giaTri['ten_sach'], $giaTri['tac_gia'], $idTheLoai, $idNxb,
                    $namXuatBan, $soTrang, $giaTien, $soLuong, $soConLai, $moTa,
                ]);
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã thêm sách "' . $giaTri['ten_sach'] . '".');
            }
            header('Location: sach.php');
            exit;
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() === 1062) {
                // 1062 = trùng giá trị ở cột UNIQUE (ở đây chỉ có thể là ma_sach)
                $loi['ma_sach'] = 'Mã sách này đã tồn tại.';
            } else {
                throw $ngoaiLe;   // lỗi khác thì không nuốt, để lộ ra mà sửa
            }
        }
    }
}

$tieuDe      = $dangSua ? 'Sửa sách' : 'Thêm sách';
$menuHienTai = 'sach';
require __DIR__ . '/../includes/admin-dau.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="sach.php">Quản lý sách</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($tieuDe) ?></li>
    </ol>
</nav>

<div class="card">
    <div class="card-body">
        <h1 class="h4 mb-4"><?= e($tieuDe) ?></h1>

        <?php if (count($loi) > 0): ?>
            <div class="alert alert-danger">Chưa lưu được. Hãy sửa các ô đang viền đỏ rồi bấm Lưu lại.</div>
        <?php endif; ?>

        <!-- Không đặt action: form gửi về chính URL hiện tại (giữ nguyên ?id=...) -->
        <form method="post" novalidate>
            <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="ma_sach" class="form-label">Mã sách <span class="text-danger">*</span></label>
                    <input type="text" class="form-control<?= lopLoi($loi, 'ma_sach') ?>" id="ma_sach" name="ma_sach"
                           value="<?= e($giaTri['ma_sach']) ?>" maxlength="20">
                    <?php hienLoi($loi, 'ma_sach'); ?>
                </div>
                <div class="col-md-8">
                    <label for="ten_sach" class="form-label">Tên sách <span class="text-danger">*</span></label>
                    <input type="text" class="form-control<?= lopLoi($loi, 'ten_sach') ?>" id="ten_sach" name="ten_sach"
                           value="<?= e($giaTri['ten_sach']) ?>" maxlength="200">
                    <?php hienLoi($loi, 'ten_sach'); ?>
                </div>

                <div class="col-md-6">
                    <label for="tac_gia" class="form-label">Tác giả <span class="text-danger">*</span></label>
                    <input type="text" class="form-control<?= lopLoi($loi, 'tac_gia') ?>" id="tac_gia" name="tac_gia"
                           value="<?= e($giaTri['tac_gia']) ?>" maxlength="150">
                    <?php hienLoi($loi, 'tac_gia'); ?>
                </div>
                <div class="col-md-3">
                    <label for="id_theloai" class="form-label">Thể loại <span class="text-danger">*</span></label>
                    <select class="form-select<?= lopLoi($loi, 'id_theloai') ?>" id="id_theloai" name="id_theloai">
                        <option value="">-- Chọn --</option>
                        <?php foreach ($dsTheLoai as $theLoai): ?>
                            <option value="<?= (int) $theLoai['id'] ?>"<?= ((string) $theLoai['id'] === $giaTri['id_theloai']) ? ' selected' : '' ?>>
                                <?= e($theLoai['ten_theloai']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php hienLoi($loi, 'id_theloai'); ?>
                </div>
                <div class="col-md-3">
                    <label for="id_nxb" class="form-label">Nhà xuất bản <span class="text-danger">*</span></label>
                    <select class="form-select<?= lopLoi($loi, 'id_nxb') ?>" id="id_nxb" name="id_nxb">
                        <option value="">-- Chọn --</option>
                        <?php foreach ($dsNxb as $nxb): ?>
                            <option value="<?= (int) $nxb['id'] ?>"<?= ((string) $nxb['id'] === $giaTri['id_nxb']) ? ' selected' : '' ?>>
                                <?= e($nxb['ten_nxb']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php hienLoi($loi, 'id_nxb'); ?>
                </div>

                <div class="col-6 col-md-3">
                    <label for="nam_xuat_ban" class="form-label">Năm xuất bản</label>
                    <input type="text" inputmode="numeric" class="form-control<?= lopLoi($loi, 'nam_xuat_ban') ?>"
                           id="nam_xuat_ban" name="nam_xuat_ban" value="<?= e($giaTri['nam_xuat_ban']) ?>">
                    <?php hienLoi($loi, 'nam_xuat_ban'); ?>
                </div>
                <div class="col-6 col-md-3">
                    <label for="so_trang" class="form-label">Số trang</label>
                    <input type="text" inputmode="numeric" class="form-control<?= lopLoi($loi, 'so_trang') ?>"
                           id="so_trang" name="so_trang" value="<?= e($giaTri['so_trang']) ?>">
                    <?php hienLoi($loi, 'so_trang'); ?>
                </div>
                <div class="col-6 col-md-3">
                    <label for="gia_tien" class="form-label">Giá tiền (đồng) <span class="text-danger">*</span></label>
                    <input type="text" inputmode="numeric" class="form-control<?= lopLoi($loi, 'gia_tien') ?>"
                           id="gia_tien" name="gia_tien" value="<?= e($giaTri['gia_tien']) ?>">
                    <?php hienLoi($loi, 'gia_tien'); ?>
                </div>
                <div class="col-6 col-md-3">
                    <label for="so_luong" class="form-label">Tổng số bản <span class="text-danger">*</span></label>
                    <input type="text" inputmode="numeric" class="form-control<?= lopLoi($loi, 'so_luong') ?>"
                           id="so_luong" name="so_luong" value="<?= e($giaTri['so_luong']) ?>">
                    <?php hienLoi($loi, 'so_luong'); ?>
                    <?php if ($dangSua): ?>
                        <div class="form-text">Đang có <?= $dangMuon ?> bản được mượn.</div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="mo_ta" class="form-label">Mô tả</label>
                    <textarea class="form-control<?= lopLoi($loi, 'mo_ta') ?>" id="mo_ta" name="mo_ta" rows="3"><?= e($giaTri['mo_ta']) ?></textarea>
                    <?php hienLoi($loi, 'mo_ta'); ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Lưu</button>
                <a href="sach.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

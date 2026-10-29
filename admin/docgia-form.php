<?php
// Form thêm độc giả mới (không có ?id=) và sửa độc giả (có ?id=...). Cả hai dùng chung một trang.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

// ---- 1. Xác định đang thêm hay sửa ----
$dangSua       = isset($_GET['id']);
$idDocGia      = 0;
$docGiaHienTai = null;
$dangMuon      = 0;   // số cuốn độc giả này đang giữ (chưa trả)

if ($dangSua) {
    $idDocGia = filter_var(layChuoiGet('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idDocGia !== false) {
        $stmt = truyVan($conn, 'SELECT * FROM docgia WHERE id = ?', 'i', [$idDocGia]);
        $docGiaHienTai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
    if (!$docGiaHienTai) {
        datThongBao('danger', 'Không tìm thấy độc giả cần sửa.');
        header('Location: docgia.php');
        exit;
    }
    $stmt = truyVan(
        $conn,
        'SELECT COUNT(*)
         FROM chitietphieumuon ct
         JOIN phieumuon pm ON pm.id = ct.id_phieu_muon
         WHERE pm.id_doc_gia = ? AND ct.ngay_tra_thuc_te IS NULL',
        'i',
        [$idDocGia]
    );
    $dangMuon = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
    mysqli_stmt_close($stmt);
}

// ---- 2. Dữ liệu cho các ô chọn ----
$dsLoai = mysqli_fetch_all(
    mysqli_query($conn, 'SELECT id, ten_loai, so_sach_toi_da, so_ngay_muon FROM loaidocgia ORDER BY id'),
    MYSQLI_ASSOC
);
$soSachToiDaTheoLoai = [];   // [id loại => số sách tối đa], dùng để kiểm tra và biết id nào hợp lệ
foreach ($dsLoai as $loai) {
    $soSachToiDaTheoLoai[(int) $loai['id']] = (int) $loai['so_sach_toi_da'];
}
$dsGioiTinh = ['nam' => 'Nam', 'nu' => 'Nữ', 'khac' => 'Khác'];
$dsTrangThai = ['hoat_dong' => 'Hoạt động', 'khoa' => 'Đã khóa'];

// ---- 3. Giá trị hiện trong các ô (dạng chuỗi). Mật khẩu không bao giờ điền sẵn. ----
$cacTruong = ['ma_doc_gia', 'ho_ten', 'ngay_sinh', 'gioi_tinh', 'dia_chi', 'sdt', 'email', 'id_loai', 'trang_thai'];
$giaTri    = array_fill_keys($cacTruong, '');
$giaTri['gioi_tinh']  = 'khac';
$giaTri['trang_thai'] = 'hoat_dong';
if ($dangSua) {
    foreach ($cacTruong as $truong) {
        $giaTri[$truong] = (string) ($docGiaHienTai[$truong] ?? '');
    }
}

// ---- 4. Xử lý khi bấm Lưu ----
$loi = [];   // lỗi theo từng ô: $loi['ho_ten'] = '...'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();

    // Giữ lại những gì người dùng vừa nhập để hiện lại nếu có lỗi (sticky form)
    foreach ($cacTruong as $truong) {
        $giaTri[$truong] = layChuoiPost($truong);
    }
    // Mật khẩu: không cắt khoảng trắng (khoảng trắng có thể là một phần của mật khẩu)
    $matKhau = (isset($_POST['mat_khau']) && is_string($_POST['mat_khau'])) ? $_POST['mat_khau'] : '';

    // Mã độc giả
    if ($giaTri['ma_doc_gia'] === '') {
        $loi['ma_doc_gia'] = 'Vui lòng nhập mã độc giả.';
    } elseif (doDaiChuoi($giaTri['ma_doc_gia']) > 20) {
        $loi['ma_doc_gia'] = 'Mã độc giả tối đa 20 ký tự.';
    } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $giaTri['ma_doc_gia'])) {
        $loi['ma_doc_gia'] = 'Mã độc giả chỉ gồm chữ không dấu, số, gạch ngang và gạch dưới.';
    }

    // Họ tên
    if ($giaTri['ho_ten'] === '') {
        $loi['ho_ten'] = 'Vui lòng nhập họ tên.';
    } elseif (doDaiChuoi($giaTri['ho_ten']) > 100) {
        $loi['ho_ten'] = 'Họ tên tối đa 100 ký tự.';
    }

    // Ngày sinh: không bắt buộc. Có nhập thì phải đúng dạng năm-tháng-ngày, là ngày có thật, không ở tương lai.
    $ngaySinh = null;
    if ($giaTri['ngay_sinh'] !== '') {
        $ngay = DateTime::createFromFormat('Y-m-d', $giaTri['ngay_sinh']);
        // So sánh lại chuỗi để loại ngày không có thật (vd 2025-02-30 sẽ bị PHP tự đổi sang 2025-03-02)
        if ($ngay === false || $ngay->format('Y-m-d') !== $giaTri['ngay_sinh']) {
            $loi['ngay_sinh'] = 'Ngày sinh không hợp lệ.';
        } elseif ($ngay > new DateTime('today')) {
            $loi['ngay_sinh'] = 'Ngày sinh không được ở tương lai.';
        } elseif ((int) $ngay->format('Y') < 1900) {
            $loi['ngay_sinh'] = 'Ngày sinh phải từ năm 1900 trở đi.';
        } else {
            $ngaySinh = $giaTri['ngay_sinh'];
        }
    }

    // Giới tính
    if (!array_key_exists($giaTri['gioi_tinh'], $dsGioiTinh)) {
        $loi['gioi_tinh'] = 'Vui lòng chọn giới tính.';
    }

    // Địa chỉ: không bắt buộc
    if (doDaiChuoi($giaTri['dia_chi']) > 255) {
        $loi['dia_chi'] = 'Địa chỉ tối đa 255 ký tự.';
    }

    // Số điện thoại: không bắt buộc. Gồm 8-14 chữ số, có thể có dấu + ở đầu (tối đa 15 ký tự khớp cột sdt).
    if ($giaTri['sdt'] !== '' && !preg_match('/^\+?[0-9]{8,14}$/', $giaTri['sdt'])) {
        $loi['sdt'] = 'Số điện thoại gồm 8 đến 14 chữ số, có thể bắt đầu bằng dấu +.';
    }

    // Email: không bắt buộc
    if ($giaTri['email'] !== '') {
        if (doDaiChuoi($giaTri['email']) > 100) {
            $loi['email'] = 'Email tối đa 100 ký tự.';
        } elseif (filter_var($giaTri['email'], FILTER_VALIDATE_EMAIL) === false) {
            $loi['email'] = 'Email không hợp lệ.';
        }
    }

    // Loại độc giả: phải là số và phải thuộc danh sách có thật
    $idLoai = filter_var($giaTri['id_loai'], FILTER_VALIDATE_INT);
    if ($idLoai === false || !isset($soSachToiDaTheoLoai[$idLoai])) {
        $loi['id_loai'] = 'Vui lòng chọn loại độc giả.';
    } elseif ($dangSua && $dangMuon > $soSachToiDaTheoLoai[$idLoai]) {
        // Đổi sang loại có mức mượn thấp hơn số cuốn đang giữ thì dữ liệu sẽ mâu thuẫn
        $loi['id_loai'] = "Độc giả đang giữ $dangMuon cuốn, nhiều hơn mức tối đa "
                        . $soSachToiDaTheoLoai[$idLoai] . ' cuốn của loại này.';
    }

    // Trạng thái
    if (!array_key_exists($giaTri['trang_thai'], $dsTrangThai)) {
        $loi['trang_thai'] = 'Vui lòng chọn trạng thái.';
    }

    // Mật khẩu: thêm mới thì bắt buộc; sửa mà để trống thì giữ mật khẩu cũ.
    // Giới hạn 72 BYTE vì bcrypt (thuật toán mặc định của password_hash) chỉ dùng 72 byte đầu.
    if ($matKhau === '') {
        if (!$dangSua) {
            $loi['mat_khau'] = 'Vui lòng nhập mật khẩu.';
        }
    } elseif (doDaiChuoi($matKhau) < 6) {
        $loi['mat_khau'] = 'Mật khẩu tối thiểu 6 ký tự.';
    } elseif (strlen($matKhau) > 72) {
        $loi['mat_khau'] = 'Mật khẩu tối đa 72 byte (tiếng Việt có dấu chiếm nhiều byte hơn chữ không dấu).';
    }

    // ---- Không có lỗi nào thì ghi vào CSDL ----
    if (count($loi) === 0) {
        // Ô không bắt buộc để trống thì lưu NULL
        $diaChi = ($giaTri['dia_chi'] === '') ? null : $giaTri['dia_chi'];
        $sdt    = ($giaTri['sdt'] === '') ? null : $giaTri['sdt'];
        $email  = ($giaTri['email'] === '') ? null : $giaTri['email'];

        try {
            if ($dangSua) {
                // Các cột luôn được cập nhật
                $cacCot  = 'ma_doc_gia = ?, ho_ten = ?, ngay_sinh = ?, gioi_tinh = ?, dia_chi = ?,
                            sdt = ?, email = ?, id_loai = ?, trang_thai = ?';
                $kieu    = 'sssssssis';
                $thamSo  = [
                    $giaTri['ma_doc_gia'], $giaTri['ho_ten'], $ngaySinh, $giaTri['gioi_tinh'], $diaChi,
                    $sdt, $email, $idLoai, $giaTri['trang_thai'],
                ];
                // Chỉ đổi mật khẩu khi người dùng có nhập mật khẩu mới
                if ($matKhau !== '') {
                    $cacCot  .= ', mat_khau = ?';
                    $kieu    .= 's';
                    $thamSo[] = password_hash($matKhau, PASSWORD_DEFAULT);
                }
                $kieu    .= 'i';
                $thamSo[] = $idDocGia;

                $stmt = truyVan($conn, "UPDATE docgia SET $cacCot WHERE id = ?", $kieu, $thamSo);
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã cập nhật độc giả "' . $giaTri['ho_ten'] . '".');
            } else {
                $sql = 'INSERT INTO docgia (ma_doc_gia, ho_ten, ngay_sinh, gioi_tinh, dia_chi, sdt, email,
                               id_loai, mat_khau, ngay_dang_ky, trang_thai)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)';
                $stmt = truyVan($conn, $sql, 'sssssssiss', [
                    $giaTri['ma_doc_gia'], $giaTri['ho_ten'], $ngaySinh, $giaTri['gioi_tinh'], $diaChi,
                    $sdt, $email, $idLoai, password_hash($matKhau, PASSWORD_DEFAULT), $giaTri['trang_thai'],
                ]);
                mysqli_stmt_close($stmt);
                datThongBao('success', 'Đã thêm độc giả "' . $giaTri['ho_ten'] . '".');
            }
            header('Location: docgia.php');
            exit;
        } catch (mysqli_sql_exception $ngoaiLe) {
            if ($ngoaiLe->getCode() === 1062) {
                // 1062 = trùng giá trị ở cột UNIQUE (ở bảng docgia chỉ có ma_doc_gia)
                $loi['ma_doc_gia'] = 'Mã độc giả này đã tồn tại.';
            } else {
                throw $ngoaiLe;   // lỗi khác thì không nuốt, để lộ ra mà sửa
            }
        }
    }
}

$tieuDe      = $dangSua ? 'Sửa độc giả' : 'Thêm độc giả';
$menuHienTai = 'docgia';
require __DIR__ . '/../includes/admin-dau.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="docgia.php">Quản lý độc giả</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($tieuDe) ?></li>
    </ol>
</nav>

<div class="card">
    <div class="card-body p-4">
        <h1 class="h4 mb-1"><?= e($tieuDe) ?></h1>
        <?php if ($dangSua): ?>
            <p class="text-muted mb-4">
                Đăng ký ngày <?= e(date('d/m/Y', strtotime($docGiaHienTai['ngay_dang_ky']))) ?>
                &middot; đang giữ <?= $dangMuon ?> cuốn
            </p>
        <?php else: ?>
            <div class="mb-4"></div>
        <?php endif; ?>

        <?php if (count($loi) > 0): ?>
            <div class="alert alert-danger alert-thong-bao" role="alert">
                <img class="ic" src="../assets/icons/den/x-circle.svg" alt="" width="20" height="20">
                <span>Chưa lưu được. Hãy sửa các ô đang viền đỏ rồi bấm Lưu lại.</span>
            </div>
        <?php endif; ?>

        <!-- Không đặt action: form gửi về chính URL hiện tại (giữ nguyên ?id=...) -->
        <form method="post" novalidate autocomplete="off">
            <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">

            <!-- Khối 1: thông tin độc giả -->
            <section class="form-block">
                <h2 class="form-section">Thông tin độc giả</h2>
                <p class="form-section-sub">Loại độc giả quyết định số sách được mượn và hạn mượn.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="ma_doc_gia" class="form-label">Mã độc giả <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?= lopLoi($loi, 'ma_doc_gia') ?>" id="ma_doc_gia" name="ma_doc_gia"
                               value="<?= e($giaTri['ma_doc_gia']) ?>" maxlength="20">
                        <?php hienLoi($loi, 'ma_doc_gia'); ?>
                        <div class="form-text">Dùng để độc giả đăng nhập tra cứu (có thể trùng MSSV).</div>
                    </div>
                    <div class="col-md-8">
                        <label for="ho_ten" class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?= lopLoi($loi, 'ho_ten') ?>" id="ho_ten" name="ho_ten"
                               value="<?= e($giaTri['ho_ten']) ?>" maxlength="100">
                        <?php hienLoi($loi, 'ho_ten'); ?>
                    </div>

                    <div class="col-md-4">
                        <label for="ngay_sinh" class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control<?= lopLoi($loi, 'ngay_sinh') ?>" id="ngay_sinh" name="ngay_sinh"
                               value="<?= e($giaTri['ngay_sinh']) ?>" max="<?= e(date('Y-m-d')) ?>">
                        <?php hienLoi($loi, 'ngay_sinh'); ?>
                    </div>
                    <div class="col-md-4">
                        <label for="gioi_tinh" class="form-label">Giới tính <span class="text-danger">*</span></label>
                        <select class="form-select<?= lopLoi($loi, 'gioi_tinh') ?>" id="gioi_tinh" name="gioi_tinh">
                            <?php foreach ($dsGioiTinh as $ma => $ten): ?>
                                <option value="<?= e($ma) ?>"<?= ($ma === $giaTri['gioi_tinh']) ? ' selected' : '' ?>><?= e($ten) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php hienLoi($loi, 'gioi_tinh'); ?>
                    </div>
                    <div class="col-md-4">
                        <label for="id_loai" class="form-label">Loại độc giả <span class="text-danger">*</span></label>
                        <select class="form-select<?= lopLoi($loi, 'id_loai') ?>" id="id_loai" name="id_loai">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($dsLoai as $loai): ?>
                                <option value="<?= (int) $loai['id'] ?>"<?= ((string) $loai['id'] === $giaTri['id_loai']) ? ' selected' : '' ?>>
                                    <?= e($loai['ten_loai']) ?> (<?= (int) $loai['so_sach_toi_da'] ?> cuốn / <?= (int) $loai['so_ngay_muon'] ?> ngày)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php hienLoi($loi, 'id_loai'); ?>
                    </div>
                </div>
            </section>

            <!-- Khối 2: liên hệ -->
            <section class="form-block">
                <h2 class="form-section">Liên hệ</h2>
                <p class="form-section-sub">Không bắt buộc.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="sdt" class="form-label">Số điện thoại</label>
                        <input type="text" inputmode="tel" class="form-control<?= lopLoi($loi, 'sdt') ?>" id="sdt" name="sdt"
                               value="<?= e($giaTri['sdt']) ?>" maxlength="15">
                        <?php hienLoi($loi, 'sdt'); ?>
                    </div>
                    <div class="col-md-8">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control<?= lopLoi($loi, 'email') ?>" id="email" name="email"
                               value="<?= e($giaTri['email']) ?>" maxlength="100">
                        <?php hienLoi($loi, 'email'); ?>
                    </div>

                    <div class="col-12">
                        <label for="dia_chi" class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control<?= lopLoi($loi, 'dia_chi') ?>" id="dia_chi" name="dia_chi"
                               value="<?= e($giaTri['dia_chi']) ?>" maxlength="255">
                        <?php hienLoi($loi, 'dia_chi'); ?>
                    </div>
                </div>
            </section>

            <!-- Khối 3: tài khoản -->
            <section class="form-block">
                <h2 class="form-section">Tài khoản tra cứu</h2>
                <p class="form-section-sub">Độc giả dùng mã độc giả và mật khẩu này để tự đăng nhập xem sách đang mượn.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="mat_khau" class="form-label">
                            <?= $dangSua ? 'Mật khẩu mới' : 'Mật khẩu' ?>
                            <?php if (!$dangSua): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="password" class="form-control<?= lopLoi($loi, 'mat_khau') ?>" id="mat_khau" name="mat_khau"
                               autocomplete="new-password">
                        <?php hienLoi($loi, 'mat_khau'); ?>
                        <div class="form-text">
                            <?= $dangSua ? 'Để trống nếu không đổi mật khẩu. ' : '' ?>Từ 6 ký tự trở lên.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="trang_thai" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select<?= lopLoi($loi, 'trang_thai') ?>" id="trang_thai" name="trang_thai">
                            <?php foreach ($dsTrangThai as $ma => $ten): ?>
                                <option value="<?= e($ma) ?>"<?= ($ma === $giaTri['trang_thai']) ? ' selected' : '' ?>><?= e($ten) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php hienLoi($loi, 'trang_thai'); ?>
                        <div class="form-text">Độc giả bị khóa không đăng nhập tra cứu và không được lập phiếu mượn mới.</div>
                    </div>
                </div>
            </section>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><img class="ic" src="../assets/icons/trang/check-circle.svg" alt="" width="16" height="16">Lưu</button>
                <a href="docgia.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

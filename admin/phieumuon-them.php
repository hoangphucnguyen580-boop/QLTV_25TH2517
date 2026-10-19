<?php
// Lập phiếu mượn: thủ thư nhập mã độc giả (như quẹt thẻ) rồi nhập mã từng cuốn sách (như quét mã vạch).
// Mọi kiểm tra nghiệp vụ và việc ghi dữ liệu nằm trong hàm taoPhieuMuon() (includes/muon-tra.php).
require_once __DIR__ . '/../includes/khoi-tao-admin.php';
require_once __DIR__ . '/../includes/muon-tra.php';

// Số ô nhập sách = số cuốn tối đa của loại độc giả được mượn nhiều nhất (giảng viên: 5)
$soOSach = (int) demDong($conn, 'SELECT IFNULL(MAX(so_sach_toi_da), 1) FROM loaidocgia');
$soOSach = max(1, min($soOSach, 10));

$maDocGia = '';
$dsMa     = array_fill(0, $soOSach, '');   // nội dung các ô mã sách, giữ lại khi báo lỗi (sticky form)
$loi      = ['ma_doc_gia' => '', 'chung' => '', 'sach' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    batBuocCsrf();

    $maDocGia = layChuoiPost('ma_doc_gia');

    // Các ô mã sách gửi lên dạng mảng. Gửi sai kiểu (không phải mảng) thì coi như không nhập gì.
    $guiLen = $_POST['ma_sach'] ?? [];
    if (!is_array($guiLen)) {
        $guiLen = [];
    }
    $guiLen   = array_slice($guiLen, 0, 20, true);   // chặn gửi lên số lượng quá lớn
    $dsMaSach = [];                                  // [số thứ tự ô => mã sách], chỉ gồm các ô có nhập
    foreach ($guiLen as $chiSo => $giaTri) {
        $giaTri = is_string($giaTri) ? trim($giaTri) : '';
        if (is_int($chiSo) && $chiSo >= 0 && $chiSo < $soOSach) {
            $dsMa[$chiSo] = $giaTri;
        }
        if ($giaTri !== '') {
            $dsMaSach[(int) $chiSo] = $giaTri;
        }
    }

    // Kiểm tra dạng dữ liệu trước (chưa cần hỏi CSDL)
    if ($maDocGia === '') {
        $loi['ma_doc_gia'] = 'Vui lòng nhập mã độc giả.';
    } elseif (doDaiChuoi($maDocGia) > 20 || !preg_match('/^[A-Za-z0-9_-]+$/', $maDocGia)) {
        $loi['ma_doc_gia'] = 'Mã độc giả không hợp lệ.';
    }
    if (count($dsMaSach) === 0) {
        $loi['chung'] = 'Hãy nhập mã của ít nhất một cuốn sách.';
    }
    foreach ($dsMaSach as $chiSo => $maSach) {
        if (doDaiChuoi($maSach) > 20 || !preg_match('/^[A-Za-z0-9_-]+$/', $maSach)) {
            $loi['sach'][$chiSo] = 'Mã sách không hợp lệ.';
        }
    }

    // Dạng dữ liệu đúng hết thì mới lập phiếu
    if ($loi['ma_doc_gia'] === '' && $loi['chung'] === '' && count($loi['sach']) === 0) {
        $nhanVien = nhanVienHienTai();
        $ketQua   = taoPhieuMuon($conn, (int) $nhanVien['id'], $maDocGia, $dsMaSach);

        if ($ketQua['thanh_cong']) {
            datThongBao('success', 'Đã lập phiếu ' . $ketQua['ma_phieu'] . '. Hạn trả: ' . ngayVN($ketQua['ngay_hen_tra']) . '.');
            header('Location: phieumuon-chitiet.php?id=' . (int) $ketQua['id_phieu']);
            exit;
        }
        $loi['ma_doc_gia'] = $ketQua['loi_doc_gia'];
        $loi['chung']      = $ketQua['loi_chung'];
        $loi['sach']       = $ketQua['loi_sach'];
    }
}

$coLoi = ($loi['ma_doc_gia'] !== '' || $loi['chung'] !== '' || count($loi['sach']) > 0);

// lopLoi() và hienLoi() coi một ô là "có lỗi" khi KHÓA của ô đó tồn tại trong mảng (kể cả giá trị rỗng).
// Vì vậy chỉ đưa vào $loiO những ô thật sự có lỗi, nếu không ô nào cũng bị viền đỏ oan.
$loiO = [];
if ($loi['ma_doc_gia'] !== '') {
    $loiO['ma_doc_gia'] = $loi['ma_doc_gia'];
}

// Dữ liệu cho ô gợi ý và bảng quy định (chỉ để hiển thị)
$dsGoiYDocGia = mysqli_fetch_all(
    mysqli_query($conn, "SELECT ma_doc_gia, ho_ten FROM docgia WHERE trang_thai = 'hoat_dong' ORDER BY ma_doc_gia LIMIT 500"),
    MYSQLI_ASSOC
);
$dsGoiYSach = mysqli_fetch_all(
    mysqli_query($conn, 'SELECT ma_sach, ten_sach, so_con_lai FROM sach WHERE so_con_lai > 0 ORDER BY ma_sach LIMIT 500'),
    MYSQLI_ASSOC
);
$dsLoai = mysqli_fetch_all(
    mysqli_query($conn, 'SELECT ten_loai, so_sach_toi_da, so_ngay_muon, so_lan_gia_han FROM loaidocgia ORDER BY id'),
    MYSQLI_ASSOC
);
$tienPhatMoiNgay = (int) ($cauHinh['tien_phat_moi_ngay'] ?? 3000);

$tieuDe      = 'Lập phiếu mượn';
$menuHienTai = 'phieumuon';
require __DIR__ . '/../includes/admin-dau.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="phieumuon.php">Phiếu mượn</a></li>
        <li class="breadcrumb-item active" aria-current="page">Lập phiếu mượn</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-4">Lập phiếu mượn</h1>

                <?php if ($loi['chung'] !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= e($loi['chung']) ?></div>
                <?php elseif ($coLoi): ?>
                    <div class="alert alert-danger" role="alert">Chưa lập được phiếu. Hãy sửa các ô đang viền đỏ.</div>
                <?php endif; ?>

                <!-- Không đặt action: form gửi về chính trang này -->
                <form method="post" novalidate autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= e(taoTokenCsrf()) ?>">

                    <div class="mb-4">
                        <label for="ma_doc_gia" class="form-label">Mã độc giả <span class="text-danger">*</span></label>
                        <input type="text" class="form-control<?= lopLoi($loiO, 'ma_doc_gia') ?>" id="ma_doc_gia"
                               name="ma_doc_gia" value="<?= e($maDocGia) ?>" maxlength="20" list="goi-y-doc-gia" autofocus>
                        <?php hienLoi($loiO, 'ma_doc_gia'); ?>
                        <datalist id="goi-y-doc-gia">
                            <?php foreach ($dsGoiYDocGia as $docGia): ?>
                                <option value="<?= e($docGia['ma_doc_gia']) ?>"><?= e($docGia['ho_ten']) ?></option>
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text">Gõ vài ký tự đầu của mã để chọn từ danh sách gợi ý.</div>
                    </div>

                    <p class="form-label mb-2">Sách muốn mượn <span class="text-danger">*</span></p>
                    <div class="row g-3">
                        <?php for ($i = 0; $i < $soOSach; $i++): ?>
                            <div class="col-md-6">
                                <input type="text" class="form-control<?= isset($loi['sach'][$i]) ? ' is-invalid' : '' ?>"
                                       name="ma_sach[<?= $i ?>]" value="<?= e($dsMa[$i]) ?>" maxlength="20"
                                       list="goi-y-sach" placeholder="Mã sách thứ <?= $i + 1 ?>"
                                       aria-label="Mã sách thứ <?= $i + 1 ?>">
                                <?php if (isset($loi['sach'][$i])): ?>
                                    <div class="invalid-feedback"><?= e($loi['sach'][$i]) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <datalist id="goi-y-sach">
                        <?php foreach ($dsGoiYSach as $sach): ?>
                            <option value="<?= e($sach['ma_sach']) ?>"><?= e($sach['ten_sach']) ?> (còn <?= (int) $sach['so_con_lai'] ?>)</option>
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text mt-2">
                        Chỉ gợi ý các sách còn bản trên kệ. Ô nào không dùng thì để trống.
                        Ngày mượn là hôm nay, hạn trả tự tính theo loại độc giả.
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Lập phiếu</button>
                        <a href="phieumuon.php" class="btn btn-outline-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3">Quy định mượn</h2>
                <table class="table table-sm mb-2">
                    <thead>
                        <tr><th>Loại</th><th class="text-center">Tối đa</th><th class="text-center">Hạn</th><th class="text-center">Gia hạn</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dsLoai as $loai): ?>
                        <tr>
                            <td><?= e($loai['ten_loai']) ?></td>
                            <td class="text-center"><?= (int) $loai['so_sach_toi_da'] ?> cuốn</td>
                            <td class="text-center"><?= (int) $loai['so_ngay_muon'] ?> ngày</td>
                            <td class="text-center"><?= (int) $loai['so_lan_gia_han'] ?> lần</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Phạt trễ hạn: <?= e(tienVND($tienPhatMoiNgay)) ?> / cuốn / ngày.</li>
                    <li>Không lập phiếu cho độc giả bị khóa hoặc đang giữ sách quá hạn.</li>
                    <li>Không mượn hai bản của cùng một đầu sách.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

<?php
// Trang chủ admin: vài con số tổng quan.
require_once __DIR__ . '/../includes/khoi-tao-admin.php';

$tongSach       = demDong($conn, 'SELECT COUNT(*) FROM sach');
$tongDocGia     = demDong($conn, "SELECT COUNT(*) FROM docgia WHERE trang_thai = 'hoat_dong'");

// "Quá hạn" tính theo ngày hẹn trả so với hôm nay và còn cuốn chưa trả,
// không dựa vào cột phieumuon.trang_thai (cột đó chỉ là giá trị lưu sẵn, có thể cũ).
$phieuConHan = demDong($conn,
    'SELECT COUNT(DISTINCT pm.id)
     FROM phieumuon pm
     JOIN chitietphieumuon ct ON ct.id_phieu_muon = pm.id
     WHERE ct.ngay_tra_thuc_te IS NULL AND pm.ngay_hen_tra >= CURDATE()');
$phieuQuaHan = demDong($conn,
    'SELECT COUNT(DISTINCT pm.id)
     FROM phieumuon pm
     JOIN chitietphieumuon ct ON ct.id_phieu_muon = pm.id
     WHERE ct.ngay_tra_thuc_te IS NULL AND pm.ngay_hen_tra < CURDATE()');

$tieuDe      = 'Trang chủ';
$menuHienTai = 'trangchu';
require __DIR__ . '/../includes/admin-dau.php';
?>
<h1 class="h3 mb-1">Xin chào, <?= e($nhanVien['ho_ten']) ?></h1>
<p class="text-muted mb-4">Tình hình thư viện hôm nay.</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><img class="ic" src="../assets/icons/den/book.svg" alt="" width="18" height="18">Đầu sách</div>
            <div class="stat-num"><?= $tongSach ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><img class="ic" src="../assets/icons/den/people.svg" alt="" width="18" height="18">Độc giả hoạt động</div>
            <div class="stat-num"><?= $tongDocGia ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><img class="ic" src="../assets/icons/den/journal-text.svg" alt="" width="18" height="18">Phiếu đang mượn (còn hạn)</div>
            <div class="stat-num"><?= $phieuConHan ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <!-- Chỉ tô đỏ khi thật sự có phiếu quá hạn; bằng 0 thì hiện như các ô khác. Ô luôn có chữ "Phiếu quá hạn". -->
        <div class="stat-card<?= $phieuQuaHan > 0 ? ' canh-bao' : '' ?>">
            <div class="stat-label"><img class="ic" src="../assets/icons/den/exclamation-triangle.svg" alt="" width="18" height="18">Phiếu quá hạn</div>
            <div class="stat-num"><?= $phieuQuaHan ?></div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2">
    <a href="phieumuon-them.php" class="btn btn-primary"><img class="ic" src="../assets/icons/trang/plus-lg.svg" alt="" width="16" height="16">Lập phiếu mượn</a>
    <a href="phieumuon.php?trangthai=qua_han" class="btn btn-outline-danger"><img class="ic" src="../assets/icons/den/exclamation-triangle.svg" alt="" width="16" height="16">Xem phiếu quá hạn</a>
    <a href="thongke.php" class="btn btn-outline-primary"><img class="ic" src="../assets/icons/den/bar-chart.svg" alt="" width="16" height="16">Thống kê</a>
    <a href="sach.php" class="btn btn-outline-secondary"><img class="ic" src="../assets/icons/den/book.svg" alt="" width="16" height="16">Quản lý sách</a>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

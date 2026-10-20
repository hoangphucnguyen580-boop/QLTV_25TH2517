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
<h1 class="h3 mb-4">Xin chào, <?= e($nhanVien['ho_ten']) ?></h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="display-6"><?= $tongSach ?></div>
            <div class="text-muted">Đầu sách</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="display-6"><?= $tongDocGia ?></div>
            <div class="text-muted">Độc giả hoạt động</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="display-6"><?= $phieuConHan ?></div>
            <div class="text-muted">Phiếu đang mượn (còn hạn)</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100 border-danger"><div class="card-body">
            <div class="display-6 text-danger"><?= $phieuQuaHan ?></div>
            <div class="text-muted">Phiếu quá hạn</div>
        </div></div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2">
    <a href="phieumuon-them.php" class="btn btn-primary">+ Lập phiếu mượn</a>
    <a href="phieumuon.php?trangthai=qua_han" class="btn btn-outline-danger">Xem phiếu quá hạn</a>
    <a href="thongke.php" class="btn btn-outline-primary">Thống kê</a>
    <a href="sach.php" class="btn btn-outline-secondary">Quản lý sách</a>
</div>
<?php require __DIR__ . '/../includes/admin-cuoi.php'; ?>

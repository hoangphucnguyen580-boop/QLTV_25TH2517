<?php
// Đăng nhập / đăng xuất cho ĐỘC GIẢ (trang tra cứu).
// Dùng khóa session RIÊNG $_SESSION['doc_gia'], khác hoàn toàn $_SESSION['nhan_vien'] của thủ thư:
// phiên độc giả không bao giờ mở được trang admin (admin chỉ kiểm khóa 'nhan_vien'), và ngược lại.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csdl.php';

const TRACUU_SO_LAN_SAI_TOI_DA = 5;         // sai liên tiếp từng này lần thì bị khóa tạm
const TRACUU_THOI_GIAN_KHOA    = 300;       // giây (5 phút)
const TRACUU_DO_TRE_KHI_SAI    = 1000000;   // micro-giây (1 giây) chờ sau mỗi lần sai để làm chậm việc dò mật khẩu
const TRACUU_LOI_CHUNG         = 'Sai mã độc giả hoặc mật khẩu.';

// Hash bcrypt của một chuỗi ngẫu nhiên không ai biết. Dùng khi mã độc giả không tồn tại:
// vẫn chạy password_verify một lần để thời gian trả lời gần như bằng nhau,
// nhờ đó người ngoài không đoán được mã nào có thật bằng cách đo thời gian.
const TRACUU_HASH_GIA = '$2y$10$NCjJIDHHuPV2G47e9J3rj.ippAoqZYQRevszYHIqkKSXnpkh6.URC';

// Thử đăng nhập. Trả về ['thanh_cong' => bool, 'loi' => chuỗi].
// Thành công thì đã ghi $_SESSION['doc_gia'] = ['id' => ...] (chỉ lưu id, thông tin khác đọc lại từ CSDL mỗi lần).
function dangNhapDocGia(mysqli $conn, string $maDocGia, string $matKhau): array
{
    // Đang bị khóa tạm vì sai nhiều lần: từ chối luôn, kể cả khi nhập đúng
    $khoaDen = (int) ($_SESSION['tracuu_khoa_den'] ?? 0);
    if ($khoaDen > time()) {
        $soPhut = (int) ceil(($khoaDen - time()) / 60);
        return ['thanh_cong' => false, 'loi' => 'Bạn nhập sai quá nhiều lần. Hãy đợi khoảng ' . $soPhut . ' phút rồi thử lại.'];
    }
    if ($khoaDen !== 0) {
        // Hết thời gian khóa: làm lại từ đầu
        unset($_SESSION['tracuu_khoa_den'], $_SESSION['tracuu_so_lan_sai']);
    }

    // Mã hoặc mật khẩu sai dạng thì khỏi hỏi CSDL, nhưng vẫn tính là một lần sai.
    // Giới hạn 72 byte khớp với lúc đặt mật khẩu (bcrypt chỉ dùng 72 byte đầu).
    $dungDang = doDaiChuoi($maDocGia) <= 20
        && preg_match('/^[A-Za-z0-9_-]+$/', $maDocGia) === 1
        && strlen($matKhau) <= 72;

    $docGia = null;
    if ($dungDang) {
        $stmt   = truyVan($conn, 'SELECT id, mat_khau, trang_thai FROM docgia WHERE ma_doc_gia = ?', 's', [$maDocGia]);
        $docGia = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
        mysqli_stmt_close($stmt);
    }

    // Luôn chạy password_verify đúng một lần (với hash giả nếu không có độc giả)
    $hashDeSoSanh = ($docGia !== null) ? $docGia['mat_khau'] : TRACUU_HASH_GIA;
    $matKhauDung  = password_verify($matKhau, $hashDeSoSanh);

    // Độc giả bị khóa cũng bị báo lỗi chung như sai mật khẩu (không lộ tài khoản nào tồn tại / bị khóa)
    $hopLe = ($docGia !== null) && $matKhauDung && ($docGia['trang_thai'] === 'hoat_dong');

    if (!$hopLe) {
        $soLanSai = (int) ($_SESSION['tracuu_so_lan_sai'] ?? 0) + 1;
        $_SESSION['tracuu_so_lan_sai'] = $soLanSai;
        usleep(TRACUU_DO_TRE_KHI_SAI);

        if ($soLanSai >= TRACUU_SO_LAN_SAI_TOI_DA) {
            $_SESSION['tracuu_khoa_den'] = time() + TRACUU_THOI_GIAN_KHOA;
            return ['thanh_cong' => false, 'loi' => 'Bạn nhập sai quá nhiều lần. Hãy đợi '
                . (TRACUU_THOI_GIAN_KHOA / 60) . ' phút rồi thử lại.'];
        }
        return ['thanh_cong' => false, 'loi' => TRACUU_LOI_CHUNG];
    }

    // Nếu thuật toán/độ khó mặc định của PHP đã đổi thì băm lại và cập nhật
    if (password_needs_rehash($docGia['mat_khau'], PASSWORD_DEFAULT)) {
        $bamMoi = password_hash($matKhau, PASSWORD_DEFAULT);
        $stmt   = truyVan($conn, 'UPDATE docgia SET mat_khau = ? WHERE id = ?', 'si', [$bamMoi, (int) $docGia['id']]);
        mysqli_stmt_close($stmt);
    }

    session_regenerate_id(true);   // đổi mã phiên sau khi đăng nhập (chống session fixation)
    unset($_SESSION['tracuu_so_lan_sai'], $_SESSION['tracuu_khoa_den']);
    $_SESSION['doc_gia'] = ['id' => (int) $docGia['id']];
    return ['thanh_cong' => true, 'loi' => ''];
}

function daDangNhapDocGia(): bool
{
    return isset($_SESSION['doc_gia']);
}

// Gọi ở đầu mọi trang tra cứu (trừ trang đăng nhập). Trả về thông tin độc giả đang đăng nhập.
// Mỗi lần đều đọc lại CSDL: độc giả bị khóa hoặc bị xóa giữa chừng thì bị đẩy ra ngay,
// không đợi hết phiên.
function yeuCauDangNhapDocGia(mysqli $conn): array
{
    if (!daDangNhapDocGia()) {
        header('Location: dang-nhap.php');
        exit;
    }

    $idDocGia = $_SESSION['doc_gia']['id'] ?? 0;
    $docGia   = null;
    if (is_int($idDocGia) && $idDocGia > 0) {
        $stmt = truyVan(
            $conn,
            'SELECT dg.id, dg.ma_doc_gia, dg.ho_ten, dg.trang_thai,
                    ld.ten_loai, ld.so_sach_toi_da, ld.so_ngay_muon, ld.so_lan_gia_han
             FROM docgia dg
             JOIN loaidocgia ld ON ld.id = dg.id_loai
             WHERE dg.id = ?',
            'i',
            [$idDocGia]
        );
        $docGia = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
        mysqli_stmt_close($stmt);
    }

    if ($docGia === null || $docGia['trang_thai'] !== 'hoat_dong') {
        unset($_SESSION['doc_gia']);
        header('Location: dang-nhap.php?het_hieu_luc=1');
        exit;
    }
    return $docGia;
}

// Chỉ bỏ khóa của độc giả, KHÔNG đụng tới phiên thủ thư (nếu cùng trình duyệt đang đăng nhập cả hai)
function dangXuatDocGia(): void
{
    unset($_SESSION['doc_gia']);
    session_regenerate_id(true);
}

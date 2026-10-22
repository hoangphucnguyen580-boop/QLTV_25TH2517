<?php
// Đăng nhập / đăng xuất cho nhân viên (thủ thư, admin), dùng password_verify + session.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csdl.php';

const ADMIN_SO_LAN_SAI_TOI_DA = 5;         // sai liên tiếp từng này lần thì bị khóa tạm
const ADMIN_THOI_GIAN_KHOA    = 300;       // giây (5 phút)
const ADMIN_DO_TRE_KHI_SAI    = 1000000;   // micro-giây (1 giây) chờ sau mỗi lần sai để làm chậm việc dò mật khẩu
const ADMIN_LOI_CHUNG         = 'Sai tên đăng nhập hoặc mật khẩu.';

// Hash bcrypt của một chuỗi ngẫu nhiên không ai biết. Dùng khi tên đăng nhập không tồn tại:
// vẫn chạy password_verify một lần để thời gian trả lời gần như bằng nhau,
// nhờ đó người ngoài không đoán được tên nào có thật bằng cách đo thời gian.
const ADMIN_HASH_GIA = '$2y$10$NCjJIDHHuPV2G47e9J3rj.ippAoqZYQRevszYHIqkKSXnpkh6.URC';

// Thử đăng nhập. Trả về ['thanh_cong' => bool, 'loi' => chuỗi].
// Thành công thì đã ghi $_SESSION['nhan_vien'] = [id, ho_ten, vai_tro].
function dangNhapNhanVien(mysqli $conn, string $tenDangNhap, string $matKhau): array
{
    // Đang bị khóa tạm vì sai nhiều lần: từ chối luôn, kể cả khi nhập đúng
    $khoaDen = (int) ($_SESSION['admin_khoa_den'] ?? 0);
    if ($khoaDen > time()) {
        $soPhut = (int) ceil(($khoaDen - time()) / 60);
        return ['thanh_cong' => false, 'loi' => 'Bạn nhập sai quá nhiều lần. Hãy đợi khoảng ' . $soPhut . ' phút rồi thử lại.'];
    }
    if ($khoaDen !== 0) {
        // Hết thời gian khóa: làm lại từ đầu
        unset($_SESSION['admin_khoa_den'], $_SESSION['admin_so_lan_sai']);
    }

    // Tên quá dài (cột ten_dang_nhap chỉ có 30 ký tự) hoặc mật khẩu quá 72 byte thì chắc chắn sai,
    // khỏi hỏi CSDL nhưng vẫn tính là một lần sai.
    $dungDang = doDaiChuoi($tenDangNhap) <= 30 && strlen($matKhau) <= 72;

    $nhanVien = null;
    if ($dungDang) {
        $stmt     = truyVan(
            $conn,
            'SELECT id, ho_ten, mat_khau, vai_tro FROM nhanvien WHERE ten_dang_nhap = ?',
            's',
            [$tenDangNhap]
        );
        $nhanVien = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
        mysqli_stmt_close($stmt);
    }

    // Luôn chạy password_verify đúng một lần (với hash giả nếu không có nhân viên này)
    $hashDeSoSanh = ($nhanVien !== null) ? $nhanVien['mat_khau'] : ADMIN_HASH_GIA;
    $matKhauDung  = password_verify($matKhau, $hashDeSoSanh);

    // Không nói rõ là sai tên hay sai mật khẩu
    $hopLe = ($nhanVien !== null) && $matKhauDung;

    if (!$hopLe) {
        $soLanSai = (int) ($_SESSION['admin_so_lan_sai'] ?? 0) + 1;
        $_SESSION['admin_so_lan_sai'] = $soLanSai;
        usleep(ADMIN_DO_TRE_KHI_SAI);

        if ($soLanSai >= ADMIN_SO_LAN_SAI_TOI_DA) {
            $_SESSION['admin_khoa_den'] = time() + ADMIN_THOI_GIAN_KHOA;
            return ['thanh_cong' => false, 'loi' => 'Bạn nhập sai quá nhiều lần. Hãy đợi '
                . (ADMIN_THOI_GIAN_KHOA / 60) . ' phút rồi thử lại.'];
        }
        return ['thanh_cong' => false, 'loi' => ADMIN_LOI_CHUNG];
    }

    // Nếu thuật toán/độ khó mặc định của PHP đã đổi thì băm lại và cập nhật
    if (password_needs_rehash($nhanVien['mat_khau'], PASSWORD_DEFAULT)) {
        $bamMoi = password_hash($matKhau, PASSWORD_DEFAULT);
        $stmt   = truyVan($conn, 'UPDATE nhanvien SET mat_khau = ? WHERE id = ?', 'si', [$bamMoi, (int) $nhanVien['id']]);
        mysqli_stmt_close($stmt);
    }

    session_regenerate_id(true);   // đổi mã phiên sau khi đăng nhập (chống session fixation)
    unset($_SESSION['admin_so_lan_sai'], $_SESSION['admin_khoa_den']);
    $_SESSION['nhan_vien'] = [
        'id'      => (int) $nhanVien['id'],
        'ho_ten'  => $nhanVien['ho_ten'],
        'vai_tro' => $nhanVien['vai_tro'],
    ];
    return ['thanh_cong' => true, 'loi' => ''];
}

function daDangNhap(): bool
{
    return isset($_SESSION['nhan_vien']);
}

// Thông tin nhân viên đang đăng nhập, null nếu chưa đăng nhập
function nhanVienHienTai(): ?array
{
    return $_SESSION['nhan_vien'] ?? null;
}

// Gọi ở đầu mọi trang admin: chưa đăng nhập thì đẩy về trang đăng nhập và dừng luôn
function yeuCauDangNhap(): void
{
    if (!daDangNhap()) {
        header('Location: dang-nhap.php');
        exit;
    }
}

// Tên hiển thị của vai trò
function tenVaiTro(string $vaiTro): string
{
    return $vaiTro === 'admin' ? 'Quản trị viên' : 'Thủ thư';
}

// Xóa dữ liệu session và cookie session
function dangXuat(): void
{
    $_SESSION = [];
    $thamSo = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $thamSo['path'],
        'domain'   => $thamSo['domain'],
        'secure'   => $thamSo['secure'],
        'httponly' => $thamSo['httponly'],
        'samesite' => $thamSo['samesite'] ?: 'Lax',
    ]);
    session_destroy();
}

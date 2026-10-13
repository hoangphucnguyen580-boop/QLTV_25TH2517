<?php
// Đăng nhập / đăng xuất cho nhân viên (thủ thư, admin), dùng password_verify + session.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csdl.php';

// Trả về true nếu đăng nhập đúng và đã ghi thông tin vào session.
function dangNhapNhanVien(mysqli $conn, string $tenDangNhap, string $matKhau): bool
{
    $stmt = truyVan(
        $conn,
        'SELECT id, ho_ten, mat_khau, vai_tro FROM nhanvien WHERE ten_dang_nhap = ?',
        's',
        [$tenDangNhap]
    );
    $hang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Không nói rõ là sai tên hay sai mật khẩu
    if (!$hang || !password_verify($matKhau, $hang['mat_khau'])) {
        return false;
    }

    // Nếu thuật toán/độ khó mặc định của PHP đã đổi thì băm lại và cập nhật
    if (password_needs_rehash($hang['mat_khau'], PASSWORD_DEFAULT)) {
        $bamMoi = password_hash($matKhau, PASSWORD_DEFAULT);
        $stmt   = truyVan($conn, 'UPDATE nhanvien SET mat_khau = ? WHERE id = ?', 'si', [$bamMoi, (int) $hang['id']]);
        mysqli_stmt_close($stmt);
    }

    session_regenerate_id(true);   // đổi mã phiên sau khi đăng nhập (chống session fixation)
    $_SESSION['nhan_vien'] = [
        'id'     => (int) $hang['id'],
        'ho_ten' => $hang['ho_ten'],
        'vai_tro' => $hang['vai_tro'],
    ];
    return true;
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

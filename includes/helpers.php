<?php
// Hàm dùng chung cho toàn dự án: escape đầu ra, đọc dữ liệu form, session an toàn, token CSRF, thông báo một lần.

// ---------------------------------------------------------------------
// Escape + đọc dữ liệu người dùng
// ---------------------------------------------------------------------

// Escape dữ liệu trước khi in ra HTML (chống XSS)
function e($chuoi): string
{
    return htmlspecialchars((string) $chuoi, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Đọc một tham số GET dưới dạng chuỗi đã cắt khoảng trắng.
// Nếu người dùng cố tình gửi dạng mảng (?q[]=a) thì coi như rỗng, tránh lỗi "Array to string".
function layChuoiGet(string $ten): string
{
    $giaTri = $_GET[$ten] ?? '';
    return is_string($giaTri) ? trim($giaTri) : '';
}

// Tương tự layChuoiGet nhưng cho dữ liệu POST
function layChuoiPost(string $ten): string
{
    $giaTri = $_POST[$ten] ?? '';
    return is_string($giaTri) ? trim($giaTri) : '';
}

// Đếm số KÝ TỰ của chuỗi (không phải số byte): "Đắc nhân tâm" tiếng Việt có dấu chiếm nhiều byte hơn số ký tự.
// Máy nào chưa bật extension mbstring thì đếm theo byte: chặt hơn thực tế nên vẫn an toàn, chỉ là báo "quá dài" sớm hơn.
function doDaiChuoi(string $chuoi): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($chuoi, 'UTF-8');
    }
    return strlen($chuoi);
}

// ---------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------

// Bật session với cookie an toàn (gọi trước khi in bất kỳ nội dung nào)
function khoiDongSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Đặt tên riêng để không đè lên session của dự án khác cùng chạy ở localhost:8000
    session_name('QLTV_PHIEN');
    session_set_cookie_params([
        'lifetime' => 0,                            // mất khi đóng trình duyệt
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),    // chỉ gửi qua HTTPS khi site chạy HTTPS
        'httponly' => true,                         // JavaScript không đọc được cookie session
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------
// CSRF: mọi form có hành động ghi dữ liệu phải gửi kèm token
// ---------------------------------------------------------------------

// Tạo (hoặc lấy lại) token CSRF của phiên hiện tại
function taoTokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// So sánh token gửi lên với token trong session (hash_equals so sánh an toàn)
function kiemTraCsrf($tokenGui): bool
{
    return isset($_SESSION['csrf'])
        && is_string($tokenGui)
        && hash_equals($_SESSION['csrf'], $tokenGui);
}

// Dừng ngay với mã 400 nếu form POST không có token hợp lệ
function batBuocCsrf(): void
{
    if (!kiemTraCsrf($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Yêu cầu không hợp lệ (thiếu hoặc sai mã bảo vệ CSRF). Hãy quay lại và tải lại trang.');
    }
}

// ---------------------------------------------------------------------
// Thông báo hiện đúng một lần sau khi chuyển trang (flash message)
// ---------------------------------------------------------------------

// $loai: success | danger | warning | info (trùng tên lớp alert của Bootstrap)
function datThongBao(string $loai, string $noiDung): void
{
    $_SESSION['thong_bao'] = ['loai' => $loai, 'noi_dung' => $noiDung];
}

// Lấy thông báo ra và xóa luôn khỏi session. Trả về null nếu không có.
function layThongBao(): ?array
{
    if (!isset($_SESSION['thong_bao'])) {
        return null;
    }
    $thongBao = $_SESSION['thong_bao'];
    unset($_SESSION['thong_bao']);
    return $thongBao;
}

// ---------------------------------------------------------------------
// Hỗ trợ hiển thị lỗi từng ô trong form Bootstrap
// ---------------------------------------------------------------------

// Trả về " is-invalid" nếu ô này có lỗi (để viền đỏ)
function lopLoi(array $loi, string $truong): string
{
    return isset($loi[$truong]) ? ' is-invalid' : '';
}

// In dòng chữ lỗi dưới ô nhập (nếu có)
function hienLoi(array $loi, string $truong): void
{
    if (isset($loi[$truong])) {
        echo '<div class="invalid-feedback">' . e($loi[$truong]) . '</div>';
    }
}

<?php
// Xử lý ảnh bìa sách: kiểm tra file upload, lưu vào thư mục uploads/, xóa file cũ.
//
// Đường dẫn lưu trong CSDL (cột sach.hinh_bia) luôn có dạng  uploads/<32 ký tự hex>.<jpg|png|webp>
// và tính từ thư mục gốc dự án. Tên file do server tự sinh ngẫu nhiên, KHÔNG dùng tên người dùng gửi lên.

const ANH_BIA_TOI_DA  = 2097152;   // 2 MB (tính bằng byte). Trùng với upload_max_filesize mặc định 2M của XAMPP.
const ANH_BIA_THU_MUC = 'uploads';

// Các kiểu ảnh cho phép: MIME thật (do finfo đọc từ nội dung file) => đuôi file sẽ lưu
function cacKieuAnhChoPhep(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
}

// Kiểm tra một file vừa upload (một phần tử của $_FILES).
// Trả về mảng [thông báo lỗi, đuôi file]: nếu hợp lệ thì lỗi = null và đuôi là 'jpg' / 'png' / 'webp'.
function kiemTraAnhBia(array $tep): array
{
    // Gửi nhiều file cùng tên ô (hinh_bia[]) thì 'error' là mảng: không chấp nhận
    if (!isset($tep['error']) || is_array($tep['error'])) {
        return ['Dữ liệu ảnh gửi lên không hợp lệ.', null];
    }

    $maLoi = (int) $tep['error'];
    if ($maLoi === UPLOAD_ERR_INI_SIZE || $maLoi === UPLOAD_ERR_FORM_SIZE) {
        return ['Ảnh vượt quá dung lượng cho phép (tối đa 2 MB).', null];
    }
    if ($maLoi !== UPLOAD_ERR_OK) {
        return ['Tải ảnh lên không thành công (mã lỗi ' . $maLoi . '). Hãy thử lại.', null];
    }

    // Chỉ chấp nhận file thật sự do PHP nhận qua upload, không phải đường dẫn người dùng tự điền
    $tam = (string) ($tep['tmp_name'] ?? '');
    if ($tam === '' || !is_uploaded_file($tam)) {
        return ['Tệp ảnh gửi lên không hợp lệ.', null];
    }

    // Dung lượng: lấy từ chính file trên đĩa, không tin con số do trình duyệt báo
    $dungLuong = filesize($tam);
    if ($dungLuong === false || $dungLuong <= 0) {
        return ['Tệp ảnh rỗng hoặc không đọc được.', null];
    }
    if ($dungLuong > ANH_BIA_TOI_DA) {
        return ['Ảnh vượt quá dung lượng cho phép (tối đa 2 MB).', null];
    }

    // Loại file: đọc từ NỘI DUNG, không tin đuôi file hay $_FILES['type'] (cả hai đều giả mạo được)
    if (!class_exists('finfo')) {
        return ['Máy chủ chưa bật extension fileinfo nên không kiểm tra được loại ảnh.', null];
    }
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $kieuMime = $finfo->file($tam);
    $choPhep  = cacKieuAnhChoPhep();
    if ($kieuMime === false || !isset($choPhep[$kieuMime])) {
        return ['Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.', null];
    }

    // Kiểm tra thêm: nội dung phải đọc được như một ảnh thật
    if (@getimagesize($tam) === false) {
        return ['Tệp không phải ảnh hợp lệ.', null];
    }

    return [null, $choPhep[$kieuMime]];
}

// Lưu file đã kiểm tra vào uploads/ với tên ngẫu nhiên.
// Trả về đường dẫn tương đối để ghi vào CSDL (vd 'uploads/9f2c....png'), hoặc null nếu lưu thất bại.
function luuAnhBia(string $duongDanTam, string $duoiFile): ?string
{
    $thuMuc = __DIR__ . '/../' . ANH_BIA_THU_MUC;
    if (!is_dir($thuMuc) || !is_writable($thuMuc)) {
        return null;
    }

    $tenFile = bin2hex(random_bytes(16)) . '.' . $duoiFile;
    $dich    = $thuMuc . '/' . $tenFile;
    if (!move_uploaded_file($duongDanTam, $dich)) {
        return null;
    }
    @chmod($dich, 0644);   // để Apache đọc được (file tạm của PHP thường chỉ có quyền 0600 trên Linux)

    return ANH_BIA_THU_MUC . '/' . $tenFile;
}

// Đường dẫn có đúng dạng do hàm luuAnhBia() tạo ra không?
// Dùng làm chốt chặn: dù giá trị trong CSDL có bị sửa bậy cũng không bao giờ xóa hay hiện file ngoài uploads/.
function laDuongDanAnhBia(?string $duongDan): bool
{
    if ($duongDan === null) {
        return false;
    }
    return preg_match('#^' . ANH_BIA_THU_MUC . '/[a-f0-9]{32}\.(jpg|png|webp)$#', $duongDan) === 1;
}

// Xóa file ảnh bìa trên đĩa. Đường dẫn null / sai dạng / file không còn thì bỏ qua, không báo lỗi.
function xoaAnhBia(?string $duongDan): void
{
    if (!laDuongDanAnhBia($duongDan)) {
        return;
    }
    $dayDu = __DIR__ . '/../' . $duongDan;
    if (is_file($dayDu)) {
        @unlink($dayDu);
    }
}

// Địa chỉ để đặt vào thẻ <img> trong các trang admin (các trang này nằm trong admin/ nên phải lùi một cấp).
// Trả về null nếu không có ảnh, đường dẫn sai dạng hoặc file đã mất => trang sẽ hiện khối xám thay thế.
function urlAnhBia(?string $duongDan): ?string
{
    if (!laDuongDanAnhBia($duongDan)) {
        return null;
    }
    if (!is_file(__DIR__ . '/../' . $duongDan)) {
        return null;
    }
    return '../' . $duongDan;
}

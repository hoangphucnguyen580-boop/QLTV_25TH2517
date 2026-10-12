# QLTV_25TH2517
Hệ thống quản lý mượn - trả sách (PHP thuần + mysqli + MySQL/MariaDB, chạy trên XAMPP).

## Cấu trúc thư mục
```
qltv/
├── database/
│   ├── schema.sql          # tạo CSDL qltv + 8 bảng (chạy trước)
│   └── du-lieu-mau.sql     # dữ liệu mẫu để demo (chạy sau)
├── uploads/                # ảnh bìa sách upload (nội dung không đưa lên Git)
├── config.mau.php          # mẫu cấu hình -> sao chép thành config.php
├── db.php                  # kết nối mysqli dùng chung
├── kiem-tra.php            # trang kiểm tra môi trường (xóa trước khi nộp)
└── .gitignore
```

## Cách chạy
1. Đặt thư mục dự án tại `C:\xampp\htdocs\qltv`.
2. Mở XAMPP Control Panel, Start **Apache** và **MySQL**.
3. Mở phpMyAdmin, tab **Import**, nạp lần lượt `database/schema.sql` rồi `database/du-lieu-mau.sql`
   (chọn character set của file là utf8).
4. Sao chép `config.mau.php` thành `config.php` (sửa nếu MySQL có mật khẩu hoặc đổi cổng).
5. Mở `http://localhost:8000/qltv/kiem-tra.php`, thấy "Kết nối CSDL: OK" và bảng số dòng là đạt.

## Tài khoản mẫu
| Đối tượng | Đăng nhập | Mật khẩu |
|---|---|---|
| Admin | `admin` | `admin123` |
| Thủ thư | `thuthu01` | `admin123` |
| Độc giả | mã độc giả, ví dụ `25TH0001` | `123456` |

Chỉ dùng cho dữ liệu mẫu trên máy cá nhân.

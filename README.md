# QLTV_25TH2517
Hệ thống quản lý mượn - trả sách (PHP thuần + mysqli + MySQL/MariaDB, chạy trên XAMPP, giao diện Bootstrap 5.3.3).

## Cấu trúc thư mục
```
QLTV_25TH2517/
├── admin/                  # khu vực thủ thư (mọi trang trừ dang-nhap.php đều bắt buộc đăng nhập)
│   ├── dang-nhap.php
│   ├── dang-xuat.php
│   ├── index.php           # trang chủ: số liệu tổng quan
│   ├── sach.php            # danh sách sách: tìm kiếm, lọc thể loại, phân trang
│   ├── sach-form.php       # thêm / sửa sách
│   ├── sach-xoa.php        # xóa sách (chỉ nhận POST)
│   ├── theloai.php
│   └── nhaxuatban.php
├── includes/               # file dùng chung, không mở trực tiếp bằng trình duyệt
│   ├── helpers.php         # escape, session an toàn, CSRF, thông báo
│   ├── csdl.php            # hàm chạy prepared statement
│   ├── xac-thuc.php        # đăng nhập / đăng xuất nhân viên
│   ├── khoi-tao-admin.php  # đầu mọi trang admin: nạp thư viện + chặn người chưa đăng nhập
│   ├── admin-dau.php       # phần đầu HTML + thanh menu
│   └── admin-cuoi.php      # phần cuối HTML
├── assets/bootstrap/       # Bootstrap 5.3.3 (chạy được khi không có mạng)
├── database/
│   ├── schema.sql          # tạo CSDL qltv + 8 bảng (chạy trước)
│   └── du-lieu-mau.sql     # dữ liệu mẫu để demo (chạy sau)
├── uploads/                # ảnh bìa sách upload (nội dung không đưa lên Git)
├── config.mau.php          # mẫu cấu hình -> sao chép thành config.php
├── db.php                  # kết nối mysqli dùng chung
├── index.php               # tạm thời chuyển thẳng vào admin/
├── kiem-tra.php            # trang kiểm tra môi trường (xóa trước khi nộp)
└── .gitignore
```

## Cách chạy
1. Đặt thư mục dự án trong `htdocs` của XAMPP.
2. Mở XAMPP Control Panel, Start **Apache** và **MySQL**.
3. Mở phpMyAdmin, tab **Import**, nạp lần lượt `database/schema.sql` rồi `database/du-lieu-mau.sql`
   (chọn character set của file là utf-8).
4. Sao chép `config.mau.php` thành `config.php` (sửa nếu MySQL có mật khẩu hoặc đổi cổng).
5. Mở `http://localhost:8000/QLTV_25TH2517/kiem-tra.php`, thấy "Kết nối CSDL: OK" và bảng số dòng là đạt.
6. Mở `http://localhost:8000/QLTV_25TH2517/admin/` để đăng nhập.

## Tài khoản mẫu
| Đối tượng | Đăng nhập | Mật khẩu |
|---|---|---|
| Admin | `admin` | `admin123` |
| Thủ thư | `thuthu01` | `admin123` |
| Độc giả | mã độc giả, ví dụ `25TH0001` | `123456` |

Chỉ dùng cho dữ liệu mẫu trên máy cá nhân.

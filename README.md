# QLTV_25TH2517
Hệ thống quản lý mượn - trả sách (PHP thuần + mysqli + MySQL/MariaDB, chạy trên XAMPP, giao diện Bootstrap 5.3.3).

Hai nhóm người dùng:
- **Thủ thư / Admin**: quản lý sách, độc giả, thể loại, nhà xuất bản; lập phiếu mượn, nhận trả sách (tự tính phạt và bồi thường), gia hạn; xem thống kê.
- **Độc giả**: tự đăng nhập để xem sách đang mượn, hạn trả và lịch sử mượn - trả (không đặt mượn online).

## Cấu trúc thư mục
```
QLTV_25TH2517/
├── index.php               # trang chọn: Thủ thư hoặc Độc giả
├── admin/                  # khu vực thủ thư (mọi trang trừ dang-nhap.php đều bắt buộc đăng nhập)
│   ├── dang-nhap.php, dang-xuat.php
│   ├── index.php           # trang chủ: số liệu tổng quan
│   ├── sach.php, sach-form.php, sach-xoa.php          # sách (có upload ảnh bìa)
│   ├── docgia.php, docgia-form.php, docgia-khoa.php, docgia-xoa.php
│   ├── theloai.php, nhaxuatban.php
│   ├── phieumuon.php           # danh sách phiếu mượn
│   ├── phieumuon-them.php      # lập phiếu mượn
│   ├── phieumuon-chitiet.php   # chi tiết phiếu, nút trả sách / gia hạn
│   ├── phieumuon-tra.php       # nhận trả sách (chỉ nhận POST)
│   ├── phieumuon-giahan.php    # gia hạn phiếu (chỉ nhận POST)
│   └── thongke.php             # thống kê: sách mượn nhiều, độc giả trễ hạn, phạt theo tháng
├── tracuu/                 # khu vực độc giả
│   ├── dang-nhap.php, dang-xuat.php
│   └── index.php           # sách đang mượn + lịch sử mượn - trả
├── includes/               # file dùng chung, không mở trực tiếp bằng trình duyệt
│   ├── helpers.php         # escape, session an toàn, CSRF, thông báo
│   ├── csdl.php            # hàm chạy prepared statement
│   ├── muon-tra.php        # nghiệp vụ mượn / trả / gia hạn, tính phạt và bồi thường
│   ├── anh-bia.php         # kiểm tra và lưu ảnh bìa sách
│   ├── xac-thuc.php        # đăng nhập / đăng xuất thủ thư
│   ├── khoi-tao-admin.php  # đầu mọi trang admin: nạp thư viện + chặn người chưa đăng nhập
│   ├── admin-dau.php, admin-cuoi.php
│   ├── xac-thuc-doc-gia.php    # đăng nhập độc giả (khóa tạm sau 5 lần sai)
│   ├── khoi-tao-tracuu.php     # đầu mọi trang tra cứu: chặn người chưa đăng nhập
│   └── tracuu-dau.php, tracuu-cuoi.php
├── assets/bootstrap/       # Bootstrap 5.3.3 (chạy được khi không có mạng)
├── database/
│   ├── schema.sql          # tạo CSDL qltv + 8 bảng (chạy trước)
│   └── du-lieu-mau.sql     # dữ liệu mẫu để demo (chạy sau)
├── uploads/                # ảnh bìa sách upload (nội dung không đưa lên Git; có .htaccess cấm chạy mã)
├── config.mau.php          # mẫu cấu hình -> sao chép thành config.php
├── db.php                  # kết nối mysqli dùng chung
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
6. Mở `http://localhost:8000/QLTV_25TH2517/` rồi chọn khu vực: **Thủ thư** (vào `admin/`) hoặc **Độc giả** (vào `tracuu/`).

## Tài khoản mẫu
| Đối tượng | Đăng nhập | Mật khẩu |
|---|---|---|
| Admin | `admin` | `admin123` |
| Thủ thư | `thuthu01` | `admin123` |
| Độc giả | mã độc giả, ví dụ `25TH0001` | `123456` |

Chỉ dùng cho dữ liệu mẫu trên máy cá nhân.

Dữ liệu mẫu tính ngày theo thời điểm import (`CURDATE()`), nên lúc nào import cũng có phiếu đang mượn và phiếu quá hạn thật để demo.
Một vài độc giả để thử: `25TH0005` (đang giữ 2 cuốn quá hạn), `25TH0002` (lịch sử có phạt trễ, sách hỏng, sách mất), `GV002` (phiếu đã gia hạn),
`25TH0008` (tài khoản bị khóa, không đăng nhập được).

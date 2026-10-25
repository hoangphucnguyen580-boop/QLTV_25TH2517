# QLTV_25TH2517
Hệ thống quản lý mượn - trả sách (PHP thuần + mysqli + MySQL/MariaDB, chạy trên XAMPP, giao diện Bootstrap 5.3.3).

Hai nhóm người dùng:
- **Thủ thư / Admin**: quản lý sách, độc giả, thể loại, nhà xuất bản; lập phiếu mượn, nhận trả sách (tự tính phạt và bồi thường), gia hạn; xem thống kê.
- **Độc giả**: tự đăng nhập để xem sách đang mượn, hạn trả và lịch sử mượn - trả (không đặt mượn online).

## Yêu cầu môi trường
- XAMPP (Apache + MySQL/MariaDB), PHP 8.x với các extension `mysqli`, `mbstring`, `fileinfo` (XAMPP bật sẵn).
- Apache phải cho phép `.htaccess` (`AllowOverride`, XAMPP mặc định đã bật). Các file `.htaccess` của dự án dùng để chặn tải trực tiếp `config.php`, file `.sql`, thư mục `includes/` và `database/`.
- Đã chạy thử: Apache 2.4.58 (Win64), PHP 8.2.12.

## Cấu trúc thư mục
```
QLTV_25TH2517/
├── index.php               # trang chọn: Thủ thư hoặc Độc giả
├── .htaccess               # chặn config.php, *.sql, *.md, *.log, .git; tắt liệt kê thư mục
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
├── includes/               # file dùng chung (có .htaccess chặn mở trực tiếp bằng trình duyệt)
│   ├── .htaccess
│   ├── helpers.php         # escape, session an toàn, header bảo mật, hết hạn phiên, CSRF, thông báo
│   ├── csdl.php            # hàm chạy prepared statement
│   ├── muon-tra.php        # nghiệp vụ mượn / trả / gia hạn, tính phạt và bồi thường
│   ├── anh-bia.php         # kiểm tra và lưu ảnh bìa sách
│   ├── xac-thuc.php        # đăng nhập / đăng xuất thủ thư (khóa tạm sau 5 lần sai)
│   ├── khoi-tao-admin.php  # đầu mọi trang admin: nạp thư viện + chặn người chưa đăng nhập
│   ├── admin-dau.php, admin-cuoi.php
│   ├── xac-thuc-doc-gia.php    # đăng nhập độc giả (khóa tạm sau 5 lần sai)
│   ├── khoi-tao-tracuu.php     # đầu mọi trang tra cứu: chặn người chưa đăng nhập
│   └── tracuu-dau.php, tracuu-cuoi.php
├── assets/bootstrap/       # Bootstrap 5.3.3 (chạy được khi không có mạng)
├── database/
│   ├── .htaccess           # chặn tải trực tiếp file .sql
│   ├── schema.sql          # tạo CSDL qltv + 8 bảng (chạy trước)
│   └── du-lieu-mau.sql     # dữ liệu mẫu để demo (chạy sau)
├── uploads/                # ảnh bìa sách upload (nội dung không đưa lên Git)
│   └── .htaccess           # cấm chạy mã, chỉ cho phép đúng tên file ảnh do server sinh
├── config.mau.php          # mẫu cấu hình -> sao chép thành config.php
├── db.php                  # kết nối mysqli dùng chung
├── kiem-tra.php            # trang kiểm tra môi trường (XÓA TRƯỚC KHI NỘP)
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

Lưu ý: không chạy bằng máy chủ tích hợp `php -S`, vì máy chủ đó bỏ qua `.htaccess` nên các file cấu hình và `.sql` sẽ tải được qua URL.

## Tài khoản mẫu
| Đối tượng | Đăng nhập | Mật khẩu |
|---|---|---|
| Admin | `admin` | `admin123` |
| Thủ thư | `thuthu01`, `thuthu02` | `admin123` |
| Độc giả | mã độc giả, ví dụ `25TH0001` | `123456` |

Chỉ dùng cho dữ liệu mẫu trên máy cá nhân.

Dữ liệu mẫu gồm 40 độc giả, 72 đầu sách (10 thể loại, 10 nhà xuất bản), 77 phiếu mượn và 125 lượt mượn rải trong khoảng 6 tháng.
Ngày tháng tính theo thời điểm import (`CURDATE()`), nên lúc nào import cũng có phiếu đang mượn và phiếu quá hạn thật để demo.

Một vài mã để thử:

| Muốn thử | Dùng |
|---|---|
| Độc giả đang giữ 2 cuốn quá hạn (bị chặn mượn thêm) | `25TH0005` |
| Lịch sử có phạt trễ, sách hỏng, sách mất | `25TH0002` |
| Phiếu đã gia hạn 1/2 lần (giảng viên) | `GV002` |
| Phiếu đã dùng hết lượt gia hạn 2/2 | `GV004` |
| Tài khoản bị khóa (không đăng nhập, không mượn được) | `25TH0008`, `24TH0005` |
| Độc giả chưa có phiếu (thử lập phiếu mới, thử xóa) | `25TH0009`, `25TH0020`, `23TH0004` |
| Sách đã hết bản trên kệ | `SH0057` |
| Sách chỉ còn 1 bản | `SH0013` |

## Quy định nghiệp vụ
| Loại độc giả | Số sách tối đa | Hạn mượn | Gia hạn |
|---|---|---|---|
| Sinh viên | 3 cuốn | 7 ngày | 1 lần (+7 ngày) |
| Giảng viên | 5 cuốn | 15 ngày | 2 lần (+15 ngày) |

- Phạt trả trễ: 3.000đ / cuốn / ngày (chỉnh trong `config.php`).
- Sách hỏng: bồi thường 1 lần giá sách. Sách mất: bồi thường gấp đôi giá sách và tổng số bản của sách giảm 1.
- Không lập phiếu cho độc giả bị khóa hoặc đang giữ sách quá hạn. Không mượn hai bản của cùng một đầu sách.
- Chỉ gia hạn khi phiếu còn sách chưa trả, chưa quá hạn và còn lượt gia hạn.

## Bảo mật đã áp dụng
| Nguy cơ | Biện pháp |
|---|---|
| SQL Injection | Mọi truy vấn có dữ liệu người dùng dùng prepared statement (`bind_param`) |
| XSS | Mọi giá trị in ra HTML qua `htmlspecialchars` (hàm `e()`) hoặc ép kiểu số |
| CSRF | Mọi form ghi dữ liệu có token; xóa, khóa, trả, gia hạn, đăng xuất chỉ nhận POST |
| Lộ mật khẩu | `password_hash` / `password_verify`, tối đa 72 byte (giới hạn của bcrypt) |
| Dò mật khẩu | Mỗi lần sai chờ 1 giây; sai 5 lần liên tiếp khóa 5 phút (cả thủ thư và độc giả); báo lỗi chung, không lộ tài khoản nào có thật |
| Session fixation | `session.use_strict_mode`, đổi mã phiên sau khi đăng nhập |
| Phiên treo | Quá 30 phút không thao tác thì bị đăng xuất (chỉnh hằng `PHIEN_HET_HAN_SAU` trong `includes/helpers.php`) |
| Clickjacking | Header `X-Frame-Options`, `frame-ancestors`, `nosniff`, `Referrer-Policy` |
| Lộ file qua URL | Các file `.htaccess` chặn `config.php`, `*.sql`, thư mục `includes/` và `database/` |
| Upload ảnh độc hại | Kiểm loại ảnh theo nội dung thật (`finfo` + `getimagesize`), chỉ JPG/PNG/WebP, tối đa 2 MB, tên file do server sinh, `uploads/.htaccess` cấm chạy mã |
| Gia hạn trùng, mượn vượt số sách | Transaction và khóa dòng (`SELECT ... FOR UPDATE`) cho lập phiếu, trả sách, gia hạn |

## Chạy lại để demo hoặc nộp bài
1. Import lại `schema.sql` rồi `du-lieu-mau.sql` để dữ liệu về đúng như file mẫu (`schema.sql` xóa sạch dữ liệu cũ).
2. Import lại **không xóa** ảnh đã upload: vào thư mục `uploads/`, xóa các file ảnh, giữ lại `.gitkeep` và `.htaccess`.
3. Xóa `kiem-tra.php` trước khi nộp.

## Giới hạn đã biết
- Bộ đếm sai mật khẩu lưu trong session, nên người dùng xóa cookie là đếm lại từ đầu. Đủ cho đồ án, nếu triển khai thật nên đếm theo tài khoản và địa chỉ IP trong CSDL.
- Cookie phiên chỉ gắn cờ `secure` khi trang chạy HTTPS. Bản chạy XAMPP dùng HTTP.
- MySQL của XAMPP mặc định tài khoản `root` không mật khẩu: chỉ phù hợp khi học trên máy cá nhân.
- Độc giả không tự đặt mượn hay gia hạn online, việc này do thủ thư thực hiện tại quầy.

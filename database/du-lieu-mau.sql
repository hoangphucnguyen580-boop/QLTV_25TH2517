-- =====================================================================
-- du-lieu-mau.sql : dữ liệu mẫu để demo (chạy SAU schema.sql)
-- Quy mô: 40 độc giả, 72 đầu sách, 77 phiếu mượn, 125 dòng chi tiết.
-- Ngày tháng dùng CURDATE() +/- n ngày => lúc nào import cũng có phiếu "đang mượn" và "quá hạn" thật,
-- lịch sử mượn - trả rải đều khoảng 6 tháng gần nhất để thống kê theo tháng có nhiều cột.
-- Thông tin sách (năm, số trang, giá) và độc giả là dữ liệu giả lập để test, không phải số liệu thật.
-- Mật khẩu mẫu: nhân viên "admin"/"thuthu01"/"thuthu02" = admin123 ; mọi độc giả = 123456
--
-- Quy ước quan trọng:
--  * sach.so_luong là số bản HIỆN CÓ (đã trừ các bản bị mất trong lịch sử). Khai đúng ngay trong INSERT,
--    đừng sửa bằng UPDATE riêng vì CHECK (so_con_lai <= so_luong) sẽ chặn. Đổi số lượng thì đổi cả hai cột.
--  * so_con_lai được đồng bộ ở cuối file = so_luong - số bản đang được mượn (chưa trả).
--  * Mã phiếu đánh theo thứ tự thời gian (PM0001 là phiếu cũ nhất).
--
-- Số liệu kỳ vọng (không phụ thuộc ngày import):
--   tổng phạt trễ đã thu    = 417.000 đ
--   tổng bồi thường đã thu  = 928.000 đ
--   cuốn đã trả / tổng lượt mượn = 81 / 125
--   phiếu: 54 đã trả, 17 đang mượn còn hạn, 6 quá hạn ; độc giả hoạt động: 38
--   25TH0002: 4 lượt đã trả, phạt 87.000 đ, đền 265.000 đ
--   25TH0001: 3 lượt đã trả, phạt 0 đ, đền 0 đ
--   25TH0003: 1 lượt đã trả, phạt 0 đ, đền 150.000 đ
--   sách đang hết bản trên kệ (so_con_lai = 0): SH0057 ; sách còn đúng 1 bản: SH0013
-- =====================================================================
SET NAMES utf8mb4;
USE qltv;

-- ---- Quy định mượn (mục 1 kế hoạch) ----
INSERT INTO loaidocgia (ten_loai, so_sach_toi_da, so_ngay_muon, so_lan_gia_han) VALUES
  ('Sinh viên', 3, 7, 1),  -- id 1
  ('Giảng viên', 5, 15, 2);  -- id 2

-- ---- Thể loại ----
INSERT INTO theloai (ten_theloai) VALUES
  ('Công nghệ thông tin'),  -- 1
  ('Văn học'),  -- 2
  ('Khoa học'),  -- 3
  ('Kinh tế'),  -- 4
  ('Thiếu nhi'),  -- 5
  ('Lịch sử'),  -- 6
  ('Kỹ năng sống'),  -- 7
  ('Ngoại ngữ'),  -- 8
  ('Toán - Thống kê'),  -- 9
  ('Giáo trình đại cương');  -- 10

-- ---- Nhà xuất bản ----
INSERT INTO nhaxuatban (ten_nxb, dia_chi) VALUES
  ('NXB Giáo dục Việt Nam', 'Hà Nội'),  -- 1
  ('NXB Trẻ', 'TP. Hồ Chí Minh'),  -- 2
  ('NXB Kim Đồng', 'Hà Nội'),  -- 3
  ('NXB Khoa học và Kỹ thuật', 'Hà Nội'),  -- 4
  ('NXB Văn học', 'Hà Nội'),  -- 5
  ('NXB Tổng hợp TP.HCM', 'TP. Hồ Chí Minh'),  -- 6
  ('NXB Thanh Niên', 'Hà Nội'),  -- 7
  ('NXB Lao Động', 'Hà Nội'),  -- 8
  ('NXB Đại học Quốc gia TP.HCM', 'TP. Hồ Chí Minh'),  -- 9
  ('NXB Hội Nhà văn', 'Hà Nội');  -- 10

-- ---- Nhân viên (mật khẩu: admin123) ----
INSERT INTO nhanvien (ho_ten, ten_dang_nhap, mat_khau, vai_tro) VALUES
  ('Nguyễn Hoàng Phúc', 'admin', '$2y$10$gda1Rm88z1V9wn4r6CNBROQ4rMHoVidfqTQ9MxVxBKGRnoXc43s5u', 'admin'),  -- id 1
  ('Lê Thị Thu Hà', 'thuthu01', '$2y$10$gda1Rm88z1V9wn4r6CNBROQ4rMHoVidfqTQ9MxVxBKGRnoXc43s5u', 'thu_thu'),  -- id 2
  ('Phạm Minh Quân', 'thuthu02', '$2y$10$gda1Rm88z1V9wn4r6CNBROQ4rMHoVidfqTQ9MxVxBKGRnoXc43s5u', 'thu_thu');  -- id 3

-- ---- Độc giả (mật khẩu: 123456) ----
-- id_loai: 1 = Sinh viên, 2 = Giảng viên
-- Cố ý để trống phiếu: 25TH0009, 25TH0020, 23TH0004 (thử lập phiếu mới và thử xóa độc giả)
-- Tài khoản bị khóa: 25TH0008, 24TH0005
INSERT INTO docgia (ma_doc_gia, ho_ten, ngay_sinh, gioi_tinh, dia_chi, sdt, email, id_loai, mat_khau, ngay_dang_ky, trang_thai) VALUES
  ('25TH0001', 'Nguyễn Văn An', '2007-03-12', 'nam', 'Nha Trang', '0900000001', 'an@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 230 DAY, 'hoat_dong'),  -- 1
  ('25TH0002', 'Trần Thị Bình', '2007-07-25', 'nu', 'Nha Trang', '0900000002', 'binh@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 232 DAY, 'hoat_dong'),  -- 2
  ('25TH0003', 'Lê Hoàng Cường', '2006-11-02', 'nam', 'Cam Ranh', '0900000003', 'cuong@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 234 DAY, 'hoat_dong'),  -- 3
  ('25TH0004', 'Phạm Thu Dung', '2007-01-18', 'nu', 'Nha Trang', '0900000004', 'dung@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 236 DAY, 'hoat_dong'),  -- 4
  ('25TH0005', 'Võ Minh Đạt', '2006-09-30', 'nam', 'Diên Khánh', '0900000005', 'dat@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 238 DAY, 'hoat_dong'),  -- 5
  ('25TH0006', 'Đặng Ngọc Hà', '2007-05-14', 'nu', 'Nha Trang', '0900000006', 'ha@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 240 DAY, 'hoat_dong'),  -- 6
  ('25TH0007', 'Bùi Quốc Huy', '2006-12-08', 'nam', 'Ninh Hòa', '0900000007', 'huy@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 242 DAY, 'hoat_dong'),  -- 7
  ('25TH0008', 'Hồ Thị Lan', '2007-02-21', 'nu', 'Nha Trang', '0900000008', 'lan@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 244 DAY, 'khoa'),  -- 8
  ('25TH0009', 'Phan Gia Hân', '2007-08-16', 'nu', 'Nha Trang', '0900000012', 'han@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 246 DAY, 'hoat_dong'),  -- 9
  ('25TH0010', 'Nguyễn Thanh Khôi', '2007-04-09', 'nam', 'Nha Trang', '0900000013', 'khoi.nt@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 248 DAY, 'hoat_dong'),  -- 10
  ('25TH0011', 'Trương Mỹ Linh', '2006-10-27', 'nu', 'Nha Trang', '0900000014', 'linh.tm@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 250 DAY, 'hoat_dong'),  -- 11
  ('25TH0012', 'Lý Quang Minh', '2007-06-03', 'nam', 'Vạn Ninh', '0900000015', 'minh.lq@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 252 DAY, 'hoat_dong'),  -- 12
  ('25TH0013', 'Đinh Thảo Nguyên', '2007-09-19', 'nu', 'Nha Trang', '0900000016', 'nguyen.dt@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 254 DAY, 'hoat_dong'),  -- 13
  ('25TH0014', 'Huỳnh Đức Phát', '2006-08-22', 'nam', 'Cam Lâm', '0900000017', 'phat.hd@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 256 DAY, 'hoat_dong'),  -- 14
  ('25TH0015', 'Mai Ngọc Quỳnh', '2007-12-01', 'nu', 'Nha Trang', '0900000018', 'quynh.mn@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 258 DAY, 'hoat_dong'),  -- 15
  ('25TH0016', 'Tạ Anh Quân', '2007-02-14', 'nam', 'Ninh Hòa', '0900000019', 'quan.ta@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 260 DAY, 'hoat_dong'),  -- 16
  ('25TH0017', 'Dương Khánh Ly', '2006-07-30', 'nu', 'Nha Trang', '0900000020', 'ly.dk@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 262 DAY, 'hoat_dong'),  -- 17
  ('25TH0018', 'Châu Minh Tuấn', '2007-10-05', 'nam', 'Diên Khánh', '0900000021', 'tuan.cm@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 264 DAY, 'hoat_dong'),  -- 18
  ('25TH0019', 'Ngô Bảo Trân', '2007-03-28', 'nu', 'Nha Trang', '0900000022', 'tran.nb@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 266 DAY, 'hoat_dong'),  -- 19
  ('25TH0020', 'Cao Gia Bảo', '2006-05-17', 'nam', 'Khánh Vĩnh', '0900000023', 'bao.cg@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 268 DAY, 'hoat_dong'),  -- 20
  ('24TH0001', 'Lâm Hữu Đức', '2006-01-11', 'nam', 'Nha Trang', '0900000024', 'duc.lh@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 540 DAY, 'hoat_dong'),  -- 21
  ('24TH0002', 'Vũ Thị Hương', '2005-11-23', 'nu', 'Nha Trang', '0900000025', 'huong.vt@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 543 DAY, 'hoat_dong'),  -- 22
  ('24TH0003', 'Đoàn Minh Khang', '2005-08-07', 'nam', 'Cam Ranh', '0900000026', 'khang.dm@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 546 DAY, 'hoat_dong'),  -- 23
  ('24TH0004', 'Phùng Thị Thanh', '2006-02-26', 'nu', 'Nha Trang', '0900000027', 'thanh.pt@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 549 DAY, 'hoat_dong'),  -- 24
  ('24TH0005', 'Kiều Văn Thắng', '2005-06-15', 'nam', 'Nha Trang', '0900000028', 'thang.kv@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 552 DAY, 'khoa'),  -- 25
  ('24TH0006', 'Lương Bích Ngọc', '2005-09-09', 'nu', 'Diên Khánh', '0900000029', 'ngoc.lb@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 555 DAY, 'hoat_dong'),  -- 26
  ('24TH0007', 'Thái Quốc Việt', '2006-04-04', 'nam', 'Ninh Hòa', '0900000030', 'viet.tq@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 558 DAY, 'hoat_dong'),  -- 27
  ('24TH0008', 'Hà Phương Anh', '2005-12-30', 'nu', 'Nha Trang', '0900000031', 'anh.hp@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 561 DAY, 'hoat_dong'),  -- 28
  ('23TH0001', 'Nguyễn Công Thành', '2004-05-20', 'nam', 'Nha Trang', '0900000032', 'thanh.nc@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 934 DAY, 'hoat_dong'),  -- 29
  ('23TH0002', 'Đỗ Thị Yến', '2004-10-12', 'nu', 'Cam Ranh', '0900000033', 'yen.dt@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 937 DAY, 'hoat_dong'),  -- 30
  ('23TH0003', 'Bạch Minh Hoàng', '2004-03-03', 'nam', 'Vạn Ninh', '0900000034', 'hoang.bm@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 940 DAY, 'hoat_dong'),  -- 31
  ('23TH0004', 'Tô Ngọc Diệp', '2004-07-21', 'nu', 'Nha Trang', '0900000035', 'diep.tn@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 943 DAY, 'hoat_dong'),  -- 32
  ('GV001', 'Nguyễn Thanh Tùng', '1980-04-10', 'nam', 'Nha Trang', '0900000009', 'tung@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1640 DAY, 'hoat_dong'),  -- 33
  ('GV002', 'Lê Thị Mai', '1985-06-27', 'nu', 'Nha Trang', '0900000010', 'mai@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1660 DAY, 'hoat_dong'),  -- 34
  ('GV003', 'Trần Quốc Bảo', '1978-10-03', 'nam', 'Nha Trang', '0900000011', 'bao@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1680 DAY, 'hoat_dong'),  -- 35
  ('GV004', 'Phạm Thị Hồng Nhung', '1983-02-15', 'nu', 'Nha Trang', '0900000036', 'nhung.pth@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1700 DAY, 'hoat_dong'),  -- 36
  ('GV005', 'Võ Anh Tuấn', '1976-09-08', 'nam', 'Nha Trang', '0900000037', 'tuan.va@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1720 DAY, 'hoat_dong'),  -- 37
  ('GV006', 'Nguyễn Thị Kim Chi', '1988-11-30', 'nu', 'Diên Khánh', '0900000038', 'chi.ntk@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1740 DAY, 'hoat_dong'),  -- 38
  ('GV007', 'Lê Minh Trí', '1990-01-22', 'nam', 'Nha Trang', '0900000039', 'tri.lm@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1760 DAY, 'hoat_dong'),  -- 39
  ('GV008', 'Đặng Thu Thủy', '1982-07-14', 'nu', 'Cam Ranh', '0900000040', 'thuy.dt@example.com', 2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', CURDATE() - INTERVAL 1780 DAY, 'hoat_dong');  -- 40

-- ---- Sách (so_con_lai tạm = so_luong, cuối file sẽ tự trừ theo phiếu đang mượn) ----
-- cột: ma_sach, ten_sach, tac_gia, id_theloai, id_nxb, nam_xuat_ban, so_trang, gia_tien, so_luong, so_con_lai, mo_ta
INSERT INTO sach (ma_sach, ten_sach, tac_gia, id_theloai, id_nxb, nam_xuat_ban, so_trang, gia_tien, so_luong, so_con_lai, mo_ta) VALUES
  ('SH0001', 'Dế Mèn phiêu lưu ký', 'Tô Hoài', 5, 3, 2018, 160, 45000, 4, 4, 'Truyện thiếu nhi kinh điển.'),  -- 1
  ('SH0002', 'Số đỏ', 'Vũ Trọng Phụng', 2, 5, 2019, 320, 80000, 3, 3, 'Tiểu thuyết trào phúng.'),  -- 2
  ('SH0003', 'Tắt đèn', 'Ngô Tất Tố', 2, 5, 2017, 200, 60000, 3, 3, 'Tiểu thuyết hiện thực.'),  -- 3
  ('SH0004', 'Lão Hạc', 'Nam Cao', 2, 5, 2018, 120, 50000, 3, 3, 'Truyện ngắn.'),  -- 4
  ('SH0005', 'Mắt biếc', 'Nguyễn Nhật Ánh', 2, 2, 2020, 300, 90000, 5, 5, 'Truyện dài.'),  -- 5
  ('SH0006', 'Cho tôi xin một vé đi tuổi thơ', 'Nguyễn Nhật Ánh', 2, 2, 2019, 220, 85000, 5, 5, 'Truyện dài.'),  -- 6
  ('SH0007', 'Nhà giả kim', 'Paulo Coelho', 2, 5, 2020, 228, 79000, 4, 4, 'Tiểu thuyết.'),  -- 7
  ('SH0008', 'Đắc nhân tâm', 'Dale Carnegie', 7, 6, 2021, 320, 86000, 5, 5, 'Sách kỹ năng giao tiếp.'),  -- 8
  ('SH0009', 'Nghĩ giàu làm giàu', 'Napoleon Hill', 4, 6, 2020, 380, 98000, 3, 3, 'Sách phát triển bản thân.'),  -- 9
  ('SH0010', 'Nguyên lý kinh tế học', 'N. Gregory Mankiw', 4, 1, 2019, 700, 250000, 2, 2, 'Giáo trình kinh tế.'),  -- 10
  ('SH0011', 'Lược sử thời gian', 'Stephen Hawking', 3, 2, 2018, 280, 120000, 3, 3, 'Khoa học phổ thông.'),  -- 11
  ('SH0012', 'Sapiens: Lược sử loài người', 'Yuval Noah Harari', 6, 6, 2021, 560, 220000, 4, 4, 'Lịch sử loài người.'),  -- 12
  ('SH0013', 'Việt Nam sử lược', 'Trần Trọng Kim', 6, 6, 2017, 600, 90000, 1, 1, 'Sử học.'),  -- 13 (chỉ còn 1 bản vì 1 bản đã mất trong lịch sử mượn)
  ('SH0014', 'Giáo trình Cơ sở dữ liệu', 'Nhiều tác giả', 1, 1, 2022, 350, 95000, 6, 6, 'Giáo trình CSDL.'),  -- 14
  ('SH0015', 'Giáo trình Công nghệ phần mềm', 'Nhiều tác giả', 1, 1, 2022, 400, 110000, 6, 6, 'Giáo trình CNPM.'),  -- 15
  ('SH0016', 'Lập trình C++ căn bản', 'Nhiều tác giả', 1, 4, 2021, 420, 105000, 5, 5, 'Giáo trình C++.'),  -- 16
  ('SH0017', 'Clean Code', 'Robert C. Martin', 1, 4, 2020, 464, 150000, 3, 3, 'Viết mã sạch.'),  -- 17
  ('SH0018', 'Introduction to Algorithms', 'Thomas H. Cormen và cộng sự', 1, 4, 2022, 1312, 450000, 2, 2, 'Sách thuật toán.'),  -- 18
  ('SH0019', 'Doraemon tập 1', 'Fujiko F. Fujio', 5, 3, 2020, 190, 25000, 8, 8, 'Truyện tranh thiếu nhi.'),  -- 19
  ('SH0020', 'Tuổi thơ dữ dội', 'Phùng Quán', 2, 3, 2019, 800, 130000, 3, 3, 'Tiểu thuyết.'),  -- 20
  ('SH0021', 'Clean Architecture', 'Robert C. Martin', 1, 4, 2019, 432, 180000, 2, 2, 'Kiến trúc phần mềm sạch.'),  -- 21
  ('SH0022', 'The Pragmatic Programmer', 'Andrew Hunt, David Thomas', 1, 4, 2020, 352, 165000, 2, 2, 'Nghề lập trình thực chiến.'),  -- 22
  ('SH0023', 'Design Patterns', 'Erich Gamma và cộng sự', 1, 4, 2018, 416, 175000, 2, 2, 'Các mẫu thiết kế hướng đối tượng.'),  -- 23
  ('SH0024', 'Code Complete', 'Steve McConnell', 1, 4, 2019, 960, 320000, 2, 2, 'Xây dựng phần mềm.'),  -- 24
  ('SH0025', 'Head First Java', 'Kathy Sierra, Bert Bates', 1, 4, 2021, 720, 260000, 3, 3, 'Học Java bằng hình ảnh.'),  -- 25
  ('SH0026', 'Refactoring', 'Martin Fowler', 1, 4, 2020, 448, 210000, 2, 2, 'Cải tiến cấu trúc mã nguồn.'),  -- 26
  ('SH0027', 'Python Crash Course', 'Eric Matthes', 1, 4, 2022, 544, 230000, 3, 3, 'Học Python qua dự án.'),  -- 27
  ('SH0028', 'Learning PHP, MySQL & JavaScript', 'Robin Nixon', 1, 4, 2021, 800, 280000, 3, 3, 'Lập trình web với PHP và MySQL.'),  -- 28
  ('SH0029', 'JavaScript: The Good Parts', 'Douglas Crockford', 1, 4, 2018, 176, 120000, 2, 2, 'Những phần tốt của JavaScript.'),  -- 29
  ('SH0030', 'Database System Concepts', 'Abraham Silberschatz và cộng sự', 1, 1, 2020, 1376, 420000, 2, 2, 'Giáo trình hệ cơ sở dữ liệu.'),  -- 30
  ('SH0031', 'Computer Networking: A Top-Down Approach', 'James Kurose, Keith Ross', 1, 1, 2021, 864, 380000, 2, 2, 'Giáo trình mạng máy tính.'),  -- 31
  ('SH0032', 'Operating System Concepts', 'Abraham Silberschatz và cộng sự', 1, 1, 2021, 1136, 400000, 2, 2, 'Giáo trình hệ điều hành.'),  -- 32
  ('SH0033', 'Artificial Intelligence: A Modern Approach', 'Stuart Russell, Peter Norvig', 1, 4, 2022, 1136, 480000, 2, 2, 'Giáo trình trí tuệ nhân tạo.'),  -- 33
  ('SH0034', 'Giáo trình Mạng máy tính', 'Nhiều tác giả', 1, 1, 2021, 320, 90000, 6, 6, 'Giáo trình mạng máy tính.'),  -- 34
  ('SH0035', 'Giáo trình Cấu trúc dữ liệu và giải thuật', 'Nhiều tác giả', 1, 1, 2021, 380, 100000, 6, 6, 'Giáo trình CTDL và giải thuật.'),  -- 35
  ('SH0036', 'Giáo trình Lập trình hướng đối tượng', 'Nhiều tác giả', 1, 1, 2022, 340, 95000, 5, 5, 'Giáo trình OOP.'),  -- 36
  ('SH0037', 'Giáo trình Lập trình Web', 'Nhiều tác giả', 1, 1, 2022, 360, 98000, 5, 5, 'Giáo trình lập trình web.'),  -- 37
  ('SH0038', 'Truyện Kiều', 'Nguyễn Du', 2, 5, 2018, 280, 70000, 4, 4, 'Truyện thơ Nôm.'),  -- 38
  ('SH0039', 'Chí Phèo', 'Nam Cao', 2, 5, 2019, 110, 45000, 3, 3, 'Truyện ngắn.'),  -- 39
  ('SH0040', 'Vợ nhặt', 'Kim Lân', 2, 5, 2019, 150, 50000, 3, 3, 'Truyện ngắn.'),  -- 40
  ('SH0041', 'Nỗi buồn chiến tranh', 'Bảo Ninh', 2, 10, 2020, 320, 95000, 2, 2, 'Tiểu thuyết.'),  -- 41
  ('SH0042', 'Tôi thấy hoa vàng trên cỏ xanh', 'Nguyễn Nhật Ánh', 2, 2, 2020, 380, 98000, 4, 4, 'Truyện dài.'),  -- 42
  ('SH0043', 'Cánh đồng bất tận', 'Nguyễn Ngọc Tư', 2, 2, 2019, 200, 75000, 2, 2, 'Tập truyện.'),  -- 43
  ('SH0044', 'Những người khốn khổ', 'Victor Hugo', 2, 5, 2021, 1400, 220000, 2, 2, 'Tiểu thuyết kinh điển.'),  -- 44
  ('SH0045', 'Ông già và biển cả', 'Ernest Hemingway', 2, 5, 2020, 140, 55000, 3, 3, 'Truyện vừa.'),  -- 45
  ('SH0046', 'Rừng Na Uy', 'Haruki Murakami', 2, 10, 2020, 540, 140000, 2, 2, 'Tiểu thuyết.'),  -- 46
  ('SH0047', 'Vũ trụ trong vỏ hạt dẻ', 'Stephen Hawking', 3, 2, 2019, 240, 115000, 2, 2, 'Khoa học phổ thông.'),  -- 47
  ('SH0048', 'Cosmos', 'Carl Sagan', 3, 2, 2020, 520, 180000, 2, 2, 'Khoa học phổ thông.'),  -- 48
  ('SH0049', 'Nguồn gốc các loài', 'Charles Darwin', 3, 4, 2018, 600, 160000, 2, 2, 'Sinh học tiến hóa.'),  -- 49
  ('SH0050', 'Cha giàu cha nghèo', 'Robert T. Kiyosaki', 4, 8, 2021, 340, 92000, 4, 4, 'Tài chính cá nhân.'),  -- 50
  ('SH0051', 'Khởi nghiệp tinh gọn', 'Eric Ries', 4, 6, 2020, 360, 105000, 2, 2, 'Khởi nghiệp.'),  -- 51
  ('SH0052', 'Từ tốt đến vĩ đại', 'Jim Collins', 4, 8, 2019, 400, 120000, 2, 2, 'Quản trị doanh nghiệp.'),  -- 52
  ('SH0053', 'Hoàng tử bé', 'Antoine de Saint-Exupéry', 5, 3, 2020, 120, 48000, 5, 5, 'Truyện thiếu nhi kinh điển.'),  -- 53
  ('SH0054', 'Thám tử lừng danh Conan tập 1', 'Aoyama Gosho', 5, 3, 2020, 180, 22000, 6, 6, 'Truyện tranh trinh thám.'),  -- 54
  ('SH0055', 'Harry Potter và Hòn đá Phù thủy', 'J. K. Rowling', 5, 2, 2019, 400, 110000, 4, 4, 'Truyện giả tưởng.'),  -- 55
  ('SH0056', 'Súng, vi trùng và thép', 'Jared Diamond', 6, 6, 2021, 580, 195000, 2, 2, 'Lịch sử văn minh.'),  -- 56
  ('SH0057', 'Đại Việt sử ký toàn thư', 'Ngô Sĩ Liên và các sử thần', 6, 6, 2020, 1400, 350000, 1, 1, 'Bộ chính sử.'),  -- 57
  ('SH0058', 'Thói quen nguyên tử', 'James Clear', 7, 2, 2021, 320, 110000, 4, 4, 'Xây dựng thói quen.'),  -- 58
  ('SH0059', '7 thói quen của người thành đạt', 'Stephen R. Covey', 7, 6, 2019, 440, 125000, 3, 3, 'Phát triển bản thân.'),  -- 59
  ('SH0060', 'Tuổi trẻ đáng giá bao nhiêu', 'Rosie Nguyễn', 7, 10, 2018, 300, 80000, 4, 4, 'Sách dành cho người trẻ.'),  -- 60
  ('SH0061', 'English Grammar in Use', 'Raymond Murphy', 8, 9, 2020, 380, 150000, 4, 4, 'Ngữ pháp tiếng Anh.'),  -- 61
  ('SH0062', 'Giáo trình Hán ngữ tập 1', 'Dương Ký Châu', 8, 9, 2019, 250, 85000, 4, 4, 'Giáo trình tiếng Trung.'),  -- 62
  ('SH0063', 'Từ điển Anh - Việt', 'Nhiều tác giả', 8, 1, 2018, 1800, 250000, 2, 2, 'Từ điển.'),  -- 63
  ('SH0064', 'Toán cao cấp tập 1', 'Nguyễn Đình Trí', 9, 1, 2019, 400, 90000, 5, 5, 'Giáo trình toán đại cương.'),  -- 64
  ('SH0065', 'Xác suất thống kê', 'Đào Hữu Hồ', 9, 1, 2018, 260, 70000, 5, 5, 'Giáo trình xác suất thống kê.'),  -- 65
  ('SH0066', 'Toán rời rạc', 'Nguyễn Hữu Anh', 9, 1, 2019, 320, 80000, 4, 4, 'Giáo trình toán rời rạc.'),  -- 66
  ('SH0067', 'Đại số tuyến tính', 'Nhiều tác giả', 9, 9, 2020, 280, 75000, 4, 4, 'Giáo trình đại số tuyến tính.'),  -- 67
  ('SH0068', 'Giáo trình Triết học Mác - Lênin', 'Bộ Giáo dục và Đào tạo', 10, 1, 2021, 480, 60000, 6, 6, 'Giáo trình lý luận chính trị.'),  -- 68
  ('SH0069', 'Giáo trình Pháp luật đại cương', 'Nhiều tác giả', 10, 1, 2021, 260, 55000, 5, 5, 'Giáo trình đại cương.'),  -- 69
  ('SH0070', 'Giáo trình Tư tưởng Hồ Chí Minh', 'Bộ Giáo dục và Đào tạo', 10, 1, 2021, 330, 58000, 5, 5, 'Giáo trình lý luận chính trị.'),  -- 70
  ('SH0071', 'Lập trình C# căn bản', 'Nhiều tác giả', 1, 4, 2021, 450, 108000, 4, 4, 'Giáo trình C#.'),  -- 71
  ('SH0072', 'Giáo trình SQL Server', 'Nhiều tác giả', 1, 4, 2021, 400, 102000, 3, 3, 'Giáo trình SQL Server.');  -- 72

-- ---- Phiếu mượn ----
-- Cột: ma_phieu, id_doc_gia, id_nhan_vien, ngay_muon, ngay_hen_tra, so_lan_gia_han_da_dung, trang_thai
-- ngay_hen_tra = ngay_muon + so_ngay_muon x (1 + số lần gia hạn đã dùng)
INSERT INTO phieumuon (ma_phieu, id_doc_gia, id_nhan_vien, ngay_muon, ngay_hen_tra, so_lan_gia_han_da_dung, trang_thai) VALUES
  ('PM0001', 12, 3, CURDATE() - INTERVAL 173 DAY, CURDATE() - INTERVAL 166 DAY, 0, 'da_tra'),  -- 25TH0012: đã trả hết
  ('PM0002', 29, 1, CURDATE() - INTERVAL 172 DAY, CURDATE() - INTERVAL 165 DAY, 0, 'da_tra'),  -- 23TH0001: đã trả hết
  ('PM0003', 24, 1, CURDATE() - INTERVAL 171 DAY, CURDATE() - INTERVAL 164 DAY, 0, 'da_tra'),  -- 24TH0004: đã trả hết
  ('PM0004', 22, 3, CURDATE() - INTERVAL 169 DAY, CURDATE() - INTERVAL 155 DAY, 1, 'da_tra'),  -- 24TH0002: đã trả hết ; đã gia hạn 1/1
  ('PM0005', 30, 3, CURDATE() - INTERVAL 165 DAY, CURDATE() - INTERVAL 158 DAY, 0, 'da_tra'),  -- 23TH0002: đã trả hết
  ('PM0006', 11, 2, CURDATE() - INTERVAL 165 DAY, CURDATE() - INTERVAL 151 DAY, 1, 'da_tra'),  -- 25TH0011: đã trả hết ; đã gia hạn 1/1
  ('PM0007', 25, 1, CURDATE() - INTERVAL 163 DAY, CURDATE() - INTERVAL 156 DAY, 0, 'da_tra'),  -- 24TH0005: đã trả hết
  ('PM0008', 37, 2, CURDATE() - INTERVAL 161 DAY, CURDATE() - INTERVAL 146 DAY, 0, 'da_tra'),  -- GV005: đã trả hết
  ('PM0009', 14, 3, CURDATE() - INTERVAL 156 DAY, CURDATE() - INTERVAL 149 DAY, 0, 'da_tra'),  -- 25TH0014: đã trả hết
  ('PM0010', 39, 2, CURDATE() - INTERVAL 151 DAY, CURDATE() - INTERVAL 121 DAY, 1, 'da_tra'),  -- GV007: đã trả hết ; đã gia hạn 1/2
  ('PM0011', 16, 3, CURDATE() - INTERVAL 149 DAY, CURDATE() - INTERVAL 142 DAY, 0, 'da_tra'),  -- 25TH0016: đã trả hết
  ('PM0012', 21, 2, CURDATE() - INTERVAL 146 DAY, CURDATE() - INTERVAL 139 DAY, 0, 'da_tra'),  -- 24TH0001: đã trả hết
  ('PM0013', 36, 2, CURDATE() - INTERVAL 144 DAY, CURDATE() - INTERVAL 129 DAY, 0, 'da_tra'),  -- GV004: đã trả hết
  ('PM0014', 29, 2, CURDATE() - INTERVAL 139 DAY, CURDATE() - INTERVAL 132 DAY, 0, 'da_tra'),  -- 23TH0001: đã trả hết
  ('PM0015', 27, 2, CURDATE() - INTERVAL 133 DAY, CURDATE() - INTERVAL 126 DAY, 0, 'da_tra'),  -- 24TH0007: đã trả hết
  ('PM0016', 28, 3, CURDATE() - INTERVAL 126 DAY, CURDATE() - INTERVAL 119 DAY, 0, 'da_tra'),  -- 24TH0008: đã trả hết
  ('PM0017', 12, 2, CURDATE() - INTERVAL 125 DAY, CURDATE() - INTERVAL 118 DAY, 0, 'da_tra'),  -- 25TH0012: đã trả hết
  ('PM0018', 38, 2, CURDATE() - INTERVAL 121 DAY, CURDATE() - INTERVAL 106 DAY, 0, 'da_tra'),  -- GV006: đã trả hết
  ('PM0019', 11, 2, CURDATE() - INTERVAL 121 DAY, CURDATE() - INTERVAL 114 DAY, 0, 'da_tra'),  -- 25TH0011: đã trả hết
  ('PM0020', 28, 3, CURDATE() - INTERVAL 113 DAY, CURDATE() - INTERVAL 106 DAY, 0, 'da_tra'),  -- 24TH0008: đã trả hết
  ('PM0021', 2, 1, CURDATE() - INTERVAL 110 DAY, CURDATE() - INTERVAL 103 DAY, 0, 'da_tra'),  -- 25TH0002: đã trả hết
  ('PM0022', 35, 1, CURDATE() - INTERVAL 100 DAY, CURDATE() - INTERVAL 85 DAY, 0, 'da_tra'),  -- GV003: đã trả hết
  ('PM0023', 38, 2, CURDATE() - INTERVAL 100 DAY, CURDATE() - INTERVAL 85 DAY, 0, 'da_tra'),  -- GV006: đã trả hết
  ('PM0024', 26, 2, CURDATE() - INTERVAL 99 DAY, CURDATE() - INTERVAL 92 DAY, 0, 'da_tra'),  -- 24TH0006: đã trả hết
  ('PM0025', 23, 2, CURDATE() - INTERVAL 98 DAY, CURDATE() - INTERVAL 91 DAY, 0, 'da_tra'),  -- 24TH0003: đã trả hết
  ('PM0026', 10, 3, CURDATE() - INTERVAL 92 DAY, CURDATE() - INTERVAL 85 DAY, 0, 'da_tra'),  -- 25TH0010: đã trả hết
  ('PM0027', 40, 2, CURDATE() - INTERVAL 91 DAY, CURDATE() - INTERVAL 61 DAY, 1, 'da_tra'),  -- GV008: đã trả hết ; đã gia hạn 1/2
  ('PM0028', 19, 1, CURDATE() - INTERVAL 90 DAY, CURDATE() - INTERVAL 83 DAY, 0, 'da_tra'),  -- 25TH0019: đã trả hết
  ('PM0029', 14, 3, CURDATE() - INTERVAL 90 DAY, CURDATE() - INTERVAL 83 DAY, 0, 'da_tra'),  -- 25TH0014: đã trả hết
  ('PM0030', 29, 2, CURDATE() - INTERVAL 83 DAY, CURDATE() - INTERVAL 76 DAY, 0, 'da_tra'),  -- 23TH0001: đã trả hết
  ('PM0031', 26, 2, CURDATE() - INTERVAL 78 DAY, CURDATE() - INTERVAL 71 DAY, 0, 'da_tra'),  -- 24TH0006: đã trả hết
  ('PM0032', 1, 2, CURDATE() - INTERVAL 75 DAY, CURDATE() - INTERVAL 68 DAY, 0, 'da_tra'),  -- 25TH0001: đã trả hết
  ('PM0033', 21, 2, CURDATE() - INTERVAL 75 DAY, CURDATE() - INTERVAL 68 DAY, 0, 'da_tra'),  -- 24TH0001: đã trả hết
  ('PM0034', 13, 2, CURDATE() - INTERVAL 64 DAY, CURDATE() - INTERVAL 57 DAY, 0, 'da_tra'),  -- 25TH0013: đã trả hết
  ('PM0035', 26, 2, CURDATE() - INTERVAL 63 DAY, CURDATE() - INTERVAL 56 DAY, 0, 'da_tra'),  -- 24TH0006: đã trả hết
  ('PM0036', 2, 1, CURDATE() - INTERVAL 60 DAY, CURDATE() - INTERVAL 53 DAY, 0, 'da_tra'),  -- 25TH0002: đã trả hết
  ('PM0037', 10, 2, CURDATE() - INTERVAL 59 DAY, CURDATE() - INTERVAL 52 DAY, 0, 'da_tra'),  -- 25TH0010: đã trả hết
  ('PM0038', 28, 2, CURDATE() - INTERVAL 54 DAY, CURDATE() - INTERVAL 47 DAY, 0, 'da_tra'),  -- 24TH0008: đã trả hết
  ('PM0039', 18, 1, CURDATE() - INTERVAL 54 DAY, CURDATE() - INTERVAL 47 DAY, 0, 'da_tra'),  -- 25TH0018: đã trả hết
  ('PM0040', 4, 1, CURDATE() - INTERVAL 50 DAY, CURDATE() - INTERVAL 43 DAY, 0, 'da_tra'),  -- 25TH0004: đã trả hết
  ('PM0041', 21, 1, CURDATE() - INTERVAL 48 DAY, CURDATE() - INTERVAL 41 DAY, 0, 'da_tra'),  -- 24TH0001: đã trả hết
  ('PM0042', 23, 3, CURDATE() - INTERVAL 44 DAY, CURDATE() - INTERVAL 37 DAY, 0, 'da_tra'),  -- 24TH0003: đã trả hết
  ('PM0043', 29, 3, CURDATE() - INTERVAL 43 DAY, CURDATE() - INTERVAL 36 DAY, 0, 'da_tra'),  -- 23TH0001: đã trả hết
  ('PM0044', 22, 3, CURDATE() - INTERVAL 42 DAY, CURDATE() - INTERVAL 35 DAY, 0, 'da_tra'),  -- 24TH0002: đã trả hết
  ('PM0045', 3, 1, CURDATE() - INTERVAL 40 DAY, CURDATE() - INTERVAL 33 DAY, 0, 'da_tra'),  -- 25TH0003: đã trả hết
  ('PM0046', 30, 2, CURDATE() - INTERVAL 40 DAY, CURDATE() - INTERVAL 33 DAY, 0, 'da_tra'),  -- 23TH0002: đã trả hết
  ('PM0047', 17, 1, CURDATE() - INTERVAL 39 DAY, CURDATE() - INTERVAL 32 DAY, 0, 'da_tra'),  -- 25TH0017: đã trả hết
  ('PM0048', 11, 2, CURDATE() - INTERVAL 38 DAY, CURDATE() - INTERVAL 31 DAY, 0, 'da_tra'),  -- 25TH0011: đã trả hết
  ('PM0049', 36, 2, CURDATE() - INTERVAL 33 DAY, CURDATE() + INTERVAL 12 DAY, 2, 'dang_muon'),  -- GV004: đang mượn, còn 12 ngày ; đã gia hạn 2/2
  ('PM0050', 28, 3, CURDATE() - INTERVAL 31 DAY, CURDATE() - INTERVAL 24 DAY, 0, 'da_tra'),  -- 24TH0008: đã trả hết
  ('PM0051', 24, 3, CURDATE() - INTERVAL 31 DAY, CURDATE() - INTERVAL 24 DAY, 0, 'da_tra'),  -- 24TH0004: đã trả hết
  ('PM0052', 1, 1, CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 23 DAY, 0, 'da_tra'),  -- 25TH0001: đã trả hết
  ('PM0053', 21, 1, CURDATE() - INTERVAL 29 DAY, CURDATE() - INTERVAL 22 DAY, 0, 'da_tra'),  -- 24TH0001: đã trả hết
  ('PM0054', 30, 3, CURDATE() - INTERVAL 27 DAY, CURDATE() - INTERVAL 20 DAY, 0, 'qua_han'),  -- 23TH0002: QUÁ HẠN 20 ngày
  ('PM0055', 2, 2, CURDATE() - INTERVAL 25 DAY, CURDATE() - INTERVAL 18 DAY, 0, 'da_tra'),  -- 25TH0002: đã trả hết
  ('PM0056', 19, 2, CURDATE() - INTERVAL 25 DAY, CURDATE() - INTERVAL 18 DAY, 0, 'da_tra'),  -- 25TH0019: đã trả hết
  ('PM0057', 34, 1, CURDATE() - INTERVAL 20 DAY, CURDATE() + INTERVAL 10 DAY, 1, 'dang_muon'),  -- GV002: đang mượn, còn 10 ngày ; đã gia hạn 1/2
  ('PM0058', 6, 2, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 13 DAY, 0, 'qua_han'),  -- 25TH0006: QUÁ HẠN 13 ngày
  ('PM0059', 38, 2, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 5 DAY, 0, 'qua_han'),  -- GV006: QUÁ HẠN 5 ngày
  ('PM0060', 5, 1, CURDATE() - INTERVAL 14 DAY, CURDATE() - INTERVAL 7 DAY, 0, 'qua_han'),  -- 25TH0005: QUÁ HẠN 7 ngày
  ('PM0061', 37, 1, CURDATE() - INTERVAL 14 DAY, CURDATE() + INTERVAL 1 DAY, 0, 'dang_muon'),  -- GV005: đang mượn, còn 1 ngày
  ('PM0062', 16, 2, CURDATE() - INTERVAL 12 DAY, CURDATE() + INTERVAL 2 DAY, 1, 'dang_muon'),  -- 25TH0016: đang mượn, còn 2 ngày ; đã gia hạn 1/1
  ('PM0063', 7, 2, CURDATE() - INTERVAL 10 DAY, CURDATE() + INTERVAL 4 DAY, 1, 'dang_muon'),  -- 25TH0007: đang mượn, còn 4 ngày ; đã gia hạn 1/1
  ('PM0064', 40, 1, CURDATE() - INTERVAL 10 DAY, CURDATE() + INTERVAL 5 DAY, 0, 'dang_muon'),  -- GV008: đang mượn, còn 5 ngày ; đã trả một phần
  ('PM0065', 23, 2, CURDATE() - INTERVAL 9 DAY, CURDATE() - INTERVAL 2 DAY, 0, 'qua_han'),  -- 24TH0003: QUÁ HẠN 2 ngày
  ('PM0066', 13, 1, CURDATE() - INTERVAL 8 DAY, CURDATE() - INTERVAL 1 DAY, 0, 'qua_han'),  -- 25TH0013: QUÁ HẠN 1 ngày
  ('PM0067', 27, 1, CURDATE() - INTERVAL 7 DAY, CURDATE(), 0, 'dang_muon'),  -- 24TH0007: đang mượn, hạn trả là hôm nay
  ('PM0068', 14, 3, CURDATE() - INTERVAL 6 DAY, CURDATE() + INTERVAL 1 DAY, 0, 'dang_muon'),  -- 25TH0014: đang mượn, còn 1 ngày
  ('PM0069', 29, 3, CURDATE() - INTERVAL 6 DAY, CURDATE() + INTERVAL 1 DAY, 0, 'dang_muon'),  -- 23TH0001: đang mượn, còn 1 ngày
  ('PM0070', 33, 1, CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 10 DAY, 0, 'dang_muon'),  -- GV001: đang mượn, còn 10 ngày
  ('PM0071', 12, 3, CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 2 DAY, 0, 'dang_muon'),  -- 25TH0012: đang mượn, còn 2 ngày ; đã trả một phần
  ('PM0072', 18, 2, CURDATE() - INTERVAL 4 DAY, CURDATE() + INTERVAL 3 DAY, 0, 'dang_muon'),  -- 25TH0018: đang mượn, còn 3 ngày
  ('PM0073', 21, 1, CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 4 DAY, 0, 'dang_muon'),  -- 24TH0001: đang mượn, còn 4 ngày
  ('PM0074', 39, 3, CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 12 DAY, 0, 'dang_muon'),  -- GV007: đang mượn, còn 12 ngày
  ('PM0075', 4, 2, CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 5 DAY, 0, 'dang_muon'),  -- 25TH0004: đang mượn, còn 5 ngày
  ('PM0076', 22, 1, CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 5 DAY, 0, 'dang_muon'),  -- 24TH0002: đang mượn, còn 5 ngày
  ('PM0077', 11, 2, CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 6 DAY, 0, 'dang_muon');  -- 25TH0011: đang mượn, còn 6 ngày

-- ---- Chi tiết phiếu mượn ----
-- Cột: id_phieu_muon, id_sach, ngay_tra_thuc_te, tinh_trang_tra, tien_phat, tien_boi_thuong
-- (phạt trễ = số ngày trễ x 3.000đ cho từng cuốn, kể cả cuốn hỏng/mất ; hỏng = 1 x gia_tien ; mất = 2 x gia_tien)
INSERT INTO chitietphieumuon (id_phieu_muon, id_sach, ngay_tra_thuc_te, tinh_trang_tra, tien_phat, tien_boi_thuong) VALUES
  (1, 1, CURDATE() - INTERVAL 166 DAY, 'nguyen_ven', 0, 0),  -- PM0001 SH0001
  (2, 12, CURDATE() - INTERVAL 168 DAY, 'nguyen_ven', 0, 0),  -- PM0002 SH0012
  (3, 37, CURDATE() - INTERVAL 157 DAY, 'nguyen_ven', 21000, 0),  -- PM0003 SH0037: trễ 7 ngày
  (4, 37, CURDATE() - INTERVAL 161 DAY, 'nguyen_ven', 0, 0),  -- PM0004 SH0037
  (5, 51, CURDATE() - INTERVAL 164 DAY, 'nguyen_ven', 0, 0),  -- PM0005 SH0051
  (6, 7, CURDATE() - INTERVAL 164 DAY, 'nguyen_ven', 0, 0),  -- PM0006 SH0007
  (6, 35, CURDATE() - INTERVAL 164 DAY, 'nguyen_ven', 0, 0),  -- PM0006 SH0035
  (7, 7, CURDATE() - INTERVAL 151 DAY, 'nguyen_ven', 15000, 0),  -- PM0007 SH0007: trễ 5 ngày
  (8, 42, CURDATE() - INTERVAL 151 DAY, 'nguyen_ven', 0, 0),  -- PM0008 SH0042
  (8, 58, CURDATE() - INTERVAL 151 DAY, 'nguyen_ven', 0, 0),  -- PM0008 SH0058
  (9, 58, CURDATE() - INTERVAL 150 DAY, 'nguyen_ven', 0, 0),  -- PM0009 SH0058
  (9, 25, CURDATE() - INTERVAL 150 DAY, 'nguyen_ven', 0, 0),  -- PM0009 SH0025
  (9, 64, CURDATE() - INTERVAL 150 DAY, 'nguyen_ven', 0, 0),  -- PM0009 SH0064
  (10, 72, CURDATE() - INTERVAL 124 DAY, 'hu_hong', 0, 102000),  -- PM0010 SH0072: hỏng
  (10, 64, CURDATE() - INTERVAL 124 DAY, 'nguyen_ven', 0, 0),  -- PM0010 SH0064
  (11, 64, CURDATE() - INTERVAL 146 DAY, 'nguyen_ven', 0, 0),  -- PM0011 SH0064
  (12, 58, CURDATE() - INTERVAL 142 DAY, 'nguyen_ven', 0, 0),  -- PM0012 SH0058
  (13, 28, CURDATE() - INTERVAL 122 DAY, 'nguyen_ven', 21000, 0),  -- PM0013 SH0028: trễ 7 ngày
  (14, 28, CURDATE() - INTERVAL 125 DAY, 'nguyen_ven', 21000, 0),  -- PM0014 SH0028: trễ 7 ngày
  (15, 35, CURDATE() - INTERVAL 130 DAY, 'nguyen_ven', 0, 0),  -- PM0015 SH0035
  (16, 45, CURDATE() - INTERVAL 120 DAY, 'nguyen_ven', 0, 0),  -- PM0016 SH0045
  (16, 61, CURDATE() - INTERVAL 120 DAY, 'nguyen_ven', 0, 0),  -- PM0016 SH0061
  (17, 12, CURDATE() - INTERVAL 122 DAY, 'nguyen_ven', 0, 0),  -- PM0017 SH0012
  (17, 14, CURDATE() - INTERVAL 122 DAY, 'nguyen_ven', 0, 0),  -- PM0017 SH0014
  (18, 17, CURDATE() - INTERVAL 119 DAY, 'nguyen_ven', 0, 0),  -- PM0018 SH0017
  (19, 64, CURDATE() - INTERVAL 114 DAY, 'nguyen_ven', 0, 0),  -- PM0019 SH0064
  (20, 60, CURDATE() - INTERVAL 112 DAY, 'nguyen_ven', 0, 0),  -- PM0020 SH0060
  (20, 7, CURDATE() - INTERVAL 112 DAY, 'nguyen_ven', 0, 0),  -- PM0020 SH0007
  (21, 6, CURDATE() - INTERVAL 106 DAY, 'hu_hong', 0, 85000),  -- PM0021 SH0006: hỏng
  (22, 18, CURDATE() - INTERVAL 83 DAY, 'nguyen_ven', 6000, 0),  -- PM0022 SH0018: trễ 2 ngày
  (23, 65, CURDATE() - INTERVAL 96 DAY, 'nguyen_ven', 0, 0),  -- PM0023 SH0065
  (24, 71, CURDATE() - INTERVAL 97 DAY, 'nguyen_ven', 0, 0),  -- PM0024 SH0071
  (25, 50, CURDATE() - INTERVAL 92 DAY, 'nguyen_ven', 0, 0),  -- PM0025 SH0050
  (26, 64, CURDATE() - INTERVAL 73 DAY, 'nguyen_ven', 36000, 0),  -- PM0026 SH0064: trễ 12 ngày
  (26, 18, CURDATE() - INTERVAL 73 DAY, 'nguyen_ven', 36000, 0),  -- PM0026 SH0018: trễ 12 ngày
  (26, 14, CURDATE() - INTERVAL 73 DAY, 'nguyen_ven', 36000, 0),  -- PM0026 SH0014: trễ 12 ngày
  (27, 17, CURDATE() - INTERVAL 52 DAY, 'nguyen_ven', 27000, 0),  -- PM0027 SH0017: trễ 9 ngày
  (27, 16, CURDATE() - INTERVAL 52 DAY, 'hu_hong', 27000, 105000),  -- PM0027 SH0016: trễ 9 ngày, hỏng
  (27, 68, CURDATE() - INTERVAL 52 DAY, 'nguyen_ven', 27000, 0),  -- PM0027 SH0068: trễ 9 ngày
  (28, 11, CURDATE() - INTERVAL 83 DAY, 'nguyen_ven', 0, 0),  -- PM0028 SH0011
  (29, 12, CURDATE() - INTERVAL 87 DAY, 'nguyen_ven', 0, 0),  -- PM0029 SH0012
  (30, 61, CURDATE() - INTERVAL 80 DAY, 'nguyen_ven', 0, 0),  -- PM0030 SH0061
  (30, 34, CURDATE() - INTERVAL 80 DAY, 'nguyen_ven', 0, 0),  -- PM0030 SH0034
  (31, 69, CURDATE() - INTERVAL 71 DAY, 'nguyen_ven', 0, 0),  -- PM0031 SH0069
  (31, 54, CURDATE() - INTERVAL 71 DAY, 'nguyen_ven', 0, 0),  -- PM0031 SH0054
  (31, 39, CURDATE() - INTERVAL 71 DAY, 'nguyen_ven', 0, 0),  -- PM0031 SH0039
  (32, 3, CURDATE() - INTERVAL 70 DAY, 'nguyen_ven', 0, 0),  -- PM0032 SH0003
  (33, 5, CURDATE() - INTERVAL 68 DAY, 'nguyen_ven', 0, 0),  -- PM0033 SH0005
  (34, 20, CURDATE() - INTERVAL 57 DAY, 'nguyen_ven', 0, 0),  -- PM0034 SH0020
  (34, 71, CURDATE() - INTERVAL 57 DAY, 'nguyen_ven', 0, 0),  -- PM0034 SH0071
  (35, 12, CURDATE() - INTERVAL 62 DAY, 'nguyen_ven', 0, 0),  -- PM0035 SH0012
  (36, 9, CURDATE() - INTERVAL 40 DAY, 'nguyen_ven', 39000, 0),  -- PM0036 SH0009: trễ 13 ngày
  (36, 13, CURDATE() - INTERVAL 40 DAY, 'mat', 39000, 180000),  -- PM0036 SH0013: trễ 13 ngày, MẤT
  (37, 42, CURDATE() - INTERVAL 53 DAY, 'nguyen_ven', 0, 0),  -- PM0037 SH0042
  (38, 50, CURDATE() - INTERVAL 48 DAY, 'nguyen_ven', 0, 0),  -- PM0038 SH0050
  (39, 66, CURDATE() - INTERVAL 43 DAY, 'nguyen_ven', 12000, 0),  -- PM0039 SH0066: trễ 4 ngày
  (39, 20, CURDATE() - INTERVAL 43 DAY, 'nguyen_ven', 12000, 0),  -- PM0039 SH0020: trễ 4 ngày
  (40, 19, CURDATE() - INTERVAL 44 DAY, 'nguyen_ven', 0, 0),  -- PM0040 SH0019
  (41, 42, CURDATE() - INTERVAL 40 DAY, 'nguyen_ven', 3000, 0),  -- PM0041 SH0042: trễ 1 ngày
  (42, 69, CURDATE() - INTERVAL 43 DAY, 'nguyen_ven', 0, 0),  -- PM0042 SH0069
  (42, 65, CURDATE() - INTERVAL 43 DAY, 'nguyen_ven', 0, 0),  -- PM0042 SH0065
  (43, 53, CURDATE() - INTERVAL 37 DAY, 'nguyen_ven', 0, 0),  -- PM0043 SH0053
  (43, 37, CURDATE() - INTERVAL 37 DAY, 'mat', 0, 196000),  -- PM0043 SH0037: MẤT
  (44, 49, CURDATE() - INTERVAL 25 DAY, 'nguyen_ven', 30000, 0),  -- PM0044 SH0049: trễ 10 ngày
  (45, 17, CURDATE() - INTERVAL 33 DAY, 'hu_hong', 0, 150000),  -- PM0045 SH0017: hỏng
  (46, 19, CURDATE() - INTERVAL 36 DAY, 'nguyen_ven', 0, 0),  -- PM0046 SH0019
  (46, 58, CURDATE() - INTERVAL 36 DAY, 'hu_hong', 0, 110000),  -- PM0046 SH0058: hỏng
  (47, 35, CURDATE() - INTERVAL 33 DAY, 'nguyen_ven', 0, 0),  -- PM0047 SH0035
  (48, 48, CURDATE() - INTERVAL 35 DAY, 'nguyen_ven', 0, 0),  -- PM0048 SH0048
  (48, 5, CURDATE() - INTERVAL 35 DAY, 'nguyen_ven', 0, 0),  -- PM0048 SH0005
  (48, 36, CURDATE() - INTERVAL 35 DAY, 'nguyen_ven', 0, 0),  -- PM0048 SH0036
  (49, 21, NULL, NULL, 0, 0),  -- PM0049 SH0021: chưa trả
  (49, 22, NULL, NULL, 0, 0),  -- PM0049 SH0022: chưa trả
  (49, 23, NULL, NULL, 0, 0),  -- PM0049 SH0023: chưa trả
  (49, 33, NULL, NULL, 0, 0),  -- PM0049 SH0033: chưa trả
  (50, 63, CURDATE() - INTERVAL 28 DAY, 'nguyen_ven', 0, 0),  -- PM0050 SH0063
  (51, 24, CURDATE() - INTERVAL 26 DAY, 'nguyen_ven', 0, 0),  -- PM0051 SH0024
  (52, 14, CURDATE() - INTERVAL 26 DAY, 'nguyen_ven', 0, 0),  -- PM0052 SH0014
  (52, 15, CURDATE() - INTERVAL 26 DAY, 'nguyen_ven', 0, 0),  -- PM0052 SH0015
  (53, 5, CURDATE() - INTERVAL 28 DAY, 'nguyen_ven', 0, 0),  -- PM0053 SH0005
  (54, 39, NULL, NULL, 0, 0),  -- PM0054 SH0039: chưa trả
  (55, 5, CURDATE() - INTERVAL 15 DAY, 'nguyen_ven', 9000, 0),  -- PM0055 SH0005: trễ 3 ngày
  (56, 58, CURDATE() - INTERVAL 21 DAY, 'nguyen_ven', 0, 0),  -- PM0056 SH0058
  (56, 14, CURDATE() - INTERVAL 21 DAY, 'nguyen_ven', 0, 0),  -- PM0056 SH0014
  (57, 10, NULL, NULL, 0, 0),  -- PM0057 SH0010: chưa trả
  (57, 11, NULL, NULL, 0, 0),  -- PM0057 SH0011: chưa trả
  (58, 16, NULL, NULL, 0, 0),  -- PM0058 SH0016: chưa trả
  (59, 44, NULL, NULL, 0, 0),  -- PM0059 SH0044: chưa trả
  (59, 32, NULL, NULL, 0, 0),  -- PM0059 SH0032: chưa trả
  (60, 2, NULL, NULL, 0, 0),  -- PM0060 SH0002: chưa trả
  (60, 8, NULL, NULL, 0, 0),  -- PM0060 SH0008: chưa trả
  (61, 30, NULL, NULL, 0, 0),  -- PM0061 SH0030: chưa trả
  (61, 31, NULL, NULL, 0, 0),  -- PM0061 SH0031: chưa trả
  (62, 50, NULL, NULL, 0, 0),  -- PM0062 SH0050: chưa trả
  (63, 6, NULL, NULL, 0, 0),  -- PM0063 SH0006: chưa trả
  (64, 15, CURDATE() - INTERVAL 4 DAY, 'nguyen_ven', 0, 0),  -- PM0064 SH0015
  (64, 25, NULL, NULL, 0, 0),  -- PM0064 SH0025: chưa trả
  (64, 26, NULL, NULL, 0, 0),  -- PM0064 SH0026: chưa trả
  (65, 72, NULL, NULL, 0, 0),  -- PM0065 SH0072: chưa trả
  (65, 66, NULL, NULL, 0, 0),  -- PM0065 SH0066: chưa trả
  (66, 59, NULL, NULL, 0, 0),  -- PM0066 SH0059: chưa trả
  (67, 64, NULL, NULL, 0, 0),  -- PM0067 SH0064: chưa trả
  (67, 65, NULL, NULL, 0, 0),  -- PM0067 SH0065: chưa trả
  (68, 8, NULL, NULL, 0, 0),  -- PM0068 SH0008: chưa trả
  (68, 42, NULL, NULL, 0, 0),  -- PM0068 SH0042: chưa trả
  (68, 58, NULL, NULL, 0, 0),  -- PM0068 SH0058: chưa trả
  (69, 61, NULL, NULL, 0, 0),  -- PM0069 SH0061: chưa trả
  (70, 14, NULL, NULL, 0, 0),  -- PM0070 SH0014: chưa trả
  (70, 17, NULL, NULL, 0, 0),  -- PM0070 SH0017: chưa trả
  (70, 18, NULL, NULL, 0, 0),  -- PM0070 SH0018: chưa trả
  (71, 14, CURDATE() - INTERVAL 2 DAY, 'nguyen_ven', 0, 0),  -- PM0071 SH0014
  (71, 36, NULL, NULL, 0, 0),  -- PM0071 SH0036: chưa trả
  (71, 37, NULL, NULL, 0, 0),  -- PM0071 SH0037: chưa trả
  (72, 53, NULL, NULL, 0, 0),  -- PM0072 SH0053: chưa trả
  (72, 54, NULL, NULL, 0, 0),  -- PM0072 SH0054: chưa trả
  (72, 55, NULL, NULL, 0, 0),  -- PM0072 SH0055: chưa trả
  (73, 34, NULL, NULL, 0, 0),  -- PM0073 SH0034: chưa trả
  (73, 35, NULL, NULL, 0, 0),  -- PM0073 SH0035: chưa trả
  (74, 57, NULL, NULL, 0, 0),  -- PM0074 SH0057: chưa trả
  (75, 1, NULL, NULL, 0, 0),  -- PM0075 SH0001: chưa trả
  (75, 7, NULL, NULL, 0, 0),  -- PM0075 SH0007: chưa trả
  (75, 12, NULL, NULL, 0, 0),  -- PM0075 SH0012: chưa trả
  (76, 68, NULL, NULL, 0, 0),  -- PM0076 SH0068: chưa trả
  (76, 69, NULL, NULL, 0, 0),  -- PM0076 SH0069: chưa trả
  (77, 27, NULL, NULL, 0, 0);  -- PM0077 SH0027: chưa trả

-- ---- Đồng bộ so_con_lai = so_luong - số bản đang được mượn (chưa trả) ----
-- Làm bước này để số liệu luôn khớp với phiếu mượn, khỏi tự đếm tay.
UPDATE sach s
SET s.so_con_lai = s.so_luong - (
    SELECT COUNT(*)
    FROM chitietphieumuon ct
    WHERE ct.id_sach = s.id
      AND ct.ngay_tra_thuc_te IS NULL
);

-- =====================================================================
-- du-lieu-mau.sql : dữ liệu mẫu để demo (chạy SAU schema.sql)
-- Ngày tháng dùng CURDATE() +/- n ngày => lúc nào import cũng có phiếu "đang mượn" và "quá hạn" thật.
-- Thông tin sách (năm, số trang, giá) và độc giả là dữ liệu giả lập để test, không phải số liệu thật.
-- Mật khẩu mẫu: nhân viên "admin"/"thuthu01" = admin123 ; mọi độc giả = 123456
-- =====================================================================
SET NAMES utf8mb4;
USE qltv;

-- ---- Quy định mượn (mục 1 kế hoạch) ----
INSERT INTO loaidocgia (ten_loai, so_sach_toi_da, so_ngay_muon, so_lan_gia_han) VALUES
  ('Sinh viên', 3, 7, 1),
  ('Giảng viên', 5, 15, 2);

-- ---- Thể loại ----
INSERT INTO theloai (ten_theloai) VALUES
  ('Công nghệ thông tin'),  -- 1
  ('Văn học'),              -- 2
  ('Khoa học'),             -- 3
  ('Kinh tế'),              -- 4
  ('Thiếu nhi'),            -- 5
  ('Lịch sử'),              -- 6
  ('Kỹ năng sống');         -- 7

-- ---- Nhà xuất bản ----
INSERT INTO nhaxuatban (ten_nxb, dia_chi) VALUES
  ('NXB Giáo dục Việt Nam',    'Hà Nội'),       -- 1
  ('NXB Trẻ',                  'TP. Hồ Chí Minh'), -- 2
  ('NXB Kim Đồng',             'Hà Nội'),       -- 3
  ('NXB Khoa học và Kỹ thuật', 'Hà Nội'),       -- 4
  ('NXB Văn học',              'Hà Nội'),       -- 5
  ('NXB Tổng hợp TP.HCM',      'TP. Hồ Chí Minh'); -- 6

-- ---- Nhân viên (mật khẩu: admin123) ----
INSERT INTO nhanvien (ho_ten, ten_dang_nhap, mat_khau, vai_tro) VALUES
  ('Nguyễn Hoàng Phúc', 'admin',    '$2y$10$gda1Rm88z1V9wn4r6CNBROQ4rMHoVidfqTQ9MxVxBKGRnoXc43s5u', 'admin'),     -- id 1
  ('Lê Thị Thu Hà',     'thuthu01', '$2y$10$gda1Rm88z1V9wn4r6CNBROQ4rMHoVidfqTQ9MxVxBKGRnoXc43s5u', 'thu_thu');   -- id 2

-- ---- Độc giả (mật khẩu: 123456) ----
-- id_loai: 1 = Sinh viên, 2 = Giảng viên
INSERT INTO docgia (ma_doc_gia, ho_ten, ngay_sinh, gioi_tinh, dia_chi, sdt, email, id_loai, mat_khau, ngay_dang_ky, trang_thai) VALUES
  ('25TH0001', 'Nguyễn Văn An',     '2007-03-12', 'nam', 'Nha Trang', '0900000001', 'an@example.com',    1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-05', 'hoat_dong'), -- 1
  ('25TH0002', 'Trần Thị Bình',     '2007-07-25', 'nu',  'Nha Trang', '0900000002', 'binh@example.com',  1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-05', 'hoat_dong'), -- 2
  ('25TH0003', 'Lê Hoàng Cường',    '2006-11-02', 'nam', 'Cam Ranh',  '0900000003', 'cuong@example.com', 1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-06', 'hoat_dong'), -- 3
  ('25TH0004', 'Phạm Thu Dung',     '2007-01-18', 'nu',  'Nha Trang', '0900000004', 'dung@example.com',  1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-06', 'hoat_dong'), -- 4
  ('25TH0005', 'Võ Minh Đạt',       '2006-09-30', 'nam', 'Diên Khánh','0900000005', 'dat@example.com',   1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-07', 'hoat_dong'), -- 5
  ('25TH0006', 'Đặng Ngọc Hà',      '2007-05-14', 'nu',  'Nha Trang', '0900000006', 'ha@example.com',    1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-07', 'hoat_dong'), -- 6
  ('25TH0007', 'Bùi Quốc Huy',      '2006-12-08', 'nam', 'Ninh Hòa',  '0900000007', 'huy@example.com',   1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-08', 'hoat_dong'), -- 7
  ('25TH0008', 'Hồ Thị Lan',        '2007-02-21', 'nu',  'Nha Trang', '0900000008', 'lan@example.com',   1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-08', 'khoa'),       -- 8 (bị khóa)
  ('GV001',    'Nguyễn Thanh Tùng', '1980-04-10', 'nam', 'Nha Trang', '0900000009', 'tung@example.com',  2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2024-08-20', 'hoat_dong'), -- 9
  ('GV002',    'Lê Thị Mai',        '1985-06-27', 'nu',  'Nha Trang', '0900000010', 'mai@example.com',   2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2024-08-20', 'hoat_dong'), -- 10
  ('GV003',    'Trần Quốc Bảo',     '1978-10-03', 'nam', 'Nha Trang', '0900000011', 'bao@example.com',   2, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2024-08-21', 'hoat_dong'), -- 11
  ('25TH0009', 'Phan Gia Hân',      '2007-08-16', 'nu',  'Nha Trang', '0900000012', 'han@example.com',   1, '$2y$10$.EcuKZHlWyDroFJuaLaF2.aQSDNpYnu28fRLbLgqJtL5Rr44PS3x2', '2025-09-09', 'hoat_dong'); -- 12 (chưa mượn gì: dùng test lập phiếu mới)

-- ---- Sách (so_con_lai tạm = so_luong, cuối file sẽ tự trừ theo phiếu đang mượn) ----
-- cột: ma_sach, ten_sach, tac_gia, id_theloai, id_nxb, nam_xuat_ban, so_trang, gia_tien, so_luong, so_con_lai, mo_ta
INSERT INTO sach (ma_sach, ten_sach, tac_gia, id_theloai, id_nxb, nam_xuat_ban, so_trang, gia_tien, so_luong, so_con_lai, mo_ta) VALUES
  ('SH0001', 'Dế Mèn phiêu lưu ký',              'Tô Hoài',                       5, 3, 2018, 160,  45000, 4, 4, 'Truyện thiếu nhi kinh điển.'),                  -- 1
  ('SH0002', 'Số đỏ',                            'Vũ Trọng Phụng',                2, 5, 2019, 320,  80000, 3, 3, 'Tiểu thuyết trào phúng.'),                      -- 2
  ('SH0003', 'Tắt đèn',                          'Ngô Tất Tố',                    2, 5, 2017, 200,  60000, 3, 3, 'Tiểu thuyết hiện thực.'),                       -- 3
  ('SH0004', 'Lão Hạc',                          'Nam Cao',                       2, 5, 2018, 120,  50000, 3, 3, 'Truyện ngắn.'),                                 -- 4
  ('SH0005', 'Mắt biếc',                         'Nguyễn Nhật Ánh',               2, 2, 2020, 300,  90000, 5, 5, 'Truyện dài.'),                                  -- 5
  ('SH0006', 'Cho tôi xin một vé đi tuổi thơ',   'Nguyễn Nhật Ánh',               2, 2, 2019, 220,  85000, 5, 5, 'Truyện dài.'),                                  -- 6
  ('SH0007', 'Nhà giả kim',                      'Paulo Coelho',                  2, 5, 2020, 228,  79000, 4, 4, 'Tiểu thuyết.'),                                 -- 7
  ('SH0008', 'Đắc nhân tâm',                     'Dale Carnegie',                 7, 6, 2021, 320,  86000, 5, 5, 'Sách kỹ năng giao tiếp.'),                      -- 8
  ('SH0009', 'Nghĩ giàu làm giàu',               'Napoleon Hill',                 4, 6, 2020, 380,  98000, 3, 3, 'Sách phát triển bản thân.'),                    -- 9
  ('SH0010', 'Nguyên lý kinh tế học',            'N. Gregory Mankiw',             4, 1, 2019, 700, 250000, 2, 2, 'Giáo trình kinh tế.'),                          -- 10
  ('SH0011', 'Lược sử thời gian',                'Stephen Hawking',               3, 2, 2018, 280, 120000, 3, 3, 'Khoa học phổ thông.'),                          -- 11
  ('SH0012', 'Sapiens: Lược sử loài người',      'Yuval Noah Harari',             6, 6, 2021, 560, 220000, 4, 4, 'Lịch sử loài người.'),                          -- 12
  ('SH0013', 'Việt Nam sử lược',                 'Trần Trọng Kim',                6, 6, 2017, 600,  90000, 2, 2, 'Sử học.'),                                      -- 13
  ('SH0014', 'Giáo trình Cơ sở dữ liệu',         'Nhiều tác giả',                 1, 1, 2022, 350,  95000, 6, 6, 'Giáo trình CSDL.'),                             -- 14
  ('SH0015', 'Giáo trình Công nghệ phần mềm',    'Nhiều tác giả',                 1, 1, 2022, 400, 110000, 6, 6, 'Giáo trình CNPM.'),                             -- 15
  ('SH0016', 'Lập trình C++ căn bản',            'Nhiều tác giả',                 1, 4, 2021, 420, 105000, 5, 5, 'Giáo trình C++.'),                              -- 16
  ('SH0017', 'Clean Code',                       'Robert C. Martin',              1, 4, 2020, 464, 150000, 3, 3, 'Viết mã sạch.'),                                -- 17
  ('SH0018', 'Introduction to Algorithms',       'Thomas H. Cormen và cộng sự',   1, 4, 2022, 1312,450000, 2, 2, 'Sách thuật toán.'),                             -- 18
  ('SH0019', 'Doraemon tập 1',                   'Fujiko F. Fujio',               5, 3, 2020, 190,  25000, 8, 8, 'Truyện tranh thiếu nhi.'),                      -- 19
  ('SH0020', 'Tuổi thơ dữ dội',                  'Phùng Quán',                    2, 3, 2019, 800, 130000, 3, 3, 'Tiểu thuyết.');                                 -- 20

-- ---- Phiếu mượn ----
-- Cột: ma_phieu, id_doc_gia, id_nhan_vien, ngay_muon, ngay_hen_tra, so_lan_gia_han_da_dung, trang_thai
INSERT INTO phieumuon (ma_phieu, id_doc_gia, id_nhan_vien, ngay_muon, ngay_hen_tra, so_lan_gia_han_da_dung, trang_thai) VALUES
  -- Phiếu đã trả (lịch sử, rải nhiều tháng để thống kê theo tháng)
  ('PM0001',  1, 1, CURDATE() - INTERVAL 30 DAY,  CURDATE() - INTERVAL 23 DAY,  0, 'da_tra'),  -- 1: trả đúng hạn
  ('PM0002',  2, 2, CURDATE() - INTERVAL 25 DAY,  CURDATE() - INTERVAL 18 DAY,  0, 'da_tra'),  -- 2: trả trễ 3 ngày
  ('PM0003',  3, 1, CURDATE() - INTERVAL 40 DAY,  CURDATE() - INTERVAL 33 DAY,  0, 'da_tra'),  -- 3: sách bị hỏng
  ('PM0010',  2, 1, CURDATE() - INTERVAL 60 DAY,  CURDATE() - INTERVAL 53 DAY,  0, 'da_tra'),  -- 4: trễ 13 ngày, 1 cuốn mất
  ('PM0011',  1, 2, CURDATE() - INTERVAL 75 DAY,  CURDATE() - INTERVAL 68 DAY,  0, 'da_tra'),  -- 5
  ('PM0012', 11, 1, CURDATE() - INTERVAL 100 DAY, CURDATE() - INTERVAL 85 DAY,  0, 'da_tra'),  -- 6: giảng viên trễ 2 ngày
  ('PM0013',  4, 1, CURDATE() - INTERVAL 50 DAY,  CURDATE() - INTERVAL 43 DAY,  0, 'da_tra'),  -- 7
  -- Phiếu đang mượn
  ('PM0004',  4, 2, CURDATE() - INTERVAL 2 DAY,   CURDATE() + INTERVAL 5 DAY,   0, 'dang_muon'), -- 8: SV mượn đủ 3 cuốn (chạm mức tối đa)
  ('PM0007',  9, 1, CURDATE() - INTERVAL 5 DAY,   CURDATE() + INTERVAL 10 DAY,  0, 'dang_muon'), -- 9: GV, chưa gia hạn lần nào
  ('PM0008', 10, 1, CURDATE() - INTERVAL 20 DAY,  CURDATE() + INTERVAL 10 DAY,  1, 'dang_muon'), -- 10: GV đã gia hạn 1/2 lần
  ('PM0009',  7, 2, CURDATE() - INTERVAL 10 DAY,  CURDATE() + INTERVAL 4 DAY,   1, 'dang_muon'), -- 11: SV đã gia hạn 1/1 lần (hết lượt)
  -- Phiếu quá hạn (dùng test: không cho lập phiếu mới + tính tiền phạt)
  ('PM0005',  5, 1, CURDATE() - INTERVAL 14 DAY,  CURDATE() - INTERVAL 7 DAY,   0, 'qua_han'),  -- 12: trễ 7 ngày, 2 cuốn
  ('PM0006',  6, 2, CURDATE() - INTERVAL 20 DAY,  CURDATE() - INTERVAL 13 DAY,  0, 'qua_han');  -- 13: trễ 13 ngày, 1 cuốn

-- ---- Chi tiết phiếu mượn ----
-- Cột: id_phieu_muon, id_sach, ngay_tra_thuc_te, tinh_trang_tra, tien_phat, tien_boi_thuong
-- (phạt trễ = số ngày trễ x 3.000đ ; hỏng = 1 x gia_tien ; mất = 2 x gia_tien)
INSERT INTO chitietphieumuon (id_phieu_muon, id_sach, ngay_tra_thuc_te, tinh_trang_tra, tien_phat, tien_boi_thuong) VALUES
  -- PM0001 (id 1): trả đúng hạn
  (1, 14, CURDATE() - INTERVAL 26 DAY, 'nguyen_ven', 0, 0),
  (1, 15, CURDATE() - INTERVAL 26 DAY, 'nguyen_ven', 0, 0),
  -- PM0002 (id 2): trễ 3 ngày => 9.000đ
  (2,  5, CURDATE() - INTERVAL 15 DAY, 'nguyen_ven', 9000, 0),
  -- PM0003 (id 3): trả đúng hạn nhưng hỏng => bồi thường 1 x 150.000đ
  (3, 17, CURDATE() - INTERVAL 33 DAY, 'hu_hong', 0, 150000),
  -- PM0010 (id 4): trễ 13 ngày => 39.000đ/cuốn ; sách 13 bị mất => bồi thường 2 x 90.000đ
  (4,  9, CURDATE() - INTERVAL 40 DAY, 'nguyen_ven', 39000, 0),
  (4, 13, CURDATE() - INTERVAL 40 DAY, 'mat',        39000, 180000),
  -- PM0011 (id 5)
  (5,  3, CURDATE() - INTERVAL 70 DAY, 'nguyen_ven', 0, 0),
  -- PM0012 (id 6): trễ 2 ngày => 6.000đ
  (6, 18, CURDATE() - INTERVAL 83 DAY, 'nguyen_ven', 6000, 0),
  -- PM0013 (id 7)
  (7, 19, CURDATE() - INTERVAL 44 DAY, 'nguyen_ven', 0, 0),
  -- PM0004 (id 8): đang mượn 3 cuốn
  (8,  1, NULL, NULL, 0, 0),
  (8,  7, NULL, NULL, 0, 0),
  (8, 12, NULL, NULL, 0, 0),
  -- PM0007 (id 9): GV đang mượn 3 cuốn
  (9, 14, NULL, NULL, 0, 0),
  (9, 17, NULL, NULL, 0, 0),
  (9, 18, NULL, NULL, 0, 0),
  -- PM0008 (id 10)
  (10, 10, NULL, NULL, 0, 0),
  (10, 11, NULL, NULL, 0, 0),
  -- PM0009 (id 11)
  (11,  6, NULL, NULL, 0, 0),
  -- PM0005 (id 12): quá hạn
  (12,  2, NULL, NULL, 0, 0),
  (12,  8, NULL, NULL, 0, 0),
  -- PM0006 (id 13): quá hạn
  (13, 16, NULL, NULL, 0, 0);

-- ---- Đồng bộ so_con_lai = so_luong - số bản đang được mượn (chưa trả) ----
-- Làm bước này để số liệu luôn khớp với phiếu mượn, khỏi tự đếm tay.
UPDATE sach s
SET s.so_con_lai = s.so_luong - (
    SELECT COUNT(*)
    FROM chitietphieumuon ct
    WHERE ct.id_sach = s.id
      AND ct.ngay_tra_thuc_te IS NULL
);

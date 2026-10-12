-- =====================================================================
-- schema.sql : tạo CSDL + toàn bộ bảng cho Hệ thống Quản lý Mượn - Trả Sách
-- Chạy file này TRƯỚC, sau đó mới chạy du-lieu-mau.sql
-- CẢNH BÁO: file này DROP các bảng cũ rồi tạo lại => mất sạch dữ liệu đang có.
--           Chỉ dùng khi đang phát triển, không chạy lên dữ liệu thật.
-- =====================================================================

-- Báo cho máy chủ biết file này viết bằng UTF-8 (tránh lỗi font tiếng Việt khi nhập bằng dòng lệnh)
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS qltv
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qltv;

-- Tắt kiểm tra khóa ngoại tạm thời để DROP theo thứ tự nào cũng được
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS chitietphieumuon;
DROP TABLE IF EXISTS phieumuon;
DROP TABLE IF EXISTS sach;
DROP TABLE IF EXISTS docgia;
DROP TABLE IF EXISTS nhanvien;
DROP TABLE IF EXISTS nhaxuatban;
DROP TABLE IF EXISTS theloai;
DROP TABLE IF EXISTS loaidocgia;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. loaidocgia : quy định mượn theo từng nhóm độc giả
-- ---------------------------------------------------------------------
CREATE TABLE loaidocgia (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ten_loai         VARCHAR(50)  NOT NULL UNIQUE,
  so_sach_toi_da   TINYINT UNSIGNED NOT NULL,   -- số cuốn được mượn cùng lúc
  so_ngay_muon     TINYINT UNSIGNED NOT NULL,   -- hạn mượn mỗi lượt (ngày)
  so_lan_gia_han   TINYINT UNSIGNED NOT NULL,   -- số lần được gia hạn tối đa
  CHECK (so_sach_toi_da > 0),
  CHECK (so_ngay_muon > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. theloai, nhaxuatban
-- ---------------------------------------------------------------------
CREATE TABLE theloai (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ten_theloai VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nhaxuatban (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ten_nxb  VARCHAR(150) NOT NULL UNIQUE,
  dia_chi  VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. nhanvien : tài khoản thủ thư / admin
-- ---------------------------------------------------------------------
CREATE TABLE nhanvien (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ho_ten         VARCHAR(100) NOT NULL,
  ten_dang_nhap  VARCHAR(30)  NOT NULL UNIQUE,
  mat_khau       VARCHAR(255) NOT NULL,          -- kết quả password_hash(), bắt buộc 255 ký tự
  vai_tro        ENUM('admin','thu_thu') NOT NULL DEFAULT 'thu_thu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. docgia : độc giả
-- ---------------------------------------------------------------------
CREATE TABLE docgia (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ma_doc_gia    VARCHAR(20)  NOT NULL UNIQUE,    -- dùng để độc giả tự đăng nhập tra cứu
  ho_ten        VARCHAR(100) NOT NULL,
  ngay_sinh     DATE         NULL,
  gioi_tinh     ENUM('nam','nu','khac') NOT NULL DEFAULT 'khac',
  dia_chi       VARCHAR(255) NULL,
  sdt           VARCHAR(15)  NULL,
  email         VARCHAR(100) NULL,
  id_loai       INT UNSIGNED NOT NULL,
  mat_khau      VARCHAR(255) NOT NULL,           -- password_hash()
  ngay_dang_ky  DATE         NOT NULL,
  trang_thai    ENUM('hoat_dong','khoa') NOT NULL DEFAULT 'hoat_dong',
  CONSTRAINT fk_docgia_loai FOREIGN KEY (id_loai) REFERENCES loaidocgia(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. sach : đầu sách
-- ---------------------------------------------------------------------
CREATE TABLE sach (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ma_sach       VARCHAR(20)  NOT NULL UNIQUE,
  ten_sach      VARCHAR(200) NOT NULL,
  tac_gia       VARCHAR(150) NOT NULL,
  id_theloai    INT UNSIGNED NOT NULL,
  id_nxb        INT UNSIGNED NOT NULL,
  nam_xuat_ban  SMALLINT UNSIGNED NULL,
  so_trang      SMALLINT UNSIGNED NULL,
  gia_tien      DECIMAL(12,0) NOT NULL DEFAULT 0,   -- dùng tính tiền bồi thường
  hinh_bia      VARCHAR(255) NULL,                  -- đường dẫn ảnh upload, NULL = chưa có ảnh
  so_luong      INT UNSIGNED NOT NULL DEFAULT 1,    -- tổng số bản
  so_con_lai    INT UNSIGNED NOT NULL DEFAULT 1,    -- số bản còn cho mượn
  mo_ta         TEXT NULL,
  CONSTRAINT fk_sach_theloai FOREIGN KEY (id_theloai) REFERENCES theloai(id),
  CONSTRAINT fk_sach_nxb     FOREIGN KEY (id_nxb)     REFERENCES nhaxuatban(id),
  CHECK (gia_tien >= 0),
  CHECK (so_con_lai <= so_luong),                   -- không bao giờ còn nhiều hơn tổng
  INDEX idx_sach_ten (ten_sach)                     -- phục vụ tìm kiếm theo tên
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. phieumuon : 1 phiếu = 1 lượt mượn (có thể nhiều cuốn)
-- ---------------------------------------------------------------------
CREATE TABLE phieumuon (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ma_phieu              VARCHAR(20)  NOT NULL UNIQUE,
  id_doc_gia            INT UNSIGNED NOT NULL,
  id_nhan_vien          INT UNSIGNED NOT NULL,          -- ai lập phiếu
  ngay_muon             DATE NOT NULL,
  ngay_hen_tra          DATE NOT NULL,                  -- = ngay_muon + so_ngay_muon (+ các lần gia hạn)
  so_lan_gia_han_da_dung TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- so với loaidocgia.so_lan_gia_han
  trang_thai            ENUM('dang_muon','da_tra','qua_han') NOT NULL DEFAULT 'dang_muon',
  CONSTRAINT fk_phieu_docgia   FOREIGN KEY (id_doc_gia)   REFERENCES docgia(id),
  CONSTRAINT fk_phieu_nhanvien FOREIGN KEY (id_nhan_vien) REFERENCES nhanvien(id),
  CHECK (ngay_hen_tra >= ngay_muon),
  INDEX idx_phieu_hen_tra (trang_thai, ngay_hen_tra)      -- phục vụ lọc phiếu quá hạn
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. chitietphieumuon : từng cuốn sách trong 1 phiếu
-- ---------------------------------------------------------------------
CREATE TABLE chitietphieumuon (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_phieu_muon    INT UNSIGNED NOT NULL,
  id_sach          INT UNSIGNED NOT NULL,
  ngay_tra_thuc_te DATE NULL,                                   -- NULL = chưa trả
  tinh_trang_tra   ENUM('nguyen_ven','hu_hong','mat') NULL,     -- NULL = chưa trả
  tien_phat        DECIMAL(12,0) NOT NULL DEFAULT 0,            -- phạt trả trễ
  tien_boi_thuong  DECIMAL(12,0) NOT NULL DEFAULT 0,            -- bồi thường hỏng/mất
  CONSTRAINT fk_ct_phieu FOREIGN KEY (id_phieu_muon) REFERENCES phieumuon(id) ON DELETE CASCADE,
  CONSTRAINT fk_ct_sach  FOREIGN KEY (id_sach)       REFERENCES sach(id),
  UNIQUE KEY uq_phieu_sach (id_phieu_muon, id_sach),            -- 1 phiếu không mượn trùng 1 đầu sách
  CHECK (tien_phat >= 0),
  CHECK (tien_boi_thuong >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- DATABASE SQL: BUKU PINTAR WARUNG & KASIR DIGITAL
-- SISTEM MANAJEMEN DATA & POS WARUNG KELONTONG 0% ADMIN
-- STUDI KASUS: RT 002 / RW 004 KEL. KEDAUNG KALI ANGKE, KEC. CENGKARENG, JAKARTA BARAT
-- ==============================================================================

-- Buat Database (Jika Belum Ada)
CREATE DATABASE IF NOT EXISTS `buku_kita` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `buku_kita`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `transaction_items`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `kasbon_pelanggan`;
DROP TABLE IF EXISTS `kas_buku`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `surat_pengajuan`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 1. TABEL USERS & PENGGUNA (ADMIN RT & PEMILIK WARUNG & WARGA)
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('pemilik_warung', 'admin_rt', 'kasir', 'warga') NOT NULL DEFAULT 'pemilik_warung',
  `nama_lengkap` VARCHAR(150) NOT NULL,
  `nik` VARCHAR(30) NULL,
  `no_hp` VARCHAR(30) NULL,
  `alamat` TEXT NULL,
  `no_kk` VARCHAR(30) NULL,
  `rt_rw` VARCHAR(50) DEFAULT 'RT 002 / RW 004',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama_lengkap`, `nik`, `no_hp`, `alamat`, `rt_rw`) VALUES
(1, 'warung', '$2y$10$wE9K2jTsmvF0oK.q/6oH.eP7p79OqP6fT9x8vP0.0Fwz2K8Jq3xW2', 'pemilik_warung', 'Ibu Siti Aminah (Warung Berkah RT 002)', '3173012209920005', '085611223344', 'Jl. Utama RT 002 No. 08, RW 004', 'RT 002 / RW 004'),
(2, 'admin', '$2y$10$wE9K2jTsmvF0oK.q/6oH.eP7p79OqP6fT9x8vP0.0Fwz2K8Jq3xW2', 'admin_rt', 'Bpk. H. Bambang Suherman (Ketua RT 002)', '3173011205750001', '081288990011', 'Jl. Kali Angke Indah No. 12, RT 002 / RW 004', 'RT 002 / RW 004'),
(3, 'warga', '$2y$10$wE9K2jTsmvF0oK.q/6oH.eP7p79OqP6fT9x8vP0.0Fwz2K8Jq3xW2', 'warga', 'Budi Santoso (Warga RT 002)', '3173011508900003', '081377889900', 'Jl. Pesing Poglar No. 45, RT 002 / RW 004', 'RT 002 / RW 004');

-- ------------------------------------------------------------------------------
-- 2. TABEL ETALASE PRODUK & BARANG WARUNG (SEMBAKO, GAS, GALON, ROKOK, DLL)
-- ------------------------------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT 1,
  `kode_produk` VARCHAR(50) NOT NULL UNIQUE,
  `nama_produk` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(50) NOT NULL DEFAULT 'sembako', -- sembako, minuman, snack, rokok, gas_galon, rumah_tangga, iuran_rt
  `harga_beli` DECIMAL(12,2) DEFAULT 0.00,
  `harga_jual` DECIMAL(12,2) NOT NULL,
  `stok` INT DEFAULT 0,
  `stok_minimum` INT DEFAULT 5,
  `satuan` VARCHAR(30) DEFAULT 'pcs',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`kategori`),
  INDEX (`stok`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `user_id`, `kode_produk`, `nama_produk`, `kategori`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `satuan`) VALUES
(1, 1, 'WRG-001', 'Beras Ramos Super 5 Kg', 'sembako', 62000.00, 68000.00, 18, 5, 'karung'),
(2, 1, 'WRG-002', 'Minyak Goreng Kita 2 Liter', 'sembako', 30000.00, 34000.00, 24, 6, 'pouch'),
(3, 1, 'WRG-003', 'Gula Pasir Kristal 1 Kg', 'sembako', 15000.00, 17500.00, 15, 5, 'kg'),
(4, 1, 'WRG-004', 'Telur Ayam Ras Fresh 1 Kg', 'sembako', 26000.00, 29000.00, 20, 4, 'kg'),
(5, 1, 'WRG-005', 'Tepung Terigu Segitiga Biru 1 Kg', 'sembako', 11000.00, 13000.00, 12, 3, 'kg'),
(6, 1, 'WRG-006', 'Gas Elpiji 3 Kg Melon', 'gas_galon', 19000.00, 22000.00, 14, 4, 'tabung'),
(7, 1, 'WRG-007', 'Air Galon Aqua Asli 19L', 'gas_galon', 18000.00, 21000.00, 10, 3, 'galon'),
(8, 1, 'WRG-008', 'Air Mineral Le Minerale 600ml', 'minuman', 2800.00, 3500.00, 36, 10, 'botol'),
(9, 1, 'WRG-009', 'Teh Pucuk Harum 350ml', 'minuman', 3200.00, 4000.00, 28, 8, 'botol'),
(10, 1, 'WRG-010', 'Indomie Goreng Original 85g', 'sembako', 2800.00, 3500.00, 80, 20, 'bungkus'),
(11, 1, 'WRG-011', 'Indomie Kuah Ayam Bawang 75g', 'sembako', 2800.00, 3500.00, 60, 15, 'bungkus'),
(12, 1, 'WRG-012', 'Kopi Kapal Api Spesial Mix (Renceng)', 'minuman', 12500.00, 15000.00, 15, 3, 'renceng'),
(13, 1, 'WRG-013', 'Rokok Sampoerna Mild 16', 'rokok', 33500.00, 36000.00, 12, 3, 'bungkus'),
(14, 1, 'WRG-014', 'Rokok Djarum Super 12', 'rokok', 22500.00, 25000.00, 10, 3, 'bungkus'),
(15, 1, 'WRG-015', 'Chiki Taro Net Potato BBQ 36g', 'snack', 4000.00, 5000.00, 20, 5, 'bungkus'),
(16, 1, 'WRG-016', 'Sabun Cuci Piring Sunlight 700ml', 'rumah_tangga', 13500.00, 16000.00, 10, 3, 'pouch'),
(17, 1, 'WRG-017', 'Deterjen Rinso Molto 770g', 'rumah_tangga', 18000.00, 21500.00, 8, 2, 'bungkus'),
(18, 1, 'WRG-018', 'Iuran Warga RT 002 (Kebersihan + Keamanan)', 'iuran_rt', 0.00, 50000.00, 999, 0, 'bulan');

-- ------------------------------------------------------------------------------
-- 3. TABEL TRANSAKSI KASIR POS (BI QRIS, CASH, TRANSFER, KASBON)
-- ------------------------------------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(60) NOT NULL UNIQUE,
  `user_id` INT DEFAULT 1,
  `tipe_transaksi` VARCHAR(50) NOT NULL DEFAULT 'penjualan_warung',
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `total_modal` DECIMAL(12,2) DEFAULT 0.00,
  `laba_kotor` DECIMAL(12,2) DEFAULT 0.00,
  `bayar_tunai` DECIMAL(12,2) DEFAULT 0.00,
  `kembalian` DECIMAL(12,2) DEFAULT 0.00,
  `fee_admin` DECIMAL(12,2) DEFAULT 0.00,
  `payment_method` VARCHAR(30) NOT NULL DEFAULT 'tunai', -- tunai, qris_bi, bca_va, mandiri_va, bri_va, bni_va, kasbon
  `payment_status` ENUM('paid', 'pending', 'kasbon') NOT NULL DEFAULT 'paid',
  `qris_payload` TEXT NULL,
  `va_number` VARCHAR(60) NULL,
  `bank_reference` VARCHAR(60) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `paid_at` TIMESTAMP NULL,
  `note` TEXT NULL,
  INDEX (`payment_status`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABEL DETAIL ITEM TRANSAKSI KASIR
-- ------------------------------------------------------------------------------
CREATE TABLE `transaction_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT NOT NULL,
  `product_id` INT NULL,
  `nama_item` VARCHAR(200) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `harga_beli_satuan` DECIMAL(12,2) DEFAULT 0.00,
  `harga_satuan` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `subtotal_laba` DECIMAL(12,2) DEFAULT 0.00,
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. TABEL BUKU KAS BON / HUTANG WARGA (REMINDER WHATSAPP & QRIS)
-- ------------------------------------------------------------------------------
CREATE TABLE `kasbon_pelanggan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT NULL,
  `nama_pelanggan` VARCHAR(150) NOT NULL,
  `no_hp` VARCHAR(30) NULL,
  `alamat` TEXT NULL,
  `total_hutang` DECIMAL(12,2) NOT NULL,
  `sudah_dibayar` DECIMAL(12,2) DEFAULT 0.00,
  `sisa_hutang` DECIMAL(12,2) NOT NULL,
  `status` ENUM('belum_lunas', 'lunas') DEFAULT 'belum_lunas',
  `jatuh_tempo` DATE NULL,
  `tanggal_hutang` DATE DEFAULT (CURRENT_DATE),
  `tanggal_lunas` DATE NULL,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`status`),
  INDEX (`jatuh_tempo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kasbon_pelanggan` (`id`, `nama_pelanggan`, `no_hp`, `alamat`, `total_hutang`, `sudah_dibayar`, `sisa_hutang`, `status`, `jatuh_tempo`, `tanggal_hutang`, `keterangan`) VALUES
(1, 'Pak Joko (Warga RT 002)', '081288776655', 'Jl. Kali Angke Gang 1 No. 04', 85000.00, 0.00, 85000.00, 'belum_lunas', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 'Belanja beras 5kg + minyak 2L (Bayar saat gajian)'),
(2, 'Mas Kevin (Warga RT 002)', '085799881122', 'Jl. Pesing RT 002 No. 19', 45000.00, 20000.00, 25000.00, 'belum_lunas', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'Rokok sampoerna + kopi (Sudah dicicil 20rb)');

-- ------------------------------------------------------------------------------
-- 6. TABEL BUKU KAS & LABA RUGI OPERASIONAL
-- ------------------------------------------------------------------------------
CREATE TABLE `kas_buku` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT NULL,
  `tipe` ENUM('masuk', 'keluar') NOT NULL,
  `kategori` VARCHAR(100) NOT NULL, -- Penjualan Warung, Modal Kulakan, Operasional Listrik/Air, Bayar Kasbon, Iuran Lingkungan RT
  `nominal` DECIMAL(12,2) NOT NULL,
  `keterangan` TEXT NOT NULL,
  `tanggal` DATE DEFAULT (CURRENT_DATE),
  `petugas` VARCHAR(100) DEFAULT 'Pemilik Warung',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`tipe`),
  INDEX (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kas_buku` (`id`, `tipe`, `kategori`, `nominal`, `keterangan`, `tanggal`, `petugas`) VALUES
(1, 'masuk', 'Penjualan Warung', 850000.00, 'Omset Penjualan Warung Harian', DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 'Ibu Siti Aminah'),
(2, 'keluar', 'Modal Kulakan', 620000.00, 'Kulakan Beras & Minyak Goreng di Agen Pasar', DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 'Ibu Siti Aminah'),
(3, 'keluar', 'Operasional Listrik/Air', 50000.00, 'Token Listrik Kulkas Minuman Warung', CURRENT_DATE, 'Ibu Siti Aminah');

-- ------------------------------------------------------------------------------
-- 7. TABEL SURAT & LAYANAN WARGA RT 002 (PROSES KILAT 1 MENIT)
-- ------------------------------------------------------------------------------
CREATE TABLE `surat_pengajuan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `no_pengajuan` VARCHAR(60) NOT NULL UNIQUE,
  `nama_pemohon` VARCHAR(150) NOT NULL,
  `nik` VARCHAR(30) NOT NULL,
  `jenis_surat` VARCHAR(100) NOT NULL,
  `keperluan` TEXT NOT NULL,
  `status` ENUM('menunggu', 'disetujui', 'ditolak') DEFAULT 'menunggu',
  `catatan_rt` TEXT NULL,
  `waktu_pengajuan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `waktu_proses` TIMESTAMP NULL,
  `no_surat_resmi` VARCHAR(100) NULL,
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `surat_pengajuan` (`id`, `user_id`, `no_pengajuan`, `nama_pemohon`, `nik`, `jenis_surat`, `keperluan`, `status`, `no_surat_resmi`) VALUES
(1, 3, 'SRT-2026-001', 'Budi Santoso', '3173011508900003', 'Surat Pengantar Domisili', 'Pengurusan Pembukaan Rekening Bank & Bantuan UMKM', 'disetujui', '005/RT002/RW004/IX/2026');

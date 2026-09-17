<?php
// ==============================================================================
// BUKU PINTAR WARUNG & KASIR DIGITAL (BI QRIS & MULTI-BANK 0% ADMIN FEE)
// TOKO / WARUNG KELONTONG RT 002 / RW 004 KEL. KEDAUNG KALI ANGKE, CENGKARENG
// ==============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection via SQLite
$db_file = __DIR__ . '/buku_kita.db';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Initialize tables
function initDatabase($pdo) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'pemilik_warung', -- 'pemilik_warung', 'kasir', 'warga'
            nama_lengkap TEXT NOT NULL,
            nik TEXT,
            no_hp TEXT,
            alamat TEXT,
            no_kk TEXT,
            rt_rw TEXT DEFAULT 'RT 002 / RW 004',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode_produk TEXT UNIQUE NOT NULL,
            nama_produk TEXT NOT NULL,
            kategori TEXT NOT NULL, -- 'sembako', 'minuman', 'snack', 'rokok', 'gas_galon', 'rumah_tangga', 'iuran_rt'
            harga_beli REAL DEFAULT 0,
            harga_jual REAL NOT NULL,
            stok INTEGER DEFAULT 0,
            stok_minimum INTEGER DEFAULT 5,
            satuan TEXT DEFAULT 'pcs',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_no TEXT UNIQUE NOT NULL,
            user_id INTEGER,
            tipe_transaksi TEXT NOT NULL, -- 'penjualan_warung', 'pembelian_stok', 'iuran_rt'
            customer_name TEXT NOT NULL,
            customer_phone TEXT,
            total_amount REAL NOT NULL,
            total_modal REAL DEFAULT 0,
            laba_kotor REAL DEFAULT 0,
            bayar_tunai REAL DEFAULT 0,
            kembalian REAL DEFAULT 0,
            fee_admin REAL DEFAULT 0, -- 0% MDR
            payment_method TEXT NOT NULL, -- 'tunai', 'qris_bi', 'bca_va', 'mandiri_va', 'bri_va', 'bni_va', 'kasbon'
            payment_status TEXT NOT NULL DEFAULT 'paid', -- 'paid', 'pending', 'kasbon'
            qris_payload TEXT,
            va_number TEXT,
            bank_reference TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME,
            note TEXT
        )",
        "CREATE TABLE IF NOT EXISTS transaction_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NOT NULL,
            product_id INTEGER,
            nama_item TEXT NOT NULL,
            qty INTEGER NOT NULL,
            harga_beli_satuan REAL DEFAULT 0,
            harga_satuan REAL NOT NULL,
            subtotal REAL NOT NULL,
            subtotal_laba REAL DEFAULT 0,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS kasbon_pelanggan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER,
            nama_pelanggan TEXT NOT NULL,
            no_hp TEXT,
            alamat TEXT,
            total_hutang REAL NOT NULL,
            sudah_dibayar REAL DEFAULT 0,
            sisa_hutang REAL NOT NULL,
            status TEXT DEFAULT 'belum_lunas', -- 'belum_lunas', 'lunas'
            jatuh_tempo DATE,
            tanggal_hutang DATE DEFAULT CURRENT_DATE,
            tanggal_lunas DATE,
            keterangan TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS kas_buku (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER,
            tipe TEXT NOT NULL, -- 'masuk', 'keluar'
            kategori TEXT NOT NULL, -- 'Penjualan Warung', 'Modal Kulakan', 'Operasional Listrik/Air', 'Bayar Kasbon', 'Iuran Lingkungan RT'
            nominal REAL NOT NULL,
            keterangan TEXT NOT NULL,
            tanggal DATE DEFAULT CURRENT_DATE,
            petugas TEXT DEFAULT 'Pemilik Warung',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS surat_pengajuan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            no_pengajuan TEXT UNIQUE NOT NULL,
            nama_pemohon TEXT NOT NULL,
            nik TEXT NOT NULL,
            jenis_surat TEXT NOT NULL,
            keperluan TEXT NOT NULL,
            status TEXT DEFAULT 'menunggu',
            catatan_rt TEXT,
            waktu_pengajuan DATETIME DEFAULT CURRENT_TIMESTAMP,
            waktu_proses DATETIME,
            no_surat_resmi TEXT
        )"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }

    // Auto-migrate columns if missing
    $alterQueries = [
        "ALTER TABLE transactions ADD COLUMN total_modal REAL DEFAULT 0",
        "ALTER TABLE transactions ADD COLUMN laba_kotor REAL DEFAULT 0",
        "ALTER TABLE transactions ADD COLUMN bayar_tunai REAL DEFAULT 0",
        "ALTER TABLE transactions ADD COLUMN kembalian REAL DEFAULT 0",
        "ALTER TABLE products ADD COLUMN stok_minimum INTEGER DEFAULT 5",
        "ALTER TABLE transaction_items ADD COLUMN harga_beli_satuan REAL DEFAULT 0",
        "ALTER TABLE transaction_items ADD COLUMN subtotal_laba REAL DEFAULT 0"
    ];

    foreach ($alterQueries as $aq) {
        try {
            $pdo->exec($aq);
        } catch (Exception $e) {
            // Column already exists, safe to ignore
        }
    }

    // Seed users
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUsers == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap, nik, no_hp, alamat, rt_rw) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Pemilik Warung & Admin RT
        $stmt->execute([
            'warung',
            password_hash('warung123', PASSWORD_DEFAULT),
            'pemilik_warung',
            'Ibu Siti Aminah (Warung Berkah RT 002)',
            '3173012209920005',
            '085611223344',
            'Jl. Utama RT 002 No. 08, RW 004',
            'RT 002 / RW 004'
        ]);

        $stmt->execute([
            'admin',
            password_hash('admin123', PASSWORD_DEFAULT),
            'pemilik_warung',
            'Bpk. H. Bambang Suherman (Ketua RT 002)',
            '3173011205750001',
            '081288990011',
            'Jl. Kali Angke Indah No. 12, RT 002 / RW 004',
            'RT 002 / RW 004'
        ]);

        $stmt->execute([
            'warga',
            password_hash('warga123', PASSWORD_DEFAULT),
            'warga',
            'Budi Santoso',
            '3173011508900003',
            '081377889900',
            'Jl. Pesing Poglar No. 45, RT 002 / RW 004',
            'RT 002 / RW 004'
        ]);
    }

    // Seed Warung products
    $checkProd = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($checkProd == 0) {
        $products = [
            ['WRG-001', 'Beras Ramos Super 5 Kg', 'sembako', 62000, 68000, 18, 5, 'karung'],
            ['WRG-002', 'Minyak Goreng Kita 2 Liter', 'sembako', 30000, 34000, 24, 6, 'pouch'],
            ['WRG-003', 'Gula Pasir Kristal 1 Kg', 'sembako', 15000, 17500, 15, 5, 'kg'],
            ['WRG-004', 'Telur Ayam Ras Fresh 1 Kg', 'sembako', 26000, 29000, 20, 4, 'kg'],
            ['WRG-005', 'Tepung Terigu Segitiga Biru 1 Kg', 'sembako', 11000, 13000, 12, 3, 'kg'],
            ['WRG-006', 'Gas Elpiji 3 Kg Melon', 'gas_galon', 19000, 22000, 14, 4, 'tabung'],
            ['WRG-007', 'Air Galon Aqua Asli 19L', 'gas_galon', 18000, 21000, 10, 3, 'galon'],
            ['WRG-008', 'Air Mineral Le Minerale 600ml', 'minuman', 2800, 3500, 36, 10, 'botol'],
            ['WRG-009', 'Teh Pucuk Harum 350ml', 'minuman', 3200, 4000, 28, 8, 'botol'],
            ['WRG-010', 'Indomie Goreng Original 85g', 'sembako', 2800, 3500, 80, 20, 'bungkus'],
            ['WRG-011', 'Indomie Kuah Ayam Bawang 75g', 'sembako', 2800, 3500, 60, 15, 'bungkus'],
            ['WRG-012', 'Kopi Kapal Api Spesial Mix (Renceng)', 'minuman', 12500, 15000, 15, 3, 'renceng'],
            ['WRG-013', 'Rokok Sampoerna Mild 16', 'rokok', 33500, 36000, 12, 3, 'bungkus'],
            ['WRG-014', 'Rokok Djarum Super 12', 'rokok', 22500, 25000, 10, 3, 'bungkus'],
            ['WRG-015', 'Chiki Taro Net Potato BBQ 36g', 'snack', 4000, 5000, 20, 5, 'bungkus'],
            ['WRG-016', 'Sabun Cuci Piring Sunlight 700ml', 'rumah_tangga', 13500, 16000, 10, 3, 'pouch'],
            ['WRG-017', 'Deterjen Rinso Molto 770g', 'rumah_tangga', 18000, 21500, 8, 2, 'bungkus'],
            ['WRG-018', 'Iuran Warga RT 002 (Kebersihan + Keamanan)', 'iuran_rt', 0, 50000, 999, 0, 'bulan']
        ];
        $stmtP = $pdo->prepare("INSERT INTO products (kode_produk, nama_produk, kategori, harga_beli, harga_jual, stok, stok_minimum, satuan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmtP->execute($p);
        }
    }

    // Seed Kasbon Sample
    $checkKasbon = $pdo->query("SELECT COUNT(*) FROM kasbon_pelanggan")->fetchColumn();
    if ($checkKasbon == 0) {
        $stmtKb = $pdo->prepare("INSERT INTO kasbon_pelanggan (nama_pelanggan, no_hp, alamat, total_hutang, sudah_dibayar, sisa_hutang, status, jatuh_tempo, tanggal_hutang, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtKb->execute([
            'Pak Joko (Warga RT 002)',
            '081288776655',
            'Jl. Kali Angke Gang 1 No. 04',
            85000,
            0,
            85000,
            'belum_lunas',
            date('Y-m-d', strtotime('+7 days')),
            date('Y-m-d', strtotime('-2 days')),
            'Belanja beras 5kg + minyak 2L (Bayar saat gajian)'
        ]);
        $stmtKb->execute([
            'Mas Kevin (Warga RT 002)',
            '085799881122',
            'Jl. Pesing RT 002 No. 19',
            45000,
            20000,
            25000,
            'belum_lunas',
            date('Y-m-d', strtotime('+3 days')),
            date('Y-m-d', strtotime('-5 days')),
            'Rokok sampoerna + kopi (Sudah dicicil 20rb)'
        ]);
    }

    // Seed Kas Buku
    $checkKas = $pdo->query("SELECT COUNT(*) FROM kas_buku")->fetchColumn();
    if ($checkKas == 0) {
        $kasList = [
            ['masuk', 'Penjualan Warung', 850000, 'Omset Penjualan Warung Harian', date('Y-m-d', strtotime('-1 day')), 'Ibu Siti Aminah'],
            ['keluar', 'Modal Kulakan', 620000, 'Kulakan Beras & Minyak Goreng di Agen Pasar', date('Y-m-d', strtotime('-1 day')), 'Ibu Siti Aminah'],
            ['keluar', 'Operasional Listrik/Air', 50000, 'Token Listrik Kulkas Minuman Warung', date('Y-m-d'), 'Ibu Siti Aminah']
        ];
        $stmtK = $pdo->prepare("INSERT INTO kas_buku (tipe, kategori, nominal, keterangan, tanggal, petugas) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($kasList as $k) {
            $stmtK->execute($k);
        }
    }
}

// Run DB initialization
initDatabase($pdo);

// Helper for Bank Indonesia QRIS Generator (EMVCo Standard with CRC16-CCITT)
class BIQrisHelper {
    public static function crc16($data) {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
        }
        return strtoupper(sprintf('%04X', $crc));
    }

    public static function generatePayload($merchantName, $invoiceNo, $amount, $city = "JAKARTA BARAT", $postalCode = "11710") {
        $formattedAmount = number_format($amount, 2, '.', '');
        
        $p00 = "000201";
        $p01 = "010212"; // Dynamic QR
        
        $tag26_gui = "0016ID.CO.QRIS.WWW";
        $tag26_nmid = "0118ID1020023489234567";
        $tag26_criteria = "0215000000000000000";
        $tag26_data = $tag26_gui . $tag26_nmid . $tag26_criteria;
        $tag26_len = sprintf('%02d', strlen($tag26_data));
        $p26 = "26" . $tag26_len . $tag26_data;

        $tag51_gui = "0014ID.LINKAJA.WWW";
        $tag51_id = "0115936009990000123";
        $tag51_data = $tag51_gui . $tag51_id;
        $tag51_len = sprintf('%02d', strlen($tag51_data));
        $p51 = "51" . $tag51_len . $tag51_data;

        $p52 = "52045999";
        $p53 = "5303360";
        
        $amtLen = sprintf('%02d', strlen($formattedAmount));
        $p54 = "54" . $amtLen . $formattedAmount;

        $p58 = "5802ID";
        
        $cleanMerchant = substr(strtoupper($merchantName), 0, 25);
        $p59 = "59" . sprintf('%02d', strlen($cleanMerchant)) . $cleanMerchant;

        $cleanCity = substr(strtoupper($city), 0, 15);
        $p60 = "60" . sprintf('%02d', strlen($cleanCity)) . $cleanCity;

        $p61 = "61" . sprintf('%02d', strlen($postalCode)) . $postalCode;

        $tag62_bill = "01" . sprintf('%02d', strlen($invoiceNo)) . $invoiceNo;
        $tag62_term = "0708WARUNGRT2";
        $tag62_data = $tag62_bill . $tag62_term;
        $p62 = "62" . sprintf('%02d', strlen($tag62_data)) . $tag62_data;

        $raw = $p00 . $p01 . $p26 . $p51 . $p52 . $p53 . $p54 . $p58 . $p59 . $p60 . $p61 . $p62 . "6304";
        $crc = self::crc16($raw);
        
        return $raw . $crc;
    }
}

function generateVirtualAccount($bankCode, $invoiceNo) {
    $numericOnly = preg_replace('/[^0-9]/', '', $invoiceNo . time());
    $tail = substr($numericOnly, -8);
    
    $bankPrefixes = [
        'bca_va' => '70012',
        'mandiri_va' => '88908',
        'bri_va' => '12888',
        'bni_va' => '98888',
        'permata_va' => '85555',
        'seabank_va' => '78299',
        'bsi_va' => '90022'
    ];

    $prefix = $bankPrefixes[$bankCode] ?? '88000';
    return $prefix . $tail;
}

function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

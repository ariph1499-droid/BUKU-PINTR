<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function jsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function getCurrentUser($pdo) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, username, role, nama_lengkap, nik, no_hp, alamat, no_kk, rt_rw FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) return $user;
    }
    // Default fallback to Pemilik Warung
    $stmt = $pdo->query("SELECT id, username, role, nama_lengkap, nik, no_hp, alamat, no_kk, rt_rw FROM users WHERE role = 'pemilik_warung' LIMIT 1");
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        return $user;
    }
    return null;
}

$currentUser = getCurrentUser($pdo);

switch ($action) {
    // ==========================================
    // 1. AUTH & USER MANAGEMENT
    // ==========================================
    case 'get_current_user':
        jsonResponse(true, 'Data user aktif', $currentUser);
        break;

    case 'switch_user':
        $role = $_POST['role'] ?? 'pemilik_warung';
        $stmt = $pdo->prepare("SELECT id, username, role, nama_lengkap, nik, no_hp, alamat, no_kk, rt_rw FROM users WHERE role = ? LIMIT 1");
        $stmt->execute([$role]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            jsonResponse(true, "Beralih ke: {$user['nama_lengkap']}", $user);
        }
        jsonResponse(false, "User role {$role} tidak ditemukan");
        break;

    // ==========================================
    // 2. DASHBOARD WARGA & KEUANGAN WARUNG
    // ==========================================
    case 'get_warung_dashboard':
        $today = date('Y-m-d');

        // Omset & Laba Hari Ini
        $stmtToday = $pdo->prepare("SELECT 
            COALESCE(SUM(total_amount), 0) as omset_hari_ini,
            COALESCE(SUM(total_modal), 0) as modal_hari_ini,
            COALESCE(SUM(laba_kotor), 0) as laba_hari_ini,
            COUNT(*) as trx_count
            FROM transactions 
            WHERE DATE(created_at) = ? AND payment_status != 'kasbon'");
        $stmtToday->execute([$today]);
        $todayStats = $stmtToday->fetch();

        // Total Kasbon Belum Lunas
        $totalKasbon = $pdo->query("SELECT COALESCE(SUM(sisa_hutang), 0) FROM kasbon_pelanggan WHERE status = 'belum_lunas'")->fetchColumn();
        $countKasbon = $pdo->query("SELECT COUNT(*) FROM kasbon_pelanggan WHERE status = 'belum_lunas'")->fetchColumn();

        // Peringatan Stok Menipis (< 5 atau <= stok_minimum)
        $stokMenipis = $pdo->query("SELECT COUNT(*) FROM products WHERE stok <= stok_minimum AND kategori != 'iuran_rt'")->fetchColumn();
        $listMenipis = $pdo->query("SELECT id, kode_produk, nama_produk, stok, stok_minimum, satuan FROM products WHERE stok <= stok_minimum AND kategori != 'iuran_rt' ORDER BY stok ASC LIMIT 5")->fetchAll();

        // Saldo Kas Warung (Buku Kas)
        $totalMasuk = $pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM kas_buku WHERE tipe = 'masuk'")->fetchColumn();
        $totalKeluar = $pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM kas_buku WHERE tipe = 'keluar'")->fetchColumn();
        $saldoKas = $totalMasuk - $totalKeluar;

        // Total Omset Bulanan
        $curMonth = date('Y-m');
        $stmtMonth = $pdo->prepare("SELECT 
            COALESCE(SUM(total_amount), 0) as omset_bulan_ini,
            COALESCE(SUM(laba_kotor), 0) as laba_bulan_ini
            FROM transactions 
            WHERE strftime('%Y-%m', created_at) = ? AND payment_status != 'kasbon'");
        $stmtMonth->execute([$curMonth]);
        $monthStats = $stmtMonth->fetch();

        jsonResponse(true, 'Statistik Warung Pintar', [
            'omset_hari_ini' => floatval($todayStats['omset_hari_ini']),
            'laba_hari_ini' => floatval($todayStats['laba_hari_ini']),
            'trx_count_today' => intval($todayStats['trx_count']),
            'omset_bulan_ini' => floatval($monthStats['omset_bulan_ini']),
            'laba_bulan_ini' => floatval($monthStats['laba_bulan_ini']),
            'total_piutang_kasbon' => floatval($totalKasbon),
            'pelanggan_kasbon_count' => intval($countKasbon),
            'stok_menipis_count' => intval($stokMenipis),
            'stok_menipis_list' => $listMenipis,
            'saldo_kas_warung' => floatval($saldoKas)
        ]);
        break;

    // ==========================================
    // 3. PRODUK & INVENTARIS STOK WARUNG
    // ==========================================
    case 'get_products':
        $kategori = $_GET['kategori'] ?? '';
        $query = "SELECT * FROM products";
        if ($kategori) {
            $query .= " WHERE kategori = " . $pdo->quote($kategori);
        }
        $query .= " ORDER BY nama_produk ASC";
        $stmt = $pdo->query($query);
        $products = $stmt->fetchAll();
        jsonResponse(true, 'Daftar produk warung', $products);
        break;

    case 'save_product':
        $id = intval($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_produk'] ?? '');
        $kategori = $_POST['kategori'] ?? 'sembako';
        $harga_beli = floatval($_POST['harga_beli'] ?? 0);
        $harga_jual = floatval($_POST['harga_jual'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $stok_min = intval($_POST['stok_minimum'] ?? 5);
        $satuan = trim($_POST['satuan'] ?? 'pcs');

        if (!$nama || $harga_jual <= 0) {
            jsonResponse(false, 'Nama barang dan harga jual wajib diisi');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET nama_produk = ?, kategori = ?, harga_beli = ?, harga_jual = ?, stok = ?, stok_minimum = ?, satuan = ? WHERE id = ?");
            $stmt->execute([$nama, $kategori, $harga_beli, $harga_jual, $stok, $stok_min, $satuan, $id]);
            jsonResponse(true, 'Data produk berhasil diperbarui');
        } else {
            $kode = 'WRG-' . strtoupper(substr(uniqid(), -5));
            $stmt = $pdo->prepare("INSERT INTO products (kode_produk, nama_produk, kategori, harga_beli, harga_jual, stok, stok_minimum, satuan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode, $nama, $kategori, $harga_beli, $harga_jual, $stok, $stok_min, $satuan]);
            jsonResponse(true, 'Produk baru berhasil ditambahkan');
        }
        break;

    case 'restock_product':
        $id = intval($_POST['product_id'] ?? 0);
        $tambah_stok = intval($_POST['tambah_stok'] ?? 0);
        $harga_beli_baru = floatval($_POST['harga_beli_baru'] ?? 0);

        if ($id <= 0 || $tambah_stok <= 0) {
            jsonResponse(false, 'Jumlah stok kulakan harus lebih dari 0');
        }

        $stmtP = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmtP->execute([$id]);
        $prod = $stmtP->fetch();

        if (!$prod) {
            jsonResponse(false, 'Produk tidak ditemukan');
        }

        $hb = ($harga_beli_baru > 0) ? $harga_beli_baru : $prod['harga_beli'];
        $total_kulakan = $tambah_stok * $hb;

        $pdo->beginTransaction();
        try {
            $stmtUp = $pdo->prepare("UPDATE products SET stok = stok + ?, harga_beli = ? WHERE id = ?");
            $stmtUp->execute([$tambah_stok, $hb, $id]);

            // Catat pengeluaran kulakan di buku kas
            $stmtKas = $pdo->prepare("INSERT INTO kas_buku (tipe, kategori, nominal, keterangan, tanggal, petugas) VALUES ('keluar', 'Modal Kulakan', ?, ?, CURRENT_DATE, ?)");
            $stmtKas->execute([$total_kulakan, "Kulakan {$prod['nama_produk']} x{$tambah_stok} {$prod['satuan']}", $currentUser['nama_lengkap'] ?? 'Pemilik Warung']);

            $pdo->commit();
            jsonResponse(true, "Berhasil menambah {$tambah_stok} {$prod['satuan']} stok {$prod['nama_produk']}");
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal restock: ' . $e->getMessage());
        }
        break;

    case 'delete_product':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(true, 'Barang berhasil dihapus dari etalase');
        }
        jsonResponse(false, 'ID Produk tidak valid');
        break;

    // ==========================================
    // 4. KASIR POS & TRANSAKSI WARUNG
    // ==========================================
    case 'create_pos_transaction':
        $customer_name = trim($_POST['customer_name'] ?? 'Pelanggan Umum');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $payment_method = $_POST['payment_method'] ?? 'tunai'; // 'tunai', 'qris_bi', 'bca_va', 'mandiri_va', 'bri_va', 'bni_va', 'kasbon'
        $bayar_tunai = floatval($_POST['bayar_tunai'] ?? 0);
        $jatuh_tempo = $_POST['jatuh_tempo'] ?? date('Y-m-d', strtotime('+7 days'));
        $note = trim($_POST['note'] ?? '');
        $items_raw = $_POST['items'] ?? '[]';
        $items = is_string($items_raw) ? json_decode($items_raw, true) : $items_raw;

        if (empty($items) || !is_array($items)) {
            jsonResponse(false, 'Keranjang belanja tidak boleh kosong');
        }

        $total_amount = 0;
        $total_modal = 0;
        foreach ($items as $item) {
            $qty = intval($item['qty'] ?? 1);
            $price = floatval($item['harga_satuan'] ?? 0);
            $hb = floatval($item['harga_beli_satuan'] ?? 0);
            $total_amount += ($qty * $price);
            $total_modal += ($qty * $hb);
        }

        $laba_kotor = $total_amount - $total_modal;
        $kembalian = ($payment_method === 'tunai' && $bayar_tunai >= $total_amount) ? ($bayar_tunai - $total_amount) : 0;

        $invoice_no = 'NOTA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        
        // QRIS Payload
        $merchant_name = "WARUNG BERKAH RT 002";
        $qris_payload = BIQrisHelper::generatePayload($merchant_name, $invoice_no, $total_amount);
        $va_number = generateVirtualAccount($payment_method, $invoice_no);
        $bank_ref = 'BIFAST-' . rand(10000000, 99999999);

        $initial_status = ($payment_method === 'kasbon') ? 'kasbon' : (($payment_method === 'tunai') ? 'paid' : 'pending');
        $paid_at = ($initial_status === 'paid') ? date('Y-m-d H:i:s') : null;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions 
                (invoice_no, user_id, tipe_transaksi, customer_name, customer_phone, total_amount, total_modal, laba_kotor, bayar_tunai, kembalian, fee_admin, payment_method, payment_status, qris_payload, va_number, bank_reference, paid_at, note) 
                VALUES (?, ?, 'penjualan_warung', ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $invoice_no,
                $currentUser['id'] ?? 1,
                $customer_name,
                $customer_phone,
                $total_amount,
                $total_modal,
                $laba_kotor,
                $bayar_tunai,
                $kembalian,
                $payment_method,
                $initial_status,
                $qris_payload,
                $va_number,
                $bank_ref,
                $paid_at,
                $note
            ]);

            $trx_id = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, nama_item, qty, harga_beli_satuan, harga_satuan, subtotal, subtotal_laba) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtStock = $pdo->prepare("UPDATE products SET stok = MAX(0, stok - ?) WHERE id = ?");

            foreach ($items as $item) {
                $pid = !empty($item['product_id']) ? intval($item['product_id']) : null;
                $pname = $item['nama_item'] ?? 'Barang Warung';
                $qty = intval($item['qty'] ?? 1);
                $unit_price = floatval($item['harga_satuan'] ?? 0);
                $unit_hb = floatval($item['harga_beli_satuan'] ?? 0);
                $subtotal = $qty * $unit_price;
                $subtotal_laba = $subtotal - ($qty * $unit_hb);

                $stmtItem->execute([$trx_id, $pid, $pname, $qty, $unit_hb, $unit_price, $subtotal, $subtotal_laba]);

                if ($pid) {
                    $stmtStock->execute([$qty, $pid]);
                }
            }

            // If Kasbon / Hutang
            if ($payment_method === 'kasbon') {
                $stmtKb = $pdo->prepare("INSERT INTO kasbon_pelanggan (transaction_id, nama_pelanggan, no_hp, total_hutang, sudah_dibayar, sisa_hutang, status, jatuh_tempo, keterangan) VALUES (?, ?, ?, ?, 0, ?, 'belum_lunas', ?, ?)");
                $stmtKb->execute([$trx_id, $customer_name, $customer_phone, $total_amount, $total_amount, $jatuh_tempo, "Kasbon Nota #{$invoice_no} ({$note})"]);
            }

            // If Paid Cash -> Catat ke Kas Buku Warung
            if ($initial_status === 'paid') {
                $stmtKas = $pdo->prepare("INSERT INTO kas_buku (transaction_id, tipe, kategori, nominal, keterangan, tanggal, petugas) VALUES (?, 'masuk', 'Penjualan Warung', ?, ?, CURRENT_DATE, ?)");
                $stmtKas->execute([$trx_id, $total_amount, "Penjualan Kasir Nota #{$invoice_no} ({$customer_name})", $currentUser['nama_lengkap'] ?? 'Kasir']);
            }

            $pdo->commit();

            jsonResponse(true, 'Transaksi kasir berhasil diproses', [
                'transaction_id' => $trx_id,
                'invoice_no' => $invoice_no,
                'total_amount' => $total_amount,
                'bayar_tunai' => $bayar_tunai,
                'kembalian' => $kembalian,
                'payment_method' => $payment_method,
                'payment_status' => $initial_status,
                'qris_payload' => $qris_payload,
                'va_number' => $va_number,
                'customer_name' => $customer_name,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal memproses transaksi kasir: ' . $e->getMessage());
        }
        break;

    // ==========================================
    // 5. BUKU KAS BON / HUTANG PELANGGAN
    // ==========================================
    case 'get_kasbon_list':
        $status = $_GET['status'] ?? '';
        $query = "SELECT * FROM kasbon_pelanggan";
        if ($status) {
            $query .= " WHERE status = " . $pdo->quote($status);
        }
        $query .= " ORDER BY status ASC, jatuh_tempo ASC, id DESC";
        $stmt = $pdo->query($query);
        $kasbon = $stmt->fetchAll();
        jsonResponse(true, 'Daftar buku kasbon / hutang pelanggan', $kasbon);
        break;

    case 'add_kasbon':
        $nama = trim($_POST['nama_pelanggan'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $nominal = floatval($_POST['nominal'] ?? 0);
        $jatuh_tempo = $_POST['jatuh_tempo'] ?? date('Y-m-d', strtotime('+7 days'));
        $keterangan = trim($_POST['keterangan'] ?? '');

        if (!$nama || $nominal <= 0) {
            jsonResponse(false, 'Nama pelanggan dan nominal hutang wajib diisi');
        }

        $stmt = $pdo->prepare("INSERT INTO kasbon_pelanggan (nama_pelanggan, no_hp, alamat, total_hutang, sudah_dibayar, sisa_hutang, status, jatuh_tempo, keterangan) VALUES (?, ?, ?, ?, 0, ?, 'belum_lunas', ?, ?)");
        $stmt->execute([$nama, $no_hp, $alamat, $nominal, $nominal, $jatuh_tempo, $keterangan]);

        jsonResponse(true, 'Catatan kas bon baru berhasil disimpan');
        break;

    case 'pay_kasbon':
        $id = intval($_POST['id'] ?? 0);
        $nominal_bayar = floatval($_POST['nominal_bayar'] ?? 0);

        if ($id <= 0 || $nominal_bayar <= 0) {
            jsonResponse(false, 'Nominal pembayaran kas bon harus lebih dari 0');
        }

        $stmtKb = $pdo->prepare("SELECT * FROM kasbon_pelanggan WHERE id = ?");
        $stmtKb->execute([$id]);
        $kb = $stmtKb->fetch();

        if (!$kb) {
            jsonResponse(false, 'Data kas bon tidak ditemukan');
        }

        $new_sudah_dibayar = $kb['sudah_dibayar'] + $nominal_bayar;
        $new_sisa = max(0, $kb['total_hutang'] - $new_sudah_dibayar);
        $new_status = ($new_sisa <= 0) ? 'lunas' : 'belum_lunas';
        $tgl_lunas = ($new_status === 'lunas') ? date('Y-m-d') : null;

        $pdo->beginTransaction();
        try {
            $stmtUp = $pdo->prepare("UPDATE kasbon_pelanggan SET sudah_dibayar = ?, sisa_hutang = ?, status = ?, tanggal_lunas = ? WHERE id = ?");
            $stmtUp->execute([$new_sudah_dibayar, $new_sisa, $new_status, $tgl_lunas, $id]);

            // Catat pemasukan ke kas buku warung
            $stmtKas = $pdo->prepare("INSERT INTO kas_buku (tipe, kategori, nominal, keterangan, tanggal, petugas) VALUES ('masuk', 'Bayar Kasbon', ?, ?, CURRENT_DATE, ?)");
            $stmtKas->execute([$nominal_bayar, "Pelunasan/Cicilan Kasbon dari {$kb['nama_pelanggan']}", $currentUser['nama_lengkap'] ?? 'Pemilik Warung']);

            $pdo->commit();
            jsonResponse(true, "Pembayaran kas bon Rp " . number_format($nominal_bayar, 0, ',', '.') . " berhasil dicatat. Status: {$new_status}");
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal update kasbon: ' . $e->getMessage());
        }
        break;

    // ==========================================
    // 6. BUKU KAS & LABA RUGI WARUNG
    // ==========================================
    case 'get_laba_rugi':
        $bulan = $_GET['bulan'] ?? date('Y-m');

        // Total Penjualan & Modal
        $stmtTrx = $pdo->prepare("SELECT 
            COALESCE(SUM(total_amount), 0) as omset_penjualan,
            COALESCE(SUM(total_modal), 0) as modal_pokok_penjualan,
            COALESCE(SUM(laba_kotor), 0) as laba_kotor_penjualan
            FROM transactions 
            WHERE strftime('%Y-%m', created_at) = ? AND payment_status != 'kasbon'");
        $stmtTrx->execute([$bulan]);
        $trxData = $stmtTrx->fetch();

        // Biaya Operasional (Kas Keluar selain Kulakan)
        $stmtOps = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kas_buku WHERE tipe = 'keluar' AND kategori != 'Modal Kulakan' AND strftime('%Y-%m', tanggal) = ?");
        $stmtOps->execute([$bulan]);
        $biayaOps = $stmtOps->fetchColumn();

        // Total Kulakan Bulan Ini
        $stmtKul = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM kas_buku WHERE tipe = 'keluar' AND kategori = 'Modal Kulakan' AND strftime('%Y-%m', tanggal) = ?");
        $stmtKul->execute([$bulan]);
        $totalKulakan = $stmtKul->fetchColumn();

        $labaKotor = floatval($trxData['laba_kotor_penjualan']);
        $labaBersih = $labaKotor - floatval($biayaOps);

        jsonResponse(true, "Laporan Laba Rugi {$bulan}", [
            'bulan' => $bulan,
            'omset_penjualan' => floatval($trxData['omset_penjualan']),
            'modal_pokok' => floatval($trxData['modal_pokok_penjualan']),
            'laba_kotor' => $labaKotor,
            'biaya_operasional' => floatval($biayaOps),
            'total_kulakan' => floatval($totalKulakan),
            'laba_bersih' => $labaBersih
        ]);
        break;

    case 'get_kas_buku':
        $bulan = $_GET['bulan'] ?? '';
        $query = "SELECT * FROM kas_buku";
        if ($bulan) {
            $query .= " WHERE strftime('%Y-%m', tanggal) = " . $pdo->quote($bulan);
        }
        $query .= " ORDER BY tanggal DESC, id DESC";
        $stmt = $pdo->query($query);
        $records = $stmt->fetchAll();

        $totalMasuk = 0;
        $totalKeluar = 0;
        foreach ($records as $r) {
            if ($r['tipe'] === 'masuk') $totalMasuk += $r['nominal'];
            else $totalKeluar += $r['nominal'];
        }

        jsonResponse(true, 'Buku Kas Warung', [
            'records' => $records,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo' => ($totalMasuk - $totalKeluar)
        ]);
        break;

    case 'add_kas_buku':
        $tipe = $_POST['tipe'] ?? 'keluar';
        $kategori = $_POST['kategori'] ?? 'Operasional Warung';
        $nominal = floatval($_POST['nominal'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

        if ($nominal <= 0 || !$keterangan) {
            jsonResponse(false, 'Nominal dan keterangan biaya wajib diisi');
        }

        $stmt = $pdo->prepare("INSERT INTO kas_buku (tipe, kategori, nominal, keterangan, tanggal, petugas) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tipe, $kategori, $nominal, $keterangan, $tanggal, $currentUser['nama_lengkap'] ?? 'Pemilik Warung']);

        jsonResponse(true, 'Catatan buku kas warung berhasil disimpan');
        break;

    // ==========================================
    // 7. VERIFIKASI QRIS BI 0% & RIWAYAT NOTA
    // ==========================================
    case 'verify_payment':
        $invoice_no = trim($_POST['invoice_no'] ?? '');
        if (!$invoice_no) {
            jsonResponse(false, 'Nomor invoice diperlukan');
        }

        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        $trx = $stmt->fetch();

        if (!$trx) {
            jsonResponse(false, 'Transaksi tidak ditemukan');
        }

        if ($trx['payment_status'] === 'paid') {
            jsonResponse(true, 'Transaksi sudah lunas', $trx);
        }

        $pdo->beginTransaction();
        try {
            $paid_time = date('Y-m-d H:i:s');
            $stmtUp = $pdo->prepare("UPDATE transactions SET payment_status = 'paid', paid_at = ? WHERE id = ?");
            $stmtUp->execute([$paid_time, $trx['id']]);

            // Masuk ke Kas Buku Warung
            $stmtKas = $pdo->prepare("INSERT INTO kas_buku (transaction_id, tipe, kategori, nominal, keterangan, tanggal, petugas) VALUES (?, 'masuk', 'Penjualan Warung (QRIS 0%)', ?, ?, CURRENT_DATE, 'Auto Settlement BI')");
            $stmtKas->execute([$trx['id'], $trx['total_amount'], "Pembayaran QRIS Nota #{$trx['invoice_no']} ({$trx['customer_name']})"]);

            $pdo->commit();
            $trx['payment_status'] = 'paid';
            $trx['paid_at'] = $paid_time;

            jsonResponse(true, 'Pembayaran QRIS berhasil dikonfirmasi lunas (0% Biaya Admin)', $trx);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Gagal verifikasi pembayaran: ' . $e->getMessage());
        }
        break;

    case 'get_transactions':
        $limit = intval($_GET['limit'] ?? 50);
        $stmt = $pdo->query("SELECT * FROM transactions ORDER BY id DESC LIMIT {$limit}");
        $transactions = $stmt->fetchAll();

        foreach ($transactions as &$t) {
            $stmtItems = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
            $stmtItems->execute([$t['id']]);
            $t['items'] = $stmtItems->fetchAll();
        }

        jsonResponse(true, 'Riwayat transaksi penjualan warung', $transactions);
        break;

    case 'get_transaction_detail':
        $invoice_no = $_GET['invoice_no'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        $trx = $stmt->fetch();
        if ($trx) {
            $stmtItems = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
            $stmtItems->execute([$trx['id']]);
            $trx['items'] = $stmtItems->fetchAll();
            jsonResponse(true, 'Detail nota transaksi', $trx);
        }
        jsonResponse(false, 'Transaksi tidak ditemukan');
        break;

    // ==========================================
    // 8. EKSPOR LAPORAN KEUANGAN KE CSV
    // ==========================================
    case 'export_laba_rugi_csv':
        $stmt = $pdo->query("SELECT invoice_no, customer_name, total_amount, total_modal, laba_kotor, payment_method, payment_status, created_at FROM transactions ORDER BY id DESC");
        $records = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Laporan_Keuangan_Warung_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No. Nota', 'Pelanggan', 'Total Belanja (Rp)', 'Total Modal (Rp)', 'Laba Kotor (Rp)', 'Metode Bayar', 'Status', 'Waktu Transaksi']);

        foreach ($records as $r) {
            fputcsv($output, [
                $r['invoice_no'],
                $r['customer_name'],
                $r['total_amount'],
                $r['total_modal'],
                $r['laba_kotor'],
                strtoupper($r['payment_method']),
                strtoupper($r['payment_status']),
                $r['created_at']
            ]);
        }
        fclose($output);
        exit;
        break;

    // ==========================================
    // 9. INTEGRASI LAYANAN SURAT RT 002
    // ==========================================
    case 'get_surat_list':
        $stmt = $pdo->query("SELECT s.*, u.nama_lengkap, u.no_hp, u.alamat FROM surat_pengajuan s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC");
        $suratList = $stmt->fetchAll();
        jsonResponse(true, 'Daftar pengajuan surat', $suratList);
        break;

    case 'ajukan_surat':
        $jenis_surat = trim($_POST['jenis_surat'] ?? 'Surat Keterangan Usaha (SKU)');
        $keperluan = trim($_POST['keperluan'] ?? '');
        $nama_pemohon = trim($_POST['nama_pemohon'] ?? $currentUser['nama_lengkap']);
        $nik = trim($_POST['nik'] ?? $currentUser['nik']);

        if (!$keperluan) {
            jsonResponse(false, 'Keperluan pengajuan surat wajib diisi');
        }

        $no_pengajuan = 'SRT-' . date('Y') . '-' . sprintf('%03d', rand(100, 999));
        $stmt = $pdo->prepare("INSERT INTO surat_pengajuan (user_id, no_pengajuan, nama_pemohon, nik, jenis_surat, keperluan, status, catatan_rt, waktu_pengajuan) VALUES (?, ?, ?, ?, ?, ?, 'menunggu', 'Pengajuan diterima via Agen Warung RT 002', CURRENT_TIMESTAMP)");
        $stmt->execute([$currentUser['id'], $no_pengajuan, $nama_pemohon, $nik, $jenis_surat, $keperluan]);

        jsonResponse(true, 'Pengajuan surat berhasil dikirim ke Admin RT 002 (Proses 1 Menit)', ['no_pengajuan' => $no_pengajuan]);
        break;

    case 'process_surat':
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'disetujui';
        $catatan = trim($_POST['catatan_rt'] ?? 'Disetujui kilat oleh Ketua RT 002 RW 004.');
        $no_resmi = sprintf("471.1/%03d/RT002-RW04/%s/%s", rand(10, 99), date('m'), date('Y'));

        $stmt = $pdo->prepare("UPDATE surat_pengajuan SET status = ?, catatan_rt = ?, waktu_proses = CURRENT_TIMESTAMP, no_surat_resmi = ? WHERE id = ?");
        $stmt->execute([$status, $catatan, $no_resmi, $id]);

        jsonResponse(true, "Surat berhasil diproses ({$status}) dalam 1 menit", ['no_surat_resmi' => $no_resmi]);
        break;

    default:
        jsonResponse(false, 'Aksi API Warung tidak valid');
        break;
}

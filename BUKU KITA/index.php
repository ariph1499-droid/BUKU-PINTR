<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buku Pintar Warung &amp; Kasir Digital - RT 002 RW 004 Kedaung Kali Angke</title>
  
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <!-- Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Header -->
  <header class="app-header">
    <div class="header-container">
      <div class="brand-wrapper">
        <div class="brand-icon">🏪</div>
        <div>
          <div class="brand-title">BUKU PINTAR WARUNG &amp; KASIR DIGITAL</div>
          <div class="brand-subtitle">WARUNG BERKAH • RT 002 / RW 004 KEDAUNG KALI ANGKE, CENGKARENG</div>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div class="bi-badge">
          <span>🇮🇩 QRIS BI Standard</span>
          <span class="zero-fee-pill">0% Biaya Admin</span>
        </div>

        <div style="display: flex; align-items: center; gap: 0.6rem; background: rgba(255,255,255,0.1); padding: 0.35rem 0.8rem; border-radius: var(--radius-md); cursor: pointer;" onclick="openModal('modal-switch-user')">
          <div style="width:30px; height:30px; border-radius:50%; background:#34d399; color:#064e3b; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.85rem;" id="current-user-avatar">W</div>
          <div>
            <div style="font-size:0.82rem; font-weight:700;" id="current-user-name">Ibu Siti Aminah</div>
            <div style="font-size:0.7rem; color:#a7f3d0;" id="current-user-role">Pemilik Warung</div>
          </div>
          <span style="font-size:0.75rem; color:#a7f3d0; margin-left:4px;">⇄ Ganti</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Navigation Tabs -->
  <nav class="nav-tab-bar">
    <div class="nav-tab-container">
      <button class="nav-tab-btn active" data-tab="pos">
        <span>🛒</span> Kasir Pintar (POS)
      </button>
      <button class="nav-tab-btn" data-tab="dashboard">
        <span>📊</span> Dashboard &amp; Omset
      </button>
      <button class="nav-tab-btn" data-tab="kasbon">
        <span>📒</span> Buku Kas Bon / Hutang <span class="tab-badge" id="badge-kasbon-count">0</span>
      </button>
      <button class="nav-tab-btn" data-tab="labarugi">
        <span>📈</span> Buku Kas &amp; Laba Rugi
      </button>
      <button class="nav-tab-btn" data-tab="stok">
        <span>📦</span> Stok &amp; Kulakan <span class="tab-badge" id="badge-stok-menipis">0</span>
      </button>
      <button class="nav-tab-btn" data-tab="transaksi">
        <span>🧾</span> Riwayat Nota Transaksi
      </button>
      <button class="nav-tab-btn" data-tab="surat">
        <span>🏛️</span> Agen Layanan RT 002
      </button>
    </div>
  </nav>

  <!-- Main Container -->
  <main class="main-wrapper">

    <!-- ==================================================================== -->
    <!-- TAB 1: KASIR PINTAR (POS) WARUNG                                      -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel active" id="tab-pos">
      <div class="content-grid-2">
        
        <!-- Left: Product Catalog -->
        <div>
          <div class="panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <span>🛍️</span> Etalase Barang Warung
              </div>
              <button class="btn btn-sm btn-primary" onclick="openTambahBarangModal()">➕ Tambah Barang</button>
            </div>
            <div class="panel-body">
              <div class="pos-search-bar">
                <input type="text" id="pos-search-input" class="form-input" placeholder="🔍 Cari nama barang warung (Beras, Minyak, Telur, Rokok, Gas, Galon)...">
                <select id="pos-category-filter" class="form-select" style="max-width: 170px;">
                  <option value="">Semua Kategori</option>
                  <option value="sembako">Sembako</option>
                  <option value="minuman">Minuman</option>
                  <option value="snack">Snack &amp; Makanan</option>
                  <option value="rokok">Rokok</option>
                  <option value="gas_galon">Gas &amp; Galon</option>
                  <option value="rumah_tangga">Rumah Tangga</option>
                  <option value="iuran_rt">Iuran RT 002</option>
                </select>
              </div>

              <!-- Product Grid -->
              <div class="catalog-grid" id="pos-catalog-grid">
                <!-- Dynamically populated via JS -->
              </div>
            </div>
          </div>
        </div>

        <!-- Right: POS Kasir Checkout & Kembalian Calculator -->
        <div id="mobile-cart-drawer" class="mobile-cart-drawer-wrapper">
          <div class="cart-panel">
            <div class="panel-header">
              <div class="panel-title">
                <span>🧾</span> Kasir Pembayaran
              </div>
              <div style="display: flex; gap: 6px;">
                <button class="btn btn-sm btn-outline" onclick="clearCart()">Kosongkan</button>
                <button class="btn-qty" style="display: none;" id="btn-close-mobile-cart" onclick="toggleMobileCart(false)">✕</button>
              </div>
            </div>

            <!-- Cart Items -->
            <div class="cart-items-list" id="pos-cart-items">
              <!-- Cart items -->
            </div>

            <!-- Customer Details -->
            <div style="padding: 0 1rem; margin-top: 0.5rem;">
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                <div>
                  <label style="font-size: 0.72rem; font-weight: 700; color: #64748b;">Nama Pembeli / Warga</label>
                  <input type="text" id="pos-cust-name" class="form-input" placeholder="Pelanggan Umum" style="padding: 0.4rem 0.6rem; font-size: 0.82rem;">
                </div>
                <div>
                  <label style="font-size: 0.72rem; font-weight: 700; color: #64748b;">No. HP / WhatsApp</label>
                  <input type="text" id="pos-cust-phone" class="form-input" placeholder="0812XXXXXXXX" style="padding: 0.4rem 0.6rem; font-size: 0.82rem;">
                </div>
              </div>
            </div>

            <!-- Payment Method Selector -->
            <div style="padding: 0 1rem;">
              <label style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Metode Pembayaran (0% Potongan):</label>
              <div class="payment-methods-grid">
                <button type="button" class="payment-method-btn selected" data-method="tunai" onclick="selectPaymentMethod('tunai')">
                  <span style="font-size: 1.1rem;">💵</span>
                  <div>Tunai / Cash</div>
                </button>
                <button type="button" class="payment-method-btn" data-method="qris_bi" onclick="selectPaymentMethod('qris_bi')">
                  <span style="font-size: 1.1rem;">📱</span>
                  <div>QRIS BI 0%</div>
                  <span class="badge-0percent">0% MDR</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="bca_va" onclick="selectPaymentMethod('bca_va')">
                  <span style="font-size: 1.1rem;">🏦</span>
                  <div>Transfer Bank</div>
                  <span class="badge-0percent">0% Fee</span>
                </button>
                <button type="button" class="payment-method-btn" data-method="kasbon" onclick="selectPaymentMethod('kasbon')" style="grid-column: 1 / -1; border-color: #f59e0b; background: #fffbeb; color: #b45309;">
                  <span style="font-size: 1.1rem;">📒</span>
                  <div style="font-weight: 800;">Catat Sebagai Hutang / Kas Bon Warga</div>
                </button>
              </div>
            </div>

            <!-- Cash Input Section -->
            <div id="pos-cash-section" style="padding: 0 1rem;">
              <label style="font-size: 0.72rem; font-weight: 700; color: #64748b;">Uang Tunai Diterima (Rp):</label>
              <input type="number" id="pos-cash-input" class="form-input" placeholder="Contoh: 50000" style="font-size: 1.1rem; font-weight: 800; color: #16a34a; padding: 0.5rem 0.75rem;">
              
              <!-- Quick Cash Buttons -->
              <div class="quick-cash-grid">
                <button type="button" class="quick-cash-btn uang-pas" onclick="setQuickCash('pas')">Uang Pas</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(10000)">10.000</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(20000)">20.000</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(50000)">50.000</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(100000)">100.000</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(150000)">150.000</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(200000)">200.000</button>
                <button type="button" class="quick-cash-btn" onclick="setQuickCash(500000)">500.000</button>
              </div>

              <!-- Live Kembalian -->
              <div class="kembalian-display-box" id="pos-kembalian-box" style="display: none;">
                <span style="font-weight: 700; font-size: 0.85rem; color: #166534;">UANG KEMBALIAN:</span>
                <span class="kembalian-val" id="pos-kembalian-val">Rp 0</span>
              </div>
            </div>

            <!-- Kasbon Input Section -->
            <div id="pos-kasbon-section" style="display: none; padding: 0 1rem; margin-top: 0.25rem;">
              <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 0.75rem; border-radius: var(--radius-md);">
                <label style="font-size: 0.75rem; font-weight: 700; color: #b45309;">Tanggal Jatuh Tempo Pembayaran:</label>
                <input type="date" id="pos-kasbon-tempo" class="form-input" style="margin-top: 4px;" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
              </div>
            </div>

            <!-- Summary & Submit -->
            <div style="background: #f8fafc; padding: 1.15rem; border-top: 1px solid var(--border); margin-top: 0.75rem;">
              <div style="display: flex; justify-content: space-between; font-size: 0.88rem; color: #64748b; margin-bottom: 0.4rem;">
                <span>Total Belanja:</span>
                <span id="pos-cart-subtotal" style="font-weight: 700; color: #0f172a;">Rp 0</span>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #16a34a; margin-bottom: 0.5rem;">
                <span>Biaya Admin (MDR):</span>
                <span style="font-weight: 800;">Rp 0 (0% Gratis)</span>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 900; border-top: 1px dashed var(--border); padding-top: 0.6rem; color: #15803d;">
                <span>TOTAL AKHIR:</span>
                <span id="pos-cart-grandtotal">Rp 0</span>
              </div>
              <button class="btn btn-primary btn-block" style="margin-top: 0.85rem; padding: 0.85rem; font-size: 1rem;" onclick="processPOSCheckout()">
                ⚡ Bayar &amp; Cetak Struk (0% Admin)
              </button>
            </div>

          </div>
        </div>

      </div>

      <!-- Mobile Floating Cart Bar (Appears on phones when cart has items) -->
      <div class="mobile-floating-cart" id="mobile-floating-cart" onclick="toggleMobileCart(true)">
        <div class="cart-info">
          <div class="cart-qty-badge" id="mobile-cart-badge">0</div>
          <div>
            <div style="font-size: 0.72rem; opacity: 0.9;">Total Belanjaan</div>
            <div class="cart-amount" id="mobile-cart-amount">Rp 0</div>
          </div>
        </div>
        <div class="cart-action-btn">
          <span>🧾 Kasir &amp; Bayar</span>
          <span>➔</span>
        </div>
      </div>

    </div>

    <!-- ==================================================================== -->
    <!-- TAB 2: DASHBOARD & OMSET WARUNG                                      -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel" id="tab-dashboard">
      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <div class="stat-info-title">Omset Penjualan Hari Ini</div>
            <div class="stat-info-val" id="stat-omset-today" style="color: #16a34a;">Rp 0</div>
            <div class="stat-info-sub">✓ Kasir POS Terverifikasi</div>
          </div>
          <div class="stat-icon-box icon-green">💵</div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-info-title">Perkiraan Laba Hari Ini</div>
            <div class="stat-info-val" id="stat-laba-today" style="color: #0284c7;">Rp 0</div>
            <div class="stat-info-sub">Laba Kotor Bersih</div>
          </div>
          <div class="stat-icon-box icon-blue">📈</div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-info-title">Total Piutang Kas Bon</div>
            <div class="stat-info-val" id="stat-kasbon-total" style="color: #d97706;">Rp 0</div>
            <div class="stat-info-sub">Hutang Belum Lunas</div>
          </div>
          <div class="stat-icon-box icon-amber">📒</div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-info-title">Saldo Kas Warung</div>
            <div class="stat-info-val" id="stat-saldo-kas">Rp 0</div>
            <div class="stat-info-sub">Total Kas Masuk - Keluar</div>
          </div>
          <div class="stat-icon-box icon-green">💰</div>
        </div>
      </div>

      <div class="content-grid-2">
        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">
              <span>🏪</span> Profil Warung &amp; Layanan Agen RT
            </div>
            <span class="badge badge-paid">Aktif Melayani</span>
          </div>
          <div class="panel-body" style="font-size: 0.9rem; line-height: 1.7;">
            <p><strong>Nama Usaha:</strong> Warung Berkah Kelontong RT 002</p>
            <p><strong>Pengelola:</strong> Ibu Siti Aminah</p>
            <p><strong>Alamat:</strong> Jl. Utama RT 002 No. 08, RW 004 Kel. Kedaung Kali Angke, Cengkareng</p>
            <p><strong>Metode Pembayaran:</strong> Tunai, QRIS Bank Indonesia 0% MDR, Virtual Account Multi-Bank, dan Kasbon Warga.</p>
            <div style="margin-top: 1rem; padding: 0.85rem; background: #dcfce7; border-radius: 8px; font-size: 0.82rem; color: #166534;">
              💡 <b>Keunggulan Kasir Warung:</b> Seluruh transaksi belanja, iuran kebersihan/keamanan RT, dan catatan kas bon warga langsung dihitung laba kotor &amp; arus kasnya secara otomatis.
            </div>
          </div>
        </div>

        <div class="panel-card">
          <div class="panel-header">
            <div class="panel-title">
              <span>⚡</span> Aksi Cepat Warung
            </div>
          </div>
          <div class="panel-body" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <button class="btn btn-primary" onclick="switchTab('pos')">🛒 Buka Kasir Penjualan</button>
            <button class="btn btn-kasbon" onclick="openModal('modal-tambah-kasbon')">📒 Catat Kas Bon / Hutang Baru</button>
            <button class="btn btn-outline" onclick="openModal('modal-tambah-biaya')">💸 Catat Pengeluaran Operasional</button>
            <button class="btn btn-outline" onclick="openModal('modal-tambah-barang')">➕ Tambah Barang Etalase Baru</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- TAB 3: BUKU KAS BON / HUTANG PELANGGAN                                -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel" id="tab-kasbon">
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <span>📒</span> Buku Kas Bon &amp; Piutang Pelanggan Warung
          </div>
          <button class="btn btn-kasbon btn-sm" onclick="openModal('modal-tambah-kasbon')">+ Catat Kas Bon Baru</button>
        </div>
        <div class="panel-body">
          <div style="margin-bottom: 1rem; background: #fffbeb; border: 1px solid #fde68a; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.82rem; color: #92400e;">
            📢 <b>Fitur Tagih WhatsApp:</b> Klik tombol <b>"Tagih WA"</b> untuk mengirimkan rincian hutang dan kode QRIS secara otomatis ke WhatsApp pelanggan.
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama Pelanggan</th>
                  <th>Total Hutang</th>
                  <th>Sudah Dibayar</th>
                  <th>Sisa Hutang</th>
                  <th>Status</th>
                  <th>Jatuh Tempo</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="kasbon-table-body">
                <!-- Dynamically populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- TAB 4: BUKU KAS & LABA RUGI WARUNG                                    -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel" id="tab-labarugi">
      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <div class="stat-info-title">Omset Penjualan (Bulan Ini)</div>
            <div class="stat-info-val" id="lr-omset" style="color: #16a34a;">Rp 0</div>
          </div>
          <div class="stat-icon-box icon-green">🛍️</div>
        </div>
        <div class="stat-card">
          <div>
            <div class="stat-info-title">Modal Pokok Kulakan</div>
            <div class="stat-info-val" id="lr-modal" style="color: #dc2626;">Rp 0</div>
          </div>
          <div class="stat-icon-box icon-red">📦</div>
        </div>
        <div class="stat-card">
          <div>
            <div class="stat-info-title">Laba Kotor Penjualan</div>
            <div class="stat-info-val" id="lr-laba-kotor" style="color: #0284c7;">Rp 0</div>
          </div>
          <div class="stat-icon-box icon-blue">📈</div>
        </div>
        <div class="stat-card">
          <div>
            <div class="stat-info-title">Biaya Operasional (Listrik/Air)</div>
            <div class="stat-info-val" id="lr-biaya-ops" style="color: #d97706;">Rp 0</div>
          </div>
          <div class="stat-icon-box icon-amber">⚡</div>
        </div>
        <div class="stat-card" style="border: 2px solid #16a34a; background: #f0fdf4;">
          <div>
            <div class="stat-info-title" style="color: #15803d;">LABA BERSIH WARUNG</div>
            <div class="stat-info-val" id="lr-laba-bersih" style="color: #15803d; font-size: 1.6rem;">Rp 0</div>
          </div>
          <div class="stat-icon-box icon-green">🏆</div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <span>📖</span> Pembukuan Arus Kas Warung
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <a href="api.php?action=export_laba_rugi_csv" class="btn btn-outline btn-sm" target="_blank" style="text-decoration:none;">
              📥 Ekspor Excel (CSV)
            </a>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah-biaya')">+ Biaya Operasional</button>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Arus</th>
                  <th>Kategori</th>
                  <th>Keterangan</th>
                  <th>Nominal</th>
                </tr>
              </thead>
              <tbody id="kas-buku-table-body">
                <!-- Dynamically populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- TAB 5: STOK & KULAKAN BARANG                                         -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel" id="tab-stok">
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <span>📦</span> Manajemen Stok &amp; Kulakan Barang Warung
          </div>
          <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah-barang')">+ Tambah Barang Baru</button>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Barang</th>
                  <th>Kategori</th>
                  <th>Harga Modal</th>
                  <th>Harga Jual</th>
                  <th>Stok Saat Ini</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="stok-table-body">
                <!-- Dynamically populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- TAB 6: RIWAYAT NOTA TRANSAKSI                                        -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel" id="tab-transaksi">
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <span>🧾</span> Riwayat Nota Penjualan Kasir Warung
          </div>
          <span class="zero-fee-pill">0% MDR BI QRIS</span>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No. Nota</th>
                  <th>Pelanggan</th>
                  <th>Metode</th>
                  <th>Total Belanja</th>
                  <th>Laba Kotor</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="trx-table-body">
                <!-- Dynamically populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- TAB 7: AGEN LAYANAN RT 002                                           -->
    <!-- ==================================================================== -->
    <div class="tab-content-panel" id="tab-surat">
      <div class="panel-card">
        <div class="panel-header">
          <div class="panel-title">
            <span>🏛️</span> Layanan Pengantar Warga RT 002 (Via Warung)
          </div>
          <span class="badge badge-paid">Proses Kilat 1 Menit</span>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No. Pengajuan</th>
                  <th>Nama Pemohon</th>
                  <th>Jenis Dokumen</th>
                  <th>Keperluan</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="surat-table-body">
                <!-- Dynamically populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- ====================================================================== -->
  <!-- MODAL: GATEWAY QRIS BI 0%                                              -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-payment-gateway">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
          <span>🇮🇩</span> Pembayaran QRIS BI Warung (0% MDR)
        </div>
        <button class="btn-qty" onclick="closeModal('modal-payment-gateway')">✕</button>
      </div>
      <div class="modal-body">
        <div id="pg-qris-container" style="text-align: center; border: 2px solid #e2e8f0; border-radius: var(--radius-lg); padding: 1.5rem;">
          <div style="font-size: 1.5rem; font-weight: 900; color: #dc2626; margin-bottom: 0.5rem;">QRIS</div>
          <div style="font-size: 0.85rem; font-weight: 700;">WARUNG BERKAH RT 002</div>
          <div style="font-size: 0.68rem; color: #64748b; font-family: monospace;">NMID: ID1020023489234</div>
          
          <div id="dynamic-qris-canvas" style="display: inline-block; padding: 0.75rem; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin: 0.75rem 0;">
            <!-- Canvas -->
          </div>

          <div style="font-size: 0.8rem; color: #64748b;">Scan dengan <b>BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana, ShopeePay</b></div>
          <div style="margin-top: 0.75rem;"><span class="zero-fee-pill">0% Biaya Admin</span></div>
        </div>

        <div id="pg-va-container" style="display: none; text-align: center; padding: 1rem;">
          <div style="font-size: 1.1rem; font-weight: 800;" id="pg-va-bank-name">Virtual Account</div>
          <div style="font-family: monospace; font-size: 1.4rem; font-weight: 800; color: #0284c7; margin: 0.75rem 0;" id="pg-va-number">880000000000</div>
        </div>

        <div style="margin-top: 1rem; background: #f8fafc; padding: 0.85rem; border-radius: var(--radius-md); font-size: 0.85rem;">
          <div style="display:flex; justify-content:space-between;"><span>No. Nota:</span><b id="pg-invoice-no">-</b></div>
          <div style="display:flex; justify-content:space-between; margin-top:3px;"><span>Pelanggan:</span><span id="pg-customer-name">-</span></div>
          <div style="display:flex; justify-content:space-between; font-weight:800; font-size:1.1rem; border-top:1px dashed #cbd5e1; margin-top:6px; padding-top:6px; color:#16a34a;">
            <span>Total Tagihan:</span>
            <span id="pg-total-amount">Rp 0</span>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="justify-content:space-between;">
        <button class="btn btn-outline" onclick="closeModal('modal-payment-gateway')">Tutup</button>
        <button class="btn btn-primary" onclick="simulateInstantPayment()">⚡ Simulasikan Pembayaran Sukses</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: CETAK STRUK THERMAL POS                                         -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-receipt">
    <div class="modal-card" style="max-width: 380px;">
      <div class="modal-header">
        <div class="modal-title">🧾 Struk Belanja Warung</div>
        <button class="btn-qty" onclick="closeModal('modal-receipt')">✕</button>
      </div>
      <div class="modal-body" id="printable-receipt">
        <!-- Thermal Receipt -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('modal-receipt')">Tutup</button>
        <button class="btn btn-primary" onclick="printDocument()">🖨️ Cetak Struk</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: CATAT KAS BON BARU                                              -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-tambah-kasbon">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">📒 Catat Kas Bon / Hutang Pelanggan</div>
        <button class="btn-qty" onclick="closeModal('modal-tambah-kasbon')">✕</button>
      </div>
      <div class="modal-body">
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Nama Pelanggan / Warga</label>
          <input type="text" id="kb-form-nama" class="form-input" placeholder="Contoh: Pak Joko Gang 1">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">No. WhatsApp</label>
            <input type="text" id="kb-form-nohp" class="form-input" placeholder="0812XXXXXXXX">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Nominal Hutang (Rp)</label>
            <input type="number" id="kb-form-nominal" class="form-input" placeholder="85000">
          </div>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Tanggal Jatuh Tempo</label>
          <input type="date" id="kb-form-tempo" class="form-input" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Rincian Barang / Keterangan</label>
          <input type="text" id="kb-form-ket" class="form-input" placeholder="Contoh: Beras 5kg + Telur 1kg">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('modal-tambah-kasbon')">Batal</button>
        <button class="btn btn-kasbon" onclick="submitKasbonManual()">💾 Simpan Kas Bon</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: BAYAR / CICIL KAS BON                                           -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-bayar-kasbon">
    <div class="modal-card" style="max-width: 420px;">
      <div class="modal-header">
        <div class="modal-title">💵 Pembayaran Kas Bon</div>
        <button class="btn-qty" onclick="closeModal('modal-bayar-kasbon')">✕</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="bayar-kb-id">
        <div style="margin-bottom: 0.75rem;">
          <div style="font-size: 0.78rem; color: #64748b;">Nama Pelanggan:</div>
          <div style="font-size: 1.1rem; font-weight: 800;" id="bayar-kb-nama">-</div>
        </div>
        <div style="margin-bottom: 1rem;">
          <div style="font-size: 0.78rem; color: #64748b;">Sisa Tagihan Saat Ini:</div>
          <div style="font-size: 1.25rem; font-weight: 900; color: #dc2626;" id="bayar-kb-sisa">Rp 0</div>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Nominal Yang Dibayar Sekarang (Rp):</label>
          <input type="number" id="bayar-kb-nominal" class="form-input" style="font-size: 1.1rem; font-weight: 800; color: #16a34a; margin-top: 4px;">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('modal-bayar-kasbon')">Batal</button>
        <button class="btn btn-primary" onclick="submitBayarKasbon()">✓ Simpan Pembayaran</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: RESTOCK / KULAKAN BARANG                                        -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-restock-barang">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <div class="modal-title">📦 Kulakan / Tambah Stok Barang</div>
        <button class="btn-qty" onclick="closeModal('modal-restock-barang')">✕</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="restock-id">
        <div style="margin-bottom: 0.75rem;">
          <div style="font-size: 0.78rem; color: #64748b;">Nama Barang:</div>
          <div style="font-size: 1.1rem; font-weight: 800;" id="restock-nama">-</div>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Jumlah Kulakan (Qty Tambah):</label>
          <input type="number" id="restock-qty" class="form-input" value="10" style="margin-top: 4px;">
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Harga Beli / Modal Satuan (Rp):</label>
          <input type="number" id="restock-hb" class="form-input" style="margin-top: 4px;">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('modal-restock-barang')">Batal</button>
        <button class="btn btn-primary" onclick="submitRestock()">✓ Tambah Stok &amp; Catat Kas</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: CATAT BIAYA OPERASIONAL                                         -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-tambah-biaya">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title">💸 Catat Pengeluaran Operasional Warung</div>
        <button class="btn-qty" onclick="closeModal('modal-tambah-biaya')">✕</button>
      </div>
      <div class="modal-body">
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Kategori Pengeluaran</label>
          <select id="ops-kategori" class="form-select" style="margin-top: 4px;">
            <option value="Operasional Listrik/Air">Token Listrik Kulkas / Air</option>
            <option value="Plastik & Kantong">Plastik &amp; Kantong Kresek</option>
            <option value="Transport Kulakan">Ongkos Bensin / Transport Kulakan</option>
            <option value="Iuran Lingkungan RT">Iuran Keamanan &amp; Sampah RT 002</option>
            <option value="Lainnya">Lainnya</option>
          </select>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Nominal Pengeluaran (Rp)</label>
          <input type="number" id="ops-nominal" class="form-input" placeholder="50000" style="margin-top: 4px;">
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Keterangan Rinci</label>
          <input type="text" id="ops-keterangan" class="form-input" placeholder="Contoh: Beli token listrik kulkas minuman" style="margin-top: 4px;">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('modal-tambah-biaya')">Batal</button>
        <button class="btn btn-primary" onclick="submitBiayaOperasional()">Simpan Biaya</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: TAMBAH / EDIT BARANG KE ETALASE                                 -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-tambah-barang">
    <div class="modal-card">
      <div class="modal-header">
        <div class="modal-title" id="prod-modal-title">➕ Tambah Barang Baru ke Etalase</div>
        <button class="btn-qty" onclick="closeModal('modal-tambah-barang')">✕</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="prod-id">
        <div style="margin-bottom: 1rem;">
          <label style="font-size: 0.8rem; font-weight: 700;">Nama Barang Warung</label>
          <input type="text" id="prod-nama" class="form-input" placeholder="Contoh: Kopi Good Day Cappuccino">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Kategori</label>
            <select id="prod-kategori" class="form-select">
              <option value="sembako">Sembako</option>
              <option value="minuman">Minuman</option>
              <option value="snack">Snack &amp; Makanan</option>
              <option value="rokok">Rokok</option>
              <option value="gas_galon">Gas &amp; Galon</option>
              <option value="rumah_tangga">Rumah Tangga</option>
              <option value="iuran_rt">Iuran RT 002</option>
            </select>
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Satuan</label>
            <input type="text" id="prod-satuan" class="form-input" placeholder="pcs / botol / bungkus" value="pcs">
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Harga Modal (Beli)</label>
            <input type="number" id="prod-hb" class="form-input" placeholder="2500">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Harga Jual</label>
            <input type="number" id="prod-hj" class="form-input" placeholder="3000">
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Stok Barang</label>
            <input type="number" id="prod-stok" class="form-input" value="20">
          </div>
          <div>
            <label style="font-size: 0.8rem; font-weight: 700;">Batas Stok Minimum</label>
            <input type="number" id="prod-stok-min" class="form-input" value="5">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('modal-tambah-barang')">Batal</button>
        <button class="btn btn-primary" onclick="submitTambahBarang()">💾 Simpan ke Etalase</button>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MODAL: SWITCH USER ROLE                                                -->
  <!-- ====================================================================== -->
  <div class="modal-overlay" id="modal-switch-user">
    <div class="modal-card" style="max-width: 420px;">
      <div class="modal-header">
        <div class="modal-title">🔄 Pilih Akun Pengguna</div>
        <button class="btn-qty" onclick="closeModal('modal-switch-user')">✕</button>
      </div>
      <div class="modal-body" style="display: flex; flex-direction: column; gap: 0.75rem;">
        <div style="padding: 1rem; border: 1.5px solid #16a34a; background: #dcfce7; border-radius: 10px; cursor: pointer;" onclick="switchUser('pemilik_warung')">
          <div style="font-weight: 800; color: #15803d;">🏪 Ibu Siti Aminah (Pemilik Warung)</div>
          <div style="font-size: 0.78rem; color: #166534;">Akses penuh: Kasir POS, input/hapus etalase, hitung laba bersih, kasbon hutang, restock.</div>
        </div>
        <div style="padding: 1rem; border: 1.5px solid #e2e8f0; background: #ffffff; border-radius: 10px; cursor: pointer;" onclick="switchUser('warga')">
          <div style="font-weight: 800; color: #0f172a;">👤 Budi Santoso (Warga / Pembeli)</div>
          <div style="font-size: 0.78rem; color: #64748b;">Akses pembeli: Cek tagihan kasbon, bayar iuran RT via QRIS 0%.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- MOBILE BOTTOM NAVIGATION BAR                                           -->
  <!-- ====================================================================== -->
  <nav class="mobile-bottom-nav">
    <button class="mobile-nav-item active" data-tab="pos">
      <span class="icon">🛒</span>
      <span>Kasir POS</span>
    </button>
    <button class="mobile-nav-item" data-tab="stok">
      <span class="icon">📦</span>
      <span>Etalase</span>
      <span class="mobile-nav-badge" id="m-badge-stok-menipis" style="display:none;">0</span>
    </button>
    <button class="mobile-nav-item" data-tab="kasbon">
      <span class="icon">📒</span>
      <span>Kas Bon</span>
      <span class="mobile-nav-badge" id="m-badge-kasbon-count" style="display:none;">0</span>
    </button>
    <button class="mobile-nav-item" data-tab="labarugi">
      <span class="icon">📈</span>
      <span>Laba Rugi</span>
    </button>
    <button class="mobile-nav-item" data-tab="surat">
      <span class="icon">🏛️</span>
      <span>RT 002</span>
    </button>
  </nav>

  <!-- QR Code Library -->
  <script src="assets/js/qrcode.min.js"></script>
  <!-- Application JS -->
  <script src="assets/js/app.js"></script>
</body>
</html>

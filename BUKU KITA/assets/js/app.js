/**
 * BUKU PINTAR WARUNG & KASIR DIGITAL (BI QRIS & MULTI-BANK 0% ADMIN)
 * TOKO & WARUNG KELONTONG RT 002 / RW 004 KEDAUNG KALI ANGKE
 */

// App State
const state = {
  currentUser: null,
  products: [],
  cart: [],
  kasbonList: [],
  transactions: [],
  currentTab: 'pos',
  selectedPaymentMethod: 'tunai',
  cashAmount: 0,
  activeTransaction: null,
  qrisTimerInterval: null,
  qrisSecondsLeft: 60
};

document.addEventListener('DOMContentLoaded', () => {
  initWarungApp();
});

async function initWarungApp() {
  await loadCurrentUser();
  setupEventListeners();
  loadWarungDashboard();
  loadProducts();
  loadKasbonList();
  loadLabaRugi();
  loadTransactions();
  loadSuratList();
}

// ==============================================================================
// 1. API HELPER & TOAST NOTIFICATION
// ==============================================================================
async function apiCall(action, method = 'GET', body = null) {
  try {
    let url = `api.php?action=${action}`;
    const options = { method: method };

    if (method === 'POST') {
      if (body instanceof FormData) {
        options.body = body;
      } else if (body) {
        const formData = new FormData();
        for (const key in body) {
          if (Array.isArray(body[key]) || typeof body[key] === 'object') {
            formData.append(key, JSON.stringify(body[key]));
          } else {
            formData.append(key, body[key]);
          }
        }
        options.body = formData;
      }
    }

    const res = await fetch(url, options);
    const data = await res.json();
    return data;
  } catch (err) {
    console.error(`API Error on [${action}]:`, err);
    showToast('Terjadi kesalahan koneksi', 'error');
    return { success: false, message: err.message };
  }
}

function showToast(message, type = 'success') {
  let toastContainer = document.getElementById('toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px;';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bg = type === 'success' ? '#16a34a' : (type === 'error' ? '#ef4444' : '#0284c7');
  toast.style.cssText = `background: ${bg}; color: white; padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; transform: translateY(20px); opacity: 0; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);`;
  
  const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
  toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
  toastContainer.appendChild(toast);

  setTimeout(() => {
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
  }, 10);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ==============================================================================
// 2. USER AUTH & ROLE SWITCHER
// ==============================================================================
async function loadCurrentUser() {
  const res = await apiCall('get_current_user');
  if (res.success) {
    state.currentUser = res.data;
    renderUserBar();
  }
}

function renderUserBar() {
  if (!state.currentUser) return;
  const user = state.currentUser;
  
  const userNameEl = document.getElementById('current-user-name');
  const userRoleEl = document.getElementById('current-user-role');
  const avatarEl = document.getElementById('current-user-avatar');

  if (userNameEl) userNameEl.textContent = user.nama_lengkap;
  if (userRoleEl) {
    userRoleEl.textContent = user.role === 'pemilik_warung' ? 'Pemilik Warung' : 'Warga / Pelanggan';
  }
  if (avatarEl) {
    avatarEl.textContent = user.nama_lengkap.charAt(0).toUpperCase();
  }
}

async function switchUser(role) {
  const res = await apiCall('switch_user', 'POST', { role: role });
  if (res.success) {
    showToast(res.message, 'success');
    state.currentUser = res.data;
    renderUserBar();
    closeModal('modal-switch-user');
    loadWarungDashboard();
  }
}

// ==============================================================================
// 3. TABS & EVENT LISTENERS
// ==============================================================================
function setupEventListeners() {
  // Top nav tabs
  document.querySelectorAll('.nav-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      switchTab(btn.dataset.tab);
    });
  });

  // Mobile bottom nav items
  document.querySelectorAll('.mobile-nav-item').forEach(btn => {
    btn.addEventListener('click', () => {
      switchTab(btn.dataset.tab);
    });
  });

  // Search input in POS
  const searchInput = document.getElementById('pos-search-input');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      filterCatalog(e.target.value);
    });
  }

  // Category filter
  const catFilter = document.getElementById('pos-category-filter');
  if (catFilter) {
    catFilter.addEventListener('change', (e) => {
      loadProducts(e.target.value);
    });
  }

  // Cash payment input
  const cashInput = document.getElementById('pos-cash-input');
  if (cashInput) {
    cashInput.addEventListener('input', (e) => {
      state.cashAmount = parseFloat(e.target.value) || 0;
      updateKembalian();
    });
  }
}

function switchTab(tabId) {
  state.currentTab = tabId;
  
  // Update desktop tabs
  document.querySelectorAll('.nav-tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabId);
  });

  // Update mobile bottom nav
  document.querySelectorAll('.mobile-nav-item').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabId);
  });

  // Update panel content
  document.querySelectorAll('.tab-content-panel').forEach(panel => {
    panel.classList.toggle('active', panel.id === `tab-${tabId}`);
  });

  // Hide mobile cart drawer if switching away from POS
  toggleMobileCart(false);

  if (tabId === 'pos') {
    loadProducts();
    updateMobileFloatingCart();
  }
  if (tabId === 'dashboard') loadWarungDashboard();
  if (tabId === 'kasbon') loadKasbonList();
  if (tabId === 'labarugi') loadLabaRugi();
  if (tabId === 'stok') loadRestockList();
  if (tabId === 'transaksi') loadTransactions();
  if (tabId === 'surat') loadSuratList();
}

// ==============================================================================
// 4. WARUNG DASHBOARD & STATS
// ==============================================================================
async function loadWarungDashboard() {
  const res = await apiCall('get_warung_dashboard');
  if (res.success) {
    const d = res.data;
    const omsetEl = document.getElementById('stat-omset-today');
    const labaEl = document.getElementById('stat-laba-today');
    const kasbonEl = document.getElementById('stat-kasbon-total');
    const saldoEl = document.getElementById('stat-saldo-kas');

    if (omsetEl) omsetEl.textContent = formatRupiah(d.omset_hari_ini);
    if (labaEl) labaEl.textContent = formatRupiah(d.laba_hari_ini);
    if (kasbonEl) kasbonEl.textContent = formatRupiah(d.total_piutang_kasbon);
    if (saldoEl) saldoEl.textContent = formatRupiah(d.saldo_kas_warung);

    // Kasbon badge
    const badgeKb = document.getElementById('badge-kasbon-count');
    const mBadgeKb = document.getElementById('m-badge-kasbon-count');
    if (badgeKb) {
      badgeKb.textContent = d.pelanggan_kasbon_count;
      badgeKb.classList.toggle('highlight', d.pelanggan_kasbon_count > 0);
    }
    if (mBadgeKb) {
      mBadgeKb.textContent = d.pelanggan_kasbon_count;
      mBadgeKb.style.display = d.pelanggan_kasbon_count > 0 ? 'inline-block' : 'none';
    }

    // Stok menipis badge
    const badgeStok = document.getElementById('badge-stok-menipis');
    const mBadgeStok = document.getElementById('m-badge-stok-menipis');
    if (badgeStok) {
      badgeStok.textContent = d.stok_menipis_count;
      badgeStok.classList.toggle('highlight', d.stok_menipis_count > 0);
    }
    if (mBadgeStok) {
      mBadgeStok.textContent = d.stok_menipis_count;
      mBadgeStok.style.display = d.stok_menipis_count > 0 ? 'inline-block' : 'none';
    }
  }
}

// ==============================================================================
// 5. KASIR POS WARUNG (ETALASE, INPUT, EDIT, HAPUS, & KASIR CHECKOUT)
// ==============================================================================
async function loadProducts(category = '') {
  const res = await apiCall('get_products' + (category ? `&kategori=${category}` : ''));
  if (res.success) {
    state.products = res.data;
    renderCatalogGrid(state.products);
  }
}

function renderCatalogGrid(products) {
  const grid = document.getElementById('pos-catalog-grid');
  if (!grid) return;

  if (products.length === 0) {
    grid.innerHTML = `
      <div style="grid-column:1/-1; text-align:center; padding:2.5rem 1rem; color:#94a3b8; background:#ffffff; border-radius:12px; border:1px dashed #cbd5e1;">
        <div style="font-size:2rem; margin-bottom:0.5rem;">📦</div>
        <div style="font-weight:700; color:#475569;">Barang tidak ditemukan di etalase</div>
        <div style="font-size:0.8rem; margin-top:4px;">Klik tombol "+ Barang Baru" untuk menambahkan barang jualan.</div>
        <button class="btn btn-sm btn-primary" style="margin-top:1rem;" onclick="openTambahBarangModal()">➕ Tambah Barang Baru</button>
      </div>`;
    return;
  }

  grid.innerHTML = products.map(p => {
    const isLow = p.stok <= p.stok_minimum && p.kategori !== 'iuran_rt';
    return `
      <div class="product-item-card" onclick="addToCart(${p.id})">
        <div class="product-card-top">
          <span class="product-tag tag-${p.kategori}">${p.kategori.replace('_', ' ').toUpperCase()}</span>
          <div class="product-card-actions">
            <button class="btn-card-action btn-card-edit" title="Edit Barang" onclick="openEditProductModal(${p.id}, event)">✏️</button>
            <button class="btn-card-action btn-card-delete" title="Hapus Barang" onclick="deleteProduct(${p.id}, '${escapeHtml(p.nama_produk)}', event)">🗑️</button>
          </div>
        </div>

        <div>
          <div class="product-name">${escapeHtml(p.nama_produk)}</div>
          <div class="product-price">${formatRupiah(p.harga_jual)} <span style="font-size:0.75rem; color:#64748b; font-weight:normal;">/ ${p.satuan}</span></div>
          <div class="product-stock ${isLow ? 'low' : ''}">
            <span>${isLow ? '⚠️ Stok Tinggal:' : 'Stok:'} <b>${p.stok}</b> ${p.satuan}</span>
          </div>
        </div>

        <button class="btn-add-cart" onclick="addToCart(${p.id}); event.stopPropagation();">
          <span>🛒</span> + Keranjang
        </button>
      </div>
    `;
  }).join('');
}

function filterCatalog(keyword) {
  const filtered = state.products.filter(p => 
    p.nama_produk.toLowerCase().includes(keyword.toLowerCase()) ||
    p.kode_produk.toLowerCase().includes(keyword.toLowerCase())
  );
  renderCatalogGrid(filtered);
}

// Open modal for adding a new product
function openTambahBarangModal() {
  document.getElementById('prod-modal-title').textContent = '➕ Tambah Barang Baru ke Etalase';
  document.getElementById('prod-id').value = '';
  document.getElementById('prod-nama').value = '';
  document.getElementById('prod-kategori').value = 'sembako';
  document.getElementById('prod-satuan').value = 'pcs';
  document.getElementById('prod-hb').value = '';
  document.getElementById('prod-hj').value = '';
  document.getElementById('prod-stok').value = '20';
  document.getElementById('prod-stok-min').value = '5';
  openModal('modal-tambah-barang');
}

// Open modal for editing an existing product
function openEditProductModal(productId, event) {
  if (event) event.stopPropagation();
  const prod = state.products.find(p => p.id === productId);
  if (!prod) return;

  document.getElementById('prod-modal-title').textContent = `✏️ Edit Barang: ${prod.nama_produk}`;
  document.getElementById('prod-id').value = prod.id;
  document.getElementById('prod-nama').value = prod.nama_produk;
  document.getElementById('prod-kategori').value = prod.kategori;
  document.getElementById('prod-satuan').value = prod.satuan || 'pcs';
  document.getElementById('prod-hb').value = prod.harga_beli;
  document.getElementById('prod-hj').value = prod.harga_jual;
  document.getElementById('prod-stok').value = prod.stok;
  document.getElementById('prod-stok-min').value = prod.stok_minimum || 5;

  openModal('modal-tambah-barang');
}

// Delete product
async function deleteProduct(productId, productName, event) {
  if (event) event.stopPropagation();
  
  if (!confirm(`Apakah Anda yakin ingin menghapus barang "${productName}" dari etalase warung?`)) {
    return;
  }

  const res = await apiCall('delete_product', 'POST', { id: productId });
  if (res.success) {
    showToast(res.message, 'success');
    loadProducts();
    loadRestockList();
    loadWarungDashboard();
  } else {
    showToast(res.message, 'error');
  }
}

function addToCart(productId) {
  const prod = state.products.find(p => p.id === productId);
  if (!prod) return;

  const existing = state.cart.find(item => item.product_id === prod.id);
  if (existing) {
    if (existing.qty < prod.stok || prod.kategori === 'iuran_rt') {
      existing.qty++;
    } else {
      showToast('Stok barang di etalase sudah habis!', 'error');
      return;
    }
  } else {
    state.cart.push({
      product_id: prod.id,
      nama_item: prod.nama_produk,
      harga_beli_satuan: prod.harga_beli,
      harga_satuan: prod.harga_jual,
      qty: 1,
      satuan: prod.satuan,
      kategori: prod.kategori
    });
  }

  renderCart();
  showToast(`✓ Ditambahkan ke keranjang: ${prod.nama_produk}`, 'success');
}

function updateCartQty(productId, delta) {
  const index = state.cart.findIndex(i => i.product_id === productId);
  if (index === -1) return;

  state.cart[index].qty += delta;
  if (state.cart[index].qty <= 0) {
    state.cart.splice(index, 1);
  }
  renderCart();
}

function clearCart() {
  state.cart = [];
  state.cashAmount = 0;
  const cashInput = document.getElementById('pos-cash-input');
  if (cashInput) cashInput.value = '';
  renderCart();
  updateMobileFloatingCart();
}

function getCartTotal() {
  return state.cart.reduce((sum, item) => sum + (item.qty * item.harga_satuan), 0);
}

function getCartItemCount() {
  return state.cart.reduce((sum, item) => sum + item.qty, 0);
}

function renderCart() {
  const cartContainer = document.getElementById('pos-cart-items');
  const totalSubEl = document.getElementById('pos-cart-subtotal');
  const grandTotalEl = document.getElementById('pos-cart-grandtotal');

  if (!cartContainer) return;

  if (state.cart.length === 0) {
    cartContainer.innerHTML = `
      <div class="cart-empty">
        <div style="font-size:2.2rem; margin-bottom:0.4rem;">🛍️</div>
        <div>Keranjang Belanja Kosong</div>
        <div style="font-size:0.75rem; color:#94a3b8; margin-top:3px;">Pilih barang warung di etalase sebelah kiri</div>
      </div>
    `;
    if (totalSubEl) totalSubEl.textContent = 'Rp 0';
    if (grandTotalEl) grandTotalEl.textContent = 'Rp 0';
    updateKembalian();
    updateMobileFloatingCart();
    return;
  }

  const subtotal = getCartTotal();
  cartContainer.innerHTML = state.cart.map(item => {
    const itemTotal = item.qty * item.harga_satuan;
    return `
      <div class="cart-item-row">
        <div>
          <div class="cart-item-title">${escapeHtml(item.nama_item)}</div>
          <div class="cart-item-calc">${formatRupiah(item.harga_satuan)} x ${item.qty}</div>
        </div>
        <div class="cart-qty-ctrl">
          <button class="btn-qty" onclick="updateCartQty(${item.product_id}, -1)">-</button>
          <span style="font-weight:700; font-size:0.88rem; min-width:20px; text-align:center;">${item.qty}</span>
          <button class="btn-qty" onclick="updateCartQty(${item.product_id}, 1)">+</button>
          <span style="font-weight:800; font-size:0.9rem; margin-left:6px; color:#16a34a;">${formatRupiah(itemTotal)}</span>
        </div>
      </div>
    `;
  }).join('');

  if (totalSubEl) totalSubEl.textContent = formatRupiah(subtotal);
  if (grandTotalEl) grandTotalEl.textContent = formatRupiah(subtotal);

  updateKembalian();
  updateMobileFloatingCart();
}

function updateMobileFloatingCart() {
  const floatingBar = document.getElementById('mobile-floating-cart');
  const countBadge = document.getElementById('mobile-cart-badge');
  const amountEl = document.getElementById('mobile-cart-amount');

  if (!floatingBar) return;

  const count = getCartItemCount();
  const total = getCartTotal();

  if (count > 0 && state.currentTab === 'pos') {
    floatingBar.classList.add('active');
    if (countBadge) countBadge.textContent = count;
    if (amountEl) amountEl.textContent = formatRupiah(total);
  } else {
    floatingBar.classList.remove('active');
  }
}

function toggleMobileCart(show = true) {
  const drawer = document.getElementById('mobile-cart-drawer');
  if (drawer) {
    if (show) {
      drawer.classList.add('active');
    } else {
      drawer.classList.remove('active');
    }
  }
}

function setQuickCash(amount) {
  const total = getCartTotal();
  if (amount === 'pas') {
    state.cashAmount = total;
  } else {
    state.cashAmount = Number(amount);
  }

  const cashInput = document.getElementById('pos-cash-input');
  if (cashInput) cashInput.value = state.cashAmount;

  updateKembalian();
}

function updateKembalian() {
  const total = getCartTotal();
  const kembalianBox = document.getElementById('pos-kembalian-box');
  const kembalianVal = document.getElementById('pos-kembalian-val');

  if (state.selectedPaymentMethod === 'tunai' && state.cart.length > 0) {
    if (kembalianBox) kembalianBox.style.display = 'flex';
    const sisa = state.cashAmount - total;
    if (kembalianVal) {
      if (sisa >= 0) {
        kembalianVal.textContent = formatRupiah(sisa);
        kembalianVal.style.color = '#15803d';
      } else {
        kembalianVal.textContent = `Kurang ${formatRupiah(Math.abs(sisa))}`;
        kembalianVal.style.color = '#dc2626';
      }
    }
  } else {
    if (kembalianBox) kembalianBox.style.display = 'none';
  }
}

function selectPaymentMethod(method) {
  state.selectedPaymentMethod = method;
  document.querySelectorAll('.payment-method-btn').forEach(btn => {
    btn.classList.toggle('selected', btn.dataset.method === method);
  });

  // Toggle cash input visibility vs Kasbon inputs
  const cashSection = document.getElementById('pos-cash-section');
  const kasbonSection = document.getElementById('pos-kasbon-section');

  if (cashSection) cashSection.style.display = (method === 'tunai') ? 'block' : 'none';
  if (kasbonSection) kasbonSection.style.display = (method === 'kasbon') ? 'block' : 'none';

  updateKembalian();
}

// ==============================================================================
// 6. PROSES CHECKOUT TRANSAKSI KASIR
// ==============================================================================
async function processPOSCheckout() {
  if (state.cart.length === 0) {
    showToast('Keranjang belanja warung masih kosong!', 'error');
    return;
  }

  const total = getCartTotal();
  const custName = document.getElementById('pos-cust-name')?.value || 'Pelanggan Umum';
  const custPhone = document.getElementById('pos-cust-phone')?.value || '';
  const jatuhTempo = document.getElementById('pos-kasbon-tempo')?.value || '';

  // Validation for Cash
  if (state.selectedPaymentMethod === 'tunai') {
    if (state.cashAmount < total) {
      showToast(`Uang tunai kurang ${formatRupiah(total - state.cashAmount)}! Masukkan nominal uang pembeli.`, 'error');
      return;
    }
  }

  // Validation for Kasbon
  if (state.selectedPaymentMethod === 'kasbon') {
    if (!custName || custName === 'Pelanggan Umum') {
      showToast('Wajib isi Nama Pelanggan untuk mencatat kasbon hutang!', 'error');
      return;
    }
  }

  const payload = {
    customer_name: custName,
    customer_phone: custPhone,
    payment_method: state.selectedPaymentMethod,
    bayar_tunai: state.cashAmount,
    jatuh_tempo: jatuhTempo,
    items: state.cart
  };

  const res = await apiCall('create_pos_transaction', 'POST', payload);
  if (res.success) {
    const trx = res.data;
    state.activeTransaction = trx;
    showToast(res.message, 'success');

    clearCart();
    loadWarungDashboard();
    loadProducts();
    loadTransactions();
    loadKasbonList();

    if (trx.payment_method === 'qris_bi' || trx.payment_method.endsWith('_va')) {
      openPaymentGatewayModal(trx);
    } else {
      showReceiptModal(trx.invoice_no);
    }
  } else {
    showToast(res.message, 'error');
  }
}

// ==============================================================================
// 7. BUKU KAS BON / HUTANG PELANGGAN
// ==============================================================================
async function loadKasbonList() {
  const res = await apiCall('get_kasbon_list');
  if (res.success) {
    state.kasbonList = res.data;
    renderKasbonTable(state.kasbonList);
  }
}

function renderKasbonTable(list) {
  const tbody = document.getElementById('kasbon-table-body');
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2rem; color:#94a3b8;">Tidak ada catatan hutang / kas bon pelanggan</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(k => {
    const isLunas = k.status === 'lunas';
    return `
      <tr>
        <td>
          <div style="font-weight:800;">${escapeHtml(k.nama_pelanggan)}</div>
          <div style="font-size:0.75rem; color:#64748b;">${escapeHtml(k.no_hp || '-')} • ${escapeHtml(k.alamat || '-')}</div>
        </td>
        <td><b>${formatRupiah(k.total_hutang)}</b></td>
        <td style="color:#16a34a;">${formatRupiah(k.sudah_dibayar)}</td>
        <td style="font-weight:800; color:${isLunas ? '#16a34a' : '#dc2626'};">${formatRupiah(k.sisa_hutang)}</td>
        <td><span class="badge ${isLunas ? 'badge-lunas' : 'badge-belum_lunas'}">${isLunas ? '✓ LUNAS' : '⚠️ BELUM LUNAS'}</span></td>
        <td style="font-size:0.78rem;">${k.jatuh_tempo || '-'}</td>
        <td>
          <div style="display:flex; gap:4px;">
            ${!isLunas ? `
              <button class="btn btn-sm btn-primary" onclick="openBayarKasbonModal(${k.id}, '${escapeHtml(k.nama_pelanggan)}', ${k.sisa_hutang})">💵 Bayar</button>
              ${k.no_hp ? `<button class="btn btn-sm btn-wa" onclick="sendWAReminder(${k.id})">📲 Tagih WA</button>` : ''}
            ` : `<span style="font-size:0.75rem; color:#16a34a; font-weight:bold;">Lunas (${k.tanggal_lunas})</span>`}
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function openBayarKasbonModal(id, nama, sisa) {
  document.getElementById('bayar-kb-id').value = id;
  document.getElementById('bayar-kb-nama').textContent = nama;
  document.getElementById('bayar-kb-sisa').textContent = formatRupiah(sisa);
  document.getElementById('bayar-kb-nominal').value = sisa;
  openModal('modal-bayar-kasbon');
}

async function submitBayarKasbon() {
  const id = document.getElementById('bayar-kb-id')?.value;
  const nominal = document.getElementById('bayar-kb-nominal')?.value;

  const res = await apiCall('pay_kasbon', 'POST', { id: id, nominal_bayar: nominal });
  if (res.success) {
    showToast(res.message, 'success');
    closeModal('modal-bayar-kasbon');
    loadKasbonList();
    loadWarungDashboard();
  } else {
    showToast(res.message, 'error');
  }
}

function sendWAReminder(kasbonId) {
  const kb = state.kasbonList.find(k => k.id === kasbonId);
  if (!kb || !kb.no_hp) {
    showToast('Nomor WhatsApp pelanggan tidak tersedia', 'error');
    return;
  }

  const cleanPhone = kb.no_hp.replace(/[^0-9]/g, '').replace(/^0/, '62');
  const pesan = encodeURIComponent(
    `Halo Yth. Bpk/Ibu ${kb.nama_pelanggan},\n\n` +
    `Kami dari *Warung Berkah RT 002 RW 004 Kedaung Kali Angke* menginformasikan catatan belanja kasbon:\n` +
    `📌 *Total Hutang:* ${formatRupiah(kb.total_hutang)}\n` +
    `💰 *Sisa Tagihan:* *${formatRupiah(kb.sisa_hutang)}*\n` +
    `⏱️ *Jatuh Tempo:* ${kb.jatuh_tempo || 'Segera'}\n\n` +
    `Pembayaran dapat langsung ke warung atau transfer via *QRIS 0% Biaya Admin* / Bank Virtual Account.\n` +
    `Terima kasih banyak atas kerjasamanya! 🙏`
  );

  window.open(`https://wa.me/${cleanPhone}?text=${pesan}`, '_blank');
}

// ==============================================================================
// 8. LABA RUGI & BUKU KAS WARUNG
// ==============================================================================
async function loadLabaRugi() {
  const res = await apiCall('get_laba_rugi');
  if (res.success) {
    const lr = res.data;
    document.getElementById('lr-omset').textContent = formatRupiah(lr.omset_penjualan);
    document.getElementById('lr-modal').textContent = formatRupiah(lr.modal_pokok);
    document.getElementById('lr-laba-kotor').textContent = formatRupiah(lr.laba_kotor);
    document.getElementById('lr-biaya-ops').textContent = formatRupiah(lr.biaya_operasional);
    document.getElementById('lr-laba-bersih').textContent = formatRupiah(lr.laba_bersih);
  }
  loadKasBukuTable();
}

async function loadKasBukuTable() {
  const res = await apiCall('get_kas_buku');
  if (res.success) {
    const tbody = document.getElementById('kas-buku-table-body');
    if (!tbody) return;

    const list = res.data.records;
    if (list.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:1.5rem; color:#94a3b8;">Belum ada catatan mutasi kas</td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(k => `
      <tr>
        <td>${k.tanggal}</td>
        <td><span class="badge ${k.tipe === 'masuk' ? 'badge-lunas' : 'badge-belum_lunas'}">${k.tipe.toUpperCase()}</span></td>
        <td><b>${escapeHtml(k.kategori)}</b></td>
        <td>${escapeHtml(k.keterangan)}</td>
        <td style="font-weight:800; color:${k.tipe === 'masuk' ? '#16a34a' : '#dc2626'};">
          ${k.tipe === 'masuk' ? '+' : '-'} ${formatRupiah(k.nominal)}
        </td>
      </tr>
    `).join('');
  }
}

async function submitBiayaOperasional() {
  const kategori = document.getElementById('ops-kategori')?.value;
  const nominal = document.getElementById('ops-nominal')?.value;
  const keterangan = document.getElementById('ops-keterangan')?.value;

  if (!nominal || !keterangan) {
    showToast('Nominal dan keterangan biaya operasional wajib diisi', 'error');
    return;
  }

  const res = await apiCall('add_kas_buku', 'POST', {
    tipe: 'keluar',
    kategori: kategori,
    nominal: nominal,
    keterangan: keterangan
  });

  if (res.success) {
    showToast(res.message, 'success');
    closeModal('modal-tambah-biaya');
    loadLabaRugi();
    loadWarungDashboard();
  }
}

// ==============================================================================
// 9. INVENTARIS & RESTOCK BARANG
// ==============================================================================
async function loadRestockList() {
  const res = await apiCall('get_products');
  if (res.success) {
    const tbody = document.getElementById('stok-table-body');
    if (!tbody) return;

    tbody.innerHTML = res.data.map(p => {
      const isLow = p.stok <= p.stok_minimum && p.kategori !== 'iuran_rt';
      return `
        <tr>
          <td><b>${escapeHtml(p.kode_produk)}</b></td>
          <td><b>${escapeHtml(p.nama_produk)}</b></td>
          <td><span class="product-tag tag-${p.kategori}">${p.kategori}</span></td>
          <td>${formatRupiah(p.harga_beli)}</td>
          <td style="font-weight:800; color:#16a34a;">${formatRupiah(p.harga_jual)}</td>
          <td style="font-weight:800; color:${isLow ? '#dc2626' : '#0f172a'};">
            ${p.stok} ${p.satuan} ${isLow ? '⚠️ (Menipis)' : ''}
          </td>
          <td>
            <div style="display:flex; gap:4px; flex-wrap:wrap;">
              <button class="btn btn-sm btn-primary" onclick="openRestockModal(${p.id}, '${escapeHtml(p.nama_produk)}', ${p.harga_beli})">➕ Kulakan</button>
              <button class="btn btn-sm btn-outline" onclick="openEditProductModal(${p.id})">✏️ Edit</button>
              <button class="btn btn-sm btn-outline" style="color:#ef4444; border-color:#fee2e2;" onclick="deleteProduct(${p.id}, '${escapeHtml(p.nama_produk)}')">🗑️</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }
}

function openRestockModal(id, nama, hargaBeli) {
  document.getElementById('restock-id').value = id;
  document.getElementById('restock-nama').textContent = nama;
  document.getElementById('restock-hb').value = hargaBeli;
  document.getElementById('restock-qty').value = 10;
  openModal('modal-restock-barang');
}

async function submitRestock() {
  const id = document.getElementById('restock-id')?.value;
  const qty = document.getElementById('restock-qty')?.value;
  const hb = document.getElementById('restock-hb')?.value;

  const res = await apiCall('restock_product', 'POST', {
    product_id: id,
    tambah_stok: qty,
    harga_beli_baru: hb
  });

  if (res.success) {
    showToast(res.message, 'success');
    closeModal('modal-restock-barang');
    loadRestockList();
    loadProducts();
    loadWarungDashboard();
  }
}

// ==============================================================================
// 10. QRIS & CETAK NOTA / STRUK
// ==============================================================================
function openPaymentGatewayModal(trx) {
  state.activeTransaction = trx;
  state.qrisSecondsLeft = 60;

  const modal = document.getElementById('modal-payment-gateway');
  if (!modal) return;

  document.getElementById('pg-invoice-no').textContent = trx.invoice_no;
  document.getElementById('pg-customer-name').textContent = trx.customer_name;
  document.getElementById('pg-total-amount').textContent = formatRupiah(trx.total_amount);

  const qrisBox = document.getElementById('pg-qris-container');
  const vaBox = document.getElementById('pg-va-container');

  if (trx.payment_method === 'qris_bi') {
    qrisBox.style.display = 'block';
    vaBox.style.display = 'none';
    
    const qrTarget = document.getElementById('dynamic-qris-canvas');
    qrTarget.innerHTML = '';
    new QRCode(qrTarget, {
      text: trx.qris_payload,
      width: 190,
      height: 190,
      colorDark: "#000000",
      colorLight: "#ffffff"
    });
  } else {
    qrisBox.style.display = 'none';
    vaBox.style.display = 'block';
    document.getElementById('pg-va-number').textContent = trx.va_number;
    document.getElementById('pg-va-bank-name').textContent = trx.payment_method.toUpperCase();
  }

  modal.classList.add('active');
}

async function simulateInstantPayment() {
  if (!state.activeTransaction) return;

  const res = await apiCall('verify_payment', 'POST', { invoice_no: state.activeTransaction.invoice_no });
  if (res.success) {
    showToast('Pembayaran QRIS Dikonfirmasi Lunas (0% Fee)', 'success');
    closeModal('modal-payment-gateway');
    loadTransactions();
    loadWarungDashboard();
    showReceiptModal(state.activeTransaction.invoice_no);
  }
}

async function loadTransactions() {
  const res = await apiCall('get_transactions');
  if (res.success) {
    state.transactions = res.data;
    renderTransactionTable(state.transactions);
  }
}

function renderTransactionTable(transactions) {
  const tbody = document.getElementById('trx-table-body');
  if (!tbody) return;

  tbody.innerHTML = transactions.map(t => `
    <tr>
      <td><b>${escapeHtml(t.invoice_no)}</b></td>
      <td>${escapeHtml(t.customer_name)}</td>
      <td><span class="badge" style="background:#f1f5f9;">${t.payment_method.toUpperCase()}</span></td>
      <td><b>${formatRupiah(t.total_amount)}</b></td>
      <td style="color:#16a34a; font-weight:bold;">${formatRupiah(t.laba_kotor)}</td>
      <td><span class="badge ${t.payment_status === 'paid' ? 'badge-lunas' : 'badge-kasbon'}">${t.payment_status.toUpperCase()}</span></td>
      <td>
        <button class="btn btn-sm btn-outline" onclick="showReceiptModal('${t.invoice_no}')">🧾 Struk</button>
      </td>
    </tr>
  `).join('');
}

async function showReceiptModal(invoiceNo) {
  const res = await apiCall(`get_transaction_detail&invoice_no=${invoiceNo}`);
  if (res.success) {
    const trx = res.data;
    const modal = document.getElementById('modal-receipt');
    const container = document.getElementById('printable-receipt');
    
    container.innerHTML = `
      <div class="thermal-receipt-sheet">
        <div class="receipt-header">
          <div style="font-size:15px; font-weight:bold;">WARUNG BERKAH RT 002</div>
          <div>RT 002 / RW 004 Kedaung Kali Angke</div>
          <div>Cengkareng, Jakarta Barat</div>
          <div>Telp: 0856-1122-3344</div>
        </div>
        <div style="margin-bottom:6px;">
          <div>No. Nota : <b>${escapeHtml(trx.invoice_no)}</b></div>
          <div>Tgl      : ${trx.created_at}</div>
          <div>Pelanggan: ${escapeHtml(trx.customer_name)}</div>
          <div>Metode   : ${trx.payment_method.toUpperCase()}</div>
        </div>
        <div class="receipt-divider"></div>
        <div>
          ${(trx.items || []).map(i => `
            <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
              <span>${escapeHtml(i.nama_item)} x${i.qty}</span>
              <span>${formatRupiah(i.subtotal)}</span>
            </div>
          `).join('')}
        </div>
        <div class="receipt-divider"></div>
        <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:13px;">
          <span>TOTAL BELANJA</span>
          <span>${formatRupiah(trx.total_amount)}</span>
        </div>
        ${trx.payment_method === 'tunai' ? `
          <div style="display:flex; justify-content:space-between; font-size:11px; margin-top:3px;">
            <span>Bayar Tunai</span>
            <span>${formatRupiah(trx.bayar_tunai)}</span>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:11px; font-weight:bold; color:#15803d;">
            <span>KEMBALIAN</span>
            <span>${formatRupiah(trx.kembalian)}</span>
          </div>
        ` : ''}
        <div style="display:flex; justify-content:space-between; font-size:10px; color:#16a34a; margin-top:3px;">
          <span>BIAYA ADMIN MDR</span>
          <span>GRATIS (0%)</span>
        </div>
        <div class="receipt-divider"></div>
        <div style="text-align:center; font-size:10px; margin-top:6px;">
          <div>*** Terima Kasih Telah Berbelanja ***</div>
          <div>Barang yang dibeli tidak dapat ditukar</div>
        </div>
      </div>
    `;

    modal.classList.add('active');
  }
}

// ==============================================================================
// 11. LAYANAN RT 002 (SURAT PENGANTAR 1 MENIT)
// ==============================================================================
async function loadSuratList() {
  const res = await apiCall('get_surat_list');
  if (res.success) {
    const tbody = document.getElementById('surat-table-body');
    if (!tbody) return;

    tbody.innerHTML = res.data.map(s => `
      <tr>
        <td><b>${escapeHtml(s.no_pengajuan)}</b></td>
        <td><b>${escapeHtml(s.nama_pemohon)}</b></td>
        <td>${escapeHtml(s.jenis_surat)}</td>
        <td>${escapeHtml(s.keperluan)}</td>
        <td><span class="badge ${s.status === 'disetujui' ? 'badge-lunas' : 'badge-kasbon'}">${s.status.toUpperCase()}</span></td>
        <td>
          ${s.status === 'menunggu' ? `
            <button class="btn btn-sm btn-primary" onclick="approveSuratFast(${s.id})">⚡ Setujui (1 Menit)</button>
          ` : `<span style="font-size:0.75rem; color:#16a34a;">Selesai (${s.no_surat_resmi || '-'})</span>`}
        </td>
      </tr>
    `).join('');
  }
}

async function approveSuratFast(id) {
  const res = await apiCall('process_surat', 'POST', { id: id, status: 'disetujui' });
  if (res.success) {
    showToast(res.message, 'success');
    loadSuratList();
  }
}

async function submitKasbonManual() {
  const nama = document.getElementById('kb-form-nama')?.value;
  const nohp = document.getElementById('kb-form-nohp')?.value;
  const nominal = document.getElementById('kb-form-nominal')?.value;
  const tempo = document.getElementById('kb-form-tempo')?.value;
  const ket = document.getElementById('kb-form-ket')?.value;

  if (!nama || !nominal) {
    showToast('Nama pelanggan dan nominal hutang wajib diisi', 'error');
    return;
  }

  const res = await apiCall('add_kasbon', 'POST', {
    nama_pelanggan: nama,
    no_hp: nohp,
    nominal: nominal,
    jatuh_tempo: tempo,
    keterangan: ket
  });

  if (res.success) {
    showToast(res.message, 'success');
    closeModal('modal-tambah-kasbon');
    document.getElementById('kb-form-nama').value = '';
    document.getElementById('kb-form-nominal').value = '';
    document.getElementById('kb-form-ket').value = '';
    loadKasbonList();
    loadWarungDashboard();
  } else {
    showToast(res.message, 'error');
  }
}

async function submitTambahBarang() {
  const id = document.getElementById('prod-id')?.value;
  const nama = document.getElementById('prod-nama')?.value;
  const kategori = document.getElementById('prod-kategori')?.value;
  const satuan = document.getElementById('prod-satuan')?.value;
  const hb = document.getElementById('prod-hb')?.value;
  const hj = document.getElementById('prod-hj')?.value;
  const stok = document.getElementById('prod-stok')?.value;
  const stokMin = document.getElementById('prod-stok-min')?.value;

  if (!nama || !hj) {
    showToast('Nama barang dan harga jual wajib diisi', 'error');
    return;
  }

  const payload = {
    nama_produk: nama,
    kategori: kategori,
    satuan: satuan,
    harga_beli: hb,
    harga_jual: hj,
    stok: stok,
    stok_minimum: stokMin
  };

  if (id) {
    payload.id = id;
  }

  const res = await apiCall('save_product', 'POST', payload);

  if (res.success) {
    showToast(res.message, 'success');
    closeModal('modal-tambah-barang');
    document.getElementById('prod-id').value = '';
    document.getElementById('prod-nama').value = '';
    document.getElementById('prod-hj').value = '';
    loadProducts();
    loadRestockList();
    loadWarungDashboard();
  } else {
    showToast(res.message, 'error');
  }
}

// Utilities
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) modal.classList.remove('active');
}

function printDocument() {
  window.print();
}

function formatRupiah(number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(number || 0);
}

function escapeHtml(string) {
  if (!string) return '';
  return String(string)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

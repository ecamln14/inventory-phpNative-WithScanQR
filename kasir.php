<?php
require_once 'auth.php';
$namaUser = $_SESSION['nama'] ?? $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir POS</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 220px;
            height: 100vh;
            background: #1a1a2e;
            padding: 24px 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            color: white;
            font-size: 16px;
            font-weight: 700;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-nav {
            padding: 16px 0;
        }

        .nav-label {
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 20px 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }

        .nav-item.active {
            background: #4f46e5;
            color: white;
        }

        /* MAIN */
        .main-wrap {
            margin-left: 220px;
            padding: 24px;
            min-height: 100vh;
        }

        h2 {
            font-size: 22px;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
            max-width: 1100px;
        }

        .panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .panel h3 {
            font-size: 15px;
            color: #555;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        #reader {
            width: 100% !important;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .input-row {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }

        .input-row input {
            flex: 1;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .input-row input:focus {
            border-color: #4f46e5;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:focus-visible, .metode-btn:focus-visible, .qty-btn:focus-visible {
            outline: 2px solid #4f46e5;
            outline-offset: 2px;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-success {
            background: #16a34a;
            color: white;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .btn-success:hover:not(:disabled) {
            background: #15803d;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border-radius: 10px;
            margin-top: 8px;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-outline {
            background: white;
            color: #4f46e5;
            border: 2px solid #4f46e5;
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border-radius: 10px;
            margin-top: 8px;
        }

        .btn-outline:hover {
            background: #f0f0ff;
        }

        .card {
            background: #f8faff;
            border: 1px solid #e0e7ff;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .card-thumb {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: #fff;
            border: 1px solid #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .card-body {
            flex: 1;
            min-width: 0;
        }

        .card h3 {
            color: #4f46e5;
            border: none;
            margin-bottom: 4px;
            font-size: 16px;
        }

        .card p {
            font-size: 13px;
            color: #555;
            margin: 2px 0;
        }

        .card-error {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
            color: #dc2626;
            font-size: 14px;
            font-weight: 600;
        }

        .cart-item {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
            background: #fafafa;
            display: flex;
            gap: 12px;
        }

        .cart-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: #fff;
            border: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .cart-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cart-item-body {
            flex: 1;
            min-width: 0;
        }

        .cart-item strong {
            font-size: 15px;
            color: #1a1a2e;
        }

        .cart-item .harga {
            font-size: 13px;
            color: #666;
            margin: 4px 0 10px;
        }

        .qty-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .qty-btn:hover {
            background: #f0f0f0;
        }

        .qty-num {
            font-weight: 700;
            font-size: 15px;
            min-width: 24px;
            text-align: center;
        }

        .btn-hapus {
            margin-left: auto;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 6px;
            padding: 5px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-hapus:hover {
            background: #fecaca;
        }

        .total-box {
            background: #1a1a2e;
            color: white;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: 700;
        }

        .empty-cart {
            text-align: center;
            color: #aaa;
            padding: 30px 0;
            font-size: 14px;
        }

        /* BAYAR */
        .bayar-section {
            margin-top: 14px;
            border-top: 1px solid #eee;
            padding-top: 14px;
        }

        .bayar-section label {
            font-size: 13px;
            color: #555;
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        .bayar-section input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            margin-bottom: 10px;
        }

        .bayar-section input:focus {
            border-color: #4f46e5;
        }

        /* PILIH METODE */
        .metode-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 14px;
        }

        .metode-btn {
            padding: 11px 8px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: white;
            color: #555;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .metode-btn:hover {
            border-color: #4f46e5;
        }

        .metode-btn.aktif {
            border-color: #4f46e5;
            background: #4f46e5;
            color: white;
        }

        /* MEMBER */
        .member-search {
            position: relative;
        }

        .member-list {
            position: absolute;
            left: 0; right: 0;
            top: 100%;
            margin-top: -6px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            z-index: 20;
            max-height: 240px;
            overflow-y: auto;
        }

        .member-opt {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-bottom: 1px solid #f1f1f1;
            background: white;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
        }

        .member-opt:last-child {
            border-bottom: none;
        }

        .member-opt:hover, .member-opt:focus-visible {
            background: #f0f0ff;
            outline: none;
        }

        .member-opt small {
            display: block;
            color: #777;
            font-size: 12px;
        }

        .member-opt .saldo-opt {
            font-weight: 700;
            color: #4f46e5;
            white-space: nowrap;
        }

        .member-empty {
            padding: 12px;
            font-size: 13px;
            color: #888;
        }

        .member-chip {
            background: #f0f0ff;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }

        .member-chip .baris {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .member-chip strong {
            font-size: 15px;
            color: #1a1a2e;
        }

        .member-chip small {
            color: #666;
            font-size: 12px;
        }

        .member-chip .btn-ganti {
            background: white;
            border: 1px solid #c7d2fe;
            color: #4f46e5;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .saldo-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 700;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .saldo-box.kurang {
            background: #fff5f5;
            border-color: #fecaca;
            color: #dc2626;
        }

        .nominal-cepat {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .nominal-cepat button {
            flex: 1;
            min-width: 70px;
            padding: 7px 4px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f8faff;
            color: #4f46e5;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .nominal-cepat button:hover {
            background: #4f46e5;
            color: white;
        }

        .kembalian-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: 700;
            color: #16a34a;
            margin-bottom: 10px;
        }

        /* STRUK */
        #struk-modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }

        #struk-modal.show {
            display: flex;
        }

        #struk-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 340px;
            max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        #struk-content {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
        }

        #struk-content h3 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 4px;
        }

        #struk-content .garis {
            border-top: 1px dashed #aaa;
            margin: 8px 0;
        }

        #struk-content .row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        #struk-content .row.bold {
            font-weight: 700;
        }

        .struk-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .struk-actions button {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-print {
            background: #4f46e5;
            color: white;
        }

        .btn-close-struk {
            background: #f1f1f1;
            color: #333;
        }

        .user-info {
            padding: 12px 20px 0;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main-wrap { margin-left: 0; padding: 14px; }
            .layout { grid-template-columns: 1fr; }
        }

        @media print {
            body * { visibility: hidden; }
            #struk-content, #struk-content * { visibility: visible; }
            #struk-content {
                position: fixed;
                top: 0; left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        📦 TrackInventori
    </div>
    <?php if ($namaUser !== ''): ?>
        <div class="user-info">Masuk sebagai <?= htmlspecialchars($namaUser) ?> (<?= htmlspecialchars($_SESSION['role'] ?? 'kasir') ?>)</div>
    <?php endif; ?>
    <nav class="sidebar-nav">
        <div class="nav-label">Dashboard</div>
        <a href="index.php" class="nav-item">🏠 Dashboard</a>
        <div class="nav-label">Master Data</div>
        <a href="barang.php" class="nav-item">📦 Data Barang</a>
        <a href="kategori.php" class="nav-item">🏷️ Kategori</a>
        <?php if (is_admin()): ?>
        <a href="member.php" class="nav-item">👥 Member &amp; Saldo</a>
        <?php endif; ?>
        <div class="nav-label">Transaksi</div>
        <a href="kasir.php" class="nav-item active">🏪 Kasir POS</a>
        <a href="transaksi_masuk.php" class="nav-item">⬇️ Barang Masuk</a>
        <a href="transaksi_keluar.php" class="nav-item">⬆️ Barang Keluar</a>
        <div class="nav-label">Laporan</div>
        <a href="laporan.php" class="nav-item">📊 Laporan</a>
        <div class="nav-label">Akun</div>
        <a href="logout.php" class="nav-item" style="color:#f87171">🚪 Logout</a>
    </nav>
</div>

<div class="main-wrap">

<h2>🏪 Kasir POS</h2>

<div class="layout">

    <!-- KIRI: SCANNER -->
    <div>
        <div class="panel">
            <h3>📷 Scan QR Produk</h3>
            <div id="reader"></div>
            <div class="input-row">
                <input
                    type="text"
                    id="qr"
                    placeholder="Input kode QR manual..."
                    onkeydown="if(event.key==='Enter'){cariBarang();}"
                >
                <button class="btn btn-primary" onclick="cariBarang()">
                    Cari
                </button>
            </div>
            <div id="hasil"></div>
        </div>
    </div>

    <!-- KANAN: KERANJANG -->
    <div>
        <div class="panel">
            <h3>🛒 Keranjang Belanja</h3>
            <div id="cart">
                <div class="empty-cart">Belum ada produk di keranjang</div>
            </div>
            <div class="total-box">
                <span>Total</span>
                <span id="total">Rp 0</span>
            </div>

            <!-- PILIH METODE -->
            <div class="bayar-section">
                <label>Metode pembayaran</label>
                <div class="metode-toggle">
                    <button type="button" class="metode-btn aktif" id="btnTunai" onclick="setMetode('tunai')">💵 Tunai</button>
                    <button type="button" class="metode-btn" id="btnSaldo" onclick="setMetode('saldo')">👤 Saldo Member</button>
                </div>

                <!-- TUNAI -->
                <div id="sectionTunai">
                    <label for="uangBayar">💵 Uang Bayar</label>
                    <div class="nominal-cepat">
                        <button type="button" onclick="setNominal(10000)">10rb</button>
                        <button type="button" onclick="setNominal(20000)">20rb</button>
                        <button type="button" onclick="setNominal(50000)">50rb</button>
                        <button type="button" onclick="setNominal(100000)">100rb</button>
                        <button type="button" onclick="setNominal(200000)">200rb</button>
                    </div>
                    <input
                        type="number"
                        id="uangBayar"
                        placeholder="Masukkan jumlah uang..."
                        oninput="hitungKembalian()"
                    >
                    <div class="kembalian-box">
                        <span>Kembalian</span>
                        <span id="kembalian">Rp 0</span>
                    </div>
                </div>

                <!-- SALDO MEMBER -->
                <div id="sectionSaldo" style="display:none">
                    <div id="memberPicker">
                        <label for="cariMember">👤 Cari member</label>
                        <div class="member-search">
                            <input
                                type="text"
                                id="cariMember"
                                placeholder="Kode, nama, atau nomor HP..."
                                autocomplete="off"
                                oninput="cariMemberDebounced()"
                            >
                            <div id="memberList" class="member-list" style="display:none"></div>
                        </div>
                    </div>
                    <div id="memberTerpilih" style="display:none"></div>
                    <div id="saldoInfo"></div>
                </div>
            </div>

            <button class="btn btn-success" id="btnBayar" onclick="bayar()">
                💳 Bayar Sekarang
            </button>
            <button class="btn btn-outline" onclick="lihatStruk()">
                🧾 Lihat Struk
            </button>
            <button class="btn btn-danger" onclick="kosongkanKeranjang()">
                🗑️ Kosongkan Keranjang
            </button>
        </div>
    </div>

</div>

</div>

<!-- MODAL STRUK -->
<div id="struk-modal">
    <div id="struk-box">
        <div id="struk-content"></div>
        <div class="struk-actions">
            <button class="btn-print" onclick="cetakStruk()">🖨️ Cetak</button>
            <button class="btn-close-struk" onclick="tutupStruk()">✕ Tutup</button>
        </div>
    </div>
</div>

<audio id="beepSound" preload="auto">
    <source src="beep.mp3" type="audio/mpeg">
</audio>

<script>
    // folder tempat gambar barang disimpan (samakan dengan barang.php / transaksi_keluar.php)
    const UPLOAD_URL = 'uploads/barang/';

    let cart = [];
    let isScanning = true;
    let metode = 'tunai';
    let memberDipilih = null;   // {id, kode_member, nama, saldo}
    let sedangBayar = false;
    let timerCariMember = null;

    // data struk terakhir
    let lastInvoice = '';
    let lastTotal = 0;
    let lastBayar = 0;
    let lastCart = [];
    let lastMetode = 'tunai';
    let lastMember = null;      // {nama, kode_member, saldo_sesudah}

    // ---------- helper ----------
    function rp(n){
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    // cegah teks dari database dibaca sebagai HTML
    function esc(s){
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
        }[c]));
    }

    function totalKeranjang(){
        return cart.reduce((s, i) => s + i.harga * i.qty, 0);
    }

    // bikin HTML thumbnail: gambar kalau ada, fallback ikon kotak kalau tidak
    function thumbHtml(gambar, nama, cssClass) {
        if (gambar) {
            return `<div class="${cssClass}"><img src="${UPLOAD_URL}${encodeURIComponent(gambar)}" alt="${esc(nama)}" onerror="this.onerror=null;this.parentElement.innerHTML='📦';"></div>`;
        }
        return `<div class="${cssClass}">📦</div>`;
    }

    // ---------- keranjang ----------
    function renderCart(){

        let html = '';
        let total = 0;

        if(cart.length === 0){
            document.getElementById('cart').innerHTML =
                '<div class="empty-cart">Belum ada produk di keranjang</div>';
            document.getElementById('total').innerHTML = 'Rp 0';
            hitungKembalian();
            updateSaldoInfo();
            return;
        }

        cart.forEach((item, index) => {

            total += item.harga * item.qty;

            html += `
                <div class="cart-item">
                    ${thumbHtml(item.gambar, item.nama, 'cart-thumb')}
                    <div class="cart-item-body">
                        <strong>${esc(item.nama)}</strong>
                        <div class="harga">${rp(item.harga)} / pcs</div>
                        <div class="qty-row">
                            <button class="qty-btn" onclick="kurangQty(${index})" aria-label="Kurangi jumlah">−</button>
                            <span class="qty-num">${item.qty}</span>
                            <button class="qty-btn" onclick="tambahQty(${index})" aria-label="Tambah jumlah">+</button>
                            <button class="btn-hapus" onclick="hapusItem(${index})">Hapus</button>
                        </div>
                    </div>
                </div>
            `;
        });

        document.getElementById('cart').innerHTML = html;
        document.getElementById('total').innerHTML = rp(total);
        hitungKembalian();
        updateSaldoInfo();
    }

    function setNominal(nominal){
        document.getElementById('uangBayar').value = nominal;
        hitungKembalian();
    }

    function hitungKembalian(){
        const total = totalKeranjang();
        const bayar = parseInt(document.getElementById('uangBayar').value) || 0;
        const kembalian = bayar - total;
        document.getElementById('kembalian').innerHTML =
            kembalian >= 0
                ? rp(kembalian)
                : `<span style="color:#dc2626">Kurang ${rp(Math.abs(kembalian))}</span>`;
    }

    function tambahQty(index){
        const item = cart[index];
        if(item.stok !== undefined && item.qty >= item.stok){
            alert(`Stok ${item.nama} hanya ${item.stok}`);
            return;
        }
        item.qty++;
        renderCart();
    }

    function kurangQty(index){
        if(cart[index].qty > 1){
            cart[index].qty--;
        }else{
            if(confirm("Hapus barang dari keranjang?")){
                cart.splice(index, 1);
            }
        }
        renderCart();
    }

    function hapusItem(index){
        if(confirm("Hapus barang ini?")){
            cart.splice(index, 1);
            renderCart();
        }
    }

    function resetForm(){
        cart = [];
        document.getElementById('hasil').innerHTML = '';
        document.getElementById('qr').value = '';
        document.getElementById('uangBayar').value = '';
        pilihMember(null);
        renderCart();
    }

    function kosongkanKeranjang(){
        if(cart.length === 0) return;
        if(confirm("Kosongkan semua keranjang?")){
            resetForm();
        }
    }

    // ---------- metode bayar ----------
    function setMetode(m){
        metode = m;
        document.getElementById('btnTunai').classList.toggle('aktif', m === 'tunai');
        document.getElementById('btnSaldo').classList.toggle('aktif', m === 'saldo');
        document.getElementById('sectionTunai').style.display = m === 'tunai' ? '' : 'none';
        document.getElementById('sectionSaldo').style.display = m === 'saldo' ? '' : 'none';
        updateSaldoInfo();
        if(m === 'saldo'){
            document.getElementById('cariMember').focus();
        }
    }

    // ---------- member ----------
    function cariMemberDebounced(){
        clearTimeout(timerCariMember);
        timerCariMember = setTimeout(cariMember, 250);
    }

    function cariMember(){
        const q = document.getElementById('cariMember').value.trim();
        const list = document.getElementById('memberList');

        if(q.length < 2){
            list.style.display = 'none';
            return;
        }

        fetch(`cari_member.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
            if(!Array.isArray(data) || data.length === 0){
                list.innerHTML = '<div class="member-empty">Tidak ada member aktif yang cocok</div>';
            } else {
                list.innerHTML = data.map((m, i) => `
                    <button type="button" class="member-opt" onclick='pilihMemberIndex(${i})'>
                        <span>${esc(m.nama)}<small>${esc(m.kode_member)}${m.no_hp ? ' · ' + esc(m.no_hp) : ''}</small></span>
                        <span class="saldo-opt">${rp(m.saldo)}</span>
                    </button>
                `).join('');
                window._hasilMember = data;
            }
            list.style.display = '';
        })
        .catch(() => {
            list.innerHTML = '<div class="member-empty">Gagal menghubungi server</div>';
            list.style.display = '';
        });
    }

    function pilihMemberIndex(i){
        pilihMember(window._hasilMember[i]);
    }

    function pilihMember(m){
        memberDipilih = m;
        const picker = document.getElementById('memberPicker');
        const chip = document.getElementById('memberTerpilih');
        document.getElementById('memberList').style.display = 'none';
        document.getElementById('cariMember').value = '';

        if(!m){
            picker.style.display = '';
            chip.style.display = 'none';
            chip.innerHTML = '';
        } else {
            picker.style.display = 'none';
            chip.style.display = '';
            chip.innerHTML = `
                <div class="member-chip">
                    <div class="baris">
                        <div><strong>${esc(m.nama)}</strong><br><small>${esc(m.kode_member)}</small></div>
                        <button type="button" class="btn-ganti" onclick="pilihMember(null)">Ganti</button>
                    </div>
                </div>
            `;
        }
        updateSaldoInfo();
    }

    function updateSaldoInfo(){
        const box = document.getElementById('saldoInfo');
        if(metode !== 'saldo' || !memberDipilih){
            box.innerHTML = '';
            return;
        }
        const total = totalKeranjang();
        const sisa = memberDipilih.saldo - total;
        if(sisa >= 0){
            box.innerHTML = `
                <div class="saldo-box"><span>Saldo saat ini</span><span>${rp(memberDipilih.saldo)}</span></div>
                <div class="saldo-box"><span>Saldo setelah bayar</span><span>${rp(sisa)}</span></div>
            `;
        } else {
            box.innerHTML = `
                <div class="saldo-box"><span>Saldo saat ini</span><span>${rp(memberDipilih.saldo)}</span></div>
                <div class="saldo-box kurang"><span>Saldo kurang</span><span>${rp(Math.abs(sisa))}</span></div>
            `;
        }
    }

    // ---------- bayar ----------
    function setSedangBayar(v){
        sedangBayar = v;
        const b = document.getElementById('btnBayar');
        b.disabled = v;
        b.innerHTML = v ? '⏳ Memproses...' : '💳 Bayar Sekarang';
    }

    function bayar(){

        if(sedangBayar) return;

        if(cart.length === 0){
            alert("Keranjang kosong");
            return;
        }

        const total = totalKeranjang();
        let bayar = 0;

        if(metode === 'tunai'){
            bayar = parseInt(document.getElementById('uangBayar').value) || 0;
            if(bayar < total){
                alert("Uang bayar kurang!");
                return;
            }
        } else {
            if(!memberDipilih){
                alert("Pilih member terlebih dahulu");
                return;
            }
            if(memberDipilih.saldo < total){
                alert("Saldo member tidak cukup");
                return;
            }
            if(!confirm(`Potong saldo ${memberDipilih.nama} sebesar ${rp(total)}?`)){
                return;
            }
        }

        setSedangBayar(true);

        fetch("proses_bayar.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                items: cart.map(i => ({ id: i.id, qty: i.qty })),
                metode: metode,
                member_id: metode === 'saldo' ? memberDipilih.id : null,
                bayar: bayar
            })
        })
        .then(res => {
            if(res.status === 401){
                alert("Sesi login habis. Silakan login lagi.");
                window.location = 'login.php';
                throw new Error('unauthorized');
            }
            return res.json();
        })
        .then(result => {

            if(result.success){

                // pakai data dari server supaya struk sama persis dengan yang tercatat
                lastInvoice = result.invoice;
                lastTotal   = result.total;
                lastBayar   = result.bayar;
                lastCart    = [...cart];
                lastMetode  = result.metode;
                lastMember  = result.member;

                resetForm();
                tampilStruk();

            } else {
                alert(result.message);
            }
        })
        .catch(err => {
            if(err.message !== 'unauthorized'){
                alert("Gagal menghubungi server. Cek koneksi lalu cek riwayat sebelum mengulang transaksi.");
            }
        })
        .finally(() => setSedangBayar(false));
    }

    // ---------- struk ----------
    function tampilStruk(){

        const tgl = new Date().toLocaleString('id-ID');
        const kembalian = lastBayar - lastTotal;

        let itemsHtml = '';
        lastCart.forEach(item => {
            itemsHtml += `
                <div class="row">
                    <span>${esc(item.nama)} x${item.qty}</span>
                    <span>${rp(item.harga * item.qty)}</span>
                </div>
            `;
        });

        let pembayaranHtml;
        if(lastMetode === 'saldo' && lastMember){
            pembayaranHtml = `
                <div class="row">
                    <span>Metode</span>
                    <span>Saldo Member</span>
                </div>
                <div class="row">
                    <span>Member</span>
                    <span>${esc(lastMember.nama)}</span>
                </div>
                <div class="row">
                    <span>Kode</span>
                    <span>${esc(lastMember.kode_member)}</span>
                </div>
                <div class="row" style="color:#16a34a;font-weight:700">
                    <span>Sisa saldo</span>
                    <span>${rp(lastMember.saldo_sesudah)}</span>
                </div>
            `;
        } else {
            pembayaranHtml = `
                <div class="row">
                    <span>Metode</span>
                    <span>Tunai</span>
                </div>
                <div class="row">
                    <span>Bayar</span>
                    <span>${rp(lastBayar)}</span>
                </div>
                <div class="row" style="color:#16a34a;font-weight:700">
                    <span>Kembalian</span>
                    <span>${rp(kembalian)}</span>
                </div>
            `;
        }

        document.getElementById('struk-content').innerHTML = `
            <h3>🏪 TrackInventori</h3>
            <div style="text-align:center;font-size:12px;color:#888">${tgl}</div>
            <div style="text-align:center;font-size:12px;color:#888">No: ${esc(lastInvoice)}</div>
            <div class="garis"></div>
            ${itemsHtml}
            <div class="garis"></div>
            <div class="row bold">
                <span>Total</span>
                <span>${rp(lastTotal)}</span>
            </div>
            ${pembayaranHtml}
            <div class="garis"></div>
            <div style="text-align:center;font-size:12px;color:#888">Terima kasih! 🙏</div>
        `;

        document.getElementById('struk-modal').classList.add('show');
    }

    function lihatStruk(){
        if(lastInvoice === ''){
            alert("Belum ada transaksi");
            return;
        }
        document.getElementById('struk-modal').classList.add('show');
    }

    function cetakStruk(){
        window.print();
    }

    function tutupStruk(){
        document.getElementById('struk-modal').classList.remove('show');
    }

    // ---------- cari barang ----------
    function cariBarang(){

        const qr = document.getElementById('qr').value.trim();

        if(!qr){
            document.getElementById('hasil').innerHTML =
                '<div class="card-error">⚠️ Masukkan kode QR terlebih dahulu</div>';
            return;
        }

        fetch(`get_barang.php?qr=${encodeURIComponent(qr)}`)
        .then(res => {
            if(res.status === 401){
                alert("Sesi login habis. Silakan login lagi.");
                window.location = 'login.php';
                throw new Error('unauthorized');
            }
            return res.json();
        })
        .then(barang => {

            if(!barang || !barang.id){
                document.getElementById('hasil').innerHTML =
                    '<div class="card-error">❌ Barang tidak ditemukan</div>';
                return;
            }

            document.getElementById('hasil').innerHTML = `
                <div class="card">
                    ${thumbHtml(barang.gambar, barang.nama_barang, 'card-thumb')}
                    <div class="card-body">
                        <h3>${esc(barang.nama_barang)}</h3>
                        <p>Kode : ${esc(barang.kode_barang)}</p>
                        <p>Harga : ${rp(barang.harga_jual)}</p>
                        <p>Stok : ${barang.stok}</p>
                    </div>
                </div>
            `;

            const stok = Number(barang.stok);
            const existing = cart.find(item => item.id == barang.id);
            const qtySekarang = existing ? existing.qty : 0;

            if(qtySekarang + 1 > stok){
                document.getElementById('hasil').innerHTML +=
                    `<div class="card-error">⚠️ Stok ${esc(barang.nama_barang)} tidak cukup (tersisa ${stok})</div>`;
                return;
            }

            if(existing){
                existing.qty++;
                existing.stok = stok;
            }else{
                cart.push({
                    id: barang.id,
                    nama: barang.nama_barang,
                    harga: Number(barang.harga_jual),
                    gambar: barang.gambar || '',
                    stok: stok,
                    qty: 1
                });
            }

            renderCart();
        })
        .catch(err => {
            if(err.message === 'unauthorized') return;
            document.getElementById('hasil').innerHTML =
                '<div class="card-error">❌ Gagal menghubungi server</div>';
        });
    }

    function onScanSuccess(decodedText){

        if(!isScanning) return;

        isScanning = false;

        const sound = document.getElementById('beepSound');
        sound.currentTime = 0;
        sound.play().catch(() => {});

        document.getElementById('qr').value = decodedText;

        cariBarang();

        setTimeout(() => {
            isScanning = true;
        }, 2000);
    }

    const html5QrCode = new Html5Qrcode("reader");

    html5QrCode.start(
        { facingMode: "user" },
        {
            fps: 10,
            qrbox: 250
        },
        onScanSuccess
    ).catch(err => {
        console.log(err);
    });

</script>

</body>
</html>
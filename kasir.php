<!DOCTYPE html>
<html>
<head>
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

        .btn-success:hover {
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
    <nav class="sidebar-nav">
        <div class="nav-label">Dashboard</div>
        <a href="index.php" class="nav-item">🏠 Dashboard</a>
        <div class="nav-label">Master Data</div>
        <a href="barang.php" class="nav-item">📦 Data Barang</a>
        <a href="kategori.php" class="nav-item">🏷️ Kategori</a>
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

            <!-- INPUT BAYAR -->
            <div class="bayar-section">
                <label>💵 Uang Bayar</label>
                <div class="nominal-cepat">
                    <button onclick="setNominal(10000)">10rb</button>
                    <button onclick="setNominal(20000)">20rb</button>
                    <button onclick="setNominal(50000)">50rb</button>
                    <button onclick="setNominal(100000)">100rb</button>
                    <button onclick="setNominal(200000)">200rb</button>
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

            <button class="btn btn-success" onclick="bayar()">
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
    let lastInvoice = '';
    let lastTotal = 0;
    let lastBayar = 0;
    let lastCart = [];

    // bikin HTML thumbnail: gambar kalau ada, fallback ikon kotak kalau tidak
    function thumbHtml(gambar, nama, cssClass) {
        if (gambar) {
            return `<div class="${cssClass}"><img src="${UPLOAD_URL}${gambar}" alt="${nama}" onerror="this.onerror=null;this.parentElement.innerHTML='📦';"></div>`;
        }
        return `<div class="${cssClass}">📦</div>`;
    }

    function renderCart(){

        let html = '';
        let total = 0;

        if(cart.length === 0){
            document.getElementById('cart').innerHTML =
                '<div class="empty-cart">Belum ada produk di keranjang</div>';
            document.getElementById('total').innerHTML = 'Rp 0';
            hitungKembalian();
            return;
        }

        cart.forEach((item, index) => {

            total += item.harga * item.qty;

            html += `
                <div class="cart-item">
                    ${thumbHtml(item.gambar, item.nama, 'cart-thumb')}
                    <div class="cart-item-body">
                        <strong>${item.nama}</strong>
                        <div class="harga">Rp ${item.harga.toLocaleString()} / pcs</div>
                        <div class="qty-row">
                            <button class="qty-btn" onclick="kurangQty(${index})">−</button>
                            <span class="qty-num">${item.qty}</span>
                            <button class="qty-btn" onclick="tambahQty(${index})">+</button>
                            <button class="btn-hapus" onclick="hapusItem(${index})">Hapus</button>
                        </div>
                    </div>
                </div>
            `;
        });

        document.getElementById('cart').innerHTML = html;
        document.getElementById('total').innerHTML = `Rp ${total.toLocaleString()}`;
        hitungKembalian();
    }

    function setNominal(nominal){
        document.getElementById('uangBayar').value = nominal;
        hitungKembalian();
    }

    function hitungKembalian(){
        const total = cart.reduce((s, i) => s + i.harga * i.qty, 0);
        const bayar = parseInt(document.getElementById('uangBayar').value) || 0;
        const kembalian = bayar - total;
        document.getElementById('kembalian').innerHTML =
            kembalian >= 0
                ? `Rp ${kembalian.toLocaleString()}`
                : `<span style="color:#dc2626">Kurang Rp ${Math.abs(kembalian).toLocaleString()}</span>`;
    }

    function tambahQty(index){
        cart[index].qty++;
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

    function kosongkanKeranjang(){
        if(cart.length === 0) return;
        if(confirm("Kosongkan semua keranjang?")){
            cart = [];
            document.getElementById('hasil').innerHTML = '';
            document.getElementById('qr').value = '';
            document.getElementById('uangBayar').value = '';
            renderCart();
        }
    }

    function bayar(){

        if(cart.length === 0){
            alert("Keranjang kosong");
            return;
        }

        const total = cart.reduce((s, i) => s + i.harga * i.qty, 0);
        const bayar = parseInt(document.getElementById('uangBayar').value) || 0;

        if(bayar < total){
            alert("Uang bayar kurang!");
            return;
        }

        lastTotal = total;
        lastBayar = bayar;
        lastCart = [...cart];

        fetch("proses_bayar.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(cart)
        })
        .then(res => res.json())
        .then(result => {

            if(result.success){

                lastInvoice = result.invoice ?? ('TRX' + Date.now());

                cart = [];
                document.getElementById('hasil').innerHTML = '';
                document.getElementById('qr').value = '';
                document.getElementById('uangBayar').value = '';
                renderCart();

                tampilStruk();

            } else {
                alert(result.message);
            }
        });
    }

    function tampilStruk(){

        const tgl = new Date().toLocaleString('id-ID');
        const kembalian = lastBayar - lastTotal;

        let itemsHtml = '';
        lastCart.forEach(item => {
            itemsHtml += `
                <div class="row">
                    <span>${item.nama} x${item.qty}</span>
                    <span>Rp ${(item.harga * item.qty).toLocaleString()}</span>
                </div>
            `;
        });

        document.getElementById('struk-content').innerHTML = `
            <h3>🏪 TrackInventori</h3>
            <div style="text-align:center;font-size:12px;color:#888">${tgl}</div>
            <div style="text-align:center;font-size:12px;color:#888">No: ${lastInvoice}</div>
            <div class="garis"></div>
            ${itemsHtml}
            <div class="garis"></div>
            <div class="row bold">
                <span>Total</span>
                <span>Rp ${lastTotal.toLocaleString()}</span>
            </div>
            <div class="row">
                <span>Bayar</span>
                <span>Rp ${lastBayar.toLocaleString()}</span>
            </div>
            <div class="row" style="color:#16a34a;font-weight:700">
                <span>Kembalian</span>
                <span>Rp ${kembalian.toLocaleString()}</span>
            </div>
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

    function cariBarang(){

        const qr = document.getElementById('qr').value.trim();

        if(!qr){
            document.getElementById('hasil').innerHTML =
                '<div class="card-error">⚠️ Masukkan kode QR terlebih dahulu</div>';
            return;
        }

        fetch(`get_barang.php?qr=${qr}`)
        .then(res => res.json())
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
                        <h3>${barang.nama_barang}</h3>
                        <p>Kode : ${barang.kode_barang}</p>
                        <p>Harga : Rp ${Number(barang.harga_jual).toLocaleString()}</p>
                        <p>Stok : ${barang.stok}</p>
                    </div>
                </div>
            `;

            const existing = cart.find(item => item.id == barang.id);

            if(existing){
                existing.qty++;
            }else{
                cart.push({
                    id: barang.id,
                    nama: barang.nama_barang,
                    harga: Number(barang.harga_jual),
                    gambar: barang.gambar || '',
                    qty: 1
                });
            }

            renderCart();
        })
        .catch(() => {
            document.getElementById('hasil').innerHTML =
                '<div class="card-error">❌ Gagal menghubungi server</div>';
        });
    }

    function beep(){

        const audioCtx =
            new (window.AudioContext || window.webkitAudioContext)();

        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.frequency.value = 1000;
        oscillator.type = "sine";
        gainNode.gain.value = 0.1;

        oscillator.start();

        setTimeout(() => {
            oscillator.stop();
        }, 120);
    }

    function onScanSuccess(decodedText){

        if(!isScanning) return;

        isScanning = false;

        const sound = document.getElementById('beepSound');
        sound.currentTime = 0;
        sound.play();

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
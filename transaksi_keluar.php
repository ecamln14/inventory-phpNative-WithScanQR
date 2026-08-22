<?php
require_once 'auth.php';
require_once 'database.php';
$conn = getConnection();
$pesan = '';
 
function generateNoTrx($conn, $tipe) {
    $prefix = $tipe === 'masuk' ? 'MSK' : 'KLR';
    $today  = date('Ymd');
    $r = $conn->query("SELECT no_transaksi FROM transaksi WHERE tipe='$tipe' AND DATE(created_at)=CURDATE() ORDER BY id DESC LIMIT 1");
    if ($r->num_rows === 0) return "$prefix-$today-001";
    $last = $r->fetch_assoc()['no_transaksi'];
    return "$prefix-$today-" . str_pad((int)substr($last,-3)+1, 3, '0', STR_PAD_LEFT);
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl = $conn->real_escape_string($_POST['tanggal']);
    $ket = $conn->real_escape_string(trim($_POST['keterangan'] ?? ''));
    $no  = generateNoTrx($conn, 'keluar');
 
    $items = $_POST['items'] ?? [];
 
    if (empty($items)) {
        $pesan = ['type'=>'danger','text'=>'Pilih minimal 1 barang!'];
    } else {
        $errors     = [];
        $validItems = [];
 
        foreach ($items as $item) {
            $barang_id = (int)($item['id'] ?? 0);
            $jumlah    = (int)($item['jumlah'] ?? 0);
            $harga     = (float)($item['harga'] ?? 0);
            $total     = $jumlah * $harga;
 
            if ($barang_id <= 0) continue;
 
            if ($jumlah <= 0) {
                $errors[] = "Jumlah harus lebih dari 0 untuk salah satu barang!";
                continue;
            }
 
            $stokRow = $conn->query("SELECT stok, nama_barang FROM barang WHERE id=$barang_id")->fetch_assoc();
            if (!$stokRow) {
                $errors[] = "Barang ID $barang_id tidak ditemukan!";
                continue;
            }
 
            if ($stokRow['stok'] < $jumlah) {
                $errors[] = "{$stokRow['nama_barang']}: Stok tidak cukup! Tersedia: {$stokRow['stok']}";
                continue;
            }
 
            $validItems[] = [
                'id'     => $barang_id,
                'jumlah' => $jumlah,
                'harga'  => $harga,
                'total'  => $total,
                'nama'   => $stokRow['nama_barang']
            ];
        }
 
        if (!empty($errors)) {
            $pesan = ['type'=>'danger','text'=>implode('<br>', $errors)];
        } elseif (!empty($validItems)) {
            $conn->begin_transaction();
            try {
                foreach ($validItems as $v) {
                    $conn->query("INSERT INTO transaksi (no_transaksi, tipe, barang_id, jumlah, harga_satuan, total_harga, keterangan, tanggal)
                                  VALUES ('$no', 'keluar', {$v['id']}, {$v['jumlah']}, {$v['harga']}, {$v['total']}, '$ket', '$tgl')");
                    $conn->query("UPDATE barang SET stok = stok - {$v['jumlah']} WHERE id = {$v['id']}");
                }
                $conn->commit();
                $pesan = ['type'=>'success','text'=>"Transaksi berhasil disimpan! No: $no (".count($validItems)." barang)"];
            } catch (Exception $e) {
                $conn->rollback();
                $pesan = ['type'=>'danger','text'=>'Gagal menyimpan: '.$e->getMessage()];
            }
        } else {
            $pesan = ['type'=>'danger','text'=>'Tidak ada barang valid yang diproses.'];
        }
    }
}
 
// Kolom `gambar` harus sudah ada di tabel barang:
// ALTER TABLE barang ADD COLUMN gambar VARCHAR(255) NULL AFTER deskripsi;
define('UPLOAD_URL', 'uploads/barang/');
$barangList = $conn->query("SELECT id, kode_barang, nama_barang, stok, satuan, harga_jual, gambar FROM barang ORDER BY nama_barang");
$noTrxBaru  = generateNoTrx($conn, 'keluar');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== Grid pemilihan barang berbasis gambar (Horizontal Scroll) ===== */
        .barang-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 4px 4px 10px;
            border: 1px solid var(--gray-light, #e5e7eb);
            border-radius: 8px;
            scroll-behavior: smooth;
        }
        .barang-grid::-webkit-scrollbar {
            height: 8px;
        }
        .barang-grid::-webkit-scrollbar-thumb {
            background: var(--gray-light, #d1d5db);
            border-radius: 4px;
        }
        .barang-card {
            flex-shrink: 0;
            width: 130px;
            border: 2px solid var(--gray-light, #e5e7eb);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
            position: relative;
        }
        .barang-card.selected {
            border-color: var(--danger, #ef4444);
            box-shadow: 0 0 0 3px rgba(239,68,68,0.12);
        }
        .barang-card.disabled { opacity: 0.5; }
        .barang-card-img {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: var(--gray-lighter, #f9fafb);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .barang-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .barang-card-img > i { font-size: 30px; color: var(--gray, #9ca3af); }
        .stok-habis-badge {
            position: absolute; top: 4px; left: 4px;
            background: var(--danger, #ef4444); color: #fff;
            font-size: 10px; font-weight: 700;
            padding: 2px 6px; border-radius: 4px; z-index: 2;
        }
        .qty-badge-count {
            position: absolute; top: 4px; right: 4px;
            min-width: 20px; height: 20px; border-radius: 10px;
            background: var(--danger, #ef4444); color: #fff;
            font-size: 11px; font-weight: 700;
            display: none; align-items: center; justify-content: center;
            padding: 0 5px; z-index: 2;
        }
        .barang-card.selected .qty-badge-count { display: flex; }
 
        /* kontrol +/- yang menempel di bawah gambar (overlay) */
        .qty-overlay {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            display: flex; align-items: center; justify-content: center;
            padding: 5px;
            background: linear-gradient(to top, rgba(0,0,0,0.55), rgba(0,0,0,0));
            z-index: 3;
        }
        .btn-add-overlay {
            background: var(--danger, #ef4444); color: #fff;
            border: none; border-radius: 20px;
            font-size: 12px; font-weight: 700;
            padding: 5px 12px; cursor: pointer;
            display: flex; align-items: center; gap: 5px;
        }
        .btn-add-overlay:hover { opacity: 0.9; }
        .qty-row-card {
            display: flex; align-items: center; gap: 4px;
            background: rgba(255,255,255,0.95);
            border-radius: 20px; padding: 3px;
        }
        .qty-row-card button {
            width: 22px; height: 22px; border-radius: 50%; border: none;
            background: var(--danger, #ef4444); color: #fff;
            font-size: 14px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; line-height: 1;
            flex-shrink: 0;
        }
        .qty-row-card button:hover { opacity: 0.88; }
        .qty-row-card input {
            width: 32px; text-align: center; border: none; background: transparent;
            font-size: 12.5px; font-weight: 700; color: var(--dark, #1f2937);
        }
 
        .barang-card-info { padding: 6px 8px 8px; display: flex; flex-direction: column; gap: 2px; }
        .barang-card-info strong {
            font-size: 12px; line-height: 1.3; color: var(--dark, #1f2937);
            display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .barang-card-info .kode { font-size: 10px; color: var(--gray, #6b7280); font-weight: 600; }
        .barang-card-info .stok { font-size: 10.5px; color: var(--gray, #6b7280); }
        .barang-card-info .harga { font-size: 12px; font-weight: 700; color: var(--danger, #ef4444); }
 
        /* ===== Panel ringkasan barang terpilih ===== */
        .selected-item {
            display: flex; gap: 10px;
            background: var(--gray-light, #f3f4f6);
            padding: 10px; border-radius: 10px; margin-bottom: 8px;
            border-left: 4px solid var(--danger, #ef4444);
        }
        .selected-item-img {
            width: 48px; height: 48px; border-radius: 8px; overflow: hidden; flex-shrink: 0;
            background: #fff; display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--gray-light, #e5e7eb);
        }
        .selected-item-img img { width: 100%; height: 100%; object-fit: cover; }
        .selected-item-img i { font-size: 18px; color: var(--gray, #9ca3af); }
        .selected-item-body { flex: 1; min-width: 0; }
        .selected-item .item-header {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 6px; margin-bottom: 4px;
        }
        .selected-item .item-header strong { font-size: 12.5px; color: var(--dark, #1f2937); line-height: 1.3; }
        .selected-item .item-header .qty-mini { font-size: 11.5px; color: var(--gray, #6b7280); white-space: nowrap; }
        .btn-remove-item {
            background: none; border: none; cursor: pointer; color: var(--gray, #9ca3af); font-size: 13px;
            flex-shrink: 0; padding: 2px 4px; line-height: 1;
        }
        .btn-remove-item:hover { color: var(--danger, #ef4444); }
        .price-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .price-row label { font-size: 11px; color: var(--gray, #6b7280); white-space: nowrap; }
        .price-row input {
            flex: 1; padding: 4px 6px; font-size: 12.5px;
            border: 1px solid var(--gray-light, #d1d5db); border-radius: 6px;
        }
        .selected-item .subtotal { text-align: right; font-size: 13px; color: var(--danger, #ef4444); font-weight: 700; }
        .btn-select-all {
            font-size: 12px; padding: 4px 10px;
            background: var(--primary, #3b82f6); color: white;
            border: none; border-radius: 6px; cursor: pointer;
            margin-bottom: 8px;
        }
        .btn-select-all:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><i class="fa-solid fa-boxes-stacked"></i><span>TrackInventori</span></div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <div class="nav-label">Master Data</div>
            <a href="barang.php" class="nav-item"><i class="fa-solid fa-box"></i> Data Barang</a>
            <a href="kategori.php" class="nav-item"><i class="fa-solid fa-tags"></i> Kategori</a>
            <div class="nav-label">Transaksi</div>
            <a href="kasir.php" class="nav-item"><i class="fa-solid fa-cash-register"></i> Kasir POS</a>
            <a href="transaksi_masuk.php" class="nav-item"><i class="fa-solid fa-arrow-down"></i> Barang Masuk</a>
            <a href="transaksi_keluar.php" class="nav-item active"><i class="fa-solid fa-arrow-up"></i> Barang Keluar</a>
            <div class="nav-label">Laporan</div>
            <a href="laporan.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Laporan</a>
            <div class="nav-label">Akun</div>
            <a href="logout.php" class="nav-item" style="color:#f87171"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">Barang Keluar</h1>
            <div class="topbar-right">
                <i class="fa-solid fa-user" style="margin-right:6px"></i>
                <?= htmlspecialchars($_SESSION['nama']) ?> &nbsp;|&nbsp;
                <i class="fa-regular fa-calendar"></i> <?= date('d F Y') ?>
            </div>
        </div>
        <div class="content-area">
 
            <?php if ($pesan): ?>
            <div class="alert alert-<?= $pesan['type'] ?>" style="margin-bottom: 20px;">
                <i class="fa-solid fa-circle-check"></i> <?= $pesan['text'] ?>
            </div>
            <?php endif; ?>
 
            <!-- Layout diubah menjadi 1 kolom penuh agar fokus ke form input -->
            <div style="max-width: 950px; margin: 0 auto;">
                <div class="card">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center">
                        <span class="card-title"><i class="fa-solid fa-arrow-up" style="color:var(--danger)"></i> Input Barang Keluar</span>
                        <!-- Tombol menuju halaman riwayat terpisah -->
                        <a href="riwayat_keluar.php" class="btn" style="background:var(--primary, #3b82f6); color:#fff; text-decoration:none; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-clock-rotate-left"></i> Lihat Riwayat
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formKeluar">
                            <div class="form-group" style="margin-bottom:12px">
                                <label>No. Transaksi</label>
                                <input type="text" class="form-control" value="<?= $noTrxBaru ?>" disabled style="background:var(--gray-light)">
                            </div>
                            <div class="form-group" style="margin-bottom:12px">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:12px">
                                <label>Pilih Barang <small style="color:var(--gray)">(klik + pada gambar untuk menambah)</small></label>
                                <button type="button" class="btn-select-all" onclick="toggleSelectAll()">
                                    <i class="fa-solid fa-check-double"></i> Pilih Semua (1pcs) / Batal
                                </button>
                                <div class="barang-grid" id="barangGrid">
                                    <?php while($b = $barangList->fetch_assoc()):
                                        $gambar = trim($b['gambar'] ?? '');
                                    ?>
                                    <div class="barang-card <?= $b['stok']==0?'disabled':'' ?>"
                                         id="card_<?= $b['id'] ?>"
                                         data-id="<?= $b['id'] ?>"
                                         data-harga="<?= $b['harga_jual'] ?>"
                                         data-stok="<?= $b['stok'] ?>"
                                         data-nama="<?= htmlspecialchars($b['nama_barang']) ?>"
                                         data-kode="<?= htmlspecialchars($b['kode_barang']) ?>"
                                         data-gambar="<?= htmlspecialchars($gambar) ?>">
                                        <div class="barang-card-img">
                                            <?php if ($gambar !== ''): ?>
                                                <img src="<?= UPLOAD_URL . htmlspecialchars($gambar) ?>"
                                                     alt="<?= htmlspecialchars($b['nama_barang']) ?>"
                                                     onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-box'}));">
                                            <?php else: ?>
                                                <i class="fa-solid fa-box"></i>
                                            <?php endif; ?>
                                            <?php if ($b['stok']==0): ?><span class="stok-habis-badge">Habis</span><?php endif; ?>
                                            <span class="qty-badge-count" id="badge_<?= $b['id'] ?>">0</span>
 
                                            <!-- kontrol +/- menempel di bawah gambar -->
                                            <div class="qty-overlay" id="overlay_<?= $b['id'] ?>">
                                                <button type="button" class="btn-add-overlay" onclick="addFirst(<?= $b['id'] ?>)" <?= $b['stok']==0?'disabled':'' ?>>
                                                    <i class="fa-solid fa-plus"></i> Tambah
                                                </button>
                                            </div>
                                        </div>
                                        <div class="barang-card-info">
                                            <strong><?= htmlspecialchars($b['nama_barang']) ?></strong>
                                            <span class="kode"><?= htmlspecialchars($b['kode_barang']) ?></span>
                                            <span class="stok">Stok: <?= $b['stok'] ?> <?= htmlspecialchars($b['satuan']) ?></span>
                                            <span class="harga">Rp <?= number_format($b['harga_jual'],0,',','.') ?></span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <div id="selectedItems" style="margin-bottom:12px"></div>
                            <div style="background:var(--danger-light);border-radius:8px;padding:12px;margin-bottom:12px;display:flex;justify-content:space-between">
                                <span style="font-weight:600;color:var(--danger)">Grand Total</span>
                                <span id="total_preview" style="font-weight:700;color:var(--danger)">Rp 0</span>
                            </div>
                            <div class="form-group" style="margin-bottom:16px">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" placeholder="Keterangan untuk semua barang..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi</button>
                        </form>
                    </div>
                </div>
            </div>
 
        </div>
    </main>
</div>
<script>
// state barang yang dipilih: { id: {nama, kode, gambar, harga, stok, jumlah} }
const selected = {};
const selectedItemsContainer = document.getElementById('selectedItems');
 
function getCardData(id) {
    const card = document.getElementById('card_' + id);
    return {
        nama:   card.dataset.nama,
        kode:   card.dataset.kode,
        gambar: card.dataset.gambar,
        harga:  parseFloat(card.dataset.harga) || 0,
        stok:   parseInt(card.dataset.stok) || 0
    };
}
 
function addFirst(id) {
    const card = document.getElementById('card_' + id);
    if (card.classList.contains('disabled')) return;
    const data = getCardData(id);
    selected[id] = { ...data, jumlah: 1 };
    card.classList.add('selected');
    renderCardOverlay(id);
    renderSelectedItems();
}
 
function changeQty(id, delta) {
    const item = selected[id];
    if (!item) return;
    let next = item.jumlah + delta;
 
    if (next < 1) {
        removeBarang(id);
        return;
    }
    if (next > item.stok) next = item.stok;
    item.jumlah = next;
    renderCardOverlay(id);
    renderSelectedItems();
}
 
function setQty(id, value) {
    const item = selected[id];
    if (!item) return;
    let v = parseInt(value) || 1;
    if (v < 1) v = 1;
    if (v > item.stok) v = item.stok;
    item.jumlah = v;
    renderCardOverlay(id);
    renderSelectedItems();
}
 
function setHarga(id, value) {
    const item = selected[id];
    if (!item) return;
    item.harga = parseFloat(value) || 0;
    renderSelectedItems();
}
 
function removeBarang(id) {
    delete selected[id];
    const card = document.getElementById('card_' + id);
    if (card) card.classList.remove('selected');
    renderCardOverlay(id);
    renderSelectedItems();
}
 
function renderCardOverlay(id) {
    const overlay = document.getElementById('overlay_' + id);
    const badge   = document.getElementById('badge_' + id);
    const item    = selected[id];
 
    if (!item) {
        badge.style.display = 'none';
        badge.textContent = '0';
        overlay.innerHTML = `
            <button type="button" class="btn-add-overlay" onclick="addFirst(${id})">
                <i class="fa-solid fa-plus"></i> Tambah
            </button>`;
        return;
    }
 
    badge.textContent = item.jumlah;
    overlay.innerHTML = `
        <div class="qty-row-card">
            <button type="button" onclick="changeQty(${id},-1)">−</button>
            <input type="number" min="1" max="${item.stok}" value="${item.jumlah}"
                   onclick="event.stopPropagation()"
                   onchange="setQty(${id}, this.value)">
            <button type="button" onclick="changeQty(${id},1)" ${item.jumlah>=item.stok?'disabled':''}>+</button>
        </div>`;
}
 
function renderSelectedItems() {
    const ids = Object.keys(selected);
 
    if (ids.length === 0) {
        selectedItemsContainer.innerHTML = '<div style="text-align:center;color:var(--gray);font-size:13px;padding:10px"><i class="fa-solid fa-info-circle"></i> Belum ada barang dipilih</div>';
        document.getElementById('total_preview').textContent = 'Rp 0';
        return;
    }
 
    let grandTotal = 0;
    let html = '';
 
    ids.forEach(id => {
        const it = selected[id];
        const subtotal = it.jumlah * it.harga;
        grandTotal += subtotal;
 
        const imgHtml = it.gambar
            ? `<img src="<?= UPLOAD_URL ?>${it.gambar}" alt="${it.nama}" onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-box'}));">`
            : `<i class="fa-solid fa-box"></i>`;
 
        html += `
        <div class="selected-item">
            <div class="selected-item-img">${imgHtml}</div>
            <div class="selected-item-body">
                <div class="item-header">
                    <strong>${it.kode} - ${it.nama} <span class="qty-mini">(x${it.jumlah})</span></strong>
                    <button type="button" class="btn-remove-item" onclick="removeBarang('${id}')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="price-row">
                    <label>Harga</label>
                    <input type="number" min="0" value="${it.harga}" onchange="setHarga('${id}', this.value)" name="items[${id}][harga]">
                </div>
                <input type="hidden" name="items[${id}][id]" value="${id}">
                <input type="hidden" name="items[${id}][jumlah]" value="${it.jumlah}">
                <div class="subtotal">Rp ${subtotal.toLocaleString('id-ID')}</div>
            </div>
        </div>`;
    });
 
    selectedItemsContainer.innerHTML = html;
    document.getElementById('total_preview').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
}
 
function toggleSelectAll() {
    const cards = Array.from(document.querySelectorAll('.barang-card:not(.disabled)'));
    const allSelected = cards.every(c => selected[c.dataset.id]);
 
    cards.forEach(c => {
        const id = c.dataset.id;
        if (allSelected) {
            removeBarang(id);
        } else if (!selected[id]) {
            addFirst(id);
        }
    });
}
 
renderSelectedItems();
 
document.getElementById('formKeluar').addEventListener('submit', function(e) {
    if (Object.keys(selected).length === 0) {
        e.preventDefault();
        alert('Pilih minimal 1 barang!');
    }
});
</script>
 
</body>
</html>
<?php $conn->close(); ?>
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
    $barang_id = (int)$_POST['barang_id'];
    $jumlah    = (int)$_POST['jumlah'];
    $harga     = (float)$_POST['harga_satuan'];
    $total     = $jumlah * $harga;
    $ket       = $conn->real_escape_string(trim($_POST['keterangan']));
    $tgl       = $conn->real_escape_string($_POST['tanggal']);
    $no        = generateNoTrx($conn, 'keluar');

    $stokRow = $conn->query("SELECT stok,nama_barang FROM barang WHERE id=$barang_id")->fetch_assoc();

    if ($jumlah <= 0) {
        $pesan = ['type'=>'danger','text'=>'Jumlah harus lebih dari 0!'];
    } elseif ($stokRow['stok'] < $jumlah) {
        $pesan = ['type'=>'danger','text'=>"Stok tidak cukup! Stok tersedia: {$stokRow['stok']}"];
    } else {
        $conn->query("INSERT INTO transaksi (no_transaksi,tipe,barang_id,jumlah,harga_satuan,total_harga,keterangan,tanggal) VALUES ('$no','keluar',$barang_id,$jumlah,$harga,$total,'$ket','$tgl')");
        $conn->query("UPDATE barang SET stok=stok-$jumlah WHERE id=$barang_id");
        $pesan = ['type'=>'success','text'=>"Transaksi berhasil! No: $no"];
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $trx = $conn->query("SELECT * FROM transaksi WHERE id=$id AND tipe='keluar'")->fetch_assoc();
    if ($trx) {
        $conn->query("UPDATE barang SET stok=stok+{$trx['jumlah']} WHERE id={$trx['barang_id']}");
        $conn->query("DELETE FROM transaksi WHERE id=$id");
        $pesan = ['type'=>'success','text'=>'Transaksi dihapus dan stok dikembalikan.'];
    }
}

$barangList = $conn->query("SELECT id,kode_barang,nama_barang,stok,satuan,harga_jual FROM barang ORDER BY nama_barang");
$riwayat    = $conn->query("SELECT t.*,b.nama_barang,b.kode_barang FROM transaksi t JOIN barang b ON t.barang_id=b.id WHERE t.tipe='keluar' ORDER BY t.created_at DESC LIMIT 20");
$noTrxBaru  = generateNoTrx($conn, 'keluar');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
<div class="alert alert-<?= $pesan['type'] ?>"><i class="fa-solid fa-circle-check"></i> <?= $pesan['text'] ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-arrow-up" style="color:var(--danger)"></i> Input Barang Keluar</span></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group" style="margin-bottom:12px">
                    <label>No. Transaksi</label>
                    <input type="text" class="form-control" value="<?= $noTrxBaru ?>" disabled style="background:var(--gray-light)">
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Pilih Barang</label>
                    <select name="barang_id" class="form-control" required onchange="isiHarga(this)">
                        <option value="">-- Pilih Barang --</option>
                        <?php while($b=$barangList->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>" data-stok="<?= $b['stok'] ?>" <?= $b['stok']==0?'disabled':'' ?>>
                            <?= $b['kode_barang'] ?> - <?= htmlspecialchars($b['nama_barang']) ?> (Stok: <?= $b['stok'] ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div id="infoStok" style="display:none;background:var(--warning-light);border-radius:8px;padding:10px;margin-bottom:12px;font-size:13px">
                    <i class="fa-solid fa-circle-info" style="color:var(--warning)"></i>
                    Stok tersedia: <strong id="stokTersedia">0</strong>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Harga Satuan (Rp)</label>
                    <input type="number" name="harga_satuan" id="harga_satuan" class="form-control" value="0">
                </div>
                <div style="background:var(--danger-light);border-radius:8px;padding:12px;margin-bottom:12px;display:flex;justify-content:space-between">
                    <span style="font-weight:600;color:var(--danger)">Total</span>
                    <span id="total_preview" style="font-weight:700;color:var(--danger)">Rp 0</span>
                </div>
                <div class="form-group" style="margin-bottom:16px">
                    <label>Keterangan</label>
                    <textarea name="keterangan" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-danger" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Riwayat Barang Keluar</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Harga Satuan</th><th>Total</th><th>Ket</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php while($r=$riwayat->fetch_assoc()): ?>
                    <tr>
                        <td style="font-size:12px;font-weight:700"><?= $r['no_transaksi'] ?></td>
                        <td><?= date('d/m/Y',strtotime($r['tanggal'])) ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($r['nama_barang']) ?></td>
                        <td><span class="badge badge-danger">-<?= $r['jumlah'] ?></span></td>
                        <td>Rp <?= number_format($r['harga_satuan'],0,',','.') ?></td>
                        <td style="font-weight:600">Rp <?= number_format($r['total_harga'],0,',','.') ?></td>
                        <td style="color:var(--gray)"> <?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                        <td><button class="btn btn-danger btn-sm" onclick="confirmDelete('?hapus=<?= $r['id'] ?>','transaksi ini')"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function isiHarga(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('harga_satuan').value = opt.dataset.harga || 0;
    document.getElementById('stokTersedia').textContent = opt.dataset.stok || 0;
    document.getElementById('infoStok').style.display = opt.value ? 'block' : 'none';
    document.getElementById('jumlah').max = opt.dataset.stok || 0;
    hitungTotal();
}
</script>

        </div>
    </main>
</div>
<script src="main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
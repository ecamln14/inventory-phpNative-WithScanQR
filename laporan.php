<?php
require_once 'auth.php';
require_once 'database.php';
$conn = getConnection();

$tipe      = isset($_GET['tipe']) ? $conn->real_escape_string($_GET['tipe']) : '';
$dari      = isset($_GET['dari']) && $_GET['dari'] ? $_GET['dari'] : date('Y-m-01');
$sampai    = isset($_GET['sampai']) && $_GET['sampai'] ? $_GET['sampai'] : date('Y-m-d');
$barang_id = isset($_GET['barang_id']) ? (int)$_GET['barang_id'] : 0;

$where = "WHERE t.tanggal BETWEEN '$dari' AND '$sampai'";
if ($tipe === 'masuk' || $tipe === 'keluar') $where .= " AND t.tipe='$tipe'";
if ($barang_id > 0) $where .= " AND t.barang_id=$barang_id";

$laporan = $conn->query("SELECT t.*,b.nama_barang,b.kode_barang,b.satuan,k.nama_kategori FROM transaksi t JOIN barang b ON t.barang_id=b.id LEFT JOIN kategori k ON b.kategori_id=k.id $where ORDER BY t.tanggal DESC,t.created_at DESC");
$summary = $conn->query("SELECT SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE 0 END) as total_masuk, SUM(CASE WHEN tipe='keluar' THEN jumlah ELSE 0 END) as total_keluar, SUM(CASE WHEN tipe='keluar' THEN total_harga ELSE 0 END) as nilai_keluar, COUNT(*) as total_trx FROM transaksi t $where")->fetch_assoc();
$barangList = $conn->query("SELECT id,nama_barang FROM barang ORDER BY nama_barang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan</title>
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
            <a href="transaksi_keluar.php" class="nav-item"><i class="fa-solid fa-arrow-up"></i> Barang Keluar</a>
            <div class="nav-label">Laporan</div>
            <a href="laporan.php" class="nav-item active"><i class="fa-solid fa-chart-bar"></i> Laporan</a>
            <div class="nav-label">Akun</div>
            <a href="logout.php" class="nav-item" style="color:#f87171"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">Laporan Transaksi</h1>
            <div class="topbar-right">
                <i class="fa-solid fa-user" style="margin-right:6px"></i>
                <?= htmlspecialchars($_SESSION['nama']) ?> &nbsp;|&nbsp;
                <i class="fa-regular fa-calendar"></i> <?= date('d F Y') ?>
            </div>
        </div>
        <div class="content-area">

<!-- Filter -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-filter"></i> Filter</span></div>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group">
                <label>Dari</label>
                <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select name="tipe" class="form-control">
                    <option value="" <?= $tipe===''?'selected':'' ?>>Semua</option>
                    <option value="masuk"  <?= $tipe==='masuk'?'selected':'' ?>>Masuk</option>
                    <option value="keluar" <?= $tipe==='keluar'?'selected':'' ?>>Keluar</option>
                </select>
            </div>
            <div class="form-group">
                <label>Barang</label>
                <select name="barang_id" class="form-control" style="min-width:180px">
                    <option value="">Semua Barang</option>
                    <?php while($b=$barangList->fetch_assoc()): ?>
                    <option value="<?= $b['id'] ?>" <?= $barang_id==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['nama_barang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Tampilkan</button>
                <a href="laporan.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-info"><div class="label">Total Transaksi</div><div class="value"><?= $summary['total_trx'] ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-arrow-down"></i></div>
        <div class="stat-info"><div class="label">Total Masuk</div><div class="value"><?= $summary['total_masuk']??0 ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-arrow-up"></i></div>
        <div class="stat-info"><div class="label">Total Keluar</div><div class="value"><?= $summary['total_keluar']??0 ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fa-solid fa-money-bill"></i></div>
        <div class="stat-info"><div class="label">Nilai Keluar</div><div class="value" style="font-size:15px">Rp <?= number_format($summary['nilai_keluar']??0,0,',','.') ?></div></div>
    </div>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Detail Transaksi</span>
        <span style="font-size:13px;color:var(--gray)"><?= $laporan->num_rows ?> data</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>No. Transaksi</th><th>Tanggal</th><th>Tipe</th><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Jumlah</th><th>Satuan</th><th>Harga Satuan</th><th>Total</th><th>Keterangan</th></tr>
            </thead>
            <tbody>
                <?php $no=1; $grand=0; while($r=$laporan->fetch_assoc()): $grand+=$r['total_harga']; ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td style="font-size:12px;font-weight:700"><?= $r['no_transaksi'] ?></td>
                    <td><?= date('d/m/Y',strtotime($r['tanggal'])) ?></td>
                    <td><?= $r['tipe']==='masuk' ? '<span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> Masuk</span>' : '<span class="badge badge-danger"><i class="fa-solid fa-arrow-up"></i> Keluar</span>' ?></td>
                    <td><code style="background:var(--gray-light);padding:2px 6px;border-radius:4px;font-size:12px"><?= $r['kode_barang'] ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['nama_barang']) ?></td>
                    <td><?= htmlspecialchars($r['nama_kategori']??'-') ?></td>
                    <td style="font-weight:700;color:<?= $r['tipe']==='masuk'?'var(--success)':'var(--danger)' ?>"><?= $r['tipe']==='masuk'?'+':'-' ?><?= $r['jumlah'] ?></td>
                    <td><?= $r['satuan'] ?></td>
                    <td>Rp <?= number_format($r['harga_satuan'],0,',','.') ?></td>
                    <td style="font-weight:600">Rp <?= number_format($r['total_harga'],0,',','.') ?></td>
                    <td style="color:var(--gray)"><?= htmlspecialchars($r['keterangan'])??'-' ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($laporan->num_rows > 0): ?>
                <tr style="background:var(--gray-light);font-weight:700">
                    <td colspan="10" style="text-align:right;padding:12px 16px">Grand Total:</td>
                    <td style="padding:12px 16px">Rp <?= number_format($grand,0,',','.') ?></td>
                    <td></td>
                </tr>
                <?php else: ?>
                <tr><td colspan="12" style="text-align:center;padding:40px;color:var(--gray)">Tidak ada data</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

        </div>
    </main>
</div>
<script src="main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
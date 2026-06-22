<?php
require_once 'auth.php';
require_once 'database.php';
$pageTitle = 'Dashboard';
$conn = getConnection();

$totalBarang     = $conn->query("SELECT COUNT(*) as c FROM barang")->fetch_assoc()['c'];
$masukBulanIni   = $conn->query("SELECT COALESCE(SUM(jumlah),0) as c FROM transaksi WHERE tipe='masuk' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetch_assoc()['c'];
$keluarBulanIni  = $conn->query("SELECT COALESCE(SUM(jumlah),0) as c FROM transaksi WHERE tipe='keluar' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetch_assoc()['c'];
$trxHariIni      = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE tanggal=CURDATE()")->fetch_assoc()['c'];
$stokMinimum     = $conn->query("SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE b.stok <= b.stok_minimum ORDER BY b.stok ASC LIMIT 5");
$trxTerbaru      = $conn->query("SELECT t.*, b.nama_barang FROM transaksi t JOIN barang b ON t.barang_id=b.id ORDER BY t.created_at DESC LIMIT 7");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><i class="fa-solid fa-boxes-stacked"></i><span>TrackInventori</span></div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <div class="nav-label">Master Data</div>
            <a href="barang.php" class="nav-item"><i class="fa-solid fa-box"></i> Data Barang</a>
            <a href="kategori.php" class="nav-item"><i class="fa-solid fa-tags"></i> Kategori</a>
            <div class="nav-label">Transaksi</div>
            <a href="kasir.php" class="nav-item">
    <i class="fa-solid fa-cash-register"></i> Kasir POS </a>
            <a href="transaksi_masuk.php" class="nav-item"><i class="fa-solid fa-arrow-down"></i> Barang Masuk</a>
            <a href="transaksi_keluar.php" class="nav-item"><i class="fa-solid fa-arrow-up"></i> Barang Keluar</a>
            <div class="nav-label">Laporan</div>
            <a href="laporan.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Laporan</a>
            <div class="nav-label">Akun</div>
            <a href="logout.php" class="nav-item" style="color:#f87171"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">Dashboard</h1>
            <div class="topbar-right">
                <i class="fa-solid fa-user" style="margin-right:6px"></i>
                <?= htmlspecialchars($_SESSION['nama']) ?> &nbsp;|&nbsp;
                <i class="fa-regular fa-calendar"></i> <?= date('d F Y') ?>
            </div>
        </div>
        <div class="content-area">

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-info"><div class="label">Total Barang</div><div class="value"><?= $totalBarang ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-arrow-down"></i></div>
                    <div class="stat-info"><div class="label">Masuk Bulan Ini</div><div class="value"><?= $masukBulanIni ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-arrow-up"></i></div>
                    <div class="stat-info"><div class="label">Keluar Bulan Ini</div><div class="value"><?= $keluarBulanIni ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-info"><div class="label">Transaksi Hari Ini</div><div class="value"><?= $trxHariIni ?></div></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)"></i> Stok Menipis</span>
                        <a href="barang.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Barang</th><th>Stok</th><th>Min</th></tr></thead>
                            <tbody>
                                <?php if ($stokMinimum->num_rows === 0): ?>
                                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray)">✓ Semua stok aman</td></tr>
                                <?php else: ?>
                                <?php while ($r = $stokMinimum->fetch_assoc()): ?>
                                <tr>
                                    <td><div style="font-weight:600"><?= htmlspecialchars($r['nama_barang']) ?></div><div style="font-size:12px;color:var(--gray)"><?= $r['kode_barang'] ?></div></td>
                                    <td class="stok-low"><?= $r['stok'] ?></td>
                                    <td><?= $r['stok_minimum'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Transaksi Terbaru</span>
                        <a href="laporan.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>No. Transaksi</th><th>Barang</th><th>Tipe</th><th>Jml</th></tr></thead>
                            <tbody>
                                <?php if ($trxTerbaru->num_rows === 0): ?>
                                <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--gray)">Belum ada transaksi</td></tr>
                                <?php else: ?>
                                <?php while ($r = $trxTerbaru->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-size:12px;font-weight:600"><?= $r['no_transaksi'] ?></td>
                                    <td><?= htmlspecialchars($r['nama_barang']) ?></td>
                                    <td><?= $r['tipe']==='masuk' ? '<span class="badge badge-success">Masuk</span>' : '<span class="badge badge-danger">Keluar</span>' ?></td>
                                    <td><?= $r['jumlah'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>
<script src="main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
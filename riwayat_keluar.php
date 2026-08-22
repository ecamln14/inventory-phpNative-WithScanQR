<?php
require_once 'auth.php';
require_once 'database.php';
$conn = getConnection();
$pesan = '';

// Fitur Hapus Transaksi (Mengembalikan Stok)
if (isset($_GET['hapus'])) {
    $no_trx = $conn->real_escape_string($_GET['hapus']);
    
    // Ambil semua item dalam transaksi ini untuk dikembalikan stoknya
    $items = $conn->query("SELECT barang_id, jumlah FROM transaksi WHERE no_transaksi='$no_trx' AND tipe='keluar'");
    
    $conn->begin_transaction();
    try {
        while ($item = $items->fetch_assoc()) {
            $conn->query("UPDATE barang SET stok = stok + {$item['jumlah']} WHERE id = {$item['barang_id']}");
        }
        // Hapus semua baris transaksi dengan nomor ini
        $conn->query("DELETE FROM transaksi WHERE no_transaksi='$no_trx' AND tipe='keluar'");
        $conn->commit();
        $pesan = ['type'=>'success', 'text'=>"Transaksi $no_trx berhasil dihapus dan stok telah dikembalikan."];
    } catch (Exception $e) {
        $conn->rollback();
        $pesan = ['type'=>'danger', 'text'=>'Gagal menghapus transaksi: '.$e->getMessage()];
    }
}

// Ambil data riwayat dan kelompokkan berdasarkan no_transaksi
$riwayat_raw = $conn->query("
    SELECT t.*, b.nama_barang, b.kode_barang 
    FROM transaksi t 
    JOIN barang b ON t.barang_id = b.id 
    WHERE t.tipe = 'keluar' 
    ORDER BY t.created_at DESC
");

$grouped_riwayat = [];
while ($row = $riwayat_raw->fetch_assoc()) {
    $no = $row['no_transaksi'];
    if (!isset($grouped_riwayat[$no])) {
        $grouped_riwayat[$no] = [
            'tanggal' => $row['tanggal'],
            'keterangan' => $row['keterangan'],
            'grand_total' => 0,
            'items' => []
        ];
    }
    $grouped_riwayat[$no]['items'][] = $row;
    $grouped_riwayat[$no]['grand_total'] += $row['total_harga'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Barang Keluar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .trx-group {
            margin-bottom: 24px;
            border: 1px solid var(--gray-light, #e5e7eb);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .trx-header {
            background: var(--gray-lighter, #f9fafb);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-light, #e5e7eb);
        }
        .trx-header-info {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--gray, #6b7280);
        }
        .trx-header-info strong {
            color: var(--dark, #1f2937);
            font-size: 14px;
        }
        .trx-table {
            width: 100%;
            border-collapse: collapse;
        }
        .trx-table th, .trx-table td {
            padding: 10px 16px;
            text-align: left;
            font-size: 13px;
            border-bottom: 1px solid var(--gray-light, #f3f4f6);
        }
        .trx-table th {
            background: #fff;
            font-weight: 600;
            color: var(--gray, #6b7280);
        }
        .trx-table tr:last-child td {
            border-bottom: none;
        }
        .trx-footer {
            padding: 12px 16px;
            background: var(--gray-lighter, #f9fafb);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
        }
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
            <a href="transaksi_keluar.php" class="nav-item"><i class="fa-solid fa-arrow-up"></i> Barang Keluar</a>
            <div class="nav-label">Laporan</div>
            <a href="laporan.php" class="nav-item"><i class="fa-solid fa-chart-bar"></i> Laporan</a>
            <div class="nav-label">Akun</div>
            <a href="logout.php" class="nav-item" style="color:#f87171"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title">Riwayat Barang Keluar</h1>
            <div class="topbar-right">
                <a href="transaksi_keluar.php" class="btn" style="background:var(--primary, #3b82f6); color:#fff; text-decoration:none; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Input
                </a>
            </div>
        </div>
        <div class="content-area">

            <?php if ($pesan): ?>
            <div class="alert alert-<?= $pesan['type'] ?>" style="margin-bottom: 20px;">
                <i class="fa-solid fa-circle-check"></i> <?= $pesan['text'] ?>
            </div>
            <?php endif; ?>

            <?php if (empty($grouped_riwayat)): ?>
                <div class="card" style="text-align:center; padding: 40px 20px;">
                    <i class="fa-solid fa-inbox" style="font-size: 48px; color: var(--gray, #9ca3af); margin-bottom: 16px;"></i>
                    <p style="color: var(--gray, #6b7280); font-size: 15px;">Belum ada riwayat transaksi barang keluar.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_riwayat as $no_trx => $data): ?>
                <div class="trx-group">
                    <div class="trx-header">
                        <div class="trx-header-info">
                            <span><i class="fa-solid fa-receipt"></i> <strong><?= htmlspecialchars($no_trx) ?></strong></span>
                            <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($data['tanggal'])) ?></span>
                            <span><i class="fa-solid fa-box-open"></i> <?= count($data['items']) ?> Item</span>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete('?hapus=<?= urlencode($no_trx) ?>', 'transaksi <?= htmlspecialchars($no_trx) ?>')">
                            <i class="fa-solid fa-trash"></i> Hapus Transaksi
                        </button>
                    </div>
                    
                    <table class="trx-table">
                        <thead>
                            <tr>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th style="text-align:center">Jumlah</th>
                                <th style="text-align:right">Harga Satuan</th>
                                <th style="text-align:right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['kode_barang']) ?></td>
                                <td style="font-weight:500"><?= htmlspecialchars($item['nama_barang']) ?></td>
                                <td style="text-align:center"><span class="badge badge-danger">-<?= $item['jumlah'] ?></span></td>
                                <td style="text-align:right">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                                <td style="text-align:right; font-weight:600">Rp <?= number_format($item['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="trx-footer">
                        <span style="color:var(--gray, #6b7280); font-weight:400; font-size:13px;">
                            Ket: <?= htmlspecialchars($data['keterangan'] ?: '-') ?>
                        </span>
                        <span style="color:var(--danger, #ef4444);">
                            Grand Total: Rp <?= number_format($data['grand_total'], 0, ',', '.') ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function confirmDelete(url, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus ${name}? Stok barang akan dikembalikan secara otomatis.`)) {
        window.location.href = url;
    }
}
</script>
</body>
</html>
<?php $conn->close(); ?>
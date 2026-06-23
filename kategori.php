<?php
require_once 'auth.php';
require_once 'database.php';
$conn = getConnection();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $nama = $conn->real_escape_string(trim($_POST['nama_kategori']));
    $desk = $conn->real_escape_string(trim($_POST['deskripsi']));

    if ($action === 'tambah') {
        if ($conn->query("INSERT INTO kategori (nama_kategori,deskripsi) VALUES ('$nama','$desk')"))
            $pesan = ['type'=>'success','text'=>'Kategori berhasil ditambahkan!'];
        else
            $pesan = ['type'=>'danger','text'=>'Gagal: '.$conn->error];
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        if ($conn->query("UPDATE kategori SET nama_kategori='$nama',deskripsi='$desk' WHERE id=$id"))
            $pesan = ['type'=>'success','text'=>'Kategori berhasil diperbarui!'];
        else
            $pesan = ['type'=>'danger','text'=>'Gagal: '.$conn->error];
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($conn->query("DELETE FROM kategori WHERE id=$id"))
        $pesan = ['type'=>'success','text'=>'Kategori berhasil dihapus!'];
    else
        $pesan = ['type'=>'danger','text'=>'Gagal menghapus!'];
}

$editData = isset($_GET['edit']) ? $conn->query("SELECT * FROM kategori WHERE id=".(int)$_GET['edit'])->fetch_assoc() : null;
$list = $conn->query("SELECT k.*, COUNT(b.id) as jml FROM kategori k LEFT JOIN barang b ON k.id=b.kategori_id GROUP BY k.id ORDER BY k.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori</title>
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
            <a href="kategori.php" class="nav-item active"><i class="fa-solid fa-tags"></i> Kategori</a>
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
            <h1 class="page-title">Kategori</h1>
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

<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
    <button class="btn btn-primary" onclick="openModal('modalTambah')"><i class="fa-solid fa-plus"></i> Tambah Kategori</button>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Daftar Kategori</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th>Jumlah Barang</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php $no=1; while($r=$list->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['nama_kategori']) ?></td>
                    <td style="color:var(--gray)"><?= htmlspecialchars($r['deskripsi']) ?: '-' ?></td>
                    <td><span class="badge badge-primary"><?= $r['jml'] ?> barang</span></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="?edit=<?= $r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete('?hapus=<?= $r['id'] ?>','<?= htmlspecialchars($r['nama_kategori']) ?>')"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-backdrop" id="modalTambah">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Tambah Kategori</span>
            <button class="modal-close" onclick="closeModal('modalTambah')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:12px">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<?php if ($editData): ?>
<div class="modal-backdrop show">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Kategori</span>
            <a href="kategori.php" class="modal-close"><i class="fa-solid fa-xmark"></i></a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:12px">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" value="<?= htmlspecialchars($editData['nama_kategori']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($editData['deskripsi']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a href="kategori.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

        </div>
    </main>
</div>
<script src="main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
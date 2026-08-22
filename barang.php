<?php
require_once 'auth.php';
require_once 'database.php';
$pageTitle = 'Data Barang';
$conn = getConnection();
$pesan = '';

// Folder tempat menyimpan gambar barang
define('UPLOAD_DIR', __DIR__ . '/uploads/barang/');
define('UPLOAD_URL', 'uploads/barang/');
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

function generateKode($conn) {
    $r = $conn->query("SELECT kode_barang FROM barang ORDER BY id DESC LIMIT 1");
    if ($r->num_rows === 0) return 'BRG001';
    $last = $r->fetch_assoc()['kode_barang'];
    return 'BRG' . str_pad((int)substr($last,3)+1, 3, '0', STR_PAD_LEFT);
}

/**
 * Proses upload gambar barang.
 * Mengembalikan nama file baru jika berhasil, null jika tidak ada file diupload,
 * atau melempar Exception jika file tidak valid.
 */
function uploadGambarBarang($fileField = 'gambar') {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada file yang diupload
    }
    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload gambar gagal (kode error: '.$file['error'].')');
    }

    $allowedExt  = ['jpg','jpeg','png','webp'];
    $allowedMime = ['image/jpeg','image/png','image/webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt) || !in_array($file['type'], $allowedMime)) {
        throw new Exception('Format gambar harus JPG, PNG, atau WEBP.');
    }
    if ($file['size'] > 2 * 1024 * 1024) { // maks 2MB
        throw new Exception('Ukuran gambar maksimal 2MB.');
    }

    $newName = 'brg_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName)) {
        throw new Exception('Gagal menyimpan file gambar ke server.');
    }
    return $newName;
}

function hapusFileGambar($namaFile) {
    if (!empty($namaFile) && file_exists(UPLOAD_DIR . $namaFile)) {
        @unlink(UPLOAD_DIR . $namaFile);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'tambah') {
        $kode  = $conn->real_escape_string(trim($_POST['kode_barang']));
        $nama  = $conn->real_escape_string(trim($_POST['nama_barang']));
        $katid = (int)$_POST['kategori_id'];
        $sat   = $conn->real_escape_string($_POST['satuan']);
        $hb    = (float)$_POST['harga_beli'];
        $hj    = (float)$_POST['harga_jual'];
        $stok  = (int)$_POST['stok'];
        $smin  = (int)$_POST['stok_minimum'];
        $desk  = $conn->real_escape_string(trim($_POST['deskripsi']));

        try {
            $gambar = uploadGambarBarang('gambar');
            $gambarSql = $gambar ? "'".$conn->real_escape_string($gambar)."'" : "NULL";

            if ($conn->query("INSERT INTO barang (kode_barang,nama_barang,kategori_id,satuan,harga_beli,harga_jual,stok,stok_minimum,deskripsi,gambar) VALUES ('$kode','$nama',$katid,'$sat',$hb,$hj,$stok,$smin,'$desk',$gambarSql)"))
                $pesan = ['type'=>'success','text'=>'Barang berhasil ditambahkan!'];
            else {
                if ($gambar) hapusFileGambar($gambar);
                $pesan = ['type'=>'danger','text'=>'Gagal: '.$conn->error];
            }
        } catch (Exception $e) {
            $pesan = ['type'=>'danger','text'=>$e->getMessage()];
        }
    }

    if ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $nama  = $conn->real_escape_string(trim($_POST['nama_barang']));
        $katid = (int)$_POST['kategori_id'];
        $sat   = $conn->real_escape_string($_POST['satuan']);
        $hb    = (float)$_POST['harga_beli'];
        $hj    = (float)$_POST['harga_jual'];
        $stok  = (int)$_POST['stok']; // <-- DITAMBAHKAN: Ambil nilai stok dari form
        $smin  = (int)$_POST['stok_minimum'];
        $desk  = $conn->real_escape_string(trim($_POST['deskripsi']));

        try {
            $gambarBaru = uploadGambarBarang('gambar');
            $hapusGambarLama = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] === '1';

            $row = $conn->query("SELECT gambar FROM barang WHERE id=$id")->fetch_assoc();
            $gambarLama = $row['gambar'] ?? null;

            $setGambar = '';
            if ($gambarBaru) {
                // ada gambar baru diupload -> hapus gambar lama, pakai yang baru
                if ($gambarLama) hapusFileGambar($gambarLama);
                $setGambar = ", gambar='".$conn->real_escape_string($gambarBaru)."'";
            } elseif ($hapusGambarLama) {
                // user memilih hapus gambar tanpa upload gambar baru
                if ($gambarLama) hapusFileGambar($gambarLama);
                $setGambar = ", gambar=NULL";
            }

            // <-- DITAMBAHKAN: stok=$stok pada query UPDATE
            if ($conn->query("UPDATE barang SET nama_barang='$nama',kategori_id=$katid,satuan='$sat',harga_beli=$hb,harga_jual=$hj,stok=$stok,stok_minimum=$smin,deskripsi='$desk'$setGambar WHERE id=$id"))
                $pesan = ['type'=>'success','text'=>'Barang berhasil diperbarui!'];
            else {
                if ($gambarBaru) hapusFileGambar($gambarBaru);
                $pesan = ['type'=>'danger','text'=>'Gagal: '.$conn->error];
            }
        } catch (Exception $e) {
            $pesan = ['type'=>'danger','text'=>$e->getMessage()];
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $row = $conn->query("SELECT gambar FROM barang WHERE id=$id")->fetch_assoc();
    if ($conn->query("DELETE FROM barang WHERE id=$id")) {
        if ($row && !empty($row['gambar'])) hapusFileGambar($row['gambar']);
        $pesan = ['type'=>'success','text'=>'Barang berhasil dihapus!'];
    } else
        $pesan = ['type'=>'danger','text'=>'Gagal menghapus!'];
}

$editData    = isset($_GET['edit']) ? $conn->query("SELECT * FROM barang WHERE id=".(int)$_GET['edit'])->fetch_assoc() : null;
$barangList  = $conn->query("SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id ORDER BY b.id DESC");
$kategoriList= $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
$newKode     = generateKode($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .thumb-barang {
            width: 42px; height: 42px; border-radius: 8px; object-fit: cover;
            border: 1px solid var(--gray-light, #e5e7eb); background: var(--gray-lighter, #f9fafb);
        }
        .thumb-barang-placeholder {
            width: 42px; height: 42px; border-radius: 8px;
            border: 1px solid var(--gray-light, #e5e7eb); background: var(--gray-lighter, #f9fafb);
            display: flex; align-items: center; justify-content: center; color: var(--gray, #9ca3af);
        }
        .upload-gambar-box {
            display: flex; align-items: center; gap: 12px;
            border: 1px dashed var(--gray-light, #d1d5db); border-radius: 10px;
            padding: 10px; margin-bottom: 12px;
        }
        .upload-preview {
            width: 64px; height: 64px; border-radius: 10px; object-fit: cover;
            background: var(--gray-lighter, #f9fafb); border: 1px solid var(--gray-light, #e5e7eb);
            flex-shrink: 0;
        }
        .upload-preview-placeholder {
            width: 64px; height: 64px; border-radius: 10px;
            background: var(--gray-lighter, #f9fafb); border: 1px solid var(--gray-light, #e5e7eb);
            display: flex; align-items: center; justify-content: center; color: var(--gray, #9ca3af);
            font-size: 22px; flex-shrink: 0;
        }
        .upload-gambar-info { flex: 1; }
        .upload-gambar-info input[type="file"] { font-size: 12.5px; }
        .upload-gambar-info small { display:block; color: var(--gray, #6b7280); font-size: 11px; margin-top: 4px; }
        .link-hapus-gambar { font-size: 12px; color: var(--danger,#ef4444); cursor: pointer; margin-top: 4px; display: inline-block; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand"><i class="fa-solid fa-boxes-stacked"></i><span>TrackInventori</span></div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <div class="nav-label">Master Data</div>
            <a href="barang.php" class="nav-item active"><i class="fa-solid fa-box"></i> Data Barang</a>
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
            <h1 class="page-title">Data Barang</h1>
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

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Cari barang..." style="max-width:260px">
    <button class="btn btn-primary" onclick="openModal('modalTambah')"><i class="fa-solid fa-plus"></i> Tambah Barang</button>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Daftar Barang</span>
        <span style="font-size:13px;color:var(--gray)"><?= $barangList->num_rows ?> barang</span>
    </div>
    <div class="table-wrap">
        <table id="mainTable">
            <thead>
                <tr><th>#</th><th>Gambar</th><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th><th>Harga Beli</th><th>Harga Jual</th><th>Stok</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php $no=1; while($r=$barangList->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <?php if (!empty($r['gambar'])): ?>
                            <img class="thumb-barang" src="<?= UPLOAD_URL . htmlspecialchars($r['gambar']) ?>" alt="<?= htmlspecialchars($r['nama_barang']) ?>"
                                 onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('div'),{className:'thumb-barang-placeholder',innerHTML:'<i class=\'fa-solid fa-image\'></i>'}));">
                        <?php else: ?>
                            <div class="thumb-barang-placeholder"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><code style="background:var(--gray-light);padding:2px 6px;border-radius:4px"><?= $r['kode_barang'] ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($r['nama_barang']) ?></td>
                    <td><?= $r['nama_kategori'] ?? '-' ?></td>
                    <td><?= $r['satuan'] ?></td>
                    <td>Rp <?= number_format($r['harga_beli'],0,',','.') ?></td>
                    <td>Rp <?= number_format($r['harga_jual'],0,',','.') ?></td>
                    <td class="<?= $r['stok'] <= $r['stok_minimum'] ? 'stok-low' : '' ?>"><?= $r['stok'] ?></td>
                    <td>
                        <?php if ($r['stok']==0): ?><span class="badge badge-danger">Habis</span>
                        <?php elseif ($r['stok']<=$r['stok_minimum']): ?><span class="badge badge-warning">Menipis</span>
                        <?php else: ?><span class="badge badge-success">Aman</span><?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="?edit=<?= $r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete('?hapus=<?= $r['id'] ?>','<?= htmlspecialchars($r['nama_barang']) ?>')"><i class="fa-solid fa-trash"></i></button>
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
            <span class="modal-title">Tambah Barang</span>
            <button class="modal-close" onclick="closeModal('modalTambah')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="tambah">
            <div class="modal-body">
                <div class="upload-gambar-box">
                    <div class="upload-preview-placeholder" id="previewTambah"><i class="fa-solid fa-image"></i></div>
                    <div class="upload-gambar-info">
                        <label>Foto Barang</label>
                        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp" onchange="previewGambar(this,'previewTambah')">
                        <small>JPG/PNG/WEBP, maks 2MB. Opsional.</small>
                    </div>
                </div>
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                    <div class="form-group">
                        <label>Kode Barang</label>
                        <input type="text" name="kode_barang" class="form-control" value="<?= $newKode ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <select name="satuan" class="form-control">
                            <?php foreach(['pcs','unit','box','rim','lusin','kg','liter','galon','pak'] as $s): ?>
                            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Nama Barang</label>
                    <input type="text" name="nama_barang" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Kategori</label>
                    <select name="kategori_id" class="form-control">
                        <option value="">-- Pilih Kategori --</option>
                        <?php $kategoriList->data_seek(0); while($k=$kategoriList->fetch_assoc()): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                    <div class="form-group"><label>Harga Beli</label><input type="number" name="harga_beli" class="form-control" value="0"></div>
                    <div class="form-group"><label>Harga Jual</label><input type="number" name="harga_jual" class="form-control" value="0"></div>
                </div>
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                    <div class="form-group"><label>Stok Awal</label><input type="number" name="stok" class="form-control" value="0"></div>
                    <div class="form-group"><label>Stok Minimum</label><input type="number" name="stok_minimum" class="form-control" value="5"></div>
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
<div class="modal-backdrop show" id="modalEdit">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Edit Barang</span>
            <a href="barang.php" class="modal-close"><i class="fa-solid fa-xmark"></i></a>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <input type="hidden" name="hapus_gambar" id="hapusGambarFlag" value="0">
            <div class="modal-body">
                <div class="upload-gambar-box">
                    <?php if (!empty($editData['gambar'])): ?>
                        <img class="upload-preview" id="previewEdit" src="<?= UPLOAD_URL . htmlspecialchars($editData['gambar']) ?>" alt="preview">
                    <?php else: ?>
                        <div class="upload-preview-placeholder" id="previewEdit"><i class="fa-solid fa-image"></i></div>
                    <?php endif; ?>
                    <div class="upload-gambar-info">
                        <label>Foto Barang</label>
                        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp" onchange="previewGambar(this,'previewEdit')">
                        <small>Kosongkan jika tidak ingin mengganti foto. JPG/PNG/WEBP, maks 2MB.</small>
                        <?php if (!empty($editData['gambar'])): ?>
                        <span class="link-hapus-gambar" onclick="hapusGambarEdit()"><i class="fa-solid fa-trash"></i> Hapus foto saat ini</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Kode Barang</label>
                    <input type="text" class="form-control" value="<?= $editData['kode_barang'] ?>" disabled>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label>Nama Barang</label>
                    <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($editData['nama_barang']) ?>" required>
                </div>
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            <?php $kategoriList->data_seek(0); while($k=$kategoriList->fetch_assoc()): ?>
                            <option value="<?= $k['id'] ?>" <?= $k['id']==$editData['kategori_id']?'selected':'' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <select name="satuan" class="form-control">
                            <?php foreach(['pcs','unit','box','rim','lusin','kg','liter','galon','pak'] as $s): ?>
                            <option value="<?= $s ?>" <?= $editData['satuan']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                    <div class="form-group"><label>Harga Beli</label><input type="number" name="harga_beli" class="form-control" value="<?= $editData['harga_beli'] ?>"></div>
                    <div class="form-group"><label>Harga Jual</label><input type="number" name="harga_jual" class="form-control" value="<?= $editData['harga_jual'] ?>"></div>
                </div>
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                    <!-- DIPERBAIKI: Input stok sekarang bisa diedit (type="number" dan ada name="stok") -->
                    <div class="form-group"><label>Stok Saat Ini</label><input type="number" name="stok" class="form-control" value="<?= $editData['stok'] ?>"><span class="form-hint">Bisa diubah langsung</span></div>
                    <div class="form-group"><label>Stok Minimum</label><input type="number" name="stok_minimum" class="form-control" value="<?= $editData['stok_minimum'] ?>"></div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($editData['deskripsi']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a href="barang.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk"></i> Update</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function previewGambar(input, previewId) {
    const el = document.getElementById(previewId);
    if (!input.files || !input.files[0]) return;
    const url = URL.createObjectURL(input.files[0]);
    if (el.tagName === 'IMG') {
        el.src = url;
    } else {
        const img = document.createElement('img');
        img.className = 'upload-preview';
        img.id = previewId;
        img.src = url;
        el.replaceWith(img);
    }
    // batalkan penghapusan gambar jika sebelumnya user klik "hapus foto"
    const flag = document.getElementById('hapusGambarFlag');
    if (flag) flag.value = '0';
}

function hapusGambarEdit() {
    if (!confirm('Hapus foto barang ini?')) return;
    document.getElementById('hapusGambarFlag').value = '1';
    const el = document.getElementById('previewEdit');
    const placeholder = document.createElement('div');
    placeholder.className = 'upload-preview-placeholder';
    placeholder.id = 'previewEdit';
    placeholder.innerHTML = '<i class="fa-solid fa-image"></i>';
    el.replaceWith(placeholder);
}
</script>

        </div>
    </main>
</div>
<script src="main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
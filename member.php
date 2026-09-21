<?php
require_once 'auth.php';
wajib_admin();
require_once 'database.php';

$conn   = getConnection();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$userId = (int)$_SESSION['user_id'];

function rp($n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function flash(string $type, string $text): void {
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

/* ---------------- AKSI (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $back = 'member.php';

    if (!csrf_valid()) {
        flash('error', 'Sesi form kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        header("Location: $back");
        exit;
    }

    try {
        if ($aksi === 'tambah') {
            $nama = trim($_POST['nama'] ?? '');
            $hp   = trim($_POST['no_hp'] ?? '');
            if ($nama === '' || mb_strlen($nama) > 100) throw new RuntimeException('Nama wajib diisi (maksimal 100 karakter).');
            if ($hp !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $hp)) throw new RuntimeException('Format nomor HP tidak valid.');

            $conn->begin_transaction();
            $tmp  = 'TMP' . bin2hex(random_bytes(6));
            $hpDb = $hp === '' ? null : $hp;
            $st = $conn->prepare("INSERT INTO member (kode_member, nama, no_hp) VALUES (?, ?, ?)");
            $st->bind_param('sss', $tmp, $nama, $hpDb);
            $st->execute();
            $newId = $conn->insert_id;
            $kode  = sprintf('MBR%05d', $newId);
            $st = $conn->prepare("UPDATE member SET kode_member = ? WHERE id = ?");
            $st->bind_param('si', $kode, $newId);
            $st->execute();
            $conn->commit();
            flash('ok', "Member $nama ditambahkan dengan kode $kode.");

        } elseif ($aksi === 'topup') {
            $id     = (int)($_POST['member_id'] ?? 0);
            $jumlah = (int)($_POST['jumlah'] ?? 0);
            if ($jumlah < 1000)      throw new RuntimeException('Minimal top-up Rp 1.000.');
            if ($jumlah > 10000000)  throw new RuntimeException('Maksimal top-up sekali proses Rp 10.000.000.');

            $conn->begin_transaction();
            $st = $conn->prepare("SELECT nama, saldo, aktif FROM member WHERE id = ? FOR UPDATE");
            $st->bind_param('i', $id);
            $st->execute();
            $m = $st->get_result()->fetch_assoc();
            if (!$m)                    throw new RuntimeException('Member tidak ditemukan.');
            if ((int)$m['aktif'] !== 1) throw new RuntimeException('Member nonaktif tidak bisa top-up. Aktifkan dulu.');

            $baru = round((float)$m['saldo'] + $jumlah, 2);
            $j    = (float)$jumlah;
            $st = $conn->prepare("UPDATE member SET saldo = ? WHERE id = ?");
            $st->bind_param('di', $baru, $id);
            $st->execute();

            $ket = 'Top-up saldo';
            $st = $conn->prepare("
                INSERT INTO saldo_log (member_id, tipe, jumlah, saldo_sesudah, keterangan, user_id)
                VALUES (?, 'topup', ?, ?, ?, ?)
            ");
            $st->bind_param('iddsi', $id, $j, $baru, $ket, $userId);
            $st->execute();
            $conn->commit();
            flash('ok', 'Top-up ' . rp($jumlah) . ' untuk ' . $m['nama'] . ' berhasil. Saldo sekarang ' . rp($baru) . '.');

        } elseif ($aksi === 'toggle') {
            $id = (int)($_POST['member_id'] ?? 0);
            $st = $conn->prepare("UPDATE member SET aktif = 1 - aktif WHERE id = ?");
            $st->bind_param('i', $id);
            $st->execute();
            flash('ok', 'Status member diperbarui.');
        }
    } catch (RuntimeException $e) {
        $conn->rollback();
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('member.php: ' . $e->getMessage());
        flash('error', 'Terjadi kesalahan di server. Perubahan dibatalkan.');
    }

    header("Location: $back");
    exit;
}

/* ---------------- DATA (GET) ---------------- */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$cari = trim($_GET['cari'] ?? '');
if ($cari !== '') {
    $like = '%' . addcslashes($cari, '%_\\') . '%';
    $st = $conn->prepare("SELECT * FROM member WHERE kode_member = ? OR nama LIKE ? OR no_hp LIKE ? ORDER BY nama LIMIT 200");
    $st->bind_param('sss', $cari, $like, $like);
} else {
    $st = $conn->prepare("SELECT * FROM member ORDER BY created_at DESC, id DESC LIMIT 200");
}
$st->execute();
$members = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$totalSaldo = 0;
foreach ($members as $m) $totalSaldo += (float)$m['saldo'];

$riwayatId = (int)($_GET['riwayat'] ?? 0);
$riwayat = [];
$riwayatNama = '';
if ($riwayatId > 0) {
    $st = $conn->prepare("SELECT nama FROM member WHERE id = ?");
    $st->bind_param('i', $riwayatId);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if ($r) {
        $riwayatNama = $r['nama'];
        $st = $conn->prepare("
            SELECT l.tipe, l.jumlah, l.saldo_sesudah, l.no_transaksi, l.keterangan, l.created_at, u.nama AS petugas
            FROM saldo_log l
            LEFT JOIN users u ON u.id = l.user_id
            WHERE l.member_id = ?
            ORDER BY l.id DESC
            LIMIT 20
        ");
        $st->bind_param('i', $riwayatId);
        $st->execute();
        $riwayat = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member &amp; Saldo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #1a1a2e; }

        .sidebar { position: fixed; top: 0; left: 0; width: 220px; height: 100vh; background: #1a1a2e; padding: 24px 0; z-index: 100; overflow-y: auto; }
        .sidebar-brand { color: white; font-size: 16px; font-weight: 700; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding: 16px 0; }
        .nav-label { font-size: 10px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px; padding: 10px 20px 4px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; }
        .nav-item:hover { background: rgba(255,255,255,0.08); color: white; }
        .nav-item.active { background: #4f46e5; color: white; }

        .main-wrap { margin-left: 220px; padding: 24px; min-height: 100vh; }
        h2 { font-size: 22px; margin-bottom: 4px; }
        .sub { color: #666; font-size: 14px; margin-bottom: 20px; }

        .panel { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .panel h3 { font-size: 15px; color: #555; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #eee; }

        .top-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1100px; }
        .stat { display: flex; gap: 28px; margin-bottom: 20px; max-width: 1100px; }
        .stat div { background: white; border-radius: 12px; padding: 14px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stat b { display: block; font-size: 20px; color: #4f46e5; }
        .stat span { font-size: 12px; color: #777; }

        label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
        input[type=text], input[type=number], select {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; margin-bottom: 12px; font-family: inherit;
        }
        input:focus, select:focus { outline: none; border-color: #4f46e5; }
        .btn { padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #4f46e5; color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-green { background: #16a34a; color: white; }
        .btn-green:hover { background: #15803d; }
        .btn-small { padding: 5px 10px; font-size: 12px; border-radius: 6px; }
        .btn-ghost { background: #f1f1f1; color: #333; }
        .btn-warn { background: #fee2e2; color: #dc2626; }
        .btn:focus-visible, a:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; }

        .notice { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; font-weight: 600; max-width: 1100px; }
        .notice.ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .notice.error { background: #fff5f5; border: 1px solid #fecaca; color: #dc2626; }

        .search-row { display: flex; gap: 8px; margin-bottom: 14px; }
        .search-row input { margin-bottom: 0; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; font-size: 12px; color: #777; padding: 8px 10px; border-bottom: 1px solid #eee; }
        td { padding: 10px; border-bottom: 1px solid #f3f3f3; vertical-align: middle; }
        td.num, th.num { text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge.on { background: #dcfce7; color: #15803d; }
        .badge.off { background: #f3f4f6; color: #6b7280; }
        .tipe-topup { color: #15803d; font-weight: 600; }
        .tipe-bayar { color: #dc2626; font-weight: 600; }
        .tipe-koreksi { color: #b45309; font-weight: 600; }
        .row-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .table-scroll { overflow-x: auto; }
        .empty { text-align: center; color: #aaa; padding: 26px 0; font-size: 14px; }
        form.inline { display: inline; }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main-wrap { margin-left: 0; }
            .top-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">📦 TrackInventori</div>
    <nav class="sidebar-nav">
        <div class="nav-label">Dashboard</div>
        <a href="index.php" class="nav-item">🏠 Dashboard</a>
        <div class="nav-label">Master Data</div>
        <a href="barang.php" class="nav-item">📦 Data Barang</a>
        <a href="kategori.php" class="nav-item">🏷️ Kategori</a>
        <a href="member.php" class="nav-item active">👥 Member &amp; Saldo</a>
        <div class="nav-label">Transaksi</div>
        <a href="kasir.php" class="nav-item">🏪 Kasir POS</a>
        <a href="transaksi_masuk.php" class="nav-item">⬇️ Barang Masuk</a>
        <a href="transaksi_keluar.php" class="nav-item">⬆️ Barang Keluar</a>
        <div class="nav-label">Laporan</div>
        <a href="laporan.php" class="nav-item">📊 Laporan</a>
        <div class="nav-label">Akun</div>
        <a href="logout.php" class="nav-item" style="color:#f87171">🚪 Logout</a>
    </nav>
</div>

<div class="main-wrap">
    <h2>👥 Member &amp; Saldo</h2>
    <p class="sub">Tambah member, isi saldo, dan lihat riwayatnya. Pembayaran pakai saldo dilakukan di halaman Kasir.</p>

    <?php if ($flash): ?>
        <div class="notice <?= $flash['type'] === 'ok' ? 'ok' : 'error' ?>"><?= h($flash['text']) ?></div>
    <?php endif; ?>

    <div class="stat">
        <div><b><?= count($members) ?></b><span><?= $cari !== '' ? 'Member ditemukan' : 'Member terdaftar' ?></span></div>
        <div><b><?= rp($totalSaldo) ?></b><span>Total saldo<?= $cari !== '' ? ' (hasil pencarian)' : '' ?></span></div>
    </div>

    <div class="top-grid">
        <div class="panel">
            <h3>Tambah member baru</h3>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="aksi" value="tambah">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" maxlength="100" required>
                <label for="no_hp">No. HP (opsional)</label>
                <input type="text" id="no_hp" name="no_hp" maxlength="20" inputmode="tel">
                <button class="btn btn-primary" type="submit">Simpan member</button>
            </form>
        </div>

        <div class="panel">
            <h3>Top-up saldo</h3>
            <form method="POST" onsubmit="return confirm('Proses top-up ini?');">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="aksi" value="topup">
                <label for="member_id">Member</label>
                <select id="member_id" name="member_id" required>
                    <option value="">Pilih member</option>
                    <?php foreach ($members as $m): if (!(int)$m['aktif']) continue; ?>
                        <option value="<?= (int)$m['id'] ?>"><?= h($m['kode_member']) ?> — <?= h($m['nama']) ?> (<?= rp($m['saldo']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <label for="jumlah">Jumlah top-up (Rp)</label>
                <input type="number" id="jumlah" name="jumlah" min="1000" max="10000000" step="1000" required>
                <button class="btn btn-green" type="submit">Tambah saldo</button>
            </form>
        </div>
    </div>

    <div class="panel" style="max-width:1100px">
        <h3>Daftar member</h3>
        <form method="GET" class="search-row">
            <input type="text" name="cari" value="<?= h($cari) ?>" placeholder="Cari kode, nama, atau nomor HP">
            <button class="btn btn-primary" type="submit">Cari</button>
            <?php if ($cari !== ''): ?><a class="btn btn-ghost" href="member.php" style="text-decoration:none">Reset</a><?php endif; ?>
        </form>

        <div class="table-scroll">
        <?php if (count($members) === 0): ?>
            <div class="empty"><?= $cari !== '' ? 'Tidak ada member yang cocok dengan pencarian.' : 'Belum ada member. Tambahkan member pertama di form atas.' ?></div>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>No. HP</th><th class="num">Saldo</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= h($m['kode_member']) ?></td>
                        <td><?= h($m['nama']) ?></td>
                        <td><?= h($m['no_hp'] ?: '-') ?></td>
                        <td class="num"><?= rp($m['saldo']) ?></td>
                        <td><span class="badge <?= $m['aktif'] ? 'on' : 'off' ?>"><?= $m['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div class="row-actions">
                                <a class="btn btn-small btn-ghost" style="text-decoration:none" href="?<?= h(http_build_query(['cari' => $cari, 'riwayat' => $m['id']])) ?>#riwayat">Riwayat</a>
                                <form class="inline" method="POST" onsubmit="return confirm('<?= $m['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> member ini?');">
                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="aksi" value="toggle">
                                    <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                                    <button class="btn btn-small <?= $m['aktif'] ? 'btn-warn' : 'btn-green' ?>" type="submit"><?= $m['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
    </div>

    <?php if ($riwayatNama !== ''): ?>
    <div class="panel" id="riwayat" style="max-width:1100px">
        <h3>Riwayat saldo: <?= h($riwayatNama) ?> (20 terakhir)</h3>
        <div class="table-scroll">
        <?php if (count($riwayat) === 0): ?>
            <div class="empty">Belum ada mutasi saldo untuk member ini.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Waktu</th><th>Jenis</th><th class="num">Jumlah</th><th class="num">Saldo sesudah</th><th>No. nota</th><th>Petugas</th></tr>
                </thead>
                <tbody>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                        <td class="tipe-<?= h($r['tipe']) ?>"><?= h(ucfirst($r['tipe'])) ?></td>
                        <td class="num"><?= rp($r['jumlah']) ?></td>
                        <td class="num"><?= rp($r['saldo_sesudah']) ?></td>
                        <td><?= h($r['no_transaksi'] ?: '-') ?></td>
                        <td><?= h($r['petugas'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
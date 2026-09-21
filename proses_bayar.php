<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

require_once 'database.php';

// Error yang boleh ditampilkan ke kasir (stok kurang, saldo kurang, dll.)
class BayarException extends Exception {}

$conn = getConnection();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$input = json_decode(file_get_contents('php://input'), true);

// Format baru: {items:[{id,qty}], metode, member_id, bayar}
// Format lama (array item saja) tetap diterima sebagai tunai.
if (is_array($input) && count($input) > 0 && array_keys($input) === range(0, count($input) - 1)) {
    $input = ['items' => $input, 'metode' => 'tunai'];
}

$items    = $input['items'] ?? [];
$metode   = $input['metode'] ?? 'tunai';
$memberId = (int)($input['member_id'] ?? 0);
$bayar    = (float)($input['bayar'] ?? 0);
$userId   = (int)$_SESSION['user_id'];

if (!is_array($items) || count($items) === 0) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit;
}
if (!in_array($metode, ['tunai', 'saldo'], true)) {
    echo json_encode(['success' => false, 'message' => 'Metode bayar tidak dikenal']);
    exit;
}

// Gabungkan item dengan id sama (jaga-jaga keranjang berisi duplikat)
$qtyPerBarang = [];
foreach ($items as $item) {
    $id  = (int)($item['id'] ?? 0);
    $qty = (int)($item['qty'] ?? 0);
    if ($id <= 0 || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data item tidak valid']);
        exit;
    }
    $qtyPerBarang[$id] = ($qtyPerBarang[$id] ?? 0) + $qty;
}

// Satu nomor nota untuk seluruh keranjang (20 karakter, muat di transaksi & saldo_log)
$noTransaksi = 'TRX' . date('YmdHis') . random_int(100, 999);

$conn->begin_transaction();
try {
    $getBarang = $conn->prepare("SELECT nama_barang, harga_jual FROM barang WHERE id = ?");
    $kurangi   = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE id = ? AND stok >= ?");

    // 1) Kurangi stok + hitung total (harga selalu dari database, bukan dari browser)
    $baris = [];
    $total = 0.0;
    foreach ($qtyPerBarang as $id => $qty) {
        $getBarang->bind_param('i', $id);
        $getBarang->execute();
        $row = $getBarang->get_result()->fetch_assoc();
        if (!$row) throw new BayarException('Barang tidak ditemukan');

        $kurangi->bind_param('iii', $qty, $id, $qty);
        $kurangi->execute();
        if ($kurangi->affected_rows === 0) {
            throw new BayarException('Stok tidak cukup: ' . $row['nama_barang']);
        }

        $harga = (float)$row['harga_jual'];
        $sub   = round($qty * $harga, 2);
        $total = round($total + $sub, 2);
        $baris[] = ['id' => $id, 'qty' => $qty, 'harga' => $harga, 'sub' => $sub];
    }

    // 2) Pembayaran
    $memberInfo = null;
    $kembalian  = 0.0;
    $memberDb   = null; // member_id yang dicatat di transaksi

    if ($metode === 'tunai') {
        if ($bayar < $total) throw new BayarException('Uang bayar kurang');
        $kembalian = round($bayar - $total, 2);
    } else {
        if ($memberId <= 0) throw new BayarException('Pilih member terlebih dahulu');

        // Kunci baris member supaya dua kasir tidak memotong saldo bersamaan
        $getMember = $conn->prepare("SELECT kode_member, nama, saldo, aktif FROM member WHERE id = ? FOR UPDATE");
        $getMember->bind_param('i', $memberId);
        $getMember->execute();
        $m = $getMember->get_result()->fetch_assoc();

        if (!$m)                    throw new BayarException('Member tidak ditemukan');
        if ((int)$m['aktif'] !== 1) throw new BayarException('Member ini sudah dinonaktifkan');

        $saldoLama = (float)$m['saldo'];
        if ($saldoLama < $total) {
            throw new BayarException('Saldo tidak cukup. Saldo Rp ' . number_format($saldoLama, 0, ',', '.')
                . ', total Rp ' . number_format($total, 0, ',', '.'));
        }

        $saldoBaru = round($saldoLama - $total, 2);

        $upd = $conn->prepare("UPDATE member SET saldo = ? WHERE id = ?");
        $upd->bind_param('di', $saldoBaru, $memberId);
        $upd->execute();

        // Dipotong SEKALI sebesar total nota, bukan per baris
        $ket = 'Pembayaran belanja ' . $noTransaksi;
        $log = $conn->prepare("
            INSERT INTO saldo_log (member_id, tipe, jumlah, saldo_sesudah, no_transaksi, keterangan, user_id)
            VALUES (?, 'bayar', ?, ?, ?, ?, ?)
        ");
        $log->bind_param('iddssi', $memberId, $total, $saldoBaru, $noTransaksi, $ket, $userId);
        $log->execute();

        $memberDb   = $memberId;
        $memberInfo = [
            'kode_member'   => $m['kode_member'],
            'nama'          => $m['nama'],
            'saldo_sesudah' => $saldoBaru,
        ];
        $bayar = $total;
    }

    // 3) Satu baris transaksi per barang, nomor nota sama
    $insert = $conn->prepare("
        INSERT INTO transaksi
            (no_transaksi, tipe, barang_id, jumlah, harga_satuan, total_harga, tanggal, metode_bayar, member_id, user_id)
        VALUES (?, 'keluar', ?, ?, ?, ?, CURDATE(), ?, ?, ?)
    ");
    foreach ($baris as $b) {
        $insert->bind_param('siiddsii',
            $noTransaksi, $b['id'], $b['qty'], $b['harga'], $b['sub'],
            $metode, $memberDb, $userId
        );
        $insert->execute();
    }

    $conn->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Transaksi berhasil',
        'invoice'      => $noTransaksi,   // dibaca kasir.php
        'no_transaksi' => $noTransaksi,   // nama lama, dipertahankan
        'total'        => $total,
        'bayar'        => $bayar,
        'kembalian'    => $kembalian,
        'metode'       => $metode,
        'member'       => $memberInfo,
    ]);
} catch (BayarException $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('proses_bayar: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan di server. Transaksi dibatalkan, coba lagi.']);
}

$conn->close();
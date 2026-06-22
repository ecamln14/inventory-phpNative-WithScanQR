<?php

require_once 'database.php';

$conn = getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || count($data) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Keranjang kosong"
    ]);
    exit;
}

foreach ($data as $item) {

    $noTransaksi = "TRX" . date("YmdHis") . rand(100,999);

    $barangId = $item['id'];
    $qty = $item['qty'];
    $harga = $item['harga'];
    $total = $qty * $harga;

    // Simpan transaksi
    $stmt = $conn->prepare("
        INSERT INTO transaksi
        (
            no_transaksi,
            tipe,
            barang_id,
            jumlah,
            harga_satuan,
            total_harga,
            tanggal
        )
        VALUES
        (?, 'keluar', ?, ?, ?, ?, CURDATE())
    ");

    $stmt->bind_param(
        "siidd",
        $noTransaksi,
        $barangId,
        $qty,
        $harga,
        $total
    );

    $stmt->execute();

    // Kurangi stok
    $update = $conn->prepare("
        UPDATE barang
        SET stok = stok - ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ii",
        $qty,
        $barangId
    );

    $update->execute();
}

echo json_encode([
    "success" => true,
    "message" => "Transaksi berhasil"
]);

$conn->close();
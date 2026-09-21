<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'belum login']);
    exit;
}

require_once 'database.php';
$conn = getConnection();

$qr = trim($_GET['qr'] ?? '');

// Hanya kolom yang dibutuhkan kasir (harga_beli tidak ikut terkirim)
$stmt = $conn->prepare("
    SELECT id, kode_barang, nama_barang, harga_jual, stok, gambar
    FROM barang
    WHERE qr_code = ?
    LIMIT 1
");
$stmt->bind_param('s', $qr);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

echo json_encode($data ?: null);
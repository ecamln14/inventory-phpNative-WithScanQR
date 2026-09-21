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

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Escape karakter khusus LIKE supaya % dan _ dianggap huruf biasa
$like = '%' . addcslashes($q, '%_\\') . '%';

$stmt = $conn->prepare("
    SELECT id, kode_member, nama, no_hp, saldo
    FROM member
    WHERE aktif = 1
      AND (kode_member = ? OR nama LIKE ? OR no_hp LIKE ?)
    ORDER BY nama
    LIMIT 8
");
$stmt->bind_param('sss', $q, $like, $like);
$stmt->execute();

$hasil = [];
foreach ($stmt->get_result() as $row) {
    $row['saldo'] = (float)$row['saldo'];
    $hasil[] = $row;
}

echo json_encode($hasil);
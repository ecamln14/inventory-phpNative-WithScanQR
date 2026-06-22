<?php

require_once 'database.php';

$conn = getConnection();

$qr = $_GET['qr'] ?? '';

$stmt = $conn->prepare("
    SELECT *
    FROM barang
    WHERE qr_code = ?
");

$stmt->bind_param("s", $qr);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($data);
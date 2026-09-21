<?php
// auth.php — include di SEMUA halaman (bukan endpoint JSON) yang perlu login.
// Cukup: require_once 'auth.php';  (tidak perlu session_start() lagi)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function wajib_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function wajib_admin(): void {
    wajib_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Akses ditolak. Halaman ini hanya untuk admin. <a href="index.php">Kembali</a>');
    }
}

// Token sederhana untuk form POST (mencegah CSRF)
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

wajib_login();
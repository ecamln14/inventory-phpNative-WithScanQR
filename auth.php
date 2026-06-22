<?php
// auth.php — include file ini di SEMUA halaman yang perlu login
// Letakkan di baris paling atas, setelah session_start()

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
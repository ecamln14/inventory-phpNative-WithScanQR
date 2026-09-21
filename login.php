<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn     = getConnection();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, nama, password, role, aktif FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        if ((int)$user['aktif'] !== 1) {
            $pesan = ['type' => 'danger', 'text' => 'Akun ini dinonaktifkan. Hubungi admin.'];
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = $user['role'] ?: 'kasir';
            $conn->close();
            header('Location: index.php');
            exit;
        }
    } else {
        $pesan = ['type' => 'danger', 'text' => 'Username atau password salah!'];
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — TrackInventori</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --ink: #0f1b13;
            --amber: #d97706;
            --amber-dark: #b45309;
            --amber-light: #fffbeb;
            --forest: #14532d;
            --forest-deep: #0b3320;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --gray: #6b7280;
            --border: #e7e5e4;
            --paper: #fafaf9;
            --white: #ffffff;
            --font: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font);
            background: var(--paper);
            color: var(--ink);
            font-size: 14px;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
        }

        /* ---------- LEFT: brand / crate illustration panel ---------- */
        .panel {
            position: relative;
            background: linear-gradient(160deg, var(--forest) 0%, var(--forest-deep) 100%);
            color: white;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(45deg, rgba(255,255,255,.035) 0 2px, transparent 2px 26px),
                repeating-linear-gradient(-45deg, rgba(255,255,255,.035) 0 2px, transparent 2px 26px);
        }

        .panel-top {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 17px;
            letter-spacing: .2px;
        }

        .panel-top .mark {
            width: 34px; height: 34px;
            background: var(--amber);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            transform: rotate(-3deg);
        }

        .crates {
            position: relative;
            display: flex;
            align-items: flex-end;
            gap: 14px;
            margin: 40px 0;
        }

        .crate {
            background: rgba(255,255,255,.08);
            border: 1.5px solid rgba(255,255,255,.18);
            border-radius: 10px;
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--amber);
            font-size: 22px;
        }
        .crate.c1 { width: 68px; height: 68px; }
        .crate.c2 { width: 88px; height: 96px; background: rgba(217,119,6,.16); border-color: rgba(217,119,6,.4); }
        .crate.c3 { width: 68px; height: 52px; }
        .crate.c4 { width: 54px; height: 80px; }

        .panel-copy {
            position: relative;
        }

        .panel-copy h2 {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .panel-copy p {
            color: rgba(255,255,255,.7);
            font-size: 13.5px;
            line-height: 1.6;
            max-width: 380px;
        }

        .panel-stats {
            position: relative;
            display: flex;
            gap: 28px;
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,.14);
        }

        .panel-stats div strong {
            display: block;
            font-size: 19px;
            font-weight: 800;
            color: var(--amber);
        }
        .panel-stats div span {
            font-size: 11.5px;
            color: rgba(255,255,255,.6);
        }

        /* ---------- RIGHT: form panel ---------- */
        .form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .form-box {
            width: 100%;
            max-width: 360px;
        }

        .form-box .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--amber-dark);
            background: var(--amber-light);
            padding: 5px 10px;
            border-radius: 999px;
            margin-bottom: 16px;
        }

        .form-box h1 {
            font-size: 23px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .form-box .sub {
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 26px;
        }

        .form-group { margin-bottom: 16px; }

        label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--ink);
        }

        .input-wrap { position: relative; }

        .input-wrap i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #a8a29e;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 11px 12px 11px 38px;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-size: 13px;
            font-family: var(--font);
            color: var(--ink);
            background: var(--white);
            transition: border-color .15s, box-shadow .15s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--forest);
            box-shadow: 0 0 0 3px rgba(20,83,45,.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 16px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            font-family: var(--font);
            cursor: pointer;
            border: none;
            transition: transform .1s, background .15s;
        }

        .btn-primary { background: var(--forest); color: white; }
        .btn-primary:hover { background: var(--forest-deep); }
        .btn-primary:active { transform: scale(.99); }

        .alert {
            padding: 10px 14px;
            border-radius: 9px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .form-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            font-size: 12.5px;
        }

        .form-foot a { color: var(--gray); text-decoration: none; }
        .form-foot a:hover { color: var(--forest); }

        .register-link {
            text-align: center;
            font-size: 13px;
            color: var(--gray);
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .register-link a {
            color: var(--forest);
            font-weight: 700;
            text-decoration: none;
        }
        .register-link a:hover { text-decoration: underline; }

        .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #a8a29e;
            font-size: 14px;
            padding: 0;
        }

        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
            .panel { display: none; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="panel">
            <div class="panel-top">
                <div class="mark"><i class="fa-solid fa-boxes-stacked"></i></div>
                TrackInventori
            </div>

            <div class="crates">
                <div class="crate c1"><i class="fa-solid fa-cube"></i></div>
                <div class="crate c2"><i class="fa-solid fa-barcode"></i></div>
                <div class="crate c3"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="crate c4"><i class="fa-solid fa-layer-group"></i></div>
            </div>

            <div class="panel-copy">
                <h2>Kelola stok &amp; transaksi tanpa drama.</h2>
                <p>Satu dashboard untuk barang masuk, barang keluar, kasir, dan laporan — supaya stok gudang selalu akurat.</p>

                <div class="panel-stats">
                    <div><strong>+RT</strong><span>Update Stok</span></div>
                    <div><strong>QR</strong><span>Scan Kasir</span></div>
                    <div><strong>24/7</strong><span>Laporan</span></div>
                </div>
            </div>
        </div>

        <div class="form-side">
            <div class="form-box">
                <span class="eyebrow"><i class="fa-solid fa-lock"></i> Area Terbatas</span>
                <h1>Selamat Datang Kembali</h1>
                <p class="sub">Masuk untuk mengelola inventori dan transaksi kamu.</p>

                <?php if ($pesan): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-xmark"></i> <?= $pesan['text'] ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="username" class="form-control"
                                   placeholder="Masukkan username" required
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="password" id="passwordInput"
                                   class="form-control" placeholder="Masukkan password" required>
                            <button type="button" class="toggle-pass" onclick="togglePassword()">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-foot">
                        <label style="display:flex;align-items:center;gap:6px;font-weight:500;color:var(--gray)">
                            <input type="checkbox" style="width:auto"> Ingat saya
                        </label>
                        <a href="#">Lupa password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:20px">
                        <i class="fa-solid fa-right-to-bracket"></i> Masuk
                    </button>
                </form>

                <div class="register-link">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon  = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
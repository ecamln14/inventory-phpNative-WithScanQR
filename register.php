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
    $nama     = $conn->real_escape_string(trim($_POST['nama']));
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirmasi_password'];

    // Validasi
    if (empty($nama) || empty($username) || empty($password)) {
        $pesan = ['type' => 'danger', 'text' => 'Semua field wajib diisi!'];
    } elseif (strlen($password) < 6) {
        $pesan = ['type' => 'danger', 'text' => 'Password minimal 6 karakter!'];
    } elseif ($password !== $konfirm) {
        $pesan = ['type' => 'danger', 'text' => 'Konfirmasi password tidak cocok!'];
    } else {
        // Cek username sudah ada
        $cek = $conn->query("SELECT id FROM users WHERE username='$username'");
        if ($cek->num_rows > 0) {
            $pesan = ['type' => 'danger', 'text' => 'Username sudah digunakan!'];
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($conn->query("INSERT INTO users (nama, username, password) VALUES ('$nama', '$username', '$hash')")) {
                $pesan = ['type' => 'success', 'text' => 'Akun berhasil dibuat! Silakan login.'];
            } else {
                $pesan = ['type' => 'danger', 'text' => 'Gagal membuat akun: ' . $conn->error];
            }
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — TrackInventori</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --success-light: #f0fdf4;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --border: #e2e8f0;
            --white: #ffffff;
            --font: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font);
            background: var(--gray-light);
            color: var(--dark);
            font-size: 14px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-brand .icon {
            width: 56px; height: 56px;
            background: var(--primary);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 12px;
        }

        .login-brand h1 { font-size: 22px; font-weight: 700; }
        .login-brand p  { color: var(--gray); font-size: 13px; margin-top: 4px; }

        .card {
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            border: 1px solid var(--border);
            padding: 28px;
        }

        .form-group { margin-bottom: 14px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .input-wrap { position: relative; }

        .input-wrap i.icon-left {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px 10px 36px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: var(--font);
            color: var(--dark);
            transition: border-color .15s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            border: none;
            transition: all .15s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        .alert-danger  { background: var(--danger-light);  color: var(--danger);  border: 1px solid #fecaca; }
        .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #bbf7d0; }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
            color: var(--gray);
            font-size: 12px;
        }

        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: var(--border);
        }

        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .login-link {
            text-align: center;
            font-size: 13px;
            color: var(--gray);
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover { text-decoration: underline; }

        .toggle-pass {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            font-size: 14px;
            padding: 0;
        }

        .strength-bar {
            height: 4px;
            border-radius: 99px;
            background: var(--border);
            margin-top: 6px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 99px;
            transition: all .3s;
            width: 0;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-brand">
            <div class="icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <h1>TrackInventori</h1>
            <p>Sistem Manajemen Inventori</p>
        </div>

        <div class="card">
            <h2 style="font-size:17px;font-weight:700;margin-bottom:20px">Buat Akun Baru</h2>

            <?php if ($pesan): ?>
            <div class="alert alert-<?= $pesan['type'] ?>">
                <i class="fa-solid fa-<?= $pesan['type']==='success' ? 'circle-check' : 'circle-xmark' ?>"></i>
                <?= $pesan['text'] ?>
                <?php if ($pesan['type'] === 'success'): ?>
                    <a href="login.php" style="margin-left:8px;color:var(--success);font-weight:700">Login →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-id-card icon-left"></i>
                        <input type="text" name="nama" class="form-control"
                               placeholder="Masukkan nama lengkap" required
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user icon-left"></i>
                        <input type="text" name="username" class="form-control"
                               placeholder="Buat username unik" required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock icon-left"></i>
                        <input type="password" name="password" id="passInput"
                               class="form-control" placeholder="Minimal 6 karakter"
                               required oninput="cekKekuatan(this.value)">
                        <button type="button" class="toggle-pass" onclick="togglePass('passInput','eye1')">
                            <i class="fa-solid fa-eye" id="eye1"></i>
                        </button>
                    </div>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock icon-left"></i>
                        <input type="password" name="konfirmasi_password" id="konfirmInput"
                               class="form-control" placeholder="Ulangi password" required>
                        <button type="button" class="toggle-pass" onclick="togglePass('konfirmInput','eye2')">
                            <i class="fa-solid fa-eye" id="eye2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:8px">
                    <i class="fa-solid fa-user-plus"></i> Daftar
                </button>
            </form>

            <div class="divider">atau</div>

            <div class="login-link">
                Sudah punya akun? <a href="login.php">Masuk sekarang</a>
            </div>
        </div>
    </div>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function cekKekuatan(val) {
            const fill = document.getElementById('strengthFill');
            let score = 0;
            if (val.length >= 6)  score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const colors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
            fill.style.width      = widths[score];
            fill.style.background = colors[score];
        }
    </script>
</body>
</html>
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
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];

    $r = $conn->query("SELECT * FROM users WHERE username='$username' LIMIT 1");

    if ($r->num_rows === 1) {
        $user = $r->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            header('Location: index.php');
            exit;
        }
    }

    $pesan = ['type' => 'danger', 'text' => 'Username atau password salah!'];
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — TrackInventori</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
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
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
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

        .login-brand h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
        }

        .login-brand p {
            color: var(--gray);
            font-size: 13px;
            margin-top: 4px;
        }

        .card {
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            border: 1px solid var(--border);
            padding: 28px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--dark);
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
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

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid #fecaca;
        }

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
        .divider::after { right: 0; }

        .register-link {
            text-align: center;
            font-size: 13px;
            color: var(--gray);
        }

        .register-link a {
            color: var(--primary);
            font-weight: 600;
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
            color: var(--gray);
            font-size: 14px;
            padding: 0;
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
            <h2 style="font-size:17px;font-weight:700;margin-bottom:20px">Masuk ke Akun</h2>

            <?php if ($pesan): ?>
            <div class="alert alert-<?= $pesan['type'] ?>">
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

                <button type="submit" class="btn btn-primary" style="margin-top:8px">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk
                </button>
            </form>

            <div class="divider">atau</div>

            <div class="register-link">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
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
<?php
// login.php - LOGIN UNTUK SISWA
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if (hasRole(['admin', 'pembina'])) {
        redirect('admin/dashboard.php');
    } else {
        redirect('siswa/dashboard.php');
    }
}

$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($nis) || empty($password)) {
        $error = 'NIS dan password harus diisi!';
    } else {
        $sql = "SELECT * FROM users WHERE nis = ? AND role = 'siswa' AND is_active = 1";
        $result = query($sql, [$nis], 's');
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_nis'] = $user['nis'];
                $_SESSION['user_role'] = $user['role'];
                
                setFlash('success', 'Login berhasil! Selamat datang, ' . $user['name']);
                redirect('siswa/dashboard.php');
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'NIS tidak ditemukan atau akun tidak aktif!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Siswa - MTsN 1 Lebak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: white;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20%;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            backdrop-filter: blur;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 80px;
        }

        .header-text h1 {
            color: white;
            font-size: 18px;
            font-weight: 700;
            /* margin-bottom: 300px; */
        }

        .header-text p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 80px;
        }

        .login-container {
            width: 100%;
            max-width: 640px;
            background: white;
            border-radius: 14px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            z-index: 99;
            max-height: 90vh;
            overflow-y: auto;
            margin-top: 50px;
        }
        .login-container::-webkit-scrollbar {
                width: 6px;
            }
        .login-container::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

        .illustration {
            text-align: center;
            margin-bottom: 32px;
        }

        .illustration img {
            width: 290px;
            height: auto;
        }

        .login-title {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .login-title p {
            font-size: 14px;
            color: #64748b;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fee;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
        }

        .form-input {
            width: 100%;
            height: 52px;
            padding: 0 16px 0 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 20px;
            padding: 4px;
        }

        .password-toggle:hover {
            color: #64748b;
        }

        .btn-login {
            width: 100%;
            height: 52px;
            background: #3b82f6;
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .btn-login:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-links {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .footer-links p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-block;
            margin: 0 8px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .copyright {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Icons using Unicode */
        .icon-user::before { content: '👤'; }
        .icon-lock::before { content: '🔒'; }
        .icon-eye::before { content: '👁'; }
        .icon-eye-slash::before { content: '🙈'; }
        .icon-alert::before { content: '⚠️'; }
        .icon-check::before { content: '✓'; }

        @media (max-width: 576px) {
            .header {
                padding: 16px 20px;
            }

            .header-logo {
                width: 40px;
                height: 40px;
            }

            .header-text h1 {
                font-size: 16px;
            }

            .header-text p {
                font-size: 12px;
            }

            .login-container {
                padding: 32px 24px;
            }

            .illustration img {
                width: 180px;
            }

            .login-title h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <img src="<?php echo BASE_URL; ?>assets/images/logo MTSN1.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="header-text">
            <h1>MTsN 1 Lebak</h1>
            <p>SISTEM EKSTRAKURIKULER</p>
        </div>
    </div>

    <div class="login-container">
        <div class="illustration">
            <img src="<?php echo BASE_URL; ?>assets/images/logo-belajar.png" alt="Login Illustration" 
                 onerror="this.style.display='none'">
        </div>

        <div class="login-title">
            <h2>Aplikasi Ekstrakurikuler</h2>
            <p>Silakan Login dengan NIS dan Password</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?>">
            <span class="icon-<?php echo $flash['type'] == 'success' ? 'check' : 'alert'; ?>"></span>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <span class="icon-alert"></span>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">NIS</label>
                <div class="input-wrapper">
                    <span class="input-icon icon-user"></span>
                    <input type="text" 
                           name="nis" 
                           class="form-input" 
                           placeholder="Masukkan NIS"
                           value="<?php echo isset($_POST['nis']) ? htmlspecialchars($_POST['nis']) : ''; ?>"
                           required 
                           autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon icon-lock"></span>
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="form-input" 
                           placeholder="Masukkan Password"
                           required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <span id="toggleIcon" class="icon-eye"></span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="footer-links">
            <p>Belum punya akun? <a href="<?php echo BASE_URL; ?>registerasi.php">Registrasi</a></p>
            <a href="<?php echo BASE_URL; ?>">← Kembali ke Beranda</a>
            <span style="color: #cbd5e1;">|</span>
            <a href="<?php echo BASE_URL; ?>admin/login_admin.php">Login Admin/Pembina</a>
        </div>
        <p class="copyright" style="color: black;">© 2025 MTsN 1 Lebak. Sistem Ekstrakurikuler</p>
    </div>


    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'icon-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'icon-eye';
            }
        }
    </script>
</body>
</html>
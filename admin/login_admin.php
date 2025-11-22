<?php
// admin/login_admin.php - LOGIN UNTUK ADMIN & PEMBINA
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if (hasRole(['admin'])) {
        redirect('admin/dashboard.php');
    } elseif(hasRole(['pembina'])) {
        redirect('pembina/dashboard.php');
    } else {
        redirect('siswa/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $result = query($sql, [$email], 's');
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Cek apakah admin atau pembina
                if (in_array($user['role'], ['admin', 'pembina'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    if ($user['role'] == 'pembina') {
                        $_SESSION['pembina_id'] = $user['id'];
                    }
                    
                    setFlash('success', 'Login berhasil! Selamat datang, ' . $user['name']);
                    
                    if ($user['role'] == 'admin') {
                        redirect('admin/dashboard.php');
                    } else {
                        redirect('pembina/dashboard.php');
                    }
                } else {
                    $error = 'Anda tidak memiliki akses ke halaman admin!';
                }
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Email tidak ditemukan atau akun tidak aktif!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin & Pembina - MTsN 1 Lebak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            padding: 48px 40px;
            border: 1px solid #e9ecef;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            width: 64px;
            height: 64px;
            background: white;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .logo-icon i {
            font-size: 32px;
            color: white;
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .login-subtitle {
            font-size: 14px;
            color: #6c757d;
            font-weight: 400;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control {
            height: 48px;
            border: 1.5px solid #dee2e6;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #fff;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: #fff;
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 16px;
        }
        
        .input-icon .form-control {
            padding-left: 44px;
        }
        
        .btn-login {
            height: 48px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            color: white;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 14px 16px;
            font-size: 14px;
            margin-bottom: 24px;
        }
        
        .alert-danger {
            background: #fff5f5;
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e9ecef;
        }
        
        .divider span {
            background: white;
            padding: 0 16px;
            position: relative;
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .link-siswa {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-radius: 10px;
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1.5px solid #e9ecef;
        }
        
        .link-siswa:hover {
            background: #e9ecef;
            color: #212529;
            border-color: #dee2e6;
        }
        
        .link-siswa i {
            font-size: 16px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: #6c757d;
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 32px 24px;
            }
            
            .login-title {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo-icon">
                    <img src="<?= BASE_URL ?>assets/images/logo MTSN1.png" width="70" alt="logo sekolah">
                </div>
                <h1 class="login-title">Selamat Datang</h1>
                <p class="login-subtitle">Admin & Pembina Dashboard</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="nama@example.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Masukkan password"
                               required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Masuk ke Dashboard
                </button>
            </form>
            
            <div class="divider">
                <span>atau</span>
            </div>
            
            <a href="<?= BASE_URL?>admin/login.php" class="link-siswa">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Login sebagai Siswa</span>
            </a>
        </div>
        
        <p class="footer-text">
            © 2024 MTsN 1 Lebak. Sistem Ekstrakurikuler
        </p>
    </div>
</body>
</html>
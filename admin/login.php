<?php
// admin/login.php - LOGIN UNTUK SISWA
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if (hasRole(['admin', 'pembina'])) {
        redirect('admin/dashboard.php');
    } else {
        redirect('siswa/dashboard.php'); // Dashboard siswa
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($nis) || empty($password)) {
        $error = 'NIS dan password harus diisi!';
    } else {
        // Login dengan NIS untuk siswa
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2.5rem;
            background: white;
        }
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="login-header">
                        <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-0">Login Siswa</h3>
                        <p class="mb-0">Sistem Ekstrakurikuler MTsN 1 Lebak</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-person-badge-fill"></i> NIS
                                </label>
                                <input type="text" name="nis" class="form-control" placeholder="Masukkan NIS Anda" required autofocus>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-lock-fill"></i> Password
                                </label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                            
                            <button type="submit" class="btn btn-login btn-success w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                            
                            <div class="text-center">
                                <a href="<?php echo BASE_URL; ?>" class="text-decoration-none me-3">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                                </a>
                                <a href="<?php echo BASE_URL; ?>admin/login_admin.php" class="text-decoration-none">
                                    <i class="bi bi-shield-lock"></i> Login Admin/Pembina
                                </a>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        <div class="text-center text-muted small">
                            <p class="mb-1">Belum punya akun?</p>
                            <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-pencil-square"></i> Daftar Ekstrakurikuler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
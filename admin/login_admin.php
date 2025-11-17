<?php
// admin/login_admin.php - LOGIN UNTUK ADMIN & PEMBINA
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
                    
                    setFlash('success', 'Login berhasil! Selamat datang, ' . $user['name']);
                    redirect('admin/dashboard.php');
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
    <title>Login Admin - MTsN 1 Lebak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card { border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .login-header { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); color: white; padding: 2rem; text-align: center; }
        .login-body { padding: 2.5rem; background: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="login-header">
                        <i class="bi bi-shield-lock-fill" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 mb-0">Admin & Pembina Login</h3>
                        <p class="mb-0">Sistem Ekstrakurikuler MTsN 1 Lebak</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-envelope-fill"></i> Email
                                </label>
                                <input type="email" name="email" class="form-control" required autofocus>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-lock-fill"></i> Password
                                </label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-danger w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                            
                            <div class="text-center">
                                <a href="<?php echo BASE_URL; ?>admin/login.php" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> Login Siswa
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
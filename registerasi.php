<?php
// registerasi.php
$page_title = 'Registrasi Akun Siswa';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis            = trim($_POST['nis'] ?? '');
    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $kelas          = trim($_POST['kelas'] ?? '');
    $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
    $no_hp          = trim($_POST['no_hp'] ?? '');
    $alamat         = trim($_POST['alamat'] ?? '');
    $password       = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi sederhana
    $errors = [];

    if ($nis === '' || $name === '' || $kelas === '' || $jenis_kelamin === '' || $no_hp === '' || $alamat === '') {
        $errors[] = 'Semua field bertanda * wajib diisi.';
    }

    if ($password === '' || $password_confirm === '') {
        $errors[] = 'Password dan konfirmasi password wajib diisi.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Password dan konfirmasi password tidak sama.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    // Cek NIS sudah digunakan
    if ($nis !== '') {
        $cek_nis = query("SELECT id FROM users WHERE nis = ?", [$nis], 's');
        if ($cek_nis && $cek_nis->num_rows > 0) {
            $errors[] = 'NIS sudah terdaftar. Silakan login atau hubungi admin.';
        }
    }

    // Cek email jika diisi
    if ($email !== '') {
        $cek_email = query("SELECT id FROM users WHERE email = ?", [$email], 's');
        if ($cek_email && $cek_email->num_rows > 0) {
            $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, nis, password, role, kelas, jenis_kelamin, no_hp, alamat)
                VALUES (?, ?, ?, ?, 'siswa', ?, ?, ?, ?)";

        $result = execute(
            $sql,
            [$name, $email ?: null, $nis, $hashed_password, $kelas, $jenis_kelamin, $no_hp, $alamat],
            'ssssssss'
        );

        if ($result['success']) {
            setFlash('success', 'Registrasi berhasil! Silakan login menggunakan NIS dan password yang Anda buat.');
            redirect('admin/login.php');
        } else {
            $errors[] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        }
    }

    if (!empty($errors)) {
        // Gabungkan semua error ke flash message
        $message = '<ul class="mb-0">';
        foreach ($errors as $err) {
            $message .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $message .= '</ul>';
        setFlash('danger', $message);
        redirect('registerasi.php');
    }
}
require_once 'includes/header.php';
?>

<style>
    .register-section {
        background: radial-gradient(circle at top left, rgba(16, 185, 129, 0.15), transparent 55%),
                    radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.12), transparent 50%),
                    #f8fafc;
        min-height:100vh;
        display: flex;
        align-items: center;
        padding: 3rem 0;
    }
    .register-card {
        border-radius: 32px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25);
    }
    .register-hero {
        background: linear-gradient(140deg, #0f766e, #15803d);
        color: #fff;
        padding: 2.5rem 2.75rem;
        display: flex;
        align-items: center;
    }
    .register-hero h2 {
        font-weight: 700;
    }
    .hero-bullet {
        display: flex;
        align-items: center;
        gap: .75rem;
        color: rgba(255,255,255,.85);
        margin-bottom: .65rem;
        font-size: .95rem;
    }
    .register-form {
        padding: 2.5rem 2.75rem;
        background: #fdfefe;
    }
    .register-form .form-label {
        font-weight: 600;
        color: #0f172a;
    }
    .register-form .form-control,
    .register-form .form-select,
    .register-form textarea {
        border-radius: 14px;
        padding: 0.75rem 0.95rem;
        border-color: #e2e8f0;
    }
    .register-form .form-control:focus,
    .register-form .form-select:focus,
    .register-form textarea:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 0.15rem rgba(16, 185, 129, 0.15);
    }
    .register-form .btn-register {
        border-radius: 999px;
        padding: 0.85rem 1rem;
        font-weight: 600;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        border: none;
        color: #fff;
        box-shadow: 0 12px 30px rgba(16, 185, 129, 0.3);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .register-form .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 40px rgba(16, 185, 129, 0.35);
    }
    .register-form .btn-outline {
        border-radius: 999px;
        border: 1px solid #0f766e;
        color: #0f766e;
        font-weight: 600;
        padding: 0.8rem 1rem;
    }
    @media (max-width: 991px) {
        .register-hero {
            border-radius: 32px 32px 0 0;
        }
        .register-form {
            border-radius: 0 0 32px 32px;
        }
    }
</style>

<section class="register-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="register-card row g-0">
                    <div class="col-lg-5 register-hero">
                        <div>
                            <span class="badge bg-white text-emerald-600 text-uppercase fw-semibold mb-3">MTsN 1 Lebak</span>
                            <h2 class="mb-3">Buat Akun Siswa</h2>
                            <p class="text-white-50 mb-4">Daftarkan diri Anda untuk mendapatkan akses ke seluruh fitur ekstrakurikuler, jadwal, dan informasi terbaru sekolah.</p>
                            <div class="hero-bullet"><i class="bi bi-check-circle-fill"></i>One ID untuk semua ekstrakurikuler</div>
                            <div class="hero-bullet"><i class="bi bi-check-circle-fill"></i>Update kegiatan dan jadwal secara real-time</div>
                            <div class="hero-bullet"><i class="bi bi-check-circle-fill"></i>Pantau progres dan sertifikat Anda</div>
                        </div>
                    </div>
                    <div class="col-lg-7 register-form">
                        <div class="text-center mb-4">
                            <div class="mb-2 text-success" style="font-size:2.25rem;"><i class="bi bi-person-plus-fill"></i></div>
                            <h3 class="fw-bold mb-1">Formulir Registrasi</h3>
                            <p class="text-muted small mb-0">Isi data dengan benar sesuai identitas sekolah Anda.</p>
                        </div>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">NIS <span class="text-danger">*</span></label>
                                    <input type="text" name="nis" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                    <select name="kelas" class="form-select" required>
                                        <option value="">Pilih Kelas</option>
                                        <?php 
                                        for ($i = 7; $i <= 9; $i++) {
                                            foreach (['A', 'B', 'C', 'D'] as $huruf) {
                                                echo "<option value='{$i}{$huruf}'>{$i}{$huruf}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="jenis_kelamin" class="form-select" required>
                                        <option value="">Pilih</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No HP/WA <span class="text-danger">*</span></label>
                                    <input type="text" name="no_hp" class="form-control" placeholder="081234567890" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="email@example.com">
                                    <small class="text-muted">Opsional, gunakan email aktif.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                    <textarea name="alamat" class="form-control" rows="2" required></textarea>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" required>
                                    <small class="text-muted">Minimal 6 karakter.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirm" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-check form-switch mt-4 mb-4">
                                <input class="form-check-input" type="checkbox" id="agree" required>
                                <label class="form-check-label small" for="agree">
                                    Saya menyatakan data yang saya isi adalah benar dan siap menjaga kerahasiaan akun.
                                </label>
                            </div>

                            <div class="d-grid gap-3">
                                <button type="submit" class="btn-register">
                                    Buat Akun Sekarang
                                </button>
                                <a href="<?php echo BASE_URL; ?>admin/login.php" class="btn btn-outline-success rounded-pill px-4">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Sudah punya akun? Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

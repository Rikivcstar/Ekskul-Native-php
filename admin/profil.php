<?php
// admin/profil.php
require_once '../config/database.php';
require_once __DIR__ . '/../config/middleware.php';
only('admin');
requireRole(['admin', 'pembina']);

$page_title = 'Profil Saya';
$current_user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profil') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $no_hp = trim($_POST['no_hp']);
        $alamat = trim($_POST['alamat']);
        
        // Validasi email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Format email tidak valid');
        } else {
            $result = query(
                "UPDATE users 
                SET name = ?, email = ?, no_hp = ?, alamat = ?
                WHERE id = ?
            ", [$name, $email, $no_hp, $alamat, $current_user['id']], 'ssssi');
            
            if ($result['success']) {
                setFlash('success', 'Profil berhasil diperbarui');
                redirect('admin/profil.php');
            } else {
                setFlash('danger', 'Gagal memperbarui profil');
            }
        }
    }
    
    if ($action == 'update_password') {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $password_konfirmasi = $_POST['password_konfirmasi'];
        
        // Cek password lama
        $user = query("SELECT password FROM users WHERE id = ?", [$current_user['id']], 'i')->fetch_assoc();
        
        if (!password_verify($password_lama, $user['password'])) {
            setFlash('danger', 'Password lama tidak sesuai');
        } elseif ($password_baru !== $password_konfirmasi) {
            setFlash('danger', 'Konfirmasi password tidak sesuai');
        } elseif (strlen($password_baru) < 6) {
            setFlash('danger', 'Password minimal 6 karakter');
        } else {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $result = query("UPDATE users SET password = ? WHERE id = ?", [$password_hash, $current_user['id']], 'si');
            
            if ($result['success']) {
                setFlash('success', 'Password berhasil diubah');
                redirect('admin/profil.php');
            } else {
                setFlash('danger', 'Gagal mengubah password');
            }
        }
    }
    
    if ($action == 'upload_foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                setFlash('danger', 'Format file harus JPG, JPEG, atau PNG');
            } else {
                $newname = 'foto_' . $current_user['id'] . '_' . time() . '.' . $ext;
                $upload_path = '../assets/img/uploads/users/' . $newname;
                
                if (!is_dir('../assets/img/uploads/users')) {
                    mkdir('../assets/img/uploads/users', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    // Hapus foto lama jika ada
                    if ($current_user['foto'] && file_exists('../' . $current_user['foto'])) {
                        unlink('../' . $current_user['foto']);
                    }
                    
                    $result = query("UPDATE users SET foto = ? WHERE id = ?", 
                                  ['assets/img/uploads/users/' . $newname, $current_user['id']], 'si');
                    
                    if ($result['success']) {
                        setFlash('success', 'Foto profil berhasil diupdate');
                        redirect('admin/profil.php');
                    } else {
                        setFlash('danger', 'Gagal menyimpan foto ke database');
                    }
                } else {
                    setFlash('danger', 'Gagal mengupload foto');
                }
            }
        } else {
            setFlash('warning', 'Pilih file foto terlebih dahulu');
        }
    }
}

// Refresh user data
$current_user = getCurrentUser();

// Statistik berdasarkan role
$stats = [];
if ($current_user['role'] == 'admin') {
    $stats = [
        'eskul' => query("SELECT COUNT(*) as total FROM ekstrakurikulers WHERE status = 'aktif'")->fetch_assoc()['total'],
        'siswa' => query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa' AND is_active = 1")->fetch_assoc()['total'],
        'anggota' => query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima'")->fetch_assoc()['total'],
        'pending' => query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'pending'")->fetch_assoc()['total']
    ];
} else { // pembina
    $stats = [
        'eskul' => query("SELECT COUNT(*) as total FROM ekstrakurikulers WHERE pembina_id = ? AND status = 'aktif'", [$current_user['id']], 'i')->fetch_assoc()['total'],
        'anggota' => query("SELECT COUNT(*) as total FROM anggota_ekskul ae JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id WHERE e.pembina_id = ? AND ae.status = 'diterima'", [$current_user['id']], 'i')->fetch_assoc()['total'],
        'pending' => query("SELECT COUNT(*) as total FROM anggota_ekskul ae JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id WHERE e.pembina_id = ? AND ae.status = 'pending'", [$current_user['id']], 'i')->fetch_assoc()['total'],
        'prestasi' => query("SELECT COUNT(*) as total FROM prestasis p JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id WHERE e.pembina_id = ?", [$current_user['id']], 'i')->fetch_assoc()['total']
    ];
}
?>
<?php include __DIR__ . '/../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../includes/berry_shell_open.php'; ?>

            <div class="p-4">
                <?php
                $flash = getFlash();
                if ($flash):
                ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-person-circle text-success"></i> Profil Saya</h2>
                        <p class="text-muted">Kelola informasi pribadi Anda</p>
                    </div>
                </div>

                <div class="row">
                    <!-- Profile Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body py-4">
                                <?php if ($current_user['foto']): ?>
                                <img src="<?php echo BASE_URL . $current_user['foto']; ?>" 
                                     class="rounded-circle mb-3 d-block mx-auto" 
                                     style="width: 180px; height: 180px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.2);" 
                                     alt="Foto Profil">
                                <?php else: ?>
                                <div class="rounded-circle mb-3 mx-auto bg-success text-white d-flex align-items-center justify-content-center" style="width: 180px; height: 180px; font-size: 5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <?php endif; ?>
                                
                                <h4 class="mb-1"><?php echo $current_user['name']; ?></h4>
                                <p class="text-muted mb-3">
                                    <span class="badge bg-<?php echo $current_user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($current_user['role']); ?>
                                    </span>
                                </p>
                                
                                <button class="btn btn-success btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#modalFoto">
                                    <i class="bi bi-camera"></i> Ubah Foto
                                </button>
                                
                                <hr>
                                
                                <div class="text-start">
                                    <p class="mb-2"><i class="bi bi-envelope text-success"></i> <strong>Email:</strong> <?php echo $current_user['email']; ?></p>
                                    <p class="mb-2"><i class="bi bi-telephone text-success"></i> <strong>Telepon:</strong> <?php echo $current_user['no_hp'] ?: '-'; ?></p>
                                    <p class="mb-0"><i class="bi bi-geo-alt text-success"></i> <strong>Alamat:</strong> <?php echo $current_user['alamat'] ?: '-'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Card -->
                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Statistik</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($current_user['role'] == 'admin'): ?>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span><i class="bi bi-grid text-success"></i> Eskul Aktif</span>
                                    <strong><?php echo $stats['eskul']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span><i class="bi bi-people text-primary"></i> Total Siswa</span>
                                    <strong><?php echo $stats['siswa']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span><i class="bi bi-person-check text-success"></i> Anggota Aktif</span>
                                    <strong><?php echo $stats['anggota']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-clock text-warning"></i> Pending</span>
                                    <strong><?php echo $stats['pending']; ?></strong>
                                </div>
                                <?php else: // pembina ?>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span><i class="bi bi-grid text-success"></i> Eskul Diampu</span>
                                    <strong><?php echo $stats['eskul']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span><i class="bi bi-people text-primary"></i> Total Anggota</span>
                                    <strong><?php echo $stats['anggota']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span><i class="bi bi-clock text-warning"></i> Pending</span>
                                    <strong><?php echo $stats['pending']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-trophy text-warning"></i> Prestasi</span>
                                    <strong><?php echo $stats['prestasi']; ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Form Section -->
                    <div class="col-md-8">
                        <!-- Update Profil -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-person-gear"></i> Update Profil</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profil">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" 
                                                   value="<?php echo $current_user['name']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control" 
                                                   value="<?php echo $current_user['email']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">No. Telepon</label>
                                            <input type="text" name="no_hp" class="form-control" 
                                                   value="<?php echo $current_user['no_hp'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo ucfirst($current_user['role']); ?>" disabled>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="alamat" class="form-control" rows="3"><?php echo $current_user['alamat'] ?? ''; ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Ubah Password -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Ubah Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_password">
                                    <div class="mb-3">
                                        <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                                        <input type="password" name="password_lama" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                        <input type="password" name="password_baru" class="form-control" 
                                               minlength="6" required>
                                        <small class="text-muted">Minimal 6 karakter</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                        <input type="password" name="password_konfirmasi" class="form-control" 
                                               minlength="6" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key"></i> Ubah Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Modal Upload Foto -->
    <div class="modal fade" id="modalFoto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Foto Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_foto">
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <?php if ($current_user['foto']): ?>
                            <img src="<?php echo BASE_URL . $current_user['foto']; ?>" 
                                 class="rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover;"
                                 alt="Current Photo">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Foto Baru</label>
                            <input type="file" name="foto" class="form-control" 
                                   accept="image/jpeg,image/jpg,image/png" required>
                            <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/berry_shell_close.php'; ?>
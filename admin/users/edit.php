<?php
// admin/users/edit.php
require_once '../../config/database.php';
requireRole(['admin']);

$page_title = 'Edit User';
$current_user = getCurrentUser();
$id = $_GET['id'] ?? 0;

// Ambil data user
$user = query("SELECT * FROM users WHERE id = ?", [$id], 'i');
if (!$user || $user->num_rows == 0) {
    setFlash('danger', 'User tidak ditemukan!');
    redirect('admin/users/index.php');
}
$data = $user->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update Profil
    if ($action == 'update_profil') {
        $name = trim($_POST['name']);
        $email = $_POST['email'] ?? NULL;
        $nis = $_POST['nis'] ?? NULL;
        $kelas = $_POST['kelas'] ?? NULL;
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? NULL;
        $no_hp = $_POST['no_hp'] ?? NULL;
        $alamat = $_POST['alamat'] ?? NULL;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $result = execute("
            UPDATE users 
            SET name = ?, email = ?, nis = ?, kelas = ?, jenis_kelamin = ?, no_hp = ?, alamat = ?, is_active = ?
            WHERE id = ?
        ", [$name, $email, $nis, $kelas, $jenis_kelamin, $no_hp, $alamat, $is_active, $id], 'sssssssii');
        
        if ($result['success']) {
            setFlash('success', 'Profil user berhasil diupdate!');
            redirect('admin/users/edit.php?id=' . $id);
        } else {
            setFlash('danger', 'Gagal update profil user!');
        }
    }
    
    // Reset Password
    if ($action == 'reset_password') {
        $password_baru = $_POST['password_baru'];
        $password_konfirmasi = $_POST['password_konfirmasi'];
        
        if ($password_baru !== $password_konfirmasi) {
            setFlash('danger', 'Konfirmasi password tidak sesuai!');
        } elseif (strlen($password_baru) < 6) {
            setFlash('danger', 'Password minimal 6 karakter!');
        } else {
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $result = execute("UPDATE users SET password = ? WHERE id = ?", [$password_hash, $id], 'si');
            
            if ($result['success']) {
                setFlash('success', 'Password berhasil direset!');
                redirect('admin/users/edit.php?id=' . $id);
            } else {
                setFlash('danger', 'Gagal reset password!');
            }
        }
    }
    
    // Upload Foto
    if ($action == 'upload_foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                setFlash('danger', 'Format file harus JPG, JPEG, atau PNG');
            } else {
                $newname = 'foto_' . $id . '_' . time() . '.' . $ext;
                $upload_path = '../../assets/img/uploads/users/' . $newname;
                
                if (!is_dir('../../assets/img/uploads/users')) {
                    mkdir('../../assets/img/uploads/users', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    // Hapus foto lama jika ada
                    if ($data['foto'] && file_exists('../../' . $data['foto'])) {
                        unlink('../../' . $data['foto']);
                    }
                    
                    $result = execute("UPDATE users SET foto = ? WHERE id = ?", 
                                  ['assets/img/uploads/users/' . $newname, $id], 'si');
                    
                    if ($result['success']) {
                        setFlash('success', 'Foto user berhasil diupdate!');
                        redirect('admin/users/edit.php?id=' . $id);
                    }
                }
            }
        } else {
            setFlash('warning', 'Pilih file foto terlebih dahulu');
        }
    }
}

// Refresh data
$user = query("SELECT * FROM users WHERE id = ?", [$id], 'i');
$data = $user->fetch_assoc();

// Statistik user (jika siswa)
$stats = [];
if ($data['role'] == 'siswa') {
    $stats = [
        'eskul' => query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'", [$id], 'i')->fetch_assoc()['total'],
        'prestasi' => query("SELECT COUNT(*) as total FROM prestasis p JOIN anggota_ekskul ae ON p.anggota_id = ae.id WHERE ae.user_id = ?", [$id], 'i')->fetch_assoc()['total'],
        'presensi' => query("SELECT COUNT(*) as total FROM presensis p JOIN anggota_ekskul ae ON p.anggota_id = ae.id WHERE ae.user_id = ?", [$id], 'i')->fetch_assoc()['total']
    ];
} elseif ($data['role'] == 'pembina') {
    $stats = [
        'eskul' => query("SELECT COUNT(*) as total FROM ekstrakurikulers WHERE pembina_id = ?", [$id], 'i')->fetch_assoc()['total'],
        'anggota' => query("SELECT COUNT(*) as total FROM anggota_ekskul ae JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id WHERE e.pembina_id = ? AND ae.status = 'diterima'", [$id], 'i')->fetch_assoc()['total']
    ];
}
?>

<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
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

    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>admin/users/index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-person-gear text-success"></i> Edit User: <?php echo $data['name']; ?></h2>
            <p class="text-muted">Kelola profil dan password user</p>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-4">
                    <?php if ($data['foto']): ?>
                    <img src="<?php echo BASE_URL . $data['foto']; ?>" 
                         class="rounded-circle profile-img mb-3" 
                         alt="Foto Profil">
                    <?php else: ?>
                    <div class="rounded-circle profile-img mb-3 mx-auto bg-success text-white d-flex align-items-center justify-content-center" style="font-size: 5rem;">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <?php endif; ?>
                    
                    <h4 class="mb-1"><?php echo $data['name']; ?></h4>
                    <p class="text-muted mb-3">
                        <span class="badge bg-<?php echo $data['role'] == 'admin' ? 'danger' : ($data['role'] == 'pembina' ? 'primary' : 'info'); ?>">
                            <?php echo ucfirst($data['role']); ?>
                        </span>
                        <?php if ($data['is_active']): ?>
                        <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </p>
                    
                    <button class="btn btn-success btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#modalFoto">
                        <i class="bi bi-camera"></i> Ubah Foto
                    </button>
                    
                    <hr>
                    
                    <div class="text-start">
                        <?php if ($data['role'] == 'siswa'): ?>
                        <p class="mb-2"><i class="bi bi-person-badge text-success"></i> <strong>NIS:</strong> <?php echo $data['nis']; ?></p>
                        <p class="mb-2"><i class="bi bi-book text-success"></i> <strong>Kelas:</strong> <?php echo $data['kelas'] ?: '-'; ?></p>
                        <?php else: ?>
                        <p class="mb-2"><i class="bi bi-envelope text-success"></i> <strong>Email:</strong> <?php echo $data['email']; ?></p>
                        <?php endif; ?>
                        <p class="mb-2"><i class="bi bi-telephone text-success"></i> <strong>Telepon:</strong> <?php echo $data['no_hp'] ?: '-'; ?></p>
                        <p class="mb-0"><i class="bi bi-calendar text-success"></i> <strong>Terdaftar:</strong> <?php echo date('d M Y', strtotime($data['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <?php if (!empty($stats)): ?>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> Statistik</h6>
                </div>
                <div class="card-body">
                    <?php if ($data['role'] == 'siswa'): ?>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span><i class="bi bi-grid text-success"></i> Eskul Diikuti</span>
                        <strong><?php echo $stats['eskul']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span><i class="bi bi-trophy text-warning"></i> Prestasi</span>
                        <strong><?php echo $stats['prestasi']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-clipboard-check text-success"></i> Presensi</span>
                        <strong><?php echo $stats['presensi']; ?></strong>
                    </div>
                    <?php elseif ($data['role'] == 'pembina'): ?>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span><i class="bi bi-grid text-success"></i> Eskul Diampu</span>
                        <strong><?php echo $stats['eskul']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-people text-primary"></i> Total Anggota</span>
                        <strong><?php echo $stats['anggota']; ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Form Section -->
        <div class="col-md-8">
            <!-- Update Profil -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person-gear"></i> Update Profil User</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profil">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($data['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo ucfirst($data['role']); ?>" disabled>
                            </div>

                            <?php if ($data['role'] == 'siswa'): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NIS</label>
                                <input type="text" name="nis" class="form-control" 
                                       value="<?php echo $data['nis']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="kelas" class="form-control" 
                                       value="<?php echo $data['kelas']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select">
                                    <option value="">Pilih</option>
                                    <option value="L" <?php echo $data['jenis_kelamin'] == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="P" <?php echo $data['jenis_kelamin'] == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                            <?php else: ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $data['email']; ?>">
                            </div>
                            <?php endif; ?>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="no_hp" class="form-control" 
                                       value="<?php echo $data['no_hp'] ?? ''; ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2"><?php echo $data['alamat'] ?? ''; ?></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                        <?php echo $data['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Akun Aktif</strong> (centang untuk mengaktifkan akun)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reset Password -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Reset Password User</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Perhatian!</strong> Anda akan mereset password user ini. Pastikan untuk memberitahu password baru kepada user.
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" name="password_baru" class="form-control" 
                                       minlength="6" required>
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" name="password_konfirmasi" class="form-control" 
                                       minlength="6" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Yakin ingin reset password user ini?')">
                            <i class="bi bi-key"></i> Reset Password
                        </button>
                    </form>
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
        <h5 class="modal-title">Ubah Foto User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_foto">
        <div class="modal-body">
          <div class="text-center mb-3">
            <?php if ($data['foto']): ?>
            <img src="<?php echo BASE_URL . $data['foto']; ?>" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;" alt="Current Photo">
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Pilih Foto Baru</label>
            <input type="file" name="foto" class="form-control" accept="image/jpeg,image/jpg,image/png" required>
            <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>
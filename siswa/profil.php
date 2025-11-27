<?php
require_once '../config/database.php';
require_once '../config/middleware.php';
requireRole(['siswa']);

$page_title = 'Profil Saya';
$current_user = getCurrentUser();
if (!$current_user) {
    setFlash('danger', 'Akun tidak ditemukan atau session kadaluarsa.');
    redirect('logout.php');  // atau redirect ke login
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $no_hp = trim($_POST['no_hp']);
        $alamat = trim($_POST['alamat']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Format email tidak valid');
        } else {
            $result = query(
                "UPDATE users SET name = ?, email = ?, no_hp = ?, alamat = ? WHERE id = ?",
                [$name, $email, $no_hp, $alamat, $current_user['id']],
                'ssssi'
            );
            if ($result['success']) {
                setFlash('success', 'Profil berhasil diperbarui');
                redirect('siswa/profil.php');
            } else {
                setFlash('danger', 'Gagal memperbarui profil');
            }
        }
    }

    if ($action === 'update_password') {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $password_konfirmasi = $_POST['password_konfirmasi'];
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
                redirect('siswa/profil.php');
            } else {
                setFlash('danger', 'Gagal mengubah password');
            }
        }
    }

    if ($action === 'upload_foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                setFlash('danger', 'Format file harus JPG, JPEG, atau PNG');
            } else {
                $newname = 'foto_' . $current_user['id'] . '_' . time() . '.' . $ext;
                $upload_dir = '../assets/img/uploads/users';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $upload_path = $upload_dir . '/' . $newname;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    if ($current_user['foto'] && file_exists('../' . $current_user['foto'])) {
                        unlink('../' . $current_user['foto']);
                    }
                    $result = query(
                        "UPDATE users SET foto = ? WHERE id = ?",
                        ['assets/img/uploads/users/' . $newname, $current_user['id']],
                        'si'
                    );
                    if ($result['success']) {
                        setFlash('success', 'Foto profil berhasil diupdate');
                        redirect('siswa/profil.php');
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

$current_user = getCurrentUser();

$stats = [
    'eskul' => query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'", [$current_user['id']], 'i')->fetch_assoc()['total'],
    'prestasi' => query("SELECT COUNT(*) as total FROM prestasis p JOIN anggota_ekskul ae ON p.anggota_id = ae.id WHERE ae.user_id = ?", [$current_user['id']], 'i')->fetch_assoc()['total'],
    'presensi' => query("SELECT COUNT(*) as total FROM presensis p JOIN anggota_ekskul ae ON p.anggota_id = ae.id WHERE ae.user_id = ?", [$current_user['id']], 'i')->fetch_assoc()['total'],
];

$eskul_query = query("
    SELECT ae.id, e.nama_ekskul, ae.tanggal_diterima
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY ae.tanggal_diterima DESC
", [$current_user['id']], 'i');
$eskul_items = $eskul_query ? $eskul_query->fetch_all(MYSQLI_ASSOC) : [];

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.profile-img {
  width: 200px;
  height: 200px;
  object-fit: cover;
  border: 5px solid #fff;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.eskul-card {
  transition: all 0.3s;
  border-left: 4px solid #198754;
}
.eskul-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>

<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
  <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
    <?php echo $flash['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-primary mb-2"><i class="bi bi-person"></i> Profil Siswa</span>
    <h3 class="fw-bold mb-1">Kelola Profil Anda</h3>
    <p class="text-muted mb-0">Perbarui informasi pribadi dan keamanan akun ekstrakurikuler.</p>
  </div>
</div>

<div class="row">
  <div class="col-lg-4 mb-4">
    <div class="card border-0 shadow-sm text-center">
      <div class="card-body py-4">
        <?php if (!empty($current_user['foto'])): ?>
          <img src="<?php echo BASE_URL . $current_user['foto']; ?>" class="rounded-circle profile-img mb-3" alt="Foto Profil">
        <?php else: ?>
          <div class="rounded-circle profile-img mb-3 mx-auto bg-primary text-white d-flex align-items-center justify-content-center" style="font-size:4rem;">
            <i class="bi bi-person"></i>
          </div>
        <?php endif; ?>
        <h4 class="mb-1"><?php echo htmlspecialchars($current_user['name']); ?></h4>
        <p class="text-muted mb-3"><span class="badge bg-primary text-uppercase">Siswa</span></p>
        <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#modalFoto">
          <i class="bi bi-camera"></i> Ubah Foto
        </button>
        <hr>
        <div class="text-start small">
          <p class="mb-2"><i class="bi bi-person-badge text-primary"></i> <strong>NISN:</strong> <?php echo htmlspecialchars($current_user['nisn']); ?></p>
          <p class="mb-2"><i class="bi bi-envelope text-primary"></i> <strong>Email:</strong> <?php echo htmlspecialchars($current_user['email']); ?></p>
          <p class="mb-2"><i class="bi bi-telephone text-primary"></i> <strong>Telepon:</strong> <?php echo $current_user['no_hp'] ?: '-'; ?></p>
          <p class="mb-2"><i class="bi bi-book text-primary"></i> <strong>Kelas:</strong> <?php echo $current_user['kelas'] ?: '-'; ?></p>
          <p class="mb-0"><i class="bi bi-geo-alt text-primary"></i> <strong>Alamat:</strong> <?php echo $current_user['alamat'] ?: '-'; ?></p>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0 text-white"><i class="bi bi-graph-up"></i> Statistik</h6>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
          <span><i class="bi bi-grid text-primary"></i> Eskul Diikuti</span>
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
      </div>
    </div>

    <?php if (!empty($eskul_items)): ?>
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-success text-white">
        <h6 class="mb-0 text-white" ><i class="bi bi-credit-card-2-front"></i> Kartu Anggota</h6>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3"><i class="bi bi-info-circle"></i> Cetak kartu anggota untuk setiap ekstrakurikuler.</p>
        <?php foreach ($eskul_items as $eskul): ?>
          <div class="mb-2">
            <a href="<?php echo BASE_URL; ?>siswa/cetak_kartu.php?id=<?php echo $eskul['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success w-100">
              <i class="bi bi-printer"></i> Cetak Kartu <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
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
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">NISN</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['nisn']); ?>" disabled>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Kelas</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['kelas']); ?>" disabled>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">No. Telepon</label>
              <input type="text" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($current_user['no_hp'] ?? ''); ?>">
            </div>
            <div class="col-md-12 mb-3">
              <label class="form-label">Alamat</label>
              <textarea name="alamat" class="form-control" rows="3"><?php echo htmlspecialchars($current_user['alamat'] ?? ''); ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
        </form>
      </div>
    </div>

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
            <input type="password" name="password_baru" class="form-control" minlength="6" required>
            <small class="text-muted">Minimal 6 karakter</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
            <input type="password" name="password_konfirmasi" class="form-control" minlength="6" required>
          </div>
          <button type="submit" class="btn btn-warning text-white"><i class="bi bi-key"></i> Ubah Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
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
            <?php if (!empty($current_user['foto'])): ?>
              <img src="<?php echo BASE_URL . $current_user['foto']; ?>" class="rounded-circle" style="width:150px;height:150px;object-fit:cover;" alt="Current Photo">
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Pilih Foto Baru</label>
            <input type="file" name="foto" class="form-control" accept="image/jpeg,image/jpg,image/png" required>
            <small class="text-muted">Format JPG/PNG, maksimal 2MB.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>


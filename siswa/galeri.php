<?php
// siswa/galeri.php - dengan Fitur Upload Galeri
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$page_title = 'Galeri';
$current_user = getCurrentUser();

//  PROSES UPLOAD GALERI 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_galeri'])) {
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'];
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $tanggal_upload = date('Y-m-d');
    
    // Validasi: Cek apakah siswa terdaftar di eskul tersebut
    $cek_anggota = query("
        SELECT id FROM anggota_ekskul 
        WHERE user_id = ? AND ekstrakurikuler_id = ? AND status = 'diterima'
    ", [$current_user['id'], $ekstrakurikuler_id], 'ii');
    
    if (!$cek_anggota || $cek_anggota->num_rows == 0) {
        setFlash('danger', 'Anda tidak terdaftar di ekstrakurikuler ini!');
        redirect('siswa/galeri.php');
    }
    
    // Upload gambar
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            setFlash('danger', 'Format file harus JPG, JPEG, PNG, atau GIF');
            redirect('siswa/galeri.php');
        }
        
        // Validasi ukuran (max 5MB)
        if ($_FILES['gambar']['size'] > 5000000) {
            setFlash('danger', 'Ukuran file maksimal 5MB');
            redirect('siswa/galeri.php');
        }
        
        $newname = 'galeri_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_dir = '../assets/img/uploads/galeri/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_path = $upload_dir . $newname;
        
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
            $gambar_path = 'galeri/' . $newname;
            
            // Insert ke database
            $result = query("
                INSERT INTO galeris (ekstrakurikuler_id, judul, deskripsi, gambar, tanggal_upload, is_active, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, 1, ?)
            ", [$ekstrakurikuler_id, $judul, $deskripsi, $gambar_path, $tanggal_upload, $current_user['id']], 'issssi');
            
            if ($result['success']) {
                setFlash('success', 'Foto berhasil diupload ke galeri!');
            } else {
                setFlash('danger', 'Gagal menyimpan foto ke database.');
            }
        } else {
            setFlash('danger', 'Gagal mengupload file.');
        }
    } else {
        setFlash('danger', 'Silakan pilih gambar untuk diupload.');
    }
    
    redirect('siswa/galeri.php');
}


//  HAPUS GALERI (Hanya foto yang diupload sendiri) 

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Cek apakah foto ini milik user yang login
    $cek = query("SELECT gambar FROM galeris WHERE id = ? AND uploaded_by = ?", [$id, $current_user['id']], 'ii');
    
    if ($cek && $cek->num_rows > 0) {
        $data = $cek->fetch_assoc();
        
        // Hapus file fisik
        $file_path = '../assets/img/uploads/' . $data['gambar'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Hapus dari database
        $result = query("DELETE FROM galeris WHERE id = ?", [$id], 'i');
        
        if ($result['success']) {
            setFlash('success', 'Foto berhasil dihapus dari galeri.');
        } else {
            setFlash('danger', 'Gagal menghapus foto.');
        }
    } else {
        setFlash('danger', 'Anda tidak memiliki izin untuk menghapus foto ini.');
    }
    
    redirect('siswa/galeri.php');
}

$eskul_filter = isset($_GET['eskul']) ? $_GET['eskul'] : '';

$where_clause = "ae.user_id = ? AND ae.status = 'diterima' AND g.is_active = 1";
$params = [$current_user['id']];
$types = 'i';

if ($eskul_filter) {
    $where_clause .= " AND e.id = ?";
    $params[] = $eskul_filter;
    $types .= 'i';
}

$galeri = query("
    SELECT g.*, e.nama_ekskul, g.uploaded_by
    FROM galeris g
    JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE $where_clause
    ORDER BY g.tanggal_upload DESC, g.urutan ASC
", $params, $types);

$eskul_list = query("
    SELECT DISTINCT e.id, e.nama_ekskul
    FROM ekstrakurikulers e
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY e.nama_ekskul
", [$current_user['id']], 'i');

// List eskul untuk form upload
$eskul_for_upload = query("
    SELECT DISTINCT e.id, e.nama_ekskul
    FROM ekstrakurikulers e
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY e.nama_ekskul
", [$current_user['id']], 'i');

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.gallery-item {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 8px 20px rgba(15,23,42,0.15);
}
.gallery-item img {
  width: 100%;
  height: 240px;
  object-fit: cover;
}
.gallery-item:hover {
  transform: translateY(-4px);
}
.gallery-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(0deg, rgba(15,23,42,.9) 0%, rgba(15,23,42,0) 100%);
  color: #fff;
  padding: 16px;
}
.owner-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
}
</style>

<?php
$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
    <?php echo $flash['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-primary mb-2"><i class="bi bi-images"></i> Dokumentasi</span>
    <h3 class="fw-bold mb-1">Galeri Ekstrakurikuler</h3>
    <p class="text-muted mb-0">Kumpulan momen terbaik dari kegiatan eskul yang Anda ikuti.</p>
  </div>
  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadGaleriModal">
    <i class="bi bi-cloud-upload"></i> Upload Foto
  </button>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Filter Ekstrakurikuler</label>
                <select name="eskul" class="form-select">
                    <option value="">Semua Ekstrakurikuler</option>
                    <?php 
                    $eskul_list->data_seek(0);
                    while ($e = $eskul_list->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo $eskul_filter == $e['id'] ? 'selected' : ''; ?>>
                        <?php echo $e['nama_ekskul']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($galeri && $galeri->num_rows > 0): ?>
  <div class="row g-4">
    <?php while ($g = $galeri->fetch_assoc()): ?>
      <?php
        $img_path = str_replace('uploads/', '', $g['gambar']);
        $full_path = BASE_URL . 'assets/img/uploads/' . $img_path;
        $is_owner = ($g['uploaded_by'] == $current_user['id']);
      ?>
      <div class="col-md-4">
        <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modal<?php echo $g['id']; ?>">
          <?php if ($is_owner): ?>
          <span class="owner-badge badge bg-success">
            <i class="bi bi-person-check"></i> Foto Saya
          </span>
          <?php endif; ?>
          <img src="<?php echo $full_path; ?>" alt="<?php echo htmlspecialchars($g['judul']); ?>"
               onerror="this.onerror=null;this.src='https://via.placeholder.com/400x300/198754/ffffff?text=No+Image';">
          <div class="gallery-overlay">
            <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($g['judul']); ?></h6>
            <small>
              <span class="badge bg-primary"><?php echo htmlspecialchars($g['nama_ekskul']); ?></span>
              <span class="ms-2"><i class="bi bi-calendar3"></i> <?php echo date('d M Y', strtotime($g['tanggal_upload'])); ?></span>
            </small>
          </div>
        </div>
      </div>

      <div class="modal fade" id="modal<?php echo $g['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?php echo htmlspecialchars($g['judul']); ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
              <img src="<?php echo $full_path; ?>" class="img-fluid rounded mb-3"
                   alt="<?php echo htmlspecialchars($g['judul']); ?>"
                   onerror="this.onerror=null;this.src='https://via.placeholder.com/800x600/198754/ffffff?text=No+Image';">
              <div class="mb-3">
                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($g['nama_ekskul']); ?></span>
                <small class="text-muted"><i class="bi bi-calendar3"></i> <?php echo date('d F Y', strtotime($g['tanggal_upload'])); ?></small>
                <?php if ($is_owner): ?>
                <span class="badge bg-success ms-2"><i class="bi bi-person-check"></i> Diupload oleh Anda</span>
                <?php endif; ?>
              </div>
              <?php if ($g['deskripsi']): ?>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($g['deskripsi'])); ?></p>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <a href="<?php echo $full_path; ?>" download class="btn btn-primary">
                <i class="bi bi-download"></i> Download
              </a>
              <?php if ($is_owner): ?>
              <a href="?action=delete&id=<?php echo $g['id']; ?>" class="btn btn-danger" 
                 onclick="return confirm('Yakin ingin menghapus foto ini?')">
                <i class="bi bi-trash"></i> Hapus
              </a>
              <?php endif; ?>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-images text-muted" style="font-size:4rem;opacity:.2;"></i>
      <h5 class="mt-3 text-muted">Belum ada galeri</h5>
      <p class="text-muted mb-3">Belum ada dokumentasi untuk ekstrakurikuler yang Anda ikuti.</p>
      <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadGaleriModal">
        <i class="bi bi-cloud-upload"></i> Upload Foto Pertama
      </button>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert-info mt-4">
  <i class="bi bi-info-circle"></i> Klik foto untuk melihat detail. Anda dapat mengupload dan menghapus foto yang Anda upload sendiri.
</div>

<!-- Modal Upload Galeri -->
<div class="modal fade" id="uploadGaleriModal" tabindex="-1" aria-labelledby="uploadGaleriModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="uploadGaleriModalLabel">
                        <i class="bi bi-cloud-upload"></i> Upload Foto ke Galeri
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="upload_galeri" value="1">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Ketentuan Upload:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Format: JPG, JPEG, PNG, atau GIF</li>
                            <li>Ukuran maksimal: 5MB</li>
                            <li>Hanya foto kegiatan ekstrakurikuler yang Anda ikuti</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ekstrakurikuler_id" class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                        <select name="ekstrakurikuler_id" id="ekstrakurikuler_id" class="form-select" required>
                            <option value="">-- Pilih Ekstrakurikuler --</option>
                            <?php while ($e = $eskul_for_upload->fetch_assoc()): ?>
                            <option value="<?php echo $e['id']; ?>">
                                <?php echo htmlspecialchars($e['nama_ekskul']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="judul" class="form-label">Judul Foto <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="judul" class="form-control" 
                               placeholder="Contoh: Latihan Basket Rutin" required maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3" 
                                  placeholder="Tambahkan deskripsi singkat tentang foto ini (opsional)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="gambar" class="form-label">Pilih Foto <span class="text-danger">*</span></label>
                        <input type="file" name="gambar" id="gambar" class="form-control" 
                               accept="image/jpeg,image/jpg,image/png,image/gif" required>
                    </div>
                    
                    <div id="preview" class="text-center d-none">
                        <label class="form-label">Preview:</label>
                        <img id="preview-img" src="" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-cloud-upload"></i> Upload Foto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview gambar sebelum upload
document.getElementById('gambar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('preview-img').src = event.target.result;
            document.getElementById('preview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>
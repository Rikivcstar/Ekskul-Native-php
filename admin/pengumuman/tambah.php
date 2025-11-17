<?php
// admin/pengumuman/tambah.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$page_title = 'Tambah Pengumuman';
$current_user = getCurrentUser();
$edit_mode = false;
$data = null;

// Check edit mode
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $result = query("SELECT * FROM pengumuman WHERE id = ?", [$id], 'i');
    $data = $result->fetch_assoc();
    $page_title = 'Edit Pengumuman';
}

// Ambil daftar eskul
$where_eskul = "";
$params_eskul = [];
$types_eskul = "";

if ($current_user['role'] == 'pembina') {
    $where_eskul = "WHERE pembina_id = ?";
    $params_eskul = [$current_user['id']];
    $types_eskul = "i";
}

$eskul_list = query("SELECT id, nama_ekskul FROM ekstrakurikulers $where_eskul ORDER BY nama_ekskul", $params_eskul, $types_eskul);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'] ?: NULL;
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $tanggal_mulai = $_POST['tanggal_mulai'] ?: NULL;
    $tanggal_selesai = $_POST['tanggal_selesai'] ?: NULL;
    $prioritas = $_POST['prioritas'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($edit_mode) {
        $sql = "UPDATE pengumuman SET 
                ekstrakurikuler_id = ?, judul = ?, isi = ?, tanggal_mulai = ?, 
                tanggal_selesai = ?, prioritas = ?, is_active = ?
                WHERE id = ?";
        $result = execute($sql, [$ekstrakurikuler_id, $judul, $isi, $tanggal_mulai, $tanggal_selesai, $prioritas, $is_active, $id], 'issssiii');
    } else {
        $sql = "INSERT INTO pengumuman (ekstrakurikuler_id, user_id, judul, isi, tanggal_mulai, tanggal_selesai, prioritas, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $result = execute($sql, [$ekstrakurikuler_id, $current_user['id'], $judul, $isi, $tanggal_mulai, $tanggal_selesai, $prioritas, $is_active], 'iisssssi');
    }
    
    if ($result['success']) {
        setFlash('success', 'Pengumuman berhasil ' . ($edit_mode ? 'diupdate' : 'ditambahkan') . '!');
        redirect('admin/pengumuman/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-success sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">
                    <i class="bi bi-person-circle"></i> <?php echo $current_user['name']; ?>
                </span>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4">
                    <a href="<?php echo BASE_URL; ?>admin/pengumuman/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <h2 class="mb-4">
                    <i class="bi bi-<?php echo $edit_mode ? 'pencil-square' : 'plus-circle'; ?>"></i> 
                    <?php echo $page_title; ?>
                </h2>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Ekstrakurikuler</label>
                                <select name="ekstrakurikuler_id" class="form-select">
                                    <option value="">-- Pengumuman Umum (Semua Eskul) --</option>
                                    <?php while ($eskul = $eskul_list->fetch_assoc()): ?>
                                    <option value="<?php echo $eskul['id']; ?>" 
                                        <?php echo ($edit_mode && $data['ekstrakurikuler_id'] == $eskul['id']) ? 'selected' : ''; ?>>
                                        <?php echo $eskul['nama_ekskul']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Kosongkan jika pengumuman untuk semua ekstrakurikuler</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Judul Pengumuman <span class="text-danger">*</span></label>
                                <input type="text" name="judul" class="form-control" 
                                    value="<?php echo $edit_mode ? htmlspecialchars($data['judul']) : ''; ?>" 
                                    placeholder="Contoh: Pendaftaran Ekstrakurikuler Dibuka" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Isi Pengumuman <span class="text-danger">*</span></label>
                                <textarea name="isi" class="form-control" rows="6" required><?php echo $edit_mode ? htmlspecialchars($data['isi']) : ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Mulai</label>
                                        <input type="date" name="tanggal_mulai" class="form-control" 
                                            value="<?php echo $edit_mode ? $data['tanggal_mulai'] : date('Y-m-d'); ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Selesai</label>
                                        <input type="date" name="tanggal_selesai" class="form-control" 
                                            value="<?php echo $edit_mode ? $data['tanggal_selesai'] : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                                        <select name="prioritas" class="form-select" required>
                                            <option value="rendah" <?php echo ($edit_mode && $data['prioritas'] == 'rendah') ? 'selected' : ''; ?>>Rendah</option>
                                            <option value="sedang" <?php echo (!$edit_mode || $data['prioritas'] == 'sedang') ? 'selected' : ''; ?>>Sedang</option>
                                            <option value="tinggi" <?php echo ($edit_mode && $data['prioritas'] == 'tinggi') ? 'selected' : ''; ?>>Tinggi</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                                <?php echo (!$edit_mode || $data['is_active']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                Aktifkan Pengumuman
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="text-end">
                                <a href="<?php echo BASE_URL; ?>admin/pengumuman/index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save"></i> <?php echo $edit_mode ? 'Update' : 'Simpan'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
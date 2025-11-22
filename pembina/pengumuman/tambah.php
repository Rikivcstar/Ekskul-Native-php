<?php
require_once '../../config/database.php';
require_once '../../config/middleware.php';
only('pembina');
requireRole(['pembina']);

$page_title = isset($_GET['edit']) ? 'Edit Pengumuman' : 'Tambah Pengumuman';
$current_user = getCurrentUser();

$edit_id = $_GET['edit'] ?? null;
$data = null;

// Jika edit, ambil datanya
if ($edit_id) {
    $data = query("SELECT * FROM pengumuman WHERE id = ?", [$edit_id], "i")->fetch_assoc();
    if (!$data) {
        setFlash('danger', 'Data pengumuman tidak ditemukan!');
        redirect('pembina/pengumuman/index.php');
    }
}

// Ambil daftar ekskul berdasarkan role
if ($current_user['role'] == 'pembina'){
    $ekskul = query("SELECT * FROM ekstrakurikulers WHERE pembina_id = ?", [$current_user['id']], "i");
} else {
    $ekskul = query("SELECT * FROM ekstrakurikulers ORDER BY nama_ekskul ASC");
}

// Submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $prioritas = $_POST['prioritas'];
    $tanggal_mulai = $_POST['tanggal_mulai'] ?: null;
    $tanggal_selesai = $_POST['tanggal_selesai'] ?: null;

    // Ekskul bisa null (Umum)
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'] ?: null;

    if ($edit_id) {
        // Update
        query("
            UPDATE pengumuman SET 
                judul = ?, isi = ?, prioritas = ?, ekstrakurikuler_id = ?, 
                tanggal_mulai = ?, tanggal_selesai = ?
            WHERE id = ?
        ", [
            $judul, $isi, $prioritas, $ekstrakurikuler_id,
            $tanggal_mulai, $tanggal_selesai, $edit_id
        ], "sssissi");

        setFlash('success', 'Pengumuman berhasil diupdate!');
    } else {
        // Insert
        query("
            INSERT INTO pengumuman 
                (judul, isi, prioritas, ekstrakurikuler_id, tanggal_mulai, tanggal_selesai, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $judul, $isi, $prioritas, $ekstrakurikuler_id,
            $tanggal_mulai, $tanggal_selesai, $current_user['id']
        ], "sssissi");

        setFlash('success', 'Pengumuman berhasil ditambahkan!');
    }

    redirect('pembina/pengumuman/index.php');
}

?>

<?php include '../../includes/berry_head.php'; ?>
<?php include '../../includes/berry_shell_open.php'; ?>

<div class="p-4">
    <h2><i class="bi bi-megaphone"></i> <?= $page_title; ?></h2>

    <form method="POST" class="mt-4">

        <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control" required
                   value="<?= $data['judul'] ?? '' ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Isi Pengumuman</label>
            <textarea name="isi" class="form-control" rows="5" required><?= $data['isi'] ?? '' ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Ekskul (Opsional)</label>
            <select name="ekstrakurikuler_id" class="form-select">
                <option value="">Untuk Semua Ekskul</option>

                <?php while ($row = $ekskul->fetch_assoc()): ?>
                    <option value="<?= $row['id']; ?>"
                        <?= (isset($data['ekstrakurikuler_id']) && $data['ekstrakurikuler_id'] == $row['id']) ? 'selected' : '' ?>>
                        <?= $row['nama_ekskul']; ?>
                    </option>
                <?php endwhile; ?>

            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Prioritas</label>
            <select name="prioritas" class="form-select" required>
                <option value="tinggi" <?= ($data['prioritas'] ?? '') == 'tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                <option value="sedang" <?= ($data['prioritas'] ?? '') == 'sedang' ? 'selected' : ''; ?>>Sedang</option>
                <option value="rendah" <?= ($data['prioritas'] ?? '') == 'rendah' ? 'selected' : ''; ?>>Rendah</option>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control"
                       value="<?= $data['tanggal_mulai'] ?? '' ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control"
                       value="<?= $data['tanggal_selesai'] ?? '' ?>">
            </div>
        </div>

        <button class="btn btn-primary mt-3">
            <i class="bi bi-save"></i> Simpan
        </button>
        <a href="index.php" class="btn btn-secondary mt-3">Kembali</a>

    </form>
</div>

<?php include '../../includes/berry_shell_close.php'; ?>

<?php
session_start();
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina'); // hanya pembina

$pembina_id = $_SESSION['user_id'];

// Mode Edit (optional)
$edit_id = isset($_GET['edit']) ? $_GET['edit'] : null;
$data_edit = null;

if ($edit_id) {
    $data_edit = query("
        SELECT j.*
        FROM jadwal_latihans j
        JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
        WHERE j.id = ? AND e.pembina_id = ?
    ", [$edit_id, $pembina_id], "ii")->fetch_assoc();

    if (!$data_edit) {
        setFlash('danger', 'Data jadwal tidak ditemukan atau bukan milik Anda.');
        redirect('pembina/jadwal/index.php');
    }
}

// Ambil ekskul milik pembina
$ekskul = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers 
    WHERE pembina_id = ?
", [$pembina_id], "i");


// Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ekskul_id = $_POST['ekskul_id'];
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $lokasi = $_POST['lokasi'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Cek bentrok jadwal
    $bentrok = query("
        SELECT j.*
        FROM jadwal_latihans j
        JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
        WHERE e.pembina_id = ?
        AND j.hari = ?
        AND (
            (j.jam_mulai <= ? AND j.jam_selesai >= ?)
            OR
            (j.jam_mulai <= ? AND j.jam_selesai >= ?)
        )
        " . ($edit_id ? " AND j.id != $edit_id " : "") . "
    ", [
        $pembina_id,
        $hari,
        $jam_mulai, $jam_mulai,
        $jam_selesai, $jam_selesai
    ], "isssss");


    if ($bentrok->num_rows > 0) {
        setFlash('danger', 'Jadwal bentrok dengan jadwal lain!');
        redirect('pembina/jadwal/tambah.php' . ($edit_id ? "?edit=$edit_id" : ""));
    }

    // INSERT atau UPDATE
    if ($edit_id) {
        query("
            UPDATE jadwal_latihans SET 
                ekstrakurikuler_id = ?, 
                hari = ?, 
                jam_mulai = ?, 
                jam_selesai = ?, 
                lokasi = ?, 
                is_active = ?
            WHERE id = ?
        ", [
            $ekskul_id, $hari, $jam_mulai, $jam_selesai, $lokasi, $is_active, $edit_id
        ], "issssii");

        setFlash('success', 'Jadwal berhasil diperbarui!');
    } else {
        query("
            INSERT INTO jadwal_latihans (ekstrakurikuler_id, hari, jam_mulai, jam_selesai, lokasi, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $ekskul_id, $hari, $jam_mulai, $jam_selesai, $lokasi, $is_active
        ], "issssi");

        setFlash('success', 'Jadwal berhasil ditambahkan!');
    }

    redirect('pembina/jadwal/index.php');
}

?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>

<div class="p-4">

    <h2 class="mb-4">
        <i class="bi bi-plus-circle"></i>
        <?php echo $edit_id ? "Edit Jadwal" : "Tambah Jadwal"; ?>
    </h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <form method="POST">

                <!-- Ekskul -->
                <div class="mb-3">
                    <label class="form-label">Ekstrakurikuler</label>
                    <select name="ekskul_id" class="form-select" required>
                        <option value="" disabled selected>Pilih Ekskul</option>
                        <?php while ($e = $ekskul->fetch_assoc()): ?>
                        <option value="<?= $e['id']; ?>"
                            <?= $data_edit && $data_edit['ekstrakurikuler_id'] == $e['id'] ? 'selected' : ''; ?>>
                            <?= $e['nama_ekskul']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Hari -->
                <div class="mb-3">
                    <label class="form-label">Hari</label>
                    <select name="hari" class="form-select" required>
                        <?php
                        $hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                        foreach ($hari_list as $hari):
                        ?>
                        <option value="<?= $hari; ?>"
                            <?= ($data_edit && $data_edit['hari'] == $hari) ? 'selected' : ''; ?>>
                            <?= $hari; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Waktu -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control"
                            value="<?= $data_edit['jam_mulai'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control"
                            value="<?= $data_edit['jam_selesai'] ?? ''; ?>" required>
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="mb-3">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control"
                        value="<?= $data_edit['lokasi'] ?? ''; ?>" required>
                </div>

                <!-- Status -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="activeCheck"
                        <?= ($data_edit && $data_edit['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activeCheck">
                        Jadwal Aktif
                    </label>
                </div>

                <!-- Tombol -->
                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL; ?>pembina/jadwal/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <button class="btn btn-success">
                        <i class="bi bi-check-circle"></i>
                        Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>

<?php
// pembina/penilaian_siswa.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/middleware.php';

// Pastikan fungsi only & requireRole tersedia di middleware kamu
only('pembina');
requireRole(['pembina']);

$page_title = 'Penilaian Siswa';
$current_user = getCurrentUser();

// AMBIL pembina_id dengan fallback
$pembina_id = null;
if (isset($_SESSION['pembina_id']) && !empty($_SESSION['pembina_id'])) {
    $pembina_id = intval($_SESSION['pembina_id']);
} elseif (!empty($current_user) && isset($current_user['id'])) {
    $pembina_id = intval($current_user['id']);
}

// Jika tetap null -> user tidak login dengan benar
if (!$pembina_id) {
    setFlash('danger', 'Sesi tidak valid. Silakan login kembali.');
    header('Location: ' . BASE_URL . 'admin/login.php');
    exit();
}

//  SIMPAN NILAI (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $anggota_id = (int)($_POST['anggota_id'] ?? 0);
    $nilai = $_POST['nilai'] ?? '';
    $catatan = trim($_POST['catatan_pembina'] ?? '');
    $tanggal_penilaian = date('Y-m-d');

    // Validasi input minimal
    if ($anggota_id <= 0) {
        setFlash('danger', 'Anggota tidak valid.');
        header("Location: penilaian_siswa.php");
        exit();
    }

    if (!in_array($nilai, ['A', 'B', 'C'])) {
        setFlash('danger', 'Nilai tidak valid!');
        header("Location: penilaian_siswa.php");
        exit();
    }

    // CEK: pastikan anggota ini memang milik ekskul yang dibina pembina ini
    $cek_q = query("
        SELECT COUNT(*) AS total 
        FROM anggota_ekskul ae
        JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
        WHERE ae.id = ? AND e.pembina_id = ?
    ", [$anggota_id, $pembina_id], 'ii');

    $allowed = false;
    if ($cek_q && $cek_q->num_rows > 0) {
        $allowed = intval($cek_q->fetch_assoc()['total'] ?? 0) > 0;
    }

    if (!$allowed) {
        setFlash('danger', 'Anda tidak berhak menilai anggota ekskul ini.');
        header("Location: penilaian_siswa.php");
        exit();
    }

    // Update nilai
    $res = query(
        "UPDATE anggota_ekskul SET nilai = ?, tanggal_penilaian = ?, catatan_pembina = ? WHERE id = ?",
        [$nilai, $tanggal_penilaian, $catatan, $anggota_id],
        'sssi'
    );

    if ($res['success']) {
        setFlash('success', 'Nilai berhasil disimpan!');
    } else {
        setFlash('danger', 'Gagal menyimpan nilai.');
    }

    header("Location: penilaian_siswa.php?updated=" . time());
    exit();
}

//  AMBIL DATA ANGGOTA HANYA EKSUL PEMBINA
$anggota = query("
    SELECT 
        ae.*,
        u.name, u.nis, u.kelas, u.jenis_kelamin,
        e.nama_ekskul, e.id AS eskul_id,
        (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id AND p.status = 'hadir') AS total_hadir,
        (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id) AS total_pertemuan,
        ae.catatan_pembina, ae.nilai, ae.tanggal_penilaian
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.status = 'diterima'
      AND e.pembina_id = ?
    ORDER BY e.nama_ekskul, u.name
", [$pembina_id], 'i');

//  LIST ESKUL KHUSUS PEMBINA (filter dropdown)
$eskul_list = query("
    SELECT id, nama_ekskul FROM ekstrakurikulers
    WHERE status = 'aktif' AND pembina_id = ?
    ORDER BY nama_ekskul
", [$pembina_id], 'i');

//  HITUNG BELUM DINILAI (opsional)
$belum_dinilai_q = query("
    SELECT COUNT(*) AS total
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE e.pembina_id = ? AND (ae.nilai = '' OR ae.nilai IS NULL)
", [$pembina_id], 'i');

$belum_dinilai = $belum_dinilai_q ? intval($belum_dinilai_q->fetch_assoc()['total'] ?? 0) : 0;

?>

<?php include __DIR__ . '/../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../includes/berry_shell_open.php'; ?>

<div class="p-4">
    <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h2><i class="bi bi-star-fill text-warning"></i> Penilaian Siswa (Pembina)</h2>
    <p class="text-muted">Anda membina <?= $belum_dinilai ?> anggota yang belum dinilai.</p>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter Ekstrakurikuler</label>
                    <select id="filterEskul" class="form-select">
                        <option value="">Semua Ekskul</option>
                        <?php while ($e = $eskul_list->fetch_assoc()): ?>
                            <option value="<?= $e['id']; ?>"><?= htmlspecialchars($e['nama_ekskul']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter Nilai</label>
                    <select id="filterNilai" class="form-select">
                        <option value="">Semua</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="null">Belum Dinilai</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cari Siswa</label>
                    <input id="searchSiswa" class="form-control" placeholder="Ketik nama siswa...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>No</th><th>Siswa</th><th>Eskul</th><th>Kehadiran</th><th>Nilai</th><th>Catatan</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabelPenilaian">
                        <?php if ($anggota && $anggota->num_rows > 0): $no = 1; ?>
                            <?php while ($r = $anggota->fetch_assoc()): 
                                $persen = ($r['total_pertemuan'] > 0) ? round(($r['total_hadir'] / $r['total_pertemuan']) * 100) : 0;
                                $nilai_attr = $r['nilai'] ?: 'null';
                            ?>
                                <tr data-eskul="<?= $r['eskul_id']; ?>" data-nilai="<?= $nilai_attr; ?>" data-nama="<?= strtolower($r['name']); ?>">
                                    <td><?= $no++; ?></td>
                                    <td><strong><?= htmlspecialchars($r['name']); ?></strong><br><small>NIS: <?= htmlspecialchars($r['nis']); ?> | Kls: <?= htmlspecialchars($r['kelas']); ?></small></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($r['nama_ekskul']); ?></span></td>
                                    <td>
                                        <small><?= $r['total_hadir']; ?>/<?= $r['total_pertemuan']; ?></small>
                                        <div class="progress" style="height:6px;"><div class="progress-bar" style="width:<?= $persen; ?>%"></div></div>
                                        <small class="text-muted"><?= $persen; ?>%</small>
                                    </td>
                                    <td><?= $r['nilai'] ? '<span class="badge bg-success">'.htmlspecialchars($r['nilai']).'</span>' : '<span class="badge bg-secondary">Belum Dinilai</span>'; ?></td>
                                    <td><?= $r['catatan_pembina'] ? htmlspecialchars(substr($r['catatan_pembina'],0,60)) : '-'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalNilai"
                                            onclick="setNilai(<?= $r['id']; ?>, '<?= addslashes($r['name']); ?>', '<?= $r['nilai'] ?: ''; ?>', '<?= htmlspecialchars($r['catatan_pembina'] ?? '', ENT_QUOTES); ?>')">
                                            Nilai
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">Belum ada anggota.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalNilai" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Beri Nilai</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="anggota_id" id="anggota_id">
            <div><strong>Siswa:</strong> <span id="nama_siswa"></span></div>
            <div class="mt-3">
                <input type="radio" class="btn-check" name="nilai" id="nilaiA" value="A"><label class="btn btn-outline-success" for="nilaiA">A</label>
                <input type="radio" class="btn-check" name="nilai" id="nilaiB" value="B"><label class="btn btn-outline-warning" for="nilaiB">B</label>
                <input type="radio" class="btn-check" name="nilai" id="nilaiC" value="C"><label class="btn btn-outline-danger" for="nilaiC">C</label>
            </div>
            <div class="mt-3">
                <label>Catatan Pembina</label>
                <textarea name="catatan_pembina" id="catatan_pembina" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="simpan_nilai" class="btn btn-success">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function setNilai(id, nama, nilai, catatan) {
    document.getElementById('anggota_id').value = id;
    document.getElementById('nama_siswa').innerText = nama;
    document.getElementById('catatan_pembina').value = catatan || '';

    document.querySelectorAll('input[name="nilai"]').forEach(r => r.checked = false);
    if (nilai) {
        const node = document.getElementById('nilai' + nilai);
        if (node) node.checked = true;
    }
}

// Simple client-side filtering (optional)
// Add event listeners only if elements exist
const filterEskul = document.getElementById('filterEskul');
const filterNilai = document.getElementById('filterNilai');
const searchSiswa = document.getElementById('searchSiswa');

if (filterEskul) filterEskul.addEventListener('change', filterTable);
if (filterNilai) filterNilai.addEventListener('change', filterTable);
if (searchSiswa) searchSiswa.addEventListener('keyup', filterTable);

function filterTable(){
    const fe = filterEskul ? filterEskul.value : '';
    const fn = filterNilai ? filterNilai.value : '';
    const s = searchSiswa ? searchSiswa.value.toLowerCase() : '';
    document.querySelectorAll('#tabelPenilaian tr[data-eskul]').forEach(row=>{
        const esk = row.getAttribute('data-eskul');
        const nil = row.getAttribute('data-nilai');
        const nama = row.getAttribute('data-nama') || '';
        let show = true;
        if (fe && esk !== fe) show = false;
        if (fn && fn !== '' && nil !== fn) show = false;
        if (s && !nama.includes(s)) show = false;
        row.style.display = show ? '' : 'none';
    });
}
</script>

<?php include __DIR__ . '/../includes/berry_shell_close.php'; ?>

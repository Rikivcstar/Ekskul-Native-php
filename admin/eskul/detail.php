<?php
// admin/eskul/detail.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$page_title = 'Detail Ekstrakurikuler';
$current_user = getCurrentUser();
$id = $_GET['id'] ?? 0;

// Ambil data eskul
$eskul = query("
    SELECT e.*, u.name as nama_pembina, u.email as email_pembina
    FROM ekstrakurikulers e
    LEFT JOIN users u ON e.pembina_id = u.id
    WHERE e.id = ?
", [$id], 'i');

if (!$eskul || $eskul->num_rows == 0) {
    setFlash('danger', 'Ekstrakurikuler tidak ditemukan!');
    redirect('admin/eskul/index.php');
}

$data = $eskul->fetch_assoc();

// Statistik
$total_anggota = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE ekstrakurikuler_id = ? AND status = 'diterima'", [$id], 'i')->fetch_assoc()['total'];
$total_pending = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE ekstrakurikuler_id = ? AND status = 'pending'", [$id], 'i')->fetch_assoc()['total'];
$total_prestasi = query("SELECT COUNT(*) as total FROM prestasis WHERE ekstrakurikuler_id = ?", [$id], 'i')->fetch_assoc()['total'];

// Anggota terbaru
$anggota_terbaru = query("
    SELECT ae.*, u.name, u.nis, u.kelas
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    ORDER BY ae.created_at DESC
    LIMIT 10
", [$id], 'i');

// Jadwal
$jadwal = query("SELECT * FROM jadwal_latihans WHERE ekstrakurikuler_id = ? ORDER BY 
    CASE hari 
        WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 
        WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 WHEN 'Minggu' THEN 7 
    END", [$id], 'i');

// Prestasi
$prestasi = query("
    SELECT p.*, u.name as nama_siswa
    FROM prestasis p
    LEFT JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    LEFT JOIN users u ON ae.user_id = u.id
    WHERE p.ekstrakurikuler_id = ?
    ORDER BY p.tanggal DESC
    LIMIT 5
", [$id], 'i');

// Berita
$berita = query("SELECT * FROM berita WHERE ekstrakurikuler_id = ? ORDER BY created_at DESC LIMIT 5", [$id], 'i');
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
<div class="p-4">

                <div class="mb-4">
                    <a href="<?php echo BASE_URL; ?>admin/eskul/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Header -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2"><?php echo $data['nama_ekskul']; ?></h2>
                                <p class="text-muted mb-2"><?php echo $data['deskripsi']; ?></p>
                                <div class="mt-3">
                                    <span class="badge bg-<?php echo $data['status'] == 'aktif' ? 'success' : 'secondary'; ?> me-2">
                                        <?php echo ucfirst($data['status']); ?>
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="bi bi-person"></i> Pembina: <?php echo $data['nama_pembina'] ?? 'Belum ada'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <?php if ($data['gambar']): ?>
                                <img src="<?php echo UPLOAD_URL . $data['gambar']; ?>" class="img-thumbnail" style="max-width: 200px;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistik -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-success">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h3 class="mt-3"><?php echo $total_anggota; ?>/<?php echo $data['kuota']; ?></h3>
                                <p class="text-muted mb-0">Anggota Aktif</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-warning">
                                    <i class="bi bi-clock-fill"></i>
                                </div>
                                <h3 class="mt-3"><?php echo $total_pending; ?></h3>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-primary">
                                    <i class="bi bi-trophy-fill"></i>
                                </div>
                                <h3 class="mt-3"><?php echo $total_prestasi; ?></h3>
                                <p class="text-muted mb-0">Prestasi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="display-4 text-info">
                                    <i class="bi bi-percent"></i>
                                </div>
                                <h3 class="mt-3"><?php echo $total_anggota > 0 ? round(($total_anggota / $data['kuota']) * 100) : 0; ?>%</h3>
                                <p class="text-muted mb-0">Terisi</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#anggota">
                            <i class="bi bi-people"></i> Anggota
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#jadwal">
                            <i class="bi bi-calendar"></i> Jadwal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#prestasi">
                            <i class="bi bi-trophy"></i> Prestasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#berita">
                            <i class="bi bi-newspaper"></i> Berita
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Anggota -->
                    <div class="tab-pane fade show active" id="anggota">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Anggota Terbaru</h5>
                                <a href="<?php echo BASE_URL; ?>admin/anggota/manage.php" class="btn btn-sm btn-primary">
                                    Lihat Semua
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>NIS</th>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Tanggal Daftar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if ($anggota_terbaru && $anggota_terbaru->num_rows > 0):
                                                $no = 1;
                                                while ($anggota = $anggota_terbaru->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo $anggota['nis']; ?></td>
                                                <td><?php echo $anggota['name']; ?></td>
                                                <td><?php echo $anggota['kelas']; ?></td>
                                                <td><?php echo formatTanggal($anggota['tanggal_daftar']); ?></td>
                                            </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    Belum ada anggota
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jadwal -->
                    <div class="tab-pane fade" id="jadwal">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Jadwal Latihan</h5>
                                <a href="<?php echo BASE_URL; ?>admin/jadwal/tambah.php" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus"></i> Tambah Jadwal
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if ($jadwal && $jadwal->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($j = $jadwal->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><strong><?php echo $j['hari']; ?></strong></h6>
                                                <p class="mb-1">
                                                    <i class="bi bi-clock"></i> <?php echo substr($j['jam_mulai'], 0, 5); ?> - <?php echo substr($j['jam_selesai'], 0, 5); ?>
                                                </p>
                                                <p class="mb-0 text-muted">
                                                    <i class="bi bi-geo-alt"></i> <?php echo $j['lokasi']; ?>
                                                </p>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $j['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $j['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Belum ada jadwal latihan
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Prestasi -->
                    <div class="tab-pane fade" id="prestasi">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Prestasi Terbaru</h5>
                                <a href="<?php echo BASE_URL; ?>admin/prestasi/tambah.php" class="btn btn-sm btn-warning">
                                    <i class="bi bi-plus"></i> Tambah Prestasi
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if ($prestasi && $prestasi->num_rows > 0): ?>
                                <div class="row">
                                    <?php 
                                    $badge_color = [
                                        'internasional' => 'danger',
                                        'nasional' => 'primary',
                                        'provinsi' => 'success',
                                        'kabupaten' => 'info',
                                        'kecamatan' => 'warning',
                                        'sekolah' => 'secondary'
                                    ];
                                    while ($p = $prestasi->fetch_assoc()): 
                                    ?>
                                    <div class="col-md-12 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="badge bg-<?php echo $badge_color[$p['tingkat']] ?? 'secondary'; ?>">
                                                            <?php echo ucfirst($p['tingkat']); ?>
                                                        </span>
                                                        <h6 class="mt-2 mb-1"><?php echo $p['nama_prestasi']; ?></h6>
                                                        <p class="text-warning mb-1">
                                                            <i class="bi bi-award"></i> <strong><?php echo $p['peringkat']; ?></strong>
                                                        </p>
                                                        <?php if ($p['nama_siswa']): ?>
                                                        <p class="mb-0 small text-muted">
                                                            <i class="bi bi-person"></i> <?php echo $p['nama_siswa']; ?>
                                                        </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo formatTanggal($p['tanggal']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Belum ada prestasi
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Berita -->
                    <div class="tab-pane fade" id="berita">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Berita Terbaru</h5>
                                <a href="<?php echo BASE_URL; ?>admin/berita/tambah.php" class="btn btn-sm btn-info">
                                    <i class="bi bi-plus"></i> Tambah Berita
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if ($berita && $berita->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($b = $berita->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo $b['judul']; ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    <?php echo substr(strip_tags($b['konten']), 0, 100); ?>...
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> <?php echo formatTanggal($b['tanggal_post']); ?> | 
                                                    <i class="bi bi-eye"></i> <?php echo $b['views']; ?> views
                                                </small>
                                            </div>
                                            <span class="badge bg-<?php echo $b['is_published'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $b['is_published'] ? 'Published' : 'Draft'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Belum ada berita
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>
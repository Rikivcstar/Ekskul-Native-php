<?php
// pembina/laporan/index.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$page_title = 'Laporan';
$current_user = getCurrentUser();

// Ambil daftar eskul yang diampu pembina
$eskul_list = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers 
    WHERE pembina_id = ? AND status = 'aktif'
    ORDER BY nama_ekskul
", [$current_user['id']], 'i');

// Hitung total eskul yang diampu
$total_eskul = $eskul_list->num_rows;
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-file-earmark-text text-primary"></i> Laporan Ekstrakurikuler</h2>
            <p class="text-muted">Cetak dan export data ekstrakurikuler yang Anda ampu</p>
        </div>
    </div>

    <?php if ($total_eskul == 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Anda belum mengampu ekstrakurikuler apapun. 
            Silakan hubungi admin untuk penugasan.
        </div>
    <?php else: ?>

    <div class="row">
        <!-- Laporan Daftar Anggota -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-people-fill text-primary"></i> Daftar Anggota
                    </h5>
                    <p class="text-muted">Cetak daftar anggota ekstrakurikuler yang Anda ampu</p>
                    <form method="GET" action="cetak_anggota.php" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Pilih Ekstrakurikuler</label>
                            <select name="eskul_id" class="form-select" required>
                                <option value="">-- Pilih Eskul --</option>
                                <?php 
                                $eskul_list->data_seek(0);
                                while ($eskul = $eskul_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>">
                                    <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-printer"></i> Cetak Laporan Anggota
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Laporan Presensi -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar-check text-success"></i> Laporan Presensi
                    </h5>
                    <p class="text-muted">Cetak rekap presensi berdasarkan periode</p>
                    <form method="GET" action="cetak_presensi.php" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Pilih Ekstrakurikuler</label>
                            <select name="eskul_id" class="form-select" required>
                                <option value="">-- Pilih Eskul --</option>
                                <?php 
                                $eskul_list->data_seek(0);
                                while ($eskul = $eskul_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>">
                                    <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dari Tanggal</label>
                                <input type="date" name="dari" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sampai Tanggal</label>
                                <input type="date" name="sampai" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-printer"></i> Cetak Laporan Presensi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Laporan Nilai -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-award text-warning"></i> Laporan Nilai
                    </h5>
                    <p class="text-muted">Cetak daftar nilai anggota ekstrakurikuler</p>
                    <form method="GET" action="cetak_nilai.php" target="_blank">
                        <div class="mb-3">
                            <label class="form-label">Pilih Ekstrakurikuler</label>
                            <select name="eskul_id" class="form-select" required>
                                <option value="">-- Pilih Eskul --</option>
                                <?php 
                                $eskul_list->data_seek(0);
                                while ($eskul = $eskul_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>">
                                    <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-printer"></i> Cetak Laporan Nilai
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Export Data -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-download text-info"></i> Export Data Excel
                    </h5>
                    <p class="text-muted">Download data dalam format Excel (CSV)</p>
                    
                    <!-- Export Anggota -->
                    <form method="GET" action="export_anggota.php" class="mb-3">
                        <label class="form-label small">Export Data Anggota</label>
                        <select name="eskul_id" class="form-select form-select-sm mb-2" required>
                            <option value="">-- Pilih Eskul --</option>
                            <?php 
                            $eskul_list->data_seek(0);
                            while ($eskul = $eskul_list->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $eskul['id']; ?>">
                                <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-info btn-sm w-100">
                            <i class="bi bi-file-earmark-excel"></i> Export Anggota
                        </button>
                    </form>
                    
                    <!-- Export Presensi -->
                    <form method="GET" action="export_presensi.php">
                        <label class="form-label small">Export Rekap Presensi</label>
                        <select name="eskul_id" class="form-select form-select-sm mb-2" required>
                            <option value="">-- Pilih Eskul --</option>
                            <?php 
                            $eskul_list->data_seek(0);
                            while ($eskul = $eskul_list->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $eskul['id']; ?>">
                                <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="row mb-2">
                            <div class="col-6">
                                <input type="date" name="dari" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <input type="date" name="sampai" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-info btn-sm w-100">
                            <i class="bi bi-file-earmark-excel"></i> Export Presensi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Ekstrakurikuler -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-fill"></i> Statistik Ekstrakurikuler Anda
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $stats = query("
                        SELECT 
                            e.id,
                            e.nama_ekskul,
                            e.kuota,
                            COUNT(DISTINCT CASE WHEN ae.status = 'diterima' THEN ae.id END) as total_anggota,
                            COUNT(DISTINCT CASE WHEN ae.status = 'pending' THEN ae.id END) as pending,
                            ROUND((COUNT(DISTINCT CASE WHEN ae.status = 'diterima' THEN ae.id END) / e.kuota * 100), 2) as persentase
                        FROM ekstrakurikulers e
                        LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
                        WHERE e.pembina_id = ? AND e.status = 'aktif'
                        GROUP BY e.id
                        ORDER BY e.nama_ekskul
                    ", [$current_user['id']], 'i');
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Ekstrakurikuler</th>
                                    <th class="text-center">Anggota Diterima</th>
                                    <th class="text-center">Pending</th>
                                    <th class="text-center">Kuota</th>
                                    <th>Persentase Terisi</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($stats->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">Tidak ada data ekstrakurikuler</td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = $stats->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['nama_ekskul']); ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $row['total_anggota']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['pending'] > 0): ?>
                                                <span class="badge bg-warning"><?php echo $row['pending']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?php echo $row['kuota']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar <?php 
                                                    if ($row['persentase'] >= 80) echo 'bg-danger';
                                                    elseif ($row['persentase'] >= 50) echo 'bg-warning';
                                                    else echo 'bg-success';
                                                ?>" role="progressbar" 
                                                    style="width: <?php echo $row['persentase']; ?>%" 
                                                    aria-valuenow="<?php echo $row['persentase']; ?>" 
                                                    aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $row['persentase']; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['persentase'] >= 100): ?>
                                                <span class="badge bg-danger">Penuh</span>
                                            <?php elseif ($row['persentase'] >= 80): ?>
                                                <span class="badge bg-warning">Hampir Penuh</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Tersedia</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>
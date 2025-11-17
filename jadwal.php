<?php
// jadwal.php
$page_title = 'Jadwal Kegiatan';
require_once 'includes/header.php';

// Ambil semua jadwal aktif
$jadwal = query("
    SELECT j.*, e.nama_ekskul, u.name as nama_pembina
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON e.pembina_id = u.id
    WHERE j.is_active = 1 AND e.status = 'aktif'
    ORDER BY 
        CASE j.hari
            WHEN 'Senin' THEN 1
            WHEN 'Selasa' THEN 2
            WHEN 'Rabu' THEN 3
            WHEN 'Kamis' THEN 4
            WHEN 'Jumat' THEN 5
            WHEN 'Sabtu' THEN 6
            WHEN 'Minggu' THEN 7
        END,
        j.jam_mulai
");
?>

<div class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Jadwal Kegiatan Ekstrakurikuler</h2>
            <p class="text-muted">Jadwal latihan rutin ekstrakurikuler MTsN 1 Lebak</p>
        </div>

        <!-- Jadwal Per Hari -->
        <div class="row">
            <?php
            $hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary', 'dark'];
            
            foreach ($hari_list as $index => $hari):
                $jadwal_hari = query("
                    SELECT j.*, e.nama_ekskul, u.name as nama_pembina
                    FROM jadwal_latihans j
                    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
                    LEFT JOIN users u ON e.pembina_id = u.id
                    WHERE j.hari = ? AND j.is_active = 1 AND e.status = 'aktif'
                    ORDER BY j.jam_mulai
                ", [$hari], 's');
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-<?php echo $colors[$index % count($colors)]; ?> text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-week"></i> <?php echo $hari; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($jadwal_hari && $jadwal_hari->num_rows > 0): ?>
                            <?php while ($jh = $jadwal_hari->fetch_assoc()): ?>
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="fw-bold text-success mb-2">
                                    <i class="bi bi-grid-fill"></i> <?php echo $jh['nama_ekskul']; ?>
                                </h6>
                                <p class="mb-1">
                                    <i class="bi bi-clock text-primary"></i> 
                                    <strong><?php echo substr($jh['jam_mulai'], 0, 5); ?> - <?php echo substr($jh['jam_selesai'], 0, 5); ?></strong>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-geo-alt text-danger"></i> <?php echo $jh['lokasi']; ?>
                                </p>
                                <p class="mb-0 small text-muted">
                                    <i class="bi bi-person"></i> <?php echo $jh['nama_pembina'] ?? 'Pembina'; ?>
                                </p>
                                <?php if ($jh['keterangan']): ?>
                                <p class="mb-0 small text-muted mt-2">
                                    <em><?php echo $jh['keterangan']; ?></em>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-1"></i>
                                <p class="small mt-2 mb-0">Tidak ada jadwal</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabel Jadwal (Desktop View) -->
        <div class="card border-0 shadow-sm mt-5 d-none d-lg-block">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-table"></i> Tabel Jadwal Lengkap
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Ekstrakurikuler</th>
                                <th>Hari</th>
                                <th>Waktu</th>
                                <th>Lokasi</th>
                                <th>Pembina</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($jadwal && $jadwal->num_rows > 0):
                                $jadwal->data_seek(0);
                                $no = 1;
                                while ($row = $jadwal->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong class="text-success"><?php echo $row['nama_ekskul']; ?></strong>
                                </td>
                                <td><strong><?php echo $row['hari']; ?></strong></td>
                                <td>
                                    <i class="bi bi-clock"></i> 
                                    <?php echo substr($row['jam_mulai'], 0, 5); ?> - 
                                    <?php echo substr($row['jam_selesai'], 0, 5); ?>
                                </td>
                                <td>
                                    <i class="bi bi-geo-alt"></i> <?php echo $row['lokasi']; ?>
                                </td>
                                <td><?php echo $row['nama_pembina'] ?? '-'; ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada jadwal tersedia
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Penting -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-info-circle text-info"></i> Informasi Penting
                </h5>
                <ul class="mb-0">
                    <li>Harap datang tepat waktu sesuai jadwal yang telah ditentukan</li>
                    <li>Jika berhalangan hadir, wajib memberikan keterangan kepada pembina</li>
                    <li>Jadwal dapat berubah sewaktu-waktu, mohon selalu cek jadwal terbaru</li>
                    <li>Untuk informasi lebih lanjut, hubungi pembina masing-masing ekstrakurikuler</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
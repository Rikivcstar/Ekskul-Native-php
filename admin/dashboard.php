<?php
// admin/dashboard.php
require_once '../config/database.php';
requireRole(['admin', 'pembina']);

$page_title = 'Dashboard';
$current_user = getCurrentUser();

// Statistik
$total_eskul = query("SELECT COUNT(*) as total FROM ekstrakurikulers WHERE status = 'aktif'")->fetch_assoc()['total'];
$total_siswa = query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa' AND is_active = 1")->fetch_assoc()['total'];
$total_anggota = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima'")->fetch_assoc()['total'];
$total_pembina = query("SELECT COUNT(*) as total FROM users WHERE role = 'pembina' AND is_active = 1")->fetch_assoc()['total'];

// Statistik Penilaian
$sudah_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai != ''")->fetch_assoc()['total'];
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai = ''")->fetch_assoc()['total'];

// Pendaftaran terbaru
$pendaftaran_baru = query("
    SELECT ae.*, u.name, u.kelas, e.nama_ekskul
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.status = 'pending'
    ORDER BY ae.created_at DESC
    LIMIT 5
");

// Eskul populer
$eskul_populer = query("
    SELECT e.nama_ekskul, COUNT(ae.id) as jumlah_anggota
    FROM ekstrakurikulers e
    LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id AND ae.status = 'diterima'
    WHERE e.status = 'aktif'
    GROUP BY e.id
    ORDER BY jumlah_anggota DESC
    LIMIT 5
");

// Presensi hari ini
$presensi_hari_ini = query("
    SELECT COUNT(*) as total FROM presensis WHERE tanggal = CURDATE()
")->fetch_assoc()['total'];

// Berita terbaru
$berita_terbaru = query("
    SELECT b.*, e.nama_ekskul, u.name as penulis
    FROM berita b
    JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 3
");
$grafik_eskul = query("
    SELECT e.nama_ekskul, COUNT(ae.id) AS total
    FROM ekstrakurikulers e
    LEFT JOIN anggota_ekskul ae 
        ON e.id = ae.ekstrakurikuler_id AND ae.status = 'diterima'
    GROUP BY e.id, e.nama_ekskul
")->fetch_all(MYSQLI_ASSOC);


?>
<?php include __DIR__ . '/../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../includes/berry_shell_open.php'; ?>
            <div class="col-md-12 p-4">
                <?php
                $flash = getFlash();
                if ($flash):
                ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
                <?php endif; ?>

                <div class="content-shell">
                <h2 class="mb-4 section-title">Dashboard Admin</h2>

                <!-- Statistik Cards -->
                <div class="row mb">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Ekstrakurikuler</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $total_eskul; ?>">0</h2>
                                    </div>
                                    <div class="icon-pill bg-emerald-soft"><i class="bi bi-grid-fill fs-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Siswa</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $total_siswa; ?>">0</h2>
                                    </div>
                                    <div class="icon-pill bg-blue-soft"><i class="bi bi-people-fill fs-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Anggota Aktif</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $total_anggota; ?>">0</h2>
                                    </div>
                                    <div class="icon-pill bg-amber-soft"><i class="bi bi-person-check-fill fs-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Presensi Hari Ini</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $presensi_hari_ini; ?>">0</h2>
                                    </div>
                                    <div class="icon-pill bg-cyan-soft"><i class="bi bi-clipboard-check fs-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row ">
                        <!-- Grafik 2 -->
                    <div class="col-md-6 mt-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Statistik Utama</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="grafikStatistik"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm my-4">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> Grafik Anggota per Ekskul</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="grafikEkskul" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                  
                </div>
                <div class="row">
                    <!-- Grafik 3 -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-pie-chart-fill"></i> Status Penilaian</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="grafikPenilaian"></canvas>
                            </div>
                        </div>
                    </div>
                        <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-star-fill text-warning"></i> Eskul Populer
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if ($eskul_populer && $eskul_populer->num_rows > 0): ?>
                                <div class="list-group list-group-flush list-clean">
                                    <?php 
                                    $no = 1;
                                    while ($row = $eskul_populer->fetch_assoc()): 
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="rank"><?php echo $no++; ?></span>
                                            <div class="d-flex flex-column">
                                                <span class="name"><?php echo $row['nama_ekskul']; ?></span>
                                                <small class="meta">Terbanyak diminati</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo $row['jumlah_anggota']; ?> anggota</span>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted text-center py-3">Belum ada data</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                  
                </div>
                                
                <!-- Statistik Penilaian -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-star-fill text-warning"></i> Status Penilaian Siswa
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Sudah Dinilai</span>
                                    <span class="badge bg-success"><?php echo $sudah_dinilai; ?> siswa</span>
                                </div>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $total_anggota > 0 ? round(($sudah_dinilai/$total_anggota)*100) : 0; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Belum Dinilai</span>
                                    <span class="badge bg-danger"><?php echo $belum_dinilai; ?> siswa</span>
                                </div>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo $total_anggota > 0 ? round(($belum_dinilai/$total_anggota)*100) : 0; ?>%"></div>
                                </div>
                                <a href="<?php echo BASE_URL; ?>admin/penilaian_siswa.php" class="btn btn-sm btn-warning w-100">
                                    <i class="bi bi-star-fill"></i> Kelola Penilaian
                                </a>
                            </div>
                        </div>
                    </div> 
                     <!-- Pendaftaran Baru -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-bell-fill text-warning"></i> Pendaftaran Baru
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($pendaftaran_baru && $pendaftaran_baru->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Siswa</th>
                                                <th>Kelas</th>
                                                <th>Eskul</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $pendaftaran_baru->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['name']; ?></td>
                                                <td><?php echo $row['kelas']; ?></td>
                                                <td><?php echo $row['nama_ekskul']; ?></td>
                                                <td>
                                                    <a href="<?php echo BASE_URL; ?>admin/anggota/manage.php" class="btn btn-sm btn-success">
                                                        Lihat
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted text-center py-3">Tidak ada pendaftaran baru</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                
                    <!-- Quick Actions -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-lightning-fill text-warning"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo BASE_URL; ?>admin/penilaian_siswa.php" class="btn btn-outline-warning">
                                        <i class="bi bi-star-fill"></i> Penilaian Siswa
                                        <?php if ($belum_dinilai > 0): ?>
                                        <span class="badge bg-danger"><?php echo $belum_dinilai; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/presensi/index.php" class="btn btn-outline-primary">
                                        <i class="bi bi-clipboard-check"></i> Input Presensi
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/berita/manage.php" class="btn btn-outline-info">
                                        <i class="bi bi-newspaper"></i> Tambah Berita
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/laporan/index.php" class="btn btn-outline-success">
                                        <i class="bi bi-file-earmark-text"></i> Lihat Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Berita Terbaru -->
                <?php if ($berita_terbaru && $berita_terbaru->num_rows > 0): ?>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-newspaper"></i> Berita Terbaru
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while ($berita = $berita_terbaru->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <span class="badge bg-success mb-2"><?php echo $berita['nama_ekskul']; ?></span>
                                        <h6><?php echo substr($berita['judul'], 0, 50); ?>...</h6>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo $berita['penulis'] ?? 'Admin'; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        // Data Statistik Umum
        const statistikData = {
            eskul: <?= $total_eskul ?>,
            siswa: <?= $total_siswa ?>,
            anggota: <?= $total_anggota ?>,
            pembina: <?= $total_pembina ?>
        };

        // Data Penilaian
        const penilaianData = {
            sudah: <?= $sudah_dinilai ?>,
            belum: <?= $belum_dinilai ?>
        };

        // Data Eskul Populer (nama & jumlah)
        const labelEskul = <?php echo json_encode(array_column($grafik_eskul, 'nama_ekskul')); ?>;
        const dataEskul = <?php echo json_encode(array_column($grafik_eskul, 'total')); ?>;
    </script>

    <script>
        // Counter animation
        document.querySelectorAll('.counter').forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const increment = target / 50;
            let count = 0;
            
            const updateCounter = () => {
                count += increment;
                if (count < target) {
                    counter.textContent = Math.ceil(count);
                    setTimeout(updateCounter, 20);
                } else {
                    counter.textContent = target;
                }
            };
            
            updateCounter();
        });
    </script>
<?php include __DIR__ . '/../includes/berry_shell_close.php'; ?>
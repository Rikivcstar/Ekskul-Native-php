<?php
require_once '../config/database.php';
require_once __DIR__ . '/../config/middleware.php';
only('pembina');
requireRole(['pembina']);

$page_title = 'Dashboard Pembina';
$current_user = getCurrentUser();
$pembina_id = intval($current_user['id'] ?? 0);

/* ============================================================
   1. AMBIL ESKUL YANG DIBINA PEMBINA INI
   ============================================================ */
$ekskul_pembina_res = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers
    WHERE pembina_id = '{$pembina_id}'
");
$ekskul_pembina = [];
if ($ekskul_pembina_res && $ekskul_pembina_res->num_rows > 0) {
    // gunakan fetch_all jika tersedia
    if (method_exists($ekskul_pembina_res, 'fetch_all')) {
        $ekskul_pembina = $ekskul_pembina_res->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($r = $ekskul_pembina_res->fetch_assoc()) $ekskul_pembina[] = $r;
    }
}

if (empty($ekskul_pembina)) {
    $ekskul_ids = "0"; // Supaya query IN(...) aman
    $ekskul_ids_array = [];
} else {
    $ekskul_ids_array = array_map('intval', array_column($ekskul_pembina, 'id'));
    $ekskul_ids = implode(",", $ekskul_ids_array);
}

/* ============================================================
   2. STATISTIK KHUSUS PEMBINA
   ============================================================ */

// Jumlah ekskul yang dibina pembina ini
$total_eskul = count($ekskul_pembina);

// Total siswa (distinct) di ekskul pembina
$total_siswa = 0;
    $q = query("
        SELECT COUNT(DISTINCT u.id) AS total
        FROM users u
        JOIN anggota_ekskul ae ON ae.user_id = u.id
        WHERE u.role = 'siswa' AND u.is_active = 1
        AND ae.status = 'diterima' AND ae.ekstrakurikuler_id IN ($ekskul_ids)
    ");
    if ($q) $total_siswa = intval($q->fetch_assoc()['total'] ?? 0);

    // Total anggota aktif pada ekskul yang dibina
    $total_anggota = 0;
    $q = query("
        SELECT COUNT(*) AS total
        FROM anggota_ekskul
        WHERE status='diterima'
        AND ekstrakurikuler_id IN ($ekskul_ids)
    ");
    if ($q) $total_anggota = intval($q->fetch_assoc()['total'] ?? 0);

    // Total pembina (umum) - tetap tampilkan keseluruhan pembina aktif
    $total_pembina = 0;
    $q = query("SELECT COUNT(*) AS total FROM users WHERE role = 'pembina' AND is_active = 1");
    if ($q) $total_pembina = intval($q->fetch_assoc()['total'] ?? 0);

    /* ============================================================
    3. PRESENSI HARI INI (JOIN karena presensis pakai anggota_id)
    ============================================================ */
    $presensi_hari_ini = 0;
    $q = query("
        SELECT COUNT(*) AS total
        FROM presensis p
        JOIN anggota_ekskul a ON a.id = p.anggota_id
        WHERE p.tanggal = CURDATE()
        AND a.ekstrakurikuler_id IN ($ekskul_ids)
    ");
    if ($q) $presensi_hari_ini = intval($q->fetch_assoc()['total'] ?? 0);

    /* ============================================================
    4. STATISTIK PENILAIAN
    ============================================================ */
    $sudah_dinilai = 0;
    $belum_dinilai = 0;

    $q = query("
        SELECT COUNT(*) AS total
        FROM anggota_ekskul
        WHERE status='diterima'
        AND nilai != ''
        AND ekstrakurikuler_id IN ($ekskul_ids)
    ");
    if ($q) $sudah_dinilai = intval($q->fetch_assoc()['total'] ?? 0);

    $q = query("
        SELECT COUNT(*) AS total
        FROM anggota_ekskul
        WHERE status='diterima'
        AND (nilai = '' OR nilai IS NULL)
        AND ekstrakurikuler_id IN ($ekskul_ids)
    ");
    if ($q) $belum_dinilai = intval($q->fetch_assoc()['total'] ?? 0);

    /* ============================================================
    5. PENDAFTARAN BARU KHUSUS ESKUL PEMBINA
    ============================================================ */
    $pendaftaran_baru = query("
        SELECT ae.*, u.name, u.kelas, e.nama_ekskul
        FROM anggota_ekskul ae
        JOIN users u ON ae.user_id = u.id
        JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
        WHERE ae.status = 'pending'
        AND ae.ekstrakurikuler_id IN ($ekskul_ids)
        ORDER BY ae.created_at DESC
        LIMIT 5
    ");

    /* ============================================================
    6. ESKUL POPULER (khusus pembina)
    ============================================================ */
    $eskul_populer = query("
        SELECT e.nama_ekskul, COUNT(ae.id) AS jumlah_anggota
        FROM ekstrakurikulers e
        LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id AND ae.status = 'diterima'
        WHERE e.id IN ($ekskul_ids)
        GROUP BY e.id
        ORDER BY jumlah_anggota DESC
        LIMIT 5
    ");

    /* ============================================================
    7. BERITA TERBARU (KHUSUS ESKUL PEMBINA)
    ============================================================ */
    $berita_terbaru = query("
        SELECT b.*, e.nama_ekskul, u.name AS penulis
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.ekstrakurikuler_id IN ($ekskul_ids)
        ORDER BY b.created_at DESC
        LIMIT 3
    ");

    /* ============================================================
    8. GRAFIK – ANGGOTA PER ESKUL PEMBINA
    ============================================================ */
    $grafik_eskul = [];
    $q = query("
        SELECT e.nama_ekskul, COUNT(ae.id) AS total
        FROM ekstrakurikulers e
        LEFT JOIN anggota_ekskul ae
            ON e.id = ae.ekstrakurikuler_id AND ae.status = 'diterima'
        WHERE e.id IN ($ekskul_ids)
        GROUP BY e.id, e.nama_ekskul
    ");
    if ($q) {
        if (method_exists($q, 'fetch_all')) {
            $grafik_eskul = $q->fetch_all(MYSQLI_ASSOC);
        } else {
            while ($r = $q->fetch_assoc()) $grafik_eskul[] = $r;
        }
    }

/* ============================================================
   include head & sidebar (tetap seperti strukturmu)
   ============================================================ */
?>
<?php include __DIR__ . '/../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../includes/berry_shell_open.php'; ?>

            <div class="col-md-12 p-4">
                <?php
                $flash = function_exists('getFlash') ? getFlash() : null;
                if ($flash):
                ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
                <?php endif; ?>

                <div class="content-shell">
                <h2 class="mb-4 section-title">Dashboard Pembina</h2>

                <!-- Statistik Cards -->
                <div class="row mb">
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Ekstrakurikuler</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo intval($total_eskul); ?>">0</h2>
                                    </div>
                                    <div class="icon-pill bg-emerald-soft"><i class="bi bi-grid-fill fs-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Anggota Aktif</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo intval($total_anggota); ?>">0</h2>
                                    </div>
                                    <div class="icon-pill bg-amber-soft"><i class="bi bi-person-check-fill fs-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Presensi Hari Ini</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo intval($presensi_hari_ini); ?>">0</h2>
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
                                    <span class="badge bg-success"><?php echo intval($sudah_dinilai); ?> siswa</span>
                                </div>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $total_anggota > 0 ? round(($sudah_dinilai/$total_anggota)*100) : 0; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Belum Dinilai</span>
                                    <span class="badge bg-danger"><?php echo intval($belum_dinilai); ?> siswa</span>
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
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_ekskul']); ?></td>
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
                                        <span class="badge bg-success mb-2"><?php echo htmlspecialchars($berita['nama_ekskul']); ?></span>
                                        <h6><?php echo htmlspecialchars(substr($berita['judul'] ?? '', 0, 50)); ?>...</h6>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($berita['penulis'] ?? 'Admin'); ?>
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
            eskul: <?= intval($total_eskul) ?>,
            siswa: <?= intval($total_siswa) ?>,
            anggota: <?= intval($total_anggota) ?>,
            pembina: <?= intval($total_pembina) ?>
        };

        // Data Penilaian
        const penilaianData = {
            sudah: <?= intval($sudah_dinilai) ?>,
            belum: <?= intval($belum_dinilai) ?>
        };

        // Data Eskul Populer (nama & jumlah)
        const labelEskul = <?= json_encode(array_column($grafik_eskul, 'nama_ekskul')) ?>;
        const dataEskul = <?= json_encode(array_map('intval', array_column($grafik_eskul, 'total'))) ?>;
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

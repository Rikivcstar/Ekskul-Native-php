<?php
// admin/penilaian_siswa.php (untuk admin & pembina)
require_once '../config/database.php';
require_once __DIR__ . '/../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Penilaian Siswa';
$current_user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $anggota_id = (int)$_POST['anggota_id'];
    $nilai = $_POST['nilai'];
    $catatan = trim($_POST['catatan_pembina'] ?? '');
    $tanggal_penilaian = date('Y-m-d');
    
    // Validasi nilai
    if (!in_array($nilai, ['A', 'B', 'C'])) {
        setFlash('danger', 'Nilai tidak valid!');
    } else {
        $result = query(
            "UPDATE anggota_ekskul SET nilai = ?, tanggal_penilaian = ?, catatan_pembina = ? WHERE id = ?",
            [$nilai, $tanggal_penilaian, $catatan, $anggota_id],
            'sssi'
        );
        
        if ($result['success'] && $result['affected_rows'] > 0) {
            setFlash('success', 'Nilai berhasil disimpan!');
        } else {
            setFlash('danger', 'Gagal menyimpan nilai atau data tidak berubah!');
        }
    }
    
    // Redirect dengan query string untuk refresh data
    header("Location: penilaian_siswa.php?updated=" . time());
    exit();
}

// Get anggota berdasarkan role
if ($current_user['role'] === 'admin') {
    // Admin bisa lihat semua
    $anggota = query("
        SELECT 
            ae.*,
            u.name, u.nis, u.kelas, u.jenis_kelamin,
            e.nama_ekskul, e.id as eskul_id,
            pembina.name as nama_pembina,
            (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id AND p.status = 'hadir') as total_hadir,
            (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id) as total_pertemuan
        FROM anggota_ekskul ae
        JOIN users u ON ae.user_id = u.id
        JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
        LEFT JOIN users pembina ON e.pembina_id = pembina.id
        WHERE ae.status = 'diterima'
        ORDER BY e.nama_ekskul, u.name
    ");
} else {
    // Pembina hanya lihat ekskul yang dibina
    $anggota = query("
        SELECT 
            ae.*,
            u.name, u.nis, u.kelas, u.jenis_kelamin,
            e.nama_ekskul, e.id as eskul_id,
            (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id AND p.status = 'hadir') as total_hadir,
            (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id) as total_pertemuan
        FROM anggota_ekskul ae
        JOIN users u ON ae.user_id = u.id
        JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
        WHERE ae.status = 'diterima' AND e.pembina_id = ?
        ORDER BY e.nama_ekskul, u.name
    ", [$current_user['id']], 'i');
}

// Get eskul list for filter
if ($current_user['role'] === 'admin') {
    $eskul_list = query("SELECT * FROM ekstrakurikulers WHERE status = 'aktif' ORDER BY nama_ekskul");
} else {
    $eskul_list = query("SELECT * FROM ekstrakurikulers WHERE status = 'aktif' AND pembina_id = ? ORDER BY nama_ekskul", [$current_user['id']], 'i');
}

// Statistik Penilaian untuk badge
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai = ''")->fetch_assoc()['total'];
?>
<?php include __DIR__ . '/../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../includes/berry_shell_open.php'; ?>

            <!-- Main Content in Berry layout -->
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
                        <h2><i class="bi bi-star-fill text-warning"></i> Penilaian Siswa</h2>
                        <p class="text-muted">Berikan penilaian (A, B, C) untuk anggota ekstrakurikuler</p>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="alert alert-info mb-4">
                    <h6><i class="bi bi-info-circle"></i> Panduan Penilaian:</h6>
                    <ul class="mb-0">
                        <li><strong>Nilai A:</strong> Sangat Baik - Kehadiran ≥90%, aktif, dan berprestasi</li>
                        <li><strong>Nilai B:</strong> Baik - Kehadiran 75-89%, cukup aktif</li>
                        <li><strong>Nilai C:</strong> Cukup - Kehadiran 60-74%, kurang aktif</li>
                        <li class="text-danger"><strong>Catatan:</strong> Siswa dengan nilai akan muncul di sertifikat mereka</li>
                    </ul>
                </div>

                <!-- Filter -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Filter Ekstrakurikuler</label>
                                <select class="form-select" id="filterEskul">
                                    <option value="">Semua Ekstrakurikuler</option>
                                    <?php while ($e = $eskul_list->fetch_assoc()): ?>
                                    <option value="<?php echo $e['id']; ?>"><?php echo $e['nama_ekskul']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Filter Nilai</label>
                                <select class="form-select" id="filterNilai">
                                    <option value="">Semua Nilai</option>
                                    <option value="A">Nilai A</option>
                                    <option value="B">Nilai B</option>
                                    <option value="C">Nilai C</option>
                                    <option value="null">Belum Dinilai</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cari Siswa</label>
                                <input type="text" class="form-control" id="searchSiswa" placeholder="Ketik nama siswa...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Penilaian -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="20%">Siswa</th>
                                        <th width="15%">Ekstrakurikuler</th>
                                        <th width="12%">Kehadiran</th>
                                        <th width="15%">Nilai Saat Ini</th>
                                        <th width="20%">Catatan</th>
                                        <th width="13%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tabelPenilaian">
                                    <?php 
                                    if ($anggota && $anggota->num_rows > 0):
                                        $no = 1;
                                        while ($row = $anggota->fetch_assoc()): 
                                            $persentase_hadir = $row['total_pertemuan'] > 0 ? round(($row['total_hadir'] / $row['total_pertemuan']) * 100) : 0;
                                            $nilai_attr = $row['nilai'] ?? 'null';
                                    ?>
                                    <tr data-eskul="<?php echo $row['eskul_id']; ?>" data-nilai="<?php echo $nilai_attr; ?>" data-nama="<?php echo strtolower($row['name']); ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo $row['name']; ?></strong><br>
                                            <small class="text-muted">
                                                NIS: <?php echo $row['nis']; ?> | Kelas: <?php echo $row['kelas']; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo $row['nama_ekskul']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <small><?php echo $row['total_hadir']; ?> / <?php echo $row['total_pertemuan']; ?></small>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar <?php echo $persentase_hadir >= 75 ? 'bg-success' : 'bg-warning'; ?>" 
                                                     style="width: <?php echo $persentase_hadir; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $persentase_hadir; ?>%</small>
                                        </td>
                                        <td>
                                            <?php if ($row['nilai']): ?>
                                            <span class="nilai-badge nilai-<?php echo $row['nilai']; ?>">
                                                <?php echo $row['nilai']; ?>
                                            </span>
                                            <?php if ($row['tanggal_penilaian']): ?>
                                            <br><small class="text-muted"><?php echo formatTanggal($row['tanggal_penilaian']); ?></small>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Belum Dinilai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo $row['catatan_pembina'] ? substr($row['catatan_pembina'], 0, 50) . '...' : '-'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalNilai"
                                                    onclick="setNilai(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', '<?php echo $row['nilai']; ?>', '<?php echo htmlspecialchars($row['catatan_pembina'] ?? '', ENT_QUOTES); ?>')">
                                                <i class="bi bi-star-fill"></i> Nilai
                                            </button>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">Belum ada data anggota</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Modal Input Nilai -->
    <div class="modal fade" id="modalNilai" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-star-fill"></i> Beri Penilaian
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="anggota_id" id="anggota_id">
                        
                        <div class="alert alert-info">
                            <strong>Siswa:</strong> <span id="nama_siswa"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Nilai <span class="text-danger">*</span></label>
                            <div class="d-grid gap-2">
                                <input type="radio" class="btn-check" name="nilai" id="nilaiA" value="A" required>
                                <label class="btn btn-outline-success btn-lg" for="nilaiA">
                                    <i class="bi bi-star-fill"></i> Nilai A - Sangat Baik
                                </label>

                                <input type="radio" class="btn-check" name="nilai" id="nilaiB" value="B">
                                <label class="btn btn-outline-warning btn-lg" for="nilaiB">
                                    <i class="bi bi-star-half"></i> Nilai B - Baik
                                </label>

                                <input type="radio" class="btn-check" name="nilai" id="nilaiC" value="C">
                                <label class="btn btn-outline-danger btn-lg" for="nilaiC">
                                    <i class="bi bi-star"></i> Nilai C - Cukup
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan Pembina (Opsional)</label>
                            <textarea class="form-control" name="catatan_pembina" id="catatan_pembina" rows="3" 
                                      placeholder="Berikan catatan atau komentar untuk siswa..."></textarea>
                            <small class="text-muted">Catatan ini akan muncul di sertifikat siswa</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Batal
                        </button>
                        <button type="submit" name="simpan_nilai" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Simpan Nilai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
    <script>
        // Set nilai di modal
        function setNilai(id, nama, nilaiSekarang, catatan) {
            document.getElementById('anggota_id').value = id;
            document.getElementById('nama_siswa').textContent = nama;
            document.getElementById('catatan_pembina').value = catatan;
            
            // Reset radio buttons
            document.querySelectorAll('input[name="nilai"]').forEach(radio => {
                radio.checked = false;
            });
            
            // Set nilai yang sudah ada
            if (nilaiSekarang && nilaiSekarang !== 'null') {
                document.getElementById('nilai' + nilaiSekarang).checked = true;
            }
        }

        // Filter ekstrakurikuler
        document.getElementById('filterEskul').addEventListener('change', function() {
            filterTable();
        });

        // Filter nilai
        document.getElementById('filterNilai').addEventListener('change', function() {
            filterTable();
        });

        // Search siswa
        document.getElementById('searchSiswa').addEventListener('keyup', function() {
            filterTable();
        });

        function filterTable() {
            const filterEskul = document.getElementById('filterEskul').value;
            const filterNilai = document.getElementById('filterNilai').value;
            const searchText = document.getElementById('searchSiswa').value.toLowerCase();
            
            const rows = document.querySelectorAll('#tabelPenilaian tr[data-eskul]');
            
            rows.forEach(row => {
                const eskul = row.getAttribute('data-eskul');
                const nilai = row.getAttribute('data-nilai');
                const nama = row.getAttribute('data-nama');
                
                let showRow = true;
                
                // Filter eskul
                if (filterEskul && eskul !== filterEskul) {
                    showRow = false;
                }
                
                // Filter nilai
                if (filterNilai !== '' && nilai !== filterNilai) {
                    showRow = false;
                }
                
                // Search nama
                if (searchText && !nama.includes(searchText)) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
    </script>
<?php include __DIR__ . '/../includes/berry_shell_close.php'; ?>
<?php
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$page_title = 'Sertifikat';
$current_user = getCurrentUser();

$eskul_saya = query("
    SELECT 
        e.*,
        ae.tanggal_daftar,
        ae.id as anggota_id,
        ae.nilai,
        ae.tanggal_penilaian,
        ae.catatan_pembina,
        u.name as pembina,
        (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id AND p.status = 'hadir') as total_hadir,
        (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id) as total_pertemuan
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON e.pembina_id = u.id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY ae.tanggal_daftar DESC
", [$current_user['id']], 'i');

$prestasi = query("
    SELECT p.*, e.nama_ekskul
    FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.user_id = ?
    ORDER BY p.tanggal DESC
", [$current_user['id']], 'i');

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.sertifikat-card {
  transition: all 0.3s;
  border: 2px solid #e0e0e0;
  border-radius: 20px;
}
.sertifikat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 15px 35px rgba(15,23,42,0.15);
  border-color: var(--bs-primary);
}
.sertifikat-preview {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
  border-radius: 16px;
  color: #fff;
  text-align: center;
}
.nilai-badge-big {
  display: inline-block;
  padding: 10px 20px;
  border-radius: 50px;
  font-size: 1.1rem;
  font-weight: 700;
  margin: 10px 0;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.nilai-A { background: linear-gradient(135deg, #28a745, #20c997); color: #fff; }
.nilai-B { background: linear-gradient(135deg, #ffc107, #fd7e14); color: #fff; }
.nilai-C { background: linear-gradient(135deg, #dc3545, #e83e8c); color: #fff; }
@media print {
  .no-print { display: none !important; }
  .pc-header, .pc-sidebar, .pc-footer { display: none !important; }
  .pc-container { margin-left: 0 !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 no-print">
  <div>
    <span class="badge bg-light text-success mb-2"><i class="bi bi-award"></i> Sertifikat</span>
    <h3 class="fw-bold mb-1">Cetak Sertifikat Ekstrakurikuler</h3>
    <p class="text-muted mb-0">Cetak sertifikat keikutsertaan dan prestasi yang telah Anda capai.</p>
  </div>
  <a href="#" onclick="window.print()" class="btn btn-outline-primary rounded-pill">
    <i class="bi bi-printer"></i> Print Halaman
  </a>
</div>

<div class="alert alert-info mb-4">
  <i class="bi bi-info-circle"></i>
  Sertifikat dapat dicetak ketika kehadiran minimal 75% dan pembina telah memberikan penilaian. Nilai dan catatan pembina akan tercantum pada sertifikat.
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0 text-white"><i class="bi bi-award "></i> Sertifikat Keikutsertaan</h5>
  </div>
  <div class="card-body">
    <?php if ($eskul_saya && $eskul_saya->num_rows > 0): ?>
      <div class="row">
        <?php while ($e = $eskul_saya->fetch_assoc()): 
          $total_pertemuan = max(1, (int)$e['total_pertemuan']);
          $persentase_hadir = round($e['total_hadir'] / $total_pertemuan * 100);
        ?>
          <div class="col-md-6 mb-4">
            <div class="card sertifikat-card h-100">
              <div class="card-body">
                <div class="sertifikat-preview mb-3">
                  <i class="bi bi-award-fill" style="font-size:4rem;"></i>
                  <h5 class="mt-3 mb-0">SERTIFIKAT</h5>
                  <small>Keikutsertaan Ekstrakurikuler</small>
                </div>
                <h5 class="text-primary mb-3"><?php echo htmlspecialchars($e['nama_ekskul']); ?></h5>

                <?php if (!empty($e['nilai'])): ?>
                  <div class="text-center mb-3">
                    <span class="nilai-badge-big nilai-<?php echo $e['nilai']; ?>">⭐ NILAI <?php echo $e['nilai']; ?> ⭐</span>
                    <?php if ($e['tanggal_penilaian']): ?>
                      <div><small class="text-muted">Dinilai: <?php echo formatTanggal($e['tanggal_penilaian']); ?></small></div>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning text-center mb-3">
                    <small>⏳ Penilaian pembina belum tersedia</small>
                  </div>
                <?php endif; ?>

                <?php if (!empty($e['catatan_pembina'])): ?>
                  <div class="alert alert-info mb-3">
                    <small><strong>Catatan Pembina:</strong><br><?php echo nl2br(htmlspecialchars($e['catatan_pembina'])); ?></small>
                  </div>
                <?php endif; ?>

                <small class="text-muted d-block mb-1"><i class="bi bi-person"></i> Pembina: <?php echo htmlspecialchars($e['pembina'] ?? '-'); ?></small>
                <small class="text-muted d-block mb-1"><i class="bi bi-calendar3"></i> Bergabung: <?php echo formatTanggal($e['tanggal_daftar']); ?></small>
                <small class="text-muted d-block mb-3"><i class="bi bi-clipboard-check"></i> Kehadiran: <?php echo $e['total_hadir']; ?>/<?php echo $e['total_pertemuan']; ?> (<?php echo $persentase_hadir; ?>%)</small>

                <div class="progress mb-3" style="height:6px;">
                  <div class="progress-bar bg-success" style="width: <?php echo min(100, $persentase_hadir); ?>%;"></div>
                </div>

                <?php if ($persentase_hadir >= 75 && !empty($e['nilai'])): ?>
                  <a href="<?php echo BASE_URL; ?>cetak_sertifikat.php?id=<?php echo $e['anggota_id']; ?>&print=1" target="_blank" class="btn btn-success w-100">
                    <i class="bi bi-printer"></i> Cetak Sertifikat
                  </a>
                <?php else: ?>
                  <button class="btn btn-secondary w-100" disabled>
                    <i class="bi bi-exclamation-triangle"></i> Kehadiran minimal 75% & nilai harus tersedia
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size:4rem;opacity:.2;"></i>
        <h5 class="mt-3 text-muted">Belum ada keikutsertaan aktif</h5>
        <p class="text-muted mb-0">Daftar ekstrakurikuler terlebih dahulu untuk memperoleh sertifikat.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($prestasi && $prestasi->num_rows > 0): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
      <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> Sertifikat Prestasi</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Nama Prestasi</th>
              <th>Peringkat</th>
              <th>Tingkat</th>
              <th>Tanggal</th>
              <th>Sertifikat</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; while ($p = $prestasi->fetch_assoc()): ?>
              <tr>
                <td><?php echo $no++; ?></td>
                <td>
                  <strong><?php echo htmlspecialchars($p['nama_prestasi'] ?? '-'); ?></strong><br>
                  <small class="text-muted"><?php echo htmlspecialchars($p['nama_ekskul']); ?></small>
                </td>
                <td><span class="badge bg-success"><?php echo htmlspecialchars($p['peringkat'] ?? '-'); ?></span></td>
                <td><span class="badge bg-primary"><?php echo ucfirst($p['tingkat']); ?></span></td>
                <td><?php echo formatTanggal($p['tanggal']); ?></td>
                <td>
                  <?php if (!empty($p['sertifikat'])): ?>
                    <a href="<?php echo BASE_URL . $p['sertifikat']; ?>" target="_blank" class="btn btn-sm btn-outline-success">
                      <i class="bi bi-file-earmark-pdf"></i> Lihat
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert-warning">
  <h6 class="fw-bold"><i class="bi bi-exclamation-triangle"></i> Syarat & Ketentuan</h6>
  <ul class="mb-0">
    <li>Sertifikat keikutsertaan dapat dicetak jika kehadiran minimal 75%.</li>
    <li>Nilai diberikan oleh pembina dan akan tercantum di sertifikat.</li>
    <li>Sertifikat prestasi tersedia jika file sudah diunggah oleh pembina.</li>
    <li>Pastikan data profil Anda lengkap sebelum mencetak sertifikat.</li>
  </ul>
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>


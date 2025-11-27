        <?php
// siswa/jadwal.php
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$page_title = 'Jadwal Kegiatan';
$current_user = getCurrentUser();

$hari_ini = date('l');
$hari_indonesia = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hari_sekarang = $hari_indonesia[$hari_ini];

$jadwal_result = query("
    SELECT 
        j.id,
        j.hari,
        j.jam_mulai,
        j.jam_selesai,
        j.lokasi,
        j.keterangan,
        e.nama_ekskul,
        e.id as eskul_id,
        u.name as pembina
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    LEFT JOIN users u ON e.pembina_id = u.id
    WHERE ae.user_id = ? 
      AND ae.status = 'diterima'
      AND j.is_active = 1
    ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), j.jam_mulai
", [$current_user['id']], 'i');

$jadwal = $jadwal_result ? $jadwal_result->fetch_all(MYSQLI_ASSOC) : [];
$jadwal_per_hari = [];
foreach ($jadwal as $j) {
    $jadwal_per_hari[$j['hari']][] = $j;
}
$jadwal_hari_ini = $jadwal_per_hari[$hari_sekarang] ?? [];
$urutan_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.schedule-table td {
  width:14.28%;
  min-width:150px;
  vertical-align: top;
}
.schedule-card {
  border-radius:16px;
  border:1px solid rgba(226,232,240,.7);
}
@media print {
  .pc-header, .pc-sidebar, .pc-footer, .btn, .no-print { display:none !important; }
  .pc-container { margin-left:0 !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-primary mb-2"><i class="bi bi-calendar-week"></i> Jadwal Latihan</span>
    <h3 class="fw-bold mb-1">Jadwal Kegiatan Mingguan</h3>
    <p class="text-muted mb-0">Catat jadwal latihanmu dan pastikan hadir tepat waktu.</p>
  </div>
  <button onclick="window.print()" class="btn btn-outline-primary rounded-pill no-print">
    <i class="bi bi-printer"></i> Cetak Jadwal
  </button>
</div>

<?php if (count($jadwal_hari_ini) > 0): ?>
<div class="alert alert-primary shadow-sm border-0">
  <h5 class="mb-1"><i class="bi bi-bell-fill"></i> Kegiatan Hari Ini - <?php echo $hari_sekarang; ?>, <?php echo formatTanggal(date('Y-m-d')); ?></h5>
  <?php foreach ($jadwal_hari_ini as $j): ?>
    <div class="small mb-1">
      <strong><?php echo htmlspecialchars($j['nama_ekskul']); ?></strong> • 
      <i class="bi bi-clock"></i> <?php echo substr($j['jam_mulai'],0,5); ?> - <?php echo substr($j['jam_selesai'],0,5); ?> WIB • 
      <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($j['lokasi']); ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <?php
  $stats = [
    ['title' => 'Total Jadwal', 'value' => count($jadwal), 'icon' => 'ti ti-calendar', 'bg' => 'bg-primary'],
    ['title' => 'Kegiatan Hari Ini', 'value' => count($jadwal_hari_ini), 'icon' => 'ti ti-calendar-check', 'bg' => 'bg-success'],
    ['title' => 'Hari Aktif', 'value' => count($jadwal_per_hari), 'icon' => 'ti ti-grid-dots', 'bg' => 'bg-warning text-dark'],
  ];
  foreach ($stats as $stat): ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <p class="text-muted mb-1"><?php echo $stat['title']; ?></p>
            <h2 class="mb-0"><?php echo $stat['value']; ?></h2>
          </div>
          <span class="avatar <?php echo $stat['bg']; ?> text-white"><i class="<?php echo $stat['icon']; ?>"></i></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (count($jadwal) > 0): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0 text-white"><i class="bi bi-calendar-range"></i> Jadwal Mingguan</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0 schedule-table">
          <thead class="table-light">
            <tr>
              <?php foreach ($urutan_hari as $hari): ?>
                <th class="text-center <?php echo $hari == $hari_sekarang ? 'table-primary' : ''; ?>">
                  <?php echo $hari; ?>
                  <?php if ($hari == $hari_sekarang): ?><br><span class="badge bg-primary">Hari Ini</span><?php endif; ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr>
              <?php foreach ($urutan_hari as $hari): ?>
                <td class="<?php echo $hari == $hari_sekarang ? 'table-primary bg-opacity-10' : ''; ?>">
                  <?php if (isset($jadwal_per_hari[$hari])): ?>
                    <?php foreach ($jadwal_per_hari[$hari] as $j): ?>
                      <div class="card schedule-card mb-2">
                        <div class="card-body p-2">
                          <div class="fw-bold text-primary small"><?php echo htmlspecialchars($j['nama_ekskul']); ?></div>
                          <div class="small mb-1"><i class="bi bi-clock"></i> <?php echo substr($j['jam_mulai'],0,5); ?> - <?php echo substr($j['jam_selesai'],0,5); ?></div>
                          <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($j['lokasi']); ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center text-muted py-4"><small>Tidak ada kegiatan</small></div>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> Detail Jadwal</h5>
    </div>
    <div class="card-body">
      <?php foreach ($urutan_hari as $hari): ?>
        <?php if (isset($jadwal_per_hari[$hari])): ?>
          <div class="mb-4">
            <h5 class="border-bottom pb-2 mb-3">
              <i class="bi bi-calendar-day text-primary"></i> <?php echo $hari; ?>
              <?php if ($hari == $hari_sekarang): ?><span class="badge bg-primary">Hari Ini</span><?php endif; ?>
            </h5>
            <div class="row g-3">
              <?php foreach ($jadwal_per_hari[$hari] as $j): ?>
                <div class="col-md-6">
                  <div class="card schedule-card h-100">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="text-primary mb-0"><?php echo htmlspecialchars($j['nama_ekskul']); ?></h6>
                        <span class="badge bg-primary">
                          <?php echo (strtotime($j['jam_selesai']) - strtotime($j['jam_mulai']))/3600; ?> jam
                        </span>
                      </div>
                      <div class="mb-2"><i class="bi bi-clock text-primary"></i> <strong><?php echo substr($j['jam_mulai'],0,5); ?> - <?php echo substr($j['jam_selesai'],0,5); ?> WIB</strong></div>
                      <div class="mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> <?php echo htmlspecialchars($j['lokasi']); ?></div>
                      <?php if ($j['pembina']): ?><div class="mb-2"><i class="bi bi-person-fill text-success"></i> <?php echo htmlspecialchars($j['pembina']); ?></div><?php endif; ?>
                      <?php if ($j['keterangan']): ?><div class="mt-2 pt-2 border-top"><small class="text-muted"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($j['keterangan']); ?></small></div><?php endif; ?>
                      <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $j['eskul_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                          <i class="bi bi-eye"></i> Detail Eskul
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-calendar-x text-muted" style="font-size:4rem;opacity:.2;"></i>
      <h5 class="mt-3 text-muted">Belum ada jadwal kegiatan</h5>
      <p class="text-muted">Daftar ekstrakurikuler terlebih dahulu untuk melihat jadwal latihan.</p>
      <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-pencil-square"></i> Daftar Ekstrakurikuler</a>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert-info mt-4">
  <i class="bi bi-info-circle"></i> Jadwal dapat berubah sewaktu-waktu. Selalu cek informasi terbaru dari pembina atau pengumuman.
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>
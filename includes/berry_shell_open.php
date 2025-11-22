<?php
// includes/berry_shell_open.php
$current_script = basename($_SERVER['PHP_SELF'] ?? '');

if (!isset($belum_dinilai)) {
    $belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul 
                            WHERE status='diterima' AND nilai=''")->fetch_assoc()['total'];
}

$role = $current_user['role'] ?? 'guest';
?>
<div class="loader-bg">
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>
<!-- SIDEBAR -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">

    <!-- LOGO -->
    <div class="m-header">
      <a href="<?= BASE_URL ?>admin/dashboard.php" class="b-brand d-flex align-items-center">
        <img src="<?= BASE_URL ?>assets/images/logo MTSN1.png" width="50" class="img-fluid p-1 mt-3">
        <h4 class="mt-4 mx-2">SIK MTSN 1 LEBAK</h4>
      </a>
      <button type="button" class="btn-close-sidebar" id="sidebar-close">
        <i class="ti ti-x"></i>
      </button>
    </div>

    <div class="navbar-content" data-simplebar>
      <ul class="pc-navbar">

<!-- ================================================================= -->
<!--  MENU UNTUK PEMBINA (LIMITED) -->
<!-- ================================================================= -->
<?php if ($role === 'pembina'): ?>
    <!-- Dashboard -->
    <li class="pc-item <?= $current_script==='dashboard.php'?'active':'' ?>">
    <a href="<?= BASE_URL ?>pembina/dashboard.php" class="pc-link">
      <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
      <span class="pc-mtext">Dashboard</span>
    </a>
  </li>

    <!-- ANGGOTA -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/anggota/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/anggota/manage.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-users"></i></span>
        <span class="pc-mtext">Anggota</span>
      </a>
    </li>

    <!-- JADWAL -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/jadwal/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/jadwal/index.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
        <span class="pc-mtext">Jadwal Latihan</span>
      </a>
    </li>

    <!-- PRESENSI -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/presensi/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/presensi/index.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-checkbox"></i></span>
        <span class="pc-mtext">Presensi</span>
      </a>
    </li>

    <!-- PRESTASI -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/prestasi/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/prestasi/index.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-medal"></i></span>
        <span class="pc-mtext">Prestasi</span>
      </a>
    </li>

    <!-- GALERI -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/galeri/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/galeri/index.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-photo"></i></span>
        <span class="pc-mtext">Galeri</span>
      </a>
    </li>
    <!-- Pengumuman -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/pembina/pengumuman/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/pengumuman/index.php" class="pc-link">
        <span class="pc-micon"><i class="bi bi-megaphone"></i></span>
        <span class="pc-mtext">Pengumuman</span>
      </a>
    </li>
    <!-- penilaian -->
    <li class="pc-item <?= $current_script==='penilaian_siswa.php'?'active':'' ?>">
        <a class="pc-link" href="<?= BASE_URL ?>pembina/penilaian_siswa.php">
          <span class="pc-micon"><i class="bi bi-stars"></i></span>
          Penilaian Siswa
          <?php if ($belum_dinilai > 0): ?>
            <span class="badge bg-danger ms-2"><?= $belum_dinilai ?></span>
          <?php endif; ?>
        </a>
      </li>
    <!-- LAPORAN -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/laporan/')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/laporan/index.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-file-text"></i></span>
        <span class="pc-mtext">Laporan</span>
      </a>
    </li>
    
    
    <!-- PROFIL -->
    <li class="pc-item <?= $current_script==='profil.php'?'active':'' ?>">
      <a href="<?= BASE_URL ?>pembina/profil.php" class="pc-link">
        <span class="pc-micon"><i class="ti ti-user"></i></span>
        <span class="pc-mtext">Profil</span>
      </a>
    </li>

    <!-- Logout -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/logout')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>admin/logout.php" class="pc-link">
        <span class="pc-micon"><i class="bi bi-box-arrow-left"></i> </span>
        <span class="pc-mtext">Logout</span>
      </a>
    </li>

  <li class="pc-item">
    <a href="<?= BASE_URL ?>" class="pc-link" target="_blank">
      <span class="pc-micon"><i class="ti ti-external-link"></i></span>
      <span class="pc-mtext">Lihat Website</span>
    </a>
  </li>

<?php else: ?>

<!--  MENU ADMIN PENUH -->
  <!-- Dashboard -->
  <li class="pc-item <?= $current_script==='dashboard.php'?'active':'' ?>">
    <a href="<?= BASE_URL ?>admin/dashboard.php" class="pc-link">
      <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
      <span class="pc-mtext">Dashboard</span>
    </a>
  </li>

  <!-- KEANGGOTAAN -->
  <li class="pc-nav-header ms-4">Keanggotaan</li>
  <li class="pc-item pc-trigger 
      <?= strpos($_SERVER['PHP_SELF'],'/admin/users/')!==false ||
          strpos($_SERVER['PHP_SELF'],'/admin/anggota/')!==false ||
          strpos($_SERVER['PHP_SELF'],'/admin/eskul/')!==false ? 'active' : '' ?>">

    <a href="#" class="pc-link">
      <span class="pc-micon"><i class="ti ti-users"></i></span>
      <span class="pc-mtext">Data Keanggotaan</span>
      <span class="pc-arrow"><i class="ti ti-chevron-down"></i></span>
    </a>

    <ul class="pc-submenu">
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/users/index.php">Users</a></li>
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/anggota/manage.php">Anggota</a></li>
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/eskul/index.php">Ekstrakurikuler</a></li>
    </ul>
  </li>

  <!-- KEGIATAN -->
  <li class="pc-nav-header ms-4">Kegiatan</li>
  <li class="pc-item pc-trigger 
      <?= strpos($_SERVER['PHP_SELF'],'/admin/jadwal/')!==false ||
          strpos($_SERVER['PHP_SELF'],'/admin/presensi/')!==false ? 'active':'' ?>">

    <a href="#" class="pc-link">
      <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
      <span class="pc-mtext">Kegiatan & Presensi</span>
      <span class="pc-arrow"><i class="ti ti-chevron-down"></i></span>
    </a>

    <ul class="pc-submenu">
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/jadwal/index.php">Jadwal</a></li>
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/presensi/index.php">Presensi</a></li>
    </ul>
  </li>

  <!-- PENILAIAN -->
  <li class="pc-nav-header ms-4">Penilaian</li>
  <li class="pc-item pc-trigger 
      <?= $current_script==='penilaian_siswa.php' ||
          strpos($_SERVER['PHP_SELF'],'/admin/prestasi/')!==false ? 'active':'' ?>">

    <a href="#" class="pc-link">
      <span class="pc-micon"><i class="ti ti-medal"></i></span>
      <span class="pc-mtext">Penilaian & Prestasi</span>
      <span class="pc-arrow"><i class="ti ti-chevron-down"></i></span>
    </a>

    <ul class="pc-submenu">
      <li class="pc-item">
        <a class="pc-link" href="<?= BASE_URL ?>admin/penilaian_siswa.php">
          Penilaian Siswa
          <?php if ($belum_dinilai > 0): ?>
            <span class="badge bg-danger ms-2"><?= $belum_dinilai ?></span>
          <?php endif; ?>
        </a>
      </li>

      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/prestasi/index.php">Prestasi</a></li>
    </ul>
  </li>

  <!-- MEDIA -->
  <li class="pc-nav-header ms-4">Media</li>
  <li class="pc-item pc-trigger 
      <?= strpos($_SERVER['PHP_SELF'],'/admin/berita/')!==false ||
          strpos($_SERVER['PHP_SELF'],'/admin/pengumuman')!==false ||
          strpos($_SERVER['PHP_SELF'],'/admin/galeri/')!==false ? 'active':'' ?>">

    <a href="#" class="pc-link">
      <span class="pc-micon"><i class="ti ti-photo"></i></span>
      <span class="pc-mtext">Media Informasi</span>
      <span class="pc-arrow"><i class="ti ti-chevron-down"></i></span>
    </a>

    <ul class="pc-submenu">
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/berita/manage.php">Berita</a></li>
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/galeri/index.php">Galeri</a></li>
      <li class="pc-item"><a class="pc-link" href="<?= BASE_URL ?>admin/pengumuman/index.php">Pengumuman</a></li>
    </ul>
  </li>

  <!-- ADMINISTRASI -->
  <li class="pc-nav-header ms-4">Administrasi</li>
  <li class="pc-item <?= $current_script==='pengaturan_sertifikat.php' ? 'active':'' ?>">
    <a href="<?= BASE_URL ?>admin/pengaturan_sertifikat.php" class="pc-link">
      <span class="pc-micon"><i class="ti ti-settings"></i></span>
      <span class="pc-mtext">Pengaturan Sertifikat</span>
    </a>
  </li>

  <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/laporan/')!==false ? 'active':'' ?>">
    <a href="<?= BASE_URL ?>admin/laporan/index.php" class="pc-link">
      <span class="pc-micon"><i class="ti ti-file-text"></i></span>
      <span class="pc-mtext">Laporan</span>
    </a>
  </li>

  <!-- PROFIL -->
  <li class="pc-nav-header ms-4">Pengguna</li>
  <li class="pc-item <?= $current_script==='profil.php' ? 'active':'' ?>">
    <a href="<?= BASE_URL ?>admin/profil.php" class="pc-link">
      <span class="pc-micon"><i class="ti ti-user"></i></span>
      <span class="pc-mtext">Profil</span>
    </a>
  </li>
    <!-- Logout -->
    <li class="pc-item <?= strpos($_SERVER['PHP_SELF'],'/admin/logout')!==false ? 'active':'' ?>">
      <a href="<?= BASE_URL ?>admin/logout.php" class="pc-link">
        <span class="pc-micon"><i class="bi bi-box-arrow-left"></i> </span>
        <span class="pc-mtext">Logout</span>
      </a>
    </li>
  <hr>

  <li class="pc-item">
    <a href="<?= BASE_URL ?>" class="pc-link" target="_blank">
      <span class="pc-micon"><i class="ti ti-external-link"></i></span>
      <span class="pc-mtext">Lihat Website</span>
    </a>
  </li>

<?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<!-- [ Header ] start -->
<header class="pc-header">
  <div class="header-wrapper">
    <div class="me-auto">
      <ul class="list-unstyled m-0 d-flex align-items-center gap-2">
        <li class="pc-h-item d-inline-block">
          <a href="#" id="sidebar-hide" class="pc-head-link icon-pill mt-4 me-1" aria-label="Collapse sidebar"><i class="ti ti-menu-2"></i></a>
        </li>
        <li>
           <div class="badge bg-light text-success fs-6">
            <i class="ti ti-school"></i> MTsN 1 Lebak
          </div>
        </li>
      </ul>
    </div>
    <div class="ms-auto">
      <ul class="list-unstyled m-0 d-flex align-items-center gap-1">
        <li class="pc-h-item">
           <div class="px-3 pb-2 fw-semibold"><?php echo htmlspecialchars($current_user['name'] ?? 'User'); ?></div>
        </li>
        <li class="pc-h-item dropdown">
          <a href="#" class="pc-head-link avatar-pill" style="width:100px; height:40px;" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if (!empty($current_user['foto'])): ?>
              <img src="<?php echo BASE_URL . $current_user['foto']; ?>" alt="avatar" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;" />
            <?php else: ?>
              <span class="avatar bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                <i class="ti ti-user text-white"></i>
              </span>
            <?php endif; ?>
            <i class="ti ti-settings settings"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0">
            <div class="px-3 py-2 small text-muted">Signed in as</div>
            <div class="px-3 pb-2 fw-semibold"><?php echo htmlspecialchars($current_user['name'] ?? 'User'); ?></div>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/profil.php"><i class="ti ti-user me-2"></i>Profil</a>
            <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>admin/logout.php"><i class="ti ti-logout me-2"></i>Logout</a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>
<!-- [ Header ] end -->


<div class="pc-container"><div class="pc-content">

<?php
// includes/berry_siswa_shell_open.php
$current_script = basename($_SERVER['PHP_SELF'] ?? '');
if (!isset($current_user) || !$current_user) {
  $current_user = getCurrentUser();
}

$pending_pendaftaran = query(
  "SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'pending'",
  [$current_user['id']],
  'i'
)->fetch_assoc()['total'] ?? 0;

$menu_items = [
  ['label' => 'Dashboard', 'icon' => 'ti ti-dashboard', 'file' => 'dashboard.php', 'url' => BASE_URL . 'siswa/dashboard.php'],
  ['label' => 'Jadwal', 'icon' => 'ti ti-calendar-time', 'file' => 'jadwal.php', 'url' => BASE_URL . 'siswa/jadwal.php'],
  ['label' => 'Presensi', 'icon' => 'ti ti-clipboard-check', 'file' => 'index.php', 'url' => BASE_URL . 'siswa/presensi/index.php'],
  ['label' => 'Prestasi', 'icon' => 'ti ti-medal', 'file' => 'prestasi.php', 'url' => BASE_URL . 'siswa/prestasi.php'],
  ['label' => 'Berita', 'icon' => 'ti ti-news', 'file' => 'berita.php', 'url' => BASE_URL . 'siswa/berita.php'],
  ['label' => 'Galeri', 'icon' => 'ti ti-photo', 'file' => 'galeri.php', 'url' => BASE_URL . 'siswa/galeri.php'],
  ['label' => 'Sertifikat', 'icon' => 'ti ti-certificate', 'file' => 'sertifikat.php', 'url' => BASE_URL . 'siswa/sertifikat.php'],
  ['label' => 'Profil', 'icon' => 'ti ti-user', 'file' => 'profil.php', 'url' => BASE_URL . 'siswa/profil.php'],
];
?>

<div class="loader-bg">
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>

<nav class="pc-sidebar pc-mob-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="<?php echo BASE_URL; ?>siswa/dashboard.php" class="b-brand text-white d-flex align-items-center">
        <img src="<?php echo BASE_URL; ?>assets/images/logo MTSN1.png" alt="Logo" width="46" class="img-fluid p-1">
        <span class="ms-2 fw-bold">Portal Siswa</span>
      </a>
      <button type="button" class="btn btn-icon btn-light text-dark d-lg-none" id="sidebar-close">
        <i class="ti ti-x"></i>
      </button>
    </div>
    <div class="navbar-content" data-simplebar>
      <ul class="pc-navbar">
        <?php foreach ($menu_items as $item): ?>
        <li class="pc-item <?php echo $current_script === $item['file'] ? 'active' : ''; ?>">
          <a href="<?php echo $item['url']; ?>" class="pc-link">
            <span class="pc-micon"><i class="<?php echo $item['icon']; ?>"></i></span>
            <span class="pc-mtext"><?php echo $item['label']; ?></span>
          </a>
        </li>
        <?php endforeach; ?>

        <li class="pc-nav-header p-4">Layanan Siswa</li>
        <li class="pc-item">
          <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="pc-link">
            <span class="pc-micon"><i class="ti ti-edit-circle"></i></span>
            <span class="pc-mtext">Daftar Eskul</span>
            <?php if ($pending_pendaftaran > 0): ?>
              <span class="badge bg-warning text-dark ms-auto"><?php echo $pending_pendaftaran; ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="pc-item">
          <a class="pc-link" href="<?php echo BASE_URL; ?>siswa/logout.php">
            <span class="pc-micon"><i class="ti ti-logout me-2"></i></span>
            <span class="pc-mtext">Logout</span>
          </a>   
        </li>
        <li class="pc-item">
          <a href="<?php echo BASE_URL; ?>" class="pc-link" target="_blank">
            <span class="pc-micon"><i class="ti ti-external-link"></i></span>
            <span class="pc-mtext">Beranda Website</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div class="pc-sidebar-overlay" id="pc-sidebar-overlay"></div>

<header class="pc-header">
  <div class="header-wrapper">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-icon btn-light d-lg-none" id="sidebar-hide"><i class="ti ti-menu-2"></i></button>
      <div class="student-pill">
        <i class="ti ti-school"></i> MTsN 1 Lebak
      </div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="text-end">
        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($current_user['name']); ?></div>
        <small class="text-muted">NISN: <?php echo htmlspecialchars($current_user['nisn']); ?></small>
      </div>
      <div class="dropdown">
        <a href="#" class="avatar border dropdown-toggle" data-bs-toggle="dropdown">
          <?php if (!empty($current_user['foto']) && file_exists(__DIR__ . '/../' . $current_user['foto'])): ?>
            <img src="<?php echo BASE_URL . $current_user['foto']; ?>" alt="avatar" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
          <?php else: ?>
            <span class="avatar bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
              <i class="ti ti-user"></i>
            </span>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-end shadow">
          <a class="dropdown-item" href="<?php echo BASE_URL; ?>siswa/profil.php"><i class="ti ti-user me-2"></i> Profil Saya</a>
          <a class="dropdown-item" href="<?php echo BASE_URL; ?>siswa/jadwal.php"><i class="ti ti-calendar me-2"></i> Jadwal</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>siswa/logout.php"><i class="ti ti-logout me-2"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="pc-container">
  <div class="pc-content">


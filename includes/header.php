<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Sistem Ekstrakurikuler MTsN 1 Lebak</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap CSS (to support existing navbar/footer markup) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <!-- Font Google -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>
    <!-- Navbar (Tailwind) -->
    <header class="sticky top-0 z-40 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-900/95 text-white backdrop-blur  border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <a href="<?php echo BASE_URL; ?>" class="flex items-center gap-2 text-white font-bold">
                    <i class="bi bi-mortarboard-fill"></i>
                    <span>MTsN 1 Lebak</span>
                </a>
                <button id="navToggle" class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-md text-white hover:bg-emerald-600/60" aria-label="Toggle navigation">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <nav id="navMenu" class="hidden lg:block">
                    <ul class="flex flex-col lg:flex-row gap-2 lg:gap-4 text-emerald-50">
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'index.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>">
                                <i class="bi bi-house-fill"></i> Beranda
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'profile_eskul.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>profile_eskul.php">
                                <i class="bi bi-grid-fill"></i> Eskul
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'update_kegiatan.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>update_kegiatan.php">
                                <i class="bi bi-newspaper"></i> Kegiatan
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_id']) && hasRole(['siswa'])): ?>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'galeri.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>siswa/galeri.php">
                                <i class="bi bi-images"></i> Galeri
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'daftar_eskul.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>daftar_eskul.php">
                                <i class="bi bi-pencil-square"></i> Daftar
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'cetak_sertifikat.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>cetak_sertifikat.php">
                                <i class="bi bi-award-fill"></i> Sertifikat
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="relative group">
                            <button class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                <i class="bi bi-caret-down-fill text-xs"></i>
                            </button>
                            <ul class="absolute right-0 mt-2 w-56 bg-white text-slate-700 rounded-xl shadow-lg ring-1 ring-slate-900/10 p-2 hidden group-hover:block">
                                <?php if (hasRole(['admin', 'pembina'])): ?>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                                <?php else: ?>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/profil.php"><i class="bi bi-person"></i> Profil</a></li>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/jadwal.php"><i class="bi bi-calendar-week"></i> Jadwal</a></li>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/berita.php"><i class="bi bi-newspaper"></i> Berita</a></li>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/galeri.php"><i class="bi bi-images"></i> Galeri</a></li>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/prestasi.php"><i class="bi bi-trophy"></i> Prestasi</a></li>
                                    <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?>siswa/sertifikat.php"><i class="bi bi-award"></i> Sertifikat</a></li>
                                <?php endif; ?>
                                <li><hr class="my-1 border-slate-200"></li>
                                <li><a class="block px-3 py-2 rounded-lg hover:bg-slate-100" href="<?php echo BASE_URL; ?><?php echo hasRole('siswa') ? 'siswa' : 'admin'; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                        <?php else: ?>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'registerasi.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>registerasi.php">
                                <i class="bi bi-person-plus-fill"></i> Registrasi
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-emerald-600/60 <?php echo $current_page == 'login.php' ? 'bg-emerald-600/60' : ''; ?>" href="<?php echo BASE_URL; ?>admin/login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <!-- mobile dropdown -->
            <nav id="navMenuMobile" class="hidden lg:hidden pb-3">
                <ul class="grid gap-1 text-emerald-50">
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>">Beranda</a></li>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>profile_eskul.php">Eskul</a></li>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>update_kegiatan.php">Kegiatan</a></li>
                    <?php if (isset($_SESSION['user_id']) && hasRole(['siswa'])): ?>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>siswa/galeri.php">Galeri</a></li>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>daftar_eskul.php">Daftar</a></li>
                    <?php endif; ?>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>cetak_sertifikat.php">Sertifikat</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?><?php echo hasRole('siswa') ? 'siswa' : 'admin'; ?>/logout.php">Logout</a></li>
                    <?php else: ?>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>registerasi.php">Registrasi</a></li>
                    <li><a class="block px-3 py-2 rounded-lg hover:bg-emerald-600/60" href="<?php echo BASE_URL; ?>admin/login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var t=document.getElementById('navToggle');
        var m=document.getElementById('navMenuMobile');
        if(t&&m){ t.addEventListener('click', function(){ m.classList.toggle('hidden'); }); }
      });
    </script>

    <?php
    // Tampilkan flash message jika ada
    $flash = getFlash();
    if ($flash):
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
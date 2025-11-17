<?php
// index.php
$page_title = 'Beranda';
require_once 'includes/header.php';

// Statistik
$total_eskul = query("SELECT COUNT(*) as total FROM ekstrakurikulers WHERE status = 'aktif'")->fetch_assoc()['total'];
$total_siswa = query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa' AND is_active = 1")->fetch_assoc()['total'];
$total_anggota = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima'")->fetch_assoc()['total'];

// Eskul populer
$eskul_populer = query("
    SELECT e.*, u.name as nama_pembina, COUNT(ae.id) as jumlah_anggota
    FROM ekstrakurikulers e
    LEFT JOIN users u ON e.pembina_id = u.id
    LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id AND ae.status = 'diterima'
    WHERE e.status = 'aktif'
    GROUP BY e.id
    ORDER BY jumlah_anggota DESC
    LIMIT 6
");

// Berita terbaru
$berita_terbaru = query("
    SELECT b.*, e.nama_ekskul
    FROM berita b
    JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
    WHERE b.is_published = 1
    ORDER BY b.created_at DESC
    LIMIT 3
");

// Prestasi terbaru
$prestasi_terbaru = query("
    SELECT p.*, e.nama_ekskul, u.name as nama_siswa
    FROM prestasis p
    JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
    LEFT JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    LEFT JOIN users u ON ae.user_id = u.id
    ORDER BY p.tanggal DESC
    LIMIT 4
");
?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 text-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 pt-20 pb-28 grid lg:grid-cols-2 gap-10 items-center">
        <div class="space-y-6 reveal">
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">Sistem Ekstrakurikuler</h1>
            <h2 class="text-emerald-300 text-2xl font-semibold">MTsN 1 Lebak</h2>
            <p class="text-slate-200 text-lg max-w-2xl">
                Bergabunglah dengan berbagai ekstrakurikuler untuk mengembangkan bakat dan minat Anda.
                Tersedia lebih dari <span class="font-bold text-emerald-300"><?php echo $total_eskul; ?></span> pilihan menarik.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white text-slate-900 font-semibold shadow hover:shadow-lg hover:-translate-y-0.5 transition">
                    <i class="bi bi-pencil-square"></i>
                    Daftar Sekarang
                </a>
                <a href="<?php echo BASE_URL; ?>profile_eskul.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-white/30 text-white font-semibold hover:bg-white/10 backdrop-blur transition">
                    <i class="bi bi-grid-fill"></i>
                    Lihat Eskul
                </a>
            </div>
        </div>
        <div class="relative text-center lg:text-right reveal">
            <div class="absolute -top-10 -right-10 h-56 w-56 bg-emerald-400/20 rounded-full blur-3xl"></div>
            <img src="<?php echo BASE_URL; ?>assets/images/logo MTSN1.png" alt="Logo MTsN 1 Lebak" class="inline-block w-full max-w-md rounded-full mb-28  " />
        </div>
    </div>
</section>

<!-- Statistik (Floating) -->
<section class="relative bg-transparent -mt-16 md:-mt-20 lg:-mt-24 z-10">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="reveal text-center rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/10 p-8">
                <div class="mb-2 text-emerald-600 text-3xl">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <h3 class="counter text-4xl font-extrabold tracking-tight" data-target="<?php echo $total_eskul; ?>">0</h3>
                <p class="text-slate-500 mt-1">Ekstrakurikuler Aktif</p>
            </div>
            <div class="reveal text-center rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/10 p-8">
                <div class="mb-2 text-emerald-600 text-3xl">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h3 class="counter text-4xl font-extrabold tracking-tight" data-target="<?php echo $total_siswa; ?>">0</h3>
                <p class="text-slate-500 mt-1">Siswa Terdaftar</p>
            </div>
            <div class="reveal text-center rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/10 p-8">
                <div class="mb-2 text-emerald-600 text-3xl">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <h3 class="counter text-4xl font-extrabold tracking-tight" data-target="<?php echo $total_anggota; ?>">0</h3>
                <p class="text-slate-500 mt-1">Anggota Aktif</p>
            </div>
        </div>
    </div>
</section>

<!-- Ekstrakurikuler Populer (Carousel) -->
<section class="pt-20 pb-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Ekstrakurikuler Populer</h2>
            <p class="text-slate-500 mt-2">Pilih ekstrakurikuler sesuai minat dan bakat Anda</p>
        </div>

        <div class="relative">
            <!-- Prev/Next -->
            <button type="button" aria-label="Sebelumnya" id="eskulPrev" class="hidden md:flex absolute -left-3 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-900/10 hover:bg-slate-50">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" aria-label="Berikutnya" id="eskulNext" class="hidden md:flex absolute -right-3 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-900/10 hover:bg-slate-50">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Track -->
            <div id="eskulCarousel" class="reveal flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 [-ms-overflow-style:none] [scrollbar-width:none]" style="scrollbar-width: none;">
                <?php while ($eskul = $eskul_populer->fetch_assoc()): ?>
                <div class="snap-start shrink-0 w-[85%] sm:w-[60%] md:w-[45%] lg:w-[32%] bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
                    <?php if ($eskul['gambar']): ?>
                    <img src="<?php echo UPLOAD_URL . $eskul['gambar']; ?>" alt="<?php echo $eskul['nama_ekskul']; ?>" class="h-56 sm:h-64 md:h-72 w-full object-cover" />
                    <?php else: ?>
                    <img src="https://via.placeholder.com/800x350/10b981/ffffff?text=<?php echo urlencode($eskul['nama_ekskul']); ?>" alt="<?php echo $eskul['nama_ekskul']; ?>" class="h-56 sm:h-64 md:h-72 w-full object-cover" />
                    <?php endif; ?>
                    <div class="p-5">
                        <div class="mb-2">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 shadow">
                                <?php echo $eskul['jumlah_anggota']; ?> Anggota
                            </span>
                        </div>
                        <h5 class="text-lg font-semibold text-slate-900"><?php echo $eskul['nama_ekskul']; ?></h5>
                        <p class="text-slate-500 text-sm mt-1"><?php echo substr($eskul['deskripsi'], 0, 100); ?>...</p>
                        <div class="flex items-center gap-2 text-slate-600 text-sm mt-3">
                            <i class="bi bi-person"></i>
                            <span><?php echo $eskul['nama_pembina'] ?? 'Belum ada pembina'; ?></span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $eskul['id']; ?>" class="mt-4 inline-flex w-full justify-center items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
                            <i class="bi bi-eye"></i>
                            Lihat Detail
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="text-center mt-10">
            <a href="<?php echo BASE_URL; ?>profile_eskul.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow transition">
                Lihat Semua Ekstrakurikuler <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Prestasi -->
<?php if ($prestasi_terbaru && $prestasi_terbaru->num_rows > 0): ?>
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Prestasi Terbaru</h2>
            <p class="text-slate-500 mt-2">Kebanggaan siswa MTsN 1 Lebak</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php 
            $badge_color = [
                'internasional' => 'bg-red-100 text-red-700',
                'nasional' => 'bg-blue-100 text-blue-700',
                'provinsi' => 'bg-emerald-100 text-emerald-700',
                'kabupaten' => 'bg-cyan-100 text-cyan-700',
                'kecamatan' => 'bg-amber-100 text-amber-700',
                'sekolah' => 'bg-slate-100 text-slate-700'
            ];
            while ($prestasi = $prestasi_terbaru->fetch_assoc()): 
            ?>
            <div class="reveal rounded-2xl bg-white shadow ring-1 ring-slate-900/10 p-6 text-center">
                <div class="mb-3 text-amber-500 text-5xl">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <?php $cls = $badge_color[$prestasi['tingkat']] ?? 'bg-slate-100 text-slate-700'; ?>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold mb-2 <?php echo $cls; ?>">
                    <?php echo ucfirst($prestasi['tingkat']); ?>
                </span>
                <h5 class="text-lg font-semibold text-slate-900"><?php echo $prestasi['nama_prestasi']; ?></h5>
                <p class="text-amber-600 font-bold mt-1"><?php echo $prestasi['peringkat']; ?></p>
                <p class="text-slate-500 text-sm mb-2"><?php echo $prestasi['nama_ekskul']; ?></p>
                <?php if ($prestasi['nama_siswa']): ?>
                <p class="text-slate-700 text-sm"><strong><?php echo $prestasi['nama_siswa']; ?></strong></p>
                <?php endif; ?>
                <div class="text-slate-400 text-xs mt-1">
                    <i class="bi bi-calendar"></i> <?php echo formatTanggal($prestasi['tanggal']); ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Berita & Kegiatan -->
<section class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Berita & Kegiatan Terbaru</h2>
            <p class="text-slate-500 mt-2">Update terkini dari kegiatan ekstrakurikuler</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php if ($berita_terbaru && $berita_terbaru->num_rows > 0): ?>
                <?php while ($berita = $berita_terbaru->fetch_assoc()): ?>
                <div class="reveal bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden flex flex-col">
                    <?php if ($berita['gambar']): ?>
                    <img src="<?php echo UPLOAD_URL . $berita['gambar']; ?>" alt="<?php echo $berita['judul']; ?>" class="h-56 md:h-64 w-full object-cover" />
                    <?php else: ?>
                    <img src="https://via.placeholder.com/800x350/34d399/ffffff?text=Berita" alt="<?php echo $berita['judul']; ?>" class="h-56 md:h-64 w-full object-cover" />
                    <?php endif; ?>
                    
                    <div class="p-5 flex-1">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 mb-2"><?php echo $berita['nama_ekskul']; ?></span>
                        <h5 class="text-lg font-semibold text-slate-900"><?php echo $berita['judul']; ?></h5>
                        <p class="text-slate-500 text-sm mt-1"><?php echo substr(strip_tags($berita['konten']), 0, 120); ?>...</p>
                        <div class="flex items-center gap-4 text-slate-400 text-xs mt-3">
                            <span><i class="bi bi-calendar"></i> <?php echo formatTanggal($berita['tanggal_post']); ?></span>
                            <span><i class="bi bi-eye"></i> <?php echo $berita['views']; ?></span>
                        </div>
                    </div>
                    <div class="p-5 pt-0">
                        <a href="<?php echo BASE_URL; ?>post_berita.php?id=<?php echo $berita['id']; ?>" class="inline-flex w-full justify-center items-center gap-2 px-4 py-2 rounded-xl border border-emerald-600 text-emerald-700 font-semibold hover:bg-emerald-50 transition">
                            Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-1 md:col-span-3">
                    <div class="text-center rounded-xl bg-white ring-1 ring-slate-900/10 p-6 text-slate-600">Belum ada berita</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-10">
            <a href="<?php echo BASE_URL; ?>update_kegiatan.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-emerald-600 text-emerald-700 font-semibold hover:bg-emerald-50 transition">
                Lihat Semua Berita <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 text-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-900">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold mb-3">Siap Bergabung?</h2>
        <p class="text-emerald-100 text-lg mb-6">Daftarkan diri Anda sekarang dan kembangkan potensi terbaik Anda!</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-emerald-800 font-semibold hover:shadow-lg hover:-translate-y-0.5 transition">
                <i class="bi bi-pencil-square"></i>
                Daftar Ekstrakurikuler
            </a>
            <a href="<?php echo BASE_URL; ?>cetak_sertifikat.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-white/40 text-white font-semibold hover:bg-white/10 transition">
                <i class="bi bi-award"></i>
                Cetak Sertifikat
            </a>
        </div>
    </div>
</section>

<!-- GSAP Animations -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof gsap !== 'undefined') {
      gsap.registerPlugin(ScrollTrigger);

      // Reveal on scroll
      gsap.utils.toArray('.reveal').forEach(function (el) {
        gsap.from(el, {
          y: 30,
          opacity: 0,
          duration: 0.8,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: el,
            start: 'top 85%'
          }
        });
      });

      // Counter animation
      const counters = document.querySelectorAll('.counter');
      counters.forEach(function (counter) {
        const target = parseInt(counter.getAttribute('data-target') || '0', 10);
        ScrollTrigger.create({
          trigger: counter,
          start: 'top 90%',
          once: true,
          onEnter: function () {
            gsap.fromTo(counter, { innerText: 0 }, {
              innerText: target,
              duration: 1.6,
              ease: 'power1.out',
              snap: { innerText: 1 },
              onUpdate: function () {
                counter.innerText = Math.floor(counter.innerText);
              }
            });
          }
        });
      });
    }

    // Simple carousel controls for Eskul Populer
    var track = document.getElementById('eskulCarousel');
    var prev = document.getElementById('eskulPrev');
    var next = document.getElementById('eskulNext');
    if (track && prev && next) {
      var step = track.clientWidth * 0.6; // scroll 60% width each click
      prev.addEventListener('click', function(){ track.scrollBy({ left: -step, behavior: 'smooth' }); });
      next.addEventListener('click', function(){ track.scrollBy({ left: step, behavior: 'smooth' }); });
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>
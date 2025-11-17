<?php
// profile_eskul.php
$page_title = 'Profil Ekstrakurikuler';
require_once 'includes/header.php';

// Jika ada ID, tampilkan detail
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $eskul = query("
        SELECT e.*, u.name as nama_pembina, u.email as email_pembina
        FROM ekstrakurikulers e
        LEFT JOIN users u ON e.pembina_id = u.id
        WHERE e.id = ? AND e.status = 'aktif'
    ", [$id], 'i')->fetch_assoc();
    
    if (!$eskul) {
        setFlash('danger', 'Ekstrakurikuler tidak ditemukan!');
        redirect('profile_eskul.php');
    }
    
    // Ambil anggota
    $anggota = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE ekstrakurikuler_id = ? AND status = 'diterima'", [$id], 'i')->fetch_assoc()['total'];
    
    // Ambil jadwal
    $jadwal = query("SELECT * FROM jadwal_latihans WHERE ekstrakurikuler_id = ? AND is_active = 1 ORDER BY 
        CASE hari 
            WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 
            WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 WHEN 'Minggu' THEN 7 
        END", [$id], 'i');
    
    // Ambil berita eskul
    $berita = query("SELECT * FROM berita WHERE ekstrakurikuler_id = ? AND is_published = 1 ORDER BY created_at DESC LIMIT 3", [$id], 'i');
    
    // Ambil prestasi
    $prestasi = query("SELECT * FROM prestasis WHERE ekstrakurikuler_id = ? ORDER BY tanggal DESC LIMIT 5", [$id], 'i');
    
    // Ambil galeri
    $galeri = query("SELECT * FROM galeris WHERE ekstrakurikuler_id = ? AND is_active = 1 ORDER BY tanggal_upload DESC LIMIT 6", [$id], 'i');
    ?>
    
    <!-- Detail Eskul (Tailwind) -->
    <section class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 text-white">
      <div class="max-w-7xl mx-auto px-6 py-10">
        <nav class="text-sm text-emerald-100/80 mb-5">
          <a href="<?php echo BASE_URL; ?>" class="hover:text-white">Beranda</a>
          <span class="mx-2">/</span>
          <a href="<?php echo BASE_URL; ?>profile_eskul.php" class="hover:text-white">Ekstrakurikuler</a>
          <span class="mx-2">/</span>
          <span class="text-white font-semibold"><?php echo $eskul['nama_ekskul']; ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
          <!-- Main -->
          <div class="lg:col-span-2">
            <div class="bg-white text-slate-800 rounded-2xl shadow-xl ring-1 ring-slate-900/10 overflow-hidden">
              <?php if ($eskul['gambar']): ?>
              <img src="<?php echo UPLOAD_URL . $eskul['gambar']; ?>" alt="<?php echo $eskul['nama_ekskul']; ?>" class="w-full h-60 md:h-80 object-cover" />
              <?php endif; ?>
              <div class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                  <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900"><?php echo $eskul['nama_ekskul']; ?></h2>
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                      <i class="bi bi-people-fill mr-1"></i> <?php echo $anggota; ?>/<?php echo $eskul['kuota']; ?> Anggota
                    </span>
                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                      <i class="bi bi-check-circle mr-1"></i> Aktif
                    </span>
                  </div>
                </div>

                <h5 class="font-semibold text-emerald-700 mb-2">Deskripsi</h5>
                <p class="text-slate-600 leading-relaxed"><?php echo nl2br($eskul['deskripsi']); ?></p>

                <div class="my-6 h-px bg-slate-200"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
                    <h6 class="text-slate-700 font-semibold mb-1"><i class="bi bi-person-fill"></i> Pembina</h6>
                    <p class="text-slate-600 mb-0"><?php echo $eskul['nama_pembina'] ?? 'Belum ada pembina'; ?></p>
                  </div>
                  <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
                    <h6 class="text-slate-700 font-semibold mb-1"><i class="bi bi-people"></i> Kuota</h6>
                    <p class="text-slate-600 mb-0"><?php echo $eskul['kuota']; ?> siswa</p>
                  </div>
                </div>

                <div class="mt-6">
                  <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow">
                    <i class="bi bi-pencil-square"></i> Daftar Sekarang
                  </a>
                </div>
              </div>
            </div>

            <!-- Jadwal -->
            <?php if ($jadwal && $jadwal->num_rows > 0): ?>
            <div class="mt-6 bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-200">
                <h5 class="font-bold text-slate-900"><i class="bi bi-calendar-check"></i> Jadwal Latihan</h5>
              </div>
              <div class="p-6">
                <div class="divide-y divide-slate-200">
                  <?php while ($j = $jadwal->fetch_assoc()): ?>
                  <div class="py-3 flex items-start justify-between">
                    <div>
                      <strong class="text-slate-800"><?php echo $j['hari']; ?></strong>
                      <div class="text-slate-500 text-sm">
                        <i class="bi bi-clock"></i> <?php echo substr($j['jam_mulai'], 0, 5); ?> - <?php echo substr($j['jam_selesai'], 0, 5); ?>
                        <br><i class="bi bi-geo-alt"></i> <?php echo $j['lokasi']; ?>
                      </div>
                    </div>
                  </div>
                  <?php endwhile; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Prestasi -->
            <?php if ($prestasi && $prestasi->num_rows > 0): ?>
            <div class="mt-6 bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-200">
                <h5 class="font-bold text-slate-900"><i class="bi bi-trophy-fill text-amber-500"></i> Prestasi</h5>
              </div>
              <div class="p-6">
                <div class="divide-y divide-slate-200">
                  <?php while ($p = $prestasi->fetch_assoc()): ?>
                  <div class="py-3">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><?php echo ucfirst($p['tingkat']); ?></span>
                    <h6 class="mt-2 font-semibold text-slate-800"><?php echo $p['nama_prestasi']; ?></h6>
                    <p class="text-amber-600 font-bold mb-1"><?php echo $p['peringkat']; ?></p>
                    <div class="text-slate-500 text-xs"><i class="bi bi-calendar"></i> <?php echo formatTanggal($p['tanggal']); ?></div>
                  </div>
                  <?php endwhile; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Galeri -->
            <?php if ($galeri && $galeri->num_rows > 0): ?>
            <div class="mt-6 bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-200">
                <h5 class="font-bold text-slate-900"><i class="bi bi-images"></i> Galeri Foto</h5>
              </div>
              <div class="p-6">
                <div class="grid grid-cols-3 gap-2">
                  <?php while ($g = $galeri->fetch_assoc()): ?>
                  <img src="<?php echo UPLOAD_URL . $g['gambar']; ?>" alt="<?php echo $g['judul']; ?>" class="h-36 w-full object-cover rounded-lg ring-1 ring-slate-200" />
                  <?php endwhile; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Berita Eskul -->
            <?php if ($berita && $berita->num_rows > 0): ?>
            <div class="mt-6 bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-200">
                <h5 class="font-bold text-slate-900"><i class="bi bi-newspaper"></i> Berita & Kegiatan</h5>
              </div>
              <div class="p-6">
                <div class="divide-y divide-slate-200">
                  <?php while ($b = $berita->fetch_assoc()): ?>
                  <div class="py-3">
                    <a href="<?php echo BASE_URL; ?>post_berita.php?id=<?php echo $b['id']; ?>" class="font-semibold text-slate-800 hover:text-emerald-700"><?php echo $b['judul']; ?></a>
                    <div class="text-slate-500 text-xs mt-1"><i class="bi bi-calendar"></i> <?php echo formatTanggal($b['tanggal_post']); ?></div>
                  </div>
                  <?php endwhile; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Sidebar -->
          <aside class="space-y-4">
            <div class="bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
              <div class="px-6 py-4 bg-emerald-600 text-white">
                <h6 class="font-semibold"><i class="bi bi-info-circle"></i> Informasi</h6>
              </div>
              <div class="p-6">
                <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                  <i class="bi bi-exclamation-circle"></i> Pendaftaran akan diverifikasi oleh admin sebelum Anda resmi menjadi anggota.
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-200">
                <h6 class="font-semibold text-slate-800"><i class="bi bi-grid"></i> Eskul Lainnya</h6>
              </div>
              <div class="p-6 space-y-2">
                <?php
                $other_eskul = query("SELECT * FROM ekstrakurikulers WHERE status = 'aktif' AND id != ? ORDER BY RAND() LIMIT 5", [$id], 'i');
                while ($other = $other_eskul->fetch_assoc()):
                ?>
                <div>
                  <a href="?id=<?php echo $other['id']; ?>" class="inline-flex items-center gap-2 text-slate-700 hover:text-emerald-700"><i class="bi bi-arrow-right-circle"></i> <?php echo $other['nama_ekskul']; ?></a>
                </div>
                <?php endwhile; ?>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <?php
} else {
    // Tampilkan semua eskul
    $all_eskul = query("
        SELECT e.*, u.name as nama_pembina, COUNT(ae.id) as jumlah_anggota 
        FROM ekstrakurikulers e 
        LEFT JOIN users u ON e.pembina_id = u.id
        LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id AND ae.status = 'diterima'
        WHERE e.status = 'aktif' 
        GROUP BY e.id 
        ORDER BY e.nama_ekskul
    ");
    ?>
    
    <section class="py-14 bg-slate-50">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-8">
          <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">Daftar Ekstrakurikuler</h2>
          <p class="text-slate-500">Pilih ekstrakurikuler yang sesuai dengan minat Anda</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php while ($eskul = $all_eskul->fetch_assoc()): ?>
          <div class="reveal bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden flex flex-col">
            <?php if ($eskul['gambar']): ?>
            <img src="<?php echo UPLOAD_URL . $eskul['gambar']; ?>" alt="<?php echo $eskul['nama_ekskul']; ?>" class="h-48 md:h-56 w-full object-cover" />
            <?php else: ?>
            <img src="https://via.placeholder.com/800x350/10b981/ffffff?text=<?php echo urlencode($eskul['nama_ekskul']); ?>" alt="<?php echo $eskul['nama_ekskul']; ?>" class="h-48 md:h-56 w-full object-cover" />
            <?php endif; ?>
            <div class="p-5 flex-1">
              <div class="mb-2">
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 shadow"><?php echo $eskul['jumlah_anggota']; ?>/<?php echo $eskul['kuota']; ?></span>
              </div>
              <h5 class="text-lg font-semibold text-slate-900"><?php echo $eskul['nama_ekskul']; ?></h5>
              <p class="text-slate-500 text-sm mt-1"><?php echo substr($eskul['deskripsi'], 0, 100); ?>...</p>
              <div class="flex items-center gap-2 text-slate-600 text-sm mt-3">
                <i class="bi bi-person"></i>
                <span><?php echo $eskul['nama_pembina'] ?? 'Belum ada pembina'; ?></span>
              </div>
            </div>
            <div class="p-5 pt-0">
              <a href="?id=<?php echo $eskul['id']; ?>" class="inline-flex w-full justify-center items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
                <i class="bi bi-eye"></i> Lihat Detail
              </a>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>

    <?php
}

require_once 'includes/footer.php';